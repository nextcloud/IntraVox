<?php
declare(strict_types=1);

namespace OCA\IntraVox\Tests\Unit\Service\Language;

use OCA\IntraVox\Service\Folder\FolderContext;
use OCA\IntraVox\Service\Language\LanguageResolver;
use OCA\IntraVox\Service\Language\LanguageStatusService;
use OCA\IntraVox\Service\LanguageService;
use OCA\IntraVox\Service\Listing\PageLister;
use OCA\IntraVox\Service\Locator\PageLocator;
use OCA\IntraVox\Service\PageIndexService;
use OCA\IntraVox\Tests\Unit\Service\Harness\BuildsServiceDoubles;
use OCP\Files\File;
use OCP\Files\FileInfo;
use OCP\Files\Folder;
use OCP\Files\IRootFolder;
use OCP\Files\NotFoundException;
use OCP\IConfig;
use Psr\Log\LoggerInterface;
use PHPUnit\Framework\TestCase;

/**
 * Behavioural characterization of the two language-CONTENT-STATUS readers,
 * carved out of the retired PageService facade onto LanguageStatusService:
 *
 *   - getContentStatus() — the "where does content live, per language"
 *     signal that drives the landing-page fallback notice and the admin
 *     "Languages with content" chips.
 *   - getPageCountByLanguage()   — the per-language page count the admin "remove
 *     language" confirmation warns with.
 *
 * These two had only arity pins before; their real behaviour (the [a-z]{2,3}
 * folder filter, the real-vs-placeholder split, the dual sort(), the +1 for a
 * loose home.json, and the log-and-continue on a throwing folder) was untested.
 * This file locks that behaviour in, so the language-status carve is proven
 * byte-equivalent by tests that pin behaviour, not location.
 *
 * LanguageStatusService is built DIRECTLY here. Where the retired facade threaded
 * three $this-bound closures into getContentStatus (resolveHomepageNodeUniqueId /
 * languageFolderHasHomepage / languageFolderHasRealContent), this test reproduces
 * them EXACTLY as the LanguageController does over the injected substrate:
 *   - resolveHomepage: fn(?string) => a homepage uniqueId (no test pins its VALUE,
 *     only that the key is present, so a fixed fixture id stands in for the
 *     HomepageResolverService the facade delegated to);
 *   - hasHomepage:     fn(Folder) => $folders->hasHomepage($folder);
 *   - hasRealContent:  fn(Folder) => $folders->hasRealContent($folder).
 *
 * Like the old file, this drives the REAL FolderContext bodies (the #75
 * effective-language probe, the real-vs-placeholder homepage resolution) over a
 * fixture IntraVox tree via a mocked rootFolder->getUserFolder()->get('IntraVox'),
 * so nothing here overrides a folder seam.
 */
class LanguageStatusServiceTest extends TestCase {

    use BuildsServiceDoubles;

    /** A File whose getContent()/cached read returns the given JSON. */
    private function jsonFile(string $path, array $json): File {
        $file = $this->createMock(File::class);
        $file->method('getName')->willReturn(basename($path));
        $file->method('getType')->willReturn(FileInfo::TYPE_FILE);
        $file->method('getPath')->willReturn($path);
        $file->method('getContent')->willReturn(json_encode($json));
        $file->method('getId')->willReturn(abs(crc32($path)));
        $file->method('getMTime')->willReturn(1000);
        $file->method('isReadable')->willReturn(true);
        return $file;
    }

    /**
     * A folder with named children (files or folders).
     *
     * @param array<string,\OCP\Files\Node> $children
     */
    private function folder(string $path, array $children = []): Folder {
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
     * A language folder with a loose home.json. With a real title it counts as
     * real content; with `_generated` it is a placeholder that is "active" but
     * not "with content".
     *
     * @param array<string,\OCP\Files\Node> $extra additional named children (subpages)
     */
    private function langFolder(string $path, ?array $home = null, array $extra = []): Folder {
        $children = $extra;
        if ($home !== null) {
            $children['home.json'] = $this->jsonFile($path . '/home.json', $home);
        }
        return $this->folder($path, $children);
    }

    /** A subpage folder {name}/ holding {name}.json — what walkPlain counts. */
    private function subpage(string $langPath, string $name): Folder {
        $path = $langPath . '/' . $name;
        return $this->folder($path, [
            $name . '.json' => $this->jsonFile(
                $path . '/' . $name . '.json',
                ['uniqueId' => 'page-' . $name, 'title' => ucfirst($name)]
            ),
        ]);
    }

    private function realHome(string $title = 'Welcome'): array {
        return ['uniqueId' => 'page-home', 'title' => $title];
    }

    private function placeholderHome(): array {
        return ['uniqueId' => 'page-home', 'title' => 'Placeholder', '_generated' => true];
    }

    /**
     * The /IntraVox base whose direct listing is the given code=>folder map,
     * plus any non-language decoy folders passed in $decoys.
     *
     * @param array<string,Folder> $languages
     * @param array<string,Folder> $decoys non-language-code siblings (e.g. _media)
     */
    private function baseFolder(array $languages, array $decoys = []): Folder {
        $all = $languages + $decoys;
        $base = $this->createMock(Folder::class);
        $base->method('getName')->willReturn('IntraVox');
        $base->method('getType')->willReturn(FileInfo::TYPE_FOLDER);
        $base->method('getPath')->willReturn('/IntraVox');
        $base->method('getDirectoryListing')->willReturn(array_values($all));
        $base->method('nodeExists')->willReturnCallback(fn($n) => isset($all[$n]));
        $base->method('get')->willReturnCallback(function ($code) use ($all) {
            if (isset($all[$code])) {
                return $all[$code];
            }
            throw new NotFoundException('/IntraVox/' . $code);
        });
        return $base;
    }

    /**
     * A LanguageStatusService running the real folder bodies over $base, built
     * DIRECTLY (no PageService facade), with an injected logger so log assertions
     * are possible.
     *
     * The folder scan + #75 probe run through the REAL FolderContext (no
     * intraVoxOverride -> genuine mount walk over $rootFolder), exactly as the old
     * facade drove them.
     */
    private function makeService(Folder $base, ?LoggerInterface $logger = null): LanguageStatusService {
        $rootFolder = $this->createMock(IRootFolder::class);
        $userFolder = $this->createMock(Folder::class);
        $userFolder->method('get')->willReturnCallback(function ($p) use ($base) {
            if ($p === 'IntraVox') {
                return $base;
            }
            throw new NotFoundException('/' . $p);
        });
        $rootFolder->method('getUserFolder')->with('tester')->willReturn($userFolder);

        $config = $this->createMock(IConfig::class);
        $config->method('getUserValue')->willReturnCallback(
            fn($uid, $app, $key, $default = '') => 'en'
        );

        $languageService = $this->createMock(LanguageService::class);
        $languageService->method('getPrimaryLanguage')->willReturn('en');

        $log = $logger ?? $this->createMock(LoggerInterface::class);

        // The service's own read-only page locator (drives cachedDirectoryListing
        // over the fixture tree). A separate real locator backs FolderContext's
        // #75 probe, matching production where each collaborator owns its own.
        $index = $this->createMock(PageIndexService::class);
        $serviceLocator = new PageLocator($index, $log);

        $folderContext = new FolderContext(
            $rootFolder,
            'tester',
            $config,
            $languageService,
            new LanguageResolver(),
            new PageLocator(
                $this->createMock(PageIndexService::class),
                $this->createMock(LoggerInterface::class)
            )
        );

        // A real (final) PageLister: getPageCountByLanguage delegates its subpage
        // walk to walkPlain(), which recurses the lister's OWN PageLocator (its
        // cachedDirectoryListing) and calls permissionService->permissionsFromNode
        // — nothing else. So the locator MUST be a real one that walks the fixture
        // tree (a doubled mock returns empty listings and the walk finds nothing);
        // the remaining deps are inert (permissionsFromNode's value never affects
        // the count, and FolderContext is never touched by walkPlain).
        $pageLister = new PageLister(
            new PageLocator($this->createMock(PageIndexService::class), $log),
            $this->createMock(PageIndexService::class),
            $this->createMock(\OCA\IntraVox\Service\PermissionService::class),
            $log,
            $folderContext,
            $this->doubleOrBuild(\OCA\IntraVox\Service\Sanitize\PageShapeSanitizer::class),
            $this->createMock(\OCA\IntraVox\Service\Cache\PageCacheService::class),
            $this->doubleOrBuild(\OCA\IntraVox\Service\Path\PageDataEnricher::class),
        );

        return new LanguageStatusService(
            $folderContext,
            $pageLister,
            $serviceLocator,
            $log,
        );
    }

    /**
     * The three closures the retired PageService facade threaded into
     * getContentStatus, reproduced EXACTLY as the LanguageController does — the
     * ANY-homepage and real-content probes forwarded to FolderContext, and a
     * homepage-uniqueId resolver (the facade delegated this to
     * HomepageResolverService; no test pins its VALUE, only that the key exists,
     * so a fixed fixture id stands in).
     *
     * @return array{0:\Closure,1:\Closure,2:\Closure}
     */
    private function statusClosures(FolderContext $folders): array {
        return [
            fn(?string $language): string => 'page-home',
            fn(Folder $folder): bool => $folders->hasHomepage($folder),
            fn(Folder $folder): bool => $folders->hasRealContent($folder),
        ];
    }

    /**
     * Drive getContentStatus with the reproduced closures bound to $folders (the
     * REAL FolderContext), so hasHomepage/hasRealContent run their genuine bodies.
     */
    private function contentStatus(LanguageStatusService $svc): array {
        // The closures must be bound to the SAME FolderContext the service scans,
        // so read them back off the service via reflection — the private property
        // is the exact substrate the facade shared with its closures.
        $folders = (new \ReflectionObject($svc))->getProperty('folders');
        [$resolve, $hasHomepage, $hasRealContent] = $this->statusClosures($folders->getValue($svc));
        return $svc->getContentStatus($resolve, $hasHomepage, $hasRealContent);
    }

    // -------------------------------------------------- getLanguageContentStatus

    public function testContentStatusSplitsRealFromPlaceholderAndFiltersNonLanguageFolders(): void {
        $base = $this->baseFolder(
            [
                'nl' => $this->langFolder('/IntraVox/nl', $this->realHome('Welkom')),
                'en' => $this->langFolder('/IntraVox/en', $this->placeholderHome()),
                'de' => $this->langFolder('/IntraVox/de', $this->realHome('Willkommen')),
            ],
            [
                // A non-language sibling and a 4-letter folder: both must be
                // filtered by the /^[a-z]{2,3}$/ guard.
                '_media' => $this->folder('/IntraVox/_media'),
                'test' => $this->folder('/IntraVox/test', [
                    'home.json' => $this->jsonFile('/IntraVox/test/home.json', $this->realHome()),
                ]),
            ]
        );

        $status = $this->contentStatus($this->makeService($base));

        // languagesWithContent = only REAL homepages, sorted. en is a placeholder.
        $this->assertSame(['de', 'nl'], $status['languagesWithContent']);
        // activeLanguages = ANY homepage incl. placeholder, sorted.
        $this->assertSame(['de', 'en', 'nl'], $status['activeLanguages']);
        // Non-language folders never appear.
        $this->assertNotContains('_media', $status['activeLanguages']);
        $this->assertNotContains('test', $status['activeLanguages']);
        $this->assertNotContains('test', $status['languagesWithContent']);

        // The result carries all six keys.
        foreach (['language', 'hasContent', 'servedLanguage', 'languagesWithContent', 'activeLanguages', 'homepageUniqueId'] as $key) {
            $this->assertArrayHasKey($key, $status);
        }
        $this->assertSame('en', $status['language']);
    }

    public function testContentStatusHasContentTracksServedLanguage(): void {
        // en has real content, so the served language resolves and hasContent is true.
        $base = $this->baseFolder([
            'en' => $this->langFolder('/IntraVox/en', $this->realHome()),
        ]);
        $status = $this->contentStatus($this->makeService($base));
        $this->assertTrue($status['hasContent']);
        $this->assertSame('en', $status['servedLanguage']);
        $this->assertSame($status['hasContent'], $status['servedLanguage'] !== null);
    }

    public function testContentStatusNoRealContentAnywhereYieldsFalseAndNullServed(): void {
        // Only a placeholder exists: active, but nothing real to serve → the
        // sole trigger of the landing-page fallback notice.
        $base = $this->baseFolder([
            'en' => $this->langFolder('/IntraVox/en', $this->placeholderHome()),
        ]);
        $status = $this->contentStatus($this->makeService($base));
        $this->assertFalse($status['hasContent']);
        $this->assertNull($status['servedLanguage']);
        $this->assertSame(['en'], $status['activeLanguages']);
        $this->assertSame([], $status['languagesWithContent']);
    }

    public function testContentStatusLogsAndDegradesWhenTheBaseListingThrows(): void {
        $base = $this->createMock(Folder::class);
        $base->method('getName')->willReturn('IntraVox');
        $base->method('getType')->willReturn(FileInfo::TYPE_FOLDER);
        $base->method('getPath')->willReturn('/IntraVox');
        // The directory walk blows up mid-status.
        $base->method('getDirectoryListing')->willThrowException(new \RuntimeException('disk gone'));
        $base->method('get')->willThrowException(new NotFoundException('/IntraVox/x'));

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->atLeastOnce())
            ->method('warning')
            ->with($this->stringContains('[PageService] getLanguageContentStatus failed:'));

        $status = $this->contentStatus($this->makeService($base, $logger));

        // The shape survives: empty content arrays, not a fatal.
        $this->assertSame([], $status['languagesWithContent']);
        $this->assertSame([], $status['activeLanguages']);
        $this->assertArrayHasKey('homepageUniqueId', $status);
    }

    // ----------------------------------------------------- getPageCountByLanguage

    public function testPageCountAddsOneForALooseHomeJsonButNotForNormalizedHome(): void {
        // nl: loose home.json + two subpages → 2 walked + 1 for home.json = 3.
        $nl = $this->langFolder('/IntraVox/nl', $this->realHome(), [
            'about' => $this->subpage('/IntraVox/nl', 'about'),
            'news' => $this->subpage('/IntraVox/nl', 'news'),
        ]);
        // en: normalized home/home.json (NO loose home.json) + one subpage. The
        // +1 is loose-only, so en counts just its walked pages.
        $en = $this->folder('/IntraVox/en', [
            'home' => $this->folder('/IntraVox/en/home', [
                'home.json' => $this->jsonFile('/IntraVox/en/home/home.json', $this->realHome()),
            ]),
            'about' => $this->subpage('/IntraVox/en', 'about'),
        ]);

        $base = $this->baseFolder(
            ['nl' => $nl, 'en' => $en],
            ['_media' => $this->folder('/IntraVox/_media')]
        );

        $counts = $this->makeService($base)->getPageCountByLanguage();

        $this->assertSame(3, $counts['nl'], 'two subpages + the loose home.json');
        // en: the normalized "home" folder holds home/home.json, which walkPlain
        // DOES count as a page (folder "home" + "home.json" with uniqueId+title),
        // plus one real subpage = 2. Crucially there is NO loose home.json at the
        // language root, so the +1 does NOT fire — that +1 is loose-only.
        $this->assertSame(2, $counts['en'], 'home page + one subpage, no +1 (no loose home.json)');
        $this->assertArrayNotHasKey('_media', $counts, 'non-language folders are not counted');
    }

    public function testPageCountLogsAndDegradesWhenTheBaseListingThrows(): void {
        $base = $this->createMock(Folder::class);
        $base->method('getName')->willReturn('IntraVox');
        $base->method('getType')->willReturn(FileInfo::TYPE_FOLDER);
        $base->method('getPath')->willReturn('/IntraVox');
        $base->method('getDirectoryListing')->willThrowException(new \RuntimeException('disk gone'));
        $base->method('get')->willThrowException(new NotFoundException('/IntraVox/x'));

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->atLeastOnce())
            ->method('warning')
            ->with($this->stringContains('[PageService] getPageCountByLanguage failed:'));

        $counts = $this->makeService($base, $logger)->getPageCountByLanguage();
        $this->assertSame([], $counts, 'a failed listing yields an empty count map, not a fatal');
    }
}
