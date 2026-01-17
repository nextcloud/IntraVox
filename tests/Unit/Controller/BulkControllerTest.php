<?php
declare(strict_types=1);

namespace OCA\IntraVox\Tests\Unit\Controller;

use OCA\IntraVox\Controller\BulkController;
use OCA\IntraVox\Service\BulkOperationService;
use OCA\IntraVox\Service\BulkOperationResult;
use OCP\AppFramework\Http;
use OCP\IRequest;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Unit tests for BulkController
 *
 * Tests cover:
 * - Bulk delete operations
 * - Bulk move operations
 * - Bulk update operations
 * - Operation validation (dry-run)
 * - Error handling
 * - Permission checks
 */
class BulkControllerTest extends TestCase {
    private BulkController $controller;
    private BulkOperationService $bulkService;
    private LoggerInterface $logger;
    private IRequest $request;

    protected function setUp(): void {
        parent::setUp();

        $this->bulkService = $this->createMock(BulkOperationService::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->request = $this->createMock(IRequest::class);

        $this->controller = new BulkController(
            'intravox',
            $this->request,
            $this->bulkService,
            $this->logger
        );
    }

    // ==========================================
    // validateOperation Tests
    // ==========================================

    public function testValidateOperationReturnsValidationResult(): void {
        $pageIds = ['page-1', 'page-2', 'page-3'];
        $validation = [
            'valid' => [
                ['pageId' => 'page-1', 'title' => 'Page 1'],
                ['pageId' => 'page-2', 'title' => 'Page 2']
            ],
            'invalid' => [
                ['pageId' => 'page-3', 'reason' => 'Permission denied']
            ]
        ];

        $this->bulkService->method('validateBatchPermissions')
            ->with($pageIds, 'delete')
            ->willReturn($validation);

        $response = $this->controller->validateOperation($pageIds, 'delete');

        $this->assertEquals(Http::STATUS_OK, $response->getStatus());
        $data = $response->getData();
        $this->assertEquals('delete', $data['operation']);
        $this->assertEquals(3, $data['total']);
        $this->assertEquals(2, $data['canProceed']);
        $this->assertEquals(1, $data['willFail']);
    }

    public function testValidateOperationReturnsBadRequestForEmptyPageIds(): void {
        $response = $this->controller->validateOperation([], 'delete');

        $this->assertEquals(Http::STATUS_BAD_REQUEST, $response->getStatus());
        $this->assertStringContainsString('No page IDs', $response->getData()['error']);
    }

    public function testValidateOperationReturnsBadRequestForInvalidOperation(): void {
        $response = $this->controller->validateOperation(['page-1'], 'invalid');

        $this->assertEquals(Http::STATUS_BAD_REQUEST, $response->getStatus());
        $this->assertStringContainsString('Invalid operation', $response->getData()['error']);
    }

    public function testValidateOperationAcceptsValidOperations(): void {
        $this->bulkService->method('validateBatchPermissions')
            ->willReturn(['valid' => [], 'invalid' => []]);

        foreach (['delete', 'move', 'update'] as $operation) {
            $response = $this->controller->validateOperation(['page-1'], $operation);
            $this->assertEquals(Http::STATUS_OK, $response->getStatus());
        }
    }

    // ==========================================
    // deletePages Tests
    // ==========================================

    public function testDeletePagesSuccessful(): void {
        $result = new BulkOperationResult('delete');
        $result->addSucceeded('page-1', 'Page 1');
        $result->addSucceeded('page-2', 'Page 2');

        $this->request->method('getParam')
            ->willReturnCallback(function ($key, $default = null) {
                if ($key === 'pageIds') return ['page-1', 'page-2'];
                if ($key === 'deleteChildren') return true;
                if ($key === 'dryRun') return false;
                return $default;
            });

        $this->bulkService->method('bulkDelete')
            ->with(['page-1', 'page-2'], true)
            ->willReturn($result);

        $response = $this->controller->deletePages();

        $this->assertEquals(Http::STATUS_OK, $response->getStatus());
        $data = $response->getData();
        $this->assertTrue($data['success']);
        $this->assertEquals(2, $data['deleted']);
        $this->assertEquals(0, $data['failed']);
    }

    public function testDeletePagesPartialSuccess(): void {
        $result = new BulkOperationResult('delete');
        $result->addSucceeded('page-1', 'Page 1');
        $result->addFailed('page-2', 'Permission denied');

        $this->request->method('getParam')
            ->willReturnCallback(function ($key, $default = null) {
                if ($key === 'pageIds') return ['page-1', 'page-2'];
                if ($key === 'dryRun') return false;
                return $default;
            });

        $this->bulkService->method('bulkDelete')->willReturn($result);

        $response = $this->controller->deletePages();

        $this->assertEquals(Http::STATUS_MULTI_STATUS, $response->getStatus());
        $data = $response->getData();
        $this->assertFalse($data['success']);
        $this->assertEquals(1, $data['deleted']);
        $this->assertEquals(1, $data['failed']);
    }

    public function testDeletePagesReturnsBadRequestForEmptyPageIds(): void {
        $this->request->method('getParam')
            ->willReturnCallback(function ($key, $default = null) {
                if ($key === 'pageIds') return [];
                return $default;
            });

        $response = $this->controller->deletePages();

        $this->assertEquals(Http::STATUS_BAD_REQUEST, $response->getStatus());
    }

    public function testDeletePagesDryRunReturnsValidation(): void {
        $this->request->method('getParam')
            ->willReturnCallback(function ($key, $default = null) {
                if ($key === 'pageIds') return ['page-1'];
                if ($key === 'dryRun') return true;
                return $default;
            });

        $this->bulkService->method('validateBatchPermissions')
            ->willReturn([
                'valid' => [['pageId' => 'page-1', 'title' => 'Page 1']],
                'invalid' => []
            ]);

        $response = $this->controller->deletePages();

        $this->assertEquals(Http::STATUS_OK, $response->getStatus());
        $this->assertArrayHasKey('canProceed', $response->getData());
    }

    // ==========================================
    // movePages Tests
    // ==========================================

    public function testMovePagesSuccessful(): void {
        $result = new BulkOperationResult('move');
        $result->addSucceeded('page-1', 'Page 1');

        $this->request->method('getParam')
            ->willReturnCallback(function ($key, $default = null) {
                if ($key === 'pageIds') return ['page-1'];
                if ($key === 'targetParentId') return 'parent-page';
                if ($key === 'dryRun') return false;
                return $default;
            });

        $this->bulkService->method('bulkMove')
            ->with(['page-1'], 'parent-page')
            ->willReturn($result);

        $response = $this->controller->movePages();

        $this->assertEquals(Http::STATUS_OK, $response->getStatus());
        $data = $response->getData();
        $this->assertTrue($data['success']);
        $this->assertEquals(1, $data['moved']);
    }

    public function testMovePagesReturnsBadRequestWithoutTargetParent(): void {
        $this->request->method('getParam')
            ->willReturnCallback(function ($key, $default = null) {
                if ($key === 'pageIds') return ['page-1'];
                if ($key === 'targetParentId') return null;
                return $default;
            });

        $response = $this->controller->movePages();

        $this->assertEquals(Http::STATUS_BAD_REQUEST, $response->getStatus());
        $this->assertStringContainsString('targetParentId', $response->getData()['error']);
    }

    public function testMovePagesDryRunReturnsValidation(): void {
        $this->request->method('getParam')
            ->willReturnCallback(function ($key, $default = null) {
                if ($key === 'pageIds') return ['page-1'];
                if ($key === 'targetParentId') return 'parent-page';
                if ($key === 'dryRun') return true;
                return $default;
            });

        $this->bulkService->method('validateBatchPermissions')
            ->willReturn(['valid' => [], 'invalid' => []]);

        $response = $this->controller->movePages();

        $this->assertEquals(Http::STATUS_OK, $response->getStatus());
    }

    // ==========================================
    // updatePages Tests
    // ==========================================

    public function testUpdatePagesSuccessful(): void {
        $result = new BulkOperationResult('update');
        $result->addSucceeded('page-1', 'Page 1');
        $result->addSucceeded('page-2', 'Page 2');

        $this->request->method('getParam')
            ->willReturnCallback(function ($key, $default = null) {
                if ($key === 'pageIds') return ['page-1', 'page-2'];
                if ($key === 'updates') return ['metadata' => ['category' => 'news']];
                if ($key === 'dryRun') return false;
                return $default;
            });

        $this->bulkService->method('bulkUpdate')
            ->with(['page-1', 'page-2'], ['metadata' => ['category' => 'news']])
            ->willReturn($result);

        $response = $this->controller->updatePages();

        $this->assertEquals(Http::STATUS_OK, $response->getStatus());
        $data = $response->getData();
        $this->assertTrue($data['success']);
        $this->assertEquals(2, $data['updated']);
    }

    public function testUpdatePagesReturnsBadRequestForEmptyUpdates(): void {
        $this->request->method('getParam')
            ->willReturnCallback(function ($key, $default = null) {
                if ($key === 'pageIds') return ['page-1'];
                if ($key === 'updates') return [];
                return $default;
            });

        $response = $this->controller->updatePages();

        $this->assertEquals(Http::STATUS_BAD_REQUEST, $response->getStatus());
        $this->assertStringContainsString('updates', $response->getData()['error']);
    }

    public function testUpdatePagesPartialFailure(): void {
        $result = new BulkOperationResult('update');
        $result->addSucceeded('page-1', 'Page 1');
        $result->addFailed('page-2', 'Page not found');

        $this->request->method('getParam')
            ->willReturnCallback(function ($key, $default = null) {
                if ($key === 'pageIds') return ['page-1', 'page-2'];
                if ($key === 'updates') return ['title' => 'New Title'];
                if ($key === 'dryRun') return false;
                return $default;
            });

        $this->bulkService->method('bulkUpdate')->willReturn($result);

        $response = $this->controller->updatePages();

        $this->assertEquals(Http::STATUS_MULTI_STATUS, $response->getStatus());
        $data = $response->getData();
        $this->assertEquals(1, $data['updated']);
        $this->assertEquals(1, $data['failed']);
    }

    // ==========================================
    // Error Handling Tests
    // ==========================================

    public function testDeletePagesReturnsErrorOnException(): void {
        $this->request->method('getParam')
            ->willReturnCallback(function ($key, $default = null) {
                if ($key === 'pageIds') return ['page-1'];
                if ($key === 'dryRun') return false;
                return $default;
            });

        $this->bulkService->method('bulkDelete')
            ->willThrowException(new \Exception('Database error'));

        $response = $this->controller->deletePages();

        $this->assertEquals(Http::STATUS_INTERNAL_SERVER_ERROR, $response->getStatus());
        $this->assertStringContainsString('failed', $response->getData()['error']);
    }

    public function testMovePagesReturnsErrorOnException(): void {
        $this->request->method('getParam')
            ->willReturnCallback(function ($key, $default = null) {
                if ($key === 'pageIds') return ['page-1'];
                if ($key === 'targetParentId') return 'parent';
                if ($key === 'dryRun') return false;
                return $default;
            });

        $this->bulkService->method('bulkMove')
            ->willThrowException(new \Exception('Move failed'));

        $response = $this->controller->movePages();

        $this->assertEquals(Http::STATUS_INTERNAL_SERVER_ERROR, $response->getStatus());
    }
}
