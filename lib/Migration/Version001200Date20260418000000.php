<?php
declare(strict_types=1);

namespace OCA\IntraVox\Migration;

use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\DB\Types;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

/**
 * Migration to create per-user LMS token storage for personalized feed content.
 *
 * Creates:
 * - oc_intravox_lms_tokens: Stores per-user OAuth2/manual tokens for Canvas, Moodle, etc.
 */
class Version001200Date20260418000000 extends SimpleMigrationStep {

    public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper {
        /** @var ISchemaWrapper $schema */
        $schema = $schemaClosure();

        if (!$schema->hasTable('intravox_lms_tokens')) {
            $table = $schema->createTable('intravox_lms_tokens');

            $table->addColumn('id', Types::BIGINT, [
                'autoincrement' => true,
                'notnull' => true,
                'unsigned' => true,
            ]);

            $table->addColumn('user_id', Types::STRING, [
                'notnull' => true,
                'length' => 64,
            ]);

            $table->addColumn('connection_id', Types::STRING, [
                'notnull' => true,
                'length' => 32,
            ]);

            $table->addColumn('access_token', Types::TEXT, [
                'notnull' => true,
            ]);

            $table->addColumn('refresh_token', Types::TEXT, [
                'notnull' => false,
                'default' => null,
            ]);

            // 'oauth2', 'manual', or 'oidc'
            $table->addColumn('token_type', Types::STRING, [
                'notnull' => true,
                'length' => 16,
            ]);

            $table->addColumn('expires_at', Types::DATETIME, [
                'notnull' => false,
                'default' => null,
            ]);

            $table->addColumn('created_at', Types::DATETIME, [
                'notnull' => true,
            ]);

            $table->addColumn('updated_at', Types::DATETIME, [
                'notnull' => true,
            ]);

            $table->setPrimaryKey(['id']);
            $table->addUniqueIndex(['user_id', 'connection_id'], 'iv_lms_user_conn');
            $table->addIndex(['expires_at'], 'iv_lms_expires');
        }

        return $schema;
    }
}
