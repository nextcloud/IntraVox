<?php
declare(strict_types=1);

namespace OCA\IntraVox\Tests\Unit\Service;

use OCA\IntraVox\Service\Cache\PageCacheService;
use OCA\IntraVox\Service\Media\PageMediaService;
use OCA\IntraVox\Service\PageService;
use OCA\IntraVox\Tests\Unit\Service\Harness\BuildsPageService;
use OCP\Files\File;
use OCP\Files\FileInfo;
use OCP\Files\Folder;
use PHPUnit\Framework\TestCase;

/**
 * Characterizes getMedia() — the most substantial media method (four resolution
 * paths) and, until now, the only one with ZERO direct test coverage.
 * PageServiceMediaLanguageTest covers upload/list/check/resources + the #92
 * cross-language miss path; this pins getMedia's own orchestration: which
 * _media folder it resolves for the home id, the two cache-hit fast paths, the
 * cache-miss walk, the total-miss error, and the basename() traversal strip.
 *
 * media()->streamMediaFile is the observable sink: a mocked PageMediaService
 * records the ($mediaFolder, $filename) it is handed, so each branch is
 * asserted by WHICH folder reached the stream.
 */
class PageServiceGetMediaTest extends TestCase {

    use BuildsPageService;

    /** The (folder, filename) the last streamMediaFile call received. */
    private ?array $streamed = null;

    private function makeMediaFolder(string $path): Folder {
        $folder = $this->createMock(Folder::class);
        $folder->method('getName')->willReturn(basename($path));
        $folder->method('getType')->willReturn(FileInfo::TYPE_FOLDER);
        $folder->method('getPath')->willReturn($path);
        return $folder;
    }

    /**
     * A page folder that resolves '_media' to $mediaFolder (or throws NotFound
     * when $mediaFolder is null, mimicking a page with no media).
     */
    private function makePageFolder(string $path, ?Folder $mediaFolder): Folder {
        $folder = $this->createMock(Folder::class);
        $folder->method('getName')->willReturn(basename($path));
        $folder->method('getType')->willReturn(FileInfo::TYPE_FOLDER);
        $folder->method('getPath')->willReturn($path);
        $folder->method('get')->willReturnCallback(function ($p) use ($mediaFolder, $path) {
            if ($p === '_media' && $mediaFolder !== null) {
                return $mediaFolder;
            }
            throw new \OCP\Files\NotFoundException($path . '/' . $p);
        });
        return $folder;
    }

    /**
     * Build a service whose read-language folder is $langFolder, with the media
     * gateway mocked to record streamMediaFile, and the cache mocked from
     * $cachedFolders (pageId => pageFolder).
     *
     * @param array<string,Folder> $cachedFolders
     */
    private function makeService(Folder $langFolder, array $cachedFolders = []): PageService {
        $svc = new class($langFolder) extends PageService {
            private Folder $lang;
            public function __construct(Folder $lang) {
                $this->lang = $lang;
            }
            protected function getReadLanguageFolder(): Folder {
                return $this->lang;
            }
            protected function getLanguageFolder() {
                return $this->lang;
            }
            protected function getIntraVoxFolder() {
                return $this->lang;
            }
            public function clearCache(): void {
            }
        };

        $media = $this->createMock(PageMediaService::class);
        $media->method('streamMediaFile')->willReturnCallback(
            function ($mediaFolder, $filename) {
                $this->streamed = [$mediaFolder, $filename];
                return 'STREAMED:' . $filename;
            }
        );

        $cache = $this->createMock(PageCacheService::class);
        $cache->method('hasPageFolder')->willReturnCallback(fn($id) => isset($cachedFolders[$id]));
        $cache->method('getPageFolder')->willReturnCallback(fn($id) => $cachedFolders[$id] ?? null);

        $this->injectPageServiceDependencies($svc, [
            'userId' => 'tester',
            'logger' => $this->createMock(\Psr\Log\LoggerInterface::class),
            'pageMediaService' => $media,
            'cache' => $cache,
        ]);
        return $svc;
    }

    public function testHomeIdStreamsFromTheLanguageRootMediaFolder(): void {
        $rootMedia = $this->makeMediaFolder('/IntraVox/en/_media');
        $lang = $this->makePageFolder('/IntraVox/en', $rootMedia);
        $svc = $this->makeService($lang);

        foreach (['home', '2e8f694e-147e-4793-8949-4732e679ae6b', 'page-2e8f694e-147e-4793-8949-4732e679ae6b'] as $homeId) {
            $this->streamed = null;
            $svc->getMedia($homeId, 'logo.png');
            $this->assertSame($rootMedia, $this->streamed[0], "home id '$homeId' streams from the language-root _media");
            $this->assertSame('logo.png', $this->streamed[1]);
        }
    }

    public function testCacheHitOnOriginalIdUsesThatPageMedia(): void {
        $pageMedia = $this->makeMediaFolder('/IntraVox/en/about/_media');
        $pageFolder = $this->makePageFolder('/IntraVox/en/about', $pageMedia);
        $lang = $this->makePageFolder('/IntraVox/en', null);

        // Cache holds the page folder under the ORIGINAL (unsanitized) id.
        $svc = $this->makeService($lang, ['page-about-123' => $pageFolder]);
        $svc->getMedia('page-about-123', 'pic.png');

        $this->assertSame($pageMedia, $this->streamed[0], 'a cache hit on the original id streams that page media');
    }

    public function testCacheHitOnSanitizedIdUsesThatPageMedia(): void {
        // sanitizeId() strips non-[a-zA-Z0-9_-] chars (it does NOT lowercase), so
        // 'my page' -> 'mypage'. The original id misses the cache; the sanitized
        // form hits -> the second cache branch resolves that page's media.
        $pageMedia = $this->makeMediaFolder('/IntraVox/en/mypage/_media');
        $pageFolder = $this->makePageFolder('/IntraVox/en/mypage', $pageMedia);
        $lang = $this->makePageFolder('/IntraVox/en', null);

        $svc = $this->makeService($lang, ['mypage' => $pageFolder]);
        $svc->getMedia('my page', 'pic.png');

        $this->assertSame($pageMedia, $this->streamed[0], 'a cache hit on the sanitized id streams that page media');
    }

    public function testTotalMissThrowsMediaNotFound(): void {
        // No home, no cache, and the language folder holds nothing to walk.
        $lang = $this->makePageFolder('/IntraVox/en', null);
        $lang->method('getDirectoryListing')->willReturn([]);
        $svc = $this->makeService($lang);

        $this->expectException(\Exception::class);
        $svc->getMedia('page-ghost', 'pic.png');
    }

    public function testFilenameIsStrippedToBasenameAgainstTraversal(): void {
        // A traversal filename must be reduced to its basename before streaming.
        $rootMedia = $this->makeMediaFolder('/IntraVox/en/_media');
        $lang = $this->makePageFolder('/IntraVox/en', $rootMedia);
        $svc = $this->makeService($lang);

        $svc->getMedia('home', '../../../../etc/passwd');

        $this->assertSame('passwd', $this->streamed[1], 'the filename is reduced to basename, blocking directory traversal');
    }
}
