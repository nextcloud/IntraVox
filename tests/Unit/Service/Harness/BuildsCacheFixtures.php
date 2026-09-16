<?php
declare(strict_types=1);

namespace OCA\IntraVox\Tests\Unit\Service\Harness;

use OCA\IntraVox\Service\Cache\PageCacheInvalidator;
use OCA\IntraVox\Service\Cache\PageCacheService;
use OCA\IntraVox\Service\Locator\PageLocator;
use OCA\IntraVox\Service\PermissionService;

/**
 * Facade-free cache fixture (COMMIT 0, fase-10 harness split).
 * fakeCacheInvalidator() builds a real PageCacheInvalidator over inert doubles.
 * Lifted verbatim out of BuildsPageService.
 */
trait BuildsCacheFixtures {
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
}
