<?php
declare(strict_types=1);

namespace OCA\IntraVox\Service\Feed;

use OCA\IntraVox\Service\Sanitize\HtmlSanitizer;
use OCP\ICache;

/**
 * The article bodies behind a feed's items, kept one cache entry per item.
 *
 * A feed item usually carries the whole article — `content:encoded` in RSS,
 * `<content>` in Atom, the description field of a Jira issue or a Moodle post.
 * The list view only ever shows a 300-character excerpt of it, so until now the
 * rest was parsed and thrown away. Showing it means a reader can open a piece
 * without leaving the intranet, which for external news also means without
 * meeting a cookie wall.
 *
 * WHY A SEPARATE ENTRY PER ITEM, AND NOT A FIELD ON THE LIST
 * ----------------------------------------------------------
 * Measured on dev (Redis), 10 items of nextcloud.com/feed, 195 KB of sanitized
 * article between them:
 *
 *     in the list entry   211 KB per feed, 1.0 ms to read+decode  — every page view
 *     one entry per item  195 KB per feed, 0.04 ms to read one    — only when opened
 *
 * Both hold the same bytes; the difference is who pays. Folding the bodies into
 * the list response makes every visitor download all twenty articles to read
 * none of them — on a page with five feed widgets that is 678 KB instead of
 * 18 KB. Per item, the reader pays ~1.7 KB for the one they opened.
 *
 * WHY NOT FETCH ON CLICK INSTEAD
 * ------------------------------
 * Because the text is already in hand. The feed that produced the list carried
 * every article with it; fetching again on click would re-download the whole
 * feed — measured at 893 ms and 280 KB for nextcloud.com — to recover something
 * that was parsed forty seconds earlier. Storing costs 0.03 ms per item.
 *
 * The store is therefore written as a side effect of the list fetch, and read
 * on demand. A miss is normal and not an error: the entry may have expired, the
 * feed may not carry article bodies at all, or the cache may be disabled. The
 * widget falls back to linking out, which is what it did before this existed.
 *
 * @see FeedResponseReader for the stateless per-item helpers.
 */
class FeedArticleStore {
    /**
     * Matches FeedReaderService::CACHE_TTL.
     *
     * Deliberately the same: the body and the list entry that points at it come
     * from one fetch, and an item whose body outlived its list entry is a body
     * nothing can reach any more.
     */
    private const TTL = 900;

    /**
     * Ceiling on one stored article.
     *
     * The heaviest feed measured (forgejo.org release notes) puts 590 KB across
     * twenty items; a single entry above this is an outlier that would cost
     * every other reader cache space for one very long page. Truncating is
     * better than refusing — the reader still gets the opening, and the
     * "read on the site" link is always there.
     */
    public const MAX_ARTICLE_BYTES = 65536;

    public function __construct(
        private HtmlSanitizer $htmlSanitizer,
        private ?ICache $cache = null,
    ) {
    }

    /**
     * Cache key for one article.
     *
     * Derived from the feed's own cache key plus the item id, so the split
     * that protects the list protects the bodies too: a personalised LMS feed
     * is per user, a public share is per token. Getting this wrong would serve
     * one reader's article to another, which is why it is derived rather than
     * composed by the caller.
     *
     * The item id is hashed because it comes from the feed — a guid can be a
     * URL, contain a colon, or be arbitrarily long.
     */
    public function keyFor(string $feedCacheKey, string $itemId): string {
        return 'feedart_' . md5($feedCacheKey . '|' . $itemId);
    }

    /**
     * Store the bodies that came with a list fetch.
     *
     * Takes the items as parsed, reads `contentHtml` off each, and drops that
     * field from the array it returns — the body belongs in its own entry, not
     * in the list. Returns the items ready to be cached as the list.
     *
     * @param array<int, array<string, mixed>> $items
     * @return array<int, array<string, mixed>> the same items without contentHtml
     */
    public function putAll(string $feedCacheKey, array $items): array {
        foreach ($items as &$item) {
            $html = (string)($item['contentHtml'] ?? '');
            unset($item['contentHtml']);

            $id = (string)($item['id'] ?? '');
            if ($id === '' || $html === '') {
                // Nothing to store; the item simply has no body to open.
                $item['hasArticle'] = false;
                continue;
            }

            $clean = $this->prepare($html);
            if ($clean === '') {
                $item['hasArticle'] = false;
                continue;
            }

            // The flag is what the widget reads to decide whether to offer a
            // "read here" affordance. Without it the client would have to
            // request every item to find out which ones have anything.
            $item['hasArticle'] = $this->cache !== null
                && $this->cache->set($this->keyFor($feedCacheKey, $id), $clean, self::TTL);
        }
        unset($item);

        return $items;
    }

    /**
     * One article body, or null when there is nothing to show.
     *
     * Null is an ordinary outcome, not a failure: entries expire with the feed,
     * and a reader who leaves a page open past the TTL will simply follow the
     * link instead.
     */
    public function get(string $feedCacheKey, string $itemId): ?string {
        if ($this->cache === null || $itemId === '') {
            return null;
        }
        $stored = $this->cache->get($this->keyFor($feedCacheKey, $itemId));

        return is_string($stored) && $stored !== '' ? $stored : null;
    }

    /**
     * Sanitize and bound one article body.
     *
     * The HTML comes from a third party, so it is sanitized here rather than
     * anywhere nearer the browser: a body that is never stored unsanitized
     * cannot be served unsanitized by a later mistake.
     *
     * Truncation is applied after sanitizing. Cutting first could split a tag
     * and hand the sanitizer a fragment to repair, which is a worse input than
     * the one it was written for.
     */
    private function prepare(string $html): string {
        // Drop script and style wholesale first, contents included. The shared
        // sanitizer removes the tags but leaves what was between them, because
        // for editor HTML that is the safer default — stripping a paragraph's
        // text would lose the author's work. Here the opposite holds: nothing
        // inside <script> is article text, and leaving it turns "alert(1)" into
        // a visible line of the piece. It is not dangerous, only wrong.
        $html = preg_replace('#<(script|style)\b[^>]*>.*?</\1\s*>#is', '', $html) ?? $html;

        $clean = trim($this->htmlSanitizer->sanitize($html));
        if ($clean === '') {
            return '';
        }
        if (strlen($clean) <= self::MAX_ARTICLE_BYTES) {
            return $clean;
        }

        // mb_strcut, not mb_substr: the limit is a byte budget for the cache,
        // and strcut respects it while still landing on a character boundary.
        return mb_strcut($clean, 0, self::MAX_ARTICLE_BYTES, 'UTF-8');
    }
}
