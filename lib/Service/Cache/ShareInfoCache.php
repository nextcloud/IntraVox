<?php

declare(strict_types=1);

namespace OCA\IntraVox\Service\Cache;

/**
 * The "is this page shared, and how" answer, remembered for a few minutes.
 *
 * WHY THIS EXISTS
 * ---------------
 * Measured on dev once Xdebug was off, share-info was the slowest endpoint in
 * a page view: 716 ms, against 265 ms to fetch the page itself and 592 ms for
 * the whole navigation tree. It is not one slow query. Finding "the nearest
 * share" means starting at the page and walking up through every parent
 * folder, resolving a node and asking the share manager at each level, until
 * something is found or the IntraVox root is reached. That walk is repeated on
 * every page view, for an answer that changes only when somebody creates or
 * revokes a link.
 *
 * WHY THE USER IS IN THE KEY
 * --------------------------
 * This is a security boundary, not a tuning detail. The answer carries a
 * `filesUrl`, and PublicShareService builds that by resolving the file through
 * the *calling user's own* mount — a user who cannot see the file gets a
 * different URL than one who can. Sharing an entry between users would hand
 * one of them a link assembled from another's view of the filesystem, and with
 * it the implication that they may open it. Two users with different access
 * must get different answers, so they get different entries.
 *
 * WHY A TTL AND NOTHING ELSE
 * --------------------------
 * Unusually for this app, the TTL is the whole invalidation story. IntraVox
 * never creates or revokes a share: ShareAdminApiController has two endpoints
 * and both are GETs. Sharing happens in the Files app or Nextcloud's own
 * sharing UI — code this app does not run and cannot hook — so there is no
 * mutation point at which to invalidate.
 *
 * Five minutes is therefore a deliberate ceiling on how long someone can be
 * told a page is shared after the link was revoked elsewhere: short enough
 * that a stale answer is a brief annoyance rather than a false statement about
 * access, long enough to absorb the repeat views that made this expensive. If
 * that window ever proves too wide, the fix is a listener on OCP\Share\Events,
 * not a smaller number here.
 *
 * @see PageCacheService which owns the distributed backend this rides on.
 */
final class ShareInfoCache {
    /** How long one answer stays good. See the class docblock. */
    public const TTL = 300;

    /**
     * Nullable for the same reason PageCacheService's own factory is: an
     * instance without a distributed backend still works, it just recomputes
     * every time. That is the shape unit tests get, and the shape of an
     * install with no Redis or APCu configured.
     */
    public function __construct(
        private ?PageCacheService $pages = null,
    ) {
    }

    /**
     * Return the remembered answer, or compute and remember it.
     *
     * The caller passes the work as a closure rather than the result, so a hit
     * never pays for the walk it is avoiding.
     *
     * @param callable():array $compute
     * @return array
     */
    public function remember(string $pageUniqueId, string $language, ?string $userId, callable $compute): array {
        $key = $this->keyFor($pageUniqueId, $language, $userId);

        $cached = $this->pages?->getDistributed($key);
        if (is_array($cached)) {
            return $cached;
        }

        $fresh = $compute();
        $this->pages?->setDistributed($key, $fresh, self::TTL);

        return $fresh;
    }

    /**
     * The cache key for one page, language and user.
     *
     * Hashed because a uniqueId is a sha1 and a userId is arbitrary text that
     * may contain characters a cache backend would rather not see in a key.
     * The empty string stands in for "no user" — an anonymous caller gets its
     * own entry rather than sharing one with the first logged-in visitor.
     */
    public function keyFor(string $pageUniqueId, string $language, ?string $userId): string {
        return 'shareinfo_' . md5($pageUniqueId . '|' . $language . '|' . ($userId ?? ''));
    }
}
