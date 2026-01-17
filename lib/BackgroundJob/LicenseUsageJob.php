<?php
declare(strict_types=1);

namespace OCA\IntraVox\BackgroundJob;

use OCA\IntraVox\AppInfo\Application;
use OCA\IntraVox\Service\LicenseService;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\BackgroundJob\TimedJob;
use OCP\IConfig;
use Psr\Log\LoggerInterface;

/**
 * Background job that periodically sends license usage data to the license server
 * Runs every 24 hours by default, with random jitter to spread load across installations
 */
class LicenseUsageJob extends TimedJob {
    private const DEFAULT_INTERVAL_HOURS = 24;
    private const JITTER_MAX_MINUTES = 60; // Random delay 0-60 minutes

    private LicenseService $licenseService;
    private IConfig $config;
    private LoggerInterface $logger;

    public function __construct(
        ITimeFactory $time,
        LicenseService $licenseService,
        IConfig $config,
        LoggerInterface $logger
    ) {
        parent::__construct($time);

        $this->licenseService = $licenseService;
        $this->config = $config;
        $this->logger = $logger;

        // Get configured interval (in hours), default to 24 hours
        $intervalHours = (int)$this->config->getAppValue(
            Application::APP_ID,
            'license_sync_interval_hours',
            (string)self::DEFAULT_INTERVAL_HOURS
        );

        // Set interval in seconds (minimum 1 hour)
        $intervalHours = max(1, $intervalHours);

        // Add instance-specific jitter to spread load across installations
        // Use a stable jitter based on instance ID so it's consistent across restarts
        $jitterMinutes = $this->getStableJitter();

        $this->setInterval(($intervalHours * 60 * 60) + ($jitterMinutes * 60));
    }

    /**
     * Get a stable jitter value unique to this installation
     * Uses a hash of the instance ID to generate consistent random delay
     */
    private function getStableJitter(): int {
        $instanceId = $this->config->getSystemValue('instanceid', '');
        if (empty($instanceId)) {
            // Fallback to random if no instance ID
            return random_int(0, self::JITTER_MAX_MINUTES);
        }

        // Generate a stable number between 0 and JITTER_MAX_MINUTES based on instance ID
        $hash = crc32($instanceId . 'license_sync_jitter');
        return abs($hash) % (self::JITTER_MAX_MINUTES + 1);
    }

    /**
     * Run the background job
     * @param mixed $argument Not used
     */
    protected function run($argument): void {
        $this->logger->info('LicenseUsageJob: Starting license usage sync');

        // Check if we have a license key configured
        $licenseKey = $this->licenseService->getLicenseKey();
        if (empty($licenseKey)) {
            $this->logger->info('LicenseUsageJob: No license key configured, skipping');
            return;
        }

        try {
            // First validate the license
            $validation = $this->licenseService->validateLicense();

            if (!$validation['valid']) {
                $this->logger->warning('LicenseUsageJob: License validation failed', [
                    'reason' => $validation['reason'] ?? 'Unknown'
                ]);
                // Still try to update usage so the server knows about this instance
            }

            // Update usage statistics
            $result = $this->licenseService->updateUsage();

            if ($result['success']) {
                $this->logger->info('LicenseUsageJob: Usage sync completed successfully', [
                    'limits' => $result['limits'] ?? null
                ]);

                // Store last sync time
                $this->config->setAppValue(
                    Application::APP_ID,
                    'license_last_sync',
                    (string)time()
                );
            } else {
                $this->logger->warning('LicenseUsageJob: Usage sync failed', [
                    'reason' => $result['reason'] ?? 'Unknown'
                ]);
            }
        } catch (\Exception $e) {
            $this->logger->error('LicenseUsageJob: Exception during sync', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }
}
