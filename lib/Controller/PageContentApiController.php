<?php
declare(strict_types=1);

namespace OCA\IntraVox\Controller;

use OCA\IntraVox\Service\PageService;
use OCP\App\IAppManager;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\Attribute\NoCSRFRequired;
use OCP\AppFramework\Http\DataResponse;
use OCP\IRequest;
use Psr\Log\LoggerInterface;

/**
 * What a page IS, apart from its layout: version history, metadata, MetaVox
 * fields and cache state.
 *
 * Split out of ApiController (PR-A). These ten routes share a subject — the
 * page as a record rather than as content — and a shape: locate the page, check
 * write permission when mutating, delegate to PageService.
 *
 * MetaVox is an optional companion app, so getMetavoxStatus/getMetavoxFields go
 * through IAppManager and answer honestly when it is absent rather than
 * failing.
 */
class PageContentApiController extends Controller {
    use ApiErrorTrait;
    use RequiresPagePermission;

    public function __construct(
        string $appName,
        IRequest $request,
        private PageService $pageService,
        private IAppManager $appManager,
        private LoggerInterface $logger,
    ) {
        parent::__construct($appName, $request);
    }

    protected function getLogger(): LoggerInterface {
        return $this->logger;
    }

    protected function getPageService(): PageService {
        return $this->pageService;
    }
    /**
     */
    #[NoAdminRequired]
    #[NoCSRFRequired]
    public function getPageVersions(string $pageId): DataResponse {
        $this->logger->info('[ApiController::getPageVersions] Called with pageId: ' . $pageId);

        try {
            // First get the page to check permissions (from Nextcloud filesystem)
            $this->logger->info('[ApiController::getPageVersions] Getting page...');
            $existingPage = $this->pageService->getPage($pageId);
            $this->logger->info('[ApiController::getPageVersions] Got page, checking permissions...');

            // Check read permission using Nextcloud's permissions
            if (!($existingPage['permissions']['canRead'] ?? false)) {
                $this->logger->warning('[ApiController::getPageVersions] Access denied for pageId: ' . $pageId);
                return new DataResponse(
                    ['error' => 'Access denied'],
                    Http::STATUS_FORBIDDEN
                );
            }

            $this->logger->info('[ApiController::getPageVersions] Calling pageService->getPageVersions...');
            $versions = $this->pageService->getPageVersions($pageId);
            $this->logger->info('[ApiController::getPageVersions] Got ' . count($versions) . ' versions');
            return new DataResponse($versions);
        } catch (\Exception $e) {
            $this->logger->error('[ApiController::getPageVersions] Error: ' . $e->getMessage());
            return new DataResponse(
                ['error' => $e->getMessage()],
                Http::STATUS_INTERNAL_SERVER_ERROR
            );
        }
    }
    /**
     */
    #[NoAdminRequired]
    public function restorePageVersion(string $pageId, string $timestamp): DataResponse {
        try {
            // First get the page to check permissions (from Nextcloud filesystem)
            $existingPage = $this->requireWritablePage($pageId, 'cannot restore this page');
            if ($existingPage instanceof DataResponse) {
                return $existingPage;
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
     */
    #[NoAdminRequired]
    public function updateVersionLabel(string $pageId, string $timestamp): DataResponse {
        try {
            // First get the page to check permissions (from Nextcloud filesystem)
            $existingPage = $this->requireWritablePage($pageId, '');
            if ($existingPage instanceof DataResponse) {
                return $existingPage;
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
     */
    #[NoAdminRequired]
    #[NoCSRFRequired]
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
     */
    #[NoAdminRequired]
    #[NoCSRFRequired]
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
     */
    #[NoAdminRequired]
    #[NoCSRFRequired]
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
     */
    #[NoAdminRequired]
    public function updatePageMetadata(string $pageId): DataResponse {
        try {
            // First get the page to check permissions (from Nextcloud filesystem)
            $existingPage = $this->requireWritablePage($pageId, '');
            if ($existingPage instanceof DataResponse) {
                return $existingPage;
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
     */
    #[NoAdminRequired]
    #[NoCSRFRequired]
    public function getMetavoxStatus(): DataResponse {
        try {
            $appManager = $this->appManager;
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
     * Get MetaVox fields for the IntraVox groupfolder
     */
    #[NoAdminRequired]
    #[NoCSRFRequired]
    public function getMetavoxFields(): DataResponse {
        try {
            $appManager = $this->appManager;
            if (!$appManager->isInstalled('metavox') || !$appManager->isEnabledForUser('metavox')) {
                return new DataResponse(['fields' => [], 'error' => 'MetaVox not available']);
            }

            // Get the IntraVox groupfolder ID
            $setupService = \OC::$server->get(\OCA\IntraVox\Service\SetupService::class);
            $groupfolderId = $setupService->getGroupFolderId();

            if ($groupfolderId <= 0) {
                return new DataResponse(['fields' => [], 'error' => 'IntraVox groupfolder not found']);
            }

            // Get MetaVox FieldService
            $fieldService = \OC::$server->get(\OCA\MetaVox\Service\FieldService::class);

            // Get fields assigned to this groupfolder (with full field data)
            $allFields = $fieldService->getAssignedFieldsWithDataForGroupfolder($groupfolderId);

            // Format fields for the frontend
            $fields = array_map(function($field) {
                return [
                    'field_name' => $field['field_name'] ?? '',
                    'field_label' => $field['field_label'] ?? $field['field_name'] ?? '',
                    'field_type' => $field['field_type'] ?? 'text',
                    'options' => $field['field_options'] ?? [],
                ];
            }, $allFields);

            return new DataResponse([
                'fields' => array_values($fields),
                'groupfolderId' => $groupfolderId
            ]);
        } catch (\Exception $e) {
            $this->logger->warning('IntraVox: MetaVox fields unavailable', ['error' => $e->getMessage()]);
            return new DataResponse(['fields' => [], 'error' => 'MetaVox fields are unavailable'], Http::STATUS_OK);
        }
    }
    /**
     */
    #[NoAdminRequired]
    #[NoCSRFRequired]
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
}
