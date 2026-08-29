<?php
declare(strict_types=1);

namespace OCA\IntraVox\Tests\Unit\Service\Feed;

use OCA\IntraVox\Service\Feed\FeedTokenResolver;
use OCA\IntraVox\Service\LmsOAuthService;
use OCA\IntraVox\Service\LmsTokenService;
use OCP\Http\Client\IClientService;
use OCP\Security\ICrypto;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Which credential a feed request runs under.
 *
 * The precedence order is a security contract, and the case that matters most
 * is the one an accident would break quietly: a PUBLIC share must never borrow
 * the admin's token. That is a null-check in the middle of a method with four
 * branches, which is exactly the kind of thing a later refactor "tidies away".
 *
 * These are the first assertions on this logic — it used to be private inside
 * FeedReaderService and reachable only through a live LMS call.
 */
class FeedTokenResolverTest extends TestCase {
    private LmsTokenService $lmsTokens;
    private ICrypto $crypto;

    protected function setUp(): void {
        parent::setUp();
        $this->lmsTokens = $this->createMock(LmsTokenService::class);
        $this->crypto = $this->createMock(ICrypto::class);
    }

    /** @param array<string,mixed>|null $connection */
    private function resolver(?array $connection): FeedTokenResolver {
        return new FeedTokenResolver(
            $this->lmsTokens,
            $this->createMock(LmsOAuthService::class),
            $this->crypto,
            $this->createMock(IClientService::class),
            $this->createMock(LoggerInterface::class),
            static fn (string $id): ?array => $connection,
        );
    }

    public function testAnUnknownConnectionResolvesToNothing(): void {
        $this->assertNull($this->resolver(null)->resolveToken('weg', 'alice'));
    }

    /**
     * THE regression this class exists to protect.
     *
     * /api/share/{token}/feed/external is #[PublicPage]: an anonymous visitor
     * reaches the feed reader with $userId === null. In "token" mode the only
     * credential available is the admin's, and handing that to the open
     * internet would let anyone read the admin's LMS through the share.
     */
    public function testAnAnonymousCallerNeverGetsTheAdminToken(): void {
        $this->crypto->expects($this->never())->method('decrypt');

        $resolved = $this->resolver([
            'id' => 'moodle1',
            'authMode' => 'token',
            'token' => 'versleuteld',
        ])->resolveToken('moodle1', null);

        $this->assertNull($resolved, 'a public share must not borrow the admin credential');
    }

    public function testASignedInCallerDoesGetTheAdminTokenInTokenMode(): void {
        $this->crypto->method('decrypt')->willReturn('geheim');

        $resolved = $this->resolver([
            'id' => 'moodle1',
            'authMode' => 'token',
            'token' => 'versleuteld',
        ])->resolveToken('moodle1', 'alice');

        $this->assertSame(['token' => 'geheim', 'source' => 'admin'], $resolved);
    }

    /** "both" mode falls back to the admin token, but still never anonymously. */
    public function testBothModeFallsBackToAdminOnlyForASignedInUser(): void {
        $this->crypto->method('decrypt')->willReturn('geheim');
        $this->lmsTokens->method('getUserToken')->willReturn(null);

        $connection = ['id' => 'canvas1', 'authMode' => 'both', 'token' => 'versleuteld'];

        $this->assertSame(
            ['token' => 'geheim', 'source' => 'admin'],
            $this->resolver($connection)->resolveToken('canvas1', 'alice')
        );
        $this->assertNull(
            $this->resolver($connection)->resolveToken('canvas1', null),
            'anonymous must not reach the admin fallback either'
        );
    }

    /** In "user" mode there is no admin fallback at all. */
    public function testUserModeWithoutAStoredTokenResolvesToNothing(): void {
        $this->crypto->expects($this->never())->method('decrypt');
        $this->lmsTokens->method('getUserToken')->willReturn(null);

        $this->assertNull(
            $this->resolver(['id' => 'c1', 'authMode' => 'user', 'token' => 'versleuteld'])
                ->resolveToken('c1', 'alice')
        );
    }

    public function testAStoredUserTokenIsPreferredOverTheAdminToken(): void {
        $this->crypto->expects($this->never())->method('decrypt');
        $this->lmsTokens->method('getUserToken')->willReturn([
            'access_token' => 'van-alice',
            'token_type' => 'manual',
        ]);

        $this->assertSame(
            ['token' => 'van-alice', 'source' => 'manual'],
            $this->resolver(['id' => 'c1', 'authMode' => 'both', 'token' => 'admin'])
                ->resolveToken('c1', 'alice')
        );
    }

    /**
     * An OAuth2 token that expired and cannot be refreshed is DELETED, not
     * retried. Otherwise every page load hits the LMS with a dead credential
     * and the user is never prompted to reconnect.
     */
    public function testAnUnrefreshableExpiredTokenIsDeleted(): void {
        $this->lmsTokens->method('getUserToken')->willReturn([
            'access_token' => 'verlopen',
            'token_type' => 'oauth2',
        ]);
        $this->lmsTokens->method('isTokenExpired')->willReturn(true);
        $this->lmsTokens->expects($this->once())
            ->method('deleteUserToken')
            ->with('alice', 'c1');

        $this->resolver(['id' => 'c1', 'authMode' => 'user'])->resolveToken('c1', 'alice');
    }

    /** A connection with no token at all must not blow up on decrypt. */
    public function testAnEmptyAdminTokenIsSimplyNoToken(): void {
        $this->crypto->expects($this->never())->method('decrypt');

        $this->assertNull(
            $this->resolver(['id' => 'c1', 'authMode' => 'token', 'token' => ''])
                ->resolveToken('c1', 'alice')
        );
    }

    /** Undecryptable is refused, not passed through as ciphertext. */
    public function testAnUndecryptableTokenResolvesToNothing(): void {
        $this->crypto->method('decrypt')->willThrowException(new \Exception('kapot'));

        $this->assertNull(
            $this->resolver(['id' => 'c1', 'authMode' => 'token', 'token' => 'rommel'])
                ->resolveToken('c1', 'alice')
        );
    }
}
