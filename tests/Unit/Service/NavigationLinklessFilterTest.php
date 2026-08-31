<?php
declare(strict_types=1);

namespace OCA\IntraVox\Tests\Unit\Service;

use OCA\IntraVox\Service\PermissionService;
use PHPUnit\Framework\TestCase;

/**
 * filterNavigation() drops a navigation item that has no link and no visible
 * children -- and must NOT do so for the editor.
 *
 * The menu rule is deliberate: a heading that goes nowhere and holds nothing is
 * noise for a visitor. Feeding that same filtered copy back into the navigation
 * editor is what made issue #104: an item is created and saved before its page
 * link is set, so on the next load it was gone from the editor too, and with it
 * every way to add the link. The file kept it -- it was simply unreachable.
 *
 * These lock both halves down, because fixing one by breaking the other is the
 * obvious wrong move: the menu must keep hiding them.
 */
class NavigationLinklessFilterTest extends TestCase {
    private function service(): PermissionService {
        // filterNavigation() only walks the array when no pagePathMap is given:
        // an item without uniqueId never reaches a permission lookup.
        return (new \ReflectionClass(PermissionService::class))->newInstanceWithoutConstructor();
    }

    /** An item as saveNavigation() stores it: children always present, as []. */
    private function linkless(string $title, array $children = []): array {
        return [
            'id' => 'nav_' . $title,
            'title' => $title,
            'uniqueId' => null,
            'url' => null,
            'target' => null,
            'children' => $children,
        ];
    }

    public function testTheMenuStillHidesAnItemWithoutLinkOrChildren(): void {
        $filtered = $this->service()->filterNavigation([$this->linkless('test')], 'nl');

        $this->assertSame([], $filtered, 'a linkless, childless item has no place in the visitor menu');
    }

    public function testTheEditorKeepsThatSameItem(): void {
        $filtered = $this->service()->filterNavigation([$this->linkless('test')], 'nl', null, true);

        $this->assertCount(1, $filtered, 'issue #104: dropping it here makes the link impossible to add');
        $this->assertSame('test', $filtered[0]['title']);
    }

    /**
     * The rule was never about the link alone: an item without a link that still
     * has a reachable child is a section heading and belongs in the menu.
     */
    public function testAnItemWithoutLinkSurvivesWhenAChildIsVisible(): void {
        $item = $this->linkless('section', [[
            'id' => 'nav_child',
            'title' => 'child',
            'uniqueId' => null,
            'url' => 'https://example.com',
            'target' => null,
            'children' => [],
        ]]);

        $filtered = $this->service()->filterNavigation([$item], 'nl');

        $this->assertCount(1, $filtered);
        $this->assertCount(1, $filtered[0]['children']);
    }

    /** The editor flag has to reach nested levels, not just the top one. */
    public function testTheEditorKeepsALinklessItemNestedUnderAParent(): void {
        $parent = $this->linkless('parent', [$this->linkless('empty child')]);

        $menu = $this->service()->filterNavigation([$parent], 'nl');
        $editor = $this->service()->filterNavigation([$parent], 'nl', null, true);

        $this->assertSame([], $menu, 'parent and child are both empty, so the menu shows neither');
        $this->assertCount(1, $editor);
        $this->assertCount(1, $editor[0]['children'], 'the child must stay editable too');
    }
}
