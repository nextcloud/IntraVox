<?php
declare(strict_types=1);

namespace OCA\IntraVox\Controller;

use OCA\IntraVox\Service\LicenseService;
use OCA\IntraVox\Service\TelemetryService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\NoCSRFRequired;
use OCP\AppFramework\Http\DataResponse;
use OCP\IGroupManager;
use OCP\IRequest;
use OCP\IUserSession;
use Psr\Log\LoggerInterface;

/**
 * API Controller for IntraVox License Management
 *
 * Provides endpoints for:
 * - Getting page statistics per language
 * - Sending telemetry data to license server
 *
 * All endpoints are admin-only (no @NoAdminRequired)
 */
class LicenseController extends Controller {
    use ApiErrorTrait;

    public function __construct(
        string $appName,
        IRequest $request,
        private LicenseService $licenseService,
        private TelemetryService $telemetryService,
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
     * Get license statistics
     *
     * Returns page counts per language and total.
     * Admin only - no @NoAdminRequired annotation.
     *
     * @NoCSRFRequired
     *
     * @return DataResponse
     */
    public function getStats(): DataResponse {
        // Double-check admin (annotation already enforces this, but extra safety)
        if (!$this->isAdmin()) {
            return $this->forbiddenResponse('Admin privileges required');
        }

        try {
            $stats = $this->licenseService->getStats();

            return new DataResponse([
                'success' => true,
                ...$stats
            ]);
        } catch (\Exception $e) {
            return $this->safeErrorResponse(
                $e,
                'Failed to retrieve license statistics',
                Http::STATUS_INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     * Send telemetry to license server
     *
     * Admin only - no @NoAdminRequired annotation.
     *
     * @return DataResponse
     */
    public function sendTelemetry(): DataResponse {
        // Double-check admin
        if (!$this->isAdmin()) {
            return $this->forbiddenResponse('Admin privileges required');
        }

        try {
            $success = $this->telemetryService->sendReport();

            if ($success) {
                return new DataResponse([
                    'success' => true,
                    'message' => 'Telemetry sent successfully'
                ]);
            } else {
                return new DataResponse([
                    'success' => false,
                    'message' => 'Telemetry is disabled or failed to send'
                ]);
            }
        } catch (\Exception $e) {
            return $this->safeErrorResponse(
                $e,
                'Failed to send telemetry',
                Http::STATUS_INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     * Get instance info (for debugging/display)
     *
     * Admin only - no @NoAdminRequired annotation.
     *
     * @NoCSRFRequired
     *
     * @return DataResponse
     */
    public function getInstanceInfo(): DataResponse {
        // Double-check admin
        if (!$this->isAdmin()) {
            return $this->forbiddenResponse('Admin privileges required');
        }

        try {
            $info = $this->licenseService->getInstanceInfo();

            return new DataResponse([
                'success' => true,
                ...$info
            ]);
        } catch (\Exception $e) {
            return $this->safeErrorResponse(
                $e,
                'Failed to retrieve instance info',
                Http::STATUS_INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     * Save license settings (server URL and license key)
     *
     * Admin only - no @NoAdminRequired annotation.
     *
     * @return DataResponse
     */
    public function saveSettings(): DataResponse {
        // Double-check admin
        if (!$this->isAdmin()) {
            return $this->forbiddenResponse('Admin privileges required');
        }

        try {
            $licenseKey = $this->request->getParam('licenseKey', '');

            // Save license key if provided
            if ($licenseKey !== null) {
                $this->licenseService->setLicenseKey($licenseKey);
            }

            return new DataResponse([
                'success' => true,
                'message' => 'License settings saved successfully'
            ]);
        } catch (\Exception $e) {
            return $this->safeErrorResponse(
                $e,
                'Failed to save license settings',
                Http::STATUS_INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     * Validate the configured license
     *
     * Admin only - no @NoAdminRequired annotation.
     *
     * @return DataResponse
     */
    public function validate(): DataResponse {
        // Double-check admin
        if (!$this->isAdmin()) {
            return $this->forbiddenResponse('Admin privileges required');
        }

        try {
            $result = $this->licenseService->validateLicense();

            return new DataResponse($result);
        } catch (\Exception $e) {
            return $this->safeErrorResponse(
                $e,
                'Failed to validate license',
                Http::STATUS_INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     * Update license usage on the license server
     *
     * Admin only - no @NoAdminRequired annotation.
     *
     * @return DataResponse
     */
    public function updateUsage(): DataResponse {
        // Double-check admin
        if (!$this->isAdmin()) {
            return $this->forbiddenResponse('Admin privileges required');
        }

        try {
            $result = $this->licenseService->updateUsage();

            return new DataResponse($result);
        } catch (\Exception $e) {
            return $this->safeErrorResponse(
                $e,
                'Failed to update license usage',
                Http::STATUS_INTERNAL_SERVER_ERROR
            );
        }
    }
}
