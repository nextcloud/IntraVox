<?php
declare(strict_types=1);

namespace OCA\IntraVox\Tests\Unit\Controller;

use OCA\IntraVox\Controller\ProvidesWorkspaceIdentity;
use OCP\AppFramework\Services\IInitialState;
use OCP\IGroupManager;
use OCP\IUser;
use OCP\IUserSession;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class WorkspaceIdentityTest extends TestCase {
    private function emit(?string $uid, array $groups = [], bool $failure = false): mixed {
        $session = $this->createMock(IUserSession::class);
        $user = $uid === null ? null : $this->createMock(IUser::class);
        $user?->method('getUID')->willReturn($uid);
        $session->method('getUser')->willReturn($user);
        $manager = $this->createMock(IGroupManager::class);
        if ($failure) {
            $manager->method('getUserGroupIds')->willThrowException(new \RuntimeException('unavailable'));
        } else {
            $manager->method('getUserGroupIds')->willReturn($groups);
        }
        $state = $this->createMock(IInitialState::class);
        $identity = 'not called';
        $state->expects($this->once())->method('provideInitialState')->with(
            'aglIdentity', $this->callback(function ($value) use (&$identity): bool {
                $identity = $value;
                return true;
            })
        );
        $shell = new class($session, $manager, $state, $this->createMock(LoggerInterface::class)) {
            use ProvidesWorkspaceIdentity;
            public function __construct(
                private IUserSession $userSession,
                private IGroupManager $groupManager,
                private IInitialState $initialState,
                private LoggerInterface $logger
            ) {}
            public function emit(): void { $this->provideWorkspaceIdentity(); }
        };
        $shell->emit();
        return $identity;
    }

    public function testAuthenticatedGroupsComeFromNextcloud(): void {
        $this->assertSame(['uid' => 'member', 'groups' => ['ExCom', 'staff']], $this->emit('member', ['ExCom', 'staff']));
    }

    public function testAnonymousHasNoIdentity(): void {
        $this->assertSame([], $this->emit(null));
    }

    public function testIdentityFailureFailsClosed(): void {
        $this->assertSame([], $this->emit('member', [], true));
    }
}
