<?php
declare(strict_types=1);

namespace OCA\IntraVox\Service;

use OCP\Files\Folder;
use OCP\Files\IRootFolder;
use OCP\Files\NotFoundException;
use OCP\ICache;
use OCP\ICacheFactory;
use Psr\Log\LoggerInterface;
use OCA\IntraVox\Service\Path\PagePathHelper;

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
    private const ALLOWED_SHARED_FILES = ['navigation.json', 'footer.json', 'homepage.json'];
    private const MAX_JSON_SIZE = 5 * 1024 * 1024; // 5 MB
    private const MAX_JSON_DEPTH = 64;
    private const FALLBACK_LANGUAGE = 'en';

    /** @var int Cache TTL for the public page tree, matching PageService. */
    private const PAGE_TREE_CACHE_TTL = 300; // 5 minutes

    /** @var array Per-request cache: the same tree is often asked for twice. */
    private static array $pageTreeCache = [];

    /**
     * Drop the in-process tree cache. The distributed half shares PageService's
     * 'intravox-pages' namespace, so its clear() already covers that; this
     * handles the static copy inside a single request.
     */
    public static function clearStaticTreeCache(): void {
        self::$pageTreeCache = [];
    }

    private IRootFolder $rootFolder;
    private SetupService $setupService;
    private LoggerInterface $logger;
    private LanguageService $languageService;
    private ?ICache $distributedCache = null;

    public function __construct(
        IRootFolder $rootFolder,
        SetupService $setupService,
        LoggerInterface $logger,
        LanguageService $languageService,
        ICacheFactory $cacheFactory
    ) {
        $this->rootFolder = $rootFolder;
        $this->setupService = $setupService;
        $this->logger = $logger;
        $this->languageService = $languageService;

        if ($cacheFactory->isAvailable()) {
            $this->distributedCache = $cacheFactory->createDistributed('intravox-pages');
        }
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

        // Validate language: must be enabled by admin, otherwise fall back to English.
        if (!$this->languageService->isLanguageEnabled($language)) {
            $language = self::FALLBACK_LANGUAGE;
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
     * Whether the ACL-bypassing fallback is legitimate for this user and file.
     *
     * getSharedResource() reads in system context and deliberately ignores
     * user ACLs, so that a user with department-only access (no read on the
     * language root) still gets a menu and footer. That fallback is correct
     * for exactly one situation: the user cannot reach the PARENT FOLDER.
     *
     * It is wrong for a second situation that used to arrive as the very same
     * exception: an administrator put an explicit deny on the FILE itself.
     * Honouring the fallback there serves content the admin just forbade
     * (issue #112) — navigation.json stayed visible, with its page titles,
     * for a user configured to have no access to it at all.
     *
     * The two are told apart from the USER's view, not the system view: if the
     * user can list the language folder, they were not shut out at the folder
     * level, so a file they cannot read there is a deliberate deny and must
     * stay denied. Only when the language folder itself is unreachable does
     * the department-only case apply and the fallback stand.
     *
     * Fails OPEN on an unexpected error, which preserves the pre-existing
     * behaviour for every case this check was not written for: navigation is
     * infrastructure, and a broken check must not blank out everyone's menu.
     *
     * @param string $userId   The user the request is for.
     * @param string $language Language code (already validated by the caller).
     * @param string $filename One of ALLOWED_SHARED_FILES.
     * @return bool True when the system-context read may proceed.
     */
    public function mayUseSystemFallback(string $userId, string $language, string $filename): bool {
        if (!in_array($filename, self::ALLOWED_SHARED_FILES, true)) {
            return false;
        }

        try {
            $userFolder = $this->rootFolder->getUserFolder($userId);
            $languageFolder = $userFolder->get('IntraVox/' . $language);

            // get() is typed as Node; only a Folder can be asked what it
            // contains. Anything else in that path means the install is not
            // shaped the way IntraVox expects, which is not a
            // department-only user -- so it is not a case for the bypass.
            if (!$languageFolder instanceof Folder) {
                return false;
            }

            // The user CAN see the language folder. Whether the file is
            // missing or denied, the department-only rationale does not
            // apply here, so the fallback must not fire.
            if (!$languageFolder->nodeExists($filename)) {
                $this->logger->debug('[SystemFileService] Denying system fallback: user can read the language folder', [
                    'user' => $userId,
                    'language' => $language,
                    'filename' => $filename,
                ]);
                return false;
            }

            // Present and readable through the user's own view: the caller
            // never needed the fallback, and does not need it now.
            return false;
        } catch (NotFoundException $e) {
            // Language folder unreachable for this user — the department-only
            // case the fallback exists for.
            return true;
        } catch (\Throwable $e) {
            $this->logger->warning('[SystemFileService] Fallback eligibility check failed; allowing fallback', [
                'user' => $userId,
                'language' => $language,
                'filename' => $filename,
                'error' => $e->getMessage(),
            ]);
            return true;
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
        if (!$this->languageService->isLanguageEnabled($language)) {
            $language = self::FALLBACK_LANGUAGE;
        }

        // Building this tree costs one file read per page. Without a cache a
        // shared intranet of a few thousand pages would rebuild it on every
        // visit — and since 2.2.0 the structure panel loads it on every page.
        $cacheKey = 'system-tree-' . $language;
        $now = time();

        if (isset(self::$pageTreeCache[$cacheKey])
            && ($now - self::$pageTreeCache[$cacheKey]['time']) < self::PAGE_TREE_CACHE_TTL) {
            return self::$pageTreeCache[$cacheKey]['tree'];
        }

        if ($this->distributedCache !== null) {
            $cached = $this->distributedCache->get($cacheKey);
            if ($cached !== null) {
                $decoded = json_decode($cached, true);
                if (is_array($decoded)) {
                    self::$pageTreeCache[$cacheKey] = ['tree' => $decoded, 'time' => $now];
                    return $decoded;
                }
            }
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
            $homeData = null;
            $homeFileId = null;
            try {
                $homeFile = $languageFolder->get('home.json');
                $content = $homeFile->getContent();
                $homeData = $this->safeJsonDecode($content);
                if ($homeFile instanceof \OCP\Files\File) {
                    $homeFileId = $homeFile->getId();
                }
            } catch (NotFoundException $e) {
                // home.json not found, try page-*.json fallback
                try {
                    foreach ($languageFolder->getDirectoryListing() as $node) {
                        $name = $node->getName();
                        if (str_starts_with($name, 'page-') && str_ends_with($name, '.json')) {
                            $content = $node->getContent();
                            $homeData = $this->safeJsonDecode($content);
                            if ($homeData && isset($homeData['uniqueId'])) {
                                if ($node instanceof \OCP\Files\File) {
                                    $homeFileId = $node->getId();
                                }
                                break;
                            }
                        }
                    }
                } catch (\Exception $e2) {
                    // Fall through
                }
            }

            if ($homeData && isset($homeData['uniqueId'], $homeData['title'])) {
                $tree[] = [
                    'uniqueId' => $homeData['uniqueId'],
                    'title' => $homeData['title'],
                    // status + fileId let the public tree apply the same
                    // scheduled-publish visibility gate as the rest of the app.
                    'status' => $homeData['status'] ?? 'published',
                    'fileId' => $homeFileId,
                    'path' => $language,
                    'language' => $language,
                    'isCurrent' => false,
                    'children' => [],
                ];
            }

            // Recursively build tree from subfolders
            $this->buildPageTreeRecursive($languageFolder, $tree, $language, $basePath);

            // Configurable homepage: float the pointer target to the front so a
            // public share shows the configured homepage first (issue: homepage).
            try {
                if ($languageFolder->nodeExists('homepage.json')) {
                    $ptr = $this->safeJsonDecode($languageFolder->get('homepage.json')->getContent());
                    $pointer = is_array($ptr) ? ($ptr['homepageUniqueId'] ?? null) : null;
                    if (is_string($pointer) && $pointer !== '' && $pointer !== 'home') {
                        foreach ($tree as $i => $node) {
                            if (($node['uniqueId'] ?? null) === $pointer && $i !== 0) {
                                $picked = array_splice($tree, $i, 1);
                                array_unshift($tree, $picked[0]);
                                break;
                            }
                        }
                    }
                }
            } catch (\Exception $e) {
                // Non-fatal: fall back to home.json-first ordering.
            }

            self::$pageTreeCache[$cacheKey] = ['tree' => $tree, 'time' => $now];
            if ($this->distributedCache !== null) {
                $this->distributedCache->set($cacheKey, json_encode($tree), self::PAGE_TREE_CACHE_TTL);
            }

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
            if (PagePathHelper::isInfrastructureFolder($folderName)) {
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
                        // status + fileId let the public tree apply the same
                        // scheduled-publish visibility gate as the rest of the app.
                        'status' => $data['status'] ?? 'published',
                        'fileId' => ($jsonFile instanceof \OCP\Files\File) ? $jsonFile->getId() : null,
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
        if (!$this->languageService->isLanguageEnabled($language)) {
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
        if (!$this->languageService->isLanguageEnabled($language)) {
            $language = self::FALLBACK_LANGUAGE;
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
            if (PagePathHelper::isInfrastructureFolder($folderName)) {
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
            if (PagePathHelper::isInfrastructureFolder($folderName)) {
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

                    // Never expose a draft through a public share: there is no
                    // editor here to reveal it. (Publish/expiration dates are
                    // enforced by the share endpoints in ApiController, which do
                    // have PageService; this keeps SystemFileService free of that
                    // dependency — it is constructed by a manual factory in
                    // Application.php.)
                    if (($data['status'] ?? 'published') === 'draft') {
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
                        // Carried so the caller can apply the publication gate:
                        // status is the manual flag, fileId is what the
                        // publish/expiration dates in MetaVox hang off. Without
                        // these two, the gate in ApiController silently passes
                        // everything (READER-GATE).
                        'status' => $data['status'] ?? 'published',
                        'fileId' => $jsonFile->getId(),
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
