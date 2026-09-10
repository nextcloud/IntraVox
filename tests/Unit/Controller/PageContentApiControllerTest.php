<?php
declare(strict_types=1);

namespace OCA\IntraVox\Tests\Unit\Controller;

use OCA\IntraVox\Controller\PageContentApiController;
use OCA\IntraVox\Service\Folder\FolderContext;
use OCA\IntraVox\Service\Language\LanguageResolver;
use OCA\IntraVox\Service\Locator\PageLocator;
use OCA\IntraVox\Service\PageService;
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
 *   - happy path delegates to PageService and returns its result;
 *   - a page whose permissions.canRead is false -> 403 ['error' => 'Access denied'];
 *   - an exception -> 500 ['error' => <message>].
 *
 * The 500 body currently leaks the exception message; that is a KNOWN issue the
 * plan defers to a separate ApiErrorTrait-adoption PR (behaviour change). It is
 * pinned here so Phase 2 does not alter it by accident — see 'known leak' notes.
 */
class PageContentApiControllerTest extends TestCase {

    private PageService $pageService;
    private IAppManager $appManager;
    private PageContentApiController $controller;
    /** The mocked version-manager engine behind the real (final) versionDomain. */
    private PageVersionService $versionEngine;
    /** The mocked locator the real versionDomain resolves pages through. */
    private PageLocator $versionLocator;

    protected function setUp(): void {
        $this->pageService = $this->createMock(PageService::class);
        $this->appManager = $this->createMock(IAppManager::class);

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

        $this->controller = new PageContentApiController(
            'intravox',
            $this->createMock(IRequest::class),
            $this->pageService,
            $versionDomain,
            $this->appManager,
            $this->createMock(LoggerInterface::class),
        );
    }

    private function pageReadable(bool $canRead): array {
        return ['uniqueId' => 'page-x', 'permissions' => ['canRead' => $canRead]];
    }

    // --- getCurrentPageContent ---

    public function testGetCurrentContentHappyPathDelegates(): void {
        $this->pageService->method('getPage')->willReturn($this->pageReadable(true));
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
        $this->pageService->method('getPage')->willReturn($this->pageReadable(false));

        $res = $this->controller->getCurrentPageContent('page-x');

        $this->assertSame(Http::STATUS_FORBIDDEN, $res->getStatus());
        $this->assertSame(['error' => 'Access denied'], $res->getData());
    }

    public function testGetCurrentContentExceptionReturns500WithMessage(): void {
        // known leak: the raw message is returned (see class docblock).
        $this->pageService->method('getPage')->willThrowException(new \RuntimeException('boom'));

        $res = $this->controller->getCurrentPageContent('page-x');

        $this->assertSame(Http::STATUS_INTERNAL_SERVER_ERROR, $res->getStatus());
        $this->assertSame(['error' => 'boom'], $res->getData());
    }

    // --- getPageMetadata (same gate) ---

    public function testGetMetadataDeniedReturns403AccessDenied(): void {
        $this->pageService->method('getPage')->willReturn($this->pageReadable(false));

        $res = $this->controller->getPageMetadata('page-x');

        $this->assertSame(Http::STATUS_FORBIDDEN, $res->getStatus());
        $this->assertSame(['error' => 'Access denied'], $res->getData());
    }

    public function testGetMetadataHappyPathDelegates(): void {
        $this->pageService->method('getPage')->willReturn($this->pageReadable(true));
        $this->pageService->method('getPageMetadata')->willReturn(['title' => 'X']);

        $res = $this->controller->getPageMetadata('page-x');

        $this->assertSame(Http::STATUS_OK, $res->getStatus());
        $this->assertSame(['title' => 'X'], $res->getData());
    }

    // --- getPageVersions (same gate) ---

    public function testGetVersionsDeniedReturns403(): void {
        $this->pageService->method('getPage')->willReturn($this->pageReadable(false));

        $res = $this->controller->getPageVersions('page-x');

        $this->assertSame(Http::STATUS_FORBIDDEN, $res->getStatus());
        $this->assertSame(['error' => 'Access denied'], $res->getData());
    }

    // --- getVersionContent (same gate) ---

    public function testGetVersionContentDeniedReturns403(): void {
        $this->pageService->method('getPage')->willReturn($this->pageReadable(false));

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
