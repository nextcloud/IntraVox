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
     */
    public function firstImageIn(string $html): ?string {
        if (empty($html)) {
            return null;
        }
        if (preg_match('/<img[^>]+src=["\']([^"\']+)["\']/', $html, $matches)) {
            $src = html_entity_decode($matches[1], ENT_QUOTES | ENT_HTML5, 'UTF-8');
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
     * Cache key for one feed fetch.
     *
     * The user split is load-bearing: LMS feeds are personalised, so a shared
     * key would serve one student's deadlines to another. RSS is the same for
     * everyone and is therefore cached once.
     */
    public function cacheKey(string $sourceType, array $config, ?string $userId = null): string {
        $key = $sourceType . json_encode($config);
        if ($sourceType !== 'rss') {
            // Isolate cache per user for LMS feeds (personalized content)
            // Public/anonymous requests get a separate '_public' cache key
            $key .= $userId !== null ? ('_user_' . $userId) : '_public';
        }
        return 'feed_' . md5($key);
    }
}
