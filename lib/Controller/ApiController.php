<?php
declare(strict_types=1);

namespace OCA\IntraVox\Controller;

use OCA\IntraVox\Service\PageService;
use OCA\IntraVox\Service\SetupService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\Http\StreamResponse;
use OCP\IRequest;
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
    private LoggerInterface $logger;

    public function __construct(
        string $appName,
        IRequest $request,
        PageService $pageService,
        SetupService $setupService,
        LoggerInterface $logger
    ) {
        parent::__construct($appName, $request);
        $this->pageService = $pageService;
        $this->setupService = $setupService;
        $this->logger = $logger;
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
     * @NoAdminRequired
     */
    public function uploadImage(string $pageId): DataResponse {
        try {
            // First get the page to check permissions (from Nextcloud filesystem)
            $existingPage = $this->pageService->getPage($pageId);

            // Check write permission (uploading images requires write access)
            if (!($existingPage['permissions']['canWrite'] ?? false)) {
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
            // First get the page to check permissions (from Nextcloud filesystem)
            $existingPage = $this->pageService->getPage($pageId);

            // Check read permission using Nextcloud's permissions
            if (!($existingPage['permissions']['canRead'] ?? false)) {
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
    public function getPageTree(?string $currentPageId = null): DataResponse {
        try {
            $tree = $this->pageService->getPageTree($currentPageId);

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
        try {
            $this->logger->info('[ApiController] Running setup');

            $result = $this->setupService->setup();

            return new DataResponse([
                'success' => true,
                'message' => 'Setup completed successfully',
                'result' => $result,
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
}
