<?php
declare(strict_types=1);

namespace OCA\IntraVox\Service;

use OCP\Files\Folder;
use OCP\Http\Client\IClientService;
use OCP\IConfig;
use Psr\Log\LoggerInterface;

/**
 * Service for downloading and importing demo data from external source
 */
class DemoDataService {
    private const APP_ID = 'intravox';

    // Demo data source URL - will be changed to GitHub when released
    private const DEMO_DATA_BASE_URL = 'https://raw.githubusercontent.com/nextcloud/intravox/main/demo-data';

    private const SUPPORTED_LANGUAGES = ['nl', 'en'];
    private const DEFAULT_LANGUAGE = 'nl';

    private SetupService $setupService;
    private IClientService $clientService;
    private IConfig $config;
    private LoggerInterface $logger;

    public function __construct(
        SetupService $setupService,
        IClientService $clientService,
        IConfig $config,
        LoggerInterface $logger
    ) {
        $this->setupService = $setupService;
        $this->clientService = $clientService;
        $this->config = $config;
        $this->logger = $logger;
    }

    /**
     * Check if demo data has already been imported
     */
    public function isDemoDataImported(): bool {
        return $this->config->getAppValue(self::APP_ID, 'demo_data_imported', 'false') === 'true';
    }

    /**
     * Mark demo data as imported
     */
    private function markDemoDataImported(): void {
        $this->config->setAppValue(self::APP_ID, 'demo_data_imported', 'true');
    }

    /**
     * Get available languages for demo data
     */
    public function getAvailableLanguages(): array {
        return self::SUPPORTED_LANGUAGES;
    }

    /**
     * Get demo data status for API response
     */
    public function getStatus(): array {
        return [
            'imported' => $this->isDemoDataImported(),
            'available_languages' => self::SUPPORTED_LANGUAGES,
            'default_language' => self::DEFAULT_LANGUAGE,
        ];
    }

    /**
     * Import demo data for a specific language
     *
     * @param string $language Language code (nl, en)
     * @return array Result with success status and message
     */
    public function importDemoData(string $language = 'nl'): array {
        if (!in_array($language, self::SUPPORTED_LANGUAGES)) {
            return [
                'success' => false,
                'message' => "Unsupported language: {$language}. Supported: " . implode(', ', self::SUPPORTED_LANGUAGES),
            ];
        }

        try {
            $this->logger->info("[DemoData] Starting demo data import for language: {$language}");

            // Get IntraVox groupfolder
            $sharedFolder = $this->setupService->getSharedFolder();

            // Get or create language folder
            if (!$sharedFolder->nodeExists($language)) {
                $languageFolder = $sharedFolder->newFolder($language);
                $this->logger->info("[DemoData] Created language folder: {$language}");
            } else {
                $languageFolder = $sharedFolder->get($language);
            }

            // Download and import manifest
            $manifest = $this->downloadManifest($language);
            if ($manifest === null) {
                return [
                    'success' => false,
                    'message' => 'Failed to download demo data manifest',
                ];
            }

            $imported = 0;
            $errors = 0;

            // Import home.json
            if ($this->downloadAndSaveFile($language, 'home.json', $languageFolder)) {
                $imported++;
            } else {
                $errors++;
            }

            // Import navigation.json
            if ($this->downloadAndSaveFile($language, 'navigation.json', $languageFolder)) {
                $imported++;
            } else {
                $errors++;
            }

            // Import footer.json
            if ($this->downloadAndSaveFile($language, 'footer.json', $languageFolder)) {
                $imported++;
            } else {
                $errors++;
            }

            // Import images folder
            $imagesImported = $this->importImagesFolder($language, $languageFolder);
            $imported += $imagesImported;

            // Import page folders from manifest
            if (isset($manifest['pages']) && is_array($manifest['pages'])) {
                foreach ($manifest['pages'] as $pagePath) {
                    $result = $this->importPageFolder($language, $pagePath, $languageFolder);
                    $imported += $result['imported'];
                    $errors += $result['errors'];
                }
            }

            // Mark as imported
            $this->markDemoDataImported();

            // Trigger groupfolder scan
            $this->scanGroupfolder();

            $this->logger->info("[DemoData] Import complete. Imported: {$imported}, Errors: {$errors}");

            return [
                'success' => $errors === 0,
                'message' => "Demo data imported successfully. {$imported} items imported" . ($errors > 0 ? ", {$errors} errors" : ''),
                'imported' => $imported,
                'errors' => $errors,
            ];

        } catch (\Exception $e) {
            $this->logger->error("[DemoData] Import failed: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to import demo data: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Download manifest file for a language
     */
    private function downloadManifest(string $language): ?array {
        $url = self::DEMO_DATA_BASE_URL . "/{$language}/manifest.json";
        $content = $this->downloadFile($url);

        if ($content === null) {
            // If no manifest exists, create a default one based on typical structure
            $this->logger->info("[DemoData] No manifest found, using default structure");
            return [
                'pages' => [
                    'afdeling',
                    'afdeling/hr',
                    'afdeling/it',
                    'afdeling/marketing',
                    'afdeling/sales',
                    'afdeling/finance',
                    'contact',
                    'documentatie',
                    'documentatie/handleidingen',
                    'documentatie/procedures',
                    'documentatie/templates',
                    'documentatie/faq',
                    'documentatie/api',
                    'functies',
                    'functies/dashboard',
                    'functies/widgets',
                    'functies/navigatie',
                    'functies/zoeken',
                    'klanten',
                    'nieuws',
                    'nieuws/updates',
                    'nieuws/releases',
                    'nieuws/tips',
                    'nextcloud',
                    'over-intravox',
                    'prijzen',
                ],
            ];
        }

        return json_decode($content, true);
    }

    /**
     * Download a file from the demo data source
     */
    private function downloadFile(string $url): ?string {
        try {
            $client = $this->clientService->newClient();
            $response = $client->get($url, [
                'timeout' => 30,
                'connect_timeout' => 10,
            ]);

            if ($response->getStatusCode() === 200) {
                return $response->getBody();
            }

            $this->logger->warning("[DemoData] Download failed for {$url}: HTTP " . $response->getStatusCode());
            return null;

        } catch (\Exception $e) {
            $this->logger->warning("[DemoData] Download failed for {$url}: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Download and save a file to the target folder
     */
    private function downloadAndSaveFile(string $language, string $filename, Folder $targetFolder): bool {
        $url = self::DEMO_DATA_BASE_URL . "/{$language}/{$filename}";
        $content = $this->downloadFile($url);

        if ($content === null) {
            return false;
        }

        try {
            if ($targetFolder->nodeExists($filename)) {
                $file = $targetFolder->get($filename);
                $file->putContent($content);
                $this->logger->info("[DemoData] Updated: {$filename}");
            } else {
                $targetFolder->newFile($filename, $content);
                $this->logger->info("[DemoData] Created: {$filename}");
            }
            return true;
        } catch (\Exception $e) {
            $this->logger->error("[DemoData] Failed to save {$filename}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Import images folder for the language root
     */
    private function importImagesFolder(string $language, Folder $languageFolder): int {
        $imported = 0;

        try {
            // Create images folder if not exists
            if (!$languageFolder->nodeExists('images')) {
                $imagesFolder = $languageFolder->newFolder('images');
            } else {
                $imagesFolder = $languageFolder->get('images');
            }

            // Download image list from manifest or use known images
            $knownImages = $this->getKnownImages($language);

            foreach ($knownImages as $imageName) {
                $url = self::DEMO_DATA_BASE_URL . "/{$language}/images/{$imageName}";
                $content = $this->downloadFile($url);

                if ($content !== null) {
                    if ($imagesFolder->nodeExists($imageName)) {
                        $file = $imagesFolder->get($imageName);
                        $file->putContent($content);
                    } else {
                        $imagesFolder->newFile($imageName, $content);
                    }
                    $imported++;
                    $this->logger->info("[DemoData] Imported image: images/{$imageName}");
                }
            }

        } catch (\Exception $e) {
            $this->logger->error("[DemoData] Failed to import images: " . $e->getMessage());
        }

        return $imported;
    }

    /**
     * Get list of known images for a language from manifest
     */
    private function getKnownImages(string $language): array {
        $manifest = $this->downloadManifest($language);
        if ($manifest !== null && isset($manifest['images'])) {
            return $manifest['images'];
        }

        // Fallback: typical images used in demo content
        return [
            'hero-banner.jpg',
            'team-collaboration.jpg',
            'office-workspace.jpg',
            'meeting-room.jpg',
            'innovation.jpg',
            'customer-support.jpg',
            'analytics-dashboard.jpg',
            'security.jpg',
        ];
    }

    /**
     * Import a page folder recursively
     */
    private function importPageFolder(string $language, string $pagePath, Folder $parentFolder): array {
        $imported = 0;
        $errors = 0;

        try {
            // Get or create page folder
            $pathParts = explode('/', $pagePath);
            $pageId = end($pathParts);
            $currentFolder = $parentFolder;

            // Navigate/create folder structure
            foreach ($pathParts as $part) {
                if (!$currentFolder->nodeExists($part)) {
                    $currentFolder = $currentFolder->newFolder($part);
                } else {
                    $currentFolder = $currentFolder->get($part);
                }
            }

            // Create images subfolder
            if (!$currentFolder->nodeExists('images')) {
                $currentFolder->newFolder('images');
            }

            // Download and save page JSON
            $jsonUrl = self::DEMO_DATA_BASE_URL . "/{$language}/{$pagePath}/{$pageId}.json";
            $jsonContent = $this->downloadFile($jsonUrl);

            if ($jsonContent !== null) {
                $jsonFilename = "{$pageId}.json";
                if ($currentFolder->nodeExists($jsonFilename)) {
                    $file = $currentFolder->get($jsonFilename);
                    $file->putContent($jsonContent);
                } else {
                    $currentFolder->newFile($jsonFilename, $jsonContent);
                }
                $imported++;
                $this->logger->info("[DemoData] Imported page: {$pagePath}/{$jsonFilename}");

                // Download page-specific images
                $pageData = json_decode($jsonContent, true);
                if ($pageData && isset($pageData['layout']['rows'])) {
                    $imageNames = $this->extractImageNames($pageData);
                    $imagesFolder = $currentFolder->get('images');

                    foreach ($imageNames as $imageName) {
                        $imageUrl = self::DEMO_DATA_BASE_URL . "/{$language}/{$pagePath}/images/{$imageName}";
                        $imageContent = $this->downloadFile($imageUrl);

                        if ($imageContent !== null) {
                            if ($imagesFolder->nodeExists($imageName)) {
                                $imgFile = $imagesFolder->get($imageName);
                                $imgFile->putContent($imageContent);
                            } else {
                                $imagesFolder->newFile($imageName, $imageContent);
                            }
                            $this->logger->info("[DemoData] Imported image: {$pagePath}/images/{$imageName}");
                        }
                    }
                }
            } else {
                $errors++;
                $this->logger->warning("[DemoData] Failed to download page: {$pagePath}");
            }

        } catch (\Exception $e) {
            $errors++;
            $this->logger->error("[DemoData] Failed to import page {$pagePath}: " . $e->getMessage());
        }

        return ['imported' => $imported, 'errors' => $errors];
    }

    /**
     * Extract image names from page data
     */
    private function extractImageNames(array $pageData): array {
        $images = [];

        if (!isset($pageData['layout']['rows'])) {
            return $images;
        }

        foreach ($pageData['layout']['rows'] as $row) {
            if (!isset($row['widgets'])) {
                continue;
            }

            foreach ($row['widgets'] as $widget) {
                if ($widget['type'] === 'image' && !empty($widget['src'])) {
                    // Extract filename from src path
                    $src = $widget['src'];
                    if (strpos($src, 'images/') !== false) {
                        $parts = explode('images/', $src);
                        if (count($parts) > 1) {
                            $images[] = end($parts);
                        }
                    } else {
                        $images[] = basename($src);
                    }
                }
            }
        }

        return array_unique($images);
    }

    /**
     * Import demo data from bundled files in the app package
     *
     * @param string $language Language code (nl, en)
     * @return array Result with success status and message
     */
    public function importBundledDemoData(string $language = 'nl'): array {
        if (!in_array($language, self::SUPPORTED_LANGUAGES)) {
            return [
                'success' => false,
                'message' => "Unsupported language: {$language}. Supported: " . implode(', ', self::SUPPORTED_LANGUAGES),
            ];
        }

        try {
            $this->logger->info("[DemoData] Starting bundled demo data import for language: {$language}");

            // Get path to bundled demo data
            $appPath = \OC::$SERVERROOT . '/apps/intravox';
            $demoDataPath = $appPath . '/demo-data/' . $language;

            if (!is_dir($demoDataPath)) {
                $this->logger->error("[DemoData] Demo data path not found: {$demoDataPath}");
                return [
                    'success' => false,
                    'message' => "Demo data not found for language: {$language}",
                ];
            }

            // Get IntraVox groupfolder
            $sharedFolder = $this->setupService->getSharedFolder();

            // Get or create language folder
            if (!$sharedFolder->nodeExists($language)) {
                $languageFolder = $sharedFolder->newFolder($language);
                $this->logger->info("[DemoData] Created language folder: {$language}");
            } else {
                $languageFolder = $sharedFolder->get($language);
            }

            // Import all files recursively
            $result = $this->importDirectoryRecursive($demoDataPath, $languageFolder);

            // Mark as imported
            $this->markDemoDataImported();

            // Trigger groupfolder scan
            $this->scanGroupfolder();

            $this->logger->info("[DemoData] Bundled import complete. Imported: {$result['imported']}, Errors: {$result['errors']}");

            return [
                'success' => $result['errors'] === 0,
                'message' => "Demo data imported successfully. {$result['imported']} items imported" . ($result['errors'] > 0 ? ", {$result['errors']} errors" : ''),
                'imported' => $result['imported'],
                'errors' => $result['errors'],
            ];

        } catch (\Exception $e) {
            $this->logger->error("[DemoData] Bundled import failed: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to import demo data: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Import a directory recursively from local filesystem to Nextcloud folder
     */
    private function importDirectoryRecursive(string $sourcePath, Folder $targetFolder): array {
        $imported = 0;
        $errors = 0;

        $items = scandir($sourcePath);
        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            $itemPath = $sourcePath . '/' . $item;

            if (is_dir($itemPath)) {
                // Create subfolder and recurse
                try {
                    if (!$targetFolder->nodeExists($item)) {
                        $subFolder = $targetFolder->newFolder($item);
                    } else {
                        $subFolder = $targetFolder->get($item);
                    }
                    $subResult = $this->importDirectoryRecursive($itemPath, $subFolder);
                    $imported += $subResult['imported'];
                    $errors += $subResult['errors'];
                } catch (\Exception $e) {
                    $this->logger->error("[DemoData] Failed to create folder {$item}: " . $e->getMessage());
                    $errors++;
                }
            } else {
                // Import file
                try {
                    $content = file_get_contents($itemPath);
                    if ($content === false) {
                        $errors++;
                        continue;
                    }

                    if ($targetFolder->nodeExists($item)) {
                        $file = $targetFolder->get($item);
                        $file->putContent($content);
                    } else {
                        $targetFolder->newFile($item, $content);
                    }
                    $imported++;
                    $this->logger->debug("[DemoData] Imported: {$item}");
                } catch (\Exception $e) {
                    $this->logger->error("[DemoData] Failed to import {$item}: " . $e->getMessage());
                    $errors++;
                }
            }
        }

        return ['imported' => $imported, 'errors' => $errors];
    }

    /**
     * Check if bundled demo data is available
     */
    public function hasBundledDemoData(string $language = 'nl'): bool {
        $appPath = \OC::$SERVERROOT . '/apps/intravox';
        $demoDataPath = $appPath . '/demo-data/' . $language;
        return is_dir($demoDataPath) && is_file($demoDataPath . '/home.json');
    }

    /**
     * Trigger groupfolder scan to update file cache
     */
    private function scanGroupfolder(): void {
        try {
            $folderId = $this->setupService->getGroupFolderId();
            $ncRoot = \OC::$SERVERROOT;

            $command = sprintf(
                'php %s/occ groupfolders:scan %d > /dev/null 2>&1 &',
                escapeshellarg($ncRoot),
                $folderId
            );

            $descriptorspec = [
                0 => ['pipe', 'r'],
                1 => ['pipe', 'w'],
                2 => ['pipe', 'w'],
            ];

            $process = proc_open($command, $descriptorspec, $pipes);

            if (is_resource($process)) {
                fclose($pipes[0]);
                fclose($pipes[1]);
                fclose($pipes[2]);
                proc_close($process);
                $this->logger->info('[DemoData] Triggered groupfolder scan');
            }
        } catch (\Exception $e) {
            $this->logger->warning('[DemoData] Failed to trigger scan: ' . $e->getMessage());
        }
    }
}
