<?php

declare(strict_types=1);

namespace OCA\IntraVox\Controller;

use OCA\IntraVox\Service\NavigationService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IRequest;
use OCP\IL10N;
use Psr\Log\LoggerInterface;

class NavigationController extends Controller {
    private NavigationService $navigationService;
    private IL10N $l10n;
    private LoggerInterface $logger;

    public function __construct(
        string $appName,
        IRequest $request,
        NavigationService $navigationService,
        IL10N $l10n,
        LoggerInterface $logger
    ) {
        parent::__construct($appName, $request);
        $this->navigationService = $navigationService;
        $this->l10n = $l10n;
        $this->logger = $logger;
    }

    /**
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    public function get(): JSONResponse {
        try {
            $currentLang = $this->l10n->getLanguageCode();
            $this->logger->info('IntraVox: Getting navigation for language: ' . $currentLang);

            $navigation = $this->navigationService->getNavigation();
            $canEdit = $this->navigationService->canEdit();

            $this->logger->info('IntraVox: Navigation loaded successfully with ' . count($navigation['items'] ?? []) . ' items');

            return new JSONResponse([
                'navigation' => $navigation,
                'canEdit' => $canEdit,
                'language' => $currentLang  // Add debug info
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
            if (!$this->navigationService->canEdit()) {
                return new JSONResponse([
                    'error' => 'No permission to edit navigation'
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
