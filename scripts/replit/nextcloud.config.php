<?php
// Copied into the ignored Nextcloud config directory by setup.sh.
declare(strict_types=1);

$nextcloud = dirname(__DIR__);
$domain = getenv('REPLIT_DEV_DOMAIN') ?: '';
$CONFIG = [
    'apps_paths' => [
        ['path' => $nextcloud . '/apps', 'url' => '/apps', 'writable' => false],
        ['path' => $nextcloud . '/custom_apps', 'url' => '/custom_apps', 'writable' => true],
    ],
    'trusted_domains' => array_values(array_filter([
        '127.0.0.1:5000', 'localhost:5000', $domain,
    ])),
    'overwrite.cli.url' => $domain !== '' ? 'https://' . $domain : 'http://localhost:5000',
    'htaccess.RewriteBase' => '/',
    'check_for_working_htaccess' => false,
];
// Replit terminates HTTPS before forwarding to PHP. Do not trust arbitrary
// forwarded headers, and keep loopback HTTP working for local health checks.
if ($domain !== '' && ($_SERVER['HTTP_HOST'] ?? '') === $domain) {
    $CONFIG['overwriteprotocol'] = 'https';
}
