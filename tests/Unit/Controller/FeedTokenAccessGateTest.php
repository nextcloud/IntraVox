<?php
declare(strict_types=1);

namespace OCA\IntraVox\Tests\Unit\Controller;

use OCA\IntraVox\Controller\FeedController;
use OCA\IntraVox\Service\FeedService;
use OCA\IntraVox\Service\FeedTokenService;
use OCA\IntraVox\Service\PermissionService;
use OCP\AppFramework\Http;
use OCP\IConfig;
use OCP\IRequest;
use OCP\IURLGenerator;
use OCP\IUser;
use OCP\IUserSession;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * IV-12: the personal feed-token endpoints must require IntraVox access, not
 * merely a logged-in session. A user in no IntraVox group (raw:0) should not be
 * able to mint or manage a feed token.
 */
class FeedTokenAccessGateTest extends TestCase {
    private FeedTokenService $tokenService;

    private function makeController(?string $userId, bool $hasAccess): FeedController {
        $this->tokenService = $this->createMock(FeedTokenService::class);

        $userSession = $this->createMock(IUserSession::class);
        if ($userId !== null) {
            $user = $this->createMock(IUser::class);
            $user->method('getUID')->willReturn($userId);
            $userSession->method('getUser')->willReturn($user);
        } else {
            $userSession->method('getUser')->willReturn(null);
        }

        $permissions = $this->createMock(PermissionService::class);
        $permissions->method('hasAccess')->willReturn($hasAccess);

        $config = $this->createMock(IConfig::class);
        // link sharing allowed, so a granted user reaches the token lookup
        $config->method('getAppValue')->willReturn('yes');

        return new FeedController(
            'intravox',
            $this->createMock(IRequest::class),
            $this->tokenService,
            $this->createMock(FeedService::class),
            $this->createMock(IURLGenerator::class),
            $userSession,
            $config,
            $permissions,
            $this->createMock(LoggerInterface::class),
        );
    }

    public function testGetTokenUnauthenticatedReturns401(): void {
        $controller = $this->makeController(null, false);
        $this->tokenService->expects($this->never())->method('getTokenForUser');
        $response = $controller->getToken();
        $this->assertSame(Http::STATUS_UNAUTHORIZED, $response->getStatus());
    }

    public function testGetTokenWithoutIntraVoxAccessReturns403(): void {
        $controller = $this->makeController('outsider', false);
        // The token store must not be touched for a user with no access.
        $this->tokenService->expects($this->never())->method('getTokenForUser');
        $response = $controller->getToken();
        $this->assertSame(Http::STATUS_FORBIDDEN, $response->getStatus());
    }

    public function testRegenerateTokenWithoutIntraVoxAccessReturns403(): void {
        $controller = $this->makeController('outsider', false);
        $this->tokenService->expects($this->never())->method('generateToken');
        $response = $controller->regenerateToken();
        $this->assertSame(Http::STATUS_FORBIDDEN, $response->getStatus());
    }

    public function testGetTokenWithAccessReachesTokenLookup(): void {
        $controller = $this->makeController('member', true);
        $this->tokenService->expects($this->once())
            ->method('getTokenForUser')
            ->with('member')
            ->willReturn(null);

        $response = $controller->getToken();

        $this->assertSame(Http::STATUS_OK, $response->getStatus());
        $this->assertFalse($response->getData()['hasToken']);
    }
}
