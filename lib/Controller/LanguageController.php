<?php
declare(strict_types=1);

namespace OCA\IntraVox\Controller;

use OCA\IntraVox\Service\LanguageHomepageService;
use OCA\IntraVox\Service\LanguageService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\DataResponse;
use OCP\IRequest;
use Psr\Log\LoggerInterface;

/**
 * Admin endpoints for the language-enablement UI in the Demo Data settings tab.
 *
 * All endpoints require admin rights — the framework enforces that for any
 * method not annotated `#[NoAdminRequired]`. We deliberately do NOT annotate
 * these, so they are admin-only by default.
 */
class LanguageController extends Controller {
    private LanguageService $languageService;
    private LanguageHomepageService $homepageService;
    private LoggerInterface $logger;

    public function __construct(
        string $appName,
        IRequest $request,
        LanguageService $languageService,
        LanguageHomepageService $homepageService,
        LoggerInterface $logger
    ) {
        parent::__construct($appName, $request);
        $this->languageService = $languageService;
        $this->homepageService = $homepageService;
        $this->logger = $logger;
    }

    /**
     * List every translated language with its display name, enabled status,
     * and a flag for whether it's the universal default (English).
     *
     * Powers the "Available languages" checkbox grid in the admin settings.
     */
    public function list(): DataResponse {
        try {
            return new DataResponse([
                'languages' => $this->languageService->getLanguagesWithMetadata(),
                'defaultLanguage' => $this->languageService->getDefaultLanguage(),
            ]);
        } catch (\Throwable $e) {
            $this->logger->error('[LanguageController] list failed: ' . $e->getMessage());
            return new DataResponse(
                ['error' => 'Failed to list languages'],
                Http::STATUS_INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     * Persist the admin's chosen set of enabled languages.
     *
     * For any language that newly becomes enabled, create an empty homepage
     * in its content folder (idempotent — never overwrites existing content).
     * For any language that becomes disabled, no destructive action is taken:
     * the content folder stays on disk and reappears if the admin re-enables
     * the language later. The upgrade-safety contract requires this.
     */
    public function setEnabled(): DataResponse {
        try {
            $codes = $this->request->getParam('codes');
            if (!is_array($codes)) {
                return new DataResponse(
                    ['error' => 'codes must be a JSON array of base language codes'],
                    Http::STATUS_BAD_REQUEST
                );
            }

            $previous = $this->languageService->getEnabledLanguages();
            $this->languageService->setEnabledLanguages($codes);
            $current = $this->languageService->getEnabledLanguages();

            $newlyEnabled = array_values(array_diff($current, $previous));
            $homepageResults = [];
            foreach ($newlyEnabled as $code) {
                $homepageResults[$code] = $this->homepageService->createEmptyHomepage($code);
            }

            return new DataResponse([
                'enabled' => $current,
                'newlyEnabled' => $newlyEnabled,
                'homepageResults' => $homepageResults,
            ]);
        } catch (\Throwable $e) {
            $this->logger->error('[LanguageController] setEnabled failed: ' . $e->getMessage());
            return new DataResponse(
                ['error' => 'Failed to save language selection: ' . $e->getMessage()],
                Http::STATUS_INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     * Create an empty homepage for a single language on demand. Used by the
     * Demo Data tab when an admin clicks "Install empty homepage" for a
     * language that doesn't have a full-intranet bundled demo (so the
     * existing import path is not appropriate).
     */
    public function createEmptyHomepage(string $code): DataResponse {
        try {
            if (!$this->languageService->isLanguageEnabled($code)) {
                return new DataResponse(
                    ['error' => "Language {$code} is not enabled"],
                    Http::STATUS_BAD_REQUEST
                );
            }

            $result = $this->homepageService->createEmptyHomepage($code);
            return new DataResponse($result);
        } catch (\Throwable $e) {
            $this->logger->error('[LanguageController] createEmptyHomepage failed: ' . $e->getMessage());
            return new DataResponse(
                ['error' => 'Failed to create empty homepage: ' . $e->getMessage()],
                Http::STATUS_INTERNAL_SERVER_ERROR
            );
        }
    }
}
