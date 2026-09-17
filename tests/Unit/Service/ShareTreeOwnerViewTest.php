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
