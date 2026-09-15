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
        return $this->fakePageReadFrom(fn(string $id) => $byId[$id] ?? $page);
    }

    /**
     * A real PageReadService whose pageExistsByUniqueId($id) returns $exists for any
     * id — the fase-9 replacement for `createMock(PageService)->method('pageExistsByUniqueId')`.
     * pageExistsByUniqueId resolves via folders->readLanguageFolder() +
     * locator->findPageByUniqueId(); rigging the locator to return a hit (or null)
     * makes the probe answer $exists. getPage is inert here (not the subject).
     */
    protected function fakePageReadExisting(bool $exists): PageReadService {
        return $this->fakePageReadFrom(fn(string $id) => null, fn(string $id): bool => $exists);
    }

    /**
     * A real PageReadService whose getPage($id) runs $fn($id) — for per-id logic or
     * throwing (a callback that throws propagates verbatim through the cache-hit
     * short-circuit, reproducing a getPage that throws).
     *
     * When $existsFn is given, the internal PageLocator's findPageByUniqueId is
     * rigged so pageExistsByUniqueId($id) === $existsFn($id) (read at call time, so a
     * test can flip a mutable predicate); the FolderContext's readLanguageFolder()
     * resolves to a bare folder so the probe reaches the locator.
     *
     * @param callable(string):(array|null) $fn
     * @param callable(string):bool|null $existsFn
     */
    protected function fakePageReadFrom(callable $fn, ?callable $existsFn = null): PageReadService {
        $cache = $this->createMock(PageCacheService::class);
        $cache->method('getPageData')->willReturnCallback($fn);

        $locator = $this->createMock(\OCA\IntraVox\Service\Locator\PageLocator::class);
        $folders = $this->buildInert(\OCA\IntraVox\Service\Folder\FolderContext::class);
        if ($existsFn !== null) {
            // pageExistsByUniqueId: readLanguageFolder() must resolve (not throw) so
            // the probe reaches findPageByUniqueId, whose hit/miss decides existence.
            // FolderContext is final, so build a REAL one whose getReadLanguageFolder
            // seam closure yields a bare folder (all other atoms inert — the read seam
            // fires first).
            $folders = new \OCA\IntraVox\Service\Folder\FolderContext(
                $this->createMock(\OCP\Files\IRootFolder::class),
                'tester',
                $this->createMock(\OCP\IConfig::class),
                $this->createMock(\OCA\IntraVox\Service\LanguageService::class),
                new \OCA\IntraVox\Service\Language\LanguageResolver(),
                $this->createMock(\OCA\IntraVox\Service\Locator\PageLocator::class),
                null,
                fn(): \OCP\Files\Folder => $this->createMock(\OCP\Files\Folder::class)
            );
            $hitFolder = $this->createMock(\OCP\Files\Folder::class);
            $locator->method('findPageByUniqueId')->willReturnCallback(
                fn($folder, string $uniqueId, $lang = null) => $existsFn($uniqueId) ? ['folder' => $hitFolder] : null
            );
        }

        return new PageReadService(
            $cache,
            $locator,
            $this->createMock(\OCA\IntraVox\Service\Publication\MetaVoxGateway::class),
            $this->buildInert(\OCA\IntraVox\Service\Path\PageDataEnricher::class),
            $this->buildInert(\OCA\IntraVox\Service\Sanitize\PageShapeSanitizer::class),
            $this->createMock(\OCA\IntraVox\Service\PermissionService::class),
            new \OCA\IntraVox\Service\Util\PageIdUtils(),
            $this->createMock(\Psr\Log\LoggerInterface::class),
            $folders,
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
