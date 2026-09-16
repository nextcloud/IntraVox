<?php

declare(strict_types=1);

namespace OCA\IntraVox\Service\Cache;

use OCA\IntraVox\Service\Locator\PageLocator;
use OCA\IntraVox\Service\PermissionService;
use OCA\IntraVox\Service\SystemFileService;

/**
 * The post-mutation cache fan-out — extracted verbatim from PageService::clearCache
 * / clearCollaboratorCaches (facade-elimination phase 2). One place that knows which
 * caches a page mutation invalidates and in what order: the request-level caches
 * always, the expensive (tree + distributed) caches unless a deferred batch is
 * suppressing them, and the collaborator caches on that same suppression condition.
 *
 * The deferred-batch state itself lives on PageCacheService (clearExpensive()
 * records-instead-of-clears while suppressed); this service only orchestrates the
 * fan-out. PageService::clearCache() stays as a thin private delegator so the 19
 * test subclasses that shadow it keep intercepting unchanged.
 */
final class PageCacheInvalidator {
    public function __construct(
        private PageCacheService $cache,
        private PageLocator $locator,
        private PermissionService $permissionService,
    ) {
    }

    /**
     * Clear all request-level caches (call after mutations).
     */
    public function invalidate(?string $pageId = null): void {
        // Request-level caches are always invalidated immediately: these are cheap
        // array resets, and doing them per item keeps every mutation seeing a
        // truthful filesystem view mid-batch (identical to the non-batch path).
        $this->cache->clearRequest($pageId);
        if ($pageId === null) {
            $this->locator->clearRequestCaches();
            $this->permissionService->clearNodePermissionsCache();
        }

        // The expensive part — the tree cache and the distributed cache
        // (IPC/Redis clear()) — is what makes a 100-item bulk op wipe the
        // distributed cache 100×. clearExpensive() defers it during a batch and
        // returns false; the collaborator caches are part of that same flush and
        // are skipped on the same condition.
        //
        // The clear is blanket rather than targeted: a single page mutation can
        // be visible to any group with read access via GroupFolder ACL, and we
        // cannot enumerate those from here. The bucket count is small (≤ groups
        // × languages, typically ~40), so a blanket clear is cheaper than
        // tracking dependencies. This also drops the news-version counters and
        // content caches; subsequent reads re-initialize at 0 and rebuild.
        if ($this->cache->clearExpensive()) {
            $this->invalidateCollaborators();
        }
    }

    /**
     * Open a deferred-clear batch: the expensive tree/distributed clears are held
     * until endDeferred(), so a bulk op wipes the distributed cache once, not once
     * per item. Verbatim from PageService::beginDeferredClear (the batch state
     * lives on PageCacheService).
     */
    public function beginDeferred(): void {
        $this->cache->beginDeferred();
    }

    /**
     * Close a deferred-clear batch. endDeferred() performs the deferred
     * tree/distributed clear itself and reports whether it did; the collaborator
     * caches below belong to the same flush, so they follow on exactly that
     * condition. Verbatim from PageService::endDeferredClear.
     */
    public function endDeferred(): void {
        if ($this->cache->endDeferred()) {
            $this->invalidateCollaborators();
        }
    }

    /**
     * Caches owned by OTHER services that must drop whenever ours do.
     *
     * Separate because two paths reach it: an ordinary invalidate(), and the
     * flush that closes a deferred batch. These are not ours to own —
     * SystemFileService builds the public-share tree, PermissionService keeps
     * the per-language path map — so we invalidate through their APIs rather
     * than reaching into their state.
     */
    public function invalidateCollaborators(): void {
        SystemFileService::clearStaticTreeCache();
        $this->permissionService->clearDistributedCache();
    }
}
