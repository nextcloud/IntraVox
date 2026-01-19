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
            'phpVersion' => PHP_VERSION
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
