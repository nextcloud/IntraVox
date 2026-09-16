<?php
declare(strict_types=1);

namespace OCA\IntraVox\Service\Locator;

use OCP\Files\IRootFolder;
use OCP\Files\Node;

/**
 * Resolve the IntraVox groupfolder from the current user's perspective (Phase 7).
 *
 * FooterService, HomepageService and NavigationService each carried a
 * byte-identical private getIntraVoxFolder(): throw when there is no user, then
 * return userFolder->get('IntraVox'). Going through the user's folder is what
 * makes GroupFolder ACLs apply. This is the shared home for that lookup.
 *
 * NOTE: PageService deliberately does NOT use this — its own getIntraVoxFolder is
 * a test seam with different semantics (a silent 'en' fallback and an
 * unconditional create), so it stays separate.
 */
class IntraVoxFolderResolver {

    public function __construct(
        private IRootFolder $rootFolder,
        private ?string $userId,
    ) {
    }

    /**
     * @throws \Exception when there is no logged-in user
     */
    public function resolve(): Node {
        if (!$this->userId) {
            throw new \Exception('User not logged in');
        }

        // Get user's folder (this respects GroupFolder ACL)
        $userFolder = $this->rootFolder->getUserFolder($this->userId);

        // Get folder from user's perspective (mounted GroupFolder)
        return $userFolder->get('IntraVox');
    }
}
