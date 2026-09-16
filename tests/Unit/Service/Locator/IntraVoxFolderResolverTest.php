<?php
declare(strict_types=1);

namespace OCA\IntraVox\Tests\Unit\Service\Locator;

use OCA\IntraVox\Service\Locator\IntraVoxFolderResolver;
use OCP\Files\Folder;
use OCP\Files\IRootFolder;
use PHPUnit\Framework\TestCase;

/**
 * Pins IntraVoxFolderResolver, the shared home (Phase 7) for the byte-identical
 * getIntraVoxFolder() that FooterService/HomepageService/NavigationService each
 * carried: no user -> throw; otherwise return the user folder's 'IntraVox' child,
 * resolved through the user's view so GroupFolder ACLs apply.
 */
class IntraVoxFolderResolverTest extends TestCase {

    public function testThrowsWhenNoUser(): void {
        $resolver = new IntraVoxFolderResolver($this->createMock(IRootFolder::class), null);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('User not logged in');
        $resolver->resolve();
    }

    public function testThrowsForEmptyUserId(): void {
        $resolver = new IntraVoxFolderResolver($this->createMock(IRootFolder::class), '');

        $this->expectException(\Exception::class);
        $resolver->resolve();
    }

    public function testResolvesIntraVoxThroughTheUsersFolder(): void {
        $intraVox = $this->createMock(Folder::class);
        $userFolder = $this->createMock(Folder::class);
        $userFolder->expects($this->once())->method('get')->with('IntraVox')->willReturn($intraVox);

        $root = $this->createMock(IRootFolder::class);
        $root->expects($this->once())->method('getUserFolder')->with('alice')->willReturn($userFolder);

        $resolver = new IntraVoxFolderResolver($root, 'alice');

        $this->assertSame($intraVox, $resolver->resolve());
    }
}
