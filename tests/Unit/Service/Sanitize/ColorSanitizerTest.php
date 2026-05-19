<?php
declare(strict_types=1);

namespace OCA\IntraVox\Tests\Unit\Service\Sanitize;

use OCA\IntraVox\Service\Sanitize\ColorSanitizer;
use PHPUnit\Framework\TestCase;

class ColorSanitizerTest extends TestCase {
    private ColorSanitizer $sanitizer;

    protected function setUp(): void {
        parent::setUp();
        $this->sanitizer = new ColorSanitizer();
    }

    public function testEmptyInputReturnsEmpty(): void {
        $this->assertSame('', $this->sanitizer->sanitize(''));
    }

    public function testAcceptsTransparent(): void {
        $this->assertSame('transparent', $this->sanitizer->sanitize('transparent'));
    }

    public function testAcceptsNextcloudThemeVariable(): void {
        $this->assertSame(
            'var(--color-primary-element)',
            $this->sanitizer->sanitize('var(--color-primary-element)')
        );
    }

    public function testRejectsArbitraryCssVariable(): void {
        $this->assertSame('', $this->sanitizer->sanitize('var(--my-custom-var)'));
    }

    public function testAcceptsShortHex(): void {
        $this->assertSame('#fff', $this->sanitizer->sanitize('#fff'));
    }

    public function testAcceptsLongHex(): void {
        $this->assertSame('#FF8800', $this->sanitizer->sanitize('#FF8800'));
    }

    public function testRejectsInvalidHex(): void {
        $this->assertSame('', $this->sanitizer->sanitize('#GGGGGG'));
    }

    public function testAcceptsRgb(): void {
        $this->assertSame('rgb(255, 128, 0)', $this->sanitizer->sanitize('rgb(255, 128, 0)'));
    }

    public function testAcceptsRgba(): void {
        $this->assertSame('rgba(255, 128, 0, 0.5)', $this->sanitizer->sanitize('rgba(255, 128, 0, 0.5)'));
    }

    public function testRejectsNamedColor(): void {
        $this->assertSame('', $this->sanitizer->sanitize('red'));
    }

    public function testRejectsExpression(): void {
        $this->assertSame('', $this->sanitizer->sanitize('expression(alert(1))'));
    }
}
