<?php
declare(strict_types=1);

namespace OCA\IntraVox\Listener;

use OCA\IntraVox\Service\PageService;
use OCP\Comments\CommentsEntityEvent;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use Psr\Log\LoggerInterface;

/**
 * Registers 'intravox_page' as a valid objectType for Nextcloud Comments
 *
 * This allows IntraVox pages to have comments and reactions using the
 * native Nextcloud Comments API (ICommentsManager).
 *
 * @template-implements IEventListener<CommentsEntityEvent>
 */
class CommentsEntityListener implements IEventListener {
    public function __construct(
        private PageService $pageService,
        private LoggerInterface $logger
    ) {}

    public function handle(Event $event): void {
        if (!$event instanceof CommentsEntityEvent) {
            return;
        }

        // Register 'intravox_page' as a valid object type for comments
        $event->addEntityCollection('intravox_page', function (string $pageUniqueId): bool {
            try {
                return $this->pageService->pageExistsByUniqueId($pageUniqueId);
            } catch (\Exception $e) {
                $this->logger->warning('CommentsEntityListener: Error checking page existence', [
                    'pageUniqueId' => $pageUniqueId,
                    'error' => $e->getMessage()
                ]);
                return false;
            }
        });
    }
}
