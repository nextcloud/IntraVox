<?php

declare(strict_types=1);

namespace OCA\IntraVox\Service\Reorder;

use OCA\IntraVox\Service\Homepage\HomepageResolverService;
use OCA\IntraVox\Service\Locator\PageLocator;
use OCP\Files\NotFoundException;

/**
 * Writes the stable sibling `order` field onto the direct children of a parent
 * folder, extracted verbatim from PageService::reorderSiblings().
 *
 * Self-contained: it does not touch the CRUD core (no createPage/getPage). The
 * cached directory-listing and file-content reads go through the injected
 * PageLocator (the same request caches PageService uses); the homepage check
 * comes from the injected HomepageResolverService and clearCache (private) is
 * passed in as a closure, and the already-resolved language folder is passed in
 * as an argument so the getLanguageFolder seam stays on PageService.
 *
 * PageServiceReorderTest pins the behaviour end-to-end through the PageService
 * delegator: sequential order writes, the folder-layout and loose-json layouts,
 * the homepage/config-file skips, foreign-id skips, the no-rewrite-when-correct
 * rule, and the exact pretty-printed/unescaped-unicode bytes.
 */
class PageReorderer {
    public function __construct(
        private PageLocator $locator,
        private HomepageResolverService $homepageResolver,
        private \OCA\IntraVox\Service\Cache\PageCacheInvalidator $cacheInvalidator,
    ) {
    }

    /**
     * @param \OCP\Files\Folder $languageFolder the resolved language folder
     *   (PageService's getLanguageFolder seam)
     */
    public function reorder(
        ?string $parentUniqueId,
        array $orderedChildIds,
        \OCP\Files\Folder $languageFolder
    ): void {
        // Resolve the parent folder whose direct children we are reordering.
        if ($parentUniqueId === null || $parentUniqueId === '') {
            $parentFolder = $languageFolder;
        } else {
            $parentResult = $this->locator->findPageByUniqueId($languageFolder, $parentUniqueId);
            if (!$parentResult || !isset($parentResult['folder'])) {
                throw new \Exception('Parent page not found: ' . $parentUniqueId);
            }
            $parentFolder = $parentResult['folder'];
        }

        // Build a uniqueId => page-JSON File map of this parent's DIRECT children
        // in a single cached directory pass. Reorder only touches direct children,
        // so we do NOT recurse into their subtrees (the old per-child
        // findPageByUniqueId() walked the whole subtree per id — O(N²) plus
        // uncached reads on a wide set). A child page is a subfolder holding
        // {folderName}.json (the canonical layout, mirrors buildPageTree); the
        // legacy loose {slug}.json at the parent level is also honoured.
        $isLanguageRoot = ($parentFolder->getPath() === $languageFolder->getPath());
        $childMap = [];
        foreach ($this->locator->cachedDirectoryListing($parentFolder) as $item) {
            $itemName = $item->getName();
            $file = null;

            if ($item->getType() === \OCP\Files\FileInfo::TYPE_FOLDER) {
                // Skip media/special folders (mirror findPageByUniqueId).
                if ($itemName === '_media' || $itemName === 'images' || $itemName === 'files' || $itemName === '.nomedia') {
                    continue;
                }
                try {
                    $candidate = $item->get($itemName . '.json');
                    if ($candidate instanceof \OCP\Files\File) {
                        $file = $candidate;
                    }
                } catch (NotFoundException $e) {
                    continue; // a folder without its page-JSON is not a page
                }
            } else {
                // Loose {slug}.json directly in the parent (legacy flat layout).
                if (substr($itemName, -5) !== '.json' || $itemName === 'home.json') {
                    continue; // home.json is the homepage, never ordered
                }
                if ($isLanguageRoot && ($itemName === 'navigation.json' || $itemName === 'footer.json' || $itemName === 'homepage.json')) {
                    continue; // root config files are not pages
                }
                $file = $item;
            }

            if ($file === null) {
                continue;
            }
            $data = json_decode($this->locator->cachedFileContent($file), true);
            if (is_array($data) && isset($data['uniqueId'])) {
                $childMap[$data['uniqueId']] = $file;
            }
        }

        foreach ($orderedChildIds as $index => $childId) {
            // The homepage is pinned first and never carries an order — skip the
            // legacy 'home' id as well as a configured pointer target.
            if ($childId === 'home' || $this->homepageResolver->isHomepage($childId)) {
                continue;
            }

            // A foreign id (not among this parent's direct children) is simply
            // absent from the map and is skipped, rather than reordered.
            $file = $childMap[$childId] ?? null;
            if ($file === null) {
                continue;
            }

            $data = json_decode($this->locator->cachedFileContent($file), true);
            if (!is_array($data)) {
                continue;
            }

            if (($data['order'] ?? null) !== $index) {
                $data['order'] = $index;
                $encoded = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
                $file->putContent($encoded);
            }
        }

        // Critical: without this the new order stays invisible for up to 5 min.
        $this->cacheInvalidator->invalidate();
    }
}
