<?php
declare(strict_types=1);

namespace OCA\IntraVox\Controller;

use OCA\IntraVox\Service\CommentService;
use OCA\IntraVox\Service\PageService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\DataResponse;
use OCP\IGroupManager;
use OCP\IRequest;
use OCP\IUserSession;
use Psr\Log\LoggerInterface;

/**
 * Controller for IntraVox page comments and reactions
 *
 * REST API wrapper around Nextcloud's Comments API
 */
class CommentController extends Controller {
    public function __construct(
        string $appName,
        IRequest $request,
        private CommentService $commentService,
        private PageService $pageService,
        private IUserSession $userSession,
        private IGroupManager $groupManager,
        private LoggerInterface $logger
    ) {
        parent::__construct($appName, $request);
    }

    /**
     * Check if current user is admin
     */
    private function isAdmin(): bool {
        $user = $this->userSession->getUser();
        if ($user === null) {
            return false;
        }
        return $this->groupManager->isAdmin($user->getUID());
    }

    /**
     * Check if page exists and user has read access
     */
    private function checkPageAccess(string $pageId): bool {
        return $this->pageService->pageExistsByUniqueId($pageId);
    }

    // ==================== COMMENTS ====================

    /**
     * Get comments for a page
     *
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    public function getComments(string $pageId, int $limit = 50, int $offset = 0): DataResponse {
        try {
            if (!$this->checkPageAccess($pageId)) {
                return new DataResponse(
                    ['error' => 'Page not found'],
                    Http::STATUS_NOT_FOUND
                );
            }

            $comments = $this->commentService->getComments($pageId, $limit, $offset);
            $count = $this->commentService->getCommentCount($pageId);

            return new DataResponse([
                'comments' => $comments,
                'total' => $count
            ]);
        } catch (\Exception $e) {
            $this->logger->error('IntraVox: Error getting comments', [
                'pageId' => $pageId,
                'error' => $e->getMessage()
            ]);
            return new DataResponse(
                ['error' => $e->getMessage()],
                Http::STATUS_INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     * Create a new comment
     *
     * @NoAdminRequired
     */
    public function createComment(string $pageId, string $message, ?string $parentId = null): DataResponse {
        try {
            if (!$this->checkPageAccess($pageId)) {
                return new DataResponse(
                    ['error' => 'Page not found'],
                    Http::STATUS_NOT_FOUND
                );
            }

            if (empty(trim($message))) {
                return new DataResponse(
                    ['error' => 'Message cannot be empty'],
                    Http::STATUS_BAD_REQUEST
                );
            }

            $comment = $this->commentService->createComment($pageId, $message, $parentId);

            return new DataResponse($comment, Http::STATUS_CREATED);
        } catch (\Exception $e) {
            $this->logger->error('IntraVox: Error creating comment', [
                'pageId' => $pageId,
                'error' => $e->getMessage()
            ]);
            return new DataResponse(
                ['error' => $e->getMessage()],
                Http::STATUS_INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     * Update an existing comment
     *
     * @NoAdminRequired
     */
    public function updateComment(string $commentId, string $message): DataResponse {
        try {
            if (empty(trim($message))) {
                return new DataResponse(
                    ['error' => 'Message cannot be empty'],
                    Http::STATUS_BAD_REQUEST
                );
            }

            $comment = $this->commentService->updateComment($commentId, $message);

            return new DataResponse($comment);
        } catch (\RuntimeException $e) {
            $status = match ($e->getMessage()) {
                'Comment not found' => Http::STATUS_NOT_FOUND,
                'Not authorized to edit this comment' => Http::STATUS_FORBIDDEN,
                default => Http::STATUS_INTERNAL_SERVER_ERROR
            };
            return new DataResponse(['error' => $e->getMessage()], $status);
        } catch (\Exception $e) {
            $this->logger->error('IntraVox: Error updating comment', [
                'commentId' => $commentId,
                'error' => $e->getMessage()
            ]);
            return new DataResponse(
                ['error' => $e->getMessage()],
                Http::STATUS_INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     * Delete a comment
     *
     * @NoAdminRequired
     */
    public function deleteComment(string $commentId): DataResponse {
        try {
            $this->commentService->deleteComment($commentId, $this->isAdmin());

            return new DataResponse(['success' => true]);
        } catch (\RuntimeException $e) {
            $status = match ($e->getMessage()) {
                'Comment not found' => Http::STATUS_NOT_FOUND,
                'Not authorized to delete this comment' => Http::STATUS_FORBIDDEN,
                default => Http::STATUS_INTERNAL_SERVER_ERROR
            };
            return new DataResponse(['error' => $e->getMessage()], $status);
        } catch (\Exception $e) {
            $this->logger->error('IntraVox: Error deleting comment', [
                'commentId' => $commentId,
                'error' => $e->getMessage()
            ]);
            return new DataResponse(
                ['error' => $e->getMessage()],
                Http::STATUS_INTERNAL_SERVER_ERROR
            );
        }
    }

    // ==================== PAGE REACTIONS ====================

    /**
     * Get reactions for a page
     *
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    public function getPageReactions(string $pageId): DataResponse {
        try {
            if (!$this->checkPageAccess($pageId)) {
                return new DataResponse(
                    ['error' => 'Page not found'],
                    Http::STATUS_NOT_FOUND
                );
            }

            $reactions = $this->commentService->getPageReactions($pageId);

            return new DataResponse($reactions);
        } catch (\Exception $e) {
            $this->logger->error('IntraVox: Error getting page reactions', [
                'pageId' => $pageId,
                'error' => $e->getMessage()
            ]);
            return new DataResponse(
                ['error' => $e->getMessage()],
                Http::STATUS_INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     * Add a reaction to a page
     *
     * @NoAdminRequired
     */
    public function addPageReaction(string $pageId, string $emoji): DataResponse {
        try {
            if (!$this->checkPageAccess($pageId)) {
                return new DataResponse(
                    ['error' => 'Page not found'],
                    Http::STATUS_NOT_FOUND
                );
            }

            $reactions = $this->commentService->addPageReaction($pageId, $emoji);

            return new DataResponse($reactions);
        } catch (\Exception $e) {
            $this->logger->error('IntraVox: Error adding page reaction', [
                'pageId' => $pageId,
                'emoji' => $emoji,
                'error' => $e->getMessage()
            ]);
            return new DataResponse(
                ['error' => $e->getMessage()],
                Http::STATUS_INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     * Remove a reaction from a page
     *
     * @NoAdminRequired
     */
    public function removePageReaction(string $pageId, string $emoji): DataResponse {
        try {
            if (!$this->checkPageAccess($pageId)) {
                return new DataResponse(
                    ['error' => 'Page not found'],
                    Http::STATUS_NOT_FOUND
                );
            }

            $reactions = $this->commentService->removePageReaction($pageId, $emoji);

            return new DataResponse($reactions);
        } catch (\Exception $e) {
            $this->logger->error('IntraVox: Error removing page reaction', [
                'pageId' => $pageId,
                'emoji' => $emoji,
                'error' => $e->getMessage()
            ]);
            return new DataResponse(
                ['error' => $e->getMessage()],
                Http::STATUS_INTERNAL_SERVER_ERROR
            );
        }
    }

    // ==================== COMMENT REACTIONS ====================

    /**
     * Get reactions for a comment
     *
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    public function getCommentReactions(string $commentId): DataResponse {
        try {
            $reactions = $this->commentService->getCommentReactions($commentId);

            return new DataResponse($reactions);
        } catch (\Exception $e) {
            $this->logger->error('IntraVox: Error getting comment reactions', [
                'commentId' => $commentId,
                'error' => $e->getMessage()
            ]);
            return new DataResponse(
                ['error' => $e->getMessage()],
                Http::STATUS_INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     * Add a reaction to a comment
     *
     * @NoAdminRequired
     */
    public function addCommentReaction(string $commentId, string $emoji): DataResponse {
        try {
            $reactions = $this->commentService->addCommentReaction($commentId, $emoji);

            return new DataResponse($reactions);
        } catch (\Exception $e) {
            $this->logger->error('IntraVox: Error adding comment reaction', [
                'commentId' => $commentId,
                'emoji' => $emoji,
                'error' => $e->getMessage()
            ]);
            return new DataResponse(
                ['error' => $e->getMessage()],
                Http::STATUS_INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     * Remove a reaction from a comment
     *
     * @NoAdminRequired
     */
    public function removeCommentReaction(string $commentId, string $emoji): DataResponse {
        try {
            $reactions = $this->commentService->removeCommentReaction($commentId, $emoji);

            return new DataResponse($reactions);
        } catch (\Exception $e) {
            $this->logger->error('IntraVox: Error removing comment reaction', [
                'commentId' => $commentId,
                'emoji' => $emoji,
                'error' => $e->getMessage()
            ]);
            return new DataResponse(
                ['error' => $e->getMessage()],
                Http::STATUS_INTERNAL_SERVER_ERROR
            );
        }
    }
}
