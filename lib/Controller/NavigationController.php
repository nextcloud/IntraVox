<?php

declare(strict_types=1);

namespace OCA\IntraVox\Controller;

use OCA\IntraVox\Service\NavigationService;
use OCA\IntraVox\Service\PageService;
use OCA\IntraVox\Service\PermissionService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IRequest;
use OCP\IL10N;
use Psr\Log\LoggerInterface;

/**
 * Navigation Controller
 *
 * Uses Nextcloud's native filesystem permissions which automatically
 * respect GroupFolder ACL rules.
 */
class NavigationController extends Controller {
    private NavigationService $navigationService;
    private PageService $pageService;
    private PermissionService $permissionService;
    private IL10N $l10n;
    private LoggerInterface $logger;

    public function __construct(
        string $appName,
        IRequest $request,
        NavigationService $navigationService,
        PageService $pageService,
        PermissionService $permissionService,
        IL10N $l10n,
        LoggerInterface $logger
    ) {
        parent::__construct($appName, $request);
        $this->navigationService = $navigationService;
        $this->pageService = $pageService;
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
            // Get the current language
            $currentLang = $this->navigationService->getCurrentLanguage();

            // Get navigation (uses SystemFileService fallback for users with limited access)
            $navigation = $this->navigationService->getNavigation();

            // Try to get root permissions, but don't fail if user has limited access
            $canEdit = false;
            $permissions = ['canRead' => true, 'canWrite' => false];

            try {
                $permissions = $this->pageService->getFolderPermissions('');
                $canEdit = $permissions['canWrite'] ?? false;
            } catch (\Exception $e) {
                // User might have limited access (e.g., department-only)
                // Navigation was already loaded via SystemFileService, so continue
                $this->logger->debug('NavigationController: Limited permissions, using SystemFileService fallback');
            }

            // Build a lookup map of page permissions by uniqueId
            // Use PermissionService to scan all pages and build the map
            $pagePathMap = $this->permissionService->buildPagePathMap($currentLang);

            // Filter navigation items based on user's actual read permissions
            if (isset($navigation['items']) && is_array($navigation['items'])) {
                $navigation['items'] = $this->permissionService->filterNavigation(
                    $navigation['items'],
                    $currentLang,
                    $pagePathMap
                );
            }

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
     * @NoCSRFRequired
     */
    public function save(): JSONResponse {
        try {
            // Check write permission on root using Nextcloud's filesystem
            $permissions = $this->pageService->getFolderPermissions('');
            if (!$permissions['canWrite']) {
                return new JSONResponse([
                    'error' => 'Permission denied: cannot edit navigation'
                ], Http::STATUS_FORBIDDEN);
            }

            $data = $this->request->getParams();

            // Handle both wrapped and unwrapped data formats:
            // - Wrapped: { navigation: { type: '...', items: [...] } }
            // - Unwrapped: { type: '...', items: [...] }
            if (isset($data['navigation']) && is_array($data['navigation'])) {
                $navigationData = $data['navigation'];
            } else {
                $navigationData = $data;
            }

            $navigation = $this->navigationService->saveNavigation($navigationData);

            return new JSONResponse(['navigation' => $navigation]);
        } catch (\Exception $e) {
            return new JSONResponse([
                'error' => $e->getMessage()
            ], Http::STATUS_INTERNAL_SERVER_ERROR);
        }
    }
}
