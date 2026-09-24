<?php
declare(strict_types=1);

namespace OCA\IntraVox\Tests\Unit\Service\Feed;

use OCA\IntraVox\Service\FeedReaderService;
use PHPUnit\Framework\TestCase;

/**
 * A batch never sleeps on the singleflight lock.
 *
 * When a feed's cache entry is missing and another request already holds the
 * refresh lock, fetchFeed() waits for that request to populate the cache:
 * fifty 100ms steps, up to five seconds. For ONE feed that is the behaviour the
 * lock was designed for — one reader waits rather than stampeding the source.
 *
 * In a batch it does not scale, because the wait is paid per feed and the feeds
 * are fetched one after another. The dev instance has pages carrying 57, 33, 33,
 * 33 and 32 feed widgets, sent 20 at a time, so a single batch could spend
 * 20 x 5s = 100 seconds asleep without issuing one HTTP request. It also
 * contradicts the stale-while-revalidate design around it, which exists to hand
 * back what we have rather than block a reader behind someone else's refetch.
 *
 * What these tests pin is the switch itself, not the arithmetic:
 *
 *  - the default is unchanged, so the single-feed routes still wait;
 *  - inside withoutSingleflightWait() the budget is zero;
 *  - the previous value is restored afterwards, including when the work throws,
 *    because the flag is instance state on a service shared by every route in
 *    the request — a batch must not change how the single-feed routes behave
 *    for the rest of it.
 */
class FeedBatchSingleflightTest extends TestCase {
    /** The wait budget, read off a constructor-less instance. */
    private function steps(FeedReaderService $svc): int {
        $p = new \ReflectionProperty(FeedReaderService::class, 'singleflightWaitSteps');

        return $p->getValue($svc);
    }

    private function service(): FeedReaderService {
        return (new \ReflectionClass(FeedReaderService::class))->newInstanceWithoutConstructor();
    }

    /**
     * 50 steps of 100ms. The single-feed routes — and FeedRefreshJob — keep the
     * wait that the singleflight lock exists to provide.
     */
    public function testTheDefaultWaitIsUnchanged(): void {
        $this->assertSame(50, $this->steps($this->service()));
    }

    /** Inside the callable, nothing sleeps. */
    public function testABatchWaitsForNothing(): void {
        $svc = $this->service();
        $gezien = null;

        $svc->withoutSingleflightWait(function () use ($svc, &$gezien) {
            $gezien = $this->steps($svc);
        });

        $this->assertSame(0, $gezien, 'a batch must not sleep on the lock');
    }

    /** The flag is shared state, so it goes back to what it was. */
    public function testThePreviousBudgetIsRestored(): void {
        $svc = $this->service();

        $svc->withoutSingleflightWait(fn() => null);

        $this->assertSame(50, $this->steps($svc), 'the single-feed routes keep their wait');
    }

    /**
     * Restored on the failure path too. Without the finally, one failing feed
     * would silently disable the wait for every later call in the request.
     */
    public function testTheBudgetIsRestoredWhenTheWorkThrows(): void {
        $svc = $this->service();

        try {
            $svc->withoutSingleflightWait(function (): void {
                throw new \RuntimeException('feed exploded');
            });
            $this->fail('the exception should reach the caller');
        } catch (\RuntimeException $e) {
            $this->assertSame('feed exploded', $e->getMessage());
        }

        $this->assertSame(50, $this->steps($svc));
    }

    /** The callable's return value is handed back unchanged. */
    public function testTheResultIsPassedThrough(): void {
        $svc = $this->service();

        $result = $svc->withoutSingleflightWait(fn() => ['items' => [1, 2, 3]]);

        $this->assertSame(['items' => [1, 2, 3]], $result);
    }

    /**
     * Nesting keeps the outer restore honest — a batch inside a batch (the
     * client can send a second one while the first is in flight) must not
     * restore to 0 and leave the wait disabled.
     */
    public function testNestingRestoresToTheOuterValue(): void {
        $svc = $this->service();

        $svc->withoutSingleflightWait(function () use ($svc) {
            $svc->withoutSingleflightWait(fn() => null);
            $this->assertSame(0, $this->steps($svc), 'still inside the outer batch');
        });

        $this->assertSame(50, $this->steps($svc));
    }
}
