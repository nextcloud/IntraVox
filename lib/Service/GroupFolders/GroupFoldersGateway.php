<?php
declare(strict_types=1);

namespace OCA\IntraVox\Service\GroupFolders;

use OCP\App\IAppManager;
use Psr\Log\LoggerInterface;

/**
 * The one place IntraVox talks to the groupfolders app. (GFG-0, SE-1)
 *
 * Two things were spread across the codebase and are consolidated here.
 *
 * First, the service-locator lookup. Eight sites did
 * `\OC::$server->get(FolderManager::class)` by hand, which is untyped, untestable
 * and silently fatal when the groupfolders app is disabled. FolderManager cannot
 * simply be constructor-injected: groupfolders is an optional app, so the class
 * may not exist at all, and injecting it would make IntraVox fail to boot
 * without it. So the lookup stays lazy — but it happens once, here.
 *
 * Second, the resolution loop. Four call sites walked getAllFolders() looking for
 * a mount point and keeping the highest id, in code that was identical apart from
 * error handling, and two of them carried their own copy of the mount-point
 * extraction. getAllFolders() is three unbounded queries plus an object per row,
 * and getSharedFolder() has 41 call sites, several on the page-render path — so
 * on an instance with thousands of team folders this dominated page load.
 *
 * Resolution is memoised per request. The id is safe to cache; the Folder NODE it
 * resolves to deliberately is not, because that object is user-view dependent and
 * this runs both in request scope and from occ.
 */
class GroupFoldersGateway {

	/** @var array<string,int|null> mount point => folder id, memoised per request */
	private array $folderIdByName = [];

	/** Counts resolutions that actually hit getAllFolders(), for the SE-1 test. */
	private int $resolveCalls = 0;

	public function __construct(
		private readonly IAppManager $appManager,
		private readonly LoggerInterface $logger,
	) {
	}

	/** Is the groupfolders app available at all? */
	public function isAvailable(): bool {
		return $this->appManager->isEnabledForUser('groupfolders');
	}

	/**
	 * The id of the groupfolder mounted under $mountPoint, or null.
	 *
	 * When several groupfolders share a mount point the HIGHEST id wins, which
	 * is what all four original loops did. That is a coin flip dressed up as a
	 * rule, so it is logged: it means the instance has two team folders with the
	 * same name and IntraVox is guessing which one is its own.
	 */
	public function findFolderIdByMountPoint(string $mountPoint): ?int {
		if (array_key_exists($mountPoint, $this->folderIdByName)) {
			return $this->folderIdByName[$mountPoint];
		}

		if (!$this->isAvailable()) {
			$this->logger->warning('[GroupFoldersGateway] groupfolders app is not enabled');

			return $this->folderIdByName[$mountPoint] = null;
		}

		$this->resolveCalls++;

		$folderId = null;
		$highestId = 0;
		$matches = 0;

		foreach ($this->allFolders() as $id => $folderData) {
			if ($this->mountPointOf($folderData) !== $mountPoint) {
				continue;
			}

			$matches++;
			if ((int)$id > $highestId) {
				$folderId = (int)$id;
				$highestId = (int)$id;
			}
		}

		if ($matches > 1) {
			$this->logger->warning(
				'[GroupFoldersGateway] several groupfolders share a mount point; using the highest id',
				['mountPoint' => $mountPoint, 'matches' => $matches, 'chosen' => $folderId]
			);
		}

		return $this->folderIdByName[$mountPoint] = $folderId;
	}

	/**
	 * Create a groupfolder and return its id, or null when that is impossible.
	 * Drops the memoised "not found" so the caller sees what it just created.
	 */
	public function createFolder(string $mountPoint): ?int {
		if (!$this->isAvailable()) {
			return null;
		}

		try {
			$id = (int)$this->folderManager()->createFolder($mountPoint);
			$this->folderIdByName[$mountPoint] = $id;

			return $id;
		} catch (\Throwable $e) {
			$this->logger->error('[GroupFoldersGateway] could not create groupfolder', [
				'mountPoint' => $mountPoint,
				'error' => $e->getMessage(),
			]);

			return null;
		}
	}

	/**
	 * The configuration row of one groupfolder, or null.
	 *
	 * getFolder() takes exactly one argument. An earlier call site passed a
	 * second (a storage id) which PHP silently discarded on a userland method —
	 * harmless until a groupfolders release adds a real second parameter. Wrapped
	 * here so there is one signature to get wrong.
	 */
	/**
	 * Whether Advanced Permissions (per-path ACLs) are switched on for a folder.
	 *
	 * With ACLs off, every member of a group sees the same content, so results
	 * may be cached per group set. With ACLs on that assumption breaks: two
	 * users in the same groups can have different per-path rights, and a
	 * group-keyed cache then serves one user's view to the other (issue #112).
	 *
	 * Returns false when the flag cannot be read, which keeps the cheaper
	 * group-shared caching for installations this cannot inspect -- the
	 * behaviour that shipped before this check existed.
	 */
	public function hasAcl(int $folderId): bool {
		try {
			$folder = $this->getFolder($folderId);
			if ($folder === null) {
				return false;
			}
			if (is_array($folder)) {
				return (bool)($folder['acl'] ?? false);
			}
			if (is_object($folder) && property_exists($folder, 'acl')) {
				return (bool)$folder->acl;
			}
		} catch (\Throwable $e) {
			$this->logger->debug('[IntraVox] Could not determine ACL flag for groupfolder ' . $folderId . ': ' . $e->getMessage());
		}

		return false;
	}

	public function getFolder(int $folderId): mixed {
		if (!$this->isAvailable()) {
			return null;
		}

		try {
			return $this->folderManager()->getFolder($folderId);
		} catch (\Throwable $e) {
			$this->logger->error('[GroupFoldersGateway] getFolder() failed', [
				'folderId' => $folderId,
				'error' => $e->getMessage(),
			]);

			return null;
		}
	}

	/**
	 * The ids of every groupfolder on the instance.
	 *
	 * Only the orphan scan wants this — everything else should resolve a single
	 * folder by mount point instead of walking them all.
	 *
	 * @return list<int>
	 */
	public function allFolderIds(): array {
		if (!$this->isAvailable()) {
			return [];
		}

		// NOT [...$folders]: the spread operator renumbers keys, which would turn
		// the folder ids into 0,1,2… and hand the orphan scan the wrong list.
		$ids = [];
		foreach ($this->allFolders() as $id => $_) {
			$ids[] = (int)$id;
		}

		return $ids;
	}

	/**
	 * The groupfolders mounted for one user, as the raw rows groupfolders
	 * returns. The shape differs per release (assoc array vs FolderDefinition),
	 * so the caller still normalises; this only centralises the lookup.
	 *
	 * @return iterable<mixed>
	 */
	public function foldersForUser(\OCP\IUser $user): iterable {
		if (!$this->isAvailable()) {
			return [];
		}

		try {
			return $this->folderManager()->getFoldersForUser($user);
		} catch (\Throwable $e) {
			$this->logger->error('[GroupFoldersGateway] getFoldersForUser() failed', [
				'error' => $e->getMessage(),
			]);

			return [];
		}
	}

	/**
	 * Forget what we resolved. Setup creates folders inside a request that may
	 * already have memoised their absence.
	 */
	public function forget(?string $mountPoint = null): void {
		if ($mountPoint === null) {
			$this->folderIdByName = [];

			return;
		}

		unset($this->folderIdByName[$mountPoint]);
	}

	/** How often a resolution reached getAllFolders() this request (SE-1 test). */
	public function resolveCallCount(): int {
		return $this->resolveCalls;
	}

	/**
	 * The raw FolderManager, for the few operations this gateway does not wrap.
	 * Prefer adding a method here over calling this from a service.
	 */
	public function folderManager(): object {
		return \OC::$server->get(\OCA\GroupFolders\Folder\FolderManager::class);
	}

	/** @return iterable<int|string,mixed> */
	private function allFolders(): iterable {
		try {
			return $this->folderManager()->getAllFolders();
		} catch (\Throwable $e) {
			$this->logger->error('[GroupFoldersGateway] getAllFolders() failed', [
				'error' => $e->getMessage(),
			]);

			return [];
		}
	}

	/**
	 * The mount point of a row, which groupfolders returns as an array on some
	 * versions and an object on others. Both shapes were handled by two separate
	 * inline copies before this.
	 */
	private function mountPointOf(mixed $folderData): ?string {
		if (is_object($folderData)) {
			if (property_exists($folderData, 'mountPoint')) {
				return $folderData->mountPoint;
			}

			return method_exists($folderData, 'getMountPoint') ? $folderData->getMountPoint() : null;
		}

		return is_array($folderData) ? ($folderData['mount_point'] ?? null) : null;
	}
}
