<?php
declare(strict_types=1);

namespace OCA\IntraVox\Tests\Unit\Service;

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

        $pages = $this->lister($en)->listAllWithContent();
        $ids = array_column($pages, 'uniqueId');

        $this->assertContains('page-real', $ids, 'the real page is listed');
        $this->assertNotContains('template-kb', $ids, 'template pages must never be served as pages');
        $this->assertNotContains('page-library', $ids, 'resource-library files are not pages');
    }

    /**
     * Robustness (LISTING carve #9 review): a filesystem node literally named
     * `home.json` that is a DIRECTORY, not a file, must not crash the listing. The
     * pre-carve listPages called getContent() on it (fatal PHP Error on a Folder,
     * not caught by catch(NotFoundException)); PageLister's instanceof-File guard
     * now skips it cleanly and still serves the real pages.
     */
    public function testDirectoryNamedHomeJsonDoesNotCrashTheListing(): void {
        $realPage = $this->makeFolder('/IntraVox/en/about', [
            'about.json' => $this->makeFile('/IntraVox/en/about/about.json',
                ['uniqueId' => 'page-real', 'title' => 'About']),
        ]);
        // home.json exists at the root but is a FOLDER, not a File.
        $en = $this->makeFolder('/IntraVox/en', [
            'home.json' => $this->makeFolder('/IntraVox/en/home.json', []),
            'about' => $realPage,
        ]);

        $pages = $this->lister($en)->listAll();
        $ids = array_column($pages, 'uniqueId');

        $this->assertContains('page-real', $ids, 'real pages are still listed');
        $this->assertNotContains('home.json', $ids, 'a directory named home.json is not a page');
    }

    /**
     * Build the real PageLister over $en (fase-5: the listing walk is
     * PageLister's, and it is now DI-buildable with a closure-free ctor, so this
     * drives it directly instead of reflecting through a PageService subclass).
     * The index service reports no entries so both listAll and listAllWithContent
     * take the filesystem-walk path this test exercises. The sanitizer is real (a
     * mock would return null and pass for the wrong reason).
     */
    private function lister(Folder $en): \OCA\IntraVox\Service\Listing\PageLister {
        $index = $this->createMock(\OCA\IntraVox\Service\PageIndexService::class);
        $index->method('hasEntries')->willReturn(false); // force the walk, not fromIndex
        $locator = new \OCA\IntraVox\Service\Locator\PageLocator(
            $index,
            $this->createMock(\Psr\Log\LoggerInterface::class)
        );
        $folders = new \OCA\IntraVox\Service\Folder\FolderContext(
            $this->createMock(\OCP\Files\IRootFolder::class),
            'tester',
            $this->createMock(\OCP\IConfig::class),
            $this->createMock(\OCA\IntraVox\Service\LanguageService::class),
            new \OCA\IntraVox\Service\Language\LanguageResolver(),
            $locator,
            $en,                    // intraVoxOverride
            fn(): Folder => $en     // readLanguageFolder seam
        );
        $shape = new \OCA\IntraVox\Service\Sanitize\PageShapeSanitizer(
            $this->createMock(\OCP\IConfig::class),
            $this->createMock(\Psr\Log\LoggerInterface::class),
            new \OCA\IntraVox\Service\Sanitize\HtmlSanitizer(),
            new \OCA\IntraVox\Service\Sanitize\UrlSanitizer(),
            new \OCA\IntraVox\Service\Sanitize\ColorSanitizer(),
        );
        return new \OCA\IntraVox\Service\Listing\PageLister(
            $locator,
            $index,
            $this->createMock(\OCA\IntraVox\Service\PermissionService::class),
            $this->createMock(\Psr\Log\LoggerInterface::class),
            $folders,
            $shape,
            $this->createMock(\OCA\IntraVox\Service\Cache\PageCacheService::class),
            // Enricher is only reached by byFolderPath, not the walks under test;
            // an inert real instance satisfies the closure-free ctor.
            new \OCA\IntraVox\Service\Path\PageDataEnricher(
                new \OCA\IntraVox\Service\Path\PagePathHelper(),
                $this->createMock(\OCA\IntraVox\Service\PermissionService::class),
                $this->createMock(\OCA\IntraVox\Service\Publication\MetaVoxGateway::class),
                $folders,
                $this->createMock(\OCA\IntraVox\Service\Translation\TranslationGroupService::class),
                new \OCA\IntraVox\Service\Util\GroupfolderResolver(),
            ),
        );
    }
}
