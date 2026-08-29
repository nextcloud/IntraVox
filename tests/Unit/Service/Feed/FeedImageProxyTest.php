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
}
