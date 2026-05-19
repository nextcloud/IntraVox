<?php
declare(strict_types=1);

namespace OCA\IntraVox\Tests\Unit\Service\Sanitize;

use OCA\IntraVox\Service\Sanitize\MediaSanitizer;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class MediaSanitizerTest extends TestCase {
    private MediaSanitizer $sanitizer;

    protected function setUp(): void {
        parent::setUp();
        $this->sanitizer = new MediaSanitizer($this->createMock(LoggerInterface::class));
    }

    // ---------- sanitizeFilename ----------

    public function testFilenameKeepsAlphanumeric(): void {
        $this->assertSame('foo_bar.png', $this->sanitizer->sanitizeFilename('foo_bar.png'));
    }

    public function testFilenameReplacesSpecialChars(): void {
        $this->assertSame('hello_world.jpg', $this->sanitizer->sanitizeFilename('hello world!.jpg'));
    }

    public function testFilenameCollapsesMultipleUnderscores(): void {
        $this->assertSame('a_b.png', $this->sanitizer->sanitizeFilename('a   b.png'));
    }

    public function testFilenameTrimsLeadingUnderscores(): void {
        // Leading "..." → "___" → trimmed away.
        $output = $this->sanitizer->sanitizeFilename('...foo.png');
        $this->assertSame('foo.png', $output);
    }

    public function testFilenameRejectsDisallowedExtensionByDefault(): void {
        $this->expectException(\InvalidArgumentException::class);
        $this->sanitizer->sanitizeFilename('virus.exe');
    }

    public function testFilenameAllowsAnyExtensionWhenValidationOff(): void {
        $this->assertSame('virus.exe', $this->sanitizer->sanitizeFilename('virus.exe', false));
    }

    public function testFilenameReplacesWindowsReservedName(): void {
        $output = $this->sanitizer->sanitizeFilename('con.png');
        $this->assertNotSame('con.png', $output);
        $this->assertStringEndsWith('.png', $output);
        $this->assertStringStartsWith('file_', $output);
    }

    public function testFilenameWindowsReservedIsCaseInsensitive(): void {
        $output = $this->sanitizer->sanitizeFilename('PRN.jpg');
        $this->assertStringStartsWith('file_', $output);
    }

    public function testFilenameTruncatesLongInputToFilesystemLimit(): void {
        $long = str_repeat('a', 400) . '.png';
        $output = $this->sanitizer->sanitizeFilename($long);
        $this->assertLessThanOrEqual(255, strlen($output));
        $this->assertStringEndsWith('.png', $output);
    }

    public function testFilenameEmptyBaseGetsFallback(): void {
        $output = $this->sanitizer->sanitizeFilename('___.png');
        $this->assertStringStartsWith('file_', $output);
        $this->assertStringEndsWith('.png', $output);
    }

    public function testFilenameLowercasesExtensionInValidation(): void {
        $this->assertSame('image.png', $this->sanitizer->sanitizeFilename('image.PNG'));
    }

    // ---------- sanitizeSVG ----------

    public function testSvgKeepsCleanContent(): void {
        $clean = '<?xml version="1.0"?><svg xmlns="http://www.w3.org/2000/svg"><rect width="10" height="10"/></svg>';
        $output = $this->sanitizer->sanitizeSVG($clean);
        $this->assertStringContainsString('<svg', $output);
        $this->assertStringContainsString('<rect', $output);
    }

    public function testSvgStripsScriptTag(): void {
        $malicious = '<svg xmlns="http://www.w3.org/2000/svg"><script>alert(1)</script></svg>';
        $output = $this->sanitizer->sanitizeSVG($malicious);
        $this->assertStringNotContainsString('<script', $output);
        $this->assertStringNotContainsString('alert(1)', $output);
    }

    public function testSvgStripsDoctype(): void {
        $payload = '<!DOCTYPE svg PUBLIC "-//W3C//DTD SVG 1.1//EN" "http://www.w3.org/Graphics/SVG/1.1/DTD/svg11.dtd"><svg xmlns="http://www.w3.org/2000/svg"/>';
        $output = $this->sanitizer->sanitizeSVG($payload);
        $this->assertStringNotContainsString('<!DOCTYPE', $output);
    }

    public function testSvgRejectsExternalEntity(): void {
        $this->expectException(\Exception::class);
        $payload = '<?xml version="1.0"?><!ENTITY xxe SYSTEM "file:///etc/passwd"><svg xmlns="http://www.w3.org/2000/svg">&xxe;</svg>';
        $this->sanitizer->sanitizeSVG($payload);
    }

    public function testSvgRejectsEmptyInput(): void {
        $this->expectException(\Exception::class);
        $this->sanitizer->sanitizeSVG('');
    }

    // ---------- validateImageFile ----------

    public function testValidateImageAcceptsRealPng(): void {
        $tmp = $this->writeRealPng();
        try {
            $this->sanitizer->validateImageFile($tmp, 'image/png');
            $this->expectNotToPerformAssertions();
        } finally {
            unlink($tmp);
        }
    }

    public function testValidateImageRejectsMimeMismatch(): void {
        $tmp = $this->writeRealPng();
        try {
            $this->expectException(\InvalidArgumentException::class);
            // We hand it the same file but claim it's a JPEG.
            $this->sanitizer->validateImageFile($tmp, 'image/jpeg');
        } finally {
            unlink($tmp);
        }
    }

    public function testValidateImageRejectsNonImageFile(): void {
        $tmp = tempnam(sys_get_temp_dir(), 'iv-not-image-');
        file_put_contents($tmp, 'plain text, definitely not an image');
        try {
            $this->expectException(\InvalidArgumentException::class);
            $this->sanitizer->validateImageFile($tmp, 'image/png');
        } finally {
            unlink($tmp);
        }
    }

    /**
     * Write a minimal but valid 1x1 transparent PNG to a temp file.
     */
    private function writeRealPng(): string {
        $tmp = tempnam(sys_get_temp_dir(), 'iv-png-');
        $png = base64_decode(
            'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk' .
            'YAAAAAYAAjCB0C8AAAAASUVORK5CYII='
        );
        file_put_contents($tmp, $png);
        return $tmp;
    }
}
