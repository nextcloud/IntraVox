<?php
declare(strict_types=1);

namespace OCA\IntraVox\Service;

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
        private ?IUserStatusManager $userStatusManager = null,
        private ?IConfig $config = null,
        private ?IActivityManager $activityManager = null,
        private ?IDBConnection $db = null,
        ?ICacheFactory $cacheFactory = null
    ) {
        if ($cacheFactory !== null && $cacheFactory->isAvailable()) {
            $this->cache = $cacheFactory->createDistributed('intravox-people');
        }
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
            foreach ($groupValues as $groupId) {
                $group = $this->groupManager->get($groupId);
                if ($group !== null) {
                    foreach ($group->getUsers() as $user) {
                        if (!isset($seen[$user->getUID()])) {
                            $seen[$user->getUID()] = true;
                            $profile = $this->buildUserProfile($user);
                            // Apply other filters
                            if (empty($otherFilters) || $this->matchesFilters($profile, $otherFilters, $operator)) {
                                $users[] = $profile;
                            }
                        }
                    }
                }
            }
        } else {
            // No group filter - need to iterate all users (less efficient).
            // Apply a hard cap to prevent OOM/timeout on large instances.
            $maxToCollect = $returnTotal ? self::MAX_FILTER_SCAN : min(($limit + $offset) * 2, self::MAX_FILTER_SCAN);

            // Try cache for this filter combination
            // Cache is shared, but results depend on which users the requesting user can see
            // (Nextcloud account visibility settings). Cache is short-lived (1h) and shared
            // to avoid N×5k profile builds, which is an acceptable trade-off.
            $filterCacheKey = 'filter_shared_' . md5(json_encode($filters) . $operator . $sortBy . $sortOrder);
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
                    if ($this->matchesFilters($profile, $filters, $operator)) {
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
     * Build user profile array from IUser
     *
     * @param IUser $user The user object
     * @return array User profile data
     */
    private function buildUserProfile(IUser $user): array {
        $profile = [
            'uid' => $user->getUID(),
            'displayName' => $user->getDisplayName(),
            'email' => $user->getEMailAddress(),
            'avatarUrl' => $this->urlGenerator->linkToRouteAbsolute('core.avatar.getAvatar', [
                'userId' => $user->getUID(),
                'size' => 128,
            ]),
            'groups' => $this->getGroupsForUser($user->getUID()),
            'status' => $this->getUserStatus($user->getUID()),
        ];

        // Get account data from IAccountManager
        try {
            $account = $this->accountManager->getAccount($user);

            // Add standard properties
            foreach (array_keys(self::STANDARD_PROPERTIES) as $property) {
                try {
                    $prop = $account->getProperty($property);
                    $value = $prop->getValue();
                    // Use a simplified key name
                    $key = $this->propertyToKey($property);

                    // Normalize birthdate to ISO 8601 (YYYY-MM-DD) format
                    // Nextcloud may store locale-specific formats (e.g. DD-MM-YYYY)
                    if ($property === IAccountManager::PROPERTY_BIRTHDATE && !empty($value)) {
                        $value = $this->normalizeDateToISO($value);
                    }

                    $profile[$key] = $value ?: null;
                } catch (\Exception $e) {
                    // Property not available for this user
                }
            }

            // Try to get additional properties (from LDAP/OIDC)
            try {
                $allProperties = $account->getProperties();
                foreach ($allProperties as $prop) {
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
        // These are stored by the intravox:add-demo-fields command or LDAP/OIDC sync
        if ($this->config !== null) {
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
     * @return array Array of group IDs
     */
    private function getGroupsForUser(string $userId): array {
        $groups = [];
        foreach ($this->groupManager->getUserGroups($this->userManager->get($userId)) as $group) {
            $groups[] = $group->getGID();
        }
        return $groups;
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
     * Normalize a date string to ISO 8601 (YYYY-MM-DD) format.
     * Handles locale-specific formats like DD-MM-YYYY, DD/MM/YYYY, DD.MM.YYYY.
     *
     * @param string $value The date string to normalize
     * @return string|null ISO date string or null if unparseable
     */
    private function normalizeDateToISO(string $value): ?string {
        // Already in ISO format (YYYY-MM-DD)
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            return $value;
        }

        // DD-MM-YYYY or DD/MM/YYYY or DD.MM.YYYY (European formats)
        if (preg_match('/^(\d{1,2})[\/.\-](\d{1,2})[\/.\-](\d{4})$/', $value, $matches)) {
            $date = \DateTime::createFromFormat('d-m-Y', $matches[1] . '-' . $matches[2] . '-' . $matches[3]);
            if ($date !== false) {
                return $date->format('Y-m-d');
            }
        }

        // Fallback: let PHP try to parse it
        try {
            $date = new \DateTime($value);
            return $date->format('Y-m-d');
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Check if a user profile matches the given filters
     *
     * @param array $profile User profile
     * @param array $filters Array of filters
     * @param string $operator 'AND' or 'OR'
     * @return bool True if matches
     */
    private function matchesFilters(array $profile, array $filters, string $operator): bool {
        if (empty($filters)) {
            return true;
        }

        $results = [];
        foreach ($filters as $filter) {
            $fieldName = $filter['fieldName'];
            $filterOperator = $filter['operator'];
            // Support both 'value' and 'values' from frontend
            // Prefer non-empty 'values' array, fallback to 'value'
            $filterValue = (!empty($filter['values']) && is_array($filter['values']))
                ? $filter['values']
                : ($filter['value'] ?? null);

            // Get the actual value from profile
            $actualValue = $profile[$fieldName] ?? null;

            // Special handling for group filter
            if ($fieldName === 'group') {
                $actualValue = $profile['groups'] ?? [];
            }

            $results[] = $this->matchesSingleFilter($actualValue, $filterOperator, $filterValue);
        }

        if ($operator === 'AND') {
            return !in_array(false, $results, true);
        } else {
            return in_array(true, $results, true);
        }
    }

    /**
     * Check if a value matches a single filter condition
     *
     * @param mixed $actualValue The actual value from the profile
     * @param string $operator The filter operator
     * @param mixed $filterValue The filter value to match against
     * @return bool True if matches
     */
    private function matchesSingleFilter(mixed $actualValue, string $operator, mixed $filterValue): bool {
        switch ($operator) {
            case 'equals':
                // For arrays (like groups), check if filterValue is in the array
                if (is_array($actualValue)) {
                    return in_array($filterValue, $actualValue, true);
                }
                return $actualValue === $filterValue;

            case 'contains':
                if (is_string($actualValue) && is_string($filterValue)) {
                    return stripos($actualValue, $filterValue) !== false;
                }
                return false;

            case 'not_contains':
                if (is_string($actualValue) && is_string($filterValue)) {
                    return stripos($actualValue, $filterValue) === false;
                }
                return true;

            case 'in':
                // Value should be in the filter array
                $filterValues = is_array($filterValue) ? $filterValue : [$filterValue];
                if (is_array($actualValue)) {
                    // Check if any of the actual values are in the filter values
                    return !empty(array_intersect($actualValue, $filterValues));
                }
                return in_array($actualValue, $filterValues, true);

            case 'not_empty':
                if (is_array($actualValue)) {
                    return !empty($actualValue);
                }
                return $actualValue !== null && $actualValue !== '';

            case 'empty':
                if (is_array($actualValue)) {
                    return empty($actualValue);
                }
                return $actualValue === null || $actualValue === '';

            case 'is_today':
                // Compare month and day only (for birthdays)
                if (empty($actualValue)) {
                    return false;
                }
                try {
                    $date = new \DateTime($actualValue);
                    $today = new \DateTime();
                    return $date->format('m-d') === $today->format('m-d');
                } catch (\Exception $e) {
                    return false;
                }

            case 'within_next_days':
                // Check if month-day falls within next X days (for upcoming birthdays)
                if (empty($actualValue) || !is_numeric($filterValue)) {
                    return false;
                }
                try {
                    $date = new \DateTime($actualValue);
                    $today = new \DateTime();
                    $currentYear = (int)$today->format('Y');

                    // Create a date in the current year with the same month-day
                    $birthdayThisYear = new \DateTime($currentYear . '-' . $date->format('m-d'));

                    // If the birthday already passed this year, check next year
                    if ($birthdayThisYear < $today) {
                        $birthdayThisYear = new \DateTime(($currentYear + 1) . '-' . $date->format('m-d'));
                    }

                    $daysUntil = (int)$today->diff($birthdayThisYear)->days;
                    return $daysUntil <= (int)$filterValue;
                } catch (\Exception $e) {
                    return false;
                }

            default:
                return false;
        }
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

        $this->userManager->callForAllUsers(function (IUser $user) use (&$sampleCount, &$detectedFields, $knownFields) {
            if ($sampleCount >= 5) {
                return;
            }
            $sampleCount++;

            try {
                $account = $this->accountManager->getAccount($user);
                $allProperties = $account->getProperties();
                foreach ($allProperties as $prop) {
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
