<?php
declare(strict_types=1);

namespace OCA\IntraVox\Controller;

use OCP\IGroupManager;
use OCP\IUserSession;

/**
 * "Is the caller a Nextcloud admin?" — asked in one place.
 *
 * Five controllers carried a byte-identical private copy of this, and the
 * ApiController split (PR-A) was about to make it seven. It gates the routes
 * that carry no #[NoAdminRequired] AND is checked in the body of another 23
 * (see docs/route-table.md, "admin (checked in body)"), so it is a security
 * primitive, not a convenience.
 *
 * Fails closed on an anonymous caller: no user, no admin. Worth stating,
 * because these are exactly the endpoints where a null user must not read as
 * "unknown, so allow".
 *
 * @property IUserSession $userSession
 * @property IGroupManager $groupManager
 */
trait ChecksAdminAccess {
    protected function isAdmin(): bool {
        $user = $this->userSession->getUser();
        if ($user === null) {
            return false;
        }
        return $this->groupManager->isAdmin($user->getUID());
    }
}
