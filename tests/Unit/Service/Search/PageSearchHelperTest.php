<?php
declare(strict_types=1);

namespace OCA\IntraVox\Tests\Unit\Service\Search;

use OCA\IntraVox\Service\Search\PageSearchHelper;
use PHPUnit\Framework\TestCase;

class PageSearchHelperTest extends TestCase {
    private PageSearchHelper $helper;

    protected function setUp(): void {
        parent::setUp();
        $this->helper = new PageSearchHelper();
    }

    // ---------- extractSnippet ----------

    public function testExtractSnippetReturnsBeginningWhenQueryMissing(): void {
        $text = 'Lorem ipsum dolor sit amet';
        $output = $this->helper->extractSnippet($text, 'missing', 10);
        $this->assertStringStartsWith('Lorem', $output);
        $this->assertStringEndsWith('...', $output);
    }

    public function testExtractSnippetCentersAroundMatch(): void {
        $text = 'aaaaaaaaaaaaaa needle bbbbbbbbbbbbbb';
        $output = $this->helper->extractSnippet($text, 'needle', 20);
        $this->assertStringContainsString('needle', $output);
        $this->assertStringStartsWith('...', $output);
        $this->assertStringEndsWith('...', $output);
    }

    public function testExtractSnippetOmitsLeadingEllipsisAtStart(): void {
        $output = $this->helper->extractSnippet('needle at the start', 'needle', 30);
        $this->assertStringStartsNotWith('...', $output);
    }

    public function testExtractSnippetStripsMarkdownChars(): void {
        $output = $this->helper->extractSnippet('**bold** _italic_ text', 'italic', 30);
        $this->assertStringNotContainsString('**', $output);
        $this->assertStringNotContainsString('_', $output);
    }

    // ---------- searchWidget ----------

    public function testTextWidgetMatchScoresThree(): void {
        $matches = $this->helper->searchWidget(
            ['type' => 'text', 'content' => 'this contains needle here'],
            'needle'
        );
        $this->assertCount(1, $matches);
        $this->assertSame('content', $matches[0]['type']);
        $this->assertSame(3, $matches[0]['score']);
    }

    public function testHeadingWidgetMatchScoresFiveAndKeepsFullText(): void {
        $matches = $this->helper->searchWidget(
            ['type' => 'heading', 'content' => 'A heading with needle'],
            'needle'
        );
        $this->assertCount(1, $matches);
        $this->assertSame('heading', $matches[0]['type']);
        $this->assertSame(5, $matches[0]['score']);
        $this->assertSame('A heading with needle', $matches[0]['text']);
    }

    public function testImageWidgetMatchUsesAltText(): void {
        $matches = $this->helper->searchWidget(
            ['type' => 'image', 'alt' => 'photo of a needle', 'src' => '/x.png'],
            'needle'
        );
        $this->assertCount(1, $matches);
        $this->assertSame('image', $matches[0]['type']);
        $this->assertSame('photo of a needle', $matches[0]['text']);
        $this->assertSame(2, $matches[0]['score']);
    }

    public function testLinksWidgetMatchesEachLinkSeparately(): void {
        $matches = $this->helper->searchWidget(
            [
                'type' => 'links',
                'items' => [
                    ['title' => 'Find the needle', 'text' => ''],
                    ['title' => 'Some other link', 'text' => 'with a needle inside'],
                    ['title' => 'No match', 'text' => 'nothing here'],
                ],
            ],
            'needle'
        );
        $this->assertCount(2, $matches);
        foreach ($matches as $match) {
            $this->assertSame('link', $match['type']);
            $this->assertSame(3, $match['score']);
        }
    }

    public function testFileWidgetMatchUsesName(): void {
        $matches = $this->helper->searchWidget(
            ['type' => 'file', 'name' => 'needle-report.pdf'],
            'needle'
        );
        $this->assertSame('file', $matches[0]['type']);
    }

    public function testVideoWidgetMatchUsesTitle(): void {
        $matches = $this->helper->searchWidget(
            ['type' => 'video', 'title' => 'Demo about needles'],
            'needle'
        );
        $this->assertSame('video', $matches[0]['type']);
    }

    public function testNoMatchReturnsEmptyArray(): void {
        $matches = $this->helper->searchWidget(
            ['type' => 'text', 'content' => 'haystack only'],
            'needle'
        );
        $this->assertSame([], $matches);
    }

    public function testUnknownWidgetTypeReturnsEmptyArray(): void {
        $matches = $this->helper->searchWidget(
            ['type' => 'spacer'],
            'needle'
        );
        $this->assertSame([], $matches);
    }

    public function testSearchIsCaseInsensitive(): void {
        $matches = $this->helper->searchWidget(
            ['type' => 'heading', 'content' => 'NEEDLE in heading'],
            'needle'
        );
        $this->assertCount(1, $matches);
    }
}
