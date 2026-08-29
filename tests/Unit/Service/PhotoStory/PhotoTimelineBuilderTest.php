<?php
declare(strict_types=1);

namespace OCA\IntraVox\Tests\Unit\Service\PhotoStory;

use OCA\IntraVox\Service\PhotoStory\PhotoTimelineBuilder;
use OCP\IL10N;
use PHPUnit\Framework\TestCase;

/**
 * The arithmetic behind a photo story.
 *
 * PhotoStoryService had 3287 lines and no tests at all — the largest untested
 * service in the app. This block was pulled out first precisely because it
 * touches no database, filesystem or MetaVox client, so it can be exercised on
 * plain arrays. These are the first assertions that code has ever had.
 */
class PhotoTimelineBuilderTest extends TestCase {
    private PhotoTimelineBuilder $builder;

    protected function setUp(): void {
        parent::setUp();
        $l10n = $this->createMock(IL10N::class);
        // Identity translator: the month names are asserted in English.
        $l10n->method('t')->willReturnCallback(static fn (string $text, array $p = []) => $text);
        $this->builder = new PhotoTimelineBuilder($l10n);
    }

    /** @return array<string,mixed> */
    private function photo(int $id, string $takenAt, array $extra = []): array {
        return array_merge([
            'file_id' => $id,
            'taken_at' => $takenAt,
            'mtime' => 0,
        ], $extra);
    }

    public function testPhotosAreBucketedByDay(): void {
        $timeline = $this->builder->computeTimeline([
            $this->photo(1, '2026-05-08 10:00:00'),
            $this->photo(2, '2026-05-08 18:00:00'),
            $this->photo(3, '2026-05-09 09:00:00'),
        ]);

        $this->assertCount(2, $timeline, 'two distinct days');
        $this->assertSame('2026-05-08', $timeline[0]['date']);
        $this->assertSame('2026-05-09', $timeline[1]['date']);
    }

    public function testMonthAndYearGranularityCollapseTheBuckets(): void {
        $photos = [
            $this->photo(1, '2026-05-08 10:00:00'),
            $this->photo(2, '2026-05-30 10:00:00'),
            $this->photo(3, '2026-06-01 10:00:00'),
        ];

        $byMonth = $this->builder->computeTimeline($photos, 'asc', 'month');
        $this->assertSame(['2026-05', '2026-06'], array_column($byMonth, 'date'));

        $byYear = $this->builder->computeTimeline($photos, 'asc', 'year');
        $this->assertSame(['2026'], array_column($byYear, 'date'));
    }

    public function testDescendingOrderReversesTheBuckets(): void {
        $photos = [
            $this->photo(1, '2026-05-08 10:00:00'),
            $this->photo(2, '2026-05-09 10:00:00'),
        ];

        $this->assertSame(
            ['2026-05-09', '2026-05-08'],
            array_column($this->builder->computeTimeline($photos, 'desc'), 'date')
        );
    }

    /**
     * taken_at is the EXIF capture time and mtime is when the file landed on
     * disk. Sorting a holiday album by mtime would order it by upload, not by
     * when the pictures were taken, so the caller gets to choose.
     */
    public function testTheDateFieldDecidesWhichTimestampIsUsed(): void {
        $photos = [
            ['file_id' => 1, 'taken_at' => '2020-01-01 10:00:00', 'mtime' => mktime(10, 0, 0, 6, 1, 2026)],
        ];

        $byTaken = $this->builder->computeTimeline($photos, 'asc', 'year', 'taken_at');
        $byMtime = $this->builder->computeTimeline($photos, 'asc', 'year', 'mtime');

        $this->assertSame(['2020'], array_column($byTaken, 'date'));
        $this->assertSame(['2026'], array_column($byMtime, 'date'));
    }

    /** A photo without EXIF falls back to mtime rather than to epoch zero. */
    public function testAMissingCaptureTimeFallsBackToMtime(): void {
        $mtime = mktime(12, 0, 0, 3, 4, 2026);

        $this->assertSame($mtime, $this->builder->resolveTimestamp(['mtime' => $mtime]));
        $this->assertSame($mtime, $this->builder->resolveTimestamp(['taken_at' => '', 'mtime' => $mtime]));
    }

    /** An unparseable EXIF date must not throw; it degrades to mtime. */
    public function testAnUnparseableCaptureTimeDegradesInsteadOfThrowing(): void {
        $mtime = mktime(12, 0, 0, 3, 4, 2026);

        $this->assertSame(
            $mtime,
            $this->builder->resolveTimestamp(['taken_at' => 'volstrekte onzin', 'mtime' => $mtime])
        );
    }

    public function testAnEmptySetProducesAnEmptyTimeline(): void {
        $this->assertSame([], $this->builder->computeTimeline([]));
        $this->assertSame([], $this->builder->getLocationClusters([]));
    }

    public function testPhotosWithTheSamePlaceNameClusterTogether(): void {
        $clusters = $this->builder->getLocationClusters([
            $this->photo(1, '2026-05-08 10:00:00', ['location' => 'Amsterdam']),
            $this->photo(2, '2026-05-08 11:00:00', ['location' => 'Amsterdam']),
            $this->photo(3, '2026-05-08 12:00:00', ['location' => 'Utrecht']),
        ]);

        $this->assertCount(2, $clusters);
        $byName = array_column($clusters, 'count', 'location');
        $this->assertSame(2, $byName['Amsterdam']);
        $this->assertSame(1, $byName['Utrecht']);
    }

    /** Without a place name, nearby coordinates collapse onto a ~1km grid. */
    public function testNearbyCoordinatesClusterOntoTheSameGridCell(): void {
        $clusters = $this->builder->getLocationClusters([
            $this->photo(1, '2026-05-08 10:00:00', ['gps' => ['lat' => 52.3701, 'lon' => 4.8951]]),
            $this->photo(2, '2026-05-08 10:05:00', ['gps' => ['lat' => 52.3703, 'lon' => 4.8952]]),
            $this->photo(3, '2026-05-08 10:10:00', ['gps' => ['lat' => 51.9244, 'lon' => 4.4777]]),
        ]);

        $this->assertCount(2, $clusters, 'two locations a kilometre apart, not three');
    }

    public function testPhotosWithoutAnyLocationAreLeftOut(): void {
        $clusters = $this->builder->getLocationClusters([
            $this->photo(1, '2026-05-08 10:00:00'),
            $this->photo(2, '2026-05-08 10:00:00', ['location' => 'Delft']),
        ]);

        $this->assertCount(1, $clusters);
        $this->assertSame('Delft', $clusters[0]['location']);
        $this->assertSame([2], $clusters[0]['photo_ids']);
    }

    /** A label-only cluster picks up coordinates from a later photo. */
    public function testALabelClusterAdoptsGpsFromAPhotoThatHasIt(): void {
        $clusters = $this->builder->getLocationClusters([
            $this->photo(1, '2026-05-08 10:00:00', ['location' => 'Delft']),
            $this->photo(2, '2026-05-08 11:00:00', ['location' => 'Delft', 'gps' => ['lat' => 52.0116, 'lon' => 4.3571]]),
        ]);

        $this->assertCount(1, $clusters);
        $this->assertNotNull($clusters[0]['gps']);
        $this->assertEqualsWithDelta(52.0116, $clusters[0]['gps']['lat'], 0.0001);
    }

    public function testHighlightsAreCappedAtTheRequestedNumber(): void {
        $photos = [];
        for ($i = 1; $i <= 20; $i++) {
            $photos[] = $this->photo($i, sprintf('2026-05-%02d 10:00:00', $i));
        }

        $this->assertLessThanOrEqual(5, count($this->builder->computeHighlights($photos, 5)));
    }
}
