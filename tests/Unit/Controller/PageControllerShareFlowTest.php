<?php
declare(strict_types=1);

namespace OCA\IntraVox\Tests\Unit\Controller;

use OCA\IntraVox\Controller\PageController;
use OCA\IntraVox\Service\PageService;
use OCA\IntraVox\Service\PublicShareService;
use OCP\App\IAppManager;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\RedirectResponse;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\AppFramework\Services\IInitialState;
use OCP\IConfig;
use OCP\IRequest;
use OCP\ISession;
use OCP\IURLGenerator;
use OCP\IUserSession;
use OCP\Security\Bruteforce\IThrottler;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Real unit tests for PageController's anonymous share flow — the most
 * security-sensitive code in the controller. Phase 6 moves the token validator
 * and session-key literal into PublicShareService and adds a RendersAppShell
 * trait, all verbatim; these pins make any behavioural drift in the guard order,
 * brute-force registration, session handling or redirect a loud failure.
 *
 * A valid NC share token is 10-32 alphanumeric chars; 'validtoken123' is used
 * throughout.
 */
class PageControllerShareFlowTest extends TestCase {

    private const TOKEN = 'validtoken123';

    private PublicShareService $shareService;
    private IConfig $config;
    private IUserSession $userSession;
    private IThrottler $throttler;
    private ISession $session;
    private IURLGenerator $urlGenerator;
    private IRequest $request;

    private function makeController(): PageController {
        $this->shareService = $this->createMock(PublicShareService::class);
        // Token-shape validation moved to PublicShareService (Phase 6.2); reproduce
        // the real rule so a valid token passes and 'bad' is rejected as before.
        $this->shareService->method('isValidShareTokenFormat')->willReturnCallback(
            fn(?string $t) => $t !== null && $t !== '' && strlen($t) >= 10 && strlen($t) <= 32 && ctype_alnum($t)
        );
        $this->config = $this->createMock(IConfig::class);
        // Default: NC link sharing enabled.
        $this->config->method('getAppValue')->willReturnCallback(
            fn($app, $key, $default = '') => $key === 'shareapi_allow_links' ? 'yes' : $default
        );
        $this->userSession = $this->createMock(IUserSession::class);
        $this->userSession->method('getUser')->willReturn(null); // anonymous visitor
        $this->throttler = $this->createMock(IThrottler::class);
        $this->session = $this->createMock(ISession::class);
        $this->urlGenerator = $this->createMock(IURLGenerator::class);
        $this->urlGenerator->method('linkToRoute')->willReturnCallback(
            fn($route, $params = []) => '/index.php/apps/intravox/s/' . ($params['shareToken'] ?? '')
        );
        $this->request = $this->createMock(IRequest::class);
        $this->request->method('getRemoteAddress')->willReturn('10.0.0.1');

        return new PageController(
            'intravox',
            $this->request,
            $this->createMock(PageService::class),
            $this->shareService,
            $this->createMock(LoggerInterface::class),
            $this->config,
            $this->userSession,
            $this->throttler,
            $this->session,
            $this->urlGenerator,
            $this->createMock(IInitialState::class),
            $this->createMock(IAppManager::class),
        );
    }

    // --- shareAccess ---

    public function testInvalidTokenRegistersBruteForceAndReturnsNotFound(): void {
        $ctrl = $this->makeController();
        $this->throttler->expects($this->once())
            ->method('registerAttempt')
            ->with('intravox_share_access', $this->anything());

        $res = $ctrl->shareAccess('bad');

        $this->assertInstanceOf(TemplateResponse::class, $res);
        $this->assertSame('public-not-found', $res->getTemplateName());
        $this->assertSame(Http::STATUS_NOT_FOUND, $res->getStatus());
    }

    public function testLinkSharingDisabledReturnsNotFoundWithoutBruteForce(): void {
        // Build a controller with NC link sharing turned off.
        $this->makeController();
        $config = $this->createMock(IConfig::class);
        $config->method('getAppValue')->willReturnCallback(
            fn($app, $key, $default = '') => $key === 'shareapi_allow_links' ? 'no' : $default
        );
        $ctrl = new PageController(
            'intravox', $this->request, $this->createMock(PageService::class), $this->shareService,
            $this->createMock(LoggerInterface::class), $config, $this->userSession, $this->throttler,
            $this->session, $this->urlGenerator, $this->createMock(IInitialState::class),
            $this->createMock(IAppManager::class),
        );
        // A valid token but sharing disabled: no brute-force attempt, just 404.
        $this->throttler->expects($this->never())->method('registerAttempt');

        $res = $ctrl->shareAccess(self::TOKEN);

        $this->assertSame('public-not-found', $res->getTemplateName());
        $this->assertSame(Http::STATUS_NOT_FOUND, $res->getStatus());
    }

    public function testPasswordRequiredWithoutSessionShowsChallenge(): void {
        $ctrl = $this->makeController();
        $this->shareService->method('shareRequiresPassword')->willReturn(true);
        $this->session->method('get')->willReturn(null); // no stored password

        $res = $ctrl->shareAccess(self::TOKEN);

        $this->assertSame('public-password', $res->getTemplateName());
    }

    public function testStaleSessionPasswordIsRemovedAndChallengeShown(): void {
        $ctrl = $this->makeController();
        $this->shareService->method('shareRequiresPassword')->willReturn(true);
        $this->session->method('get')->willReturn('oldpassword');
        // The stored password no longer validates (share password was changed).
        $this->shareService->method('checkSharePassword')->willReturn(false);
        // The stale key must be removed.
        $this->session->expects($this->once())
            ->method('remove')
            ->with('intravox_share_pw_' . self::TOKEN);

        $res = $ctrl->shareAccess(self::TOKEN);

        $this->assertSame('public-password', $res->getTemplateName());
    }

    public function testValidSessionPasswordRendersTheApp(): void {
        $ctrl = $this->makeController();
        $this->shareService->method('shareRequiresPassword')->willReturn(true);
        $this->session->method('get')->willReturn('goodpassword');
        $this->shareService->method('checkSharePassword')->willReturn(true);

        $res = $ctrl->shareAccess(self::TOKEN);

        $this->assertSame('main', $res->getTemplateName());
        $this->assertTrue($res->getParams()['isPublicShare']);
        $this->assertSame(self::TOKEN, $res->getParams()['shareToken']);
    }

    // --- shareAuthenticate ---

    public function testWrongPasswordRegistersBruteForceAndShowsErrorChallenge(): void {
        $ctrl = $this->makeController();
        $this->request->method('getParam')->willReturnCallback(
            fn($k, $d = '') => $k === 'password' ? 'wrong' : $d
        );
        $this->shareService->method('checkSharePassword')->willReturn(false);
        $this->throttler->expects($this->once())
            ->method('registerAttempt')
            ->with('intravox_share_password', '10.0.0.1');

        $res = $ctrl->shareAuthenticate(self::TOKEN);

        $this->assertInstanceOf(TemplateResponse::class, $res);
        $this->assertSame('public-password', $res->getTemplateName());
        $this->assertTrue($res->getParams()['wrongPassword']);
    }

    public function testCorrectPasswordStoresSessionAndRedirects(): void {
        $ctrl = $this->makeController();
        $this->request->method('getParam')->willReturnCallback(
            fn($k, $d = '') => $k === 'password' ? 'right' : $d
        );
        $this->shareService->method('checkSharePassword')->willReturn(true);
        $this->session->expects($this->once())
            ->method('set')
            ->with('intravox_share_pw_' . self::TOKEN, 'right');

        $res = $ctrl->shareAuthenticate(self::TOKEN);

        $this->assertInstanceOf(RedirectResponse::class, $res);
        $this->assertStringContainsString(self::TOKEN, $res->getRedirectUrl());
    }

    public function testReturnQueryIsPreservedInTheRedirect(): void {
        $ctrl = $this->makeController();
        $this->request->method('getParam')->willReturnCallback(function ($k, $d = '') {
            return match ($k) {
                'password' => 'right',
                'returnQuery' => 'page=xyz',
                default => $d,
            };
        });
        $this->shareService->method('checkSharePassword')->willReturn(true);

        $res = $ctrl->shareAuthenticate(self::TOKEN);

        $this->assertInstanceOf(RedirectResponse::class, $res);
        $this->assertStringContainsString('?page=xyz', $res->getRedirectUrl());
    }

    public function testInvalidTokenOnAuthenticateRegistersBruteForceAndReturnsNotFound(): void {
        $ctrl = $this->makeController();
        $this->throttler->expects($this->once())->method('registerAttempt');

        $res = $ctrl->shareAuthenticate('bad');

        $this->assertInstanceOf(TemplateResponse::class, $res);
        $this->assertSame('public-not-found', $res->getTemplateName());
    }
}
