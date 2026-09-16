<?php
declare(strict_types=1);

namespace OCA\IntraVox\Tests\Unit\Controller;

use OCA\IntraVox\Controller\FeedReaderController;
use OCA\IntraVox\Service\FeedReaderService;
use OCP\AppFramework\Http;
use OCP\IGroupManager;
use OCP\IRequest;
use OCP\IUser;
use OCP\IUserSession;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Characterizes the admin gate on FeedReaderController::setConnections() before
 * Phase 2 migrates it onto ChecksAdminAccess::isAdmin(). FeedReaderController is
 * the straggler that still calls groupManager->isAdmin($this->userId) directly;
 * the migration is only safe if the observable contract is unchanged, so this
 * pins it: null user -> 401, non-admin -> 403 'Admin access required',
 * admin -> delegates to the service.
 */
class FeedReaderAdminGateTest extends TestCase {

    private IGroupManager $groupManager;
    private IRequest $request;
    private IUserSession $userSession;

    /**
     * Build the controller. The IUserSession is passed so this test survives the
     * Phase-2 migration that adds it to the constructor; the current constructor
     * signature is matched via a variadic tail so both shapes work.
     */
    private function makeController(?string $userId, bool $isAdmin, ?FeedReaderService $service = null): FeedReaderController {
        $service = $service ?? $this->createMock(FeedReaderService::class);
        $this->groupManager = $this->createMock(IGroupManager::class);
        $this->groupManager->method('isAdmin')->willReturn($isAdmin);
        $this->request = $this->createMock(IRequest::class);
        $this->request->method('getParam')->willReturn([]);
        $this->userSession = $this->createMock(IUserSession::class);
        if ($userId !== null) {
            $user = $this->createMock(IUser::class);
            $user->method('getUID')->willReturn($userId);
            $this->userSession->method('getUser')->willReturn($user);
        } else {
            $this->userSession->method('getUser')->willReturn(null);
        }

        return $this->construct($service, $userId);
    }

    /**
     * Reflection-based constructor call that tolerates the IUserSession parameter
     * being present or absent, so the same test passes before AND after Phase 2.
     */
    private function construct(FeedReaderService $service, ?string $userId): FeedReaderController {
        $ref = new \ReflectionClass(FeedReaderController::class);
        $params = $ref->getConstructor()->getParameters();
        $args = [];
        foreach ($params as $p) {
            $type = $p->getType();
            $name = $type instanceof \ReflectionNamedType ? $type->getName() : '';
            $args[] = match (true) {
                $p->getName() === 'appName' => 'intravox',
                $name === IRequest::class => $this->request,
                $name === FeedReaderService::class => $service,
                $name === IGroupManager::class => $this->groupManager,
                $name === LoggerInterface::class => $this->createMock(LoggerInterface::class),
                $name === IUserSession::class => $this->userSession,
                $p->getName() === 'userId' => $userId,
                default => $p->isOptional() ? $p->getDefaultValue() : null,
            };
        }
        return $ref->newInstanceArgs($args);
    }

    public function testAnonymousCallerGets401(): void {
        $res = $this->makeController(userId: null, isAdmin: false)->setConnections();

        $this->assertSame(Http::STATUS_UNAUTHORIZED, $res->getStatus());
        $this->assertSame(['error' => 'Authentication required'], $res->getData());
    }

    public function testNonAdminGets403AdminAccessRequired(): void {
        $res = $this->makeController(userId: 'bob', isAdmin: false)->setConnections();

        $this->assertSame(Http::STATUS_FORBIDDEN, $res->getStatus());
        $this->assertSame(['error' => 'Admin access required'], $res->getData());
    }

    public function testAdminReachesTheServiceAndGetsOk(): void {
        $service = $this->createMock(FeedReaderService::class);
        $service->expects($this->once())->method('saveConnections')->with([]);

        $res = $this->makeController(userId: 'admin', isAdmin: true, service: $service)->setConnections();

        $this->assertSame(Http::STATUS_OK, $res->getStatus());
        $this->assertSame(['status' => 'ok'], $res->getData());
    }
}
