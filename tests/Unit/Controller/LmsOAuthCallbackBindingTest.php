<?php
declare(strict_types=1);

namespace OCA\IntraVox\Tests\Unit\Controller;

use OCA\IntraVox\Controller\LmsOAuthController;
use OCA\IntraVox\Service\FeedReaderService;
use OCA\IntraVox\Service\LmsOAuthService;
use OCA\IntraVox\Service\LmsTokenService;
use OCP\Http\Client\IClientService;
use OCP\IConfig;
use OCP\IRequest;
use OCP\IURLGenerator;
use OCP\Security\ICrypto;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * IV-05: the OAuth callback must bind the saved token to the SESSION user, not
 * to the user embedded in the (HMAC-signed) state. Otherwise an attacker starts
 * a flow and has a victim finish it, linking the victim's LMS token to the
 * attacker's account.
 */
class LmsOAuthCallbackBindingTest extends TestCase {
    private function buildController(LmsOAuthService $oauth, LmsTokenService $tokens, ?string $sessionUser): LmsOAuthController {
        $request = $this->createMock(IRequest::class);
        $request->method('getParam')->willReturnMap([
            ['code', '', 'the-code'],
            ['state', '', 'the-state'],
            ['error', '', ''],
            ['error_description', '', ''],
        ]);

        return new LmsOAuthController(
            'intravox',
            $request,
            $oauth,
            $tokens,
            $this->createMock(FeedReaderService::class),
            $this->createMock(IClientService::class),
            $this->createMock(IConfig::class),
            $this->createMock(ICrypto::class),
            $this->createMock(IURLGenerator::class),
            $this->createMock(LoggerInterface::class),
            $sessionUser,
        );
    }

    public function testCallbackRejectsWhenStateUserDiffersFromSessionUser(): void {
        $oauth = $this->createMock(LmsOAuthService::class);
        $oauth->method('handleCallback')->willReturn([
            'access_token' => 'tok',
            'refresh_token' => null,
            'expires_in' => 3600,
            'connectionId' => 'conn1',
            'userId' => 'attacker', // state was issued for the attacker
        ]);

        $tokens = $this->createMock(LmsTokenService::class);
        // The whole point: the victim's token must NOT be saved.
        $tokens->expects($this->never())->method('saveUserToken');

        // Session belongs to the VICTIM finishing the attacker's flow.
        $controller = $this->buildController($oauth, $tokens, 'victim');
        $response = $controller->callback();

        $body = $response->render();
        $this->assertStringContainsString('"success":false', $body);
        $this->assertStringNotContainsString('"success":true', $body);
    }

    public function testCallbackSavesWhenStateUserMatchesSessionUser(): void {
        $oauth = $this->createMock(LmsOAuthService::class);
        $oauth->method('handleCallback')->willReturn([
            'access_token' => 'tok',
            'refresh_token' => 'refresh',
            'expires_in' => 3600,
            'connectionId' => 'conn1',
            'userId' => 'alice',
        ]);

        $tokens = $this->createMock(LmsTokenService::class);
        $tokens->expects($this->once())
            ->method('saveUserToken')
            ->with('alice', 'conn1', 'tok', 'refresh', 'oauth2', $this->anything());

        $controller = $this->buildController($oauth, $tokens, 'alice');
        $controller->callback();
    }

    public function testCallbackRejectsWhenNoSessionUser(): void {
        $oauth = $this->createMock(LmsOAuthService::class);
        $oauth->method('handleCallback')->willReturn([
            'access_token' => 'tok',
            'refresh_token' => null,
            'expires_in' => 3600,
            'connectionId' => 'conn1',
            'userId' => 'alice',
        ]);

        $tokens = $this->createMock(LmsTokenService::class);
        $tokens->expects($this->never())->method('saveUserToken');

        $controller = $this->buildController($oauth, $tokens, null);
        $response = $controller->callback();

        $this->assertStringContainsString('"success":false', $response->render());
    }
}
