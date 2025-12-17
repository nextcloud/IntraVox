<?php
declare(strict_types=1);

namespace OCA\IntraVox\Service;

use OCP\App\IAppManager;
use OCP\Files\File;
use OCP\Files\Folder;
use OCP\ITempManager;
use Psr\Log\LoggerInterface;

/**
 * Service for exporting IntraVox pages, navigation, and engagement data
 */
class ExportService {
    private ?bool $metaVoxAvailable = null;
    private ?string $metaVoxVersion = null;
    private $metaVoxFieldService = null;

    public function __construct(
        private PageService $pageService,
        private CommentService $commentService,
        private NavigationService $navigationService,
        private FooterService $footerService,
        private SetupService $setupService,
        private ITempManager $tempManager,
        private LoggerInterface $logger,
        private IAppManager $appManager
    ) {}

    /**
     * Export all pages for a language
     *
     * @param string $language Language code (nl, en, de, fr)
     * @param bool $includeComments Include comments and reactions
     * @return array Export data
     */
    public function exportLanguage(string $language, bool $includeComments = true): array {
        $this->logger->info('Starting export for language: ' . $language);

        // Get pages from language folder directly via SetupService
        $pages = $this->getPagesFromLanguageFolder($language);

        $export = [
            'exportVersion' => '1.1',  // Bumped version for MetaVox support
            'exportDate' => (new \DateTime())->format('c'),
            'language' => $language,
            'navigation' => $this->navigationService->getNavigation($language),
            'footer' => $this->footerService->getFooter($language),
            'pages' => []
        ];

        // Add MetaVox information if available
        if ($this->isMetaVoxAvailable()) {
            $export['metavox'] = [
                'version' => $this->metaVoxVersion,
                'fieldDefinitions' => $this->getMetaVoxFieldDefinitions()
            ];

            // Collect all file IDs for batch retrieval
            $fileIds = $this->collectPageFileIds($pages);

            // Batch retrieve metadata for all pages
            $metadataByFileId = $this->retrieveMetadataForPages($fileIds);
        }

        foreach ($pages as $page) {
            $uniqueId = $page['uniqueId'] ?? '';
            if (empty($uniqueId)) {
                continue;
            }

            // Remove internal export properties before adding to content
            $pageContent = $page;
            unset($pageContent['_fileId']);
            unset($pageContent['_exportPath']);

            $data = [
                'uniqueId' => $uniqueId,
                'title' => $page['title'] ?? '',
                'content' => $pageContent,
            ];

            // Add MetaVox metadata if available
            if (isset($metadataByFileId) && isset($page['_fileId'])) {
                $fileId = $page['_fileId'];
                if (isset($metadataByFileId[$fileId])) {
                    $data['metadata'] = $metadataByFileId[$fileId];
                    $this->logger->debug('Added metadata for page', [
                        'uniqueId' => $uniqueId,
                        'fileId' => $fileId,
                        'metadataCount' => count($metadataByFileId[$fileId])
                    ]);
                }
            }

            if ($includeComments) {
                $data['comments'] = $this->exportComments($uniqueId);
                $data['pageReactions'] = $this->exportPageReactions($uniqueId);
            }

            $export['pages'][] = $data;
        }

        $this->logger->info('Export complete', [
            'pages' => count($export['pages']),
            'metavoxEnabled' => isset($export['metavox'])
        ]);

        return $export;
    }

    /**
     * Get all pages from a specific language folder
     *
     * @param string $language Language code
     * @return array Pages with full content
     */
    private function getPagesFromLanguageFolder(string $language): array {
        $pages = [];

        try {
            $intraVoxFolder = $this->setupService->getSharedFolder();

            if (!$intraVoxFolder->nodeExists($language)) {
                $this->logger->info('Language folder does not exist: ' . $language);
                return [];
            }

            $langFolder = $intraVoxFolder->get($language);
            if (!($langFolder instanceof Folder)) {
                return [];
            }

            // Check for home.json in root
            if ($langFolder->nodeExists('home.json')) {
                $homeFile = $langFolder->get('home.json');
                if ($homeFile instanceof File) {
                    $content = $homeFile->getContent();
                    $data = json_decode($content, true);
                    if ($data && isset($data['uniqueId'])) {
                        // Mark as home page for import
                        $data['_exportPath'] = 'home';
                        // Add file ID for MetaVox metadata retrieval
                        $data['_fileId'] = $homeFile->getId();
                        $pages[] = $data;
                    }
                }
            }

            // Recursively find all pages
            $this->findPagesInFolderRecursive($langFolder, $pages);

        } catch (\Exception $e) {
            $this->logger->error('Failed to get pages from language folder: ' . $e->getMessage());
        }

        return $pages;
    }

    /**
     * Recursively find pages in folders
     *
     * @param Folder $folder Folder to search
     * @param array &$pages Pages array to add to
     * @param string $relativePath Current relative path from language root
     */
    private function findPagesInFolderRecursive(Folder $folder, array &$pages, string $relativePath = ''): void {
        foreach ($folder->getDirectoryListing() as $node) {
            if ($node instanceof Folder) {
                $nodeName = $node->getName();

                // Skip _media and _resources folders
                if ($nodeName === '_media' || $nodeName === '_resources') {
                    continue;
                }

                $currentPath = $relativePath ? $relativePath . '/' . $nodeName : $nodeName;

                // Check for {folderId}.json in this folder (IntraVox page structure)
                $pageFileName = $nodeName . '.json';
                if ($node->nodeExists($pageFileName)) {
                    $pageFile = $node->get($pageFileName);
                    if ($pageFile instanceof File) {
                        $content = $pageFile->getContent();
                        $data = json_decode($content, true);
                        if ($data && isset($data['uniqueId'])) {
                            // Add the folder path for import to recreate structure
                            $data['_exportPath'] = $currentPath;
                            // Add file ID for MetaVox metadata retrieval
                            $data['_fileId'] = $pageFile->getId();
                            $pages[] = $data;
                        }
                    }
                }

                // Recursively search subfolders
                $this->findPagesInFolderRecursive($node, $pages, $currentPath);
            }
        }
    }

    /**
     * Export single page with optional comments/reactions
     *
     * @param string $uniqueId Page unique ID
     * @param bool $includeComments Include comments and reactions
     * @return array|null Page data or null if not found
     */
    public function exportPage(string $uniqueId, bool $includeComments = true): ?array {
        try {
            // Search for page in all language folders
            $page = $this->findPageByUniqueIdInAllLanguages($uniqueId);
            if (!$page) {
                return null;
            }

            $data = [
                'uniqueId' => $uniqueId,
                'title' => $page['title'] ?? '',
                'content' => $page,
            ];

            if ($includeComments) {
                $data['comments'] = $this->exportComments($uniqueId);
                $data['pageReactions'] = $this->exportPageReactions($uniqueId);
            }

            return $data;
        } catch (\Exception $e) {
            $this->logger->error('Failed to export page ' . $uniqueId . ': ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Find a page by uniqueId across all language folders
     *
     * @param string $uniqueId Page unique ID
     * @return array|null Page data or null if not found
     */
    private function findPageByUniqueIdInAllLanguages(string $uniqueId): ?array {
        $languages = ['nl', 'en', 'de', 'fr'];

        foreach ($languages as $lang) {
            $pages = $this->getPagesFromLanguageFolder($lang);
            foreach ($pages as $page) {
                if (($page['uniqueId'] ?? '') === $uniqueId) {
                    return $page;
                }
            }
        }

        return null;
    }

    /**
     * Export comments for a page
     *
     * @param string $uniqueId Page unique ID
     * @return array Comments data
     */
    private function exportComments(string $uniqueId): array {
        try {
            return $this->commentService->getComments($uniqueId);
        } catch (\Exception $e) {
            $this->logger->warning('Failed to export comments for ' . $uniqueId . ': ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Export page-level reactions
     *
     * @param string $uniqueId Page unique ID
     * @return array Reactions data
     */
    private function exportPageReactions(string $uniqueId): array {
        try {
            return $this->commentService->getPageReactions($uniqueId);
        } catch (\Exception $e) {
            $this->logger->warning('Failed to export reactions for ' . $uniqueId . ': ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Get available languages that have content
     *
     * @return array List of languages with content
     */
    public function getExportableLanguages(): array {
        $languages = [
            ['code' => 'nl', 'name' => 'Nederlands', 'flag' => '🇳🇱'],
            ['code' => 'en', 'name' => 'English', 'flag' => '🇬🇧'],
            ['code' => 'de', 'name' => 'Deutsch', 'flag' => '🇩🇪'],
            ['code' => 'fr', 'name' => 'Français', 'flag' => '🇫🇷'],
        ];

        // Check which languages have content using our own method
        $result = [];
        foreach ($languages as $lang) {
            $pages = $this->getPagesFromLanguageFolder($lang['code']);
            $lang['hasContent'] = count($pages) > 0;
            $lang['pageCount'] = count($pages);
            $result[] = $lang;
        }

        return $result;
    }

    /**
     * Export language as ZIP with media files
     *
     * @param string $language Language code
     * @param bool $includeComments Include comments and reactions
     * @return string Path to ZIP file
     */
    public function exportLanguageAsZip(string $language, bool $includeComments = true): string {
        $this->logger->info('Starting ZIP export for language: ' . $language);

        // 1. Create temporary directory
        $tempDir = $this->tempManager->getTemporaryFolder();

        // 2. Get export data
        $exportData = $this->exportLanguage($language, $includeComments);

        // 3. Write export.json
        file_put_contents(
            $tempDir . '/export.json',
            json_encode($exportData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
        );

        // 4. Copy media files
        $this->copyMediaFiles($language, $tempDir);

        // 5. Create ZIP
        $zipPath = $tempDir . '/export.zip';
        $this->createZip($tempDir, $zipPath);

        $this->logger->info('ZIP export complete: ' . $zipPath);

        return $zipPath;
    }

    /**
     * Copy media files from IntraVox folder to temp directory
     *
     * @param string $language Language code
     * @param string $tempDir Temporary directory path
     */
    private function copyMediaFiles(string $language, string $tempDir): void {
        try {
            $intraVoxFolder = $this->setupService->getSharedFolder();

            if (!$intraVoxFolder->nodeExists($language)) {
                $this->logger->info('Language folder does not exist: ' . $language);
                return;
            }

            $langFolder = $intraVoxFolder->get($language);
            if (!($langFolder instanceof Folder)) {
                return;
            }

            $this->copyMediaRecursive($langFolder, $tempDir . '/' . $language);
            $this->logger->info('Copied media files for language: ' . $language);
        } catch (\Exception $e) {
            $this->logger->warning('Failed to copy media files: ' . $e->getMessage());
        }
    }

    /**
     * Recursively copy _media and _resources folders
     *
     * @param Folder $folder Source folder
     * @param string $targetPath Target path
     */
    private function copyMediaRecursive(Folder $folder, string $targetPath): void {
        foreach ($folder->getDirectoryListing() as $node) {
            $nodeName = $node->getName();

            if (($nodeName === '_media' || $nodeName === '_resources') && $node instanceof Folder) {
                // Copy entire _media or _resources folder (including subfolders)
                $mediaTargetPath = $targetPath . '/' . $nodeName;
                $this->copyMediaFolderRecursive($node, $mediaTargetPath);
            } elseif ($node instanceof Folder && $nodeName !== '_media' && $nodeName !== '_resources') {
                // Recursively process subfolders
                $this->copyMediaRecursive($node, $targetPath . '/' . $nodeName);
            }
        }
    }

    /**
     * Copy a media folder recursively (supports nested folders in _resources)
     *
     * @param Folder $sourceFolder Source folder node
     * @param string $targetPath Target path
     */
    private function copyMediaFolderRecursive(Folder $sourceFolder, string $targetPath): void {
        if (!is_dir($targetPath)) {
            mkdir($targetPath, 0755, true);
        }

        foreach ($sourceFolder->getDirectoryListing() as $item) {
            if ($item instanceof File) {
                try {
                    file_put_contents(
                        $targetPath . '/' . $item->getName(),
                        $item->getContent()
                    );
                } catch (\Exception $e) {
                    $this->logger->warning('Failed to copy file: ' . $item->getName());
                }
            } elseif ($item instanceof Folder) {
                // Recursively copy subfolder (for _resources with nested folders)
                $this->copyMediaFolderRecursive($item, $targetPath . '/' . $item->getName());
            }
        }
    }

    /**
     * Create ZIP archive from directory
     *
     * @param string $sourceDir Source directory
     * @param string $zipPath Target ZIP path
     */
    private function createZip(string $sourceDir, string $zipPath): void {
        $zip = new \ZipArchive();
        if ($zip->open($zipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
            throw new \Exception('Failed to create ZIP archive');
        }

        // Normalize sourceDir - remove trailing slash if present
        $sourceDir = rtrim($sourceDir, '/');

        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($sourceDir, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::LEAVES_ONLY
        );

        foreach ($files as $file) {
            if (!$file->isDir()) {
                $filePath = $file->getRealPath();
                $relativePath = substr($filePath, strlen($sourceDir) + 1);

                // Skip the zip itself
                if ($relativePath !== 'export.zip') {
                    $zip->addFile($filePath, $relativePath);
                }
            }
        }

        $zip->close();
    }

    /**
     * Check if MetaVox is installed and available
     *
     * @return bool True if MetaVox is available
     */
    private function isMetaVoxAvailable(): bool {
        if ($this->metaVoxAvailable === null) {
            try {
                $this->metaVoxAvailable =
                    $this->appManager->isInstalled('metavox') &&
                    $this->appManager->isEnabledForUser('metavox');

                if ($this->metaVoxAvailable) {
                    // Get MetaVox version
                    $this->metaVoxVersion = $this->appManager->getAppVersion('metavox');

                    // Get FieldService instance
                    try {
                        $this->metaVoxFieldService = \OC::$server->get(\OCA\MetaVox\Service\FieldService::class);
                    } catch (\Exception $e) {
                        $this->logger->warning('MetaVox FieldService not available: ' . $e->getMessage());
                        $this->metaVoxAvailable = false;
                        return false;
                    }

                    $this->logger->info('MetaVox detected for export', [
                        'version' => $this->metaVoxVersion,
                        'serviceAvailable' => $this->metaVoxFieldService !== null
                    ]);
                }
            } catch (\Exception $e) {
                $this->logger->warning('MetaVox detection failed: ' . $e->getMessage());
                $this->metaVoxAvailable = false;
            }
        }

        return $this->metaVoxAvailable;
    }

    /**
     * Get MetaVox field definitions
     *
     * @return array Field definitions with schema information
     */
    private function getMetaVoxFieldDefinitions(): array {
        if (!$this->isMetaVoxAvailable() || !$this->metaVoxFieldService) {
            return [];
        }

        try {
            $fields = $this->metaVoxFieldService->getAllFields();

            // Transform to export format
            return array_map(function($field) {
                return [
                    'field_name' => $field['field_name'],
                    'field_label' => $field['field_label'],
                    'field_type' => $field['field_type'],
                    'field_description' => $field['field_description'] ?? '',
                    'field_options' => $field['field_options'] ?? [],
                    'is_required' => $field['is_required'],
                    'sort_order' => $field['sort_order'],
                    'scope' => $field['scope'] ?? 'groupfolder',
                ];
            }, $fields);
        } catch (\Exception $e) {
            $this->logger->error('Failed to get MetaVox field definitions: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Collect file IDs from pages for batch metadata retrieval
     *
     * @param array $pages Array of pages
     * @return array Array of file IDs
     */
    private function collectPageFileIds(array $pages): array {
        $fileIds = [];

        foreach ($pages as $page) {
            if (isset($page['_fileId'])) {
                $fileIds[] = $page['_fileId'];
            }
        }

        return $fileIds;
    }

    /**
     * Retrieve metadata for multiple pages using batch API
     *
     * @param array $fileIds Array of file IDs
     * @return array Metadata indexed by file ID
     */
    private function retrieveMetadataForPages(array $fileIds): array {
        if (empty($fileIds) || !$this->isMetaVoxAvailable() || !$this->metaVoxFieldService) {
            $this->logger->debug('Skipping metadata retrieval', [
                'emptyFileIds' => empty($fileIds),
                'metaVoxAvailable' => $this->isMetaVoxAvailable(),
                'fieldServiceAvailable' => $this->metaVoxFieldService !== null
            ]);
            return [];
        }

        try {
            $this->logger->info('Retrieving metadata for pages', [
                'fileIdCount' => count($fileIds),
                'fileIds' => $fileIds
            ]);

            // Use bulk retrieval for performance
            $bulkMetadata = $this->metaVoxFieldService->getBulkFileMetadata($fileIds);

            $this->logger->info('Bulk metadata retrieved', [
                'resultCount' => count($bulkMetadata)
            ]);

            // Transform to simplified export format
            $result = [];
            foreach ($bulkMetadata as $fileId => $fields) {
                $filteredFields = array_filter($fields, function($field) {
                    // Only export fields with values
                    return !empty($field['value']);
                });

                $result[$fileId] = array_map(function($field) {
                    return [
                        'field_name' => $field['field_name'],
                        'value' => $field['value']
                    ];
                }, $filteredFields);

                $this->logger->debug('Processed metadata for file', [
                    'fileId' => $fileId,
                    'totalFields' => count($fields),
                    'filteredFields' => count($filteredFields)
                ]);
            }

            $this->logger->info('Metadata processing complete', [
                'filesWithMetadata' => count($result)
            ]);

            return $result;

        } catch (\Exception $e) {
            $this->logger->error('Failed to retrieve metadata: ' . $e->getMessage(), [
                'exception' => get_class($e),
                'trace' => $e->getTraceAsString()
            ]);
            return [];
        }
    }
}
