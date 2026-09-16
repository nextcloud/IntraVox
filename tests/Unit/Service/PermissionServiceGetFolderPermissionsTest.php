<?php
declare(strict_types=1);

namespace OCA\IntraVox\Tests\Unit\Service;

use OCA\IntraVox\Service\PermissionService;
use OCP\Files\Folder;
use OCP\Files\IRootFolder;
use OCP\Files\Node;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * getFolderPermissions() resolves a path relative to the IntraVox root through
 * the user's mounted folder view (so GroupFolder ACLs apply) and derives its
 * permissions via permissionsFromNode(). Moved verbatim from PageService to
 * PermissionService (permission-shell step 1); this pins the three branches on
 * its new home so the delegator on PageService can later be collapsed safely.
 */
class PermissionServiceGetFolderPermissionsTest extends TestCase {

    /**
     * A PermissionService with userId/rootFolder/logger set by reflection (the
     * constructor does heavy DI wiring irrelevant here).
     */
    private function service(?string $userId, ?IRootFolder $rootFolder): PermissionService {
        $svc = (new \ReflectionClass(PermissionService::class))->newInstanceWithoutConstructor();
        $set = function (string $prop, $value) use ($svc): void {
            $p = new \ReflectionProperty(PermissionService::class, $prop);
            $p->setValue($svc, $value);
        };
        $set('userId', $userId);
        if ($rootFolder !== null) {
            $set('rootFolder', $rootFolder);
        }
        $set('logger', $this->createMock(LoggerInterface::class));
        return $svc;
    }

    private function node(int $perms): Node {
        $node = $this->createMock(Node::class);
        $node->method('getPath')->willReturn('/tester/files/IntraVox/en/about');
        $node->method('getPermissions')->willReturn($perms);
        $node->method('isUpdateable')->willReturn(($perms & 2) !== 0);
        $node->method('isCreatable')->willReturn(($perms & 4) !== 0);
        $node->method('isDeletable')->willReturn(($perms & 8) !== 0);
        return $node;
    }

    public function testNoUserYieldsAllFalse(): void {
        $svc = $this->service(null, null);

        $this->assertSame(
            ['canRead' => false, 'canWrite' => false, 'canCreate' => false, 'canDelete' => false, 'canShare' => false, 'raw' => 0],
            $svc->getFolderPermissions('en/about')
        );
    }

    public function testResolvedFolderDerivesPermissionsFromTheNode(): void {
        // PERMISSION_ALL (31) -> every capability true.
        $node = $this->node(31);
        $userFolder = $this->createMock(Folder::class);
        $userFolder->method('get')->with('IntraVox/en/about')->willReturn($node);

        $root = $this->createMock(IRootFolder::class);
        $root->method('getUserFolder')->with('tester')->willReturn($userFolder);

        $svc = $this->service('tester', $root);
        $perms = $svc->getFolderPermissions('en/about');

        $this->assertTrue($perms['canRead']);
        $this->assertTrue($perms['canWrite']);
        $this->assertTrue($perms['canCreate']);
        $this->assertTrue($perms['canDelete']);
        $this->assertSame(31, $perms['raw']);
    }

    public function testEmptyPathResolvesTheIntraVoxRootItself(): void {
        $node = $this->node(1); // read-only
        $userFolder = $this->createMock(Folder::class);
        // An empty relativePath must resolve exactly 'IntraVox', no trailing slash.
        $userFolder->method('get')->with('IntraVox')->willReturn($node);

        $root = $this->createMock(IRootFolder::class);
        $root->method('getUserFolder')->willReturn($userFolder);

        $svc = $this->service('tester', $root);
        $perms = $svc->getFolderPermissions('');

        $this->assertTrue($perms['canRead']);
        $this->assertFalse($perms['canWrite']);
    }

    public function testAnUnresolvablePathYieldsAllFalse(): void {
        // userFolder->get() throws (folder gone / no access) -> caught -> all false.
        $userFolder = $this->createMock(Folder::class);
        $userFolder->method('get')->willThrowException(new \OCP\Files\NotFoundException('gone'));

        $root = $this->createMock(IRootFolder::class);
        $root->method('getUserFolder')->willReturn($userFolder);

        $svc = $this->service('tester', $root);

        $this->assertSame(
            ['canRead' => false, 'canWrite' => false, 'canCreate' => false, 'canDelete' => false, 'canShare' => false, 'raw' => 0],
            $svc->getFolderPermissions('en/ghost')
        );
    }
}
