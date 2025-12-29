<?php
declare(strict_types=1);

namespace OCA\IntraVox\Controller;

use OCA\IntraVox\Service\DemoDataService;
use OCA\IntraVox\Service\PermissionService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\DataResponse;
use OCP\IRequest;
use Psr\Log\LoggerInterface;

/**
 * Controller for demo data import operations
 */
class DemoDataController extends Controller {
    private DemoDataService $demoDataService;
    private PermissionService $permissionService;
    private LoggerInterface $logger;

    public function __construct(
        string $appName,
        IRequest $request,
        DemoDataService $demoDataService,
        PermissionService $permissionService,
        LoggerInterface $logger
    ) {
        parent::__construct($appName, $request);
        $this->demoDataService = $demoDataService;
        $this->permissionService = $permissionService;
        $this->logger = $logger;
    }

    /**
     * Get demo data status
     *
     * @return DataResponse
     */
    #[NoAdminRequired]
    public function getStatus(): DataResponse {
        try {
            // Check if user has access
            if (!$this->permissionService->hasAccess()) {
                return new DataResponse(
                    ['error' => 'Access denied'],
                    Http::STATUS_FORBIDDEN
                );
            }

            $status = $this->demoDataService->getStatus();
            return new DataResponse($status);
        } catch (\Exception $e) {
            $this->logger->error('[DemoDataController] Failed to get status: ' . $e->getMessage());
            return new DataResponse(
                ['error' => 'Failed to get demo data status'],
                Http::STATUS_INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     * Import demo data for a specific language
     * Only Nextcloud admins can import demo data (no NoAdminRequired attribute)
     *
     * @param string $language Language code (nl, en)
     * @param string $mode Import mode: 'overwrite' (default) or 'skip_existing'
     * @return DataResponse
     */
    public function importDemoData(string $language = 'nl', string $mode = 'overwrite'): DataResponse {
        try {
            // Validate mode
            if (!in_array($mode, ['overwrite', 'skip_existing'])) {
                $mode = 'overwrite';
            }

            // Try bundled demo data first, fall back to remote download
            if ($this->demoDataService->hasBundledDemoData($language)) {
                $result = $this->demoDataService->importBundledDemoData($language, $mode);
            } else {
                $result = $this->demoDataService->importDemoData($language);
            }

            $statusCode = $result['success'] ? Http::STATUS_OK : Http::STATUS_INTERNAL_SERVER_ERROR;
            return new DataResponse($result, $statusCode);

        } catch (\Exception $e) {
            return new DataResponse(
                ['success' => false, 'error' => 'Failed to import demo data: ' . $e->getMessage()],
                Http::STATUS_INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     * Get available languages for demo data
     *
     * @return DataResponse
     */
    #[NoAdminRequired]
    public function getLanguages(): DataResponse {
        try {
            // Check if user has access
            if (!$this->permissionService->hasAccess()) {
                return new DataResponse(
                    ['error' => 'Access denied'],
                    Http::STATUS_FORBIDDEN
                );
            }

            $languages = $this->demoDataService->getAvailableLanguages();
            return new DataResponse(['languages' => $languages]);
        } catch (\Exception $e) {
            $this->logger->error('[DemoDataController] Failed to get languages: ' . $e->getMessage());
            return new DataResponse(
                ['error' => 'Failed to get available languages'],
                Http::STATUS_INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     * Perform clean start for a specific language
     * Only Nextcloud admins can perform clean start (no NoAdminRequired attribute)
     *
     * Deletes all content for the specified language and creates a fresh
     * homepage with a new uniqueId, empty navigation, and empty footer.
     *
     * @param string $language Language code (nl, en, de, fr)
     * @return DataResponse
     */
    public function cleanStart(string $language = 'nl'): DataResponse {
        try {
            $this->logger->info("[DemoDataController] Clean start requested for language: {$language}");

            $result = $this->demoDataService->performCleanStart($language);

            $statusCode = $result['success'] ? Http::STATUS_OK : Http::STATUS_INTERNAL_SERVER_ERROR;
            return new DataResponse($result, $statusCode);

        } catch (\Exception $e) {
            $this->logger->error('[DemoDataController] Clean start failed: ' . $e->getMessage());
            return new DataResponse(
                ['success' => false, 'error' => 'Failed to perform clean start: ' . $e->getMessage()],
                Http::STATUS_INTERNAL_SERVER_ERROR
            );
        }
    }
}
