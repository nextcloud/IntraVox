<?php
declare(strict_types=1);

namespace OCA\IntraVox\Tests\Unit\Service;

use OCA\IntraVox\Service\Locator\PageLocator;
use OCA\IntraVox\Service\PageIndexService;
use OCA\IntraVox\Service\Reorder\PageReorderer;
use OCA\IntraVox\Tests\Unit\Service\Harness\BuildsCollaboratorFixtures;
use OCP\Files\File;
use OCP\Files\FileInfo;
use OCP\Files\Folder;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Characterizes Reorder/PageReorderer::reorder DIRECTLY — `new PageReorderer(...)`
 * — rather than through the thin PageService::reorderSiblings delegator. This is
 * the coverage that must exist BEFORE the god-class can be deleted (dissolution
 * plan, sessie 1): today the reorder behaviour is pinned by PageServiceReorderTest,
 * which reaches the reorderer through a ctor-bypass PageService subclass wired by
 * reflection — ceremony that exists only because the delegator sits on PageService.
 *
 * PageReorderer is the simplest of the three sub-services to characterize
 * directly: 3 ctor deps (PageLocator / HomepageResolverService /
 * PageCacheInvalidator) and NO injected closures — reorder() takes the resolved
 * language folder as a plain argument. The homepage-skip comes from the injected
 * HomepageResolverService (rigged via fakeHomepageResolver), the cache flush from
 * the injected PageCacheInvalidator (inert here), and the cached directory/content
 * reads go through a real PageLocator over the fixture folders.
 *
 * Migrated verbatim from PageServiceReorderTest: sequential order writes, the
 * folder-layout and loose-json layouts, the homepage/config-file skips, foreign-id
 * skips, the no-rewrite-when-correct rule, and the exact pretty-printed /
 * unescaped-unicode bytes.
 */
class PageReordererTest extends TestCase {

    use BuildsCollaboratorFixtures;
    /** Build a File mock that records putContent() calls into $writes. */
    private function reorderFile(string $path, array $json, array &$writes): File {
        $file = $this->createMock(File::class);
        $file->method('getName')->willReturn(basename($path));
        $file->method('getType')->willReturn(FileInfo::TYPE_FILE);
        $file->method('getPath')->willReturn($path);
        $file->method('getContent')->willReturn(json_encode($json));
        $file->method('putContent')->willReturnCallback(function ($data) use ($path, &$writes): void {
            $writes[$path] = $data;
        });
        return $file;
    }

    /**
     * A child-page *folder* named $slug that holds {$slug}.json (the canonical
     * on-disk layout: en/news/events/events.json).
     */
    private function childFolder(string $slug, array $json, array &$writes): Folder {
        $path = '/lang/' . $slug;
        $file = $this->reorderFile($path . '/' . $slug . '.json', $json, $writes);
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

    /** The parent/language folder listing the given direct children. */
    private function parentFolder(array $children): Folder {
        $parent = $this->createMock(Folder::class);
        $parent->method('getName')->willReturn('lang');
        $parent->method('getType')->willReturn(FileInfo::TYPE_FOLDER);
        $parent->method('getPath')->willReturn('/lang');
        $parent->method('getDirectoryListing')->willReturn($children);
        return $parent;
    }

    /**
     * A real PageReorderer whose isHomepage() reports $homeUniqueId as the
     * configured homepage (via the rigged HomepageResolverService), over a real
     * PageLocator (cached folder/content reads) and an inert cache invalidator.
     */
    private function makeReorderer(?string $homeUniqueId): PageReorderer {
        return new PageReorderer(
            new PageLocator(
                $this->createMock(PageIndexService::class),
                $this->createMock(LoggerInterface::class)
            ),
            $this->fakeHomepageResolver($homeUniqueId),
            $this->fakeCacheInvalidator(),
        );
    }

    public function testReorderWritesSequentialOrderToDirectChildren(): void {
        $writes = [];
        $a = $this->reorderFile('/lang/a.json', ['uniqueId' => 'page-a', 'order' => 0], $writes);
        $b = $this->reorderFile('/lang/b.json', ['uniqueId' => 'page-b', 'order' => 1], $writes);
        $c = $this->reorderFile('/lang/c.json', ['uniqueId' => 'page-c', 'order' => 2], $writes);
        $parent = $this->parentFolder([$a, $b, $c]);

        // New order: c, a, b
        $this->makeReorderer(null)->reorder(null, ['page-c', 'page-a', 'page-b'], $parent);

        $this->assertEquals(0, json_decode($writes['/lang/c.json'], true)['order']);
        $this->assertEquals(1, json_decode($writes['/lang/a.json'], true)['order']);
        $this->assertEquals(2, json_decode($writes['/lang/b.json'], true)['order']);
    }

    public function testReorderWritesOrderToFolderLayoutChildren(): void {
        // The real layout: each child page is a subfolder holding {slug}.json.
        $writes = [];
        $events = $this->childFolder('events', ['uniqueId' => 'page-ev', 'order' => 0], $writes);
        $updates = $this->childFolder('updates', ['uniqueId' => 'page-up', 'order' => 1], $writes);
        $parent = $this->parentFolder([$events, $updates]);

        // Put updates first.
        $this->makeReorderer(null)->reorder(null, ['page-up', 'page-ev'], $parent);

        $this->assertEquals(0, json_decode($writes['/lang/updates/updates.json'], true)['order']);
        $this->assertEquals(1, json_decode($writes['/lang/events/events.json'], true)['order']);
    }

    public function testReorderNeverWritesHomepageOrConfigFiles(): void {
        $writes = [];
        $home = $this->reorderFile('/lang/home.json', ['uniqueId' => 'page-home', 'order' => 0], $writes);
        $nav = $this->reorderFile('/lang/navigation.json', ['uniqueId' => 'nav'], $writes);
        $a = $this->reorderFile('/lang/a.json', ['uniqueId' => 'page-a', 'order' => 5], $writes);
        $parent = $this->parentFolder([$home, $nav, $a]);

        // page-home is the configured homepage; reorder against the language ROOT
        // (parent path === languageFolder path), so navigation.json counts as a
        // root config file.
        $this->makeReorderer('page-home')->reorder(null, ['page-home', 'page-a'], $parent);

        $this->assertArrayNotHasKey('/lang/home.json', $writes);
        $this->assertArrayNotHasKey('/lang/navigation.json', $writes);
        $this->assertEquals(1, json_decode($writes['/lang/a.json'], true)['order']);
    }

    public function testReorderSkipsForeignIds(): void {
        $writes = [];
        $a = $this->reorderFile('/lang/a.json', ['uniqueId' => 'page-a', 'order' => 0], $writes);
        $parent = $this->parentFolder([$a]);

        // page-zzz is not among the direct children — must be ignored, not fatal.
        $this->makeReorderer(null)->reorder(null, ['page-zzz', 'page-a'], $parent);

        // page-a is at index 1 in the requested order.
        $this->assertEquals(1, json_decode($writes['/lang/a.json'], true)['order']);
        $this->assertCount(1, $writes);
    }

    public function testReorderDoesNotRewriteAlreadyCorrectOrder(): void {
        $writes = [];
        // a already has order 0, b already has order 1 — the requested order matches.
        $a = $this->reorderFile('/lang/a.json', ['uniqueId' => 'page-a', 'order' => 0], $writes);
        $b = $this->reorderFile('/lang/b.json', ['uniqueId' => 'page-b', 'order' => 1], $writes);
        $parent = $this->parentFolder([$a, $b]);

        $this->makeReorderer(null)->reorder(null, ['page-a', 'page-b'], $parent);

        // Nothing changed, so no writes at all.
        $this->assertCount(0, $writes);
    }

    /**
     * The exact bytes written are determined solely by the page data + the encode
     * flags. Pinning the full written string (not just the order field) makes it
     * provable that the written output is exactly the source JSON with `order`
     * replaced, re-encoded pretty + unescaped-unicode.
     */
    public function testReorderWrittenBytesAreFullyDeterminedByDataAndFlags(): void {
        $writes = [];
        $a = $this->reorderFile('/lang/a.json', ['uniqueId' => 'page-a', 'title' => 'A', 'order' => 5], $writes);
        $b = $this->reorderFile('/lang/b.json', ['uniqueId' => 'page-b', 'title' => 'B', 'order' => 9], $writes);
        $parent = $this->parentFolder([$a, $b]);

        $this->makeReorderer(null)->reorder(null, ['page-b', 'page-a'], $parent);

        // b -> index 0, a -> index 1. The written bytes are exactly the source JSON
        // with `order` replaced, re-encoded pretty + unescaped-unicode.
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
        // A title with a non-ASCII char to observe the flags.
        $a = $this->reorderFile('/lang/a.json', ['uniqueId' => 'page-a', 'title' => 'Café', 'order' => 9], $writes);
        $parent = $this->parentFolder([$a]);

        $this->makeReorderer(null)->reorder(null, ['page-a'], $parent);

        $written = $writes['/lang/a.json'];
        $this->assertStringContainsString('Café', $written, 'unicode must not be escaped');
        $this->assertStringContainsString("\n", $written, 'pretty-print must add newlines');
        $this->assertEquals(0, json_decode($written, true)['order']);
    }
}
