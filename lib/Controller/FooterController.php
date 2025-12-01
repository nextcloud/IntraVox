<?php
declare(strict_types=1);

namespace OCA\IntraVox\Controller;

use OCA\IntraVox\Service\FooterService;
use OCA\IntraVox\Service\PermissionService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IRequest;

class FooterController extends Controller {
    private FooterService $footerService;
    private PermissionService $permissionService;

    public function __construct(
        string $appName,
        IRequest $request,
        FooterService $footerService,
        PermissionService $permissionService
    ) {
        parent::__construct($appName, $request);
        $this->footerService = $footerService;
        $this->permissionService = $permissionService;
    }

    /**
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    public function get(): JSONResponse {
        try {
            // Check if user has access
            if (!$this->permissionService->hasAccess()) {
                return new JSONResponse(
                    ['error' => 'Access denied'],
                    Http::STATUS_FORBIDDEN
                );
            }

            $footer = $this->footerService->getFooter();

            // Add permissions to response
            $footer['permissions'] = $this->permissionService->getPermissionsObject('');
            $footer['canEdit'] = $this->permissionService->canWrite('');

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
     */
    public function save(): JSONResponse {
        try {
            // Check write permission
            if (!$this->permissionService->canWrite('')) {
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
