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
    // COMMIT 0 (fase-10): the facade-free fixture helpers moved into these
    // per-domain traits so they outlive PageService; composed here so every
    // existing consumer keeps seeing them transitively. Only the
    // PageService-coupled machinery below (buildRealPageService et al.) stays,
    // to be deleted WITH PageService in the final fase-10 commit.
    use BuildsNodeFixtures;
    use BuildsFolderFixtures;
    use BuildsCacheFixtures;
    use BuildsServiceDoubles;
    use BuildsCollaboratorFixtures;

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

        // The ctor filled pageLister/homepageResolver with throw-away doubles (every
        // ctor arg is required non-null). Unset the ones the caller did not explicitly
        // wire so wirePromotedServices' $already() guard sees them as unwired and
        // rebuilds them coherently — and, crucially, so a 'home' request rigs the
        // homepage resolver instead of being blocked by the ctor's mock. A caller that
        // passed the property by name keeps its exact value.
        foreach (['pageLister', 'homepageResolver'] as $promoted) {
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
        // A caller can pass a promoted service by its PROPERTY name (pageLister /
        // homepageResolver) — but the ctor-fill above matches by ctor-PARAM name
        // (pageListerService / homepageResolverService), so such a value never reached
        // the ctor. Reflection-set it here so it is honoured (mirrors what
        // injectPageServiceDependencies does), then the $already guard sees it wired.
        foreach (['pageLister', 'homepageResolver'] as $promoted) {
            if (isset($explicit[$promoted]) && is_object($explicit[$promoted])) {
                $set($promoted, $explicit[$promoted]);
            }
        }
        // A property already reflection-set by the caller (e.g. a test that rigged
        // its own homepageResolver) counts as "wired" too.
        $already = fn(string $p) => (new \ReflectionProperty(PageService::class, $p))->isInitialized($svc)
            || array_key_exists($p, $explicit);

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

        // NewsWidgetService is no longer a PageService dep (fase-5 Phase II C moved
        // getNewsPages entirely to the ApiController→NewsWidgetService seam), so it is
        // NOT wired onto PageService here. A News test builds it directly via
        // buildNewsWidget().

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
            // The two fase-5 promoted services still on PageService are built
            // coherently by wirePromotedServices() at the end of this method (over the
            // test's real cache/folderContext), never as a bare mock here — leaving
            // them unset so that builder's $already() guard sees them as unwired and
            // constructs them. (newsWidget left PageService in Phase II C.)
            if (in_array($prop->getName(), ['pageLister', 'homepageResolver'], true)) {
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

        // The two fase-5 promoted services still on PageService (pageLister /
        // homepageResolver) are ordinary non-lazy ctor deps, so the auto-fill loop
        // above would leave them as bare doubleOrBuild()s built over a MOCKED
        // FolderContext/cache — which answers null to every folder/parent lookup and
        // silently breaks getBreadcrumb → findPageByFolderPath → pageLister->byFolderPath.
        // Rebuild them coherently from the test's explicit deps ($cache/$folderContext),
        // skipping any the caller reflection-set (the $already guard inside). Passing
        // (null, false) means "no rigged homepage" — the real resolver body runs.
        // (newsWidget left PageService in Phase II C; News tests build it directly.)
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
