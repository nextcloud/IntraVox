<?php
declare(strict_types=1);

namespace OCA\IntraVox\Service;

use OCA\IntraVox\Service\Filter\FacetCalculator;
use OCA\IntraVox\Service\Filter\FilterSpec;
use OCA\IntraVox\Service\People\AccountBulkLoader;
use OCA\IntraVox\Service\People\AccountScopePolicy;
use OCA\IntraVox\Service\People\CohortCache;
use OCA\IntraVox\Service\People\CohortSnapshot;
use OCA\IntraVox\Service\People\ProfileFilterMatcher;
use OCP\Accounts\IAccountManager;
use OCP\Activity\IManager as IActivityManager;
use OCP\ICache;
use OCP\ICacheFactory;
use OCP\IConfig;
use OCP\IDBConnection;
use OCP\IGroupManager;
use OCP\IURLGenerator;
use OCP\IUser;
use OCP\IUserManager;
use OCP\IUserSession;
use OCP\UserStatus\IManager as IUserStatusManager;
use Psr\Log\LoggerInterface;

/**
 * Service for user profile data and filtering for the People widget
 *
 * Provides access to Nextcloud user profiles including:
 * - Standard profile fields (name, email, phone, etc.)
 * - Extended fields from LDAP/OIDC via IAccountManager
 * - Group membership
 * - Filtering and search capabilities
 */
class UserService {
    public const DEFAULT_LIMIT = 50;
    public const SEARCH_LIMIT = 20;
    private const APP_ID = 'intravox';
    private const CUSTOM_FIELDS_KEY = 'custom_fields';

    // Standard account properties that are always available
    private const STANDARD_PROPERTIES = [
        IAccountManager::PROPERTY_DISPLAYNAME => ['label' => 'Name', 'type' => 'text'],
        IAccountManager::PROPERTY_EMAIL => ['label' => 'Email', 'type' => 'email'],
        IAccountManager::PROPERTY_PHONE => ['label' => 'Phone', 'type' => 'phone'],
        IAccountManager::PROPERTY_ADDRESS => ['label' => 'Address', 'type' => 'text'],
        IAccountManager::PROPERTY_WEBSITE => ['label' => 'Website', 'type' => 'url'],
        IAccountManager::PROPERTY_TWITTER => ['label' => 'X (Twitter)', 'type' => 'text'],
        IAccountManager::PROPERTY_BLUESKY => ['label' => 'Bluesky', 'type' => 'text'],
        IAccountManager::PROPERTY_FEDIVERSE => ['label' => 'Fediverse', 'type' => 'text'],
        IAccountManager::PROPERTY_ORGANISATION => ['label' => 'Organisation', 'type' => 'text'],
        IAccountManager::PROPERTY_ROLE => ['label' => 'Role', 'type' => 'text'],
        IAccountManager::PROPERTY_HEADLINE => ['label' => 'Headline', 'type' => 'text'],
        IAccountManager::PROPERTY_BIOGRAPHY => ['label' => 'Biography', 'type' => 'textarea'],
        IAccountManager::PROPERTY_PRONOUNS => ['label' => 'Pronouns', 'type' => 'text'],
        IAccountManager::PROPERTY_BIRTHDATE => ['label' => 'Date of birth', 'type' => 'date'],
    ];

    private ?ICache $cache = null;
    private ?CohortCache $cohortCache = null;

    /** Hard cap on users processed in the no-group filter path to prevent OOM/timeouts */
    private const MAX_FILTER_SCAN = 5000;

    /** Cache TTL for filter results (1 hour) */
    private const FILTER_CACHE_TTL = 3600;

    public function __construct(
        private IUserManager $userManager,
        private IGroupManager $groupManager,
        private IAccountManager $accountManager,
        private IURLGenerator $urlGenerator,
        private LoggerInterface $logger,
        private ProfileFilterMatcher $filterMatcher,
        private ?IUserStatusManager $userStatusManager = null,
        private ?IConfig $config = null,
        private ?IActivityManager $activityManager = null,
        private ?IDBConnection $db = null,
        ?ICacheFactory $cacheFactory = null,
        private ?IUserSession $userSession = null,
        private ?GroupContextService $groupContext = null,
        private ?AccountBulkLoader $bulkLoader = null
    ) {
        if ($cacheFactory !== null && $cacheFactory->isAvailable()) {
            $this->cache = $cacheFactory->createDistributed('intravox-people');
        }

        // Autowiring cannot build the loader when IDBConnection is optional,
        // so construct it here when the connection is available.
        if ($this->bulkLoader === null && $db !== null) {
            $this->bulkLoader = new AccountBulkLoader($db, $logger);
        }
    }

    /**
     * Which audience the current request is being served to.
     *
     * Anonymous (public share) visitors see strictly less than logged-in
     * users. When no session is wired up we fail closed to anonymous — the
     * safer of the two.
     */
    private function currentAudience(): string {
        if ($this->userSession !== null && $this->userSession->getUser() !== null) {
            return AccountScopePolicy::AUDIENCE_LOCAL;
        }

        return AccountScopePolicy::AUDIENCE_ANONYMOUS;
    }

    /**
     * Built lazily because it closes over two of our own methods: the scan
     * needs the account and group layers, and the cache context needs the
     * session. Passing them as callables keeps CohortCache about caching.
     */
    private function cohortCache(): CohortCache {
        return $this->cohortCache ??= new CohortCache(
            $this->cache,
            $this->logger,
            fn (array $f, string $op, array $needed, ?string $audience = null): CohortSnapshot => $this->scanCohort($f, $op, $needed, $audience),
            fn (): string => $this->cacheContext(),
            fn (): array => [
                'audience' => $this->currentAudience(),
                'groupHash' => $this->groupContext?->getGroupHash() ?? 'nogroups',
            ],
        );
    }

    /**
     * @deprecated Delegated to People\CohortCache.
     *
     * Kept here because PeopleCohortWarmupJob calls it on the service.
     */
    public function warmCohorts(): array {
        return $this->cohortCache()->warmCohorts();
    }

    /**
     * Cache-key discriminator for anything derived from account properties.
     *
     * Two things vary what a user may see: the audience (scope gating) and
     * their group membership (which accounts are visible at all). Both must
     * be in the key, or one user's cached result leaks to another.
     */
    private function cacheContext(): string {
        $groupHash = $this->groupContext !== null
            ? $this->groupContext->getGroupHash()
            : 'nogroups';

        return $this->currentAudience() . '_' . $groupHash;
    }

    /**
     * Search users by display name or UID
     *
     * @param string $query Search query
     * @param int $limit Maximum number of results
     * @return array Array of user profiles
     */
    public function searchUsers(string $query, int $limit = self::SEARCH_LIMIT): array {
        $users = [];
        $seen = [];

        // Search by display name (returns array of IUser)
        $displayNameResults = $this->userManager->searchDisplayName($query, $limit);
        foreach ($displayNameResults as $user) {
            if (count($users) >= $limit) {
                break;
            }
            if (!isset($seen[$user->getUID()])) {
                $seen[$user->getUID()] = true;
                $users[] = $this->buildUserProfile($user);
            }
        }

        // Also search by UID if we haven't reached the limit
        if (count($users) < $limit) {
            $remaining = $limit - count($users);
            $uidResults = $this->userManager->search($query, $remaining);
            foreach ($uidResults as $user) {
                if (count($users) >= $limit) {
                    break;
                }
                if (!isset($seen[$user->getUID()])) {
                    $seen[$user->getUID()] = true;
                    $users[] = $this->buildUserProfile($user);
                }
            }
        }

        return $users;
    }

    /**
     * Get full profile for a single user
     *
     * @param string $userId User ID
     * @return array|null User profile or null if not found
     */
    public function getUserProfile(string $userId): ?array {
        $user = $this->userManager->get($userId);
        if ($user === null) {
            return null;
        }
        return $this->buildUserProfile($user);
    }

    /**
     * Get profiles for multiple users
     *
     * @param array $userIds Array of user IDs
     * @return array User profiles (indexed by uid for easy lookup)
     */
    public function getUserProfiles(array $userIds): array {
        $profiles = [];
        foreach ($userIds as $userId) {
            $profile = $this->getUserProfile($userId);
            if ($profile !== null) {
                $profiles[] = $profile;
            }
        }
        return $profiles;
    }

    /**
     * Faceted query over the cohort an editor's widget config selects.
     *
     * The narrowing invariant lives here: $editorFilters build the cohort,
     * $refinements are applied to that cohort afterwards. A viewer therefore
     * cannot reach any user the widget config excludes — the data simply is
     * not in the snapshot. See FacetCalculator for the counting rules.
     *
     * @param array $editorFilters Legacy or canonical filter rows from the widget config
     * @param array $refinements   Canonical viewer refinement rows
     * @param array $facetFields   Field names to compute facets for
     * @param string $q            Free-text term, matched against $searchFields
     * @param array $searchFields  Fields $q searches; defaults to display name
     * @return array{users: array, total: int, hasMore: bool, facets: array, meta: array}
     */
    public function queryFaceted(
        array $editorFilters,
        string $operator = 'AND',
        array $refinements = [],
        array $facetFields = [],
        string $q = '',
        array $searchFields = [],
        int $limit = self::DEFAULT_LIMIT,
        int $offset = 0,
        string $sortBy = 'displayName',
        string $sortOrder = 'asc',
        int $facetLimit = FacetCalculator::DEFAULT_FACET_LIMIT
    ): array {
        $editorFilters = FilterSpec::normalizeList($editorFilters);
        $refinements = FilterSpec::normalizeList($refinements);

        // A facet on a field the editor already filters would only ever show
        // options that yield nothing, so drop those. Same reasoning as the
        // permission check: silently, because the client need not care.
        $editorFields = array_map(static fn(array $f): string => $f['field'], $editorFilters);
        $facetFields = array_values(array_filter(
            array_map(static fn($f): string => FilterSpec::aliasField((string)$f), $facetFields),
            static fn(string $f): bool => $f !== '' && !in_array($f, $editorFields, true)
        ));

        // The refined fields must be loaded into the snapshot too, not just
        // the facet fields: a viewer can refine on a field the page never
        // renders as a facet (a deep link, a chip restored from the URL).
        // Leaving them out made the result set depend on which facets were
        // requested — refining on `organisation` without asking for it as a
        // facet matched nothing at all.
        $refinedFields = array_map(static fn(array $r): string => $r['field'], $refinements);

        $snapshot = $this->cohortCache()->buildCohortSnapshot(
            $editorFilters,
            $operator,
            array_values(array_unique(array_merge($facetFields, $refinedFields))),
            $searchFields,
            $sortBy
        );
        $rows = $snapshot->toFilterRows();

        // Free text first: it is part of "the set the viewer is looking at",
        // so facets must be counted after it, not before.
        if ($q !== '') {
            $rows = $this->filterMatcher->applyFreeText($rows, $q, $searchFields);
        }

        $facets = FacetCalculator::compute($rows, $facetFields, $refinements, [], $facetLimit);

        $matched = FacetCalculator::applyFilters($rows, $refinements);

        usort($matched, static function (array $a, array $b) use ($sortBy, $sortOrder): int {
            $result = strcasecmp((string)($a[$sortBy] ?? ''), (string)($b[$sortBy] ?? ''));
            return $sortOrder === 'desc' ? -$result : $result;
        });

        $total = count($matched);
        $page = array_slice($matched, $offset, $limit);
        $pageUids = array_column($page, 'uid');

        // Hydrate only the page. This is the whole point of the snapshot:
        // full profiles (avatar, live presence) for <= $limit users instead
        // of for the entire cohort.
        $this->prefetchStatuses($pageUids);
        $users = $this->getUserProfiles($pageUids);

        return [
            'users' => $users,
            'total' => $total,
            'hasMore' => ($offset + count($page)) < $total,
            'facets' => $facets,
            'meta' => [
                'approximate' => $snapshot->approximate,
                'scanned' => $snapshot->scanned,
                'cap' => $snapshot->cap,
            ],
        ];
    }

    /**
     * How large this instance is relative to the scan cap.
     *
     * countUsers() is a cheap aggregate per backend, unlike the scan itself,
     * so this is safe to call from an editor panel.
     *
     * @return array{userCount: int, cap: int, approximate: bool}
     */
    public function facetPreflight(): array {
        $total = 0;

        try {
            foreach ($this->userManager->countUsers() as $count) {
                $total += (int)$count;
            }
        } catch (\Throwable $e) {
            $this->logger->debug('IntraVox: countUsers() unavailable: ' . $e->getMessage());
        }

        return [
            'userCount' => $total,
            'cap' => self::MAX_FILTER_SCAN,
            'approximate' => $total > self::MAX_FILTER_SCAN,
        ];
    }








    /**
     * Walk the candidate users and record only the needed fields.
     */
    private function scanCohort(
        array $editorFilters,
        string $operator,
        array $neededFields,
        ?string $audience = null
    ): CohortSnapshot {
        $needsGroups = in_array('group', $neededFields, true) || in_array('groups', $neededFields, true);
        // The audience is a property of the cohort being built, not of the
        // process building it. A background job has no session, so deriving it
        // from currentAudience() there would silently rebuild a logged-in
        // cohort as anonymous and strip every Local field from it.
        $audience = $audience ?? $this->currentAudience();

        $scanned = 0;
        $cap = self::MAX_FILTER_SCAN;

        // Collect the users first, then read their account data in bulk.
        // getAccount() is one query per user and dominates this scan by two
        // orders of magnitude (20.4 ms per 100 accounts, versus 0.1 ms for
        // reading every property once loaded), so the single most valuable
        // thing this method does is not call it in a loop.
        $users = [];
        $groupIds = $this->groupIdsFromFilters($editorFilters);

        if ($groupIds !== []) {
            // A group filter narrows the scan enormously, so honour it first.
            $seen = [];
            foreach ($groupIds as $groupId) {
                $group = $this->groupManager->get($groupId);
                if ($group === null) {
                    continue;
                }
                foreach ($group->getUsers() as $user) {
                    $uid = $user->getUID();
                    if (isset($seen[$uid])) {
                        continue;
                    }
                    $seen[$uid] = true;
                    $scanned++;
                    $users[] = $user;
                }
            }
        } else {
            $this->userManager->callForAllUsers(function (IUser $user) use (&$scanned, &$users, $cap): void {
                if ($scanned >= $cap) {
                    return;
                }
                $scanned++;
                $users[] = $user;
            });
        }

        $uids = array_map(static fn(IUser $u): string => $u->getUID(), $users);

        $bulkAccounts = [];
        $bulkCustom = [];
        if ($this->bulkLoader !== null && $this->bulkLoader->isAvailable()) {
            $bulkAccounts = $this->bulkLoader->loadAccounts($uids);
            if ($audience === AccountScopePolicy::AUDIENCE_LOCAL) {
                $bulkCustom = $this->bulkLoader->loadCustomFields($uids, self::APP_ID, self::CUSTOM_FIELDS_KEY);
            }
        }

        $rows = [];
        foreach ($users as $user) {
            $rows[] = $this->snapshotRowFor(
                $user,
                $neededFields,
                $needsGroups,
                $audience,
                $bulkAccounts[$user->getUID()] ?? null,
                $bulkCustom[$user->getUID()] ?? null
            );
        }

        $approximate = $groupIds === [] && $scanned >= $cap;

        if ($approximate) {
            $this->logger->warning(
                'IntraVox: People cohort scan hit the cap of ' . $cap
                . ' accounts; facet counts are approximate. Add a group filter for exact numbers.'
            );
        }

        // Editor filters define the cohort. Applying them here — rather than
        // at query time — is what makes the narrowing invariant structural:
        // a viewer refinement operates on this set and cannot escape it.
        $snapshotRows = [];
        foreach ($rows as $row) {
            $flat = $row['f'];
            $flat['uid'] = $row['u'];
            $flat['displayName'] = $row['n'];
            $flat['groups'] = $row['g'];

            if ($editorFilters === [] || FacetCalculator::applyFilters([$flat], $editorFilters, $operator) !== []) {
                $snapshotRows[] = $row;
            }
        }

        return new CohortSnapshot($snapshotRows, $approximate, $scanned, $cap);
    }

    /**
     * Extract group ids from the editor filters, if any.
     *
     * @return array<int, string>
     */
    private function groupIdsFromFilters(array $editorFilters): array {
        $ids = [];

        foreach ($editorFilters as $filter) {
            if (($filter['field'] ?? '') !== 'group') {
                continue;
            }
            $value = $filter['value'] ?? null;
            foreach (is_array($value) ? $value : [$value] as $candidate) {
                if (is_scalar($candidate) && trim((string)$candidate) !== '') {
                    $ids[] = trim((string)$candidate);
                }
            }
        }

        return array_values(array_unique($ids));
    }

    /**
     * Build one compact snapshot row.
     *
     * @return array{u: string, n: string, f: array<string, mixed>, g: array<int, string>}
     */
    private function snapshotRowFor(
        IUser $user,
        array $neededFields,
        bool $needsGroups,
        string $audience,
        ?array $bulkAccount = null,
        ?array $bulkCustom = null
    ): array {
        $fields = [];

        if ($bulkAccount !== null) {
            // Fast path: properties already read in one bulk query. Scope
            // handling is identical to the per-user path below — it runs
            // through AccountScopePolicy either way, so the two cannot
            // disagree about what an audience may see.
            foreach ($bulkAccount as $name => $meta) {
                if (!AccountScopePolicy::isVisible($meta['scope'] ?? null, $audience)) {
                    continue;
                }
                $key = $this->propertyToKey((string)$name);
                $aliased = FilterSpec::aliasField($key);
                if ($neededFields !== [] && !in_array($aliased, $neededFields, true) && !in_array($key, $neededFields, true)) {
                    continue;
                }
                $fields[$aliased] = ($meta['value'] ?? '') !== '' ? $meta['value'] : null;
            }
        } else {
            // Fallback: no bulk row for this uid (an LDAP backend that does
            // not mirror into oc_accounts, or a failed bulk query).
            try {
                $account = $this->accountManager->getAccount($user);

                foreach ($account->getProperties() as $prop) {
                    if (!$this->propertyVisible($prop, $audience)) {
                        continue;
                    }
                    $key = $this->propertyToKey($prop->getName());
                    $aliased = FilterSpec::aliasField($key);
                    if ($neededFields !== [] && !in_array($aliased, $neededFields, true) && !in_array($key, $neededFields, true)) {
                        continue;
                    }
                    $fields[$aliased] = $prop->getValue() ?: null;
                }
            } catch (\Exception $e) {
                // No account data available; the row still carries uid + name.
            }
        }

        // IntraVox custom fields carry no scope, so they follow the same
        // rule as in buildUserProfile(): logged-in only.
        if ($audience === AccountScopePolicy::AUDIENCE_LOCAL && ($bulkCustom !== null || $this->config !== null)) {
            try {
                $custom = $bulkCustom;
                if ($custom === null) {
                    $custom = json_decode(
                        $this->config->getUserValue($user->getUID(), self::APP_ID, self::CUSTOM_FIELDS_KEY, '{}'),
                        true
                    );
                }
                if (is_array($custom)) {
                    foreach ($custom as $key => $value) {
                        $aliased = FilterSpec::aliasField((string)$key);
                        if ($neededFields !== [] && !in_array($aliased, $neededFields, true)) {
                            continue;
                        }
                        if (!isset($fields[$aliased]) && $value !== null && $value !== '') {
                            $fields[$aliased] = $value;
                        }
                    }
                }
            } catch (\Exception $e) {
                // Ignore unreadable preferences.
            }
        }

        return [
            'u' => $user->getUID(),
            'n' => $user->getDisplayName(),
            'f' => $fields,
            'g' => $needsGroups ? $this->getGroupsForUser($user->getUID()) : [],
        ];
    }

    /**
     * Get users by group membership
     *
     * @param string $groupId Group ID
     * @param int $limit Maximum number of results
     * @return array User profiles
     */
    public function getUsersByGroup(string $groupId, int $limit = self::DEFAULT_LIMIT): array {
        $group = $this->groupManager->get($groupId);
        if ($group === null) {
            return [];
        }

        // Collect user IDs first, then prefetch statuses in batch
        $groupUsers = [];
        $userIds = [];
        foreach ($group->getUsers() as $user) {
            if (count($groupUsers) >= $limit) {
                break;
            }
            $groupUsers[] = $user;
            $userIds[] = $user->getUID();
        }

        $this->prefetchStatuses($userIds);

        $users = [];
        foreach ($groupUsers as $user) {
            $users[] = $this->buildUserProfile($user);
        }
        return $users;
    }

    /**
     * Get users matching filters
     *
     * @param array $filters Array of filter definitions
     * @param string $operator 'AND' or 'OR' for combining filters
     * @param int $limit Maximum number of results
     * @param string $sortBy Field to sort by
     * @param string $sortOrder 'asc' or 'desc'
     * @param int $offset Offset for pagination
     * @param bool $returnTotal Whether to return total count
     * @return array User profiles (or ['users' => [...], 'total' => N] if returnTotal is true)
     */
    public function getUsersByFilters(
        array $filters,
        string $operator = 'AND',
        int $limit = self::DEFAULT_LIMIT,
        string $sortBy = 'displayName',
        string $sortOrder = 'asc',
        int $offset = 0,
        bool $returnTotal = false
    ): array {
        // If no filters, return empty (don't return all users)
        if (empty($filters)) {
            return $returnTotal ? ['users' => [], 'total' => 0] : [];
        }

        // Check if we have a group filter - this is more efficient
        $groupFilter = null;
        $otherFilters = [];
        foreach ($filters as $filter) {
            if ($filter['fieldName'] === 'group') {
                $groupFilter = $filter;
            } else {
                $otherFilters[] = $filter;
            }
        }

        $users = [];

        // If we have a group filter, start with those users (more efficient)
        if ($groupFilter !== null) {
            // Support both 'value' and 'values' from frontend
            // Prefer non-empty 'values' array, fallback to 'value'
            $filterValue = (!empty($groupFilter['values']) && is_array($groupFilter['values']))
                ? $groupFilter['values']
                : ($groupFilter['value'] ?? []);
            $groupValues = is_array($filterValue) ? $filterValue : [$filterValue];
            // Filter out empty values
            $groupValues = array_filter($groupValues, fn($v) => !empty($v));
            $seen = [];
            // The same hard cap the all-users branch below applies. A group
            // filter is cheaper PER USER but not bounded in total: nothing stops
            // a group holding every account on the instance, and several such
            // groups can be requested at once. Without this, one People-widget
            // request built a full profile for every member of every named group
            // — reachable from the anonymous share endpoint too.
            $scanned = 0;
            $capped = false;

            foreach ($groupValues as $groupId) {
                if ($capped) {
                    break;
                }

                $group = $this->groupManager->get($groupId);
                if ($group === null) {
                    continue;
                }

                foreach ($group->getUsers() as $user) {
                    if ($scanned >= self::MAX_FILTER_SCAN) {
                        $capped = true;
                        break;
                    }
                    $scanned++;

                    if (isset($seen[$user->getUID()])) {
                        continue;
                    }
                    $seen[$user->getUID()] = true;

                    $profile = $this->buildUserProfile($user);
                    // Apply other filters
                    if (empty($otherFilters) || $this->filterMatcher->matchesFilters($profile, $otherFilters, $operator)) {
                        $users[] = $profile;
                    }
                }
            }

            if ($capped) {
                $this->logger->warning(
                    'IntraVox: People widget group-filter scan hit hard cap of '
                    . self::MAX_FILTER_SCAN . ' users. Narrow the group selection.'
                );
            }
        } else {
            // No group filter - need to iterate all users (less efficient).
            // Apply a hard cap to prevent OOM/timeout on large instances.
            $maxToCollect = $returnTotal ? self::MAX_FILTER_SCAN : min(($limit + $offset) * 2, self::MAX_FILTER_SCAN);

            // Try cache for this filter combination.
            // Key includes audience + group hash. The previous key was
            // 'filter_shared_<hash>', shared across every user regardless of
            // what they were allowed to see. Renaming the prefix is not
            // cosmetic: it orphans every entry built before the scope gate
            // existed, which would otherwise keep serving private fields
            // until it aged out. Do not revert to the old prefix.
            $filterCacheKey = 'filter_v2_' . $this->cacheContext() . '_'
                . md5(json_encode($filters) . $operator . $sortBy . $sortOrder);
            if ($this->cache !== null) {
                $cached = $this->cache->get($filterCacheKey);
                if ($cached !== null) {
                    $decoded = json_decode($cached, true);
                    if ($decoded !== null) {
                        $users = $decoded;
                    }
                }
            }

            if (empty($users)) {
                $scanned = 0;
                $this->userManager->callForAllUsers(function (IUser $user) use (&$users, &$scanned, $filters, $operator, $maxToCollect) {
                    $scanned++;
                    if (count($users) >= $maxToCollect) {
                        return;
                    }
                    $profile = $this->buildUserProfile($user);
                    if ($this->filterMatcher->matchesFilters($profile, $filters, $operator)) {
                        $users[] = $profile;
                    }
                });

                // Cache results for subsequent requests
                if ($this->cache !== null && !empty($users)) {
                    $this->cache->set($filterCacheKey, json_encode($users), self::FILTER_CACHE_TTL);
                }

                if ($scanned >= self::MAX_FILTER_SCAN && count($users) >= $maxToCollect) {
                    $this->logger->warning('IntraVox: People widget filter scan hit hard cap of ' . self::MAX_FILTER_SCAN . ' users. Consider adding a group filter for better performance.');
                }
            }
        }

        // Sort results
        usort($users, function ($a, $b) use ($sortBy, $sortOrder) {
            $valueA = $a[$sortBy] ?? '';
            $valueB = $b[$sortBy] ?? '';
            $result = strcasecmp($valueA, $valueB);
            return $sortOrder === 'desc' ? -$result : $result;
        });

        // Get total before applying offset/limit
        $total = count($users);

        // Apply offset and limit
        $paginatedUsers = array_slice($users, $offset, $limit);

        if ($returnTotal) {
            return [
                'users' => $paginatedUsers,
                'total' => $total,
            ];
        }

        return $paginatedUsers;
    }

    /**
     * Get all groups (for filter dropdown)
     *
     * @return array Array of groups with id and displayName
     */
    public function getGroups(): array {
        $groups = [];
        foreach ($this->groupManager->search('') as $group) {
            $groups[] = [
                'id' => $group->getGID(),
                'displayName' => $group->getDisplayName(),
            ];
        }

        // Sort by display name
        usort($groups, fn($a, $b) => strcasecmp($a['displayName'], $b['displayName']));

        return $groups;
    }

    /**
     * Get available user profile fields (for filter/display configuration)
     *
     * @return array Field definitions with name, label, and type
     */
    public function getAvailableFields(): array {
        // Define fields in order matching Display Options in PeopleWidgetEditor
        $orderedFields = [
            // Group filter (most common filter, at top)
            'group' => ['label' => 'Group', 'type' => 'select'],

            // Basic Information (matches Display Options order)
            IAccountManager::PROPERTY_DISPLAYNAME => ['label' => 'Name', 'type' => 'text'],
            IAccountManager::PROPERTY_PRONOUNS => ['label' => 'Pronouns', 'type' => 'text'],
            IAccountManager::PROPERTY_ROLE => ['label' => 'Role', 'type' => 'text'],
            IAccountManager::PROPERTY_HEADLINE => ['label' => 'Headline', 'type' => 'text'],
            IAccountManager::PROPERTY_ORGANISATION => ['label' => 'Organisation', 'type' => 'text'],

            // Contact
            IAccountManager::PROPERTY_EMAIL => ['label' => 'Email', 'type' => 'text'],
            IAccountManager::PROPERTY_PHONE => ['label' => 'Phone', 'type' => 'text'],
            IAccountManager::PROPERTY_ADDRESS => ['label' => 'Address', 'type' => 'text'],
            IAccountManager::PROPERTY_WEBSITE => ['label' => 'Website', 'type' => 'text'],
            IAccountManager::PROPERTY_BIRTHDATE => ['label' => 'Date of birth', 'type' => 'date'],

            // Extended
            IAccountManager::PROPERTY_BIOGRAPHY => ['label' => 'Biography', 'type' => 'text'],
            IAccountManager::PROPERTY_TWITTER => ['label' => 'X (Twitter)', 'type' => 'text'],
            IAccountManager::PROPERTY_BLUESKY => ['label' => 'Bluesky', 'type' => 'text'],
            IAccountManager::PROPERTY_FEDIVERSE => ['label' => 'Fediverse', 'type' => 'text'],
        ];

        $fields = [];

        // Build fields array in defined order
        foreach ($orderedFields as $fieldName => $config) {
            $field = [
                'fieldName' => $fieldName,
                'label' => $config['label'],
                'type' => $config['type'],
            ];

            // Add group options for select fields
            if ($fieldName === 'group') {
                $field['options'] = $this->getGroups();
            }

            $fields[] = $field;
        }

        // Try to detect additional fields from a sample user (LDAP/OIDC)
        $additionalFields = $this->detectAdditionalFields();
        foreach ($additionalFields as $field) {
            $fields[] = $field;
        }

        return $fields;
    }

    /**
     * Whether an account property may be shown to the given audience.
     *
     * Tolerant by design: an account-property implementation that predates
     * getScope(), or throws, is treated as v2-local — visible to logged-in
     * users, never to anonymous share visitors.
     *
     * @param mixed $prop An IAccountProperty (loosely typed so older
     *                    Nextcloud property objects don't fatal here)
     */
    private function propertyVisible(mixed $prop, string $audience): bool {
        $scope = null;

        if (is_object($prop) && method_exists($prop, 'getScope')) {
            try {
                $scope = $prop->getScope();
            } catch (\Throwable $e) {
                $scope = null;
            }
        }

        return AccountScopePolicy::isVisible(
            is_string($scope) ? $scope : null,
            $audience
        );
    }

    /**
     * Build user profile array from IUser
     *
     * @param IUser $user The user object
     * @param string|null $audience Visibility audience; defaults to the
     *                              audience of the current request.
     * @return array User profile data
     */
    private function buildUserProfile(IUser $user, ?string $audience = null): array {
        $audience = $audience ?? $this->currentAudience();

        $profile = [
            'uid' => $user->getUID(),
            'displayName' => $user->getDisplayName(),
            'avatarUrl' => $this->urlGenerator->linkToRouteAbsolute('core.avatar.getAvatar', [
                'userId' => $user->getUID(),
                'size' => 128,
            ]),
            'groups' => $this->getGroupsForUser($user->getUID()),
            'status' => $this->getUserStatus($user->getUID()),
        ];

        // NOTE: `email` is deliberately NOT seeded from IUser::getEMailAddress()
        // here. That call bypasses the account-property scope entirely, so it
        // would hand out an address the user marked private. The scoped loop
        // below sets `email` when the scope allows it; the key is initialised
        // to null so consumers keep seeing a stable shape.
        $profile['email'] = null;

        // Get account data from IAccountManager
        try {
            $account = $this->accountManager->getAccount($user);

            // Add standard properties
            foreach (array_keys(self::STANDARD_PROPERTIES) as $property) {
                try {
                    $prop = $account->getProperty($property);

                    // Respect the per-property visibility scope the user set
                    // in Personal info. Without this gate a property marked
                    // private is handed to every viewer, and on a public
                    // share to the open internet.
                    if (!$this->propertyVisible($prop, $audience)) {
                        continue;
                    }

                    $value = $prop->getValue();
                    // Use a simplified key name
                    $key = $this->propertyToKey($property);

                    // Normalize birthdate to ISO 8601 (YYYY-MM-DD) format
                    // Nextcloud may store locale-specific formats (e.g. DD-MM-YYYY)
                    if ($property === IAccountManager::PROPERTY_BIRTHDATE && !empty($value)) {
                        $value = $this->filterMatcher->normalizeDateToISO($value);
                    }

                    $profile[$key] = $value ?: null;
                } catch (\Exception $e) {
                    // Property not available for this user
                }
            }

            // Try to get additional properties (from LDAP/OIDC).
            // This is where the widest leak was: these are never enumerated
            // in STANDARD_PROPERTIES, so whatever the directory syncs lands
            // here — including fields an admin considers internal.
            try {
                $allProperties = $account->getProperties();
                foreach ($allProperties as $prop) {
                    if (!$this->propertyVisible($prop, $audience)) {
                        continue;
                    }
                    $name = $prop->getName();
                    $key = $this->propertyToKey($name);
                    if (!isset($profile[$key])) {
                        $profile[$key] = $prop->getValue() ?: null;
                    }
                }
            } catch (\Exception $e) {
                // getProperties may not be available in all versions
            }
        } catch (\Exception $e) {
            $this->logger->debug('Could not get account for user {userId}: {message}', [
                'userId' => $user->getUID(),
                'message' => $e->getMessage(),
            ]);
        }

        // Get IntraVox custom fields from user preferences
        // These are stored by the intravox:add-demo-fields command or LDAP/OIDC sync.
        //
        // They carry no visibility scope of their own, so we treat them as
        // v2-local: available to logged-in users, never to anonymous visitors
        // on a public share.
        if ($this->config !== null && $audience === AccountScopePolicy::AUDIENCE_LOCAL) {
            try {
                $customFieldsJson = $this->config->getUserValue(
                    $user->getUID(),
                    self::APP_ID,
                    self::CUSTOM_FIELDS_KEY,
                    '{}'
                );
                $customFields = json_decode($customFieldsJson, true);
                if (is_array($customFields)) {
                    foreach ($customFields as $key => $value) {
                        if (!isset($profile[$key]) && $value !== null && $value !== '') {
                            $profile[$key] = $value;
                        }
                    }
                }
            } catch (\Exception $e) {
                $this->logger->debug('Could not get custom fields for user {userId}: {message}', [
                    'userId' => $user->getUID(),
                    'message' => $e->getMessage(),
                ]);
            }
        }

        return $profile;
    }

    /**
     * Get group IDs for a user
     *
     * @param string $userId User ID
     * @return list<string> Array of group IDs
     */
    private function getGroupsForUser(string $userId): array {
        return $this->groupManager->getUserGroupIds($this->userManager->get($userId));
    }

    /**
     * Get user status (online/away/dnd/offline)
     *
     * @param string $userId User ID
     * @return array|null Status info or null if not available
     */
    /** @var array Request-level cache for user statuses */
    private array $statusCache = [];

    /**
     * Prefetch statuses for multiple users in a single call.
     * Call this before building profiles to populate the cache.
     *
     * @param string[] $userIds User IDs to prefetch
     */
    public function prefetchStatuses(array $userIds): void {
        if ($this->userStatusManager === null || empty($userIds)) {
            return;
        }

        // Only fetch for uncached users
        $uncached = array_filter($userIds, fn($id) => !isset($this->statusCache[$id]));
        if (empty($uncached)) {
            return;
        }

        try {
            $statuses = $this->userStatusManager->getUserStatuses(array_values($uncached));
            foreach ($statuses as $userId => $status) {
                $this->statusCache[$userId] = [
                    'status' => $status->getStatus(),
                    'message' => $status->getMessage(),
                    'icon' => $status->getIcon(),
                ];
            }
            // Mark missing users so we don't re-fetch
            foreach ($uncached as $userId) {
                if (!isset($this->statusCache[$userId])) {
                    $this->statusCache[$userId] = null;
                }
            }
        } catch (\Exception $e) {
            $this->logger->debug('Could not prefetch statuses: {message}', [
                'message' => $e->getMessage(),
            ]);
        }
    }

    private function getUserStatus(string $userId): ?array {
        // Return from cache if available
        if (array_key_exists($userId, $this->statusCache)) {
            return $this->statusCache[$userId];
        }

        if ($this->userStatusManager === null) {
            return null;
        }

        try {
            $statuses = $this->userStatusManager->getUserStatuses([$userId]);
            if (isset($statuses[$userId])) {
                $status = $statuses[$userId];
                $result = [
                    'status' => $status->getStatus(),
                    'message' => $status->getMessage(),
                    'icon' => $status->getIcon(),
                ];
                $this->statusCache[$userId] = $result;
                return $result;
            }
        } catch (\Exception $e) {
            $this->logger->debug('Could not get status for user {userId}: {message}', [
                'userId' => $userId,
                'message' => $e->getMessage(),
            ]);
        }

        $this->statusCache[$userId] = null;
        return null;
    }

    /**
     * Convert property constant to a simple key name
     *
     * @param string $property Property constant or name
     * @return string Simple key name
     */
    private function propertyToKey(string $property): string {
        // Remove common prefixes and convert to camelCase
        $key = $property;

        // Handle constants like IAccountManager::PROPERTY_EMAIL -> email
        if (str_contains($property, '::')) {
            $parts = explode('::', $property);
            $key = end($parts);
        }

        // Remove PROPERTY_ prefix if present
        if (str_starts_with($key, 'PROPERTY_')) {
            $key = substr($key, 9);
        }

        // Convert to lowercase
        return strtolower($key);
    }




    /**
     * Try to detect additional fields from sample users
     * This helps discover LDAP/OIDC fields that might be available
     *
     * @return array Additional field definitions
     */
    private function detectAdditionalFields(): array {
        $additionalFields = [];
        $knownFields = array_map(fn($p) => $this->propertyToKey($p), array_keys(self::STANDARD_PROPERTIES));
        $knownFields = array_merge($knownFields, ['uid', 'displayName', 'email', 'avatarUrl', 'groups']);
        // Internal Nextcloud fields that should not appear as filter options
        $knownFields = array_merge($knownFields, [
            'profileenabled', 'profile_enabled', 'profileEnabled',
            'status', 'lastlogin', 'last_login', 'lastLogin',
            // Avatar is not useful as a filter field
            'avatar',
        ]);

        // Sample a few users to detect additional fields
        $sampleCount = 0;
        $detectedFields = [];

        // Fields are only advertised when this audience could actually see
        // them. Listing a private field in the editor dropdown leaks its
        // existence even if its values never render.
        $audience = $this->currentAudience();

        $this->userManager->callForAllUsers(function (IUser $user) use (&$sampleCount, &$detectedFields, $knownFields, $audience) {
            if ($sampleCount >= 5) {
                return;
            }
            $sampleCount++;

            try {
                $account = $this->accountManager->getAccount($user);
                $allProperties = $account->getProperties();
                foreach ($allProperties as $prop) {
                    if (!$this->propertyVisible($prop, $audience)) {
                        continue;
                    }
                    $key = $this->propertyToKey($prop->getName());
                    if (!in_array($key, $knownFields) && !isset($detectedFields[$key])) {
                        $detectedFields[$key] = [
                            'fieldName' => $key,
                            'label' => ucfirst(str_replace('_', ' ', $key)),
                            'type' => 'text',
                        ];
                    }
                }
            } catch (\Exception $e) {
                // Skip if account not available
            }
        });

        // Sort additional fields: social fields first (bluesky), then alphabetically
        $socialFields = ['bluesky', 'mastodon', 'linkedin', 'github', 'instagram'];
        $sorted = [];
        $other = [];

        foreach ($detectedFields as $key => $field) {
            if (in_array($key, $socialFields)) {
                $sorted[$key] = $field;
            } else {
                $other[$key] = $field;
            }
        }

        // Sort other fields alphabetically by label
        uasort($other, fn($a, $b) => strcasecmp($a['label'], $b['label']));

        return array_values(array_merge($sorted, $other));
    }

}
