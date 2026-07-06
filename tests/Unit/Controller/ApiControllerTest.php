<?php
declare(strict_types=1);

namespace OCA\IntraVox\Tests\Unit\Controller;

use OCA\IntraVox\Controller\ApiController;
use OCA\IntraVox\Service\EngagementSettingsService;
use OCA\IntraVox\Service\ImportService;
use OCA\IntraVox\Service\NavigationService;
use OCA\IntraVox\Service\PageLockService;
use OCA\IntraVox\Service\PageService;
use OCA\IntraVox\Service\PermissionService;
use OCA\IntraVox\Service\PublicationSettingsService;
use OCA\IntraVox\Service\PublicShareService;
use OCA\IntraVox\Service\SetupService;
use OCA\IntraVox\Service\SystemFileService;
use OCA\IntraVox\Service\TelemetryService;
use OCA\IntraVox\Tests\Mocks\MockGroupManager;
use OCA\IntraVox\Tests\Mocks\MockUserSession;
use OCP\App\IAppManager;
use OCP\AppFramework\Http;
use OCP\IConfig;
use OCP\IRequest;
use OCP\ISession;
use OCP\ITempManager;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Unit tests for ApiController
 *
 * Tests cover:
 * - Core CRUD operations (listPages, getPage, createPage, updatePage, deletePage)
 * - Permission checks
 * - Error handling
 * - Admin-only endpoints
 */
class ApiControllerTest extends TestCase {
    private ApiController $controller;
    private PageService $pageService;
    private SetupService $setupService;
    private EngagementSettingsService $engagementSettings;
    private PublicationSettingsService $publicationSettings;
    private PublicShareService $publicShareService;
    private TelemetryService $telemetryService;
    private ImportService $importService;
    private LoggerInterface $logger;
    private IConfig $config;
    private MockGroupManager $groupManager;
    private MockUserSession $userSession;
    private IRequest $request;
    private ITempManager $tempManager;
    private SystemFileService $systemFileService;
    private NavigationService $navigationService;
    private PermissionService $permissionService;
    private PageLockService $pageLockService;
    private ISession $session;
    private IAppManager $appManager;

    protected function setUp(): void {
        parent::setUp();

        // Create mocks
        $this->pageService = $this->createMock(PageService::class);
        $this->setupService = $this->createMock(SetupService::class);
        $this->engagementSettings = $this->createMock(EngagementSettingsService::class);
        $this->publicationSettings = $this->createMock(PublicationSettingsService::class);
        $this->publicShareService = $this->createMock(PublicShareService::class);
        $this->telemetryService = $this->createMock(TelemetryService::class);
        $this->importService = $this->createMock(ImportService::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->config = $this->createMock(IConfig::class);
        $this->request = $this->createMock(IRequest::class);
        $this->tempManager = $this->createMock(ITempManager::class);
        $this->systemFileService = $this->createMock(SystemFileService::class);
        $this->navigationService = $this->createMock(NavigationService::class);
        $this->permissionService = $this->createMock(PermissionService::class);
        $this->pageLockService = $this->createMock(PageLockService::class);
        $this->session = $this->createMock(ISession::class);
        $this->appManager = $this->createMock(IAppManager::class);

        // Use real mock implementations for user/group
        $this->userSession = MockUserSession::loggedInAs('testuser');
        $this->groupManager = MockGroupManager::noAdmins();

        $this->controller = new ApiController(
            'intravox',
            $this->request,
            $this->pageService,
            $this->setupService,
            $this->engagementSettings,
            $this->publicationSettings,
            $this->publicShareService,
            $this->telemetryService,
            $this->importService,
            $this->logger,
            $this->config,
            $this->groupManager,
            $this->userSession,
            $this->tempManager,
            $this->systemFileService,
            $this->navigationService,
            $this->permissionService,
            $this->pageLockService,
            $this->session,
            $this->appManager
        );
    }

    // ==========================================
    // listPages Tests
    // ==========================================

    public function testListPagesReturnsEmptyArrayWhenNoPages(): void {
        $this->pageService->method('listPages')->willReturn([]);

        $response = $this->controller->listPages();

        $this->assertEquals(Http::STATUS_OK, $response->getStatus());
        $this->assertEquals([], $response->getData());
    }

    public function testListPagesReturnsOnlyReadablePages(): void {
        $pages = [
            [
                'id' => 'page-1',
                'title' => 'Readable Page',
                'permissions' => ['canRead' => true, 'canWrite' => true]
            ],
            [
                'id' => 'page-2',
                'title' => 'Unreadable Page',
                'permissions' => ['canRead' => false, 'canWrite' => false]
            ],
            [
                'id' => 'page-3',
                'title' => 'Another Readable',
                'permissions' => ['canRead' => true, 'canWrite' => false]
            ]
        ];

        $this->pageService->method('listPages')->willReturn($pages);

        $response = $this->controller->listPages();

        $this->assertEquals(Http::STATUS_OK, $response->getStatus());
        $data = $response->getData();
        $this->assertCount(2, $data);
        $this->assertEquals('page-1', $data[0]['id']);
        $this->assertEquals('page-3', $data[1]['id']);
    }

    public function testListPagesReturnsEmptyWhenFolderNotFound(): void {
        $this->pageService->method('listPages')
            ->willThrowException(new \Exception('IntraVox folder not found'));

        $response = $this->controller->listPages();

        $this->assertEquals(Http::STATUS_OK, $response->getStatus());
        $this->assertEquals([], $response->getData());
    }

    public function testListPagesReturnsErrorOnException(): void {
        $this->pageService->method('listPages')
            ->willThrowException(new \Exception('Database error'));

        $response = $this->controller->listPages();

        $this->assertEquals(Http::STATUS_INTERNAL_SERVER_ERROR, $response->getStatus());
        $this->assertArrayHasKey('error', $response->getData());
    }

    // ==========================================
    // getPage Tests
    // ==========================================

    public function testGetPageReturnsPageWhenReadable(): void {
        $page = [
            'id' => 'page-123',
            'uniqueId' => 'page-123',
            'title' => 'Test Page',
            'permissions' => ['canRead' => true, 'canWrite' => true]
        ];

        $this->pageService->method('getPage')
            ->with('page-123')
            ->willReturn($page);

        $this->pageService->method('getBreadcrumb')
            ->willReturn([['id' => 'page-123', 'title' => 'Test Page']]);

        $response = $this->controller->getPage('page-123');

        $this->assertEquals(Http::STATUS_OK, $response->getStatus());
        $data = $response->getData();
        $this->assertEquals('page-123', $data['id']);
        $this->assertEquals('Test Page', $data['title']);
        $this->assertArrayHasKey('breadcrumb', $data);
    }

    public function testGetPageReturnsForbiddenWhenNotReadable(): void {
        $page = [
            'id' => 'page-123',
            'title' => 'Secret Page',
            'permissions' => ['canRead' => false, 'canWrite' => false]
        ];

        $this->pageService->method('getPage')
            ->with('page-123')
            ->willReturn($page);

        $response = $this->controller->getPage('page-123');

        $this->assertEquals(Http::STATUS_FORBIDDEN, $response->getStatus());
        $this->assertEquals(['error' => 'Access denied'], $response->getData());
    }

    public function testGetPageReturnsNotFoundForInvalidId(): void {
        $this->pageService->method('getPage')
            ->willThrowException(new \Exception('Page not found'));

        $response = $this->controller->getPage('invalid-id');

        $this->assertEquals(Http::STATUS_NOT_FOUND, $response->getStatus());
        $this->assertArrayHasKey('error', $response->getData());
    }

    public function testGetPageStillWorksWhenBreadcrumbFails(): void {
        $page = [
            'id' => 'page-123',
            'title' => 'Test Page',
            'permissions' => ['canRead' => true]
        ];

        $this->pageService->method('getPage')->willReturn($page);
        $this->pageService->method('getBreadcrumb')
            ->willThrowException(new \Exception('Breadcrumb failed'));

        $response = $this->controller->getPage('page-123');

        $this->assertEquals(Http::STATUS_OK, $response->getStatus());
        $data = $response->getData();
        $this->assertEquals([], $data['breadcrumb']);
    }

    public function testGetPageReturnsEtagAndCacheHeadersOnFreshResponse(): void {
        $page = [
            'id' => 'page-etag',
            'uniqueId' => 'page-etag',
            'title' => 'Cached Page',
            'permissions' => ['canRead' => true]
        ];

        $this->pageService->method('getPage')->willReturn($page);
        $this->pageService->method('getBreadcrumb')->willReturn([]);
        $this->request->method('getHeader')->willReturn('');

        $response = $this->controller->getPage('page-etag');

        $this->assertEquals(Http::STATUS_OK, $response->getStatus());
        $headers = $this->getResponseHeaders($response);
        $this->assertArrayHasKey('ETag', $headers);
        $this->assertMatchesRegularExpression('/^"[a-f0-9]{32}"$/', $headers['ETag']);
        $this->assertArrayHasKey('Cache-Control', $headers);
        $this->assertStringContainsString('must-revalidate', $headers['Cache-Control']);
    }

    public function testGetPageReturnsNotModifiedWhenIfNoneMatchMatches(): void {
        $page = [
            'id' => 'page-etag',
            'uniqueId' => 'page-etag',
            'title' => 'Cached Page',
            'permissions' => ['canRead' => true]
        ];

        $this->pageService->method('getPage')->willReturn($page);
        $this->pageService->method('getBreadcrumb')->willReturn([]);

        // First request: capture the ETag the controller assigns.
        $this->request->method('getHeader')->willReturn('');
        $first = $this->controller->getPage('page-etag');
        $etag = $this->getResponseHeaders($first)['ETag'];

        // Second request: replay the ETag in If-None-Match. We rebuild the
        // controller so the new mock for getHeader takes effect.
        $newRequest = $this->createMock(\OCP\IRequest::class);
        $newRequest->method('getHeader')->willReturn($etag);
        $controller = new ApiController(
            'intravox',
            $newRequest,
            $this->pageService,
            $this->setupService,
            $this->engagementSettings,
            $this->publicationSettings,
            $this->publicShareService,
            $this->telemetryService,
            $this->importService,
            $this->logger,
            $this->config,
            $this->groupManager,
            $this->userSession,
            $this->tempManager,
            $this->systemFileService,
            $this->navigationService,
            $this->permissionService,
            $this->pageLockService,
            $this->session,
            $this->appManager
        );

        $second = $controller->getPage('page-etag');

        $this->assertEquals(Http::STATUS_NOT_MODIFIED, $second->getStatus());
        $this->assertEquals([], $second->getData());
    }

    /**
     * Test-only accessor for protected headers on DataResponse.
     *
     * @return array<string, string>
     */
    private function getResponseHeaders($response): array {
        $reflection = new \ReflectionClass($response);
        // Headers property lives on Response (parent class), so walk up the
        // hierarchy until we find it.
        while ($reflection !== false && !$reflection->hasProperty('headers')) {
            $reflection = $reflection->getParentClass();
        }
        $prop = $reflection->getProperty('headers');
        return $prop->getValue($response);
    }

    // ==========================================
    // createPage Tests
    // ==========================================

    public function testCreatePageSuccessful(): void {
        $inputData = ['title' => 'New Page', 'content' => ''];
        $createdPage = [
            'id' => 'page-new',
            'uniqueId' => 'page-new',
            'title' => 'New Page',
            'permissions' => ['canRead' => true, 'canWrite' => true]
        ];

        $this->request->method('getParams')->willReturn($inputData);

        $this->pageService->method('getFolderPermissions')
            ->willReturn(['canCreate' => true]);

        $this->pageService->method('createPage')
            ->willReturn($createdPage);

        $response = $this->controller->createPage();

        $this->assertEquals(Http::STATUS_CREATED, $response->getStatus());
        $this->assertEquals('page-new', $response->getData()['id']);
    }

    public function testCreatePageReturnsForbiddenWithoutPermission(): void {
        $this->request->method('getParams')->willReturn(['title' => 'New Page']);

        $this->pageService->method('getFolderPermissions')
            ->willReturn(['canCreate' => false]);

        $response = $this->controller->createPage();

        $this->assertEquals(Http::STATUS_FORBIDDEN, $response->getStatus());
        $this->assertStringContainsString('Permission denied', $response->getData()['error']);
    }

    public function testCreatePageReturnsBadRequestForInvalidData(): void {
        $this->request->method('getParams')->willReturn(['title' => '']);

        $this->pageService->method('getFolderPermissions')
            ->willReturn(['canCreate' => true]);

        $this->pageService->method('createPage')
            ->willThrowException(new \InvalidArgumentException('Title is required'));

        $response = $this->controller->createPage();

        $this->assertEquals(Http::STATUS_BAD_REQUEST, $response->getStatus());
        $this->assertStringContainsString('Title', $response->getData()['error']);
    }

    // ==========================================
    // updatePage Tests
    // ==========================================

    public function testUpdatePageSuccessful(): void {
        $existingPage = [
            'id' => 'page-123',
            'title' => 'Old Title',
            'permissions' => ['canRead' => true, 'canWrite' => true]
        ];
        $updatedPage = [
            'id' => 'page-123',
            'title' => 'New Title',
            'permissions' => ['canRead' => true, 'canWrite' => true]
        ];

        $this->pageService->method('getPage')
            ->with('page-123')
            ->willReturn($existingPage);

        $this->request->method('getParams')->willReturn(['title' => 'New Title']);

        $this->pageService->method('updatePage')
            ->willReturn($updatedPage);

        $response = $this->controller->updatePage('page-123');

        $this->assertEquals(Http::STATUS_OK, $response->getStatus());
        $this->assertEquals('New Title', $response->getData()['title']);
    }

    public function testUpdatePageReturnsForbiddenWithoutWritePermission(): void {
        $existingPage = [
            'id' => 'page-123',
            'permissions' => ['canRead' => true, 'canWrite' => false]
        ];

        $this->pageService->method('getPage')->willReturn($existingPage);

        $response = $this->controller->updatePage('page-123');

        $this->assertEquals(Http::STATUS_FORBIDDEN, $response->getStatus());
    }

    public function testUpdatePageReturnsBadRequestForInvalidData(): void {
        $existingPage = [
            'id' => 'page-123',
            'permissions' => ['canRead' => true, 'canWrite' => true]
        ];

        $this->pageService->method('getPage')->willReturn($existingPage);
        $this->request->method('getParams')->willReturn(['title' => null]);

        $this->pageService->method('updatePage')
            ->willThrowException(new \InvalidArgumentException('Invalid page data'));

        $response = $this->controller->updatePage('page-123');

        $this->assertEquals(Http::STATUS_BAD_REQUEST, $response->getStatus());
    }

    // ==========================================
    // deletePage Tests
    // ==========================================

    public function testDeletePageSuccessful(): void {
        $existingPage = [
            'id' => 'page-123',
            'permissions' => ['canRead' => true, 'canDelete' => true]
        ];

        $this->pageService->method('getPage')->willReturn($existingPage);
        $this->pageService->expects($this->once())
            ->method('deletePage')
            ->with('page-123');

        $response = $this->controller->deletePage('page-123');

        $this->assertEquals(Http::STATUS_OK, $response->getStatus());
        $this->assertEquals(['success' => true], $response->getData());
    }

    public function testDeletePageReturnsForbiddenWithoutDeletePermission(): void {
        $existingPage = [
            'id' => 'page-123',
            'permissions' => ['canRead' => true, 'canDelete' => false]
        ];

        $this->pageService->method('getPage')->willReturn($existingPage);

        $response = $this->controller->deletePage('page-123');

        $this->assertEquals(Http::STATUS_FORBIDDEN, $response->getStatus());
    }

    public function testDeletePageReturnsErrorOnException(): void {
        $existingPage = [
            'id' => 'page-123',
            'permissions' => ['canRead' => true, 'canDelete' => true]
        ];

        $this->pageService->method('getPage')->willReturn($existingPage);
        $this->pageService->method('deletePage')
            ->willThrowException(new \Exception('Delete failed'));

        $response = $this->controller->deletePage('page-123');

        $this->assertEquals(Http::STATUS_INTERNAL_SERVER_ERROR, $response->getStatus());
    }

    public function testDeletePageHomepageProtectedReturnsBadRequest(): void {
        // deletePage() throwing HOMEPAGE_PROTECTED must surface as 400 (not 500)
        // so the UI can show "reassign the homepage first".
        $existingPage = [
            'id' => 'page-home',
            'permissions' => ['canRead' => true, 'canDelete' => true]
        ];

        $this->pageService->method('getPage')->willReturn($existingPage);
        $this->pageService->method('deletePage')
            ->willThrowException(new \InvalidArgumentException('HOMEPAGE_PROTECTED'));

        $response = $this->controller->deletePage('page-home');

        $this->assertEquals(Http::STATUS_BAD_REQUEST, $response->getStatus());
        $this->assertEquals('HOMEPAGE_PROTECTED', $response->getData()['error']);
    }

    // ==========================================
    // movePage Tests (issue #69)
    // ==========================================

    public function testMovePageReturnsBadRequestWhenPageIdMissing(): void {
        $response = $this->controller->movePage(null, 'page-parent');

        $this->assertEquals(Http::STATUS_BAD_REQUEST, $response->getStatus());
        $this->assertStringContainsStringIgnoringCase('pageId', $response->getData()['error']);
    }

    public function testMovePageReturnsForbiddenWithoutWriteOnSource(): void {
        $this->pageService->method('getPage')
            ->with('page-1')
            ->willReturn(['id' => 'page-1', 'permissions' => ['canWrite' => false]]);

        $response = $this->controller->movePage('page-1', 'page-parent');

        $this->assertEquals(Http::STATUS_FORBIDDEN, $response->getStatus());
    }

    public function testMovePageReturnsForbiddenWithoutCreateOnTarget(): void {
        $this->pageService->method('getPage')->willReturnCallback(function ($id) {
            if ($id === 'page-1') {
                return ['id' => 'page-1', 'permissions' => ['canWrite' => true]];
            }
            return ['id' => 'page-parent', 'path' => 'en/parent', 'permissions' => ['canWrite' => true]];
        });
        $this->pageService->method('getFolderPermissions')
            ->with('en/parent')
            ->willReturn(['canCreate' => false]);

        $response = $this->controller->movePage('page-1', 'page-parent');

        $this->assertEquals(Http::STATUS_FORBIDDEN, $response->getStatus());
    }

    public function testMovePageHappyPathDelegatesToService(): void {
        $this->pageService->method('getPage')->willReturnCallback(function ($id) {
            if ($id === 'page-1') {
                return ['id' => 'page-1', 'permissions' => ['canWrite' => true]];
            }
            return ['id' => 'page-parent', 'path' => 'en/parent', 'permissions' => ['canWrite' => true]];
        });
        $this->pageService->method('getFolderPermissions')->willReturn(['canCreate' => true]);
        $this->pageService->expects($this->once())
            ->method('movePage')
            ->with('page-1', 'page-parent');

        $response = $this->controller->movePage('page-1', 'page-parent');

        $this->assertEquals(Http::STATUS_OK, $response->getStatus());
        $this->assertEquals(['success' => true], $response->getData());
    }

    public function testMovePageRejectsCycleWithBadRequest(): void {
        $this->pageService->method('getPage')->willReturnCallback(function ($id) {
            if ($id === 'page-1') {
                return ['id' => 'page-1', 'permissions' => ['canWrite' => true]];
            }
            return ['id' => 'page-child', 'path' => 'en/1/child', 'permissions' => ['canWrite' => true]];
        });
        $this->pageService->method('getFolderPermissions')->willReturn(['canCreate' => true]);
        $this->pageService->method('movePage')
            ->willThrowException(new \InvalidArgumentException('Cannot move a page into itself or its descendant'));

        $response = $this->controller->movePage('page-1', 'page-child');

        $this->assertEquals(Http::STATUS_BAD_REQUEST, $response->getStatus());
        $this->assertStringContainsString('itself or its descendant', $response->getData()['error']);
    }

    public function testMovePageRejectsDepthLimitWithBadRequest(): void {
        $this->pageService->method('getPage')->willReturnCallback(function ($id) {
            if ($id === 'page-1') {
                return ['id' => 'page-1', 'permissions' => ['canWrite' => true]];
            }
            return ['id' => 'page-deep', 'path' => 'en/a/b/c/d', 'permissions' => ['canWrite' => true]];
        });
        $this->pageService->method('getFolderPermissions')->willReturn(['canCreate' => true]);
        $this->pageService->method('movePage')
            ->willThrowException(new \InvalidArgumentException('Maximum nesting depth exceeded'));

        $response = $this->controller->movePage('page-1', 'page-deep');

        $this->assertEquals(Http::STATUS_BAD_REQUEST, $response->getStatus());
    }

    public function testMovePageHomepageProtectedReturnsBadRequest(): void {
        $this->pageService->method('getPage')->willReturnCallback(function ($id) {
            if ($id === 'page-home') {
                return ['id' => 'page-home', 'permissions' => ['canWrite' => true]];
            }
            return ['id' => 'page-parent', 'path' => 'en/parent', 'permissions' => ['canWrite' => true]];
        });
        $this->pageService->method('getFolderPermissions')->willReturn(['canCreate' => true]);
        $this->pageService->method('movePage')
            ->willThrowException(new \InvalidArgumentException('HOMEPAGE_PROTECTED'));

        $response = $this->controller->movePage('page-home', 'page-parent');

        $this->assertEquals(Http::STATUS_BAD_REQUEST, $response->getStatus());
        $this->assertEquals('HOMEPAGE_PROTECTED', $response->getData()['error']);
    }

    // ==========================================
    // copyPage Tests (copy page feature)
    // ==========================================

    public function testCopyPageReturnsBadRequestWhenSourceIdMissing(): void {
        $response = $this->controller->copyPage(null, null, null);

        $this->assertEquals(Http::STATUS_BAD_REQUEST, $response->getStatus());
        $this->assertStringContainsStringIgnoringCase('sourceId', $response->getData()['error']);
    }

    public function testCopyPageForbiddenWhenSourceNotReadable(): void {
        $this->pageService->method('getPage')
            ->with('page-src')
            ->willReturn(['id' => 'page-src', 'permissions' => ['canRead' => false]]);

        $response = $this->controller->copyPage('page-src', null, null);

        $this->assertEquals(Http::STATUS_FORBIDDEN, $response->getStatus());
    }

    public function testCopyPageForbiddenWithoutCreateOnTarget(): void {
        $this->pageService->method('getPage')->willReturnCallback(function ($id) {
            if ($id === 'page-src') {
                return ['id' => 'page-src', 'permissions' => ['canRead' => true]];
            }
            return ['id' => 'page-parent', 'path' => 'en/parent', 'permissions' => ['canRead' => true]];
        });
        $this->pageService->method('getFolderPermissions')
            ->with('en/parent')
            ->willReturn(['canCreate' => false]);

        $response = $this->controller->copyPage('page-src', 'page-parent', null);

        $this->assertEquals(Http::STATUS_FORBIDDEN, $response->getStatus());
    }

    public function testCopyPageHappyPathReturnsCreated(): void {
        $this->pageService->method('getPage')->willReturnCallback(function ($id) {
            return ['id' => $id, 'path' => 'en/parent', 'permissions' => ['canRead' => true]];
        });
        $this->pageService->method('getFolderPermissions')->willReturn(['canCreate' => true]);
        $copy = ['uniqueId' => 'page-new', 'title' => 'Thing (copy)', 'status' => 'draft'];
        $this->pageService->expects($this->once())
            ->method('copyPage')
            ->with('page-src', 'page-parent', null)
            ->willReturn($copy);

        $response = $this->controller->copyPage('page-src', 'page-parent', null);

        $this->assertEquals(Http::STATUS_CREATED, $response->getStatus());
        $data = $response->getData();
        $this->assertTrue($data['success']);
        $this->assertEquals($copy, $data['page']);
    }

    // ==========================================
    // reorderPages Tests (issue #69)
    // ==========================================

    public function testReorderReturnsBadRequestForEmptyOrderedIds(): void {
        $response = $this->controller->reorderPages(null, []);

        $this->assertEquals(Http::STATUS_BAD_REQUEST, $response->getStatus());
        $this->assertStringContainsString('non-empty', $response->getData()['error']);
    }

    public function testReorderReturnsBadRequestForNonStringId(): void {
        $response = $this->controller->reorderPages(null, ['page-a', '']);

        $this->assertEquals(Http::STATUS_BAD_REQUEST, $response->getStatus());
        $this->assertStringContainsString('non-empty strings', $response->getData()['error']);
    }

    public function testReorderForbiddenWithoutWriteOnRoot(): void {
        $this->pageService->method('getFolderPermissions')
            ->with('')
            ->willReturn(['canWrite' => false]);

        $response = $this->controller->reorderPages(null, ['page-a', 'page-b']);

        $this->assertEquals(Http::STATUS_FORBIDDEN, $response->getStatus());
    }

    public function testReorderForbiddenWithoutWriteOnParent(): void {
        $this->pageService->method('getPage')
            ->with('page-parent')
            ->willReturn(['id' => 'page-parent', 'path' => 'en/parent']);
        $this->pageService->method('getFolderPermissions')
            ->with('en/parent')
            ->willReturn(['canWrite' => false]);

        $response = $this->controller->reorderPages('page-parent', ['page-a', 'page-b']);

        $this->assertEquals(Http::STATUS_FORBIDDEN, $response->getStatus());
    }

    public function testReorderRootNormalizesEmptyParentToNull(): void {
        $this->pageService->method('getFolderPermissions')->willReturn(['canWrite' => true]);
        // The controller must pass null (not '') to the service for the root.
        $this->pageService->expects($this->once())
            ->method('reorderSiblings')
            ->with(null, ['page-a', 'page-b']);

        $response = $this->controller->reorderPages('', ['page-a', 'page-b']);

        $this->assertEquals(Http::STATUS_OK, $response->getStatus());
    }

    public function testReorderHappyPathDelegatesWithParent(): void {
        $this->pageService->method('getPage')
            ->with('page-parent')
            ->willReturn(['id' => 'page-parent', 'path' => 'en/parent']);
        $this->pageService->method('getFolderPermissions')->willReturn(['canWrite' => true]);
        $this->pageService->expects($this->once())
            ->method('reorderSiblings')
            ->with('page-parent', ['page-b', 'page-a']);

        $response = $this->controller->reorderPages('page-parent', ['page-b', 'page-a']);

        $this->assertEquals(Http::STATUS_OK, $response->getStatus());
        $this->assertEquals(['success' => true], $response->getData());
    }

    // ==========================================
    // Admin Check Tests
    // ==========================================

    public function testIsAdminReturnsTrueForAdminUser(): void {
        $this->userSession = MockUserSession::loggedInAs('admin');
        $this->groupManager = MockGroupManager::withAdmin('admin');

        // Recreate controller with admin user
        $this->controller = new ApiController(
            'intravox',
            $this->request,
            $this->pageService,
            $this->setupService,
            $this->engagementSettings,
            $this->publicationSettings,
            $this->publicShareService,
            $this->telemetryService,
            $this->importService,
            $this->logger,
            $this->config,
            $this->groupManager,
            $this->userSession,
            $this->tempManager,
            $this->systemFileService,
            $this->navigationService,
            $this->permissionService,
            $this->pageLockService,
            $this->session,
            $this->appManager
        );

        // Use reflection to test private method
        $reflection = new \ReflectionClass($this->controller);
        $method = $reflection->getMethod('isAdmin');
        $method->setAccessible(true);

        $this->assertTrue($method->invoke($this->controller));
    }

    public function testIsAdminReturnsFalseForRegularUser(): void {
        // Use reflection to test private method
        $reflection = new \ReflectionClass($this->controller);
        $method = $reflection->getMethod('isAdmin');
        $method->setAccessible(true);

        $this->assertFalse($method->invoke($this->controller));
    }

    public function testIsAdminReturnsFalseWhenNotLoggedIn(): void {
        $this->userSession = MockUserSession::anonymous();

        $this->controller = new ApiController(
            'intravox',
            $this->request,
            $this->pageService,
            $this->setupService,
            $this->engagementSettings,
            $this->publicationSettings,
            $this->publicShareService,
            $this->telemetryService,
            $this->importService,
            $this->logger,
            $this->config,
            $this->groupManager,
            $this->userSession,
            $this->tempManager,
            $this->systemFileService,
            $this->navigationService,
            $this->permissionService,
            $this->pageLockService,
            $this->session,
            $this->appManager
        );

        $reflection = new \ReflectionClass($this->controller);
        $method = $reflection->getMethod('isAdmin');
        $method->setAccessible(true);

        $this->assertFalse($method->invoke($this->controller));
    }
}
