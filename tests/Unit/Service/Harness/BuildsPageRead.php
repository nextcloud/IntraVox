<?php

declare(strict_types=1);

namespace OCA\IntraVox\Tests\Unit\Service\Harness;

use OCA\IntraVox\Service\Cache\PageCacheService;
use OCA\IntraVox\Service\Read\PageReadService;

/**
 * Builds a REAL (final) PageReadService whose getPage() returns a fixed page —
 * the fase-4 C6 replacement for `createMock(PageService::class)->method('getPage')`.
 *
 * PageReadService is final (cannot be mocked), and the codebase convention is to
 * build the real domain service over mocked collaborators rather than double the
 * service itself (see PageContentApiControllerTest's versionDomain). getPage()'s
 * very first act is to return the request-cache entry verbatim
 * (`$this->cache->getPageData($id)`), so a PageCacheService mock whose getPageData
 * returns the fixture makes getPage return it — none of the other ten ctor deps are
 * reached on that path, so they are inert doubles.
 *
 * The host TestCase supplies createMock() (this is a PHPUnit\Framework\TestCase
 * trait).
 */
trait BuildsPageRead {
    /**
     * A real PageReadService::getPage() returning $page for ANY id (or per-id when
     * $byId is given: id => page array).
     *
     * @param array<string,mixed>|null $page returned for any id (the common case)
     * @param array<string,array<string,mixed>> $byId optional per-id map
     */
    protected function fakePageReadReturning(?array $page, array $byId = []): PageReadService {
        $cache = $this->createMock(PageCacheService::class);
        $cache->method('getPageData')->willReturnCallback(
            fn(string $id) => $byId[$id] ?? $page
        );

        return new PageReadService(
            $cache,
            $this->createMock(\OCA\IntraVox\Service\Locator\PageLocator::class),
            $this->createMock(\OCA\IntraVox\Service\Publication\MetaVoxGateway::class),
            $this->buildInert(\OCA\IntraVox\Service\Path\PageDataEnricher::class),
            $this->buildInert(\OCA\IntraVox\Service\Sanitize\PageShapeSanitizer::class),
            $this->createMock(\OCA\IntraVox\Service\PermissionService::class),
            new \OCA\IntraVox\Service\Util\PageIdUtils(),
            $this->createMock(\Psr\Log\LoggerInterface::class),
            $this->buildInert(\OCA\IntraVox\Service\Folder\FolderContext::class),
            $this->buildInert(\OCA\IntraVox\Service\Translation\TranslationGroupService::class),
            new \OCA\IntraVox\Service\Util\GroupfolderResolver(),
        );
    }

    /**
     * Mock $class, or — when it is final and cannot be doubled — build a real one,
     * recursing for its own non-builtin ctor deps. These are never reached on the
     * cache-hit path fakePageReadReturning() drives, so their behaviour is irrelevant.
     */
    private function buildInert(string $class): object {
        try {
            return $this->createMock($class);
        } catch (\PHPUnit\Framework\MockObject\Generator\ClassIsFinalException $e) {
            $ctor = (new \ReflectionClass($class))->getConstructor();
            $args = [];
            foreach ($ctor?->getParameters() ?? [] as $p) {
                $t = $p->getType();
                $name = $t instanceof \ReflectionNamedType ? $t->getName() : null;
                // A \Closure/callable dep cannot be instantiated; nullable ones take
                // their default, otherwise a no-op closure. Interfaces/classes are
                // mocked-or-built; everything else falls back to the default/null.
                if ($name === \Closure::class) {
                    $args[] = $p->allowsNull() && $p->isDefaultValueAvailable()
                        ? $p->getDefaultValue()
                        : ($p->allowsNull() ? null : static function (): void {});
                } elseif ($name !== null && !$t->isBuiltin()
                    && (class_exists($name) || interface_exists($name))
                    && (new \ReflectionClass($name))->isInstantiable() === false
                    && !interface_exists($name)) {
                    // abstract/uninstantiable concrete class → null (unreachable on the cache-hit path)
                    $args[] = null;
                } elseif ($name !== null && !$t->isBuiltin() && (class_exists($name) || interface_exists($name))) {
                    $args[] = $this->buildInert($name);
                } elseif ($p->isDefaultValueAvailable()) {
                    $args[] = $p->getDefaultValue();
                } else {
                    $args[] = null;
                }
            }
            return new $class(...$args);
        }
    }
}
