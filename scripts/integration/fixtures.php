<?php
declare(strict_types=1);

require __DIR__ . '/guard.php';
$nextcloud = requireDisposableNextcloud();
require $nextcloud . '/lib/base.php';
\OC_App::loadApp('intravox');
$user = \OC::$server->get(\OCP\IUserManager::class)->get('integration-admin');
if ($user === null) {
    throw new RuntimeException('Missing disposable fixture administrator.');
}
\OC::$server->get(\OCP\IUserSession::class)->setUser($user);
\OC_Util::setupFS($user->getUID());
\OC::$server->get(\OCP\IConfig::class)->setUserValue($user->getUID(), 'core', 'lang', 'en');
$write = \OC::$server->get(\OCA\IntraVox\Service\Write\PageWriteService::class);
$write->createPage([
    'id' => 'release-fixture',
    'title' => 'Disposable release fixture',
    'layout' => ['rows' => [['widgets' => [
        ['type' => 'text', 'content' => '<p>Integration fixture</p>', 'column' => 1, 'order' => 1],
    ]]]],
], null);
// Valid page-shaped decoys must never appear in the normal page listing.
$folder = \OC::$server->get(\OCA\IntraVox\Service\Folder\FolderContext::class)->intraVox()->get('en');
foreach (['_templates', '_resources'] as $name) {
    $directory = $folder->nodeExists($name) ? $folder->get($name) : $folder->newFolder($name);
    $directory->newFile('excluded.json', json_encode([
        'id' => 'excluded', 'uniqueId' => 'excluded-' . $name, 'title' => 'Excluded fixture',
        'layout' => ['rows' => []],
    ], JSON_THROW_ON_ERROR));
}
echo "Disposable administrator, content page and excluded-directory fixtures ready.\n";
