<?php
declare(strict_types=1);

namespace OCA\IntraVox\Service\Path;

/**
 * Pure helpers that derive structural information from an IntraVox page
 * path. All four methods take a path string (and sometimes a flag) and
 * return a primitive — no filesystem, no DB, no config.
 *
 * IntraVox paths follow this convention:
 *
 *   {lang}/public/{slug}[/{slug}...]
 *   {lang}/departments/{dept}/{slug}[/{slug}...]
 *   {lang}/{slug}                       (legacy)
 *
 * `markCurrentPageInTree` is a pure tree-walk used by getPageTree to
 * flag the page the user is currently viewing.
 */
final class PagePathHelper {
    /** Languages whose root segment is stripped before counting depth. */
    public const SUPPORTED_LANGUAGES = ['nl', 'en', 'de', 'fr'];

    /**
     * Depth of the page relative to its language root. The leading
     * "departments/{dept}" prefix doesn't count as depth, mirroring how
     * the UI nests pages under a department dashboard.
     */
    public function calculateDepth(string $path): int {
        $parts = $this->stripLanguagePrefix($path);
        if ($parts === []) {
            return 0;
        }
        if ($parts[0] === 'public') {
            return count($parts) - 1;
        }
        if ($parts[0] === 'departments' && count($parts) > 1) {
            return count($parts) - 2;
        }
        return count($parts) - 1;
    }

    /**
     * Page type for tree rendering and routing:
     *   - 'department' for a department root (departments/{dept})
     *   - 'container'  for an intermediate node with children
     *   - 'page'       for a leaf
     */
    public function determinePageType(string $path, bool $hasChildren): string {
        $parts = $this->stripLanguagePrefix($path);

        if (count($parts) === 2 && $parts[0] === 'departments') {
            return 'department';
        }
        if ($hasChildren) {
            return 'container';
        }
        return 'page';
    }

    /**
     * Department slug for paths inside `{lang}/departments/{dept}/...`,
     * or null when the path is not a department page.
     */
    public function parseDepartmentFromPath(string $path): ?string {
        $parts = $this->stripLanguagePrefix($path);
        if (count($parts) >= 2 && $parts[0] === 'departments') {
            return $parts[1];
        }
        return null;
    }

    /**
     * Locate the subtree whose root has `uniqueId === $rootPageId` and
     * return it wrapped in a single-element list, so callers receive the
     * same shape as `getPageTree` (a list of top-level nodes). Returns
     * an empty list when the root is not found, which lets API callers
     * treat "no such root" and "root has no children" uniformly.
     *
     * Pure tree-walk: no filesystem, no permission re-check — relies on
     * the input tree already being filtered by ACL upstream (which it is,
     * because PageService builds the tree through the user's mounted
     * folder view).
     *
     * @param array<int, array<string, mixed>> $tree Full tree as returned by getPageTree
     * @return array<int, array<string, mixed>> [] when not found, [subtreeRoot] otherwise
     */
    public function findSubtree(array $tree, string $rootPageId): array {
        foreach ($tree as $node) {
            if (($node['uniqueId'] ?? null) === $rootPageId) {
                return [$node];
            }
            if (!empty($node['children']) && is_array($node['children'])) {
                $found = $this->findSubtree($node['children'], $rootPageId);
                if ($found !== []) {
                    return $found;
                }
            }
        }
        return [];
    }

    /**
     * Recursively walk a page tree and set `isCurrent` on the node whose
     * `uniqueId` matches `$currentPageId`. Returns a new array (does not
     * mutate the input). When `$currentPageId` is null, returns the tree
     * unchanged.
     *
     * @param array<int, array<string, mixed>> $tree
     * @return array<int, array<string, mixed>>
     */
    public function markCurrentPageInTree(array $tree, ?string $currentPageId): array {
        if ($currentPageId === null) {
            return $tree;
        }
        $result = [];
        foreach ($tree as $node) {
            $newNode = $node;
            $newNode['isCurrent'] = ($node['uniqueId'] ?? null) === $currentPageId;
            if (!empty($node['children'])) {
                $newNode['children'] = $this->markCurrentPageInTree($node['children'], $currentPageId);
            }
            $result[] = $newNode;
        }
        return $result;
    }

    /**
     * Split a path into its segments after dropping the language code.
     *
     * @return array<int, string>
     */
    private function stripLanguagePrefix(string $path): array {
        $parts = explode('/', trim($path, '/'));
        if ($parts !== [] && in_array($parts[0], self::SUPPORTED_LANGUAGES, true)) {
            array_shift($parts);
        }
        // Preserve the [''] result from an empty path → return [].
        if ($parts === [''] || $parts === []) {
            return [];
        }
        return array_values($parts);
    }
}
