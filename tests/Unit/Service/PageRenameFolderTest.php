<?php
declare(strict_types=1);

namespace OCA\IntraVox\Tests\Unit\Service;

use OCA\IntraVox\Service\Folder\FolderContext;
use OCA\IntraVox\Service\Language\LanguageResolver;
use OCA\IntraVox\Service\Locator\PageLocator;
use OCA\IntraVox\Service\Metadata\PageMetadataService;
use OCA\IntraVox\Service\PageIndexService;
use OCA\IntraVox\Service\Sanitize\PageShapeSanitizer;
use OCA\IntraVox\Service\Util\PageIdUtils;
use OCA\IntraVox\Tests\Unit\Service\Harness\BuildsPageService;
use OCP\Files\File;
use OCP\Files\Folder;
use PHPUnit\Framework\TestCase;

/**
 * Folder rename on page rename (#95).
 *
 * The invariant under test: a page's folder and its `.json` are renamed as
 * a PAIR or not at all. A folder whose JSON carries another base name is
 * exactly the mismatch the index rebuild skips as "not a page", so a half
 * rename would make the page vanish from the index. Hence the rollback
 * test is the one that matters most here.
 *
 * renamePageFolder now lives in Metadata/PageMetadataService (private); this test
 * builds that service directly and drives it by reflection, with isHomepage and
 * clearCache passed as the closures the domain receives per call.
 */
class PageRenameFolderTest extends TestCase {

    use BuildsPageService;

    /** Records clearCache() calls so a rename's cache-clear is observable. */
    private int $clearCacheCalls = 0;
    /** The isHomepage answer the rename() closure returns for this fixture. */
    private bool $isHomepageAnswer = false;

    private function makeService(bool $isHomepage, PageIndexService $index): PageMetadataService {
        $this->clearCacheCalls = 0;
        $this->isHomepageAnswer = $isHomepage;
        // renamePageFolder touches only idUtils/pageIndexService/logger + the
        // isHomepage closure; the other ctor deps are irrelevant here, so they are
        // plain mocks / a bare FolderContext.
        $locator = new PageLocator(
            $this->createMock(PageIndexService::class),
            $this->createMock(\Psr\Log\LoggerInterface::class)
        );
        $folders = new FolderContext(
            $this->createMock(\OCP\Files\IRootFolder::class),
            'tester',
            $this->createMock(\OCP\IConfig::class),
            $this->createMock(\OCA\IntraVox\Service\LanguageService::class),
            new LanguageResolver(),
            $locator,
            $this->createMock(Folder::class) // intraVoxOverride (unused by renamePageFolder)
        );
        // PageShapeSanitizer + PageDataEnricher are final and unused by
        // renamePageFolder (they serve sanitizeText / getPageMetadata). Build real
        // instances: the sanitizer via doubleOrBuild (mockable leaves), the enricher
        // directly with trivial closures since enrich() is never called here.
        $enricher = new \OCA\IntraVox\Service\Path\PageDataEnricher(
            $this->doubleOrBuild(\OCA\IntraVox\Service\Path\PagePathHelper::class),
            $this->createMock(\OCA\IntraVox\Service\PermissionService::class),
            $this->createMock(\OCA\IntraVox\Service\Publication\MetaVoxGateway::class),
            $folders,
            fn(?string $g, ?string $u): array => [],
            fn(\OCP\Files\Node $n): ?int => null
        );
        return new PageMetadataService(
            new PageIdUtils(),
            $this->createMock(\OCA\IntraVox\Service\Version\PageVersionService::class),
            $index,
            $this->createMock(\OCA\IntraVox\Service\NavigationService::class),
            $this->doubleOrBuild(PageShapeSanitizer::class),
            $folders,
            $enricher,
            $this->createMock(\Psr\Log\LoggerInterface::class)
        );
    }

    private function rename(PageMetadataService $svc, array $result, string $requested): array {
        $m = new \ReflectionMethod(PageMetadataService::class, 'renamePageFolder');
        return $m->invoke(
            $svc,
            $result,
            $requested,
            ['uniqueId' => 'page-x', 'language' => 'en'],
            fn(string $uid, ?string $language = null): bool => $this->isHomepageAnswer,
            function (): void {
                $this->clearCacheCalls++;
            }
        );
    }

    /** Inside layout: folder first, then the JSON inside; index repathed. */
    public function testInsideLayoutRenamesFolderAndJsonAsPair(): void {
        $parent = $this->createMock(Folder::class);
        $parent->method('nodeExists')->willReturn(false);

        $innerFile = $this->createMock(File::class);
        $innerFile->expects($this->once())->method('move')
            ->with('/IntraVox/en/contact-us/contact-us.json');

        $movedFolder = $this->createMock(Folder::class);
        $movedFolder->method('get')->with('contact.json')->willReturn($innerFile);
        $movedFolder->expects($this->never())->method('move');

        $folder = $this->createMock(Folder::class);
        $folder->method('getName')->willReturn('contact');
        $folder->method('getPath')->willReturn('/IntraVox/en/contact');
        $folder->method('getParent')->willReturn($parent);
        $folder->method('isUpdateable')->willReturn(true);
        $folder->method('nodeExists')->willReturn(false);
        $folder->expects($this->once())->method('move')
            ->with('/IntraVox/en/contact-us')->willReturn($movedFolder);

        $file = $this->createMock(File::class);
        $file->method('getName')->willReturn('contact.json');
        $file->method('getPath')->willReturn('/IntraVox/en/contact/contact.json');
        $file->method('isUpdateable')->willReturn(true);

        $index = $this->createMock(PageIndexService::class);
        $index->expects($this->once())->method('repathSubtree')
            ->with('/IntraVox/en/contact', '/IntraVox/en/contact-us');

        $svc = $this->makeService(false, $index);
        $out = $this->rename($svc, ['file' => $file, 'folder' => $folder], 'contact-us');

        $this->assertSame('renamed', $out['status']);
        $this->assertSame('contact-us', $out['folderName']);
    }

    /** A failing JSON rename rolls the folder back — never a half pair. */
    public function testJsonRenameFailureRollsBackFolder(): void {
        $parent = $this->createMock(Folder::class);
        $parent->method('nodeExists')->willReturn(false);

        $innerFile = $this->createMock(File::class);
        $innerFile->method('move')->willThrowException(new \RuntimeException('locked'));

        $movedFolder = $this->createMock(Folder::class);
        $movedFolder->method('get')->willReturn($innerFile);
        $movedFolder->expects($this->once())->method('move')
            ->with('/IntraVox/en/contact');

        $folder = $this->createMock(Folder::class);
        $folder->method('getName')->willReturn('contact');
        $folder->method('getPath')->willReturn('/IntraVox/en/contact');
        $folder->method('getParent')->willReturn($parent);
        $folder->method('isUpdateable')->willReturn(true);
        $folder->method('nodeExists')->willReturn(false);
        $folder->method('move')->willReturn($movedFolder);

        $file = $this->createMock(File::class);
        $file->method('getName')->willReturn('contact.json');
        $file->method('getPath')->willReturn('/IntraVox/en/contact/contact.json');
        $file->method('isUpdateable')->willReturn(true);

        $index = $this->createMock(PageIndexService::class);
        $index->expects($this->never())->method('repathSubtree');

        $svc = $this->makeService(false, $index);
        $out = $this->rename($svc, ['file' => $file, 'folder' => $folder], 'contact-us');

        $this->assertSame('failed', $out['status']);
        $this->assertSame('rename_failed', $out['reason']);
    }

    /** Sibling collisions get the createPage/movePage suffix. */
    public function testCollisionGetsSuffix(): void {
        $parent = $this->createMock(Folder::class);
        $parent->method('nodeExists')->willReturnCallback(
            fn(string $name) => in_array($name, ['contact-us', 'contact-us-2.json'], true)
        );

        $innerFile = $this->createMock(File::class);
        $innerFile->expects($this->once())->method('move')
            ->with('/IntraVox/en/contact-us-3/contact-us-3.json');

        $movedFolder = $this->createMock(Folder::class);
        $movedFolder->method('get')->willReturn($innerFile);

        $folder = $this->createMock(Folder::class);
        $folder->method('getName')->willReturn('contact');
        $folder->method('getPath')->willReturn('/IntraVox/en/contact');
        $folder->method('getParent')->willReturn($parent);
        $folder->method('isUpdateable')->willReturn(true);
        $folder->method('nodeExists')->willReturn(false);
        $folder->expects($this->once())->method('move')
            ->with('/IntraVox/en/contact-us-3')->willReturn($movedFolder);

        $file = $this->createMock(File::class);
        $file->method('getName')->willReturn('contact.json');
        $file->method('getPath')->willReturn('/IntraVox/en/contact/contact.json');
        $file->method('isUpdateable')->willReturn(true);

        $svc = $this->makeService(false, $this->createMock(PageIndexService::class));
        $out = $this->rename($svc, ['file' => $file, 'folder' => $folder], 'contact-us');

        $this->assertSame('renamed', $out['status']);
        $this->assertSame('contact-us-3', $out['folderName']);
    }

    /** Inside layout: a CHILD entry with the target name also collides —
     *  `{slug}/{slug}.json` next to a child folder `{slug}` would be read
     *  as that child's beside-layout JSON. */
    public function testChildEntryWithTargetNameCollides(): void {
        $parent = $this->createMock(Folder::class);
        $parent->method('nodeExists')->willReturn(false);

        $innerFile = $this->createMock(File::class);
        $innerFile->expects($this->once())->method('move')
            ->with('/IntraVox/en/contact-us-2/contact-us-2.json');

        $movedFolder = $this->createMock(Folder::class);
        $movedFolder->method('get')->willReturn($innerFile);

        $folder = $this->createMock(Folder::class);
        $folder->method('getName')->willReturn('contact');
        $folder->method('getPath')->willReturn('/IntraVox/en/contact');
        $folder->method('getParent')->willReturn($parent);
        $folder->method('isUpdateable')->willReturn(true);
        $folder->method('nodeExists')->willReturnCallback(
            fn(string $name) => $name === 'contact-us'    // child page folder
        );
        $folder->expects($this->once())->method('move')
            ->with('/IntraVox/en/contact-us-2')->willReturn($movedFolder);

        $file = $this->createMock(File::class);
        $file->method('getName')->willReturn('contact.json');
        $file->method('getPath')->willReturn('/IntraVox/en/contact/contact.json');
        $file->method('isUpdateable')->willReturn(true);

        $svc = $this->makeService(false, $this->createMock(PageIndexService::class));
        $out = $this->rename($svc, ['file' => $file, 'folder' => $folder], 'contact-us');

        $this->assertSame('renamed', $out['status']);
        $this->assertSame('contact-us-2', $out['folderName']);
    }

    /** Beside layout (legacy): JSON first, then the folder; both in the parent. */
    public function testBesideLayoutRenamesBothNodes(): void {
        $parent = $this->createMock(Folder::class);
        $parent->method('nodeExists')->willReturn(false);

        $folder = $this->createMock(Folder::class);
        $folder->method('getName')->willReturn('about');
        $folder->method('getPath')->willReturn('/IntraVox/en/about');
        $folder->method('getParent')->willReturn($parent);
        $folder->method('isUpdateable')->willReturn(true);
        $folder->expects($this->once())->method('move')
            ->with('/IntraVox/en/about-us');

        $file = $this->createMock(File::class);
        $file->method('getName')->willReturn('about.json');
        $file->method('getPath')->willReturn('/IntraVox/en/about.json');
        $file->method('isUpdateable')->willReturn(true);
        $file->expects($this->once())->method('move')
            ->with('/IntraVox/en/about-us.json')->willReturn($file);

        $index = $this->createMock(PageIndexService::class);
        $index->expects($this->once())->method('repathSubtree')
            ->with('/IntraVox/en/about', '/IntraVox/en/about-us');

        $svc = $this->makeService(false, $index);
        $out = $this->rename($svc, ['file' => $file, 'folder' => $folder], 'about-us');

        $this->assertSame('renamed', $out['status']);
    }

    /** The homepage is never folder-renamed, whatever its layout. */
    public function testHomepageIsSkipped(): void {
        $folder = $this->createMock(Folder::class);
        $folder->expects($this->never())->method('move');
        $file = $this->createMock(File::class);
        $file->expects($this->never())->method('move');

        $svc = $this->makeService(true, $this->createMock(PageIndexService::class));
        $out = $this->rename($svc, ['file' => $file, 'folder' => $folder], 'welcome');

        $this->assertSame('skipped', $out['status']);
        $this->assertSame('layout', $out['reason']);
    }

    /** A loose JSON (folder is just the containing directory) has no pair. */
    public function testLooseJsonIsSkipped(): void {
        $folder = $this->createMock(Folder::class);
        $folder->method('getName')->willReturn('en');
        $folder->method('getPath')->willReturn('/IntraVox/en');
        $folder->expects($this->never())->method('move');

        $file = $this->createMock(File::class);
        $file->method('getName')->willReturn('stray.json');
        $file->method('getPath')->willReturn('/IntraVox/en/stray.json');
        $file->expects($this->never())->method('move');

        $svc = $this->makeService(false, $this->createMock(PageIndexService::class));
        $out = $this->rename($svc, ['file' => $file, 'folder' => $folder], 'stray-renamed');

        $this->assertSame('skipped', $out['status']);
        $this->assertSame('layout', $out['reason']);
    }

    /** No write permission on either node refuses before any move. */
    public function testPermissionDeniedBeforeAnyMove(): void {
        $folder = $this->createMock(Folder::class);
        $folder->method('getName')->willReturn('contact');
        $folder->method('getPath')->willReturn('/IntraVox/en/contact');
        $folder->method('isUpdateable')->willReturn(false);
        $folder->expects($this->never())->method('move');

        $file = $this->createMock(File::class);
        $file->method('getName')->willReturn('contact.json');
        $file->method('getPath')->willReturn('/IntraVox/en/contact/contact.json');
        $file->method('isUpdateable')->willReturn(true);
        $file->expects($this->never())->method('move');

        $svc = $this->makeService(false, $this->createMock(PageIndexService::class));
        $out = $this->rename($svc, ['file' => $file, 'folder' => $folder], 'contact-us');

        $this->assertSame('failed', $out['status']);
        $this->assertSame('permission', $out['reason']);
    }

    /** Same slug in, nothing to do. */
    public function testUnchangedNameIsSkipped(): void {
        $folder = $this->createMock(Folder::class);
        $folder->method('getName')->willReturn('contact');
        $folder->method('getPath')->willReturn('/IntraVox/en/contact');
        $folder->expects($this->never())->method('move');

        $file = $this->createMock(File::class);
        $file->method('getName')->willReturn('contact.json');
        $file->method('getPath')->willReturn('/IntraVox/en/contact/contact.json');
        $file->expects($this->never())->method('move');

        $svc = $this->makeService(false, $this->createMock(PageIndexService::class));
        $out = $this->rename($svc, ['file' => $file, 'folder' => $folder], 'contact');

        $this->assertSame('skipped', $out['status']);
        $this->assertSame('unchanged', $out['reason']);
    }
}
