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
        private LoggerInterface $logger,
        private MetaVoxImportService $metaVoxImportService
    ) {}

    /**
     * Import from ZIP file
     *
     * @param string $zipContent ZIP file content
     * @param bool $importComments Import comments and reactions
     * @param bool $overwrite Overwrite existing pages
     * @param string|null $parentPageId Parent page ID for Confluence imports
     * @param bool $autoCreateMetaVoxFields Auto-create missing MetaVox field definitions
     * @return array Import result with stats
     */
    public function importFromZip(string $zipContent, bool $importComments = true, bool $overwrite = false, ?string $parentPageId = null, bool $autoCreateMetaVoxFields = false): array {
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

        // Check for MetaVox data
        $hasMetaVoxData = isset($exportData['metavox']);
        $metaVoxCompatibility = null;
        $metaVoxFieldValidation = null;

        if ($hasMetaVoxData) {
            // Validate MetaVox compatibility
            $metaVoxCompatibility = $this->metaVoxImportService->validateCompatibility(
                $exportData['metavox']
            );

            // Validate field definitions
            $metaVoxFieldValidation = $this->metaVoxImportService->validateFieldDefinitions(
                $exportData['metavox']['fieldDefinitions'] ?? []
            );

            // Log compatibility status
            $this->logger->info('MetaVox import compatibility check', $metaVoxCompatibility);

            // Create missing fields if auto-create enabled
            if ($autoCreateMetaVoxFields && !empty($metaVoxFieldValidation['fieldsToCreate'])) {
                $createResult = $this->metaVoxImportService->createFieldDefinitions(
                    $metaVoxFieldValidation['fieldsToCreate'],
                    true
                );

                $this->logger->info('Auto-created MetaVox fields', [
                    'created' => count($createResult['created']),
                    'failed' => count($createResult['failed'])
                ]);
            }
        }

        $stats = [
            'pagesImported' => 0,
            'pagesSkipped' => 0,
            'mediaFilesImported' => 0,
            'commentsImported' => 0,
            'navigationImported' => false,
            'footerImported' => false,
            'metavoxEnabled' => $hasMetaVoxData,
            'metavoxFieldsImported' => 0,
            'metavoxFieldsSkipped' => 0,
            'metavoxFieldsFailed' => 0,
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

        // Get groupfolder ID for MetaVox imports
        $groupfolderId = $this->setupService->getGroupFolderId();

        // 8. Import pages
        foreach ($sortedPages as $pageData) {
            $result = $this->importPage($pageData, $language, $overwrite, $importedPages);
            if ($result['imported']) {
                $stats['pagesImported']++;

                // Track this page for parent path resolution
                $importedPages[$pageData['uniqueId']] = $result['path'];

                // Import MetaVox metadata
                if ($hasMetaVoxData &&
                    !empty($pageData['metadata']) &&
                    isset($result['fileId']) &&
                    $groupfolderId > 0) {

                    $this->logger->info('Importing MetaVox metadata for page', [
                        'uniqueId' => $pageData['uniqueId'],
                        'fileId' => $result['fileId'],
                        'groupfolderId' => $groupfolderId,
                        'metadataCount' => count($pageData['metadata'])
                    ]);

                    $metaStats = $this->metaVoxImportService->importPageMetadata(
                        $result['fileId'],
                        $groupfolderId,
                        $pageData['metadata']
                    );

                    $stats['metavoxFieldsImported'] += $metaStats['imported'];
                    $stats['metavoxFieldsSkipped'] += $metaStats['skipped'];
                    $stats['metavoxFieldsFailed'] += $metaStats['failed'];

                    $this->logger->info('MetaVox metadata import complete', [
                        'imported' => $metaStats['imported'],
                        'skipped' => $metaStats['skipped'],
                        'failed' => $metaStats['failed']
                    ]);
                } elseif ($hasMetaVoxData) {
                    $this->logger->debug('Skipping MetaVox metadata import', [
                        'uniqueId' => $pageData['uniqueId'],
                        'hasMetadata' => !empty($pageData['metadata']),
                        'hasFileId' => isset($result['fileId']),
                        'groupfolderId' => $groupfolderId
                    ]);
                }

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
        $stats['mediaFilesImported'] = $this->importMediaFiles($tempDir, $language, $overwrite);

        // 10. Update navigation with imported pages (if navigation wasn't explicitly imported)
        // Always update navigation unless it was explicitly provided in export
        $this->logger->info('Navigation update check', [
            'hasNavigationInExport' => !empty($exportData['navigation']),
            'importedPagesCount' => count($importedPages),
            'parentPageId' => $parentPageId,
            'willUpdateNavigation' => empty($exportData['navigation']) && !empty($importedPages)
        ]);

        if (empty($exportData['navigation']) && !empty($importedPages)) {
            try {
                $this->updateNavigationWithImportedPages($sortedPages, $importedPages, $language, $parentPageId);
                $stats['navigationUpdated'] = true;
                $this->logger->info('Updated navigation with imported pages');
            } catch (\Exception $e) {
                $this->logger->warning('Failed to update navigation: ' . $e->getMessage());
            }
        } else {
            $this->logger->warning('Skipping navigation update', [
                'reason' => !empty($exportData['navigation']) ? 'navigation in export' : 'no imported pages'
            ]);
        }

        // 11. Trigger groupfolder scan to update file cache
        $this->triggerGroupfolderScan();

        // Add MetaVox compatibility info to stats
        if ($metaVoxCompatibility) {
            $stats['metavoxCompatibility'] = $metaVoxCompatibility;
        }
        if ($metaVoxFieldValidation) {
            $stats['metavoxFieldValidation'] = $metaVoxFieldValidation;
        }

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
                    $file = $targetFolder->newFile($targetFile, $jsonContent);
                    $this->logger->info('Created home page');
                }

                // Get file ID for MetaVox metadata import
                $fileId = $file->getId();

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
                    $file = $pageFolder->newFile($targetFile, $jsonContent);
                    $this->logger->info('Created page JSON: ' . $targetFile);
                }

                // Get file ID for MetaVox metadata import
                $fileId = $file->getId();

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

            return ['imported' => true, 'path' => $pageFolderPath, 'fileId' => $fileId];
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
                    $commentData['parentId'] ?? null,
                    $commentData['userId'] ?? null
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
     * @param bool $overwrite Whether to overwrite existing media files
     * @return int Number of media files imported
     */
    private function importMediaFiles(string $tempDir, string $language, bool $overwrite = false): int {
        $count = 0;
        $mediaSourceDir = $tempDir . '/' . $language;

        if (!is_dir($mediaSourceDir)) {
            return 0;
        }

        try {
            $intraVoxFolder = $this->setupService->getSharedFolder();

            // Recursively find _media and _resources folders
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($mediaSourceDir, \FilesystemIterator::SKIP_DOTS),
                \RecursiveIteratorIterator::SELF_FIRST
            );

            foreach ($iterator as $item) {
                if ($item->isFile() && (str_contains($item->getPath(), '_media') || str_contains($item->getPath(), '_resources'))) {
                    $relativePath = substr($item->getPathname(), strlen($tempDir) + 1);

                    try {
                        // Ensure folder path exists (including nested folders in _resources)
                        $targetFolder = $this->ensureFolderPath($intraVoxFolder, dirname($relativePath));

                        // Copy file
                        $fileName = $item->getFilename();
                        $fileExists = $targetFolder->nodeExists($fileName);

                        if (!$fileExists) {
                            // File doesn't exist, create new
                            $targetFolder->newFile($fileName, file_get_contents($item->getPathname()));
                            $count++;
                        } elseif ($overwrite) {
                            // File exists but overwrite is enabled
                            $existingFile = $targetFolder->get($fileName);
                            $existingFile->putContent(file_get_contents($item->getPathname()));
                            $count++;
                        }
                        // If file exists and overwrite is disabled, skip silently
                    } catch (\Exception $e) {
                        $this->logger->warning('Failed to import media file: ' . $e->getMessage(), [
                            'file' => $relativePath
                        ]);
                    }
                }
            }
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

        // Group pages by parent (siblings)
        $pagesByParent = [];
        foreach ($pages as $page) {
            $parentId = $page['parentUniqueId'] ?? 'root';
            if (!isset($pagesByParent[$parentId])) {
                $pagesByParent[$parentId] = [];
            }
            $pagesByParent[$parentId][] = $page;
        }

        // Sort siblings by confluenceOrder if available
        foreach ($pagesByParent as $parentId => &$siblings) {
            usort($siblings, function($a, $b) {
                $orderA = $a['content']['metadata']['confluenceOrder'] ?? 9999;
                $orderB = $b['content']['metadata']['confluenceOrder'] ?? 9999;
                return $orderA <=> $orderB;
            });
        }
        unset($siblings);

        // Build dependency graph with sorted siblings
        $sorted = [];
        $visited = [];

        // Helper function to visit a page and its ancestors first
        $visit = function($uniqueId) use (&$visit, &$sorted, &$visited, $pageMap, $pagesByParent) {
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

            // Visit children in sorted order
            if (isset($pagesByParent[$uniqueId])) {
                foreach ($pagesByParent[$uniqueId] as $child) {
                    $visit($child['uniqueId']);
                }
            }
        };

        // Visit root pages first (in sorted order)
        if (isset($pagesByParent['root'])) {
            foreach ($pagesByParent['root'] as $page) {
                $visit($page['uniqueId']);
            }
        }

        // Visit any remaining pages that weren't reached (shouldn't happen normally)
        foreach ($pages as $page) {
            $visit($page['uniqueId']);
        }

        $this->logger->info('Sorted pages by hierarchy and Confluence order', [
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
     * @param string|null $parentPageId Parent page uniqueId where pages were imported under (null = root level)
     */
    private function updateNavigationWithImportedPages(array $sortedPages, array $importedPages, string $language, ?string $parentPageId): void {
        $this->logger->info('Starting navigation update', [
            'sortedPagesCount' => count($sortedPages),
            'importedPagesCount' => count($importedPages),
            'language' => $language,
            'parentPageId' => $parentPageId ?? 'null (root level)'
        ]);

        // Load current navigation
        $navigation = $this->navigationService->getNavigation($language);

        if (!$navigation || !isset($navigation['items'])) {
            $this->logger->warning('No navigation found for language: ' . $language);
            return;
        }

        $this->logger->info('Current navigation loaded', [
            'itemsCount' => count($navigation['items'])
        ]);

        // Find the parent navigation item path first to determine depth (if parent specified)
        $parentPath = null;
        if ($parentPageId) {
            $parentPath = $this->findNavigationItemPath($navigation['items'], $parentPageId);
            $this->logger->info('Parent path search result', [
                'parentPageId' => $parentPageId,
                'foundPath' => $parentPath
            ]);
        } else {
            $this->logger->info('No parent specified, adding to root level');
        }

        // Calculate parent depth to determine max depth for imported tree
        // Navigation allows max 3 levels (0, 1, 2)
        // If parent is at level 1, we can only add 2 more levels (children at level 2, grandchildren at level 3)
        $parentDepth = 0;
        if ($parentPath) {
            // Count 'children' occurrences in path to get depth
            $parentDepth = substr_count(implode('', $parentPath), 'children') / strlen('children');
            // Actually, let's count properly:
            $depth = 0;
            foreach ($parentPath as $key) {
                if ($key === 'children') {
                    $depth++;
                }
            }
            $parentDepth = $depth;
        }

        // Maximum depth for imported tree = 2 (3 levels total) - parentDepth
        $maxImportDepth = 2 - $parentDepth;
        $this->logger->info('Calculated depth limits', [
            'parentDepth' => $parentDepth,
            'maxImportDepth' => $maxImportDepth,
            'explanation' => "Parent is at level {$parentDepth}, so imported pages can be {$maxImportDepth} levels deep"
        ]);

        // Build a tree structure of imported pages with depth limit
        $pageTree = $this->buildPageTree($sortedPages, $maxImportDepth);
        $this->logger->info('Page tree built', [
            'treeSize' => count($pageTree),
            'tree' => array_map(function($node) {
                return [
                    'id' => $node['id'],
                    'title' => $node['title'],
                    'uniqueId' => substr($node['uniqueId'], 0, 20),
                    'childrenCount' => count($node['children'] ?? [])
                ];
            }, $pageTree)
        ]);

        if (!$parentPath) {
            if ($parentPageId) {
                $this->logger->warning('Parent navigation item not found', ['parentPageId' => $parentPageId]);
                $this->logger->info('Adding to root level as fallback');
            } else {
                $this->logger->info('Adding to root level (no parent specified)');
            }
            $this->addPagesToNavigation($navigation['items'], $pageTree);
        } else {
            $this->logger->info('Found parent, adding to children', ['path' => $parentPath]);
            // Get reference to parent item
            $parentNavItem = &$this->getNavigationItemByPath($navigation['items'], $parentPath);

            $this->logger->info('Parent nav item before adding children', [
                'parentTitle' => $parentNavItem['title'] ?? 'unknown',
                'existingChildrenCount' => count($parentNavItem['children'] ?? [])
            ]);

            // Add to parent's children
            if (!isset($parentNavItem['children'])) {
                $parentNavItem['children'] = [];
            }
            $this->addPagesToNavigation($parentNavItem['children'], $pageTree);

            $this->logger->info('Parent nav item after adding children', [
                'newChildrenCount' => count($parentNavItem['children'])
            ]);
        }

        // Save updated navigation
        $itemsCount = count($navigation['items']);
        $totalChildren = 0;
        foreach ($navigation['items'] as $item) {
            if (isset($item['children'])) {
                $totalChildren += count($item['children']);
            }
        }

        // Log Departments item specifically before save
        $deptsBefore = null;
        foreach ($navigation['items'] as $item) {
            if ($item['uniqueId'] === $parentPageId) {
                $deptsBefore = [
                    'title' => $item['title'],
                    'childrenCount' => count($item['children'] ?? []),
                    'childrenTitles' => array_map(fn($c) => $c['title'] ?? 'no-title', $item['children'] ?? [])
                ];
                break;
            }
        }

        $this->logger->info('Saving updated navigation', [
            'topLevelItems' => $itemsCount,
            'totalChildrenAcrossAllItems' => $totalChildren,
            'departmentsBeforeSave' => $deptsBefore
        ]);

        $savedNavigation = $this->navigationService->saveNavigation($navigation, $language);

        // Log Departments item specifically after save
        $deptsAfter = null;
        foreach ($savedNavigation['items'] as $item) {
            if ($item['uniqueId'] === $parentPageId) {
                $deptsAfter = [
                    'title' => $item['title'],
                    'childrenCount' => count($item['children'] ?? []),
                    'childrenTitles' => array_map(fn($c) => $c['title'] ?? 'no-title', $item['children'] ?? [])
                ];
                break;
            }
        }

        $savedItemsCount = count($savedNavigation['items']);
        $savedTotalChildren = 0;
        foreach ($savedNavigation['items'] as $item) {
            if (isset($item['children'])) {
                $savedTotalChildren += count($item['children']);
            }
        }

        $this->logger->info('Navigation saved - comparison', [
            'beforeSave' => ['topLevel' => $itemsCount, 'children' => $totalChildren],
            'afterSave' => ['topLevel' => $savedItemsCount, 'children' => $savedTotalChildren],
            'itemsLost' => ($itemsCount !== $savedItemsCount),
            'childrenLost' => ($totalChildren !== $savedTotalChildren),
            'departmentsBefore' => $deptsBefore,
            'departmentsAfter' => $deptsAfter,
            'childrenLostFromDepartments' => ($deptsBefore['childrenCount'] ?? 0) - ($deptsAfter['childrenCount'] ?? 0)
        ]);
        $this->logger->info('Navigation updated with imported pages', [
            'language' => $language,
            'pagesAdded' => count($sortedPages)
        ]);
    }

    /**
     * Build a tree structure from flat page list
     * Limits depth to maximum levels allowed in navigation (3 levels total)
     *
     * @param array $pages Flat list of pages
     * @param int $maxDepth Maximum depth to include (default 2 = 3 levels total with root)
     * @return array Tree structure limited to maxDepth
     */
    private function buildPageTree(array $pages, int $maxDepth = 2): array {
        $tree = [];
        $lookup = [];

        // First pass: create all nodes with depth tracking
        foreach ($pages as $page) {
            $uniqueId = $page['uniqueId'];
            $node = [
                'id' => 'nav_' . substr($uniqueId, 5, 8), // nav_abc12345
                'title' => $page['content']['title'] ?? 'Untitled',
                'uniqueId' => $uniqueId,
                'url' => null,
                'target' => null,
                'children' => [],
                '_depth' => 0, // Will be calculated
                '_parentId' => $page['parentUniqueId'] ?? null
            ];
            $lookup[$uniqueId] = $node;
        }

        // Calculate depth for each node
        foreach ($lookup as $uniqueId => &$node) {
            $node['_depth'] = $this->calculateNodeDepth($uniqueId, $lookup);
        }
        unset($node);

        // Second pass: build hierarchy, but only include nodes within maxDepth
        $skippedCount = 0;
        foreach ($pages as $page) {
            $uniqueId = $page['uniqueId'];
            $parentUniqueId = $page['parentUniqueId'] ?? null;
            $nodeDepth = $lookup[$uniqueId]['_depth'];

            // Skip nodes that are too deep
            if ($nodeDepth > $maxDepth) {
                $skippedCount++;
                continue;
            }

            if ($parentUniqueId && isset($lookup[$parentUniqueId])) {
                $parentDepth = $lookup[$parentUniqueId]['_depth'];

                // Only add if parent is also within depth limit
                if ($parentDepth <= $maxDepth) {
                    $lookup[$parentUniqueId]['children'][] = &$lookup[$uniqueId];
                }
            } else {
                // No parent or parent not in imported set = root level
                $tree[] = &$lookup[$uniqueId];
            }
        }

        if ($skippedCount > 0) {
            $this->logger->info("Skipped pages too deep for navigation", [
                'skippedCount' => $skippedCount,
                'maxDepth' => $maxDepth,
                'note' => 'These pages are still created and accessible via page structure'
            ]);
        }

        // Remove temporary depth tracking fields
        $this->cleanupTreeMetadata($tree);

        return $tree;
    }

    /**
     * Calculate depth of a node in the tree (0 = root)
     */
    private function calculateNodeDepth(string $uniqueId, array &$lookup, array &$visited = []): int {
        // Prevent infinite loops
        if (isset($visited[$uniqueId])) {
            return 999; // Very deep to exclude circular references
        }
        $visited[$uniqueId] = true;

        $node = $lookup[$uniqueId];
        if (!$node['_parentId'] || !isset($lookup[$node['_parentId']])) {
            return 0; // Root level
        }

        return 1 + $this->calculateNodeDepth($node['_parentId'], $lookup, $visited);
    }

    /**
     * Remove temporary metadata fields from tree
     */
    private function cleanupTreeMetadata(array &$tree): void {
        foreach ($tree as &$node) {
            unset($node['_depth']);
            unset($node['_parentId']);

            if (!empty($node['children'])) {
                $this->cleanupTreeMetadata($node['children']);
            }
        }
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
     * Returns reference to the item so modifications persist
     */
    private function &getNavigationItemByPath(array &$items, array $path) {
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
