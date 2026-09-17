<?php
declare(strict_types=1);

namespace OCA\IntraVox\Tests\Unit\Controller;

use OCA\IntraVox\Controller\PageLockController;
use OCA\IntraVox\Service\PageLockService;
use OCA\IntraVox\Service\PermissionService;
use OCA\IntraVox\Tests\Mocks\MockUserSession;
use OCA\IntraVox\Tests\Unit\Service\Harness\BuildsPageRead;
use OCP\AppFramework\Http;
use OCP\IRequest;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * IV-07: acquiring/refreshing a page lock is an edit primitive and must require
 * write permission on the page. Otherwise any authenticated user (even one with
 * no IntraVox access) can lock any page and block its editors.
 */
class PageLockPermissionTest extends TestCase {
    use BuildsPageRead;

    private \Closure $getPageFn;
    private PageLockService $lockService;

    private function buildController(): PageLockController {
        $this->getPageFn = $this->getPageFn ?? (fn(string $id) => null);
        $pageRead = $this->fakePageReadFrom(fn(string $id) => ($this->getPageFn)($id));

        $userSession = MockUserSession::loggedInAs('editor', 'Editor');

        return new PageLockController(
            'intravox',
            $this->createMock(IRequest::class),
            $this->lockService,
            $this->createMock(PermissionService::class),
            $pageRead,
            $userSession,
            $this->createMock(LoggerInterface::class),
        );
    }

    public function testAcquireLockDeniedWithoutWritePermission(): void {
        $this->getPageFn = fn(string $id) => ['permissions' => ['canWrite' => false]];
        // The lock must never be taken when the user cannot write.
        $this->lockService = $this->createMock(PageLockService::class);
        $this->lockService->expects($this->never())->method('acquireLock');

        $response = $this->buildController()->acquireLock('page-victim');

        $this->assertEquals(Http::STATUS_FORBIDDEN, $response->getStatus());
    }

    public function testRefreshLockDeniedWithoutWritePermission(): void {
        $this->getPageFn = fn(string $id) => ['permissions' => ['canWrite' => false]];
        $this->lockService = $this->createMock(PageLockService::class);
        $this->lockService->expects($this->never())->method('refreshLock');

        $response = $this->buildController()->refreshLock('page-victim');

        $this->assertEquals(Http::STATUS_FORBIDDEN, $response->getStatus());
    }

    public function testAcquireLockAllowedWithWritePermission(): void {
        $this->getPageFn = fn(string $id) => ['permissions' => ['canWrite' => true]];
        $this->lockService = $this->createMock(PageLockService::class);
        $this->lockService->expects($this->once())
            ->method('acquireLock')
            ->willReturn(['success' => true]);

        $response = $this->buildController()->acquireLock('page-mine');

        $this->assertEquals(Http::STATUS_OK, $response->getStatus());
        $this->assertTrue($response->getData()['success']);
    }

    /**
     * Fail-closed: a page whose permissions could not be determined reads as
     * "no write", never as "unset, so allow".
     */
    public function testAcquireLockDeniedWhenPermissionsMissing(): void {
        $this->getPageFn = fn(string $id) => [];
        $this->lockService = $this->createMock(PageLockService::class);
        $this->lockService->expects($this->never())->method('acquireLock');

        $response = $this->buildController()->acquireLock('page-unknown');

        $this->assertEquals(Http::STATUS_FORBIDDEN, $response->getStatus());
    }

    /**
     * A user with no IntraVox access at all makes getPage() throw (the folder
     * is not mounted for them). That must be a clean 403, not an uncaught 500
     * with a leaky message.
     */
    public function testAcquireLockReturns403WhenPageCannotBeResolved(): void {
        $this->getPageFn = function (string $id) {
            throw new \Exception('IntraVox folder not found. Please check that you have access to the IntraVox GroupFolder.');
        };
        $this->lockService = $this->createMock(PageLockService::class);
        $this->lockService->expects($this->never())->method('acquireLock');

        $response = $this->buildController()->acquireLock('page-x');

        $this->assertEquals(Http::STATUS_FORBIDDEN, $response->getStatus());
        $this->assertSame(['error' => 'Permission denied'], $response->getData());
    }
}
