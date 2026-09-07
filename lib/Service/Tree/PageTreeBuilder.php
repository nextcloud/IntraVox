<?php

declare(strict_types=1);

namespace OCA\IntraVox\Service\Tree;

use OCA\IntraVox\Service\Folder\FolderContext;
use OCA\IntraVox\Service\Locator\PageLocator;
use OCA\IntraVox\Service\Path\PagePathHelper;
use OCA\IntraVox\Service\PermissionService;

/**
 * The recursive page-tree walk, extracted verbatim from
 * PageService::buildPageTree(). Given a language (or sub-)folder it appends the
 * ordered child nodes to $tree, recursing into subfolders, emitting synthetic
 * non-navigable placeholder nodes for bare ancestor folders that still hold
 * pages below them, skipping infrastructure folders, and applying the stable
 * sibling order (issue #69).
 *
 * The cache/home.json/homepage-pointer orchestration and the per-user
 * permission recompute (issue #86/#70) stay on PageService — this class owns
 * only the walk. Folder-root-relative paths and the current-user language come
 * from the injected FolderContext (the substrate); everything else is a direct
 * collaborator.
 *
 * Behaviour is byte-identical to the original body: PageTreePlaceholderTest
 * (which drives PageService::buildPageTree by reflection through a thin
 * delegator) and PageTreeBuilderTest (which drives build() directly) pin it.
 */
final class PageTreeBuilder {
    public function __construct(
        private PageLocator $locator,
        private PermissionService $permissionService,
        private FolderContext $folders,
    ) {
    }

    /**
     * Append the ordered child page nodes of $folder to $tree (by reference,
     * matching the original recursion), recursing into each subfolder.
     *
     * @param \OCP\Files\Folder $folder
     * @param array<int,array> $tree accumulator, appended to in place
     */
    public function build($folder, array &$tree, ?string $currentPageId, ?string $language = null): void {
        // Collect siblings locally so we can apply the stable order comparator
        // (issue #69) before appending them to the tree in the right sequence.
        $nodes = [];
        foreach ($this->locator->cachedDirectoryListing($folder) as $item) {
            if ($item->getType() !== \OCP\Files\FileInfo::TYPE_FOLDER) {
                continue;
            }

            $folderName = $item->getName();

            // Skip special folders
            if (PagePathHelper::isInfrastructureFolder($folderName)) {
                continue;
            }

            // Underscore- and dot-prefixed folders are infrastructure (_media,
            // _resources, _templates, hidden dirs). They never held pages, but
            // the placeholder recursion below WOULD walk into them — and
            // _templates does contain page-shaped JSON that must never surface
            // as tree nodes — so they are excluded by name shape, not by list.
            if (str_starts_with($folderName, '_') || str_starts_with($folderName, '.')) {
                continue;
            }

            // Skip folders starting with emoji (images folders)
            if (preg_match('/^[\x{1F300}-\x{1F9FF}]/u', $folderName)) {
                continue;
            }

            $foundPage = false;

            // Look for {foldername}.json inside the folder
            try {
                $jsonFile = $item->get($folderName . '.json');

                // Check if file is readable and user has access
                if (!$jsonFile->isReadable()) {
                    continue;
                }

                // Use cached file content to avoid repeated reads
                $content = $jsonFile instanceof \OCP\Files\File
                    ? $this->locator->cachedFileContent($jsonFile)
                    : @$jsonFile->getContent();

                if ($content === false || $content === null) {
                    continue;
                }

                $data = json_decode($content, true);

                if ($data && isset($data['uniqueId'], $data['title'])) {
                    // Folder permissions (respects ACLs + mount writability).
                    $perm = $this->permissionService->permissionsFromNode($item);

                    // Skip if user can't read this folder
                    if (!$perm['canRead']) {
                        continue;
                    }

                    $pageNode = [
                        'uniqueId' => $data['uniqueId'],
                        'title' => $data['title'],
                        'status' => $data['status'] ?? 'published',
                        // fileId of the page JSON, so the tree gate can resolve the
                        // publish/expiration MetaVox fields for scheduled visibility.
                        'fileId' => ($jsonFile instanceof \OCP\Files\File) ? $jsonFile->getId() : null,
                        'path' => $this->folders->relativePathFromRoot($item),
                        'language' => $language ?? $this->folders->userLanguage(),
                        'isCurrent' => ($currentPageId === $data['uniqueId']),
                        'children' => [],
                        'permissions' => $perm
                    ];

                    // Carry the sibling order (issue #69) for the comparator. Kept
                    // out of the public node shape below — it's stripped after sort.
                    if (isset($data['order']) && is_int($data['order'])) {
                        $pageNode['order'] = $data['order'];
                    }

                    // Recursively get children
                    $this->build($item, $pageNode['children'], $currentPageId, $language);

                    $nodes[] = $pageNode;
                    $foundPage = true;
                }
            } catch (\Exception $e) {
                // This folder doesn't contain a valid page or can't be read, continue
            } catch (\Throwable $e) {
                // Catch any other errors
                continue;
            }

            // A folder without a page of its own can still hold pages below it —
            // exactly what translating a deep page before its ancestors produces:
            // createTranslation mirrors the source path and creates the missing
            // levels as bare folders. Skipping such a folder made every page
            // underneath unreachable in the tree, while search, breadcrumb and
            // direct links all still worked — a ghost page for anyone browsing.
            //
            // The breadcrumb already renders a missing ancestor as a plain,
            // non-clickable label; the tree now applies the same rule: recurse,
            // and when pages exist below, emit a non-navigable pass-through
            // node. A bare folder with nothing underneath still renders nothing.
            if (!$foundPage) {
                try {
                    $perm = $this->permissionService->permissionsFromNode($item);
                    if (!$perm['canRead']) {
                        continue;
                    }
                    $children = [];
                    $this->build($item, $children, $currentPageId, $language);
                    if ($children !== []) {
                        $nodes[] = [
                            // Synthetic, stable identity: never navigable, but
                            // the tree needs a key for expand/collapse state
                            // and list rendering. The 'folder:' prefix cannot
                            // collide with real ids, which are 'page-…'.
                            'uniqueId' => 'folder:' . $this->folders->relativePathFromRoot($item),
                            // Same label derivation the breadcrumb uses for a
                            // missing ancestor. This is the SOURCE-language
                            // slug until the ancestor is translated — accepted,
                            // and itself a nudge to translate it.
                            'title' => ucfirst(str_replace('-', ' ', $folderName)),
                            'status' => 'published',
                            'isPlaceholder' => true,
                            'fileId' => null,
                            'path' => $this->folders->relativePathFromRoot($item),
                            'language' => $language ?? $this->folders->userLanguage(),
                            'isCurrent' => false,
                            'children' => $children,
                            'permissions' => $perm,
                        ];
                    }
                } catch (\Throwable $e) {
                    continue;
                }
            }
        }

        // Apply the stable sibling order (issue #69) and drop the internal
        // 'order' key so the tree shape the frontend sees is unchanged.
        foreach ($this->sortSiblingsByOrder($nodes) as $node) {
            unset($node['order']);
            $tree[] = $node;
        }
    }

    /**
     * Stable ordering of sibling nodes by their optional integer 'order' field
     * (issue #69): ordered nodes first, ascending; ties and unordered nodes keep
     * their original relative position. Decorate/usort/undecorate so the sort is
     * stable across PHP versions.
     *
     * @param array<int,array> $siblings
     * @return array<int,array>
     */
    private function sortSiblingsByOrder(array $siblings): array {
        $decorated = [];
        foreach ($siblings as $i => $node) {
            $decorated[] = ['i' => $i, 'node' => $node];
        }
        usort($decorated, function ($a, $b) {
            $ao = $a['node']['order'] ?? null;
            $bo = $b['node']['order'] ?? null;
            $aHas = is_int($ao);
            $bHas = is_int($bo);
            if ($aHas && $bHas) {
                return ($ao <=> $bo) ?: ($a['i'] <=> $b['i']);
            }
            if ($aHas !== $bHas) {
                return $aHas ? -1 : 1;
            }
            return $a['i'] <=> $b['i'];
        });
        return array_map(fn ($d) => $d['node'], $decorated);
    }
}
