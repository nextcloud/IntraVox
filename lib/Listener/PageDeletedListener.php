<?php
declare(strict_types=1);

namespace OCA\IntraVox\Listener;

use OCA\IntraVox\Event\PageDeletedEvent;
use OCP\Comments\ICommentsManager;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use Psr\Log\LoggerInterface;

/**
 * Listener that cleans up comments and reactions when a page is deleted
 *
 * @template-implements IEventListener<PageDeletedEvent>
 */
class PageDeletedListener implements IEventListener {
    public function __construct(
        private ICommentsManager $commentsManager,
        private LoggerInterface $logger
    ) {}

    public function handle(Event $event): void {
        if (!$event instanceof PageDeletedEvent) {
            return;
        }

        $uniqueId = $event->getUniqueId();

        if (empty($uniqueId)) {
            $this->logger->warning('PageDeletedListener: No uniqueId provided for page ' . $event->getPageId());
            return;
        }

        try {
            // Delete all comments for this page (includes reactions stored as comments)
            $this->commentsManager->deleteCommentsAtObject('intravox_page', $uniqueId);
            $this->logger->info('IntraVox: Deleted comments for page: ' . $uniqueId);
        } catch (\Exception $e) {
            $this->logger->error('IntraVox: Failed to delete comments for page ' . $uniqueId . ': ' . $e->getMessage());
        }
    }
}
