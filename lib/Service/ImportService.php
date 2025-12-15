<?php
declare(strict_types=1);

namespace OCA\IntraVox\Service;

use OCP\Files\File;
use OCP\Files\Folder;
use OCP\ITempManager;
use Psr\Log\LoggerInterface;

/**
 * Service for importing IntraVox pages and media from ZIP exports
 */
class ImportService {
    public function __construct(
        private PageService $pageService,
        private SetupService $setupService,
        private CommentService $commentService,
        private NavigationService $navigationService,
        private FooterService $footerService,
        private ITempManager $tempManager,
        private LoggerInterface $logger
    ) {}

    /**
     * Import from ZIP file
     *
     * @param string $zipContent ZIP file content
     * @param bool $importComments Import comments and reactions
     * @param bool $overwrite Overwrite existing pages
     * @return array Import result with stats
     */
    public function importFromZip(string $zipContent, bool $importComments = true, bool $overwrite = false, ?string $parentPageId = null): array {
        $tempDir = $this->tempManager->getTemporaryFolder();
        $zipPath = $tempDir . '/import.zip';

        $this->logger->info('Starting import from ZIP', [
            'parentPageId' => $parentPageId
        ]);

        // 1. Write ZIP to temp
        file_put_contents($zipPath, $zipContent);

        // 2. Extract ZIP
        $zip = new \ZipArchive();
        if ($zip->open($zipPath) !== true) {
            throw new \Exception('Invalid ZIP file');
        }
        $zip->extractTo($tempDir);
        $zip->close();

        // 3. Read export.json
        $exportJsonPath = $tempDir . '/export.json';
        if (!file_exists($exportJsonPath)) {
            throw new \Exception('export.json not found in ZIP');
        }
        $exportData = json_decode(file_get_contents($exportJsonPath), true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \Exception('Invalid JSON in export.json: ' . json_last_error_msg());
        }

        // 4. Validate export version
        $version = $exportData['exportVersion'] ?? '0';
        if (version_compare($version, '1.0', '<')) {
            throw new \Exception('Unsupported export version: ' . $version);
        }

        $language = $exportData['language'] ?? 'nl';
        $stats = [
            'pagesImported' => 0,
            'pagesSkipped' => 0,
            'mediaFilesImported' => 0,
            'commentsImported' => 0,
            'navigationImported' => false,
            'footerImported' => false,
        ];

        // 5. Import navigation (arguments: navigation array, language string)
        if (!empty($exportData['navigation'])) {
            try {
                $this->navigationService->saveNavigation($exportData['navigation'], $language);
                $stats['navigationImported'] = true;
                $this->logger->info('Imported navigation for language: ' . $language);
            } catch (\Exception $e) {
                $this->logger->warning('Failed to import navigation: ' . $e->getMessage());
            }
        }

        // 6. Import footer (saveFooter only takes content string, uses user's current language)
        if (!empty($exportData['footer'])) {
            try {
                // Footer data contains 'content' key
                $footerContent = $exportData['footer']['content'] ?? '';
                if (!empty($footerContent)) {
                    $this->footerService->saveFooter($footerContent);
                    $stats['footerImported'] = true;
                    $this->logger->info('Imported footer');
                }
            } catch (\Exception $e) {
                $this->logger->warning('Failed to import footer: ' . $e->getMessage());
            }
        }

        // 7. Sort pages by parent relationships (parents before children)
        $sortedPages = $this->sortPagesByHierarchy($exportData['pages'] ?? []);

        // Track imported pages by uniqueId to resolve parent paths
        $importedPages = [];

        // If parent page ID is provided, find its path and add to importedPages
        if ($parentPageId) {
            try {
                $parentPath = $this->findPagePath($parentPageId, $language);
                if ($parentPath) {
                    $importedPages[$parentPageId] = $parentPath;
                    $this->logger->info('Found parent page path', [
                        'parentPageId' => $parentPageId,
                        'parentPath' => $parentPath
                    ]);
                } else {
                    $this->logger->warning('Parent page not found', [
                        'parentPageId' => $parentPageId
                    ]);
                }
            } catch (\Exception $e) {
                $this->logger->error('Failed to find parent page path', [
                    'parentPageId' => $parentPageId,
                    'error' => $e->getMessage()
                ]);
            }
        }

        // 8. Import pages
        foreach ($sortedPages as $pageData) {
            $result = $this->importPage($pageData, $language, $overwrite, $importedPages);
            if ($result['imported']) {
                $stats['pagesImported']++;

                // Track this page for parent path resolution
                $importedPages[$pageData['uniqueId']] = $result['path'];

                // 9. Import comments if requested
                if ($importComments && !empty($pageData['comments'])) {
                    $commentsCount = $this->importComments(
                        $pageData['uniqueId'],
                        $pageData['comments']
                    );
                    $stats['commentsImported'] += $commentsCount;
                }
            } else {
                $stats['pagesSkipped']++;
            }
        }

        // 9. Import media files
        $mediaStats = $this->importMediaFiles($tempDir, $language);
        $stats['mediaFilesImported'] = $mediaStats;

        // 10. Update navigation with imported pages (if navigation wasn't explicitly imported)
        if (empty($exportData['navigation']) && !empty($importedPages) && $parentPageId) {
            try {
                $this->updateNavigationWithImportedPages($sortedPages, $importedPages, $language, $parentPageId);
                $stats['navigationUpdated'] = true;
                $this->logger->info('Updated navigation with imported pages');
            } catch (\Exception $e) {
                $this->logger->warning('Failed to update navigation: ' . $e->getMessage());
            }
        }

        // 11. Trigger groupfolder scan to update file cache
        $this->triggerGroupfolderScan();

        // Cleanup
        $this->cleanupTempDir($tempDir);

        $this->logger->info('Import complete', $stats);

        return $stats;
    }

    /**
     * Import a single page
     *
     * @param array $pageData Page data from export
     * @param string $language Language code
     * @param bool $overwrite Overwrite existing pages
     * @param array $importedPages Map of uniqueId => folderPath for already imported pages
     * @return array Result with 'imported' boolean and 'path' if imported
     */
    private function importPage(array $pageData, string $language, bool $overwrite, array $importedPages = []): array {
        $uniqueId = $pageData['uniqueId'] ?? '';
        $content = $pageData['content'] ?? [];
        $parentUniqueId = $pageData['parentUniqueId'] ?? null;

        if (empty($uniqueId)) {
            $this->logger->warning('Page has no uniqueId, skipping');
            return ['imported' => false, 'reason' => 'no_uniqueId'];
        }

        // Get the export path (determines where page should be saved)
        $exportPath = $content['_exportPath'] ?? null;

        // If page has a parent, determine the parent path
        $parentPath = null;
        if ($parentUniqueId && isset($importedPages[$parentUniqueId])) {
            $parentPath = $importedPages[$parentUniqueId];
            $this->logger->info('Page has parent', [
                'uniqueId' => $uniqueId,
                'parentUniqueId' => $parentUniqueId,
                'parentPath' => $parentPath
            ]);
        }

        $this->logger->info('Importing page', [
            'uniqueId' => $uniqueId,
            'exportPath' => $exportPath,
            'parentUniqueId' => $parentUniqueId,
            'parentPath' => $parentPath,
            'language' => $language,
            'overwrite' => $overwrite,
            'hasContent' => !empty($content)
        ]);

        // Remove internal export metadata before saving
        unset($content['_exportPath']);

        try {
            $intraVoxFolder = $this->setupService->getSharedFolder();

            // Ensure language folder exists
            if (!$intraVoxFolder->nodeExists($language)) {
                $intraVoxFolder->newFolder($language);
            }
            $langFolder = $intraVoxFolder->get($language);

            if (!($langFolder instanceof Folder)) {
                throw new \Exception('Language folder is not a folder');
            }

            // Determine target file path based on parent path or export path
            if ($exportPath === 'home') {
                // Home page goes directly in language folder as home.json
                $targetFile = 'home.json';
                $targetFolder = $langFolder;
                $pageFolderPath = $language; // For tracking

                // Check if file already exists
                $exists = $targetFolder->nodeExists($targetFile);
                $this->logger->info('Checking if home page exists', [
                    'targetFile' => $targetFile,
                    'exists' => $exists,
                    'overwrite' => $overwrite
                ]);

                if ($exists && !$overwrite) {
                    $this->logger->info('Home page already exists, skipping');
                    return ['imported' => false, 'reason' => 'exists'];
                }

                // Write or update the home page file
                $jsonContent = json_encode($content, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

                if ($exists) {
                    $file = $targetFolder->get($targetFile);
                    $file->putContent($jsonContent);
                    $this->logger->info('Updated home page');
                } else {
                    $targetFolder->newFile($targetFile, $jsonContent);
                    $this->logger->info('Created home page');
                }

                // Ensure _media folder exists for home page
                if (!$targetFolder->nodeExists('_media')) {
                    $mediaFolder = $targetFolder->newFolder('_media');
                    $this->createMediaFolderMarker($mediaFolder);
                    $this->logger->info('Created _media folder for home page');
                }
            } else {
                // Regular page: create folder structure like IntraVox does
                // 1. Determine folder name (pageId)
                $folderId = $exportPath ?: $this->extractPageIdFromUniqueId($uniqueId);

                // 2. Determine parent location
                if ($parentPath) {
                    // Remove language prefix from parent path to get relative path
                    $relativeParentPath = preg_replace('/^' . preg_quote($language, '/') . '\//', '', $parentPath);
                    $parentFolder = $this->ensureFolderPath($langFolder, $relativeParentPath);
                } else {
                    // No parent, create at language root
                    $parentFolder = $langFolder;
                }

                // 3. Create or get page folder
                $exists = $parentFolder->nodeExists($folderId);
                $this->logger->info('Checking if page folder exists', [
                    'folderId' => $folderId,
                    'parentFolder' => $parentFolder->getPath(),
                    'exists' => $exists,
                    'overwrite' => $overwrite
                ]);

                if ($exists && !$overwrite) {
                    $this->logger->info('Page folder already exists, skipping: ' . $uniqueId);
                    return ['imported' => false, 'reason' => 'exists'];
                }

                if ($exists) {
                    $pageFolder = $parentFolder->get($folderId);
                    if (!($pageFolder instanceof Folder)) {
                        throw new \Exception("Page path exists but is not a folder: $folderId");
                    }
                } else {
                    $pageFolder = $parentFolder->newFolder($folderId);
                    $this->logger->info('Created page folder: ' . $folderId);
                }

                // 4. Create or update {pageId}.json file inside the folder
                $targetFile = $folderId . '.json';
                $jsonContent = json_encode($content, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

                if ($pageFolder->nodeExists($targetFile)) {
                    $file = $pageFolder->get($targetFile);
                    $file->putContent($jsonContent);
                    $this->logger->info('Updated page JSON: ' . $targetFile);
                } else {
                    $pageFolder->newFile($targetFile, $jsonContent);
                    $this->logger->info('Created page JSON: ' . $targetFile);
                }

                // 5. Ensure _media folder exists
                if (!$pageFolder->nodeExists('_media')) {
                    $mediaFolder = $pageFolder->newFolder('_media');
                    $this->createMediaFolderMarker($mediaFolder);
                    $this->logger->info('Created _media folder for page: ' . $folderId);
                }

                // Calculate full page path
                $relativePagePath = $this->getRelativePath($pageFolder, $langFolder);
                $pageFolderPath = $language . '/' . $relativePagePath;
            }

            return ['imported' => true, 'path' => $pageFolderPath];
        } catch (\Exception $e) {
            $this->logger->error('Failed to import page ' . $uniqueId . ': ' . $e->getMessage());
            return ['imported' => false, 'reason' => 'error: ' . $e->getMessage()];
        }
    }

    /**
     * Extract page ID from uniqueId (e.g., "page-abc123" -> "abc123" or use as-is)
     */
    private function extractPageIdFromUniqueId(string $uniqueId): string {
        // If uniqueId starts with "page-", extract the rest
        if (str_starts_with($uniqueId, 'page-')) {
            return substr($uniqueId, 5);
        }
        return $uniqueId;
    }

    /**
     * Import comments for a page
     *
     * @param string $uniqueId Page unique ID
     * @param array $comments Comments data
     * @return int Number of comments imported
     */
    private function importComments(string $uniqueId, array $comments): int {
        $count = 0;

        foreach ($comments as $commentData) {
            try {
                // Import comment using CommentService
                $this->commentService->createComment(
                    $uniqueId,
                    $commentData['message'] ?? '',
                    $commentData['userId'] ?? null,
                    $commentData['parentId'] ?? null
                );
                $count++;

                // Import replies recursively
                if (!empty($commentData['replies'])) {
                    $count += $this->importComments($uniqueId, $commentData['replies']);
                }
            } catch (\Exception $e) {
                $this->logger->warning('Failed to import comment: ' . $e->getMessage());
            }
        }

        return $count;
    }

    /**
     * Import media files from temp directory to IntraVox folder
     *
     * @param string $tempDir Temporary directory
     * @param string $language Language code
     * @return int Number of media files imported
     */
    private function importMediaFiles(string $tempDir, string $language): int {
        $count = 0;
        $mediaSourceDir = $tempDir . '/' . $language;

        if (!is_dir($mediaSourceDir)) {
            $this->logger->info('No media directory found for language: ' . $language);
            return 0;
        }

        try {
            $intraVoxFolder = $this->setupService->getSharedFolder();

            // Recursively find _media folders
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($mediaSourceDir, \FilesystemIterator::SKIP_DOTS),
                \RecursiveIteratorIterator::SELF_FIRST
            );

            foreach ($iterator as $item) {
                if ($item->isFile() && str_contains($item->getPath(), '_media')) {
                    // Determine target path relative to temp dir
                    $relativePath = substr($item->getPathname(), strlen($tempDir) + 1);

                    try {
                        // Ensure folder path exists
                        $targetFolder = $this->ensureFolderPath($intraVoxFolder, dirname($relativePath));

                        // Copy file
                        $fileName = $item->getFilename();
                        if (!$targetFolder->nodeExists($fileName)) {
                            $targetFolder->newFile($fileName, file_get_contents($item->getPathname()));
                            $count++;
                            $this->logger->debug('Imported media file: ' . $relativePath);
                        } else {
                            $this->logger->debug('Media file already exists: ' . $relativePath);
                        }
                    } catch (\Exception $e) {
                        $this->logger->warning('Failed to import media file ' . $relativePath . ': ' . $e->getMessage());
                    }
                }
            }

            $this->logger->info('Imported ' . $count . ' media files');
        } catch (\Exception $e) {
            $this->logger->error('Failed to import media files: ' . $e->getMessage());
        }

        return $count;
    }

    /**
     * Ensure folder path exists, creating folders as needed
     *
     * @param Folder $root Root folder
     * @param string $path Path to ensure
     * @return Folder Target folder
     */
    private function ensureFolderPath(Folder $root, string $path): Folder {
        $parts = explode('/', $path);
        $current = $root;

        foreach ($parts as $part) {
            if (empty($part)) {
                continue;
            }

            if ($current->nodeExists($part)) {
                $node = $current->get($part);
                if ($node instanceof Folder) {
                    $current = $node;
                } else {
                    throw new \Exception('Path element is not a folder: ' . $part);
                }
            } else {
                $current = $current->newFolder($part);
            }
        }

        return $current;
    }

    /**
     * Create .nomedia marker file in _media folder
     */
    private function createMediaFolderMarker($mediaFolder): void {
        try {
            // Only create a simple .nomedia file
            // This is standard practice for media storage folders
            if (!$mediaFolder->nodeExists('.nomedia')) {
                $nomediaFile = $mediaFolder->newFile('.nomedia');
                $nomediaFile->putContent('');
            }
        } catch (\Exception $e) {
            // Not critical if this fails
            $this->logger->debug('Could not create .nomedia file', [
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Get relative path of a folder within another folder
     */
    private function getRelativePath(Folder $folder, Folder $baseFolder): string {
        $folderPath = $folder->getPath();
        $basePath = $baseFolder->getPath();

        // Remove base path from folder path
        if (str_starts_with($folderPath, $basePath)) {
            $relativePath = substr($folderPath, strlen($basePath) + 1);
            return $relativePath;
        }

        // Fallback: return folder name
        return $folder->getName();
    }

    /**
     * Trigger groupfolder scan to update file cache
     */
    private function triggerGroupfolderScan(): void {
        try {
            $folderId = $this->setupService->getGroupFolderId();
            if ($folderId <= 0) {
                $this->logger->warning('Cannot scan: no groupfolder ID');
                return;
            }

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
                $this->logger->info('Triggered groupfolder scan after import', ['folderId' => $folderId]);
            }
        } catch (\Exception $e) {
            $this->logger->warning('Failed to trigger groupfolder scan: ' . $e->getMessage());
        }
    }

    /**
     * Cleanup temporary directory
     *
     * @param string $dir Directory to cleanup
     */
    private function cleanupTempDir(string $dir): void {
        if (!is_dir($dir)) {
            return;
        }

        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($files as $file) {
            if ($file->isDir()) {
                @rmdir($file->getPathname());
            } else {
                @unlink($file->getPathname());
            }
        }
        @rmdir($dir);
    }

    /**
     * Sort pages by hierarchy - parents before children
     *
     * @param array $pages Array of page data
     * @return array Sorted array of pages
     */
    private function sortPagesByHierarchy(array $pages): array {
        // Build a map of uniqueId => page for quick lookup
        $pageMap = [];
        foreach ($pages as $page) {
            $pageMap[$page['uniqueId']] = $page;
        }

        // Build dependency graph
        $sorted = [];
        $visited = [];

        // Helper function to visit a page and its ancestors first
        $visit = function($uniqueId) use (&$visit, &$sorted, &$visited, $pageMap) {
            if (isset($visited[$uniqueId])) {
                return; // Already processed
            }

            $visited[$uniqueId] = true;

            if (!isset($pageMap[$uniqueId])) {
                return; // Page not found
            }

            $page = $pageMap[$uniqueId];

            // Visit parent first if exists
            if (!empty($page['parentUniqueId']) && isset($pageMap[$page['parentUniqueId']])) {
                $visit($page['parentUniqueId']);
            }

            // Add this page
            $sorted[] = $page;
        };

        // Visit all pages
        foreach ($pages as $page) {
            $visit($page['uniqueId']);
        }

        $this->logger->info('Sorted pages by hierarchy', [
            'original_count' => count($pages),
            'sorted_count' => count($sorted)
        ]);

        return $sorted;
    }

    /**
     * Find the folder path for a page by its uniqueId
     *
     * @param string $uniqueId Page unique ID
     * @param string $language Language code
     * @return string|null Path relative to IntraVox folder, or null if not found
     */
    private function findPagePath(string $uniqueId, string $language): ?string {
        $intraVoxFolder = $this->setupService->getSharedFolder();

        // Check if language folder exists
        if (!$intraVoxFolder->nodeExists($language)) {
            return null;
        }

        $langFolder = $intraVoxFolder->get($language);
        if (!($langFolder instanceof Folder)) {
            return null;
        }

        // Check for home page first
        if ($langFolder->nodeExists('home.json')) {
            $homeFile = $langFolder->get('home.json');
            if ($homeFile instanceof \OCP\Files\File) {
                $content = json_decode($homeFile->getContent(), true);
                if ($content && ($content['uniqueId'] ?? '') === $uniqueId) {
                    return $language;
                }
            }
        }

        // Search recursively through folders
        return $this->searchForPagePath($langFolder, $uniqueId, $language);
    }

    /**
     * Recursively search for a page by uniqueId
     *
     * @param Folder $folder Folder to search in
     * @param string $uniqueId Page unique ID to find
     * @param string $currentPath Current path relative to IntraVox folder
     * @return string|null Path if found, null otherwise
     */
    private function searchForPagePath(Folder $folder, string $uniqueId, string $currentPath): ?string {
        foreach ($folder->getDirectoryListing() as $node) {
            if (!($node instanceof Folder)) {
                continue;
            }

            $folderName = $node->getName();

            // Skip special folders
            if (in_array($folderName, ['_media', 'images', 'files', '.nomedia'])) {
                continue;
            }

            // Look for page.json or {foldername}.json
            $possibleFiles = ['page.json', $folderName . '.json'];
            foreach ($possibleFiles as $filename) {
                if ($node->nodeExists($filename)) {
                    $file = $node->get($filename);
                    if ($file instanceof \OCP\Files\File) {
                        $content = json_decode($file->getContent(), true);
                        if ($content && ($content['uniqueId'] ?? '') === $uniqueId) {
                            // Found it! Return path
                            return $currentPath . '/' . $folderName;
                        }
                    }
                }
            }

            // Recursively search in subfolders
            $result = $this->searchForPagePath($node, $uniqueId, $currentPath . '/' . $folderName);
            if ($result !== null) {
                return $result;
            }
        }

        return null;
    }

    /**
     * Update navigation.json with imported pages
     *
     * @param array $sortedPages Imported pages (sorted by hierarchy)
     * @param array $importedPages Map of uniqueId => path
     * @param string $language Language code
     * @param string $parentPageId Parent page uniqueId where pages were imported under
     */
    private function updateNavigationWithImportedPages(array $sortedPages, array $importedPages, string $language, string $parentPageId): void {
        // Load current navigation
        $navigation = $this->navigationService->getNavigation($language);

        if (!$navigation || !isset($navigation['items'])) {
            $this->logger->warning('No navigation found for language: ' . $language);
            return;
        }

        // Build a tree structure of imported pages
        $pageTree = $this->buildPageTree($sortedPages);

        // Find the parent navigation item path
        $parentPath = $this->findNavigationItemPath($navigation['items'], $parentPageId);

        if (!$parentPath) {
            $this->logger->warning('Parent navigation item not found', ['parentPageId' => $parentPageId]);
            // Add to root level as fallback
            $this->addPagesToNavigation($navigation['items'], $pageTree);
        } else {
            // Get reference to parent item
            $parentNavItem = &$this->getNavigationItemByPath($navigation['items'], $parentPath);

            // Add to parent's children
            if (!isset($parentNavItem['children'])) {
                $parentNavItem['children'] = [];
            }
            $this->addPagesToNavigation($parentNavItem['children'], $pageTree);
        }

        // Save updated navigation
        $this->navigationService->saveNavigation($navigation, $language);
        $this->logger->info('Navigation updated with imported pages', [
            'language' => $language,
            'pagesAdded' => count($sortedPages)
        ]);
    }

    /**
     * Build a tree structure from flat page list
     */
    private function buildPageTree(array $pages): array {
        $tree = [];
        $lookup = [];

        // First pass: create all nodes
        foreach ($pages as $page) {
            $uniqueId = $page['uniqueId'];
            $node = [
                'id' => 'nav_' . substr($uniqueId, 5, 8), // nav_abc12345
                'title' => $page['content']['title'] ?? 'Untitled',
                'uniqueId' => $uniqueId,
                'url' => null,
                'target' => null,
                'children' => []
            ];
            $lookup[$uniqueId] = $node;
        }

        // Second pass: build hierarchy
        foreach ($pages as $page) {
            $uniqueId = $page['uniqueId'];
            $parentUniqueId = $page['parentUniqueId'] ?? null;

            if ($parentUniqueId && isset($lookup[$parentUniqueId])) {
                // Add to parent's children
                $lookup[$parentUniqueId]['children'][] = &$lookup[$uniqueId];
            } else {
                // No parent or parent not in imported set = root level
                $tree[] = &$lookup[$uniqueId];
            }
        }

        return $tree;
    }

    /**
     * Find index of navigation item by uniqueId (recursive search)
     * Returns path to item as array of indices
     */
    private function findNavigationItemPath(array $items, string $uniqueId, array $path = []): ?array {
        foreach ($items as $index => $item) {
            if (isset($item['uniqueId']) && $item['uniqueId'] === $uniqueId) {
                return array_merge($path, [$index]);
            }

            if (!empty($item['children'])) {
                $found = $this->findNavigationItemPath($item['children'], $uniqueId, array_merge($path, [$index, 'children']));
                if ($found !== null) {
                    return $found;
                }
            }
        }

        return null;
    }

    /**
     * Get navigation item by path
     */
    private function getNavigationItemByPath(array &$items, array $path) {
        $current = &$items;
        foreach ($path as $key) {
            $current = &$current[$key];
        }
        return $current;
    }

    /**
     * Add pages to navigation array
     */
    private function addPagesToNavigation(array &$navItems, array $pageTree): void {
        foreach ($pageTree as $pageNode) {
            // Check if already exists
            $exists = false;
            foreach ($navItems as $existingItem) {
                if (isset($existingItem['uniqueId']) && $existingItem['uniqueId'] === $pageNode['uniqueId']) {
                    $exists = true;
                    break;
                }
            }

            if (!$exists) {
                $navItems[] = $pageNode;
            }
        }
    }
}
