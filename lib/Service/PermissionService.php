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

    /** @var array Permission cache: [path => permissions] */
    private array $permissionCache = [];

    /** @var int|null Cached GroupFolder ID */
    private ?int $groupFolderId = null;

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
        if ($this->groupFolderId !== null) {
            return $this->groupFolderId;
        }

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
                    $this->groupFolderId = (int)$id;
                    return $this->groupFolderId;
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

        if (!$userId) {
            $this->logger->debug('No user ID, returning no permissions');
            return 0;
        }

        // Check cache first
        $cacheKey = $userId . ':' . $relativePath;
        if (isset($this->permissionCache[$cacheKey])) {
            return $this->permissionCache[$cacheKey];
        }

        try {
            $permissions = $this->calculatePermissions($relativePath, $userId);
            $this->permissionCache[$cacheKey] = $permissions;
            return $permissions;
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
     */
    private function applyAclRules(int $folderId, string $relativePath, string $userId, array $userGroups, int $basePermissions): int {
        try {
            // Check if ACL manager is available (groupfolders with ACL enabled)
            if (!class_exists('\OCA\GroupFolders\ACL\ACLManager')) {
                return $basePermissions;
            }

            $aclManager = \OC::$server->get(\OCA\GroupFolders\ACL\ACLManager::class);

            // Build the full path for ACL lookup
            // ACL paths are relative to the groupfolder root
            $aclPath = $relativePath;

            // Get the file node if it exists
            try {
                $intraVoxFolder = $this->setupService->getSharedFolder();
                $node = empty($relativePath) ? $intraVoxFolder : $intraVoxFolder->get($relativePath);

                // Get permissions from the ACL manager for this node
                $permissions = $node->getPermissions();

                // The node's getPermissions() already incorporates ACL rules
                // But we need to ensure we don't exceed base permissions
                return $permissions & $basePermissions;

            } catch (\OCP\Files\NotFoundException $e) {
                // Path doesn't exist yet (e.g., creating new page)
                // Use base permissions but check parent path ACL
                if (!empty($relativePath)) {
                    $parentPath = dirname($relativePath);
                    if ($parentPath !== '.' && $parentPath !== $relativePath) {
                        return $this->getPermissions($parentPath, $userId);
                    }
                }
                return $basePermissions;
            }

        } catch (\Exception $e) {
            $this->logger->debug('ACL check failed, using base permissions: ' . $e->getMessage());
            return $basePermissions;
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
                // We need to find the path for this uniqueId
                // For now, we'll include the item and let the page load check permissions
                // In a future optimization, we could cache uniqueId -> path mapping
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

    /**
     * Clear the permission cache.
     * Call this when ACL rules might have changed.
     */
    public function clearCache(): void {
        $this->permissionCache = [];
    }
}
