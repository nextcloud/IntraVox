<?php

declare(strict_types=1);

namespace OCA\IntraVox\Controller;

use OCA\IntraVox\Service\NavigationService;
use OCA\IntraVox\Service\PageService;
use OCA\IntraVox\Service\PermissionService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\Attribute\NoCSRFRequired;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\JSONResponse;
use OCP\Files\NotPermittedException;
use OCP\IRequest;
use OCP\IL10N;
use Psr\Log\LoggerInterface;
use OCA\IntraVox\Controller\HasConditionalResponse;

/**
 * Navigation Controller
 *
 * Uses Nextcloud's native filesystem permissions which automatically
 * respect GroupFolder ACL rules.
 */
class NavigationController extends Controller {
    use HasConditionalResponse;

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
     */
    #[NoAdminRequired]
    #[NoCSRFRequired]
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
            $navigationForEditor = null;
            if (isset($navigation['items']) && is_array($navigation['items'])) {
                $rawItems = $navigation['items'];

                $navigation['items'] = $this->permissionService->filterNavigation(
                    $rawItems,
                    $currentLang,
                    $pagePathMap
                );

                // The editor needs the items the menu deliberately hides: one
                // without a link and without children. Those are saved fine but
                // filtered out of the menu, so feeding the menu's copy back into
                // the editor made a freshly created item vanish on the next load
                // -- and with it any way to add the link (issue #104).
                //
                // Same permission filtering, only the linkless rule relaxed: an
                // editor still never sees items for pages they cannot read.
                // Only sent to users who may actually edit, so a reader's payload
                // does not grow and holds nothing extra.
                if ($canEdit) {
                    $navigationForEditor = $navigation;
                    $navigationForEditor['items'] = $this->permissionService->filterNavigation(
                        $rawItems,
                        $currentLang,
                        $pagePathMap,
                        true
                    );
                }
            }

            $responseData = [
                'navigation' => $navigation,
                'navigationForEditor' => $navigationForEditor,
                'canEdit' => $canEdit,
                'language' => $currentLang,
                'permissions' => $permissions
            ];

            // The ETag was already being sent; nothing ever read If-None-Match
            // back, so a client holding the current navigation still received the
            // whole tree. It is a bandwidth saving only: the payload has to be
            // built and permission-filtered before the hash exists.
            $etag = '"' . md5(json_encode($responseData)) . '"';
            $response = $this->clientHasCurrent($etag)
                ? new JSONResponse([], Http::STATUS_NOT_MODIFIED)
                : new JSONResponse($responseData);
            $response->addHeader('Cache-Control', 'private, max-age=300, must-revalidate');
            $response->addHeader('ETag', $etag);

            return $response;
        } catch (\Exception $e) {
            $this->logger->error('IntraVox: Error loading navigation: ' . $e->getMessage());
            return new JSONResponse(['error' => 'Could not load navigation'], Http::STATUS_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     */
    #[NoAdminRequired]
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
        } catch (NotPermittedException $e) {
            // The permission check above already guards the common case, but a
            // filesystem write can still be refused (e.g. an ACL/mount edge the
            // canWrite bit didn't reflect). Return 403, not a 500 (issue #86).
            return new JSONResponse([
                'error' => 'Permission denied: cannot edit navigation'
            ], Http::STATUS_FORBIDDEN);
        } catch (\Exception $e) {
            return new JSONResponse([
                'error' => $e->getMessage()
            ], Http::STATUS_INTERNAL_SERVER_ERROR);
        }
    }
}
