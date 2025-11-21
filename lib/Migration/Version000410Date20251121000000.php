<?php
declare(strict_types=1);

namespace OCA\IntraVox\Migration;

use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\DB\Types;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

/**
 * Create search index table for fast full-text search
 */
class Version000410Date20251121000000 extends SimpleMigrationStep {

    /**
     * @param IOutput $output
     * @param Closure $schemaClosure The `\Closure` returns a `ISchemaWrapper`
     * @param array $options
     * @return null|ISchemaWrapper
     */
    public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper {
        /** @var ISchemaWrapper $schema */
        $schema = $schemaClosure();

        if (!$schema->hasTable('intravox_search_index')) {
            $table = $schema->createTable('intravox_search_index');

            $table->addColumn('id', Types::BIGINT, [
                'autoincrement' => true,
                'notnull' => true,
                'unsigned' => true,
            ]);

            $table->addColumn('page_unique_id', Types::STRING, [
                'notnull' => true,
                'length' => 64,
            ]);

            $table->addColumn('language', Types::STRING, [
                'notnull' => true,
                'length' => 10,
            ]);

            $table->addColumn('title', Types::STRING, [
                'notnull' => true,
                'length' => 255,
            ]);

            $table->addColumn('content', Types::TEXT, [
                'notnull' => false,
            ]);

            $table->addColumn('path', Types::STRING, [
                'notnull' => true,
                'length' => 512,
            ]);

            $table->addColumn('modified', Types::BIGINT, [
                'notnull' => true,
                'unsigned' => true,
            ]);

            $table->addColumn('indexed_at', Types::BIGINT, [
                'notnull' => true,
                'unsigned' => true,
            ]);

            $table->setPrimaryKey(['id']);
            $table->addUniqueIndex(['page_unique_id'], 'intravox_search_unique_id');
            $table->addIndex(['language'], 'intravox_search_language');
            $table->addIndex(['title'], 'intravox_search_title');

            return $schema;
        }

        return null;
    }
}
