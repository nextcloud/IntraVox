<?php
declare(strict_types=1);

/**
 * Fail before Nextcloud boots: no test may open an owner's database.
 * The runner creates both a unique directory and a per-process marker.
 */
function requireDisposableNextcloud(bool $installed = true): string {
    $workspace = dirname(__DIR__, 2);
    $sandbox = getenv('INTRAVOX_TEST_SANDBOX') ?: '';
    $root = getenv('NEXTCLOUD_ROOT') ?: '';
    $token = getenv('INTRAVOX_TEST_TOKEN') ?: '';
    if (!preg_match('#^' . preg_quote($workspace, '#') . '/\.runtime/integration-[A-Za-z0-9]+$#', $sandbox)
        || realpath($sandbox) !== $sandbox || is_link($sandbox)
        || $root !== $sandbox . '/nextcloud' || realpath($root) !== $root
        || strlen($token) !== 64 || !is_file($sandbox . '/.disposable')
        || !hash_equals($token, (string)file_get_contents($sandbox . '/.disposable'))) {
        throw new RuntimeException('Refusing non-disposable Nextcloud. Use scripts/run-integration-tests.sh.');
    }
    if ($installed) {
        $CONFIG = [];
        require $root . '/config/config.php';
        if (($CONFIG['installed'] ?? false) !== true || ($CONFIG['dbtype'] ?? '') !== 'sqlite3'
            || ($CONFIG['datadirectory'] ?? '') !== $sandbox . '/data'
            || realpath($sandbox . '/data') !== $sandbox . '/data') {
            throw new RuntimeException('Integration database must belong to the disposable sandbox.');
        }
    }
    return $root;
}
