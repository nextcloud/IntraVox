<?php
declare(strict_types=1);

namespace OCA\IntraVox\Service\Feed;

/**
 * The bits every feed connector needs after the HTTP call returns.
 *
 * Extracted from FeedReaderService (service split, fase 2). That class holds six
 * connectors -- Moodle, Canvas, Brightspace, SharePoint, a generic REST client
 * and RSS/Atom -- which look independent but are not: all six call the same
 * handful of helpers for status checking, JSON size limits, excerpting and date
 * normalising. Pulling the connectors apart without pulling this out first would
 * mean injecting the same eight helpers into six classes.
 *
 * Everything here is stateless and free of Nextcloud dependencies, so it can be
 * tested against strings instead of against a live LMS. The two helpers that DO
 * need collaborators (the Accept-Language header, which reads a user preference,
 * and courseId validation, which logs) stay behind for now.
 *
 * @see FeedReaderService for the orchestration these serve.
 */
final class FeedResponseReader {
    /** Excerpt length; matches FeedReaderService::EXCERPT_LENGTH. */
    public const EXCERPT_LENGTH = 300;

    /**
     * Ceiling on a response body we will parse as JSON.
     *
     * A feed source is external and may be hostile or merely broken; without a
     * cap a single reply can exhaust memory for the whole request.
     */
    public const MAX_RESPONSE_SIZE = 10485760; // 10 MB

    /**
     * Turn a status code into a message a person can act on.
     *
     * The distinction matters more than it looks: 401 means the token is wrong,
     * 403 means the token is right but the account lacks access, and 429 means
     * try later. Collapsing them into "request failed" is what sends admins
     * hunting through logs.
     *
     * @param \OCP\Http\Client\IResponse|object $response anything with getStatusCode()
     * @throws \RuntimeException on any non-2xx status
     */
    public function assertSuccessful($response, string $context = ''): void {
        $status = $response->getStatusCode();
        if ($status >= 200 && $status < 300) {
            return;
        }
        $prefix = $context ? "$context: " : '';
        throw match (true) {
            $status === 401 => new \RuntimeException("{$prefix}Authentication failed (401)"),
            $status === 403 => new \RuntimeException("{$prefix}Access denied (403)"),
            $status === 404 => new \RuntimeException("{$prefix}Not found (404)"),
            $status === 429 => new \RuntimeException("{$prefix}Rate limited (429). Try again later."),
            $status >= 500 => new \RuntimeException("{$prefix}Server error ($status)"),
            default => new \RuntimeException("{$prefix}HTTP error ($status)"),
        };
    }

    /**
     * @param \OCP\Http\Client\IResponse|object $response anything with getBody()
     * @throws \RuntimeException when the body exceeds MAX_RESPONSE_SIZE
     */
    public function decodeJson($response): ?array {
        $body = $response->getBody();
        if (strlen($body) > self::MAX_RESPONSE_SIZE) {
            throw new \RuntimeException('API response too large (' . round(strlen($body) / 1024 / 1024, 1) . ' MB, limit ' . (self::MAX_RESPONSE_SIZE / 1024 / 1024) . ' MB)');
        }
        return json_decode($body, true);
    }

    /**
     * Strip a UTF-8 BOM and leading whitespace before the XML declaration.
     *
     * Both make the document invalid per the XML spec, so libxml rejects the
     * whole feed — but they are common in the wild. A single blank line after a
     * closing PHP tag in a WordPress theme or plugin prepends a newline to every
     * feed that site serves, and the site owner never notices: browsers and most
     * feed readers tolerate it. Without this the widget reports "Could not load
     * feed" for a feed that demonstrably has items.
     *
     * Measured on vn.nl/feed (Vrij Nederland): 106 KB of valid RSS behind one
     * leading space, parsing as false before and 10 items after.
     *
     * Only the prologue is touched. A body that is genuinely not XML — an HTML
     * error page, say — still fails to parse, which is what should happen.
     */
    public function stripXmlPrologueNoise(string $body): string {
        return preg_replace('/^(?:\xEF\xBB\xBF|[\s\x00])+(?=<)/', '', $body) ?? $body;
    }

    /**
     * Plain-text summary of a feed item's HTML body.
     *
     * Not to be confused with News\NewsContentExtractor::getExcerpt(), which
     * looks similar but walks IntraVox page widgets. This one takes raw HTML
     * from a third party; they are different inputs doing different jobs and
     * deliberately not shared.
     */
    public function excerpt(string $html): string {
        // Strip HTML tags and decode entities
        $text = html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = preg_replace('/\s+/', ' ', $text);
        $text = trim($text);

        if (mb_strlen($text) > self::EXCERPT_LENGTH) {
            $truncated = mb_substr($text, 0, self::EXCERPT_LENGTH);
            $lastSpace = mb_strrpos($truncated, ' ');
            // Only snap back to a word boundary if it does not cost most of the
            // excerpt; otherwise a single long token would truncate to nothing.
            if ($lastSpace !== false && $lastSpace > self::EXCERPT_LENGTH * 0.7) {
                $truncated = mb_substr($truncated, 0, $lastSpace);
            }
            $text = $truncated . '...';
        }

        return $text;
    }

    /**
     * First <img src> in a body, when it is a URL we can hand on.
     *
     * The FILTER_VALIDATE_URL check is what stops a relative or javascript:
     * source becoming an image URL the proxy is then asked to sign.
     *
     * A protocol-relative source (`//host/path`) is upgraded to https first.
     * FILTER_VALIDATE_URL rejects it, so without this the image is silently
     * dropped even though the feed shipped one. Measured on planet.kde.org:
     * two of 25 items (both from dvratil.cz, a Hugo site) lost their picture
     * this way while nine others genuinely carry no <img> at all — which is
     * exactly why it reads as "some items just have no image" rather than as
     * a bug. Hugo and WordPress both emit this form, so it is not one feed.
     *
     * Only the scheme is filled in, and only for `//`. Everything the check
     * refused before it still refuses: a relative path has no host to borrow
     * a scheme for, and `javascript:` never matches `//` in the first place.
     */
    public function firstImageIn(string $html): ?string {
        if (empty($html)) {
            return null;
        }
        if (preg_match('/<img[^>]+src=["\']([^"\']+)["\']/', $html, $matches)) {
            $src = html_entity_decode($matches[1], ENT_QUOTES | ENT_HTML5, 'UTF-8');
            if (str_starts_with($src, '//')) {
                $src = 'https:' . $src;
            }
            if (filter_var($src, FILTER_VALIDATE_URL)) {
                return $src;
            }
        }
        return null;
    }

    /**
     * Normalise whatever a feed calls a date into ISO 8601.
     *
     * Never throws and never returns null: an item with an unparseable date
     * sorts as "now" rather than disappearing from the list.
     */
    public function normaliseDate(string $dateString): string {
        if (empty($dateString)) {
            return date('c');
        }
        // Epoch seconds are not a date DateTime can read. It parses
        // "1757500000" as 17:57:50 in the year 0000 and rejects "1700000000"
        // outright, so a JSON API that timestamps this way — Nextcloud Talk and
        // Deck both do — had every item either sorting below the whole feed or
        // falling back to "now". Nine to eleven digits is the epoch-seconds
        // range; it keeps a bare year like "2024" out of this branch.
        if (preg_match('/^\d{9,11}$/', $dateString) === 1) {
            return date('c', (int) $dateString);
        }
        // Use DateTime for proper timezone handling — ambiguous dates default to UTC
        try {
            $dt = new \DateTime($dateString, new \DateTimeZone('UTC'));
            return $dt->format('c');
        } catch (\Exception $e) {
            $timestamp = strtotime($dateString);
            if ($timestamp === false) {
                return date('c');
            }
            return date('c', $timestamp);
        }
    }

    /**
     * Whether the source gave a time of day, not just a day.
     *
     * normaliseDate() always yields a full datetime, so its output cannot
     * answer this: "2026-11-06" becomes midnight UTC,
     * and a missing or unparseable date becomes "now", to the second. A
     * widget set to show the time would present both as real clock times.
     * This reads the raw value instead, following the same branches.
     */
    public function dateHasTime(string $dateString): bool {
        $dateString = trim($dateString);
        if ($dateString === '') {
            return false;
        }
        if (preg_match('/^\d{9,11}$/', $dateString) === 1) {
            return true;
        }
        // A bare year: DateTime reads "2024" as the clock time 20:24 today.
        if (preg_match('/^\d{1,4}$/', $dateString) === 1) {
            return false;
        }
        if (strtotime($dateString) === false) {
            return false;
        }
        // date_parse() reports hour as false when the string names no time.
        return date_parse($dateString)['hour'] !== false;
    }

    /**
     * Resolve a dot-notation path in a JSON array.
     * E.g., "data.items" resolves $data['data']['items'].
     * E.g., "author.name" resolves $item['author']['name'].
     *
     * @return mixed The resolved value, or null if path doesn't exist
     */
    public function resolvePath(array $data, string $path): mixed {
        if ($path === '') {
            return null;
        }

        $keys = explode('.', $path);
        $current = $data;

        foreach ($keys as $key) {
            if (!is_array($current) || !array_key_exists($key, $current)) {
                return null;
            }
            $current = $current[$key];
        }

        return $current;
    }

    /**
     * Fill {field} placeholders in a URL pattern from one item.
     *
     * A mapping can name a field but not compose one, which is fine for an API
     * that returns a link and useless for one that returns the PARTS of a link:
     * Nextcloud Talk gives a conversation token and a message id, Forms a share
     * hash. Those items rendered as href="" — and since every feed item is an
     * anchor, clicking one took the reader to the intranet front page.
     *
     * Values are percent-encoded because they come from an external API: a field
     * carrying "/" or "?x=1" fills the slot it was given rather than adding path
     * segments or a query string to the pattern an admin wrote. Encoding alone is
     * not enough, though — rawurlencode() leaves "." and ".." untouched, and a
     * whole segment of dots is read by the browser as "go up one level", which
     * moves the link somewhere else on the same host without ever adding a
     * character the encoder would catch. Those are refused outright.
     *
     * Booleans are refused for a different reason: (string) false is "", which
     * would fill the slot with nothing and quietly hand back the half-built URL
     * this method exists to avoid.
     *
     * A placeholder that cannot be filled yields no URL at all. The item still
     * appears — a Talk message is worth reading whether or not it can be linked
     * to — it simply renders without a link (see FeedItem.vue).
     */
    public function expandUrlTemplate(string $template, array $item): string {
        $missing = false;
        $url = preg_replace_callback(
            '/\{([a-zA-Z0-9_.]+)\}/',
            function (array $matches) use ($item, &$missing): string {
                $value = $this->resolvePath($item, $matches[1]);
                if (!is_scalar($value) || is_bool($value)) {
                    $missing = true;
                    return '';
                }
                $value = (string) $value;
                // "" fills nothing; "." and ".." survive encoding and re-point the URL.
                if ($value === '' || preg_match('/^\.+$/', $value) === 1) {
                    $missing = true;
                    return '';
                }
                return rawurlencode($value);
            },
            $template
        );

        return $missing ? '' : (string) $url;
    }

    /**
     * Cache key for one feed fetch.
     *
     * The user split is load-bearing: LMS feeds are personalised, so a shared
     * key would serve one student's deadlines to another. RSS is the same for
     * everyone and is therefore cached once.
     *
     * The share split is load-bearing for a different reason: the cached body
     * holds fully-formed image URLs, and those differ per route — a logged-in
     * reader gets `/apps/intravox/api/feed/image`, an anonymous visitor on a
     * share needs `/api/share/{token}/feed/image`. Without this, whichever
     * request populated the cache first decided what the other one saw, so the
     * images on a public share broke roughly half the time. Same feed, two
     * renderings; the key has to say which.
     */
    public function cacheKey(string $sourceType, array $config, ?string $userId = null, ?string $shareToken = null): string {
        $key = $sourceType . json_encode($config);
        if ($sourceType !== 'rss') {
            // Isolate cache per user for LMS feeds (personalized content)
            // Public/anonymous requests get a separate '_public' cache key
            $key .= $userId !== null ? ('_user_' . $userId) : '_public';
        }
        if ($shareToken !== null && $shareToken !== '') {
            $key .= '_share_' . $shareToken;
        }
        return 'feed_' . md5($key);
    }
}
