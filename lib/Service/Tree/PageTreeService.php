<?php

declare(strict_types=1);

namespace OCA\IntraVox\Service\Tree;

use OCA\IntraVox\Service\Cache\PageCacheService;
use OCA\IntraVox\Service\Folder\FolderContext;
use OCA\IntraVox\Service\GroupContextService;
use OCA\IntraVox\Service\HomepageService;
use OCA\IntraVox\Service\Path\PagePathHelper;
use OCA\IntraVox\Service\PermissionService;
use OCP\Files\NotFoundException;

/**
 * Builds and serves the per-language page tree — extracted verbatim from
 * PageService::getPageTree + shapeTreeResponse + refreshTreePermissions
 * (facade elimination fase-4 capstone).
 *
 * The full per-language tree is cached WHOLE, keyed by groupHash + language, so
 * users that share a group set share one bucket (issue #45). The cached tree
 * carries group-level permissions; because GroupFolder ACLs can grant or deny
 * per USER within the same group, every served response recomputes each node's
 * permissions from the live filesystem view (issue #86 tree-COW). That recompute
 * runs AFTER the cache read/build and BEFORE return, on ALL THREE cache paths
 * (in-process hit, distributed hit, fresh build), and markCurrentPageInTree
 * deep-copies the shared tree before any overwrite so the cache is never polluted.
 * This service OWNS its own PermissionService — the same structural guarantee that
 * lets PageReadService own the #70 per-read recompute — so per-user ACL isolation
 * survives the carve.
 */
final class PageTreeService {
    public function __construct(
        private PageCacheService $cache,
        private GroupContextService $groupContext,
        private FolderContext $folders,
        private PageTreeBuilder $treeBuilder,
        private HomepageService $homepageService,
        private PagePathHelper $pathHelper,
        private PermissionService $permissionService,
    ) {
    }

    public function getPageTree(?string $currentPageId = null, ?string $language = null, ?string $rootPageId = null): array {
        // Use provided language, else the language the user is actually shown
        // (recommended-language fallback, #75), else their own language.
        $lang = $language ?? $this->folders->effectiveLanguage() ?? $this->folders->userLanguage();

        // Cache key is groupHash + language. Users that share a group set
        // share a bucket — at enterprise scale (1k+ users, ~10 groups) that
        // turns 2000 entries into ~10.
        //
        // The full per-language tree is cached *whole*; subtree requests
        // filter from that cached blob (issue #45). Caching subtrees
        // separately would multiply key cardinality by the number of
        // candidate roots without saving work.
        // Group-shared by default; per-user once Advanced Permissions are on,
        // because then two users in the same groups can legitimately see
        // different trees and a group-keyed entry serves one to the other
        // (issue #112). getCacheDiscriminator() returns '' when ACLs are off,
        // so the cheap shared key is unchanged for those installations.
        $cacheKey = $this->groupContext->getGroupHash()
            . $this->permissionService->getCacheDiscriminator()
            . '_' . $lang;
        $distributedCacheKey = 'tree_' . $cacheKey;
        $now = time();

        // Check in-process cache first (fastest)
        $cached = $this->cache->getTree($cacheKey);
        if ($cached !== null) {
            if (($now - $cached['time']) < PageCacheService::PAGE_TREE_TTL) {
                return $this->shapeTreeResponse($cached['tree'], $currentPageId, $rootPageId);
            }
        }

        // Check distributed cache (shared across PHP processes/requests)
        if ($this->cache->isDistributedAvailable()) {
            $distributedCached = $this->cache->getDistributed($distributedCacheKey);
            if ($distributedCached !== null) {
                $decoded = json_decode($distributedCached, true);
                if ($decoded !== null) {
                    // Populate the in-process cache too for later calls in this request
                    $this->cache->setTree($cacheKey, [
                        'tree' => $decoded,
                        'time' => $now
                    ]);
                    return $this->shapeTreeResponse($decoded, $currentPageId, $rootPageId);
                }
            }
        }

        // Build fresh tree for specified language
        $folder = $this->folders->languageFolderByCode($lang);
        $tree = [];

        // Check for home.json in root
        try {
            $homeFile = $folder->get('home.json');
            $content = $homeFile->getContent();
            $data = json_decode($content, true);

            if ($data && isset($data['uniqueId'], $data['title'])) {
                $tree[] = [
                    'uniqueId' => $data['uniqueId'],
                    'title' => $data['title'],
                    'status' => $data['status'] ?? 'published',
                    'fileId' => ($homeFile instanceof \OCP\Files\File) ? $homeFile->getId() : null,
                    'path' => $lang,
                    'language' => $lang,
                    'isCurrent' => false, // Will be set by markCurrentPageInTree
                    'children' => [],
                    'permissions' => $this->permissionService->permissionsFromNode($folder)
                ];
            }
        } catch (NotFoundException $e) {
            // No home page yet
        }

        // Recursively build tree from subfolders
        $this->buildPageTree($folder, $tree, null, $lang); // Pass null, marking done separately

        // Configurable homepage: if a pointer designates a root page other than
        // the loose home.json, float that node to the front so the homepage is
        // always first (matches the legacy home.json-first behaviour).
        $pointer = $this->homepageService->getHomepageUniqueId($lang);
        if ($pointer !== null && $pointer !== '' && $pointer !== 'home') {
            foreach ($tree as $i => $node) {
                if (($node['uniqueId'] ?? null) === $pointer) {
                    if ($i !== 0) {
                        $picked = array_splice($tree, $i, 1);
                        array_unshift($tree, $picked[0]);
                    }
                    break;
                }
            }
        }

        // Store in the in-process cache
        $this->cache->setTree($cacheKey, [
            'tree' => $tree,
            'time' => $now
        ]);

        // Store in distributed cache (shared across requests)
        $this->cache->setDistributed($distributedCacheKey, json_encode($tree), PageCacheService::PAGE_TREE_TTL);

        return $this->shapeTreeResponse($tree, $currentPageId, $rootPageId);
    }

    /**
     * Apply the response-shaping steps that come after cache lookup:
     * optionally narrow to a subtree, then mark the current page.
     * Centralised so the three cache paths (static, distributed, fresh)
     * stay identical.
     */
    private function shapeTreeResponse(array $tree, ?string $currentPageId, ?string $rootPageId): array {
        if ($rootPageId !== null && $rootPageId !== '') {
            $tree = $this->pathHelper->findSubtree($tree, $rootPageId);
        }
        // markCurrentPageInTree deep-copies the (group-shared) cached tree, so it
        // is safe to overwrite permissions on the copy without polluting the cache.
        $tree = $this->pathHelper->markCurrentPageInTree($tree, $currentPageId);
        // The tree is cached per group-set, but GroupFolder ACLs can grant/deny
        // per USER within the same group. Recompute each node's permissions for
        // the current user from the live filesystem view so per-user ACLs are
        // reflected (issue #86) — same reasoning as the per-read permission
        // recompute in getPage() (issue #70).
        $this->refreshTreePermissions($tree);
        return $tree;
    }

    /**
     * Overwrite each tree node's `permissions` with the current user's live,
     * ACL-aware permissions, resolved from the node's path. Recurses into
     * children. Per-path results are memoised for the request via the shared
     * permissions cache inside getFolderPermissions/permissionsFromNode.
     *
     * @param array<int, array> $nodes
     */
    private function refreshTreePermissions(array &$nodes): void {
        foreach ($nodes as &$node) {
            $path = $node['path'] ?? null;
            if (is_string($path) && $path !== '') {
                try {
                    $node['permissions'] = $this->permissionService->getFolderPermissions($path);
                } catch (\Throwable $e) {
                    // Leave the cached (group-level) permissions as a safe fallback.
                }
            }
            if (!empty($node['children']) && is_array($node['children'])) {
                $this->refreshTreePermissions($node['children']);
            }
        }
        unset($node);
    }

    /**
     * Recursively build the page tree from folder structure — the recursive walk
     * lives in PageTreeBuilder; this by-ref wrapper preserves the call shape.
     */
    private function buildPageTree($folder, array &$tree, ?string $currentPageId, ?string $language = null): void {
        $this->treeBuilder->build($folder, $tree, $currentPageId, $language);
    }
}
