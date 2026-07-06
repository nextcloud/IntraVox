<?php
declare(strict_types=1);

namespace OCA\IntraVox\Tests\Unit\Service;

use OCA\IntraVox\Service\LanguageService;
use OCA\IntraVox\Service\SetupService;
use OCA\IntraVox\Tests\Mocks\MockGroupManager;
use OCA\IntraVox\Tests\Mocks\MockUser;
use OCA\IntraVox\Tests\Mocks\MockUserSession;
use OCP\App\IAppManager;
use OCP\Files\IRootFolder;
use OCP\IConfig;
use OCP\Share\IManager as IShareManager;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Unit tests for SetupService's groupfolder member resolution (#71).
 *
 * getGroupfolderObject() now resolves the groupfolder via a member's mounted view
 * (object-storage safe) instead of the raw /__groupfolders path. These tests prove
 * the member selection: session user first, then an enabled member of the write
 * groups, with graceful null when none exist.
 */
class SetupServiceResolutionTest extends TestCase {

    private const ADMIN_GROUP = 'IntraVox Admins';
    private const EDITOR_GROUP = 'IntraVox Editors';
    private const USER_GROUP = 'IntraVox Users';

    /** Build a SetupService whose protected resolution helpers are exposed. */
    private function svc(MockUserSession $session, MockGroupManager $groups): SetupService {
        return new class(
            $this->createMock(IRootFolder::class),
            $this->createMock(IConfig::class),
            $this->createMock(LoggerInterface::class),
            $session,
            $this->createMock(IShareManager::class),
            $groups,
            $this->createMock(LanguageService::class),
            $this->createMock(IAppManager::class)
        ) extends SetupService {
            public function callResolve(): ?string {
                return $this->resolveGroupfolderMemberUser();
            }
        };
    }

    public function testSessionMemberIsChosenFirst(): void {
        $session = MockUserSession::loggedInAs('support');
        $groups = MockGroupManager::noAdmins();
        $groups->addUserToGroup('support', self::ADMIN_GROUP);

        $this->assertSame('support', $this->svc($session, $groups)->callResolve());
    }

    public function testSessionNonMemberIsSkippedForGroupMember(): void {
        // Session user 'alice' is in no IntraVox group; an admin 'bob' exists.
        $session = MockUserSession::loggedInAs('alice');
        $groups = MockGroupManager::noAdmins();
        $groups->setGroupMembers(self::ADMIN_GROUP, [new MockUser('bob')]);

        $this->assertSame('bob', $this->svc($session, $groups)->callResolve());
    }

    public function testOccContextPicksAdminGroupMember(): void {
        // No session user (OCC / repair step).
        $session = MockUserSession::anonymous();
        $groups = MockGroupManager::noAdmins();
        $groups->setGroupMembers(self::ADMIN_GROUP, [new MockUser('carol')]);

        $this->assertSame('carol', $this->svc($session, $groups)->callResolve());
    }

    public function testFallsThroughToEditorGroupWhenAdminsEmpty(): void {
        $session = MockUserSession::anonymous();
        $groups = MockGroupManager::noAdmins();
        $groups->setGroupMembers(self::ADMIN_GROUP, []);          // no admins
        $groups->setGroupMembers(self::EDITOR_GROUP, [new MockUser('dave')]);

        $this->assertSame('dave', $this->svc($session, $groups)->callResolve());
    }

    public function testReturnsNullWhenNoMembersAnywhere(): void {
        $session = MockUserSession::anonymous();
        $groups = MockGroupManager::noAdmins(); // no group members registered

        $this->assertNull($this->svc($session, $groups)->callResolve());
    }

    public function testDisabledMemberIsSkipped(): void {
        $session = MockUserSession::anonymous();
        $groups = MockGroupManager::noAdmins();
        $disabled = new MockUser('erin');
        $disabled->setEnabled(false);
        $enabled = new MockUser('frank');
        $groups->setGroupMembers(self::ADMIN_GROUP, [$disabled, $enabled]);

        $this->assertSame('frank', $this->svc($session, $groups)->callResolve());
    }

    public function testReadOnlySessionUserStillCountsAsMember(): void {
        // A session user who is only in IntraVox Users is still a member (the mount
        // exists); resolution returns them. (Any later write would fail per ACL, but
        // that is out of scope here — the web import path is admin-only anyway.)
        $session = MockUserSession::loggedInAs('reader');
        $groups = MockGroupManager::noAdmins();
        $groups->addUserToGroup('reader', self::USER_GROUP);

        $this->assertSame('reader', $this->svc($session, $groups)->callResolve());
    }
}
