<?php
declare(strict_types=1);

namespace OCA\IntraVox\Activity;

use OCP\Activity\ISetting;
use OCP\IL10N;

/**
 * Activity Setting for IntraVox
 *
 * Allows users to enable/disable IntraVox notifications in their activity settings.
 */
class Setting implements ISetting {
    public function __construct(
        private IL10N $l10n
    ) {}

    /**
     * @return string Identifier of the setting
     */
    public function getIdentifier(): string {
        return 'intravox';
    }

    /**
     * @return string Human readable name of the setting
     */
    public function getName(): string {
        return $this->l10n->t('IntraVox page activity');
    }

    /**
     * @return int Priority of the setting, smaller = more important
     */
    public function getPriority(): int {
        return 51; // Just after files (50)
    }

    /**
     * @return bool True if the activity should show by default
     */
    public function canChangeStream(): bool {
        return true;
    }

    /**
     * @return bool True if the user can change the stream setting
     */
    public function isDefaultEnabledStream(): bool {
        return true;
    }

    /**
     * @return bool True if the user can receive email notifications
     */
    public function canChangeMail(): bool {
        return true;
    }

    /**
     * @return bool True if email notifications are enabled by default
     */
    public function isDefaultEnabledMail(): bool {
        return false;
    }
}
