<?php
declare(strict_types=1);

namespace OCA\IntraVox\Service;

use OCP\Files\File;
use OCP\Files\Folder;
use OCP\ITempManager;
use Psr\Log\LoggerInterface;

/**
 * Service for exporting IntraVox pages, navigation, and engagement data
 */
class ExportService {
    public function __construct(
        private PageService $pageService,
        private CommentService $commentService,
        private NavigationService $navigationService,
        private FooterService $footerService,
        private SetupService $setupService,
        private ITempManager $tempManager,
        private LoggerInterface $logger
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
            'exportVersion' => '1.0',
            'exportDate' => (new \DateTime())->format('c'),
            'language' => $language,
            'navigation' => $this->navigationService->getNavigation($language),
            'footer' => $this->footerService->getFooter($language),
            'pages' => []
        ];

        foreach ($pages as $page) {
            $uniqueId = $page['uniqueId'] ?? '';
            if (empty($uniqueId)) {
                continue;
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

            $export['pages'][] = $data;
        }

        $this->logger->info('Export complete: ' . count($export['pages']) . ' pages');

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

                // Skip _media folders
                if ($nodeName === '_media') {
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
     * Recursively copy _media folders
     *
     * @param Folder $folder Source folder
     * @param string $targetPath Target path
     */
    private function copyMediaRecursive(Folder $folder, string $targetPath): void {
        foreach ($folder->getDirectoryListing() as $node) {
            $nodeName = $node->getName();

            if ($nodeName === '_media' && $node instanceof Folder) {
                // Copy entire _media folder
                $mediaTargetPath = $targetPath . '/_media';
                if (!is_dir($mediaTargetPath)) {
                    mkdir($mediaTargetPath, 0755, true);
                }

                foreach ($node->getDirectoryListing() as $file) {
                    if ($file instanceof File) {
                        try {
                            file_put_contents(
                                $mediaTargetPath . '/' . $file->getName(),
                                $file->getContent()
                            );
                        } catch (\Exception $e) {
                            $this->logger->warning('Failed to copy file: ' . $file->getName());
                        }
                    }
                }
            } elseif ($node instanceof Folder && $nodeName !== '_media') {
                // Recursively process subfolders
                $this->copyMediaRecursive($node, $targetPath . '/' . $nodeName);
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
}
