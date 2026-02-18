<?php
declare(strict_types=1);

namespace OCA\IntraVox\Service;

use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\Files\IRootFolder;
use OCP\Files\NotFoundException;
use OCP\IConfig;
use OCP\IDBConnection;
use OCP\Share\IManager as IShareManager;
use OCP\Share\IShare;
use OCP\Security\IHasher;
use OCP\Share\Exceptions\ShareNotFound;
use Psr\Log\LoggerInterface;

/**
 * PublicShareService handles detection and validation of NC share links for IntraVox pages.
 *
 * This service integrates with Nextcloud's native share system (IShareManager) to:
 * 1. Detect if a page or its parent folder has a public share link
 * 2. Validate share tokens for anonymous access
 * 3. Provide scope information (page-level vs folder-level shares)
 *
 * IMPORTANT: IntraVox always enforces read-only access for anonymous users,
 * regardless of the permissions set on the NC share.
 */
class PublicShareService {
    private IShareManager $shareManager;
    private IRootFolder $rootFolder;
    private IDBConnection $db;
    private SetupService $setupService;
    private PageService $pageService;
    private IConfig $config;
    private LoggerInterface $logger;
    private PermissionService $permissionService;
    private IHasher $hasher;

    public function __construct(
        IShareManager $shareManager,
        IRootFolder $rootFolder,
        IDBConnection $db,
        SetupService $setupService,
        PageService $pageService,
        IConfig $config,
        LoggerInterface $logger,
        PermissionService $permissionService,
        IHasher $hasher
    ) {
        $this->shareManager = $shareManager;
        $this->rootFolder = $rootFolder;
        $this->db = $db;
        $this->setupService = $setupService;
        $this->pageService = $pageService;
        $this->config = $config;
        $this->logger = $logger;
        $this->permissionService = $permissionService;
        $this->hasher = $hasher;
    }

    /**
     * Check if a share requires a password.
     */
    public function shareRequiresPassword(string $token): bool {
        try {
            $share = $this->shareManager->getShareByToken($token);
            if ($share->getShareType() !== IShare::TYPE_LINK) {
                return false;
            }
            $hash = $share->getPassword();
            return $hash !== null && $hash !== '';
        } catch (ShareNotFound $e) {
            return false;
        }
    }

    /**
     * Verify a password against a share's stored password hash.
     *
     * @return bool True if password is correct or share has no password
     */
    public function checkSharePassword(string $token, string $password): bool {
        try {
            $share = $this->shareManager->getShareByToken($token);
            $hash = $share->getPassword();
            if ($hash === null || $hash === '') {
                return true; // No password required
            }
            return $this->hasher->verify($password, $hash);
        } catch (ShareNotFound $e) {
            return false;
        }
    }

    /**
     * Get share info for a page.
     *
     * Searches for NC share links on the page itself and all parent folders.
     * Returns the first (most specific) share found.
     *
     * @param string $pageUniqueId The page's unique ID
     * @param string $language The language folder
     * @param string|null $userId The current user (for permission checking)
     * @return array Share info with hasShare, token, scope, scopeName, children, managePath
     */
    public function getShareInfoForPage(string $pageUniqueId, string $language, ?string $userId = null): array {
        // Check NC-level sharing setting
        $ncAllowsLinks = $this->config->getAppValue('core', 'shareapi_allow_links', 'yes') === 'yes';

        try {
            // Get the page's file path
            $pageInfo = $this->findPageFileInfo($pageUniqueId, $language);
            if ($pageInfo === null) {
                $this->logger->debug('[PublicShareService] Page not found', [
                    'pageUniqueId' => $pageUniqueId,
                    'language' => $language
                ]);
                return ['hasShare' => false, 'reason' => 'page_not_found'];
            }

            $pagePath = $pageInfo['path'];

            // If NC link sharing is disabled, return early but include filesUrl
            if (!$ncAllowsLinks) {
                $this->logger->debug('[PublicShareService] NC link sharing disabled');
                return [
                    'hasShare' => false,
                    'reason' => 'nc_disabled',
                    'filesUrl' => $this->buildFilesUrl(dirname($pagePath), null, $userId),
                ];
            }
            $pageNode = $pageInfo['node'];

            $this->logger->debug('[PublicShareService] Found page', [
                'pageUniqueId' => $pageUniqueId,
                'pagePath' => $pagePath
            ]);

            // 1. Check for share on the page JSON file itself
            $shares = $this->getSharesForNode($pageNode);
            $this->logger->debug('[PublicShareService] Checked page node for shares', [
                'pagePath' => $pagePath,
                'sharesFound' => count($shares)
            ]);

            if (!empty($shares)) {
                $share = $shares[0];
                $pw = $share->getPassword();
                return [
                    'hasShare' => true,
                    'token' => $share->getToken(),
                    'scope' => 'page',
                    'scopeName' => $pageInfo['title'] ?? basename($pagePath, '.json'),
                    'scopeLabel' => $pageInfo['title'] ?? basename($pagePath, '.json'),
                    'isLanguageRoot' => false,
                    'hasPassword' => $pw !== null && $pw !== '',
                    'children' => [],
                    'navigation' => [],
                    'managePath' => $this->getRelativePath(dirname($pagePath)),
                    'filesUrl' => $this->buildFilesUrl(dirname($pagePath), null, $userId),
                    'publicUrl' => $this->buildPublicUrl($share->getToken(), $pageUniqueId),
                ];
            }

            // 2. Check parent folders (page folder, then language folder, etc.)
            $parentPath = dirname($pagePath);
            $this->logger->debug('[PublicShareService] Starting parent folder check', [
                'startingPath' => $parentPath
            ]);

            while ($this->isInIntraVoxFolder($parentPath)) {
                $this->logger->debug('[PublicShareService] Checking parent folder', [
                    'parentPath' => $parentPath
                ]);

                try {
                    $parentNode = $this->getNodeByPath($parentPath);
                    $this->logger->debug('[PublicShareService] Got parent node', [
                        'parentPath' => $parentPath,
                        'nodeType' => $parentNode->getType()
                    ]);

                    $shares = $this->getSharesForNode($parentNode);
                    $this->logger->debug('[PublicShareService] Checked parent for shares', [
                        'parentPath' => $parentPath,
                        'sharesFound' => count($shares)
                    ]);

                    if (!empty($shares)) {
                        $share = $shares[0];
                        $pw = $share->getPassword();
                        $children = $this->getChildPagesInFolder($parentPath, $language);
                        return [
                            'hasShare' => true,
                            'token' => $share->getToken(),
                            'scope' => 'folder',
                            'scopeName' => basename($parentPath),
                            'scopeLabel' => $this->getScopeLabel($parentPath, $language),
                            'isLanguageRoot' => $this->isLanguageRoot($parentPath),
                            'hasPassword' => $pw !== null && $pw !== '',
                            'children' => $children,
                            'childCount' => count($children),
                            'navigation' => $this->getNavigationTree($parentPath, $language),
                            'managePath' => $this->getRelativePath($parentPath),
                            'filesUrl' => $this->buildFilesUrl($parentPath, null, $userId),
                            'publicUrl' => $this->buildPublicUrl($share->getToken(), $pageUniqueId),
                        ];
                    }
                } catch (NotFoundException $e) {
                    $this->logger->debug('[PublicShareService] Parent not found, continuing up', [
                        'parentPath' => $parentPath,
                        'error' => $e->getMessage()
                    ]);
                }

                $parentPath = dirname($parentPath);
            }

            $this->logger->debug('[PublicShareService] No share found after checking all parents', [
                'finalPath' => $parentPath
            ]);

            return [
                'hasShare' => false,
                'reason' => 'no_share',
                'filesUrl' => $this->buildFilesUrl(dirname($pagePath), null, $userId),
            ];

        } catch (\Exception $e) {
            $this->logger->error('[PublicShareService] Error getting share info', [
                'pageUniqueId' => $pageUniqueId,
                'error' => $e->getMessage()
            ]);
            return ['hasShare' => false, 'reason' => 'error'];
        }
    }

    /**
     * Get a share by its token.
     *
     * Simple wrapper around IShareManager::getShareByToken() for use in controllers.
     *
     * @param string $token The NC share token
     * @return IShare|null The share object, or null if not found/invalid
     */
    public function getShareByToken(string $token): ?IShare {
        $ncAllowsLinks = $this->config->getAppValue('core', 'shareapi_allow_links', 'yes') === 'yes';
        if (!$ncAllowsLinks) {
            return null;
        }

        try {
            $share = $this->shareManager->getShareByToken($token);

            // Must be a link share
            if ($share->getShareType() !== IShare::TYPE_LINK) {
                return null;
            }

            // Check if share is expired
            $expirationDate = $share->getExpirationDate();
            if ($expirationDate !== null && $expirationDate < new \DateTime()) {
                return null;
            }

            return $share;

        } catch (ShareNotFound $e) {
            return null;
        } catch (\Exception $e) {
            $this->logger->debug('[PublicShareService] Error getting share by token', [
                'token' => '***',
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Resolve the actual GF storage path for a share.
     *
     * file_target in oc_share is unreliable for GroupFolders (e.g. "/afdeling" instead of "files/nl/afdeling").
     * This method looks up the share's file_source fileid in the GF storage's filecache
     * to get the canonical path.
     *
     * @param IShare $share The share object
     * @return string|null GF storage path (e.g. "files/nl/afdeling"), or null on error
     */
    public function resolveShareScopePath(IShare $share): ?string {
        try {
            $shareNodeId = $share->getNodeId();
            $groupfolderId = $this->setupService->getGroupFolderId();

            // Look up GF storage ID
            $qbStorage = $this->db->getQueryBuilder();
            $qbStorage->select('storage_id')
                ->from('group_folders')
                ->where($qbStorage->expr()->eq('folder_id', $qbStorage->createNamedParameter($groupfolderId, IQueryBuilder::PARAM_INT)));
            $storageResult = $qbStorage->executeQuery();
            $storageRow = $storageResult->fetch();
            $storageResult->closeCursor();

            if (!$storageRow) {
                return null;
            }
            $gfStorageId = (int)$storageRow['storage_id'];

            // Look up the share's path on GF storage
            $qb = $this->db->getQueryBuilder();
            $qb->select('path')
                ->from('filecache')
                ->where($qb->expr()->eq('storage', $qb->createNamedParameter($gfStorageId, IQueryBuilder::PARAM_INT)))
                ->andWhere($qb->expr()->eq('fileid', $qb->createNamedParameter($shareNodeId, IQueryBuilder::PARAM_INT)));
            $result = $qb->executeQuery();
            $row = $result->fetch();
            $result->closeCursor();

            // Security: Do NOT fall back to querying any storage.
            // Share must reference a file within the IntraVox GroupFolder storage.
            if (!$row || empty($row['path'])) {
                return null;
            }

            return $row['path'];
        } catch (\Exception $e) {
            $this->logger->error('[PublicShareService] Error resolving share scope path', [
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Validate a share token and check if it grants access to a specific page.
     *
     * @param string $token The NC share token
     * @param string $pageUniqueId The page's unique ID
     * @param string $language The language folder
     * @return array Validation result with 'valid', 'share', 'pageData'
     */
    public function validateShareAccess(string $token, string $pageUniqueId, string $language, ?string $password = null): array {
        // Check NC-level sharing setting
        $ncAllowsLinks = $this->config->getAppValue('core', 'shareapi_allow_links', 'yes') === 'yes';
        if (!$ncAllowsLinks) {
            $this->logger->debug('[PublicShareService] validateShareAccess: NC link sharing disabled');
            return ['valid' => false, 'reason' => 'nc_disabled'];
        }

        try {
            // Get the share by token
            $share = $this->shareManager->getShareByToken($token);

            $this->logger->debug('[PublicShareService] validateShareAccess: got share', [
                'token' => '***',
                'shareType' => $share->getShareType()
            ]);

            // Must be a link share
            if ($share->getShareType() !== IShare::TYPE_LINK) {
                $this->logger->debug('[PublicShareService] validateShareAccess: not a link share');
                return ['valid' => false, 'reason' => 'not_link_share'];
            }

            // Check if share is expired
            $expirationDate = $share->getExpirationDate();
            if ($expirationDate !== null && $expirationDate < new \DateTime()) {
                $this->logger->debug('[PublicShareService] validateShareAccess: share expired');
                return ['valid' => false, 'reason' => 'expired'];
            }

            // Check password protection (respects NC share password setting)
            $sharePasswordHash = $share->getPassword();
            if ($sharePasswordHash !== null && $sharePasswordHash !== '') {
                if ($password === null || $password === '') {
                    $this->logger->debug('[PublicShareService] validateShareAccess: password required');
                    return ['valid' => false, 'reason' => 'password_required'];
                }
                if (!$this->hasher->verify($password, $sharePasswordHash)) {
                    $this->logger->debug('[PublicShareService] validateShareAccess: invalid password');
                    return ['valid' => false, 'reason' => 'invalid_password'];
                }
            }

            // Get the shared node's file_target (relative path like "/nl" or "/nl/about")
            // This is what's stored in the database and matches our relative path format
            $shareTarget = $share->getTarget();

            $this->logger->debug('[PublicShareService] validateShareAccess: share target', [
                'shareTarget' => $shareTarget
            ]);

            // Get the page's path (internal GroupFolders path)
            // First try with the provided language, then try to detect from share target
            $pageInfo = $this->findPageFileInfo($pageUniqueId, $language);

            // If not found, try to detect language from share target
            // Share target like "/nl" or "/nl/about" tells us the language
            if ($pageInfo === null) {
                $detectedLanguage = $this->detectLanguageFromPath($shareTarget);
                if ($detectedLanguage !== null && $detectedLanguage !== $language) {
                    $this->logger->debug('[PublicShareService] validateShareAccess: trying detected language', [
                        'originalLanguage' => $language,
                        'detectedLanguage' => $detectedLanguage
                    ]);
                    $pageInfo = $this->findPageFileInfo($pageUniqueId, $detectedLanguage);
                    if ($pageInfo !== null) {
                        $language = $detectedLanguage;
                    }
                }
            }

            // If still not found, search all available languages
            if ($pageInfo === null) {
                $this->logger->debug('[PublicShareService] validateShareAccess: searching all languages');
                $pageInfo = $this->findPageFileInfoAllLanguages($pageUniqueId);
            }

            if ($pageInfo === null) {
                $this->logger->debug('[PublicShareService] validateShareAccess: page not found in any language', [
                    'pageUniqueId' => $pageUniqueId,
                    'triedLanguage' => $language
                ]);
                return ['valid' => false, 'reason' => 'page_not_found'];
            }

            $pagePath = $pageInfo['path'];

            // Resolve the share's actual path via file_source on the GF storage.
            // file_target (e.g. "/afdeling") is unreliable for GroupFolders because
            // it's relative to the user's mount, not the internal GF path.
            // Instead, we look up the file_source fileid in the GF storage's filecache
            // to get the canonical path (e.g. "files/nl/afdeling").
            $folder = $this->setupService->getSharedFolder();
            if ($folder === null) {
                $this->logger->debug('[PublicShareService] validateShareAccess: IntraVox folder not found');
                return ['valid' => false, 'reason' => 'error'];
            }

            $intraVoxPath = $folder->getPath();

            // Get the share's file_source path on GF storage
            $shareNodeId = $share->getNodeId();
            $groupfolderId = $this->setupService->getGroupFolderId();

            // Look up GF storage ID
            $qbStorage = $this->db->getQueryBuilder();
            $qbStorage->select('storage_id')
                ->from('group_folders')
                ->where($qbStorage->expr()->eq('folder_id', $qbStorage->createNamedParameter($groupfolderId, IQueryBuilder::PARAM_INT)));
            $storageResult = $qbStorage->executeQuery();
            $storageRow = $storageResult->fetch();
            $storageResult->closeCursor();

            if (!$storageRow) {
                return ['valid' => false, 'reason' => 'error'];
            }
            $gfStorageId = (int)$storageRow['storage_id'];

            // Look up the share's path on GF storage using file_source
            $qbSharePath = $this->db->getQueryBuilder();
            $qbSharePath->select('path')
                ->from('filecache')
                ->where($qbSharePath->expr()->eq('storage', $qbSharePath->createNamedParameter($gfStorageId, IQueryBuilder::PARAM_INT)))
                ->andWhere($qbSharePath->expr()->eq('fileid', $qbSharePath->createNamedParameter($shareNodeId, IQueryBuilder::PARAM_INT)));
            $sharePathResult = $qbSharePath->executeQuery();
            $sharePathRow = $sharePathResult->fetch();
            $sharePathResult->closeCursor();

            // Security: Do NOT fall back to querying any storage.
            // Share must reference a file within the IntraVox GroupFolder storage.
            if (!$sharePathRow || empty($sharePathRow['path'])) {
                $this->logger->debug('[PublicShareService] validateShareAccess: share path not found in GroupFolder storage');
                return ['valid' => false, 'reason' => 'error'];
            }

            // The GF storage path is like "files/nl/afdeling"
            // The page's internal path is like "/__groupfolders/1/files/nl/afdeling/afdeling.json"
            // Convert page path to GF storage format: strip __groupfolders/{id}/ prefix
            $gfPrefix = '__groupfolders/' . $groupfolderId . '/';
            $pageGfPath = $pagePath;
            // Strip leading / and remove the __groupfolders/{id}/ prefix if present
            $pageGfPath = ltrim($pageGfPath, '/');
            if (str_starts_with($pageGfPath, $gfPrefix)) {
                $pageGfPath = substr($pageGfPath, strlen($gfPrefix));
            }

            $sharePath = $sharePathRow['path'];
            // Also strip __groupfolders/{id}/ from share path if present
            $sharePath = ltrim($sharePath, '/');
            if (str_starts_with($sharePath, $gfPrefix)) {
                $sharePath = substr($sharePath, strlen($gfPrefix));
            }

            $sharePathNormalized = $this->normalizePath(rtrim($sharePath, '/'));
            $pageGfPath = $this->normalizePath($pageGfPath);

            $this->logger->debug('[PublicShareService] validateShareAccess: path comparison', [
                'shareTarget' => $shareTarget,
                'shareNodeId' => $shareNodeId,
                'sharePath' => $sharePathNormalized,
                'pageGfPath' => $pageGfPath,
            ]);

            // Page is accessible if:
            // - sharePath is exact match with page path
            // - sharePath is a parent folder of the page
            $isExactMatch = ($pageGfPath === $sharePathNormalized);
            $isInScope = str_starts_with($pageGfPath, $sharePathNormalized . '/');

            $this->logger->debug('[PublicShareService] validateShareAccess: scope check', [
                'sharePathNormalized' => $sharePathNormalized,
                'pageGfPath' => $pageGfPath,
                'isExactMatch' => $isExactMatch,
                'isInScope' => $isInScope
            ]);

            if (!$isExactMatch && !$isInScope) {
                $this->logger->debug('[PublicShareService] validateShareAccess: page out of share scope');
                return ['valid' => false, 'reason' => 'out_of_scope'];
            }

            $this->logger->debug('[PublicShareService] validateShareAccess: SUCCESS', [
                'pageTitle' => $pageInfo['data']['title'] ?? 'unknown'
            ]);

            // Valid! Return share and page data
            return [
                'valid' => true,
                'share' => $share,
                'pageData' => $pageInfo['data'],
                'pagePath' => $pagePath,
            ];

        } catch (ShareNotFound $e) {
            $this->logger->debug('[PublicShareService] validateShareAccess: share not found');
            return ['valid' => false, 'reason' => 'share_not_found'];
        } catch (\Exception $e) {
            $this->logger->error('[PublicShareService] Error validating share access', [
                'token' => '***',
                'pageUniqueId' => $pageUniqueId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return ['valid' => false, 'reason' => 'error'];
        }
    }

    /**
     * Find page file info by uniqueId.
     *
     * @param string $uniqueId Page unique ID
     * @param string $language Language folder
     * @return array|null Array with 'path', 'node', 'data', 'title' or null
     */
    private function findPageFileInfo(string $uniqueId, string $language): ?array {
        try {
            $folder = $this->setupService->getSharedFolder();
            if ($folder === null || !$folder->nodeExists($language)) {
                return null;
            }

            $langFolder = $folder->get($language);
            $result = $this->searchForPageFile($langFolder, $uniqueId);

            if ($result !== null) {
                return [
                    'path' => $result['file']->getPath(),
                    'node' => $result['file'],
                    'data' => $result['data'],
                    'title' => $result['data']['title'] ?? null,
                ];
            }

            return null;
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Recursively search for a page file by uniqueId.
     */
    private function searchForPageFile($folder, string $uniqueId): ?array {
        try {
            $items = $folder->getDirectoryListing();
        } catch (\Exception $e) {
            return null;
        }

        foreach ($items as $item) {
            $name = $item->getName();

            if ($item->getType() === \OCP\Files\FileInfo::TYPE_FOLDER) {
                // Skip special folders
                if (in_array($name, ['_media', '_resources', 'images', '.nomedia'], true)) {
                    continue;
                }
                // Recurse
                $result = $this->searchForPageFile($item, $uniqueId);
                if ($result !== null) {
                    return $result;
                }
            } elseif (str_ends_with($name, '.json')) {
                // Skip navigation and footer
                if ($name === 'navigation.json' || $name === 'footer.json') {
                    continue;
                }

                try {
                    $content = $item->getContent();
                    $data = json_decode($content, true);

                    if ($data && ($data['uniqueId'] ?? '') === $uniqueId) {
                        return ['file' => $item, 'data' => $data];
                    }
                } catch (\Exception $e) {
                    continue;
                }
            }
        }

        return null;
    }

    /**
     * Get shares for a node.
     *
     * GroupFolders creates separate file IDs for the internal storage vs the user's mount point.
     * Shares are created on the user's mount point (files/IntraVox/nl), but we access via
     * the internal path (__groupfolders/1/files/nl).
     *
     * Solution: Query the share database directly by file_target path.
     */
    private function getSharesForNode($node): array {
        try {
            // Get the relative path within IntraVox folder
            $folder = $this->setupService->getSharedFolder();
            if ($folder === null) {
                return [];
            }

            $intraVoxPath = $folder->getPath();
            $nodePath = $node->getPath();

            // Extract relative path (e.g., "nl" or "nl/about/about.json")
            if (!str_starts_with($nodePath, $intraVoxPath)) {
                $this->logger->debug('[PublicShareService] Node not in IntraVox folder', [
                    'nodePath' => $nodePath,
                    'intraVoxPath' => $intraVoxPath
                ]);
                return [];
            }

            $relativePath = substr($nodePath, strlen($intraVoxPath));
            $relativePath = ltrim($relativePath, '/');

            // GroupFolders use separate storages: the internal storage (global root)
            // has paths like __groupfolders/{id}/files/nl/afdeling, but shares reference
            // file IDs on the GroupFolder's own storage with paths like files/nl/afdeling.
            // We need to find the file_source (fileid) on the GF storage to match shares.
            $groupfolderId = $this->setupService->getGroupFolderId();
            $gfStoragePath = 'files/' . $relativePath;

            $this->logger->debug('[PublicShareService] Looking for shares via file_source', [
                'nodePath' => $nodePath,
                'relativePath' => $relativePath,
                'gfStoragePath' => $gfStoragePath,
                'groupfolderId' => $groupfolderId
            ]);

            // Step 1: Find the fileid on the GroupFolder storage
            // The GF storage ID is stored in oc_group_folders.storage_id
            $qbStorage = $this->db->getQueryBuilder();
            $qbStorage->select('storage_id')
                ->from('group_folders')
                ->where($qbStorage->expr()->eq('folder_id', $qbStorage->createNamedParameter($groupfolderId, IQueryBuilder::PARAM_INT)));

            $storageResult = $qbStorage->executeQuery();
            $storageRow = $storageResult->fetch();
            $storageResult->closeCursor();

            if (!$storageRow) {
                $this->logger->debug('[PublicShareService] GroupFolder storage not found');
                return [];
            }

            $gfStorageId = (int)$storageRow['storage_id'];

            // Step 2: Find the fileid for this path on the GF storage
            $qbFile = $this->db->getQueryBuilder();
            $qbFile->select('fileid')
                ->from('filecache')
                ->where($qbFile->expr()->eq('storage', $qbFile->createNamedParameter($gfStorageId, IQueryBuilder::PARAM_INT)))
                ->andWhere($qbFile->expr()->eq('path', $qbFile->createNamedParameter($gfStoragePath)));

            $fileResult = $qbFile->executeQuery();
            $fileRow = $fileResult->fetch();
            $fileResult->closeCursor();

            if (!$fileRow) {
                $this->logger->debug('[PublicShareService] File not found on GF storage', [
                    'gfStorageId' => $gfStorageId,
                    'gfStoragePath' => $gfStoragePath
                ]);
                return [];
            }

            $fileSource = (int)$fileRow['fileid'];

            // Step 3: Query shares by file_source
            $qb = $this->db->getQueryBuilder();
            $qb->select('id', 'token', 'file_target', 'uid_owner', 'expiration')
               ->from('share')
               ->where($qb->expr()->eq('share_type', $qb->createNamedParameter(IShare::TYPE_LINK, IQueryBuilder::PARAM_INT)))
               ->andWhere($qb->expr()->eq('file_source', $qb->createNamedParameter($fileSource, IQueryBuilder::PARAM_INT)));

            $result = $qb->executeQuery();
            $rows = $result->fetchAll();
            $result->closeCursor();

            $this->logger->debug('[PublicShareService] DB query result', [
                'fileSource' => $fileSource,
                'gfStoragePath' => $gfStoragePath,
                'rowCount' => count($rows)
            ]);

            if (empty($rows)) {
                return [];
            }

            // Convert to IShare objects using the token
            $shares = [];
            foreach ($rows as $row) {
                try {
                    // Check expiration
                    if (!empty($row['expiration'])) {
                        $expiration = new \DateTime($row['expiration']);
                        if ($expiration < new \DateTime()) {
                            $this->logger->debug('[PublicShareService] Share expired', [
                                'token' => '***'
                            ]);
                            continue;
                        }
                    }

                    $share = $this->shareManager->getShareByToken($row['token']);
                    $shares[] = $share;

                    $this->logger->debug('[PublicShareService] Found share', [
                        'token' => '***',
                        'target' => $row['file_target']
                    ]);
                } catch (ShareNotFound $e) {
                    // Token no longer valid
                    continue;
                }
            }

            return $shares;
        } catch (\Exception $e) {
            $this->logger->warning('[PublicShareService] Error getting shares for node', [
                'path' => $node->getPath(),
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }

    /**
     * Get a node by absolute path.
     */
    private function getNodeByPath(string $path) {
        // The path is absolute, we need to access via rootFolder
        // First, try via the groupfolder
        $folder = $this->setupService->getSharedFolder();
        if ($folder === null) {
            throw new NotFoundException("IntraVox folder not found");
        }

        // Get relative path within IntraVox folder
        $intraVoxPath = $folder->getPath();
        if (str_starts_with($path, $intraVoxPath)) {
            $relativePath = substr($path, strlen($intraVoxPath));
            $relativePath = ltrim($relativePath, '/');
            if (empty($relativePath)) {
                return $folder;
            }
            return $folder->get($relativePath);
        }

        throw new NotFoundException("Path not in IntraVox folder");
    }

    /**
     * Check if a path is within the IntraVox folder.
     */
    private function isInIntraVoxFolder(string $path): bool {
        try {
            $folder = $this->setupService->getSharedFolder();
            if ($folder === null) {
                return false;
            }
            $intraVoxPath = $folder->getPath();
            return str_starts_with($path, $intraVoxPath) && $path !== $intraVoxPath;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Get child pages in a folder.
     */
    private function getChildPagesInFolder(string $folderPath, string $language): array {
        $children = [];

        try {
            $node = $this->getNodeByPath($folderPath);
            if ($node->getType() !== \OCP\Files\FileInfo::TYPE_FOLDER) {
                return $children;
            }

            $this->collectChildPages($node, $children, 0, 10); // Max 10 children shown
        } catch (\Exception $e) {
            // Ignore errors
        }

        return $children;
    }

    /**
     * Recursively collect child pages.
     */
    private function collectChildPages($folder, array &$children, int $depth, int $maxChildren): void {
        if (count($children) >= $maxChildren || $depth > 7) {
            return;
        }

        try {
            $items = $folder->getDirectoryListing();
        } catch (\Exception $e) {
            return;
        }

        foreach ($items as $item) {
            if (count($children) >= $maxChildren) {
                return;
            }

            $name = $item->getName();

            if ($item->getType() === \OCP\Files\FileInfo::TYPE_FOLDER) {
                if (in_array($name, ['_media', '_resources', 'images', '.nomedia'], true)) {
                    continue;
                }
                $this->collectChildPages($item, $children, $depth + 1, $maxChildren);
            } elseif (str_ends_with($name, '.json') && $name !== 'navigation.json' && $name !== 'footer.json') {
                try {
                    $content = $item->getContent();
                    $data = json_decode($content, true);
                    if ($data && isset($data['title'])) {
                        $children[] = [
                            'title' => $data['title'],
                            'uniqueId' => $data['uniqueId'] ?? null,
                        ];
                    }
                } catch (\Exception $e) {
                    continue;
                }
            }
        }
    }

    /**
     * Get relative path for display.
     */
    private function getRelativePath(string $absolutePath): string {
        try {
            $folder = $this->setupService->getSharedFolder();
            if ($folder === null) {
                return $absolutePath;
            }
            $intraVoxPath = $folder->getPath();
            if (str_starts_with($absolutePath, $intraVoxPath)) {
                return 'IntraVox' . substr($absolutePath, strlen($intraVoxPath));
            }
        } catch (\Exception $e) {
            // Ignore
        }
        return $absolutePath;
    }

    /**
     * Build the public IntraVox URL for a share.
     * Uses path-based URL /s/{token} instead of query param to avoid NC routing issues.
     * Page ID is passed as ?page= query parameter (survives chat apps, email clients, etc.)
     */
    private function buildPublicUrl(string $token, string $pageUniqueId): string {
        return '/apps/intravox/s/' . $token . '?page=' . urlencode($pageUniqueId);
    }

    /**
     * Detect language from a path like "/nl" or "/nl/about/about.json".
     *
     * @param string $path The path to analyze
     * @return string|null The detected language code or null
     */
    private function detectLanguageFromPath(string $path): ?string {
        // Remove leading slash and get first segment
        $path = ltrim($path, '/');
        $segments = explode('/', $path);

        if (empty($segments) || empty($segments[0])) {
            return null;
        }

        $potentialLanguage = $segments[0];

        // Validate it's a valid language folder (2-3 char code)
        if (strlen($potentialLanguage) >= 2 && strlen($potentialLanguage) <= 3 && ctype_alpha($potentialLanguage)) {
            // Check if this folder actually exists
            try {
                $folder = $this->setupService->getSharedFolder();
                if ($folder !== null && $folder->nodeExists($potentialLanguage)) {
                    return $potentialLanguage;
                }
            } catch (\Exception $e) {
                // Ignore
            }
        }

        return null;
    }

    /**
     * Find page file info by searching all available languages.
     *
     * @param string $uniqueId Page unique ID
     * @return array|null Array with 'path', 'node', 'data', 'title' or null
     */
    private function findPageFileInfoAllLanguages(string $uniqueId): ?array {
        try {
            $folder = $this->setupService->getSharedFolder();
            if ($folder === null) {
                return null;
            }

            // Get all language folders (top-level directories)
            $items = $folder->getDirectoryListing();
            foreach ($items as $item) {
                if ($item->getType() !== \OCP\Files\FileInfo::TYPE_FOLDER) {
                    continue;
                }

                $name = $item->getName();

                // Skip special folders
                if (in_array($name, ['_media', '_resources', 'images', '.nomedia'], true)) {
                    continue;
                }

                // Try to find the page in this language folder
                $result = $this->findPageFileInfo($uniqueId, $name);
                if ($result !== null) {
                    $this->logger->debug('[PublicShareService] Found page in language folder', [
                        'uniqueId' => $uniqueId,
                        'language' => $name
                    ]);
                    return $result;
                }
            }

            return null;
        } catch (\Exception $e) {
            $this->logger->warning('[PublicShareService] Error searching all languages', [
                'uniqueId' => $uniqueId,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Normalize a path for safe comparison.
     *
     * Applies Unicode NFC normalization to prevent bypasses using
     * different Unicode representations of the same characters
     * (e.g., NFC precomposed "é" vs NFD decomposed "e" + combining accent).
     *
     * @param string $path The path to normalize
     * @return string Normalized path
     */
    private function normalizePath(string $path): string {
        if (function_exists('normalizer_normalize')) {
            $normalized = \Normalizer::normalize($path, \Normalizer::FORM_C);
            if ($normalized !== false) {
                return $normalized;
            }
        }
        return $path;
    }

    /**
     * Filter navigation items by share scope.
     *
     * @param array $items Navigation items
     * @param string $shareScopeRelative Share scope path relative to language folder (e.g. "afdeling" or "" for whole language)
     * @param array $pagePathMap Map of uniqueId => folder path (e.g. "nl/afdeling")
     * @return array Filtered navigation items
     */
    /**
     * Get a human-readable scope label for the share dialog.
     */
    private function getScopeLabel(string $folderPath, string $language): string {
        $languageNames = [
            'en' => 'English',
            'nl' => 'Nederlands',
            'de' => 'Deutsch',
            'fr' => 'Français',
            'es' => 'Español',
            'it' => 'Italiano',
            'pt' => 'Português',
            'pl' => 'Polski',
            'cs' => 'Čeština',
            'da' => 'Dansk',
            'sv' => 'Svenska',
            'nb' => 'Norsk',
            'fi' => 'Suomi',
            'ja' => '日本語',
            'zh' => '中文',
            'ko' => '한국어',
            'ru' => 'Русский',
            'uk' => 'Українська',
            'ar' => 'العربية',
            'tr' => 'Türkçe',
        ];

        // Check if this is a language-root share
        $relativePath = $this->getRelativePathRaw($folderPath);
        $parts = explode('/', ltrim($relativePath, '/'));

        if (count($parts) === 1) {
            // Language root — use language name
            $langName = $languageNames[$language] ?? strtoupper($language);
            return $langName;
        }

        // Subfolder — read page title from {foldername}.json
        $folderName = basename($folderPath);
        try {
            $node = $this->getNodeByPath($folderPath);
            $jsonFileName = $folderName . '.json';
            if ($node->nodeExists($jsonFileName)) {
                $jsonFile = $node->get($jsonFileName);
                $data = json_decode($jsonFile->getContent(), true);
                if ($data && !empty($data['title'])) {
                    return $data['title'];
                }
            }
        } catch (\Exception $e) {
            // fallback
        }

        return ucfirst(str_replace('-', ' ', $folderName));
    }

    /**
     * Get navigation tree for the share dialog.
     * Returns items from navigation.json, limited to 2 levels deep.
     */
    private function getNavigationTree(string $folderPath, string $language): array {
        try {
            $folder = $this->setupService->getSharedFolder();
            if ($folder === null) {
                return [];
            }

            $navPath = $language . '/navigation.json';
            if (!$folder->nodeExists($navPath)) {
                return [];
            }

            $navFile = $folder->get($navPath);
            $navData = json_decode($navFile->getContent(), true, 64);
            if (!$navData || empty($navData['items'])) {
                return [];
            }

            $items = $navData['items'];

            // Filter by share scope (unless language root which includes everything)
            if (!$this->isLanguageRoot($folderPath)) {
                $relativePath = $this->getRelativePathRaw($folderPath);
                $parts = explode('/', ltrim($relativePath, '/'));
                $shareScopeRelative = implode('/', array_slice($parts, 1));

                $pagePathMap = $this->permissionService->buildPagePathMap($language);
                $items = $this->filterNavigationByShareScope($items, $shareScopeRelative, $pagePathMap);
            }

            // Simplify: only title + children (2 levels deep max)
            $result = [];
            foreach ($items as $item) {
                $navItem = ['title' => $item['title'] ?? ''];
                if (!empty($item['children'])) {
                    $children = [];
                    foreach ($item['children'] as $child) {
                        $children[] = ['title' => $child['title'] ?? ''];
                    }
                    $navItem['children'] = $children;
                }
                $result[] = $navItem;
            }

            return $result;
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Build a Nextcloud Files app URL that opens the sharing details sidebar.
     * Format: /apps/files/files/{fileid}?dir=/parent&opendetails=true
     *
     * Uses the user-mounted GroupFolder view to get the correct file ID,
     * since the internal GroupFolder storage has different IDs.
     */
    private function buildFilesUrl(string $absolutePath, ?int $fileId = null, ?string $userId = null): string {
        $groupFolderName = $this->setupService->getGroupFolderName();
        $rawRelPath = $this->getRelativePathRaw($absolutePath);
        $parentPath = dirname($rawRelPath);
        $dirPath = '/' . $groupFolderName . ($parentPath !== '.' ? '/' . $parentPath : '');

        // Try to get the user-visible file ID via the mounted GroupFolder
        $userFileId = null;
        if ($userId !== null) {
            try {
                $userFolder = $this->rootFolder->getUserFolder($userId);
                $mountedPath = $groupFolderName . '/' . $rawRelPath;
                if ($userFolder->nodeExists($mountedPath)) {
                    $node = $userFolder->get($mountedPath);
                    $userFileId = $node->getId();
                }
            } catch (\Exception $e) {
                // Fall through to URL without fileid
            }
        }

        if ($userFileId !== null) {
            return '/apps/files/files/' . $userFileId . '?dir=' . $dirPath . '&opendetails=true';
        }

        return '/apps/files/?dir=' . $dirPath;
    }

    /**
     * Get the raw relative path within IntraVox folder (without "IntraVox" prefix).
     */
    private function getRelativePathRaw(string $absolutePath): string {
        try {
            $folder = $this->setupService->getSharedFolder();
            if ($folder === null) {
                return $absolutePath;
            }
            $intraVoxPath = $folder->getPath();
            if (str_starts_with($absolutePath, $intraVoxPath)) {
                return ltrim(substr($absolutePath, strlen($intraVoxPath)), '/');
            }
        } catch (\Exception $e) {
            // Ignore
        }
        return $absolutePath;
    }

    /**
     * Check if a folder path is a language-root (e.g. just "en" or "nl").
     */
    private function isLanguageRoot(string $folderPath): bool {
        $relativePath = $this->getRelativePathRaw($folderPath);
        $parts = explode('/', ltrim($relativePath, '/'));
        return count($parts) === 1;
    }

    public function filterNavigationByShareScope(array $items, string $shareScopeRelative, array $pagePathMap): array {
        $shareScopeNormalized = $this->normalizePath(rtrim($shareScopeRelative, '/'));
        $filtered = [];

        foreach ($items as $item) {
            $includeItem = true;

            if (!empty($item['uniqueId'])) {
                $pagePath = $pagePathMap[$item['uniqueId']] ?? null;
                if ($pagePath !== null) {
                    // pagePathMap values are like "nl/afdeling" (language + folder path)
                    // Strip the language prefix to get just the folder path: "afdeling"
                    $parts = explode('/', $pagePath, 2);
                    $pagePathWithinLang = $this->normalizePath($parts[1] ?? '');

                    if ($shareScopeNormalized === '') {
                        // Share covers entire language folder — include everything
                        $includeItem = true;
                    } else {
                        // Check exact match (page is in the shared folder itself)
                        $isExactMatch = ($pagePathWithinLang === $shareScopeNormalized);

                        // Check if page is within the share scope (share folder is parent)
                        $isInScope = str_starts_with($pagePathWithinLang, $shareScopeNormalized . '/');

                        if (!$isExactMatch && !$isInScope) {
                            $includeItem = false;
                        }
                    }
                } else {
                    // Page not found in path map - hide in public context
                    $includeItem = false;
                }
            }
            // Items with only an external URL (no uniqueId) are always shown

            if (!$includeItem) {
                continue;
            }

            $filteredItem = $item;

            // Recursively filter children
            if (isset($item['children']) && is_array($item['children'])) {
                $filteredItem['children'] = $this->filterNavigationByShareScope(
                    $item['children'], $shareScopeRelative, $pagePathMap
                );

                // Parent without its own link and no visible children -> hide
                if (empty($item['uniqueId']) && empty($item['url']) && empty($filteredItem['children'])) {
                    continue;
                }
            }

            $filtered[] = $filteredItem;
        }

        return $filtered;
    }

    /**
     * Get all active NC link shares on files/folders within the IntraVox GroupFolder.
     * Used by the admin settings panel to show an overview of public shares.
     *
     * @return array List of share info objects
     */
    public function getActiveShares(): array {
        try {
            $groupfolderId = $this->setupService->getGroupFolderId();
            $groupFolderName = $this->setupService->getGroupFolderName();

            // Step 1: Get the GF storage ID
            $qbStorage = $this->db->getQueryBuilder();
            $qbStorage->select('storage_id')
                ->from('group_folders')
                ->where($qbStorage->expr()->eq('folder_id', $qbStorage->createNamedParameter($groupfolderId, IQueryBuilder::PARAM_INT)));

            $storageResult = $qbStorage->executeQuery();
            $storageRow = $storageResult->fetch();
            $storageResult->closeCursor();

            if (!$storageRow) {
                return [];
            }

            $gfStorageId = (int)$storageRow['storage_id'];

            // Step 2: Find all link shares whose file_source exists on the GF storage
            // Join share table with filecache to get the path
            $qb = $this->db->getQueryBuilder();
            $qb->select('s.id', 's.token', 's.file_source', 's.file_target', 's.uid_owner', 's.expiration', 's.stime', 'fc.path')
               ->from('share', 's')
               ->innerJoin('s', 'filecache', 'fc',
                   $qb->expr()->eq('s.file_source', 'fc.fileid'))
               ->where($qb->expr()->eq('s.share_type', $qb->createNamedParameter(IShare::TYPE_LINK, IQueryBuilder::PARAM_INT)))
               ->andWhere($qb->expr()->eq('fc.storage', $qb->createNamedParameter($gfStorageId, IQueryBuilder::PARAM_INT)))
               ->orderBy('s.stime', 'DESC');

            $result = $qb->executeQuery();
            $rows = $result->fetchAll();
            $result->closeCursor();

            $shares = [];
            $folder = $this->setupService->getSharedFolder();

            foreach ($rows as $row) {
                // Path on GF storage is like "files/nl/afdeling" or "files/nl/afdeling/page.json"
                $gfPath = $row['path'] ?? '';
                if (!str_starts_with($gfPath, 'files/')) {
                    continue;
                }

                $relativePath = substr($gfPath, 6); // Strip "files/"
                $isFile = str_ends_with($relativePath, '.json');
                $isExpired = false;

                if (!empty($row['expiration'])) {
                    $expiration = new \DateTime($row['expiration']);
                    if ($expiration < new \DateTime()) {
                        $isExpired = true;
                    }
                }

                // Determine scope and label
                $parts = explode('/', $relativePath);
                $language = $parts[0] ?? '';
                $scope = 'folder';
                $scopeLabel = '';

                if ($isFile) {
                    $scope = 'page';
                    // Try to read the page title from the JSON file
                    $scopeLabel = basename($relativePath, '.json');
                    if ($folder !== null) {
                        try {
                            if ($folder->nodeExists($relativePath)) {
                                $fileNode = $folder->get($relativePath);
                                $data = json_decode($fileNode->getContent(), true);
                                if (!empty($data['title'])) {
                                    $scopeLabel = $data['title'];
                                }
                            }
                        } catch (\Exception $e) {
                            // Use fallback label
                        }
                    }
                } elseif (count($parts) === 1) {
                    $scope = 'language';
                    $scopeLabel = strtoupper($language);
                } else {
                    // Folder share — read page title from {foldername}/{foldername}.json
                    $folderName = basename($relativePath);
                    $scopeLabel = ucfirst(str_replace('-', ' ', $folderName));
                    if ($folder !== null) {
                        try {
                            $jsonPath = $relativePath . '/' . $folderName . '.json';
                            if ($folder->nodeExists($jsonPath)) {
                                $jsonFile = $folder->get($jsonPath);
                                $data = json_decode($jsonFile->getContent(), true);
                                if (!empty($data['title'])) {
                                    $scopeLabel = $data['title'];
                                }
                            }
                        } catch (\Exception $e) {
                            // Use fallback label
                        }
                    }
                }

                $shares[] = [
                    'token' => $row['token'],
                    'scope' => $scope,
                    'scopeLabel' => $scopeLabel,
                    'language' => $language,
                    'path' => $relativePath,
                    'owner' => $row['uid_owner'] ?? '',
                    'expired' => $isExpired,
                    'expirationDate' => $row['expiration'] ?? null,
                    'createdAt' => isset($row['stime']) ? date('Y-m-d H:i', (int)$row['stime']) : null,
                    'filesUrl' => '/apps/files/?dir=/' . $groupFolderName . '/' . dirname($relativePath),
                    'publicUrl' => '/apps/intravox/s/' . $row['token'],
                ];
            }

            return $shares;
        } catch (\Exception $e) {
            $this->logger->error('[PublicShareService] Error getting active shares', [
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }
}
