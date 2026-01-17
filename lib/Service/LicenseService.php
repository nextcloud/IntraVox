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
     * Get free tier limit per language
     */
    public function getFreeLimit(): int {
        return self::FREE_LIMIT;
    }

    /**
     * Get instance info for telemetry
     */
    public function getInstanceInfo(): array {
        // Get Nextcloud version
        $nextcloudVersion = \OC::$server->getConfig()->getSystemValue('version', 'unknown');

        // Get IntraVox version from info.xml
        $intravoxVersion = $this->config->getAppValue(Application::APP_ID, 'installed_version', '0.0.0');

        // Get instance URL
        $instanceUrl = $this->config->getSystemValue('overwrite.cli.url', '');
        if (empty($instanceUrl)) {
            $instanceUrl = \OC::$server->getURLGenerator()->getAbsoluteURL('/');
        }

        return [
            'instance_url' => $instanceUrl,
            'intravox_version' => $intravoxVersion,
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
        return [
            'pageCounts' => $this->getPageCountsPerLanguage(),
            'totalPages' => $this->getTotalPageCount(),
            'freeLimit' => self::FREE_LIMIT,
            'supportedLanguages' => self::SUPPORTED_LANGUAGES,
        ];
    }
}
