<?php
declare(strict_types=1);

namespace OCA\IntraVox\Tests\Unit\Service\People;

use OCA\IntraVox\Service\People\ProfileFilterMatcher;
use PHPUnit\Framework\TestCase;

/**
 * Whether a person matches a filter row.
 *
 * These were private helpers on UserService, reachable only with a user backend
 * wired up. Extracting them made the operator semantics testable directly —
 * including the two birthday operators, which are the ones most likely to be
 * "simplified" by someone who has not noticed they ignore the year.
 */
class ProfileFilterMatcherTest extends TestCase {
    private ProfileFilterMatcher $matcher;

    protected function setUp(): void {
        parent::setUp();
        $this->matcher = new ProfileFilterMatcher();
    }

    private function match(mixed $actual, string $op, mixed $filter): bool {
        return $this->matcher->matchesFilters(
            ['veld' => $actual],
            [['fieldName' => 'veld', 'operator' => $op, 'value' => $filter]],
            'AND'
        );
    }

    public function testEqualsComparesStrictlyOnScalars(): void {
        $this->assertTrue($this->match('Sales', 'equals', 'Sales'));
        $this->assertFalse($this->match('Sales', 'equals', 'sales'), 'equals is case-sensitive');
    }

    /** For a multi-value field like groups, "equals" means "is among them". */
    public function testEqualsOnAnArrayMeansMembership(): void {
        $this->assertTrue($this->match(['Sales', 'Support'], 'equals', 'Support'));
        $this->assertFalse($this->match(['Sales', 'Support'], 'equals', 'Finance'));
    }

    public function testContainsIsCaseInsensitive(): void {
        $this->assertTrue($this->match('Hoofd Verkoop', 'contains', 'verkoop'));
        $this->assertFalse($this->match('Hoofd Verkoop', 'contains', 'inkoop'));
    }

    /** not_contains is true for a non-string, which is what makes it "not". */
    public function testNotContainsInvertsAndDefaultsToTrue(): void {
        $this->assertFalse($this->match('Hoofd Verkoop', 'not_contains', 'verkoop'));
        $this->assertTrue($this->match('Hoofd Verkoop', 'not_contains', 'inkoop'));
        $this->assertTrue($this->match(null, 'not_contains', 'x'));
    }

    /**
     * The case from issue #108: show Domain Users, minus Board Members.
     * On a list-valued field "does not equal" has to mean "is in none of
     * them" — an implementation that returns true as soon as one entry
     * differs would let every excluded user straight through.
     */
    public function testNotEqualsOnAnArrayMeansMembershipInNone(): void {
        $groups = ['Domain Users', 'Board Members'];

        $this->assertFalse($this->match($groups, 'not_equals', 'Board Members'));
        $this->assertTrue($this->match($groups, 'not_equals', 'Service accounts'));
        $this->assertTrue($this->match([], 'not_equals', 'Board Members'));
    }

    public function testNotEqualsIsTheExactInverseOfEqualsOnScalars(): void {
        foreach ([['Sales', 'Sales'], ['Sales', 'sales'], ['Sales', 'Support']] as [$actual, $expected]) {
            $this->assertSame(
                !$this->match($actual, 'equals', $expected),
                $this->match($actual, 'not_equals', $expected),
                "not_equals must invert equals for {$actual}/{$expected}"
            );
        }
    }

    public function testNotInExcludesEveryListedValue(): void {
        $groups = ['Domain Users', 'Service accounts'];

        $this->assertFalse($this->match($groups, 'not_in', ['Board Members', 'Service accounts']));
        $this->assertTrue($this->match($groups, 'not_in', ['Board Members', 'Contractors']));
        $this->assertFalse($this->match('Sales', 'not_in', ['Sales', 'Support']));
        $this->assertTrue($this->match('Sales', 'not_in', ['Support']));
    }

    public function testInAcceptsBothScalarAndArrayValues(): void {
        $this->assertTrue($this->match('Sales', 'in', ['Sales', 'Finance']));
        $this->assertFalse($this->match('HR', 'in', ['Sales', 'Finance']));
        $this->assertTrue($this->match(['HR', 'Sales'], 'in', ['Sales']), 'any overlap counts');
    }

    public function testEmptyAndNotEmptyHandleStringsAndArrays(): void {
        $this->assertTrue($this->match('', 'empty', null));
        $this->assertTrue($this->match(null, 'empty', null));
        $this->assertTrue($this->match([], 'empty', null));
        $this->assertFalse($this->match('x', 'empty', null));

        $this->assertTrue($this->match('x', 'not_empty', null));
        $this->assertTrue($this->match(['x'], 'not_empty', null));
        $this->assertFalse($this->match('', 'not_empty', null));
    }

    /**
     * The birthday operators compare month-day and IGNORE the year. That is the
     * whole point: someone born in 1980 has a birthday today, not in 1980.
     */
    public function testIsTodayIgnoresTheYear(): void {
        $todayLongAgo = (new \DateTime())->modify('-40 years')->format('Y-m-d');

        $this->assertTrue($this->match($todayLongAgo, 'is_today', null));
        $this->assertFalse($this->match('1980-01-01', 'is_today', null), 'unless it is actually today');
        $this->assertFalse($this->match('', 'is_today', null));
    }

    public function testWithinNextDaysLooksForwardAcrossTheYearBoundary(): void {
        $inThreeDays = (new \DateTime())->modify('+3 days')->format('Y-m-d');
        $longAgo = (new \DateTime())->modify('+3 days')->modify('-30 years')->format('Y-m-d');

        $this->assertTrue($this->match($inThreeDays, 'within_next_days', 7));
        $this->assertTrue($this->match($longAgo, 'within_next_days', 7), 'the year must not matter');
        $this->assertFalse($this->match($inThreeDays, 'within_next_days', 1));
    }

    public function testWithinNextDaysRefusesNonsenseInput(): void {
        $this->assertFalse($this->match('', 'within_next_days', 7));
        $this->assertFalse($this->match('2026-05-08', 'within_next_days', 'zeven'));
    }

    /** An unparseable date must not throw out of a list render. */
    public function testAnUnparseableDateIsSimplyNoMatch(): void {
        $this->assertFalse($this->match('volstrekte onzin', 'is_today', null));
        $this->assertFalse($this->match('volstrekte onzin', 'within_next_days', 7));
    }

    public function testAnUnknownOperatorMatchesNothing(): void {
        $this->assertFalse($this->match('x', 'verzonnen_operator', 'x'));
    }

    public function testAndRequiresEveryRowWhileOrRequiresOne(): void {
        $profile = ['afdeling' => 'Sales', 'functie' => 'Manager'];
        $filters = [
            ['fieldName' => 'afdeling', 'operator' => 'equals', 'value' => 'Sales'],
            ['fieldName' => 'functie', 'operator' => 'equals', 'value' => 'Stagiair'],
        ];

        $this->assertFalse($this->matcher->matchesFilters($profile, $filters, 'AND'));
        $this->assertTrue($this->matcher->matchesFilters($profile, $filters, 'OR'));
    }

    public function testNoFiltersMatchesEveryone(): void {
        $this->assertTrue($this->matcher->matchesFilters(['a' => 1], [], 'AND'));
    }

    public function testFreeTextSearchesTheNamedFields(): void {
        $rows = [
            ['displayName' => 'Anna Jansen', 'functie' => 'Manager'],
            ['displayName' => 'Bob de Vries', 'functie' => 'Ontwikkelaar'],
        ];

        $hit = $this->matcher->applyFreeText($rows, 'ontwikkel', ['functie']);
        $this->assertCount(1, $hit);
        $this->assertSame('Bob de Vries', $hit[0]['displayName']);
    }

    /**
     * A free-text box next to a people list is expected to find people by name,
     * whatever fields the widget was configured with.
     */
    public function testFreeTextAlwaysMatchesTheNameToo(): void {
        $rows = [['displayName' => 'Anna Jansen', 'functie' => 'Manager']];

        $this->assertCount(1, $this->matcher->applyFreeText($rows, 'anna', ['functie']));
    }

    public function testAnEmptyQueryReturnsEverythingUnchanged(): void {
        $rows = [['displayName' => 'Anna'], ['displayName' => 'Bob']];

        $this->assertSame($rows, $this->matcher->applyFreeText($rows, '   ', ['displayName']));
    }

    public function testFreeTextIsCaseInsensitiveAndSearchesInsideArrays(): void {
        $rows = [['displayName' => 'Anna', 'groups' => ['Sales', 'Support']]];

        $this->assertCount(1, $this->matcher->applyFreeText($rows, 'SUPP', ['groups']));
    }
}
