<?php
declare(strict_types=1);

namespace OCA\IntraVox\Service;

use OCP\IEventDispatcher;
use Psr\Log\LoggerInterface;

/**
 * Bridge to Nextcloud's user_oidc app for zero-click LMS authentication.
 *
 * When Nextcloud and the LMS (Canvas/Moodle) share the same OIDC identity provider,
 * the IdP token stored by user_oidc can be used to access the LMS API directly.
 * This means users see personalized content without any manual account linking.
 *
 * Works with any OIDC provider: Keycloak, Azure AD/Entra ID, Authentik, Okta, etc.
 *
 * Requirements:
 * - user_oidc app installed and configured
 * - store_login_token enabled: occ config:app:set --value=1 user_oidc store_login_token
 * - LMS uses the same IdP as Nextcloud
 * - IdP token has audience/scope accepted by the LMS API
 */
class OidcTokenBridge {
    private const EVENT_CLASS = 'OCA\\UserOIDC\\Event\\ExternalTokenRequestedEvent';
    private const EXCEPTION_CLASS = 'OCA\\UserOIDC\\Exception\\GetExternalTokenFailedException';

    public function __construct(
        private IEventDispatcher $eventDispatcher,
        private LoggerInterface $logger,
    ) {}

    /**
     * Check if the OIDC token bridge is available (user_oidc installed and configured).
     */
    public function isAvailable(): bool {
        return class_exists(self::EVENT_CLASS);
    }

    /**
     * Try to get the stored OIDC access token for the current user.
     *
     * @return array|null {access_token: string, expires_in: ?int} or null if unavailable
     */
    public function getToken(): ?array {
        if (!$this->isAvailable()) {
            return null;
        }

        try {
            $eventClass = self::EVENT_CLASS;
            $event = new $eventClass();

            $this->eventDispatcher->dispatchTyped($event);

            $token = $event->getToken();
            if ($token === null) {
                return null;
            }

            return [
                'access_token' => $token->getAccessToken(),
                'id_token' => method_exists($token, 'getIdToken') ? $token->getIdToken() : null,
                'expires_in' => method_exists($token, 'getExpiresInFromNow') ? $token->getExpiresInFromNow() : null,
            ];
        } catch (\Throwable $e) {
            // Catch both GetExternalTokenFailedException and any other errors
            $this->logger->debug('[OidcTokenBridge] Failed to get OIDC token', [
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Test if the OIDC token works for a specific LMS API.
     *
     * @param string $type LMS type (canvas, moodle)
     * @param string $baseUrl LMS base URL
     * @param string $accessToken The OIDC access token to test
     * @return bool Whether the token is accepted by the LMS
     */
    public function validateTokenForLms(string $type, string $baseUrl, string $accessToken): bool {
        // This validation is delegated to the caller (FeedReaderService)
        // since it already has the HTTP client infrastructure.
        // This method exists as a contract for future use.
        return false;
    }
}
