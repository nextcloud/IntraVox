<?php
declare(strict_types=1);

namespace OCA\IntraVox\Service;

use OCA\IntraVox\AppInfo\Application;
use OCP\IConfig;

/**
 * Service for managing IntraVox engagement settings (reactions & comments)
 *
 * Settings are stored in Nextcloud's app config using IConfig
 */
class EngagementSettingsService {
    // Config keys
    private const KEY_ALLOW_PAGE_REACTIONS = 'allow_page_reactions';
    private const KEY_ALLOW_COMMENTS = 'allow_comments';
    private const KEY_ALLOW_COMMENT_REACTIONS = 'allow_comment_reactions';
    private const KEY_SINGLE_REACTION_PER_USER = 'single_reaction_per_user';

    // Defaults
    private const DEFAULT_ALLOW_PAGE_REACTIONS = true;
    private const DEFAULT_ALLOW_COMMENTS = true;
    private const DEFAULT_ALLOW_COMMENT_REACTIONS = true;
    private const DEFAULT_SINGLE_REACTION_PER_USER = true; // Facebook-style: one reaction per user

    public function __construct(
        private IConfig $config
    ) {}

    /**
     * Get all engagement settings
     *
     * @return array All settings with their current values
     */
    public function getAll(): array {
        return [
            'allowPageReactions' => $this->getAllowPageReactions(),
            'allowComments' => $this->getAllowComments(),
            'allowCommentReactions' => $this->getAllowCommentReactions(),
            'singleReactionPerUser' => $this->getSingleReactionPerUser(),
        ];
    }

    /**
     * Update multiple settings at once
     *
     * @param array $settings Key-value pairs of settings to update
     * @return array Updated settings
     */
    public function updateAll(array $settings): array {
        if (isset($settings['allowPageReactions'])) {
            $this->setAllowPageReactions((bool)$settings['allowPageReactions']);
        }
        if (isset($settings['allowComments'])) {
            $this->setAllowComments((bool)$settings['allowComments']);
        }
        if (isset($settings['allowCommentReactions'])) {
            $this->setAllowCommentReactions((bool)$settings['allowCommentReactions']);
        }
        // singleReactionPerUser is always true, not configurable

        return $this->getAll();
    }

    /**
     * Check if page reactions are allowed globally
     */
    public function getAllowPageReactions(): bool {
        return $this->config->getAppValue(
            Application::APP_ID,
            self::KEY_ALLOW_PAGE_REACTIONS,
            self::DEFAULT_ALLOW_PAGE_REACTIONS ? '1' : '0'
        ) === '1';
    }

    /**
     * Set whether page reactions are allowed globally
     */
    public function setAllowPageReactions(bool $value): void {
        $this->config->setAppValue(
            Application::APP_ID,
            self::KEY_ALLOW_PAGE_REACTIONS,
            $value ? '1' : '0'
        );
    }

    /**
     * Check if comments are allowed globally
     */
    public function getAllowComments(): bool {
        return $this->config->getAppValue(
            Application::APP_ID,
            self::KEY_ALLOW_COMMENTS,
            self::DEFAULT_ALLOW_COMMENTS ? '1' : '0'
        ) === '1';
    }

    /**
     * Set whether comments are allowed globally
     */
    public function setAllowComments(bool $value): void {
        $this->config->setAppValue(
            Application::APP_ID,
            self::KEY_ALLOW_COMMENTS,
            $value ? '1' : '0'
        );
    }

    /**
     * Check if reactions on comments are allowed globally
     */
    public function getAllowCommentReactions(): bool {
        return $this->config->getAppValue(
            Application::APP_ID,
            self::KEY_ALLOW_COMMENT_REACTIONS,
            self::DEFAULT_ALLOW_COMMENT_REACTIONS ? '1' : '0'
        ) === '1';
    }

    /**
     * Set whether reactions on comments are allowed globally
     */
    public function setAllowCommentReactions(bool $value): void {
        $this->config->setAppValue(
            Application::APP_ID,
            self::KEY_ALLOW_COMMENT_REACTIONS,
            $value ? '1' : '0'
        );
    }

    /**
     * Check if single reaction mode is enabled (new reaction replaces old)
     * Always returns true - users can only have one reaction type per page/comment
     */
    public function getSingleReactionPerUser(): bool {
        return true;
    }

    /**
     * Check if reactions are allowed for a specific page
     * Takes into account both global settings and page-level overrides
     *
     * @param array|null $pageSettings Page-level settings (from page JSON)
     * @return bool Whether reactions are allowed
     */
    public function areReactionsAllowedForPage(?array $pageSettings = null): bool {
        // Check global setting first
        if (!$this->getAllowPageReactions()) {
            return false;
        }

        // If page has explicit setting, use it
        if ($pageSettings !== null && isset($pageSettings['allowReactions'])) {
            return (bool)$pageSettings['allowReactions'];
        }

        // Default: inherit from global (which is true if we got here)
        return true;
    }

    /**
     * Check if comments are allowed for a specific page
     * Takes into account both global settings and page-level overrides
     *
     * @param array|null $pageSettings Page-level settings (from page JSON)
     * @return bool Whether comments are allowed
     */
    public function areCommentsAllowedForPage(?array $pageSettings = null): bool {
        // Check global setting first
        if (!$this->getAllowComments()) {
            return false;
        }

        // If page has explicit setting, use it
        if ($pageSettings !== null && isset($pageSettings['allowComments'])) {
            return (bool)$pageSettings['allowComments'];
        }

        // Default: inherit from global (which is true if we got here)
        return true;
    }
}
