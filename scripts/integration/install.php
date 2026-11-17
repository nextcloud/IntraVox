<?php
declare(strict_types=1);

require __DIR__ . '/guard.php';
$nextcloud = requireDisposableNextcloud(false);
if (file_exists($nextcloud . '/config/config.php')) {
    throw new RuntimeException('Refusing to reuse an installed test dataset.');
}
$argv = [
    $nextcloud . '/occ', 'maintenance:install', '--database=sqlite',
    '--data-dir=' . getenv('INTRAVOX_TEST_SANDBOX') . '/data',
    '--admin-user=integration-admin', '--admin-pass=' . bin2hex(random_bytes(32)),
    '--no-interaction',
];
$_SERVER['argv'] = $argv;
$_SERVER['argc'] = count($argv);
$_SERVER['SCRIPT_FILENAME'] = $nextcloud . '/occ';
$_SERVER['SCRIPT_NAME'] = $_SERVER['PHP_SELF'] = $nextcloud . '/occ';
chdir($nextcloud);
require $nextcloud . '/console.php';
