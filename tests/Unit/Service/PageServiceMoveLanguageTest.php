<?php
declare(strict_types=1);

namespace OCA\IntraVox\Tests\Unit\Service;

use OCA\IntraVox\Exception\CrossLanguageMoveException;
use OCA\IntraVox\Exception\ForbiddenException;
use OCA\IntraVox\Service\PageService;
use OCA\IntraVox\Tests\Unit\Service\Harness\BuildsPageService;
use OCP\Files\File;
use OCP\Files\FileInfo;
use OCP\Files\Folder;
use PHPUnit\Framework\TestCase;

/**
 * movePage() must never relocate a page between language folders.
 *
 * Once pages resolve across language folders (#90, #92), movePage() can FIND a
 * page outside the user's own language. The destination therefore has to be
 * constrained explicitly: the root branch used to target the USER's language
 * folder, so a move-to-root would have physically relocated the page and its
 * whole subtree into another language — silently, with no undo, and in a loop
 * from BulkOperationService.
 *
 * Language folders are independent content trees (nothing links a page in en/ to
 * one in de/; uniqueIds are unique only per language), so such a move is a
 * relocation between intranets, not a translation. It is refused.
 *
 * These tests also pin the permission preflight movePage() previously lacked
 * entirely — it called move() and left the filesystem to throw.
 */
class PageServiceMoveLanguageTest extends TestCase {

    use BuildsPageService;

    /** Records every move() performed: [sourcePath => destinationPath]. */
    private array $moves = [];

    /** Index subtree operations triggered by the service under test. */
    private array $indexRepaths = [];
    private array $indexRemovals = [];

    protected function setUp(): void {
        $this->moves = [];
        $this->indexRepaths = [];
        $this->indexRemovals = [];
    }

    private function makeFile(string $path, array $json): File {
        $file = $this->createMock(File::class);
        $file->method('getName')->willReturn(basename($path));
        $file->method('getType')->willReturn(FileInfo::TYPE_FILE);
        $file->method('getPath')->willReturn($path);
        $file->method('getContent')->willReturn(json_encode($json));
        $file->method('getId')->willReturn(abs(crc32($path)));
        return $file;
    }

    /**
     * A folder that records move() and can be made read-only / non-creatable so
     * the permission preflight is testable.
     *
     * @param array $children name => node
     */
    private function makeFolder(
        string $path,
        array $children = [],
        bool $deletable = true,
        bool $creatable = true
    ): Folder {
        $folder = $this->createMock(Folder::class);
        $folder->method('getName')->willReturn(basename($path));
        $folder->method('getType')->willReturn(FileInfo::TYPE_FOLDER);
        $folder->method('getPath')->willReturn($path);
        $folder->method('getDirectoryListing')->willReturn(array_values($children));
        $folder->method('isDeletable')->willReturn($deletable);
        $folder->method('isCreatable')->willReturn($creatable);
        $folder->method('isUpdateable')->willReturn(true);
        $folder->method('nodeExists')->willReturnCallback(
            fn($n) => isset($children[$n])
        );
        $folder->method('get')->willReturnCallback(function ($p) use ($children, $path) {
            if (isset($children[$p])) {
                return $children[$p];
            }
            throw new \OCP\Files\NotFoundException($path . '/' . $p);
        });
        $folder->method('move')->willReturnCallback(function ($dest) use ($path) {
            $this->moves[$path] = $dest;
        });
        return $folder;
    }

    /**
     * @param Folder $userLanguageFolder the folder for the user's PROFILE language
     * @param Folder[] $allLanguages every language folder under /IntraVox
     */
    private function makeService(Folder $userLanguageFolder, array $allLanguages): PageService {
        $byLang = [];
        foreach ($allLanguages as $l) {
            $byLang[$l->getName()] = $l;
        }
        $base = $this->createMock(Folder::class);
        $base->method('getPath')->willReturn('/IntraVox');
        $base->method('getDirectoryListing')->willReturn($allLanguages);
        $base->method('get')->willReturnCallback(function ($p) use ($byLang) {
            if (isset($byLang[$p])) {
                return $byLang[$p];
            }
            throw new \OCP\Files\NotFoundException($p);
        });

        // movePage + getCurrentPageContent/updateVersionLabel resolve their folders
        // through FolderContext: languageFolder + readLanguageFolder are the user's
        // language folder, and languageOfFolder/relativePathFromRoot use the
        // intraVox() root ($base). getIntraVoxFolder stays for the cross-language
        // locate walks (locatePageAnyLanguage/BySlug -> rootClosure()).
        $svc = new class($base) extends PageService {
            private Folder $baseFolder;
            public function __construct(Folder $baseFolder) {
                $this->baseFolder = $baseFolder;
            }
            protected function getIntraVoxFolder() {
                return $this->baseFolder;
            }
            public function clearCache(): void {
            }
            // isHomepage() reads appconfig via collaborators irrelevant here;
            // no fixture page is the configured homepage.
            public function isHomepage(string $uniqueId, ?string $language = null): bool {
                return false;
            }
        };

        $user = $this->createMock(\OCP\IUser::class);
        $user->method('getUID')->willReturn('tester');
        $session = $this->createMock(\OCP\IUserSession::class);
        $session->method('getUser')->willReturn($user);

        $config = $this->createMock(\OCP\IConfig::class);
        $config->method('getUserValue')->willReturn('de');

        $languageService = $this->createMock(\OCA\IntraVox\Service\LanguageService::class);
        $languageService->method('isLanguageAvailable')->willReturnCallback(
            fn(string $code) => in_array($code, ['en', 'de', 'fr', 'nl'], true)
        );
        $languageService->method('getPrimaryLanguage')->willReturn('en');
        $languageService->method('getAvailableLanguages')->willReturn([
            ['code' => 'en', 'name' => 'English'],
            ['code' => 'de', 'name' => 'Deutsch'],
        ]);

        // Record what the index is told, so a move can be checked for keeping
        // the indexed paths of the whole subtree in step.
        $index = $this->createMock(\OCA\IntraVox\Service\PageIndexService::class);
        $index->method('repathSubtree')->willReturnCallback(
            function (string $old, string $new): int {
                $this->indexRepaths[] = ['from' => $old, 'to' => $new];
                return 1;
            }
        );
        $index->method('removeSubtree')->willReturnCallback(
            function (string $prefix): int {
                $this->indexRemovals[] = $prefix;
                return 1;
            }
        );

        $explicit = [
            'userSession' => $session,
            'userId' => 'tester',
            'config' => $config,
            'logger' => $this->createMock(\Psr\Log\LoggerInterface::class),
            'languageService' => $languageService,
            'pageIndexService' => $index,
            'folderContext' => $this->fakeFolderContext(
                readLanguageFolder: $userLanguageFolder,
                intraVox: $base,
                userLanguage: 'de',
                primaryLanguage: 'en',
                languageFolder: $userLanguageFolder
            ),
        ];
        $this->injectPageServiceDependencies($svc, $explicit);

        return $svc;
    }

    /**
     * Fixture: an English page `about` nested under `news`, a German user.
     * Returns the service; the en/ and de/ roots are addressable via $this.
     */
    private function twoLanguageFixture(
        bool $sourceDeletable = true,
        bool $deRootCreatable = true
    ): PageService {
        $pageJson = $this->makeFile(
            '/IntraVox/en/news/about.json',
            ['uniqueId' => 'page-move1', 'title' => 'About', 'widgets' => []]
        );
        $pageFolder = $this->makeFolder('/IntraVox/en/news/about', [], $sourceDeletable);
        $newsFolder = $this->makeFolder('/IntraVox/en/news', [
            'about.json' => $pageJson,
            'about' => $pageFolder,
        ]);
        $en = $this->makeFolder('/IntraVox/en', ['news' => $newsFolder]);
        $de = $this->makeFolder('/IntraVox/de', [], true, $deRootCreatable);

        // The user's PROFILE language is de/ — the page lives in en/.
        return $this->makeService($de, [$de, $en]);
    }

    /**
     * THE regression: a move to root must not relocate the page into the
     * user's own language folder. Before the fix the page (and its subtree)
     * moved from /IntraVox/en/news/about to /IntraVox/de/about.
     */
    public function testMoveToRootStaysInThePagesOwnLanguage(): void {
        $svc = $this->twoLanguageFixture();

        $svc->movePage('page-move1', '');

        $this->assertArrayHasKey(
            '/IntraVox/en/news/about',
            $this->moves,
            'the page should have been moved'
        );
        $this->assertSame(
            '/IntraVox/en/about',
            $this->moves['/IntraVox/en/news/about'],
            'a move to root must land in the page\'s OWN language root, never the user\'s'
        );
    }

    /**
     * The language guard: an explicit target parent in another language is
     * refused outright, with both language NAMES in the message so the user
     * learns why (a bare "Page not found" is what made #90 so hard to report).
     */
    public function testMoveIntoAnotherLanguageIsRefused(): void {
        $targetJson = $this->makeFile(
            '/IntraVox/de/ziele.json',
            ['uniqueId' => 'page-detarget', 'title' => 'Ziele', 'widgets' => []]
        );
        $targetFolder = $this->makeFolder('/IntraVox/de/ziele', []);
        $de = $this->makeFolder('/IntraVox/de', [
            'ziele.json' => $targetJson,
            'ziele' => $targetFolder,
        ]);

        $sourceJson = $this->makeFile(
            '/IntraVox/en/about.json',
            ['uniqueId' => 'page-move2', 'title' => 'About', 'widgets' => []]
        );
        $sourceFolder = $this->makeFolder('/IntraVox/en/about', []);
        $en = $this->makeFolder('/IntraVox/en', [
            'about.json' => $sourceJson,
            'about' => $sourceFolder,
        ]);

        $svc = $this->makeService($de, [$de, $en]);

        try {
            $svc->movePage('page-move2', 'page-detarget');
            $this->fail('a cross-language move must be refused');
        } catch (CrossLanguageMoveException $e) {
            $this->assertStringContainsString('English', $e->getMessage());
            $this->assertStringContainsString('Deutsch', $e->getMessage());
        }

        $this->assertSame([], $this->moves, 'nothing may be moved when the guard fires');
    }

    /** A move within one language still works normally. */
    public function testMoveWithinTheSameLanguageStillWorks(): void {
        $sourceJson = $this->makeFile(
            '/IntraVox/en/about.json',
            ['uniqueId' => 'page-move3', 'title' => 'About', 'widgets' => []]
        );
        $sourceFolder = $this->makeFolder('/IntraVox/en/about', []);
        $targetJson = $this->makeFile(
            '/IntraVox/en/news.json',
            ['uniqueId' => 'page-entarget', 'title' => 'News', 'widgets' => []]
        );
        $targetFolder = $this->makeFolder('/IntraVox/en/news', []);
        $en = $this->makeFolder('/IntraVox/en', [
            'about.json' => $sourceJson,
            'about' => $sourceFolder,
            'news.json' => $targetJson,
            'news' => $targetFolder,
        ]);
        $de = $this->makeFolder('/IntraVox/de', []);

        $svc = $this->makeService($de, [$de, $en]);
        $svc->movePage('page-move3', 'page-entarget');

        $this->assertSame(
            '/IntraVox/en/news/about',
            $this->moves['/IntraVox/en/about'] ?? null,
            'a same-language move must still relocate the page'
        );
    }

    /**
     * Permission preflight: movePage() had none, so a read-only source relied on
     * the filesystem throwing — an opaque 500 with unguarded partial state.
     */
    public function testMoveIsRefusedWhenSourceIsNotDeletable(): void {
        $svc = $this->twoLanguageFixture(false);

        $this->expectException(ForbiddenException::class);
        $svc->movePage('page-move1', '');
    }

    /** The destination side of the same preflight. */
    public function testMoveIsRefusedWhenDestinationIsNotCreatable(): void {
        // Source and destination in the SAME language, so the language guard
        // cannot be what rejects this — the permission check must be.
        $sourceJson = $this->makeFile(
            '/IntraVox/en/news/about.json',
            ['uniqueId' => 'page-move4', 'title' => 'About', 'widgets' => []]
        );
        $sourceFolder = $this->makeFolder('/IntraVox/en/news/about', []);
        $newsFolder = $this->makeFolder('/IntraVox/en/news', [
            'about.json' => $sourceJson,
            'about' => $sourceFolder,
        ]);
        // The en/ root is not creatable.
        $en = $this->makeFolder('/IntraVox/en', ['news' => $newsFolder], true, false);
        $de = $this->makeFolder('/IntraVox/de', []);

        $svc = $this->makeService($de, [$de, $en]);

        $this->expectException(ForbiddenException::class);
        $svc->movePage('page-move4', '');
    }

    /** A genuinely missing page still reports not-found, not a language error. */
    public function testUnknownPageStillReportsNotFound(): void {
        $en = $this->makeFolder('/IntraVox/en', []);
        $de = $this->makeFolder('/IntraVox/de', []);
        $svc = $this->makeService($de, [$de, $en]);

        $this->expectException(\OCA\IntraVox\Exception\PageNotFoundException::class);
        $svc->movePage('page-nope', '');
    }

    /**
     * A move relocates the page AND everything nested under it, so the index
     * must repoint the whole subtree — not just the page that was dragged.
     * Without this, every descendant keeps an indexed path to a folder that no
     * longer exists, and the index silently rots with each move.
     */
    public function testMoveRepathsTheIndexedSubtree(): void {
        $svc = $this->twoLanguageFixture();

        $svc->movePage('page-move1', '');

        $this->assertCount(1, $this->indexRepaths, 'a move must repath the index once');
        $this->assertSame(
            ['from' => '/IntraVox/en/news/about', 'to' => '/IntraVox/en/about'],
            $this->indexRepaths[0],
            'the index must follow the page from its old path to its new one'
        );
    }

    /** A refused cross-language move must not touch the index either. */
    public function testRefusedMoveLeavesTheIndexAlone(): void {
        $targetJson = $this->makeFile(
            '/IntraVox/de/ziele.json',
            ['uniqueId' => 'page-detarget2', 'title' => 'Ziele', 'widgets' => []]
        );
        $de = $this->makeFolder('/IntraVox/de', [
            'ziele.json' => $targetJson,
            'ziele' => $this->makeFolder('/IntraVox/de/ziele', []),
        ]);
        $sourceJson = $this->makeFile(
            '/IntraVox/en/about.json',
            ['uniqueId' => 'page-move5', 'title' => 'About', 'widgets' => []]
        );
        $en = $this->makeFolder('/IntraVox/en', [
            'about.json' => $sourceJson,
            'about' => $this->makeFolder('/IntraVox/en/about', []),
        ]);

        $svc = $this->makeService($de, [$de, $en]);

        try {
            $svc->movePage('page-move5', 'page-detarget2');
            $this->fail('a cross-language move must be refused');
        } catch (CrossLanguageMoveException $e) {
            // expected
        }

        $this->assertSame([], $this->indexRepaths, 'a refused move must not repath anything');
    }

    /**
     * getCurrentPageContent() had no uniqueId branch at all — it passed a
     * page-… id straight to findPageById() — and no cross-language fallback.
     * The "compare with current" panel in version history therefore failed on
     * every modern id, and on any foreign-language page.
     */
    public function testCurrentPageContentResolvesForeignLanguageByUniqueId(): void {
        $pageJson = $this->makeFile(
            '/IntraVox/en/about.json',
            ['uniqueId' => 'page-cur1', 'title' => 'About', 'name' => 'About', 'widgets' => []]
        );
        $en = $this->makeFolder('/IntraVox/en', [
            'about.json' => $pageJson,
            'about' => $this->makeFolder('/IntraVox/en/about', []),
        ]);
        $de = $this->makeFolder('/IntraVox/de', []);

        $svc = $this->makeService($de, [$de, $en]);
        $content = $svc->getCurrentPageContent('page-cur1');

        $this->assertStringContainsString(
            'page-cur1',
            $content['rawContent'],
            'the current content of a foreign-language page must be readable'
        );
    }

    /** The same lookup gap in updateVersionLabel's existence check. */
    public function testUpdateVersionLabelFindsForeignLanguagePage(): void {
        $pageJson = $this->makeFile(
            '/IntraVox/en/about.json',
            ['uniqueId' => 'page-lbl1', 'title' => 'About', 'widgets' => []]
        );
        $en = $this->makeFolder('/IntraVox/en', [
            'about.json' => $pageJson,
            'about' => $this->makeFolder('/IntraVox/en/about', []),
        ]);
        $de = $this->makeFolder('/IntraVox/de', []);

        $svc = $this->makeService($de, [$de, $en]);

        // The page must be FOUND: resolution is what was broken. It then fails
        // later on the version manager, which this fixture does not provide —
        // so anything other than "Page not found" proves the lookup succeeded.
        try {
            $svc->updateVersionLabel('page-lbl1', 12345, 'Release');
            $this->addToAssertionCount(1);
        } catch (\Throwable $e) {
            $this->assertStringNotContainsString(
                'Page not found',
                $e->getMessage(),
                'the page must resolve across languages; only later steps may fail'
            );
        }
    }


}
