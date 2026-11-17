<?php
declare(strict_types=1);

// Copied into the disposable install, never the owner's config directory.
$root = dirname(__DIR__);
$CONFIG = [
    'apps_paths' => [
        ['path' => $root . '/apps', 'url' => '/apps', 'writable' => false],
        ['path' => $root . '/custom_apps', 'url' => '/custom_apps', 'writable' => false],
    ],
    'appstoreenabled' => false,
    'updatechecker' => false,
    'has_internet_connection' => false,
];
