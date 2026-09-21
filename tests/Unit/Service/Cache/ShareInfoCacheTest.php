<?php
declare(strict_types=1);

namespace OCA\IntraVox\Tests\Unit\Service\Cache;

use OCA\IntraVox\Service\Cache\PageCacheService;
use OCA\IntraVox\Service\Cache\ShareInfoCache;
use OCP\ICache;
use OCP\ICacheFactory;
use PHPUnit\Framework\TestCase;

/**
 * What the share-info cache must never do.
 *
 * The saving here is ordinary — skip a walk up the folder tree — but the risk
 * is not. The cached answer contains a `filesUrl` that PublicShareService
 * builds by resolving the file through the *calling user's own* mount, so an
 * entry shared between users would hand one of them a link built from
 * another's view of the filesystem, plus the implication that they may open
 * it. That is an access-control statement, cached.
 *
 * These tests are therefore mostly about separation: who gets their own entry,
 * and who does not. The performance property (a hit does not recompute) is
 * asserted too, because a cache that silently stops caching is a regression
 * nobody notices until a page is slow again.
 */
class ShareInfoCacheTest extends TestCase {
    /** @var array<string, mixed> */
    private array $opslag = [];

    private function maakCache(): ShareInfoCache {
        $cache = $this->createMock(ICache::class);
        $cache->method('set')->willReturnCallback(function (string $k, $v): bool {
            $this->opslag[$k] = $v;
            return true;
        });
        $cache->method('get')->willReturnCallback(fn(string $k) => $this->opslag[$k] ?? null);

        $factory = $this->createMock(ICacheFactory::class);
        $factory->method('isAvailable')->willReturn(true);
        $factory->method('createDistributed')->willReturn($cache);

        return new ShareInfoCache(new PageCacheService($factory));
    }

    /**
     * The one that matters: user A's answer must never be served to user B.
     */
    public function testTwoUsersNeverShareAnEntry(): void {
        $cache = $this->maakCache();

        $vanAnne = $cache->remember('page1', 'nl', 'anne', fn() => ['filesUrl' => '/files/anne', 'hasShare' => true]);
        $vanBob = $cache->remember('page1', 'nl', 'bob', fn() => ['filesUrl' => '/files/bob', 'hasShare' => true]);

        $this->assertSame('/files/anne', $vanAnne['filesUrl']);
        $this->assertSame('/files/bob', $vanBob['filesUrl'], "bob got anne's answer");
    }

    /**
     * An anonymous caller is its own audience, not "whoever asked first".
     */
    public function testAnonymousDoesNotShareWithALoggedInUser(): void {
        $cache = $this->maakCache();

        $cache->remember('page1', 'nl', 'anne', fn() => ['who' => 'anne']);
        $anoniem = $cache->remember('page1', 'nl', null, fn() => ['who' => 'anon']);

        $this->assertSame('anon', $anoniem['who']);
    }

    /** A page's answer must not leak into another page. */
    public function testDifferentPagesGetDifferentEntries(): void {
        $cache = $this->maakCache();

        $cache->remember('page1', 'nl', 'anne', fn() => ['p' => 1]);
        $tweede = $cache->remember('page2', 'nl', 'anne', fn() => ['p' => 2]);

        $this->assertSame(2, $tweede['p']);
    }

    /**
     * Language is part of the identity of a page here: the same uniqueId in
     * another language folder is a different file, which may be shared
     * differently.
     */
    public function testLanguageSeparatesEntries(): void {
        $cache = $this->maakCache();

        $cache->remember('page1', 'nl', 'anne', fn() => ['taal' => 'nl']);
        $engels = $cache->remember('page1', 'en', 'anne', fn() => ['taal' => 'en']);

        $this->assertSame('en', $engels['taal']);
    }

    /** The point of the thing: a second ask must not redo the work. */
    public function testASecondCallDoesNotRecompute(): void {
        $cache = $this->maakCache();
        $keer = 0;
        $werk = function () use (&$keer) {
            $keer++;
            return ['hasShare' => true];
        };

        $cache->remember('page1', 'nl', 'anne', $werk);
        $cache->remember('page1', 'nl', 'anne', $werk);

        $this->assertSame(1, $keer, 'the walk ran twice for one cached answer');
    }

    /**
     * Without a distributed backend the cache must still answer correctly —
     * just without remembering. That is an install with no Redis or APCu, and
     * it must not be a broken one.
     */
    public function testWorksWithoutAnyBackend(): void {
        $cache = new ShareInfoCache(null);
        $keer = 0;

        for ($i = 0; $i < 2; $i++) {
            $uit = $cache->remember('page1', 'nl', 'anne', function () use (&$keer) {
                $keer++;
                return ['hasShare' => false];
            });
            $this->assertFalse($uit['hasShare']);
        }

        $this->assertSame(2, $keer, 'without a backend every call recomputes, by design');
    }

    /** Keys are opaque, but they must at least differ where it counts. */
    public function testKeysDifferPerUserPageAndLanguage(): void {
        $cache = new ShareInfoCache(null);

        $sleutels = [
            $cache->keyFor('p', 'nl', 'anne'),
            $cache->keyFor('p', 'nl', 'bob'),
            $cache->keyFor('p', 'nl', null),
            $cache->keyFor('p', 'en', 'anne'),
            $cache->keyFor('q', 'nl', 'anne'),
        ];

        $this->assertCount(5, array_unique($sleutels), 'two distinct inputs produced one key');
    }
}
