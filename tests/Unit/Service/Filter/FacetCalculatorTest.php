<?php

declare(strict_types=1);

namespace OCA\IntraVox\Tests\Unit\Service\Filter;

use OCA\IntraVox\Service\Filter\FacetCalculator;
use OCA\IntraVox\Service\Filter\FilterSpec;
use PHPUnit\Framework\TestCase;

/**
 * Correctness proof for the facet engine.
 *
 * Two properties are under test and both are load-bearing:
 *   1. narrowing   — a viewer refinement can only ever shrink the result set
 *   2. disjunctive — a facet is counted excluding its own refinements
 */
class FacetCalculatorTest extends TestCase {
	/**
	 * A small fixed cohort. Counts are hand-checkable:
	 *   role:   Manager 4, Adviseur 3, Stagiair 2
	 *   gebouw: Noord 4, Zuid 3, Oost 2
	 *   thema:  multi-valued
	 */
	private static function cohort(): array {
		return [
			['uid' => 'u1', 'role' => 'Manager', 'gebouw' => 'Noord', 'thema' => ['Zorg', 'Wonen']],
			['uid' => 'u2', 'role' => 'Manager', 'gebouw' => 'Noord', 'thema' => ['Zorg']],
			['uid' => 'u3', 'role' => 'Manager', 'gebouw' => 'Zuid', 'thema' => ['Wonen']],
			['uid' => 'u4', 'role' => 'Manager', 'gebouw' => 'Oost', 'thema' => []],
			['uid' => 'u5', 'role' => 'Adviseur', 'gebouw' => 'Noord', 'thema' => ['Zorg']],
			['uid' => 'u6', 'role' => 'Adviseur', 'gebouw' => 'Zuid', 'thema' => ['Werk']],
			['uid' => 'u7', 'role' => 'Adviseur', 'gebouw' => 'Oost', 'thema' => ['Werk', 'Zorg']],
			['uid' => 'u8', 'role' => 'Stagiair', 'gebouw' => 'Noord', 'thema' => ['Wonen']],
			['uid' => 'u9', 'role' => 'Stagiair', 'gebouw' => 'Zuid', 'thema' => []],
		];
	}

	private static function refine(string $field, mixed $value, string $op = 'in'): array {
		return FilterSpec::normalizeRow(['field' => $field, 'op' => $op, 'value' => $value]);
	}

	/** @return array<string, int> value => count */
	private static function countsOf(array $facets, string $field): array {
		foreach ($facets as $facet) {
			if ($facet['field'] === $field) {
				$out = [];
				foreach ($facet['values'] as $entry) {
					$out[$entry['value']] = $entry['count'];
				}
				return $out;
			}
		}
		self::fail('facet not found: ' . $field);
	}

	/**
	 * Issue #108: exclude a group instead of only selecting one. On the
	 * list-valued `thema` field, "none of" is the meaning that matters —
	 * u7 holds both Werk and Zorg and must be excluded by either.
	 */
	public function testNotEqualsExcludesEveryRowHoldingTheValue(): void {
		$rows = FacetCalculator::applyFilters(self::cohort(), [self::refine('thema', 'Werk', 'not_equals')]);
		$uids = array_column($rows, 'uid');

		$this->assertNotContains('u7', $uids, 'a row holding the value must be excluded');
		$this->assertContains('u5', $uids, 'a row without it stays');
		$this->assertContains('u4', $uids, 'an empty list is "not it"');
	}

	public function testNotInExcludesRowsMatchingAnyListedValue(): void {
		$rows = FacetCalculator::applyFilters(self::cohort(), [self::refine('role', ['Stagiair', 'Manager'], 'not_in')]);
		$roles = array_unique(array_column($rows, 'role'));

		$this->assertNotContains('Stagiair', $roles);
		$this->assertNotContains('Manager', $roles);
		$this->assertContains('Adviseur', $roles);
	}

	/** Negation must partition the cohort exactly: nothing lost, nothing double. */
	public function testNegationPartitionsTheCohort(): void {
		$all = self::cohort();
		$in = FacetCalculator::applyFilters($all, [self::refine('role', 'Adviseur', 'equals')]);
		$out = FacetCalculator::applyFilters($all, [self::refine('role', 'Adviseur', 'not_equals')]);

		$this->assertCount(count($all), array_merge($in, $out));
		$this->assertSame([], array_intersect(array_column($in, 'uid'), array_column($out, 'uid')));
	}

	public function testUnrefinedCountsMatchNaiveTally(): void {
		$facets = FacetCalculator::compute(self::cohort(), ['role', 'gebouw']);

		$this->assertSame(['Manager' => 4, 'Adviseur' => 3, 'Stagiair' => 2], self::countsOf($facets, 'role'));
		$this->assertSame(['Noord' => 4, 'Zuid' => 3, 'Oost' => 2], self::countsOf($facets, 'gebouw'));
	}

	/**
	 * THE disjunctive-faceting assertion.
	 *
	 * With role=Manager selected, gebouw must narrow to managers only, while
	 * role itself keeps showing every role at its UNREFINED count so the user
	 * can switch or add a second role.
	 *
	 * If someone later "simplifies" the rule to count everything over the
	 * fully-filtered set, this test goes red. That is its entire purpose —
	 * do not relax it.
	 */
	public function testFacetIsNotConstrainedByItsOwnRefinement(): void {
		$refinements = [self::refine('role', ['Manager'])];

		$facets = FacetCalculator::compute(self::cohort(), ['role', 'gebouw'], $refinements);

		// gebouw IS constrained by role: managers live in Noord x2, Zuid, Oost.
		// Zuid and Oost tie on count, so they order alphabetically.
		$this->assertSame(['Noord' => 2, 'Oost' => 1, 'Zuid' => 1], self::countsOf($facets, 'gebouw'));

		// role is NOT constrained by itself — siblings stay selectable.
		$this->assertSame(['Manager' => 4, 'Adviseur' => 3, 'Stagiair' => 2], self::countsOf($facets, 'role'));
	}

	public function testMultiSelectWithinAFacetIsExpressible(): void {
		$refinements = [self::refine('gebouw', ['Noord', 'Zuid'])];

		$rows = FacetCalculator::applyFilters(self::cohort(), $refinements);
		$facets = FacetCalculator::compute(self::cohort(), ['gebouw'], $refinements);

		// Noord (4) + Zuid (3); the union, not an intersection.
		$this->assertCount(7, $rows);

		// Oost must still be listed with a live count, or the user could
		// never widen their selection.
		$this->assertSame(2, self::countsOf($facets, 'gebouw')['Oost']);
	}

	public function testTwoDifferentFacetsIntersect(): void {
		$refinements = [self::refine('role', ['Manager']), self::refine('gebouw', ['Noord'])];

		$rows = FacetCalculator::applyFilters(self::cohort(), $refinements);

		$this->assertCount(2, $rows);
		$this->assertSame(['u1', 'u2'], array_column($rows, 'uid'));
	}

	public function testSelectedValueSurvivesAtCountZero(): void {
		// Managers in Oost: exactly u4, who has no thema at all.
		$refinements = [self::refine('role', ['Manager']), self::refine('gebouw', ['Oost'])];

		$facets = FacetCalculator::compute(self::cohort(), ['gebouw'], $refinements);
		$gebouw = self::countsOf($facets, 'gebouw');

		$this->assertArrayHasKey('Oost', $gebouw, 'a selected value must never vanish from its facet');

		$entry = null;
		foreach ($facets[0]['values'] as $candidate) {
			if ($candidate['value'] === 'Oost') {
				$entry = $candidate;
			}
		}
		$this->assertNotNull($entry);
		$this->assertTrue($entry['selected']);
	}

	public function testSelectedValuesArePinnedFirst(): void {
		$refinements = [self::refine('gebouw', ['Oost'])];

		$facets = FacetCalculator::compute(self::cohort(), ['gebouw'], $refinements);

		$this->assertSame('Oost', $facets[0]['values'][0]['value']);
		$this->assertTrue($facets[0]['values'][0]['selected']);
	}

	/**
	 * A multi-valued field legitimately counts above the row count. Asserted
	 * so nobody "fixes" it into rendering a sum as a total.
	 */
	public function testMultiValuedFacetMayExceedRowCount(): void {
		$facets = FacetCalculator::compute(self::cohort(), ['thema']);
		$counts = self::countsOf($facets, 'thema');

		$this->assertSame(['Zorg' => 4, 'Wonen' => 3, 'Werk' => 2], $counts);

		// Two of the nine rows carry no thema, so seven rows contribute — yet
		// the counts sum to nine, because rows with several themes are counted
		// once per theme. This is why a sum of facet counts must never be
		// rendered as a total.
		$rowsWithAValue = count(array_filter(self::cohort(), static fn(array $r): bool => $r['thema'] !== []));

		$this->assertSame(7, $rowsWithAValue);
		$this->assertSame(9, array_sum($counts));
		$this->assertGreaterThan($rowsWithAValue, array_sum($counts));
	}

	public function testEmptyValuesAreNotCounted(): void {
		$facets = FacetCalculator::compute(self::cohort(), ['thema']);

		$this->assertArrayNotHasKey('', self::countsOf($facets, 'thema'));
	}

	public function testFacetLimitTruncatesAndFlags(): void {
		$facets = FacetCalculator::compute(self::cohort(), ['role'], [], [], 2);

		$this->assertCount(2, $facets[0]['values']);
		$this->assertTrue($facets[0]['truncated']);
	}

	public function testOrderIsCountDescThenLabelAsc(): void {
		$rows = [
			['x' => 'beta'], ['x' => 'beta'],
			['x' => 'alpha'], ['x' => 'alpha'],
			['x' => 'gamma'],
		];

		$facets = FacetCalculator::compute($rows, ['x']);

		$this->assertSame(['alpha', 'beta', 'gamma'], array_column($facets[0]['values'], 'value'));
	}

	// ---------------------------------------------------------------
	// Narrowing invariant
	// ---------------------------------------------------------------

	/**
	 * The single most important property of the whole design: a viewer
	 * refinement can only ever shrink what the widget already shows.
	 *
	 * Exhaustive over every combination of the three facets' values, rather
	 * than a handful of hand-picked cases.
	 */
	public function testRefinementNeverWidensTheResultSet(): void {
		$cohort = self::cohort();
		$baseline = FacetCalculator::applyFilters($cohort, []);
		$baselineUids = array_column($baseline, 'uid');

		$roleValues = [null, ['Manager'], ['Adviseur'], ['Manager', 'Adviseur'], ['Onbekend']];
		$gebouwValues = [null, ['Noord'], ['Zuid'], ['Noord', 'Oost'], ['Elders']];
		$themaValues = [null, ['Zorg'], ['Werk'], ['Zorg', 'Wonen']];

		$combinations = 0;

		foreach ($roleValues as $role) {
			foreach ($gebouwValues as $gebouw) {
				foreach ($themaValues as $thema) {
					$refinements = [];
					if ($role !== null) {
						$refinements[] = self::refine('role', $role);
					}
					if ($gebouw !== null) {
						$refinements[] = self::refine('gebouw', $gebouw);
					}
					if ($thema !== null) {
						$refinements[] = self::refine('thema', $thema);
					}

					$result = FacetCalculator::applyFilters($cohort, $refinements);
					$combinations++;

					$this->assertLessThanOrEqual(
						count($baseline),
						count($result),
						'refinement widened the result set'
					);

					foreach (array_column($result, 'uid') as $uid) {
						$this->assertContains(
							$uid,
							$baselineUids,
							'refinement produced a row outside the unrefined set'
						);
					}
				}
			}
		}

		$this->assertSame(100, $combinations);
	}

	/**
	 * Editor filters are a hard floor. A viewer refinement on the same field
	 * intersects with them; it never replaces them.
	 */
	public function testViewerCannotEscapeAnEditorFilterOnTheSameField(): void {
		$cohort = self::cohort();
		$editorFilters = [self::refine('gebouw', ['Noord'])];

		// This is what the controller does: the candidate set is built with
		// the editor filters already applied.
		$candidates = FacetCalculator::applyFilters($cohort, $editorFilters);
		$this->assertCount(4, $candidates);

		// The viewer asks for a different building entirely.
		$refined = FacetCalculator::applyFilters($candidates, [self::refine('gebouw', ['Zuid'])]);

		$this->assertSame([], $refined, 'viewer must not be able to reach outside the editor filter');
	}

	public function testViewerRefinementNarrowsWithinAnEditorFilter(): void {
		$cohort = self::cohort();
		$candidates = FacetCalculator::applyFilters($cohort, [self::refine('gebouw', ['Noord'])]);

		$refined = FacetCalculator::applyFilters($candidates, [self::refine('role', ['Manager'])]);

		$this->assertSame(['u1', 'u2'], array_column($refined, 'uid'));
	}

	/**
	 * Facet counts are computed over the candidate set, so they can never
	 * promise more rows than the widget could show.
	 */
	public function testFacetCountsNeverExceedTheCandidateSet(): void {
		$candidates = FacetCalculator::applyFilters(self::cohort(), [self::refine('gebouw', ['Noord'])]);

		$facets = FacetCalculator::compute($candidates, ['role']);

		foreach ($facets[0]['values'] as $entry) {
			$this->assertLessThanOrEqual(count($candidates), $entry['count']);
		}
	}

	/**
	 * The count shown on a facet value must equal the number of rows you get
	 * after clicking it. This is what a user actually verifies by eye.
	 */
	public function testClickingAFacetValueYieldsExactlyItsCount(): void {
		$cohort = self::cohort();

		foreach (['role', 'gebouw', 'thema'] as $field) {
			$facets = FacetCalculator::compute($cohort, [$field]);

			foreach ($facets[0]['values'] as $entry) {
				$rows = FacetCalculator::applyFilters($cohort, [self::refine($field, [$entry['value']])]);

				$this->assertCount(
					$entry['count'],
					$rows,
					sprintf('%s=%s advertised %d', $field, $entry['value'], $entry['count'])
				);
			}
		}
	}

	/**
	 * Same check, one level deeper: after a first refinement, the counts on a
	 * second facet must still predict the outcome exactly.
	 */
	public function testCountsRemainExactAfterAPriorRefinement(): void {
		$cohort = self::cohort();
		$first = [self::refine('role', ['Manager'])];

		$facets = FacetCalculator::compute($cohort, ['gebouw'], $first);

		foreach ($facets[0]['values'] as $entry) {
			$rows = FacetCalculator::applyFilters(
				$cohort,
				[...$first, self::refine('gebouw', [$entry['value']])]
			);

			$this->assertCount($entry['count'], $rows, 'gebouw=' . $entry['value']);
		}
	}

	public function testUnknownOperatorDoesNotWiden(): void {
		$rows = FacetCalculator::applyFilters(self::cohort(), [self::refine('role', 'Manager', 'wharrgarbl')]);

		$this->assertSame([], $rows);
	}

	public function testGroupFieldAliasesOntoGroupsKey(): void {
		$rows = [
			['uid' => 'a', 'groups' => ['hr', 'staff']],
			['uid' => 'b', 'groups' => ['ict']],
		];

		$facets = FacetCalculator::compute($rows, ['group']);
		$counts = self::countsOf($facets, 'group');

		$this->assertSame(1, $counts['hr']);
		$this->assertSame(1, $counts['ict']);

		$filtered = FacetCalculator::applyFilters($rows, [self::refine('group', ['hr'])]);
		$this->assertSame(['a'], array_column($filtered, 'uid'));
	}
}
