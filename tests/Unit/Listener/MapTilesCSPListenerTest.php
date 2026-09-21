<?php
declare(strict_types=1);

namespace OCA\IntraVox\Tests\Unit\Listener;

use OCA\IntraVox\Listener\MapTilesCSPListener;
use OCP\EventDispatcher\Event;
use OCP\IConfig;
use OCP\Security\CSP\AddContentSecurityPolicyEvent;
use PHPUnit\Framework\TestCase;

class MapTilesCSPListenerTest extends TestCase {
    private IConfig $config;

    protected function setUp(): void {
        parent::setUp();
        $this->config = $this->createMock(IConfig::class);
    }

    /**
     * @param array<string,string> $values keyed by appconfig key
     */
    private function listenerWith(array $values): MapTilesCSPListener {
        $this->config->method('getAppValue')->willReturnCallback(
            static fn (string $app, string $key, string $default = '') => $values[$key] ?? $default
        );
        return new MapTilesCSPListener($this->config);
    }

    /** @return list<string> */
    private function grantedDomains(MapTilesCSPListener $listener): array {
        $event = new AddContentSecurityPolicyEvent();
        $listener->handle($event);

        $domains = [];
        foreach ($event->getPolicies() as $policy) {
            foreach ($policy->getAllowedImageDomains() as $domain) {
                $domains[] = $domain;
            }
        }
        return $domains;
    }

    /**
     * The bug this listener exists for: the default tile URL has no subdomain,
     * so the wildcard the photos app contributes ('https://*.tile.openstreetmap.org')
     * never matched it and the basemap stayed blank.
     */
    public function testDefaultTileUrlGrantsBareOpenStreetMapHost(): void {
        $listener = $this->listenerWith([]);

        $this->assertSame(
            ['https://tile.openstreetmap.org'],
            $this->grantedDomains($listener)
        );
    }

    public function testOriginIsDerivedFromConfiguredTileServer(): void {
        $listener = $this->listenerWith([
            'photostory.tiles.url' => 'https://tiles.example.org/osm/{z}/{x}/{y}.png',
        ]);

        $this->assertSame(
            ['https://tiles.example.org'],
            $this->grantedDomains($listener)
        );
    }

    public function testNonDefaultPortIsPreserved(): void {
        $listener = $this->listenerWith([
            'photostory.tiles.url' => 'http://tiles.internal:8080/{z}/{x}/{y}.png',
        ]);

        $this->assertSame(
            ['http://tiles.internal:8080'],
            $this->grantedDomains($listener)
        );
    }

    /** @return list<string> */
    private function grantedFrameDomains(MapTilesCSPListener $listener): array {
        $event = new AddContentSecurityPolicyEvent();
        $listener->handle($event);

        $domains = [];
        foreach ($event->getPolicies() as $policy) {
            foreach ($policy->getAllowedFrameDomains() as $domain) {
                $domains[] = $domain;
            }
        }
        return $domains;
    }

    /**
     * PhotoLightbox's mini-map is an openstreetmap.org iframe, a different host
     * than the tile server and therefore blocked by frame-src on its own.
     */
    public function testLightboxMiniMapFrameIsAllowed(): void {
        $listener = $this->listenerWith([]);

        $this->assertSame(
            ['https://www.openstreetmap.org'],
            $this->grantedFrameDomains($listener)
        );
    }

    /**
     * A self-hosted tile server must not change the frame grant: the embed URL
     * is hardcoded client-side and still points at openstreetmap.org.
     */
    public function testFrameGrantIsIndependentOfConfiguredTileServer(): void {
        $listener = $this->listenerWith([
            'photostory.tiles.url' => 'https://tiles.example.org/{z}/{x}/{y}.png',
        ]);

        $this->assertSame(
            ['https://www.openstreetmap.org'],
            $this->grantedFrameDomains($listener)
        );
    }

    public function testDisabledMapAddsNoFramePolicy(): void {
        $listener = $this->listenerWith([
            'photostory.map.enabled' => 'no',
        ]);

        $this->assertSame([], $this->grantedFrameDomains($listener));
    }

    public function testDisabledMapAddsNoPolicy(): void {
        $listener = $this->listenerWith([
            'photostory.map.enabled' => 'no',
        ]);

        $this->assertSame([], $this->grantedDomains($listener));
    }

    /**
     * A malformed or relative URL must add no directive at all rather than an
     * unparseable source that would weaken the merged img-src list.
     *
     * @dataProvider unusableTileUrlProvider
     */
    public function testUnusableTileUrlAddsNoPolicy(string $url): void {
        $listener = $this->listenerWith(['photostory.tiles.url' => $url]);

        $this->assertSame([], $this->grantedDomains($listener));
    }

    /** @return array<string,array{string}> */
    public static function unusableTileUrlProvider(): array {
        return [
            'empty' => [''],
            'whitespace only' => ['   '],
            'relative path' => ['/local/tiles/{z}/{x}/{y}.png'],
            'no scheme' => ['tiles.example.org/{z}/{x}/{y}.png'],
            'javascript scheme' => ['javascript:alert(1)'],
            'data scheme' => ['data:image/png;base64,AAAA'],
        ];
    }

    public function testIgnoresUnrelatedEvents(): void {
        $listener = $this->listenerWith([]);
        $event = new class extends Event {};

        $listener->handle($event);

        $this->addToAssertionCount(1);
    }
}
