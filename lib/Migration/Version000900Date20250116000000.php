<?php
declare(strict_types=1);

namespace OCA\IntraVox\Migration;

use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\DB\Types;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

/**
 * Migration to create analytics tables for page view tracking
 *
 * Creates:
 * - oc_intravox_page_stats: Daily aggregated page view statistics
 */
class Version000900Date20250116000000 extends SimpleMigrationStep {

    /**
     * @param IOutput $output
     * @param Closure(): ISchemaWrapper $schemaClosure
     * @param array $options
     * @return null|ISchemaWrapper
     */
    public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper {
        /** @var ISchemaWrapper $schema */
        $schema = $schemaClosure();

        // Create page_stats table for daily aggregated statistics
        if (!$schema->hasTable('intravox_page_stats')) {
            $table = $schema->createTable('intravox_page_stats');

            $table->addColumn('id', Types::BIGINT, [
                'autoincrement' => true,
                'notnull' => true,
                'unsigned' => true,
            ]);

            $table->addColumn('page_unique_id', Types::STRING, [
                'notnull' => true,
                'length' => 64,
            ]);

            $table->addColumn('stat_date', Types::DATE, [
                'notnull' => true,
            ]);

            $table->addColumn('view_count', Types::INTEGER, [
                'notnull' => true,
                'default' => 0,
                'unsigned' => true,
            ]);

            $table->addColumn('unique_users', Types::INTEGER, [
                'notnull' => true,
                'default' => 0,
                'unsigned' => true,
            ]);

            $table->addColumn('created_at', Types::DATETIME, [
                'notnull' => true,
            ]);

            $table->addColumn('updated_at', Types::DATETIME, [
                'notnull' => true,
            ]);

            $table->setPrimaryKey(['id']);

            // Unique constraint on page + date (one row per page per day)
            $table->addUniqueIndex(['page_unique_id', 'stat_date'], 'intravox_ps_page_date');

            // Index for querying by date (for cleanup jobs)
            $table->addIndex(['stat_date'], 'intravox_ps_date');

            // Index for getting top pages
            $table->addIndex(['view_count'], 'intravox_ps_views');
        }

        // Create daily user tracking table (for unique user counts)
        // This table tracks which users viewed which pages on which day
        // Privacy: Only stores user hashes, not user IDs
        // Note: Table name kept short to avoid MySQL index length limits
        if (!$schema->hasTable('intravox_uv')) {
            $table = $schema->createTable('intravox_uv');

            $table->addColumn('id', Types::BIGINT, [
                'autoincrement' => true,
                'notnull' => true,
                'unsigned' => true,
            ]);

            $table->addColumn('page_id', Types::STRING, [
                'notnull' => true,
                'length' => 64,
            ]);

            $table->addColumn('user_hash', Types::STRING, [
                'notnull' => true,
                'length' => 64,
            ]);

            $table->addColumn('view_date', Types::DATE, [
                'notnull' => true,
            ]);

            $table->setPrimaryKey(['id']);

            // Unique constraint: one entry per user per page per day
            $table->addUniqueIndex(
                ['page_id', 'user_hash', 'view_date'],
                'iv_uv_unique'
            );

            // Index for cleanup by date
            $table->addIndex(['view_date'], 'iv_uv_date');
        }

        return $schema;
    }
}
