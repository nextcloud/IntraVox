<?php
declare(strict_types=1);

namespace OCA\IntraVox\Tests\Unit\Listener;

use OCA\IntraVox\Listener\GroupMembershipChangedListener;
use OCP\EventDispatcher\Event;
use OCP\Group\Events\UserAddedEvent;
use OCP\Group\Events\UserRemovedEvent;
use OCP\ICache;
use OCP\ICacheFactory;
use OCP\IGroup;
use OCP\IUser;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class GroupMembershipChangedListenerTest extends TestCase {
    private ICache $pageCache;
    private ICache $permissionCache;
    private ICacheFactory $factory;
    private LoggerInterface $logger;

    protected function setUp(): void {
        parent::setUp();
        $this->pageCache = $this->createMock(ICache::class);
        $this->permissionCache = $this->createMock(ICache::class);
        $this->factory = $this->createMock(ICacheFactory::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->factory->method('isAvailable')->willReturn(true);
        $this->factory->method('createDistributed')->willReturnMap([
            ['intravox-pages', $this->pageCache],
            ['intravox-permissions', $this->permissionCache],
        ]);
    }

    public function testUserAddedEventClearsBothCaches(): void {
        $this->pageCache->expects($this->once())->method('clear');
        $this->permissionCache->expects($this->once())->method('clear');

        $listener = new GroupMembershipChangedListener($this->factory, $this->logger);
        $listener->handle($this->makeEvent(UserAddedEvent::class));
    }

    public function testUserRemovedEventClearsBothCaches(): void {
        $this->pageCache->expects($this->once())->method('clear');
        $this->permissionCache->expects($this->once())->method('clear');

        $listener = new GroupMembershipChangedListener($this->factory, $this->logger);
        $listener->handle($this->makeEvent(UserRemovedEvent::class));
    }

    public function testUnrelatedEventDoesNotClearCache(): void {
        $this->pageCache->expects($this->never())->method('clear');
        $this->permissionCache->expects($this->never())->method('clear');

        $unrelated = new class extends Event {};
        $listener = new GroupMembershipChangedListener($this->factory, $this->logger);
        $listener->handle($unrelated);
    }

    public function testListenerSilentlyTolleratesCacheUnavailable(): void {
        $factory = $this->createMock(ICacheFactory::class);
        $factory->method('isAvailable')->willReturn(false);

        $listener = new GroupMembershipChangedListener($factory, $this->logger);

        $this->expectNotToPerformAssertions();
        $listener->handle($this->makeEvent(UserAddedEvent::class));
    }

    public function testCacheExceptionsAreSwallowedAndLogged(): void {
        $this->pageCache->method('clear')->willThrowException(new \RuntimeException('redis down'));
        $this->logger->expects($this->once())->method('warning');

        $listener = new GroupMembershipChangedListener($this->factory, $this->logger);
        $listener->handle($this->makeEvent(UserAddedEvent::class));
    }

    private function makeEvent(string $class): Event {
        $group = $this->createMock(IGroup::class);
        $group->method('getGID')->willReturn('editors');
        $user = $this->createMock(IUser::class);
        $user->method('getUID')->willReturn('alice');
        return new $class($group, $user);
    }
}
