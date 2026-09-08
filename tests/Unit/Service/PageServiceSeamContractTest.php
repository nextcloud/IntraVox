<?php
declare(strict_types=1);

namespace OCA\IntraVox\Tests\Unit\Service;

use OCA\IntraVox\Service\PageService;
use OCA\IntraVox\Tests\Unit\Service\Harness\BuildsPageService;
use PHPUnit\Framework\TestCase;

/**
 * Pins the test-seam contract the PageService decomposition depends on.
 *
 * The three protected FOLDER seams (getIntraVoxFolder / getLanguageFolder /
 * getReadLanguageFolder) are RETIRED (clean-target step 11): the folder/language
 * substrate is now FolderContext, and tests inject one directly instead of
 * subclass-overriding a seam. What remains contract-pinned here is the public
 * isHomepage() de-facto seam, the private clearCache() (nine subclasses redeclare
 * it), and the reflection-anchored private delegators.
 *
 * This test makes any change to those a loud, deliberate red instead.
 */
class PageServiceSeamContractTest extends TestCase {

    use BuildsPageService;

    /**
     * isHomepage() is a public de-facto seam: three tests override it because the
     * real body reads appconfig via collaborators that those fixtures do not wire.
     * It must stay public and keep its (string, ?string) shape.
     */
    public function testIsHomepageStaysPublicSeam(): void {
        $m = new \ReflectionMethod(PageService::class, 'isHomepage');

        $this->assertTrue($m->isPublic(), 'isHomepage is overridden as a public method by 3 tests');
        $this->assertFalse($m->isFinal());
        $this->assertSame('bool', (string) $m->getReturnType());
        $params = $m->getParameters();
        $this->assertCount(2, $params);
        $this->assertSame('uniqueId', $params[0]->getName());
        $this->assertFalse($params[0]->allowsNull());
        $this->assertTrue($params[1]->allowsNull(), 'the language arg is optional/nullable');
    }

    /**
     * clearCache() is PRIVATE and must stay private. Nine test files declare their
     * own clearCache() on the subclass; a child may redeclare a private parent
     * method freely, but if PageService widened it to protected/public those nine
     * declarations would become signature-conflict fatals. This is the single most
     * fragile visibility fact in the suite.
     */
    public function testClearCacheStaysPrivate(): void {
        $m = new \ReflectionMethod(PageService::class, 'clearCache');
        $this->assertTrue(
            $m->isPrivate(),
            'clearCache must remain private or 9 subclass clearCache() declarations become fatals'
        );
    }

    /**
     * The reflection-anchored private delegators a handful of tests reach via
     * ReflectionMethod (their names carry no "(" so the grep delete-gate can miss
     * them). Keeping this list green means a future removal is a conscious edit.
     */
    public function testReflectionAnchoredPrivatesStillExist(): void {
        $anchored = [
            'buildPageTree',
            'listPagesFromIndex',
            'getOrCreateFolderPath',
            'renamePageFolder',
            'resolveTranslations',
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
