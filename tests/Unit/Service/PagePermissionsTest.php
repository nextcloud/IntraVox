<?php
declare(strict_types=1);

namespace OCA\IntraVox\Tests\Unit\Service;

use OCA\IntraVox\Service\PageService;
use OCP\Files\Folder;
use PHPUnit\Framework\TestCase;

/**
 * Unit test for PageService::permissionsFromNode() — the single source of truth
 * for the API's permission object. Proves the issue #70 fix: a read-only
 * GroupFolder / Team Folder member (whose getPermissions() bitmask still reports
 * write, but whose node capability methods are false) is reported as NOT writable.
 *
 * The instance is built without the (25-arg) constructor; permissionsFromNode()
 * only touches permissionsCache (which defaults to []) and the node, so no other
 * dependency is needed.
 */
class PagePermissionsTest extends TestCase {

    /** Subclass exposing the private helper for testing. */
    private function svc(): PageService {
        return new class extends PageService {
            public function __construct() {}
            public function callPermissionsFromNode(\OCP\Files\Node $node): array {
                return $this->permissionsFromNode($node);
            }
        };
    }

    private function node(int $perms, bool $update, bool $create, bool $delete): Folder {
        $n = $this->createMock(Folder::class);
        $n->method('getPath')->willReturn('/lang/page-' . $perms . '-' . (int)$update);
        $n->method('getPermissions')->willReturn($perms);
        $n->method('isUpdateable')->willReturn($update);
        $n->method('isCreatable')->willReturn($create);
        $n->method('isDeletable')->willReturn($delete);
        return $n;
    }

    public function testWritableFolderReportsAllCapabilities(): void {
        // PERMISSION_ALL = 31, node fully capable.
        $p = $this->svc()->callPermissionsFromNode($this->node(31, true, true, true));

        $this->assertTrue($p['canRead']);
        $this->assertTrue($p['canWrite']);
        $this->assertTrue($p['canCreate']);
        $this->assertTrue($p['canDelete']);
        $this->assertTrue($p['canShare']);
        $this->assertSame(31, $p['raw']);
    }

    public function testReadOnlyGroupFolderWithoutAclReportsNoWrite(): void {
        // The issue #70 case: bitmask still says 31 (UPDATE/CREATE/DELETE set)
        // but the node capability methods correctly say false on a read-only mount.
        $p = $this->svc()->callPermissionsFromNode($this->node(31, false, false, false));

        $this->assertTrue($p['canRead'], 'read must remain true');
        $this->assertFalse($p['canWrite'], 'write must be denied despite the bitmask');
        $this->assertFalse($p['canCreate']);
        $this->assertFalse($p['canDelete']);
        // raw is preserved un-AND-ed for API back-compat.
        $this->assertSame(31, $p['raw']);
    }

    public function testAclReadOnlyBitmaskReportsNoWrite(): void {
        // With ACLs the bitmask itself is already read-only (PERMISSION_READ = 1).
        $p = $this->svc()->callPermissionsFromNode($this->node(1, false, false, false));

        $this->assertTrue($p['canRead']);
        $this->assertFalse($p['canWrite']);
        $this->assertFalse($p['canCreate']);
        $this->assertFalse($p['canDelete']);
        $this->assertSame(1, $p['raw']);
    }

    public function testLockedButWritableFolderReportsNoWrite(): void {
        // Bitmask grants write, but the node is not updateable (e.g. locked):
        // the conservative AND denies write — documents intended behaviour.
        $p = $this->svc()->callPermissionsFromNode($this->node(31, false, true, true));

        $this->assertFalse($p['canWrite']);
        $this->assertTrue($p['canCreate']);
        $this->assertTrue($p['canDelete']);
    }
}
