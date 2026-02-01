<?php
declare(strict_types=1);

namespace OCA\IntraVox\Service;

use OCP\Files\IRootFolder;
use OCP\Files\NotFoundException;
use Psr\Log\LoggerInterface;

/**
 * SystemFileService provides system-level file access for shared resources.
 *
 * This service reads files like navigation.json and footer.json using the system context
 * (via __groupfolders/{id}/files), which bypasses user-level ACL restrictions.
 *
 * Use case: Users with department-level access (e.g., only /IntraVox/nl/departments/sales)
 * need to see navigation and footer, which are stored at the language root level.
 *
 * SECURITY: This service should ONLY be used for reading specific shared resources
 * (navigation.json, footer.json) and building the page tree for public share access.
 * Never use it for arbitrary file access.
 */
class SystemFileService {
    private const SUPPORTED_LANGUAGES = ['nl', 'en', 'de', 'fr'];
    private const ALLOWED_SHARED_FILES = ['navigation.json', 'footer.json'];
    private const MAX_JSON_SIZE = 5 * 1024 * 1024; // 5 MB
    private const MAX_JSON_DEPTH = 64;

    private IRootFolder $rootFolder;
    private SetupService $setupService;
    private LoggerInterface $logger;

    public function __construct(
        IRootFolder $rootFolder,
        SetupService $setupService,
        LoggerInterface $logger
    ) {
        $this->rootFolder = $rootFolder;
        $this->setupService = $setupService;
        $this->logger = $logger;
    }

    /**
     * Get a shared resource (navigation.json or footer.json) using system context.
     *
     * This method bypasses user ACL and reads directly from the groupfolder.
     * It should only be used as a fallback when the user doesn't have direct access
     * to the language root folder.
     *
     * @param string $language Language code (nl, en, de, fr)
     * @param string $filename File to read (navigation.json or footer.json)
     * @return string|null File content or null if not found
     * @throws \Exception If file is not in the allowed list
     */
    public function getSharedResource(string $language, string $filename): ?string {
        // Security check: only allow specific files
        if (!in_array($filename, self::ALLOWED_SHARED_FILES, true)) {
            $this->logger->warning('[SystemFileService] Attempted to read non-allowed file', [
                'filename' => $filename,
                'allowed' => self::ALLOWED_SHARED_FILES
            ]);
            throw new \Exception('Access denied: file not in allowed list');
        }

        // Validate language
        if (!in_array($language, self::SUPPORTED_LANGUAGES, true)) {
            $language = 'nl'; // Default to Dutch
        }

        try {
            // Get the groupfolder using system context (bypasses user ACL)
            $groupFolder = $this->setupService->getSharedFolder();

            if ($groupFolder === null) {
                $this->logger->error('[SystemFileService] Could not access IntraVox groupfolder');
                return null;
            }

            // Navigate to language folder
            if (!$groupFolder->nodeExists($language)) {
                $this->logger->debug('[SystemFileService] Language folder does not exist', [
                    'language' => $language
                ]);
                return null;
            }

            $languageFolder = $groupFolder->get($language);

            // Get the requested file
            if (!$languageFolder->nodeExists($filename)) {
                $this->logger->debug('[SystemFileService] File does not exist', [
                    'language' => $language,
                    'filename' => $filename
                ]);
                return null;
            }

            $file = $languageFolder->get($filename);
            $content = $file->getContent();

            $this->logger->debug('[SystemFileService] Successfully read shared resource', [
                'language' => $language,
                'filename' => $filename,
                'size' => strlen($content)
            ]);

            return $content;

        } catch (NotFoundException $e) {
            $this->logger->debug('[SystemFileService] Resource not found', [
                'language' => $language,
                'filename' => $filename
            ]);
            return null;
        } catch (\Exception $e) {
            $this->logger->error('[SystemFileService] Error reading shared resource', [
                'language' => $language,
                'filename' => $filename,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Get navigation.json for a language using system context.
     *
     * @param string $language Language code
     * @return array|null Parsed navigation data or null
     */
    public function getNavigation(string $language): ?array {
        $content = $this->getSharedResource($language, 'navigation.json');

        if ($content === null) {
            return null;
        }

        $data = $this->safeJsonDecode($content);

        if ($data === null) {
            $this->logger->warning('[SystemFileService] Invalid JSON in navigation.json', [
                'language' => $language,
            ]);
            return null;
        }

        return $data;
    }

    /**
     * Get footer.json for a language using system context.
     *
     * @param string $language Language code
     * @return array|null Parsed footer data or null
     */
    public function getFooter(string $language): ?array {
        $content = $this->getSharedResource($language, 'footer.json');

        if ($content === null) {
            return null;
        }

        $data = $this->safeJsonDecode($content);

        if ($data === null) {
            $this->logger->warning('[SystemFileService] Invalid JSON in footer.json', [
                'language' => $language,
            ]);
            return null;
        }

        return $data;
    }

    /**
     * Get the page tree for a language using system context.
     *
     * Builds a hierarchical tree of pages from the folder structure,
     * without requiring a user session. Used for public share access.
     *
     * @param string $language Language code (nl, en, de, fr)
     * @return array Hierarchical tree of pages
     */
    public function getPageTree(string $language): array {
        if (!in_array($language, self::SUPPORTED_LANGUAGES, true)) {
            $language = 'nl';
        }

        try {
            $groupFolder = $this->setupService->getSharedFolder();
            if ($groupFolder === null) {
                $this->logger->error('[SystemFileService] Could not access IntraVox groupfolder for page tree');
                return [];
            }

            if (!$groupFolder->nodeExists($language)) {
                return [];
            }

            $languageFolder = $groupFolder->get($language);
            $basePath = $groupFolder->getPath();
            $tree = [];

            // Check for home.json in root
            try {
                $homeFile = $languageFolder->get('home.json');
                $content = $homeFile->getContent();
                $data = $this->safeJsonDecode($content);

                if ($data && isset($data['uniqueId'], $data['title'])) {
                    $tree[] = [
                        'uniqueId' => $data['uniqueId'],
                        'title' => $data['title'],
                        'path' => $language,
                        'language' => $language,
                        'isCurrent' => false,
                        'children' => [],
                    ];
                }
            } catch (NotFoundException $e) {
                // No home page
            }

            // Recursively build tree from subfolders
            $this->buildPageTreeRecursive($languageFolder, $tree, $language, $basePath);

            return $tree;
        } catch (\Exception $e) {
            $this->logger->error('[SystemFileService] Error building page tree', [
                'language' => $language,
                'error' => $e->getMessage(),
            ]);
            return [];
        }
    }

    /**
     * Recursively build the page tree from folder structure.
     */
    private function buildPageTreeRecursive($folder, array &$tree, string $language, string $basePath): void {
        try {
            $items = $folder->getDirectoryListing();
        } catch (\Exception $e) {
            return;
        }

        foreach ($items as $item) {
            if ($item->getType() !== \OCP\Files\FileInfo::TYPE_FOLDER) {
                continue;
            }

            $folderName = $item->getName();

            // Skip special folders
            if (in_array($folderName, ['images', 'files', '.nomedia'])) {
                continue;
            }

            // Skip folders starting with emoji
            if (preg_match('/^[\x{1F300}-\x{1F9FF}]/u', $folderName)) {
                continue;
            }

            try {
                $jsonFile = $item->get($folderName . '.json');
                $content = $jsonFile->getContent();
                $data = $this->safeJsonDecode($content);

                if ($data && isset($data['uniqueId'], $data['title'])) {
                    // Calculate relative path from GroupFolder root
                    $relativePath = str_replace($basePath . '/', '', $item->getPath());

                    $pageNode = [
                        'uniqueId' => $data['uniqueId'],
                        'title' => $data['title'],
                        'path' => $relativePath,
                        'language' => $language,
                        'isCurrent' => false,
                        'children' => [],
                    ];

                    // Recursively get children
                    $this->buildPageTreeRecursive($item, $pageNode['children'], $language, $basePath);

                    $tree[] = $pageNode;
                }
            } catch (\Exception $e) {
                // Folder doesn't contain a valid page, continue
            }
        }
    }

    /**
     * Check if a language folder exists in the groupfolder.
     *
     * @param string $language Language code
     * @return bool True if folder exists
     */
    public function languageFolderExists(string $language): bool {
        if (!in_array($language, self::SUPPORTED_LANGUAGES, true)) {
            return false;
        }

        try {
            $groupFolder = $this->setupService->getSharedFolder();
            return $groupFolder !== null && $groupFolder->nodeExists($language);
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Get news pages for a public share context.
     *
     * Uses system-level GroupFolder access (no user session needed).
     * Finds pages recursively in the source folder, filters by share scope,
     * and builds news items with share-aware image URLs.
     *
     * @param string $language Language code (nl, en, de, fr)
     * @param string|null $sourcePageId Source page unique ID (folder containing news)
     * @param string $shareScopePath Share scope relative path (e.g. "nl/afdelingen")
     * @param string $shareToken Share token for image URL rewriting
     * @param int $limit Maximum number of items
     * @param string $sortBy Sort field (modified, title)
     * @param string $sortOrder Sort direction (asc, desc)
     * @return array Result with items, total
     */
    public function getNewsPagesForShare(
        string $language,
        ?string $sourcePageId,
        ?string $sourcePath,
        string $shareScopePath,
        string $shareToken,
        int $limit = 5,
        string $sortBy = 'modified',
        string $sortOrder = 'desc'
    ): array {
        if (!in_array($language, self::SUPPORTED_LANGUAGES, true)) {
            $language = 'nl';
        }

        try {
            $groupFolder = $this->setupService->getSharedFolder();
            if ($groupFolder === null) {
                $this->logger->error('[SystemFileService] Could not access IntraVox groupfolder for news');
                return ['items' => [], 'total' => 0];
            }

            if (!$groupFolder->nodeExists($language)) {
                return ['items' => [], 'total' => 0];
            }

            $languageFolder = $groupFolder->get($language);
            $basePath = $groupFolder->getPath();
            $sourceFolder = $languageFolder;

            // If sourcePageId is provided, find that page's folder
            if (!empty($sourcePageId)) {
                $found = $this->findPageFolderByUniqueId($languageFolder, $sourcePageId);
                if ($found !== null) {
                    $sourceFolder = $found;
                } else {
                    $this->logger->debug('[SystemFileService] News source page not found', [
                        'sourcePageId' => $sourcePageId,
                        'language' => $language,
                    ]);
                    return ['items' => [], 'total' => 0];
                }
            } elseif (!empty($sourcePath)) {
                // Legacy: sourcePath is a relative folder name like "news"
                $cleanPath = trim($sourcePath, '/');
                if ($languageFolder->nodeExists($cleanPath)) {
                    $node = $languageFolder->get($cleanPath);
                    if ($node instanceof \OCP\Files\Folder) {
                        $sourceFolder = $node;
                    }
                } else {
                    $this->logger->debug('[SystemFileService] News source path not found', [
                        'sourcePath' => $sourcePath,
                        'language' => $language,
                    ]);
                    return ['items' => [], 'total' => 0];
                }
            }

            // Collect news pages recursively
            $pages = [];
            $this->findNewsPagesRecursive($sourceFolder, $pages, $language, $basePath, $shareScopePath, $shareToken);

            $total = count($pages);

            // Sort
            usort($pages, function ($a, $b) use ($sortBy, $sortOrder) {
                if ($sortBy === 'title') {
                    $cmp = strcasecmp($a['title'] ?? '', $b['title'] ?? '');
                } else {
                    $cmp = ($a['modified'] ?? 0) <=> ($b['modified'] ?? 0);
                }
                return $sortOrder === 'desc' ? -$cmp : $cmp;
            });

            // Limit
            $pages = array_slice($pages, 0, $limit);

            return ['items' => $pages, 'total' => $total];

        } catch (\Exception $e) {
            $this->logger->error('[SystemFileService] Error getting news for share', [
                'language' => $language,
                'error' => $e->getMessage(),
            ]);
            return ['items' => [], 'total' => 0];
        }
    }

    /**
     * Find a page's parent folder by uniqueId.
     *
     * @param \OCP\Files\Folder $folder Folder to search in
     * @param string $uniqueId Page unique ID
     * @return \OCP\Files\Folder|null The folder containing the page, or null
     */
    private function findPageFolderByUniqueId($folder, string $uniqueId): ?\OCP\Files\Folder {
        try {
            $items = $folder->getDirectoryListing();
        } catch (\Exception $e) {
            return null;
        }

        foreach ($items as $item) {
            if ($item->getType() !== \OCP\Files\FileInfo::TYPE_FOLDER) {
                continue;
            }

            $folderName = $item->getName();

            // Skip special folders
            if (in_array($folderName, ['images', 'files', '_media', '_resources', '.nomedia'])) {
                continue;
            }

            try {
                $jsonFile = $item->get($folderName . '.json');
                $content = $jsonFile->getContent();
                $data = $this->safeJsonDecode($content);
                if ($data !== null) {
                    if (isset($data['uniqueId']) && $data['uniqueId'] === $uniqueId) {
                        return $item;
                    }
                }
            } catch (\Exception $e) {
                // No valid page file in this folder
            }

            // Recurse into subfolders
            $found = $this->findPageFolderByUniqueId($item, $uniqueId);
            if ($found !== null) {
                return $found;
            }
        }

        return null;
    }

    /**
     * Recursively find news pages in a folder for public share context.
     */
    private function findNewsPagesRecursive(
        $folder,
        array &$pages,
        string $language,
        string $basePath,
        string $shareScopePath,
        string $shareToken
    ): void {
        try {
            $items = $folder->getDirectoryListing();
        } catch (\Exception $e) {
            return;
        }

        foreach ($items as $item) {
            if ($item->getType() !== \OCP\Files\FileInfo::TYPE_FOLDER) {
                continue;
            }

            $folderName = $item->getName();

            // Skip special folders
            if (in_array($folderName, ['_media', '_resources', 'images', 'files', '.nomedia'])) {
                continue;
            }

            try {
                $jsonFile = $item->get($folderName . '.json');
                $content = $jsonFile->getContent();
                $data = $this->safeJsonDecode($content);

                if ($data && isset($data['uniqueId'], $data['title'])) {
                    // Check if this page is within the share scope
                    // Apply NFC normalization to prevent unicode bypass
                    $relativePath = str_replace($basePath . '/', '', $item->getPath());
                    if (function_exists('normalizer_normalize')) {
                        $relativePath = \Normalizer::normalize($relativePath, \Normalizer::FORM_C) ?: $relativePath;
                    }
                    $shareScopeNormalized = rtrim($shareScopePath, '/');
                    if (function_exists('normalizer_normalize')) {
                        $shareScopeNormalized = \Normalizer::normalize($shareScopeNormalized, \Normalizer::FORM_C) ?: $shareScopeNormalized;
                    }

                    $isInScope = ($shareScopeNormalized === '' || $shareScopeNormalized === $language)
                        || str_starts_with($relativePath, $shareScopeNormalized . '/')
                        || $relativePath === $shareScopeNormalized;

                    if (!$isInScope) {
                        continue;
                    }

                    // Extract excerpt from first text widget
                    $excerpt = $this->getPageExcerpt($data, 150);

                    // Find first image and build share-aware URL
                    $imageData = $this->getPageFirstImage($data);
                    $imagePath = null;
                    if ($imageData) {
                        $imageSrc = $imageData['src'];
                        $mediaFolder = $imageData['mediaFolder'] ?? 'page';
                        if ($mediaFolder === 'resources') {
                            $imagePath = '/apps/intravox/api/share/' . $shareToken . '/resources/media/' . $imageSrc;
                        } else {
                            $imagePath = '/apps/intravox/api/share/' . $shareToken . '/page/' . $data['uniqueId'] . '/media/' . $imageSrc;
                        }
                    }

                    $modified = $jsonFile->getMTime();

                    $pages[] = [
                        'uniqueId' => $data['uniqueId'],
                        'title' => $data['title'],
                        'excerpt' => $excerpt,
                        'image' => $imageData ? $imageData['src'] : null,
                        'imagePath' => $imagePath,
                        'modified' => $modified,
                        'modifiedFormatted' => date('d M Y', $modified),
                    ];
                }
            } catch (\Exception $e) {
                // Folder doesn't contain a valid page, continue
            }

            // Recurse into subfolders
            $this->findNewsPagesRecursive($item, $pages, $language, $basePath, $shareScopePath, $shareToken);
        }
    }

    /**
     * Extract an excerpt from page content (first text widget).
     */
    private function getPageExcerpt(array $pageData, int $length = 150): string {
        if (!isset($pageData['layout']['rows']) || !is_array($pageData['layout']['rows'])) {
            return '';
        }

        foreach ($pageData['layout']['rows'] as $row) {
            if (!isset($row['widgets']) || !is_array($row['widgets'])) {
                continue;
            }

            foreach ($row['widgets'] as $widget) {
                if (($widget['type'] ?? '') === 'text' && !empty($widget['content'])) {
                    $text = strip_tags($widget['content']);
                    $text = preg_replace('/\s+/', ' ', $text);
                    $text = trim($text);

                    if (!empty($text)) {
                        if (mb_strlen($text) > $length) {
                            $text = mb_substr($text, 0, $length);
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
     * Get the first image from a page's layout.
     *
     * @return array|null ['src' => filename, 'mediaFolder' => 'page'|'resources'] or null
     */
    private function getPageFirstImage(array $pageData): ?array {
        if (!isset($pageData['layout']['rows']) || !is_array($pageData['layout']['rows'])) {
            return null;
        }

        foreach ($pageData['layout']['rows'] as $row) {
            if (!isset($row['widgets']) || !is_array($row['widgets'])) {
                continue;
            }

            foreach ($row['widgets'] as $widget) {
                if (($widget['type'] ?? '') === 'image' && !empty($widget['src'])) {
                    return [
                        'src' => $widget['src'],
                        'mediaFolder' => $widget['mediaFolder'] ?? 'page',
                    ];
                }
            }
        }

        return null;
    }

    /**
     * Safely decode JSON with size and depth limits.
     *
     * @param string|null $content Raw JSON string
     * @return array|null Decoded data or null on failure
     */
    private function safeJsonDecode(?string $content): ?array {
        if ($content === null || $content === false || $content === '') {
            return null;
        }

        if (strlen($content) > self::MAX_JSON_SIZE) {
            $this->logger->warning('[SystemFileService] JSON content exceeds size limit', [
                'size' => strlen($content),
                'limit' => self::MAX_JSON_SIZE,
            ]);
            return null;
        }

        $data = json_decode($content, true, self::MAX_JSON_DEPTH);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return null;
        }

        return is_array($data) ? $data : null;
    }

}
