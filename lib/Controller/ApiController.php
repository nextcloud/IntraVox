<?php
declare(strict_types=1);

namespace OCA\IntraVox\Controller;

use OCA\IntraVox\Service\PageService;
use OCA\IntraVox\Service\PermissionService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\Http\StreamResponse;
use OCP\IRequest;
use Psr\Log\LoggerInterface;

class ApiController extends Controller {
    private PageService $pageService;
    private PermissionService $permissionService;
    private LoggerInterface $logger;

    public function __construct(
        string $appName,
        IRequest $request,
        PageService $pageService,
        PermissionService $permissionService,
        LoggerInterface $logger
    ) {
        parent::__construct($appName, $request);
        $this->pageService = $pageService;
        $this->permissionService = $permissionService;
        $this->logger = $logger;
    }

    /**
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    public function listPages(): DataResponse {
        try {
            // Check if user has any access to IntraVox
            if (!$this->permissionService->hasAccess()) {
                return new DataResponse(
                    ['error' => 'Access denied'],
                    Http::STATUS_FORBIDDEN
                );
            }

            $pages = $this->pageService->listPages();

            // Filter pages based on permissions and add permission info
            $filteredPages = [];
            foreach ($pages as $page) {
                $path = $page['path'] ?? '';
                if ($this->permissionService->canRead($path)) {
                    $page['permissions'] = $this->permissionService->getPermissionsObject($path);
                    $filteredPages[] = $page;
                }
            }

            return new DataResponse($filteredPages);
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
    public function getPage(string $id): DataResponse {
        try {
            $page = $this->pageService->getPage($id);

            // Check read permission for this page's path
            $path = $page['path'] ?? '';
            if (!$this->permissionService->canRead($path)) {
                return new DataResponse(
                    ['error' => 'Access denied'],
                    Http::STATUS_FORBIDDEN
                );
            }

            // Add permissions to page response
            $page['permissions'] = $this->permissionService->getPermissionsObject($path);

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

            // Check create permission on parent path
            $checkPath = $parentPath ?? '';
            if (!$this->permissionService->canCreate($checkPath)) {
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
            // First get the page to check its path
            $existingPage = $this->pageService->getPage($id);
            $path = $existingPage['path'] ?? '';

            // Check write permission
            if (!$this->permissionService->canWrite($path)) {
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
            // First get the page to check its path
            $existingPage = $this->pageService->getPage($id);
            $path = $existingPage['path'] ?? '';

            // Check delete permission
            if (!$this->permissionService->canDelete($path)) {
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
     * @NoAdminRequired
     */
    public function uploadImage(string $pageId): DataResponse {
        try {
            // First get the page to check its path
            $existingPage = $this->pageService->getPage($pageId);
            $path = $existingPage['path'] ?? '';

            // Check write permission (uploading images requires write access)
            if (!$this->permissionService->canWrite($path)) {
                return new DataResponse(
                    ['error' => 'Permission denied: cannot upload images to this page'],
                    Http::STATUS_FORBIDDEN
                );
            }

            $file = $this->request->getUploadedFile('image');

            if (!$file) {
                throw new \InvalidArgumentException('No image provided');
            }

            if (empty($file['tmp_name'])) {
                throw new \InvalidArgumentException('File upload failed - tmp_name is empty. Upload error: ' . ($file['error'] ?? 'unknown'));
            }

            $filename = $this->pageService->uploadImage($pageId, $file);
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
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    public function getImage(string $pageId, string $filename) {
        try {
            // First get the page to check its path
            $existingPage = $this->pageService->getPage($pageId);
            $path = $existingPage['path'] ?? '';

            // Check read permission
            if (!$this->permissionService->canRead($path)) {
                return new DataResponse(
                    ['error' => 'Access denied'],
                    Http::STATUS_FORBIDDEN
                );
            }

            return $this->pageService->getImage($pageId, $filename);
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
            // First get the page to check its path
            $existingPage = $this->pageService->getPage($pageId);
            $path = $existingPage['path'] ?? '';

            // Check read permission
            if (!$this->permissionService->canRead($path)) {
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
            // First get the page to check its path
            $existingPage = $this->pageService->getPage($pageId);
            $path = $existingPage['path'] ?? '';

            // Check write permission (restoring requires write access)
            if (!$this->permissionService->canWrite($path)) {
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
            // First get the page to check its path
            $existingPage = $this->pageService->getPage($pageId);
            $path = $existingPage['path'] ?? '';

            // Check write permission
            if (!$this->permissionService->canWrite($path)) {
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
            // First get the page to check its path
            $existingPage = $this->pageService->getPage($pageId);
            $path = $existingPage['path'] ?? '';

            // Check read permission
            if (!$this->permissionService->canRead($path)) {
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
            // First get the page to check its path
            $existingPage = $this->pageService->getPage($pageId);
            $path = $existingPage['path'] ?? '';

            // Check read permission
            if (!$this->permissionService->canRead($path)) {
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
            // First get the page to check its path
            $existingPage = $this->pageService->getPage($pageId);
            $path = $existingPage['path'] ?? '';

            // Check read permission
            if (!$this->permissionService->canRead($path)) {
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
            // First get the page to check its path
            $existingPage = $this->pageService->getPage($pageId);
            $path = $existingPage['path'] ?? '';

            // Check write permission
            if (!$this->permissionService->canWrite($path)) {
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

            // Filter results based on permissions
            $filteredResults = [];
            foreach ($results as $result) {
                $path = $result['path'] ?? '';
                if ($this->permissionService->canRead($path)) {
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
            // First get the page to check its path
            $existingPage = $this->pageService->getPage($id);
            $path = $existingPage['path'] ?? '';

            // Check read permission
            if (!$this->permissionService->canRead($path)) {
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
    public function getPageTree(?string $currentPageId = null): DataResponse {
        try {
            // Check if user has any access
            if (!$this->permissionService->hasAccess()) {
                return new DataResponse(
                    ['error' => 'Access denied'],
                    Http::STATUS_FORBIDDEN
                );
            }

            $tree = $this->pageService->getPageTree($currentPageId);

            // Filter tree based on permissions
            $filteredTree = $this->permissionService->filterTree($tree);

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
     * Get current user's permissions for IntraVox
     *
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    public function getPermissions(?string $path = null): DataResponse {
        try {
            $checkPath = $path ?? '';
            $permissions = $this->permissionService->getPermissionsObject($checkPath);

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
}
