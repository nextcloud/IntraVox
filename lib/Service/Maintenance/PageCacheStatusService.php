<?php

declare(strict_types=1);

namespace OCA\IntraVox\Service\Maintenance;

use OCA\IntraVox\Service\Folder\FolderContext;
use OCA\IntraVox\Service\Locator\PageLocator;
use OCA\IntraVox\Service\Util\PageIdUtils;
use OCP\Files\Cache\ICacheEntry;
use OCP\Files\NotFoundException;
use Psr\Log\LoggerInterface;

/**
 * The "is this page visible in the Files app yet?" diagnostic — extracted verbatim
 * from PageService::checkPageCacheStatus (facade elimination fase-4). A read-only
 * probe of Nextcloud's file cache for a freshly-created page: home.json is checked
 * directly, a regular page is resolved across languages and its folder's cache entry
 * inspected. No #70/#86 surface: it never recomputes permissions, only reports
 * visibility/indexing state, so it moves whole with no security consideration.
 *
 * The cross-language locate (locatePageForOperation) came with it — it was private
 * on PageService and had no other caller once this method left.
 */
final class PageCacheStatusService {
    public function __construct(
        private FolderContext $folders,
        private PageLocator $locator,
        private PageIdUtils $idUtils,
        private LoggerInterface $logger,
    ) {
    }

    /**
     * @return array{visible:bool, inCache:bool, message:string, fileId?:?int, folderId?:?int, path?:string, error?:string}
     */
    public function checkPageCacheStatus(string $pageId): array {
        try {
            $folder = $this->folders->languageFolder();

            // For home page, check the JSON file directly
            if ($pageId === 'home') {
                try {
                    $file = $folder->get('home.json');
                    $storage = $file->getStorage();
                    $cache = $storage->getCache();

                    // Try to get cache entry using the storage's cache directly
                    $cacheEntry = $cache->get($file->getInternalPath());

                    return [
                        'visible' => $cacheEntry !== false,
                        'inCache' => $cacheEntry !== false,
                        'fileId' => $cacheEntry !== false ? $cacheEntry->getId() : null,
                        'path' => $file->getPath(),
                        'message' => $cacheEntry !== false ? 'Page is visible in Files app' : 'Page created but waiting for indexing'
                    ];
                } catch (NotFoundException $e) {
                    return [
                        'visible' => false,
                        'inCache' => false,
                        'fileId' => null,
                        'message' => 'Home page file not found'
                    ];
                }
            }

            // For regular pages, check if the page folder exists in cache.
            // Resolve the page itself rather than assuming a folder of that name
            // sits in the caller's own language: this diagnostic reported
            // "Page folder not found" for perfectly healthy pages that simply
            // live in another language, which is a misleading support signal.
            try {
                $located = $this->locatePageForOperation($pageId);
                $pageFolder = $located['folder'] ?? $folder->get($pageId);
                $storage = $pageFolder->getStorage();
                $cache = $storage->getCache();

                // Try to get cache entry using the storage's cache directly
                $cacheEntry = $cache->get($pageFolder->getInternalPath());

                if ($cacheEntry !== false && $cacheEntry instanceof ICacheEntry) {
                    return [
                        'visible' => true,
                        'inCache' => true,
                        'folderId' => $cacheEntry->getId(),
                        'path' => $pageFolder->getPath(),
                        'message' => 'Page is visible in Files app'
                    ];
                } else {
                    // Folder exists on disk but not in cache
                    return [
                        'visible' => false,
                        'inCache' => false,
                        'folderId' => null,
                        'message' => 'Page created but waiting for Nextcloud to index it. This may take 5-15 minutes.'
                    ];
                }
            } catch (NotFoundException $e) {
                return [
                    'visible' => false,
                    'inCache' => false,
                    'folderId' => null,
                    'message' => 'Page folder not found'
                ];
            }
        } catch (\Exception $e) {
            $this->logger->error('Failed to check page cache status', [
                'error' => $e->getMessage(),
                'pageId' => $pageId
            ]);

            return [
                'visible' => false,
                'inCache' => false,
                'error' => $e->getMessage(),
                'message' => 'Unable to check cache status'
            ];
        }
    }

    /**
     * Resolve a page across every language folder (uniqueId first, then legacy
     * slug), for a caller that only has an id. The lazy IntraVox root is invoked
     * per-language inside the locator. Moved verbatim from PageService — its only
     * caller was checkPageCacheStatus.
     */
    private function locatePageForOperation(string $pageId): ?array {
        $folder = $this->folders->readLanguageFolder();
        $root = fn(): \OCP\Files\Folder => $this->folders->intraVox();

        if (strpos($pageId, 'page-') === 0) {
            $byUniqueId = $this->locator->locatePageAnyLanguage($root, $folder, $pageId);
            if ($byUniqueId !== null) {
                return $byUniqueId;
            }
        }

        return $this->locator->locatePageBySlugAnyLanguage($root, $folder, $this->idUtils->sanitizeId($pageId));
    }
}
