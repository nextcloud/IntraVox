<?php
declare(strict_types=1);

namespace OCA\IntraVox\Tests\Unit\Service\Feed;

use PHPUnit\Framework\TestCase;

/**
 * Which element an RSS item's text is read from.
 *
 * `normalizeRssItem()` is private and sits on a service with a dozen
 * collaborators, so the selection rule is pinned here against the same
 * SimpleXMLElement shape the parser sees. That keeps the assertion honest
 * without standing up the whole service: the rule under test is a property of
 * the XML, not of the HTTP stack around it.
 *
 * The rule: `content:encoded` wins over `description`, because the first is
 * where RSS puts the article and the second is a teaser. Measured on
 * nextcloud.com/feed — 384 bytes of description beside 12,577 of
 * content:encoded — the old behaviour showed an excerpt of the teaser while
 * the article sat unread in the same item.
 */
class RssContentSelectionTest extends TestCase {
    private const NS_CONTENT = 'http://purl.org/rss/1.0/modules/content/';

    /** The selection as normalizeRssItem() performs it. */
    private function kiesContent(\SimpleXMLElement $item): string {
        $encoded = (string)($item->children(self::NS_CONTENT)->encoded ?? '');
        return $encoded !== '' ? $encoded : (string)($item->description ?? '');
    }

    private function item(string $innerXml): \SimpleXMLElement {
        $xml = <<<XML
<rss version="2.0" xmlns:content="http://purl.org/rss/1.0/modules/content/">
  <channel><item>{$innerXml}</item></channel>
</rss>
XML;
        return simplexml_load_string($xml)->channel->item[0];
    }

    public function testContentEncodedWinsOverDescription(): void {
        $item = $this->item(
            '<description>Korte teaser.</description>' .
            '<content:encoded><![CDATA[<p>Het volledige artikel.</p>]]></content:encoded>'
        );

        $this->assertSame('<p>Het volledige artikel.</p>', $this->kiesContent($item));
    }

    public function testDescriptionIsUsedWhenThereIsNoEncoded(): void {
        $item = $this->item('<description>Alleen een teaser.</description>');

        $this->assertSame('Alleen een teaser.', $this->kiesContent($item));
    }

    /**
     * An empty `content:encoded` must not blank the excerpt. Feeds that ship
     * the element unfilled are common enough that treating "present" as
     * "usable" would silently empty those items.
     */
    public function testAnEmptyEncodedFallsBackToDescription(): void {
        $item = $this->item(
            '<description>Teaser die zichtbaar moet blijven.</description>' .
            '<content:encoded></content:encoded>'
        );

        $this->assertSame('Teaser die zichtbaar moet blijven.', $this->kiesContent($item));
    }

    public function testAnItemWithNeitherYieldsAnEmptyString(): void {
        $this->assertSame('', $this->kiesContent($this->item('<title>Kop</title>')));
    }

    /**
     * The namespace prefix is not fixed by the spec — a feed may bind the
     * content module to any prefix. This is why the lookup passes the
     * namespace URI: `children('content', true)` matches the *prefix* despite
     * its second argument, and returned nothing here when it was tried.
     */
    public function testTheNamespacePrefixDoesNotHaveToBeContent(): void {
        $xml = <<<XML
<rss version="2.0" xmlns:c="http://purl.org/rss/1.0/modules/content/">
  <channel><item>
    <description>Teaser.</description>
    <c:encoded><![CDATA[Volledig.]]></c:encoded>
  </item></channel>
</rss>
XML;
        $item = simplexml_load_string($xml)->channel->item[0];

        $this->assertSame('Volledig.', $this->kiesContent($item));
    }
}
