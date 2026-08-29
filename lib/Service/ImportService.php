<?php
declare(strict_types=1);

namespace OCA\IntraVox\Service;

use OCA\IntraVox\Exception\InvalidImportException;
use OCA\IntraVox\Exception\PageNotFoundException;
use OCP\Files\File;
use OCP\Files\Folder;
use OCP\ITempManager;
use OCP\IDBConnection;
use Psr\Log\LoggerInterface;
use OCA\IntraVox\Service\Path\PagePathHelper;
use OCA\IntraVox\Service\Import\ImportNavigationBuilder;
use OCA\IntraVox\Service\Import\SafeZipExtractor;
use OCA\IntraVox\Service\Sanitize\PageShapeSanitizer;

/**
 * Service for importing IntraVox pages and media from ZIP exports
 */
class ImportService {
    private const LOG_PREFIX = '[ImportService]';

    public function __construct(
        private PageService $pageService,
        private SetupService $setupService,
        private CommentService $commentService,
        private NavigationService $navigationService,
        private FooterService $footerService,
        private ITempManager $tempManager,
        private LoggerInterface $logger,
        private MetaVoxImportService $metaVoxImportService,
        private IDBConnection $connection,
        private PageIndexService $pageIndexService,
        private PageShapeSanitizer $shapeSanitizer,
        private SafeZipExtractor $zipExtractor,
        private ImportNavigationBuilder $navigationBuilder
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

        $this->logger->info(self::LOG_PREFIX . ' Starting import from ZIP');

        // 1. Write ZIP to temp
        file_put_contents($zipPath, $zipContent);

        // 2. Extract ZIP safely (prevent ZIP Slip vulnerability)
        $zip = new \ZipArchive();
        if ($zip->open($zipPath) !== true) {
            throw new InvalidImportException(
                InvalidImportException::CODE_INVALID_ZIP,
                'Invalid ZIP file — the upload could not be opened as a ZIP archive.'
            );
        }
        $this->safeExtractZip($zip, $tempDir);
        $zip->close();

        // 3. Read export.json
        $exportJsonPath = $tempDir . '/export.json';
        if (!file_exists($exportJsonPath)) {
            throw new InvalidImportException(
                InvalidImportException::CODE_MISSING_EXPORT_JSON,
                'No export.json found in the ZIP. Make sure you uploaded an IntraVox export ' .
                '(Settings → Export), not a Nextcloud Files backup or a different app\'s archive.'
            );
        }
        $exportData = json_decode(file_get_contents($exportJsonPath), true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new InvalidImportException(
                InvalidImportException::CODE_INVALID_JSON,
                'export.json is not valid JSON: ' . json_last_error_msg(),
                ['detail' => json_last_error_msg()]
            );
        }

        // 4. Validate export version
        $version = $exportData['exportVersion'] ?? '0';
        if (version_compare($version, '1.0', '<')) {
            throw new InvalidImportException(
                InvalidImportException::CODE_UNSUPPORTED_VERSION,
                'Unsupported export version: ' . $version . '. Please re-export from a recent IntraVox install.',
                ['version' => $version]
            );
        }

        // 5. Detect if this is a Confluence import (has confluenceOrder metadata)
        $pages = $exportData['pages'] ?? [];
        $isConfluenceImport = false;
        foreach ($pages as $page) {
            if (isset($page['content']['metadata']['confluenceOrder']) ||
                isset($page['content']['metadata']['confluenceId'])) {
                $isConfluenceImport = true;
                break;
            }
        }

        // 6. Validate export completeness (v1.2 structural fix requirement)
        // For exports older than v1.2, check that all pages have _exportPath
        // Skip this validation for Confluence imports (they don't have _exportPath)
        if (!$isConfluenceImport && version_compare($version, '1.2', '<')) {
            $pagesWithoutPath = array_filter($pages, function($page) {
                // Check both top-level and content for _exportPath (different export versions)
                return empty($page['_exportPath']) && empty($page['content']['_exportPath']);
            });

            if (count($pagesWithoutPath) > 0) {
                $this->logger->error('Incomplete export detected', [
                    'exportVersion' => $version,
                    'totalPages' => count($pages),
                    'pagesWithoutPath' => count($pagesWithoutPath)
                ]);
                throw new InvalidImportException(
                    InvalidImportException::CODE_INCOMPLETE_EXPORT,
                    'Export is incomplete — ' . count($pagesWithoutPath) . ' pages are missing _exportPath. ' .
                    'Please re-export from the source system with IntraVox v0.8.11 or higher.',
                    ['missingCount' => count($pagesWithoutPath)]
                );
            }

        }

        if ($isConfluenceImport) {
            $this->logger->info(self::LOG_PREFIX . ' Confluence import detected', ['pages' => count($pages)]);
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

            // Create missing fields if auto-create enabled
            if ($autoCreateMetaVoxFields && !empty($metaVoxFieldValidation['fieldsToCreate'])) {
                $this->metaVoxImportService->createFieldDefinitions(
                    $metaVoxFieldValidation['fieldsToCreate'],
                    true
                );
            }
        }

        $stats = [
            'pagesImported' => 0,
            'pagesSkipped' => 0,
            'mediaFilesImported' => 0,
            'commentsImported' => 0,
            'navigationImported' => 0,  // 1 = imported, 0 = not imported (consistent int type)
            'footerImported' => 0,      // 1 = imported, 0 = not imported (consistent int type)
            'metavoxEnabled' => $hasMetaVoxData,
            'metavoxFieldsImported' => 0,
            'metavoxFieldsSkipped' => 0,
            'metavoxFieldsFailed' => 0,
        ];

        // 5. Import navigation
        if (!empty($exportData['navigation'])) {
            try {
                $this->navigationService->saveNavigation($exportData['navigation'], $language);
                $stats['navigationImported'] = 1;
            } catch (\Exception $e) {
                $this->logger->warning(self::LOG_PREFIX . ' Failed to import navigation: ' . $e->getMessage());
            }
        }

        // 6. Import footer
        if (!empty($exportData['footer'])) {
            try {
                $footerContent = $exportData['footer']['content'] ?? '';
                if (!empty($footerContent)) {
                    $this->footerService->saveFooter($footerContent);
                    $stats['footerImported'] = 1;
                }
            } catch (\Exception $e) {
                $this->logger->warning(self::LOG_PREFIX . ' Failed to import footer: ' . $e->getMessage());
            }
        }

        // 7. Sort pages by parent relationships (parents before children)
        $sortedPages = $this->navigationBuilder->sortPagesByHierarchy($exportData['pages'] ?? []);

        // Track imported pages by uniqueId to resolve parent paths
        $importedPages = [];

        // If parent page ID is provided, find its path and add to importedPages
        if ($parentPageId) {
            try {
                $parentPath = $this->findPagePath($parentPageId, $language);
                if ($parentPath) {
                    $importedPages[$parentPageId] = $parentPath;
                }
            } catch (\Exception $e) {
                $this->logger->warning('Failed to find parent page: ' . $e->getMessage());
            }
        }

        // Get groupfolder ID for MetaVox imports
        $groupfolderId = $this->setupService->getGroupFolderId();

        // Store MetaVox data for import after filecache rescan
        $metavoxImportQueue = [];

        // 8. Import pages
        foreach ($sortedPages as $pageData) {
            $result = $this->importPage($pageData, $language, $overwrite, $importedPages);
            if ($result['imported']) {
                $stats['pagesImported']++;

                // Track this page for parent path resolution
                $importedPages[$pageData['uniqueId']] = $result['path'];

                // Queue MetaVox metadata for import after filecache rescan
                if ($hasMetaVoxData &&
                    !empty($pageData['metadata']) &&
                    isset($result['fileId']) &&
                    $groupfolderId > 0) {

                    // Use _exportPath for IntraVox exports, or importedPath for Confluence imports
                    // Root = home.json, subfolders = {foldername}.json
                    $exportPath = $pageData['_exportPath'] ?? null;
                    $importedPath = $result['path'] ?? null;

                    $metavoxImportQueue[] = [
                        'uniqueId' => $pageData['uniqueId'],
                        'exportPath' => $exportPath,
                        'importedPath' => $importedPath,
                        'metadata' => $pageData['metadata']
                    ];
                }

                // Import comments if requested
                if ($importComments && !empty($pageData['comments'])) {
                    $commentsCount = $this->importComments(
                        $pageData['uniqueId'],
                        $pageData['comments']
                    );
                    $stats['commentsImported'] += $commentsCount;
                }

                // Import page-level reactions if requested
                if ($importComments && !empty($pageData['pageReactions'])) {
                    $reactionsCount = $this->importPageReactions(
                        $pageData['uniqueId'],
                        $pageData['pageReactions']
                    );
                    $stats['reactionsImported'] = ($stats['reactionsImported'] ?? 0) + $reactionsCount;
                }
            } else {
                $stats['pagesSkipped']++;
            }
        }

        // 9. Import media files
        $stats['mediaFilesImported'] = $this->importMediaFiles($tempDir, $language, $overwrite);

        // 10. Update navigation with imported pages (if navigation wasn't explicitly imported)

        if (empty($exportData['navigation']) && !empty($importedPages)) {
            try {
                $this->updateNavigationWithImportedPages($sortedPages, $importedPages, $language, $parentPageId);
                $stats['navigationUpdated'] = true;
            } catch (\Exception $e) {
                $this->logger->warning('Failed to update navigation: ' . $e->getMessage());
            }
        }

        // 11. Rescan filecache SYNCHRONOUSLY before MetaVox import
        if ($hasMetaVoxData && !empty($metavoxImportQueue)) {
            $this->rescanFilecache($language);
        }

        // 12. Import MetaVox metadata (after filecache rescan)
        if ($hasMetaVoxData && !empty($metavoxImportQueue) && $groupfolderId > 0) {

            $langFolder = $this->setupService->getLanguageFolder($language);

            foreach ($metavoxImportQueue as $queueItem) {
                try {
                    // Get fresh file ID after rescan
                    // Use exportPath for IntraVox exports, or importedPath for Confluence imports
                    $pageFile = null;

                    if (!empty($queueItem['exportPath'])) {
                        // IntraVox export format - use exportPath
                        $pageFile = $this->getPageFileByPath($langFolder, $queueItem['exportPath'], $queueItem['uniqueId']);
                    } elseif (!empty($queueItem['importedPath'])) {
                        // Confluence import - use the imported path directly
                        $pageFile = $this->getPageFileByImportedPath($langFolder, $queueItem['importedPath']);
                    }

                    if ($pageFile === null) {
                        $this->logger->warning('Could not find page file for MetaVox import', [
                            'uniqueId' => $queueItem['uniqueId'],
                            'exportPath' => $queueItem['exportPath'] ?? 'none',
                            'importedPath' => $queueItem['importedPath'] ?? 'none'
                        ]);
                        continue;
                    }

                    $groupfolderFileId = $pageFile->getId();
                    $groupfolderFolderId = $pageFile->getParent()->getId();

                    // Map groupfolder file IDs to Files storage IDs for MetaVox compatibility
                    // MetaVox stores metadata using Files storage IDs, not groupfolder IDs
                    $filesStorageFileId = $this->getFilesStorageFileId($groupfolderFileId, $groupfolderId);
                    $filesStorageFolderId = $this->getFilesStorageFileId($groupfolderFolderId, $groupfolderId);

                    // Use mapped IDs if available, otherwise fall back to original IDs
                    $freshFileId = $filesStorageFileId ?? $groupfolderFileId;
                    $folderFileId = $filesStorageFolderId ?? $groupfolderFolderId;

                    $metadata = $queueItem['metadata'];

                    // Check if v1.3 format (nested file/folder) or old format (flat array)
                    $isNestedFormat = isset($metadata['file']) || isset($metadata['folder']);

                    if ($isNestedFormat) {
                        // v1.3+ format: metadata nested as file/folder
                        $fileMetadata = $metadata['file'] ?? [];
                        $folderMetadata = $metadata['folder'] ?? [];

                        // Import file metadata
                        if (!empty($fileMetadata)) {
                            foreach ($fileMetadata as $field) {
                                $success = $this->metaVoxImportService->importFieldValue(
                                    $freshFileId,
                                    $groupfolderId,
                                    $field['field_name'],
                                    $field['value']
                                );
                                if ($success) {
                                    $stats['metavoxFieldsImported']++;
                                } else {
                                    $stats['metavoxFieldsFailed']++;
                                }
                            }
                        }

                        // Import folder metadata
                        if (!empty($folderMetadata)) {
                            foreach ($folderMetadata as $field) {
                                $success = $this->metaVoxImportService->importFieldValue(
                                    $folderFileId,
                                    $groupfolderId,
                                    $field['field_name'],
                                    $field['value']
                                );
                                if ($success) {
                                    $stats['metavoxFieldsImported']++;
                                } else {
                                    $stats['metavoxFieldsFailed']++;
                                }
                            }
                        }
                    } else {
                        // v1.2 or older format: flat metadata array (backward compatibility)
                        $metaStats = $this->metaVoxImportService->importPageMetadata(
                            $freshFileId,
                            $groupfolderId,
                            $metadata
                        );

                        $stats['metavoxFieldsImported'] += $metaStats['imported'];
                        $stats['metavoxFieldsSkipped'] += $metaStats['skipped'];
                        $stats['metavoxFieldsFailed'] += $metaStats['failed'];
                    }

                } catch (\Exception $e) {
                    $this->logger->error('Failed to import MetaVox metadata for page', [
                        'uniqueId' => $queueItem['uniqueId'],
                        'error' => $e->getMessage()
                    ]);
                    // Count all fields as failed
                    $metadata = $queueItem['metadata'];
                    $isNested = isset($metadata['file']) || isset($metadata['folder']);
                    if ($isNested) {
                        $total = count($metadata['file'] ?? []) + count($metadata['folder'] ?? []);
                    } else {
                        $total = count($metadata);
                    }
                    $stats['metavoxFieldsFailed'] += $total;
                }
            }
        }

        // 13. Trigger background groupfolder scan for final cleanup
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

        // Flush distributed caches so the freshly imported pages appear
        // in tree, navigation and permission lookups immediately. Without
        // this the import "succeeds" but the new pages are invisible for
        // up to 5 minutes (PR-3 distributed tree TTL).
        $this->pageService->invalidateAllCaches();

        $this->logger->info(self::LOG_PREFIX . ' Import complete', $stats);

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
        // Check both top-level (new format) and content (old format for backward compatibility)
        $exportPath = $pageData['_exportPath'] ?? $content['_exportPath'] ?? null;

        // If page has a parent, determine the parent path
        $parentPath = null;
        if ($parentUniqueId && isset($importedPages[$parentUniqueId])) {
            $parentPath = $importedPages[$parentUniqueId];
        }

        // Remove internal export metadata before saving
        unset($content['_exportPath']);

        // Run imported page bodies through the same allowlist an editor save
        // uses. Without this, import was the one write path into page JSON that
        // skipped sanitisation entirely: importPage() json_encode'd whatever the
        // uploaded (or, via DemoDataService, REMOTELY DOWNLOADED) file contained
        // and putContent() it straight into the groupfolder, so unsanitised HTML,
        // javascript: urls and unknown widget keys landed in stored pages.
        $content = $this->sanitizeImportedContent($content);

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

                if ($exists && !$overwrite) {
                    return ['imported' => false, 'reason' => 'exists'];
                }

                // Write or update the home page file
                $jsonContent = json_encode($content, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

                if ($exists) {
                    $file = $targetFolder->get($targetFile);
                    $file->putContent($jsonContent);
                } else {
                    $file = $targetFolder->newFile($targetFile, $jsonContent);
                }

                // Get file ID for MetaVox metadata import
                $fileId = $file->getId();

                // Ensure _media folder exists for home page
                if (!$targetFolder->nodeExists('_media')) {
                    $mediaFolder = $targetFolder->newFolder('_media');
                    $this->ensurePhysicalFolder($mediaFolder);
                } else {
                    $mediaFolder = $targetFolder->get('_media');
                    $this->ensurePhysicalFolder($mediaFolder);
                }
            } else {
                // Regular page: use exportPath to recreate folder structure
                // For Confluence imports: build path from parentPath + slugified title

                if (empty($exportPath)) {
                    // Confluence import: no exportPath, build from parent path and title
                    $pageTitle = $content['title'] ?? 'untitled';
                    $pageFolderName = $this->slugify($pageTitle);

                    if ($parentPath) {
                        // Strip language prefix from parent path if present
                        $relativeParentPath = $parentPath;
                        if (str_starts_with($relativeParentPath, $language . '/')) {
                            $relativeParentPath = substr($relativeParentPath, strlen($language) + 1);
                        }
                        $exportPath = $relativeParentPath . '/' . $pageFolderName;
                    } else {
                        // No parent - this is a top-level page under language folder
                        $exportPath = $pageFolderName;
                    }

                }

                // Backward compatibility: Strip language prefix for old exports (v0.8.4 and earlier)
                if (str_starts_with($exportPath, $language . '/')) {
                    $exportPath = substr($exportPath, strlen($language) + 1);
                }

                // Split path into components (e.g., "departments/sales" -> ["departments", "sales"])
                $pathParts = explode('/', $exportPath);
                $pageFolderName = end($pathParts); // Last part is the page folder name

                // Create folder structure using direct Nextcloud API
                $currentFolder = $langFolder;

                foreach ($pathParts as $part) {
                    try {
                        $exists = $currentFolder->nodeExists($part);

                        if ($exists) {
                            $currentFolder = $currentFolder->get($part);
                            if (!($currentFolder instanceof Folder)) {
                                throw new \Exception("Path exists but is not a folder: $part");
                            }

                            // Ensure physical folder exists (database cache can be out of sync)
                            $this->ensurePhysicalFolder($currentFolder);
                        } else {
                            $currentFolder = $currentFolder->newFolder($part);
                            // Force physical folder creation on disk
                            $this->ensurePhysicalFolder($currentFolder);
                        }
                    } catch (\Exception $e) {
                        // Handle transaction conflicts - retry: folder might have been created by parallel operation
                        try {
                            $currentFolder = $currentFolder->get($part);
                            if (!($currentFolder instanceof Folder)) {
                                throw new \Exception("Path exists but is not a folder: $part");
                            }
                        } catch (\Exception $retryError) {
                            $this->logger->error('Failed to create/get folder', [
                                'folder' => $part,
                                'error' => $retryError->getMessage()
                            ]);
                            throw new \Exception('Could not create/access folder: ' . $part . ' - ' . $retryError->getMessage());
                        }
                    }
                }

                $pageFolder = $currentFolder;
                $folderId = $pageFolderName;

                // Create or update {pageId}.json file inside the folder
                $targetFile = $folderId . '.json';
                $fileExists = $pageFolder->nodeExists($targetFile);

                // Skip if file exists and overwrite is false
                if ($fileExists && !$overwrite) {
                    return ['imported' => false, 'reason' => 'exists'];
                }

                $jsonContent = json_encode($content, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

                // Write the file
                if ($fileExists) {
                    $file = $pageFolder->get($targetFile);
                    $file->putContent($jsonContent);
                } else {
                    $file = $pageFolder->newFile($targetFile, $jsonContent);
                }

                // Get file ID for MetaVox metadata import
                $fileId = $file->getId();

                // Ensure _media folder exists
                if (!$pageFolder->nodeExists('_media')) {
                    $mediaFolder = $pageFolder->newFolder('_media');
                    $this->ensurePhysicalFolder($mediaFolder);
                } else {
                    $mediaFolder = $pageFolder->get('_media');
                    $this->ensurePhysicalFolder($mediaFolder);
                }

                // Calculate full page path
                $relativePagePath = $this->getRelativePath($pageFolder, $langFolder);
                $pageFolderPath = $language . '/' . $relativePagePath;
            }

            // Keep the page index in step with the import. Import writes page
            // JSON directly rather than going through PageService::createPage(),
            // so without this every imported page is invisible to the index —
            // and `occ intravox:import` is a documented way to seed a whole
            // language folder. Non-blocking: the page is already on disk, and
            // `occ intravox:reindex` repairs a miss.
            //
            // Index the ABSOLUTE path of the folder holding the JSON, matching
            // PageService. $pageFolderPath is relative and stays that way — it
            // is this method's return value and callers depend on that shape.
            try {
                $parentFolder = $file->getParent();
                $indexPath = $parentFolder->getPath();
                $this->pageIndexService->indexPage($content, $language, $indexPath, $fileId, $parentFolder->getId());
            } catch (\Exception $e) {
                $this->logger->warning('Failed to index imported page ' . $uniqueId, [
                    'error' => $e->getMessage(),
                ]);
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
                $createdComment = $this->commentService->createComment(
                    $uniqueId,
                    $commentData['message'] ?? '',
                    $commentData['parentId'] ?? null,
                    $commentData['userId'] ?? null
                );
                $count++;

                // Import comment reactions if present
                if (!empty($commentData['reactions']) && isset($createdComment['id'])) {
                    $this->importCommentReactions(
                        (string)$createdComment['id'],
                        $commentData['reactions']
                    );
                }

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
     * Import page-level reactions
     *
     * @param string $uniqueId Page unique ID
     * @param array $pageReactions Reactions data from export
     * @return int Number of reactions imported
     */
    /**
     * Sanitize an imported page body without losing the fields import owns.
     *
     * validateAndSanitizePage() is a strict WHITELIST built for the editor save
     * path, where the surrounding code sets the page metadata itself. Applied
     * naively to an import it therefore DELETES legitimate content: an A/B over
     * the 78 demo pages showed it dropping `language` from all of them, and
     * `isTemplate` + `description` from all seven templates — which is what makes
     * a template a template. That would have corrupted every imported export.
     *
     * So the widget/layout body is sanitised (that is where the untrusted HTML
     * and urls live) and these few structural fields are carried across verbatim
     * after being type-checked. They are identifiers and flags, never rendered as
     * markup.
     *
     * @param array<string,mixed> $content
     * @return array<string,mixed>
     */
    private function sanitizeImportedContent(array $content): array {
        $sanitized = $this->shapeSanitizer->validateAndSanitizePage($content);

        if (isset($content['language']) && is_string($content['language'])
            && preg_match('/^[a-z]{2}(_[A-Z]{2})?$/', $content['language'])
        ) {
            $sanitized['language'] = $content['language'];
        }

        if (isset($content['isTemplate'])) {
            $sanitized['isTemplate'] = (bool)$content['isTemplate'];
        }

        if (isset($content['description']) && is_string($content['description'])) {
            // Plain text in the template picker, so strip markup rather than
            // trusting it: this is the one preserved field an attacker controls.
            $sanitized['description'] = mb_substr(
                strip_tags($content['description']),
                0,
                500,
            );
        }

        foreach (['created', 'modified'] as $timestampField) {
            if (isset($content[$timestampField]) && is_int($content[$timestampField])) {
                $sanitized[$timestampField] = $content[$timestampField];
            }
        }

        foreach (['createdBy', 'sourcePageId'] as $idField) {
            if (isset($content[$idField]) && is_string($content[$idField])) {
                $sanitized[$idField] = mb_substr(strip_tags($content[$idField]), 0, 128);
            }
        }

        return $sanitized;
    }

    private function importPageReactions(string $uniqueId, array $pageReactions): int {
        $count = 0;

        // pageReactions format: ['reactions' => ['emoji' => count], 'userReactions' => [...]]
        $reactions = $pageReactions['reactions'] ?? [];

        foreach ($reactions as $emoji => $reactionCount) {
            // Import each reaction count times (we don't have original user info, so use current user)
            for ($i = 0; $i < $reactionCount; $i++) {
                try {
                    $this->commentService->addPageReaction($uniqueId, $emoji);
                    $count++;
                    // Only import once per emoji (we can't recreate multiple users)
                    break;
                } catch (\Exception $e) {
                    $this->logger->warning('Failed to import page reaction', [
                        'uniqueId' => $uniqueId,
                        'emoji' => $emoji,
                        'error' => $e->getMessage()
                    ]);
                }
            }
        }

        return $count;
    }

    /**
     * Import reactions for a specific comment
     *
     * @param string $commentId Comment ID
     * @param array $reactions Reactions data ['emoji' => count]
     * @return int Number of reactions imported
     */
    private function importCommentReactions(string $commentId, array $reactions): int {
        $count = 0;

        foreach ($reactions as $emoji => $reactionCount) {
            // Import one reaction per emoji (we can't recreate multiple users)
            try {
                $this->commentService->addCommentReaction($commentId, (string)$emoji);
                $count++;
            } catch (\Exception $e) {
                $this->logger->warning('Failed to import comment reaction', [
                    'commentId' => $commentId,
                    'emoji' => $emoji,
                    'error' => $e->getMessage()
                ]);
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
                    // Ensure physical folder exists even if in database
                    $this->ensurePhysicalFolder($current);
                } else {
                    throw new \Exception('Path element is not a folder: ' . $part);
                }
            } else {
                $current = $current->newFolder($part);
                // Force physical folder creation
                $this->ensurePhysicalFolder($current);
            }
        }

        return $current;
    }

    /**
     * Ensure physical folder exists on disk (not just in database)
     * Handles storage wrappers like Files_Trashbin
     *
     * @param Folder $folder Folder to ensure exists physically
     */
    private function ensurePhysicalFolder(Folder $folder): void {
        try {
            $storage = $folder->getStorage();
            $internalPath = $folder->getInternalPath();

            // Check if storage supports getLocalFile (works with wrapped storage too)
            if (method_exists($storage, 'getLocalFile')) {
                $physicalPath = $storage->getLocalFile($internalPath);

                if ($physicalPath && !file_exists($physicalPath)) {
                    if (!mkdir($physicalPath, 0750, true)) {
                        throw new \Exception("Failed to create physical directory: $physicalPath");
                    }
                }
            }
        } catch (\Exception $e) {
            $this->logger->error('Failed to ensure physical folder', [
                'folder' => $folder->getName(),
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
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
     * Get page file by export path (resolves folder structure)
     *
     * @param Folder $langFolder Language folder
     * @param string|null $exportPath Export path (e.g., "home", "about", "departments/sales")
     * @param string $uniqueId Page uniqueId for filename (e.g., "page-abc123")
     * @return File The page JSON file
     * @throws PageNotFoundException If the path does not resolve to a folder
     */
    private function getPageFileByPath(Folder $langFolder, ?string $exportPath, string $uniqueId): File {
        // exportPath format is "home", "about", "departments/sales" (NO language prefix)
        // langFolder already IS the language folder
        // Filename: root = home.json, subfolders = {foldername}.json (same as export)

        // If path is null, empty, or "home", return home.json in language folder
        if (empty($exportPath) || $exportPath === 'home') {
            return $langFolder->get('home.json');
        }

        // Navigate through folder structure
        $parts = explode('/', $exportPath);
        $currentFolder = $langFolder;

        foreach ($parts as $part) {
            if (empty($part)) continue;
            $currentFolder = $currentFolder->get($part);
            if (!($currentFolder instanceof Folder)) {
                throw new PageNotFoundException('Invalid path: ' . $exportPath);
            }
        }

        // Subfolders use {foldername}.json as filename (last part of path)
        // This matches how export finds files: about/about.json, departments/sales/sales.json
        $folderName = end($parts);
        $filename = $folderName . '.json';
        return $currentFolder->get($filename);
    }

    /**
     * Get page file by imported path (for Confluence imports)
     *
     * @param Folder $langFolder Language folder
     * @param string $importedPath Imported path (e.g., "en/about" or "en/departments/sales")
     * @return File|null The page JSON file, or null if not found
     */
    private function getPageFileByImportedPath(Folder $langFolder, string $importedPath): ?File {
        try {
            // importedPath format is "{language}/{relative_path}" (e.g., "en/about")
            // langFolder already IS the language folder, so we need to strip the language prefix
            $parts = explode('/', $importedPath);

            // Remove the first part (language code) since langFolder is already the language folder
            if (count($parts) > 0) {
                array_shift($parts);
            }

            // If no path remaining, this is the home page
            if (empty($parts) || (count($parts) === 1 && empty($parts[0]))) {
                return $langFolder->get('home.json');
            }

            // Navigate through folder structure
            $currentFolder = $langFolder;
            foreach ($parts as $part) {
                if (empty($part)) continue;
                if (!$currentFolder->nodeExists($part)) {
                    return null;
                }
                $node = $currentFolder->get($part);
                if (!($node instanceof Folder)) {
                    return null;
                }
                $currentFolder = $node;
            }

            // The page file is named {foldername}.json
            $folderName = end($parts);
            $filename = $folderName . '.json';

            if (!$currentFolder->nodeExists($filename)) {
                return null;
            }

            $file = $currentFolder->get($filename);
            return ($file instanceof File) ? $file : null;
        } catch (\Exception $e) {
            $this->logger->warning('Failed to get page file by imported path', [
                'importedPath' => $importedPath,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Rescan filecache SYNCHRONOUSLY for a specific language folder
     * This ensures fileId's are correct before MetaVox import
     *
     * @param string $language Language code
     */
    private function rescanFilecache(string $language): void {
        try {
            $langFolder = $this->setupService->getLanguageFolder($language);

            // Use the folder's internal storage scanner for compatibility
            $storage = $langFolder->getStorage();
            $internalPath = $langFolder->getInternalPath();

            if ($storage) {
                $scanner = $storage->getScanner();
                $scanner->scan($internalPath, true, false);
            }
        } catch (\Exception $e) {
            $this->logger->error('Failed to rescan filecache', [
                'language' => $language,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Trigger groupfolder scan to update file cache (ASYNC background process)
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
            if (PagePathHelper::isInfrastructureFolder($folderName)) {
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
        // Load current navigation
        $navigation = $this->navigationService->getNavigation($language);

        if (!$navigation || !isset($navigation['items'])) {
            return;
        }

        // Find the parent navigation item path first to determine depth (if parent specified)
        $parentPath = null;
        if ($parentPageId) {
            $parentPath = $this->navigationBuilder->findNavigationItemPath($navigation['items'], $parentPageId);
        }

        // Calculate parent depth to determine max depth for imported tree
        // Navigation allows max 3 levels (0, 1, 2)
        $parentDepth = 0;
        if ($parentPath) {
            foreach ($parentPath as $key) {
                if ($key === 'children') {
                    $parentDepth++;
                }
            }
        }

        // Maximum depth for imported tree = 4 (5 levels total) - parentDepth
        $maxImportDepth = 4 - $parentDepth;

        // Build a tree structure of imported pages with depth limit
        $pageTree = $this->navigationBuilder->buildPageTree($sortedPages, $maxImportDepth);

        if (!$parentPath) {
            $this->navigationBuilder->addPagesToNavigation($navigation['items'], $pageTree);
        } else {
            // Get reference to parent item
            $parentNavItem = &$this->getNavigationItemByPath($navigation['items'], $parentPath);

            // Add to parent's children
            if (!isset($parentNavItem['children'])) {
                $parentNavItem['children'] = [];
            }
            $this->navigationBuilder->addPagesToNavigation($parentNavItem['children'], $pageTree);
        }

        // Save updated navigation
        $this->navigationService->saveNavigation($navigation, $language);
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
     * Map a groupfolder file ID to its corresponding Files storage file ID
     *
     * MetaVox stores metadata using Files storage IDs (storage where path starts with 'files/'),
     * but IntraVox accesses files through groupfolders (storage where path starts with '__groupfolders/').
     * This method finds the corresponding Files storage file ID for a given groupfolder file ID.
     *
     * @param int $groupfolderFileId The file ID from groupfolder storage
     * @param int $groupfolderId The groupfolder ID
     * @return int|null The corresponding Files storage file ID, or null if not found
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
            // Path format: __groupfolders/{id}/files/{relative_path}
            $pattern = '__groupfolders/' . $groupfolderId . '/files/';
            if (strpos($groupfolderPath, $pattern) !== 0) {
                // Maybe it's already a files storage path or different format
                $this->logger->debug('Path does not match expected groupfolder pattern', [
                    'path' => $groupfolderPath,
                    'pattern' => $pattern
                ]);
                return null;
            }

            // Get the relative path after __groupfolders/{id}/files/
            $relativePath = substr($groupfolderPath, strlen($pattern));

            // Now find the file ID in Files storage with path files/{relative_path}
            $filesPath = 'files/' . $relativePath;

            $qb2 = $this->connection->getQueryBuilder();
            $qb2->select('fileid')
                ->from('filecache')
                ->where($qb2->expr()->eq('path', $qb2->createNamedParameter($filesPath)));

            $result2 = $qb2->executeQuery();
            $row2 = $result2->fetch();
            $result2->closeCursor();

            if ($row2 && isset($row2['fileid'])) {
                $filesStorageFileId = (int)$row2['fileid'];

                $this->logger->debug('Mapped groupfolder file ID to Files storage ID', [
                    'groupfolderFileId' => $groupfolderFileId,
                    'groupfolderPath' => $groupfolderPath,
                    'filesPath' => $filesPath,
                    'filesStorageFileId' => $filesStorageFileId
                ]);

                return $filesStorageFileId;
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
     * Safely extract a ZIP into $destDir.
     *
     * The ZIP-Slip logic used to live here, duplicated almost verbatim in
     * ConfluenceHtmlImporter::extractZip(). Both now delegate to
     * SafeZipExtractor, so a fix reaches every extraction path and the size
     * limits neither of them had apply here too.
     *
     * @throws \RuntimeException on traversal, an oversized archive or a write failure
     */
    private function safeExtractZip(\ZipArchive $zip, string $destDir): void {
        $this->zipExtractor->extract($zip, $destDir);
    }

    /**
     * Convert a string to a URL-friendly slug
     *
     * @param string $text Text to slugify
     * @return string Slugified text
     */
    private function slugify(string $text): string {
        // Convert to lowercase
        $text = mb_strtolower($text, 'UTF-8');

        // Replace common special characters
        $replacements = [
            'ä' => 'ae', 'ö' => 'oe', 'ü' => 'ue', 'ß' => 'ss',
            'à' => 'a', 'á' => 'a', 'â' => 'a', 'ã' => 'a', 'å' => 'a',
            'è' => 'e', 'é' => 'e', 'ê' => 'e', 'ë' => 'e',
            'ì' => 'i', 'í' => 'i', 'î' => 'i', 'ï' => 'i',
            'ò' => 'o', 'ó' => 'o', 'ô' => 'o', 'õ' => 'o',
            'ù' => 'u', 'ú' => 'u', 'û' => 'u',
            'ñ' => 'n', 'ç' => 'c',
            '&' => 'and', '@' => 'at',
        ];
        $text = str_replace(array_keys($replacements), array_values($replacements), $text);

        // Remove any remaining non-alphanumeric characters except spaces and hyphens
        $text = preg_replace('/[^a-z0-9\s\-]/', '', $text);

        // Replace spaces with hyphens
        $text = preg_replace('/[\s]+/', '-', $text);

        // Remove multiple consecutive hyphens
        $text = preg_replace('/-+/', '-', $text);

        // Trim hyphens from start and end
        $text = trim($text, '-');

        // If empty after processing, return a default
        if (empty($text)) {
            $text = 'page';
        }

        return $text;
    }
}
