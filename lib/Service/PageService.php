<?php
declare(strict_types=1);

namespace OCA\IntraVox\Service;

use OCA\IntraVox\Constants;
use OCA\IntraVox\Event\PageDeletedEvent;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\Files\IRootFolder;
use OCP\Files\NotFoundException;
use OCP\IUserSession;
use OCP\IConfig;
use OCP\IDBConnection;
use Psr\Log\LoggerInterface;
use OCP\Files\Cache\ICacheEntry;
use enshrined\svgSanitize\Sanitizer;
use OCA\Files_Versions\Versions\IVersionManager;
use OCA\Files_Versions\Versions\IVersion;

class PageService {
    private const ALLOWED_WIDGET_TYPES = ['text', 'heading', 'image', 'links', 'file', 'divider', 'spacer', 'video', 'news', 'people'];
    private const ALLOWED_IMAGE_TYPES = ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/svg+xml'];
    private const ALLOWED_VIDEO_TYPES = ['video/mp4', 'video/webm', 'video/ogg'];
    private const ALLOWED_MEDIA_TYPES = [
        'image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/svg+xml',
        'video/mp4', 'video/webm', 'video/ogg'
    ];
    private const ALLOWED_EXTENSIONS = [
        'jpg', 'jpeg', 'png', 'gif', 'webp', 'svg',  // Images
        'mp4', 'webm', 'ogg',                         // Videos
    ];
    private const MAX_IMAGE_SIZE = 2097152; // 2MB (PHP default upload limit)
    private const MAX_VIDEO_SIZE = 52428800; // 50MB
    private const MAX_MEDIA_SIZE = 52428800; // 50MB (largest of image/video limits)
    private const MAX_SVG_SIZE = 1048576; // 1MB for SVG files (prevent XML bomb attacks)
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
    private IEventDispatcher $eventDispatcher;
    private PublicationSettingsService $publicationSettings;
    private ?IVersionManager $versionManager = null;
    private array $pageFolderCache = [];

    /** @var array Request-level cache for page data */
    private array $pageDataCache = [];

    /** @var array Request-level cache for page list */
    private ?array $listPagesCache = null;

    /** @var array Request-level cache for pages by folder path */
    private array $folderPathCache = [];

    /** @var array Request-level cache for directory listings */
    private array $directoryListingCache = [];

    /** @var array Request-level cache for folder permissions */
    private array $permissionsCache = [];

    /** @var array Request-level cache for file contents */
    private array $fileContentCache = [];

    /** @var array Static cache for page tree (shared across requests within same PHP process) */
    private static array $pageTreeCache = [];

    /** @var int Cache TTL for page tree in seconds */
    private const PAGE_TREE_CACHE_TTL = 300; // 5 minutes

    /**
     * Get the effective upload limit in bytes (minimum of upload_max_filesize and post_max_size)
     */
    public function getUploadLimit(): int {
        $uploadMax = $this->parsePhpSize(ini_get('upload_max_filesize') ?: '2M');
        $postMax = $this->parsePhpSize(ini_get('post_max_size') ?: '8M');

        // Use the smaller of the two, but cap at our app's MAX_MEDIA_SIZE
        $phpLimit = min($uploadMax, $postMax);
        return min($phpLimit, self::MAX_MEDIA_SIZE);
    }

    /**
     * Parse PHP size notation (e.g., '2M', '8M', '512K') to bytes
     */
    private function parsePhpSize(string $size): int {
        $size = trim($size);
        $unit = strtoupper(substr($size, -1));
        $value = (int) substr($size, 0, -1);

        switch ($unit) {
            case 'G':
                return $value * 1024 * 1024 * 1024;
            case 'M':
                return $value * 1024 * 1024;
            case 'K':
                return $value * 1024;
            default:
                return (int) $size;
        }
    }

    /**
     * Clear all request-level caches (call after mutations)
     */
    private function clearCache(?string $pageId = null): void {
        if ($pageId !== null) {
            unset($this->pageDataCache[$pageId]);
            unset($this->pageFolderCache[$pageId]);
        } else {
            $this->pageDataCache = [];
            $this->pageFolderCache = [];
            $this->folderPathCache = [];
            $this->directoryListingCache = [];
            $this->permissionsCache = [];
            $this->fileContentCache = [];
        }
        $this->listPagesCache = null;

        // Also clear the static page tree cache for this user/language
        $cacheKey = $this->userId . '_' . $this->getUserLanguage();
        unset(self::$pageTreeCache[$cacheKey]);
    }

    /**
     * Preserve originalSrc for video widgets during page updates.
     * This ensures that video URLs are not lost when the domain whitelist changes.
     * When a video is blocked, its originalSrc is preserved so it can be re-enabled
     * if the admin adds the domain back to the whitelist.
     */
    private function preserveVideoOriginalUrls(array $newData, array $existingData): array {
        // Build a map of existing video widgets by their ID
        $existingVideos = [];
        $this->collectVideoWidgets($existingData, $existingVideos);

        // Update new data with preserved originalSrc values
        $this->updateVideoWidgetsWithOriginalUrls($newData, $existingVideos);

        return $newData;
    }

    /**
     * Collect all video widgets from page data into a map keyed by widget ID
     */
    private function collectVideoWidgets(array $data, array &$videos): void {
        // Process main rows
        if (isset($data['layout']['rows']) && is_array($data['layout']['rows'])) {
            foreach ($data['layout']['rows'] as $row) {
                if (isset($row['widgets']) && is_array($row['widgets'])) {
                    foreach ($row['widgets'] as $widget) {
                        if (($widget['type'] ?? '') === 'video' && isset($widget['id'])) {
                            $videos[$widget['id']] = $widget;
                        }
                    }
                }
            }
        }

        // Process side columns
        if (isset($data['layout']['sideColumns']) && is_array($data['layout']['sideColumns'])) {
            foreach (['left', 'right'] as $side) {
                if (isset($data['layout']['sideColumns'][$side]['widgets']) && is_array($data['layout']['sideColumns'][$side]['widgets'])) {
                    foreach ($data['layout']['sideColumns'][$side]['widgets'] as $widget) {
                        if (($widget['type'] ?? '') === 'video' && isset($widget['id'])) {
                            $videos[$widget['id']] = $widget;
                        }
                    }
                }
            }
        }

        // Process header row
        if (isset($data['layout']['headerRow']['widgets']) && is_array($data['layout']['headerRow']['widgets'])) {
            foreach ($data['layout']['headerRow']['widgets'] as $widget) {
                if (($widget['type'] ?? '') === 'video' && isset($widget['id'])) {
                    $videos[$widget['id']] = $widget;
                }
            }
        }
    }

    /**
     * Update video widgets in new data with originalSrc from existing widgets
     */
    private function updateVideoWidgetsWithOriginalUrls(array &$data, array $existingVideos): void {
        // Process main rows
        if (isset($data['layout']['rows']) && is_array($data['layout']['rows'])) {
            foreach ($data['layout']['rows'] as $rowIndex => &$row) {
                if (isset($row['widgets']) && is_array($row['widgets'])) {
                    foreach ($row['widgets'] as $widgetIndex => &$widget) {
                        $this->preserveWidgetOriginalUrl($widget, $existingVideos);
                    }
                }
            }
        }

        // Process side columns
        if (isset($data['layout']['sideColumns']) && is_array($data['layout']['sideColumns'])) {
            foreach (['left', 'right'] as $side) {
                if (isset($data['layout']['sideColumns'][$side]['widgets']) && is_array($data['layout']['sideColumns'][$side]['widgets'])) {
                    foreach ($data['layout']['sideColumns'][$side]['widgets'] as $widgetIndex => &$widget) {
                        $this->preserveWidgetOriginalUrl($widget, $existingVideos);
                    }
                }
            }
        }

        // Process header row
        if (isset($data['layout']['headerRow']['widgets']) && is_array($data['layout']['headerRow']['widgets'])) {
            foreach ($data['layout']['headerRow']['widgets'] as $widgetIndex => &$widget) {
                $this->preserveWidgetOriginalUrl($widget, $existingVideos);
            }
        }
    }

    /**
     * Preserve originalSrc for a single video widget
     */
    private function preserveWidgetOriginalUrl(array &$widget, array $existingVideos): void {
        if (($widget['type'] ?? '') !== 'video') {
            return;
        }

        // Skip local videos - they don't have originalSrc
        if (($widget['provider'] ?? '') === 'local') {
            return;
        }

        $widgetId = $widget['id'] ?? null;
        if ($widgetId && isset($existingVideos[$widgetId])) {
            $existing = $existingVideos[$widgetId];

            // If the new widget has no src or originalSrc, but the existing one does,
            // preserve the originalSrc so the URL isn't lost
            $newSrc = $widget['src'] ?? '';
            $newOriginalSrc = $widget['originalSrc'] ?? '';
            $existingOriginalSrc = $existing['originalSrc'] ?? '';
            $existingSrc = $existing['src'] ?? '';

            // Preserve originalSrc: use existing originalSrc if new one is empty
            if (empty($newOriginalSrc)) {
                if (!empty($existingOriginalSrc)) {
                    $widget['originalSrc'] = $existingOriginalSrc;
                } elseif (!empty($existingSrc)) {
                    // Fallback: use existing src as originalSrc
                    $widget['originalSrc'] = $existingSrc;
                }
            }

            // If new src is empty but we have originalSrc, keep it for re-validation
            if (empty($newSrc) && !empty($widget['originalSrc'] ?? '')) {
                // The sanitizeWidget function will re-validate against current whitelist
                // and either allow it (setting src) or block it (keeping blocked=true)
            }
        }
    }

    /**
     * Get cached directory listing for a folder
     */
    private function getCachedDirectoryListing(\OCP\Files\Folder $folder): array {
        $path = $folder->getPath();
        if (!isset($this->directoryListingCache[$path])) {
            $this->directoryListingCache[$path] = $folder->getDirectoryListing();
        }
        return $this->directoryListingCache[$path];
    }

    /**
     * Get cached permissions for a folder
     */
    private function getCachedPermissions(\OCP\Files\Folder $folder): int {
        $path = $folder->getPath();
        if (!isset($this->permissionsCache[$path])) {
            $this->permissionsCache[$path] = $folder->getPermissions();
        }
        return $this->permissionsCache[$path];
    }

    /**
     * Get cached file content (prevents repeated reads of same file within request)
     */
    private function getCachedFileContent(\OCP\Files\File $file): string {
        $path = $file->getPath();
        if (!isset($this->fileContentCache[$path])) {
            $this->fileContentCache[$path] = $file->getContent();
        }
        return $this->fileContentCache[$path];
    }

    public function __construct(
        IRootFolder $rootFolder,
        IUserSession $userSession,
        SetupService $setupService,
        IConfig $config,
        IDBConnection $db,
        LoggerInterface $logger,
        IEventDispatcher $eventDispatcher,
        PublicationSettingsService $publicationSettings,
        ?string $userId
    ) {
        $this->rootFolder = $rootFolder;
        $this->userSession = $userSession;
        $this->setupService = $setupService;
        $this->config = $config;
        $this->db = $db;
        $this->logger = $logger;
        $this->eventDispatcher = $eventDispatcher;
        $this->publicationSettings = $publicationSettings;
        $this->userId = $userId ?? '';

        // Lazy load version manager (files_versions may not be enabled)
        try {
            $this->versionManager = \OC::$server->get(IVersionManager::class);
        } catch (\Exception $e) {
            $this->logger->info('[PageService] Version manager not available: ' . $e->getMessage());
        }
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
     * Create a simple .nomedia marker for the _media folder
     * The folder name "_media" itself is the primary identifier
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
     * Get language folder by language code
     */
    private function getLanguageFolderByCode(string $lang) {
        $baseFolder = $this->getIntraVoxFolder();

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
     * Get the IntraVox folder from user's perspective (mounted GroupFolder)
     *
     * IMPORTANT: Uses the user's mounted folder view to respect GroupFolder ACL
     * This is essential for non-admin users to access the IntraVox folder
     */
    private function getIntraVoxFolder() {
        if (!$this->userId) {
            throw new \Exception('User not logged in');
        }

        // Get user's folder (this respects GroupFolder ACL)
        $userFolder = $this->rootFolder->getUserFolder($this->userId);

        // Get folder from user's perspective (mounted GroupFolder)
        try {
            return $userFolder->get('IntraVox');
        } catch (NotFoundException $e) {
            throw new \Exception("IntraVox folder not found. Please check that you have access to the IntraVox GroupFolder.");
        }
    }

    /**
     * Get permissions for a folder path (relative to IntraVox root)
     * Uses Nextcloud's native filesystem permissions which respect GroupFolder ACL
     *
     * IMPORTANT: Uses the user's mounted folder view to get ACL-aware permissions
     *
     * @param string $relativePath Path relative to IntraVox folder (e.g., "en/about" or "")
     * @return array Permissions object with canRead, canWrite, canCreate, canDelete, canShare
     */
    public function getFolderPermissions(string $relativePath): array {
        try {
            if (!$this->userId) {
                return [
                    'canRead' => false,
                    'canWrite' => false,
                    'canCreate' => false,
                    'canDelete' => false,
                    'canShare' => false,
                    'raw' => 0
                ];
            }

            // Get user's folder (this respects GroupFolder ACL)
            $userFolder = $this->rootFolder->getUserFolder($this->userId);

            // Get IntraVox folder from user's perspective (mounted GroupFolder)
            $intraVoxPath = 'IntraVox';
            if (!empty($relativePath)) {
                $intraVoxPath .= '/' . ltrim($relativePath, '/');
            }

            $folder = $userFolder->get($intraVoxPath);
            $ncPerms = $this->getCachedPermissions($folder);

            return [
                'canRead' => ($ncPerms & 1) !== 0,
                'canWrite' => ($ncPerms & 2) !== 0,
                'canCreate' => ($ncPerms & 4) !== 0,
                'canDelete' => ($ncPerms & 8) !== 0,
                'canShare' => ($ncPerms & 16) !== 0,
                'raw' => $ncPerms
            ];
        } catch (\Exception $e) {
            // If folder doesn't exist, return no permissions
            $this->logger->debug('getFolderPermissions failed for path: ' . $relativePath . ' - ' . $e->getMessage());
            return [
                'canRead' => false,
                'canWrite' => false,
                'canCreate' => false,
                'canDelete' => false,
                'canShare' => false,
                'raw' => 0
            ];
        }
    }

    /**
     * Check if a page ID already exists (recursively through all folders)
     */
    private function pageIdExists(string $id): bool {
        $folder = $this->getLanguageFolder();
        return $this->findPageById($folder, $id) !== null;
    }

    /**
     * Public method to check if a page exists by uniqueId
     * Used by CommentsEntityListener to validate comment objectIds
     */
    public function pageExistsByUniqueId(string $uniqueId): bool {
        try {
            $folder = $this->getLanguageFolder();
            return $this->findPageByUniqueId($folder, $uniqueId) !== null;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Recursively find a page by uniqueId
     */
    private function findPageByUniqueId($folder, string $uniqueId, $languageFolder = null): ?array {
        // Track the language folder for isHome detection
        if ($languageFolder === null) {
            $languageFolder = $folder;
        }

        // Check for home.json first (most common case for homepage)
        try {
            $homeFile = $folder->get('home.json');
            $content = $homeFile->getContent();
            $data = json_decode($content, true);
            if ($data && isset($data['uniqueId']) && $data['uniqueId'] === $uniqueId) {
                return ['file' => $homeFile, 'folder' => $folder, 'isHome' => true];
            }
        } catch (NotFoundException $e) {
            // home.json doesn't exist here, continue searching
        }

        // Get directory listing ONCE (cached) and separate files from folders
        $isLanguageRoot = ($folder->getPath() === $languageFolder->getPath());
        $items = $this->getCachedDirectoryListing($folder);
        $subfolderItems = [];

        // FIRST: Check all JSON files in current folder
        foreach ($items as $item) {
            $itemName = $item->getName();

            // Collect subfolders for later recursive search
            if ($item->getType() === \OCP\Files\FileInfo::TYPE_FOLDER) {
                // Skip media folders and special folders
                if ($itemName !== '_media' && $itemName !== 'images' && $itemName !== '.nomedia') {
                    $subfolderItems[] = $item;
                }
                continue;
            }

            // Skip navigation.json and footer.json only in the language root folder
            // (these are config files there, not pages). In subfolders they can be page files.
            $skipFile = ($itemName === 'home.json'); // Always skip home.json, checked above
            if ($isLanguageRoot && ($itemName === 'navigation.json' || $itemName === 'footer.json')) {
                $skipFile = true;
            }

            if (substr($itemName, -5) === '.json' && !$skipFile) {
                try {
                    $content = $item->getContent();
                    $data = json_decode($content, true);
                    if ($data && isset($data['uniqueId']) && $data['uniqueId'] === $uniqueId) {
                        // Determine the correct folder:
                        // If there's a matching subfolder (e.g., company-blog folder for company-blog.json),
                        // return that subfolder. Otherwise return current folder.
                        $baseName = substr($itemName, 0, -5); // Remove .json extension
                        try {
                            $matchingFolder = $folder->get($baseName);
                            if ($matchingFolder->getType() === \OCP\Files\FileInfo::TYPE_FOLDER) {
                                return ['file' => $item, 'folder' => $matchingFolder, 'isHome' => false];
                            }
                        } catch (NotFoundException $e) {
                            // No matching folder, use current folder
                        }
                        return ['file' => $item, 'folder' => $folder, 'isHome' => false];
                    }
                } catch (\Exception $e) {
                    // Skip invalid files
                    continue;
                }
            }
        }

        // SECOND: Recursively search subfolders (already collected above)
        foreach ($subfolderItems as $subfolder) {
            $result = $this->findPageByUniqueId($subfolder, $uniqueId, $languageFolder);
            if ($result !== null) {
                return $result;
            }
        }

        return null;
    }

    /**
     * Recursively find a page by ID (legacy support - keep for backward compatibility)
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
        foreach ($this->getCachedDirectoryListing($folder) as $item) {
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
        $intraVoxFolder = $this->getIntraVoxFolder();
        $pages = [];

        // Get base path for relative path calculation
        $basePath = $intraVoxFolder->getPath();

        // Check for home.json in root
        try {
            $homeFile = $folder->get('home.json');
            $content = $homeFile->getContent();
            $data = json_decode($content, true);

            if ($data && isset($data['uniqueId'], $data['title'])) {
                // Calculate relative path from IntraVox root
                $relativePath = substr($folder->getPath(), strlen($basePath) + 1);
                $folderPerms = $this->getCachedPermissions($folder);

                $pages[] = [
                    'uniqueId' => $data['uniqueId'],
                    'title' => $data['title'],
                    'modified' => $data['modified'] ?? $homeFile->getMTime(),
                    'permissions' => [
                        'canRead' => ($folderPerms & 1) !== 0,
                        'canWrite' => ($folderPerms & 2) !== 0,
                        'canCreate' => ($folderPerms & 4) !== 0,
                        'canDelete' => ($folderPerms & 8) !== 0,
                        'canShare' => ($folderPerms & 16) !== 0,
                        'raw' => $folderPerms
                    ]
                ];
            }
        } catch (NotFoundException $e) {
            // No home page yet
        }

        // Recursively find all pages in subfolders
        $this->findPagesInFolder($folder, $pages, $basePath);

        return $pages;
    }

    /**
     * Recursively find pages in folders
     */
    private function findPagesInFolder($folder, array &$pages, string $basePath = ''): void {
        foreach ($this->getCachedDirectoryListing($folder) as $item) {
            if ($item->getType() === \OCP\Files\FileInfo::TYPE_FOLDER) {
                $folderName = $item->getName();

                // Skip special folders
                if (in_array($folderName, ['_media', 'images', 'files'])) {
                    continue;
                }

                // Look for {foldername}.json inside the folder
                try {
                    $jsonFile = $item->get($folderName . '.json');

                    // Check if file is readable before trying to get content
                    if (!$jsonFile->isReadable()) {
                        continue;
                    }

                    // Use cached file content to avoid repeated reads
                    $content = $jsonFile instanceof \OCP\Files\File
                        ? $this->getCachedFileContent($jsonFile)
                        : @$jsonFile->getContent();

                    if ($content === false || $content === null) {
                        continue;
                    }

                    $data = json_decode($content, true);

                    if ($data && isset($data['uniqueId'], $data['title'])) {
                        // Get permissions from the folder containing the page
                        $folderPerms = $this->getCachedPermissions($item);

                        $pages[] = [
                            'uniqueId' => $data['uniqueId'],
                            'title' => $data['title'],
                            'modified' => $data['modified'] ?? $jsonFile->getMTime(),
                            'permissions' => [
                                'canRead' => ($folderPerms & 1) !== 0,
                                'canWrite' => ($folderPerms & 2) !== 0,
                                'canCreate' => ($folderPerms & 4) !== 0,
                                'canDelete' => ($folderPerms & 8) !== 0,
                                'canShare' => ($folderPerms & 16) !== 0,
                                'raw' => $folderPerms
                            ]
                        ];
                    }
                } catch (\Exception $e) {
                    // This folder doesn't contain a valid page or can't be read, continue
                } catch (\Throwable $e) {
                    // Catch any other errors including PHP errors
                    continue;
                }

                // Recursively search subfolders
                $this->findPagesInFolder($item, $pages, $basePath);
            }
        }
    }

    /**
     * List all pages with full content (including layout)
     * OPTIMIZED: Single filesystem traversal for search operations
     * This eliminates the N+1 query pattern where listPages() + getPage() for each
     */
    public function listPagesWithContent(): array {
        $folder = $this->getLanguageFolder();
        $pages = [];

        // Check for home.json in root
        try {
            $homeFile = $folder->get('home.json');
            $content = $homeFile->getContent();
            $data = json_decode($content, true);

            if ($data && isset($data['uniqueId'])) {
                $pages[] = $this->sanitizePage($data);
            }
        } catch (NotFoundException $e) {
            // No home page yet
        }

        // Recursively find all pages with full content
        $this->findPagesWithContentInFolder($folder, $pages);

        return $pages;
    }

    /**
     * Recursively find pages with full content in folders
     */
    private function findPagesWithContentInFolder($folder, array &$pages): void {
        foreach ($this->getCachedDirectoryListing($folder) as $item) {
            if ($item->getType() === \OCP\Files\FileInfo::TYPE_FOLDER) {
                $folderName = $item->getName();

                // Skip special folders
                if (in_array($folderName, ['_media', 'images', 'files'])) {
                    continue;
                }

                // Look for {foldername}.json inside the folder
                try {
                    $jsonFile = $item->get($folderName . '.json');

                    if (!$jsonFile->isReadable()) {
                        continue;
                    }

                    // Use cached file content to avoid repeated reads
                    $content = $jsonFile instanceof \OCP\Files\File
                        ? $this->getCachedFileContent($jsonFile)
                        : @$jsonFile->getContent();

                    if ($content === false || $content === null) {
                        continue;
                    }

                    $data = json_decode($content, true);

                    if ($data && isset($data['uniqueId'])) {
                        $pages[] = $this->sanitizePage($data);
                    }
                } catch (\Exception $e) {
                    // This folder doesn't contain a valid page
                } catch (\Throwable $e) {
                    continue;
                }

                // Recursively search subfolders
                $this->findPagesWithContentInFolder($item, $pages);
            }
        }
    }

    /**
     * Get a specific page by uniqueId or legacy id
     */
    public function getPage(string $id): array {
        // Check request-level cache first
        if (isset($this->pageDataCache[$id])) {
            return $this->pageDataCache[$id];
        }

        $folder = $this->getLanguageFolder();
        $result = null;

        // Save original ID before sanitization
        $originalId = $id;

        // Check for uniqueId pattern BEFORE sanitization
        if (strpos($originalId, 'page-') === 0) {
            $result = $this->findPageByUniqueId($folder, $originalId);
            if (!$result) {
                $this->logger->warning('IntraVox: Not found by uniqueId', ['uniqueId' => $originalId]);
            }
        }

        // Only sanitize for legacy ID fallback
        if ($result === null) {
            $id = $this->sanitizeId($originalId);
            $result = $this->findPageById($folder, $id);
        }

        if ($result === null) {
            throw new \Exception('Page not found');
        }

        $content = $result['file']->getContent();
        $data = json_decode($content, true);

        if (!$data) {
            throw new \Exception('Invalid page data');
        }

        // Ensure uniqueId exists for legacy pages
        if (!isset($data['uniqueId'])) {
            $data['uniqueId'] = 'page-' . $this->generateUUID();
            // Save the page with the new uniqueId
            try {
                $result['file']->putContent(json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            } catch (\Exception $e) {
                // Failed to save uniqueId - page will work but won't have permanent link
            }
        }

        // Cache folder location using both uniqueId and pageId for fast image access
        $pageFolder = $result['folder'];
        $uniqueId = $data['uniqueId'];
        $this->pageFolderCache[$uniqueId] = $pageFolder;
        $this->pageFolderCache[$originalId] = $pageFolder;
        if (isset($id)) {
            $this->pageFolderCache[$id] = $pageFolder;
        }

        // Enrich with real-time path data
        $data = $this->enrichWithPathData($data, $result['folder']);

        $sanitizedData = $this->sanitizePage($data);

        // Cache the result for this request
        $this->pageDataCache[$originalId] = $sanitizedData;
        if (isset($data['uniqueId'])) {
            $this->pageDataCache[$data['uniqueId']] = $sanitizedData;
        }

        return $sanitizedData;
    }

    /**
     * Enrich page data with real-time path information calculated from filesystem
     */
    private function enrichWithPathData(array $page, $folder): array {
        // Get relative path from IntraVox root
        $page['path'] = $this->getRelativePathFromRoot($folder);

        // Calculate depth
        $page['depth'] = $this->calculateDepth($page['path']);

        // Calculate parent path
        $pathParts = explode('/', $page['path']);
        if (count($pathParts) > 1) {
            array_pop($pathParts); // Remove current page
            $page['parentPath'] = implode('/', $pathParts);
            $page['parentId'] = basename($page['parentPath']);
        } else {
            $page['parentPath'] = null;
            $page['parentId'] = null;
        }

        // Parse language and department from path
        $parsedPath = explode('/', $page['path']);
        $page['language'] = $parsedPath[0] ?? $this->getUserLanguage();
        $page['department'] = $this->parseDepartmentFromPath($page['path']);

        // Get permissions directly from Nextcloud's filesystem
        // This automatically respects GroupFolder ACL rules
        $ncPerms = $this->getCachedPermissions($folder);

        // Nextcloud permission constants (from OCP\Constants):
        // PERMISSION_READ = 1, PERMISSION_UPDATE = 2, PERMISSION_CREATE = 4,
        // PERMISSION_DELETE = 8, PERMISSION_SHARE = 16, PERMISSION_ALL = 31
        $page['permissions'] = [
            'canRead' => ($ncPerms & 1) !== 0,
            'canWrite' => ($ncPerms & 2) !== 0,
            'canCreate' => ($ncPerms & 4) !== 0,
            'canDelete' => ($ncPerms & 8) !== 0,
            'canShare' => ($ncPerms & 16) !== 0,
            'raw' => $ncPerms
        ];

        // Keep canEdit for backwards compatibility
        $page['canEdit'] = $folder->isUpdateable();

        return $page;
    }

    /**
     * Get relative path from IntraVox root folder
     */
    private function getRelativePathFromRoot($folder): string {
        $intraVoxPath = $this->getIntraVoxFolder()->getPath();
        $folderPath = $folder->getPath();

        // Remove IntraVox base path
        $relativePath = str_replace($intraVoxPath . '/', '', $folderPath);

        return $relativePath;
    }

    /**
     * Calculate nesting depth from path
     *
     * Base paths (depth 0):
     * - nl/public/ (public pages)
     * - nl/departments/{dept}/ (department pages)
     */
    private function calculateDepth(string $path): int {
        $pathParts = explode('/', trim($path, '/'));

        // Remove language code
        if (count($pathParts) > 0 && in_array($pathParts[0], self::SUPPORTED_LANGUAGES)) {
            array_shift($pathParts);
        }

        if (count($pathParts) === 0) {
            return 0;
        }

        // Check if public path
        if ($pathParts[0] === 'public') {
            // nl/public/home/contact -> depth 1
            return count($pathParts) - 1;
        }

        // Check if department path
        if ($pathParts[0] === 'departments' && count($pathParts) > 1) {
            // nl/departments/marketing/campaigns/2024 -> depth 2
            // (subtract 'departments' and dept name)
            return count($pathParts) - 2;
        }

        // Legacy: pages directly under language folder
        return count($pathParts) - 1;
    }

    /**
     * Get maximum allowed depth for a given path
     */
    private function getMaxDepthForPath(string $path): int {
        $pathParts = explode('/', trim($path, '/'));

        // Remove language if present
        if (count($pathParts) > 0 && in_array($pathParts[0], self::SUPPORTED_LANGUAGES)) {
            array_shift($pathParts);
        }

        // Public pages: max depth 5
        if (count($pathParts) > 0 && $pathParts[0] === 'public') {
            return 5;
        }

        // Department pages: max depth 5
        if (count($pathParts) > 0 && $pathParts[0] === 'departments') {
            return 5;
        }

        // Default: max depth 5
        return 5;
    }

    /**
     * Validate that creating a child page at the given path wouldn't exceed max depth
     */
    private function validateDepth(string $parentPath): void {
        $currentDepth = $this->calculateDepth($parentPath);
        $maxDepth = $this->getMaxDepthForPath($parentPath);

        if ($currentDepth >= $maxDepth) {
            throw new \InvalidArgumentException(
                "Cannot create child page: maximum nesting depth of {$maxDepth} would be exceeded"
            );
        }
    }

    /**
     * Check if a page has child pages
     */
    private function hasChildren(string $pageId): bool {
        $result = $this->findPageById($this->getLanguageFolder(), $pageId);

        if (!$result) {
            return false;
        }

        $folder = $result['folder'];

        try {
            foreach ($this->getCachedDirectoryListing($folder) as $item) {
                if ($item->getType() !== \OCP\Files\FileInfo::TYPE_FOLDER) {
                    continue;
                }

                // Skip special folders
                if (in_array($item->getName(), ['_media', 'images', 'files'])) {
                    continue;
                }

                // Check if folder contains a page.json
                try {
                    $jsonFile = $item->get($item->getName() . '.json');
                    if ($jsonFile) {
                        return true; // Found at least one child page
                    }
                } catch (\Exception $e) {
                    // No page.json in this folder, continue
                }
            }
        } catch (\Exception $e) {
            // Error reading folder
        }

        return false;
    }

    /**
     * Determine page type based on path and structure
     *
     * @return string 'department'|'container'|'page'
     */
    private function determinePageType(string $path, bool $hasChildren): string {
        $pathParts = explode('/', trim($path, '/'));

        // Remove language if present
        if (count($pathParts) > 0 && in_array($pathParts[0], self::SUPPORTED_LANGUAGES)) {
            array_shift($pathParts);
        }

        // Check if this is a department root: nl/departments/marketing
        if (count($pathParts) >= 2 && $pathParts[0] === 'departments') {
            // If it's exactly "departments/[name]", it's a department
            if (count($pathParts) === 2) {
                return 'department';
            }
        }

        // If it has children, it's a container
        if ($hasChildren) {
            return 'container';
        }

        // Default: regular page
        return 'page';
    }

    /**
     * Parse department name from path
     */
    private function parseDepartmentFromPath(string $path): ?string {
        $pathParts = explode('/', trim($path, '/'));

        // Remove language code
        if (count($pathParts) > 0 && in_array($pathParts[0], self::SUPPORTED_LANGUAGES)) {
            array_shift($pathParts);
        }

        // Check if departments path: departments/{dept}/...
        if (count($pathParts) >= 2 && $pathParts[0] === 'departments') {
            return $pathParts[1];
        }

        return null;
    }

    /**
     * Get breadcrumb trail for a page
     *
     * Returns array of breadcrumb items from home to current page
     */
    public function getBreadcrumb(string $pageId): array {
        $page = $this->getPage($pageId);
        $breadcrumb = [];
        $language = $this->getUserLanguage();

        // Check if current page is the home page
        $isHomePage = ($pageId === 'home' ||
                       preg_match('/^[a-z]{2}\/home$/', $page['path']) ||
                       preg_match('/^[a-z]{2}$/', $page['path']));

        // Read home breadcrumb label from navigation.json (first item title)
        // This allows users to customize the label via the navigation editor
        $homeTitle = 'Home';
        $homeUniqueId = $isHomePage ? $page['uniqueId'] : null;
        try {
            $folder = $this->getLanguageFolder();
            if ($folder->nodeExists('navigation.json')) {
                $navFile = $folder->get('navigation.json');
                $navData = json_decode($navFile->getContent(), true, 64);
                if ($navData && !empty($navData['items'][0]['title'])) {
                    $homeTitle = $navData['items'][0]['title'];
                }
                if (!$isHomePage && $navData && !empty($navData['items'][0]['uniqueId'])) {
                    $homeUniqueId = $navData['items'][0]['uniqueId'];
                }
            }
        } catch (\Exception $e) {
            // fallback to 'Home'
        }

        // Always start with Home
        $breadcrumb[] = [
            'id' => 'home',
            'uniqueId' => $homeUniqueId,
            'title' => $homeTitle,
            'path' => $language . '/home',
            'url' => $isHomePage ? null : '#home',
            'current' => $isHomePage
        ];

        // If this is the home page, we're done - don't add duplicate
        if ($isHomePage) {
            return $breadcrumb;
        }

        // Build breadcrumb from the full path
        // Example path: en/departments/marketing/campaigns
        $pathParts = explode('/', $page['path']);
        $accumulatedPath = '';

        foreach ($pathParts as $index => $part) {
            // Build accumulated path for looking up parent pages
            if (!empty($accumulatedPath)) {
                $accumulatedPath .= '/';
            }
            $accumulatedPath .= $part;

            // Skip language folder in breadcrumb display (but include in accumulated path)
            if ($index === 0 && in_array($part, self::SUPPORTED_LANGUAGES)) {
                continue;
            }

            // Skip 'home' as it's already added
            if ($part === 'home') {
                continue;
            }

            // Check if this is the last item (current page)
            if ($index === count($pathParts) - 1) {
                // Add current page (not clickable)
                $breadcrumb[] = [
                    'uniqueId' => $page['uniqueId'],
                    'title' => $page['title'],
                    'path' => $page['path'],
                    'url' => null,
                    'current' => true
                ];
                break;
            }

            // Try to find parent page by its folder path
            try {
                $parentPage = $this->findPageByFolderPath($accumulatedPath);
                if ($parentPage) {
                    $breadcrumb[] = [
                        'id' => $part,
                        'uniqueId' => $parentPage['uniqueId'],
                        'title' => $parentPage['title'],
                        'path' => $parentPage['path'],
                        'url' => '#' . $parentPage['uniqueId'],
                        'current' => false
                    ];
                } else {
                    // No page found for this folder - use folder name as label but don't make clickable
                    $breadcrumb[] = [
                        'id' => $part,
                        'uniqueId' => null,
                        'title' => ucfirst(str_replace('-', ' ', $part)),
                        'path' => $accumulatedPath,
                        'url' => null,
                        'current' => false
                    ];
                }
            } catch (\Exception $e) {
                // Parent page not found or error loading it
                // Use folder name as fallback
                $breadcrumb[] = [
                    'id' => $part,
                    'uniqueId' => null,
                    'title' => ucfirst(str_replace('-', ' ', $part)),
                    'path' => $accumulatedPath,
                    'url' => null,
                    'current' => false
                ];
            }
        }

        return $breadcrumb;
    }

    /**
     * Find a page by its folder path relative to IntraVox root
     *
     * @param string $folderPath e.g., "en/departments" or "en/departments/marketing"
     * @return array|null Page data or null if not found
     */
    private function findPageByFolderPath(string $folderPath): ?array {
        // Check request-level cache first
        if (isset($this->folderPathCache[$folderPath])) {
            return $this->folderPathCache[$folderPath];
        }

        try {
            $intraVoxFolder = $this->getIntraVoxFolder();
            $folder = $intraVoxFolder->get($folderPath);

            if (!($folder instanceof \OCP\Files\Folder)) {
                $this->folderPathCache[$folderPath] = null;
                return null;
            }

            // Look for a JSON file in this folder (page definition)
            $files = $this->getCachedDirectoryListing($folder);
            foreach ($files as $file) {
                if ($file instanceof \OCP\Files\File &&
                    pathinfo($file->getName(), PATHINFO_EXTENSION) === 'json' &&
                    $file->getName() !== 'images.json') {

                    $content = $file->getContent();
                    $data = json_decode($content, true);

                    if ($data && isset($data['uniqueId'])) {
                        // Enrich with path data
                        $data = $this->enrichWithPathData($data, $folder);
                        $result = $this->sanitizePage($data);
                        $this->folderPathCache[$folderPath] = $result;
                        return $result;
                    }
                }
            }
        } catch (\Exception $e) {
            // Folder or page not found
            $this->logger->debug("Could not find page at path {$folderPath}: " . $e->getMessage());
        }

        $this->folderPathCache[$folderPath] = null;
        return null;
    }

    /**
     * Get or create folder path recursively
     * Example: "nl/departments/marketing/campaigns" will create all intermediate folders
     */
    private function getOrCreateFolderPath(string $path): \OCP\Files\Folder {
        $pathParts = explode('/', trim($path, '/'));
        $currentFolder = $this->getLanguageFolder();

        // Remove language part since we already start from language folder
        if (count($pathParts) > 0 && in_array($pathParts[0], self::SUPPORTED_LANGUAGES)) {
            array_shift($pathParts);
        }

        // Create each folder in path if it doesn't exist
        foreach ($pathParts as $folderName) {
            try {
                $currentFolder = $currentFolder->get($folderName);
                if ($currentFolder->getType() !== \OCP\Files\FileInfo::TYPE_FOLDER) {
                    throw new \InvalidArgumentException("Path component '{$folderName}' exists but is not a folder");
                }
            } catch (NotFoundException $e) {
                $currentFolder = $currentFolder->newFolder($folderName);
            }
        }

        return $currentFolder;
    }

    /**
     * Create a page at a specific path with parent support
     *
     * @param string $pageId The page ID (used as folder name)
     * @param array $data Page data (without id - id is the folder name)
     * @param string|null $parentPath Optional parent path (e.g., "nl/departments/marketing")
     * @return array Created page data
     */
    private function createPageAtPath(string $pageId, array $data, ?string $parentPath = null): array {
        $language = $this->getUserLanguage();

        // Determine target folder
        if ($parentPath) {
            // Validate depth before creating
            $this->validateDepth($parentPath);

            // Get or create parent folder path
            $targetFolder = $this->getOrCreateFolderPath($parentPath);
        } else {
            // No parent = create at language root
            $targetFolder = $this->getLanguageFolder();
        }

        // Special handling for home page (always at root)
        if ($pageId === 'home') {
            $file = $targetFolder->newFile('home.json');
            $file->putContent(json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

            // Create _media folder for home if it doesn't exist
            try {
                $mediaFolder = $targetFolder->get('_media');
                $this->createMediaFolderMarker($mediaFolder);
            } catch (NotFoundException $e) {
                $mediaFolder = $targetFolder->newFolder('_media');
                $this->createMediaFolderMarker($mediaFolder);
            }

            $this->scanPageFolder($targetFolder);
        } else {
            // Create folder for page
            try {
                $pageFolder = $targetFolder->newFolder($pageId);
            } catch (\Exception $e) {
                throw new \InvalidArgumentException('Failed to create page folder: ' . $e->getMessage());
            }

            // Create {pageId}.json inside the folder
            try {
                $file = $pageFolder->newFile($pageId . '.json');
                $file->putContent(json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            } catch (\Exception $e) {
                throw new \InvalidArgumentException('Failed to create page file: ' . $e->getMessage());
            }

            // Create _media subfolder
            try {
                $mediaFolder = $pageFolder->newFolder('_media');
                // Add a .nomedia file to indicate this is a special folder
                $this->createMediaFolderMarker($mediaFolder);
            } catch (\Exception $e) {
                // Media folder might already exist, that's okay
                try {
                    $mediaFolder = $pageFolder->get('_media');
                    $this->createMediaFolderMarker($mediaFolder);
                } catch (\Exception $ex) {
                    // Couldn't get media folder
                }
            }

            $this->scanPageFolder($pageFolder);

            // Cache the folder reference for immediate reuse (e.g., when copying media from template)
            if (isset($data['uniqueId'])) {
                $this->pageFolderCache[$data['uniqueId']] = $pageFolder;
            }
        }

        // Return data with id for frontend (id is derived from folder name)
        return array_merge(['id' => $pageId], $data);
    }

    /**
     * Create a new page
     *
     * @param array $data Page data (id, title, content, etc.)
     * @param string|null $parentPath Optional parent path for nested pages (e.g., "nl/departments/marketing")
     * @return array Created page data
     */
    public function createPage(array $data, ?string $parentPath = null): array {
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

        // Generate uniqueId if not provided
        if (!isset($data['uniqueId'])) {
            $data['uniqueId'] = 'page-' . $this->generateUUID();
        }

        $validatedData = $this->validateAndSanitizePage($data);

        // Use the new createPageAtPath helper - pass id separately (not stored in JSON)
        return $this->createPageAtPath($data['id'], $validatedData, $parentPath);
    }

    /**
     * Scan a page folder to make it immediately visible in Files app
     * This uses Nextcloud's Scanner to add the folder to the file cache
     *
     * @param \OCP\Files\Folder $folder The folder to scan (can be page folder or language folder)
     */
    private function scanPageFolder($folder): void {
        try {
            $folderPath = $folder->getPath();

            // For groupfolders, run occ files:scan directly
            // Match pattern: /__groupfolders/{id}/files/{anything}
            if (preg_match('#/__groupfolders/(\d+)/files/(.+)$#', $folderPath, $matches)) {
                $relativePath = $matches[2]; // e.g., "en/team-sales/sales-1"

                $user = $this->userSession->getUser();
                if (!$user) {
                    return;
                }

                $username = $user->getUID();
                $scanPath = "/{$username}/files/IntraVox/{$relativePath}";

                // Execute occ files:scan (already running as www-data via web server)
                $command = sprintf(
                    'php /var/www/nextcloud/occ files:scan --path=%s 2>&1',
                    escapeshellarg($scanPath)
                );

                exec($command, $output, $returnCode);

                if ($returnCode !== 0) {
                    $this->logger->warning('Failed to scan page folder', [
                        'path' => $scanPath,
                        'exit_code' => $returnCode,
                        'output' => implode("\n", $output ?? [])
                    ]);
                }

                return;
            }

            // Fallback for non-groupfolder paths (shouldn't happen in IntraVox)
            $storage = $folder->getStorage();
            $scanner = $storage->getScanner();
            $cache = $storage->getCache();

            $internalPath = $folder->getInternalPath();
            if (preg_match('#__groupfolders/\d+/(.+)$#', $internalPath, $matches)) {
                $scanPath = $matches[1];
            } else {
                $scanPath = $internalPath;
            }

            $scanner->scan($scanPath, true);
            $cache->correctFolderSize($scanPath, ['recursive' => true]);

        } catch (\Exception $e) {
            // Log but don't throw - if scanning fails, the page is still created
            $this->logger->error('Failed to scan page folder', [
                'path' => $folder->getPath(),
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Propagate cache size changes up the folder tree
     * This is critical for groupfolders to make new content visible
     */
    private function propagateCacheSizes($cache, $internalPath): void {
        try {
            // Start from the given path and work up to the root
            $currentPath = $internalPath;

            while ($currentPath !== '' && $currentPath !== '.') {
                // Update the size for this folder
                $cache->correctFolderSize($currentPath);

                // Move to parent folder
                $parentPath = dirname($currentPath);
                if ($parentPath === $currentPath || $parentPath === '.') {
                    break;
                }
                $currentPath = $parentPath;
            }
        } catch (\Exception $e) {
            // Silently fail - cache propagation is not critical
        }
    }

    /**
     * Update an existing page
     */
    public function updatePage(string $id, array $data): array {
        // Save original ID before sanitization
        $originalId = $id;

        // Get the current user
        $user = $this->userSession->getUser();
        if (!$user) {
            throw new \InvalidArgumentException('No user in session');
        }

        $languageFolder = $this->getLanguageFolder();
        $result = null;

        // Check for uniqueId pattern (page-xxx) BEFORE sanitization
        if (strpos($originalId, 'page-') === 0) {
            // Search by uniqueId
            $result = $this->findPageByUniqueId($languageFolder, $originalId);
        }

        // Fallback to legacy ID lookup if not found by uniqueId
        if ($result === null) {
            try {
                $id = $this->sanitizeId($originalId);
                $result = $this->findPageById($languageFolder, $id);
            } catch (\Exception $e) {
                throw new \InvalidArgumentException('Failed to find page: ' . $e->getMessage());
            }
        }

        if ($result === null) {
            throw new \InvalidArgumentException('Page not found: ' . $originalId);
        }

        // Get the file
        $file = $result['file'];

        try {
            $existingContent = $file->getContent();
            $existingData = json_decode($existingContent, true);
            if (!is_array($existingData)) {
                $existingData = [];
            }
        } catch (\Exception $e) {
            throw new \InvalidArgumentException('Failed to read existing page data: ' . $e->getMessage());
        }

        // Preserve uniqueId from existing data
        if (isset($existingData['uniqueId'])) {
            $data['uniqueId'] = $existingData['uniqueId'];
        }

        // Preserve originalSrc for video widgets to prevent URL loss when whitelist changes
        $data = $this->preserveVideoOriginalUrls($data, $existingData);

        try {
            $validatedData = $this->validateAndSanitizePage($data);
        } catch (\Exception $e) {
            $this->logger->error('[updatePage] Validation failed: ' . $e->getMessage(), [
                'pageId' => $originalId,
                'trace' => $e->getTraceAsString(),
            ]);
            throw new \InvalidArgumentException('Page validation failed: ' . $e->getMessage());
        }

        try {
            // Create version before update using GroupFolders VersionsBackend
            // GroupFolders 20.1.7+ has reliable versioning support
            $this->createVersionBeforeUpdate($file);

            // Update the file
            $file->putContent(json_encode($validatedData, JSON_PRETTY_PRINT));

        } catch (\Exception $e) {
            throw new \InvalidArgumentException('Failed to write updated page data: ' . $e->getMessage());
        }

        // Clear caches for this page (and uniqueId if present)
        $this->clearCache($originalId);
        if (isset($validatedData['uniqueId'])) {
            $this->clearCache($validatedData['uniqueId']);
        }

        // Return data with id for frontend (id is derived from folder name)
        // Get id from folder name (for home page it's 'home', otherwise folder basename)
        $pageId = ($result['isHome'] ?? false) ? 'home' : $result['folder']->getName();
        return array_merge(['id' => $pageId], $validatedData);
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

        // Get page data before deletion to retrieve uniqueId for comment cleanup
        try {
            $pageData = $result['data'] ?? [];
            $uniqueId = $pageData['uniqueId'] ?? '';

            // Dispatch event to cleanup comments/reactions before deleting the page
            if (!empty($uniqueId)) {
                $this->eventDispatcher->dispatchTyped(new PageDeletedEvent($id, $uniqueId));
            }
        } catch (\Exception $e) {
            // Log but don't block deletion if event dispatch fails
            $this->logger->warning('Failed to dispatch PageDeletedEvent for page ' . $id . ': ' . $e->getMessage());
        }

        // Delete the entire folder (includes .json, images/, files/)
        $result['folder']->delete();

        // Clear caches
        $this->clearCache();
    }

    /**
     * Upload media (image or video) for a specific page
     * Unified endpoint that stores all media in a single '_media' folder
     */
    public function uploadMedia(string $pageId, array $file): string {
        if (!isset($file['tmp_name']) || !isset($file['name'])) {
            throw new \InvalidArgumentException('Invalid file upload');
        }

        // Check if tmp_name is empty (upload failed on server)
        if (empty($file['tmp_name'])) {
            $errorCode = $file['error'] ?? -1;
            $errorMessages = [
                UPLOAD_ERR_INI_SIZE => 'File exceeds server upload limit',
                UPLOAD_ERR_FORM_SIZE => 'File exceeds form upload limit',
                UPLOAD_ERR_PARTIAL => 'File only partially uploaded',
                UPLOAD_ERR_NO_FILE => 'No file was uploaded',
                UPLOAD_ERR_NO_TMP_DIR => 'Server missing temporary folder',
                UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk',
                UPLOAD_ERR_EXTENSION => 'Upload stopped by PHP extension',
            ];
            $message = $errorMessages[$errorCode] ?? "Upload failed (error code: $errorCode)";
            throw new \InvalidArgumentException($message);
        }

        $pageId = $this->sanitizeId($pageId);

        // Validate file type
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        if (!in_array($mimeType, self::ALLOWED_MEDIA_TYPES)) {
            throw new \InvalidArgumentException('Invalid file type. Allowed: JPEG, PNG, GIF, WebP, SVG, MP4, WebM, OGG');
        }

        // Validate file size
        if ($file['size'] > self::MAX_MEDIA_SIZE) {
            throw new \InvalidArgumentException('File too large. Maximum size is 50MB.');
        }

        // Additional validation for image files (prevents polyglot attacks)
        if (in_array($mimeType, ['image/jpeg', 'image/png', 'image/gif', 'image/webp'])) {
            $this->validateImageFile($file['tmp_name'], $mimeType);
        }

        // SVG files get special treatment: smaller size limit + sanitization
        if ($mimeType === 'image/svg+xml') {
            if ($file['size'] > self::MAX_SVG_SIZE) {
                throw new \InvalidArgumentException('SVG file too large. Maximum size is 1MB.');
            }
            $content = file_get_contents($file['tmp_name']);
            $content = $this->sanitizeSVG($content);
        } else {
            $content = file_get_contents($file['tmp_name']);
        }

        // Sanitize filename with prefix based on type
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $isVideo = in_array($mimeType, self::ALLOWED_VIDEO_TYPES);
        $prefix = $isVideo ? 'vid_' : 'img_';
        $filename = uniqid($prefix, true) . '.' . $extension;

        $languageFolder = $this->getLanguageFolder();

        // Find the page by uniqueId (pageId is actually the uniqueId from the frontend)
        $result = $this->findPageByUniqueId($languageFolder, $pageId);
        if ($result === null) {
            // Fallback: try finding by folder name for backwards compatibility
            $result = $this->findPageById($languageFolder, $pageId);
            if ($result === null) {
                throw new \Exception('Page not found');
            }
        }

        // Get media folder for this page
        if ($result['isHome'] ?? false) {
            // Home media is in root/_media/
            try {
                $mediaFolder = $languageFolder->get('_media');
            } catch (NotFoundException $e) {
                $mediaFolder = $languageFolder->newFolder('_media');
            }
        } else {
            $pageFolder = $result['folder'];

            // Get or create media subfolder
            try {
                $mediaFolder = $pageFolder->get('_media');
            } catch (NotFoundException $e) {
                $mediaFolder = $pageFolder->newFolder('_media');
            }
        }

        $newFile = $mediaFolder->newFile($filename);
        $newFile->putContent($content);

        return $filename;
    }

    /**
     * Get media (image or video) for a specific page
     * Unified endpoint that serves all media from a single '_media' folder
     */
    public function getMedia(string $pageId, string $filename) {
        // Save original BEFORE sanitization
        $originalPageId = $pageId;
        $filename = basename($filename); // Prevent directory traversal

        $languageFolder = $this->getLanguageFolder();

        try {
            // Handle home page with original pageId
            if ($originalPageId === 'home' ||
                $originalPageId === '2e8f694e-147e-4793-8949-4732e679ae6b' ||
                $originalPageId === 'page-2e8f694e-147e-4793-8949-4732e679ae6b') {

                $mediaFolder = $languageFolder->get('_media');
                $file = $mediaFolder->get($filename);

                if ($file->getType() !== \OCP\Files\FileInfo::TYPE_FILE) {
                    throw new \Exception('Not a file');
                }

                // Get mime type
                $mimeType = $file->getMimeType();

                // Validate it's an allowed media type
                if (!in_array($mimeType, self::ALLOWED_MEDIA_TYPES)) {
                    throw new \Exception('Invalid media type');
                }

                // Create stream response
                $response = new \OCP\AppFramework\Http\StreamResponse($file->fopen('rb'));
                $response->addHeader('Content-Type', $mimeType);
                $response->addHeader('Content-Disposition', 'inline; filename="' . $file->getName() . '"');
                // Use longer cache for images, shorter for videos
                $isVideo = in_array($mimeType, self::ALLOWED_VIDEO_TYPES);
                $cacheTime = $isVideo ? 86400 : 31536000;
                $response->addHeader('Cache-Control', 'public, max-age=' . $cacheTime);

                return $response;
            }

            // Try cache with BOTH original and sanitized IDs
            $mediaFolder = null;
            $pageId = $this->sanitizeId($originalPageId);

            if (isset($this->pageFolderCache[$originalPageId])) {
                // Cache hit with original ID (page-abc-123...)
                $pageFolder = $this->pageFolderCache[$originalPageId];
                try {
                    $mediaFolder = $pageFolder->get('_media');
                } catch (NotFoundException $e) {
                    // No media folder
                }
            } else if (isset($this->pageFolderCache[$pageId])) {
                // Cache hit with sanitized ID (abc-123...)
                $pageFolder = $this->pageFolderCache[$pageId];
                try {
                    $mediaFolder = $pageFolder->get('_media');
                } catch (NotFoundException $e) {
                    // No media folder
                }
            }

            // If cache miss, search using ORIGINAL pageId
            if ($mediaFolder === null) {
                $mediaFolder = $this->findMediaFolderForPage($languageFolder, $originalPageId);
            }

            if ($mediaFolder === null) {
                throw new \Exception('Media folder not found');
            }

            $file = $mediaFolder->get($filename);

            if ($file->getType() !== \OCP\Files\FileInfo::TYPE_FILE) {
                throw new \Exception('Not a file');
            }

            // Get mime type
            $mimeType = $file->getMimeType();

            // Validate it's an allowed media type
            if (!in_array($mimeType, self::ALLOWED_MEDIA_TYPES)) {
                throw new \Exception('Invalid media type');
            }

            // Create stream response
            $response = new \OCP\AppFramework\Http\StreamResponse($file->fopen('rb'));
            $response->addHeader('Content-Type', $mimeType);
            $response->addHeader('Content-Disposition', 'inline; filename="' . $file->getName() . '"');
            // Use longer cache for images, shorter for videos
            $isVideo = in_array($mimeType, self::ALLOWED_VIDEO_TYPES);
            $cacheTime = $isVideo ? 86400 : 31536000;
            $response->addHeader('Cache-Control', 'public, max-age=' . $cacheTime);

            return $response;
        } catch (NotFoundException $e) {
            throw new \Exception('Media not found');
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
     * Recursively find media folder for a page by uniqueId
     */
    private function findMediaFolderForPage($folder, string $uniqueId): ?\OCP\Files\Folder {
        // First scan JSON files in CURRENT folder to see if page is here
        $foundMatch = false;
        foreach ($this->getCachedDirectoryListing($folder) as $item) {
            if ($item->getType() === \OCP\Files\FileInfo::TYPE_FILE &&
                substr($item->getName(), -5) === '.json' &&
                $item->getName() !== 'navigation.json' &&
                $item->getName() !== 'footer.json') {

                $content = $item->getContent();
                $data = json_decode($content, true);

                // Match against uniqueId field
                if ($data && isset($data['uniqueId']) && $data['uniqueId'] === $uniqueId) {
                    $foundMatch = true;
                    break;
                }
            }
        }

        // If we found the matching page JSON in this folder, return its media folder
        if ($foundMatch) {
            try {
                $mediaFolder = $folder->get('_media');
                if ($mediaFolder->getType() === \OCP\Files\FileInfo::TYPE_FOLDER) {
                    return $mediaFolder;
                }
            } catch (\OCP\Files\NotFoundException $e) {
                // Page found but no media folder
                return null;
            }
        }

        // Recursively search subfolders
        foreach ($this->getCachedDirectoryListing($folder) as $item) {
            if ($item->getType() === \OCP\Files\FileInfo::TYPE_FOLDER) {
                $itemName = $item->getName();

                // Skip special folders to avoid infinite loops
                if ($itemName === '_media' || $itemName === 'images' || $itemName === 'videos' || $itemName === '.nomedia') {
                    continue;
                }

                // Recurse into subfolder - wrap in try-catch to handle stale cache entries
                try {
                    $result = $this->findMediaFolderForPage($item, $uniqueId);
                    if ($result !== null) {
                        return $result;
                    }
                } catch (\OCP\Files\NotFoundException $e) {
                    // This subfolder doesn't actually exist (stale cache entry) - skip it
                    continue;
                } catch (\Exception $e) {
                    $this->logger->error("findMediaFolderForPage: Error accessing subfolder {$itemName}: {$e->getMessage()}");
                    continue;
                }
            }
        }

        return null;
    }

    /**
     * Validate and sanitize page data
     */
    private function validateAndSanitizePage(array $data): array {
        // Note: 'id' is NOT stored in JSON - the folder name IS the id
        $sanitized = [
            'title' => $this->sanitizeText($data['title']),
            'layout' => [
                'columns' => 1, // Default to 1 column
                'rows' => []
            ]
        ];

        // Preserve uniqueId if provided (for internal references)
        if (isset($data['uniqueId'])) {
            $sanitized['uniqueId'] = $data['uniqueId'];
        }

        // Preserve settings object (engagement settings for comments/reactions)
        if (isset($data['settings']) && is_array($data['settings'])) {
            $sanitized['settings'] = [
                'allowReactions' => isset($data['settings']['allowReactions']) ? (bool)$data['settings']['allowReactions'] : true,
                'allowComments' => isset($data['settings']['allowComments']) ? (bool)$data['settings']['allowComments'] : true,
                'allowCommentReactions' => isset($data['settings']['allowCommentReactions']) ? (bool)$data['settings']['allowCommentReactions'] : true,
            ];
        }

        if (isset($data['layout']['rows']) && is_array($data['layout']['rows'])) {
            foreach ($data['layout']['rows'] as $row) {
                if (isset($row['widgets']) && is_array($row['widgets'])) {
                    $sanitizedWidgets = [];

                    foreach ($row['widgets'] as $widget) {
                        $sanitizedWidget = $this->sanitizeWidget($widget);
                        if ($sanitizedWidget) {
                            $sanitizedWidgets[] = $sanitizedWidget;
                        } else {
                            // Log when widgets are dropped for debugging
                            $this->logger->warning('[validateAndSanitizePage] Widget dropped during validation', [
                                'type' => $widget['type'] ?? 'unknown',
                                'reason' => 'validation_failed'
                            ]);
                        }
                    }

                    $sanitizedRow = ['widgets' => $sanitizedWidgets];

                    // Preserve row ID if set (needed for collapsible state tracking)
                    if (isset($row['id'])) {
                        $sanitizedRow['id'] = $this->sanitizeText($row['id']);
                    }

                    // Preserve row-specific column count if set
                    if (isset($row['columns'])) {
                        $sanitizedRow['columns'] = $this->validateColumns($row['columns']);
                    }

                    // Preserve row background color if set
                    if (isset($row['backgroundColor'])) {
                        $sanitizedRow['backgroundColor'] = $this->sanitizeBackgroundColor($row['backgroundColor']);
                    }

                    // Preserve collapsible row settings
                    if (isset($row['collapsible'])) {
                        $sanitizedRow['collapsible'] = (bool)$row['collapsible'];
                    }
                    if (isset($row['sectionTitle'])) {
                        $sanitizedRow['sectionTitle'] = $this->sanitizeText($row['sectionTitle']);
                    }
                    if (isset($row['defaultCollapsed'])) {
                        $sanitizedRow['defaultCollapsed'] = (bool)$row['defaultCollapsed'];
                    }

                    // Keep row if it has widgets OR a background color OR is collapsible (don't silently drop empty styled/collapsible rows)
                    if (!empty($sanitizedWidgets) || !empty($sanitizedRow['backgroundColor']) || !empty($sanitizedRow['collapsible'])) {
                        $sanitized['layout']['rows'][] = $sanitizedRow;
                    }
                }
            }
        }

        // Validate and sanitize side columns
        if (isset($data['layout']['sideColumns']) && is_array($data['layout']['sideColumns'])) {
            $sanitized['layout']['sideColumns'] = $this->sanitizeSideColumns($data['layout']['sideColumns']);
        }

        // Validate and sanitize header row
        if (isset($data['layout']['headerRow']) && is_array($data['layout']['headerRow'])) {
            $sanitized['layout']['headerRow'] = $this->sanitizeHeaderRow($data['layout']['headerRow']);
        }

        return $sanitized;
    }

    /**
     * Sanitize side columns data
     */
    private function sanitizeSideColumns(array $sideColumns): array {
        $sanitized = [];

        foreach (['left', 'right'] as $side) {
            if (isset($sideColumns[$side]) && is_array($sideColumns[$side])) {
                $sideData = $sideColumns[$side];

                $sanitizedSide = [
                    'enabled' => !empty($sideData['enabled']),
                    'backgroundColor' => isset($sideData['backgroundColor'])
                        ? $this->sanitizeBackgroundColor($sideData['backgroundColor'])
                        : '',
                    'widgets' => []
                ];

                // Sanitize widgets in this side column
                if (isset($sideData['widgets']) && is_array($sideData['widgets'])) {
                    foreach ($sideData['widgets'] as $widget) {
                        $sanitizedWidget = $this->sanitizeWidget($widget);
                        if ($sanitizedWidget) {
                            $sanitizedSide['widgets'][] = $sanitizedWidget;
                        }
                    }
                }

                $sanitized[$side] = $sanitizedSide;
            }
        }

        return $sanitized;
    }

    /**
     * Sanitize header row data
     */
    private function sanitizeHeaderRow(array $headerRow): array {
        $sanitized = [
            'enabled' => !empty($headerRow['enabled']),
            'backgroundColor' => isset($headerRow['backgroundColor'])
                ? $this->sanitizeBackgroundColor($headerRow['backgroundColor'])
                : '',
            'widgets' => []
        ];

        // Sanitize widgets in header row
        if (isset($headerRow['widgets']) && is_array($headerRow['widgets'])) {
            foreach ($headerRow['widgets'] as $widget) {
                $sanitizedWidget = $this->sanitizeWidget($widget);
                if ($sanitizedWidget) {
                    $sanitized['widgets'][] = $sanitizedWidget;
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

        // Preserve widget ID if present (needed for frontend to identify widgets)
        if (isset($widget['id'])) {
            $sanitized['id'] = $this->sanitizeText($widget['id']);
        }

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
                // Preserve optional image properties
                if (isset($widget['width'])) {
                    $sanitized['width'] = $this->sanitizeText((string)($widget['width'] ?? ''));
                }
                if (isset($widget['objectFit'])) {
                    $allowedFits = ['cover', 'contain', 'fill', 'none', 'scale-down'];
                    $sanitized['objectFit'] = in_array($widget['objectFit'], $allowedFits) ? $widget['objectFit'] : 'cover';
                }
                if (isset($widget['objectPosition'])) {
                    $allowedPositions = ['center', 'top', 'bottom', 'left', 'right'];
                    $sanitized['objectPosition'] = in_array($widget['objectPosition'], $allowedPositions) ? $widget['objectPosition'] : 'center';
                }
                // Preserve mediaFolder property (for _resources folder media)
                if (isset($widget['mediaFolder'])) {
                    $allowedFolders = ['page', 'resources'];
                    $sanitized['mediaFolder'] = in_array($widget['mediaFolder'], $allowedFolders) ? $widget['mediaFolder'] : 'page';
                }
                // Preserve image link properties
                if (isset($widget['linkType'])) {
                    $allowedLinkTypes = ['none', 'internal', 'external'];
                    $sanitized['linkType'] = in_array($widget['linkType'], $allowedLinkTypes) ? $widget['linkType'] : 'none';
                }
                if (isset($widget['linkUrl'])) {
                    $sanitized['linkUrl'] = $this->sanitizeUrl($widget['linkUrl']);
                }
                if (isset($widget['linkPageId'])) {
                    $sanitized['linkPageId'] = $this->sanitizeText($widget['linkPageId']);
                }
                break;

            case 'links':
                $sanitized['items'] = [];
                if (isset($widget['items']) && is_array($widget['items'])) {
                    foreach ($widget['items'] as $link) {
                        $sanitizedLink = [];
                        // Preserve title if present
                        if (isset($link['title'])) {
                            $sanitizedLink['title'] = $this->sanitizeText($link['title']);
                        }
                        // Use sanitizeHtml for link text to allow HTML entities and formatting
                        $sanitizedLink['text'] = $this->sanitizeHtml($link['text'] ?? '');
                        $sanitizedLink['url'] = $this->sanitizeUrl($link['url'] ?? '');
                        $sanitizedLink['icon'] = $this->sanitizeText($link['icon'] ?? '');
                        // Preserve uniqueId for internal page links
                        if (isset($link['uniqueId']) && !empty($link['uniqueId'])) {
                            $sanitizedLink['uniqueId'] = $this->sanitizeText($link['uniqueId']);
                        }
                        // Preserve target attribute
                        if (isset($link['target'])) {
                            $allowedTargets = ['_self', '_blank'];
                            $sanitizedLink['target'] = in_array($link['target'], $allowedTargets) ? $link['target'] : '_self';
                        }
                        if (isset($link['backgroundColor'])) {
                            $sanitizedLink['backgroundColor'] = $this->sanitizeBackgroundColor($link['backgroundColor']);
                        }
                        $sanitized['items'][] = $sanitizedLink;
                    }
                }
                $sanitized['columns'] = max(1, min((int)($widget['columns'] ?? 2), 4));
                if (isset($widget['backgroundColor'])) {
                    $sanitized['backgroundColor'] = $this->sanitizeBackgroundColor($widget['backgroundColor']);
                }
                break;

            case 'file':
                $sanitized['path'] = $this->sanitizePath($widget['path'] ?? '');
                $sanitized['name'] = $this->sanitizeText($widget['name'] ?? '');
                break;

            case 'divider':
                // Preserve divider styling properties
                if (isset($widget['style'])) {
                    $allowedStyles = ['solid', 'dashed', 'dotted'];
                    $sanitized['style'] = in_array($widget['style'], $allowedStyles) ? $widget['style'] : 'solid';
                }
                if (isset($widget['color'])) {
                    $sanitized['color'] = $this->sanitizeBackgroundColor($widget['color']);
                }
                if (isset($widget['height'])) {
                    // Allow valid CSS height values like "2px", "1rem", etc.
                    $sanitized['height'] = preg_match('/^\d+(px|rem|em|%)$/', $widget['height'])
                        ? $widget['height']
                        : '2px';
                }
                break;

            case 'spacer':
                // Spacer widget - just adds vertical space
                if (isset($widget['height'])) {
                    $height = (int)$widget['height'];
                    $sanitized['height'] = max(10, min($height, 200)); // 10-200px range
                } else {
                    $sanitized['height'] = 20; // default 20px
                }
                break;

            case 'video':
                // Video widget - embed URL or local file
                // Supports: 'embed' (generic URL), 'local' (uploaded file)
                // Legacy 'peertube' is treated as 'embed' for backwards compatibility
                $provider = ($widget['provider'] ?? 'embed') === 'local' ? 'local' : 'embed';
                $sanitized['provider'] = $provider;
                $sanitized['title'] = $this->sanitizeText($widget['title'] ?? '');

                if ($provider === 'embed') {
                    // FIX: Check src (embed URL) first, then fallback to originalSrc
                    // The frontend converts youtube.com → youtube-nocookie.com in src
                    // but preserves the original URL in originalSrc
                    // We need to validate src (the embed URL) against the whitelist
                    $srcUrl = $widget['src'] ?? '';
                    $originalUrl = $widget['originalSrc'] ?? '';

                    // Validate src first (converted embed URL), fallback to originalSrc
                    $urlToValidate = !empty($srcUrl) ? $srcUrl : $originalUrl;
                    $sanitizedUrl = $this->sanitizeVideoEmbedUrl($urlToValidate);

                    if ($sanitizedUrl === '' && !empty($originalUrl)) {
                        // URL was blocked - preserve original URL so it can work again
                        // if admin adds the domain to whitelist later
                        $sanitized['src'] = '';
                        $sanitized['originalSrc'] = $originalUrl; // Preserve for later
                        $sanitized['blocked'] = true;
                        // Show blockedDomain based on what we validated
                        $blockedHost = !empty($srcUrl)
                            ? parse_url($srcUrl, PHP_URL_HOST)
                            : parse_url($originalUrl, PHP_URL_HOST);
                        $sanitized['blockedDomain'] = $blockedHost ?? '';
                    } else {
                        $sanitized['src'] = $sanitizedUrl;
                        $sanitized['originalSrc'] = $originalUrl ?: $sanitizedUrl; // Always preserve original
                        $sanitized['blocked'] = false;
                    }
                } else {
                    // Local video file - sanitize path
                    $sanitized['src'] = $this->sanitizePath($widget['src'] ?? '');
                    $sanitized['blocked'] = false;
                }

                // Preserve mediaFolder property (for _resources folder media)
                if (isset($widget['mediaFolder'])) {
                    $allowedFolders = ['page', 'resources'];
                    $sanitized['mediaFolder'] = in_array($widget['mediaFolder'], $allowedFolders) ? $widget['mediaFolder'] : 'page';
                }

                // Playback options (boolean values)
                $sanitized['autoplay'] = (bool) ($widget['autoplay'] ?? false);
                $sanitized['loop'] = (bool) ($widget['loop'] ?? false);
                $sanitized['muted'] = (bool) ($widget['muted'] ?? false);
                break;

            case 'news':
                // News widget - displays pages from a folder with optional MetaVox filters
                $sanitized['title'] = $this->sanitizeText($widget['title'] ?? '');
                $sanitized['sourcePath'] = $this->sanitizePath($widget['sourcePath'] ?? '');
                // sourcePageId is the uniqueId of the source page/folder (new PageTreeSelect approach)
                $sanitized['sourcePageId'] = isset($widget['sourcePageId']) && !empty($widget['sourcePageId'])
                    ? preg_replace('/[^a-zA-Z0-9_-]/', '', $widget['sourcePageId'])
                    : null;

                // Layout options
                $allowedLayouts = ['list', 'grid', 'carousel'];
                $sanitized['layout'] = in_array($widget['layout'] ?? 'list', $allowedLayouts)
                    ? $widget['layout']
                    : 'list';

                // Grid columns (2-4)
                $sanitized['columns'] = max(2, min((int)($widget['columns'] ?? 3), 4));

                // Limit (1-20 items)
                $sanitized['limit'] = max(1, min((int)($widget['limit'] ?? 5), 20));

                // Sort options
                $allowedSortBy = ['modified', 'title'];
                $sanitized['sortBy'] = in_array($widget['sortBy'] ?? 'modified', $allowedSortBy)
                    ? $widget['sortBy']
                    : 'modified';

                $allowedSortOrder = ['asc', 'desc'];
                $sanitized['sortOrder'] = in_array($widget['sortOrder'] ?? 'desc', $allowedSortOrder)
                    ? $widget['sortOrder']
                    : 'desc';

                // Display options (booleans)
                $sanitized['showImage'] = (bool)($widget['showImage'] ?? true);
                $sanitized['showDate'] = (bool)($widget['showDate'] ?? true);
                $sanitized['showExcerpt'] = (bool)($widget['showExcerpt'] ?? true);
                $sanitized['excerptLength'] = max(50, min((int)($widget['excerptLength'] ?? 100), 500));

                // Carousel autoplay interval (0-30 seconds, 0 = disabled)
                $sanitized['autoplayInterval'] = max(0, min((int)($widget['autoplayInterval'] ?? 5), 30));

                // MetaVox filters
                $sanitized['filters'] = [];
                if (isset($widget['filters']) && is_array($widget['filters'])) {
                    foreach ($widget['filters'] as $filter) {
                        if (isset($filter['fieldName']) && !empty($filter['fieldName'])) {
                            $allowedOperators = [
                                // Text
                                'equals', 'contains', 'not_contains', 'in',
                                // Empty
                                'not_empty', 'empty',
                                // Date
                                'before', 'after',
                                // Number
                                'greater_than', 'less_than', 'greater_or_equal', 'less_or_equal',
                                // Checkbox
                                'is_true', 'is_false',
                                // Multiselect
                                'contains_all',
                            ];
                            $sanitizedFilter = [
                                'fieldName' => $this->sanitizeText($filter['fieldName']),
                                'operator' => in_array($filter['operator'] ?? 'equals', $allowedOperators)
                                    ? $filter['operator']
                                    : 'equals',
                                'value' => $this->sanitizeText((string)($filter['value'] ?? '')),
                                'values' => [],
                            ];

                            // Sanitize values array (for 'in', 'contains', 'contains_all' operators)
                            if (isset($filter['values']) && is_array($filter['values'])) {
                                $sanitizedFilter['values'] = array_map(
                                    fn($v) => $this->sanitizeText((string)$v),
                                    $filter['values']
                                );
                            }

                            $sanitized['filters'][] = $sanitizedFilter;
                        }
                    }
                }

                $allowedFilterOperators = ['AND', 'OR'];
                $sanitized['filterOperator'] = in_array($widget['filterOperator'] ?? 'AND', $allowedFilterOperators)
                    ? $widget['filterOperator']
                    : 'AND';

                // Publication date filter (show only published pages)
                $sanitized['filterPublished'] = (bool)($widget['filterPublished'] ?? false);
                break;

            case 'people':
                // People widget - displays user profiles
                $sanitized['title'] = $this->sanitizeText($widget['title'] ?? '');

                // Selection mode
                $allowedModes = ['manual', 'filter'];
                $sanitized['selectionMode'] = in_array($widget['selectionMode'] ?? 'manual', $allowedModes)
                    ? $widget['selectionMode']
                    : 'manual';

                // Selected users (array of user IDs for manual mode)
                $sanitized['selectedUsers'] = [];
                if (isset($widget['selectedUsers']) && is_array($widget['selectedUsers'])) {
                    foreach ($widget['selectedUsers'] as $userId) {
                        // User IDs are alphanumeric strings
                        $sanitizedUserId = preg_replace('/[^a-zA-Z0-9_-]/', '', (string)$userId);
                        if (!empty($sanitizedUserId)) {
                            $sanitized['selectedUsers'][] = $sanitizedUserId;
                        }
                    }
                }

                // Filters (for filter mode)
                $sanitized['filters'] = [];
                if (isset($widget['filters']) && is_array($widget['filters'])) {
                    foreach ($widget['filters'] as $filter) {
                        if (isset($filter['fieldName']) && !empty($filter['fieldName'])) {
                            $allowedOperators = [
                                'equals', 'contains', 'in', 'not_empty', 'empty',
                                // Date operators
                                'is_today', 'within_next_days', 'before', 'after',
                            ];
                            $sanitizedFilter = [
                                'fieldName' => $this->sanitizeText($filter['fieldName']),
                                'operator' => in_array($filter['operator'] ?? 'equals', $allowedOperators)
                                    ? $filter['operator']
                                    : 'equals',
                                'value' => $this->sanitizeText((string)($filter['value'] ?? '')),
                                'values' => [],
                            ];

                            // Sanitize values array (for 'in' operator)
                            if (isset($filter['values']) && is_array($filter['values'])) {
                                $sanitizedFilter['values'] = array_map(
                                    fn($v) => $this->sanitizeText((string)$v),
                                    $filter['values']
                                );
                            }

                            $sanitized['filters'][] = $sanitizedFilter;
                        }
                    }
                }

                $allowedFilterOperators = ['AND', 'OR'];
                $sanitized['filterOperator'] = in_array($widget['filterOperator'] ?? 'AND', $allowedFilterOperators)
                    ? $widget['filterOperator']
                    : 'AND';

                // Layout options
                $allowedLayouts = ['card', 'list', 'grid'];
                $sanitized['layout'] = in_array($widget['layout'] ?? 'card', $allowedLayouts)
                    ? $widget['layout']
                    : 'card';

                // Grid/card columns (2-4)
                $sanitized['columns'] = max(2, min((int)($widget['columns'] ?? 3), 4));

                // Limit (1-50 people)
                $sanitized['limit'] = max(1, min((int)($widget['limit'] ?? 12), 50));

                // Sort options
                $allowedSortBy = ['displayName', 'email'];
                $sanitized['sortBy'] = in_array($widget['sortBy'] ?? 'displayName', $allowedSortBy)
                    ? $widget['sortBy']
                    : 'displayName';

                $allowedSortOrder = ['asc', 'desc'];
                $sanitized['sortOrder'] = in_array($widget['sortOrder'] ?? 'asc', $allowedSortOrder)
                    ? $widget['sortOrder']
                    : 'asc';

                // Display options (showFields object)
                $sanitized['showFields'] = [
                    // Basic information
                    'avatar' => (bool)($widget['showFields']['avatar'] ?? true),
                    'displayName' => (bool)($widget['showFields']['displayName'] ?? true),
                    'pronouns' => (bool)($widget['showFields']['pronouns'] ?? false),
                    'role' => (bool)($widget['showFields']['role'] ?? true),
                    'headline' => (bool)($widget['showFields']['headline'] ?? false),
                    'department' => (bool)($widget['showFields']['department'] ?? true),
                    'title' => (bool)($widget['showFields']['title'] ?? ($widget['showFields']['role'] ?? true)),
                    // Contact
                    'email' => (bool)($widget['showFields']['email'] ?? true),
                    'phone' => (bool)($widget['showFields']['phone'] ?? false),
                    'address' => (bool)($widget['showFields']['address'] ?? false),
                    'website' => (bool)($widget['showFields']['website'] ?? false),
                    'birthdate' => (bool)($widget['showFields']['birthdate'] ?? false),
                    // Extended
                    'biography' => (bool)($widget['showFields']['biography'] ?? false),
                    'socialLinks' => (bool)($widget['showFields']['socialLinks'] ?? false),
                    'customFields' => (bool)($widget['showFields']['customFields'] ?? false),
                ];

                // Background color
                if (isset($widget['backgroundColor'])) {
                    $sanitized['backgroundColor'] = $this->sanitizeBackgroundColor($widget['backgroundColor']);
                }
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
        // Define allowed tags - must match frontend sanitization.js ALLOWED_TAGS
        // Text structure
        $allowedTags = '<p><br><span><div>';
        // Text formatting
        $allowedTags .= '<strong><b><em><i><u><s><del><mark><sub><sup>';
        // Headings
        $allowedTags .= '<h1><h2><h3><h4><h5><h6>';
        // Lists
        $allowedTags .= '<ul><ol><li>';
        // Links
        $allowedTags .= '<a>';
        // Block elements
        $allowedTags .= '<blockquote><pre><code>';
        // Tables - CRITICAL for TipTap table support
        $allowedTags .= '<table><thead><tbody><tfoot><tr><th><td><caption><colgroup><col>';
        // Task lists (TipTap uses data attributes)
        $allowedTags .= '<input><label>';

        // Strip all tags except allowed ones
        $cleaned = strip_tags($html, $allowedTags);

        // Additional XSS prevention: remove any event handlers or javascript
        $cleaned = preg_replace('/on\w+\s*=\s*["\']?[^"\']*["\']?/i', '', $cleaned);
        $cleaned = preg_replace('/javascript:/i', '', $cleaned);

        // Sanitize style attributes - only allow safe CSS properties
        // This allows background-color and text-align for table cells
        $cleaned = preg_replace_callback(
            '/style\s*=\s*["\']([^"\']*)["\']?/i',
            function ($matches) {
                $style = $matches[1];
                $allowedProperties = [];

                // Extract and validate each CSS property
                preg_match_all('/([a-z-]+)\s*:\s*([^;]+)/i', $style, $props, PREG_SET_ORDER);

                foreach ($props as $prop) {
                    $property = strtolower(trim($prop[1]));
                    $value = trim($prop[2]);

                    // Only allow specific safe properties
                    if (in_array($property, ['background-color', 'text-align', 'color', 'width'])) {
                        // Validate values - no javascript or expressions
                        if (!preg_match('/expression|javascript|url\s*\(/i', $value)) {
                            $allowedProperties[] = $property . ': ' . $value;
                        }
                    }
                }

                if (empty($allowedProperties)) {
                    return '';
                }

                return 'style="' . implode('; ', $allowedProperties) . '"';
            },
            $cleaned
        );

        // Clean up any malformed HTML entities
        $cleaned = preg_replace('/&(?![a-z]+;|#[0-9]+;|#x[0-9a-f]+;)/i', '&amp;', $cleaned);

        return $cleaned;
    }

    /**
     * Sanitize file path - prevent directory traversal and other path attacks
     *
     * Security checks:
     * - Null byte injection
     * - Unicode normalization (NFD/NFC attacks)
     * - Directory traversal (..)
     * - Backslash conversion
     * - Hidden files (starting with .)
     * - Executable file extensions
     *
     * @param string $path User-provided path
     * @return string Safe path
     * @throws \InvalidArgumentException if path is malicious
     */
    private function sanitizePath(string $path): string {
        // Allow empty paths (used for news widget sourcePath to indicate "all pages")
        if (empty($path)) {
            return '';
        }

        // 1. Check for null bytes (can bypass extension checks)
        if (strpos($path, "\0") !== false) {
            throw new \InvalidArgumentException('Null bytes not allowed in path');
        }

        // 2. Unicode normalization (prevent NFD/NFC attacks)
        if (class_exists('Normalizer')) {
            $normalized = \Normalizer::normalize($path, \Normalizer::FORM_C);
            if ($normalized === false) {
                throw new \InvalidArgumentException('Invalid unicode sequence in path');
            }
            $path = $normalized;
        }

        // 3. Convert backslashes to forward slashes
        $path = str_replace('\\', '/', $path);

        // 4. Remove leading/trailing slashes
        $path = trim($path, '/');

        // 5. If path becomes empty after trimming, return empty
        if (empty($path)) {
            return '';
        }

        // 6. Detect directory traversal attempts
        if (strpos($path, '..') !== false) {
            throw new \InvalidArgumentException('Path traversal not allowed');
        }

        // 7. Validate path segments
        $segments = explode('/', $path);
        foreach ($segments as $segment) {
            // Empty segments (double slashes)
            if (empty($segment) || $segment === '.' || $segment === '..') {
                throw new \InvalidArgumentException('Invalid path segment');
            }

            // Hidden files (starting with dot)
            if (substr($segment, 0, 1) === '.') {
                throw new \InvalidArgumentException('Hidden files not allowed');
            }

            // Block executable PHP extensions
            if (preg_match('/\.(php|phtml|php[345]|phar|phps|pht)$/i', $segment)) {
                throw new \InvalidArgumentException('Executable files not allowed');
            }
        }

        return $path;
    }

    /**
     * Sanitize URL
     */
    private function sanitizeUrl(string $url): string {
        $url = filter_var($url, FILTER_SANITIZE_URL);

        // Only allow http, https, relative URLs, and hash links
        if (!empty($url) && !preg_match('/^(https?:\/\/|\/|#)/i', $url)) {
            return '';
        }

        return $url;
    }

    /**
     * Get allowed video domains from config
     * @return array List of allowed HTTPS domains
     */
    private function getAllowedVideoDomains(): array {
        $domains = $this->config->getAppValue(
            'intravox',
            'video_domains',
            Constants::getDefaultVideoDomainsJson()
        );

        // Decode the stored JSON
        $decoded = json_decode($domains, true);

        // Only use defaults if JSON decode FAILED (null), not for empty array
        // This allows admins to explicitly block all video embeds by removing all domains
        if ($decoded === null) {
            return Constants::DEFAULT_VIDEO_DOMAINS;
        }

        return $decoded;
    }

    /**
     * Map of video platform domains to their embed domains.
     * When a user enters youtube.com, the frontend converts it to youtube-nocookie.com.
     * This mapping allows the whitelist check to recognize both.
     */
    private const VIDEO_DOMAIN_ALIASES = [
        // YouTube watch URLs → youtube-nocookie.com embed
        'www.youtube.com' => 'www.youtube-nocookie.com',
        'youtube.com' => 'www.youtube-nocookie.com',
        'm.youtube.com' => 'www.youtube-nocookie.com',
        // Vimeo watch URLs → player.vimeo.com embed
        'www.vimeo.com' => 'player.vimeo.com',
        'vimeo.com' => 'player.vimeo.com',
    ];

    /**
     * Sanitize video embed URL
     * Validates against configured whitelist of allowed domains
     * Supports: YouTube, Vimeo, PeerTube, Dailymotion, Twitch, TikTok, etc.
     */
    private function sanitizeVideoEmbedUrl(string $url): string {
        if (empty($url)) {
            return '';
        }

        // Must be HTTPS
        if (!str_starts_with($url, 'https://')) {
            return '';
        }

        // Parse URL
        $parsed = parse_url($url);
        if (!$parsed || !isset($parsed['scheme']) || !isset($parsed['host']) || !isset($parsed['path'])) {
            return '';
        }

        // Check against whitelist
        $allowedDomains = $this->getAllowedVideoDomains();
        $host = $parsed['host'];

        // Check if this host has an alias (e.g., youtube.com → youtube-nocookie.com)
        $embedHost = self::VIDEO_DOMAIN_ALIASES[$host] ?? null;

        $isAllowed = false;
        foreach ($allowedDomains as $allowedDomain) {
            $allowedHost = parse_url($allowedDomain, PHP_URL_HOST);
            // Match either the original host OR its embed alias
            if ($host === $allowedHost || ($embedHost && $embedHost === $allowedHost)) {
                $isAllowed = true;
                break;
            }
        }

        if (!$isAllowed) {
            $this->logger->warning('Video domain not in whitelist: ' . $host);
            return '';
        }

        // Convert watch URLs to embed URLs for known platforms
        // YouTube: https://www.youtube.com/watch?v=VIDEO_ID → https://www.youtube-nocookie.com/embed/VIDEO_ID
        if (in_array($host, ['www.youtube.com', 'youtube.com', 'm.youtube.com'])) {
            parse_str($parsed['query'] ?? '', $queryParams);
            $videoId = $queryParams['v'] ?? null;
            if ($videoId) {
                return 'https://www.youtube-nocookie.com/embed/' . urlencode($videoId);
            }
            // If already an embed URL or other format, pass through
            if (str_contains($parsed['path'], '/embed/')) {
                return 'https://www.youtube-nocookie.com' . $parsed['path'];
            }
        }

        // Vimeo: https://vimeo.com/VIDEO_ID → https://player.vimeo.com/video/VIDEO_ID
        if (in_array($host, ['www.vimeo.com', 'vimeo.com'])) {
            // Extract video ID from path like /123456789 or /123456789?h=xxxxx
            if (preg_match('#^/(\d+)#', $parsed['path'], $matches)) {
                $videoId = $matches[1];
                $embedUrl = 'https://player.vimeo.com/video/' . $videoId;
                // Preserve hash parameter for unlisted videos
                if (isset($parsed['query'])) {
                    parse_str($parsed['query'], $queryParams);
                    if (isset($queryParams['h'])) {
                        $embedUrl .= '?h=' . urlencode($queryParams['h']);
                    }
                }
                return $embedUrl;
            }
        }

        // For PeerTube URLs, enforce privacy settings
        if (str_contains($parsed['path'], '/videos/embed/')) {
            $cleanUrl = $parsed['scheme'] . '://' . $parsed['host'] . $parsed['path'];
            return $cleanUrl . '?p2p=0&peertubeLink=0';
        }

        // For other platforms, return the embed URL with existing query params
        $cleanUrl = $parsed['scheme'] . '://' . $parsed['host'] . $parsed['path'];
        if (isset($parsed['query'])) {
            $cleanUrl .= '?' . $parsed['query'];
        }
        return $cleanUrl;
    }

    /**
     * Validate column count
     */
    private function validateColumns(int $columns): int {
        return max(1, min($columns, self::MAX_COLUMNS));
    }

    /**
     * Sanitize background color (allow theme CSS variables, hex colors, rgba, transparent)
     */
    private function sanitizeBackgroundColor(string $color): string {
        // Empty string is allowed (transparent/default)
        if (empty($color)) {
            return '';
        }

        // Allow Nextcloud theme CSS variables and specific safe values
        $allowedColors = [
            'var(--color-primary-element)',
            'var(--color-primary-element-light)',
            'var(--color-background-hover)',
            'var(--color-border)',
            'transparent',
            'rgba(255,255,255,0.3)'
        ];

        if (in_array($color, $allowedColors)) {
            return $color;
        }

        // Allow hex colors (#RGB or #RRGGBB)
        if (preg_match('/^#([0-9A-Fa-f]{3}|[0-9A-Fa-f]{6})$/', $color)) {
            return $color;
        }

        // Allow rgba/rgb colors
        if (preg_match('/^rgba?\(\s*\d{1,3}\s*,\s*\d{1,3}\s*,\s*\d{1,3}\s*(,\s*[\d.]+\s*)?\)$/', $color)) {
            return $color;
        }

        // Invalid color, return empty (default)
        return '';
    }

    /**
     * Sanitize page for output (decode HTML entities for display)
     */
    private function sanitizePage(array $data): array {
        // Re-sanitize widgets on every read to apply current whitelist settings
        // This ensures blocked video domains are marked correctly even if the
        // whitelist changed after the page was saved

        if (isset($data['layout']['rows']) && is_array($data['layout']['rows'])) {
            foreach ($data['layout']['rows'] as $rowIndex => $row) {
                if (isset($row['widgets']) && is_array($row['widgets'])) {
                    foreach ($row['widgets'] as $widgetIndex => $widget) {
                        // Only re-sanitize video widgets to check against current whitelist
                        if (($widget['type'] ?? '') === 'video') {
                            $sanitized = $this->sanitizeWidget($widget);
                            if ($sanitized) {
                                $data['layout']['rows'][$rowIndex]['widgets'][$widgetIndex] = $sanitized;
                            }
                        }
                    }
                }
            }
        }

        // Also sanitize side columns
        if (isset($data['layout']['sideColumns']) && is_array($data['layout']['sideColumns'])) {
            foreach (['left', 'right'] as $side) {
                if (isset($data['layout']['sideColumns'][$side]['widgets']) && is_array($data['layout']['sideColumns'][$side]['widgets'])) {
                    foreach ($data['layout']['sideColumns'][$side]['widgets'] as $widgetIndex => $widget) {
                        if (($widget['type'] ?? '') === 'video') {
                            $sanitized = $this->sanitizeWidget($widget);
                            if ($sanitized) {
                                $data['layout']['sideColumns'][$side]['widgets'][$widgetIndex] = $sanitized;
                            }
                        }
                    }
                }
            }
        }

        // Also sanitize header row
        if (isset($data['layout']['headerRow']['widgets']) && is_array($data['layout']['headerRow']['widgets'])) {
            foreach ($data['layout']['headerRow']['widgets'] as $widgetIndex => $widget) {
                if (($widget['type'] ?? '') === 'video') {
                    $sanitized = $this->sanitizeWidget($widget);
                    if ($sanitized) {
                        $data['layout']['headerRow']['widgets'][$widgetIndex] = $sanitized;
                    }
                }
            }
        }

        return $data;
    }

    /**
     * Get all versions of a page
     * Uses the standard IVersionManager interface for reliable version retrieval.
     * @throws \Exception if page not found
     */
    public function getPageVersions(string $pageId): array {
        $this->logger->info('[getPageVersions] START - Getting versions for page: ' . $pageId);

        $folder = $this->getLanguageFolder();
        $result = null;

        // Check for uniqueId pattern (page-xxxx) like getPage() does
        if (strpos($pageId, 'page-') === 0) {
            $result = $this->findPageByUniqueId($folder, $pageId);
        }

        // Fall back to legacy ID lookup
        if ($result === null) {
            $result = $this->findPageById($folder, $this->sanitizeId($pageId));
        }

        if (!$result) {
            $this->logger->warning('[getPageVersions] Page not found: ' . $pageId);
            throw new \Exception('Page not found: ' . $pageId);
        }

        $file = $result['file'];
        $user = $this->userSession->getUser();

        $this->logger->info('[getPageVersions] File found: ' . $file->getPath() . ' (ID: ' . $file->getId() . ')');
        $this->logger->info('[getPageVersions] Storage class: ' . get_class($file->getStorage()));

        if (!$user) {
            $this->logger->warning('[getPageVersions] No user in session');
            return [];
        }

        $this->logger->info('[getPageVersions] User: ' . $user->getUID());

        if (!$this->versionManager) {
            $this->logger->warning('[getPageVersions] Version manager not available');
            return [];
        }

        $this->logger->info('[getPageVersions] Version manager class: ' . get_class($this->versionManager));

        try {
            $this->logger->info('[getPageVersions] Calling getVersionsForFile...');

            // Use IVersionManager - works for all storage types including GroupFolders
            $versions = $this->versionManager->getVersionsForFile($user, $file);

            $this->logger->info('[getPageVersions] IVersionManager returned ' . count($versions) . ' versions');

            // Get current file metadata (like Nextcloud Files app shows)
            $currentVersion = [
                'timestamp' => $file->getMTime(),
                'size' => $file->getSize(),
                'author' => $this->getCurrentFileAuthor($file),
                'relativeTime' => $this->formatRelativeTime($file->getMTime()),
            ];

            return [
                'currentVersion' => $currentVersion,
                'versions' => $this->formatVersionsFromBackend($versions),
            ];

        } catch (\Exception $e) {
            $this->logger->error('[getPageVersions] Failed to get page versions: ' . $e->getMessage(), [
                'pageId' => $pageId,
                'exception' => $e->getTraceAsString(),
            ]);
            return [
                'currentVersion' => null,
                'versions' => [],
            ];
        }
    }

    /**
     * Format versions from IVersionManager to array format for API response
     */
    private function formatVersionsFromBackend(array $versions): array {
        $formattedVersions = [];

        foreach ($versions as $version) {
            /** @var IVersion $version */
            $timestamp = $version->getTimestamp();

            $formattedVersions[] = [
                'id' => $timestamp,
                'timestamp' => $timestamp,
                'size' => $version->getSize(),
                'author' => $this->getVersionAuthor($version),
                'label' => $this->getVersionLabel($version),
                'formattedDate' => $this->formatVersionDate($timestamp),
                'relativeTime' => $this->formatRelativeTime($timestamp),
            ];
        }

        // Sort by timestamp descending (newest first)
        usort($formattedVersions, fn($a, $b) => $b['timestamp'] - $a['timestamp']);

        return $formattedVersions;
    }

    /**
     * Get author from version metadata (if available)
     */
    private function getVersionAuthor(IVersion $version): ?string {
        // IVersion doesn't have getMetadata(), but GroupVersion does
        if (method_exists($version, 'getMetadata')) {
            $metadata = $version->getMetadata();
            return $metadata['author'] ?? null;
        }
        return null;
    }

    /**
     * Get label from version metadata (if available)
     */
    private function getVersionLabel(IVersion $version): ?string {
        if (method_exists($version, 'getMetadata')) {
            $metadata = $version->getMetadata();
            return $metadata['label'] ?? null;
        }
        return null;
    }

    /**
     * Get the author of the current file (last modifier)
     * Uses the file owner as fallback since we don't track individual modifiers
     */
    private function getCurrentFileAuthor(\OCP\Files\File $file): ?string {
        try {
            // Try to get the owner of the file
            $owner = $file->getOwner();
            if ($owner !== null) {
                return $owner->getDisplayName() ?: $owner->getUID();
            }
            // Fallback to current user if available
            $user = $this->userSession->getUser();
            return $user ? ($user->getDisplayName() ?: $user->getUID()) : null;
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Format a version timestamp for display
     * Uses relative time format like Nextcloud Files app
     */
    private function formatVersionDate(int $timestamp): string {
        return $this->formatRelativeTime($timestamp);
    }

    /**
     * Format timestamp as relative time like Nextcloud Files app
     * Examples: "36 sec. ago", "2 min. ago", "1 hour ago", "3 days ago"
     */
    private function formatRelativeTime(int $timestamp): string {
        $now = time();
        $diff = $now - $timestamp;

        if ($diff < 0) {
            // Future timestamp (shouldn't happen, but handle gracefully)
            return 'just now';
        } elseif ($diff < 60) {
            return $diff . ' sec. ago';
        } elseif ($diff < 3600) {
            $mins = (int) floor($diff / 60);
            return $mins . ' min. ago';
        } elseif ($diff < 86400) {
            $hours = (int) floor($diff / 3600);
            return $hours . ' hour' . ($hours > 1 ? 's' : '') . ' ago';
        } elseif ($diff < 604800) { // Less than 7 days
            $days = (int) floor($diff / 86400);
            return $days . ' day' . ($days > 1 ? 's' : '') . ' ago';
        } else {
            // Older than 7 days - show date
            $date = new \DateTime();
            $date->setTimestamp($timestamp);
            return $date->format('j M Y');
        }
    }

    /**
     * Find a file by its ID within a folder
     */
    private function findFileByIdInFolder(\OCP\Files\Folder $folder, int $fileId): ?\OCP\Files\File {
        try {
            $files = $this->getCachedDirectoryListing($folder);
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
            // Error searching folder
        }
        return null;
    }

    /**
     * Restore a specific version of a page
     * Uses IVersionManager for reliable version restoration across all storage types.
     * @throws \Exception if page or version not found
     */
    public function restorePageVersion(string $pageId, int $timestamp): array {
        $folder = $this->getLanguageFolder();
        $result = null;

        // Check for uniqueId pattern (page-xxxx) like getPage() does
        if (strpos($pageId, 'page-') === 0) {
            $result = $this->findPageByUniqueId($folder, $pageId);
        }

        // Fall back to legacy ID lookup
        if ($result === null) {
            $result = $this->findPageById($folder, $this->sanitizeId($pageId));
        }

        if (!$result) {
            throw new \Exception('Page not found: ' . $pageId);
        }

        $file = $result['file'];
        $user = $this->userSession->getUser();

        if (!$user) {
            throw new \Exception('No user in session');
        }

        if (!$this->versionManager) {
            throw new \Exception('Version manager not available');
        }

        try {
            // Use IVersionManager - works for all storage types including GroupFolders
            $versions = $this->versionManager->getVersionsForFile($user, $file);

            // Find the version with matching timestamp
            $targetVersion = null;
            foreach ($versions as $version) {
                if ($version->getTimestamp() === $timestamp) {
                    $targetVersion = $version;
                    break;
                }
            }

            if (!$targetVersion) {
                throw new \Exception('Version not found for timestamp: ' . $timestamp);
            }

            // Rollback via IVersionManager
            $this->versionManager->rollback($targetVersion);

            // Re-read the file content after rollback
            $content = $file->getContent();
            $restoredData = json_decode($content, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \Exception('Restored version contains invalid JSON data');
            }

            // Return data with id for frontend (id is derived from folder name)
            // For home page it's 'home', otherwise use the folder basename
            $resolvedId = ($pageId === 'home') ? 'home' : $result['folder']->getName();
            return array_merge(['id' => $resolvedId], $restoredData);
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
     * Uses IVersionManager for version creation across all storage types
     */
    private function createVersionBeforeUpdate(\OCP\Files\File $file): void {
        if (!$this->versionManager) {
            return;
        }

        try {
            $user = $this->userSession->getUser();
            if (!$user) {
                return;
            }

            // Use IVersionManager - works for all storage types including GroupFolders
            $this->versionManager->createVersion($user, $file);
        } catch (\Exception $e) {
            // Don't throw - versioning failure shouldn't prevent saves
            $this->logger->warning('[createVersionBeforeUpdate] Failed: ' . $e->getMessage());
        }
    }

    /**
     * Generate a UUID v4
     */
    private function generateUUID(): string {
        $data = random_bytes(16);

        // Set version to 4
        $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
        // Set variant to RFC 4122
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80);

        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }

    /**
     * Get the actual file ID from the database using the groupfolder storage
     *
     * This is necessary because $file->getId() may return the user mount file ID
     * instead of the groupfolder storage file ID that MetaVox needs.
     *
     * @param \OCP\Files\File $file The file object
     * @param \OCP\Files\Folder $folder The parent folder
     * @return int The actual file ID from the groupfolder storage
     */
    private function getFileIdFromDatabase($file, $folder): int {
        try {
            $filePath = $file->getPath();

            // Extract groupfolder ID and relative path from the full path
            // Path format: /__groupfolders/4/files/en/home.json
            // We need: groupfolderId=4, relPath="files/en/home.json"
            if (preg_match('#/__groupfolders/(\d+)/(.+)$#', $filePath, $matches)) {
                $groupfolderId = (int)$matches[1];
                $relPath = $matches[2];

                // Find the storage ID for this groupfolder
                $qb = $this->db->getQueryBuilder();
                $qb->select('storage_id')
                    ->from('group_folders')
                    ->where($qb->expr()->eq('folder_id', $qb->createNamedParameter($groupfolderId)));

                $result = $qb->executeQuery();
                $gfRow = $result->fetch();
                $result->closeCursor();

                if (!$gfRow || !isset($gfRow['storage_id'])) {
                    return $file->getId();
                }

                $storageId = (int)$gfRow['storage_id'];

                // Query filecache for the file in the groupfolder storage
                $qb2 = $this->db->getQueryBuilder();
                $qb2->select('fileid')
                    ->from('filecache')
                    ->where($qb2->expr()->eq('storage', $qb2->createNamedParameter($storageId)))
                    ->andWhere($qb2->expr()->eq('path', $qb2->createNamedParameter($relPath)))
                    ->andWhere($qb2->expr()->eq('name', $qb2->createNamedParameter($file->getName())));

                $result2 = $qb2->executeQuery();
                $row = $result2->fetch();
                $result2->closeCursor();

                if ($row && isset($row['fileid'])) {
                    return (int)$row['fileid'];
                }
            }

            // Fallback to file object ID
            return $file->getId();

        } catch (\Exception $e) {
            $this->logger->error('Failed to get file ID from database', [
                'error' => $e->getMessage(),
                'fileName' => $file->getName()
            ]);
            return $file->getId();
        }
    }

    /**
     * Get metadata for a page (simplified version using already loaded page data)
     */
    public function getPageMetadata(string $pageId): array {
        // Get page and file info
        $folder = $this->getLanguageFolder();
        $result = null;

        // Check for uniqueId pattern (page-xxxx)
        if (strpos($pageId, 'page-') === 0) {
            $result = $this->findPageByUniqueId($folder, $pageId);
        }

        // Fall back to legacy ID lookup
        if ($result === null) {
            $result = $this->findPageById($folder, $this->sanitizeId($pageId));
        }

        if (!$result) {
            throw new \Exception('Page not found: ' . $pageId);
        }

        $file = $result['file'];
        $folder = $result['folder'];

        // Get filesystem timestamps
        $mtime = $file->getMTime();
        $ctime = $file->getCreationTime();
        // Fallback: if creation time is 0 (not supported by groupfolder/storage), use mtime
        if ($ctime === 0) {
            $ctime = $mtime;
        }

        // Get page content for other metadata
        $content = $file->getContent();
        $data = json_decode($content, true);

        // Enrich with path data
        $data = $this->enrichWithPathData($data, $folder);

        // Format path to show full Nextcloud path starting with /IntraVox/
        $displayPath = isset($data['path']) ? '/IntraVox/' . $data['path'] : '';

        // Get file info for MetaVox integration
        $fileId = $file->getId();
        $size = $file->getSize();
        $internalPath = $file->getInternalPath();
        $storagePath = $file->getPath();

        // Get parent folder fileId for Files app link
        $parentFolderId = null;
        try {
            $parentFolderId = $folder->getId();
        } catch (\Exception $e) {
            // Not critical
        }

        // Get permissions from enriched data (uses Nextcloud's native permissions)
        $permissions = $data['permissions'] ?? [
            'canRead' => true,
            'canWrite' => false,
            'canCreate' => false,
            'canDelete' => false,
            'canShare' => false,
            'raw' => 1
        ];

        // Return metadata using filesystem timestamps
        $metadata = [
            'title' => $data['title'] ?? 'Untitled',
            'uniqueId' => $data['uniqueId'] ?? '',
            'language' => $data['language'] ?? $this->getUserLanguage(),
            'created' => $ctime,
            'createdFormatted' => date('Y-m-d H:i:s', $ctime),
            'createdRelative' => $this->getRelativeTime($ctime),
            'modified' => $mtime,
            'modifiedFormatted' => date('Y-m-d H:i:s', $mtime),
            'modifiedRelative' => $this->getRelativeTime($mtime),
            // Path-related data (already in page)
            'path' => $storagePath,
            'depth' => $data['depth'] ?? 0,
            'parentId' => $data['parentId'] ?? null,
            'parentPath' => $data['parentPath'] ?? null,
            'department' => $data['department'] ?? null,
            'canEdit' => $permissions['canWrite'] ?? false,
            // Additional data for MetaVox integration
            'fileId' => $fileId,
            'size' => $size,
            'parentFolderId' => $parentFolderId,
            'mountPoint' => 'IntraVox',
            // Permissions - use Nextcloud's native permissions
            'permissions' => $permissions,
        ];

        return $metadata;
    }

    /**
     * Update page metadata (title only for now, similar to Files rename)
     */
    public function updatePageMetadata(string $pageId, array $metadata): array {
        $folder = $this->getLanguageFolder();
        $result = null;

        // Check for uniqueId pattern (page-xxxx)
        if (strpos($pageId, 'page-') === 0) {
            $result = $this->findPageByUniqueId($folder, $pageId);
        }

        // Fall back to legacy ID lookup
        if ($result === null) {
            $result = $this->findPageById($folder, $this->sanitizeId($pageId));
        }

        if (!$result) {
            throw new \Exception('Page not found: ' . $pageId);
        }

        $file = $result['file'];

        // Get current content
        $content = $file->getContent();
        $data = json_decode($content, true);

        // Update only allowed fields
        $changed = false;
        if (isset($metadata['title']) && $metadata['title'] !== $data['title']) {
            $data['title'] = $this->sanitizeText($metadata['title']);
            $changed = true;
        }

        // Save if changed
        if ($changed) {
            // Create version before update using VersionsBackend
            $this->createVersionBeforeUpdate($file);
            $file->putContent(json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        }

        return $this->getPageMetadata($pageId);
    }

    /**
     * Extract groupfolder ID from path
     */
    private function extractGroupfolderId(string $path): ?int {
        if (preg_match('/\/__groupfolders\/(\d+)/', $path, $matches)) {
            return (int)$matches[1];
        }
        return null;
    }

    /**
     * Get groupfolder name from groupfolder ID
     */
    private function getGroupfolderName(?int $groupfolderId): string {
        if ($groupfolderId === null) {
            return 'IntraVox';
        }

        try {
            // Query the group_folders table for the mount_point
            $qb = $this->db->getQueryBuilder();
            $qb->select('mount_point')
                ->from('group_folders')
                ->where($qb->expr()->eq('folder_id', $qb->createNamedParameter($groupfolderId)));

            $result = $qb->executeQuery();
            $row = $result->fetch();
            $result->closeCursor();

            if ($row && isset($row['mount_point'])) {
                return $row['mount_point'];
            }
        } catch (\Exception $e) {
            // Fallback on error
        }

        return 'IntraVox';
    }

    /**
     * Get folder ID from database for Files app link
     */
    private function getFolderIdFromDatabase(string $folderPath, ?int $groupfolderId): ?int {
        if ($groupfolderId === null) {
            return null;
        }

        try {
            // Extract relative path from full path
            // Path format: /__groupfolders/4/files/en/mission
            if (preg_match('#/__groupfolders/\d+/(.+)$#', $folderPath, $matches)) {
                $relPath = $matches[1];

                // Find the storage ID for this groupfolder
                $qb = $this->db->getQueryBuilder();
                $qb->select('storage_id')
                    ->from('group_folders')
                    ->where($qb->expr()->eq('folder_id', $qb->createNamedParameter($groupfolderId)));

                $result = $qb->executeQuery();
                $gfRow = $result->fetch();
                $result->closeCursor();

                if (!$gfRow || !isset($gfRow['storage_id'])) {
                    return null;
                }

                $storageId = (int)$gfRow['storage_id'];

                // Query filecache for the folder in the groupfolder storage
                $qb2 = $this->db->getQueryBuilder();
                $qb2->select('fileid')
                    ->from('filecache')
                    ->where($qb2->expr()->eq('storage', $qb2->createNamedParameter($storageId)))
                    ->andWhere($qb2->expr()->eq('path', $qb2->createNamedParameter($relPath)));

                $result2 = $qb2->executeQuery();
                $row = $result2->fetch();
                $result2->closeCursor();

                if ($row && isset($row['fileid'])) {
                    return (int)$row['fileid'];
                }
            }

            return null;

        } catch (\Exception $e) {
            $this->logger->error('Failed to get folder ID from database', [
                'error' => $e->getMessage(),
                'folderPath' => $folderPath
            ]);
            return null;
        }
    }

    /**
     * Format bytes to human readable format
     */
    private function formatBytes(int $bytes): string {
        if ($bytes < 1024) {
            return $bytes . ' B';
        } elseif ($bytes < 1048576) {
            return round($bytes / 1024, 2) . ' KB';
        } elseif ($bytes < 1073741824) {
            return round($bytes / 1048576, 2) . ' MB';
        } else {
            return round($bytes / 1073741824, 2) . ' GB';
        }
    }

    /**
     * Check if a page is visible in the Nextcloud file cache
     * This is useful to determine if a groupfolder page has been indexed
     *
     * @param string $pageId The page ID to check
     * @return array Status information about the page's visibility
     */
    public function checkPageCacheStatus(string $pageId): array {
        try {
            $folder = $this->getLanguageFolder();
            $lang = $this->getUserLanguage();

            // For home page, check the JSON file directly
            if ($pageId === 'home') {
                try {
                    $file = $folder->get('home.json');
                    $storage = $file->getStorage();
                    $cache = $storage->getCache();

                    // Try to get cache entry using the storage's cache directly
                    $cacheEntry = $cache->get($file->getInternalPath());

                    return [
                        'visible' => $cacheEntry !== false,
                        'inCache' => $cacheEntry !== false,
                        'fileId' => $cacheEntry !== false ? $cacheEntry->getId() : null,
                        'path' => $file->getPath(),
                        'message' => $cacheEntry !== false ? 'Page is visible in Files app' : 'Page created but waiting for indexing'
                    ];
                } catch (NotFoundException $e) {
                    return [
                        'visible' => false,
                        'inCache' => false,
                        'fileId' => null,
                        'message' => 'Home page file not found'
                    ];
                }
            }

            // For regular pages, check if the page folder exists in cache
            try {
                $pageFolder = $folder->get($pageId);
                $storage = $pageFolder->getStorage();
                $cache = $storage->getCache();

                // Try to get cache entry using the storage's cache directly
                $cacheEntry = $cache->get($pageFolder->getInternalPath());

                if ($cacheEntry !== false && $cacheEntry instanceof ICacheEntry) {
                    return [
                        'visible' => true,
                        'inCache' => true,
                        'folderId' => $cacheEntry->getId(),
                        'path' => $pageFolder->getPath(),
                        'message' => 'Page is visible in Files app'
                    ];
                } else {
                    // Folder exists on disk but not in cache
                    return [
                        'visible' => false,
                        'inCache' => false,
                        'folderId' => null,
                        'message' => 'Page created but waiting for Nextcloud to index it. This may take 5-15 minutes.'
                    ];
                }
            } catch (NotFoundException $e) {
                return [
                    'visible' => false,
                    'inCache' => false,
                    'folderId' => null,
                    'message' => 'Page folder not found'
                ];
            }
        } catch (\Exception $e) {
            $this->logger->error('Failed to check page cache status', [
                'error' => $e->getMessage(),
                'pageId' => $pageId
            ]);

            return [
                'visible' => false,
                'inCache' => false,
                'error' => $e->getMessage(),
                'message' => 'Unable to check cache status'
            ];
        }
    }

    /**
     * Update version label
     * Uses IVersionManager with backend access for label updates.
     */
    public function updateVersionLabel(string $pageId, int $timestamp, ?string $label): void {
        // Verify page exists
        $result = $this->findPageById($this->getLanguageFolder(), $pageId);

        if (!$result) {
            throw new \Exception('Page not found: ' . $pageId);
        }

        $file = $result['file'];
        $user = $this->userSession->getUser();

        if (!$user) {
            throw new \Exception('No user in session');
        }

        if (!$this->versionManager) {
            throw new \Exception('Version manager not available');
        }

        // Use IVersionManager - works for all storage types including GroupFolders
        $versions = $this->versionManager->getVersionsForFile($user, $file);

        foreach ($versions as $version) {
            if ($version->getTimestamp() === $timestamp) {
                // Get the backend for this storage to access setVersionLabel
                $backend = $this->versionManager->getBackendForStorage($file->getStorage());
                if (method_exists($backend, 'setVersionLabel')) {
                    $backend->setVersionLabel($version, $label ?? '');
                    return;
                }
                throw new \Exception('Version labels not supported by this storage backend');
            }
        }

        throw new \Exception('Version not found');
    }

    /**
     * Get version content for preview
     * Uses IVersionManager for reliable version content retrieval across all storage types.
     */
    public function getVersionContent(string $pageId, int $timestamp): array {
        $folder = $this->getLanguageFolder();
        $result = null;

        // Check for uniqueId pattern (page-xxxx) like getPage() does
        if (strpos($pageId, 'page-') === 0) {
            $result = $this->findPageByUniqueId($folder, $pageId);
        }

        // Fall back to legacy ID lookup
        if ($result === null) {
            $result = $this->findPageById($folder, $this->sanitizeId($pageId));
        }

        if (!$result) {
            throw new \Exception('Page not found: ' . $pageId);
        }

        $file = $result['file'];
        $user = $this->userSession->getUser();

        if (!$user) {
            throw new \Exception('No user in session');
        }

        if (!$this->versionManager) {
            throw new \Exception('Version manager not available');
        }

        // Use IVersionManager - works for all storage types including GroupFolders
        $versions = $this->versionManager->getVersionsForFile($user, $file);

        foreach ($versions as $version) {
            if ($version->getTimestamp() === $timestamp) {
                // Read version content via IVersionManager (returns a stream resource)
                $stream = $this->versionManager->read($version);

                // Convert stream resource to string
                $content = stream_get_contents($stream);
                if (is_resource($stream)) {
                    fclose($stream);
                }

                return [
                    'title' => 'Version from ' . date('Y-m-d H:i:s', $timestamp),
                    'content' => $content,
                    'rawContent' => $content
                ];
            }
        }

        throw new \Exception('Version not found');
    }

    /**
     * Get current page content for comparison
     */
    public function getCurrentPageContent(string $pageId): array {
        $result = $this->findPageById($this->getLanguageFolder(), $pageId);

        if (!$result) {
            throw new \Exception('Page not found: ' . $pageId);
        }

        $file = $result['file'];
        $content = $file->getContent();

        return [
            'title' => $result['page']['name'] ?? 'Untitled',
            'content' => $content,
            'rawContent' => $content
        ];
    }

    /**
     * Get the full page tree structure for the current language
     * Returns a hierarchical tree of all pages the user has access to
     *
     * OPTIMIZED: Uses static cache with TTL to avoid repeated filesystem traversals
     *
     * @param string|null $currentPageId Optional: uniqueId of the current page to highlight
     * @return array Tree structure with pages and their children
     */
    public function getPageTree(?string $currentPageId = null, ?string $language = null): array {
        // Use provided language or fall back to user's language
        $lang = $language ?? $this->getUserLanguage();

        // Build cache key based on user and language
        $cacheKey = $this->userId . '_' . $lang;
        $now = time();

        // Check if we have a valid cached tree (without currentPageId marking)
        if (isset(self::$pageTreeCache[$cacheKey])) {
            $cached = self::$pageTreeCache[$cacheKey];
            if (($now - $cached['time']) < self::PAGE_TREE_CACHE_TTL) {
                // Return cached tree with updated currentPageId marking
                return $this->markCurrentPageInTree($cached['tree'], $currentPageId);
            }
        }

        // Build fresh tree for specified language
        $folder = $this->getLanguageFolderByCode($lang);
        $tree = [];

        // Check for home.json in root
        try {
            $homeFile = $folder->get('home.json');
            $content = $homeFile->getContent();
            $data = json_decode($content, true);

            if ($data && isset($data['uniqueId'], $data['title'])) {
                // Get permissions for the language folder
                $folderPerms = $this->getCachedPermissions($folder);
                $tree[] = [
                    'uniqueId' => $data['uniqueId'],
                    'title' => $data['title'],
                    'path' => $lang,
                    'language' => $lang,
                    'isCurrent' => false, // Will be set by markCurrentPageInTree
                    'children' => [],
                    'permissions' => [
                        'canRead' => ($folderPerms & 1) !== 0,
                        'canWrite' => ($folderPerms & 2) !== 0,
                        'canCreate' => ($folderPerms & 4) !== 0,
                        'canDelete' => ($folderPerms & 8) !== 0,
                        'canShare' => ($folderPerms & 16) !== 0,
                        'raw' => $folderPerms
                    ]
                ];
            }
        } catch (NotFoundException $e) {
            // No home page yet
        }

        // Recursively build tree from subfolders
        $this->buildPageTree($folder, $tree, null, $lang); // Pass null, marking done separately

        // Store in cache
        self::$pageTreeCache[$cacheKey] = [
            'tree' => $tree,
            'time' => $now
        ];

        // Return with current page marked
        return $this->markCurrentPageInTree($tree, $currentPageId);
    }

    /**
     * Mark the current page in a tree structure
     * Creates a deep copy to avoid modifying cached data
     */
    private function markCurrentPageInTree(array $tree, ?string $currentPageId): array {
        if ($currentPageId === null) {
            return $tree;
        }

        $result = [];
        foreach ($tree as $node) {
            $newNode = $node;
            $newNode['isCurrent'] = ($node['uniqueId'] === $currentPageId);
            if (!empty($node['children'])) {
                $newNode['children'] = $this->markCurrentPageInTree($node['children'], $currentPageId);
            }
            $result[] = $newNode;
        }
        return $result;
    }

    /**
     * Recursively build the page tree from folder structure
     */
    private function buildPageTree($folder, array &$tree, ?string $currentPageId, ?string $language = null): void {
        foreach ($this->getCachedDirectoryListing($folder) as $item) {
            if ($item->getType() !== \OCP\Files\FileInfo::TYPE_FOLDER) {
                continue;
            }

            $folderName = $item->getName();

            // Skip special folders
            if (in_array($folderName, ['images', 'files', '.nomedia'])) {
                continue;
            }

            // Skip folders starting with emoji (images folders)
            if (preg_match('/^[\x{1F300}-\x{1F9FF}]/u', $folderName)) {
                continue;
            }

            // Look for {foldername}.json inside the folder
            try {
                $jsonFile = $item->get($folderName . '.json');

                // Check if file is readable and user has access
                if (!$jsonFile->isReadable()) {
                    continue;
                }

                // Use cached file content to avoid repeated reads
                $content = $jsonFile instanceof \OCP\Files\File
                    ? $this->getCachedFileContent($jsonFile)
                    : @$jsonFile->getContent();

                if ($content === false || $content === null) {
                    continue;
                }

                $data = json_decode($content, true);

                if ($data && isset($data['uniqueId'], $data['title'])) {
                    // Get folder permissions (respects ACLs)
                    $folderPerms = $this->getCachedPermissions($item);

                    // Skip if user can't read this folder
                    if (($folderPerms & 1) === 0) {
                        continue;
                    }

                    $pageNode = [
                        'uniqueId' => $data['uniqueId'],
                        'title' => $data['title'],
                        'path' => $this->getRelativePathFromRoot($item),
                        'language' => $language ?? $this->getUserLanguage(),
                        'isCurrent' => ($currentPageId === $data['uniqueId']),
                        'children' => [],
                        'permissions' => [
                            'canRead' => ($folderPerms & 1) !== 0,
                            'canWrite' => ($folderPerms & 2) !== 0,
                            'canCreate' => ($folderPerms & 4) !== 0,
                            'canDelete' => ($folderPerms & 8) !== 0,
                            'canShare' => ($folderPerms & 16) !== 0,
                            'raw' => $folderPerms
                        ]
                    ];

                    // Recursively get children
                    $this->buildPageTree($item, $pageNode['children'], $currentPageId, $language);

                    $tree[] = $pageNode;
                }
            } catch (\Exception $e) {
                // This folder doesn't contain a valid page or can't be read, continue
            } catch (\Throwable $e) {
                // Catch any other errors
                continue;
            }
        }
    }

    /**
     * Search pages by query string
     * Searches in page titles and text widget content
     * OPTIMIZED: Loads all content in a single filesystem traversal
     */
    public function searchPages(string $query): array {
        $results = [];
        $query = mb_strtolower($query);

        // Get all pages with full content in a single traversal
        $pagesWithContent = $this->listPagesWithContent();

        foreach ($pagesWithContent as $pageData) {
            $matches = [];
            $score = 0;

            // Skip pages without uniqueId
            if (!isset($pageData['uniqueId']) || empty($pageData['uniqueId'])) {
                continue;
            }

            // Search in title (higher weight)
            if (isset($pageData['title']) && mb_stripos($pageData['title'], $query) !== false) {
                $score += 10;
                $matches[] = [
                    'type' => 'title',
                    'text' => $pageData['title']
                ];
            }

            // Search in uniqueId (medium weight)
            if (mb_stripos($pageData['uniqueId'], $query) !== false) {
                $score += 5;
            }

            // Search in content - layout is already loaded
            // Collect all widgets from all layout areas
            $allWidgets = [];

            // Main rows
            if (isset($pageData['layout']['rows'])) {
                foreach ($pageData['layout']['rows'] as $row) {
                    if (isset($row['widgets'])) {
                        $allWidgets = array_merge($allWidgets, $row['widgets']);
                    }
                }
            }

            // Header row
            if (isset($pageData['layout']['headerRow']['widgets'])) {
                $allWidgets = array_merge($allWidgets, $pageData['layout']['headerRow']['widgets']);
            }

            // Side columns
            if (isset($pageData['layout']['sideColumns']['left']['widgets'])) {
                $allWidgets = array_merge($allWidgets, $pageData['layout']['sideColumns']['left']['widgets']);
            }
            if (isset($pageData['layout']['sideColumns']['right']['widgets'])) {
                $allWidgets = array_merge($allWidgets, $pageData['layout']['sideColumns']['right']['widgets']);
            }

            // Search through all collected widgets
            foreach ($allWidgets as $widget) {
                $widgetMatches = $this->searchWidget($widget, $query);
                foreach ($widgetMatches as $match) {
                    $score += $match['score'];
                    $matches[] = [
                        'type' => $match['type'],
                        'text' => $match['text']
                    ];
                }
            }

            // If we have matches, add to results
            if ($score > 0) {
                $results[] = [
                    'uniqueId' => $pageData['uniqueId'] ?? null,
                    'title' => $pageData['title'] ?? 'Untitled',
                    'path' => $pageData['path'] ?? '',
                    'score' => $score,
                    'matches' => array_slice($matches, 0, 3), // Limit to 3 matches per page
                    'matchCount' => count($matches)
                ];
            }
        }

        // Sort by score (highest first)
        usort($results, function($a, $b) {
            return $b['score'] - $a['score'];
        });

        // Limit to top 20 results
        return array_slice($results, 0, 20);
    }

    /**
     * Extract a snippet of text around a search query match
     */
    private function extractSnippet(string $text, string $query, int $contextLength = 100): string {
        // Remove markdown formatting for cleaner snippets
        $text = strip_tags($text);
        $text = preg_replace('/[*_~`#]/', '', $text);

        $pos = mb_stripos($text, $query);
        if ($pos === false) {
            // Fallback: return beginning of text
            return mb_substr($text, 0, $contextLength) . '...';
        }

        // Calculate start position (with some context before)
        $start = max(0, $pos - (int)($contextLength / 2));

        // Extract snippet
        $snippet = mb_substr($text, $start, $contextLength);

        // Add ellipsis if needed
        $prefix = $start > 0 ? '...' : '';
        $suffix = (mb_strlen($text) > $start + $contextLength) ? '...' : '';

        return $prefix . trim($snippet) . $suffix;
    }

    /**
     * Search a single widget for matches
     *
     * @param array $widget Widget data
     * @param string $query Search query (lowercase)
     * @return array Array of matches with type, text, and score
     */
    private function searchWidget(array $widget, string $query): array {
        $matches = [];
        $type = $widget['type'] ?? '';

        // Text widgets
        if ($type === 'text' && isset($widget['content'])) {
            if (mb_stripos($widget['content'], $query) !== false) {
                $matches[] = [
                    'type' => 'content',
                    'text' => $this->extractSnippet($widget['content'], $query),
                    'score' => 3
                ];
            }
        }

        // Heading widgets
        if ($type === 'heading' && isset($widget['content'])) {
            if (mb_stripos($widget['content'], $query) !== false) {
                $matches[] = [
                    'type' => 'heading',
                    'text' => $widget['content'],
                    'score' => 5
                ];
            }
        }

        // Image widgets (alt text)
        if ($type === 'image' && !empty($widget['alt'])) {
            if (mb_stripos($widget['alt'], $query) !== false) {
                $matches[] = [
                    'type' => 'image',
                    'text' => $widget['alt'],
                    'score' => 2
                ];
            }
        }

        // Links widgets (title and text)
        if ($type === 'links' && !empty($widget['items'])) {
            foreach ($widget['items'] as $link) {
                $linkTitle = $link['title'] ?? '';
                $linkText = strip_tags($link['text'] ?? '');
                $linkSearchText = $linkTitle . ' ' . $linkText;
                if (mb_stripos($linkSearchText, $query) !== false) {
                    $displayText = $linkTitle;
                    if (!empty($linkText)) {
                        $displayText .= ' - ' . $this->extractSnippet($linkText, $query, 60);
                    }
                    $matches[] = [
                        'type' => 'link',
                        'text' => $displayText,
                        'score' => 3
                    ];
                }
            }
        }

        // File widgets (filename)
        if ($type === 'file' && !empty($widget['name'])) {
            if (mb_stripos($widget['name'], $query) !== false) {
                $matches[] = [
                    'type' => 'file',
                    'text' => $widget['name'],
                    'score' => 2
                ];
            }
        }

        // Video widgets (title)
        if ($type === 'video' && !empty($widget['title'])) {
            if (mb_stripos($widget['title'], $query) !== false) {
                $matches[] = [
                    'type' => 'video',
                    'text' => $widget['title'],
                    'score' => 2
                ];
            }
        }

        return $matches;
    }

    /**
     * Sanitize filename for safe storage
     * - Validates extension against whitelist
     * - Remove special characters
     * - Convert spaces to underscores
     * - Check for Windows reserved names
     * - Limit to filesystem-safe length
     *
     * @param string $filename Original filename
     * @param bool $validateExtension Whether to validate extension (default true)
     * @return string Sanitized filename
     * @throws \InvalidArgumentException If extension is not allowed
     */
    public function sanitizeFilename(string $filename, bool $validateExtension = true): string {
        // Get extension
        $extension = '';
        if (($dotPos = strrpos($filename, '.')) !== false) {
            $ext = strtolower(substr($filename, $dotPos + 1));

            // Whitelist check for allowed extensions
            if ($validateExtension && !in_array($ext, self::ALLOWED_EXTENSIONS)) {
                throw new \InvalidArgumentException(
                    "File extension not allowed: $ext. Allowed: " .
                    implode(', ', self::ALLOWED_EXTENSIONS)
                );
            }

            $extension = '.' . $ext;
            $filename = substr($filename, 0, $dotPos);
        }

        // Remove special characters, keep alphanumeric, dash, underscore
        $filename = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $filename);

        // Replace multiple underscores with single underscore
        $filename = preg_replace('/_+/', '_', $filename);

        // Trim underscores from start and end
        $filename = trim($filename, '_');

        // Check for Windows reserved names (security measure)
        $reserved = ['con', 'prn', 'aux', 'nul'];
        for ($i = 1; $i <= 9; $i++) {
            $reserved[] = "com$i";
            $reserved[] = "lpt$i";
        }
        if (in_array(strtolower($filename), $reserved)) {
            $filename = 'file_' . uniqid();
        }

        // Limit to safe length (255 - extension length for filesystem compatibility)
        $maxLength = 255 - strlen($extension);
        if (strlen($filename) > $maxLength) {
            $filename = substr($filename, 0, $maxLength);
        }

        // Ensure we have a filename
        if (empty($filename)) {
            $filename = 'file_' . uniqid();
        }

        return $filename . $extension;
    }

    /**
     * Sanitize SVG file content to prevent XSS attacks
     *
     * Removes: <script>, event handlers, <foreignObject>, DOCTYPE, external refs
     *
     * @param string $svgContent Raw SVG file content
     * @return string Sanitized SVG content
     * @throws \Exception If SVG is malformed or contains disallowed content
     */
    private function sanitizeSVG(string $svgContent): string {
        try {
            $sanitizer = new Sanitizer();
            $sanitizer->removeRemoteReferences(true);

            $cleanSvg = $sanitizer->sanitize($svgContent);

            if ($cleanSvg === false || empty($cleanSvg)) {
                throw new \Exception('SVG sanitization failed - file may contain malicious content');
            }

            // Additional security: reject DOCTYPE (XXE attack vector)
            if (stripos($cleanSvg, '<!DOCTYPE') !== false) {
                throw new \Exception('SVG contains DOCTYPE declaration (not allowed)');
            }

            // Check for dangerous elements that could bypass sanitizer
            $dangerousPatterns = [
                '<!ENTITY',      // XML entities (XXE)
                '<iframe',       // Embedded frames
                '<embed',        // Embedded content
                '<object',       // Embedded objects
                '<script',       // Scripts (should be caught by sanitizer, but double-check)
                'javascript:',   // JavaScript URLs
                'data:text/html', // Data URIs with HTML
                'SYSTEM',        // External entity references
                'PUBLIC',        // Public entity references
            ];

            foreach ($dangerousPatterns as $pattern) {
                if (stripos($cleanSvg, $pattern) !== false) {
                    throw new \Exception("SVG contains prohibited content: $pattern");
                }
            }

            return $cleanSvg;
        } catch (\Exception $e) {
            $this->logger->error('SVG sanitization error: ' . $e->getMessage());
            throw new \Exception('Invalid SVG file');
        }
    }

    /**
     * Validate image file using getimagesize() to prevent polyglot attacks
     *
     * This provides additional security beyond MIME type detection by
     * actually parsing the image headers.
     *
     * @param string $tmpFile Path to temporary uploaded file
     * @param string $detectedMime MIME type detected by finfo
     * @throws \InvalidArgumentException If image is invalid or MIME type doesn't match
     */
    private function validateImageFile(string $tmpFile, string $detectedMime): void {
        $imageInfo = @getimagesize($tmpFile);

        if ($imageInfo === false) {
            throw new \InvalidArgumentException(
                'File appears to be an invalid or corrupted image'
            );
        }

        // Map PHP's IMAGETYPE constants to MIME types
        $expectedMime = match ($imageInfo[2]) {
            IMAGETYPE_JPEG => 'image/jpeg',
            IMAGETYPE_PNG => 'image/png',
            IMAGETYPE_GIF => 'image/gif',
            IMAGETYPE_WEBP => 'image/webp',
            default => null
        };

        // Verify MIME type matches what getimagesize() detected
        if ($expectedMime !== null && $expectedMime !== $detectedMime) {
            $this->logger->warning('Image MIME type mismatch', [
                'detected' => $detectedMime,
                'actual' => $expectedMime
            ]);
            throw new \InvalidArgumentException(
                'Image file appears to be corrupted or has incorrect extension'
            );
        }
    }

    /**
     * Check if media file exists in page/_media or _resources folder
     *
     * @param string $pageId Page unique ID
     * @param string $filename Filename to check
     * @param string $targetFolder 'page' or 'resources'
     * @return bool True if file exists
     */
    public function checkMediaExists(string $pageId, string $filename, string $targetFolder): bool {
        try {
            $languageFolder = $this->getLanguageFolder();
            $filename = basename($filename); // Prevent directory traversal

            if ($targetFolder === 'resources') {
                // Check in _resources folder
                try {
                    $resourcesFolder = $languageFolder->get('_resources');
                    $resourcesFolder->get($filename);
                    return true;
                } catch (NotFoundException $e) {
                    return false;
                }
            } else {
                // Check in page/_media folder
                $pageId = $this->sanitizeId($pageId);

                // Find the page
                $result = $this->findPageByUniqueId($languageFolder, $pageId);
                if ($result === null) {
                    $result = $this->findPageById($languageFolder, $pageId);
                    if ($result === null) {
                        return false;
                    }
                }

                // Get media folder
                if ($result['isHome'] ?? false) {
                    try {
                        $mediaFolder = $languageFolder->get('_media');
                    } catch (NotFoundException $e) {
                        return false;
                    }
                } else {
                    $pageFolder = $result['folder'];
                    try {
                        $mediaFolder = $pageFolder->get('_media');
                    } catch (NotFoundException $e) {
                        return false;
                    }
                }

                // Check if file exists
                try {
                    $mediaFolder->get($filename);
                    return true;
                } catch (NotFoundException $e) {
                    return false;
                }
            }
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Upload media with original filename
     *
     * @param string $pageId Page unique ID
     * @param array $file Uploaded file data
     * @param string $targetFolder 'page' or 'resources'
     * @param bool $overwrite Whether to overwrite existing file
     * @return array ['filename' => '...', 'exists' => bool]
     * @throws \Exception On upload failure or if file exists and overwrite is false
     */
    public function uploadMediaWithOriginalName(string $pageId, array $file, string $targetFolder, bool $overwrite = false): array {
        if (!isset($file['tmp_name']) || !isset($file['name'])) {
            throw new \InvalidArgumentException('Invalid file upload');
        }

        // Check if tmp_name is empty (upload failed on server)
        if (empty($file['tmp_name'])) {
            $errorCode = $file['error'] ?? -1;
            $errorMessages = [
                UPLOAD_ERR_INI_SIZE => 'File exceeds server upload limit',
                UPLOAD_ERR_FORM_SIZE => 'File exceeds form upload limit',
                UPLOAD_ERR_PARTIAL => 'File only partially uploaded',
                UPLOAD_ERR_NO_FILE => 'No file was uploaded',
                UPLOAD_ERR_NO_TMP_DIR => 'Server missing temporary folder',
                UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk',
                UPLOAD_ERR_EXTENSION => 'Upload stopped by PHP extension',
            ];
            $message = $errorMessages[$errorCode] ?? "Upload failed (error code: $errorCode)";
            throw new \InvalidArgumentException($message);
        }

        // Validate file type
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        if (!in_array($mimeType, self::ALLOWED_MEDIA_TYPES)) {
            throw new \InvalidArgumentException('Invalid file type. Allowed: JPEG, PNG, GIF, WebP, SVG, MP4, WebM, OGG');
        }

        // Validate file size
        if ($file['size'] > self::MAX_MEDIA_SIZE) {
            throw new \InvalidArgumentException('File too large. Maximum size is 50MB.');
        }

        // Additional validation for image files (prevents polyglot attacks)
        if (in_array($mimeType, ['image/jpeg', 'image/png', 'image/gif', 'image/webp'])) {
            $this->validateImageFile($file['tmp_name'], $mimeType);
        }

        // SVG files get special treatment: smaller size limit + sanitization
        if ($mimeType === 'image/svg+xml') {
            if ($file['size'] > self::MAX_SVG_SIZE) {
                throw new \InvalidArgumentException('SVG file too large. Maximum size is 1MB.');
            }
            $content = file_get_contents($file['tmp_name']);
            $content = $this->sanitizeSVG($content);
        } else {
            $content = file_get_contents($file['tmp_name']);
        }

        // Sanitize original filename
        $filename = $this->sanitizeFilename($file['name']);

        // Check if file exists
        $fileExists = $this->checkMediaExists($pageId, $filename, $targetFolder);
        if ($fileExists && !$overwrite) {
            throw new \Exception('File already exists');
        }

        $languageFolder = $this->getLanguageFolder();

        // Get target folder based on targetFolder parameter
        if ($targetFolder === 'resources') {
            // Upload to _resources folder
            try {
                $uploadFolder = $languageFolder->get('_resources');
            } catch (NotFoundException $e) {
                $uploadFolder = $languageFolder->newFolder('_resources');
            }
        } else {
            // Upload to page/_media folder
            $pageId = $this->sanitizeId($pageId);

            // Find the page
            $result = $this->findPageByUniqueId($languageFolder, $pageId);
            if ($result === null) {
                $result = $this->findPageById($languageFolder, $pageId);
                if ($result === null) {
                    throw new \Exception('Page not found');
                }
            }

            // Get media folder for this page
            if ($result['isHome'] ?? false) {
                // Home media is in root/_media/
                try {
                    $uploadFolder = $languageFolder->get('_media');
                } catch (NotFoundException $e) {
                    $uploadFolder = $languageFolder->newFolder('_media');
                }
            } else {
                $pageFolder = $result['folder'];

                // Get or create media subfolder
                try {
                    $uploadFolder = $pageFolder->get('_media');
                } catch (NotFoundException $e) {
                    $uploadFolder = $pageFolder->newFolder('_media');
                }
            }
        }

        // Upload file (content already sanitized for SVG)
        if ($fileExists && $overwrite) {
            // Overwrite existing file
            $existingFile = $uploadFolder->get($filename);
            $existingFile->putContent($content);
        } else {
            // Create new file
            $newFile = $uploadFolder->newFile($filename);
            $newFile->putContent($content);
        }

        return [
            'filename' => $filename,
            'exists' => $fileExists
        ];
    }

    /**
     * Get list of media files in a folder
     *
     * @param string $pageId Page unique ID
     * @param string $folderType 'page' or 'resources'
     * @param string $subPath Subfolder path for resources (optional)
     * @return array List of media files with metadata
     */
    public function getMediaList(string $pageId, string $folderType, string $subPath = ''): array {
        try {
            $languageFolder = $this->getLanguageFolder();
            $mediaFiles = [];

            if ($folderType === 'resources') {
                // List files in _resources folder
                try {
                    $resourcesFolder = $languageFolder->get('_resources');

                    // Navigate to subfolder if path provided
                    $targetFolder = $resourcesFolder;
                    if (!empty($subPath)) {
                        $subPath = trim($subPath, '/');
                        $targetFolder = $resourcesFolder->get($subPath);
                    }

                    $files = $targetFolder->getDirectoryListing();

                    foreach ($files as $file) {
                        $relativePath = $this->getRelativePath($file, $resourcesFolder);

                        if ($file->getType() === \OCP\Files\FileInfo::TYPE_FOLDER) {
                            // It's a folder
                            $mediaFiles[] = [
                                'type' => 'folder',
                                'name' => $file->getName(),
                                'path' => $relativePath,
                                'modified' => $file->getMTime()
                            ];
                        } else {
                            // It's a file
                            $mediaFiles[] = [
                                'type' => 'file',
                                'name' => $file->getName(),
                                'path' => $relativePath,
                                'size' => $file->getSize(),
                                'mimeType' => $file->getMimetype(),
                                'modified' => $file->getMTime()
                            ];
                        }
                    }
                } catch (NotFoundException $e) {
                    // _resources folder or subfolder doesn't exist
                    return [];
                }
            } else {
                // List files in page/_media folder
                $pageId = $this->sanitizeId($pageId);

                // Find the page
                $result = $this->findPageByUniqueId($languageFolder, $pageId);
                if ($result === null) {
                    $result = $this->findPageById($languageFolder, $pageId);
                    if ($result === null) {
                        return [];
                    }
                }

                // Get media folder
                if ($result['isHome'] ?? false) {
                    try {
                        $mediaFolder = $languageFolder->get('_media');
                    } catch (NotFoundException $e) {
                        return [];
                    }
                } else {
                    $pageFolder = $result['folder'];
                    try {
                        $mediaFolder = $pageFolder->get('_media');
                    } catch (NotFoundException $e) {
                        return [];
                    }
                }

                // List files
                $files = $mediaFolder->getDirectoryListing();
                foreach ($files as $file) {
                    if ($file->getType() === \OCP\Files\FileInfo::TYPE_FILE) {
                        $mediaFiles[] = [
                            'name' => $file->getName(),
                            'size' => $file->getSize(),
                            'mimeType' => $file->getMimetype(),
                            'modified' => $file->getMTime()
                        ];
                    }
                }
            }

            // Sort by name
            usort($mediaFiles, function($a, $b) {
                return strcmp($a['name'], $b['name']);
            });

            return $mediaFiles;

        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Get media file from _resources folder
     *
     * @param string $path File path (can include subfolders)
     * @return \OCP\Files\File File object
     * @throws NotFoundException If file not found
     */
    public function getResourcesMediaFile(string $path) {
        // Path is already sanitized by ApiController::sanitizePath()
        $languageFolder = $this->getLanguageFolder();

        try {
            $resourcesFolder = $languageFolder->get('_resources');

            // Navigate to file (supports subfolder paths)
            return $resourcesFolder->get($path);
        } catch (NotFoundException $e) {
            throw new NotFoundException('Media file not found: ' . $path);
        }
    }

    /**
     * Get relative path from resources root
     * @param \OCP\Files\Node $node File or folder node
     * @param \OCP\Files\Folder $resourcesRoot _resources folder root
     * @return string Relative path (e.g., "logos/company.png")
     */
    private function getRelativePath(\OCP\Files\Node $node, \OCP\Files\Folder $resourcesRoot): string {
        $fullPath = $node->getPath();
        $rootPath = $resourcesRoot->getPath();
        return ltrim(substr($fullPath, strlen($rootPath)), '/');
    }

    /**
     * Get news pages for the News widget
     *
     * @param string $sourcePath Source folder path (relative to language folder)
     * @param array $filters MetaVox filters to apply
     * @param string $filterOperator 'AND' or 'OR' for combining filters
     * @param int $limit Maximum number of results
     * @param string $sortBy Field to sort by ('modified' or 'title')
     * @param string $sortOrder Sort direction ('asc' or 'desc')
     * @return array News items with excerpts and images
     */
    public function getNewsPages(
        string $sourcePath = '',
        array $filters = [],
        string $filterOperator = 'AND',
        int $limit = 5,
        string $sortBy = 'modified',
        string $sortOrder = 'desc',
        ?string $sourcePageId = null,
        bool $filterPublished = false
    ): array {
        $folder = $this->getLanguageFolder();
        $pages = [];
        $language = $this->getUserLanguage();

        // If sourcePageId is provided, find that page and use its folder as source
        // Also include the selected page itself in the results
        $sourcePageData = null;
        if (!empty($sourcePageId)) {
            try {
                $result = $this->findPageByUniqueId($folder, $sourcePageId);
                if ($result && isset($result['folder'])) {
                    $folder = $result['folder'];
                    // Store the source page data to include it in results
                    if (isset($result['file'])) {
                        $sourcePageData = $result;
                    }
                } else {
                    $this->logger->warning('News widget: Source page not found', ['sourcePageId' => $sourcePageId]);
                    return ['items' => [], 'total' => 0, 'metavoxAvailable' => $this->isMetaVoxAvailable()];
                }
            } catch (\Exception $e) {
                $this->logger->warning('News widget: Error finding source page', ['sourcePageId' => $sourcePageId, 'error' => $e->getMessage()]);
                return ['items' => [], 'total' => 0, 'metavoxAvailable' => $this->isMetaVoxAvailable()];
            }
        }
        // Legacy: If sourcePath is provided (but no sourcePageId), navigate to that folder
        elseif (!empty($sourcePath)) {
            $sourcePath = trim($sourcePath, '/');
            try {
                $folder = $folder->get($sourcePath);
            } catch (NotFoundException $e) {
                $this->logger->warning('News widget: Source folder not found', ['path' => $sourcePath]);
                return ['items' => [], 'total' => 0, 'metavoxAvailable' => $this->isMetaVoxAvailable()];
            }
        }

        // Recursively collect pages from the source folder
        $this->findNewsPagesInFolder($folder, $pages, $language);

        // Add the selected source page itself to the results (if sourcePageId was provided)
        if ($sourcePageData !== null && isset($sourcePageData['file'])) {
            try {
                $content = $sourcePageData['file']->getContent();
                $data = json_decode($content, true);

                if ($data && isset($data['uniqueId'], $data['title'])) {
                    // Get folder permissions
                    $folderPerms = $this->getCachedPermissions($sourcePageData['folder']);

                    // Only add if user can read
                    if (($folderPerms & 1) !== 0) {
                        $excerpt = $this->getPageExcerpt($data, 150);
                        $imageData = $this->getPageFirstImage($data);
                        $imagePath = null;
                        if ($imageData) {
                            if (($imageData['mediaFolder'] ?? 'page') === 'resources') {
                                $imagePath = '/apps/intravox/api/resources/media/' . $imageData['src'];
                            } else {
                                $imagePath = '/apps/intravox/api/pages/' . $data['uniqueId'] . '/media/' . $imageData['src'];
                            }
                        }

                        $newsItem = [
                            'uniqueId' => $data['uniqueId'],
                            'title' => $data['title'],
                            'excerpt' => $excerpt,
                            'image' => $imageData ? $imageData['src'] : null,
                            'imagePath' => $imagePath,
                            'modified' => $sourcePageData['file']->getMTime(),
                            'modifiedFormatted' => $this->formatDateLocalized($sourcePageData['file']->getMTime(), $language),
                            'path' => $sourcePageData['folder']->getPath(),
                        ];

                        // Add to beginning of pages array (it's the "parent" page)
                        array_unshift($pages, $newsItem);
                    }
                }
            } catch (\Exception $e) {
                $this->logger->debug('News widget: Could not add source page to results', ['error' => $e->getMessage()]);
            }
        }

        // Apply MetaVox filters if any and if MetaVox is available
        if (!empty($filters) && $this->isMetaVoxAvailable()) {
            $pages = $this->applyMetaVoxFilters($pages, $filters, $filterOperator);
        }

        // Apply publication date filter if enabled and MetaVox is available
        if ($filterPublished && $this->isMetaVoxAvailable()) {
            $pages = $this->applyPublicationDateFilter($pages);
        }

        $total = count($pages);

        // Sort pages
        usort($pages, function($a, $b) use ($sortBy, $sortOrder) {
            if ($sortBy === 'title') {
                $cmp = strcasecmp($a['title'] ?? '', $b['title'] ?? '');
            } else {
                // Default: sort by modified
                $cmp = ($a['modified'] ?? 0) - ($b['modified'] ?? 0);
            }
            return $sortOrder === 'asc' ? $cmp : -$cmp;
        });

        // Limit results
        $pages = array_slice($pages, 0, $limit);

        return [
            'items' => $pages,
            'total' => $total,
            'metavoxAvailable' => $this->isMetaVoxAvailable()
        ];
    }

    /**
     * Recursively find news pages in a folder
     */
    private function findNewsPagesInFolder($folder, array &$pages, string $language): void {
        foreach ($this->getCachedDirectoryListing($folder) as $item) {
            if ($item->getType() !== \OCP\Files\FileInfo::TYPE_FOLDER) {
                continue;
            }

            $folderName = $item->getName();

            // Skip special folders
            if (in_array($folderName, ['_media', '_resources', 'images', 'files'])) {
                continue;
            }

            // Look for {foldername}.json inside the folder
            try {
                $jsonFile = $item->get($folderName . '.json');

                if (!$jsonFile->isReadable()) {
                    continue;
                }

                $content = $jsonFile instanceof \OCP\Files\File
                    ? $this->getCachedFileContent($jsonFile)
                    : @$jsonFile->getContent();

                if ($content === false || $content === null) {
                    continue;
                }

                $data = json_decode($content, true);

                if ($data && isset($data['uniqueId'], $data['title'])) {
                    // Get folder permissions
                    $folderPerms = $this->getCachedPermissions($item);

                    // Skip if user can't read
                    if (($folderPerms & 1) === 0) {
                        continue;
                    }

                    // Extract excerpt from first text widget
                    $excerpt = $this->getPageExcerpt($data, 150);

                    // Find first image
                    $imageData = $this->getPageFirstImage($data);
                    $imagePath = null;
                    if ($imageData) {
                        if (($imageData['mediaFolder'] ?? 'page') === 'resources') {
                            $imagePath = '/apps/intravox/api/resources/media/' . $imageData['src'];
                        } else {
                            $imagePath = '/apps/intravox/api/pages/' . $data['uniqueId'] . '/media/' . $imageData['src'];
                        }
                    }

                    // Build relative path
                    $relativePath = $this->getRelativePathFromRoot($item);

                    // Get file modification time
                    $modified = $jsonFile->getMTime();

                    // Format modified date in user's locale
                    $modifiedFormatted = $this->formatDateLocalized($modified, $language);

                    $pages[] = [
                        'uniqueId' => $data['uniqueId'],
                        'title' => $data['title'],
                        'excerpt' => $excerpt,
                        'image' => $imageData ? $imageData['src'] : null,
                        'imagePath' => $imagePath,
                        'modified' => $modified,
                        'modifiedFormatted' => $modifiedFormatted,
                        'path' => $relativePath,
                        'fileId' => $jsonFile->getId(),
                        'permissions' => [
                            'canRead' => ($folderPerms & 1) !== 0,
                            'canWrite' => ($folderPerms & 2) !== 0,
                            'raw' => $folderPerms
                        ]
                    ];
                }
            } catch (\Exception $e) {
                // This folder doesn't contain a valid page
            } catch (\Throwable $e) {
                continue;
            }

            // Recursively search subfolders
            $this->findNewsPagesInFolder($item, $pages, $language);
        }
    }

    /**
     * Extract an excerpt from page content (first text widget)
     */
    public function getPageExcerpt(array $pageData, int $length = 150): string {
        if (!isset($pageData['layout']['rows']) || !is_array($pageData['layout']['rows'])) {
            return '';
        }

        // Search through all rows for text widgets
        foreach ($pageData['layout']['rows'] as $row) {
            if (!isset($row['widgets']) || !is_array($row['widgets'])) {
                continue;
            }

            foreach ($row['widgets'] as $widget) {
                if (($widget['type'] ?? '') === 'text' && !empty($widget['content'])) {
                    // Strip HTML tags and get plain text
                    $text = strip_tags($widget['content']);
                    // Strip markdown syntax for clean excerpts
                    $text = $this->stripMarkdown($text);
                    // Remove excessive whitespace
                    $text = preg_replace('/\s+/', ' ', $text);
                    $text = trim($text);

                    if (!empty($text)) {
                        // Truncate to desired length
                        if (mb_strlen($text) > $length) {
                            $text = mb_substr($text, 0, $length);
                            // Cut at last word boundary
                            $lastSpace = mb_strrpos($text, ' ');
                            if ($lastSpace !== false && $lastSpace > $length * 0.7) {
                                $text = mb_substr($text, 0, $lastSpace);
                            }
                            $text .= '...';
                        }
                        return $text;
                    }
                }
            }
        }

        return '';
    }

    /**
     * Strip markdown syntax from text for clean excerpts
     */
    private function stripMarkdown(string $text): string {
        // Bold: **text** or __text__
        $text = preg_replace('/\*\*(.+?)\*\*/', '$1', $text);
        $text = preg_replace('/__(.+?)__/', '$1', $text);

        // Italic: *text* or _text_
        $text = preg_replace('/\*(.+?)\*/', '$1', $text);
        $text = preg_replace('/_(.+?)_/', '$1', $text);

        // Strikethrough: ~~text~~
        $text = preg_replace('/~~(.+?)~~/', '$1', $text);

        // Links: [text](url)
        $text = preg_replace('/\[([^\]]+)\]\([^)]+\)/', '$1', $text);

        // Images: ![alt](url)
        $text = preg_replace('/!\[([^\]]*)\]\([^)]+\)/', '$1', $text);

        // Inline code: `code`
        $text = preg_replace('/`([^`]+)`/', '$1', $text);

        // Headers: # ## ### etc
        $text = preg_replace('/^#{1,6}\s+/m', '', $text);

        // Blockquotes: > text
        $text = preg_replace('/^>\s+/m', '', $text);

        // Unordered list markers: - or * or +
        $text = preg_replace('/^[\-\*\+]\s+/m', '', $text);

        // Ordered list markers: 1. 2. etc
        $text = preg_replace('/^\d+\.\s+/m', '', $text);

        // Horizontal rules: --- or *** or ___
        $text = preg_replace('/^[\-\*_]{3,}\s*$/m', '', $text);

        return $text;
    }

    /**
     * Find the first image in a page's layout
     * Returns array with 'src' and 'mediaFolder' or null if no image found
     */
    public function getPageFirstImage(array $pageData): ?array {
        // Check header row first
        if (isset($pageData['layout']['headerRow']['widgets']) && is_array($pageData['layout']['headerRow']['widgets'])) {
            foreach ($pageData['layout']['headerRow']['widgets'] as $widget) {
                if (($widget['type'] ?? '') === 'image' && !empty($widget['src'])) {
                    return [
                        'src' => $widget['src'],
                        'mediaFolder' => $widget['mediaFolder'] ?? 'page'
                    ];
                }
            }
        }

        // Then check main rows
        if (isset($pageData['layout']['rows']) && is_array($pageData['layout']['rows'])) {
            foreach ($pageData['layout']['rows'] as $row) {
                if (!isset($row['widgets']) || !is_array($row['widgets'])) {
                    continue;
                }

                foreach ($row['widgets'] as $widget) {
                    if (($widget['type'] ?? '') === 'image' && !empty($widget['src'])) {
                        return [
                            'src' => $widget['src'],
                            'mediaFolder' => $widget['mediaFolder'] ?? 'page'
                        ];
                    }
                }
            }
        }

        return null;
    }

    /**
     * Check if MetaVox app is available
     */
    private function isMetaVoxAvailable(): bool {
        try {
            $appManager = \OC::$server->getAppManager();
            return $appManager->isInstalled('metavox') && $appManager->isEnabledForUser('metavox');
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Apply MetaVox filters to pages
     *
     * @param array $pages Pages to filter
     * @param array $filters Filter definitions
     * @param string $operator 'AND' or 'OR'
     * @return array Filtered pages
     */
    private function applyMetaVoxFilters(array $pages, array $filters, string $operator = 'AND'): array {
        if (empty($filters) || !$this->isMetaVoxAvailable()) {
            return $pages;
        }

        // Get file IDs from pages
        $fileIds = array_filter(array_column($pages, 'fileId'));
        if (empty($fileIds)) {
            return $pages;
        }

        // Fetch MetaVox data for all file IDs
        $metaVoxData = $this->getMetaVoxDataForFiles($fileIds);

        // Filter pages based on MetaVox values
        return array_filter($pages, function($page) use ($filters, $operator, $metaVoxData) {
            $fileId = $page['fileId'] ?? null;
            if (!$fileId) {
                return $operator === 'OR'; // No fileId = no match for AND, possible match for OR
            }

            $meta = $metaVoxData[$fileId] ?? [];
            $results = [];

            foreach ($filters as $filter) {
                $fieldName = $filter['fieldName'] ?? '';
                $filterOperator = $filter['operator'] ?? 'equals';
                $filterValue = $filter['value'] ?? '';
                $filterValues = $filter['values'] ?? [];
                $actualValue = $meta[$fieldName] ?? null;

                // Use values array for operators that work with multiple values
                if (in_array($filterOperator, ['in', 'contains', 'contains_all']) && !empty($filterValues)) {
                    $filterValue = $filterValues;
                }

                $results[] = $this->matchesFilter($actualValue, $filterOperator, $filterValue);
            }

            if (empty($results)) {
                return true;
            }

            return $operator === 'AND'
                ? !in_array(false, $results, true)
                : in_array(true, $results, true);
        });
    }

    /**
     * Check if a value matches a filter
     */
    private function matchesFilter($value, string $operator, $filterValue): bool {
        switch ($operator) {
            // Text/general operators
            case 'equals':
                return $value === $filterValue;
            case 'contains':
                if (is_array($value)) {
                    // For multiselect: check if filterValue is in the array
                    return in_array($filterValue, $value);
                }
                return is_string($value) && str_contains($value, $filterValue);
            case 'not_contains':
                if (is_array($value)) {
                    return !in_array($filterValue, $value);
                }
                return is_string($value) && !str_contains($value, $filterValue);
            case 'in':
                $allowedValues = is_array($filterValue) ? $filterValue : [$filterValue];
                return in_array($value, $allowedValues);
            case 'not_empty':
                return !empty($value);
            case 'empty':
                return empty($value);

            // Date operators
            case 'before':
                $dateValue = $this->parseDate($value);
                $dateFilter = $this->parseDate($filterValue);
                if (!$dateValue || !$dateFilter) return false;
                return $dateValue < $dateFilter;
            case 'after':
                $dateValue = $this->parseDate($value);
                $dateFilter = $this->parseDate($filterValue);
                if (!$dateValue || !$dateFilter) return false;
                return $dateValue > $dateFilter;

            // Number operators
            case 'greater_than':
                if (!is_numeric($value) || !is_numeric($filterValue)) return false;
                return (float)$value > (float)$filterValue;
            case 'less_than':
                if (!is_numeric($value) || !is_numeric($filterValue)) return false;
                return (float)$value < (float)$filterValue;
            case 'greater_or_equal':
                if (!is_numeric($value) || !is_numeric($filterValue)) return false;
                return (float)$value >= (float)$filterValue;
            case 'less_or_equal':
                if (!is_numeric($value) || !is_numeric($filterValue)) return false;
                return (float)$value <= (float)$filterValue;

            // Checkbox operators
            case 'is_true':
                return $value === true || $value === 'true' || $value === '1' || $value === 1;
            case 'is_false':
                return $value === false || $value === 'false' || $value === '0' || $value === 0 || $value === '';

            // Multiselect operators
            case 'contains_all':
                if (!is_array($value)) return false;
                $requiredValues = is_array($filterValue) ? $filterValue : [$filterValue];
                foreach ($requiredValues as $required) {
                    if (!in_array($required, $value)) return false;
                }
                return true;

            default:
                return false;
        }
    }

    /**
     * Filter pages based on publication dates from MetaVox fields
     *
     * Logic: (Publish date is empty OR Publish date <= today)
     *    AND (Expiration date is empty OR Expiration date > today)
     *
     * @param array $pages Pages to filter
     * @return array Filtered pages that are currently published
     */
    private function applyPublicationDateFilter(array $pages): array {
        $publishDateField = $this->publicationSettings->getPublishDateField();
        $expirationDateField = $this->publicationSettings->getExpirationDateField();

        // If no fields configured, return all pages
        if (empty($publishDateField) && empty($expirationDateField)) {
            return $pages;
        }

        $fileIds = array_filter(array_column($pages, 'fileId'));
        if (empty($fileIds)) {
            return $pages;
        }

        $metaVoxData = $this->getMetaVoxDataForFiles($fileIds);
        $today = date('Y-m-d');

        return array_values(array_filter($pages, function($page) use ($publishDateField, $expirationDateField, $metaVoxData, $today) {
            $fileId = $page['fileId'] ?? null;
            if (!$fileId) {
                return true; // No fileId = include page (can't filter without metadata)
            }

            $meta = $metaVoxData[$fileId] ?? [];

            // Check publish date: page is visible if publish date is empty OR publish date <= today
            if (!empty($publishDateField)) {
                $publishDate = $meta[$publishDateField] ?? '';
                if (!empty($publishDate)) {
                    $parsedPublishDate = $this->parseDate($publishDate);
                    if ($parsedPublishDate && $parsedPublishDate > $today) {
                        return false; // Not yet published
                    }
                }
            }

            // Check expiration date: page is visible if expiration date is empty OR expiration date > today
            if (!empty($expirationDateField)) {
                $expirationDate = $meta[$expirationDateField] ?? '';
                if (!empty($expirationDate)) {
                    $parsedExpirationDate = $this->parseDate($expirationDate);
                    if ($parsedExpirationDate && $parsedExpirationDate <= $today) {
                        return false; // Already expired
                    }
                }
            }

            return true; // Page is visible
        }));
    }

    /**
     * Parse a date string to Y-m-d format for comparison
     *
     * @param string $dateStr Date string in various formats
     * @return string|null Normalized date in Y-m-d format, or null if parsing failed
     */
    private function parseDate(string $dateStr): ?string {
        if (empty($dateStr)) {
            return null;
        }

        $dateStr = trim($dateStr);

        // Try common date formats
        $formats = [
            'Y-m-d',        // ISO format: 2025-01-15
            'd-m-Y',        // European: 15-01-2025
            'm/d/Y',        // US: 01/15/2025
            'd/m/Y',        // European with slash: 15/01/2025
            'Y/m/d',        // Alternative ISO: 2025/01/15
            'Y-m-d H:i:s',  // ISO with time: 2025-01-15 14:30:00
            'd-m-Y H:i:s',  // European with time
            'Y-m-d\TH:i:s', // ISO 8601: 2025-01-15T14:30:00
        ];

        foreach ($formats as $format) {
            $date = \DateTime::createFromFormat($format, $dateStr);
            if ($date !== false) {
                return $date->format('Y-m-d');
            }
        }

        // Try strtotime as fallback for natural language dates
        $timestamp = strtotime($dateStr);
        if ($timestamp !== false) {
            return date('Y-m-d', $timestamp);
        }

        return null;
    }

    /**
     * Get MetaVox metadata for multiple files
     *
     * @param array $fileIds Array of file IDs
     * @return array Associative array: fileId => [fieldName => value, ...]
     */
    private function getMetaVoxDataForFiles(array $fileIds): array {
        if (empty($fileIds) || !$this->isMetaVoxAvailable()) {
            return [];
        }

        try {
            // Query the metavox_file_gf_meta table directly
            $qb = $this->db->getQueryBuilder();
            $qb->select('file_id', 'field_name', 'field_value')
                ->from('metavox_file_gf_meta')
                ->where($qb->expr()->in('file_id', $qb->createNamedParameter($fileIds, \Doctrine\DBAL\Connection::PARAM_INT_ARRAY)));

            $result = $qb->executeQuery();
            $rows = $result->fetchAll();
            $result->closeCursor();

            // Organize by file ID
            $metaData = [];
            foreach ($rows as $row) {
                $fileId = (int)$row['file_id'];
                $fieldName = $row['field_name'];
                $fieldValue = $row['field_value'];

                if (!isset($metaData[$fileId])) {
                    $metaData[$fileId] = [];
                }
                $metaData[$fileId][$fieldName] = $fieldValue;
            }

            return $metaData;

        } catch (\Exception $e) {
            $this->logger->error('Failed to get MetaVox data', [
                'error' => $e->getMessage(),
                'fileIds' => $fileIds
            ]);
            return [];
        }
    }

    /**
     * Format a timestamp in a localized date format
     */
    private function formatDateLocalized(int $timestamp, string $language): string {
        $months = [
            'nl' => ['januari', 'februari', 'maart', 'april', 'mei', 'juni', 'juli', 'augustus', 'september', 'oktober', 'november', 'december'],
            'en' => ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'],
            'de' => ['Januar', 'Februar', 'März', 'April', 'Mai', 'Juni', 'Juli', 'August', 'September', 'Oktober', 'November', 'Dezember'],
            'fr' => ['janvier', 'février', 'mars', 'avril', 'mai', 'juin', 'juillet', 'août', 'septembre', 'octobre', 'novembre', 'décembre'],
        ];

        $monthNames = $months[$language] ?? $months['en'];
        $monthIndex = (int)date('n', $timestamp) - 1;
        $day = date('j', $timestamp);
        $year = date('Y', $timestamp);

        return "$day {$monthNames[$monthIndex]} $year";
    }

    /**
     * Get list of available source folders for the News widget
     * Returns top-level folders in the language folder that contain pages
     */
    public function getNewsSourcFolders(): array {
        $folder = $this->getLanguageFolder();
        $folders = [];

        foreach ($this->getCachedDirectoryListing($folder) as $item) {
            if ($item->getType() !== \OCP\Files\FileInfo::TYPE_FOLDER) {
                continue;
            }

            $folderName = $item->getName();

            // Skip special folders
            if (in_array($folderName, ['_media', '_resources', 'images', 'files'])) {
                continue;
            }

            // Check if this folder contains any pages
            if ($this->folderContainsPages($item)) {
                $folders[] = [
                    'path' => $folderName,
                    'name' => $folderName,
                ];
            }
        }

        // Sort alphabetically
        usort($folders, function($a, $b) {
            return strcasecmp($a['name'], $b['name']);
        });

        return $folders;
    }

    /**
     * Check if a folder contains any pages (recursively)
     */
    private function folderContainsPages($folder): bool {
        $folderName = $folder->getName();

        // Check if this folder itself is a page
        try {
            $folder->get($folderName . '.json');
            return true;
        } catch (NotFoundException $e) {
            // Not a page folder
        }

        // Check subfolders
        foreach ($this->getCachedDirectoryListing($folder) as $item) {
            if ($item->getType() === \OCP\Files\FileInfo::TYPE_FOLDER) {
                $subFolderName = $item->getName();

                // Skip special folders
                if (in_array($subFolderName, ['_media', '_resources', 'images', 'files'])) {
                    continue;
                }

                if ($this->folderContainsPages($item)) {
                    return true;
                }
            }
        }

        return false;
    }

    // =========================================================================
    // TEMPLATE METHODS
    // =========================================================================

    /**
     * Find a page folder by its uniqueId
     *
     * @param string $uniqueId Page uniqueId
     * @return \OCP\Files\Folder|null The page folder or null if not found
     */
    private function findPageFolder(string $uniqueId): ?\OCP\Files\Folder {
        // Check cache first
        if (isset($this->pageFolderCache[$uniqueId])) {
            return $this->pageFolderCache[$uniqueId];
        }

        try {
            $langFolder = $this->getLanguageFolder();
            $result = $this->findPageByUniqueId($langFolder, $uniqueId);
            if ($result !== null && isset($result['folder'])) {
                $folder = $result['folder'];
                $this->pageFolderCache[$uniqueId] = $folder;
                return $folder;
            }
        } catch (\Exception $e) {
            $this->logger->warning('Could not find page folder for: ' . $uniqueId . ' - ' . $e->getMessage());
        }

        return null;
    }

    /**
     * Get the templates folder for the current user's language
     *
     * @return \OCP\Files\Folder|null The templates folder or null if not accessible
     */
    private function getTemplatesFolder(): ?\OCP\Files\Folder {
        try {
            $langFolder = $this->getLanguageFolder();
            if ($langFolder->nodeExists('_templates')) {
                $templatesFolder = $langFolder->get('_templates');
                if ($templatesFolder instanceof \OCP\Files\Folder) {
                    return $templatesFolder;
                }
            }
            return null;
        } catch (\Exception $e) {
            $this->logger->warning('Could not access templates folder: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * List all available page templates
     *
     * @return array List of template metadata
     */
    public function listTemplates(): array {
        $templatesFolder = $this->getTemplatesFolder();
        if ($templatesFolder === null) {
            return [];
        }

        $templates = [];

        try {
            foreach ($templatesFolder->getDirectoryListing() as $item) {
                if (!($item instanceof \OCP\Files\Folder)) {
                    continue;
                }

                $templateId = $item->getName();

                // Skip special folders
                if (str_starts_with($templateId, '.') || $templateId === '_media') {
                    continue;
                }

                // Try to read the template JSON file
                try {
                    $jsonFile = $item->get($templateId . '.json');
                    if (!($jsonFile instanceof \OCP\Files\File)) {
                        continue;
                    }

                    $content = json_decode($jsonFile->getContent(), true);
                    if (!$content) {
                        continue;
                    }

                    // Extract preview metadata
                    $preview = $this->extractTemplatePreviewMetadata($content);

                    $templates[] = [
                        'id' => $templateId,
                        'uniqueId' => $content['uniqueId'] ?? 'template-' . $templateId,
                        'title' => $content['title'] ?? $templateId,
                        'description' => $content['description'] ?? '',
                        'created' => $content['created'] ?? $jsonFile->getMTime(),
                        'modified' => $jsonFile->getMTime(),
                        'createdBy' => $content['createdBy'] ?? '',
                        'preview' => $preview,
                    ];
                } catch (NotFoundException $e) {
                    // Template folder exists but no JSON file, skip
                    continue;
                }
            }
        } catch (\Exception $e) {
            $this->logger->error('Failed to list templates: ' . $e->getMessage());
        }

        // Sort by title
        usort($templates, fn($a, $b) => strcasecmp($a['title'], $b['title']));

        return $templates;
    }

    /**
     * Extract preview metadata from template content for display in template picker
     *
     * @param array $content Template content data
     * @return array Preview metadata
     */
    private function extractTemplatePreviewMetadata(array $content): array {
        $layout = $content['layout'] ?? [];
        $rows = $layout['rows'] ?? [];
        $sideColumns = $layout['sideColumns'] ?? [];

        // Collect all widget types
        $widgetTypes = [];
        $widgetCount = 0;
        $maxColumns = 1;
        $hasCollapsible = false;
        $firstBackgroundColor = '';

        // Process main rows
        foreach ($rows as $row) {
            $rowColumns = $row['columns'] ?? 1;
            $maxColumns = max($maxColumns, $rowColumns);

            if (!empty($row['collapsible'])) {
                $hasCollapsible = true;
            }

            if (empty($firstBackgroundColor) && !empty($row['backgroundColor'])) {
                $firstBackgroundColor = $row['backgroundColor'];
            }

            if (isset($row['widgets'])) {
                foreach ($row['widgets'] as $widget) {
                    if (isset($widget['type'])) {
                        $widgetTypes[] = $widget['type'];
                        $widgetCount++;
                    }
                }
            }
        }

        // Check side columns
        $hasSidebars = false;
        foreach ($sideColumns as $side => $column) {
            if (!empty($column['enabled']) && !empty($column['widgets'])) {
                $hasSidebars = true;
                foreach ($column['widgets'] as $widget) {
                    if (isset($widget['type'])) {
                        $widgetTypes[] = $widget['type'];
                        $widgetCount++;
                    }
                }
            }
        }

        // Determine complexity
        $complexity = 'simple';
        if ($widgetCount > 10 || $hasCollapsible || $hasSidebars) {
            $complexity = 'complex';
        } elseif ($widgetCount > 5 || $maxColumns > 2) {
            $complexity = 'moderate';
        }

        return [
            'hasHeaderRow' => isset($rows[0]) && !empty($rows[0]['backgroundColor']),
            'columnCount' => $maxColumns,
            'rowCount' => count($rows),
            'widgetTypes' => array_values(array_unique($widgetTypes)),
            'widgetCount' => $widgetCount,
            'backgroundColor' => $firstBackgroundColor,
            'hasSidebars' => $hasSidebars,
            'hasCollapsible' => $hasCollapsible,
            'complexity' => $complexity,
        ];
    }

    /**
     * Get a specific template by ID
     *
     * @param string $templateId Template ID (folder name)
     * @return array|null Template data or null if not found
     */
    public function getTemplate(string $templateId): ?array {
        $templatesFolder = $this->getTemplatesFolder();
        if ($templatesFolder === null) {
            return null;
        }

        try {
            if (!$templatesFolder->nodeExists($templateId)) {
                return null;
            }

            $templateFolder = $templatesFolder->get($templateId);
            if (!($templateFolder instanceof \OCP\Files\Folder)) {
                return null;
            }

            $jsonFile = $templateFolder->get($templateId . '.json');
            if (!($jsonFile instanceof \OCP\Files\File)) {
                return null;
            }

            $content = json_decode($jsonFile->getContent(), true);
            if (!$content) {
                return null;
            }

            return $content;
        } catch (NotFoundException $e) {
            return null;
        } catch (\Exception $e) {
            $this->logger->error('Failed to get template: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Save a page as a template
     *
     * @param string $pageUniqueId The uniqueId of the page to save as template
     * @param string $templateTitle Title for the template
     * @param string|null $templateDescription Optional description
     * @return array Result with success status and template data or error message
     */
    public function saveAsTemplate(string $pageUniqueId, string $templateTitle, ?string $templateDescription = null): array {
        try {
            // Get the source page
            $pageData = $this->getPage($pageUniqueId);
            if (!$pageData) {
                return ['success' => false, 'error' => 'Page not found'];
            }

            // Get or create templates folder
            $langFolder = $this->getLanguageFolder();
            if (!$langFolder->nodeExists('_templates')) {
                $langFolder->newFolder('_templates');
            }
            $templatesFolder = $langFolder->get('_templates');

            // Generate template ID from title
            $templateId = $this->sanitizeId($templateTitle);

            // Handle duplicate names by appending number
            $originalId = $templateId;
            $counter = 1;
            while ($templatesFolder->nodeExists($templateId)) {
                $counter++;
                $templateId = $originalId . '-' . $counter;
            }

            // Create template folder
            $templateFolder = $templatesFolder->newFolder($templateId);

            // Create _media folder in template
            $templateMediaFolder = $templateFolder->newFolder('_media');

            // Prepare template data
            $templateData = $pageData;
            $templateData['uniqueId'] = 'template-' . $this->generateUUID();
            $templateData['title'] = $templateTitle;
            $templateData['description'] = $templateDescription ?? '';
            $templateData['isTemplate'] = true;
            $templateData['created'] = time();
            $templateData['createdBy'] = $this->userId;
            $templateData['sourcePageId'] = $pageUniqueId;

            // Remove page-specific data
            unset($templateData['path']);
            unset($templateData['parentPath']);

            // Copy media files from source page to template
            $pageFolder = $this->findPageFolder($pageUniqueId);
            if ($pageFolder && $pageFolder->nodeExists('_media')) {
                $sourceMediaFolder = $pageFolder->get('_media');
                if ($sourceMediaFolder instanceof \OCP\Files\Folder) {
                    $this->copyMediaFolderContents($sourceMediaFolder, $templateMediaFolder);
                }
            }

            // Write template JSON
            $jsonFile = $templateFolder->newFile($templateId . '.json');
            $jsonFile->putContent(json_encode($templateData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

            $this->logger->info('Created template: ' . $templateId . ' from page: ' . $pageUniqueId);

            return [
                'success' => true,
                'templateId' => $templateId,
                'template' => [
                    'id' => $templateId,
                    'uniqueId' => $templateData['uniqueId'],
                    'title' => $templateData['title'],
                    'description' => $templateData['description'],
                    'created' => $templateData['created'],
                    'createdBy' => $templateData['createdBy'],
                ],
            ];
        } catch (\Exception $e) {
            $this->logger->error('Failed to save as template: ' . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Delete a template
     *
     * @param string $templateId Template ID (folder name)
     * @return array Result with success status
     */
    public function deleteTemplate(string $templateId): array {
        try {
            $templatesFolder = $this->getTemplatesFolder();
            if ($templatesFolder === null) {
                return ['success' => false, 'error' => 'Templates folder not accessible'];
            }

            if (!$templatesFolder->nodeExists($templateId)) {
                return ['success' => false, 'error' => 'Template not found'];
            }

            $templateFolder = $templatesFolder->get($templateId);
            $templateFolder->delete();

            $this->logger->info('Deleted template: ' . $templateId);

            return ['success' => true];
        } catch (\Exception $e) {
            $this->logger->error('Failed to delete template: ' . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Create a new page from a template
     *
     * @param string $templateId Template ID to use
     * @param string $pageTitle Title for the new page
     * @param string|null $parentPath Optional parent path for nested pages
     * @return array Result with success status and page data
     */
    public function createPageFromTemplate(string $templateId, string $pageTitle, ?string $parentPath = null): array {
        try {
            // Get template data
            $templateData = $this->getTemplate($templateId);
            if ($templateData === null) {
                return ['success' => false, 'error' => 'Template not found'];
            }

            // Prepare page data from template
            $pageData = $templateData;

            // Generate new page ID and uniqueId
            $pageId = $this->sanitizeId($pageTitle);
            $pageData['id'] = $pageId;
            $pageData['title'] = $pageTitle;
            $pageData['uniqueId'] = 'page-' . $this->generateUUID();
            $pageData['created'] = time();
            $pageData['modified'] = time();

            // Remove template-specific fields
            unset($pageData['isTemplate']);
            unset($pageData['description']);
            unset($pageData['createdBy']);
            unset($pageData['sourcePageId']);

            // Create the page using existing method
            $createdPage = $this->createPage($pageData, $parentPath);

            // Copy media files from template to new page
            $templatesFolder = $this->getTemplatesFolder();
            if ($templatesFolder && $templatesFolder->nodeExists($templateId)) {
                $templateFolder = $templatesFolder->get($templateId);
                if ($templateFolder instanceof \OCP\Files\Folder && $templateFolder->nodeExists('_media')) {
                    $templateMediaFolder = $templateFolder->get('_media');

                    // Get the new page's folder (should be in cache from createPage)
                    $newPageFolder = $this->findPageFolder($createdPage['uniqueId']);
                    $this->logger->info('Template media copy: page folder found = ' . ($newPageFolder ? 'yes' : 'no') . ' for ' . $createdPage['uniqueId']);
                    if ($newPageFolder && $templateMediaFolder instanceof \OCP\Files\Folder) {
                        // Create _media folder if not exists
                        if (!$newPageFolder->nodeExists('_media')) {
                            $newPageFolder->newFolder('_media');
                        }
                        $pageMediaFolder = $newPageFolder->get('_media');
                        if ($pageMediaFolder instanceof \OCP\Files\Folder) {
                            $this->copyMediaFolderContents($templateMediaFolder, $pageMediaFolder);
                        }
                    }
                }
            }

            $this->logger->info('Created page from template: ' . $templateId . ' -> ' . $createdPage['uniqueId']);

            return [
                'success' => true,
                'page' => $createdPage,
            ];
        } catch (\Exception $e) {
            $this->logger->error('Failed to create page from template: ' . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Copy all files from one media folder to another (within Nextcloud storage)
     *
     * @param \OCP\Files\Folder $source Source folder
     * @param \OCP\Files\Folder $target Target folder
     */
    private function copyMediaFolderContents(\OCP\Files\Folder $source, \OCP\Files\Folder $target): void {
        try {
            foreach ($source->getDirectoryListing() as $item) {
                $name = $item->getName();

                // Skip hidden files
                if (str_starts_with($name, '.')) {
                    continue;
                }

                if ($item instanceof \OCP\Files\File) {
                    // Copy file
                    try {
                        $newFile = $target->newFile($name);
                        $newFile->putContent($item->getContent());
                    } catch (\Exception $e) {
                        $this->logger->warning('Failed to copy media file: ' . $name . ' - ' . $e->getMessage());
                    }
                } elseif ($item instanceof \OCP\Files\Folder) {
                    // Recursively copy subfolder
                    try {
                        if (!$target->nodeExists($name)) {
                            $newSubFolder = $target->newFolder($name);
                        } else {
                            $newSubFolder = $target->get($name);
                        }
                        if ($newSubFolder instanceof \OCP\Files\Folder) {
                            $this->copyMediaFolderContents($item, $newSubFolder);
                        }
                    } catch (\Exception $e) {
                        $this->logger->warning('Failed to copy media subfolder: ' . $name . ' - ' . $e->getMessage());
                    }
                }
            }
        } catch (\Exception $e) {
            $this->logger->error('Failed to copy media folder contents: ' . $e->getMessage());
        }
    }

    /**
     * Check if the user can create templates (has write access to _templates folder)
     *
     * @return bool
     */
    public function canCreateTemplates(): bool {
        try {
            $langFolder = $this->getLanguageFolder();

            // Check if _templates folder exists
            if (!$langFolder->nodeExists('_templates')) {
                // Check if user can create it
                return $langFolder->isCreatable();
            }

            $templatesFolder = $langFolder->get('_templates');
            return $templatesFolder->isCreatable();
        } catch (\Exception $e) {
            return false;
        }
    }
}
