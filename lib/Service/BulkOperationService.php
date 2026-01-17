<?php
declare(strict_types=1);

namespace OCA\IntraVox\Service;

use Psr\Log\LoggerInterface;

/**
 * Service for bulk operations on pages
 *
 * Handles batch operations like delete, move, and update
 * with proper permission checking and error handling.
 */
class BulkOperationService {
    /**
     * Maximum number of pages that can be processed in a single bulk operation.
     * This prevents DoS attacks and server overload.
     */
    public const MAX_PAGES_PER_OPERATION = 100;

    public function __construct(
        private PageService $pageService,
        private LoggerInterface $logger
    ) {}

    /**
     * Validate that the number of pages doesn't exceed the limit.
     *
     * @param array $pageIds Array of page IDs
     * @throws \InvalidArgumentException If limit is exceeded
     */
    private function validateOperationLimit(array $pageIds): void {
        $count = count($pageIds);
        if ($count > self::MAX_PAGES_PER_OPERATION) {
            throw new \InvalidArgumentException(
                "Bulk operation limit exceeded: {$count} pages requested, maximum is " . self::MAX_PAGES_PER_OPERATION
            );
        }
    }

    /**
     * Validate permissions for a batch operation
     *
     * @param array $pageIds Array of page unique IDs
     * @param string $operation Operation type: 'delete', 'move', 'update'
     * @return array{valid: array, invalid: array}
     * @throws \InvalidArgumentException If page count exceeds limit
     */
    public function validateBatchPermissions(array $pageIds, string $operation): array {
        $this->validateOperationLimit($pageIds);

        $permissionKey = match ($operation) {
            'delete' => 'canDelete',
            'move', 'update' => 'canWrite',
            default => throw new \InvalidArgumentException("Unknown operation: $operation")
        };

        $valid = [];
        $invalid = [];

        foreach ($pageIds as $pageId) {
            try {
                $page = $this->pageService->getPage($pageId);
                if ($page['permissions'][$permissionKey] ?? false) {
                    $valid[] = [
                        'pageId' => $pageId,
                        'title' => $page['title'] ?? 'Untitled'
                    ];
                } else {
                    $invalid[] = [
                        'pageId' => $pageId,
                        'reason' => 'Permission denied'
                    ];
                }
            } catch (\Exception $e) {
                $invalid[] = [
                    'pageId' => $pageId,
                    'reason' => 'Page not found'
                ];
            }
        }

        return [
            'valid' => $valid,
            'invalid' => $invalid
        ];
    }

    /**
     * Bulk delete pages
     *
     * @param array $pageIds Array of page unique IDs to delete
     * @param bool $deleteChildren Whether to delete child pages (default: true for nested pages)
     * @return BulkOperationResult
     * @throws \InvalidArgumentException If page count exceeds limit
     */
    public function bulkDelete(array $pageIds, bool $deleteChildren = true): BulkOperationResult {
        $this->validateOperationLimit($pageIds);
        $result = new BulkOperationResult('delete');

        foreach ($pageIds as $pageId) {
            try {
                // Get page to check permissions
                $page = $this->pageService->getPage($pageId);

                if (!($page['permissions']['canDelete'] ?? false)) {
                    $result->addFailed($pageId, 'Permission denied');
                    continue;
                }

                // Delete the page
                $this->pageService->deletePage($pageId);
                $result->addSucceeded($pageId, $page['title'] ?? 'Untitled');

                $this->logger->info('IntraVox Bulk: Deleted page', [
                    'pageId' => $pageId,
                    'title' => $page['title'] ?? 'Untitled'
                ]);

            } catch (\Exception $e) {
                $result->addFailed($pageId, $e->getMessage());
                $this->logger->warning('IntraVox Bulk: Failed to delete page', [
                    'pageId' => $pageId,
                    'error' => $e->getMessage()
                ]);
            }
        }

        return $result;
    }

    /**
     * Bulk move pages to a new parent
     *
     * @param array $pageIds Array of page unique IDs to move
     * @param string $targetParentId The unique ID of the new parent page
     * @return BulkOperationResult
     * @throws \InvalidArgumentException If page count exceeds limit
     */
    public function bulkMove(array $pageIds, string $targetParentId): BulkOperationResult {
        $this->validateOperationLimit($pageIds);
        $result = new BulkOperationResult('move');

        // Validate target parent exists and user has write permission
        try {
            $targetParent = $this->pageService->getPage($targetParentId);
            if (!($targetParent['permissions']['canWrite'] ?? false)) {
                // All operations fail if target is not writable
                foreach ($pageIds as $pageId) {
                    $result->addFailed($pageId, 'No write permission on target parent');
                }
                return $result;
            }
        } catch (\Exception $e) {
            foreach ($pageIds as $pageId) {
                $result->addFailed($pageId, 'Target parent page not found');
            }
            return $result;
        }

        foreach ($pageIds as $pageId) {
            try {
                // Get page to check permissions
                $page = $this->pageService->getPage($pageId);

                if (!($page['permissions']['canWrite'] ?? false)) {
                    $result->addFailed($pageId, 'Permission denied');
                    continue;
                }

                // Prevent moving a page to itself or its descendant
                if ($pageId === $targetParentId) {
                    $result->addFailed($pageId, 'Cannot move page to itself');
                    continue;
                }

                // Move the page
                $this->pageService->movePage($pageId, $targetParentId);
                $result->addSucceeded($pageId, $page['title'] ?? 'Untitled');

                $this->logger->info('IntraVox Bulk: Moved page', [
                    'pageId' => $pageId,
                    'targetParentId' => $targetParentId
                ]);

            } catch (\Exception $e) {
                $result->addFailed($pageId, $e->getMessage());
                $this->logger->warning('IntraVox Bulk: Failed to move page', [
                    'pageId' => $pageId,
                    'targetParentId' => $targetParentId,
                    'error' => $e->getMessage()
                ]);
            }
        }

        return $result;
    }

    /**
     * Bulk update page metadata
     *
     * @param array $pageIds Array of page unique IDs to update
     * @param array $updates Updates to apply (e.g., metadata fields)
     * @return BulkOperationResult
     * @throws \InvalidArgumentException If page count exceeds limit
     */
    public function bulkUpdate(array $pageIds, array $updates): BulkOperationResult {
        $this->validateOperationLimit($pageIds);
        $result = new BulkOperationResult('update');

        if (empty($updates)) {
            foreach ($pageIds as $pageId) {
                $result->addFailed($pageId, 'No updates provided');
            }
            return $result;
        }

        foreach ($pageIds as $pageId) {
            try {
                // Get page to check permissions
                $page = $this->pageService->getPage($pageId);

                if (!($page['permissions']['canWrite'] ?? false)) {
                    $result->addFailed($pageId, 'Permission denied');
                    continue;
                }

                // Apply updates
                $this->pageService->updatePage($pageId, $updates);
                $result->addSucceeded($pageId, $page['title'] ?? 'Untitled');

                $this->logger->info('IntraVox Bulk: Updated page', [
                    'pageId' => $pageId,
                    'updates' => array_keys($updates)
                ]);

            } catch (\Exception $e) {
                $result->addFailed($pageId, $e->getMessage());
                $this->logger->warning('IntraVox Bulk: Failed to update page', [
                    'pageId' => $pageId,
                    'error' => $e->getMessage()
                ]);
            }
        }

        return $result;
    }
}

/**
 * Result of a bulk operation
 */
class BulkOperationResult {
    public int $successCount = 0;
    public int $failCount = 0;
    public array $succeeded = [];
    public array $failed = [];
    public array $errors = [];

    public function __construct(
        public string $operation
    ) {}

    public function addSucceeded(string $pageId, string $title = ''): void {
        $this->successCount++;
        $this->succeeded[] = [
            'pageId' => $pageId,
            'title' => $title,
            'status' => $this->operation === 'delete' ? 'deleted' : ($this->operation === 'move' ? 'moved' : 'updated')
        ];
    }

    public function addFailed(string $pageId, string $reason): void {
        $this->failCount++;
        $this->failed[] = [
            'pageId' => $pageId,
            'status' => 'failed',
            'error' => $reason
        ];
        $this->errors[] = "$pageId: $reason";
    }

    public function isSuccess(): bool {
        return $this->failCount === 0;
    }

    public function toArray(): array {
        // Generate grammatically correct past tense key
        $pastTense = match ($this->operation) {
            'delete' => 'deleted',
            'move' => 'moved',
            'update' => 'updated',
            default => $this->operation . 'd'
        };

        return [
            'success' => $this->isSuccess(),
            'operation' => $this->operation,
            'total' => $this->successCount + $this->failCount,
            $pastTense => $this->successCount,
            'failed' => $this->failCount,
            'results' => array_merge($this->succeeded, $this->failed),
            'errors' => $this->errors
        ];
    }
}
