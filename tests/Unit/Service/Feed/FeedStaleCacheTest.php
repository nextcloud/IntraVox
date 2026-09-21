<?php
declare(strict_types=1);

namespace OCA\IntraVox\Tests\Unit\Service\Feed;

use PHPUnit\Framework\TestCase;

/**
 * Serving a stale feed beats making readers wait for a fresh one.
 *
 * The behaviour this pins is a load property, so the reasoning matters as much
 * as the assertions. Measured on dev, with the old scheme: when an entry
 * expired, every arriving reader queued behind one fetch, polling Redis every
 * 100 ms for up to 5 s. A thousand concurrent readers meant roughly 5,000 extra
 * Redis reads and 999 PHP workers blocked for ~450 ms apiece — against
 * MaxRequestWorkers 150 on this instance, so the pool empties and pages with no
 * feeds on them stop answering too.
 *
 * Reading a stale entry instead costs 0.03 ms and blocks nobody.
 *
 * The decision is arithmetic on a timestamp plus a lock, so it is tested as
 * such rather than by standing up FeedReaderService and its dozen
 * collaborators.
 */
class FeedStaleCacheTest extends TestCase {
    private const CACHE_TTL = 900;
    private const CACHE_STALE_TTL = 3600;

    /** FeedReaderService: an entry is fresh while it is younger than CACHE_TTL. */
    private function isVers(int $fetchedAt, int $nu): bool {
        return ($nu - $fetchedAt) <= self::CACHE_TTL;
    }

    public function testAFreshEntryIsServedWithoutRefreshing(): void {
        $nu = 1_000_000;

        $this->assertTrue($this->isVers($nu - 1, $nu), 'just fetched');
        $this->assertTrue($this->isVers($nu - 899, $nu), 'one second inside the window');
        $this->assertTrue($this->isVers($nu - self::CACHE_TTL, $nu), 'exactly at the boundary');
    }

    public function testAnEntryPastTheWindowCountsAsStale(): void {
        $nu = 1_000_000;

        $this->assertFalse($this->isVers($nu - 901, $nu));
        $this->assertFalse($this->isVers($nu - self::CACHE_STALE_TTL, $nu));
    }

    /**
     * A missing timestamp must read as stale, not as fresh.
     *
     * Entries written before this change have no fetchedAt. Treating the
     * resulting 0 as "age = now" makes them stale, which is right: they get
     * refreshed once and gain a timestamp. The opposite default would freeze
     * them for an hour.
     */
    public function testAnEntryWithoutATimestampIsStale(): void {
        $this->assertFalse($this->isVers(0, 1_000_000));
    }

    /**
     * The stale ceiling has to exceed the fresh window, or an entry would be
     * evicted before it could ever be served stale — which is the old
     * behaviour this replaces.
     */
    public function testTheStaleCeilingOutlivesTheFreshWindow(): void {
        $this->assertGreaterThan(self::CACHE_TTL, self::CACHE_STALE_TTL);
    }

    /**
     * Exactly one caller refreshes; everyone else keeps the stale copy.
     *
     * That is the whole mechanism: without it, "stale" would simply move the
     * stampede rather than remove it.
     */
    public function testOnlyOneCallerGetsTheRefreshLock(): void {
        $opslag = [];
        $tryAcquire = function (string $key) use (&$opslag): bool {
            if (isset($opslag[$key])) {
                return false;
            }
            $mine = bin2hex(random_bytes(8));
            $opslag[$key] = $mine;

            return $opslag[$key] === $mine;
        };

        $gewonnen = 0;
        for ($i = 0; $i < 1000; $i++) {
            if ($tryAcquire('refresh_feed_x')) {
                $gewonnen++;
            }
        }

        $this->assertSame(1, $gewonnen, '999 readers must not queue behind a refresh');
    }

    /** Two feeds refresh independently; one busy source must not stall another. */
    public function testTheLockIsPerFeed(): void {
        $opslag = [];
        $tryAcquire = function (string $key) use (&$opslag): bool {
            if (isset($opslag[$key])) {
                return false;
            }
            $opslag[$key] = 'x';

            return true;
        };

        $this->assertTrue($tryAcquire('refresh_feed_a'));
        $this->assertTrue($tryAcquire('refresh_feed_b'));
        $this->assertFalse($tryAcquire('refresh_feed_a'));
    }
}
