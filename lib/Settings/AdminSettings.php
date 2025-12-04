<?php
declare(strict_types=1);

namespace OCA\IntraVox\Settings;

use OCA\IntraVox\AppInfo\Application;
use OCA\IntraVox\Service\DemoDataService;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\AppFramework\Services\IInitialState;
use OCP\Settings\IDelegatedSettings;
use OCP\Util;

/**
 * Admin settings for IntraVox
 */
class AdminSettings implements IDelegatedSettings {
    private DemoDataService $demoDataService;
    private IInitialState $initialState;

    public function __construct(
        DemoDataService $demoDataService,
        IInitialState $initialState
    ) {
        $this->demoDataService = $demoDataService;
        $this->initialState = $initialState;
    }

    /**
     * @return TemplateResponse
     */
    public function getForm(): TemplateResponse {
        // Get demo data status
        $status = $this->demoDataService->getStatus();

        // Pass initial state to the frontend
        $this->initialState->provideInitialState('admin-settings', [
            'languages' => $status['languages'] ?? [],
            'imported' => $status['imported'] ?? false,
            'setupComplete' => $status['setupComplete'] ?? false,
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
            ],
        ];
    }
}
