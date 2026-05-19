<?php
declare(strict_types=1);

namespace OCA\IntraVox\Tests\Unit\Service;

use OCA\IntraVox\Service\GroupContextService;
use OCP\IGroupManager;
use OCP\IUser;
use OCP\IUserSession;
use PHPUnit\Framework\TestCase;

class GroupContextServiceTest extends TestCase {
    private IUserSession $userSession;
    private IGroupManager $groupManager;

    protected function setUp(): void {
        parent::setUp();
        $this->userSession = $this->createMock(IUserSession::class);
        $this->groupManager = $this->createMock(IGroupManager::class);
    }

    public function testAnonymousUserReturnsSentinelHash(): void {
        $this->userSession->method('getUser')->willReturn(null);

        $service = new GroupContextService($this->userSession, $this->groupManager);

        $this->assertSame('anon', $service->getGroupHash());
    }

    public function testHashIsStableForSameGroupSet(): void {
        $user = $this->makeUser('alice');
        $this->userSession->method('getUser')->willReturn($user);
        $this->groupManager->method('getUserGroupIds')->willReturn(['editors', 'staff']);

        $service = new GroupContextService($this->userSession, $this->groupManager);

        $this->assertSame($service->getGroupHash(), $service->getGroupHash());
    }

    public function testHashIsOrderIndependent(): void {
        $alice = $this->makeUser('alice');
        $bob = $this->makeUser('bob');
        $this->groupManager->method('getUserGroupIds')->willReturnMap([
            [$alice, ['editors', 'staff']],
            [$bob, ['staff', 'editors']],
        ]);

        $service = new GroupContextService($this->userSession, $this->groupManager);
        $aliceHash = $service->getGroupHashForUser($alice);
        $bobHash = $service->getGroupHashForUser($bob);

        $this->assertSame($aliceHash, $bobHash);
    }

    public function testHashDiffersBetweenDifferentGroupSets(): void {
        $alice = $this->makeUser('alice');
        $carol = $this->makeUser('carol');
        $this->groupManager->method('getUserGroupIds')->willReturnMap([
            [$alice, ['editors']],
            [$carol, ['readers']],
        ]);

        $service = new GroupContextService($this->userSession, $this->groupManager);

        $this->assertNotSame(
            $service->getGroupHashForUser($alice),
            $service->getGroupHashForUser($carol)
        );
    }

    public function testHashForGroupIdsMatchesUserHash(): void {
        $alice = $this->makeUser('alice');
        $this->groupManager->method('getUserGroupIds')->willReturn(['editors', 'staff']);

        $service = new GroupContextService($this->userSession, $this->groupManager);

        $this->assertSame(
            $service->getGroupHashForUser($alice),
            $service->hashForGroupIds(['editors', 'staff'])
        );
    }

    public function testMemoizationAvoidsRepeatedGroupManagerCalls(): void {
        $alice = $this->makeUser('alice');
        $this->groupManager->expects($this->once())
            ->method('getUserGroupIds')
            ->willReturn(['editors']);

        $service = new GroupContextService($this->userSession, $this->groupManager);
        $service->getGroupHashForUser($alice);
        $service->getGroupHashForUser($alice);
        $service->getGroupHashForUser($alice);
    }

    public function testResetClearsMemoization(): void {
        $alice = $this->makeUser('alice');
        $this->groupManager->expects($this->exactly(2))
            ->method('getUserGroupIds')
            ->willReturn(['editors']);

        $service = new GroupContextService($this->userSession, $this->groupManager);
        $service->getGroupHashForUser($alice);
        $service->reset();
        $service->getGroupHashForUser($alice);
    }

    private function makeUser(string $uid): IUser {
        $user = $this->createMock(IUser::class);
        $user->method('getUID')->willReturn($uid);
        return $user;
    }
}
