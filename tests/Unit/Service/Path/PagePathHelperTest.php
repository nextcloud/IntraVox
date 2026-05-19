<?php
declare(strict_types=1);

namespace OCA\IntraVox\Tests\Unit\Service\Path;

use OCA\IntraVox\Service\Path\PagePathHelper;
use PHPUnit\Framework\TestCase;

class PagePathHelperTest extends TestCase {
    private PagePathHelper $helper;

    protected function setUp(): void {
        parent::setUp();
        $this->helper = new PagePathHelper();
    }

    // ---------- calculateDepth ----------

    public function testDepthForEmptyPath(): void {
        $this->assertSame(0, $this->helper->calculateDepth(''));
    }

    public function testDepthForLanguageRootOnly(): void {
        $this->assertSame(0, $this->helper->calculateDepth('nl'));
    }

    public function testDepthForLegacyTopLevelPage(): void {
        $this->assertSame(0, $this->helper->calculateDepth('nl/about'));
    }

    public function testDepthForPublicPage(): void {
        // nl/public/home → count(2) - 1 = 1
        $this->assertSame(1, $this->helper->calculateDepth('nl/public/home'));
        // nl/public/home/contact → count(3) - 1 = 2
        $this->assertSame(2, $this->helper->calculateDepth('nl/public/home/contact'));
    }

    public function testDepthForDepartmentPage(): void {
        // nl/departments/marketing → depth 0 (department root)
        $this->assertSame(0, $this->helper->calculateDepth('nl/departments/marketing'));
        // nl/departments/marketing/campaigns → depth 1
        $this->assertSame(1, $this->helper->calculateDepth('nl/departments/marketing/campaigns'));
        // nl/departments/marketing/campaigns/2024 → depth 2
        $this->assertSame(2, $this->helper->calculateDepth('nl/departments/marketing/campaigns/2024'));
    }

    public function testDepthIgnoresLeadingTrailingSlashes(): void {
        // /nl/public/home/contact/ → count(3) - 1 = 2 (consistent with no-slash form)
        $this->assertSame(
            2,
            $this->helper->calculateDepth('/nl/public/home/contact/')
        );
    }

    // ---------- determinePageType ----------

    public function testDepartmentRootIsDepartment(): void {
        $this->assertSame('department', $this->helper->determinePageType('nl/departments/marketing', false));
    }

    public function testDepartmentSubpageIsNotDepartment(): void {
        $this->assertSame('page', $this->helper->determinePageType('nl/departments/marketing/campaigns', false));
        $this->assertSame('container', $this->helper->determinePageType('nl/departments/marketing/campaigns', true));
    }

    public function testNodeWithChildrenIsContainer(): void {
        $this->assertSame('container', $this->helper->determinePageType('nl/public/home', true));
    }

    public function testLeafIsPage(): void {
        $this->assertSame('page', $this->helper->determinePageType('nl/public/home/contact', false));
    }

    // ---------- parseDepartmentFromPath ----------

    public function testDepartmentForDepartmentRoot(): void {
        $this->assertSame('marketing', $this->helper->parseDepartmentFromPath('nl/departments/marketing'));
    }

    public function testDepartmentForDepartmentSubpage(): void {
        $this->assertSame('hr', $this->helper->parseDepartmentFromPath('en/departments/hr/policies'));
    }

    public function testNoDepartmentForPublicPath(): void {
        $this->assertNull($this->helper->parseDepartmentFromPath('nl/public/home'));
    }

    public function testNoDepartmentForLegacyPath(): void {
        $this->assertNull($this->helper->parseDepartmentFromPath('nl/about'));
    }

    // ---------- markCurrentPageInTree ----------

    public function testMarkCurrentNullReturnsTreeUnchanged(): void {
        $tree = [['uniqueId' => 'p1', 'children' => []]];
        $this->assertSame($tree, $this->helper->markCurrentPageInTree($tree, null));
    }

    public function testMarkCurrentFlagsMatchingTopLevelNode(): void {
        $tree = [
            ['uniqueId' => 'p1', 'children' => []],
            ['uniqueId' => 'p2', 'children' => []],
        ];

        $result = $this->helper->markCurrentPageInTree($tree, 'p2');

        $this->assertFalse($result[0]['isCurrent']);
        $this->assertTrue($result[1]['isCurrent']);
    }

    public function testMarkCurrentRecursesIntoChildren(): void {
        $tree = [
            [
                'uniqueId' => 'p1',
                'children' => [
                    ['uniqueId' => 'p1a', 'children' => []],
                    ['uniqueId' => 'p1b', 'children' => []],
                ],
            ],
        ];

        $result = $this->helper->markCurrentPageInTree($tree, 'p1b');

        $this->assertFalse($result[0]['isCurrent']);
        $this->assertFalse($result[0]['children'][0]['isCurrent']);
        $this->assertTrue($result[0]['children'][1]['isCurrent']);
    }

    public function testMarkCurrentReturnsCopyNotMutation(): void {
        $tree = [['uniqueId' => 'p1', 'children' => []]];
        $this->helper->markCurrentPageInTree($tree, 'p1');
        $this->assertArrayNotHasKey('isCurrent', $tree[0]);
    }
}
