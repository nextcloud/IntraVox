<?php
declare(strict_types=1);

namespace OCA\IntraVox\Tests\Unit\Service;

use PHPUnit\Framework\TestCase;

/**
 * The page listing has one order, and it is total.
 *
 * It had none. The indexed branch returned whatever the database handed back —
 * listPagesFromIndex() has no ORDER BY — and the fallback branch whatever the
 * filesystem walk produced. Which branch ran depended on whether the index
 * happened to cover the language, so one instance could answer the same request
 * two different ways.
 *
 * This is a prerequisite, not tidiness. Cursor pagination is only correct over a
 * total order: without one the same cursor skips some rows and repeats others
 * between requests, silently. plan-multisite-uitvoering.md §4.15 has already
 * settled on keyset paging over (slug, id) and "never OFFSET", so the order has
 * to exist before D2 can be built at all.
 *
 * Safe to add, checked before doing it: nothing in src/ sorts this list, every use
 * in App.vue but one is a find()/some() lookup where order cannot matter, and
 * NavigationEditor takes a :pages prop it never reads. The one exception is the
 * pages[0] fallback after a delete — and that was picking an arbitrary page
 * before, so it becomes more predictable rather than less.
 */
class PageListingOrderTest extends TestCase {
    private \ReflectionMethod $sort;
    private \OCA\IntraVox\Service\Listing\PageLister $service;

    protected function setUp(): void {
        parent::setUp();
        // inStableOrder() lives on PageLister now (LISTING carve); it is a pure sort
        // over the array it is handed, so a constructor-less instance suffices.
        $class = new \ReflectionClass(\OCA\IntraVox\Service\Listing\PageLister::class);
        $this->service = $class->newInstanceWithoutConstructor();
        $this->sort = $class->getMethod('inStableOrder');
    }

    /** @param list<array<string,mixed>> $pages */
    private function order(array $pages): array {
        $sorted = $this->sort->invoke($this->service, $pages);

        return array_map(static fn (array $p): string => $p['uniqueId'], $sorted);
    }

    public function testPagesAreOrderedByTitle(): void {
        $out = $this->order([
            ['title' => 'Verlof', 'uniqueId' => 'page-c'],
            ['title' => 'Beleid', 'uniqueId' => 'page-a'],
            ['title' => 'Afdelingen', 'uniqueId' => 'page-b'],
        ]);

        $this->assertSame(['page-b', 'page-a', 'page-c'], $out);
    }

    public function testTheOrderDoesNotDependOnTheInputOrder(): void {
        $pages = [
            ['title' => 'B', 'uniqueId' => 'page-2'],
            ['title' => 'A', 'uniqueId' => 'page-1'],
            ['title' => 'C', 'uniqueId' => 'page-3'],
        ];

        $forward = $this->order($pages);
        $backward = $this->order(array_reverse($pages));
        shuffle($pages);
        $shuffled = $this->order($pages);

        $this->assertSame($forward, $backward, 'Two branches returning different input orders must agree');
        $this->assertSame($forward, $shuffled);
    }

    /**
     * The tie-breaker earns its place: titles are not unique — two departments
     * can both call a page "Contact" — and a sort whose final key repeats is not
     * a total order, which is exactly what a cursor cannot survive.
     */
    public function testPagesSharingATitleAreOrderedByUniqueId(): void {
        $out = $this->order([
            ['title' => 'Contact', 'uniqueId' => 'page-z'],
            ['title' => 'Contact', 'uniqueId' => 'page-a'],
        ]);

        $this->assertSame(['page-a', 'page-z'], $out);
    }

    public function testMissingKeysDoNotBlowUp(): void {
        $out = $this->sort->invoke($this->service, [
            ['uniqueId' => 'page-b'],
            ['title' => 'A', 'uniqueId' => 'page-a'],
            [],
        ]);

        $this->assertCount(3, $out, 'A malformed row must not take the listing down');
    }

    public function testAnEmptyListingStaysEmpty(): void {
        $this->assertSame([], $this->sort->invoke($this->service, []));
    }
}
