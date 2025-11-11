<?php
declare(strict_types=1);

namespace OCA\IntraVox\Service;

use OCP\Files\IRootFolder;
use OCP\Files\NotFoundException;
use OCP\IUserSession;
use OCP\IConfig;
use OCP\IDBConnection;
use Psr\Log\LoggerInterface;

class PageService {
    private const ALLOWED_WIDGET_TYPES = ['text', 'heading', 'image', 'link', 'file', 'divider'];
    private const ALLOWED_IMAGE_TYPES = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    private const MAX_IMAGE_SIZE = 5242880; // 5MB
    private const MAX_COLUMNS = 5;
    private const SUPPORTED_LANGUAGES = ['nl', 'en', 'de', 'fr'];
    private const DEFAULT_LANGUAGE = 'en';

    private IRootFolder $rootFolder;
    private IUserSession $userSession;
    private string $userId;
    private SetupService $setupService;
    private IConfig $config;
    private IDBConnection $db;
    private LoggerInterface $logger;

    public function __construct(
        IRootFolder $rootFolder,
        IUserSession $userSession,
        SetupService $setupService,
        IConfig $config,
        IDBConnection $db,
        LoggerInterface $logger,
        ?string $userId
    ) {
        $this->rootFolder = $rootFolder;
        $this->userSession = $userSession;
        $this->setupService = $setupService;
        $this->config = $config;
        $this->db = $db;
        $this->logger = $logger;
        $this->userId = $userId ?? '';
    }

    /**
     * Get the user's language preference
     */
    private function getUserLanguage(): string {
        if (!$this->userId) {
            return self::DEFAULT_LANGUAGE;
        }

        $lang = $this->config->getUserValue($this->userId, 'core', 'lang', self::DEFAULT_LANGUAGE);

        // Extract language code (e.g., 'nl_NL' -> 'nl')
        $langCode = explode('_', $lang)[0];

        // Check if supported, otherwise return default
        return in_array($langCode, self::SUPPORTED_LANGUAGES) ? $langCode : self::DEFAULT_LANGUAGE;
    }

    /**
     * Get the language folder within IntraVox
     */
    private function getLanguageFolder() {
        $baseFolder = $this->getIntraVoxFolder();
        $lang = $this->getUserLanguage();

        try {
            return $baseFolder->get($lang);
        } catch (NotFoundException $e) {
            // If language folder doesn't exist, try default language
            if ($lang !== self::DEFAULT_LANGUAGE) {
                try {
                    return $baseFolder->get(self::DEFAULT_LANGUAGE);
                } catch (NotFoundException $e2) {
                    // Create default language folder if it doesn't exist
                    return $baseFolder->newFolder(self::DEFAULT_LANGUAGE);
                }
            }
            // Create the requested language folder
            return $baseFolder->newFolder($lang);
        }
    }

    /**
     * Get the shared IntraVox folder (team folder)
     */
    private function getIntraVoxFolder() {
        return $this->setupService->getSharedFolder();
    }

    /**
     * Check if a page ID already exists (recursively through all folders)
     */
    private function pageIdExists(string $id): bool {
        $folder = $this->getLanguageFolder();
        return $this->findPageById($folder, $id) !== null;
    }

    /**
     * Recursively find a page by ID
     */
    private function findPageById($folder, string $id): ?array {
        // Check root for home.json
        if ($id === 'home') {
            try {
                $file = $folder->get('home.json');
                return ['file' => $file, 'folder' => $folder];
            } catch (NotFoundException $e) {
                // Continue searching
            }
        }

        // Check if there's a folder with this ID
        try {
            $pageFolder = $folder->get($id);
            if ($pageFolder->getType() === \OCP\Files\FileInfo::TYPE_FOLDER) {
                $jsonFile = $pageFolder->get($id . '.json');
                return ['file' => $jsonFile, 'folder' => $pageFolder];
            }
        } catch (NotFoundException $e) {
            // Continue searching
        }

        // Recursively search subfolders
        foreach ($folder->getDirectoryListing() as $item) {
            if ($item->getType() === \OCP\Files\FileInfo::TYPE_FOLDER) {
                $result = $this->findPageById($item, $id);
                if ($result !== null) {
                    return $result;
                }
            }
        }

        return null;
    }

    /**
     * List all pages (recursively)
     */
    public function listPages(): array {
        $folder = $this->getLanguageFolder();
        $pages = [];

        // Check for home.json in root
        try {
            $homeFile = $folder->get('home.json');
            $content = $homeFile->getContent();
            $data = json_decode($content, true);

            if ($data && isset($data['id'], $data['title'])) {
                $pages[] = [
                    'id' => $data['id'],
                    'title' => $data['title'],
                    'modified' => $data['modified'] ?? $homeFile->getMTime()
                ];
            }
        } catch (NotFoundException $e) {
            // No home page yet
        }

        // Recursively find all pages in subfolders
        $this->findPagesInFolder($folder, $pages);

        return $pages;
    }

    /**
     * Recursively find pages in folders
     */
    private function findPagesInFolder($folder, array &$pages): void {
        foreach ($folder->getDirectoryListing() as $item) {
            if ($item->getType() === \OCP\Files\FileInfo::TYPE_FOLDER) {
                $folderName = $item->getName();

                // Skip special folders
                if (in_array($folderName, ['images', 'files'])) {
                    continue;
                }

                // Look for {foldername}.json inside the folder
                try {
                    $jsonFile = $item->get($folderName . '.json');

                    // Check if file is readable before trying to get content
                    if (!$jsonFile->isReadable()) {
                        continue;
                    }

                    // Suppress any warnings from getContent and catch all exceptions
                    $content = @$jsonFile->getContent();

                    if ($content === false || $content === null) {
                        continue;
                    }

                    $data = json_decode($content, true);

                    if ($data && isset($data['id'], $data['title'])) {
                        $pages[] = [
                            'id' => $data['id'],
                            'title' => $data['title'],
                            'modified' => $data['modified'] ?? $jsonFile->getMTime()
                        ];
                    }
                } catch (\Exception $e) {
                    // This folder doesn't contain a valid page or can't be read, continue
                } catch (\Throwable $e) {
                    // Catch any other errors including PHP errors
                    continue;
                }

                // Recursively search subfolders
                $this->findPagesInFolder($item, $pages);
            }
        }
    }

    /**
     * Get a specific page
     */
    public function getPage(string $id): array {
        $id = $this->sanitizeId($id);
        $result = $this->findPageById($this->getLanguageFolder(), $id);

        if ($result === null) {
            throw new \Exception('Page not found');
        }

        $content = $result['file']->getContent();
        $data = json_decode($content, true);

        if (!$data) {
            throw new \Exception('Invalid page data');
        }

        return $this->sanitizePage($data);
    }

    /**
     * Create a new page
     */
    public function createPage(array $data): array {
        if (!isset($data['id']) || !isset($data['title'])) {
            throw new \InvalidArgumentException('Missing required fields: id, title');
        }

        $data['id'] = $this->sanitizeId($data['id']);

        // If ID already exists, append a number to make it unique
        $originalId = $data['id'];
        $counter = 2;
        while ($this->pageIdExists($data['id'])) {
            $data['id'] = $originalId . '-' . $counter;
            $counter++;
        }

        $data['created'] = time();
        $data['modified'] = time();

        // Preserve uniqueId if provided (for internal references)
        if (isset($data['uniqueId'])) {
            $data['uniqueId'] = $data['uniqueId'];
        }

        $validatedData = $this->validateAndSanitizePage($data);

        $folder = $this->getLanguageFolder();
        $pageId = $data['id'];

        // Special handling for home page
        if ($pageId === 'home') {
            // Create home.json in root
            $file = $folder->newFile('home.json');
            $file->putContent(json_encode($validatedData, JSON_PRETTY_PRINT));

            // Create images folder for home if it doesn't exist
            try {
                $folder->get('images');
            } catch (NotFoundException $e) {
                $folder->newFolder('images');
            }
        } else {
            // Create folder for page
            try {
                $pageFolder = $folder->newFolder($pageId);
            } catch (\Exception $e) {
                throw new \InvalidArgumentException('Failed to create page folder: ' . $e->getMessage());
            }

            // Create {pageId}.json inside the folder
            try {
                $file = $pageFolder->newFile($pageId . '.json');
                $file->putContent(json_encode($validatedData, JSON_PRETTY_PRINT));
            } catch (\Exception $e) {
                throw new \InvalidArgumentException('Failed to create page file: ' . $e->getMessage());
            }

            // Create images subfolder
            try {
                $pageFolder->newFolder('images');
            } catch (\Exception $e) {
                // Images folder might already exist, that's okay
            }

            // Force Nextcloud to update the file cache so the folder is visible in Files app
            try {
                $scanner = $pageFolder->getStorage()->getScanner();
                $scanner->scan($pageFolder->getInternalPath());
            } catch (\Exception $e) {
                // If scanning fails, that's okay - the folder still exists
            }
        }

        return $validatedData;
    }

    /**
     * Update an existing page
     */
    public function updatePage(string $id, array $data): array {
        try {
            $id = $this->sanitizeId($id);
        } catch (\Exception $e) {
            throw new \InvalidArgumentException('Failed to sanitize page ID: ' . $e->getMessage());
        }

        // Get the current user
        $user = $this->userSession->getUser();
        if (!$user) {
            throw new \InvalidArgumentException('No user in session');
        }

        try {
            // First find via root to get the path
            $result = $this->findPageById($this->getLanguageFolder(), $id);
        } catch (\Exception $e) {
            throw new \InvalidArgumentException('Failed to find page by ID: ' . $e->getMessage());
        }

        if ($result === null) {
            throw new \InvalidArgumentException('Page not found: ' . $id);
        }

        // Get the file
        $file = $result['file'];

        try {
            $existingContent = $file->getContent();
            $existingData = json_decode($existingContent, true);
        } catch (\Exception $e) {
            throw new \InvalidArgumentException('Failed to read existing page data: ' . $e->getMessage());
        }

        // Preserve creation time and ID
        $data['id'] = $id;
        $data['created'] = $existingData['created'] ?? time();
        $data['modified'] = time();

        try {
            $validatedData = $this->validateAndSanitizePage($data);
        } catch (\Exception $e) {
            throw new \InvalidArgumentException('Page validation failed: ' . $e->getMessage());
        }

        try {
            // Manually create a version before updating
            // This is necessary because Nextcloud's automatic versioning doesn't work
            // when files are accessed via rootFolder instead of user mounts
            $this->createManualVersion($file, $user);

            // Update the file
            $file->putContent(json_encode($validatedData, JSON_PRETTY_PRINT));

            $this->logger->info('[updatePage] File updated successfully', [
                'pageId' => $id,
                'fileId' => $file->getId()
            ]);
        } catch (\Exception $e) {
            throw new \InvalidArgumentException('Failed to write updated page data: ' . $e->getMessage());
        }

        return $validatedData;
    }

    /**
     * Manually create a version of a file
     * This is needed because Nextcloud's automatic versioning doesn't trigger
     * when we access files via rootFolder
     */
    private function createManualVersion(\OCP\Files\File $file, \OCP\IUser $user): void {
        try {
            $groupFolderId = $this->setupService->getGroupFolderId();
            $groupfolderFileId = $file->getId();

            $this->logger->info('[createManualVersion] Creating version', [
                'groupfolderFileId' => $groupfolderFileId,
                'userId' => $user->getUID()
            ]);

            // Find the user mount fileId where versions should be stored
            $versionFileId = $this->findVersionFileId($groupFolderId, $groupfolderFileId, $file->getInternalPath());

            // If no user mount fileId exists yet, use the groupfolder fileId
            // This will happen for new pages that haven't been accessed via Files app yet
            if (!$versionFileId) {
                $versionFileId = $groupfolderFileId;
                $this->logger->info('[createManualVersion] No user mount fileId found, using groupfolder fileId', [
                    'versionFileId' => $versionFileId
                ]);
            }

            // Get current file content to save as version
            $currentContent = $file->getContent();
            $timestamp = time();

            // Create version directory if it doesn't exist
            $versionDir = "/var/www/nextcloud/data/__groupfolders/{$groupFolderId}/versions/{$versionFileId}";
            if (!is_dir($versionDir)) {
                mkdir($versionDir, 0755, true);
                chown($versionDir, 'www-data');
                chgrp($versionDir, 'www-data');
            }

            // Save version file
            $versionFile = "{$versionDir}/{$timestamp}";
            file_put_contents($versionFile, $currentContent);
            chown($versionFile, 'www-data');
            chgrp($versionFile, 'www-data');
            chmod($versionFile, 0644);

            $this->logger->info('[createManualVersion] Version created', [
                'groupfolderFileId' => $groupfolderFileId,
                'versionFileId' => $versionFileId,
                'timestamp' => $timestamp,
                'versionFile' => $versionFile
            ]);
        } catch (\Exception $e) {
            // Log but don't throw - versioning failure shouldn't block saves
            $this->logger->warning('[createManualVersion] Failed to create version', [
                'error' => $e->getMessage(),
                'fileId' => $file->getId()
            ]);
        }
    }

    /**
     * Delete a page and all its assets
     */
    public function deletePage(string $id): void {
        $id = $this->sanitizeId($id);

        if ($id === 'home') {
            throw new \InvalidArgumentException('Cannot delete home page');
        }

        $result = $this->findPageById($this->getLanguageFolder(), $id);

        if ($result === null) {
            throw new \Exception('Page not found');
        }

        // Delete the entire folder (includes .json, images/, files/)
        $result['folder']->delete();
    }

    /**
     * Upload an image for a specific page
     */
    public function uploadImage(string $pageId, array $file): string {
        if (!isset($file['tmp_name']) || !isset($file['name'])) {
            throw new \InvalidArgumentException('Invalid file upload');
        }

        $pageId = $this->sanitizeId($pageId);

        // Validate file type
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        if (!in_array($mimeType, self::ALLOWED_IMAGE_TYPES)) {
            throw new \InvalidArgumentException('Invalid image type. Allowed: JPEG, PNG, GIF, WebP');
        }

        // Validate file size
        if ($file['size'] > self::MAX_IMAGE_SIZE) {
            throw new \InvalidArgumentException('Image too large. Max size: 5MB');
        }

        // Sanitize filename
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = uniqid('img_', true) . '.' . $extension;

        $folder = $this->getLanguageFolder();

        // Get images folder for this page
        if ($pageId === 'home') {
            // Home images are in root/images/
            try {
                $imagesFolder = $folder->get('images');
            } catch (NotFoundException $e) {
                $imagesFolder = $folder->newFolder('images');
            }
        } else {
            // Other pages have images in {pageId}/images/
            try {
                $pageFolder = $folder->get($pageId);
                try {
                    $imagesFolder = $pageFolder->get('images');
                } catch (NotFoundException $e) {
                    $imagesFolder = $pageFolder->newFolder('images');
                }
            } catch (NotFoundException $e) {
                throw new \Exception('Page folder not found');
            }
        }

        $newFile = $imagesFolder->newFile($filename);
        $content = file_get_contents($file['tmp_name']);
        $newFile->putContent($content);

        return $filename;
    }

    /**
     * Get an image for a specific page
     */
    public function getImage(string $pageId, string $filename) {
        $pageId = $this->sanitizeId($pageId);
        $filename = basename($filename); // Prevent directory traversal

        $folder = $this->getLanguageFolder();

        try {
            // Get images folder for this page
            if ($pageId === 'home') {
                $imagesFolder = $folder->get('images');
            } else {
                $pageFolder = $folder->get($pageId);
                $imagesFolder = $pageFolder->get('images');
            }

            $file = $imagesFolder->get($filename);

            if ($file->getType() !== \OCP\Files\FileInfo::TYPE_FILE) {
                throw new \Exception('Not a file');
            }

            // Get mime type
            $mimeType = $file->getMimeType();

            // Create stream response
            $response = new \OCP\AppFramework\Http\StreamResponse($file->fopen('rb'));
            $response->addHeader('Content-Type', $mimeType);
            $response->addHeader('Content-Disposition', 'inline; filename="' . $file->getName() . '"');
            $response->addHeader('Cache-Control', 'public, max-age=31536000');

            return $response;
        } catch (NotFoundException $e) {
            throw new \Exception('Image not found');
        }
    }

    /**
     * Sanitize page ID
     */
    private function sanitizeId(string $id): string {
        // Only allow alphanumeric, hyphens, and underscores
        $id = preg_replace('/[^a-zA-Z0-9_-]/', '', $id);

        if (empty($id)) {
            throw new \InvalidArgumentException('Invalid page ID');
        }

        return $id;
    }

    /**
     * Validate and sanitize page data
     */
    private function validateAndSanitizePage(array $data): array {
        $sanitized = [
            'id' => $this->sanitizeId($data['id']),
            'title' => $this->sanitizeText($data['title']),
            'created' => $data['created'] ?? time(),
            'modified' => $data['modified'] ?? time(),
            'layout' => [
                'columns' => 1, // Default to 1 column
                'rows' => []
            ]
        ];

        // Preserve uniqueId if provided (for internal references)
        if (isset($data['uniqueId'])) {
            $sanitized['uniqueId'] = $data['uniqueId'];
        }

        if (isset($data['layout']['rows']) && is_array($data['layout']['rows'])) {
            foreach ($data['layout']['rows'] as $row) {
                if (isset($row['widgets']) && is_array($row['widgets'])) {
                    $sanitizedWidgets = [];

                    foreach ($row['widgets'] as $widget) {
                        $sanitizedWidget = $this->sanitizeWidget($widget);
                        if ($sanitizedWidget) {
                            $sanitizedWidgets[] = $sanitizedWidget;
                        }
                    }

                    $sanitizedRow = ['widgets' => $sanitizedWidgets];

                    // Preserve row-specific column count if set
                    if (isset($row['columns'])) {
                        $sanitizedRow['columns'] = $this->validateColumns($row['columns']);
                    }

                    // Preserve row background color if set
                    if (isset($row['backgroundColor'])) {
                        $sanitizedRow['backgroundColor'] = $this->sanitizeBackgroundColor($row['backgroundColor']);
                    }

                    if (!empty($sanitizedWidgets)) {
                        $sanitized['layout']['rows'][] = $sanitizedRow;
                    }
                }
            }
        }

        return $sanitized;
    }

    /**
     * Sanitize widget data
     */
    private function sanitizeWidget(array $widget): ?array {
        if (!isset($widget['type']) || !in_array($widget['type'], self::ALLOWED_WIDGET_TYPES)) {
            return null;
        }

        $sanitized = [
            'type' => $widget['type'],
            'column' => max(1, min((int)($widget['column'] ?? 1), self::MAX_COLUMNS)),
            'order' => (int)($widget['order'] ?? 1)
        ];

        switch ($widget['type']) {
            case 'text':
                // Text widgets now contain HTML from rich text editor - sanitize HTML not text
                $sanitized['content'] = $this->sanitizeHtml($widget['content'] ?? '');
                break;

            case 'heading':
                $sanitized['content'] = $this->sanitizeText($widget['content'] ?? '');
                $sanitized['level'] = max(1, min((int)($widget['level'] ?? 2), 6));
                break;

            case 'image':
                $sanitized['src'] = $this->sanitizePath($widget['src'] ?? '');
                $sanitized['alt'] = $this->sanitizeText($widget['alt'] ?? '');
                break;

            case 'link':
                $sanitized['url'] = $this->sanitizeUrl($widget['url'] ?? '');
                $sanitized['text'] = $this->sanitizeText($widget['text'] ?? '');
                break;

            case 'file':
                $sanitized['path'] = $this->sanitizePath($widget['path'] ?? '');
                $sanitized['name'] = $this->sanitizeText($widget['name'] ?? '');
                break;

            case 'divider':
                // Divider has no additional properties
                break;
        }

        return $sanitized;
    }

    /**
     * Sanitize text content (prevent XSS)
     */
    private function sanitizeText(string $text): string {
        return htmlspecialchars($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }

    /**
     * Sanitize HTML content (allow safe formatting tags, prevent XSS)
     */
    private function sanitizeHtml(string $html): string {
        // Define allowed tags and attributes
        $allowedTags = '<p><br><strong><em><u><s><ul><ol><li><a><h1><h2><h3><h4><h5><h6>';

        // Strip all tags except allowed ones
        $cleaned = strip_tags($html, $allowedTags);

        // Additional XSS prevention: remove any event handlers or javascript
        $cleaned = preg_replace('/on\w+\s*=\s*["\']?[^"\']*["\']?/i', '', $cleaned);
        $cleaned = preg_replace('/javascript:/i', '', $cleaned);

        // Clean up any malformed HTML entities
        $cleaned = preg_replace('/&(?![a-z]+;|#[0-9]+;|#x[0-9a-f]+;)/i', '&amp;', $cleaned);

        return $cleaned;
    }

    /**
     * Sanitize file path (prevent directory traversal)
     */
    private function sanitizePath(string $path): string {
        // Remove any path traversal attempts
        $path = str_replace(['../', '..\\'], '', $path);
        $path = ltrim($path, '/\\');

        return $path;
    }

    /**
     * Sanitize URL
     */
    private function sanitizeUrl(string $url): string {
        $url = filter_var($url, FILTER_SANITIZE_URL);

        // Only allow http, https, and relative URLs
        if (!empty($url) && !preg_match('/^(https?:\/\/|\/)/i', $url)) {
            return '';
        }

        return $url;
    }

    /**
     * Validate column count
     */
    private function validateColumns(int $columns): int {
        return max(1, min($columns, self::MAX_COLUMNS));
    }

    /**
     * Sanitize background color (only allow theme CSS variables or empty string)
     */
    private function sanitizeBackgroundColor(string $color): string {
        // Empty string is allowed (transparent/default)
        if (empty($color)) {
            return '';
        }

        // Only allow Nextcloud theme CSS variables
        $allowedColors = [
            'var(--color-primary-element)',
            'var(--color-primary-element-light)',
            'var(--color-background-hover)'
        ];

        if (in_array($color, $allowedColors)) {
            return $color;
        }

        // Invalid color, return empty (default)
        return '';
    }

    /**
     * Sanitize page for output (decode HTML entities for display)
     */
    private function sanitizePage(array $data): array {
        // Return as-is since we already sanitized on input
        // The frontend will handle the escaped content properly
        return $data;
    }

    /**
     * Get all versions of a page
     * @throws \Exception if page not found or version directory cannot be accessed
     */
    public function getPageVersions(string $pageId): array {
        // First find the page via user path to verify it exists
        $result = $this->findPageById($this->getLanguageFolder(), $pageId);

        if (!$result) {
            throw new \Exception('Page not found: ' . $pageId);
        }

        $file = $result['file'];
        $fileId = $file->getId();
        $versions = [];

        try {
            $groupFolderId = $this->setupService->getGroupFolderId();

            $this->logger->info('[getPageVersions] Looking for versions', [
                'pageId' => $pageId,
                'fileId' => $fileId,
                'groupFolderId' => $groupFolderId
            ]);

            // The problem: when files are accessed via __groupfolders path, they have one fileId (e.g., 421)
            // But when the same file is accessed via user mount (files/...), it has a different fileId (e.g., 763)
            // Versions are stored using the user mount fileId, so we need to find it
            $versionFileId = $this->findVersionFileId($groupFolderId, $fileId, $file->getInternalPath());

            if (!$versionFileId) {
                $this->logger->info('[getPageVersions] No version fileId found', [
                    'groupfolderFileId' => $fileId
                ]);
                return [];
            }

            $this->logger->info('[getPageVersions] Found version fileId', [
                'groupfolderFileId' => $fileId,
                'versionFileId' => $versionFileId
            ]);

            // Read versions directly from filesystem using the version fileId
            $versionDir = "/var/www/nextcloud/data/__groupfolders/{$groupFolderId}/versions/{$versionFileId}";

            if (!is_dir($versionDir)) {
                $this->logger->info('[getPageVersions] No versions directory', [
                    'versionDir' => $versionDir
                ]);
                return [];
            }

            // List all version files
            $versionFiles = scandir($versionDir);

            foreach ($versionFiles as $versionFile) {
                if ($versionFile === '.' || $versionFile === '..') {
                    continue;
                }

                $timestamp = (int)$versionFile;
                $versionPath = "{$versionDir}/{$versionFile}";

                if (!is_file($versionPath)) {
                    continue;
                }

                $size = filesize($versionPath);

                $versions[] = [
                    'timestamp' => $timestamp,
                    'date' => date('Y-m-d H:i:s', $timestamp),
                    'size' => $size,
                    'relativeDate' => $this->getRelativeTime($timestamp)
                ];
            }

            // Sort newest first
            usort($versions, fn($a, $b) => $b['timestamp'] <=> $a['timestamp']);

            $this->logger->info('[getPageVersions] Returning versions', [
                'count' => count($versions),
                'versionFileId' => $versionFileId
            ]);

            return $versions;
        } catch (\Exception $e) {
            $this->logger->error('[getPageVersions] Failed to retrieve versions', [
                'error' => $e->getMessage(),
                'pageId' => $pageId
            ]);
            throw new \Exception('Failed to retrieve versions: ' . $e->getMessage());
        }
    }

    /**
     * Find the fileId used for versioning
     *
     * When files are accessed via __groupfolders path, they have one fileId.
     * But versions are stored using the user mount fileId (files/... path).
     * This method queries the database to find the user mount fileId that corresponds
     * to the same file path.
     */
    private function findVersionFileId(int $groupFolderId, int $groupfolderFileId, string $internalPath): ?int {
        // First try the groupfolder fileId itself
        $baseVersionDir = "/var/www/nextcloud/data/__groupfolders/{$groupFolderId}/versions";
        if (is_dir("{$baseVersionDir}/{$groupfolderFileId}")) {
            $this->logger->info('[findVersionFileId] Found versions under groupfolder fileId', [
                'fileId' => $groupfolderFileId
            ]);
            return $groupfolderFileId;
        }

        // Query database to find user mount fileId based on path
        // The groupfolder path is: __groupfolders/{folderId}/files/{lang}/{page}/{page}.json
        // The user mount path is: files/{lang}/{page}/{page}.json
        // We need to extract the relative path and match it
        try {
            $qb = $this->db->getQueryBuilder();

            // Get the groupfolder file info first
            $qb->select('path', 'storage')
                ->from('filecache')
                ->where($qb->expr()->eq('fileid', $qb->createNamedParameter($groupfolderFileId)));

            $result = $qb->executeQuery();
            $groupfolderFile = $result->fetch();
            $result->closeCursor();

            if (!$groupfolderFile) {
                $this->logger->warning('[findVersionFileId] Groupfolder file not found in database', [
                    'fileId' => $groupfolderFileId
                ]);
                return null;
            }

            // Extract the relative path after "files/"
            // e.g., "__groupfolders/4/files/en/home.json" -> "en/home.json"
            $groupfolderPath = $groupfolderFile['path'];
            $this->logger->info('[findVersionFileId] Groupfolder path', [
                'path' => $groupfolderPath
            ]);

            if (preg_match('#__groupfolders/\d+/files/(.+)$#', $groupfolderPath, $matches)) {
                $relativePath = $matches[1];
                $this->logger->info('[findVersionFileId] Extracted relative path', [
                    'relativePath' => $relativePath
                ]);

                // Now find the user mount fileId with path "files/{relativePath}"
                $userPath = "files/{$relativePath}";

                $qb = $this->db->getQueryBuilder();
                $qb->select('fileid', 'storage')
                    ->from('filecache')
                    ->where($qb->expr()->eq('path', $qb->createNamedParameter($userPath)))
                    ->orderBy('fileid', 'DESC') // Get the most recent one
                    ->setMaxResults(1);

                $result = $qb->executeQuery();
                $userFile = $result->fetch();
                $result->closeCursor();

                if ($userFile) {
                    $userFileId = (int)$userFile['fileid'];

                    // Verify this fileId has a versions directory
                    if (is_dir("{$baseVersionDir}/{$userFileId}")) {
                        $this->logger->info('[findVersionFileId] Found user mount fileId with versions', [
                            'groupfolderFileId' => $groupfolderFileId,
                            'userFileId' => $userFileId,
                            'path' => $userPath
                        ]);
                        return $userFileId;
                    } else {
                        $this->logger->info('[findVersionFileId] Found user mount fileId but no versions directory', [
                            'userFileId' => $userFileId,
                            'path' => $userPath
                        ]);
                    }
                }
            }

            $this->logger->warning('[findVersionFileId] Could not find user mount fileId', [
                'groupfolderFileId' => $groupfolderFileId,
                'groupfolderPath' => $groupfolderPath
            ]);
            return null;

        } catch (\Exception $e) {
            $this->logger->error('[findVersionFileId] Database query failed', [
                'error' => $e->getMessage(),
                'groupfolderFileId' => $groupfolderFileId
            ]);
            return null;
        }
    }

    /**
     * Find a file by its ID within a folder
     */
    private function findFileByIdInFolder(\OCP\Files\Folder $folder, int $fileId): ?\OCP\Files\File {
        try {
            $files = $folder->getDirectoryListing();
            foreach ($files as $item) {
                if ($item->getId() === $fileId && $item instanceof \OCP\Files\File) {
                    return $item;
                }
                if ($item instanceof \OCP\Files\Folder) {
                    $found = $this->findFileByIdInFolder($item, $fileId);
                    if ($found) {
                        return $found;
                    }
                }
            }
        } catch (\Exception $e) {
            $this->logger->error('[findFileByIdInFolder] Error searching folder', [
                'error' => $e->getMessage()
            ]);
        }
        return null;
    }

    /**
     * Restore a specific version of a page
     * @throws \Exception if page or version not found
     */
    public function restorePageVersion(string $pageId, int $timestamp): array {
        $result = $this->findPageById($this->getLanguageFolder(), $pageId);

        if (!$result) {
            throw new \Exception('Page not found: ' . $pageId);
        }

        $file = $result['file'];
        $user = $this->userSession->getUser();

        if (!$user) {
            throw new \Exception('No user in session');
        }

        try {
            $groupFolderId = $this->setupService->getGroupFolderId();
            $fileId = $file->getId();

            $this->logger->info('[restorePageVersion] Restoring version', [
                'pageId' => $pageId,
                'timestamp' => $timestamp,
                'fileId' => $fileId
            ]);

            // Find the version fileId (may differ from groupfolder fileId)
            $versionFileId = $this->findVersionFileId($groupFolderId, $fileId, $file->getInternalPath());

            if (!$versionFileId) {
                throw new \Exception('No versions found for this file');
            }

            // Read version content from filesystem using version fileId
            $versionFile = "/var/www/nextcloud/data/__groupfolders/{$groupFolderId}/versions/{$versionFileId}/{$timestamp}";

            if (!file_exists($versionFile)) {
                throw new \Exception('Version file not found');
            }

            // Create a new version of current state before restoring
            $this->createManualVersion($file, $user);

            // Read version content
            $restoredContent = file_get_contents($versionFile);
            $restoredData = json_decode($restoredContent, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \Exception('Restored version contains invalid JSON data');
            }

            // Validate the restored data
            $validatedData = $this->validateAndSanitizePage($restoredData);

            // Write restored content to file
            $file->putContent(json_encode($validatedData, JSON_PRETTY_PRINT));

            $this->logger->info('[restorePageVersion] Version restored successfully', [
                'pageId' => $pageId,
                'timestamp' => $timestamp,
                'versionFileId' => $versionFileId
            ]);

            return $validatedData;
        } catch (\Exception $e) {
            $this->logger->error('[restorePageVersion] Failed to restore version', [
                'error' => $e->getMessage(),
                'pageId' => $pageId,
                'timestamp' => $timestamp
            ]);
            throw new \Exception('Failed to restore version: ' . $e->getMessage());
        }
    }

    /**
     * Get human-readable relative time
     */
    private function getRelativeTime(int $timestamp): string {
        $diff = time() - $timestamp;

        if ($diff < 60) {
            return 'just now';
        } elseif ($diff < 3600) {
            $minutes = floor($diff / 60);
            return $minutes . ' minute' . ($minutes > 1 ? 's' : '') . ' ago';
        } elseif ($diff < 86400) {
            $hours = floor($diff / 3600);
            return $hours . ' hour' . ($hours > 1 ? 's' : '') . ' ago';
        } elseif ($diff < 2592000) {
            $days = floor($diff / 86400);
            return $days . ' day' . ($days > 1 ? 's' : '') . ' ago';
        } elseif ($diff < 31536000) {
            $months = floor($diff / 2592000);
            return $months . ' month' . ($months > 1 ? 's' : '') . ' ago';
        } else {
            $years = floor($diff / 31536000);
            return $years . ' year' . ($years > 1 ? 's' : '') . ' ago';
        }
    }

    /**
     * Create a version before updating a file
     * Uses groupfolders versioning system directly via file storage
     */
    private function createVersionBeforeUpdate(\OCP\Files\File $file): void {
        try {
            // Get the storage from the file itself - this preserves the groupfolder storage context
            $storage = $file->getStorage();
            $this->logger->info('[createVersionBeforeUpdate] Storage info', [
                'fileId' => $file->getId(),
                'storageClass' => get_class($storage),
                'internalPath' => $file->getInternalPath()
            ]);

            // Check if this is a groupfolder storage
            if (!($storage instanceof \OCA\GroupFolders\Mount\GroupFolderStorage)) {
                $this->logger->warning('[createVersionBeforeUpdate] Not a GroupFolder storage, skipping versioning', [
                    'storageClass' => get_class($storage)
                ]);
                return;
            }

            // Get the VersionsBackend
            $versionsBackend = \OC::$server->get(\OCA\GroupFolders\Versions\VersionsBackend::class);

            // Get the current user
            $user = $this->userSession->getUser();
            if (!$user) {
                throw new \Exception('No user in session');
            }

            // Call createVersion directly with the file that has the correct storage
            $versionsBackend->createVersion($user, $file);

            $this->logger->info('[createVersionBeforeUpdate] Version created successfully', [
                'fileId' => $file->getId(),
                'userId' => $user->getUID()
            ]);
        } catch (\Exception $e) {
            $this->logger->warning('[createVersionBeforeUpdate] Failed to create version', [
                'error' => $e->getMessage(),
                'fileId' => $file->getId(),
                'trace' => $e->getTraceAsString()
            ]);
            // Don't throw - versioning failure shouldn't prevent saves
        }
    }
}
