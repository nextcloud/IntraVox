<?php
declare(strict_types=1);

namespace OCA\IntraVox\Service;

use OCP\Comments\ICommentsManager;
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

    private const SUPPORTED_LANGUAGES = ['nl', 'en', 'de', 'fr'];
    private const DEFAULT_LANGUAGE = 'nl';

    // Language metadata for UI
    private const LANGUAGE_META = [
        'nl' => ['name' => 'Nederlands', 'flag' => '🇳🇱', 'full' => true],
        'en' => ['name' => 'English', 'flag' => '🇬🇧', 'full' => true],
        'de' => ['name' => 'Deutsch', 'flag' => '🇩🇪', 'full' => false],
        'fr' => ['name' => 'Français', 'flag' => '🇫🇷', 'full' => false],
    ];

    private SetupService $setupService;
    private IClientService $clientService;
    private IConfig $config;
    private LoggerInterface $logger;
    private ICommentsManager $commentsManager;

    public function __construct(
        SetupService $setupService,
        IClientService $clientService,
        IConfig $config,
        LoggerInterface $logger,
        ICommentsManager $commentsManager
    ) {
        $this->setupService = $setupService;
        $this->clientService = $clientService;
        $this->config = $config;
        $this->logger = $logger;
        $this->commentsManager = $commentsManager;
    }

    /**
     * Get user-friendly error message for setup failures
     */
    private function getSetupErrorMessage(string $errorKey): string {
        $messages = [
            'groupfolders_app_not_enabled' => 'Setup failed: The Team Folders app is not installed or enabled. Please install and enable the Team Folders app in Nextcloud first.',
            'groupfolder_creation_failed' => 'Setup failed: Could not create the IntraVox Team Folder. Please check if the Team Folders app is working correctly.',
            'groupfolder_access_failed' => 'Setup failed: Could not access the IntraVox Team Folder. Please check file system permissions.',
            'setup_exception' => 'Setup failed: An unexpected error occurred while setting up the IntraVox Team Folder. Please check the Nextcloud logs for details.',
        ];

        return $messages[$errorKey] ?? 'Setup failed: Could not create IntraVox Team Folder';
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
        $setupComplete = $this->setupService->isSetupComplete();

        return [
            'imported' => $this->isDemoDataImported(),
            'available_languages' => self::SUPPORTED_LANGUAGES,
            'default_language' => self::DEFAULT_LANGUAGE,
            'languages' => $this->getLanguagesWithStatus(),
            'setupComplete' => $setupComplete,
        ];
    }

    /**
     * Get detailed status for each available language
     */
    public function getLanguagesWithStatus(): array {
        $languages = [];
        $sharedFolder = null;
        $setupComplete = false;

        try {
            $sharedFolder = $this->setupService->getSharedFolder();
            $setupComplete = true;
        } catch (\Exception $e) {
            $this->logger->info('[DemoData] GroupFolder not yet available: ' . $e->getMessage());
        }

        foreach (self::SUPPORTED_LANGUAGES as $lang) {
            $meta = self::LANGUAGE_META[$lang] ?? ['name' => $lang, 'flag' => '', 'full' => false];
            $exists = false;
            $hasContent = false;
            $hasBundled = $this->hasBundledDemoData($lang);

            if ($setupComplete && $sharedFolder !== null) {
                try {
                    $exists = $sharedFolder->nodeExists($lang);
                    if ($exists) {
                        $langFolder = $sharedFolder->get($lang);
                        // Check if there's actual content (home.json exists)
                        $hasContent = $langFolder->nodeExists('home.json');
                    }
                } catch (\Exception $e) {
                    $this->logger->warning('[DemoData] Error checking language folder: ' . $e->getMessage());
                }
            }

            $languages[] = [
                'code' => $lang,
                'name' => $meta['name'],
                'flag' => $meta['flag'],
                'fullContent' => $meta['full'],
                'folderExists' => $exists,
                'hasContent' => $hasContent,
                'hasBundledData' => $hasBundled,
                // canInstall is true if bundled data exists - setup will be done automatically during import
                'canInstall' => $hasBundled,
                'status' => $this->getLanguageStatus($exists, $hasContent, $hasBundled, $setupComplete),
            ];
        }

        return $languages;
    }

    /**
     * Determine status label for a language
     */
    private function getLanguageStatus(bool $exists, bool $hasContent, bool $hasBundled, bool $setupComplete = true): string {
        if (!$setupComplete) {
            return 'setup_required';
        }
        if (!$hasBundled) {
            return 'unavailable';
        }
        if (!$exists) {
            return 'not_installed';
        }
        if ($hasContent) {
            return 'installed';
        }
        return 'empty';
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

            // Get or create language folder (handles case where folder exists in cache but not on disk)
            $languageFolder = $this->ensureFolderExists($sharedFolder, $language);
            $this->logger->info("[DemoData] Using language folder: {$language}");

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
     * Import media folder for the language root
     */
    private function importImagesFolder(string $language, Folder $languageFolder): int {
        $imported = 0;

        try {
            // Create _media folder if not exists
            if (!$languageFolder->nodeExists('_media')) {
                $mediaFolder = $languageFolder->newFolder('_media');
            } else {
                $mediaFolder = $languageFolder->get('_media');
            }

            // Download image list from manifest or use known images
            $knownImages = $this->getKnownImages($language);

            foreach ($knownImages as $imageName) {
                $url = self::DEMO_DATA_BASE_URL . "/{$language}/_media/{$imageName}";
                $content = $this->downloadFile($url);

                if ($content !== null) {
                    if ($mediaFolder->nodeExists($imageName)) {
                        $file = $mediaFolder->get($imageName);
                        $file->putContent($content);
                    } else {
                        $mediaFolder->newFile($imageName, $content);
                    }
                    $imported++;
                    $this->logger->info("[DemoData] Imported media: _media/{$imageName}");
                }
            }

        } catch (\Exception $e) {
            $this->logger->error("[DemoData] Failed to import media: " . $e->getMessage());
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

            // Create _media subfolder
            if (!$currentFolder->nodeExists('_media')) {
                $currentFolder->newFolder('_media');
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

                // Download page-specific media
                $pageData = json_decode($jsonContent, true);
                if ($pageData && isset($pageData['layout']['rows'])) {
                    $imageNames = $this->extractImageNames($pageData);
                    $mediaFolder = $currentFolder->get('_media');

                    foreach ($imageNames as $imageName) {
                        $imageUrl = self::DEMO_DATA_BASE_URL . "/{$language}/{$pagePath}/_media/{$imageName}";
                        $imageContent = $this->downloadFile($imageUrl);

                        if ($imageContent !== null) {
                            if ($mediaFolder->nodeExists($imageName)) {
                                $imgFile = $mediaFolder->get($imageName);
                                $imgFile->putContent($imageContent);
                            } else {
                                $mediaFolder->newFile($imageName, $imageContent);
                            }
                            $this->logger->info("[DemoData] Imported media: {$pagePath}/_media/{$imageName}");
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
     * @param string $mode Import mode: 'overwrite' (default) or 'skip_existing'
     * @return array Result with success status and message
     */
    public function importBundledDemoData(string $language = 'nl', string $mode = 'overwrite'): array {
        if (!in_array($language, self::SUPPORTED_LANGUAGES)) {
            return [
                'success' => false,
                'message' => "Unsupported language: {$language}. Supported: " . implode(', ', self::SUPPORTED_LANGUAGES),
            ];
        }

        try {
            $this->logger->info("[DemoData] Starting bundled demo data import for language: {$language}, mode: {$mode}");

            // Get path to bundled demo data
            $demoDataPath = $this->getBundledDemoDataPath($language);

            if ($demoDataPath === null) {
                $this->logger->error("[DemoData] Demo data path not found for language: {$language}");
                return [
                    'success' => false,
                    'message' => "Demo data not found for language: {$language}",
                ];
            }

            $this->logger->info("[DemoData] Using demo data from: {$demoDataPath}");

            // Ensure setup is complete before importing
            if (!$this->setupService->isSetupComplete()) {
                $this->logger->info("[DemoData] Setup not complete, running setup first...");
                $setupResult = $this->setupService->setupSharedFolder();
                if (!$setupResult['success']) {
                    $errorMessage = $this->getSetupErrorMessage($setupResult['error'] ?? 'unknown');
                    return [
                        'success' => false,
                        'message' => $errorMessage,
                    ];
                }
                $this->logger->info("[DemoData] Setup completed successfully");
            }

            // Get IntraVox groupfolder
            $sharedFolder = $this->setupService->getSharedFolder();

            // For overwrite mode, delete the language folder first to clear any cached-but-nonexistent entries
            if ($mode === 'overwrite') {
                $this->deleteLanguageFolderIfExists($sharedFolder, $language);
            }

            // Get or create language folder (handles case where folder exists in cache but not on disk)
            $languageFolder = $this->ensureFolderExists($sharedFolder, $language);
            $this->logger->info("[DemoData] Using language folder: {$language}");

            // Import all files recursively
            $skipExisting = ($mode === 'skip_existing');
            $result = $this->importDirectoryRecursive($demoDataPath, $languageFolder, $skipExisting);

            // Mark as imported
            $this->markDemoDataImported();

            // Trigger groupfolder scan
            $this->scanGroupfolder();

            $skipped = $result['skipped'] ?? 0;
            $this->logger->info("[DemoData] Bundled import complete. Imported: {$result['imported']}, Skipped: {$skipped}, Errors: {$result['errors']}");

            $message = "Demo data imported successfully. {$result['imported']} items imported";
            if ($skipped > 0) {
                $message .= ", {$skipped} existing files skipped";
            }
            if ($result['errors'] > 0) {
                $message .= ", {$result['errors']} errors";
            }

            return [
                'success' => $result['errors'] === 0,
                'message' => $message,
                'imported' => $result['imported'],
                'skipped' => $skipped,
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
     *
     * @param string $sourcePath Path to source directory
     * @param Folder $targetFolder Target Nextcloud folder
     * @param bool $skipExisting If true, skip files that already exist
     */
    private function importDirectoryRecursive(string $sourcePath, Folder $targetFolder, bool $skipExisting = false): array {
        $imported = 0;
        $skipped = 0;
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
                    $subFolder = $this->ensureFolderExists($targetFolder, $item);
                    $subResult = $this->importDirectoryRecursive($itemPath, $subFolder, $skipExisting);
                    $imported += $subResult['imported'];
                    $skipped += $subResult['skipped'] ?? 0;
                    $errors += $subResult['errors'];
                } catch (\Exception $e) {
                    $this->logger->error("[DemoData] Failed to create folder {$item}: " . $e->getMessage());
                    $errors++;
                }
            } else {
                // Import file
                try {
                    // Check if file exists and we should skip it
                    if ($skipExisting && $targetFolder->nodeExists($item)) {
                        $skipped++;
                        $this->logger->debug("[DemoData] Skipped existing: {$item}");
                        continue;
                    }

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

        return ['imported' => $imported, 'skipped' => $skipped, 'errors' => $errors];
    }

    /**
     * Delete a language folder if it exists, to clear any cached-but-nonexistent entries.
     * This is needed when the file cache contains entries for folders that don't exist on disk.
     */
    private function deleteLanguageFolderIfExists(Folder $parent, string $language): void {
        try {
            if ($parent->nodeExists($language)) {
                $this->logger->info("[DemoData] Deleting existing language folder: {$language}");
                $node = $parent->get($language);
                $node->delete();
                $this->logger->info("[DemoData] Language folder deleted successfully");
            }
        } catch (\Exception $e) {
            $this->logger->warning("[DemoData] Failed to delete language folder: " . $e->getMessage());
            // Continue anyway - the folder will be recreated
        }
    }

    /**
     * Ensure a folder exists, creating it if necessary.
     * This handles the case where the folder exists in file cache but not on disk.
     */
    private function ensureFolderExists(Folder $parent, string $name): Folder {
        try {
            // First, verify the parent folder actually exists on disk
            try {
                $parent->getDirectoryListing();
            } catch (\Exception $e) {
                // Parent doesn't exist on disk - this is a deeper problem
                // Try to recreate by getting internal path
                $this->logger->warning("[DemoData] Parent folder doesn't exist on disk, this shouldn't happen");
            }

            if ($parent->nodeExists($name)) {
                $node = $parent->get($name);
                if ($node instanceof Folder) {
                    // Try to access the folder to verify it actually exists on disk
                    try {
                        // getDirectoryListing will fail if folder doesn't exist on disk
                        $node->getDirectoryListing();
                        return $node;
                    } catch (\Exception $e) {
                        // Folder exists in cache but not on disk, delete cache entry and recreate
                        $this->logger->info("[DemoData] Folder {$name} exists in cache but not on disk, recreating");
                        try {
                            $node->delete();
                        } catch (\Exception $deleteE) {
                            // Ignore delete errors, just try to create
                            $this->logger->debug("[DemoData] Could not delete cached folder: " . $deleteE->getMessage());
                        }
                    }
                } else {
                    // Node exists but is not a folder - delete it and create folder
                    $this->logger->warning("[DemoData] Node {$name} exists but is not a folder, deleting");
                    try {
                        $node->delete();
                    } catch (\Exception $deleteE) {
                        // Ignore delete errors
                    }
                }
            }
            // Create the folder
            return $parent->newFolder($name);
        } catch (\OCP\Files\NotPermittedException $e) {
            // Folder might already exist, try to get it
            if ($parent->nodeExists($name)) {
                $node = $parent->get($name);
                if ($node instanceof Folder) {
                    return $node;
                }
            }
            throw $e;
        }
    }

    /**
     * Check if bundled demo data is available
     */
    public function hasBundledDemoData(string $language = 'nl'): bool {
        $demoDataPath = $this->getBundledDemoDataPath($language);
        return $demoDataPath !== null && is_file($demoDataPath . '/home.json');
    }

    /**
     * Get the path to bundled demo data for a language
     * Uses Nextcloud's app manager to find the actual app installation path
     */
    private function getBundledDemoDataPath(string $language): ?string {
        // First, try to get the app path from Nextcloud's app manager (most reliable)
        try {
            $appManager = \OC::$server->getAppManager();
            $appPath = $appManager->getAppPath('intravox');
            if ($appPath !== null) {
                $demoDataPath = $appPath . '/demo-data/' . $language;
                if (is_dir($demoDataPath)) {
                    $this->logger->debug("[DemoData] Found demo data at app path: {$demoDataPath}");
                    return $demoDataPath;
                }
            }
        } catch (\Exception $e) {
            $this->logger->warning("[DemoData] Could not get app path from app manager: " . $e->getMessage());
        }

        // Fallback: check common paths (for backwards compatibility)
        $possiblePaths = [
            \OC::$SERVERROOT . '/apps/intravox/demo-data/' . $language,
            \OC::$SERVERROOT . '/custom_apps/intravox/demo-data/' . $language,
        ];

        foreach ($possiblePaths as $path) {
            if (is_dir($path)) {
                $this->logger->debug("[DemoData] Found demo data at fallback path: {$path}");
                return $path;
            }
        }

        $this->logger->warning("[DemoData] Demo data not found for language: {$language}");
        return null;
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

    /**
     * Perform a clean start for a language - deletes all content and creates minimal fresh content
     *
     * @param string $language Language code (nl, en, de, fr)
     * @return array Result with success status and message
     */
    public function performCleanStart(string $language): array {
        if (!in_array($language, self::SUPPORTED_LANGUAGES)) {
            return [
                'success' => false,
                'message' => "Unsupported language: {$language}. Supported: " . implode(', ', self::SUPPORTED_LANGUAGES),
            ];
        }

        try {
            $this->logger->info("[CleanStart] Starting clean start for language: {$language}");

            // Ensure setup is complete before cleaning
            if (!$this->setupService->isSetupComplete()) {
                $this->logger->info("[CleanStart] Setup not complete, running setup first...");
                $setupResult = $this->setupService->setupSharedFolder();
                if (!$setupResult['success']) {
                    $errorMessage = $this->getSetupErrorMessage($setupResult['error'] ?? 'unknown');
                    return [
                        'success' => false,
                        'message' => $errorMessage,
                    ];
                }
                $this->logger->info("[CleanStart] Setup completed successfully");
            }

            // Get IntraVox groupfolder
            $sharedFolder = $this->setupService->getSharedFolder();

            // Collect all uniqueIds before deletion (for comment cleanup)
            $uniqueIds = [];
            if ($sharedFolder->nodeExists($language)) {
                $langFolder = $sharedFolder->get($language);
                if ($langFolder instanceof Folder) {
                    $uniqueIds = $this->collectAllPageUniqueIds($langFolder);
                    $this->logger->info("[CleanStart] Found " . count($uniqueIds) . " pages to clean up comments for");
                }
            }

            // Delete all comments and reactions for collected uniqueIds
            $commentsDeleted = 0;
            foreach ($uniqueIds as $uniqueId) {
                try {
                    $this->commentsManager->deleteCommentsAtObject('intravox_page', $uniqueId);
                    $commentsDeleted++;
                    $this->logger->debug("[CleanStart] Deleted comments for page: {$uniqueId}");
                } catch (\Exception $e) {
                    $this->logger->warning("[CleanStart] Failed to delete comments for {$uniqueId}: " . $e->getMessage());
                }
            }
            $this->logger->info("[CleanStart] Deleted comments for {$commentsDeleted} pages");

            // Delete the language folder if it exists
            $this->deleteLanguageFolderIfExists($sharedFolder, $language);

            // Create fresh minimal content
            $languageFolder = $sharedFolder->newFolder($language);
            $this->logger->info("[CleanStart] Created fresh language folder: {$language}");

            // Generate new unique ID for home page
            $homeUniqueId = 'page-' . $this->generateUUID();
            $this->logger->info("[CleanStart] Generated new uniqueId for home: {$homeUniqueId}");

            // Create home.json with fresh uniqueId
            $homeContent = $this->getCleanStartHomeContent($language, $homeUniqueId);
            $languageFolder->newFile('home.json', json_encode($homeContent, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            $this->logger->info("[CleanStart] Created home.json");

            // Create navigation.json with link to home
            $navigationContent = $this->getCleanStartNavigationContent($language, $homeUniqueId);
            $languageFolder->newFile('navigation.json', json_encode($navigationContent, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            $this->logger->info("[CleanStart] Created navigation.json");

            // Create empty footer.json
            $footerContent = $this->getCleanStartFooterContent();
            $languageFolder->newFile('footer.json', json_encode($footerContent, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            $this->logger->info("[CleanStart] Created footer.json");

            // Create _resources folder
            $languageFolder->newFolder('_resources');
            $this->logger->info("[CleanStart] Created _resources folder");

            // Trigger groupfolder scan
            $this->scanGroupfolder();

            $this->logger->info("[CleanStart] Clean start completed successfully for language: {$language}");

            return [
                'success' => true,
                'message' => "Clean start completed successfully. Created fresh homepage with new ID.",
                'uniqueId' => $homeUniqueId,
                'commentsDeleted' => $commentsDeleted,
            ];

        } catch (\Exception $e) {
            $this->logger->error("[CleanStart] Clean start failed: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to perform clean start: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Recursively collect all page uniqueIds from a language folder
     * Used for cleaning up comments/reactions when deleting all content
     *
     * @param Folder $folder The folder to scan
     * @return array List of uniqueIds found
     */
    private function collectAllPageUniqueIds(Folder $folder): array {
        $uniqueIds = [];

        try {
            $nodes = $folder->getDirectoryListing();

            foreach ($nodes as $node) {
                if ($node instanceof Folder) {
                    // Recursively scan subfolders (but skip _media and _resources folders)
                    $nodeName = $node->getName();
                    if ($nodeName !== '_media' && $nodeName !== '_resources') {
                        $subIds = $this->collectAllPageUniqueIds($node);
                        $uniqueIds = array_merge($uniqueIds, $subIds);
                    }
                } elseif ($node instanceof \OCP\Files\File) {
                    // Check if it's a JSON file (potential page)
                    $name = $node->getName();
                    if (str_ends_with($name, '.json') && $name !== 'navigation.json' && $name !== 'footer.json') {
                        try {
                            $content = $node->getContent();
                            $data = json_decode($content, true);
                            if ($data && isset($data['uniqueId'])) {
                                $uniqueIds[] = $data['uniqueId'];
                            }
                        } catch (\Exception $e) {
                            $this->logger->warning("[CleanStart] Could not read page file {$name}: " . $e->getMessage());
                        }
                    }
                }
            }
        } catch (\Exception $e) {
            $this->logger->warning("[CleanStart] Error scanning folder: " . $e->getMessage());
        }

        return $uniqueIds;
    }

    /**
     * Get clean start homepage content for a specific language
     */
    private function getCleanStartHomeContent(string $lang, string $uniqueId): array {
        $translations = [
            'nl' => ['title' => 'Home', 'heading' => 'Welkom'],
            'en' => ['title' => 'Home', 'heading' => 'Welcome'],
            'de' => ['title' => 'Startseite', 'heading' => 'Willkommen'],
            'fr' => ['title' => 'Accueil', 'heading' => 'Bienvenue'],
        ];

        $t = $translations[$lang] ?? $translations['en'];
        $now = time();

        return [
            'uniqueId' => $uniqueId,
            'title' => $t['title'],
            'layout' => [
                'columns' => 1,
                'rows' => [
                    [
                        'widgets' => [
                            [
                                'type' => 'heading',
                                'content' => $t['heading'],
                                'level' => 1,
                                'column' => 1,
                                'order' => 1,
                            ],
                        ],
                    ],
                ],
            ],
            'created' => $now,
            'modified' => $now,
        ];
    }

    /**
     * Get clean start navigation content
     */
    private function getCleanStartNavigationContent(string $lang, string $homeUniqueId): array {
        $translations = [
            'nl' => 'Home',
            'en' => 'Home',
            'de' => 'Startseite',
            'fr' => 'Accueil',
        ];

        return [
            'type' => 'megamenu',
            'items' => [
                [
                    'id' => 'nav_home',
                    'title' => $translations[$lang] ?? 'Home',
                    'uniqueId' => $homeUniqueId,
                    'url' => null,
                    'target' => null,
                    'children' => [],
                ],
            ],
        ];
    }

    /**
     * Get clean start footer content (empty)
     */
    private function getCleanStartFooterContent(): array {
        return [
            'content' => '',
            'modifiedBy' => 'system',
        ];
    }

    /**
     * Generate a UUID v4
     * Same implementation as PageService::generateUUID()
     */
    private function generateUUID(): string {
        $data = random_bytes(16);

        // Set version to 4
        $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
        // Set variant to RFC 4122
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80);

        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }
}
