<?php
declare(strict_types=1);

namespace OCA\IntraVox\Service;

use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;
use Psr\Log\LoggerInterface;

/**
 * Service for managing pessimistic page locks.
 *
 * When a user starts editing a page, a lock is acquired to prevent
 * concurrent edits. Locks auto-expire after 15 minutes without a heartbeat.
 */
class PageLockService {
	private const TABLE = 'intravox_page_locks';
	private const LOCK_TIMEOUT_MINUTES = 15;

	public function __construct(
		private IDBConnection $db,
		private LoggerInterface $logger
	) {}

	/**
	 * Get the active (non-expired) lock for a page.
	 *
	 * @return array|null {pageUniqueId, userId, displayName, lockedSince, lastHeartbeat} or null
	 */
	public function getLock(string $pageUniqueId): ?array {
		$this->cleanExpiredLock($pageUniqueId);

		$qb = $this->db->getQueryBuilder();
		$qb->select('user_id', 'display_name', 'created_at', 'updated_at')
			->from(self::TABLE)
			->where($qb->expr()->eq('page_unique_id', $qb->createNamedParameter($pageUniqueId)));

		$result = $qb->executeQuery();
		$row = $result->fetch();
		$result->closeCursor();

		if (!$row) {
			return null;
		}

		return [
			'pageUniqueId' => $pageUniqueId,
			'userId' => $row['user_id'],
			'displayName' => $row['display_name'],
			'lockedSince' => $row['created_at'],
			'lastHeartbeat' => $row['updated_at'],
		];
	}

	/**
	 * Attempt to acquire a lock on a page.
	 *
	 * If the same user already holds the lock, it is refreshed (re-entrant).
	 * If another user holds the lock, acquisition fails with lock info.
	 *
	 * @return array {success: bool, lock?: array}
	 */
	public function acquireLock(string $pageUniqueId, string $userId, string $displayName): array {
		$this->cleanExpiredLock($pageUniqueId);

		$existing = $this->getLock($pageUniqueId);
		if ($existing !== null) {
			if ($existing['userId'] === $userId) {
				// Same user — refresh the lock
				$this->refreshLock($pageUniqueId, $userId);
				return ['success' => true];
			}
			// Different user holds the lock
			return ['success' => false, 'lock' => $existing];
		}

		// Insert new lock
		$now = new \DateTime();
		$qb = $this->db->getQueryBuilder();
		$qb->insert(self::TABLE)
			->values([
				'page_unique_id' => $qb->createNamedParameter($pageUniqueId),
				'user_id' => $qb->createNamedParameter($userId),
				'display_name' => $qb->createNamedParameter($displayName),
				'created_at' => $qb->createNamedParameter($now, IQueryBuilder::PARAM_DATETIME_MUTABLE),
				'updated_at' => $qb->createNamedParameter($now, IQueryBuilder::PARAM_DATETIME_MUTABLE),
			]);

		try {
			$qb->executeStatement();
			$this->logger->info('[PageLockService] Lock acquired', [
				'pageId' => $pageUniqueId, 'userId' => $userId,
			]);
			return ['success' => true];
		} catch (\Exception $e) {
			// Race condition: another user inserted between our check and insert.
			// The UNIQUE index on page_unique_id prevents duplicates.
			$existing = $this->getLock($pageUniqueId);
			return ['success' => false, 'lock' => $existing];
		}
	}

	/**
	 * Refresh the lock heartbeat. Only succeeds if the lock belongs to the given user.
	 */
	public function refreshLock(string $pageUniqueId, string $userId): bool {
		$qb = $this->db->getQueryBuilder();
		$qb->update(self::TABLE)
			->set('updated_at', $qb->createNamedParameter(new \DateTime(), IQueryBuilder::PARAM_DATETIME_MUTABLE))
			->where($qb->expr()->eq('page_unique_id', $qb->createNamedParameter($pageUniqueId)))
			->andWhere($qb->expr()->eq('user_id', $qb->createNamedParameter($userId)));

		return $qb->executeStatement() > 0;
	}

	/**
	 * Release a lock. Only the lock owner can release it.
	 */
	public function releaseLock(string $pageUniqueId, string $userId): bool {
		$qb = $this->db->getQueryBuilder();
		$qb->delete(self::TABLE)
			->where($qb->expr()->eq('page_unique_id', $qb->createNamedParameter($pageUniqueId)))
			->andWhere($qb->expr()->eq('user_id', $qb->createNamedParameter($userId)));

		$deleted = $qb->executeStatement();
		if ($deleted > 0) {
			$this->logger->info('[PageLockService] Lock released', [
				'pageId' => $pageUniqueId, 'userId' => $userId,
			]);
		}
		return $deleted > 0;
	}

	/**
	 * Force-release a lock regardless of owner (admin action).
	 */
	public function forceReleaseLock(string $pageUniqueId, string $adminUserId): bool {
		$lock = $this->getLock($pageUniqueId);

		$qb = $this->db->getQueryBuilder();
		$qb->delete(self::TABLE)
			->where($qb->expr()->eq('page_unique_id', $qb->createNamedParameter($pageUniqueId)));

		$deleted = $qb->executeStatement();
		if ($deleted > 0) {
			$this->logger->info('[PageLockService] Lock force-released by admin', [
				'pageId' => $pageUniqueId,
				'adminUserId' => $adminUserId,
				'lockedBy' => $lock['userId'] ?? 'unknown',
			]);
		}
		return $deleted > 0;
	}

	/**
	 * Check if a page is locked by someone other than the given user.
	 *
	 * @return array|null Lock info if locked by another user, null otherwise
	 */
	public function isLockedByOther(string $pageUniqueId, string $userId): ?array {
		$lock = $this->getLock($pageUniqueId);
		if ($lock !== null && $lock['userId'] !== $userId) {
			return $lock;
		}
		return null;
	}

	/**
	 * Remove expired lock for a specific page.
	 */
	private function cleanExpiredLock(string $pageUniqueId): void {
		$expiry = new \DateTime();
		$expiry->modify('-' . self::LOCK_TIMEOUT_MINUTES . ' minutes');

		$qb = $this->db->getQueryBuilder();
		$qb->delete(self::TABLE)
			->where($qb->expr()->eq('page_unique_id', $qb->createNamedParameter($pageUniqueId)))
			->andWhere($qb->expr()->lt('updated_at', $qb->createNamedParameter($expiry, IQueryBuilder::PARAM_DATETIME_MUTABLE)));

		$qb->executeStatement();
	}
}
