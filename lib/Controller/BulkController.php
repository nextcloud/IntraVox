<?php
declare(strict_types=1);

namespace OCA\IntraVox\Controller;

use OCA\IntraVox\Service\BulkOperationService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\DataResponse;
use OCP\IGroupManager;
use OCP\IRequest;
use OCP\IUserSession;
use Psr\Log\LoggerInterface;

/**
 * API Controller for bulk operations on IntraVox pages
 *
 * Provides endpoints for:
 * - Bulk delete
 * - Bulk move
 * - Bulk update
 * - Operation validation (dry-run)
 *
 * All bulk operations require admin privileges for security.
 */
class BulkController extends Controller {
    use ApiErrorTrait;

    public function __construct(
        string $appName,
        IRequest $request,
        private BulkOperationService $bulkService,
        private IUserSession $userSession,
        private IGroupManager $groupManager,
        private LoggerInterface $logger
    ) {
        parent::__construct($appName, $request);
    }

    /**
     * Get the logger instance for ApiErrorTrait.
     */
    protected function getLogger(): LoggerInterface {
        return $this->logger;
    }

    /**
     * Check if current user is admin
     */
    private function isAdmin(): bool {
        $user = $this->userSession->getUser();
        if ($user === null) {
            return false;
        }
        return $this->groupManager->isAdmin($user->getUID());
    }

    /**
     * Validate a bulk operation without executing it
     *
     * Returns which pages can be operated on and which will fail.
     * Admin only.
     *
     * @NoCSRFRequired
     *
     * @param array $pageIds Array of page unique IDs
     * @param string $operation Operation type: 'delete', 'move', 'update'
     * @return DataResponse
     */
    public function validateOperation(array $pageIds, string $operation): DataResponse {
        if (!$this->isAdmin()) {
            return $this->forbiddenResponse('Admin access required for bulk operations');
        }

        try {
            if (empty($pageIds)) {
                return $this->clientErrorResponse('No page IDs provided');
            }

            if (!in_array($operation, ['delete', 'move', 'update'])) {
                return $this->clientErrorResponse('Invalid operation. Must be: delete, move, or update');
            }

            $validation = $this->bulkService->validateBatchPermissions($pageIds, $operation);

            return new DataResponse([
                'success' => true,
                'operation' => $operation,
                'total' => count($pageIds),
                'canProceed' => count($validation['valid']),
                'willFail' => count($validation['invalid']),
                'valid' => $validation['valid'],
                'invalid' => $validation['invalid']
            ]);
        } catch (\InvalidArgumentException $e) {
            return $this->clientErrorResponse($e->getMessage());
        } catch (\Exception $e) {
            return $this->safeErrorResponse(
                $e,
                'Bulk validation failed. Please try again.',
                Http::STATUS_INTERNAL_SERVER_ERROR,
                ['operation' => $operation]
            );
        }
    }

    /**
     * Bulk delete pages
     * Admin only.
     *
     * @NoCSRFRequired
     *
     * Request body:
     * {
     *   "pageIds": ["page-xxx", "page-yyy"],
     *   "deleteChildren": true,
     *   "dryRun": false
     * }
     *
     * @return DataResponse
     */
    public function deletePages(): DataResponse {
        if (!$this->isAdmin()) {
            return $this->forbiddenResponse('Admin access required for bulk operations');
        }

        try {
            $pageIds = $this->request->getParam('pageIds', []);
            $deleteChildren = $this->request->getParam('deleteChildren', true);
            $dryRun = $this->request->getParam('dryRun', false);

            if (empty($pageIds) || !is_array($pageIds)) {
                return $this->clientErrorResponse('pageIds must be a non-empty array');
            }

            // Dry run just validates
            if ($dryRun) {
                return $this->validateOperation($pageIds, 'delete');
            }

            $result = $this->bulkService->bulkDelete($pageIds, $deleteChildren);

            $this->logger->info('IntraVox Bulk: Delete operation completed', [
                'total' => $result->successCount + $result->failCount,
                'deleted' => $result->successCount,
                'failed' => $result->failCount
            ]);

            return new DataResponse(
                $result->toArray(),
                $result->isSuccess() ? Http::STATUS_OK : Http::STATUS_MULTI_STATUS
            );
        } catch (\InvalidArgumentException $e) {
            return $this->clientErrorResponse($e->getMessage());
        } catch (\Exception $e) {
            return $this->safeErrorResponse(
                $e,
                'Bulk delete operation failed. Please try again.',
                Http::STATUS_INTERNAL_SERVER_ERROR,
                ['operation' => 'delete']
            );
        }
    }

    /**
     * Bulk move pages to a new parent
     * Admin only.
     *
     * @NoCSRFRequired
     *
     * Request body:
     * {
     *   "pageIds": ["page-xxx", "page-yyy"],
     *   "targetParentId": "page-zzz",
     *   "dryRun": false
     * }
     *
     * @return DataResponse
     */
    public function movePages(): DataResponse {
        if (!$this->isAdmin()) {
            return $this->forbiddenResponse('Admin access required for bulk operations');
        }

        try {
            $pageIds = $this->request->getParam('pageIds', []);
            $targetParentId = $this->request->getParam('targetParentId');
            $dryRun = $this->request->getParam('dryRun', false);

            if (empty($pageIds) || !is_array($pageIds)) {
                return $this->clientErrorResponse('pageIds must be a non-empty array');
            }

            if (empty($targetParentId)) {
                return $this->clientErrorResponse('targetParentId is required');
            }

            // Dry run just validates
            if ($dryRun) {
                return $this->validateOperation($pageIds, 'move');
            }

            $result = $this->bulkService->bulkMove($pageIds, $targetParentId);

            $this->logger->info('IntraVox Bulk: Move operation completed', [
                'total' => $result->successCount + $result->failCount,
                'moved' => $result->successCount,
                'failed' => $result->failCount,
                'targetParentId' => $targetParentId
            ]);

            return new DataResponse(
                $result->toArray(),
                $result->isSuccess() ? Http::STATUS_OK : Http::STATUS_MULTI_STATUS
            );
        } catch (\InvalidArgumentException $e) {
            return $this->clientErrorResponse($e->getMessage());
        } catch (\Exception $e) {
            return $this->safeErrorResponse(
                $e,
                'Bulk move operation failed. Please try again.',
                Http::STATUS_INTERNAL_SERVER_ERROR,
                ['operation' => 'move']
            );
        }
    }

    /**
     * Bulk update page properties
     * Admin only.
     *
     * @NoCSRFRequired
     *
     * Request body:
     * {
     *   "pageIds": ["page-xxx", "page-yyy"],
     *   "updates": {
     *     "metadata": { "category": "news" }
     *   },
     *   "dryRun": false
     * }
     *
     * @return DataResponse
     */
    public function updatePages(): DataResponse {
        if (!$this->isAdmin()) {
            return $this->forbiddenResponse('Admin access required for bulk operations');
        }

        try {
            $pageIds = $this->request->getParam('pageIds', []);
            $updates = $this->request->getParam('updates', []);
            $dryRun = $this->request->getParam('dryRun', false);

            if (empty($pageIds) || !is_array($pageIds)) {
                return $this->clientErrorResponse('pageIds must be a non-empty array');
            }

            if (empty($updates) || !is_array($updates)) {
                return $this->clientErrorResponse('updates must be a non-empty object');
            }

            // Dry run just validates
            if ($dryRun) {
                return $this->validateOperation($pageIds, 'update');
            }

            $result = $this->bulkService->bulkUpdate($pageIds, $updates);

            $this->logger->info('IntraVox Bulk: Update operation completed', [
                'total' => $result->successCount + $result->failCount,
                'updated' => $result->successCount,
                'failed' => $result->failCount,
                'updateFields' => array_keys($updates)
            ]);

            return new DataResponse(
                $result->toArray(),
                $result->isSuccess() ? Http::STATUS_OK : Http::STATUS_MULTI_STATUS
            );
        } catch (\InvalidArgumentException $e) {
            return $this->clientErrorResponse($e->getMessage());
        } catch (\Exception $e) {
            return $this->safeErrorResponse(
                $e,
                'Bulk update operation failed. Please try again.',
                Http::STATUS_INTERNAL_SERVER_ERROR,
                ['operation' => 'update']
            );
        }
    }
}
