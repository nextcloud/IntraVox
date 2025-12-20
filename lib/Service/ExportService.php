<?php
declare(strict_types=1);

namespace OCA\IntraVox\Service;

use OCP\App\IAppManager;
use OCP\Files\File;
use OCP\Files\Folder;
use OCP\IDBConnection;
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
        private IAppManager $appManager,
        private IDBConnection $connection
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
            'exportVersion' => '1.3',  // uniqueId-based metadata mapping (v0.9.0)
            'schemaVersion' => '1.3',
            'exportDate' => (new \DateTime())->format('c'),
            'language' => $language,
            'exportedBy' => 'IntraVox/' . $this->getAppVersion(),
            'requiresMinVersion' => '0.8.11',
            'navigation' => $this->navigationService->getNavigation($language),
            'footer' => $this->footerService->getFooter($language),
            'pages' => []
        ];

        // Add MetaVox field definitions if available
        if ($this->isMetaVoxAvailable()) {
            $export['metavox'] = [
                'version' => $this->metaVoxVersion,
                'fieldDefinitions' => $this->getMetaVoxFieldDefinitions()
            ];

            // Attach metadata directly to each page (v1.3 format)
            try {
                $groupfolderId = $this->setupService->getGroupFolderId();
                $pages = $this->attachMetadataToPages($pages, $groupfolderId);
            } catch (\Exception $e) {
                $this->logger->error('Failed to attach metadata to pages: ' . $e->getMessage());
                // Continue export without metadata (graceful degradation)
            }
        }

        foreach ($pages as $page) {
            $uniqueId = $page['uniqueId'] ?? '';
            if (empty($uniqueId)) {
                continue;
            }

            // Extract export path for top-level inclusion
            $exportPath = $page['_exportPath'] ?? null;

            // Extract metadata if attached
            $metadata = $page['metadata'] ?? null;

            // Remove internal properties before adding to content
            $pageContent = $page;
            unset($pageContent['_fileId']);
            unset($pageContent['_folderFileId']);
            unset($pageContent['_exportPath']);
            unset($pageContent['metadata']); // Remove from content since we add it at top level

            $data = [
                'uniqueId' => $uniqueId,
                'title' => $page['title'] ?? '',
                'content' => $pageContent,
                '_exportPath' => $exportPath, // Needed for import to recreate folder structure
            ];

            // Add metadata if present (nested directly in page object)
            if (!empty($metadata)) {
                $data['metadata'] = $metadata;
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
                        // Add folder ID for team folder metadata (language folder itself)
                        $data['_folderFileId'] = $langFolder->getId();
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
                            // Add folder ID for team folder metadata retrieval
                            $data['_folderFileId'] = $node->getId();
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
     * Get Files storage file ID from groupfolder file ID
     *
     * MetaVox stores metadata using file IDs from user's Files view (storage type different from groupfolder mount).
     * This method maps a groupfolder file ID to its corresponding Files storage file ID.
     *
     * @param int $groupfolderFileId File ID from groupfolder mount
     * @param int $groupfolderId Groupfolder ID
     * @return int|null Files storage file ID, or null if not found
     */
    private function getFilesStorageFileId(int $groupfolderFileId, int $groupfolderId): ?int {
        try {
            // Get the file path from the groupfolder file ID
            $qb = $this->connection->getQueryBuilder();
            $qb->select('path')
               ->from('filecache')
               ->where($qb->expr()->eq('fileid', $qb->createNamedParameter($groupfolderFileId)));

            $result = $qb->executeQuery();
            $row = $result->fetch();
            $result->closeCursor();

            if (!$row || empty($row['path'])) {
                return null;
            }

            $groupfolderPath = $row['path'];

            // Extract the relative path within the groupfolder
            // Pattern: __groupfolders/{id}/files/{language}/...
            // We need: files/{language}/...
            $pattern = '__groupfolders/' . $groupfolderId . '/';
            if (strpos($groupfolderPath, $pattern) !== 0) {
                $this->logger->debug('Path does not match groupfolder pattern', [
                    'path' => $groupfolderPath,
                    'pattern' => $pattern
                ]);
                return null;
            }

            // Get the relative path after __groupfolders/{id}/
            $relativePath = substr($groupfolderPath, strlen($pattern));

            // Now find the file ID in Files storage with this relative path
            $qb2 = $this->connection->getQueryBuilder();
            $qb2->select('fileid')
                ->from('filecache')
                ->where($qb2->expr()->eq('path', $qb2->createNamedParameter($relativePath)));

            $result2 = $qb2->executeQuery();
            $row2 = $result2->fetch();
            $result2->closeCursor();

            if ($row2 && isset($row2['fileid'])) {
                $this->logger->debug('Mapped groupfolder file ID to Files storage ID', [
                    'groupfolderFileId' => $groupfolderFileId,
                    'groupfolderPath' => $groupfolderPath,
                    'relativePath' => $relativePath,
                    'filesStorageFileId' => $row2['fileid']
                ]);
                return (int)$row2['fileid'];
            }

            return null;

        } catch (\Exception $e) {
            $this->logger->error('Failed to map groupfolder file ID to Files storage ID', [
                'groupfolderFileId' => $groupfolderFileId,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Attach metadata directly to each page object (v1.3 export format)
     * Uses uniqueId mapping instead of file_id for cross-system compatibility
     *
     * @param array $pages Array of pages with _fileId and _folderFileId properties
     * @param int $groupfolderId Groupfolder ID for metadata scoping
     * @return array Pages with metadata attached
     */
    private function attachMetadataToPages(array $pages, int $groupfolderId): array {
        if (!$this->isMetaVoxAvailable() || !$this->metaVoxFieldService) {
            return $pages;
        }

        if ($groupfolderId <= 0) {
            $this->logger->warning('Invalid groupfolder ID for metadata attachment', [
                'groupfolderId' => $groupfolderId
            ]);
            return $pages;
        }

        $this->logger->info('Attaching metadata to pages', [
            'pageCount' => count($pages),
            'groupfolderId' => $groupfolderId
        ]);

        $successCount = 0;

        foreach ($pages as &$page) {
            $groupfolderFileId = $page['_fileId'] ?? null;
            $groupfolderFolderFileId = $page['_folderFileId'] ?? null;
            $uniqueId = $page['uniqueId'] ?? 'unknown';

            if (!$groupfolderFileId) {
                continue;
            }

            try {
                // Map groupfolder file IDs to Files storage file IDs
                // (MetaVox stores metadata using Files storage IDs, not groupfolder IDs)
                $fileId = $this->getFilesStorageFileId($groupfolderFileId, $groupfolderId);
                if (!$fileId) {
                    $this->logger->warning('Could not map groupfolder file ID to Files storage ID', [
                        'uniqueId' => $uniqueId,
                        'groupfolderFileId' => $groupfolderFileId
                    ]);
                    continue;
                }

                $folderFileId = null;
                if ($groupfolderFolderFileId) {
                    $folderFileId = $this->getFilesStorageFileId($groupfolderFolderFileId, $groupfolderId);
                }

                // Get file metadata (attached to the JSON file itself)
                $fileMetadata = $this->metaVoxFieldService->getGroupfolderFileMetadata(
                    $groupfolderId,
                    $fileId
                );

                // Get folder metadata (attached to the containing folder)
                $folderMetadata = [];
                if ($folderFileId) {
                    try {
                        $folderMetadata = $this->metaVoxFieldService->getGroupfolderFileMetadata(
                            $groupfolderId,
                            $folderFileId
                        );
                    } catch (\Exception $e) {
                        $this->logger->debug('Failed to get folder metadata', [
                            'uniqueId' => $uniqueId,
                            'folderFileId' => $folderFileId,
                            'error' => $e->getMessage()
                        ]);
                    }
                }

                // Separate file vs folder metadata
                $fileFields = [];
                $folderFields = [];

                foreach ($fileMetadata as $field) {
                    if (!empty($field['value'])) {
                        if ($field['applies_to_groupfolder'] == 0) {
                            // File-level metadata
                            $fileFields[] = [
                                'field_name' => $field['field_name'],
                                'field_label' => $field['field_label'],
                                'field_type' => $field['field_type'],
                                'value' => $field['value']
                            ];
                        } else {
                            // Folder metadata (from file's own call)
                            $folderFields[] = [
                                'field_name' => $field['field_name'],
                                'field_label' => $field['field_label'],
                                'field_type' => $field['field_type'],
                                'value' => $field['value']
                            ];
                        }
                    }
                }

                // Add folder-level metadata from folder call
                foreach ($folderMetadata as $field) {
                    if (!empty($field['value']) && $field['applies_to_groupfolder'] == 1) {
                        $folderFields[] = [
                            'field_name' => $field['field_name'],
                            'field_label' => $field['field_label'],
                            'field_type' => $field['field_type'],
                            'value' => $field['value']
                        ];
                    }
                }

                // Attach metadata to page if any exists
                if (!empty($fileFields) || !empty($folderFields)) {
                    $page['metadata'] = [
                        'file' => $fileFields,
                        'folder' => $folderFields
                    ];

                    $successCount++;

                    $this->logger->debug('Attached metadata to page', [
                        'uniqueId' => $uniqueId,
                        'fileFieldCount' => count($fileFields),
                        'folderFieldCount' => count($folderFields)
                    ]);
                }

            } catch (\Exception $e) {
                $this->logger->warning('Failed to get metadata for page', [
                    'uniqueId' => $uniqueId,
                    'fileId' => $fileId,
                    'error' => $e->getMessage()
                ]);
            }
        }

        $this->logger->info('Metadata attachment complete', [
            'totalPages' => count($pages),
            'pagesWithMetadata' => $successCount
        ]);

        return $pages;
    }

    /**
     * Collect file IDs from pages for batch metadata retrieval
     *
     * @param array $pages Array of pages
     * @return array Array of file IDs
     * @deprecated Use attachMetadataToPages() instead (v0.9.0+)
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
     * Retrieve metadata for multiple pages
     *
     * NOTE: Uses individual API calls instead of bulk because MetaVox's getBulkFileMetadata()
     * doesn't accept groupfolder_id parameter, which is REQUIRED by the database schema.
     *
     * @param array $fileIds Array of file IDs
     * @param int $groupfolderId Groupfolder ID for metadata scoping
     * @return array Metadata indexed by file ID
     */
    private function retrieveMetadataForPages(array $fileIds, int $groupfolderId): array {
        if (empty($fileIds) || !$this->isMetaVoxAvailable() || !$this->metaVoxFieldService) {
            $this->logger->debug('Skipping metadata retrieval', [
                'emptyFileIds' => empty($fileIds),
                'metaVoxAvailable' => $this->isMetaVoxAvailable(),
                'fieldServiceAvailable' => $this->metaVoxFieldService !== null
            ]);
            return [];
        }

        // Validate groupfolder ID
        if ($groupfolderId <= 0) {
            $this->logger->warning('Invalid groupfolder ID for metadata retrieval', [
                'groupfolderId' => $groupfolderId
            ]);
            return [];
        }

        try {
            $this->logger->info('Retrieving metadata for pages', [
                'fileIdCount' => count($fileIds),
                'groupfolderId' => $groupfolderId,
                'fileIds' => $fileIds
            ]);

            // NOTE: We must use individual calls instead of bulk API because
            // MetaVox's getBulkFileMetadata() doesn't accept groupfolder_id parameter.
            // The database schema requires BOTH file_id AND groupfolder_id to query
            // the metavox_file_gf_meta table.

            $result = [];
            $successCount = 0;
            $failureCount = 0;

            foreach ($fileIds as $fileId) {
                try {
                    // Use getGroupfolderFileMetadata which accepts both parameters
                    $fileMetadata = $this->metaVoxFieldService->getGroupfolderFileMetadata(
                        $groupfolderId,
                        $fileId
                    );

                    // DEBUG: Log RAW API response
                    $this->logger->info('MetaVox API Response for file', [
                        'fileId' => $fileId,
                        'groupfolderId' => $groupfolderId,
                        'rawResponse' => $fileMetadata,
                        'responseCount' => count($fileMetadata)
                    ]);

                    // Filter fields with values only
                    $filteredFields = array_filter($fileMetadata, function($field) {
                        return !empty($field['value']);
                    });

                    $this->logger->info('Filtered metadata', [
                        'fileId' => $fileId,
                        'totalFields' => count($fileMetadata),
                        'fieldsWithValues' => count($filteredFields),
                        'filteredFields' => $filteredFields
                    ]);

                    if (!empty($filteredFields)) {
                        // Transform to simplified export format
                        $result[$fileId] = array_map(function($field) {
                            return [
                                'field_name' => $field['field_name'],
                                'value' => $field['value']
                            ];
                        }, array_values($filteredFields));

                        $successCount++;

                        $this->logger->debug('Retrieved metadata for file', [
                            'fileId' => $fileId,
                            'totalFields' => count($fileMetadata),
                            'fieldsWithValues' => count($filteredFields)
                        ]);
                    } else {
                        $this->logger->info('No metadata with values found for file', [
                            'fileId' => $fileId,
                            'groupfolderId' => $groupfolderId
                        ]);
                    }
                } catch (\Exception $e) {
                    $failureCount++;
                    $this->logger->warning('Failed to retrieve metadata for file', [
                        'fileId' => $fileId,
                        'groupfolderId' => $groupfolderId,
                        'error' => $e->getMessage()
                    ]);
                    // Continue processing other files
                }
            }

            $this->logger->info('Metadata retrieval complete', [
                'totalFiles' => count($fileIds),
                'filesWithMetadata' => $successCount,
                'failedFiles' => $failureCount
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

    /**
     * Retrieve team folder metadata for all folders in a language
     * Team folder metadata is attached to folders themselves, not files
     *
     * @param string $language Language code
     * @param array $pages Array of pages with their folder information
     * @return array Metadata indexed by folder path
     */
    private function retrieveTeamFolderMetadata(string $language, array $pages): array {
        if (!$this->isMetaVoxAvailable() || !$this->metaVoxFieldService) {
            return [];
        }

        try {
            $groupfolderId = $this->setupService->getGroupFolderId();
            if ($groupfolderId <= 0) {
                return [];
            }

            $this->logger->info('Retrieving team folder metadata', [
                'language' => $language,
                'groupfolderId' => $groupfolderId
            ]);

            // Get root groupfolder metadata for home page
            $groupfolderMetadata = $this->metaVoxFieldService->getGroupfolderMetadata($groupfolderId);

            $result = [];

            // Add root metadata for home page
            if (!empty($groupfolderMetadata)) {
                $filteredMetadata = array_filter($groupfolderMetadata, function($field) {
                    return !empty($field['value']);
                });

                if (!empty($filteredMetadata)) {
                    $result['home'] = array_map(function($field) {
                        return [
                            'field_name' => $field['field_name'],
                            'value' => $field['value']
                        ];
                    }, array_values($filteredMetadata));

                    $this->logger->debug('Root team folder metadata retrieved', [
                        'metadataCount' => count($result['home'])
                    ]);
                }
            }

            // Collect unique folder paths and their folder IDs
            $folderIds = [];
            $folderPaths = [];

            foreach ($pages as $page) {
                if (isset($page['_folderFileId']) && !empty($page['_exportPath'])) {
                    $exportPath = $page['_exportPath'];
                    if (!isset($folderPaths[$exportPath])) {
                        $folderPaths[$exportPath] = $page['_folderFileId'];
                        $folderIds[] = $page['_folderFileId'];
                    }
                }
            }

            if (empty($folderIds)) {
                $this->logger->info('No subfolder metadata to retrieve');
                return $result;
            }

            // Retrieve metadata for all folders using bulk API
            $this->logger->debug('Retrieving metadata for subfolders', [
                'folderCount' => count($folderIds),
                'folderIds' => $folderIds
            ]);

            $bulkFolderMetadata = $this->metaVoxFieldService->getBulkFileMetadata($folderIds);

            // Map folder metadata to export paths
            foreach ($folderPaths as $exportPath => $folderId) {
                if (isset($bulkFolderMetadata[$folderId]) && !empty($bulkFolderMetadata[$folderId])) {
                    $filteredFields = array_filter($bulkFolderMetadata[$folderId], function($field) {
                        return !empty($field['value']);
                    });

                    if (!empty($filteredFields)) {
                        $result[$exportPath] = array_map(function($field) {
                            return [
                                'field_name' => $field['field_name'],
                                'value' => $field['value']
                            ];
                        }, array_values($filteredFields));

                        $this->logger->debug('Subfolder team folder metadata retrieved', [
                            'exportPath' => $exportPath,
                            'folderId' => $folderId,
                            'metadataCount' => count($result[$exportPath])
                        ]);
                    }
                }
            }

            $this->logger->info('Team folder metadata retrieval complete', [
                'foldersWithMetadata' => count($result)
            ]);

            return $result;

        } catch (\Exception $e) {
            $this->logger->error('Failed to retrieve team folder metadata: ' . $e->getMessage(), [
                'exception' => get_class($e),
                'trace' => $e->getTraceAsString()
            ]);
            return [];
        }
    }

    /**
     * Get IntraVox app version
     *
     * @return string App version
     */
    private function getAppVersion(): string {
        try {
            return $this->appManager->getAppVersion('intravox');
        } catch (\Exception $e) {
            return 'unknown';
        }
    }
}
