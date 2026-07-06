<?php
declare(strict_types=1);

namespace OCA\IntraVox\Tests\Unit\Service;

use OCA\IntraVox\Service\BulkOperationService;
use OCA\IntraVox\Service\PageService;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Unit tests for BulkOperationService's deferred-cache-clear behaviour.
 *
 * The service must wrap its per-item loop in beginDeferredClear()/
 * endDeferredClear() so the expensive distributed cache clear happens once per
 * batch instead of once per item, and must release the deferral even if an item
 * throws. We assert on that public seam (clearCache() itself is private).
 */
class BulkOperationServiceTest extends TestCase {

    private function pageWith(array $perms): array {
        return ['id' => 'page-x', 'title' => 'X', 'permissions' => $perms];
    }

    public function testBulkDeleteWrapsLoopInSingleDeferredClear(): void {
        $pageService = $this->createMock(PageService::class);
        $pageService->method('getPage')->willReturn($this->pageWith(['canDelete' => true]));

        $pageService->expects($this->once())->method('beginDeferredClear');
        $pageService->expects($this->once())->method('endDeferredClear');
        $pageService->expects($this->exactly(3))->method('deletePage');

        $svc = new BulkOperationService($pageService, $this->createMock(LoggerInterface::class));
        $result = $svc->bulkDelete(['page-1', 'page-2', 'page-3']);

        $this->assertEquals(3, $result->successCount);
    }

    public function testBulkMoveWrapsLoopInSingleDeferredClear(): void {
        $pageService = $this->createMock(PageService::class);
        // Target parent is writable, and each moved page is writable.
        $pageService->method('getPage')->willReturn($this->pageWith(['canWrite' => true]));

        $pageService->expects($this->once())->method('beginDeferredClear');
        $pageService->expects($this->once())->method('endDeferredClear');
        $pageService->expects($this->exactly(2))->method('movePage');

        $svc = new BulkOperationService($pageService, $this->createMock(LoggerInterface::class));
        $result = $svc->bulkMove(['page-1', 'page-2'], 'page-target');

        $this->assertEquals(2, $result->successCount);
    }

    public function testBulkUpdateWrapsLoopInSingleDeferredClear(): void {
        $pageService = $this->createMock(PageService::class);
        $pageService->method('getPage')->willReturn($this->pageWith(['canWrite' => true]));

        $pageService->expects($this->once())->method('beginDeferredClear');
        $pageService->expects($this->once())->method('endDeferredClear');
        $pageService->expects($this->exactly(2))->method('updatePage');

        $svc = new BulkOperationService($pageService, $this->createMock(LoggerInterface::class));
        $result = $svc->bulkUpdate(['page-1', 'page-2'], ['status' => 'published']);

        $this->assertEquals(2, $result->successCount);
    }

    public function testBulkDeleteReleasesDeferredClearWhenItemThrows(): void {
        $pageService = $this->createMock(PageService::class);
        $pageService->method('getPage')->willReturn($this->pageWith(['canDelete' => true]));
        // Every deletePage() throws — the loop must still release the deferral.
        $pageService->method('deletePage')
            ->willThrowException(new \Exception('boom'));

        $pageService->expects($this->once())->method('beginDeferredClear');
        $pageService->expects($this->once())->method('endDeferredClear');

        $svc = new BulkOperationService($pageService, $this->createMock(LoggerInterface::class));
        $result = $svc->bulkDelete(['page-1', 'page-2']);

        // Failures are recorded, not thrown, and the deferral was released.
        $this->assertEquals(0, $result->successCount);
        $this->assertEquals(2, $result->failCount);
    }

    public function testBulkMoveReleasesDeferredClearOnUnwritableTargetEarlyReturn(): void {
        // When the target parent is not writable the method early-returns BEFORE
        // the loop; the begin/end seam must not run in that path (no deferral
        // started), so neither is expected here.
        $pageService = $this->createMock(PageService::class);
        $pageService->method('getPage')
            ->willReturn($this->pageWith(['canWrite' => false]));

        $pageService->expects($this->never())->method('beginDeferredClear');
        $pageService->expects($this->never())->method('endDeferredClear');
        $pageService->expects($this->never())->method('movePage');

        $svc = new BulkOperationService($pageService, $this->createMock(LoggerInterface::class));
        $result = $svc->bulkMove(['page-1'], 'page-target');

        $this->assertEquals(1, $result->failCount);
    }
}
