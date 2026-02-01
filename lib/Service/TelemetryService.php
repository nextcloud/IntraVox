<?php
declare(strict_types=1);

namespace OCA\IntraVox\Service;

use OCA\IntraVox\AppInfo\Application;
use OCP\Http\Client\IClientService;
use OCP\IConfig;
use OCP\IUserManager;
use OCP\IGroupManager;
use Psr\Log\LoggerInterface;

/**
 * Service for anonymous telemetry data collection and reporting
 * This is an opt-in feature that helps improve IntraVox
 */
class TelemetryService {
    private const TELEMETRY_URL = 'https://licenses.voxcloud.nl/api/telemetry/report';

    private IClientService $httpClient;
    private IConfig $config;
    private LoggerInterface $logger;
    private PageService $pageService;
    private LicenseService $licenseService;
    private IUserManager $userManager;
    private IGroupManager $groupManager;

    public function __construct(
        IClientService $httpClient,
        IConfig $config,
        LoggerInterface $logger,
        PageService $pageService,
        LicenseService $licenseService,
        IUserManager $userManager,
        IGroupManager $groupManager
    ) {
        $this->httpClient = $httpClient;
        $this->config = $config;
        $this->logger = $logger;
        $this->pageService = $pageService;
        $this->licenseService = $licenseService;
        $this->userManager = $userManager;
        $this->groupManager = $groupManager;
    }

    /**
     * Check if telemetry is enabled
     * Default is true (opt-out instead of opt-in)
     */
    public function isEnabled(): bool {
        return $this->config->getAppValue(Application::APP_ID, 'telemetry_enabled', 'true') === 'true';
    }

    /**
     * Enable or disable telemetry
     */
    public function setEnabled(bool $enabled): void {
        $this->config->setAppValue(Application::APP_ID, 'telemetry_enabled', $enabled ? 'true' : 'false');
        $this->logger->info('TelemetryService: Telemetry ' . ($enabled ? 'enabled' : 'disabled'));
    }

    /**
     * Get the telemetry server URL
     */
    public function getTelemetryUrl(): string {
        return $this->config->getAppValue(
            Application::APP_ID,
            'telemetry_url',
            self::TELEMETRY_URL
        );
    }

    /**
     * Send telemetry report to the server
     * @return bool Success status
     */
    public function sendReport(): bool {
        if (!$this->isEnabled()) {
            $this->logger->debug('TelemetryService: Telemetry is disabled, skipping report');
            return false;
        }

        try {
            $data = $this->collectData();

            $client = $this->httpClient->newClient();
            $response = $client->post($this->getTelemetryUrl(), [
                'json' => $data,
                'timeout' => 15,
                'headers' => [
                    'User-Agent' => 'IntraVox/' . $this->getAppVersion(),
                    'Content-Type' => 'application/json'
                ]
            ]);

            $statusCode = $response->getStatusCode();

            if ($statusCode >= 200 && $statusCode < 300) {
                $this->logger->info('TelemetryService: Report sent successfully', [
                    'totalPages' => $data['totalPages'],
                    'totalUsers' => $data['totalUsers']
                ]);

                // Store last report time
                $this->config->setAppValue(
                    Application::APP_ID,
                    'telemetry_last_report',
                    (string)time()
                );

                return true;
            }

            $this->logger->warning('TelemetryService: Report failed', [
                'statusCode' => $statusCode
            ]);
            return false;
        } catch (\Exception $e) {
            $this->logger->warning('TelemetryService: Report failed', [
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Collect telemetry data
     */
    private function collectData(): array {
        $instanceHash = $this->licenseService->getInstanceUrlHash();
        $pageCounts = $this->licenseService->getPageCountsPerLanguage();
        $totalPages = array_sum($pageCounts);

        return [
            'instanceHash' => $instanceHash,
            'totalPages' => $totalPages,
            'pageCountsPerLanguage' => $pageCounts,
            'totalUsers' => $this->getUserCount(),
            'activeUsers30d' => $this->getActiveUserCount(30),
            'intravoxVersion' => $this->getAppVersion(),
            'nextcloudVersion' => $this->getNextcloudVersion(),
            'phpVersion' => PHP_VERSION,
            'countryCode' => $this->getCountryCode(),
            'databaseType' => $this->config->getSystemValue('dbtype', 'sqlite'),
            'defaultLanguage' => $this->config->getSystemValue('default_language', 'en'),
            'defaultTimezone' => $this->getDefaultTimezone(),
            'osFamily' => PHP_OS_FAMILY,
            'webServer' => $this->getWebServer(),
            'isDocker' => $this->isDocker(),
        ];
    }

    /**
     * Get total user count
     */
    private function getUserCount(): int {
        try {
            // First try the IntraVox group if it exists
            $group = $this->groupManager->get('intravox');
            if ($group !== null) {
                return count($group->getUsers());
            }

            // Fall back to counting all users
            $count = 0;
            $this->userManager->callForSeenUsers(function ($user) use (&$count) {
                $count++;
            });
            return $count;
        } catch (\Exception $e) {
            $this->logger->warning('TelemetryService: Failed to count users', [
                'error' => $e->getMessage()
            ]);
            return 0;
        }
    }

    /**
     * Get active user count for the last N days
     */
    private function getActiveUserCount(int $days): int {
        try {
            $cutoffTime = time() - ($days * 24 * 60 * 60);
            $count = 0;

            $this->userManager->callForSeenUsers(function ($user) use (&$count, $cutoffTime) {
                $lastLogin = $user->getLastLogin();
                if ($lastLogin >= $cutoffTime) {
                    $count++;
                }
            });

            return $count;
        } catch (\Exception $e) {
            $this->logger->warning('TelemetryService: Failed to count active users', [
                'error' => $e->getMessage()
            ]);
            return 0;
        }
    }

    /**
     * Get the IntraVox app version
     */
    private function getAppVersion(): string {
        return $this->config->getAppValue(Application::APP_ID, 'installed_version', 'unknown');
    }

    /**
     * Get the Nextcloud version
     */
    private function getNextcloudVersion(): string {
        return $this->config->getSystemValue('version', 'unknown');
    }

    /**
     * Get the last report timestamp
     */
    public function getLastReportTime(): ?int {
        $time = $this->config->getAppValue(Application::APP_ID, 'telemetry_last_report', '');
        return empty($time) ? null : (int)$time;
    }

    /**
     * Check if a report should be sent (not sent in last 24 hours)
     */
    public function shouldSendReport(): bool {
        if (!$this->isEnabled()) {
            return false;
        }

        $lastReport = $this->getLastReportTime();
        if ($lastReport === null) {
            return true;
        }

        // Send report if more than 24 hours since last report
        return (time() - $lastReport) > (24 * 60 * 60);
    }

    /**
     * Get ISO 3166-1 alpha-2 country code from default_phone_region setting
     * Returns null if not configured — server derives country from timezone
     */
    private function getCountryCode(): ?string {
        $region = $this->config->getSystemValue('default_phone_region', '');
        if (!empty($region) && preg_match('/^[A-Z]{2}$/', strtoupper($region))) {
            return strtoupper($region);
        }
        return null;
    }

    /**
     * Get the default timezone setting
     * Tries Nextcloud config first, then PHP default, falls back to UTC
     */
    private function getDefaultTimezone(): string {
        $tz = $this->config->getSystemValue('default_timezone', '');
        if (!empty($tz) && $tz !== 'UTC') {
            return $tz;
        }
        // Try PHP's configured timezone (from php.ini)
        $phpTz = date_default_timezone_get();
        if (!empty($phpTz) && $phpTz !== 'UTC') {
            return $phpTz;
        }
        return 'UTC';
    }

    /**
     * Detect web server from SERVER_SOFTWARE header
     */
    private function getWebServer(): ?string {
        $software = $_SERVER['SERVER_SOFTWARE'] ?? null;
        if ($software === null) {
            return null;
        }
        if (stripos($software, 'apache') !== false) {
            return 'Apache';
        }
        if (stripos($software, 'nginx') !== false) {
            return 'nginx';
        }
        return explode('/', $software)[0];
    }

    /**
     * Detect if running inside a Docker container
     */
    private function isDocker(): bool {
        if (file_exists('/.dockerenv')) {
            return true;
        }
        if (file_exists('/proc/1/cgroup')) {
            $cgroup = @file_get_contents('/proc/1/cgroup');
            if ($cgroup !== false && str_contains($cgroup, 'docker')) {
                return true;
            }
        }
        return false;
    }

    /**
     * Get telemetry status for admin panel
     */
    public function getStatus(): array {
        return [
            'enabled' => $this->isEnabled(),
            'lastReport' => $this->getLastReportTime(),
            'telemetryUrl' => $this->getTelemetryUrl()
        ];
    }
}
