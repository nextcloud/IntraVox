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
use OCA\IntraVox\Service\Language\LanguageResolver;
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
    private const MAX_COLUMNS = 5;

    private IUserSession $userSession;
    private string $userId;
    // The `= null` defaults below are LOAD-BEARING, not cosmetic. They are the
    // only reason the test harness leaves these lazy services alone: a
    // nullable-default property reports isInitialized()===true, so
    // BuildsPageService::fillPageServiceDependencies skips it and the real
    // accessor (metaVox()/publicationState()/maintenance()/language()) builds
    // the genuine collaborator. Drop a default to `?Foo $x;` and the auto-fill
    // mocks it with a double that answers null/[] to everything, silently
    // breaking the path it backs. Keep the `= null`.
    /** Lazily-built MetaVox gateway; owns the memos that used to live here (Phase 3). */
    private \OCA\IntraVox\Service\Publication\MetaVoxGateway $metaVoxGateway;
    /** Lazily-built publication scheduling service (Phase 3). */
    /** Lazily-built CLI maintenance service (Phase 4). */
    private ?\OCA\IntraVox\Service\Maintenance\PageMaintenanceService $maintenanceSvc = null;
    /** Lazily-built page-search scorer (Phase "search"). */
    private ?\OCA\IntraVox\Service\Search\PageSearchEngine $searchEngine = null;
    /** Lazily-built index-based page lister (Phase "listing"). */
    private \OCA\IntraVox\Service\Listing\PageLister $pageLister;
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
    /** Lazily-built page-composition service (COMPOSE domain: copy/translate/template). */
    private ?\OCA\IntraVox\Service\Compose\PageCompositionService $compositionService = null;
    /** Lazily-built language-content-status reader (LANGUAGE-STATUS domain). */
    private ?\OCA\IntraVox\Service\Language\LanguageStatusService $languageStatusService = null;
    /** Lazily-built translation-query/link service (TRANSLATE-query domain). */
    private ?\OCA\IntraVox\Service\Translation\TranslationQueryService $translationQueryService = null;
    /** Lazily-built version-history service (VERSION/HISTORY domain). */
    /** Lazily-built homepage-resolution service (HOMEPAGE domain). */
    private \OCA\IntraVox\Service\Homepage\HomepageResolverService $homepageResolver;
    /** The folder/location substrate — now a DI-first-class ctor-injected service. */
    private \OCA\IntraVox\Service\Folder\FolderContext $folderContext;
    /** Stateless GroupFolder-id resolver (facade elimination phase 2). */
    private \OCA\IntraVox\Service\Util\GroupfolderResolver $groupfolders;
    private \OCA\IntraVox\Service\Cache\PageCacheInvalidator $cacheInvalidator;
    private LoggerInterface $logger;
    private IEventDispatcher $eventDispatcher;
    private PageIndexService $pageIndexService;
    private PageCacheService $cache;


    /**
     * Parse PHP size notation (e.g., '2M', '8M', '512K') to bytes
     */


    /**
     * Begin a batch: suppress the (expensive, blanket) clearCache() that each
     * mutation triggers, so a bulk operation clears the caches once at the end
     * instead of once per item. Must be paired with endDeferredClear() in a
     * finally block. Reentrant — nested begins are counted.
     */
    public function beginDeferredClear(): void {
        // The deferred-batch begin/end pair lives on PageCacheInvalidator now
        // (CACHE-CONTROL, god-class dissolution); this stays as a thin facade
        // delegator until BulkOperationService (the only caller) repoints.
        $this->cacheInvalidator->beginDeferred();
    }

    /**
     * End a batch. When the outermost begin is released, if any mutation asked
     * for a clear while suppressed, perform exactly one real clearCache() now.
     */
    public function endDeferredClear(): void {
        // Delegates to PageCacheInvalidator::endDeferred (CACHE-CONTROL), which
        // performs the deferred tree/distributed clear and, on exactly the
        // condition it reports, follows with the collaborator flush — the same
        // two-step this facade used to inline.
        $this->cacheInvalidator->endDeferred();
    }

    /**
     * Clear all request-level caches (call after mutations)
     */
    private function clearCache(?string $pageId = null): void {
        // The whole cache fan-out now lives in Cache/PageCacheInvalidator (facade
        // elimination phase 2). This stays a thin private delegator so the 19 test
        // subclasses that shadow clearCache() keep intercepting unchanged, and the
        // carved write/structure/reorder services keep receiving it as a $this-bound
        // closure with the same (?string $pageId) shape.
        $this->cacheInvalidator->invalidate($pageId);
    }

    private HtmlSanitizer $htmlSanitizer;
    private MediaSanitizer $mediaSanitizer;
    private PageShapeSanitizer $shapeSanitizer;
    private PageVersionService $pageVersionService;
    private PageTemplateService $pageTemplateService;
    private PageSearchHelper $searchHelper;
    private PagePathHelper $pathHelper;
    private PageIdUtils $idUtils;
    private LanguageService $languageService;
    private NavigationService $navigationService;
    private PermissionService $permissionService;
    private PageLocator $pageLocator;
    private TranslationGroupService $translationGroupService;
    private PageMediaService $pageMediaService;

    public function __construct(
        IUserSession $userSession,
        LoggerInterface $logger,
        IEventDispatcher $eventDispatcher,
        PageCacheService $cache,
        PageIndexService $pageIndexService,
        HtmlSanitizer $htmlSanitizer,
        MediaSanitizer $mediaSanitizer,
        PageShapeSanitizer $shapeSanitizer,
        PageVersionService $pageVersionService,
        PageTemplateService $pageTemplateService,
        PageSearchHelper $searchHelper,
        PagePathHelper $pathHelper,
        PageIdUtils $idUtils,
        LanguageService $languageService,
        NavigationService $navigationService,
        PermissionService $permissionService,
        PageLocator $pageLocator,
        TranslationGroupService $translationGroupService,
        PageMediaService $pageMediaService,
        \OCA\IntraVox\Service\Folder\FolderContext $folderContext,
        \OCA\IntraVox\Service\Util\GroupfolderResolver $groupfolders,
        \OCA\IntraVox\Service\Publication\MetaVoxGateway $metaVoxGateway,
        \OCA\IntraVox\Service\Cache\PageCacheInvalidator $cacheInvalidator,
        \OCA\IntraVox\Service\Listing\PageLister $pageListerService,
        \OCA\IntraVox\Service\Homepage\HomepageResolverService $homepageResolverService,
        ?string $userId
    ) {
        $this->folderContext = $folderContext;
        $this->groupfolders = $groupfolders;
        $this->metaVoxGateway = $metaVoxGateway;
        $this->cacheInvalidator = $cacheInvalidator;
        $this->pageLister = $pageListerService;
        $this->homepageResolver = $homepageResolverService;
        $this->userSession = $userSession;
        $this->logger = $logger;
        $this->eventDispatcher = $eventDispatcher;
        $this->pageIndexService = $pageIndexService;
        $this->htmlSanitizer = $htmlSanitizer;
        $this->mediaSanitizer = $mediaSanitizer;
        $this->shapeSanitizer = $shapeSanitizer;
        $this->pageVersionService = $pageVersionService;
        $this->pageTemplateService = $pageTemplateService;
        $this->searchHelper = $searchHelper;
        $this->pathHelper = $pathHelper;
        $this->idUtils = $idUtils;
        $this->languageService = $languageService;
        $this->navigationService = $navigationService;
        $this->permissionService = $permissionService;
        $this->pageLocator = $pageLocator;
        $this->translationGroupService = $translationGroupService;
        $this->pageMediaService = $pageMediaService;
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
     * Lazy seam for the language-content-status reader (LANGUAGE-STATUS domain).
     * Built from the FolderContext substrate + the plain PageLister/PageLocator
     * engines; the homepage-resolution subsystem and the real-content probe stay
     * on PageService and are passed to getContentStatus() as $this-bound closures.
     * Nullable-default so the harness auto-fill skips it.
     */
    private function languageStatus(): \OCA\IntraVox\Service\Language\LanguageStatusService {
        return $this->languageStatusService ??= new \OCA\IntraVox\Service\Language\LanguageStatusService(
            $this->folders(),
            $this->pageLister,
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
     * Lazy seam for the sibling reorderer (Phase "reorder"). Built from
     * locator() + the injected HomepageResolverService; the getLanguageFolder seam
     * is resolved by the delegator and passed in, and clearCache is passed as a
     * closure. Nullable-default so the harness auto-fill skips it (see the
     * load-bearing note).
     */
    private function reorderer(): \OCA\IntraVox\Service\Reorder\PageReorderer {
        return $this->reorderer ??= new \OCA\IntraVox\Service\Reorder\PageReorderer($this->locator(), $this->homepageResolver, $this->cacheInvalidator);
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
            $this->translationGroups(),
            $this->groupfolders
        );
    }

    /**
     * Lazy seam for the single-page reader (god-class dissolution, read cluster).
     * Built from the real read collaborators, all now plain DI instances — no
     * closures. The enricher is injected eagerly (step 4): building it is
     * $userId-free (MetaVoxGateway's ctor is inert), and it is only INVOKED on the
     * cache-MISS path, so the cache-hit early-return stays $userId-free.
     * resolveTranslations/groupfolderId live IN the read service over its own
     * injected TranslationGroupService/GroupfolderResolver. Nullable-default so the
     * harness auto-fill skips it.
     */
    private function readService(): \OCA\IntraVox\Service\Read\PageReadService {
        return $this->readService ??= new \OCA\IntraVox\Service\Read\PageReadService(
            $this->cache(),
            $this->locator(),
            $this->metaVox(),
            $this->pageDataEnricher(),
            $this->shape(),
            $this->permissionService,
            $this->idUtils,
            $this->logger,
            $this->folders(),
            $this->translationGroups(),
            $this->groupfolders
        );
    }

    /**
     * Lazy seam for the page mutation service (god-class dissolution, write
     * cluster). Built from the real deps + the injected HomepageResolverService;
     * the resolved language folder and the lookups/clearCache seams are passed
     * per-call as arg + closures. Nullable-default so the harness auto-fill skips it.
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
            $this->folders(),
            $this->locator(),
            $this->homepageResolver,
            $this->cacheInvalidator,
            $this->shape(),
            $this->media(),
            $this->cache(),
            new \OCA\IntraVox\Service\Path\PageDepthValidator($this->pathHelper, $this->languageService)
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
            $this->folders(),
            $this->homepageResolver,
            $this->cacheInvalidator,
            $this->locator(),
            $this->languageService,
            new \OCA\IntraVox\Service\Path\PageDepthValidator($this->pathHelper, $this->languageService)
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
            $this->logger,
            $this->homepageResolver,
            $this->cacheInvalidator,
            $this->locator()
        );
    }

    /**
     * Lazy seam for the page-composition service (COMPOSE domain). Built from the
     * template/translation-group/media engines + html sanitizer + id utils +
     * FolderContext substrate + userId + the injected PageReadService (the page
     * read) + PageCacheInvalidator + PageLocator + the shared PageCacheService
     * singleton (so findPageFolder's read/write hits the one pageFolders map every
     * service shares — #90); only createPage + writeTranslationGroup are passed per
     * call as $this-bound closures. Nullable-default so the harness auto-fill skips it.
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
            $this->logger,
            $this->cacheInvalidator,
            $this->readService(),
            $this->locator(),
            $this->cache()
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
     * The MetaVox DB/app-manager gateway. Now a DI-first-class ctor-injected
     * service (facade elimination phase 2): the container builds it with $userId
     * resolved once, so reaching it never lazily forces $userId — which is what
     * lets the getPage cache-hit early-return stay free of $userId. It owns its
     * own three request memos, so a single instance per request still preserves
     * the file->groupfolder map searchPages() reads back.
     */
    private function metaVox(): \OCA\IntraVox\Service\Publication\MetaVoxGateway {
        return $this->metaVoxGateway;
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
     * The concrete uniqueId (page-…) of the homepage for a language, suitable
     * for badging/comparison in the UI. When a pointer is set it is that
     * uniqueId; otherwise it resolves the legacy loose home.json to its real
     * uniqueId (not the literal 'home'). Optionally pass an already-built tree
     * to resolve the legacy home from it without an extra read.
     *
     * @param array<int,array>|null $tree Optional pre-built page tree.
     */
    public function resolveHomepageNodeUniqueId(?string $language = null, ?array $tree = null): string {
        return $this->homepageResolver->resolveHomepageNodeUniqueId($language, $tree);
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

    private function indexPathToRelative(string $storedPath): ?string {
        return $this->locator()->indexPathToRelative($this->folders()->intraVox(), $storedPath);
    }


    // languageFolderOfPageResult + languageDisplayName moved into
    // Structure/PageStructureService (god-class dissolution): movePage was their
    // only caller and self-sources both there now (over FolderContext +
    // LanguageService) instead of receiving them as $this-bound closures.

    /**
     * Public method to check if a page exists by uniqueId
     * Used by CommentsEntityListener to validate comment objectIds
     */
    public function pageExistsByUniqueId(string $uniqueId): bool {
        // The existence probe lives on Read/PageReadService now (RETRIEVE, god-class
        // dissolution); this stays as a thin facade delegator until the three
        // callers (AnalyticsController, CommentController, CommentsEntityListener)
        // repoint.
        return $this->readService()->pageExistsByUniqueId($uniqueId);
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
            function (array $result, string $group): void {
                $this->writeTranslationGroup($result, $group);
            }
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



    // The max-nesting-depth rule (getMaxDepthForPath + validateDepth) moved to
    // Path/PageDepthValidator (god-class dissolution): both writers of it —
    // createPage and movePage — now inject the validator instead of receiving the
    // rule as a $this-bound closure.

    /**
     * Create a new page
     *
     * @param array $data Page data (id, title, content, etc.)
     * @param string|null $parentPath Optional parent path for nested pages (e.g., "nl/departments/marketing")
     * @return array Created page data
     */
    public function createPage(array $data, ?string $parentPath = null): array {
        // The create body (validation, slug-dedup, group minting, write) lives in
        // Write/PageWriteService (AUTHOR domain), including the folder-path
        // provisioning (getOrCreateFolderPath is a private method there now).
        // Folder-substrate concerns come from the injected FolderContext and the
        // max-nesting-depth rule from the injected PageDepthValidator, so the
        // delegator threads in no closures. The page-folder cache write is
        // self-sourced by the write service (fase-7 T6): it holds the same
        // PageCacheService singleton, so the setPageFolder there lands in the one
        // pageFolders map findPageFolder reads back.
        return $this->writeService()->createPage($data, $parentPath);
    }

    /**
     * Update an existing page
     */
    public function updatePage(string $id, array $data): array {
        // The update body lives in Write/PageWriteService (write cluster). The
        // folder-substrate concerns (languageFolder / languageOfFolder /
        // userLanguage) are self-sourced there from the injected FolderContext —
        // the service resolves the language folder itself, after its !$user guard,
        // so the delegator no longer threads them in as closures. Page sanitisation
        // is the injected PageShapeSanitizer and cache invalidation the injected
        // PageCacheInvalidator.
        return $this->writeService()->updatePage($id, $data);
    }

    /**
     * Delete a page and all its assets
     */
    public function deletePage(string $id): void {
        // The delete body lives in Write/PageWriteService (god-class dissolution,
        // write cluster). The language folder is self-sourced there from the
        // injected FolderContext, resolved only AFTER the service's $id==='home'
        // guard — matching the pre-carve monolith, which checked 'home' before
        // touching getLanguageFolder(). The homepage check + cache invalidation come
        // from the injected HomepageResolverService + PageCacheInvalidator.
        $this->writeService()->deletePage($id);
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
    public function movePage(string $pageId, string $targetParentId): void {
        // The move body lives in Structure/PageStructureService (STRUCTURE domain).
        // Every substrate concern is self-sourced there now: the folder concerns
        // from the injected FolderContext, the page's own language folder + the
        // language display name from private methods over FolderContext +
        // LanguageService, and the max-nesting-depth rule from the injected
        // PageDepthValidator. The guards (#90 cross-language, HOMEPAGE_PROTECTED,
        // cycle, depth) and the index repath ride along in the moved body, so the
        // delegator threads in no closures.
        $this->structureService()->movePage($pageId, $targetParentId);
    }


    /**
     * Sanitize page ID
     */


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
     * Find a file by its ID within a folder
     */
    private function findFileByIdInFolder(\OCP\Files\Folder $folder, int $fileId): ?\OCP\Files\File {
        return $this->locator()->findFileByIdInFolder($folder, $fileId);
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
        // Body lives in Metadata/PageMetadataService (METADATA domain), which now
        // self-sources page lookup via its injected PageLocator (fase-7) and folder
        // concerns via its FolderContext.
        return $this->metadata()->getPageMetadata($pageId);
    }

    /**
     * Update page metadata (title only for now, similar to Files rename)
     */
    public function updatePageMetadata(string $pageId, array $metadata): array {
        // Body lives in Metadata/PageMetadataService (METADATA domain); page lookup
        // is the service's injected PageLocator (fase-7), cache invalidation its
        // injected PageCacheInvalidator.
        return $this->metadata()->updatePageMetadata($pageId, $metadata);
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
        // The write-target folder is resolved here through FolderContext; the
        // homepage check + cache invalidation come from the injected
        // HomepageResolverService + PageCacheInvalidator. PageReorderer takes the
        // resolved Folder.
        $this->reorderer()->reorder(
            $parentUniqueId,
            $orderedChildIds,
            $this->folders()->languageFolder()
        );
    }

    /**
     * Format bytes to human readable format
     */

    /**
     * Search pages by query string
     * Searches in page titles and text widget content
     * OPTIMIZED: Loads all content in a single filesystem traversal
     */
    public function searchPages(string $query): array {
        // Discovery (the filesystem walk) stays here; the scoring/sort/limit is
        // the PageSearchEngine's job (Phase "search"). Passing metaVox() in via
        // the engine keeps the request-scoped MetaVox memo a single instance.
        return $this->searchEngine()->search($this->pageLister->listAllWithContent(), $query);
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


    // =========================================================================
    // TEMPLATE METHODS
    // =========================================================================

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
            $templateDescription
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
            fn(array $data, ?string $parentPath = null): array => $this->createPage($data, $parentPath)
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
            fn(array $data, ?string $parentPath = null): array => $this->createPage($data, $parentPath)
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
