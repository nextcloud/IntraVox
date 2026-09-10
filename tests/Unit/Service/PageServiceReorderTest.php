<?php
declare(strict_types=1);

namespace OCA\IntraVox\Tests\Unit\Service;

use OCA\IntraVox\Service\PageService;
use OCA\IntraVox\Service\PermissionService;
use OCP\Files\File;
use OCP\Files\FileInfo;
use OCP\Files\Folder;
use PHPUnit\Framework\TestCase;

/**
 * Focused unit test for PageService::reorderSiblings after the O(N) refactor.
 *
 * Reorder is driven end-to-end against a fake language/parent folder so we can
 * assert the observable effects: the right `order` is written to the right page
 * files, config files and the homepage are never touched, foreign ids are
 * skipped, and an already-correct order is not rewritten.
 *
 * getLanguageFolder() and isHomepage() are overridden to isolate reorder from
 * the Nextcloud filesystem and the homepage-pointer resolution. The instance is
 * built without the (25-arg) constructor; every property reorder touches has a
 * safe default, and the distributed caches default to null so clearCache() is a
 * no-op here.
 */
class PageServiceReorderTest extends TestCase {

    /** Build a File mock that records putContent() calls. */
    private function makeFile(string $path, array $json, array &$writes): File {
        $file = $this->createMock(File::class);
        $file->method('getName')->willReturn(basename($path));
        $file->method('getType')->willReturn(FileInfo::TYPE_FILE);
        $file->method('getPath')->willReturn($path);
        $content = json_encode($json);
        $file->method('getContent')->willReturn($content);
        $file->method('putContent')->willReturnCallback(function ($data) use ($path, &$writes) {
            $writes[$path] = $data;
        });
        return $file;
    }

    /**
     * Build a child-page *folder* named $slug that holds $slug.json (the
     * canonical on-disk layout: en/news/events/events.json).
     */
    private function makeChildFolder(string $slug, array $json, array &$writes): Folder {
        $path = '/lang/' . $slug;
        $file = $this->makeFile($path . '/' . $slug . '.json', $json, $writes);
        $folder = $this->createMock(Folder::class);
        $folder->method('getName')->willReturn($slug);
        $folder->method('getType')->willReturn(FileInfo::TYPE_FOLDER);
        $folder->method('getPath')->willReturn($path);
        $folder->method('get')->willReturnCallback(function ($p) use ($slug, $file) {
            if ($p === $slug . '.json') {
                return $file;
            }
            throw new \OCP\Files\NotFoundException($p);
        });
        return $folder;
    }

    /**
     * @return PageService a subclass whose language folder is $parent and whose
     *   isHomepage() returns true only for $homeUniqueId.
     */
    private function makeService(Folder $parent, ?string $homeUniqueId): PageService {
        // reorderSiblings resolves its write-target folder via
        // folders()->languageFolder(); wiring $parent as the getLanguageFolder seam
        // returns it verbatim (no composition, so the bare '/lang' path is fine).
        // isHomepage stays overridden; reorder runs no cross-language locate.
        $svc = new class($homeUniqueId) extends PageService {
            private ?string $homeId;
            // Deliberately bypass the real 25-arg constructor.
            public function __construct(?string $homeId) {
                $this->homeId = $homeId;
            }
            public function isHomepage(string $uniqueId, ?string $language = null): bool {
                return $this->homeId !== null && $uniqueId === $this->homeId;
            }
        };
        $locator = new \OCA\IntraVox\Service\Locator\PageLocator(
            $this->createMock(\OCA\IntraVox\Service\PageIndexService::class),
            $this->createMock(\Psr\Log\LoggerInterface::class)
        );
        (new \ReflectionProperty(PageService::class, 'folderContext'))
            ->setValue($svc, new \OCA\IntraVox\Service\Folder\FolderContext(
                $this->createMock(\OCP\Files\IRootFolder::class),
                'tester',
                $this->createMock(\OCP\IConfig::class),
                $this->createMock(\OCA\IntraVox\Service\LanguageService::class),
                new \OCA\IntraVox\Service\Language\LanguageResolver(),
                $locator,
                $parent, // intraVoxOverride
                null,
                fn(): Folder => $parent
            ));
        (new \ReflectionProperty(PageService::class, 'permissionService'))
            ->setValue($svc, new class extends PermissionService {
                public function __construct() {
                }
            });
        (new \ReflectionProperty(PageService::class, 'pageLocator'))
            ->setValue($svc, $locator);
        // reorderSiblings ends with clearCache() → cacheInvalidator->invalidate();
        // this custom-ctor subclass bypasses the harness auto-fill, so wire a real
        // invalidator over inert doubles (the reorder assertions are about the
        // order writes, not the cache fan-out). This file does not use the
        // BuildsPageService trait, so it builds the invalidator inline.
        (new \ReflectionProperty(PageService::class, 'cacheInvalidator'))
            ->setValue($svc, new \OCA\IntraVox\Service\Cache\PageCacheInvalidator(
                $this->createMock(\OCA\IntraVox\Service\Cache\PageCacheService::class),
                $locator,
                $this->createMock(PermissionService::class)
            ));
        return $svc;
    }

    public function testReorderWritesSequentialOrderToDirectChildren(): void {
        $writes = [];
        $a = $this->makeFile('/lang/a.json', ['uniqueId' => 'page-a', 'order' => 0], $writes);
        $b = $this->makeFile('/lang/b.json', ['uniqueId' => 'page-b', 'order' => 1], $writes);
        $c = $this->makeFile('/lang/c.json', ['uniqueId' => 'page-c', 'order' => 2], $writes);

        $parent = $this->createMock(Folder::class);
        $parent->method('getPath')->willReturn('/lang');
        $parent->method('getDirectoryListing')->willReturn([$a, $b, $c]);

        $svc = $this->makeService($parent, null);
        // New order: c, a, b
        $svc->reorderSiblings(null, ['page-c', 'page-a', 'page-b']);

        $this->assertEquals(0, json_decode($writes['/lang/c.json'], true)['order']);
        $this->assertEquals(1, json_decode($writes['/lang/a.json'], true)['order']);
        $this->assertEquals(2, json_decode($writes['/lang/b.json'], true)['order']);
    }

    public function testReorderWritesOrderToFolderLayoutChildren(): void {
        // The real layout: each child page is a subfolder holding {slug}.json.
        $writes = [];
        $events = $this->makeChildFolder('events', ['uniqueId' => 'page-ev', 'order' => 0], $writes);
        $updates = $this->makeChildFolder('updates', ['uniqueId' => 'page-up', 'order' => 1], $writes);

        // The provided folder acts as the container being reordered (root); we
        // pass null so reorder operates on it directly, isolating this test from
        // the parent-resolution walk (covered elsewhere).
        $parent = $this->createMock(Folder::class);
        $parent->method('getPath')->willReturn('/lang');
        $parent->method('getDirectoryListing')->willReturn([$events, $updates]);

        $svc = $this->makeService($parent, null);
        // Put updates first.
        $svc->reorderSiblings(null, ['page-up', 'page-ev']);

        $this->assertEquals(0, json_decode($writes['/lang/updates/updates.json'], true)['order']);
        $this->assertEquals(1, json_decode($writes['/lang/events/events.json'], true)['order']);
    }

    public function testReorderNeverWritesHomepageOrConfigFiles(): void {
        $writes = [];
        $home = $this->makeFile('/lang/home.json', ['uniqueId' => 'page-home', 'order' => 0], $writes);
        $nav = $this->makeFile('/lang/navigation.json', ['uniqueId' => 'nav'], $writes);
        $a = $this->makeFile('/lang/a.json', ['uniqueId' => 'page-a', 'order' => 5], $writes);

        $parent = $this->createMock(Folder::class);
        $parent->method('getPath')->willReturn('/lang'); // language root
        $parent->method('getDirectoryListing')->willReturn([$home, $nav, $a]);

        // page-home is the configured homepage.
        $svc = $this->makeService($parent, 'page-home');
        $svc->reorderSiblings(null, ['page-home', 'page-a']);

        // home.json is always skipped in the map; navigation.json is a root config
        // file; only page-a should get an order (index 1).
        $this->assertArrayNotHasKey('/lang/home.json', $writes);
        $this->assertArrayNotHasKey('/lang/navigation.json', $writes);
        $this->assertEquals(1, json_decode($writes['/lang/a.json'], true)['order']);
    }

    public function testReorderSkipsForeignIds(): void {
        $writes = [];
        $a = $this->makeFile('/lang/a.json', ['uniqueId' => 'page-a', 'order' => 0], $writes);

        $parent = $this->createMock(Folder::class);
        $parent->method('getPath')->willReturn('/lang');
        $parent->method('getDirectoryListing')->willReturn([$a]);

        $svc = $this->makeService($parent, null);
        // page-zzz is not among the direct children — must be ignored, not fatal.
        $svc->reorderSiblings(null, ['page-zzz', 'page-a']);

        // page-a is at index 1 in the requested order.
        $this->assertEquals(1, json_decode($writes['/lang/a.json'], true)['order']);
        $this->assertCount(1, $writes);
    }

    public function testReorderDoesNotRewriteAlreadyCorrectOrder(): void {
        $writes = [];
        // a already has order 0, b already has order 1 — the requested order matches.
        $a = $this->makeFile('/lang/a.json', ['uniqueId' => 'page-a', 'order' => 0], $writes);
        $b = $this->makeFile('/lang/b.json', ['uniqueId' => 'page-b', 'order' => 1], $writes);

        $parent = $this->createMock(Folder::class);
        $parent->method('getPath')->willReturn('/lang');
        $parent->method('getDirectoryListing')->willReturn([$a, $b]);

        $svc = $this->makeService($parent, null);
        $svc->reorderSiblings(null, ['page-a', 'page-b']);

        // Nothing changed, so no writes at all.
        $this->assertCount(0, $writes);
    }

    /**
     * The exact bytes written are determined solely by the page data + the
     * encode flags — nothing reads back the per-request content cache mid-reorder
     * to influence them. Pinning the full written string (not just the order
     * field) makes it provable that the dead `$this->fileContentCache[...] =`
     * bookkeeping line has zero effect on output, so removing it in the
     * extraction is behaviour-identical.
     */
    public function testReorderWrittenBytesAreFullyDeterminedByDataAndFlags(): void {
        $writes = [];
        $a = $this->makeFile('/lang/a.json', ['uniqueId' => 'page-a', 'title' => 'A', 'order' => 5], $writes);
        $b = $this->makeFile('/lang/b.json', ['uniqueId' => 'page-b', 'title' => 'B', 'order' => 9], $writes);

        $parent = $this->createMock(Folder::class);
        $parent->method('getPath')->willReturn('/lang');
        $parent->method('getDirectoryListing')->willReturn([$a, $b]);

        $svc = $this->makeService($parent, null);
        $svc->reorderSiblings(null, ['page-b', 'page-a']);

        // b -> index 0, a -> index 1. The written bytes are exactly the source
        // JSON with `order` replaced, re-encoded pretty + unescaped-unicode.
        $expectedB = json_encode(
            ['uniqueId' => 'page-b', 'title' => 'B', 'order' => 0],
            JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE
        );
        $expectedA = json_encode(
            ['uniqueId' => 'page-a', 'title' => 'A', 'order' => 1],
            JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE
        );
        $this->assertSame($expectedB, $writes['/lang/b.json']);
        $this->assertSame($expectedA, $writes['/lang/a.json']);
    }

    public function testReorderEncodesWithPrettyPrintAndUnescapedUnicode(): void {
        $writes = [];
        // A title with a non-ASCII char and a nested structure to observe the flags.
        $a = $this->makeFile('/lang/a.json', ['uniqueId' => 'page-a', 'title' => 'Café', 'order' => 9], $writes);

        $parent = $this->createMock(Folder::class);
        $parent->method('getPath')->willReturn('/lang');
        $parent->method('getDirectoryListing')->willReturn([$a]);

        $svc = $this->makeService($parent, null);
        $svc->reorderSiblings(null, ['page-a']);

        $written = $writes['/lang/a.json'];
        $this->assertStringContainsString('Café', $written, 'unicode must not be escaped');
        $this->assertStringContainsString("\n", $written, 'pretty-print must add newlines');
        $this->assertEquals(0, json_decode($written, true)['order']);
    }
}
