<?php
declare(strict_types=1);

namespace OCA\IntraVox\Tests\Unit\Service\PhotoStory;

use OCA\IntraVox\Service\PhotoStory\PhotoQueryBuilder;
use PHPUnit\Framework\TestCase;

/**
 * The filter-operator allowlist, and the fact that it is written down twice.
 *
 * PhotoStoryController::sanitizeFilters() drops any filter row whose operator is
 * not on its own ALLOWED_OPS list; PhotoQueryBuilder re-checks the same thing
 * before building SQL. Defence in depth is right here — the query builder must
 * not assume a caller sanitised for it — but two lists that must agree is a
 * drift risk, and drift on THIS list means either a silently-ignored filter or
 * an operator reaching the query layer that nothing validated.
 *
 * The queries themselves are not unit-testable without a database; they are
 * exercised on dev against real data. What is testable is the contract between
 * the two lists, which is where a mistake would actually hide.
 */
class PhotoQueryBuilderTest extends TestCase {
    private function controllerOps(): array {
        $source = (string)file_get_contents(
            \dirname(__DIR__, 4) . '/lib/Controller/PhotoStoryController.php'
        );

        $this->assertMatchesRegularExpression(
            '/const ALLOWED_OPS = \[[^\]]+\];/',
            $source,
            'PhotoStoryController::ALLOWED_OPS moved or was renamed'
        );
        preg_match('/const ALLOWED_OPS = \[([^\]]+)\];/', $source, $m);
        preg_match_all("/'([a-z_]+)'/", $m[1], $ops);

        return $ops[1];
    }

    public function testTheControllerAndTheQueryLayerAllowExactlyTheSameOperators(): void {
        $this->assertSame(
            PhotoQueryBuilder::ALLOWED_OPS,
            $this->controllerOps(),
            'the controller drops operators this list does not have, and vice versa; '
            . 'they must stay identical or a filter silently stops working'
        );
    }

    /**
     * year_equals is the one that distinguishes this list from the People
     * matcher's is_today/within_next_days. It compares only the year, which is
     * why the two engines were not merged.
     */
    public function testTheAllowlistIsTheKnownFourAndNothingElse(): void {
        $this->assertSame(
            ['equals', 'contains', 'in', 'year_equals'],
            PhotoQueryBuilder::ALLOWED_OPS
        );
    }

    /** The widget maps these MetaVox fields; the list feeds the field picker. */
    public function testTheWellKnownFieldsAreAllExifPrefixed(): void {
        $this->assertNotEmpty(PhotoQueryBuilder::WELL_KNOWN_PHOTO_FIELDS);

        foreach (PhotoQueryBuilder::WELL_KNOWN_PHOTO_FIELDS as $field) {
            $this->assertStringStartsWith(
                'exif_',
                $field,
                'the controller only accepts fields matching /^exif_[a-z_]+$/'
            );
            $this->assertMatchesRegularExpression('/^exif_[a-z_]+$/', $field);
        }
    }
}
