<?php
declare(strict_types=1);

namespace OCA\IntraVox\Tests\Unit\Service;

use OCA\IntraVox\Service\GroupFolders\GroupFoldersGateway;
use OCA\IntraVox\Service\SovereignBaseline;
use OCA\IntraVox\Service\SovereignBaselineInstaller;
use OCP\Files\File;
use OCP\Files\Folder;
use OCP\Files\NotFoundException;
use PHPUnit\Framework\TestCase;

class SovereignBaselineTest extends TestCase {
    public function testHomeAliasPreservesOldBookmarksAndOnlyTargetsLanguageRoot(): void {
        $file = $this->createMock(File::class);
        $file->method('getContent')->willReturn('{"uniqueId":"page-old-home","title":"Home"}');
        $root = $this->createMock(Folder::class);
        $root->method('get')->with('home.json')->willReturn($file);
        $root->method('getPath')->willReturn('/en');
        $child = $this->createMock(Folder::class);
        $child->method('get')->with('home.json')->willReturn($file);
        $child->method('getPath')->willReturn('/en/child');
        $child->method('getDirectoryListing')->willReturn([]);
        $locator = new \OCA\IntraVox\Service\Locator\PageLocator(
            $this->createMock(\OCA\IntraVox\Service\PageIndexService::class),
            $this->createMock(\Psr\Log\LoggerInterface::class)
        );
        $this->assertSame($file, $locator->findPageByUniqueId($root, 'page-home-en')['file']);
        $this->assertSame($file, $locator->findPageByUniqueId($root, 'page-old-home')['file']);
        $this->assertNull($locator->findPageByUniqueId($child, 'page-home-en', $root));
    }

    public function testCompleteBaselineAndNoBrokenPageReferences(): void {
        $docs = (new SovereignBaseline())->documents();
        $this->assertCount(16, $docs);
        $home = $docs['home.json'];
        $this->assertSame('page-home-en', $home['uniqueId']);
        $this->assertTrue($home['disableSocial']);
        $this->assertTrue($home['hidePageTitleBar']);
        $this->assertCount(3, $home['layout']['rows']);
        $this->assertSame('organogram', $home['layout']['rows'][2]['widgets'][0]['type']);
        $this->assertSame(3, $home['layout']['rows'][0]['widgets'][2]['columns']);
        $apps = $home['layout']['rows'][1]['widgets'][1]['items'];
        $offices = $home['layout']['rows'][1]['widgets'][3]['items'];
        $this->assertCount(8, $apps);
        $this->assertCount(8, $offices);
        $this->assertCount(6, array_filter($offices, static fn(array $item): bool => !$item['excomOnly']));
        $this->assertSame('Tables & Forms', $apps[6]['title']);
        $this->assertSame('forms', $apps[6]['fallbackAppId']);
        $this->assertSame('global-search', $apps[7]['action']);
        $this->assertSame('organogram-matrix', $offices[7]['action']);
        $this->assertSame('#organogram-matrix', $offices[7]['url']);
        foreach ($offices as $office) {
            $this->assertNotEmpty($office['text']);
        }
        $ids = array_column($docs, 'uniqueId');
        $walk = function (array $node) use (&$walk, $ids): void {
            if (isset($node['uniqueId'])) {
                $this->assertContains($node['uniqueId'], $ids);
            }
            foreach ($node as $value) {
                if (is_array($value)) {
                    $walk($value);
                }
            }
        };
        $walk($docs['navigation.json']);
        $walk($home);
        $this->assertContains(SovereignBaseline::NASSAU_ID, $ids);
        $this->assertContains(SovereignBaseline::ATHENS_ID, $ids);
        $this->assertSame(SovereignBaseline::COMPLIANCE, substr($docs['footer.json']['content'], strpos($docs['footer.json']['content'], 'EU GDPR')));
        $this->assertTrue($docs['sovereign-excom/sovereign-excom.json']['excomOnly']);
        foreach (['group' => 'all', 'caribbean' => 'cloud-carib', 'emea' => 'athena-cloud'] as $slug => $entity) {
            $this->assertSame($entity, $docs["sovereign-news/$slug/$slug.json"]['entity']);
            $this->assertStringNotContainsString('Sample announcement', json_encode($docs["sovereign-news/$slug/$slug.json"]));
            $this->assertStringNotContainsString('No audit completion', json_encode($docs["sovereign-news/$slug/$slug.json"]));
        }
        $this->assertStringNotContainsString('\\n', json_decode(json_encode($home), true)['layout']['rows'][0]['widgets'][1]['content']);
    }

    public function testInstallerIsIdempotentPreservesIdentityAndOtherContent(): void {
        $files = ['en/home.json' => '{"uniqueId":"page-existing-home","title":"Old"}', 'en/keep.json' => '{"title":"Keep me"}'];
        $folders = [];
        $nextId = 400;
        $makeFolder = function (string $path) use (&$makeFolder, &$files, &$folders, &$nextId): Folder {
            if (isset($folders[$path])) {
                return $folders[$path];
            }
            $folder = $this->createMock(Folder::class);
            $folder->method('getId')->willReturn($nextId++);
            $file = function (string $name) use (&$files, $path): File {
                $key = "$path/$name";
                $node = $this->createMock(File::class);
                $node->method('getContent')->willReturnCallback(fn() => $files[$key]);
                $node->method('putContent')->willReturnCallback(function ($content) use (&$files, $key): void {
                    $files[$key] = $content;
                });
                return $node;
            };
            $folder->method('get')->willReturnCallback(function ($name) use (&$files, &$folders, $path, $file) {
                if (isset($folders["$path/$name"])) {
                    return $folders["$path/$name"];
                }
                if (isset($files["$path/$name"])) {
                    return $file($name);
                }
                throw new NotFoundException();
            });
            $folder->method('newFolder')->willReturnCallback(fn($name) => $makeFolder("$path/$name"));
            $folder->method('newFile')->willReturnCallback(function ($name) use (&$files, $path, $file) {
                $files["$path/$name"] = '';
                return $file($name);
            });
            return $folders[$path] = $folder;
        };
        $gateway = $this->createMock(GroupFoldersGateway::class);
        // The security hook must run before either installation writes content.
        $gateway->expects($this->exactly(2))->method('protectExecutiveFolder')->with(1, $this->isType('int'));
        $installer = new SovereignBaselineInstaller($gateway);
        $root = $makeFolder('en');
        $this->assertSame(16, $installer->install($root, 1, true));
        $snapshot = $files;
        $this->assertSame(0, $installer->install($root, 1, true));
        $this->assertSame($snapshot, $files);
        $this->assertSame('page-existing-home', json_decode($files['en/home.json'], true)['uniqueId']);
        $this->assertSame('{"title":"Keep me"}', $files['en/keep.json']);
        $files['en/navigation.json'] = '{"items":["owner-edited"]}';
        $files['en/home.json'] = '{"uniqueId":"page-existing-home","title":"Refresh target"}';
        $this->assertSame(1, $installer->install($root, 1, true, SovereignBaseline::FINAL_UX_PATHS));
        $this->assertSame('{"items":["owner-edited"]}', $files['en/navigation.json']);
        $this->assertSame(0, $installer->install($root, 1, true, SovereignBaseline::FINAL_UX_PATHS));
    }
}
