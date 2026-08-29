<?php
declare(strict_types=1);

namespace OCA\IntraVox\Service\Import;

use Psr\Log\LoggerInterface;

/**
 * Turning a flat list of imported pages into a navigation tree.
 *
 * Extracted from ImportService (service split, fase 2). Six methods that are
 * pure array manipulation: order the pages so parents precede children,
 * build the nested structure, cap it at a depth, strip the bookkeeping keys
 * again, and locate or graft a subtree onto an existing menu.
 *
 * What stayed behind is updateNavigationWithImportedPages(): it reads and
 * writes the real navigation through NavigationService, which is
 * orchestration rather than algorithm. The split follows the pattern the
 * service extractions already use -- the caller LOCATES and PERSISTS, the
 * extracted class COMPUTES.
 *
 * The depth cap is load-bearing, not cosmetic: navigation.json is rendered as
 * a menu, and a Confluence space nested eight levels deep would produce a
 * submenu no one can reach. Pages below the cap are still imported; they just
 * do not get a menu entry.
 */
class ImportNavigationBuilder {
    public function __construct(
        private LoggerInterface $logger,
    ) {
    }
    /**
     * Sort pages by hierarchy - parents before children
     *
     * @param array $pages Array of page data
     * @return array Sorted array of pages
     */
    public function sortPagesByHierarchy(array $pages): array {
        // Build a map of uniqueId => page for quick lookup
        $pageMap = [];
        foreach ($pages as $page) {
            $pageMap[$page['uniqueId']] = $page;
        }

        // Group pages by parent (siblings)
        $pagesByParent = [];
        foreach ($pages as $page) {
            $parentId = $page['parentUniqueId'] ?? 'root';
            if (!isset($pagesByParent[$parentId])) {
                $pagesByParent[$parentId] = [];
            }
            $pagesByParent[$parentId][] = $page;
        }

        // Sort siblings by confluenceOrder if available
        foreach ($pagesByParent as $parentId => &$siblings) {
            usort($siblings, function($a, $b) {
                $orderA = $a['content']['metadata']['confluenceOrder'] ?? 9999;
                $orderB = $b['content']['metadata']['confluenceOrder'] ?? 9999;
                return $orderA <=> $orderB;
            });
        }
        unset($siblings);

        // Build dependency graph with sorted siblings
        $sorted = [];
        $visited = [];

        // Helper function to visit a page and its ancestors first
        $visit = function($uniqueId) use (&$visit, &$sorted, &$visited, $pageMap, $pagesByParent) {
            if (isset($visited[$uniqueId])) {
                return; // Already processed
            }

            $visited[$uniqueId] = true;

            if (!isset($pageMap[$uniqueId])) {
                return; // Page not found
            }

            $page = $pageMap[$uniqueId];

            // Visit parent first if exists
            if (!empty($page['parentUniqueId']) && isset($pageMap[$page['parentUniqueId']])) {
                $visit($page['parentUniqueId']);
            }

            // Add this page
            $sorted[] = $page;

            // Visit children in sorted order
            if (isset($pagesByParent[$uniqueId])) {
                foreach ($pagesByParent[$uniqueId] as $child) {
                    $visit($child['uniqueId']);
                }
            }
        };

        // Visit root pages first (in sorted order)
        if (isset($pagesByParent['root'])) {
            foreach ($pagesByParent['root'] as $page) {
                $visit($page['uniqueId']);
            }
        }

        // Visit any remaining pages that weren't reached (shouldn't happen normally)
        foreach ($pages as $page) {
            $visit($page['uniqueId']);
        }

        return $sorted;
    }
    /**
     * Build a tree structure from flat page list
     * Limits depth to maximum levels allowed in navigation (5 levels total)
     *
     * @param array $pages Flat list of pages
     * @param int $maxDepth Maximum depth to include (default 4 = 5 levels total with root)
     * @return array Tree structure limited to maxDepth
     */
    public function buildPageTree(array $pages, int $maxDepth = 4): array {
        $tree = [];
        $lookup = [];

        // First pass: create all nodes with depth tracking
        foreach ($pages as $page) {
            $uniqueId = $page['uniqueId'];
            $node = [
                'id' => 'nav_' . substr($uniqueId, 5, 8), // nav_abc12345
                'title' => $page['content']['title'] ?? 'Untitled',
                'uniqueId' => $uniqueId,
                'url' => null,
                'target' => null,
                'children' => [],
                '_depth' => 0, // Will be calculated
                '_parentId' => $page['parentUniqueId'] ?? null
            ];
            $lookup[$uniqueId] = $node;
        }

        // Calculate depth for each node
        foreach ($lookup as $uniqueId => &$node) {
            $node['_depth'] = $this->calculateNodeDepth($uniqueId, $lookup);
        }
        unset($node);

        // Second pass: build hierarchy, but only include nodes within maxDepth
        $skippedCount = 0;
        foreach ($pages as $page) {
            $uniqueId = $page['uniqueId'];
            $parentUniqueId = $page['parentUniqueId'] ?? null;
            $nodeDepth = $lookup[$uniqueId]['_depth'];

            // Skip nodes that are too deep
            if ($nodeDepth > $maxDepth) {
                $skippedCount++;
                continue;
            }

            if ($parentUniqueId && isset($lookup[$parentUniqueId])) {
                $parentDepth = $lookup[$parentUniqueId]['_depth'];

                // Only add if parent is also within depth limit
                if ($parentDepth <= $maxDepth) {
                    $lookup[$parentUniqueId]['children'][] = &$lookup[$uniqueId];
                }
            } else {
                // No parent or parent not in imported set = root level
                $tree[] = &$lookup[$uniqueId];
            }
        }

        if ($skippedCount > 0) {
            $this->logger->info("Skipped pages too deep for navigation", [
                'skippedCount' => $skippedCount,
                'maxDepth' => $maxDepth,
                'note' => 'These pages are still created and accessible via page structure'
            ]);
        }

        // Remove temporary depth tracking fields
        $this->cleanupTreeMetadata($tree);

        return $tree;
    }
    /**
     * Calculate depth of a node in the tree (0 = root)
     */
    private function calculateNodeDepth(string $uniqueId, array &$lookup, array &$visited = []): int {
        // Prevent infinite loops
        if (isset($visited[$uniqueId])) {
            return 999; // Very deep to exclude circular references
        }
        $visited[$uniqueId] = true;

        $node = $lookup[$uniqueId];
        if (!$node['_parentId'] || !isset($lookup[$node['_parentId']])) {
            return 0; // Root level
        }

        return 1 + $this->calculateNodeDepth($node['_parentId'], $lookup, $visited);
    }
    /**
     * Remove temporary metadata fields from tree
     */
    public function cleanupTreeMetadata(array &$tree): void {
        foreach ($tree as &$node) {
            unset($node['_depth']);
            unset($node['_parentId']);

            if (!empty($node['children'])) {
                $this->cleanupTreeMetadata($node['children']);
            }
        }
    }
    /**
     * Find index of navigation item by uniqueId (recursive search)
     * Returns path to item as array of indices
     */
    public function findNavigationItemPath(array $items, string $uniqueId, array $path = []): ?array {
        foreach ($items as $index => $item) {
            if (isset($item['uniqueId']) && $item['uniqueId'] === $uniqueId) {
                return array_merge($path, [$index]);
            }

            if (!empty($item['children'])) {
                $found = $this->findNavigationItemPath($item['children'], $uniqueId, array_merge($path, [$index, 'children']));
                if ($found !== null) {
                    return $found;
                }
            }
        }

        return null;
    }
    /**
     * Add pages to navigation array
     */
    public function addPagesToNavigation(array &$navItems, array $pageTree): void {
        foreach ($pageTree as $pageNode) {
            // Check if already exists
            $exists = false;
            foreach ($navItems as $existingItem) {
                if (isset($existingItem['uniqueId']) && $existingItem['uniqueId'] === $pageNode['uniqueId']) {
                    $exists = true;
                    break;
                }
            }

            if (!$exists) {
                $navItems[] = $pageNode;
            }
        }
    }
}
