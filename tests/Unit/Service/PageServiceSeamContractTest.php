<?php
declare(strict_types=1);

namespace OCA\IntraVox\Tests\Unit\Service;

use OCA\IntraVox\Service\PageService;
use OCA\IntraVox\Tests\Unit\Service\Harness\BuildsPageService;
use PHPUnit\Framework\TestCase;

/**
 * Pins the test-seam contract the PageService decomposition depends on.
 *
 * ALL of the old subclass-override seams are now RETIRED. The three protected
 * FOLDER seams (getIntraVoxFolder / getLanguageFolder / getReadLanguageFolder)
 * became the injected FolderContext; the clearCache (private no-op shadow) and
 * isHomepage (public predicate override) seams became injected collaborators — an
 * inert PageCacheInvalidator and a rigged HomepageResolverService, both supplied
 * through the real DI ctor by BuildsPageService::buildRealPageService(). Tests no
 * longer subclass PageService to intercept behaviour; they construct the real class
 * and inject fakes.
 *
 * So the two visibility pins this test used to carry (clearCache-stays-private,
 * isHomepage-stays-public) are gone — the facts they guarded no longer exist. In
 * their place is a regression FENCE: no test may re-introduce a subclass override
 * of a retired seam. What still needs pinning are the reflection-anchored private
 * delegators and the lazy-seam/accessor pairing.
 */
class PageServiceSeamContractTest extends TestCase {

    use BuildsPageService;

    /**
     * The regression fence for the fase-3 seam retirement: no test file may declare
     * its own clearCache(), isHomepage() or createVersionBeforeUpdate() on a
     * PageService subclass. Those overrides were how tests intercepted behaviour
     * before the collaborators (PageCacheInvalidator / HomepageResolverService /
     * PageVersionService) were injectable; re-introducing one would resurrect the
     * exact god-class coupling the migration removed. This globs the whole test tree
     * so a re-added override is a loud, deliberate red.
     */
    public function testNoTestOverridesRetiredSeams(): void {
        $retired = ['clearCache', 'isHomepage', 'createVersionBeforeUpdate'];
        $offenders = [];
        $dir = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator(__DIR__ . '/../../', \FilesystemIterator::SKIP_DOTS)
        );
        foreach ($dir as $file) {
            if ($file->getExtension() !== 'php') {
                continue;
            }
            $src = file_get_contents($file->getPathname());
            foreach ($retired as $method) {
                if (preg_match('/function\s+' . $method . '\s*\(/', $src)) {
                    $offenders[] = $file->getFilename() . ' declares ' . $method . '()';
                }
            }
        }
        $this->assertSame(
            [],
            $offenders,
            "Retired seams must not be overridden in tests — inject the collaborator instead "
            . "(fakeCacheInvalidator / fakeHomepageResolver / a PageVersionService mock):\n"
            . implode("\n", $offenders)
        );
    }

    /**
     * The reflection-anchored private delegators a handful of tests reach via
     * ReflectionMethod (their names carry no "(" so the grep delete-gate can miss
     * them). Keeping this list green means a future removal is a conscious edit.
     */
    public function testReflectionAnchoredPrivatesStillExist(): void {
        $anchored = [
            // listPagesFromIndex + inStableOrder RETIRED from PageService — the
            // LISTING carve moved them to Listing/PageLister (fromIndex/inStableOrder);
            // PageIndexLookupTest + PageListingOrderTest reflect them there.
            // buildPageTree RETIRED from PageService — the fase-4 capstone moved
            // getPageTree (its only caller) to Tree/PageTreeService, so the by-ref
            // wrapper is gone; the recursive walk is PageTreeBuilder::build, which
            // PageTreePlaceholderTest + PageTreeBuilderTest now drive directly.
            'getOrCreateFolderPath',
            // renamePageFolder RETIRED from PageService — it moved to
            // Metadata/PageMetadataService (PageRenameFolderTest reflects it there).
            // resolveTranslations RETIRED from PageService — facade elimination
            // phase 2 internalized it into PageReadService/PageDataEnricher over
            // the injected TranslationGroupService; the M1 ACL-filtering is pinned
            // directly on TranslationGroupService::resolveTranslations
            // (PageTranslationGroupTest::testResolveTranslationsSkipsRowsTheMountDoesNotGrant).
        ];
        foreach ($anchored as $name) {
            $this->assertTrue(
                method_exists(PageService::class, $name),
                "$name is reached by reflection in the test suite and must not vanish silently"
            );
        }
    }

    /**
     * Pairing invariant: every lazy-seam service in the harness skip-list has a
     * corresponding lazy accessor on PageService that builds the real one, and
     * every such accessor's service is in the skip-list. If they drift, either a
     * seam service gets auto-mocked (breaking lookups) or an accessor has no
     * skip-list guard.
     */
    public function testLazySeamListPairsWithAccessors(): void {
        // The four lazy-SEAM accessors — the ones whose service must be left unset
        // in tests so the accessor builds the real collaborator. shape()/cache()
        // are excluded: their services are ordinary auto-filled mocks, not seams.
        $seamAccessors = ['locator', 'translationGroups', 'media', 'news'];

        $ref = new \ReflectionClass(PageService::class);
        $accessorReturns = [];
        foreach ($seamAccessors as $accessor) {
            $this->assertTrue($ref->hasMethod($accessor), "lazy accessor $accessor() must exist");
            $accessorReturns[] = (string) (new \ReflectionMethod(PageService::class, $accessor))->getReturnType();
        }

        // Bidirectional set equality: every skip-list service is produced by a seam
        // accessor AND every seam accessor's service is in the skip-list. A one-way
        // check let a DELETION from LAZY_SEAM_SERVICES pass (the exact M3 regression
        // in the plan's risk table) — this catches drift in both directions.
        sort($accessorReturns);
        $skipList = self::LAZY_SEAM_SERVICES;
        sort($skipList);

        $this->assertSame(
            $skipList,
            $accessorReturns,
            'LAZY_SEAM_SERVICES must equal exactly the set of seam-accessor return types; '
            . 'a mismatch means a seam service was added/removed without its accessor (or vice versa)'
        );
    }
}
