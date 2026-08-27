<?php
declare(strict_types=1);

namespace OCA\IntraVox\Service\PublicShare;

/**
 * The shape-only half of what a public share does to a page tree, extracted
 * from PublicShareController (controller split, PR-B).
 *
 * Every method here is a pure array-in/array-out transform: no filesystem, no
 * database, no session, no publication rules. That is the whole selection
 * criterion — anything that needs to know whether a page is published stayed
 * behind in the controller, because that gate lives in PageService and is
 * shared with the tree, search and single-page reads. Splitting it would mean
 * two places holding the same reader rules, which is exactly what the earlier
 * service split refused to do.
 *
 * Contract carried over unchanged, including the parts that look like details:
 *
 *   - extractSubtreeByScope FAILS CLOSED. A scope that matches no node returns
 *     an empty tree, never the whole language. A share pointing at a page that
 *     no longer exists must go quiet rather than open up.
 *   - A scope without a slash is a language-root share, where the entire tree
 *     is in scope and is returned as-is.
 *   - filterNavigationByHiddenIds matches on uniqueId and falls back to pageId,
 *     because navigation.json has carried both spellings over time.
 */
final class ShareTreeShaper {
    /**
     * Narrow a full language tree to the subtree a share actually covers.
     *
     * @param array<int, array<string, mixed>> $tree
     * @return array<int, array<string, mixed>>
     */
    public function extractSubtreeByScope(array $tree, string $scopePath): array {
        // If scopePath is just a language code (e.g., "en"), the entire language is shared.
        // Return the full tree — the home page and all subfolders are siblings at root level.
        if (!str_contains($scopePath, '/')) {
            return $tree;
        }

        foreach ($tree as $node) {
            if ($node['path'] === $scopePath) {
                return [$node];
            }
            if (!empty($node['children'])) {
                $found = $this->extractSubtreeByScope($node['children'], $scopePath);
                if (!empty($found) && count($found) === 1 && ($found[0]['path'] ?? '') === $scopePath) {
                    return $found;
                }
            }
        }
        // No node matches the scope. Fail closed: an empty tree, not the
        // whole language. The caller logs this; a share pointing at a page
        // that no longer exists must go quiet rather than open up.
        return [];
    }

    /**
     * Remove navigation items (recursively) whose target page is in the hidden
     * set. Items are matched by uniqueId; sub-items are pruned too.
     *
     * @param array<int, array>    $items
     * @param array<string, true>  $hidden
     * @return array<int, array>
     */
    public function filterNavigationByHiddenIds(array $items, array $hidden): array {
        $out = [];
        foreach ($items as $item) {
            $uid = $item['uniqueId'] ?? ($item['pageId'] ?? null);
            if ($uid !== null && isset($hidden[$uid])) {
                continue;
            }
            if (!empty($item['children']) && is_array($item['children'])) {
                $item['children'] = $this->filterNavigationByHiddenIds($item['children'], $hidden);
            }
            $out[] = $item;
        }
        return $out;
    }

    /**
     * Recursively strip permissions from tree nodes.
     *
     * An anonymous viewer has no permissions worth reporting, and leaving the
     * key in would leak what the share's owner may do.
     *
     * @param array<int, array<string, mixed>> $tree
     * @return array<int, array<string, mixed>>
     */
    public function stripPermissionsFromTree(array $tree): array {
        return array_map(function ($node) {
            unset($node['permissions']);
            if (!empty($node['children'])) {
                $node['children'] = $this->stripPermissionsFromTree($node['children']);
            }
            return $node;
        }, $tree);
    }
}
