<?php

declare(strict_types=1);

namespace OCA\IntraVox\Service;

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
    private const HTTP_TIMEOUT = 10;
    private const MAX_ITEMS = 50;
    private const EXCERPT_LENGTH = 300;

    private ?ICache $cache = null;

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

        try {
            $result = match ($sourceType) {
                'rss' => $this->fetchRss($config['url'] ?? ''),
                'moodle' => $this->fetchMoodle($config, $userId),
                'canvas' => $this->fetchCanvas($config, $userId),
                'brightspace' => $this->fetchBrightspace($config, $userId),
                'custom_rest_api' => $this->fetchCustomRestApi($config, $userId),
                'nextcloud' => $this->fetchNextcloud($config, $userId),
                default => throw new \InvalidArgumentException("Unsupported source type: $sourceType"),
            };

            // Cache the full unfiltered result
            if ($this->cache !== null) {
                $this->cache->set($cacheKey, json_encode($result), self::CACHE_TTL);
            }

            // Apply filter and sort after caching (so cache contains all items)
            $result['items'] = $this->filterAndSortItems($result['items'], $sortBy, $sortOrder, $filterKeyword);
            $result['items'] = array_slice($result['items'], 0, $limit);
            $result['cached'] = false;
            return $result;
        } catch (\Exception $e) {
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
        ]);
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
                'User-Agent' => 'IntraVox/1.0 (Nextcloud Feed Reader)',
            ],
        ]);

        $body = $response->getBody();
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

        // Detect feed type and parse accordingly
        if (isset($xml->channel)) {
            // RSS 2.0
            $source = (string)($xml->channel->title ?? '');
            foreach ($xml->channel->item as $item) {
                $items[] = $this->normalizeRssItem($item);
            }
        } elseif ($xml->getName() === 'feed') {
            // Atom
            $source = (string)($xml->title ?? '');
            foreach ($xml->entry as $entry) {
                $items[] = $this->normalizeAtomEntry($entry);
            }
        } else {
            throw new \RuntimeException('Unrecognized feed format');
        }

        // Sort by date descending
        usort($items, fn($a, $b) => strtotime($b['date']) - strtotime($a['date']));

        return [
            'items' => array_slice($items, 0, self::MAX_ITEMS),
            'source' => $source,
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
            'image' => $image,
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
            'image' => $image,
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
        $resolved = $this->resolveToken($connectionId, $userId);
        if ($resolved === null) {
            throw new \RuntimeException('No Moodle token available. Please connect your account.');
        }
        $token = $resolved['token'];
        $courseId = $this->sanitizeCourseId($config['courseId'] ?? null);

        $client = $this->httpClient->newClient();
        $contentType = $config['contentType'] ?? 'news';

        return match ($contentType) {
            'my-courses' => $this->fetchMoodleMyCourses($client, $baseUrl, $token, $connection),
            'deadlines' => $this->fetchMoodleDeadlines($client, $baseUrl, $token, $connection),
            default => $this->fetchMoodleNews($client, $baseUrl, $token, $connection, $courseId),
        };
    }

    /**
     * Fetch news/forum discussions from Moodle.
     */
    private function fetchMoodleNews($client, string $baseUrl, string $token, array $connection, ?string $courseId): array {
        $items = [];

        if ($courseId) {
            // Fetch forum discussions for a specific course
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
        } else {
            // Fetch site-level announcements
            $response = $this->moodlePost($client, $baseUrl, $token, 'core_course_get_courses');
            $courses = json_decode($response->getBody(), true);
            if (is_array($courses) && !isset($courses['exception'])) {
                foreach (array_slice($courses, 0, self::MAX_ITEMS) as $course) {
                    $items[] = [
                        'id' => 'moodle-course-' . $course['id'],
                        'title' => $course['fullname'] ?? $course['shortname'] ?? '',
                        'url' => $baseUrl . '/course/view.php?id=' . $course['id'],
                        'excerpt' => $this->sanitizeExcerpt($course['summary'] ?? ''),
                        'image' => null,
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
     * Fetch the current user's enrolled courses from Moodle.
     */
    private function fetchMoodleMyCourses($client, string $baseUrl, string $token, array $connection): array {
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
                'image' => null,
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
                'image' => null,
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
                'image' => null,
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
        $url = $baseUrl . '/api/v1/courses?' . http_build_query([
            'enrollment_state' => 'active',
            'per_page' => self::MAX_ITEMS,
        ]);

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
                'image' => null,
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
                'image' => null,
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
     * Fetch items from a custom REST API using admin-configured connection settings.
     */
    private function fetchCustomRestApi(array $config, ?string $userId = null): array {
        $connectionId = $config['connectionId'] ?? '';
        $connection = $this->getConnection($connectionId);
        if ($connection === null) {
            throw new \RuntimeException('REST API connection not found');
        }

        $baseUrl = rtrim($connection['baseUrl'], '/');
        $this->validateUrl($baseUrl);
        $endpoint = $connection['customEndpoint'] ?? '';
        $url = $baseUrl . '/' . ltrim($endpoint, '/');
        $this->validateUrl($url);

        $authMethod = $connection['authMethod'] ?? 'bearer';
        $headers = ['Accept' => 'application/json'];

        // Build auth header
        if ($authMethod === 'bearer') {
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
                $headers['Authorization'] = 'Basic ' . $resolved['token'];
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

        $data = json_decode($response->getBody(), true);
        if (!is_array($data)) {
            return ['items' => [], 'source' => $connection['name'] ?? 'REST API'];
        }

        $mapping = $connection['responseMapping'] ?? [];
        $items = $this->mapJsonResponse($data, $mapping, $baseUrl, $connection['name'] ?? 'REST API');

        usort($items, fn($a, $b) => strtotime($b['date']) - strtotime($a['date']));

        return [
            'items' => array_slice($items, 0, self::MAX_ITEMS),
            'source' => $connection['name'] ?? 'REST API',
        ];
    }

    /**
     * Fetch personalized data from a Nextcloud instance (local or remote).
     * Local: uses internal OCS calls with current user session.
     * Remote: uses per-user OAuth2 Bearer token.
     */
    private function fetchNextcloud(array $config, ?string $userId = null): array {
        $connectionId = $config['connectionId'] ?? '';
        $connection = $this->getConnection($connectionId);
        if ($connection === null) {
            throw new \RuntimeException('Nextcloud connection not found');
        }

        $baseUrl = rtrim($connection['baseUrl'], '/');
        $contentType = $config['contentType'] ?? 'news';
        $sourceName = $connection['name'] ?? 'Nextcloud';

        // Determine endpoint based on content type
        $endpoint = match ($contentType) {
            'news' => '/ocs/v2.php/apps/activity/api/v2/activity?format=json&limit=50',
            'my-courses' => '/ocs/v2.php/apps/files_sharing/api/v1/shares?format=json&shared_with_me=true',
            'deadlines' => '/ocs/v2.php/apps/notifications/api/v2/notifications?format=json',
            default => '/ocs/v2.php/apps/activity/api/v2/activity?format=json&limit=50',
        };

        $url = $baseUrl . $endpoint;

        // Build auth headers
        $headers = [
            'Accept' => 'application/json',
            'OCS-APIRequest' => 'true',
        ];

        // Detect local instance
        $localUrl = $this->config->getSystemValueString('overwrite.cli.url', '');
        $isLocal = !empty($localUrl) && str_starts_with($baseUrl, rtrim($localUrl, '/'));

        if (!$isLocal) {
            $this->validateUrl($url);
            $resolved = $this->resolveToken($connectionId, $userId);
            if ($resolved === null) {
                throw new \RuntimeException('No Nextcloud token available. Please connect your account.');
            }
            $headers['Authorization'] = 'Bearer ' . $resolved['token'];
        } else {
            // Local instance: use app password from connection if available, or session
            $resolved = $this->resolveToken($connectionId, $userId);
            if ($resolved !== null) {
                $headers['Authorization'] = 'Bearer ' . $resolved['token'];
            }
        }

        $client = $this->httpClient->newClient();
        $response = $client->get($url, [
            'timeout' => self::HTTP_TIMEOUT,
            'headers' => $headers,
        ]);

        $body = json_decode($response->getBody(), true);

        // Parse based on content type
        $items = match ($contentType) {
            'news' => $this->parseNextcloudActivity($body, $baseUrl, $sourceName),
            'my-courses' => $this->parseNextcloudShares($body, $baseUrl, $sourceName),
            'deadlines' => $this->parseNextcloudNotifications($body, $baseUrl, $sourceName),
            default => $this->parseNextcloudActivity($body, $baseUrl, $sourceName),
        };

        usort($items, fn($a, $b) => strtotime($b['date']) - strtotime($a['date']));

        return [
            'items' => array_slice($items, 0, self::MAX_ITEMS),
            'source' => $sourceName,
        ];
    }

    private function parseNextcloudActivity(array $body, string $baseUrl, string $sourceName): array {
        $data = $body['ocs']['data'] ?? [];
        $items = [];
        foreach ($data as $activity) {
            $items[] = [
                'id' => 'nc-activity-' . ($activity['activity_id'] ?? md5($activity['subject'] ?? '')),
                'title' => $activity['subject'] ?? '',
                'url' => $activity['link'] ?? '',
                'excerpt' => $activity['message'] ?? '',
                'image' => $activity['icon'] ?? null,
                'date' => $activity['datetime'] ?? date('c'),
                'source' => $sourceName,
                'author' => $activity['user'] ?? null,
            ];
        }
        return $items;
    }

    private function parseNextcloudShares(array $body, string $baseUrl, string $sourceName): array {
        $data = $body['ocs']['data'] ?? [];
        $items = [];
        foreach ($data as $share) {
            $shareTypes = ['user' => 'User', 'group' => 'Group', 'link' => 'Link', 'email' => 'Email'];
            $items[] = [
                'id' => 'nc-share-' . ($share['id'] ?? ''),
                'title' => $share['file_target'] ?? $share['path'] ?? '',
                'url' => $baseUrl . '/f/' . ($share['file_source'] ?? ''),
                'excerpt' => 'Shared by ' . ($share['displayname_owner'] ?? $share['uid_owner'] ?? '') .
                    ($share['share_with_displayname'] ? ' with ' . $share['share_with_displayname'] : ''),
                'image' => null,
                'date' => date('c', $share['stime'] ?? time()),
                'source' => $sourceName,
                'author' => $share['displayname_owner'] ?? $share['uid_owner'] ?? null,
            ];
        }
        return $items;
    }

    private function parseNextcloudNotifications(array $body, string $baseUrl, string $sourceName): array {
        $data = $body['ocs']['data'] ?? [];
        $items = [];
        foreach ($data as $notification) {
            $items[] = [
                'id' => 'nc-notification-' . ($notification['notification_id'] ?? ''),
                'title' => $notification['subject'] ?? '',
                'url' => $notification['link'] ?? '',
                'excerpt' => $notification['message'] ?? '',
                'image' => $notification['icon'] ?? null,
                'date' => $notification['datetime'] ?? date('c'),
                'source' => $sourceName,
                'author' => null,
            ];
        }
        return $items;
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
            $date = $this->resolveJsonPath($raw, $mapping['date'] ?? '') ?? '';
            $image = $this->resolveJsonPath($raw, $mapping['image'] ?? '') ?? null;
            $author = $this->resolveJsonPath($raw, $mapping['author'] ?? '') ?? null;

            $items[] = [
                'id' => 'rest-' . md5($title . $url . $date),
                'title' => (string) $title,
                'url' => (string) $url,
                'excerpt' => $this->sanitizeExcerpt((string) $excerpt),
                'image' => is_string($image) && filter_var($image, FILTER_VALIDATE_URL) ? $image : null,
                'date' => $this->parseDate((string) $date),
                'source' => $sourceName,
                'author' => is_string($author) ? $author : null,
            ];
        }

        return $items;
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
                'authMode' => $conn['authMode'] ?? 'token',
                'oidcAutoConnect' => $conn['oidcAutoConnect'] ?? false,
                'hasClientCredentials' => !empty($conn['clientId']) && !empty($conn['clientSecret']),
            ];
            // Include REST API-specific fields
            if (($conn['type'] ?? '') === 'custom_rest_api') {
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
                'active' => $conn['active'] ?? true,
                'authMode' => $conn['authMode'] ?? 'token',
                'clientId' => $conn['clientId'] ?? '',
                'clientSecret' => $encryptedClientSecret,
                'oidcAutoConnect' => $conn['oidcAutoConnect'] ?? false,
            ];

            // REST API-specific fields
            if (($conn['type'] ?? '') === 'custom_rest_api') {
                $entry['customEndpoint'] = $conn['customEndpoint'] ?? '';
                $entry['authMethod'] = in_array($conn['authMethod'] ?? '', ['bearer', 'apikey', 'basic', 'none']) ? $conn['authMethod'] : 'bearer';
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

            $toSave[] = $entry;
        }

        $this->config->setAppValue(Application::APP_ID, 'feed_connections', json_encode($toSave));
    }

    /**
     * Get a specific connection with decryptable token.
     */
    private function getConnection(string $connectionId): ?array {
        if (empty($connectionId)) {
            return null;
        }

        $json = $this->config->getAppValue(Application::APP_ID, 'feed_connections', '[]');
        $connections = json_decode($json, true) ?: [];

        foreach ($connections as $conn) {
            if (($conn['id'] ?? '') === $connectionId && ($conn['active'] ?? true)) {
                return $conn;
            }
        }

        return null;
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

    private function sanitizeExcerpt(string $html): string {
        // Strip HTML tags and decode entities
        $text = html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = preg_replace('/\s+/', ' ', $text);
        $text = trim($text);

        if (mb_strlen($text) > self::EXCERPT_LENGTH) {
            $text = mb_substr($text, 0, self::EXCERPT_LENGTH) . '...';
        }

        return $text;
    }

    private function extractImageFromHtml(string $html): ?string {
        if (empty($html)) {
            return null;
        }
        if (preg_match('/<img[^>]+src=["\']([^"\']+)["\']/', $html, $matches)) {
            $src = $matches[1];
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
        $timestamp = strtotime($dateString);
        if ($timestamp === false) {
            return date('c');
        }
        return date('c', $timestamp);
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
