<?php
declare(strict_types=1);

// Development-only, temporary diagnostics. Never log credentials, cookie
// values, tokens, account names, IP addresses, or full request headers.
return static function (string $root): void {
    if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
        return;
    }
    try {
        $initialTokenPresent = isset($_POST['requesttoken']) && is_string($_POST['requesttoken']) && $_POST['requesttoken'] !== '';
        $userFieldPresent = isset($_POST['user']);
        $passwordFieldPresent = isset($_POST['password']);
        require_once $root . '/lib/versioncheck.php';
        require_once $root . '/lib/base.php';
        $request = \OCP\Server::get(\OCP\IRequest::class);
        $params = session_get_cookie_params();
        $prefix = ($params['secure'] && $params['path'] === '/') ? '__Host-' : '';
        $token = $_POST['requesttoken'] ?? null;
        $validToken = false;
        if (is_string($token) && $token !== '') {
            $manager = \OCP\Server::get(\OC\Security\CSRF\CsrfTokenManager::class);
            $validToken = $manager->isTokenValid(new \OC\Security\CSRF\CsrfToken($token));
        }
        error_log('Login session diagnostic: ' . json_encode([
            'session_cookie_received' => isset($_COOKIE[session_name()]),
            'passphrase_cookie_received' => isset($_COOKIE['oc_sessionPassphrase']),
            'strict_cookie_received' => ($_COOKIE[$prefix . 'nc_sameSiteCookiestrict'] ?? null) === 'true',
            'lax_cookie_received' => ($_COOKIE[$prefix . 'nc_sameSiteCookielax'] ?? null) === 'true',
            'form_token_received' => is_string($token) && $token !== '',
            'form_token_received_before_bootstrap' => $initialTokenPresent,
            'user_field_received' => $userFieldPresent,
            'password_field_received' => $passwordFieldPresent,
            'urlencoded_form' => str_starts_with($_SERVER['CONTENT_TYPE'] ?? '', 'application/x-www-form-urlencoded'),
            'multipart_form' => str_starts_with($_SERVER['CONTENT_TYPE'] ?? '', 'multipart/form-data'),
            'form_token_valid' => $validToken,
            'csrf_check_passed' => $request->passesCSRFCheck(),
            'https_detected' => $request->getServerProtocol() === 'https',
            'firefox' => str_contains($_SERVER['HTTP_USER_AGENT'] ?? '', 'Firefox/'),
        ], JSON_THROW_ON_ERROR));
    } catch (\Throwable $exception) {
        // Preserve the normal front controller's error handling.
        error_log('Login session diagnostic unavailable: ' . get_class($exception));
    }
};
