<?php
declare(strict_types=1);

namespace OCA\IntraVox\Tests\Unit\Service;

use PHPUnit\Framework\TestCase;

/**
 * Structural guard: the VERSION/HISTORY carve keeps page resolution OUT of
 * PageVersionDomainService.
 *
 * findPageById (13 self-callers) and locatePageForOperation (also used by the
 * non-version cache diagnostic) are shared far beyond version history and stay
 * resident on PageService, reaching this service only as bound closures. The
 * FolderContext substrate and the id sanitizer likewise stay behind those
 * closures. If a future edit reconstructs any of them inline here, it would pull
 * a shared lookup into a version service — the tangle this carve avoids. This
 * test fails the moment such a symbol is referenced from the carved file.
 */
class PageVersionDomainIsolationTest extends TestCase {

    public function testVersionDomainServiceDoesNotReachSharedPageResolution(): void {
        $file = __DIR__ . '/../../../lib/Service/Version/PageVersionDomainService.php';
        $this->assertFileExists($file);

        $src = file_get_contents($file);
        $this->assertIsString($src);

        $forbidden = [
            'findPageById',
            'locatePageForOperation',
            'locatePageAnyLanguage',
            'sanitizeId',
            'languageFolder',
        ];
        foreach ($forbidden as $symbol) {
            $this->assertStringNotContainsString(
                $symbol,
                $src,
                "PageVersionDomainService must not reach the resident lookup '$symbol'"
                    . ' — page resolution arrives as a bound closure from PageService instead'
            );
        }
    }
}
