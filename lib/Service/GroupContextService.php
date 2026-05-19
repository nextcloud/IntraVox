<?php
declare(strict_types=1);

namespace OCA\IntraVox\Service;

use OCA\IntraVox\Http\EtagBuilder;
use OCP\IGroupManager;
use OCP\IUser;
use OCP\IUserSession;

/**
 * Resolves a stable per-user group context for caching purposes.
 *
 * Pages, navigation and tree responses are identical for every user that
 * shares the same group membership set, but historically the IntraVox cache
 * was keyed per user-id. That meant 2000 users in 10 groups produced 2000
 * cache entries instead of 10 — fine for a single-tenant intranet, wasteful
 * at the enterprise scale the codebase now targets.
 *
 * `getGroupHash()` returns a short fingerprint derived from the user's
 * sorted group ids; identical group sets always produce the same hash so
 * the cache can be shared safely. The hash is memoized per request so we
 * don't re-walk IGroupManager on every cache lookup.
 *
 * Anonymous users (no IUserSession user) get a sentinel hash so they share
 * one cache bucket among themselves rather than poisoning the per-group
 * keyspace.
 */
final class GroupContextService {
    private const ANONYMOUS_HASH = 'anon';

    private IUserSession $userSession;
    private IGroupManager $groupManager;
    private array $memo = [];

    public function __construct(IUserSession $userSession, IGroupManager $groupManager) {
        $this->userSession = $userSession;
        $this->groupManager = $groupManager;
    }

    /**
     * Hash of the current user's group membership set. Stable across
     * requests as long as the user's group membership is unchanged.
     */
    public function getGroupHash(): string {
        $user = $this->userSession->getUser();
        return $this->getGroupHashForUser($user);
    }

    /**
     * Hash for an explicit user. Useful when the listener needs to invalidate
     * cache entries on behalf of someone other than the current session
     * (e.g. an admin moves another user between groups).
     */
    public function getGroupHashForUser(?IUser $user): string {
        if ($user === null) {
            return self::ANONYMOUS_HASH;
        }
        $uid = $user->getUID();
        if (isset($this->memo[$uid])) {
            return $this->memo[$uid];
        }
        $groupIds = $this->groupManager->getUserGroupIds($user);
        $hash = EtagBuilder::userContextFromGroups($groupIds);
        $this->memo[$uid] = $hash;
        return $hash;
    }

    /**
     * Compute a hash from a raw list of group ids. Allows event listeners to
     * derive the "old" hash of a user whose membership just changed without
     * needing the IUser object.
     *
     * @param array<int, string> $groupIds
     */
    public function hashForGroupIds(array $groupIds): string {
        return EtagBuilder::userContextFromGroups($groupIds);
    }

    /**
     * Clear the per-request memoization. Call from listeners when group
     * membership changes within the same request so subsequent reads in
     * the same process see the fresh hash.
     */
    public function reset(): void {
        $this->memo = [];
    }
}
