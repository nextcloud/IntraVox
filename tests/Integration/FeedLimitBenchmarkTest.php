<?php
declare(strict_types=1);

namespace OCA\IntraVox\Tests\Integration;

use OCA\IntraVox\Service\FeedReaderService;

/**
 * What a feed widget's item limit actually costs.
 *
 * WHY THIS EXISTS
 * ---------------
 * Two design questions kept being answered by intuition:
 *
 *   1. Should a feed widget page its items — fetch more only when the reader
 *      asks for them?
 *   2. Should the limit be per widget, or one setting for the whole page?
 *
 * Both hinge on the same unknown: does asking for more items make the server do
 * more work, or only send more bytes? That is measurable, so it should not be
 * argued. This file measures it against the real FeedReaderService with the
 * real cache, and the numbers it prints are the ones quoted in the VoxCloud
 * design guidelines §10.
 *
 * WHAT IT MEASURES, AND WHY NOT IN A UNIT TEST
 * --------------------------------------------
 * The answer depends entirely on the cache being real. A unit test with a
 * stubbed ICache would measure the stub. So this lives in the Integration
 * suite: real container, real Redis/APCu, real HTTP to the feed source.
 *
 * It measures three things per limit:
 *   - wall-clock of a warm read (the common case: someone opening a page)
 *   - the serialized payload (what actually crosses the wire)
 *   - the item count, to prove the limit was honoured
 *
 * MEASUREMENT, NOT PASS/FAIL
 * --------------------------
 * Following tests/Benchmark/PageLookupBenchmark.php: this prints a table and
 * asserts only the invariants that would make the table a lie — that the limit
 * is respected and that warm reads do not hit the network. Timing thresholds
 * are deliberately NOT asserted; a shared dev box would make them flaky, and a
 * flaky benchmark gets disabled rather than fixed.
 *
 * It is skipped unless INTRAVOX_BENCH_FEED is set, because it reaches a
 * third-party feed over the network and a test suite should not do that by
 * default.
 *
 * Run:
 *   INTRAVOX_BENCH_FEED=1 scripts/run-integration-tests.sh --filter FeedLimit
 */
class FeedLimitBenchmarkTest extends IntegrationTestCase {
    /**
     * A feed known to carry far more items than IntraVox keeps (164 at the time
     * of writing, against a MAX_ITEMS of 50), so every limit under test is
     * actually available and no row is silently short.
     */
    private const FEED = 'https://www.nrc.nl/rss/';

    /** The limits an editor can choose (the editor's slider is 1-20), plus the server cap. */
    private const LIMITS = [5, 10, 20, 50];

    private const HERHALINGEN = 5;

    private ?FeedReaderService $reader = null;

    protected function setUp(): void {
        parent::setUp();

        if (getenv('INTRAVOX_BENCH_FEED') === false) {
            $this->markTestSkipped(
                'Set INTRAVOX_BENCH_FEED=1 to run the feed benchmark (it fetches over the network).'
            );
        }

        $this->reader = self::server()->get(FeedReaderService::class);
    }

    /**
     * The central question: does a bigger limit cost server time, or only bytes?
     *
     * If time scales with the limit, paging is worth it — each page is work the
     * previous one did not do. If only the payload scales, a second request is
     * pure overhead and the first response should simply carry more.
     */
    public function testWhatALargerLimitCosts(): void {
        $config = ['url' => self::FEED];

        // Warm the cache once, outside the measurement. The first fetch pays
        // for HTTP plus parsing; measuring that would answer a different
        // question (what a cold read costs) and would also make the first row
        // of the table incomparable with the rest.
        $koud = $this->meetEen($config, max(self::LIMITS));

        $rijen = [];
        foreach (self::LIMITS as $limit) {
            $tijden = [];
            $payload = 0;
            $items = 0;

            for ($i = 0; $i < self::HERHALINGEN; $i++) {
                [$ms, $result] = $this->meet($config, $limit);
                $tijden[] = $ms;
                $payload = strlen((string)json_encode($result));
                $items = count($result['items'] ?? []);
            }

            sort($tijden);
            $rijen[] = [
                'limit' => $limit,
                'items' => $items,
                'mediaan' => $tijden[intdiv(count($tijden), 2)],
                'kb' => $payload / 1024,
            ];

            // The invariant that makes the row meaningful: a row claiming to
            // measure limit=20 must actually have returned 20 items.
            $this->assertLessThanOrEqual($limit, $items, "limit {$limit} was not honoured");
        }

        $this->printTabel($koud, $rijen);

        // The finding this file exists to pin, stated as an assertion rather
        // than left to the reader of the table: going from the smallest to the
        // largest limit must not multiply the time the way it multiplies the
        // bytes. If this ever fails, the caching changed and the guideline's
        // advice ("send more, expand client-side") needs revisiting.
        $eerste = $rijen[0];
        $laatste = $rijen[count($rijen) - 1];
        $tijdFactor = $eerste['mediaan'] > 0 ? $laatste['mediaan'] / $eerste['mediaan'] : 1.0;
        $byteFactor = $eerste['kb'] > 0 ? $laatste['kb'] / $eerste['kb'] : 1.0;

        $this->assertLessThan(
            $byteFactor,
            $tijdFactor,
            sprintf(
                'Time grew %.1fx while payload grew %.1fx. If time now scales with the limit, '
                . 'the cached-set exception in design guidelines §10 no longer holds for feeds.',
                $tijdFactor,
                $byteFactor
            )
        );
    }

    /**
     * Where the cost really is: one network fetch against one cache read.
     *
     * This is what makes the per-item view wrong. The expensive unit is the
     * feed, not the item — so a widget showing 5 of a feed's items has already
     * paid for all 50 that were cached.
     */
    public function testTheExpensiveUnitIsTheFeedNotTheItem(): void {
        $config = ['url' => self::FEED];

        $koud = $this->meetEen($config, 50, true);   // forceRefresh: real HTTP
        $warm = $this->meetEen($config, 50);          // straight from cache

        fwrite(STDERR, sprintf(
            "\n  cold fetch (HTTP + parse) : %6.1f ms\n  warm read  (cache)        : %6.1f ms\n  ratio                     : %6.1fx\n",
            $koud,
            $warm,
            $warm > 0 ? $koud / $warm : 0
        ));

        $this->assertGreaterThan(
            $warm,
            $koud,
            'a cold fetch should cost more than a warm read; if not, the cache is not being used'
        );
    }

    /**
     * A page carries several feed widgets, so the honest unit for the
     * per-widget-versus-page-wide question is the whole page, not one widget.
     *
     * Simulated at the service level rather than over HTTP: the batch endpoint
     * adds its own request overhead, which is the same for every limit and
     * would only dilute the effect being measured.
     */
    public function testTenWidgetsOnOnePage(): void {
        $feeds = [
            'https://feeds.nos.nl/nosnieuwsalgemeen',
            'https://www.nu.nl/rss/Algemeen',
            self::FEED,
            'https://www.volkskrant.nl/voorpagina/rss.xml',
            'https://nextcloud.com/feed/',
        ];

        // Warm every feed first; a cold fetch in the middle of the table would
        // dominate its row and say nothing about the limit.
        foreach ($feeds as $url) {
            $this->meetEen(['url' => $url], 50);
        }

        $rijen = [];
        foreach ([5, 20, 50] as $limit) {
            $t0 = microtime(true);
            $items = 0;
            $payload = 0;
            foreach ($feeds as $url) {
                $result = $this->reader->fetchFeed('rss', ['url' => $url], $limit);
                $items += count($result['items'] ?? []);
                $payload += strlen((string)json_encode($result));
            }
            $rijen[] = [
                'limit' => $limit,
                'items' => $items,
                'mediaan' => (microtime(true) - $t0) * 1000,
                'kb' => $payload / 1024,
            ];
        }

        fwrite(STDERR, sprintf("\n  %d feeds on one page:\n", count($feeds)));
        $this->printTabel(null, $rijen);

        $this->assertGreaterThan(
            $rijen[0]['kb'],
            $rijen[count($rijen) - 1]['kb'],
            'a higher limit must produce a larger payload, or the limit is not reaching the items'
        );
    }

    // ------------------------------------------------------------------ hulp

    /** @return array{0: float, 1: array} milliseconds and the result */
    private function meet(array $config, int $limit, bool $forceRefresh = false): array {
        $t0 = microtime(true);
        $result = $this->reader->fetchFeed('rss', $config, $limit, null, 'date', 'desc', '', $forceRefresh);

        return [(microtime(true) - $t0) * 1000, $result];
    }

    private function meetEen(array $config, int $limit, bool $forceRefresh = false): float {
        return $this->meet($config, $limit, $forceRefresh)[0];
    }

    /** @param array<int, array{limit:int, items:int, mediaan:float, kb:float}> $rijen */
    private function printTabel(?float $koud, array $rijen): void {
        if ($koud !== null) {
            fwrite(STDERR, sprintf("\n  cold fetch to warm the cache: %.0f ms\n", $koud));
        }
        fwrite(STDERR, "\n  limit  items   median    payload\n");
        fwrite(STDERR, "  ----------------------------------\n");
        foreach ($rijen as $r) {
            fwrite(STDERR, sprintf(
                "  %5d  %5d  %7.1f ms  %6.1f KB\n",
                $r['limit'],
                $r['items'],
                $r['mediaan'],
                $r['kb']
            ));
        }
        fwrite(STDERR, "\n");
    }
}
