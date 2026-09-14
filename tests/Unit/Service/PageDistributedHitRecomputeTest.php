<?php
declare(strict_types=1);

namespace OCA\IntraVox\Tests\Unit\Service;

use OCA\IntraVox\Service\Cache\PageCacheService;
use OCA\IntraVox\Service\PageIndexService;
use OCA\IntraVox\Service\PageService;
use OCA\IntraVox\Service\PermissionService;
use OCA\IntraVox\Tests\Unit\Service\Harness\BuildsPageService;
use OCP\App\IAppManager;
use OCP\Files\File;
use OCP\Files\FileInfo;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Pins the per-user recompute on a distributed-cache HIT in getPage()
 * (PageService.php ~2045-2089) — the guard against issue #70, where one user's
 * canWrite could otherwise be served to another out of the shared cache.
 *
 * The distributed cache is shared across users; the write path strips the
 * per-user fields, and the read path MUST recompute them fresh on every hit:
 * permissions, canEdit, metaVoxAvailable and translations. If the Phase 14.1
 * extraction dropped or reordered this recompute, the leak would ship green — so
 * this test asserts, behaviourally, that the STALE cached values are overwritten
 * by fresh collaborator calls rather than returned verbatim.
 */
class PageDistributedHitRecomputeTest extends TestCase {

    use BuildsPageService;

    private function makeService(
        string $cachedJson,
        PermissionService $permissionService,
        bool $metavox = false
    ): \OCA\IntraVox\Service\Read\PageReadService {
        // A single page 'about' found by the primary-folder scan (index misses,
        // root seam throws so the walk stays in-folder and never reads $userId).
        $pageJson = $this->makeFile(
            '/IntraVox/en/about.json',
            ['uniqueId' => 'page-about', 'title' => 'About']
        );
        $pageFolder = $this->makeFolder('/IntraVox/en/about', []);
        $lang = $this->makeFolder('/IntraVox/en', [
            'about.json' => $pageJson,
            'about' => $pageFolder,
        ]);

        // getPage (the #70 distributed-hit recompute path) resolves its folder via
        // folders()->readLanguageFolder() ($lang), driven by the injected
        // folderContext below.
        // Cache: request miss, distributed hit returning the stale entry.
        $cache = $this->createMock(PageCacheService::class);
        $cache->method('getPageData')->willReturn(null);
        $cache->method('isDistributedAvailable')->willReturn(true);
        $cache->method('getDistributed')->willReturn($cachedJson);

        // MetaVoxGateway is now a DI-injected service (facade elimination phase 2),
        // so wire its availability directly on the gateway rather than via IAppManager.
        $metaVoxGateway = $this->createMock(\OCA\IntraVox\Service\Publication\MetaVoxGateway::class);
        $metaVoxGateway->method('isMetaVoxAvailable')->willReturn($metavox);

        $index = $this->createMock(PageIndexService::class);
        $index->method('findByUniqueId')->willReturn(null);

        // getPage moved off PageService (fase-6 Track 3b): the #70 distributed-hit
        // recompute lives ONLY on Read/PageReadService now. Build a real PageService
        // over the rigged deps and hand back the PageReadService its readService()
        // accessor builds from the same cache/folderContext/permissionService — the
        // exact instance the retired PageService::getPage delegated to.
        $svc = $this->buildRealPageService([
            'permissionService' => $permissionService,
            'cache' => $cache,
            'metaVoxGateway' => $metaVoxGateway,
            'pageIndexService' => $index,
            'userId' => 'tester',
            'logger' => $this->createMock(LoggerInterface::class),
            'folderContext' => $this->fakeFolderContext(readLanguageFolder: $lang),
        ]);
        return (new \ReflectionMethod(PageService::class, 'readService'))->invoke($svc);
    }

    public function testStalePermissionsInTheCachedEntryAreOverwrittenWithAFreshComputation(): void {
        // The shared cache entry carries a poisoned permissions block (as if
        // written for a user who could write). The current reader must NOT get it.
        $poisoned = json_encode([
            'uniqueId' => 'page-about',
            'title' => 'About',
            'permissions' => ['canRead' => true, 'canWrite' => true, 'canDelete' => true],
            'canEdit' => true,
        ]);

        $fresh = ['canRead' => true, 'canWrite' => false, 'canDelete' => false];
        $permissionService = $this->createMock(PermissionService::class);
        $permissionService->expects($this->once())
            ->method('permissionsForPage')
            ->willReturn($fresh);

        $svc = $this->makeService($poisoned, $permissionService);
        $page = $svc->getPage('page-about');

        $this->assertSame(
            $fresh,
            $page['permissions'],
            'permissions must be recomputed on a distributed hit, never served from the shared cache'
        );
        $this->assertArrayHasKey('canEdit', $page, 'canEdit is recomputed from the file, not the cache');
    }

    public function testTranslationsAreRecomputedNotServedFromTheSharedCache(): void {
        // The cached entry has no translations (stripped on write). The reader must
        // get a freshly-resolved list, not the absent/other-user value.
        $entry = json_encode([
            'uniqueId' => 'page-about',
            'title' => 'About',
            'translationGroup' => 'grp-1',
            'permissions' => ['canRead' => true],
        ]);

        $permissionService = $this->createMock(PermissionService::class);
        $permissionService->method('permissionsForPage')->willReturn(['canRead' => true]);

        $svc = $this->makeService($entry, $permissionService);
        $page = $svc->getPage('page-about');

        // resolveTranslations runs on every hit; with no real translation group
        // wired it resolves to an array (empty), and the key is always present.
        $this->assertArrayHasKey(
            'translations',
            $page,
            'translations are ACL-filtered per user and must be recomputed on every hit'
        );
        $this->assertIsArray($page['translations']);
    }

    public function testFileIdIsBackfilledOnHitWhenAbsentFromTheCachedEntry(): void {
        // Older cache entries predate the fileId field. On a hit it must be
        // backfilled from the resolved file so the publication gate keeps working
        // — a user-independent value, but one that must not be absent.
        $entry = json_encode([
            'uniqueId' => 'page-about',
            'title' => 'About',
            'permissions' => ['canRead' => true],
            // no 'fileId' key — simulates a pre-fileId cache entry
        ]);

        $permissionService = $this->createMock(PermissionService::class);
        $permissionService->method('permissionsForPage')->willReturn(['canRead' => true]);

        $svc = $this->makeService($entry, $permissionService);
        $page = $svc->getPage('page-about');

        // The harness makeFile() gives getId() = abs(crc32($path)); the recompute
        // must have populated fileId from the resolved file, not left it absent.
        $this->assertArrayHasKey('fileId', $page, 'fileId is backfilled on a hit when the cached entry lacks it');
        $this->assertSame(abs(crc32('/IntraVox/en/about.json')), $page['fileId']);
    }

    public function testGroupfolderIdIsRecomputedOnHitNotServedFromTheStaleCache(): void {
        // groupfolderId is a property of the file's mount, stripped from the shared
        // cache. With MetaVox available the recompute branch runs and overwrites any
        // stale value baked into the entry — it must never be served verbatim.
        $entry = json_encode([
            'uniqueId' => 'page-about',
            'title' => 'About',
            'permissions' => ['canRead' => true],
            'groupfolderId' => 999999, // poisoned: as if cached for another mount
        ]);

        $permissionService = $this->createMock(PermissionService::class);
        $permissionService->method('permissionsForPage')->willReturn(['canRead' => true]);

        // MetaVox available -> the groupfolderId recompute branch executes. The
        // fixture file has no real groupfolder mount, so the fresh value is null,
        // which must overwrite the poisoned 999999.
        $svc = $this->makeService($entry, $permissionService, metavox: true);
        $page = $svc->getPage('page-about');

        $this->assertTrue($page['metaVoxAvailable']);
        $this->assertNotSame(
            999999,
            $page['groupfolderId'] ?? null,
            'groupfolderId must be recomputed from the mount on every hit, never served from the shared cache'
        );
    }

    public function testMetaVoxAvailabilityIsRecomputedOnEveryHit(): void {
        // metaVoxAvailable is an install-wide fact stripped from the cache; a hit
        // must reflect the CURRENT app state, not whatever was cached.
        $entry = json_encode([
            'uniqueId' => 'page-about',
            'title' => 'About',
            'permissions' => ['canRead' => true],
            'metaVoxAvailable' => true, // stale: pretend it was cached as available
        ]);

        $permissionService = $this->createMock(PermissionService::class);
        $permissionService->method('permissionsForPage')->willReturn(['canRead' => true]);

        // MetaVox currently NOT available -> the hit must report false.
        $svc = $this->makeService($entry, $permissionService, metavox: false);
        $page = $svc->getPage('page-about');

        $this->assertFalse(
            $page['metaVoxAvailable'],
            'metaVoxAvailable is recomputed from the app manager on every hit'
        );
    }
}
