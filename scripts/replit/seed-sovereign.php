<?php
declare(strict_types=1);

// Local administrative tool, never a web endpoint. Requires the managed runtime
// and an explicit --apply; the user's approved plan requires a private backup.
if (PHP_SAPI !== 'cli' || !in_array('--apply', $argv, true)) {
    fwrite(STDERR, "Usage: php scripts/replit/seed-sovereign.php --apply\n");
    exit(2);
}
$project = dirname(__DIR__, 2);
$runtime = $project . '/.runtime/nextcloud';
if (!is_file($runtime . '/custom_apps/intravox/.replit-managed')) {
    throw new RuntimeException('Refusing to operate outside the managed local sandbox');
}
$backupRoot = $project . '/.runtime/backups';
if (!is_dir($backupRoot) || glob($backupRoot . '/athena-baseline-*/content/home.json') === []) {
    throw new RuntimeException('Create a private Athena baseline backup before applying');
}
define('OC_CONSOLE', 1);
require $runtime . '/lib/base.php';
try {
    $service = \OCP\Server::get(\OCA\IntraVox\Service\SetupService::class);
    $finalUx = in_array('--final-ux', $argv, true);
    $changed = $service->installSovereignBaseline(true, $finalUx ? \OCA\IntraVox\Service\SovereignBaseline::FINAL_UX_PATHS : null);
    \OCP\Server::get(\OCA\IntraVox\Service\Cache\PageCacheService::class)->clearExpensive();
    \OCP\Server::get(\OCA\IntraVox\Service\PageIndexService::class)->clearLanguage('en');
    echo "Sovereign baseline: $changed documents changed. Existing unrelated content retained.\n";
    if ($finalUx) {
        echo "Final UX refresh updated only the managed home and three announcements; existing navigation, office pages, ACLs and branding retained.\n";
        return;
    }
    // Supported Nextcloud image API accepts the original PNG, not a fabricated SVG.
    $images = \OCP\Server::get(\OCA\Theming\ImageManager::class);
    $defaults = \OCP\Server::get(\OCA\Theming\ThemingDefaults::class);
    foreach (['logo', 'logoheader'] as $key) {
        $mime = $images->updateImage($key, $project . '/src/img/athena-brand/athena-group-horizontal-light.png');
        $defaults->set($key . 'Mime', $mime);
    }
    $defaults->set('primary_color', '#4B164C');
    $defaults->set('color', '#4B164C');
    $defaults->set('name', 'Athena Group — Sovereign 365');
    echo "Original white Athena PNG installed; IntraVox and theming caches refreshed.\n";
} finally {
    \OCP\Server::get(\OCP\Lock\ILockingProvider::class)->releaseAll();
}
