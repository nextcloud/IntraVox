<?php
declare(strict_types=1);

namespace OCA\IntraVox\Controller;

use OCA\IntraVox\Service\Import\ConfluenceHtmlImportOrchestrator;
use OCA\IntraVox\Service\Import\ZipUploadValidator;
use OCA\IntraVox\Service\ImportService;
use OCA\IntraVox\Service\PageService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IGroupManager;
use OCP\IRequest;
use OCP\IUserSession;
use Psr\Log\LoggerInterface;

/**
 * Importing content into IntraVox: an IntraVox export ZIP, or a Confluence HTML
 * export.
 *
 * Split out of ApiController (PR-A). Both endpoints are admin-only, checked in
 * the body rather than by attribute, and both accept an uploaded file — which is
 * why validateParentPageId() travels with them: it is the IDOR guard on the
 * parent page an import is grafted onto, and importConfluenceHtml is its only
 * caller.
 *
 * The actual work lives in the service layer (PR-B): ZipUploadValidator decides
 * whether the upload is a ZIP, ConfluenceHtmlImportOrchestrator does the
 * conversion. What is left here is HTTP.
 */
class ImportApiController extends Controller {
    use ApiErrorTrait;
    use ChecksAdminAccess;

    public function __construct(
        string $appName,
        IRequest $request,
        private PageService $pageService,
        private ImportService $importService,
        private ZipUploadValidator $zipUploads,
        private ConfluenceHtmlImportOrchestrator $confluenceImport,
        private IGroupManager $groupManager,
        private IUserSession $userSession,
        private LoggerInterface $logger,
    ) {
        parent::__construct($appName, $request);
    }

    protected function getLogger(): LoggerInterface {
        return $this->logger;
    }
    /**
     * Validate parentPageId parameter for import operations
     *
     * Security: Prevents IDOR attacks by validating:
     * 1. Parent page exists
     * 2. User has write permission on parent
     * 3. Parent is in the same language (groupfolder)
     *
     * @param string $parentPageId The parent page unique ID
     * @param string $targetLanguage The target language for import
     * @return array{valid: bool, error?: string, page?: array} Validation result
     */
    private function validateParentPageId(string $parentPageId, string $targetLanguage): array {
        try {
            $parentPage = $this->pageService->getPage($parentPageId);
        } catch (\Exception $e) {
            $this->logger->warning('[ApiController] Parent page validation failed: page not found', [
                'parentPageId' => $parentPageId,
                'error' => $e->getMessage()
            ]);
            return [
                'valid' => false,
                'error' => 'Parent page not found'
            ];
        }

        // Check write permission
        if (!($parentPage['permissions']['canWrite'] ?? false)) {
            $this->logger->warning('[ApiController] Parent page validation failed: no write permission', [
                'parentPageId' => $parentPageId,
                'targetLanguage' => $targetLanguage
            ]);
            return [
                'valid' => false,
                'error' => 'No write permission for parent page'
            ];
        }

        // Check same language (prevents cross-groupfolder imports)
        $parentLanguage = $parentPage['language'] ?? null;
        if ($parentLanguage !== $targetLanguage) {
            $this->logger->warning('[ApiController] Parent page validation failed: language mismatch', [
                'parentPageId' => $parentPageId,
                'parentLanguage' => $parentLanguage,
                'targetLanguage' => $targetLanguage
            ]);
            return [
                'valid' => false,
                'error' => 'Parent page must be in the same language as import target'
            ];
        }

        return [
            'valid' => true,
            'page' => $parentPage
        ];
    }
    /**
     * Import from uploaded ZIP file
     * Admin only
     *
     * @return JSONResponse
     */
    public function importZip(): JSONResponse {
        // Security: Only admins can import
        if (!$this->isAdmin()) {
            return new JSONResponse(
                ['error' => 'Admin access required'],
                Http::STATUS_FORBIDDEN
            );
        }

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

            $this->zipUploads->assertIsZip($file);

            $importComments = $this->request->getParam('importComments', '1') === '1';
            $overwrite = $this->request->getParam('overwrite', '0') === '1';
            $autoCreateMetaVoxFields = $this->request->getParam('autoCreateMetaVoxFields', '0') === '1';

            $zipContent = file_get_contents($file['tmp_name']);
            $stats = $this->importService->importFromZip($zipContent, $importComments, $overwrite, null, $autoCreateMetaVoxFields);

            return new JSONResponse([
                'success' => true,
                'stats' => $stats,
            ]);
        } catch (\OCA\IntraVox\Exception\InvalidImportException $e) {
            // Validation errors are safe to forward to the client verbatim —
            // they describe the upload, never internal paths or IDs. See #52.
            // The frontend maps `errorCode` to a translated string; `error` is
            // an English fallback for non-UI consumers (curl, scripts).
            $this->logger->info('IntraVox Import: ZIP rejected during validation', [
                'errorCode' => $e->getErrorCode(),
                'message' => $e->getMessage(),
            ]);
            return new JSONResponse(
                [
                    'error' => $e->getMessage(),
                    'errorCode' => $e->getErrorCode(),
                    'params' => $e->getParams(),
                ],
                Http::STATUS_BAD_REQUEST
            );
        } catch (\Exception $e) {
            $errorId = uniqid('err_');
            $this->logger->error('IntraVox Import: ZIP import failed', [
                'errorId' => $errorId,
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
            return new JSONResponse(
                ['error' => 'Import failed. Please check the ZIP file format and try again.', 'errorId' => $errorId],
                Http::STATUS_INTERNAL_SERVER_ERROR
            );
        }
    }
    /**
     * Import from Confluence HTML export ZIP file
     * Admin only
     *
     * @return JSONResponse
     */
    public function importConfluenceHtml(): JSONResponse {
        // Security: Only admins can import
        if (!$this->isAdmin()) {
            return new JSONResponse(
                ['error' => 'Admin access required'],
                Http::STATUS_FORBIDDEN
            );
        }

        $this->logger->info('Confluence HTML import endpoint called');

        try {
            $file = $this->request->getUploadedFile('file');
            $language = $this->request->getParam('language', 'nl');
            $parentPageId = $this->request->getParam('parentPageId', null);

            $this->logger->info('Import request received', [
                'file' => $file ? $file['name'] : 'no file',
                'language' => $language,
                'parentPageId' => $parentPageId,
            ]);

            if (!$file || $file['error'] !== UPLOAD_ERR_OK) {
                $this->logger->warning('File upload error', ['error' => $file['error'] ?? 'no file']);
                return new JSONResponse(
                    ['error' => 'No file uploaded or upload error'],
                    Http::STATUS_BAD_REQUEST
                );
            }

            $this->zipUploads->assertIsZip($file);

            // Security: Validate parentPageId before import (IDOR prevention)
            if ($parentPageId) {
                $validation = $this->validateParentPageId($parentPageId, $language);
                if (!$validation['valid']) {
                    return new JSONResponse(
                        ['error' => $validation['error']],
                        Http::STATUS_BAD_REQUEST
                    );
                }
            }

            $this->logger->info('Starting Confluence HTML import', [
                'filename' => $file['name'],
                'size' => $file['size'],
                'language' => $language,
            ]);

            $result = $this->confluenceImport->importFromUploadedZip(
                $file['tmp_name'],
                $language,
                $parentPageId
            );

            return new JSONResponse([
                'success' => true,
                'stats' => $result['stats'],
                'pages' => $result['pages'],
                'message' => 'Confluence HTML export imported successfully',
            ]);
        } catch (\OCA\IntraVox\Exception\InvalidImportException $e) {
            $this->logger->info('Confluence HTML import: validation failed', [
                'errorCode' => $e->getErrorCode(),
                'message' => $e->getMessage(),
            ]);
            return new JSONResponse(
                [
                    'error' => $e->getMessage(),
                    'errorCode' => $e->getErrorCode(),
                    'params' => $e->getParams(),
                ],
                Http::STATUS_BAD_REQUEST
            );
        } catch (\Exception $e) {
            $errorId = uniqid('err_');
            $this->logger->error('Confluence HTML import failed', [
                'errorId' => $errorId,
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
            return new JSONResponse(
                ['error' => 'Confluence import failed. Please check the export format and try again.', 'errorId' => $errorId],
                Http::STATUS_INTERNAL_SERVER_ERROR
            );
        }
    }
}
