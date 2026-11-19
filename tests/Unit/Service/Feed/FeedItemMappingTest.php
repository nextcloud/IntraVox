<?php
declare(strict_types=1);

namespace OCA\IntraVox\Tests\Unit\Service\Feed;

use OCA\IntraVox\Service\Feed\FeedImageProxy;
use OCA\IntraVox\Service\Feed\FeedResponseReader;
use OCA\IntraVox\Service\FeedReaderService;
use PHPUnit\Framework\TestCase;

/**
 * Where a mapped field becomes a feed item.
 *
 * The URL template is exercised against the expander in FeedResponseReaderTest;
 * what is checked here is the seam above it — which of the two URL mappings wins,
 * and what the item looks like when the template cannot be filled. Both are
 * decisions mapJsonResponse makes, and neither is visible from the expander.
 *
 * Two collaborators are injected and the other 13 constructor arguments stay
 * unbuilt: mapJsonResponse touches nothing else. The image proxy arrives without
 * its own constructor because none of these items carry an image, so it is never
 * asked to sign anything.
 */
class FeedItemMappingTest extends TestCase {
    private const BASE = 'https://cloud.example.com';

    /** @param array<string,mixed> $mapping */
    private function map(array $data, array $mapping): array {
        $service = (new \ReflectionClass(FeedReaderService::class))->newInstanceWithoutConstructor();
        (new \ReflectionProperty(FeedReaderService::class, 'responses'))
            ->setValue($service, new FeedResponseReader());
        (new \ReflectionProperty(FeedReaderService::class, 'imageProxy'))
            ->setValue($service, (new \ReflectionClass(FeedImageProxy::class))->newInstanceWithoutConstructor());

        return (new \ReflectionMethod(FeedReaderService::class, 'mapJsonResponse'))
            ->invoke($service, $data, $mapping, self::BASE, 'Talk');
    }

    public function testATemplateWinsOverTheUrlFieldAndIsMadeAbsolute(): void {
        $items = $this->map(
            [['subject' => 'Standup', 'token' => 'a1b2c3d4', 'id' => 42, 'self' => '/api/v3/chat/1']],
            ['items' => '', 'title' => 'subject', 'url' => 'self', 'urlTemplate' => '/call/{token}#message_{id}']
        );

        $this->assertSame(self::BASE . '/call/a1b2c3d4#message_42', $items[0]['url']);
    }

    /** Without a template the field mapping keeps working exactly as before. */
    public function testWithoutATemplateTheUrlFieldStillDecides(): void {
        $items = $this->map(
            [['subject' => 'Standup', 'self' => '/api/v3/chat/1']],
            ['items' => '', 'title' => 'subject', 'url' => 'self', 'urlTemplate' => '']
        );

        $this->assertSame(self::BASE . '/api/v3/chat/1', $items[0]['url']);
    }

    /**
     * An item whose link cannot be composed keeps its place in the feed. A Talk
     * message is worth reading whether or not it can be linked to, so the item is
     * emitted without a URL rather than dropped — FeedItem.vue renders it as
     * something other than an anchor.
     */
    public function testAnItemSurvivesATemplateItCannotFill(): void {
        $items = $this->map(
            [['subject' => 'Standup', 'token' => 'a1b2c3d4']],
            ['items' => '', 'title' => 'subject', 'urlTemplate' => '/call/{token}#message_{id}']
        );

        $this->assertCount(1, $items);
        $this->assertSame('Standup', $items[0]['title']);
        $this->assertSame('', $items[0]['url']);
    }
}
