<?php

declare(strict_types=1);

namespace OCA\IntraVox\Service\Structure;

use OCA\IntraVox\Exception\CrossLanguageMoveException;
use OCA\IntraVox\Exception\ForbiddenException;
use OCA\IntraVox\Exception\PageNotFoundException;
use OCA\IntraVox\Service\PageIndexService;
use OCA\IntraVox\Service\Util\PageIdUtils;
use Psr\Log\LoggerInterface;

/**
 * The STRUCTURE domain — the shape of the intranet tree. Currently owns movePage
 * (relocating a page and its subtree). The tree walk and sibling reorder already
 * live in Tree/PageTreeBuilder and Reorder/PageReorderer; getPageTree's cache
 * orchestration + the #86/#70 tree-COW stay on PageService for now (they funnel
 * through the public getFolderPermissions permission surface, which belongs with
 * the future permission shell, not here).
 *
 * The protected folder seams stay on PageService; every seam / cross-language
 * lookup / folder helper this needs comes in as a $this-bound closure, so the
 * seam-subclasses keep intercepting with zero test edits. PageMoveGuardTest and
 * PageServiceMoveLanguageTest pin the behaviour byte-for-byte.
 */
final class PageStructureService {
    public function __construct(
        private PageIdUtils $idUtils,
        private PageIndexService $pageIndexService,
        private LoggerInterface $logger,
    ) {
    }

    /**
     * Move a page (with its whole subtree) under a different parent.
     *
     * @param \Closure(): \OCP\Files\Folder $languageFolder getLanguageFolder seam
     * @param \Closure(\OCP\Files\Folder, string): ?array $locatePageAnyLanguage
     * @param \Closure(\OCP\Files\Folder, string): ?array $locatePageBySlugAnyLanguage
     * @param \Closure(\OCP\Files\Folder, string): ?array $findPageById
     * @param \Closure(\OCP\Files\Folder, string): ?array $findPageByUniqueId
     * @param \Closure(array): ?\OCP\Files\Folder $languageFolderOfPageResult
     * @param \Closure(string): bool $isHomepage
     * @param \Closure(\OCP\Files\Folder): ?string $languageOfFolder
     * @param \Closure(string): string $languageDisplayName
     * @param \Closure(\OCP\Files\Folder): string $relativePathFromRoot
     * @param \Closure(string): void $validateDepth
     * @param \Closure(): void $clearCache
     */
    public function movePage(
        string $pageId,
        string $targetParentId,
        \Closure $languageFolder,
        \Closure $locatePageAnyLanguage,
        \Closure $locatePageBySlugAnyLanguage,
        \Closure $findPageById,
        \Closure $findPageByUniqueId,
        \Closure $languageFolderOfPageResult,
        \Closure $isHomepage,
        \Closure $languageOfFolder,
        \Closure $languageDisplayName,
        \Closure $relativePathFromRoot,
        \Closure $validateDepth,
        \Closure $clearCache
    ): void {
        if ($pageId === 'home') {
            throw new \InvalidArgumentException('The home page cannot be moved');
        }

        $languageFolderNode = $languageFolder();

        // Locate the source page folder, following it across language folders
        // like every other operation on an existing page (#90). This is safe
        // ONLY because the destination is anchored to the source's own language
        // below and the language guard backs it up: resolving the source
        // cross-language while leaving the destination on the user's language
        // is what would relocate content between languages.
        $source = strpos($pageId, 'page-') === 0
            ? $locatePageAnyLanguage($languageFolderNode, $pageId)
            : $locatePageBySlugAnyLanguage($languageFolderNode, $this->idUtils->sanitizeId($pageId));
        if (!$source || !isset($source['folder'])) {
            throw new PageNotFoundException('Page not found: ' . $pageId);
        }

        // The page's OWN language folder governs this move, not the user's.
        // Everything below (the root destination, the depth check, the language
        // guard) is anchored here so a move can never leave the tree the page
        // lives in. Falls back to the user's folder only when the language
        // cannot be derived, which keeps single-language installs unchanged.
        $sourceLanguageFolder = $languageFolderOfPageResult($source) ?? $languageFolderNode;

        // The configured homepage cannot be moved — reassign it first
        // (issue: configurable homepage).
        $sourceUniqueId = strpos($pageId, 'page-') === 0 ? $pageId : '';
        if ($sourceUniqueId === '' && isset($source['file'])) {
            $decoded = json_decode($source['file']->getContent(), true);
            $sourceUniqueId = is_array($decoded) ? ($decoded['uniqueId'] ?? '') : '';
        }
        if ($sourceUniqueId !== '' && $isHomepage($sourceUniqueId)) {
            throw new \InvalidArgumentException('HOMEPAGE_PROTECTED');
        }

        $sourceFolder = $source['folder'];
        $sourcePath = $sourceFolder->getPath();

        // Resolve the destination parent folder (root or a page's own folder).
        if ($targetParentId === '' ) {
            // Root of the page's OWN language, never the user's. Using the
            // user's folder here would physically relocate the page (and its
            // whole subtree) into another language the moment the source
            // resolved cross-language — silently, with no undo.
            $targetParentFolder = $sourceLanguageFolder;
        } else {
            // Search from the source's language first: a move within one tree
            // is the normal case, and it keeps the parent lookup consistent
            // with the source rather than with the user's profile language.
            $targetResult = strpos($targetParentId, 'page-') === 0
                ? $locatePageAnyLanguage($sourceLanguageFolder, $targetParentId)
                : $findPageById($sourceLanguageFolder, $this->idUtils->sanitizeId($targetParentId));
            if (!$targetResult || !isset($targetResult['folder'])) {
                throw new PageNotFoundException('Target parent page not found: ' . $targetParentId);
            }
            $targetParentFolder = $targetResult['folder'];
        }
        $targetParentPath = $targetParentFolder->getPath();

        // Language guard — the backstop for everything above. Even if a future
        // change miscomputes the destination, a move that would cross language
        // folders is refused rather than performed. Language folders are
        // independent content trees, so this is a relocation between intranets,
        // not a translation.
        $sourceLanguage = $languageOfFolder($sourceFolder);
        $targetLanguage = $languageOfFolder($targetParentFolder);
        if ($sourceLanguage !== null && $targetLanguage !== null && $sourceLanguage !== $targetLanguage) {
            throw new CrossLanguageMoveException(sprintf(
                'This page is in %s and cannot be moved into the %s structure. Pages stay in the language they were written in.',
                $languageDisplayName($sourceLanguage),
                $languageDisplayName($targetLanguage)
            ));
        }

        // Cycle guard: refuse moving into itself or one of its own descendants,
        // which would detach (and lose) the subtree.
        if ($targetParentPath === $sourcePath
            || strpos($targetParentPath . '/', $sourcePath . '/') === 0) {
            throw new \InvalidArgumentException('Cannot move a page into itself or its descendant');
        }

        // No-op if already directly under the target parent.
        if (dirname($sourcePath) === $targetParentPath) {
            return;
        }

        // Respect the configured max nesting depth at the destination.
        $targetRelPath = $relativePathFromRoot($targetParentFolder);
        $validateDepth($targetRelPath);

        // Permission preflight. movePage() had none at all: it called move()
        // and relied on the filesystem to throw, which surfaces as an opaque
        // 500 and leaves any partial state unguarded. Mirrors the checks in
        // createPageAtPath() (isCreatable) and updatePage() (isUpdateable).
        // A move both removes from the source and creates at the destination,
        // so both sides are checked.
        if (!$sourceFolder->isDeletable()) {
            throw new ForbiddenException('You do not have permission to move this page');
        }
        if (!$targetParentFolder->isCreatable()) {
            throw new ForbiddenException('You do not have permission to move a page here');
        }

        // Resolve a non-colliding folder name at the destination (mirror createPage).
        $baseName = $sourceFolder->getName();
        $newName = $baseName;
        $counter = 2;
        while ($targetParentFolder->nodeExists($newName)) {
            $newName = $baseName . '-' . $counter;
            $counter++;
        }

        // Relocate the whole folder; children travel inside it.
        $newPath = $targetParentPath . '/' . $newName;
        $sourceFolder->move($newPath);

        // The index stores a path per page, and the move just invalidated it
        // for this page AND every descendant that travelled with it. Rewriting
        // the prefix is one statement per affected row; re-walking the subtree
        // would be the filesystem traversal the index exists to avoid.
        // Non-blocking: the move already succeeded on disk, so a failure here
        // must not surface as a failed move — `occ intravox:reindex` repairs it.
        try {
            $this->pageIndexService->repathSubtree($sourcePath, $newPath);
        } catch (\Throwable $e) {
            $this->logger->warning('movePage: could not repath index subtree', [
                'from' => $sourcePath,
                'to' => $newPath,
                'error' => $e->getMessage(),
            ]);
        }

        // Send the moved page to the end of its new siblings by clearing its
        // explicit order — the stable comparator then places it after ordered
        // siblings, i.e. last. (A fresh reorder can pin it precisely later.)
        try {
            $movedResult = strpos($pageId, 'page-') === 0
                ? $findPageByUniqueId($targetParentFolder, $pageId)
                : $findPageById($targetParentFolder, $this->idUtils->sanitizeId($pageId));
            if ($movedResult && isset($movedResult['file'])) {
                $file = $movedResult['file'];
                $data = json_decode($file->getContent(), true);
                if (is_array($data) && array_key_exists('order', $data)) {
                    unset($data['order']);
                    $file->putContent(json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
                }
            }
        } catch (\Throwable $e) {
            // Non-fatal: the move succeeded; ordering just falls back to legacy.
            $this->logger->warning('movePage: could not reset order after move', ['error' => $e->getMessage()]);
        }

        // Critical: refresh tree + permission caches so the move is visible.
        $clearCache();
    }
}
