<?php

declare(strict_types=1);

namespace OCA\IntraVox\Controller;

use OCA\IntraVox\Service\NavigationService;
use OCA\IntraVox\Service\PermissionService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IRequest;
use OCP\IL10N;
use Psr\Log\LoggerInterface;

class NavigationController extends Controller {
    private NavigationService $navigationService;
    private PermissionService $permissionService;
    private IL10N $l10n;
    private LoggerInterface $logger;

    public function __construct(
        string $appName,
        IRequest $request,
        NavigationService $navigationService,
        PermissionService $permissionService,
        IL10N $l10n,
        LoggerInterface $logger
    ) {
        parent::__construct($appName, $request);
        $this->navigationService = $navigationService;
        $this->permissionService = $permissionService;
        $this->l10n = $l10n;
        $this->logger = $logger;
    }

    /**
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    public function get(): JSONResponse {
        try {
            // Check if user has access to IntraVox
            if (!$this->permissionService->hasAccess()) {
                return new JSONResponse([
                    'error' => 'Access denied'
                ], Http::STATUS_FORBIDDEN);
            }

            $currentLang = $this->l10n->getLanguageCode();
            $navigation = $this->navigationService->getNavigation();

            // Filter navigation items based on user permissions
            if (isset($navigation['items'])) {
                $navigation['items'] = $this->permissionService->filterNavigation(
                    $navigation['items'],
                    $currentLang
                );
            }

            // canEdit is now based on write permissions on root
            $canEdit = $this->permissionService->canWrite('');

            // Include user's root permissions for UI decisions
            $permissions = $this->permissionService->getPermissionsObject('');

            return new JSONResponse([
                'navigation' => $navigation,
                'canEdit' => $canEdit,
                'language' => $currentLang,
                'permissions' => $permissions
            ]);
        } catch (\Exception $e) {
            $this->logger->error('IntraVox: Error loading navigation: ' . $e->getMessage());
            return new JSONResponse([
                'error' => $e->getMessage()
            ], Http::STATUS_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * @NoAdminRequired
     */
    public function save(): JSONResponse {
        try {
            // Check write permission on root (navigation is a root-level resource)
            if (!$this->permissionService->canWrite('')) {
                return new JSONResponse([
                    'error' => 'Permission denied: cannot edit navigation'
                ], Http::STATUS_FORBIDDEN);
            }

            $data = $this->request->getParams();
            $navigation = $this->navigationService->saveNavigation($data);

            return new JSONResponse(['navigation' => $navigation]);
        } catch (\Exception $e) {
            return new JSONResponse([
                'error' => $e->getMessage()
            ], Http::STATUS_INTERNAL_SERVER_ERROR);
        }
    }
}
