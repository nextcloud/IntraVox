<?php
require_once '/var/www/nextcloud/lib/base.php';

$c = \OC::$server;
$userId = 'admin';

// Get services
$rootFolder = $c->get(\OCP\Files\IRootFolder::class);
$userSession = $c->get(\OCP\IUserSession::class);
$config = $c->get(\OCP\IConfig::class);
$db = $c->get(\OCP\IDBConnection::class);
$logger = $c->get(\Psr\Log\LoggerInterface::class);

// Set user
$userManager = $c->get(\OCP\IUserManager::class);
$user = $userManager->get($userId);
$userSession->setUser($user);

echo "Testing PageService...\n";
echo "User ID: " . $userId . "\n";

// Create services
$setupService = new \OCA\IntraVox\Service\SetupService($rootFolder, $userSession, $config, $db);
$pageService = new \OCA\IntraVox\Service\PageService($rootFolder, $userSession, $setupService, $config, $db, $logger);

try {
    echo "\nCalling listPages()...\n";
    $pages = $pageService->listPages();
    echo "Found " . count($pages) . " pages:\n";
    foreach ($pages as $page) {
        echo "  - " . $page['id'] . " | " . $page['title'] . " | " . ($page['uniqueId'] ?? 'NO UNIQUE ID') . "\n";
    }
} catch (\Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}
