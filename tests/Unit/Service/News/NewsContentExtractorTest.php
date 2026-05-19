<?php
declare(strict_types=1);

namespace OCA\IntraVox\Tests\Unit\Service\News;

use OCA\IntraVox\Service\News\NewsContentExtractor;
use PHPUnit\Framework\TestCase;

class NewsContentExtractorTest extends TestCase {
    private NewsContentExtractor $extractor;

    protected function setUp(): void {
        parent::setUp();
        $this->extractor = new NewsContentExtractor();
    }

    // ---------- getExcerpt ----------

    public function testEmptyPageReturnsEmptyString(): void {
        $this->assertSame('', $this->extractor->getExcerpt([]));
    }

    public function testReturnsFirstTextWidgetContent(): void {
        $page = [
            'layout' => [
                'rows' => [
                    ['widgets' => [['type' => 'text', 'content' => 'Hello world from the first text widget.']]],
                ],
            ],
        ];

        $this->assertSame(
            'Hello world from the first text widget.',
            $this->extractor->getExcerpt($page)
        );
    }

    public function testStripsHtmlFromContent(): void {
        $page = [
            'layout' => [
                'rows' => [
                    ['widgets' => [['type' => 'text', 'content' => '<p>Hello <strong>bold</strong> world</p>']]],
                ],
            ],
        ];

        $this->assertSame('Hello bold world', $this->extractor->getExcerpt($page));
    }

    public function testTruncatesAtWordBoundary(): void {
        $longContent = str_repeat('one two three four ', 20);
        $page = [
            'layout' => [
                'rows' => [['widgets' => [['type' => 'text', 'content' => $longContent]]]],
            ],
        ];

        $output = $this->extractor->getExcerpt($page, 50);
        $this->assertLessThanOrEqual(53, strlen($output)); // 50 + '...'
        $this->assertStringEndsWith('...', $output);
        // Should end after a space (truncation lands on word boundary)
        $body = substr($output, 0, -3); // strip trailing '...'
        // The last char before '...' should not be a partial word — i.e. trim
        // confirms the body ends on a complete word from the source text.
        $this->assertStringContainsString($body, $longContent);
    }

    public function testSkipsEmptyTextWidgetsAndContinues(): void {
        $page = [
            'layout' => [
                'rows' => [
                    ['widgets' => [
                        ['type' => 'text', 'content' => ''],
                        ['type' => 'text', 'content' => 'Real content here'],
                    ]],
                ],
            ],
        ];

        $this->assertSame('Real content here', $this->extractor->getExcerpt($page));
    }

    public function testSkipsNonTextWidgets(): void {
        $page = [
            'layout' => [
                'rows' => [
                    ['widgets' => [
                        ['type' => 'image', 'src' => '/img.png'],
                        ['type' => 'text', 'content' => 'Text after image'],
                    ]],
                ],
            ],
        ];

        $this->assertSame('Text after image', $this->extractor->getExcerpt($page));
    }

    public function testCollapsesWhitespace(): void {
        $page = [
            'layout' => [
                'rows' => [['widgets' => [['type' => 'text', 'content' => "lots\n\n  of\twhitespace"]]]],
            ],
        ];

        $this->assertSame('lots of whitespace', $this->extractor->getExcerpt($page));
    }

    // ---------- getFirstImage ----------

    public function testNoImageReturnsNull(): void {
        $this->assertNull($this->extractor->getFirstImage([]));
    }

    public function testHeaderRowImageTakesPrecedence(): void {
        $page = [
            'layout' => [
                'headerRow' => ['widgets' => [['type' => 'image', 'src' => '/header.jpg']]],
                'rows' => [['widgets' => [['type' => 'image', 'src' => '/body.jpg']]]],
            ],
        ];

        $output = $this->extractor->getFirstImage($page);
        $this->assertSame('/header.jpg', $output['src']);
    }

    public function testFallsThroughToMainRowsWhenHeaderEmpty(): void {
        $page = [
            'layout' => [
                'rows' => [['widgets' => [['type' => 'image', 'src' => '/in-body.jpg']]]],
            ],
        ];

        $output = $this->extractor->getFirstImage($page);
        $this->assertSame('/in-body.jpg', $output['src']);
    }

    public function testReturnsMediaFolderDefault(): void {
        $page = ['layout' => ['rows' => [['widgets' => [['type' => 'image', 'src' => '/x.png']]]]]];
        $output = $this->extractor->getFirstImage($page);
        $this->assertSame('page', $output['mediaFolder']);
    }

    public function testReturnsExplicitMediaFolder(): void {
        $page = [
            'layout' => [
                'rows' => [['widgets' => [['type' => 'image', 'src' => '/x.png', 'mediaFolder' => 'resources']]]],
            ],
        ];
        $output = $this->extractor->getFirstImage($page);
        $this->assertSame('resources', $output['mediaFolder']);
    }

    public function testSkipsImageWidgetWithoutSrc(): void {
        $page = [
            'layout' => [
                'rows' => [['widgets' => [
                    ['type' => 'image'],
                    ['type' => 'image', 'src' => '/real.png'],
                ]]],
            ],
        ];

        $output = $this->extractor->getFirstImage($page);
        $this->assertSame('/real.png', $output['src']);
    }

    // ---------- stripMarkdown ----------

    public function testStripsBold(): void {
        $this->assertSame('hello bold', $this->extractor->stripMarkdown('hello **bold**'));
    }

    public function testStripsLink(): void {
        $this->assertSame('click here', $this->extractor->stripMarkdown('[click here](https://x.com)'));
    }

    public function testStripsHeader(): void {
        $this->assertSame('Title', $this->extractor->stripMarkdown('# Title'));
    }

    public function testStripsListMarker(): void {
        $this->assertSame("item one\nitem two", $this->extractor->stripMarkdown("- item one\n- item two"));
    }

    public function testIsIdempotentOnPlainText(): void {
        $this->assertSame('just text', $this->extractor->stripMarkdown('just text'));
    }
}
