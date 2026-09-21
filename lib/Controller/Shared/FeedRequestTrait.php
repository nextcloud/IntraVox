<?php
declare(strict_types=1);

namespace OCA\IntraVox\Controller\Shared;

use OCP\AppFramework\Http;
use OCP\AppFramework\Http\DataDownloadResponse;
use OCP\AppFramework\Http\DataResponse;

/**
 * Feed-widget request parsing, shared by the authenticated API and the share
 * endpoints. (F6d)
 *
 * Same reasoning as SharePathTrait: these three are called from both sides of
 * the F6 split, and two of them are input validation — buildConfigFromRequest()
 * is the whitelist that keeps a course id, a Jira key or a SharePoint list id
 * from being anything other than what it claims to be. A second copy is a
 * second place for a fix to miss.
 *
 * Bodies are verbatim from FeedReaderController.
 */
trait FeedRequestTrait {

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
            if (!in_array($contentType, ['', 'news', 'my-courses', 'courses', 'assignments', 'deadlines', 'pages', 'documents', 'list', 'open', 'overdue', 'milestones', 'recently-updated', 'bugs', 'recent', 'created-recent'], true)) {
                $contentType = '';
            }
            $config['contentType'] = $contentType;

            $jiraProject = $this->request->getParam('jiraProject', '');
            if (!empty($jiraProject) && !preg_match('/^[A-Z][A-Z0-9_]{1,20}$/', $jiraProject)) {
                $jiraProject = '';
            }
            $config['jiraProject'] = $jiraProject;

            $moodleForumId = $this->request->getParam('moodleForumId', '');
            if (!empty($moodleForumId) && !preg_match('/^[0-9]{1,10}$/', $moodleForumId)) {
                $moodleForumId = '';
            }
            $config['moodleForumId'] = $moodleForumId;

            $listId = $this->request->getParam('listId', '');
            // Only allow GUID-format list IDs
            if (!empty($listId) && !preg_match('/^[a-zA-Z0-9-]{1,64}$/', $listId)) {
                $listId = '';
            }
            $config['listId'] = $listId;
        }

        return $config;
    }

    /**
     * Serve an externally hosted image, but only one this backend signed.
     *
     * The signature is what makes this not an open proxy: an unsigned or
     * mis-signed url is refused before any outbound request is made.
     */
    /**
     * One article body, for the reader who opened it.
     *
     * Lives in the trait so the logged-in route and the share route answer
     * identically — a public share is the case this matters most for, since an
     * anonymous visitor has no other way past a cookie wall.
     *
     * `sourceType` and the config are re-read from the request rather than
     * trusted from the client as a cache key: buildConfigFromRequest() is the
     * same validation the list went through, and on a share the caller has
     * already checked the feed is one that share publishes.
     */
    private function handleFetchArticle(?string $userId): DataResponse {
        $itemId = (string)$this->request->getParam('itemId', '');
        if ($itemId === '') {
            return new DataResponse(['error' => 'Missing itemId'], Http::STATUS_BAD_REQUEST);
        }

        $sourceType = (string)$this->request->getParam('sourceType', 'rss');
        $config = $this->buildConfigFromRequest($sourceType);

        $html = $this->feedReaderService->fetchArticle($sourceType, $config, $itemId, $userId);
        if ($html === null) {
            // Not an error: the entry expires with the feed it came from, and
            // an item may simply carry no body. The client links out instead.
            return new DataResponse(['content' => null], Http::STATUS_NOT_FOUND);
        }

        $response = new DataResponse(['content' => $html]);
        // Private: the body may come from a personalised LMS feed, and a shared
        // proxy must not hand one reader's article to another.
        $response->addHeader('Cache-Control', 'private, max-age=300');
        return $response;
    }

    private function handleProxyImage(): DataDownloadResponse|DataResponse {
        $url = $this->request->getParam('url', '');
        $sig = $this->request->getParam('sig', '');

        if (empty($url) || empty($sig)) {
            return new DataResponse(
                ['error' => 'Missing parameters'],
                Http::STATUS_BAD_REQUEST
            );
        }

        if (!$this->feedReaderService->verifyImageSignature($url, $sig)) {
            return new DataResponse(
                ['error' => 'Invalid signature'],
                Http::STATUS_FORBIDDEN
            );
        }

        try {
            $result = $this->feedReaderService->proxyImage($url);

            $response = new DataDownloadResponse(
                $result['body'],
                '', // no filename — inline display, not download
                $result['contentType']
            );
            $response->addHeader('Cache-Control', 'public, max-age=86400, immutable');
            $response->addHeader('X-Content-Type-Options', 'nosniff');
            $response->addHeader('Referrer-Policy', 'no-referrer');
            $response->addHeader('Content-Security-Policy', "default-src 'none'");
            // Override Content-Disposition to inline (DataDownloadResponse sets attachment)
            $response->addHeader('Content-Disposition', 'inline');
            return $response;
        } catch (\Exception $e) {
            $this->logger->warning('IntraVox: Image proxy failed', [
                'error' => $e->getMessage(),
            ]);
            return new DataResponse(
                ['error' => 'Failed to fetch image'],
                Http::STATUS_BAD_GATEWAY
            );
        }
    }
}
