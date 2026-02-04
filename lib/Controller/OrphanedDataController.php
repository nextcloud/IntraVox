<?php
declare(strict_types=1);

namespace OCA\IntraVox\Controller;

use OCA\IntraVox\Service\OrphanedDataService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\DataResponse;
use OCP\IRequest;
use Psr\Log\LoggerInterface;

/**
 * Controller for orphaned GroupFolder data management.
 *
 * All endpoints require Nextcloud admin privileges (no NoAdminRequired attribute).
 * This is intentional as orphaned data management is a sensitive operation.
 */
class OrphanedDataController extends Controller {
    private OrphanedDataService $orphanedDataService;
    private LoggerInterface $logger;

    public function __construct(
        string $appName,
        IRequest $request,
        OrphanedDataService $orphanedDataService,
        LoggerInterface $logger
    ) {
        parent::__construct($appName, $request);
        $this->orphanedDataService = $orphanedDataService;
        $this->logger = $logger;
    }

    /**
     * Scan for orphaned GroupFolder data.
     *
     * Compares physical directories in __groupfolders/ with registered
     * GroupFolders in the database to find orphaned data.
     *
     * @return DataResponse List of orphaned folders with metadata
     */
    public function scan(): DataResponse {
        try {
            $this->logger->info('[OrphanedDataController] Scanning for orphaned folders');

            $folders = $this->orphanedDataService->detectOrphanedFolders();

            return new DataResponse([
                'success' => true,
                'folders' => $folders,
                'count' => count($folders),
            ]);

        } catch (\Exception $e) {
            $this->logger->error('[OrphanedDataController] Scan failed: ' . $e->getMessage());
            return new DataResponse(
                ['success' => false, 'error' => 'Failed to scan for orphaned data: ' . $e->getMessage()],
                Http::STATUS_INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     * Get detailed information about a specific orphaned folder.
     *
     * @param int $id The orphaned folder ID
     * @return DataResponse Detailed folder information
     */
    public function getDetails(int $id): DataResponse {
        try {
            $this->logger->info("[OrphanedDataController] Getting details for orphaned folder: {$id}");

            // Validate folder ID
            if ($id <= 0) {
                return new DataResponse(
                    ['success' => false, 'error' => 'Invalid folder ID'],
                    Http::STATUS_BAD_REQUEST
                );
            }

            $details = $this->orphanedDataService->getOrphanedFolderDetails($id);

            if ($details === null) {
                return new DataResponse(
                    ['success' => false, 'error' => 'Orphaned folder not found or not accessible'],
                    Http::STATUS_NOT_FOUND
                );
            }

            return new DataResponse([
                'success' => true,
                'folder' => $details,
            ]);

        } catch (\Exception $e) {
            $this->logger->error("[OrphanedDataController] Get details failed: " . $e->getMessage());
            return new DataResponse(
                ['success' => false, 'error' => 'Failed to get folder details: ' . $e->getMessage()],
                Http::STATUS_INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     * Delete an orphaned folder and all its contents.
     *
     * @param int $id The orphaned folder ID to delete
     * @return DataResponse Result of the cleanup operation
     */
    public function cleanup(int $id): DataResponse {
        try {
            $this->logger->info("[OrphanedDataController] Cleanup requested for orphaned folder: {$id}");

            // Validate folder ID
            if ($id <= 0) {
                return new DataResponse(
                    ['success' => false, 'error' => 'Invalid folder ID'],
                    Http::STATUS_BAD_REQUEST
                );
            }

            $result = $this->orphanedDataService->cleanupOrphanedFolder($id);

            $statusCode = $result['success'] ? Http::STATUS_OK : Http::STATUS_INTERNAL_SERVER_ERROR;
            return new DataResponse($result, $statusCode);

        } catch (\Exception $e) {
            $this->logger->error("[OrphanedDataController] Cleanup failed: " . $e->getMessage());
            return new DataResponse(
                ['success' => false, 'error' => 'Failed to cleanup orphaned folder: ' . $e->getMessage()],
                Http::STATUS_INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     * Migrate content from an orphaned folder to the active IntraVox GroupFolder.
     *
     * @param int $id Source orphaned folder ID
     * @return DataResponse Result of the migration operation
     */
    public function migrate(int $id): DataResponse {
        try {
            // Get parameters from request body
            $language = $this->request->getParam('language', 'nl');
            $mode = $this->request->getParam('mode', 'merge');

            $this->logger->info("[OrphanedDataController] Migration requested: folder {$id}, language {$language}, mode {$mode}");

            // Validate folder ID
            if ($id <= 0) {
                return new DataResponse(
                    ['success' => false, 'error' => 'Invalid folder ID'],
                    Http::STATUS_BAD_REQUEST
                );
            }

            // Validate language
            $supportedLanguages = ['nl', 'en', 'de', 'fr'];
            if (!in_array($language, $supportedLanguages)) {
                return new DataResponse(
                    ['success' => false, 'error' => "Unsupported language: {$language}"],
                    Http::STATUS_BAD_REQUEST
                );
            }

            // Validate mode
            if (!in_array($mode, ['merge', 'replace'])) {
                return new DataResponse(
                    ['success' => false, 'error' => "Invalid mode: {$mode}. Use 'merge' or 'replace'"],
                    Http::STATUS_BAD_REQUEST
                );
            }

            $result = $this->orphanedDataService->migrateOrphanedToActive($id, $language, $mode);

            $statusCode = $result['success'] ? Http::STATUS_OK : Http::STATUS_INTERNAL_SERVER_ERROR;
            return new DataResponse($result, $statusCode);

        } catch (\Exception $e) {
            $this->logger->error("[OrphanedDataController] Migration failed: " . $e->getMessage());
            return new DataResponse(
                ['success' => false, 'error' => 'Failed to migrate orphaned data: ' . $e->getMessage()],
                Http::STATUS_INTERNAL_SERVER_ERROR
            );
        }
    }
}
