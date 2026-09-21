<?php
declare(strict_types=1);

namespace OCA\IntraVox\Controller;

use OCA\IntraVox\Service\CommentService;
use OCA\IntraVox\Service\EngagementSettingsService;
use OCA\IntraVox\Service\Read\PageReadService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\UserRateLimit;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\Attribute\NoCSRFRequired;
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
    use ChecksAdminAccess;
    use ApiErrorTrait;
    public function __construct(
        string $appName,
        IRequest $request,
        private CommentService $commentService,
        private PageReadService $pageRead,
        private EngagementSettingsService $engagementSettings,
        private IUserSession $userSession,
        private IGroupManager $groupManager,
        private LoggerInterface $logger,
        private ?\OCA\IntraVox\Service\Publication\PublicationStateService $publicationState = null
    ) {
        parent::__construct($appName, $request);
    }

    /** Required by ApiErrorTrait. */
    protected function getLogger(): LoggerInterface {
        return $this->logger;
    }


    /**
     * Whether the caller may read/comment on this page.
     *
     * Existence via pageExistsByUniqueId is already ACL-correct (it walks the
     * caller's own view, so an ACL-hidden page is not found). This adds the
     * publication gate the page API enforces but this path skipped: a draft,
     * scheduled or expired page is hidden from readers, so a read-only user must
     * not read or post comments on it. An editor (canWrite) still can — they see
     * the page in the editor, and pre-publication review comments are the point.
     */
    private function checkPageAccess(string $pageId): bool {
        // No publication service (legacy/unit construction) → keep the prior
        // existence-only behaviour rather than fail the whole comment surface.
        if ($this->publicationState === null) {
            return $this->pageRead->pageExistsByUniqueId($pageId);
        }

        try {
            $page = $this->pageRead->getPage($pageId);
        } catch (\Exception $e) {
            return false; // not found / no access in the caller's view
        }

        if (!($page['permissions']['canRead'] ?? false)) {
            return false;
        }
        if (!$this->publicationState->isHiddenFromReaders($page)) {
            return true; // published — any reader may comment
        }
        // Hidden from readers: only someone who may edit the page may see it.
        return (bool)($page['permissions']['canWrite'] ?? false);
    }

    /**
     * The page's own engagement settings, or null when they cannot be read.
     *
     * Null means "no page-level opinion", which is what
     * EngagementSettingsService already treats as "inherit the global
     * setting" — so a page that fails to load here falls back to the
     * admin's choice rather than silently allowing what the page forbade.
     */
    private function pageEngagementSettings(string $pageId): ?array {
        try {
            $page = $this->pageRead->getPage($pageId);
        } catch (\Exception $e) {
            return null;
        }
        return isset($page['settings']) && is_array($page['settings'])
            ? $page['settings']
            : null;
    }

    /**
     * Whether writing engagement is allowed on this page.
     *
     * The viewer hides these controls when a page turns them off, but hiding
     * a button is not a rule: until these checks existed, anyone who could
     * read a page could POST to it directly and write a comment the page had
     * disabled. The decision itself lives in EngagementSettingsService, which
     * weighs the global setting against the page-level override; these are
     * only the places that ask it.
     *
     * Three named methods rather than one with a $kind argument: the compiler
     * then rejects a caller that asks the wrong question, and there is no
     * string to mistype.
     */
    private function mayComment(string $pageId): bool {
        return $this->engagementSettings->areCommentsAllowedForPage(
            $this->pageEngagementSettings($pageId)
        );
    }

    private function mayReactToPage(string $pageId): bool {
        return $this->engagementSettings->areReactionsAllowedForPage(
            $this->pageEngagementSettings($pageId)
        );
    }

    private function mayReactToComment(string $pageId): bool {
        return $this->engagementSettings->areCommentReactionsAllowedForPage(
            $this->pageEngagementSettings($pageId)
        );
    }

    /** The response for engagement the page or the admin has switched off. */
    private function engagementDisabled(string $what): DataResponse {
        return new DataResponse(
            ['error' => $what . ' are disabled for this page'],
            Http::STATUS_FORBIDDEN
        );
    }

    /**
     * Check if user has access to the page associated with a comment
     * Prevents IDOR by verifying page access before any comment operation
     *
     * @param string $commentId Comment ID to check
     * @return bool True if user has access, false otherwise
     */
    private function checkCommentPageAccess(string $commentId): bool {
        $pageId = $this->commentService->getCommentPageId($commentId);
        if ($pageId === null) {
            return false; // Comment not found
        }
        return $this->checkPageAccess($pageId);
    }

    // ==================== COMMENTS ====================

    /**
     * Get comments for a page
     *
     */
    #[NoAdminRequired]
    #[NoCSRFRequired]
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
            return $this->safeErrorResponse(
                $e,
                'Could not load comments.',
                Http::STATUS_INTERNAL_SERVER_ERROR,
                [
                    'pageId' => $pageId,
                ]
            );
        }
    }

    /**
     * Create a new comment
     *
     */
    #[UserRateLimit(limit: 20, period: 60)]
    #[NoAdminRequired]
    public function createComment(string $pageId, string $message, ?string $parentId = null): DataResponse {
        try {
            if (!$this->checkPageAccess($pageId)) {
                return new DataResponse(
                    ['error' => 'Page not found'],
                    Http::STATUS_NOT_FOUND
                );
            }

            if (!$this->mayComment($pageId)) {
                return $this->engagementDisabled('Comments');
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
            return $this->safeErrorResponse(
                $e,
                'Could not post that comment.',
                Http::STATUS_INTERNAL_SERVER_ERROR,
                [
                    'pageId' => $pageId,
                ]
            );
        }
    }

    /**
     * Update an existing comment
     *
     */
    #[NoAdminRequired]
    public function updateComment(string $commentId, string $message): DataResponse {
        try {
            // Security: verify user has access to the page this comment belongs to
            if (!$this->checkCommentPageAccess($commentId)) {
                return new DataResponse(
                    ['error' => 'Comment not found or access denied'],
                    Http::STATUS_NOT_FOUND
                );
            }

            if (empty(trim($message))) {
                return new DataResponse(
                    ['error' => 'Message cannot be empty'],
                    Http::STATUS_BAD_REQUEST
                );
            }

            $comment = $this->commentService->updateComment($commentId, $message);

            return new DataResponse($comment);
        } catch (\RuntimeException $e) {
            // Only these two strings are contract: this app throws them and
            // clients route on them, so they stay verbatim. Anything else
            // reaching here is an unrecognised failure and gets the same
            // generic treatment as the catch below -- the default branch used
            // to return its message, which was the leak with a status code.
            $status = match ($e->getMessage()) {
                'Comment not found' => Http::STATUS_NOT_FOUND,
                'Not authorized to edit this comment' => Http::STATUS_FORBIDDEN,
                default => null
            };

            if ($status === null) {
                return $this->safeErrorResponse(
                    $e,
                    'Could not edit that comment.',
                    Http::STATUS_INTERNAL_SERVER_ERROR,
                    [
                        'commentId' => $commentId,
                    ]
                );
            }

            return new DataResponse(['error' => $e->getMessage()], $status);
        } catch (\Exception $e) {
            return $this->safeErrorResponse(
                $e,
                'Could not update that comment.',
                Http::STATUS_INTERNAL_SERVER_ERROR,
                [
                    'commentId' => $commentId,
                ]
            );
        }
    }

    /**
     * Delete a comment
     *
     */
    #[NoAdminRequired]
    public function deleteComment(string $commentId): DataResponse {
        try {
            // Security: verify user has access to the page this comment belongs to
            if (!$this->checkCommentPageAccess($commentId)) {
                return new DataResponse(
                    ['error' => 'Comment not found or access denied'],
                    Http::STATUS_NOT_FOUND
                );
            }

            $this->commentService->deleteComment($commentId, $this->isAdmin());

            return new DataResponse(['success' => true]);
        } catch (\RuntimeException $e) {
            // Only these two strings are contract: this app throws them and
            // clients route on them, so they stay verbatim. Anything else
            // reaching here is an unrecognised failure and gets the same
            // generic treatment as the catch below -- the default branch used
            // to return its message, which was the leak with a status code.
            $status = match ($e->getMessage()) {
                'Comment not found' => Http::STATUS_NOT_FOUND,
                'Not authorized to delete this comment' => Http::STATUS_FORBIDDEN,
                default => null
            };

            if ($status === null) {
                return $this->safeErrorResponse(
                    $e,
                    'Could not delete that comment.',
                    Http::STATUS_INTERNAL_SERVER_ERROR,
                    [
                        'commentId' => $commentId,
                    ]
                );
            }

            return new DataResponse(['error' => $e->getMessage()], $status);
        } catch (\Exception $e) {
            return $this->safeErrorResponse(
                $e,
                'Could not delete that comment.',
                Http::STATUS_INTERNAL_SERVER_ERROR,
                [
                    'commentId' => $commentId,
                ]
            );
        }
    }

    // ==================== PAGE REACTIONS ====================

    /**
     * Get reactions for a page
     *
     */
    #[NoAdminRequired]
    #[NoCSRFRequired]
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
            return $this->safeErrorResponse(
                $e,
                'Could not load reactions.',
                Http::STATUS_INTERNAL_SERVER_ERROR,
                [
                    'pageId' => $pageId,
                ]
            );
        }
    }

    /**
     * Add a reaction to a page
     *
     */
    #[UserRateLimit(limit: 30, period: 60)]
    #[NoAdminRequired]
    public function addPageReaction(string $pageId, string $emoji): DataResponse {
        try {
            if (!$this->checkPageAccess($pageId)) {
                return new DataResponse(
                    ['error' => 'Page not found'],
                    Http::STATUS_NOT_FOUND
                );
            }

            if (!$this->mayReactToPage($pageId)) {
                return $this->engagementDisabled('Reactions');
            }

            $reactions = $this->commentService->addPageReaction($pageId, $emoji);

            return new DataResponse($reactions);
        } catch (\Exception $e) {
            return $this->safeErrorResponse(
                $e,
                'Could not add that reaction.',
                Http::STATUS_INTERNAL_SERVER_ERROR,
                [
                    'pageId' => $pageId,
                    'emoji' => $emoji,
                ]
            );
        }
    }

    /**
     * Remove a reaction from a page
     *
     */
    #[NoAdminRequired]
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
            return $this->safeErrorResponse(
                $e,
                'Could not remove that reaction.',
                Http::STATUS_INTERNAL_SERVER_ERROR,
                [
                    'pageId' => $pageId,
                    'emoji' => $emoji,
                ]
            );
        }
    }

    // ==================== COMMENT REACTIONS ====================

    /**
     * Get reactions for a comment
     *
     */
    #[NoAdminRequired]
    #[NoCSRFRequired]
    public function getCommentReactions(string $commentId): DataResponse {
        try {
            // Security: verify user has access to the page this comment belongs to
            if (!$this->checkCommentPageAccess($commentId)) {
                return new DataResponse(
                    ['error' => 'Comment not found or access denied'],
                    Http::STATUS_NOT_FOUND
                );
            }

            $reactions = $this->commentService->getCommentReactions($commentId);

            return new DataResponse($reactions);
        } catch (\Exception $e) {
            return $this->safeErrorResponse(
                $e,
                'Could not load reactions.',
                Http::STATUS_INTERNAL_SERVER_ERROR,
                [
                    'commentId' => $commentId,
                ]
            );
        }
    }

    /**
     * Add a reaction to a comment
     *
     */
    #[UserRateLimit(limit: 30, period: 60)]
    #[NoAdminRequired]
    public function addCommentReaction(string $commentId, string $emoji): DataResponse {
        try {
            // Security: verify user has access to the page this comment belongs to
            if (!$this->checkCommentPageAccess($commentId)) {
                return new DataResponse(
                    ['error' => 'Comment not found or access denied'],
                    Http::STATUS_NOT_FOUND
                );
            }

            // The page owning this comment decides, so resolve it first: the
            // route only carries the comment id.
            $pageId = $this->commentService->getCommentPageId($commentId);
            if ($pageId !== null && !$this->mayReactToComment($pageId)) {
                return $this->engagementDisabled('Comment reactions');
            }

            $reactions = $this->commentService->addCommentReaction($commentId, $emoji);

            return new DataResponse($reactions);
        } catch (\Exception $e) {
            return $this->safeErrorResponse(
                $e,
                'Could not add that reaction.',
                Http::STATUS_INTERNAL_SERVER_ERROR,
                [
                    'commentId' => $commentId,
                    'emoji' => $emoji,
                ]
            );
        }
    }

    /**
     * Remove a reaction from a comment
     *
     */
    #[NoAdminRequired]
    public function removeCommentReaction(string $commentId, string $emoji): DataResponse {
        try {
            // Security: verify user has access to the page this comment belongs to
            if (!$this->checkCommentPageAccess($commentId)) {
                return new DataResponse(
                    ['error' => 'Comment not found or access denied'],
                    Http::STATUS_NOT_FOUND
                );
            }

            $reactions = $this->commentService->removeCommentReaction($commentId, $emoji);

            return new DataResponse($reactions);
        } catch (\Exception $e) {
            return $this->safeErrorResponse(
                $e,
                'Could not remove that reaction.',
                Http::STATUS_INTERNAL_SERVER_ERROR,
                [
                    'commentId' => $commentId,
                    'emoji' => $emoji,
                ]
            );
        }
    }
}
