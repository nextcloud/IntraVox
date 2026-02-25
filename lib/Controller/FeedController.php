<?php
declare(strict_types=1);

namespace OCA\IntraVox\Controller;

use OCA\IntraVox\Service\FeedService;
use OCA\IntraVox\Service\FeedTokenService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\AnonRateThrottle;
use OCP\AppFramework\Http\Attribute\BruteForceProtection;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\Attribute\NoCSRFRequired;
use OCP\AppFramework\Http\Attribute\PublicPage;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\Http\Response;
use OCP\IConfig;
use OCP\IRequest;
use OCP\IURLGenerator;
use OCP\IUserSession;
use OCP\Security\Bruteforce\IThrottler;
use Psr\Log\LoggerInterface;

class FeedController extends Controller {
    public function __construct(
        string $appName,
        IRequest $request,
        private FeedTokenService $feedTokenService,
        private FeedService $feedService,
        private IURLGenerator $urlGenerator,
        private IUserSession $userSession,
        private IConfig $config,
        private LoggerInterface $logger
    ) {
        parent::__construct($appName, $request);
    }

    /**
     * Serve RSS feed by token (no login required).
     */
    #[PublicPage]
    #[NoCSRFRequired]
    #[AnonRateThrottle(limit: 30, period: 60)]
    #[BruteForceProtection(action: 'intravox_feed')]
    public function getFeed(string $token): Response {
        // Cheap format validation first
        if (!$this->isValidFeedTokenFormat($token)) {
            return $this->feedNotFoundResponse();
        }

        // NC sharing must be enabled (feed is functionally a public share link)
        if (!$this->isLinkSharingAllowed()) {
            return $this->feedNotFoundResponse();
        }

        // Validate token and get user info
        $tokenData = $this->feedTokenService->validateToken($token);
        if ($tokenData === null) {
            $this->registerFeedBruteForceAttempt();
            return $this->feedNotFoundResponse();
        }

        $userId = $tokenData['user_id'];
        $config = $tokenData['config'];

        // Build the self-referencing feed URL
        $feedUrl = $this->urlGenerator->getAbsoluteURL(
            $this->urlGenerator->linkToRoute('intravox.feed.getFeed', ['token' => $token])
        );

        // Build the base URL for feed media (public, token-authenticated)
        $feedMediaBaseUrl = $this->urlGenerator->getAbsoluteURL(
            '/apps/intravox/feed/' . $token . '/media'
        );

        // Generate the feed
        $result = $this->feedService->generateFeed($userId, $config, $feedUrl, $feedMediaBaseUrl);
        $xml = $result['xml'];
        $lastModified = $result['lastModified'];

        // Handle conditional requests (304 Not Modified)
        $etag = '"' . md5($xml) . '"';

        $ifNoneMatch = $this->request->getHeader('If-None-Match');
        if ($ifNoneMatch === $etag) {
            $response = new Response();
            $response->setStatus(Http::STATUS_NOT_MODIFIED);
            return $response;
        }

        if ($lastModified > 0) {
            $ifModifiedSince = $this->request->getHeader('If-Modified-Since');
            if ($ifModifiedSince) {
                $since = strtotime($ifModifiedSince);
                if ($since !== false && $lastModified <= $since) {
                    $response = new Response();
                    $response->setStatus(Http::STATUS_NOT_MODIFIED);
                    return $response;
                }
            }
        }

        // Return feed with caching headers
        $response = new Response();
        $response->setStatus(Http::STATUS_OK);
        $response->addHeader('Content-Type', 'application/rss+xml; charset=utf-8');
        $response->addHeader('Cache-Control', 'public, max-age=300, must-revalidate');
        $response->addHeader('ETag', $etag);

        if ($lastModified > 0) {
            $response->addHeader('Last-Modified', gmdate('D, d M Y H:i:s', $lastModified) . ' GMT');
        }

        // Use a custom response that includes the XML body
        return new class($xml, $response) extends Response {
            private string $body;

            public function __construct(string $body, Response $parent) {
                parent::__construct();
                $this->body = $body;
                $this->setStatus($parent->getStatus());
                foreach ($parent->getHeaders() as $key => $value) {
                    $this->addHeader($key, $value);
                }
            }

            public function render(): string {
                return $this->body;
            }
        };
    }

    /**
     * Serve media for a feed item (no login required, token-authenticated).
     */
    #[PublicPage]
    #[NoCSRFRequired]
    #[AnonRateThrottle(limit: 60, period: 60)]
    #[BruteForceProtection(action: 'intravox_feed')]
    public function getFeedMedia(string $token, string $pageId, string $filename): Response {
        if (!$this->isValidFeedTokenFormat($token)) {
            return $this->feedNotFoundResponse();
        }

        // NC sharing must be enabled
        if (!$this->isLinkSharingAllowed()) {
            return $this->feedNotFoundResponse();
        }

        $tokenData = $this->feedTokenService->validateToken($token);
        if ($tokenData === null) {
            $this->registerFeedBruteForceAttempt();
            return $this->feedNotFoundResponse();
        }

        $userId = $tokenData['user_id'];
        $media = $this->feedService->getMediaForUser($userId, $pageId, $filename);

        if ($media === null) {
            return new DataResponse(['error' => 'Media not found'], Http::STATUS_NOT_FOUND);
        }

        return new class($media['content'], $media['mimeType'], $media['filename']) extends Response {
            private string $body;

            public function __construct(string $body, string $mimeType, string $filename) {
                parent::__construct();
                $this->body = $body;
                $this->setStatus(Http::STATUS_OK);
                $this->addHeader('Content-Type', $mimeType);
                $this->addHeader('Content-Disposition', 'inline; filename="' . $filename . '"');
                $this->addHeader('Cache-Control', 'public, max-age=86400');
            }

            public function render(): string {
                return $this->body;
            }
        };
    }

    /**
     * Get the current user's feed token (if any).
     */
    #[NoAdminRequired]
    #[NoCSRFRequired]
    public function getToken(): DataResponse {
        $userId = $this->getCurrentUserId();
        if ($userId === null) {
            return new DataResponse(['error' => 'Not authenticated'], Http::STATUS_UNAUTHORIZED);
        }

        // If NC link sharing is disabled, inform the frontend
        if (!$this->isLinkSharingAllowed()) {
            return new DataResponse(['hasToken' => false, 'linkSharingDisabled' => true]);
        }

        $tokenData = $this->feedTokenService->getTokenForUser($userId);
        if ($tokenData === null) {
            return new DataResponse(['hasToken' => false]);
        }

        return new DataResponse([
            'hasToken' => true,
            'feedUrl' => $this->buildFeedUrl($tokenData['token']),
            'config' => $tokenData['config'],
            'created_at' => $tokenData['created_at'],
            'last_accessed' => $tokenData['last_accessed'],
        ]);
    }

    /**
     * Generate or regenerate a feed token.
     */
    #[NoAdminRequired]
    #[NoCSRFRequired]
    public function regenerateToken(): DataResponse {
        $userId = $this->getCurrentUserId();
        if ($userId === null) {
            return new DataResponse(['error' => 'Not authenticated'], Http::STATUS_UNAUTHORIZED);
        }

        // NC sharing must be enabled to generate feed tokens
        if (!$this->isLinkSharingAllowed()) {
            return new DataResponse(['error' => 'Link sharing is disabled by the administrator'], Http::STATUS_FORBIDDEN);
        }

        $scope = $this->request->getParam('scope', 'language');
        $limit = (int) $this->request->getParam('limit', 20);

        $config = [
            'scope' => in_array($scope, ['all', 'language'], true) ? $scope : 'language',
            'limit' => min(max($limit, 1), 50),
        ];

        $existing = $this->feedTokenService->getTokenForUser($userId);
        if ($existing !== null) {
            $token = $this->feedTokenService->regenerateToken($userId);
            $this->feedTokenService->updateConfig($userId, $config);
        } else {
            $token = $this->feedTokenService->generateToken($userId, $config);
        }

        return new DataResponse([
            'hasToken' => true,
            'feedUrl' => $this->buildFeedUrl($token),
            'config' => $config,
        ]);
    }

    /**
     * Revoke the current user's feed token.
     */
    #[NoAdminRequired]
    #[NoCSRFRequired]
    public function revokeToken(): DataResponse {
        $userId = $this->getCurrentUserId();
        if ($userId === null) {
            return new DataResponse(['error' => 'Not authenticated'], Http::STATUS_UNAUTHORIZED);
        }

        $this->feedTokenService->revokeToken($userId);

        return new DataResponse(['hasToken' => false]);
    }

    /**
     * Update feed configuration for the current user.
     */
    #[NoAdminRequired]
    #[NoCSRFRequired]
    public function updateConfig(): DataResponse {
        $userId = $this->getCurrentUserId();
        if ($userId === null) {
            return new DataResponse(['error' => 'Not authenticated'], Http::STATUS_UNAUTHORIZED);
        }

        $existing = $this->feedTokenService->getTokenForUser($userId);
        if ($existing === null) {
            return new DataResponse(['error' => 'No feed token exists'], Http::STATUS_NOT_FOUND);
        }

        $scope = $this->request->getParam('scope', $existing['config']['scope'] ?? 'language');
        $limit = (int) $this->request->getParam('limit', $existing['config']['limit'] ?? 20);

        $config = [
            'scope' => in_array($scope, ['all', 'language'], true) ? $scope : 'language',
            'limit' => min(max($limit, 1), 50),
        ];

        $this->feedTokenService->updateConfig($userId, $config);

        return new DataResponse([
            'hasToken' => true,
            'feedUrl' => $this->buildFeedUrl($existing['token']),
            'config' => $config,
        ]);
    }

    // --- Private helpers ---

    private function getCurrentUserId(): ?string {
        $user = $this->userSession->getUser();
        return $user?->getUID();
    }

    private function buildFeedUrl(string $token): string {
        return $this->urlGenerator->getAbsoluteURL(
            $this->urlGenerator->linkToRoute('intravox.feed.getFeed', ['token' => $token])
        );
    }

    private function isLinkSharingAllowed(): bool {
        return $this->config->getAppValue('core', 'shareapi_allow_links', 'yes') === 'yes';
    }

    private function isValidFeedTokenFormat(?string $token): bool {
        if ($token === null || $token === '') {
            return false;
        }
        return strlen($token) === 64 && ctype_alnum($token);
    }

    private function registerFeedBruteForceAttempt(): void {
        /** @var IThrottler */
        $throttler = \OC::$server->get(IThrottler::class);
        $throttler->registerAttempt(
            'intravox_feed',
            $this->request->getRemoteAddress()
        );
    }

    private function feedNotFoundResponse(): Response {
        // Random delay to mask timing differences
        usleep(random_int(10000, 50000));

        $response = new DataResponse(
            ['error' => 'Feed not found'],
            Http::STATUS_NOT_FOUND
        );
        $response->throttle();
        return $response;
    }
}
