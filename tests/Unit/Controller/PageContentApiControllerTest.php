<?php
declare(strict_types=1);

namespace OCA\IntraVox\Tests\Unit\Controller;

use OCA\IntraVox\Controller\PageContentApiController;
use OCA\IntraVox\Service\Folder\FolderContext;
use OCA\IntraVox\Service\Language\LanguageResolver;
use OCA\IntraVox\Service\Locator\PageLocator;
use OCA\IntraVox\Service\Util\PageIdUtils;
use OCA\IntraVox\Service\Version\PageVersionDomainService;
use OCA\IntraVox\Service\Version\PageVersionService;
use OCP\App\IAppManager;
use OCP\AppFramework\Http;
use OCP\Files\Folder;
use OCP\IRequest;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Characterizes PageContentApiController's read endpoints before Phase 2 replaces
 * their inline canRead gates with a requireReadablePage() trait helper. The pins
 * capture the exact observable contract each endpoint returns TODAY:
 *   - happy path delegates to the domain service and returns its result;
 *   - a page whose permissions.canRead is false -> 403 ['error' => 'Access denied'];
 *   - an exception -> 500 ['error' => <message>].
 *
 * The 500 body currently leaks the exception message; that is a KNOWN issue the
 * plan defers to a separate ApiErrorTrait-adoption PR (behaviour change). It is
 * pinned here so Phase 2 does not alter it by accident — see 'known leak' notes.
 */
class PageContentApiControllerTest extends TestCase {

    use \OCA\IntraVox\Tests\Unit\Service\Harness\BuildsPageRead;
    use \OCA\IntraVox\Tests\Unit\Service\Harness\BuildsCollaboratorFixtures;
    private IAppManager $appManager;
    private PageContentApiController $controller;
    /** The mocked version-manager engine behind the real (final) versionDomain. */
    private PageVersionService $versionEngine;
    /** The mocked locator the real versionDomain resolves pages through. */
    private PageLocator $versionLocator;
    /** getPage(id) behaviour a test installs (fase-4 C6: getPage → PageReadService). */
    private \Closure $getPageFn;
    /**
     * The metadata locator's per-id resolution a test installs (fase-9: metadata →
     * PageMetadataService). Returns ['file'=>File,'folder'=>Folder] for a hit, or
     * null for "page not found". Read at call time so a test can flip it.
     */
    private \Closure $metadataResolveFn;

    protected function setUp(): void {
        $this->appManager = $this->createMock(IAppManager::class);
        // getPage now comes from a real PageReadService that delegates to the
        // per-test $this->getPageFn (default: page not found).
        $this->getPageFn = fn(string $id) => null;
        $pageRead = $this->fakePageReadFrom(fn(string $id) => ($this->getPageFn)($id));

        // PageVersionDomainService is final -> build a real one over mocked
        // collaborators. The controller test only observes delegation + gate +
        // response mapping; the engine mock is the observable sink.
        $this->versionEngine = $this->createMock(PageVersionService::class);
        $this->versionLocator = $this->createMock(PageLocator::class);
        // FolderContext is final -> build a real one whose intraVoxOverride is a
        // fake mount, so readLanguageFolder()/languageFolder() resolve without a
        // real user session. Page resolution itself goes through $versionLocator.
        $fakeMount = $this->createMock(Folder::class);
        // get(<lang>) resolves to a language folder so readLanguageFolder()/
        // languageFolder() never fall into the create-on-miss or null path.
        $fakeMount->method('get')->willReturn($this->createMock(Folder::class));
        $folders = new FolderContext(
            $this->createMock(\OCP\Files\IRootFolder::class),
            'tester',
            $this->createMock(\OCP\IConfig::class),
            $this->createMock(\OCA\IntraVox\Service\LanguageService::class),
            new LanguageResolver(),
            $this->versionLocator,
            $fakeMount
        );
        $versionDomain = new PageVersionDomainService(
            $this->versionEngine,
            $this->createMock(LoggerInterface::class),
            $folders,
            $this->versionLocator,
            new PageIdUtils()
        );
        // The cache-status endpoint calls its own service (fase-4 C5); this suite
        // does not exercise it (PageCacheStatusServiceTest does), so an inert
        // instance satisfies the ctor.
        $cacheStatus = new \OCA\IntraVox\Service\Maintenance\PageCacheStatusService(
            $folders,
            $this->versionLocator,
            new PageIdUtils(),
            $this->createMock(LoggerInterface::class)
        );

        // Metadata now goes straight to the METADATA domain service (fase-9). It is
        // final, so build a REAL one whose PageLocator resolves page ids through the
        // per-test $this->metadataResolveFn (default: not found). getPageMetadata's
        // happy path reads the resolved file's JSON and returns a dict whose 'title'
        // is $data['title'] — the enricher (real, over inert collaborators) preserves
        // it — so a fixture file carrying {"title":...} pins the delegated result.
        $this->metadataResolveFn = fn(string $id) => null;
        $metadataLocator = $this->createMock(PageLocator::class);
        $metadataLocator->method('locatePageAnyLanguage')
            ->willReturnCallback(fn($root, $folder, string $id) => ($this->metadataResolveFn)($id));
        $metadataLocator->method('findPageById')
            ->willReturnCallback(fn($folder, string $id) => ($this->metadataResolveFn)($id));
        $pageMetadata = new \OCA\IntraVox\Service\Metadata\PageMetadataService(
            new PageIdUtils(),
            $this->createMock(PageVersionService::class),
            $this->createMock(\OCA\IntraVox\Service\PageIndexService::class),
            $this->doubleOrBuild(\OCA\IntraVox\Service\NavigationService::class),
            $this->doubleOrBuild(\OCA\IntraVox\Service\Sanitize\PageShapeSanitizer::class),
            // languageFolder()/intraVox() must resolve (not throw) so getPageMetadata
            // reaches the mocked locator; the resolved page comes from that locator,
            // not from walking these folders.
            $this->fakeFolderContext(
                languageFolder: $this->makeFolder('/IntraVox/en'),
                intraVox: $this->makeFolder('/IntraVox')
            ),
            $this->doubleOrBuild(\OCA\IntraVox\Service\Path\PageDataEnricher::class),
            $this->createMock(LoggerInterface::class),
            $this->fakeHomepageResolver(null),
            $this->fakeCacheInvalidator(),
            $metadataLocator,
        );

        $this->controller = new PageContentApiController(
            'intravox',
            $this->createMock(IRequest::class),
            $pageRead,
            $versionDomain,
            $cacheStatus,
            $this->appManager,
            $this->createMock(LoggerInterface::class),
            $pageMetadata,
        );
    }

    private function pageReadable(bool $canRead): array {
        return ['uniqueId' => 'page-x', 'permissions' => ['canRead' => $canRead]];
    }

    // --- getCurrentPageContent ---

    public function testGetCurrentContentHappyPathDelegates(): void {
        $this->getPageFn = fn(string $id) => $this->pageReadable(true);
        // getCurrentPageContent resolves the page via the locator, then returns
        // {title, content, rawContent} from the file. Wire the locator to resolve
        // page-x to a file with known content.
        $file = $this->createMock(\OCP\Files\File::class);
        $file->method('getContent')->willReturn('{"blocks":[1,2]}');
        $this->versionLocator->method('locatePageAnyLanguage')
            ->willReturn(['file' => $file, 'page' => ['name' => 'X Page']]);

        $res = $this->controller->getCurrentPageContent('page-x');

        $this->assertSame(Http::STATUS_OK, $res->getStatus());
        $this->assertSame([
            'title' => 'X Page',
            'content' => '{"blocks":[1,2]}',
            'rawContent' => '{"blocks":[1,2]}',
        ], $res->getData());
    }

    public function testGetCurrentContentDeniedReturns403AccessDenied(): void {
        $this->getPageFn = fn(string $id) => $this->pageReadable(false);

        $res = $this->controller->getCurrentPageContent('page-x');

        $this->assertSame(Http::STATUS_FORBIDDEN, $res->getStatus());
        $this->assertSame(['error' => 'Access denied'], $res->getData());
    }

    public function testGetCurrentContentExceptionReturns500WithMessage(): void {
        // known leak: the raw message is returned (see class docblock).
        $this->getPageFn = function (string $id) { throw new \RuntimeException('boom'); };

        $res = $this->controller->getCurrentPageContent('page-x');

        $this->assertSame(Http::STATUS_INTERNAL_SERVER_ERROR, $res->getStatus());
        $this->assertSame(['error' => 'boom'], $res->getData());
    }

    /**
     * IV-11: a missing page must be a 404 with a fixed body, not a broad-catch
     * 500 that echoes the raw exception message (which enabled enumeration and
     * leaked internal architecture).
     */
    public function testGetCurrentContentMissingPageReturns404(): void {
        $this->getPageFn = function (string $id) {
            throw new \OCA\IntraVox\Exception\PageNotFoundException('Page not found: ' . $id);
        };

        $res = $this->controller->getCurrentPageContent('page-x');

        $this->assertSame(Http::STATUS_NOT_FOUND, $res->getStatus());
        $this->assertSame(['error' => 'Page not found'], $res->getData());
    }

    // --- getPageMetadata (same gate) ---

    public function testGetMetadataDeniedReturns403AccessDenied(): void {
        $this->getPageFn = fn(string $id) => $this->pageReadable(false);

        $res = $this->controller->getPageMetadata('page-x');

        $this->assertSame(Http::STATUS_FORBIDDEN, $res->getStatus());
        $this->assertSame(['error' => 'Access denied'], $res->getData());
    }

    public function testGetMetadataHappyPathDelegates(): void {
        $this->getPageFn = fn(string $id) => $this->pageReadable(true);
        // The real PageMetadataService resolves page-x to a file carrying
        // {"title":"X"} and returns a metadata dict whose 'title' is that value;
        // the controller passes the dict through as a 200. (The full dict has more
        // fields — timestamps, path, permissions — so pin the delegated title.)
        $file = $this->makeFile('/IntraVox/en/page-x/page-x.json', ['title' => 'X', 'uniqueId' => 'page-x']);
        $folder = $this->makeFolder('/IntraVox/en/page-x');
        $this->metadataResolveFn = fn(string $id) => $id === 'page-x'
            ? ['file' => $file, 'folder' => $folder]
            : null;

        $res = $this->controller->getPageMetadata('page-x');

        $this->assertSame(Http::STATUS_OK, $res->getStatus());
        $this->assertSame('X', $res->getData()['title']);
    }

    // --- getPageVersions (same gate) ---

    public function testGetVersionsDeniedReturns403(): void {
        $this->getPageFn = fn(string $id) => $this->pageReadable(false);

        $res = $this->controller->getPageVersions('page-x');

        $this->assertSame(Http::STATUS_FORBIDDEN, $res->getStatus());
        $this->assertSame(['error' => 'Access denied'], $res->getData());
    }

    // --- getVersionContent (same gate) ---

    public function testGetVersionContentDeniedReturns403(): void {
        $this->getPageFn = fn(string $id) => $this->pageReadable(false);

        $res = $this->controller->getVersionContent('page-x', '12345');

        $this->assertSame(Http::STATUS_FORBIDDEN, $res->getStatus());
        $this->assertSame(['error' => 'Access denied'], $res->getData());
    }

    // --- MetaVox availability endpoints ---

    public function testMetavoxStatusReportsInstalledAndEnabled(): void {
        $this->appManager->method('isInstalled')->willReturn(true);
        $this->appManager->method('isEnabledForUser')->willReturn(true);

        $res = $this->controller->getMetavoxStatus();

        $this->assertSame(['installed' => true, 'enabled' => true], $res->getData());
    }

    public function testMetavoxFieldsUnavailableReturnsEmptyWithError(): void {
        $this->appManager->method('isInstalled')->willReturn(false);
        $this->appManager->method('isEnabledForUser')->willReturn(false);

        $res = $this->controller->getMetavoxFields();

        $this->assertSame(['fields' => [], 'error' => 'MetaVox not available'], $res->getData());
    }
}
