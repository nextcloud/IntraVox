<?php
declare(strict_types=1);

// Temporary development diagnostic: checks only a standard form's CSRF token.
// It never accepts credentials or attempts to authenticate.
require_once $root . '/lib/versioncheck.php';
require_once $root . '/lib/base.php';
$request = \OCP\Server::get(\OCP\IRequest::class);
$manager = \OCP\Server::get(\OC\Security\CSRF\CsrfTokenManager::class);
$isPost = ($_SERVER['REQUEST_METHOD'] ?? '') === 'POST';
$result = null;
if ($isPost) {
    $result = [
        'form_token_received' => is_string($_POST['requesttoken'] ?? null) && $_POST['requesttoken'] !== '',
        'csrf_check_passed' => $request->passesCSRFCheck(),
    ];
    error_log('Credential-free session check: ' . json_encode($result, JSON_THROW_ON_ERROR));
}
$token = htmlspecialchars($manager->getToken()->getEncryptedValue(), ENT_QUOTES, 'UTF-8');
header('Content-Type: text/html; charset=utf-8');
header('Cache-Control: no-store');
header("Content-Security-Policy: default-src 'none'; style-src 'unsafe-inline'; form-action 'self'; frame-ancestors 'self'");
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: no-referrer');
?>
<!doctype html>
<html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>Login session check</title>
<style>body{font:18px/1.5 system-ui,sans-serif;max-width:650px;margin:60px auto;padding:20px;color:#172b4d;background:#f4f7fb}main{background:white;padding:30px;border:1px solid #ccd5e0;border-radius:12px}button{background:#006b9f;color:white;border:0;border-radius:6px;padding:12px 20px;font:inherit;cursor:pointer}code{font-size:16px}</style>
</head><body><main>
<h1>Login session check</h1>
<p>This checks a security token without signing in or sending a password. It does not count as a failed login.</p>
<?php if ($result !== null): ?>
<h2><?= $result['csrf_check_passed'] ? 'Session check passed' : 'Session check failed' ?></h2>
<p>Form token received: <strong><?= $result['form_token_received'] ? 'Yes' : 'No' ?></strong><br>
Security validation passed: <strong><?= $result['csrf_check_passed'] ? 'Yes' : 'No' ?></strong></p>
<p>You can return to the chat. No further login attempt is needed yet.</p>
<?php else: ?>
<form method="post" action="/session-check">
<input type="hidden" name="requesttoken" value="<?= $token ?>">
<button type="submit">Check session</button>
</form>
<?php endif; ?>
</main></body></html>
