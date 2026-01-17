<?php
declare(strict_types=1);

namespace OCA\IntraVox\Service;

use OCA\IntraVox\AppInfo\Application;
use OCP\Files\Folder;
use OCP\Files\NotFoundException;
use OCP\Http\Client\IClientService;
use OCP\IConfig;
use Psr\Log\LoggerInterface;

/**
 * Service for license management and page counting
 */
class LicenseService {
    private const SUPPORTED_LANGUAGES = ['nl', 'en', 'de', 'fr'];
    private const FREE_LIMIT = 50; // Pages per language in free version
    private const DEFAULT_LICENSE_SERVER_URL = 'https://licenses.voxcloud.nl';

    private SetupService $setupService;
    private IConfig $config;
    private IClientService $clientService;
    private LoggerInterface $logger;

    public function __construct(
        SetupService $setupService,
        IConfig $config,
        IClientService $clientService,
        LoggerInterface $logger
    ) {
        $this->setupService = $setupService;
        $this->config = $config;
        $this->clientService = $clientService;
        $this->logger = $logger;
    }

    /**
     * Get the configured license server URL
     */
    public function getLicenseServerUrl(): string {
        $url = $this->config->getAppValue(Application::APP_ID, 'license_server_url', '');
        return empty($url) ? self::DEFAULT_LICENSE_SERVER_URL : $url;
    }

    /**
     * Set the license server URL
     */
    public function setLicenseServerUrl(string $url): void {
        $url = rtrim(trim($url), '/');
        $this->config->setAppValue(Application::APP_ID, 'license_server_url', $url);
        // Clear cached validation result when server changes
        $this->config->deleteAppValue(Application::APP_ID, 'license_valid');
        $this->config->deleteAppValue(Application::APP_ID, 'license_info');
    }

    /**
     * Get the full API endpoint URL
     */
    private function getApiUrl(string $endpoint): string {
        return $this->getLicenseServerUrl() . '/api/licenses' . $endpoint;
    }

    /**
     * Get the configured license key
     */
    public function getLicenseKey(): ?string {
        $key = $this->config->getAppValue(Application::APP_ID, 'license_key', '');
        return empty($key) ? null : $key;
    }

    /**
     * Set the license key
     */
    public function setLicenseKey(string $key): void {
        $this->config->setAppValue(Application::APP_ID, 'license_key', trim($key));
        // Clear cached validation result
        $this->config->deleteAppValue(Application::APP_ID, 'license_valid');
        $this->config->deleteAppValue(Application::APP_ID, 'license_info');
    }

    /**
     * Get the instance URL hash (SHA-256) for privacy
     * The hash is calculated client-side to avoid sending the plain URL
     */
    public function getInstanceUrlHash(): string {
        $instanceUrl = $this->getInstanceUrl();
        // Normalize: lowercase, remove trailing slash
        $normalizedUrl = strtolower(rtrim($instanceUrl, '/'));
        return hash('sha256', $normalizedUrl);
    }

    /**
     * Get the instance URL
     */
    public function getInstanceUrl(): string {
        $instanceUrl = $this->config->getSystemValue('overwrite.cli.url', '');
        if (empty($instanceUrl)) {
            $instanceUrl = \OC::$server->getURLGenerator()->getAbsoluteURL('/');
        }
        return $instanceUrl;
    }

    /**
     * Get the instance name (for display in license server admin)
     */
    public function getInstanceName(): string {
        return $this->config->getAppValue(
            Application::APP_ID,
            'instance_name',
            $this->config->getSystemValue('instancename', 'IntraVox Instance')
        );
    }

    /**
     * Validate license against the license server
     * @return array{valid: bool, reason?: string, license?: array, limits?: array}
     */
    public function validateLicense(): array {
        $licenseKey = $this->getLicenseKey();

        if (empty($licenseKey)) {
            return [
                'valid' => false,
                'reason' => 'No license key configured',
                'isFree' => true
            ];
        }

        try {
            $client = $this->clientService->newClient();
            $response = $client->post($this->getApiUrl('/validate'), [
                'json' => [
                    'licenseKey' => $licenseKey,
                    'instanceUrlHash' => $this->getInstanceUrlHash(),
                    'appType' => 'intravox'
                ],
                'timeout' => 10,
                'headers' => [
                    'User-Agent' => 'IntraVox/' . $this->getAppVersion(),
                    'Content-Type' => 'application/json'
                ],
            ]);

            $data = json_decode($response->getBody(), true);

            if (!$data['valid']) {
                $this->logger->warning('LicenseService: License validation failed', [
                    'reason' => $data['reason'] ?? 'Unknown'
                ]);
                return [
                    'valid' => false,
                    'reason' => $data['reason'] ?? 'License validation failed',
                    'isFree' => true
                ];
            }

            // Cache the result
            $this->config->setAppValue(Application::APP_ID, 'license_valid', 'true');
            $this->config->setAppValue(Application::APP_ID, 'license_info', json_encode($data['license'] ?? []));
            $this->config->setAppValue(Application::APP_ID, 'license_last_check', (string)time());

            $this->logger->info('LicenseService: License validated successfully');

            return [
                'valid' => true,
                'license' => $data['license'] ?? [],
                'limits' => $data['limits'] ?? [],
                'isFree' => false
            ];
        } catch (\Exception $e) {
            $this->logger->warning('LicenseService: Failed to validate license', [
                'error' => $e->getMessage()
            ]);

            // If we can't reach the server, use cached result if available
            $cachedValid = $this->config->getAppValue(Application::APP_ID, 'license_valid', '');
            if ($cachedValid === 'true') {
                $cachedInfo = $this->config->getAppValue(Application::APP_ID, 'license_info', '{}');
                return [
                    'valid' => true,
                    'license' => json_decode($cachedInfo, true),
                    'cached' => true,
                    'isFree' => false
                ];
            }

            return [
                'valid' => false,
                'reason' => 'Could not connect to license server',
                'isFree' => true
            ];
        }
    }

    /**
     * Update usage statistics on the license server
     * Uses hashed instance URL for privacy
     */
    public function updateUsage(): array {
        $licenseKey = $this->getLicenseKey();

        if (empty($licenseKey)) {
            return ['success' => false, 'reason' => 'No license key configured'];
        }

        try {
            $client = $this->clientService->newClient();
            $totalPages = $this->getTotalPageCount();
            $userCount = $this->getUserCount();

            $pageCounts = $this->getPageCountsPerLanguage();

            $response = $client->post($this->getApiUrl('/usage'), [
                'json' => [
                    'licenseKey' => $licenseKey,
                    'instanceUrlHash' => $this->getInstanceUrlHash(),
                    'instanceName' => $this->getInstanceName(),
                    'appType' => 'intravox',
                    'currentPages' => $totalPages,
                    'pageCountsPerLanguage' => $pageCounts,
                    'currentUsers' => $userCount
                ],
                'timeout' => 15,
                'headers' => [
                    'User-Agent' => 'IntraVox/' . $this->getAppVersion(),
                    'Content-Type' => 'application/json'
                ],
            ]);

            $data = json_decode($response->getBody(), true);

            if ($data['success'] ?? false) {
                $this->logger->info('LicenseService: Usage updated successfully', [
                    'pages' => $totalPages,
                    'users' => $userCount
                ]);

                // Store limits info
                if (isset($data['limits'])) {
                    $this->config->setAppValue(Application::APP_ID, 'license_limits', json_encode($data['limits']));
                }

                return [
                    'success' => true,
                    'usage' => $data['usage'] ?? null,
                    'limits' => $data['limits'] ?? null
                ];
            }

            return [
                'success' => false,
                'reason' => $data['error'] ?? 'Unknown error'
            ];
        } catch (\Exception $e) {
            $this->logger->warning('LicenseService: Failed to update usage', [
                'error' => $e->getMessage()
            ]);
            return [
                'success' => false,
                'reason' => $e->getMessage()
            ];
        }
    }

    /**
     * Check if user can create more pages for a specific language
     * @param string|null $language The language to check, or null for overall check
     */
    public function checkPageLimit(?string $language = null): array {
        $licenseKey = $this->getLicenseKey();
        $pageCounts = $this->getPageCountsPerLanguage();
        $totalPages = array_sum($pageCounts);

        // Free version check - limit is per language
        if (empty($licenseKey)) {
            // Check per language if specified
            if ($language !== null && isset($pageCounts[$language])) {
                $currentForLang = $pageCounts[$language];
                $exceeded = $currentForLang >= self::FREE_LIMIT;
                return [
                    'allowed' => !$exceeded,
                    'current' => $currentForLang,
                    'max' => self::FREE_LIMIT,
                    'language' => $language,
                    'isFree' => true,
                    'perLanguage' => true,
                    'reason' => $exceeded ? "Free tier limit of " . self::FREE_LIMIT . " pages per language exceeded for {$language}" : null
                ];
            }

            // Check if any language is exceeded
            $exceededLanguages = [];
            foreach ($pageCounts as $lang => $count) {
                if ($count >= self::FREE_LIMIT) {
                    $exceededLanguages[] = $lang;
                }
            }

            return [
                'allowed' => empty($exceededLanguages),
                'current' => $totalPages,
                'currentPerLanguage' => $pageCounts,
                'max' => self::FREE_LIMIT,
                'exceededLanguages' => $exceededLanguages,
                'isFree' => true,
                'perLanguage' => true,
                'reason' => !empty($exceededLanguages) ? 'Free tier page limit exceeded for: ' . implode(', ', $exceededLanguages) : null
            ];
        }

        try {
            $client = $this->clientService->newClient();
            $response = $client->post($this->getApiUrl('/check-page-limit'), [
                'json' => [
                    'licenseKey' => $licenseKey,
                    'instanceUrlHash' => $this->getInstanceUrlHash(),
                    'language' => $language,
                    'pageCountsPerLanguage' => $pageCounts
                ],
                'timeout' => 10,
                'headers' => [
                    'User-Agent' => 'IntraVox/' . $this->getAppVersion(),
                    'Content-Type' => 'application/json'
                ],
            ]);

            $data = json_decode($response->getBody(), true);
            return [
                'allowed' => $data['allowed'] ?? false,
                'current' => $data['current'] ?? $totalPages,
                'currentPerLanguage' => $pageCounts,
                'max' => $data['max'] ?? null,
                'exceededLanguages' => $data['exceededLanguages'] ?? [],
                'reason' => $data['reason'] ?? null,
                'perLanguage' => $data['perLanguage'] ?? true,
                'isFree' => false
            ];
        } catch (\Exception $e) {
            $this->logger->warning('LicenseService: Failed to check page limit', [
                'error' => $e->getMessage()
            ]);

            // Fall back to cached limits if available
            $cachedLimits = $this->config->getAppValue(Application::APP_ID, 'license_limits', '');
            if (!empty($cachedLimits)) {
                $limits = json_decode($cachedLimits, true);
                $maxPagesPerLang = $limits['maxPagesPerLanguage'] ?? $limits['maxPages'] ?? null;

                // Check per language
                $exceededLanguages = [];
                if ($maxPagesPerLang !== null) {
                    foreach ($pageCounts as $lang => $count) {
                        if ($count >= $maxPagesPerLang) {
                            $exceededLanguages[] = $lang;
                        }
                    }
                }

                return [
                    'allowed' => empty($exceededLanguages),
                    'current' => $totalPages,
                    'currentPerLanguage' => $pageCounts,
                    'max' => $maxPagesPerLang,
                    'exceededLanguages' => $exceededLanguages,
                    'cached' => true,
                    'perLanguage' => true,
                    'isFree' => false
                ];
            }

            // If we can't verify, allow creation
            return [
                'allowed' => true,
                'current' => $totalPages,
                'currentPerLanguage' => $pageCounts,
                'max' => null,
                'reason' => 'Could not verify limit',
                'perLanguage' => true,
                'isFree' => false
            ];
        }
    }

    /**
     * Get page counts per language
     * @return array ['en' => 45, 'nl' => 32, ...]
     */
    public function getPageCountsPerLanguage(): array {
        $counts = [];

        try {
            $sharedFolder = $this->setupService->getSharedFolder();

            foreach (self::SUPPORTED_LANGUAGES as $lang) {
                try {
                    if ($sharedFolder->nodeExists($lang)) {
                        $langFolder = $sharedFolder->get($lang);
                        if ($langFolder instanceof Folder) {
                            $counts[$lang] = $this->countPagesInFolder($langFolder);
                        } else {
                            $counts[$lang] = 0;
                        }
                    } else {
                        $counts[$lang] = 0;
                    }
                } catch (\Exception $e) {
                    $this->logger->warning('LicenseService: Error counting pages for ' . $lang, [
                        'error' => $e->getMessage()
                    ]);
                    $counts[$lang] = 0;
                }
            }
        } catch (\Exception $e) {
            $this->logger->warning('LicenseService: Could not access shared folder', [
                'error' => $e->getMessage()
            ]);
            // Return zeros for all languages if folder not accessible
            foreach (self::SUPPORTED_LANGUAGES as $lang) {
                $counts[$lang] = 0;
            }
        }

        return $counts;
    }

    /**
     * Count pages recursively in a folder
     * A page is defined as a folder containing a .json file with the same name
     */
    private function countPagesInFolder(Folder $folder): int {
        $count = 0;

        // Check for home.json in root (this is a page)
        try {
            $folder->get('home.json');
            $count++;
        } catch (NotFoundException $e) {
            // No home page
        }

        // Iterate through all items
        foreach ($folder->getDirectoryListing() as $node) {
            if ($node instanceof Folder) {
                $folderName = $node->getName();

                // Skip special folders
                if (in_array($folderName, ['_media', '_resources', '_versions'])) {
                    continue;
                }

                // Check if this folder is a page (has a .json file with same name)
                try {
                    $node->get($folderName . '.json');
                    $count++;
                } catch (NotFoundException $e) {
                    // Not a page folder, just continue
                }

                // Recursively count pages in subfolders
                $count += $this->countPagesInFolder($node);
            }
        }

        return $count;
    }

    /**
     * Get total page count across all languages
     */
    public function getTotalPageCount(): int {
        $counts = $this->getPageCountsPerLanguage();
        return array_sum($counts);
    }

    /**
     * Get the number of users with access to IntraVox
     */
    private function getUserCount(): int {
        try {
            // Count users in the IntraVox group if it exists
            $groupManager = \OC::$server->get(\OCP\IGroupManager::class);
            $group = $groupManager->get('intravox');
            if ($group) {
                return count($group->getUsers());
            }

            // Fall back to counting all users
            $userManager = \OC::$server->get(\OCP\IUserManager::class);
            // This is a rough count - in production you might want to be more specific
            $count = 0;
            $userManager->callForSeenUsers(function ($user) use (&$count) {
                $count++;
            });
            return $count;
        } catch (\Exception $e) {
            $this->logger->warning('LicenseService: Failed to count users', [
                'error' => $e->getMessage()
            ]);
            return 0;
        }
    }

    /**
     * Get free tier limit per language
     */
    public function getFreeLimit(): int {
        return self::FREE_LIMIT;
    }

    /**
     * Get the app version
     */
    private function getAppVersion(): string {
        return $this->config->getAppValue(Application::APP_ID, 'installed_version', '0.0.0');
    }

    /**
     * Get instance info for telemetry
     */
    public function getInstanceInfo(): array {
        // Get Nextcloud version
        $nextcloudVersion = \OC::$server->getConfig()->getSystemValue('version', 'unknown');

        return [
            'instance_url_hash' => $this->getInstanceUrlHash(),
            'instance_name' => $this->getInstanceName(),
            'intravox_version' => $this->getAppVersion(),
            'nextcloud_version' => $nextcloudVersion,
            'page_counts' => $this->getPageCountsPerLanguage(),
            'total_pages' => $this->getTotalPageCount(),
            'timestamp' => (new \DateTime())->format('c'),
        ];
    }

    /**
     * Send telemetry to license server (opt-in)
     * @return bool Success status
     */
    public function sendTelemetry(): bool {
        $telemetryUrl = $this->config->getAppValue(
            Application::APP_ID,
            'telemetry_url',
            'https://license.shalution.nl/api/telemetry/intravox'
        );

        // Check if telemetry is enabled
        $telemetryEnabled = $this->config->getAppValue(
            Application::APP_ID,
            'telemetry_enabled',
            'false'
        ) === 'true';

        if (!$telemetryEnabled) {
            $this->logger->info('LicenseService: Telemetry is disabled');
            return false;
        }

        try {
            $client = $this->clientService->newClient();
            $instanceInfo = $this->getInstanceInfo();

            $response = $client->post($telemetryUrl, [
                'json' => $instanceInfo,
                'timeout' => 10,
                'headers' => [
                    'User-Agent' => 'IntraVox/' . $instanceInfo['intravox_version'],
                ],
            ]);

            $statusCode = $response->getStatusCode();

            if ($statusCode >= 200 && $statusCode < 300) {
                $this->logger->info('LicenseService: Telemetry sent successfully');
                return true;
            } else {
                $this->logger->warning('LicenseService: Telemetry failed', [
                    'status' => $statusCode
                ]);
                return false;
            }
        } catch (\Exception $e) {
            $this->logger->warning('LicenseService: Failed to send telemetry', [
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Get license statistics for admin panel
     */
    public function getStats(): array {
        $validation = $this->validateLicense();
        $limits = $this->checkPageLimit();
        $pageCounts = $this->getPageCountsPerLanguage();

        return [
            'pageCounts' => $pageCounts,
            'totalPages' => $this->getTotalPageCount(),
            'freeLimit' => self::FREE_LIMIT,
            'supportedLanguages' => self::SUPPORTED_LANGUAGES,
            'hasLicense' => !empty($this->getLicenseKey()),
            'licenseValid' => $validation['valid'],
            'licenseInfo' => $validation['license'] ?? null,
            'maxPagesPerLanguage' => $limits['max'] ?? self::FREE_LIMIT,
            'exceededLanguages' => $limits['exceededLanguages'] ?? [],
            'pagesExceeded' => !$limits['allowed'],
            'perLanguage' => true,
        ];
    }
}