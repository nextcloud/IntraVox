<?php

declare(strict_types=1);

namespace OCA\IntraVox\Service\Folder;

use OCA\IntraVox\Service\Language\LanguageResolver;
use OCA\IntraVox\Service\LanguageService;
use OCA\IntraVox\Service\Locator\PageLocator;
use OCP\Files\File;
use OCP\Files\Folder;
use OCP\Files\IRootFolder;
use OCP\Files\NotFoundException;
use OCP\IConfig;
use OCP\IUserSession;

/**
 * The explicit folder/location substrate of IntraVox — the ground every page
 * domain stands on.
 *
 * PageService historically WAS this substrate: the three protected seams
 * (getIntraVoxFolder / getLanguageFolder / getReadLanguageFolder) plus the
 * language resolution and path helpers lived on it as protected/private methods
 * that ~11 collaborators reached back through as $this-bound closures and that
 * 27 test-subclasses overrode. That implicit shared surface WAS the entanglement.
 * FolderContext makes it one explicit, injectable object with a single front
 * door.
 *
 * DI-FIRST-CLASS: the substrate atoms are now real constructor dependencies
 * (rootFolder + userId + config + LanguageService + LanguageResolver +
 * PageLocator), so the DI container builds FolderContext directly and any
 * consumer can inject it. It owns BOTH the atoms (the mounted IntraVox mount
 * walk, the user's language, the #75 real-content probe) AND the composition on
 * top of them (create-on-miss language-folder resolution, the #75 effective-
 * language order, the path helpers).
 *
 * Two test-only seams remain as optional constructor params: an explicit
 * $intraVoxOverride folder (so a fixture injects a fake mount without wiring a
 * real user-folder walk) and the readLanguageFolder/languageFolder closures (so
 * the not-yet-migrated subclasses that override those seams wholesale keep
 * winning). Production/DI passes null for all three → the real mount walk + the
 * owned composition.
 *
 * The user id is deliberately NOT captured once: see userId(). Binding it at
 * construction is correct for HTTP and wrong for everything else, which is how
 * every occ command came to fail with "User not logged in".
 *
 * NEVER owns page lookup for mutation or the #70 permission decision. Its only
 * page-lookup touch is PageLocator::findPageByUniqueId inside the read-only #75
 * real-content probe.
 */
final class FolderContext {
    private const DEFAULT_LANGUAGE = 'en';

    private string $userId;

    /**
     * Per-scan memo of resolveLanguageHomepageData() results, keyed by the
     * language folder's path. One content-status scan reads the same home.json
     * up to FOUR times — hasHomepage + hasRealContent per language folder, PLUS
     * hasRealContent again from effectiveLanguage()'s candidate walk — each doing
     * a raw getContent()+json_decode. This memo collapses that to ONE read+decode
     * per folder (measured ~2/3 of the endpoint's cold cost).
     *
     * DELIBERATELY NOT a long-lived cache. It is null except while a scan is in
     * flight (beginHomepageProbeScan/endHomepageProbeScan bracket it), so it can
     * never carry a decoded homepage across a mutation within the same request —
     * the failure mode a FolderContext-resident, invalidator-gated cache would
     * have, because the mutation callers that matter invalidate with a non-null
     * pageId and skip the request-cache clear. Values are ?array; a resolved-null
     * homepage is stored as the false sentinel so "resolved to nothing" is not
     * re-read as "not memoised yet".
     *
     * @var array<string, array|false>|null
     */
    private ?array $homepageProbeMemo = null;

    /**
     * @param ?string $userId the current user (null/'' = logged out). Captured at
     *   CONSTRUCTION time, which is too early for any non-HTTP entry point: an occ
     *   command calls IUserSession::setUser() in execute(), long after the DI
     *   container built this object, so the value would be '' forever and every
     *   folder lookup would throw "User not logged in". Pass $userSession as well
     *   and the empty case is re-resolved from the session on each call instead.
     * @param ?IUserSession $userSession late-binding fallback for exactly that
     *   case. Only consulted when $userId is empty, so an explicitly supplied user
     *   (tests, and any caller acting on someone else's behalf) still wins.
     * @param ?Folder $intraVoxOverride test seam: an explicit mount folder that
     *   short-circuits the real user-folder walk. Null in production/DI.
     * @param ?\Closure $readLanguageFolder getReadLanguageFolder seam. FolderContext
     *   owns the SAME composition (readLanguageFolderComposed), but this seam is
     *   honoured when supplied so the subclasses that override getReadLanguageFolder
     *   WHOLESALE keep winning. Null = owned composition.
     * @param ?\Closure $languageFolder getLanguageFolder seam. Same story
     *   (languageFolderComposed). Null = owned composition.
     */
    public function __construct(
        private IRootFolder $rootFolder,
        ?string $userId,
        private IConfig $config,
        private LanguageService $languageService,
        private LanguageResolver $language,
        private PageLocator $locator,
        private ?Folder $intraVoxOverride = null,
        private ?\Closure $readLanguageFolder = null,
        private ?\Closure $languageFolder = null,
        private ?IUserSession $userSession = null,
    ) {
        $this->userId = $userId ?? '';
    }

    /**
     * The current user id, resolved as late as possible.
     *
     * The constructor value wins when it is set — that is the HTTP path, and it is
     * also how a caller acts on behalf of a specific user. Only when it is empty do
     * we ask the session, which is what makes occ commands work: they log a user in
     * during execute(), after this object already exists.
     */
    private function userId(): string {
        if ($this->userId !== '') {
            return $this->userId;
        }
        return $this->userSession?->getUser()?->getUID() ?? '';
    }

    /**
     * The mounted IntraVox folder (formerly the getIntraVoxFolder seam). Uses the
     * user's mounted folder view so GroupFolder ACLs apply; throws "not logged in"
     * without a user, and a specific "folder not found" when the mount is missing
     * or is not a folder. A test $intraVoxOverride short-circuits the walk.
     */
    public function intraVox(): Folder {
        // The override MUST come first — before the userId guard — so a fixture
        // with an empty userId but an explicit mount returns the fake rather than
        // throwing "not logged in" (matching the old injected seam closure that
        // ignored userId entirely).
        if ($this->intraVoxOverride !== null) {
            return $this->intraVoxOverride;
        }
        $userId = $this->userId();
        if (!$userId) {
            throw new \Exception('User not logged in');
        }
        $userFolder = $this->rootFolder->getUserFolder($userId);
        try {
            $node = $userFolder->get('IntraVox');
        } catch (NotFoundException $e) {
            throw new \Exception('IntraVox folder not found. Please check that you have access to the IntraVox GroupFolder.');
        }
        if (!$node instanceof Folder) {
            throw new \Exception('IntraVox folder not found. Please check that you have access to the IntraVox GroupFolder.');
        }
        return $node;
    }

    /** The current user's base language code (formerly the getUserLanguage seam). */
    public function userLanguage(): string {
        $userId = $this->userId();
        if (!$userId) {
            return self::DEFAULT_LANGUAGE;
        }
        $lang = $this->config->getUserValue($userId, 'core', 'lang', self::DEFAULT_LANGUAGE);
        // Base-code extraction + malformed-value guard (Phase 9: LanguageResolver).
        return $this->language->baseLanguageCode($lang);
    }

    /**
     * The write-target language folder for the current user, creating the
     * language (or default) folder on miss. Honours the getLanguageFolder seam
     * closure when supplied (so wholesale subclass overrides win); otherwise runs
     * the owned composition.
     */
    public function languageFolder() {
        if ($this->languageFolder !== null) {
            return ($this->languageFolder)();
        }
        return $this->languageFolderComposed();
    }

    /**
     * The create-on-miss write-target composition, owned here; verbatim from
     * PageService::getLanguageFolder(). Split out so languageFolder() can prefer an
     * injected seam without duplicating the body.
     */
    private function languageFolderComposed() {
        $baseFolder = $this->intraVox();
        $lang = $this->userLanguage();

        try {
            return $baseFolder->get($lang);
        } catch (NotFoundException $e) {
            if ($lang !== self::DEFAULT_LANGUAGE) {
                try {
                    return $baseFolder->get(self::DEFAULT_LANGUAGE);
                } catch (NotFoundException $e2) {
                    return $baseFolder->newFolder(self::DEFAULT_LANGUAGE);
                }
            }
            return $baseFolder->newFolder($lang);
        }
    }

    /**
     * The language a user is actually SHOWN (recommended-language fallback #75),
     * or null when nothing serveable. Composition owned here; verbatim from
     * PageService::resolveEffectiveLanguage().
     */
    public function effectiveLanguage(): ?string {
        $candidates = $this->language->candidateOrder(
            $this->userLanguage(),
            $this->languageService->getPrimaryLanguage()
        );

        $baseFolder = $this->intraVox();
        foreach ($candidates as $code) {
            try {
                $folder = $baseFolder->get($code);
            } catch (NotFoundException $e) {
                continue;
            }
            if ($folder instanceof Folder && $this->hasRealContent($folder)) {
                return $code;
            }
        }
        return null;
    }

    /**
     * The content folder for READING for the current user (#75 own -> recommended
     * -> en), falling back to the write-target. Honours the getReadLanguageFolder
     * seam closure when supplied (so wholesale subclass overrides win); otherwise
     * runs the owned composition.
     */
    public function readLanguageFolder(): Folder {
        if ($this->readLanguageFolder !== null) {
            return ($this->readLanguageFolder)();
        }
        return $this->readLanguageFolderComposed();
    }

    /**
     * The #75 read-folder composition, owned here; verbatim from
     * PageService::getReadLanguageFolder(). Split out so readLanguageFolder() can
     * prefer an injected seam without duplicating the body.
     */
    private function readLanguageFolderComposed(): Folder {
        $lang = $this->effectiveLanguage();
        if ($lang !== null) {
            try {
                $folder = $this->intraVox()->get($lang);
                if ($folder instanceof Folder) {
                    return $folder;
                }
            } catch (NotFoundException $e) {
                // fall through to the write-target folder
            }
        }
        return $this->languageFolder();
    }

    /**
     * The language folder for a given code, creating the code (or default) folder
     * on miss. Composition owned here; verbatim from
     * PageService::getLanguageFolderByCode().
     */
    public function languageFolderByCode(string $lang) {
        $baseFolder = $this->intraVox();

        try {
            return $baseFolder->get($lang);
        } catch (NotFoundException $e) {
            if ($lang !== self::DEFAULT_LANGUAGE) {
                try {
                    return $baseFolder->get(self::DEFAULT_LANGUAGE);
                } catch (NotFoundException $e2) {
                    return $baseFolder->newFolder(self::DEFAULT_LANGUAGE);
                }
            }
            return $baseFolder->newFolder($lang);
        }
    }

    /**
     * Which language content folder $folder sits in, or null. Verbatim from
     * PageService::languageOfFolder() (delegates to PageLocator with the root).
     */
    public function languageOfFolder(Folder $folder): ?string {
        return $this->locator->languageOfFolder($this->intraVox(), $folder);
    }

    /**
     * The IntraVox-root-relative path of $folder. Verbatim from
     * PageService::getRelativePathFromRoot().
     */
    public function relativePathFromRoot($folder): string {
        return $this->locator->relativePathFromRoot($this->intraVox(), $folder);
    }

    /**
     * Whether a language folder holds a REAL (editor-authored) homepage, as
     * opposed to an auto-generated placeholder or no homepage at all — the #75
     * real-content probe (formerly PageService::languageFolderHasRealContent,
     * injected as the hasRealContent closure). Public because getLanguageContentStatus
     * consumes it.
     *
     * A homepage counts as real when it exists, parses, and does NOT carry the
     * `_generated` marker written by LanguageHomepageService / demo-data.
     */
    public function hasRealContent(Folder $langFolder): bool {
        $data = $this->resolveLanguageHomepageData($langFolder);
        if ($data === null) {
            return false;
        }
        return empty($data['_generated']);
    }

    /**
     * Whether a language folder has a homepage AT ALL — real OR an auto/placeholder
     * one (`_generated`). The "active language" signal (formerly
     * PageService::languageFolderHasHomepage). Public because getLanguageContentStatus
     * consumes it.
     */
    public function hasHomepage(Folder $langFolder): bool {
        return $this->resolveLanguageHomepageData($langFolder) !== null;
    }

    /**
     * Resolve the homepage JSON for a language folder regardless of storage form
     * (configurable homepage). Verbatim from PageService::resolveLanguageHomepageData.
     * Checks, in order: (1) a `homepage.json` pointer -> the designated page's JSON;
     * (2) the legacy loose `home.json`; (3) a normalized `home/home.json` folder page.
     * Read-only; the only page-lookup touch (findPageByUniqueId) is via PageLocator.
     */
    private function resolveLanguageHomepageData(Folder $langFolder): ?array {
        // Serve from the per-scan memo when a content-status scan is in flight.
        // Outside a scan ($homepageProbeMemo === null) this is a straight read,
        // so nothing is cached beyond the bracketed scan.
        if ($this->homepageProbeMemo !== null) {
            $memoKey = $langFolder->getPath();
            if (array_key_exists($memoKey, $this->homepageProbeMemo)) {
                $hit = $this->homepageProbeMemo[$memoKey];
                return $hit === false ? null : $hit;
            }
            $data = $this->resolveLanguageHomepageDataUncached($langFolder);
            $this->homepageProbeMemo[$memoKey] = $data ?? false;
            return $data;
        }
        return $this->resolveLanguageHomepageDataUncached($langFolder);
    }

    /**
     * Begin a content-status probe scan: enable the per-scan homepage memo so the
     * hasHomepage/hasRealContent/effectiveLanguage reads of the same home.json
     * share one decode. MUST be paired with endHomepageProbeScan() in a finally so
     * the memo never outlives the scan. Idempotent-safe: a nested begin keeps the
     * outer scan's memo rather than dropping warmed entries.
     */
    public function beginHomepageProbeScan(): void {
        if ($this->homepageProbeMemo === null) {
            $this->homepageProbeMemo = [];
        }
    }

    /** End the scan and discard the per-scan homepage memo. */
    public function endHomepageProbeScan(): void {
        $this->homepageProbeMemo = null;
    }

    /**
     * The raw resolve, unmemoised. Verbatim from PageService::resolveLanguage-
     * HomepageData; resolveLanguageHomepageData() wraps it with the per-scan memo.
     */
    private function resolveLanguageHomepageDataUncached(Folder $langFolder): ?array {
        // 1. Pointer.
        try {
            $pointerFile = $langFolder->nodeExists('homepage.json') ? $langFolder->get('homepage.json') : null;
            if ($pointerFile instanceof File) {
                $ptr = json_decode($pointerFile->getContent(), true);
                $uid = is_array($ptr) ? ($ptr['homepageUniqueId'] ?? null) : null;
                if (is_string($uid) && $uid !== '') {
                    $target = $this->locator->findPageByUniqueId($langFolder, $uid);
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
                $homeFile = $langFolder->get('home.json');
                if ($homeFile instanceof File) {
                    $data = json_decode($homeFile->getContent(), true);
                    if (is_array($data) && isset($data['title'])) {
                        return $data;
                    }
                }
            }
        } catch (\Throwable $e) {
            // Fall through.
        }

        // 3. Normalized home/home.json.
        try {
            if ($langFolder->nodeExists('home')) {
                $homeFolder = $langFolder->get('home');
                if ($homeFolder instanceof Folder && $homeFolder->nodeExists('home.json')) {
                    $inner = $homeFolder->get('home.json');
                    if ($inner instanceof File) {
                        $data = json_decode($inner->getContent(), true);
                        if (is_array($data) && isset($data['title'])) {
                            return $data;
                        }
                    }
                }
            }
        } catch (\Throwable $e) {
            // Fall through.
        }

        return null;
    }
}
