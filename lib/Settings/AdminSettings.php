<?php
declare(strict_types=1);

namespace OCA\IntraVox\Settings;

use OCA\IntraVox\AppInfo\Application;
use OCA\IntraVox\Constants;
use OCA\IntraVox\Service\DemoDataService;
use OCA\IntraVox\Service\EngagementSettingsService;
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
    private LicenseService $licenseService;
    private PublicationSettingsService $publicationSettings;
    private TelemetryService $telemetryService;
    private IInitialState $initialState;
    private IConfig $config;
    private IAppManager $appManager;

    public function __construct(
        DemoDataService $demoDataService,
        EngagementSettingsService $engagementSettings,
        LicenseService $licenseService,
        PublicationSettingsService $publicationSettings,
        TelemetryService $telemetryService,
        IInitialState $initialState,
        IConfig $config,
        IAppManager $appManager
    ) {
        $this->demoDataService = $demoDataService;
        $this->engagementSettings = $engagementSettings;
        $this->licenseService = $licenseService;
        $this->publicationSettings = $publicationSettings;
        $this->telemetryService = $telemetryService;
        $this->initialState = $initialState;
        $this->config = $config;
        $this->appManager = $appManager;
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
        ]);

        // Load translations for JavaScript
        Util::addTranslations(Application::APP_ID);

        // Load the admin settings JavaScript
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
