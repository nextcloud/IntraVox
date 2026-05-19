<?php
declare(strict_types=1);

namespace OCA\IntraVox\Tests\Unit\Service\Version;

use OCA\Files_Versions\Versions\IVersion;
use OCA\IntraVox\Service\Version\PageVersionFormatter;
use PHPUnit\Framework\TestCase;

class PageVersionFormatterTest extends TestCase {
    private PageVersionFormatter $formatter;

    protected function setUp(): void {
        parent::setUp();
        $this->formatter = new PageVersionFormatter();
    }

    // ---------- formatRelativeTime ----------

    public function testJustNowForFutureTimestamp(): void {
        $now = 1_000_000;
        $this->assertSame('just now', $this->formatter->formatRelativeTime($now + 5, $now));
    }

    public function testSecondsAgo(): void {
        $now = 1_000_000;
        $this->assertSame('30 sec. ago', $this->formatter->formatRelativeTime($now - 30, $now));
    }

    public function testMinutesAgo(): void {
        $now = 1_000_000;
        $this->assertSame('2 min. ago', $this->formatter->formatRelativeTime($now - 120, $now));
    }

    public function testSingleHourAgo(): void {
        $now = 1_000_000;
        $this->assertSame('1 hour ago', $this->formatter->formatRelativeTime($now - 3600, $now));
    }

    public function testMultipleHoursPluralizesCorrectly(): void {
        $now = 1_000_000;
        $this->assertSame('3 hours ago', $this->formatter->formatRelativeTime($now - 3 * 3600, $now));
    }

    public function testSingleDayAgo(): void {
        $now = 1_000_000;
        $this->assertSame('1 day ago', $this->formatter->formatRelativeTime($now - 86400, $now));
    }

    public function testMultipleDaysAgo(): void {
        $now = 1_000_000;
        $this->assertSame('4 days ago', $this->formatter->formatRelativeTime($now - 4 * 86400, $now));
    }

    public function testOlderThanWeekShowsAbsoluteDate(): void {
        $now = 1_700_000_000; // anchored
        $eightDaysAgo = $now - 8 * 86400;
        $output = $this->formatter->formatRelativeTime($eightDaysAgo, $now);

        // Format is "j M Y" — match the year at least so we know it's an absolute date.
        $expectedYear = (new \DateTime())->setTimestamp($eightDaysAgo)->format('Y');
        $this->assertStringContainsString($expectedYear, $output);
        // Should not be a "X days ago" string at this point.
        $this->assertStringNotContainsString('ago', $output);
    }

    // ---------- getAuthor / getLabel ----------

    public function testAuthorReturnsNullWhenBackendLacksMetadata(): void {
        $version = $this->createMock(IVersion::class);
        $this->assertNull($this->formatter->getAuthor($version));
    }

    public function testLabelReturnsNullWhenBackendLacksMetadata(): void {
        $version = $this->createMock(IVersion::class);
        $this->assertNull($this->formatter->getLabel($version));
    }

    public function testAuthorReadsFromGetMetadataWhenAvailable(): void {
        // Anonymous IVersion impl that also exposes getMetadata, like
        // Files_Versions' GroupVersion does in production.
        $version = new class implements IVersion {
            public function getTimestamp(): int { return 0; }
            public function getSize(): int { return 0; }
            public function getMetadata(): array { return ['author' => 'alice', 'label' => 'pre-release']; }
        };

        $this->assertSame('alice', $this->formatter->getAuthor($version));
        $this->assertSame('pre-release', $this->formatter->getLabel($version));
    }

    // ---------- formatVersions ----------

    public function testFormatVersionsSortsNewestFirst(): void {
        $older = $this->makeVersion(1_700_000_000, 100);
        $newer = $this->makeVersion(1_700_000_500, 200);

        $output = $this->formatter->formatVersions([$older, $newer]);

        $this->assertCount(2, $output);
        $this->assertSame(1_700_000_500, $output[0]['timestamp']);
        $this->assertSame(1_700_000_000, $output[1]['timestamp']);
    }

    public function testFormatVersionsExposesAllRequiredFields(): void {
        $version = $this->makeVersion(1_700_000_000, 4096);
        $output = $this->formatter->formatVersions([$version]);

        $this->assertCount(1, $output);
        $entry = $output[0];

        $this->assertArrayHasKey('id', $entry);
        $this->assertArrayHasKey('timestamp', $entry);
        $this->assertArrayHasKey('size', $entry);
        $this->assertArrayHasKey('author', $entry);
        $this->assertArrayHasKey('label', $entry);
        $this->assertArrayHasKey('formattedDate', $entry);
        $this->assertArrayHasKey('relativeTime', $entry);

        $this->assertSame(1_700_000_000, $entry['timestamp']);
        $this->assertSame(4096, $entry['size']);
    }

    public function testFormatVersionsHandlesEmptyInput(): void {
        $this->assertSame([], $this->formatter->formatVersions([]));
    }

    private function makeVersion(int $timestamp, int $size): IVersion {
        $version = $this->createMock(IVersion::class);
        $version->method('getTimestamp')->willReturn($timestamp);
        $version->method('getSize')->willReturn($size);
        return $version;
    }
}
