<?php
declare(strict_types=1);

namespace OCA\IntraVox\Controller;

use OCA\IntraVox\Service\DemoDataService;
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
    private LoggerInterface $logger;

    public function __construct(
        string $appName,
        IRequest $request,
        DemoDataService $demoDataService,
        LoggerInterface $logger
    ) {
        parent::__construct($appName, $request);
        $this->demoDataService = $demoDataService;
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
     *
     * @param string $language Language code (nl, en)
     * @return DataResponse
     */
    #[NoAdminRequired]
    public function importDemoData(string $language = 'nl'): DataResponse {
        try {
            $result = $this->demoDataService->importDemoData($language);

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
}
