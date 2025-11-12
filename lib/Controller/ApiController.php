<?php
declare(strict_types=1);

namespace OCA\IntraVox\Controller;

use OCA\IntraVox\Service\PageService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\Http\StreamResponse;
use OCP\IRequest;
use Psr\Log\LoggerInterface;

class ApiController extends Controller {
    private PageService $pageService;
    private LoggerInterface $logger;

    public function __construct(
        string $appName,
        IRequest $request,
        PageService $pageService,
        LoggerInterface $logger
    ) {
        parent::__construct($appName, $request);
        $this->pageService = $pageService;
        $this->logger = $logger;
    }

    /**
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    public function listPages(): DataResponse {
        try {
            $pages = $this->pageService->listPages();
            return new DataResponse($pages);
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
            $page = $this->pageService->createPage($data);
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
            $file = $this->request->getUploadedFile('image');
            if (!$file) {
                throw new \InvalidArgumentException('No image provided');
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
        $this->logger->info('[ApiController::getPageVersions] Called', ['pageId' => $pageId]);

        try {
            $versions = $this->pageService->getPageVersions($pageId);
            $this->logger->info('[ApiController::getPageVersions] Success', [
                'pageId' => $pageId,
                'count' => count($versions)
            ]);
            return new DataResponse($versions);
        } catch (\Exception $e) {
            $this->logger->error('[ApiController::getPageVersions] Error', [
                'pageId' => $pageId,
                'error' => $e->getMessage()
            ]);
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
    public function getPageMetadata(string $pageId): DataResponse {
        try {
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
}
