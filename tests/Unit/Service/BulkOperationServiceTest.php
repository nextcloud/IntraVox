<?php
declare(strict_types=1);

namespace OCA\IntraVox\Tests\Unit\Service;

use OCA\IntraVox\Service\BulkOperationService;
use OCA\IntraVox\Service\Cache\PageCacheInvalidator;
use OCA\IntraVox\Service\Cache\PageCacheService;
use OCA\IntraVox\Service\Locator\PageLocator;
use OCA\IntraVox\Service\PermissionService;
use OCA\IntraVox\Service\Structure\PageStructureService;
use OCA\IntraVox\Service\Write\PageWriteService;
use OCA\IntraVox\Tests\Unit\Service\Harness\BuildsCollaboratorFixtures;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Unit tests for BulkOperationService's deferred-cache-clear orchestration.
 *
 * The service must wrap its per-item loop in the deferred-clear begin/end pair so
 * the expensive distributed cache clear happens once per batch instead of once per
 * item, and must release the deferral even if an item throws (and NOT start one on
 * an early return). Since the god-class dissolution (fase-9), that pair lives on
 * PageCacheInvalidator (beginDeferred/endDeferred) and the mutating ops go straight
 * to the final Write/Structure sub-services.
 *
 * The three sub-services are final, so this drives the deferred seam through a REAL
 * PageCacheInvalidator over a SPY (non-final) PageCacheService — beginDeferred()
 * increments a suppress depth and endDeferred() decrements it, so the spy's own
 * counters ARE the observable seam. The per-item mutations run through inert real
 * sub-services (doubleOrBuild): whether an item succeeds or fails is decided by the
 * PageReadService permission gate the test controls, upstream of the mutation, so
 * the mutation call itself never needs to be counted.
 */
class BulkOperationServiceTest extends TestCase {

    use \OCA\IntraVox\Tests\Unit\Service\Harness\BuildsPageRead;
    use BuildsCollaboratorFixtures;
    /** beginDeferred/endDeferred calls the spy cache recorded, in order. */
    private array $deferredCalls = [];

    protected function setUp(): void {
        $this->deferredCalls = [];
    }

    private function pageWith(array $perms): array {
        return ['id' => 'page-x', 'title' => 'X', 'permissions' => $perms];
    }

    /**
     * A real PageCacheInvalidator over a spy PageCacheService, so beginDeferred()/
     * endDeferred() are observable (they forward to the spy). endDeferred() returns
     * false here (no clear was requested while suppressed), so the collaborator
     * flush is skipped — irrelevant to the seam assertions.
     */
    private function spyInvalidator(): PageCacheInvalidator {
        $cache = $this->createMock(PageCacheService::class);
        $cache->method('beginDeferred')->willReturnCallback(function (): void {
            $this->deferredCalls[] = 'begin';
        });
        $cache->method('endDeferred')->willReturnCallback(function (): bool {
            $this->deferredCalls[] = 'end';
            return false;
        });
        return new PageCacheInvalidator(
            $cache,
            $this->createMock(PageLocator::class),
            $this->createMock(PermissionService::class)
        );
    }

    /**
     * Build a BulkOperationService whose deferred seam is spied and whose mutating
     * sub-services are inert real instances (never reached for the failing items in
     * these tests, since the permission gate rejects first).
     */
    private function makeService(
        \OCA\IntraVox\Service\Read\PageReadService $pageRead,
        ?PageWriteService $write = null,
        ?PageStructureService $structure = null
    ): BulkOperationService {
        return new BulkOperationService(
            $write ?? $this->doubleOrBuild(PageWriteService::class),
            $structure ?? $this->doubleOrBuild(PageStructureService::class),
            $this->spyInvalidator(),
            $pageRead,
            $this->createMock(LoggerInterface::class)
        );
    }

    public function testBulkDeleteWrapsLoopInSingleDeferredClear(): void {
        // Every page is un-deletable, so the permission gate rejects each BEFORE the
        // mutation — the loop still runs once, wrapped in exactly one begin/end. The
        // seam, not the delete count, is the subject.
        $pageRead = $this->fakePageReadReturning($this->pageWith(['canDelete' => false]));
        $svc = $this->makeService($pageRead);

        $result = $svc->bulkDelete(['page-1', 'page-2', 'page-3']);

        $this->assertSame(['begin', 'end'], $this->deferredCalls, 'the batch defers exactly once around the whole loop');
        $this->assertSame(3, $result->failCount, 'every un-deletable page is rejected by the gate');
    }

    public function testBulkMoveWrapsLoopInSingleDeferredClear(): void {
        // Target parent writable (so the early return is not taken), each moved page
        // NOT writable → rejected by the gate before the move. One begin/end wraps it.
        $pageRead = $this->fakePageReadReturning($this->pageWith(['canWrite' => true, 'canMove' => false]));
        // The pages themselves report canWrite=true (target check) but we make the
        // per-item move fail via the gate below; simplest is a reader that flips.
        $svc = $this->makeService($this->readerReturning([
            'page-target' => $this->pageWith(['canWrite' => true]),
            'page-1' => $this->pageWith(['canWrite' => false]),
            'page-2' => $this->pageWith(['canWrite' => false]),
        ]));

        $result = $svc->bulkMove(['page-1', 'page-2'], 'page-target');

        $this->assertSame(['begin', 'end'], $this->deferredCalls, 'the move batch defers exactly once');
        $this->assertSame(2, $result->failCount);
    }

    public function testBulkUpdateWrapsLoopInSingleDeferredClear(): void {
        $pageRead = $this->fakePageReadReturning($this->pageWith(['canWrite' => false]));
        $svc = $this->makeService($pageRead);

        $result = $svc->bulkUpdate(['page-1', 'page-2'], ['status' => 'published']);

        $this->assertSame(['begin', 'end'], $this->deferredCalls, 'the update batch defers exactly once');
        $this->assertSame(2, $result->failCount);
    }

    public function testBulkDeleteReleasesDeferredClearWhenItemThrows(): void {
        // A real PageWriteService whose deletePage throws (an unresolvable page over
        // an empty tree) — the loop must still release the deferral in its finally.
        $pageRead = $this->fakePageReadReturning($this->pageWith(['canDelete' => true]));
        $throwingWrite = $this->throwingWriteService();
        $svc = $this->makeService($pageRead, write: $throwingWrite);

        $result = $svc->bulkDelete(['page-1', 'page-2']);

        $this->assertSame(['begin', 'end'], $this->deferredCalls, 'the deferral is released even when every item throws');
        $this->assertSame(0, $result->successCount);
        $this->assertSame(2, $result->failCount);
    }

    public function testBulkMoveReleasesDeferredClearOnUnwritableTargetEarlyReturn(): void {
        // When the target parent is not writable the method early-returns BEFORE the
        // loop; the begin/end seam must NOT run in that path (no deferral started).
        $pageRead = $this->fakePageReadReturning($this->pageWith(['canWrite' => false]));
        $svc = $this->makeService($pageRead);

        $result = $svc->bulkMove(['page-1'], 'page-target');

        $this->assertSame([], $this->deferredCalls, 'no deferral is opened on the unwritable-target early return');
        $this->assertSame(1, $result->failCount);
    }

    // ------------------------------------------------------------------ helpers

    /**
     * A PageReadService whose getPage() answers per-id from the given map (the
     * bulk ops read the target parent then each page). Unmapped ids throw
     * 'Page not found', matching production.
     *
     * @param array<string,array> $byId
     */
    private function readerReturning(array $byId): \OCA\IntraVox\Service\Read\PageReadService {
        return $this->fakePageReadFrom(function (string $id) use ($byId): array {
            if (isset($byId[$id])) {
                return $byId[$id];
            }
            throw new \Exception('Page not found');
        });
    }

    /**
     * A real PageWriteService whose deletePage() throws: it resolves the language
     * folder from a bare FolderContext (which throws "IntraVox folder not found")
     * — reached only after the home guard, so a normal page-id delete surfaces the
     * throw the release-on-throw test needs, through the real service.
     */
    private function throwingWriteService(): PageWriteService {
        $ls = $this->createMock(\OCA\IntraVox\Service\LanguageService::class);
        return new PageWriteService(
            new \OCA\IntraVox\Service\Util\PageIdUtils(),
            $this->createMock(\OCP\EventDispatcher\IEventDispatcher::class),
            $this->createMock(LoggerInterface::class),
            $this->createMock(\OCP\IUserSession::class),
            $this->createMock(\OCA\IntraVox\Service\Version\PageVersionService::class),
            $this->createMock(\OCA\IntraVox\Service\PageIndexService::class),
            $ls,
            $this->fakeFolderContext(), // bare → languageFolder() throws
            new PageLocator($this->createMock(\OCA\IntraVox\Service\PageIndexService::class), $this->createMock(LoggerInterface::class)),
            $this->fakeHomepageResolver(null),
            $this->fakeCacheInvalidator(),
            $this->doubleOrBuild(\OCA\IntraVox\Service\Sanitize\PageShapeSanitizer::class),
            $this->createMock(\OCA\IntraVox\Service\Media\PageMediaService::class),
            $this->createMock(PageCacheService::class),
            new \OCA\IntraVox\Service\Path\PageDepthValidator(new \OCA\IntraVox\Service\Path\PagePathHelper(), $ls),
        );
    }
}
