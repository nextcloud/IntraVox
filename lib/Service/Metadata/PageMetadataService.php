<?php

declare(strict_types=1);

namespace OCA\IntraVox\Service\Metadata;

use OCA\IntraVox\Service\Folder\FolderContext;
use OCA\IntraVox\Service\Homepage\HomepageResolverService;
use OCA\IntraVox\Service\NavigationService;
use OCA\IntraVox\Service\PageIndexService;
use OCA\IntraVox\Service\Path\PageDataEnricher;
use OCA\IntraVox\Service\Sanitize\PageShapeSanitizer;
use OCA\IntraVox\Service\Util\PageIdUtils;
use OCA\IntraVox\Service\Version\PageVersionService;
use OCP\Files\File;
use OCP\Files\Folder;
use Psr\Log\LoggerInterface;

/**
 * The METADATA projection domain, carved out of PageService: the derived
 * metadata view of a page (getPageMetadata) and the title/folder-rename mutation
 * that pairs with it (updatePageMetadata + the #95 folder-rename machinery).
 *
 * This is the first domain carve that leans on FolderContext: the folder concerns
 * arrive as ONE injected FolderContext (languageFolder / userLanguage) rather than
 * a bundle of seam closures. The #70 canWrite gate rides in through the shared
 * PageDataEnricher. The homepage check comes from the injected
 * HomepageResolverService; page lookup stays page-lookup-bound and comes in per
 * call as $this-bound closures, so a caller keeps intercepting them.
 *
 * Behaviour is byte-identical to the former PageService methods: PageMetadataTest,
 * PageRenameFolderTest and PageServiceSeamContractTest pin it.
 */
final class PageMetadataService {
    public function __construct(
        private PageIdUtils $idUtils,
        private PageVersionService $pageVersionService,
        private PageIndexService $pageIndexService,
        private NavigationService $navigationService,
        private PageShapeSanitizer $shape,
        private FolderContext $folders,
        private PageDataEnricher $enricher,
        private LoggerInterface $logger,
        private HomepageResolverService $homepageResolver,
        private \OCA\IntraVox\Service\Cache\PageCacheInvalidator $cacheInvalidator,
    ) {
    }

    /**
     * The derived metadata view of a page: filesystem timestamps, path, size,
     * permissions (canWrite/canEdit gated per user via enrich, #70) and the
     * folder-rename layout (#95).
     *
     * @param \Closure(\OCP\Files\Folder, string): ?array $locatePageAnyLanguage
     * @param \Closure(\OCP\Files\Folder, string): ?array $findPageById
     */
    public function getPageMetadata(
        string $pageId,
        \Closure $locatePageAnyLanguage,
        \Closure $findPageById
    ): array {
        // Get page and file info
        $folder = $this->folders->languageFolder();
        $result = null;

        // Check for uniqueId pattern (page-xxxx). Follows the page across
        // language folders so an operation on a page the user can see never
        // fails with "Page not found" (issue #90).
        if (strpos($pageId, 'page-') === 0) {
            $result = $locatePageAnyLanguage($folder, $pageId);
        }

        // Fall back to legacy ID lookup
        if ($result === null) {
            $result = $findPageById($folder, $this->idUtils->sanitizeId($pageId));
        }

        if (!$result) {
            throw new \Exception('Page not found: ' . $pageId);
        }

        $file = $result['file'];
        $folder = $result['folder'];

        // Get filesystem timestamps
        $mtime = $file->getMTime();
        $ctime = $file->getCreationTime();
        // Fallback: if creation time is 0 (not supported by groupfolder/storage), use mtime
        if ($ctime === 0) {
            $ctime = $mtime;
        }

        // Get page content for other metadata
        $content = $file->getContent();
        $data = json_decode($content, true);

        // Enrich with path data (file gates canWrite/canEdit, #70)
        $data = $this->enricher->enrich($data, $folder, $file);

        // Format path to show full Nextcloud path starting with /IntraVox/
        $displayPath = isset($data['path']) ? '/IntraVox/' . $data['path'] : '';

        // Get file info for MetaVox integration
        $fileId = $file->getId();
        $size = $file->getSize();
        $internalPath = $file->getInternalPath();
        $storagePath = $file->getPath();

        // Get parent folder fileId for Files app link
        $parentFolderId = null;
        try {
            $parentFolderId = $folder->getId();
        } catch (\Exception $e) {
            // Not critical
        }

        // Get permissions from enriched data (uses Nextcloud's native permissions)
        $permissions = $data['permissions'] ?? [
            'canRead' => true,
            'canWrite' => false,
            'canCreate' => false,
            'canDelete' => false,
            'canShare' => false,
            'raw' => 1
        ];

        // Folder-rename support (#95): null when the page has no renamable
        // folder/JSON pair (homepage, loose legacy file) — the rename dialog
        // hides the folder option in that case.
        // $data is the enriched array (enrich() returns array), so it is always an
        // array here — the former is_array() guard was dead and is dropped.
        $renameLayout = $this->resolvePageLayoutForRename($result, $data);

        // Return metadata using filesystem timestamps
        $metadata = [
            'title' => $data['title'] ?? 'Untitled',
            'uniqueId' => $data['uniqueId'] ?? '',
            'language' => $data['language'] ?? $this->folders->userLanguage(),
            'created' => $ctime,
            'createdFormatted' => date('Y-m-d H:i:s', $ctime),
            'createdRelative' => $this->getRelativeTime($ctime),
            'modified' => $mtime,
            'modifiedFormatted' => date('Y-m-d H:i:s', $mtime),
            'modifiedRelative' => $this->getRelativeTime($mtime),
            // Path-related data (already in page)
            'path' => $storagePath,
            'depth' => $data['depth'] ?? 0,
            'parentId' => $data['parentId'] ?? null,
            'parentPath' => $data['parentPath'] ?? null,
            'department' => $data['department'] ?? null,
            'canEdit' => $permissions['canWrite'] ?? false,
            // Additional data for MetaVox integration
            'fileId' => $fileId,
            'size' => $size,
            'parentFolderId' => $parentFolderId,
            'folderName' => $renameLayout !== null ? $renameLayout['folder']->getName() : null,
            'mountPoint' => 'IntraVox',
            // Permissions - use Nextcloud's native permissions
            'permissions' => $permissions,
        ];

        return $metadata;
    }

    /**
     * Update page metadata (title only for now, similar to Files rename), with an
     * optional folder rename riding along (#95).
     *
     * @param \Closure(\OCP\Files\Folder, string): ?array $locatePageAnyLanguage
     * @param \Closure(\OCP\Files\Folder, string): ?array $findPageById
     */
    public function updatePageMetadata(
        string $pageId,
        array $metadata,
        \Closure $locatePageAnyLanguage,
        \Closure $findPageById
    ): array {
        $folder = $this->folders->languageFolder();
        $result = null;

        // Check for uniqueId pattern (page-xxxx). Follows the page across
        // language folders so an operation on a page the user can see never
        // fails with "Page not found" (issue #90).
        if (strpos($pageId, 'page-') === 0) {
            $result = $locatePageAnyLanguage($folder, $pageId);
        }

        // Fall back to legacy ID lookup
        if ($result === null) {
            $result = $findPageById($folder, $this->idUtils->sanitizeId($pageId));
        }

        if (!$result) {
            throw new \Exception('Page not found: ' . $pageId);
        }

        $file = $result['file'];

        // Get current content
        $content = $file->getContent();
        $data = json_decode($content, true);

        // Update only allowed fields
        $changed = false;
        $oldTitle = $data['title'] ?? '';
        $newTitle = null;
        if (isset($metadata['title']) && $metadata['title'] !== $data['title']) {
            $newTitle = $this->sanitizeText($metadata['title']);
            $data['title'] = $newTitle;
            $changed = true;
        }

        // Save if changed
        if ($changed) {
            // Create version before update using VersionsBackend
            $this->pageVersionService->createBeforeUpdate($file);
            $file->putContent(json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

            // Keep the navigation menu label in sync when the page is renamed,
            // but only when the label still matched the old title — a label that
            // was deliberately set to something else is left untouched. Inside this
            // `if ($changed)` block $newTitle is always the sanitized string (the
            // former `$newTitle !== null` guard was dead — $changed implies it).
            if (!empty($data['uniqueId'])) {
                $this->syncNavigationTitle((string)$data['uniqueId'], $oldTitle, $newTitle);
            }
        }

        // Optional folder rename riding along with the title change (#95).
        // Best-effort by design: the title rename above already succeeded, and
        // a folder that keeps its old name is exactly today's behaviour.
        $folderRename = null;
        if (isset($metadata['folderName']) && is_string($metadata['folderName']) && $metadata['folderName'] !== '') {
            $folderRename = $this->renamePageFolder($result, $metadata['folderName'], is_array($data) ? $data : []);
        }

        // Refetch by uniqueId when we have one: after a folder rename, a
        // legacy slug-shaped $pageId no longer resolves.
        $refetchId = (is_array($data) && !empty($data['uniqueId'])) ? (string)$data['uniqueId'] : $pageId;
        $response = $this->getPageMetadata($refetchId, $locatePageAnyLanguage, $findPageById);
        if ($folderRename !== null) {
            $response['folderRename'] = $folderRename;
        }
        return $response;
    }

    private function sanitizeText(string $text): string {
        return $this->shape->sanitizeText($text);
    }

    private function getRelativeTime(int $timestamp): string {
        // Pure formatting, extracted to a stateless Format/RelativeTime helper.
        return (new \OCA\IntraVox\Service\Format\RelativeTime())->format($timestamp);
    }

    /**
     * Keep the navigation menu label in sync after a page rename (issue #84).
     *
     * Walks the navigation tree for the current language and, for every item
     * that points at this page (by uniqueId) whose label still equals the old
     * page title, updates the label to the new title. Items whose label was
     * deliberately set to something else are left as-is. Best-effort: a failure
     * here must never break the rename itself.
     */
    private function syncNavigationTitle(string $uniqueId, string $oldTitle, string $newTitle): void {
        if ($oldTitle === $newTitle) {
            return;
        }
        try {
            $navigation = $this->navigationService->getNavigation();
            $items = $navigation['items'] ?? [];
            $changed = false;

            $walk = function (array &$items) use (&$walk, $uniqueId, $oldTitle, $newTitle, &$changed): void {
                foreach ($items as &$item) {
                    $itemId = $item['uniqueId'] ?? $item['pageId'] ?? null;
                    if ($itemId === $uniqueId && ($item['title'] ?? '') === $oldTitle) {
                        $item['title'] = $newTitle;
                        $changed = true;
                    }
                    if (isset($item['children']) && is_array($item['children'])) {
                        $walk($item['children']);
                    }
                }
                unset($item);
            };
            $walk($items);

            if ($changed) {
                $navigation['items'] = $items;
                $this->navigationService->saveNavigation($navigation);
            }
        } catch (\Throwable $e) {
            $this->logger->warning('[PageMetadataService] Could not sync navigation title after rename: ' . $e->getMessage());
        }
    }

    /**
     * Classify how a located page pairs its JSON with a folder, for the
     * folder-rename option (#95).
     *
     * Returns null when there is nothing that may be renamed as a pair: the
     * homepage (both the loose `home.json` and a configured homepage page),
     * and loose JSON files whose base name matches no folder (their
     * containing folder is shared with other pages). Otherwise tells the two
     * supported shapes apart:
     *   - 'inside': modern model, `{slug}/{slug}.json`
     *   - 'beside': legacy model, `{slug}.json` next to `{slug}/`
     *
     * @param array{file?:\OCP\Files\File, folder?:\OCP\Files\Folder, isHome?:bool} $result
     * @return array{layout:string, file:\OCP\Files\File, folder:\OCP\Files\Folder}|null
     */
    private function resolvePageLayoutForRename(array $result, array $pageData): ?array {
        if (!empty($result['isHome'])) {
            return null;
        }
        $uniqueId = (string)($pageData['uniqueId'] ?? '');
        $language = isset($pageData['language']) && is_string($pageData['language'])
            ? $pageData['language'] : null;
        if ($uniqueId !== '' && $this->homepageResolver->isHomepage($uniqueId, $language)) {
            return null;
        }
        $file = $result['file'] ?? null;
        $folder = $result['folder'] ?? null;
        if (!$file instanceof File || !$folder instanceof Folder) {
            return null;
        }
        $fileName = $file->getName();
        if (substr($fileName, -5) !== '.json' || substr($fileName, 0, -5) !== $folder->getName()) {
            return null;
        }
        $fileParent = dirname($file->getPath());
        $folderPath = $folder->getPath();
        if ($fileParent === $folderPath) {
            return ['layout' => 'inside', 'file' => $file, 'folder' => $folder];
        }
        if ($fileParent === dirname($folderPath)) {
            return ['layout' => 'beside', 'file' => $file, 'folder' => $folder];
        }
        return null;
    }

    /**
     * Rename a page's folder and its paired `.json` to a new slug (#95).
     *
     * The two nodes MUST stay a pair — a folder whose JSON carries another
     * base name is exactly the mismatch the index rebuild skips as "not a
     * page" — so when the second rename fails the first is rolled back.
     * Collisions get a `-2`/`-3` suffix like createPage and movePage; for
     * the inside layout the suffix loop also avoids the name of any child
     * entry, because `{slug}/{slug}.json` next to a child folder `{slug}`
     * would be read as that child's beside-layout JSON.
     *
     * Never throws: the title rename this rides along with has already
     * succeeded, so the outcome is reported instead.
     *
     * @return array{status:string, reason?:string, folderName?:string}
     */
    private function renamePageFolder(array $result, string $requestedName, array $pageData): array {
        $layout = $this->resolvePageLayoutForRename($result, $pageData);
        if ($layout === null) {
            return ['status' => 'skipped', 'reason' => 'layout'];
        }

        try {
            $newName = $this->idUtils->sanitizeId($requestedName);
        } catch (\InvalidArgumentException $e) {
            return ['status' => 'failed', 'reason' => 'invalid_name'];
        }

        $file = $layout['file'];
        $folder = $layout['folder'];
        $inside = $layout['layout'] === 'inside';
        $folderName = $folder->getName();
        if ($newName === $folderName) {
            return ['status' => 'skipped', 'reason' => 'unchanged', 'folderName' => $folderName];
        }

        if (!$folder->isUpdateable() || !$file->isUpdateable()) {
            return ['status' => 'failed', 'reason' => 'permission'];
        }

        $parent = $folder->getParent();
        $candidate = $newName;
        $counter = 2;
        while ($parent->nodeExists($candidate)
            || $parent->nodeExists($candidate . '.json')
            || ($inside && ($folder->nodeExists($candidate) || $folder->nodeExists($candidate . '.json')))) {
            $candidate = $newName . '-' . $counter;
            $counter++;
        }

        $oldFolderPath = $folder->getPath();
        $parentPath = rtrim(dirname($oldFolderPath), '/');
        $newFolderPath = $parentPath . '/' . $candidate;

        try {
            if ($inside) {
                $fileName = $file->getName();
                $moved = $folder->move($newFolderPath);
                $movedFolder = $moved instanceof Folder ? $moved : $folder;
                try {
                    $movedFolder->get($fileName)->move($newFolderPath . '/' . $candidate . '.json');
                } catch (\Throwable $inner) {
                    try {
                        $movedFolder->move($oldFolderPath);
                    } catch (\Throwable $rollback) {
                        $this->logger->error(
                            'renamePageFolder: rollback failed — folder and JSON are out of step, run occ intravox:reindex after repairing',
                            ['folder' => $newFolderPath, 'error' => $rollback->getMessage()]
                        );
                    }
                    throw $inner;
                }
            } else {
                $oldFilePath = $file->getPath();
                $moved = $file->move($parentPath . '/' . $candidate . '.json');
                $movedFile = $moved instanceof File ? $moved : $file;
                try {
                    $folder->move($newFolderPath);
                } catch (\Throwable $inner) {
                    try {
                        $movedFile->move($oldFilePath);
                    } catch (\Throwable $rollback) {
                        $this->logger->error(
                            'renamePageFolder: rollback failed — folder and JSON are out of step, run occ intravox:reindex after repairing',
                            ['file' => $oldFilePath, 'error' => $rollback->getMessage()]
                        );
                    }
                    throw $inner;
                }
            }
        } catch (\Throwable $e) {
            $this->logger->error('renamePageFolder: rename failed', [
                'from' => $oldFolderPath,
                'to' => $newFolderPath,
                'error' => $e->getMessage(),
            ]);
            return ['status' => 'failed', 'reason' => 'rename_failed'];
        }

        // Same contract as movePage: the disk rename already succeeded, so an
        // index failure must not fail the operation — occ intravox:reindex repairs.
        try {
            $this->pageIndexService->repathSubtree($oldFolderPath, $newFolderPath);
        } catch (\Throwable $e) {
            $this->logger->warning('renamePageFolder: could not repath index subtree', [
                'from' => $oldFolderPath,
                'to' => $newFolderPath,
                'error' => $e->getMessage(),
            ]);
        }

        $this->cacheInvalidator->invalidate();
        return ['status' => 'renamed', 'folderName' => $candidate];
    }
}
