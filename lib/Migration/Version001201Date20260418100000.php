<?php
declare(strict_types=1);

namespace OCA\IntraVox\Migration;

use Closure;
use OCA\IntraVox\AppInfo\Application;
use OCP\DB\ISchemaWrapper;
use OCP\IConfig;
use OCP\IDBConnection;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;
use OCP\Security\ICrypto;

/**
 * Migrate per-user LMS tokens from custom table to Nextcloud user preferences.
 *
 * This moves tokens from oc_intravox_lms_tokens to oc_preferences,
 * where Nextcloud core automatically handles cleanup on user deletion.
 * After migration, the custom table is dropped.
 */
class Version001201Date20260418100000 extends SimpleMigrationStep {

    public function __construct(
        private IDBConnection $db,
        private IConfig $config,
        private ICrypto $crypto,
    ) {}

    public function preSchemaChange(IOutput $output, Closure $schemaClosure, array $options): void {
        /** @var ISchemaWrapper $schema */
        $schema = $schemaClosure();

        if (!$schema->hasTable('intravox_lms_tokens')) {
            return;
        }

        // Migrate existing tokens to user preferences
        $qb = $this->db->getQueryBuilder();
        $qb->select('user_id', 'connection_id', 'access_token', 'refresh_token', 'token_type', 'expires_at', 'created_at')
            ->from('intravox_lms_tokens');

        $result = $qb->executeQuery();
        $migrated = 0;

        while ($row = $result->fetch()) {
            $userId = $row['user_id'];
            $connectionId = $row['connection_id'];

            // Tokens are already encrypted in the old table — decrypt and re-encrypt in new format
            try {
                $accessToken = $this->crypto->decrypt($row['access_token']);
                $refreshToken = !empty($row['refresh_token']) ? $this->crypto->decrypt($row['refresh_token']) : null;
            } catch (\Exception $e) {
                // Skip tokens that can't be decrypted
                $output->warning("Skipping token for user $userId / connection $connectionId: decrypt failed");
                continue;
            }

            $data = [
                'access_token' => $accessToken,
                'refresh_token' => $refreshToken,
                'token_type' => $row['token_type'],
                'expires_at' => $row['expires_at'] ? (new \DateTime($row['expires_at']))->format('c') : null,
                'created_at' => $row['created_at'] ? (new \DateTime($row['created_at']))->format('c') : date('c'),
            ];

            $encrypted = $this->crypto->encrypt(json_encode($data));

            $this->config->setUserValue(
                $userId, Application::APP_ID, 'lms_token_' . $connectionId, $encrypted
            );

            $migrated++;
        }
        $result->closeCursor();

        if ($migrated > 0) {
            $output->info("Migrated $migrated LMS token(s) to user preferences");
        }
    }

    public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper {
        /** @var ISchemaWrapper $schema */
        $schema = $schemaClosure();

        if ($schema->hasTable('intravox_lms_tokens')) {
            $schema->dropTable('intravox_lms_tokens');
            $output->info('Dropped intravox_lms_tokens table');
        }

        return $schema;
    }
}
