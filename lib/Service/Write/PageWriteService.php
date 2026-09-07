<?php

declare(strict_types=1);

namespace OCA\IntraVox\Service\Write;

use OCA\IntraVox\Event\PageDeletedEvent;
use OCA\IntraVox\Exception\PageNotFoundException;
use OCA\IntraVox\Service\Util\PageIdUtils;
use OCP\EventDispatcher\IEventDispatcher;
use Psr\Log\LoggerInterface;

/**
 * The page mutation cluster — create, update and delete of a page — carved out
 * of the PageService god-class. This is the first mutation method (deletePage);
 * update and create follow.
 *
 * The protected folder seams stay on PageService; this service receives the
 * already-resolved language folder as an argument and the cross-language
 * lookups + isHomepage + clearCache as $this-bound closures, so the 26 seam
 * subclasses keep intercepting with zero test edits. PageCrudWriteTest pins the
 * behaviour byte-for-byte.
 */
final class PageWriteService {
    public function __construct(
        private PageIdUtils $idUtils,
        private IEventDispatcher $eventDispatcher,
        private LoggerInterface $logger,
    ) {
    }

    /**
     * @param \OCP\Files\Folder $languageFolder the resolved getLanguageFolder seam
     * @param \Closure(\OCP\Files\Folder, string): ?array $locatePageAnyLanguage
     * @param \Closure(\OCP\Files\Folder, string): ?array $findPageById
     * @param \Closure(string): bool $isHomepage
     * @param \Closure(): void $clearCache
     */
    public function deletePage(
        string $id,
        \OCP\Files\Folder $languageFolder,
        \Closure $locatePageAnyLanguage,
        \Closure $findPageById,
        \Closure $isHomepage,
        \Closure $clearCache
    ): void {
        if ($id === 'home') {
            throw new \InvalidArgumentException('Cannot delete home page');
        }

        // Resolve by uniqueId (page-…) first, then fall back to legacy folder id.
        // Deletion follows the page across language folders, so a page the user
        // can see is also a page the user can delete (issue #90); the caller's
        // permission check still decides whether the delete is allowed.
        $result = strpos($id, 'page-') === 0
            ? $locatePageAnyLanguage($languageFolder, $id)
            : $findPageById($languageFolder, $this->idUtils->sanitizeId($id));

        if ($result === null) {
            throw new PageNotFoundException('Page not found: ' . $id);
        }

        // Normalize $id to the folder name for downstream index/event use.
        $id = isset($result['folder']) ? $result['folder']->getName() : $this->idUtils->sanitizeId($id);

        // Read the page JSON once for uniqueId (homepage guard + comment cleanup).
        $pageData = [];
        if (isset($result['file'])) {
            $decoded = json_decode($result['file']->getContent(), true);
            if (is_array($decoded)) {
                $pageData = $decoded;
            }
        }

        // The configured homepage cannot be deleted — reassign it first
        // (issue: configurable homepage). Distinguishable error so the UI can
        // prompt the user to pick another homepage.
        $resolvedUniqueId = $pageData['uniqueId'] ?? '';
        if ($resolvedUniqueId !== '' && $isHomepage($resolvedUniqueId)) {
            throw new \InvalidArgumentException('HOMEPAGE_PROTECTED');
        }

        // Get page data before deletion to retrieve uniqueId for comment cleanup
        try {
            $uniqueId = $pageData['uniqueId'] ?? '';

            // Dispatch event to cleanup comments/reactions before deleting the page
            if (!empty($uniqueId)) {
                $this->eventDispatcher->dispatchTyped(new PageDeletedEvent($id, $uniqueId));
            }
        } catch (\Exception $e) {
            // Log but don't block deletion if event dispatch fails
            $this->logger->warning('Failed to dispatch PageDeletedEvent for page ' . $id . ': ' . $e->getMessage());
        }

        // The index rows are deliberately LEFT IN PLACE. Deleting a page moves
        // its folder to the trashbin, which is reversible, so anything dropped
        // here would have to be rebuilt on restore — and restoring fires no
        // event at all (verified on NC34: trashing gives NodeDeletedEvent,
        // restoring gives nothing). Rows removed here could therefore never
        // come back, which is exactly why a restored page used to reappear in
        // Files but stay missing from the IntraVox page structure until
        // `occ intravox:reindex` was run by hand.
        //
        // Instead the rows stay and readers ask the filecache whether the file
        // is still live (PageIndexService::whereFileIsLive()). A trashed page has
        // its filecache path moved out of `files/`, so it drops out of every
        // listing without a flag to maintain, and a restore puts it back —
        // no event, no repair step. The rows are removed for good by
        // CacheCleanupListener once the trashbin is emptied.

        // Delete the entire folder (includes .json, images/, files/)
        $result['folder']->delete();

        // Clear caches
        $clearCache();
    }
}
