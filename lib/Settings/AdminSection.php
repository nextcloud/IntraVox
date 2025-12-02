<?php
declare(strict_types=1);

namespace OCA\IntraVox\Settings;

use OCA\IntraVox\AppInfo\Application;
use OCP\IL10N;
use OCP\IURLGenerator;
use OCP\Settings\IIconSection;

/**
 * Admin section for IntraVox settings
 */
class AdminSection implements IIconSection {
    private IL10N $l;
    private IURLGenerator $urlGenerator;

    public function __construct(
        IL10N $l,
        IURLGenerator $urlGenerator
    ) {
        $this->l = $l;
        $this->urlGenerator = $urlGenerator;
    }

    /**
     * @return string The ID of the section
     */
    public function getID(): string {
        return Application::APP_ID;
    }

    /**
     * @return string The translated name of the section
     */
    public function getName(): string {
        return $this->l->t('IntraVox');
    }

    /**
     * @return int Priority (0-100, lower = higher in the list)
     */
    public function getPriority(): int {
        return 80;
    }

    /**
     * @return string The relative path to an icon
     */
    public function getIcon(): string {
        return $this->urlGenerator->imagePath(Application::APP_ID, 'app-dark.svg');
    }
}
