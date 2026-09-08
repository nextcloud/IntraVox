<?php
declare(strict_types=1);

namespace OCA\IntraVox\Tests\Unit\Service;

use OCA\IntraVox\Service\LanguageService;
use OCA\IntraVox\Service\PageService;
use OCA\IntraVox\Tests\Unit\Service\Harness\BuildsPageService;
use OCP\Files\Folder;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Characterizes getBreadcrumb() before it moves into Path/BreadcrumbBuilder in
 * Phase 5. getPage() is stubbed on the subclass (it is public and already pinned
 * by PageCrudReadTest) so the breadcrumb-assembly logic is exercised in
 * isolation: the always-present Home entry, its label sourced from
 * navigation.json, the homepage short-circuit, the language-segment skip and the
 * current-page marking.
 */
class PageBreadcrumbTest extends TestCase {

    use BuildsPageService;

    /**
     * @param array $currentPage what getPage() returns for the requested id
     * @param array|null $navItems items array for navigation.json (null = no file)
     * @param bool $isHomepage what the isHomepage() seam reports
     * @param array<string,array> $parents folderPath => parent page for findPageByFolderPath
     */
    private function makeService(
        array $currentPage,
        ?array $navItems = null,
        bool $isHomepage = false,
        array $parents = []
    ): PageService {
        $langFolder = $this->createMock(Folder::class);
        $langFolder->method('nodeExists')->willReturnCallback(fn($n) => $n === 'navigation.json' && $navItems !== null);
        if ($navItems !== null) {
            $navFile = $this->makeFile('/IntraVox/en/navigation.json', ['items' => $navItems]);
            $langFolder->method('get')->willReturn($navFile);
        }

        // getBreadcrumb reads the folder via folders()->readLanguageFolder()
        // ($langFolder) and userLanguage ('en'); getIntraVoxFolder stays THROWING
        // for the findPageByFolderPath cross-language walk (rootClosure()), which
        // pins the humanised-folder degrade fallback. getPage + isHomepage stay
        // overridden (unrelated to folders).
        $svc = new class($currentPage, $isHomepage) extends PageService {
            private array $currentPage;
            private bool $home;
            public function __construct(array $currentPage, bool $home) {
                $this->currentPage = $currentPage;
                $this->home = $home;
            }
            public function getPage(string $id): array {
                return $this->currentPage;
            }
            public function isHomepage(string $uniqueId, ?string $language = null): bool {
                return $this->home;
            }
        };

        $config = $this->createMock(\OCP\IConfig::class);
        $config->method('getUserValue')->willReturn('en');

        $language = $this->createMock(LanguageService::class);
        $language->method('isLanguageAvailable')->willReturnCallback(
            fn(string $c) => in_array($c, ['en', 'de', 'nl'], true)
        );

        // findPageByFolderPath (private, so not overridable) short-circuits on the
        // request cache before it touches getIntraVoxFolder(). Feeding the parent
        // map through the cache resolves parents without a folder fixture; an
        // absent path is a cache MISS which surfaces as null (getIntraVoxFolder
        // throws in this fixture -> caught -> humanised-folder fallback).
        $cache = $this->createMock(\OCA\IntraVox\Service\Cache\PageCacheService::class);
        $cache->method('hasFolderPath')->willReturnCallback(fn($p) => isset($parents[$p]));
        $cache->method('getFolderPath')->willReturnCallback(fn($p) => $parents[$p] ?? null);

        $this->injectPageServiceDependencies($svc, [
            'config' => $config,
            'userId' => 'tester',
            'languageService' => $language,
            'cache' => $cache,
            'userSession' => $this->createMock(\OCP\IUserSession::class),
            'logger' => $this->createMock(LoggerInterface::class),
            'folderContext' => $this->fakeFolderContext(
                readLanguageFolder: $langFolder,
                userLanguage: 'en'
            ),
        ]);

        return $svc;
    }

    public function testHomePageReturnsOnlyTheHomeEntryMarkedCurrent(): void {
        $svc = $this->makeService(
            ['uniqueId' => 'page-home', 'title' => 'Welcome', 'path' => 'en/home'],
            isHomepage: true
        );

        $crumb = $svc->getBreadcrumb('home');

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

        $crumb = $svc->getBreadcrumb('home');

        $this->assertSame('Start', $crumb[0]['title'], 'the Home label is the first nav item title');
    }

    public function testCorruptOrMissingNavigationFallsBackToHomeLabel(): void {
        $svc = $this->makeService(
            ['uniqueId' => 'page-home', 'title' => 'x', 'path' => 'en/home'],
            navItems: null, // no navigation.json
            isHomepage: true
        );

        $crumb = $svc->getBreadcrumb('home');

        $this->assertSame('Home', $crumb[0]['title'], "the label falls back to 'Home'");
    }

    public function testDeepPageBuildsHomePlusCurrentAndSkipsTheLanguageSegment(): void {
        $svc = $this->makeService(
            ['uniqueId' => 'page-camp', 'title' => 'Campaigns', 'path' => 'en/marketing/campaigns'],
            parents: [
                'en/marketing' => ['uniqueId' => 'page-mkt', 'title' => 'Marketing', 'path' => 'en/marketing'],
            ]
        );

        $crumb = $svc->getBreadcrumb('page-camp');

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

        $crumb = $svc->getBreadcrumb('page-x');

        $this->assertSame('Some dept', $crumb[1]['title'], 'the folder name is humanised as a fallback');
        $this->assertNull($crumb[1]['url'], 'an unresolved parent is not clickable');
        $this->assertNull($crumb[1]['uniqueId']);
    }
}
