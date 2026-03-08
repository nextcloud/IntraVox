<?php
declare(strict_types=1);

namespace OCA\IntraVox\Controller;

use OCA\IntraVox\Service\PageLockService;
use OCA\IntraVox\Service\PermissionService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\DataResponse;
use OCP\IRequest;
use OCP\IUserSession;
use Psr\Log\LoggerInterface;

/**
 * Controller for page lock management (pessimistic locking)
 */
class PageLockController extends Controller {
	public function __construct(
		string $appName,
		IRequest $request,
		private PageLockService $lockService,
		private PermissionService $permissionService,
		private IUserSession $userSession,
		private LoggerInterface $logger
	) {
		parent::__construct($appName, $request);
	}

	/**
	 * Get the current lock status for a page.
	 *
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 */
	public function getLock(string $pageId): DataResponse {
		$lock = $this->lockService->getLock($pageId);
		return new DataResponse(['lock' => $lock]);
	}

	/**
	 * Acquire a lock on a page before editing.
	 *
	 * Returns 409 Conflict if the page is already locked by another user.
	 *
	 * @NoAdminRequired
	 */
	public function acquireLock(string $pageId): DataResponse {
		$user = $this->userSession->getUser();
		if ($user === null) {
			return new DataResponse(['error' => 'Not authenticated'], Http::STATUS_UNAUTHORIZED);
		}

		$result = $this->lockService->acquireLock($pageId, $user->getUID(), $user->getDisplayName());

		if ($result['success']) {
			return new DataResponse(['success' => true]);
		}

		return new DataResponse([
			'success' => false,
			'lock' => $result['lock'],
		], Http::STATUS_CONFLICT);
	}

	/**
	 * Refresh the lock heartbeat to prevent auto-expiry.
	 *
	 * Returns 409 Conflict if the lock was lost (expired or taken by another user).
	 *
	 * @NoAdminRequired
	 */
	public function refreshLock(string $pageId): DataResponse {
		$user = $this->userSession->getUser();
		if ($user === null) {
			return new DataResponse(['error' => 'Not authenticated'], Http::STATUS_UNAUTHORIZED);
		}

		$refreshed = $this->lockService->refreshLock($pageId, $user->getUID());

		if ($refreshed) {
			return new DataResponse(['success' => true]);
		}

		return new DataResponse([
			'success' => false,
			'error' => 'Lock lost',
		], Http::STATUS_CONFLICT);
	}

	/**
	 * Release a page lock after saving or cancelling.
	 *
	 * @NoAdminRequired
	 */
	public function releaseLock(string $pageId): DataResponse {
		$user = $this->userSession->getUser();
		if ($user === null) {
			return new DataResponse(['error' => 'Not authenticated'], Http::STATUS_UNAUTHORIZED);
		}

		$this->lockService->releaseLock($pageId, $user->getUID());
		return new DataResponse(['success' => true]);
	}

	/**
	 * Force-release a page lock (admin only).
	 *
	 * Allows IntraVox admins to remove a lock held by another user,
	 * e.g. after a browser crash where the lock was not released.
	 *
	 * @NoAdminRequired
	 */
	public function forceReleaseLock(string $pageId): DataResponse {
		$user = $this->userSession->getUser();
		if ($user === null) {
			return new DataResponse(['error' => 'Not authenticated'], Http::STATUS_UNAUTHORIZED);
		}

		if (!$this->permissionService->isAdmin()) {
			return new DataResponse(['error' => 'Admin access required'], Http::STATUS_FORBIDDEN);
		}

		$this->lockService->forceReleaseLock($pageId, $user->getUID());
		return new DataResponse(['success' => true]);
	}
}
