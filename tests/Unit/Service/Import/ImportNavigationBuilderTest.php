<?php
declare(strict_types=1);

namespace OCA\IntraVox\Tests\Unit\Service\Import;

use OCA\IntraVox\Service\Import\ImportNavigationBuilder;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Turning imported pages into a navigation menu.
 *
 * These were private helpers on ImportService, reachable only by importing a
 * real ZIP, and therefore untested — the whole Import/ tree had no coverage.
 * Extracting them made the algorithm testable on plain arrays.
 */
class ImportNavigationBuilderTest extends TestCase {
    private ImportNavigationBuilder $builder;

    protected function setUp(): void {
        parent::setUp();
        $this->builder = new ImportNavigationBuilder($this->createMock(LoggerInterface::class));
    }

    /** @return array<string,mixed> */
    private function page(string $id, ?string $parent = null, string $title = 'Titel'): array {
        return [
            'uniqueId' => 'page-' . $id,
            'parentUniqueId' => $parent === null ? null : 'page-' . $parent,
            'content' => ['title' => $title],
        ];
    }

    /** @param array<int,array<string,mixed>> $nodes */
    private function titles(array $nodes): array {
        return array_map(static fn (array $n) => $n['title'], $nodes);
    }

    public function testAFlatListBecomesRootItems(): void {
        $tree = $this->builder->buildPageTree([
            $this->page('aaaaaaaa', null, 'Een'),
            $this->page('bbbbbbbb', null, 'Twee'),
        ]);

        $this->assertSame(['Een', 'Twee'], $this->titles($tree));
    }

    public function testChildrenAreNestedUnderTheirParent(): void {
        $tree = $this->builder->buildPageTree([
            $this->page('aaaaaaaa', null, 'Ouder'),
            $this->page('bbbbbbbb', 'aaaaaaaa', 'Kind'),
            $this->page('cccccccc', 'bbbbbbbb', 'Kleinkind'),
        ]);

        $this->assertCount(1, $tree, 'only the root belongs at top level');
        $this->assertSame('Ouder', $tree[0]['title']);
        $this->assertSame(['Kind'], $this->titles($tree[0]['children']));
        $this->assertSame(['Kleinkind'], $this->titles($tree[0]['children'][0]['children']));
    }

    /**
     * The depth cap exists because navigation.json is rendered as a menu: a
     * space nested eight deep produces a submenu nobody can reach. The pages
     * themselves are still imported — only the menu entry is dropped.
     */
    public function testNodesBelowTheDepthCapGetNoMenuEntry(): void {
        $pages = [$this->page('aaaaaaaa', null, 'L0')];
        $prev = 'aaaaaaaa';
        foreach (['bbbbbbbb' => 'L1', 'cccccccc' => 'L2', 'dddddddd' => 'L3', 'eeeeeeee' => 'L4'] as $id => $title) {
            $pages[] = $this->page($id, $prev, $title);
            $prev = $id;
        }

        $shallow = $this->builder->buildPageTree($pages, 2);

        $level0 = $shallow[0];
        $this->assertSame('L0', $level0['title']);
        $this->assertSame(['L1'], $this->titles($level0['children']));
        $this->assertSame(['L2'], $this->titles($level0['children'][0]['children']));
        $this->assertSame([], $level0['children'][0]['children'][0]['children'], 'L3 is past the cap');
    }

    /** A page whose parent was not part of the import lands at root level. */
    public function testAnOrphanIsPromotedToRootRatherThanDropped(): void {
        $tree = $this->builder->buildPageTree([
            $this->page('bbbbbbbb', 'nietgeimporteerd', 'Wees'),
        ]);

        $this->assertSame(['Wees'], $this->titles($tree));
    }

    /** The internal bookkeeping keys must not reach navigation.json. */
    public function testTemporaryDepthFieldsAreStrippedFromTheResult(): void {
        $tree = $this->builder->buildPageTree([
            $this->page('aaaaaaaa', null, 'Ouder'),
            $this->page('bbbbbbbb', 'aaaaaaaa', 'Kind'),
        ]);

        $this->assertArrayNotHasKey('_depth', $tree[0]);
        $this->assertArrayNotHasKey('_parentId', $tree[0]);
        $this->assertArrayNotHasKey('_depth', $tree[0]['children'][0]);
        $this->assertArrayNotHasKey('_parentId', $tree[0]['children'][0]);
    }

    public function testEachNodeCarriesTheKeysNavigationExpects(): void {
        $tree = $this->builder->buildPageTree([$this->page('aaaaaaaa', null, 'Een')]);

        foreach (['id', 'title', 'uniqueId', 'url', 'target', 'children'] as $key) {
            $this->assertArrayHasKey($key, $tree[0], "navigation.json items carry '$key'");
        }
        $this->assertStringStartsWith('nav_', $tree[0]['id']);
    }

    public function testSortingPutsParentsBeforeTheirChildren(): void {
        $sorted = $this->builder->sortPagesByHierarchy([
            $this->page('cccccccc', 'bbbbbbbb', 'Kleinkind'),
            $this->page('aaaaaaaa', null, 'Ouder'),
            $this->page('bbbbbbbb', 'aaaaaaaa', 'Kind'),
        ]);

        $order = array_map(static fn (array $p) => $p['content']['title'], $sorted);
        $this->assertSame(['Ouder', 'Kind', 'Kleinkind'], $order);
    }

    public function testAnEmptyImportProducesAnEmptyTree(): void {
        $this->assertSame([], $this->builder->buildPageTree([]));
        $this->assertSame([], $this->builder->sortPagesByHierarchy([]));
    }

    public function testAnExistingMenuItemIsFoundByUniqueId(): void {
        $items = [
            ['id' => 'nav_1', 'uniqueId' => 'page-aaaaaaaa', 'children' => [
                ['id' => 'nav_2', 'uniqueId' => 'page-bbbbbbbb', 'children' => []],
            ]],
        ];

        $this->assertNotNull($this->builder->findNavigationItemPath($items, 'page-bbbbbbbb'));
        $this->assertNull($this->builder->findNavigationItemPath($items, 'page-bestaatniet'));
    }

    public function testPagesAreGraftedOntoAnExistingMenu(): void {
        $items = [['id' => 'nav_1', 'uniqueId' => 'page-bestaand', 'children' => []]];
        $new = $this->builder->buildPageTree([$this->page('aaaaaaaa', null, 'Nieuw')]);

        $this->builder->addPagesToNavigation($items, $new);

        $this->assertCount(2, $items, 'the existing item survives and the import is appended');
        $this->assertSame('Nieuw', $items[1]['title']);
    }
}
