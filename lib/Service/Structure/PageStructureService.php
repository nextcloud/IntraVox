<?php

declare(strict_types=1);

namespace OCA\IntraVox\Service\Structure;

use OCA\IntraVox\Exception\CrossLanguageMoveException;
use OCA\IntraVox\Exception\ForbiddenException;
use OCA\IntraVox\Exception\PageNotFoundException;
use OCA\IntraVox\Service\Folder\FolderContext;
use OCA\IntraVox\Service\Homepage\HomepageResolverService;
use OCA\IntraVox\Service\LanguageService;
use OCA\IntraVox\Service\PageIndexService;
use OCA\IntraVox\Service\Path\PageDepthValidator;
use OCA\IntraVox\Service\Util\PageIdUtils;
use OCP\Files\NotFoundException;
use Psr\Log\LoggerInterface;

/**
 * The STRUCTURE domain — the shape of the intranet tree. Currently owns movePage
 * (relocating a page and its subtree). The tree walk and sibling reorder already
 * live in Tree/PageTreeBuilder and Reorder/PageReorderer; getPageTree's cache
 * orchestration + the #86/#70 tree-COW stay on PageService for now (they funnel
 * through the public getFolderPermissions permission surface, which belongs with
 * the future permission shell, not here).
 *
 * Folder-substrate concerns (languageFolder / languageOfFolder /
 * relativePathFromRoot) come from the injected FolderContext; the homepage check
 * from the injected HomepageResolverService; page lookup from the injected
 * PageLocator; cache invalidation from the injected PageCacheInvalidator. The
 * three seams that used to arrive as $this-bound closures are now self-sourced:
 * the page's own language folder and the language display name are private
 * methods here (over FolderContext + the injected LanguageService), and the
 * max-nesting-depth rule comes from the injected PageDepthValidator (shared with
 * createPage). PageMoveGuardTest and PageServiceMoveLanguageTest pin the
 * behaviour byte-for-byte.
 */
final class PageStructureService {
    public function __construct(
        private PageIdUtils $idUtils,
        private PageIndexService $pageIndexService,
        private LoggerInterface $logger,
        private FolderContext $folders,
        private HomepageResolverService $homepageResolver,
        private \OCA\IntraVox\Service\Cache\PageCacheInvalidator $cacheInvalidator,
        private \OCA\IntraVox\Service\Locator\PageLocator $locator,
        private LanguageService $languageService,
        private PageDepthValidator $depthValidator,
    ) {
    }

    /**
     * Move a page (with its whole subtree) under a different parent.
     *
     * Folder-substrate concerns (languageFolder / languageOfFolder /
     * relativePathFromRoot) come from the injected FolderContext; the page's own
     * language folder + the language display name are self-sourced (private methods
     * below); the max-nesting-depth rule from the injected PageDepthValidator. No
     * closures.
     */
    public function movePage(
        string $pageId,
        string $targetParentId
    ): void {
        if ($pageId === 'home') {
            throw new \InvalidArgumentException('The home page cannot be moved');
        }

        $languageFolderNode = $this->folders->languageFolder();

        // The cross-language locate takes a lazy IntraVox root (invoked per language
        // iteration inside the locator) — self-sourced from the injected FolderContext,
        // byte-identical to the old rootClosure the delegator built.
        $intraVoxRoot = fn(): \OCP\Files\Folder => $this->folders->intraVox();

        // Locate the source page folder, following it across language folders
        // like every other operation on an existing page (#90). This is safe
        // ONLY because the destination is anchored to the source's own language
        // below and the language guard backs it up: resolving the source
        // cross-language while leaving the destination on the user's language
        // is what would relocate content between languages.
        $source = strpos($pageId, 'page-') === 0
            ? $this->locator->locatePageAnyLanguage($intraVoxRoot, $languageFolderNode, $pageId)
            : $this->locator->locatePageBySlugAnyLanguage($intraVoxRoot, $languageFolderNode, $this->idUtils->sanitizeId($pageId));
        if (!$source || !isset($source['folder'])) {
            throw new PageNotFoundException('Page not found: ' . $pageId);
        }

        // The page's OWN language folder governs this move, not the user's.
        // Everything below (the root destination, the depth check, the language
        // guard) is anchored here so a move can never leave the tree the page
        // lives in. Falls back to the user's folder only when the language
        // cannot be derived, which keeps single-language installs unchanged.
        $sourceLanguageFolder = $this->languageFolderOfPageResult($source) ?? $languageFolderNode;

        // The configured homepage cannot be moved — reassign it first
        // (issue: configurable homepage).
        $sourceUniqueId = strpos($pageId, 'page-') === 0 ? $pageId : '';
        if ($sourceUniqueId === '' && isset($source['file'])) {
            $decoded = json_decode($source['file']->getContent(), true);
            $sourceUniqueId = is_array($decoded) ? ($decoded['uniqueId'] ?? '') : '';
        }
        if ($sourceUniqueId !== '' && $this->homepageResolver->isHomepage($sourceUniqueId)) {
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
                ? $this->locator->locatePageAnyLanguage($intraVoxRoot, $sourceLanguageFolder, $targetParentId)
                : $this->locator->findPageById($sourceLanguageFolder, $this->idUtils->sanitizeId($targetParentId));
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
        $sourceLanguage = $this->folders->languageOfFolder($sourceFolder);
        $targetLanguage = $this->folders->languageOfFolder($targetParentFolder);
        if ($sourceLanguage !== null && $targetLanguage !== null && $sourceLanguage !== $targetLanguage) {
            throw new CrossLanguageMoveException(sprintf(
                'This page is in %s and cannot be moved into the %s structure. Pages stay in the language they were written in.',
                $this->languageDisplayName($sourceLanguage),
                $this->languageDisplayName($targetLanguage)
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
        $targetRelPath = $this->folders->relativePathFromRoot($targetParentFolder);
        $this->depthValidator->validate($targetRelPath);

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
                ? $this->locator->findPageByUniqueId($targetParentFolder, $pageId)
                : $this->locator->findPageById($targetParentFolder, $this->idUtils->sanitizeId($pageId));
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
        $this->cacheInvalidator->invalidate();
    }

    /**
     * The page's OWN language folder, derived from a locate result — the #90
     * anchor that keeps a move inside the source's language. Verbatim from
     * PageService::languageFolderOfPageResult (now self-sourced here from the
     * injected FolderContext instead of arriving as a closure).
     */
    private function languageFolderOfPageResult(array $result): ?\OCP\Files\Folder {
        $folder = $result['folder'] ?? null;
        if (!($folder instanceof \OCP\Files\Folder)) {
            return null;
        }

        $language = $this->folders->languageOfFolder($folder);
        if ($language === null) {
            return null;
        }

        try {
            $candidate = $this->folders->intraVox()->get($language);
            return $candidate instanceof \OCP\Files\Folder ? $candidate : null;
        } catch (NotFoundException $e) {
            return null;
        }
    }

    /**
     * The human-readable name for a content-language code, used in the
     * cross-language refusal message. Verbatim from PageService::languageDisplayName
     * (now self-sourced here from the injected LanguageService instead of arriving
     * as a closure).
     */
    private function languageDisplayName(string $code): string {
        try {
            foreach ($this->languageService->getAvailableLanguages() as $lang) {
                // getAvailableLanguages() is typed array{code,name}, so both keys
                // always exist — the old `?? ''` defensive reads (baseline-suppressed
                // on PageService) are dropped rather than carried into a file with no
                // baseline. An empty-string name is still a real value the guard below
                // handles.
                if ($lang['code'] === $code) {
                    $name = $lang['name'];
                    if ($name === '') {
                        return strtoupper($code);
                    }
                    // Nextcloud's names describe INTERFACE translations and
                    // carry variant suffixes ('English (US)', 'Deutsch
                    // (Persönlich: Du)'). A content folder is a plain code, so
                    // drop the parenthesised part — "this page is in Deutsch
                    // (Persönlich: Du)" is nonsense to a reader.
                    $base = trim(explode('(', $name)[0]);
                    return $base !== '' ? $base : $name;
                }
            }
        } catch (\Throwable $e) {
            // Naming is cosmetic; never let it break the operation's real error.
        }
        return strtoupper($code);
    }
}
