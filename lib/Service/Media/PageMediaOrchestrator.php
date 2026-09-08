<?php

declare(strict_types=1);

namespace OCA\IntraVox\Service\Media;

use OCA\IntraVox\Exception\PageNotFoundException;
use OCA\IntraVox\Service\Folder\FolderContext;
use OCA\IntraVox\Service\Locator\PageLocator;
use OCA\IntraVox\Service\Cache\PageCacheService;
use OCA\IntraVox\Service\Sanitize\MediaSanitizer;
use OCA\IntraVox\Service\Util\PageIdUtils;
use OCP\Files\FileInfo;
use OCP\Files\Folder;
use OCP\Files\Node;
use OCP\Files\NotFoundException;

/**
 * The MEDIA orchestration domain, carved out of PageService: uploading, reading,
 * listing and existence-checking of page media + the _resources library, all
 * resolving the page's OWN language folder across languages (#92) before touching
 * the media engine.
 *
 * The low-level media ops (validate/write/list/stream/mediaExists/mediaFolderFor…)
 * already live in PageMediaService, the engine this orchestrates. Folder concerns
 * come from the injected FolderContext; the cache, locator, id utils and media
 * sanitizer are real collaborators. Page lookup stays page-lookup-bound and comes
 * in per call as $this-bound closures (findPageByUniqueId / findPageById), as does
 * clearCache — so PageService's seam-overriding test subclasses keep intercepting.
 *
 * NEVER touches getPage/createPage or the #70 permission recompute. Behaviour is
 * byte-identical to the former PageService methods: PageServiceMediaLanguageTest
 * and PageServiceGetMediaTest pin it.
 */
final class PageMediaOrchestrator {
    public function __construct(
        private PageMediaService $media,
        private PageCacheService $cache,
        private FolderContext $folders,
        private PageLocator $locator,
        private PageIdUtils $idUtils,
        private MediaSanitizer $mediaSanitizer,
    ) {
    }

    /**
     * Upload media (image or video) for a specific page.
     *
     * @param \Closure(\OCP\Files\Folder, string): ?array $findPageByUniqueId
     * @param \Closure(\OCP\Files\Folder, string): ?array $findPageById
     * @param \Closure(string): void $clearCache
     */
    public function uploadMedia(
        string $pageId,
        array $file,
        \Closure $findPageByUniqueId,
        \Closure $findPageById,
        \Closure $clearCache
    ): string {
        // Order matters and is preserved from before the split: the $_FILES
        // shape check runs first, then the id is sanitized (it can reject an
        // id too), then the rest of the upload validation.
        $this->media->assertUploadShape($file);

        $pageId = $this->idUtils->sanitizeId($pageId);

        $validated = $this->media->validateUpload($file);

        // Sanitize filename with prefix based on type
        $filename = $this->media->generatedMediaFilename($file['name'], $validated['mimeType']);

        // Media belongs to the page, so resolve the page across every language
        // folder and upload into the language it actually lives in (issue #92).
        $located = $this->locatePageForMedia($pageId, $findPageByUniqueId, $findPageById);
        if ($located === null) {
            throw new PageNotFoundException('Page not found: ' . $pageId);
        }

        // Home media is in root/_media/, other pages in their own folder.
        $hostFolder = $this->mediaHostFolder($located);
        if ($hostFolder === null) {
            throw new PageNotFoundException('Page not found: ' . $pageId);
        }
        $mediaFolder = $this->media->mediaFolderFor($hostFolder);

        $this->media->writeMediaFile($mediaFolder, $filename, $validated['content'], false);

        // Invalidate the per-page content cache so the next getPage()
        // includes the freshly uploaded asset. Without this a save-then-
        // navigate-back sequence served the cached page-render where the
        // media reference was still missing — particularly visible on
        // image widgets that just got their src bumped.
        $clearCache($pageId);

        return $filename;
    }

    /**
     * Get media (image or video) for a specific page. Serves all media from a
     * single '_media' folder, falling back to a cross-language lookup (#92).
     *
     * @param \Closure(\OCP\Files\Folder, string): ?array $findPageByUniqueId
     * @param \Closure(\OCP\Files\Folder, string): ?array $findPageById
     */
    public function getMedia(
        string $pageId,
        string $filename,
        \Closure $findPageByUniqueId,
        \Closure $findPageById
    ) {
        // Save original BEFORE sanitization
        $originalPageId = $pageId;
        $filename = basename($filename); // Prevent directory traversal

        // The language whose content this user is shown, which is where the
        // home page and the cache fast-path below look first. A page in another
        // language is picked up by the cross-language miss path further down.
        $languageFolder = $this->folders->readLanguageFolder();

        try {
            // Handle home page with original pageId
            if ($originalPageId === 'home' ||
                $originalPageId === '2e8f694e-147e-4793-8949-4732e679ae6b' ||
                $originalPageId === 'page-2e8f694e-147e-4793-8949-4732e679ae6b') {

                $mediaFolder = $languageFolder->get('_media');

                return $this->media->streamMediaFile($mediaFolder, $filename);
            }

            // Try cache with BOTH original and sanitized IDs
            $mediaFolder = null;
            $pageId = $this->idUtils->sanitizeId($originalPageId);

            if ($this->cache->hasPageFolder($originalPageId)) {
                // Cache hit with original ID (page-abc-123...)
                $pageFolder = $this->cache->getPageFolder($originalPageId);
                try {
                    $mediaFolder = $pageFolder->get('_media');
                } catch (NotFoundException $e) {
                    // No media folder
                }
            } else if ($this->cache->hasPageFolder($pageId)) {
                // Cache hit with sanitized ID (abc-123...)
                $pageFolder = $this->cache->getPageFolder($pageId);
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

            // Still nothing: the page may simply live in another language than
            // the one this user reads, which used to 404 every image on it
            // (#92). Only reached on a genuine miss, so the common case keeps
            // the single-folder walk above and pays nothing for this.
            if ($mediaFolder === null) {
                $located = $this->locatePageForMedia($originalPageId, $findPageByUniqueId, $findPageById);
                if ($located !== null) {
                    $mediaFolder = ($located['result']['isHome'] ?? false)
                        ? $this->locator->folderOrNull($located['languageFolder'], '_media')
                        : $this->locator->folderOrNull($located['result']['folder'] ?? null, '_media');
                }
            }

            if ($mediaFolder === null) {
                throw new \Exception('Media folder not found');
            }

            return $this->media->streamMediaFile($mediaFolder, $filename);
        } catch (NotFoundException $e) {
            throw new \Exception('Media not found');
        }
    }

    /**
     * Whether $filename already exists in the page's target media folder.
     *
     * @param \Closure(\OCP\Files\Folder, string): ?array $findPageByUniqueId
     * @param \Closure(\OCP\Files\Folder, string): ?array $findPageById
     */
    public function checkMediaExists(
        string $pageId,
        string $filename,
        string $targetFolder,
        \Closure $findPageByUniqueId,
        \Closure $findPageById
    ): bool {
        try {
            // Must resolve the page exactly as the upload does, or the
            // duplicate check inspects a different folder than the one written
            // to — silently answering "no duplicate" and overwriting nothing,
            // or prompting about a file the upload will not touch (#92).
            $located = $this->locatePageForMedia($pageId, $findPageByUniqueId, $findPageById);
            if ($located === null) {
                return false;
            }

            return $this->media->mediaExists(
                $this->mediaHostFolder($located),
                $located['languageFolder'],
                $filename,
                $targetFolder
            );
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Upload media with original filename.
     *
     * @param \Closure(\OCP\Files\Folder, string): ?array $findPageByUniqueId
     * @param \Closure(\OCP\Files\Folder, string): ?array $findPageById
     * @param \Closure(string): void $clearCache
     * @return array ['filename' => '...', 'exists' => bool]
     * @throws \Exception On upload failure or if file exists and overwrite is false
     */
    public function uploadMediaWithOriginalName(
        string $pageId,
        array $file,
        string $targetFolder,
        bool $overwrite,
        \Closure $findPageByUniqueId,
        \Closure $findPageById,
        \Closure $clearCache
    ): array {
        $validated = $this->media->validateUpload($file);

        // Sanitize original filename
        $filename = $this->mediaSanitizer->sanitizeFilename($file['name']);

        // Check if file exists
        $fileExists = $this->checkMediaExists($pageId, $filename, $targetFolder, $findPageByUniqueId, $findPageById);
        if ($fileExists && !$overwrite) {
            throw new \Exception('File already exists');
        }

        // Resolve the page first: both branches want the language folder the
        // page really lives in, not the uploader's own profile language (#92).
        $located = $this->locatePageForMedia($pageId, $findPageByUniqueId, $findPageById);
        if ($located === null) {
            throw new PageNotFoundException('Page not found: ' . $pageId);
        }

        // Get target folder based on targetFolder parameter
        if ($targetFolder === 'resources') {
            $uploadFolder = $this->media->resourcesFolderFor($located['languageFolder']);
        } else {
            $hostFolder = $this->mediaHostFolder($located);
            if ($hostFolder === null) {
                throw new PageNotFoundException('Page not found: ' . $pageId);
            }
            $uploadFolder = $this->media->mediaFolderFor($hostFolder);
        }

        // Upload file (content already sanitized for SVG). When $fileExists is
        // true here we did not throw above, so $overwrite is necessarily true —
        // the old `$fileExists && $overwrite` reduced to $fileExists.
        $this->media->writeMediaFile(
            $uploadFolder,
            $filename,
            $validated['content'],
            $fileExists
        );

        // Invalidate the per-page content cache so the next getPage()
        // reflects the new media file. See uploadMedia() for context.
        $clearCache($pageId);

        return [
            'filename' => $filename,
            'exists' => $fileExists
        ];
    }

    /**
     * List media files in a page's folder or its _resources library.
     *
     * @param \Closure(\OCP\Files\Folder, string): ?array $findPageByUniqueId
     * @param \Closure(\OCP\Files\Folder, string): ?array $findPageById
     * @return array List of media files with metadata
     */
    public function getMediaList(
        string $pageId,
        string $folderType,
        string $subPath,
        \Closure $findPageByUniqueId,
        \Closure $findPageById
    ): array {
        try {
            // List from the page's own language folder. getReadLanguageFolder()
            // answers "what should this USER see", which for the Shared Library
            // of a specific page is the wrong question: it listed one language's
            // _resources while the widget resolved images from another, so the
            // picker showed names whose previews always 404'd (#92).
            $located = $this->locatePageForMedia($pageId, $findPageByUniqueId, $findPageById);
            if ($located === null) {
                return [];
            }

            return $this->media->listMedia(
                $this->mediaHostFolder($located),
                $located['languageFolder'],
                $folderType,
                $subPath
            );
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Get a media file from a _resources folder. This route carries no pageId,
     * so it looks in the read language first, then walks the other languages
     * (#92).
     *
     * @param string $path File path (can include subfolders)
     * @return \OCP\Files\Node the resolved resource file node
     * @throws NotFoundException If file not found
     */
    public function getResourcesMediaFile(string $path) {
        // Path is already sanitized by ApiController::sanitizePath()
        //
        // This route carries no pageId, so the page's language cannot be
        // resolved the way the other media paths do. Look in the language the
        // user reads first, then in the remaining language folders: a shared
        // asset referenced from a page in another language is still a legitimate
        // request, and answering 404 blanked those images (#92).
        $readFolder = $this->folders->readLanguageFolder();

        $file = $this->findResourceIn($readFolder, $path);
        if ($file !== null) {
            return $file;
        }

        $baseFolder = $this->folders->intraVox();
        $searchedPath = $readFolder->getPath();

        foreach ($this->locator->cachedDirectoryListing($baseFolder) as $item) {
            if ($item->getType() !== FileInfo::TYPE_FOLDER
                || !($item instanceof Folder)) {
                continue;
            }
            if (!preg_match('/^[a-z]{2,3}$/', $item->getName())
                || $item->getPath() === $searchedPath) {
                continue;
            }
            $file = $this->findResourceIn($item, $path);
            if ($file !== null) {
                return $file;
            }
        }

        throw new NotFoundException('Media file not found: ' . $path);
    }

    // ---------------------------------------------------------- media-only helpers

    /**
     * Locate a page for a MEDIA operation, across every language folder, and
     * report which language folder it turned out to live in.
     *
     * @param \Closure(\OCP\Files\Folder, string): ?array $findPageByUniqueId
     * @param \Closure(\OCP\Files\Folder, string): ?array $findPageById
     * @return array{result: array, languageFolder: \OCP\Files\Folder}|null
     */
    private function locatePageForMedia(string $pageId, \Closure $findPageByUniqueId, \Closure $findPageById): ?array {
        $primary = $this->folders->readLanguageFolder();

        $find = function (Folder $folder) use ($pageId, $findPageByUniqueId, $findPageById): ?array {
            if (strpos($pageId, 'page-') === 0) {
                $byUniqueId = $findPageByUniqueId($folder, $pageId);
                if ($byUniqueId !== null) {
                    return $byUniqueId;
                }
            }
            // Legacy slug ids (and uniqueIds that predate the page- prefix)
            // stay resolvable, matching the fallback the callers already had.
            return $findPageById($folder, $this->idUtils->sanitizeId($pageId));
        };

        // The cross-language locate takes a lazy root (invoked per language
        // iteration) — the same getIntraVoxFolder resolution PageService's
        // rootClosure() used, now from this orchestrator's own FolderContext.
        $result = $this->locator->locateAcrossLanguages(fn(): Folder => $this->folders->intraVox(), $primary, $find);
        if ($result === null) {
            return null;
        }

        return [
            'result' => $result,
            'languageFolder' => $this->languageFolderOfPageResult($result) ?? $primary,
        ];
    }

    /**
     * The language content folder that a findPageByUniqueId()/findPageById()
     * result sits in, derived from the page folder's own path.
     *
     * @return \OCP\Files\Folder|null null when the path cannot be resolved.
     */
    private function languageFolderOfPageResult(array $result): ?Folder {
        $folder = $result['folder'] ?? null;
        if (!($folder instanceof Folder)) {
            return null;
        }

        // The home page's "folder" IS the language folder; deeper pages sit
        // somewhere below it. languageOfFolder() names the language either way.
        $language = $this->folders->languageOfFolder($folder);
        if ($language === null) {
            return null;
        }

        try {
            $candidate = $this->folders->intraVox()->get($language);
            return $candidate instanceof Folder ? $candidate : null;
        } catch (NotFoundException $e) {
            return null;
        }
    }

    /**
     * The folder whose `_media` holds a located page's media: the page's own
     * folder, except for the home page, whose media lives in the language
     * folder's root `_media`.
     *
     * @param array{result: array, languageFolder: \OCP\Files\Folder} $located
     */
    private function mediaHostFolder(array $located): ?Folder {
        if ($located['result']['isHome'] ?? false) {
            return $located['languageFolder'];
        }
        $folder = $located['result']['folder'] ?? null;
        return $folder instanceof Folder ? $folder : null;
    }

    /** Recursively find the _media folder for a page by uniqueId. */
    private function findMediaFolderForPage($folder, string $uniqueId): ?Folder {
        return $this->media->findMediaFolderForPage($folder, $uniqueId);
    }

    /** Resolve $path inside one language folder's `_resources`, or null. */
    private function findResourceIn(Folder $languageFolder, string $path): ?Node {
        return $this->media->findResourceIn($languageFolder, $path);
    }
}
