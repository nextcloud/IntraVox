<?php
declare(strict_types=1);

namespace OCA\IntraVox\Service\Import;

use OCA\IntraVox\Service\ImportService;
use OCP\ITempManager;
use Psr\Log\LoggerInterface;

/**
 * Turns an uploaded Confluence HTML export into imported pages.
 *
 * Extracted from ApiController::importConfluenceHtml (controller split, PR-B),
 * which was 166 lines — the longest method in that file — and did none of it
 * over HTTP: it built importers, converted formats, reassigned page parents,
 * wrote a temp directory, zipped it back up and cleaned up after itself.
 *
 * The reflection is gone. The controller used to do:
 *
 *     $m = (new \ReflectionClass($importer))->getMethod('convertToIntraVoxExport');
 *     $m->setAccessible(true);
 *
 * That existed only because the caller sat outside the class. Now that the call
 * lives in the service layer, convertToIntraVoxExport() is simply public on
 * AbstractImporter — which is what it always was in practice, since a caller
 * was already reaching it.
 *
 * The controller keeps what is genuinely HTTP: the admin check, the uploaded
 * file, and mapping exceptions onto status codes.
 */
class ConfluenceHtmlImportOrchestrator {
    public function __construct(
        private ImportService $importService,
        private ITempManager $tempManager,
        private LoggerInterface $logger,
    ) {
    }

    /**
     * @param string      $zipPath      path to the uploaded ZIP on local disk
     * @param string      $language     target language code
     * @param string|null $parentPageId uniqueId to graft root pages under, if any
     * @return array{stats: array, pages: int}
     */
    public function importFromUploadedZip(string $zipPath, string $language, ?string $parentPageId): array {
        $htmlImporter = new ConfluenceHtmlImporter($this->logger, new SafeZipExtractor($this->logger));
        $confluenceImporter = new ConfluenceImporter($this->logger);

        $intermediateFormat = $htmlImporter->importFromZip($zipPath, $language);

        $this->logger->info('Parsed Confluence HTML export', [
            'pages' => count($intermediateFormat->pages),
            'media' => count($intermediateFormat->mediaDownloads),
        ]);

        $export = $confluenceImporter->convertToIntraVoxExport($intermediateFormat);

        if ($parentPageId) {
            $export['pages'] = $this->graftOntoParent($export['pages'], $parentPageId);
        }

        $tempDir = $this->tempManager->getTemporaryFolder();
        if ($tempDir === false) {
            throw new \RuntimeException('Could not create a temporary folder for the import');
        }

        try {
            file_put_contents($tempDir . '/export.json', json_encode($export, JSON_PRETTY_PRINT));

            $zip = $tempDir . '/' . self::BUNDLE_NAME;
            $this->createZipFromDirectory($tempDir, $zip);

            $stats = $this->importService->importFromZip(
                (string) file_get_contents($zip),
                true,
                false,
                $parentPageId
            );
        } finally {
            // finally, not a trailing call: the temp directory holds the whole
            // export and must not survive a failed import.
            $this->cleanupTempDir($tempDir);
        }

        return ['stats' => $stats, 'pages' => count($intermediateFormat->pages)];
    }

    private const BUNDLE_NAME = 'confluence-html-import.zip';

    /**
     * Give every ROOT page of the import the chosen parent. Pages that already
     * have a parent inside the import keep it, so the imported hierarchy is
     * preserved and only hangs one level lower.
     *
     * @param array<int, array<string, mixed>> $pages
     * @return array<int, array<string, mixed>>
     */
    private function graftOntoParent(array $pages, string $parentPageId): array {
        $roots = 0;
        foreach ($pages as &$page) {
            if (empty($page['parentUniqueId'])) {
                $page['parentUniqueId'] = $parentPageId;
                $roots++;
            }
        }
        unset($page);

        $this->logger->info('Set parent page for root imported pages', [
            'parentPageId' => $parentPageId,
            'rootPagesCount' => $roots,
            'totalPages' => count($pages),
        ]);

        return $pages;
    }

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
                if ($relativePath !== self::BUNDLE_NAME) {
                    $zip->addFile($filePath, $relativePath);
                }
            }
        }

        $zip->close();
    }

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
