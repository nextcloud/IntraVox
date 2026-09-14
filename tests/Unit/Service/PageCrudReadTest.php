<?php
declare(strict_types=1);

namespace OCA\IntraVox\Tests\Unit\Service;

use OCA\IntraVox\Service\Cache\PageCacheService;
use OCA\IntraVox\Service\PageService;
use OCA\IntraVox\Tests\Unit\Service\Harness\BuildsPageService;
use OCP\Files\Folder;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Characterizes getPage()'s read path — the highest fan-in method in the app (27
 * external call sites) — before it splits into Page/PageReadService in Phase 14.
 *
 * These pin the two behaviours the progress-check audit flagged as most valuable:
 * the request-level cache short-circuit (a hit returns verbatim and touches no
 * filesystem), and the not-found contract. The full enrich/sanitize pipeline and
 * the distributed-hit recompute set are pinned separately once their fixtures are
 * built (they need the read language folder walked); here the cache seam lets us
 * assert the entry/exit contract cleanly.
 */
class PageCrudReadTest extends TestCase {

    use BuildsPageService;

    /**
     * @param PageCacheService $cache the (mocked) request/distributed cache
     * @param Folder|null $readFolder folder returned by readLanguageFolder()
     */
    private function makeService(PageCacheService $cache, ?Folder $readFolder = null): \OCA\IntraVox\Service\Read\PageReadService {
        // getPage moved off PageService (fase-6 Track 3b): the single-page read now
        // lives ONLY on Read/PageReadService. Build a real PageService over the rigged
        // deps and hand back the PageReadService its readService() accessor builds from
        // the same cache/folderContext — the exact instance the retired PageService::getPage
        // delegated to. A null readFolder leaves the FolderContext unable to resolve,
        // driving the clean-miss / hit-short-circuit paths as before.
        $svc = $this->buildRealPageService([
            'cache' => $cache,
            'logger' => $this->createMock(LoggerInterface::class),
            'folderContext' => $this->fakeFolderContext(readLanguageFolder: $readFolder),
        ]);
        $accessor = new \ReflectionMethod(PageService::class, 'readService');
        return $accessor->invoke($svc);
    }

    public function testRequestCacheHitReturnsVerbatimWithoutTouchingTheFilesystem(): void {
        $cached = ['uniqueId' => 'page-abc', 'title' => 'Cached', 'permissions' => ['canRead' => true]];

        $cache = $this->createMock(PageCacheService::class);
        $cache->expects($this->once())
            ->method('getPageData')
            ->with('page-abc')
            ->willReturn($cached);
        // A cache hit must return before any folder resolution.
        $cache->expects($this->never())->method('setPageData');
        $cache->expects($this->never())->method('setPageFolder');

        // No read folder wired: if getPage tried to resolve, the seam throws and
        // this test fails — proving the hit short-circuits.
        $svc = $this->makeService($cache, null);

        $this->assertSame($cached, $svc->getPage('page-abc'));
    }

    public function testCacheMissWithUnresolvablePageThrowsPageNotFound(): void {
        $cache = $this->createMock(PageCacheService::class);
        $cache->method('getPageData')->willReturn(null);

        // An empty read folder: nothing resolves, so getPage must throw. The
        // legacy contract is a bare \Exception('Page not found') for the
        // uniqueId/slug miss (distinct from deletePage's PageNotFoundException).
        $empty = $this->makeFolder('/IntraVox/en', []);
        $svc = $this->makeService($cache, $empty);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Page not found');
        $svc->getPage('page-does-not-exist');
    }
}
