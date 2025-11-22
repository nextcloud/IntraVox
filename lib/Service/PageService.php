<?php
declare(strict_types=1);

namespace OCA\IntraVox\Service;

use OCP\Files\IRootFolder;
use OCP\Files\NotFoundException;
use OCP\IUserSession;
use OCP\IConfig;
use OCP\IDBConnection;
use Psr\Log\LoggerInterface;
use OCP\Files\Cache\ICacheEntry;

class PageService {
    private const ALLOWED_WIDGET_TYPES = ['text', 'heading', 'image', 'links', 'file', 'divider'];
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
    private array $pageFolderCache = [];

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
     * Create a simple .nomedia marker for the images folder
     * The folder name "images" itself is the primary identifier
     */
    private function createImagesFolderIcon($imagesFolder): void {
        try {
            // Only create a simple .nomedia file
            // This is standard practice for media storage folders
            if (!$imagesFolder->nodeExists('.nomedia')) {
                $nomediaFile = $imagesFolder->newFile('.nomedia');
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
     * Recursively find a page by uniqueId
     */
    private function findPageByUniqueId($folder, string $uniqueId): ?array {
        // Check for home.json first (most common case)
        if ($uniqueId === 'page-2e8f694e-147e-4793-8949-4732e679ae6b') {
            try {
                $homeFile = $folder->get('home.json');
                return ['file' => $homeFile, 'folder' => $folder];
            } catch (NotFoundException $e) {
                // Continue searching
            }
        }

        // FIRST: Check all JSON files in current folder
        // This is faster and catches files like news/company-blog.json
        foreach ($folder->getDirectoryListing() as $item) {
            $itemName = $item->getName();

            if ($item->getType() === \OCP\Files\FileInfo::TYPE_FILE &&
                substr($itemName, -5) === '.json' &&
                $itemName !== 'navigation.json' &&
                $itemName !== 'footer.json') {
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
                                return ['file' => $item, 'folder' => $matchingFolder];
                            }
                        } catch (NotFoundException $e) {
                            // No matching folder, use current folder
                        }
                        return ['file' => $item, 'folder' => $folder];
                    }
                } catch (\Exception $e) {
                    // Skip invalid files
                    continue;
                }
            }
        }

        // SECOND: Recursively search subfolders
        foreach ($folder->getDirectoryListing() as $item) {
            $itemName = $item->getName();

            if ($item->getType() === \OCP\Files\FileInfo::TYPE_FOLDER) {
                // Skip image folders and special folders
                if (strpos($itemName, '📷') === 0 || $itemName === 'images' || $itemName === '.nomedia') {
                    continue;
                }

                $result = $this->findPageByUniqueId($item, $uniqueId);
                if ($result !== null) {
                    return $result;
                }
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

            if ($data && isset($data['uniqueId'], $data['title'])) {
                $pages[] = [
                    'uniqueId' => $data['uniqueId'],
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

                    if ($data && isset($data['uniqueId'], $data['title'])) {
                        $pages[] = [
                            'uniqueId' => $data['uniqueId'],
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
     * Get a specific page by uniqueId or legacy id
     */
    public function getPage(string $id): array {
        $folder = $this->getLanguageFolder();
        $result = null;

        // Save original ID before sanitization
        $originalId = $id;

        $this->logger->debug('IntraVox: Getting page - originalId', ['originalId' => $originalId]);

        // Check for uniqueId pattern BEFORE sanitization
        if (strpos($originalId, 'page-') === 0) {
            $this->logger->debug('IntraVox: Searching by uniqueId', ['uniqueId' => $originalId]);
            $result = $this->findPageByUniqueId($folder, $originalId);
            if ($result) {
                $this->logger->debug('IntraVox: Found by uniqueId', ['file' => $result['file']->getName()]);
            } else {
                $this->logger->warning('IntraVox: Not found by uniqueId', ['uniqueId' => $originalId]);
            }
        }

        // Only sanitize for legacy ID fallback
        if ($result === null) {
            $id = $this->sanitizeId($originalId);
            $this->logger->debug('IntraVox: Trying legacy id lookup', ['id' => $id]);
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

        return $this->sanitizePage($data);
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

        // Public pages: max depth 3
        if (count($pathParts) > 0 && $pathParts[0] === 'public') {
            return 3;
        }

        // Department pages: max depth 2
        if (count($pathParts) > 0 && $pathParts[0] === 'departments') {
            return 2;
        }

        // Default: max depth 3
        return 3;
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
            foreach ($folder->getDirectoryListing() as $item) {
                if ($item->getType() !== \OCP\Files\FileInfo::TYPE_FOLDER) {
                    continue;
                }

                // Skip special folders
                if (in_array($item->getName(), ['images', 'files'])) {
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

        // Always start with Home
        $breadcrumb[] = [
            'id' => 'home',
            'title' => 'Home',
            'path' => $this->getUserLanguage() . '/home',
            'url' => '#home',
            'current' => false
        ];

        // Parse path to build breadcrumb
        $pathParts = explode('/', $page['path']);
        $currentPath = '';

        foreach ($pathParts as $index => $part) {
            // Skip language folder in breadcrumb display
            if (in_array($part, self::SUPPORTED_LANGUAGES)) {
                continue;
            }

            // Skip 'public' and 'departments' keywords
            if (in_array($part, ['public', 'departments'])) {
                continue;
            }

            $currentPath .= ($currentPath ? '/' : '') . $part;

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

            // Try to load parent page for its title
            try {
                $parentPage = $this->getPage($part);
                $breadcrumb[] = [
                    'id' => $part,
                    'title' => $parentPage['title'],
                    'path' => $parentPage['path'],
                    'url' => '#' . $part,
                    'current' => false
                ];
            } catch (\Exception $e) {
                // Parent page not found or error loading it
                // Use folder name as fallback
                $breadcrumb[] = [
                    'id' => $part,
                    'title' => ucfirst(str_replace('-', ' ', $part)),
                    'path' => $currentPath,
                    'url' => '#' . $part,
                    'current' => false
                ];
            }
        }

        return $breadcrumb;
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
     * @param array $data Page data
     * @param string|null $parentPath Optional parent path (e.g., "nl/departments/marketing")
     * @return array Created page data
     */
    private function createPageAtPath(array $data, ?string $parentPath = null): array {
        $pageId = $data['id'];
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

            // Create images folder for home if it doesn't exist
            try {
                $imagesFolder = $targetFolder->get('📷 images');
                $this->createImagesFolderIcon($imagesFolder);
            } catch (NotFoundException $e) {
                $imagesFolder = $targetFolder->newFolder('📷 images');
                $this->createImagesFolderIcon($imagesFolder);
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

            // Create images subfolder
            try {
                $imagesFolder = $pageFolder->newFolder('📷 images');
                // Add a .nomedia file to indicate this is a special folder
                $this->createImagesFolderIcon($imagesFolder);
            } catch (\Exception $e) {
                // Images folder might already exist, that's okay
                try {
                    $imagesFolder = $pageFolder->get('📷 images');
                    $this->createImagesFolderIcon($imagesFolder);
                } catch (\Exception $ex) {
                    // Couldn't get images folder
                }
            }

            $this->scanPageFolder($pageFolder);
        }

        return $data;
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

        $data['created'] = time();
        $data['modified'] = time();

        // Generate uniqueId if not provided
        if (!isset($data['uniqueId'])) {
            $data['uniqueId'] = 'page-' . $this->generateUUID();
        }

        $validatedData = $this->validateAndSanitizePage($data);

        // Use the new createPageAtPath helper
        return $this->createPageAtPath($validatedData, $parentPath);
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
            // TODO: Version management disabled for v1.0
            // Waiting for Nextcloud groupfolders to properly support versioning (issue #50)
            // The versioning infrastructure is in place but hidden from users until the upstream issue is resolved.
            // See: https://github.com/nextcloud/groupfolders/issues/50
            // Uncomment when groupfolders versioning is fixed:
            // $this->createManualVersion($file, $user);

            // Update the file
            $file->putContent(json_encode($validatedData, JSON_PRETTY_PRINT));

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

            // Find the user mount fileId where versions should be stored
            $versionFileId = $this->findVersionFileId($groupFolderId, $groupfolderFileId, $file->getInternalPath());

            // If no user mount fileId exists yet, use the groupfolder fileId
            // This will happen for new pages that haven't been accessed via Files app yet
            if (!$versionFileId) {
                $versionFileId = $groupfolderFileId;
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

            // Save version metadata (author)
            $this->saveVersionMetadata($groupFolderId, $versionFileId, $timestamp, [
                'author' => $user->getDisplayName()
            ]);
        } catch (\Exception $e) {
            // Versioning failure shouldn't block saves - silently continue
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

        $languageFolder = $this->getLanguageFolder();

        // Get images folder for this page
        // Folder is named "📷 images" in Files but API accepts "images"
        if ($pageId === 'home') {
            // Home images are in root/📷 images/
            try {
                $imagesFolder = $languageFolder->get('📷 images');
            } catch (NotFoundException $e) {
                $imagesFolder = $languageFolder->newFolder('📷 images');
            }
        } else {
            // Find the page folder using findPageById (handles nested pages correctly)
            $result = $this->findPageById($languageFolder, $pageId);
            if ($result === null) {
                throw new \Exception('Page not found');
            }

            $pageFolder = $result['folder'];

            // Get or create images subfolder
            try {
                $imagesFolder = $pageFolder->get('📷 images');
            } catch (NotFoundException $e) {
                $imagesFolder = $pageFolder->newFolder('📷 images');
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
        // Save original BEFORE sanitization
        $originalPageId = $pageId;
        $filename = basename($filename); // Prevent directory traversal

        $languageFolder = $this->getLanguageFolder();

        try {
            // Handle home page with original pageId
            if ($originalPageId === 'home' ||
                $originalPageId === '2e8f694e-147e-4793-8949-4732e679ae6b' ||
                $originalPageId === 'page-2e8f694e-147e-4793-8949-4732e679ae6b') {

                $imagesFolder = $languageFolder->get('images');
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
            }

            // Try cache with BOTH original and sanitized IDs
            $imagesFolder = null;
            $pageId = $this->sanitizeId($originalPageId);

            if (isset($this->pageFolderCache[$originalPageId])) {
                // Cache hit with original ID (page-abc-123...)
                $pageFolder = $this->pageFolderCache[$originalPageId];
                $imagesFolder = $pageFolder->get('images');
            } else if (isset($this->pageFolderCache[$pageId])) {
                // Cache hit with sanitized ID (abc-123...)
                $pageFolder = $this->pageFolderCache[$pageId];
                $imagesFolder = $pageFolder->get('images');
            }

            // If cache miss, search using ORIGINAL pageId
            if ($imagesFolder === null) {
                $imagesFolder = $this->findImagesFolderForPage(
                    $languageFolder,
                    $originalPageId  // Use ORIGINAL with 'page-' prefix
                );
            }

            if ($imagesFolder === null) {
                throw new \Exception('Images folder not found for page: ' . $originalPageId);
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
     * Recursively find images folder for a page by uniqueId
     */
    private function findImagesFolderForPage($folder, string $uniqueId): ?\OCP\Files\Folder {
        // First check if the CURRENT folder contains the page JSON
        try {
            $imagesFolder = $folder->get('images');
            if ($imagesFolder->getType() === \OCP\Files\FileInfo::TYPE_FOLDER) {
                // Scan JSON files in current folder
                foreach ($folder->getDirectoryListing() as $item) {
                    if ($item->getType() === \OCP\Files\FileInfo::TYPE_FILE &&
                        substr($item->getName(), -5) === '.json' &&
                        $item->getName() !== 'navigation.json' &&
                        $item->getName() !== 'footer.json') {

                        $content = $item->getContent();
                        $data = json_decode($content, true);

                        // Match against uniqueId field
                        if ($data && isset($data['uniqueId']) &&
                            $data['uniqueId'] === $uniqueId) {

                            // Found matching page in CURRENT folder!
                            return $imagesFolder;
                        }
                    }
                }
            }
        } catch (\OCP\Files\NotFoundException $e) {
            // No images folder in current folder, continue searching subfolders
        }

        // Recursively search subfolders
        foreach ($folder->getDirectoryListing() as $item) {
            if ($item->getType() === \OCP\Files\FileInfo::TYPE_FOLDER) {
                $itemName = $item->getName();

                // Skip special folders to avoid infinite loops
                if ($itemName === 'images' || strpos($itemName, '📷') === 0 ||
                    $itemName === '.nomedia') {
                    continue;
                }

                // Recurse into subfolder
                $result = $this->findImagesFolderForPage($item, $uniqueId);
                if ($result !== null) {
                    return $result;
                }
            }
        }

        return null;
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

            case 'links':
                $sanitized['items'] = [];
                if (isset($widget['items']) && is_array($widget['items'])) {
                    foreach ($widget['items'] as $link) {
                        $sanitizedLink = [];
                        // Use sanitizeHtml for link text to allow HTML entities and formatting
                        $sanitizedLink['text'] = $this->sanitizeHtml($link['text'] ?? '');
                        $sanitizedLink['url'] = $this->sanitizeUrl($link['url'] ?? '');
                        $sanitizedLink['icon'] = $this->sanitizeText($link['icon'] ?? '');
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

            // The problem: when files are accessed via __groupfolders path, they have one fileId (e.g., 421)
            // But when the same file is accessed via user mount (files/...), it has a different fileId (e.g., 763)
            // Versions are stored using the user mount fileId, so we need to find it
            $versionFileId = $this->findVersionFileId($groupFolderId, $fileId, $file->getInternalPath());

            if (!$versionFileId) {
                return [];
            }

            // Read versions directly from filesystem using the version fileId
            $versionDir = "/var/www/nextcloud/data/__groupfolders/{$groupFolderId}/versions/{$versionFileId}";

            if (!is_dir($versionDir)) {
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

                // Read version metadata if available
                $metadata = $this->getVersionMetadata($groupFolderId, $versionFileId, $timestamp);

                $versions[] = [
                    'timestamp' => $timestamp,
                    'date' => date('Y-m-d H:i:s', $timestamp),
                    'size' => $size,
                    'relativeDate' => $this->getRelativeTime($timestamp),
                    'label' => $metadata['label'] ?? null,
                    'author' => $metadata['author'] ?? null
                ];
            }

            // Sort newest first
            usort($versions, fn($a, $b) => $b['timestamp'] <=> $a['timestamp']);

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
                return null;
            }

            // Extract the relative path after "files/"
            // e.g., "__groupfolders/4/files/en/home.json" -> "en/home.json"
            $groupfolderPath = $groupfolderFile['path'];

            if (preg_match('#__groupfolders/\d+/files/(.+)$#', $groupfolderPath, $matches)) {
                $relativePath = $matches[1];

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
                        return $userFileId;
                    }
                }
            }

            return null;

        } catch (\Exception $e) {
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
            // Error searching folder
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

            // Check if this is a groupfolder storage
            if (!($storage instanceof \OCA\GroupFolders\Mount\GroupFolderStorage)) {
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
        } catch (\Exception $e) {
            // Don't throw - versioning failure shouldn't prevent saves
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
     * Get metadata for a page (file info like in Files app)
     */
    public function getPageMetadata(string $pageId): array {
        $result = $this->findPageById($this->getLanguageFolder(), $pageId);

        if (!$result) {
            throw new \Exception('Page not found: ' . $pageId);
        }

        $file = $result['file'];
        $folder = $result['folder'];

        // Get file stats
        $mtime = $file->getMTime();
        $size = $file->getSize();
        $owner = $file->getOwner()->getUID();
        $permissions = $file->getPermissions();

        // Get page content for additional metadata
        $content = $file->getContent();
        $data = json_decode($content, true);

        // Get the groupfolder storage file ID (needed for MetaVox integration)
        $actualFileId = $this->getFileIdFromDatabase($file, $folder);

        // Get groupfolder ID and name
        $groupfolderId = $this->extractGroupfolderId($file->getPath());
        $mountPoint = $this->getGroupfolderName($groupfolderId);

        // Get parent folder ID for Files app link
        $parentFolderId = $this->getFolderIdFromDatabase($folder->getPath(), $groupfolderId);

        // Get path-related metadata using enrichWithPathData
        $pathData = [];
        $pathData['id'] = $pageId;
        $pathData = $this->enrichWithPathData($pathData, $folder);

        // Check if page has children
        $hasChildren = $this->hasChildren($pageId);

        // Determine page type
        $pageType = $this->determinePageType($pathData['path'] ?? '', $hasChildren);

        $metadata = [
            'size' => $size,
            'sizeFormatted' => $this->formatBytes($size),
            'modified' => $mtime,
            'modifiedFormatted' => date('Y-m-d H:i:s', $mtime),
            'modifiedRelative' => $this->getRelativeTime($mtime),
            'owner' => $owner,
            'path' => $file->getPath(),
            'mimeType' => $file->getMimeType(),
            'permissions' => $permissions,
            'canEdit' => ($permissions & \OCP\Constants::PERMISSION_UPDATE) === \OCP\Constants::PERMISSION_UPDATE,
            'canDelete' => ($permissions & \OCP\Constants::PERMISSION_DELETE) === \OCP\Constants::PERMISSION_DELETE,
            'title' => $data['title'] ?? '',
            'created' => $data['created'] ?? $mtime,
            'createdFormatted' => date('Y-m-d H:i:s', $data['created'] ?? $mtime),
            'uniqueId' => $data['uniqueId'] ?? '',
            'fileId' => $actualFileId,
            'groupfolderId' => $groupfolderId,
            'mountPoint' => $mountPoint,
            'parentFolderId' => $parentFolderId,
            // Add path-related data
            'depth' => $pathData['depth'] ?? null,
            'parentId' => $pathData['parentId'] ?? null,
            'parentPath' => $pathData['parentPath'] ?? null,
            'department' => $pathData['department'] ?? null,
            'relativePath' => $pathData['path'] ?? null,
            // Add structure data
            'hasChildren' => $hasChildren,
            'type' => $pageType,
        ];

        return $metadata;
    }

    /**
     * Update page metadata (title only for now, similar to Files rename)
     */
    public function updatePageMetadata(string $pageId, array $metadata): array {
        $result = $this->findPageById($this->getLanguageFolder(), $pageId);

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
            $data['modified'] = time();
            $changed = true;
        }

        // Save if changed
        if ($changed) {
            $user = $this->userSession->getUser();
            if ($user) {
                $this->createManualVersion($file, $user);
            }
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
     * Get version metadata (label, author)
     */
    private function getVersionMetadata(int $groupFolderId, int $versionFileId, int $timestamp): array {
        try {
            $metadataFile = "/var/www/nextcloud/data/__groupfolders/{$groupFolderId}/versions/{$versionFileId}/.metadata/{$timestamp}.json";

            if (!file_exists($metadataFile)) {
                return [];
            }

            $content = file_get_contents($metadataFile);
            return json_decode($content, true) ?: [];
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Save version metadata (label, author)
     */
    private function saveVersionMetadata(int $groupFolderId, int $versionFileId, int $timestamp, array $metadata): void {
        try {
            $metadataDir = "/var/www/nextcloud/data/__groupfolders/{$groupFolderId}/versions/{$versionFileId}/.metadata";

            if (!is_dir($metadataDir)) {
                mkdir($metadataDir, 0755, true);
                chown($metadataDir, 'www-data');
                chgrp($metadataDir, 'www-data');
            }

            $metadataFile = "{$metadataDir}/{$timestamp}.json";

            // Merge with existing metadata if it exists
            $existing = [];
            if (file_exists($metadataFile)) {
                $content = file_get_contents($metadataFile);
                $existing = json_decode($content, true) ?: [];
            }

            $merged = array_merge($existing, $metadata);

            file_put_contents($metadataFile, json_encode($merged, JSON_PRETTY_PRINT));
            chown($metadataFile, 'www-data');
            chgrp($metadataFile, 'www-data');
            chmod($metadataFile, 0644);
        } catch (\Exception $e) {
            // Metadata save failure shouldn't block operations
        }
    }

    /**
     * Update version label
     */
    public function updateVersionLabel(string $pageId, int $timestamp, ?string $label): void {
        // Verify page exists
        $result = $this->findPageById($this->getLanguageFolder(), $pageId);

        if (!$result) {
            throw new \Exception('Page not found: ' . $pageId);
        }

        $file = $result['file'];
        $groupFolderId = $this->setupService->getGroupFolderId();
        $versionFileId = $this->findVersionFileId($groupFolderId, $file->getId(), $file->getInternalPath());

        if (!$versionFileId) {
            throw new \Exception('Version storage not found');
        }

        // Verify version exists
        $versionFile = "/var/www/nextcloud/data/__groupfolders/{$groupFolderId}/versions/{$versionFileId}/{$timestamp}";
        if (!file_exists($versionFile)) {
            throw new \Exception('Version not found');
        }

        // Update metadata
        $this->saveVersionMetadata($groupFolderId, $versionFileId, $timestamp, [
            'label' => $label
        ]);
    }

    /**
     * Get version content for preview
     */
    public function getVersionContent(string $pageId, int $timestamp): array {
        // Verify page exists
        $result = $this->findPageById($this->getLanguageFolder(), $pageId);

        if (!$result) {
            throw new \Exception('Page not found: ' . $pageId);
        }

        $file = $result['file'];
        $groupFolderId = $this->setupService->getGroupFolderId();
        $versionFileId = $this->findVersionFileId($groupFolderId, $file->getId(), $file->getInternalPath());

        if (!$versionFileId) {
            throw new \Exception('Version storage not found');
        }

        // Read version file
        $versionFile = "/var/www/nextcloud/data/__groupfolders/{$groupFolderId}/versions/{$versionFileId}/{$timestamp}";
        if (!file_exists($versionFile)) {
            throw new \Exception('Version not found');
        }

        $content = file_get_contents($versionFile);

        // The version file contains the full JSON content of the page
        // So we return it as-is for both content and rawContent
        return [
            'title' => 'Version from ' . date('Y-m-d H:i:s', $timestamp),
            'content' => $content,
            'rawContent' => $content
        ];
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
     * Search pages by query string
     * Searches in page titles and text widget content
     */
    public function searchPages(string $query): array {
        $results = [];
        $query = mb_strtolower($query);

        // Get all pages (listPages already gets them recursively from current language folder)
        $pages = $this->listPages();

        foreach ($pages as $page) {
            $matches = [];
            $score = 0;

            // Search in title (higher weight)
            if (isset($page['title']) && mb_stripos($page['title'], $query) !== false) {
                $score += 10;
                $matches[] = [
                    'type' => 'title',
                    'text' => $page['title']
                ];
            }

            // Search in uniqueId (medium weight)
            if (isset($page['uniqueId']) && mb_stripos($page['uniqueId'], $query) !== false) {
                $score += 5;
            }

            // Load page content and search in text widgets
            try {
                // Skip pages without uniqueId
                if (!isset($page['uniqueId']) || empty($page['uniqueId'])) {
                    continue;
                }

                // Get the full page data to search in content
                $pageData = $this->getPage($page['uniqueId']);

                if ($pageData && isset($pageData['layout']['rows'])) {
                    foreach ($pageData['layout']['rows'] as $row) {
                        if (isset($row['widgets'])) {
                            foreach ($row['widgets'] as $widget) {
                                // Search in text widgets
                                if ($widget['type'] === 'text' && isset($widget['content'])) {
                                    if (mb_stripos($widget['content'], $query) !== false) {
                                        $score += 3;
                                        // Extract snippet around match
                                        $snippet = $this->extractSnippet($widget['content'], $query);
                                        $matches[] = [
                                            'type' => 'content',
                                            'text' => $snippet
                                        ];
                                    }
                                }
                                // Search in heading widgets
                                if ($widget['type'] === 'heading' && isset($widget['text'])) {
                                    if (mb_stripos($widget['text'], $query) !== false) {
                                        $score += 5;
                                        $matches[] = [
                                            'type' => 'heading',
                                            'text' => $widget['text']
                                        ];
                                    }
                                }
                            }
                        }
                    }
                }
            } catch (\Exception $e) {
                // Skip pages that can't be read
                continue;
            }

            // If we have matches, add to results
            if ($score > 0) {
                $results[] = [
                    'uniqueId' => $page['uniqueId'] ?? null,
                    'title' => $page['title'] ?? 'Untitled',
                    'path' => $page['path'] ?? '',
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
}
