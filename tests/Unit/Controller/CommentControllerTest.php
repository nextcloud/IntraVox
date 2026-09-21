<?php
declare(strict_types=1);

namespace OCA\IntraVox\Tests\Unit\Controller;

use OCA\IntraVox\Controller\CommentController;
use OCA\IntraVox\Service\CommentService;
use OCA\IntraVox\Service\EngagementSettingsService;
use OCA\IntraVox\Tests\Mocks\MockGroupManager;
use OCA\IntraVox\Tests\Mocks\MockUserSession;
use OCA\IntraVox\Tests\Unit\Service\Harness\BuildsPageRead;
use OCP\AppFramework\Http;
use OCP\IRequest;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Unit tests for CommentController
 *
 * Tests cover:
 * - Comment CRUD operations
 * - Page reactions
 * - Comment reactions
 * - Permission/access checks
 */
class CommentControllerTest extends TestCase {
    use BuildsPageRead;

    private CommentController $controller;
    private CommentService $commentService;
    private EngagementSettingsService $engagementSettings;
    private MockUserSession $userSession;
    private MockGroupManager $groupManager;
    private LoggerInterface $logger;
    private IRequest $request;

    protected function setUp(): void {
        parent::setUp();

        $this->commentService = $this->createMock(CommentService::class);
        // Default: the admin allows engagement, so only a page-level
        // override can switch it off. Tests that need the global kill
        // switch build their own.
        $this->engagementSettings = $this->engagementAllowingAll();
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->request = $this->createMock(IRequest::class);

        $this->userSession = MockUserSession::loggedInAs('testuser');
        $this->groupManager = MockGroupManager::noAdmins();

        // Default: the page exists. Tests that need a missing page call
        // withPageExists(false). The existence probe now lives on PageReadService
        // (fase-9), which is final, so it is a real service rigged to answer.
        $this->withPageExists(true);
    }

    /** (Re)build the controller with a PageReadService whose page-existence probe answers $exists. */
    private function withPageExists(bool $exists): void {
        $this->controller = new CommentController(
            'intravox',
            $this->request,
            $this->commentService,
            $this->fakePageReadExisting($exists),
            $this->engagementSettings,
            $this->userSession,
            $this->groupManager,
            $this->logger
        );
    }

    /**
     * A real EngagementSettingsService over a stubbed IConfig.
     *
     * Real, not a mock: the point of these tests is the decision the service
     * makes about global-versus-page, so mocking it away would test nothing.
     * $global is what the admin set; every key shares it.
     */
    private function engagementWithGlobal(bool $global): EngagementSettingsService {
        $config = $this->createMock(\OCP\IConfig::class);
        $config->method('getAppValue')->willReturn($global ? '1' : '0');
        return new EngagementSettingsService($config);
    }

    /** The common case: the admin allows engagement app-wide. */
    private function engagementAllowingAll(): EngagementSettingsService {
        return $this->engagementWithGlobal(true);
    }

    /**
     * Build the controller over a page whose JSON carries $settings, with the
     * publication gate satisfied — so only the engagement settings can deny.
     */
    private function withPageSettings(?array $settings, ?EngagementSettingsService $engagement = null): void {
        $page = ['uniqueId' => 'page-123', 'permissions' => ['canRead' => true, 'canWrite' => false]];
        if ($settings !== null) {
            $page['settings'] = $settings;
        }
        $publication = $this->createMock(\OCA\IntraVox\Service\Publication\PublicationStateService::class);
        $publication->method('isHiddenFromReaders')->willReturn(false);

        $this->controller = new CommentController(
            'intravox',
            $this->request,
            $this->commentService,
            $this->fakePageReadReturning($page),
            $engagement ?? $this->engagementSettings,
            $this->userSession,
            $this->groupManager,
            $this->logger,
            $publication
        );
    }

    /**
     * Build the controller with a publication gate active and a fixed page.
     * $hidden = the page is draft/scheduled/expired; $canWrite = the caller may edit.
     */
    private function withPublicationPage(bool $hidden, bool $canWrite): void {
        $publication = $this->createMock(\OCA\IntraVox\Service\Publication\PublicationStateService::class);
        $publication->method('isHiddenFromReaders')->willReturn($hidden);

        $page = ['uniqueId' => 'page-123', 'permissions' => ['canRead' => true, 'canWrite' => $canWrite]];

        $this->controller = new CommentController(
            'intravox',
            $this->request,
            $this->commentService,
            $this->fakePageReadReturning($page),
            $this->engagementSettings,
            $this->userSession,
            $this->groupManager,
            $this->logger,
            $publication
        );
    }

    // IV-16: comments honour publication status, not just existence.

    public function testHiddenPageDeniesCommentsForReadOnlyUser(): void {
        $this->withPublicationPage(hidden: true, canWrite: false);
        $response = $this->controller->getComments('page-123');
        $this->assertEquals(Http::STATUS_NOT_FOUND, $response->getStatus());
    }

    public function testHiddenPageAllowsCommentsForAnEditor(): void {
        $this->withPublicationPage(hidden: true, canWrite: true);
        $this->commentService->method('getComments')->willReturn([]);
        $this->commentService->method('getCommentCount')->willReturn(0);
        $response = $this->controller->getComments('page-123');
        $this->assertEquals(Http::STATUS_OK, $response->getStatus());
    }

    public function testPublishedPageAllowsCommentsForReadOnlyUser(): void {
        $this->withPublicationPage(hidden: false, canWrite: false);
        $this->commentService->method('getComments')->willReturn([]);
        $this->commentService->method('getCommentCount')->willReturn(0);
        $response = $this->controller->getComments('page-123');
        $this->assertEquals(Http::STATUS_OK, $response->getStatus());
    }

    // ==========================================
    // getComments Tests
    // ==========================================

    public function testGetCommentsReturnsCommentsForValidPage(): void {

        $comments = [
            ['id' => '1', 'message' => 'First comment', 'author' => 'user1'],
            ['id' => '2', 'message' => 'Second comment', 'author' => 'user2']
        ];

        $this->commentService->method('getComments')
            ->with('page-123', 50, 0)
            ->willReturn($comments);

        $this->commentService->method('getCommentCount')
            ->with('page-123')
            ->willReturn(2);

        $response = $this->controller->getComments('page-123');

        $this->assertEquals(Http::STATUS_OK, $response->getStatus());
        $data = $response->getData();
        $this->assertCount(2, $data['comments']);
        $this->assertEquals(2, $data['total']);
    }

    public function testGetCommentsReturnsNotFoundForInvalidPage(): void {
        $this->withPageExists(false);

        $response = $this->controller->getComments('invalid-page');

        $this->assertEquals(Http::STATUS_NOT_FOUND, $response->getStatus());
        $this->assertEquals(['error' => 'Page not found'], $response->getData());
    }

    public function testGetCommentsWithPagination(): void {

        $this->commentService->method('getComments')
            ->with('page-123', 10, 20)
            ->willReturn([]);
        $this->commentService->method('getCommentCount')->willReturn(25);

        $response = $this->controller->getComments('page-123', 10, 20);

        $this->assertEquals(Http::STATUS_OK, $response->getStatus());
    }

    // ==========================================
    // createComment Tests
    // ==========================================

    public function testCreateCommentSuccessful(): void {


        $newComment = [
            'id' => '123',
            'message' => 'Test comment',
            'author' => 'testuser'
        ];

        $this->commentService->method('createComment')
            ->with('page-123', 'Test comment', null)
            ->willReturn($newComment);

        $response = $this->controller->createComment('page-123', 'Test comment');

        $this->assertEquals(Http::STATUS_CREATED, $response->getStatus());
        $this->assertEquals('Test comment', $response->getData()['message']);
    }

    public function testCreateCommentWithParentId(): void {


        $replyComment = [
            'id' => '456',
            'message' => 'Reply',
            'parentId' => '123'
        ];

        $this->commentService->method('createComment')
            ->with('page-123', 'Reply', '123')
            ->willReturn($replyComment);

        $response = $this->controller->createComment('page-123', 'Reply', '123');

        $this->assertEquals(Http::STATUS_CREATED, $response->getStatus());
    }

    public function testCreateCommentReturnsNotFoundForInvalidPage(): void {
        $this->withPageExists(false);

        $response = $this->controller->createComment('invalid-page', 'Test');

        $this->assertEquals(Http::STATUS_NOT_FOUND, $response->getStatus());
    }

    public function testCreateCommentReturnsBadRequestForEmptyMessage(): void {


        $response = $this->controller->createComment('page-123', '   ');

        $this->assertEquals(Http::STATUS_BAD_REQUEST, $response->getStatus());
        $this->assertEquals(['error' => 'Message cannot be empty'], $response->getData());
    }

    // ==========================================
    // updateComment Tests
    // ==========================================

    public function testUpdateCommentSuccessful(): void {
        $this->commentService->method('getCommentPageId')
            ->with('comment-123')
            ->willReturn('page-123');


        $updatedComment = [
            'id' => 'comment-123',
            'message' => 'Updated message'
        ];

        $this->commentService->method('updateComment')
            ->with('comment-123', 'Updated message')
            ->willReturn($updatedComment);

        $response = $this->controller->updateComment('comment-123', 'Updated message');

        $this->assertEquals(Http::STATUS_OK, $response->getStatus());
        $this->assertEquals('Updated message', $response->getData()['message']);
    }

    public function testUpdateCommentReturnsForbiddenWhenNotAuthorized(): void {
        $this->commentService->method('getCommentPageId')->willReturn('page-123');


        $this->commentService->method('updateComment')
            ->willThrowException(new \RuntimeException('Not authorized to edit this comment'));

        $response = $this->controller->updateComment('comment-123', 'Updated');

        $this->assertEquals(Http::STATUS_FORBIDDEN, $response->getStatus());
    }

    public function testUpdateCommentReturnsNotFoundWhenCommentMissing(): void {
        $this->commentService->method('getCommentPageId')->willReturn(null);

        $response = $this->controller->updateComment('missing-comment', 'Updated');

        $this->assertEquals(Http::STATUS_NOT_FOUND, $response->getStatus());
    }

    public function testUpdateCommentReturnsBadRequestForEmptyMessage(): void {
        $this->commentService->method('getCommentPageId')->willReturn('page-123');


        $response = $this->controller->updateComment('comment-123', '');

        $this->assertEquals(Http::STATUS_BAD_REQUEST, $response->getStatus());
    }

    // ==========================================
    // deleteComment Tests
    // ==========================================

    public function testDeleteCommentSuccessfulByOwner(): void {
        $this->commentService->method('getCommentPageId')->willReturn('page-123');


        $this->commentService->expects($this->once())
            ->method('deleteComment')
            ->with('comment-123', false); // Not admin

        $response = $this->controller->deleteComment('comment-123');

        $this->assertEquals(Http::STATUS_OK, $response->getStatus());
        $this->assertEquals(['success' => true], $response->getData());
    }

    public function testDeleteCommentSuccessfulByAdmin(): void {
        $this->userSession = MockUserSession::loggedInAs('admin');
        $this->groupManager = MockGroupManager::withAdmin('admin');

        // Rebuild with the admin session (page exists by default).
        $this->withPageExists(true);

        $this->commentService->method('getCommentPageId')->willReturn('page-123');


        $this->commentService->expects($this->once())
            ->method('deleteComment')
            ->with('comment-123', true); // Is admin

        $response = $this->controller->deleteComment('comment-123');

        $this->assertEquals(Http::STATUS_OK, $response->getStatus());
    }

    public function testDeleteCommentReturnsForbiddenWhenNotAuthorized(): void {
        $this->commentService->method('getCommentPageId')->willReturn('page-123');


        $this->commentService->method('deleteComment')
            ->willThrowException(new \RuntimeException('Not authorized to delete this comment'));

        $response = $this->controller->deleteComment('comment-123');

        $this->assertEquals(Http::STATUS_FORBIDDEN, $response->getStatus());
    }

    // ==========================================
    // Page Reactions Tests
    // ==========================================

    public function testGetPageReactionsSuccessful(): void {


        $reactions = [
            'reactions' => [
                ['emoji' => '👍', 'count' => 5],
                ['emoji' => '❤️', 'count' => 3]
            ],
            'userReaction' => '👍'
        ];

        $this->commentService->method('getPageReactions')
            ->with('page-123')
            ->willReturn($reactions);

        $response = $this->controller->getPageReactions('page-123');

        $this->assertEquals(Http::STATUS_OK, $response->getStatus());
        $this->assertArrayHasKey('reactions', $response->getData());
    }

    public function testAddPageReactionSuccessful(): void {


        $reactions = ['reactions' => [['emoji' => '👍', 'count' => 1]]];

        $this->commentService->method('addPageReaction')
            ->with('page-123', '👍')
            ->willReturn($reactions);

        $response = $this->controller->addPageReaction('page-123', '👍');

        $this->assertEquals(Http::STATUS_OK, $response->getStatus());
    }

    public function testAddPageReactionReturnsNotFoundForInvalidPage(): void {
        $this->withPageExists(false);

        $response = $this->controller->addPageReaction('invalid-page', '👍');

        $this->assertEquals(Http::STATUS_NOT_FOUND, $response->getStatus());
    }

    public function testRemovePageReactionSuccessful(): void {


        $reactions = ['reactions' => []];

        $this->commentService->method('removePageReaction')
            ->with('page-123', '👍')
            ->willReturn($reactions);

        $response = $this->controller->removePageReaction('page-123', '👍');

        $this->assertEquals(Http::STATUS_OK, $response->getStatus());
    }

    // ==========================================
    // Comment Reactions Tests
    // ==========================================

    public function testGetCommentReactionsSuccessful(): void {
        $this->commentService->method('getCommentPageId')->willReturn('page-123');


        $reactions = ['reactions' => [['emoji' => '😀', 'count' => 2]]];

        $this->commentService->method('getCommentReactions')
            ->with('comment-123')
            ->willReturn($reactions);

        $response = $this->controller->getCommentReactions('comment-123');

        $this->assertEquals(Http::STATUS_OK, $response->getStatus());
    }

    public function testAddCommentReactionSuccessful(): void {
        $this->commentService->method('getCommentPageId')->willReturn('page-123');


        $reactions = ['reactions' => [['emoji' => '🎉', 'count' => 1]]];

        $this->commentService->method('addCommentReaction')
            ->with('comment-123', '🎉')
            ->willReturn($reactions);

        $response = $this->controller->addCommentReaction('comment-123', '🎉');

        $this->assertEquals(Http::STATUS_OK, $response->getStatus());
    }

    public function testAddCommentReactionReturnsNotFoundForInvalidComment(): void {
        $this->commentService->method('getCommentPageId')->willReturn(null);

        $response = $this->controller->addCommentReaction('invalid-comment', '👍');

        $this->assertEquals(Http::STATUS_NOT_FOUND, $response->getStatus());
    }

    public function testRemoveCommentReactionSuccessful(): void {
        $this->commentService->method('getCommentPageId')->willReturn('page-123');


        $reactions = ['reactions' => []];

        $this->commentService->method('removeCommentReaction')
            ->with('comment-123', '👍')
            ->willReturn($reactions);

        $response = $this->controller->removeCommentReaction('comment-123', '👍');

        $this->assertEquals(Http::STATUS_OK, $response->getStatus());
    }

    // ==========================================
    // IDOR Protection Tests
    // ==========================================

    public function testUpdateCommentPreventsAccessToOtherPagesComments(): void {
        // Comment belongs to a page user doesn't have access to
        $this->commentService->method('getCommentPageId')
            ->with('comment-from-other-page')
            ->willReturn('restricted-page');

        $this->withPageExists(false); // User can't see this page

        $response = $this->controller->updateComment('comment-from-other-page', 'Hack attempt');

        $this->assertEquals(Http::STATUS_NOT_FOUND, $response->getStatus());
    }

    public function testDeleteCommentPreventsAccessToOtherPagesComments(): void {
        $this->commentService->method('getCommentPageId')
            ->with('comment-from-other-page')
            ->willReturn('restricted-page');

        $this->withPageExists(false);

        $response = $this->controller->deleteComment('comment-from-other-page');

        $this->assertEquals(Http::STATUS_NOT_FOUND, $response->getStatus());
    }

    // A page that switched engagement off must refuse it at the API too, not
    // only in the viewer. Before this gate, the POST below wrote the comment.

    public function testPageWithCommentsDisabledRefusesNewComment(): void {
        $this->withPageSettings(['allowComments' => false]);
        $this->commentService->expects($this->never())->method('createComment');

        $response = $this->controller->createComment('page-123', 'hoi');

        $this->assertEquals(Http::STATUS_FORBIDDEN, $response->getStatus());
    }

    public function testPageWithCommentsEnabledStillAcceptsComment(): void {
        $this->withPageSettings(['allowComments' => true]);
        $this->commentService->expects($this->once())->method('createComment')
            ->willReturn(['id' => '1']);

        $response = $this->controller->createComment('page-123', 'hoi');

        $this->assertEquals(Http::STATUS_CREATED, $response->getStatus());
    }

    public function testPageWithoutSettingsInheritsTheGlobalAllow(): void {
        $this->withPageSettings(null);
        $this->commentService->expects($this->once())->method('createComment')
            ->willReturn(['id' => '1']);

        $response = $this->controller->createComment('page-123', 'hoi');

        $this->assertEquals(Http::STATUS_CREATED, $response->getStatus());
    }

    public function testGlobalKillSwitchRefusesEvenWhenThePageAllows(): void {
        $this->withPageSettings(['allowComments' => true], $this->engagementWithGlobal(false));
        $this->commentService->expects($this->never())->method('createComment');

        $response = $this->controller->createComment('page-123', 'hoi');

        $this->assertEquals(Http::STATUS_FORBIDDEN, $response->getStatus());
    }

    public function testPageWithReactionsDisabledRefusesPageReaction(): void {
        $this->withPageSettings(['allowReactions' => false]);
        $this->commentService->expects($this->never())->method('addPageReaction');

        $response = $this->controller->addPageReaction('page-123', '\u{1F44D}');

        $this->assertEquals(Http::STATUS_FORBIDDEN, $response->getStatus());
    }

    public function testDisablingCommentsAlsoStopsReactionsOnThem(): void {
        $this->withPageSettings(['allowComments' => false]);
        $this->commentService->method('getCommentPageId')->willReturn('page-123');
        $this->commentService->expects($this->never())->method('addCommentReaction');

        $response = $this->controller->addCommentReaction('comment-1', '\u{1F44D}');

        $this->assertEquals(Http::STATUS_FORBIDDEN, $response->getStatus());
    }

    // Reading is not engagement: a page with comments off still shows the ones
    // it already has, otherwise switching it off would hide history.
    public function testDisabledCommentsAreStillReadable(): void {
        $this->withPageSettings(['allowComments' => false]);
        $this->commentService->method('getComments')->willReturn([]);

        $response = $this->controller->getComments('page-123');

        $this->assertEquals(Http::STATUS_OK, $response->getStatus());
    }
}
