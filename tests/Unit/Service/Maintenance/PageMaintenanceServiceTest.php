<?php
declare(strict_types=1);

namespace OCA\IntraVox\Tests\Unit\Service\Maintenance;

use OCA\IntraVox\Service\Locator\PageLocator;
use OCA\IntraVox\Service\Maintenance\PageMaintenanceService;
use OCA\IntraVox\Service\PageIndexService;
use OCA\IntraVox\Service\Sanitize\HtmlSanitizer;
use OCP\Files\File;
use OCP\Files\FileInfo;
use OCP\Files\Folder;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Direct tests for PageMaintenanceService. Drives the service standalone (root
 * folder passed in, not resolved via a seam), confirming the root-as-parameter
 * contract for the CLI maintenance methods repairEntities() and rebuildIndex().
 * These pins protect the occ commands' observable contract: the stats shape,
 * the language-folder filter, the file-type filter, and — crucially — that a
 * dry run counts but never writes, and that rebuildIndex clears the index only
 * after the tree is readable.
 */
class PageMaintenanceServiceTest extends TestCase {

    private array $written = [];

    protected function setUp(): void {
        $this->written = [];
    }

    private function service(?PageIndexService $index = null): PageMaintenanceService {
        // The real (final) HtmlSanitizer with its real leaf deps decodes entities;
        // build it via its no-arg-friendly constructor mocks.
        $locator = $this->createMock(PageLocator::class);
        // cachedDirectoryListing just forwards to getDirectoryListing on our mocks.
        $locator->method('cachedDirectoryListing')->willReturnCallback(
            fn(Folder $f) => $f->getDirectoryListing()
        );

        return new PageMaintenanceService(
            $index ?? $this->createMock(PageIndexService::class),
            $locator,
            $this->realHtmlSanitizer(),
            $this->createMock(LoggerInterface::class),
        );
    }

    private function realHtmlSanitizer(): HtmlSanitizer {
        // HtmlSanitizer is final; build the real one from its constructor deps.
        $ref = new \ReflectionClass(HtmlSanitizer::class);
        $ctor = $ref->getConstructor();
        $args = [];
        foreach ($ctor?->getParameters() ?? [] as $p) {
            $t = $p->getType();
            $args[] = $t instanceof \ReflectionNamedType && !$t->isBuiltin()
                ? $this->createMock($t->getName())
                : ($p->isOptional() ? $p->getDefaultValue() : null);
        }
        return $ref->newInstanceArgs($args);
    }

    private function file(string $path, array $json): File {
        $f = $this->createMock(File::class);
        $f->method('getName')->willReturn(basename($path));
        $f->method('getType')->willReturn(FileInfo::TYPE_FILE);
        $f->method('getPath')->willReturn($path);
        $f->method('getId')->willReturn(abs(crc32($path)));
        $f->method('getContent')->willReturn(json_encode($json));
        $f->method('putContent')->willReturnCallback(function ($c) use ($path) {
            $this->written[$path] = $c;
        });
        return $f;
    }

    private function folder(string $path, array $children = []): Folder {
        $f = $this->createMock(Folder::class);
        $f->method('getName')->willReturn(basename($path));
        $f->method('getType')->willReturn(FileInfo::TYPE_FOLDER);
        $f->method('getPath')->willReturn($path);
        $f->method('getId')->willReturn(abs(crc32($path)));
        $f->method('getDirectoryListing')->willReturn(array_values($children));
        return $f;
    }

    public function testRepairDryRunCountsButDoesNotWrite(): void {
        $page = $this->file('/IntraVox/en/about.json', ['title' => 'A &amp; B']);
        $en = $this->folder('/IntraVox/en', ['about.json' => $page]);
        $root = $this->folder('/IntraVox', ['en' => $en]);

        $stats = $this->service()->repairEntities($root, dryRun: true);

        $this->assertSame(1, $stats['scanned']);
        $this->assertSame(1, $stats['changed']);
        $this->assertSame([], $this->written, 'a dry run writes nothing');
    }

    public function testRepairOnlyVisitsLanguageFolders(): void {
        $en = $this->folder('/IntraVox/en', [
            'about.json' => $this->file('/IntraVox/en/about.json', ['title' => 'A &amp; B']),
        ]);
        $media = $this->folder('/IntraVox/_media', [
            'x.json' => $this->file('/IntraVox/_media/x.json', ['title' => 'X &amp; Y']),
        ]);
        $root = $this->folder('/IntraVox', ['en' => $en, '_media' => $media]);

        $stats = $this->service()->repairEntities($root, dryRun: true);

        $this->assertSame(1, $stats['scanned'], '_media is skipped');
    }

    public function testRebuildIndexClearsBeforeReindexing(): void {
        $page = $this->file('/IntraVox/en/about.json', ['uniqueId' => 'page-a', 'title' => 'About']);
        $en = $this->folder('/IntraVox/en', ['about.json' => $page]);
        $root = $this->folder('/IntraVox', ['en' => $en]);

        $calls = [];
        $index = $this->createMock(PageIndexService::class);
        $index->method('clearAll')->willReturnCallback(function () use (&$calls) {
            $calls[] = 'clearAll';
        });
        $index->method('indexPage')->willReturnCallback(function () use (&$calls) {
            $calls[] = 'indexPage';
        });

        $this->service($index)->rebuildIndex($root, dryRun: false);

        $this->assertNotEmpty($calls);
        $this->assertSame('clearAll', $calls[0], 'the index is cleared before anything is re-indexed');
    }

    public function testRebuildIndexDryRunNeitherClearsNorIndexes(): void {
        $en = $this->folder('/IntraVox/en', [
            'about.json' => $this->file('/IntraVox/en/about.json', ['uniqueId' => 'page-a', 'title' => 'A']),
        ]);
        $root = $this->folder('/IntraVox', ['en' => $en]);

        $index = $this->createMock(PageIndexService::class);
        $index->expects($this->never())->method('clearAll');
        $index->expects($this->never())->method('indexPage');

        $stats = $this->service($index)->rebuildIndex($root, dryRun: true);

        $this->assertArrayHasKey('en', $stats['languages']);
    }

    // --- migrated from PageMaintenanceRepairTest (facade retired) ---

    public function testRepairSkipsNonLanguageFoldersAndSpecialJsonFiles(): void {
        // An 'en' language folder with one repairable page, plus a '_media' folder
        // that must be skipped entirely, plus special JSONs that must be ignored.
        $page = $this->file('/IntraVox/en/about.json', ['title' => 'A &amp; B']);
        $nav = $this->file('/IntraVox/en/navigation.json', ['title' => 'Nav &amp; stuff']);
        $en = $this->folder('/IntraVox/en', [
            'about.json' => $page,
            'navigation.json' => $nav,
        ]);
        $media = $this->folder('/IntraVox/_media', [
            'junk.json' => $this->file('/IntraVox/_media/junk.json', ['title' => 'X &amp; Y']),
        ]);
        $root = $this->folder('/IntraVox', ['en' => $en, '_media' => $media]);

        $stats = $this->service()->repairEntities($root, dryRun: true);

        // Only the page JSON in the language folder is scanned; nav.json and the
        // _media folder are skipped.
        $this->assertSame(1, $stats['scanned']);
        $this->assertSame(1, $stats['changed'], 'the &amp; entity decodes, so the page counts as changed');
        $this->assertSame(['/IntraVox/en/about.json'], $stats['files']);
    }

    public function testNonDryRunWritesOnlyChangedFiles(): void {
        $changing = $this->file('/IntraVox/en/a.json', ['title' => 'A &amp; B']);
        $clean = $this->file('/IntraVox/en/b.json', ['title' => 'Plain title']);
        $en = $this->folder('/IntraVox/en', [
            'a.json' => $changing,
            'b.json' => $clean,
        ]);
        $root = $this->folder('/IntraVox', ['en' => $en]);

        $stats = $this->service()->repairEntities($root, dryRun: false);

        $this->assertSame(2, $stats['scanned']);
        $this->assertSame(1, $stats['changed']);
        $this->assertArrayHasKey('/IntraVox/en/a.json', $this->written, 'the changed file is written');
        $this->assertArrayNotHasKey('/IntraVox/en/b.json', $this->written, 'an unchanged file is left alone');
    }

    public function testRepairRecursesIntoSubfolders(): void {
        $nested = $this->file('/IntraVox/en/news/item.json', ['title' => 'News &amp; more']);
        $newsFolder = $this->folder('/IntraVox/en/news', ['item.json' => $nested]);
        $en = $this->folder('/IntraVox/en', ['news' => $newsFolder]);
        $root = $this->folder('/IntraVox', ['en' => $en]);

        $stats = $this->service()->repairEntities($root, dryRun: true);

        $this->assertSame(1, $stats['scanned']);
        $this->assertSame(['/IntraVox/en/news/item.json'], $stats['files']);
    }

    public function testRebuildIndexDryRunDoesNotClearOrWriteTheIndex(): void {
        $page = $this->file('/IntraVox/en/about.json', ['uniqueId' => 'page-a', 'title' => 'About']);
        $en = $this->folder('/IntraVox/en', ['about.json' => $page]);
        $root = $this->folder('/IntraVox', ['en' => $en]);

        $index = $this->createMock(PageIndexService::class);
        $index->expects($this->never())->method('clearAll');
        $index->expects($this->never())->method('indexPage');

        $stats = $this->service($index)->rebuildIndex($root, dryRun: true);

        $this->assertArrayHasKey('scanned', $stats);
        $this->assertArrayHasKey('indexed', $stats);
        $this->assertArrayHasKey('languages', $stats);
        $this->assertArrayHasKey('en', $stats['languages']);
    }

    public function testRebuildIndexOnlyVisitsLanguageFolders(): void {
        $en = $this->folder('/IntraVox/en', [
            'about.json' => $this->file('/IntraVox/en/about.json', ['uniqueId' => 'page-a', 'title' => 'A']),
        ]);
        $resources = $this->folder('/IntraVox/_resources', [
            'x.json' => $this->file('/IntraVox/_resources/x.json', ['uniqueId' => 'page-x', 'title' => 'X']),
        ]);
        $root = $this->folder('/IntraVox', ['en' => $en, '_resources' => $resources]);

        $index = $this->createMock(PageIndexService::class);

        $stats = $this->service($index)->rebuildIndex($root, dryRun: true);

        $this->assertArrayHasKey('en', $stats['languages']);
        $this->assertArrayNotHasKey('_resources', $stats['languages'], '_resources is not a language folder');
    }
}
