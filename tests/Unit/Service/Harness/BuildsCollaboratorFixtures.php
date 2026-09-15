<?php
declare(strict_types=1);

namespace OCA\IntraVox\Tests\Unit\Service\Harness;

use OCA\IntraVox\Service\Cache\PageCacheService;
use OCA\IntraVox\Service\Folder\FolderContext;
use OCA\IntraVox\Service\Locator\PageLocator;
use OCA\IntraVox\Service\PermissionService;
use OCP\Files\File;
use OCP\Files\FileInfo;
use OCP\Files\Folder;
use OCP\Files\NotFoundException;
use Psr\Log\LoggerInterface;

/**
 * Facade-free composite collaborator fixtures (COMMIT 0, fase-10 harness split).
 * Builds real (final) domain collaborators — PageLister/NewsWidgetService/
 * PageTreeService/HomepageResolverService — over the leaf fixtures. Composes the
 * node/folder/cache/double traits it depends on. Lifted verbatim out of
 * BuildsPageService.
 */
trait BuildsCollaboratorFixtures {
    use BuildsNodeFixtures;
    use BuildsFolderFixtures;
    use BuildsCacheFixtures;
    use BuildsServiceDoubles;

    /**
     * A real (final) PageLister whose listAllWithContent() yields exactly the given
     * page-data arrays — the seam-free replacement for overriding the (now-removed)
     * public listPagesWithContent() delegator on a PageService subclass.
     *
     * listAllWithContent() does NOT use the index; it always walks the read-language
     * folder: it tries folder->get('home.json') (skipped here — the fixture root
     * throws NotFoundException, so no loose homepage is prepended), then recurses the
     * directory listing and, for each non-infrastructure SUBFOLDER, reads
     * {slug}/{slug}.json, requiring isReadable() === true and a decodable body with a
     * uniqueId. So this rigs that exact walk: one subfolder per page whose
     * {slug}.json's getContent() is the page JSON. The real PageShapeSanitizer runs
     * (doubleOrBuild builds it, it is final) and is identity for these fixtures — it
     * only transforms video/people widgets, which the search fixtures never use.
     *
     * The walk overwrites $data['fileId'] with the json file's getId() (harmless: the
     * search scorer only uses fileId to look up MetaVox, which the inert gateway
     * answers empty). Pages are appended in directory-listing order; since no search
     * assertion depends on order among equal-score pages (usort is stable on PHP 8),
     * that order is behaviour-neutral. Pages with no uniqueId are naturally skipped by
     * the walk's `isset($data['uniqueId'])` guard, matching the old in-memory feed.
     *
     * Slugs are derived from each page's uniqueId; they must not be infrastructure
     * names (no leading '_' / '.', not 'images'/'files') — the fixture uniqueIds
     * (page-a, page-0, …) all qualify. A synthetic /IntraVox/en tree gives every
     * folder/file a distinct getPath() so PageLocator's per-path directory/content
     * caches never collide.
     *
     * @param list<array> $pages page-data arrays as listAllWithContent() must yield
     */
    protected function fakePageListerWithContent(array $pages): \OCA\IntraVox\Service\Listing\PageLister {
        $subfolders = [];
        foreach ($pages as $i => $pageData) {
            // A stable, infrastructure-safe slug per page. The uniqueId is used when
            // present (the walk does not depend on it matching anything); id-less
            // fixtures still get a folder so the walk reaches their {slug}.json and
            // applies its own uniqueId guard, exactly as production would.
            $slug = isset($pageData['uniqueId']) && $pageData['uniqueId'] !== ''
                ? 'p-' . $pageData['uniqueId']
                : 'p-noid-' . $i;
            $slugPath = '/IntraVox/en/' . $slug;

            // {slug}.json must be readable (walkWithContent gates on isReadable()) and
            // carry the fixture JSON verbatim. makeFile does not stub isReadable, so
            // build the file here with the extra stub.
            $jsonFile = $this->createMock(File::class);
            $jsonFile->method('getName')->willReturn($slug . '.json');
            $jsonFile->method('getType')->willReturn(FileInfo::TYPE_FILE);
            $jsonFile->method('getPath')->willReturn($slugPath . '/' . $slug . '.json');
            $jsonFile->method('getContent')->willReturn(json_encode($pageData));
            $jsonFile->method('getId')->willReturn(abs(crc32($slugPath)));
            $jsonFile->method('isReadable')->willReturn(true);

            // The subfolder whose {slug}.json the walk fetches via $item->get(...).
            $subfolders[$slug] = $this->makeFolder($slugPath, [$slug . '.json' => $jsonFile]);
        }

        // The read-language folder: no home.json (get() throws NotFoundException for
        // any unknown child, so the home-prepend branch is skipped), its directory
        // listing is exactly the page subfolders in fixture order.
        $langFolder = $this->makeFolder('/IntraVox/en', $subfolders);
        $intraVox = $this->makeFolder('/IntraVox');
        $folders = $this->fakeFolderContext(readLanguageFolder: $langFolder, intraVox: $intraVox);

        $index = $this->createMock(\OCA\IntraVox\Service\PageIndexService::class);
        $logger = $this->createMock(LoggerInterface::class);

        return new \OCA\IntraVox\Service\Listing\PageLister(
            new PageLocator($index, $logger),
            $index,
            $this->createMock(PermissionService::class),
            $logger,
            $folders,
            $this->doubleOrBuild(\OCA\IntraVox\Service\Sanitize\PageShapeSanitizer::class),
            $this->createMock(PageCacheService::class),
            $this->doubleOrBuild(\OCA\IntraVox\Service\Path\PageDataEnricher::class),
        );
    }

    /**
     * Build a real (final) NewsWidgetService from the same explicit deps a News test
     * threads through — the direct-service replacement for the retired
     * PageService::getNewsPages delegator (fase-5 Phase II C). The collect/sort
     * engine (newsPageService), the metaVox gateway and the cache are taken from
     * $explicit when given so the fixture drives the real getNewsPages pipeline; the
     * folder substrate comes from the wired folderContext (else a bare fixture).
     *
     * @param array<string,mixed> $explicit
     */
    protected function buildNewsWidget(array $explicit): \OCA\IntraVox\Service\News\NewsWidgetService {
        $folders = ($explicit['folderContext'] ?? null) instanceof FolderContext
            ? $explicit['folderContext']
            : $this->fakeFolderContext();
        $logger = $explicit['logger'] ?? $this->createMock(LoggerInterface::class);
        $index = $explicit['pageIndexService'] ?? $this->createMock(\OCA\IntraVox\Service\PageIndexService::class);
        $cache = $explicit['cache'] ?? $this->createMock(PageCacheService::class);

        return new \OCA\IntraVox\Service\News\NewsWidgetService(
            $explicit['newsPageService'] ?? $this->doubleOrBuild(\OCA\IntraVox\Service\News\NewsPageService::class),
            $cache,
            $explicit['groupContext'] ?? $this->doubleOrBuild(\OCA\IntraVox\Service\GroupContextService::class),
            $explicit['metaVoxGateway'] ?? $this->doubleOrBuild(\OCA\IntraVox\Service\Publication\MetaVoxGateway::class),
            $this->doubleOrBuild(\OCA\IntraVox\Service\Publication\PublicationStateService::class),
            $folders,
            $logger,
            new PageLocator($index, $logger),
        );
    }

    /**
     * Build a real (final) PageTreeService from the explicit deps a tree test threads
     * through — the direct-service replacement for the retired PageService::getPageTree
     * delegator (fase-6 Track 3c). getPageTree's cache-hit path (which the shape tests
     * drive) only touches cache / folders / groupContext / permissionService, so those
     * come from $explicit when given; the fresh-build collaborators (treeBuilder /
     * homepageService / pathHelper) are inert doubles, never reached on a hit.
     *
     * @param array<string,mixed> $explicit
     */
    protected function fakeTreeService(array $explicit): \OCA\IntraVox\Service\Tree\PageTreeService {
        $folders = ($explicit['folderContext'] ?? null) instanceof FolderContext
            ? $explicit['folderContext']
            : $this->fakeFolderContext();
        $cache = $explicit['cache'] ?? $this->createMock(PageCacheService::class);
        $logger = $explicit['logger'] ?? $this->createMock(LoggerInterface::class);
        $index = $explicit['pageIndexService'] ?? $this->createMock(\OCA\IntraVox\Service\PageIndexService::class);
        $permissionService = $explicit['permissionService'] ?? $this->createMock(PermissionService::class);

        return new \OCA\IntraVox\Service\Tree\PageTreeService(
            $cache,
            $explicit['groupContext'] ?? $this->doubleOrBuild(\OCA\IntraVox\Service\GroupContextService::class),
            $folders,
            new \OCA\IntraVox\Service\Tree\PageTreeBuilder(
                new PageLocator($index, $logger),
                $permissionService,
                $folders
            ),
            $explicit['homepageService'] ?? $this->createMock(\OCA\IntraVox\Service\HomepageService::class),
            $this->doubleOrBuild(\OCA\IntraVox\Service\Path\PagePathHelper::class),
            $permissionService,
        );
    }

    /**
     * A real (final) HomepageResolverService rigged so resolveHomepageNodeUniqueId()
     * (any language) yields exactly $homeUniqueId — the seam-free replacement for
     * the isHomepage() subclass overrides (fase-3). With the resolver injected,
     * PageService::isHomepage(uid) === (uid === $homeUniqueId), reproducing the old
     * override's fixed predicate.
     *
     * The rig honours the pointer path of getHomepageUniqueId(): the homepageService
     * mock returns $homeUniqueId as the pointer, and the languageFolderByCode +
     * locatePage closures succeed (folder + non-null hit) so the pointer is returned
     * verbatim. When $homeUniqueId is null the mock returns null → the resolver
     * falls through to the legacy 'home', so isHomepage() is true only for the bare
     * 'home' id (the "no homepage configured" fixture). The remaining closures are
     * inert stubs (never reached on the pointer-honoured path). Model:
     * PageHomepageResolutionTest, which wires the real resolver via homepageService.
     */
    protected function fakeHomepageResolver(?string $homeUniqueId, ?FolderContext $folders = null): \OCA\IntraVox\Service\Homepage\HomepageResolverService {
        $homepageService = $this->createMock(\OCA\IntraVox\Service\HomepageService::class);
        $homepageService->method('getHomepageUniqueId')->willReturn($homeUniqueId);
        // The resolver is DI-promoted (fase-5): a PageLocator whose findPageByUniqueId
        // returns non-null makes getHomepageUniqueId honour the pointer, so
        // resolveHomepageNodeUniqueId yields $homeUniqueId. A folders() that resolves
        // a language folder keeps the pointer path from throwing.
        $locator = $this->createMock(\OCA\IntraVox\Service\Locator\PageLocator::class);
        $locator->method('findPageByUniqueId')->willReturn(['folder' => $this->createMock(Folder::class)]);
        // The language folder resolves but has no loose home.json, so the legacy path
        // of getHomepageUniqueId (reached when $homeUniqueId is null / not a pointer)
        // falls through to 'home' cleanly instead of dereferencing a null folder.
        $lang = $this->createMock(Folder::class);
        $lang->method('get')->willThrowException(new NotFoundException('home.json'));
        $base = $this->createMock(Folder::class);
        $base->method('get')->willReturn($lang);
        // Always a self-contained folders that resolves effectiveLanguage/
        // languageFolderByCode — NOT the caller's $folders, which may be an unwired
        // fixture (e.g. a delete guard-ordering test) whose intraVox() throws. The
        // resolver only needs to yield $homeUniqueId; the caller's folders drive the
        // OTHER services, not this fixed-predicate resolver. $folders is accepted for
        // signature compatibility but intentionally not used here.
        return new \OCA\IntraVox\Service\Homepage\HomepageResolverService(
            $homepageService,
            $this->fakeFolderContext(languageFolder: $lang, intraVox: $base, userLanguage: 'en', primaryLanguage: 'en'),
            $locator,
            $this->fakeCacheInvalidator()
        );
    }
}
