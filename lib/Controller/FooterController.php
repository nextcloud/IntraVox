<?php
declare(strict_types=1);

namespace OCA\IntraVox\Controller;

use OCA\IntraVox\Service\FooterService;
use OCA\IntraVox\Service\PageService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\Attribute\NoCSRFRequired;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IRequest;
use OCA\IntraVox\Controller\HasConditionalResponse;

/**
 * Footer Controller
 *
 * Uses Nextcloud's native filesystem permissions which automatically
 * respect GroupFolder ACL rules.
 */
class FooterController extends Controller {
    use HasConditionalResponse;

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
     */
    #[NoAdminRequired]
    #[NoCSRFRequired]
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

            // Add permissions to response.
            //
            // canEdit stays with FooterService: it gates on footer.json itself,
            // where $permissions describes the IntraVox ROOT folder. An ACL can
            // deny the file while the root stays writable, and overwriting the
            // service's answer here reintroduced the Edit button whose save then
            // fails (issue #112).
            $footer['permissions'] = $permissions;
            $footer['canEdit'] = $footer['canEdit'] ?? $permissions['canWrite'];

            // Same as navigation: the ETag was sent but never compared against
            // If-None-Match, so the 304 branch could not happen.
            $etag = '"' . md5(json_encode($footer)) . '"';
            $response = $this->clientHasCurrent($etag)
                ? new JSONResponse([], Http::STATUS_NOT_MODIFIED)
                : new JSONResponse($footer);
            $response->addHeader('Cache-Control', 'private, max-age=300, must-revalidate');
            $response->addHeader('ETag', $etag);

            return $response;
        } catch (\Exception $e) {
            return new JSONResponse(
                ['error' => $e->getMessage()],
                Http::STATUS_INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     */
    #[NoAdminRequired]
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
