<?php
declare(strict_types=1);

namespace OCA\IntraVox\Tests\Unit\Service;

use OCA\IntraVox\Service\HomepageService;
use OCA\IntraVox\Service\PageService;
use OCA\IntraVox\Tests\Unit\Service\Harness\BuildsPageService;
use OCP\Files\File;
use OCP\Files\FileInfo;
use OCP\Files\Folder;
use PHPUnit\Framework\TestCase;

/**
 * getHomepageUniqueId() must name a page the frontend can actually find.
 *
 * Two homepage layouts exist side by side: the normalised `home/home.json`
 * folder page, which has a real uniqueId, and the legacy loose `home.json` in
 * the language root. For the legacy form this returned the bare string 'home',
 * which matches no entry in listPages() — so
 * `pages.find(p => p.uniqueId === homepageUniqueId)` came up empty and the
 * reader fell through to a slug/path heuristic that ends at pages[0], the
 * alphabetically first page.
 *
 * On dev that put every Dutch reader on "API Referentie" instead of "Welkom bij
 * IntraVox", while English — which uses the normalised layout — was fine. The
 * two layouts coexisting is exactly what hid it.
 */
class PageHomepageResolutionTest extends TestCase {

    use BuildsPageService;

    private function makeFile(string $path, array $json): File {
        $file = $this->createMock(File::class);
        $file->method('getName')->willReturn(basename($path));
        $file->method('getType')->willReturn(FileInfo::TYPE_FILE);
        $file->method('getPath')->willReturn($path);
        $file->method('getContent')->willReturn(json_encode($json));
        $file->method('getId')->willReturn(abs(crc32($path)));
        return $file;
    }

    private function makeFolder(string $path, array $children): Folder {
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
            throw new \OCP\Files\NotFoundException($path . '/' . $p);
        });
        return $folder;
    }

    /**
     * @param array|null $homeJson contents of nl/home.json, or null for none
     * @param string|null $pointer configured homepage pointer, if any
     */
    private function makeService(?array $homeJson, ?string $pointer = null): PageService {
        $children = [
            'about.json' => $this->makeFile(
                '/IntraVox/nl/about.json',
                ['uniqueId' => 'page-about', 'title' => 'API Referentie']
            ),
        ];
        if ($homeJson !== null) {
            $children['home.json'] = $this->makeFile('/IntraVox/nl/home.json', $homeJson);
        }
        $nl = $this->makeFolder('/IntraVox/nl', $children);
        $base = $this->makeFolder('/IntraVox', ['nl' => $nl]);

        // getHomepageUniqueId resolves its folder purely through the injected
        // FolderContext (languageFolderByCode('nl') + the #75 effective-language
        // probe over the base folder), so a fakeFolderContext replaces the old
        // triple-seam override. userLanguage/primaryLanguage 'nl' mirror the config
        // + languageService this fixture wired; the real-content probe reads the
        // same nl/home.json.
        $svc = new class extends PageService {
            public function __construct() {
            }
            public function clearCache(): void {
            }
        };

        $homepageService = $this->createMock(HomepageService::class);
        $homepageService->method('getHomepageUniqueId')->willReturn($pointer);

        $config = $this->createMock(\OCP\IConfig::class);
        $config->method('getUserValue')->willReturn('nl');

        $languageService = $this->createMock(\OCA\IntraVox\Service\LanguageService::class);
        $languageService->method('isLanguageAvailable')->willReturn(true);
        $languageService->method('getPrimaryLanguage')->willReturn('nl');

        $explicit = [
            'userSession' => $this->createMock(\OCP\IUserSession::class),
            'userId' => 'tester',
            'config' => $config,
            'logger' => $this->createMock(\Psr\Log\LoggerInterface::class),
            'languageService' => $languageService,
            'homepageService' => $homepageService,
            'folderContext' => $this->fakeFolderContext(
                intraVox: $base,
                userLanguage: 'nl',
                primaryLanguage: 'nl'
            ),
        ];
        $this->injectPageServiceDependencies($svc, $explicit);
        return $svc;
    }

    /**
     * The regression: a legacy loose home.json must resolve to the uniqueId the
     * file really carries, so the frontend can match it against listPages().
     */
    public function testLooseHomeJsonResolvesToItsRealUniqueId(): void {
        $svc = $this->makeService([
            'uniqueId' => 'page-nl-home',
            'title' => 'Welkom bij IntraVox',
        ]);

        $this->assertSame('page-nl-home', $svc->getHomepageUniqueId('nl'));
    }

    /**
     * A home.json without a uniqueId keeps the legacy answer — that string is
     * what the rest of the legacy path still understands, and inventing an id
     * here would be worse than saying "the legacy default".
     */
    public function testHomeJsonWithoutUniqueIdKeepsTheLegacyAnswer(): void {
        $svc = $this->makeService(['title' => 'Welcome']);

        $this->assertSame('home', $svc->getHomepageUniqueId('nl'));
    }

    /** No loose home.json at all: unchanged legacy answer. */
    public function testMissingHomeJsonKeepsTheLegacyAnswer(): void {
        $svc = $this->makeService(null);

        $this->assertSame('home', $svc->getHomepageUniqueId('nl'));
    }

    /**
     * A configured pointer still wins over the loose file — this fix must not
     * quietly override an admin's explicit homepage choice.
     */
    public function testConfiguredPointerStillWins(): void {
        $svc = $this->makeService(
            ['uniqueId' => 'page-nl-home', 'title' => 'Welkom'],
            'page-about'
        );

        $this->assertSame('page-about', $svc->getHomepageUniqueId('nl'));
    }

    /**
     * A stale pointer (naming a page that no longer resolves) must NOT be
     * honoured — it falls through to the legacy loose-home resolution. The
     * fixture wires no page-about-shaped folder for the pointer, so
     * findPageByUniqueId(pointer) returns null and the loose home.json wins.
     */
    public function testStalePointerFallsThroughToLegacyHome(): void {
        // Pointer names a page that isn't the resolvable loose home; about.json
        // exists but the pointer 'page-ghost' resolves to nothing.
        $svc = $this->makeService(
            ['uniqueId' => 'page-nl-home', 'title' => 'Welkom'],
            'page-ghost'
        );

        $this->assertSame(
            'page-nl-home',
            $svc->getHomepageUniqueId('nl'),
            'a pointer that does not resolve must fall through to the loose home'
        );
    }

    // -------------------------------------------------- resolveHomepageNodeUniqueId

    /**
     * resolveHomepageNodeUniqueId maps the legacy bare 'home' to the real
     * uniqueId of the loose home.json (its own raw read, distinct from
     * getHomepageUniqueId's cached read).
     */
    public function testResolveHomepageNodeMapsHomeToLooseUniqueId(): void {
        $svc = $this->makeService([
            'uniqueId' => 'page-nl-home',
            'title' => 'Welkom bij IntraVox',
        ]);

        $this->assertSame('page-nl-home', $svc->resolveHomepageNodeUniqueId('nl'));
    }

    /**
     * When nothing resolves and a pre-built tree is supplied, the first root
     * node's uniqueId is the last resort.
     */
    public function testResolveHomepageNodeFallsBackToFirstTreeNode(): void {
        $svc = $this->makeService(null);

        $tree = [
            ['uniqueId' => 'page-first', 'title' => 'First'],
            ['uniqueId' => 'page-second', 'title' => 'Second'],
        ];
        $this->assertSame('page-first', $svc->resolveHomepageNodeUniqueId('nl', $tree));
    }

    /** No pointer, no home.json, no tree: the bare legacy 'home'. */
    public function testResolveHomepageNodeBareHomeWhenNothingResolves(): void {
        $svc = $this->makeService(null);
        $this->assertSame('home', $svc->resolveHomepageNodeUniqueId('nl'));
    }

    // ------------------------------------------------------------- setHomepage

    /**
     * A page folder `{name}/` under nl/ holding `{name}.json` with the given
     * uniqueId — what the real PageLocator::findPageByUniqueId walks to.
     */
    private function pageFolder(string $langPath, string $name, string $uniqueId): Folder {
        $path = $langPath . '/' . $name;
        return $this->makeFolder($path, [
            $name . '.json' => $this->makeFile(
                $path . '/' . $name . '.json',
                ['uniqueId' => $uniqueId, 'title' => ucfirst($name)]
            ),
        ]);
    }

    /**
     * A service driving the REAL setHomepage over a fixture nl/ tree. isHomepage
     * (public seam) is overridden to $alreadyHome so the already-home short-
     * circuit is testable without wiring the pointer; clearCache is recorded.
     *
     * @param array<string,Folder> $nlChildren the nl/ language-root children
     */
    private function makeSetHomepageService(
        array $nlChildren,
        bool $alreadyHome,
        HomepageService $engine
    ): PageService {
        $langFolder = $this->makeFolder('/IntraVox/nl', $nlChildren);
        $base = $this->makeFolder('/IntraVox', ['nl' => $langFolder]);

        $svc = new class extends PageService {
            public bool $stubAlreadyHome = false;
            public function __construct() {
            }
            public function clearCache(?string $pageId = null): void {
            }
            public function isHomepage(string $uniqueId, ?string $language = null): bool {
                return $this->stubAlreadyHome;
            }
        };
        $svc->stubAlreadyHome = $alreadyHome;

        $this->injectPageServiceDependencies($svc, [
            'userId' => 'tester',
            'logger' => $this->createMock(\Psr\Log\LoggerInterface::class),
            'homepageService' => $engine,
            'folderContext' => $this->fakeFolderContext(
                intraVox: $base,
                userLanguage: 'nl',
                primaryLanguage: 'nl',
                languageFolder: $langFolder,
                readLanguageFolder: $langFolder
            ),
        ]);
        return $svc;
    }

    public function testSetHomepageRejectsMissingPage(): void {
        $engine = $this->createMock(HomepageService::class);
        $engine->expects($this->never())->method('setHomepageUniqueId');
        // Empty nl/: the target uniqueId resolves to nothing.
        $svc = $this->makeSetHomepageService([], false, $engine);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Page not found');
        $svc->setHomepage('page-x');
    }

    public function testSetHomepageRejectsNonRootPage(): void {
        $engine = $this->createMock(HomepageService::class);
        $engine->expects($this->never())->method('setHomepageUniqueId');
        // page-x lives at nl/section/deep, whose parent is nl/section, not nl/.
        $deep = $this->pageFolder('/IntraVox/nl/section', 'deep', 'page-x');
        $section = $this->makeFolder('/IntraVox/nl/section', ['deep' => $deep]);
        $svc = $this->makeSetHomepageService(['section' => $section], false, $engine);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Only root-level pages can be the homepage');
        $svc->setHomepage('page-x');
    }

    public function testSetHomepageAlreadyHomeIsANoOp(): void {
        $engine = $this->createMock(HomepageService::class);
        $engine->expects($this->never())->method('setHomepageUniqueId');
        $welcome = $this->pageFolder('/IntraVox/nl', 'welcome', 'page-x');
        // isHomepage returns true -> short-circuit before any write. The
        // never() expectation on the engine is the assertion.
        $svc = $this->makeSetHomepageService(['welcome' => $welcome], true, $engine);

        $svc->setHomepage('page-x');
        $this->addToAssertionCount(1);
    }

    public function testSetHomepageWritesPointerAndClearsCache(): void {
        $engine = $this->createMock(HomepageService::class);
        $engine->expects($this->once())
            ->method('setHomepageUniqueId')
            ->with('page-x', 'nl');
        $welcome = $this->pageFolder('/IntraVox/nl', 'welcome', 'page-x');
        $svc = $this->makeSetHomepageService(['welcome' => $welcome], false, $engine);

        // The once() expectation on setHomepageUniqueId is the assertion.
        $svc->setHomepage('page-x');
        $this->addToAssertionCount(1);
    }

    /**
     * A loose home page counts as root-level even though the parent-path guard
     * would otherwise reject it: findPageByUniqueId flags home.json with
     * isHome=true, which bypasses the guard.
     */
    public function testSetHomepageAcceptsLooseHomeViaIsHomeFlag(): void {
        $engine = $this->createMock(HomepageService::class);
        $engine->expects($this->once())->method('setHomepageUniqueId')->with('page-x', 'nl');
        // A loose home.json directly in nl/ resolves with isHome=true.
        $svc = $this->makeSetHomepageService([
            'home.json' => $this->makeFile('/IntraVox/nl/home.json',
                ['uniqueId' => 'page-x', 'title' => 'Welkom']),
        ], false, $engine);

        $svc->setHomepage('page-x');
        $this->addToAssertionCount(1);
    }
}
