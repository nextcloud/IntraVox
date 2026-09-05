<?php
declare(strict_types=1);

namespace OCA\IntraVox\Tests\Unit\Service\News;

use OCA\IntraVox\Service\News\NewsPageService;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

/**
 * Matching a news page against a MetaVox filter row.
 *
 * MetaVox stores a multiselect as one ';#'-joined string ("Nieuws;#Intern") --
 * its own API does implode(';#', $value) on write and explodes on every read.
 * IntraVox reads the metavox_file_gf_meta table directly, so it has to decode
 * that itself; it did not, and the array branches here were unreachable.
 *
 * That produced three failures at once (#111): 'contains', the editor's default
 * operator for a multiselect field, fed an array into str_contains() and
 * returned a 500, while 'in' and 'contains_all' silently returned false, so a
 * correctly configured widget showed "no news". These tests pin all three, plus
 * the text/number/checkbox behaviour they must not disturb.
 */
class NewsMetaVoxFilterTest extends TestCase {
    private NewsPageService $service;

    protected function setUp(): void {
        parent::setUp();
        // matchesFilter() touches no collaborators, so the service does not
        // need its constructor wired up for these.
        $this->service = (new ReflectionClass(NewsPageService::class))
            ->newInstanceWithoutConstructor();
    }

    /**
     * The reported crash: a single-choice field with several options ticked.
     * The editor sends values[], which used to reach str_contains() as an
     * array needle -- a TypeError on PHP 8.
     */
    public function testContainsWithSeveralChosenOptionsDoesNotCrash(): void {
        $this->assertTrue($this->service->matchesFilter('Nieuws', 'contains', ['Nieuws', 'Intern']));
        $this->assertFalse($this->service->matchesFilter('Beleid', 'contains', ['Nieuws', 'Intern']));
    }

    public function testContainsMatchesAnyChosenOptionOfAMultiselect(): void {
        $stored = 'Nieuws;#Intern';

        $this->assertTrue($this->service->matchesFilter($stored, 'contains', ['Intern']));
        $this->assertTrue($this->service->matchesFilter($stored, 'contains', ['Nieuws', 'Intern']));
        $this->assertFalse($this->service->matchesFilter($stored, 'contains', ['Beleid']));
    }

    public function testNotContainsIsTheMirrorOfContains(): void {
        $stored = 'Nieuws;#Intern';

        $this->assertTrue($this->service->matchesFilter($stored, 'not_contains', ['Beleid']));
        $this->assertFalse($this->service->matchesFilter($stored, 'not_contains', ['Nieuws']));
    }

    /** 'in' means the multiselect holds any option from the allowed set. */
    public function testInMatchesOnOverlapWithTheAllowedSet(): void {
        $stored = 'Nieuws;#Intern';

        $this->assertTrue($this->service->matchesFilter($stored, 'in', ['Nieuws', 'Beleid']));
        $this->assertFalse($this->service->matchesFilter($stored, 'in', ['Beleid']));
        // A single-choice field keeps plain set membership.
        $this->assertTrue($this->service->matchesFilter('Nieuws', 'in', ['Nieuws', 'Intern']));
    }

    public function testContainsAllRequiresEveryChosenOption(): void {
        $stored = 'Nieuws;#Intern';

        $this->assertTrue($this->service->matchesFilter($stored, 'contains_all', ['Nieuws', 'Intern']));
        $this->assertFalse($this->service->matchesFilter($stored, 'contains_all', ['Nieuws', 'Beleid']));
    }

    /**
     * A text field has no separator in it and must keep matching on substring,
     * which is what "contains" means there.
     */
    public function testTextFieldKeepsSubstringMatching(): void {
        $this->assertTrue($this->service->matchesFilter('Jaarverslag 2026', 'contains', 'verslag'));
        $this->assertFalse($this->service->matchesFilter('Jaarverslag 2026', 'contains', 'notulen'));
        $this->assertTrue($this->service->matchesFilter('Jaarverslag 2026', 'not_contains', 'notulen'));
        $this->assertTrue($this->service->matchesFilter('Nieuws', 'equals', 'Nieuws'));
    }

    /** Date, number and checkbox operators read the raw stored value. */
    public function testOtherFieldTypesAreUntouched(): void {
        $this->assertTrue($this->service->matchesFilter('5', 'greater_than', '3'));
        $this->assertFalse($this->service->matchesFilter('2', 'greater_than', '3'));
        $this->assertTrue($this->service->matchesFilter('1', 'is_true', null));
        $this->assertTrue($this->service->matchesFilter('', 'empty', null));
        $this->assertTrue($this->service->matchesFilter('iets', 'not_empty', null));
    }

    /**
     * A filter row carries its choices in values[]; applyMetaVoxFilters() has to
     * hand that array to the matcher. not_contains was missing from the list
     * that does so, which made it fall back to an empty `value` -- and "does not
     * contain nothing" is true for every page, so the filter quietly did
     * nothing. Only reachable for text fields today, but silent-pass is the
     * worst failure mode a filter has.
     */
    public function testNotContainsReceivesTheChosenValues(): void {
        $pages = [
            ['id' => 'blauw', 'fileId' => 1],
            ['id' => 'groen', 'fileId' => 2],
        ];
        $meta = [
            1 => ['kleur' => 'Blue;#Red'],
            2 => ['kleur' => 'Green'],
        ];
        $filters = [['fieldName' => 'kleur', 'operator' => 'not_contains', 'values' => ['Blue']]];

        $matched = $this->service->applyMetaVoxFilters(
            $pages,
            $filters,
            'AND',
            static fn(array $fileIds): array => $meta
        );

        $this->assertSame(['groen'], array_values(array_column($matched, 'id')));
    }

    public function testEdgeCases(): void {
        // Nothing selected cannot match.
        $this->assertFalse($this->service->matchesFilter('Nieuws;#Intern', 'contains', []));
        $this->assertFalse($this->service->matchesFilter('', 'contains', ['Nieuws']));
        // A trailing separator leaves an empty token, which must be ignored.
        $this->assertTrue($this->service->matchesFilter('Nieuws;#', 'contains', ['Nieuws']));
    }
}
