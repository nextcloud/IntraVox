<?php
declare(strict_types=1);

namespace OCA\IntraVox\Service;

use OCA\IntraVox\AppInfo\Application;
use OCP\IConfig;
use OCP\Security\ICrypto;
use Psr\Log\LoggerInterface;

/**
 * Service for managing per-user LMS tokens (OAuth2, manual, OIDC).
 *
 * Tokens are stored in Nextcloud's user preferences (oc_preferences) table,
 * encrypted with ICrypto. This ensures automatic cleanup when users are deleted
 * by Nextcloud core — no custom listener needed.
 *
 * Storage key format: lms_token_{connectionId}
 * Storage value: ICrypto encrypted JSON with access_token, refresh_token, token_type, expires_at
 */
class LmsTokenService {
    private const KEY_PREFIX = 'lms_token_';

    public function __construct(
        private IConfig $config,
        private ICrypto $crypto,
        private LoggerInterface $logger,
    ) {}

    /**
     * Get the decrypted token for a user + connection.
     *
     * @return array|null {access_token, refresh_token, token_type, expires_at}
     */
    public function getUserToken(string $userId, string $connectionId): ?array {
        $encrypted = $this->config->getUserValue(
            $userId, Application::APP_ID, self::KEY_PREFIX . $connectionId, ''
        );

        if (empty($encrypted)) {
            return null;
        }

        try {
            $json = $this->crypto->decrypt($encrypted);
            $data = json_decode($json, true);
            if (!is_array($data) || empty($data['access_token'])) {
                return null;
            }
            return $data;
        } catch (\Exception $e) {
            $this->logger->error('[LmsTokenService] Failed to decrypt token', [
                'userId' => $userId,
                'connectionId' => $connectionId,
            ]);
            return null;
        }
    }

    /**
     * Save or update a user's LMS token for a connection.
     */
    private const MAX_TOKEN_LENGTH = 8192;

    public function saveUserToken(
        string $userId,
        string $connectionId,
        string $accessToken,
        ?string $refreshToken,
        string $tokenType,
        ?\DateTime $expiresAt = null,
    ): void {
        if (strlen($accessToken) > self::MAX_TOKEN_LENGTH) {
            throw new \InvalidArgumentException('Access token exceeds maximum length');
        }
        if ($refreshToken !== null && strlen($refreshToken) > self::MAX_TOKEN_LENGTH) {
            throw new \InvalidArgumentException('Refresh token exceeds maximum length');
        }

        $data = [
            'access_token' => $accessToken,
            'refresh_token' => $refreshToken,
            'token_type' => $tokenType,
            'expires_at' => $expiresAt?->format('c'),
            'created_at' => date('c'),
        ];

        $encrypted = $this->crypto->encrypt(json_encode($data));

        $this->config->setUserValue(
            $userId, Application::APP_ID, self::KEY_PREFIX . $connectionId, $encrypted
        );

        $this->logger->info('[LmsTokenService] Token saved', [
            'userId' => $userId,
            'connectionId' => $connectionId,
            'tokenType' => $tokenType,
        ]);
    }

    /**
     * Delete a user's token for a specific connection.
     */
    public function deleteUserToken(string $userId, string $connectionId): bool {
        $existing = $this->config->getUserValue(
            $userId, Application::APP_ID, self::KEY_PREFIX . $connectionId, ''
        );

        if (empty($existing)) {
            return false;
        }

        $this->config->deleteUserValue(
            $userId, Application::APP_ID, self::KEY_PREFIX . $connectionId
        );

        $this->logger->info('[LmsTokenService] Token deleted', [
            'userId' => $userId,
            'connectionId' => $connectionId,
        ]);

        return true;
    }

    /**
     * Get all connection IDs a user has tokens for, with their status.
     *
     * @return array<array{connection_id: string, token_type: string, expires_at: ?string, connected: bool}>
     */
    public function getUserConnections(string $userId): array {
        $allKeys = $this->config->getUserKeys($userId, Application::APP_ID);
        $connections = [];

        foreach ($allKeys as $key) {
            if (!str_starts_with($key, self::KEY_PREFIX)) {
                continue;
            }

            $connectionId = substr($key, strlen(self::KEY_PREFIX));
            $token = $this->getUserToken($userId, $connectionId);

            if ($token !== null) {
                $connections[] = [
                    'connection_id' => $connectionId,
                    'token_type' => $token['token_type'] ?? 'unknown',
                    'expires_at' => $token['expires_at'] ?? null,
                    'connected_at' => $token['created_at'] ?? null,
                    'connected' => true,
                ];
            }
        }

        return $connections;
    }

    /**
     * Check if a token is expired (with 60-second buffer).
     */
    public function isTokenExpired(string $userId, string $connectionId): bool {
        $token = $this->getUserToken($userId, $connectionId);

        if ($token === null) {
            return true;
        }

        // Manual tokens don't expire
        if (($token['token_type'] ?? '') === 'manual') {
            return false;
        }

        // No expiry set means doesn't expire
        if (empty($token['expires_at'])) {
            return false;
        }

        $expiresAt = new \DateTime($token['expires_at']);
        $buffer = new \DateTime('+60 seconds');

        return $expiresAt <= $buffer;
    }
}
