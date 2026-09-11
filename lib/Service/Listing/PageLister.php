<?php

declare(strict_types=1);

namespace OCA\IntraVox\Service\Listing;

use OCA\IntraVox\Service\Cache\PageCacheService;
use OCA\IntraVox\Service\Folder\FolderContext;
use OCA\IntraVox\Service\Locator\PageLocator;
use OCA\IntraVox\Service\PageIndexService;
use OCA\IntraVox\Service\Path\PageDataEnricher;
use OCA\IntraVox\Service\Path\PagePathHelper;
use OCA\IntraVox\Service\PermissionService;
use OCA\IntraVox\Service\Sanitize\PageShapeSanitizer;
use OCP\Files\File;
use OCP\Files\FileInfo;
use OCP\Files\Folder;
use OCP\Files\NotFoundException;
use Psr\Log\LoggerInterface;

/**
 * The LISTING domain: turns the page tree into flat page lists. Holds the
 * index-backed list (fromIndex, the fast path) AND the filesystem walks
 * (listAll / listAllWithContent / byFolderPath) it falls back to, carved verbatim
 * from PageService.
 *
 * The index is a CACHE over the filesystem, never an authority: fromIndex returns
 * null whenever the index cannot serve the language completely (no entries, a
 * query failure, an empty result, or a homepage that is missing from the
 * index), so the caller falls back to the slow-but-complete walk. Permissions
 * are still read per page from the live filesystem — they depend on GroupFolder
 * ACLs and the current user, so an index row cannot carry them and caching them
 * across users would leak access.
 *
 * Folder resolution comes from the injected FolderContext; the shape sanitizer,
 * request cache and enricher are the real read collaborators — all DI-first-class
 * now (fase-5): the former getIntraVoxFolder closure is FolderContext->intraVox()
 * (via languageOfFolder/folderFromAbsolutePath), and the enricher is an eagerly
 * injected PageDataEnricher. Building it is $userId-free (its ctor is inert, same
 * as the readService() rationale), and byFolderPath only INVOKES it on the
 * cache-MISS path, so the #70 request-cache hit stays $userId-free.
 * PageIndexLookupTest / PageWalkerSkipTest / PageBreadcrumbTest pin the behaviour.
 */
final class PageLister {
    public function __construct(
        private PageLocator $locator,
        private PageIndexService $pageIndexService,
        private PermissionService $permissionService,
        private LoggerInterface $logger,
        private FolderContext $folders,
        private PageShapeSanitizer $shape,
        private PageCacheService $cache,
        private PageDataEnricher $enricher,
    ) {
    }

    /**
     * @return array|null the page list, or null to fall back to the walk
     */
    public function fromIndex(\OCP\Files\Folder $folder): ?array {
        $language = $this->folders->languageOfFolder($folder);
        if ($language === null) {
            return null;
        }

        try {
            if (!$this->pageIndexService->hasEntries($language)) {
                return null;
            }
            $rows = $this->pageIndexService->getPagesByLanguage($language);
        } catch (\Throwable $e) {
            $this->logger->warning('[PageService] index listing failed, falling back to scan', [
                'language' => $language,
                'error' => $e->getMessage(),
            ]);
            return null;
        }

        if (empty($rows)) {
            return null;
        }

        // The homepage must be in the list. It lives as home.json at the
        // language ROOT rather than in a page folder, and on real installs it
        // turns out not to reach the index at all — so serving the index list
        // as-is would silently drop the homepage from the sidebar. Rather than
        // depend on that ever being fixed upstream, verify it here and fall
        // back to the walk when it is missing: a slow, complete list beats a
        // fast one with a hole in it.
        $homeUniqueId = null;
        try {
            $homeFile = $folder->get('home.json');
            if ($homeFile instanceof \OCP\Files\File) {
                $homeData = json_decode($this->locator->cachedFileContent($homeFile), true);
                $homeUniqueId = is_array($homeData) ? ($homeData['uniqueId'] ?? null) : null;
            }
        } catch (NotFoundException $e) {
            // No loose homepage in this language; nothing to guarantee.
        }
        if ($homeUniqueId !== null) {
            $indexedIds = array_column($rows, 'unique_id');
            if (!in_array($homeUniqueId, $indexedIds, true)) {
                return null;
            }
        }

        $pages = [];
        foreach ($rows as $row) {
            if (empty($row['unique_id']) || empty($row['path'])) {
                continue;
            }

            // Resolve the page folder to read permissions from. A row pointing
            // at something the user cannot reach is skipped rather than served
            // without permissions — the same mount-scoped resolution the
            // uniqueId lookup uses, so the index can never widen access.
            $pageFolder = $this->locator->folderFromAbsolutePath($this->folders->intraVox(), (string)$row['path']);
            if ($pageFolder === null) {
                continue;
            }

            $pages[] = [
                'uniqueId' => (string)$row['unique_id'],
                'title' => (string)($row['title'] ?? ''),
                'modified' => (int)($row['modified_at'] ?? 0),
                'status' => (string)($row['status'] ?? 'published'),
                'permissions' => $this->permissionService->permissionsFromNode($pageFolder),
            ];
        }

        return $pages;
    }

    /**
     * The full page list for the current read language: index fast-path when it
     * can serve the language completely, else the filesystem walk. Both branches
     * come back in the same stable order.
     */
    public function listAll(): array {
        $folder = $this->folders->readLanguageFolder();

        // Titles and statuses come from the index when it has this language,
        // which removes the read + json_decode of every page file. Permissions
        // still come from the filesystem: they depend on GroupFolder ACLs and
        // on who is asking, so they are not derivable from an index row and
        // must never be cached across users.
        $indexed = $this->fromIndex($folder);
        if ($indexed !== null) {
            return $this->inStableOrder($indexed);
        }

        $intraVoxFolder = $this->folders->intraVox();
        $pages = [];

        // Get base path for relative path calculation
        $basePath = $intraVoxFolder->getPath();

        // Check for home.json in root
        try {
            $homeFile = $folder->get('home.json');
            $content = $homeFile instanceof File ? $homeFile->getContent() : null;
            $data = $content !== null ? json_decode($content, true) : null;

            if ($homeFile instanceof File && $data && isset($data['uniqueId'], $data['title'])) {
                // Calculate relative path from IntraVox root
                $relativePath = substr($folder->getPath(), strlen($basePath) + 1);

                $pages[] = [
                    'uniqueId' => $data['uniqueId'],
                    'title' => $data['title'],
                    'modified' => $data['modified'] ?? $homeFile->getMTime(),
                    'status' => $data['status'] ?? 'published',
                    'permissions' => $this->permissionService->permissionsFromNode($folder)
                ];
            }
        } catch (NotFoundException $e) {
            // No home page yet
        }

        // Recursively find all pages in subfolders
        $this->walkPlain($folder, $pages, $basePath);

        return $this->inStableOrder($pages);
    }

    /**
     * List all pages with full content (including layout), a single filesystem
     * traversal for search — eliminates the N+1 of listAll() + getPage() each.
     */
    public function listAllWithContent(): array {
        $folder = $this->folders->readLanguageFolder();
        $pages = [];

        // Check for home.json in root
        try {
            $homeFile = $folder->get('home.json');
            $content = $homeFile instanceof File ? $homeFile->getContent() : null;
            $data = $content !== null ? json_decode($content, true) : null;

            if ($homeFile instanceof File && $data && isset($data['uniqueId'])) {
                // fileId lets callers (search) join MetaVox metadata onto the page.
                $data['fileId'] = $homeFile->getId();
                $pages[] = $this->shape->sanitizePage($data);
            }
        } catch (NotFoundException $e) {
            // No home page yet
        }

        // Recursively find all pages with full content
        $this->walkWithContent($folder, $pages);

        return $pages;
    }

    /**
     * Resolve the page whose folder is at $folderPath (relative to IntraVox root),
     * enriched + sanitised. Request-cached; enrich gates canWrite/canEdit (#70).
     */
    public function byFolderPath(string $folderPath): ?array {
        // Check request-level cache first
        if ($this->cache->hasFolderPath($folderPath)) {
            return $this->cache->getFolderPath($folderPath);
        }

        try {
            $intraVoxFolder = $this->folders->intraVox();
            $folder = $intraVoxFolder->get($folderPath);

            if (!($folder instanceof Folder)) {
                $this->cache->setFolderPath($folderPath, null);
                return null;
            }

            // Look for a JSON file in this folder (page definition)
            $files = $this->locator->cachedDirectoryListing($folder);
            foreach ($files as $file) {
                if ($file instanceof File &&
                    pathinfo($file->getName(), PATHINFO_EXTENSION) === 'json' &&
                    $file->getName() !== 'images.json') {

                    $content = $file->getContent();
                    $data = json_decode($content, true);

                    if ($data && isset($data['uniqueId'])) {
                        // Enrich with path data (file gates canWrite/canEdit, #70)
                        $data = $this->enricher->enrich($data, $folder, $file);
                        $result = $this->shape->sanitizePage($data);
                        $this->cache->setFolderPath($folderPath, $result);
                        return $result;
                    }
                }
            }
        } catch (\Exception $e) {
            // Folder or page not found
            $this->logger->debug("Could not find page at path {$folderPath}: " . $e->getMessage());
        }

        $this->cache->setFolderPath($folderPath, null);
        return null;
    }

    /**
     * Recursively append the pages (title/status/permissions, no content) under
     * $folder. Public because getPageCountByLanguage (language-status domain)
     * shares this walk.
     *
     * @param array<int,array> $pages accumulator, appended by reference
     */
    public function walkPlain($folder, array &$pages, string $basePath = ''): void {
        foreach ($this->locator->cachedDirectoryListing($folder) as $item) {
            if ($item->getType() === FileInfo::TYPE_FOLDER) {
                $folderName = $item->getName();

                // Skip asset and infrastructure folders. The underscore rule
                // matters: _templates holds page-shaped JSON, and every walker
                // that forgot to skip it served TEMPLATES as pages — search
                // returned "Knowledge Base" the template above the real page,
                // and an empty index made them appear in the page list. One
                // rule for every walker, same as buildPageTree.
                if (PagePathHelper::isInfrastructureFolder($folderName)) {
                    continue;
                }

                // Look for {foldername}.json inside the folder
                try {
                    $jsonFile = $item->get($folderName . '.json');

                    // Check if file is readable before trying to get content
                    if (!$jsonFile->isReadable()) {
                        continue;
                    }

                    // Use cached file content to avoid repeated reads
                    $content = $jsonFile instanceof File
                        ? $this->locator->cachedFileContent($jsonFile)
                        : @$jsonFile->getContent();

                    if ($content === false || $content === null) {
                        continue;
                    }

                    $data = json_decode($content, true);

                    if ($data && isset($data['uniqueId'], $data['title'])) {
                        $pages[] = [
                            'uniqueId' => $data['uniqueId'],
                            'title' => $data['title'],
                            'modified' => $data['modified'] ?? $jsonFile->getMTime(),
                            'status' => $data['status'] ?? 'published',
                            'permissions' => $this->permissionService->permissionsFromNode($item)
                        ];
                    }
                } catch (\Exception $e) {
                    // This folder doesn't contain a valid page or can't be read, continue
                } catch (\Throwable $e) {
                    // Catch any other errors including PHP errors
                    continue;
                }

                // Recursively search subfolders
                $this->walkPlain($item, $pages, $basePath);
            }
        }
    }

    /**
     * Recursively append the pages WITH full content (layout + fileId) under
     * $folder, sanitised.
     *
     * @param array<int,array> $pages accumulator, appended by reference
     */
    private function walkWithContent($folder, array &$pages): void {
        foreach ($this->locator->cachedDirectoryListing($folder) as $item) {
            if ($item->getType() === FileInfo::TYPE_FOLDER) {
                $folderName = $item->getName();

                // Skip special folders
                if (PagePathHelper::isInfrastructureFolder($folderName)) {
                    continue;
                }

                // Look for {foldername}.json inside the folder
                try {
                    $jsonFile = $item->get($folderName . '.json');

                    if (!$jsonFile->isReadable()) {
                        continue;
                    }

                    // Use cached file content to avoid repeated reads
                    $content = $jsonFile instanceof File
                        ? $this->locator->cachedFileContent($jsonFile)
                        : @$jsonFile->getContent();

                    if ($content === false || $content === null) {
                        continue;
                    }

                    $data = json_decode($content, true);

                    if ($data && isset($data['uniqueId'])) {
                        // fileId lets callers (search) join MetaVox metadata onto the page.
                        $data['fileId'] = $jsonFile->getId();
                        $pages[] = $this->shape->sanitizePage($data);
                    }
                } catch (\Exception $e) {
                    // This folder doesn't contain a valid page
                } catch (\Throwable $e) {
                    continue;
                }

                // Recursively search subfolders
                $this->walkWithContent($item, $pages);
            }
        }
    }

    /**
     * One deterministic order for the page listing (title, then uniqueId as the
     * tie-breaker; byte comparison, not locale collation — the order exists to be
     * STABLE so a cursor can rely on it). Verbatim from PageService::inStableOrder
     * (which is retained there as a reflection anchor).
     *
     * @param list<array<string,mixed>> $pages
     * @return list<array<string,mixed>>
     */
    private function inStableOrder(array $pages): array {
        usort($pages, static function (array $a, array $b): int {
            return [(string)($a['title'] ?? ''), (string)($a['uniqueId'] ?? '')]
                <=> [(string)($b['title'] ?? ''), (string)($b['uniqueId'] ?? '')];
        });

        return $pages;
    }
}
