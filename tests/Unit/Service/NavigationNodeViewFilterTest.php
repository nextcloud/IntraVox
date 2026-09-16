<?php
declare(strict_types=1);

namespace OCA\IntraVox\Tests\Unit\Service;

use OCA\IntraVox\Service\PermissionService;
use OCP\Files\Folder;
use OCP\Files\IRootFolder;
use OCP\Files\Node;
use OCP\Files\NotFoundException;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * filterNavigation() decides menu visibility through the user's mounted folder
 * view (getFolderPermissions → permissionsFromNode), NOT the raw-SQL
 * canRead()/getPermissions() path it used before.
 *
 * WHY the swap is safe: an ACL-equivalence check compared both paths' canRead
 * for two users (one with a per-user `-read` deny on a nested subtree) across
 * every page path and found ZERO divergence — the groupfolders mount applies the
 * same ACL to the Node's bits that applyAclRules() reconstructs from the ACL
 * tables. WHY the swap is worth it: the node view reads bits the mount already
 * computed (~0.4ms/path, request-memoised) instead of issuing per-path-segment
 * filecache/group_folders_acl queries (~3.3ms/item) — the bulk of the 379ms nav
 * render, run twice for editors.
 *
 * These tests pin the READ DECISION filterNavigation now makes: a readable node
 * keeps its item, a denied node (whose get() throws, mapped to canRead=false)
 * drops it. That fail-closed mapping is the same one
 * {@see PermissionServiceGetFolderPermissionsTest::testAnUnresolvablePathYieldsAllFalse}
 * pins on getFolderPermissions().
 */
class NavigationNodeViewFilterTest extends TestCase {

    /**
     * A PermissionService with only the fields getFolderPermissions() touches,
     * set by reflection (the constructor does heavy DI wiring irrelevant here).
     * $nodesByPath maps an 'IntraVox/...'-relative get() argument to the Node the
     * user's folder view returns, or to a NotFoundException to model an ACL deny.
     *
     * @param array<string, Node|NotFoundException> $nodesByPath
     */
    private function service(string $userId, array $nodesByPath): PermissionService {
        $userFolder = $this->createMock(Folder::class);
        $userFolder->method('get')->willReturnCallback(
            function (string $path) use ($nodesByPath) {
                $answer = $nodesByPath[$path] ?? new NotFoundException('no node for ' . $path);
                if ($answer instanceof \Throwable) {
                    throw $answer;
                }
                return $answer;
            }
        );

        $root = $this->createMock(IRootFolder::class);
        $root->method('getUserFolder')->with($userId)->willReturn($userFolder);

        $svc = (new \ReflectionClass(PermissionService::class))->newInstanceWithoutConstructor();
        $set = function (string $prop, $value) use ($svc): void {
            (new \ReflectionProperty(PermissionService::class, $prop))->setValue($svc, $value);
        };
        $set('userId', $userId);
        $set('rootFolder', $root);
        $set('logger', $this->createMock(LoggerInterface::class));
        return $svc;
    }

    /** A node whose permission bits are exactly $perms (READ = bit 1). */
    private function node(string $absPath, int $perms): Node {
        $node = $this->createMock(Node::class);
        $node->method('getPath')->willReturn($absPath);
        $node->method('getPermissions')->willReturn($perms);
        $node->method('isUpdateable')->willReturn(($perms & 2) !== 0);
        $node->method('isCreatable')->willReturn(($perms & 4) !== 0);
        $node->method('isDeletable')->willReturn(($perms & 8) !== 0);
        return $node;
    }

    public function testAReadableItemIsKept(): void {
        $svc = $this->service('reader', [
            'IntraVox/en/about' => $this->node('/reader/files/IntraVox/en/about', 1), // read
        ]);

        $items = [['uniqueId' => 'p-about', 'title' => 'About']];
        $map = ['p-about' => 'en/about'];

        $result = $svc->filterNavigation($items, 'en', $map);

        $this->assertCount(1, $result);
        $this->assertSame('p-about', $result[0]['uniqueId']);
    }

    public function testADeniedItemIsDropped(): void {
        // The node view throws for the denied path (a per-user `-read` ACL makes
        // get() fail), which getFolderPermissions maps to canRead=false.
        $svc = $this->service('reader', [
            'IntraVox/en/secret' => new NotFoundException('acl denied'),
        ]);

        $items = [['uniqueId' => 'p-secret', 'title' => 'Secret']];
        $map = ['p-secret' => 'en/secret'];

        $result = $svc->filterNavigation($items, 'en', $map);

        $this->assertSame([], $result, 'A denied page must not appear in the menu');
    }

    public function testAReadZeroBitmaskAlsoDrops(): void {
        // Belt and braces: even when the node resolves, a missing READ bit drops it.
        $svc = $this->service('reader', [
            'IntraVox/en/hidden' => $this->node('/reader/files/IntraVox/en/hidden', 0),
        ]);

        $result = $svc->filterNavigation(
            [['uniqueId' => 'p-hidden', 'title' => 'Hidden']],
            'en',
            ['p-hidden' => 'en/hidden']
        );

        $this->assertSame([], $result);
    }

    public function testMixedTreeKeepsOnlyReadableItems(): void {
        $svc = $this->service('reader', [
            'IntraVox/en/about'       => $this->node('/reader/files/IntraVox/en/about', 1),
            'IntraVox/en/departments' => new NotFoundException('acl denied'), // the deny
            'IntraVox/en/news'        => $this->node('/reader/files/IntraVox/en/news', 1),
        ]);

        $items = [
            ['uniqueId' => 'p-about', 'title' => 'About'],
            ['uniqueId' => 'p-dept', 'title' => 'Departments'],
            ['uniqueId' => 'p-news', 'title' => 'News'],
        ];
        $map = ['p-about' => 'en/about', 'p-dept' => 'en/departments', 'p-news' => 'en/news'];

        $result = $svc->filterNavigation($items, 'en', $map);

        $this->assertSame(
            ['p-about', 'p-news'],
            array_column($result, 'uniqueId'),
            'Only readable pages survive; the ACL-denied one is filtered out'
        );
    }

    public function testAnItemWithNoMappedPathIsKept(): void {
        // Orphaned item (uniqueId not in the map): included, page load handles it.
        // No node lookup happens, so the view is never consulted for it.
        $svc = $this->service('reader', []);

        $result = $svc->filterNavigation(
            [['uniqueId' => 'p-orphan', 'title' => 'Orphan']],
            'en',
            [] // empty map
        );

        $this->assertCount(1, $result);
        $this->assertSame('p-orphan', $result[0]['uniqueId']);
    }

    public function testExternalUrlItemIsKeptWithoutAPermissionCheck(): void {
        // An item with a url but no uniqueId is always included; no view lookup.
        $svc = $this->service('reader', []);

        $result = $svc->filterNavigation(
            [['title' => 'Docs', 'url' => 'https://example.com']],
            'en',
            []
        );

        $this->assertCount(1, $result);
        $this->assertSame('Docs', $result[0]['title']);
    }
}
