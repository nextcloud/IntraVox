<?php
declare(strict_types=1);

// PHP's built-in server ignores .htaccess. Never serve the repository root,
// arbitrary PHP files, config, SQLite data, or linked source files.
$root = dirname(__DIR__, 2) . '/.runtime/nextcloud';
$uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
$path = is_string($uri) ? rawurldecode($uri) : '';
$segments = explode('/', $path);
$deny = static function (): never {
    http_response_code(404);
    header('Content-Type: text/plain; charset=utf-8');
    echo "Not found\n";
    exit;
};
if ($path === '' || str_contains($path, "\0") || str_contains($path, '\\')) {
    $deny();
}
foreach ($segments as $segment) {
    if (str_starts_with($segment, '.')) {
        $deny();
    }
}

if ($path === '/session-check') {
    $_SERVER['SCRIPT_NAME'] = '/index.php';
    $_SERVER['SCRIPT_FILENAME'] = $root . '/index.php';
    $_SERVER['PHP_SELF'] = '/index.php';
    $_SERVER['PATH_INFO'] = '';
    chdir($root);
    require __DIR__ . '/session-check.php';
    return true;
}

$entrypoints = ['index.php', 'remote.php', 'public.php', 'status.php', 'ocs/v1.php', 'ocs/v2.php', 'ocs-provider/index.php'];
foreach ($entrypoints as $entrypoint) {
    $prefix = '/' . $entrypoint;
    if ($path === $prefix || str_starts_with($path, $prefix . '/')) {
        $_SERVER['SCRIPT_NAME'] = $prefix;
        $_SERVER['SCRIPT_FILENAME'] = $root . $prefix;
        $_SERVER['PHP_SELF'] = $path;
        $_SERVER['PATH_INFO'] = substr($path, strlen($prefix));
        chdir($root);
        if ($path === '/index.php/login') {
            $diagnoseLogin = require __DIR__ . '/login-diagnostics.php';
            $diagnoseLogin($root);
        }
        require $root . $prefix;
        return true;
    }
}

// Only known public asset directories and file formats may bypass Nextcloud.
if (preg_match('~^/(core|dist|apps|custom_apps|themes|resources)/~', $path)
    && preg_match('~\.(css|js|mjs|png|jpg|jpeg|gif|svg|ico|webp|avif|woff|woff2|ttf|otf|webmanifest)$~i', $path)
    && !preg_match('~/(config|data|lib|tests|node_modules|vendor|3rdparty)/~', $path)) {
    // The IntraVox directory links to source paths: expose assets only.
    if (str_starts_with($path, '/custom_apps/intravox/')
        && !preg_match('~^/custom_apps/intravox/(js|css|img|l10n)/~', $path)) {
        $deny();
    }
    if (is_file($root . $path)) {
        return false;
    }
    $deny();
}
if ($path === '/robots.txt') {
    return false;
}
if ($path !== '/' && preg_match('~^/(config|data|lib|3rdparty|vendor|node_modules|custom_apps|apps|core|dist|resources|themes)(/|$)|\.[a-z0-9]+$~i', $path)) {
    $deny();
}
// Pretty application routes still go through the authenticated front controller.
$_SERVER['SCRIPT_NAME'] = '/index.php';
$_SERVER['SCRIPT_FILENAME'] = $root . '/index.php';
$_SERVER['PHP_SELF'] = '/index.php' . ($path === '/' ? '' : $path);
$_SERVER['PATH_INFO'] = $path === '/' ? '' : $path;
chdir($root);
require $root . '/index.php';
return true;
