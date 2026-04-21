<?php
declare(strict_types=1);

namespace OCA\IntraVox\Controller;

use OCA\IntraVox\AppInfo\Application;
use OCA\IntraVox\Service\FeedReaderService;
use OCA\IntraVox\Service\LmsOAuthService;
use OCA\IntraVox\Service\LmsTokenService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\DataDisplayResponse;
use OCP\AppFramework\Http\DataResponse;
use OCP\Http\Client\IClientService;
use OCP\IConfig;
use OCP\IRequest;
use OCP\IURLGenerator;
use OCP\Security\ICrypto;
use Psr\Log\LoggerInterface;

class LmsOAuthController extends Controller {
    public function __construct(
        string $appName,
        IRequest $request,
        private LmsOAuthService $oauthService,
        private LmsTokenService $tokenService,
        private FeedReaderService $feedReaderService,
        private IClientService $httpClient,
        private IConfig $config,
        private ICrypto $crypto,
        private IURLGenerator $urlGenerator,
        private LoggerInterface $logger,
        private ?string $userId = null,
    ) {
        parent::__construct($appName, $request);
    }

    /**
     * Get all LMS connections with the current user's connection status.
     *
     * @NoAdminRequired
     * @NoCSRFRequired
     *
     * @return DataResponse
     */
    public function getUserConnections(): DataResponse {
        if ($this->userId === null) {
            return new DataResponse(
                ['error' => 'Authentication required'],
                Http::STATUS_UNAUTHORIZED
            );
        }

        try {
            $adminConnections = $this->feedReaderService->getConnections();
            $userTokens = $this->tokenService->getUserConnections($this->userId);

            // Index user tokens by connection ID
            $userTokenMap = [];
            foreach ($userTokens as $token) {
                $userTokenMap[$token['connection_id']] = $token;
            }

            // Merge admin connections with user status
            $result = [];
            foreach ($adminConnections as $conn) {
                $authMode = $conn['authMode'] ?? 'token';
                // Only include connections that support user-level auth
                if ($authMode === 'token') {
                    continue;
                }

                $userToken = $userTokenMap[$conn['id']] ?? null;
                $result[] = [
                    'id' => $conn['id'],
                    'name' => $conn['name'],
                    'type' => $conn['type'],
                    'authMode' => $authMode,
                    'oidcAutoConnect' => $conn['oidcAutoConnect'] ?? false,
                    'connected' => $userToken !== null,
                    'tokenType' => $userToken['token_type'] ?? null,
                    'connectedAt' => $userToken['connected_at'] ?? null,
                ];
            }

            return new DataResponse(['connections' => $result]);
        } catch (\Exception $e) {
            $this->logger->error('[LmsOAuthController] Failed to get user connections', [
                'error' => $e->getMessage(),
            ]);
            return new DataResponse(
                ['error' => 'Failed to get connections'],
                Http::STATUS_INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     * Start an OAuth2 flow for a specific connection.
     * Returns the authorization URL for the frontend to redirect to.
     *
     * @NoAdminRequired
     *
     * @return DataResponse
     */
    public function startOAuth(string $connectionId): DataResponse {
        if ($this->userId === null) {
            return new DataResponse(
                ['error' => 'Authentication required'],
                Http::STATUS_UNAUTHORIZED
            );
        }

        try {
            $connections = $this->getFullConnections();
            $connection = null;
            foreach ($connections as $conn) {
                if ($conn['id'] === $connectionId) {
                    $connection = $conn;
                    break;
                }
            }

            if ($connection === null) {
                return new DataResponse(
                    ['error' => 'Connection not found'],
                    Http::STATUS_NOT_FOUND
                );
            }

            $authMode = $connection['authMode'] ?? 'token';
            if ($authMode === 'token') {
                return new DataResponse(
                    ['error' => 'This connection does not support OAuth2'],
                    Http::STATUS_BAD_REQUEST
                );
            }

            if (empty($connection['clientId']) || empty($connection['clientSecret'])) {
                return new DataResponse(
                    ['error' => 'OAuth2 credentials not configured for this connection'],
                    Http::STATUS_BAD_REQUEST
                );
            }

            $authUrl = $this->oauthService->getAuthorizationUrl($connection, $this->userId);

            return new DataResponse(['authUrl' => $authUrl]);
        } catch (\Exception $e) {
            $this->logger->error('[LmsOAuthController] Failed to start OAuth', [
                'connectionId' => $connectionId,
                'error' => $e->getMessage(),
            ]);
            return new DataResponse(
                ['error' => 'Failed to start OAuth flow'],
                Http::STATUS_INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     * OAuth2 callback endpoint. Exchanges the code for tokens and stores them.
     * Returns an HTML page that sends postMessage to the opener and closes the popup.
     *
     * @NoAdminRequired
     * @NoCSRFRequired
     *
     * @return DataDisplayResponse
     */
    public function callback(): DataDisplayResponse {
        $code = $this->request->getParam('code', '');
        $state = $this->request->getParam('state', '');
        $error = $this->request->getParam('error', '');

        if (!empty($error)) {
            $this->logger->warning('[LmsOAuthController] OAuth error from provider', [
                'error' => $error,
                'description' => $this->request->getParam('error_description', ''),
            ]);
            return $this->oauthPopupResponse(false, 'Authorization was denied or failed.');
        }

        if (empty($code) || empty($state)) {
            return $this->oauthPopupResponse(false, 'Invalid callback parameters.');
        }

        try {
            $tokens = $this->oauthService->handleCallback($code, $state);

            $expiresAt = null;
            if (isset($tokens['expires_in']) && $tokens['expires_in'] > 0) {
                $expiresAt = new \DateTime('+' . $tokens['expires_in'] . ' seconds');
            }

            $this->tokenService->saveUserToken(
                $tokens['userId'],
                $tokens['connectionId'],
                $tokens['access_token'],
                $tokens['refresh_token'],
                'oauth2',
                $expiresAt,
            );

            return $this->oauthPopupResponse(true);
        } catch (\Exception $e) {
            $this->logger->error('[LmsOAuthController] OAuth callback failed', [
                'error' => $e->getMessage(),
            ]);
            return $this->oauthPopupResponse(false, 'Connection failed. Please try again.');
        }
    }

    /**
     * Return an HTML response that notifies the opener window and closes the popup.
     */
    private function oauthPopupResponse(bool $success, string $error = ''): DataDisplayResponse {
        $message = json_encode([
            'type' => 'intravox-lms-connected',
            'success' => $success,
            'error' => $error,
        ], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);

        $statusText = $success
            ? 'Connected successfully. This window will close.'
            : 'Connection failed. This window will close.';

        $html = '<!DOCTYPE html><html><body><script>'
            . 'if(window.opener){window.opener.postMessage(' . $message . ',window.location.origin);}'
            . 'window.close();'
            . '</script><p>' . htmlspecialchars($statusText) . '</p></body></html>';

        $response = new DataDisplayResponse($html);
        $response->addHeader('Content-Type', 'text/html; charset=utf-8');
        return $response;
    }

    /**
     * Save a manually entered API token (e.g. Moodle web service token).
     *
     * @NoAdminRequired
     *
     * @return DataResponse
     */
    public function saveManualToken(string $connectionId): DataResponse {
        if ($this->userId === null) {
            return new DataResponse(
                ['error' => 'Authentication required'],
                Http::STATUS_UNAUTHORIZED
            );
        }

        $token = $this->request->getParam('token', '');
        if (empty($token)) {
            return new DataResponse(
                ['error' => 'Token is required'],
                Http::STATUS_BAD_REQUEST
            );
        }

        try {
            // Validate the token by making a test API call
            $connections = $this->getFullConnections();
            $connection = null;
            foreach ($connections as $conn) {
                if ($conn['id'] === $connectionId) {
                    $connection = $conn;
                    break;
                }
            }

            if ($connection === null) {
                return new DataResponse(
                    ['error' => 'Connection not found'],
                    Http::STATUS_NOT_FOUND
                );
            }

            $valid = $this->validateManualToken($connection, $token);
            if (!$valid) {
                return new DataResponse(
                    ['error' => 'Token validation failed. Please check that the token is correct and has the required permissions.'],
                    Http::STATUS_BAD_REQUEST
                );
            }

            $this->tokenService->saveUserToken(
                $this->userId,
                $connectionId,
                $token,
                null,
                'manual',
                null,
            );

            return new DataResponse(['status' => 'ok']);
        } catch (\Exception $e) {
            $this->logger->error('[LmsOAuthController] Failed to save manual token', [
                'connectionId' => $connectionId,
                'error' => $e->getMessage(),
            ]);
            return new DataResponse(
                ['error' => 'Failed to save token'],
                Http::STATUS_INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     * Disconnect a user's LMS account (delete their token).
     *
     * @NoAdminRequired
     *
     * @return DataResponse
     */
    public function disconnect(string $connectionId): DataResponse {
        if ($this->userId === null) {
            return new DataResponse(
                ['error' => 'Authentication required'],
                Http::STATUS_UNAUTHORIZED
            );
        }

        try {
            $this->tokenService->deleteUserToken($this->userId, $connectionId);
            return new DataResponse(['status' => 'ok']);
        } catch (\Exception $e) {
            return new DataResponse(
                ['error' => 'Failed to disconnect'],
                Http::STATUS_INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     * Validate a manual token by making a test API call.
     */
    private function validateManualToken(array $connection, string $token): bool {
        $type = $connection['type'] ?? '';
        $baseUrl = rtrim($connection['baseUrl'] ?? '', '/');

        try {
            $client = $this->httpClient->newClient();

            if ($type === 'moodle') {
                $response = $client->post($baseUrl . '/webservice/rest/server.php', [
                    'timeout' => 10,
                    'body' => [
                        'wstoken' => $token,
                        'wsfunction' => 'core_webservice_get_site_info',
                        'moodlewsrestformat' => 'json',
                    ],
                ]);

                $data = json_decode($response->getBody(), true);
                return is_array($data) && !isset($data['exception']) && isset($data['sitename']);
            }

            if ($type === 'canvas') {
                $response = $client->get($baseUrl . '/api/v1/users/self', [
                    'timeout' => 10,
                    'headers' => [
                        'Authorization' => 'Bearer ' . $token,
                        'Accept' => 'application/json',
                    ],
                ]);

                $data = json_decode($response->getBody(), true);
                return is_array($data) && isset($data['id']);
            }

            if ($type === 'brightspace') {
                $response = $client->get($baseUrl . '/d2l/api/lp/1.43/users/whoami', [
                    'timeout' => 10,
                    'headers' => [
                        'Authorization' => 'Bearer ' . $token,
                        'Accept' => 'application/json',
                    ],
                ]);

                $data = json_decode($response->getBody(), true);
                return is_array($data) && isset($data['Identifier']);
            }

            return false;
        } catch (\Exception $e) {
            $this->logger->warning('[LmsOAuthController] Token validation failed', [
                'type' => $type,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Get full connection data including secrets.
     */
    private function getFullConnections(): array {
        $json = $this->config->getAppValue(Application::APP_ID, 'feed_connections', '[]');
        return json_decode($json, true) ?: [];
    }
}
