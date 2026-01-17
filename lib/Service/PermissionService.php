<?php
declare(strict_types=1);

namespace OCA\IntraVox\Service;

use OCP\Files\IRootFolder;
use OCP\Files\Node;
use OCP\IUserSession;
use OCP\IGroupManager;
use OCP\IConfig;
use OCP\Constants;
use Psr\Log\LoggerInterface;

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
    private ?string $userId;

    public function __construct(
        IRootFolder $rootFolder,
        IUserSession $userSession,
        IGroupManager $groupManager,
        SetupService $setupService,
        IConfig $config,
        LoggerInterface $logger,
        ?string $userId
    ) {
        $this->rootFolder = $rootFolder;
        $this->userSession = $userSession;
        $this->groupManager = $groupManager;
        $this->setupService = $setupService;
        $this->config = $config;
        $this->logger = $logger;
        $this->userId = $userId;
    }

    /**
     * Get the GroupFolder ID for IntraVox
     */
    private function getGroupFolderId(): ?int {
        try {
            if (!\OC::$server->getAppManager()->isEnabledForUser('groupfolders')) {
                return null;
            }

            $groupfolderManager = \OC::$server->get(\OCA\GroupFolders\Folder\FolderManager::class);
            $folders = $groupfolderManager->getAllFolders();

            foreach ($folders as $id => $folderData) {
                $mountPoint = null;
                if (is_object($folderData)) {
                    $mountPoint = property_exists($folderData, 'mountPoint') ? $folderData->mountPoint :
                                 (method_exists($folderData, 'getMountPoint') ? $folderData->getMountPoint() : null);
                } else {
                    $mountPoint = $folderData['mount_point'] ?? null;
                }

                if ($mountPoint === 'IntraVox') {
                    return (int)$id;
                }
            }
        } catch (\Exception $e) {
            $this->logger->error('Failed to get GroupFolder ID: ' . $e->getMessage());
        }

        return null;
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
            $this->logger->debug('No GroupFolder found, using default permissions');
            return self::PERMISSION_ALL; // Fallback if groupfolders not setup
        }

        try {
            $groupfolderManager = \OC::$server->get(\OCA\GroupFolders\Folder\FolderManager::class);

            // Get user's groups
            $user = \OC::$server->getUserManager()->get($userId);
            if (!$user) {
                return 0;
            }

            $userGroups = $this->groupManager->getUserGroupIds($user);

            // Get folder configuration
            $folderData = $groupfolderManager->getFolder($folderId, $this->rootFolder->getMountPoint()->getNumericStorageId());

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
            $db = \OC::$server->getDatabaseConnection();

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

        $user = \OC::$server->getUserManager()->get($userId);
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
     * @param array $items Navigation items
     * @param string $language Current language
     * @return array Filtered navigation items
     */
    public function filterNavigation(array $items, string $language): array {
        $filtered = [];

        foreach ($items as $item) {
            // If item has a uniqueId, check permissions for that page
            if (!empty($item['uniqueId'])) {
                // We'll include the item and let the page load check permissions
                $filteredItem = $item;
            } elseif (!empty($item['url'])) {
                // External URL, always include
                $filteredItem = $item;
            } else {
                // No link, include for structure
                $filteredItem = $item;
            }

            // Recursively filter children
            if (isset($item['children']) && is_array($item['children'])) {
                $filteredChildren = $this->filterNavigation($item['children'], $language);
                // Only include parent if it has accessible children or is accessible itself
                if (empty($filteredChildren) && empty($item['uniqueId']) && empty($item['url'])) {
                    continue;
                }
                $filteredItem['children'] = $filteredChildren;
            }

            $filtered[] = $filteredItem;
        }

        return $filtered;
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
