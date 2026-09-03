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
use Psr\Log\LoggerInterface;

/**
 * Characterizes getPageMetadata() before it moves into Page/PageMetadataService
 * in Phase 15. Pins the load-bearing quirks the plan flagged: the creation-time
 * fallback (ctime 0 -> mtime, for storages that do not report creation time), the
 * exact "Page not found: <id>" error text, and the core response shape used by
 * the metadata/rename UI.
 */
class PageMetadataTest extends TestCase {

    use BuildsPageService;

    /**
     * A page file 'about' found in the primary language folder (index misses,
     * root seam throws so the walk stays in-folder).
     *
     * @param int $ctime creation time the file reports (0 = unsupported)
     */
    private function makeService(int $ctime, int $mtime = 1_700_000_000): PageService {
        $file = $this->createMock(File::class);
        $file->method('getName')->willReturn('about.json');
        $file->method('getType')->willReturn(FileInfo::TYPE_FILE);
        $file->method('getPath')->willReturn('/IntraVox/en/about/about.json');
        $file->method('getId')->willReturn(4242);
        $file->method('getMTime')->willReturn($mtime);
        $file->method('getCreationTime')->willReturn($ctime);
        $file->method('getSize')->willReturn(512);
        $file->method('getInternalPath')->willReturn('__groupfolders/1/files/IntraVox/en/about/about.json');
        $file->method('getContent')->willReturn(json_encode([
            'uniqueId' => 'page-about',
            'title' => 'About',
            'language' => 'en',
        ]));

        // beside layout: about.json sits beside the about/ folder in en/.
        $pageFolder = $this->makeFolder('/IntraVox/en/about', ['about.json' => $file]);
        $lang = $this->makeFolder('/IntraVox/en', [
            'about.json' => $file,
            'about' => $pageFolder,
        ]);
        // enrichWithPathData resolves the relative path via the root, so the root
        // must exist; the primary-folder scan still finds the page first (the
        // index mock misses), so the root is only used for path math.
        $base = $this->makeFolder('/IntraVox', ['en' => $lang]);

        $svc = new class($lang, $base) extends PageService {
            private Folder $lang;
            private Folder $base;
            public function __construct(Folder $lang, Folder $base) {
                $this->lang = $lang;
                $this->base = $base;
            }
            protected function getLanguageFolder(): Folder {
                return $this->lang;
            }
            protected function getReadLanguageFolder(): Folder {
                return $this->lang;
            }
            protected function getIntraVoxFolder(): Folder {
                return $this->base;
            }
            public function isHomepage(string $uniqueId, ?string $language = null): bool {
                return false;
            }
        };

        $index = $this->createMock(PageIndexService::class);
        $index->method('findByUniqueId')->willReturn(null);

        $this->injectPageServiceDependencies($svc, [
            'userId' => 'tester',
            'pageIndexService' => $index,
            'logger' => $this->createMock(LoggerInterface::class),
        ]);

        return $svc;
    }

    public function testUnknownPageThrowsPageNotFoundWithTheIdInTheMessage(): void {
        $empty = $this->makeFolder('/IntraVox/en', []);
        $base = $this->makeFolder('/IntraVox', ['en' => $empty]);
        $svc = new class($empty, $base) extends PageService {
            private Folder $empty;
            private Folder $base;
            public function __construct(Folder $empty, Folder $base) {
                $this->empty = $empty;
                $this->base = $base;
            }
            protected function getLanguageFolder(): Folder {
                return $this->empty;
            }
            protected function getReadLanguageFolder(): Folder {
                return $this->empty;
            }
            protected function getIntraVoxFolder(): Folder {
                return $this->base;
            }
        };
        $index = $this->createMock(PageIndexService::class);
        $index->method('findByUniqueId')->willReturn(null);
        $this->injectPageServiceDependencies($svc, [
            'userId' => 'tester',
            'pageIndexService' => $index,
            'logger' => $this->createMock(LoggerInterface::class),
        ]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Page not found: page-nope');
        $svc->getPageMetadata('page-nope');
    }

    public function testCreationTimeZeroFallsBackToModifiedTime(): void {
        $mtime = 1_700_000_500;
        $svc = $this->makeService(ctime: 0, mtime: $mtime);

        $meta = $svc->getPageMetadata('page-about');

        $this->assertSame($mtime, $meta['created'], 'ctime 0 falls back to mtime');
        $this->assertSame($mtime, $meta['modified']);
        $this->assertSame(date('Y-m-d H:i:s', $mtime), $meta['createdFormatted']);
    }

    public function testRealCreationTimeIsKeptWhenReported(): void {
        $svc = $this->makeService(ctime: 1_600_000_000, mtime: 1_700_000_000);

        $meta = $svc->getPageMetadata('page-about');

        $this->assertSame(1_600_000_000, $meta['created'], 'a real ctime is preserved');
        $this->assertSame(1_700_000_000, $meta['modified']);
    }

    public function testCoreResponseShape(): void {
        $svc = $this->makeService(ctime: 1_600_000_000);

        $meta = $svc->getPageMetadata('page-about');

        $this->assertSame('About', $meta['title']);
        $this->assertSame('page-about', $meta['uniqueId']);
        $this->assertSame(4242, $meta['fileId']);
        $this->assertSame(512, $meta['size']);
        $this->assertSame('IntraVox', $meta['mountPoint']);
        $this->assertSame('/IntraVox/en/about/about.json', $meta['path']);
        foreach (['created', 'modified', 'createdFormatted', 'modifiedFormatted',
                  'createdRelative', 'modifiedRelative', 'permissions', 'canEdit'] as $key) {
            $this->assertArrayHasKey($key, $meta, "metadata must expose '$key'");
        }
    }
}
