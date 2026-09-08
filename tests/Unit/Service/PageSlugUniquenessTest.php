<?php
declare(strict_types=1);

namespace OCA\IntraVox\Tests\Unit\Service;

use OCA\IntraVox\Service\PageService;
use OCA\IntraVox\Tests\Unit\Service\Harness\BuildsPageService;
use OCP\Files\FileInfo;
use OCP\Files\Folder;
use PHPUnit\Framework\TestCase;

/**
 * Regression tests for the slug de-duplication scope in createPage().
 *
 * A page's slug is a FOLDER NAME: it only has to be unique among the entries of
 * the folder the page is written into. The check that used to guard it asked a
 * different question — does this slug exist anywhere in the ACTING USER'S
 * language tree — and was therefore wrong twice over:
 *
 *   1. Wrong folder. Translating nl/…/niv5 into English checked nl/ (which of
 *      course holds niv5) and wrote en/…/niv5-2, even though en/…/niv5 was free.
 *      A translation lives in a different language tree and must keep its name.
 *   2. Wrong scope. The check recursed the whole language tree, so about/team
 *      reserved the slug "team" for sales/team as well.
 *
 * Following PageServiceCrossLanguageTest, the instance is built without the real
 * (25-arg) constructor and the filesystem seams are overridden.
 */
class PageSlugUniquenessTest extends TestCase {

    use BuildsPageService;

    /** Names newFolder() was called with, per folder path. */
    private array $created = [];

    /**
     * A folder holding $entries (names → child folders, or bare names for files).
     *
     * nodeExists() answers from $entries, which is what the new sibling-scoped
     * check consults; newFolder() records the call so a test can assert both the
     * name a page got AND the folder it landed in.
     */
    private function makeFolder(string $path, array $entries = [], bool $creatable = true): Folder {
        // getInternalPath() is on the OCP\Files\Folder stub (the scanPageFolder()
        // fallback branch calls it); getStorage() joined the stub interface with
        // the PageVersionService tests (PR-13). Both are configured the normal way.
        $folder = $this->createMock(Folder::class);
        // The storage/scanner/cache trio has no OCP stub in this test suite, so
        // it is a hand-rolled no-op: the scan is a cache-warming side effect,
        // irrelevant to where a page lands.
        $folder->method('getStorage')->willReturn(new class {
            public function getScanner() {
                return new class {
                    public function scan($path, $recursive = false) {
                    }
                };
            }
            public function getCache() {
                return new class {
                    public function correctFolderSize($path, $data = null) {
                    }
                };
            }
        });
        $folder->method('getInternalPath')->willReturn(ltrim($path, '/'));
        $folder->method('getName')->willReturn(basename($path));
        $folder->method('getType')->willReturn(FileInfo::TYPE_FOLDER);
        $folder->method('getPath')->willReturn($path);
        $folder->method('isCreatable')->willReturn($creatable);
        // Only the node entries are listable; bare string entries stand for
        // files whose content no test reads (they exist to occupy a name).
        $folder->method('getDirectoryListing')->willReturn(
            array_values(array_filter(
                $entries,
                fn($e) => $e instanceof \OCP\Files\Node
            ))
        );

        $folder->method('nodeExists')->willReturnCallback(
            fn($name) => in_array($name, $entries, true) || isset($entries[$name])
        );

        $folder->method('get')->willReturnCallback(function ($name) use ($entries, $path) {
            if (isset($entries[$name]) && $entries[$name] instanceof \OCP\Files\Node) {
                return $entries[$name];
            }
            throw new \OCP\Files\NotFoundException($path . '/' . $name);
        });

        $self = $this;
        $folder->method('newFolder')->willReturnCallback(
            function ($name) use ($self, $path) {
                $self->created[$path][] = $name;
                return $self->makeFolder($path . '/' . $name);
            }
        );
        $folder->method('newFile')->willReturnCallback(
            function ($name) use ($self, $path) {
                $file = $self->createMock(\OCP\Files\File::class);
                $file->method('getName')->willReturn($name);
                $file->method('getPath')->willReturn($path . '/' . $name);
                $file->method('getId')->willReturn(abs(crc32($path . '/' . $name)));
                return $file;
            }
        );

        return $folder;
    }

    /** Names newFolder() recorded for $path. */
    private function createdIn(string $path): array {
        return $this->created[$path] ?? [];
    }

    /**
     * @param array $languages language code → folder, mounted under /IntraVox
     * @param string $writeLang the acting user's own display language
     * @param string|null $readLang language getReadLanguageFolder() resolves to
     */
    private function makeService(array $languages, string $writeLang, ?string $readLang = null): PageService {
        $base = $this->createMock(Folder::class);
        $base->method('getPath')->willReturn('/IntraVox');
        $base->method('getDirectoryListing')->willReturn(array_values($languages));
        $base->method('get')->willReturnCallback(function ($p) use ($languages) {
            if (isset($languages[$p])) {
                return $languages[$p];
            }
            throw new \OCP\Files\NotFoundException($p);
        });

        $writeFolder = $languages[$writeLang];
        $readFolder = $languages[$readLang ?? $writeLang];

        // createPage resolves its write-target ($writeFolder) via
        // folders()->languageFolder(), the no-parent fallback ($readFolder) via
        // readLanguageFolder(), and the language-code/getOrCreateFolderPath branch
        // via intraVox() ($base) — all from the injected FolderContext below.
        $svc = new class extends PageService {
            public function __construct() {
            }
            public function clearCache(): void {
            }
        };

        $user = $this->createMock(\OCP\IUser::class);
        $user->method('getUID')->willReturn('tester');
        $user->method('getDisplayName')->willReturn('Tester');
        $session = $this->createMock(\OCP\IUserSession::class);
        $session->method('getUser')->willReturn($user);

        $config = $this->createMock(\OCP\IConfig::class);
        $config->method('getUserValue')->willReturn($writeLang);

        $languageService = $this->createMock(\OCA\IntraVox\Service\LanguageService::class);
        $languageService->method('isLanguageAvailable')->willReturnCallback(
            fn(string $code) => in_array($code, ['en', 'de', 'fr', 'nl'], true)
        );
        $languageService->method('getPrimaryLanguage')->willReturn('en');

        $explicit = [
            'userSession' => $session,
            'userId' => 'tester',
            'config' => $config,
            'logger' => $this->createMock(\Psr\Log\LoggerInterface::class),
            'languageService' => $languageService,
            'folderContext' => $this->fakeFolderContext(
                readLanguageFolder: $readFolder,
                intraVox: $base,
                languageFolder: $writeFolder,
                userLanguage: $writeLang
            ),
        ];
        $this->injectPageServiceDependencies($svc, $explicit);

        return $svc;
    }

    private function pageData(string $id, string $title): array {
        return ['id' => $id, 'title' => $title, 'layout' => ['columns' => 1, 'rows' => []]];
    }

    /**
     * The reported bug: translating into another language must keep the name.
     *
     * nl/ holds niv5 and the acting user's language is nl, but the page is
     * written into en/ — where the name is free.
     */
    public function testTranslationKeepsSourceSlugInOtherLanguage(): void {
        $nl = $this->makeFolder('/IntraVox/nl', ['niv5' => $this->makeFolder('/IntraVox/nl/niv5')]);
        $en = $this->makeFolder('/IntraVox/en');
        $svc = $this->makeService(['nl' => $nl, 'en' => $en], 'nl');

        $created = $svc->createPage($this->pageData('niv5', 'niv5'), 'en');

        $this->assertSame('niv5', $created['id'], 'a translation must keep the source name');
        $this->assertContains('niv5', $this->createdIn('/IntraVox/en'));
    }

    /** A real sibling collision still gets a suffix. */
    public function testSiblingCollisionStillGetsSuffix(): void {
        $sales = $this->makeFolder('/IntraVox/nl/sales', ['team' => $this->makeFolder('/IntraVox/nl/sales/team')]);
        $nl = $this->makeFolder('/IntraVox/nl', ['sales' => $sales]);
        $svc = $this->makeService(['nl' => $nl], 'nl');

        $created = $svc->createPage($this->pageData('team', 'Team'), 'nl/sales');

        $this->assertSame('team-2', $created['id']);
    }

    /** The scope defect: the same name under a different parent is fine. */
    public function testSameSlugUnderDifferentParentsIsAllowed(): void {
        $about = $this->makeFolder('/IntraVox/nl/about', ['team' => $this->makeFolder('/IntraVox/nl/about/team')]);
        $sales = $this->makeFolder('/IntraVox/nl/sales');
        $nl = $this->makeFolder('/IntraVox/nl', ['about' => $about, 'sales' => $sales]);
        $svc = $this->makeService(['nl' => $nl], 'nl');

        $created = $svc->createPage($this->pageData('team', 'Team'), 'nl/sales');

        $this->assertSame('team', $created['id'], 'a sibling elsewhere must not reserve the name');
        $this->assertContains('team', $this->createdIn('/IntraVox/nl/sales'));
    }

    /**
     * In the legacy "beside" layout a page's JSON sits next to its folder, so
     * the slug is taken even when no folder of that name exists.
     */
    public function testBesideLayoutJsonBlocksSlug(): void {
        $sales = $this->makeFolder('/IntraVox/nl/sales', ['team.json']);
        $nl = $this->makeFolder('/IntraVox/nl', ['sales' => $sales]);
        $svc = $this->makeService(['nl' => $nl], 'nl');

        $created = $svc->createPage($this->pageData('team', 'Team'), 'nl/sales');

        $this->assertSame('team-2', $created['id']);
    }

    /**
     * 'home' is written as home.json at the language root; a 'home-2' would only
     * create a page folder the homepage resolver never looks at.
     */
    public function testHomeIsNeverSuffixed(): void {
        $nl = $this->makeFolder('/IntraVox/nl', ['home.json']);
        $svc = $this->makeService(['nl' => $nl], 'nl');

        $created = $svc->createPage($this->pageData('home', 'Home'), 'nl');

        $this->assertSame('home', $created['id']);
        // home.json is written at the language root; the only folder the home
        // branch creates is its _media sibling, never a 'home'/'home-2' page folder.
        $this->assertSame(['_media'], $this->createdIn('/IntraVox/nl'));
    }

    /**
     * A destination that does not exist yet cannot hold a collision — and the
     * resolver must not create it on the way to finding that out.
     */
    public function testMissingParentFolderSkipsDedup(): void {
        $en = $this->makeFolder('/IntraVox/en');
        $nl = $this->makeFolder('/IntraVox/nl', ['news' => $this->makeFolder('/IntraVox/nl/news')]);
        $svc = $this->makeService(['nl' => $nl, 'en' => $en], 'nl');

        $created = $svc->createPage($this->pageData('news', 'News'), 'en/does/not/exist');

        $this->assertSame('news', $created['id']);
        $this->assertSame(
            ['does'],
            $this->createdIn('/IntraVox/en'),
            'only the write path may create folders, and only once'
        );
    }

    /**
     * With no parent path, createPageAtPath() falls back to the READ language
     * folder — so the collision check has to look there too, not in the user's
     * own language folder.
     */
    public function testNullParentPathUsesReadLanguageFolder(): void {
        $nl = $this->makeFolder('/IntraVox/nl', ['news' => $this->makeFolder('/IntraVox/nl/news')]);
        $en = $this->makeFolder('/IntraVox/en');
        $svc = $this->makeService(['nl' => $nl, 'en' => $en], 'nl', 'en');

        $created = $svc->createPage($this->pageData('news', 'News'), null);

        $this->assertSame('news', $created['id']);
        $this->assertContains('news', $this->createdIn('/IntraVox/en'));
    }

    /**
     * The #70 create preflight: a read-only target folder must yield a clean 403
     * BEFORE any write, not a filesystem-level error mid-create. A read-only
     * GroupFolder member trying to create a page here gets ForbiddenException, and
     * nothing is written to disk (no newFolder/newFile ran).
     */
    public function testReadOnlyTargetFolderYields403BeforeAnyWrite(): void {
        // The read-language folder (where a null-parentPath create lands) is not
        // creatable — the acting user is a read-only member.
        $readOnly = $this->makeFolder('/IntraVox/nl', [], creatable: false);
        $svc = $this->makeService(['nl' => $readOnly], 'nl');

        try {
            $svc->createPage($this->pageData('news', 'News'), null);
            $this->fail('a read-only target must throw ForbiddenException');
        } catch (\OCA\IntraVox\Exception\ForbiddenException $e) {
            $this->assertStringContainsString('permission to create', $e->getMessage());
        }

        // The preflight fires before any folder/file is written.
        $this->assertSame(
            [],
            $this->createdIn('/IntraVox/nl'),
            'a denied create must not touch the filesystem'
        );
    }

    /**
     * A copy is a new page, not a translation of its source: inheriting the
     * group made it a same-language member of that group — the very state
     * createTranslation() refuses to create, because it makes the language
     * switcher ambiguous.
     *
     * copyPage() ends in getPage(), which needs a far deeper fixture than the
     * naming behaviour under test here, so this asserts the contract at the
     * seam: createPage() honours a group it is GIVEN (which is why the caller
     * has to unset it), and mints a fresh one when there is none.
     */
    public function testCreatePageMintsGroupOnlyWhenAbsent(): void {
        $sourceGroup = 'tg-11111111-2222-3333-4444-555555555555';
        $nl = $this->makeFolder('/IntraVox/nl');
        $svc = $this->makeService(['nl' => $nl], 'nl');

        $kept = $svc->createPage([
            'id' => 'kept',
            'title' => 'Kept',
            'translationGroup' => $sourceGroup,
            'layout' => ['columns' => 1, 'rows' => []],
        ], 'nl');
        $this->assertSame(
            $sourceGroup,
            $kept['translationGroup'],
            'a supplied group is kept — so copyPage must unset it to avoid inheriting'
        );

        $minted = $svc->createPage($this->pageData('minted', 'Minted'), 'nl');
        $this->assertMatchesRegularExpression('/^tg-[a-f0-9-]{36}$/', $minted['translationGroup']);
        $this->assertNotSame($sourceGroup, $minted['translationGroup']);
    }

    /**
     * The other half of that contract: copyPage() strips the group before
     * delegating, so a copy can never inherit its source's language links.
     *
     * createPage() is captured rather than executed — what matters is the data
     * copyPage() hands it, and stopping there keeps the fixture to the source
     * page itself.
     */
    public function testCopyPageStripsTranslationGroupBeforeCreating(): void {
        $sourceGroup = 'tg-11111111-2222-3333-4444-555555555555';
        $sourceJson = [
            'uniqueId' => 'page-aaaaaaaa-bbbb-cccc-dddd-eeeeeeeeeeee',
            'title' => 'Handbook',
            'translationGroup' => $sourceGroup,
            'order' => 3,
            'layout' => ['columns' => 1, 'rows' => []],
        ];

        $file = $this->createMock(\OCP\Files\File::class);
        $file->method('getName')->willReturn('handbook.json');
        $file->method('getType')->willReturn(FileInfo::TYPE_FILE);
        $file->method('getContent')->willReturn(json_encode($sourceJson));
        $file->method('getId')->willReturn(4242);

        $pageFolder = $this->makeFolder('/IntraVox/nl/handbook', ['handbook.json' => $file]);
        $nl = $this->makeFolder('/IntraVox/nl', ['handbook' => $pageFolder]);
        $base = $this->makeFolder('/IntraVox', ['nl' => $nl]);

        // Drive PageCompositionService directly with a stub createPage closure that
        // captures the data copyPage hands over (what this test targets) — no
        // PageService subclass, no reflection property-copy.
        $svc = new \OCA\IntraVox\Service\Compose\PageCompositionService(
            $this->createMock(\OCA\IntraVox\Service\Template\PageTemplateService::class),
            $this->createMock(\OCA\IntraVox\Service\Translation\TranslationGroupService::class),
            $this->createMock(\OCA\IntraVox\Service\Media\PageMediaService::class),
            $this->doubleOrBuild(\OCA\IntraVox\Service\Sanitize\HtmlSanitizer::class),
            new \OCA\IntraVox\Service\Util\PageIdUtils(),
            $this->fakeFolderContext(intraVox: $base, languageFolder: $nl),
            'tester',
            $this->createMock(\Psr\Log\LoggerInterface::class)
        );
        $locator = new \OCA\IntraVox\Service\Locator\PageLocator(
            $this->createMock(\OCA\IntraVox\Service\PageIndexService::class),
            $this->createMock(\Psr\Log\LoggerInterface::class)
        );

        $seen = null;
        $svc->copyPage(
            'page-aaaaaaaa-bbbb-cccc-dddd-eeeeeeeeeeee',
            null,
            null,
            function (array $data, ?string $parentPath = null) use (&$seen): array {
                $seen = $data;
                return $data + ['translationGroup' => 'tg-fresh'];
            },
            fn(string $id): array => $seen ?? [],
            fn(Folder $folder, string $uid): ?array => $locator->locatePageAnyLanguage(fn() => $base, $folder, $uid),
            fn(string $id): ?Folder => null,
            function (): void {
            }
        );

        $this->assertNotNull($seen, 'copyPage should have reached createPage()');
        $this->assertArrayNotHasKey(
            'translationGroup',
            $seen,
            'a copy must not inherit the source translationGroup'
        );
        $this->assertArrayNotHasKey('order', $seen, 'a copy must not inherit sibling order');
    }


}
