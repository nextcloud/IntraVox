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
use OCP\Files\IRootFolder;
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
    private const DEFAULT_LANGUAGE = 'en';

    private IRootFolder $rootFolder;
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
    /** Lazily-built stateless language resolver (Phase 9). */
    private ?LanguageResolver $languageResolver = null;
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
    /** Lazily-built folder/location substrate (clean-target step 1; shipped unused). */
    private ?\OCA\IntraVox\Service\Folder\FolderContext $folderContext = null;
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
     * Get cached directory listing for a folder
     */
    private function getCachedDirectoryListing(\OCP\Files\Folder $folder): array {
        return $this->locator()->cachedDirectoryListing($folder);
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
        IRootFolder $rootFolder,
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
        ?string $userId
    ) {
        $this->rootFolder = $rootFolder;
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
     * Lazy seam for the stateless language resolver (Phase 9). Like cache(), it
     * can always synthesise its collaborator — it has no dependencies — so a test
     * that never wires it gets the real behaviour for free. Nullable-default so
     * the harness auto-fill's isInitialized() check skips it (never mocked).
     */
    private function language(): LanguageResolver {
        return $this->languageResolver ??= new LanguageResolver();
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
            $this->rootClosure()
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
            fn(\OCP\Files\Node $node): ?int => $this->groupfolderIdForNode($node)
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
            fn(\OCP\Files\Node $node): ?int => $this->groupfolderIdForNode($node)
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
        // All three former folder seams are RETIRED. FolderContext owns the
        // language/path composition; the mount atom is resolveIntraVoxMount() below,
        // passed as a lazy closure so a request-cache hit never forces $userId.
        // Tests inject a FolderContext with a fake intraVox rather than overriding
        // any seam.
        return $this->folderContext ??= new \OCA\IntraVox\Service\Folder\FolderContext(
            fn(): \OCP\Files\Folder => $this->resolveIntraVoxMount(),
            fn(): string => $this->getUserLanguage(),
            fn(): string => $this->languageService->getPrimaryLanguage(),
            fn(\OCP\Files\Folder $folder): bool => $this->languageFolderHasRealContent($folder),
            $this->language(),
            $this->locator()
        );
    }

    /**
     * The atomic IntraVox GroupFolder mount lookup (formerly the getIntraVoxFolder
     * seam). Uses the user's mounted folder view so GroupFolder ACLs apply; throws
     * "not logged in" without a user, and a specific "folder not found" when the
     * mount is missing or is not a folder.
     */
    private function resolveIntraVoxMount(): \OCP\Files\Folder {
        if (!$this->userId) {
            throw new \Exception('User not logged in');
        }
        $userFolder = $this->rootFolder->getUserFolder($this->userId);
        try {
            $node = $userFolder->get('IntraVox');
        } catch (NotFoundException $e) {
            throw new \Exception('IntraVox folder not found. Please check that you have access to the IntraVox GroupFolder.');
        }
        if (!$node instanceof \OCP\Files\Folder) {
            throw new \Exception('IntraVox folder not found. Please check that you have access to the IntraVox GroupFolder.');
        }
        return $node;
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
        if (!$this->userId) {
            return self::DEFAULT_LANGUAGE;
        }

        $lang = $this->config->getUserValue($this->userId, 'core', 'lang', self::DEFAULT_LANGUAGE);

        // Base-code extraction + malformed-value guard (Phase 9: LanguageResolver).
        return $this->language()->baseLanguageCode($lang);
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
        // Without an explicit language, use the language the user is actually
        // shown (recommended-language fallback, #75) so the homepage pointer is
        // resolved in — and checked against — the served language's folder.
        $lang = $language ?? $this->resolveEffectiveLanguage() ?? $this->getUserLanguage();

        $pointer = $this->homepageService->getHomepageUniqueId($lang);
        if ($pointer !== null && $pointer !== '' && $pointer !== 'home') {
            // Only honour the pointer when it resolves to an existing page.
            try {
                $folder = $this->getLanguageFolderByCode($lang);
                if ($this->findPageByUniqueId($folder, $pointer) !== null) {
                    return $pointer;
                }
            } catch (\Exception $e) {
                // Fall through to the legacy default.
            }
        }

        // Legacy default: the loose home.json in the language root.
        //
        // Resolve it to the uniqueId the file actually carries. Returning the
        // bare string 'home' hands the frontend an id that matches no page in
        // listPages(), so `pages.find(p => p.uniqueId === homepageUniqueId)`
        // came up empty and the reader fell through to a slug/path heuristic
        // that ends at `pages[0]` — the alphabetically first page. On dev that
        // put every Dutch reader on "API Referentie" instead of "Welkom bij
        // IntraVox", while English (which uses the normalised home/home.json
        // layout, so it already had a real uniqueId) worked fine.
        //
        // Falls back to the literal 'home' when the file is missing or carries
        // no uniqueId, which is the pre-existing behaviour and what the rest of
        // the legacy path still understands.
        try {
            $folder = $this->getLanguageFolderByCode($lang);
            $homeFile = $folder->get('home.json');
            if ($homeFile instanceof \OCP\Files\File) {
                $data = json_decode($this->getCachedFileContent($homeFile), true);
                $homeUniqueId = is_array($data) ? ($data['uniqueId'] ?? null) : null;
                if (is_string($homeUniqueId) && $homeUniqueId !== '') {
                    return $homeUniqueId;
                }
            }
        } catch (\Exception $e) {
            // No loose home.json in this language — fall through.
        }

        return 'home';
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
        $resolved = $this->getHomepageUniqueId($language);
        if ($resolved !== 'home') {
            return $resolved;
        }

        // Legacy default: map 'home' to the real uniqueId of the loose home.json.
        try {
            $folder = $this->getLanguageFolderByCode($language ?? $this->getUserLanguage());
            if ($folder->nodeExists('home.json')) {
                $data = json_decode($folder->get('home.json')->getContent(), true);
                if (is_array($data) && !empty($data['uniqueId'])) {
                    return (string)$data['uniqueId'];
                }
            }
        } catch (\Exception $e) {
            // Fall through.
        }

        // Last resort: first root node of a supplied tree.
        if (is_array($tree) && isset($tree[0]['uniqueId'])) {
            return (string)$tree[0]['uniqueId'];
        }
        return 'home';
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

    private function locateAcrossLanguages(\OCP\Files\Folder $primaryFolder, callable $find): ?array {
        return $this->locator()->locateAcrossLanguages($this->rootClosure(), $primaryFolder, $find);
    }

    /**
     * Locate a page for a MEDIA operation, and report which language folder it
     * turned out to live in.
     *
     * Media resolution used to start from a language folder chosen for the
     * USER — getLanguageFolder() (the profile language) on the write paths,
     * getReadLanguageFolder() (own → recommended → en, #75) on the list path —
     * and then look for the page only there. Both are the wrong question. A
     * page's media lives next to the page, so the only folder that matters is
     * the one holding the page itself.
     *
     * When the two disagreed, every media operation failed on a page that was
     * plainly on screen: uploads threw "Page not found" while the very same
     * request had already passed its permission check through the
     * cross-language getPage(), listings came back empty so the Shared Library
     * showed names without previews, and thumbnails 404'd (issue #92). This is
     * the same read/write asymmetry #90 fixed for pages, applied to the media
     * cluster that #90 did not reach.
     *
     * Returns the language folder alongside the page so callers can resolve
     * `_media` / `_resources` for the HOME page and for the resources library
     * in that same language, instead of falling back to the user's own.
     *
     * @param string $pageId uniqueId (page-…) or legacy slug id.
     * @return array{result: array, languageFolder: \OCP\Files\Folder}|null
     *   null when the page exists in no language folder at all.
     */
    private function locatePageForMedia(string $pageId): ?array {
        $primary = $this->folders()->readLanguageFolder();

        $find = function (\OCP\Files\Folder $folder) use ($pageId): ?array {
            if (strpos($pageId, 'page-') === 0) {
                $byUniqueId = $this->findPageByUniqueId($folder, $pageId);
                if ($byUniqueId !== null) {
                    return $byUniqueId;
                }
            }
            // Legacy slug ids (and uniqueIds that predate the page- prefix)
            // stay resolvable, matching the fallback the callers already had.
            return $this->findPageById($folder, $this->idUtils->sanitizeId($pageId));
        };

        $result = $this->locateAcrossLanguages($primary, $find);
        if ($result === null) {
            return null;
        }

        return [
            'result' => $result,
            'languageFolder' => $this->languageFolderOfPageResult($result) ?? $primary,
        ];
    }

    /**
     * The language content folder that a findPageByUniqueId()/findPageById()
     * result sits in, derived from the page folder's own path.
     *
     * Walks up from the page folder to the language folder rather than trusting
     * the folder the search STARTED from — after a cross-language hit those are
     * not the same, and it is the page's own language that owns its media.
     *
     * @return \OCP\Files\Folder|null null when the path cannot be resolved, in
     *   which case callers fall back to the folder they searched from.
     */
    private function languageFolderOfPageResult(array $result): ?\OCP\Files\Folder {
        $folder = $result['folder'] ?? null;
        if (!($folder instanceof \OCP\Files\Folder)) {
            return null;
        }

        // The home page's "folder" IS the language folder; deeper pages sit
        // somewhere below it. languageOfFolder() names the language either way.
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
     * $parent->get($name) as a Folder, or null when it is missing or is a file.
     * Saves the repeated try/catch around optional `_media` / `_resources`
     * lookups on paths that treat "absent" as an ordinary outcome.
     */
    private function folderOrNull(?\OCP\Files\Folder $parent, string $name): ?\OCP\Files\Folder {
        return $this->locator()->folderOrNull($parent, $name);
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
        $folder = $this->folders()->readLanguageFolder();

        // Titles and statuses come from the index when it has this language,
        // which removes the read + json_decode of every page file. Permissions
        // still come from the filesystem: they depend on GroupFolder ACLs and
        // on who is asking, so they are not derivable from an index row and
        // must never be cached across users.
        $indexed = $this->listPagesFromIndex($folder);
        if ($indexed !== null) {
            return $this->inStableOrder($indexed);
        }

        $intraVoxFolder = $this->folders()->intraVox();
        $pages = [];

        // Get base path for relative path calculation
        $basePath = $intraVoxFolder->getPath();

        // Check for home.json in root
        try {
            $homeFile = $folder->get('home.json');
            $content = $homeFile->getContent();
            $data = json_decode($content, true);

            if ($data && isset($data['uniqueId'], $data['title'])) {
                // Calculate relative path from IntraVox root
                $relativePath = substr($folder->getPath(), strlen($basePath) + 1);

                $pages[] = [
                    'uniqueId' => $data['uniqueId'],
                    'title' => $data['title'],
                    'modified' => $data['modified'] ?? $homeFile->getMTime(),
                    'status' => $data['status'] ?? 'published',
                    'permissions' => $this->permissionService->permissionsFromNode($folder)
                ];
            }
        } catch (NotFoundException $e) {
            // No home page yet
        }

        // Recursively find all pages in subfolders
        $this->findPagesInFolder($folder, $pages, $basePath);

        return $this->inStableOrder($pages);
    }

    /**
     * One deterministic order for the page listing.
     *
     * There was none. The indexed branch returned whatever the database handed
     * back (no ORDER BY) and the fallback branch whatever the filesystem walk
     * produced, so the same instance could answer the same request in a different
     * order — and which of the two branches ran depended on whether the index
     * happened to cover the language.
     *
     * That is a problem beyond tidiness. Cursor pagination needs a total order to
     * be correct: without one, the same cursor silently skips some rows and
     * repeats others between two requests. plan-multisite-uitvoering.md §4.15 has
     * already settled on keyset paging over (slug, id) and 'never OFFSET', so the
     * order has to exist before that can be built.
     *
     * Sorted on title, then uniqueId as the tie-breaker. Title because it is the
     * only human-meaningful field this listing actually carries — it returns
     * uniqueId, title, status, modified and permissions, and no path, so sorting
     * on a path would silently degrade to sorting on nothing. uniqueId last
     * because titles are not unique and a sort whose final key repeats is not a
     * total order.
     *
     * Byte comparison, not locale collation: two pages whose titles differ only in
     * accents may not land where a Dutch reader would file them. That is a
     * deliberate trade — this order exists to be STABLE, so that a cursor can
     * rely on it, and locale-aware collation would make it depend on the server's
     * locale, which is the opposite of what a cursor needs.
     *
     * @param list<array<string,mixed>> $pages
     * @return list<array<string,mixed>>
     */
    private function inStableOrder(array $pages): array {
        usort($pages, static function (array $a, array $b): int {
            return [(string)($a['title'] ?? ''), (string)($a['uniqueId'] ?? '')]
                <=> [(string)($b['title'] ?? ''), (string)($b['uniqueId'] ?? '')];
        });

        return $pages;
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
        if ($uniqueIdA === $uniqueIdB) {
            throw new \InvalidArgumentException('A page cannot be a translation of itself');
        }

        $folder = $this->folders()->readLanguageFolder();
        $a = $this->locatePageAnyLanguage($folder, $uniqueIdA);
        $b = $this->locatePageAnyLanguage($folder, $uniqueIdB);
        if ($a === null) {
            throw new PageNotFoundException('Page not found: ' . $uniqueIdA);
        }
        if ($b === null) {
            throw new PageNotFoundException('Page not found: ' . $uniqueIdB);
        }

        $langA = $this->languageOfFolder($a['folder']);
        $langB = $this->languageOfFolder($b['folder']);
        if ($langA !== null && $langA === $langB) {
            throw new \InvalidArgumentException(
                'These pages are both in the same language, so one cannot be a translation of the other.'
            );
        }

        // BOTH sides must be writable before either is written. The order
        // matters more than it looks: the group is adopted from whichever side
        // already has one, so writing A first and then failing on B would leave
        // A a member of B's existing group — a link B's editors never made,
        // created by someone without write access to B. Checking up front makes
        // denial happen before any state changes.
        foreach ([$a, $b] as $side) {
            if (!$side['file']->isUpdateable()) {
                throw new ForbiddenException('You need edit permission on both pages to link them');
            }
        }

        // Adopt an existing group when there is one, so linking is additive.
        $dataA = json_decode($a['file']->getContent(), true);
        $dataB = json_decode($b['file']->getContent(), true);
        $group = (is_array($dataA) ? ($dataA['translationGroup'] ?? null) : null)
            ?: (is_array($dataB) ? ($dataB['translationGroup'] ?? null) : null)
            ?: $this->translationGroups()->newGroupId();

        // Adoption must not smuggle in a language the group already has —
        // the invariant lives with the rest of the group rules.
        $this->translationGroups()->assertAdoptionAddsNoDuplicateLanguage(
            $group,
            [[$uniqueIdA, $langA], [$uniqueIdB, $langB]]
        );

        $this->writeTranslationGroup($a, $group);
        $this->writeTranslationGroup($b, $group);
        $this->clearCache();

        return $group;
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
        if (!preg_match('/^[a-z]{2,3}$/', $language)) {
            throw new \InvalidArgumentException('Invalid language code: ' . $language);
        }

        $source = $this->locatePageAnyLanguage($this->folders()->readLanguageFolder(), $sourceUniqueId);
        if ($source === null || !isset($source['file'])) {
            throw new PageNotFoundException('Page not found: ' . $sourceUniqueId);
        }

        $sourceLanguage = $this->languageOfFolder($source['folder']);
        if ($sourceLanguage === $language) {
            throw new \InvalidArgumentException(
                'This page is already in that language.'
            );
        }

        $sourceData = json_decode($source['file']->getContent(), true);
        if (!is_array($sourceData)) {
            throw new \InvalidArgumentException('Could not read the source page');
        }

        // One page per language per group — refuse rather than create a second
        // German version that would make the switcher ambiguous.
        $group = $sourceData['translationGroup'] ?? null;
        if (!empty($group) && $this->translationGroups()->groupHasLanguage($group, $language)) {
            throw new \InvalidArgumentException(
                'A version of this page already exists in that language.'
            );
        }

        // The target language folder must exist; creating one silently would
        // add a language to the intranet as a side effect of translating.
        try {
            $targetFolder = $this->folders()->intraVox()->get($language);
        } catch (NotFoundException $e) {
            throw new \InvalidArgumentException(
                'That language has no content folder yet. Add the language in the admin settings first.'
            );
        }
        if (!($targetFolder instanceof \OCP\Files\Folder)) {
            throw new \InvalidArgumentException('Invalid language folder: ' . $language);
        }
        if (!$targetFolder->isCreatable()) {
            throw new ForbiddenException('You do not have permission to create a page in that language');
        }

        // Assign the group up front so both sides land linked in one write
        // each, rather than being linked afterwards as a second step that
        // could half-fail.
        //
        // Known half-state: if createPage() below fails, the SOURCE keeps this
        // fresh group as its only member. That is harmless by construction —
        // resolveTranslations() excludes the page itself, so a singleton group
        // renders nothing — and the next successful link or unlink rewrites it.
        if (empty($group)) {
            $group = $this->translationGroups()->newGroupId();
            $this->writeTranslationGroup($source, $group);
        }

        $pageData = $sourceData;
        unset($pageData['order']);
        $baseTitle = $this->htmlSanitizer->decodeEntitiesRecursive((string)($sourceData['title'] ?? 'Untitled'));
        $pageData['title'] = ($title !== null && $title !== '') ? $title : $baseTitle;
        $pageData['id'] = $this->idUtils->sanitizeId($pageData['title']);
        $pageData['uniqueId'] = 'page-' . $this->idUtils->generateUUID();
        $pageData['translationGroup'] = $group;
        // Draft: an untranslated copy is not something readers should meet.
        $pageData['status'] = 'draft';
        $pageData['created'] = time();
        $pageData['modified'] = time();

        // Mirror the source's position within its own language tree, so the
        // German page sits where the English one does rather than at the root.
        $sourceRelative = $this->getRelativePathFromRoot($source['folder']);
        $sourceParent = dirname($sourceRelative);
        $parentPath = $language;
        if ($sourceParent !== '.' && $sourceParent !== '') {
            $segments = explode('/', $sourceParent);
            // Swap the language segment for the target language; the rest of
            // the path only exists in the target tree if the parents were
            // translated too, and getOrCreateFolderPath() creates what is missing.
            array_shift($segments);
            $parentPath = $language . (empty($segments) ? '' : '/' . implode('/', $segments));
        }

        $created = $this->createPage($pageData, $parentPath);

        // A translation starts as a copy of the source, so it needs the
        // source's images too — the same way copyPage does it. Without this the
        // text carried over but every image 404'd, because the JSON stores bare
        // file names that resolve against the page being viewed.
        $this->copyPageMedia($source['folder'] ?? null, $created['uniqueId'], 'createTranslation');

        $this->clearCache();

        return $created;
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
        $result = $this->locatePageAnyLanguage($this->folders()->readLanguageFolder(), $pageId);
        if ($result === null) {
            throw new PageNotFoundException('Page not found: ' . $pageId);
        }

        $ownLanguage = $this->languageOfFolder($result['folder']);
        $data = json_decode($result['file']->getContent(), true);
        $group = is_array($data) ? ($data['translationGroup'] ?? null) : null;

        $root = $this->folders()->intraVox();
        $taken = $this->translationGroups()->languagesTaken($group);

        $languages = [];
        foreach ($this->translationGroups()->otherContentLanguages($root, $ownLanguage) as $code) {
            if (isset($taken[$code])) {
                continue;
            }
            $languages[] = [
                'code' => $code,
                // Naming stays here: it reads LanguageService's list, which is
                // the interface-language source the admin tab shares.
                'name' => $this->languageDisplayName($code),
                // How many of this page's ancestors do not exist as pages in
                // that language yet. The translation still lands mirrored
                // (createTranslation creates the missing levels as bare
                // folders, and the tree renders those as non-clickable
                // pass-through nodes) — but the editor deserves to know
                // BEFORE creating, not by discovering grey levels afterwards.
                'missingAncestors' => $this->translationGroups()
                    ->countMissingAncestors($root, $result['folder'], $code),
            ];
        }

        return $languages;
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
        $folder = $this->folders()->readLanguageFolder();
        $result = $this->locatePageAnyLanguage($folder, $pageId);
        if ($result === null) {
            throw new PageNotFoundException('Page not found: ' . $pageId);
        }

        $ownLanguage = $this->languageOfFolder($result['folder']);
        $ownData = json_decode($result['file']->getContent(), true);
        $ownGroup = is_array($ownData) ? ($ownData['translationGroup'] ?? null) : null;

        // Languages this page's group already covers — candidates in those
        // languages are filtered below, one indexed query for the whole list.
        $takenLanguages = $this->translationGroups()->languagesTaken($ownGroup, $pageId);

        // Languages to offer: everything with content except this page's own,
        // listed through the caller's own mount so denied languages never
        // appear (see otherContentLanguages()).
        $languages = $this->translationGroups()->otherContentLanguages(
            $this->folders()->intraVox(),
            $ownLanguage,
            $language
        );

        return $this->translationGroups()->candidatesInLanguages(
            $pageId,
            $ownGroup,
            $languages,
            $takenLanguages
        );
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
        $folder = $this->folders()->readLanguageFolder();
        $result = $this->locatePageAnyLanguage($folder, $uniqueId);
        if ($result === null) {
            throw new PageNotFoundException('Page not found: ' . $uniqueId);
        }

        $group = $this->translationGroups()->newGroupId();
        $this->writeTranslationGroup($result, $group);
        $this->clearCache();

        return $group;
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
     * Build the page list from the index instead of walking the tree.
     *
     * Returns null when the index cannot serve this language, so the caller
     * falls back to the filesystem walk. That is the whole safety story: the
     * index is a cache, and an empty or partial one costs a slow path, never a
     * short list. A page the index does not know about would otherwise silently
     * disappear from the sidebar — a far worse failure than being slow.
     *
     * Permissions are still read per page from the filesystem. They depend on
     * GroupFolder ACLs and on the current user, so an index row cannot carry
     * them and caching them across users would leak access.
     *
     * @return array|null the page list, or null to fall back to the walk
     */
    private function listPagesFromIndex(\OCP\Files\Folder $folder): ?array {
        // The index-listing body lives in Listing/PageLister (Phase "listing");
        // kept here as a delegator because listPages() calls it and
        // PageServiceSeamContractTest reflection-anchors its existence.
        return $this->pageLister()->fromIndex($folder);
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
    /**
     * Resolve the homepage JSON for a language folder regardless of storage form
     * (configurable homepage). Checks, in order:
     *   1. a `homepage.json` pointer → the designated root page's JSON;
     *   2. the legacy loose `home.json`;
     *   3. a normalized `home/home.json` folder page (post-normalization default).
     *
     * Returns the decoded page data array, or null when no homepage exists.
     */
    private function resolveLanguageHomepageData(\OCP\Files\Folder $langFolder): ?array {
        // 1. Pointer.
        try {
            if ($langFolder->nodeExists('homepage.json')) {
                $ptr = json_decode($langFolder->get('homepage.json')->getContent(), true);
                $uid = is_array($ptr) ? ($ptr['homepageUniqueId'] ?? null) : null;
                if (is_string($uid) && $uid !== '') {
                    $target = $this->findPageByUniqueId($langFolder, $uid);
                    if ($target !== null && isset($target['file'])) {
                        $data = json_decode($target['file']->getContent(), true);
                        if (is_array($data) && isset($data['title'])) {
                            return $data;
                        }
                    }
                }
            }
        } catch (\Throwable $e) {
            // Fall through to loose/normalized forms.
        }

        // 2. Legacy loose home.json.
        try {
            if ($langFolder->nodeExists('home.json')) {
                $data = json_decode($langFolder->get('home.json')->getContent(), true);
                if (is_array($data) && isset($data['title'])) {
                    return $data;
                }
            }
        } catch (\Throwable $e) {
            // Fall through.
        }

        // 3. Normalized home/home.json.
        try {
            if ($langFolder->nodeExists('home')) {
                $homeFolder = $langFolder->get('home');
                if ($homeFolder instanceof \OCP\Files\Folder && $homeFolder->nodeExists('home.json')) {
                    $data = json_decode($homeFolder->get('home.json')->getContent(), true);
                    if (is_array($data) && isset($data['title'])) {
                        return $data;
                    }
                }
            }
        } catch (\Throwable $e) {
            // Fall through.
        }

        return null;
    }

    private function languageFolderHasRealContent(\OCP\Files\Folder $langFolder): bool {
        $data = $this->resolveLanguageHomepageData($langFolder);
        if ($data === null) {
            return false;
        }
        return empty($data['_generated']);
    }

    /**
     * Whether a language folder has a homepage AT ALL — real OR an auto/placeholder
     * one (`_generated`). This is the "active language" signal: a language an admin
     * added via "Add language" has a placeholder homepage and should show up as an
     * active intranet language even before an editor fills it.
     */
    private function languageFolderHasHomepage(\OCP\Files\Folder $langFolder): bool {
        return $this->resolveLanguageHomepageData($langFolder) !== null;
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
        $userLang = $this->folders()->userLanguage();
        $withContent = [];
        $active = [];

        try {
            $baseFolder = $this->folders()->intraVox();
            foreach ($this->getCachedDirectoryListing($baseFolder) as $item) {
                if ($item->getType() !== \OCP\Files\FileInfo::TYPE_FOLDER) {
                    continue;
                }
                $name = $item->getName();
                // Language folders are two-letter base codes (nl, en, de, ...).
                if (!preg_match('/^[a-z]{2,3}$/', $name) || !($item instanceof \OCP\Files\Folder)) {
                    continue;
                }
                if ($this->languageFolderHasHomepage($item)) {
                    $active[] = $name;
                }
                if ($this->languageFolderHasRealContent($item)) {
                    $withContent[] = $name;
                }
            }
        } catch (\Throwable $e) {
            $this->logger->warning('[PageService] getLanguageContentStatus failed: ' . $e->getMessage());
        }

        sort($withContent);
        sort($active);

        // The language the user will actually be shown: own language, else the
        // recommended (primary) language, else English — issue #75. null means
        // nothing can be served (only then does the fallback notice appear).
        $served = $this->resolveEffectiveLanguage();

        // Resolve the homepage for the SERVED language (not necessarily the
        // user's), so the app lands on the correct homepage after fallback.
        $homepageUniqueId = null;
        try {
            $homepageUniqueId = $this->resolveHomepageNodeUniqueId($served ?? $userLang);
        } catch (\Throwable $e) {
            // Non-fatal: the frontend falls back to its own heuristic.
        }

        return [
            'language' => $userLang,
            // hasContent = "the user will see real content" (own language, the
            // recommended language, or English all count). Only false when
            // nothing resolves — the sole trigger for the fallback notice.
            'hasContent' => $served !== null,
            'servedLanguage' => $served,
            'languagesWithContent' => $withContent,
            'activeLanguages' => $active,
            'homepageUniqueId' => $homepageUniqueId,
        ];
    }

    /**
     * Number of pages per language folder (base code => count). Used by the
     * admin "remove language" confirmation so it can warn how many pages would
     * be deleted. Counts the homepage plus every `{name}/{name}.json` subpage.
     *
     * @return array<string,int>
     */
    public function getPageCountByLanguage(): array {
        $counts = [];
        try {
            $baseFolder = $this->folders()->intraVox();
            foreach ($this->getCachedDirectoryListing($baseFolder) as $item) {
                if ($item->getType() !== \OCP\Files\FileInfo::TYPE_FOLDER) {
                    continue;
                }
                $name = $item->getName();
                if (!preg_match('/^[a-z]{2,3}$/', $name) || !($item instanceof \OCP\Files\Folder)) {
                    continue;
                }
                $pages = [];
                $this->findPagesInFolder($item, $pages, '');
                $count = count($pages);
                // Homepage counts as a page when present (findPagesInFolder skips it).
                if ($item->nodeExists('home.json')) {
                    $count++;
                }
                $counts[$name] = $count;
            }
        } catch (\Throwable $e) {
            $this->logger->warning('[PageService] getPageCountByLanguage failed: ' . $e->getMessage());
        }
        return $counts;
    }

    /**
     * Recursively find pages in folders
     */
    private function findPagesInFolder($folder, array &$pages, string $basePath = ''): void {
        foreach ($this->getCachedDirectoryListing($folder) as $item) {
            if ($item->getType() === \OCP\Files\FileInfo::TYPE_FOLDER) {
                $folderName = $item->getName();

                // Skip asset and infrastructure folders. The underscore rule
                // matters: _templates holds page-shaped JSON, and every walker
                // that forgot to skip it served TEMPLATES as pages — search
                // returned "Knowledge Base" the template above the real page,
                // and an empty index made them appear in the page list. One
                // rule for every walker, same as buildPageTree.
                if (PagePathHelper::isInfrastructureFolder($folderName)) {
                    continue;
                }

                // Look for {foldername}.json inside the folder
                try {
                    $jsonFile = $item->get($folderName . '.json');

                    // Check if file is readable before trying to get content
                    if (!$jsonFile->isReadable()) {
                        continue;
                    }

                    // Use cached file content to avoid repeated reads
                    $content = $jsonFile instanceof \OCP\Files\File
                        ? $this->getCachedFileContent($jsonFile)
                        : @$jsonFile->getContent();

                    if ($content === false || $content === null) {
                        continue;
                    }

                    $data = json_decode($content, true);

                    if ($data && isset($data['uniqueId'], $data['title'])) {
                        $pages[] = [
                            'uniqueId' => $data['uniqueId'],
                            'title' => $data['title'],
                            'modified' => $data['modified'] ?? $jsonFile->getMTime(),
                            'status' => $data['status'] ?? 'published',
                            'permissions' => $this->permissionService->permissionsFromNode($item)
                        ];
                    }
                } catch (\Exception $e) {
                    // This folder doesn't contain a valid page or can't be read, continue
                } catch (\Throwable $e) {
                    // Catch any other errors including PHP errors
                    continue;
                }

                // Recursively search subfolders
                $this->findPagesInFolder($item, $pages, $basePath);
            }
        }
    }

    /**
     * List all pages with full content (including layout)
     * OPTIMIZED: Single filesystem traversal for search operations
     * This eliminates the N+1 query pattern where listPages() + getPage() for each
     */
    public function listPagesWithContent(): array {
        $folder = $this->folders()->readLanguageFolder();
        $pages = [];

        // Check for home.json in root
        try {
            $homeFile = $folder->get('home.json');
            $content = $homeFile->getContent();
            $data = json_decode($content, true);

            if ($data && isset($data['uniqueId'])) {
                // fileId lets callers (search) join MetaVox metadata onto the page.
                $data['fileId'] = $homeFile->getId();
                $pages[] = $this->sanitizePage($data);
            }
        } catch (NotFoundException $e) {
            // No home page yet
        }

        // Recursively find all pages with full content
        $this->findPagesWithContentInFolder($folder, $pages);

        return $pages;
    }

    /**
     * Recursively find pages with full content in folders
     */
    private function findPagesWithContentInFolder($folder, array &$pages): void {
        foreach ($this->getCachedDirectoryListing($folder) as $item) {
            if ($item->getType() === \OCP\Files\FileInfo::TYPE_FOLDER) {
                $folderName = $item->getName();

                // Skip special folders
                if (PagePathHelper::isInfrastructureFolder($folderName)) {
                    continue;
                }

                // Look for {foldername}.json inside the folder
                try {
                    $jsonFile = $item->get($folderName . '.json');

                    if (!$jsonFile->isReadable()) {
                        continue;
                    }

                    // Use cached file content to avoid repeated reads
                    $content = $jsonFile instanceof \OCP\Files\File
                        ? $this->getCachedFileContent($jsonFile)
                        : @$jsonFile->getContent();

                    if ($content === false || $content === null) {
                        continue;
                    }

                    $data = json_decode($content, true);

                    if ($data && isset($data['uniqueId'])) {
                        // fileId lets callers (search) join MetaVox metadata onto the page.
                        $data['fileId'] = $jsonFile->getId();
                        $pages[] = $this->sanitizePage($data);
                    }
                } catch (\Exception $e) {
                    // This folder doesn't contain a valid page
                } catch (\Throwable $e) {
                    continue;
                }

                // Recursively search subfolders
                $this->findPagesWithContentInFolder($item, $pages);
            }
        }
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
     * Enrich page data with real-time path information calculated from filesystem
     */
    private function enrichWithPathData(array $page, $folder, ?\OCP\Files\Node $file = null): array {
        // The fresh-build enrichment lives in Path/PageDataEnricher (Phase "crud").
        // The four seam-bound concerns (getRelativePathFromRoot, getUserLanguage,
        // resolveTranslations, groupfolderIdForNode) are handed in as closures so
        // they stay on PageService (resolveTranslations/groupfolderIdForNode are
        // shared with the #70 cache-hit block, which stays inline in getPage).
        return $this->pageDataEnricher()->enrich($page, $folder, $file);
    }

    /**
     * Get relative path from IntraVox root folder
     */
    private function getRelativePathFromRoot($folder): string {
        // First consumer of the FolderContext substrate (clean-target step 1).
        // Byte-identical: relativePathFromRoot resolves the root via the
        // getIntraVoxFolder seam closure, so test-subclass overrides still flow.
        return $this->folders()->relativePathFromRoot($folder);
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
        // Check request-level cache first
        if ($this->cache()->hasFolderPath($folderPath)) {
            return $this->cache()->getFolderPath($folderPath);
        }

        try {
            $intraVoxFolder = $this->folders()->intraVox();
            $folder = $intraVoxFolder->get($folderPath);

            if (!($folder instanceof \OCP\Files\Folder)) {
                $this->cache()->setFolderPath($folderPath, null);
                return null;
            }

            // Look for a JSON file in this folder (page definition)
            $files = $this->getCachedDirectoryListing($folder);
            foreach ($files as $file) {
                if ($file instanceof \OCP\Files\File &&
                    pathinfo($file->getName(), PATHINFO_EXTENSION) === 'json' &&
                    $file->getName() !== 'images.json') {

                    $content = $file->getContent();
                    $data = json_decode($content, true);

                    if ($data && isset($data['uniqueId'])) {
                        // Enrich with path data (file gates canWrite/canEdit, #70)
                        $data = $this->enrichWithPathData($data, $folder, $file);
                        $result = $this->sanitizePage($data);
                        $this->cache()->setFolderPath($folderPath, $result);
                        return $result;
                    }
                }
            }
        } catch (\Exception $e) {
            // Folder or page not found
            $this->logger->debug("Could not find page at path {$folderPath}: " . $e->getMessage());
        }

        $this->cache()->setFolderPath($folderPath, null);
        return null;
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
        $lang = $this->folders()->userLanguage();
        $languageFolder = $this->folders()->languageFolder();

        // Resolve the target and require it to be a real page.
        $target = $this->findPageByUniqueId($languageFolder, $uniqueId);
        if ($target === null || !isset($target['folder'])) {
            throw new \InvalidArgumentException('Page not found');
        }

        // Must be a ROOT-level page: its folder's parent is the language root
        // (or it is the loose home itself). Compare parent paths.
        $isLooseHome = !empty($target['isHome']);
        if (!$isLooseHome) {
            $parentPath = dirname($target['folder']->getPath());
            if ($parentPath !== $languageFolder->getPath()) {
                throw new \InvalidArgumentException('Only root-level pages can be the homepage');
            }
        }

        // If the target is already the resolved homepage, nothing to do.
        if ($this->isHomepage($uniqueId, $lang)) {
            return;
        }

        // Pages never move when the homepage changes — only the pointer shifts.
        // The old loose home.json simply stays where it is and shows up as a
        // normal root page once the pointer designates a different page.
        $this->homepageService->setHomepageUniqueId($uniqueId, $lang);
        $this->clearCache();
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
        // Order matters and is preserved from before the split: the $_FILES
        // shape check runs first, then the id is sanitized (it can reject an
        // id too), then the rest of the upload validation.
        $this->media()->assertUploadShape($file);

        $pageId = $this->idUtils->sanitizeId($pageId);

        $validated = $this->media()->validateUpload($file);

        // Sanitize filename with prefix based on type
        $filename = $this->media()->generatedMediaFilename($file['name'], $validated['mimeType']);

        // Media belongs to the page, so resolve the page across every language
        // folder and upload into the language it actually lives in (issue #92).
        $located = $this->locatePageForMedia($pageId);
        if ($located === null) {
            throw new PageNotFoundException('Page not found: ' . $pageId);
        }

        // Home media is in root/_media/, other pages in their own folder.
        $hostFolder = $this->mediaHostFolder($located);
        if ($hostFolder === null) {
            throw new PageNotFoundException('Page not found: ' . $pageId);
        }
        $mediaFolder = $this->media()->mediaFolderFor($hostFolder);

        $this->media()->writeMediaFile($mediaFolder, $filename, $validated['content'], false);

        // Invalidate the per-page content cache so the next getPage()
        // includes the freshly uploaded asset. Without this a save-then-
        // navigate-back sequence served the cached page-render where the
        // media reference was still missing — particularly visible on
        // image widgets that just got their src bumped.
        $this->clearCache($pageId);

        return $filename;
    }

    /**
     * The folder whose `_media` holds a located page's media: the page's own
     * folder, except for the home page, whose media lives in the language
     * folder's root `_media`.
     *
     * Null only when a non-home result carries no folder, which the media
     * paths already treated as "nothing to read or write here".
     *
     * @param array{result: array, languageFolder: \OCP\Files\Folder} $located
     */
    private function mediaHostFolder(array $located): ?\OCP\Files\Folder {
        if ($located['result']['isHome'] ?? false) {
            return $located['languageFolder'];
        }
        $folder = $located['result']['folder'] ?? null;
        return $folder instanceof \OCP\Files\Folder ? $folder : null;
    }

    /**
     * Get media (image or video) for a specific page
     * Unified endpoint that serves all media from a single '_media' folder
     */
    public function getMedia(string $pageId, string $filename) {
        // Save original BEFORE sanitization
        $originalPageId = $pageId;
        $filename = basename($filename); // Prevent directory traversal

        // The language whose content this user is shown, which is where the
        // home page and the cache fast-path below look first. A page in another
        // language is picked up by the cross-language miss path further down.
        $languageFolder = $this->folders()->readLanguageFolder();

        try {
            // Handle home page with original pageId
            if ($originalPageId === 'home' ||
                $originalPageId === '2e8f694e-147e-4793-8949-4732e679ae6b' ||
                $originalPageId === 'page-2e8f694e-147e-4793-8949-4732e679ae6b') {

                $mediaFolder = $languageFolder->get('_media');

                return $this->media()->streamMediaFile($mediaFolder, $filename);
            }

            // Try cache with BOTH original and sanitized IDs
            $mediaFolder = null;
            $pageId = $this->idUtils->sanitizeId($originalPageId);

            if ($this->cache()->hasPageFolder($originalPageId)) {
                // Cache hit with original ID (page-abc-123...)
                $pageFolder = $this->cache()->getPageFolder($originalPageId);
                try {
                    $mediaFolder = $pageFolder->get('_media');
                } catch (NotFoundException $e) {
                    // No media folder
                }
            } else if ($this->cache()->hasPageFolder($pageId)) {
                // Cache hit with sanitized ID (abc-123...)
                $pageFolder = $this->cache()->getPageFolder($pageId);
                try {
                    $mediaFolder = $pageFolder->get('_media');
                } catch (NotFoundException $e) {
                    // No media folder
                }
            }

            // If cache miss, search using ORIGINAL pageId
            if ($mediaFolder === null) {
                $mediaFolder = $this->findMediaFolderForPage($languageFolder, $originalPageId);
            }

            // Still nothing: the page may simply live in another language than
            // the one this user reads, which used to 404 every image on it
            // (#92). Only reached on a genuine miss, so the common case keeps
            // the single-folder walk above and pays nothing for this.
            if ($mediaFolder === null) {
                $located = $this->locatePageForMedia($originalPageId);
                if ($located !== null) {
                    $mediaFolder = ($located['result']['isHome'] ?? false)
                        ? $this->folderOrNull($located['languageFolder'], '_media')
                        : $this->folderOrNull($located['result']['folder'] ?? null, '_media');
                }
            }

            if ($mediaFolder === null) {
                throw new \Exception('Media folder not found');
            }

            return $this->media()->streamMediaFile($mediaFolder, $filename);
        } catch (NotFoundException $e) {
            throw new \Exception('Media not found');
        }
    }

    /**
     * Sanitize page ID
     */

    /**
     * Recursively find media folder for a page by uniqueId
     */
    private function findMediaFolderForPage($folder, string $uniqueId): ?\OCP\Files\Folder {
        return $this->media()->findMediaFolderForPage($folder, $uniqueId);
    }

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
     * @see PageShapeSanitizer::sanitizeText()
     */
    private function sanitizeText(string $text): string {
        return $this->shape()->sanitizeText($text);
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
     * @see PageShapeSanitizer::sanitizePage()
     */
    private function sanitizePage(array $data): array {
        return $this->shape()->sanitizePage($data);
    }

    /**
     * Get all versions of a page
     * Uses the standard IVersionManager interface for reliable version retrieval.
     * @throws \Exception if page not found
     */
    public function getPageVersions(string $pageId): array {
        $folder = $this->folders()->languageFolder();
        $result = null;

        // Check for uniqueId pattern (page-xxxx) like getPage() does. Follows
        // the page across language folders so an operation on a page the user
        // can see never fails with "Page not found" (issue #90).
        if (strpos($pageId, 'page-') === 0) {
            $result = $this->locatePageAnyLanguage($folder, $pageId);
        }

        // Fall back to legacy ID lookup
        if ($result === null) {
            $result = $this->findPageById($folder, $this->idUtils->sanitizeId($pageId));
        }

        if (!$result) {
            $this->logger->warning('[getPageVersions] Page not found: ' . $pageId);
            throw new \Exception('Page not found: ' . $pageId);
        }

        return $this->pageVersionService->listForFile($result['file']);
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
        $folder = $this->folders()->languageFolder();
        $result = null;

        // Check for uniqueId pattern (page-xxxx) like getPage() does. Follows
        // the page across language folders so an operation on a page the user
        // can see never fails with "Page not found" (issue #90).
        if (strpos($pageId, 'page-') === 0) {
            $result = $this->locatePageAnyLanguage($folder, $pageId);
        }

        // Fall back to legacy ID lookup
        if ($result === null) {
            $result = $this->findPageById($folder, $this->idUtils->sanitizeId($pageId));
        }

        if (!$result) {
            throw new \Exception('Page not found: ' . $pageId);
        }

        $restoredData = $this->pageVersionService->restoreToTimestamp(
            $result['file'],
            $result['folder'],
            $timestamp
        );

        // Return data with id for frontend (id is derived from folder name)
        // For home page it's 'home', otherwise use the folder basename
        $resolvedId = ($pageId === 'home') ? 'home' : $result['folder']->getName();
        return array_merge(['id' => $resolvedId], $restoredData);
    }

    /**
     * Get human-readable relative time
     */
    private function getRelativeTime(int $timestamp): string {
        // Pure formatting, extracted to a stateless Format/RelativeTime helper.
        return (new \OCA\IntraVox\Service\Format\RelativeTime())->format($timestamp);
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
     * The groupfolder a node lives in, or null when it is not in one.
     *
     * Read from the mount path (`/__groupfolders/{id}/…`) rather than from
     * MetaVox's value table, which only lists files that already have values
     * stored and so cannot answer this for a page with empty fields.
     *
     * @param \OCP\Files\Node $node
     */
    private function groupfolderIdForNode($node): ?int {
        try {
            // The mount knows its own folder id. Note that getPath() is NOT a
            // source for this: it returns the per-user mount path
            // (/Rik/files/IntraVox/…), not /__groupfolders/{id}/…, so parsing
            // it yields nothing.
            $mount = $node->getMountPoint();
            if (method_exists($mount, 'getFolderId')) {
                return (int)$mount->getFolderId();
            }

            // Fallback for mount types that do not expose it: the storage id
            // still carries the folder id (local::…/__groupfolders/1/).
            if (preg_match('#/__groupfolders/(\d+)/#', $node->getStorage()->getId(), $m)) {
                return (int)$m[1];
            }
        } catch (\Throwable $e) {
            // A node whose mount cannot be read is not worth failing the page
            // response over; the MetaVox tab simply stays empty.
        }
        return null;
    }

    /**
     * Get metadata for a page (simplified version using already loaded page data)
     */
    public function getPageMetadata(string $pageId): array {
        // Get page and file info
        $folder = $this->folders()->languageFolder();
        $result = null;

        // Check for uniqueId pattern (page-xxxx). Follows the page across
        // language folders so an operation on a page the user can see never
        // fails with "Page not found" (issue #90).
        if (strpos($pageId, 'page-') === 0) {
            $result = $this->locatePageAnyLanguage($folder, $pageId);
        }

        // Fall back to legacy ID lookup
        if ($result === null) {
            $result = $this->findPageById($folder, $this->idUtils->sanitizeId($pageId));
        }

        if (!$result) {
            throw new \Exception('Page not found: ' . $pageId);
        }

        $file = $result['file'];
        $folder = $result['folder'];

        // Get filesystem timestamps
        $mtime = $file->getMTime();
        $ctime = $file->getCreationTime();
        // Fallback: if creation time is 0 (not supported by groupfolder/storage), use mtime
        if ($ctime === 0) {
            $ctime = $mtime;
        }

        // Get page content for other metadata
        $content = $file->getContent();
        $data = json_decode($content, true);

        // Enrich with path data (file gates canWrite/canEdit, #70)
        $data = $this->enrichWithPathData($data, $folder, $file);

        // Format path to show full Nextcloud path starting with /IntraVox/
        $displayPath = isset($data['path']) ? '/IntraVox/' . $data['path'] : '';

        // Get file info for MetaVox integration
        $fileId = $file->getId();
        $size = $file->getSize();
        $internalPath = $file->getInternalPath();
        $storagePath = $file->getPath();

        // Get parent folder fileId for Files app link
        $parentFolderId = null;
        try {
            $parentFolderId = $folder->getId();
        } catch (\Exception $e) {
            // Not critical
        }

        // Get permissions from enriched data (uses Nextcloud's native permissions)
        $permissions = $data['permissions'] ?? [
            'canRead' => true,
            'canWrite' => false,
            'canCreate' => false,
            'canDelete' => false,
            'canShare' => false,
            'raw' => 1
        ];

        // Folder-rename support (#95): null when the page has no renamable
        // folder/JSON pair (homepage, loose legacy file) — the rename dialog
        // hides the folder option in that case.
        $renameLayout = $this->resolvePageLayoutForRename($result, is_array($data) ? $data : []);

        // Return metadata using filesystem timestamps
        $metadata = [
            'title' => $data['title'] ?? 'Untitled',
            'uniqueId' => $data['uniqueId'] ?? '',
            'language' => $data['language'] ?? $this->getUserLanguage(),
            'created' => $ctime,
            'createdFormatted' => date('Y-m-d H:i:s', $ctime),
            'createdRelative' => $this->getRelativeTime($ctime),
            'modified' => $mtime,
            'modifiedFormatted' => date('Y-m-d H:i:s', $mtime),
            'modifiedRelative' => $this->getRelativeTime($mtime),
            // Path-related data (already in page)
            'path' => $storagePath,
            'depth' => $data['depth'] ?? 0,
            'parentId' => $data['parentId'] ?? null,
            'parentPath' => $data['parentPath'] ?? null,
            'department' => $data['department'] ?? null,
            'canEdit' => $permissions['canWrite'] ?? false,
            // Additional data for MetaVox integration
            'fileId' => $fileId,
            'size' => $size,
            'parentFolderId' => $parentFolderId,
            'folderName' => $renameLayout !== null ? $renameLayout['folder']->getName() : null,
            'mountPoint' => 'IntraVox',
            // Permissions - use Nextcloud's native permissions
            'permissions' => $permissions,
        ];

        return $metadata;
    }

    /**
     * Update page metadata (title only for now, similar to Files rename)
     */
    public function updatePageMetadata(string $pageId, array $metadata): array {
        $folder = $this->folders()->languageFolder();
        $result = null;

        // Check for uniqueId pattern (page-xxxx). Follows the page across
        // language folders so an operation on a page the user can see never
        // fails with "Page not found" (issue #90).
        if (strpos($pageId, 'page-') === 0) {
            $result = $this->locatePageAnyLanguage($folder, $pageId);
        }

        // Fall back to legacy ID lookup
        if ($result === null) {
            $result = $this->findPageById($folder, $this->idUtils->sanitizeId($pageId));
        }

        if (!$result) {
            throw new \Exception('Page not found: ' . $pageId);
        }

        $file = $result['file'];

        // Get current content
        $content = $file->getContent();
        $data = json_decode($content, true);

        // Update only allowed fields
        $changed = false;
        $oldTitle = $data['title'] ?? '';
        $newTitle = null;
        if (isset($metadata['title']) && $metadata['title'] !== $data['title']) {
            $newTitle = $this->sanitizeText($metadata['title']);
            $data['title'] = $newTitle;
            $changed = true;
        }

        // Save if changed
        if ($changed) {
            // Create version before update using VersionsBackend
            $this->pageVersionService->createBeforeUpdate($file);
            $file->putContent(json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

            // Keep the navigation menu label in sync when the page is renamed,
            // but only when the label still matched the old title — a label that
            // was deliberately set to something else is left untouched.
            if ($newTitle !== null && !empty($data['uniqueId'])) {
                $this->syncNavigationTitle((string)$data['uniqueId'], $oldTitle, $newTitle);
            }
        }

        // Optional folder rename riding along with the title change (#95).
        // Best-effort by design: the title rename above already succeeded, and
        // a folder that keeps its old name is exactly today's behaviour.
        $folderRename = null;
        if (isset($metadata['folderName']) && is_string($metadata['folderName']) && $metadata['folderName'] !== '') {
            $folderRename = $this->renamePageFolder($result, $metadata['folderName'], is_array($data) ? $data : []);
        }

        // Refetch by uniqueId when we have one: after a folder rename, a
        // legacy slug-shaped $pageId no longer resolves.
        $refetchId = (is_array($data) && !empty($data['uniqueId'])) ? (string)$data['uniqueId'] : $pageId;
        $response = $this->getPageMetadata($refetchId);
        if ($folderRename !== null) {
            $response['folderRename'] = $folderRename;
        }
        return $response;
    }

    /**
     * Keep the navigation menu label in sync after a page rename (issue #84).
     *
     * Walks the navigation tree for the current language and, for every item
     * that points at this page (by uniqueId) whose label still equals the old
     * page title, updates the label to the new title. Items whose label was
     * deliberately set to something else are left as-is. Best-effort: a failure
     * here must never break the rename itself.
     */
    private function syncNavigationTitle(string $uniqueId, string $oldTitle, string $newTitle): void {
        if ($oldTitle === $newTitle) {
            return;
        }
        try {
            $navigation = $this->navigationService->getNavigation();
            $items = $navigation['items'] ?? [];
            $changed = false;

            $walk = function (array &$items) use (&$walk, $uniqueId, $oldTitle, $newTitle, &$changed): void {
                foreach ($items as &$item) {
                    $itemId = $item['uniqueId'] ?? $item['pageId'] ?? null;
                    if ($itemId === $uniqueId && ($item['title'] ?? '') === $oldTitle) {
                        $item['title'] = $newTitle;
                        $changed = true;
                    }
                    if (isset($item['children']) && is_array($item['children'])) {
                        $walk($item['children']);
                    }
                }
                unset($item);
            };
            $walk($items);

            if ($changed) {
                $navigation['items'] = $items;
                $this->navigationService->saveNavigation($navigation);
            }
        } catch (\Throwable $e) {
            $this->logger->warning('[PageService] Could not sync navigation title after rename: ' . $e->getMessage());
        }
    }

    /**
     * Classify how a located page pairs its JSON with a folder, for the
     * folder-rename option (#95).
     *
     * Returns null when there is nothing that may be renamed as a pair: the
     * homepage (both the loose `home.json` and a configured homepage page),
     * and loose JSON files whose base name matches no folder (their
     * containing folder is shared with other pages). Otherwise tells the two
     * supported shapes apart:
     *   - 'inside': modern model, `{slug}/{slug}.json`
     *   - 'beside': legacy model, `{slug}.json` next to `{slug}/`
     *
     * @param array{file?:\OCP\Files\File, folder?:\OCP\Files\Folder, isHome?:bool} $result
     * @return array{layout:string, file:\OCP\Files\File, folder:\OCP\Files\Folder}|null
     */
    private function resolvePageLayoutForRename(array $result, array $pageData): ?array {
        if (!empty($result['isHome'])) {
            return null;
        }
        $uniqueId = (string)($pageData['uniqueId'] ?? '');
        $language = isset($pageData['language']) && is_string($pageData['language'])
            ? $pageData['language'] : null;
        if ($uniqueId !== '' && $this->isHomepage($uniqueId, $language)) {
            return null;
        }
        $file = $result['file'] ?? null;
        $folder = $result['folder'] ?? null;
        if (!$file instanceof \OCP\Files\File || !$folder instanceof \OCP\Files\Folder) {
            return null;
        }
        $fileName = $file->getName();
        if (substr($fileName, -5) !== '.json' || substr($fileName, 0, -5) !== $folder->getName()) {
            return null;
        }
        $fileParent = dirname($file->getPath());
        $folderPath = $folder->getPath();
        if ($fileParent === $folderPath) {
            return ['layout' => 'inside', 'file' => $file, 'folder' => $folder];
        }
        if ($fileParent === dirname($folderPath)) {
            return ['layout' => 'beside', 'file' => $file, 'folder' => $folder];
        }
        return null;
    }

    /**
     * Rename a page's folder and its paired `.json` to a new slug (#95).
     *
     * The two nodes MUST stay a pair — a folder whose JSON carries another
     * base name is exactly the mismatch the index rebuild skips as "not a
     * page" — so when the second rename fails the first is rolled back.
     * Collisions get a `-2`/`-3` suffix like createPage and movePage; for
     * the inside layout the suffix loop also avoids the name of any child
     * entry, because `{slug}/{slug}.json` next to a child folder `{slug}`
     * would be read as that child's beside-layout JSON.
     *
     * Never throws: the title rename this rides along with has already
     * succeeded, so the outcome is reported instead.
     *
     * @return array{status:string, reason?:string, folderName?:string}
     */
    private function renamePageFolder(array $result, string $requestedName, array $pageData): array {
        $layout = $this->resolvePageLayoutForRename($result, $pageData);
        if ($layout === null) {
            return ['status' => 'skipped', 'reason' => 'layout'];
        }

        try {
            $newName = $this->idUtils->sanitizeId($requestedName);
        } catch (\InvalidArgumentException $e) {
            return ['status' => 'failed', 'reason' => 'invalid_name'];
        }

        $file = $layout['file'];
        $folder = $layout['folder'];
        $inside = $layout['layout'] === 'inside';
        $folderName = $folder->getName();
        if ($newName === $folderName) {
            return ['status' => 'skipped', 'reason' => 'unchanged', 'folderName' => $folderName];
        }

        if (!$folder->isUpdateable() || !$file->isUpdateable()) {
            return ['status' => 'failed', 'reason' => 'permission'];
        }

        $parent = $folder->getParent();
        $candidate = $newName;
        $counter = 2;
        while ($parent->nodeExists($candidate)
            || $parent->nodeExists($candidate . '.json')
            || ($inside && ($folder->nodeExists($candidate) || $folder->nodeExists($candidate . '.json')))) {
            $candidate = $newName . '-' . $counter;
            $counter++;
        }

        $oldFolderPath = $folder->getPath();
        $parentPath = rtrim(dirname($oldFolderPath), '/');
        $newFolderPath = $parentPath . '/' . $candidate;

        try {
            if ($inside) {
                $fileName = $file->getName();
                $moved = $folder->move($newFolderPath);
                $movedFolder = $moved instanceof \OCP\Files\Folder ? $moved : $folder;
                try {
                    $movedFolder->get($fileName)->move($newFolderPath . '/' . $candidate . '.json');
                } catch (\Throwable $inner) {
                    try {
                        $movedFolder->move($oldFolderPath);
                    } catch (\Throwable $rollback) {
                        $this->logger->error(
                            'renamePageFolder: rollback failed — folder and JSON are out of step, run occ intravox:reindex after repairing',
                            ['folder' => $newFolderPath, 'error' => $rollback->getMessage()]
                        );
                    }
                    throw $inner;
                }
            } else {
                $oldFilePath = $file->getPath();
                $moved = $file->move($parentPath . '/' . $candidate . '.json');
                $movedFile = $moved instanceof \OCP\Files\File ? $moved : $file;
                try {
                    $folder->move($newFolderPath);
                } catch (\Throwable $inner) {
                    try {
                        $movedFile->move($oldFilePath);
                    } catch (\Throwable $rollback) {
                        $this->logger->error(
                            'renamePageFolder: rollback failed — folder and JSON are out of step, run occ intravox:reindex after repairing',
                            ['file' => $oldFilePath, 'error' => $rollback->getMessage()]
                        );
                    }
                    throw $inner;
                }
            }
        } catch (\Throwable $e) {
            $this->logger->error('renamePageFolder: rename failed', [
                'from' => $oldFolderPath,
                'to' => $newFolderPath,
                'error' => $e->getMessage(),
            ]);
            return ['status' => 'failed', 'reason' => 'rename_failed'];
        }

        // Same contract as movePage: the disk rename already succeeded, so an
        // index failure must not fail the operation — occ intravox:reindex repairs.
        try {
            $this->pageIndexService->repathSubtree($oldFolderPath, $newFolderPath);
        } catch (\Throwable $e) {
            $this->logger->warning('renamePageFolder: could not repath index subtree', [
                'from' => $oldFolderPath,
                'to' => $newFolderPath,
                'error' => $e->getMessage(),
            ]);
        }

        $this->clearCache();
        return ['status' => 'renamed', 'folderName' => $candidate];
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
        // Verify page exists. Had neither a uniqueId branch nor a cross-language
        // fallback, so labelling a version failed on any page-… id and on any
        // page outside the caller's own language (#90).
        $result = $this->locatePageForOperation($pageId);

        if (!$result) {
            throw new PageNotFoundException('Page not found: ' . $pageId);
        }

        $this->pageVersionService->setLabel($result['file'], $timestamp, $label);
    }

    /**
     * Get version content for preview
     * Uses IVersionManager for reliable version content retrieval across all storage types.
     */
    public function getVersionContent(string $pageId, int $timestamp): array {
        $folder = $this->folders()->languageFolder();
        $result = null;

        // Check for uniqueId pattern (page-xxxx) like getPage() does. Follows
        // the page across language folders so an operation on a page the user
        // can see never fails with "Page not found" (issue #90).
        if (strpos($pageId, 'page-') === 0) {
            $result = $this->locatePageAnyLanguage($folder, $pageId);
        }

        // Fall back to legacy ID lookup
        if ($result === null) {
            $result = $this->findPageById($folder, $this->idUtils->sanitizeId($pageId));
        }

        if (!$result) {
            throw new \Exception('Page not found: ' . $pageId);
        }

        return $this->pageVersionService->contentAtTimestamp($result['file'], $timestamp);
    }

    /**
     * Get current page content for comparison
     */
    public function getCurrentPageContent(string $pageId): array {
        // Same shape as updateVersionLabel(): no uniqueId branch and no
        // cross-language fallback, so the "compare with current" panel in the
        // version history broke on page-… ids and on foreign-language pages.
        $result = $this->locatePageForOperation($pageId);

        if (!$result) {
            throw new PageNotFoundException('Page not found: ' . $pageId);
        }

        $file = $result['file'];
        $content = $file->getContent();

        return [
            'title' => $result['page']['name'] ?? 'Untitled',
            'content' => $content,
            'rawContent' => $content
        ];
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
        try {
            // Must resolve the page exactly as the upload does, or the
            // duplicate check inspects a different folder than the one written
            // to — silently answering "no duplicate" and overwriting nothing,
            // or prompting about a file the upload will not touch (#92).
            $located = $this->locatePageForMedia($pageId);
            if ($located === null) {
                return false;
            }

            return $this->media()->mediaExists(
                $this->mediaHostFolder($located),
                $located['languageFolder'],
                $filename,
                $targetFolder
            );
        } catch (\Exception $e) {
            return false;
        }
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
        $validated = $this->media()->validateUpload($file);

        // Sanitize original filename
        $filename = $this->mediaSanitizer->sanitizeFilename($file['name']);

        // Check if file exists
        $fileExists = $this->checkMediaExists($pageId, $filename, $targetFolder);
        if ($fileExists && !$overwrite) {
            throw new \Exception('File already exists');
        }

        // Resolve the page first: both branches want the language folder the
        // page really lives in, not the uploader's own profile language (#92).
        $located = $this->locatePageForMedia($pageId);
        if ($located === null) {
            throw new PageNotFoundException('Page not found: ' . $pageId);
        }

        // Get target folder based on targetFolder parameter
        if ($targetFolder === 'resources') {
            $uploadFolder = $this->media()->resourcesFolderFor($located['languageFolder']);
        } else {
            $hostFolder = $this->mediaHostFolder($located);
            if ($hostFolder === null) {
                throw new PageNotFoundException('Page not found: ' . $pageId);
            }
            $uploadFolder = $this->media()->mediaFolderFor($hostFolder);
        }

        // Upload file (content already sanitized for SVG)
        $this->media()->writeMediaFile(
            $uploadFolder,
            $filename,
            $validated['content'],
            $fileExists && $overwrite
        );

        // Invalidate the per-page content cache so the next getPage()
        // reflects the new media file. See uploadMedia() for context.
        $this->clearCache($pageId);

        return [
            'filename' => $filename,
            'exists' => $fileExists
        ];
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
        try {
            // List from the page's own language folder. getReadLanguageFolder()
            // answers "what should this USER see", which for the Shared Library
            // of a specific page is the wrong question: it listed one language's
            // _resources while the widget resolved images from another, so the
            // picker showed names whose previews always 404'd (#92).
            $located = $this->locatePageForMedia($pageId);
            if ($located === null) {
                return [];
            }

            return $this->media()->listMedia(
                $this->mediaHostFolder($located),
                $located['languageFolder'],
                $folderType,
                $subPath
            );
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Get media file from _resources folder
     *
     * @param string $path File path (can include subfolders)
     * @return \OCP\Files\File File object
     * @throws NotFoundException If file not found
     */
    public function getResourcesMediaFile(string $path) {
        // Path is already sanitized by ApiController::sanitizePath()
        //
        // This route carries no pageId, so the page's language cannot be
        // resolved the way the other media paths do. Look in the language the
        // user reads first, then in the remaining language folders: a shared
        // asset referenced from a page in another language is still a legitimate
        // request, and answering 404 blanked those images (#92).
        $readFolder = $this->folders()->readLanguageFolder();

        $file = $this->findResourceIn($readFolder, $path);
        if ($file !== null) {
            return $file;
        }

        $baseFolder = $this->folders()->intraVox();
        $searchedPath = $readFolder->getPath();

        foreach ($this->getCachedDirectoryListing($baseFolder) as $item) {
            if ($item->getType() !== \OCP\Files\FileInfo::TYPE_FOLDER
                || !($item instanceof \OCP\Files\Folder)) {
                continue;
            }
            if (!preg_match('/^[a-z]{2,3}$/', $item->getName())
                || $item->getPath() === $searchedPath) {
                continue;
            }
            $file = $this->findResourceIn($item, $path);
            if ($file !== null) {
                return $file;
            }
        }

        throw new NotFoundException('Media file not found: ' . $path);
    }

    /**
     * Resolve $path inside one language folder's `_resources`, or null.
     * Kept separate so the cross-language walk above reads as a walk.
     */
    private function findResourceIn(\OCP\Files\Folder $languageFolder, string $path): ?\OCP\Files\Node {
        return $this->media()->findResourceIn($languageFolder, $path);
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
        $folder = $this->folders()->readLanguageFolder();
        $pages = [];
        // Match the served language (recommended-language fallback, #75) so
        // the news cache key and date localisation agree with the folder.
        $language = $this->resolveEffectiveLanguage() ?? $this->folders()->userLanguage();

        // Version-counter cache: the news widget result depends on all pages in
        // the source folder plus user-supplied filters/sort/limit, plus the
        // user's group context (permissions). We don't want to rebuild on every
        // dashboard render, but invalidation must be instant on any page write.
        //
        // Strategy: a per-language counter that PageService::clearCache bumps
        // on every mutation. Cache entries embed the current counter value;
        // after a bump, every old entry is unreachable (no reader looks under
        // the stale counter), so they age out via TTL without ever serving
        // stale data. Plan B4 from the roadmap.
        $newsVersionKey = 'news_version_' . $language;
        $newsVersion = 0;
        $newsCacheKey = null;
        if ($this->cache()->isDistributedAvailable()) {
            $newsVersion = (int) ($this->cache()->getDistributed($newsVersionKey) ?? 0);
            $paramHash = md5(json_encode([
                $sourcePath, $filters, $filterOperator, $limit, $sortBy,
                $sortOrder, $sourcePageId, $filterPublished,
            ]));
            $newsCacheKey = 'news_' . $language . '_' . $this->groupContext->getGroupHash()
                . '_v' . $newsVersion . '_' . $paramHash;
            $cached = $this->cache()->getDistributed($newsCacheKey);
            if (is_string($cached)) {
                $decoded = json_decode($cached, true);
                if (is_array($decoded)) {
                    return $decoded;
                }
            }
        }

        // If sourcePageId is provided, find that page and use its folder as source
        // Also include the selected page itself in the results
        $sourcePageData = null;
        if (!empty($sourcePageId)) {
            try {
                $result = $this->findPageByUniqueId($folder, $sourcePageId);
                if ($result && isset($result['folder'])) {
                    $folder = $result['folder'];
                    // Store the source page data to include it in results
                    if (isset($result['file'])) {
                        $sourcePageData = $result;
                    }
                } else {
                    $this->logger->warning('News widget: Source page not found', ['sourcePageId' => $sourcePageId]);
                    return ['items' => [], 'total' => 0, 'metavoxAvailable' => $this->metaVox()->isMetaVoxAvailable()];
                }
            } catch (\Exception $e) {
                $this->logger->warning('News widget: Error finding source page', ['sourcePageId' => $sourcePageId, 'error' => $e->getMessage()]);
                return ['items' => [], 'total' => 0, 'metavoxAvailable' => $this->metaVox()->isMetaVoxAvailable()];
            }
        }
        // Legacy: If sourcePath is provided (but no sourcePageId), navigate to that folder
        elseif (!empty($sourcePath)) {
            $sourcePath = trim($sourcePath, '/');
            try {
                $folder = $folder->get($sourcePath);
            } catch (NotFoundException $e) {
                $this->logger->warning('News widget: Source folder not found', ['path' => $sourcePath]);
                return ['items' => [], 'total' => 0, 'metavoxAvailable' => $this->metaVox()->isMetaVoxAvailable()];
            }
        }

        // Recursively collect pages from the source folder.
        // Pass a hard cap to allow early-exit and prevent unbounded filesystem scans.
        $collectLimit = max($limit * 4, 200); // collect enough for filtering/sorting, cap at 200 minimum
        $this->findNewsPagesInFolder($folder, $pages, $language, $collectLimit);

        // Add the selected source page itself to the results (if sourcePageId was provided)
        if ($sourcePageData !== null && isset($sourcePageData['file'])) {
            $newsItem = $this->news()->buildSourcePageItem($sourcePageData, $language);
            if ($newsItem !== null) {
                // Add to beginning of pages array (it's the "parent" page)
                array_unshift($pages, $newsItem);
            }
        }

        // Apply MetaVox filters if any and if MetaVox is available
        if (!empty($filters) && $this->metaVox()->isMetaVoxAvailable()) {
            $pages = $this->applyMetaVoxFilters($pages, $filters, $filterOperator);
        }

        // Apply the publication filter when the widget asks for published pages
        // only. Not gated on MetaVox: the manual draft/published status must be
        // honoured even when no publication date fields are configured.
        if ($filterPublished) {
            $pages = $this->applyPublicationDateFilter($pages);
        }

        $total = count($pages);

        $pages = $this->news()->sortAndLimit($pages, $sortBy, $sortOrder, $limit);

        $result = [
            'items' => $pages,
            'total' => $total,
            'metavoxAvailable' => $this->metaVox()->isMetaVoxAvailable(),
        ];

        // Cache for 5 minutes — the version-counter scheme makes correctness
        // independent of TTL (a counter bump renders this entry unreachable),
        // so the TTL only bounds memory growth from orphaned entries.
        if ($this->cache()->isDistributedAvailable() && $newsCacheKey !== null) {
            $this->cache()->setDistributed($newsCacheKey, json_encode($result), PageCacheService::NEWS_TTL);
        }

        return $result;
    }

    /**
     * Recursively find news pages in a folder
     *
     * @param int $maxCollect Hard cap on items to collect (0 = unlimited)
     */
    private function findNewsPagesInFolder($folder, array &$pages, string $language, int $maxCollect = 0): void {
        $this->news()->findNewsPagesInFolder(
            $this->folders()->intraVox(),
            $folder,
            $pages,
            $language,
            $maxCollect
        );
    }

    /**
     * Extract an excerpt from page content (first text widget)
     */

    /**
     * Find the first image in a page's layout
     * Returns array with 'src' and 'mediaFolder' or null if no image found
     */

    /**
     * Apply MetaVox filters to pages
     *
     * @param array $pages Pages to filter
     * @param array $filters Filter definitions
     * @param string $operator 'AND' or 'OR'
     * @return array Filtered pages
     */
    private function applyMetaVoxFilters(array $pages, array $filters, string $operator = 'AND'): array {
        if (empty($filters) || !$this->metaVox()->isMetaVoxAvailable()) {
            return $pages;
        }

        return $this->news()->applyMetaVoxFilters(
            $pages,
            $filters,
            $operator,
            fn(array $fileIds): array => $this->metaVox()->getMetaVoxDataForFiles($fileIds)
        );
    }

    /**
     * Filter pages based on publication dates from MetaVox fields
     *
     * Logic: (Publish date is empty OR Publish date <= today)
     *    AND (Expiration date is empty OR Expiration date > today)
     *
     * @param array $pages Pages to filter
     * @return array Filtered pages that are currently published
     */
    private function applyPublicationDateFilter(array $pages): array {
        // The gate itself stays here — the tree, search and single-page reads
        // share it — and is handed to the news service as callables.
        return $this->news()->applyPublicationDateFilter(
            $pages,
            fn(array $fileIds): array => $this->publicationState()->publicationMetaForFiles($fileIds),
            fn(array $page, array $meta): string => $this->publicationState()->effectivePublishState($page, $meta)
        );
    }

    /**
     * The date-only parser used by the MetaVox filter operators moved to
     * NewsPageService with matchesFilter(), its only caller. The publication
     * paths here use the time-aware parseDateTime() above instead.
     */

    /**
     * Format a timestamp in a localized date format
     */
    private function formatDateLocalized(int $timestamp, string $language): string {
        return $this->news()->formatDateLocalized($timestamp, $language);
    }

    /**
     * Check if a folder contains any pages (recursively)
     */
    private function folderContainsPages($folder): bool {
        return $this->news()->folderContainsPages($folder);
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
        try {
            // Get the source page
            $pageData = $this->getPage($pageUniqueId);
            if (!$pageData) {
                return ['success' => false, 'error' => 'Page not found'];
            }

            // Reserve a collision-free template folder (+_media)
            $langFolder = $this->folders()->languageFolder();
            [$templateId, $templateFolder, $templateMediaFolder] =
                $this->pageTemplateService->newTemplateFolder($langFolder, $this->idUtils->sanitizeId($templateTitle));

            // Prepare template data
            $templateData = $pageData;
            $templateData['uniqueId'] = 'template-' . $this->idUtils->generateUUID();
            $templateData['title'] = $templateTitle;
            $templateData['description'] = $templateDescription ?? '';
            $templateData['isTemplate'] = true;
            $templateData['created'] = time();
            $templateData['createdBy'] = $this->userId;
            $templateData['sourcePageId'] = $pageUniqueId;

            // Remove page-specific data
            unset($templateData['path']);
            unset($templateData['parentPath']);

            // Copy media files from source page to template
            $pageFolder = $this->findPageFolder($pageUniqueId);
            if ($pageFolder && $pageFolder->nodeExists('_media')) {
                $sourceMediaFolder = $pageFolder->get('_media');
                if ($sourceMediaFolder instanceof \OCP\Files\Folder) {
                    $this->copyMediaFolderContents($sourceMediaFolder, $templateMediaFolder);
                }
            }

            // Write template JSON
            $jsonFile = $templateFolder->newFile($templateId . '.json');
            $jsonFile->putContent(json_encode($templateData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

            $this->logger->info('Created template: ' . $templateId . ' from page: ' . $pageUniqueId);

            return [
                'success' => true,
                'templateId' => $templateId,
                'template' => [
                    'id' => $templateId,
                    'uniqueId' => $templateData['uniqueId'],
                    'title' => $templateData['title'],
                    'description' => $templateData['description'],
                    'created' => $templateData['created'],
                    'createdBy' => $templateData['createdBy'],
                ],
            ];
        } catch (\Exception $e) {
            $this->logger->error('Failed to save as template: ' . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
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
        try {
            // Get template data
            $templateData = $this->getTemplate($templateId);
            if ($templateData === null) {
                return ['success' => false, 'error' => 'Template not found'];
            }

            // Prepare page data from template
            $pageData = $templateData;

            // Generate new page ID and uniqueId
            $pageId = $this->idUtils->sanitizeId($pageTitle);
            $pageData['id'] = $pageId;
            $pageData['title'] = $pageTitle;
            $pageData['uniqueId'] = 'page-' . $this->idUtils->generateUUID();
            $pageData['created'] = time();
            $pageData['modified'] = time();

            // Remove template-specific fields
            unset($pageData['isTemplate']);
            unset($pageData['description']);
            unset($pageData['createdBy']);
            unset($pageData['sourcePageId']);

            // New pages from templates always start as draft
            $pageData['status'] = 'draft';

            // Create the page using existing method
            $createdPage = $this->createPage($pageData, $parentPath);

            // Copy media files from template to new page
            $templatesFolder = $this->pageTemplateService->templatesFolder($this->folders()->languageFolder());
            if ($templatesFolder && $templatesFolder->nodeExists($templateId)) {
                $templateFolder = $templatesFolder->get($templateId);
                if ($templateFolder instanceof \OCP\Files\Folder && $templateFolder->nodeExists('_media')) {
                    $templateMediaFolder = $templateFolder->get('_media');

                    // Get the new page's folder (should be in cache from createPage)
                    $newPageFolder = $this->findPageFolder($createdPage['uniqueId']);
                    $this->logger->info('Template media copy: page folder found = ' . ($newPageFolder ? 'yes' : 'no') . ' for ' . $createdPage['uniqueId']);
                    if ($newPageFolder && $templateMediaFolder instanceof \OCP\Files\Folder) {
                        // Create _media folder if not exists
                        if (!$newPageFolder->nodeExists('_media')) {
                            $newPageFolder->newFolder('_media');
                        }
                        $pageMediaFolder = $newPageFolder->get('_media');
                        if ($pageMediaFolder instanceof \OCP\Files\Folder) {
                            $this->copyMediaFolderContents($templateMediaFolder, $pageMediaFolder);
                        }
                    }
                }
            }

            $this->logger->info('Created page from template: ' . $templateId . ' -> ' . $createdPage['uniqueId']);

            // Re-fetch through getPage() so the response includes
            // enrichWithPathData (path, breadcrumb info, permissions) and
            // a sanitize pass — the same shape the frontend gets on a
            // normal page load. Without this the editor mounts with a
            // half-populated page and rendered blank until manual save +
            // reload. Falls back to createdPage if the fresh read fails
            // for any reason (e.g. ACL race on a brand-new folder).
            try {
                $fullPage = $this->getPage($createdPage['uniqueId']);
            } catch (\Exception $e) {
                $this->logger->warning(
                    '[createPageFromTemplate] getPage failed on freshly created page, falling back to validated data',
                    ['uniqueId' => $createdPage['uniqueId'], 'error' => $e->getMessage()]
                );
                $fullPage = $createdPage;
            }

            return [
                'success' => true,
                'page' => $fullPage,
            ];
        } catch (\Exception $e) {
            $this->logger->error('Failed to create page from template: ' . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
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
        $languageFolder = $this->folders()->languageFolder();

        // A copy follows its source across language folders, like every other
        // operation on an existing page (#90).
        $source = $this->locatePageAnyLanguage($languageFolder, $sourceUniqueId);
        if ($source === null || !isset($source['file'])) {
            throw new PageNotFoundException('Page not found: ' . $sourceUniqueId);
        }

        $sourceData = json_decode($source['file']->getContent(), true);
        if (!is_array($sourceData)) {
            throw new \Exception('Could not read source page');
        }

        // Determine the destination parent path.
        $parentPath = null;
        if ($targetParentId !== null && $targetParentId !== '') {
            $targetParent = $this->locatePageAnyLanguage($languageFolder, $targetParentId);
            if ($targetParent === null || !isset($targetParent['folder'])) {
                throw new PageNotFoundException('Target parent not found: ' . $targetParentId);
            }
            // getRelativePathFromRoot() keeps the leading language segment, and
            // getOrCreateFolderPath() honours it, so the copy lands in the
            // target parent's language rather than the copier's.
            $parentPath = $this->getRelativePathFromRoot($targetParent['folder']);
        } elseif (isset($source['folder'])) {
            // Same parent as the source. For a page at the language ROOT,
            // dirname() yields '.', which used to become null and sent the copy
            // to the reader's own language folder — an English page copied by a
            // German user landed in de/. Fall back to the source's own language
            // root instead, so a copy never changes language.
            $sourceRelPath = $this->getRelativePathFromRoot($source['folder']);
            $sourceParentPath = dirname($sourceRelPath);
            if ($sourceParentPath === '.' || $sourceParentPath === '') {
                $sourceLanguage = $this->languageOfFolder($source['folder']);
                $parentPath = $sourceLanguage;
            } else {
                $parentPath = $sourceParentPath;
            }
        }

        // Build the copy's page data (fresh identity, draft status).
        // Decode the source title first: it is stored HTML-encoded (sanitizeText),
        // and createPage re-encodes it — without decoding, "Tips &amp; Tricks"
        // would double-encode to "Tips &amp;amp; Tricks (copy)".
        $baseTitle = $this->htmlSanitizer->decodeEntitiesRecursive((string)($sourceData['title'] ?? 'Untitled'));
        $title = $newTitle !== null && $newTitle !== '' ? $newTitle : $baseTitle . ' (copy)';
        $pageData = $sourceData;
        unset($pageData['order']); // never inherit sibling order
        // A copy is a new page, not a translation of the source. Inheriting the
        // group made the copy a same-language member of it, which is the exact
        // state createTranslation() refuses to create because it makes the
        // language switcher ambiguous. createPage() assigns a fresh group.
        unset($pageData['translationGroup']);
        $pageData['id'] = $this->idUtils->sanitizeId($title);
        $pageData['title'] = $title;
        $pageData['uniqueId'] = 'page-' . $this->idUtils->generateUUID();
        $pageData['status'] = 'draft';
        $pageData['created'] = time();
        $pageData['modified'] = time();

        $createdPage = $this->createPage($pageData, $parentPath);

        // Copy media assets from the source page folder into the copy.
        $this->copyPageMedia($source['folder'] ?? null, $createdPage['uniqueId'], 'copyPage');

        $this->clearCache();

        try {
            return $this->getPage($createdPage['uniqueId']);
        } catch (\Exception $e) {
            return $createdPage;
        }
    }

    /**
     * Give a newly derived page its own copy of the source page's media.
     *
     * A page's images live in a `_media` folder beside its JSON, and the JSON
     * stores only the FILE NAME — the URL is built client-side from whichever
     * page is being viewed (see WidgetEditor.vue:696). So a derived page needs
     * the files themselves and nothing rewritten; without them every image
     * resolves to a 404 under the new page id.
     *
     * Copies rather than shares the files, so editing or deleting an image on
     * the translation cannot alter the original.
     *
     * Failure is logged, not thrown: losing the images is bad, but it is not
     * worth discarding a page that was already written to disk.
     *
     * @param \OCP\Files\Folder|null $sourceFolder folder holding the source page
     * @param string $newUniqueId the derived page
     * @param string $context caller name, for the log line
     */
    private function copyPageMedia(?\OCP\Files\Folder $sourceFolder, string $newUniqueId, string $context): void {
        $this->media()->copyPageMedia(
            $sourceFolder,
            $this->findPageFolder($newUniqueId),
            $context
        );
    }

    private function copyMediaFolderContents(\OCP\Files\Folder $source, \OCP\Files\Folder $target): void {
        $this->media()->copyMediaFolderContents($source, $target);
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
