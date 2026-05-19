<?php
declare(strict_types=1);

namespace OCA\IntraVox\Tests\Unit\Service\Util;

use OCA\IntraVox\Service\Util\PageIdUtils;
use PHPUnit\Framework\TestCase;

class PageIdUtilsTest extends TestCase {
    private PageIdUtils $utils;

    protected function setUp(): void {
        parent::setUp();
        $this->utils = new PageIdUtils();
    }

    // ---------- sanitizeId ----------

    public function testSanitizeIdKeepsAlphanumericHyphenUnderscore(): void {
        $this->assertSame('page-123_abc', $this->utils->sanitizeId('page-123_abc'));
    }

    public function testSanitizeIdStripsOtherCharacters(): void {
        $this->assertSame('page123', $this->utils->sanitizeId('page!@#$%^123'));
    }

    public function testSanitizeIdThrowsOnEmptyResult(): void {
        $this->expectException(\InvalidArgumentException::class);
        $this->utils->sanitizeId('@#$%');
    }

    public function testSanitizeIdThrowsOnEmptyInput(): void {
        $this->expectException(\InvalidArgumentException::class);
        $this->utils->sanitizeId('');
    }

    // ---------- generateUUID ----------

    public function testGenerateUUIDMatchesV4Format(): void {
        $uuid = $this->utils->generateUUID();
        // 8-4-4-4-12 hex, with version 4 in third group and variant 8/9/a/b in fourth
        $this->assertMatchesRegularExpression(
            '/^[a-f0-9]{8}-[a-f0-9]{4}-4[a-f0-9]{3}-[89ab][a-f0-9]{3}-[a-f0-9]{12}$/',
            $uuid
        );
    }

    public function testGenerateUUIDProducesDistinctValues(): void {
        $a = $this->utils->generateUUID();
        $b = $this->utils->generateUUID();
        $this->assertNotSame($a, $b);
    }

    // ---------- parsePhpSize ----------

    public function testParsePhpSizeKilobytes(): void {
        $this->assertSame(2048, $this->utils->parsePhpSize('2K'));
    }

    public function testParsePhpSizeMegabytes(): void {
        $this->assertSame(2 * 1024 * 1024, $this->utils->parsePhpSize('2M'));
    }

    public function testParsePhpSizeGigabytes(): void {
        $this->assertSame(1024 * 1024 * 1024, $this->utils->parsePhpSize('1G'));
    }

    public function testParsePhpSizeLowercaseSuffixWorks(): void {
        $this->assertSame(8 * 1024 * 1024, $this->utils->parsePhpSize('8m'));
    }

    public function testParsePhpSizeBareNumberIsBytes(): void {
        $this->assertSame(12345, $this->utils->parsePhpSize('12345'));
    }

    public function testParsePhpSizeEmptyReturnsZero(): void {
        $this->assertSame(0, $this->utils->parsePhpSize(''));
    }

    // ---------- formatBytes ----------

    public function testFormatBytesBelowKilo(): void {
        $this->assertSame('512 B', $this->utils->formatBytes(512));
    }

    public function testFormatBytesKilobytes(): void {
        $this->assertSame('1 KB', $this->utils->formatBytes(1024));
        $this->assertSame('1.5 KB', $this->utils->formatBytes(1536));
    }

    public function testFormatBytesMegabytes(): void {
        $this->assertSame('1 MB', $this->utils->formatBytes(1024 * 1024));
    }

    public function testFormatBytesGigabytes(): void {
        $this->assertSame('1 GB', $this->utils->formatBytes(1024 * 1024 * 1024));
    }

    public function testFormatBytesZero(): void {
        $this->assertSame('0 B', $this->utils->formatBytes(0));
    }
}
