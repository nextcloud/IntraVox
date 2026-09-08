<?php

declare(strict_types=1);

namespace OCA\IntraVox\Service\Folder;

use OCA\IntraVox\Service\Language\LanguageResolver;
use OCA\IntraVox\Service\Locator\PageLocator;
use OCP\Files\Folder;
use OCP\Files\NotFoundException;

/**
 * The explicit folder/location substrate of IntraVox — the ground every page
 * domain stands on.
 *
 * PageService historically WAS this substrate: the three protected seams
 * (getIntraVoxFolder / getLanguageFolder / getReadLanguageFolder) plus the
 * language resolution and path helpers lived on it as protected/private methods
 * that ~11 collaborators reached back through as $this-bound closures and that
 * 27 test-subclasses overrode. That implicit shared surface IS the entanglement.
 * FolderContext makes it one explicit, injectable object with a single front
 * door.
 *
 * Built as a FACADE OVER THE SEAMS: the ATOMIC seams (the mounted IntraVox
 * folder, the user's language, the primary language, the #75 real-content probe)
 * come in as $this-bound closures, so a PageService subclass that overrides
 * getIntraVoxFolder/getUserLanguage still wins — its override is captured by the
 * closure and flows through the context. What FolderContext OWNS is the
 * COMPOSITION on top of those atoms: the create-on-miss language-folder
 * resolution, the #75 effective-language order, and the path helpers. That
 * composition is the scattered logic being centralised; the atoms stay
 * overridable so the 27 subclasses keep working while they migrate one by one.
 *
 * NEVER owns page lookup (findPageByUniqueId) or the #70 permission decision.
 *
 * SHIPPED alongside a first non-seam consumer (getRelativePathFromRoot) so the
 * accessor is live and FolderContextSeamTest proves byte-equivalence with the
 * PageService seams before the seam-migration phases begin.
 */
final class FolderContext {
    private const DEFAULT_LANGUAGE = 'en';

    /**
     * @param \Closure(): \OCP\Files\Folder $intraVox getIntraVoxFolder seam
     * @param \Closure(): string $userLanguage getUserLanguage seam
     * @param \Closure(): string $primaryLanguage languageService->getPrimaryLanguage
     * @param \Closure(Folder): bool $hasRealContent the #75 real-content probe
     *   (page-lookup-bound, so injected rather than owned)
     * @param \Closure(): \OCP\Files\Folder|null $readLanguageFolder getReadLanguageFolder
     *   seam. FolderContext owns the SAME composition (effectiveLanguage ->
     *   write-target fallback), but this seam is honoured when supplied so the 26
     *   test-subclasses that override getReadLanguageFolder WHOLESALE keep winning
     *   — exactly as the atomic intraVox seam already flows. Null = use the owned
     *   composition (readLanguageFolderComposed).
     * @param \Closure(): \OCP\Files\Folder|null $languageFolder getLanguageFolder seam.
     *   Same story as readLanguageFolder: FolderContext owns the create-on-miss
     *   composition, but the seam wins when supplied so wholesale getLanguageFolder
     *   overrides keep intercepting. Null = use the owned composition
     *   (languageFolderComposed).
     */
    public function __construct(
        private \Closure $intraVox,
        private \Closure $userLanguage,
        private \Closure $primaryLanguage,
        private \Closure $hasRealContent,
        private LanguageResolver $language,
        private PageLocator $locator,
        private ?\Closure $readLanguageFolder = null,
        private ?\Closure $languageFolder = null,
    ) {
    }

    /** The mounted IntraVox folder (via the getIntraVoxFolder seam). */
    public function intraVox() {
        return ($this->intraVox)();
    }

    /** The current user's base language code (via the getUserLanguage seam). */
    public function userLanguage(): string {
        return ($this->userLanguage)();
    }

    /**
     * The write-target language folder for the current user, creating the
     * language (or default) folder on miss. Honours the getLanguageFolder seam
     * closure when supplied (so wholesale subclass overrides win); otherwise runs
     * the owned composition.
     */
    public function languageFolder() {
        if ($this->languageFolder !== null) {
            return ($this->languageFolder)();
        }
        return $this->languageFolderComposed();
    }

    /**
     * The create-on-miss write-target composition, owned here; verbatim from
     * PageService::getLanguageFolder(). Split out so languageFolder() can prefer an
     * injected seam without duplicating the body.
     */
    private function languageFolderComposed() {
        $baseFolder = $this->intraVox();
        $lang = $this->userLanguage();

        try {
            return $baseFolder->get($lang);
        } catch (NotFoundException $e) {
            if ($lang !== self::DEFAULT_LANGUAGE) {
                try {
                    return $baseFolder->get(self::DEFAULT_LANGUAGE);
                } catch (NotFoundException $e2) {
                    return $baseFolder->newFolder(self::DEFAULT_LANGUAGE);
                }
            }
            return $baseFolder->newFolder($lang);
        }
    }

    /**
     * The language a user is actually SHOWN (recommended-language fallback #75),
     * or null when nothing serveable. Composition owned here; verbatim from
     * PageService::resolveEffectiveLanguage(). The real-content probe is injected.
     */
    public function effectiveLanguage(): ?string {
        $candidates = $this->language->candidateOrder(
            $this->userLanguage(),
            ($this->primaryLanguage)()
        );

        $baseFolder = $this->intraVox();
        foreach ($candidates as $code) {
            try {
                $folder = $baseFolder->get($code);
            } catch (NotFoundException $e) {
                continue;
            }
            if ($folder instanceof Folder && ($this->hasRealContent)($folder)) {
                return $code;
            }
        }
        return null;
    }

    /**
     * The content folder for READING for the current user (#75 own -> recommended
     * -> en), falling back to the write-target. Honours the getReadLanguageFolder
     * seam closure when supplied (so wholesale subclass overrides win); otherwise
     * runs the owned composition.
     */
    public function readLanguageFolder(): Folder {
        if ($this->readLanguageFolder !== null) {
            return ($this->readLanguageFolder)();
        }
        return $this->readLanguageFolderComposed();
    }

    /**
     * The #75 read-folder composition, owned here; verbatim from
     * PageService::getReadLanguageFolder(). Split out so readLanguageFolder() can
     * prefer an injected seam without duplicating the body.
     */
    private function readLanguageFolderComposed(): Folder {
        $lang = $this->effectiveLanguage();
        if ($lang !== null) {
            try {
                $folder = $this->intraVox()->get($lang);
                if ($folder instanceof Folder) {
                    return $folder;
                }
            } catch (NotFoundException $e) {
                // fall through to the write-target folder
            }
        }
        return $this->languageFolder();
    }

    /**
     * The language folder for a given code, creating the code (or default) folder
     * on miss. Composition owned here; verbatim from
     * PageService::getLanguageFolderByCode().
     */
    public function languageFolderByCode(string $lang) {
        $baseFolder = $this->intraVox();

        try {
            return $baseFolder->get($lang);
        } catch (NotFoundException $e) {
            if ($lang !== self::DEFAULT_LANGUAGE) {
                try {
                    return $baseFolder->get(self::DEFAULT_LANGUAGE);
                } catch (NotFoundException $e2) {
                    return $baseFolder->newFolder(self::DEFAULT_LANGUAGE);
                }
            }
            return $baseFolder->newFolder($lang);
        }
    }

    /**
     * Which language content folder $folder sits in, or null. Verbatim from
     * PageService::languageOfFolder() (delegates to PageLocator with the root).
     */
    public function languageOfFolder(Folder $folder): ?string {
        return $this->locator->languageOfFolder($this->intraVox(), $folder);
    }

    /**
     * The IntraVox-root-relative path of $folder. Verbatim from
     * PageService::getRelativePathFromRoot().
     */
    public function relativePathFromRoot($folder): string {
        return $this->locator->relativePathFromRoot($this->intraVox(), $folder);
    }
}
