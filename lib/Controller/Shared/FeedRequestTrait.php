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
        return $this->buildConfig($sourceType, fn(string $naam) => $this->request->getParam($naam, ''));
    }

    /**
     * Validate one feed's selectors, whatever they were read from.
     *
     * Split out so the batch endpoint validates each entry through the same
     * code as a single request. A second copy of these patterns would drift,
     * and the copy that drifted would be the one an anonymous visitor reaches.
     *
     * @param callable(string):string $lees names a parameter, returns its raw value
     */
    private function buildConfig(string $sourceType, callable $lees): array {
        $config = [];

        if ($sourceType === 'rss') {
            $config['url'] = $lees('url');
        } else {
            $config['connectionId'] = $lees('connectionId');
            $courseId = $lees('courseId');
            // Only allow alphanumeric course IDs (prevents parameter injection)
            if (!empty($courseId) && !preg_match('/^[a-zA-Z0-9_-]{1,64}$/', $courseId)) {
                $courseId = '';
            }
            $config['courseId'] = $courseId;

            $contentType = $lees('contentType');
            // Whitelist allowed content types to prevent injection
            if (!in_array($contentType, ['', 'news', 'my-courses', 'courses', 'assignments', 'deadlines', 'pages', 'documents', 'list', 'open', 'overdue', 'milestones', 'recently-updated', 'bugs', 'recent', 'created-recent'], true)) {
                $contentType = '';
            }
            $config['contentType'] = $contentType;

            $jiraProject = $lees('jiraProject');
            if (!empty($jiraProject) && !preg_match('/^[A-Z][A-Z0-9_]{1,20}$/', $jiraProject)) {
                $jiraProject = '';
            }
            $config['jiraProject'] = $jiraProject;

            $moodleForumId = $lees('moodleForumId');
            if (!empty($moodleForumId) && !preg_match('/^[0-9]{1,10}$/', $moodleForumId)) {
                $moodleForumId = '';
            }
            $config['moodleForumId'] = $moodleForumId;

            $listId = $lees('listId');
            // Only allow GUID-format list IDs
            if (!empty($listId) && !preg_match('/^[a-zA-Z0-9-]{1,64}$/', $listId)) {
                $listId = '';
            }
            $config['listId'] = $listId;
        }

        return $config;
    }

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

    /**
     * Ceiling on feeds in one batch.
     *
     * A page with more feed widgets than this still works — the client sends a
     * second batch. The limit exists so one request cannot ask the server to
     * make an unbounded number of outbound fetches.
     */
    private const MAX_BATCH_FEEDS = 20;

    /**
     * Several feeds in one request.
     *
     * A page carries one feed widget per block, and each used to fetch on its
     * own: three widgets produced nine requests once re-renders were counted,
     * and every one counts against the per-IP rate limit — which an office
     * behind one outgoing address runs out of after a dozen page views.
     *
     * Each entry is validated through buildConfig(), the same code a single
     * request uses. A failure in one feed is reported in that feed's slot and
     * never fails the batch: one unreachable source must not blank a page.
     *
     * @param callable(array):?DataResponse $bewaker optional per-feed guard
     */
    private function handleFetchFeedBatch(?string $userId, ?callable $bewaker = null): DataResponse {
        $rauw = $this->request->getParam('feeds', null);
        if (is_string($rauw)) {
            $rauw = json_decode($rauw, true);
        }
        if (!is_array($rauw) || $rauw === []) {
            return new DataResponse(['error' => 'Missing feeds'], Http::STATUS_BAD_REQUEST);
        }
        if (count($rauw) > self::MAX_BATCH_FEEDS) {
            return new DataResponse(
                ['error' => 'Too many feeds in one request (max ' . self::MAX_BATCH_FEEDS . ')'],
                Http::STATUS_BAD_REQUEST
            );
        }

        $uit = [];
        foreach ($rauw as $index => $spec) {
            $sleutel = is_string($index) ? $index : (string)$index;
            if (!is_array($spec)) {
                $uit[$sleutel] = ['items' => [], 'error' => 'Invalid feed specification'];
                continue;
            }

            $sourceType = (string)($spec['sourceType'] ?? 'rss');
            $config = $this->buildConfig($sourceType, static fn(string $naam) => (string)($spec[$naam] ?? ''));

            if ($bewaker !== null) {
                $geweigerd = $bewaker($config);
                if ($geweigerd !== null) {
                    $uit[$sleutel] = ['items' => [], 'error' => 'Not published by this share'];
                    continue;
                }
            }

            $limit = (int)($spec['limit'] ?? 5);
            $sortBy = in_array($spec['sortBy'] ?? 'date', ['date', 'title'], true) ? (string)$spec['sortBy'] : 'date';
            $sortOrder = in_array($spec['sortOrder'] ?? 'desc', ['asc', 'desc'], true) ? (string)$spec['sortOrder'] : 'desc';
            $filter = mb_substr(trim((string)($spec['filterKeyword'] ?? '')), 0, 100);

            try {
                // No singleflight sleep in a batch. @see withoutSingleflightWait
                $uit[$sleutel] = $this->feedReaderService->withoutSingleflightWait(
                    fn() => $this->feedReaderService->fetchFeed(
                        $sourceType, $config, $limit, $userId, $sortBy, $sortOrder, $filter
                    )
                );
            } catch (\Exception $e) {
                // Logged, not returned: the message can name internal hosts.
                $this->logger->warning('IntraVox: batch feed failed', ['error' => $e->getMessage()]);
                $uit[$sleutel] = ['items' => [], 'error' => 'Failed to fetch feed'];
            }
        }

        return new DataResponse(['feeds' => $uit]);
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
