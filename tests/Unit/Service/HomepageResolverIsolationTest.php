<?php
declare(strict_types=1);

namespace OCA\IntraVox\Tests\Unit\Service;

use PHPUnit\Framework\TestCase;

/**
 * Structural guard: the HOMEPAGE carve keeps the shared/pinned resident lookups
 * OUT of HomepageResolverService.
 *
 * findPageByUniqueId (17 refs), getLanguageFolderByCode (reflection-anchored +
 * buildPageTree caller), getCachedFileContent, resolveEffectiveLanguage,
 * getUserLanguage, clearCache (private), and the isHomepage public seam (6
 * subclass overrides + 4 carved-service bindings) all stay resident on
 * PageService and reach this service only as bound closures (named locatePage /
 * languageFolderByCode / cachedFileContent / effectiveLanguage / userLanguage /
 * homepagePredicate / invalidateCache). If a future edit reconstructs any of
 * them inline here, it would pull a shared/pinned symbol into a homepage service
 * — the tangle this carve avoids. This test fails the moment such a symbol is
 * referenced from the carved file.
 *
 * The HomepageService pointer engine is legitimately used, so it is not
 * forbidden here.
 */
class HomepageResolverIsolationTest extends TestCase {

    public function testHomepageResolverDoesNotReachResidentLookups(): void {
        $file = __DIR__ . '/../../../lib/Service/Homepage/HomepageResolverService.php';
        $this->assertFileExists($file);

        $src = file_get_contents($file);
        $this->assertIsString($src);

        $forbidden = [
            'findPageByUniqueId',
            'getLanguageFolderByCode',
            'getCachedFileContent',
            'resolveEffectiveLanguage',
            'getUserLanguage',
            'isHomepage',
            'clearCache',
        ];
        foreach ($forbidden as $symbol) {
            $this->assertStringNotContainsString(
                $symbol,
                $src,
                "HomepageResolverService must not reach the resident lookup '$symbol'"
                    . ' — it arrives as a bound closure from PageService instead'
            );
        }
    }
}
