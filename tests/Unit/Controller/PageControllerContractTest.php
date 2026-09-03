<?php
declare(strict_types=1);

namespace OCA\IntraVox\Tests\Unit\Controller;

use PHPUnit\Framework\TestCase;

/**
 * PageController is the HTML-shell render controller (TemplateResponse /
 * RedirectResponse), not a JSON API. Its share endpoints are security-sensitive,
 * so before Phase 6 slims it down (RendersAppShell trait, token validator moved
 * to PublicShareService, constructor promotion) this locks the parts that a unit
 * test cannot easily drive: the per-route security attributes, the byte-identical
 * share-token validator it shares with PublicShareController, and the webpack
 * asset triple every render path must emit.
 *
 * These are lexical/reflection assertions over the source, so they need no
 * controller instantiation (which would require a large stub surface).
 */
class PageControllerContractTest extends TestCase {

    private string $pageSrc;
    private string $publicShareSrc;

    protected function setUp(): void {
        $this->pageSrc = file_get_contents(__DIR__ . '/../../../lib/Controller/PageController.php');
        $this->publicShareSrc = file_get_contents(__DIR__ . '/../../../lib/Controller/PublicShareController.php');
    }

    public function testTheFiveRenderEndpointsExistAndArePublicWhereExpected(): void {
        $ref = new \ReflectionClass(\OCA\IntraVox\Controller\PageController::class);
        foreach (['index', 'shareAccess', 'shareAuthenticate', 'show', 'showByUniqueId'] as $method) {
            $this->assertTrue($ref->hasMethod($method), "PageController must keep the $method endpoint");
            $this->assertTrue($ref->getMethod($method)->isPublic(), "$method is a routed endpoint");
        }
    }

    public function testShareEndpointsCarryTheirSecurityAttributes(): void {
        // The anonymous share landing/auth must stay public, rate-limited and
        // brute-force protected — dropping any of these is a security regression.
        $this->assertShareEndpointBlock('shareAccess');
        $this->assertShareEndpointBlock('shareAuthenticate');
    }

    private function assertShareEndpointBlock(string $method): void {
        // Grab the ~15 lines of attributes/signature preceding the method.
        $pos = strpos($this->pageSrc, "function $method(");
        $this->assertNotFalse($pos, "$method must exist");
        $block = substr($this->pageSrc, max(0, $pos - 600), 600);

        $this->assertStringContainsString('#[PublicPage]', $block, "$method must be a PublicPage");
        $this->assertStringContainsString('#[AnonRateLimit', $block, "$method must be rate-limited");
        $this->assertStringContainsString('#[BruteForceProtection', $block, "$method must be brute-force protected");
    }

    public function testShareTokenValidatorIsByteIdenticalToPublicShareController(): void {
        $page = $this->extractMethodBody($this->pageSrc, 'isValidShareTokenFormat');
        $share = $this->extractMethodBody($this->publicShareSrc, 'isValidShareTokenFormat');

        $this->assertNotSame('', $page, 'PageController must have the token validator');
        $this->assertNotSame('', $share, 'PublicShareController must have the token validator');
        $this->assertSame(
            $share,
            $page,
            'the two validators are byte-identical duplicates; Phase 6 consolidates them'
        );
    }

    public function testEveryRenderPathEmitsTheWebpackAssetTriple(): void {
        // The three bundles (vendors/shared/main) must load on every render path;
        // a path that emits only some yields a half-initialised SPA.
        $this->assertSame(
            substr_count($this->pageSrc, 'intravox-vendors'),
            substr_count($this->pageSrc, 'intravox-shared'),
            'vendors and shared bundles must be emitted the same number of times'
        );
        $this->assertSame(
            substr_count($this->pageSrc, 'intravox-shared'),
            substr_count($this->pageSrc, 'intravox-main'),
            'shared and main bundles must be emitted the same number of times'
        );
        $this->assertGreaterThanOrEqual(
            4,
            substr_count($this->pageSrc, 'intravox-main'),
            'the triple appears on each render path (index/show/showByUniqueId/shareAccess + notfound)'
        );
    }

    /** Extract just the body between the first { and its matching close for a named method. */
    private function extractMethodBody(string $src, string $method): string {
        $sig = strpos($src, "function $method(");
        if ($sig === false) {
            return '';
        }
        $open = strpos($src, '{', $sig);
        if ($open === false) {
            return '';
        }
        $depth = 0;
        for ($i = $open; $i < strlen($src); $i++) {
            if ($src[$i] === '{') {
                $depth++;
            } elseif ($src[$i] === '}') {
                $depth--;
                if ($depth === 0) {
                    return trim(substr($src, $open + 1, $i - $open - 1));
                }
            }
        }
        return '';
    }
}
