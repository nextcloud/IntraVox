<?php
declare(strict_types=1);

namespace OCA\IntraVox\Migration;

use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\DB\Types;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

/**
 * Migration to create page locks table for pessimistic page locking
 *
 * Creates:
 * - oc_intravox_page_locks: Stores active page edit locks with heartbeat tracking
 */
class Version001001Date20260307000000 extends SimpleMigrationStep {

	/**
	 * @param IOutput $output
	 * @param Closure(): ISchemaWrapper $schemaClosure
	 * @param array $options
	 * @return null|ISchemaWrapper
	 */
	public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper {
		/** @var ISchemaWrapper $schema */
		$schema = $schemaClosure();

		if (!$schema->hasTable('intravox_page_locks')) {
			$table = $schema->createTable('intravox_page_locks');

			$table->addColumn('id', Types::BIGINT, [
				'autoincrement' => true,
				'notnull' => true,
				'unsigned' => true,
			]);

			$table->addColumn('page_unique_id', Types::STRING, [
				'notnull' => true,
				'length' => 64,
			]);

			$table->addColumn('user_id', Types::STRING, [
				'notnull' => true,
				'length' => 64,
			]);

			$table->addColumn('display_name', Types::STRING, [
				'notnull' => true,
				'length' => 255,
			]);

			$table->addColumn('created_at', Types::DATETIME, [
				'notnull' => true,
			]);

			$table->addColumn('updated_at', Types::DATETIME, [
				'notnull' => true,
			]);

			$table->setPrimaryKey(['id']);
			$table->addUniqueIndex(['page_unique_id'], 'iv_pl_page');
			$table->addIndex(['user_id'], 'iv_pl_user');
			$table->addIndex(['updated_at'], 'iv_pl_updated');
		}

		return $schema;
	}
}
