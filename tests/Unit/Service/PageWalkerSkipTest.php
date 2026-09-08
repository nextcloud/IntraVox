<?php
declare(strict_types=1);

namespace OCA\IntraVox\Tests\Unit\Service;

use OCA\IntraVox\Service\PageService;
use OCA\IntraVox\Service\Path\PagePathHelper;
use OCP\Files\File;
use OCP\Files\FileInfo;
use OCP\Files\Folder;
use PHPUnit\Framework\TestCase;

/**
 * One folder-skip rule for every tree walker (#96).
 *
 * The rule used to be hand-copied in thirteen places with different lists,
 * and each divergence was a live bug: search returned the "Knowledge Base"
 * TEMPLATE above the real page because the search walker's list lacked
 * `_templates`. The fix also went missing once without any test noticing —
 * it had only been verified live on dev. Hence this net: the helper's
 * contract, plus the walker most likely to leak (search's), pinned.
 */
class PageWalkerSkipTest extends TestCase {

    /** The helper is THE rule; every walker delegates to it. */
    public function testInfrastructureFolderRule(): void {
        foreach (['_media', '_resources', '_templates', '_versions', '.nomedia', '.git', 'images', 'files'] as $name) {
            $this->assertTrue(PagePathHelper::isInfrastructureFolder($name), "$name is infrastructure");
        }
        foreach (['about', 'files-2', 'documentatie', 'x_media', 'nieuws.archief'] as $name) {
            $this->assertFalse(PagePathHelper::isInfrastructureFolder($name), "$name can hold pages");
        }
    }

    private function makeFile(string $path, array $json): File {
        $file = $this->createMock(File::class);
        $file->method('getName')->willReturn(basename($path));
        $file->method('getType')->willReturn(FileInfo::TYPE_FILE);
        $file->method('getPath')->willReturn($path);
        $file->method('getId')->willReturn(abs(crc32($path)));
        $file->method('isReadable')->willReturn(true);
        $file->method('getContent')->willReturn(json_encode($json));
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
     * The search walker must not serve template or resource-library pages.
     * This is the walker whose missing `_templates` skip shipped the
     * template-above-the-real-page search result.
     */
    public function testSearchWalkerSkipsTemplatesAndResources(): void {
        $en = $this->makeFolder('/IntraVox/en', [
            'about' => $this->makeFolder('/IntraVox/en/about', [
                'about.json' => $this->makeFile('/IntraVox/en/about/about.json',
                    ['uniqueId' => 'page-real', 'title' => 'About']),
            ]),
            '_templates' => $this->makeFolder('/IntraVox/en/_templates', [
                'kb' => $this->makeFolder('/IntraVox/en/_templates/kb', [
                    'kb.json' => $this->makeFile('/IntraVox/en/_templates/kb/kb.json',
                        ['uniqueId' => 'template-kb', 'title' => 'Knowledge Base']),
                ]),
            ]),
            '_resources' => $this->makeFolder('/IntraVox/en/_resources', [
                'lib' => $this->makeFolder('/IntraVox/en/_resources/lib', [
                    'lib.json' => $this->makeFile('/IntraVox/en/_resources/lib/lib.json',
                        ['uniqueId' => 'page-library', 'title' => 'Library']),
                ]),
            ]),
        ]);

        // listPagesWithContent resolves its folder via folders()->readLanguageFolder
        // and never runs a cross-language locate, so the seam override goes away
        // entirely — a FolderContext whose readLanguageFolder seam returns $en
        // covers it. (This file has its own fixture helpers, not the shared
        // harness, so the context is built inline rather than via fakeFolderContext.)
        $svc = new class extends PageService {
            public function __construct() {
            }
        };
        $folderContext = new \OCA\IntraVox\Service\Folder\FolderContext(
            fn() => $en,
            fn(): string => 'en',
            fn(): string => 'en',
            fn(Folder $f): bool => false,
            new \OCA\IntraVox\Service\Language\LanguageResolver(),
            new \OCA\IntraVox\Service\Locator\PageLocator(
                $this->createMock(\OCA\IntraVox\Service\PageIndexService::class),
                $this->createMock(\Psr\Log\LoggerInterface::class)
            ),
            fn(): Folder => $en
        );
        (new \ReflectionProperty(PageService::class, 'folderContext'))
            ->setValue($svc, $folderContext);
        (new \ReflectionProperty(PageService::class, 'logger'))
            ->setValue($svc, $this->createMock(\Psr\Log\LoggerInterface::class));
        // listPagesWithContent now routes through pageLister(), whose accessor
        // eagerly reads these two — set them (the content walk itself never calls
        // permissionService, but the lister is constructed regardless).
        (new \ReflectionProperty(PageService::class, 'permissionService'))
            ->setValue($svc, $this->createMock(\OCA\IntraVox\Service\PermissionService::class));
        (new \ReflectionProperty(PageService::class, 'pageIndexService'))
            ->setValue($svc, $this->createMock(\OCA\IntraVox\Service\PageIndexService::class));
        (new \ReflectionProperty(PageService::class, 'pageLocator'))
            ->setValue($svc, new \OCA\IntraVox\Service\Locator\PageLocator(
                $this->createMock(\OCA\IntraVox\Service\PageIndexService::class),
                $this->createMock(\Psr\Log\LoggerInterface::class)
            ));
        // The walker sanitizes every page it serves; a mock would return null
        // and make the assertions pass for the wrong reason.
        (new \ReflectionProperty(PageService::class, 'shapeSanitizer'))
            ->setValue($svc, new \OCA\IntraVox\Service\Sanitize\PageShapeSanitizer(
                $this->createMock(\OCP\IConfig::class),
                $this->createMock(\Psr\Log\LoggerInterface::class),
                new \OCA\IntraVox\Service\Sanitize\HtmlSanitizer(),
                new \OCA\IntraVox\Service\Sanitize\UrlSanitizer(),
                new \OCA\IntraVox\Service\Sanitize\ColorSanitizer(),
            ));

        $pages = $svc->listPagesWithContent();
        $ids = array_column($pages, 'uniqueId');

        $this->assertContains('page-real', $ids, 'the real page is listed');
        $this->assertNotContains('template-kb', $ids, 'template pages must never be served as pages');
        $this->assertNotContains('page-library', $ids, 'resource-library files are not pages');
    }
}
