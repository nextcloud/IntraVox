<?php
declare(strict_types=1);

namespace OCA\IntraVox\Controller;

use OCA\IntraVox\Service\PageLockService;
use OCA\IntraVox\Service\PermissionService;
use OCA\IntraVox\Service\Read\PageReadService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\Attribute\NoCSRFRequired;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\DataResponse;
use OCP\IRequest;
use OCP\IUserSession;
use Psr\Log\LoggerInterface;

/**
 * Controller for page lock management (pessimistic locking)
 */
class PageLockController extends Controller {
	use RequiresPagePermission;

	public function __construct(
		string $appName,
		IRequest $request,
		private PageLockService $lockService,
		private PermissionService $permissionService,
		private PageReadService $pageRead,
		private IUserSession $userSession,
		private LoggerInterface $logger
	) {
		parent::__construct($appName, $request);
	}

	protected function getPageReadService(): PageReadService {
		return $this->pageRead;
	}

	/**
	 * Require write permission on the page before a lock may be taken/held.
	 *
	 * Returns a DataResponse to return on refusal, or null when allowed.
	 * requireWritablePage() resolves the page through getPage(), which THROWS
	 * for a user who has no IntraVox folder at all (raw:0) — left uncaught that
	 * surfaced as a 500 with a leaky message. A user who cannot resolve the page
	 * plainly may not write it, so any resolution failure is a clean 403.
	 */
	private function denyUnlessMayLock(string $pageId): ?DataResponse {
		try {
			$page = $this->requireWritablePage($pageId, 'cannot lock this page');
		} catch (\Exception $e) {
			return new DataResponse(['error' => 'Permission denied'], Http::STATUS_FORBIDDEN);
		}
		return $page instanceof DataResponse ? $page : null;
	}

	/**
	 * Get the current lock status for a page.
	 *
	 */
	#[NoAdminRequired]
	#[NoCSRFRequired]
	public function getLock(string $pageId): DataResponse {
		$lock = $this->lockService->getLock($pageId);
		return new DataResponse(['lock' => $lock]);
	}

	/**
	 * Acquire a lock on a page before editing.
	 *
	 * Returns 409 Conflict if the page is already locked by another user.
	 *
	 */
	#[NoAdminRequired]
	public function acquireLock(string $pageId): DataResponse {
		$user = $this->userSession->getUser();
		if ($user === null) {
			return new DataResponse(['error' => 'Not authenticated'], Http::STATUS_UNAUTHORIZED);
		}

		// A lock is an edit primitive: only a user who may write the page may
		// take it. Without this check any authenticated user — even one with no
		// IntraVox access — could lock any page and block its editors (IV-07).
		$denied = $this->denyUnlessMayLock($pageId);
		if ($denied !== null) {
			return $denied;
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
	 */
	#[NoAdminRequired]
	public function refreshLock(string $pageId): DataResponse {
		$user = $this->userSession->getUser();
		if ($user === null) {
			return new DataResponse(['error' => 'Not authenticated'], Http::STATUS_UNAUTHORIZED);
		}

		// Same gate as acquireLock: refreshing is holding the edit lock.
		$denied = $this->denyUnlessMayLock($pageId);
		if ($denied !== null) {
			return $denied;
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
	 */
	#[NoAdminRequired]
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
	 */
	#[NoAdminRequired]
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
