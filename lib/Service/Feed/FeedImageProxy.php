<?php
declare(strict_types=1);

namespace OCA\IntraVox\Service\Feed;

use OCP\IConfig;

/**
 * Signing the image-proxy URLs that feed items point at.
 *
 * Extracted from FeedReaderService (service split, fase 2). A feed item may
 * carry an image on a third-party host; rendering it directly would leak the
 * viewer's IP and referrer to that host, so IntraVox proxies it. The proxy
 * endpoint is #[PublicPage], which is exactly why the URL is signed: without
 * a signature it would be an open redirect-and-fetch for anyone.
 *
 * Two details are load-bearing and easy to lose in a rewrite:
 *
 *  - The key rotates DAILY (intdiv(time(), 86400) is part of the HMAC input),
 *    so a signature harvested from a page cannot be replayed indefinitely.
 *  - Verification therefore accepts yesterday's signature too. Without that,
 *    every image on a page open across midnight breaks until reload.
 *
 * hash_equals(), not ===, because comparing a signature byte by byte with
 * early exit leaks its content through timing.
 */
class FeedImageProxy {
    public function __construct(
        private IConfig $config,
    ) {
    }

    /**
     * The instance web root, as a seam.
     *
     * \OC is the server's internal namespace and does not exist in a unit
     * test, so reading it inline would make this class untestable for the
     * sake of one string. Overridden in tests; unchanged at runtime.
     */
    protected function webRoot(): string {
        return \OC::$WEBROOT ?: '';
    }

    /**
     * Generate a signed proxy URL for an external image.
     * Uses HMAC-SHA256 to prevent the proxy from being used as an open relay.
     */
    public function signImageUrl(string $imageUrl): string {
        $day = (string)intdiv(time(), 86400);
        $sig = hash_hmac('sha256', $imageUrl . '|' . $day, $this->getImageProxySecret());
        $webRoot = $this->webRoot();
        return $webRoot . '/apps/intravox/api/feed/image?url=' . urlencode($imageUrl) . '&sig=' . $sig;
    }
    /**
     * Verify the HMAC signature on a proxied image URL.
     * Accepts signatures from today and yesterday (grace window for day boundary).
     */
    public function verifyImageSignature(string $url, string $sig): bool {
        $today = (string)intdiv(time(), 86400);
        $yesterday = (string)(intdiv(time(), 86400) - 1);

        $expectedToday = hash_hmac('sha256', $url . '|' . $today, $this->getImageProxySecret());
        if (hash_equals($expectedToday, $sig)) {
            return true;
        }

        $expectedYesterday = hash_hmac('sha256', $url . '|' . $yesterday, $this->getImageProxySecret());
        return hash_equals($expectedYesterday, $sig);
    }
    /**
     * Sign an image URL for proxying, or return null if invalid.
     */
    public function proxyImageUrl(?string $url): ?string {
        if ($url === null || !filter_var($url, FILTER_VALIDATE_URL)) {
            return null;
        }
        return $this->signImageUrl($url);
    }
    private function getImageProxySecret(): string {
        return hash('sha256', 'intravox-img-' . $this->config->getSystemValueString('secret', ''));
    }
}
