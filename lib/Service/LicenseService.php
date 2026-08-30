<?php
declare(strict_types=1);

namespace OCA\IntraVox\Service;

use OCA\IntraVox\AppInfo\Application;
use OCP\Files\Folder;
use OCP\Support\Subscription\IRegistry;
use OCP\Files\NotFoundException;
use OCP\Http\Client\IClientService;
use OCP\IConfig;
use OCP\IURLGenerator;
use Psr\Log\LoggerInterface;
use OCA\IntraVox\Service\Path\PagePathHelper;

/**
 * Service for license management and page counting
 */
class LicenseService {
    private const FREE_LIMIT = 50; // Pages per language in free version
    /**
     * Above this many users the interface suggests a support subscription.
     *
     * Not a limit and not enforced anywhere -- the app behaves identically on
     * either side of it. It marks where paid subscriptions begin in the price
     * list, so below it there is genuinely nothing to suggest.
     */
    private const SUPPORT_NUDGE_USER_THRESHOLD = 100;

    private const DEFAULT_LICENSE_SERVER_URL = 'https://licenses.voxcloud.nl';

    private SetupService $setupService;
    private IConfig $config;
    private IClientService $clientService;
    private LoggerInterface $logger;
    private LanguageService $languageService;
    private IURLGenerator $urlGenerator;
    private UserCountService $userCounts;
    private ?IRegistry $subscriptionRegistry;

    public function __construct(
        SetupService $setupService,
        IConfig $config,
        IClientService $clientService,
        LoggerInterface $logger,
        LanguageService $languageService,
        IURLGenerator $urlGenerator,
        UserCountService $userCounts,
        ?IRegistry $subscriptionRegistry = null
    ) {
        $this->setupService = $setupService;
        $this->config = $config;
        $this->clientService = $clientService;
        $this->logger = $logger;
        $this->languageService = $languageService;
        $this->urlGenerator = $urlGenerator;
        $this->userCounts = $userCounts;
        $this->subscriptionRegistry = $subscriptionRegistry;
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
    /**
     * SHA-256 of the instance URL, so the licence server never sees the URL
     * itself.
     *
     * The source must be request-context-independent: the daily cron job and an
     * admin web request both compute this hash, and if they disagreed the server
     * would see two instances for one customer and freeze the seat count.
     */
    public function getInstanceUrlHash(): string {
        return hash('sha256', $this->normalizedInstanceUrl());
    }

    /**
     * Request-independent instance URL, lower-cased and without a trailing
     * slash. overwrite.cli.url wins; otherwise trusted_domains[0] is promoted
     * to https:// so it is a full URL rather than a bare hostname.
     *
     * Deliberately NOT getInstanceUrl(): that falls back to
     * getAbsoluteURL(), whose result derives from the current request host and
     * so differs between a web request and the cron job.
     */
    private function normalizedInstanceUrl(): string {
        $url = $this->config->getSystemValue('overwrite.cli.url', '');
        if (empty($url)) {
            $domain = $this->config->getSystemValue('trusted_domains', ['localhost'])[0] ?? 'localhost';
            // Promote a bare hostname to a full URL; leave an already-qualified
            // value (someone put a scheme in trusted_domains) untouched.
            $url = preg_match('#^https?://#i', $domain) ? $domain : 'https://' . $domain;
        }
        return strtolower(rtrim($url, '/'));
    }

    /**
     * The hash this app used to send before the change above, so the server can
     * recognise the instance across it instead of treating it as a second one —
     * which would be refused, freezing the seat count at its pre-update value.
     *
     * Returns '' when overwrite.cli.url is set (the hash never changed for those
     * instances) or when the legacy hash equals the current one. Otherwise it
     * keeps returning the legacy hash: we have no local signal that the server
     * has adopted the new one, so we keep sending it — the server is idempotent
     * and ignores it once adopted.
     */
    public function getPreviousInstanceUrlHash(): string {
        if (!empty($this->config->getSystemValue('overwrite.cli.url', ''))) {
            return '';
        }

        $legacy = strtolower(rtrim($this->getInstanceUrl(), '/'));
        $hash = hash('sha256', $legacy);

        return $hash === $this->getInstanceUrlHash() ? '' : $hash;
    }

    /**
     * Includes previousInstanceUrlHash while the legacy hash differs from the
     * current one, so the server can adopt the new hash. The field is omitted
     * for instances whose hash never changed (overwrite.cli.url set).
     */
    private function hashMigrationPayload(): array {
        $previous = $this->getPreviousInstanceUrlHash();

        return $previous === '' ? [] : ['previousInstanceUrlHash' => $previous];
    }

    /**
     * Get the instance URL
     */
    public function getInstanceUrl(): string {
        $instanceUrl = $this->config->getSystemValue('overwrite.cli.url', '');
        if (empty($instanceUrl)) {
            $instanceUrl = $this->urlGenerator->getAbsoluteURL('/');
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
                ] + $this->hashMigrationPayload(),
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

                // The server distinguishes expired from not-found, already-in-use
                // and inactive; getStats() reads from cache, so keep the reason or
                // the admin only ever learns that something is wrong.
                $this->config->setAppValue(Application::APP_ID, 'license_valid', 'false');
                $this->config->setAppValue(
                    Application::APP_ID,
                    'license_reason',
                    (string)($data['reason'] ?? '')
                );
                // A refused response carries validUntil flat rather than nested
                // under 'license', so an expired key can still name its date.
                $this->config->setAppValue(
                    Application::APP_ID,
                    'license_info',
                    json_encode($data['license'] ?? array_filter(
                        ['validUntil' => $data['validUntil'] ?? null],
                        static fn ($v) => $v !== null
                    ))
                );
                $this->config->setAppValue(Application::APP_ID, 'license_last_check', (string)time());

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
            $this->config->deleteAppValue(Application::APP_ID, 'license_reason');

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
            $userCount = $this->userCounts->getTotal();

            $pageCounts = $this->getPageCountsPerLanguage();

            $response = $client->post($this->getApiUrl('/usage'), [
                'json' => [
                    'licenseKey' => $licenseKey,
                    'instanceUrlHash' => $this->getInstanceUrlHash(),
                    'instanceName' => $this->getInstanceName(),
                    'appType' => 'intravox',
                    'currentPages' => $totalPages,
                    'pageCountsPerLanguage' => $pageCounts,
                    'currentUsers' => $userCount,
                    'activeUsers30d' => $this->userCounts->getActive(30),
                    'disabledUsers' => $this->userCounts->getDisabled(),
                    // Tells the server how the count was taken, so readings
                    // from releases that counted unreliably stay out of the
                    // averages a contract is measured against.
                    'countMethod' => UserCountService::COUNT_METHOD,
                    'appVersion' => $this->getAppVersion()
                ] + $this->hashMigrationPayload(),
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
                ] + $this->hashMigrationPayload(),
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

            if ($sharedFolder === null) {
                $this->logger->warning('LicenseService: Shared folder not available, returning empty counts');
                return [];
            }

            foreach ($this->languageService->getEnabledLanguages() as $lang) {
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
            foreach ($this->languageService->getEnabledLanguages() as $lang) {
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
                if (PagePathHelper::isInfrastructureFolder($folderName)) {
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
        $nextcloudVersion = $this->config->getSystemValue('version', 'unknown');

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
            'https://licenses.voxcloud.nl/api/telemetry/intravox'
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
        $limits = $this->checkPageLimit();
        $pageCounts = $this->getPageCountsPerLanguage();
        $hasLicense = !empty($this->getLicenseKey());

        // Read the cached verdict rather than calling the licence server: this
        // runs on every admin settings render, and a server that is merely slow
        // would otherwise stall the page for the full 10s timeout. The daily
        // LicenseUsageJob refreshes the cache, and saving a key revalidates
        // immediately via LicenseController.
        $licenseValid = false;
        $licenseInfo = null;
        $licenseReason = '';
        $licenseValidUntil = null;
        if ($hasLicense) {
            $licenseValid = $this->config->getAppValue(Application::APP_ID, 'license_valid', '') === 'true';
            $licenseInfo = json_decode(
                $this->config->getAppValue(Application::APP_ID, 'license_info', '{}'),
                true
            ) ?: null;
            if (!$licenseValid) {
                $licenseReason = $this->config->getAppValue(Application::APP_ID, 'license_reason', '');
            }
            // A valid response nests the dates under 'license'; a refused one
            // carries only validUntil. Either way the admin sees the date it
            // lapsed, not just that it did.
            $licenseValidUntil = $licenseInfo['license']['validUntil']
                ?? $licenseInfo['validUntil']
                ?? null;
        }

        // Mask license key for display
        $maskedKey = '';
        if ($hasLicense) {
            $key = $this->getLicenseKey();
            if (strlen($key) > 8) {
                $maskedKey = substr($key, 0, 4) . '-••••-••••-' . substr($key, -4);
            } else {
                $maskedKey = '••••••••';
            }
        }

        return [
            'pageCounts' => $pageCounts,
            'totalPages' => $this->getTotalPageCount(),
            'freeLimit' => self::FREE_LIMIT,
            'supportedLanguages' => $this->languageService->getEnabledLanguages(),
            // For the subscription notice: the same figure a subscription is
            // priced on, every account including disabled ones.
            'totalUsers' => $this->userCounts->getTotal(),
            'supportNudgeUserThreshold' => self::SUPPORT_NUDGE_USER_THRESHOLD,
            'hasValidSubscription' => $this->hasValidSubscription(),
            'hasExtendedSupport' => $this->hasExtendedSupport(),
            'hasLicense' => $hasLicense,
            'licenseValid' => $licenseValid,
            'licenseInfo' => $licenseInfo,
            'licenseReason' => $licenseReason,
            'licenseValidUntil' => $licenseValidUntil,
            'licenseKeyMasked' => $maskedKey,
            'maxPagesPerLanguage' => $limits['max'] ?? self::FREE_LIMIT,
            'exceededLanguages' => $limits['exceededLanguages'] ?? [],
            'pagesExceeded' => !$limits['allowed'],
            'perLanguage' => true,
        ];
    }

    /**
     * Whether the host Nextcloud has a valid Enterprise subscription.
     *
     * Asks IRegistry rather than OCP\Util::hasExtendedSupport(), which answers a
     * different question: that helper reports the paid Extended Support add-on,
     * so an ordinary Enterprise customer without it answers false and looks like
     * Community. It also falls back to the `extendedSupport` system config value
     * when the registry is missing, which an admin can set by hand.
     *
     * Mirrors TelemetryService, so the settings page and the report sent to the
     * licence server cannot disagree about the same instance.
     */
    private function hasValidSubscription(): bool {
        try {
            return $this->subscriptionRegistry?->delegateHasValidSubscription() ?? false;
        } catch (\Throwable $e) {
            $this->logger->debug('LicenseService: delegateHasValidSubscription() check failed', [
                'error' => $e->getMessage()
            ]);
        }
        return false;
    }

    /**
     * Whether that subscription also carries the Extended Support add-on. A
     * strict subset of hasValidSubscription(), reported separately so the two
     * signals stay distinguishable.
     */
    private function hasExtendedSupport(): bool {
        try {
            return $this->subscriptionRegistry?->delegateHasExtendedSupport() ?? false;
        } catch (\Throwable $e) {
            $this->logger->debug('LicenseService: delegateHasExtendedSupport() check failed', [
                'error' => $e->getMessage()
            ]);
        }
        return false;
    }

}
