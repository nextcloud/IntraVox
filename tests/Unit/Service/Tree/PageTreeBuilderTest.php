<?php
declare(strict_types=1);

namespace OCA\IntraVox\Tests\Unit\Service\Tree;

use OCA\IntraVox\Service\Locator\PageLocator;
use OCA\IntraVox\Service\PageIndexService;
use OCA\IntraVox\Service\PermissionService;
use OCA\IntraVox\Service\Tree\PageTreeBuilder;
use OCP\Files\File;
use OCP\Files\FileInfo;
use OCP\Files\Folder;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * The recursive tree walk in isolation, after extraction from
 * PageService::buildPageTree(). Mirrors PageTreePlaceholderTest's fixture and
 * assertions but drives PageTreeBuilder::build() DIRECTLY with a real
 * PageLocator (cache passthrough), a bare-subclass PermissionService, and the
 * two seam closures — so the walk is proven byte-equivalent at the class level.
 * PageTreePlaceholderTest continues to prove the same through the PageService
 * delegator.
 */
class PageTreeBuilderTest extends TestCase {

    private function makeFile(string $path, array $json): File {
        $file = $this->createMock(File::class);
        $file->method('getName')->willReturn(basename($path));
        $file->method('getType')->willReturn(FileInfo::TYPE_FILE);
        $file->method('getPath')->willReturn($path);
        $file->method('getId')->willReturn(abs(crc32($path)));
        $file->method('isReadable')->willReturn(true);
        $file->method('getContent')->willReturn(json_encode($json));
        return $file;
    }

    /** @param array<string,\OCP\Files\Node> $children */
    private function makeFolder(string $path, array $children): Folder {
        $folder = $this->createMock(Folder::class);
        $folder->method('getName')->willReturn(basename($path));
        $folder->method('getType')->willReturn(FileInfo::TYPE_FOLDER);
        $folder->method('getPath')->willReturn($path);
        $folder->method('getDirectoryListing')->willReturn(array_values($children));
        $folder->method('nodeExists')->willReturnCallback(fn($n) => isset($children[$n]));
        $folder->method('get')->willReturnCallback(function ($p) use ($children, $path) {
            if (isset($children[$p])) {
                return $children[$p];
            }
            throw new \OCP\Files\NotFoundException($path . '/' . $p);
        });
        // permissionsFromNode reads these.
        $folder->method('isReadable')->willReturn(true);
        $folder->method('isUpdateable')->willReturn(false);
        $folder->method('isCreatable')->willReturn(false);
        $folder->method('isDeletable')->willReturn(false);
        $folder->method('getPermissions')->willReturn(1); // PERMISSION_READ bit
        return $folder;
    }

    private function makeBuilder(): PageTreeBuilder {
        $locator = new PageLocator(
            $this->createMock(PageIndexService::class),
            $this->createMock(LoggerInterface::class),
        );
        $permissionService = new class extends PermissionService {
            public function __construct() {
            }
        };
        // The seam closures: relativePathFromRoot mirrors the folder's IntraVox-
        // relative path (strip the leading "/IntraVox/"); userLanguage is only the
        // fallback, unused here because every node carries an explicit language.
        $relativePath = fn(Folder $item): string => ltrim(
            substr($item->getPath(), strlen('/IntraVox/')),
            '/'
        );
        $userLanguage = fn(): string => 'de';

        return new PageTreeBuilder($locator, $permissionService, $relativePath, $userLanguage);
    }

    /**
     * de/afdelingen/hr/vacatures is a real page; afdelingen and hr are bare
     * folders (the shape createTranslation leaves behind). de/leeg is a bare
     * folder with nothing below. de/_templates holds page-shaped JSON that must
     * never surface.
     */
    private function deFolder(): Folder {
        $vacFolder = $this->makeFolder('/IntraVox/de/afdelingen/hr/vacatures', [
            'vacatures.json' => $this->makeFile(
                '/IntraVox/de/afdelingen/hr/vacatures/vacatures.json',
                ['uniqueId' => 'page-vac', 'title' => 'Stellen', 'status' => 'published']
            ),
        ]);
        $hr = $this->makeFolder('/IntraVox/de/afdelingen/hr', ['vacatures' => $vacFolder]);
        $afdelingen = $this->makeFolder('/IntraVox/de/afdelingen', ['hr' => $hr]);
        $leeg = $this->makeFolder('/IntraVox/de/leeg', []);
        $tplPage = $this->makeFolder('/IntraVox/de/_templates/sjabloon', [
            'sjabloon.json' => $this->makeFile(
                '/IntraVox/de/_templates/sjabloon/sjabloon.json',
                ['uniqueId' => 'page-tpl', 'title' => 'Sjabloon', 'status' => 'published']
            ),
        ]);
        $templates = $this->makeFolder('/IntraVox/de/_templates', ['sjabloon' => $tplPage]);
        return $this->makeFolder('/IntraVox/de', [
            'afdelingen' => $afdelingen,
            'leeg' => $leeg,
            '_templates' => $templates,
        ]);
    }

    private function buildTree(Folder $folder): array {
        $tree = [];
        $this->makeBuilder()->build($folder, $tree, null, 'de');
        return $tree;
    }

    public function testBareAncestorsRenderAsPlaceholdersDownToTheRealPage(): void {
        $tree = $this->buildTree($this->deFolder());

        // Only 'afdelingen' at the root: 'leeg' has nothing below and
        // '_templates' is infrastructure.
        $this->assertCount(1, $tree);

        $afd = $tree[0];
        $this->assertTrue($afd['isPlaceholder']);
        $this->assertSame('folder:de/afdelingen', $afd['uniqueId']);
        $this->assertSame('Afdelingen', $afd['title'], 'label derives from the folder name');

        $hr = $afd['children'][0];
        $this->assertTrue($hr['isPlaceholder']);

        $vac = $hr['children'][0];
        $this->assertArrayNotHasKey('isPlaceholder', $vac, 'the real page is a normal node');
        $this->assertSame('page-vac', $vac['uniqueId']);
        $this->assertSame('Stellen', $vac['title']);
    }

    public function testEmptyBareFolderAndTemplatesStayInvisible(): void {
        $tree = $this->buildTree($this->deFolder());

        $flatIds = [];
        $walk = function (array $nodes) use (&$walk, &$flatIds) {
            foreach ($nodes as $n) {
                $flatIds[] = $n['uniqueId'];
                $walk($n['children']);
            }
        };
        $walk($tree);

        $this->assertNotContains('folder:de/leeg', $flatIds, 'a bare folder with nothing below renders nothing');
        $this->assertNotContains('page-tpl', $flatIds, 'template pages must never surface in the tree');
    }

    public function testCurrentPageIsMarkedDuringTheWalk(): void {
        $tree = [];
        $this->makeBuilder()->build($this->deFolder(), $tree, 'page-vac', 'de');

        $vac = $tree[0]['children'][0]['children'][0];
        $this->assertTrue($vac['isCurrent'], 'the walk marks the node whose uniqueId matches currentPageId');
    }

    public function testSiblingsAreOrderedByTheStableOrderComparator(): void {
        // Three real pages under a root, given out-of-order 'order' fields; the
        // #69 comparator must sort ascending and strip the internal 'order' key.
        $mk = function (string $slug, string $uid, int $order): Folder {
            return $this->makeFolder('/IntraVox/de/' . $slug, [
                $slug . '.json' => $this->makeFile(
                    '/IntraVox/de/' . $slug . '/' . $slug . '.json',
                    ['uniqueId' => $uid, 'title' => ucfirst($slug), 'order' => $order]
                ),
            ]);
        };
        $root = $this->makeFolder('/IntraVox/de', [
            'gamma' => $mk('gamma', 'page-g', 2),
            'alpha' => $mk('alpha', 'page-a', 0),
            'beta'  => $mk('beta', 'page-b', 1),
        ]);

        $tree = $this->buildTree($root);

        $this->assertSame(['page-a', 'page-b', 'page-g'], array_column($tree, 'uniqueId'));
        $this->assertArrayNotHasKey('order', $tree[0], 'the internal order key is stripped from the output');
    }

    public function testUnorderedSiblingsKeepDirectoryOrderAfterOrderedOnes(): void {
        // Ordered nodes come first (ascending); unordered nodes keep their
        // original relative position, after the ordered ones.
        $mkOrdered = function (string $slug, string $uid, int $order): Folder {
            return $this->makeFolder('/IntraVox/de/' . $slug, [
                $slug . '.json' => $this->makeFile(
                    '/IntraVox/de/' . $slug . '/' . $slug . '.json',
                    ['uniqueId' => $uid, 'title' => ucfirst($slug), 'order' => $order]
                ),
            ]);
        };
        $mkPlain = function (string $slug, string $uid): Folder {
            return $this->makeFolder('/IntraVox/de/' . $slug, [
                $slug . '.json' => $this->makeFile(
                    '/IntraVox/de/' . $slug . '/' . $slug . '.json',
                    ['uniqueId' => $uid, 'title' => ucfirst($slug)]
                ),
            ]);
        };
        $root = $this->makeFolder('/IntraVox/de', [
            'plain1'  => $mkPlain('plain1', 'page-p1'),
            'ordered' => $mkOrdered('ordered', 'page-o', 5),
            'plain2'  => $mkPlain('plain2', 'page-p2'),
        ]);

        $tree = $this->buildTree($root);

        $this->assertSame(['page-o', 'page-p1', 'page-p2'], array_column($tree, 'uniqueId'));
    }
}
