<?php
declare(strict_types=1);

namespace OCA\IntraVox\Tests\Unit\Service\Language;

use OCA\IntraVox\Service\Language\LanguageResolver;
use PHPUnit\Framework\TestCase;

/**
 * The folder-free half of PageService language resolution, extracted in Phase 9.
 *
 * These cases mirror the branches the original getUserLanguage() /
 * resolveEffectiveLanguage() bodies had, so this file is the direct unit-level
 * proof for the two methods PageService now delegates to. The end-to-end proof
 * that PageService still behaves identically lives in PageLanguageResolutionTest.
 */
class LanguageResolverTest extends TestCase {

    private LanguageResolver $resolver;

    protected function setUp(): void {
        $this->resolver = new LanguageResolver();
    }

    // ---------------------------------------------------------- baseLanguageCode

    public function testNullProfileValueFallsBackToDefault(): void {
        // null == "logged out / no value" — same outcome as an empty string.
        $this->assertSame('en', $this->resolver->baseLanguageCode(null));
    }

    public function testEmptyProfileValueFallsBackToDefault(): void {
        $this->assertSame('en', $this->resolver->baseLanguageCode(''));
    }

    public function testLocaleIsReducedToItsBaseCode(): void {
        $this->assertSame('nl', $this->resolver->baseLanguageCode('nl_NL'));
    }

    public function testPlainTwoLetterCodeIsKept(): void {
        $this->assertSame('de', $this->resolver->baseLanguageCode('de'));
    }

    public function testThreeLetterCodeIsAllowed(): void {
        // The guard regex is /^[a-z]{2,3}$/ — a 3-letter code like Filipino passes.
        $this->assertSame('fil', $this->resolver->baseLanguageCode('fil'));
    }

    public function testThreeLetterLocaleIsReducedToItsBaseCode(): void {
        $this->assertSame('fil', $this->resolver->baseLanguageCode('fil_PH'));
    }

    /**
     * @dataProvider malformedProvider
     */
    public function testMalformedValuesFallBackToDefault(string $raw): void {
        $this->assertSame(
            'en',
            $this->resolver->baseLanguageCode($raw),
            "'$raw' should fail the [a-z]{2,3} guard and fall back to en"
        );
    }

    public static function malformedProvider(): array {
        return [
            'single letter'   => ['x'],
            'four letters'    => ['toolong'],
            'digits'          => ['1234'],
            'uppercase'       => ['NL'],
            'mixed case'      => ['En'],
            'letter+digit'    => ['e2'],
            'leading digit'   => ['2e'],
            'locale of digits'=> ['12_34'],
        ];
    }

    // ---------------------------------------------------------- candidateOrder

    public function testUserPrimaryAndDefaultAllEnglishCollapsesToSingleEntry(): void {
        $this->assertSame(['en'], $this->resolver->candidateOrder('en', 'en'));
    }

    public function testUserWithEnglishPrimaryYieldsUserThenEnglish(): void {
        $this->assertSame(['nl', 'en'], $this->resolver->candidateOrder('nl', 'en'));
    }

    public function testUserPrimaryAndDefaultAllDistinctPreservesOrder(): void {
        // user nl, primary de, then the en floor appended.
        $this->assertSame(['nl', 'de', 'en'], $this->resolver->candidateOrder('nl', 'de'));
    }

    public function testEnglishUserWithDistinctPrimaryDoesNotDuplicateEnglish(): void {
        // user en, primary de: en is already present, so it is NOT appended again.
        $this->assertSame(['en', 'de'], $this->resolver->candidateOrder('en', 'de'));
    }

    public function testPrimaryEqualToUserIsNotDuplicated(): void {
        // primary == user (nl) — the primary branch is skipped, en floor appended.
        $this->assertSame(['nl', 'en'], $this->resolver->candidateOrder('nl', 'nl'));
    }

    public function testUserEqualsDefaultButPrimaryDiffersKeepsEnglishFirst(): void {
        // user en, primary nl: nl appended (differs), en already there → [en, nl].
        $this->assertSame(['en', 'nl'], $this->resolver->candidateOrder('en', 'nl'));
    }
}
