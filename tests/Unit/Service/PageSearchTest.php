<?php
declare(strict_types=1);

namespace OCA\IntraVox\Tests\Unit\Service;

use OCA\IntraVox\Service\PageService;
use OCA\IntraVox\Service\Publication\MetaVoxGateway;
use OCA\IntraVox\Tests\Unit\Service\Harness\BuildsPageService;
use PHPUnit\Framework\TestCase;

/**
 * Characterization of searchPages() — its scoring, match-collection, sorting and
 * limits — before that body is extracted to a search collaborator.
 *
 * searchPages had ZERO behavioural coverage: the only file that even mentioned
 * it (PageServicePublicSurfaceTest) is an arity map ('searchPages' => 1). This
 * pins the observable contract so the extraction can be proven byte-equivalent.
 *
 * Strategy: override the public listPagesWithContent() seam to feed fixture
 * pages straight into the scorer (bypassing the filesystem walk), stub the
 * private metaVox() collaborator to contribute nothing (so content scoring is
 * tested in isolation), and let the REAL final PageSearchHelper run the widget
 * scoring. What is asserted is the exact score weighting, the 3-match cap with
 * an uncapped matchCount, the score-descending sort, and the top-20 limit.
 */
class PageSearchTest extends TestCase {

    use BuildsPageService;

    /**
     * A PageService whose listPagesWithContent() returns $pages verbatim and
     * whose metaVox() gateway contributes no metadata matches.
     *
     * @param list<array> $pages page-data arrays as listPagesWithContent yields
     */
    private function makeService(array $pages): PageService {
        $svc = new class($pages) extends PageService {
            /** @var list<array> */
            private array $pages;
            public function __construct(array $pages) {
                $this->pages = $pages;
            }
            public function listPagesWithContent(): array {
                return $this->pages;
            }
        };

        // metaVox() is private (not overridable); set the gateway property so the
        // lazy accessor finds it already built. It answers "no metadata" to every
        // call, keeping the MetaVox scoring branch inert for these fixtures.
        $metaVox = $this->createMock(MetaVoxGateway::class);
        $metaVox->method('getMetaVoxDataForFiles')->willReturn([]);
        $metaVox->method('getMetaVoxFieldLabels')->willReturn([]);
        $metaVox->method('searchMetaVoxValues')->willReturn([]);
        $metaVox->method('groupfolderIdForFile')->willReturn(null);

        $this->injectPageServiceDependencies($svc, [
            'metaVoxGateway' => $metaVox,
        ]);
        return $svc;
    }

    /** A page with a title and an optional list of layout rows/widgets. */
    private function page(string $uniqueId, string $title, array $widgets = [], string $path = ''): array {
        return [
            'uniqueId' => $uniqueId,
            'title' => $title,
            'path' => $path,
            'fileId' => abs(crc32($uniqueId)),
            'layout' => ['rows' => [['widgets' => $widgets]]],
        ];
    }

    // ------------------------------------------------------------ scoring weights

    public function testTitleMatchScoresTenAndAddsATitleMatch(): void {
        $svc = $this->makeService([$this->page('page-a', 'Vakantiebeleid')]);

        $results = $svc->searchPages('vakantie');

        $this->assertCount(1, $results);
        $this->assertSame('page-a', $results[0]['uniqueId']);
        $this->assertSame(10, $results[0]['score']);
        $this->assertSame('title', $results[0]['matches'][0]['type']);
        $this->assertSame('Vakantiebeleid', $results[0]['matches'][0]['text']);
    }

    public function testTitleMatchIsCaseInsensitive(): void {
        $svc = $this->makeService([$this->page('page-a', 'VAKANTIEbeleid')]);

        $results = $svc->searchPages('Vakantie');

        $this->assertCount(1, $results);
        $this->assertSame(10, $results[0]['score']);
    }

    public function testUniqueIdMatchScoresFiveWithoutAddingAMatchEntry(): void {
        // Query hits the uniqueId but neither the title nor any widget.
        $svc = $this->makeService([$this->page('page-vakantie', 'Onbekend')]);

        $results = $svc->searchPages('vakantie');

        $this->assertCount(1, $results);
        $this->assertSame(5, $results[0]['score']);
        $this->assertSame(0, $results[0]['matchCount'], 'a uniqueId hit scores but adds no match descriptor');
        $this->assertSame([], $results[0]['matches']);
    }

    public function testHeadingWidgetContentScoresFive(): void {
        $svc = $this->makeService([
            $this->page('page-a', 'Onbekend', [
                ['type' => 'heading', 'content' => 'Ons vakantierooster'],
            ]),
        ]);

        $results = $svc->searchPages('vakantie');

        $this->assertCount(1, $results);
        $this->assertSame(5, $results[0]['score']);
        $this->assertSame('heading', $results[0]['matches'][0]['type']);
    }

    public function testTextWidgetContentScoresThree(): void {
        $svc = $this->makeService([
            $this->page('page-a', 'Onbekend', [
                ['type' => 'text', 'content' => 'Alles over de vakantie hier.'],
            ]),
        ]);

        $results = $svc->searchPages('vakantie');

        $this->assertCount(1, $results);
        $this->assertSame(3, $results[0]['score']);
        $this->assertSame('content', $results[0]['matches'][0]['type']);
    }

    public function testScoresFromTitleAndWidgetsAccumulate(): void {
        // title(+10) + heading(+5) + text(+3) = 18, plus 3 match descriptors.
        $svc = $this->makeService([
            $this->page('page-a', 'Vakantie', [
                ['type' => 'heading', 'content' => 'Vakantie rooster'],
                ['type' => 'text', 'content' => 'Vakantie uitleg'],
            ]),
        ]);

        $results = $svc->searchPages('vakantie');

        $this->assertSame(18, $results[0]['score']);
        $this->assertSame(3, $results[0]['matchCount']);
    }

    // ------------------------------------------------------------ filtering & shape

    public function testPagesWithoutAUniqueIdAreSkipped(): void {
        $svc = $this->makeService([
            ['title' => 'Vakantie zonder id', 'layout' => ['rows' => []]],
            $this->page('page-b', 'Vakantie met id'),
        ]);

        $results = $svc->searchPages('vakantie');

        $this->assertCount(1, $results, 'the id-less page must be skipped even though it matches');
        $this->assertSame('page-b', $results[0]['uniqueId']);
    }

    public function testPagesWithZeroScoreAreExcluded(): void {
        $svc = $this->makeService([
            $this->page('page-a', 'Vakantiebeleid'),
            $this->page('page-b', 'Iets heel anders'),
        ]);

        $results = $svc->searchPages('vakantie');

        $this->assertCount(1, $results, 'only pages with a positive score appear');
        $this->assertSame('page-a', $results[0]['uniqueId']);
    }

    public function testEmptyResultWhenNothingMatches(): void {
        $svc = $this->makeService([$this->page('page-a', 'Iets anders')]);

        $this->assertSame([], $svc->searchPages('vakantie'));
    }

    public function testMatchesAreCappedAtThreeButMatchCountIsTheRealTotal(): void {
        // title + 3 heading widgets = 4 match descriptors; matches[] keeps 3,
        // matchCount reports 4.
        $svc = $this->makeService([
            $this->page('page-a', 'Vakantie', [
                ['type' => 'heading', 'content' => 'Vakantie een'],
                ['type' => 'heading', 'content' => 'Vakantie twee'],
                ['type' => 'heading', 'content' => 'Vakantie drie'],
            ]),
        ]);

        $results = $svc->searchPages('vakantie');

        $this->assertCount(3, $results[0]['matches'], 'matches are capped at 3');
        $this->assertSame(4, $results[0]['matchCount'], 'matchCount is the uncapped total');
    }

    public function testResultShapeCarriesTitlePathAndScore(): void {
        $svc = $this->makeService([$this->page('page-a', 'Vakantie', [], '/nl/vakantie')]);

        $results = $svc->searchPages('vakantie');

        $this->assertSame('page-a', $results[0]['uniqueId']);
        $this->assertSame('Vakantie', $results[0]['title']);
        $this->assertSame('/nl/vakantie', $results[0]['path']);
        $this->assertArrayHasKey('score', $results[0]);
        $this->assertArrayHasKey('matchCount', $results[0]);
    }

    // ------------------------------------------------------------ sort & limit

    public function testResultsAreSortedByScoreDescending(): void {
        $svc = $this->makeService([
            // uniqueId-only hit (score 5)
            $this->page('page-vakantie', 'Niks'),
            // title hit (score 10)
            $this->page('page-b', 'Vakantie'),
            // title + heading (score 15)
            $this->page('page-c', 'Vakantie', [['type' => 'heading', 'content' => 'Vakantie']]),
        ]);

        $results = $svc->searchPages('vakantie');

        $this->assertSame(['page-c', 'page-b', 'page-vakantie'], array_column($results, 'uniqueId'));
        $this->assertSame([15, 10, 5], array_column($results, 'score'));
    }

    public function testResultsAreLimitedToTwenty(): void {
        $pages = [];
        for ($i = 0; $i < 25; $i++) {
            $pages[] = $this->page('page-' . $i, 'Vakantie ' . $i);
        }
        $svc = $this->makeService($pages);

        $results = $svc->searchPages('vakantie');

        $this->assertCount(20, $results, 'the result set is capped at the top 20');
    }
}
