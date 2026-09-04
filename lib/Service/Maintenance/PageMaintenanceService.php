<?php
declare(strict_types=1);

namespace OCA\IntraVox\Service\Maintenance;

use OCA\IntraVox\Service\Locator\PageLocator;
use OCA\IntraVox\Service\PageIndexService;
use OCA\IntraVox\Service\Path\PagePathHelper;
use OCA\IntraVox\Service\Sanitize\HtmlSanitizer;
use OCP\Files\File;
use OCP\Files\Folder;
use Psr\Log\LoggerInterface;

/**
 * The CLI maintenance operations extracted from PageService (cluster P/Q, Phase
 * 4): entity repair and index rebuild, driven by occ RepairEntitiesCommand /
 * ReindexPagesCommand. Verbatim bodies; the only structural change is that the
 * root folder is passed IN rather than resolved via a folder seam — PageService
 * keeps thin facades that resolve the root and delegate here, so the CLI commands
 * and the seam-overriding tests stay unchanged.
 */
class PageMaintenanceService {

    public function __construct(
        private PageIndexService $pageIndexService,
        private PageLocator $locator,
        private HtmlSanitizer $htmlSanitizer,
        private LoggerInterface $logger,
    ) {
    }

    /**
     * Walk every language folder under $root, decoding entity-encoded plain text
     * in each page JSON.
     *
     * @return array{scanned:int, changed:int, files:string[]}
     */
    public function repairEntities(Folder $root, bool $dryRun = false): array {
        $stats = ['scanned' => 0, 'changed' => 0, 'files' => []];
        foreach ($this->locator->cachedDirectoryListing($root) as $langFolder) {
            if (!($langFolder instanceof Folder)) {
                continue;
            }
            // Language folders are 2–3 letter codes; skip _media/_resources/etc.
            if (!preg_match('/^[a-z]{2,3}$/', $langFolder->getName())) {
                continue;
            }
            $this->repairEntitiesInFolder($langFolder, $dryRun, $stats);
        }
        return $stats;
    }

    /**
     * Recurse a folder, decoding entity-encoded plain-text in each page JSON.
     *
     * @param array{scanned:int, changed:int, files:string[]} $stats
     */
    private function repairEntitiesInFolder(Folder $folder, bool $dryRun, array &$stats): void {
        foreach ($this->locator->cachedDirectoryListing($folder) as $node) {
            if ($node instanceof File && str_ends_with($node->getName(), '.json')) {
                // Only page JSONs carry the fields we repair; navigation.json,
                // footer.json, homepage.json are handled/normalised elsewhere.
                $name = $node->getName();
                if (in_array($name, ['navigation.json', 'footer.json', 'homepage.json'], true)) {
                    continue;
                }
                $stats['scanned']++;
                try {
                    $data = json_decode($node->getContent(), true);
                    if (!is_array($data) || !isset($data['title'])) {
                        continue;
                    }
                    $before = json_encode($data);
                    $this->decodePlainTextFields($data);
                    $after = json_encode($data);
                    if ($before !== $after) {
                        $stats['changed']++;
                        $stats['files'][] = $node->getPath();
                        if (!$dryRun) {
                            $node->putContent(json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
                        }
                    }
                } catch (\Throwable $e) {
                    $this->logger->warning('[PageService] repairEntities skipped ' . $node->getPath() . ': ' . $e->getMessage());
                }
            } elseif ($node instanceof Folder) {
                $this->repairEntitiesInFolder($node, $dryRun, $stats);
            }
        }
    }

    /**
     * Rebuild the page index from the filesystem, which is the source of truth.
     *
     * The index is a derived structure: pages live as JSON on disk, and the
     * index only exists so lookups do not have to walk that tree. Anything
     * that writes page files outside the service — a restore, a manual copy in
     * the Files app, an `occ files:scan`, an older IntraVox version, or simply
     * a bug — leaves it stale. Without a rebuild, a stale index is unfixable
     * short of editing the database by hand, which is why no read path may be
     * built on the index until this exists.
     *
     * Clears and repopulates in one pass rather than diffing: at intranet
     * scale a full rebuild is seconds, and a diff would have to solve exactly
     * the "which rows are wrong" question that a corrupt index cannot answer.
     *
     * Deliberately does NOT infer or repair translation groupings — it records
     * only what the files say. (WPML shipped a bug where an update silently
     * re-linked translations an editor had deliberately unlinked; guessing
     * relationships during a repair is how that happens.)
     *
     * @param bool $dryRun count what would be indexed without writing
     * @return array{scanned:int, indexed:int, languages:array<string,int>}
     */
    public function rebuildIndex(Folder $root, bool $dryRun = false): array {
        $stats = ['scanned' => 0, 'indexed' => 0, 'languages' => []];

        $languageFolders = [];
        foreach ($this->locator->cachedDirectoryListing($root) as $node) {
            if (!($node instanceof Folder)) {
                continue;
            }
            // Language folders are 2–3 letter codes; skips _media/_resources.
            if (!preg_match('/^[a-z]{2,3}$/', $node->getName())) {
                continue;
            }
            $languageFolders[] = $node;
        }

        // Clear only after the tree is readable: wiping first and then failing
        // to read would leave the install with no index at all.
        if (!$dryRun) {
            $this->pageIndexService->clearAll();
        }

        foreach ($languageFolders as $langFolder) {
            $lang = $langFolder->getName();
            $stats['languages'][$lang] = 0;
            $this->rebuildIndexInFolder($langFolder, $lang, $dryRun, $stats);
        }

        return $stats;
    }

    /**
     * Recurse one language folder, indexing every page JSON found.
     *
     * @param array{scanned:int, indexed:int, languages:array<string,int>} $stats
     */
    private function rebuildIndexInFolder(
        Folder $folder,
        string $language,
        bool $dryRun,
        array &$stats
    ): void {
        // Two passes over one listing: subfolder names first, because whether a
        // JSON file IS a page depends on them (see below).
        $listing = $this->locator->cachedDirectoryListing($folder);
        $subfolders = [];
        foreach ($listing as $node) {
            if ($node instanceof Folder) {
                $subfolders[$node->getName()] = $node;
            }
        }

        foreach ($subfolders as $name => $node) {
            // Media, asset and infrastructure folders hold no pages.
            if (PagePathHelper::isInfrastructureFolder($name)) {
                continue;
            }
            $this->rebuildIndexInFolder($node, $language, $dryRun, $stats);
        }

        foreach ($listing as $node) {
            if (!($node instanceof File) || !str_ends_with($node->getName(), '.json')) {
                continue;
            }
            // Per-language config files are not pages.
            if (in_array($node->getName(), ['navigation.json', 'footer.json', 'homepage.json'], true)) {
                continue;
            }

            // Only files that fit the PAGE MODEL are pages. Indexing every JSON
            // in sight put loose files (POC data dropped beside a real page)
            // into the index, and since 2.0 serves the page list FROM the
            // index, those rows became ghost entries that 404 when clicked —
            // the tree never showed them and getPage cannot resolve them.
            $base = substr($node->getName(), 0, -5);
            if ($base === $folder->getName()) {
                $pageFolder = $folder;               // {slug}/{slug}.json — canonical
            } elseif ($node->getName() === 'home.json' && $folder->getName() === $language) {
                $pageFolder = $folder;               // language-root homepage
            } elseif (isset($subfolders[$base])) {
                $pageFolder = $subfolders[$base];    // legacy beside-layout: {slug}.json next to {slug}/
            } else {
                continue;                            // loose JSON — not a page
            }

            $stats['scanned']++;
            try {
                $data = json_decode($node->getContent(), true);
                if (!is_array($data) || empty($data['uniqueId'])) {
                    // A JSON file without a uniqueId is not an indexable page.
                    continue;
                }
                if (!$dryRun) {
                    $this->pageIndexService->indexPage(
                        $data,
                        $language,
                        // The page's OWN folder, matching createPage/updatePage —
                        // locateViaIndex derives its candidates from this path.
                        $pageFolder->getPath(),
                        $node->getId(),
                        $pageFolder->getId()
                    );
                }
                $stats['indexed']++;
                $stats['languages'][$language]++;
            } catch (\Throwable $e) {
                // One unreadable file must not abort the whole rebuild.
                $this->logger->warning(
                    '[PageService] rebuildIndex skipped ' . $node->getPath() . ': ' . $e->getMessage()
                );
            }
        }
    }

    /**
     * Decode HTML entities in the plain-text fields of a page-data array,
     * in place: title, and each widget's content/alt/title and link titles.
     */
    private function decodePlainTextFields(array &$data): void {
        if (isset($data['title']) && is_string($data['title'])) {
            $data['title'] = $this->htmlSanitizer->decodeEntitiesRecursive($data['title']);
        }
        $rows = $data['layout']['rows'] ?? null;
        if (!is_array($rows)) {
            return;
        }
        foreach ($rows as &$row) {
            if (isset($row['sectionTitle']) && is_string($row['sectionTitle'])) {
                $row['sectionTitle'] = $this->htmlSanitizer->decodeEntitiesRecursive($row['sectionTitle']);
            }
            $columns = $row['columns'] ?? (isset($row['widgets']) ? [$row] : []);
            foreach ($columns as &$col) {
                foreach (($col['widgets'] ?? []) as &$widget) {
                    foreach (['content', 'alt', 'title'] as $field) {
                        if (isset($widget[$field]) && is_string($widget[$field])) {
                            $widget[$field] = $this->htmlSanitizer->decodeEntitiesRecursive($widget[$field]);
                        }
                    }
                    foreach (($widget['links'] ?? []) as &$link) {
                        if (isset($link['title']) && is_string($link['title'])) {
                            $link['title'] = $this->htmlSanitizer->decodeEntitiesRecursive($link['title']);
                        }
                    }
                    unset($link);
                }
                unset($widget);
            }
            unset($col);
        }
        unset($row);
    }
}
