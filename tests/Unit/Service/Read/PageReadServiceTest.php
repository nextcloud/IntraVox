<?php
declare(strict_types=1);

namespace OCA\IntraVox\Tests\Unit\Service\Read;

use OCA\IntraVox\Service\Cache\PageCacheService;
use OCA\IntraVox\Service\Folder\FolderContext;
use OCA\IntraVox\Service\Language\LanguageResolver;
use OCA\IntraVox\Service\Locator\PageLocator;
use OCA\IntraVox\Service\PageIndexService;
use OCA\IntraVox\Service\PermissionService;
use OCA\IntraVox\Service\Publication\MetaVoxGateway;
use OCA\IntraVox\Service\Read\PageReadService;
use OCA\IntraVox\Service\Sanitize\ColorSanitizer;
use OCA\IntraVox\Service\Sanitize\HtmlSanitizer;
use OCA\IntraVox\Service\Sanitize\PageShapeSanitizer;
use OCA\IntraVox\Service\Sanitize\UrlSanitizer;
use OCA\IntraVox\Service\Translation\TranslationGroupService;
use OCA\IntraVox\Service\Util\GroupfolderResolver;
use OCA\IntraVox\Service\Util\PageIdUtils;
use OCA\IntraVox\Tests\Unit\Service\Harness\BuildsNodeFixtures;
use OCP\Files\Folder;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Behavioural coverage of PageReadService::getPage() — the single-page read, the
 * highest fan-in method in the app.
 *
 * Migrated (fase-10) from PageCrudReadTest + PageDistributedHitRecomputeTest, which
 * pinned exactly this behaviour by reflecting readService() off a
 * buildRealPageService() and calling getPage() on the returned sub-service. This
 * builds PageReadService DIRECTLY over the same collaborators the retired
 * PageService::readService() accessor assembled — same cache / FolderContext /
 * PermissionService / MetaVoxGateway / PageDataEnricher, so the #90 request-cache
 * short-circuit and the #70 distributed-hit per-user recompute run their genuine
 * bodies. Assertions are byte-identical to the two retired facade tests.
 *
 * The #70 leak guard is the load-bearing half: the distributed cache is shared
 * across users, the write path strips per-user fields, and the read path MUST
 * recompute permissions / canEdit / metaVoxAvailable / translations / fileId /
 * groupfolderId fresh on every hit — never serve the shared entry verbatim.
 */
class PageReadServiceTest extends TestCase {

    use BuildsNodeFixtures;

    /**
     * Build PageReadService DIRECTLY, reproducing the collaborator graph the
     * retired PageService::readService() accessor built: a real PageDataEnricher
     * (the #70 per-user permission/translation recompute), the real
     * PageShapeSanitizer, and the rigged cache / folders / permissionService /
     * metaVox the individual tests drive.
     */
    private function makeReadService(
        PageCacheService $cache,
        ?Folder $readFolder,
        ?PermissionService $permissionService = null,
        bool $metavox = false
    ): PageReadService {
        $logger = $this->createMock(LoggerInterface::class);
        $index = $this->createMock(PageIndexService::class);
        $index->method('findByUniqueId')->willReturn(null);
        $locator = new PageLocator($index, $logger);

        $config = $this->createMock(\OCP\IConfig::class);
        $config->method('getUserValue')->willReturn('en');
        $languageService = $this->createMock(\OCA\IntraVox\Service\LanguageService::class);
        $languageService->method('getPrimaryLanguage')->willReturn('en');

        // A real FolderContext whose readLanguageFolder() seam yields $readFolder
        // (or leaves it unresolvable when null — driving the clean-miss / hit-
        // short-circuit paths). intraVoxOverride set so the mount walk is bypassed;
        // it is never reached on these paths.
        $folders = new FolderContext(
            $this->createMock(\OCP\Files\IRootFolder::class),
            'tester',
            $config,
            $languageService,
            new LanguageResolver(),
            $locator,
            $readFolder,                                                  // intraVoxOverride
            $readFolder === null ? null : fn(): Folder => $readFolder,    // readLanguageFolder seam
        );

        $permissionService ??= $this->createMock(PermissionService::class);

        $metaVox = $this->createMock(MetaVoxGateway::class);
        $metaVox->method('isMetaVoxAvailable')->willReturn($metavox);

        $translationGroups = new TranslationGroupService(
            $index,
            $locator,
            new PageIdUtils(),
            $logger
        );
        $groupfolders = new GroupfolderResolver();

        $shape = new PageShapeSanitizer(
            $config,
            $logger,
            new HtmlSanitizer(),
            new UrlSanitizer(),
            new ColorSanitizer(),
        );

        // The enricher IS the #70 recompute: permissionsForPage + resolveTranslations
        // + groupfolderId. Built exactly as PageService::pageDataEnricher() did.
        $enricher = new \OCA\IntraVox\Service\Path\PageDataEnricher(
            new \OCA\IntraVox\Service\Path\PagePathHelper(),
            $permissionService,
            $metaVox,
            $folders,
            $translationGroups,
            $groupfolders,
        );

        return new PageReadService(
            $cache,
            $locator,
            $metaVox,
            $enricher,
            $shape,
            $permissionService,
            new PageIdUtils(),
            $logger,
            $folders,
            $translationGroups,
            $groupfolders,
        );
    }

    /**
     * A single page 'about' found by the primary-folder scan, for the
     * distributed-hit recompute fixtures (index misses; the walk stays in-folder).
     */
    private function aboutLangFolder(): Folder {
        $pageJson = $this->makeFile('/IntraVox/en/about.json', ['uniqueId' => 'page-about', 'title' => 'About']);
        $pageFolder = $this->makeFolder('/IntraVox/en/about', []);
        return $this->makeFolder('/IntraVox/en', [
            'about.json' => $pageJson,
            'about' => $pageFolder,
        ]);
    }

    /** A cache mock: request miss, distributed hit returning $cachedJson. */
    private function distributedHitCache(string $cachedJson): PageCacheService {
        $cache = $this->createMock(PageCacheService::class);
        $cache->method('getPageData')->willReturn(null);
        $cache->method('isDistributedAvailable')->willReturn(true);
        $cache->method('getDistributed')->willReturn($cachedJson);
        return $cache;
    }

    // ------------------------------------------------------ request-cache short-circuit (#90)

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
        $svc = $this->makeReadService($cache, null);

        $this->assertSame($cached, $svc->getPage('page-abc'));
    }

    public function testCacheMissWithUnresolvablePageThrowsPageNotFound(): void {
        $cache = $this->createMock(PageCacheService::class);
        $cache->method('getPageData')->willReturn(null);

        // An empty read folder: nothing resolves, so getPage must throw. The
        // legacy contract is a bare \Exception('Page not found') for the
        // uniqueId/slug miss (distinct from deletePage's PageNotFoundException).
        $empty = $this->makeFolder('/IntraVox/en', []);
        $svc = $this->makeReadService($cache, $empty);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Page not found');
        $svc->getPage('page-does-not-exist');
    }

    // ------------------------------------------------------ distributed-hit recompute (#70)

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

        $svc = $this->makeReadService($this->distributedHitCache($poisoned), $this->aboutLangFolder(), $permissionService);
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

        $svc = $this->makeReadService($this->distributedHitCache($entry), $this->aboutLangFolder(), $permissionService);
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

        $svc = $this->makeReadService($this->distributedHitCache($entry), $this->aboutLangFolder(), $permissionService);
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
        $svc = $this->makeReadService($this->distributedHitCache($entry), $this->aboutLangFolder(), $permissionService, metavox: true);
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
        $svc = $this->makeReadService($this->distributedHitCache($entry), $this->aboutLangFolder(), $permissionService, metavox: false);
        $page = $svc->getPage('page-about');

        $this->assertFalse(
            $page['metaVoxAvailable'],
            'metaVoxAvailable is recomputed from the app manager on every hit'
        );
    }
}
