<?php
declare(strict_types=1);

namespace OCA\IntraVox\Tests\Unit\Service\Sanitize;

use OCA\IntraVox\Service\NavigationService;
use OCA\IntraVox\Service\Sanitize\ColorSanitizer;
use OCA\IntraVox\Service\Sanitize\HtmlSanitizer;
use OCA\IntraVox\Service\Sanitize\PageShapeSanitizer;
use OCA\IntraVox\Service\Sanitize\UrlSanitizer;
use OCA\IntraVox\Service\Sanitize\WorkspaceMetadata;
use OCA\IntraVox\Tests\Unit\Service\Harness\EnglishL10n;
use OCP\IConfig;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class WorkspaceMetadataTest extends TestCase {
    private function sanitizer(): PageShapeSanitizer {
        return new PageShapeSanitizer(
            $this->createMock(IConfig::class), $this->createMock(LoggerInterface::class),
            new HtmlSanitizer(), new UrlSanitizer(), new ColorSanitizer(), new EnglishL10n()
        );
    }

    private function page(array $widgets): array {
        return [
            'title' => 'Sovereign', 'hidePageTitleBar' => true,
            'layout' => ['columns' => 1, 'rows' => [['columns' => 1, 'widgets' => $widgets]]],
        ];
    }

    public function testOrganogramCreateEditReloadAndDelete(): void {
        $service = $this->sanitizer();
        $page = $service->validateAndSanitizePage($this->page([[
            'type' => 'organogram', 'id' => 'org-1', 'column' => 1, 'order' => 0,
            'title' => '<b>Operating model</b>', 'defaultExpanded' => false,
            'showCompanies' => true, 'unsafeHtml' => '<script>alert(1)</script>',
        ]]));
        $widget = $page['layout']['rows'][0]['widgets'][0];
        $this->assertSame('organogram', $widget['type']);
        $this->assertSame('Operating model', $widget['title']);
        $this->assertFalse($widget['defaultExpanded']);
        $this->assertTrue($widget['showCompanies']);
        $this->assertArrayNotHasKey('unsafeHtml', $widget);
        $this->assertTrue($page['hidePageTitleBar']);
        $page['layout']['rows'][0]['widgets'][0]['title'] = 'Updated model';
        $reloaded = $service->validateAndSanitizePage(json_decode(json_encode($page), true));
        $this->assertSame('Updated model', $reloaded['layout']['rows'][0]['widgets'][0]['title']);
        $this->assertFalse($reloaded['layout']['rows'][0]['widgets'][0]['defaultExpanded']);
        $reloaded['layout']['rows'][0]['widgets'] = [];
        $deleted = $service->validateAndSanitizePage($reloaded);
        $this->assertSame([], $deleted['layout']['rows']);
    }

    public function testLinkMetadataSurvivesRepeatedSaves(): void {
        $service = $this->sanitizer();
        $page = $this->page([['type' => 'links', 'items' => [
            ['title' => 'Cloud', 'url' => '/cloud', 'entity' => 'cloud-carib', 'excomOnly' => false],
            ['title' => 'Board', 'url' => '/board', 'entity' => 'excom', 'excomOnly' => true],
            ['title' => 'Legacy', 'url' => '/shared'],
        ]]]);
        for ($i = 0; $i < 2; $i++) {
            $page = $service->validateAndSanitizePage($page);
        }
        $items = $page['layout']['rows'][0]['widgets'][0]['items'];
        $this->assertSame('cloud-carib', $items[0]['entity']);
        $this->assertTrue($items[1]['excomOnly']);
        $this->assertSame('all', $items[2]['entity']);
        $this->assertFalse($items[2]['excomOnly']);
    }

    public function testPortalSettingsAndLaunchActionsSurviveRepeatedSaves(): void {
        $page = $this->page([['type' => 'links', 'items' => [
            ['title' => 'Mail', 'url' => '/index.php/apps/mail/', 'appId' => 'mail'],
            ['title' => 'Search', 'url' => '#agl-global-search', 'action' => 'global-search'],
            ['title' => 'Invalid', 'url' => '/safe', 'action' => 'javascript', 'appId' => 'not-an-app'],
            ['title' => 'Matrix', 'url' => '#organogram-matrix', 'action' => 'organogram-matrix', 'excomOnly' => true],
            ['title' => 'Tables & Forms', 'url' => '/index.php/apps/tables/', 'appId' => 'tables',
                'fallbackAppId' => 'forms', 'fallbackUrl' => 'javascript:alert(1)'],
        ]]]);
        $page += ['disableSocial' => true, 'entity' => 'athena-cloud', 'excomOnly' => false,
            'settings' => ['allowComments' => null, 'allowReactions' => true, 'allowCommentReactions' => false]];
        for ($i = 0; $i < 2; $i++) {
            $page = $this->sanitizer()->validateAndSanitizePage($page);
        }
        $this->assertTrue($page['disableSocial']);
        $this->assertSame('athena-cloud', $page['entity']);
        $this->assertNull($page['settings']['allowComments']);
        $this->assertTrue($page['settings']['allowReactions']);
        $this->assertFalse($page['settings']['allowCommentReactions']);
        $links = $page['layout']['rows'][0]['widgets'][0]['items'];
        $this->assertSame('mail', $links[0]['appId']);
        $this->assertSame('global-search', $links[1]['action']);
        $this->assertArrayNotHasKey('appId', $links[2]);
        $this->assertArrayNotHasKey('action', $links[2]);
        $this->assertSame('organogram-matrix', $links[3]['action']);
        $this->assertTrue($links[3]['excomOnly']);
        $this->assertSame('forms', $links[4]['fallbackAppId']);
        $this->assertSame('/index.php/apps/forms/', $links[4]['fallbackUrl']);
    }

    public function testMetadataRejectsMalformedValuesWithoutGrantingPermissions(): void {
        $this->assertSame(['entity' => 'all', 'excomOnly' => false],
            WorkspaceMetadata::item(['entity' => ['excom'], 'excomOnly' => 'true']));
        $this->assertSame(['title' => '', 'defaultExpanded' => false, 'showCompanies' => false],
            WorkspaceMetadata::organogram(['title' => [], 'defaultExpanded' => 'true', 'showCompanies' => 1]));
        $page = $this->page([]);
        $page['hidePageTitleBar'] = 'false';
        $this->assertArrayNotHasKey('hidePageTitleBar', $this->sanitizer()->validateAndSanitizePage($page));
    }

    public function testNestedNavigationRetainsMetadataAndItsUrlAllowlist(): void {
        $service = (new \ReflectionClass(NavigationService::class))->newInstanceWithoutConstructor();
        (new \ReflectionProperty(NavigationService::class, 'urlSanitizer'))->setValue($service, new UrlSanitizer());
        $method = new \ReflectionMethod(NavigationService::class, 'validateNavigationItems');
        $items = [['title' => 'Parent', 'entity' => 'athena-cloud', 'children' => [
            ['title' => 'Private', 'entity' => 'excom', 'excomOnly' => true, 'url' => 'javascript:alert(1)'],
        ]]];
        $first = $method->invoke($service, $items, 1);
        $reloaded = $method->invoke($service, $first, 1);
        $this->assertSame('athena-cloud', $reloaded[0]['entity']);
        $this->assertSame('excom', $reloaded[0]['children'][0]['entity']);
        $this->assertTrue($reloaded[0]['children'][0]['excomOnly']);
        $this->assertNull($reloaded[0]['children'][0]['url']);
    }
}
