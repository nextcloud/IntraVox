<?php
declare(strict_types=1);

namespace OCA\IntraVox\Controller;

use OCA\IntraVox\Service\FooterService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IRequest;

class FooterController extends Controller {
    private FooterService $footerService;

    public function __construct(
        string $appName,
        IRequest $request,
        FooterService $footerService
    ) {
        parent::__construct($appName, $request);
        $this->footerService = $footerService;
    }

    /**
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    public function get(): JSONResponse {
        try {
            $footer = $this->footerService->getFooter();
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
