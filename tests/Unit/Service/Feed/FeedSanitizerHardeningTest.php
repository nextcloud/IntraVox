<?php
declare(strict_types=1);

namespace OCA\IntraVox\Tests\Unit\Service\Feed;

use OCA\IntraVox\Service\Feed\FeedArticleStore;
use OCA\IntraVox\Service\Sanitize\HtmlSanitizer;
use OCP\ICache;
use PHPUnit\Framework\TestCase;

/**
 * What a hostile feed cannot get past the sanitizer and into an article.
 *
 * Feed HTML is the least trustworthy input in the app: it is third-party, it is
 * rendered with v-html, and the reader never chose to trust its author. The
 * article body is sanitized once, on the way into the cache, so this is the
 * single gate — anything through it is served to every reader of that feed,
 * including anonymous visitors on a public share.
 *
 * These cases are written as a checklist of the vectors that keep coming back
 * rather than as coverage for the sanitizer's branches. Each one asserts what
 * must NOT survive, so the test fails loudly if a future rewrite loosens the
 * whitelist rather than quietly changing what it strips.
 *
 * @see FeedArticleStore::prepare() for the strip-then-truncate order.
 * @see HtmlSanitizerTest for the sanitizer's own behaviour.
 */
class FeedSanitizerHardeningTest extends TestCase {
    private FeedArticleStore $store;
    /** @var array<string, string> */
    private array $opslag = [];

    protected function setUp(): void {
        parent::setUp();

        $cache = $this->createMock(ICache::class);
        $cache->method('set')->willReturnCallback(function (string $k, $v): bool {
            $this->opslag[$k] = (string)$v;
            return true;
        });
        $cache->method('get')->willReturnCallback(fn(string $k) => $this->opslag[$k] ?? null);

        $this->store = new FeedArticleStore(new HtmlSanitizer(), $cache);
    }

    /** Store one body and read back exactly what a reader would receive. */
    private function doorDeMolen(string $html): string {
        $this->opslag = [];
        $this->store->putAll('feed_x', [['id' => '1', 'contentHtml' => $html]]);

        return (string)$this->store->get('feed_x', '1');
    }

    public static function scriptVectors(): array {
        return [
            'plain script' => ['<p>ok</p><script>alert(1)</script>'],
            'uppercase' => ['<p>ok</p><SCRIPT>alert(1)</SCRIPT>'],
            'with attributes' => ['<p>ok</p><script type="text/javascript" defer>alert(1)</script>'],
            'split across lines' => ["<p>ok</p><script>\nalert(1);\n</script>"],
            'nested in noscript' => ['<p>ok</p><noscript><script>alert(1)</script></noscript>'],
            'style block' => ['<p>ok</p><style>body{display:none}</style>'],
        ];
    }

    /**
     * Script and style go with their contents, not just their tags.
     *
     * strip_tags() keeps inner text, which is right for editor HTML — losing a
     * paragraph's words would destroy an author's work — and wrong here, where
     * "alert(1)" would appear as a line of the article. Harmless but plainly
     * broken, and the kind of thing a reader reports as "the site is hacked".
     *
     * @dataProvider scriptVectors
     */
    public function testScriptAndStyleAreRemovedWithTheirContents(string $html): void {
        $uit = $this->doorDeMolen($html);

        $this->assertStringContainsString('<p>ok</p>', $uit, 'the real content must survive');
        $this->assertStringNotContainsString('alert(1)', $uit);
        $this->assertStringNotContainsString('display:none', $uit);
        $this->assertDoesNotMatchRegularExpression('/<\s*script/i', $uit);
        $this->assertDoesNotMatchRegularExpression('/<\s*style/i', $uit);
    }

    public static function eventHandlerVectors(): array {
        return [
            'img onerror' => ['<img src=x onerror=alert(1)>'],
            'body onload' => ['<div onload="alert(1)">x</div>'],
            'onclick quoted' => ['<p onclick="alert(1)">x</p>'],
            'onmouseover unquoted' => ['<p onmouseover=alert(1)>x</p>'],
            'svg onload' => ['<svg onload=alert(1)></svg>'],
            'onfocus autofocus' => ['<input onfocus=alert(1) autofocus>'],
        ];
    }

    /** @dataProvider eventHandlerVectors */
    public function testEventHandlersNeverSurvive(string $html): void {
        $uit = $this->doorDeMolen($html);

        $this->assertDoesNotMatchRegularExpression(
            '/\bon[a-z]+\s*=/i',
            $uit,
            'an inline event handler reached the reader'
        );
    }

    public static function embedVectors(): array {
        return [
            'iframe' => ['<iframe src="//evil.example/x"></iframe>'],
            'object' => ['<object data="//evil.example/x"></object>'],
            'embed' => ['<embed src="//evil.example/x">'],
            'form with input' => ['<form action="//evil.example"><input name="pw" type="password"></form>'],
            'meta refresh' => ['<meta http-equiv="refresh" content="0;url=//evil.example">'],
            'base tag' => ['<base href="//evil.example/">'],
            'link stylesheet' => ['<link rel="stylesheet" href="//evil.example/x.css">'],
        ];
    }

    /**
     * Nothing that loads or submits anywhere.
     *
     * An iframe or a form in an article body would let a feed publish a login
     * prompt inside the intranet's own chrome — the reader has every reason to
     * believe what they see belongs to IntraVox.
     *
     * @dataProvider embedVectors
     */
    public function testNothingThatLoadsOrSubmitsSurvives(string $html): void {
        $uit = $this->doorDeMolen($html);

        foreach (['iframe', 'object', 'embed', 'form', 'meta', 'base', 'link'] as $tag) {
            $this->assertDoesNotMatchRegularExpression("/<\s*{$tag}\b/i", $uit, "<{$tag}> survived");
        }
    }

    /**
     * No images at all.
     *
     * Not an XSS concern but a privacy one, and the reason the feature is worth
     * having: a reader opens the article precisely to avoid the publisher's
     * page. An <img> pointing at the source would hand that publisher the
     * reader's IP and a tracking pixel anyway.
     */
    public function testImagesAreStrippedSoNothingPhonesHome(): void {
        $uit = $this->doorDeMolen(
            '<p>Tekst</p><img src="https://tracker.example/pixel.gif" width="1" height="1">'
        );

        $this->assertStringContainsString('<p>Tekst</p>', $uit);
        $this->assertDoesNotMatchRegularExpression('/<\s*img/i', $uit);
        $this->assertStringNotContainsString('tracker.example', $uit);
    }

    public static function scriptUriVectors(): array {
        return [
            'javascript lower' => ['<a href="javascript:alert(1)">x</a>'],
            'javascript mixed case' => ['<a href="JaVaScRiPt:alert(1)">x</a>'],
            'vbscript' => ['<a href="vbscript:msgbox(1)">x</a>'],
        ];
    }

    /** @dataProvider scriptUriVectors */
    public function testScriptUrisAreDefused(string $html): void {
        $uit = $this->doorDeMolen($html);

        $this->assertDoesNotMatchRegularExpression('/javascript\s*:/i', $uit);
        $this->assertDoesNotMatchRegularExpression('/vbscript\s*:/i', $uit);
    }

    /**
     * CSS is a vector too, and the one people forget.
     *
     * expression() runs script in old engines, and url() in an article body
     * fetches from wherever the feed says — the same privacy leak as an <img>,
     * dressed as styling.
     */
    public function testDangerousCssIsRemoved(): void {
        $uit = $this->doorDeMolen(
            '<p style="color:expression(alert(1));background:url(//tracker.example/x.png)">x</p>'
        );

        $this->assertStringNotContainsString('expression', $uit);
        $this->assertStringNotContainsString('tracker.example', $uit);
        $this->assertStringContainsString('x</p>', $uit, 'the text itself must survive');
    }

    /**
     * Ordinary article markup has to come through, or the sanitizer is only
     * safe because it destroys everything.
     */
    public function testRealArticleMarkupSurvives(): void {
        $uit = $this->doorDeMolen(
            '<h2>Kop</h2>'
            . '<p>Een <strong>vette</strong> en <em>cursieve</em> alinea met een '
            . '<a href="https://nos.nl/artikel/1">link</a>.</p>'
            . '<ul><li>punt een</li><li>punt twee</li></ul>'
            . '<blockquote><p>Een citaat.</p></blockquote>'
            . '<table><tr><th>Kop</th><td>Cel</td></tr></table>'
        );

        foreach (['<h2>Kop</h2>', '<strong>vette</strong>', '<em>cursieve</em>',
                  'href="https://nos.nl/artikel/1"', '<li>punt een</li>',
                  '<blockquote>', '<table>', '<th>Kop</th>'] as $verwacht) {
            $this->assertStringContainsString($verwacht, $uit, "lost: {$verwacht}");
        }
    }

    /**
     * A body of nothing but hostile markup is not worth a cache entry, and the
     * widget must not offer to open an article that is empty.
     */
    public function testABodyOfOnlyHostileMarkupIsNotStored(): void {
        $uit = $this->store->putAll('feed_x', [
            ['id' => '1', 'contentHtml' => '<script>alert(1)</script><iframe src="//evil"></iframe>'],
        ]);

        $this->assertFalse($uit[0]['hasArticle']);
        $this->assertNull($this->store->get('feed_x', '1'));
    }

    /**
     * Truncation must not be a way in.
     *
     * The cut happens after sanitizing, so a body engineered to put a payload
     * across the boundary cannot have the closing part removed to leave
     * something executable behind.
     */
    public function testTruncationCannotReopenAnInjection(): void {
        $vulling = str_repeat('<p>vulling</p>', 9000);
        $uit = $this->doorDeMolen($vulling . '<script>alert(1)</script>');

        $this->assertLessThanOrEqual(FeedArticleStore::MAX_ARTICLE_BYTES, strlen($uit));
        $this->assertStringNotContainsString('alert(1)', $uit);
        $this->assertDoesNotMatchRegularExpression('/<\s*script/i', $uit);
    }
}
