<?php
declare(strict_types=1);

namespace OCA\IntraVox\Tests\Unit\Service;

use OCA\IntraVox\Service\PageIndexService;
use OCA\IntraVox\Service\PageService;
use OCA\IntraVox\Tests\Unit\Service\Harness\BuildsPageService;
use OCP\Files\File;
use OCP\Files\FileInfo;
use OCP\Files\Folder;
use OCP\Files\Node;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Characterizes the CLI maintenance methods repairEntities() and rebuildIndex()
 * before they move into Maintenance/PageMaintenanceService in Phase 4. These are
 * driven by RepairEntitiesCommand / ReindexPagesCommand, so the pins protect the
 * occ commands' observable contract: the stats shape, the language-folder filter,
 * the file-type filter, and — crucially — that a dry run counts but never writes,
 * and that rebuildIndex clears the index only after the tree is readable.
 */
class PageMaintenanceRepairTest extends TestCase {

    use BuildsPageService;

    /** Files that had putContent() called on them, by path. */
    private array $written = [];

    protected function setUp(): void {
        $this->written = [];
    }

    /** A page JSON file that records writes into $this->written. */
    private function pageFile(string $path, array $json): File {
        $file = $this->createMock(File::class);
        $file->method('getName')->willReturn(basename($path));
        $file->method('getType')->willReturn(FileInfo::TYPE_FILE);
        $file->method('getPath')->willReturn($path);
        $file->method('getContent')->willReturn(json_encode($json));
        $file->method('getId')->willReturn(abs(crc32($path)));
        $file->method('putContent')->willReturnCallback(function ($content) use ($path) {
            $this->written[$path] = $content;
        });
        return $file;
    }

    /**
     * @param Node[] $baseChildren language folders (and non-language folders) under the root
     */
    private function makeService(array $baseChildren, ?PageIndexService $index = null): PageService {
        $base = $this->makeFolder('/IntraVox', array_combine(
            array_map(fn(Node $n) => $n->getName(), $baseChildren),
            $baseChildren
        ));

        // repairEntities/rebuildIndex pass folders()->intraVox() to the maintenance
        // service and run no cross-language locate, so the seam override goes away
        // entirely — fakeFolderContext(intraVox: $base) covers it.
        // Built through the real DI ctor (fase-6 Track 3a: no PageService subclass).
        return $this->buildRealPageService(array_filter([
            'pageIndexService' => $index,
            'logger' => $this->createMock(LoggerInterface::class),
            'folderContext' => $this->fakeFolderContext(intraVox: $base),
        ]));
    }

    // --- repairEntities ---

    public function testRepairSkipsNonLanguageFoldersAndSpecialJsonFiles(): void {
        // An 'en' language folder with one repairable page, plus a '_media' folder
        // that must be skipped entirely, plus special JSONs that must be ignored.
        $page = $this->pageFile('/IntraVox/en/about.json', ['title' => 'A &amp; B']);
        $nav = $this->pageFile('/IntraVox/en/navigation.json', ['title' => 'Nav &amp; stuff']);
        $en = $this->makeFolder('/IntraVox/en', [
            'about.json' => $page,
            'navigation.json' => $nav,
        ]);
        $media = $this->makeFolder('/IntraVox/_media', [
            'junk.json' => $this->pageFile('/IntraVox/_media/junk.json', ['title' => 'X &amp; Y']),
        ]);

        $stats = $this->makeService([$en, $media])->repairEntities(dryRun: true);

        // Only the page JSON in the language folder is scanned; nav.json and the
        // _media folder are skipped.
        $this->assertSame(1, $stats['scanned']);
        $this->assertSame(1, $stats['changed'], 'the &amp; entity decodes, so the page counts as changed');
        $this->assertSame(['/IntraVox/en/about.json'], $stats['files']);
    }

    public function testDryRunCountsButNeverWrites(): void {
        $page = $this->pageFile('/IntraVox/en/about.json', ['title' => 'A &amp; B']);
        $en = $this->makeFolder('/IntraVox/en', ['about.json' => $page]);

        $stats = $this->makeService([$en])->repairEntities(dryRun: true);

        $this->assertSame(1, $stats['changed']);
        $this->assertSame([], $this->written, 'a dry run must not write any file');
    }

    public function testNonDryRunWritesOnlyChangedFiles(): void {
        $changing = $this->pageFile('/IntraVox/en/a.json', ['title' => 'A &amp; B']);
        $clean = $this->pageFile('/IntraVox/en/b.json', ['title' => 'Plain title']);
        $en = $this->makeFolder('/IntraVox/en', [
            'a.json' => $changing,
            'b.json' => $clean,
        ]);

        $stats = $this->makeService([$en])->repairEntities(dryRun: false);

        $this->assertSame(2, $stats['scanned']);
        $this->assertSame(1, $stats['changed']);
        $this->assertArrayHasKey('/IntraVox/en/a.json', $this->written, 'the changed file is written');
        $this->assertArrayNotHasKey('/IntraVox/en/b.json', $this->written, 'an unchanged file is left alone');
    }

    public function testRepairRecursesIntoSubfolders(): void {
        $nested = $this->pageFile('/IntraVox/en/news/item.json', ['title' => 'News &amp; more']);
        $newsFolder = $this->makeFolder('/IntraVox/en/news', ['item.json' => $nested]);
        $en = $this->makeFolder('/IntraVox/en', ['news' => $newsFolder]);

        $stats = $this->makeService([$en])->repairEntities(dryRun: true);

        $this->assertSame(1, $stats['scanned']);
        $this->assertSame(['/IntraVox/en/news/item.json'], $stats['files']);
    }

    // --- rebuildIndex ---

    public function testRebuildIndexDryRunDoesNotClearOrWriteTheIndex(): void {
        $page = $this->pageFile('/IntraVox/en/about.json', ['uniqueId' => 'page-a', 'title' => 'About']);
        $en = $this->makeFolder('/IntraVox/en', ['about.json' => $page]);

        $index = $this->createMock(PageIndexService::class);
        $index->expects($this->never())->method('clearAll');
        $index->expects($this->never())->method('indexPage');

        $stats = $this->makeService([$en], $index)->rebuildIndex(dryRun: true);

        $this->assertArrayHasKey('scanned', $stats);
        $this->assertArrayHasKey('indexed', $stats);
        $this->assertArrayHasKey('languages', $stats);
        $this->assertArrayHasKey('en', $stats['languages']);
    }

    public function testRebuildIndexClearsBeforeReindexingOnARealRun(): void {
        $page = $this->pageFile('/IntraVox/en/about.json', ['uniqueId' => 'page-a', 'title' => 'About']);
        $en = $this->makeFolder('/IntraVox/en', ['about.json' => $page]);

        $calls = [];
        $index = $this->createMock(PageIndexService::class);
        $index->method('clearAll')->willReturnCallback(function () use (&$calls) {
            $calls[] = 'clearAll';
        });
        $index->method('indexPage')->willReturnCallback(function () use (&$calls) {
            $calls[] = 'indexPage';
        });

        $this->makeService([$en], $index)->rebuildIndex(dryRun: false);

        $this->assertNotEmpty($calls);
        $this->assertSame('clearAll', $calls[0], 'the index is cleared before anything is re-indexed');
    }

    public function testRebuildIndexOnlyVisitsLanguageFolders(): void {
        $en = $this->makeFolder('/IntraVox/en', [
            'about.json' => $this->pageFile('/IntraVox/en/about.json', ['uniqueId' => 'page-a', 'title' => 'A']),
        ]);
        $resources = $this->makeFolder('/IntraVox/_resources', [
            'x.json' => $this->pageFile('/IntraVox/_resources/x.json', ['uniqueId' => 'page-x', 'title' => 'X']),
        ]);

        $index = $this->createMock(PageIndexService::class);

        $stats = $this->makeService([$en, $resources], $index)->rebuildIndex(dryRun: true);

        $this->assertArrayHasKey('en', $stats['languages']);
        $this->assertArrayNotHasKey('_resources', $stats['languages'], '_resources is not a language folder');
    }
}
