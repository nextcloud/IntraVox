<?php
declare(strict_types=1);

namespace OCA\IntraVox\Service;

use OCP\Files\IRootFolder;
use OCP\Files\Node;
use OCP\ICache;
use OCP\ICacheFactory;
use OCP\IUserSession;
use OCP\IGroupManager;
use OCP\IConfig;
use OCP\IUserManager;
use OCP\IDBConnection;
use OCA\IntraVox\Service\GroupFolders\GroupFoldersGateway;
use OCP\App\IAppManager;
use OCP\Constants;
use Psr\Log\LoggerInterface;
use OCA\IntraVox\Service\Path\PagePathHelper;

/**
 * PermissionService handles authorization based on GroupFolder ACL permissions.
 *
 * This service ensures that IntraVox respects the same permission model as the underlying
 * GroupFolder, so users only see and can interact with content they have access to.
 */
class PermissionService {
    // Permission bit flags (matching Nextcloud constants)
    public const PERMISSION_READ = 1;
    public const PERMISSION_UPDATE = 2;
    public const PERMISSION_CREATE = 4;
    public const PERMISSION_DELETE = 8;
    public const PERMISSION_SHARE = 16;
    public const PERMISSION_ALL = 31;

    private IRootFolder $rootFolder;
    private IUserSession $userSession;
    private IGroupManager $groupManager;
    private SetupService $setupService;
    private IConfig $config;
    private LoggerInterface $logger;
    private IUserManager $userManager;
    private IDBConnection $db;
    private IAppManager $appManager;
    private ?ICache $distributedCache = null;
    private ?string $userId;

    /**
     * Per-request cache of raw node permission bitmasks, keyed by node path.
     * One cache, one owner: PageService used to keep its own copy of this,
     * which meant two permission cache layers that had to be invalidated in
     * step. PageService::clearCache() calls
     * {@see clearNodePermissionsCache()} instead.
     */
    private array $nodePermissionsCache = [];

    /**
     * The assembled permission array per node path.
     *
     * getNodePermissions() already memoises the bitmask, but permissionsFromNode()
     * ANDs three capability calls on top of it — isUpdateable/isCreatable/
     * isDeletable — and those ran on every call. The page tree resolves
     * permissions per node on every request (ACLs differ WITHIN a group, so the
     * cached tree cannot carry them), so any path asked for twice in a request
     * paid for all three again.
     */
    private array $permissionArrayCache = [];

    /**
     * Per-request memo of the groupfolder id, keyed by mount point name.
     * Uses array_key_exists, not isset: a resolved-to-null answer must be
     * cached too, otherwise a broken install re-walks every groupfolder on
     * the instance for every permission check.
     *
     * @var array<string, int|null>
     */
    private array $groupFolderIdCache = [];
    private GroupFoldersGateway $groupFolders;

    /** Distributed cache TTL for the per-language page path map (5 minutes). */
    private const PAGE_PATH_MAP_TTL = 300;

    public function __construct(
        IRootFolder $rootFolder,
        IUserSession $userSession,
        IGroupManager $groupManager,
        SetupService $setupService,
        IConfig $config,
        LoggerInterface $logger,
        ICacheFactory $cacheFactory,
        IUserManager $userManager,
        IDBConnection $db,
        IAppManager $appManager,
        ?string $userId,
        ?GroupFoldersGateway $groupFolders = null
    ) {
        $this->rootFolder = $rootFolder;
        $this->userSession = $userSession;
        $this->groupManager = $groupManager;
        $this->setupService = $setupService;
        $this->config = $config;
        $this->logger = $logger;
        $this->userManager = $userManager;
        $this->db = $db;
        $this->appManager = $appManager;
        $this->userId = $userId;
        // Optional: 11 test files build this service without a container.
        $this->groupFolders = $groupFolders ?? new GroupFoldersGateway($appManager, $logger);

        if ($cacheFactory->isAvailable()) {
            $this->distributedCache = $cacheFactory->createDistributed('intravox-permissions');
        }
    }

    /**
     * Get the GroupFolder ID for IntraVox (or IntraVox Site)
     */
    private function getGroupFolderId(string $folderName = 'IntraVox'): ?int {
        if (array_key_exists($folderName, $this->groupFolderIdCache)) {
            return $this->groupFolderIdCache[$folderName];
        }

        $folderId = $this->resolveGroupFolderId($folderName);
        $this->groupFolderIdCache[$folderName] = $folderId;

        return $folderId;
    }

    /**
     * Resolve the groupfolder id by mount point name.
     *
     * getAllFolders() is three unbounded queries plus one object construction
     * per row, so this walks every groupfolder on the instance to find the one
     * we want. That is why {@see getGroupFolderId()} memoises the answer for
     * the request: this used to run on every single permission check, which on
     * an instance with thousands of team folders meant walking all of them to
     * render one page.
     *
     * Matching on the mount point name is also why renaming the IntraVox
     * groupfolder breaks resolution. Keying on folder_id instead is the
     * multi-site registry's job; until that exists this stays name-based, but
     * it is now called once per request rather than once per check.
     */
    protected function resolveGroupFolderId(string $folderName): ?int {
        if (!$this->groupFolders->isAvailable()) {
            $this->logger->warning(
                'IntraVox permission check with the groupfolders app disabled; denying access. '
                . 'IntraVox stores all content in a groupfolder, so this is a broken installation, '
                . 'not an unconfigured one.'
            );
            return null;
        }

        // One chokepoint for the walk (SE-1). This used to be a fourth copy of
        // the same loop, with its own inline mount-point extraction.
        $folderId = $this->groupFolders->findFolderIdByMountPoint($folderName);

        if ($folderId === null) {
            $this->logger->warning(
                'No groupfolder named "' . $folderName . '" found; denying access. '
                . 'Either IntraVox has not been set up yet, or the groupfolder was renamed '
                . '(resolution is by mount point name).'
            );
        }

        return $folderId;
    }

    /**
     * Get effective permissions for a user on a specific path within the GroupFolder.
     *
     * @param string $relativePath Path relative to the IntraVox folder (e.g., "nl/afdeling/sales")
     * @param string|null $userId User ID (defaults to current user)
     * @return int Permission bitmask
     */
    public function getPermissions(string $relativePath, ?string $userId = null): int {
        $userId = $userId ?? $this->userId;

        $this->logger->info("[PermissionService] getPermissions called for path: '{$relativePath}', user: " . ($userId ?? 'null'));

        if (!$userId) {
            $this->logger->debug('No user ID, returning no permissions');
            return 0;
        }

        try {
            $perms = $this->calculatePermissions($relativePath, $userId);
            $this->logger->info("[PermissionService] Final permissions for '{$relativePath}': {$perms}");
            return $perms;
        } catch (\Exception $e) {
            $this->logger->error('Failed to get permissions for path ' . $relativePath . ': ' . $e->getMessage());
            return 0;
        }
    }

    /**
     * Calculate effective permissions for a user on a path.
     *
     * The permission calculation follows this logic:
     * 1. Get base permissions from GroupFolder group membership
     * 2. Apply ACL rules (if ACL app is enabled)
     * 3. Child paths cannot have more permissions than parent paths
     */
    private function calculatePermissions(string $relativePath, string $userId): int {
        $folderId = $this->getGroupFolderId();
        if ($folderId === null) {
            // Fail CLOSED. This used to return PERMISSION_ALL "if groupfolders
            // is not set up", but every IntraVox page lives inside a
            // groupfolder — setup refuses to run without one. So there is no
            // legitimate state in which we cannot find the folder and should
            // still hand out full rights; the reachable causes are a disabled
            // groupfolders app, a renamed groupfolder, or setup never having
            // run. Granting write access on any of those is a hole, and one
            // that would become impossible to bisect once pages can live in
            // more than one site. getGroupFolderId() logs which cause it was.
            return 0;
        }

        try {
            // Get user's groups
            $user = $this->userManager->get($userId);
            if (!$user) {
                return 0;
            }

            $userGroups = $this->groupManager->getUserGroupIds($user);

            // Get folder configuration
            // getFolder() takes one argument. The extra storage id we used to
            // pass was silently discarded (PHP ignores excess args on userland
            // methods), so this is not a behaviour change — but it was a call
            // that only looked correct, and any groupfolders release that adds
            // a second parameter would have started feeding it our storage id.
            $folderData = $this->groupFolders->getFolder($folderId);

            // Calculate base permissions from group membership
            $basePermissions = 0;

            // Get groups that have access to this groupfolder
            $applicableGroups = [];
            if (is_object($folderData)) {
                if (method_exists($folderData, 'getGroups')) {
                    $applicableGroups = $folderData->getGroups();
                } elseif (property_exists($folderData, 'groups')) {
                    $applicableGroups = $folderData->groups;
                }
            } elseif (is_array($folderData) && isset($folderData['groups'])) {
                $applicableGroups = $folderData['groups'];
            }

            // Check each group the user belongs to
            foreach ($userGroups as $groupId) {
                if (isset($applicableGroups[$groupId])) {
                    $groupPerms = $applicableGroups[$groupId];
                    // Handle both array and object formats
                    $permissions = is_array($groupPerms) ? ($groupPerms['permissions'] ?? 0) :
                                  (is_object($groupPerms) && property_exists($groupPerms, 'permissions') ? $groupPerms->permissions : 0);
                    $basePermissions |= $permissions;
                }
            }

            // If no base permissions from groups, user has no access
            if ($basePermissions === 0) {
                $this->logger->debug("User {$userId} has no group access to IntraVox folder");
                return 0;
            }

            // Now check ACL rules if the ACL system is available
            $permissions = $this->applyAclRules($folderId, $relativePath, $userId, $userGroups, $basePermissions);

            $this->logger->debug("Calculated permissions for {$userId} on {$relativePath}: {$permissions}");
            return $permissions;

        } catch (\Exception $e) {
            $this->logger->error('Error calculating permissions: ' . $e->getMessage());
            // In case of error, be restrictive
            return self::PERMISSION_READ;
        }
    }

    /**
     * Apply ACL rules from the GroupFolders ACL system.
     *
     * This method directly queries the ACL database to get the correct permissions,
     * as the __groupfolders storage does not apply ACL rules to getPermissions().
     */
    private function applyAclRules(int $folderId, string $relativePath, string $userId, array $userGroups, int $basePermissions): int {
        try {
            // Build path segments to check (from least specific to most specific)
            // ACL rules are stored with paths like "files/en/departments/hr"
            // We check from root to specific so that more specific rules override parent rules
            $pathsToCheck = ['files']; // Start with root

            if (!empty($relativePath)) {
                // Clean up the path
                $cleanPath = trim($relativePath, '/');

                // Build all parent paths to check (least specific to most specific)
                $parts = explode('/', $cleanPath);
                $currentPath = '';
                foreach ($parts as $part) {
                    $currentPath = $currentPath ? $currentPath . '/' . $part : $part;
                    // ACL paths in database are prefixed with "files/"
                    $pathsToCheck[] = 'files/' . $currentPath;
                }
            }

            $this->logger->debug("Checking ACL for paths: " . implode(', ', $pathsToCheck));

            // Query ACL rules directly from database
            $db = $this->db;

            // Get storage ID for the groupfolder
            // Try both storage ID formats: object::groupfolder:: (newer) and local:: (older)
            $storageQuery = $db->getQueryBuilder();
            $storageQuery->select('numeric_id')
                ->from('storages')
                ->where($storageQuery->expr()->orX(
                    $storageQuery->expr()->like('id', $storageQuery->createNamedParameter('object::groupfolder::' . $folderId)),
                    $storageQuery->expr()->like('id', $storageQuery->createNamedParameter('%__groupfolders/' . $folderId . '/%'))
                ));
            $storageResult = $storageQuery->executeQuery();
            $storageRow = $storageResult->fetch();
            $storageResult->closeCursor();

            if (!$storageRow) {
                $this->logger->debug("No storage found for groupfolder {$folderId}, using base permissions");
                return $basePermissions;
            }

            $storageId = $storageRow['numeric_id'];
            $this->logger->debug("Found storage ID {$storageId} for groupfolder {$folderId}");

            // For each path (most specific to least specific), check for ACL rules
            $effectivePermissions = $basePermissions;

            foreach ($pathsToCheck as $aclPath) {
                // Get fileid for this path
                $fileQuery = $db->getQueryBuilder();
                $fileQuery->select('fileid')
                    ->from('filecache')
                    ->where($fileQuery->expr()->eq('storage', $fileQuery->createNamedParameter($storageId)))
                    ->andWhere($fileQuery->expr()->eq('path', $fileQuery->createNamedParameter($aclPath)));
                $fileResult = $fileQuery->executeQuery();
                $fileRow = $fileResult->fetch();
                $fileResult->closeCursor();

                if (!$fileRow) {
                    $this->logger->debug("No filecache entry for path {$aclPath}");
                    continue;
                }

                $fileId = $fileRow['fileid'];

                // Check ACL rules for this file
                // First check group rules (user belongs to these groups)
                foreach ($userGroups as $groupId) {
                    $aclQuery = $db->getQueryBuilder();
                    $aclQuery->select('mask', 'permissions')
                        ->from('group_folders_acl')
                        ->where($aclQuery->expr()->eq('fileid', $aclQuery->createNamedParameter($fileId)))
                        ->andWhere($aclQuery->expr()->eq('mapping_type', $aclQuery->createNamedParameter('group')))
                        ->andWhere($aclQuery->expr()->eq('mapping_id', $aclQuery->createNamedParameter($groupId)));
                    $aclResult = $aclQuery->executeQuery();
                    $aclRow = $aclResult->fetch();
                    $aclResult->closeCursor();

                    if ($aclRow) {
                        $mask = (int)$aclRow['mask'];
                        $permissions = (int)$aclRow['permissions'];

                        $this->logger->debug("Found ACL rule for group {$groupId} on path {$aclPath}: mask={$mask}, permissions={$permissions}");

                        // Apply the ACL rule: permissions in the ACL override base permissions for the masked bits
                        // mask indicates which permission bits are controlled by this ACL rule
                        // permissions indicates the actual permission values
                        // Clear the masked bits from effective permissions, then OR in the ACL permissions
                        $effectivePermissions = ($effectivePermissions & ~$mask) | ($permissions & $mask);

                        $this->logger->debug("After applying ACL: effectivePermissions={$effectivePermissions}");
                    }
                }

                // Also check user-specific rules
                $userAclQuery = $db->getQueryBuilder();
                $userAclQuery->select('mask', 'permissions')
                    ->from('group_folders_acl')
                    ->where($userAclQuery->expr()->eq('fileid', $userAclQuery->createNamedParameter($fileId)))
                    ->andWhere($userAclQuery->expr()->eq('mapping_type', $userAclQuery->createNamedParameter('user')))
                    ->andWhere($userAclQuery->expr()->eq('mapping_id', $userAclQuery->createNamedParameter($userId)));
                $userAclResult = $userAclQuery->executeQuery();
                $userAclRow = $userAclResult->fetch();
                $userAclResult->closeCursor();

                if ($userAclRow) {
                    $mask = (int)$userAclRow['mask'];
                    $permissions = (int)$userAclRow['permissions'];

                    $this->logger->debug("Found user ACL rule for {$userId} on path {$aclPath}: mask={$mask}, permissions={$permissions}");

                    // User rules override group rules
                    $effectivePermissions = ($effectivePermissions & ~$mask) | ($permissions & $mask);

                    $this->logger->debug("After applying user ACL: effectivePermissions={$effectivePermissions}");
                }
            }

            $this->logger->debug("Final permissions for {$userId} on {$relativePath}: {$effectivePermissions}");
            return $effectivePermissions;

        } catch (\Exception $e) {
            $this->logger->error('ACL check failed: ' . $e->getMessage());
            $this->logger->error('Stack trace: ' . $e->getTraceAsString());
            // In case of error, be restrictive - return read-only
            return self::PERMISSION_READ;
        }
    }

    /**
     * Check if user can read the given path.
     */
    public function canRead(string $relativePath, ?string $userId = null): bool {
        return ($this->getPermissions($relativePath, $userId) & self::PERMISSION_READ) !== 0;
    }

    /**
     * Check if user can update/write to the given path.
     */
    public function canWrite(string $relativePath, ?string $userId = null): bool {
        return ($this->getPermissions($relativePath, $userId) & self::PERMISSION_UPDATE) !== 0;
    }

    /**
     * Check if user can create new content in the given path.
     */
    public function canCreate(string $relativePath, ?string $userId = null): bool {
        return ($this->getPermissions($relativePath, $userId) & self::PERMISSION_CREATE) !== 0;
    }

    /**
     * Check if user can delete content at the given path.
     */
    public function canDelete(string $relativePath, ?string $userId = null): bool {
        return ($this->getPermissions($relativePath, $userId) & self::PERMISSION_DELETE) !== 0;
    }

    /**
     * Check if user can share content at the given path.
     */
    public function canShare(string $relativePath, ?string $userId = null): bool {
        return ($this->getPermissions($relativePath, $userId) & self::PERMISSION_SHARE) !== 0;
    }

    /**
     * Check if user has any access to the IntraVox folder.
     */
    public function hasAccess(?string $userId = null): bool {
        return $this->getPermissions('', $userId) > 0;
    }

    /**
     * Check if user is an IntraVox admin (full permissions on root).
     */
    public function isAdmin(?string $userId = null): bool {
        $permissions = $this->getPermissions('', $userId);
        // Admin has at least read, write, create, and delete
        return ($permissions & (self::PERMISSION_READ | self::PERMISSION_UPDATE | self::PERMISSION_CREATE | self::PERMISSION_DELETE)) ===
               (self::PERMISSION_READ | self::PERMISSION_UPDATE | self::PERMISSION_CREATE | self::PERMISSION_DELETE);
    }

    /**
     * Check if user is a Nextcloud system administrator.
     *
     * This checks the admin group membership, not the IntraVox folder permissions.
     * Use this for operations that require system-level access like settings,
     * bulk operations, and import/export.
     */
    public function isSystemAdmin(?string $userId = null): bool {
        $userId = $userId ?? $this->userId;
        if (!$userId) {
            return false;
        }

        $user = $this->userManager->get($userId);
        if (!$user) {
            return false;
        }

        return $this->groupManager->isAdmin($userId);
    }

    /**
     * Check if user can manage IntraVox settings.
     * Requires system admin privileges.
     */
    public function canManageSettings(?string $userId = null): bool {
        return $this->isSystemAdmin($userId);
    }

    /**
     * Check if user can perform bulk operations.
     * Requires system admin privileges.
     */
    public function canBulkDelete(?string $userId = null): bool {
        return $this->isSystemAdmin($userId);
    }

    /**
     * Check if user can perform bulk move operations.
     * Requires system admin privileges.
     */
    public function canBulkMove(?string $userId = null): bool {
        return $this->isSystemAdmin($userId);
    }

    /**
     * Check if user can perform bulk update operations.
     * Requires system admin privileges.
     */
    public function canBulkUpdate(?string $userId = null): bool {
        return $this->isSystemAdmin($userId);
    }

    /**
     * Check if user can import content.
     * Requires system admin privileges.
     */
    public function canImport(?string $userId = null): bool {
        return $this->isSystemAdmin($userId);
    }

    /**
     * Check if user can export all content.
     * Requires system admin privileges for full export.
     */
    public function canExport(?string $userId = null): bool {
        return $this->isSystemAdmin($userId);
    }

    /**
     * Check if user can view analytics dashboard.
     * Requires system admin privileges.
     */
    public function canViewAnalyticsDashboard(?string $userId = null): bool {
        return $this->isSystemAdmin($userId);
    }

    /**
     * Check if user can modify analytics settings.
     * Requires system admin privileges.
     */
    public function canManageAnalytics(?string $userId = null): bool {
        return $this->isSystemAdmin($userId);
    }

    /**
     * Get a permissions object for API responses.
     *
     * @param string $relativePath Path to get permissions for
     * @return array Permissions object with boolean flags
     */
    public function getPermissionsObject(string $relativePath, ?string $userId = null): array {
        $perms = $this->getPermissions($relativePath, $userId);

        return [
            'canRead' => ($perms & self::PERMISSION_READ) !== 0,
            'canWrite' => ($perms & self::PERMISSION_UPDATE) !== 0,
            'canCreate' => ($perms & self::PERMISSION_CREATE) !== 0,
            'canDelete' => ($perms & self::PERMISSION_DELETE) !== 0,
            'canShare' => ($perms & self::PERMISSION_SHARE) !== 0,
            'isAdmin' => $this->isAdmin($userId),
            'raw' => $perms
        ];
    }

    /**
     * Raw permission bitmask for a node, cached per request by node path.
     */
    public function getNodePermissions(Node $node): int {
        $path = $node->getPath();
        if (!isset($this->nodePermissionsCache[$path])) {
            $this->nodePermissionsCache[$path] = $node->getPermissions();
        }
        return $this->nodePermissionsCache[$path];
    }

    /**
     * Build the canRead/canWrite/canCreate/canDelete/canShare permission object
     * for a node from Nextcloud's filesystem view — the single source of truth
     * used by getPage(), getFolderPermissions(), the page tree and listings.
     *
     * canWrite/canCreate/canDelete AND the raw permission bit with the node's
     * capability method. For a read-only GroupFolder / Team Folder member WITHOUT
     * Advanced Permissions (ACLs), getPermissions() can still report UPDATE/CREATE
     * because the group read-only toggle is enforced on the mount mask, not always
     * reflected per child node (issue #70). isUpdateable()/isCreatable()/
     * isDeletable() DO account for mount writability and are already trusted
     * elsewhere (canEdit, template creation, NavigationService/FooterService
     * canEdit). Under ACLs the bitmask is already correct and these methods reflect
     * it, so AND-ing can only ever REMOVE a wrongly-granted capability, never grant
     * one — it never turns a genuinely writable folder read-only. `raw` is kept
     * un-AND-ed for API back-compat.
     */
    public function permissionsFromNode(Node $node): array {
        $path = $node->getPath();
        if (isset($this->permissionArrayCache[$path])) {
            return $this->permissionArrayCache[$path];
        }

        $perms = $this->getNodePermissions($node);
        return $this->permissionArrayCache[$path] = [
            'canRead' => ($perms & 1) !== 0,
            'canWrite' => ($perms & 2) !== 0 && $node->isUpdateable(),
            'canCreate' => ($perms & 4) !== 0 && $node->isCreatable(),
            'canDelete' => ($perms & 8) !== 0 && $node->isDeletable(),
            'canShare' => ($perms & 16) !== 0,
            'raw' => $perms,
        ];
    }

    /**
     * Permissions for a single page, where "write" is gated on the page FILE and
     * the remaining capabilities describe operations on the page FOLDER.
     *
     * Why the split: editing a page writes its JSON file (updatePage preflights
     * $file->isUpdateable() and then putContent()s the file). In a read-only
     * Team Folder without ACLs the FOLDER can report isUpdateable()=true while
     * the FILE reports false, so a folder-derived canWrite showed an "Edit page"
     * button that then 403'd on save (issue #70). canCreate/canDelete stay
     * folder-derived: creating a child or removing the page are folder-level
     * operations, consistent with the tree/listing builders.
     */
    public function permissionsForPage(Node $folder, Node $file): array {
        $perms = $this->permissionsFromNode($folder);
        $filePerms = $this->getNodePermissions($file);
        $perms['canWrite'] = ($filePerms & 2) !== 0 && $file->isUpdateable();
        return $perms;
    }

    /**
     * Drop the per-request node permission cache. Called by
     * PageService::clearCache() whenever the filesystem view mutates.
     */
    public function clearNodePermissionsCache(): void {
        $this->nodePermissionsCache = [];
        $this->permissionArrayCache = [];
    }

    /**
     * Clear this service's whole distributed cache namespace (page-path maps
     * included). PageService::clearCache() calls this on page mutations; it
     * used to reach into the cache through its own handle to the same
     * namespace, which was the second of two permission cache layers.
     */
    public function clearDistributedCache(): void {
        if ($this->distributedCache !== null) {
            $this->distributedCache->clear();
        }
    }

    /**
     * Filter a page tree to only include accessible pages.
     *
     * @param array $tree The full page tree
     * @param string $basePath Base path for permission calculation
     * @return array Filtered tree with only accessible pages
     */
    public function filterTree(array $tree, string $basePath = ''): array {
        $filtered = [];

        foreach ($tree as $node) {
            $nodePath = $basePath ? $basePath . '/' . ($node['slug'] ?? $node['id'] ?? '') : ($node['slug'] ?? $node['id'] ?? '');

            // Check if user can read this node
            if (!$this->canRead($nodePath)) {
                continue;
            }

            $filteredNode = $node;

            // Add permissions to the node
            $filteredNode['permissions'] = $this->getPermissionsObject($nodePath);

            // Recursively filter children
            if (isset($node['children']) && is_array($node['children'])) {
                $filteredNode['children'] = $this->filterTree($node['children'], $nodePath);
            }

            $filtered[] = $filteredNode;
        }

        return $filtered;
    }

    /**
     * Filter navigation items to only include accessible pages.
     *
     * Items are filtered based on the user's actual permissions:
     * - Items with uniqueId: Check if user has read access to that page
     * - Items with external URL: Always include
     * - Parent items without link: Include only if they have accessible children
     *
     * @param array $items Navigation items
     * @param string $language Current language
     * @param array|null $pagePathMap Optional pre-built map of uniqueId => path for performance
     * @return array Filtered navigation items
     */
    /**
     * @param bool $keepLinkless Keep items that have no link and no visible
     *   children. The menu drops those -- a heading that goes nowhere and holds
     *   nothing is noise for a visitor. The navigation EDITOR must keep them:
     *   an item is saved before its page link is set, and dropping it there
     *   makes it unreachable, so the link can never be added (issue #104).
     *   Permission filtering above still applies either way: an editor must not
     *   see items for pages they cannot read.
     */
    public function filterNavigation(array $items, string $language, ?array $pagePathMap = null, bool $keepLinkless = false): array {
        $filtered = [];

        foreach ($items as $item) {
            $includeItem = true;

            // If item has a uniqueId, check permissions for that page
            if (!empty($item['uniqueId'])) {
                $pagePath = null;

                // Try to get path from pre-built map first (faster)
                if ($pagePathMap !== null && isset($pagePathMap[$item['uniqueId']])) {
                    $pagePath = $pagePathMap[$item['uniqueId']];
                }

                // If we have a path, check permissions
                if ($pagePath !== null) {
                    if (!$this->canRead($pagePath)) {
                        $includeItem = false;
                    }
                }
                // If no path found, item might be orphaned - include it and let page load handle it
            }
            // External URLs are always included
            // Items without link are included if they have accessible children (checked below)

            if (!$includeItem) {
                continue;
            }

            $filteredItem = $item;

            // Recursively filter children
            if (isset($item['children']) && is_array($item['children'])) {
                $filteredChildren = $this->filterNavigation($item['children'], $language, $pagePathMap, $keepLinkless);
                $filteredItem['children'] = $filteredChildren;

                // If this item has no link (no uniqueId and no url), only include if it has accessible children
                if (!$keepLinkless && empty($item['uniqueId']) && empty($item['url']) && empty($filteredChildren)) {
                    continue;
                }
            }

            $filtered[] = $filteredItem;
        }

        return $filtered;
    }

    /**
     * Build a map of uniqueId => path for all pages in a language.
     *
     * This is used to efficiently filter navigation items by pre-loading all page paths.
     * The map is built by scanning the groupfolder structure.
     *
     * @param string $language Language code (nl, en, de, fr)
     * @return array Map of uniqueId => relative path
     */
    public function buildPagePathMap(string $language): array {
        // The path map is identical for every user (filesystem-derived, no
        // permission filtering), so we cache it per-language. Saves an
        // O(folders) walk on every navigation render at enterprise scale.
        $cacheKey = 'path_map_' . $language;
        if ($this->distributedCache !== null) {
            $cached = $this->distributedCache->get($cacheKey);
            if (is_string($cached)) {
                $decoded = json_decode($cached, true);
                if (is_array($decoded)) {
                    return $decoded;
                }
            }
        }

        $map = [];
        try {
            $folder = $this->setupService->getSharedFolder();
            if ($folder === null || !$folder->nodeExists($language)) {
                return $map;
            }

            $languageFolder = $folder->get($language);
            $this->scanFolderForPaths($languageFolder, $language, $map);
        } catch (\Exception $e) {
            $this->logger->warning('[PermissionService] Failed to build page path map', [
                'language' => $language,
                'error' => $e->getMessage()
            ]);
        }

        if ($this->distributedCache !== null) {
            $this->distributedCache->set($cacheKey, json_encode($map), self::PAGE_PATH_MAP_TTL);
        }

        return $map;
    }

    /**
     * Invalidate the cached page-path map for a single language. Called by
     * PageService on create/update/delete so the next nav render rebuilds.
     */
    public function invalidatePagePathMap(string $language): void {
        if ($this->distributedCache !== null) {
            $this->distributedCache->remove('path_map_' . $language);
        }
    }

    /**
     * Invalidate the cached page-path maps for all supported languages.
     * Use sparingly — most mutations only affect one language at a time.
     */
    public function invalidateAllPagePathMaps(): void {
        if ($this->distributedCache === null) {
            return;
        }
        foreach (['nl', 'en', 'de', 'fr'] as $language) {
            $this->distributedCache->remove('path_map_' . $language);
        }
    }

    /**
     * Recursively scan a folder to build the page path map.
     *
     * @param mixed $folder Folder to scan
     * @param string $currentPath Current path relative to IntraVox root
     * @param array &$map Reference to the map being built
     */
    private function scanFolderForPaths($folder, string $currentPath, array &$map): void {
        try {
            $items = $folder->getDirectoryListing();
        } catch (\Exception $e) {
            return;
        }

        foreach ($items as $item) {
            $name = $item->getName();

            if ($item->getType() === \OCP\Files\FileInfo::TYPE_FOLDER) {
                // Skip special folders
                if (PagePathHelper::isInfrastructureFolder($name)) {
                    continue;
                }

                // Recurse into subfolder
                $this->scanFolderForPaths($item, $currentPath . '/' . $name, $map);
            } elseif (substr($name, -5) === '.json') {
                // Skip navigation and footer files at language root
                if ($name === 'navigation.json' || $name === 'footer.json') {
                    continue;
                }

                try {
                    $content = $item->getContent();
                    $data = json_decode($content, true);

                    if ($data && isset($data['uniqueId'])) {
                        // Store the path for this uniqueId
                        $map[$data['uniqueId']] = $currentPath;
                    }
                } catch (\Exception $e) {
                    // Skip files we can't read
                    continue;
                }
            }
        }
    }

    /**
     * Get the relative path for a page within the IntraVox folder.
     *
     * @param string $absolutePath Full filesystem path
     * @return string Relative path within IntraVox folder
     */
    public function getRelativePath(string $absolutePath): string {
        try {
            $intraVoxFolder = $this->setupService->getSharedFolder();
            $basePath = $intraVoxFolder->getPath();

            if (strpos($absolutePath, $basePath) === 0) {
                return ltrim(substr($absolutePath, strlen($basePath)), '/');
            }
        } catch (\Exception $e) {
            $this->logger->debug('Could not determine relative path: ' . $e->getMessage());
        }

        return $absolutePath;
    }

}
