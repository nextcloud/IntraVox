<?php

declare(strict_types=1);

namespace OCA\IntraVox\Service;

use enshrined\svgSanitize\Sanitizer;
use OCA\IntraVox\AppInfo\Application;
use OCP\Http\Client\IClientService;
use OCP\ICacheFactory;
use OCP\ICache;
use OCP\IConfig;
use OCP\Security\ICrypto;
use Psr\Log\LoggerInterface;

/**
 * Service for fetching and normalizing external feeds.
 *
 * Supports RSS/Atom feeds and LMS APIs (Moodle, Canvas, Brightspace).
 * All sources are normalized to a common FeedItem format.
 */
class FeedReaderService {
    private const CACHE_TTL = 900; // 15 minutes
    private const HTTP_TIMEOUT = 5;
    private const MAX_ITEMS = 50;
    private const EXCERPT_LENGTH = 300;
    private const MAX_RESPONSE_SIZE = 10 * 1024 * 1024; // 10 MB

    /** @var array Connection type presets with default configuration */
    public const PRESETS = [
        'moodle' => ['label' => 'Moodle', 'hasContentTypes' => true],
        'canvas' => ['label' => 'Canvas', 'hasContentTypes' => true],
        'brightspace' => ['label' => 'Brightspace', 'hasContentTypes' => true],
        'jira' => [
            'label' => 'Jira',
            'authMethod' => 'bearer',
            'customEndpoint' => '/rest/api/2/search?jql=ORDER+BY+updated+DESC&maxResults=20',
            'responseMapping' => ['items' => 'issues', 'title' => 'fields.summary', 'url' => 'key', 'excerpt' => 'fields.description', 'date' => 'fields.updated', 'author' => 'fields.assignee.displayName'],
        ],
        'confluence' => [
            'label' => 'Confluence',
            'authMethod' => 'bearer',
            'customEndpoint' => '/rest/api/content?type=page&orderby=lastmodified&limit=10&expand=history.lastUpdated',
            'responseMapping' => ['items' => 'results', 'title' => 'title', 'url' => '_links.webui', 'date' => 'history.lastUpdated.when', 'author' => 'history.lastUpdated.by.displayName'],
        ],
        'sharepoint' => [
            'label' => 'SharePoint (Graph API)',
            'authMethod' => 'client_credentials',
            'customEndpoint' => '/v1.0/sites/root/pages?$orderby=lastModifiedDateTime+desc&$top=10',
            'responseMapping' => ['items' => 'value', 'title' => 'title', 'url' => 'webUrl', 'excerpt' => 'description', 'date' => 'lastModifiedDateTime', 'image' => 'thumbnailWebUrl', 'author' => 'lastModifiedBy.user.displayName'],
        ],
        'openproject' => [
            'label' => 'OpenProject',
            'authMethod' => 'basic',
            'customEndpoint' => '/api/v3/work_packages?sortBy=[["updatedAt","desc"]]&pageSize=20',
            'responseMapping' => ['items' => '_embedded.elements', 'title' => 'subject', 'url' => '_links.self.href', 'excerpt' => 'description.raw', 'date' => 'updatedAt', 'author' => '_links.assignee.title'],
        ],
        'afas' => [
            'label' => 'AFAS',
            'authMethod' => 'bearer',
            'customEndpoint' => '/profitrestservices/connectors/',
            'responseMapping' => ['items' => 'rows', 'title' => 'Naam'],
        ],
        'topdesk' => [
            'label' => 'TOPdesk',
            'authMethod' => 'bearer',
            'customEndpoint' => '/tas/api/incidents?pageSize=20&order_by=creation_date+desc',
            'responseMapping' => ['items' => '', 'title' => 'briefDescription', 'url' => '_links.self.href', 'date' => 'creationDate', 'author' => 'caller.dynamicName'],
        ],
        'custom' => ['label' => 'Custom'],
    ];

    private ?ICache $cache = null;
    private string $acceptLanguage = 'en';

    private const MAX_IMAGE_SIZE = 5 * 1024 * 1024; // 5 MB

    public function __construct(
        private IClientService $httpClient,
        private ICacheFactory $cacheFactory,
        private IConfig $config,
        private ICrypto $crypto,
        private LoggerInterface $logger,
        private LmsTokenService $lmsTokenService,
        private LmsOAuthService $lmsOAuthService,
        private ?OidcTokenBridge $oidcTokenBridge = null,
    ) {
        if ($this->cacheFactory->isAvailable()) {
            $this->cache = $this->cacheFactory->createDistributed('intravox-feeds');
        }
    }

    /**
     * Fetch feed items from any supported source type.
     *
     * @param string $sourceType One of: rss, moodle, canvas, brightspace
     * @param array $config Source-specific configuration
     * @param int $limit Maximum items to return
     * @param string|null $userId Current user ID for personalized feeds
     * @return array{items: array, source: string, cached: bool}
     */
    /**
     * @param string $sortBy Sort field: 'date' (default), 'title'
     * @param string $sortOrder Sort order: 'desc' (default), 'asc'
     * @param string $filterKeyword Filter items by keyword in title/excerpt (case-insensitive)
     */
    public function fetchFeed(string $sourceType, array $config, int $limit = 5, ?string $userId = null, string $sortBy = 'date', string $sortOrder = 'desc', string $filterKeyword = ''): array {
        $this->acceptLanguage = $this->buildAcceptLanguage($userId);
        $limit = min(max($limit, 1), self::MAX_ITEMS);
        $cacheKey = $this->buildCacheKey($sourceType, $config, $userId);

        // Try cache first
        if ($this->cache !== null) {
            $cached = $this->cache->get($cacheKey);
            if ($cached !== null) {
                $decoded = json_decode($cached, true);
                if ($decoded !== null) {
                    $decoded['items'] = $this->filterAndSortItems($decoded['items'], $sortBy, $sortOrder, $filterKeyword);
                    $decoded['items'] = array_slice($decoded['items'], 0, $limit);
                    $decoded['cached'] = true;
                    return $decoded;
                }
            }
        }

        // Circuit breaker: skip sources that have repeatedly failed
        $circuitKey = 'circuit_' . $cacheKey;
        if ($this->cache !== null) {
            $circuitData = $this->cache->get($circuitKey);
            if ($circuitData !== null) {
                $circuit = json_decode($circuitData, true);
                if (is_array($circuit) && ($circuit['failures'] ?? 0) >= 3) {
                    return [
                        'items' => [],
                        'source' => '',
                        'cached' => false,
                        'error' => 'Source temporarily unavailable (circuit breaker open)',
                    ];
                }
            }
        }

        // Singleflight: use a distributed lock to prevent thundering herd on cache miss.
        // Only the first request fetches; concurrent requests wait and read from cache.
        // Use a unique request ID to detect if WE set the lock (avoids non-atomic get+set race).
        $lockKey = 'lock_' . $cacheKey;
        $requestId = bin2hex(random_bytes(8));
        $acquired = false;
        if ($this->cache !== null) {
            // Set lock unconditionally, then check if our value won
            $existing = $this->cache->get($lockKey);
            if ($existing === null) {
                $this->cache->set($lockKey, $requestId, 30); // 30s lock TTL
                // Re-read to verify we won (reduces race window significantly)
                $acquired = $this->cache->get($lockKey) === $requestId;
            }

            if (!$acquired) {
                // Another request is fetching — wait for cache to be populated
                for ($i = 0; $i < 50; $i++) { // max 5 seconds (50 × 100ms)
                    usleep(100_000); // 100ms
                    $cached = $this->cache->get($cacheKey);
                    if ($cached !== null) {
                        $decoded = json_decode($cached, true);
                        if ($decoded !== null) {
                            $decoded['items'] = $this->filterAndSortItems($decoded['items'], $sortBy, $sortOrder, $filterKeyword);
                            $decoded['items'] = array_slice($decoded['items'], 0, $limit);
                            $decoded['cached'] = true;
                            return $decoded;
                        }
                    }
                }
                // Lock holder might have failed — fall through and fetch ourselves
            }
        }

        try {
            $result = match ($sourceType) {
                'rss' => $this->fetchRss($config['url'] ?? ''),
                default => $this->fetchConnection($sourceType, $config, $userId),
            };

            // Resolve connection type for metadata enrichment
            $connectionType = 'rss';
            if ($sourceType !== 'rss') {
                $conn = $this->getConnection($config['connectionId'] ?? '');
                $connectionType = $conn['type'] ?? 'custom';
            }

            // Decode HTML entities and enrich items with connection/file type metadata
            foreach ($result['items'] as &$item) {
                foreach (['title', 'author'] as $field) {
                    if (!empty($item[$field])) {
                        $item[$field] = html_entity_decode($item[$field], ENT_QUOTES | ENT_HTML5, 'UTF-8');
                    }
                }
                $item['connectionType'] = $item['connectionType'] ?? $connectionType;
                $item['fileType'] = $item['fileType'] ?? $this->detectFileType($item['title'] ?? '');
            }
            unset($item);

            // Cache the full unfiltered result
            if ($this->cache !== null) {
                $this->cache->set($cacheKey, json_encode($result), self::CACHE_TTL);
                $this->cache->remove($lockKey);
                $this->cache->remove($circuitKey); // Reset circuit breaker on success
            }

            // Apply filter and sort after caching (so cache contains all items)
            $result['items'] = $this->filterAndSortItems($result['items'], $sortBy, $sortOrder, $filterKeyword);
            $result['items'] = array_slice($result['items'], 0, $limit);
            $result['cached'] = false;
            return $result;
        } catch (\Exception $e) {
            // Release singleflight lock on failure so other requests can retry
            if ($this->cache !== null) {
                $this->cache->remove($lockKey);

                // Record failure for circuit breaker
                $circuitData = $this->cache->get($circuitKey);
                $circuit = $circuitData !== null ? json_decode($circuitData, true) : [];
                $failures = ($circuit['failures'] ?? 0) + 1;
                $this->cache->set($circuitKey, json_encode(['failures' => $failures]), 300); // 5 min cooldown
            }
            $this->logger->error('IntraVox: Feed fetch failed', [
                'sourceType' => $sourceType,
                'error' => $e->getMessage(),
            ]);
            return [
                'items' => [],
                'source' => '',
                'cached' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Filter items by keyword and sort by field/order.
     */
    private function filterAndSortItems(array $items, string $sortBy, string $sortOrder, string $filterKeyword): array {
        // Filter by keyword (case-insensitive search in title + excerpt)
        if ($filterKeyword !== '') {
            $keyword = mb_strtolower($filterKeyword);
            $items = array_values(array_filter($items, function ($item) use ($keyword) {
                $title = mb_strtolower($item['title'] ?? '');
                $excerpt = mb_strtolower($item['excerpt'] ?? '');
                $author = mb_strtolower($item['author'] ?? '');
                return str_contains($title, $keyword) || str_contains($excerpt, $keyword) || str_contains($author, $keyword);
            }));
        }

        // Sort
        usort($items, function ($a, $b) use ($sortBy, $sortOrder) {
            if ($sortBy === 'title') {
                $cmp = strcasecmp($a['title'] ?? '', $b['title'] ?? '');
            } else {
                // Default: sort by date
                $cmp = strtotime($a['date'] ?? '0') - strtotime($b['date'] ?? '0');
            }
            return $sortOrder === 'asc' ? $cmp : -$cmp;
        });

        return $items;
    }

    /**
     * Resolve the best available token for a connection + user.
     *
     * Priority: OIDC auto-connect > per-user OAuth2/manual > admin fallback
     *
     * @return array|null {token: string, source: string} or null if no token available
     */
    public function resolveToken(string $connectionId, ?string $userId): ?array {
        $connection = $this->getConnection($connectionId);
        if ($connection === null) {
            return null;
        }

        // Client credentials flow (app-level, e.g. SharePoint/Graph API)
        if (($connection['authMethod'] ?? '') === 'client_credentials') {
            return $this->acquireClientCredentialsToken($connection);
        }

        $authMode = $connection['authMode'] ?? 'token';

        // Legacy mode: use admin token (only for authenticated users, not public shares)
        if ($authMode === 'token') {
            if ($userId === null) {
                return null; // Don't expose admin token to public/anonymous requests
            }
            return $this->decryptAdminToken($connection);
        }

        // Try user-level tokens if we have a userId
        if ($userId !== null) {
            // 1. Try OIDC auto-connect
            if (($connection['oidcAutoConnect'] ?? false) && $this->oidcTokenBridge !== null) {
                $oidcToken = $this->oidcTokenBridge->getToken();
                if ($oidcToken !== null && !empty($oidcToken['access_token'])) {
                    return [
                        'token' => $oidcToken['access_token'],
                        'source' => 'oidc',
                    ];
                }
            }

            // 2. Try per-user stored token (OAuth2 or manual)
            $userToken = $this->lmsTokenService->getUserToken($userId, $connectionId);
            if ($userToken !== null) {
                // Check if OAuth2 token needs refresh
                if ($userToken['token_type'] === 'oauth2' && $this->lmsTokenService->isTokenExpired($userId, $connectionId)) {
                    $refreshed = $this->tryRefreshToken($connection, $userToken, $userId, $connectionId);
                    if ($refreshed !== null) {
                        return $refreshed;
                    }
                    // Refresh failed — token is invalid, delete it
                    $this->lmsTokenService->deleteUserToken($userId, $connectionId);
                } else {
                    return [
                        'token' => $userToken['access_token'],
                        'source' => $userToken['token_type'],
                    ];
                }
            }
        }

        // 3. Fall back to admin token (only for "both" mode, authenticated users only)
        if ($authMode === 'both' && $userId !== null) {
            return $this->decryptAdminToken($connection);
        }

        return null;
    }

    /**
     * Route a connection-based feed request to the appropriate fetch strategy.
     * LMS types (moodle, canvas, brightspace) use dedicated logic.
     * All other types use the generic REST API approach.
     */
    private function fetchConnection(string $sourceType, array $config, ?string $userId): array {
        $connectionId = $config['connectionId'] ?? '';

        // Check if connection exists at all (including inactive)
        $connectionAny = $this->getConnection($connectionId, true);
        if ($connectionAny === null) {
            throw new \RuntimeException('Connection not found');
        }

        // Check if connection is active
        $connection = $this->getConnection($connectionId);
        if ($connection === null) {
            throw new \RuntimeException('Connection is inactive');
        }

        $connectionType = $connection['type'] ?? 'custom';

        return match ($connectionType) {
            'moodle' => $this->fetchMoodle($config, $userId),
            'canvas' => $this->fetchCanvas($config, $userId),
            'brightspace' => $this->fetchBrightspace($config, $userId),
            'sharepoint' => $this->fetchSharePoint($config, $userId),
            default => $this->fetchGenericRestApi($config, $userId),
        };
    }

    /**
     * Make a POST request to Moodle's web service API.
     * Uses POST body for the token instead of URL query string to prevent token leakage in logs.
     */
    private function moodlePost($client, string $baseUrl, string $token, string $wsfunction, array $extraParams = []) {
        $params = array_merge([
            'wstoken' => $token,
            'moodlewsrestformat' => 'json',
            'wsfunction' => $wsfunction,
        ], $extraParams);

        return $client->post($baseUrl . '/webservice/rest/server.php', [
            'timeout' => self::HTTP_TIMEOUT,
            'body' => $params,
            'headers' => ['Accept-Language' => $this->acceptLanguage],
        ]);
    }

    /**
     * Convert a Moodle pluginfile URL to a webservice-accessible URL with token.
     * Moodle's /pluginfile.php requires session auth; /webservice/pluginfile.php accepts a token parameter.
     */
    private function moodleFileUrl(?string $url, string $baseUrl, string $token): ?string {
        if ($url === null) {
            return null;
        }
        // Replace /pluginfile.php/ with /webservice/pluginfile.php/ and append token
        if (str_contains($url, '/pluginfile.php/')) {
            $url = str_replace('/pluginfile.php/', '/webservice/pluginfile.php/', $url);
            $url .= (str_contains($url, '?') ? '&' : '?') . 'token=' . $token;
        }
        return $url;
    }

    /**
     * Safely decrypt the admin token from a connection config.
     */
    /**
     * Sanitize a course/org unit ID. Returns null if empty or invalid.
     */
    /**
     * Fetch available courses for a connection, using the current user's token.
     *
     * @return array{courses: array<array{id: string, name: string}>}
     */
    public function getCourses(string $connectionId, ?string $userId): array {
        $connection = $this->getConnection($connectionId);
        if ($connection === null) {
            throw new \RuntimeException('Connection not found');
        }

        // Only use the user's personal token for course listing — never admin token.
        // This ensures users only see courses they have access to.
        if ($userId === null) {
            return ['courses' => []];
        }

        $userToken = $this->lmsTokenService->getUserToken($userId, $connectionId);
        if ($userToken === null) {
            return ['courses' => []];
        }

        $token = $userToken['access_token'];
        $baseUrl = rtrim($connection['baseUrl'], '/');
        $type = $connection['type'] ?? '';

        try {
            return match ($type) {
                'canvas' => $this->getCanvasCourses($baseUrl, $token),
                'moodle' => $this->getMoodleCourses($baseUrl, $token),
                'brightspace' => $this->getBrightspaceCourses($baseUrl, $token),
                default => ['courses' => []],
            };
        } catch (\Exception $e) {
            $this->logger->warning('IntraVox: Failed to fetch courses', [
                'connectionId' => $connectionId,
                'type' => $type,
            ]);
            return ['courses' => []];
        }
    }

    private function getCanvasCourses(string $baseUrl, string $token): array {
        $client = $this->httpClient->newClient();
        $response = $client->get($baseUrl . '/api/v1/courses?' . http_build_query([
            'per_page' => 100,
            'enrollment_state' => 'active',
        ]), [
            'timeout' => self::HTTP_TIMEOUT,
            'headers' => [
                'Authorization' => 'Bearer ' . $token,
                'Accept' => 'application/json',
            ],
        ]);

        $data = json_decode($response->getBody(), true);
        if (!is_array($data)) {
            return ['courses' => []];
        }

        $courses = [];
        foreach ($data as $course) {
            if (isset($course['id'])) {
                $courses[] = [
                    'id' => (string)$course['id'],
                    'name' => $course['name'] ?? $course['course_code'] ?? 'Course ' . $course['id'],
                ];
            }
        }

        usort($courses, fn($a, $b) => strcasecmp($a['name'], $b['name']));
        return ['courses' => $courses];
    }

    private function getMoodleCourses(string $baseUrl, string $token): array {
        $client = $this->httpClient->newClient();
        $response = $this->moodlePost($client, $baseUrl, $token, 'core_course_get_courses');

        $data = json_decode($response->getBody(), true);
        if (!is_array($data) || isset($data['exception'])) {
            return ['courses' => []];
        }

        $courses = [];
        foreach ($data as $course) {
            // Skip Moodle's site-level course (id=1)
            if (($course['id'] ?? 0) <= 1) {
                continue;
            }
            $courses[] = [
                'id' => (string)$course['id'],
                'name' => $course['fullname'] ?? $course['shortname'] ?? 'Course ' . $course['id'],
            ];
        }

        usort($courses, fn($a, $b) => strcasecmp($a['name'], $b['name']));
        return ['courses' => $courses];
    }

    /**
     * Get available forums for a Moodle course.
     */
    public function getMoodleForums(string $connectionId, string $courseId): array {
        $connection = $this->getConnection($connectionId);
        if ($connection === null) {
            return ['forums' => []];
        }

        $baseUrl = rtrim($connection['baseUrl'], '/');
        try {
            $token = !empty($connection['token']) ? $this->crypto->decrypt($connection['token']) : '';
        } catch (\Exception $e) {
            return ['forums' => []];
        }

        $client = $this->httpClient->newClient();
        $response = $this->moodlePost($client, $baseUrl, $token, 'mod_forum_get_forums_by_courses', [
            'courseids[0]' => $courseId,
        ]);

        $data = json_decode($response->getBody(), true);
        if (!is_array($data) || isset($data['exception'])) {
            return ['forums' => []];
        }

        $forums = [];
        foreach ($data as $forum) {
            $forums[] = [
                'id' => (string)($forum['id'] ?? ''),
                'name' => $forum['name'] ?? 'Forum',
            ];
        }

        usort($forums, fn($a, $b) => strcasecmp($a['name'], $b['name']));
        return ['forums' => $forums];
    }

    private function getBrightspaceCourses(string $baseUrl, string $token): array {
        $client = $this->httpClient->newClient();
        $response = $client->get($baseUrl . '/d2l/api/lp/1.43/enrollments/myenrollments/?' . http_build_query([
            'isActive' => 'true',
            'pageSize' => 100,
        ]), [
            'timeout' => self::HTTP_TIMEOUT,
            'headers' => [
                'Authorization' => 'Bearer ' . $token,
                'Accept' => 'application/json',
            ],
        ]);

        $data = json_decode($response->getBody(), true);
        $enrollments = $data['Items'] ?? [];
        if (!is_array($enrollments)) {
            return ['courses' => []];
        }

        $courses = [];
        foreach ($enrollments as $enrollment) {
            $orgUnit = $enrollment['OrgUnit'] ?? [];
            $orgUnitId = $orgUnit['Id'] ?? '';
            if (empty($orgUnitId)) {
                continue;
            }
            $courses[] = [
                'id' => (string)$orgUnitId,
                'name' => $orgUnit['Name'] ?? 'Course ' . $orgUnitId,
            ];
        }

        usort($courses, fn($a, $b) => strcasecmp($a['name'], $b['name']));
        return ['courses' => $courses];
    }

    private function sanitizeCourseId(?string $courseId): ?string {
        if (empty($courseId)) {
            return null;
        }
        // Only allow alphanumeric, underscore, hyphen (max 64 chars)
        if (!preg_match('/^[a-zA-Z0-9_-]{1,64}$/', $courseId)) {
            $this->logger->warning('IntraVox: Invalid courseId rejected', ['courseId' => substr($courseId, 0, 20)]);
            return null;
        }
        return $courseId;
    }

    private function decryptAdminToken(array $connection): ?array {
        if (empty($connection['token'])) {
            return null;
        }
        try {
            return [
                'token' => $this->crypto->decrypt($connection['token']),
                'source' => 'admin',
            ];
        } catch (\Exception $e) {
            $this->logger->error('IntraVox: Failed to decrypt admin token', [
                'connectionId' => $connection['id'] ?? 'unknown',
            ]);
            return null;
        }
    }

    /**
     * Try to refresh an expired OAuth2 token.
     */
    private function tryRefreshToken(array $connection, array $userToken, string $userId, string $connectionId): ?array {
        if (empty($userToken['refresh_token'])) {
            return null;
        }

        try {
            $refreshed = $this->lmsOAuthService->refreshToken($connection, $userToken['refresh_token']);

            $expiresAt = null;
            if (isset($refreshed['expires_in']) && $refreshed['expires_in'] > 0) {
                $expiresAt = new \DateTime('+' . $refreshed['expires_in'] . ' seconds');
            }

            $this->lmsTokenService->saveUserToken(
                $userId,
                $connectionId,
                $refreshed['access_token'],
                $refreshed['refresh_token'] ?? $userToken['refresh_token'],
                'oauth2',
                $expiresAt,
            );

            return [
                'token' => $refreshed['access_token'],
                'source' => 'oauth2',
            ];
        } catch (\Exception $e) {
            $this->logger->warning('IntraVox: Token refresh failed', [
                'connectionId' => $connectionId,
                'userId' => $userId,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Acquire an access token using OAuth2 client_credentials flow.
     * Used for app-level authentication (e.g., Microsoft Graph API for SharePoint).
     *
     * Tokens are cached in the distributed cache. No refresh_token is used —
     * when the token expires, a new one is requested with the same credentials.
     *
     * @return array|null {token: string, source: string}
     */
    private function acquireClientCredentialsToken(array $connection): ?array {
        $tenantId = $connection['tenantId'] ?? '';
        $clientId = $connection['clientId'] ?? '';
        $encryptedSecret = $connection['clientSecret'] ?? '';

        if (empty($tenantId) || empty($clientId) || empty($encryptedSecret)) {
            return null;
        }

        $connectionId = $connection['id'] ?? '';
        $cacheKey = 'cc_token_' . md5($connectionId);

        // Check cache first
        if ($this->cache !== null) {
            $cached = $this->cache->get($cacheKey);
            if ($cached !== null) {
                return ['token' => $cached, 'source' => 'client_credentials'];
            }
        }

        try {
            $clientSecret = $this->crypto->decrypt($encryptedSecret);
        } catch (\Exception $e) {
            $this->logger->error('IntraVox: Failed to decrypt client secret for client_credentials', [
                'connectionId' => $connectionId,
            ]);
            return null;
        }

        $tokenUrl = 'https://login.microsoftonline.com/' . urlencode($tenantId) . '/oauth2/v2.0/token';

        try {
            $client = $this->httpClient->newClient();
            $response = $client->post($tokenUrl, [
                'timeout' => self::HTTP_TIMEOUT,
                'body' => [
                    'grant_type' => 'client_credentials',
                    'client_id' => $clientId,
                    'client_secret' => $clientSecret,
                    'scope' => 'https://graph.microsoft.com/.default',
                ],
            ]);

            $data = json_decode($response->getBody(), true);
            if (!is_array($data) || empty($data['access_token'])) {
                $this->logger->error('IntraVox: client_credentials token response missing access_token', [
                    'connectionId' => $connectionId,
                ]);
                return null;
            }

            $accessToken = $data['access_token'];
            $expiresIn = isset($data['expires_in']) ? (int) $data['expires_in'] : 3600;

            // Cache with safety buffer (2 minutes before actual expiry, minimum 60s)
            if ($this->cache !== null) {
                $cacheTtl = max(60, $expiresIn - 120);
                $this->cache->set($cacheKey, $accessToken, $cacheTtl);
            }

            return ['token' => $accessToken, 'source' => 'client_credentials'];
        } catch (\Exception $e) {
            $this->logger->error('IntraVox: client_credentials token request failed', [
                'connectionId' => $connectionId,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Fetch and parse an RSS or Atom feed.
     */
    private function fetchRss(string $url): array {
        if (empty($url)) {
            throw new \InvalidArgumentException('Feed URL is required');
        }
        $this->validateUrl($url);

        $client = $this->httpClient->newClient();
        $response = $client->get($url, [
            'timeout' => self::HTTP_TIMEOUT,
            'headers' => [
                'Accept' => 'application/rss+xml, application/atom+xml, application/xml, text/xml',
                'Accept-Language' => $this->acceptLanguage,
                'User-Agent' => 'IntraVox/1.0 (Nextcloud Feed Reader)',
            ],
        ]);
        $this->validateHttpResponse($response, 'RSS feed');

        $body = $response->getBody();
        if (strlen($body) > self::MAX_RESPONSE_SIZE) {
            throw new \RuntimeException('Feed response too large');
        }
        $xml = @simplexml_load_string($body, 'SimpleXMLElement', LIBXML_NOCDATA | LIBXML_NONET);
        if ($xml === false) {
            throw new \RuntimeException('Failed to parse feed XML');
        }

        return $this->parseXmlFeed($xml);
    }

    /**
     * Parse RSS 2.0 or Atom feed XML into normalized items.
     */
    private function parseXmlFeed(\SimpleXMLElement $xml): array {
        $items = [];
        $source = '';
        $feedImage = null;

        // Detect feed type and parse accordingly
        if (isset($xml->channel)) {
            // RSS 2.0
            $source = (string)($xml->channel->title ?? '');
            foreach ($xml->channel->item as $item) {
                $items[] = $this->normalizeRssItem($item);
            }

            // Extract feed-level image
            if (isset($xml->channel->image->url)) {
                $feedImage = (string)$xml->channel->image->url;
            }
            if ($feedImage === null) {
                $itunes = $xml->channel->children('itunes', true);
                if (isset($itunes->image) && isset($itunes->image['href'])) {
                    $feedImage = (string)$itunes->image['href'];
                }
            }
        } elseif ($xml->getName() === 'feed') {
            // Atom
            $source = (string)($xml->title ?? '');
            foreach ($xml->entry as $entry) {
                $items[] = $this->normalizeAtomEntry($entry);
            }

            // Extract feed-level image
            if (isset($xml->icon)) {
                $feedImage = (string)$xml->icon;
            } elseif (isset($xml->logo)) {
                $feedImage = (string)$xml->logo;
            }
        } else {
            throw new \RuntimeException('Unrecognized feed format');
        }

        // Sort by date descending
        usort($items, fn($a, $b) => strtotime($b['date']) - strtotime($a['date']));

        return [
            'items' => array_slice($items, 0, self::MAX_ITEMS),
            'source' => $source,
            'feedImage' => $this->proxyImageUrl($feedImage),
        ];
    }

    private function normalizeRssItem(\SimpleXMLElement $item): array {
        $content = (string)($item->description ?? '');

        // Try to extract image from content or enclosure
        $image = null;
        if (isset($item->enclosure) && str_starts_with((string)$item->enclosure['type'], 'image/')) {
            $image = (string)$item->enclosure['url'];
        }
        if ($image === null) {
            $image = $this->extractImageFromHtml($content);
        }

        // Try media:content namespace
        if ($image === null) {
            $media = $item->children('media', true);
            if (isset($media->content) && isset($media->content['url'])) {
                $image = (string)$media->content['url'];
            }
            if ($image === null && isset($media->thumbnail) && isset($media->thumbnail['url'])) {
                $image = (string)$media->thumbnail['url'];
            }
        }

        return [
            'id' => (string)($item->guid ?? $item->link ?? md5((string)$item->title)),
            'title' => (string)($item->title ?? ''),
            'url' => (string)($item->link ?? ''),
            'excerpt' => $this->sanitizeExcerpt($content),
            'image' => $this->proxyImageUrl($image),
            'date' => $this->parseDate((string)($item->pubDate ?? '')),
            'source' => '',
            'author' => (string)($item->author ?? $item->children('dc', true)->creator ?? ''),
        ];
    }

    private function normalizeAtomEntry(\SimpleXMLElement $entry): array {
        // Find the best link
        $url = '';
        foreach ($entry->link as $link) {
            $rel = (string)($link['rel'] ?? 'alternate');
            if ($rel === 'alternate' || $rel === '') {
                $url = (string)$link['href'];
                break;
            }
        }
        if (empty($url) && isset($entry->link['href'])) {
            $url = (string)$entry->link['href'];
        }

        $content = (string)($entry->content ?? $entry->summary ?? '');
        $image = $this->extractImageFromHtml($content);

        return [
            'id' => (string)($entry->id ?? $url ?? md5((string)$entry->title)),
            'title' => (string)($entry->title ?? ''),
            'url' => $url,
            'excerpt' => $this->sanitizeExcerpt($content),
            'image' => $this->proxyImageUrl($image),
            'date' => $this->parseDate((string)($entry->updated ?? $entry->published ?? '')),
            'source' => '',
            'author' => (string)($entry->author->name ?? ''),
        ];
    }

    /**
     * Fetch personalized data from Moodle REST API.
     *
     * Supports content types: news (default), my-courses, deadlines.
     */
    private function fetchMoodle(array $config, ?string $userId = null): array {
        $connectionId = $config['connectionId'] ?? '';
        $connection = $this->getConnection($connectionId);
        if ($connection === null) {
            throw new \RuntimeException('LMS connection not found');
        }

        $baseUrl = rtrim($connection['baseUrl'], '/');
        $this->validateUrl($baseUrl);
        $resolved = $this->resolveToken($connectionId, $userId);
        if ($resolved === null) {
            throw new \RuntimeException('No Moodle token available. Please connect your account.');
        }
        $token = $resolved['token'];
        $courseId = $this->sanitizeCourseId($config['courseId'] ?? null);

        $client = $this->httpClient->newClient();
        $contentType = $config['contentType'] ?? 'news';

        $moodleForumId = $config['moodleForumId'] ?? null;
        if ($moodleForumId !== null && !preg_match('/^[0-9]{1,10}$/', (string)$moodleForumId)) {
            $moodleForumId = null;
        }

        return match ($contentType) {
            'courses', 'my-courses' => $this->fetchMoodleCourses($client, $baseUrl, $token, $connection),
            'assignments' => $this->fetchMoodleAssignments($client, $baseUrl, $token, $connection, $courseId),
            'deadlines' => $this->fetchMoodleDeadlines($client, $baseUrl, $token, $connection),
            default => $this->fetchMoodleNews($client, $baseUrl, $token, $connection, $courseId, $moodleForumId),
        };
    }

    /**
     * Fetch news/forum discussions from Moodle.
     */
    private function fetchMoodleNews($client, string $baseUrl, string $token, array $connection, ?string $courseId, ?string $moodleForumId = null): array {
        $items = [];

        if ($courseId) {
            if ($moodleForumId) {
                // Fetch discussions from a specific forum
                $items = $this->fetchMoodleForumDiscussions($client, $baseUrl, $token, (int)$moodleForumId);
            } else {
                // Fetch forum discussions for a specific course (all forums)
                $response = $this->moodlePost($client, $baseUrl, $token, 'mod_forum_get_forums_by_courses', [
                    'courseids[0]' => $courseId,
                ]);

                $forums = json_decode($response->getBody(), true);
                if (is_array($forums) && !isset($forums['exception'])) {
                    foreach (array_slice($forums, 0, 3) as $forum) {
                        $discussionItems = $this->fetchMoodleForumDiscussions($client, $baseUrl, $token, (int)$forum['id']);
                        $items = array_merge($items, $discussionItems);
                    }
                }
            }
        } else {
            // Fetch site-level courses
            $response = $this->moodlePost($client, $baseUrl, $token, 'core_course_get_courses_by_field');
            $data = json_decode($response->getBody(), true);
            $courses = $data['courses'] ?? [];
            if (is_array($courses) && !isset($data['exception'])) {
                foreach (array_slice($courses, 0, self::MAX_ITEMS) as $course) {
                    $image = $course['overviewfiles'][0]['fileurl'] ?? $course['courseimage'] ?? null;
                    $items[] = [
                        'id' => 'moodle-course-' . $course['id'],
                        'title' => $course['fullname'] ?? $course['shortname'] ?? '',
                        'url' => $baseUrl . '/course/view.php?id=' . $course['id'],
                        'excerpt' => $this->sanitizeExcerpt($course['summary'] ?? ''),
                        'image' => $this->proxyImageUrl($this->moodleFileUrl($image, $baseUrl, $token)),
                        'date' => date('c', $course['timemodified'] ?? time()),
                        'source' => $connection['name'] ?? 'Moodle',
                        'author' => null,
                    ];
                }
            }
        }

        usort($items, fn($a, $b) => strtotime($b['date']) - strtotime($a['date']));

        return [
            'items' => array_slice($items, 0, self::MAX_ITEMS),
            'source' => $connection['name'] ?? 'Moodle',
        ];
    }

    /**
     * Fetch available courses from Moodle (organizational catalog view).
     */
    private function fetchMoodleCourses($client, string $baseUrl, string $token, array $connection): array {
        // Get user ID via site info first
        $siteResponse = $this->moodlePost($client, $baseUrl, $token, 'core_webservice_get_site_info');
        $siteInfo = json_decode($siteResponse->getBody(), true);
        $moodleUserId = $siteInfo['userid'] ?? null;

        if ($moodleUserId === null) {
            return ['items' => [], 'source' => $connection['name'] ?? 'Moodle'];
        }

        $response = $this->moodlePost($client, $baseUrl, $token, 'core_enrol_get_users_courses', [
            'userid' => $moodleUserId,
        ]);

        $courses = json_decode($response->getBody(), true);
        if (!is_array($courses) || isset($courses['exception'])) {
            return ['items' => [], 'source' => $connection['name'] ?? 'Moodle'];
        }

        $items = [];
        foreach ($courses as $course) {
            $courseId = $course['id'] ?? 0;
            if ($courseId <= 1) {
                continue; // Skip Moodle's site-level course
            }

            $items[] = [
                'id' => 'moodle-course-' . $courseId,
                'title' => $course['fullname'] ?? $course['shortname'] ?? '',
                'url' => $baseUrl . '/course/view.php?id=' . $courseId,
                'excerpt' => $course['shortname'] ?? '',
                'image' => $this->proxyImageUrl($this->moodleFileUrl($course['overviewfiles'][0]['fileurl'] ?? $course['courseimage'] ?? null, $baseUrl, $token)),
                'date' => isset($course['startdate']) ? date('c', $course['startdate']) : date('c'),
                'source' => $connection['name'] ?? 'Moodle',
                'author' => null,
            ];
        }

        return [
            'items' => $items,
            'source' => $connection['name'] ?? 'Moodle',
        ];
    }

    /**
     * Fetch upcoming calendar events/deadlines from Moodle.
     */
    private function fetchMoodleDeadlines($client, string $baseUrl, string $token, array $connection): array {
        $response = $this->moodlePost($client, $baseUrl, $token, 'core_calendar_get_calendar_upcoming_view');

        $data = json_decode($response->getBody(), true);
        $events = $data['events'] ?? [];
        if (!is_array($events)) {
            return ['items' => [], 'source' => $connection['name'] ?? 'Moodle'];
        }

        $items = [];
        foreach ($events as $event) {
            $eventId = $event['id'] ?? '';
            if (empty($eventId)) {
                continue;
            }

            $items[] = [
                'id' => 'moodle-deadline-' . $eventId,
                'title' => $event['name'] ?? '',
                'url' => $event['url'] ?? ($baseUrl . '/calendar/view.php?id=' . $eventId),
                'excerpt' => $this->sanitizeExcerpt($event['description'] ?? ''),
                'image' => null,
                'date' => isset($event['timestart']) ? date('c', $event['timestart']) : date('c'),
                'source' => $event['course']['fullname'] ?? $connection['name'] ?? 'Moodle',
                'author' => null,
            ];
        }

        // Sort by date (earliest deadline first)
        usort($items, fn($a, $b) => strtotime($a['date']) - strtotime($b['date']));

        return [
            'items' => $items,
            'source' => $connection['name'] ?? 'Moodle',
        ];
    }

    /**
     * Fetch assignments overview from Moodle (organizational — shows what assignments exist, not personal submissions).
     */
    private function fetchMoodleAssignments($client, string $baseUrl, string $token, array $connection, ?string $courseId): array {
        $params = [];
        if ($courseId) {
            $params['courseids[0]'] = $courseId;
        }

        $response = $this->moodlePost($client, $baseUrl, $token, 'mod_assign_get_assignments', $params);
        $data = json_decode($response->getBody(), true);

        if (!is_array($data) || isset($data['exception'])) {
            return ['items' => [], 'source' => $connection['name'] ?? 'Moodle'];
        }

        $items = [];
        foreach ($data['courses'] ?? [] as $course) {
            $courseName = $course['fullname'] ?? '';
            foreach ($course['assignments'] ?? [] as $assignment) {
                $items[] = [
                    'id' => 'moodle-assign-' . ($assignment['id'] ?? ''),
                    'title' => $assignment['name'] ?? '',
                    'url' => $baseUrl . '/mod/assign/view.php?id=' . ($assignment['cmid'] ?? $assignment['id'] ?? ''),
                    'excerpt' => $this->sanitizeExcerpt($assignment['intro'] ?? ''),
                    'image' => null,
                    'date' => isset($assignment['duedate']) && $assignment['duedate'] > 0
                        ? date('c', $assignment['duedate'])
                        : (isset($assignment['timemodified']) ? date('c', $assignment['timemodified']) : date('c')),
                    'source' => $courseName ?: ($connection['name'] ?? 'Moodle'),
                    'author' => null,
                ];
            }
        }

        usort($items, fn($a, $b) => strtotime($b['date']) - strtotime($a['date']));

        return [
            'items' => array_slice($items, 0, self::MAX_ITEMS),
            'source' => $connection['name'] ?? 'Moodle',
        ];
    }

    private function fetchMoodleForumDiscussions($client, string $baseUrl, string $token, int $forumId): array {
        $response = $this->moodlePost($client, $baseUrl, $token, 'mod_forum_get_forum_discussions', [
            'forumid' => $forumId,
            'sortorder' => -1,
            'perpage' => 10,
        ]);

        $data = json_decode($response->getBody(), true);
        if (!is_array($data) || isset($data['exception']) || !isset($data['discussions'])) {
            return [];
        }

        $items = [];
        foreach ($data['discussions'] as $discussion) {
            $items[] = [
                'id' => 'moodle-discussion-' . $discussion['discussion'],
                'title' => $discussion['name'] ?? $discussion['subject'] ?? '',
                'url' => $baseUrl . '/mod/forum/discuss.php?d=' . $discussion['discussion'],
                'excerpt' => $this->sanitizeExcerpt($discussion['message'] ?? ''),
                'image' => $this->proxyImageUrl($this->extractImageFromHtml($discussion['message'] ?? '')),
                'date' => date('c', $discussion['timemodified'] ?? $discussion['created'] ?? time()),
                'source' => $discussion['forum'] ?? 'Moodle',
                'author' => ($discussion['userfullname'] ?? null),
            ];
        }

        return $items;
    }

    /**
     * Fetch personalized data from Canvas REST API.
     *
     * Supports content types: news (default), my-courses, deadlines.
     */
    private function fetchCanvas(array $config, ?string $userId = null): array {
        $connectionId = $config['connectionId'] ?? '';
        $connection = $this->getConnection($connectionId);
        if ($connection === null) {
            throw new \RuntimeException('LMS connection not found');
        }

        $baseUrl = rtrim($connection['baseUrl'], '/');
        $this->validateUrl($baseUrl);
        $resolved = $this->resolveToken($connectionId, $userId);
        if ($resolved === null) {
            throw new \RuntimeException('No Canvas token available. Please connect your account.');
        }
        $token = $resolved['token'];
        $courseId = $this->sanitizeCourseId($config['courseId'] ?? null);

        $client = $this->httpClient->newClient();
        $headers = [
            'Authorization' => 'Bearer ' . $token,
            'Accept' => 'application/json',
            'Accept-Language' => $this->acceptLanguage,
        ];

        $contentType = $config['contentType'] ?? 'news';

        return match ($contentType) {
            'my-courses' => $this->fetchCanvasMyCourses($client, $headers, $baseUrl, $connection),
            'deadlines' => $this->fetchCanvasDeadlines($client, $headers, $baseUrl, $connection),
            default => $this->fetchCanvasNews($client, $headers, $baseUrl, $connection, $courseId),
        };
    }

    /**
     * Fetch announcements from Canvas.
     */
    private function fetchCanvasNews($client, array $headers, string $baseUrl, array $connection, ?string $courseId): array {
        if ($courseId) {
            $contextCodes = ['course_' . $courseId];
        } else {
            // Canvas requires context_codes — fetch the user's courses first
            $coursesUrl = $baseUrl . '/api/v1/courses?' . http_build_query([
                'per_page' => 50,
                'enrollment_state' => 'active',
            ]);
            $coursesResponse = $client->get($coursesUrl, [
                'timeout' => self::HTTP_TIMEOUT,
                'headers' => $headers,
            ]);
            $courses = json_decode($coursesResponse->getBody(), true);
            if (!is_array($courses) || empty($courses)) {
                return ['items' => [], 'source' => $connection['name'] ?? 'Canvas'];
            }
            $contextCodes = array_map(fn($c) => 'course_' . $c['id'], $courses);
        }

        // Build URL with multiple context_codes[]
        $params = ['per_page' => self::MAX_ITEMS];
        $query = http_build_query($params);
        foreach ($contextCodes as $code) {
            $query .= '&' . urlencode('context_codes[]') . '=' . urlencode($code);
        }
        $url = $baseUrl . '/api/v1/announcements?' . $query;

        $response = $client->get($url, [
            'timeout' => self::HTTP_TIMEOUT,
            'headers' => $headers,
        ]);

        $announcements = json_decode($response->getBody(), true);
        if (!is_array($announcements)) {
            return ['items' => [], 'source' => $connection['name'] ?? 'Canvas'];
        }

        $items = [];
        foreach ($announcements as $announcement) {
            $items[] = [
                'id' => 'canvas-' . ($announcement['id'] ?? md5($announcement['title'] ?? '')),
                'title' => $announcement['title'] ?? '',
                'url' => $announcement['html_url'] ?? '',
                'excerpt' => $this->sanitizeExcerpt($announcement['message'] ?? ''),
                'image' => $this->proxyImageUrl($this->extractImageFromHtml($announcement['message'] ?? '')),
                'date' => $announcement['posted_at'] ?? $announcement['created_at'] ?? date('c'),
                'source' => $connection['name'] ?? 'Canvas',
                'author' => $announcement['author']['display_name'] ?? null,
            ];
        }

        return [
            'items' => $items,
            'source' => $connection['name'] ?? 'Canvas',
        ];
    }

    /**
     * Fetch the current user's enrolled courses from Canvas.
     */
    private function fetchCanvasMyCourses($client, array $headers, string $baseUrl, array $connection): array {
        $query = http_build_query([
            'enrollment_state' => 'active',
            'per_page' => self::MAX_ITEMS,
        ]) . '&' . urlencode('include[]') . '=course_image';
        $url = $baseUrl . '/api/v1/courses?' . $query;

        $response = $client->get($url, [
            'timeout' => self::HTTP_TIMEOUT,
            'headers' => $headers,
        ]);

        $courses = json_decode($response->getBody(), true);
        if (!is_array($courses)) {
            return ['items' => [], 'source' => $connection['name'] ?? 'Canvas'];
        }

        $items = [];
        foreach ($courses as $course) {
            $courseId = $course['id'] ?? '';
            if (empty($courseId)) {
                continue;
            }

            $items[] = [
                'id' => 'canvas-course-' . $courseId,
                'title' => $course['name'] ?? '',
                'url' => $baseUrl . '/courses/' . $courseId,
                'excerpt' => $course['course_code'] ?? '',
                'image' => $this->proxyImageUrl($course['image_download_url'] ?? null),
                'date' => $course['created_at'] ?? date('c'),
                'source' => $connection['name'] ?? 'Canvas',
                'author' => null,
            ];
        }

        return [
            'items' => $items,
            'source' => $connection['name'] ?? 'Canvas',
        ];
    }

    /**
     * Fetch upcoming assignment deadlines from Canvas.
     */
    private function fetchCanvasDeadlines($client, array $headers, string $baseUrl, array $connection): array {
        $now = new \DateTime('now', new \DateTimeZone('UTC'));
        $end = (clone $now)->modify('+30 days');

        // Canvas calendar_events requires context_codes — fetch courses first
        $coursesUrl = $baseUrl . '/api/v1/courses?' . http_build_query([
            'per_page' => 50,
            'enrollment_state' => 'active',
        ]);
        $coursesResponse = $client->get($coursesUrl, [
            'timeout' => self::HTTP_TIMEOUT,
            'headers' => $headers,
        ]);
        $courses = json_decode($coursesResponse->getBody(), true);
        if (!is_array($courses) || empty($courses)) {
            return ['items' => [], 'source' => $connection['name'] ?? 'Canvas'];
        }

        $params = [
            'type' => 'assignment',
            'start_date' => $now->format('Y-m-d'),
            'end_date' => $end->format('Y-m-d'),
            'per_page' => self::MAX_ITEMS,
        ];
        $query = http_build_query($params);
        foreach ($courses as $course) {
            $query .= '&' . urlencode('context_codes[]') . '=' . urlencode('course_' . $course['id']);
        }
        $url = $baseUrl . '/api/v1/calendar_events?' . $query;

        $response = $client->get($url, [
            'timeout' => self::HTTP_TIMEOUT,
            'headers' => $headers,
        ]);

        $events = json_decode($response->getBody(), true);
        if (!is_array($events)) {
            return ['items' => [], 'source' => $connection['name'] ?? 'Canvas'];
        }

        $items = [];
        foreach ($events as $event) {
            $eventId = $event['id'] ?? '';
            if (empty($eventId)) {
                continue;
            }

            $items[] = [
                'id' => 'canvas-deadline-' . $eventId,
                'title' => $event['title'] ?? '',
                'url' => $event['html_url'] ?? '',
                'excerpt' => $this->sanitizeExcerpt($event['description'] ?? ''),
                'image' => null,
                'date' => $event['end_at'] ?? $event['start_at'] ?? date('c'),
                'source' => $connection['name'] ?? 'Canvas',
                'author' => null,
            ];
        }

        // Sort by date (earliest deadline first)
        usort($items, fn($a, $b) => strtotime($a['date']) - strtotime($b['date']));

        return [
            'items' => $items,
            'source' => $connection['name'] ?? 'Canvas',
        ];
    }

    /**
     * Fetch personalized data from Brightspace REST API.
     *
     * Supports content types: news (default), my-courses, deadlines.
     */
    private function fetchBrightspace(array $config, ?string $userId = null): array {
        $connectionId = $config['connectionId'] ?? '';
        $connection = $this->getConnection($connectionId);
        if ($connection === null) {
            throw new \RuntimeException('LMS connection not found');
        }

        $baseUrl = rtrim($connection['baseUrl'], '/');
        $this->validateUrl($baseUrl);
        $resolved = $this->resolveToken($connectionId, $userId);
        if ($resolved === null) {
            throw new \RuntimeException('No Brightspace token available. Please connect your account.');
        }
        $token = $resolved['token'];
        $courseId = $this->sanitizeCourseId($config['courseId'] ?? null);

        $client = $this->httpClient->newClient();
        $headers = [
            'Authorization' => 'Bearer ' . $token,
            'Accept' => 'application/json',
            'Accept-Language' => $this->acceptLanguage,
        ];

        $contentType = $config['contentType'] ?? 'news';

        return match ($contentType) {
            'my-courses' => $this->fetchBrightspaceMyCourses($client, $headers, $baseUrl, $connection),
            'deadlines' => $this->fetchBrightspaceDeadlines($client, $headers, $baseUrl, $connection),
            default => $this->fetchBrightspaceNews($client, $headers, $baseUrl, $connection, $courseId),
        };
    }

    /**
     * Fetch news/announcements from a Brightspace org unit.
     */
    private function fetchBrightspaceNews($client, array $headers, string $baseUrl, array $connection, ?string $orgUnitId): array {
        if ($orgUnitId) {
            $url = $baseUrl . '/d2l/api/le/1.67/' . $orgUnitId . '/news/';
        } else {
            // Organization-level news
            $url = $baseUrl . '/d2l/api/lp/1.43/organization/info';
            $response = $client->get($url, [
                'timeout' => self::HTTP_TIMEOUT,
                'headers' => $headers,
            ]);
            $orgInfo = json_decode($response->getBody(), true);
            $orgId = $this->sanitizeCourseId((string)($orgInfo['Identifier'] ?? '6606')) ?? '6606';
            $url = $baseUrl . '/d2l/api/le/1.67/' . $orgId . '/news/';
        }

        $response = $client->get($url, [
            'timeout' => self::HTTP_TIMEOUT,
            'headers' => $headers,
        ]);

        $newsItems = json_decode($response->getBody(), true);
        if (!is_array($newsItems)) {
            return ['items' => [], 'source' => $connection['name'] ?? 'Brightspace'];
        }

        $items = [];
        foreach ($newsItems as $newsItem) {
            $items[] = [
                'id' => 'brightspace-' . ($newsItem['Id'] ?? md5($newsItem['Title'] ?? '')),
                'title' => $newsItem['Title'] ?? '',
                'url' => $baseUrl . '/d2l/le/news/' . ($orgUnitId ?? '') . '/' . ($newsItem['Id'] ?? ''),
                'excerpt' => $this->sanitizeExcerpt($newsItem['Body']['Html'] ?? $newsItem['Body']['Text'] ?? ''),
                'image' => $this->proxyImageUrl($this->extractImageFromHtml($newsItem['Body']['Html'] ?? '')),
                'date' => $newsItem['StartDate'] ?? $newsItem['CreatedDate'] ?? date('c'),
                'source' => $connection['name'] ?? 'Brightspace',
                'author' => null,
            ];
        }

        return [
            'items' => $items,
            'source' => $connection['name'] ?? 'Brightspace',
        ];
    }

    /**
     * Fetch the current user's enrolled courses from Brightspace.
     */
    private function fetchBrightspaceMyCourses($client, array $headers, string $baseUrl, array $connection): array {
        $url = $baseUrl . '/d2l/api/lp/1.43/enrollments/myenrollments/?' . http_build_query([
            'sortBy' => '-StartDate',
            'isActive' => 'true',
            'pageSize' => self::MAX_ITEMS,
        ]);

        $response = $client->get($url, [
            'timeout' => self::HTTP_TIMEOUT,
            'headers' => $headers,
        ]);

        $data = json_decode($response->getBody(), true);
        $enrollments = $data['Items'] ?? [];
        if (!is_array($enrollments)) {
            return ['items' => [], 'source' => $connection['name'] ?? 'Brightspace'];
        }

        $items = [];
        foreach ($enrollments as $enrollment) {
            $orgUnit = $enrollment['OrgUnit'] ?? [];
            $orgUnitId = $orgUnit['Id'] ?? '';
            if (empty($orgUnitId)) {
                continue;
            }

            $items[] = [
                'id' => 'brightspace-course-' . $orgUnitId,
                'title' => $orgUnit['Name'] ?? '',
                'url' => $baseUrl . '/d2l/home/' . $orgUnitId,
                'excerpt' => $orgUnit['Code'] ?? '',
                'image' => null,
                'date' => $enrollment['Access']['StartDate'] ?? date('c'),
                'source' => $connection['name'] ?? 'Brightspace',
                'author' => null,
            ];
        }

        return [
            'items' => $items,
            'source' => $connection['name'] ?? 'Brightspace',
        ];
    }

    /**
     * Fetch upcoming calendar events/deadlines from Brightspace.
     */
    private function fetchBrightspaceDeadlines($client, array $headers, string $baseUrl, array $connection): array {
        $now = new \DateTime('now', new \DateTimeZone('UTC'));
        $end = (clone $now)->modify('+30 days');

        $url = $baseUrl . '/d2l/api/le/1.67/calendar/events/myEvents/?' . http_build_query([
            'startDateTime' => $now->format('Y-m-d\TH:i:s.000\Z'),
            'endDateTime' => $end->format('Y-m-d\TH:i:s.000\Z'),
        ]);

        $response = $client->get($url, [
            'timeout' => self::HTTP_TIMEOUT,
            'headers' => $headers,
        ]);

        $events = json_decode($response->getBody(), true);
        if (!is_array($events)) {
            return ['items' => [], 'source' => $connection['name'] ?? 'Brightspace'];
        }

        $items = [];
        foreach ($events as $event) {
            $eventId = $event['CalendarEventId'] ?? '';
            if (empty($eventId)) {
                continue;
            }

            $items[] = [
                'id' => 'brightspace-deadline-' . $eventId,
                'title' => $event['Title'] ?? '',
                'url' => $baseUrl . '/d2l/le/calendar/' . ($event['OrgUnitId'] ?? ''),
                'excerpt' => $this->sanitizeExcerpt($event['Description'] ?? ''),
                'image' => null,
                'date' => $event['EndDateTime'] ?? $event['StartDateTime'] ?? date('c'),
                'source' => $connection['name'] ?? 'Brightspace',
                'author' => null,
            ];
        }

        // Sort by date (earliest deadline first)
        usort($items, fn($a, $b) => strtotime($a['date']) - strtotime($b['date']));

        return [
            'items' => $items,
            'source' => $connection['name'] ?? 'Brightspace',
        ];
    }

    /**
     * Fetch items from SharePoint via Microsoft Graph API.
     * Supports content types: pages, news, documents, list.
     */
    private function fetchSharePoint(array $config, ?string $userId): array {
        $connectionId = $config['connectionId'] ?? '';
        $connection = $this->getConnection($connectionId);
        if ($connection === null) {
            throw new \RuntimeException('SharePoint connection not found');
        }

        $siteUrl = $connection['siteUrl'] ?? '';
        if (empty($siteUrl)) {
            // Backward compat: fall back to generic REST API if no siteUrl
            return $this->fetchGenericRestApi($config, $userId);
        }

        $resolved = $this->resolveToken($connectionId, $userId);
        if ($resolved === null) {
            throw new \RuntimeException('No SharePoint token available');
        }
        $token = $resolved['token'];

        $siteId = $this->resolveSharePointSiteId($siteUrl, $token);
        $contentType = $config['contentType'] ?? 'pages';
        $limit = min((int)($config['limit'] ?? 10), self::MAX_ITEMS);

        $client = $this->httpClient->newClient();
        $headers = [
            'Authorization' => 'Bearer ' . $token,
            'Accept' => 'application/json',
            'Accept-Language' => $this->acceptLanguage,
        ];
        $graphBase = 'https://graph.microsoft.com/v1.0';
        $sourceName = $connection['name'] ?? 'SharePoint';

        return match ($contentType) {
            'news' => $this->fetchSharePointPages($client, $headers, $graphBase, $siteId, $sourceName, $limit, true),
            'documents' => $this->fetchSharePointListItems($client, $headers, $graphBase, $siteId, $config['listId'] ?? '', $sourceName, $limit, true),
            'list' => $this->fetchSharePointListItems($client, $headers, $graphBase, $siteId, $config['listId'] ?? '', $sourceName, $limit, false),
            default => $this->fetchSharePointPages($client, $headers, $graphBase, $siteId, $sourceName, $limit, false),
        };
    }

    private function fetchSharePointPages($client, array $headers, string $graphBase, string $siteId, string $sourceName, int $limit, bool $newsOnly): array {
        $fetchLimit = $newsOnly ? 50 : $limit; // Fetch more for news filtering
        $url = $graphBase . '/sites/' . $siteId . '/pages?$orderby=lastModifiedDateTime+desc&$top=' . $fetchLimit;

        $response = $client->get($url, [
            'timeout' => self::HTTP_TIMEOUT,
            'headers' => $headers,
        ]);

        $data = json_decode($response->getBody(), true);
        $items = [];

        foreach ($data['value'] ?? [] as $page) {
            if ($newsOnly && ($page['promotionKind'] ?? '') !== 'newsPost') {
                continue;
            }
            $items[] = [
                'id' => 'sp-page-' . ($page['id'] ?? md5($page['title'] ?? '')),
                'title' => $page['title'] ?? '',
                'url' => $page['webUrl'] ?? '',
                'excerpt' => $this->sanitizeExcerpt($page['description'] ?? ''),
                'image' => $this->proxyImageUrl($page['thumbnailWebUrl'] ?? null),
                'date' => $this->parseDate($page['lastModifiedDateTime'] ?? ''),
                'source' => $sourceName,
                'author' => $page['lastModifiedBy']['user']['displayName'] ?? null,
            ];
        }

        return [
            'items' => array_slice($items, 0, $limit),
            'source' => $sourceName,
        ];
    }

    private function fetchSharePointListItems($client, array $headers, string $graphBase, string $siteId, string $listId, string $sourceName, int $limit, bool $isDocumentLibrary): array {
        if (empty($listId)) {
            return ['items' => [], 'source' => $sourceName];
        }

        // Sanitize listId (GUID format)
        if (!preg_match('/^[a-zA-Z0-9-]{1,64}$/', $listId)) {
            throw new \RuntimeException('Invalid list ID');
        }

        $expand = $isDocumentLibrary ? 'fields,driveItem' : 'fields';
        $url = $graphBase . '/sites/' . $siteId . '/lists/' . $listId . '/items?$expand=' . $expand . '&$top=' . $limit;

        $response = $client->get($url, [
            'timeout' => self::HTTP_TIMEOUT,
            'headers' => $headers,
        ]);
        $this->validateHttpResponse($response, 'SharePoint list');

        $data = json_decode($response->getBody(), true);
        $items = [];

        foreach ($data['value'] ?? [] as $item) {
            $fields = $item['fields'] ?? [];

            // Skip folders in document libraries
            if ($isDocumentLibrary && ($fields['ContentType'] ?? '') === 'Folder') {
                continue;
            }

            $title = $isDocumentLibrary
                ? ($fields['FileLeafRef'] ?? $fields['Title'] ?? '')
                : ($fields['Title'] ?? '');

            if (empty($title)) {
                continue;
            }

            // Build proper web UI URL
            $itemUrl = $item['webUrl'] ?? '';
            if ($isDocumentLibrary) {
                // Documents: use driveItem.webUrl to open in Office Online
                $itemUrl = $item['driveItem']['webUrl'] ?? $itemUrl;
            } elseif (!empty($itemUrl)) {
                // List items: convert direct URL to DispForm.aspx view
                $lastSlash = strrpos($itemUrl, '/');
                if ($lastSlash !== false) {
                    $itemUrl = substr($itemUrl, 0, $lastSlash) . '/DispForm.aspx?ID=' . ($item['fields']['id'] ?? $item['id'] ?? '');
                }
            }

            $items[] = [
                'id' => 'sp-item-' . ($item['id'] ?? md5($title)),
                'title' => $title,
                'url' => $itemUrl,
                'excerpt' => $this->sanitizeExcerpt($fields['Body'] ?? $fields['_ExtendedDescription'] ?? ''),
                'image' => null,
                'date' => $this->parseDate($item['lastModifiedDateTime'] ?? $fields['Modified'] ?? ''),
                'source' => $sourceName,
                'author' => $item['lastModifiedBy']['user']['displayName'] ?? null,
            ];
        }

        usort($items, fn($a, $b) => strtotime($b['date']) - strtotime($a['date']));

        return [
            'items' => $items,
            'source' => $sourceName,
        ];
    }

    /**
     * Resolve a SharePoint site URL to a Graph API siteId.
     * Cached permanently (siteId never changes).
     */
    private function resolveSharePointSiteId(string $siteUrl, string $token): string {
        $cacheKey = 'sp_site_' . md5($siteUrl);

        if ($this->cache !== null) {
            $cached = $this->cache->get($cacheKey);
            if ($cached !== null) {
                return $cached;
            }
        }

        // SSRF protection: validate admin-configured site URL
        $this->validateUrl($siteUrl);

        $parsed = parse_url($siteUrl);
        $hostname = $parsed['host'] ?? '';
        $path = ltrim($parsed['path'] ?? '', '/');

        if (empty($hostname)) {
            throw new \RuntimeException('Invalid SharePoint site URL');
        }

        // Build Graph API URL: /sites/{hostname}:/{path} or /sites/{hostname} for root
        $graphUrl = 'https://graph.microsoft.com/v1.0/sites/' . $hostname;
        if (!empty($path)) {
            $graphUrl .= ':/' . $path;
        }

        $client = $this->httpClient->newClient();
        $response = $client->get($graphUrl, [
            'timeout' => self::HTTP_TIMEOUT,
            'headers' => [
                'Authorization' => 'Bearer ' . $token,
                'Accept' => 'application/json',
            ],
        ]);

        $data = json_decode($response->getBody(), true);
        $siteId = $data['id'] ?? '';

        if (empty($siteId)) {
            throw new \RuntimeException('Failed to resolve SharePoint site ID');
        }

        // Cache permanently (siteId never changes)
        if ($this->cache !== null) {
            $this->cache->set($cacheKey, $siteId, 0);
        }

        return $siteId;
    }

    /**
     * Get available lists and document libraries for a SharePoint site.
     *
     * @return array{libraries: array, lists: array}
     */
    public function getSharePointLists(string $connectionId): array {
        $connection = $this->getConnection($connectionId);
        if ($connection === null || ($connection['type'] ?? '') !== 'sharepoint') {
            throw new \RuntimeException('SharePoint connection not found');
        }

        $siteUrl = $connection['siteUrl'] ?? '';
        if (empty($siteUrl)) {
            throw new \RuntimeException('SharePoint site URL not configured');
        }

        $resolved = $this->resolveToken($connectionId, null);
        if ($resolved === null) {
            throw new \RuntimeException('No SharePoint token available');
        }

        $siteId = $this->resolveSharePointSiteId($siteUrl, $resolved['token']);

        $client = $this->httpClient->newClient();
        $response = $client->get('https://graph.microsoft.com/v1.0/sites/' . $siteId . '/lists', [
            'timeout' => self::HTTP_TIMEOUT,
            'headers' => [
                'Authorization' => 'Bearer ' . $resolved['token'],
                'Accept' => 'application/json',
            ],
        ]);

        $data = json_decode($response->getBody(), true);
        $libraries = [];
        $lists = [];

        foreach ($data['value'] ?? [] as $list) {
            if ($list['list']['hidden'] ?? false) {
                continue;
            }

            $entry = [
                'id' => $list['id'] ?? '',
                'name' => $list['displayName'] ?? '',
                'template' => $list['list']['template'] ?? '',
            ];

            if (($list['list']['template'] ?? '') === 'documentLibrary') {
                $libraries[] = $entry;
            } else {
                $lists[] = $entry;
            }
        }

        return ['libraries' => $libraries, 'lists' => $lists];
    }

    /**
     * Fetch items from a custom REST API using admin-configured connection settings.
     */
    private function fetchGenericRestApi(array $config, ?string $userId = null): array {
        $connectionId = $config['connectionId'] ?? '';
        $connection = $this->getConnection($connectionId);
        if ($connection === null) {
            throw new \RuntimeException('REST API connection not found');
        }

        $baseUrl = rtrim($connection['baseUrl'], '/');
        $this->validateUrl($baseUrl);
        $endpoint = $connection['customEndpoint'] ?? '';
        $url = $baseUrl . '/' . ltrim($endpoint, '/');
        $isOpenProject = ($connection['type'] ?? '') === 'openproject';
        $isJira = ($connection['type'] ?? '') === 'jira';

        // Jira: apply content type and project filters via JQL
        if ($isJira) {
            $jqlParts = [];

            // Project filter from widget config
            $jiraProject = $config['jiraProject'] ?? '';
            if ($jiraProject !== '' && preg_match('/^[A-Z][A-Z0-9_]{1,20}$/', $jiraProject)) {
                $jqlParts[] = 'project=' . $jiraProject;
            }

            // Content type JQL filters
            if (!empty($config['contentType'])) {
                $jqlParts[] = match ($config['contentType']) {
                    'open' => 'status!=Done',
                    'bugs' => 'type=Bug',
                    'recent' => 'updated>=-7d',
                    'created-recent' => 'created>=-7d',
                    default => '',
                };
                $jqlParts = array_filter($jqlParts);
            }

            $isCloud = str_contains($baseUrl, '.atlassian.net');
            $jql = !empty($jqlParts)
                ? implode(' AND ', $jqlParts) . ' ORDER BY updated DESC'
                : 'updated>=-30d ORDER BY updated DESC';

            if ($isCloud) {
                $url = $baseUrl . '/rest/api/3/search/jql?jql=' . urlencode($jql) . '&maxResults=20&fields=summary,description,updated,assignee,key,issuetype';
            } else {
                $url = $baseUrl . '/rest/api/2/search?jql=' . urlencode($jql) . '&maxResults=20';
            }
        }

        // OpenProject content type filters
        if ($isOpenProject && !empty($config['contentType'])) {
            $filters = match ($config['contentType']) {
                'open' => '[{"status":{"operator":"o","values":[]}}]',
                'overdue' => '[{"dueDate":{"operator":"<t-","values":["0"]}},{"status":{"operator":"o","values":[]}}]',
                'milestones' => '[{"type":{"operator":"=","values":["2"]}}]',
                'recently-updated' => '[{"updatedAt":{"operator":">t-","values":["7"]}}]',
                default => null,
            };
            if ($filters !== null) {
                $separator = str_contains($url, '?') ? '&' : '?';
                $url .= $separator . 'filters=' . urlencode($filters);
            }
        }

        $this->validateUrl($url);

        $authMethod = $connection['authMethod'] ?? 'bearer';
        $headers = [
            'Accept' => 'application/json',
            'Accept-Language' => $this->acceptLanguage,
        ];

        // Build auth header
        if ($authMethod === 'bearer' || $authMethod === 'client_credentials') {
            $resolved = $this->resolveToken($connectionId, $userId);
            if ($resolved !== null) {
                $headers['Authorization'] = 'Bearer ' . $resolved['token'];
            }
        } elseif ($authMethod === 'apikey') {
            $resolved = $this->resolveToken($connectionId, $userId);
            $headerName = $connection['apiKeyHeader'] ?? 'X-API-Key';
            if ($resolved !== null && preg_match('/^[a-zA-Z0-9\-]+$/', $headerName)) {
                $headers[$headerName] = $resolved['token'];
            }
        } elseif ($authMethod === 'basic') {
            $resolved = $this->resolveToken($connectionId, $userId);
            if ($resolved !== null) {
                $token = $resolved['token'];
                // Jira Cloud: prepend email to token for Basic auth (email:api-token)
                $jiraEmail = $connection['jiraEmail'] ?? '';
                if ($jiraEmail !== '' && !str_contains($token, ':')) {
                    $token = $jiraEmail . ':' . $token;
                }
                // If the token contains a colon, treat it as username:password and base64 encode it.
                // Otherwise assume it's already base64-encoded.
                if (str_contains($token, ':')) {
                    $token = base64_encode($token);
                }
                $headers['Authorization'] = 'Basic ' . $token;
            }
        }

        // Merge custom headers (admin-configured, e.g. OCS-APIRequest: true)
        foreach ($connection['customHeaders'] ?? [] as $header) {
            $key = $header['key'] ?? '';
            $value = $header['value'] ?? '';
            if ($key !== '' && !isset($headers[$key])) {
                $headers[$key] = $value;
            }
        }

        $client = $this->httpClient->newClient();
        $response = $client->get($url, [
            'timeout' => self::HTTP_TIMEOUT,
            'headers' => $headers,
        ]);
        $this->validateHttpResponse($response, $connection['name'] ?? 'REST API');

        $data = $this->safeJsonDecode($response);
        if (!is_array($data)) {
            return ['items' => [], 'source' => $connection['name'] ?? 'REST API'];
        }

        $mapping = $connection['responseMapping'] ?? [];
        $items = $this->mapJsonResponse($data, $mapping, $baseUrl, $connection['name'] ?? 'REST API');

        // Jira: convert issue keys to browse URLs
        if (($connection['type'] ?? '') === 'jira') {
            foreach ($items as &$item) {
                if ($item['url'] && !str_contains($item['url'], '/browse/')) {
                    // URL is baseUrl/KEY from relative URL resolution, rewrite to baseUrl/browse/KEY
                    $key = basename($item['url']);
                    $item['url'] = $baseUrl . '/browse/' . $key;
                }
            }
            unset($item);
        }

        // OpenProject: convert API URLs to web URLs
        if ($isOpenProject) {
            foreach ($items as &$item) {
                if (preg_match('#/api/v3/work_packages/(\d+)#', $item['url'], $m)) {
                    $item['url'] = $baseUrl . '/work_packages/' . $m[1];
                }
            }
            unset($item);
        }

        usort($items, fn($a, $b) => strtotime($b['date']) - strtotime($a['date']));

        return [
            'items' => array_slice($items, 0, self::MAX_ITEMS),
            'source' => $connection['name'] ?? 'REST API',
        ];
    }

    /**
     * Get available Jira projects for a connection.
     */
    public function getJiraProjects(string $connectionId): array {
        $connection = $this->getConnection($connectionId);
        if ($connection === null) {
            return ['projects' => []];
        }

        $baseUrl = rtrim($connection['baseUrl'], '/');
        $this->validateUrl($baseUrl);
        $isCloud = str_contains($baseUrl, '.atlassian.net');
        $url = $baseUrl . ($isCloud ? '/rest/api/3/project' : '/rest/api/2/project');

        $headers = ['Accept' => 'application/json'];
        $authMethod = $connection['authMethod'] ?? 'bearer';

        $resolved = $this->resolveToken($connectionId, null);
        if ($resolved === null) {
            return ['projects' => []];
        }
        $token = $resolved['token'];

        if ($authMethod === 'basic') {
            $jiraEmail = $connection['jiraEmail'] ?? '';
            if ($jiraEmail !== '' && !str_contains($token, ':')) {
                $token = $jiraEmail . ':' . $token;
            }
            if (str_contains($token, ':')) {
                $token = base64_encode($token);
            }
            $headers['Authorization'] = 'Basic ' . $token;
        } elseif ($authMethod === 'bearer') {
            $headers['Authorization'] = 'Bearer ' . $token;
        }

        $client = $this->httpClient->newClient();
        $response = $client->get($url, [
            'timeout' => self::HTTP_TIMEOUT,
            'headers' => $headers,
        ]);

        $data = json_decode($response->getBody(), true);
        if (!is_array($data)) {
            return ['projects' => []];
        }

        $projects = [];
        foreach ($data as $project) {
            $projects[] = [
                'key' => $project['key'] ?? '',
                'name' => $project['name'] ?? '',
            ];
        }

        usort($projects, fn($a, $b) => strcasecmp($a['name'], $b['name']));

        return ['projects' => $projects];
    }

    /**
     * Map a JSON API response to normalized feed items using a field mapping config.
     */
    private function mapJsonResponse(array $data, array $mapping, string $baseUrl, string $sourceName): array {
        // Extract items array from response using items path
        $itemsPath = $mapping['items'] ?? '';
        $rawItems = $itemsPath ? $this->resolveJsonPath($data, $itemsPath) : $data;

        if (!is_array($rawItems)) {
            return [];
        }

        // If the result is an associative array (single item), wrap it
        if (!empty($rawItems) && !array_is_list($rawItems)) {
            $rawItems = [$rawItems];
        }

        $items = [];
        foreach ($rawItems as $raw) {
            if (!is_array($raw)) {
                continue;
            }

            $title = $this->resolveJsonPath($raw, $mapping['title'] ?? 'title');
            if (empty($title)) {
                continue; // skip items without a title
            }

            $url = $this->resolveJsonPath($raw, $mapping['url'] ?? 'url') ?? '';
            // Make relative URLs absolute
            if ($url && !str_starts_with($url, 'http')) {
                $url = $baseUrl . '/' . ltrim($url, '/');
            }

            $excerpt = $this->resolveJsonPath($raw, $mapping['excerpt'] ?? '') ?? '';
            // Handle Atlassian Document Format (ADF) objects (Jira Cloud v3)
            if (is_array($excerpt) && ($excerpt['type'] ?? '') === 'doc') {
                $excerpt = $this->extractAdfText($excerpt);
            }
            $date = $this->resolveJsonPath($raw, $mapping['date'] ?? '') ?? '';
            $image = $this->resolveJsonPath($raw, $mapping['image'] ?? '') ?? null;
            $author = $this->resolveJsonPath($raw, $mapping['author'] ?? '') ?? null;

            $items[] = [
                'id' => 'rest-' . md5($title . $url . $date),
                'title' => (string) $title,
                'url' => (string) $url,
                'excerpt' => $this->sanitizeExcerpt(is_string($excerpt) ? $excerpt : ''),
                'image' => $this->proxyImageUrl(is_string($image) ? $image : null),
                'date' => $this->parseDate((string) $date),
                'source' => $sourceName,
                'author' => is_string($author) ? $author : null,
            ];
        }

        return $items;
    }

    /**
     * Extract plain text from an Atlassian Document Format (ADF) object.
     */
    private function extractAdfText(array $doc): string {
        $text = '';
        foreach ($doc['content'] ?? [] as $block) {
            foreach ($block['content'] ?? [] as $inline) {
                if (($inline['type'] ?? '') === 'text') {
                    $text .= $inline['text'] ?? '';
                }
            }
            $text .= ' ';
        }
        return trim($text);
    }

    /**
     * Resolve a dot-notation path in a JSON array.
     * E.g., "data.items" resolves $data['data']['items'].
     * E.g., "author.name" resolves $item['author']['name'].
     *
     * @return mixed The resolved value, or null if path doesn't exist
     */
    private function resolveJsonPath(array $data, string $path): mixed {
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
     * Get available LMS connections (without exposing tokens or secrets).
     */
    public function getConnections(): array {
        $json = $this->config->getAppValue(Application::APP_ID, 'feed_connections', '[]');
        $connections = json_decode($json, true) ?: [];

        return array_map(function ($conn) {
            $result = [
                'id' => $conn['id'] ?? '',
                'name' => $conn['name'] ?? '',
                'type' => $conn['type'] ?? '',
                'baseUrl' => $conn['baseUrl'] ?? '',
                'active' => $conn['active'] ?? true,
                'hasToken' => !empty($conn['token']),
                'authMode' => $conn['authMode'] ?? 'token',
                'oidcAutoConnect' => $conn['oidcAutoConnect'] ?? false,
                'tenantId' => $conn['tenantId'] ?? '',
                'siteUrl' => $conn['siteUrl'] ?? '',
                'clientId' => $conn['clientId'] ?? '',
                'hasClientCredentials' => !empty($conn['clientId']) && !empty($conn['clientSecret']),
                'jiraEmail' => $conn['jiraEmail'] ?? '',
            ];
            // Include REST API-specific fields for all non-LMS types
            if (!in_array($conn['type'] ?? '', ['moodle', 'canvas', 'brightspace'], true)) {
                $result['customEndpoint'] = $conn['customEndpoint'] ?? '';
                $result['authMethod'] = $conn['authMethod'] ?? 'bearer';
                $result['apiKeyHeader'] = $conn['apiKeyHeader'] ?? '';
                $result['responseMapping'] = $conn['responseMapping'] ?? [];
                $result['customHeaders'] = $conn['customHeaders'] ?? [];
            }
            return $result;
        }, $connections);
    }

    /**
     * Save LMS connections (admin only).
     */
    public function saveConnections(array $connections): void {
        // Preserve existing encrypted tokens when token field is empty (masked)
        $existing = json_decode(
            $this->config->getAppValue(Application::APP_ID, 'feed_connections', '[]'),
            true
        ) ?: [];
        $existingById = [];
        foreach ($existing as $conn) {
            if (isset($conn['id'])) {
                $existingById[$conn['id']] = $conn;
            }
        }

        $toSave = [];
        foreach ($connections as $conn) {
            $id = !empty($conn['id']) ? $conn['id'] : bin2hex(random_bytes(8));
            $token = $conn['token'] ?? '';

            // If token is empty/masked, keep the existing encrypted token
            if (empty($token) && isset($existingById[$id]['token'])) {
                $encryptedToken = $existingById[$id]['token'];
            } elseif (!empty($token)) {
                $encryptedToken = $this->crypto->encrypt($token);
            } else {
                $encryptedToken = '';
            }

            // Handle clientSecret the same way as token
            $clientSecret = $conn['clientSecret'] ?? '';
            if (empty($clientSecret) && isset($existingById[$id]['clientSecret'])) {
                $encryptedClientSecret = $existingById[$id]['clientSecret'];
            } elseif (!empty($clientSecret)) {
                $encryptedClientSecret = $this->crypto->encrypt($clientSecret);
            } else {
                $encryptedClientSecret = '';
            }

            $entry = [
                'id' => $id,
                'name' => $conn['name'] ?? '',
                'type' => $conn['type'] ?? 'rss',
                'baseUrl' => $conn['baseUrl'] ?? '',
                'token' => $encryptedToken,
                'jiraEmail' => filter_var($conn['jiraEmail'] ?? '', FILTER_VALIDATE_EMAIL) ? $conn['jiraEmail'] : '',
                'active' => $conn['active'] ?? true,
                'authMode' => $conn['authMode'] ?? 'token',
                'clientId' => $conn['clientId'] ?? '',
                'clientSecret' => $encryptedClientSecret,
                'oidcAutoConnect' => $conn['oidcAutoConnect'] ?? false,
                'tenantId' => preg_match('/^[a-zA-Z0-9._-]{1,128}$/', $conn['tenantId'] ?? '') ? $conn['tenantId'] : '',
                'siteUrl' => filter_var($conn['siteUrl'] ?? '', FILTER_VALIDATE_URL) ? $conn['siteUrl'] : '',
            ];

            // REST API-specific fields for all non-LMS types
            if (!in_array($conn['type'] ?? '', ['moodle', 'canvas', 'brightspace'], true)) {
                $entry['customEndpoint'] = $conn['customEndpoint'] ?? '';
                $entry['authMethod'] = in_array($conn['authMethod'] ?? '', ['bearer', 'apikey', 'basic', 'none', 'client_credentials']) ? $conn['authMethod'] : 'bearer';
                $entry['apiKeyHeader'] = preg_match('/^[a-zA-Z0-9\-]{1,64}$/', $conn['apiKeyHeader'] ?? '') ? $conn['apiKeyHeader'] : '';
                $mapping = $conn['responseMapping'] ?? [];
                $entry['responseMapping'] = [
                    'items' => $this->sanitizeJsonPath($mapping['items'] ?? ''),
                    'title' => $this->sanitizeJsonPath($mapping['title'] ?? 'title'),
                    'url' => $this->sanitizeJsonPath($mapping['url'] ?? ''),
                    'excerpt' => $this->sanitizeJsonPath($mapping['excerpt'] ?? ''),
                    'date' => $this->sanitizeJsonPath($mapping['date'] ?? ''),
                    'image' => $this->sanitizeJsonPath($mapping['image'] ?? ''),
                    'author' => $this->sanitizeJsonPath($mapping['author'] ?? ''),
                ];

                // Custom request headers (sanitize keys and values)
                $customHeaders = [];
                foreach ($conn['customHeaders'] ?? [] as $header) {
                    $key = trim((string) ($header['key'] ?? ''));
                    $value = trim((string) ($header['value'] ?? ''));
                    if ($key !== '' && preg_match('/^[a-zA-Z0-9\-]{1,64}$/', $key)) {
                        $customHeaders[] = ['key' => $key, 'value' => mb_substr($value, 0, 256)];
                    }
                }
                $entry['customHeaders'] = array_slice($customHeaders, 0, 10);
            }

            // Invalidate cached client_credentials token when credentials change
            if ($this->cache !== null
                && ($entry['authMethod'] ?? '') === 'client_credentials'
                && !empty($clientSecret) // non-empty means admin entered new credentials
            ) {
                $this->cache->remove('cc_token_' . md5($id));
            }

            $toSave[] = $entry;
        }

        $this->config->setAppValue(Application::APP_ID, 'feed_connections', json_encode($toSave));
    }

    /**
     * Get a specific connection with decryptable token.
     */
    private function getConnection(string $connectionId, bool $includeInactive = false): ?array {
        if (empty($connectionId)) {
            return null;
        }

        $json = $this->config->getAppValue(Application::APP_ID, 'feed_connections', '[]');
        $connections = json_decode($json, true) ?: [];

        foreach ($connections as $conn) {
            if (($conn['id'] ?? '') === $connectionId) {
                if (!$includeInactive && !($conn['active'] ?? true)) {
                    return null;
                }
                return $conn;
            }
        }

        return null;
    }

    /**
     * Build Accept-Language header value from the user's Nextcloud language preference.
     */
    private function buildAcceptLanguage(?string $userId): string {
        if ($userId === null) {
            return 'en';
        }
        $lang = $this->config->getUserValue($userId, 'core', 'lang', 'en');
        $langCode = explode('_', $lang)[0];
        if ($langCode === 'en' || $langCode === '') {
            return 'en';
        }
        return $langCode . ',en;q=0.9';
    }

    /**
     * Validate that an HTTP response has a successful status code (2xx).
     */
    private function validateHttpResponse($response, string $context = ''): void {
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

    private function validateUrl(string $url): void {
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            throw new \InvalidArgumentException('Invalid URL');
        }
        $scheme = parse_url($url, PHP_URL_SCHEME);
        if ($scheme !== 'https' && $scheme !== 'http') {
            throw new \InvalidArgumentException('Only HTTP(S) URLs are supported');
        }

        // SSRF protection: block private/internal IP ranges
        // Note: Nextcloud's IClientService also enforces allow_local_address config,
        // providing a second layer of protection against DNS rebinding attacks.
        $host = parse_url($url, PHP_URL_HOST);
        if ($host !== null) {
            // Check all resolved IPs (not just the first) to prevent DNS rebinding
            $ips = gethostbynamel($host);
            if (is_array($ips)) {
                foreach ($ips as $ip) {
                    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false) {
                        throw new \InvalidArgumentException('URLs pointing to private or reserved IP addresses are not allowed');
                    }
                }
            }
        }
    }

    /**
     * Generate a signed proxy URL for an external image.
     * Uses HMAC-SHA256 to prevent the proxy from being used as an open relay.
     */
    public function signImageUrl(string $imageUrl): string {
        $day = (string)intdiv(time(), 86400);
        $sig = hash_hmac('sha256', $imageUrl . '|' . $day, $this->getImageProxySecret());
        $webRoot = \OC::$WEBROOT ?: '';
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
     * Fetch an external image for proxying.
     * Validates the URL (SSRF protection), enforces size limits, and checks content type.
     *
     * @return array{body: string, contentType: string}
     */
    private const ALLOWED_IMAGE_TYPES = [
        'image/jpeg', 'image/png', 'image/gif', 'image/webp',
        'image/x-icon', 'image/avif', 'image/svg+xml',
    ];

    public function proxyImage(string $url): array {
        $this->validateUrl($url);

        $client = $this->httpClient->newClient();
        $response = $client->get($url, [
            'timeout' => self::HTTP_TIMEOUT,
            'headers' => [
                'Accept' => 'image/jpeg, image/png, image/gif, image/webp, image/avif, image/svg+xml, image/x-icon',
                'Accept-Language' => $this->acceptLanguage,
                'User-Agent' => 'IntraVox/1.0 (Nextcloud Feed Reader)',
            ],
        ]);
        $this->validateHttpResponse($response, 'Image proxy');

        // Parse MIME type (strip charset/boundary parameters)
        $contentType = trim(strtok($response->getHeader('Content-Type'), ';'));
        if (!in_array($contentType, self::ALLOWED_IMAGE_TYPES, true)) {
            throw new \RuntimeException('Unsupported image type');
        }

        $body = $response->getBody();
        if (strlen($body) > self::MAX_IMAGE_SIZE) {
            throw new \RuntimeException('Image exceeds maximum size');
        }

        // SVG requires sanitization to prevent XSS
        if ($contentType === 'image/svg+xml') {
            $body = $this->sanitizeSvgContent($body);
        }

        return [
            'body' => $body,
            'contentType' => $contentType,
        ];
    }

    /**
     * Sign an image URL for proxying, or return null if invalid.
     */
    private function proxyImageUrl(?string $url): ?string {
        if ($url === null || !filter_var($url, FILTER_VALIDATE_URL)) {
            return null;
        }
        return $this->signImageUrl($url);
    }

    /**
     * Sanitize SVG content to prevent XSS attacks.
     * Uses enshrined/svg-sanitize + additional pattern checks.
     */
    private function sanitizeSvgContent(string $content): string {
        $sanitizer = new Sanitizer();
        $sanitizer->removeRemoteReferences(true);

        $clean = $sanitizer->sanitize($content);
        if ($clean === false || empty($clean)) {
            throw new \RuntimeException('SVG sanitization failed');
        }

        // Reject dangerous patterns that could bypass the sanitizer
        $dangerous = ['<!DOCTYPE', '<!ENTITY', '<iframe', '<embed', '<object',
            '<script', 'javascript:', 'data:text/html', 'SYSTEM', 'PUBLIC'];
        foreach ($dangerous as $pattern) {
            if (stripos($clean, $pattern) !== false) {
                throw new \RuntimeException('SVG contains prohibited content');
            }
        }

        return $clean;
    }

    /**
     * Safely decode a JSON response body with size limit to prevent OOM.
     */
    private function safeJsonDecode($response): ?array {
        $body = $response->getBody();
        if (strlen($body) > self::MAX_RESPONSE_SIZE) {
            throw new \RuntimeException('API response too large (' . round(strlen($body) / 1024 / 1024, 1) . ' MB, limit ' . (self::MAX_RESPONSE_SIZE / 1024 / 1024) . ' MB)');
        }
        return json_decode($body, true);
    }

    private function getImageProxySecret(): string {
        return hash('sha256', 'intravox-img-' . $this->config->getSystemValueString('secret', ''));
    }

    private function detectFileType(string $text): ?string {
        if (preg_match('/\.(\w{2,5})$/i', trim($text), $m)) {
            return strtolower($m[1]);
        }
        return null;
    }

    private function sanitizeExcerpt(string $html): string {
        // Strip HTML tags and decode entities
        $text = html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = preg_replace('/\s+/', ' ', $text);
        $text = trim($text);

        if (mb_strlen($text) > self::EXCERPT_LENGTH) {
            $truncated = mb_substr($text, 0, self::EXCERPT_LENGTH);
            $lastSpace = mb_strrpos($truncated, ' ');
            if ($lastSpace !== false && $lastSpace > self::EXCERPT_LENGTH * 0.7) {
                $truncated = mb_substr($truncated, 0, $lastSpace);
            }
            $text = $truncated . '...';
        }

        return $text;
    }

    private function extractImageFromHtml(string $html): ?string {
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

    private function parseDate(string $dateString): string {
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
     * Sanitize a JSON dot-notation path to prevent injection.
     * Only allows alphanumeric characters, dots, and underscores.
     */
    private function sanitizeJsonPath(string $path): string {
        $path = trim($path);
        if ($path === '') {
            return '';
        }
        if (!preg_match('/^[a-zA-Z0-9_.]+$/', $path)) {
            return '';
        }
        return $path;
    }

    private function buildCacheKey(string $sourceType, array $config, ?string $userId = null): string {
        $key = $sourceType . json_encode($config);
        if ($sourceType !== 'rss') {
            // Isolate cache per user for LMS feeds (personalized content)
            // Public/anonymous requests get a separate '_public' cache key
            $key .= $userId !== null ? ('_user_' . $userId) : '_public';
        }
        return 'feed_' . md5($key);
    }
}
