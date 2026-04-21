<?php
declare(strict_types=1);

namespace OCA\IntraVox\Migration;

use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\DB\Types;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

/**
 * Create page metadata index table.
 *
 * Stores pre-indexed metadata (title, uniqueId, path, language, modtime)
 * so that page listing, tree, and search operations can query the database
 * instead of traversing the filesystem and parsing JSON files per request.
 */
class Version001300Date20260420000000 extends SimpleMigrationStep {

    public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper {
        /** @var ISchemaWrapper $schema */
        $schema = $schemaClosure();

        if (!$schema->hasTable('intravox_page_index')) {
            $table = $schema->createTable('intravox_page_index');

            $table->addColumn('id', Types::BIGINT, [
                'autoincrement' => true,
                'notnull' => true,
                'unsigned' => true,
            ]);
            $table->addColumn('unique_id', Types::STRING, [
                'notnull' => true,
                'length' => 64,
            ]);
            $table->addColumn('title', Types::STRING, [
                'notnull' => true,
                'length' => 512,
                'default' => '',
            ]);
            $table->addColumn('language', Types::STRING, [
                'notnull' => true,
                'length' => 8,
            ]);
            $table->addColumn('path', Types::STRING, [
                'notnull' => true,
                'length' => 1024,
                'default' => '',
            ]);
            $table->addColumn('status', Types::STRING, [
                'notnull' => true,
                'length' => 16,
                'default' => 'published',
            ]);
            $table->addColumn('modified_at', Types::BIGINT, [
                'notnull' => true,
                'unsigned' => true,
                'default' => 0,
            ]);
            $table->addColumn('parent_id', Types::STRING, [
                'notnull' => false,
                'length' => 64,
            ]);
            $table->addColumn('file_id', Types::BIGINT, [
                'notnull' => false,
                'unsigned' => true,
            ]);

            $table->setPrimaryKey(['id']);
            $table->addUniqueIndex(['unique_id', 'language'], 'ivox_page_uid_lang');
            $table->addIndex(['language'], 'ivox_page_lang');
            $table->addIndex(['language', 'status'], 'ivox_page_lang_status');
            $table->addIndex(['parent_id'], 'ivox_page_parent');
            $table->addIndex(['modified_at'], 'ivox_page_modified');
        }

        return $schema;
    }
}
