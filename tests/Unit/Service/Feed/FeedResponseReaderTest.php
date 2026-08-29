<?php
declare(strict_types=1);

namespace OCA\IntraVox\Tests\Unit\Service\Feed;

use OCA\IntraVox\Service\Feed\FeedResponseReader;
use PHPUnit\Framework\TestCase;

/**
 * The shared half of the six feed connectors.
 *
 * These were private helpers on FeedReaderService, reachable only through a
 * live HTTP call to an LMS, and therefore untested. Extracting them made them
 * testable against strings, which is most of the point.
 */
class FeedResponseReaderTest extends TestCase {
    private FeedResponseReader $reader;

    protected function setUp(): void {
        parent::setUp();
        $this->reader = new FeedResponseReader();
    }

    private function response(int $status, string $body = ''): object {
        return new class($status, $body) {
            public function __construct(private int $status, private string $body) {
            }
            public function getStatusCode(): int {
                return $this->status;
            }
            public function getBody(): string {
                return $this->body;
            }
        };
    }

    public function testSuccessfulStatusesPass(): void {
        foreach ([200, 201, 204, 299] as $status) {
            $this->reader->assertSuccessful($this->response($status));
        }
        $this->addToAssertionCount(4);
    }

    /**
     * The distinction is the point: an admin reading "Access denied (403)" knows
     * the token works and the account lacks rights, which "request failed" does
     * not tell them.
     */
    public function testEachFailureStatusKeepsItsOwnMessage(): void {
        foreach ([
            401 => 'Authentication failed (401)',
            403 => 'Access denied (403)',
            404 => 'Not found (404)',
            429 => 'Rate limited (429)',
            500 => 'Server error (500)',
            418 => 'HTTP error (418)',
        ] as $status => $expected) {
            try {
                $this->reader->assertSuccessful($this->response($status));
                $this->fail("status $status should throw");
            } catch (\RuntimeException $e) {
                $this->assertStringContainsString($expected, $e->getMessage());
            }
        }
    }

    public function testTheContextPrefixesTheMessage(): void {
        try {
            $this->reader->assertSuccessful($this->response(404), 'SharePoint list');
            $this->fail('should throw');
        } catch (\RuntimeException $e) {
            $this->assertStringStartsWith('SharePoint list: ', $e->getMessage());
        }
    }

    /** A feed source is external; an unbounded body would exhaust memory. */
    public function testAnOversizedBodyIsRefusedRatherThanParsed(): void {
        $big = str_repeat('x', FeedResponseReader::MAX_RESPONSE_SIZE + 1);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessageMatches('/response too large/');
        $this->reader->decodeJson($this->response(200, $big));
    }

    public function testOrdinaryJsonDecodes(): void {
        $this->assertSame(
            ['items' => [1, 2]],
            $this->reader->decodeJson($this->response(200, '{"items":[1,2]}'))
        );
    }

    public function testExcerptStripsMarkupAndDecodesEntities(): void {
        $this->assertSame(
            'Hallo wereld & vrienden',
            $this->reader->excerpt('<p>Hallo <b>wereld</b> &amp; vrienden</p>')
        );
    }

    public function testExcerptTruncatesOnAWordBoundary(): void {
        $text = str_repeat('woord ', 200);
        $out = $this->reader->excerpt($text);

        $this->assertLessThanOrEqual(FeedResponseReader::EXCERPT_LENGTH + 3, mb_strlen($out));
        $this->assertStringEndsWith('...', $out);
        $this->assertStringNotContainsString('woor.', $out, 'must not cut mid-word');
    }

    /**
     * A single very long token has no space to snap back to. The 0.7 guard means
     * we accept a mid-word cut rather than truncating almost everything away.
     */
    public function testASingleLongTokenStillProducesAnExcerpt(): void {
        $out = $this->reader->excerpt(str_repeat('a', 1000));

        $this->assertSame(FeedResponseReader::EXCERPT_LENGTH + 3, mb_strlen($out));
    }

    public function testFirstImageIsFoundAndValidated(): void {
        $this->assertSame(
            'https://example.com/a.png',
            $this->reader->firstImageIn('<p>x</p><img src="https://example.com/a.png">')
        );
    }

    /**
     * The URL check is what stops a relative or javascript: source becoming an
     * image URL the proxy is then asked to sign.
     */
    public function testANonUrlImageSourceIsRefused(): void {
        $this->assertNull($this->reader->firstImageIn('<img src="/relative/a.png">'));
        $this->assertNull($this->reader->firstImageIn('<img src="javascript:alert(1)">'));
        $this->assertNull($this->reader->firstImageIn(''));
        $this->assertNull($this->reader->firstImageIn('<p>geen afbeelding</p>'));
    }

    public function testDatesNormaliseToIso8601(): void {
        $this->assertStringStartsWith('2026-03-05', $this->reader->normaliseDate('2026-03-05T12:00:00Z'));
        $this->assertStringStartsWith('2026-03-05', $this->reader->normaliseDate('5 March 2026'));
    }

    /** An unparseable date must not make the item disappear. */
    public function testAnUnparseableDateFallsBackToNowRatherThanFailing(): void {
        foreach (['', 'volstrekte onzin'] as $input) {
            $out = $this->reader->normaliseDate($input);
            $this->assertNotSame('', $out);
            $this->assertNotFalse(strtotime($out), "'$input' must still yield a usable date");
        }
    }

    /**
     * The regression that matters here: LMS feeds are personalised, so two users
     * must never share a cache entry.
     */
    public function testPersonalisedFeedsGetPerUserCacheKeys(): void {
        $config = ['url' => 'https://lms.example/api'];

        $a = $this->reader->cacheKey('moodle', $config, 'alice');
        $b = $this->reader->cacheKey('moodle', $config, 'bob');
        $anon = $this->reader->cacheKey('moodle', $config, null);

        $this->assertNotSame($a, $b, "one student's deadlines must not be served to another");
        $this->assertNotSame($a, $anon);
    }

    /** RSS is the same for everyone, so it is cached once. */
    public function testRssIsCachedOnceForEveryone(): void {
        $config = ['url' => 'https://example.com/feed.xml'];

        $this->assertSame(
            $this->reader->cacheKey('rss', $config, 'alice'),
            $this->reader->cacheKey('rss', $config, 'bob')
        );
    }

    public function testADifferentConfigIsADifferentKey(): void {
        $this->assertNotSame(
            $this->reader->cacheKey('rss', ['url' => 'https://a.example/f.xml']),
            $this->reader->cacheKey('rss', ['url' => 'https://b.example/f.xml'])
        );
    }
}
