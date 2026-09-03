<?php
declare(strict_types=1);

namespace OCA\IntraVox\Tests\Unit\Service;

use OCA\IntraVox\Service\PageService;
use PHPUnit\Framework\TestCase;

/**
 * Pins preserveVideoOriginalUrls() and its helpers (cluster B) before they move
 * into Sanitize/VideoOriginalUrlPreserver in Phase 5. These are pure array
 * transformations with no collaborators, so they are driven directly via
 * reflection on a constructor-less PageService — the whole point is that the
 * extracted class must reproduce this transform byte-for-byte.
 *
 * The behaviour: when a page is re-saved, a video widget that lost its src/
 * originalSrc (e.g. because the domain whitelist changed) has its originalSrc
 * carried over from the previously-saved version, so the URL is never lost.
 */
class PageVideoWidgetPreservationTest extends TestCase {

    private PageService $svc;
    private \ReflectionMethod $preserve;

    protected function setUp(): void {
        $this->svc = new class extends PageService {
            public function __construct() {
            }
        };
        // Since PHP 8.1 reflection reaches private methods without setAccessible().
        $this->preserve = new \ReflectionMethod(PageService::class, 'preserveVideoOriginalUrls');
    }

    /** @param array $new @param array $existing */
    private function preserveUrls(array $new, array $existing): array {
        return $this->preserve->invoke($this->svc, $new, $existing);
    }

    private function videoWidget(string $id, array $extra = []): array {
        return array_merge(['type' => 'video', 'id' => $id], $extra);
    }

    private function pageWith(array $rowWidgets = [], array $sideLeft = [], array $sideRight = [], array $header = []): array {
        return [
            'layout' => [
                'rows' => [['widgets' => $rowWidgets]],
                'sideColumns' => [
                    'left' => ['widgets' => $sideLeft],
                    'right' => ['widgets' => $sideRight],
                ],
                'headerRow' => ['widgets' => $header],
            ],
        ];
    }

    public function testOriginalSrcIsCarriedOverWhenTheNewWidgetLostIt(): void {
        $existing = $this->pageWith([
            $this->videoWidget('v1', ['originalSrc' => 'https://youtube.com/watch?v=abc', 'src' => 'https://youtube.com/embed/abc']),
        ]);
        $new = $this->pageWith([
            $this->videoWidget('v1'), // src and originalSrc both gone
        ]);

        $result = $this->preserveUrls($new, $existing);

        $this->assertSame(
            'https://youtube.com/watch?v=abc',
            $result['layout']['rows'][0]['widgets'][0]['originalSrc'],
            'the previous originalSrc is preserved so the URL is not lost'
        );
    }

    public function testFallsBackToExistingSrcWhenExistingHasNoOriginalSrc(): void {
        $existing = $this->pageWith([
            $this->videoWidget('v1', ['src' => 'https://vimeo.com/123']),
        ]);
        $new = $this->pageWith([$this->videoWidget('v1')]);

        $result = $this->preserveUrls($new, $existing);

        $this->assertSame(
            'https://vimeo.com/123',
            $result['layout']['rows'][0]['widgets'][0]['originalSrc'],
            'existing src is used as originalSrc when the existing widget had none'
        );
    }

    public function testAnExistingNewOriginalSrcIsNotOverwritten(): void {
        $existing = $this->pageWith([
            $this->videoWidget('v1', ['originalSrc' => 'https://old.example/vid']),
        ]);
        $new = $this->pageWith([
            $this->videoWidget('v1', ['originalSrc' => 'https://new.example/vid']),
        ]);

        $result = $this->preserveUrls($new, $existing);

        $this->assertSame(
            'https://new.example/vid',
            $result['layout']['rows'][0]['widgets'][0]['originalSrc'],
            'a new originalSrc that is already present must be kept as-is'
        );
    }

    public function testLocalVideosAreSkipped(): void {
        $existing = $this->pageWith([
            $this->videoWidget('v1', ['provider' => 'local', 'originalSrc' => 'should-not-copy']),
        ]);
        $new = $this->pageWith([
            $this->videoWidget('v1', ['provider' => 'local']),
        ]);

        $result = $this->preserveUrls($new, $existing);

        $this->assertArrayNotHasKey(
            'originalSrc',
            $result['layout']['rows'][0]['widgets'][0],
            'local videos have no originalSrc and must be left untouched'
        );
    }

    public function testPreservationAppliesToAllFourLayoutAreas(): void {
        $existing = $this->pageWith(
            [$this->videoWidget('r', ['originalSrc' => 'row-url'])],
            [$this->videoWidget('l', ['originalSrc' => 'left-url'])],
            [$this->videoWidget('rt', ['originalSrc' => 'right-url'])],
            [$this->videoWidget('h', ['originalSrc' => 'header-url'])],
        );
        $new = $this->pageWith(
            [$this->videoWidget('r')],
            [$this->videoWidget('l')],
            [$this->videoWidget('rt')],
            [$this->videoWidget('h')],
        );

        $result = $this->preserveUrls($new, $existing);

        $this->assertSame('row-url', $result['layout']['rows'][0]['widgets'][0]['originalSrc']);
        $this->assertSame('left-url', $result['layout']['sideColumns']['left']['widgets'][0]['originalSrc']);
        $this->assertSame('right-url', $result['layout']['sideColumns']['right']['widgets'][0]['originalSrc']);
        $this->assertSame('header-url', $result['layout']['headerRow']['widgets'][0]['originalSrc']);
    }

    public function testNonVideoWidgetsAreUntouched(): void {
        $existing = $this->pageWith([['type' => 'text', 'id' => 't1', 'content' => 'hi']]);
        $new = $this->pageWith([['type' => 'text', 'id' => 't1', 'content' => 'hi']]);

        $result = $this->preserveUrls($new, $existing);

        $this->assertSame(
            ['type' => 'text', 'id' => 't1', 'content' => 'hi'],
            $result['layout']['rows'][0]['widgets'][0],
            'a non-video widget passes through unchanged'
        );
    }
}
