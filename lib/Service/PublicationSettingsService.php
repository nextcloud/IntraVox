<?php
declare(strict_types=1);

namespace OCA\IntraVox\Service;

use OCA\IntraVox\AppInfo\Application;
use OCP\IConfig;

/**
 * Service for managing IntraVox publication date settings
 *
 * Stores the MetaVox field names used for publication date filtering in the News widget.
 * Settings are stored in Nextcloud's app config using IConfig.
 */
class PublicationSettingsService {
    // Config keys
    private const KEY_PUBLISH_DATE_FIELD = 'publication_publish_date_field';
    private const KEY_EXPIRATION_DATE_FIELD = 'publication_expiration_date_field';

    // Defaults (empty = feature disabled until configured)
    private const DEFAULT_PUBLISH_DATE_FIELD = '';
    private const DEFAULT_EXPIRATION_DATE_FIELD = '';

    public function __construct(
        private IConfig $config
    ) {}

    /**
     * Get all publication settings
     *
     * @return array All settings with their current values
     */
    public function getAll(): array {
        return [
            'publishDateField' => $this->getPublishDateField(),
            'expirationDateField' => $this->getExpirationDateField(),
        ];
    }

    /**
     * Update multiple settings at once
     *
     * @param array $settings Key-value pairs of settings to update
     * @return array Updated settings
     */
    public function updateAll(array $settings): array {
        if (isset($settings['publishDateField'])) {
            $this->setPublishDateField((string)$settings['publishDateField']);
        }
        if (isset($settings['expirationDateField'])) {
            $this->setExpirationDateField((string)$settings['expirationDateField']);
        }

        return $this->getAll();
    }

    /**
     * Get the MetaVox field name for publish date
     */
    public function getPublishDateField(): string {
        return $this->config->getAppValue(
            Application::APP_ID,
            self::KEY_PUBLISH_DATE_FIELD,
            self::DEFAULT_PUBLISH_DATE_FIELD
        );
    }

    /**
     * Set the MetaVox field name for publish date
     */
    public function setPublishDateField(string $value): void {
        $this->config->setAppValue(
            Application::APP_ID,
            self::KEY_PUBLISH_DATE_FIELD,
            trim($value)
        );
    }

    /**
     * Get the MetaVox field name for expiration date
     */
    public function getExpirationDateField(): string {
        return $this->config->getAppValue(
            Application::APP_ID,
            self::KEY_EXPIRATION_DATE_FIELD,
            self::DEFAULT_EXPIRATION_DATE_FIELD
        );
    }

    /**
     * Set the MetaVox field name for expiration date
     */
    public function setExpirationDateField(string $value): void {
        $this->config->setAppValue(
            Application::APP_ID,
            self::KEY_EXPIRATION_DATE_FIELD,
            trim($value)
        );
    }

    /**
     * Check if publication filtering is configured
     * Returns true if at least one field name is set
     */
    public function isConfigured(): bool {
        return !empty($this->getPublishDateField()) || !empty($this->getExpirationDateField());
    }
}
