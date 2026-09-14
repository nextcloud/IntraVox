<?php
declare(strict_types=1);

namespace OCA\IntraVox\Tests\Unit\Service\Path;

use OCA\IntraVox\Service\Cache\PageCacheService;
use OCA\IntraVox\Service\LanguageService;
use OCA\IntraVox\Service\Listing\PageLister;
use OCA\IntraVox\Service\Locator\PageLocator;
use OCA\IntraVox\Service\Path\BreadcrumbService;
use OCA\IntraVox\Service\PermissionService;
use OCA\IntraVox\Tests\Unit\Service\Harness\BuildsPageRead;
use OCA\IntraVox\Tests\Unit\Service\Harness\BuildsPageService;
use OCP\Files\Folder;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Characterizes BreadcrumbService — the fase-6 Track 1 carve of the former
 * PageService::getBreadcrumb. The five cases are ported verbatim from the retired
 * PageBreadcrumbTest; instead of overriding getPage() on a PageService subclass,
 * they feed the collaborators the service now injects: the page via a real
 * PageReadService (fakePageReadReturning), the homepage flag via a rigged
 * HomepageResolverService, the navigation folder via a fixture FolderContext, and
 * the parent-page lookup via a real PageLister whose byFolderPath reads a rigged
 * request cache. Behaviour is byte-identical to the old getBreadcrumb.
 */
class BreadcrumbServiceTest extends TestCase {

    use BuildsPageService;
    use BuildsPageRead;

    /**
     * @param array<string,mixed> $currentPage what getPage() returns for the id
     * @param array<int,array>|null $navItems items for navigation.json (null = no file)
     * @param bool $isHomepage what the homepage resolver reports for currentPage's id
     * @param array<string,array> $parents folderPath => parent page for byFolderPath
     */
    private function makeService(
        array $currentPage,
        ?array $navItems = null,
        bool $isHomepage = false,
        array $parents = []
    ): BreadcrumbService {
        // The read-language folder navigation.json feeds the Home label.
        $langFolder = $this->createMock(Folder::class);
        $langFolder->method('nodeExists')->willReturnCallback(
            fn($n) => $n === 'navigation.json' && $navItems !== null
        );
        if ($navItems !== null) {
            $navFile = $this->makeFile('/IntraVox/en/navigation.json', ['items' => $navItems]);
            $langFolder->method('get')->willReturn($navFile);
        }

        $language = $this->createMock(LanguageService::class);
        $language->method('isLanguageAvailable')->willReturnCallback(
            fn(string $c) => in_array($c, ['en', 'de', 'nl'], true)
        );

        // The parent lookup: byFolderPath short-circuits on the request cache, so a
        // rigged cache resolves parents without any folder fixture. An absent path is
        // a cache MISS -> null -> the humanised-folder fallback.
        $cache = $this->createMock(PageCacheService::class);
        $cache->method('hasFolderPath')->willReturnCallback(fn($p) => isset($parents[$p]));
        $cache->method('getFolderPath')->willReturnCallback(fn($p) => $parents[$p] ?? null);
        $index = $this->createMock(\OCA\IntraVox\Service\PageIndexService::class);
        $logger = $this->createMock(LoggerInterface::class);
        $pageLister = new PageLister(
            new PageLocator($index, $logger),
            $index,
            $this->createMock(PermissionService::class),
            $logger,
            $this->fakeFolderContext(readLanguageFolder: $langFolder, userLanguage: 'en'),
            $this->doubleOrBuild(\OCA\IntraVox\Service\Sanitize\PageShapeSanitizer::class),
            $cache,
            $this->doubleOrBuild(\OCA\IntraVox\Service\Path\PageDataEnricher::class),
        );

        return new BreadcrumbService(
            $this->fakePageReadReturning($currentPage),
            $this->fakeFolderContext(readLanguageFolder: $langFolder, userLanguage: 'en'),
            $this->fakeHomepageResolver($isHomepage ? ($currentPage['uniqueId'] ?? null) : null),
            $language,
            $pageLister,
        );
    }

    public function testHomePageReturnsOnlyTheHomeEntryMarkedCurrent(): void {
        $svc = $this->makeService(
            ['uniqueId' => 'page-home', 'title' => 'Welcome', 'path' => 'en/home'],
            isHomepage: true
        );

        $crumb = $svc->build('home');

        $this->assertCount(1, $crumb, 'the homepage breadcrumb is just Home');
        $this->assertSame('home', $crumb[0]['id']);
        $this->assertTrue($crumb[0]['current']);
        $this->assertNull($crumb[0]['url'], 'the current Home entry is not clickable');
    }

    public function testHomeLabelComesFromNavigationJsonFirstItem(): void {
        $svc = $this->makeService(
            ['uniqueId' => 'page-home', 'title' => 'x', 'path' => 'en/home'],
            navItems: [['title' => 'Start', 'uniqueId' => 'page-home']],
            isHomepage: true
        );

        $crumb = $svc->build('home');

        $this->assertSame('Start', $crumb[0]['title'], 'the Home label is the first nav item title');
    }

    public function testCorruptOrMissingNavigationFallsBackToHomeLabel(): void {
        $svc = $this->makeService(
            ['uniqueId' => 'page-home', 'title' => 'x', 'path' => 'en/home'],
            navItems: null, // no navigation.json
            isHomepage: true
        );

        $crumb = $svc->build('home');

        $this->assertSame('Home', $crumb[0]['title'], "the label falls back to 'Home'");
    }

    public function testDeepPageBuildsHomePlusCurrentAndSkipsTheLanguageSegment(): void {
        $svc = $this->makeService(
            ['uniqueId' => 'page-camp', 'title' => 'Campaigns', 'path' => 'en/marketing/campaigns'],
            parents: [
                'en/marketing' => ['uniqueId' => 'page-mkt', 'title' => 'Marketing', 'path' => 'en/marketing'],
            ]
        );

        $crumb = $svc->build('page-camp');

        // Home, Marketing (parent), Campaigns (current). The 'en' segment is skipped.
        $this->assertCount(3, $crumb);
        $this->assertSame('home', $crumb[0]['id']);
        $this->assertFalse($crumb[0]['current']);
        $this->assertSame('#home', $crumb[0]['url'], 'Home is clickable when not on the homepage');

        $this->assertSame('Marketing', $crumb[1]['title']);
        $this->assertSame('#page-mkt', $crumb[1]['url']);
        $this->assertFalse($crumb[1]['current']);

        $this->assertSame('Campaigns', $crumb[2]['title']);
        $this->assertTrue($crumb[2]['current']);
        $this->assertNull($crumb[2]['url'], 'the current page is not clickable');
    }

    public function testAnUnresolvedParentFolderUsesAHumanisedFolderName(): void {
        $svc = $this->makeService(
            ['uniqueId' => 'page-x', 'title' => 'X', 'path' => 'en/some-dept/x'],
            parents: [] // no parent page for 'en/some-dept'
        );

        $crumb = $svc->build('page-x');

        $this->assertSame('Some dept', $crumb[1]['title'], 'the folder name is humanised as a fallback');
        $this->assertNull($crumb[1]['url'], 'an unresolved parent is not clickable');
        $this->assertNull($crumb[1]['uniqueId']);
    }
}
