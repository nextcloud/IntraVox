<?php
declare(strict_types=1);

namespace OCA\IntraVox\Tests\Unit\Controller;

use OCA\IntraVox\Controller\ApiController;
use OCA\IntraVox\Tests\Unit\Service\Harness\BuildsPageRead;
use OCA\IntraVox\Exception\ForbiddenException;
use OCA\IntraVox\Service\EngagementSettingsService;
use OCA\IntraVox\Service\ImportService;
use OCA\IntraVox\Service\Import\ConfluenceHtmlImportOrchestrator;
use OCA\IntraVox\Service\Import\ZipUploadValidator;
use OCA\IntraVox\Service\VideoDomainPolicy;
use OCA\IntraVox\Service\PageLockService;
use OCA\IntraVox\Service\PublicationSettingsService;
use OCA\IntraVox\Service\PublicShareService;
use OCA\IntraVox\Service\SetupService;
use OCA\IntraVox\Service\TelemetryService;
use OCA\IntraVox\Tests\Mocks\MockGroupManager;
use OCA\IntraVox\Tests\Mocks\MockUserSession;
use OCP\App\IAppManager;
use OCP\AppFramework\Http;
use OCP\IConfig;
use OCP\IRequest;
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

    use \OCA\IntraVox\Tests\Unit\Service\Harness\BuildsPageRead;
    use \OCA\IntraVox\Tests\Unit\Controller\Harness\BuildsPageLister;
    // fakeFolderContext(): a real FolderContext whose getLanguageFolder seam returns
    // the fixture folder — reorderPages() is the only endpoint that resolves it.
    use \OCA\IntraVox\Tests\Unit\Service\Harness\BuildsFolderFixtures;
    use \OCA\IntraVox\Tests\Unit\Service\Harness\BuildsServiceDoubles;
    private ApiController $controller;
    // The PageService god-facade is gone from ApiController (fase-9): the eight
    // page endpoints call the domain services directly. These are the mocks the
    // create/update/delete/move/copy/reorder tests set expectations on — the same
    // ->with()/->willReturn()/->willThrowException() pins, moved off the facade
    // onto the concrete service that now owns each method.
    private \OCA\IntraVox\Service\Write\PageWriteService $pageWrite;
    private \OCA\IntraVox\Service\Compose\PageCompositionService $composition;
    private \OCA\IntraVox\Service\Structure\PageStructureService $structure;
    private \OCA\IntraVox\Service\Reorder\PageReorderer $reorderer;
    private \OCA\IntraVox\Service\Search\PageSearchEngine $searchEngine;
    private \OCA\IntraVox\Service\Folder\FolderContext $folders;
    private \OCA\IntraVox\Service\Read\PageReadService $pageRead;
    /** getPage(id) behaviour a test installs (fase-4 C6: getPage → PageReadService). */
    private \Closure $getPageFn;
    private \OCA\IntraVox\Service\PermissionService $permissionService;
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
    private PageLockService $pageLockService;
    private IAppManager $appManager;
    private \OCA\IntraVox\Service\Publication\PublicationStateService $publicationState;
    private \OCA\IntraVox\Service\Listing\PageLister $pageLister;
    private \OCA\IntraVox\Service\Homepage\HomepageResolverService $homepageResolver;
    private \OCA\IntraVox\Service\News\NewsWidgetService $newsWidget;
    private \OCA\IntraVox\Service\Path\BreadcrumbService $breadcrumbService;
    private \OCA\IntraVox\Service\Tree\PageTreeService $treeService;

    protected function setUp(): void {
        parent::setUp();

        // Create mocks. The domain services are no longer final (fase-9), so the
        // create/update/delete/move/copy/reorder tests mock them directly — the
        // same expectations that used to sit on the PageService facade.
        $this->pageWrite = $this->createMock(\OCA\IntraVox\Service\Write\PageWriteService::class);
        $this->composition = $this->createMock(\OCA\IntraVox\Service\Compose\PageCompositionService::class);
        $this->structure = $this->createMock(\OCA\IntraVox\Service\Structure\PageStructureService::class);
        $this->reorderer = $this->createMock(\OCA\IntraVox\Service\Reorder\PageReorderer::class);
        // searchPages is not exercised here; an inert double keeps the ctor happy.
        $this->searchEngine = $this->createMock(\OCA\IntraVox\Service\Search\PageSearchEngine::class);
        // Only reorderPages() reaches folders->languageFolder(); the harness builds a
        // real FolderContext whose getLanguageFolder seam returns this fixture folder
        // (the reorderer mock intercepts reorder() regardless of the folder identity).
        $this->folders = $this->fakeFolderContext(
            languageFolder: $this->createMock(\OCP\Files\Folder::class)
        );
        // getPage now comes from a real PageReadService that delegates to the
        // per-test $this->getPageFn (default: page not found). fase-4 C6.
        $this->getPageFn = fn(string $id) => null;
        $this->pageRead = $this->fakePageReadFrom(fn(string $id) => ($this->getPageFn)($id));
        $this->permissionService = $this->createMock(\OCA\IntraVox\Service\PermissionService::class);
        $this->publicationState = $this->createMock(\OCA\IntraVox\Service\Publication\PublicationStateService::class);
        $this->setupService = $this->createMock(SetupService::class);
        $this->engagementSettings = $this->createMock(EngagementSettingsService::class);
        $this->publicationSettings = $this->createMock(PublicationSettingsService::class);
        $this->publicShareService = $this->createMock(PublicShareService::class);
        $this->telemetryService = $this->createMock(TelemetryService::class);
        $this->importService = $this->createMock(ImportService::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->config = $this->createMock(IConfig::class);
        $this->request = $this->createMock(IRequest::class);
        $this->pageLockService = $this->createMock(PageLockService::class);
        $this->appManager = $this->createMock(IAppManager::class);
        // PageLister is final (cannot be mocked). Default to a real one over an empty
        // index; listPages tests reassign a rigged one via fakePageListerReturning /
        // fakePageListerThrowing and rebuild the controller with buildController().
        $this->pageLister = $this->fakePageListerReturning([]);
        // HomepageResolverService is final too, but the setHomepage endpoint is not
        // exercised in this test, so an inert real instance suffices (closure-free
        // ctor → doubleOrBuild constructs it over auto-filled collaborators).
        $this->homepageResolver = $this->doubleOrBuild(\OCA\IntraVox\Service\Homepage\HomepageResolverService::class);
        // NewsWidgetService is final too; the getNews endpoint is not exercised in this
        // test, so an inert real one suffices (closure-free ctor → doubleOrBuild).
        $this->newsWidget = $this->doubleOrBuild(\OCA\IntraVox\Service\News\NewsWidgetService::class);
        // BreadcrumbService is final; getBreadcrumb moved off PageService (fase-6 T1).
        // Default returns a trail; the breadcrumb-fails test rebuilds with a throw.
        $this->breadcrumbService = $this->fakeBreadcrumbService();
        // PageTreeService is final; the getPageTree endpoint is not exercised here, so
        // an inert real one suffices (closure-free ctor -> doubleOrBuild).
        $this->treeService = $this->doubleOrBuild(\OCA\IntraVox\Service\Tree\PageTreeService::class);

        // Use real mock implementations for user/group
        $this->userSession = MockUserSession::loggedInAs('testuser');
        $this->groupManager = MockGroupManager::noAdmins();

        $this->controller = $this->buildController();
    }

    /**
     * Construct the ApiController from the current mock fields. Tests that reassign a
     * field (a rigged PageLister, an admin userSession, an If-None-Match request)
     * call this again to rebuild with the new collaborator. $request overrides the
     * shared $this->request for the one ETag test that needs a second request.
     */
    private function buildController(?IRequest $request = null): ApiController {
        return new ApiController(
            'intravox',
            $request ?? $this->request,
            $this->pageWrite,
            $this->composition,
            $this->structure,
            $this->reorderer,
            $this->searchEngine,
            $this->folders,
            $this->pageRead,
            $this->permissionService,
            $this->setupService,
            $this->logger,
            $this->config,
            $this->groupManager,
            $this->userSession,
            $this->pageLockService,
            $this->appManager,
            $this->publicationState,
            $this->pageLister,
            $this->homepageResolver,
            $this->newsWidget,
            $this->breadcrumbService,
            $this->treeService
        );
    }

    // ==========================================
    // listPages Tests
    // ==========================================

    public function testListPagesReturnsEmptyArrayWhenNoPages(): void {
        $this->pageLister = $this->fakePageListerReturning([]);
        $this->controller = $this->buildController();

        $response = $this->controller->listPages();

        $this->assertEquals(Http::STATUS_OK, $response->getStatus());
        $this->assertEquals([], $response->getData());
    }

    public function testListPagesReturnsOnlyReadablePages(): void {
        // The real PageLister returns uniqueId-keyed rows in stable (title,uniqueId)
        // order; titles are chosen so the two readable pages come back A-Page then
        // C-Page. The unreadable middle page is dropped by the controller's canRead
        // filter, not by the lister.
        $this->pageLister = $this->fakePageListerReturning([
            ['uniqueId' => 'page-1', 'title' => 'A Page', 'permissions' => ['canRead' => true, 'canWrite' => true]],
            ['uniqueId' => 'page-2', 'title' => 'B Page', 'permissions' => ['canRead' => false, 'canWrite' => false]],
            ['uniqueId' => 'page-3', 'title' => 'C Page', 'permissions' => ['canRead' => true, 'canWrite' => false]],
        ]);
        $this->controller = $this->buildController();

        $response = $this->controller->listPages();

        $this->assertEquals(Http::STATUS_OK, $response->getStatus());
        $data = $response->getData();
        $this->assertCount(2, $data);
        $this->assertEquals('page-1', $data[0]['uniqueId']);
        $this->assertEquals('page-3', $data[1]['uniqueId']);
    }

    public function testListPagesReturnsEmptyWhenFolderNotFound(): void {
        $this->pageLister = $this->fakePageListerThrowing(new \Exception('IntraVox folder not found'));
        $this->controller = $this->buildController();

        $response = $this->controller->listPages();

        $this->assertEquals(Http::STATUS_OK, $response->getStatus());
        $this->assertEquals([], $response->getData());
    }

    public function testListPagesReturnsErrorOnException(): void {
        $this->pageLister = $this->fakePageListerThrowing(new \Exception('Database error'));
        $this->controller = $this->buildController();

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

        $this->getPageFn = fn(string $id) => $page;

        // breadcrumbService (fake) returns a trail; the test only asserts the response
        // carries a 'breadcrumb' key, not its content (that is BreadcrumbServiceTest's).

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

        $this->getPageFn = fn(string $id) => $page;

        $response = $this->controller->getPage('page-123');

        $this->assertEquals(Http::STATUS_FORBIDDEN, $response->getStatus());
        $this->assertEquals(['error' => 'Access denied'], $response->getData());
    }

    public function testGetPageReturnsNotFoundForInvalidId(): void {
        $this->getPageFn = function (string $id) { throw new \Exception('Page not found'); };

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

        $this->getPageFn = fn(string $id) => $page;
        // A breadcrumb service whose build() throws -> the getPage handler's catch
        // falls back to an empty breadcrumb.
        $this->breadcrumbService = $this->fakeBreadcrumbService(new \Exception('Breadcrumb failed'));
        $this->controller = $this->buildController();

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

        $this->getPageFn = fn(string $id) => $page;
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

        $this->getPageFn = fn(string $id) => $page;

        // First request: capture the ETag the controller assigns.
        $this->request->method('getHeader')->willReturn('');
        $first = $this->controller->getPage('page-etag');
        $etag = $this->getResponseHeaders($first)['ETag'];

        // Second request: replay the ETag in If-None-Match. We rebuild the
        // controller so the new mock for getHeader takes effect.
        $newRequest = $this->createMock(\OCP\IRequest::class);
        $newRequest->method('getHeader')->willReturn($etag);
        $controller = $this->buildController($newRequest);

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

        $this->permissionService->method('getFolderPermissions')
            ->willReturn(['canCreate' => true]);

        $this->pageWrite->method('createPage')
            ->willReturn($createdPage);

        $response = $this->controller->createPage();

        $this->assertEquals(Http::STATUS_CREATED, $response->getStatus());
        $this->assertEquals('page-new', $response->getData()['id']);
    }

    /**
     * IV-06: createPage must NOT honour a client-supplied uniqueId or
     * translationGroup — otherwise an editor could hijack the identity of a
     * page they cannot edit. The service mints these server-side.
     */
    public function testCreatePageStripsClientUniqueIdAndTranslationGroup(): void {
        $this->request->method('getParams')->willReturn([
            'title' => 'New Page',
            'uniqueId' => 'page-victim-uuid',
            'translationGroup' => 'tg-victim',
        ]);

        $this->permissionService->method('getFolderPermissions')
            ->willReturn(['canCreate' => true]);

        $captured = null;
        $this->pageWrite->method('createPage')
            ->willReturnCallback(function (array $data) use (&$captured) {
                $captured = $data;
                return ['id' => 'page-new', 'uniqueId' => 'page-new', 'title' => 'New Page'];
            });

        $this->controller->createPage();

        $this->assertIsArray($captured);
        $this->assertArrayNotHasKey('uniqueId', $captured, 'client uniqueId must be stripped');
        $this->assertArrayNotHasKey('translationGroup', $captured, 'client translationGroup must be stripped');
        $this->assertSame('New Page', $captured['title']);
    }

    public function testCreatePageReturnsForbiddenWithoutPermission(): void {
        $this->request->method('getParams')->willReturn(['title' => 'New Page']);

        $this->permissionService->method('getFolderPermissions')
            ->willReturn(['canCreate' => false]);

        $response = $this->controller->createPage();

        $this->assertEquals(Http::STATUS_FORBIDDEN, $response->getStatus());
        $this->assertStringContainsString('Permission denied', $response->getData()['error']);
    }

    public function testCreatePageReturnsBadRequestForInvalidData(): void {
        $this->request->method('getParams')->willReturn(['title' => '']);

        $this->permissionService->method('getFolderPermissions')
            ->willReturn(['canCreate' => true]);

        $this->pageWrite->method('createPage')
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

        $this->getPageFn = fn(string $id) => $existingPage;

        $this->request->method('getParams')->willReturn(['title' => 'New Title']);

        $this->pageWrite->method('updatePage')
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

        $this->getPageFn = fn(string $id) => $existingPage;

        $response = $this->controller->updatePage('page-123');

        $this->assertEquals(Http::STATUS_FORBIDDEN, $response->getStatus());
    }

    public function testUpdatePageReturnsBadRequestForInvalidData(): void {
        $existingPage = [
            'id' => 'page-123',
            'permissions' => ['canRead' => true, 'canWrite' => true]
        ];

        $this->getPageFn = fn(string $id) => $existingPage;
        $this->request->method('getParams')->willReturn(['title' => null]);

        $this->pageWrite->method('updatePage')
            ->willThrowException(new \InvalidArgumentException('Invalid page data'));

        $response = $this->controller->updatePage('page-123');

        $this->assertEquals(Http::STATUS_BAD_REQUEST, $response->getStatus());
    }

    public function testUpdatePageReturnsForbiddenWhenServiceThrowsForbidden(): void {
        // #70 regression guard: a read-only GroupFolder write must surface as 403,
        // NOT the old 400 "Failed to write updated page data".
        $existingPage = [
            'id' => 'page-123',
            // The pre-write guard passes (canWrite true) so we reach the service,
            // which then throws ForbiddenException from its isUpdateable() preflight.
            'permissions' => ['canRead' => true, 'canWrite' => true]
        ];

        $this->getPageFn = fn(string $id) => $existingPage;
        $this->request->method('getParams')->willReturn(['title' => 'x']);
        $this->pageWrite->method('updatePage')
            ->willThrowException(new ForbiddenException('You do not have permission to edit this page'));

        $response = $this->controller->updatePage('page-123');

        $this->assertEquals(Http::STATUS_FORBIDDEN, $response->getStatus());
        $this->assertStringContainsString('permission', $response->getData()['error']);
    }

    public function testCreatePageReturnsForbiddenWhenServiceThrowsForbidden(): void {
        $this->request->method('getParams')->willReturn(['title' => 'x']);
        $this->permissionService->method('getFolderPermissions')->willReturn(['canCreate' => true]);
        $this->pageWrite->method('createPage')
            ->willThrowException(new ForbiddenException('You do not have permission to create a page here'));

        $response = $this->controller->createPage();

        $this->assertEquals(Http::STATUS_FORBIDDEN, $response->getStatus());
    }

    // ==========================================
    // deletePage Tests
    // ==========================================

    public function testDeletePageSuccessful(): void {
        $existingPage = [
            'id' => 'page-123',
            'permissions' => ['canRead' => true, 'canDelete' => true]
        ];

        $this->getPageFn = fn(string $id) => $existingPage;
        $this->pageWrite->expects($this->once())
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

        $this->getPageFn = fn(string $id) => $existingPage;

        $response = $this->controller->deletePage('page-123');

        $this->assertEquals(Http::STATUS_FORBIDDEN, $response->getStatus());
    }

    public function testDeletePageReturnsErrorOnException(): void {
        $existingPage = [
            'id' => 'page-123',
            'permissions' => ['canRead' => true, 'canDelete' => true]
        ];

        $this->getPageFn = fn(string $id) => $existingPage;
        $this->pageWrite->method('deletePage')
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

        $this->getPageFn = fn(string $id) => $existingPage;
        $this->pageWrite->method('deletePage')
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
        $this->getPageFn = fn(string $id) => ['id' => 'page-1', 'permissions' => ['canWrite' => false]];

        $response = $this->controller->movePage('page-1', 'page-parent');

        $this->assertEquals(Http::STATUS_FORBIDDEN, $response->getStatus());
    }

    public function testMovePageReturnsForbiddenWithoutCreateOnTarget(): void {
        $this->getPageFn = function ($id) {
            if ($id === 'page-1') {
                return ['id' => 'page-1', 'permissions' => ['canWrite' => true]];
            }
            return ['id' => 'page-parent', 'path' => 'en/parent', 'permissions' => ['canWrite' => true]];
        };
        $this->permissionService->method('getFolderPermissions')
            ->with('en/parent')
            ->willReturn(['canCreate' => false]);

        $response = $this->controller->movePage('page-1', 'page-parent');

        $this->assertEquals(Http::STATUS_FORBIDDEN, $response->getStatus());
    }

    public function testMovePageHappyPathDelegatesToService(): void {
        $this->getPageFn = function ($id) {
            if ($id === 'page-1') {
                return ['id' => 'page-1', 'permissions' => ['canWrite' => true]];
            }
            return ['id' => 'page-parent', 'path' => 'en/parent', 'permissions' => ['canWrite' => true]];
        };
        $this->permissionService->method('getFolderPermissions')->willReturn(['canCreate' => true]);
        $this->structure->expects($this->once())
            ->method('movePage')
            ->with('page-1', 'page-parent');

        $response = $this->controller->movePage('page-1', 'page-parent');

        $this->assertEquals(Http::STATUS_OK, $response->getStatus());
        $this->assertEquals(['success' => true], $response->getData());
    }

    public function testMovePageRejectsCycleWithBadRequest(): void {
        $this->getPageFn = function ($id) {
            if ($id === 'page-1') {
                return ['id' => 'page-1', 'permissions' => ['canWrite' => true]];
            }
            return ['id' => 'page-child', 'path' => 'en/1/child', 'permissions' => ['canWrite' => true]];
        };
        $this->permissionService->method('getFolderPermissions')->willReturn(['canCreate' => true]);
        $this->structure->method('movePage')
            ->willThrowException(new \InvalidArgumentException('Cannot move a page into itself or its descendant'));

        $response = $this->controller->movePage('page-1', 'page-child');

        $this->assertEquals(Http::STATUS_BAD_REQUEST, $response->getStatus());
        $this->assertStringContainsString('itself or its descendant', $response->getData()['error']);
    }

    public function testMovePageRejectsDepthLimitWithBadRequest(): void {
        $this->getPageFn = function ($id) {
            if ($id === 'page-1') {
                return ['id' => 'page-1', 'permissions' => ['canWrite' => true]];
            }
            return ['id' => 'page-deep', 'path' => 'en/a/b/c/d', 'permissions' => ['canWrite' => true]];
        };
        $this->permissionService->method('getFolderPermissions')->willReturn(['canCreate' => true]);
        $this->structure->method('movePage')
            ->willThrowException(new \InvalidArgumentException('Maximum nesting depth exceeded'));

        $response = $this->controller->movePage('page-1', 'page-deep');

        $this->assertEquals(Http::STATUS_BAD_REQUEST, $response->getStatus());
    }

    public function testMovePageHomepageProtectedReturnsBadRequest(): void {
        $this->getPageFn = function ($id) {
            if ($id === 'page-home') {
                return ['id' => 'page-home', 'permissions' => ['canWrite' => true]];
            }
            return ['id' => 'page-parent', 'path' => 'en/parent', 'permissions' => ['canWrite' => true]];
        };
        $this->permissionService->method('getFolderPermissions')->willReturn(['canCreate' => true]);
        $this->structure->method('movePage')
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
        $this->getPageFn = fn(string $id) => ['id' => 'page-src', 'permissions' => ['canRead' => false]];

        $response = $this->controller->copyPage('page-src', null, null);

        $this->assertEquals(Http::STATUS_FORBIDDEN, $response->getStatus());
    }

    public function testCopyPageForbiddenWithoutCreateOnTarget(): void {
        $this->getPageFn = function ($id) {
            if ($id === 'page-src') {
                return ['id' => 'page-src', 'permissions' => ['canRead' => true]];
            }
            return ['id' => 'page-parent', 'path' => 'en/parent', 'permissions' => ['canRead' => true]];
        };
        $this->permissionService->method('getFolderPermissions')
            ->with('en/parent')
            ->willReturn(['canCreate' => false]);

        $response = $this->controller->copyPage('page-src', 'page-parent', null);

        $this->assertEquals(Http::STATUS_FORBIDDEN, $response->getStatus());
    }

    public function testCopyPageHappyPathReturnsCreated(): void {
        $this->getPageFn = function ($id) {
            return ['id' => $id, 'path' => 'en/parent', 'permissions' => ['canRead' => true]];
        };
        $this->permissionService->method('getFolderPermissions')->willReturn(['canCreate' => true]);
        $copy = ['uniqueId' => 'page-new', 'title' => 'Thing (copy)', 'status' => 'draft'];
        // copyPage now goes to the COMPOSE service with the createPage closure as a
        // 4th arg (the write-service binding the #70 preflight rides on); the first
        // three args are pinned exactly as before, the closure matched by type.
        $this->composition->expects($this->once())
            ->method('copyPage')
            ->with('page-src', 'page-parent', null, $this->isInstanceOf(\Closure::class))
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
        $this->permissionService->method('getFolderPermissions')
            ->with('')
            ->willReturn(['canWrite' => false]);

        $response = $this->controller->reorderPages(null, ['page-a', 'page-b']);

        $this->assertEquals(Http::STATUS_FORBIDDEN, $response->getStatus());
    }

    public function testReorderForbiddenWithoutWriteOnParent(): void {
        $this->getPageFn = fn(string $id) => ['id' => 'page-parent', 'path' => 'en/parent'];
        $this->permissionService->method('getFolderPermissions')
            ->with('en/parent')
            ->willReturn(['canWrite' => false]);

        $response = $this->controller->reorderPages('page-parent', ['page-a', 'page-b']);

        $this->assertEquals(Http::STATUS_FORBIDDEN, $response->getStatus());
    }

    public function testReorderRootNormalizesEmptyParentToNull(): void {
        $this->permissionService->method('getFolderPermissions')->willReturn(['canWrite' => true]);
        // The controller must pass null (not '') to the reorderer for the root, plus
        // the resolved language folder as the third arg (PageService::reorderSiblings
        // resolved it through FolderContext and handed it to PageReorderer::reorder).
        $this->reorderer->expects($this->once())
            ->method('reorder')
            ->with(null, ['page-a', 'page-b'], $this->isInstanceOf(\OCP\Files\Folder::class));

        $response = $this->controller->reorderPages('', ['page-a', 'page-b']);

        $this->assertEquals(Http::STATUS_OK, $response->getStatus());
    }

    public function testReorderHappyPathDelegatesWithParent(): void {
        $this->getPageFn = fn(string $id) => ['id' => 'page-parent', 'path' => 'en/parent'];
        $this->permissionService->method('getFolderPermissions')->willReturn(['canWrite' => true]);
        $this->reorderer->expects($this->once())
            ->method('reorder')
            ->with('page-parent', ['page-b', 'page-a'], $this->isInstanceOf(\OCP\Files\Folder::class));

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
        $this->controller = $this->buildController();

        // Use reflection to test private method
        $reflection = new \ReflectionClass($this->controller);
        $method = $reflection->getMethod('isAdmin');

        $this->assertTrue($method->invoke($this->controller));
    }

    public function testIsAdminReturnsFalseForRegularUser(): void {
        // Use reflection to test private method
        $reflection = new \ReflectionClass($this->controller);
        $method = $reflection->getMethod('isAdmin');

        $this->assertFalse($method->invoke($this->controller));
    }

    public function testIsAdminReturnsFalseWhenNotLoggedIn(): void {
        $this->userSession = MockUserSession::anonymous();

        $this->controller = $this->buildController();

        $reflection = new \ReflectionClass($this->controller);
        $method = $reflection->getMethod('isAdmin');

        $this->assertFalse($method->invoke($this->controller));
    }
}
