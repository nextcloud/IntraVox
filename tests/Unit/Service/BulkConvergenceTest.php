<?php
declare(strict_types=1);

namespace OCA\IntraVox\Tests\Unit\Service;

use OCA\IntraVox\Service\BulkOperationService;
use OCA\IntraVox\Service\Cache\PageCacheService;
use OCA\IntraVox\Service\Locator\PageLocator;
use OCA\IntraVox\Service\PageIndexService;
use OCA\IntraVox\Service\PermissionService;
use OCA\IntraVox\Service\Structure\PageStructureService;
use OCA\IntraVox\Service\Write\PageWriteService;
use OCA\IntraVox\Tests\Unit\Service\Harness\BuildsCollaboratorFixtures;
use OCP\Files\File;
use OCP\Files\FileInfo;
use OCP\Files\Folder;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Repeating a bulk delete converges instead of reporting failures.
 *
 * Bulk operations are not transactional and never will be — they act on the
 * filesystem, item by item, and answer 207 Multi-Status. That is the right design
 * for what they do. It does mean a client has to be able to retry, and retrying
 * was where this fell over: a page deleted on the first attempt threw "Page not
 * found" on the second, which was counted as a failure.
 *
 * So a resumed run — after a timeout, a dropped connection, a migration picking up
 * where it left off — came back full of errors describing work that had actually
 * succeeded, with nothing to distinguish them from real ones. The operation was
 * idempotent in effect and non-idempotent in its answer, which is the worst
 * combination: safe to repeat, impossible to trust.
 *
 * This is deliberately the cheap half of the plan's phase 3. Convergent items make
 * a plain retry safe without an operation ledger, a new table or a migration —
 * and a ledger is only worth building once something needs more than "run it
 * again".
 */
class BulkConvergenceTest extends TestCase {
    use \OCA\IntraVox\Tests\Unit\Service\Harness\BuildsPageRead;
    use BuildsCollaboratorFixtures;
    private BulkOperationService $service;
    /** getPage(id) behaviour a test installs (fase-4 C6: getPage → PageReadService). */
    private \Closure $getPageFn;

    protected function setUp(): void {
        parent::setUp();
        $this->getPageFn = fn(string $id) => null;
        $pageRead = $this->fakePageReadFrom(fn(string $id) => ($this->getPageFn)($id));
        // The delete path goes to a REAL PageWriteService (god-class dissolution).
        // Its fixture holds 'page-here' so a permitted delete succeeds; any other
        // uniqueId resolves to nothing → PageNotFoundException, which convergence
        // treats as done. (Most tests never reach it: the not-found / permission /
        // genuine-error cases are all decided by the getPage gate upstream.)
        $this->service = new BulkOperationService(
            $this->deleteCapableWriteService(),
            $this->doubleOrBuild(PageStructureService::class),
            $this->fakeCacheInvalidator(),
            $pageRead,
            $this->createMock(LoggerInterface::class)
        );
    }

    /**
     * A real PageWriteService whose deletePage('page-here') resolves + deletes
     * cleanly (the folder is present in its FolderContext) and whose any-other-id
     * delete resolves to nothing → PageNotFoundException.
     */
    private function deleteCapableWriteService(): PageWriteService {
        $pageJson = $this->makeFile('/IntraVox/en/here.json', ['uniqueId' => 'page-here', 'title' => 'Still here']);
        $pageFolder = $this->createMock(Folder::class);
        $pageFolder->method('getName')->willReturn('here');
        $pageFolder->method('getType')->willReturn(FileInfo::TYPE_FOLDER);
        $pageFolder->method('getPath')->willReturn('/IntraVox/en/here');
        $pageFolder->method('getDirectoryListing')->willReturn([]);
        $pageFolder->method('delete')->willReturnCallback(function (): void {});
        $lang = $this->makeFolder('/IntraVox/en', ['here.json' => $pageJson, 'here' => $pageFolder]);
        $base = $this->makeFolder('/IntraVox', ['en' => $lang]);

        $ls = $this->createMock(\OCA\IntraVox\Service\LanguageService::class);
        $index = $this->createMock(PageIndexService::class);
        $index->method('findByUniqueId')->willReturn(null);

        return new PageWriteService(
            new \OCA\IntraVox\Service\Util\PageIdUtils(),
            $this->createMock(\OCP\EventDispatcher\IEventDispatcher::class),
            $this->createMock(LoggerInterface::class),
            $this->createMock(\OCP\IUserSession::class),
            $this->createMock(\OCA\IntraVox\Service\Version\PageVersionService::class),
            $index,
            $ls,
            $this->fakeFolderContext(intraVox: $base, languageFolder: $lang),
            new PageLocator($index, $this->createMock(LoggerInterface::class)),
            $this->fakeHomepageResolver(null),
            $this->fakeCacheInvalidator(),
            $this->doubleOrBuild(\OCA\IntraVox\Service\Sanitize\PageShapeSanitizer::class),
            $this->createMock(\OCA\IntraVox\Service\Media\PageMediaService::class),
            $this->createMock(PageCacheService::class),
            new \OCA\IntraVox\Service\Path\PageDepthValidator(new \OCA\IntraVox\Service\Path\PagePathHelper(), $ls),
        );
    }

    public function testDeletingAnAlreadyDeletedPageCountsAsDone(): void {
        $this->getPageFn = function (string $id): array {
            throw new \Exception('Page not found');
        };

        $result = $this->service->bulkDelete(['page-gone'], true);

        $this->assertSame(0, $result->toArray()['failed'], 'A retry must not report work that already succeeded as an error');
        $this->assertSame(1, $result->toArray()['deleted']);
    }

    public function testAGenuineFailureIsStillAFailure(): void {
        $this->getPageFn = function (string $id): array {
            throw new \Exception('Storage is not writable');
        };

        $result = $this->service->bulkDelete(['page-1'], true);

        $this->assertSame(1, $result->toArray()['failed'], 'Only the not-found case converges; everything else stays an error');
        $this->assertSame(0, $result->toArray()['deleted']);
    }

    public function testPermissionDenialIsNotSwallowedByConvergence(): void {
        $this->getPageFn = fn(string $id) => [
            'title' => 'Protected',
            'permissions' => ['canDelete' => false],
        ];

        $result = $this->service->bulkDelete(['page-1'], true);

        $this->assertSame(1, $result->toArray()['failed']);
        $this->assertStringContainsString('Permission denied', implode(' ', $result->toArray()['errors']));
    }

    /**
     * A mixed batch is the realistic retry: some already gone, some still there.
     */
    public function testAMixedRetryReportsEverythingAsDone(): void {
        $this->getPageFn = static function (string $id): array {
            if ($id === 'page-gone') {
                throw new \Exception('Page not found');
            }
            return ['title' => 'Still here', 'permissions' => ['canDelete' => true]];
        };

        $result = $this->service->bulkDelete(['page-gone', 'page-here'], true)->toArray();

        $this->assertSame(0, $result['failed']);
        $this->assertSame(2, $result['deleted']);
        $this->assertTrue($result['success']);
    }
}
