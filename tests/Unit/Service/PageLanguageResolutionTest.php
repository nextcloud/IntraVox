<?php
declare(strict_types=1);

namespace OCA\IntraVox\Tests\Unit\Service;

use OCA\IntraVox\Service\LanguageService;
use OCA\IntraVox\Service\PageService;
use OCA\IntraVox\Tests\Unit\Service\Harness\BuildsPageService;
use OCP\Files\File;
use OCP\Files\FileInfo;
use OCP\Files\Folder;
use OCP\Files\IRootFolder;
use OCP\Files\NotFoundException;
use OCP\IConfig;
use PHPUnit\Framework\TestCase;

/**
 * Characterization of PageService's "cluster C" language resolution — the four
 * bodies that decide which language a user reads/writes and where those folders
 * live:
 *
 *   - getUserLanguage()          (private) — the user's own display language
 *   - resolveEffectiveLanguage() (private) — the SERVED language, #75 fallback
 *   - getReadLanguageFolder()    (protected seam) — folder to READ from
 *   - getLanguageFolderByCode()  (private) — folder for a code, CREATE-on-miss
 *
 * Why this test exists before Phase 9 extracts a LanguageResolver: every other
 * PageService test OVERRIDES the folder seams (getIntraVoxFolder /
 * getReadLanguageFolder / getLanguageFolder), which means it BYPASSES these
 * bodies entirely. The whole resolver had zero behavioural coverage — a Phase-9
 * extraction could silently change the candidate order, the malformed-code
 * guard, or (most dangerously) drop the create-on-miss folder side-effect and
 * every existing test would stay green.
 *
 * So this file deliberately does NOT override the seams. It drives the REAL
 * getIntraVoxFolder() body through a mocked rootFolder->getUserFolder()->get(
 * 'IntraVox'), so getUserLanguage/resolveEffectiveLanguage/getReadLanguageFolder
 * /getLanguageFolderByCode all run their actual code. Private methods are
 * exercised through ReflectionMethod; the protected getReadLanguageFolder is
 * called directly (this subclass does not shadow it).
 */
class PageLanguageResolutionTest extends TestCase {

    use BuildsPageService;

    /** newFolder() calls recorded by base-folder mocks, to assert the create-on-miss side-effect. */
    private array $created = [];

    /**
     * A File whose getContent() returns the given JSON.
     */
    private function jsonFile(string $path, array $json): File {
        $file = $this->createMock(File::class);
        $file->method('getName')->willReturn(basename($path));
        $file->method('getType')->willReturn(FileInfo::TYPE_FILE);
        $file->method('getPath')->willReturn($path);
        $file->method('getContent')->willReturn(json_encode($json));
        $file->method('getId')->willReturn(abs(crc32($path)));
        return $file;
    }

    /**
     * A language folder. Pass $home to give it a loose home.json — with a real
     * title it counts as "real content" (resolveLanguageHomepageData tak 2),
     * with `_generated` set it is a placeholder that does NOT count.
     *
     * @param array<string,\OCP\Files\Node> $extra additional named children
     */
    private function langFolder(string $path, ?array $home = null, array $extra = []): Folder {
        $children = $extra;
        if ($home !== null) {
            $children['home.json'] = $this->jsonFile($path . '/home.json', $home);
        }
        $folder = $this->createMock(Folder::class);
        $folder->method('getName')->willReturn(basename($path));
        $folder->method('getType')->willReturn(FileInfo::TYPE_FOLDER);
        $folder->method('getPath')->willReturn($path);
        $folder->method('getDirectoryListing')->willReturn(array_values($children));
        $folder->method('nodeExists')->willReturnCallback(fn($n) => isset($children[$n]));
        $folder->method('get')->willReturnCallback(function ($p) use ($children, $path) {
            if (isset($children[$p])) {
                return $children[$p];
            }
            throw new NotFoundException($path . '/' . $p);
        });
        return $folder;
    }

    /**
     * The /IntraVox base folder. Its get($code) returns a child language folder
     * when present, else throws NotFound; newFolder($code) records the code in
     * $this->created and returns a bare folder — this is how the create-on-miss
     * side-effect of getLanguageFolderByCode / getLanguageFolder is observed.
     *
     * @param array<string,Folder> $languages code => language folder
     */
    private function baseFolder(array $languages): Folder {
        $base = $this->createMock(Folder::class);
        $base->method('getName')->willReturn('IntraVox');
        $base->method('getType')->willReturn(FileInfo::TYPE_FOLDER);
        $base->method('getPath')->willReturn('/IntraVox');
        $base->method('getDirectoryListing')->willReturn(array_values($languages));
        $base->method('nodeExists')->willReturnCallback(fn($n) => isset($languages[$n]));
        $base->method('get')->willReturnCallback(function ($code) use ($languages) {
            if (isset($languages[$code])) {
                return $languages[$code];
            }
            throw new NotFoundException('/IntraVox/' . $code);
        });
        $base->method('newFolder')->willReturnCallback(function ($code) {
            $this->created[] = $code;
            return $this->langFolder('/IntraVox/' . $code);
        });
        return $base;
    }

    /**
     * Build a PageService that runs the REAL folder seams. $userLangValue is what
     * config->getUserValue() returns for the profile-language lookup (or null to
     * simulate a logged-out user, which short-circuits getUserLanguage).
     *
     * @param array<string,Folder> $languages code => language folder in /IntraVox
     */
    private function makeService(
        Folder $base,
        ?string $userLangValue = 'en',
        string $primaryLanguage = 'en'
    ): PageService {
        // A subclass that shadows ONLY clearCache (private, like the rest of the
        // suite) and overrides NO folder seam — the whole point is that
        // getIntraVoxFolder & friends run their real bodies here.
        $svc = new class extends PageService {
            public function __construct() {
            }
            public function clearCache(): void {
            }
        };

        // PageService::$userId is a non-nullable string; the constructor stores
        // $userId ?? '' so a logged-out user is the EMPTY STRING, not null. That
        // empty string is what getUserLanguage()/getIntraVoxFolder() test with
        // `if (!$this->userId)`, so it is the faithful logged-out fixture.
        $userId = $userLangValue === null ? '' : 'tester';

        $rootFolder = $this->createMock(IRootFolder::class);
        if ($userId !== '') {
            $userFolder = $this->createMock(Folder::class);
            $userFolder->method('get')->willReturnCallback(function ($p) use ($base) {
                if ($p === 'IntraVox') {
                    return $base;
                }
                throw new NotFoundException('/' . $p);
            });
            $rootFolder->method('getUserFolder')->with($userId)->willReturn($userFolder);
        }

        $config = $this->createMock(IConfig::class);
        // getUserValue(userId, 'core', 'lang', default) — return the raw profile
        // value; when null was requested we still return the default so the body
        // sees "en", but userId is null so getUserLanguage short-circuits first.
        $config->method('getUserValue')->willReturnCallback(
            fn($uid, $app, $key, $default = '') => $userLangValue ?? $default
        );

        $languageService = $this->createMock(LanguageService::class);
        $languageService->method('getPrimaryLanguage')->willReturn($primaryLanguage);

        $explicit = [
            'rootFolder' => $rootFolder,
            'userId' => $userId,
            'config' => $config,
            'languageService' => $languageService,
            'logger' => $this->createMock(\Psr\Log\LoggerInterface::class),
        ];
        $this->injectPageServiceDependencies($svc, $explicit);
        return $svc;
    }

    /** Invoke a private PageService method by name. */
    private function callPrivate(PageService $svc, string $method, array $args = []): mixed {
        $m = new \ReflectionMethod(PageService::class, $method);
        return $m->invokeArgs($svc, $args);
    }

    private function realHome(string $title): array {
        return ['uniqueId' => 'page-home', 'title' => $title];
    }

    private function placeholderHome(): array {
        // A _generated homepage is a placeholder: languageFolderHasRealContent()
        // returns false for it, so it must NOT count as "real content" for #75.
        return ['uniqueId' => 'page-home', 'title' => 'Placeholder', '_generated' => true];
    }

    // ---------------------------------------------------------------- getUserLanguage

    public function testLoggedOutUserGetsDefaultLanguage(): void {
        $svc = $this->makeService($this->baseFolder([]), userLangValue: null);
        $this->assertSame('en', $this->callPrivate($svc, 'getUserLanguage'));
    }

    public function testProfileLanguageBaseCodeIsExtracted(): void {
        $svc = $this->makeService($this->baseFolder([]), userLangValue: 'nl_NL');
        $this->assertSame('nl', $this->callPrivate($svc, 'getUserLanguage'));
    }

    public function testPlainProfileLanguageIsReturned(): void {
        $svc = $this->makeService($this->baseFolder([]), userLangValue: 'de');
        $this->assertSame('de', $this->callPrivate($svc, 'getUserLanguage'));
    }

    public function testThreeLetterLanguageCodeIsAllowed(): void {
        // The guard regex is /^[a-z]{2,3}$/ — a 3-letter code like Filipino passes.
        $svc = $this->makeService($this->baseFolder([]), userLangValue: 'fil');
        $this->assertSame('fil', $this->callPrivate($svc, 'getUserLanguage'));
    }

    public function testMalformedProfileLanguageFallsBackToDefault(): void {
        foreach (['FOO', '1', 'x', 'toolong', 'e2'] as $bad) {
            $svc = $this->makeService($this->baseFolder([]), userLangValue: $bad);
            $this->assertSame(
                'en',
                $this->callPrivate($svc, 'getUserLanguage'),
                "profile value '$bad' should fail the [a-z]{2,3} guard and fall back to en"
            );
        }
    }

    // ------------------------------------------------------ getLanguageFolderByCode (create-on-miss)

    public function testExistingLanguageFolderIsReturnedWithoutCreation(): void {
        $nl = $this->langFolder('/IntraVox/nl');
        $svc = $this->makeService($this->baseFolder(['nl' => $nl]));

        $folder = $this->callPrivate($svc, 'getLanguageFolderByCode', ['nl']);

        $this->assertSame($nl, $folder);
        $this->assertSame([], $this->created, 'an existing folder must never be re-created');
    }

    public function testMissingLanguageFallsBackToExistingDefaultWithoutCreation(): void {
        // 'nl' is missing but 'en' exists → return 'en', create nothing.
        $en = $this->langFolder('/IntraVox/en');
        $svc = $this->makeService($this->baseFolder(['en' => $en]));

        $folder = $this->callPrivate($svc, 'getLanguageFolderByCode', ['nl']);

        $this->assertSame($en, $folder);
        $this->assertSame([], $this->created, 'default already exists — no folder should be created');
    }

    public function testMissingLanguageAndMissingDefaultCreatesTheDefault(): void {
        // Neither 'nl' nor 'en' exist → the body creates the DEFAULT ('en'). This
        // silent side-effect is the whole reason this test exists: a resolver
        // extraction that dropped it would still pass every seam-overriding test.
        $svc = $this->makeService($this->baseFolder([]));

        $this->callPrivate($svc, 'getLanguageFolderByCode', ['nl']);

        $this->assertSame(['en'], $this->created, 'missing lang + missing default must create the default folder');
    }

    public function testMissingDefaultLanguageItselfCreatesIt(): void {
        // Asking for 'en' when it is missing takes the else-branch: create 'en'.
        $svc = $this->makeService($this->baseFolder([]));

        $this->callPrivate($svc, 'getLanguageFolderByCode', ['en']);

        $this->assertSame(['en'], $this->created);
    }

    // ------------------------------------------------------ getLanguageFolder (user-language driven)

    public function testGetLanguageFolderUsesUserLanguageAndCreatesOnMiss(): void {
        // The write-target composition (retired getLanguageFolder, now
        // FolderContext::languageFolder) resolves the USER's language (nl_NL -> nl)
        // and, when neither nl nor the default exist, creates the default.
        $svc = $this->makeService($this->baseFolder([]), userLangValue: 'nl_NL');

        $folders = (new \ReflectionMethod(PageService::class, 'folders'))->invoke($svc);
        $folders->languageFolder();

        $this->assertSame(['en'], $this->created);
    }

    // ------------------------------------------------------ resolveEffectiveLanguage (#75)

    public function testEffectiveLanguageIsUserLanguageWhenItHasRealContent(): void {
        $nl = $this->langFolder('/IntraVox/nl', $this->realHome('Welkom'));
        $svc = $this->makeService($this->baseFolder(['nl' => $nl]), userLangValue: 'nl', primaryLanguage: 'nl');

        $this->assertSame('nl', $this->callPrivate($svc, 'resolveEffectiveLanguage'));
    }

    public function testEffectiveLanguageFallsToPrimaryWhenUserLanguageIsEmpty(): void {
        // User 'nl' has only a placeholder (no real content); primary 'de' has real
        // content → the served language is the recommended/primary one (#75 rule 2).
        $nl = $this->langFolder('/IntraVox/nl', $this->placeholderHome());
        $de = $this->langFolder('/IntraVox/de', $this->realHome('Willkommen'));
        $svc = $this->makeService(
            $this->baseFolder(['nl' => $nl, 'de' => $de]),
            userLangValue: 'nl',
            primaryLanguage: 'de'
        );

        $this->assertSame('de', $this->callPrivate($svc, 'resolveEffectiveLanguage'));
    }

    public function testEffectiveLanguageFallsToEnglishWhenUserAndPrimaryAreEmpty(): void {
        // #75 rule 3: neither the user's language nor the primary has real content,
        // but English does → serve English.
        $nl = $this->langFolder('/IntraVox/nl', $this->placeholderHome());
        $en = $this->langFolder('/IntraVox/en', $this->realHome('Welcome'));
        $svc = $this->makeService(
            $this->baseFolder(['nl' => $nl, 'en' => $en]),
            userLangValue: 'nl',
            primaryLanguage: 'nl'
        );

        $this->assertSame('en', $this->callPrivate($svc, 'resolveEffectiveLanguage'));
    }

    public function testEffectiveLanguageIsNullWhenNothingHasRealContent(): void {
        // #75 rule 4: pure other-language install, nothing can be served → null.
        $nl = $this->langFolder('/IntraVox/nl', $this->placeholderHome());
        $svc = $this->makeService(
            $this->baseFolder(['nl' => $nl]),
            userLangValue: 'nl',
            primaryLanguage: 'nl'
        );

        $this->assertNull($this->callPrivate($svc, 'resolveEffectiveLanguage'));
    }

    public function testEffectiveLanguageSkipsMissingCandidateFolders(): void {
        // primary 'de' folder does not exist on disk at all (get throws NotFound);
        // the loop must `continue` past it and land on English.
        $en = $this->langFolder('/IntraVox/en', $this->realHome('Welcome'));
        $svc = $this->makeService(
            $this->baseFolder(['en' => $en]),
            userLangValue: 'nl',
            primaryLanguage: 'de'
        );

        $this->assertSame('en', $this->callPrivate($svc, 'resolveEffectiveLanguage'));
    }

    // ------------------------- read-folder composition (retired getReadLanguageFolder,
    //                           now FolderContext::readLanguageFolder, same #75 body)

    public function testReadLanguageFolderReturnsTheResolvedLanguageFolder(): void {
        $nl = $this->langFolder('/IntraVox/nl', $this->realHome('Welkom'));
        $svc = $this->makeService(
            $this->baseFolder(['nl' => $nl]),
            userLangValue: 'nl',
            primaryLanguage: 'nl'
        );

        $folders = (new \ReflectionMethod(PageService::class, 'folders'))->invoke($svc);
        $this->assertSame($nl, $folders->readLanguageFolder());
    }

    public function testReadLanguageFolderFallsBackToWriteTargetWhenNothingResolves(): void {
        // effectiveLanguage() returns null (only a placeholder), so the read-folder
        // composition falls back to the write-target — the user's own folder ('nl'),
        // even though it has no real content.
        $nl = $this->langFolder('/IntraVox/nl', $this->placeholderHome());
        $svc = $this->makeService(
            $this->baseFolder(['nl' => $nl]),
            userLangValue: 'nl',
            primaryLanguage: 'nl'
        );

        $folders = (new \ReflectionMethod(PageService::class, 'folders'))->invoke($svc);
        $this->assertSame($nl, $folders->readLanguageFolder(), 'must fall back to the plain write-target folder');
        $this->assertSame([], $this->created, 'nl already exists — fallback must not create anything');
    }
}
