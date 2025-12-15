<?php
declare(strict_types=1);

namespace OCA\IntraVox\Service\Import;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Psr\Log\LoggerInterface;

/**
 * Confluence REST API Importer
 *
 * Imports content from Confluence via REST API
 * Supports Cloud, Server, and Data Center with auto-detection
 */
class ConfluenceApiImporter {
    private Client $httpClient;
    private ConfluenceImporter $confluenceImporter;
    private string $baseUrl;
    private string $version = 'unknown';
    private string $apiPrefix = ''; // Will be set to '' or '/wiki' after detection

    /** Rate limiting */
    private int $requestCount = 0;
    private float $lastRequestTime = 0;
    private int $maxRequestsPerMinute = 100;

    public function __construct(
        private LoggerInterface $logger,
        string $baseUrl,
        private string $authType,
        private string $authUser,
        private string $authToken
    ) {
        // Normalize base URL
        $this->baseUrl = rtrim($baseUrl, '/');

        // Initialize HTTP client
        $this->httpClient = new Client([
            'base_uri' => $this->baseUrl,
            'timeout' => 30,
            'headers' => $this->buildAuthHeaders(),
        ]);

        $this->confluenceImporter = new ConfluenceImporter($logger);
    }

    /**
     * Detect Confluence version (Cloud, Server, or Data Center)
     * Also detects the correct API prefix (/wiki or empty)
     *
     * @return string Version type: 'cloud', 'server', or 'datacenter'
     */
    public function detectVersion(): string {
        // Cloud detection: URL contains '.atlassian.net'
        if (str_contains($this->baseUrl, '.atlassian.net')) {
            $this->logger->info('Detected Confluence Cloud');
            $this->version = 'cloud';
            $this->apiPrefix = ''; // Cloud doesn't use /wiki prefix
            return 'cloud';
        }

        // Server/Data Center detection via REST API
        // Try with /wiki prefix first (common for Server installations)
        $prefixes = [
            '/wiki' => '/wiki/rest/api/settings/systemInfo',
            '' => '/rest/api/settings/systemInfo',
        ];

        foreach ($prefixes as $prefix => $endpoint) {
            try {
                $response = $this->httpClient->get($endpoint);
                $info = json_decode($response->getBody()->getContents(), true);

                // Success! Remember this prefix
                $this->apiPrefix = $prefix;

                if (isset($info['isDataCenter']) && $info['isDataCenter']) {
                    $this->logger->info('Detected Confluence Data Center with prefix: ' . ($prefix ?: 'none'));
                    $this->version = 'datacenter';
                    return 'datacenter';
                }

                $this->logger->info('Detected Confluence Server with prefix: ' . ($prefix ?: 'none'));
                $this->version = 'server';
                return 'server';

            } catch (GuzzleException $e) {
                // Try next prefix
                continue;
            }
        }

        // If all endpoints fail, assume Server with /wiki prefix (most common)
        $this->logger->warning('Failed to detect version via API, assuming Server with /wiki prefix');
        $this->version = 'server';
        $this->apiPrefix = '/wiki';
        return 'server';
    }

    /**
     * Test connection to Confluence
     *
     * @return array Status result with 'success' and optional 'error' message
     */
    public function testConnection(): array {
        try {
            // Detect version first (also sets $this->apiPrefix)
            $version = $this->detectVersion();

            // Try different endpoints for getting current user info
            // Try both /user/current and /myself endpoints with the detected prefix
            $endpoints = [
                $this->apiPrefix . '/rest/api/user/current',
                $this->apiPrefix . '/rest/api/myself',
            ];

            $user = null;
            $lastError = null;

            foreach ($endpoints as $endpoint) {
                try {
                    $response = $this->httpClient->get($endpoint);
                    $userData = json_decode($response->getBody()->getContents(), true);

                    if ($userData) {
                        $user = $userData;
                        $this->logger->info('Successfully authenticated via endpoint: ' . $endpoint);
                        break;
                    }
                } catch (GuzzleException $e) {
                    $lastError = $e->getMessage();
                    $this->logger->debug('Failed endpoint ' . $endpoint . ': ' . $lastError);
                    continue;
                }
            }

            if (!$user) {
                return [
                    'success' => false,
                    'error' => 'Failed to authenticate. Last error: ' . $lastError,
                ];
            }

            return [
                'success' => true,
                'version' => $version,
                'user' => $user['displayName'] ?? $user['username'] ?? $user['name'] ?? 'Unknown',
            ];

        } catch (GuzzleException $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * List available spaces
     *
     * @return array Spaces with keys and names
     */
    public function listSpaces(): array {
        try {
            // Ensure we have detected the version/prefix
            if (empty($this->apiPrefix) && $this->version === 'unknown') {
                $this->detectVersion();
            }

            $response = $this->httpClient->get($this->apiPrefix . '/rest/api/space', [
                'query' => [
                    'limit' => 100,
                    'expand' => 'description.plain,homepage',
                ],
            ]);

            $data = json_decode($response->getBody()->getContents(), true);
            $spaces = [];

            foreach ($data['results'] ?? [] as $space) {
                $spaces[] = [
                    'key' => $space['key'],
                    'name' => $space['name'],
                    'type' => $space['type'],
                    'description' => $space['description']['plain']['value'] ?? '',
                ];
            }

            return $spaces;

        } catch (GuzzleException $e) {
            $this->logger->error('Failed to list spaces: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Import entire space
     *
     * @param string $spaceKey Space key
     * @param string $language Target language for IntraVox
     * @return IntermediateFormat Intermediate representation
     */
    public function importSpace(string $spaceKey, string $language = 'nl'): IntermediateFormat {
        $this->logger->info('Importing Confluence space: ' . $spaceKey);

        $format = new IntermediateFormat();
        $format->language = $language;

        // Fetch all pages in space
        $pages = $this->fetchSpacePages($spaceKey);

        $this->logger->info('Found ' . count($pages) . ' pages in space');

        // Convert each page
        foreach ($pages as $pageInfo) {
            $pageData = $this->fetchPageContent($pageInfo['id'], $spaceKey);

            if ($pageData) {
                // Parse using ConfluenceImporter
                $pageFormat = $this->confluenceImporter->parse($pageData);

                // Merge pages
                foreach ($pageFormat->pages as $page) {
                    $page->language = $language; // Override language
                    $format->addPage($page);
                }

                // Merge media downloads
                foreach ($pageFormat->mediaDownloads as $media) {
                    $format->addMediaDownload($media);
                }
            }

            // Rate limiting
            $this->enforceRateLimit();
        }

        return $format;
    }

    /**
     * Fetch all pages in a space
     *
     * @param string $spaceKey Space key
     * @return array Page info (id, title, type)
     */
    private function fetchSpacePages(string $spaceKey): array {
        $pages = [];
        $start = 0;
        $limit = 50;

        try {
            do {
                $response = $this->httpClient->get($this->apiPrefix . '/rest/api/space/' . $spaceKey . '/content/page', [
                    'query' => [
                        'start' => $start,
                        'limit' => $limit,
                        'expand' => 'version,ancestors',
                    ],
                ]);

                $data = json_decode($response->getBody()->getContents(), true);

                foreach ($data['results'] ?? [] as $page) {
                    $pages[] = [
                        'id' => $page['id'],
                        'title' => $page['title'],
                        'type' => $page['type'],
                        'parentId' => $page['ancestors'][0]['id'] ?? null,
                    ];
                }

                $start += $limit;

                // Check if there are more pages
                $hasMore = isset($data['_links']['next']);

            } while ($hasMore);

        } catch (GuzzleException $e) {
            $this->logger->error('Failed to fetch space pages: ' . $e->getMessage());
        }

        return $pages;
    }

    /**
     * Fetch page content with Storage Format
     *
     * @param string $pageId Page ID
     * @param string $spaceKey Space key
     * @return array|null Page data or null
     */
    private function fetchPageContent(string $pageId, string $spaceKey): ?array {
        try {
            $response = $this->httpClient->get($this->apiPrefix . '/rest/api/content/' . $pageId, [
                'query' => [
                    'expand' => 'body.storage,version,ancestors,metadata.labels',
                ],
            ]);

            $data = json_decode($response->getBody()->getContents(), true);

            if (!$data) {
                return null;
            }

            // Extract Storage Format HTML
            $bodyHtml = $data['body']['storage']['value'] ?? '';

            // Determine parent slug
            $parentSlug = '';
            if (!empty($data['ancestors'])) {
                $parent = end($data['ancestors']);
                $parentSlug = $this->generateSlug($parent['title'] ?? '');
            }

            return [
                'title' => $data['title'],
                'body' => $bodyHtml,
                'parentSlug' => $parentSlug,
                'created' => $data['version']['when'] ?? null,
                'modified' => $data['version']['when'] ?? null,
                'author' => $data['version']['by']['displayName'] ?? null,
                'baseUrl' => $this->baseUrl,
                'spaceKey' => $spaceKey,
            ];

        } catch (GuzzleException $e) {
            $this->logger->error('Failed to fetch page ' . $pageId . ': ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Download attachment
     *
     * @param string $pageId Page ID
     * @param string $filename Attachment filename
     * @return string|null File content or null
     */
    public function downloadAttachment(string $pageId, string $filename): ?string {
        try {
            // First, get attachment ID
            $response = $this->httpClient->get('/rest/api/content/' . $pageId . '/child/attachment', [
                'query' => [
                    'filename' => $filename,
                ],
            ]);

            $data = json_decode($response->getBody()->getContents(), true);

            if (empty($data['results'])) {
                $this->logger->warning('Attachment not found: ' . $filename);
                return null;
            }

            $attachment = $data['results'][0];
            $downloadUrl = $attachment['_links']['download'] ?? null;

            if (!$downloadUrl) {
                return null;
            }

            // Download file
            $response = $this->httpClient->get($downloadUrl);
            return $response->getBody()->getContents();

        } catch (GuzzleException $e) {
            $this->logger->error('Failed to download attachment ' . $filename . ': ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Build authentication headers
     *
     * @return array Headers
     */
    private function buildAuthHeaders(): array {
        $headers = [
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ];

        switch ($this->authType) {
            case 'api_token': // Cloud: API Token
                $credentials = base64_encode($this->authUser . ':' . $this->authToken);
                $headers['Authorization'] = 'Basic ' . $credentials;
                break;

            case 'bearer': // Server/DC: Personal Access Token
                $headers['Authorization'] = 'Bearer ' . $this->authToken;
                break;

            case 'basic': // Server/DC: Basic Auth (legacy)
                $credentials = base64_encode($this->authUser . ':' . $this->authToken);
                $headers['Authorization'] = 'Basic ' . $credentials;
                break;

            default:
                $this->logger->warning('Unknown auth type: ' . $this->authType);
                break;
        }

        return $headers;
    }

    /**
     * Enforce rate limiting
     */
    private function enforceRateLimit(): void {
        $this->requestCount++;

        $now = microtime(true);
        $elapsed = $now - $this->lastRequestTime;

        // If we've made too many requests in the last minute, sleep
        if ($this->requestCount >= $this->maxRequestsPerMinute && $elapsed < 60) {
            $sleepTime = (int)((60 - $elapsed) * 1000000);
            $this->logger->debug('Rate limiting: sleeping for ' . ($sleepTime / 1000000) . ' seconds');
            usleep($sleepTime);
            $this->requestCount = 0;
            $this->lastRequestTime = microtime(true);
        } elseif ($elapsed >= 60) {
            // Reset counter after a minute
            $this->requestCount = 1;
            $this->lastRequestTime = $now;
        }
    }

    /**
     * Generate slug from title
     *
     * @param string $title Page title
     * @return string Slug
     */
    private function generateSlug(string $title): string {
        $slug = strtolower($title);
        $slug = preg_replace('/[^a-z0-9]+/', '-', $slug);
        $slug = trim($slug, '-');

        if (empty($slug)) {
            $slug = 'page-' . substr(md5($title), 0, 8);
        }

        return $slug;
    }
}
