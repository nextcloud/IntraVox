<?php
declare(strict_types=1);

namespace OCA\IntraVox\Controller;

use OCA\IntraVox\Service\PageService;
use OCA\IntraVox\Service\PublicShareService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\Attribute\NoCSRFRequired;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IGroupManager;
use OCP\IRequest;
use OCP\IUserSession;
use Psr\Log\LoggerInterface;

/**
 * Share administration, as seen from INSIDE the app.
 *
 * Split out of ApiController (PR-A). Not to be confused with
 * PublicShareController, which serves anonymous visitors of a share link. These
 * two endpoints answer questions an editor or admin asks about shares: "is this
 * page shared?" and "which shares exist?".
 *
 * The asymmetry is deliberate and moved across unchanged: getShareInfo is
 * #[NoAdminRequired] because any editor may check their own page, while
 * getActiveShares lists every share on the instance and is admin-gated in the
 * body.
 */
class ShareAdminApiController extends Controller {
    use ApiErrorTrait;
    use ChecksAdminAccess;

    public function __construct(
        string $appName,
        IRequest $request,
        private PageService $pageService,
        private PublicShareService $publicShareService,
        private IGroupManager $groupManager,
        private IUserSession $userSession,
        private LoggerInterface $logger,
    ) {
        parent::__construct($appName, $request);
    }

    protected function getLogger(): LoggerInterface {
        return $this->logger;
    }
    /**
     * Get share info for a page (NC Files share detection).
     *
     * Checks if an NC Files share link exists for the page or its parent folder.
     * Used by the ShareButton component to determine if the share button should be shown.
     *
     * @param string $uniqueId The page's unique ID
     * @return JSONResponse
     */
    #[NoAdminRequired]
    #[NoCSRFRequired]
    public function getShareInfo(string $uniqueId): JSONResponse {
        try {
            // Get the page to verify it exists and get its language
            $page = $this->pageService->getPage($uniqueId);

            // Check read permission
            if (!($page['permissions']['canRead'] ?? false)) {
                return new JSONResponse([
                    'error' => 'Access denied'
                ], Http::STATUS_FORBIDDEN);
            }

            $language = $page['language'] ?? 'en';
            $user = $this->userSession->getUser();
            $userId = $user ? $user->getUID() : null;

            // Get share info from PublicShareService
            $shareInfo = $this->publicShareService->getShareInfoForPage($uniqueId, $language, $userId);

            return new JSONResponse($shareInfo);
        } catch (\Exception $e) {
            $this->logger->error('[ApiController] Failed to get share info', [
                'uniqueId' => $uniqueId,
                'error' => $e->getMessage()
            ]);
            return new JSONResponse([
                'hasShare' => false,
                'reason' => 'error',
                'error' => $e->getMessage()
            ], Http::STATUS_INTERNAL_SERVER_ERROR);
        }
    }
    /**
     * Get all active public share links on IntraVox content.
     * Admin-only endpoint for the sharing overview tab.
     */
    public function getActiveShares(): JSONResponse {
        if (!$this->isAdmin()) {
            return new JSONResponse(['error' => 'Admin access required'], Http::STATUS_FORBIDDEN);
        }

        try {
            $shares = $this->publicShareService->getActiveShares();
            return new JSONResponse($shares);
        } catch (\Exception $e) {
            $this->logger->error('[ApiController] Failed to get active shares', [
                'error' => $e->getMessage()
            ]);
            return new JSONResponse(['error' => 'Failed to retrieve shares'], Http::STATUS_INTERNAL_SERVER_ERROR);
        }
    }
}
