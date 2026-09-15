<?php
declare(strict_types=1);

namespace OCA\IntraVox\Tests\Unit\Controller;

use OCA\IntraVox\Controller\AnalyticsController;
use OCA\IntraVox\Service\AnalyticsService;
use OCA\IntraVox\Service\Read\PageReadService;
use OCA\IntraVox\Tests\Mocks\MockGroupManager;
use OCA\IntraVox\Tests\Mocks\MockUserSession;
use OCA\IntraVox\Tests\Unit\Service\Harness\BuildsPageRead;
use OCP\AppFramework\Http;
use OCP\IConfig;
use OCP\IRequest;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Unit tests for AnalyticsController
 *
 * Tests cover:
 * - Page statistics retrieval
 * - Top pages listing
 * - Dashboard statistics
 * - Admin settings (get/set)
 * - View tracking
 * - Permission checks
 */
class AnalyticsControllerTest extends TestCase {
    use BuildsPageRead;

    private AnalyticsController $controller;
    private AnalyticsService $analyticsService;
    private PageReadService $pageRead;
    /** getPage(id) behaviour a test installs (fase-4 C6: getPage moved to PageReadService). */
    private \Closure $getPageFn;
    /** pageExistsByUniqueId(id) behaviour a test installs (fase-9: probe moved to PageReadService). */
    private \Closure $pageExistsFn;
    private LoggerInterface $logger;
    private IConfig $config;
    private MockGroupManager $groupManager;
    private MockUserSession $userSession;
    private IRequest $request;

    protected function setUp(): void {
        parent::setUp();
        // The real PageReadService delegates getPage($id)/pageExistsByUniqueId($id) to
        // whatever $this->getPageFn / $this->pageExistsFn a test installs; defaults are
        // "page not found" (empty) and "does not exist".
        $this->getPageFn = fn(string $id) => null;
        $this->pageExistsFn = fn(string $id) => false;
        $this->pageRead = $this->fakePageReadFrom(
            fn(string $id) => ($this->getPageFn)($id),
            fn(string $id): bool => ($this->pageExistsFn)($id)
        );

        $this->analyticsService = $this->createMock(AnalyticsService::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->config = $this->createMock(IConfig::class);
        $this->request = $this->createMock(IRequest::class);

        $this->userSession = MockUserSession::loggedInAs('testuser');
        $this->groupManager = MockGroupManager::noAdmins();

        $this->controller = new AnalyticsController(
            'intravox',
            $this->request,
            $this->analyticsService,
            $this->pageRead,
            $this->userSession,
            $this->groupManager,
            $this->config,
            $this->logger
        );
    }

    private function createAdminController(): AnalyticsController {
        $userSession = MockUserSession::loggedInAs('admin');
        $groupManager = MockGroupManager::withAdmin('admin');

        return new AnalyticsController(
            'intravox',
            $this->request,
            $this->analyticsService,
            $this->pageRead,
            $userSession,
            $groupManager,
            $this->config,
            $this->logger
        );
    }

    // ==========================================
    // getPageStats Tests
    // ==========================================

    public function testGetPageStatsReturnsStatsForValidPage(): void {
        $pageId = 'page-123';
        $stats = [
            'pageId' => $pageId,
            'period' => 30,
            'totalViews' => 150,
            'totalUniqueUsers' => 42,
            'dailyStats' => []
        ];

        $this->pageExistsFn = fn(string $id): bool => true;

        $this->analyticsService->method('getPageStats')
            ->with($pageId, 30)
            ->willReturn($stats);

        $response = $this->controller->getPageStats($pageId);

        $this->assertEquals(Http::STATUS_OK, $response->getStatus());
        $this->assertEquals(array_merge($stats, ['success' => true]), $response->getData());
    }

    public function testGetPageStatsReturnsNotFoundForInvalidPage(): void {
        $this->pageExistsFn = fn(string $id): bool => false;

        $response = $this->controller->getPageStats('invalid-page');

        $this->assertEquals(Http::STATUS_NOT_FOUND, $response->getStatus());
        $this->assertArrayHasKey('error', $response->getData());
    }

    public function testGetPageStatsLimitsDaysParameter(): void {
        $pageId = 'page-123';

        $this->pageExistsFn = fn(string $id): bool => true;

        // Test max limit (365)
        $this->analyticsService->expects($this->once())
            ->method('getPageStats')
            ->with($pageId, 365)
            ->willReturn([]);

        $this->controller->getPageStats($pageId, 500);
    }

    public function testGetPageStatsReturnsErrorOnException(): void {
        $this->pageExistsFn = fn(string $id): bool => true;
        $this->analyticsService->method('getPageStats')
            ->willThrowException(new \Exception('Database error'));

        $response = $this->controller->getPageStats('page-123');

        $this->assertEquals(Http::STATUS_INTERNAL_SERVER_ERROR, $response->getStatus());
        $this->assertArrayHasKey('error', $response->getData());
    }

    // ==========================================
    // getTopPages Tests
    // ==========================================

    public function testGetTopPagesReturnsEnrichedPages(): void {
        $topPages = [
            ['pageId' => 'page-1', 'totalViews' => 100, 'totalUniqueUsers' => 30],
            ['pageId' => 'page-2', 'totalViews' => 80, 'totalUniqueUsers' => 25]
        ];

        $this->analyticsService->method('getTopPages')->willReturn($topPages);

        $this->getPageFn = (function ($id) {
                return [
                    'title' => "Title for $id",
                    'path' => "/pages/$id",
                    'permissions' => ['canRead' => true]
                ];
            });

        $response = $this->controller->getTopPages();

        $this->assertEquals(Http::STATUS_OK, $response->getStatus());
        $data = $response->getData();
        $this->assertArrayHasKey('pages', $data);
        $this->assertCount(2, $data['pages']);
        $this->assertEquals('Title for page-1', $data['pages'][0]['title']);
    }

    public function testGetTopPagesFiltersInaccessiblePages(): void {
        $topPages = [
            ['pageId' => 'page-1', 'totalViews' => 100],
            ['pageId' => 'page-2', 'totalViews' => 80]
        ];

        $this->analyticsService->method('getTopPages')->willReturn($topPages);

        $this->getPageFn = (function ($id) {
                if ($id === 'page-1') {
                    return ['title' => 'Accessible', 'permissions' => ['canRead' => true]];
                }
                return ['title' => 'Forbidden', 'permissions' => ['canRead' => false]];
            });

        $response = $this->controller->getTopPages();

        $data = $response->getData();
        $this->assertCount(1, $data['pages']);
        $this->assertEquals('page-1', $data['pages'][0]['pageId']);
    }

    public function testGetTopPagesSkipsDeletedPages(): void {
        $topPages = [
            ['pageId' => 'page-exists', 'totalViews' => 100],
            ['pageId' => 'page-deleted', 'totalViews' => 80]
        ];

        $this->analyticsService->method('getTopPages')->willReturn($topPages);

        $this->getPageFn = (function ($id) {
                if ($id === 'page-deleted') {
                    throw new \Exception('Page not found');
                }
                return ['title' => 'Existing', 'permissions' => ['canRead' => true]];
            });

        $response = $this->controller->getTopPages();

        $data = $response->getData();
        $this->assertCount(1, $data['pages']);
    }

    // ==========================================
    // getDashboard Tests
    // ==========================================

    public function testGetDashboardReturnsStatistics(): void {
        $dashboardStats = [
            'period' => 30,
            'totalViews' => 500,
            'totalPagesViewed' => 25,
            'dailyStats' => [],
            'topPages' => [
                ['pageId' => 'page-1', 'totalViews' => 100]
            ]
        ];

        $this->analyticsService->method('getDashboardStats')->willReturn($dashboardStats);

        $this->getPageFn = fn(string $id) => [
            'title' => 'Test Page',
            'permissions' => ['canRead' => true]
        ];

        $adminController = $this->createAdminController();
        $response = $adminController->getDashboard();

        $this->assertEquals(Http::STATUS_OK, $response->getStatus());
        $data = $response->getData();
        $this->assertEquals(500, $data['totalViews']);
        $this->assertArrayHasKey('topPages', $data);
    }

    public function testGetDashboardReturnsErrorOnException(): void {
        $this->analyticsService->method('getDashboardStats')
            ->willThrowException(new \Exception('Database error'));

        $adminController = $this->createAdminController();
        $response = $adminController->getDashboard();

        $this->assertEquals(Http::STATUS_INTERNAL_SERVER_ERROR, $response->getStatus());
    }

    // ==========================================
    // getSettings Tests (Admin only)
    // ==========================================

    public function testGetSettingsReturnsForbiddenForNonAdmin(): void {
        $response = $this->controller->getSettings();

        $this->assertEquals(Http::STATUS_FORBIDDEN, $response->getStatus());
        $this->assertStringContainsString('Admin', $response->getData()['error']);
    }

    public function testGetSettingsReturnsSettingsForAdmin(): void {
        $controller = $this->createAdminController();

        $this->config->method('getAppValue')
            ->willReturnCallback(function ($app, $key, $default) {
                if ($key === 'analytics_enabled') return 'true';
                if ($key === 'analytics_retention_days') return '90';
                return $default;
            });

        $response = $controller->getSettings();

        $this->assertEquals(Http::STATUS_OK, $response->getStatus());
        $data = $response->getData();
        $this->assertTrue($data['enabled']);
        $this->assertEquals(90, $data['retentionDays']);
    }

    // ==========================================
    // setSettings Tests (Admin only)
    // ==========================================

    public function testSetSettingsReturnsForbiddenForNonAdmin(): void {
        $response = $this->controller->setSettings(true, 90);

        $this->assertEquals(Http::STATUS_FORBIDDEN, $response->getStatus());
    }

    public function testSetSettingsUpdatesConfigForAdmin(): void {
        $controller = $this->createAdminController();

        $this->config->expects($this->exactly(2))
            ->method('setAppValue')
            ->willReturnCallback(function ($app, $key, $value) {
                $this->assertEquals('intravox', $app);
                if ($key === 'analytics_enabled') {
                    $this->assertEquals('false', $value);
                }
                if ($key === 'analytics_retention_days') {
                    $this->assertEquals('60', $value);
                }
            });

        $response = $controller->setSettings(false, 60);

        $this->assertEquals(Http::STATUS_OK, $response->getStatus());
        $data = $response->getData();
        $this->assertTrue($data['success']);
        $this->assertFalse($data['enabled']);
        $this->assertEquals(60, $data['retentionDays']);
    }

    public function testSetSettingsEnforcesRetentionLimits(): void {
        $controller = $this->createAdminController();

        // Test minimum (7 days)
        $response = $controller->setSettings(true, 1);
        $this->assertEquals(7, $response->getData()['retentionDays']);
    }

    // ==========================================
    // trackView Tests
    // ==========================================

    public function testTrackViewSuccessful(): void {
        $pageId = 'page-123';

        $this->pageExistsFn = fn(string $id): bool => true;

        $this->analyticsService->method('trackPageView')
            ->with($pageId)
            ->willReturn(true);

        $response = $this->controller->trackView($pageId);

        $this->assertEquals(Http::STATUS_OK, $response->getStatus());
        $this->assertTrue($response->getData()['success']);
    }

    public function testTrackViewReturnsNotFoundForInvalidPage(): void {
        $this->pageExistsFn = fn(string $id): bool => false;

        $response = $this->controller->trackView('invalid-page');

        $this->assertEquals(Http::STATUS_NOT_FOUND, $response->getStatus());
    }

    public function testTrackViewReturnsErrorOnException(): void {
        $this->pageExistsFn = fn(string $id): bool => true;
        $this->analyticsService->method('trackPageView')
            ->willThrowException(new \Exception('Tracking failed'));

        $response = $this->controller->trackView('page-123');

        $this->assertEquals(Http::STATUS_INTERNAL_SERVER_ERROR, $response->getStatus());
    }

    public function testTrackViewReturnsFalseWhenDisabled(): void {
        $this->pageExistsFn = fn(string $id): bool => true;
        $this->analyticsService->method('trackPageView')->willReturn(false);

        $response = $this->controller->trackView('page-123');

        $this->assertEquals(Http::STATUS_OK, $response->getStatus());
        $this->assertFalse($response->getData()['success']);
    }
}
