<?php
declare(strict_types=1);

namespace OCA\IntraVox\Service;

use OCA\IntraVox\AppInfo\Application;
use OCA\IntraVox\Constants;
use OCA\IntraVox\Event\PageDeletedEvent;
use OCA\IntraVox\Exception\CrossLanguageMoveException;
use OCA\IntraVox\Exception\ForbiddenException;
use OCA\IntraVox\Exception\PageConflictException;
use OCA\IntraVox\Exception\PageNotFoundException;
use OCA\IntraVox\Service\GroupContextService;
use OCA\IntraVox\Service\Language\LanguageResolver;
use OCA\IntraVox\Service\News\NewsContentExtractor;
use OCA\IntraVox\Service\News\NewsPageService;
use OCA\IntraVox\Service\Path\PagePathHelper;
use OCA\IntraVox\Service\Sanitize\HtmlSanitizer;
use OCA\IntraVox\Service\Sanitize\MediaSanitizer;
use OCA\IntraVox\Service\Cache\PageCacheService;
use OCA\IntraVox\Service\Sanitize\PageShapeSanitizer;
use OCA\IntraVox\Service\Search\PageSearchHelper;
use OCA\IntraVox\Service\Locator\PageLocator;
use OCA\IntraVox\Service\Media\PageMediaService;
use OCA\IntraVox\Service\Template\PageTemplateService;
use OCA\IntraVox\Service\Translation\TranslationGroupService;
use OCA\IntraVox\Service\Util\PageIdUtils;
use OCA\IntraVox\Service\Version\PageVersionService;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\Files\NotFoundException;
use OCP\IUserSession;
use OCP\IConfig;
use OCP\IDBConnection;
use OCP\App\IAppManager;
use Psr\Log\LoggerInterface;
use OCP\Files\Cache\ICacheEntry;

class PageService {
    private const ALLOWED_WIDGET_TYPES = ['text', 'heading', 'image', 'links', 'divider', 'video', 'news', 'people', 'calendar', 'feed', 'photo-story', 'file-story'];
    private const ALLOWED_IMAGE_TYPES = ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/svg+xml'];
    private const ALLOWED_EXTENSIONS = [
        'jpg', 'jpeg', 'png', 'gif', 'webp', 'svg',  // Images
        'mp4', 'webm', 'ogg',                         // Videos
    ];
    private const MAX_IMAGE_SIZE = 2097152; // 2MB (PHP default upload limit)
    private const MAX_VIDEO_SIZE = 52428800; // 50MB
    // The media allow-lists and the SVG ceiling moved with the upload paths
    // (PR-17b); PageMediaService owns them now. Only the overall ceiling is
    // still read here, by the upload-limit the editor is told about.
    private const MAX_MEDIA_SIZE = PageMediaService::MAX_MEDIA_SIZE;
    private const MAX_COLUMNS = 5;

    private IUserSession $userSession;
    private string $userId;
    private IAppManager $appManager;
    private IConfig $config;
    private IDBConnection $db;
    // The `= null` defaults below are LOAD-BEARING, not cosmetic. They are the
    // only reason the test harness leaves these lazy services alone: a
    // nullable-default property reports isInitialized()===true, so
    // BuildsPageService::fillPageServiceDependencies skips it and the real
    // accessor (metaVox()/publicationState()/maintenance()/language()) builds
    // the genuine collaborator. Drop a default to `?Foo $x;` and the auto-fill
    // mocks it with a double that answers null/[] to everything, silently
    // breaking the path it backs. Keep the `= null`.
    /** Lazily-built MetaVox gateway; owns the memos that used to live here (Phase 3). */
    private ?\OCA\IntraVox\Service\Publication\MetaVoxGateway $metaVoxGateway = null;
    /** Lazily-built publication scheduling service (Phase 3). */
    private ?\OCA\IntraVox\Service\Publication\PublicationStateService $publicationStateSvc = null;
    /** Lazily-built CLI maintenance service (Phase 4). */
    private ?\OCA\IntraVox\Service\Maintenance\PageMaintenanceService $maintenanceSvc = null;
    /** Lazily-built page-search scorer (Phase "search"). */
    private ?\OCA\IntraVox\Service\Search\PageSearchEngine $searchEngine = null;
    /** Lazily-built recursive tree walker (Phase "tree"). */
    private ?\OCA\IntraVox\Service\Tree\PageTreeBuilder $treeBuilder = null;
    /** Lazily-built index-based page lister (Phase "listing"). */
    private ?\OCA\IntraVox\Service\Listing\PageLister $pageLister = null;
    /** Lazily-built sibling reorderer (Phase "reorder"). */
    private ?\OCA\IntraVox\Service\Reorder\PageReorderer $reorderer = null;
    /** Lazily-built page-data enricher (Phase "crud" — fresh-build enrichment). */
    private ?\OCA\IntraVox\Service\Path\PageDataEnricher $pageDataEnricher = null;
    /** Lazily-built single-page reader (god-class dissolution — read cluster). */
    private ?\OCA\IntraVox\Service\Read\PageReadService $readService = null;
    /** Lazily-built page mutation service (god-class dissolution — write cluster). */
    private ?\OCA\IntraVox\Service\Write\PageWriteService $writeService = null;
    /** Lazily-built tree-structure service (god-class dissolution — STRUCTURE domain). */
    private ?\OCA\IntraVox\Service\Structure\PageStructureService $structureService = null;
    /** Lazily-built metadata projection service (METADATA domain). */
    private ?\OCA\IntraVox\Service\Metadata\PageMetadataService $metadataService = null;
    /** Lazily-built media orchestration service (MEDIA domain). */
    private ?\OCA\IntraVox\Service\Media\PageMediaOrchestrator $mediaOrchestrator = null;
    /** Lazily-built page-composition service (COMPOSE domain: copy/translate/template). */
    private ?\OCA\IntraVox\Service\Compose\PageCompositionService $compositionService = null;
    /** Lazily-built language-content-status reader (LANGUAGE-STATUS domain). */
    private ?\OCA\IntraVox\Service\Language\LanguageStatusService $languageStatusService = null;
    /** Lazily-built translation-query/link service (TRANSLATE-query domain). */
    private ?\OCA\IntraVox\Service\Translation\TranslationQueryService $translationQueryService = null;
    /** Lazily-built version-history service (VERSION/HISTORY domain). */
    private ?\OCA\IntraVox\Service\Version\PageVersionDomainService $versionDomain = null;
    /** Lazily-built homepage-resolution service (HOMEPAGE domain). */
    private ?\OCA\IntraVox\Service\Homepage\HomepageResolverService $homepageResolver = null;
    /** Lazily-built news-widget orchestration service (NEWS domain). */
    private ?\OCA\IntraVox\Service\News\NewsWidgetService $newsWidget = null;
    /** The folder/location substrate — now a DI-first-class ctor-injected service. */
    private \OCA\IntraVox\Service\Folder\FolderContext $folderContext;
    /** Stateless GroupFolder-id resolver (facade elimination phase 2). */
    private \OCA\IntraVox\Service\Util\GroupfolderResolver $groupfolders;
    private LoggerInterface $logger;
    private IEventDispatcher $eventDispatcher;
    private PublicationSettingsService $publicationSettings;
    private PageIndexService $pageIndexService;
    private PageCacheService $cache;


    /**
     * Get the effective upload limit in bytes (minimum of upload_max_filesize and post_max_size)
     */
    public function getUploadLimit(): int {
        $uploadMax = $this->idUtils->parsePhpSize(ini_get('upload_max_filesize') ?: '2M');
        $postMax = $this->idUtils->parsePhpSize(ini_get('post_max_size') ?: '8M');

        // Use the smaller of the two, but cap at our app's MAX_MEDIA_SIZE
        $phpLimit = min($uploadMax, $postMax);
        return min($phpLimit, self::MAX_MEDIA_SIZE);
    }

    /**
     * Parse PHP size notation (e.g., '2M', '8M', '512K') to bytes
     */

    /**
     * Public flush hook for callers that mutate the underlying filesystem
     * outside of PageService (notably ImportService, NavigationService,
     * BulkOperationService) and need the IntraVox cache layers to forget
     * everything so a fresh read rebuilds. Equivalent to the internal
     * clearCache() but exposed for cross-service invalidation.
     */
    public function invalidateAllCaches(): void {
        $this->clearCache();
    }

    /**
     * Begin a batch: suppress the (expensive, blanket) clearCache() that each
     * mutation triggers, so a bulk operation clears the caches once at the end
     * instead of once per item. Must be paired with endDeferredClear() in a
     * finally block. Reentrant — nested begins are counted.
     */
    public function beginDeferredClear(): void {
        $this->cache()->beginDeferred();
    }

    /**
     * End a batch. When the outermost begin is released, if any mutation asked
     * for a clear while suppressed, perform exactly one real clearCache() now.
     */
    public function endDeferredClear(): void {
        // endDeferred() performs the deferred tree/distributed clear itself and
        // reports whether it did. The collaborator caches below belong to the
        // same flush, so they follow on exactly that condition.
        if ($this->cache()->endDeferred()) {
            $this->clearCollaboratorCaches();
        }
    }

    /**
     * Clear all request-level caches (call after mutations)
     */
    private function clearCache(?string $pageId = null): void {
        // Request-level caches are always invalidated immediately: these are cheap
        // array resets, and doing them per item keeps every mutation seeing a
        // truthful filesystem view mid-batch (identical to the non-batch path).
        $this->cache()->clearRequest($pageId);
        if ($pageId === null) {
            $this->locator()->clearRequestCaches();
            $this->permissionService->clearNodePermissionsCache();
        }

        // The expensive part — the tree cache and the distributed cache
        // (IPC/Redis clear()) — is what makes a 100-item bulk op wipe the
        // distributed cache 100×. clearExpensive() defers it during a batch and
        // returns false; the collaborator caches are part of that same flush and
        // are skipped on the same condition.
        //
        // The clear is blanket rather than targeted: a single page mutation can
        // be visible to any group with read access via GroupFolder ACL, and we
        // cannot enumerate those from here. The bucket count is small (≤ groups
        // × languages, typically ~40), so a blanket clear is cheaper than
        // tracking dependencies. This also drops the news-version counters and
        // content caches; subsequent reads re-initialize at 0 and rebuild.
        if ($this->cache()->clearExpensive()) {
            $this->clearCollaboratorCaches();
        }
    }

    /**
     * Caches owned by OTHER services that must drop whenever ours do.
     *
     * Separate because two paths reach it: an ordinary clearCache(), and the
     * flush that closes a deferred batch. These are not ours to own —
     * SystemFileService builds the public-share tree, PermissionService keeps
     * the per-language path map — so we invalidate through their APIs rather
     * than reaching into their state.
     */
    private function clearCollaboratorCaches(): void {
        SystemFileService::clearStaticTreeCache();
        $this->permissionService->clearDistributedCache();
    }

    /**
     * Get cached file content (prevents repeated reads of same file within request)
     */
    private function getCachedFileContent(\OCP\Files\File $file): string {
        return $this->locator()->cachedFileContent($file);
    }

    private HtmlSanitizer $htmlSanitizer;
    private MediaSanitizer $mediaSanitizer;
    private PageShapeSanitizer $shapeSanitizer;
    private PageVersionService $pageVersionService;
    private PageTemplateService $pageTemplateService;
    private NewsContentExtractor $newsContent;
    private PageSearchHelper $searchHelper;
    private PagePathHelper $pathHelper;
    private PageIdUtils $idUtils;
    private GroupContextService $groupContext;
    private LanguageService $languageService;
    private HomepageService $homepageService;
    private NavigationService $navigationService;
    private PermissionService $permissionService;
    private PageLocator $pageLocator;
    private TranslationGroupService $translationGroupService;
    private PageMediaService $pageMediaService;
    private NewsPageService $newsPageService;

    public function __construct(
        IUserSession $userSession,
        IConfig $config,
        IDBConnection $db,
        LoggerInterface $logger,
        IEventDispatcher $eventDispatcher,
        PublicationSettingsService $publicationSettings,
        PageCacheService $cache,
        PageIndexService $pageIndexService,
        HtmlSanitizer $htmlSanitizer,
        MediaSanitizer $mediaSanitizer,
        PageShapeSanitizer $shapeSanitizer,
        PageVersionService $pageVersionService,
        PageTemplateService $pageTemplateService,
        NewsContentExtractor $newsContent,
        PageSearchHelper $searchHelper,
        PagePathHelper $pathHelper,
        PageIdUtils $idUtils,
        GroupContextService $groupContext,
        LanguageService $languageService,
        HomepageService $homepageService,
        NavigationService $navigationService,
        PermissionService $permissionService,
        PageLocator $pageLocator,
        TranslationGroupService $translationGroupService,
        PageMediaService $pageMediaService,
        NewsPageService $newsPageService,
        IAppManager $appManager,
        \OCA\IntraVox\Service\Folder\FolderContext $folderContext,
        \OCA\IntraVox\Service\Util\GroupfolderResolver $groupfolders,
        ?string $userId
    ) {
        $this->folderContext = $folderContext;
        $this->groupfolders = $groupfolders;
        $this->userSession = $userSession;
        $this->config = $config;
        $this->db = $db;
        $this->logger = $logger;
        $this->eventDispatcher = $eventDispatcher;
        $this->publicationSettings = $publicationSettings;
        $this->pageIndexService = $pageIndexService;
        $this->htmlSanitizer = $htmlSanitizer;
        $this->mediaSanitizer = $mediaSanitizer;
        $this->shapeSanitizer = $shapeSanitizer;
        $this->pageVersionService = $pageVersionService;
        $this->pageTemplateService = $pageTemplateService;
        $this->newsContent = $newsContent;
        $this->searchHelper = $searchHelper;
        $this->pathHelper = $pathHelper;
        $this->idUtils = $idUtils;
        $this->groupContext = $groupContext;
        $this->languageService = $languageService;
        $this->homepageService = $homepageService;
        $this->navigationService = $navigationService;
        $this->permissionService = $permissionService;
        $this->pageLocator = $pageLocator;
        $this->translationGroupService = $translationGroupService;
        $this->pageMediaService = $pageMediaService;
        $this->newsPageService = $newsPageService;
        $this->appManager = $appManager;
        $this->userId = $userId ?? '';
        $this->cache = $cache;

    }

    /**
     * The page-location engine. DI injects it via the constructor; this
     * accessor exists because the unit tests build PageService through
     * constructor-less anonymous subclasses and reflection-set only the
     * dependencies a test needs. Building the locator lazily from the same
     * pageIndexService + logger those tests already set reproduces exactly
     * what the pre-split inline code used — the seam mirrors the protected
     * getIntraVoxFolder()/getLanguageFolder() convention.
     */
    /**
     * Lazy seam, matching locator()/news(). Unlike those, this one cannot
     * synthesise its collaborator: two of the widget rules read admin config
     * (the video-domain allowlist and the configured feed connection types),
     * so a fallback instance would quietly apply *different* security rules
     * than the injected one. Tests that reach a sanitizing path therefore
     * wire PageShapeSanitizer explicitly — see $lazySeamServices.
     */
    private function shape(): PageShapeSanitizer {
        return $this->shapeSanitizer;
    }

    /**
     * Lazy seam, like locator()/news(). Unlike the sanitizer this one CAN be
     * synthesised: an empty cache is always a valid cache — it just misses and
     * the caller rebuilds. A test that never wires it therefore behaves as if
     * caching is cold, which is exactly the behaviour those tests want.
     */
    private function cache(): PageCacheService {
        if (!isset($this->cache)) {
            $this->cache = new PageCacheService();
        }
        return $this->cache;
    }

    /**
     * Lazy seam for the page-search scorer (Phase "search"). Built from the
     * already-injected searchHelper and the shared metaVox() gateway, so the
     * request-scoped MetaVox memo stays a single instance. Nullable-default so
     * the harness auto-fill skips it (see the load-bearing `= null` note above).
     */
    private function searchEngine(): \OCA\IntraVox\Service\Search\PageSearchEngine {
        return $this->searchEngine ??= new \OCA\IntraVox\Service\Search\PageSearchEngine(
            $this->searchHelper,
            $this->metaVox()
        );
    }

    /**
     * Lazy seam for the recursive tree walker (Phase "tree"). Built from the
     * locator + permissionService, with the two seam-bound bits
     * (getRelativePathFromRoot, getUserLanguage) passed in as closures so the
     * protected folder seams stay on PageService. Nullable-default so the harness
     * auto-fill skips it (see the load-bearing `= null` note above).
     */
    private function treeBuilder(): \OCA\IntraVox\Service\Tree\PageTreeBuilder {
        return $this->treeBuilder ??= new \OCA\IntraVox\Service\Tree\PageTreeBuilder(
            $this->locator(),
            $this->permissionService,
            $this->folders()
        );
    }

    /**
     * Lazy seam for the index-based page lister (Phase "listing"). Built from
     * locator() + pageIndexService + permissionService + logger, with the
     * IntraVox-root seam passed in as a closure. Nullable-default so the harness
     * auto-fill skips it (see the load-bearing `= null` note above).
     */
    private function pageLister(): \OCA\IntraVox\Service\Listing\PageLister {
        return $this->pageLister ??= new \OCA\IntraVox\Service\Listing\PageLister(
            $this->locator(),
            $this->pageIndexService,
            $this->permissionService,
            $this->logger,
            $this->rootClosure(),
            $this->folders(),
            $this->shape(),
            $this->cache(),
            fn(): \OCA\IntraVox\Service\Path\PageDataEnricher => $this->pageDataEnricher()
        );
    }

    /**
     * Lazy seam for the language-content-status reader (LANGUAGE-STATUS domain).
     * Built from the FolderContext substrate + the plain PageLister/PageLocator
     * engines; the homepage-resolution subsystem and the real-content probe stay
     * on PageService and are passed to getContentStatus() as $this-bound closures.
     * Nullable-default so the harness auto-fill skips it.
     */
    private function languageStatus(): \OCA\IntraVox\Service\Language\LanguageStatusService {
        return $this->languageStatusService ??= new \OCA\IntraVox\Service\Language\LanguageStatusService(
            $this->folders(),
            $this->pageLister(),
            $this->locator(),
            $this->logger
        );
    }

    /**
     * Lazy seam for the translation-query/link service (TRANSLATE-query domain).
     * Built from the FolderContext substrate + the TranslationGroupService
     * engine; page lookup / language-of-folder / display-name / the group-writer
     * (shared with COMPOSE's createTranslation, so it stays resident) / clearCache
     * go in as $this-bound closures. Nullable-default so the harness auto-fill
     * skips it.
     */
    private function translationQuery(): \OCA\IntraVox\Service\Translation\TranslationQueryService {
        return $this->translationQueryService ??= new \OCA\IntraVox\Service\Translation\TranslationQueryService(
            $this->folders(),
            $this->translationGroups(),
            $this->locator(),
            $this->languageService
        );
    }

    /**
     * Lazy seam for the version-history service (VERSION/HISTORY domain). Built
     * from the PageVersionService engine; page resolution stays on PageService
     * (findPageById / locatePageForOperation are shared far beyond versions) and
     * is bound as two $this-closures — one per pre-carve locate idiom, preserved
     * verbatim. Nullable-default so the harness auto-fill skips it.
     */
    private function versionDomain(): \OCA\IntraVox\Service\Version\PageVersionDomainService {
        return $this->versionDomain ??= new \OCA\IntraVox\Service\Version\PageVersionDomainService(
            $this->pageVersionService,
            $this->logger,
            $this->folders(),
            $this->locator(),
            $this->idUtils
        );
    }

    /**
     * Lazy seam for the homepage-resolution service (HOMEPAGE domain). Built from
     * the HomepageService pointer engine + the FolderContext substrate; page
     * lookup / language-folder resolver / cached read / language resolution / the
     * homepage predicate seam / clearCache stay resident on PageService (shared,
     * reflection-anchored, or pinned) and are bound as $this-closures. The
     * isHomepage closure keeps the resident, subclass-overridable seam
     * authoritative. Nullable-default so the harness auto-fill skips it.
     */
    private function homepageResolver(): \OCA\IntraVox\Service\Homepage\HomepageResolverService {
        return $this->homepageResolver ??= new \OCA\IntraVox\Service\Homepage\HomepageResolverService(
            $this->homepageService,
            $this->folders(),
            fn(string $lang): \OCP\Files\Folder => $this->getLanguageFolderByCode($lang),
            fn(\OCP\Files\Folder $folder, string $uid): ?array => $this->findPageByUniqueId($folder, $uid),
            fn(\OCP\Files\File $file): string => $this->getCachedFileContent($file),
            fn(): ?string => $this->resolveEffectiveLanguage(),
            fn(): string => $this->getUserLanguage(),
            fn(string $uid, ?string $language = null): bool => $this->isHomepage($uid, $language),
            function (): void {
                $this->clearCache();
            }
        );
    }

    /**
     * Lazy seam for the news-widget orchestration service (NEWS domain). Built
     * from the NewsPageService engine + its cache/group/metaVox/publication
     * collaborators + the FolderContext substrate; page lookup stays resident on
     * PageService (findPageByUniqueId is shared far beyond news) and is bound as
     * a $this-closure. Nullable-default so the harness auto-fill skips it.
     */
    private function newsWidget(): \OCA\IntraVox\Service\News\NewsWidgetService {
        return $this->newsWidget ??= new \OCA\IntraVox\Service\News\NewsWidgetService(
            $this->news(),
            $this->cache(),
            $this->groupContext,
            $this->metaVox(),
            $this->publicationState(),
            $this->folders(),
            $this->logger,
            fn(\OCP\Files\Folder $folder, string $uid): ?array => $this->findPageByUniqueId($folder, $uid)
        );
    }


    /**
     * Lazy seam for the sibling reorderer (Phase "reorder"). Built from
     * locator(); the getLanguageFolder seam is resolved by the delegator and
     * passed in, and isHomepage/clearCache are passed as closures. Nullable-
     * default so the harness auto-fill skips it (see the load-bearing note).
     */
    private function reorderer(): \OCA\IntraVox\Service\Reorder\PageReorderer {
        return $this->reorderer ??= new \OCA\IntraVox\Service\Reorder\PageReorderer($this->locator());
    }

    /**
     * Lazy seam for the fresh-build page-data enricher (Phase "crud"). Built from
     * pathHelper + permissionService + the metaVox() gateway, with the three
     * seam-bound concerns (getRelativePathFromRoot, resolveTranslations,
     * groupfolderIdForNode) passed in as closures so they stay on PageService.
     * Nullable-default so the harness auto-fill skips it.
     */
    private function pageDataEnricher(): \OCA\IntraVox\Service\Path\PageDataEnricher {
        return $this->pageDataEnricher ??= new \OCA\IntraVox\Service\Path\PageDataEnricher(
            $this->pathHelper,
            $this->permissionService,
            $this->metaVox(),
            $this->folders(),
            fn(?string $group, ?string $uniqueId): array => $this->resolveTranslations($group, $uniqueId),
            $this->groupfolders
        );
    }

    /**
     * Lazy seam for the single-page reader (god-class dissolution, read cluster).
     * Built from the real read collaborators + four $this-bound closures for the
     * seams and #70-shared concerns that stay on PageService (getReadLanguageFolder,
     * getIntraVoxFolder, resolveTranslations, groupfolderIdForNode). Nullable-
     * default so the harness auto-fill skips it; the seam closures bind $this so
     * the 26 subclasses keep intercepting getReadLanguageFolder/getIntraVoxFolder.
     */
    private function readService(): \OCA\IntraVox\Service\Read\PageReadService {
        return $this->readService ??= new \OCA\IntraVox\Service\Read\PageReadService(
            $this->cache(),
            $this->locator(),
            fn(): \OCA\IntraVox\Service\Publication\MetaVoxGateway => $this->metaVox(),
            fn(): \OCA\IntraVox\Service\Path\PageDataEnricher => $this->pageDataEnricher(),
            $this->shape(),
            $this->permissionService,
            $this->idUtils,
            $this->logger,
            $this->folders(),
            fn(?string $group, ?string $uniqueId): array => $this->resolveTranslations($group, $uniqueId),
            $this->groupfolders
        );
    }

    /**
     * Lazy seam for the page mutation service (god-class dissolution, write
     * cluster). Built from the real deps; the resolved language folder and the
     * lookups/isHomepage/clearCache seams are passed per-call as arg + closures.
     * Nullable-default so the harness auto-fill skips it.
     */
    private function writeService(): \OCA\IntraVox\Service\Write\PageWriteService {
        return $this->writeService ??= new \OCA\IntraVox\Service\Write\PageWriteService(
            $this->idUtils,
            $this->eventDispatcher,
            $this->logger,
            $this->userSession,
            $this->pageVersionService,
            $this->pageIndexService,
            $this->languageService,
            $this->folders()
        );
    }

    /**
     * Lazy seam for the tree-structure service (god-class dissolution, STRUCTURE
     * domain). Built from the real deps + the FolderContext substrate; the
     * remaining cross-language lookups pass per-call as $this-bound closures.
     * Nullable-default so the harness auto-fill skips it.
     */
    private function structureService(): \OCA\IntraVox\Service\Structure\PageStructureService {
        return $this->structureService ??= new \OCA\IntraVox\Service\Structure\PageStructureService(
            $this->idUtils,
            $this->pageIndexService,
            $this->logger,
            $this->folders()
        );
    }

    /**
     * Lazy seam for the metadata projection service (METADATA domain). Built from
     * the real collaborators + the FolderContext substrate + the shared
     * PageDataEnricher (the #70 canWrite gate); page lookup + the homepage seam are
     * passed per call as $this-bound closures. Nullable-default so the harness
     * auto-fill skips it.
     */
    private function metadata(): \OCA\IntraVox\Service\Metadata\PageMetadataService {
        return $this->metadataService ??= new \OCA\IntraVox\Service\Metadata\PageMetadataService(
            $this->idUtils,
            $this->pageVersionService,
            $this->pageIndexService,
            $this->navigationService,
            $this->shape(),
            $this->folders(),
            $this->pageDataEnricher(),
            $this->logger
        );
    }

    /**
     * Lazy seam for the media orchestration service (MEDIA domain). Built from the
     * media engine + cache + FolderContext substrate + locator + id utils + media
     * sanitizer; page lookup + clearCache are passed per call as $this-bound
     * closures. Nullable-default so the harness auto-fill skips it.
     */
    private function mediaOrchestrator(): \OCA\IntraVox\Service\Media\PageMediaOrchestrator {
        return $this->mediaOrchestrator ??= new \OCA\IntraVox\Service\Media\PageMediaOrchestrator(
            $this->media(),
            $this->cache(),
            $this->folders(),
            $this->locator(),
            $this->idUtils,
            $this->mediaSanitizer
        );
    }

    /**
     * Lazy seam for the page-composition service (COMPOSE domain). Built from the
     * template/translation-group/media engines + html sanitizer + id utils +
     * FolderContext substrate + userId; createPage/getPage and the page-lookup
     * concerns are passed per call as $this-bound closures. Nullable-default so the
     * harness auto-fill skips it.
     */
    private function composition(): \OCA\IntraVox\Service\Compose\PageCompositionService {
        return $this->compositionService ??= new \OCA\IntraVox\Service\Compose\PageCompositionService(
            $this->pageTemplateService,
            $this->translationGroups(),
            $this->media(),
            $this->htmlSanitizer,
            $this->idUtils,
            $this->folders(),
            $this->userId,
            $this->logger
        );
    }

    /**
     * Lazy seam for the folder/location substrate (clean-target step 1). Built
     * from the same deps the seams already use, with the #75 real-content probe
     * (page-lookup-bound) and the getReadLanguageFolder seam injected as closures
     * so wholesale subclass overrides still win. Now the single source of folder-
     * root-relative paths, language resolution, and the read-language folder for
     * the migrated collaborators (getRelativePathFromRoot, PageTreeBuilder,
     * PageDataEnricher, PageReadService). Nullable-default AND in
     * LAZY_SEAM_SERVICES so the harness auto-fill leaves it (and the 27
     * subclasses) untouched until they opt in.
     */
    private function folders(): \OCA\IntraVox\Service\Folder\FolderContext {
        // FolderContext is now a DI-first-class service (all substrate atoms are
        // real ctor deps: rootFolder/userId/config/LanguageService/LanguageResolver/
        // PageLocator). It is ctor-injected; tests reflection-inject a fixture one.
        return $this->folderContext;
    }


    private function locator(): PageLocator {
        if (!isset($this->pageLocator)) {
            $this->pageLocator = new PageLocator($this->pageIndexService, $this->logger);
        }
        return $this->pageLocator;
    }

    /**
     * Translation-group semantics. Same seam convention as locator(): DI
     * injects it, and the constructor-less test subclasses fall back to a
     * real instance built from the dependencies they already set.
     */
    private function translationGroups(): TranslationGroupService {
        if (!isset($this->translationGroupService)) {
            $this->translationGroupService = new TranslationGroupService(
                $this->pageIndexService,
                $this->locator(),
                $this->idUtils,
                $this->logger
            );
        }
        return $this->translationGroupService;
    }

    /**
     * Page media mechanics (copy engine, _media resolution). Same lazy seam
     * convention as locator() for the constructor-less test subclasses.
     */
    private function media(): PageMediaService {
        if (!isset($this->pageMediaService)) {
            $this->pageMediaService = new PageMediaService(
                $this->locator(),
                $this->mediaSanitizer,
                $this->logger
            );
        }
        return $this->pageMediaService;
    }

    /**
     * The News widget's collect/filter engine. Same lazy seam convention as
     * locator() for the constructor-less test subclasses.
     */
    private function news(): NewsPageService {
        if (!isset($this->newsPageService)) {
            $this->newsPageService = new NewsPageService(
                $this->locator(),
                $this->permissionService,
                $this->newsContent,
                $this->logger
            );
        }
        return $this->newsPageService;
    }

    /**
     * The MetaVox DB/app-manager gateway (cluster U, Phase 3). Same lazy seam
     * convention as news()/locator(): DI does not inject it, and the accessor
     * builds the real one from the deps this service already holds. Memoised so a
     * single instance per request preserves the file->groupfolder map that
     * getMetaVoxDataForFiles() fills and searchPages() reads back.
     */
    private function metaVox(): \OCA\IntraVox\Service\Publication\MetaVoxGateway {
        if (!isset($this->metaVoxGateway)) {
            $this->metaVoxGateway = new \OCA\IntraVox\Service\Publication\MetaVoxGateway(
                $this->db,
                $this->appManager,
                $this->userId,
                $this->logger
            );
        }
        return $this->metaVoxGateway;
    }

    /**
     * The publication scheduling logic (cluster U, Phase 3). Same lazy seam
     * convention; reuses the metaVox() instance so its memos stay shared.
     */
    private function publicationState(): \OCA\IntraVox\Service\Publication\PublicationStateService {
        if (!isset($this->publicationStateSvc)) {
            $this->publicationStateSvc = new \OCA\IntraVox\Service\Publication\PublicationStateService(
                $this->publicationSettings,
                $this->config,
                $this->userSession,
                $this->metaVox()
            );
        }
        return $this->publicationStateSvc;
    }

    /**
     * The CLI maintenance operations (repair/reindex, Phase 4). Same lazy seam
     * convention; built from the deps this service already holds.
     */
    private function maintenance(): \OCA\IntraVox\Service\Maintenance\PageMaintenanceService {
        if (!isset($this->maintenanceSvc)) {
            $this->maintenanceSvc = new \OCA\IntraVox\Service\Maintenance\PageMaintenanceService(
                $this->pageIndexService,
                $this->locator(),
                $this->htmlSanitizer,
                $this->logger
            );
        }
        return $this->maintenanceSvc;
    }

    /**
     * Get the user's TRUE intranet language (base code) from their Nextcloud
     * language preference, e.g. 'nl_NL' -> 'nl', 'da' -> 'da'.
     *
     * VoxCloud language model: we return the user's actual language and do NOT
     * silently remap it to English here. Two consumers rely on this:
     *   - getLanguageFolder() resolves the content folder and falls back to the
     *     English folder itself when the user's language folder is absent, so a
     *     language without content still renders *something*.
     *   - getLanguageContentStatus() needs the real language to detect "the
     *     user's language has no content" and drive the fallback notice. The old
     *     enabled_languages remap broke that: a Danish user was reported as
     *     English, so the notice never showed.
     */
    private function getUserLanguage(): string {
        return $this->folders()->userLanguage();
    }

    /**
     * Resolve which page is the homepage for a language (configurable homepage).
     *
     * Returns the configured pointer target if set AND it resolves to a real
     * page; otherwise falls back to the legacy loose `home.json` (uniqueId
     * 'home' / the page at the language root). This fallback is the entire
     * back-compat story: installs without a homepage.json behave exactly as
     * before.
     *
     * @return string uniqueId of the homepage ('home' for the legacy default).
     */
    public function getHomepageUniqueId(?string $language = null): string {
        return $this->homepageResolver()->getHomepageUniqueId($language);
    }

    /**
     * Whether the given uniqueId is the resolved homepage for the language.
     * Handles the legacy 'home' id as well as a configured pointer target.
     */
    public function isHomepage(string $uniqueId, ?string $language = null): bool {
        if ($uniqueId === '') {
            return false;
        }
        return $uniqueId === $this->resolveHomepageNodeUniqueId($language);
    }

    /**
     * The concrete uniqueId (page-…) of the homepage for a language, suitable
     * for badging/comparison in the UI. When a pointer is set it is that
     * uniqueId; otherwise it resolves the legacy loose home.json to its real
     * uniqueId (not the literal 'home'). Optionally pass an already-built tree
     * to resolve the legacy home from it without an extra read.
     *
     * @param array<int,array>|null $tree Optional pre-built page tree.
     */
    public function resolveHomepageNodeUniqueId(?string $language = null, ?array $tree = null): string {
        return $this->homepageResolver()->resolveHomepageNodeUniqueId($language, $tree);
    }

    /**
     * Create a simple .nomedia marker for the _media folder
     * The folder name "_media" itself is the primary identifier
     */
    private function createMediaFolderMarker($mediaFolder): void {
        $this->media()->createMediaFolderMarker($mediaFolder);
    }

    /**
     * The language whose content the CURRENT user will actually be SHOWN on the
     * landing/read paths. Read-only resolution — NEVER used to decide where to
     * write (authoring must always target the user's own language folder).
     *
     * Order (issue #75):
     *   1. the user's own display language, if it has real content
     *   2. the admin "recommended" (primary) language, if it has real content
     *      and differs from the user's language — this is what the admin
     *      settings promise: "if there is none, they are shown the recommended
     *      language below"
     *   3. English ('en'), if it has real content
     *   4. null — nothing can be served (pure other-language install) → notice
     *
     * "Has real content" = languageFolderHasRealContent (a homepage that is not
     * a _generated placeholder), matching how languagesWithContent is built, so
     * a non-null result is always one of languagesWithContent. primaryLanguage
     * already defaults to 'en', so when unset the chain collapses to user → en.
     */
    private function resolveEffectiveLanguage(): ?string {
        return $this->folders()->effectiveLanguage();
    }

    /**
     * Which language content folder does $folder sit in?
     *
     * Walks up from $folder to the IntraVox root and returns the top-level
     * segment when it is a language code. Used to record where a page really
     * landed rather than assuming the author's own language.
     *
     * @return string|null the language code, or null when $folder is outside
     *   the IntraVox tree or is the tree root itself.
     */
    private function languageOfFolder(\OCP\Files\Folder $folder): ?string {
        return $this->folders()->languageOfFolder($folder);
    }

    /**
     * Locate a page by uniqueId across every language folder that exists on
     * disk, starting with $primaryFolder.
     *
     * Reading and writing used to resolve the language folder differently:
     * getPage() searched the *effective* language (recommended-language
     * fallback, #75) and then every other language folder, while the write
     * paths searched only the folder for the user's own display language. Any
     * page that IntraVox could render but that lived outside the user's own
     * language folder was therefore impossible to save — the save failed with
     * "Page not found" on a page that was visibly on screen (issue #90).
     *
     * Both sides now locate pages through here. This decides only WHERE AN
     * EXISTING PAGE LIVES, never where a NEW page is created: creation still
     * targets the user's own language folder via getLanguageFolder(). Callers
     * that write remain responsible for permissions — the file returned here
     * is still subject to the isUpdateable() check on the caller's side.
     *
     * @param \OCP\Files\Folder $primaryFolder Folder to search first.
     * @param string $uniqueId The page-… uniqueId to locate.
     * @return array|null findPageByUniqueId() result, or null when unknown.
     */
    private function locatePageAnyLanguage(\OCP\Files\Folder $primaryFolder, string $uniqueId): ?array {
        return $this->locator()->locatePageAnyLanguage($this->rootClosure(), $primaryFolder, $uniqueId);
    }

    private function locateViaIndex(string $uniqueId, \OCP\Files\Folder $primaryFolder): ?array {
        return $this->locator()->locateViaIndex($this->rootClosure(), $uniqueId, $primaryFolder);
    }

    private function indexPathToRelative(string $storedPath): ?string {
        return $this->locator()->indexPathToRelative($this->folders()->intraVox(), $storedPath);
    }

    private function locatePageBySlugAnyLanguage(\OCP\Files\Folder $primaryFolder, string $id): ?array {
        return $this->locator()->locatePageBySlugAnyLanguage($this->rootClosure(), $primaryFolder, $id);
    }

    /**
     * The language content folder that a findPageByUniqueId()/findPageById()
     * result sits in, derived from the page folder's own path. Kept on PageService
     * because movePage still leans on it (the media orchestrator carries its own
     * copy); walks up from the page folder to the language folder.
     *
     * @return \OCP\Files\Folder|null null when the path cannot be resolved.
     */
    private function languageFolderOfPageResult(array $result): ?\OCP\Files\Folder {
        $folder = $result['folder'] ?? null;
        if (!($folder instanceof \OCP\Files\Folder)) {
            return null;
        }

        $language = $this->languageOfFolder($folder);
        if ($language === null) {
            return null;
        }

        try {
            $candidate = $this->folders()->intraVox()->get($language);
            return $candidate instanceof \OCP\Files\Folder ? $candidate : null;
        } catch (NotFoundException $e) {
            return null;
        }
    }

    /**
     * Locate an existing page by uniqueId OR legacy slug, across every language
     * folder. The plain "find this page, wherever and however it is addressed"
     * lookup.
     *
     * Several operations each open-coded a subset of this and got a different
     * subset wrong: some tried the uniqueId branch but not the slug branch,
     * some (updateVersionLabel, getCurrentPageContent) had no uniqueId branch at
     * all and so failed on every modern page-… id, and none of them looked
     * outside the caller's own language. Routing them through one helper is what
     * stops that drift.
     *
     * Read-only resolution: callers that write still check permissions on the
     * node they get back.
     *
     * @return array|null findPageByUniqueId()/findPageById() result, or null.
     */
    private function locatePageForOperation(string $pageId): ?array {
        $folder = $this->folders()->readLanguageFolder();

        if (strpos($pageId, 'page-') === 0) {
            $byUniqueId = $this->locatePageAnyLanguage($folder, $pageId);
            if ($byUniqueId !== null) {
                return $byUniqueId;
            }
        }

        return $this->locatePageBySlugAnyLanguage($folder, $this->idUtils->sanitizeId($pageId));
    }

    /**
     * Human-readable name for a language code ('en' -> 'English'), for messages
     * a user reads. Falls back to the uppercased code when the name is unknown,
     * so an exotic content folder still produces "EO" rather than nothing.
     *
     * Reuses LanguageService::getAvailableLanguages(), the same source the
     * admin Languages tab and the fallback notice display.
     */
    private function languageDisplayName(string $code): string {
        try {
            foreach ($this->languageService->getAvailableLanguages() as $lang) {
                if (($lang['code'] ?? '') === $code) {
                    $name = $lang['name'] ?? '';
                    if ($name === '') {
                        return strtoupper($code);
                    }
                    // Nextcloud's names describe INTERFACE translations and
                    // carry variant suffixes ('English (US)', 'Deutsch
                    // (Persönlich: Du)'). A content folder is a plain code, so
                    // drop the parenthesised part — "this page is in Deutsch
                    // (Persönlich: Du)" is nonsense to a reader.
                    $base = trim(explode('(', $name)[0]);
                    return $base !== '' ? $base : $name;
                }
            }
        } catch (\Throwable $e) {
            // Naming is cosmetic; never let it break the operation's real error.
        }
        return strtoupper($code);
    }


    /**
     * Get language folder by language code
     */
    private function getLanguageFolderByCode(string $lang) {
        return $this->folders()->languageFolderByCode($lang);
    }


    /**
     * The "where is the IntraVox root?" question, as a late-bound closure the
     * folder-shaped collaborators (PageLocator, PageLister, …) need.
     *
     * A single home for what was six identical `fn() => $this->getIntraVoxFolder()`
     * call-sites. Now routes through the FolderContext substrate — folders()->
     * intraVox() resolves the same getIntraVoxFolder seam (bound as a $this-closure
     * in folders()), so a subclass override still wins, but the last locate-family
     * consumers of the raw seam now go through the one front door.
     */
    private function rootClosure(): \Closure {
        return fn() => $this->folders()->intraVox();
    }

    /**
     * Public method to check if a page exists by uniqueId
     * Used by CommentsEntityListener to validate comment objectIds
     */
    public function pageExistsByUniqueId(string $uniqueId): bool {
        try {
            $folder = $this->folders()->readLanguageFolder();
            return $this->findPageByUniqueId($folder, $uniqueId) !== null;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Recursively find a page by uniqueId
     */
    private function findPageByUniqueId($folder, string $uniqueId, $languageFolder = null): ?array {
        return $this->locator()->findPageByUniqueId($folder, $uniqueId, $languageFolder);
    }

    private function findPageById($folder, string $id): ?array {
        return $this->locator()->findPageById($folder, $id);
    }

    /**
     * List all pages (recursively)
     */
    public function listPages(): array {
        return $this->pageLister()->listAll();
    }

    /**
     * Link two pages as language versions of each other.
     *
     * Both pages end up sharing one translation group. Symmetric by design:
     * neither becomes the "source", so removing either one later shrinks the
     * group instead of orphaning the other — the failure mode that leaves
     * SharePoint's source-pointer model with dangling references.
     *
     * Refuses to link two pages in the SAME language: a group holds at most one
     * page per language, and allowing a second would make "the German version"
     * ambiguous for the switcher and the reader notice alike.
     *
     * When either page is already linked, the existing group wins and the other
     * page joins it, so linking A→B and later B→C leaves all three together
     * rather than splitting into two pairs.
     *
     * @throws PageNotFoundException when either page cannot be found
     * @throws \InvalidArgumentException when both pages share a language
     */
    public function linkTranslation(string $uniqueIdA, string $uniqueIdB): string {
        return $this->translationQuery()->linkTranslation(
            $uniqueIdA,
            $uniqueIdB,
            function (array $result, string $group): void {
                $this->writeTranslationGroup($result, $group);
            },
            function (): void {
                $this->clearCache();
            }
        );
    }

    /**
     * Create this page in another language and link the two.
     *
     * The entry point an editor actually wants: "make this page in German".
     * Linking two pages that already exist is the rarer case — normally the
     * other version does not exist yet, and asking an editor to first create a
     * blank page elsewhere, find it, and then link it is the workflow every
     * mature CMS avoids. SharePoint's Translation button, Drupal's Translate
     * tab and WPML's "+" all do exactly this in one step.
     *
     * The copy is a STARTING POINT, not a synchronised mirror: content is
     * copied once, and from then on the two pages are independent. The German
     * page may gain a widget the English one does not have. WPML's translation
     * editor enforces structural parity and overwrites a diverging layout;
     * Polylang free starts from a blank page and makes the editor rebuild it.
     * This is the middle neither offers.
     *
     * Lands as a DRAFT: a machine-made copy in the wrong language is not
     * something readers should meet before an editor has been through it.
     *
     * @param string $sourceUniqueId page to translate
     * @param string $language target language code
     * @param string|null $title title for the new page (defaults to the source's)
     * @return array the created page
     * @throws PageNotFoundException when the source does not exist
     * @throws \InvalidArgumentException when the target language is invalid,
     *   is the source's own, or already holds a version of this page
     */
    public function createTranslation(
        string $sourceUniqueId,
        string $language,
        ?string $title = null
    ): array {
        // Body lives in Compose/PageCompositionService (COMPOSE domain). createPage
        // + page lookup + writeTranslationGroup + clearCache go in as $this-bound
        // closures so subclasses keep intercepting and #70 stays on create/read.
        return $this->composition()->createTranslation(
            $sourceUniqueId,
            $language,
            $title,
            fn(array $data, ?string $parentPath = null): array => $this->createPage($data, $parentPath),
            fn(\OCP\Files\Folder $folder, string $uid): ?array => $this->locatePageAnyLanguage($folder, $uid),
            fn(string $id): ?\OCP\Files\Folder => $this->findPageFolder($id),
            function (array $result, string $group): void {
                $this->writeTranslationGroup($result, $group);
            },
            function (): void {
                $this->clearCache();
            }
        );
    }

    /**
     * Languages this page could still be created in.
     *
     * A language qualifies when it has a content folder, is not the page's own,
     * and does not already hold a version of this page. Offering anything else
     * would produce a control that fails when used.
     *
     * @return array<int, array{code:string, name:string}>
     */
    public function getTranslatableLanguages(string $pageId): array {
        return $this->translationQuery()->getTranslatableLanguages($pageId);
    }

    /**
     * Pages this page could be linked to as a translation.
     *
     * Excludes three sets, each for a reason:
     *   - the page's own language, since a group holds one page per language;
     *   - pages already in a group with something else, so linking cannot
     *     silently steal a page out of an existing set;
     *   - the page itself.
     *
     * Answered from the index, so the picker stays cheap on a large intranet.
     *
     * @param string|null $language limit to one language, or null for all others
     * @return array<int, array{uniqueId:string, title:string, language:string}>
     */
    public function getTranslationCandidates(string $pageId, ?string $language = null): array {
        return $this->translationQuery()->getTranslationCandidates($pageId, $language);
    }

    /**
     * Detach a page from its translation group.
     *
     * The page gets a fresh group of its own rather than none at all, so
     * "linked" and "unlinked" stay the same shape and the page can be linked
     * again later without a special case.
     *
     * Only ever touches the page asked for. WPML shipped a bug where an update
     * silently re-linked translations an editor had deliberately unlinked;
     * nothing here infers a relationship from similarity.
     *
     * @throws PageNotFoundException when the page cannot be found
     */
    public function unlinkTranslation(string $uniqueId): string {
        return $this->translationQuery()->unlinkTranslation(
            $uniqueId,
            function (array $result, string $group): void {
                $this->writeTranslationGroup($result, $group);
            },
            function (): void {
                $this->clearCache();
            }
        );
    }

    /**
     * Write a translation group into a page file and its index row.
     *
     * @param array $result findPageByUniqueId()-shaped result
     */
    private function writeTranslationGroup(array $result, string $group): void {
        $language = $this->languageOfFolder($result['folder']) ?? $this->getUserLanguage();
        $this->translationGroups()->writeGroup($result, $group, $language);
    }

    /**
     * The other language versions of a page, from its translation group.
     *
     * Answered entirely from the index — one lookup, no tree walk — which is
     * what makes it cheap enough to attach to every page render.
     *
     * Returns [] rather than throwing on any problem: this decorates a page,
     * and a missing switcher is a far smaller failure than a page that will
     * not load. Also returns [] for a page with no group, which is the normal
     * state of every page that is not linked to another language.
     *
     * @return array<int, array{language:string, uniqueId:string, title:string, status:string}>
     */
    private function resolveTranslations(?string $translationGroup, ?string $ownUniqueId): array {
        return $this->translationGroups()->resolveTranslations(
            $translationGroup,
            $ownUniqueId,
            $this->rootClosure()
        );
    }


    /**
     * Whether a language folder holds a REAL (editor-authored) homepage, as
     * opposed to an auto-generated placeholder or no homepage at all.
     *
     * A homepage counts as real when `home.json` exists, parses, and does NOT
     * carry the `_generated` marker written by LanguageHomepageService /
     * demo-data. The marker is dropped on the first editor save, so any edited
     * homepage reads as real. Homepages from installs predating the marker also
     * read as real (no marker present) — which is the safe, no-regression
     * default.
     */
    private function languageFolderHasRealContent(\OCP\Files\Folder $langFolder): bool {
        return $this->folders()->hasRealContent($langFolder);
    }

    /**
     * Whether a language folder has a homepage AT ALL — real OR an auto/placeholder
     * one (`_generated`). This is the "active language" signal: a language an admin
     * added via "Add language" has a placeholder homepage and should show up as an
     * active intranet language even before an editor fills it.
     */
    private function languageFolderHasHomepage(\OCP\Files\Folder $langFolder): bool {
        return $this->folders()->hasHomepage($langFolder);
    }

    /**
     * Language content status for the CURRENT user. Drives the landing-page
     * fallback notice and is the "active = where content is" signal for the
     * VoxCloud language model (replaces the enabled_languages opt-in list).
     *
     * Two distinct sets:
     *   - languagesWithContent: only REAL (editor-authored) homepages. The
     *     fallback notice uses this so a placeholder doesn't mask "no content".
     *   - activeLanguages: every language with ANY homepage (incl. an added
     *     placeholder). The admin "Languages with content" chips use this so a
     *     just-added language appears immediately.
     *
     * @return array{
     *   language: string,
     *   hasContent: bool,
     *   servedLanguage: ?string,
     *   languagesWithContent: string[],
     *   activeLanguages: string[]
     * }
     */
    public function getLanguageContentStatus(): array {
        // The content-status read (language-folder scan, real-vs-placeholder
        // split, #75 served-language + homepage resolution) lives in the
        // LANGUAGE-STATUS domain service. Homepage resolution and the real-
        // content probe stay here (shared subsystem / FolderContext-circular
        // seam) and are passed as $this-bound closures.
        return $this->languageStatus()->getContentStatus(
            fn(?string $language): string => $this->resolveHomepageNodeUniqueId($language),
            fn(\OCP\Files\Folder $folder): bool => $this->languageFolderHasHomepage($folder),
            fn(\OCP\Files\Folder $folder): bool => $this->languageFolderHasRealContent($folder)
        );
    }

    /**
     * Number of pages per language folder (base code => count). Used by the
     * admin "remove language" confirmation so it can warn how many pages would
     * be deleted. Counts the homepage plus every `{name}/{name}.json` subpage.
     *
     * @return array<string,int>
     */
    public function getPageCountByLanguage(): array {
        return $this->languageStatus()->getPageCountByLanguage();
    }

    /**
     * List all pages with full content (including layout)
     * OPTIMIZED: Single filesystem traversal for search operations
     * This eliminates the N+1 query pattern where listPages() + getPage() for each
     */
    public function listPagesWithContent(): array {
        return $this->pageLister()->listAllWithContent();
    }

    /**
     * Get a specific page by uniqueId or legacy id
     */
    public function getPage(string $id): array {
        // The single-page read (resolution, #70 cache-hit recompute + strip,
        // enrich + sanitize) lives in Read/PageReadService — the first service
        // carved out of the god-class. The delegator supplies the folder seams
        // and the two #70-shared concerns as $this-bound closures so the 26
        // seam-subclasses keep intercepting.
        return $this->readService()->getPage($id);
    }


    /**
     * Calculate nesting depth from path
     *
     * Base paths (depth 0):
     * - nl/public/ (public pages)
     * - nl/departments/{dept}/ (department pages)
     */

    /**
     * Get maximum allowed depth for a given path
     */
    private function getMaxDepthForPath(string $path): int {
        $pathParts = explode('/', trim($path, '/'));

        // Remove language if present. Uses the available (= every NC-known)
        // language set so paths in any language an admin added (e.g. 'da') get
        // correct depth math, not only the ones IntraVox ships a translation for.
        if (count($pathParts) > 0 && $this->languageService->isLanguageAvailable($pathParts[0])) {
            array_shift($pathParts);
        }

        // Public pages: max depth 5
        if (count($pathParts) > 0 && $pathParts[0] === 'public') {
            return 5;
        }

        // Department pages: max depth 5
        if (count($pathParts) > 0 && $pathParts[0] === 'departments') {
            return 5;
        }

        // Default: max depth 5
        return 5;
    }

    /**
     * Validate that creating a child page at the given path wouldn't exceed max depth
     */
    private function validateDepth(string $parentPath): void {
        $currentDepth = $this->pathHelper->calculateDepth($parentPath);
        $maxDepth = $this->getMaxDepthForPath($parentPath);

        if ($currentDepth >= $maxDepth) {
            throw new \InvalidArgumentException(
                "Cannot create child page: maximum nesting depth of {$maxDepth} would be exceeded"
            );
        }
    }

    /**
     * Determine page type based on path and structure
     *
     * @return string 'department'|'container'|'page'
     */

    /**
     * Get breadcrumb trail for a page
     *
     * Returns array of breadcrumb items from home to current page
     */
    public function getBreadcrumb(string $pageId): array {
        $page = $this->getPage($pageId);
        $language = $this->folders()->userLanguage();

        // The configured-homepage pointer check, evaluated here so the builder
        // stays free of PageService seams (it keeps the same !empty() guard).
        $isHomepagePointer = !empty($page['uniqueId'])
            && $this->isHomepage((string)$page['uniqueId'], $language);

        $readFolder = null;
        try {
            $readFolder = $this->folders()->readLanguageFolder();
        } catch (\Exception $e) {
            // no folder — builder falls back to the 'Home' label
        }

        return (new \OCA\IntraVox\Service\Path\BreadcrumbBuilder($this->languageService))->build(
            $pageId,
            $page,
            $language,
            $isHomepagePointer,
            $readFolder,
            fn(string $folderPath): ?array => $this->findPageByFolderPath($folderPath)
        );
    }

    /**
     * Find a page by its folder path relative to IntraVox root
     *
     * @param string $folderPath e.g., "en/departments" or "en/departments/marketing"
     * @return array|null Page data or null if not found
     */
    private function findPageByFolderPath(string $folderPath): ?array {
        return $this->pageLister()->byFolderPath($folderPath);
    }

    /**
     * Get or create folder path recursively
     * Example: "nl/departments/marketing/campaigns" will create all intermediate folders
     *
     * A sub-page belongs in its PARENT's language folder, not in the author's
     * own. When the path names a language, that language wins: an English
     * editor adding a page under a German parent writes into de/, exactly where
     * the parent lives. Previously the language segment was stripped and the
     * remainder re-created under the author's own language, which fabricated an
     * empty mirror tree (de/departments/marketing/) whose parent pages did not
     * exist there — the created page vanished from the context it was made in.
     *
     * Mirrors resolveExistingFolderPath() — keep the two in step.
     */
    private function getOrCreateFolderPath(string $path): \OCP\Files\Folder {
        $pathParts = explode('/', trim($path, '/'));

        // A leading language segment selects the content folder to build in.
        // Fall back to the author's own language folder when the path carries
        // no language (legacy callers) or when that language has no folder yet.
        $currentFolder = null;
        if (count($pathParts) > 0 && $this->languageService->isLanguageAvailable($pathParts[0])) {
            $langCode = array_shift($pathParts);
            try {
                $candidate = $this->folders()->intraVox()->get($langCode);
                if ($candidate instanceof \OCP\Files\Folder) {
                    $currentFolder = $candidate;
                }
            } catch (NotFoundException $e) {
                // No folder for that language — fall through to the author's own.
            }
        }
        if ($currentFolder === null) {
            $currentFolder = $this->folders()->languageFolder();
        }

        // Create each folder in path if it doesn't exist
        foreach ($pathParts as $folderName) {
            try {
                $currentFolder = $currentFolder->get($folderName);
                if ($currentFolder->getType() !== \OCP\Files\FileInfo::TYPE_FOLDER) {
                    throw new \InvalidArgumentException("Path component '{$folderName}' exists but is not a folder");
                }
            } catch (NotFoundException $e) {
                $currentFolder = $currentFolder->newFolder($folderName);
            }
        }

        return $currentFolder;
    }

    /**
     * Create a new page
     *
     * @param array $data Page data (id, title, content, etc.)
     * @param string|null $parentPath Optional parent path for nested pages (e.g., "nl/departments/marketing")
     * @return array Created page data
     */
    public function createPage(array $data, ?string $parentPath = null): array {
        // The create body (validation, slug-dedup, group minting, write) lives in
        // Write/PageWriteService (AUTHOR domain). Folder-substrate concerns come
        // from the injected FolderContext; getOrCreateFolderPath (reflection-
        // anchored) and validateDepth (shared with movePage) stay on PageService
        // and are passed as closures.
        return $this->writeService()->createPage(
            $data,
            $parentPath,
            fn(array $page): array => $this->validateAndSanitizePage($page),
            function (?string $pageId = null): void {
                $this->clearCache($pageId);
            },
            fn(string $path): \OCP\Files\Folder => $this->getOrCreateFolderPath($path),
            function (string $path): void {
                $this->validateDepth($path);
            },
            function (\OCP\Files\Node $mediaFolder): void {
                $this->createMediaFolderMarker($mediaFolder);
            },
            function (string $uniqueId, \OCP\Files\Folder $pageFolder): void {
                $this->cache()->setPageFolder($uniqueId, $pageFolder);
            }
        );
    }

    /**
     * Update an existing page
     */
    public function updatePage(string $id, array $data): array {
        // The update body lives in Write/PageWriteService (write cluster). The
        // language folder goes in as a CLOSURE (resolved inside, after the !$user
        // guard — matching the pre-carve monolith). Folder-substrate concerns
        // (languageFolder / languageOfFolder / userLanguage) come from FolderContext;
        // the page lookups + validateAndSanitizePage + clearCache go in as closures
        // so the seam-subclasses keep intercepting. clearCache here forwards the
        // page id (unlike deletePage's arg-less call).
        return $this->writeService()->updatePage(
            $id,
            $data,
            fn(): \OCP\Files\Folder => $this->folders()->languageFolder(),
            fn(\OCP\Files\Folder $folder, string $uid): ?array => $this->locatePageAnyLanguage($folder, $uid),
            fn(\OCP\Files\Folder $folder, string $legacyId): ?array => $this->findPageById($folder, $legacyId),
            fn(\OCP\Files\Folder $folder): ?string => $this->folders()->languageOfFolder($folder),
            fn(): string => $this->folders()->userLanguage(),
            fn(array $page): array => $this->validateAndSanitizePage($page),
            function (?string $pageId = null): void {
                $this->clearCache($pageId);
            }
        );
    }

    /**
     * Delete a page and all its assets
     */
    public function deletePage(string $id): void {
        // The delete body lives in Write/PageWriteService (god-class dissolution,
        // write cluster). The language folder goes in as a CLOSURE (not resolved
        // here) so PageWriteService can fire its $id==='home' guard before
        // resolving — matching the pre-carve monolith, which checked 'home' before
        // touching getLanguageFolder(). The cross-language lookups + isHomepage +
        // clearCache go in as closures so the seam-subclasses keep intercepting.
        $this->writeService()->deletePage(
            $id,
            fn(): \OCP\Files\Folder => $this->folders()->languageFolder(),
            fn(\OCP\Files\Folder $folder, string $uid): ?array => $this->locatePageAnyLanguage($folder, $uid),
            fn(\OCP\Files\Folder $folder, string $legacyId): ?array => $this->findPageById($folder, $legacyId),
            fn(string $uid): bool => $this->isHomepage($uid),
            function (): void {
                $this->clearCache();
            }
        );
    }

    /**
     * Move a page (with its whole subtree) under a different parent (issue #69).
     *
     * The page keeps its uniqueId — so internal links and URLs by uniqueId stay
     * valid — while its folder is relocated into the target parent's folder.
     * Children ride along inside the moved folder. On a folder-name collision at
     * the destination the folder is given a `-2`/`-3` suffix (mirrors createPage);
     * the uniqueId is untouched.
     *
     * @param string $pageId       uniqueId (or legacy id) of the page to move.
     * @param string $targetParentId uniqueId of the destination parent; '' = root.
     * @throws \InvalidArgumentException On home, self/descendant cycles, depth.
     * @throws \Exception When source or target cannot be located.
     */
    /**
     * Set a root-level page as the homepage for the current language
     * (issue: configurable homepage). Validates the page exists AND sits at the
     * language root; lazily normalizes a still-loose home.json into a folder page
     * first so the old homepage becomes reorderable; then writes the pointer.
     *
     * @throws \InvalidArgumentException When the page is unknown or not at root.
     */
    public function setHomepage(string $uniqueId): void {
        $this->homepageResolver()->setHomepage($uniqueId);
    }

    public function movePage(string $pageId, string $targetParentId): void {
        // The move body lives in Structure/PageStructureService (STRUCTURE domain).
        // Folder-substrate concerns come from the injected FolderContext; the
        // remaining cross-language lookups are handed in as $this-bound closures so
        // the seam-subclasses keep intercepting; the guards (#90 cross-language,
        // HOMEPAGE_PROTECTED, cycle, depth) and the index repath ride along in the
        // moved body.
        $this->structureService()->movePage(
            $pageId,
            $targetParentId,
            fn(\OCP\Files\Folder $folder, string $uid): ?array => $this->locatePageAnyLanguage($folder, $uid),
            fn(\OCP\Files\Folder $folder, string $slug): ?array => $this->locatePageBySlugAnyLanguage($folder, $slug),
            fn(\OCP\Files\Folder $folder, string $legacyId): ?array => $this->findPageById($folder, $legacyId),
            fn(\OCP\Files\Folder $folder, string $uid): ?array => $this->findPageByUniqueId($folder, $uid),
            fn(array $result): ?\OCP\Files\Folder => $this->languageFolderOfPageResult($result),
            fn(string $uid): bool => $this->isHomepage($uid),
            fn(string $code): string => $this->languageDisplayName($code),
            function (string $path): void {
                $this->validateDepth($path);
            },
            function (): void {
                $this->clearCache();
            }
        );
    }

    /**
     * Upload media (image or video) for a specific page
     * Unified endpoint that stores all media in a single '_media' folder
     */
    public function uploadMedia(string $pageId, array $file): string {
        return $this->mediaOrchestrator()->uploadMedia(
            $pageId,
            $file,
            function (string $mediaPageId): void {
                $this->clearCache($mediaPageId);
            }
        );
    }

    /**
     * Get media (image or video) for a specific page
     * Unified endpoint that serves all media from a single '_media' folder
     */
    public function getMedia(string $pageId, string $filename) {
        return $this->mediaOrchestrator()->getMedia(
            $pageId,
            $filename
        );
    }

    /**
     * Sanitize page ID
     */

    /**
     * @see PageShapeSanitizer::validateAndSanitizePage()
     */
    private function validateAndSanitizePage(array $data): array {
        return $this->shape()->validateAndSanitizePage($data);
    }

    /**
     * @see PageShapeSanitizer::sanitizeViewerFilters()
     */
    private function sanitizeViewerFilters($raw, string $fieldPattern): array {
        return $this->shape()->sanitizeViewerFilters($raw, $fieldPattern);
    }

    /**
     * @see PageShapeSanitizer::sanitizeWidget()
     */
    private function sanitizeWidget(array $widget): ?array {
        return $this->shape()->sanitizeWidget($widget);
    }



    /**
     * @see PageShapeSanitizer::sanitizeFolderPath()
     */
    private function sanitizeFolderPath(string $path): string {
        return $this->shape()->sanitizeFolderPath($path);
    }

    /**
     * @see PageShapeSanitizer::sanitizePath()
     */
    private function sanitizePath(string $path): string {
        return $this->shape()->sanitizePath($path);
    }

    /**
     * Map of video platform domains to their embed domains.
     * When a user enters youtube.com, the frontend converts it to youtube-nocookie.com.
     * This mapping allows the whitelist check to recognize both.
     */
    private const VIDEO_DOMAIN_ALIASES = [
        // YouTube watch URLs → youtube-nocookie.com embed
        'www.youtube.com' => 'www.youtube-nocookie.com',
        'youtube.com' => 'www.youtube-nocookie.com',
        'm.youtube.com' => 'www.youtube-nocookie.com',
        // Vimeo watch URLs → player.vimeo.com embed
        'www.vimeo.com' => 'player.vimeo.com',
        'vimeo.com' => 'player.vimeo.com',
    ];

    /**
     * Base domains whose subdomains are ALL allowed when the base domain is on
     * the whitelist. Needed for providers that give each customer/space its own
     * subdomain — e.g. mave.io serves iframes from space-{hash}.video-dns.com,
     * so a single fixed allowlist entry can never match every space.
     *
     * Matching is boundary-safe (see sanitizeVideoEmbedUrl): the host must equal
     * the base OR end with '.' . $base, so evilvideo-dns.com and
     * video-dns.com.attacker.com are NOT matched.
     *
     * Keep this in sync with WILDCARD_VIDEO_DOMAINS in
     * src/components/WidgetEditor.vue (frontend Save-gate).
     */
    private const WILDCARD_VIDEO_DOMAINS = ['video-dns.com'];

    /**
     * @see PageShapeSanitizer::sanitizeVideoEmbedUrl()
     */
    private function sanitizeVideoEmbedUrl(string $url): string {
        return $this->shape()->sanitizeVideoEmbedUrl($url);
    }

    /**
     * Get all versions of a page
     * Uses the standard IVersionManager interface for reliable version retrieval.
     * @throws \Exception if page not found
     */
    public function getPageVersions(string $pageId): array {
        return $this->versionDomain()->getPageVersions($pageId);
    }

    /**
     * Find a file by its ID within a folder
     */
    private function findFileByIdInFolder(\OCP\Files\Folder $folder, int $fileId): ?\OCP\Files\File {
        return $this->locator()->findFileByIdInFolder($folder, $fileId);
    }

    /**
     * Restore a specific version of a page
     * Uses IVersionManager for reliable version restoration across all storage types.
     * @throws \Exception if page or version not found
     */
    public function restorePageVersion(string $pageId, int $timestamp): array {
        return $this->versionDomain()->restorePageVersion($pageId, $timestamp);
    }


    /**
     * Get the actual file ID from the database using the groupfolder storage
     *
     * This is necessary because $file->getId() may return the user mount file ID
     * instead of the groupfolder storage file ID that MetaVox needs.
     *
     * @param \OCP\Files\File $file The file object
     * @param \OCP\Files\Folder $folder The parent folder
     * @return int The actual file ID from the groupfolder storage
     */

    /**
     * Get metadata for a page (simplified version using already loaded page data)
     */
    public function getPageMetadata(string $pageId): array {
        // Body lives in Metadata/PageMetadataService (METADATA domain). Folder
        // concerns come from the injected FolderContext; page lookup + the
        // homepage seam go in as $this-bound closures so subclasses keep
        // intercepting.
        return $this->metadata()->getPageMetadata(
            $pageId,
            fn(\OCP\Files\Folder $folder, string $uid): ?array => $this->locatePageAnyLanguage($folder, $uid),
            fn(\OCP\Files\Folder $folder, string $legacyId): ?array => $this->findPageById($folder, $legacyId),
            fn(string $uid, ?string $language = null): bool => $this->isHomepage($uid, $language)
        );
    }

    /**
     * Update page metadata (title only for now, similar to Files rename)
     */
    public function updatePageMetadata(string $pageId, array $metadata): array {
        // Body lives in Metadata/PageMetadataService (METADATA domain). Folder
        // concerns come from the injected FolderContext; page lookup + the
        // homepage seam + clearCache go in as $this-bound closures.
        return $this->metadata()->updatePageMetadata(
            $pageId,
            $metadata,
            fn(\OCP\Files\Folder $folder, string $uid): ?array => $this->locatePageAnyLanguage($folder, $uid),
            fn(\OCP\Files\Folder $folder, string $legacyId): ?array => $this->findPageById($folder, $legacyId),
            fn(string $uid, ?string $language = null): bool => $this->isHomepage($uid, $language),
            function (): void {
                $this->clearCache();
            }
        );
    }


    /**
     * One-off repair for data corrupted by the old sanitizeText(), which
     * HTML-encoded plain-text fields (title "Collega's" was stored as
     * "Collega&apos;s", "A & B" as "A &amp; B", …). Walks every page JSON in
     * every language folder and decodes the entity-encoded plain-text fields
     * (page title, widget content/alt/title, link titles), then rewrites the
     * file. Idempotent — already-clean text decodes to itself.
     *
     * @param bool $dryRun When true, count changes but do not write.
     * @return array{scanned:int, changed:int, files:string[]} Repair stats.
     */
    public function repairEntities(bool $dryRun = false): array {
        return $this->maintenance()->repairEntities($this->folders()->intraVox(), $dryRun);
    }

    /**
     * Rebuild the page index from the filesystem, which is the source of truth.
     *
     * The index is a derived structure: pages live as JSON on disk, and the
     * index only exists so lookups do not have to walk that tree. Anything
     * that writes page files outside the service — a restore, a manual copy in
     * the Files app, an `occ files:scan`, an older IntraVox version, or simply
     * a bug — leaves it stale. Without a rebuild, a stale index is unfixable
     * short of editing the database by hand, which is why no read path may be
     * built on the index until this exists.
     *
     * Clears and repopulates in one pass rather than diffing: at intranet
     * scale a full rebuild is seconds, and a diff would have to solve exactly
     * the "which rows are wrong" question that a corrupt index cannot answer.
     *
     * Deliberately does NOT infer or repair translation groupings — it records
     * only what the files say. (WPML shipped a bug where an update silently
     * re-linked translations an editor had deliberately unlinked; guessing
     * relationships during a repair is how that happens.)
     *
     * @param bool $dryRun count what would be indexed without writing
     * @return array{scanned:int, indexed:int, languages:array<string,int>}
     */
    public function rebuildIndex(bool $dryRun = false): array {
        return $this->maintenance()->rebuildIndex($this->folders()->intraVox(), $dryRun);
    }

    /**
     * Persist a new sibling order (issue #69). Writes `order = 0..n` onto each
     * child's page-JSON in the given sequence. A targeted metadata write — no
     * file version is created (order is metadata, not content).
     *
     * @param string|null $parentUniqueId Parent page uniqueId; null/'' = root.
     * @param string[] $orderedChildIds Child uniqueIds in the desired order.
     * @throws \Exception When the parent cannot be located.
     */
    public function reorderSiblings(?string $parentUniqueId, array $orderedChildIds): void {
        // The order-writing walk lives in Reorder/PageReorderer (Phase "reorder").
        // The write-target folder is resolved here through FolderContext; isHomepage
        // and the private clearCache are handed in as closures so both stay
        // overridable/private. PageReorderer's signature is unchanged (it still
        // takes the resolved Folder).
        $this->reorderer()->reorder(
            $parentUniqueId,
            $orderedChildIds,
            $this->folders()->languageFolder(),
            fn(string $id): bool => $this->isHomepage($id),
            function (): void {
                $this->clearCache();
            }
        );
    }

    /**
     * Format bytes to human readable format
     */

    /**
     * Check if a page is visible in the Nextcloud file cache
     * This is useful to determine if a groupfolder page has been indexed
     *
     * @param string $pageId The page ID to check
     * @return array Status information about the page's visibility
     */
    public function checkPageCacheStatus(string $pageId): array {
        try {
            $folder = $this->folders()->languageFolder();

            // For home page, check the JSON file directly
            if ($pageId === 'home') {
                try {
                    $file = $folder->get('home.json');
                    $storage = $file->getStorage();
                    $cache = $storage->getCache();

                    // Try to get cache entry using the storage's cache directly
                    $cacheEntry = $cache->get($file->getInternalPath());

                    return [
                        'visible' => $cacheEntry !== false,
                        'inCache' => $cacheEntry !== false,
                        'fileId' => $cacheEntry !== false ? $cacheEntry->getId() : null,
                        'path' => $file->getPath(),
                        'message' => $cacheEntry !== false ? 'Page is visible in Files app' : 'Page created but waiting for indexing'
                    ];
                } catch (NotFoundException $e) {
                    return [
                        'visible' => false,
                        'inCache' => false,
                        'fileId' => null,
                        'message' => 'Home page file not found'
                    ];
                }
            }

            // For regular pages, check if the page folder exists in cache.
            // Resolve the page itself rather than assuming a folder of that name
            // sits in the caller's own language: this diagnostic reported
            // "Page folder not found" for perfectly healthy pages that simply
            // live in another language, which is a misleading support signal.
            try {
                $located = $this->locatePageForOperation($pageId);
                $pageFolder = $located['folder'] ?? $folder->get($pageId);
                $storage = $pageFolder->getStorage();
                $cache = $storage->getCache();

                // Try to get cache entry using the storage's cache directly
                $cacheEntry = $cache->get($pageFolder->getInternalPath());

                if ($cacheEntry !== false && $cacheEntry instanceof ICacheEntry) {
                    return [
                        'visible' => true,
                        'inCache' => true,
                        'folderId' => $cacheEntry->getId(),
                        'path' => $pageFolder->getPath(),
                        'message' => 'Page is visible in Files app'
                    ];
                } else {
                    // Folder exists on disk but not in cache
                    return [
                        'visible' => false,
                        'inCache' => false,
                        'folderId' => null,
                        'message' => 'Page created but waiting for Nextcloud to index it. This may take 5-15 minutes.'
                    ];
                }
            } catch (NotFoundException $e) {
                return [
                    'visible' => false,
                    'inCache' => false,
                    'folderId' => null,
                    'message' => 'Page folder not found'
                ];
            }
        } catch (\Exception $e) {
            $this->logger->error('Failed to check page cache status', [
                'error' => $e->getMessage(),
                'pageId' => $pageId
            ]);

            return [
                'visible' => false,
                'inCache' => false,
                'error' => $e->getMessage(),
                'message' => 'Unable to check cache status'
            ];
        }
    }

    /**
     * Update version label
     * Uses IVersionManager with backend access for label updates.
     */
    public function updateVersionLabel(string $pageId, int $timestamp, ?string $label): void {
        $this->versionDomain()->updateVersionLabel($pageId, $timestamp, $label);
    }

    /**
     * Get version content for preview
     * Uses IVersionManager for reliable version content retrieval across all storage types.
     */
    public function getVersionContent(string $pageId, int $timestamp): array {
        return $this->versionDomain()->getVersionContent($pageId, $timestamp);
    }

    /**
     * Get current page content for comparison
     */
    public function getCurrentPageContent(string $pageId): array {
        return $this->versionDomain()->getCurrentPageContent($pageId);
    }

    /**
     * Get the full page tree structure for the current language
     * Returns a hierarchical tree of all pages the user has access to
     *
     * OPTIMIZED: Uses static cache with TTL to avoid repeated filesystem traversals
     *
     * @param string|null $currentPageId Optional: uniqueId of the current page to highlight
     * @return array Tree structure with pages and their children
     */
    public function getPageTree(?string $currentPageId = null, ?string $language = null, ?string $rootPageId = null): array {
        // Use provided language, else the language the user is actually shown
        // (recommended-language fallback, #75), else their own language.
        $lang = $language ?? $this->resolveEffectiveLanguage() ?? $this->getUserLanguage();

        // Cache key is groupHash + language. Users that share a group set
        // share a bucket — at enterprise scale (1k+ users, ~10 groups) that
        // turns 2000 entries into ~10.
        //
        // The full per-language tree is cached *whole*; subtree requests
        // filter from that cached blob (issue #45). Caching subtrees
        // separately would multiply key cardinality by the number of
        // candidate roots without saving work.
        $cacheKey = $this->groupContext->getGroupHash() . '_' . $lang;
        $distributedCacheKey = 'tree_' . $cacheKey;
        $now = time();

        // Check in-process cache first (fastest)
        $cached = $this->cache()->getTree($cacheKey);
        if ($cached !== null) {
            if (($now - $cached['time']) < PageCacheService::PAGE_TREE_TTL) {
                return $this->shapeTreeResponse($cached['tree'], $currentPageId, $rootPageId);
            }
        }

        // Check distributed cache (shared across PHP processes/requests)
        if ($this->cache()->isDistributedAvailable()) {
            $distributedCached = $this->cache()->getDistributed($distributedCacheKey);
            if ($distributedCached !== null) {
                $decoded = json_decode($distributedCached, true);
                if ($decoded !== null) {
                    // Populate the in-process cache too for later calls in this request
                    $this->cache()->setTree($cacheKey, [
                        'tree' => $decoded,
                        'time' => $now
                    ]);
                    return $this->shapeTreeResponse($decoded, $currentPageId, $rootPageId);
                }
            }
        }

        // Build fresh tree for specified language
        $folder = $this->getLanguageFolderByCode($lang);
        $tree = [];

        // Check for home.json in root
        try {
            $homeFile = $folder->get('home.json');
            $content = $homeFile->getContent();
            $data = json_decode($content, true);

            if ($data && isset($data['uniqueId'], $data['title'])) {
                $tree[] = [
                    'uniqueId' => $data['uniqueId'],
                    'title' => $data['title'],
                    'status' => $data['status'] ?? 'published',
                    'fileId' => ($homeFile instanceof \OCP\Files\File) ? $homeFile->getId() : null,
                    'path' => $lang,
                    'language' => $lang,
                    'isCurrent' => false, // Will be set by markCurrentPageInTree
                    'children' => [],
                    'permissions' => $this->permissionService->permissionsFromNode($folder)
                ];
            }
        } catch (NotFoundException $e) {
            // No home page yet
        }

        // Recursively build tree from subfolders
        $this->buildPageTree($folder, $tree, null, $lang); // Pass null, marking done separately

        // Configurable homepage: if a pointer designates a root page other than
        // the loose home.json, float that node to the front so the homepage is
        // always first (matches the legacy home.json-first behaviour).
        $pointer = $this->homepageService->getHomepageUniqueId($lang);
        if ($pointer !== null && $pointer !== '' && $pointer !== 'home') {
            foreach ($tree as $i => $node) {
                if (($node['uniqueId'] ?? null) === $pointer) {
                    if ($i !== 0) {
                        $picked = array_splice($tree, $i, 1);
                        array_unshift($tree, $picked[0]);
                    }
                    break;
                }
            }
        }

        // Store in the in-process cache
        $this->cache()->setTree($cacheKey, [
            'tree' => $tree,
            'time' => $now
        ]);

        // Store in distributed cache (shared across requests)
        $this->cache()->setDistributed($distributedCacheKey, json_encode($tree), PageCacheService::PAGE_TREE_TTL);

        return $this->shapeTreeResponse($tree, $currentPageId, $rootPageId);
    }

    /**
     * Apply the response-shaping steps that come after cache lookup:
     * optionally narrow to a subtree, then mark the current page.
     * Centralised so the three cache paths (static, distributed, fresh)
     * stay identical.
     */
    private function shapeTreeResponse(array $tree, ?string $currentPageId, ?string $rootPageId): array {
        if ($rootPageId !== null && $rootPageId !== '') {
            $tree = $this->pathHelper->findSubtree($tree, $rootPageId);
        }
        // markCurrentPageInTree deep-copies the (group-shared) cached tree, so it
        // is safe to overwrite permissions on the copy without polluting the cache.
        $tree = $this->pathHelper->markCurrentPageInTree($tree, $currentPageId);
        // The tree is cached per group-set, but GroupFolder ACLs can grant/deny
        // per USER within the same group. Recompute each node's permissions for
        // the current user from the live filesystem view so per-user ACLs are
        // reflected (issue #86) — same reasoning as the per-read permission
        // recompute in getPage() (issue #70).
        $this->refreshTreePermissions($tree);
        return $tree;
    }

    /**
     * Overwrite each tree node's `permissions` with the current user's live,
     * ACL-aware permissions, resolved from the node's path. Recurses into
     * children. Per-path results are memoised for the request via the shared
     * permissions cache inside getFolderPermissions/permissionsFromNode.
     *
     * @param array<int, array> $nodes
     */
    private function refreshTreePermissions(array &$nodes): void {
        foreach ($nodes as &$node) {
            $path = $node['path'] ?? null;
            if (is_string($path) && $path !== '') {
                try {
                    $node['permissions'] = $this->permissionService->getFolderPermissions($path);
                } catch (\Throwable $e) {
                    // Leave the cached (group-level) permissions as a safe fallback.
                }
            }
            if (!empty($node['children']) && is_array($node['children'])) {
                $this->refreshTreePermissions($node['children']);
            }
        }
        unset($node);
    }

    /**
     * Mark the current page in a tree structure
     * Creates a deep copy to avoid modifying cached data
     */

    /**
     * Recursively build the page tree from folder structure
     */
    private function buildPageTree($folder, array &$tree, ?string $currentPageId, ?string $language = null): void {
        // The recursive walk lives in Tree/PageTreeBuilder (Phase "tree"); kept
        // here as a by-ref delegator so the reflection-anchored contract
        // (PageServiceSeamContractTest) and the by-ref recursion both hold.
        $this->treeBuilder()->build($folder, $tree, $currentPageId, $language);
    }

    /**
     * Search pages by query string
     * Searches in page titles and text widget content
     * OPTIMIZED: Loads all content in a single filesystem traversal
     */
    public function searchPages(string $query): array {
        // Discovery (the filesystem walk) stays here; the scoring/sort/limit is
        // the PageSearchEngine's job (Phase "search"). Passing metaVox() in via
        // the engine keeps the request-scoped MetaVox memo a single instance.
        return $this->searchEngine()->search($this->listPagesWithContent(), $query);
    }

    /**
     * Extract a snippet of text around a search query match
     */

    /**
     * Search a single widget for matches
     *
     * @param array $widget Widget data
     * @param string $query Search query (lowercase)
     * @return array Array of matches with type, text, and score
     */

    /**
     * Sanitize filename for safe storage
     * - Validates extension against whitelist
     * - Remove special characters
     * - Convert spaces to underscores
     * - Check for Windows reserved names
     * - Limit to filesystem-safe length
     *
     * @param string $filename Original filename
     * @param bool $validateExtension Whether to validate extension (default true)
     * @return string Sanitized filename
     * @throws \InvalidArgumentException If extension is not allowed
     */
    /**
     * SVG sanitizing and the polyglot image check are no longer reached from
     * here: the upload paths that called them now validate through
     * PageMediaService::validateUpload(), which uses the same MediaSanitizer.
     */

    /**
     * Check if media file exists in page/_media or _resources folder
     *
     * @param string $pageId Page unique ID
     * @param string $filename Filename to check
     * @param string $targetFolder 'page' or 'resources'
     * @return bool True if file exists
     */
    public function checkMediaExists(string $pageId, string $filename, string $targetFolder): bool {
        return $this->mediaOrchestrator()->checkMediaExists(
            $pageId,
            $filename,
            $targetFolder
        );
    }

    /**
     * Upload media with original filename
     *
     * @param string $pageId Page unique ID
     * @param array $file Uploaded file data
     * @param string $targetFolder 'page' or 'resources'
     * @param bool $overwrite Whether to overwrite existing file
     * @return array ['filename' => '...', 'exists' => bool]
     * @throws \Exception On upload failure or if file exists and overwrite is false
     */
    public function uploadMediaWithOriginalName(string $pageId, array $file, string $targetFolder, bool $overwrite = false): array {
        return $this->mediaOrchestrator()->uploadMediaWithOriginalName(
            $pageId,
            $file,
            $targetFolder,
            $overwrite,
            function (string $mediaPageId): void {
                $this->clearCache($mediaPageId);
            }
        );
    }

    /**
     * Get list of media files in a folder
     *
     * @param string $pageId Page unique ID
     * @param string $folderType 'page' or 'resources'
     * @param string $subPath Subfolder path for resources (optional)
     * @return array List of media files with metadata
     */
    public function getMediaList(string $pageId, string $folderType, string $subPath = ''): array {
        return $this->mediaOrchestrator()->getMediaList(
            $pageId,
            $folderType,
            $subPath
        );
    }

    /**
     * Get media file from _resources folder
     *
     * @param string $path File path (can include subfolders)
     * @return \OCP\Files\File File object
     * @throws NotFoundException If file not found
     */
    public function getResourcesMediaFile(string $path) {
        return $this->mediaOrchestrator()->getResourcesMediaFile($path);
    }

    /**
     * Get news pages for the News widget
     *
     * @param string $sourcePath Source folder path (relative to language folder)
     * @param array $filters MetaVox filters to apply
     * @param string $filterOperator 'AND' or 'OR' for combining filters
     * @param int $limit Maximum number of results
     * @param string $sortBy Field to sort by ('modified' or 'title')
     * @param string $sortOrder Sort direction ('asc' or 'desc')
     * @return array News items with excerpts and images
     */
    public function getNewsPages(
        string $sourcePath = '',
        array $filters = [],
        string $filterOperator = 'AND',
        int $limit = 5,
        string $sortBy = 'modified',
        string $sortOrder = 'desc',
        ?string $sourcePageId = null,
        bool $filterPublished = false
    ): array {
        return $this->newsWidget()->getNewsPages(
            $sourcePath,
            $filters,
            $filterOperator,
            $limit,
            $sortBy,
            $sortOrder,
            $sourcePageId,
            $filterPublished
        );
    }

    // =========================================================================
    // TEMPLATE METHODS
    // =========================================================================

    /**
     * Find a page folder by its uniqueId
     *
     * @param string $uniqueId Page uniqueId
     * @return \OCP\Files\Folder|null The page folder or null if not found
     */
    private function findPageFolder(string $uniqueId): ?\OCP\Files\Folder {
        // Check cache first
        if ($this->cache()->hasPageFolder($uniqueId)) {
            return $this->cache()->getPageFolder($uniqueId);
        }

        try {
            // Follow the page across language folders. This resolves the folder
            // media is copied FROM and TO, and it fails by returning null, which
            // callers treat as "no media" — so on a foreign-language page,
            // "Save as template" and copy-page silently produced a page with no
            // images at all rather than reporting anything (#90 family).
            $result = $this->locatePageAnyLanguage($this->folders()->readLanguageFolder(), $uniqueId);
            if ($result !== null && isset($result['folder'])) {
                $folder = $result['folder'];
                $this->cache()->setPageFolder($uniqueId, $folder);
                return $folder;
            }
        } catch (\Exception $e) {
            $this->logger->warning('Could not find page folder for: ' . $uniqueId . ' - ' . $e->getMessage());
        }

        return null;
    }

    /**
     * Get the templates folder for the current user's language
     *
     * @return \OCP\Files\Folder|null The templates folder or null if not accessible
     */
    /**
     * List all available page templates for the user's language.
     * Storage and lookup live in PageTemplateService; these wrappers only
     * resolve the language folder (the locator stays here) and preserve the
     * old degradation: an unresolvable language folder means no templates,
     * never an error.
     */
    public function listTemplates(): array {
        try {
            $langFolder = $this->folders()->languageFolder();
        } catch (\Exception $e) {
            return [];
        }
        return $this->pageTemplateService->listTemplates($langFolder);
    }

    /**
     * Get a specific template by ID (folder name), or null.
     */
    public function getTemplate(string $templateId): ?array {
        try {
            $langFolder = $this->folders()->languageFolder();
        } catch (\Exception $e) {
            return null;
        }
        return $this->pageTemplateService->getTemplate($langFolder, $templateId);
    }

    /**
     * Save a page as a template
     *
     * @param string $pageUniqueId The uniqueId of the page to save as template
     * @param string $templateTitle Title for the template
     * @param string|null $templateDescription Optional description
     * @return array Result with success status and template data or error message
     */
    public function saveAsTemplate(string $pageUniqueId, string $templateTitle, ?string $templateDescription = null): array {
        return $this->composition()->saveAsTemplate(
            $pageUniqueId,
            $templateTitle,
            $templateDescription,
            fn(string $id): array => $this->getPage($id),
            fn(string $id): ?\OCP\Files\Folder => $this->findPageFolder($id)
        );
    }

    /**
     * Delete a template
     *
     * @param string $templateId Template ID (folder name)
     * @return array Result with success status
     */
    public function deleteTemplate(string $templateId): array {
        try {
            $langFolder = $this->folders()->languageFolder();
        } catch (\Exception $e) {
            return ['success' => false, 'error' => 'Templates folder not accessible'];
        }
        return $this->pageTemplateService->deleteTemplate($langFolder, $templateId);
    }

    /**
     * Create a new page from a template
     *
     * @param string $templateId Template ID to use
     * @param string $pageTitle Title for the new page
     * @param string|null $parentPath Optional parent path for nested pages
     * @return array Result with success status and page data
     */
    public function createPageFromTemplate(string $templateId, string $pageTitle, ?string $parentPath = null): array {
        return $this->composition()->createPageFromTemplate(
            $templateId,
            $pageTitle,
            $parentPath,
            fn(array $data, ?string $parentPath = null): array => $this->createPage($data, $parentPath),
            fn(string $id): array => $this->getPage($id),
            fn(string $id): ?array => $this->getTemplate($id),
            fn(string $id): ?\OCP\Files\Folder => $this->findPageFolder($id)
        );
    }

    /**
     * Copy a page (its content + media) into a new draft page (issue: copy page).
     *
     * Mirrors createPageFromTemplate: reuses createPage() for a fresh uniqueId +
     * collision-safe slug, keeps the layout/widgets (widget ids are page-scoped
     * and the copy gets its own media folder), copies media assets, and never
     * inherits the homepage pointer. Result is a draft under the same parent
     * (or an explicit target parent).
     *
     * @param string      $sourceUniqueId uniqueId of the page to copy.
     * @param string|null $targetParentId uniqueId of the destination parent; null/'' = same parent as source (root when source is root).
     * @param string|null $newTitle       Title for the copy; defaults to "{title} (copy)".
     * @return array The freshly created page (getPage shape).
     * @throws \Exception When the source cannot be located.
     */
    public function copyPage(string $sourceUniqueId, ?string $targetParentId = null, ?string $newTitle = null): array {
        return $this->composition()->copyPage(
            $sourceUniqueId,
            $targetParentId,
            $newTitle,
            fn(array $data, ?string $parentPath = null): array => $this->createPage($data, $parentPath),
            fn(string $id): array => $this->getPage($id),
            fn(\OCP\Files\Folder $folder, string $uid): ?array => $this->locatePageAnyLanguage($folder, $uid),
            fn(string $id): ?\OCP\Files\Folder => $this->findPageFolder($id),
            function (): void {
                $this->clearCache();
            }
        );
    }

    /**
     * Check if the user can create templates (has write access to _templates folder)
     *
     * @return bool
     */
    public function canCreateTemplates(): bool {
        try {
            $langFolder = $this->folders()->languageFolder();
        } catch (\Exception $e) {
            return false;
        }
        return $this->pageTemplateService->canCreateTemplates($langFolder);
    }
}
