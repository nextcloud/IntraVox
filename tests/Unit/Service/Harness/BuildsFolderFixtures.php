<?php
declare(strict_types=1);

namespace OCA\IntraVox\Tests\Unit\Service\Harness;

use OCA\IntraVox\Service\Folder\FolderContext;
use OCA\IntraVox\Service\Language\LanguageResolver;
use OCA\IntraVox\Service\Locator\PageLocator;
use OCP\Files\Folder;
use Psr\Log\LoggerInterface;

/**
 * Facade-free FolderContext fixtures (COMMIT 0, fase-10 harness split).
 * A real FolderContext wired for a test (seam closures for read/write language
 * folders). Lifted verbatim out of BuildsPageService.
 */
trait BuildsFolderFixtures {
    /**
     * Build a real FolderContext wired for a test, so a migrating subclass can
     * inject it directly (folderContext: ...) instead of overriding the three
     * protected folder seams to smuggle a fake folder in.
     *
     * FolderContext is final and closure-driven, so the "fake" IS a real one
     * whose seam closures return the given fixtures — identical in spirit to how
     * FolderContextSeamTest constructs it. Once folderContext is set on a
     * PageService, folders() returns it verbatim (??=) and never rebuilds from
     * the seams, so this is the injection point that lets the seam overrides
     * finally go away (clean-target step 8).
     *
     * @param Folder|null $readLanguageFolder what readLanguageFolder() returns.
     *   When given it is injected as the getReadLanguageFolder seam closure so it
     *   wins wholesale (matches production). Null -> the owned composition runs.
     * @param Folder|null $intraVox base folder for intraVox()/relativePathFromRoot;
     *   defaults to $readLanguageFolder when omitted.
     * @param string $userLanguage what userLanguage() returns.
     * @param string $primaryLanguage the primary-language seam.
     */
    protected function fakeFolderContext(
        ?Folder $readLanguageFolder = null,
        ?Folder $intraVox = null,
        string $userLanguage = 'en',
        string $primaryLanguage = 'en',
        ?Folder $languageFolder = null
    ): FolderContext {
        $base = $intraVox ?? $readLanguageFolder;
        $resolver = new LanguageResolver();
        $locator = new PageLocator(
            $this->createMock(\OCA\IntraVox\Service\PageIndexService::class),
            $this->createMock(LoggerInterface::class)
        );

        // FolderContext is DI-first-class now; the fixture supplies the substrate
        // atoms as real deps. $base is passed as the intraVoxOverride so the mount
        // walk (rootFolder->getUserFolder(userId)->get('IntraVox')) is never run —
        // fixtures inject the fake mount directly. config->getUserValue returns
        // $userLanguage (a base code, so baseLanguageCode() is idempotent) and the
        // LanguageService mock returns $primaryLanguage. The #75 real-content probe
        // now runs FolderContext's own resolveLanguageHomepageData over $locator,
        // which the loose-home.json fixtures satisfy via its form-2 branch.
        $config = $this->createMock(\OCP\IConfig::class);
        $config->method('getUserValue')->willReturn($userLanguage);
        $languageService = $this->createMock(\OCA\IntraVox\Service\LanguageService::class);
        $languageService->method('getPrimaryLanguage')->willReturn($primaryLanguage);

        return new FolderContext(
            $this->createMock(\OCP\Files\IRootFolder::class), // unused: $base override short-circuits the mount walk
            'test-user',                                       // non-empty so userLanguage() reads config, not the logged-out 'en'
            $config,
            $languageService,
            $resolver,
            $locator,
            $base, // intraVoxOverride — the fake mount
            // getReadLanguageFolder seam: wired when a read folder is given, so a
            // wholesale override is reproduced. Null -> owned composition.
            $readLanguageFolder === null
                ? null
                : fn(): Folder => $readLanguageFolder,
            // getLanguageFolder seam: wired when a write-target folder is given.
            // Null -> owned create-on-miss composition (intraVox->get(userLang)).
            $languageFolder === null
                ? null
                : fn(): Folder => $languageFolder
        );
    }

    /**
     * A real (final) FolderContext whose readLanguageFolder() THROWS the given
     * exception — the getReadLanguageFolder seam closure re-throws, so any body that
     * opens with `$this->folders->readLanguageFolder()` surfaces it verbatim. Lets a
     * fixture reproduce the "IntraVox folder not found" / read-error branch without a
     * subclass. All other substrate atoms are inert (the read seam fires first).
     */
    protected function fakeFolderContextThrowingRead(\Throwable $e): FolderContext {
        $config = $this->createMock(\OCP\IConfig::class);
        $config->method('getUserValue')->willReturn('en');
        $languageService = $this->createMock(\OCA\IntraVox\Service\LanguageService::class);
        $languageService->method('getPrimaryLanguage')->willReturn('en');

        return new FolderContext(
            $this->createMock(\OCP\Files\IRootFolder::class),
            'test-user',
            $config,
            $languageService,
            new LanguageResolver(),
            new PageLocator(
                $this->createMock(\OCA\IntraVox\Service\PageIndexService::class),
                $this->createMock(LoggerInterface::class)
            ),
            null,
            static function () use ($e): Folder { throw $e; }
        );
    }
}
