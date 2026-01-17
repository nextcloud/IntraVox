<?php
declare(strict_types=1);

namespace OCA\IntraVox\Tests\Mocks;

use OCP\IUser;
use OCP\IUserSession;

/**
 * Mock implementation of IUserSession for testing
 */
class MockUserSession implements IUserSession {
    private ?MockUser $user = null;
    private bool $loggedIn = false;

    public function setUser(?MockUser $user): self {
        $this->user = $user;
        $this->loggedIn = $user !== null;
        return $this;
    }

    public function getUser(): ?IUser {
        return $this->user;
    }

    public function isLoggedIn(): bool {
        return $this->loggedIn;
    }

    /**
     * Create a logged-in session with a test user
     */
    public static function loggedInAs(string $uid, string $displayName = ''): self {
        $session = new self();
        $session->setUser(new MockUser($uid, $displayName ?: $uid));
        return $session;
    }

    /**
     * Create an anonymous (not logged in) session
     */
    public static function anonymous(): self {
        return new self();
    }
}

/**
 * Mock implementation of IUser for testing
 */
class MockUser implements IUser {
    public function __construct(
        private string $uid,
        private string $displayName = ''
    ) {
        if ($this->displayName === '') {
            $this->displayName = $uid;
        }
    }

    public function getUID(): string {
        return $this->uid;
    }

    public function getDisplayName(): string {
        return $this->displayName;
    }

    // Implement other IUser methods as needed (stubs)
    public function setDisplayName($displayName): bool { return true; }
    public function getLastLogin(): int { return time(); }
    public function updateLastLoginTimestamp(): void {}
    public function delete(): bool { return true; }
    public function setPassword(string $password, string $recoveryPassword = null): bool { return true; }
    public function getHome(): string { return '/home/' . $this->uid; }
    public function getBackendClassName(): string { return 'Database'; }
    public function canChangeAvatar(): bool { return true; }
    public function canChangePassword(): bool { return true; }
    public function canChangeDisplayName(): bool { return true; }
    public function isEnabled(): bool { return true; }
    public function setEnabled(bool $enabled = true): void {}
    public function getEMailAddress(): ?string { return $this->uid . '@test.local'; }
    public function getAvatarImage($size): ?\OCP\IImage { return null; }
    public function getCloudId(): string { return $this->uid . '@localhost'; }
    public function setEMailAddress($mailAddress): void {}
    public function getQuota(): string { return 'default'; }
    public function setQuota($quota): void {}
    public function getSystemEMailAddress(): ?string { return null; }
    public function getPrimaryEMailAddress(): ?string { return null; }
    public function getManagerUids(): array { return []; }
}
