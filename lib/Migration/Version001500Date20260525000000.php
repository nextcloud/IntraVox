<?php
declare(strict_types=1);

namespace OCA\IntraVox\Migration;

use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\DB\Types;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

/**
 * Create reverse-geocode cache table for PhotoStory widget.
 *
 * Stores Nominatim-derived place names (POI / suburb / city / country) keyed by
 * a 4-decimal-rounded lat/lon pair, so repeated lookups of nearby photos avoid
 * hitting the upstream service.
 */
class Version001500Date20260525000000 extends SimpleMigrationStep {

    public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper {
        /** @var ISchemaWrapper $schema */
        $schema = $schemaClosure();

        if (!$schema->hasTable('intravox_geocode_cache')) {
            $table = $schema->createTable('intravox_geocode_cache');

            $table->addColumn('id', Types::BIGINT, [
                'autoincrement' => true,
                'notnull' => true,
                'unsigned' => true,
            ]);
            // DECIMAL(7,4) — range -180.0000..180.0000 fits comfortably.
            $table->addColumn('lat_rounded', Types::DECIMAL, [
                'notnull' => true,
                'precision' => 7,
                'scale' => 4,
            ]);
            $table->addColumn('lon_rounded', Types::DECIMAL, [
                'notnull' => true,
                'precision' => 7,
                'scale' => 4,
            ]);
            $table->addColumn('poi', Types::STRING, [
                'notnull' => false,
                'length' => 255,
            ]);
            $table->addColumn('place', Types::STRING, [
                'notnull' => false,
                'length' => 255,
            ]);
            $table->addColumn('city', Types::STRING, [
                'notnull' => false,
                'length' => 255,
            ]);
            $table->addColumn('country', Types::STRING, [
                'notnull' => false,
                'length' => 64,
            ]);
            $table->addColumn('country_code', Types::STRING, [
                'notnull' => false,
                'length' => 2,
                'fixed' => true,
            ]);
            $table->addColumn('raw_json', Types::TEXT, [
                'notnull' => false,
            ]);
            $table->addColumn('fetched_at', Types::BIGINT, [
                'notnull' => true,
                'unsigned' => true,
                'default' => 0,
            ]);

            $table->setPrimaryKey(['id']);
            $table->addUniqueIndex(['lat_rounded', 'lon_rounded'], 'ivox_geocache_latlon');
        }

        return $schema;
    }
}
