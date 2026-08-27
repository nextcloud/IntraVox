<?php
declare(strict_types=1);

namespace OCA\IntraVox\Controller;

use OCA\IntraVox\Exception\PageNotFoundException;
use OCA\IntraVox\Service\PageService;
use OCP\Files\NotFoundException;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\Attribute\NoCSRFRequired;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\Http\StreamResponse;
use OCP\IConfig;
use OCP\IRequest;
use Psr\Log\LoggerInterface;

/**
 * Media on pages: uploading, listing, and serving images and video.
 *
 * Split out of ApiController (PR-A). Eight routes that all resolve a page,
 * check write permission for the mutating half, and hand the actual file work
 * to PageService (which delegates to PageMediaService).
 *
 * Serving is #[NoCSRFRequired] because these URLs end up in <img src>; uploading
 * is not. Both forms moved across unchanged — see docs/route-table.md.
 *
 * sanitizePath() comes from Shared\SharePathTrait: the same path-sanitising is
 * used by the anonymous share endpoints, and two copies of that would be two
 * places for a traversal bug to hide.
 */
class MediaApiController extends Controller {
    /** Ceiling on the media listing; the picker shows far fewer than this. */
    private const MAX_MEDIA_IN_LISTING = 1000;

    use ApiErrorTrait;
    use RequiresPagePermission;
    use \OCA\IntraVox\Controller\Shared\SharePathTrait;

    public function __construct(
        string $appName,
        IRequest $request,
        private PageService $pageService,
        // Required by Shared\SharePathTrait::peopleAllowedOnPublicShares().
        // This controller never calls that method, but the trait reaches for the
        // property and PHP would only complain at runtime, on a request that hits
        // it. PHPStan caught it here instead -- see the note in PR-B about those
        // traits having no abstract accessor.
        private IConfig $config,
        private LoggerInterface $logger,
    ) {
        parent::__construct($appName, $request);
    }

    protected function getLogger(): LoggerInterface {
        return $this->logger;
    }

    protected function getPageService(): PageService {
        return $this->pageService;
    }
    /**
     * Upload media (image or video) for a page
     * Unified endpoint that stores all media in a single 'media' folder
     */
    #[NoAdminRequired]
    public function uploadMedia(string $pageId): DataResponse {
        try {
            // First get the page to check permissions (from Nextcloud filesystem)
            $existingPage = $this->requireWritablePage($pageId, 'cannot upload media to this page');
            if ($existingPage instanceof DataResponse) {
                return $existingPage;
            }

            // Try 'media' field first, then fall back to 'image' or 'video' for compatibility
            $file = $this->request->getUploadedFile('media');
            if (!$file) {
                $file = $this->request->getUploadedFile('image');
            }
            if (!$file) {
                $file = $this->request->getUploadedFile('video');
            }

            if (!$file) {
                throw new \InvalidArgumentException('No media file provided');
            }

            if (empty($file['tmp_name'])) {
                throw new \InvalidArgumentException('File upload failed - tmp_name is empty. Upload error: ' . ($file['error'] ?? 'unknown'));
            }

            $filename = $this->pageService->uploadMedia($pageId, $file);
            return new DataResponse(['filename' => $filename], Http::STATUS_CREATED);
        } catch (PageNotFoundException $e) {
            $this->logger->warning('[uploadMedia] PageNotFoundException: ' . $e->getMessage(), [
                'pageId' => $pageId,
            ]);
            return new DataResponse(
                ['error' => $e->getMessage()],
                Http::STATUS_NOT_FOUND
            );
        } catch (\InvalidArgumentException $e) {
            return new DataResponse(
                ['error' => $e->getMessage()],
                Http::STATUS_BAD_REQUEST
            );
        } catch (\Exception $e) {
            return new DataResponse(
                ['error' => $e->getMessage()],
                Http::STATUS_INTERNAL_SERVER_ERROR
            );
        }
    }
    /**
     * Check if media file with given name already exists
     */
    #[NoAdminRequired]
    public function checkMediaDuplicate(string $pageId): DataResponse {
        try {
            $filename = $this->request->getParam('filename');
            $target = $this->request->getParam('target', 'page');

            if (!$filename) {
                return new DataResponse(
                    ['error' => 'Filename parameter required'],
                    Http::STATUS_BAD_REQUEST
                );
            }

            // Ask about the name the upload will actually write. The client
            // sends the name as the user picked it, while
            // uploadMediaWithOriginalName() sanitizes before writing, so
            // checking the raw name answered a question nobody asked: two
            // different names that sanitize to the same one were reported as
            // "no duplicate" and then collided on write.
            $exists = $this->pageService->checkMediaExists(
                $pageId,
                $this->pageService->sanitizeFilename($filename),
                $target
            );

            return new DataResponse(['exists' => $exists]);
        } catch (\InvalidArgumentException $e) {
            // A rejected extension is the caller's input, not a server fault —
            // the same 400 the upload itself answers with.
            return new DataResponse(
                ['error' => $e->getMessage()],
                Http::STATUS_BAD_REQUEST
            );
        } catch (\Exception $e) {
            return new DataResponse(
                ['error' => $e->getMessage()],
                Http::STATUS_INTERNAL_SERVER_ERROR
            );
        }
    }
    /**
     * Upload media with original filename
     */
    #[NoAdminRequired]
    public function uploadMediaWithName(string $pageId): DataResponse {
        try {
            // Check write permission
            $existingPage = $this->requireWritablePage($pageId, 'cannot upload media to this page');
            if ($existingPage instanceof DataResponse) {
                return $existingPage;
            }

            // Get uploaded file
            $file = $this->request->getUploadedFile('media');
            if (!$file) {
                $file = $this->request->getUploadedFile('file');
            }

            if (!$file || empty($file['tmp_name'])) {
                return new DataResponse(
                    ['error' => 'No file uploaded'],
                    Http::STATUS_BAD_REQUEST
                );
            }

            // Get parameters
            $target = $this->request->getParam('target', 'page');
            $overwrite = $this->request->getParam('overwrite', '0') === '1';

            // Upload file
            $result = $this->pageService->uploadMediaWithOriginalName($pageId, $file, $target, $overwrite);

            return new DataResponse($result, Http::STATUS_CREATED);

        } catch (PageNotFoundException $e) {
            // Was an unlogged 500 with a bare "Page not found" body, which is
            // why issue #92 came in with no Nextcloud log entries at all.
            $this->logger->warning('[uploadMediaWithName] PageNotFoundException: ' . $e->getMessage(), [
                'pageId' => $pageId,
            ]);
            return new DataResponse(
                ['error' => $e->getMessage()],
                Http::STATUS_NOT_FOUND
            );
        } catch (\InvalidArgumentException $e) {
            return new DataResponse(
                ['error' => $e->getMessage()],
                Http::STATUS_BAD_REQUEST
            );
        } catch (\Exception $e) {
            // Check if it's a "File already exists" error
            if ($e->getMessage() === 'File already exists') {
                return new DataResponse(
                    ['error' => $e->getMessage()],
                    Http::STATUS_CONFLICT
                );
            }

            return new DataResponse(
                ['error' => $e->getMessage()],
                Http::STATUS_INTERNAL_SERVER_ERROR
            );
        }
    }
    /**
     * Get list of media files for a page or resources folder
     */
    #[NoAdminRequired]
    #[NoCSRFRequired]
    public function listMedia(string $pageId): DataResponse {
        try {
            $folder = $this->request->getParam('folder', 'page');
            $path = $this->request->getParam('path', ''); // NEW: for subfolder navigation

            // Security: Sanitize path for resources folder
            if ($folder === 'resources' && !empty($path)) {
                try {
                    $path = $this->sanitizePath($path);
                } catch (\InvalidArgumentException $e) {
                    return new DataResponse(
                        ['error' => 'Invalid path: ' . $e->getMessage()],
                        Http::STATUS_BAD_REQUEST
                    );
                }
            }

            $mediaList = $this->pageService->getMediaList($pageId, $folder, $path);

            // Naturally bounded by one folder's contents, which is not the same as
            // bounded. The shared resources folder in particular grows with the
            // instance rather than with the page. Same contract as the page
            // listing: the shape does not change, and a truncated answer says so.
            $total = count($mediaList);
            $response = new DataResponse(['media' => array_slice($mediaList, 0, self::MAX_MEDIA_IN_LISTING)]);
            $response->addHeader('X-IntraVox-Cap', (string)self::MAX_MEDIA_IN_LISTING);
            $response->addHeader('X-IntraVox-Truncated', $total > self::MAX_MEDIA_IN_LISTING ? 'true' : 'false');

            return $response;
        } catch (\Exception $e) {
            return new DataResponse(
                ['error' => $e->getMessage()],
                Http::STATUS_INTERNAL_SERVER_ERROR
            );
        }
    }
    /**
     * Get media file from resources folder with separate folder and filename
     * Handles URLs like: /api/resources/media/backgrounds/header.svg
     */
    #[NoAdminRequired]
    #[NoCSRFRequired]
    public function getResourcesMediaWithFolder(string $folder, string $filename) {
        $path = $folder . '/' . $filename;
        return $this->getResourcesMedia($path);
    }
    /**
     * Get media file from resources folder (globally readable)
     */
    #[NoAdminRequired]
    #[NoCSRFRequired]
    public function getResourcesMedia(string $filename) {
        try {
            // Security: Validate path (prevent directory traversal)
            try {
                $safePath = $this->sanitizePath($filename);
            } catch (\InvalidArgumentException $e) {
                return new DataResponse(
                    ['error' => 'Invalid path: ' . $e->getMessage()],
                    Http::STATUS_BAD_REQUEST
                );
            }

            $file = $this->pageService->getResourcesMediaFile($safePath);

            // Set appropriate content type
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mimeType = $finfo->buffer($file->getContent());
            finfo_close($finfo);

            // Get just the filename for Content-Disposition (not the full path)
            $displayName = basename($safePath);

            $response = new StreamResponse($file->fopen('rb'));
            $response->addHeader('Content-Type', $mimeType);
            $response->addHeader('Content-Disposition', 'inline; filename="' . $displayName . '"');
            $response->addHeader('Cache-Control', 'public, max-age=31536000'); // 1 year cache

            return $response;
        } catch (NotFoundException $e) {
            return new DataResponse(
                ['error' => 'Media file not found'],
                Http::STATUS_NOT_FOUND
            );
        } catch (\Exception $e) {
            $this->logger->error('Error serving resources media: ' . $e->getMessage());
            return new DataResponse(
                ['error' => 'Internal server error'],
                Http::STATUS_INTERNAL_SERVER_ERROR
            );
        }
    }
    /**
     * Get server upload limit
     * Returns the effective upload limit in bytes
     */
    #[NoAdminRequired]
    #[NoCSRFRequired]
    public function getUploadLimit(): DataResponse {
        try {
            $limit = $this->pageService->getUploadLimit();
            return new DataResponse([
                'limit' => $limit,
                'limitMB' => round($limit / (1024 * 1024), 1)
            ]);
        } catch (\Exception $e) {
            return new DataResponse(
                ['error' => $e->getMessage()],
                Http::STATUS_INTERNAL_SERVER_ERROR
            );
        }
    }
    /**
     * Get media (image or video) for a page
     * Unified endpoint that serves all media from a single 'media' folder
     */
    #[NoAdminRequired]
    #[NoCSRFRequired]
    public function getMedia(string $pageId, string $filename) {
        try {
            // First get the page to check permissions (from Nextcloud filesystem)
            $existingPage = $this->pageService->getPage($pageId);

            // Check read permission using Nextcloud's permissions
            if (!($existingPage['permissions']['canRead'] ?? false)) {
                return new DataResponse(
                    ['error' => 'Access denied'],
                    Http::STATUS_FORBIDDEN
                );
            }

            return $this->pageService->getMedia($pageId, $filename);
        } catch (\Exception $e) {
            return new DataResponse(
                ['error' => $e->getMessage()],
                Http::STATUS_NOT_FOUND
            );
        }
    }
}
