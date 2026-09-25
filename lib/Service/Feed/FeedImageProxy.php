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
 * The signature is independent of which route serves the image: the two
 * endpoints differ only in who may reach them, not in what they verify. See
 * setShareToken().
 *
 * hash_equals(), not ===, because comparing a signature byte by byte with
 * early exit leaks its content through timing.
 */
class FeedImageProxy {
    /** @var null|callable():array<array<string,mixed>> */
    private $connectionLister = null;

    /** @var null|callable(string,?string):?array<string,mixed> */
    private $tokenResolver = null;

    public function __construct(
        private IConfig $config,
    ) {
    }

    /**
     * Wire up the lookups needed to re-attach a Moodle token at fetch time.
     *
     * Passed as callables rather than services because FeedReaderService owns
     * both, and injecting it here would close a dependency cycle. Left unset
     * (in tests, and on any path that never proxies a Moodle file) the proxy
     * simply fetches URLs unchanged.
     *
     * @param callable():array<array<string,mixed>> $connectionLister
     * @param callable(string,?string):?array<string,mixed> $tokenResolver
     */
    public function setMoodleTokenLookup(callable $connectionLister, callable $tokenResolver): void {
        $this->connectionLister = $connectionLister;
        $this->tokenResolver = $tokenResolver;
    }

    /**
     * Re-attach the Moodle webservice token when the proxy fetches a file.
     *
     * Counterpart to FeedReaderService::moodleFileUrl(), which deliberately
     * leaves the token out: the URL it produces is signed and handed to the
     * browser in the `url=` query parameter, so a token in that string would be
     * readable by every visitor of the page, in the page source, in browser
     * history and in any access log along the way. For a Moodle connection
     * configured with an admin webservice token that is a leak of administrator
     * rights on the LMS (IV-04).
     *
     * Adding it here means it only ever goes onto the outgoing request.
     *
     * Two guards keep this from becoming a token oracle:
     *
     *  - The URL must start with the configured baseUrl of an ACTIVE Moodle
     *    connection and hit its webservice endpoint. A signed URL for any other
     *    host is fetched unchanged, so the proxy cannot be steered into sending
     *    a token somewhere else.
     *  - The token is resolved through the same path the feed itself uses, with
     *    the requesting $userId. That keeps the existing rule that an anonymous
     *    caller (public share) never receives the admin token: the resolver
     *    returns null and the image is fetched without one.
     */
    public function attachMoodleToken(string $url, ?string $userId): string {
        if ($this->connectionLister === null || $this->tokenResolver === null) {
            return $url;
        }
        if (!str_contains($url, '/webservice/pluginfile.php/')) {
            return $url;
        }

        foreach (($this->connectionLister)() as $connection) {
            if (($connection['type'] ?? '') !== 'moodle' || ($connection['active'] ?? true) === false) {
                continue;
            }

            $baseUrl = rtrim((string)($connection['baseUrl'] ?? ''), '/');
            if ($baseUrl === '' || !str_starts_with($url, $baseUrl . '/')) {
                continue;
            }

            $resolved = ($this->tokenResolver)((string)($connection['id'] ?? ''), $userId);
            $token = (string)($resolved['token'] ?? '');
            if ($token === '') {
                return $url;
            }

            return $url . (str_contains($url, '?') ? '&' : '?') . 'token=' . urlencode($token);
        }

        return $url;
    }

    /** Share token to sign image URLs for, when rendering a public share. */
    private ?string $shareToken = null;

    /**
     * Point generated URLs at the public share route instead of the app route.
     *
     * `/apps/intravox/api/feed/image` is #[NoAdminRequired] — reachable only by
     * a logged-in user. An anonymous visitor on a shared page therefore got a
     * 401 for every image and saw alt text where the pictures should be, while
     * the items themselves loaded fine (those already went through the share
     * route). The share endpoint `/api/share/{token}/feed/image` is
     * #[PublicPage] and verifies the same HMAC, so only the path differs.
     *
     * Set per request by the share controller before the feed is fetched, and
     * never on the logged-in path — a token here would hand out share URLs to
     * users who are reading the page normally.
     */
    public function setShareToken(?string $token): void {
        $this->shareToken = ($token !== null && $token !== '') ? $token : null;
    }

    /** The share context, so the feed cache key can reflect it. */
    public function getShareToken(): ?string {
        return $this->shareToken;
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
        // Same signature either way; only the route differs in who may reach it.
        $path = $this->shareToken !== null
            ? '/apps/intravox/api/share/' . rawurlencode($this->shareToken) . '/feed/image'
            : '/apps/intravox/api/feed/image';
        return $webRoot . $path . '?url=' . urlencode($imageUrl) . '&sig=' . $sig;
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
