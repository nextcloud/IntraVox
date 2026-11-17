<?php
declare(strict_types=1);

namespace OCA\IntraVox\Service\Locator;

use OCA\IntraVox\Service\PageIndexService;
use OCA\IntraVox\Service\Path\PagePathHelper;
use OCP\Files\File;
use OCP\Files\Folder;
use OCP\Files\NotFoundException;
use Psr\Log\LoggerInterface;

/**
 * The page-location engine, extracted from PageService (service split,
 * PR-15). This is the shared machinery every domain leans on: find a page
 * by uniqueId or legacy slug, in one language folder or across all of
 * them, index-accelerated but never index-trusting — plus the two
 * request-level filesystem caches the walkers share.
 *
 * The IntraVox ROOT folder is always an explicit parameter. Resolving it
 * (user mount, ACLs, language fallback) stays in PageService, whose
 * protected getIntraVoxFolder()/getLanguageFolder()/getReadLanguageFolder()
 * are the unit tests' seams; this class never resolves a folder it was not
 * handed. That keeps it free of user/session state: its only dependencies
 * are the page index and a logger.
 *
 * Contract carried over unchanged: locate* answers WHERE AN EXISTING PAGE
 * LIVES, never where a new page is created, and callers that write remain
 * responsible for permission checks on the nodes they get back (#90).
 */
class PageLocator {
    private PageIndexService $pageIndexService;
    private LoggerInterface $logger;

    /** @var array Request-level cache for directory listings */
    private array $directoryListingCache = [];

    /** @var array Request-level cache for file contents */
    private array $fileContentCache = [];

    public function __construct(
        PageIndexService $pageIndexService,
        LoggerInterface $logger
    ) {
        $this->pageIndexService = $pageIndexService;
        $this->logger = $logger;
    }

    /**
     * Get cached directory listing for a folder
     */
    public function cachedDirectoryListing(Folder $folder): array {
        $path = $folder->getPath();
        if (!isset($this->directoryListingCache[$path])) {
            $this->directoryListingCache[$path] = $folder->getDirectoryListing();
        }
        return $this->directoryListingCache[$path];
    }

    /**
     * Get cached file content (prevents repeated reads of same file within request)
     */
    public function cachedFileContent(File $file): string {
        $path = $file->getPath();
        if (!isset($this->fileContentCache[$path])) {
            $this->fileContentCache[$path] = $file->getContent();
        }
        return $this->fileContentCache[$path];
    }

    /**
     * Drop both request caches. Called from PageService::clearCache() on
     * every mutation, so walkers keep seeing a truthful filesystem view.
     */
    public function clearRequestCaches(): void {
        $this->directoryListingCache = [];
        $this->fileContentCache = [];
    }

    /**
     * Recursively find a page by uniqueId within $folder. $languageFolder
     * tracks the search root for isHome detection.
     */
    public function findPageByUniqueId($folder, string $uniqueId, $languageFolder = null): ?array {
        // Track the language folder for isHome detection
        if ($languageFolder === null) {
            $languageFolder = $folder;
        }

        // Check for home.json first (most common case for homepage)
        try {
            $homeFile = $folder->get('home.json');
            $content = $homeFile->getContent();
            $data = json_decode($content, true);
            if ($data && isset($data['uniqueId'])
                && ($data['uniqueId'] === $uniqueId
                    || ($uniqueId === 'page-home-en' && $folder->getPath() === $languageFolder->getPath()))) {
                return ['file' => $homeFile, 'folder' => $folder, 'isHome' => true];
            }
        } catch (NotFoundException $e) {
            // home.json doesn't exist here, continue searching
        }

        // Get directory listing ONCE (cached) and separate files from folders
        $isLanguageRoot = ($folder->getPath() === $languageFolder->getPath());
        $items = $this->cachedDirectoryListing($folder);
        $subfolderItems = [];

        // FIRST: Check all JSON files in current folder
        foreach ($items as $item) {
            $itemName = $item->getName();

            // Collect subfolders for later recursive search
            if ($item->getType() === \OCP\Files\FileInfo::TYPE_FOLDER) {
                // Skip media folders and special folders
                if (!PagePathHelper::isInfrastructureFolder($itemName)) {
                    $subfolderItems[] = $item;
                }
                continue;
            }

            // Skip navigation.json and footer.json only in the language root folder
            // (these are config files there, not pages). In subfolders they can be page files.
            $skipFile = ($itemName === 'home.json'); // Always skip home.json, checked above
            if ($isLanguageRoot && ($itemName === 'navigation.json' || $itemName === 'footer.json' || $itemName === 'homepage.json')) {
                $skipFile = true;
            }

            if (substr($itemName, -5) === '.json' && !$skipFile) {
                try {
                    $content = $this->cachedFileContent($item);
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
    public function findPageById($folder, string $id): ?array {
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
        foreach ($this->cachedDirectoryListing($folder) as $item) {
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
     * Find a file by its ID within a folder
     */
    public function findFileByIdInFolder(Folder $folder, int $fileId): ?File {
        try {
            $files = $this->cachedDirectoryListing($folder);
            foreach ($files as $item) {
                if ($item->getId() === $fileId && $item instanceof File) {
                    return $item;
                }
                if ($item instanceof Folder) {
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
     * Locate a page by uniqueId across every language folder that exists on
     * disk, starting with $primaryFolder. Index-first: one query instead of
     * a full walk, but a hit is always verified against disk and anything
     * that does not check out falls through to the walk — a stale index
     * costs performance, never correctness (#90).
     */
    public function locatePageAnyLanguage(callable $root, Folder $primaryFolder, string $uniqueId): ?array {
        $indexed = $this->locateViaIndex($root, $uniqueId, $primaryFolder);
        if ($indexed !== null) {
            return $indexed;
        }

        // A MISS still costs the full scan, and that is deliberate. The index
        // cannot prove a page does not exist — only that it does not know of
        // one — so treating "not in the index" as "not found" would turn a
        // stale index from a slowdown into pages that vanish.
        return $this->locateAcrossLanguages(
            $root,
            $primaryFolder,
            fn(Folder $f) => $this->findPageByUniqueId($f, $uniqueId)
        );
    }

    /**
     * Same cross-language walk for a legacy slug id (e.g. "about"), so a slug
     * link resolves wherever the page lives — matching uniqueId links.
     */
    public function locatePageBySlugAnyLanguage(callable $root, Folder $primaryFolder, string $id): ?array {
        return $this->locateAcrossLanguages(
            $root,
            $primaryFolder,
            fn(Folder $f) => $this->findPageById($f, $id)
        );
    }

    /**
     * Run $find against $primaryFolder first, then against every other language
     * folder on disk. Shared by the uniqueId and slug locators.
     *
     * @param callable(Folder): ?array $find
     */
    public function locateAcrossLanguages(callable $root, Folder $primaryFolder, callable $find): ?array {
        $result = $find($primaryFolder);
        if ($result !== null) {
            return $result;
        }

        // Resolve the root only now: the primary folder answers most lookups,
        // and the pre-split code never touched the root on that path either
        // (unit tests build services without a resolvable root on purpose).
        $rootFolder = $root();

        // Scan the remaining language folders that actually exist on disk,
        // rather than an opt-in list, so content in any language (e.g. 'da')
        // stays reachable. Skip the folder we just searched — comparing paths
        // rather than language codes, so the folder that was really searched is
        // the one that is really skipped.
        $searchedPath = $primaryFolder->getPath();

        foreach ($this->cachedDirectoryListing($rootFolder) as $item) {
            if ($item->getType() !== \OCP\Files\FileInfo::TYPE_FOLDER
                || !($item instanceof Folder)) {
                continue;
            }
            // Language folders are two/three-letter base codes.
            if (!preg_match('/^[a-z]{2,3}$/', $item->getName())
                || $item->getPath() === $searchedPath) {
                continue;
            }
            $result = $find($item);
            if ($result !== null) {
                return $result;
            }
        }

        return null;
    }

    /**
     * Resolve a uniqueId through the page index, verified against disk.
     *
     * Returns the same shape as findPageByUniqueId() — ['file', 'folder',
     * 'isHome'] — so callers cannot tell which route answered.
     *
     * @return array|null null when the index does not know the id, or when
     *   what it points at no longer matches the file on disk.
     */
    public function locateViaIndex(callable $root, string $uniqueId, Folder $primaryFolder): ?array {
        try {
            // Root resolution sits INSIDE the try on purpose: like everything
            // else on the index path, a failure here degrades to the walk.
            $rootFolder = $root();
            $row = $this->pageIndexService->findByUniqueId(
                $uniqueId,
                $this->languageOfFolder($rootFolder, $primaryFolder)
            );
            if ($row === null || empty($row['path'])) {
                return null;
            }

            $folder = $this->folderFromAbsolutePath($rootFolder, (string)$row['path']);
            if ($folder === null) {
                return null;
            }

            // The index stores what findPageByUniqueId() calls 'folder': the
            // page's OWN {slug}/ folder. The page JSON is {slug}.json, which
            // sits BESIDE that folder in its parent — not inside it. The home
            // page is the exception: its folder IS the language folder and its
            // file (home.json) really is inside.
            $name = $folder->getName();
            $candidates = [];
            try {
                $candidates[] = [$folder->getParent(), $name . '.json', false];
            } catch (\Throwable $e) {
                // No reachable parent (mount root); the in-folder forms below
                // still apply.
            }
            $candidates[] = [$folder, 'home.json', true];
            // Legacy/flat layouts keep {slug}.json inside its own folder.
            $candidates[] = [$folder, $name . '.json', false];

            foreach ($candidates as [$container, $fileName, $isHome]) {
                if (!($container instanceof Folder)) {
                    continue;
                }
                try {
                    $file = $container->get($fileName);
                } catch (NotFoundException $e) {
                    continue;
                }
                if (!($file instanceof File)) {
                    continue;
                }

                // Verify: the file must really carry this uniqueId. This is
                // what makes a stale index harmless — a page that moved, was
                // deleted, or was overwritten simply fails the check and the
                // caller falls back to the filesystem walk.
                $data = json_decode($this->cachedFileContent($file), true);
                if (!is_array($data) || ($data['uniqueId'] ?? null) !== $uniqueId) {
                    continue;
                }

                return [
                    'file' => $file,
                    'folder' => $folder,
                    'isHome' => $isHome,
                ];
            }

            return null;
        } catch (\Throwable $e) {
            // Never let an index problem break a read.
            $this->logger->warning('[PageLocator] index lookup failed, falling back to scan', [
                'uniqueId' => $uniqueId,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Which language content folder does $folder sit in?
     *
     * Walks up from $folder to the IntraVox root and returns the top-level
     * segment when it is a language code.
     *
     * @return string|null the language code, or null when $folder is outside
     *   the IntraVox tree or is the tree root itself.
     */
    public function languageOfFolder(Folder $root, Folder $folder): ?string {
        $basePath = rtrim($root->getPath(), '/');
        $path = rtrim($folder->getPath(), '/');
        if ($path === $basePath || strpos($path, $basePath . '/') !== 0) {
            return null;
        }
        $rest = substr($path, strlen($basePath) + 1);
        $first = explode('/', $rest)[0];
        return preg_match('/^[a-z]{2,3}$/', $first) ? $first : null;
    }

    /**
     * Resolve an absolute Nextcloud path (as stored in the index) to a Folder
     * inside the caller's IntraVox tree.
     *
     * Deliberately resolves relative to the handed-in root (the user's own
     * mounted IntraVox folder), so the index can never hand a user a folder
     * their mount does not grant them.
     */
    public function folderFromAbsolutePath(Folder $root, string $absolutePath): ?Folder {
        $relative = $this->indexPathToRelative($root, $absolutePath);
        if ($relative === null) {
            return null;
        }
        if ($relative === '') {
            return $root;
        }

        try {
            $node = $root->get($relative);
        } catch (NotFoundException $e) {
            return null;
        }
        return $node instanceof Folder ? $node : null;
    }

    /**
     * Normalise a stored index path to a path relative to the IntraVox root.
     *
     * The index is shared by every user, but a Nextcloud path is per-user:
     * the same page is /admin/files/IntraVox/en/about for one account and
     * /Rik/files/IntraVox/en/about for another. Rows written before the
     * relative-path fix still hold a per-user absolute path, so both forms
     * are accepted: anything up to and including an `IntraVox/` segment is
     * stripped, and what remains is resolved against the caller's own mount.
     *
     * @return string|null relative path ('' for the root), or null when the
     *   path does not sit inside an IntraVox tree at all
     */
    public function indexPathToRelative(Folder $root, string $storedPath): ?string {
        $path = trim($storedPath, '/');
        if ($path === '') {
            return null;
        }

        $base = rtrim($root->getPath(), '/');
        $basePath = trim($base, '/');

        // Fast path: written by this same user (or already relative).
        if ($basePath !== '' && $path === $basePath) {
            return '';
        }
        if ($basePath !== '' && strpos($path, $basePath . '/') === 0) {
            return substr($path, strlen($basePath) + 1);
        }

        // Another user's absolute path, or a legacy row: keep everything after
        // the LAST 'IntraVox' segment, which is the app-root marker in every
        // form of the path.
        $segments = explode('/', $path);
        $rootIndex = null;
        foreach ($segments as $i => $segment) {
            if ($segment === 'IntraVox') {
                $rootIndex = $i;
            }
        }
        if ($rootIndex === null) {
            // No IntraVox segment. Since 2.0 the index stores paths RELATIVE to
            // the app root ('en/about'), which is exactly this shape — so treat
            // it as already relative rather than refusing it. A path that is
            // neither relative nor inside an IntraVox tree simply will not
            // resolve against the caller's mount, which is the safe outcome.
            return $path;
        }

        return implode('/', array_slice($segments, $rootIndex + 1));
    }

    /**
     * $parent->get($name) as a Folder, or null when it is missing or is a file.
     * Saves the repeated try/catch around optional `_media` / `_resources`
     * lookups on paths that treat "absent" as an ordinary outcome.
     */
    public function folderOrNull(?Folder $parent, string $name): ?Folder {
        if ($parent === null) {
            return null;
        }
        try {
            $node = $parent->get($name);
            return $node instanceof Folder ? $node : null;
        } catch (NotFoundException $e) {
            return null;
        }
    }

    /**
     * Path of $folder relative to the IntraVox root.
     */
    public function relativePathFromRoot(Folder $root, $folder): string {
        $intraVoxPath = $root->getPath();
        $folderPath = $folder->getPath();

        // Remove IntraVox base path
        return str_replace($intraVoxPath . '/', '', $folderPath);
    }
}
