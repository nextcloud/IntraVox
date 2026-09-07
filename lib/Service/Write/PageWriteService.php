<?php

declare(strict_types=1);

namespace OCA\IntraVox\Service\Write;

use OCA\IntraVox\Event\PageDeletedEvent;
use OCA\IntraVox\Exception\ForbiddenException;
use OCA\IntraVox\Exception\PageConflictException;
use OCA\IntraVox\Exception\PageNotFoundException;
use OCA\IntraVox\Service\PageIndexService;
use OCA\IntraVox\Service\Sanitize\VideoOriginalUrlPreserver;
use OCA\IntraVox\Service\Util\PageIdUtils;
use OCA\IntraVox\Service\Version\PageVersionService;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\IUserSession;
use Psr\Log\LoggerInterface;

/**
 * The page mutation cluster — create, update and delete of a page — carved out
 * of the PageService god-class. Currently owns deletePage and updatePage;
 * create follows.
 *
 * The protected folder seams stay on PageService; this service receives the
 * already-resolved language folder as an argument and the cross-language
 * lookups + isHomepage + validateAndSanitizePage + clearCache as $this-bound
 * closures, so the 26 seam subclasses keep intercepting with zero test edits.
 * PageCrudWriteTest / PageConcurrencyTest / PageUpdatePipelineTest pin the
 * behaviour byte-for-byte.
 */
final class PageWriteService {
    public function __construct(
        private PageIdUtils $idUtils,
        private IEventDispatcher $eventDispatcher,
        private LoggerInterface $logger,
        private IUserSession $userSession,
        private PageVersionService $pageVersionService,
        private PageIndexService $pageIndexService,
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

    /**
     * @param \OCP\Files\Folder $languageFolder the resolved getLanguageFolder seam
     * @param \Closure(\OCP\Files\Folder, string): ?array $locatePageAnyLanguage
     * @param \Closure(\OCP\Files\Folder, string): ?array $findPageById
     * @param \Closure(\OCP\Files\Folder): ?string $languageOfFolder
     * @param \Closure(): string $userLanguage
     * @param \Closure(array): array $validateAndSanitizePage
     * @param \Closure(?string): void $clearCache takes an optional page id
     */
    public function updatePage(
        string $id,
        array $data,
        \OCP\Files\Folder $languageFolder,
        \Closure $locatePageAnyLanguage,
        \Closure $findPageById,
        \Closure $languageOfFolder,
        \Closure $userLanguage,
        \Closure $validateAndSanitizePage,
        \Closure $clearCache
    ): array {
        // Save original ID before sanitization
        $originalId = $id;

        // Get the current user
        $user = $this->userSession->getUser();
        if (!$user) {
            throw new \InvalidArgumentException('No user in session');
        }

        $result = null;

        // Check for uniqueId pattern (page-xxx) BEFORE sanitization. Editing an
        // existing page writes back to wherever that page actually lives, which
        // is not necessarily the current user's own language folder (issue #90);
        // the isUpdateable() preflight below still gates the write.
        if (strpos($originalId, 'page-') === 0) {
            $result = $locatePageAnyLanguage($languageFolder, $originalId);
        }

        // Fallback to legacy ID lookup if not found by uniqueId
        if ($result === null) {
            try {
                $id = $this->idUtils->sanitizeId($originalId);
                $result = $findPageById($languageFolder, $id);
            } catch (\Exception $e) {
                throw new \InvalidArgumentException('Failed to find page: ' . $e->getMessage());
            }
        }

        if ($result === null) {
            throw new PageNotFoundException('Page not found: ' . $originalId);
        }

        // Get the file
        $file = $result['file'];

        // Preflight the write capability on the actual file/mount. permissionsFromNode
        // already gates canWrite on this, but a read-only GroupFolder member must get a
        // clean 403 here rather than a filesystem-level 400 if anything reported wrong
        // (issue #70). This also avoids Nextcloud core's share-access-list side effect
        // ("foreach() on null") that a doomed putContent would otherwise trigger.
        if (!$file->isUpdateable()) {
            throw new ForbiddenException('You do not have permission to edit this page');
        }

        try {
            $existingContent = $file->getContent();
            $existingData = json_decode($existingContent, true);
            if (!is_array($existingData)) {
                $existingData = [];
            }
        } catch (\Exception $e) {
            throw new \InvalidArgumentException('Failed to read existing page data: ' . $e->getMessage());
        }

        // Optimistic concurrency. putContent() replaces the WHOLE document, so
        // a save built on stale content erases everything written since — not a
        // field, the entire page. PageLockService catches the common case, but
        // locks expire after 15 minutes without a heartbeat, so a tab left open
        // comes back with stale content and no lock to stop it.
        //
        // The FILE's mtime is the version token, not the `modified` field in the
        // JSON: that field is whatever the client last sent (updatePage never
        // stamps it), so it would compare a value against itself. The mtime is
        // set by the filesystem on every write and cannot be spoofed by a stale
        // client.
        //
        // A client that sends no baseVersion — an older frontend, a script, an
        // import — is not blocked. This rejects only a save that demonstrably
        // started from an older version, never one that merely failed to say.
        $submittedBase = $data['baseVersion'] ?? null;
        if (is_numeric($submittedBase)) {
            $currentMtime = $file->getMTime();
            if ((int)$submittedBase < $currentMtime) {
                $this->logger->warning('[updatePage] stale write rejected', [
                    'pageId' => $originalId,
                    'baseVersion' => (int)$submittedBase,
                    'currentMtime' => $currentMtime,
                ]);
                throw new PageConflictException(
                    'This page was changed by someone else while you were editing it. '
                    . 'Reload the page to get the latest version before saving again.'
                );
            }
        }

        // Never persist the transport-only concurrency token.
        unset($data['baseVersion']);

        // Preserve uniqueId from existing data
        if (isset($existingData['uniqueId'])) {
            $data['uniqueId'] = $existingData['uniqueId'];
        }

        // Same for the translation group: it belongs to the page, not to the
        // payload a client happens to send. An editor saving from a UI that
        // knows nothing about translation groups (or an older frontend, or a
        // script) must not silently unlink the page from its other languages.
        //
        // Linking and unlinking are explicit operations with their own entry
        // points; an ordinary save is never one of them.
        if (isset($existingData['translationGroup'])) {
            $data['translationGroup'] = $existingData['translationGroup'];
        }

        // Preserve originalSrc for video widgets to prevent URL loss when whitelist changes
        $data = (new VideoOriginalUrlPreserver())->preserve($data, $existingData);

        try {
            $validatedData = $validateAndSanitizePage($data);
        } catch (\Exception $e) {
            $this->logger->error('[updatePage] Validation failed: ' . $e->getMessage(), [
                'pageId' => $originalId,
                'trace' => $e->getTraceAsString(),
            ]);
            throw new \InvalidArgumentException('Page validation failed: ' . $e->getMessage());
        }

        try {
            // Create version before update using GroupFolders VersionsBackend
            // GroupFolders 20.1.7+ has reliable versioning support
            $this->pageVersionService->createBeforeUpdate($file);

            // Update the file
            $file->putContent(json_encode($validatedData, JSON_PRETTY_PRINT));

        } catch (\Exception $e) {
            throw new \InvalidArgumentException('Failed to write updated page data: ' . $e->getMessage());
        }

        // Clear caches for this page (and uniqueId if present)
        $clearCache($originalId);
        if (isset($validatedData['uniqueId'])) {
            $clearCache($validatedData['uniqueId']);
        }

        // Update page metadata index (non-blocking — page was already saved).
        // Index the language the page actually LIVES in, never the editor's
        // own: since #90 an editor can save a page outside their own language,
        // and getUserLanguage() here wrote rows under the WRONG language. The
        // index is keyed (unique_id, language), so those rows did not match the
        // existing entry — every such save INSERTed a duplicate under a
        // language the page was never in, and nothing ever cleaned them up.
        // Mirrors createPageAtPath(), which already derives it from the folder.
        try {
            $folderPath = $result['folder']->getPath();
            $language = $languageOfFolder($result['folder']) ?? $userLanguage();
            $this->pageIndexService->indexPage($validatedData, $language, $folderPath, $file->getId(), $result['folder']->getId());
        } catch (\Exception $e) {
            $this->logger->warning('Failed to update page index', ['error' => $e->getMessage()]);
        }

        // Return data with id for frontend (id is derived from folder name)
        // Get id from folder name (for home page it's 'home', otherwise folder basename)
        $pageId = ($result['isHome'] ?? false) ? 'home' : $result['folder']->getName();

        // Hand back the version this write produced, so the editor can keep
        // saving without reloading. Without it the client would still hold the
        // token from page load, and its NEXT save would look stale against the
        // file it just wrote — a conflict with itself.
        return array_merge(
            ['id' => $pageId],
            $validatedData,
            ['baseVersion' => $file->getMTime()]
        );
    }
}
