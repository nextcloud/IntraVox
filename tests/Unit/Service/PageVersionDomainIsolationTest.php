<?php
declare(strict_types=1);

namespace OCA\IntraVox\Tests\Unit\Service;

use PHPUnit\Framework\TestCase;

/**
 * Structural guard: PageVersionDomainService owns its page resolution through the
 * injected FolderContext + PageLocator collaborators and never reaches back into
 * PageService.
 *
 * (Facade elimination phase 1 reversed the original premise: page resolution used
 * to arrive as bound closures from PageService; the two locate idioms now live IN
 * this service over its injected PageLocator/FolderContext, which is what makes it
 * DI-first-class and directly injectable by PageContentApiController. The invariant
 * worth guarding is therefore the absence of any PageService back-reference — a
 * regression to the facade would reintroduce the god-class coupling.)
 */
class PageVersionDomainIsolationTest extends TestCase {

    public function testVersionDomainServiceNeverReachesBackIntoPageService(): void {
        $file = __DIR__ . '/../../../lib/Service/Version/PageVersionDomainService.php';
        $this->assertFileExists($file);

        $src = file_get_contents($file);
        $this->assertIsString($src);

        $this->assertStringNotContainsString(
            'PageService',
            $src,
            'PageVersionDomainService must not reference PageService — it owns page '
                . 'resolution via injected FolderContext + PageLocator'
        );
    }
}
