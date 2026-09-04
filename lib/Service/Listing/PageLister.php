<?php

declare(strict_types=1);

namespace OCA\IntraVox\Service\Listing;

use OCA\IntraVox\Service\Locator\PageLocator;
use OCA\IntraVox\Service\PageIndexService;
use OCA\IntraVox\Service\PermissionService;
use OCP\Files\NotFoundException;
use Psr\Log\LoggerInterface;

/**
 * Builds the page list from the index instead of walking the filesystem tree,
 * extracted verbatim from PageService::listPagesFromIndex().
 *
 * The index is a CACHE over the filesystem, never an authority: this returns
 * null whenever the index cannot serve the language completely (no entries, a
 * query failure, an empty result, or a homepage that is missing from the
 * index), so the caller falls back to the slow-but-complete walk. Permissions
 * are still read per page from the live filesystem — they depend on GroupFolder
 * ACLs and the current user, so an index row cannot carry them and caching them
 * across users would leak access.
 *
 * The one PageService seam this needs — the IntraVox root folder, used by
 * languageOfFolder/folderFromAbsolutePath — is passed in as a closure so the
 * protected getIntraVoxFolder seam stays on PageService. PageIndexLookupTest
 * pins the behaviour end-to-end through the PageService delegator.
 */
final class PageLister {
    /**
     * @param \Closure(): \OCP\Files\Folder $intraVoxFolder resolves the IntraVox
     *   root (PageService's getIntraVoxFolder seam)
     */
    public function __construct(
        private PageLocator $locator,
        private PageIndexService $pageIndexService,
        private PermissionService $permissionService,
        private LoggerInterface $logger,
        private \Closure $intraVoxFolder,
    ) {
    }

    /**
     * @return array|null the page list, or null to fall back to the walk
     */
    public function fromIndex(\OCP\Files\Folder $folder): ?array {
        $language = $this->locator->languageOfFolder(($this->intraVoxFolder)(), $folder);
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
            $pageFolder = $this->locator->folderFromAbsolutePath(($this->intraVoxFolder)(), (string)$row['path']);
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
}
