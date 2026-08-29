<?php
declare(strict_types=1);

namespace OCA\IntraVox\Tests\Unit\Service\Import;

use OCA\IntraVox\Service\ImportService;
use PHPUnit\Framework\TestCase;

/**
 * Turning an imported page title into a folder name.
 *
 * slugify() decides what a page is CALLED ON DISK, from a title that came out of
 * someone else's export. It was untested, which is uncomfortable for a function
 * whose output becomes a path segment: everything it fails to strip ends up in
 * a filename.
 *
 * Reflection because the method is private and pure. Making it public to test it
 * would widen the surface of a class that is otherwise all orchestration.
 */
class ImportSlugTest extends TestCase {
    private \ReflectionMethod $slugify;

    protected function setUp(): void {
        parent::setUp();
        $class = new \ReflectionClass(ImportService::class);
        $this->slugify = $class->getMethod('slugify');
    }

    private function slug(string $text): string {
        // The method touches no collaborators, so the constructor can be skipped.
        return $this->slugify->invoke(
            (new \ReflectionClass(ImportService::class))->newInstanceWithoutConstructor(),
            $text
        );
    }

    public function testAnOrdinaryTitleBecomesALowercaseHyphenatedSlug(): void {
        $this->assertSame('over-intravox', $this->slug('Over IntraVox'));
        $this->assertSame('jaarverslag-2026', $this->slug('Jaarverslag 2026'));
    }

    /** Dutch and German characters must survive as something readable. */
    public function testAccentedCharactersAreTransliteratedNotDropped(): void {
        $this->assertSame('uebersicht', $this->slug('Übersicht'));
        $this->assertSame('strasse', $this->slug('Straße'));
        $this->assertSame('reunion', $this->slug('Réunion'));
        $this->assertSame('espana', $this->slug('España'));
    }

    public function testAmpersandAndAtBecomeWords(): void {
        $this->assertSame('sales-and-support', $this->slug('Sales & Support'));
        $this->assertSame('mail-at-ons', $this->slug('Mail @ ons'));
    }

    /**
     * The one that matters: a title is attacker-controlled on an import, and the
     * result becomes a path segment. Nothing that steers a path may survive.
     */
    public function testNothingThatSteersAPathSurvives(): void {
        foreach ([
            '../../../etc/passwd',
            '..\\..\\windows\\system32',
            'map/submap',
            'C:\\Windows',
            '.hidden',
            'page.json',
        ] as $hostile) {
            $slug = $this->slug($hostile);

            $this->assertStringNotContainsString('/', $slug, "'$hostile' liet een / achter");
            $this->assertStringNotContainsString('\\', $slug, "'$hostile' liet een \\ achter");
            $this->assertStringNotContainsString('..', $slug, "'$hostile' liet .. achter");
            $this->assertStringNotContainsString('.', $slug, "'$hostile' liet een punt achter");
            $this->assertNotSame('', $slug);
        }
    }

    public function testControlCharactersAndNullBytesAreStripped(): void {
        $this->assertSame('abc', $this->slug("a\x00b\x07c"));
        // A newline is whitespace, so it becomes a separator rather than
        // disappearing -- which is what you want for "Titel\nSubtitel".
        $this->assertSame('a-b', $this->slug("a\nb"));
    }

    /** A title with nothing usable still has to produce a valid folder name. */
    public function testATitleWithNoUsableCharactersFallsBackToADefault(): void {
        foreach (['', '---', '???', '。。。', '   '] as $unusable) {
            $this->assertSame('page', $this->slug($unusable), "'$unusable' moet terugvallen");
        }
    }

    public function testHyphensAreCollapsedAndTrimmed(): void {
        $this->assertSame('a-b', $this->slug('  a   ---   b  '));
        $this->assertSame('test', $this->slug('---test---'));
    }

    /** Whatever comes out is safe to use as a single path segment. */
    public function testTheResultIsAlwaysASafeSingleSegment(): void {
        foreach ([
            'Over IntraVox', 'Übersicht', '../etc', 'Sales & Support',
            '', 'page.json', "a\x00b", '???',
        ] as $input) {
            $this->assertMatchesRegularExpression(
                '/^[a-z0-9]+(-[a-z0-9]+)*$/',
                $this->slug($input),
                "'$input' produceerde geen veilig segment"
            );
        }
    }
}
