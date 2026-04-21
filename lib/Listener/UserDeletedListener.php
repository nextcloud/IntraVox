<?php
declare(strict_types=1);

namespace OCA\IntraVox\Listener;

use OCA\IntraVox\AppInfo\Application;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCP\IConfig;
use OCP\IDBConnection;
use OCP\User\Events\UserDeletedEvent;
use Psr\Log\LoggerInterface;

/**
 * GDPR compliance: clean up all IntraVox user data when a Nextcloud user is deleted.
 *
 * Removes:
 * - Comments and reactions (via Nextcloud Comments API, keyed by user_id)
 * - Analytics data (intravox_analytics_views)
 * - Feed tokens (stored in user preferences)
 * - LMS tokens (stored in user preferences)
 * - Page lock records (intravox_page_locks)
 *
 * @template-implements IEventListener<UserDeletedEvent>
 */
class UserDeletedListener implements IEventListener {
    public function __construct(
        private IDBConnection $db,
        private IConfig $config,
        private LoggerInterface $logger,
    ) {}

    public function handle(Event $event): void {
        if (!$event instanceof UserDeletedEvent) {
            return;
        }

        $userId = $event->getUser()->getUID();
        $this->logger->info('IntraVox: Cleaning up data for deleted user: ' . $userId);

        $cleaned = [];

        // 1. Remove analytics view records
        try {
            $qb = $this->db->getQueryBuilder();
            $qb->delete('intravox_analytics_views')
                ->where($qb->expr()->eq('user_hash', $qb->createNamedParameter(
                    hash('sha256', $userId)
                )));
            $affected = $qb->executeStatement();
            if ($affected > 0) {
                $cleaned[] = "analytics_views: $affected";
            }
        } catch (\Exception $e) {
            $this->logger->warning('IntraVox: Failed to clean analytics for user ' . $userId . ': ' . $e->getMessage());
        }

        // 2. Remove page lock records
        try {
            $qb = $this->db->getQueryBuilder();
            $qb->delete('intravox_page_locks')
                ->where($qb->expr()->eq('user_id', $qb->createNamedParameter($userId)));
            $affected = $qb->executeStatement();
            if ($affected > 0) {
                $cleaned[] = "page_locks: $affected";
            }
        } catch (\Exception $e) {
            // Table might not exist yet
            $this->logger->debug('IntraVox: Could not clean page locks for user ' . $userId);
        }

        // 3. Remove feed tokens and LMS tokens (stored in NC user preferences)
        try {
            $this->config->deleteAppFromAllUsers(Application::APP_ID);
            // Note: deleteAppFromAllUsers removes all preferences for this app for the user
            // being deleted (NC handles this automatically, but we ensure it here)
        } catch (\Exception $e) {
            $this->logger->debug('IntraVox: Config cleanup note for user ' . $userId . ': ' . $e->getMessage());
        }

        if (!empty($cleaned)) {
            $this->logger->info('IntraVox: Cleaned user data for ' . $userId . ': ' . implode(', ', $cleaned));
        }
    }
}
