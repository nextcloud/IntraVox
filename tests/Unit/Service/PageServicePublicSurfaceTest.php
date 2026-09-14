<?php
declare(strict_types=1);

namespace OCA\IntraVox\Tests\Unit\Service;

use OCA\IntraVox\Service\PageService;
use PHPUnit\Framework\TestCase;

/**
 * Freezes PageService's entire public surface (method name => parameter count)
 * as it stands at the start of the decomposition. 18 external consumers depend on
 * this surface, so as the refactor moves bodies into focused services behind
 * one-line delegators, the surface must NOT change: any removed/added/renamed
 * public method, or any changed arity, shows up as a deliberate one-line diff to
 * this expectation — turning an accidental API break into a red test.
 *
 * When the plan intends to drop a public method (a dead @deprecated shim with no
 * external callers), that removal is made here in the same commit, on purpose.
 */
class PageServicePublicSurfaceTest extends TestCase {

    /**
     * The public methods (excluding the constructor) and their declared parameter
     * counts. Sorted by name so a diff is readable.
     *
     * @return array<string,int>
     */
    private const PUBLIC_SURFACE = [
        'beginDeferredClear' => 0,
        'canCreateTemplates' => 0,
        'copyPage' => 3,
        'createPage' => 2,
        'createPageFromTemplate' => 3,
        'createTranslation' => 3,
        'deletePage' => 1,
        'deleteTemplate' => 1,
        'endDeferredClear' => 0,
        'getLanguageContentStatus' => 0,
        'getPageCountByLanguage' => 0,
        'getPageMetadata' => 1,
        'getTemplate' => 1,
        'linkTranslation' => 2,
        'listTemplates' => 0,
        'movePage' => 2,
        'pageExistsByUniqueId' => 1,
        'rebuildIndex' => 1,
        'reorderSiblings' => 2,
        'repairEntities' => 1,
        'resolveHomepageNodeUniqueId' => 2,
        'saveAsTemplate' => 3,
        'searchPages' => 1,
        'unlinkTranslation' => 1,
        'updatePage' => 2,
        'updatePageMetadata' => 2,
    ];

    /** @return array<string,int> name => parameter count for own public methods */
    private function currentSurface(): array {
        $ref = new \ReflectionClass(PageService::class);
        $surface = [];
        foreach ($ref->getMethods(\ReflectionMethod::IS_PUBLIC) as $m) {
            if ($m->getName() === '__construct') {
                continue;
            }
            if ($m->getDeclaringClass()->getName() !== PageService::class) {
                continue;
            }
            $surface[$m->getName()] = $m->getNumberOfParameters();
        }
        ksort($surface);
        return $surface;
    }

    public function testPublicSurfaceMatchesTheFrozenExpectation(): void {
        $expected = self::PUBLIC_SURFACE;
        ksort($expected);

        $this->assertSame(
            $expected,
            $this->currentSurface(),
            'PageService public surface changed. If deliberate (a body extraction that '
            . 'drops a dead shim, or an intended signature change), update PUBLIC_SURFACE '
            . 'in the same commit; otherwise this is an accidental API break for one of '
            . 'the 18 consumers.'
        );
    }

    public function testSurfaceCountIsPinned(): void {
        $this->assertCount(
            26,
            $this->currentSurface(),
            'the public method count changed; see testPublicSurfaceMatchesTheFrozenExpectation'
        );
    }
}
