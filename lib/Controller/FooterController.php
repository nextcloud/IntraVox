<?php
declare(strict_types=1);

namespace OCA\IntraVox\Controller;

use OCA\IntraVox\Service\FooterService;
use OCA\IntraVox\Service\PageService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IRequest;

/**
 * Footer Controller
 *
 * Uses Nextcloud's native filesystem permissions which automatically
 * respect GroupFolder ACL rules.
 */
class FooterController extends Controller {
    private FooterService $footerService;
    private PageService $pageService;

    public function __construct(
        string $appName,
        IRequest $request,
        FooterService $footerService,
        PageService $pageService
    ) {
        parent::__construct($appName, $request);
        $this->footerService = $footerService;
        $this->pageService = $pageService;
    }

    /**
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    public function get(): JSONResponse {
        try {
            // Get root permissions using Nextcloud's filesystem
            $permissions = $this->pageService->getFolderPermissions('');

            // Check if user has access
            if (!$permissions['canRead']) {
                return new JSONResponse(
                    ['error' => 'Access denied'],
                    Http::STATUS_FORBIDDEN
                );
            }

            $footer = $this->footerService->getFooter();

            // Add permissions to response
            $footer['permissions'] = $permissions;
            $footer['canEdit'] = $permissions['canWrite'];

            return new JSONResponse($footer);
        } catch (\Exception $e) {
            return new JSONResponse(
                ['error' => $e->getMessage()],
                Http::STATUS_INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    public function save(): JSONResponse {
        try {
            // Check write permission using Nextcloud's filesystem
            $permissions = $this->pageService->getFolderPermissions('');
            if (!$permissions['canWrite']) {
                return new JSONResponse(
                    ['error' => 'Permission denied: cannot edit footer'],
                    Http::STATUS_FORBIDDEN
                );
            }

            $content = $this->request->getParam('content', '');
            $footer = $this->footerService->saveFooter($content);
            return new JSONResponse($footer);
        } catch (\Exception $e) {
            return new JSONResponse(
                ['error' => $e->getMessage()],
                Http::STATUS_INTERNAL_SERVER_ERROR
            );
        }
    }
}
