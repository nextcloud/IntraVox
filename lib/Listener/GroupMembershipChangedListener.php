<?php
declare(strict_types=1);

namespace OCA\IntraVox\Listener;

use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCP\Group\Events\UserAddedEvent;
use OCP\Group\Events\UserRemovedEvent;
use OCP\ICache;
use OCP\ICacheFactory;
use Psr\Log\LoggerInterface;

/**
 * Invalidates IntraVox per-group cache buckets when a user's group
 * membership changes. The tree- and path-map caches are keyed by a hash
 * derived from `IGroupManager::getUserGroupIds()`; once a user moves in or
 * out of a group their hash changes, which by itself is safe (they'd just
 * miss the cache once), *but* peers who still share the old group set keep
 * seeing stale data until TTL expires.
 *
 * Because we cannot derive the user's *previous* group set from the event,
 * we wipe every IntraVox group-keyed bucket — at most ~10 groups × 4 langs
 * = 40 entries — and let the next reader rebuild. Group-membership changes
 * are rare enough that the rebuild cost is negligible.
 *
 * @implements IEventListener<UserAddedEvent|UserRemovedEvent>
 */
final class GroupMembershipChangedListener implements IEventListener {
    private ?ICache $pageCache = null;
    private ?ICache $permissionCache = null;
    private LoggerInterface $logger;

    public function __construct(ICacheFactory $cacheFactory, LoggerInterface $logger) {
        if ($cacheFactory->isAvailable()) {
            $this->pageCache = $cacheFactory->createDistributed('intravox-pages');
            $this->permissionCache = $cacheFactory->createDistributed('intravox-permissions');
        }
        $this->logger = $logger;
    }

    public function handle(Event $event): void {
        if (!($event instanceof UserAddedEvent) && !($event instanceof UserRemovedEvent)) {
            return;
        }

        try {
            $this->pageCache?->clear();
            $this->permissionCache?->clear();
            $this->logger->info('[IntraVox] Cleared group-keyed caches after group membership change', [
                'group' => $event->getGroup()->getGID(),
                'user' => $event->getUser()->getUID(),
                'event' => $event instanceof UserAddedEvent ? 'added' : 'removed',
            ]);
        } catch (\Throwable $e) {
            // Cache invalidation must never break group management.
            $this->logger->warning('[IntraVox] Failed to clear caches after group membership change', [
                'error' => $e->getMessage(),
            ]);
        }
    }
}
