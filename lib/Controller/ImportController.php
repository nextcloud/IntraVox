<?php
declare(strict_types=1);

namespace OCA\IntraVox\Controller;

use OCA\IntraVox\Service\ImportService;
use OCA\IntraVox\Service\Import\ConfluenceApiImporter;
use OCA\IntraVox\Service\Import\ConfluenceImporter;
use OCA\IntraVox\Service\Import\ConfluenceHtmlImporter;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IRequest;
use OCP\ITempManager;
use Psr\Log\LoggerInterface;

/**
 * Controller for importing IntraVox pages and content from ZIP exports and external systems
 */
class ImportController extends Controller {
    public function __construct(
        string $appName,
        IRequest $request,
        private ImportService $importService,
        private LoggerInterface $logger,
        private ITempManager $tempManager
    ) {
        parent::__construct($appName, $request);
    }

    /**
     * Import from uploaded ZIP file
     *
     * @NoAdminRequired
     * @NoCSRFRequired
     * @return JSONResponse
     */
    public function importZip(string $type = 'file'): JSONResponse {
        try {
            $file = $this->request->getUploadedFile('file');

            if (!$file || $file['error'] !== UPLOAD_ERR_OK) {
                $errorMessages = [
                    UPLOAD_ERR_INI_SIZE => 'File exceeds upload_max_filesize',
                    UPLOAD_ERR_FORM_SIZE => 'File exceeds MAX_FILE_SIZE',
                    UPLOAD_ERR_PARTIAL => 'File was only partially uploaded',
                    UPLOAD_ERR_NO_FILE => 'No file was uploaded',
                    UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary folder',
                    UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk',
                    UPLOAD_ERR_EXTENSION => 'Upload stopped by PHP extension',
                ];
                $errorCode = $file['error'] ?? UPLOAD_ERR_NO_FILE;
                $errorMessage = $errorMessages[$errorCode] ?? 'Unknown upload error';
                return new JSONResponse(['error' => $errorMessage], Http::STATUS_BAD_REQUEST);
            }

            // Validate file type
            $finfo = new \finfo(FILEINFO_MIME_TYPE);
            $mimeType = $finfo->file($file['tmp_name']);

            // Accept both application/zip and application/x-zip-compressed
            if (!in_array($mimeType, ['application/zip', 'application/x-zip-compressed', 'application/octet-stream'])) {
                return new JSONResponse(
                    ['error' => 'Invalid file type. Expected ZIP file, got: ' . $mimeType],
                    Http::STATUS_BAD_REQUEST
                );
            }

            // Additional check: verify it's actually a ZIP by checking magic bytes
            $handle = fopen($file['tmp_name'], 'rb');
            $header = fread($handle, 4);
            fclose($handle);

            // ZIP files start with PK (0x50 0x4B)
            if (substr($header, 0, 2) !== 'PK') {
                return new JSONResponse(
                    ['error' => 'Invalid ZIP file format'],
                    Http::STATUS_BAD_REQUEST
                );
            }

            $importComments = $this->request->getParam('importComments', '1') === '1';
            $overwrite = $this->request->getParam('overwrite', '0') === '1';

            $zipContent = file_get_contents($file['tmp_name']);
            $stats = $this->importService->importFromZip($zipContent, $importComments, $overwrite);

            return new JSONResponse([
                'success' => true,
                'stats' => $stats,
            ]);
        } catch (\Exception $e) {
            return new JSONResponse(
                ['error' => $e->getMessage()],
                Http::STATUS_INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     * Test Confluence connection
     *
     * @NoAdminRequired
     * @return JSONResponse
     */
    public function testConfluenceConnection(): JSONResponse {
        try {
            $baseUrl = $this->request->getParam('baseUrl');
            $authType = $this->request->getParam('authType');
            $authUser = $this->request->getParam('authUser', '');
            $authToken = $this->request->getParam('authToken');

            if (empty($baseUrl) || empty($authToken)) {
                return new JSONResponse(
                    ['error' => 'Missing required parameters'],
                    Http::STATUS_BAD_REQUEST
                );
            }

            $importer = new ConfluenceApiImporter(
                $this->logger,
                $baseUrl,
                $authType,
                $authUser,
                $authToken
            );

            $result = $importer->testConnection();

            if ($result['success']) {
                return new JSONResponse([
                    'success' => true,
                    'version' => $result['version'],
                    'user' => $result['user'],
                ]);
            } else {
                return new JSONResponse(
                    ['error' => $result['error']],
                    Http::STATUS_UNAUTHORIZED
                );
            }

        } catch (\Exception $e) {
            return new JSONResponse(
                ['error' => $e->getMessage()],
                Http::STATUS_INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     * List Confluence spaces
     *
     * @NoAdminRequired
     * @return JSONResponse
     */
    public function listConfluenceSpaces(): JSONResponse {
        try {
            $baseUrl = $this->request->getParam('baseUrl');
            $authType = $this->request->getParam('authType');
            $authUser = $this->request->getParam('authUser', '');
            $authToken = $this->request->getParam('authToken');

            if (empty($baseUrl) || empty($authToken)) {
                return new JSONResponse(
                    ['error' => 'Missing required parameters'],
                    Http::STATUS_BAD_REQUEST
                );
            }

            $importer = new ConfluenceApiImporter(
                $this->logger,
                $baseUrl,
                $authType,
                $authUser,
                $authToken
            );

            $spaces = $importer->listSpaces();

            return new JSONResponse([
                'success' => true,
                'spaces' => $spaces,
            ]);

        } catch (\Exception $e) {
            return new JSONResponse(
                ['error' => $e->getMessage()],
                Http::STATUS_INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     * Import from Confluence via REST API
     *
     * @NoAdminRequired
     * @return JSONResponse
     */
    public function importFromConfluence(): JSONResponse {
        try {
            $baseUrl = $this->request->getParam('baseUrl');
            $authType = $this->request->getParam('authType');
            $authUser = $this->request->getParam('authUser', '');
            $authToken = $this->request->getParam('authToken');
            $spaceKey = $this->request->getParam('spaceKey');
            $language = $this->request->getParam('language', 'nl');

            if (empty($baseUrl) || empty($authToken) || empty($spaceKey)) {
                return new JSONResponse(
                    ['error' => 'Missing required parameters'],
                    Http::STATUS_BAD_REQUEST
                );
            }

            $this->logger->info('Starting Confluence import', [
                'baseUrl' => $baseUrl,
                'spaceKey' => $spaceKey,
                'language' => $language,
            ]);

            // Create API importer
            $apiImporter = new ConfluenceApiImporter(
                $this->logger,
                $baseUrl,
                $authType,
                $authUser,
                $authToken
            );

            // Import space
            $intermediateFormat = $apiImporter->importSpace($spaceKey, $language);

            // Create Confluence importer instance to use conversion methods
            $confluenceImporter = new ConfluenceImporter($this->logger);

            // Convert IntermediateFormat to IntraVox export structure
            $exportData = $this->convertIntermediateToExport($confluenceImporter, $intermediateFormat);

            // Create temporary ZIP file with export data
            $tempDir = $this->tempManager->getTemporaryFolder();

            // Write export.json
            $exportJson = json_encode($exportData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            file_put_contents($tempDir . '/export.json', $exportJson);

            // Download attachments/media files
            $mediaStats = $this->downloadConfluenceMedia(
                $apiImporter,
                $intermediateFormat,
                $tempDir,
                $language
            );

            // Create ZIP
            $zipPath = $tempDir . '/confluence-import.zip';
            $this->createZipFromDirectory($tempDir, $zipPath);

            // Import using existing ImportService
            $zipContent = file_get_contents($zipPath);
            $stats = $this->importService->importFromZip($zipContent, false, false);

            // Cleanup
            $this->cleanupTempDir($tempDir);

            $this->logger->info('Confluence import complete', [
                'pages' => $stats['pagesImported'],
                'media' => $stats['mediaFilesImported'],
            ]);

            return new JSONResponse([
                'success' => true,
                'stats' => $stats,
            ]);

        } catch (\Exception $e) {
            $this->logger->error('Confluence import failed: ' . $e->getMessage());
            return new JSONResponse(
                ['error' => $e->getMessage()],
                Http::STATUS_INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     * Import from Confluence HTML export ZIP file
     *
     * @NoAdminRequired
     * @NoCSRFRequired
     * @return JSONResponse
     */
    public function importFromConfluenceHtml(string $type = 'upload'): JSONResponse {
        $this->logger->info('Confluence HTML import endpoint called');

        try {
            $file = $this->request->getUploadedFile('file');
            $language = $this->request->getParam('language', 'nl');

            $this->logger->info('Import request received', [
                'file' => $file ? $file['name'] : 'no file',
                'language' => $language,
            ]);

            if (!$file || $file['error'] !== UPLOAD_ERR_OK) {
                $this->logger->warning('File upload error', ['error' => $file['error'] ?? 'no file']);
                return new JSONResponse(
                    ['error' => 'No file uploaded or upload error'],
                    Http::STATUS_BAD_REQUEST
                );
            }

            // Validate ZIP file
            $finfo = new \finfo(FILEINFO_MIME_TYPE);
            $mimeType = $finfo->file($file['tmp_name']);

            if (!in_array($mimeType, ['application/zip', 'application/x-zip-compressed', 'application/octet-stream'])) {
                return new JSONResponse(
                    ['error' => 'Invalid file type. Expected ZIP file'],
                    Http::STATUS_BAD_REQUEST
                );
            }

            $this->logger->info('Starting Confluence HTML import', [
                'filename' => $file['name'],
                'size' => $file['size'],
                'language' => $language,
            ]);

            // Create HTML importer
            $htmlImporter = new ConfluenceHtmlImporter($this->logger);
            $confluenceImporter = new ConfluenceImporter($this->logger);

            // Import from ZIP
            $intermediateFormat = $htmlImporter->importFromZip($file['tmp_name'], $language);

            $this->logger->info('Parsed Confluence HTML export', [
                'pages' => count($intermediateFormat->pages),
                'media' => count($intermediateFormat->mediaDownloads),
            ]);

            // Convert to IntraVox export format
            $export = $this->convertIntermediateToExport($confluenceImporter, $intermediateFormat);

            // Create temporary directory for export
            $tempDir = $this->tempManager->getTemporaryFolder();

            // Write export.json
            file_put_contents($tempDir . '/export.json', json_encode($export, JSON_PRETTY_PRINT));

            // Create ZIP
            $zipPath = $tempDir . '/confluence-html-import.zip';
            $this->createZipFromDirectory($tempDir, $zipPath);

            // Import the ZIP
            $stats = $this->importService->import($zipPath);

            // Cleanup
            $this->cleanupTempDir($tempDir);

            return new JSONResponse([
                'success' => true,
                'stats' => $stats,
                'pages' => count($intermediateFormat->pages),
                'message' => 'Confluence HTML export imported successfully',
            ]);

        } catch (\Exception $e) {
            $this->logger->error('Confluence HTML import failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return new JSONResponse(
                ['error' => 'Import failed: ' . $e->getMessage()],
                Http::STATUS_INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     * Convert IntermediateFormat to IntraVox export structure
     */
    private function convertIntermediateToExport(ConfluenceImporter $importer, $intermediateFormat): array {
        // Use the AbstractImporter's conversion method via reflection
        // Since convertToIntraVoxExport is protected, we'll recreate it here
        $export = [
            'exportVersion' => '1.0',
            'exportDate' => (new \DateTime())->format('c'),
            'language' => $intermediateFormat->language,
            'navigation' => $intermediateFormat->navigation,
            'footer' => [],
            'pages' => [],
        ];

        // Use reflection to access the protected method
        $reflectionClass = new \ReflectionClass($importer);
        $method = $reflectionClass->getMethod('convertToIntraVoxExport');
        $method->setAccessible(true);

        $export = $method->invoke($importer, $intermediateFormat);

        return $export;
    }

    /**
     * Download media files from Confluence
     */
    private function downloadConfluenceMedia(
        ConfluenceApiImporter $apiImporter,
        $intermediateFormat,
        string $tempDir,
        string $language
    ): int {
        $count = 0;

        foreach ($intermediateFormat->mediaDownloads as $media) {
            try {
                // Determine page directory
                $pageSlug = $media->pageSlug;
                $mediaDir = $tempDir . '/' . $language . '/' . $pageSlug . '/images';

                if (!is_dir($mediaDir)) {
                    mkdir($mediaDir, 0755, true);
                }

                // Download file (this would need the page ID, which we'd need to track)
                // For now, skip actual download - this would be implemented fully later
                $this->logger->info('Media download placeholder: ' . $media->targetFilename);

                // In full implementation:
                // $content = $apiImporter->downloadAttachment($pageId, $media->targetFilename);
                // if ($content) {
                //     file_put_contents($mediaDir . '/' . $media->targetFilename, $content);
                //     $count++;
                // }

            } catch (\Exception $e) {
                $this->logger->warning('Failed to download media: ' . $e->getMessage());
            }
        }

        return $count;
    }

    /**
     * Create ZIP archive from directory
     */
    private function createZipFromDirectory(string $sourceDir, string $zipPath): void {
        $zip = new \ZipArchive();
        if ($zip->open($zipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
            throw new \Exception('Failed to create ZIP archive');
        }

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
                if ($relativePath !== 'confluence-import.zip') {
                    $zip->addFile($filePath, $relativePath);
                }
            }
        }

        $zip->close();
    }

    /**
     * Cleanup temporary directory
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
}
