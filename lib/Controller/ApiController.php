<?php
declare(strict_types=1);

namespace OCA\IntraVox\Controller;

use OCA\IntraVox\AppInfo\Application;
use OCA\IntraVox\Constants;
use OCA\IntraVox\Service\EngagementSettingsService;
use OCA\IntraVox\Service\ImportService;
use OCA\IntraVox\Service\PublicationSettingsService;
use OCA\IntraVox\Service\PublicShareService;
use OCA\IntraVox\Service\TelemetryService;
use OCA\IntraVox\Service\Import\ConfluenceHtmlImporter;
use OCA\IntraVox\Service\Import\ConfluenceImporter;
use OCA\IntraVox\Service\NavigationService;
use OCA\IntraVox\Service\PageService;
use OCA\IntraVox\Service\PermissionService;
use OCA\IntraVox\Service\SetupService;
use OCA\IntraVox\Service\SystemFileService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\AnonRateThrottle;
use OCP\AppFramework\Http\Attribute\BruteForceProtection;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\Attribute\NoCSRFRequired;
use OCP\AppFramework\Http\Attribute\PublicPage;
use OCP\Security\Bruteforce\IThrottler;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\Http\JSONResponse;
use OCP\AppFramework\Http\StreamResponse;
use OCP\Files\NotFoundException;
use OCP\IConfig;
use OCP\IGroupManager;
use OCP\IRequest;
use OCP\ITempManager;
use OCP\ISession;
use OCP\IUserSession;
use Psr\Log\LoggerInterface;

/**
 * API Controller for IntraVox pages
 *
 * All permission checks use Nextcloud's native filesystem permissions
 * which automatically respect GroupFolder ACL rules.
 */
class ApiController extends Controller {
    use ApiErrorTrait;

    private PageService $pageService;
    private SetupService $setupService;
    private EngagementSettingsService $engagementSettings;
    private PublicationSettingsService $publicationSettings;
    private PublicShareService $publicShareService;
    private TelemetryService $telemetryService;
    private ImportService $importService;
    private LoggerInterface $logger;
    private IConfig $config;
    private IGroupManager $groupManager;
    private IUserSession $userSession;
    private ITempManager $tempManager;
    private SystemFileService $systemFileService;
    private NavigationService $navigationService;
    private PermissionService $permissionService;
    private ISession $session;

    public function __construct(
        string $appName,
        IRequest $request,
        PageService $pageService,
        SetupService $setupService,
        EngagementSettingsService $engagementSettings,
        PublicationSettingsService $publicationSettings,
        PublicShareService $publicShareService,
        TelemetryService $telemetryService,
        ImportService $importService,
        LoggerInterface $logger,
        IConfig $config,
        IGroupManager $groupManager,
        IUserSession $userSession,
        ITempManager $tempManager,
        SystemFileService $systemFileService,
        NavigationService $navigationService,
        PermissionService $permissionService,
        ISession $session
    ) {
        parent::__construct($appName, $request);
        $this->pageService = $pageService;
        $this->setupService = $setupService;
        $this->engagementSettings = $engagementSettings;
        $this->publicationSettings = $publicationSettings;
        $this->publicShareService = $publicShareService;
        $this->telemetryService = $telemetryService;
        $this->importService = $importService;
        $this->logger = $logger;
        $this->config = $config;
        $this->groupManager = $groupManager;
        $this->userSession = $userSession;
        $this->tempManager = $tempManager;
        $this->systemFileService = $systemFileService;
        $this->navigationService = $navigationService;
        $this->permissionService = $permissionService;
        $this->session = $session;
    }

    /**
     * Get the logger instance for ApiErrorTrait.
     */
    protected function getLogger(): LoggerInterface {
        return $this->logger;
    }

    /**
     * Check if current user is admin
     */
    private function isAdmin(): bool {
        $user = $this->userSession->getUser();
        if ($user === null) {
            return false;
        }
        return $this->groupManager->isAdmin($user->getUID());
    }

    /**
     * Validate parentPageId parameter for import operations
     *
     * Security: Prevents IDOR attacks by validating:
     * 1. Parent page exists
     * 2. User has write permission on parent
     * 3. Parent is in the same language (groupfolder)
     *
     * @param string $parentPageId The parent page unique ID
     * @param string $targetLanguage The target language for import
     * @return array{valid: bool, error?: string, page?: array} Validation result
     */
    private function validateParentPageId(string $parentPageId, string $targetLanguage): array {
        try {
            $parentPage = $this->pageService->getPage($parentPageId);
        } catch (\Exception $e) {
            $this->logger->warning('[ApiController] Parent page validation failed: page not found', [
                'parentPageId' => $parentPageId,
                'error' => $e->getMessage()
            ]);
            return [
                'valid' => false,
                'error' => 'Parent page not found'
            ];
        }

        // Check write permission
        if (!($parentPage['permissions']['canWrite'] ?? false)) {
            $this->logger->warning('[ApiController] Parent page validation failed: no write permission', [
                'parentPageId' => $parentPageId,
                'targetLanguage' => $targetLanguage
            ]);
            return [
                'valid' => false,
                'error' => 'No write permission for parent page'
            ];
        }

        // Check same language (prevents cross-groupfolder imports)
        $parentLanguage = $parentPage['language'] ?? null;
        if ($parentLanguage !== $targetLanguage) {
            $this->logger->warning('[ApiController] Parent page validation failed: language mismatch', [
                'parentPageId' => $parentPageId,
                'parentLanguage' => $parentLanguage,
                'targetLanguage' => $targetLanguage
            ]);
            return [
                'valid' => false,
                'error' => 'Parent page must be in the same language as import target'
            ];
        }

        return [
            'valid' => true,
            'page' => $parentPage
        ];
    }

    /**
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    public function listPages(): DataResponse {
        try {
            $pages = $this->pageService->listPages();

            // PageService already includes permissions from Nextcloud's filesystem
            // Filter pages to only include those the user can read
            $filteredPages = [];
            foreach ($pages as $page) {
                if ($page['permissions']['canRead'] ?? false) {
                    $filteredPages[] = $page;
                }
            }

            return new DataResponse($filteredPages);
        } catch (\Exception $e) {
            // If IntraVox folder doesn't exist, return empty array
            // This allows the WelcomeScreen to be shown instead of an error
            if (strpos($e->getMessage(), 'IntraVox folder not found') !== false) {
                return new DataResponse([]);
            }
            return new DataResponse(
                ['error' => $e->getMessage()],
                Http::STATUS_INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    public function getPage(string $id): DataResponse {
        try {
            $page = $this->pageService->getPage($id);

            // PageService already includes permissions from Nextcloud's filesystem
            // which automatically respects GroupFolder ACL rules

            // Check if user can read (permissions are already in the page data)
            if (!($page['permissions']['canRead'] ?? false)) {
                return new DataResponse(
                    ['error' => 'Access denied'],
                    Http::STATUS_FORBIDDEN
                );
            }

            // Add breadcrumb to page response
            try {
                $page['breadcrumb'] = $this->pageService->getBreadcrumb($id);
            } catch (\Exception $e) {
                // Breadcrumb failed, but page is still valid
                $page['breadcrumb'] = [];
            }

            return new DataResponse($page);
        } catch (\Exception $e) {
            return new DataResponse(
                ['error' => $e->getMessage()],
                Http::STATUS_NOT_FOUND
            );
        }
    }

    /**
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    public function createPage(): DataResponse {
        try {
            $data = $this->request->getParams();

            // Extract parentPath from request if provided
            $parentPath = $data['parentPath'] ?? null;
            unset($data['parentPath']); // Remove from data array to avoid storing it

            // Check create permission on parent path using Nextcloud's filesystem permissions
            $checkPath = $parentPath ?? '';
            $folderPerms = $this->pageService->getFolderPermissions($checkPath);
            if (!$folderPerms['canCreate']) {
                return new DataResponse(
                    ['error' => 'Permission denied: cannot create pages in this location'],
                    Http::STATUS_FORBIDDEN
                );
            }

            $page = $this->pageService->createPage($data, $parentPath);
            return new DataResponse($page, Http::STATUS_CREATED);
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
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    public function updatePage(string $id): DataResponse {
        try {
            // First get the page to check permissions (from Nextcloud filesystem)
            $existingPage = $this->pageService->getPage($id);

            // Check write permission using Nextcloud's permissions
            if (!($existingPage['permissions']['canWrite'] ?? false)) {
                return new DataResponse(
                    ['error' => 'Permission denied: cannot edit this page'],
                    Http::STATUS_FORBIDDEN
                );
            }

            $data = $this->request->getParams();
            $page = $this->pageService->updatePage($id, $data);
            return new DataResponse($page);
        } catch (\InvalidArgumentException $e) {
            $this->logger->error('[updatePage] InvalidArgumentException: ' . $e->getMessage(), [
                'pageId' => $id,
                'trace' => $e->getTraceAsString(),
            ]);
            return new DataResponse(
                ['error' => $e->getMessage()],
                Http::STATUS_BAD_REQUEST
            );
        } catch (\Exception $e) {
            $this->logger->error('[updatePage] Exception: ' . $e->getMessage(), [
                'pageId' => $id,
                'trace' => $e->getTraceAsString(),
            ]);
            return new DataResponse(
                ['error' => $e->getMessage()],
                Http::STATUS_INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    public function deletePage(string $id): DataResponse {
        try {
            // First get the page to check permissions (from Nextcloud filesystem)
            $existingPage = $this->pageService->getPage($id);

            // Check delete permission using Nextcloud's permissions
            if (!($existingPage['permissions']['canDelete'] ?? false)) {
                return new DataResponse(
                    ['error' => 'Permission denied: cannot delete this page'],
                    Http::STATUS_FORBIDDEN
                );
            }

            $this->pageService->deletePage($id);
            return new DataResponse(['success' => true]);
        } catch (\Exception $e) {
            return new DataResponse(
                ['error' => $e->getMessage()],
                Http::STATUS_INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     * Upload media (image or video) for a page
     * Unified endpoint that stores all media in a single 'media' folder
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    public function uploadMedia(string $pageId): DataResponse {
        try {
            // First get the page to check permissions (from Nextcloud filesystem)
            $existingPage = $this->pageService->getPage($pageId);

            // Check write permission (uploading media requires write access)
            if (!($existingPage['permissions']['canWrite'] ?? false)) {
                return new DataResponse(
                    ['error' => 'Permission denied: cannot upload media to this page'],
                    Http::STATUS_FORBIDDEN
                );
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
     * @NoAdminRequired
     * @NoCSRFRequired
     */
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

            $exists = $this->pageService->checkMediaExists($pageId, $filename, $target);

            return new DataResponse(['exists' => $exists]);
        } catch (\Exception $e) {
            return new DataResponse(
                ['error' => $e->getMessage()],
                Http::STATUS_INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     * Upload media with original filename
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    public function uploadMediaWithName(string $pageId): DataResponse {
        try {
            // Check write permission
            $existingPage = $this->pageService->getPage($pageId);
            if (!($existingPage['permissions']['canWrite'] ?? false)) {
                return new DataResponse(
                    ['error' => 'Permission denied: cannot upload media to this page'],
                    Http::STATUS_FORBIDDEN
                );
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
     * @NoAdminRequired
     * @NoCSRFRequired
     */
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

            return new DataResponse(['media' => $mediaList]);
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
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    public function getResourcesMediaWithFolder(string $folder, string $filename) {
        $path = $folder . '/' . $filename;
        return $this->getResourcesMedia($path);
    }

    /**
     * Get media file from resources folder (globally readable)
     * @NoAdminRequired
     * @NoCSRFRequired
     */
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
     * @NoAdminRequired
     * @NoCSRFRequired
     */
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
     * @NoAdminRequired
     * @NoCSRFRequired
     */
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

    /**
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    public function getPageVersions(string $pageId): DataResponse {
        $this->logger->info('[ApiController::getPageVersions] Called with pageId: ' . $pageId);

        try {
            // First get the page to check permissions (from Nextcloud filesystem)
            $this->logger->info('[ApiController::getPageVersions] Getting page...');
            $existingPage = $this->pageService->getPage($pageId);
            $this->logger->info('[ApiController::getPageVersions] Got page, checking permissions...');

            // Check read permission using Nextcloud's permissions
            if (!($existingPage['permissions']['canRead'] ?? false)) {
                $this->logger->warning('[ApiController::getPageVersions] Access denied for pageId: ' . $pageId);
                return new DataResponse(
                    ['error' => 'Access denied'],
                    Http::STATUS_FORBIDDEN
                );
            }

            $this->logger->info('[ApiController::getPageVersions] Calling pageService->getPageVersions...');
            $versions = $this->pageService->getPageVersions($pageId);
            $this->logger->info('[ApiController::getPageVersions] Got ' . count($versions) . ' versions');
            return new DataResponse($versions);
        } catch (\Exception $e) {
            $this->logger->error('[ApiController::getPageVersions] Error: ' . $e->getMessage());
            return new DataResponse(
                ['error' => $e->getMessage()],
                Http::STATUS_INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    public function restorePageVersion(string $pageId, string $timestamp): DataResponse {
        try {
            // First get the page to check permissions (from Nextcloud filesystem)
            $existingPage = $this->pageService->getPage($pageId);

            // Check write permission (restoring requires write access)
            if (!($existingPage['permissions']['canWrite'] ?? false)) {
                return new DataResponse(
                    ['error' => 'Permission denied: cannot restore this page'],
                    Http::STATUS_FORBIDDEN
                );
            }

            $page = $this->pageService->restorePageVersion($pageId, (int)$timestamp);
            return new DataResponse($page);
        } catch (\Exception $e) {
            return new DataResponse(
                ['error' => $e->getMessage()],
                Http::STATUS_INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    public function updateVersionLabel(string $pageId, string $timestamp): DataResponse {
        try {
            // First get the page to check permissions (from Nextcloud filesystem)
            $existingPage = $this->pageService->getPage($pageId);

            // Check write permission using Nextcloud's permissions
            if (!($existingPage['permissions']['canWrite'] ?? false)) {
                return new DataResponse(
                    ['error' => 'Permission denied'],
                    Http::STATUS_FORBIDDEN
                );
            }

            $label = $this->request->getParam('label');
            $this->pageService->updateVersionLabel($pageId, (int)$timestamp, $label);
            return new DataResponse(['success' => true]);
        } catch (\Exception $e) {
            return new DataResponse(
                ['error' => $e->getMessage()],
                Http::STATUS_INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    public function getVersionContent(string $pageId, string $timestamp): DataResponse {
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

            $content = $this->pageService->getVersionContent($pageId, (int)$timestamp);
            return new DataResponse($content);
        } catch (\Exception $e) {
            return new DataResponse(
                ['error' => $e->getMessage()],
                Http::STATUS_INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    public function getCurrentPageContent(string $pageId): DataResponse {
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

            $content = $this->pageService->getCurrentPageContent($pageId);
            return new DataResponse($content);
        } catch (\Exception $e) {
            return new DataResponse(
                ['error' => $e->getMessage()],
                Http::STATUS_INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    public function getPageMetadata(string $pageId): DataResponse {
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

            $metadata = $this->pageService->getPageMetadata($pageId);
            return new DataResponse($metadata);
        } catch (\Exception $e) {
            return new DataResponse(
                ['error' => $e->getMessage()],
                Http::STATUS_INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    public function updatePageMetadata(string $pageId): DataResponse {
        try {
            // First get the page to check permissions (from Nextcloud filesystem)
            $existingPage = $this->pageService->getPage($pageId);

            // Check write permission using Nextcloud's permissions
            if (!($existingPage['permissions']['canWrite'] ?? false)) {
                return new DataResponse(
                    ['error' => 'Permission denied'],
                    Http::STATUS_FORBIDDEN
                );
            }

            $metadata = $this->request->getParams();
            $updated = $this->pageService->updatePageMetadata($pageId, $metadata);
            return new DataResponse($updated);
        } catch (\Exception $e) {
            return new DataResponse(
                ['error' => $e->getMessage()],
                Http::STATUS_INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    public function getMetavoxStatus(): DataResponse {
        try {
            $appManager = \OC::$server->getAppManager();
            $installed = $appManager->isInstalled('metavox') && $appManager->isEnabledForUser('metavox');

            return new DataResponse([
                'installed' => $installed,
                'enabled' => $installed
            ]);
        } catch (\Exception $e) {
            return new DataResponse(
                ['installed' => false, 'enabled' => false],
                Http::STATUS_OK
            );
        }
    }

    /**
     * Get MetaVox fields for the IntraVox groupfolder
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    public function getMetavoxFields(): DataResponse {
        try {
            $appManager = \OC::$server->getAppManager();
            if (!$appManager->isInstalled('metavox') || !$appManager->isEnabledForUser('metavox')) {
                return new DataResponse(['fields' => [], 'error' => 'MetaVox not available']);
            }

            // Get the IntraVox groupfolder ID
            $setupService = \OC::$server->get(\OCA\IntraVox\Service\SetupService::class);
            $groupfolderId = $setupService->getGroupFolderId();

            if ($groupfolderId <= 0) {
                return new DataResponse(['fields' => [], 'error' => 'IntraVox groupfolder not found']);
            }

            // Get MetaVox FieldService
            $fieldService = \OC::$server->get(\OCA\MetaVox\Service\FieldService::class);

            // Get fields assigned to this groupfolder (with full field data)
            $allFields = $fieldService->getAssignedFieldsWithDataForGroupfolder($groupfolderId);

            // Format fields for the frontend
            $fields = array_map(function($field) {
                return [
                    'field_name' => $field['field_name'] ?? '',
                    'field_label' => $field['field_label'] ?? $field['field_name'] ?? '',
                    'field_type' => $field['field_type'] ?? 'text',
                    'options' => $field['field_options'] ?? [],
                ];
            }, $allFields);

            return new DataResponse([
                'fields' => array_values($fields),
                'groupfolderId' => $groupfolderId
            ]);
        } catch (\Exception $e) {
            return new DataResponse(
                ['fields' => [], 'error' => $e->getMessage()],
                Http::STATUS_OK
            );
        }
    }

    /**
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    public function checkPageCacheStatus(string $pageId): DataResponse {
        try {
            $status = $this->pageService->checkPageCacheStatus($pageId);
            return new DataResponse($status);
        } catch (\Exception $e) {
            return new DataResponse(
                ['error' => $e->getMessage()],
                Http::STATUS_INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     * Get news pages for the News widget
     *
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    public function getNews(): DataResponse {
        try {
            $sourcePath = $this->request->getParam('sourcePath', '');
            $sourcePageId = $this->request->getParam('sourcePageId', '');
            $filtersJson = $this->request->getParam('filters', '[]');
            $filterOperator = $this->request->getParam('filterOperator', 'AND');
            $limit = (int) $this->request->getParam('limit', 5);
            $sortBy = $this->request->getParam('sortBy', 'modified');
            $sortOrder = $this->request->getParam('sortOrder', 'desc');
            $filterPublished = $this->request->getParam('filterPublished', 'false') === 'true';

            // Parse filters JSON
            $filters = json_decode($filtersJson, true) ?? [];

            // Validate limit
            $limit = max(1, min($limit, 50));

            // Validate sortBy
            if (!in_array($sortBy, ['modified', 'title'])) {
                $sortBy = 'modified';
            }

            // Validate sortOrder
            if (!in_array($sortOrder, ['asc', 'desc'])) {
                $sortOrder = 'desc';
            }

            // Validate filterOperator
            if (!in_array($filterOperator, ['AND', 'OR'])) {
                $filterOperator = 'AND';
            }

            $result = $this->pageService->getNewsPages(
                $sourcePath,
                $filters,
                $filterOperator,
                $limit,
                $sortBy,
                $sortOrder,
                !empty($sourcePageId) ? $sourcePageId : null,
                $filterPublished
            );

            return new DataResponse($result);
        } catch (\Exception $e) {
            $this->logger->error('News widget error: ' . $e->getMessage());
            return new DataResponse(
                ['error' => $e->getMessage()],
                Http::STATUS_INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    public function searchPages(string $query): DataResponse {
        try {
            if (strlen($query) < 2) {
                return new DataResponse([
                    'results' => [],
                    'query' => $query,
                    'message' => 'Query too short'
                ]);
            }

            $results = $this->pageService->searchPages($query);

            // Filter results based on Nextcloud's permissions (already in the results)
            $filteredResults = [];
            foreach ($results as $result) {
                if ($result['permissions']['canRead'] ?? false) {
                    $filteredResults[] = $result;
                }
            }

            return new DataResponse([
                'results' => $filteredResults,
                'query' => $query,
                'count' => count($filteredResults)
            ]);
        } catch (\Exception $e) {
            return new DataResponse(
                ['error' => $e->getMessage()],
                Http::STATUS_INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    public function getBreadcrumb(string $id): DataResponse {
        try {
            // First get the page to check permissions (from Nextcloud filesystem)
            $existingPage = $this->pageService->getPage($id);

            // Check read permission using Nextcloud's permissions
            if (!($existingPage['permissions']['canRead'] ?? false)) {
                return new DataResponse(
                    ['error' => 'Access denied'],
                    Http::STATUS_FORBIDDEN
                );
            }

            $breadcrumb = $this->pageService->getBreadcrumb($id);
            return new DataResponse($breadcrumb);
        } catch (\Exception $e) {
            return new DataResponse(
                ['error' => $e->getMessage()],
                Http::STATUS_NOT_FOUND
            );
        }
    }

    /**
     * Get the full page tree structure
     * Used for the "View structure" modal to show all accessible pages
     *
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    public function getPageTree(?string $currentPageId = null, ?string $language = null): DataResponse {
        try {
            $tree = $this->pageService->getPageTree($currentPageId, $language);

            // Filter tree to only include pages user can read
            // PageService already includes Nextcloud permissions in each page
            $filteredTree = $this->filterTreeByPermissions($tree);

            return new DataResponse([
                'tree' => $filteredTree
            ]);
        } catch (\Exception $e) {
            return new DataResponse(
                ['error' => $e->getMessage()],
                Http::STATUS_INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     * Filter page tree to only include pages user can read
     */
    private function filterTreeByPermissions(array $tree): array {
        $filtered = [];
        foreach ($tree as $item) {
            if ($item['permissions']['canRead'] ?? false) {
                if (!empty($item['children'])) {
                    $item['children'] = $this->filterTreeByPermissions($item['children']);
                }
                $filtered[] = $item;
            }
        }
        return $filtered;
    }

    /**
     * Get current user's permissions for IntraVox
     *
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    public function getPermissions(?string $path = null): DataResponse {
        try {
            $checkPath = $path ?? '';
            // Use Nextcloud's native filesystem permissions
            $permissions = $this->pageService->getFolderPermissions($checkPath);

            $response = [
                'path' => $checkPath,
                'permissions' => $permissions
            ];

            return new DataResponse($response);
        } catch (\Exception $e) {
            return new DataResponse(
                ['error' => $e->getMessage()],
                Http::STATUS_INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     * Run IntraVox setup (create GroupFolder)
     * Admin only - creates the IntraVox GroupFolder
     */
    public function runSetup(): DataResponse {
        // Security: Only admins can run setup
        if (!$this->isAdmin()) {
            return new DataResponse(
                ['error' => 'Admin access required'],
                Http::STATUS_FORBIDDEN
            );
        }

        try {
            $this->logger->info('[ApiController] Running setup');

            $result = $this->setupService->setup();

            // Run _resources folder migration
            $this->logger->info('[ApiController] Running _resources migration');
            $migrationResult = $this->setupService->migrateResourcesFolders();
            $this->logger->info('[ApiController] Migration result: ' . ($migrationResult ? 'success' : 'failed'));

            return new DataResponse([
                'success' => true,
                'message' => 'Setup completed successfully',
                'result' => $result,
                'migration' => $migrationResult,
            ]);
        } catch (\Exception $e) {
            $this->logger->error('[ApiController] Setup failed: ' . $e->getMessage());
            return new DataResponse(
                [
                    'success' => false,
                    'message' => 'Setup failed: ' . $e->getMessage(),
                ],
                Http::STATUS_INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     * Get video domain whitelist
     *
     * @NoCSRFRequired
     * @NoAdminRequired
     */
    public function getVideoDomains(): DataResponse {
        $domains = $this->config->getAppValue(
            Application::APP_ID,
            'video_domains',
            Constants::getDefaultVideoDomainsJson()
        );

        // Decode the stored JSON
        $decoded = json_decode($domains, true);

        // Only use defaults if JSON decode FAILED (null), not for empty array
        // This allows admins to explicitly block all video embeds by removing all domains
        if ($decoded === null) {
            $decoded = Constants::DEFAULT_VIDEO_DOMAINS;
        }

        return new DataResponse(['domains' => $decoded]);
    }

    /**
     * Get domain category for warning system
     * Returns: recommended, commercial, discouraged, or blocked
     */
    private function getDomainCategory(string $host): array {
        // Category 1: Recommended - privacy-friendly platforms
        $recommended = [
            'video.edu.nl',
            'peertube.tv',
            'framatube.org',
            'tilvids.com',
            'peertube.social',
            'video.ploud.fr',
            'diode.zone',
            'tube.privacytools.io',
            'peertube.debian.social',
            'video.linux.it',
        ];

        // Category 2: Commercial but relatively safe (business platforms)
        $commercial = [
            'vimeo.com',
            'wistia.com',
            'loom.com',
            'streamable.com',
            'bunny.net',
            'bunnycdn.com',
        ];

        // Category 3: Discouraged - major tracking/privacy concerns
        $discouraged = [
            'youtube.com',
            'youtu.be',
            'dailymotion.com',
            'tiktok.com',
            'facebook.com',
            'fb.watch',
            'instagram.com',
            'twitter.com',
            'x.com',
            'twitch.tv',
        ];

        foreach ($recommended as $pattern) {
            if (str_contains($host, $pattern)) {
                return ['category' => 'recommended', 'level' => 1];
            }
        }

        foreach ($commercial as $pattern) {
            if (str_contains($host, $pattern)) {
                return ['category' => 'commercial', 'level' => 2];
            }
        }

        foreach ($discouraged as $pattern) {
            if (str_contains($host, $pattern)) {
                return ['category' => 'discouraged', 'level' => 3];
            }
        }

        // Unknown domains - treat as custom PeerTube instances (allowed)
        return ['category' => 'custom', 'level' => 1];
    }

    /**
     * Set video domain whitelist
     * Warning-based system: all domains allowed, but with category warnings
     * Admin only
     *
     * @NoCSRFRequired
     */
    public function setVideoDomains(): DataResponse {
        // Manual admin check since @AuthorizedAdminSetting has dependency issues
        if (!$this->isAdmin()) {
            return new DataResponse([
                'success' => false,
                'message' => 'Only administrators can change video domain settings',
            ], Http::STATUS_FORBIDDEN);
        }

        // Get domains from request - handle both JSON body and form data
        $domains = $this->request->getParam('domains');

        // If domains is null, try parsing JSON body directly
        if ($domains === null) {
            $body = file_get_contents('php://input');
            $data = json_decode($body, true);
            $domains = $data['domains'] ?? [];
            $this->logger->debug('[ApiController] Parsed JSON body for domains: ' . json_encode($domains));
        }

        // Ensure domains is an array
        if (!is_array($domains)) {
            $domains = [];
        }

        // Validate and categorize domains
        $validDomains = [];
        $invalidDomains = [];
        $domainCategories = [];
        $warnings = [];

        foreach ($domains as $domain) {
            $domain = trim($domain);
            if (empty($domain)) {
                continue;
            }

            // Must be HTTPS - this is a hard requirement for security
            if (!str_starts_with($domain, 'https://')) {
                $invalidDomains[] = $domain . ' (HTTPS required for security)';
                continue;
            }

            // Valid URL check
            if (!filter_var($domain, FILTER_VALIDATE_URL)) {
                $invalidDomains[] = $domain . ' (invalid URL format)';
                continue;
            }

            // Get domain category
            $parsedUrl = parse_url($domain);
            $host = $parsedUrl['host'] ?? '';
            $category = $this->getDomainCategory($host);

            // Remove trailing slash
            $domain = rtrim($domain, '/');

            $validDomains[] = $domain;
            $domainCategories[$domain] = $category;

            // Add warnings for non-recommended domains
            if ($category['category'] === 'commercial') {
                $warnings[] = $host . ': Commercial platform - consider privacy-friendly alternatives like PeerTube';
            } elseif ($category['category'] === 'discouraged') {
                $warnings[] = $host . ': This platform has significant tracking and privacy concerns. Consider using PeerTube instead.';
            }
        }

        // If there are invalid domains (HTTP or malformed), return error
        if (!empty($invalidDomains)) {
            return new DataResponse([
                'success' => false,
                'message' => 'Some domains are not valid: ' . implode(', ', $invalidDomains),
                'invalidDomains' => $invalidDomains,
            ], Http::STATUS_BAD_REQUEST);
        }

        // Save domains - all valid HTTPS domains are allowed
        $this->config->setAppValue(
            Application::APP_ID,
            'video_domains',
            json_encode(array_unique($validDomains))
        );

        $this->logger->info('[ApiController] Video domains updated: ' . implode(', ', $validDomains));

        // Return success with warnings if applicable
        $response = [
            'success' => true,
            'domains' => $validDomains,
            'categories' => $domainCategories,
        ];

        if (!empty($warnings)) {
            $response['warnings'] = $warnings;
        }

        return new DataResponse($response);
    }

    /**
     * Get engagement settings (reactions & comments)
     *
     * @NoCSRFRequired
     * @NoAdminRequired
     */
    public function getEngagementSettings(): DataResponse {
        return new DataResponse($this->engagementSettings->getAll());
    }

    /**
     * Update engagement settings
     * Admin only
     *
     * @NoCSRFRequired
     */
    public function setEngagementSettings(): DataResponse {
        // Only admins can change engagement settings
        if (!$this->isAdmin()) {
            return new DataResponse([
                'success' => false,
                'message' => 'Only administrators can change engagement settings',
            ], Http::STATUS_FORBIDDEN);
        }

        // Get settings from request body
        $body = file_get_contents('php://input');
        $settings = json_decode($body, true) ?? [];

        try {
            $updated = $this->engagementSettings->updateAll($settings);

            $this->logger->info('[ApiController] Engagement settings updated', $settings);

            return new DataResponse([
                'success' => true,
                'settings' => $updated,
            ]);
        } catch (\Exception $e) {
            $this->logger->error('[ApiController] Failed to update engagement settings: ' . $e->getMessage());
            return new DataResponse([
                'success' => false,
                'message' => $e->getMessage(),
            ], Http::STATUS_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Get publication settings (MetaVox field names for date filtering)
     *
     * @NoCSRFRequired
     * @NoAdminRequired
     */
    public function getPublicationSettings(): DataResponse {
        return new DataResponse($this->publicationSettings->getAll());
    }

    /**
     * Update publication settings
     * Admin only
     *
     * @NoCSRFRequired
     */
    public function setPublicationSettings(): DataResponse {
        // Only admins can change publication settings
        if (!$this->isAdmin()) {
            return new DataResponse([
                'success' => false,
                'message' => 'Only administrators can change publication settings',
            ], Http::STATUS_FORBIDDEN);
        }

        // Get settings from request body
        $body = file_get_contents('php://input');
        $settings = json_decode($body, true) ?? [];

        try {
            $updated = $this->publicationSettings->updateAll($settings);

            $this->logger->info('[ApiController] Publication settings updated', $settings);

            return new DataResponse([
                'success' => true,
                'settings' => $updated,
            ]);
        } catch (\Exception $e) {
            $this->logger->error('[ApiController] Failed to update publication settings: ' . $e->getMessage());
            return new DataResponse([
                'success' => false,
                'message' => $e->getMessage(),
            ], Http::STATUS_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Set telemetry settings (enable/disable anonymous usage statistics)
     * Admin only
     *
     * @return DataResponse
     */
    public function setTelemetrySettings(): DataResponse {
        // Only admins can change telemetry settings
        if (!$this->isAdmin()) {
            return new DataResponse([
                'success' => false,
                'message' => 'Only administrators can change telemetry settings',
            ], Http::STATUS_FORBIDDEN);
        }

        // Get settings from request body
        $body = file_get_contents('php://input');
        $settings = json_decode($body, true) ?? [];

        try {
            $enabled = isset($settings['enabled']) ? (bool)$settings['enabled'] : false;
            $this->telemetryService->setEnabled($enabled);

            $this->logger->info('[ApiController] Telemetry settings updated', ['enabled' => $enabled]);

            return new DataResponse([
                'success' => true,
                'enabled' => $enabled,
            ]);
        } catch (\Exception $e) {
            $this->logger->error('[ApiController] Failed to update telemetry settings: ' . $e->getMessage());
            return new DataResponse([
                'success' => false,
                'message' => $e->getMessage(),
            ], Http::STATUS_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Import from uploaded ZIP file
     * Admin only
     *
     * @NoCSRFRequired
     * @return JSONResponse
     */
    public function importZip(): JSONResponse {
        // Security: Only admins can import
        if (!$this->isAdmin()) {
            return new JSONResponse(
                ['error' => 'Admin access required'],
                Http::STATUS_FORBIDDEN
            );
        }

        try {
            $file = $this->request->getUploadedFile('file');

            if (!$file || $file['error'] !== UPLOAD_ERR_OK) {
                $errorMessages = [
                    UPLOAD_ERR_INI_SIZE => 'File exceeds upload_max_filesize',
                    UPLOAD_ERR_FORM_SIZE => 'File exceeds MAX_FILE_SIZE',
                    UPLOAD_ERR_PARTIAL => 'File was only partially uploaded',
                    UPLOAD_ERR_NO_FILE => 'No file was uploaded',
                    UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary folder',
                    UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk',
                    UPLOAD_ERR_EXTENSION => 'Upload stopped by PHP extension',
                ];
                $errorCode = $file['error'] ?? UPLOAD_ERR_NO_FILE;
                $errorMessage = $errorMessages[$errorCode] ?? 'Unknown upload error';
                return new JSONResponse(['error' => $errorMessage], Http::STATUS_BAD_REQUEST);
            }

            // Validate file type
            $finfo = new \finfo(FILEINFO_MIME_TYPE);
            $mimeType = $finfo->file($file['tmp_name']);

            // Accept both application/zip and application/x-zip-compressed
            if (!in_array($mimeType, ['application/zip', 'application/x-zip-compressed', 'application/octet-stream'])) {
                return new JSONResponse(
                    ['error' => 'Invalid file type. Expected ZIP file, got: ' . $mimeType],
                    Http::STATUS_BAD_REQUEST
                );
            }

            // Additional check: verify it's actually a ZIP by checking magic bytes
            $handle = fopen($file['tmp_name'], 'rb');
            $header = fread($handle, 4);
            fclose($handle);

            // ZIP files start with PK (0x50 0x4B)
            if (substr($header, 0, 2) !== 'PK') {
                return new JSONResponse(
                    ['error' => 'Invalid ZIP file format'],
                    Http::STATUS_BAD_REQUEST
                );
            }

            $importComments = $this->request->getParam('importComments', '1') === '1';
            $overwrite = $this->request->getParam('overwrite', '0') === '1';
            $autoCreateMetaVoxFields = $this->request->getParam('autoCreateMetaVoxFields', '0') === '1';

            $zipContent = file_get_contents($file['tmp_name']);
            $stats = $this->importService->importFromZip($zipContent, $importComments, $overwrite, null, $autoCreateMetaVoxFields);

            return new JSONResponse([
                'success' => true,
                'stats' => $stats,
            ]);
        } catch (\Exception $e) {
            $errorId = uniqid('err_');
            $this->logger->error('IntraVox Import: ZIP import failed', [
                'errorId' => $errorId,
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
            return new JSONResponse(
                ['error' => 'Import failed. Please check the ZIP file format and try again.', 'errorId' => $errorId],
                Http::STATUS_INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     * Import from Confluence HTML export ZIP file
     * Admin only
     *
     * @NoCSRFRequired
     * @return JSONResponse
     */
    public function importConfluenceHtml(): JSONResponse {
        // Security: Only admins can import
        if (!$this->isAdmin()) {
            return new JSONResponse(
                ['error' => 'Admin access required'],
                Http::STATUS_FORBIDDEN
            );
        }

        $this->logger->info('Confluence HTML import endpoint called');

        try {
            $file = $this->request->getUploadedFile('file');
            $language = $this->request->getParam('language', 'nl');
            $parentPageId = $this->request->getParam('parentPageId', null);

            $this->logger->info('Import request received', [
                'file' => $file ? $file['name'] : 'no file',
                'language' => $language,
                'parentPageId' => $parentPageId,
            ]);

            if (!$file || $file['error'] !== UPLOAD_ERR_OK) {
                $this->logger->warning('File upload error', ['error' => $file['error'] ?? 'no file']);
                return new JSONResponse(
                    ['error' => 'No file uploaded or upload error'],
                    Http::STATUS_BAD_REQUEST
                );
            }

            // Validate ZIP file
            $finfo = new \finfo(FILEINFO_MIME_TYPE);
            $mimeType = $finfo->file($file['tmp_name']);

            if (!in_array($mimeType, ['application/zip', 'application/x-zip-compressed', 'application/octet-stream'])) {
                return new JSONResponse(
                    ['error' => 'Invalid file type. Expected ZIP file'],
                    Http::STATUS_BAD_REQUEST
                );
            }

            // Security: Validate parentPageId before import (IDOR prevention)
            if ($parentPageId) {
                $validation = $this->validateParentPageId($parentPageId, $language);
                if (!$validation['valid']) {
                    return new JSONResponse(
                        ['error' => $validation['error']],
                        Http::STATUS_BAD_REQUEST
                    );
                }
            }

            $this->logger->info('Starting Confluence HTML import', [
                'filename' => $file['name'],
                'size' => $file['size'],
                'language' => $language,
            ]);

            // Create HTML importer
            $htmlImporter = new ConfluenceHtmlImporter($this->logger);
            $confluenceImporter = new ConfluenceImporter($this->logger);

            // Import from ZIP
            $intermediateFormat = $htmlImporter->importFromZip($file['tmp_name'], $language);

            $this->logger->info('Parsed Confluence HTML export', [
                'pages' => count($intermediateFormat->pages),
                'media' => count($intermediateFormat->mediaDownloads),
            ]);

            // Convert to IntraVox export format
            $export = $this->convertIntermediateToExport($confluenceImporter, $intermediateFormat);

            // Log hierarchy before setting parent
            $this->logger->info('Export hierarchy BEFORE setting parent', [
                'pages' => array_map(function($p) {
                    return [
                        'title' => $p['content']['title'] ?? 'unknown',
                        'uniqueId' => substr($p['uniqueId'], 0, 20),
                        'parentUniqueId' => isset($p['parentUniqueId']) ? substr($p['parentUniqueId'], 0, 20) : 'NONE'
                    ];
                }, $export['pages'])
            ]);

            // Set parent page for ROOT imported pages (pages without a parent in the import)
            if ($parentPageId) {
                $rootCount = 0;
                foreach ($export['pages'] as &$page) {
                    // Only set parent for pages that don't already have a parent
                    if (empty($page['parentUniqueId'])) {
                        $page['parentUniqueId'] = $parentPageId;
                        $rootCount++;
                    }
                }
                unset($page); // Break reference
                $this->logger->info('Set parent page for root imported pages', [
                    'parentPageId' => $parentPageId,
                    'rootPagesCount' => $rootCount,
                    'totalPages' => count($export['pages'])
                ]);

                // Log hierarchy AFTER setting parent
                $this->logger->info('Export hierarchy AFTER setting parent', [
                    'pages' => array_map(function($p) {
                        return [
                            'title' => $p['content']['title'] ?? 'unknown',
                            'uniqueId' => substr($p['uniqueId'], 0, 20),
                            'parentUniqueId' => isset($p['parentUniqueId']) ? substr($p['parentUniqueId'], 0, 20) : 'NONE'
                        ];
                    }, $export['pages'])
                ]);
            }

            // Create temporary directory for export
            $tempDir = $this->tempManager->getTemporaryFolder();

            // Write export.json
            file_put_contents($tempDir . '/export.json', json_encode($export, JSON_PRETTY_PRINT));

            // Create ZIP
            $zipPath = $tempDir . '/confluence-html-import.zip';
            $this->createZipFromDirectory($tempDir, $zipPath);

            // Import the ZIP with parent page ID
            $zipContent = file_get_contents($zipPath);
            $stats = $this->importService->importFromZip($zipContent, true, false, $parentPageId);

            // Cleanup
            $this->cleanupTempDir($tempDir);

            return new JSONResponse([
                'success' => true,
                'stats' => $stats,
                'pages' => count($intermediateFormat->pages),
                'message' => 'Confluence HTML export imported successfully',
            ]);
        } catch (\Exception $e) {
            $errorId = uniqid('err_');
            $this->logger->error('Confluence HTML import failed', [
                'errorId' => $errorId,
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
            return new JSONResponse(
                ['error' => 'Confluence import failed. Please check the export format and try again.', 'errorId' => $errorId],
                Http::STATUS_INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     * Convert intermediate format to IntraVox export format
     */
    private function convertIntermediateToExport(ConfluenceImporter $importer, $intermediateFormat): array {
        // Use reflection to access the protected method
        $reflectionClass = new \ReflectionClass($importer);
        $method = $reflectionClass->getMethod('convertToIntraVoxExport');
        $method->setAccessible(true);

        $export = $method->invoke($importer, $intermediateFormat);

        return $export;
    }

    /**
     * Create ZIP archive from directory
     */
    private function createZipFromDirectory(string $sourceDir, string $zipPath): void {
        $zip = new \ZipArchive();
        if ($zip->open($zipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
            throw new \Exception('Failed to create ZIP archive');
        }

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
                if ($relativePath !== 'confluence-html-import.zip') {
                    $zip->addFile($filePath, $relativePath);
                }
            }
        }

        $zip->close();
    }

    /**
     * Cleanup temporary directory
     */
    private function cleanupTempDir(string $dir): void {
        if (!is_dir($dir)) {
            return;
        }

        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($files as $file) {
            if ($file->isDir()) {
                @rmdir($file->getPathname());
            } else {
                @unlink($file->getPathname());
            }
        }
        @rmdir($dir);
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
        if (empty($path)) {
            return '';
        }

        // 1. Check for null bytes FIRST (can bypass extension checks)
        if (strpos($path, "\0") !== false) {
            throw new \InvalidArgumentException('Invalid path: null bytes detected');
        }

        // 2. URL decode and check for double-encoding attacks
        $decoded = urldecode($path);
        if ($decoded !== $path) {
            // Path was URL-encoded, decode once more to detect double-encoding
            $doubleDecoded = urldecode($decoded);
            if (strpos($doubleDecoded, '..') !== false ||
                strpos($doubleDecoded, '\\') !== false ||
                strpos($doubleDecoded, "\0") !== false) {
                throw new \InvalidArgumentException('Path traversal detected');
            }
            $path = $decoded;
        }

        // 3. Unicode normalization (prevent NFD/NFC attacks)
        if (class_exists('Normalizer')) {
            $normalized = \Normalizer::normalize($path, \Normalizer::FORM_C);
            if ($normalized === false) {
                throw new \InvalidArgumentException('Invalid unicode in path');
            }
            $path = $normalized;
        }

        // 4. Convert backslashes to forward slashes
        $path = str_replace('\\', '/', $path);

        // 5. Remove leading/trailing slashes and dots
        $path = trim($path, '/.');

        // 6. Detect directory traversal patterns
        if (preg_match('#(\.\./|/\.\.|\.\.$|^\.\./)#', $path)) {
            throw new \InvalidArgumentException('Path traversal detected');
        }

        // 7. Validate characters - only allow safe path characters
        if (!empty($path) && !preg_match('#^[a-zA-Z0-9/_\-\.]+$#', $path)) {
            throw new \InvalidArgumentException('Invalid characters in path');
        }

        // 8. Split into segments and validate each
        if (!empty($path)) {
            $segments = explode('/', $path);
            foreach ($segments as $segment) {
                // Empty segments (double slashes)
                if (empty($segment) || $segment === '.' || $segment === '..') {
                    throw new \InvalidArgumentException('Invalid path segment');
                }

                // Hidden files (starting with dot) - except _media, _resources
                if (substr($segment, 0, 1) === '.' && $segment !== '.') {
                    throw new \InvalidArgumentException('Hidden files not allowed');
                }

                // Block executable PHP extensions
                if (preg_match('/\.(php|phtml|php[345]|phar|phps|pht|cgi|pl|sh|bash)$/i', $segment)) {
                    throw new \InvalidArgumentException('Executable files not allowed');
                }
            }
        }

        return $path;
    }

    /**
     * Get share info for a page (NC Files share detection).
     *
     * Checks if an NC Files share link exists for the page or its parent folder.
     * Used by the ShareButton component to determine if the share button should be shown.
     *
     * @NoAdminRequired
     * @NoCSRFRequired
     * @param string $uniqueId The page's unique ID
     * @return JSONResponse
     */
    public function getShareInfo(string $uniqueId): JSONResponse {
        try {
            // Get the page to verify it exists and get its language
            $page = $this->pageService->getPage($uniqueId);

            // Check read permission
            if (!($page['permissions']['canRead'] ?? false)) {
                return new JSONResponse([
                    'error' => 'Access denied'
                ], Http::STATUS_FORBIDDEN);
            }

            $language = $page['language'] ?? 'en';
            $user = $this->userSession->getUser();
            $userId = $user ? $user->getUID() : null;

            // Get share info from PublicShareService
            $shareInfo = $this->publicShareService->getShareInfoForPage($uniqueId, $language, $userId);

            return new JSONResponse($shareInfo);
        } catch (\Exception $e) {
            $this->logger->error('[ApiController] Failed to get share info', [
                'uniqueId' => $uniqueId,
                'error' => $e->getMessage()
            ]);
            return new JSONResponse([
                'hasShare' => false,
                'reason' => 'error',
                'error' => $e->getMessage()
            ], Http::STATUS_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Get all active public share links on IntraVox content.
     * Admin-only endpoint for the sharing overview tab.
     */
    public function getActiveShares(): JSONResponse {
        if (!$this->isAdmin()) {
            return new JSONResponse(['error' => 'Admin access required'], Http::STATUS_FORBIDDEN);
        }

        try {
            $shares = $this->publicShareService->getActiveShares();
            return new JSONResponse($shares);
        } catch (\Exception $e) {
            $this->logger->error('[ApiController] Failed to get active shares', [
                'error' => $e->getMessage()
            ]);
            return new JSONResponse(['error' => 'Failed to retrieve shares'], Http::STATUS_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Get page data via NC share token (anonymous access).
     *
     * This endpoint allows anonymous users to fetch page data when they have
     * a valid NC Files share token. The share must cover the requested page.
     *
     * IMPORTANT: This returns READ-ONLY data. No write operations are possible
     * through this endpoint regardless of NC share permissions.
     *
     * @PublicPage
     * @NoCSRFRequired
     * @AnonRateThrottle(limit=60, period=60)
     * @BruteForceProtection(action="intravox_share_page")
     * @param string $token The NC share token
     * @param string $uniqueId The page's unique ID
     * @return JSONResponse
     */
    #[PublicPage]
    #[NoCSRFRequired]
    #[AnonRateThrottle(limit: 60, period: 60)]
    #[BruteForceProtection(action: 'intravox_share_page')]
    public function getPageByShare(string $token, string $uniqueId): JSONResponse {
        // Validate token format first (cheap check)
        if (!$this->isValidShareTokenFormat($token)) {
            $this->registerShareBruteForceAttempt();
            return $this->shareNotFoundResponse();
        }

        // NC sharing must be enabled
        $ncAllowsLinks = $this->config->getAppValue('core', 'shareapi_allow_links', 'yes') === 'yes';
        if (!$ncAllowsLinks) {
            return $this->shareNotFoundResponse();
        }

        // Check share password
        $pwDenied = $this->checkSharePasswordOrDeny($token);
        if ($pwDenied !== null) {
            return $pwDenied;
        }

        try {
            // Determine language from page or use default
            // First try to get language from existing page data
            $language = 'en'; // Default
            try {
                $existingPage = $this->pageService->getPage($uniqueId);
                $language = $existingPage['language'] ?? 'en';
            } catch (\Exception $e) {
                // Page not found yet, will be handled by validateShareAccess
            }

            // Validate share access (password already verified via session)
            $sessionPw = $this->getSharePasswordFromSession($token);
            $validation = $this->publicShareService->validateShareAccess($token, $uniqueId, $language, $sessionPw);

            if (!$validation['valid']) {
                $reason = $validation['reason'] ?? '';
                if ($reason === 'password_required' || $reason === 'invalid_password') {
                    return new JSONResponse([
                        'error' => 'Password required',
                        'passwordRequired' => true,
                    ], Http::STATUS_UNAUTHORIZED);
                }
                $this->registerShareBruteForceAttempt();
                return $this->shareNotFoundResponse();
            }

            // Get the page data - this comes from validateShareAccess
            $pageData = $validation['pageData'] ?? null;

            if ($pageData === null) {
                $this->registerShareBruteForceAttempt();
                return $this->shareNotFoundResponse();
            }

            // Sanitize the response - only include safe fields for public access
            $sanitizedPage = $this->sanitizePageForPublicAccess($pageData);

            // Audit: log successful public page access
            $this->logger->info('[PublicShare] Page accessed', [
                'uniqueId' => $uniqueId,
                'ip' => $this->request->getRemoteAddress(),
            ]);

            // Add public access metadata
            $sanitizedPage['isPublicShare'] = true;
            $sanitizedPage['shareToken'] = $token;
            $sanitizedPage['permissions'] = [
                'canRead' => true,
                'canWrite' => false,
                'canDelete' => false,
                'canCreate' => false,
            ];

            // Add share-scoped breadcrumb
            try {
                $share = $this->publicShareService->getShareByToken($token);
                if ($share !== null) {
                    $shareScopePath = $this->publicShareService->resolveShareScopePath($share);
                    if ($shareScopePath !== null) {
                        $relPath = $shareScopePath;
                        if (str_starts_with($relPath, 'files/')) {
                            $relPath = substr($relPath, 6);
                        }
                        // Compute the page's relative path from the filesystem path
                        // pagePath is like "/__groupfolders/1/files/nl/afdeling/marketing/campagnes/campagnes.json"
                        // We need: "nl/afdeling/marketing/campagnes"
                        $pagePath = $validation['pagePath'] ?? '';
                        $pageRelPath = $pagePath;
                        // Strip __groupfolders/{id}/files/ prefix
                        if (preg_match('#__groupfolders/\d+/files/(.+)$#', $pageRelPath, $m)) {
                            $pageRelPath = $m[1];
                        }
                        // Remove filename (e.g., campagnes.json) to get folder path
                        $pageRelPath = dirname($pageRelPath);
                        if ($pageRelPath === '.') {
                            $pageRelPath = '';
                        }
                        // Add path to pageData for breadcrumb builder
                        $pageDataWithPath = $pageData;
                        $pageDataWithPath['path'] = $pageRelPath;
                        $sanitizedPage['breadcrumb'] = $this->buildShareScopedBreadcrumb($pageDataWithPath, $relPath, $language);
                    }
                }
            } catch (\Exception $e) {
                $this->logger->debug('[ApiController] Could not build share breadcrumb', [
                    'error' => $e->getMessage()
                ]);
                $sanitizedPage['breadcrumb'] = [];
            }

            return new JSONResponse($sanitizedPage);

        } catch (\Exception $e) {
            $this->logger->error('[ApiController] Error in getPageByShare', [
                'token' => '***',
                'uniqueId' => $uniqueId,
                'error' => $e->getMessage()
            ]);
            return $this->shareNotFoundResponse();
        }
    }

    /**
     * Get navigation for a public share, filtered by share scope.
     *
     * Only navigation items whose pages fall within the share target path are returned.
     * External URL items are always included.
     *
     * @PublicPage
     * @NoCSRFRequired
     * @AnonRateThrottle(limit=60, period=60)
     * @param string $token The NC share token
     * @return JSONResponse
     */
    #[PublicPage]
    #[NoCSRFRequired]
    #[AnonRateThrottle(limit: 60, period: 60)]
    public function getNavigationByShare(string $token): JSONResponse {
        // Validate token format
        if (!$this->isValidShareTokenFormat($token)) {
            return $this->shareNotFoundResponse();
        }

        // NC sharing must be enabled
        $ncAllowsLinks = $this->config->getAppValue('core', 'shareapi_allow_links', 'yes') === 'yes';
        if (!$ncAllowsLinks) {
            return $this->shareNotFoundResponse();
        }

        // Check share password
        $pwDenied = $this->checkSharePasswordOrDeny($token);
        if ($pwDenied !== null) {
            return $pwDenied;
        }

        try {
            // Get the share to determine scope
            $share = $this->publicShareService->getShareByToken($token);
            if ($share === null) {
                return $this->shareNotFoundResponse();
            }

            // Resolve the share's actual path via file_source on the GF storage.
            // file_target (e.g. "/afdeling") is unreliable for GroupFolders.
            $shareScopePath = $this->publicShareService->resolveShareScopePath($share);
            if ($shareScopePath === null) {
                $this->logger->debug('[ApiController] getNavigationByShare: could not resolve share scope path');
                return new JSONResponse(['navigation' => ['type' => 'dropdown', 'items' => []]]);
            }

            // shareScopePath is like "files/nl/afdeling" — extract language from it
            // Strip "files/" prefix to get "nl/afdeling"
            $relPath = $shareScopePath;
            if (str_starts_with($relPath, 'files/')) {
                $relPath = substr($relPath, 6);
            }

            $segments = explode('/', $relPath);
            $language = $segments[0] ?? null;

            if ($language === null || strlen($language) < 2 || strlen($language) > 3) {
                $this->logger->debug('[ApiController] getNavigationByShare: could not detect language from scope path', [
                    'shareScopePath' => $shareScopePath
                ]);
                return new JSONResponse(['navigation' => ['type' => 'dropdown', 'items' => []]]);
            }

            // Read navigation.json via system context (no user session needed)
            $navigation = $this->systemFileService->getNavigation($language);
            if ($navigation === null) {
                return new JSONResponse(['navigation' => ['type' => 'dropdown', 'items' => []]]);
            }

            // Normalize navigation items (pageId -> uniqueId)
            if (isset($navigation['items']) && is_array($navigation['items'])) {
                $navigation['items'] = $this->navigationService->normalizeNavigationItems($navigation['items']);
            }

            // Build page path map (uniqueId -> relative path within language folder)
            // Values are like "afdeling/afdeling.json" or "afdeling/hr/hr.json"
            $pagePathMap = $this->permissionService->buildPagePathMap($language);

            // Filter navigation items by share scope
            // shareScopeRelative is the path after "files/{language}/" — e.g. "afdeling"
            $shareScopeRelative = count($segments) > 1 ? implode('/', array_slice($segments, 1)) : '';

            if (isset($navigation['items']) && is_array($navigation['items'])) {
                $navigation['items'] = $this->publicShareService->filterNavigationByShareScope(
                    $navigation['items'],
                    $shareScopeRelative,
                    $pagePathMap
                );
            }

            $this->logger->info('[PublicShare] Navigation accessed', [
                'language' => $language,
                'ip' => $this->request->getRemoteAddress(),
            ]);

            return new JSONResponse([
                'navigation' => $navigation,
                'language' => $language,
            ]);

        } catch (\Exception $e) {
            $this->logger->error('[ApiController] Error in getNavigationByShare', [
                'token' => '***',
                'error' => $e->getMessage()
            ]);
            return new JSONResponse(['navigation' => ['type' => 'dropdown', 'items' => []]]);
        }
    }

    /**
     * Get page tree for a public share, filtered by share scope.
     *
     * Returns a hierarchical tree of pages within the share target folder.
     * The share root becomes the tree root — ancestors above it are not included.
     *
     * @PublicPage
     * @NoCSRFRequired
     * @AnonRateThrottle(limit=60, period=60)
     * @param string $token The NC share token
     * @return JSONResponse
     */
    #[PublicPage]
    #[NoCSRFRequired]
    #[AnonRateThrottle(limit: 60, period: 60)]
    public function getPageTreeByShare(string $token): JSONResponse {
        if (!$this->isValidShareTokenFormat($token)) {
            return $this->shareNotFoundResponse();
        }

        $ncAllowsLinks = $this->config->getAppValue('core', 'shareapi_allow_links', 'yes') === 'yes';
        if (!$ncAllowsLinks) {
            return $this->shareNotFoundResponse();
        }

        // Check share password
        $pwDenied = $this->checkSharePasswordOrDeny($token);
        if ($pwDenied !== null) {
            return $pwDenied;
        }

        try {
            $share = $this->publicShareService->getShareByToken($token);
            if ($share === null) {
                return $this->shareNotFoundResponse();
            }

            $shareScopePath = $this->publicShareService->resolveShareScopePath($share);
            if ($shareScopePath === null) {
                return new JSONResponse(['tree' => []]);
            }

            // shareScopePath is like "files/nl/afdeling" — extract language
            $relPath = $shareScopePath;
            if (str_starts_with($relPath, 'files/')) {
                $relPath = substr($relPath, 6);
            }

            $segments = explode('/', $relPath);
            $language = $segments[0] ?? null;

            if ($language === null || strlen($language) < 2 || strlen($language) > 3) {
                return new JSONResponse(['tree' => []]);
            }

            // Build full tree via system context (no user session needed)
            $tree = $this->systemFileService->getPageTree($language);

            // Extract subtree matching the share scope
            // scopePath is the full relative path like "nl/afdeling/hr"
            $scopePath = $relPath;
            $filteredTree = $this->extractSubtreeByScope($tree, $scopePath);

            // Strip permissions from all nodes (public = read-only)
            $filteredTree = $this->stripPermissionsFromTree($filteredTree);

            // Mark current page if provided (for page structure navigation highlighting)
            $currentPageId = $this->request->getParam('currentPageId');
            if ($currentPageId !== null && is_string($currentPageId) && $currentPageId !== '') {
                $filteredTree = $this->markCurrentPageInTree($filteredTree, $currentPageId);
            }

            $this->logger->info('[PublicShare] Page tree accessed', [
                'language' => $language,
                'ip' => $this->request->getRemoteAddress(),
            ]);

            return new JSONResponse(['tree' => $filteredTree]);

        } catch (\Exception $e) {
            $this->logger->error('[ApiController] Error in getPageTreeByShare', [
                'token' => '***',
                'error' => $e->getMessage()
            ]);
            return new JSONResponse(['tree' => []]);
        }
    }

    /**
     * Get news items for a public share.
     *
     * @PublicPage
     * @NoCSRFRequired
     * @AnonRateThrottle(limit=60, period=60)
     * @param string $token The NC share token
     * @return JSONResponse
     */
    #[PublicPage]
    #[NoCSRFRequired]
    #[AnonRateThrottle(limit: 60, period: 60)]
    public function getNewsByShare(string $token): JSONResponse {
        if (!$this->isValidShareTokenFormat($token)) {
            return $this->shareNotFoundResponse();
        }

        $ncAllowsLinks = $this->config->getAppValue('core', 'shareapi_allow_links', 'yes') === 'yes';
        if (!$ncAllowsLinks) {
            return $this->shareNotFoundResponse();
        }

        // Check share password
        $pwDenied = $this->checkSharePasswordOrDeny($token);
        if ($pwDenied !== null) {
            return $pwDenied;
        }

        try {
            $share = $this->publicShareService->getShareByToken($token);
            if ($share === null) {
                return $this->shareNotFoundResponse();
            }

            $shareScopePath = $this->publicShareService->resolveShareScopePath($share);
            if ($shareScopePath === null) {
                return new JSONResponse(['items' => [], 'total' => 0]);
            }

            // shareScopePath is like "files/nl/afdeling" — extract language
            $relPath = $shareScopePath;
            if (str_starts_with($relPath, 'files/')) {
                $relPath = substr($relPath, 6);
            }

            $segments = explode('/', $relPath);
            $language = $segments[0] ?? null;

            if ($language === null || strlen($language) < 2 || strlen($language) > 3) {
                return new JSONResponse(['items' => [], 'total' => 0]);
            }

            // Parse request parameters
            $sourcePageId = $this->request->getParam('sourcePageId', '');
            $sourcePath = $this->request->getParam('sourcePath', '');
            $limit = max(1, min((int) $this->request->getParam('limit', 5), 50));
            $sortBy = in_array($this->request->getParam('sortBy', 'modified'), ['modified', 'title'])
                ? $this->request->getParam('sortBy', 'modified') : 'modified';
            $sortOrder = in_array($this->request->getParam('sortOrder', 'desc'), ['asc', 'desc'])
                ? $this->request->getParam('sortOrder', 'desc') : 'desc';

            // Get news pages via system file service (no user session needed)
            $result = $this->systemFileService->getNewsPagesForShare(
                $language,
                !empty($sourcePageId) ? $sourcePageId : null,
                !empty($sourcePath) ? $sourcePath : null,
                $relPath,
                $token,
                $limit,
                $sortBy,
                $sortOrder
            );

            $this->logger->info('[PublicShare] News accessed', [
                'language' => $language,
                'itemCount' => count($result['items'] ?? []),
                'ip' => $this->request->getRemoteAddress(),
            ]);

            return new JSONResponse($result);

        } catch (\Exception $e) {
            $this->logger->error('[ApiController] Error in getNewsByShare', [
                'token' => '***',
                'error' => $e->getMessage()
            ]);
            return new JSONResponse(['items' => [], 'total' => 0]);
        }
    }

    /**
     * Extract a subtree matching the share scope path.
     *
     * Finds the node whose path matches the scope and returns it as the root.
     * If the scope is the language root (e.g., "nl"), returns the full tree.
     */
    private function extractSubtreeByScope(array $tree, string $scopePath): array {
        // If scopePath is just a language code (e.g., "en"), the entire language is shared.
        // Return the full tree — the home page and all subfolders are siblings at root level.
        if (!str_contains($scopePath, '/')) {
            return $tree;
        }

        foreach ($tree as $node) {
            if ($node['path'] === $scopePath) {
                return [$node];
            }
            if (!empty($node['children'])) {
                $found = $this->extractSubtreeByScope($node['children'], $scopePath);
                if (!empty($found) && count($found) === 1 && ($found[0]['path'] ?? '') === $scopePath) {
                    return $found;
                }
            }
        }
        // Fallback: no exact match found, return full tree
        return $tree;
    }

    /**
     * Recursively strip permissions from tree nodes.
     */
    private function stripPermissionsFromTree(array $tree): array {
        return array_map(function ($node) {
            unset($node['permissions']);
            if (!empty($node['children'])) {
                $node['children'] = $this->stripPermissionsFromTree($node['children']);
            }
            return $node;
        }, $tree);
    }

    /**
     * Recursively mark the current page in a tree structure.
     * Sets 'isCurrent' to true for the matching uniqueId.
     */
    private function markCurrentPageInTree(array $tree, string $currentPageId): array {
        $result = [];
        foreach ($tree as $node) {
            $newNode = $node;
            $newNode['isCurrent'] = ($node['uniqueId'] ?? '') === $currentPageId;
            if (!empty($node['children'])) {
                $newNode['children'] = $this->markCurrentPageInTree($node['children'], $currentPageId);
            }
            $result[] = $newNode;
        }
        return $result;
    }

    /**
     * Build a breadcrumb scoped to a share target.
     *
     * Starts from the share root instead of "Home". Ancestors above the share
     * scope are not included.
     */
    private function buildShareScopedBreadcrumb(array $pageData, string $shareScopePath, string $language): array {
        $pagePath = $pageData['path'] ?? '';
        if (empty($pagePath)) {
            return [];
        }

        // Normalize paths for Unicode-safe comparison
        if (function_exists('normalizer_normalize')) {
            $pagePath = \Normalizer::normalize($pagePath, \Normalizer::FORM_C) ?: $pagePath;
            $shareScopePath = \Normalizer::normalize($shareScopePath, \Normalizer::FORM_C) ?: $shareScopePath;
        }

        // shareScopePath is relative path like "nl" or "nl/afdeling" or "nl/afdeling/hr"
        $pathParts = explode('/', $pagePath);

        $breadcrumb = [];

        // Get GroupFolder for system-level page lookup
        $groupFolder = null;
        try {
            $groupFolder = $this->setupService->getSharedFolder();
        } catch (\Exception $e) {
            // Fall back to folder names only
        }

        // Determine if this is a language-root share (scope is just the language, e.g. "en")
        $isLanguageRootShare = !str_contains($shareScopePath, '/');

        if ($isLanguageRootShare) {
            // Language-root share: add actual Home page as first breadcrumb item
            $homeUniqueId = null;
            $homeTitle = 'Home';
            if ($groupFolder !== null) {
                // Read home breadcrumb label from navigation.json (first item title)
                try {
                    $navPath = $language . '/navigation.json';
                    if ($groupFolder->nodeExists($navPath)) {
                        $navFile = $groupFolder->get($navPath);
                        $navData = json_decode($navFile->getContent(), true, 64);
                        if ($navData && !empty($navData['items'][0]['title'])) {
                            $homeTitle = $navData['items'][0]['title'];
                        }
                        if ($navData && !empty($navData['items'][0]['uniqueId'])) {
                            $homeUniqueId = $navData['items'][0]['uniqueId'];
                        }
                    }
                } catch (\Exception $e) {
                    // Fall through, Home without uniqueId
                }
                // Get home uniqueId from home.json if not found in navigation
                if ($homeUniqueId === null) {
                    try {
                        $homeJsonPath = $language . '/home.json';
                        if ($groupFolder->nodeExists($homeJsonPath)) {
                            $homeFile = $groupFolder->get($homeJsonPath);
                            $homeData = json_decode($homeFile->getContent(), true, 64);
                            if ($homeData && isset($homeData['uniqueId'])) {
                                $homeUniqueId = $homeData['uniqueId'];
                            }
                        }
                    } catch (\Exception $e) {
                        // Fall through
                    }
                }
                // Fallback: scan language folder for any page-*.json file at root level
                if ($homeUniqueId === null) {
                    try {
                        $langFolder = $groupFolder->get($language);
                        if ($langFolder instanceof \OCP\Files\Folder) {
                            foreach ($langFolder->getDirectoryListing() as $node) {
                                $name = $node->getName();
                                if (str_starts_with($name, 'page-') && str_ends_with($name, '.json')) {
                                    $content = $node->getContent();
                                    $data = json_decode($content, true, 64);
                                    if ($data && isset($data['uniqueId'])) {
                                        $homeUniqueId = $data['uniqueId'];
                                        break;
                                    }
                                }
                            }
                        }
                    } catch (\Exception $e) {
                        // Fall through
                    }
                }
            }

            // Check if the current page IS the home page
            $isHomePage = ($pagePath === $language || $pagePath === $language . '/home');

            $breadcrumb[] = [
                'uniqueId' => $homeUniqueId,
                'title' => $homeTitle,
                'path' => $language,
                'url' => $isHomePage ? null : ($homeUniqueId ? '#' . $homeUniqueId : null),
                'current' => $isHomePage,
            ];

            if ($isHomePage) {
                return $breadcrumb;
            }
        } else {
            // Subfolder share: add the share scope root page as "Home" breadcrumb
            $scopeRootUniqueId = null;
            $scopeFolderName = basename($shareScopePath);
            $scopeRootTitle = ucfirst(str_replace('-', ' ', $scopeFolderName));
            if ($groupFolder !== null) {
                try {
                    $scopeJsonPath = $shareScopePath . '/' . $scopeFolderName . '.json';
                    if ($groupFolder->nodeExists($scopeJsonPath)) {
                        $scopeFile = $groupFolder->get($scopeJsonPath);
                        $scopeContent = $scopeFile->getContent();
                        $scopeData = json_decode($scopeContent, true, 64);
                        if ($scopeData && isset($scopeData['uniqueId'])) {
                            $scopeRootUniqueId = $scopeData['uniqueId'];
                            $scopeRootTitle = $scopeData['title'] ?? $scopeRootTitle;
                        }
                    }
                } catch (\Exception $e) {
                    // Fall through
                }
            }

            // Check if the current page IS the scope root page
            $isScopeRoot = ($pagePath === $shareScopePath);

            $breadcrumb[] = [
                'uniqueId' => $scopeRootUniqueId,
                'title' => $scopeRootTitle,
                'path' => $shareScopePath,
                'url' => $isScopeRoot ? null : ($scopeRootUniqueId ? '#' . $scopeRootUniqueId : null),
                'current' => $isScopeRoot,
            ];

            if ($isScopeRoot) {
                return $breadcrumb;
            }
        }

        // Walk through page path parts, only include items within the share scope
        $accumulatedPath = '';
        $scopeReached = false;

        foreach ($pathParts as $index => $part) {
            if (!empty($accumulatedPath)) {
                $accumulatedPath .= '/';
            }
            $accumulatedPath .= $part;

            // Skip language folder (already covered by root breadcrumb)
            if ($index === 0 && strlen($part) >= 2 && strlen($part) <= 3) {
                continue;
            }

            // For language-root shares, all items after the language are in scope
            // For subfolder shares, wait until we PASS the share scope (root is already in breadcrumb)
            if (!$isLanguageRootShare) {
                if ($accumulatedPath === $shareScopePath) {
                    // This is the scope root, already added as first breadcrumb — skip
                    continue;
                }
                if (!str_starts_with($accumulatedPath, $shareScopePath . '/')) {
                    // Not yet within share scope
                    continue;
                }
            }

            $isLastItem = ($index === count($pathParts) - 1);

            if ($isLastItem) {
                // Current page
                $breadcrumb[] = [
                    'uniqueId' => $pageData['uniqueId'] ?? null,
                    'title' => $pageData['title'] ?? ucfirst(str_replace('-', ' ', $part)),
                    'path' => $pagePath,
                    'url' => null,
                    'current' => true,
                ];
            } else {
                // Parent — try to find the page JSON in the GroupFolder
                $parentPage = null;
                if ($groupFolder !== null) {
                    try {
                        $jsonPath = $accumulatedPath . '/' . $part . '.json';
                        if ($groupFolder->nodeExists($jsonPath)) {
                            $jsonFile = $groupFolder->get($jsonPath);
                            $content = $jsonFile->getContent();
                            $data = json_decode($content, true, 64);
                            if ($data && isset($data['uniqueId'], $data['title'])) {
                                $parentPage = $data;
                            }
                        }
                    } catch (\Exception $e) {
                        // Fall through to folder name fallback
                    }
                }

                if ($parentPage) {
                    $breadcrumb[] = [
                        'uniqueId' => $parentPage['uniqueId'],
                        'title' => $parentPage['title'],
                        'path' => $accumulatedPath,
                        'url' => '#' . $parentPage['uniqueId'],
                        'current' => false,
                    ];
                } else {
                    $breadcrumb[] = [
                        'uniqueId' => null,
                        'title' => ucfirst(str_replace('-', ' ', $part)),
                        'path' => $accumulatedPath,
                        'url' => null,
                        'current' => false,
                    ];
                }
            }
        }

        return $breadcrumb;
    }

    /**
     * Validate share token format.
     */
    private function isValidShareTokenFormat(?string $token): bool {
        if ($token === null || $token === '') {
            return false;
        }
        // NC share tokens are alphanumeric, typically 15-20 chars
        return strlen($token) >= 10 && strlen($token) <= 32 && ctype_alnum($token);
    }

    /**
     * Register a failed share access attempt.
     */
    private function registerShareBruteForceAttempt(): void {
        /** @var IThrottler */
        $throttler = \OC::$server->get(IThrottler::class);
        $throttler->registerAttempt(
            'intravox_share_page',
            $this->request->getRemoteAddress()
        );
    }

    /**
     * Return a generic "not found" response for share access.
     */
    private function shareNotFoundResponse(): JSONResponse {
        // Add random delay to mask timing differences
        usleep(random_int(10000, 50000)); // 10-50ms

        return new JSONResponse([
            'error' => 'Page not found or not accessible via this share link'
        ], Http::STATUS_NOT_FOUND);
    }

    /**
     * Get share password from session (stored after password challenge).
     */
    private function getSharePasswordFromSession(string $token): ?string {
        return $this->session->get('intravox_share_pw_' . $token);
    }

    /**
     * Check if share password is required and validate session password.
     * Returns null if OK (no password needed or session password valid).
     * Returns a JSONResponse if access should be denied.
     */
    private function checkSharePasswordOrDeny(string $token): ?JSONResponse {
        try {
            if (!$this->publicShareService->shareRequiresPassword($token)) {
                return null; // No password needed
            }
        } catch (\Exception $e) {
            // Share not found — let the normal validation handle it
            return null;
        }

        $sessionPw = $this->getSharePasswordFromSession($token);
        if ($sessionPw === null || $sessionPw === '') {
            return new JSONResponse([
                'error' => 'Password required',
                'passwordRequired' => true,
            ], Http::STATUS_UNAUTHORIZED);
        }

        if (!$this->publicShareService->checkSharePassword($token, $sessionPw)) {
            return new JSONResponse([
                'error' => 'Password required',
                'passwordRequired' => true,
            ], Http::STATUS_UNAUTHORIZED);
        }

        return null; // Session password is valid
    }

    /**
     * Sanitize page data for public (anonymous) access.
     *
     * Only includes safe fields - removes internal metadata like uniqueId path, author info, etc.
     */
    private function sanitizePageForPublicAccess(array $pageData): array {
        // Whitelist approach - only include explicitly safe fields
        $safe = [
            'uniqueId' => $pageData['uniqueId'] ?? null,
            'title' => $pageData['title'] ?? '',
            'layout' => $pageData['layout'] ?? [],
            'language' => $pageData['language'] ?? 'en',
            'lastModified' => $pageData['lastModified'] ?? null,
        ];

        // Include publication dates if available (for public info)
        if (isset($pageData['metadata'])) {
            $safeMeta = [];
            // Only include non-sensitive metadata
            if (isset($pageData['metadata']['publishDate'])) {
                $safeMeta['publishDate'] = $pageData['metadata']['publishDate'];
            }
            if (!empty($safeMeta)) {
                $safe['metadata'] = $safeMeta;
            }
        }

        return $safe;
    }

    /**
     * Get media (image) for a page via NC share token.
     *
     * Validates the share token and serves media if the page is within the share scope.
     *
     * @PublicPage
     * @NoCSRFRequired
     * @AnonRateThrottle(limit=60, period=60)
     * @param string $token The NC share token
     * @param string $uniqueId The page's unique ID
     * @param string $filename The media filename
     * @return Response
     */
    #[PublicPage]
    #[NoCSRFRequired]
    #[AnonRateThrottle(limit: 60, period: 60)]
    public function getMediaByShare(string $token, string $uniqueId, string $filename) {
        // Validate token format first (cheap check)
        if (!$this->isValidShareTokenFormat($token)) {
            return new DataResponse(['error' => 'Not found'], Http::STATUS_NOT_FOUND);
        }

        // NC sharing must be enabled
        $ncAllowsLinks = $this->config->getAppValue('core', 'shareapi_allow_links', 'yes') === 'yes';
        if (!$ncAllowsLinks) {
            return new DataResponse(['error' => 'Not found'], Http::STATUS_NOT_FOUND);
        }

        // Check share password
        $pwDenied = $this->checkSharePasswordOrDeny($token);
        if ($pwDenied !== null) {
            return $pwDenied;
        }

        // Sanitize filename to prevent directory traversal
        $filename = basename($filename);

        try {
            // Validate share access - this checks if the page is within share scope
            // We use 'en' as default language, but validateShareAccess will search all languages
            $sessionPw = $this->getSharePasswordFromSession($token);
            $validation = $this->publicShareService->validateShareAccess($token, $uniqueId, 'en', $sessionPw);

            if (!$validation['valid']) {
                return new DataResponse(['error' => 'Not found'], Http::STATUS_NOT_FOUND);
            }

            // Get the page path from validation result
            // pagePath is like: /__groupfolders/1/files/nl/documentatie/documentatie.json
            $pagePath = $validation['pagePath'] ?? null;
            if ($pagePath === null) {
                return new DataResponse(['error' => 'Not found'], Http::STATUS_NOT_FOUND);
            }

            // Get the page folder (parent of the .json file)
            // From: /__groupfolders/1/files/nl/documentatie/documentatie.json
            // To:   /__groupfolders/1/files/nl/documentatie
            $pageFolder = dirname($pagePath);

            // Get the IntraVox folder via system context
            $folder = $this->setupService->getSharedFolder();
            if ($folder === null) {
                return new DataResponse(['error' => 'Not found'], Http::STATUS_NOT_FOUND);
            }

            // Calculate the relative path from IntraVox root to the page folder
            // IntraVox path: /__groupfolders/1/files
            // Page folder:   /__groupfolders/1/files/nl/documentatie
            // Relative:      nl/documentatie
            $intraVoxPath = $folder->getPath();
            $relativePath = ltrim(substr($pageFolder, strlen($intraVoxPath)), '/');

            $this->logger->debug('[ApiController] getMediaByShare: looking for media', [
                'pagePath' => $pagePath,
                'pageFolder' => $pageFolder,
                'relativePath' => $relativePath,
                'filename' => $filename
            ]);

            // Navigate to the page folder
            $targetFolder = $folder->get($relativePath);

            // Get the _media folder
            $mediaFolder = $targetFolder->get('_media');

            // Get the file
            $file = $mediaFolder->get($filename);

            if ($file->getType() !== \OCP\Files\FileInfo::TYPE_FILE) {
                return new DataResponse(['error' => 'Not found'], Http::STATUS_NOT_FOUND);
            }

            // Get mime type
            $mimeType = $file->getMimeType();

            // Validate it's an allowed media type
            $allowedTypes = [
                'image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/svg+xml', 'image/bmp',
                'video/mp4', 'video/webm', 'video/ogg', 'video/quicktime'
            ];
            if (!in_array($mimeType, $allowedTypes)) {
                return new DataResponse(['error' => 'Not found'], Http::STATUS_NOT_FOUND);
            }

            // Create stream response with security headers
            $response = new \OCP\AppFramework\Http\StreamResponse($file->fopen('rb'));
            $response->addHeader('Content-Type', $mimeType);
            $response->addHeader('Content-Disposition', 'inline; filename="' . $file->getName() . '"');
            $response->addHeader('X-Content-Type-Options', 'nosniff');
            $response->addHeader('X-Frame-Options', 'DENY');
            $isVideo = strpos($mimeType, 'video/') === 0;
            $cacheTime = $isVideo ? 86400 : 31536000;
            $response->addHeader('Cache-Control', 'public, max-age=' . $cacheTime);

            return $response;

        } catch (\OCP\Files\NotFoundException $e) {
            $this->logger->debug('[ApiController] Media file not found in getMediaByShare', [
                'token' => '***',
                'uniqueId' => $uniqueId,
                'filename' => $filename
            ]);
            return new DataResponse(['error' => 'Not found'], Http::STATUS_NOT_FOUND);
        } catch (\Exception $e) {
            $this->logger->debug('[ApiController] Error in getMediaByShare', [
                'token' => '***',
                'uniqueId' => $uniqueId,
                'filename' => $filename,
                'error' => $e->getMessage()
            ]);
            return new DataResponse(['error' => 'Not found'], Http::STATUS_NOT_FOUND);
        }
    }

    /**
     * Get resources media via NC share token.
     *
     * Validates the share token and serves shared resources.
     * Note: _resources is a language-level shared folder (not per-scope).
     * Pages within any scope may reference these shared resources (backgrounds, icons, etc.).
     * Access is granted to all resources within the language, not limited to share scope.
     *
     * @PublicPage
     * @NoCSRFRequired
     * @AnonRateThrottle(limit=60, period=60)
     * @param string $token The NC share token
     * @param string $filename The resource filename
     * @return Response
     */
    #[PublicPage]
    #[NoCSRFRequired]
    #[AnonRateThrottle(limit: 60, period: 60)]
    public function getResourcesMediaByShare(string $token, string $filename) {
        // Validate token format first (cheap check)
        if (!$this->isValidShareTokenFormat($token)) {
            return new DataResponse(['error' => 'Not found'], Http::STATUS_NOT_FOUND);
        }

        // NC sharing must be enabled
        $ncAllowsLinks = $this->config->getAppValue('core', 'shareapi_allow_links', 'yes') === 'yes';
        if (!$ncAllowsLinks) {
            return new DataResponse(['error' => 'Not found'], Http::STATUS_NOT_FOUND);
        }

        // Check share password
        $pwDenied = $this->checkSharePasswordOrDeny($token);
        if ($pwDenied !== null) {
            return $pwDenied;
        }

        // Sanitize path to prevent directory traversal
        try {
            $safePath = $this->sanitizePath($filename);
        } catch (\InvalidArgumentException $e) {
            return new DataResponse(['error' => 'Not found'], Http::STATUS_NOT_FOUND);
        }

        try {
            // Validate that the share token is valid (belongs to something in IntraVox)
            $share = $this->publicShareService->getShareByToken($token);
            if ($share === null) {
                return new DataResponse(['error' => 'Not found'], Http::STATUS_NOT_FOUND);
            }

            // Get the share target to determine the language
            $shareTarget = $share->getTarget();
            $language = $this->detectLanguageFromShareTarget($shareTarget);
            if ($language === null) {
                $language = 'nl'; // Default fallback
            }

            // Get the IntraVox folder via system context
            $folder = $this->setupService->getSharedFolder();
            if ($folder === null) {
                return new DataResponse(['error' => 'Not found'], Http::STATUS_NOT_FOUND);
            }

            // Get the _resources folder in the language folder
            $languageFolder = $folder->get($language);
            $resourcesFolder = $languageFolder->get('_resources');

            // Get the file
            $file = $resourcesFolder->get($safePath);

            if ($file->getType() !== \OCP\Files\FileInfo::TYPE_FILE) {
                return new DataResponse(['error' => 'Not found'], Http::STATUS_NOT_FOUND);
            }

            // Get mime type
            $mimeType = $file->getMimeType();

            // Create stream response with security headers
            $response = new \OCP\AppFramework\Http\StreamResponse($file->fopen('rb'));
            $response->addHeader('Content-Type', $mimeType);
            $response->addHeader('Content-Disposition', 'inline; filename="' . basename($safePath) . '"');
            $response->addHeader('X-Content-Type-Options', 'nosniff');
            $response->addHeader('X-Frame-Options', 'DENY');
            $response->addHeader('Cache-Control', 'public, max-age=31536000'); // 1 year cache

            return $response;

        } catch (\OCP\Files\NotFoundException $e) {
            $this->logger->debug('[ApiController] Resources file not found in getResourcesMediaByShare', [
                'filename' => $filename
            ]);
            return new DataResponse(['error' => 'Not found'], Http::STATUS_NOT_FOUND);
        } catch (\Exception $e) {
            $this->logger->debug('[ApiController] Error in getResourcesMediaByShare', [
                'filename' => $filename,
                'error' => $e->getMessage()
            ]);
            return new DataResponse(['error' => 'Not found'], Http::STATUS_NOT_FOUND);
        }
    }

    /**
     * Detect language from share target path.
     * E.g., "/nl" or "/nl/about" -> "nl"
     */
    private function detectLanguageFromShareTarget(string $shareTarget): ?string {
        $path = ltrim($shareTarget, '/');
        $segments = explode('/', $path);

        if (empty($segments) || empty($segments[0])) {
            return null;
        }

        $potentialLanguage = $segments[0];

        // Validate it's a valid language code (2-3 chars)
        if (strlen($potentialLanguage) >= 2 && strlen($potentialLanguage) <= 3 && ctype_alpha($potentialLanguage)) {
            return $potentialLanguage;
        }

        return null;
    }

    /**
     * Get resources media with folder via NC share token.
     *
     * @PublicPage
     * @NoCSRFRequired
     * @AnonRateThrottle(limit=60, period=60)
     */
    #[PublicPage]
    #[NoCSRFRequired]
    #[AnonRateThrottle(limit: 60, period: 60)]
    public function getResourcesMediaWithFolderByShare(string $token, string $folder, string $filename) {
        $path = $folder . '/' . $filename;
        return $this->getResourcesMediaByShare($token, $path);
    }

    // =========================================================================
    // TEMPLATE ENDPOINTS
    // =========================================================================

    /**
     * List all available page templates
     *
     * @NoAdminRequired
     */
    #[NoAdminRequired]
    public function listTemplates(): DataResponse {
        try {
            $templates = $this->pageService->listTemplates();
            $canCreate = $this->pageService->canCreateTemplates();

            return new DataResponse([
                'templates' => $templates,
                'canCreate' => $canCreate,
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Failed to list templates: ' . $e->getMessage());
            return new DataResponse([
                'error' => $e->getMessage(),
            ], Http::STATUS_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Get a specific template by ID
     *
     * @NoAdminRequired
     */
    #[NoAdminRequired]
    public function getTemplate(string $id): DataResponse {
        try {
            $template = $this->pageService->getTemplate($id);

            if ($template === null) {
                return new DataResponse([
                    'error' => 'Template not found',
                ], Http::STATUS_NOT_FOUND);
            }

            return new DataResponse($template);
        } catch (\Exception $e) {
            $this->logger->error('Failed to get template: ' . $e->getMessage());
            return new DataResponse([
                'error' => $e->getMessage(),
            ], Http::STATUS_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Save a page as a template
     *
     * @NoAdminRequired
     */
    #[NoAdminRequired]
    public function saveAsTemplate(): DataResponse {
        try {
            $pageUniqueId = $this->request->getParam('pageUniqueId');
            $templateTitle = $this->request->getParam('templateTitle');
            $templateDescription = $this->request->getParam('templateDescription');

            if (!$pageUniqueId || !$templateTitle) {
                return new DataResponse([
                    'error' => 'Missing required parameters: pageUniqueId, templateTitle',
                ], Http::STATUS_BAD_REQUEST);
            }

            // Check if user can create templates
            if (!$this->pageService->canCreateTemplates()) {
                return new DataResponse([
                    'error' => 'You do not have permission to create templates',
                ], Http::STATUS_FORBIDDEN);
            }

            $result = $this->pageService->saveAsTemplate($pageUniqueId, $templateTitle, $templateDescription);

            if (!$result['success']) {
                return new DataResponse([
                    'error' => $result['error'] ?? 'Failed to save template',
                ], Http::STATUS_BAD_REQUEST);
            }

            return new DataResponse($result, Http::STATUS_CREATED);
        } catch (\Exception $e) {
            $this->logger->error('Failed to save as template: ' . $e->getMessage());
            return new DataResponse([
                'error' => $e->getMessage(),
            ], Http::STATUS_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Delete a template
     *
     * @NoAdminRequired
     */
    #[NoAdminRequired]
    public function deleteTemplate(string $id): DataResponse {
        try {
            $result = $this->pageService->deleteTemplate($id);

            if (!$result['success']) {
                return new DataResponse([
                    'error' => $result['error'] ?? 'Failed to delete template',
                ], Http::STATUS_BAD_REQUEST);
            }

            return new DataResponse(['success' => true]);
        } catch (\Exception $e) {
            $this->logger->error('Failed to delete template: ' . $e->getMessage());
            return new DataResponse([
                'error' => $e->getMessage(),
            ], Http::STATUS_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Create a new page from a template
     *
     * @NoAdminRequired
     */
    #[NoAdminRequired]
    public function createPageFromTemplate(): DataResponse {
        try {
            $templateId = $this->request->getParam('templateId');
            $pageTitle = $this->request->getParam('pageTitle');
            $parentPath = $this->request->getParam('parentPath');

            if (!$templateId || !$pageTitle) {
                return new DataResponse([
                    'error' => 'Missing required parameters: templateId, pageTitle',
                ], Http::STATUS_BAD_REQUEST);
            }

            $result = $this->pageService->createPageFromTemplate($templateId, $pageTitle, $parentPath);

            if (!$result['success']) {
                return new DataResponse([
                    'error' => $result['error'] ?? 'Failed to create page from template',
                ], Http::STATUS_BAD_REQUEST);
            }

            return new DataResponse($result, Http::STATUS_CREATED);
        } catch (\Exception $e) {
            $this->logger->error('Failed to create page from template: ' . $e->getMessage());
            return new DataResponse([
                'error' => $e->getMessage(),
            ], Http::STATUS_INTERNAL_SERVER_ERROR);
        }
    }
}
