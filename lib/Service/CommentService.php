<?php
declare(strict_types=1);

namespace OCA\IntraVox\Service;

use OCP\Comments\IComment;
use OCP\Comments\ICommentsManager;
use OCP\Comments\NotFoundException as CommentNotFoundException;
use OCP\IUserManager;
use OCP\IUserSession;
use Psr\Log\LoggerInterface;

/**
 * Service wrapper around Nextcloud's ICommentsManager for IntraVox pages
 *
 * Provides a clean API for:
 * - Comments (create, read, update, delete)
 * - Replies (1 level deep threading)
 * - Emoji reactions on pages and comments
 */
class CommentService {
    public const OBJECT_TYPE = 'intravox_page';
    public const DEFAULT_LIMIT = 50;

    public function __construct(
        private ICommentsManager $commentsManager,
        private IUserSession $userSession,
        private IUserManager $userManager,
        private EngagementSettingsService $engagementSettings,
        private LoggerInterface $logger
    ) {}

    /**
     * Get current user ID
     */
    private function getCurrentUserId(): ?string {
        $user = $this->userSession->getUser();
        return $user?->getUID();
    }

    /**
     * Get comments for a page (with replies nested)
     *
     * @param string $pageId The uniqueId of the page
     * @param int $limit Maximum number of comments
     * @param int $offset Pagination offset
     * @return array Array of comments with nested replies
     */
    public function getComments(string $pageId, int $limit = self::DEFAULT_LIMIT, int $offset = 0): array {
        $comments = $this->commentsManager->getForObject(
            self::OBJECT_TYPE,
            $pageId,
            $limit,
            $offset
        );

        $result = [];
        foreach ($comments as $comment) {
            // Skip comments that are reactions (verb = 'reaction')
            if ($comment->getVerb() === 'reaction') {
                continue;
            }

            // Only include top-level comments (no parent)
            if ($comment->getParentId() === '0' || $comment->getParentId() === '') {
                $result[] = $this->formatComment($comment, true);
            }
        }

        return $result;
    }

    /**
     * Get a single comment by ID
     */
    public function getComment(string $commentId): ?array {
        try {
            $comment = $this->commentsManager->get($commentId);
            return $this->formatComment($comment, true);
        } catch (CommentNotFoundException $e) {
            return null;
        }
    }

    /**
     * Get the page ID (objectId) that a comment belongs to
     * Used for IDOR prevention - ensures user has access to page before operating on comment
     *
     * @param string $commentId The comment ID
     * @return string|null The page uniqueId, or null if comment not found
     */
    public function getCommentPageId(string $commentId): ?string {
        try {
            $comment = $this->commentsManager->get($commentId);
            return $comment->getObjectId();
        } catch (CommentNotFoundException $e) {
            return null;
        }
    }

    /**
     * Create a new comment on a page
     *
     * @param string $pageId The uniqueId of the page
     * @param string $message The comment message
     * @param string|null $parentId Parent comment ID for replies (optional)
     * @param string|null $userId User ID (for imports, otherwise uses current user)
     * @return array The created comment
     */
    public function createComment(string $pageId, string $message, ?string $parentId = null, ?string $userId = null): array {
        // Use provided userId or get current user
        if ($userId === null) {
            $userId = $this->getCurrentUserId();
            if ($userId === null) {
                throw new \RuntimeException('User not logged in');
            }
        } else {
            // Verify user exists when importing
            $user = $this->userManager->get($userId);
            if ($user === null) {
                $this->logger->warning('User not found for import, using current user', [
                    'importUserId' => $userId
                ]);
                $userId = $this->getCurrentUserId();
                if ($userId === null) {
                    throw new \RuntimeException('User not logged in');
                }
            }
        }

        $comment = $this->commentsManager->create('users', $userId, self::OBJECT_TYPE, $pageId);
        $comment->setMessage(trim($message));
        $comment->setVerb('comment');

        if ($parentId !== null && $parentId !== '' && $parentId !== '0') {
            $comment->setParentId($parentId);
        }

        $this->commentsManager->save($comment);

        $this->logger->info('IntraVox: Comment created', [
            'commentId' => $comment->getId(),
            'pageId' => $pageId,
            'userId' => $userId,
            'hasParent' => $parentId !== null
        ]);

        return $this->formatComment($comment, false);
    }

    /**
     * Update an existing comment
     *
     * @param string $commentId The comment ID
     * @param string $message The new message
     * @return array The updated comment
     */
    public function updateComment(string $commentId, string $message): array {
        $userId = $this->getCurrentUserId();
        if ($userId === null) {
            throw new \RuntimeException('User not logged in');
        }

        try {
            $comment = $this->commentsManager->get($commentId);
        } catch (CommentNotFoundException $e) {
            throw new \RuntimeException('Comment not found');
        }

        // Check ownership
        if ($comment->getActorId() !== $userId) {
            throw new \RuntimeException('Not authorized to edit this comment');
        }

        $comment->setMessage(trim($message));
        $this->commentsManager->save($comment);

        return $this->formatComment($comment, false);
    }

    /**
     * Delete a comment and all its children (replies and reactions)
     *
     * @param string $commentId The comment ID
     * @param bool $isAdmin Whether the current user is admin
     */
    public function deleteComment(string $commentId, bool $isAdmin = false): void {
        $userId = $this->getCurrentUserId();
        if ($userId === null) {
            throw new \RuntimeException('User not logged in');
        }

        try {
            $comment = $this->commentsManager->get($commentId);
        } catch (CommentNotFoundException $e) {
            throw new \RuntimeException('Comment not found');
        }

        // Check ownership or admin
        if ($comment->getActorId() !== $userId && !$isAdmin) {
            throw new \RuntimeException('Not authorized to delete this comment');
        }

        // Get page ID before deleting
        $pageId = $comment->getObjectId();

        // Delete all child comments first (replies and reactions)
        $deletedCount = $this->deleteChildComments($pageId, $commentId);

        // Delete the parent comment
        $this->commentsManager->delete($commentId);

        $this->logger->info('IntraVox: Comment deleted with children', [
            'commentId' => $commentId,
            'userId' => $userId,
            'isAdmin' => $isAdmin,
            'childrenDeleted' => $deletedCount
        ]);
    }

    /**
     * Recursively delete all child comments/reactions of a parent
     *
     * @param string $pageId The page ID
     * @param string $parentId The parent comment ID
     * @return int Number of children deleted
     */
    private function deleteChildComments(string $pageId, string $parentId): int {
        $allComments = $this->commentsManager->getForObject(
            self::OBJECT_TYPE,
            $pageId,
            1000,
            0
        );

        $deletedCount = 0;
        foreach ($allComments as $comment) {
            if ($comment->getParentId() === $parentId) {
                // Recursively delete children of this child first
                $deletedCount += $this->deleteChildComments($pageId, $comment->getId());
                // Delete this child (reply or reaction)
                $this->commentsManager->delete($comment->getId());
                $deletedCount++;
            }
        }

        return $deletedCount;
    }

    /**
     * Get reactions for a page
     *
     * @param string $pageId The uniqueId of the page
     * @return array Aggregated reactions ['emoji' => count, ...]
     */
    public function getPageReactions(string $pageId): array {
        // Page-level reactions are stored as comments with verb='reaction'
        // and no parent, where the message is the emoji
        $comments = $this->commentsManager->getForObject(
            self::OBJECT_TYPE,
            $pageId,
            1000,  // High limit to get all reactions
            0
        );

        $reactions = [];
        $userReactions = [];
        $userId = $this->getCurrentUserId();

        foreach ($comments as $comment) {
            if ($comment->getVerb() === 'reaction' &&
                ($comment->getParentId() === '0' || $comment->getParentId() === '')) {
                $emoji = $comment->getMessage();
                if (!isset($reactions[$emoji])) {
                    $reactions[$emoji] = 0;
                }
                $reactions[$emoji]++;

                // Track if current user has this reaction
                if ($userId !== null && $comment->getActorId() === $userId) {
                    $userReactions[] = $emoji;
                }
            }
        }

        return [
            'reactions' => $reactions,
            'userReactions' => $userReactions
        ];
    }

    /**
     * Add a reaction to a page
     *
     * @param string $pageId The uniqueId of the page
     * @param string $emoji The emoji reaction
     * @return array Updated reactions
     */
    public function addPageReaction(string $pageId, string $emoji): array {
        $userId = $this->getCurrentUserId();
        if ($userId === null) {
            throw new \RuntimeException('User not logged in');
        }

        // Check if user already has this reaction
        $existing = $this->findUserReaction($pageId, null, $emoji);
        if ($existing !== null) {
            // Already exists, return current state
            return $this->getPageReactions($pageId);
        }

        // In single reaction mode: remove any existing reaction from this user first
        if ($this->engagementSettings->getSingleReactionPerUser()) {
            $this->removeAllUserPageReactions($pageId);
        }

        // Create reaction as a special comment
        $comment = $this->commentsManager->create('users', $userId, self::OBJECT_TYPE, $pageId);
        $comment->setMessage($emoji);
        $comment->setVerb('reaction');
        $this->commentsManager->save($comment);

        return $this->getPageReactions($pageId);
    }

    /**
     * Remove all reactions from current user on a page (used in single reaction mode)
     */
    private function removeAllUserPageReactions(string $pageId): void {
        $userId = $this->getCurrentUserId();
        if ($userId === null) {
            return;
        }

        $comments = $this->commentsManager->getForObject(
            self::OBJECT_TYPE,
            $pageId,
            1000,
            0
        );

        foreach ($comments as $comment) {
            if ($comment->getVerb() === 'reaction' &&
                $comment->getActorId() === $userId &&
                ($comment->getParentId() === '0' || $comment->getParentId() === '')) {
                $this->commentsManager->delete($comment->getId());
            }
        }
    }

    /**
     * Remove a reaction from a page
     *
     * @param string $pageId The uniqueId of the page
     * @param string $emoji The emoji reaction
     * @return array Updated reactions
     */
    public function removePageReaction(string $pageId, string $emoji): array {
        $userId = $this->getCurrentUserId();
        if ($userId === null) {
            throw new \RuntimeException('User not logged in');
        }

        $existing = $this->findUserReaction($pageId, null, $emoji);
        if ($existing !== null) {
            $this->commentsManager->delete($existing->getId());
        }

        return $this->getPageReactions($pageId);
    }

    /**
     * Get reactions for a specific comment
     *
     * @param string $commentId The comment ID
     * @param bool $forceRefresh Force fresh data from database
     * @return array Aggregated reactions
     */
    public function getCommentReactions(string $commentId, bool $forceRefresh = false): array {
        // First get the comment to find its object info
        try {
            $comment = $this->commentsManager->get($commentId);
        } catch (CommentNotFoundException $e) {
            return ['reactions' => [], 'userReactions' => []];
        }

        $reactions = [];
        $userReactions = [];
        $userId = $this->getCurrentUserId();

        if ($this->commentsManager->supportReactions()) {
            // Use retrieveAllReactions to get fresh data from database
            // This is more reliable than getReactions() which may be cached
            try {
                $reactionComments = $this->commentsManager->retrieveAllReactions((int)$commentId);

                foreach ($reactionComments as $reactionComment) {
                    $emoji = $reactionComment->getMessage();
                    if (!isset($reactions[$emoji])) {
                        $reactions[$emoji] = 0;
                    }
                    $reactions[$emoji]++;

                    // Track if current user has this reaction
                    if ($userId !== null && $reactionComment->getActorId() === $userId) {
                        if (!in_array($emoji, $userReactions, true)) {
                            $userReactions[] = $emoji;
                        }
                    }
                }
            } catch (\Exception $e) {
                // Fallback to getReactions() if retrieveAllReactions fails
                try {
                    $reactions = $comment->getReactions();

                    // Determine user reactions
                    if ($userId !== null && !empty($reactions)) {
                        foreach (array_keys($reactions) as $emoji) {
                            try {
                                $emojiStr = (string)$emoji;
                                $reaction = $this->commentsManager->getReactionComment(
                                    (int)$commentId,
                                    'users',
                                    $userId,
                                    $emojiStr
                                );
                                if ($reaction !== null) {
                                    $userReactions[] = $emojiStr;
                                }
                            } catch (\Exception $e2) {
                                // User doesn't have this reaction
                            }
                        }
                    }
                } catch (\Exception $e2) {
                    // No reactions available
                }
            }
        }

        return [
            'reactions' => $reactions,
            'userReactions' => $userReactions
        ];
    }

    /**
     * Add a reaction to a comment
     */
    public function addCommentReaction(string $commentId, string $emoji): array {
        $userId = $this->getCurrentUserId();
        if ($userId === null) {
            throw new \RuntimeException('User not logged in');
        }

        if ($this->commentsManager->supportReactions()) {
            try {
                // First check if user already has this reaction
                try {
                    $existing = $this->commentsManager->getReactionComment(
                        (int)$commentId,
                        'users',
                        $userId,
                        $emoji
                    );
                    // Already exists, just return current state
                    if ($existing !== null) {
                        return $this->getCommentReactions($commentId);
                    }
                } catch (\OCP\Comments\NotFoundException $e) {
                    // Good - reaction doesn't exist yet, we can add it
                }

                // In single reaction mode: remove any existing reaction from this user first
                if ($this->engagementSettings->getSingleReactionPerUser()) {
                    $this->removeAllUserCommentReactions($commentId);
                }

                $comment = $this->commentsManager->get($commentId);
                // Use the native reaction system - this creates a reaction comment internally
                $reactionComment = $this->commentsManager->create(
                    'users',
                    $userId,
                    $comment->getObjectType(),
                    $comment->getObjectId()
                );
                $reactionComment->setMessage($emoji);
                $reactionComment->setVerb('reaction');
                $reactionComment->setParentId($commentId);
                $this->commentsManager->save($reactionComment);
            } catch (\Exception $e) {
                $this->logger->warning('IntraVox: Failed to add comment reaction', [
                    'commentId' => $commentId,
                    'emoji' => $emoji,
                    'error' => $e->getMessage()
                ]);
            }
        }

        return $this->getCommentReactions($commentId);
    }

    /**
     * Remove all reactions from current user on a comment (used in single reaction mode)
     */
    private function removeAllUserCommentReactions(string $commentId): void {
        $userId = $this->getCurrentUserId();
        if ($userId === null || !$this->commentsManager->supportReactions()) {
            return;
        }

        try {
            $comment = $this->commentsManager->get($commentId);
            $reactions = $comment->getReactions();

            $this->logger->debug('IntraVox: removeAllUserCommentReactions', [
                'commentId' => $commentId,
                'userId' => $userId,
                'reactions' => $reactions
            ]);

            foreach (array_keys($reactions) as $emoji) {
                try {
                    $emojiStr = (string)$emoji;
                    $reactionComment = $this->commentsManager->getReactionComment(
                        (int)$commentId,
                        'users',
                        $userId,
                        $emojiStr
                    );
                    if ($reactionComment !== null) {
                        $this->logger->debug('IntraVox: Removing user reaction', [
                            'commentId' => $commentId,
                            'emoji' => $emojiStr,
                            'reactionId' => $reactionComment->getId()
                        ]);
                        $this->commentsManager->delete($reactionComment->getId());
                    }
                } catch (\OCP\Comments\NotFoundException $e) {
                    // User doesn't have this reaction, continue
                } catch (\Exception $e) {
                    $this->logger->warning('IntraVox: Error removing reaction', [
                        'error' => $e->getMessage()
                    ]);
                }
            }
        } catch (\Exception $e) {
            $this->logger->warning('IntraVox: removeAllUserCommentReactions failed', [
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Remove a reaction from a comment
     */
    public function removeCommentReaction(string $commentId, string $emoji): array {
        $userId = $this->getCurrentUserId();
        if ($userId === null) {
            throw new \RuntimeException('User not logged in');
        }

        // Use the native NC API to find and delete the reaction
        if ($this->commentsManager->supportReactions()) {
            try {
                $reactionComment = $this->commentsManager->getReactionComment(
                    (int)$commentId,
                    'users',
                    $userId,
                    $emoji
                );
                if ($reactionComment !== null) {
                    $this->commentsManager->delete($reactionComment->getId());
                }
            } catch (\Exception $e) {
                $this->logger->warning('IntraVox: Failed to remove comment reaction', [
                    'commentId' => $commentId,
                    'emoji' => $emoji,
                    'error' => $e->getMessage()
                ]);
            }
        }

        return $this->getCommentReactions($commentId);
    }

    /**
     * Delete all comments for a page (cleanup when page is deleted)
     */
    public function deleteAllCommentsForPage(string $pageId): void {
        $this->commentsManager->deleteCommentsAtObject(self::OBJECT_TYPE, $pageId);

        $this->logger->info('IntraVox: All comments deleted for page', [
            'pageId' => $pageId
        ]);
    }

    /**
     * Get comment count for a page
     */
    public function getCommentCount(string $pageId): int {
        return $this->commentsManager->getNumberOfCommentsForObject(
            self::OBJECT_TYPE,
            $pageId
        );
    }

    /**
     * Find a user's specific reaction
     */
    private function findUserReaction(string $pageId, ?string $parentId, string $emoji): ?IComment {
        $userId = $this->getCurrentUserId();
        if ($userId === null) {
            return null;
        }

        $comments = $this->commentsManager->getForObject(
            self::OBJECT_TYPE,
            $pageId,
            1000,
            0
        );

        foreach ($comments as $comment) {
            if ($comment->getVerb() === 'reaction' &&
                $comment->getActorId() === $userId &&
                $comment->getMessage() === $emoji) {
                // Check parent match
                $commentParent = $comment->getParentId();
                if ($parentId === null && ($commentParent === '0' || $commentParent === '')) {
                    return $comment;
                }
                if ($parentId !== null && $commentParent === $parentId) {
                    return $comment;
                }
            }
        }

        return null;
    }

    /**
     * Format a comment for API response
     */
    private function formatComment(IComment $comment, bool $includeReplies = true): array {
        $userId = $comment->getActorId();
        $user = $this->userManager->get($userId);
        $displayName = $user?->getDisplayName() ?? $userId;
        $currentUserId = $this->getCurrentUserId();

        $result = [
            'id' => $comment->getId(),
            'message' => $comment->getMessage(),
            'userId' => $userId,
            'displayName' => $displayName,
            'createdAt' => $comment->getCreationDateTime()->format('c'),
            'isEdited' => $comment->getCreationDateTime() != $comment->getLatestChildDateTime(),
            'parentId' => $comment->getParentId(),
            'reactions' => [],
            'userReactions' => []
        ];

        // Add reactions - use retrieveAllReactions for fresh data
        if ($this->commentsManager->supportReactions()) {
            try {
                $reactionComments = $this->commentsManager->retrieveAllReactions((int)$comment->getId());

                foreach ($reactionComments as $reactionComment) {
                    $emoji = $reactionComment->getMessage();
                    if (!isset($result['reactions'][$emoji])) {
                        $result['reactions'][$emoji] = 0;
                    }
                    $result['reactions'][$emoji]++;

                    // Track if current user has this reaction
                    if ($currentUserId !== null && $reactionComment->getActorId() === $currentUserId) {
                        if (!in_array($emoji, $result['userReactions'], true)) {
                            $result['userReactions'][] = $emoji;
                        }
                    }
                }
            } catch (\Exception $e) {
                // Fallback to getReactions() if retrieveAllReactions fails
                try {
                    $reactions = $comment->getReactions();
                    if (!empty($reactions)) {
                        $result['reactions'] = $reactions;

                        if ($currentUserId !== null) {
                            $commentId = (int)$comment->getId();
                            foreach (array_keys($reactions) as $emoji) {
                                try {
                                    $emojiStr = (string)$emoji;
                                    $reaction = $this->commentsManager->getReactionComment(
                                        $commentId,
                                        'users',
                                        $currentUserId,
                                        $emojiStr
                                    );
                                    if ($reaction !== null) {
                                        $result['userReactions'][] = $emojiStr;
                                    }
                                } catch (\Exception $e2) {
                                    // User doesn't have this reaction
                                }
                            }
                        }
                    }
                } catch (\Exception $e2) {
                    // No reactions available
                }
            }
        }

        // Add replies (only for top-level comments, 1 level deep)
        if ($includeReplies && ($comment->getParentId() === '0' || $comment->getParentId() === '')) {
            $result['replies'] = $this->getReplies($comment);
        }

        return $result;
    }

    /**
     * Get replies for a comment (1 level deep)
     *
     * Note: We don't use getTree() because it has issues with string IDs.
     * Instead, we filter from getForObject() which is more reliable.
     */
    private function getReplies(IComment $parent): array {
        $parentId = $parent->getId();

        // Get all comments for this page and filter for replies to this parent
        $allComments = $this->commentsManager->getForObject(
            $parent->getObjectType(),
            $parent->getObjectId(),
            1000,  // High limit to get all
            0
        );

        $replies = [];
        foreach ($allComments as $comment) {
            // Check if this comment is a reply to the parent (not a reaction)
            if ($comment->getParentId() === $parentId && $comment->getVerb() !== 'reaction') {
                $replies[] = $this->formatComment($comment, false);
            }
        }

        return $replies;
    }
}
