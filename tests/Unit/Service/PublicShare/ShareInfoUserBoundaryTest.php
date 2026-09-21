<?php
declare(strict_types=1);

namespace OCA\IntraVox\Tests\Unit\Service\PublicShare;

use OCA\IntraVox\Service\PublicShareService;
use OCP\Share\IManager as IShareManager;
use OCP\Share\IShare;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * The per-user boundary of the share-info lookup.
 *
 * getSharesForNode() replaced a hand-written raw-SQL walk (group_folders ->
 * filecache -> share by file_source, three queries per folder level, ~107ms on
 * the VM) with Nextcloud's own share manager (getSharesBy, ~0.7ms, byte-identical
 * answer). That swap also retired the separate ShareInfoCache whose test used to
 * guard the per-user boundary ("bob must not get anne's answer"). The boundary
 * still matters — the answer's filesUrl is resolved through the caller's mount —
 * so it is pinned here, at the method that now owns it:
 *
 *  - the VIEWER's userId is what getSharesBy resolves the node through, so it must
 *    be passed straight down (a share is reported through the caller's own view);
 *  - a null/empty user (an anonymous caller that slipped past the read gate) gets
 *    no shares rather than someone else's.
 */
class ShareInfoUserBoundaryTest extends TestCase {

    private function service(IShareManager $shareManager): PublicShareService {
        $svc = (new \ReflectionClass(PublicShareService::class))->newInstanceWithoutConstructor();
        $set = fn(string $p, $v) => (new \ReflectionProperty(PublicShareService::class, $p))->setValue($svc, $v);
        $set('shareManager', $shareManager);
        $set('logger', $this->createMock(LoggerInterface::class));
        return $svc;
    }

    private function callGetSharesForNode(PublicShareService $svc, $node, ?string $userId): array {
        $m = new \ReflectionMethod(PublicShareService::class, 'getSharesForNode');
        return $m->invoke($svc, $node, $userId);
    }

    public function testTheViewersUserIdIsPassedToTheShareManager(): void {
        $node = $this->createMock(\OCP\Files\Node::class);
        $share = $this->createMock(IShare::class);

        $shareManager = $this->createMock(IShareManager::class);
        // The boundary: getSharesBy MUST be asked for THIS viewer's shares, for a
        // LINK share, on THIS node. If a future refactor drops the user, this fails.
        $shareManager->expects($this->once())
            ->method('getSharesBy')
            ->with('anne', IShare::TYPE_LINK, $node, false, -1)
            ->willReturn([$share]);

        $result = $this->callGetSharesForNode($this->service($shareManager), $node, 'anne');

        $this->assertSame([$share], $result);
    }

    public function testAnonymousCallerGetsNoSharesAndNeverHitsTheShareManager(): void {
        $node = $this->createMock(\OCP\Files\Node::class);
        $shareManager = $this->createMock(IShareManager::class);
        // A null user owns no mount to resolve shares through: return empty, and do
        // not even ask the manager (which would need a user to answer meaningfully).
        $shareManager->expects($this->never())->method('getSharesBy');

        $this->assertSame([], $this->callGetSharesForNode($this->service($shareManager), $node, null));
        $this->assertSame([], $this->callGetSharesForNode($this->service($shareManager), $node, ''));
    }

    public function testAShareManagerFailureDegradesToNoShares(): void {
        $node = $this->createMock(\OCP\Files\Node::class);
        $node->method('getPath')->willReturn('/anne/files/IntraVox/en/about');
        $shareManager = $this->createMock(IShareManager::class);
        $shareManager->method('getSharesBy')->willThrowException(new \RuntimeException('share backend down'));

        // A failure to look up shares must not fail the page view — it degrades to
        // "no share reported", the same fail-safe the raw-SQL version had.
        $this->assertSame([], $this->callGetSharesForNode($this->service($shareManager), $node, 'anne'));
    }
}
