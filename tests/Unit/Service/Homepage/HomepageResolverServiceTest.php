<?php
declare(strict_types=1);

namespace OCA\IntraVox\Tests\Unit\Service\Homepage;

use OCA\IntraVox\Service\HomepageService;
use OCA\IntraVox\Service\Homepage\HomepageResolverService;
use OCA\IntraVox\Service\Locator\PageLocator;
use OCA\IntraVox\Tests\Unit\Service\Harness\BuildsCacheFixtures;
use OCA\IntraVox\Tests\Unit\Service\Harness\BuildsFolderFixtures;
use OCA\IntraVox\Tests\Unit\Service\Harness\BuildsNodeFixtures;
use OCP\Files\Folder;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * getHomepageUniqueId() must name a page the frontend can actually find.
 *
 * Two homepage layouts exist side by side: the normalised `home/home.json`
 * folder page, which has a real uniqueId, and the legacy loose `home.json` in
 * the language root. For the legacy form this returned the bare string 'home',
 * which matches no entry in listPages() — so
 * `pages.find(p => p.uniqueId === homepageUniqueId)` came up empty and the
 * reader fell through to a slug/path heuristic that ends at pages[0], the
 * alphabetically first page.
 *
 * On dev that put every Dutch reader on "API Referentie" instead of "Welkom bij
 * IntraVox", while English — which uses the normalised layout — was fine. The
 * two layouts coexisting is exactly what hid it.
 */
class HomepageResolverServiceTest extends TestCase {

    use BuildsNodeFixtures;
    use BuildsFolderFixtures;
    use BuildsCacheFixtures;

    /**
     * @param array|null $homeJson contents of nl/home.json, or null for none
     * @param string|null $pointer configured homepage pointer, if any
     */
    private function makeResolver(?array $homeJson, ?string $pointer = null): HomepageResolverService {
        $children = [
            'about.json' => $this->makeFile(
                '/IntraVox/nl/about.json',
                ['uniqueId' => 'page-about', 'title' => 'API Referentie']
            ),
        ];
        if ($homeJson !== null) {
            $children['home.json'] = $this->makeFile('/IntraVox/nl/home.json', $homeJson);
        }
        $nl = $this->makeFolder('/IntraVox/nl', $children);
        $base = $this->makeFolder('/IntraVox', ['nl' => $nl]);

        // getHomepageUniqueId resolves its folder purely through the injected
        // FolderContext (languageFolderByCode('nl') + the #75 effective-language
        // probe over the base folder), so a fakeFolderContext replaces the old
        // triple-seam override. userLanguage/primaryLanguage 'nl' mirror the config
        // + languageService this fixture wired; the real-content probe reads the
        // same nl/home.json.
        // NO 'home' key here: this builder tests the REAL homepage resolution
        // (getHomepageUniqueId / resolveHomepageNodeUniqueId), so the real resolver
        // must run — built directly from the wired homepageService mock + fakeFolder
        // context, exactly as before. The inert invalidator no-ops clearCache.
        $homepageService = $this->createMock(HomepageService::class);
        $homepageService->method('getHomepageUniqueId')->willReturn($pointer);

        $folders = $this->fakeFolderContext(
            intraVox: $base,
            userLanguage: 'nl',
            primaryLanguage: 'nl'
        );

        return new HomepageResolverService(
            $homepageService,
            $folders,
            new PageLocator(
                $this->createMock(\OCA\IntraVox\Service\PageIndexService::class),
                $this->createMock(LoggerInterface::class)
            ),
            $this->fakeCacheInvalidator()
        );
    }

    /**
     * Read the homepage uniqueId directly off the HomepageResolverService.
     * fase-5 Phase II deleted the getHomepageUniqueId facade delegator (it had
     * no production caller — ApiController resolves via resolveHomepageNodeUniqueId),
     * so these characterization tests reach the resolver directly, exactly what the
     * one-line delegator forwarded to.
     */
    private function homepageUniqueId(HomepageResolverService $resolver, string $lang): string {
        return $resolver->getHomepageUniqueId($lang);
    }

    /**
     * The regression: a legacy loose home.json must resolve to the uniqueId the
     * file really carries, so the frontend can match it against listPages().
     */
    public function testLooseHomeJsonResolvesToItsRealUniqueId(): void {
        $resolver = $this->makeResolver([
            'uniqueId' => 'page-nl-home',
            'title' => 'Welkom bij IntraVox',
        ]);

        $this->assertSame('page-nl-home', $this->homepageUniqueId($resolver, 'nl'));
    }

    /**
     * A home.json without a uniqueId keeps the legacy answer — that string is
     * what the rest of the legacy path still understands, and inventing an id
     * here would be worse than saying "the legacy default".
     */
    public function testHomeJsonWithoutUniqueIdKeepsTheLegacyAnswer(): void {
        $resolver = $this->makeResolver(['title' => 'Welcome']);

        $this->assertSame('home', $this->homepageUniqueId($resolver, 'nl'));
    }

    /** No loose home.json at all: unchanged legacy answer. */
    public function testMissingHomeJsonKeepsTheLegacyAnswer(): void {
        $resolver = $this->makeResolver(null);

        $this->assertSame('home', $this->homepageUniqueId($resolver, 'nl'));
    }

    /**
     * A configured pointer still wins over the loose file — this fix must not
     * quietly override an admin's explicit homepage choice.
     */
    public function testConfiguredPointerStillWins(): void {
        $resolver = $this->makeResolver(
            ['uniqueId' => 'page-nl-home', 'title' => 'Welkom'],
            'page-about'
        );

        $this->assertSame('page-about', $this->homepageUniqueId($resolver, 'nl'));
    }

    /**
     * A stale pointer (naming a page that no longer resolves) must NOT be
     * honoured — it falls through to the legacy loose-home resolution. The
     * fixture wires no page-about-shaped folder for the pointer, so
     * findPageByUniqueId(pointer) returns null and the loose home.json wins.
     */
    public function testStalePointerFallsThroughToLegacyHome(): void {
        // Pointer names a page that isn't the resolvable loose home; about.json
        // exists but the pointer 'page-ghost' resolves to nothing.
        $resolver = $this->makeResolver(
            ['uniqueId' => 'page-nl-home', 'title' => 'Welkom'],
            'page-ghost'
        );

        $this->assertSame(
            'page-nl-home',
            $this->homepageUniqueId($resolver, 'nl'),
            'a pointer that does not resolve must fall through to the loose home'
        );
    }

    // -------------------------------------------------- resolveHomepageNodeUniqueId

    /**
     * resolveHomepageNodeUniqueId maps the legacy bare 'home' to the real
     * uniqueId of the loose home.json (its own raw read, distinct from
     * getHomepageUniqueId's cached read).
     */
    public function testResolveHomepageNodeMapsHomeToLooseUniqueId(): void {
        $resolver = $this->makeResolver([
            'uniqueId' => 'page-nl-home',
            'title' => 'Welkom bij IntraVox',
        ]);

        $this->assertSame('page-nl-home', $resolver->resolveHomepageNodeUniqueId('nl'));
    }

    /**
     * When nothing resolves and a pre-built tree is supplied, the first root
     * node's uniqueId is the last resort.
     */
    public function testResolveHomepageNodeFallsBackToFirstTreeNode(): void {
        $resolver = $this->makeResolver(null);

        $tree = [
            ['uniqueId' => 'page-first', 'title' => 'First'],
            ['uniqueId' => 'page-second', 'title' => 'Second'],
        ];
        $this->assertSame('page-first', $resolver->resolveHomepageNodeUniqueId('nl', $tree));
    }

    /** No pointer, no home.json, no tree: the bare legacy 'home'. */
    public function testResolveHomepageNodeBareHomeWhenNothingResolves(): void {
        $resolver = $this->makeResolver(null);
        $this->assertSame('home', $resolver->resolveHomepageNodeUniqueId('nl'));
    }

    // ------------------------------------------------------------- setHomepage

    /**
     * A page folder `{name}/` under nl/ holding `{name}.json` with the given
     * uniqueId — what the real PageLocator::findPageByUniqueId walks to.
     */
    private function pageFolder(string $langPath, string $name, string $uniqueId): Folder {
        $path = $langPath . '/' . $name;
        return $this->makeFolder($path, [
            $name . '.json' => $this->makeFile(
                $path . '/' . $name . '.json',
                ['uniqueId' => $uniqueId, 'title' => ucfirst($name)]
            ),
        ]);
    }

    /**
     * A resolver driving the REAL setHomepage over a fixture nl/ tree — so the page
     * lookup, root-level check and engine write all run for real. NO 'home' key
     * here: setHomepage's already-home short-circuit calls resolveHomepageNodeUniqueId
     * (the REAL resolver), so rigging a fake resolver would break setHomepage's own
     * logic. Instead the already-home case is driven the honest way: the engine mock
     * reports the target 'page-x' as the current homepage pointer, which the real
     * resolver resolves as isHomepage true. The inert PageCacheInvalidator no-ops
     * clearCache.
     *
     * @param array<string,Folder> $nlChildren the nl/ language-root children
     */
    private function makeSetHomepageResolver(
        array $nlChildren,
        bool $alreadyHome,
        HomepageService $engine
    ): HomepageResolverService {
        $langFolder = $this->makeFolder('/IntraVox/nl', $nlChildren);
        $base = $this->makeFolder('/IntraVox', ['nl' => $langFolder]);

        $folders = $this->fakeFolderContext(
            intraVox: $base,
            userLanguage: 'nl',
            primaryLanguage: 'nl',
            languageFolder: $langFolder,
            readLanguageFolder: $langFolder
        );

        // setHomepage runs REAL (page lookup + root-level check + engine write) over
        // a REAL PageLocator against the fixture tree, so the missing-page /
        // non-root-page reject tests resolve genuinely. The already-home short-circuit
        // is now `$uniqueId === resolveHomepageNodeUniqueId()` (fase-5 inlined the
        // former homepagePredicate). $alreadyHome controls it via the engine pointer:
        // true → pointer 'page-x' (resolves as home → no-op); false → no pointer, and
        // the fixtures for the write tests carry no home.json that resolves to page-x,
        // so page-x is genuinely not-home and the write path runs. Real collaborators
        // + inert cache-invalidator.
        $engine->method('getHomepageUniqueId')->willReturn($alreadyHome ? 'page-x' : null);
        $realLocator = new PageLocator(
            $this->createMock(\OCA\IntraVox\Service\PageIndexService::class),
            $this->createMock(LoggerInterface::class)
        );
        return new HomepageResolverService(
            $engine,
            $folders,
            $realLocator,
            $this->fakeCacheInvalidator()
        );
    }

    public function testSetHomepageRejectsMissingPage(): void {
        $engine = $this->createMock(HomepageService::class);
        $engine->expects($this->never())->method('setHomepageUniqueId');
        // Empty nl/: the target uniqueId resolves to nothing.
        $resolver = $this->makeSetHomepageResolver([], false, $engine);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Page not found');
        $resolver->setHomepage('page-x');
    }

    public function testSetHomepageRejectsNonRootPage(): void {
        $engine = $this->createMock(HomepageService::class);
        $engine->expects($this->never())->method('setHomepageUniqueId');
        // page-x lives at nl/section/deep, whose parent is nl/section, not nl/.
        $deep = $this->pageFolder('/IntraVox/nl/section', 'deep', 'page-x');
        $section = $this->makeFolder('/IntraVox/nl/section', ['deep' => $deep]);
        $resolver = $this->makeSetHomepageResolver(['section' => $section], false, $engine);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Only root-level pages can be the homepage');
        $resolver->setHomepage('page-x');
    }

    public function testSetHomepageAlreadyHomeIsANoOp(): void {
        $engine = $this->createMock(HomepageService::class);
        $engine->expects($this->never())->method('setHomepageUniqueId');
        $welcome = $this->pageFolder('/IntraVox/nl', 'welcome', 'page-x');
        // isHomepage returns true -> short-circuit before any write. The
        // never() expectation on the engine is the assertion.
        $resolver = $this->makeSetHomepageResolver(['welcome' => $welcome], true, $engine);

        $resolver->setHomepage('page-x');
        $this->addToAssertionCount(1);
    }

    public function testSetHomepageWritesPointerAndClearsCache(): void {
        $engine = $this->createMock(HomepageService::class);
        $engine->expects($this->once())
            ->method('setHomepageUniqueId')
            ->with('page-x', 'nl');
        $welcome = $this->pageFolder('/IntraVox/nl', 'welcome', 'page-x');
        $resolver = $this->makeSetHomepageResolver(['welcome' => $welcome], false, $engine);

        // The once() expectation on setHomepageUniqueId is the assertion.
        $resolver->setHomepage('page-x');
        $this->addToAssertionCount(1);
    }

    /**
     * A loose home page counts as root-level even though the parent-path guard
     * would otherwise reject it: findPageByUniqueId flags home.json with
     * isHome=true, which bypasses the guard.
     */
    public function testSetHomepageAcceptsLooseHomeViaIsHomeFlag(): void {
        // The isHome bypass in the root-level guard: a loose home.json's folder-parent
        // is the language root's PARENT (not the root itself), so the strict parent-path
        // check would reject it — but findPageByUniqueId flags it isHome=true, which
        // bypasses the guard. Since fase-5 inlined the already-home predicate as a real
        // self-call, setting the loose home (page-x) as homepage when it already resolves
        // as home is a legitimate no-op AFTER the guard passes. The observable proof the
        // guard accepted it is therefore the ABSENCE of the 'Only root-level' rejection.
        $engine = $this->createMock(HomepageService::class);
        $resolver = $this->makeSetHomepageResolver([
            'home.json' => $this->makeFile('/IntraVox/nl/home.json',
                ['uniqueId' => 'page-x', 'title' => 'Welkom']),
        ], false, $engine);

        // Must NOT throw 'Only root-level pages can be the homepage' — the isHome
        // flag carried it past the guard.
        $resolver->setHomepage('page-x');
        $this->addToAssertionCount(1);
    }
}
