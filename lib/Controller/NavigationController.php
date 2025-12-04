<?php

declare(strict_types=1);

namespace OCA\IntraVox\Controller;

use OCA\IntraVox\Service\NavigationService;
use OCA\IntraVox\Service\PageService;
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
    private IL10N $l10n;
    private LoggerInterface $logger;

    public function __construct(
        string $appName,
        IRequest $request,
        NavigationService $navigationService,
        PageService $pageService,
        IL10N $l10n,
        LoggerInterface $logger
    ) {
        parent::__construct($appName, $request);
        $this->navigationService = $navigationService;
        $this->pageService = $pageService;
        $this->l10n = $l10n;
        $this->logger = $logger;
    }

    /**
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    public function get(): JSONResponse {
        try {
            // Get root permissions using Nextcloud's filesystem
            $permissions = $this->pageService->getFolderPermissions('');

            // Check if user has access to IntraVox
            if (!$permissions['canRead']) {
                return new JSONResponse([
                    'error' => 'Access denied'
                ], Http::STATUS_FORBIDDEN);
            }

            $currentLang = $this->l10n->getLanguageCode();
            $navigation = $this->navigationService->getNavigation();

            // Build a lookup map of page permissions by uniqueId
            $pages = $this->pageService->listPages();
            $pagePermissions = [];
            foreach ($pages as $page) {
                if (isset($page['uniqueId'])) {
                    $pagePermissions[$page['uniqueId']] = $page['permissions']['canRead'] ?? false;
                }
            }

            // Filter navigation items based on user's read permissions
            if (isset($navigation['items']) && is_array($navigation['items'])) {
                $navigation['items'] = $this->filterNavigationByPermissions($navigation['items'], $pagePermissions);
            }

            return new JSONResponse([
                'navigation' => $navigation,
                'canEdit' => $permissions['canWrite'],
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
     * Filter navigation items based on user's page permissions
     * Keeps external links (url set) and removes pages user can't read
     */
    private function filterNavigationByPermissions(array $items, array $pagePermissions): array {
        $filtered = [];

        foreach ($items as $item) {
            // Keep item if:
            // 1. It has a URL (external/custom link)
            // 2. It has no uniqueId (custom item without page reference)
            // 3. The user can read the referenced page
            $hasUrl = !empty($item['url']);
            $hasUniqueId = !empty($item['uniqueId']);
            $canRead = !$hasUniqueId || ($pagePermissions[$item['uniqueId']] ?? false);

            if ($hasUrl || !$hasUniqueId || $canRead) {
                // Recursively filter children
                if (isset($item['children']) && is_array($item['children'])) {
                    $item['children'] = $this->filterNavigationByPermissions($item['children'], $pagePermissions);
                }

                // Only add item if it has accessible content or children
                // (don't add empty parent items that only had inaccessible children)
                $hasAccessibleChildren = !empty($item['children']);
                $hasDirectAccess = $hasUrl || !$hasUniqueId || $canRead;

                if ($hasDirectAccess || $hasAccessibleChildren) {
                    $filtered[] = $item;
                }
            }
        }

        return $filtered;
    }

    /**
     * @NoAdminRequired
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
