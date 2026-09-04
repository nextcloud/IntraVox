<?php
declare(strict_types=1);

namespace OCA\IntraVox\Tests\Unit\Service\Path;

use OCA\IntraVox\Service\LanguageService;
use OCA\IntraVox\Service\Path\BreadcrumbBuilder;
use OCP\Files\Folder;
use PHPUnit\Framework\TestCase;

/**
 * Direct tests for BreadcrumbBuilder (Phase 5.2). PageBreadcrumbTest already pins
 * the behaviour end-to-end through PageService's getBreadcrumb facade; this drives
 * the builder standalone (page/language/homepage-flag/folder/parent-resolver passed
 * in) so it stays covered independently and confirms the parameter contract.
 */
class BreadcrumbBuilderTest extends TestCase {

    private function builder(): BreadcrumbBuilder {
        $language = $this->createMock(LanguageService::class);
        $language->method('isLanguageAvailable')->willReturnCallback(
            fn(string $c) => in_array($c, ['en', 'de', 'nl'], true)
        );
        return new BreadcrumbBuilder($language);
    }

    /** @param callable(string):?array $findParent */
    private function build(array $page, bool $pointer, callable $findParent, ?Folder $folder = null, string $pageId = 'p'): array {
        return $this->builder()->build($pageId, $page, 'en', $pointer, $folder, $findParent);
    }

    public function testHomepagePointerYieldsOnlyHomeMarkedCurrent(): void {
        $crumb = $this->build(
            ['uniqueId' => 'page-home', 'title' => 'Welcome', 'path' => 'en/home'],
            pointer: true,
            findParent: fn() => null
        );

        $this->assertCount(1, $crumb);
        $this->assertSame('home', $crumb[0]['id']);
        $this->assertTrue($crumb[0]['current']);
        $this->assertNull($crumb[0]['url']);
    }

    public function testDeepPageBuildsHomeParentCurrentAndSkipsLanguage(): void {
        $crumb = $this->build(
            ['uniqueId' => 'page-camp', 'title' => 'Campaigns', 'path' => 'en/marketing/campaigns'],
            pointer: false,
            findParent: fn(string $path) => $path === 'en/marketing'
                ? ['uniqueId' => 'page-mkt', 'title' => 'Marketing', 'path' => 'en/marketing']
                : null,
            pageId: 'page-camp'
        );

        $this->assertCount(3, $crumb);
        $this->assertSame('home', $crumb[0]['id']);
        $this->assertSame('#home', $crumb[0]['url']);
        $this->assertSame('Marketing', $crumb[1]['title']);
        $this->assertSame('#page-mkt', $crumb[1]['url']);
        $this->assertSame('Campaigns', $crumb[2]['title']);
        $this->assertTrue($crumb[2]['current']);
        $this->assertNull($crumb[2]['url']);
    }

    public function testUnresolvedParentIsHumanisedAndNotClickable(): void {
        $crumb = $this->build(
            ['uniqueId' => 'page-x', 'title' => 'X', 'path' => 'en/some-dept/x'],
            pointer: false,
            findParent: fn() => null,
            pageId: 'page-x'
        );

        $this->assertSame('Some dept', $crumb[1]['title']);
        $this->assertNull($crumb[1]['url']);
        $this->assertNull($crumb[1]['uniqueId']);
    }

    public function testHomeLabelComesFromNavigationJson(): void {
        $navFile = $this->createMock(\OCP\Files\File::class);
        $navFile->method('getContent')->willReturn(json_encode([
            'items' => [['title' => 'Start', 'uniqueId' => 'page-home']],
        ]));
        $folder = $this->createMock(Folder::class);
        $folder->method('nodeExists')->willReturn(true);
        $folder->method('get')->willReturn($navFile);

        $crumb = $this->build(
            ['uniqueId' => 'page-home', 'title' => 'x', 'path' => 'en/home'],
            pointer: true,
            findParent: fn() => null,
            folder: $folder,
            pageId: 'home'
        );

        $this->assertSame('Start', $crumb[0]['title']);
    }

    public function testNoFolderFallsBackToHomeLabel(): void {
        $crumb = $this->build(
            ['uniqueId' => 'page-home', 'title' => 'x', 'path' => 'en/home'],
            pointer: true,
            findParent: fn() => null,
            folder: null,
            pageId: 'home'
        );

        $this->assertSame('Home', $crumb[0]['title']);
    }
}
