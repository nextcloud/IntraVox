<?php
declare(strict_types=1);

namespace OCA\IntraVox\Tests\Unit\Service;

use PHPUnit\Framework\TestCase;

/**
 * Structural guard: the TRANSLATE-query carve keeps the cross-domain shared
 * seams OUT of TranslationQueryService.
 *
 * writeTranslationGroup is shared with the compose-domain createTranslation, and
 * resolveTranslations lives on the hot #70-adjacent read/enrich path — both stay
 * resident on PageService and reach TranslationQueryService only as bound
 * closures. getUserLanguage is reached only inside the resident writeTranslation
 * -Group. If a future edit reconstructs any of them inline here, it would pull a
 * symbol shared across a domain boundary into a query service — the tangle this
 * carve avoids. This test fails the moment such a symbol is referenced.
 *
 * Scoped to the single carved file: the sibling TranslationGroupService engine
 * legitimately DEFINES writeGroup/resolveTranslations, so a dir-wide grep would
 * false-positive on it.
 */
class TranslationQueryIsolationTest extends TestCase {

    public function testTranslationQueryServiceDoesNotReachTheResidentSeams(): void {
        $file = __DIR__ . '/../../../lib/Service/Translation/TranslationQueryService.php';
        $this->assertFileExists($file);

        $src = file_get_contents($file);
        $this->assertIsString($src);

        $forbidden = [
            'writeTranslationGroup',
            'resolveTranslations',
            'getUserLanguage',
        ];
        foreach ($forbidden as $symbol) {
            $this->assertStringNotContainsString(
                $symbol,
                $src,
                "TranslationQueryService must not reach the resident seam '$symbol'"
                    . ' — it arrives as a bound closure from PageService instead'
            );
        }
    }
}
