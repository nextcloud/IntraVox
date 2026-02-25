<?php
declare(strict_types=1);

namespace OCA\IntraVox\Migration;

use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\DB\Types;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

/**
 * Migration to create feed tokens table for per-user RSS feed access
 *
 * Creates:
 * - oc_intravox_feed_tokens: Stores per-user feed tokens with scope configuration
 */
class Version001000Date20260223000000 extends SimpleMigrationStep {

    /**
     * @param IOutput $output
     * @param Closure(): ISchemaWrapper $schemaClosure
     * @param array $options
     * @return null|ISchemaWrapper
     */
    public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper {
        /** @var ISchemaWrapper $schema */
        $schema = $schemaClosure();

        if (!$schema->hasTable('intravox_feed_tokens')) {
            $table = $schema->createTable('intravox_feed_tokens');

            $table->addColumn('id', Types::BIGINT, [
                'autoincrement' => true,
                'notnull' => true,
                'unsigned' => true,
            ]);

            $table->addColumn('user_id', Types::STRING, [
                'notnull' => true,
                'length' => 64,
            ]);

            $table->addColumn('token', Types::STRING, [
                'notnull' => true,
                'length' => 64,
            ]);

            // Feed scope configuration (JSON):
            // {"scope": "all|language|folder", "language": "en", "folderId": "page-xxx", "limit": 20}
            $table->addColumn('config', Types::TEXT, [
                'notnull' => false,
                'default' => null,
            ]);

            $table->addColumn('created_at', Types::DATETIME, [
                'notnull' => true,
            ]);

            $table->addColumn('last_accessed', Types::DATETIME, [
                'notnull' => false,
                'default' => null,
            ]);

            $table->setPrimaryKey(['id']);
            $table->addUniqueIndex(['token'], 'iv_ft_token');
            $table->addUniqueIndex(['user_id'], 'iv_ft_user');
            $table->addIndex(['last_accessed'], 'iv_ft_accessed');
        }

        return $schema;
    }
}
