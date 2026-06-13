<?php
declare(strict_types=1);

namespace OCA\IntraVox\Migration;

use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\IConfig;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

/**
 * Seed `intravox.enabled_languages` for upgrades from <1.6.0.
 *
 * Pre-1.6.0 the supported-languages list was a hardcoded `['nl','en','de','fr']`
 * constant duplicated across 12 services. From 1.6.0 the set lives in
 * `oc_appconfig.intravox.enabled_languages` and is admin-controlled.
 *
 * Existing installs must see exactly the four languages they had before the
 * upgrade — see the upgrade-safety contract in the plan. This migration
 * writes the legacy default once if (and only if) the key is missing.
 *
 * Pure config-init: no folder creation, no folder deletion, no destructive
 * side-effects. Idempotent.
 */
class Version001600Date20260609000000 extends SimpleMigrationStep {

    private const APP_ID = 'intravox';
    private const CONFIG_KEY = 'enabled_languages';
    private const LEGACY_DEFAULT = ['nl', 'en', 'de', 'fr'];

    private IConfig $config;

    public function __construct(IConfig $config) {
        $this->config = $config;
    }

    public function postSchemaChange(IOutput $output, Closure $schemaClosure, array $options): void {
        $existing = $this->config->getAppValue(self::APP_ID, self::CONFIG_KEY, '');
        if ($existing !== '') {
            $output->info('[IntraVox] enabled_languages already set, skipping seed');
            return;
        }

        $this->config->setAppValue(self::APP_ID, self::CONFIG_KEY, json_encode(self::LEGACY_DEFAULT));
        $output->info('[IntraVox] Seeded enabled_languages with legacy default ["nl","en","de","fr"]');
    }

    public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper {
        // No schema changes for 1.6.0.
        return null;
    }
}
