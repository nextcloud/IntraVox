<?php
declare(strict_types=1);

namespace OCA\IntraVox\Tests\Unit\Service\Feed;

use OCA\IntraVox\Service\Feed\FeedImageProxy;
use OCP\IConfig;
use PHPUnit\Framework\TestCase;

/**
 * Signing the image-proxy URLs.
 *
 * The proxy endpoint is #[PublicPage]. Without a valid signature it would be an
 * open fetch-anything for whoever finds the URL, so these assertions are about
 * the signature actually being required — not about convenience.
 */
class FeedImageProxyTest extends TestCase {
    private FeedImageProxy $proxy;

    protected function setUp(): void {
        parent::setUp();
        $config = $this->createMock(IConfig::class);
        $config->method('getSystemValueString')->willReturn('instance-secret');
        // Anonymous subclass: \OC does not exist in a unit test, and the web
        // root is not what these assertions are about.
        $this->proxy = new class($config) extends FeedImageProxy {
            protected function webRoot(): string {
                return '';
            }
        };
    }

    private function signatureOf(string $url): string {
        parse_str(parse_url($this->proxy->signImageUrl($url), PHP_URL_QUERY) ?: '', $q);
        return $q['sig'] ?? '';
    }

    public function testASignedUrlVerifies(): void {
        $url = 'https://example.com/plaatje.png';

        $this->assertTrue($this->proxy->verifyImageSignature($url, $this->signatureOf($url)));
    }

    /** The signature is over the URL, so it cannot be moved to another one. */
    public function testASignatureDoesNotTransferToAnotherUrl(): void {
        $sig = $this->signatureOf('https://example.com/plaatje.png');

        $this->assertFalse(
            $this->proxy->verifyImageSignature('https://evil.example/secret.png', $sig),
            'a harvested signature must not authorise a different fetch'
        );
    }

    public function testGarbageAndEmptySignaturesAreRefused(): void {
        $url = 'https://example.com/plaatje.png';

        $this->assertFalse($this->proxy->verifyImageSignature($url, ''));
        $this->assertFalse($this->proxy->verifyImageSignature($url, 'onzin'));
        $this->assertFalse($this->proxy->verifyImageSignature($url, str_repeat('a', 64)));
    }

    /**
     * The key rotates daily, and verification accepts yesterday's signature.
     * Without that grace window every image on a page left open across midnight
     * breaks until reload; with a longer window a harvested URL would live too
     * long.
     */
    public function testYesterdaysSignatureStillVerifiesButTheDayBeforeDoesNot(): void {
        $url = 'https://example.com/plaatje.png';
        $secret = hash('sha256', 'intravox-img-instance-secret');
        $today = intdiv(time(), 86400);

        $yesterday = hash_hmac('sha256', $url . '|' . ($today - 1), $secret);
        $twoDaysAgo = hash_hmac('sha256', $url . '|' . ($today - 2), $secret);

        $this->assertTrue($this->proxy->verifyImageSignature($url, $yesterday));
        $this->assertFalse(
            $this->proxy->verifyImageSignature($url, $twoDaysAgo),
            'the grace window is one day, not indefinite'
        );
    }

    /** A different instance secret must produce a different, non-verifying signature. */
    public function testASignatureFromAnotherInstanceDoesNotVerify(): void {
        $other = $this->createMock(IConfig::class);
        $other->method('getSystemValueString')->willReturn('een-ander-geheim');
        $otherProxy = new class($other) extends FeedImageProxy {
            protected function webRoot(): string {
                return '';
            }
        };

        $url = 'https://example.com/plaatje.png';
        parse_str(parse_url($otherProxy->signImageUrl($url), PHP_URL_QUERY) ?: '', $q);

        $this->assertFalse($this->proxy->verifyImageSignature($url, $q['sig'] ?? ''));
    }

    public function testTheSignedUrlCarriesBackTheOriginalUrl(): void {
        $url = 'https://example.com/pad?a=1&b=2';
        parse_str(parse_url($this->proxy->signImageUrl($url), PHP_URL_QUERY) ?: '', $q);

        $this->assertSame($url, $q['url'] ?? null, 'query parameters must survive encoding');
    }

    public function testOnlyRealUrlsGetProxied(): void {
        $this->assertNull($this->proxy->proxyImageUrl(null));
        $this->assertNull($this->proxy->proxyImageUrl('/relatief/pad.png'));
        $this->assertNull($this->proxy->proxyImageUrl('javascript:alert(1)'));
        $this->assertNotNull($this->proxy->proxyImageUrl('https://example.com/a.png'));
    }

    /**
     * The bug this guards: `/apps/intravox/api/feed/image` is #[NoAdminRequired],
     * so an anonymous visitor on a shared page got a 401 for every image and saw
     * alt text instead of pictures — while the items themselves loaded, because
     * those already went through the share route.
     */
    public function testShareContextSignsForThePublicRoute(): void {
        $url = 'https://example.com/plaatje.png';

        $this->assertStringContainsString(
            '/apps/intravox/api/feed/image?',
            $this->proxy->signImageUrl($url),
            'without a share context the app route is correct'
        );

        $this->proxy->setShareToken('PbPqtPRBF3Wdkfm');
        $this->assertStringContainsString(
            '/apps/intravox/api/share/PbPqtPRBF3Wdkfm/feed/image?',
            $this->proxy->signImageUrl($url),
            'on a share the URL must point at the #[PublicPage] route'
        );
    }

    /** Both routes verify the same HMAC; only reachability differs. */
    public function testTheSignatureIsTheSameOnBothRoutes(): void {
        $url = 'https://example.com/plaatje.png';

        parse_str(parse_url($this->proxy->signImageUrl($url), PHP_URL_QUERY) ?: '', $app);
        $this->proxy->setShareToken('tok123');
        parse_str(parse_url($this->proxy->signImageUrl($url), PHP_URL_QUERY) ?: '', $share);

        $this->assertSame($app['sig'] ?? 'a', $share['sig'] ?? 'b');
        $this->assertTrue($this->proxy->verifyImageSignature($url, $share['sig'] ?? ''));
    }

    /** An empty token is no token — it must not produce `/share//feed/image`. */
    public function testAnEmptyTokenFallsBackToTheAppRoute(): void {
        $this->proxy->setShareToken('');
        $this->assertStringContainsString('/apps/intravox/api/feed/image?', $this->proxy->signImageUrl('https://example.com/a.png'));

        $this->proxy->setShareToken('tok');
        $this->proxy->setShareToken(null);
        $this->assertStringContainsString('/apps/intravox/api/feed/image?', $this->proxy->signImageUrl('https://example.com/a.png'));
    }

    /** A token with URL-unsafe characters must not break out of the path. */
    public function testTheTokenIsEncodedIntoThePath(): void {
        $this->proxy->setShareToken('a/b?c');
        $this->assertStringContainsString('/api/share/a%2Fb%3Fc/feed/image?', $this->proxy->signImageUrl('https://example.com/a.png'));
    }

    /**
     * The share context is per-request state on a service the DI container
     * shares. Apache/mod_php builds a fresh container per request so it cannot
     * survive one, but the guarantee the code relies on is narrower and worth
     * pinning: setting a token must never be irreversible.
     *
     * If this ever regresses, a logged-in reader would be handed URLs carrying
     * someone else's share token — which is a working link to a page they may
     * not be entitled to see.
     */
    public function testTheShareContextCanAlwaysBeCleared(): void {
        $url = 'https://example.com/a.png';

        $this->proxy->setShareToken('geheim-token');
        $this->assertStringContainsString('/api/share/geheim-token/', $this->proxy->signImageUrl($url));

        $this->proxy->setShareToken(null);
        $this->assertStringNotContainsString('/api/share/', $this->proxy->signImageUrl($url),
            'a cleared context must not keep signing for the share route');
        $this->assertNull($this->proxy->getShareToken());
    }

    /**
     * The token ends up in a URL the browser requests. It must not be able to
     * carry a query string or fragment into the path and change which endpoint
     * is addressed.
     */
    public function testATokenCannotAlterTheRoute(): void {
        foreach (['../../admin', 'a?b=c', 'a#frag', 'a/b'] as $vies) {
            $this->proxy->setShareToken($vies);
            $pad = parse_url($this->proxy->signImageUrl('https://example.com/a.png'), PHP_URL_PATH);
            $this->assertStringEndsWith('/feed/image', $pad,
                "token {$vies} must not change the endpoint");
            $this->assertStringContainsString('/apps/intravox/api/share/', $pad);
        }
    }
}
