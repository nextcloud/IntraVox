<?php
declare(strict_types=1);

namespace OCA\IntraVox\Tests\Unit\Service;

use OCA\IntraVox\Service\GroupFolders\GroupFoldersGateway;
use OCA\IntraVox\Service\PermissionService;
use OCP\IUser;
use OCP\IUserManager;
use PHPUnit\Framework\TestCase;

/**
 * canExport()/canImport() gate on administering the IntraVox team folder.
 *
 * Both methods existed before this and were never called: every export
 * endpoint carried #[NoAdminRequired] with no check in the body, so any
 * logged-in account could download the whole intranet (pentest IV-01, live
 * reproduced — a user with raw:0 pulled 196 pages). The endpoints now call
 * these, which makes them a security boundary rather than dead code, so the
 * cases that must deny are pinned here.
 *
 * The authorisation is deliberately NOT the NC admin group it replaced.
 * Export and import act on the whole folder, and the multi-site design assigns
 * that kind of structural permission to the administrator of the team folder —
 * which groupfolders already answers via FolderManager::canManageACL(). Test
 * three therefore pins the widening that is the point of the change: a folder
 * manager who is not a Nextcloud admin must pass.
 */
class ExportImportGateTest extends TestCase {

    /**
     * A PermissionService with the two collaborators the gate touches wired,
     * and groupfolder resolution forced to a known id. Following
     * PermissionFailClosedTest: subclass past the container, then set the
     * private properties by reflection.
     */
    private function svc(
        ?int $folderId,
        bool $canManage,
        bool $userExists = true,
        ?string $userId = 'alice'
    ): PermissionService {
        $svc = new class extends PermissionService {
            public ?int $stubFolderId = null;
            public function __construct() {
            }
            protected function resolveGroupFolderId(string $folderName): ?int {
                return $this->stubFolderId;
            }
        };
        $svc->stubFolderId = $folderId;

        $user = $this->createMock(IUser::class);
        $user->method('getUID')->willReturn((string)$userId);

        $userManager = $this->createMock(IUserManager::class);
        $userManager->method('get')->willReturn($userExists ? $user : null);

        $gateway = $this->createMock(GroupFoldersGateway::class);
        $gateway->method('canManageAcl')->willReturn($canManage);

        foreach ([
            'userManager' => $userManager,
            'groupFolders' => $gateway,
            'userId' => $userId,
        ] as $property => $value) {
            (new \ReflectionProperty(PermissionService::class, $property))->setValue($svc, $value);
        }

        return $svc;
    }

    public function testFolderManagerMayExportAndImport(): void {
        $svc = $this->svc(folderId: 3, canManage: true);

        $this->assertTrue($svc->canExport(), 'a manager of the IntraVox team folder may export');
        $this->assertTrue($svc->canImport(), 'a manager of the IntraVox team folder may import');
    }

    public function testNonManagerMayNotExportOrImport(): void {
        $svc = $this->svc(folderId: 3, canManage: false);

        $this->assertFalse($svc->canExport(), 'IV-01: a user who does not administer the folder must not export');
        $this->assertFalse($svc->canImport(), 'a user who does not administer the folder must not import');
    }

    /**
     * The widening that motivates the change. canManageACL() is true here
     * while the NC admin group is not consulted at all — the old gate would
     * have denied this caller.
     */
    public function testFolderManagerWithoutNextcloudAdminStillPasses(): void {
        $svc = $this->svc(folderId: 3, canManage: true);

        $this->assertTrue(
            $svc->canExport(),
            'delegated folder managers are exactly who this gate is meant to admit'
        );
    }

    public function testAnonymousCallerIsDenied(): void {
        $svc = $this->svc(folderId: 3, canManage: true, userId: null);

        $this->assertFalse($svc->canExport(), 'no session must never mean allowed');
        $this->assertFalse($svc->canImport(), 'no session must never mean allowed');
    }

    public function testUnknownUserIsDenied(): void {
        $svc = $this->svc(folderId: 3, canManage: true, userExists: false);

        $this->assertFalse($svc->canExport(), 'a uid that resolves to no user must be denied');
    }

    /**
     * Resolution is by mount point name, so renaming the team folder makes the
     * id unresolvable. That must deny everyone rather than admit anyone — the
     * same fail-closed direction PermissionFailClosedTest pins for content.
     */
    public function testUnresolvableFolderDeniesEvenAManager(): void {
        $svc = $this->svc(folderId: null, canManage: true);

        $this->assertFalse($svc->canExport(), 'no resolvable folder must fail closed');
        $this->assertFalse($svc->canImport(), 'no resolvable folder must fail closed');
    }
}
