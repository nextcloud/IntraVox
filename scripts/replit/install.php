<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

$root = dirname(__DIR__, 2);
$nextcloud = $root . '/.runtime/nextcloud';
$CONFIG = [];
if (is_file($nextcloud . '/config/config.php')) {
    require $nextcloud . '/config/config.php';
}
if (($CONFIG['installed'] ?? false) === true) {
    echo "Nextcloud is already installed; preserving accounts and data.\n";
    exit(0);
}

$argv = [
    $nextcloud . '/occ', 'maintenance:install',
    '--database=sqlite',
    '--data-dir=' . $root . '/.runtime/data',
    '--admin-user=admin',
    '--admin-pass=' . bin2hex(random_bytes(32)),
    '--no-interaction',
];
$_SERVER['argv'] = $argv;
$_SERVER['argc'] = count($argv);
$_SERVER['SCRIPT_FILENAME'] = $nextcloud . '/occ';
$_SERVER['SCRIPT_NAME'] = $nextcloud . '/occ';
$_SERVER['PHP_SELF'] = $nextcloud . '/occ';
chdir($nextcloud);
require $nextcloud . '/console.php';
