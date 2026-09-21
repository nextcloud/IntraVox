<?php
declare(strict_types=1);

namespace OCA\IntraVox\Tests\Unit\Service\Feed;

use OCA\IntraVox\Service\Feed\FeedArticleStore;
use OCA\IntraVox\Service\Sanitize\HtmlSanitizer;
use OCP\ICache;
use PHPUnit\Framework\TestCase;

/**
 * The article store: what it keeps, what it refuses, and who may read it.
 */
class FeedArticleStoreTest extends TestCase {
    private FeedArticleStore $store;
    private ICache $cache;
    /** @var array<string, string> */
    private array $opslag = [];

    protected function setUp(): void {
        parent::setUp();

        // An in-memory stand-in for Redis. Real enough for the behaviour under
        // test — set/get by key — without a server in the unit suite.
        $this->cache = $this->createMock(ICache::class);
        $this->cache->method('set')->willReturnCallback(
            function (string $k, $v): bool {
                $this->opslag[$k] = (string)$v;
                return true;
            }
        );
        $this->cache->method('get')->willReturnCallback(
            fn(string $k) => $this->opslag[$k] ?? null
        );

        $this->store = new FeedArticleStore(new HtmlSanitizer(), $this->cache);
    }

    private function item(string $id, string $html): array {
        return ['id' => $id, 'title' => 'Kop', 'contentHtml' => $html];
    }

    // ---------- what leaves in the list ----------

    /**
     * The body must not travel with the list. That is the whole point of the
     * split: measured on dev, folding it in makes the per-feed entry 211 KB
     * against 195 KB spread over items, and every page view pays the difference
     * to read nothing.
     */
    public function testTheBodyIsStrippedFromTheListItems(): void {
        $uit = $this->store->putAll('feed_abc', [$this->item('1', '<p>Artikel</p>')]);

        $this->assertArrayNotHasKey('contentHtml', $uit[0]);
        $this->assertTrue($uit[0]['hasArticle']);
    }

    /**
     * The flag is what the widget reads to decide whether to offer opening the
     * piece. Without it the client would have to request every item to find
     * out which ones have anything behind them.
     */
    public function testAnItemWithoutABodyIsFlaggedAsHavingNone(): void {
        $uit = $this->store->putAll('feed_abc', [
            $this->item('1', ''),
            ['id' => '2', 'title' => 'Geen contentHtml-sleutel'],
        ]);

        $this->assertFalse($uit[0]['hasArticle']);
        $this->assertFalse($uit[1]['hasArticle']);
    }

    /** An item with no id cannot be addressed later, so it is not stored. */
    public function testAnItemWithoutAnIdIsNotStored(): void {
        $uit = $this->store->putAll('feed_abc', [['id' => '', 'contentHtml' => '<p>x</p>']]);

        $this->assertFalse($uit[0]['hasArticle']);
        $this->assertSame([], $this->opslag);
    }

    // ---------- round trip ----------

    public function testAStoredArticleComesBack(): void {
        $this->store->putAll('feed_abc', [$this->item('item-1', '<p>Het <strong>artikel</strong>.</p>')]);

        $this->assertSame(
            '<p>Het <strong>artikel</strong>.</p>',
            $this->store->get('feed_abc', 'item-1')
        );
    }

    /** A miss is ordinary: entries expire with the feed they came from. */
    public function testAMissReturnsNull(): void {
        $this->assertNull($this->store->get('feed_abc', 'bestaat-niet'));
        $this->assertNull($this->store->get('feed_abc', ''));
    }

    public function testWithoutACacheNothingIsStoredAndNothingBreaks(): void {
        $zonder = new FeedArticleStore(new HtmlSanitizer(), null);

        $uit = $zonder->putAll('feed_abc', [$this->item('1', '<p>x</p>')]);

        $this->assertFalse($uit[0]['hasArticle']);
        $this->assertNull($zonder->get('feed_abc', '1'));
    }

    // ---------- isolation ----------

    /**
     * The key is derived from the feed's own cache key, so whatever separates
     * one reader's feed from another's separates their article bodies too — a
     * personalised LMS feed is per user, a public share is per token. Composing
     * the key from the item id alone would hand one reader's article to
     * another.
     */
    public function testTwoFeedsDoNotShareArticles(): void {
        $this->store->putAll('feed_alice', [$this->item('same-id', '<p>Alice.</p>')]);
        $this->store->putAll('feed_bob', [$this->item('same-id', '<p>Bob.</p>')]);

        $this->assertSame('<p>Alice.</p>', $this->store->get('feed_alice', 'same-id'));
        $this->assertSame('<p>Bob.</p>', $this->store->get('feed_bob', 'same-id'));
        $this->assertNotSame(
            $this->store->keyFor('feed_alice', 'same-id'),
            $this->store->keyFor('feed_bob', 'same-id')
        );
    }

    /** A guid may be a URL or contain separators, so it is hashed. */
    public function testTheKeyIsStableAndOpaque(): void {
        $k = $this->store->keyFor('feed_abc', 'https://example.com/a?b=c#d');

        $this->assertSame($k, $this->store->keyFor('feed_abc', 'https://example.com/a?b=c#d'));
        $this->assertMatchesRegularExpression('/^feedart_[0-9a-f]{32}$/', $k);
    }

    // ---------- safety ----------

    /**
     * Sanitized on the way in, so a body that was never stored unsafe cannot be
     * served unsafe by a later mistake at the controller.
     */
    public function testArticlesAreSanitizedBeforeStorage(): void {
        $this->store->putAll('feed_abc', [
            $this->item('1', '<p>Goed</p><script>alert(1)</script><img src=x onerror=alert(1)>'),
        ]);

        $opgeslagen = $this->store->get('feed_abc', '1');

        $this->assertStringContainsString('<p>Goed</p>', $opgeslagen);
        $this->assertStringNotContainsString('<script', $opgeslagen);
        $this->assertStringNotContainsString('onerror', $opgeslagen);
        $this->assertStringNotContainsString('<img', $opgeslagen);
    }

    /**
     * An outlier must not cost every other reader cache space. Truncating beats
     * refusing: the opening is still worth reading, and the link out is always
     * offered beside it.
     */
    public function testAnOversizedArticleIsTruncatedRatherThanRefused(): void {
        $lang = '<p>' . str_repeat('x', FeedArticleStore::MAX_ARTICLE_BYTES * 2) . '</p>';

        $this->store->putAll('feed_abc', [$this->item('1', $lang)]);
        $opgeslagen = $this->store->get('feed_abc', '1');

        $this->assertNotNull($opgeslagen);
        $this->assertLessThanOrEqual(FeedArticleStore::MAX_ARTICLE_BYTES, strlen($opgeslagen));
    }

    /** Truncation must not cut a multi-byte character in half. */
    public function testTruncationLandsOnACharacterBoundary(): void {
        $lang = '<p>' . str_repeat('é', FeedArticleStore::MAX_ARTICLE_BYTES) . '</p>';

        $this->store->putAll('feed_abc', [$this->item('1', $lang)]);

        $this->assertSame(
            $this->store->get('feed_abc', '1'),
            mb_convert_encoding($this->store->get('feed_abc', '1'), 'UTF-8', 'UTF-8'),
            'the stored string must still be valid UTF-8'
        );
    }

    /** Markup that sanitizes away to nothing is not worth an entry. */
    public function testABodyThatSanitizesToNothingIsNotStored(): void {
        $uit = $this->store->putAll('feed_abc', [$this->item('1', '<script>alert(1)</script>')]);

        $this->assertFalse($uit[0]['hasArticle']);
        $this->assertNull($this->store->get('feed_abc', '1'));
    }
}
