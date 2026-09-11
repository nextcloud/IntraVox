<?php
declare(strict_types=1);

namespace OCA\IntraVox\Tests\Unit\Service;

use OCA\IntraVox\Service\BulkOperationService;
use OCA\IntraVox\Service\PageService;
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

    private PageService $pageService;
    private BulkOperationService $service;
    /** getPage(id) behaviour a test installs (fase-4 C6: getPage → PageReadService). */
    private \Closure $getPageFn;

    protected function setUp(): void {
        parent::setUp();
        $this->pageService = $this->createMock(PageService::class);
        $this->getPageFn = fn(string $id) => null;
        $pageRead = $this->fakePageReadFrom(fn(string $id) => ($this->getPageFn)($id));
        $this->service = new BulkOperationService(
            $this->pageService,
            $pageRead,
            $this->createMock(LoggerInterface::class)
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
