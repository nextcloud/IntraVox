<?php
declare(strict_types=1);

namespace OCA\IntraVox\Tests\Unit\Service\Search;

use OCA\IntraVox\Service\Publication\MetaVoxGateway;
use OCA\IntraVox\Service\Search\PageSearchEngine;
use OCA\IntraVox\Service\Search\PageSearchHelper;
use PHPUnit\Framework\TestCase;

/**
 * Behavioural coverage of PageSearchEngine::search() — its scoring weights,
 * match-collection, sorting and limits.
 *
 * Migrated from the fase-10 PageSearchTest, which pinned exactly this behaviour
 * THROUGH the retired PageService::searchPages facade (searchEngine()->search(
 * pageLister->listAllWithContent(), q)). The facade only fed the engine the page
 * list; the scoring IS the engine. So this builds the engine directly and feeds
 * the fixture pages straight to search() — no PageLister walk needed. The real
 * final PageSearchHelper runs the widget scoring; the MetaVox gateway is an inert
 * double so the content-scoring branch is tested in isolation (no fixture uses a
 * MetaVox field). Assertions are byte-identical to the retired facade test.
 */
class PageSearchEngineTest extends TestCase {

    /**
     * A PageSearchEngine over the real PageSearchHelper and an inert MetaVox gateway
     * that contributes no metadata matches, so only title/uniqueId/widget scoring runs.
     */
    private function engine(): PageSearchEngine {
        $metaVox = $this->createMock(MetaVoxGateway::class);
        $metaVox->method('getMetaVoxDataForFiles')->willReturn([]);
        $metaVox->method('getMetaVoxFieldLabels')->willReturn([]);
        $metaVox->method('searchMetaVoxValues')->willReturn([]);
        $metaVox->method('groupfolderIdForFile')->willReturn(null);

        return new PageSearchEngine(new PageSearchHelper(), $metaVox);
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
        $results = $this->engine()->search([$this->page('page-a', 'Vakantiebeleid')], 'vakantie');

        $this->assertCount(1, $results);
        $this->assertSame('page-a', $results[0]['uniqueId']);
        $this->assertSame(10, $results[0]['score']);
        $this->assertSame('title', $results[0]['matches'][0]['type']);
        $this->assertSame('Vakantiebeleid', $results[0]['matches'][0]['text']);
    }

    public function testTitleMatchIsCaseInsensitive(): void {
        $results = $this->engine()->search([$this->page('page-a', 'VAKANTIEbeleid')], 'Vakantie');

        $this->assertCount(1, $results);
        $this->assertSame(10, $results[0]['score']);
    }

    public function testUniqueIdMatchScoresFiveWithoutAddingAMatchEntry(): void {
        // Query hits the uniqueId but neither the title nor any widget.
        $results = $this->engine()->search([$this->page('page-vakantie', 'Onbekend')], 'vakantie');

        $this->assertCount(1, $results);
        $this->assertSame(5, $results[0]['score']);
        $this->assertSame(0, $results[0]['matchCount'], 'a uniqueId hit scores but adds no match descriptor');
        $this->assertSame([], $results[0]['matches']);
    }

    public function testHeadingWidgetContentScoresFive(): void {
        $results = $this->engine()->search([
            $this->page('page-a', 'Onbekend', [
                ['type' => 'heading', 'content' => 'Ons vakantierooster'],
            ]),
        ], 'vakantie');

        $this->assertCount(1, $results);
        $this->assertSame(5, $results[0]['score']);
        $this->assertSame('heading', $results[0]['matches'][0]['type']);
    }

    public function testTextWidgetContentScoresThree(): void {
        $results = $this->engine()->search([
            $this->page('page-a', 'Onbekend', [
                ['type' => 'text', 'content' => 'Alles over de vakantie hier.'],
            ]),
        ], 'vakantie');

        $this->assertCount(1, $results);
        $this->assertSame(3, $results[0]['score']);
        $this->assertSame('content', $results[0]['matches'][0]['type']);
    }

    public function testScoresFromTitleAndWidgetsAccumulate(): void {
        // title(+10) + heading(+5) + text(+3) = 18, plus 3 match descriptors.
        $results = $this->engine()->search([
            $this->page('page-a', 'Vakantie', [
                ['type' => 'heading', 'content' => 'Vakantie rooster'],
                ['type' => 'text', 'content' => 'Vakantie uitleg'],
            ]),
        ], 'vakantie');

        $this->assertSame(18, $results[0]['score']);
        $this->assertSame(3, $results[0]['matchCount']);
    }

    // ------------------------------------------------------------ filtering & shape

    public function testPagesWithoutAUniqueIdAreSkipped(): void {
        $results = $this->engine()->search([
            ['title' => 'Vakantie zonder id', 'layout' => ['rows' => []]],
            $this->page('page-b', 'Vakantie met id'),
        ], 'vakantie');

        $this->assertCount(1, $results, 'the id-less page must be skipped even though it matches');
        $this->assertSame('page-b', $results[0]['uniqueId']);
    }

    public function testPagesWithZeroScoreAreExcluded(): void {
        $results = $this->engine()->search([
            $this->page('page-a', 'Vakantiebeleid'),
            $this->page('page-b', 'Iets heel anders'),
        ], 'vakantie');

        $this->assertCount(1, $results, 'only pages with a positive score appear');
        $this->assertSame('page-a', $results[0]['uniqueId']);
    }

    public function testEmptyResultWhenNothingMatches(): void {
        $this->assertSame([], $this->engine()->search([$this->page('page-a', 'Iets anders')], 'vakantie'));
    }

    public function testMatchesAreCappedAtThreeButMatchCountIsTheRealTotal(): void {
        // title + 3 heading widgets = 4 match descriptors; matches[] keeps 3,
        // matchCount reports 4.
        $results = $this->engine()->search([
            $this->page('page-a', 'Vakantie', [
                ['type' => 'heading', 'content' => 'Vakantie een'],
                ['type' => 'heading', 'content' => 'Vakantie twee'],
                ['type' => 'heading', 'content' => 'Vakantie drie'],
            ]),
        ], 'vakantie');

        $this->assertCount(3, $results[0]['matches'], 'matches are capped at 3');
        $this->assertSame(4, $results[0]['matchCount'], 'matchCount is the uncapped total');
    }

    public function testResultShapeCarriesTitlePathAndScore(): void {
        $results = $this->engine()->search([$this->page('page-a', 'Vakantie', [], '/nl/vakantie')], 'vakantie');

        $this->assertSame('page-a', $results[0]['uniqueId']);
        $this->assertSame('Vakantie', $results[0]['title']);
        $this->assertSame('/nl/vakantie', $results[0]['path']);
        $this->assertArrayHasKey('score', $results[0]);
        $this->assertArrayHasKey('matchCount', $results[0]);
    }

    // ------------------------------------------------------------ sort & limit

    public function testResultsAreSortedByScoreDescending(): void {
        $results = $this->engine()->search([
            // uniqueId-only hit (score 5)
            $this->page('page-vakantie', 'Niks'),
            // title hit (score 10)
            $this->page('page-b', 'Vakantie'),
            // title + heading (score 15)
            $this->page('page-c', 'Vakantie', [['type' => 'heading', 'content' => 'Vakantie']]),
        ], 'vakantie');

        $this->assertSame(['page-c', 'page-b', 'page-vakantie'], array_column($results, 'uniqueId'));
        $this->assertSame([15, 10, 5], array_column($results, 'score'));
    }

    public function testResultsAreLimitedToTwenty(): void {
        $pages = [];
        for ($i = 0; $i < 25; $i++) {
            $pages[] = $this->page('page-' . $i, 'Vakantie ' . $i);
        }

        $results = $this->engine()->search($pages, 'vakantie');

        $this->assertCount(20, $results, 'the result set is capped at the top 20');
    }
}
