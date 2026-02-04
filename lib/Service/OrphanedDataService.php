<?php
declare(strict_types=1);

namespace OCA\IntraVox\Service;

use OCP\IConfig;
use Psr\Log\LoggerInterface;

/**
 * Service for detecting and managing orphaned GroupFolder data.
 *
 * When the GroupFolders app is reinstalled, physical data may remain on disk
 * while database registrations are lost. This service helps detect, recover,
 * or clean up such orphaned data.
 */
class OrphanedDataService {
    private const SUPPORTED_LANGUAGES = ['nl', 'en', 'de', 'fr'];

    private IConfig $config;
    private SetupService $setupService;
    private LoggerInterface $logger;

    public function __construct(
        IConfig $config,
        SetupService $setupService,
        LoggerInterface $logger
    ) {
        $this->config = $config;
        $this->setupService = $setupService;
        $this->logger = $logger;
    }

    /**
     * Scan for orphaned GroupFolder directories.
     *
     * Compares physical directories in __groupfolders/ with registered
     * GroupFolders in the database to find orphaned data.
     *
     * @return array List of orphaned folders with metadata
     */
    public function detectOrphanedFolders(): array {
        $orphaned = [];

        try {
            $groupFoldersPath = $this->getGroupFoldersPhysicalPath();

            if (!is_dir($groupFoldersPath)) {
                $this->logger->info('[OrphanedData] GroupFolders directory does not exist');
                return [];
            }

            // Get all physical folder IDs
            $physicalIds = $this->getPhysicalFolderIds($groupFoldersPath);
            $this->logger->info('[OrphanedData] Found ' . count($physicalIds) . ' physical folders');

            // Get all registered folder IDs from database
            $registeredIds = $this->getRegisteredGroupFolderIds();
            $this->logger->info('[OrphanedData] Found ' . count($registeredIds) . ' registered folders');

            // Find orphaned folders (physical but not registered)
            $orphanedIds = array_diff($physicalIds, $registeredIds);
            $this->logger->info('[OrphanedData] Found ' . count($orphanedIds) . ' orphaned folders');

            foreach ($orphanedIds as $id) {
                $folderPath = $groupFoldersPath . '/' . $id;
                $filesPath = $folderPath . '/files';

                // Check if it has a files subdirectory (standard GroupFolder structure)
                if (!is_dir($filesPath)) {
                    $this->logger->debug("[OrphanedData] Folder {$id} has no files subdirectory, skipping");
                    continue;
                }

                $folderInfo = $this->analyzeOrphanedFolder($id, $filesPath);
                if ($folderInfo !== null) {
                    $orphaned[] = $folderInfo;
                }
            }

        } catch (\Exception $e) {
            $this->logger->error('[OrphanedData] Detection failed: ' . $e->getMessage());
        }

        return $orphaned;
    }

    /**
     * Get detailed information about a specific orphaned folder.
     *
     * @param int $orphanedId The orphaned folder ID
     * @return array|null Detailed folder info or null if not found/not orphaned
     */
    public function getOrphanedFolderDetails(int $orphanedId): ?array {
        // Verify it's actually orphaned
        if (!$this->isOrphaned($orphanedId)) {
            $this->logger->warning("[OrphanedData] Folder {$orphanedId} is not orphaned");
            return null;
        }

        $groupFoldersPath = $this->getGroupFoldersPhysicalPath();
        $filesPath = $groupFoldersPath . '/' . $orphanedId . '/files';

        if (!is_dir($filesPath)) {
            return null;
        }

        return $this->analyzeOrphanedFolder($orphanedId, $filesPath, true);
    }

    /**
     * Delete an orphaned folder and all its contents.
     *
     * @param int $orphanedId The orphaned folder ID to delete
     * @return array Result with success status and details
     */
    public function cleanupOrphanedFolder(int $orphanedId): array {
        try {
            // Verify it's actually orphaned (safety check)
            if (!$this->isOrphaned($orphanedId)) {
                return [
                    'success' => false,
                    'message' => 'Folder is not orphaned or does not exist',
                ];
            }

            $groupFoldersPath = $this->getGroupFoldersPhysicalPath();
            $folderPath = $groupFoldersPath . '/' . $orphanedId;

            if (!is_dir($folderPath)) {
                return [
                    'success' => false,
                    'message' => 'Folder does not exist',
                ];
            }

            $this->logger->info("[OrphanedData] Starting cleanup of orphaned folder: {$orphanedId}");

            // Calculate size before deletion
            $size = $this->calculateFolderSize($folderPath);

            // Delete recursively
            $deletedCount = $this->recursiveDelete($folderPath);

            $this->logger->info("[OrphanedData] Cleanup complete. Deleted {$deletedCount} items from folder {$orphanedId}");

            return [
                'success' => true,
                'message' => "Orphaned folder {$orphanedId} deleted successfully",
                'deletedItems' => $deletedCount,
                'freedSpace' => $size,
                'freedSpaceFormatted' => $this->formatSize($size),
            ];

        } catch (\Exception $e) {
            $this->logger->error("[OrphanedData] Cleanup failed: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Cleanup failed: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Migrate content from an orphaned folder to the active IntraVox GroupFolder.
     *
     * @param int $orphanedId Source orphaned folder ID
     * @param string $language Language to migrate (nl, en, de, fr)
     * @param string $mode Migration mode: 'merge' (skip existing) or 'replace' (overwrite)
     * @return array Result with success status and details
     */
    public function migrateOrphanedToActive(int $orphanedId, string $language, string $mode = 'merge'): array {
        try {
            // Validate language
            if (!in_array($language, self::SUPPORTED_LANGUAGES)) {
                return [
                    'success' => false,
                    'message' => "Unsupported language: {$language}",
                ];
            }

            // Validate mode
            if (!in_array($mode, ['merge', 'replace'])) {
                return [
                    'success' => false,
                    'message' => "Invalid mode: {$mode}. Use 'merge' or 'replace'",
                ];
            }

            // Verify orphaned folder exists
            if (!$this->isOrphaned($orphanedId)) {
                return [
                    'success' => false,
                    'message' => 'Source folder is not orphaned or does not exist',
                ];
            }

            $groupFoldersPath = $this->getGroupFoldersPhysicalPath();
            $sourcePath = $groupFoldersPath . '/' . $orphanedId . '/files/' . $language;

            if (!is_dir($sourcePath)) {
                return [
                    'success' => false,
                    'message' => "Language folder '{$language}' not found in orphaned data",
                ];
            }

            // Ensure setup is complete (creates the active GroupFolder if needed)
            if (!$this->setupService->isSetupComplete()) {
                $this->logger->info("[OrphanedData] Setup not complete, running setup first...");
                $setupResult = $this->setupService->setupSharedFolder();
                if (!$setupResult['success']) {
                    return [
                        'success' => false,
                        'message' => 'Failed to setup IntraVox: ' . ($setupResult['error'] ?? 'unknown error'),
                    ];
                }
            }

            // Get active GroupFolder ID
            $activeId = $this->setupService->getGroupFolderId();
            $targetPath = $groupFoldersPath . '/' . $activeId . '/files/' . $language;

            $this->logger->info("[OrphanedData] Starting migration from {$orphanedId} to {$activeId} for language {$language}, mode: {$mode}");

            // Handle replace mode - delete existing content first
            if ($mode === 'replace' && is_dir($targetPath)) {
                $this->logger->info("[OrphanedData] Replace mode: deleting existing content at {$targetPath}");
                $this->recursiveDelete($targetPath);
            }

            // Ensure target directory exists
            if (!is_dir($targetPath)) {
                if (!mkdir($targetPath, 0755, true)) {
                    return [
                        'success' => false,
                        'message' => 'Failed to create target directory',
                    ];
                }
            }

            // Copy files
            $overwrite = ($mode === 'replace');
            $result = $this->recursiveCopy($sourcePath, $targetPath, $overwrite);

            // Trigger file cache scan
            $this->scanGroupfolder($activeId);

            $this->logger->info("[OrphanedData] Migration complete. Copied: {$result['copied']}, Skipped: {$result['skipped']}, Errors: {$result['errors']}");

            return [
                'success' => $result['errors'] === 0,
                'message' => $result['errors'] === 0
                    ? "Migration completed successfully"
                    : "Migration completed with {$result['errors']} errors",
                'migratedFiles' => $result['copied'],
                'skippedExisting' => $result['skipped'],
                'errors' => $result['errors'],
            ];

        } catch (\Exception $e) {
            $this->logger->error("[OrphanedData] Migration failed: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Migration failed: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Get the physical path to the GroupFolders data directory.
     */
    private function getGroupFoldersPhysicalPath(): string {
        $dataDir = $this->config->getSystemValue('datadirectory', \OC::$SERVERROOT . '/data');
        return $dataDir . '/__groupfolders';
    }

    /**
     * Get all physical folder IDs from the GroupFolders directory.
     */
    private function getPhysicalFolderIds(string $groupFoldersPath): array {
        $ids = [];
        $entries = scandir($groupFoldersPath);

        foreach ($entries as $entry) {
            // Only include numeric directory names
            if ($entry !== '.' && $entry !== '..' && is_numeric($entry) && is_dir($groupFoldersPath . '/' . $entry)) {
                $ids[] = (int)$entry;
            }
        }

        return $ids;
    }

    /**
     * Get all registered GroupFolder IDs from the database.
     */
    private function getRegisteredGroupFolderIds(): array {
        try {
            if (!\OC::$server->getAppManager()->isEnabledForUser('groupfolders')) {
                $this->logger->warning('[OrphanedData] GroupFolders app is not enabled');
                return [];
            }

            $groupfolderManager = \OC::$server->get(\OCA\GroupFolders\Folder\FolderManager::class);
            $folders = $groupfolderManager->getAllFolders();

            return array_map('intval', array_keys($folders));

        } catch (\Exception $e) {
            $this->logger->error('[OrphanedData] Failed to get registered folders: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Check if a folder ID is orphaned (exists physically but not in database).
     */
    private function isOrphaned(int $folderId): bool {
        $groupFoldersPath = $this->getGroupFoldersPhysicalPath();
        $folderPath = $groupFoldersPath . '/' . $folderId;

        // Must exist physically
        if (!is_dir($folderPath)) {
            return false;
        }

        // Must NOT be registered in database
        $registeredIds = $this->getRegisteredGroupFolderIds();
        return !in_array($folderId, $registeredIds, true);
    }

    /**
     * Analyze an orphaned folder and return metadata.
     *
     * @param int $id Folder ID
     * @param string $filesPath Path to the files subdirectory
     * @param bool $detailed Whether to include detailed file lists
     */
    private function analyzeOrphanedFolder(int $id, string $filesPath, bool $detailed = false): ?array {
        try {
            $size = $this->calculateFolderSize($filesPath);
            $lastModified = $this->getLastModified($filesPath);
            $hasIntraVoxContent = $this->hasIntraVoxContent($filesPath);
            $languages = $this->detectLanguages($filesPath);
            $pageCount = $this->countPages($filesPath);

            $info = [
                'id' => $id,
                'path' => dirname($filesPath),
                'size' => $size,
                'sizeFormatted' => $this->formatSize($size),
                'hasIntraVoxContent' => $hasIntraVoxContent,
                'languages' => $languages,
                'lastModified' => $lastModified,
                'lastModifiedFormatted' => $lastModified ? date('Y-m-d H:i', $lastModified) : null,
                'pageCount' => $pageCount,
            ];

            if ($detailed) {
                $info['languageDetails'] = $this->getLanguageDetails($filesPath);
            }

            return $info;

        } catch (\Exception $e) {
            $this->logger->error("[OrphanedData] Failed to analyze folder {$id}: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Check if a folder contains IntraVox content.
     * Looks for language folders with home.json files.
     */
    private function hasIntraVoxContent(string $filesPath): bool {
        foreach (self::SUPPORTED_LANGUAGES as $lang) {
            $homeJson = $filesPath . '/' . $lang . '/home.json';
            if (is_file($homeJson)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Detect which language folders exist in the files directory.
     */
    private function detectLanguages(string $filesPath): array {
        $found = [];
        foreach (self::SUPPORTED_LANGUAGES as $lang) {
            if (is_dir($filesPath . '/' . $lang)) {
                $found[] = $lang;
            }
        }
        return $found;
    }

    /**
     * Get detailed information about each language folder.
     */
    private function getLanguageDetails(string $filesPath): array {
        $details = [];

        foreach (self::SUPPORTED_LANGUAGES as $lang) {
            $langPath = $filesPath . '/' . $lang;
            if (!is_dir($langPath)) {
                continue;
            }

            $pageCount = $this->countPagesInLanguage($langPath);
            $hasHome = is_file($langPath . '/home.json');
            $hasNavigation = is_file($langPath . '/navigation.json');
            $hasFooter = is_file($langPath . '/footer.json');
            $mediaCount = $this->countMediaFiles($langPath);

            $details[$lang] = [
                'pageCount' => $pageCount,
                'hasHome' => $hasHome,
                'hasNavigation' => $hasNavigation,
                'hasFooter' => $hasFooter,
                'mediaCount' => $mediaCount,
                'size' => $this->calculateFolderSize($langPath),
                'sizeFormatted' => $this->formatSize($this->calculateFolderSize($langPath)),
            ];
        }

        return $details;
    }

    /**
     * Count total pages across all languages.
     */
    private function countPages(string $filesPath): int {
        $total = 0;
        foreach (self::SUPPORTED_LANGUAGES as $lang) {
            $langPath = $filesPath . '/' . $lang;
            if (is_dir($langPath)) {
                $total += $this->countPagesInLanguage($langPath);
            }
        }
        return $total;
    }

    /**
     * Count pages in a specific language folder.
     * Counts .json files (excluding navigation.json and footer.json).
     */
    private function countPagesInLanguage(string $langPath): int {
        $count = 0;
        $this->countJsonFilesRecursive($langPath, $count);
        return $count;
    }

    /**
     * Recursively count JSON files (pages).
     */
    private function countJsonFilesRecursive(string $path, int &$count): void {
        $entries = scandir($path);
        foreach ($entries as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }

            $fullPath = $path . '/' . $entry;

            if (is_dir($fullPath)) {
                // Skip _media and _resources folders
                if ($entry !== '_media' && $entry !== '_resources') {
                    $this->countJsonFilesRecursive($fullPath, $count);
                }
            } elseif (is_file($fullPath) && str_ends_with($entry, '.json')) {
                // Count JSON files except navigation and footer
                if ($entry !== 'navigation.json' && $entry !== 'footer.json') {
                    $count++;
                }
            }
        }
    }

    /**
     * Count media files in a language folder.
     */
    private function countMediaFiles(string $langPath): int {
        $count = 0;
        $this->countMediaRecursive($langPath, $count);
        return $count;
    }

    /**
     * Recursively count media files.
     */
    private function countMediaRecursive(string $path, int &$count): void {
        $entries = scandir($path);
        foreach ($entries as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }

            $fullPath = $path . '/' . $entry;

            if (is_dir($fullPath)) {
                // Only recurse into _media and _resources folders for media counting
                if ($entry === '_media' || $entry === '_resources') {
                    $this->countFilesInDir($fullPath, $count);
                } else {
                    $this->countMediaRecursive($fullPath, $count);
                }
            }
        }
    }

    /**
     * Count all files in a directory (non-recursive).
     */
    private function countFilesInDir(string $path, int &$count): void {
        $entries = scandir($path);
        foreach ($entries as $entry) {
            if ($entry !== '.' && $entry !== '..' && is_file($path . '/' . $entry)) {
                $count++;
            }
        }
    }

    /**
     * Calculate the total size of a directory recursively.
     */
    private function calculateFolderSize(string $path): int {
        $size = 0;

        if (!is_dir($path)) {
            return 0;
        }

        $entries = scandir($path);
        foreach ($entries as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }

            $fullPath = $path . '/' . $entry;
            if (is_file($fullPath)) {
                $size += filesize($fullPath);
            } elseif (is_dir($fullPath)) {
                $size += $this->calculateFolderSize($fullPath);
            }
        }

        return $size;
    }

    /**
     * Get the most recent modification time in a directory.
     */
    private function getLastModified(string $path): ?int {
        $latest = null;

        if (!is_dir($path)) {
            return null;
        }

        $entries = scandir($path);
        foreach ($entries as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }

            $fullPath = $path . '/' . $entry;
            $mtime = filemtime($fullPath);

            if ($mtime !== false && ($latest === null || $mtime > $latest)) {
                $latest = $mtime;
            }

            if (is_dir($fullPath)) {
                $subLatest = $this->getLastModified($fullPath);
                if ($subLatest !== null && ($latest === null || $subLatest > $latest)) {
                    $latest = $subLatest;
                }
            }
        }

        return $latest;
    }

    /**
     * Format a size in bytes to human-readable format.
     */
    private function formatSize(int $bytes): string {
        $units = ['B', 'KB', 'MB', 'GB'];
        $unitIndex = 0;
        $size = (float)$bytes;

        while ($size >= 1024 && $unitIndex < count($units) - 1) {
            $size /= 1024;
            $unitIndex++;
        }

        return round($size, 1) . ' ' . $units[$unitIndex];
    }

    /**
     * Recursively delete a directory and all its contents.
     *
     * @return int Number of items deleted
     */
    private function recursiveDelete(string $path): int {
        $count = 0;

        if (!is_dir($path)) {
            if (is_file($path)) {
                unlink($path);
                return 1;
            }
            return 0;
        }

        $entries = scandir($path);
        foreach ($entries as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }

            $fullPath = $path . '/' . $entry;
            if (is_dir($fullPath)) {
                $count += $this->recursiveDelete($fullPath);
            } else {
                if (unlink($fullPath)) {
                    $count++;
                }
            }
        }

        if (rmdir($path)) {
            $count++;
        }

        return $count;
    }

    /**
     * Recursively copy a directory.
     *
     * @param string $source Source directory
     * @param string $target Target directory
     * @param bool $overwrite Whether to overwrite existing files
     * @return array Result with copied, skipped, errors counts
     */
    private function recursiveCopy(string $source, string $target, bool $overwrite): array {
        $result = ['copied' => 0, 'skipped' => 0, 'errors' => 0];

        if (!is_dir($source)) {
            $result['errors']++;
            return $result;
        }

        // Ensure target directory exists
        if (!is_dir($target)) {
            if (!mkdir($target, 0755, true)) {
                $result['errors']++;
                return $result;
            }
        }

        $entries = scandir($source);
        foreach ($entries as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }

            $sourcePath = $source . '/' . $entry;
            $targetPath = $target . '/' . $entry;

            if (is_dir($sourcePath)) {
                $subResult = $this->recursiveCopy($sourcePath, $targetPath, $overwrite);
                $result['copied'] += $subResult['copied'];
                $result['skipped'] += $subResult['skipped'];
                $result['errors'] += $subResult['errors'];
            } else {
                // Check if target exists
                if (is_file($targetPath) && !$overwrite) {
                    $result['skipped']++;
                    continue;
                }

                // Copy file
                if (copy($sourcePath, $targetPath)) {
                    $result['copied']++;
                } else {
                    $result['errors']++;
                    $this->logger->error("[OrphanedData] Failed to copy: {$sourcePath} -> {$targetPath}");
                }
            }
        }

        return $result;
    }

    /**
     * Trigger a GroupFolder scan to update the file cache.
     */
    private function scanGroupfolder(int $folderId): void {
        try {
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
                $this->logger->info("[OrphanedData] Triggered groupfolder scan for folder {$folderId}");
            }
        } catch (\Exception $e) {
            $this->logger->warning("[OrphanedData] Failed to trigger scan: " . $e->getMessage());
        }
    }
}
