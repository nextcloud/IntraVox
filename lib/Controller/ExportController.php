<?php
declare(strict_types=1);

namespace OCA\IntraVox\Controller;

use OCA\IntraVox\Service\ExportService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\JSONResponse;
use OCP\AppFramework\Http\DataDownloadResponse;
use OCP\IRequest;

/**
 * Controller for exporting IntraVox pages and content
 */
class ExportController extends Controller {
    public function __construct(
        string $appName,
        IRequest $request,
        private ExportService $exportService
    ) {
        parent::__construct($appName, $request);
    }

    /**
     * Get available languages for export
     *
     * @NoAdminRequired
     * @NoCSRFRequired
     * @return JSONResponse
     */
    public function getExportableLanguages(): JSONResponse {
        $languages = $this->exportService->getExportableLanguages();
        return new JSONResponse($languages);
    }

    /**
     * Export all pages for a language as JSON download
     *
     * @NoAdminRequired
     * @NoCSRFRequired
     * @param string $language Language code
     * @return DataDownloadResponse
     */
    public function exportLanguage(string $language): DataDownloadResponse {
        $includeComments = $this->request->getParam('includeComments', '1') === '1';

        $data = $this->exportService->exportLanguage($language, $includeComments);
        $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $filename = "intravox-export-{$language}-" . date('Y-m-d') . ".json";

        return new DataDownloadResponse($json, $filename, 'application/json');
    }

    /**
     * Export single page as JSON download
     *
     * @NoAdminRequired
     * @NoCSRFRequired
     * @param string $uniqueId Page unique ID
     * @return DataDownloadResponse|JSONResponse
     */
    public function exportPage(string $uniqueId): DataDownloadResponse|JSONResponse {
        $includeComments = $this->request->getParam('includeComments', '1') === '1';

        $data = $this->exportService->exportPage($uniqueId, $includeComments);

        if ($data === null) {
            return new JSONResponse(['error' => 'Page not found'], 404);
        }

        $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $safeId = preg_replace('/[^a-zA-Z0-9_-]/', '_', $uniqueId);
        $filename = "intravox-page-{$safeId}-" . date('Y-m-d') . ".json";

        return new DataDownloadResponse($json, $filename, 'application/json');
    }

    /**
     * Export all pages for a language as ZIP with media files
     *
     * @NoAdminRequired
     * @NoCSRFRequired
     * @param string $language Language code
     * @return DataDownloadResponse|JSONResponse
     */
    public function exportLanguageZip(string $language): DataDownloadResponse|JSONResponse {
        try {
            $includeComments = $this->request->getParam('includeComments', '1') === '1';

            $zipPath = $this->exportService->exportLanguageAsZip($language, $includeComments);
            $filename = "intravox-export-{$language}-" . date('Y-m-d') . ".zip";

            // Read ZIP content
            $zipContent = file_get_contents($zipPath);

            // Cleanup temp files
            @unlink($zipPath);
            $tempDir = dirname($zipPath);
            $this->cleanupTempDir($tempDir);

            return new DataDownloadResponse($zipContent, $filename, 'application/zip');
        } catch (\Exception $e) {
            return new JSONResponse(['error' => $e->getMessage()], Http::STATUS_INTERNAL_SERVER_ERROR);
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
}
