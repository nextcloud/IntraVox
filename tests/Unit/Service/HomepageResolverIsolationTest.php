<?php
declare(strict_types=1);

namespace OCA\IntraVox\Tests\Unit\Service;

use OCA\IntraVox\Service\Homepage\HomepageResolverService;
use PHPUnit\Framework\TestCase;

/**
 * Structural guard: the HOMEPAGE carve keeps HomepageResolverService decoupled
 * from the PageService god-class.
 *
 * Originally the resident lookups (findPageByUniqueId / getLanguageFolderByCode /
 * getCachedFileContent / resolveEffectiveLanguage / userLanguage / isHomepage /
 * clearCache) reached this service ONLY as $this-bound closures from PageService.
 * Fase-5 DI-promoted the service: those closures were replaced by direct calls on
 * injected first-class collaborators (FolderContext / PageLocator /
 * PageCacheInvalidator) and the circular homepage predicate was inlined as a
 * self-call. So the invariant is no longer "must not name those methods" (it now
 * legitimately calls folders->/locator->/cacheInvalidator-> equivalents) but the
 * stronger, real one: the service must have a CLOSURE-FREE, PageService-FREE ctor —
 * i.e. it is fully DI-autowirable and never reaches back into the facade.
 */
class HomepageResolverIsolationTest extends TestCase {

    public function testHomepageResolverHasNoClosureOrPageServiceDependency(): void {
        $ctor = (new \ReflectionClass(HomepageResolverService::class))->getConstructor();
        $this->assertNotNull($ctor);

        foreach ($ctor->getParameters() as $p) {
            $type = $p->getType();
            $name = $type instanceof \ReflectionNamedType ? $type->getName() : (string) $type;
            $this->assertNotSame(
                \Closure::class,
                $name,
                "HomepageResolverService ctor param \${$p->getName()} is a \\Closure — the "
                    . 'fase-5 DI-promotion must leave the ctor closure-free (autowirable).'
            );
            $this->assertNotSame(
                \OCA\IntraVox\Service\PageService::class,
                $name,
                "HomepageResolverService must not depend on the PageService facade (param \${$p->getName()})"
                    . ' — it would re-tangle the homepage service into the god-class.'
            );
        }
    }

    /**
     * It must also not reach the facade through code (a `new PageService` or a
     * `PageService::` static call), which a type-only ctor check would miss.
     * Matches only real code references, not "NewsPageService" or docblock prose.
     */
    public function testHomepageResolverSourceDoesNotConstructOrCallPageService(): void {
        $src = file_get_contents(
            __DIR__ . '/../../../lib/Service/Homepage/HomepageResolverService.php'
        );
        $this->assertIsString($src);
        $this->assertDoesNotMatchRegularExpression(
            '/\bnew\s+PageService\b|(?<![A-Za-z])PageService::/',
            $src,
            'HomepageResolverService must not construct or statically call the PageService facade.'
        );
    }
}
