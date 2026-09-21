<?php
declare(strict_types=1);

namespace OCA\IntraVox\Tests\Unit\Service\Feed;

use PHPUnit\Framework\TestCase;

/**
 * Entities survive the trip into a feed exactly once.
 *
 * Page content is stored as markdown, and that markdown may already hold
 * entities: an editor who typed "&" can leave "&amp;" in the source. Running
 * htmlspecialchars() over that yields "&amp;amp;", the feed carries it verbatim
 * inside CDATA, and every reader renders a literal "&amp;" in the middle of a
 * sentence. Measured on the onboarding page: the stored source reads
 * "Feedback &amp; vragen", the feed shipped "Feedback &amp;amp; vragen".
 *
 * The rule under test is that encoding is idempotent — running it on already
 * encoded text must not encode it again. The logic is mirrored here rather than
 * reached through FeedService, which needs a groupfolder and a dozen
 * collaborators to construct; what matters is a property of the string.
 */
class FeedEntityEncodingTest extends TestCase {
    /** FeedService::escapeOnce(). */
    private function escapeOnce(string $text): string {
        $prev = null;
        while ($prev !== $text) {
            $prev = $text;
            $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        }

        return htmlspecialchars($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }

    /** The reported case. */
    public function testAlreadyEncodedTextIsNotEncodedAgain(): void {
        $this->assertSame(
            'Feedback &amp; vragen',
            $this->escapeOnce('Feedback &amp; vragen')
        );
    }

    public function testPlainTextIsStillEncoded(): void {
        $this->assertSame('Feedback &amp; vragen', $this->escapeOnce('Feedback & vragen'));
        $this->assertSame('&lt;script&gt;', $this->escapeOnce('<script>'));
    }

    /**
     * Running it twice must give the same answer as running it once —
     * the property that stops this recurring the next time content is
     * re-saved or re-exported.
     */
    public function testEncodingIsIdempotent(): void {
        foreach (['A & B', 'A &amp; B', '"quoted"', "it's", '<b>x</b>', '3 < 5 > 2'] as $in) {
            $een = $this->escapeOnce($in);
            $this->assertSame($een, $this->escapeOnce($een), "not idempotent for: $in");
        }
    }

    /**
     * Accents and emoji are not entities and must pass through untouched — the
     * feed is UTF-8, so encoding them would be noise that readers then have to
     * decode.
     */
    public function testNonAsciiSurvivesUnchanged(): void {
        foreach (['café', 'Grüße', 'naïef', '日本語', '🎉 feest', 'Łódź', 'Ⅻ'] as $in) {
            $this->assertSame($in, $this->escapeOnce($in), "mangled: $in");
        }
    }

    /** Quotes matter: a title lands in an alt= attribute elsewhere in the feed. */
    public function testQuotesAreEncoded(): void {
        $this->assertSame('&quot;Kop&quot;', $this->escapeOnce('"Kop"'));
        // &apos; rather than &#039;: that is what ENT_HTML5 emits, and readers
        // decode both. Asserting the real output beats asserting a guess.
        $this->assertSame('&apos;Kop&apos;', $this->escapeOnce("'Kop'"));
    }

    /** A numeric entity is an entity too, and must not be re-encoded. */
    public function testNumericEntitiesAreDecodedBeforeEncoding(): void {
        // &#38; is "&" — encoding it again would produce &amp;#38;
        $this->assertSame('&amp;', $this->escapeOnce('&#38;'));
        $this->assertSame('&lt;', $this->escapeOnce('&#60;'));
    }
}
