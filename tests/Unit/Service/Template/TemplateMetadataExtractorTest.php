<?php
declare(strict_types=1);

namespace OCA\IntraVox\Tests\Unit\Service\Template;

use OCA\IntraVox\Service\Template\TemplateMetadataExtractor;
use PHPUnit\Framework\TestCase;

class TemplateMetadataExtractorTest extends TestCase {
    private TemplateMetadataExtractor $extractor;

    protected function setUp(): void {
        parent::setUp();
        $this->extractor = new TemplateMetadataExtractor();
    }

    public function testEmptyContentReturnsSensibleDefaults(): void {
        $output = $this->extractor->extract([]);

        $this->assertFalse($output['hasHeaderRow']);
        $this->assertSame(1, $output['columnCount']);
        $this->assertSame(0, $output['rowCount']);
        $this->assertSame([], $output['widgetTypes']);
        $this->assertSame(0, $output['widgetCount']);
        $this->assertSame('', $output['backgroundColor']);
        $this->assertFalse($output['hasSidebars']);
        $this->assertFalse($output['hasCollapsible']);
        $this->assertSame('simple', $output['complexity']);
    }

    public function testCountsRowsAndWidgets(): void {
        $content = [
            'layout' => [
                'rows' => [
                    ['widgets' => [['type' => 'text'], ['type' => 'image']]],
                    ['widgets' => [['type' => 'text']]],
                ],
            ],
        ];

        $output = $this->extractor->extract($content);

        $this->assertSame(2, $output['rowCount']);
        $this->assertSame(3, $output['widgetCount']);
        $this->assertSame(['text', 'image'], $output['widgetTypes']);
    }

    public function testTracksMaxColumnsAcrossRows(): void {
        $content = [
            'layout' => [
                'rows' => [
                    ['columns' => 1, 'widgets' => []],
                    ['columns' => 3, 'widgets' => []],
                    ['columns' => 2, 'widgets' => []],
                ],
            ],
        ];

        $this->assertSame(3, $this->extractor->extract($content)['columnCount']);
    }

    public function testCapturesFirstBackgroundColor(): void {
        $content = [
            'layout' => [
                'rows' => [
                    ['backgroundColor' => '', 'widgets' => []],
                    ['backgroundColor' => '#fff', 'widgets' => []],
                    ['backgroundColor' => '#000', 'widgets' => []],
                ],
            ],
        ];

        // The first row has an *empty* backgroundColor, so the second wins.
        $this->assertSame('#fff', $this->extractor->extract($content)['backgroundColor']);
    }

    public function testHeaderRowDetectedFromFirstRowBackground(): void {
        $content = [
            'layout' => [
                'rows' => [
                    ['backgroundColor' => '#abc', 'widgets' => []],
                ],
            ],
        ];

        $this->assertTrue($this->extractor->extract($content)['hasHeaderRow']);
    }

    public function testHeaderRowFalseWithoutBackground(): void {
        $content = [
            'layout' => [
                'rows' => [['widgets' => []]],
            ],
        ];

        $this->assertFalse($this->extractor->extract($content)['hasHeaderRow']);
    }

    public function testCollapsibleRowFlipsFlag(): void {
        $content = [
            'layout' => [
                'rows' => [['collapsible' => true, 'widgets' => []]],
            ],
        ];

        $this->assertTrue($this->extractor->extract($content)['hasCollapsible']);
    }

    public function testSidebarsAreCountedWhenEnabledAndHaveWidgets(): void {
        $content = [
            'layout' => [
                'sideColumns' => [
                    'left' => ['enabled' => true, 'widgets' => [['type' => 'links']]],
                    'right' => ['enabled' => false, 'widgets' => [['type' => 'unused']]],
                ],
            ],
        ];

        $output = $this->extractor->extract($content);
        $this->assertTrue($output['hasSidebars']);
        $this->assertContains('links', $output['widgetTypes']);
        // Right sidebar is disabled — its widgets must not be counted.
        $this->assertNotContains('unused', $output['widgetTypes']);
    }

    public function testWidgetTypesAreDeduplicated(): void {
        $content = [
            'layout' => [
                'rows' => [
                    ['widgets' => [['type' => 'text'], ['type' => 'text']]],
                ],
            ],
        ];

        $this->assertSame(['text'], $this->extractor->extract($content)['widgetTypes']);
        // Count is *not* deduplicated.
        $this->assertSame(2, $this->extractor->extract($content)['widgetCount']);
    }

    public function testComplexitySimpleForSmallPage(): void {
        $content = ['layout' => ['rows' => [['widgets' => [['type' => 'text']]]]]];
        $this->assertSame('simple', $this->extractor->extract($content)['complexity']);
    }

    public function testComplexityModerateForMidSizePage(): void {
        $widgets = array_fill(0, 6, ['type' => 'text']);
        $content = ['layout' => ['rows' => [['widgets' => $widgets]]]];
        $this->assertSame('moderate', $this->extractor->extract($content)['complexity']);
    }

    public function testComplexityComplexWhenMoreThanTenWidgets(): void {
        $widgets = array_fill(0, 11, ['type' => 'text']);
        $content = ['layout' => ['rows' => [['widgets' => $widgets]]]];
        $this->assertSame('complex', $this->extractor->extract($content)['complexity']);
    }

    public function testComplexityComplexWithCollapsibleEvenIfFewWidgets(): void {
        $content = [
            'layout' => [
                'rows' => [['collapsible' => true, 'widgets' => [['type' => 'text']]]],
            ],
        ];
        $this->assertSame('complex', $this->extractor->extract($content)['complexity']);
    }
}
