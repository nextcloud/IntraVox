<?php
/**
 * Does IntraVox agree with Nextcloud about who may read what?
 *
 * Run inside the nc-dev container:
 *   docker exec -u www-data nc-dev php /var/www/html/custom_apps/intravox/scripts/acl-mapping-matrix.php
 *
 * WHAT IT DOES
 * Twice over, once for GROUPS and once for CIRCLES (Teams), it:
 *   1. grants the two applicable entities the maximum right on the team folder,
 *   2. sets conflicting ACL rules on en/documentation and en/news,
 *   3. asks Nextcloud Files, groupfolders' own ACLManager and IntraVox what the
 *      test user may do,
 *   4. removes everything it added.
 *
 * The grant-then-strip order is deliberate: groupfolders applies ACL rules ON
 * TOP of the folder-level permission (ACLManager::calculatePermissionsForPath
 * ends with applyPermissions(getBasePermission($folderId))). An entity that is
 * not on the folder has a base of 0, and no ACL rule can add to that. So the
 * documented model is: put the maximum on the folder, then narrow inside it.
 *
 * WHY IT EXISTS
 * IntraVox does not call groupfolders' ACL code; PermissionService rebuilds it
 * from raw SQL over group_folders_groups and group_folders_acl. This script is
 * the differential test for that reimplementation: any row where IntraVox
 * disagrees with Files is a defect in IntraVox, because Files is what the
 * administrator configured and what the user sees everywhere else.
 *
 * SAFE TO RE-RUN
 * It only ever adds rows it removes again, and it refuses to touch a folder
 * that already has ACL rules on the two test paths. Nothing pre-existing is
 * modified: the "restore" is a delete of exactly what was inserted.
 */
declare(strict_types=1);

require '/var/www/html/lib/base.php';

const FOLDER_ID   = 1;
const TEST_USER   = 'demo065';
const GROUPS      = ['G-A', 'G-B'];
const CIRCLE_NAME = ['GroupA', 'GroupB'];
const PATHS       = ['en/documentation', 'en/news'];

$db      = \OC::$server->get(\OCP\IDBConnection::class);
$root    = \OC::$server->get(\OCP\Files\IRootFolder::class);
$userMgr = \OC::$server->get(\OCP\IUserManager::class);
$user    = $userMgr->get(TEST_USER);

if ($user === null) {
    exit("Test user " . TEST_USER . " does not exist.\n");
}

/** Numeric storage id of the group folder, needed by ACLManager. */
$storageId = $root->get('/__groupfolders/' . FOLDER_ID . '/files')
    ->getStorage()->getCache()->getNumericStorageId();

/** fileid per test path. */
$fileIds = [];
foreach (PATHS as $p) {
    $fileIds[$p] = $root->get('/__groupfolders/' . FOLDER_ID . '/files/' . $p)->getId();
}

/** Circle ids, looked up by display name so the script is not tied to hashes. */
$circleIds = [];
$q = $db->getQueryBuilder();
$q->select('unique_id', 'display_name')->from('circles_circle')
  ->where($q->expr()->in('display_name', $q->createNamedParameter(CIRCLE_NAME, \OCP\DB\QueryBuilder\IQueryBuilder::PARAM_STR_ARRAY)));
foreach ($q->executeQuery()->fetchAll() as $r) {
    $circleIds[$r['display_name']] = $r['unique_id'];
}

/**
 * Refuse to run when the paths already carry ACL rules.
 *
 * The cleanup deletes what this script inserted; it does not restore rules it
 * found. Bailing out is the only honest option — a half-restored ACL is worse
 * than no test.
 */
$q = $db->getQueryBuilder();
$q->select($q->func()->count('*', 'n'))->from('group_folders_acl')
  ->where($q->expr()->in('fileid', $q->createNamedParameter(array_values($fileIds), \OCP\DB\QueryBuilder\IQueryBuilder::PARAM_INT_ARRAY)));
if ((int)$q->executeQuery()->fetchOne() > 0) {
    exit("REFUSING TO RUN: the test paths already have ACL rules.\n"
       . "Remove them first, or this script would delete configuration it did not create.\n");
}

// --------------------------------------------------------------- helpers

function grantOnFolder(\OCP\IDBConnection $db, string $type, string $id, int $perms): void {
    $q = $db->getQueryBuilder();
    $q->insert('group_folders_groups')->values([
        'folder_id'   => $q->createNamedParameter(FOLDER_ID),
        'group_id'    => $q->createNamedParameter($type === 'group' ? $id : ''),
        'circle_id'   => $q->createNamedParameter($type === 'circle' ? $id : ''),
        'permissions' => $q->createNamedParameter($perms),
    ]);
    $q->executeStatement();
}

function revokeOnFolder(\OCP\IDBConnection $db, string $type, string $id): void {
    $q = $db->getQueryBuilder();
    $col = $type === 'group' ? 'group_id' : 'circle_id';
    $q->delete('group_folders_groups')
      ->where($q->expr()->eq('folder_id', $q->createNamedParameter(FOLDER_ID)))
      ->andWhere($q->expr()->eq($col, $q->createNamedParameter($id)));
    $q->executeStatement();
}

function setAcl(\OCP\IDBConnection $db, int $fileId, string $type, string $id, int $mask, int $perms): void {
    $q = $db->getQueryBuilder();
    $q->insert('group_folders_acl')->values([
        'fileid'       => $q->createNamedParameter($fileId),
        'mapping_type' => $q->createNamedParameter($type),
        'mapping_id'   => $q->createNamedParameter($id),
        'mask'         => $q->createNamedParameter($mask),
        'permissions'  => $q->createNamedParameter($perms),
    ]);
    $q->executeStatement();
}

function clearAcl(\OCP\IDBConnection $db, array $fileIds): void {
    $q = $db->getQueryBuilder();
    $q->delete('group_folders_acl')
      ->where($q->expr()->in('fileid', $q->createNamedParameter($fileIds, \OCP\DB\QueryBuilder\IQueryBuilder::PARAM_INT_ARRAY)));
    $q->executeStatement();
}

/** Ask all three sources what the user may do on one path. */
function measure(string $path, int $storageId, \OCP\IUser $user): array {
    // Caches are per-request in IntraVox and per-object in groupfolders, so
    // both are rebuilt here — otherwise the second scenario reports the first.
    \OC_Util::tearDownFS();
    \OC_Util::setupFS(TEST_USER);

    $root = \OC::$server->get(\OCP\Files\IRootFolder::class);
    $uf   = $root->getUserFolder(TEST_USER);
    $files = $uf->nodeExists('IntraVox/' . $path) ? $uf->get('IntraVox/' . $path)->getPermissions() : 0;

    $acl = \OC::$server->get(\OCA\GroupFolders\ACL\ACLManagerFactory::class)->getACLManager($user);
    $aclPerm = $acl->getACLPermissionsForPath(FOLDER_ID, $storageId, '__groupfolders/' . FOLDER_ID . '/files/' . $path);

    // What groupfolders' own folder-level API says. This is the candidate
    // replacement for IntraVox's hand-written group loop: it unions groups AND
    // circle memberships, which the loop cannot.
    $fm = \OC::$server->get(\OCA\GroupFolders\Folder\FolderManager::class);
    $base = $fm->getFolderPermissionsForUser($user, FOLDER_ID);

    // Resolved from the container, not constructed by hand: this must exercise
    // the same wiring production uses. A fresh instance per measurement because
    // getPermissions() memoises per (user, path) for the life of the object.
    $ps = \OC::$server->get(\OCA\IntraVox\Service\PermissionService::class);
    $reset = new \ReflectionProperty(\OCA\IntraVox\Service\PermissionService::class, 'permissionResultCache');
    $reset->setAccessible(true);
    $reset->setValue($ps, []);
    $iv = $ps->getPermissions($path, TEST_USER);

    return ['files' => $files, 'acl' => $aclPerm, 'base' => $base, 'intravox' => $iv];
}

function readable(int $p): string { return ($p & 1) ? 'r' : '-'; }
function writable(int $p): string { return ($p & 2) ? 'w' : '-'; }

// --------------------------------------------------------------- scenarios

$failures = 0;

/**
 * One scenario: grant on the folder, set conflicting ACLs, measure, clean up.
 *
 * The ACL shape mirrors GitHub issue #116: on documentation the first entity
 * may write and the second may not; on news the other way round. A user in
 * both should, per the administrator's intent, be able to write in both.
 */
function scenario(string $label, string $type, array $ids, \OCP\IDBConnection $db, array $fileIds, int $storageId, \OCP\IUser $user): int {
    echo "\n=== {$label} ===\n";

    if (count($ids) !== 2) {
        echo "  SKIPPED: expected two {$type}s, found " . count($ids) . "\n";
        return 0;
    }

    [$first, $second] = array_values($ids);

    // 1. Maximum on the folder — ACL rules narrow from here, they cannot widen.
    foreach ([$first, $second] as $id) {
        grantOnFolder($db, $type, $id, 31);
    }

    // 2. Conflicting rules per path (mask read+write, so both bits are governed).
    setAcl($db, $fileIds['en/documentation'], $type, $first,  3, 3); // may write
    setAcl($db, $fileIds['en/documentation'], $type, $second, 3, 1); // read only
    setAcl($db, $fileIds['en/news'],          $type, $second, 3, 3);
    setAcl($db, $fileIds['en/news'],          $type, $first,  3, 1);

    $bad = 0;
    printf("  %-18s %-12s %-12s %-12s %-12s %s\n", 'path', 'Files', 'ACLManager', 'FolderMgr', 'IntraVox', 'verdict');
    foreach (PATHS as $path) {
        $m = measure($path, $storageId, $user);
        $agrees = $m['files'] === $m['intravox'];
        if (!$agrees) { $bad++; }
        printf("  %-18s %2d (%s%s)      %2d (%s%s)      %2d (%s%s)      %2d (%s%s)      %s\n",
            $path,
            $m['files'], readable($m['files']), writable($m['files']),
            $m['acl'], readable($m['acl']), writable($m['acl']),
            $m['base'], readable($m['base']), writable($m['base']),
            $m['intravox'], readable($m['intravox']), writable($m['intravox']),
            $agrees ? 'agree' : 'DISAGREE'
        );
    }

    // 3. Remove exactly what was added.
    clearAcl($db, array_values($fileIds));
    foreach ([$first, $second] as $id) {
        revokeOnFolder($db, $type, $id);
    }
    echo "  cleaned up\n";

    return $bad;
}

/**
 * The shape the earlier scenarios MISS: conflicting rules at different DEPTHS.
 *
 * Scenarios 1 and 2 put both rules on the same file, which is the case 3.1.0
 * fixed — and which passed while issue #116 was still open, because an
 * administrator does not set two rules on one folder. They set a right on a
 * department folder and another on the space above it. There the per-path
 * results used to be folded onto each other in path order, so the deeper rule
 * decided the answer and the other group was ignored.
 *
 * Note what "agree" means here: Files is still the truth. Whether the allow
 * survives depends on the instance's `acl-inherit-per-user` setting, so this
 * does not assert a fixed number — it asserts that IntraVox lands on whatever
 * Files lands on, under whichever setting this instance has.
 */
function depthScenario(string $label, string $type, array $ids, \OCP\IDBConnection $db, array $fileIds, int $storageId, \OCP\IUser $user): int {
    echo "\n=== {$label} ===\n";

    if (count($ids) !== 2) {
        echo "  SKIPPED: expected two {$type}s, found " . count($ids) . "\n";
        return 0;
    }

    [$first, $second] = array_values($ids);
    $inherit = \OC::$server->get(\OCP\IAppConfig::class)
        ->getValueString('groupfolders', 'acl-inherit-per-user', 'false');
    echo "  groupfolders acl-inherit-per-user = {$inherit}\n";

    foreach ([$first, $second] as $id) {
        grantOnFolder($db, $type, $id, 31);
    }

    // Parent (en) denies write to the SECOND entity; the child (en/documentation)
    // allows it for the FIRST. A user in both should keep the child's allow when
    // the instance merges per user.
    $parentId = \OC::$server->get(\OCP\Files\IRootFolder::class)
        ->get('/__groupfolders/' . FOLDER_ID . '/files/en')->getId();

    setAcl($db, $parentId,                      $type, $second, 3, 1); // deny write high
    setAcl($db, $fileIds['en/documentation'],   $type, $first,  3, 3); // allow write low

    $bad = 0;
    printf("  %-18s %-12s %-12s %-12s %-12s %s\n", 'path', 'Files', 'ACLManager', 'FolderMgr', 'IntraVox', 'verdict');
    foreach (PATHS as $path) {
        $m = measure($path, $storageId, $user);
        $agrees = $m['files'] === $m['intravox'];
        if (!$agrees) { $bad++; }
        printf("  %-18s %2d (%s%s)      %2d (%s%s)      %2d (%s%s)      %2d (%s%s)      %s\n",
            $path,
            $m['files'], readable($m['files']), writable($m['files']),
            $m['acl'], readable($m['acl']), writable($m['acl']),
            $m['base'], readable($m['base']), writable($m['base']),
            $m['intravox'], readable($m['intravox']), writable($m['intravox']),
            $agrees ? 'agree' : 'DISAGREE'
        );
    }

    clearAcl($db, array_merge(array_values($fileIds), [$parentId]));
    foreach ([$first, $second] as $id) {
        revokeOnFolder($db, $type, $id);
    }
    echo "  cleaned up\n";

    return $bad;
}

echo "IntraVox vs Nextcloud: permission mapping types\n";
echo "user=" . TEST_USER . "  folder=" . FOLDER_ID . "  storage={$storageId}\n";

$failures += scenario('SCENARIO 1 — groups (G-A, G-B)', 'group', GROUPS, $db, $fileIds, $storageId, $user);
$failures += scenario('SCENARIO 2 — circles (GroupA, GroupB)', 'circle', $circleIds, $db, $fileIds, $storageId, $user);
$failures += depthScenario('SCENARIO 3 — groups, conflicting at different DEPTHS (issue #116)', 'group', GROUPS, $db, $fileIds, $storageId, $user);
$failures += depthScenario('SCENARIO 4 — circles, conflicting at different DEPTHS', 'circle', $circleIds, $db, $fileIds, $storageId, $user);

echo "\n" . str_repeat('-', 64) . "\n";
if ($failures === 0) {
    echo "PASS: IntraVox agrees with Nextcloud on every path.\n";
} else {
    echo "FAIL: {$failures} path(s) where IntraVox disagrees with what the user\n";
    echo "      actually has in Files. Files is the configured truth.\n";
}
exit($failures === 0 ? 0 : 1);
