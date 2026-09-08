<?php
declare(strict_types=1);

namespace OCA\IntraVox\Tests\Unit\Service;

use OCA\IntraVox\Service\PageIndexService;
use OCA\IntraVox\Service\PageService;
use OCA\IntraVox\Tests\Unit\Service\Harness\BuildsPageService;
use OCP\Files\File;
use OCP\Files\FileInfo;
use OCP\Files\Folder;
use PHPUnit\Framework\TestCase;

/**
 * The page index answers lookups, but never at the cost of correctness.
 *
 * Resolving a uniqueId used to read and JSON-parse every page file in every
 * language folder — measured at 9,000 reads for a 3,000-page x 3-language
 * install. The index turns that into one query.
 *
 * The rule that makes it safe to rely on: the index is a CACHE over the
 * filesystem, never an authority. Every hit is verified against the file on
 * disk, and anything that does not check out falls through to the scan. A
 * stale index therefore costs performance, never correctness — which is what
 * these tests pin down.
 */
class PageIndexLookupTest extends TestCase {

    use BuildsPageService;

    /** Page files whose content was read, to prove the scan was skipped. */
    private array $reads = [];

    protected function setUp(): void {
        $this->reads = [];
    }

    private function makeFile(string $path, array $json): File {
        $file = $this->createMock(File::class);
        $file->method('getName')->willReturn(basename($path));
        $file->method('getType')->willReturn(FileInfo::TYPE_FILE);
        $file->method('getPath')->willReturn($path);
        $file->method('getId')->willReturn(abs(crc32($path)));
        $file->method('isUpdateable')->willReturn(true);
        $file->method('getContent')->willReturnCallback(function () use ($path, $json) {
            $this->reads[] = $path;
            return json_encode($json);
        });
        return $file;
    }

    /** @param array<string, File|Folder> $children */
    private function makeFolder(string $path, array $children, ?Folder $parent = null): Folder {
        $folder = $this->createMock(Folder::class);
        $folder->method('getName')->willReturn(basename($path));
        $folder->method('getType')->willReturn(FileInfo::TYPE_FOLDER);
        $folder->method('getPath')->willReturn($path);
        $folder->method('getDirectoryListing')->willReturn(array_values($children));
        $folder->method('nodeExists')->willReturnCallback(fn($n) => isset($children[$n]));
        $folder->method('getParent')->willReturn($parent);
        $folder->method('get')->willReturnCallback(function ($p) use ($children, $path) {
            if (isset($children[$p])) {
                return $children[$p];
            }
            // Resolve nested relative paths, as folderFromAbsolutePath() does.
            if (str_contains($p, '/')) {
                [$head, $rest] = explode('/', $p, 2);
                if (isset($children[$head]) && $children[$head] instanceof Folder) {
                    return $children[$head]->get($rest);
                }
            }
            throw new \OCP\Files\NotFoundException($path . '/' . $p);
        });
        return $folder;
    }

    /**
     * Fixture: /IntraVox/en holds about.json beside an about/ folder.
     * $indexRows simulates the index; empty means it knows nothing.
     */
    private function makeService(array $indexRows, ?array $pageJson = null): PageService {
        $pageJson ??= ['uniqueId' => 'page-idx', 'title' => 'About', 'widgets' => []];

        $aboutJson = $this->makeFile('/IntraVox/en/about.json', $pageJson);

        // Built in two passes: about/ needs a parent link to en/, and en/ needs
        // about/ among its children. The first en/ exists only to be that
        // parent; the second is the one the service sees.
        $enForParent = $this->makeFolder('/IntraVox/en', ['about.json' => $aboutJson]);
        $aboutFolder = $this->makeFolder('/IntraVox/en/about', [], $enForParent);
        $en = $this->makeFolder('/IntraVox/en', [
            'about.json' => $aboutJson,
            'about' => $aboutFolder,
        ]);

        $base = $this->makeFolder('/IntraVox', ['en' => $en]);

        // locatePageAnyLanguage (driven by reflection below) resolves its read
        // folder via folders()->readLanguageFolder() ($en) and walks cross-language
        // via the injected FolderContext ($base -> intraVox).
        $svc = new class() extends PageService {
            public function __construct() {
            }
            public function clearCache(): void {
            }
        };

        $index = $this->createMock(PageIndexService::class);
        $index->method('findByUniqueId')->willReturnCallback(
            fn(string $uniqueId, ?string $pref = null) => $indexRows[$uniqueId] ?? null
        );

        $explicit = [
            'userSession' => $this->createMock(\OCP\IUserSession::class),
            'userId' => 'tester',
            'config' => $this->createMock(\OCP\IConfig::class),
            'logger' => $this->createMock(\Psr\Log\LoggerInterface::class),
            'languageService' => $this->createMock(\OCA\IntraVox\Service\LanguageService::class),
            'pageIndexService' => $index,
            'folderContext' => $this->fakeFolderContext(readLanguageFolder: $en, intraVox: $base),
        ];
        $this->injectPageServiceDependencies($svc, $explicit);
        return $svc;
    }

    /**
     * Drive the private locator through reflection. The read folder now comes from
     * the injected FolderContext (folders()->readLanguageFolder()) rather than the
     * retired getReadLanguageFolder seam.
     */
    private function locate(PageService $svc, string $uniqueId): ?array {
        $m = new \ReflectionMethod(PageService::class, 'locatePageAnyLanguage');
        $folders = (new \ReflectionMethod(PageService::class, 'folders'))->invoke($svc);
        $readFolder = $folders->readLanguageFolder();
        return $m->invoke($svc, $readFolder, $uniqueId);
    }

    /** An indexed page resolves, and the result matches what a scan returns. */
    public function testIndexedLookupResolvesThePage(): void {
        $svc = $this->makeService([
            'page-idx' => ['path' => '/IntraVox/en/about', 'language' => 'en'],
        ]);

        $result = $this->locate($svc, 'page-idx');

        $this->assertNotNull($result);
        $this->assertSame('/IntraVox/en/about.json', $result['file']->getPath());
        $this->assertSame('/IntraVox/en/about', $result['folder']->getPath());
        $this->assertFalse($result['isHome']);
    }

    /**
     * The point of the exercise: an indexed hit reads ONE file (the verify),
     * where the scan reads every page it passes.
     */
    public function testIndexedLookupReadsOnlyTheTargetFile(): void {
        $svc = $this->makeService([
            'page-idx' => ['path' => '/IntraVox/en/about', 'language' => 'en'],
        ]);

        $this->locate($svc, 'page-idx');

        $this->assertSame(
            ['/IntraVox/en/about.json'],
            $this->reads,
            'an indexed hit must read only the file it verifies'
        );
    }

    /**
     * A STALE index must not produce a wrong answer. Here the index points at a
     * folder that holds a different page; the verify fails and the scan takes
     * over, still finding the real page.
     */
    public function testStaleIndexFallsBackToTheScan(): void {
        $svc = $this->makeService([
            // Points at the right folder, but claims a uniqueId the file does
            // not carry — exactly what a moved or overwritten page looks like.
            'page-gone' => ['path' => '/IntraVox/en/about', 'language' => 'en'],
        ]);

        // The id the index knows resolves to nothing…
        $this->assertNull($this->locate($svc, 'page-gone'));
        // …while the page that really exists is still found by the scan.
        $this->assertNotNull($this->locate($svc, 'page-idx'));
    }

    /** An index pointing outside the user's tree is ignored, not followed. */
    public function testIndexPathOutsideTheTreeIsIgnored(): void {
        $svc = $this->makeService([
            'page-idx' => ['path' => '/SomeoneElse/en/about', 'language' => 'en'],
        ]);

        // Falls back to the scan and still finds the real page.
        $result = $this->locate($svc, 'page-idx');
        $this->assertNotNull($result);
        $this->assertSame('/IntraVox/en/about.json', $result['file']->getPath());
    }

    /** With an empty index everything still works, via the scan. */
    public function testEmptyIndexStillResolvesViaScan(): void {
        $svc = $this->makeService([]);

        $result = $this->locate($svc, 'page-idx');

        $this->assertNotNull($result);
        $this->assertSame('/IntraVox/en/about.json', $result['file']->getPath());
    }

    /** A genuinely unknown id is not found by either route. */
    public function testUnknownIdIsNotFound(): void {
        $svc = $this->makeService([]);
        $this->assertNull($this->locate($svc, 'page-nope'));
    }

    /**
     * listPages() may serve from the index only when the homepage is in it.
     *
     * The homepage lives as home.json at the language ROOT, not in a page
     * folder, and on real installs it is sometimes absent from the index — a
     * loose home.json without a uniqueId cannot be indexed at all. Serving the
     * index list regardless would silently drop the homepage from the sidebar,
     * which is a worse failure than being slow.
     */
    public function testListPagesFallsBackWhenTheHomepageIsNotIndexed(): void {
        $svc = $this->makeServiceWithHome(
            // The index knows about a page, but NOT about the homepage.
            [['unique_id' => 'page-idx', 'title' => 'About', 'path' => '/IntraVox/en/about',
              'status' => 'published', 'modified_at' => 100]],
            ['uniqueId' => 'page-home', 'title' => 'Home']
        );

        $result = (new \ReflectionMethod(PageService::class, 'listPagesFromIndex'))
            ->invoke($svc, (new \ReflectionMethod(PageService::class, 'folders'))->invoke($svc)->readLanguageFolder());

        $this->assertNull($result, 'an index list missing the homepage must not be served');
    }

    /** With the homepage indexed, the fast path is used. */
    public function testListPagesUsesTheIndexWhenComplete(): void {
        $svc = $this->makeServiceWithHome(
            [
                ['unique_id' => 'page-home', 'title' => 'Home', 'path' => '/IntraVox/en',
                 'status' => 'published', 'modified_at' => 50],
                ['unique_id' => 'page-idx', 'title' => 'About', 'path' => '/IntraVox/en/about',
                 'status' => 'published', 'modified_at' => 100],
            ],
            ['uniqueId' => 'page-home', 'title' => 'Home']
        );

        $result = (new \ReflectionMethod(PageService::class, 'listPagesFromIndex'))
            ->invoke($svc, (new \ReflectionMethod(PageService::class, 'folders'))->invoke($svc)->readLanguageFolder());

        $this->assertNotNull($result);
        $this->assertSame(['page-home', 'page-idx'], array_column($result, 'uniqueId'));
    }

    /**
     * A language folder with no loose homepage at all has nothing to guarantee,
     * so the index list is served as-is.
     */
    public function testListPagesServesIndexWhenThereIsNoLooseHomepage(): void {
        $svc = $this->makeServiceWithHome(
            [['unique_id' => 'page-idx', 'title' => 'About', 'path' => '/IntraVox/en/about',
              'status' => 'published', 'modified_at' => 100]],
            null // no home.json in the language root
        );

        $result = (new \ReflectionMethod(PageService::class, 'listPagesFromIndex'))
            ->invoke($svc, (new \ReflectionMethod(PageService::class, 'folders'))->invoke($svc)->readLanguageFolder());

        $this->assertNotNull($result);
        $this->assertSame(['page-idx'], array_column($result, 'uniqueId'));
    }

    /** No index entries for this language -> fall back to the scan (null). */
    public function testEmptyIndexForLanguageFallsBack(): void {
        // hasEntries() returns false when indexRows is empty (see the mock).
        $svc = $this->makeServiceWithHome([], null);

        $result = (new \ReflectionMethod(PageService::class, 'listPagesFromIndex'))
            ->invoke($svc, (new \ReflectionMethod(PageService::class, 'folders'))->invoke($svc)->readLanguageFolder());

        $this->assertNull($result, 'no entries for the language means fall back to the walk');
    }

    /** An index query that throws must fall back to the scan, not blow up. */
    public function testIndexQueryFailureFallsBack(): void {
        $svc = $this->makeServiceWithHome(
            [['unique_id' => 'page-idx', 'title' => 'About', 'path' => '/IntraVox/en/about',
              'status' => 'published', 'modified_at' => 100]],
            null,
            throwOnGetPages: true
        );

        $result = (new \ReflectionMethod(PageService::class, 'listPagesFromIndex'))
            ->invoke($svc, (new \ReflectionMethod(PageService::class, 'folders'))->invoke($svc)->readLanguageFolder());

        $this->assertNull($result, 'an index failure falls back to the walk rather than throwing');
    }

    /** Rows with a blank unique_id or path are skipped, not served. */
    public function testRowsWithBlankIdOrPathAreSkipped(): void {
        $svc = $this->makeServiceWithHome(
            [
                ['unique_id' => '', 'title' => 'No id', 'path' => '/IntraVox/en/about',
                 'status' => 'published', 'modified_at' => 10],
                ['unique_id' => 'page-nopath', 'title' => 'No path', 'path' => '',
                 'status' => 'published', 'modified_at' => 20],
                ['unique_id' => 'page-idx', 'title' => 'About', 'path' => '/IntraVox/en/about',
                 'status' => 'published', 'modified_at' => 100],
            ],
            null
        );

        $result = (new \ReflectionMethod(PageService::class, 'listPagesFromIndex'))
            ->invoke($svc, (new \ReflectionMethod(PageService::class, 'folders'))->invoke($svc)->readLanguageFolder());

        $this->assertNotNull($result);
        $this->assertSame(['page-idx'], array_column($result, 'uniqueId'), 'blank-id and blank-path rows are dropped');
    }

    /** A row whose path resolves nowhere in the tree is skipped. */
    public function testRowWithUnresolvablePathIsSkipped(): void {
        $svc = $this->makeServiceWithHome(
            [
                ['unique_id' => 'page-ghost', 'title' => 'Ghost', 'path' => '/IntraVox/en/does-not-exist',
                 'status' => 'published', 'modified_at' => 10],
                ['unique_id' => 'page-idx', 'title' => 'About', 'path' => '/IntraVox/en/about',
                 'status' => 'published', 'modified_at' => 100],
            ],
            null
        );

        $result = (new \ReflectionMethod(PageService::class, 'listPagesFromIndex'))
            ->invoke($svc, (new \ReflectionMethod(PageService::class, 'folders'))->invoke($svc)->readLanguageFolder());

        $this->assertNotNull($result);
        $this->assertSame(['page-idx'], array_column($result, 'uniqueId'), 'a row pointing nowhere is skipped');
    }

    /** The served rows carry the index title/status/modified verbatim. */
    public function testServedRowShapeCarriesIndexFields(): void {
        $svc = $this->makeServiceWithHome(
            [['unique_id' => 'page-idx', 'title' => 'About Us', 'path' => '/IntraVox/en/about',
              'status' => 'draft', 'modified_at' => 1234]],
            null
        );

        $result = (new \ReflectionMethod(PageService::class, 'listPagesFromIndex'))
            ->invoke($svc, (new \ReflectionMethod(PageService::class, 'folders'))->invoke($svc)->readLanguageFolder());

        $this->assertNotNull($result);
        $this->assertSame('page-idx', $result[0]['uniqueId']);
        $this->assertSame('About Us', $result[0]['title']);
        $this->assertSame('draft', $result[0]['status']);
        $this->assertSame(1234, $result[0]['modified']);
        $this->assertArrayHasKey('permissions', $result[0]);
    }

    /**
     * Build a service whose en/ folder optionally holds a loose home.json.
     *
     * @param array $indexRows rows getPagesByLanguage() should return
     * @param array|null $homeJson contents of en/home.json, or null for none
     * @param bool $throwOnGetPages make getPagesByLanguage() throw, to pin the
     *   fall-back-on-failure branch
     */
    private function makeServiceWithHome(array $indexRows, ?array $homeJson, bool $throwOnGetPages = false): PageService {
        $aboutJson = $this->makeFile(
            '/IntraVox/en/about.json',
            ['uniqueId' => 'page-idx', 'title' => 'About']
        );

        $children = ['about.json' => $aboutJson];
        if ($homeJson !== null) {
            $children['home.json'] = $this->makeFile('/IntraVox/en/home.json', $homeJson);
        }

        $enForParent = $this->makeFolder('/IntraVox/en', $children);
        $aboutFolder = $this->makeFolder('/IntraVox/en/about', [], $enForParent);
        $en = $this->makeFolder('/IntraVox/en', $children + ['about' => $aboutFolder]);
        $base = $this->makeFolder('/IntraVox', ['en' => $en]);

        // listPagesFromIndex (driven by reflection) resolves its folder via
        // folders()->readLanguageFolder() ($en). Cross-language locate resolves
        // its root via the injected FolderContext ($base -> intraVox).
        $svc = new class() extends PageService {
            public function __construct() {
            }
            public function clearCache(): void {
            }
        };

        $index = $this->createMock(PageIndexService::class);
        $index->method('hasEntries')->willReturn(!empty($indexRows));
        if ($throwOnGetPages) {
            $index->method('getPagesByLanguage')
                ->willThrowException(new \RuntimeException('index query blew up'));
        } else {
            $index->method('getPagesByLanguage')->willReturn($indexRows);
        }

        $explicit = [
            'userSession' => $this->createMock(\OCP\IUserSession::class),
            'userId' => 'tester',
            'config' => $this->createMock(\OCP\IConfig::class),
            'logger' => $this->createMock(\Psr\Log\LoggerInterface::class),
            'languageService' => $this->createMock(\OCA\IntraVox\Service\LanguageService::class),
            'pageIndexService' => $index,
            'folderContext' => $this->fakeFolderContext(readLanguageFolder: $en, intraVox: $base),
        ];
        $this->injectPageServiceDependencies($svc, $explicit);
        return $svc;
    }


}
