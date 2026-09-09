<?php
declare(strict_types=1);

namespace OCA\IntraVox\Tests\Unit\Service;

use PHPUnit\Framework\TestCase;

/**
 * Structural guard: the LANGUAGE-STATUS carve keeps the homepage-resolution
 * subsystem OUT of lib/Service/Language/.
 *
 * getLanguageContentStatus reaches homepage resolution
 * (resolveHomepageNodeUniqueId -> getHomepageUniqueId -> findPageByUniqueId ->
 * isHomepage) and the real-content probe only through closures bound on
 * PageService. If a future edit "simplifies" the service by reconstructing any
 * of that machinery inline, it would drag a subsystem shared with isHomepage()
 * and ApiController into a status reader — exactly the tangle the carve avoids.
 * This test fails the moment such a symbol is referenced from the namespace.
 */
class LanguageStatusIsolationTest extends TestCase {

    public function testLanguageNamespaceDoesNotReachTheHomepageSubsystem(): void {
        $dir = __DIR__ . '/../../../lib/Service/Language';
        $this->assertDirectoryExists($dir, 'the LANGUAGE-STATUS domain directory must exist');

        $forbidden = [
            'homepageService',
            'resolveHomepageNodeUniqueId',
            'getHomepageUniqueId',
            'findPageByUniqueId',
            'getLanguageFolderByCode',
            'isHomepage',
        ];

        foreach (glob($dir . '/*.php') as $file) {
            $src = file_get_contents($file);
            $this->assertIsString($src);
            foreach ($forbidden as $symbol) {
                $this->assertStringNotContainsString(
                    $symbol,
                    $src,
                    basename($file) . ' must not reach the homepage-resolution subsystem'
                        . " ('$symbol'); it arrives as a bound closure from PageService instead"
                );
            }
        }
    }
}
