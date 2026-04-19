<?php

declare(strict_types=1);

namespace OCA\IntraVox\Controller;

use OCA\IntraVox\Service\FeedReaderService;
use OCA\IntraVox\Service\PublicShareService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\DataResponse;
use OCP\IGroupManager;
use OCP\IRequest;
use Psr\Log\LoggerInterface;

class FeedReaderController extends Controller {
    public function __construct(
        string $appName,
        IRequest $request,
        private FeedReaderService $feedReaderService,
        private PublicShareService $publicShareService,
        private IGroupManager $groupManager,
        private LoggerInterface $logger,
        private ?string $userId = null,
    ) {
        parent::__construct($appName, $request);
    }

    /**
     * Fetch feed items from an external source.
     *
     * @NoAdminRequired
     * @NoCSRFRequired
     *
     * @return DataResponse
     */
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
            $result = $this->feedReaderService->fetchFeed($sourceType, $config, $limit, $this->userId, $sortBy, $sortOrder, $filterKeyword);

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
     * @NoAdminRequired
     * @NoCSRFRequired
     *
     * @return DataResponse
     */
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
            return new DataResponse(
                ['error' => $e->getMessage(), 'items' => []],
                Http::STATUS_BAD_REQUEST
            );
        }
    }

    /**
     * Fetch feed via public share link.
     *
     * @PublicPage
     * @NoCSRFRequired
     *
     * @param string $token Share token
     * @return DataResponse
     */
    public function getFeedByShare(string $token): DataResponse {
        try {
            $share = $this->publicShareService->getShareByToken($token);
            if ($share === null) {
                return new DataResponse(
                    ['error' => 'Invalid or expired share token'],
                    Http::STATUS_FORBIDDEN
                );
            }

            $sourceType = $this->request->getParam('sourceType', 'rss');
            $limit = (int)$this->request->getParam('limit', 5);

            $config = $this->buildConfigFromRequest($sourceType);
            [$sortBy, $sortOrder, $filterKeyword] = $this->parseSortAndFilter();
            $result = $this->feedReaderService->fetchFeed($sourceType, $config, $limit, null, $sortBy, $sortOrder, $filterKeyword);

            return new DataResponse($result);
        } catch (\Exception $e) {
            $this->logger->error('IntraVox: Error fetching feed by share', [
                'token' => substr($token, 0, 8) . '...',
                'error' => $e->getMessage(),
            ]);
            return new DataResponse(
                ['error' => 'Failed to fetch feed', 'items' => []],
                Http::STATUS_INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     * Get configured LMS connections (without tokens).
     *
     * @NoAdminRequired
     * @NoCSRFRequired
     *
     * @return DataResponse
     */
    public function getConnections(): DataResponse {
        if ($this->userId === null) {
            return new DataResponse(
                ['error' => 'Authentication required'],
                Http::STATUS_UNAUTHORIZED
            );
        }

        try {
            $connections = $this->feedReaderService->getConnections();
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
     * @NoAdminRequired
     * @NoCSRFRequired
     *
     * @param string $connectionId
     * @return DataResponse
     */
    public function getCourses(string $connectionId): DataResponse {
        if ($this->userId === null) {
            return new DataResponse(
                ['error' => 'Authentication required'],
                Http::STATUS_UNAUTHORIZED
            );
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
     * Save LMS connections (admin only).
     *
     * @NoCSRFRequired
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

        if (!$this->groupManager->isAdmin($this->userId)) {
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

    /**
     * Parse sort and filter parameters from request.
     * @return array{string, string, string} [sortBy, sortOrder, filterKeyword]
     */
    private function parseSortAndFilter(): array {
        $sortBy = $this->request->getParam('sortBy', 'date');
        $sortBy = in_array($sortBy, ['date', 'title'], true) ? $sortBy : 'date';

        $sortOrder = $this->request->getParam('sortOrder', 'desc');
        $sortOrder = in_array($sortOrder, ['asc', 'desc'], true) ? $sortOrder : 'desc';

        $filterKeyword = trim((string) $this->request->getParam('filterKeyword', ''));
        // Limit keyword length to prevent abuse
        $filterKeyword = mb_substr($filterKeyword, 0, 100);

        return [$sortBy, $sortOrder, $filterKeyword];
    }

    private function buildConfigFromRequest(string $sourceType): array {
        $config = [];

        if ($sourceType === 'rss') {
            $config['url'] = $this->request->getParam('url', '');
        } else {
            $config['connectionId'] = $this->request->getParam('connectionId', '');
            $courseId = $this->request->getParam('courseId', '');
            // Only allow alphanumeric course IDs (prevents parameter injection)
            if (!empty($courseId) && !preg_match('/^[a-zA-Z0-9_-]{1,64}$/', $courseId)) {
                $courseId = '';
            }
            $config['courseId'] = $courseId;

            $contentType = $this->request->getParam('contentType', '');
            // Whitelist allowed content types to prevent injection
            if (!in_array($contentType, ['', 'news', 'my-courses', 'deadlines'], true)) {
                $contentType = '';
            }
            $config['contentType'] = $contentType;
        }

        return $config;
    }
}
