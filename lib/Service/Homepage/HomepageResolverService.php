<?php

declare(strict_types=1);

namespace OCA\IntraVox\Service\Homepage;

use OCA\IntraVox\Service\Folder\FolderContext;
use OCA\IntraVox\Service\HomepageService;

/**
 * The HOMEPAGE-resolution domain carved out of the PageService god-class: the
 * read + write side of "which page is the homepage for this language".
 *
 *   - getHomepageUniqueId / resolveHomepageNodeUniqueId: resolve the concrete
 *     uniqueId of the homepage (honouring an admin pointer, else the legacy
 *     loose home.json, else a bare 'home' / first-tree-node last resort).
 *   - setHomepage: point the homepage at a root-level page.
 *
 * The pointer store lives in the HomepageService engine (ctor-injected). Page
 * lookup, the language-folder resolver, the cached read, the language
 * resolution, the homepage predicate and the cache-clear stay on PageService —
 * they are shared far beyond homepage, reflection-anchored, or a pinned public
 * seam — and arrive as bound closures. The predicate closure in particular
 * keeps the resident, subclass-overridable homepage seam authoritative for
 * setHomepage's already-home short-circuit.
 */
final class HomepageResolverService {

    /**
     * @param \Closure(string): \OCP\Files\Folder $languageFolderByCode
     * @param \Closure(\OCP\Files\Folder, string): ?array $locatePage
     * @param \Closure(\OCP\Files\File): string $cachedFileContent
     * @param \Closure(): ?string $effectiveLanguage
     * @param \Closure(): string $userLanguage
     * @param \Closure(string, ?string): bool $homepagePredicate
     * @param \Closure(): void $invalidateCache
     */
    public function __construct(
        private HomepageService $homepageService,
        private FolderContext $folders,
        private \Closure $languageFolderByCode,
        private \Closure $locatePage,
        private \Closure $cachedFileContent,
        private \Closure $effectiveLanguage,
        private \Closure $userLanguage,
        private \Closure $homepagePredicate,
        private \Closure $invalidateCache,
    ) {
    }

    /**
     * The concrete uniqueId of the homepage for a language.
     *
     * Honours a configured pointer when it resolves to an existing page; else
     * reads the legacy loose home.json's real uniqueId; else the bare 'home'.
     */
    public function getHomepageUniqueId(?string $language = null): string {
        // Without an explicit language, use the language the user is actually
        // shown (recommended-language fallback, #75) so the homepage pointer is
        // resolved in — and checked against — the served language's folder.
        $lang = $language ?? ($this->effectiveLanguage)() ?? ($this->userLanguage)();

        $pointer = $this->homepageService->getHomepageUniqueId($lang);
        if ($pointer !== null && $pointer !== '' && $pointer !== 'home') {
            // Only honour the pointer when it resolves to an existing page.
            try {
                $folder = ($this->languageFolderByCode)($lang);
                if (($this->locatePage)($folder, $pointer) !== null) {
                    return $pointer;
                }
            } catch (\Exception $e) {
                // Fall through to the legacy default.
            }
        }

        // Legacy default: the loose home.json in the language root.
        //
        // Resolve it to the uniqueId the file actually carries. Returning the
        // bare string 'home' hands the frontend an id that matches no page in
        // listPages(), so `pages.find(p => p.uniqueId === homepageUniqueId)`
        // came up empty and the reader fell through to a slug/path heuristic
        // that ends at `pages[0]` — the alphabetically first page. On dev that
        // put every Dutch reader on "API Referentie" instead of "Welkom bij
        // IntraVox", while English (which uses the normalised home/home.json
        // layout, so it already had a real uniqueId) worked fine.
        //
        // Falls back to the literal 'home' when the file is missing or carries
        // no uniqueId, which is the pre-existing behaviour and what the rest of
        // the legacy path still understands.
        try {
            $folder = ($this->languageFolderByCode)($lang);
            $homeFile = $folder->get('home.json');
            if ($homeFile instanceof \OCP\Files\File) {
                $data = json_decode(($this->cachedFileContent)($homeFile), true);
                $homeUniqueId = is_array($data) ? ($data['uniqueId'] ?? null) : null;
                if (is_string($homeUniqueId) && $homeUniqueId !== '') {
                    return $homeUniqueId;
                }
            }
        } catch (\Exception $e) {
            // No loose home.json in this language — fall through.
        }

        return 'home';
    }

    /**
     * The concrete uniqueId (page-…) of the homepage for a language, suitable
     * for badging/comparison in the UI. When a pointer is set it is that
     * uniqueId; otherwise it resolves the legacy loose home.json to its real
     * uniqueId (not the literal 'home'). Optionally pass an already-built tree
     * to resolve the legacy home from it without an extra read.
     *
     * @param array<int,array>|null $tree Optional pre-built page tree.
     */
    public function resolveHomepageNodeUniqueId(?string $language = null, ?array $tree = null): string {
        $resolved = $this->getHomepageUniqueId($language);
        if ($resolved !== 'home') {
            return $resolved;
        }

        // Legacy default: map 'home' to the real uniqueId of the loose home.json.
        try {
            $folder = ($this->languageFolderByCode)($language ?? ($this->userLanguage)());
            if ($folder->nodeExists('home.json')) {
                $homeFile = $folder->get('home.json');
                // A loose home.json is always a File; the instanceof narrows the
                // Node type for the getContent() read (a directory named
                // home.json — never valid — is skipped rather than fatal-erroring
                // on getContent(), matching getHomepageUniqueId's own guard).
                if ($homeFile instanceof \OCP\Files\File) {
                    $data = json_decode($homeFile->getContent(), true);
                    if (is_array($data) && !empty($data['uniqueId'])) {
                        return (string)$data['uniqueId'];
                    }
                }
            }
        } catch (\Exception $e) {
            // Fall through.
        }

        // Last resort: first root node of a supplied tree.
        if (is_array($tree) && isset($tree[0]['uniqueId'])) {
            return (string)$tree[0]['uniqueId'];
        }
        return 'home';
    }

    /**
     * Point the homepage at a root-level page (pointer only — pages never move).
     *
     * @throws \InvalidArgumentException when the page is missing or not root-level
     */
    public function setHomepage(string $uniqueId): void {
        $lang = $this->folders->userLanguage();
        $languageFolder = $this->folders->languageFolder();

        // Resolve the target and require it to be a real page.
        $target = ($this->locatePage)($languageFolder, $uniqueId);
        if ($target === null || !isset($target['folder'])) {
            throw new \InvalidArgumentException('Page not found');
        }

        // Must be a ROOT-level page: its folder's parent is the language root
        // (or it is the loose home itself). Compare parent paths.
        $isLooseHome = !empty($target['isHome']);
        if (!$isLooseHome) {
            $parentPath = dirname($target['folder']->getPath());
            if ($parentPath !== $languageFolder->getPath()) {
                throw new \InvalidArgumentException('Only root-level pages can be the homepage');
            }
        }

        // If the target is already the resolved homepage, nothing to do.
        if (($this->homepagePredicate)($uniqueId, $lang)) {
            return;
        }

        // Pages never move when the homepage changes — only the pointer shifts.
        // The old loose home.json simply stays where it is and shows up as a
        // normal root page once the pointer designates a different page.
        $this->homepageService->setHomepageUniqueId($uniqueId, $lang);
        ($this->invalidateCache)();
    }
}
