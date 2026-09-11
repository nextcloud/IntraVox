<?php
declare(strict_types=1);

namespace OCA\IntraVox\Tests\Unit\Service\Harness;

use OCA\IntraVox\Service\Cache\PageCacheInvalidator;
use OCA\IntraVox\Service\Cache\PageCacheService;
use OCA\IntraVox\Service\Folder\FolderContext;
use OCA\IntraVox\Service\Language\LanguageResolver;
use OCA\IntraVox\Service\Locator\PageLocator;
use OCA\IntraVox\Service\PermissionService;
use OCA\IntraVox\Service\Media\PageMediaService;
use OCA\IntraVox\Service\PageService;
use OCA\IntraVox\Service\Translation\TranslationGroupService;
use OCP\Files\File;
use OCP\Files\FileInfo;
use OCP\Files\Folder;
use OCP\Files\NotFoundException;
use Psr\Log\LoggerInterface;

/**
 * Shared harness for the PageService characterization tests.
 *
 * Ten unit test files (and one benchmark) grew byte-identical copies of the same
 * three primitives: makeFile()/makeFolder() fixture builders, doubleOrBuild() for
 * the final leaf sanitizers, and the reflection auto-fill that populates a
 * seam-overriding anonymous PageService subclass while leaving the lazy-seam
 * services unset. This trait is the single home for that pattern so the refactor
 * that decomposes PageService can add a new service by editing ONE list
 * (LAZY_SEAM_SERVICES) instead of nine files.
 *
 * The trait deliberately reproduces the existing behaviour verbatim — it is a
 * consolidation, not a redesign. Consumers still build their own anonymous
 * subclass (each overrides a different set of seams and needs different explicit
 * collaborators); the trait only owns the parts that were identical everywhere.
 *
 * Requires the host to be a PHPUnit\Framework\TestCase (uses createMock()).
 */
trait BuildsPageService {

    /**
     * The PageService properties whose types are lazy-seam services: they must be
     * left UNSET so PageService's own lazy accessors build the real collaborator
     * from pageIndexService + logger, reproducing the pre-split inline behaviour.
     * An auto-mock here would answer null to every lookup and silently break the
     * page-location / translation / media / news paths.
     *
     * As the refactor extracts new lazy-only services, add their class here.
     */
    protected const LAZY_SEAM_SERVICES = [
        PageLocator::class,
        TranslationGroupService::class,
        PageMediaService::class,
    ];

    /**
     * A File whose getContent() returns the given page JSON. getId() is a stable
     * hash of the path so fixtures that look a page up by file id are consistent.
     */
    protected function makeFile(string $path, array $json): File {
        $file = $this->createMock(File::class);
        $file->method('getName')->willReturn(basename($path));
        $file->method('getType')->willReturn(FileInfo::TYPE_FILE);
        $file->method('getPath')->willReturn($path);
        $file->method('getContent')->willReturn(json_encode($json));
        $file->method('getId')->willReturn(abs(crc32($path)));
        return $file;
    }

    /**
     * A Folder that records move() into $this->moves and can be made read-only /
     * non-creatable / non-deletable so permission preflights are testable.
     *
     * The host class must declare `private array $moves = [];` if it asserts on
     * moves; makeFolder() only writes to it when move() is actually called.
     *
     * @param array<string,\OCP\Files\Node> $children name => node
     */
    protected function makeFolder(
        string $path,
        array $children = [],
        bool $deletable = true,
        bool $creatable = true
    ): Folder {
        $folder = $this->createMock(Folder::class);
        $folder->method('getName')->willReturn(basename($path));
        $folder->method('getType')->willReturn(FileInfo::TYPE_FOLDER);
        $folder->method('getPath')->willReturn($path);
        $folder->method('getDirectoryListing')->willReturn(array_values($children));
        $folder->method('isDeletable')->willReturn($deletable);
        $folder->method('isCreatable')->willReturn($creatable);
        $folder->method('isUpdateable')->willReturn(true);
        $folder->method('nodeExists')->willReturnCallback(
            fn($n) => isset($children[$n])
        );
        $folder->method('get')->willReturnCallback(function ($p) use ($children, $path) {
            if (isset($children[$p])) {
                return $children[$p];
            }
            throw new NotFoundException($path . '/' . $p);
        });
        $folder->method('move')->willReturnCallback(function ($dest) use ($path) {
            if (property_exists($this, 'moves')) {
                $this->moves[$path] = $dest;
            }
        });
        return $folder;
    }

    /**
     * Build a real FolderContext wired for a test, so a migrating subclass can
     * inject it directly (folderContext: ...) instead of overriding the three
     * protected folder seams to smuggle a fake folder in.
     *
     * FolderContext is final and closure-driven, so the "fake" IS a real one
     * whose seam closures return the given fixtures — identical in spirit to how
     * FolderContextSeamTest constructs it. Once folderContext is set on a
     * PageService, folders() returns it verbatim (??=) and never rebuilds from
     * the seams, so this is the injection point that lets the seam overrides
     * finally go away (clean-target step 8).
     *
     * @param Folder|null $readLanguageFolder what readLanguageFolder() returns.
     *   When given it is injected as the getReadLanguageFolder seam closure so it
     *   wins wholesale (matches production). Null -> the owned composition runs.
     * @param Folder|null $intraVox base folder for intraVox()/relativePathFromRoot;
     *   defaults to $readLanguageFolder when omitted.
     * @param string $userLanguage what userLanguage() returns.
     * @param string $primaryLanguage the primary-language seam.
     */
    protected function fakeFolderContext(
        ?Folder $readLanguageFolder = null,
        ?Folder $intraVox = null,
        string $userLanguage = 'en',
        string $primaryLanguage = 'en',
        ?Folder $languageFolder = null
    ): FolderContext {
        $base = $intraVox ?? $readLanguageFolder;
        $resolver = new LanguageResolver();
        $locator = new PageLocator(
            $this->createMock(\OCA\IntraVox\Service\PageIndexService::class),
            $this->createMock(LoggerInterface::class)
        );

        // FolderContext is DI-first-class now; the fixture supplies the substrate
        // atoms as real deps. $base is passed as the intraVoxOverride so the mount
        // walk (rootFolder->getUserFolder(userId)->get('IntraVox')) is never run —
        // fixtures inject the fake mount directly. config->getUserValue returns
        // $userLanguage (a base code, so baseLanguageCode() is idempotent) and the
        // LanguageService mock returns $primaryLanguage. The #75 real-content probe
        // now runs FolderContext's own resolveLanguageHomepageData over $locator,
        // which the loose-home.json fixtures satisfy via its form-2 branch.
        $config = $this->createMock(\OCP\IConfig::class);
        $config->method('getUserValue')->willReturn($userLanguage);
        $languageService = $this->createMock(\OCA\IntraVox\Service\LanguageService::class);
        $languageService->method('getPrimaryLanguage')->willReturn($primaryLanguage);

        return new FolderContext(
            $this->createMock(\OCP\Files\IRootFolder::class), // unused: $base override short-circuits the mount walk
            'test-user',                                       // non-empty so userLanguage() reads config, not the logged-out 'en'
            $config,
            $languageService,
            $resolver,
            $locator,
            $base, // intraVoxOverride — the fake mount
            // getReadLanguageFolder seam: wired when a read folder is given, so a
            // wholesale override is reproduced. Null -> owned composition.
            $readLanguageFolder === null
                ? null
                : fn(): Folder => $readLanguageFolder,
            // getLanguageFolder seam: wired when a write-target folder is given.
            // Null -> owned create-on-miss composition (intraVox->get(userLang)).
            $languageFolder === null
                ? null
                : fn(): Folder => $languageFolder
        );
    }

    /**
     * A real (final) FolderContext whose readLanguageFolder() THROWS the given
     * exception — the getReadLanguageFolder seam closure re-throws, so any body that
     * opens with `$this->folders->readLanguageFolder()` surfaces it verbatim. Lets a
     * fixture reproduce the "IntraVox folder not found" / read-error branch without a
     * subclass. All other substrate atoms are inert (the read seam fires first).
     */
    protected function fakeFolderContextThrowingRead(\Throwable $e): FolderContext {
        $config = $this->createMock(\OCP\IConfig::class);
        $config->method('getUserValue')->willReturn('en');
        $languageService = $this->createMock(\OCA\IntraVox\Service\LanguageService::class);
        $languageService->method('getPrimaryLanguage')->willReturn('en');

        return new FolderContext(
            $this->createMock(\OCP\Files\IRootFolder::class),
            'test-user',
            $config,
            $languageService,
            new LanguageResolver(),
            new PageLocator(
                $this->createMock(\OCA\IntraVox\Service\PageIndexService::class),
                $this->createMock(LoggerInterface::class)
            ),
            null,
            static function () use ($e): Folder { throw $e; }
        );
    }

    /**
     * Construct a REAL PageService through its real DI constructor — the seam-free
     * replacement for `new class extends PageService { ctor-bypass + shadows }`
     * (fase-3). Every ctor param is filled reflectively (robust against ctor drift):
     * from $explicit by param name when given, else fakeFolderContext /
     * fakeCacheInvalidator for the substrate deps, else doubleOrBuild for the rest;
     * ?string $userId defaults to 'tester'.
     *
     * Recognised non-ctor keys in $explicit:
     *   'home'     => ?string — the homepage uniqueId; reflection-sets a
     *                 fakeHomepageResolver so isHomepage(uid) === (uid === home).
     *                 Omit the key to leave the real resolver (built lazily from the
     *                 wired homepageService/folderContext, as before).
     *   'cacheSpy' => PageCacheService — a spy cache for the invalidator (position
     *                 tests), used only when 'cacheInvalidator' is not given.
     *
     * @param array<string,mixed> $explicit ctor-param-name => value, plus the keys above
     */
    protected function buildRealPageService(array $explicit = []): PageService {
        $home = $explicit['home'] ?? null;
        $hasHome = array_key_exists('home', $explicit);
        $cacheSpy = $explicit['cacheSpy'] ?? null;
        unset($explicit['home'], $explicit['cacheSpy']);

        // The ctor requires every param non-null, but the 4 lazy-seam services get
        // unset again below so their accessor rebuilds them lazily — so a mock for
        // the ctor arg is fine (it is thrown away). FolderContext / PageCacheInvalidator
        // get the real fixtures because they are NOT lazy-seams (they stay).
        $ctor = (new \ReflectionClass(PageService::class))->getConstructor();
        $args = [];
        foreach ($ctor->getParameters() as $param) {
            $name = $param->getName();
            if (array_key_exists($name, $explicit)) {
                $args[] = $explicit[$name];
                continue;
            }
            if ($name === 'userId') {
                $args[] = 'tester';
                continue;
            }
            $type = $param->getType();
            if ($type instanceof \ReflectionNamedType && !$type->isBuiltin()) {
                $class = $type->getName();
                if ($class === FolderContext::class) {
                    $args[] = $this->fakeFolderContext();
                } elseif ($class === PageCacheInvalidator::class) {
                    $args[] = $this->fakeCacheInvalidator($cacheSpy);
                } else {
                    $args[] = $this->doubleOrBuild($class);
                }
                continue;
            }
            // Any other builtin/nullable scalar: its default (none exist besides userId).
            $args[] = $param->isDefaultValueAvailable() ? $param->getDefaultValue() : null;
        }

        $svc = new PageService(...$args);

        // The 4 lazy-seam services were passed to the ctor as real instances (the
        // ctor requires them non-null), but the old ctor-bypass subclasses left them
        // UNSET so their lazy accessor (locator()/translationGroups()/media()/news())
        // rebuilds from the CURRENT pageIndexService/logger on first use — which
        // matters when a test reflection-sets a fresh pageIndexService after
        // construction. Reproduce that: unset each lazy-seam property the caller did
        // not explicitly wire, so isset()===false and the accessor rebuilds lazily.
        $unset = \Closure::bind(function (string $prop): void {
            unset($this->{$prop});
        }, $svc, PageService::class);
        foreach ((new \ReflectionClass(PageService::class))->getProperties() as $prop) {
            $t = $prop->getType();
            if ($t instanceof \ReflectionNamedType
                && in_array($t->getName(), self::LAZY_SEAM_SERVICES, true)
                && !array_key_exists($prop->getName(), $explicit)) {
                $unset($prop->getName());
            }
        }

        // The ctor filled pageLister/newsWidget/homepageResolver with throw-away
        // doubles (every ctor arg is required non-null). Unset the ones the caller
        // did not explicitly wire so wirePromotedServices' $already() guard sees them
        // as unwired and rebuilds them coherently — and, crucially, so a 'home'
        // request rigs the homepage resolver instead of being blocked by the ctor's
        // mock. A caller that passed the property by name keeps its exact value.
        foreach (['pageLister', 'newsWidget', 'homepageResolver'] as $promoted) {
            if (!array_key_exists($promoted, $explicit)) {
                $unset($promoted);
            }
        }

        $this->wirePromotedServices($svc, $explicit, $home, $hasHome);
        return $svc;
    }

    /**
     * Wire the three fase-5-promoted DI services (PageLister / NewsWidgetService /
     * HomepageResolverService) onto $svc, built from the SAME explicit deps the test
     * passed so their bodies walk the fixture (a doubled leaf would carry a
     * disconnected mock index / null folders). Each is skipped when the caller wired
     * it explicitly. Shared by buildRealPageService (real DI ctor) and
     * fillPageServiceDependencies (constructor-less subclasses).
     *
     * @param array<string,mixed> $explicit
     */
    private function wirePromotedServices(PageService $svc, array $explicit, ?string $home, bool $hasHome): void {
        $folders = ($explicit['folderContext'] ?? null) instanceof FolderContext
            ? $explicit['folderContext']
            : $this->fakeFolderContext();
        $logger = $explicit['logger'] ?? $this->createMock(LoggerInterface::class);
        $index = $explicit['pageIndexService'] ?? $this->createMock(\OCA\IntraVox\Service\PageIndexService::class);
        $cache = $explicit['cache'] ?? $this->createMock(PageCacheService::class);
        $set = fn(string $p, object $v) => (new \ReflectionProperty(PageService::class, $p))->setValue($svc, $v);
        // A property already reflection-set by the caller (e.g. a test that rigged
        // its own homepageResolver) counts as "wired" too.
        $already = fn(string $p) => array_key_exists($p, $explicit)
            || (new \ReflectionProperty(PageService::class, $p))->isInitialized($svc);

        if (!$already('pageLister')) {
            $set('pageLister', new \OCA\IntraVox\Service\Listing\PageLister(
                new PageLocator($index, $logger),
                $index,
                $explicit['permissionService'] ?? $this->createMock(PermissionService::class),
                $logger,
                $folders,
                $this->doubleOrBuild(\OCA\IntraVox\Service\Sanitize\PageShapeSanitizer::class),
                $cache,
                $this->doubleOrBuild(\OCA\IntraVox\Service\Path\PageDataEnricher::class),
            ));
        }

        if (!$already('newsWidget')) {
            $set('newsWidget', new \OCA\IntraVox\Service\News\NewsWidgetService(
                $explicit['newsPageService'] ?? $this->doubleOrBuild(\OCA\IntraVox\Service\News\NewsPageService::class),
                $cache,
                $explicit['groupContext'] ?? $this->doubleOrBuild(\OCA\IntraVox\Service\GroupContextService::class),
                $explicit['metaVoxGateway'] ?? $this->doubleOrBuild(\OCA\IntraVox\Service\Publication\MetaVoxGateway::class),
                $this->doubleOrBuild(\OCA\IntraVox\Service\Publication\PublicationStateService::class),
                $folders,
                $logger,
                new PageLocator($index, $logger),
            ));
        }

        // When 'home' is passed, the resolver is rigged to a fixed isHomepage
        // predicate; otherwise built over the wired homepageService + folders so its
        // real body runs against the fixture. Skipped if wired explicitly.
        if ($hasHome && !$already('homepageResolver')) {
            $set('homepageResolver', $this->fakeHomepageResolver(
                $home,
                ($explicit['folderContext'] ?? null) instanceof FolderContext ? $explicit['folderContext'] : null
            ));
        } elseif (!$already('homepageResolver')) {
            $set('homepageResolver', new \OCA\IntraVox\Service\Homepage\HomepageResolverService(
                $explicit['homepageService'] ?? $this->createMock(\OCA\IntraVox\Service\HomepageService::class),
                $folders,
                new PageLocator($index, $logger),
                $this->fakeCacheInvalidator()
            ));
        }
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

    /**
     * A real (final) PageCacheInvalidator over inert doubles — the seam-free
     * replacement for the empty `clearCache()` overrides the subclasses used to
     * carry (fase-3). invalidate() over these mocks is a de-facto no-op: the
     * PageCacheService mock's clearExpensive() returns false (default), so the
     * static SystemFileService::clearStaticTreeCache() fan-out never fires and the
     * per-user cache resets hit inert mocks — exactly what an empty override gave.
     *
     * Pass $spy to observe the clearRequest() calls (position-checking tests):
     * wire it before handing it in, e.g. a mock whose clearRequest willReturnCallback
     * records the call, then fakeCacheInvalidator($spy).
     */
    protected function fakeCacheInvalidator(?PageCacheService $spy = null): PageCacheInvalidator {
        return new PageCacheInvalidator(
            $spy ?? $this->createMock(PageCacheService::class),
            $this->createMock(PageLocator::class),
            $this->createMock(PermissionService::class)
        );
    }

    /**
     * Mock $class, or — when it is final and therefore not doubleable — build a
     * real one and recurse for its own final dependencies (PageShapeSanitizer
     * takes three final leaf sanitizers).
     */
    protected function doubleOrBuild(string $class): object {
        try {
            return $this->createMock($class);
        } catch (\PHPUnit\Framework\MockObject\Generator\ClassIsFinalException $e) {
            $ctor = (new \ReflectionClass($class))->getConstructor();
            $args = [];
            foreach ($ctor?->getParameters() ?? [] as $param) {
                $pType = $param->getType();
                $name = $pType instanceof \ReflectionNamedType ? $pType->getName() : null;
                if ($name === \Closure::class) {
                    // A final service that keeps a \Closure ctor param (e.g. FolderContext's
                    // optional seam closures): take the default, else a no-op closure. These
                    // are never invoked on the paths a doubled leaf is reached through.
                    $args[] = $param->isDefaultValueAvailable()
                        ? $param->getDefaultValue()
                        : ($param->allowsNull() ? null : static function (): void {});
                } elseif ($name !== null && !$pType->isBuiltin()
                    && (class_exists($name) || interface_exists($name))) {
                    $args[] = $this->doubleOrBuild($name);
                } elseif ($param->isDefaultValueAvailable()) {
                    $args[] = $param->getDefaultValue();
                } else {
                    $args[] = null;
                }
            }
            return new $class(...$args);
        }
    }

    /**
     * Populate every not-yet-set, non-lazy-seam object property of a
     * seam-overriding PageService subclass with a mock (or built leaf).
     *
     * $explicit are the collaborators the caller has already set by name; they are
     * skipped here. Properties typed as a LAZY_SEAM_SERVICES class are left unset
     * on purpose (see the const docblock).
     *
     * @param array<string,mixed> $explicit property name => already-set value
     */
    protected function fillPageServiceDependencies(PageService $svc, array $explicit): void {
        foreach ((new \ReflectionClass(PageService::class))->getProperties() as $prop) {
            if ($prop->isStatic() || isset($explicit[$prop->getName()])) {
                continue;
            }
            $type = $prop->getType();
            if (!$type instanceof \ReflectionNamedType || $type->isBuiltin()) {
                continue;
            }
            if (in_array($type->getName(), self::LAZY_SEAM_SERVICES, true)) {
                continue;
            }
            // The three fase-5 promoted services are built coherently by
            // wirePromotedServices() at the end of this method (over the test's real
            // cache/folderContext), never as a bare mock here — leaving them unset so
            // that builder's $already() guard sees them as unwired and constructs them.
            if (in_array($prop->getName(), ['pageLister', 'newsWidget', 'homepageResolver'], true)) {
                continue;
            }
            if ($prop->isInitialized($svc)) {
                continue;
            }
            $class = $type->getName();
            // FolderContext is the DI-first-class substrate: doubleOrBuild would
            // choke on its \Closure ctor params. A test that does not wire it gets a
            // bare fixture context (no folders) — the same "left effectively real"
            // outcome the old nullable-default property gave.
            if ($class === FolderContext::class) {
                $prop->setValue($svc, $this->fakeFolderContext());
                continue;
            }
            if (!interface_exists($class) && !class_exists($class)) {
                continue;
            }
            $prop->setValue($svc, $this->doubleOrBuild($class));
        }

        // The three fase-5 promoted services (pageLister / newsWidget /
        // homepageResolver) are ordinary non-lazy ctor deps now, so the auto-fill
        // loop above would leave them as bare doubleOrBuild()s built over a MOCKED
        // FolderContext/cache — which answers null to every folder/parent lookup and
        // silently breaks getBreadcrumb → findPageByFolderPath → pageLister->byFolderPath.
        // Rebuild them coherently from the test's explicit deps ($cache/$folderContext),
        // skipping any the caller reflection-set (the $already guard inside). Passing
        // (null, false) means "no rigged homepage" — the real resolver body runs.
        $this->wirePromotedServices($svc, $explicit, null, false);
    }

    /**
     * Set the given collaborators on the subclass by property name (the "explicit"
     * set every test threads through), then auto-fill the rest.
     *
     * @param array<string,mixed> $explicit property name => value
     */
    protected function injectPageServiceDependencies(PageService $svc, array $explicit): void {
        foreach ($explicit as $name => $value) {
            // Tolerate a property a fase-5 promotion removed (e.g. config /
            // newsPageService / publicationSettings, once folded into DI-injected
            // domain services): a fixture that still names it is harmlessly ignored
            // rather than fatalling every consumer with ReflectionException.
            if (!property_exists(PageService::class, $name)) {
                continue;
            }
            (new \ReflectionProperty(PageService::class, $name))->setValue($svc, $value);
        }
        $this->fillPageServiceDependencies($svc, $explicit);
    }
}
