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
    /** @var array<string, string>|null Request-lifetime cache of MetaVox field labels */
    private ?array $metaVoxFieldLabelsCache = null;
    /** @var array<string, bool> Request-lifetime cache of per-field view permissions */
    private array $metaVoxFieldViewCache = [];
    /** @var array<int, int> file_id => groupfolder_id, filled by getMetaVoxDataForFiles */
    private array $metaVoxGroupfolderByFile = [];
    private LoggerInterface $logger;
    private IEventDispatcher $eventDispatcher;
    private PublicationSettingsService $publicationSettings;
    private PageIndexService $pageIndexService;
    private PageCacheService $cache;


    /**
     * Get the effective upload limit in bytes (minimum of upload_max_filesize and post_max_size)
     */
    public function getUploadLimit(): int {
        $uploadMax = $this->parsePhpSize(ini_get('upload_max_filesize') ?: '2M');
        $postMax = $this->parsePhpSize(ini_get('post_max_size') ?: '8M');

        // Use the smaller of the two, but cap at our app's MAX_MEDIA_SIZE
        $phpLimit = min($uploadMax, $postMax);
        return min($phpLimit, self::MAX_MEDIA_SIZE);
    }

    /**
     * Parse PHP size notation (e.g., '2M', '8M', '512K') to bytes
     */
    /**
     * @deprecated Delegated to PageIdUtils::parsePhpSize.
     */
    private function parsePhpSize(string $size): int {
        return $this->idUtils->parsePhpSize($size);
    }

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
     * Preserve originalSrc for video widgets during page updates.
     * This ensures that video URLs are not lost when the domain whitelist changes.
     * When a video is blocked, its originalSrc is preserved so it can be re-enabled
     * if the admin adds the domain back to the whitelist.
     */
    private function preserveVideoOriginalUrls(array $newData, array $existingData): array {
        // Build a map of existing video widgets by their ID
        $existingVideos = [];
        $this->collectVideoWidgets($existingData, $existingVideos);

        // Update new data with preserved originalSrc values
        $this->updateVideoWidgetsWithOriginalUrls($newData, $existingVideos);

        return $newData;
    }

    /**
     * Collect all video widgets from page data into a map keyed by widget ID
     */
    private function collectVideoWidgets(array $data, array &$videos): void {
        // Process main rows
        if (isset($data['layout']['rows']) && is_array($data['layout']['rows'])) {
            foreach ($data['layout']['rows'] as $row) {
                if (isset($row['widgets']) && is_array($row['widgets'])) {
                    foreach ($row['widgets'] as $widget) {
                        if (($widget['type'] ?? '') === 'video' && isset($widget['id'])) {
                            $videos[$widget['id']] = $widget;
                        }
                    }
                }
            }
        }

        // Process side columns
        if (isset($data['layout']['sideColumns']) && is_array($data['layout']['sideColumns'])) {
            foreach (['left', 'right'] as $side) {
                if (isset($data['layout']['sideColumns'][$side]['widgets']) && is_array($data['layout']['sideColumns'][$side]['widgets'])) {
                    foreach ($data['layout']['sideColumns'][$side]['widgets'] as $widget) {
                        if (($widget['type'] ?? '') === 'video' && isset($widget['id'])) {
                            $videos[$widget['id']] = $widget;
                        }
                    }
                }
            }
        }

        // Process header row
        if (isset($data['layout']['headerRow']['widgets']) && is_array($data['layout']['headerRow']['widgets'])) {
            foreach ($data['layout']['headerRow']['widgets'] as $widget) {
                if (($widget['type'] ?? '') === 'video' && isset($widget['id'])) {
                    $videos[$widget['id']] = $widget;
                }
            }
        }
    }

    /**
     * Update video widgets in new data with originalSrc from existing widgets
     */
    private function updateVideoWidgetsWithOriginalUrls(array &$data, array $existingVideos): void {
        // Process main rows
        if (isset($data['layout']['rows']) && is_array($data['layout']['rows'])) {
            foreach ($data['layout']['rows'] as $rowIndex => &$row) {
                if (isset($row['widgets']) && is_array($row['widgets'])) {
                    foreach ($row['widgets'] as $widgetIndex => &$widget) {
                        $this->preserveWidgetOriginalUrl($widget, $existingVideos);
                    }
                }
            }
        }

        // Process side columns
        if (isset($data['layout']['sideColumns']) && is_array($data['layout']['sideColumns'])) {
            foreach (['left', 'right'] as $side) {
                if (isset($data['layout']['sideColumns'][$side]['widgets']) && is_array($data['layout']['sideColumns'][$side]['widgets'])) {
                    foreach ($data['layout']['sideColumns'][$side]['widgets'] as $widgetIndex => &$widget) {
                        $this->preserveWidgetOriginalUrl($widget, $existingVideos);
                    }
                }
            }
        }

        // Process header row
        if (isset($data['layout']['headerRow']['widgets']) && is_array($data['layout']['headerRow']['widgets'])) {
            foreach ($data['layout']['headerRow']['widgets'] as $widgetIndex => &$widget) {
                $this->preserveWidgetOriginalUrl($widget, $existingVideos);
            }
        }
    }

    /**
     * Preserve originalSrc for a single video widget
     */
    private function preserveWidgetOriginalUrl(array &$widget, array $existingVideos): void {
        if (($widget['type'] ?? '') !== 'video') {
            return;
        }

        // Skip local videos - they don't have originalSrc
        if (($widget['provider'] ?? '') === 'local') {
            return;
        }

        $widgetId = $widget['id'] ?? null;
        if ($widgetId && isset($existingVideos[$widgetId])) {
            $existing = $existingVideos[$widgetId];

            // If the new widget has no src or originalSrc, but the existing one does,
            // preserve the originalSrc so the URL isn't lost
            $newSrc = $widget['src'] ?? '';
            $newOriginalSrc = $widget['originalSrc'] ?? '';
            $existingOriginalSrc = $existing['originalSrc'] ?? '';
            $existingSrc = $existing['src'] ?? '';

            // Preserve originalSrc: use existing originalSrc if new one is empty
            if (empty($newOriginalSrc)) {
                if (!empty($existingOriginalSrc)) {
                    $widget['originalSrc'] = $existingOriginalSrc;
                } elseif (!empty($existingSrc)) {
                    // Fallback: use existing src as originalSrc
                    $widget['originalSrc'] = $existingSrc;
                }
            }

            // If new src is empty but we have originalSrc, keep it for re-validation
            if (empty($newSrc) && !empty($widget['originalSrc'] ?? '')) {
                // The sanitizeWidget function will re-validate against current whitelist
                // and either allow it (setting src) or block it (keeping blocked=true)
            }
        }
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

        // Extract base language code (e.g., 'nl_NL' -> 'nl').
        $langCode = explode('_', $lang)[0];

        // Guard against malformed values; fall back to the default language.
        return preg_match('/^[a-z]{2,3}$/', $langCode) ? $langCode : self::DEFAULT_LANGUAGE;
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
     * Get the language folder within IntraVox
     *
     * `protected` (not private) purely to give unit tests a seam to inject a
     * fake language folder; no runtime behaviour depends on the visibility.
     */
    protected function getLanguageFolder() {
        $baseFolder = $this->getIntraVoxFolder();
        $lang = $this->getUserLanguage();

        try {
            return $baseFolder->get($lang);
        } catch (NotFoundException $e) {
            // If language folder doesn't exist, try default language
            if ($lang !== self::DEFAULT_LANGUAGE) {
                try {
                    return $baseFolder->get(self::DEFAULT_LANGUAGE);
                } catch (NotFoundException $e2) {
                    // Create default language folder if it doesn't exist
                    return $baseFolder->newFolder(self::DEFAULT_LANGUAGE);
                }
            }
            // Create the requested language folder
            return $baseFolder->newFolder($lang);
        }
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
        $userLang = $this->getUserLanguage();
        $candidates = [$userLang];

        $primary = $this->languageService->getPrimaryLanguage();
        if ($primary !== $userLang) {
            $candidates[] = $primary;
        }
        if (!in_array(self::DEFAULT_LANGUAGE, $candidates, true)) {
            $candidates[] = self::DEFAULT_LANGUAGE;
        }

        $baseFolder = $this->getIntraVoxFolder();
        foreach ($candidates as $code) {
            try {
                $folder = $baseFolder->get($code);
            } catch (NotFoundException $e) {
                continue;
            }
            if ($folder instanceof \OCP\Files\Folder
                && $this->languageFolderHasRealContent($folder)) {
                return $code;
            }
        }
        return null;
    }

    /**
     * Content folder for READING/VIEWING for the current user, honouring the
     * recommended-language fallback (issue #75). When nothing resolves it falls
     * back to the plain write-target folder (getLanguageFolder), so callers get
     * a valid — possibly empty — folder rather than an exception; the fallback
     * notice decides separately whether to blank the page.
     *
     * `protected` (like getLanguageFolder/getIntraVoxFolder) only to give unit
     * tests a seam for language resolution; no runtime behaviour depends on it.
     */
    protected function getReadLanguageFolder(): \OCP\Files\Folder {
        $lang = $this->resolveEffectiveLanguage();
        if ($lang !== null) {
            try {
                $folder = $this->getIntraVoxFolder()->get($lang);
                if ($folder instanceof \OCP\Files\Folder) {
                    return $folder;
                }
            } catch (NotFoundException $e) {
                // fall through to the write-target folder
            }
        }
        return $this->getLanguageFolder();
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
        return $this->locator()->languageOfFolder($this->getIntraVoxFolder(), $folder);
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
        return $this->locator()->locatePageAnyLanguage(fn() => $this->getIntraVoxFolder(), $primaryFolder, $uniqueId);
    }

    private function locateViaIndex(string $uniqueId, \OCP\Files\Folder $primaryFolder): ?array {
        return $this->locator()->locateViaIndex(fn() => $this->getIntraVoxFolder(), $uniqueId, $primaryFolder);
    }

    private function folderFromAbsolutePath(string $absolutePath): ?\OCP\Files\Folder {
        return $this->locator()->folderFromAbsolutePath($this->getIntraVoxFolder(), $absolutePath);
    }

    private function indexPathToRelative(string $storedPath): ?string {
        return $this->locator()->indexPathToRelative($this->getIntraVoxFolder(), $storedPath);
    }

    private function locatePageBySlugAnyLanguage(\OCP\Files\Folder $primaryFolder, string $id): ?array {
        return $this->locator()->locatePageBySlugAnyLanguage(fn() => $this->getIntraVoxFolder(), $primaryFolder, $id);
    }

    private function locateAcrossLanguages(\OCP\Files\Folder $primaryFolder, callable $find): ?array {
        return $this->locator()->locateAcrossLanguages(fn() => $this->getIntraVoxFolder(), $primaryFolder, $find);
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
        $primary = $this->getReadLanguageFolder();

        $find = function (\OCP\Files\Folder $folder) use ($pageId): ?array {
            if (strpos($pageId, 'page-') === 0) {
                $byUniqueId = $this->findPageByUniqueId($folder, $pageId);
                if ($byUniqueId !== null) {
                    return $byUniqueId;
                }
            }
            // Legacy slug ids (and uniqueIds that predate the page- prefix)
            // stay resolvable, matching the fallback the callers already had.
            return $this->findPageById($folder, $this->sanitizeId($pageId));
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
            $candidate = $this->getIntraVoxFolder()->get($language);
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
        $folder = $this->getReadLanguageFolder();

        if (strpos($pageId, 'page-') === 0) {
            $byUniqueId = $this->locatePageAnyLanguage($folder, $pageId);
            if ($byUniqueId !== null) {
                return $byUniqueId;
            }
        }

        return $this->locatePageBySlugAnyLanguage($folder, $this->sanitizeId($pageId));
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
        $baseFolder = $this->getIntraVoxFolder();

        try {
            return $baseFolder->get($lang);
        } catch (NotFoundException $e) {
            // If language folder doesn't exist, try default language
            if ($lang !== self::DEFAULT_LANGUAGE) {
                try {
                    return $baseFolder->get(self::DEFAULT_LANGUAGE);
                } catch (NotFoundException $e2) {
                    // Create default language folder if it doesn't exist
                    return $baseFolder->newFolder(self::DEFAULT_LANGUAGE);
                }
            }
            // Create the requested language folder
            return $baseFolder->newFolder($lang);
        }
    }

    /**
     * Get the IntraVox folder from user's perspective (mounted GroupFolder)
     *
     * IMPORTANT: Uses the user's mounted folder view to respect GroupFolder ACL
     * This is essential for non-admin users to access the IntraVox folder
     *
     * `protected` (like getLanguageFolder) only to give unit tests a seam for
     * the mounted-folder lookup; no runtime behaviour depends on it.
     */
    protected function getIntraVoxFolder() {
        if (!$this->userId) {
            throw new \Exception('User not logged in');
        }

        // Get user's folder (this respects GroupFolder ACL)
        $userFolder = $this->rootFolder->getUserFolder($this->userId);

        // Get folder from user's perspective (mounted GroupFolder)
        try {
            return $userFolder->get('IntraVox');
        } catch (NotFoundException $e) {
            throw new \Exception("IntraVox folder not found. Please check that you have access to the IntraVox GroupFolder.");
        }
    }

    /**
     * Get permissions for a folder path (relative to IntraVox root)
     * Uses Nextcloud's native filesystem permissions which respect GroupFolder ACL
     *
     * IMPORTANT: Uses the user's mounted folder view to get ACL-aware permissions
     *
     * @param string $relativePath Path relative to IntraVox folder (e.g., "en/about" or "")
     * @return array Permissions object with canRead, canWrite, canCreate, canDelete, canShare
     */
    public function getFolderPermissions(string $relativePath): array {
        try {
            if (!$this->userId) {
                return [
                    'canRead' => false,
                    'canWrite' => false,
                    'canCreate' => false,
                    'canDelete' => false,
                    'canShare' => false,
                    'raw' => 0
                ];
            }

            // Get user's folder (this respects GroupFolder ACL)
            $userFolder = $this->rootFolder->getUserFolder($this->userId);

            // Get IntraVox folder from user's perspective (mounted GroupFolder)
            $intraVoxPath = 'IntraVox';
            if (!empty($relativePath)) {
                $intraVoxPath .= '/' . ltrim($relativePath, '/');
            }

            $folder = $userFolder->get($intraVoxPath);
            return $this->permissionService->permissionsFromNode($folder);
        } catch (\Exception $e) {
            // If folder doesn't exist, return no permissions
            $this->logger->debug('getFolderPermissions failed for path: ' . $relativePath . ' - ' . $e->getMessage());
            return [
                'canRead' => false,
                'canWrite' => false,
                'canCreate' => false,
                'canDelete' => false,
                'canShare' => false,
                'raw' => 0
            ];
        }
    }

    /**
     * Is this slug already taken by a sibling in $parent?
     *
     * A slug is a FOLDER NAME, so it only has to be unique among the entries of
     * the one folder the page is written into. The check this replaced asked a
     * broader and differently-anchored question — does this slug exist anywhere
     * in the ACTING USER'S language tree — which was wrong twice over: a
     * translation into en/ was refused a name that was taken in nl/, and
     * about/team reserved "team" for sales/team as well.
     *
     * $parent is null when the destination folder does not exist yet; nothing
     * can collide inside a folder that is about to be created empty.
     *
     * Probes `$id.json` as well as `$id`, like renamePageFolder(): in the
     * legacy "beside" layout a page's JSON sits NEXT TO its folder rather than
     * inside it, so it occupies two names in the parent, and findPageById()
     * would resolve the new page over the existing one.
     *
     * The three suffix loops in this file (here, movePage, renamePageFolder)
     * differ on purpose and should not be unified: a move relocates a populated
     * folder, a rename must additionally dodge its own children, and a create
     * makes an empty folder with no children to dodge.
     */
    private function slugTakenIn(?\OCP\Files\Folder $parent, string $id): bool {
        if ($parent === null) {
            return false;
        }
        return $parent->nodeExists($id) || $parent->nodeExists($id . '.json');
    }

    /**
     * Public method to check if a page exists by uniqueId
     * Used by CommentsEntityListener to validate comment objectIds
     */
    public function pageExistsByUniqueId(string $uniqueId): bool {
        try {
            $folder = $this->getReadLanguageFolder();
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
        $folder = $this->getReadLanguageFolder();

        // Titles and statuses come from the index when it has this language,
        // which removes the read + json_decode of every page file. Permissions
        // still come from the filesystem: they depend on GroupFolder ACLs and
        // on who is asking, so they are not derivable from an index row and
        // must never be cached across users.
        $indexed = $this->listPagesFromIndex($folder);
        if ($indexed !== null) {
            return $this->inStableOrder($indexed);
        }

        $intraVoxFolder = $this->getIntraVoxFolder();
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

        $folder = $this->getReadLanguageFolder();
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

        $source = $this->locatePageAnyLanguage($this->getReadLanguageFolder(), $sourceUniqueId);
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
            $targetFolder = $this->getIntraVoxFolder()->get($language);
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
        $baseTitle = $this->decodeHtmlEntitiesRecursive((string)($sourceData['title'] ?? 'Untitled'));
        $pageData['title'] = ($title !== null && $title !== '') ? $title : $baseTitle;
        $pageData['id'] = $this->sanitizeId($pageData['title']);
        $pageData['uniqueId'] = 'page-' . $this->generateUUID();
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
        $result = $this->locatePageAnyLanguage($this->getReadLanguageFolder(), $pageId);
        if ($result === null) {
            throw new PageNotFoundException('Page not found: ' . $pageId);
        }

        $ownLanguage = $this->languageOfFolder($result['folder']);
        $data = json_decode($result['file']->getContent(), true);
        $group = is_array($data) ? ($data['translationGroup'] ?? null) : null;

        $root = $this->getIntraVoxFolder();
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
        $folder = $this->getReadLanguageFolder();
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
            $this->getIntraVoxFolder(),
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
        $folder = $this->getReadLanguageFolder();
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
            fn() => $this->getIntraVoxFolder()
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
        $language = $this->languageOfFolder($folder);
        if ($language === null) {
            return null;
        }

        try {
            if (!$this->pageIndexService->hasEntries($language)) {
                return null;
            }
            $rows = $this->pageIndexService->getPagesByLanguage($language);
        } catch (\Throwable $e) {
            $this->logger->warning('[PageService] index listing failed, falling back to scan', [
                'language' => $language,
                'error' => $e->getMessage(),
            ]);
            return null;
        }

        if (empty($rows)) {
            return null;
        }

        // The homepage must be in the list. It lives as home.json at the
        // language ROOT rather than in a page folder, and on real installs it
        // turns out not to reach the index at all — so serving the index list
        // as-is would silently drop the homepage from the sidebar. Rather than
        // depend on that ever being fixed upstream, verify it here and fall
        // back to the walk when it is missing: a slow, complete list beats a
        // fast one with a hole in it.
        $homeUniqueId = null;
        try {
            $homeFile = $folder->get('home.json');
            if ($homeFile instanceof \OCP\Files\File) {
                $homeData = json_decode($this->getCachedFileContent($homeFile), true);
                $homeUniqueId = is_array($homeData) ? ($homeData['uniqueId'] ?? null) : null;
            }
        } catch (NotFoundException $e) {
            // No loose homepage in this language; nothing to guarantee.
        }
        if ($homeUniqueId !== null) {
            $indexedIds = array_column($rows, 'unique_id');
            if (!in_array($homeUniqueId, $indexedIds, true)) {
                return null;
            }
        }

        $pages = [];
        foreach ($rows as $row) {
            if (empty($row['unique_id']) || empty($row['path'])) {
                continue;
            }

            // Resolve the page folder to read permissions from. A row pointing
            // at something the user cannot reach is skipped rather than served
            // without permissions — the same mount-scoped resolution the
            // uniqueId lookup uses, so the index can never widen access.
            $pageFolder = $this->folderFromAbsolutePath((string)$row['path']);
            if ($pageFolder === null) {
                continue;
            }

            $pages[] = [
                'uniqueId' => (string)$row['unique_id'],
                'title' => (string)($row['title'] ?? ''),
                'modified' => (int)($row['modified_at'] ?? 0),
                'status' => (string)($row['status'] ?? 'published'),
                'permissions' => $this->permissionService->permissionsFromNode($pageFolder),
            ];
        }

        return $pages;
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
        $userLang = $this->getUserLanguage();
        $withContent = [];
        $active = [];

        try {
            $baseFolder = $this->getIntraVoxFolder();
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
            $baseFolder = $this->getIntraVoxFolder();
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
        $folder = $this->getReadLanguageFolder();
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
        // Check request-level cache first
        $cachedPage = $this->cache()->getPageData($id);
        if ($cachedPage !== null) {
            return $cachedPage;
        }

        $folder = $this->getReadLanguageFolder();
        $result = null;

        // Save original ID before sanitization
        $originalId = $id;

        // Check for uniqueId pattern BEFORE sanitization. The cross-language
        // scan inside locatePageAnyLanguage() lets feed links and shared links
        // resolve regardless of which language folder holds the page.
        if (strpos($originalId, 'page-') === 0) {
            $result = $this->locatePageAnyLanguage($folder, $originalId);
            if (!$result) {
                $this->logger->warning('IntraVox: Not found by uniqueId', ['uniqueId' => $originalId]);
            }
        }

        // Only sanitize for legacy ID fallback
        if ($result === null) {
            $id = $this->sanitizeId($originalId);
            $result = $this->findPageById($folder, $id);
            // Slug links get the same cross-language treatment as uniqueId
            // links, so which kind of link a reader follows never decides
            // whether the page resolves.
            if ($result === null) {
                $result = $this->locatePageBySlugAnyLanguage($folder, $id);
            }
        }

        if ($result === null) {
            throw new \Exception('Page not found');
        }

        $content = $result['file']->getContent();
        $data = json_decode($content, true);

        if (!$data) {
            throw new \Exception('Invalid page data');
        }

        // Ensure uniqueId exists for legacy pages
        if (!isset($data['uniqueId'])) {
            $data['uniqueId'] = 'page-' . $this->generateUUID();
            // Save the page with the new uniqueId
            try {
                $result['file']->putContent(json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            } catch (\Exception $e) {
                // Failed to save uniqueId - page will work but won't have permanent link
            }
        }

        // Cache folder location using both uniqueId and pageId for fast image access
        $pageFolder = $result['folder'];
        $uniqueId = $data['uniqueId'];
        $this->cache()->setPageFolder($uniqueId, $pageFolder);
        $this->cache()->setPageFolder($originalId, $pageFolder);
        if (isset($id)) {
            $this->cache()->setPageFolder($id, $pageFolder);
        }

        // Distributed content cache. Key is content-addressable via mtime, so
        // invalidation is automatic — a write bumps mtime, the next read
        // misses cache and rebuilds. The sanitize+enrich pipeline is the
        // expensive part (~500 lines of widget processing); cache stores
        // the post-sanitize result keyed by `{uniqueId}_{mtime}`.
        $mtime = $result['file']->getMTime();
        $contentCacheKey = 'content_' . $uniqueId . '_' . $mtime;
        if ($this->cache()->isDistributedAvailable()) {
            $cached = $this->cache()->getDistributed($contentCacheKey);
            if (is_string($cached)) {
                $decoded = json_decode($cached, true);
                if (is_array($decoded)) {
                    // Permissions are per-user and are NOT stored in the shared
                    // distributed cache (see the set() below). Recompute them
                    // fresh on every hit so one user's canWrite can never leak to
                    // another (issue #70). $result['file']/['folder'] are already
                    // resolved above. This also overwrites any stale permissions
                    // baked in by pre-fix cache entries, so no flush is needed.
                    $decoded['permissions'] = $this->permissionService->permissionsForPage($result['folder'], $result['file']);
                    $decoded['canEdit'] = $result['file']->isUpdateable();
                    // fileId is user-independent but may be absent from older cache
                    // entries; ensure it's present so the publication gate works.
                    if (!isset($decoded['fileId']) && $result['file'] instanceof \OCP\Files\File) {
                        $decoded['fileId'] = $result['file']->getId();
                    }
                    // MetaVox availability is an install-wide fact and the
                    // groupfolder id is a property of the file's mount, so
                    // neither is cached — availability can change under a cache
                    // entry when the app is enabled or disabled, and entries
                    // written before these fields existed would otherwise never
                    // gain them. Both are cheap: an in-memory app-manager lookup
                    // and a regex over a path.
                    $decoded['metaVoxAvailable'] = $this->isMetaVoxAvailable();
                    if ($decoded['metaVoxAvailable'] && $result['file'] instanceof \OCP\Files\File) {
                        $decoded['groupfolderId'] = $this->groupfolderIdForNode($result['file']);
                    }
                    // Translations are ACL-filtered per user (resolveTranslations
                    // skips group members the caller's mount does not grant), so
                    // one user's list must never be served to another. Stripped
                    // from the shared cache on write — recomputed here on every
                    // hit: one indexed query plus a filecache lookup per group
                    // member.
                    $decoded['translations'] = $this->resolveTranslations(
                        $decoded['translationGroup'] ?? null,
                        $decoded['uniqueId'] ?? null
                    );
                    $this->cache()->setPageData($originalId, $decoded);
                    $this->cache()->setPageData($uniqueId, $decoded);
                    return $decoded;
                }
            }
        }

        // Enrich with real-time path data. Pass the page file so canWrite/canEdit
        // are gated on the file the write path actually targets (issue #70).
        $data = $this->enrichWithPathData($data, $result['folder'], $result['file']);

        $sanitizedData = $this->sanitizePage($data);

        // Cache the result for this request
        $this->cache()->setPageData($originalId, $sanitizedData);
        if (isset($data['uniqueId'])) {
            $this->cache()->setPageData($data['uniqueId'], $sanitizedData);
        }

        // Cache for cross-request reuse (1 hour TTL; older entries are
        // naturally orphaned when mtime changes, distributed-cache GC will
        // clean them up). The distributed cache is shared across users, so the
        // per-user permissions/canEdit are stripped before storing and are
        // recomputed on every read (issue #70). The user-independent enriched
        // fields (path/depth/parent/language/department) stay cached.
        if ($this->cache()->isDistributedAvailable()) {
            $cacheable = $sanitizedData;
            // metaVoxAvailable is stripped for the same reason as permissions:
            // enabling or disabling the app must take effect immediately rather
            // than waiting out an hour-long cache entry. It is recomputed on
            // every read above.
            // translations joins the per-user list: it is ACL-filtered through
            // the caller's mount, so caching it would leak one user's view to
            // another. Recomputed on every cache hit above.
            unset($cacheable['permissions'], $cacheable['canEdit'], $cacheable['metaVoxAvailable'], $cacheable['translations']);
            $this->cache()->setDistributed($contentCacheKey, json_encode($cacheable), PageCacheService::PAGE_CONTENT_TTL);
        }

        return $sanitizedData;
    }

    /**
     * Enrich page data with real-time path information calculated from filesystem
     */
    private function enrichWithPathData(array $page, $folder, ?\OCP\Files\Node $file = null): array {
        // Get relative path from IntraVox root
        $page['path'] = $this->getRelativePathFromRoot($folder);

        // Calculate depth
        $page['depth'] = $this->calculateDepth($page['path']);

        // Calculate parent path
        $pathParts = explode('/', $page['path']);
        if (count($pathParts) > 1) {
            array_pop($pathParts); // Remove current page
            $page['parentPath'] = implode('/', $pathParts);
            $page['parentId'] = basename($page['parentPath']);
        } else {
            $page['parentPath'] = null;
            $page['parentId'] = null;
        }

        // Parse language and department from path
        $parsedPath = explode('/', $page['path']);
        $page['language'] = $parsedPath[0] ?? $this->getUserLanguage();
        $page['department'] = $this->parseDepartmentFromPath($page['path']);

        // Get permissions directly from Nextcloud's filesystem, combining the
        // bitmask with the node capability methods so a read-only GroupFolder
        // member (without ACLs) is reported correctly — see permissionsFromNode().
        // When the page's file node is available, gate canWrite/canEdit on the
        // FILE (the real edit target) rather than the folder, so the "Edit page"
        // affordance matches what the write path actually allows (issue #70).
        if ($file !== null) {
            $page['permissions'] = $this->permissionService->permissionsForPage($folder, $file);
            $page['canEdit'] = $file->isUpdateable();
            // Expose the page file's id so the publication gate can resolve the
            // scheduled-publish MetaVox fields (publish/expiration) for this page.
            if ($file instanceof \OCP\Files\File) {
                $page['fileId'] = $file->getId();
            }
            // Concurrency token: the editor sends this back on save, and
            // updatePage() refuses a write whose baseVersion predates the file
            // on disk. Deliberately the file's mtime rather than the `modified`
            // field in the JSON, which is client-supplied and would compare a
            // value against itself.
            $page['baseVersion'] = $file->getMTime();

            // Which languages this page exists in. Powers the reader's "also
            // available in X" notice and the language switcher, and tells an
            // editor at a glance what still needs translating.
            //
            // Excludes the page's own language: the list answers "where ELSE
            // can I read this", so including the page you are on would only add
            // a no-op entry to every switcher.
            $page['translations'] = $this->resolveTranslations(
                $page['translationGroup'] ?? null,
                $page['uniqueId'] ?? null
            );

            // Whether the MetaVox tab and its menu entry should exist at all.
            // Rides along on a response the client already fetches: this is an
            // in-memory app-manager lookup, no query and no HTTP, so it is
            // cheaper than the separate /api/metavox/status call the sidebar
            // used to make every time it opened.
            $page['metaVoxAvailable'] = $this->isMetaVoxAvailable();

            // The groupfolder holding this page. MetaVox's field definitions are
            // assigned per groupfolder, and its groupfolder-scoped endpoint
            // returns exactly the fields for that folder — where the
            // auto-detecting variant returned every field of every folder.
            //
            // Derived from the file's mount path rather than from MetaVox's
            // value table: that table only holds rows for files that already
            // have values SAVED, so looking there would return nothing for a
            // page whose fields are still empty — precisely the freshly copied
            // and translated pages that need the form most.
            if ($page['metaVoxAvailable'] && $file instanceof \OCP\Files\File) {
                $page['groupfolderId'] = $this->groupfolderIdForNode($file);
            }
        } else {
            $page['permissions'] = $this->permissionService->permissionsFromNode($folder);
            $page['canEdit'] = $folder->isUpdateable();
        }

        return $page;
    }

    /**
     * Get relative path from IntraVox root folder
     */
    private function getRelativePathFromRoot($folder): string {
        return $this->locator()->relativePathFromRoot($this->getIntraVoxFolder(), $folder);
    }

    /**
     * Calculate nesting depth from path
     *
     * Base paths (depth 0):
     * - nl/public/ (public pages)
     * - nl/departments/{dept}/ (department pages)
     */
    /**
     * @deprecated Delegated to PagePathHelper::calculateDepth.
     */
    private function calculateDepth(string $path): int {
        return $this->pathHelper->calculateDepth($path);
    }

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
        $currentDepth = $this->calculateDepth($parentPath);
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
     * @deprecated Delegated to PagePathHelper::determinePageType.
     */
    private function determinePageType(string $path, bool $hasChildren): string {
        return $this->pathHelper->determinePageType($path, $hasChildren);
    }

    /**
     * @deprecated Delegated to PagePathHelper::parseDepartmentFromPath.
     */
    private function parseDepartmentFromPath(string $path): ?string {
        return $this->pathHelper->parseDepartmentFromPath($path);
    }

    /**
     * Get breadcrumb trail for a page
     *
     * Returns array of breadcrumb items from home to current page
     */
    public function getBreadcrumb(string $pageId): array {
        $page = $this->getPage($pageId);
        $breadcrumb = [];
        $language = $this->getUserLanguage();

        // Check if current page is the home page (legacy id/path detection, plus
        // a configured homepage pointer via uniqueId).
        $isHomePage = ($pageId === 'home' ||
                       preg_match('/^[a-z]{2,3}\/home$/', $page['path']) ||
                       preg_match('/^[a-z]{2,3}$/', $page['path']) ||
                       (!empty($page['uniqueId']) && $this->isHomepage((string)$page['uniqueId'], $language)));

        // Read home breadcrumb label from navigation.json (first item title)
        // This allows users to customize the label via the navigation editor
        $homeTitle = 'Home';
        $homeUniqueId = $isHomePage ? $page['uniqueId'] : null;
        try {
            $folder = $this->getReadLanguageFolder();
            if ($folder->nodeExists('navigation.json')) {
                $navFile = $folder->get('navigation.json');
                $navData = json_decode($navFile->getContent(), true, 64);
                if ($navData && !empty($navData['items'][0]['title'])) {
                    $homeTitle = $navData['items'][0]['title'];
                }
                if (!$isHomePage && $navData && !empty($navData['items'][0]['uniqueId'])) {
                    $homeUniqueId = $navData['items'][0]['uniqueId'];
                }
            }
        } catch (\Exception $e) {
            // fallback to 'Home'
        }

        // Always start with Home
        $breadcrumb[] = [
            'id' => 'home',
            'uniqueId' => $homeUniqueId,
            'title' => $homeTitle,
            'path' => $language . '/home',
            'url' => $isHomePage ? null : '#home',
            'current' => $isHomePage
        ];

        // If this is the home page, we're done - don't add duplicate
        if ($isHomePage) {
            return $breadcrumb;
        }

        // Build breadcrumb from the full path
        // Example path: en/departments/marketing/campaigns
        $pathParts = explode('/', $page['path']);
        $accumulatedPath = '';

        foreach ($pathParts as $index => $part) {
            // Build accumulated path for looking up parent pages
            if (!empty($accumulatedPath)) {
                $accumulatedPath .= '/';
            }
            $accumulatedPath .= $part;

            // Skip language folder in breadcrumb display (but include in accumulated path)
            if ($index === 0 && $this->languageService->isLanguageAvailable($part)) {
                continue;
            }

            // Skip 'home' as it's already added
            if ($part === 'home') {
                continue;
            }

            // Check if this is the last item (current page)
            if ($index === count($pathParts) - 1) {
                // Add current page (not clickable)
                $breadcrumb[] = [
                    'uniqueId' => $page['uniqueId'],
                    'title' => $page['title'],
                    'path' => $page['path'],
                    'url' => null,
                    'current' => true
                ];
                break;
            }

            // Try to find parent page by its folder path
            try {
                $parentPage = $this->findPageByFolderPath($accumulatedPath);
                if ($parentPage) {
                    $breadcrumb[] = [
                        'id' => $part,
                        'uniqueId' => $parentPage['uniqueId'],
                        'title' => $parentPage['title'],
                        'path' => $parentPage['path'],
                        'url' => '#' . $parentPage['uniqueId'],
                        'current' => false
                    ];
                } else {
                    // No page found for this folder - use folder name as label but don't make clickable
                    $breadcrumb[] = [
                        'id' => $part,
                        'uniqueId' => null,
                        'title' => ucfirst(str_replace('-', ' ', $part)),
                        'path' => $accumulatedPath,
                        'url' => null,
                        'current' => false
                    ];
                }
            } catch (\Exception $e) {
                // Parent page not found or error loading it
                // Use folder name as fallback
                $breadcrumb[] = [
                    'id' => $part,
                    'uniqueId' => null,
                    'title' => ucfirst(str_replace('-', ' ', $part)),
                    'path' => $accumulatedPath,
                    'url' => null,
                    'current' => false
                ];
            }
        }

        return $breadcrumb;
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
            $intraVoxFolder = $this->getIntraVoxFolder();
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
                $candidate = $this->getIntraVoxFolder()->get($langCode);
                if ($candidate instanceof \OCP\Files\Folder) {
                    $currentFolder = $candidate;
                }
            } catch (NotFoundException $e) {
                // No folder for that language — fall through to the author's own.
            }
        }
        if ($currentFolder === null) {
            $currentFolder = $this->getLanguageFolder();
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
     * Which folder would createPageAtPath() write $parentPath into — resolved
     * WITHOUT creating anything.
     *
     * createPage() has to know the destination before createPageAtPath() runs,
     * so it can check the new slug against the right siblings. It cannot call
     * getOrCreateFolderPath() for that: the create-on-miss there is load-bearing
     * on the write path (createTranslation relies on it to materialise mirrored
     * parent folders), but calling it early would leave stray folders behind
     * whenever the permission preflight in createPageAtPath() then refuses.
     *
     * Returns null when the destination does not exist yet, which callers read
     * as "nothing can collide there".
     *
     * Mirrors getOrCreateFolderPath() — keep the two in step.
     */
    private function resolveExistingFolderPath(?string $parentPath): ?\OCP\Files\Folder {
        try {
            if ($parentPath === null || trim($parentPath, '/') === '') {
                // No parent = the language root createPageAtPath() falls back to.
                return $this->getReadLanguageFolder();
            }

            $pathParts = explode('/', trim($parentPath, '/'));

            $currentFolder = null;
            if (count($pathParts) > 0 && $this->languageService->isLanguageAvailable($pathParts[0])) {
                $langCode = array_shift($pathParts);
                try {
                    $candidate = $this->getIntraVoxFolder()->get($langCode);
                    if ($candidate instanceof \OCP\Files\Folder) {
                        $currentFolder = $candidate;
                    }
                } catch (NotFoundException $e) {
                    // No folder for that language — fall through to the author's own.
                }
            }
            if ($currentFolder === null) {
                $currentFolder = $this->getLanguageFolder();
            }

            foreach ($pathParts as $folderName) {
                try {
                    $next = $currentFolder->get($folderName);
                } catch (NotFoundException $e) {
                    return null;
                }
                if (!($next instanceof \OCP\Files\Folder)
                    || $next->getType() !== \OCP\Files\FileInfo::TYPE_FOLDER) {
                    // createPageAtPath() will raise its own error for this.
                    return null;
                }
                $currentFolder = $next;
            }

            return $currentFolder;
        } catch (\Throwable $e) {
            // A destination we cannot resolve simply gets no de-duplication;
            // failing the create over it would be worse than a suffix-free name.
            return null;
        }
    }

    /**
     * Create a page at a specific path with parent support
     *
     * @param string $pageId The page ID (used as folder name)
     * @param array $data Page data (without id - id is the folder name)
     * @param string|null $parentPath Optional parent path (e.g., "nl/departments/marketing")
     * @return array Created page data
     */
    private function createPageAtPath(string $pageId, array $data, ?string $parentPath = null): array {
        $language = $this->getUserLanguage();

        // Determine target folder
        if ($parentPath) {
            // Validate depth before creating
            $this->validateDepth($parentPath);

            // Get or create parent folder path
            $targetFolder = $this->getOrCreateFolderPath($parentPath);
        } else {
            // No parent = create at the root of the language being VIEWED, so a
            // new page lands in the structure the author is actually working in
            // rather than in their profile language. getReadLanguageFolder()
            // resolves own language → recommended → en, and falls back to the
            // author's own folder when nothing else resolves.
            $targetFolder = $this->getReadLanguageFolder();
        }

        // Preflight: creating a page writes a file (and a folder) into $targetFolder.
        // A read-only GroupFolder member must get a clean 403 here instead of a
        // filesystem-level 400 (issue #70).
        if (!$targetFolder->isCreatable()) {
            throw new ForbiddenException('You do not have permission to create a page here');
        }

        // The folder whose path the index must store: the one holding the page
        // JSON. For home that is the language root itself; for every other page
        // it is the page's OWN folder, set in the else-branch below. Indexing
        // the PARENT here made every freshly created page unresolvable via the
        // index (the lookup derives candidates from this path and the verify
        // step then rejects them), silently demoting each first lookup to the
        // full scan until the next save or reindex repaired the row.
        $indexFolder = $targetFolder;

        // Special handling for home page (always at root)
        if ($pageId === 'home') {
            $file = $targetFolder->newFile('home.json');
            $file->putContent(json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

            // Create _media folder for home if it doesn't exist
            try {
                $mediaFolder = $targetFolder->get('_media');
                $this->createMediaFolderMarker($mediaFolder);
            } catch (NotFoundException $e) {
                $mediaFolder = $targetFolder->newFolder('_media');
                $this->createMediaFolderMarker($mediaFolder);
            }

            $this->scanPageFolder($targetFolder);
        } else {
            // Create folder for page
            try {
                $pageFolder = $targetFolder->newFolder($pageId);
            } catch (\Exception $e) {
                throw new \InvalidArgumentException('Failed to create page folder: ' . $e->getMessage());
            }

            // Create {pageId}.json inside the folder
            try {
                $file = $pageFolder->newFile($pageId . '.json');
                $file->putContent(json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            } catch (\Exception $e) {
                throw new \InvalidArgumentException('Failed to create page file: ' . $e->getMessage());
            }

            // Create _media subfolder
            try {
                $mediaFolder = $pageFolder->newFolder('_media');
                // Add a .nomedia file to indicate this is a special folder
                $this->createMediaFolderMarker($mediaFolder);
            } catch (\Exception $e) {
                // Media folder might already exist, that's okay
                try {
                    $mediaFolder = $pageFolder->get('_media');
                    $this->createMediaFolderMarker($mediaFolder);
                } catch (\Exception $ex) {
                    // Couldn't get media folder
                }
            }

            $this->scanPageFolder($pageFolder);

            // Cache the folder reference for immediate reuse (e.g., when copying media from template)
            if (isset($data['uniqueId'])) {
                $this->cache()->setPageFolder($data['uniqueId'], $pageFolder);
                $indexFolder = $pageFolder;
        }
            $indexFolder = $pageFolder;
        }

        // Update page metadata index (non-blocking — page was already saved).
        // Index the language the page actually LANDED in, which is not always
        // the author's own (a sub-page follows its parent's language).
        //
        // The stored path is the ABSOLUTE path of the folder holding the page
        // JSON, matching updatePage() and rebuildIndex(). This used to store a
        // relative parent path here and an absolute one everywhere else, so the
        // same table held two incompatible path shapes — which breaks both the
        // index lookup (it resolves the stored path) and repathSubtree() (it
        // matches on a path prefix).
        try {
            $language = $this->languageOfFolder($indexFolder) ?? $this->getUserLanguage();
            $this->pageIndexService->indexPage(
                $data,
                $language,
                $indexFolder->getPath(),
                $file->getId(),
                $indexFolder->getId()
            );
        } catch (\Exception $e) {
            $this->logger->warning('Failed to index new page', ['error' => $e->getMessage()]);
        }

        // Return data with id for frontend (id is derived from folder name)
        return array_merge(['id' => $pageId], $data);
    }

    /**
     * Create a new page
     *
     * @param array $data Page data (id, title, content, etc.)
     * @param string|null $parentPath Optional parent path for nested pages (e.g., "nl/departments/marketing")
     * @return array Created page data
     */
    public function createPage(array $data, ?string $parentPath = null): array {
        if (!isset($data['id']) || !isset($data['title'])) {
            throw new \InvalidArgumentException('Missing required fields: id, title');
        }

        $data['id'] = $this->sanitizeId($data['id']);

        // If the slug is taken by a SIBLING at the destination, append a number.
        // Resolved once, outside the loop: nodeExists() is cheap, walking the
        // path is not.
        //
        // 'home' is exempt: createPageAtPath() writes it as home.json at the
        // language root with no folder of its own, so a 'home-2' would only
        // create a page folder the homepage resolver never looks at.
        if ($data['id'] !== 'home') {
            $targetFolder = $this->resolveExistingFolderPath($parentPath);
            $originalId = $data['id'];
            $counter = 2;
            while ($this->slugTakenIn($targetFolder, $data['id'])) {
                $data['id'] = $originalId . '-' . $counter;
                $counter++;
            }
        }

        // Generate uniqueId if not provided
        if (!isset($data['uniqueId'])) {
            $data['uniqueId'] = 'page-' . $this->generateUUID();
        }

        // Every page belongs to a translation group, even when it is the only
        // member. Giving each new page its own group from the start means
        // "linked" and "not linked" are the same shape — there is no special
        // case for an unlinked page, and linking later is a value change rather
        // than a structural one. A caller that supplies a group (adding a
        // translation of an existing page) keeps it.
        if (empty($data['translationGroup'])) {
            $data['translationGroup'] = 'tg-' . $this->generateUUID();
        }

        $validatedData = $this->validateAndSanitizePage($data);

        // Use the new createPageAtPath helper - pass id separately (not stored in JSON)
        $created = $this->createPageAtPath($data['id'], $validatedData, $parentPath);

        // Flush all cached page-tree + permission map entries so subsequent
        // reads (loadPages, getPageTree) immediately see the new page.
        // Historically only updatePage/deletePage did this; createPage
        // relied on the static cache's TTL to age out, which became
        // visible as "create page from template renders blank" once PR-3
        // shifted to a 5-minute distributed tree cache.
        $this->clearCache();

        return $created;
    }

    /**
     * Scan a page folder to make it immediately visible in Files app
     * This uses Nextcloud's Scanner to add the folder to the file cache
     *
     * @param \OCP\Files\Folder $folder The folder to scan (can be page folder or language folder)
     */
    private function scanPageFolder($folder): void {
        try {
            // There used to be a groupfolders branch here that shelled out to
            // `php /var/www/nextcloud/occ files:scan` per page and returned
            // unconditionally. It never ran. The regex tested getPath(), which is
            // the user-facing view (/rik/files/IntraVox/nl/page) and never
            // contains /__groupfolders/ — that only appears in getInternalPath(),
            // which is exactly what the code below matches on.
            //
            // So the fork was unreachable and the in-process scanner has been
            // doing the work all along. Verified on dev: four page creations, zero
            // 'Failed to scan page folder' warnings, on a container where the
            // hardcoded /var/www/nextcloud/occ does not even exist — had the
            // branch been live, every one of them would have logged a failure.
            //
            // Removing it takes out a hardcoded occ path, a hardcoded 'IntraVox'
            // mount name, and a synchronous process fork from the request path,
            // none of which were earning anything.
            // Fallback for non-groupfolder paths (shouldn't happen in IntraVox)
            $storage = $folder->getStorage();
            $scanner = $storage->getScanner();
            $cache = $storage->getCache();

            $internalPath = $folder->getInternalPath();
            if (preg_match('#__groupfolders/\d+/(.+)$#', $internalPath, $matches)) {
                $scanPath = $matches[1];
            } else {
                $scanPath = $internalPath;
            }

            $scanner->scan($scanPath, true);
            $cache->correctFolderSize($scanPath, ['recursive' => true]);

        } catch (\Exception $e) {
            // Log but don't throw - if scanning fails, the page is still created
            $this->logger->error('Failed to scan page folder', [
                'path' => $folder->getPath(),
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Update an existing page
     */
    public function updatePage(string $id, array $data): array {
        // Save original ID before sanitization
        $originalId = $id;

        // Get the current user
        $user = $this->userSession->getUser();
        if (!$user) {
            throw new \InvalidArgumentException('No user in session');
        }

        $languageFolder = $this->getLanguageFolder();
        $result = null;

        // Check for uniqueId pattern (page-xxx) BEFORE sanitization. Editing an
        // existing page writes back to wherever that page actually lives, which
        // is not necessarily the current user's own language folder (issue #90);
        // the isUpdateable() preflight below still gates the write.
        if (strpos($originalId, 'page-') === 0) {
            $result = $this->locatePageAnyLanguage($languageFolder, $originalId);
        }

        // Fallback to legacy ID lookup if not found by uniqueId
        if ($result === null) {
            try {
                $id = $this->sanitizeId($originalId);
                $result = $this->findPageById($languageFolder, $id);
            } catch (\Exception $e) {
                throw new \InvalidArgumentException('Failed to find page: ' . $e->getMessage());
            }
        }

        if ($result === null) {
            throw new PageNotFoundException('Page not found: ' . $originalId);
        }

        // Get the file
        $file = $result['file'];

        // Preflight the write capability on the actual file/mount. permissionsFromNode
        // already gates canWrite on this, but a read-only GroupFolder member must get a
        // clean 403 here rather than a filesystem-level 400 if anything reported wrong
        // (issue #70). This also avoids Nextcloud core's share-access-list side effect
        // ("foreach() on null") that a doomed putContent would otherwise trigger.
        if (!$file->isUpdateable()) {
            throw new ForbiddenException('You do not have permission to edit this page');
        }

        try {
            $existingContent = $file->getContent();
            $existingData = json_decode($existingContent, true);
            if (!is_array($existingData)) {
                $existingData = [];
            }
        } catch (\Exception $e) {
            throw new \InvalidArgumentException('Failed to read existing page data: ' . $e->getMessage());
        }

        // Optimistic concurrency. putContent() replaces the WHOLE document, so
        // a save built on stale content erases everything written since — not a
        // field, the entire page. PageLockService catches the common case, but
        // locks expire after 15 minutes without a heartbeat, so a tab left open
        // comes back with stale content and no lock to stop it.
        //
        // The FILE's mtime is the version token, not the `modified` field in the
        // JSON: that field is whatever the client last sent (updatePage never
        // stamps it), so it would compare a value against itself. The mtime is
        // set by the filesystem on every write and cannot be spoofed by a stale
        // client.
        //
        // A client that sends no baseVersion — an older frontend, a script, an
        // import — is not blocked. This rejects only a save that demonstrably
        // started from an older version, never one that merely failed to say.
        $submittedBase = $data['baseVersion'] ?? null;
        if (is_numeric($submittedBase)) {
            $currentMtime = $file->getMTime();
            if ((int)$submittedBase < $currentMtime) {
                $this->logger->warning('[updatePage] stale write rejected', [
                    'pageId' => $originalId,
                    'baseVersion' => (int)$submittedBase,
                    'currentMtime' => $currentMtime,
                ]);
                throw new PageConflictException(
                    'This page was changed by someone else while you were editing it. '
                    . 'Reload the page to get the latest version before saving again.'
                );
            }
        }

        // Never persist the transport-only concurrency token.
        unset($data['baseVersion']);

        // Preserve uniqueId from existing data
        if (isset($existingData['uniqueId'])) {
            $data['uniqueId'] = $existingData['uniqueId'];
        }

        // Same for the translation group: it belongs to the page, not to the
        // payload a client happens to send. An editor saving from a UI that
        // knows nothing about translation groups (or an older frontend, or a
        // script) must not silently unlink the page from its other languages.
        //
        // Linking and unlinking are explicit operations with their own entry
        // points; an ordinary save is never one of them.
        if (isset($existingData['translationGroup'])) {
            $data['translationGroup'] = $existingData['translationGroup'];
        }

        // Preserve originalSrc for video widgets to prevent URL loss when whitelist changes
        $data = $this->preserveVideoOriginalUrls($data, $existingData);

        try {
            $validatedData = $this->validateAndSanitizePage($data);
        } catch (\Exception $e) {
            $this->logger->error('[updatePage] Validation failed: ' . $e->getMessage(), [
                'pageId' => $originalId,
                'trace' => $e->getTraceAsString(),
            ]);
            throw new \InvalidArgumentException('Page validation failed: ' . $e->getMessage());
        }

        try {
            // Create version before update using GroupFolders VersionsBackend
            // GroupFolders 20.1.7+ has reliable versioning support
            $this->pageVersionService->createBeforeUpdate($file);

            // Update the file
            $file->putContent(json_encode($validatedData, JSON_PRETTY_PRINT));

        } catch (\Exception $e) {
            throw new \InvalidArgumentException('Failed to write updated page data: ' . $e->getMessage());
        }

        // Clear caches for this page (and uniqueId if present)
        $this->clearCache($originalId);
        if (isset($validatedData['uniqueId'])) {
            $this->clearCache($validatedData['uniqueId']);
        }

        // Update page metadata index (non-blocking — page was already saved).
        // Index the language the page actually LIVES in, never the editor's
        // own: since #90 an editor can save a page outside their own language,
        // and getUserLanguage() here wrote rows under the WRONG language. The
        // index is keyed (unique_id, language), so those rows did not match the
        // existing entry — every such save INSERTed a duplicate under a
        // language the page was never in, and nothing ever cleaned them up.
        // Mirrors createPageAtPath(), which already derives it from the folder.
        try {
            $folderPath = $result['folder']->getPath();
            $language = $this->languageOfFolder($result['folder']) ?? $this->getUserLanguage();
            $this->pageIndexService->indexPage($validatedData, $language, $folderPath, $file->getId(), $result['folder']->getId());
        } catch (\Exception $e) {
            $this->logger->warning('Failed to update page index', ['error' => $e->getMessage()]);
        }

        // Return data with id for frontend (id is derived from folder name)
        // Get id from folder name (for home page it's 'home', otherwise folder basename)
        $pageId = ($result['isHome'] ?? false) ? 'home' : $result['folder']->getName();

        // Hand back the version this write produced, so the editor can keep
        // saving without reloading. Without it the client would still hold the
        // token from page load, and its NEXT save would look stale against the
        // file it just wrote — a conflict with itself.
        return array_merge(
            ['id' => $pageId],
            $validatedData,
            ['baseVersion' => $file->getMTime()]
        );
    }

    /**
     * Delete a page and all its assets
     */
    public function deletePage(string $id): void {
        if ($id === 'home') {
            throw new \InvalidArgumentException('Cannot delete home page');
        }

        // Resolve by uniqueId (page-…) first, then fall back to legacy folder id.
        // Deletion follows the page across language folders, so a page the user
        // can see is also a page the user can delete (issue #90); the caller's
        // permission check still decides whether the delete is allowed.
        $languageFolder = $this->getLanguageFolder();
        $result = strpos($id, 'page-') === 0
            ? $this->locatePageAnyLanguage($languageFolder, $id)
            : $this->findPageById($languageFolder, $this->sanitizeId($id));

        if ($result === null) {
            throw new PageNotFoundException('Page not found: ' . $id);
        }

        // Normalize $id to the folder name for downstream index/event use.
        $id = isset($result['folder']) ? $result['folder']->getName() : $this->sanitizeId($id);

        // Read the page JSON once for uniqueId (homepage guard + comment cleanup).
        $pageData = [];
        if (isset($result['file'])) {
            $decoded = json_decode($result['file']->getContent(), true);
            if (is_array($decoded)) {
                $pageData = $decoded;
            }
        }

        // The configured homepage cannot be deleted — reassign it first
        // (issue: configurable homepage). Distinguishable error so the UI can
        // prompt the user to pick another homepage.
        $resolvedUniqueId = $pageData['uniqueId'] ?? '';
        if ($resolvedUniqueId !== '' && $this->isHomepage($resolvedUniqueId)) {
            throw new \InvalidArgumentException('HOMEPAGE_PROTECTED');
        }

        // Get page data before deletion to retrieve uniqueId for comment cleanup
        try {
            $uniqueId = $pageData['uniqueId'] ?? '';

            // Dispatch event to cleanup comments/reactions before deleting the page
            if (!empty($uniqueId)) {
                $this->eventDispatcher->dispatchTyped(new PageDeletedEvent($id, $uniqueId));
            }
        } catch (\Exception $e) {
            // Log but don't block deletion if event dispatch fails
            $this->logger->warning('Failed to dispatch PageDeletedEvent for page ' . $id . ': ' . $e->getMessage());
        }

        // The index rows are deliberately LEFT IN PLACE. Deleting a page moves
        // its folder to the trashbin, which is reversible, so anything dropped
        // here would have to be rebuilt on restore — and restoring fires no
        // event at all (verified on NC34: trashing gives NodeDeletedEvent,
        // restoring gives nothing). Rows removed here could therefore never
        // come back, which is exactly why a restored page used to reappear in
        // Files but stay missing from the IntraVox page structure until
        // `occ intravox:reindex` was run by hand.
        //
        // Instead the rows stay and readers ask the filecache whether the file
        // is still live (PageIndexService::whereFileIsLive()). A trashed page has
        // its filecache path moved out of `files/`, so it drops out of every
        // listing without a flag to maintain, and a restore puts it back —
        // no event, no repair step. The rows are removed for good by
        // CacheCleanupListener once the trashbin is emptied.

        // Delete the entire folder (includes .json, images/, files/)
        $result['folder']->delete();

        // Clear caches
        $this->clearCache();
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
        $lang = $this->getUserLanguage();
        $languageFolder = $this->getLanguageFolder();

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
        if ($pageId === 'home') {
            throw new \InvalidArgumentException('The home page cannot be moved');
        }

        $languageFolder = $this->getLanguageFolder();

        // Locate the source page folder, following it across language folders
        // like every other operation on an existing page (#90). This is safe
        // ONLY because the destination is anchored to the source's own language
        // below and the language guard backs it up: resolving the source
        // cross-language while leaving the destination on the user's language
        // is what would relocate content between languages.
        $source = strpos($pageId, 'page-') === 0
            ? $this->locatePageAnyLanguage($languageFolder, $pageId)
            : $this->locatePageBySlugAnyLanguage($languageFolder, $this->sanitizeId($pageId));
        if (!$source || !isset($source['folder'])) {
            throw new PageNotFoundException('Page not found: ' . $pageId);
        }

        // The page's OWN language folder governs this move, not the user's.
        // Everything below (the root destination, the depth check, the language
        // guard) is anchored here so a move can never leave the tree the page
        // lives in. Falls back to the user's folder only when the language
        // cannot be derived, which keeps single-language installs unchanged.
        $sourceLanguageFolder = $this->languageFolderOfPageResult($source) ?? $languageFolder;

        // The configured homepage cannot be moved — reassign it first
        // (issue: configurable homepage).
        $sourceUniqueId = strpos($pageId, 'page-') === 0 ? $pageId : '';
        if ($sourceUniqueId === '' && isset($source['file'])) {
            $decoded = json_decode($source['file']->getContent(), true);
            $sourceUniqueId = is_array($decoded) ? ($decoded['uniqueId'] ?? '') : '';
        }
        if ($sourceUniqueId !== '' && $this->isHomepage($sourceUniqueId)) {
            throw new \InvalidArgumentException('HOMEPAGE_PROTECTED');
        }

        $sourceFolder = $source['folder'];
        $sourcePath = $sourceFolder->getPath();

        // Resolve the destination parent folder (root or a page's own folder).
        if ($targetParentId === '' ) {
            // Root of the page's OWN language, never the user's. Using the
            // user's folder here would physically relocate the page (and its
            // whole subtree) into another language the moment the source
            // resolved cross-language — silently, with no undo.
            $targetParentFolder = $sourceLanguageFolder;
        } else {
            // Search from the source's language first: a move within one tree
            // is the normal case, and it keeps the parent lookup consistent
            // with the source rather than with the user's profile language.
            $targetResult = strpos($targetParentId, 'page-') === 0
                ? $this->locatePageAnyLanguage($sourceLanguageFolder, $targetParentId)
                : $this->findPageById($sourceLanguageFolder, $this->sanitizeId($targetParentId));
            if (!$targetResult || !isset($targetResult['folder'])) {
                throw new PageNotFoundException('Target parent page not found: ' . $targetParentId);
            }
            $targetParentFolder = $targetResult['folder'];
        }
        $targetParentPath = $targetParentFolder->getPath();

        // Language guard — the backstop for everything above. Even if a future
        // change miscomputes the destination, a move that would cross language
        // folders is refused rather than performed. Language folders are
        // independent content trees, so this is a relocation between intranets,
        // not a translation.
        $sourceLanguage = $this->languageOfFolder($sourceFolder);
        $targetLanguage = $this->languageOfFolder($targetParentFolder);
        if ($sourceLanguage !== null && $targetLanguage !== null && $sourceLanguage !== $targetLanguage) {
            throw new CrossLanguageMoveException(sprintf(
                'This page is in %s and cannot be moved into the %s structure. Pages stay in the language they were written in.',
                $this->languageDisplayName($sourceLanguage),
                $this->languageDisplayName($targetLanguage)
            ));
        }

        // Cycle guard: refuse moving into itself or one of its own descendants,
        // which would detach (and lose) the subtree.
        if ($targetParentPath === $sourcePath
            || strpos($targetParentPath . '/', $sourcePath . '/') === 0) {
            throw new \InvalidArgumentException('Cannot move a page into itself or its descendant');
        }

        // No-op if already directly under the target parent.
        if (dirname($sourcePath) === $targetParentPath) {
            return;
        }

        // Respect the configured max nesting depth at the destination.
        $targetRelPath = $this->getRelativePathFromRoot($targetParentFolder);
        $this->validateDepth($targetRelPath);

        // Permission preflight. movePage() had none at all: it called move()
        // and relied on the filesystem to throw, which surfaces as an opaque
        // 500 and leaves any partial state unguarded. Mirrors the checks in
        // createPageAtPath() (isCreatable) and updatePage() (isUpdateable).
        // A move both removes from the source and creates at the destination,
        // so both sides are checked.
        if (!$sourceFolder->isDeletable()) {
            throw new ForbiddenException('You do not have permission to move this page');
        }
        if (!$targetParentFolder->isCreatable()) {
            throw new ForbiddenException('You do not have permission to move a page here');
        }

        // Resolve a non-colliding folder name at the destination (mirror createPage).
        $baseName = $sourceFolder->getName();
        $newName = $baseName;
        $counter = 2;
        while ($targetParentFolder->nodeExists($newName)) {
            $newName = $baseName . '-' . $counter;
            $counter++;
        }

        // Relocate the whole folder; children travel inside it.
        $newPath = $targetParentPath . '/' . $newName;
        $sourceFolder->move($newPath);

        // The index stores a path per page, and the move just invalidated it
        // for this page AND every descendant that travelled with it. Rewriting
        // the prefix is one statement per affected row; re-walking the subtree
        // would be the filesystem traversal the index exists to avoid.
        // Non-blocking: the move already succeeded on disk, so a failure here
        // must not surface as a failed move — `occ intravox:reindex` repairs it.
        try {
            $this->pageIndexService->repathSubtree($sourcePath, $newPath);
        } catch (\Throwable $e) {
            $this->logger->warning('movePage: could not repath index subtree', [
                'from' => $sourcePath,
                'to' => $newPath,
                'error' => $e->getMessage(),
            ]);
        }

        // Send the moved page to the end of its new siblings by clearing its
        // explicit order — the stable comparator then places it after ordered
        // siblings, i.e. last. (A fresh reorder can pin it precisely later.)
        try {
            $movedResult = strpos($pageId, 'page-') === 0
                ? $this->findPageByUniqueId($targetParentFolder, $pageId)
                : $this->findPageById($targetParentFolder, $this->sanitizeId($pageId));
            if ($movedResult && isset($movedResult['file'])) {
                $file = $movedResult['file'];
                $data = json_decode($file->getContent(), true);
                if (is_array($data) && array_key_exists('order', $data)) {
                    unset($data['order']);
                    $file->putContent(json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
                }
            }
        } catch (\Throwable $e) {
            // Non-fatal: the move succeeded; ordering just falls back to legacy.
            $this->logger->warning('movePage: could not reset order after move', ['error' => $e->getMessage()]);
        }

        // Critical: refresh tree + permission caches so the move is visible.
        $this->clearCache();
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

        $pageId = $this->sanitizeId($pageId);

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
        $languageFolder = $this->getReadLanguageFolder();

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
            $pageId = $this->sanitizeId($originalPageId);

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
     * @deprecated Delegated to PageIdUtils::sanitizeId.
     */
    private function sanitizeId(string $id): string {
        return $this->idUtils->sanitizeId($id);
    }

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
     * @deprecated Use HtmlSanitizer::decodeEntitiesRecursive directly.
     * Kept as a thin wrapper so internal call-sites continue to work; will be
     * removed once all call-sites are migrated to the injected sanitizer.
     */
    private function decodeHtmlEntitiesRecursive(string $value): string {
        return $this->htmlSanitizer->decodeEntitiesRecursive($value);
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
        $folder = $this->getLanguageFolder();
        $result = null;

        // Check for uniqueId pattern (page-xxxx) like getPage() does. Follows
        // the page across language folders so an operation on a page the user
        // can see never fails with "Page not found" (issue #90).
        if (strpos($pageId, 'page-') === 0) {
            $result = $this->locatePageAnyLanguage($folder, $pageId);
        }

        // Fall back to legacy ID lookup
        if ($result === null) {
            $result = $this->findPageById($folder, $this->sanitizeId($pageId));
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
        $folder = $this->getLanguageFolder();
        $result = null;

        // Check for uniqueId pattern (page-xxxx) like getPage() does. Follows
        // the page across language folders so an operation on a page the user
        // can see never fails with "Page not found" (issue #90).
        if (strpos($pageId, 'page-') === 0) {
            $result = $this->locatePageAnyLanguage($folder, $pageId);
        }

        // Fall back to legacy ID lookup
        if ($result === null) {
            $result = $this->findPageById($folder, $this->sanitizeId($pageId));
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
        $diff = time() - $timestamp;

        if ($diff < 60) {
            return 'just now';
        } elseif ($diff < 3600) {
            $minutes = floor($diff / 60);
            return $minutes . ' minute' . ($minutes > 1 ? 's' : '') . ' ago';
        } elseif ($diff < 86400) {
            $hours = floor($diff / 3600);
            return $hours . ' hour' . ($hours > 1 ? 's' : '') . ' ago';
        } elseif ($diff < 2592000) {
            $days = floor($diff / 86400);
            return $days . ' day' . ($days > 1 ? 's' : '') . ' ago';
        } elseif ($diff < 31536000) {
            $months = floor($diff / 2592000);
            return $months . ' month' . ($months > 1 ? 's' : '') . ' ago';
        } else {
            $years = floor($diff / 31536000);
            return $years . ' year' . ($years > 1 ? 's' : '') . ' ago';
        }
    }

    /**
     * Generate a UUID v4
     */
    /**
     * @deprecated Delegated to PageIdUtils::generateUUID.
     */
    private function generateUUID(): string {
        return $this->idUtils->generateUUID();
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
        $folder = $this->getLanguageFolder();
        $result = null;

        // Check for uniqueId pattern (page-xxxx). Follows the page across
        // language folders so an operation on a page the user can see never
        // fails with "Page not found" (issue #90).
        if (strpos($pageId, 'page-') === 0) {
            $result = $this->locatePageAnyLanguage($folder, $pageId);
        }

        // Fall back to legacy ID lookup
        if ($result === null) {
            $result = $this->findPageById($folder, $this->sanitizeId($pageId));
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
        $folder = $this->getLanguageFolder();
        $result = null;

        // Check for uniqueId pattern (page-xxxx). Follows the page across
        // language folders so an operation on a page the user can see never
        // fails with "Page not found" (issue #90).
        if (strpos($pageId, 'page-') === 0) {
            $result = $this->locatePageAnyLanguage($folder, $pageId);
        }

        // Fall back to legacy ID lookup
        if ($result === null) {
            $result = $this->findPageById($folder, $this->sanitizeId($pageId));
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
            $newName = $this->sanitizeId($requestedName);
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
        $stats = ['scanned' => 0, 'changed' => 0, 'files' => []];
        $base = $this->getIntraVoxFolder();
        foreach ($this->getCachedDirectoryListing($base) as $langFolder) {
            if (!($langFolder instanceof \OCP\Files\Folder)) {
                continue;
            }
            // Language folders are 2–3 letter codes; skip _media/_resources/etc.
            if (!preg_match('/^[a-z]{2,3}$/', $langFolder->getName())) {
                continue;
            }
            $this->repairEntitiesInFolder($langFolder, $dryRun, $stats);
        }
        return $stats;
    }

    /**
     * Recurse a folder, decoding entity-encoded plain-text in each page JSON.
     *
     * @param array{scanned:int, changed:int, files:string[]} $stats
     */
    private function repairEntitiesInFolder(\OCP\Files\Folder $folder, bool $dryRun, array &$stats): void {
        foreach ($this->getCachedDirectoryListing($folder) as $node) {
            if ($node instanceof \OCP\Files\File && str_ends_with($node->getName(), '.json')) {
                // Only page JSONs carry the fields we repair; navigation.json,
                // footer.json, homepage.json are handled/normalised elsewhere.
                $name = $node->getName();
                if (in_array($name, ['navigation.json', 'footer.json', 'homepage.json'], true)) {
                    continue;
                }
                $stats['scanned']++;
                try {
                    $data = json_decode($node->getContent(), true);
                    if (!is_array($data) || !isset($data['title'])) {
                        continue;
                    }
                    $before = json_encode($data);
                    $this->decodePlainTextFields($data);
                    $after = json_encode($data);
                    if ($before !== $after) {
                        $stats['changed']++;
                        $stats['files'][] = $node->getPath();
                        if (!$dryRun) {
                            $node->putContent(json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
                        }
                    }
                } catch (\Throwable $e) {
                    $this->logger->warning('[PageService] repairEntities skipped ' . $node->getPath() . ': ' . $e->getMessage());
                }
            } elseif ($node instanceof \OCP\Files\Folder) {
                $this->repairEntitiesInFolder($node, $dryRun, $stats);
            }
        }
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
        $stats = ['scanned' => 0, 'indexed' => 0, 'languages' => []];

        $base = $this->getIntraVoxFolder();
        $languageFolders = [];
        foreach ($this->getCachedDirectoryListing($base) as $node) {
            if (!($node instanceof \OCP\Files\Folder)) {
                continue;
            }
            // Language folders are 2–3 letter codes; skips _media/_resources.
            if (!preg_match('/^[a-z]{2,3}$/', $node->getName())) {
                continue;
            }
            $languageFolders[] = $node;
        }

        // Clear only after the tree is readable: wiping first and then failing
        // to read would leave the install with no index at all.
        if (!$dryRun) {
            $this->pageIndexService->clearAll();
        }

        foreach ($languageFolders as $langFolder) {
            $lang = $langFolder->getName();
            $stats['languages'][$lang] = 0;
            $this->rebuildIndexInFolder($langFolder, $lang, $dryRun, $stats);
        }

        return $stats;
    }

    /**
     * Recurse one language folder, indexing every page JSON found.
     *
     * @param array{scanned:int, indexed:int, languages:array<string,int>} $stats
     */
    private function rebuildIndexInFolder(
        \OCP\Files\Folder $folder,
        string $language,
        bool $dryRun,
        array &$stats
    ): void {
        // Two passes over one listing: subfolder names first, because whether a
        // JSON file IS a page depends on them (see below).
        $listing = $this->getCachedDirectoryListing($folder);
        $subfolders = [];
        foreach ($listing as $node) {
            if ($node instanceof \OCP\Files\Folder) {
                $subfolders[$node->getName()] = $node;
            }
        }

        foreach ($subfolders as $name => $node) {
            // Media, asset and infrastructure folders hold no pages.
            if (PagePathHelper::isInfrastructureFolder($name)) {
                continue;
            }
            $this->rebuildIndexInFolder($node, $language, $dryRun, $stats);
        }

        foreach ($listing as $node) {
            if (!($node instanceof \OCP\Files\File) || !str_ends_with($node->getName(), '.json')) {
                continue;
            }
            // Per-language config files are not pages.
            if (in_array($node->getName(), ['navigation.json', 'footer.json', 'homepage.json'], true)) {
                continue;
            }

            // Only files that fit the PAGE MODEL are pages. Indexing every JSON
            // in sight put loose files (POC data dropped beside a real page)
            // into the index, and since 2.0 serves the page list FROM the
            // index, those rows became ghost entries that 404 when clicked —
            // the tree never showed them and getPage cannot resolve them.
            $base = substr($node->getName(), 0, -5);
            if ($base === $folder->getName()) {
                $pageFolder = $folder;               // {slug}/{slug}.json — canonical
            } elseif ($node->getName() === 'home.json' && $folder->getName() === $language) {
                $pageFolder = $folder;               // language-root homepage
            } elseif (isset($subfolders[$base])) {
                $pageFolder = $subfolders[$base];    // legacy beside-layout: {slug}.json next to {slug}/
            } else {
                continue;                            // loose JSON — not a page
            }

            $stats['scanned']++;
            try {
                $data = json_decode($node->getContent(), true);
                if (!is_array($data) || empty($data['uniqueId'])) {
                    // A JSON file without a uniqueId is not an indexable page.
                    continue;
                }
                if (!$dryRun) {
                    $this->pageIndexService->indexPage(
                        $data,
                        $language,
                        // The page's OWN folder, matching createPage/updatePage —
                        // locateViaIndex derives its candidates from this path.
                        $pageFolder->getPath(),
                        $node->getId(),
                        $pageFolder->getId()
                    );
                }
                $stats['indexed']++;
                $stats['languages'][$language]++;
            } catch (\Throwable $e) {
                // One unreadable file must not abort the whole rebuild.
                $this->logger->warning(
                    '[PageService] rebuildIndex skipped ' . $node->getPath() . ': ' . $e->getMessage()
                );
            }
        }
    }

    /**
     * Decode HTML entities in the plain-text fields of a page-data array,
     * in place: title, and each widget's content/alt/title and link titles.
     */
    private function decodePlainTextFields(array &$data): void {
        if (isset($data['title']) && is_string($data['title'])) {
            $data['title'] = $this->htmlSanitizer->decodeEntitiesRecursive($data['title']);
        }
        $rows = $data['layout']['rows'] ?? null;
        if (!is_array($rows)) {
            return;
        }
        foreach ($rows as &$row) {
            if (isset($row['sectionTitle']) && is_string($row['sectionTitle'])) {
                $row['sectionTitle'] = $this->htmlSanitizer->decodeEntitiesRecursive($row['sectionTitle']);
            }
            $columns = $row['columns'] ?? (isset($row['widgets']) ? [$row] : []);
            foreach ($columns as &$col) {
                foreach (($col['widgets'] ?? []) as &$widget) {
                    foreach (['content', 'alt', 'title'] as $field) {
                        if (isset($widget[$field]) && is_string($widget[$field])) {
                            $widget[$field] = $this->htmlSanitizer->decodeEntitiesRecursive($widget[$field]);
                        }
                    }
                    foreach (($widget['links'] ?? []) as &$link) {
                        if (isset($link['title']) && is_string($link['title'])) {
                            $link['title'] = $this->htmlSanitizer->decodeEntitiesRecursive($link['title']);
                        }
                    }
                    unset($link);
                }
                unset($widget);
            }
            unset($col);
        }
        unset($row);
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
        $languageFolder = $this->getLanguageFolder();

        // Resolve the parent folder whose direct children we are reordering.
        if ($parentUniqueId === null || $parentUniqueId === '') {
            $parentFolder = $languageFolder;
        } else {
            $parentResult = $this->findPageByUniqueId($languageFolder, $parentUniqueId);
            if (!$parentResult || !isset($parentResult['folder'])) {
                throw new \Exception('Parent page not found: ' . $parentUniqueId);
            }
            $parentFolder = $parentResult['folder'];
        }

        // Build a uniqueId => page-JSON File map of this parent's DIRECT children
        // in a single cached directory pass. Reorder only touches direct children,
        // so we do NOT recurse into their subtrees (the old per-child
        // findPageByUniqueId() walked the whole subtree per id — O(N²) plus
        // uncached reads on a wide set). A child page is a subfolder holding
        // {folderName}.json (the canonical layout, mirrors buildPageTree); the
        // legacy loose {slug}.json at the parent level is also honoured.
        $isLanguageRoot = ($parentFolder->getPath() === $languageFolder->getPath());
        $childMap = [];
        foreach ($this->getCachedDirectoryListing($parentFolder) as $item) {
            $itemName = $item->getName();
            $file = null;

            if ($item->getType() === \OCP\Files\FileInfo::TYPE_FOLDER) {
                // Skip media/special folders (mirror findPageByUniqueId).
                if ($itemName === '_media' || $itemName === 'images' || $itemName === 'files' || $itemName === '.nomedia') {
                    continue;
                }
                try {
                    $candidate = $item->get($itemName . '.json');
                    if ($candidate instanceof \OCP\Files\File) {
                        $file = $candidate;
                    }
                } catch (NotFoundException $e) {
                    continue; // a folder without its page-JSON is not a page
                }
            } else {
                // Loose {slug}.json directly in the parent (legacy flat layout).
                if (substr($itemName, -5) !== '.json' || $itemName === 'home.json') {
                    continue; // home.json is the homepage, never ordered
                }
                if ($isLanguageRoot && ($itemName === 'navigation.json' || $itemName === 'footer.json' || $itemName === 'homepage.json')) {
                    continue; // root config files are not pages
                }
                $file = $item;
            }

            if ($file === null) {
                continue;
            }
            $data = json_decode($this->getCachedFileContent($file), true);
            if (is_array($data) && isset($data['uniqueId'])) {
                $childMap[$data['uniqueId']] = $file;
            }
        }

        foreach ($orderedChildIds as $index => $childId) {
            // The homepage is pinned first and never carries an order — skip the
            // legacy 'home' id as well as a configured pointer target.
            if ($childId === 'home' || $this->isHomepage($childId)) {
                continue;
            }

            // A foreign id (not among this parent's direct children) is simply
            // absent from the map and is skipped, rather than reordered.
            $file = $childMap[$childId] ?? null;
            if ($file === null) {
                continue;
            }

            $data = json_decode($this->getCachedFileContent($file), true);
            if (!is_array($data)) {
                continue;
            }

            if (($data['order'] ?? null) !== $index) {
                $data['order'] = $index;
                $encoded = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
                $file->putContent($encoded);
                // Keep the per-request content cache honest with what we just wrote.
                $this->fileContentCache[$file->getPath()] = $encoded;
            }
        }

        // Critical: without this the new order stays invisible for up to 5 min.
        $this->clearCache();
    }

    /**
     * Format bytes to human readable format
     */
    /**
     * @deprecated Delegated to PageIdUtils::formatBytes.
     */
    private function formatBytes(int $bytes): string {
        return $this->idUtils->formatBytes($bytes);
    }

    /**
     * Check if a page is visible in the Nextcloud file cache
     * This is useful to determine if a groupfolder page has been indexed
     *
     * @param string $pageId The page ID to check
     * @return array Status information about the page's visibility
     */
    public function checkPageCacheStatus(string $pageId): array {
        try {
            $folder = $this->getLanguageFolder();
            $lang = $this->getUserLanguage();

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
        $folder = $this->getLanguageFolder();
        $result = null;

        // Check for uniqueId pattern (page-xxxx) like getPage() does. Follows
        // the page across language folders so an operation on a page the user
        // can see never fails with "Page not found" (issue #90).
        if (strpos($pageId, 'page-') === 0) {
            $result = $this->locatePageAnyLanguage($folder, $pageId);
        }

        // Fall back to legacy ID lookup
        if ($result === null) {
            $result = $this->findPageById($folder, $this->sanitizeId($pageId));
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
        $tree = $this->markCurrentPageInTree($tree, $currentPageId);
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
                    $node['permissions'] = $this->getFolderPermissions($path);
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
     * @deprecated Delegated to PagePathHelper::markCurrentPageInTree.
     */
    private function markCurrentPageInTree(array $tree, ?string $currentPageId): array {
        return $this->pathHelper->markCurrentPageInTree($tree, $currentPageId);
    }

    /**
     * Recursively build the page tree from folder structure
     */
    /**
     * Stable sibling sort (issue #69). Pages WITH an integer `order` come first,
     * ascending. Pages WITHOUT `order` (all legacy pages) keep their original
     * input order AFTER the ordered ones — so an installation that has never
     * reordered anything does not reshuffle.
     *
     * @param array<int, array> $siblings
     * @return array<int, array>
     */
    private function sortSiblingsByOrder(array $siblings): array {
        $decorated = [];
        foreach ($siblings as $i => $node) {
            $decorated[] = ['i' => $i, 'node' => $node];
        }
        usort($decorated, function ($a, $b) {
            $ao = $a['node']['order'] ?? null;
            $bo = $b['node']['order'] ?? null;
            $aHas = is_int($ao);
            $bHas = is_int($bo);
            if ($aHas && $bHas) {
                return ($ao <=> $bo) ?: ($a['i'] <=> $b['i']);
            }
            if ($aHas !== $bHas) {
                return $aHas ? -1 : 1;
            }
            return $a['i'] <=> $b['i'];
        });
        return array_map(fn ($d) => $d['node'], $decorated);
    }

    private function buildPageTree($folder, array &$tree, ?string $currentPageId, ?string $language = null): void {
        // Collect siblings locally so we can apply the stable order comparator
        // (issue #69) before appending them to the tree in the right sequence.
        $nodes = [];
        foreach ($this->getCachedDirectoryListing($folder) as $item) {
            if ($item->getType() !== \OCP\Files\FileInfo::TYPE_FOLDER) {
                continue;
            }

            $folderName = $item->getName();

            // Skip special folders
            if (PagePathHelper::isInfrastructureFolder($folderName)) {
                continue;
            }

            // Underscore- and dot-prefixed folders are infrastructure (_media,
            // _resources, _templates, hidden dirs). They never held pages, but
            // the placeholder recursion below WOULD walk into them — and
            // _templates does contain page-shaped JSON that must never surface
            // as tree nodes — so they are excluded by name shape, not by list.
            if (str_starts_with($folderName, '_') || str_starts_with($folderName, '.')) {
                continue;
            }

            // Skip folders starting with emoji (images folders)
            if (preg_match('/^[\x{1F300}-\x{1F9FF}]/u', $folderName)) {
                continue;
            }

            $foundPage = false;

            // Look for {foldername}.json inside the folder
            try {
                $jsonFile = $item->get($folderName . '.json');

                // Check if file is readable and user has access
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
                    // Folder permissions (respects ACLs + mount writability).
                    $perm = $this->permissionService->permissionsFromNode($item);

                    // Skip if user can't read this folder
                    if (!$perm['canRead']) {
                        continue;
                    }

                    $pageNode = [
                        'uniqueId' => $data['uniqueId'],
                        'title' => $data['title'],
                        'status' => $data['status'] ?? 'published',
                        // fileId of the page JSON, so the tree gate can resolve the
                        // publish/expiration MetaVox fields for scheduled visibility.
                        'fileId' => ($jsonFile instanceof \OCP\Files\File) ? $jsonFile->getId() : null,
                        'path' => $this->getRelativePathFromRoot($item),
                        'language' => $language ?? $this->getUserLanguage(),
                        'isCurrent' => ($currentPageId === $data['uniqueId']),
                        'children' => [],
                        'permissions' => $perm
                    ];

                    // Carry the sibling order (issue #69) for the comparator. Kept
                    // out of the public node shape below — it's stripped after sort.
                    if (isset($data['order']) && is_int($data['order'])) {
                        $pageNode['order'] = $data['order'];
                    }

                    // Recursively get children
                    $this->buildPageTree($item, $pageNode['children'], $currentPageId, $language);

                    $nodes[] = $pageNode;
                    $foundPage = true;
                }
            } catch (\Exception $e) {
                // This folder doesn't contain a valid page or can't be read, continue
            } catch (\Throwable $e) {
                // Catch any other errors
                continue;
            }

            // A folder without a page of its own can still hold pages below it —
            // exactly what translating a deep page before its ancestors produces:
            // createTranslation mirrors the source path and creates the missing
            // levels as bare folders. Skipping such a folder made every page
            // underneath unreachable in the tree, while search, breadcrumb and
            // direct links all still worked — a ghost page for anyone browsing.
            //
            // The breadcrumb already renders a missing ancestor as a plain,
            // non-clickable label; the tree now applies the same rule: recurse,
            // and when pages exist below, emit a non-navigable pass-through
            // node. A bare folder with nothing underneath still renders nothing.
            if (!$foundPage) {
                try {
                    $perm = $this->permissionService->permissionsFromNode($item);
                    if (!$perm['canRead']) {
                        continue;
                    }
                    $children = [];
                    $this->buildPageTree($item, $children, $currentPageId, $language);
                    if ($children !== []) {
                        $nodes[] = [
                            // Synthetic, stable identity: never navigable, but
                            // the tree needs a key for expand/collapse state
                            // and list rendering. The 'folder:' prefix cannot
                            // collide with real ids, which are 'page-…'.
                            'uniqueId' => 'folder:' . $this->getRelativePathFromRoot($item),
                            // Same label derivation the breadcrumb uses for a
                            // missing ancestor. This is the SOURCE-language
                            // slug until the ancestor is translated — accepted,
                            // and itself a nudge to translate it.
                            'title' => ucfirst(str_replace('-', ' ', $folderName)),
                            'status' => 'published',
                            'isPlaceholder' => true,
                            'fileId' => null,
                            'path' => $this->getRelativePathFromRoot($item),
                            'language' => $language ?? $this->getUserLanguage(),
                            'isCurrent' => false,
                            'children' => $children,
                            'permissions' => $perm,
                        ];
                    }
                } catch (\Throwable $e) {
                    continue;
                }
            }
        }

        // Apply the stable sibling order (issue #69) and drop the internal
        // 'order' key so the tree shape the frontend sees is unchanged.
        foreach ($this->sortSiblingsByOrder($nodes) as $node) {
            unset($node['order']);
            $tree[] = $node;
        }
    }

    /**
     * Search pages by query string
     * Searches in page titles and text widget content
     * OPTIMIZED: Loads all content in a single filesystem traversal
     */
    public function searchPages(string $query): array {
        $results = [];
        $query = mb_strtolower($query);

        // Get all pages with full content in a single traversal
        $pagesWithContent = $this->listPagesWithContent();

        // MetaVox metadata is stored alongside the file, not inside the page
        // JSON, so a page tagged "Stad: Luik" is invisible to a content-only
        // search. Batch-load it for every page in one query (no N+1) and treat
        // it as an additional match source below.
        $metaVoxData = $this->getMetaVoxDataForFiles(
            array_values(array_filter(array_column($pagesWithContent, 'fileId')))
        );
        $metaVoxLabels = empty($metaVoxData) ? [] : $this->getMetaVoxFieldLabels();

        foreach ($pagesWithContent as $pageData) {
            $matches = [];
            $score = 0;

            // Skip pages without uniqueId
            if (!isset($pageData['uniqueId']) || empty($pageData['uniqueId'])) {
                continue;
            }

            // Search in title (higher weight)
            if (isset($pageData['title']) && mb_stripos($pageData['title'], $query) !== false) {
                $score += 10;
                $matches[] = [
                    'type' => 'title',
                    'text' => $pageData['title']
                ];
            }

            // Search in uniqueId (medium weight)
            if (mb_stripos($pageData['uniqueId'], $query) !== false) {
                $score += 5;
            }

            // Search in content - layout is already loaded
            // Collect all widgets from all layout areas
            $allWidgets = [];

            // Main rows
            if (isset($pageData['layout']['rows'])) {
                foreach ($pageData['layout']['rows'] as $row) {
                    if (isset($row['widgets'])) {
                        $allWidgets = array_merge($allWidgets, $row['widgets']);
                    }
                }
            }

            // Header row
            if (isset($pageData['layout']['headerRow']['widgets'])) {
                $allWidgets = array_merge($allWidgets, $pageData['layout']['headerRow']['widgets']);
            }

            // Side columns
            if (isset($pageData['layout']['sideColumns']['left']['widgets'])) {
                $allWidgets = array_merge($allWidgets, $pageData['layout']['sideColumns']['left']['widgets']);
            }
            if (isset($pageData['layout']['sideColumns']['right']['widgets'])) {
                $allWidgets = array_merge($allWidgets, $pageData['layout']['sideColumns']['right']['widgets']);
            }

            // Search through all collected widgets
            foreach ($allWidgets as $widget) {
                $widgetMatches = $this->searchWidget($widget, $query);
                foreach ($widgetMatches as $match) {
                    $score += $match['score'];
                    $matches[] = [
                        'type' => $match['type'],
                        'text' => $match['text']
                    ];
                }
            }

            // Search MetaVox metadata (Stad, Thema, ...). Scored between title
            // (10) and plain content so a metadata hit ranks meaningfully but
            // never outranks the page actually being named after the term.
            $fileId = $pageData['fileId'] ?? null;
            $pageMeta = $fileId !== null ? ($metaVoxData[$fileId] ?? []) : [];
            $metaMatches = $this->searchMetaVoxValues(
                $pageMeta,
                $query,
                $metaVoxLabels,
                $fileId !== null ? ($this->metaVoxGroupfolderByFile[$fileId] ?? null) : null
            );
            if (!empty($metaMatches)) {
                $score += 7;
                // The subline mirrors MetaVox's own format so results read the
                // same in both providers: "Label: value" joined with " • ",
                // matching field first, capped at 3 fields.
                $matches[] = [
                    'type' => 'metadata',
                    'text' => $metaMatches['subline'],
                ];
            }

            // If we have matches, add to results
            if ($score > 0) {
                $results[] = [
                    'uniqueId' => $pageData['uniqueId'] ?? null,
                    'title' => $pageData['title'] ?? 'Untitled',
                    'path' => $pageData['path'] ?? '',
                    'score' => $score,
                    'matches' => array_slice($matches, 0, 3), // Limit to 3 matches per page
                    'matchCount' => count($matches)
                ];
            }
        }

        // Sort by score (highest first)
        usort($results, function($a, $b) {
            return $b['score'] - $a['score'];
        });

        // Limit to top 20 results
        return array_slice($results, 0, 20);
    }

    /**
     * Extract a snippet of text around a search query match
     */
    /**
     * @deprecated Delegated to PageSearchHelper::extractSnippet.
     */
    private function extractSnippet(string $text, string $query, int $contextLength = 100): string {
        return $this->searchHelper->extractSnippet($text, $query, $contextLength);
    }

    /**
     * Search a single widget for matches
     *
     * @param array $widget Widget data
     * @param string $query Search query (lowercase)
     * @return array Array of matches with type, text, and score
     */
    /**
     * @deprecated Delegated to PageSearchHelper::searchWidget.
     */
    private function searchWidget(array $widget, string $query): array {
        return $this->searchHelper->searchWidget($widget, $query);
    }

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
     * @deprecated Delegated to MediaSanitizer::sanitizeFilename.
     * Thin wrapper for existing call-sites in ApiController and templates.
     */
    public function sanitizeFilename(string $filename, bool $validateExtension = true): string {
        return $this->mediaSanitizer->sanitizeFilename($filename, $validateExtension);
    }

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
        $filename = $this->sanitizeFilename($file['name']);

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
        $readFolder = $this->getReadLanguageFolder();

        $file = $this->findResourceIn($readFolder, $path);
        if ($file !== null) {
            return $file;
        }

        $baseFolder = $this->getIntraVoxFolder();
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
        $folder = $this->getReadLanguageFolder();
        $pages = [];
        // Match the served language (recommended-language fallback, #75) so
        // the news cache key and date localisation agree with the folder.
        $language = $this->resolveEffectiveLanguage() ?? $this->getUserLanguage();

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
                    return ['items' => [], 'total' => 0, 'metavoxAvailable' => $this->isMetaVoxAvailable()];
                }
            } catch (\Exception $e) {
                $this->logger->warning('News widget: Error finding source page', ['sourcePageId' => $sourcePageId, 'error' => $e->getMessage()]);
                return ['items' => [], 'total' => 0, 'metavoxAvailable' => $this->isMetaVoxAvailable()];
            }
        }
        // Legacy: If sourcePath is provided (but no sourcePageId), navigate to that folder
        elseif (!empty($sourcePath)) {
            $sourcePath = trim($sourcePath, '/');
            try {
                $folder = $folder->get($sourcePath);
            } catch (NotFoundException $e) {
                $this->logger->warning('News widget: Source folder not found', ['path' => $sourcePath]);
                return ['items' => [], 'total' => 0, 'metavoxAvailable' => $this->isMetaVoxAvailable()];
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
        if (!empty($filters) && $this->isMetaVoxAvailable()) {
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
            'metavoxAvailable' => $this->isMetaVoxAvailable(),
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
            $this->getIntraVoxFolder(),
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
     * @deprecated Delegated to NewsContentExtractor::getExcerpt.
     */
    public function getPageExcerpt(array $pageData, int $length = 150): string {
        return $this->newsContent->getExcerpt($pageData, $length);
    }

    /**
     * @deprecated Delegated to NewsContentExtractor::stripMarkdown.
     */
    private function stripMarkdown(string $text): string {
        return $this->newsContent->stripMarkdown($text);
    }

    /**
     * Find the first image in a page's layout
     * Returns array with 'src' and 'mediaFolder' or null if no image found
     */
    /**
     * @deprecated Delegated to NewsContentExtractor::getFirstImage.
     */
    public function getPageFirstImage(array $pageData): ?array {
        return $this->newsContent->getFirstImage($pageData);
    }

    /**
     * Check if MetaVox app is available
     */
    private function isMetaVoxAvailable(): bool {
        try {
            return $this->appManager->isInstalled('metavox') && $this->appManager->isEnabledForUser('metavox');
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Apply MetaVox filters to pages
     *
     * @param array $pages Pages to filter
     * @param array $filters Filter definitions
     * @param string $operator 'AND' or 'OR'
     * @return array Filtered pages
     */
    private function applyMetaVoxFilters(array $pages, array $filters, string $operator = 'AND'): array {
        if (empty($filters) || !$this->isMetaVoxAvailable()) {
            return $pages;
        }

        return $this->news()->applyMetaVoxFilters(
            $pages,
            $filters,
            $operator,
            fn(array $fileIds): array => $this->getMetaVoxDataForFiles($fileIds)
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
            fn(array $fileIds): array => $this->publicationMetaForFiles($fileIds),
            fn(array $page, array $meta): string => $this->effectivePublishState($page, $meta)
        );
    }

    /**
     * Effective publication state of a single page, evaluated live ("lazy") so a
     * scheduled page flips to published the moment its publish time passes — no
     * cron needed. Combines the manual draft/published status with the
     * admin-configured MetaVox publish/expiration date fields.
     *
     * Returns one of:
     *   'published' — publicly visible now
     *   'draft'     — manually held back
     *   'scheduled' — publish date is in the future
     *   'expired'   — expiration date has passed
     *
     * Only 'published' is visible to readers/anonymous visitors; the other three
     * are hidden from them but shown to users with write permission.
     *
     * @param array      $page        Page array (needs 'status' and 'fileId')
     * @param array|null $metaForFile Pre-fetched MetaVox fields for this file
     *                                (fieldName => value). Pass this in list
     *                                contexts to avoid an N+1 query; when null it
     *                                is looked up on demand.
     */
    public function effectivePublishState(array $page, ?array $metaForFile = null): string {
        $manualDraft = ($page['status'] ?? 'published') === 'draft';

        $publishField = $this->publicationSettings->getPublishDateField();
        $expireField = $this->publicationSettings->getExpirationDateField();

        // No scheduling configured, or MetaVox unavailable → the manual
        // draft/published flag governs, exactly as before.
        if ((empty($publishField) && empty($expireField)) || !$this->isMetaVoxAvailable()) {
            return $manualDraft ? 'draft' : 'published';
        }

        $meta = $metaForFile;
        if ($meta === null) {
            $fileId = $page['fileId'] ?? null;
            $meta = $fileId ? ($this->getMetaVoxDataForFiles([$fileId])[$fileId] ?? []) : [];
        }

        // Interpret the publish/expire dates AND "now" in one consistent instance
        // timezone. The MetaVox datetime-local input stores a naive local time
        // (e.g. "2026-08-04T15:57:00", no zone); the editor entered it in their
        // local time. Comparing that against a UTC "now" was off by the UTC
        // offset, so a page could read "Scheduled" when it was already live.
        $tz = $this->publicationTimezone();
        $now = new \DateTime('now', $tz);

        // Resolve the configured date values (field names are admin-configurable
        // in the IntraVox settings and may differ or be empty).
        $publishAt = (!empty($publishField) && !empty($meta[$publishField]))
            ? $this->parseDateTime((string)$meta[$publishField], $tz) : null;
        $expireAt = (!empty($expireField) && !empty($meta[$expireField]))
            ? $this->parseDateTime((string)$meta[$expireField], $tz) : null;

        // WordPress-style model: a Publish-on DATE, when set, governs publication
        // and overrides the manual draft flag — so you never get the confusing
        // "Draft badge + past publish date" combination. The manual draft only
        // applies when no publish date is set.
        if ($publishAt !== null) {
            if ($publishAt > $now) {
                return 'scheduled'; // future → not live yet (draft flag ignored)
            }
            // publish date has passed → published, subject only to expiration below.
        } elseif ($manualDraft) {
            return 'draft'; // no publish date → manual draft holds it back
        }

        // Expiration applies regardless of how the page became published.
        if ($expireAt !== null && $expireAt <= $now) {
            return 'expired';
        }

        return 'published';
    }

    /**
     * Whether a page must be hidden from a viewer WITHOUT write permission.
     * True for draft, scheduled (future) and expired pages.
     *
     * @param array      $page
     * @param array|null $metaForFile Optional pre-fetched MetaVox fields (see
     *                                effectivePublishState) to avoid N+1 queries.
     */
    public function isHiddenFromReaders(array $page, ?array $metaForFile = null): bool {
        return $this->effectivePublishState($page, $metaForFile) !== 'published';
    }

    /**
     * Whether a page has an active publish/expiration date (from the configured
     * MetaVox fields). When true, that date governs publication and the manual
     * draft/published toggle is overridden — the editor UI uses this to explain
     * why the toggle is showing the effective state instead of the raw status.
     *
     * @param array      $page
     * @param array|null $metaForFile Optional pre-fetched MetaVox fields.
     */
    public function hasPublicationDate(array $page, ?array $metaForFile = null): bool {
        $publishField = $this->publicationSettings->getPublishDateField();
        $expireField = $this->publicationSettings->getExpirationDateField();
        if ((empty($publishField) && empty($expireField)) || !$this->isMetaVoxAvailable()) {
            return false;
        }
        $meta = $metaForFile;
        if ($meta === null) {
            $fileId = $page['fileId'] ?? null;
            $meta = $fileId ? ($this->getMetaVoxDataForFiles([$fileId])[$fileId] ?? []) : [];
        }
        return (!empty($publishField) && !empty($meta[$publishField]))
            || (!empty($expireField) && !empty($meta[$expireField]));
    }

    /**
     * Time-aware date/time parse for publication scheduling. Unlike parseDate()
     * (which truncates to Y-m-d and made "today 03:25" count as already
     * published), this preserves the time component so a same-day schedule is
     * respected to the minute.
     *
     * The MetaVox datetime-local input stores a NAIVE local time with no zone
     * (e.g. "2026-08-04T15:57:00"); such values are interpreted in $tz (the
     * instance timezone). Values that carry an explicit offset ("…Z" / "+02:00")
     * keep their own zone.
     *
     * @param string             $dateStr
     * @param \DateTimeZone|null  $tz Timezone for naive values (default: instance).
     * @return \DateTime|null Parsed date/time, or null if unparseable.
     */
    private function parseDateTime(string $dateStr, ?\DateTimeZone $tz = null): ?\DateTime {
        $dateStr = trim($dateStr);
        if ($dateStr === '') {
            return null;
        }
        $tz = $tz ?? $this->publicationTimezone();

        $formats = [
            'Y-m-d\TH:i:s',   // ISO 8601 (naive): 2025-01-15T14:30:00
            'Y-m-d\TH:i',     // datetime-local input: 2025-01-15T14:30
            'Y-m-d H:i:s',    // 2025-01-15 14:30:00
            'Y-m-d H:i',      // 2025-01-15 14:30
            'd-m-Y H:i:s',    // European with time
            'd-m-Y H:i',
            'Y-m-d',          // date only → midnight
            'd-m-Y',
            'm/d/Y',
            'd/m/Y',
            'Y/m/d',
        ];

        // If the value carries an explicit zone/offset, honour it (don't force $tz).
        if (preg_match('/(Z|[+-]\d{2}:?\d{2})$/', $dateStr)) {
            try {
                return new \DateTime($dateStr);
            } catch (\Exception $e) {
                // fall through to format parsing
            }
        }

        foreach ($formats as $format) {
            // Parse naive values IN the instance timezone so the comparison
            // against "now" (also in $tz) is apples-to-apples.
            $date = \DateTime::createFromFormat($format, $dateStr, $tz);
            if ($date !== false) {
                // For date-only formats, createFromFormat keeps the current time;
                // normalise those to start-of-day so "publish on <date>" means
                // 00:00 of that day.
                if (!str_contains($format, 'H')) {
                    $date->setTime(0, 0, 0);
                }
                return $date;
            }
        }

        $timestamp = strtotime($dateStr);
        if ($timestamp !== false) {
            return (new \DateTime('now', $tz))->setTimestamp($timestamp);
        }

        return null;
    }

    /**
     * The timezone in which naive publication dates and "now" are compared.
     * Prefers an explicit instance timezone (NC system `logtimezone`), then the
     * current user's Nextcloud timezone (intranet ≈ org timezone), then the
     * server default. Consistent for logged-in users and anonymous visitors.
     */
    private function publicationTimezone(): \DateTimeZone {
        // 1. Admin-set instance timezone.
        $sys = (string)$this->config->getSystemValue('logtimezone', '');
        if ($sys !== '') {
            try { return new \DateTimeZone($sys); } catch (\Exception $e) {}
        }
        // 2. Current user's NC timezone (empty for anonymous share visitors).
        $uid = $this->userSession->getUser()?->getUID();
        if ($uid) {
            $userTz = (string)$this->config->getUserValue($uid, 'core', 'timezone', '');
            if ($userTz !== '') {
                try { return new \DateTimeZone($userTz); } catch (\Exception $e) {}
            }
        }
        // 3. Server default (PHP date.timezone).
        try {
            return new \DateTimeZone(date_default_timezone_get());
        } catch (\Exception $e) {
            return new \DateTimeZone('UTC');
        }
    }

    /**
     * The date-only parser used by the MetaVox filter operators moved to
     * NewsPageService with matchesFilter(), its only caller. The publication
     * paths here use the time-aware parseDateTime() above instead.
     */

    /**
     * Public batch accessor for MetaVox fields, so list-context callers (page
     * loading, tree, search) can fetch once and hand per-page metadata to
     * effectivePublishState()/isHiddenFromReaders() — avoiding an N+1 query.
     * Returns [] when scheduling is not configured or MetaVox is unavailable.
     *
     * @param int[] $fileIds
     * @return array<int, array<string, string>> fileId => [fieldName => value]
     */
    public function publicationMetaForFiles(array $fileIds): array {
        $publishField = $this->publicationSettings->getPublishDateField();
        $expireField = $this->publicationSettings->getExpirationDateField();
        if ((empty($publishField) && empty($expireField)) || empty($fileIds)) {
            return [];
        }
        return $this->getMetaVoxDataForFiles(array_values(array_filter($fileIds)));
    }

    /**
     * Get MetaVox metadata for multiple files
     *
     * @param array $fileIds Array of file IDs
     * @return array Associative array: fileId => [fieldName => value, ...]
     */
    private function getMetaVoxDataForFiles(array $fileIds): array {
        if (empty($fileIds) || !$this->isMetaVoxAvailable()) {
            return [];
        }

        try {
            // Query the metavox_file_gf_meta table directly
            $qb = $this->db->getQueryBuilder();
            $qb->select('file_id', 'field_name', 'field_value', 'groupfolder_id')
                ->from('metavox_file_gf_meta')
                ->where($qb->expr()->in('file_id', $qb->createNamedParameter($fileIds, \Doctrine\DBAL\Connection::PARAM_INT_ARRAY)));

            $result = $qb->executeQuery();
            $rows = $result->fetchAll();
            $result->closeCursor();

            // Organize by file ID. The shape stays field_name => value (callers
            // like applyMetaVoxFilters rely on it); the owning groupfolder is
            // recorded separately so per-field view permissions can be scoped.
            $metaData = [];
            foreach ($rows as $row) {
                $fileId = (int)$row['file_id'];
                $fieldName = $row['field_name'];
                $fieldValue = $row['field_value'];

                if (!isset($metaData[$fileId])) {
                    $metaData[$fileId] = [];
                }
                $metaData[$fileId][$fieldName] = $fieldValue;
                $this->metaVoxGroupfolderByFile[$fileId] = (int)$row['groupfolder_id'];
            }

            return $metaData;

        } catch (\Exception $e) {
            $this->logger->error('Failed to get MetaVox data', [
                'error' => $e->getMessage(),
                'fileIds' => $fileIds
            ]);
            return [];
        }
    }

    /**
     * Map field_name => field_label for MetaVox fields, so search sublines show
     * the human label ("Stad") rather than the raw column name ("stad").
     * Cached for the request; falls back to an empty map when MetaVox is absent,
     * in which case callers use the raw field name.
     *
     * @return array<string, string>
     */
    private function getMetaVoxFieldLabels(): array {
        if ($this->metaVoxFieldLabelsCache !== null) {
            return $this->metaVoxFieldLabelsCache;
        }

        $this->metaVoxFieldLabelsCache = [];

        if (!$this->isMetaVoxAvailable()) {
            return $this->metaVoxFieldLabelsCache;
        }

        try {
            $qb = $this->db->getQueryBuilder();
            $qb->select('field_name', 'field_label')
                ->from('metavox_gf_fields');

            $result = $qb->executeQuery();
            while ($row = $result->fetch()) {
                $this->metaVoxFieldLabelsCache[$row['field_name']] = $row['field_label'];
            }
            $result->closeCursor();
        } catch (\Exception $e) {
            $this->logger->warning('Failed to load MetaVox field labels', [
                'error' => $e->getMessage(),
            ]);
        }

        return $this->metaVoxFieldLabelsCache;
    }

    /**
     * Search a page's MetaVox metadata for the query and build a subline.
     *
     * The subline format deliberately mirrors MetaVox's own search provider
     * (MetadataSearchProvider::formatMetadataSubline): "Label: value" parts
     * joined with " • ", the matching field first, capped at three fields — so
     * the same document reads identically in both providers' results.
     *
     * Fields the user may not view are skipped, so a restricted MetaVox field
     * cannot leak through an IntraVox search result.
     *
     * @param array<string, mixed> $meta   field_name => value for one file
     * @param string $query                lowercased search term
     * @param array<string, string> $labels field_name => field_label
     * @param int|null $groupfolderId      folder owning the file, for permission scoping
     * @return array{subline: string}|null null when nothing matched
     */
    private function searchMetaVoxValues(array $meta, string $query, array $labels, ?int $groupfolderId = null): ?array {
        if (empty($meta) || $query === '') {
            return null;
        }

        $matching = [];
        $other = [];
        $found = false;

        foreach ($meta as $fieldName => $value) {
            // Multiselect values are stored JSON-encoded; flatten to a string so
            // both the match test and the subline read naturally.
            if (is_string($value) && str_starts_with($value, '[')) {
                $decoded = json_decode($value, true);
                if (is_array($decoded)) {
                    $value = implode(', ', array_filter($decoded, 'is_scalar'));
                }
            }
            if (!is_scalar($value)) {
                continue;
            }
            $value = (string)$value;
            if ($value === '') {
                continue;
            }
            if (!$this->canViewMetaVoxField($fieldName, $groupfolderId)) {
                continue;
            }

            $part = ($labels[$fieldName] ?? $fieldName) . ': ' . $value;
            if (mb_stripos($value, $query) !== false) {
                $matching[] = $part;
                $found = true;
            } else {
                $other[] = $part;
            }
        }

        if (!$found) {
            return null;
        }

        $parts = array_merge($matching, $other);
        return ['subline' => implode(' • ', array_slice($parts, 0, 3))];
    }

    /**
     * Whether the current user may view a MetaVox field. Delegates to MetaVox's
     * own PermissionService (resolved lazily — MetaVox is an optional app).
     *
     * On any failure we return false: hiding a field costs a subline entry,
     * showing one the user may not see would leak metadata.
     */
    private function canViewMetaVoxField(string $fieldName, ?int $groupfolderId = null): bool {
        $cacheKey = $fieldName . ':' . ($groupfolderId ?? 'null');
        if (isset($this->metaVoxFieldViewCache[$cacheKey])) {
            return $this->metaVoxFieldViewCache[$cacheKey];
        }

        $allowed = false;
        try {
            if ($this->userId !== '') {
                $permissionService = \OC::$server->get(\OCA\MetaVox\Service\PermissionService::class);
                $allowed = $permissionService->hasPermission(
                    $this->userId,
                    \OCA\MetaVox\Service\PermissionService::PERM_VIEW_METADATA,
                    $groupfolderId,
                    $fieldName
                );
            }
        } catch (\Throwable $e) {
            $allowed = false;
        }

        $this->metaVoxFieldViewCache[$cacheKey] = $allowed;
        return $allowed;
    }

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
            $result = $this->locatePageAnyLanguage($this->getReadLanguageFolder(), $uniqueId);
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
            $langFolder = $this->getLanguageFolder();
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
            $langFolder = $this->getLanguageFolder();
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
            $langFolder = $this->getLanguageFolder();
            [$templateId, $templateFolder, $templateMediaFolder] =
                $this->pageTemplateService->newTemplateFolder($langFolder, $this->sanitizeId($templateTitle));

            // Prepare template data
            $templateData = $pageData;
            $templateData['uniqueId'] = 'template-' . $this->generateUUID();
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
            $langFolder = $this->getLanguageFolder();
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
            $pageId = $this->sanitizeId($pageTitle);
            $pageData['id'] = $pageId;
            $pageData['title'] = $pageTitle;
            $pageData['uniqueId'] = 'page-' . $this->generateUUID();
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
            $templatesFolder = $this->pageTemplateService->templatesFolder($this->getLanguageFolder());
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
        $languageFolder = $this->getLanguageFolder();

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
        $baseTitle = $this->decodeHtmlEntitiesRecursive((string)($sourceData['title'] ?? 'Untitled'));
        $title = $newTitle !== null && $newTitle !== '' ? $newTitle : $baseTitle . ' (copy)';
        $pageData = $sourceData;
        unset($pageData['order']); // never inherit sibling order
        // A copy is a new page, not a translation of the source. Inheriting the
        // group made the copy a same-language member of it, which is the exact
        // state createTranslation() refuses to create because it makes the
        // language switcher ambiguous. createPage() assigns a fresh group.
        unset($pageData['translationGroup']);
        $pageData['id'] = $this->sanitizeId($title);
        $pageData['title'] = $title;
        $pageData['uniqueId'] = 'page-' . $this->generateUUID();
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
            $langFolder = $this->getLanguageFolder();
        } catch (\Exception $e) {
            return false;
        }
        return $this->pageTemplateService->canCreateTemplates($langFolder);
    }
}
