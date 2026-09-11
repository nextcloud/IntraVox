<?php
declare(strict_types=1);

namespace OCA\IntraVox\Tests\Unit\Service;

use OCA\IntraVox\Service\PageService;
use OCA\IntraVox\Service\PermissionService;
use OCP\Files\File;
use OCP\Files\FileInfo;
use OCP\Files\Folder;
use PHPUnit\Framework\TestCase;

/**
 * The tree and bare folders — the ghost-page problem.
 *
 * Translating a deep page before its ancestors mirrors the source path and
 * creates the missing levels as BARE folders (no page JSON). The tree used to
 * skip any folder without a page, which made every page underneath unreachable
 * by browsing while search, breadcrumb and direct links all worked.
 *
 * The rule under test: a bare folder WITH pages below it renders as a
 * non-navigable placeholder node ('folder:…' id, isPlaceholder), a bare folder
 * with nothing below renders nothing, and infrastructure folders (_templates!)
 * are never entered at all.
 */
class PageTreePlaceholderTest extends TestCase {

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
        $folder->method('getPermissions')->willReturn(1); // PERMISSION_READ bit, as permissionsFromNode reads it
        return $folder;
    }

    /**
     * de/afdelingen/hr/vacatures is a real page; afdelingen and hr are bare
     * folders (the shape createTranslation leaves behind). de/leeg is a bare
     * folder with nothing below. de/_templates holds page-shaped JSON that
     * must never surface.
     */
    private function buildTreeFor(): array {
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
        $de = $this->makeFolder('/IntraVox/de', [
            'afdelingen' => $afdelingen,
            'leeg' => $leeg,
            '_templates' => $templates,
        ]);

        $base = $this->makeFolder('/IntraVox', ['de' => $de]);
        // The recursive walk lives in PageTreeBuilder (fase-4 capstone moved
        // getPageTree to Tree/PageTreeService; the by-ref buildPageTree wrapper is
        // retired). Drive the builder directly — the real owner of this behaviour.
        $locator = new \OCA\IntraVox\Service\Locator\PageLocator(
            $this->createMock(\OCA\IntraVox\Service\PageIndexService::class),
            $this->createMock(\Psr\Log\LoggerInterface::class)
        );
        $folders = new \OCA\IntraVox\Service\Folder\FolderContext(
            $this->createMock(\OCP\Files\IRootFolder::class),
            'tester',
            $this->createMock(\OCP\IConfig::class),
            $this->createMock(\OCA\IntraVox\Service\LanguageService::class),
            new \OCA\IntraVox\Service\Language\LanguageResolver(),
            $locator,
            $base // intraVoxOverride
        );
        $builder = new \OCA\IntraVox\Service\Tree\PageTreeBuilder(
            $locator,
            new class extends PermissionService {
                public function __construct() {
                }
            },
            $folders
        );

        $tree = [];
        $builder->build($de, $tree, null, 'de');
        return $tree;
    }

    public function testBareAncestorsRenderAsPlaceholdersDownToTheRealPage(): void {
        $tree = $this->buildTreeFor();

        // Only 'afdelingen' at the root: 'leeg' has nothing below and
        // '_templates' is infrastructure.
        $this->assertCount(1, $tree);

        $afd = $tree[0];
        $this->assertTrue($afd['isPlaceholder']);
        $this->assertSame('folder:de/afdelingen', $afd['uniqueId']);
        $this->assertSame('Afdelingen', $afd['title'], 'label derives from the folder name, like the breadcrumb');

        $hr = $afd['children'][0];
        $this->assertTrue($hr['isPlaceholder']);

        $vac = $hr['children'][0];
        $this->assertArrayNotHasKey('isPlaceholder', $vac, 'the real page is a normal node');
        $this->assertSame('page-vac', $vac['uniqueId']);
        $this->assertSame('Stellen', $vac['title']);
    }

    public function testEmptyBareFolderAndTemplatesStayInvisible(): void {
        $tree = $this->buildTreeFor();

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
}
