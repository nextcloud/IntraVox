<?php

declare(strict_types=1);

namespace OCA\IntraVox\Controller;

use OCA\IntraVox\Controller\Shared\FeedRequestTrait;
use OCA\IntraVox\Service\FeedReaderService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\UserRateLimit;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\Attribute\NoCSRFRequired;
use OCP\AppFramework\Http\DataDownloadResponse;
use OCP\AppFramework\Http\DataResponse;
use OCP\IGroupManager;
use OCP\IRequest;
use OCP\IUserSession;
use Psr\Log\LoggerInterface;

class FeedReaderController extends Controller {
    use FeedRequestTrait;
    use ChecksAdminAccess;

    public function __construct(
        string $appName,
        IRequest $request,
        private FeedReaderService $feedReaderService,
        private IGroupManager $groupManager,
        private LoggerInterface $logger,
        private IUserSession $userSession,
        private \OCA\IntraVox\Service\PermissionService $permissionService,
        private ?string $userId = null,
    ) {
        parent::__construct($appName, $request);
    }

    /**
     * The connector helpers below use a stored connection's credentials to query
     * the external service, so — like the feed-token endpoints — they require
     * IntraVox access rather than mere authentication: 401 for an anonymous
     * caller, 403 for a logged-in user without access.
     */
    private function denyUnlessIntraVoxAccess(): ?DataResponse {
        if ($this->userId === null) {
            return new DataResponse(['error' => 'Authentication required'], Http::STATUS_UNAUTHORIZED);
        }
        if (!$this->permissionService->hasAccess($this->userId)) {
            return new DataResponse(['error' => 'Access denied'], Http::STATUS_FORBIDDEN);
        }
        return null;
    }

    /**
     * Fetch feed items from an external source.
     *
     *
     * @return DataResponse
     */
    #[UserRateLimit(limit: 30, period: 60)]
    #[NoAdminRequired]
    #[NoCSRFRequired]
    public function getFeed(): DataResponse {
        if ($this->userId === null) {
            return new DataResponse(
                ['error' => 'Authentication required'],
                Http::STATUS_UNAUTHORIZED
            );
        }

        try {
            $sourceType = $this->request->getParam('sourceType', 'rss');
            $limit = (int)$this->request->getParam('limit', 5);

            $config = $this->buildConfigFromRequest($sourceType);

            [$sortBy, $sortOrder, $filterKeyword] = $this->parseSortAndFilter();
            // A reader pressing refresh means "now", so the freshness window is
            // skipped. The refresh lock still applies, so a page full of widgets
            // cannot turn one click into a stampede.
            $force = $this->request->getParam('refresh', '') === '1';
            $result = $this->feedReaderService->fetchFeed($sourceType, $config, $limit, $this->userId, $sortBy, $sortOrder, $filterKeyword, $force);

            return new DataResponse($result);
        } catch (\Exception $e) {
            $this->logger->error('IntraVox: Error fetching feed', [
                'error' => $e->getMessage(),
            ]);
            return new DataResponse(
                ['error' => 'Failed to fetch feed', 'items' => []],
                Http::STATUS_INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     * Fetch feed preview (limited items for editor).
     *
     *
     * @return DataResponse
     */
    #[UserRateLimit(limit: 30, period: 60)]
    #[NoAdminRequired]
    public function getPreview(): DataResponse {
        if ($this->userId === null) {
            return new DataResponse(
                ['error' => 'Authentication required'],
                Http::STATUS_UNAUTHORIZED
            );
        }

        try {
            $sourceType = $this->request->getParam('sourceType', 'rss');
            $config = $this->buildConfigFromRequest($sourceType);
            [$sortBy, $sortOrder, $filterKeyword] = $this->parseSortAndFilter();
            $result = $this->feedReaderService->fetchFeed($sourceType, $config, 3, $this->userId, $sortBy, $sortOrder, $filterKeyword);

            return new DataResponse($result);
        } catch (\Exception $e) {
            $this->logger->warning('IntraVox: feed preview failed', ['error' => $e->getMessage()]);
            return new DataResponse(['error' => 'Could not fetch that feed', 'items' => []], Http::STATUS_BAD_REQUEST);
        }
    }

    /**
     * Proxy an external image to bypass CSP restrictions.
     * Only serves images whose URL was signed by the backend (HMAC).
     *
     *
     * @return DataDownloadResponse|DataResponse
     */
    #[NoAdminRequired]
    #[NoCSRFRequired]
    public function proxyImage(): DataDownloadResponse|DataResponse {
        return $this->handleProxyImage();
    }

    /**
     * Several feeds in one request, so a page costs one round trip.
     *
     * Rate-limited per batch rather than per feed: that is the point. A page
     * with five widgets counted five times against the limit before, and the
     * limit is per IP — which is what an intranet behind one outgoing address
     * runs into.
     *
     * @return DataResponse
     */
    #[UserRateLimit(limit: 30, period: 60)]
    #[NoAdminRequired]
    #[NoCSRFRequired]
    public function getFeedBatch(): DataResponse {
        if ($this->userId === null) {
            return new DataResponse(['error' => 'Authentication required'], Http::STATUS_UNAUTHORIZED);
        }

        return $this->handleFetchFeedBatch($this->userId);
    }

    /**
     * The article body behind one feed item, for reading it in place.
     *
     * Rate-limited like the other feed reads: it serves from cache, so the
     * limit is about enumeration rather than load — an item id is guessable
     * only by having seen the list it came from.
     *
     * @return DataResponse
     */
    #[UserRateLimit(limit: 60, period: 60)]
    #[NoAdminRequired]
    #[NoCSRFRequired]
    public function getArticle(): DataResponse {
        if ($this->userId === null) {
            return new DataResponse(['error' => 'Authentication required'], Http::STATUS_UNAUTHORIZED);
        }

        return $this->handleFetchArticle($this->userId);
    }

    /**
     * Get configured LMS connections (without tokens).
     *
     *
     * @return DataResponse
     */
    #[NoAdminRequired]
    #[NoCSRFRequired]
    public function getConnections(): DataResponse {
        if ($this->userId === null) {
            return new DataResponse(
                ['error' => 'Authentication required'],
                Http::STATUS_UNAUTHORIZED
            );
        }

        try {
            // Only an administrator sees the credential-adjacent fields
            // (FEED-CRED). This endpoint is @NoAdminRequired because the
            // widget editor needs the connection NAMES to build a dropdown;
            // it has never needed the OAuth client id, the tenant or the
            // custom headers, which routinely hold an API key.
            $isAdmin = $this->groupManager->isAdmin($this->userId);
            $connections = $this->feedReaderService->getConnections($isAdmin);
            return new DataResponse(['connections' => $connections]);
        } catch (\Exception $e) {
            return new DataResponse(
                ['error' => 'Failed to get connections'],
                Http::STATUS_INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     * Get available courses for a connection (uses current user's token).
     *
     *
     * @param string $connectionId
     * @return DataResponse
     */
    #[NoAdminRequired]
    #[NoCSRFRequired]
    public function getCourses(string $connectionId): DataResponse {
        $denied = $this->denyUnlessIntraVoxAccess();
        if ($denied !== null) {
            return $denied;
        }

        try {
            $result = $this->feedReaderService->getCourses($connectionId, $this->userId);
            return new DataResponse($result);
        } catch (\Exception $e) {
            return new DataResponse(
                ['courses' => []],
                Http::STATUS_OK
            );
        }
    }

    /**
     * Get available lists and document libraries for a SharePoint connection.
     *
     *
     * @param string $connectionId
     * @return DataResponse
     */
    #[NoAdminRequired]
    #[NoCSRFRequired]
    public function getSharePointLists(string $connectionId): DataResponse {
        $denied = $this->denyUnlessIntraVoxAccess();
        if ($denied !== null) {
            return $denied;
        }

        try {
            $result = $this->feedReaderService->getSharePointLists($connectionId);
            return new DataResponse($result);
        } catch (\Exception $e) {
            $this->logger->error('IntraVox: SharePoint lists fetch failed', [
                'connectionId' => $connectionId,
                'error' => $e->getMessage(),
            ]);
            return new DataResponse(['libraries' => [], 'lists' => [], 'error' => 'Could not load SharePoint lists'], Http::STATUS_OK);
        }
    }

    /**
     * Get available Jira projects for a connection.
     *
     *
     * @param string $connectionId
     * @return DataResponse
     */
    #[NoAdminRequired]
    #[NoCSRFRequired]
    public function getJiraProjects(string $connectionId): DataResponse {
        $denied = $this->denyUnlessIntraVoxAccess();
        if ($denied !== null) {
            return $denied;
        }

        try {
            $result = $this->feedReaderService->getJiraProjects($connectionId);
            return new DataResponse($result);
        } catch (\Exception $e) {
            return new DataResponse(
                ['projects' => []],
                Http::STATUS_OK
            );
        }
    }

    /**
     * Get available Moodle forums for a course in a connection.
     *
     *
     * @param string $connectionId
     * @return DataResponse
     */
    #[NoAdminRequired]
    #[NoCSRFRequired]
    public function getMoodleForums(string $connectionId): DataResponse {
        $denied = $this->denyUnlessIntraVoxAccess();
        if ($denied !== null) {
            return $denied;
        }

        $courseId = $this->request->getParam('courseId', '');
        if (empty($courseId) || !preg_match('/^[a-zA-Z0-9_-]{1,64}$/', $courseId)) {
            return new DataResponse(['forums' => []], Http::STATUS_OK);
        }

        try {
            $result = $this->feedReaderService->getMoodleForums($connectionId, $courseId);
            return new DataResponse($result);
        } catch (\Exception $e) {
            return new DataResponse(
                ['forums' => []],
                Http::STATUS_OK
            );
        }
    }

    /**
     * Save LMS connections (admin only).
     *
     * @return DataResponse
     */
    public function setConnections(): DataResponse {
        if ($this->userId === null) {
            return new DataResponse(
                ['error' => 'Authentication required'],
                Http::STATUS_UNAUTHORIZED
            );
        }

        if (!$this->isAdmin()) {
            return new DataResponse(
                ['error' => 'Admin access required'],
                Http::STATUS_FORBIDDEN
            );
        }

        try {
            $connections = $this->request->getParam('connections', []);
            if (!is_array($connections)) {
                return new DataResponse(
                    ['error' => 'Invalid connections data'],
                    Http::STATUS_BAD_REQUEST
                );
            }

            $this->feedReaderService->saveConnections($connections);

            return new DataResponse(['status' => 'ok']);
        } catch (\Exception $e) {
            $this->logger->error('IntraVox: Error saving feed connections', [
                'error' => $e->getMessage(),
            ]);
            return new DataResponse(
                ['error' => 'Failed to save connections'],
                Http::STATUS_INTERNAL_SERVER_ERROR
            );
        }
    }
}
