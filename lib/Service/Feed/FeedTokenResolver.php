<?php
declare(strict_types=1);

namespace OCA\IntraVox\Service\Feed;

use OCA\IntraVox\Service\LmsOAuthService;
use OCA\IntraVox\Service\LmsTokenService;
use OCA\IntraVox\Service\OidcTokenBridge;
use OCP\Http\Client\IClientService;
use OCP\ICache;
use OCP\Security\ICrypto;
use Psr\Log\LoggerInterface;

/**
 * Which credential a feed request runs under.
 *
 * Extracted from FeedReaderService (service split, fase 2). All seven
 * connectors call resolveToken(); the other three methods exist only to serve
 * it. Keeping that decision in one small class is worth more than the line
 * count, because the precedence order below is a security contract:
 *
 *   1. client_credentials -- an app-level token, no user involved
 *   2. authMode "token"   -- the ADMIN token, and ONLY for a signed-in user.
 *                            A public share must never borrow it, which is
 *                            why $userId === null returns null here rather
 *                            than falling through.
 *   3. OIDC auto-connect  -- the signed-in user's own IdP token
 *   4. per-user token     -- OAuth2 (refreshed when expired) or manual
 *
 * An expired OAuth2 token that cannot be refreshed is DELETED rather than
 * retried, so the user is asked to reconnect instead of hitting the LMS with
 * a dead credential on every page load.
 *
 * getConnection() stays behind and arrives as a callable: it reads appconfig
 * and is shared with the connection store, whose projection is pinned by
 * FeedConnectionProjectionTest against the service source (FEED-CRED).
 */
class FeedTokenResolver {
    /**
     * Same value as FeedReaderService::HTTP_TIMEOUT. Duplicated rather than
     * shared because the service still uses it in 21 places; if one changes,
     * the other should be changed with it.
     */
    private const HTTP_TIMEOUT = 5;

    /**
     * @param callable(string):?array $connectionLookup the stored connection by id
     */
    public function __construct(
        private LmsTokenService $lmsTokenService,
        private LmsOAuthService $lmsOAuthService,
        private ICrypto $crypto,
        private IClientService $httpClient,
        private LoggerInterface $logger,
        private $connectionLookup,
        private ?ICache $cache = null,
        private ?OidcTokenBridge $oidcTokenBridge = null,
    ) {
    }

    /**
     * Resolve the best available token for a connection + user.
     *
     * Priority: OIDC auto-connect > per-user OAuth2/manual > admin fallback
     *
     * @return array|null {token: string, source: string} or null if no token available
     */
    public function resolveToken(string $connectionId, ?string $userId): ?array {
        $connection = ($this->connectionLookup)($connectionId);
        if ($connection === null) {
            return null;
        }

        // Client credentials flow (app-level, e.g. SharePoint/Graph API)
        if (($connection['authMethod'] ?? '') === 'client_credentials') {
            return $this->acquireClientCredentialsToken($connection);
        }

        $authMode = $connection['authMode'] ?? 'token';

        // Legacy mode: use admin token (only for authenticated users, not public shares)
        if ($authMode === 'token') {
            if ($userId === null) {
                return null; // Don't expose admin token to public/anonymous requests
            }
            return $this->decryptAdminToken($connection);
        }

        // Try user-level tokens if we have a userId
        if ($userId !== null) {
            // 1. Try OIDC auto-connect
            if (($connection['oidcAutoConnect'] ?? false) && $this->oidcTokenBridge !== null) {
                $oidcToken = $this->oidcTokenBridge->getToken();
                if ($oidcToken !== null && !empty($oidcToken['access_token'])) {
                    return [
                        'token' => $oidcToken['access_token'],
                        'source' => 'oidc',
                    ];
                }
            }

            // 2. Try per-user stored token (OAuth2 or manual)
            $userToken = $this->lmsTokenService->getUserToken($userId, $connectionId);
            if ($userToken !== null) {
                // Check if OAuth2 token needs refresh
                if ($userToken['token_type'] === 'oauth2' && $this->lmsTokenService->isTokenExpired($userId, $connectionId)) {
                    $refreshed = $this->tryRefreshToken($connection, $userToken, $userId, $connectionId);
                    if ($refreshed !== null) {
                        return $refreshed;
                    }
                    // Refresh failed — token is invalid, delete it
                    $this->lmsTokenService->deleteUserToken($userId, $connectionId);
                } else {
                    return [
                        'token' => $userToken['access_token'],
                        'source' => $userToken['token_type'],
                    ];
                }
            }
        }

        // 3. Fall back to admin token (only for "both" mode, authenticated users only)
        if ($authMode === 'both' && $userId !== null) {
            return $this->decryptAdminToken($connection);
        }

        return null;
    }
    private function decryptAdminToken(array $connection): ?array {
        if (empty($connection['token'])) {
            return null;
        }
        try {
            return [
                'token' => $this->crypto->decrypt($connection['token']),
                'source' => 'admin',
            ];
        } catch (\Exception $e) {
            $this->logger->error('IntraVox: Failed to decrypt admin token', [
                'connectionId' => $connection['id'] ?? 'unknown',
            ]);
            return null;
        }
    }
    /**
     * Try to refresh an expired OAuth2 token.
     */
    private function tryRefreshToken(array $connection, array $userToken, string $userId, string $connectionId): ?array {
        if (empty($userToken['refresh_token'])) {
            return null;
        }

        try {
            $refreshed = $this->lmsOAuthService->refreshToken($connection, $userToken['refresh_token']);

            $expiresAt = null;
            if (isset($refreshed['expires_in']) && $refreshed['expires_in'] > 0) {
                $expiresAt = new \DateTime('+' . $refreshed['expires_in'] . ' seconds');
            }

            $this->lmsTokenService->saveUserToken(
                $userId,
                $connectionId,
                $refreshed['access_token'],
                $refreshed['refresh_token'] ?? $userToken['refresh_token'],
                'oauth2',
                $expiresAt,
            );

            return [
                'token' => $refreshed['access_token'],
                'source' => 'oauth2',
            ];
        } catch (\Exception $e) {
            $this->logger->warning('IntraVox: Token refresh failed', [
                'connectionId' => $connectionId,
                'userId' => $userId,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }
    /**
     * Acquire an access token using OAuth2 client_credentials flow.
     * Used for app-level authentication (e.g., Microsoft Graph API for SharePoint).
     *
     * Tokens are cached in the distributed cache. No refresh_token is used —
     * when the token expires, a new one is requested with the same credentials.
     *
     * @return array|null {token: string, source: string}
     */
    private function acquireClientCredentialsToken(array $connection): ?array {
        $tenantId = $connection['tenantId'] ?? '';
        $clientId = $connection['clientId'] ?? '';
        $encryptedSecret = $connection['clientSecret'] ?? '';

        if (empty($tenantId) || empty($clientId) || empty($encryptedSecret)) {
            return null;
        }

        $connectionId = $connection['id'] ?? '';
        $cacheKey = 'cc_token_' . md5($connectionId);

        // Check cache first
        if ($this->cache !== null) {
            $cached = $this->cache->get($cacheKey);
            if ($cached !== null) {
                return ['token' => $cached, 'source' => 'client_credentials'];
            }
        }

        try {
            $clientSecret = $this->crypto->decrypt($encryptedSecret);
        } catch (\Exception $e) {
            $this->logger->error('IntraVox: Failed to decrypt client secret for client_credentials', [
                'connectionId' => $connectionId,
            ]);
            return null;
        }

        $tokenUrl = 'https://login.microsoftonline.com/' . urlencode($tenantId) . '/oauth2/v2.0/token';

        try {
            $client = $this->httpClient->newClient();
            $response = $client->post($tokenUrl, [
                'timeout' => self::HTTP_TIMEOUT,
                'body' => [
                    'grant_type' => 'client_credentials',
                    'client_id' => $clientId,
                    'client_secret' => $clientSecret,
                    'scope' => 'https://graph.microsoft.com/.default',
                ],
            ]);

            $data = json_decode($response->getBody(), true);
            if (!is_array($data) || empty($data['access_token'])) {
                $this->logger->error('IntraVox: client_credentials token response missing access_token', [
                    'connectionId' => $connectionId,
                ]);
                return null;
            }

            $accessToken = $data['access_token'];
            $expiresIn = isset($data['expires_in']) ? (int) $data['expires_in'] : 3600;

            // Cache with safety buffer (2 minutes before actual expiry, minimum 60s)
            if ($this->cache !== null) {
                $cacheTtl = max(60, $expiresIn - 120);
                $this->cache->set($cacheKey, $accessToken, $cacheTtl);
            }

            return ['token' => $accessToken, 'source' => 'client_credentials'];
        } catch (\Exception $e) {
            $this->logger->error('IntraVox: client_credentials token request failed', [
                'connectionId' => $connectionId,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }
}
