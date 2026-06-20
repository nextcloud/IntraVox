<?php
declare(strict_types=1);

namespace OCA\IntraVox\Controller;

use OCA\IntraVox\Service\LanguageHomepageService;
use OCA\IntraVox\Service\LanguageService;
use OCA\IntraVox\Service\PageService;
use OCA\IntraVox\Service\PermissionService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
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
    private PageService $pageService;
    private PermissionService $permissionService;
    private LoggerInterface $logger;

    public function __construct(
        string $appName,
        IRequest $request,
        LanguageService $languageService,
        LanguageHomepageService $homepageService,
        PageService $pageService,
        PermissionService $permissionService,
        LoggerInterface $logger
    ) {
        parent::__construct($appName, $request);
        $this->languageService = $languageService;
        $this->homepageService = $homepageService;
        $this->pageService = $pageService;
        $this->permissionService = $permissionService;
        $this->logger = $logger;
    }

    /**
     * Language metadata for the admin settings UI, in the VoxCloud model:
     *   - availableLanguages: ALL Nextcloud-known languages (the pick-from set)
     *   - languagesWithContent: base codes that have a real homepage today
     *     (the derived "active" set — no opt-in list)
     *   - primaryLanguage / defaultLanguage: recommended + universal fallback
     *
     * The legacy `languages` (discovered + enabled flags) field is kept for
     * backward compatibility with any older frontend still reading it.
     */
    public function list(): DataResponse {
        try {
            $status = $this->pageService->getLanguageContentStatus();
            return new DataResponse([
                'availableLanguages' => $this->languageService->getAvailableLanguages(),
                'languagesWithContent' => $status['languagesWithContent'] ?? [],
                'primaryLanguage' => $this->languageService->getPrimaryLanguage(),
                'defaultLanguage' => $this->languageService->getDefaultLanguage(),
                // Legacy fields (deprecated):
                'languages' => $this->languageService->getLanguagesWithMetadata(),
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
     * Persist the admin's primary (recommended fallback) language.
     */
    public function setPrimary(): DataResponse {
        try {
            $code = $this->request->getParam('code');
            if (!is_string($code) || $code === '') {
                return new DataResponse(
                    ['error' => 'code is required'],
                    Http::STATUS_BAD_REQUEST
                );
            }
            $this->languageService->setPrimaryLanguage($code);
            return new DataResponse(['primaryLanguage' => $this->languageService->getPrimaryLanguage()]);
        } catch (\InvalidArgumentException $e) {
            return new DataResponse(['error' => $e->getMessage()], Http::STATUS_BAD_REQUEST);
        } catch (\Throwable $e) {
            $this->logger->error('[LanguageController] setPrimary failed: ' . $e->getMessage());
            return new DataResponse(
                ['error' => 'Failed to set primary language'],
                Http::STATUS_INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     * Add a language to the intranet by creating an empty homepage in its
     * content folder. The language must be a valid NC-known code (from the
     * available set), not necessarily one IntraVox ships a translation for.
     * Idempotent — never overwrites an existing homepage.
     */
    public function addLanguage(string $code): DataResponse {
        try {
            if (!$this->languageService->isLanguageAvailable($code)) {
                return new DataResponse(
                    ['error' => "Unknown language code: {$code}"],
                    Http::STATUS_BAD_REQUEST
                );
            }
            $result = $this->homepageService->createEmptyHomepage($code);
            return new DataResponse($result);
        } catch (\Throwable $e) {
            $this->logger->error('[LanguageController] addLanguage failed: ' . $e->getMessage());
            return new DataResponse(
                ['error' => 'Failed to add language: ' . $e->getMessage()],
                Http::STATUS_INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     * For the CURRENT user: does their language have real (editor-maintained)
     * content, and which languages do? Powers the landing-page fallback notice.
     * Available to all users (not admin-only).
     */
    #[NoAdminRequired]
    public function contentStatus(): DataResponse {
        try {
            $status = $this->pageService->getLanguageContentStatus();
            $status['primaryLanguage'] = $this->languageService->getPrimaryLanguage();
            // Decorate the content languages with display names for the notice UI.
            $names = [];
            foreach ($this->languageService->getAvailableLanguages() as $lang) {
                $names[$lang['code']] = $lang['name'];
            }
            $status['languageNames'] = $names;
            // The "Manage intranet languages" link in the notice points at the
            // admin settings, which only NC admins can open — so only tell the
            // frontend to show it for them.
            $status['isAdmin'] = $this->permissionService->isSystemAdmin();
            return new DataResponse($status);
        } catch (\Throwable $e) {
            $this->logger->error('[LanguageController] contentStatus failed: ' . $e->getMessage());
            return new DataResponse(
                ['error' => 'Failed to get content status'],
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
