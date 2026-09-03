<?php
declare(strict_types=1);

namespace OCA\IntraVox\Tests\Unit\Controller;

use OCA\IntraVox\Controller\PageContentApiController;
use OCA\IntraVox\Service\PageService;
use OCP\App\IAppManager;
use OCP\AppFramework\Http;
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

    protected function setUp(): void {
        $this->pageService = $this->createMock(PageService::class);
        $this->appManager = $this->createMock(IAppManager::class);
        $this->controller = new PageContentApiController(
            'intravox',
            $this->createMock(IRequest::class),
            $this->pageService,
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
        $this->pageService->method('getCurrentPageContent')->willReturn(['blocks' => [1, 2]]);

        $res = $this->controller->getCurrentPageContent('page-x');

        $this->assertSame(Http::STATUS_OK, $res->getStatus());
        $this->assertSame(['blocks' => [1, 2]], $res->getData());
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
