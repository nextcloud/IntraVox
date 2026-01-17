<?php
declare(strict_types=1);

namespace OCA\IntraVox\Tests\Unit\Controller;

use OCA\IntraVox\Controller\ApiController;
use OCA\IntraVox\Service\EngagementSettingsService;
use OCA\IntraVox\Service\ImportService;
use OCA\IntraVox\Service\PageService;
use OCA\IntraVox\Service\PublicationSettingsService;
use OCA\IntraVox\Service\SetupService;
use OCA\IntraVox\Tests\Mocks\MockGroupManager;
use OCA\IntraVox\Tests\Mocks\MockUserSession;
use OCP\AppFramework\Http;
use OCP\IConfig;
use OCP\IRequest;
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
    private ImportService $importService;
    private LoggerInterface $logger;
    private IConfig $config;
    private MockGroupManager $groupManager;
    private MockUserSession $userSession;
    private IRequest $request;
    private ITempManager $tempManager;

    protected function setUp(): void {
        parent::setUp();

        // Create mocks
        $this->pageService = $this->createMock(PageService::class);
        $this->setupService = $this->createMock(SetupService::class);
        $this->engagementSettings = $this->createMock(EngagementSettingsService::class);
        $this->publicationSettings = $this->createMock(PublicationSettingsService::class);
        $this->importService = $this->createMock(ImportService::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->config = $this->createMock(IConfig::class);
        $this->request = $this->createMock(IRequest::class);
        $this->tempManager = $this->createMock(ITempManager::class);

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
            $this->importService,
            $this->logger,
            $this->config,
            $this->groupManager,
            $this->userSession,
            $this->tempManager
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
            $this->importService,
            $this->logger,
            $this->config,
            $this->groupManager,
            $this->userSession,
            $this->tempManager
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
            $this->importService,
            $this->logger,
            $this->config,
            $this->groupManager,
            $this->userSession,
            $this->tempManager
        );

        $reflection = new \ReflectionClass($this->controller);
        $method = $reflection->getMethod('isAdmin');
        $method->setAccessible(true);

        $this->assertFalse($method->invoke($this->controller));
    }
}
