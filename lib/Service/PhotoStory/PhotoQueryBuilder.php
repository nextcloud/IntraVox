<?php
declare(strict_types=1);

namespace OCA\IntraVox\Service\PhotoStory;

use OCP\IDBConnection;
use Psr\Log\LoggerInterface;

/**
 * Every SQL query a photo story runs.
 *
 * Extracted from PhotoStoryService (service split, fase 2). Measured, not
 * assumed: these twelve methods need the database connection and a logger and
 * nothing else — no filesystem, no session, no EXIF reader. That made them the
 * natural seam in a 2800-line class.
 *
 * Why a photo story talks to the filecache directly at all: a folder can hold
 * 200k files, and walking it through the Files API means one stat() per node.
 * A path-prefix LIKE plus ORDER BY mtime with LIMIT/OFFSET runs in
 * milliseconds on indexed columns. The cost is that this class knows about
 * oc_filecache and oc_metavox_* directly, which is exactly why it is one
 * class and not spread through the service.
 *
 * The caller resolves WHICH storages and paths are in scope (that needs mounts
 * and permissions); this class only queries within the scopes it is handed.
 */
class PhotoQueryBuilder {
	/**
	 * Allowed filter operators.
	 *
	 * Mirrored in PhotoStoryController::sanitizeFilters(): the controller
	 * refuses anything outside this list before it reaches a query, so the
	 * two lists must agree. Public rather than duplicated, so there is one
	 * definition to keep in step.
	 */
	public const ALLOWED_OPS = ['equals', 'contains', 'in', 'year_equals'];

	/** MetaVox fields PhotoStoryWidget knows how to map. */
	public const WELL_KNOWN_PHOTO_FIELDS = [
		'exif_taken_at', 'exif_location', 'exif_country',
		'exif_camera', 'exif_people', 'exif_subjects',
	];

	public function __construct(
		private IDBConnection $db,
		private LoggerInterface $logger,
	) {
	}
	/**
	 * Collect every file_id in the given scope's storages + path-prefixes +
	 * media mimetypes. Used when there are no filters but a meta-sort is
	 * requested — we still need the full candidate set before sorting.
	 *
	 * @param array<int, array{0: int, 1: string}> $scopes
	 * @param int[] $mimeIds
	 * @return int[]
	 */
	public function collectAllFileIdsInScope(array $scopes, array $mimeIds): array {
		$out = [];
		foreach ($scopes as [$sid, $ip]) {
			$prefix = $ip === '' ? '' : ($ip . '/');
			$like = $this->escapeLikePattern($prefix) . '%';
			try {
				$qb = $this->db->getQueryBuilder();
				$qb->select('fileid')
					->from('filecache')
					->where($qb->expr()->eq('storage', $qb->createNamedParameter($sid, \OCP\DB\QueryBuilder\IQueryBuilder::PARAM_INT)))
					->andWhere($qb->expr()->like('path', $qb->createNamedParameter($like)))
					->andWhere($qb->expr()->in('mimetype', $qb->createNamedParameter($mimeIds, \OCP\DB\QueryBuilder\IQueryBuilder::PARAM_INT_ARRAY)));
				$r = $qb->executeQuery();
				while ($row = $r->fetch()) {
					$out[] = (int)$row['fileid'];
				}
				$r->closeCursor();
			} catch (\Throwable $e) {
				$this->logger->warning('PhotoStoryService: collectAllFileIdsInScope failed: ' . $e->getMessage());
			}
		}
		return array_values(array_unique($out));
	}
	/**
	 * Given a list of file_ids (typically from MetaVox), drop any that aren't
	 * inside the given (storage, path-prefix) scopes or don't match the media
	 * mime-set. Done in chunks of 500 to respect NC's IN-list cap.
	 *
	 * @param int[] $fileIds
	 * @param array<int, array{0: int, 1: string}> $scopes
	 * @param int[] $mimeIds
	 * @return int[]
	 */
	public function filterFileIdsByScope(array $fileIds, array $scopes, array $mimeIds): array {
		if (empty($fileIds) || empty($scopes)) {
			return [];
		}
		$out = [];
		// Collapse scopes into one ORed clause per chunk so we issue
		// max(ceil(N/500)) queries instead of (scopes × chunks).
		foreach (array_chunk(array_values(array_unique(array_map('intval', $fileIds))), 500) as $chunk) {
			try {
				$qb = $this->db->getQueryBuilder();
				$qb->select('fileid')
					->from('filecache')
					->where($qb->expr()->in('fileid', $qb->createNamedParameter($chunk, \OCP\DB\QueryBuilder\IQueryBuilder::PARAM_INT_ARRAY)))
					->andWhere($qb->expr()->in('mimetype', $qb->createNamedParameter($mimeIds, \OCP\DB\QueryBuilder\IQueryBuilder::PARAM_INT_ARRAY)));

				// (storage = sid1 AND path LIKE 'p1/%') OR (storage = sid2 AND path LIKE 'p2/%') OR ...
				$scopeOrs = [];
				foreach ($scopes as [$sid, $ip]) {
					$prefix = $ip === '' ? '' : ($ip . '/');
					$like = $this->escapeLikePattern($prefix) . '%';
					$scopeOrs[] = $qb->expr()->andX(
						$qb->expr()->eq('storage', $qb->createNamedParameter($sid, \OCP\DB\QueryBuilder\IQueryBuilder::PARAM_INT)),
						$qb->expr()->like('path', $qb->createNamedParameter($like))
					);
				}
				if (count($scopeOrs) === 1) {
					$qb->andWhere($scopeOrs[0]);
				} else {
					$qb->andWhere($qb->expr()->orX(...$scopeOrs));
				}

				$r = $qb->executeQuery();
				while ($row = $r->fetch()) {
					$out[] = (int)$row['fileid'];
				}
				$r->closeCursor();
			} catch (\Throwable $e) {
				$this->logger->warning('PhotoStoryService: filterFileIdsByScope failed: ' . $e->getMessage());
			}
		}
		return array_values(array_unique($out));
	}
	/**
	 * Sort a list of file_ids by either a filecache column (mtime/name/size)
	 * or a MetaVox field name. Returns the ids in sorted order.
	 *
	 * @param int[] $fileIds
	 * @return int[]
	 */
	public function sortFileIds(array $fileIds, string $sortKey, string $sortOrder, bool $isMetaSort): array {
		if (empty($fileIds)) {
			return [];
		}
		$values = [];
		if ($isMetaSort) {
			// Pull sort values from MetaVox in chunks.
			foreach (array_chunk(array_values(array_unique(array_map('intval', $fileIds))), 500) as $chunk) {
				try {
					$qb = $this->db->getQueryBuilder();
					$qb->select('file_id', 'field_value')
						->from('metavox_file_gf_meta')
						->where($qb->expr()->in('file_id', $qb->createNamedParameter($chunk, \OCP\DB\QueryBuilder\IQueryBuilder::PARAM_INT_ARRAY)))
						->andWhere($qb->expr()->eq('field_name', $qb->createNamedParameter($sortKey)));
					$r = $qb->executeQuery();
					while ($row = $r->fetch()) {
						$values[(int)$row['file_id']] = $row['field_value'];
					}
					$r->closeCursor();
				} catch (\Throwable $e) {
					$this->logger->warning('PhotoStoryService: sortFileIds meta-query failed: ' . $e->getMessage());
				}
			}
		} else {
			// Pull sort values from filecache (mtime/name/size).
			$col = in_array($sortKey, ['mtime', 'name', 'size'], true) ? $sortKey : 'mtime';
			foreach (array_chunk(array_values(array_unique(array_map('intval', $fileIds))), 500) as $chunk) {
				try {
					$qb = $this->db->getQueryBuilder();
					$qb->select('fileid', $col)
						->from('filecache')
						->where($qb->expr()->in('fileid', $qb->createNamedParameter($chunk, \OCP\DB\QueryBuilder\IQueryBuilder::PARAM_INT_ARRAY)));
					$r = $qb->executeQuery();
					while ($row = $r->fetch()) {
						$values[(int)$row['fileid']] = $row[$col];
					}
					$r->closeCursor();
				} catch (\Throwable $e) {
					$this->logger->warning('PhotoStoryService: sortFileIds fc-query failed: ' . $e->getMessage());
				}
			}
		}

		$desc = ($sortOrder === 'desc');
		usort($fileIds, function ($a, $b) use ($values, $desc) {
			$va = $values[(int)$a] ?? null;
			$vb = $values[(int)$b] ?? null;
			if ($va === null && $vb === null) return 0;
			if ($va === null) return 1;  // nulls last
			if ($vb === null) return -1;
			if (is_numeric($va) && is_numeric($vb)) {
				$cmp = (float)$va <=> (float)$vb;
			} else {
				$cmp = strcasecmp((string)$va, (string)$vb);
			}
			return $desc ? -$cmp : $cmp;
		});
		return $fileIds;
	}
	/**
	 * Fetch full filecache rows (with mime-name) for a list of file_ids. Used
	 * to hydrate the slice after filter+sort resolution.
	 *
	 * @param int[] $fileIds
	 * @return array<int, array<string, mixed>>
	 */
	public function fetchFilecacheRowsByIds(array $fileIds): array {
		if (empty($fileIds)) {
			return [];
		}
		$out = [];
		foreach (array_chunk(array_values(array_unique(array_map('intval', $fileIds))), 500) as $chunk) {
			try {
				$qb = $this->db->getQueryBuilder();
				$qb->select('fc.fileid', 'fc.storage', 'fc.path', 'fc.name', 'fc.mtime', 'fc.mimetype', 'fc.size', 'mt.mimetype as mimetype_name')
					->from('filecache', 'fc')
					->leftJoin('fc', 'mimetypes', 'mt', $qb->expr()->eq('fc.mimetype', 'mt.id'))
					->where($qb->expr()->in('fc.fileid', $qb->createNamedParameter($chunk, \OCP\DB\QueryBuilder\IQueryBuilder::PARAM_INT_ARRAY)));
				$r = $qb->executeQuery();
				while ($row = $r->fetch()) {
					$out[] = $row;
				}
				$r->closeCursor();
			} catch (\Throwable $e) {
				$this->logger->warning('PhotoStoryService: fetchFilecacheRowsByIds failed: ' . $e->getMessage());
			}
		}
		return $out;
	}
	/**
	 * Count media files in a folder tree using oc_filecache. Returns
	 * [total, truncated]. Truncated is true when the count hits the hard cap.
	 *
	 * @param int[] $mimeIds
	 * @return array{0: int, 1: bool}
	 */
	public function countMediaInTree(int $storageId, string $internalPath, array $mimeIds, int $hardCap): array {
		// path LIKE 'files/Foto/Albums/%' OR path = 'files/Foto/Albums'.
		// (No `OR path = base` here — we only count files inside, not the dir itself.)
		$prefix = $internalPath === '' ? '' : ($internalPath . '/');
		$like = $this->escapeLikePattern($prefix) . '%';

		// MariaDB's COUNT(*) over an indexed LIKE-prefix + IN mimetype is fast
		// even on 200k+ row trees (sub-second). No subqueries needed.
		try {
			$qb = $this->db->getQueryBuilder();
			$qb->select($qb->func()->count('*', 'cnt'))
				->from('filecache')
				->where($qb->expr()->eq('storage', $qb->createNamedParameter($storageId, \OCP\DB\QueryBuilder\IQueryBuilder::PARAM_INT)))
				->andWhere($qb->expr()->like('path', $qb->createNamedParameter($like)))
				->andWhere($qb->expr()->in('mimetype', $qb->createNamedParameter($mimeIds, \OCP\DB\QueryBuilder\IQueryBuilder::PARAM_INT_ARRAY)));
			$r = $qb->executeQuery();
			$row = $r->fetch();
			$r->closeCursor();
			$total = (int)($row['cnt'] ?? 0);
			$truncated = false;
			if ($total > $hardCap) {
				$total = $hardCap;
				$truncated = true;
			}
			return [$total, $truncated];
		} catch (\Throwable $e) {
			$this->logger->warning('PhotoStoryService: count query failed: ' . $e->getMessage());
			return [0, false];
		}
	}
	/**
	 * Fetch one page of media files directly from oc_filecache, joined with
	 * oc_mimetypes for the mime-name string.
	 *
	 * Returns rows with: fileid, path, name, mtime, mimetype (id), mimetype_name, size.
	 *
	 * @param int[] $mimeIds
	 * @return array<int, array<string, mixed>>
	 */
	public function fetchMediaPageFromFilecache(
		int $storageId, string $internalPath, array $mimeIds, int $offset, int $pageSize, string $sortOrder,
		string $sortColumn = 'mtime'
	): array {
		$prefix = $internalPath === '' ? '' : ($internalPath . '/');
		$like = $this->escapeLikePattern($prefix) . '%';

		// Whitelist the SQL column to prevent injection; only allow oc_filecache
		// columns we know can be safely ordered on.
		$allowedColumns = ['mtime', 'name', 'size'];
		if (!in_array($sortColumn, $allowedColumns, true)) {
			$sortColumn = 'mtime';
		}

		try {
			$qb = $this->db->getQueryBuilder();
			$qb->select('fc.fileid', 'fc.storage', 'fc.path', 'fc.name', 'fc.mtime', 'fc.mimetype', 'fc.size', 'mt.mimetype as mimetype_name')
				->from('filecache', 'fc')
				->leftJoin('fc', 'mimetypes', 'mt', $qb->expr()->eq('fc.mimetype', 'mt.id'))
				->where($qb->expr()->eq('fc.storage', $qb->createNamedParameter($storageId, \OCP\DB\QueryBuilder\IQueryBuilder::PARAM_INT)))
				->andWhere($qb->expr()->like('fc.path', $qb->createNamedParameter($like)))
				->andWhere($qb->expr()->in('fc.mimetype', $qb->createNamedParameter($mimeIds, \OCP\DB\QueryBuilder\IQueryBuilder::PARAM_INT_ARRAY)))
				->orderBy('fc.' . $sortColumn, $sortOrder === 'desc' ? 'DESC' : 'ASC')
				->setFirstResult(max(0, $offset))
				->setMaxResults(max(1, $pageSize));

			$r = $qb->executeQuery();
			$rows = [];
			while ($row = $r->fetch()) {
				$rows[] = $row;
			}
			$r->closeCursor();
			return $rows;
		} catch (\Throwable $e) {
			$this->logger->error('PhotoStoryService: page query failed: ' . $e->getMessage());
			return [];
		}
	}
	/**
	 * Escape LIKE wildcards (%, _, \) using NC's dialect-correct helper.
	 * `IDBConnection::escapeLikeParameter` knows which escape char each
	 * supported backend (MariaDB/PostgreSQL/Oracle/SQLite) expects, so the
	 * pattern stays portable.
	 */
	public function escapeLikePattern(string $s): string {
		return $this->db->escapeLikeParameter($s);
	}
	public function fetchMetaVoxMeta(array $fileIds, int $groupfolderId, array $extraFieldNames = []): array {
		if (empty($fileIds)) {
			return [];
		}

		// Union of well-known photo fields (driving special UI like day-headers,
		// maps, highlight-scoring) and any extra fields the caller needs for
		// filtering or sorting. Deduplicated.
		$fieldsOfInterest = array_values(array_unique(array_merge(
			self::WELL_KNOWN_PHOTO_FIELDS,
			array_filter(array_map('strval', $extraFieldNames), fn($n) => $n !== '')
		)));

		// Chunk file_ids to stay within NC QueryBuilder's IN-list cap (1000 on Oracle).
		$out = [];
		foreach (array_chunk(array_values(array_unique(array_map('intval', $fileIds))), 500) as $chunk) {
			try {
				$qb = $this->db->getQueryBuilder();
				$qb->select('file_id', 'field_name', 'field_value')
					->from('metavox_file_gf_meta')
					->where($qb->expr()->in('file_id', $qb->createNamedParameter($chunk, \OCP\DB\QueryBuilder\IQueryBuilder::PARAM_INT_ARRAY)))
					->andWhere($qb->expr()->eq('groupfolder_id', $qb->createNamedParameter($groupfolderId, \OCP\DB\QueryBuilder\IQueryBuilder::PARAM_INT)))
					->andWhere($qb->expr()->in('field_name', $qb->createNamedParameter($fieldsOfInterest, \OCP\DB\QueryBuilder\IQueryBuilder::PARAM_STR_ARRAY)));

				$result = $qb->executeQuery();
				while ($row = $result->fetch()) {
					$fid = (int)$row['file_id'];
					$fname = (string)$row['field_name'];
					$fval = (string)$row['field_value'];
					if (!isset($out[$fid])) {
						$out[$fid] = [];
					}
					$out[$fid][$fname] = $fval;
				}
				$result->closeCursor();
			} catch (\Throwable $e) {
				$this->logger->warning('PhotoStoryService: MetaVox query chunk failed: ' . $e->getMessage());
			}
		}
		return $out;
	}
	/**
	 * Cross-folder variant: batch-fetch MetaVox values without filtering by groupfolder_id.
	 * Permission scoping happens later via $userFolder->getById().
	 *
	 * @param array<int, int> $fileIds
	 * @return array<int, array<string, string>>
	 */
	public function fetchMetaVoxMetaAllGroupfolders(array $fileIds, array $extraFieldNames = []): array {
		if (empty($fileIds)) {
			return [];
		}

		$fieldsOfInterest = array_values(array_unique(array_merge(
			self::WELL_KNOWN_PHOTO_FIELDS,
			array_filter(array_map('strval', $extraFieldNames), fn($n) => $n !== '')
		)));

		// Chunk file_ids to respect NC QueryBuilder's IN-list cap.
		$out = [];
		foreach (array_chunk(array_values(array_unique(array_map('intval', $fileIds))), 500) as $chunk) {
			try {
				$qb = $this->db->getQueryBuilder();
				$qb->select('file_id', 'field_name', 'field_value')
					->from('metavox_file_gf_meta')
					->where($qb->expr()->in('file_id', $qb->createNamedParameter($chunk, \OCP\DB\QueryBuilder\IQueryBuilder::PARAM_INT_ARRAY)))
					->andWhere($qb->expr()->in('field_name', $qb->createNamedParameter($fieldsOfInterest, \OCP\DB\QueryBuilder\IQueryBuilder::PARAM_STR_ARRAY)));

				$result = $qb->executeQuery();
				while ($row = $result->fetch()) {
					$fid = (int)$row['file_id'];
					$fname = (string)$row['field_name'];
					$fval = (string)$row['field_value'];
					if (!isset($out[$fid])) {
						$out[$fid] = [];
					}
					// If same field exists across multiple groupfolders, keep the first (any-folder match).
					if (!isset($out[$fid][$fname])) {
						$out[$fid][$fname] = $fval;
					}
				}
				$result->closeCursor();
			} catch (\Throwable $e) {
				$this->logger->warning('PhotoStoryService: cross-folder MetaVox query chunk failed: ' . $e->getMessage());
			}
		}
		return $out;
	}
	/**
	 * Find file_ids whose MetaVox metadata matches ALL given filters (AND).
	 * Permission scoping is applied later via $userFolder->getById().
	 *
	 * @param array<int, array<string, mixed>> $filters
	 *        Loosely typed on purpose: the controller sanitises these, but this
	 *        method re-checks field and operator anyway, and a stricter shape here
	 *        would make PHPStan flag that second check as dead.
	 * @return array<int, int>
	 */
	public function queryFileIdsMatchingFilters(array $filters): array {
		if (empty($filters)) {
			return [];
		}

		$matchingIdSets = [];

		foreach ($filters as $filter) {
			$field = (string)($filter['field'] ?? '');
			$op = (string)($filter['op'] ?? 'equals');
			$value = $filter['value'] ?? '';

			if ($field === '' || !in_array($op, self::ALLOWED_OPS, true)) {
				// Be defensive — controller should already sanitize, but ignore bad rows here.
				continue;
			}

			try {
				$qb = $this->db->getQueryBuilder();
				$qb->select('file_id')
					->from('metavox_file_gf_meta')
					->where($qb->expr()->eq('field_name', $qb->createNamedParameter($field)));

				switch ($op) {
					case 'equals':
						$qb->andWhere($qb->expr()->eq('field_value', $qb->createNamedParameter((string)$value)));
						break;
					case 'contains':
						$like = '%' . $this->escapeLikePattern((string)$value) . '%';
						$qb->andWhere($qb->expr()->like('field_value', $qb->createNamedParameter($like)));
						break;
					case 'in':
						$values = is_array($value) ? array_map('strval', $value) : [(string)$value];
						$values = array_values(array_filter($values, fn($v) => $v !== ''));
						if (empty($values)) {
							$matchingIdSets[] = [];
							continue 2;
						}
						$qb->andWhere($qb->expr()->in('field_value', $qb->createNamedParameter($values, \OCP\DB\QueryBuilder\IQueryBuilder::PARAM_STR_ARRAY)));
						break;
					case 'year_equals':
						$year = (string)$value;
						if (!preg_match('/^\d{4}$/', $year)) {
							$matchingIdSets[] = [];
							continue 2;
						}
						$like = $year . '%';
						$qb->andWhere($qb->expr()->like('field_value', $qb->createNamedParameter($like)));
						break;
				}

				$result = $qb->executeQuery();
				$ids = [];
				while ($row = $result->fetch()) {
					$ids[(int)$row['file_id']] = true;
				}
				$result->closeCursor();
				$matchingIdSets[] = array_keys($ids);
			} catch (\Throwable $e) {
				$this->logger->warning('PhotoStoryService: filter query failed: ' . $e->getMessage());
				return [];
			}
		}

		if (empty($matchingIdSets)) {
			return [];
		}

		// AND across all filter result sets via array_intersect.
		$intersection = $matchingIdSets[0];
		for ($i = 1, $n = count($matchingIdSets); $i < $n; $i++) {
			$intersection = array_intersect($intersection, $matchingIdSets[$i]);
			if (empty($intersection)) {
				return [];
			}
		}

		return array_values(array_map('intval', $intersection));
	}
	/**
	 * Fetch the top-100 most common values for a given field_name, scoped to
	 * the groupfolder-ids the current user can access. For non-multiselect
	 * fields the aggregation happens in SQL (GROUP BY field_value); multiselect
	 * fields still split in PHP because `;#` is not a SQL-aggregatable value.
	 *
	 * @param int[] $accessibleGfIds
	 * @return array<int, string>
	 */
	public function fetchTopValuesForField(string $fieldName, bool $isMultiselect, array $accessibleGfIds): array {
		// Personal-storage / no-groupfolder context: include rows where
		// groupfolder_id=0 (MetaVox's convention for file-level fields not
		// tied to a specific groupfolder). When the user has actual gf access,
		// merge gf=0 with their accessible gf-ids so values from both scopes
		// are surfaced.
		$scopeIds = !empty($accessibleGfIds) ? array_values(array_unique(array_merge($accessibleGfIds, [0]))) : [0];

		try {
			$qb = $this->db->getQueryBuilder();
			if ($isMultiselect) {
				// Multiselect: need raw values to split client-side.
				$qb->select('field_value')
					->from('metavox_file_gf_meta')
					->where($qb->expr()->eq('field_name', $qb->createNamedParameter($fieldName)))
					->andWhere($qb->expr()->neq('field_value', $qb->createNamedParameter('')))
					->andWhere($qb->expr()->in(
						'groupfolder_id',
						$qb->createNamedParameter($scopeIds, \OCP\DB\QueryBuilder\IQueryBuilder::PARAM_INT_ARRAY)
					));
				$result = $qb->executeQuery();
				$counts = [];
				while ($row = $result->fetch()) {
					$raw = (string)$row['field_value'];
					foreach ($this->splitMultiselect($raw) as $piece) {
						if ($piece !== '') {
							$counts[$piece] = ($counts[$piece] ?? 0) + 1;
						}
					}
				}
				$result->closeCursor();
				arsort($counts);
				return array_slice(array_keys($counts), 0, 100);
			}

			// Single-value: SQL-side GROUP BY + COUNT + ORDER BY + LIMIT.
			$qb->select('field_value', $qb->func()->count('*', 'cnt'))
				->from('metavox_file_gf_meta')
				->where($qb->expr()->eq('field_name', $qb->createNamedParameter($fieldName)))
				->andWhere($qb->expr()->neq('field_value', $qb->createNamedParameter('')))
				->andWhere($qb->expr()->in(
					'groupfolder_id',
					$qb->createNamedParameter($scopeIds, \OCP\DB\QueryBuilder\IQueryBuilder::PARAM_INT_ARRAY)
				))
				->groupBy('field_value')
				->orderBy('cnt', 'DESC')
				->setMaxResults(100);
			$result = $qb->executeQuery();
			$values = [];
			while ($row = $result->fetch()) {
				$values[] = (string)$row['field_value'];
			}
			$result->closeCursor();
			return $values;
		} catch (\Throwable $e) {
			$this->logger->warning('PhotoStoryService: top-values query failed for ' . $fieldName . ': ' . $e->getMessage());
			return [];
		}
	}
	/**
	 * One-shot lookup of every federated storage on this NC instance.
	 *
	 * NC's `\OCA\Files_Sharing\External\Storage::__construct()` writes storage
	 * id = `shared::md5(token + '@' + cloudId->getRemote())`, where the cloud-id
	 * strips a trailing slash from the remote. We mirror that in PHP because
	 * MD5+CONCAT in SQL is dialect-fragile across MySQL/PostgreSQL/SQLite.
	 *
	 * Two cheap queries per request (oc_share_external is usually < 50 rows
	 * even on busy instances; oc_storages.id has a UNIQUE index).
	 *
	 * @return array<int, true>
	 */
	public function loadFederatedStorageIds(): array {
		$result = [];
		try {
			// Step 1: every accepted incoming federated share.
			$qb = $this->db->getQueryBuilder();
			$qb->select('share_token', 'remote')
				->from('share_external')
				->where($qb->expr()->eq('accepted', $qb->createNamedParameter(1, \OCP\DB\QueryBuilder\IQueryBuilder::PARAM_INT)));
			$rs = $qb->executeQuery();
			$expectedIds = [];
			while ($row = $rs->fetch()) {
				$remote = rtrim((string)$row['remote'], '/');
				$token = (string)$row['share_token'];
				if ($token === '' || $remote === '') continue;
				$expectedIds[] = 'shared::' . md5($token . '@' . $remote);
			}
			$rs->closeCursor();
			if (empty($expectedIds)) return $result;

			// Step 2: resolve those storage strings to numeric storage_ids.
			$qb2 = $this->db->getQueryBuilder();
			$qb2->select('numeric_id')
				->from('storages')
				->where($qb2->expr()->in('id', $qb2->createNamedParameter($expectedIds, \OCP\DB\QueryBuilder\IQueryBuilder::PARAM_STR_ARRAY)));
			$rs2 = $qb2->executeQuery();
			while ($row = $rs2->fetch()) {
				$result[(int)$row['numeric_id']] = true;
			}
			$rs2->closeCursor();
		} catch (\Throwable $e) {
			$this->logger->debug('PhotoStoryService: loadFederatedStorageIds failed: ' . $e->getMessage());
		}
		return $result;
	}

	/**
	 * MetaVox stores a multiselect as "a;#b;#c".
	 *
	 * A copy of PhotoStoryService::splitMultiselect(): it is fourteen lines of
	 * pure string work and threading a callable through the constructor for it
	 * would cost more than it saves. If the separator ever changes, both change.
	 */
	private function splitMultiselect(string $raw): array {
		$raw = trim($raw);
		if ($raw === '') {
			return [];
		}
		$parts = explode(';#', $raw);
		$out = [];
		foreach ($parts as $part) {
			$p = trim($part);
			if ($p !== '') {
				$out[] = $p;
			}
		}
		return $out;
	}
}
