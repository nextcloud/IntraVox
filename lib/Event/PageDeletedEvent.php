<?php
declare(strict_types=1);

namespace OCA\IntraVox\Event;

use OCP\EventDispatcher\Event;

/**
 * Event dispatched when an IntraVox page is deleted
 *
 * Used to trigger cleanup of associated comments and reactions
 */
class PageDeletedEvent extends Event {
    public function __construct(
        private string $pageId,
        private string $uniqueId
    ) {
        parent::__construct();
    }

    /**
     * Get the page ID (folder path)
     */
    public function getPageId(): string {
        return $this->pageId;
    }

    /**
     * Get the page unique ID (used for comments association)
     */
    public function getUniqueId(): string {
        return $this->uniqueId;
    }
}
