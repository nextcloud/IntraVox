<?php
declare(strict_types=1);

namespace OCA\IntraVox\Controller;

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
        // The five version-history endpoints call the VERSION domain service
        // directly (facade elimination phase 1); the cache-status endpoint calls
        // its own service (fase-4 C5); getPage comes from the READ-domain service
        // (fase-4 C6); metadata now goes straight to the METADATA domain service
        // (fase-9). The RequiresPagePermission gate runs entirely on that read
        // service, so the PageService facade this controller used to hold purely
        // for the gate's (now-removed) accessor is gone (fase-9).
        private \OCA\IntraVox\Service\Read\PageReadService $pageRead,
        private \OCA\IntraVox\Service\Version\PageVersionDomainService $versionDomain,
        private \OCA\IntraVox\Service\Maintenance\PageCacheStatusService $cacheStatus,
        private IAppManager $appManager,
        private LoggerInterface $logger,
        private \OCA\IntraVox\Service\Metadata\PageMetadataService $pageMetadata,
    ) {
        parent::__construct($appName, $request);
    }

    protected function getLogger(): LoggerInterface {
        return $this->logger;
    }

    protected function getPageReadService(): \OCA\IntraVox\Service\Read\PageReadService {
        return $this->pageRead;
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
            $existingPage = $this->pageRead->getPage($pageId);
            $this->logger->info('[ApiController::getPageVersions] Got page, checking permissions...');

            if (($denied = $this->denyUnlessReadable($existingPage)) !== null) {
                $this->logger->warning('[ApiController::getPageVersions] Access denied for pageId: ' . $pageId);
                return $denied;
            }

            $this->logger->info('[ApiController::getPageVersions] Calling versionDomain->getPageVersions...');
            $versions = $this->versionDomain->getPageVersions($pageId);
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

            $page = $this->versionDomain->restorePageVersion($pageId, (int)$timestamp);
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
            $this->versionDomain->updateVersionLabel($pageId, (int)$timestamp, $label);
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
            $existingPage = $this->pageRead->getPage($pageId);

            if (($denied = $this->denyUnlessReadable($existingPage)) !== null) {
                return $denied;
            }

            $content = $this->versionDomain->getVersionContent($pageId, (int)$timestamp);
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
            $existingPage = $this->pageRead->getPage($pageId);

            if (($denied = $this->denyUnlessReadable($existingPage)) !== null) {
                return $denied;
            }

            $content = $this->versionDomain->getCurrentPageContent($pageId);
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
            $existingPage = $this->pageRead->getPage($pageId);

            if (($denied = $this->denyUnlessReadable($existingPage)) !== null) {
                return $denied;
            }

            $metadata = $this->pageMetadata->getPageMetadata($pageId);
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
            $updated = $this->pageMetadata->updatePageMetadata($pageId, $metadata);
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
            $status = $this->cacheStatus->checkPageCacheStatus($pageId);
            return new DataResponse($status);
        } catch (\Exception $e) {
            return new DataResponse(
                ['error' => $e->getMessage()],
                Http::STATUS_INTERNAL_SERVER_ERROR
            );
        }
    }
}
