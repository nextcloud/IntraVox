<?php

declare(strict_types=1);

namespace OCA\IntraVox\Service\Path;

use OCA\IntraVox\Service\Folder\FolderContext;
use OCA\IntraVox\Service\PermissionService;
use OCA\IntraVox\Service\Publication\MetaVoxGateway;

/**
 * Enriches a decoded page array with the real-time, filesystem-derived fields:
 * path/depth/parentPath/parentId/language/department, and — when the page file
 * node is available — the per-user permissions/canEdit, the concurrency
 * baseVersion, fileId, the ACL-filtered translations list, and MetaVox
 * availability + groupfolderId.
 *
 * Extracted verbatim from PageService::enrichWithPathData(). This is NOT the #70
 * distributed-cache-hit recompute (that stays inline in getPage as the security
 * boundary) — this is the fresh-build enrichment shared by getPage,
 * findPageByFolderPath and getPageMetadata.
 *
 * The folder-root-relative path comes from the injected FolderContext (the
 * substrate). Two PageService concerns it still leans on stay on PageService and
 * are passed in as closures: resolveTranslations and groupfolderIdForNode (both
 * also used by the #70 block, so they must not be duplicated here).
 * PageMetadataTest and the getPage suites pin the derived fields.
 */
final class PageDataEnricher {
    public function __construct(
        private PagePathHelper $pathHelper,
        private PermissionService $permissionService,
        private MetaVoxGateway $metaVox,
        private FolderContext $folders,
        private \OCA\IntraVox\Service\Translation\TranslationGroupService $translationGroups,
        private \OCA\IntraVox\Service\Util\GroupfolderResolver $groupfolders,
    ) {
    }

    /**
     * The other language versions of a page, from its translation group,
     * ACL-filtered per user (the former resolveTranslations closure, now over the
     * injected TranslationGroupService + FolderContext root). Returns [] on any
     * problem — a missing switcher is a smaller failure than a page that won't
     * load.
     *
     * @return array<int, array{language:string, uniqueId:string, title:string, status:string}>
     */
    private function resolveTranslations(?string $translationGroup, ?string $ownUniqueId): array {
        return $this->translationGroups->resolveTranslations(
            $translationGroup,
            $ownUniqueId,
            fn(): \OCP\Files\Folder => $this->folders->intraVox()
        );
    }

    /**
     * @param array $page decoded page data
     * @param \OCP\Files\Folder $folder the page's folder
     */
    public function enrich(array $page, $folder, ?\OCP\Files\Node $file = null): array {
        // Get relative path from IntraVox root
        $page['path'] = $this->folders->relativePathFromRoot($folder);

        // Calculate depth
        $page['depth'] = $this->pathHelper->calculateDepth($page['path']);

        // Calculate parent path
        $pathParts = explode('/', $page['path']);
        if (count($pathParts) > 1) {
            array_pop($pathParts); // Remove current page
            $page['parentPath'] = implode('/', $pathParts);
            $page['parentId'] = basename($page['parentPath']);
        } else {
            $page['parentPath'] = null;
            $page['parentId'] = null;
        }

        // Parse language and department from path. explode() always yields at
        // least one element, so [0] always exists — the original `?? userLanguage`
        // fallback was dead code (an empty path gives '' at [0], never null), so it
        // is dropped and the userLanguage closure with it. Behaviour-identical.
        $parsedPath = explode('/', $page['path']);
        $page['language'] = $parsedPath[0];
        $page['department'] = $this->pathHelper->parseDepartmentFromPath($page['path']);

        // Get permissions directly from Nextcloud's filesystem, combining the
        // bitmask with the node capability methods so a read-only GroupFolder
        // member (without ACLs) is reported correctly — see permissionsFromNode().
        // When the page's file node is available, gate canWrite/canEdit on the
        // FILE (the real edit target) rather than the folder, so the "Edit page"
        // affordance matches what the write path actually allows (issue #70).
        if ($file !== null) {
            $page['permissions'] = $this->permissionService->permissionsForPage($folder, $file);
            $page['canEdit'] = $file->isUpdateable();
            // Expose the page file's id so the publication gate can resolve the
            // scheduled-publish MetaVox fields (publish/expiration) for this page.
            if ($file instanceof \OCP\Files\File) {
                $page['fileId'] = $file->getId();
            }
            // Concurrency token: the editor sends this back on save, and
            // updatePage() refuses a write whose baseVersion predates the file
            // on disk. Deliberately the file's mtime rather than the `modified`
            // field in the JSON, which is client-supplied and would compare a
            // value against itself.
            $page['baseVersion'] = $file->getMTime();

            // Which languages this page exists in. Powers the reader's "also
            // available in X" notice and the language switcher, and tells an
            // editor at a glance what still needs translating.
            //
            // Excludes the page's own language: the list answers "where ELSE
            // can I read this", so including the page you are on would only add
            // a no-op entry to every switcher.
            $page['translations'] = $this->resolveTranslations(
                $page['translationGroup'] ?? null,
                $page['uniqueId'] ?? null
            );

            // Whether the MetaVox tab and its menu entry should exist at all.
            // Rides along on a response the client already fetches: this is an
            // in-memory app-manager lookup, no query and no HTTP, so it is
            // cheaper than the separate /api/metavox/status call the sidebar
            // used to make every time it opened.
            $page['metaVoxAvailable'] = $this->metaVox->isMetaVoxAvailable();

            // The groupfolder holding this page. MetaVox's field definitions are
            // assigned per groupfolder, and its groupfolder-scoped endpoint
            // returns exactly the fields for that folder — where the
            // auto-detecting variant returned every field of every folder.
            //
            // Derived from the file's mount path rather than from MetaVox's
            // value table: that table only holds rows for files that already
            // have values SAVED, so looking there would return nothing for a
            // page whose fields are still empty — precisely the freshly copied
            // and translated pages that need the form most.
            if ($page['metaVoxAvailable'] && $file instanceof \OCP\Files\File) {
                $page['groupfolderId'] = $this->groupfolders->forNode($file);
            }
        } else {
            $page['permissions'] = $this->permissionService->permissionsFromNode($folder);
            $page['canEdit'] = $folder->isUpdateable();
        }

        return $page;
    }
}
