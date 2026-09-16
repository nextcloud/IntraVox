<?php
declare(strict_types=1);

namespace OCA\IntraVox\Tests\Unit\Service;

use OCA\IntraVox\Service\News\NewsWidgetService;
use PHPUnit\Framework\TestCase;

/**
 * Structural guard: the NEWS-widget carve keeps NewsWidgetService decoupled from
 * the PageService god-class.
 *
 * Originally the source-page lookup reached this service as a $this-bound
 * `locatePage` closure from PageService. Fase-5 DI-promoted the service: that
 * closure became a direct call on an injected PageLocator, so the invariant is no
 * longer "must not name findPageByUniqueId" (it now legitimately calls
 * locator->findPageByUniqueId) but the stronger, real one: the ctor must be
 * CLOSURE-FREE and PageService-FREE — fully DI-autowirable, never reaching back
 * into the facade.
 */
class NewsWidgetIsolationTest extends TestCase {

    public function testNewsWidgetServiceHasNoClosureOrPageServiceDependency(): void {
        $ctor = (new \ReflectionClass(NewsWidgetService::class))->getConstructor();
        $this->assertNotNull($ctor);

        foreach ($ctor->getParameters() as $p) {
            $type = $p->getType();
            $name = $type instanceof \ReflectionNamedType ? $type->getName() : (string) $type;
            $this->assertNotSame(
                \Closure::class,
                $name,
                "NewsWidgetService ctor param \${$p->getName()} is a \\Closure — the fase-5 "
                    . 'DI-promotion must leave the ctor closure-free (autowirable).'
            );
            $this->assertNotSame(
                // String literal, not ::class — this guard outlives PageService.php
                // (fase-10 removes it); it asserts the ctor param TYPE NAME is never
                // that FQN, which is a plain string comparison.
                'OCA\\IntraVox\\Service\\PageService',
                $name,
                "NewsWidgetService must not depend on the PageService facade (param \${$p->getName()})."
            );
        }
    }

    public function testNewsWidgetSourceDoesNotConstructOrCallPageService(): void {
        $src = file_get_contents(
            __DIR__ . '/../../../lib/Service/News/NewsWidgetService.php'
        );
        $this->assertIsString($src);
        $this->assertDoesNotMatchRegularExpression(
            '/\bnew\s+PageService\b|(?<![A-Za-z])PageService::/',
            $src,
            'NewsWidgetService must not construct or statically call the PageService facade.'
        );
    }
}
