<?php

declare(strict_types=1);

namespace OCA\IntraVox\Service\Write;

use OCA\IntraVox\Event\PageDeletedEvent;
use OCA\IntraVox\Exception\ForbiddenException;
use OCA\IntraVox\Exception\PageConflictException;
use OCA\IntraVox\Exception\PageNotFoundException;
use OCA\IntraVox\Service\Folder\FolderContext;
use OCA\IntraVox\Service\Homepage\HomepageResolverService;
use OCA\IntraVox\Service\LanguageService;
use OCA\IntraVox\Service\PageIndexService;
use OCA\IntraVox\Service\Sanitize\VideoOriginalUrlPreserver;
use OCA\IntraVox\Service\Util\PageIdUtils;
use OCA\IntraVox\Service\Version\PageVersionService;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\Files\NotFoundException;
use OCP\IUserSession;
use Psr\Log\LoggerInterface;

/**
 * The page mutation cluster — create, update and delete of a page — carved out
 * of the PageService god-class. Currently owns deletePage and updatePage;
 * create follows.
 *
 * The protected folder seams stay on PageService; this service receives the
 * already-resolved language folder as an argument, the homepage check from the
 * injected HomepageResolverService, page sanitisation from the injected
 * PageShapeSanitizer, and the cross-language lookups as $this-bound closures, so
 * the seam subclasses keep intercepting with zero test edits.
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
        private LanguageService $languageService,
        private FolderContext $folders,
        private \OCA\IntraVox\Service\Locator\PageLocator $locator,
        private HomepageResolverService $homepageResolver,
        private \OCA\IntraVox\Service\Cache\PageCacheInvalidator $cacheInvalidator,
        private \OCA\IntraVox\Service\Sanitize\PageShapeSanitizer $shape,
        private \OCA\IntraVox\Service\Media\PageMediaService $media,
        private \OCA\IntraVox\Service\Cache\PageCacheService $pageCache,
    ) {
    }

    /**
     * The language folder is self-sourced from the injected FolderContext, but
     * resolved only AFTER the cheap $id==='home' guard: resolving it can
     * create-on-miss or throw, and the pre-carve monolith checked 'home' first.
     * (It used to arrive as a $this-bound closure so the caller controlled that
     * timing; now the timing lives here, at the one call site, and the closure is
     * gone — byte-identical, since the closure only ever forwarded to
     * folders->languageFolder().)
     */
    public function deletePage(
        string $id
    ): void {
        if ($id === 'home') {
            throw new \InvalidArgumentException('Cannot delete home page');
        }

        // Resolved only after the home guard (see above): the seam can
        // create-on-miss / throw, so it must not run for a rejected 'home' delete.
        $languageFolderNode = $this->folders->languageFolder();

        // Resolve by uniqueId (page-…) first, then fall back to legacy folder id.
        // Deletion follows the page across language folders, so a page the user
        // can see is also a page the user can delete (issue #90); the caller's
        // permission check still decides whether the delete is allowed. The
        // cross-language locate takes a lazy IntraVox root (invoked per language
        // iteration inside the locator) — the same rootClosure the delegator built.
        $result = strpos($id, 'page-') === 0
            ? $this->locator->locatePageAnyLanguage(fn(): \OCP\Files\Folder => $this->folders->intraVox(), $languageFolderNode, $id)
            : $this->locator->findPageById($languageFolderNode, $this->idUtils->sanitizeId($id));

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
        if ($resolvedUniqueId !== '' && $this->homepageResolver->isHomepage($resolvedUniqueId)) {
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
        $this->cacheInvalidator->invalidate();
    }

    /**
     * The language folder is self-sourced from the injected FolderContext, but
     * resolved only AFTER the cheap !$user guard: resolving it can create-on-miss
     * or throw, and the pre-carve monolith checked the session user first. (The
     * folder + languageOfFolder + userLanguage seams used to arrive as $this-bound
     * closures so the caller controlled that timing; now the timing lives here and
     * the closures are gone — byte-identical, since each only forwarded to the
     * matching FolderContext method.)
     */
    public function updatePage(
        string $id,
        array $data
    ): array {
        // Save original ID before sanitization
        $originalId = $id;

        // Get the current user
        $user = $this->userSession->getUser();
        if (!$user) {
            throw new \InvalidArgumentException('No user in session');
        }

        // Resolved only after the user guard (see above): the seam can
        // create-on-miss / throw, so it must not run for a rejected no-user update.
        $languageFolderNode = $this->folders->languageFolder();

        $result = null;

        // Check for uniqueId pattern (page-xxx) BEFORE sanitization. Editing an
        // existing page writes back to wherever that page actually lives, which
        // is not necessarily the current user's own language folder (issue #90);
        // the isUpdateable() preflight below still gates the write.
        if (strpos($originalId, 'page-') === 0) {
            $result = $this->locator->locatePageAnyLanguage(fn(): \OCP\Files\Folder => $this->folders->intraVox(), $languageFolderNode, $originalId);
        }

        // Fallback to legacy ID lookup if not found by uniqueId
        if ($result === null) {
            try {
                $id = $this->idUtils->sanitizeId($originalId);
                $result = $this->locator->findPageById($languageFolderNode, $id);
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
            $validatedData = $this->shape->validateAndSanitizePage($data);
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
        $this->cacheInvalidator->invalidate($originalId);
        if (isset($validatedData['uniqueId'])) {
            $this->cacheInvalidator->invalidate($validatedData['uniqueId']);
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
            $language = $this->folders->languageOfFolder($result['folder']) ?? $this->folders->userLanguage();
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

    /**
     * Create a page at a specific path with parent support.
     *
     * The folder-substrate concerns (readLanguageFolder / userLanguage /
     * languageOfFolder) come from the injected FolderContext. The write-exclusive
     * helpers (resolveExistingFolderPath, slugTakenIn, scanPageFolder,
     * getOrCreateFolderPath) co-locate here as private methods. validateDepth
     * (shared with movePage) stays on PageService and comes in as a closure.
     *
     * @param \Closure(string): void $validateDepth
     */
    public function createPageAtPath(
        string $pageId,
        array $data,
        ?string $parentPath,
        \Closure $validateDepth
    ): array {
        $language = $this->folders->userLanguage();

        // Determine target folder
        if ($parentPath) {
            // Validate depth before creating
            $validateDepth($parentPath);

            // Get or create parent folder path
            $targetFolder = $this->getOrCreateFolderPath($parentPath);
        } else {
            // No parent = create at the root of the language being VIEWED, so a
            // new page lands in the structure the author is actually working in
            // rather than in their profile language. readLanguageFolder()
            // resolves own language → recommended → en, and falls back to the
            // author's own folder when nothing else resolves.
            $targetFolder = $this->folders->readLanguageFolder();
        }

        // Preflight: creating a page writes a file (and a folder) into $targetFolder.
        // A read-only GroupFolder member must get a clean 403 here instead of a
        // filesystem-level 400 (issue #70).
        if (!$targetFolder->isCreatable()) {
            throw new ForbiddenException('You do not have permission to create a page here');
        }

        // The folder whose path the index must store: the one holding the page
        // JSON. For home that is the language root itself; for every other page
        // it is the page's OWN folder, set in the else-branch below. Indexing
        // the PARENT here made every freshly created page unresolvable via the
        // index (the lookup derives candidates from this path and the verify
        // step then rejects them), silently demoting each first lookup to the
        // full scan until the next save or reindex repaired the row.
        $indexFolder = $targetFolder;

        // Special handling for home page (always at root)
        if ($pageId === 'home') {
            $file = $targetFolder->newFile('home.json');
            $file->putContent(json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

            // Create _media folder for home if it doesn't exist
            try {
                $mediaFolder = $targetFolder->get('_media');
                $this->media->createMediaFolderMarker($mediaFolder);
            } catch (NotFoundException $e) {
                $mediaFolder = $targetFolder->newFolder('_media');
                $this->media->createMediaFolderMarker($mediaFolder);
            }

            $this->scanPageFolder($targetFolder);
        } else {
            // Create folder for page
            try {
                $pageFolder = $targetFolder->newFolder($pageId);
            } catch (\Exception $e) {
                throw new \InvalidArgumentException('Failed to create page folder: ' . $e->getMessage());
            }

            // Create {pageId}.json inside the folder
            try {
                $file = $pageFolder->newFile($pageId . '.json');
                $file->putContent(json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            } catch (\Exception $e) {
                throw new \InvalidArgumentException('Failed to create page file: ' . $e->getMessage());
            }

            // Create _media subfolder
            try {
                $mediaFolder = $pageFolder->newFolder('_media');
                // Add a .nomedia file to indicate this is a special folder
                $this->media->createMediaFolderMarker($mediaFolder);
            } catch (\Exception $e) {
                // Media folder might already exist, that's okay
                try {
                    $mediaFolder = $pageFolder->get('_media');
                    $this->media->createMediaFolderMarker($mediaFolder);
                } catch (\Exception $ex) {
                    // Couldn't get media folder
                }
            }

            $this->scanPageFolder($pageFolder);

            // Cache the folder reference for immediate reuse (e.g., when copying media from template).
            // Self-sourced (fase-7 T6): the injected PageCacheService IS the same
            // per-request singleton PageService hands every service, so this write
            // lands in the one pageFolders map findPageFolder reads back.
            if (isset($data['uniqueId'])) {
                $this->pageCache->setPageFolder($data['uniqueId'], $pageFolder);
            }

            // Every non-home page is indexed under its OWN folder.
            $indexFolder = $pageFolder;
        }

        // Update page metadata index (non-blocking — page was already saved).
        // Index the language the page actually LANDED in, which is not always
        // the author's own (a sub-page follows its parent's language).
        //
        // The stored path is the ABSOLUTE path of the folder holding the page
        // JSON, matching updatePage() and rebuildIndex(). This used to store a
        // relative parent path here and an absolute one everywhere else, so the
        // same table held two incompatible path shapes — which breaks both the
        // index lookup (it resolves the stored path) and repathSubtree() (it
        // matches on a path prefix).
        try {
            $language = $this->folders->languageOfFolder($indexFolder) ?? $this->folders->userLanguage();
            $this->pageIndexService->indexPage(
                $data,
                $language,
                $indexFolder->getPath(),
                $file->getId(),
                $indexFolder->getId()
            );
        } catch (\Exception $e) {
            $this->logger->warning('Failed to index new page', ['error' => $e->getMessage()]);
        }

        // Return data with id for frontend (id is derived from folder name)
        return array_merge(['id' => $pageId], $data);
    }

    /**
     * Resolve an EXISTING folder path for the slug-dedup scan (no create). Write-
     * exclusive helper co-located from PageService. Folder resolution comes from
     * the injected FolderContext.
     */
    private function resolveExistingFolderPath(
        ?string $parentPath
    ): ?\OCP\Files\Folder {
        try {
            if ($parentPath === null || trim($parentPath, '/') === '') {
                // No parent = the language root createPageAtPath() falls back to.
                return $this->folders->readLanguageFolder();
            }

            $pathParts = explode('/', trim($parentPath, '/'));

            $currentFolder = null;
            // explode() always yields >=1 element, so the old `count($pathParts) > 0`
            // guard was always true (dead) — dropped; $pathParts[0] always exists.
            if ($this->languageService->isLanguageAvailable($pathParts[0])) {
                $langCode = array_shift($pathParts);
                try {
                    $candidate = $this->folders->intraVox()->get($langCode);
                    if ($candidate instanceof \OCP\Files\Folder) {
                        $currentFolder = $candidate;
                    }
                } catch (NotFoundException $e) {
                    // No folder for that language — fall through to the author's own.
                }
            }
            if ($currentFolder === null) {
                $currentFolder = $this->folders->languageFolder();
            }

            foreach ($pathParts as $folderName) {
                try {
                    $next = $currentFolder->get($folderName);
                } catch (NotFoundException $e) {
                    return null;
                }
                if (!($next instanceof \OCP\Files\Folder)
                    || $next->getType() !== \OCP\Files\FileInfo::TYPE_FOLDER) {
                    // createPageAtPath() will raise its own error for this.
                    return null;
                }
                $currentFolder = $next;
            }

            return $currentFolder;
        } catch (\Throwable $e) {
            // A destination we cannot resolve simply gets no de-duplication;
            // failing the create over it would be worse than a suffix-free name.
            return null;
        }
    }

    /**
     * Get or create folder path recursively
     * Example: "nl/departments/marketing/campaigns" will create all intermediate folders
     *
     * A sub-page belongs in its PARENT's language folder, not in the author's
     * own. When the path names a language, that language wins: an English
     * editor adding a page under a German parent writes into de/, exactly where
     * the parent lives. Previously the language segment was stripped and the
     * remainder re-created under the author's own language, which fabricated an
     * empty mirror tree (de/departments/marketing/) whose parent pages did not
     * exist there — the created page vanished from the context it was made in.
     *
     * Mirrors resolveExistingFolderPath() — keep the two in step.
     */
    private function getOrCreateFolderPath(string $path): \OCP\Files\Folder {
        $pathParts = explode('/', trim($path, '/'));

        // A leading language segment selects the content folder to build in.
        // Fall back to the author's own language folder when the path carries
        // no language (legacy callers) or when that language has no folder yet.
        $currentFolder = null;
        // explode() always yields >=1 element, so the old `count($pathParts) > 0`
        // guard was always true (dead) — dropped; $pathParts[0] always exists.
        if ($this->languageService->isLanguageAvailable($pathParts[0])) {
            $langCode = array_shift($pathParts);
            try {
                $candidate = $this->folders->intraVox()->get($langCode);
                if ($candidate instanceof \OCP\Files\Folder) {
                    $currentFolder = $candidate;
                }
            } catch (NotFoundException $e) {
                // No folder for that language — fall through to the author's own.
            }
        }
        if ($currentFolder === null) {
            $currentFolder = $this->folders->languageFolder();
        }

        // Create each folder in path if it doesn't exist
        foreach ($pathParts as $folderName) {
            try {
                $currentFolder = $currentFolder->get($folderName);
                if ($currentFolder->getType() !== \OCP\Files\FileInfo::TYPE_FOLDER) {
                    throw new \InvalidArgumentException("Path component '{$folderName}' exists but is not a folder");
                }
            } catch (NotFoundException $e) {
                $currentFolder = $currentFolder->newFolder($folderName);
            }
        }

        return $currentFolder;
    }

    /**
     * Create a new page (validation, slug-dedup, uniqueId + translation-group
     * minting, then the write via createPageAtPath). PageService keeps a thin
     * public delegator; the seam-bound folder concerns come in as closures.
     *
     * The folder-substrate concerns (readLanguageFolder / intraVox / languageFolder
     * / userLanguage / languageOfFolder) now come from the injected FolderContext,
     * and getOrCreateFolderPath is a private method here; only the non-folder
     * concerns stay as closures.
     *
     * @param \Closure(string): void $validateDepth
     */
    public function createPage(
        array $data,
        ?string $parentPath,
        \Closure $validateDepth
    ): array {
        if (!isset($data['id']) || !isset($data['title'])) {
            throw new \InvalidArgumentException('Missing required fields: id, title');
        }

        $data['id'] = $this->idUtils->sanitizeId($data['id']);

        // If the slug is taken by a SIBLING at the destination, append a number.
        // Resolved once, outside the loop: nodeExists() is cheap, walking the
        // path is not.
        //
        // 'home' is exempt: createPageAtPath() writes it as home.json at the
        // language root with no folder of its own, so a 'home-2' would only
        // create a page folder the homepage resolver never looks at.
        if ($data['id'] !== 'home') {
            $targetFolder = $this->resolveExistingFolderPath($parentPath);
            $originalId = $data['id'];
            $counter = 2;
            while ($this->slugTakenIn($targetFolder, $data['id'])) {
                $data['id'] = $originalId . '-' . $counter;
                $counter++;
            }
        }

        // Generate uniqueId if not provided
        if (!isset($data['uniqueId'])) {
            $data['uniqueId'] = 'page-' . $this->idUtils->generateUUID();
        }

        // Every page belongs to a translation group, even when it is the only
        // member. Giving each new page its own group from the start means
        // "linked" and "not linked" are the same shape — there is no special
        // case for an unlinked page, and linking later is a value change rather
        // than a structural one. A caller that supplies a group (adding a
        // translation of an existing page) keeps it.
        if (empty($data['translationGroup'])) {
            $data['translationGroup'] = 'tg-' . $this->idUtils->generateUUID();
        }

        $validatedData = $this->shape->validateAndSanitizePage($data);

        // Use the createPageAtPath helper - pass id separately (not stored in JSON)
        $created = $this->createPageAtPath(
            $data['id'],
            $validatedData,
            $parentPath,
            $validateDepth
        );

        // Flush all cached page-tree + permission map entries so subsequent
        // reads (loadPages, getPageTree) immediately see the new page.
        // Historically only updatePage/deletePage did this; createPage
        // relied on the static cache's TTL to age out, which became
        // visible as "create page from template renders blank" once PR-3
        // shifted to a 5-minute distributed tree cache.
        $this->cacheInvalidator->invalidate(null);

        return $created;
    }

    /** True when $id is already a sibling in $parent (folder or loose json). */
    private function slugTakenIn(?\OCP\Files\Folder $parent, string $id): bool {
        if ($parent === null) {
            return false;
        }
        return $parent->nodeExists($id) || $parent->nodeExists($id . '.json');
    }

    /**
     * Scan a page folder to make it immediately visible in the Files app.
     * Uses Nextcloud's Scanner to add the folder to the file cache.
     */
    private function scanPageFolder($folder): void {
        try {
            // There used to be a groupfolders branch here that shelled out to
            // `php /var/www/nextcloud/occ files:scan` per page and returned
            // unconditionally. It never ran. The regex tested getPath(), which is
            // the user-facing view (/rik/files/IntraVox/nl/page) and never
            // contains /__groupfolders/ — that only appears in getInternalPath(),
            // which is exactly what the code below matches on.
            //
            // So the fork was unreachable and the in-process scanner has been
            // doing the work all along. Verified on dev: four page creations, zero
            // 'Failed to scan page folder' warnings, on a container where the
            // hardcoded /var/www/nextcloud/occ does not even exist — had the
            // branch been live, every one of them would have logged a failure.
            //
            // Removing it takes out a hardcoded occ path, a hardcoded 'IntraVox'
            // mount name, and a synchronous process fork from the request path,
            // none of which were earning anything.
            // Fallback for non-groupfolder paths (shouldn't happen in IntraVox)
            $storage = $folder->getStorage();
            $scanner = $storage->getScanner();
            $cache = $storage->getCache();

            $internalPath = $folder->getInternalPath();
            if (preg_match('#__groupfolders/\d+/(.+)$#', $internalPath, $matches)) {
                $scanPath = $matches[1];
            } else {
                $scanPath = $internalPath;
            }

            $scanner->scan($scanPath, true);
            $cache->correctFolderSize($scanPath, ['recursive' => true]);

        } catch (\Exception $e) {
            // Log but don't throw - if scanning fails, the page is still created
            $this->logger->error('Failed to scan page folder', [
                'path' => $folder->getPath(),
                'error' => $e->getMessage()
            ]);
        }
    }
}
