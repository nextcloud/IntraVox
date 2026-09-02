<?php

declare(strict_types=1);

namespace OCA\IntraVox\Service\People;

use OCP\IDBConnection;
use Psr\Log\LoggerInterface;

/**
 * Reads account properties for many users in one query instead of one query
 * each.
 *
 * IAccountManager::getAccount() issues a query per user. Measured on a real
 * instance that is 20.4 ms per 100 accounts — roughly a hundred times the
 * cost of everything else the cohort scan does, and the only thing standing
 * between this feature and a five-figure user base:
 *
 *     106 accounts, getAccount() per user : 23.2 ms, 106 queries
 *     106 accounts, one bulk SELECT       :  1.2 ms,   1 query
 *
 * oc_accounts is a flat `uid -> JSON` table, so the whole set is a single
 * SELECT. Output was verified field-by-field against IAccountManager before
 * this class was written; it is a faster route to the same data, not a
 * different interpretation of it.
 *
 * Scope handling deliberately stays out of here — AccountScopePolicy remains
 * the one place that decides what an audience may see.
 */
class AccountBulkLoader {
	/**
	 * How many uids per IN() clause.
	 *
	 * Keeps both the query and the decoded result bounded on instances where
	 * "all users" means tens of thousands.
	 */
	private const CHUNK_SIZE = 1000;

	public function __construct(
		private ?IDBConnection $db,
		private LoggerInterface $logger,
	) {
	}

	/** Whether the bulk path is usable at all. */
	public function isAvailable(): bool {
		return $this->db !== null;
	}

	/**
	 * Load account properties for the given uids.
	 *
	 * @param array<int, string> $uids
	 * @return array<string, array<string, array{value: string, scope: string}>>
	 *         uid => property name => {value, scope}. A uid with no row is
	 *         simply absent; the caller falls back for those.
	 */
	public function loadAccounts(array $uids): array {
		if ($this->db === null || $uids === []) {
			return [];
		}

		$out = [];

		foreach (array_chunk(array_values(array_unique($uids)), self::CHUNK_SIZE) as $chunk) {
			try {
				$qb = $this->db->getQueryBuilder();
				$qb->select('uid', 'data')
					->from('accounts')
					->where($qb->expr()->in(
						'uid',
						$qb->createNamedParameter($chunk, \OCP\DB\QueryBuilder\IQueryBuilder::PARAM_STR_ARRAY)
					));

				$result = $qb->executeQuery();
				while ($row = $result->fetch()) {
					$parsed = $this->parseAccountData((string)($row['data'] ?? ''));
					if ($parsed !== []) {
						$out[(string)$row['uid']] = $parsed;
					}
				}
				$result->closeCursor();
			} catch (\Throwable $e) {
				// A failed chunk must not take the page down: the caller
				// falls back to IAccountManager for whatever is missing.
				$this->logger->warning(
					'IntraVox: bulk account load failed, falling back per user: ' . $e->getMessage()
				);
				return $out;
			}
		}

		return $out;
	}

	/**
	 * Load IntraVox custom fields for many users in one query.
	 *
	 * @param array<int, string> $uids
	 * @return array<string, array<string, mixed>> uid => field => value
	 */
	public function loadCustomFields(array $uids, string $appId, string $configKey): array {
		if ($this->db === null || $uids === []) {
			return [];
		}

		$out = [];

		foreach (array_chunk(array_values(array_unique($uids)), self::CHUNK_SIZE) as $chunk) {
			try {
				$qb = $this->db->getQueryBuilder();
				$qb->select('userid', 'configvalue')
					->from('preferences')
					->where($qb->expr()->eq('appid', $qb->createNamedParameter($appId)))
					->andWhere($qb->expr()->eq('configkey', $qb->createNamedParameter($configKey)))
					->andWhere($qb->expr()->in(
						'userid',
						$qb->createNamedParameter($chunk, \OCP\DB\QueryBuilder\IQueryBuilder::PARAM_STR_ARRAY)
					));

				$result = $qb->executeQuery();
				while ($row = $result->fetch()) {
					$decoded = json_decode((string)($row['configvalue'] ?? '{}'), true);
					if (is_array($decoded) && $decoded !== []) {
						$out[(string)$row['userid']] = $decoded;
					}
				}
				$result->closeCursor();
			} catch (\Throwable $e) {
				$this->logger->warning(
					'IntraVox: bulk custom-field load failed: ' . $e->getMessage()
				);
				return $out;
			}
		}

		return $out;
	}

	/**
	 * Distinct field names present in a custom-fields preference.
	 *
	 * Used to discover which custom fields an instance has at all, so the
	 * editor can hide a display option that would render nothing. Samples a
	 * bounded number of rows rather than scanning the whole table.
	 *
	 * @return array<int, string>
	 */
	public function sampleCustomFieldNames(string $appId, string $configKey, int $limit): array {
		if ($this->db === null || $limit < 1) {
			return [];
		}

		$names = [];

		try {
			$qb = $this->db->getQueryBuilder();
			$qb->select('configvalue')
				->from('preferences')
				->where($qb->expr()->eq('appid', $qb->createNamedParameter($appId)))
				->andWhere($qb->expr()->eq('configkey', $qb->createNamedParameter($configKey)))
				->setMaxResults($limit);

			$result = $qb->executeQuery();
			while ($row = $result->fetch()) {
				$decoded = json_decode((string)($row['configvalue'] ?? '{}'), true);
				if (!is_array($decoded)) {
					continue;
				}
				foreach ($decoded as $key => $value) {
					if (!is_string($key) || $key === '' || $value === null || $value === '') {
						continue;
					}
					$names[$key] = true;
				}
			}
			$result->closeCursor();
		} catch (\Throwable $e) {
			$this->logger->debug(
				'IntraVox: custom-field name sampling failed: ' . $e->getMessage()
			);
			return [];
		}

		return array_keys($names);
	}

	/**
	 * Decode one oc_accounts row.
	 *
	 * Nextcloud stores `{"propertyName": {"value": "...", "scope": "v2-..."}}`.
	 * Anything that does not match that shape is skipped rather than guessed
	 * at — a malformed row must not invent a scope, because a wrong scope is
	 * a visibility bug.
	 *
	 * @return array<string, array{value: string, scope: string}>
	 */
	public function parseAccountData(string $json): array {
		if ($json === '') {
			return [];
		}

		$decoded = json_decode($json, true);
		if (!is_array($decoded)) {
			return [];
		}

		$fields = [];
		foreach ($decoded as $name => $meta) {
			if (!is_string($name) || $name === '' || !is_array($meta)) {
				continue;
			}
			// A property without an explicit scope is left empty here;
			// AccountScopePolicy normalises it (and fails closed to local).
			$fields[$name] = [
				'value' => is_scalar($meta['value'] ?? null) ? (string)$meta['value'] : '',
				'scope' => is_string($meta['scope'] ?? null) ? $meta['scope'] : '',
			];
		}

		return $fields;
	}
}
