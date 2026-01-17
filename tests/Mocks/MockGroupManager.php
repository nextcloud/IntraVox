<?php
declare(strict_types=1);

namespace OCA\IntraVox\Tests\Mocks;

use OCP\IGroupManager;

/**
 * Mock implementation of IGroupManager for testing
 */
class MockGroupManager implements IGroupManager {
    private array $adminUsers = [];
    private array $userGroups = [];

    /**
     * Set which users are admins
     */
    public function setAdmins(array $uids): self {
        $this->adminUsers = array_flip($uids);
        return $this;
    }

    /**
     * Add a user to admin group
     */
    public function addAdmin(string $uid): self {
        $this->adminUsers[$uid] = true;
        return $this;
    }

    /**
     * Set group memberships for a user
     */
    public function setUserGroups(string $uid, array $groups): self {
        $this->userGroups[$uid] = array_flip($groups);
        return $this;
    }

    /**
     * Add user to a group
     */
    public function addUserToGroup(string $uid, string $gid): self {
        if (!isset($this->userGroups[$uid])) {
            $this->userGroups[$uid] = [];
        }
        $this->userGroups[$uid][$gid] = true;
        return $this;
    }

    public function isAdmin(string $uid): bool {
        return isset($this->adminUsers[$uid]);
    }

    public function isInGroup(string $uid, string $gid): bool {
        return isset($this->userGroups[$uid][$gid]);
    }

    // Implement other IGroupManager methods as stubs
    public function get($gid) { return null; }
    public function groupExists($gid): bool { return false; }
    public function createGroup($gid) { return null; }
    public function search(string $search, ?int $limit = null, ?int $offset = null): array { return []; }
    public function getUserGroups($user): array { return []; }
    public function getUserGroupIds($user): array {
        $uid = is_string($user) ? $user : $user->getUID();
        return array_keys($this->userGroups[$uid] ?? []);
    }
    public function displayNamesInGroup($gid, $search = '', $limit = -1, $offset = 0): array { return []; }
    public function getSubAdmin() { return null; }
    public function listen($scope, $method, callable $callback): void {}
    public function removeListener($scope = null, $method = null, ?callable $callback = null): void {}

    /**
     * Factory method for tests with an admin user
     */
    public static function withAdmin(string $adminUid): self {
        return (new self())->addAdmin($adminUid);
    }

    /**
     * Factory method for tests with no admins
     */
    public static function noAdmins(): self {
        return new self();
    }
}
