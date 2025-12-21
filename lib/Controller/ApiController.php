<?php
declare(strict_types=1);

namespace OCA\IntraVox\Controller;

use OCA\IntraVox\AppInfo\Application;
use OCA\IntraVox\Constants;
use OCA\IntraVox\Service\EngagementSettingsService;
use OCA\IntraVox\Service\ImportService;
use OCA\IntraVox\Service\Import\ConfluenceHtmlImporter;
use OCA\IntraVox\Service\Import\ConfluenceImporter;
use OCA\IntraVox\Service\PageService;
use OCA\IntraVox\Service\SetupService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\Attribute\NoCSRFRequired;
use OCP\AppFramework\Http\Attribute\PublicPage;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\Http\JSONResponse;
use OCP\AppFramework\Http\StreamResponse;
use OCP\Files\NotFoundException;
use OCP\IConfig;
use OCP\IGroupManager;
use OCP\IRequest;
use OCP\ITempManager;
use OCP\IUserSession;
use Psr\Log\LoggerInterface;

/**
 * API Controller for IntraVox pages
 *
 * All permission checks use Nextcloud's native filesystem permissions
 * which automatically respect GroupFolder ACL rules.
 */
class ApiController extends Controller {
    private PageService $pageService;
    private SetupService $setupService;
    private EngagementSettingsService $engagementSettings;
    private ImportService $importService;
    private LoggerInterface $logger;
    private IConfig $config;
    private IGroupManager $groupManager;
    private IUserSession $userSession;
    private ITempManager $tempManager;

    public function __construct(
        string $appName,
        IRequest $request,
        PageService $pageService,
        SetupService $setupService,
        EngagementSettingsService $engagementSettings,
        ImportService $importService,
        LoggerInterface $logger,
        IConfig $config,
        IGroupManager $groupManager,
        IUserSession $userSession,
        ITempManager $tempManager
    ) {
        parent::__construct($appName, $request);
        $this->pageService = $pageService;
        $this->setupService = $setupService;
        $this->engagementSettings = $engagementSettings;
        $this->importService = $importService;
        $this->logger = $logger;
        $this->config = $config;
        $this->groupManager = $groupManager;
        $this->userSession = $userSession;
        $this->tempManager = $tempManager;
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
    public function getResourcesMedia(string $path) {
        try {
            // Security: Validate path (prevent directory traversal)
            try {
                $safePath = $this->sanitizePath($path);
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

            $versions = $this->pageService->getPageVersions($pageId);
            return new DataResponse($versions);
        } catch (\Exception $e) {
            return new DataResponse(
                ['error' => $e->getMessage()],
                Http::STATUS_INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     * @NoAdminRequired
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
    public function getMetaVoxStatus(): DataResponse {
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

            return new DataResponse([
                'path' => $checkPath,
                'permissions' => $permissions
            ]);
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
     *
     * @NoAdminRequired
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
     * Import from uploaded ZIP file
     *
     * @NoAdminRequired
     * @NoCSRFRequired
     * @return JSONResponse
     */
    public function importZip(): JSONResponse {
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
            return new JSONResponse(
                ['error' => $e->getMessage()],
                Http::STATUS_INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     * Import from Confluence HTML export ZIP file
     *
     * @NoAdminRequired
     * @NoCSRFRequired
     * @return JSONResponse
     */
    public function importConfluenceHtml(): JSONResponse {
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
            $this->logger->error('Confluence HTML import failed: ' . $e->getMessage(), [
                'exception' => $e,
            ]);
            return new JSONResponse(
                ['error' => $e->getMessage()],
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

        // 5. Detect directory traversal attempts
        if (strpos($path, '..') !== false) {
            throw new \InvalidArgumentException('Path traversal not allowed');
        }

        // 6. Split into segments and validate each
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
}
