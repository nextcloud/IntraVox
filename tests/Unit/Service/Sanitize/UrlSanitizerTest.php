<?php
declare(strict_types=1);

namespace OCA\IntraVox\Tests\Unit\Service\Sanitize;

use OCA\IntraVox\Service\Sanitize\UrlSanitizer;
use PHPUnit\Framework\TestCase;

class UrlSanitizerTest extends TestCase {
    private UrlSanitizer $sanitizer;

    protected function setUp(): void {
        parent::setUp();
        $this->sanitizer = new UrlSanitizer();
    }

    public function testAcceptsHttpsUrl(): void {
        $this->assertSame('https://example.com/page', $this->sanitizer->sanitize('https://example.com/page'));
    }

    public function testAcceptsHttpUrl(): void {
        $this->assertSame('http://example.com/', $this->sanitizer->sanitize('http://example.com/'));
    }

    public function testAcceptsRelativePath(): void {
        $this->assertSame('/internal/page', $this->sanitizer->sanitize('/internal/page'));
    }

    public function testAcceptsHashAnchor(): void {
        $this->assertSame('#section', $this->sanitizer->sanitize('#section'));
    }

    public function testRejectsJavascriptScheme(): void {
        $this->assertSame('', $this->sanitizer->sanitize('javascript:alert(1)'));
    }

    public function testRejectsFileScheme(): void {
        $this->assertSame('', $this->sanitizer->sanitize('file:///etc/passwd'));
    }

    public function testRejectsDataUri(): void {
        $this->assertSame('', $this->sanitizer->sanitize('data:text/html;base64,PHNjcmlwdD4='));
    }

    public function testRejectsBareDomain(): void {
        // "example.com" without scheme is not a valid relative path either.
        $this->assertSame('', $this->sanitizer->sanitize('example.com'));
    }

    public function testReturnsEmptyForEmptyInput(): void {
        $this->assertSame('', $this->sanitizer->sanitize(''));
    }
}
