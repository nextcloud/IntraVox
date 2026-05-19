<?php
declare(strict_types=1);

namespace OCA\IntraVox\Tests\Unit\Service\Sanitize;

use OCA\IntraVox\Service\Sanitize\HtmlSanitizer;
use PHPUnit\Framework\TestCase;

class HtmlSanitizerTest extends TestCase {
    private HtmlSanitizer $sanitizer;

    protected function setUp(): void {
        parent::setUp();
        $this->sanitizer = new HtmlSanitizer();
    }

    public function testKeepsAllowedFormattingTags(): void {
        $html = '<p><strong>Hello</strong> <em>world</em></p>';
        $this->assertSame($html, $this->sanitizer->sanitize($html));
    }

    public function testKeepsTableMarkup(): void {
        $html = '<table><thead><tr><th>A</th></tr></thead><tbody><tr><td>B</td></tr></tbody></table>';
        $this->assertSame($html, $this->sanitizer->sanitize($html));
    }

    public function testStripsScriptTags(): void {
        $output = $this->sanitizer->sanitize('<p>safe</p><script>alert(1)</script>');
        $this->assertStringNotContainsString('<script', $output);
        $this->assertStringContainsString('<p>safe</p>', $output);
    }

    public function testStripsEventHandlers(): void {
        $output = $this->sanitizer->sanitize('<a href="/x" onclick="alert(1)">x</a>');
        $this->assertStringNotContainsString('onclick', $output);
        $this->assertStringContainsString('href="/x"', $output);
    }

    public function testRemovesJavascriptUriToken(): void {
        $output = $this->sanitizer->sanitize('<a href="javascript:alert(1)">x</a>');
        $this->assertStringNotContainsString('javascript:', $output);
    }

    public function testFiltersStyleAttributeToAllowedProperties(): void {
        $output = $this->sanitizer->sanitize('<td style="background-color: #fff; color: red; position: absolute">x</td>');
        $this->assertStringContainsString('background-color: #fff', $output);
        $this->assertStringContainsString('color: red', $output);
        $this->assertStringNotContainsString('position', $output);
    }

    public function testRejectsExpressionStyleValues(): void {
        $output = $this->sanitizer->sanitize('<p style="background-color: expression(alert(1))">x</p>');
        $this->assertStringNotContainsString('expression', $output);
    }

    public function testEscapesBareAmpersand(): void {
        $output = $this->sanitizer->sanitize('<p>A & B</p>');
        $this->assertStringContainsString('A &amp; B', $output);
    }

    public function testPreservesValidEntities(): void {
        $output = $this->sanitizer->sanitize('<p>&copy; 2026</p>');
        $this->assertStringContainsString('&copy;', $output);
    }

    public function testDecodeEntitiesRecursiveStablesAfterMultipleRounds(): void {
        // "&amp;amp;" → "&amp;" → "&"
        $this->assertSame('&', $this->sanitizer->decodeEntitiesRecursive('&amp;amp;'));
    }

    public function testDecodeEntitiesRecursiveIsIdempotentOnPlainText(): void {
        $this->assertSame('plain', $this->sanitizer->decodeEntitiesRecursive('plain'));
    }
}
