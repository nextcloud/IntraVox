<?php
declare(strict_types=1);

namespace OCA\IntraVox\Service\Media;

use OCA\IntraVox\Service\Locator\PageLocator;
use OCA\IntraVox\Service\Sanitize\MediaSanitizer;
use OCP\Files\File;
use OCP\Files\Folder;
use OCP\Files\NotFoundException;
use Psr\Log\LoggerInterface;

/**
 * Page media mechanics, extracted from PageService (service split, PR-17 and
 * PR-17b): the streamed copy engine every copy/template/translation flow uses,
 * the recursive `_media` folder resolver, and — since PR-17b — the upload
 * validation, the `_media`/`_resources` target resolution and the read/list/
 * serve mechanics on top of them.
 *
 * The split's standing pattern holds: PageService LOCATES the page (which
 * language folder it lives in, its page folder, the request caches) and
 * invalidates its own caches; this service works on the nodes it is handed.
 * Nothing here resolves a pageId or touches PageService's caches.
 *
 * The copy paths are best-effort by contract: a media problem is logged and
 * skipped, never allowed to fail the page operation it accompanies. The
 * upload paths are the opposite — they throw, because a rejected file must
 * reach the user rather than silently vanish.
 */
class PageMediaService {
    /**
     * Upload constraints. Duplicated from PageService rather than shared:
     * these bound what this service will WRITE, and a media rule belongs with
     * the media code. PageService keeps its own copies for the widget/page
     * validation paths that still consult them.
     */
    public const ALLOWED_VIDEO_TYPES = ['video/mp4', 'video/webm', 'video/ogg'];
    public const ALLOWED_MEDIA_TYPES = [
        'image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/svg+xml',
        'video/mp4', 'video/webm', 'video/ogg'
    ];
    public const MAX_MEDIA_SIZE = 52428800; // 50MB (largest of image/video limits)
    public const MAX_SVG_SIZE = 1048576; // 1MB for SVG files (prevent XML bomb attacks)

    private PageLocator $locator;
    private MediaSanitizer $mediaSanitizer;
    private LoggerInterface $logger;

    public function __construct(
        PageLocator $locator,
        MediaSanitizer $mediaSanitizer,
        LoggerInterface $logger
    ) {
        $this->locator = $locator;
        $this->mediaSanitizer = $mediaSanitizer;
        $this->logger = $logger;
    }

    /**
     * Copy the source page's `_media` folder into the target page's folder,
     * creating `_media` there when missing. Either side being absent is an
     * ordinary outcome (a page without media, a target that did not resolve).
     */
    public function copyPageMedia(?Folder $sourceFolder, ?Folder $targetPageFolder, string $context): void {
        try {
            if ($sourceFolder === null || !$sourceFolder->nodeExists('_media')) {
                return;
            }
            $sourceMedia = $sourceFolder->get('_media');
            if (!($sourceMedia instanceof Folder)) {
                return;
            }
            if ($targetPageFolder === null) {
                return;
            }
            if (!$targetPageFolder->nodeExists('_media')) {
                $targetPageFolder->newFolder('_media');
            }
            $targetMedia = $targetPageFolder->get('_media');
            if ($targetMedia instanceof Folder) {
                $this->copyMediaFolderContents($sourceMedia, $targetMedia);
            }
        } catch (\Exception $e) {
            $this->logger->warning($context . ': media copy failed', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Copy all files from one media folder to another (within Nextcloud storage)
     */
    public function copyMediaFolderContents(Folder $source, Folder $target): void {
        try {
            foreach ($source->getDirectoryListing() as $item) {
                $name = $item->getName();

                // Skip hidden files
                if (str_starts_with($name, '.')) {
                    continue;
                }

                if ($item instanceof File) {
                    // Copy STREAMED, never via getContent(): that buffers the
                    // whole file in RAM, and media folders hold videos — a
                    // 2 GB file would hit memory_limit halfway through a copy
                    // or translation. putContent() accepts a resource and
                    // pipes it chunk-wise.
                    try {
                        $stream = $item->fopen('rb');
                        if ($stream === false) {
                            // Storage without stream support; small-file path.
                            $target->newFile($name)->putContent($item->getContent());
                        } else {
                            try {
                                $target->newFile($name)->putContent($stream);
                            } finally {
                                if (is_resource($stream)) {
                                    fclose($stream);
                                }
                            }
                        }
                    } catch (\Exception $e) {
                        $this->logger->warning('Failed to copy media file: ' . $name . ' - ' . $e->getMessage());
                    }
                } elseif ($item instanceof Folder) {
                    // Recursively copy subfolder
                    try {
                        if (!$target->nodeExists($name)) {
                            $newSubFolder = $target->newFolder($name);
                        } else {
                            $newSubFolder = $target->get($name);
                        }
                        if ($newSubFolder instanceof Folder) {
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
     * Recursively find the `_media` folder of the page carrying $uniqueId,
     * starting at $folder. Returns null when the page has no media folder.
     */
    public function findMediaFolderForPage($folder, string $uniqueId): ?Folder {
        // First scan JSON files in CURRENT folder to see if page is here
        $foundMatch = false;
        foreach ($this->locator->cachedDirectoryListing($folder) as $item) {
            if ($item->getType() === \OCP\Files\FileInfo::TYPE_FILE &&
                substr($item->getName(), -5) === '.json' &&
                $item->getName() !== 'navigation.json' &&
                $item->getName() !== 'footer.json' &&
                $item->getName() !== 'homepage.json') {

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
        foreach ($this->locator->cachedDirectoryListing($folder) as $item) {
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
     * Mark a media folder with `.nomedia`, standard practice for media
     * storage folders. Never critical.
     */
    public function createMediaFolderMarker($mediaFolder): void {
        try {
            if (!$mediaFolder->nodeExists('.nomedia')) {
                $nomediaFile = $mediaFolder->newFile('.nomedia');
                $nomediaFile->putContent('');
            }
        } catch (\Exception $e) {
            $this->logger->debug('Could not create .nomedia file', [
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * The $_FILES shape check and the PHP upload-error translation.
     *
     * Separate from validateUpload() because uploadMedia() has to run it
     * BEFORE sanitizing the page id (which can reject an id of its own) and
     * the rest of the validation after — the pre-split order, kept verbatim
     * so a request with both a bad file and a bad id reports the same one.
     *
     * @throws \InvalidArgumentException on a malformed or failed upload
     */
    public function assertUploadShape(array $file): void {
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
    }

    /**
     * Validate an uploaded file and return its sanitized CONTENT.
     *
     * Everything the two upload paths did identically before writing: the
     * $_FILES shape check, the PHP upload-error translation, MIME sniffing
     * against the allow-list, the size ceilings, the polyglot check on raster
     * images and the SVG sanitize pass with its own smaller ceiling.
     *
     * Returns the bytes to write, so callers never re-read the temp file. For
     * raster images it also carries the display metadata harvested by the same
     * polyglot check (dimensions + dominant colour); null for video/SVG.
     *
     * @param array $file $_FILES-shaped upload array
     * @return array{content: string, mimeType: string, imageMeta: ?array{width:int,height:int,dominantColor:?string}}
     * @throws \InvalidArgumentException on any rejected upload
     */
    public function validateUpload(array $file): array {
        $this->assertUploadShape($file);

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

        // Additional validation for image files (prevents polyglot attacks).
        // The same header read also yields the display metadata (dimensions +
        // dominant colour) that lets the frontend reserve layout space and show
        // an instant placeholder — no separate decode pass.
        $imageMeta = null;
        if (in_array($mimeType, ['image/jpeg', 'image/png', 'image/gif', 'image/webp'])) {
            $imageMeta = $this->mediaSanitizer->validateImageFile($file['tmp_name'], $mimeType);
        }

        // SVG files get special treatment: smaller size limit + sanitization
        if ($mimeType === 'image/svg+xml') {
            if ($file['size'] > self::MAX_SVG_SIZE) {
                throw new \InvalidArgumentException('SVG file too large. Maximum size is 1MB.');
            }
            $content = file_get_contents($file['tmp_name']);
            $content = $this->mediaSanitizer->sanitizeSVG($content);
        } else {
            $content = file_get_contents($file['tmp_name']);
        }

        return ['content' => $content, 'mimeType' => $mimeType, 'imageMeta' => $imageMeta];
    }

    /**
     * The extension each accepted mime type is stored with. (UP-1)
     *
     * Derived from the SNIFFED type, never from the uploaded filename, so the
     * name on disk cannot disagree with the bytes.
     */
    private const EXTENSION_BY_MIME = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/gif' => 'gif',
        'image/webp' => 'webp',
        'image/svg+xml' => 'svg',
        'video/mp4' => 'mp4',
        'video/webm' => 'webm',
        'video/ogg' => 'ogv',
    ];

    /**
     * The generated storage name for a validated upload: `img_`/`vid_` plus a
     * uniqid and an extension derived from the sniffed mime type.
     *
     * The extension used to come from pathinfo() on the CLIENT-SUPPLIED name,
     * while only the prefix honoured the sniffed type. A genuine PNG uploaded as
     * "evil.php" therefore landed on disk as "img_<uniqid>.php" — real image
     * bytes under a name a misconfigured web server may hand to an interpreter,
     * and one that defeats any extension-based rule downstream. "noext" produced
     * a filename ending in a bare dot.
     *
     * validateUpload() has already sniffed and allowlisted the type by the time
     * we get here, so the mime is authoritative and the client name contributes
     * nothing: the stored name is entirely generated.
     *
     * @param string $originalName Deliberately unused; kept so the signature
     *                             still documents what callers pass, and so
     *                             nothing reintroduces it as the extension.
     */
    public function generatedMediaFilename(string $originalName, string $mimeType): string {
        $isVideo = in_array($mimeType, self::ALLOWED_VIDEO_TYPES, true);
        $prefix = $isVideo ? 'vid_' : 'img_';

        // Unknown types cannot reach here (validateUpload allowlists first), but
        // fail closed rather than emitting a trailing-dot name if one ever does.
        $extension = self::EXTENSION_BY_MIME[$mimeType] ?? 'bin';

        return uniqid($prefix, true) . '.' . $extension;
    }

    /**
     * The page's `_media` folder, created when absent.
     *
     * $pageFolder is the located page's own folder, or — for the home page,
     * whose media lives in root/_media/ — the language folder itself. Which
     * of the two applies is PageService's call, since it did the locating.
     */
    public function mediaFolderFor(Folder $pageFolder): Folder {
        try {
            $mediaFolder = $pageFolder->get('_media');
        } catch (NotFoundException $e) {
            $mediaFolder = $pageFolder->newFolder('_media');
        }
        return $mediaFolder;
    }

    /**
     * The language folder's `_resources` library, created when absent.
     *
     * The shared library of the PAGE's own language, so the asset lands where
     * that page can serve it (#92).
     */
    public function resourcesFolderFor(Folder $languageFolder): Folder {
        try {
            $uploadFolder = $languageFolder->get('_resources');
        } catch (NotFoundException $e) {
            $uploadFolder = $languageFolder->newFolder('_resources');
        }
        return $uploadFolder;
    }

    /**
     * Write $content into $folder under $filename, overwriting an existing
     * file in place when told to. Splitting create from overwrite matters:
     * putContent() on the existing node keeps its fileid, and with it the
     * shares, comments and previews hanging off it.
     */
    public function writeMediaFile(Folder $folder, string $filename, string $content, bool $overwriteExisting): void {
        if ($overwriteExisting) {
            // Overwrite existing file
            $existingFile = $folder->get($filename);
            $existingFile->putContent($content);
        } else {
            // Create new file
            $newFile = $folder->newFile($filename);
            $newFile->putContent($content);
        }
    }

    /**
     * Does $filename exist in the media folder of the located page, or in the
     * language's `_resources` library?
     *
     * $pageFolder is the page's own folder (or, for the home page, the
     * language folder); $languageFolder is the page's language. Both come
     * from the caller's locate step. Absent folders answer false, as does any
     * failure — a duplicate check must never break an upload.
     */
    public function mediaExists(?Folder $pageFolder, Folder $languageFolder, string $filename, string $targetFolder): bool {
        try {
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
            }

            if ($pageFolder === null) {
                return false;
            }

            // Get media folder
            try {
                $mediaFolder = $pageFolder->get('_media');
            } catch (NotFoundException $e) {
                return false;
            }

            // Check if file exists
            try {
                $mediaFolder->get($filename);
                return true;
            } catch (NotFoundException $e) {
                return false;
            }
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * List the located page's `_media`, or the language's `_resources`
     * library (optionally a subfolder of it), sorted by name.
     *
     * Resources entries carry their path relative to the `_resources` root and
     * describe folders as well as files, because the Shared Library browses;
     * page media is a flat file list. Any miss yields [] — the picker renders
     * an empty library rather than an error.
     *
     * @return array<int, array<string, mixed>>
     */
    public function listMedia(?Folder $pageFolder, Folder $languageFolder, string $folderType, string $subPath = ''): array {
        try {
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
                // List files in page/_media folder — the page was already
                // resolved cross-language by the caller.
                if ($pageFolder === null) {
                    return [];
                }

                // Get media folder
                try {
                    $mediaFolder = $pageFolder->get('_media');
                } catch (NotFoundException $e) {
                    return [];
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
     * Stream one media file out of $mediaFolder as an HTTP response.
     *
     * Serving is deliberately re-validated against the allow-list: the folder
     * is user-writable through Files, so "it is in _media" is not proof that
     * it is servable media.
     *
     * $mediaFolder is deliberately untyped: the callers reach it through
     * `->get('_media')` on cached page folders, which is declared as Node.
     * Pre-split this code simply called ->get() on whatever came back, and a
     * strict Folder hint here would turn that into a TypeError instead.
     *
     * @param \OCP\Files\Node|\OCP\Files\Folder $mediaFolder
     * @return \OCP\AppFramework\Http\StreamResponse
     * @throws \Exception when absent, not a file, or not an allowed type
     */
    public function streamMediaFile($mediaFolder, string $filename) {
        $file = $mediaFolder->get($filename);

        if ($file->getType() !== \OCP\Files\FileInfo::TYPE_FILE) {
            throw new \Exception('Not a file');
        }

        // Get mime type (with fallback for incorrect GroupFolder cache)
        $mimeType = $this->resolveMediaMimeType($file);

        // Validate it's an allowed media type
        if (!in_array($mimeType, self::ALLOWED_MEDIA_TYPES)) {
            throw new \Exception('Invalid media type');
        }

        // Create stream response
        $response = new \OCP\AppFramework\Http\StreamResponse($file->fopen('rb'));
        $response->addHeader('Content-Type', $mimeType);
        // Uploads through this app sanitise SVG, but a file may reach _media another
        // way (e.g. WebDAV), and SVG renders as an active document. Serve SVG as a
        // download rather than inline; everything else on the media allowlist is
        // raster/video and safe inline. nosniff stops a mislabelled file from being
        // reinterpreted as something scriptable.
        $isSvg = $mimeType === 'image/svg+xml';
        $disposition = $isSvg ? 'attachment' : 'inline';
        $response->addHeader('Content-Disposition', $disposition . '; filename="' . $file->getName() . '"');
        $response->addHeader('X-Content-Type-Options', 'nosniff');
        // Use longer cache for images, shorter for videos
        $isVideo = in_array($mimeType, self::ALLOWED_VIDEO_TYPES);
        $cacheTime = $isVideo ? 86400 : 31536000;
        $response->addHeader('Cache-Control', 'public, max-age=' . $cacheTime);

        return $response;
    }

    /**
     * Resolve MIME type for a media file, with extension-based fallback.
     *
     * GroupFolders can store incorrect MIME types (application/octet-stream)
     * in the file cache. When that happens, fall back to extension detection.
     */
    public function resolveMediaMimeType(File $file): string {
        $mimeType = $file->getMimeType();

        if ($mimeType === 'application/octet-stream') {
            $ext = strtolower(pathinfo($file->getName(), PATHINFO_EXTENSION));
            $mimeType = match ($ext) {
                'jpg', 'jpeg' => 'image/jpeg',
                'png' => 'image/png',
                'gif' => 'image/gif',
                'webp' => 'image/webp',
                'svg' => 'image/svg+xml',
                'mp4' => 'video/mp4',
                'webm' => 'video/webm',
                'ogg' => 'video/ogg',
                default => $mimeType,
            };
        }

        return $mimeType;
    }

    /**
     * Resolve $path inside one language folder's `_resources`, or null.
     * Kept separate so the cross-language walk in PageService reads as a walk.
     */
    public function findResourceIn(Folder $languageFolder, string $path): ?\OCP\Files\Node {
        $resourcesFolder = $this->locator->folderOrNull($languageFolder, '_resources');
        if ($resourcesFolder === null) {
            return null;
        }
        try {
            return $resourcesFolder->get($path);
        } catch (NotFoundException $e) {
            return null;
        }
    }

    /**
     * Get relative path from resources root
     * @param \OCP\Files\Node $node File or folder node
     * @param \OCP\Files\Folder $resourcesRoot _resources folder root
     * @return string Relative path (e.g., "logos/company.png")
     */
    private function getRelativePath(\OCP\Files\Node $node, Folder $resourcesRoot): string {
        $fullPath = $node->getPath();
        $rootPath = $resourcesRoot->getPath();
        return ltrim(substr($fullPath, strlen($rootPath)), '/');
    }
}
