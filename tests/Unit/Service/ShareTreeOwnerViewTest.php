<?php
declare(strict_types=1);

namespace OCA\IntraVox\Tests\Unit\Service;

use OCA\IntraVox\Service\LanguageService;
use OCA\IntraVox\Service\SetupService;
use OCA\IntraVox\Service\SystemFileService;
use OCP\Files\File;
use OCP\Files\FileInfo;
use OCP\Files\Folder;
use OCP\Files\IRootFolder;
use OCP\ICacheFactory;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * IV-02: getPageTreeForShareNode() walks the share OWNER's node, so a subtree a
 * GroupFolders ACL hides from the sharer (absent from getDirectoryListing()) is
 * absent from the shared tree — instead of being republished from the admin view.
 */
class ShareTreeOwnerViewTest extends TestCase {
    private function service(): SystemFileService {
        $language = $this->createMock(LanguageService::class);
        $language->method('isLanguageEnabled')->willReturn(true);

        $cacheFactory = $this->createMock(ICacheFactory::class);
        $cacheFactory->method('isAvailable')->willReturn(false);

        return new SystemFileService(
            $this->createMock(IRootFolder::class),
            $this->createMock(SetupService::class),
            $this->createMock(LoggerInterface::class),
            $language,
            $cacheFactory,
        );
    }

    /** A page folder <name>/ containing <name>.json with the given uniqueId/title. */
    private function pageFolder(string $name, string $path, string $uniqueId, string $title, array $children = []): Folder {
        $json = $this->createMock(File::class);
        $json->method('getContent')->willReturn(json_encode([
            'uniqueId' => $uniqueId,
            'title' => $title,
            'status' => 'published',
        ]));
        $json->method('getId')->willReturn(crc32($uniqueId));

        $folder = $this->createMock(Folder::class);
        $folder->method('getType')->willReturn(FileInfo::TYPE_FOLDER);
        $folder->method('getName')->willReturn($name);
        $folder->method('getPath')->willReturn($path);
        $folder->method('get')->willReturnCallback(function ($p) use ($name, $json) {
            if ($p === $name . '.json') {
                return $json;
            }
            throw new \OCP\Files\NotFoundException($p);
        });
        $folder->method('getDirectoryListing')->willReturn(array_merge([$json], $children));
        return $folder;
    }

    public function testAclDeniedSubtreeIsAbsentFromTheSharedTree(): void {
        // Share node: /IntraVox/nl/afdeling. The owner can see 'hr' but a
        // GroupFolders ACL hides 'directie', so getDirectoryListing() on the
        // share node returns only 'hr' — exactly what NC serves the owner.
        $base = '/admin/files/IntraVox';
        $hr = $this->pageFolder('hr', $base . '/nl/afdeling/hr', 'page-hr', 'HR');

        $shareNode = $this->createMock(Folder::class);
        $shareNode->method('getType')->willReturn(FileInfo::TYPE_FOLDER);
        $shareNode->method('getName')->willReturn('afdeling');
        $shareNode->method('getPath')->willReturn($base . '/nl/afdeling');
        // 'directie' is intentionally NOT listed — the ACL hid it from the owner.
        $shareNode->method('getDirectoryListing')->willReturn([$hr]);

        $tree = $this->service()->getPageTreeForShareNode($shareNode, 'nl');

        $ids = array_column($tree, 'uniqueId');
        $this->assertContains('page-hr', $ids, 'a readable child must appear');
        $this->assertNotContains('page-directie', $ids, 'an ACL-hidden child must NOT appear');
        // Paths are relative to the groupfolder root, like the system tree.
        $this->assertSame('nl/afdeling/hr', $tree[0]['path']);
    }

    /**
     * IV-02b: getNewsPagesForShare() must traverse the share OWNER's view when an
     * ownerId is given, so a news page in an ACL-hidden subtree is never listed —
     * the same guarantee the tree route already had.
     */
    public function testNewsForShareUsesOwnerViewAndHidesAclDeniedPages(): void {
        $base = '/owner/files/IntraVox';

        // Owner can see a 'nieuws' hub with one visible article; the ACL-hidden
        // 'sales' article is simply not in the owner's directory listing.
        $article = $this->newsPageFolder('visible', $base . '/nl/nieuws/visible', 'page-visible', 'Visible news');
        $nieuws = $this->pageFolder('nieuws', $base . '/nl/nieuws', 'page-nieuws', 'Nieuws', [$article]);

        $langFolder = $this->createMock(Folder::class);
        $langFolder->method('getType')->willReturn(FileInfo::TYPE_FOLDER);
        $langFolder->method('getName')->willReturn('nl');
        $langFolder->method('getPath')->willReturn($base . '/nl');
        $langFolder->method('getDirectoryListing')->willReturn([$nieuws]);

        $ownerIntraVox = $this->createMock(Folder::class);
        $ownerIntraVox->method('getPath')->willReturn($base);
        $ownerIntraVox->method('nodeExists')->willReturnCallback(fn ($n) => $n === 'nl');
        $ownerIntraVox->method('get')->willReturnCallback(function ($p) use ($langFolder) {
            if ($p === 'nl') {
                return $langFolder;
            }
            throw new \OCP\Files\NotFoundException($p);
        });

        $userFolder = $this->createMock(Folder::class);
        $userFolder->method('get')->willReturnCallback(function ($p) use ($ownerIntraVox) {
            if ($p === 'IntraVox') {
                return $ownerIntraVox;
            }
            throw new \OCP\Files\NotFoundException($p);
        });

        $rootFolder = $this->createMock(IRootFolder::class);
        $rootFolder->method('getUserFolder')->with('owner')->willReturn($userFolder);

        $setup = $this->createMock(SetupService::class);
        // If the code fell back to the system view, getSharedFolder() would be used;
        // fail the test if it is, since the owner view should win.
        $setup->expects($this->never())->method('getSharedFolder');

        $language = $this->createMock(LanguageService::class);
        $language->method('isLanguageEnabled')->willReturn(true);
        $cacheFactory = $this->createMock(ICacheFactory::class);
        $cacheFactory->method('isAvailable')->willReturn(false);

        $svc = new SystemFileService($rootFolder, $setup, $this->createMock(LoggerInterface::class), $language, $cacheFactory);

        $result = $svc->getNewsPagesForShare('nl', null, null, 'nl/nieuws', 'tok', 5, 'modified', 'desc', 'owner');
        $titles = array_column($result['items'], 'title');
        $this->assertContains('Visible news', $titles, 'a readable news page must be listed');
        $this->assertNotContains('Sales', $titles, 'an ACL-hidden news page must never be listed');
    }

    /** Like pageFolder but tagged as a news page so it is collected by the news walk. */
    private function newsPageFolder(string $name, string $path, string $uniqueId, string $title): Folder {
        $json = $this->createMock(File::class);
        $json->method('getContent')->willReturn(json_encode([
            'uniqueId' => $uniqueId,
            'title' => $title,
            'status' => 'published',
            'isNews' => true,
        ]));
        $json->method('getId')->willReturn(crc32($uniqueId));
        $folder = $this->createMock(Folder::class);
        $folder->method('getType')->willReturn(FileInfo::TYPE_FOLDER);
        $folder->method('getName')->willReturn($name);
        $folder->method('getPath')->willReturn($path);
        $folder->method('get')->willReturnCallback(function ($p) use ($name, $json) {
            if ($p === $name . '.json') {
                return $json;
            }
            throw new \OCP\Files\NotFoundException($p);
        });
        $folder->method('getDirectoryListing')->willReturn([$json]);
        return $folder;
    }

    public function testNestedChildrenAreWalked(): void {
        $base = '/admin/files/IntraVox';
        $leaf = $this->pageFolder('sub', $base . '/nl/afdeling/hr/sub', 'page-sub', 'Sub');
        $hr = $this->pageFolder('hr', $base . '/nl/afdeling/hr', 'page-hr', 'HR', [$leaf]);

        $shareNode = $this->createMock(Folder::class);
        $shareNode->method('getType')->willReturn(FileInfo::TYPE_FOLDER);
        $shareNode->method('getName')->willReturn('afdeling');
        $shareNode->method('getPath')->willReturn($base . '/nl/afdeling');
        $shareNode->method('getDirectoryListing')->willReturn([$hr]);

        $tree = $this->service()->getPageTreeForShareNode($shareNode, 'nl');

        $this->assertSame('page-hr', $tree[0]['uniqueId']);
        $this->assertSame('page-sub', $tree[0]['children'][0]['uniqueId']);
    }
}
