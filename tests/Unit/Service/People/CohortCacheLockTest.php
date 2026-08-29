<?php

declare(strict_types=1);

namespace OCA\IntraVox\Tests\Unit\Service\People;

use OCA\IntraVox\Service\People\CohortCache;
use OCA\IntraVox\Service\People\CohortSnapshot;
use OCP\ICache;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * The rebuild lock, and what happens when the cache backend cannot provide one.
 *
 * On an LDAP instance $group->getUsers() takes 10-30 seconds. Without a lock,
 * every concurrent reader arriving during a cache miss starts its own full
 * scan — fifty readers means fifty simultaneous scans, which is an outage
 * rather than a slow page.
 *
 * The lock is add(): atomic set-if-absent. That method lives on IMemcache, not
 * on ICache — Redis and APCu have it, the database backend does not. This pins
 * both halves of that: take the lock where it exists, and proceed anyway where
 * it does not, because a duplicate scan beats no cohort at all.
 */
class CohortCacheLockTest extends TestCase {
    private function snapshot(): CohortSnapshot {
        return new CohortSnapshot([], false, 0, 0);
    }

    /** A cache that supports add(), like Redis. */
    private function memcacheLike(): object {
        return new class implements ICache {
            /** @var array<string,mixed> */
            public array $store = [];
            public int $addCalls = 0;

            public function get($key) {
                return $this->store[$key] ?? null;
            }
            public function set($key, $value, $ttl = 0) {
                $this->store[$key] = $value;
                return true;
            }
            public function hasKey($key) {
                return isset($this->store[$key]);
            }
            public function remove($key) {
                unset($this->store[$key]);
                return true;
            }
            public function clear($prefix = '') {
                $this->store = [];
                return true;
            }
            public function add($key, $value, $ttl = 0) {
                $this->addCalls++;
                if (isset($this->store[$key])) {
                    return false;
                }
                $this->store[$key] = $value;
                return true;
            }
        };
    }

    private function cache(?ICache $backend, callable $scan): CohortCache {
        return new CohortCache(
            $backend,
            $this->createMock(LoggerInterface::class),
            $scan,
            static fn (): string => 'audience-test',
            static fn (): array => ['audience' => 'loggedin', 'groupHash' => 'nogroups'],
        );
    }

    public function testTheFirstCallerTakesTheLockAndTheSecondDoesNot(): void {
        $backend = $this->memcacheLike();
        $cache = $this->cache($backend, fn () => $this->snapshot());

        $this->assertTrue($cache->acquireRebuildLock('lock_a'), 'first caller rebuilds');
        $this->assertFalse($cache->acquireRebuildLock('lock_a'), 'second caller must not');
    }

    public function testReleasingTheLockLetsTheNextRebuildIn(): void {
        $backend = $this->memcacheLike();
        $cache = $this->cache($backend, fn () => $this->snapshot());

        $cache->acquireRebuildLock('lock_b');
        $cache->releaseRebuildLock('lock_b');

        $this->assertTrue($cache->acquireRebuildLock('lock_b'));
    }

    /**
     * The database backend has no add(). The rebuild must still happen: a
     * duplicate scan is worse than one scan, and far better than an empty
     * people widget.
     */
    public function testABackendWithoutAddStillAllowsTheRebuild(): void {
        $plain = $this->createMock(ICache::class);
        $cache = $this->cache($plain, fn () => $this->snapshot());

        $this->assertTrue($cache->acquireRebuildLock('lock_c'));
        $this->assertTrue($cache->acquireRebuildLock('lock_c'), 'and it never starts blocking');
    }

    /** No cache at all (cacheFactory unavailable) must not break the scan. */
    public function testWithoutACacheEveryCallerProceeds(): void {
        $cache = $this->cache(null, fn () => $this->snapshot());

        $this->assertTrue($cache->acquireRebuildLock('lock_d'));
        $this->assertTrue($cache->acquireRebuildLock('lock_d'));
    }
}
