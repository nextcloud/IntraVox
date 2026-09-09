<?php
declare(strict_types=1);

namespace OCA\IntraVox\Tests\Unit\Service;

use PHPUnit\Framework\TestCase;

/**
 * Structural guard: the NEWS-widget carve keeps the shared/resident lookups OUT
 * of NewsWidgetService.
 *
 * findPageByUniqueId (shared far beyond news), resolveEffectiveLanguage /
 * getUserLanguage (resolved via the injected FolderContext), getCachedFileContent
 * and clearCache all stay resident on PageService and reach this service only as
 * a bound closure (locatePage) or the FolderContext substrate. If a future edit
 * reconstructs any of them inline here, it would pull a shared symbol into the
 * news orchestrator — the tangle this carve avoids. This test fails the moment
 * such a symbol is referenced from the carved file.
 *
 * The NewsPageService engine + its cache/group/metaVox/publication collaborators
 * are legitimately used, so they are not forbidden here.
 */
class NewsWidgetIsolationTest extends TestCase {

    public function testNewsWidgetServiceDoesNotReachResidentLookups(): void {
        $file = __DIR__ . '/../../../lib/Service/News/NewsWidgetService.php';
        $this->assertFileExists($file);

        $src = file_get_contents($file);
        $this->assertIsString($src);

        $forbidden = [
            'findPageByUniqueId',
            'resolveEffectiveLanguage',
            'getUserLanguage',
            'getCachedFileContent',
            'clearCache',
        ];
        foreach ($forbidden as $symbol) {
            $this->assertStringNotContainsString(
                $symbol,
                $src,
                "NewsWidgetService must not reach the resident lookup '$symbol'"
                    . ' — it arrives as a bound closure / the FolderContext substrate instead'
            );
        }
    }
}
