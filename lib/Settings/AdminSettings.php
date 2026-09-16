<?php
declare(strict_types=1);

namespace OCA\IntraVox\Settings;

use OCA\IntraVox\AppInfo\Application;
use OCA\IntraVox\Constants;
use OCA\IntraVox\Service\DemoDataService;
use OCA\IntraVox\Service\EngagementSettingsService;
use OCA\IntraVox\Service\LanguageService;
use OCA\IntraVox\Service\LicenseService;
use OCA\IntraVox\Service\PublicationSettingsService;
use OCA\IntraVox\Service\TelemetryService;
use OCP\App\IAppManager;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\AppFramework\Services\IInitialState;
use OCP\IConfig;
use OCP\Settings\IDelegatedSettings;
use OCP\Util;

/**
 * Admin settings for IntraVox
 */
class AdminSettings implements IDelegatedSettings {
    private DemoDataService $demoDataService;
    private EngagementSettingsService $engagementSettings;
    private LanguageService $languageService;
    private LicenseService $licenseService;
    private \OCA\IntraVox\Service\Language\LanguageStatusService $languageStatus;
    private \OCA\IntraVox\Service\Homepage\HomepageResolverService $homepageResolver;
    private \OCA\IntraVox\Service\Folder\FolderContext $folders;
    private PublicationSettingsService $publicationSettings;
    private TelemetryService $telemetryService;
    private IInitialState $initialState;
    private IConfig $config;
    private IAppManager $appManager;

    public function __construct(
        DemoDataService $demoDataService,
        EngagementSettingsService $engagementSettings,
        LanguageService $languageService,
        LicenseService $licenseService,
        // Content-status + page-count reads are the LANGUAGE-STATUS domain service
        // now (fase-9); getContentStatus needs the homepage-resolution and
        // real-content probes, threaded in as the exact closures the retired
        // PageService delegator supplied (HomepageResolverService / FolderContext).
        \OCA\IntraVox\Service\Language\LanguageStatusService $languageStatus,
        \OCA\IntraVox\Service\Homepage\HomepageResolverService $homepageResolver,
        \OCA\IntraVox\Service\Folder\FolderContext $folders,
        PublicationSettingsService $publicationSettings,
        TelemetryService $telemetryService,
        IInitialState $initialState,
        IConfig $config,
        IAppManager $appManager
    ) {
        $this->demoDataService = $demoDataService;
        $this->engagementSettings = $engagementSettings;
        $this->languageService = $languageService;
        $this->licenseService = $licenseService;
        $this->languageStatus = $languageStatus;
        $this->homepageResolver = $homepageResolver;
        $this->folders = $folders;
        $this->publicationSettings = $publicationSettings;
        $this->telemetryService = $telemetryService;
        $this->initialState = $initialState;
        $this->config = $config;
        $this->appManager = $appManager;
    }

    /**
     * Language content status — byte-identical to the retired
     * PageService::getLanguageContentStatus delegator: the LANGUAGE-STATUS domain
     * service scans the language folders, with homepage resolution
     * (HomepageResolverService) and the ANY-homepage / real-content probes
     * (FolderContext) passed as the same closures PageService threaded in.
     *
     * @return array{language:string,hasContent:bool,servedLanguage:?string,languagesWithContent:string[],activeLanguages:string[],homepageUniqueId:?string}
     */
    private function getLanguageContentStatus(): array {
        return $this->languageStatus->getContentStatus(
            fn(?string $language): string => $this->homepageResolver->resolveHomepageNodeUniqueId($language),
            fn(\OCP\Files\Folder $folder): bool => $this->folders->hasHomepage($folder),
            fn(\OCP\Files\Folder $folder): bool => $this->folders->hasRealContent($folder)
        );
    }

    /**
     * @return TemplateResponse
     */
    public function getForm(): TemplateResponse {
        // Get demo data status
        $status = $this->demoDataService->getStatus();

        // Get video domain whitelist
        $videoDomains = $this->config->getAppValue(
            Application::APP_ID,
            'video_domains',
            Constants::getDefaultVideoDomainsJson()
        );

        // Decode the stored JSON
        $videoDomainsArray = json_decode($videoDomains, true);

        // Only use defaults if JSON decode FAILED (null), not for empty array
        // This allows admins to explicitly block all video embeds by removing all domains
        if ($videoDomainsArray === null) {
            $videoDomainsArray = Constants::DEFAULT_VIDEO_DOMAINS;
        }

        // Check if MetaVox is available
        $metavoxAvailable = $this->appManager->isInstalled('metavox')
            && $this->appManager->isEnabledForUser('metavox');

        // Pass initial state to the frontend
        $this->initialState->provideInitialState('admin-settings', [
            'languages' => $status['languages'] ?? [],
            'imported' => $status['imported'] ?? false,
            'setupComplete' => $status['setupComplete'] ?? false,
            'videoDomains' => $videoDomainsArray,
            'engagementSettings' => $this->engagementSettings->getAll(),
            'publicationSettings' => $this->publicationSettings->getAll(),
            'metavoxAvailable' => $metavoxAvailable,
            'licenseStats' => $this->licenseService->getStats(),
            'licenseKey' => $this->licenseService->getLicenseKey() ?? '',
            'telemetryEnabled' => $this->telemetryService->isEnabled(),
            'telemetryLastReport' => $this->telemetryService->getStatus()['lastReport'] ?? null,
            // Legacy (deprecated) Transifex-discovered + enabled state. Kept so
            // any older frontend bits keep working during the transition.
            'availableLanguages' => $this->languageService->getLanguagesWithMetadata(),
            'enabledLanguageCodes' => $this->languageService->getEnabledLanguages(),
            'defaultLanguage' => $this->languageService->getDefaultLanguage(),
            // VoxCloud language model: ALL Nextcloud languages are selectable,
            // a language is "active" once it has content, and the admin picks a
            // recommended primary (fallback) language.
            'allAvailableLanguages' => $this->languageService->getAvailableLanguages(),
            'primaryLanguage' => $this->languageService->getPrimaryLanguage(),
            // Admin chips show ACTIVE languages (any homepage, incl. a just-added
            // placeholder) — not only real-content ones, so additions show up.
            'languagesWithContent' => $this->getLanguageContentStatus()['activeLanguages'],
            // base code => how much of the IntraVox UI is translated in that
            // language (Transifex), as a percentage. Drives the coverage hint.
            'translationCoverage' => $this->languageService->getTranslationCoverage(),
            // base code => number of pages, so the "remove language" dialog can
            // warn how many pages would be deleted.
            'pageCountByLanguage' => $this->languageStatus->getPageCountByLanguage(),
        ]);

        // Load translations for JavaScript
        Util::addTranslations(Application::APP_ID);

        // Load the admin settings JavaScript
        // Webpack splits into: vendors (node_modules) → shared (code used by both main+admin) → admin
        Util::addScript(Application::APP_ID, 'intravox-vendors');
        Util::addScript(Application::APP_ID, 'intravox-shared');
        Util::addScript(Application::APP_ID, 'intravox-admin');

        return new TemplateResponse(Application::APP_ID, 'settings/admin', [], '');
    }

    /**
     * @return string the section ID
     */
    public function getSection(): string {
        return Application::APP_ID;
    }

    /**
     * @return int priority (0-100, lower = higher priority)
     */
    public function getPriority(): int {
        return 50;
    }

    /**
     * @return string Name for identifying the setting
     */
    public function getName(): string {
        return 'intravox';
    }

    /**
     * @return array List of authorized app configs that this setting is responsible for
     */
    public function getAuthorizedAppConfig(): array {
        return [
            'intravox' => [
                'demo_data_imported',
                'video_domains',
                'publication_publish_date_field',
                'publication_expiration_date_field',
            ],
        ];
    }
}
