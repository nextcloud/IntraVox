<?php
declare(strict_types=1);

namespace OCA\IntraVox\Service;

use OCA\IntraVox\AppInfo\Application;
use OCP\Http\Client\IClientService;
use OCP\ICacheFactory;
use OCP\IConfig;
use OCP\IURLGenerator;
use OCP\Security\ICrypto;
use OCP\Security\ISecureRandom;
use Psr\Log\LoggerInterface;

/**
 * Service for handling OAuth2 flows with Canvas and Moodle LMS platforms.
 *
 * Supports:
 * - Canvas: native OAuth2 via Developer Keys
 * - Moodle: OAuth2 via local_oauth2 plugin
 * - Brightspace: OAuth2 via Manage Extensibility
 * - Token refresh for expired tokens
 */
class LmsOAuthService {
    private const STATE_TTL = 600; // 10 minutes
    private const HTTP_TIMEOUT = 10;

    public function __construct(
        private IClientService $httpClient,
        private ICacheFactory $cacheFactory,
        private IConfig $config,
        private ICrypto $crypto,
        private ISecureRandom $secureRandom,
        private IURLGenerator $urlGenerator,
        private LoggerInterface $logger,
    ) {}

    /**
     * Build the OAuth2 authorization URL for a given connection.
     *
     * @return string The authorization URL to redirect the user to
     */
    public function getAuthorizationUrl(array $connection, string $userId): string {
        $state = $this->generateState($connection['id'], $userId);
        $redirectUri = $this->getCallbackUrl();

        $type = $connection['type'] ?? '';
        $baseUrl = rtrim($connection['baseUrl'] ?? '', '/');
        $clientId = $connection['clientId'] ?? '';

        return match ($type) {
            'canvas' => $baseUrl . '/login/oauth2/auth?' . http_build_query([
                'client_id' => $clientId,
                'response_type' => 'code',
                'redirect_uri' => $redirectUri,
                'state' => $state,
            ]),
            'moodle' => $baseUrl . '/local/oauth2/authorize.php?' . http_build_query([
                'client_id' => $clientId,
                'response_type' => 'code',
                'redirect_uri' => $redirectUri,
                'state' => $state,
            ]),
            'brightspace' => $baseUrl . '/d2l/lp/auth/oauth2/authorize?' . http_build_query([
                'client_id' => $clientId,
                'response_type' => 'code',
                'redirect_uri' => $redirectUri,
                'state' => $state,
                'scope' => 'core:*:*',
            ]),
            'nextcloud' => $baseUrl . '/apps/oauth2/authorize?' . http_build_query([
                'client_id' => $clientId,
                'response_type' => 'code',
                'redirect_uri' => $redirectUri,
                'state' => $state,
            ]),
            default => throw new \InvalidArgumentException("OAuth2 not supported for type: $type"),
        };
    }

    /**
     * Exchange an authorization code for access + refresh tokens.
     *
     * @return array{access_token: string, refresh_token: ?string, expires_in: ?int}
     */
    public function handleCallback(string $code, string $state): array {
        $stateData = $this->validateState($state);
        if ($stateData === null) {
            throw new \RuntimeException('Invalid or expired OAuth2 state');
        }

        $connection = $this->getConnectionWithSecrets($stateData['connectionId']);
        if ($connection === null) {
            throw new \RuntimeException('LMS connection not found');
        }

        $type = $connection['type'] ?? '';
        $baseUrl = rtrim($connection['baseUrl'] ?? '', '/');
        $clientId = $connection['clientId'] ?? '';
        $clientSecret = $this->crypto->decrypt($connection['clientSecret']);

        $tokenUrl = match ($type) {
            'canvas' => $baseUrl . '/login/oauth2/token',
            'moodle' => $baseUrl . '/local/oauth2/token.php',
            'brightspace' => $baseUrl . '/d2l/lp/auth/oauth2/token',
            'nextcloud' => $baseUrl . '/apps/oauth2/api/v1/token',
            default => throw new \RuntimeException("OAuth2 not supported for type: $type"),
        };

        $client = $this->httpClient->newClient();
        $response = $client->post($tokenUrl, [
            'timeout' => self::HTTP_TIMEOUT,
            'body' => [
                'grant_type' => 'authorization_code',
                'code' => $code,
                'redirect_uri' => $this->getCallbackUrl(),
                'client_id' => $clientId,
                'client_secret' => $clientSecret,
            ],
        ]);

        $data = json_decode($response->getBody(), true);
        if (!is_array($data) || empty($data['access_token'])) {
            $this->logger->error('[LmsOAuthService] Token exchange failed', [
                'type' => $type,
                'httpStatus' => $response->getStatusCode(),
            ]);
            throw new \RuntimeException('Token exchange failed');
        }

        return [
            'access_token' => $data['access_token'],
            'refresh_token' => $data['refresh_token'] ?? null,
            'expires_in' => isset($data['expires_in']) ? (int)$data['expires_in'] : null,
            'connectionId' => $stateData['connectionId'],
            'userId' => $stateData['userId'],
        ];
    }

    /**
     * Refresh an expired OAuth2 access token using the refresh token.
     *
     * @return array{access_token: string, refresh_token: ?string, expires_in: ?int}
     */
    public function refreshToken(array $connection, string $refreshToken): array {
        $type = $connection['type'] ?? '';
        $baseUrl = rtrim($connection['baseUrl'] ?? '', '/');
        $clientId = $connection['clientId'] ?? '';
        $clientSecret = $this->crypto->decrypt($connection['clientSecret']);

        $tokenUrl = match ($type) {
            'canvas' => $baseUrl . '/login/oauth2/token',
            'moodle' => $baseUrl . '/local/oauth2/token.php',
            'brightspace' => $baseUrl . '/d2l/lp/auth/oauth2/token',
            'nextcloud' => $baseUrl . '/apps/oauth2/api/v1/token',
            default => throw new \RuntimeException("Token refresh not supported for type: $type"),
        };

        $client = $this->httpClient->newClient();
        $response = $client->post($tokenUrl, [
            'timeout' => self::HTTP_TIMEOUT,
            'body' => [
                'grant_type' => 'refresh_token',
                'refresh_token' => $refreshToken,
                'client_id' => $clientId,
                'client_secret' => $clientSecret,
            ],
        ]);

        $data = json_decode($response->getBody(), true);
        if (!is_array($data) || empty($data['access_token'])) {
            throw new \RuntimeException('Token refresh failed');
        }

        return [
            'access_token' => $data['access_token'],
            'refresh_token' => $data['refresh_token'] ?? $refreshToken,
            'expires_in' => isset($data['expires_in']) ? (int)$data['expires_in'] : null,
        ];
    }

    /**
     * Get the OAuth2 callback URL for this Nextcloud instance.
     */
    public function getCallbackUrl(): string {
        return $this->urlGenerator->linkToRouteAbsolute('intravox.lmsOAuth.callback');
    }

    /**
     * Generate a CSRF state parameter.
     *
     * Uses HMAC-signed payload so state validation works even without cache.
     * Cache is used for one-time-use enforcement when available.
     */
    private function generateState(string $connectionId, string $userId): string {
        $nonce = $this->secureRandom->generate(16, ISecureRandom::CHAR_ALPHANUMERIC);
        $timestamp = time();

        $payload = json_encode([
            'c' => $connectionId,
            'u' => $userId,
            't' => $timestamp,
            'n' => $nonce,
        ]);

        $secret = $this->getHmacSecret();
        $signature = hash_hmac('sha256', $payload, $secret);

        // Mark nonce as used in cache (for one-time-use enforcement)
        $cache = $this->getCache();
        if ($cache !== null) {
            $cache->set('oauth_nonce_' . $nonce, '1', self::STATE_TTL);
        }

        return $this->base64UrlEncode($payload) . '.' . $signature;
    }

    /**
     * Validate and consume a state parameter.
     *
     * Verifies HMAC signature (works without cache) and checks nonce
     * for one-time use (when cache is available).
     *
     * @return array|null {connectionId, userId}
     */
    private function validateState(string $state): ?array {
        $parts = explode('.', $state, 2);
        if (count($parts) !== 2) {
            return null;
        }

        [$encodedPayload, $signature] = $parts;
        $payload = $this->base64UrlDecode($encodedPayload);
        if ($payload === false) {
            return null;
        }

        // Verify HMAC signature
        $secret = $this->getHmacSecret();
        $expectedSignature = hash_hmac('sha256', $payload, $secret);
        if (!hash_equals($expectedSignature, $signature)) {
            return null;
        }

        $decoded = json_decode($payload, true);
        if (!is_array($decoded) || empty($decoded['c']) || empty($decoded['u']) || empty($decoded['t'])) {
            return null;
        }

        // Check TTL
        if ((time() - $decoded['t']) > self::STATE_TTL) {
            return null;
        }

        // One-time use: check and consume nonce via cache
        if (empty($decoded['n'])) {
            return null;
        }
        $cache = $this->getCache();
        if ($cache === null) {
            // Cache is required for one-time-use enforcement — reject without it
            $this->logger->error('[LmsOAuthService] Cache unavailable, cannot validate OAuth nonce');
            return null;
        }
        $nonceKey = 'oauth_nonce_' . $decoded['n'];
        if ($cache->get($nonceKey) === null) {
            // Nonce already consumed or never existed
            return null;
        }
        $cache->remove($nonceKey);

        return [
            'connectionId' => $decoded['c'],
            'userId' => $decoded['u'],
        ];
    }

    /**
     * Get a connection with its secrets (clientId, encrypted clientSecret).
     */
    private function getConnectionWithSecrets(string $connectionId): ?array {
        $json = $this->config->getAppValue(Application::APP_ID, 'feed_connections', '[]');
        $connections = json_decode($json, true) ?: [];

        foreach ($connections as $conn) {
            if (($conn['id'] ?? '') === $connectionId && ($conn['active'] ?? true)) {
                return $conn;
            }
        }

        return null;
    }

    private function getCache(): ?\OCP\ICache {
        if ($this->cacheFactory->isAvailable()) {
            return $this->cacheFactory->createDistributed('intravox-oauth');
        }
        return null;
    }

    private function getHmacSecret(): string {
        $secret = $this->config->getSystemValueString('secret', '');
        if (empty($secret)) {
            throw new \RuntimeException('Nextcloud system secret is not configured. OAuth2 state signing requires a valid secret.');
        }
        return $secret;
    }

    private function base64UrlEncode(string $data): string {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    private function base64UrlDecode(string $data): string|false {
        return base64_decode(strtr($data, '-_', '+/'), true);
    }
}
