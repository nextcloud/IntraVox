<?php
declare(strict_types=1);

namespace OCA\IntraVox\Controller;

use OCA\IntraVox\Service\AnalyticsService;
use OCA\IntraVox\Service\PageService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\Attribute\NoCSRFRequired;
use OCP\AppFramework\Http\DataResponse;
use OCP\IConfig;
use OCP\IGroupManager;
use OCP\IRequest;
use OCP\IUserSession;
use Psr\Log\LoggerInterface;

/**
 * API Controller for IntraVox Analytics
 *
 * Provides endpoints for:
 * - Retrieving page statistics
 * - Dashboard aggregates
 * - Admin settings management
 */
class AnalyticsController extends Controller {
    use ApiErrorTrait;

    private const APP_ID = 'intravox';

    public function __construct(
        string $appName,
        IRequest $request,
        private AnalyticsService $analyticsService,
        private PageService $pageService,
        private IUserSession $userSession,
        private IGroupManager $groupManager,
        private IConfig $config,
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
     * Get statistics for a specific page
     *
     * @NoAdminRequired
     * @NoCSRFRequired
     *
     * @param string $pageId The unique ID of the page
     * @param int $days Number of days to include (default 30, max 365)
     * @return DataResponse
     */
    public function getPageStats(string $pageId, int $days = 30): DataResponse {
        try {
            // Verify page exists and user has access
            if (!$this->pageService->pageExistsByUniqueId($pageId)) {
                return $this->notFoundResponse('Page not found');
            }

            // Limit days to reasonable range
            $days = max(1, min(365, $days));

            $stats = $this->analyticsService->getPageStats($pageId, $days);

            return new DataResponse(array_merge(['success' => true], $stats));
        } catch (\Exception $e) {
            return $this->safeErrorResponse(
                $e,
                'Failed to retrieve statistics',
                Http::STATUS_INTERNAL_SERVER_ERROR,
                ['pageId' => $pageId]
            );
        }
    }

    /**
     * Get top pages by view count
     *
     * @NoAdminRequired
     * @NoCSRFRequired
     *
     * @param int $limit Maximum pages to return (default 10, max 50)
     * @param int $days Number of days to consider (default 30, max 365)
     * @return DataResponse
     */
    public function getTopPages(int $limit = 10, int $days = 30): DataResponse {
        try {
            // Limit parameters to reasonable ranges
            $limit = max(1, min(50, $limit));
            $days = max(1, min(365, $days));

            $topPages = $this->analyticsService->getTopPages($limit, $days);

            // Enrich with page titles
            $enrichedPages = [];
            foreach ($topPages as $page) {
                try {
                    $pageData = $this->pageService->getPage($page['pageId']);
                    // Only include pages user can read
                    if ($pageData['permissions']['canRead'] ?? false) {
                        $page['title'] = $pageData['title'] ?? 'Untitled';
                        $page['path'] = $pageData['path'] ?? '';
                        $enrichedPages[] = $page;
                    }
                } catch (\Exception $e) {
                    // Page no longer exists or user doesn't have access, skip it
                    continue;
                }
            }

            return new DataResponse([
                'success' => true,
                'period' => $days,
                'pages' => $enrichedPages
            ]);
        } catch (\Exception $e) {
            return $this->safeErrorResponse(
                $e,
                'Failed to retrieve top pages'
            );
        }
    }

    /**
     * Get dashboard statistics
     * Admin only - shows aggregate statistics across all pages.
     *
     * @NoCSRFRequired
     *
     * @param int $days Number of days to include (default 30, max 365)
     * @return DataResponse
     */
    public function getDashboard(int $days = 30): DataResponse {
        if (!$this->isAdmin()) {
            return $this->forbiddenResponse('Admin access required');
        }

        try {
            $days = max(1, min(365, $days));

            $stats = $this->analyticsService->getDashboardStats($days);

            // Enrich top pages with titles
            $enrichedTopPages = [];
            foreach ($stats['topPages'] as $page) {
                try {
                    $pageData = $this->pageService->getPage($page['pageId']);
                    if ($pageData['permissions']['canRead'] ?? false) {
                        $page['title'] = $pageData['title'] ?? 'Untitled';
                        $enrichedTopPages[] = $page;
                    }
                } catch (\Exception $e) {
                    continue;
                }
            }
            $stats['topPages'] = $enrichedTopPages;

            return new DataResponse(array_merge(['success' => true], $stats));
        } catch (\Exception $e) {
            return $this->safeErrorResponse(
                $e,
                'Failed to retrieve dashboard statistics'
            );
        }
    }

    /**
     * Get analytics settings (admin only)
     *
     * @NoCSRFRequired
     *
     * @return DataResponse
     */
    public function getSettings(): DataResponse {
        if (!$this->isAdmin()) {
            return $this->forbiddenResponse('Admin access required');
        }

        return new DataResponse([
            'success' => true,
            'enabled' => $this->config->getAppValue(self::APP_ID, 'analytics_enabled', 'true') === 'true',
            'retentionDays' => (int)$this->config->getAppValue(self::APP_ID, 'analytics_retention_days', '90')
        ]);
    }

    /**
     * Update analytics settings (admin only)
     *
     * @param bool $enabled Whether analytics is enabled
     * @param int $retentionDays Number of days to retain data
     * @return DataResponse
     */
    public function setSettings(bool $enabled = true, int $retentionDays = 90): DataResponse {
        if (!$this->isAdmin()) {
            return $this->forbiddenResponse('Admin access required');
        }

        // Validate retention days
        $retentionDays = max(7, min(365, $retentionDays));

        $this->config->setAppValue(self::APP_ID, 'analytics_enabled', $enabled ? 'true' : 'false');
        $this->config->setAppValue(self::APP_ID, 'analytics_retention_days', (string)$retentionDays);

        $this->logger->info('IntraVox Analytics: Settings updated', [
            'enabled' => $enabled,
            'retentionDays' => $retentionDays
        ]);

        return new DataResponse([
            'success' => true,
            'enabled' => $enabled,
            'retentionDays' => $retentionDays
        ]);
    }

    /**
     * Track a page view (called from frontend)
     *
     * @NoAdminRequired
     *
     * @param string $pageId The unique ID of the page
     * @return DataResponse
     */
    public function trackView(string $pageId): DataResponse {
        try {
            // Verify page exists
            if (!$this->pageService->pageExistsByUniqueId($pageId)) {
                return $this->notFoundResponse('Page not found');
            }

            $tracked = $this->analyticsService->trackPageView($pageId);

            return new DataResponse([
                'success' => $tracked
            ]);
        } catch (\Exception $e) {
            return $this->safeErrorResponse(
                $e,
                'Failed to track view',
                Http::STATUS_INTERNAL_SERVER_ERROR,
                ['pageId' => $pageId]
            );
        }
    }
}
