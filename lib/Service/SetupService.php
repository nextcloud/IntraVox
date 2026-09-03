<?php
declare(strict_types=1);

namespace OCA\IntraVox\Service;

use OCP\DB\Exception as DBException;
use OCP\Files\IRootFolder;
use OCP\Files\NotFoundException;
use OCP\Files\Folder;
use OCP\IConfig;
use OCP\IUserSession;
use OCP\Share\IManager as IShareManager;
use OCP\Share\IShare;
use OCP\IGroupManager;
use OCA\IntraVox\Service\GroupFolders\GroupFoldersGateway;
use OCP\App\IAppManager;
use Psr\Log\LoggerInterface;
use Symfony\Component\Process\Process;

class SetupService {
    private const GROUPFOLDER_NAME = 'IntraVox';
    private const ADMIN_GROUP = 'IntraVox Admins';
    private const EDITOR_GROUP = 'IntraVox Editors';
    private const USER_GROUP = 'IntraVox Users';
    private const DEFAULT_LANGUAGE = 'en';

    private IRootFolder $rootFolder;
    private IConfig $config;
    private LoggerInterface $logger;
    private IUserSession $userSession;
    private IShareManager $shareManager;
    private IGroupManager $groupManager;
    private LanguageService $languageService;
    private IAppManager $appManager;

    /**
     * Per-request memo of groupfolder ids by mount point name.
     * array_key_exists, not isset: "no such folder" must be cached too.
     *
     * @var array<string, int|null>
     */
    private array $groupFolderIdCache = [];
    private GroupFoldersGateway $groupFolders;

    public function __construct(
        IRootFolder $rootFolder,
        IConfig $config,
        LoggerInterface $logger,
        IUserSession $userSession,
        IShareManager $shareManager,
        IGroupManager $groupManager,
        LanguageService $languageService,
        IAppManager $appManager,
        ?GroupFoldersGateway $groupFolders = null
    ) {
        $this->rootFolder = $rootFolder;
        $this->config = $config;
        $this->logger = $logger;
        $this->userSession = $userSession;
        $this->shareManager = $shareManager;
        $this->groupManager = $groupManager;
        $this->languageService = $languageService;
        // Optional so the many manual constructions in tests and occ keep working;
        // built on demand from the same dependencies when absent.
        $this->groupFolders = $groupFolders ?? new GroupFoldersGateway($appManager, $logger);
        $this->appManager = $appManager;
    }

    /**
     * Detect the default language based on Nextcloud system configuration.
     * Returns 'nl' if the system language is Dutch, otherwise 'en'.
     */
    public function detectDefaultLanguage(): string {
        $systemLanguage = $this->config->getSystemValue('default_language', 'en');
        $langCode = substr($systemLanguage, 0, 2);

        if ($langCode === 'nl') {
            return 'nl';
        }

        return 'en';
    }

    /**
     * Check if the GroupFolders app is installed and enabled
     */
    public function isGroupFoldersAppEnabled(): bool {
        return $this->appManager->isEnabledForUser('groupfolders');
    }

    /**
     * Setup IntraVox groupfolder
     * @return array{success: bool, error?: string} Returns success status and optional error key for translation
     */
    public function setupSharedFolder(): array {
        try {
            $this->logger->info('=== STEP 1: Starting IntraVox groupfolder setup ===');

            // Check if GroupFolders app is enabled first
            if (!$this->isGroupFoldersAppEnabled()) {
                $this->logger->error('GroupFolders app is not installed or enabled');
                return [
                    'success' => false,
                    'error' => 'groupfolders_app_not_enabled',
                ];
            }

            // First, ensure the required groups exist
            $this->logger->info('=== STEP 2: Ensuring groups exist ===');
            $this->ensureGroupsExist();
            $this->logger->info('=== STEP 2: Groups exist check completed ===');

            // Create or get groupfolder
            $this->logger->info('=== STEP 3: Creating or getting groupfolder ===');
            $folderId = $this->createOrGetGroupfolder();
            $this->logger->info("=== STEP 3: Groupfolder ID obtained: {$folderId} ===");

            if ($folderId === null) {
                $this->logger->error('Failed to create or get groupfolder');
                return [
                    'success' => false,
                    'error' => 'groupfolder_creation_failed',
                ];
            }

            // Configure groupfolder permissions
            $this->logger->info('=== STEP 4: Configuring groupfolder permissions ===');
            $this->configureGroupfolderPermissions($folderId);
            $this->logger->info('=== STEP 4: Permissions configured ===');

            // Get the folder object
            $this->logger->info('=== STEP 5: Getting folder object ===');
            $folder = $this->getGroupfolderObject($folderId);
            $this->logger->info('=== STEP 5: Folder object obtained ===');

            if ($folder === null) {
                $this->logger->error('Failed to access groupfolder');
                return [
                    'success' => false,
                    'error' => 'groupfolder_access_failed',
                ];
            }

            // Create default content
            $this->logger->info('=== STEP 6: Creating default content ===');
            $this->createDefaultContent($folder);
            $this->logger->info('=== STEP 6: Default content created ===');

            // Scan folder to update file cache asynchronously
            $this->logger->info('=== STEP 7: Starting async folder scan ===');
            $this->scanFolder($folderId);
            $this->logger->info('=== STEP 7: Async scan initiated ===');

            $this->logger->info('=== SETUP COMPLETE: IntraVox groupfolder setup completed successfully ===');
            return ['success' => true];

        } catch (\Exception $e) {
            $this->logger->error('=== SETUP FAILED: Exception caught ===');
            $this->logger->error('Failed to setup IntraVox groupfolder: ' . $e->getMessage());
            $this->logger->error('Stack trace: ' . $e->getTraceAsString());
            return [
                'success' => false,
                'error' => 'setup_exception',
            ];
        }
    }

    /**
     * Ensure required groups exist and add current user to admin group
     */
    private function ensureGroupsExist(): void {
        foreach ([self::ADMIN_GROUP, self::EDITOR_GROUP, self::USER_GROUP] as $groupId) {
            $this->logger->info("Checking if group exists: {$groupId}");
            if (!$this->groupManager->groupExists($groupId)) {
                $this->logger->info("Group does not exist, creating: {$groupId}");
                $this->groupManager->createGroup($groupId);
                $this->logger->info("Created group: {$groupId}");
            } else {
                $this->logger->info("Group already exists: {$groupId}");
            }
        }

        // Sync all Nextcloud admins to IntraVox Admins group
        // This ensures all NC admins get IntraVox admin rights, regardless of who installs the app
        $ncAdminGroup = $this->groupManager->get('admin');
        $adminGroup = $this->groupManager->get(self::ADMIN_GROUP);
        if ($ncAdminGroup !== null && $adminGroup !== null) {
            foreach ($ncAdminGroup->getUsers() as $ncAdmin) {
                if (!$adminGroup->inGroup($ncAdmin)) {
                    $adminGroup->addUser($ncAdmin);
                    $this->logger->info("Synced NC admin '{$ncAdmin->getUID()}' to " . self::ADMIN_GROUP . " group");
                }
            }
        }
    }

    /**
     * Create or get existing groupfolder
     */
    private function createOrGetGroupfolder(): ?int {
        return $this->createOrGetGroupfolderByName(self::GROUPFOLDER_NAME);
    }

    /**
     * Configure groupfolder permissions for groups
     * Idempotent: safe to run multiple times (on install and updates)
     */
    private function configureGroupfolderPermissions(int $folderId): void {
        try {
            $this->logger->info("Getting FolderManager for permissions configuration...");
            // Setup-only group wiring: addApplicableGroup/setGroupPermissions have
            // no gateway wrapper because nothing else calls them. Reached through
            // the gateway's escape hatch so there is still exactly one place that
            // resolves FolderManager (GFG-0).
            $groupfolderManager = $this->groupFolders->folderManager();

            // Define groups to configure
            $groupsToAdd = [
                ['name' => 'admin', 'permissions' => \OCP\Constants::PERMISSION_ALL],
                ['name' => self::ADMIN_GROUP, 'permissions' => \OCP\Constants::PERMISSION_ALL],
                ['name' => self::EDITOR_GROUP, 'permissions' => \OCP\Constants::PERMISSION_READ | \OCP\Constants::PERMISSION_UPDATE | \OCP\Constants::PERMISSION_CREATE],
                ['name' => self::USER_GROUP, 'permissions' => \OCP\Constants::PERMISSION_READ],
            ];

            foreach ($groupsToAdd as $groupConfig) {
                $groupName = $groupConfig['name'];
                $permissions = $groupConfig['permissions'];

                // Try to add group - ignore duplicate entry errors (idempotent)
                try {
                    $this->logger->info("Adding group '{$groupName}' to groupfolder {$folderId}...");
                    $groupfolderManager->addApplicableGroup($folderId, $groupName);
                    $this->logger->info("Group '{$groupName}' added successfully");
                } catch (DBException $e) {
                    if ($e->getReason() === DBException::REASON_UNIQUE_CONSTRAINT_VIOLATION) {
                        // Group already exists - this is expected on updates
                        $this->logger->info("Group '{$groupName}' already exists in groupfolder (expected on updates)");
                    } else {
                        // Re-throw other exceptions
                        throw $e;
                    }
                }

                // Always set permissions to ensure they're correct (even on updates)
                $this->logger->info("Setting permissions for '{$groupName}'...");
                $groupfolderManager->setGroupPermissions($folderId, $groupName, $permissions);

                $permissionType = ($permissions === \OCP\Constants::PERMISSION_ALL) ? 'full' : 'read';
                $this->logger->info("Granted {$permissionType} permissions to: {$groupName}");
            }

        } catch (\Exception $e) {
            $this->logger->error('Exception in configureGroupfolderPermissions: ' . $e->getMessage());
            $this->logger->error('Stack trace: ' . $e->getTraceAsString());
        }
    }

    /**
     * Get the groupfolder's `files` Folder object.
     *
     * Preferred: resolve through a member's MOUNTED view — the same mechanism the
     * whole runtime uses (PageService::getIntraVoxFolder(), Navigation/Footer/
     * HomepageService). This is storage-backend agnostic, so it works with primary
     * object storage, where the internal `/__groupfolders/{id}/files` path is not a
     * resolvable node on the root view (issue #71). The returned node's getPath()
     * NOTE: the returned node's getPath() is the MOUNT path
     * (`/<uid>/files/<mountPoint>/...`), NOT the internal
     * `/__groupfolders/{id}/files/...` path. An earlier version of this comment
     * claimed the opposite; verified false against the live groupfolder on dev,
     * where getSharedFolder()->getPath() returns `/Femke/files/IntraVox`. Only
     * the raw fallback below yields the internal path. Path-parsing callers
     * must therefore not assume `__groupfolders` appears in the path — see
     * tests/Integration/GroupFolderResolutionTest.
     *
     * Fallback: the legacy raw storage walk. It works on LOCAL primary storage and
     * is kept so no currently-working install can regress.
     *
     * @return \OCP\Files\Folder|null The groupfolder `files` folder, or null.
     */
    private function getGroupfolderObject(int $folderId) {
        // Preferred: mounted view of a member user (object-storage safe).
        $uid = $this->resolveGroupfolderMemberUser();
        if ($uid !== null) {
            try {
                $userFolder = $this->rootFolder->getUserFolder($uid);
                $node = $userFolder->get(self::GROUPFOLDER_NAME);
                if ($node instanceof Folder) {
                    $this->logger->info("Resolved IntraVox groupfolder via mounted view of user '{$uid}'");
                    return $node;
                }
                $this->logger->warning("Mounted '" . self::GROUPFOLDER_NAME . "' node for user '{$uid}' is not a folder; falling back to raw path");
            } catch (\Exception $e) {
                $this->logger->warning("Could not resolve groupfolder via user '{$uid}': " . $e->getMessage() . ' — falling back to raw path');
            }
        } else {
            $this->logger->warning('No member user available to mount the IntraVox groupfolder; falling back to raw path');
        }

        // Fallback: legacy raw storage walk (__groupfolders/<id>/files). Works on
        // local primary storage; preserves existing behaviour there.
        try {
            $groupfoldersRoot = $this->rootFolder->get('__groupfolders');
            $groupfolderMeta = $groupfoldersRoot->get((string)$folderId);
            $folder = $groupfolderMeta->get('files');
            $this->logger->info('Resolved IntraVox groupfolder via raw __groupfolders path (fallback)');
            return $folder;
        } catch (\Exception $e) {
            $this->logger->error('Failed to get groupfolder object (mount unavailable and raw path not accessible): ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Pick a user who is a member of the IntraVox groupfolder, so its mount exists
     * in that user's filesystem view. Prefers a WRITE member (Admins/Editors) since
     * setup and demo import need to write.
     *   1. The current session user, if it is a member (web-UI demo import).
     *   2. Any enabled member of 'IntraVox Admins', then 'IntraVox Editors' (OCC /
     *      repair-step context, where there is no session user).
     * Returns null if none can be found (caller falls back to the raw path).
     *
     * `protected` only to give unit tests a seam; no runtime behaviour depends on it.
     */
    protected function resolveGroupfolderMemberUser(): ?string {
        // 1. Session user (web request) — must be a member so the mount is present.
        $sessionUser = $this->userSession->getUser();
        if ($sessionUser !== null && $this->isGroupfolderMember($sessionUser->getUID())) {
            return $sessionUser->getUID();
        }

        // 2. First available enabled member of the write-capable groups (OCC).
        foreach ([self::ADMIN_GROUP, self::EDITOR_GROUP] as $groupId) {
            $group = $this->groupManager->get($groupId);
            if ($group === null) {
                continue;
            }
            foreach ($group->getUsers() as $member) {
                if ($member->isEnabled()) {
                    return $member->getUID();
                }
            }
        }
        return null;
    }

    /**
     * Whether a user belongs to any IntraVox groupfolder group (Admins/Editors/Users).
     * These app groups are exactly the folder's applicable groups (bound in
     * configureGroupfolderPermissions()), so membership here means the mount exists.
     *
     * `protected` only to give unit tests a seam.
     */
    protected function isGroupfolderMember(string $uid): bool {
        foreach ([self::ADMIN_GROUP, self::EDITOR_GROUP, self::USER_GROUP] as $groupId) {
            if ($this->groupManager->isInGroup($uid, $groupId)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Get the IntraVox groupfolder
     */
    public function getSharedFolder() {
        return $this->getSharedFolderByName(self::GROUPFOLDER_NAME);
    }

    /**
     * Resolve a groupfolder id by mount point name, once per request.
     *
     * getAllFolders() is three unbounded queries plus an object per row, and
     * getSharedFolder() has 41 call sites across controllers and services —
     * several of them on the page-render path. Walking every groupfolder on
     * the instance that many times per request is the enterprise blocker; on
     * an instance with thousands of team folders it dominates page load.
     *
     * Only the id lookup is memoised. The Folder node it resolves to is
     * deliberately NOT cached: that object is user-view dependent, and this
     * service is used both from request scope and from occ.
     *
     * @return int|null Highest matching folder id, or null if there is none.
     */
    private function groupFolderIdByName(string $folderName): ?int {
        if (array_key_exists($folderName, $this->groupFolderIdCache)) {
            return $this->groupFolderIdCache[$folderName];
        }

        // The walk itself lives in GroupFoldersGateway (SE-1). This local cache
        // stays because callers here also memoise "not found" between setup steps.
        $folderId = $this->groupFolders->findFolderIdByMountPoint($folderName);

        $this->groupFolderIdCache[$folderName] = $folderId;

        return $folderId;
    }

    /**
     * Get a groupfolder by name (generic)
     */
    private function getSharedFolderByName(string $folderName) {
        try {
            $folderId = $this->groupFolderIdByName($folderName);

            if ($folderId === null) {
                throw new \Exception("Groupfolder '{$folderName}' not found.");
            }

            // Note: getGroupfolderObject() resolves primarily via the member's
            // mounted view (get by MOUNT-POINT NAME == the groupfolder name). In the
            // pathological case of two groupfolders both named 'IntraVox', the mounted
            // view may resolve a different one than the highest-id chosen above. We
            // intentionally prefer the mounted view, because every runtime page/nav/
            // footer operation resolves the same way — keeping setup consistent with
            // reads. $folderId is still used by the raw-path fallback.
            $result = $this->getGroupfolderObject($folderId);
            if ($result === null) {
                throw new \Exception("Groupfolder '{$folderName}' not accessible.");
            }
            return $result;

        } catch (\Exception $e) {
            throw new \Exception("Groupfolder '{$folderName}' not accessible: " . $e->getMessage());
        }
    }

    /**
     * Create default content in the IntraVox folder
     *
     * @param Folder $folder The IntraVox groupfolder
     * @param string|null $language If provided, only create content for this language. If null, uses detected default language.
     */
    private function createDefaultContent($folder, ?string $language = null): void {
        try {
            $languages = $language !== null ? [$language] : [$this->detectDefaultLanguage()];

            foreach ($languages as $lang) {
                try {
                    $langFolder = $folder->get($lang);
                    $this->logger->info("Language folder '{$lang}' already exists");
                } catch (NotFoundException $e) {
                    $langFolder = $folder->newFolder($lang);
                    $this->logger->info("Created language folder: {$lang}");
                }

                // Create default homepage for each language
                try {
                    $langFolder->get('home.json');
                    $this->logger->info("home.json already exists in {$lang}, skipping");
                } catch (NotFoundException $e) {
                    $this->createDefaultHomePageViaAPI($langFolder, $lang);
                }

                // Create _resources folder for shared media
                try {
                    $langFolder->get('_resources');
                    $this->logger->info("_resources folder already exists in {$lang}");
                } catch (NotFoundException $e) {
                    $langFolder->newFolder('_resources');
                    $this->logger->info("Created _resources folder in {$lang}");
                }

                // Create _templates folder for page templates
                try {
                    $langFolder->get('_templates');
                    $this->logger->info("_templates folder already exists in {$lang}");
                } catch (NotFoundException $e) {
                    $langFolder->newFolder('_templates');
                    $this->logger->info("Created _templates folder in {$lang}");
                }
            }

            $this->logger->info('Created default content in IntraVox folder');
        } catch (\Exception $e) {
            $this->logger->warning('Could not create default content: ' . $e->getMessage());
            // Non-fatal, continue anyway
        }
    }

    /**
     * Create default homepage via Nextcloud Files API
     */
    private function createDefaultHomePageViaAPI($folder, string $lang): void {
        $content = $this->getDefaultHomePageContent($lang);

        // Create file via Nextcloud Files API to ensure filecache is updated
        $file = $folder->newFile('home.json');
        $file->putContent(json_encode($content, JSON_PRETTY_PRINT));
        $this->logger->info("Created default homepage for language: {$lang}");
    }

    /**
     * Get default homepage content for a specific language
     */
    private function getDefaultHomePageContent(string $lang): array {
        $translations = [
            'nl' => [
                'title' => 'Welkom bij IntraVox',
                'heading' => 'Welkom bij IntraVox',
                'intro' => 'Dit is je organisatie intranet. Deze folder is een groupfolder waar admins standaard toegang toe hebben. Je kunt via Groepsmappen beheer andere groepen toegang geven.',
                'getting_started' => 'Aan de slag',
                'instructions' => '1. Klik op "Bewerken" om deze pagina aan te passen\n2. Klik op "+ Nieuwe Pagina" om meer pagina\'s toe te voegen\n3. Gebruik widgets om content toe te voegen\n4. Sleep widgets om je layout aan te passen\n\nDeze folder is niet gekoppeld aan een gebruikersaccount maar is een echte systeemfolder!'
            ],
            'en' => [
                'title' => 'Welcome to IntraVox',
                'heading' => 'Welcome to IntraVox',
                'intro' => 'This is your organization intranet. This folder is a group folder where admins have access by default. You can grant access to other groups via Group folders management.',
                'getting_started' => 'Getting Started',
                'instructions' => '1. Click "Edit" to modify this page\n2. Click "+ New Page" to add more pages\n3. Use widgets to add content\n4. Drag widgets to adjust your layout\n\nThis folder is not linked to a user account but is a real system folder!'
            ],
            'de' => [
                'title' => 'Willkommen bei IntraVox',
                'heading' => 'Willkommen bei IntraVox',
                'intro' => 'Dies ist Ihr Organisations-Intranet. Dieser Ordner ist ein Gruppenordner, auf den Administratoren standardmäßig Zugriff haben. Sie können anderen Gruppen über die Gruppenordnerverwaltung Zugriff gewähren.',
                'getting_started' => 'Erste Schritte',
                'instructions' => '1. Klicken Sie auf "Bearbeiten", um diese Seite anzupassen\n2. Klicken Sie auf "+ Neue Seite", um weitere Seiten hinzuzufügen\n3. Verwenden Sie Widgets, um Inhalte hinzuzufügen\n4. Ziehen Sie Widgets, um Ihr Layout anzupassen\n\nDieser Ordner ist nicht mit einem Benutzerkonto verknüpft, sondern ein echter Systemordner!'
            ],
            'fr' => [
                'title' => 'Bienvenue sur IntraVox',
                'heading' => 'Bienvenue sur IntraVox',
                'intro' => 'Ceci est l\'intranet de votre organisation. Ce dossier est un dossier de groupe auquel les administrateurs ont accès par défaut. Vous pouvez accorder l\'accès à d\'autres groupes via la gestion des dossiers de groupe.',
                'getting_started' => 'Pour commencer',
                'instructions' => '1. Cliquez sur "Modifier" pour modifier cette page\n2. Cliquez sur "+ Nouvelle page" pour ajouter plus de pages\n3. Utilisez des widgets pour ajouter du contenu\n4. Faites glisser les widgets pour ajuster votre mise en page\n\nCe dossier n\'est pas lié à un compte utilisateur mais est un véritable dossier système!'
            ]
        ];

        $t = $translations[$lang] ?? $translations[self::DEFAULT_LANGUAGE];

        return [
            'id' => 'home',
            'title' => $t['title'],
            'layout' => [
                'columns' => 2,
                'rows' => [
                    [
                        'widgets' => [
                            [
                                'type' => 'heading',
                                'content' => $t['heading'],
                                'level' => 1,
                                'column' => 1,
                                'order' => 1
                            ],
                            [
                                'type' => 'text',
                                'content' => $t['intro'],
                                'column' => 1,
                                'order' => 2
                            ],
                            [
                                'type' => 'heading',
                                'content' => $t['getting_started'],
                                'level' => 2,
                                'column' => 2,
                                'order' => 1
                            ],
                            [
                                'type' => 'text',
                                'content' => $t['instructions'],
                                'column' => 2,
                                'order' => 2
                            ]
                        ]
                    ]
                ]
            ],
            'created' => time(),
            'modified' => time()
        ];
    }

    /**
     * Scan folder to update file cache asynchronously
     */
    private function scanFolder(int $folderId): void {
        try {
            $this->logger->info('Starting async scan for folder', ['folderId' => $folderId]);

            // Get the Nextcloud root directory from config
            $ncRoot = \OC::$SERVERROOT;

            // Build the command without sudo since Apache already runs as www-data
            $command = sprintf(
                'php %s/occ groupfolders:scan %d > /dev/null 2>&1 &',
                escapeshellarg($ncRoot),
                $folderId
            );

            $this->logger->debug('Executing async scan command', [
                'command' => $command,
                'folderId' => $folderId,
            ]);

            // Use proc_open for better background process handling
            $descriptorspec = [
                0 => ['pipe', 'r'],  // stdin
                1 => ['pipe', 'w'],  // stdout
                2 => ['pipe', 'w'],  // stderr
            ];

            $process = proc_open($command, $descriptorspec, $pipes);

            if (is_resource($process)) {
                // Close pipes immediately and don't wait for process
                fclose($pipes[0]);
                fclose($pipes[1]);
                fclose($pipes[2]);

                // Don't wait for the process to finish
                proc_close($process);

                $this->logger->info('Async scan process started for folder', ['folderId' => $folderId]);
            } else {
                $this->logger->error('Failed to start scan process', ['folderId' => $folderId]);
            }
        } catch (\Exception $e) {
            $this->logger->error('Failed to start async scan', [
                'folderId' => $folderId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Synchronously rescan the IntraVox groupfolder so file-cache changes (a
     * just-added or just-removed language folder) are reflected in every user's
     * mounted view immediately. Unlike the async scanFolder(), this waits for
     * the scan to finish. Best-effort: logs and returns on any failure.
     *
     * A storage-level scan from a user's jailed mount does NOT reliably update
     * the groupfolder mount cache, so we run the same `groupfolders:scan` the
     * demo-data import relies on.
     */
    public function rescanGroupfolderSync(): void {
        try {
            $folderId = $this->getGroupFolderId();
            $command = sprintf(
                'php %s/occ groupfolders:scan %d 2>&1',
                escapeshellarg(\OC::$SERVERROOT),
                $folderId
            );
            exec($command, $output, $returnCode);
            if ($returnCode !== 0) {
                $this->logger->warning('[SetupService] sync groupfolder scan returned non-zero', [
                    'exit' => $returnCode,
                    'output' => implode("\n", $output ?? []),
                ]);
            }
        } catch (\Throwable $e) {
            $this->logger->warning('[SetupService] sync groupfolder scan failed: ' . $e->getMessage());
        }
    }

    /**
     * Get the IntraVox groupfolder ID
     * @throws \Exception if groupfolder not found
     */
    public function getGroupFolderId(): int {
        if (!$this->groupFolders->isAvailable()) {
            throw new \Exception('Failed to get groupfolder ID: Groupfolders app is not enabled');
        }

        // Same resolution as everywhere else, through the one chokepoint (SE-1).
        $folderId = $this->groupFolders->findFolderIdByMountPoint(self::GROUPFOLDER_NAME);

        if ($folderId === null) {
            throw new \Exception('Failed to get groupfolder ID: IntraVox groupfolder not found');
        }

        return $folderId;
    }

    /**
     * Get the IntraVox groupfolder name
     */
    public function getGroupFolderName(): string {
        return self::GROUPFOLDER_NAME;
    }

    /**
     * Get a specific language folder within the IntraVox groupfolder
     *
     * @param string $language Language code (e.g., 'en', 'nl', 'de')
     * @return \OCP\Files\Folder The language folder
     * @throws \OCP\Files\NotFoundException If the language folder doesn't exist
     */
    public function getLanguageFolder(string $language): \OCP\Files\Folder {
        $sharedFolder = $this->getSharedFolder();

        if (!$sharedFolder->nodeExists($language)) {
            throw new \OCP\Files\NotFoundException("Language folder '{$language}' not found");
        }

        $langFolder = $sharedFolder->get($language);
        if (!($langFolder instanceof \OCP\Files\Folder)) {
            throw new \OCP\Files\NotFoundException("Language path '{$language}' is not a folder");
        }

        return $langFolder;
    }

    /**
     * Check if setup is complete (GroupFolder exists and is accessible)
     */
    public function isSetupComplete(): bool {
        try {
            $this->getSharedFolder();
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Create or get a groupfolder by name (generic version)
     */
    private function createOrGetGroupfolderByName(string $folderName): ?int {
        try {
            if (!$this->groupFolders->isAvailable()) {
                $this->logger->error('Groupfolders app is not enabled');
                return null;
            }

            // Resolution through the one chokepoint (SE-1).
            $existingFolderId = $this->groupFolders->findFolderIdByMountPoint($folderName);

            if ($existingFolderId !== null) {
                $this->logger->info("Using existing groupfolder '{$folderName}' with ID: {$existingFolderId}");
                return $existingFolderId;
            }

            $folderId = $this->groupFolders->createFolder($folderName);

            // Setup runs in the same request that may already have looked this
            // name up and memoised "not found"; drop both caches so the folder we
            // just created is visible to getSharedFolder() below.
            unset($this->groupFolderIdCache[$folderName]);

            $this->logger->info("Created groupfolder '{$folderName}' with ID: " . var_export($folderId, true));
            return $folderId;
        } catch (\Exception $e) {
            $this->logger->error("Exception in createOrGetGroupfolderByName('{$folderName}'): " . $e->getMessage());
            return null;
        }
    }

    /**
     * Extract mount point from folder data (handles both object and array)
     */
    private function getMountPointFromFolderData($folderData): ?string {
        if (is_object($folderData)) {
            return property_exists($folderData, 'mountPoint') ? $folderData->mountPoint :
                   (method_exists($folderData, 'getMountPoint') ? $folderData->getMountPoint() : null);
        }
        return $folderData['mount_point'] ?? null;
    }

    /**
     * Migrate existing installations to add _resources folders
     * Idempotent: safe to run multiple times
     */
    public function migrateResourcesFolders(): bool {
        try {
            $this->logger->info('Starting _resources folder migration');

            // Get the IntraVox groupfolder
            $folder = $this->getSharedFolder();

            // Create _resources folder for each language
            // Iterate only over admin-enabled languages. The existing
            // `catch (NotFoundException) { continue; }` skips languages whose
            // folder never existed, so a 1.5.x install upgrades without any
            // new folders being silently created.
            foreach ($this->languageService->getEnabledLanguages() as $lang) {
                try {
                    $langFolder = $folder->get($lang);
                    $this->logger->info("Checking language folder: {$lang}");

                    // Check if _resources folder exists
                    try {
                        $langFolder->get('_resources');
                        $this->logger->info("_resources folder already exists in {$lang}");
                    } catch (NotFoundException $e) {
                        // Create _resources folder
                        $langFolder->newFolder('_resources');
                        $this->logger->info("Created _resources folder in {$lang}");
                    }
                } catch (NotFoundException $e) {
                    $this->logger->warning("Language folder {$lang} not found, skipping");
                    continue;
                }
            }

            $this->logger->info('_resources folder migration completed successfully');
            return true;

        } catch (\Exception $e) {
            $this->logger->error('_resources folder migration failed: ' . $e->getMessage());
            $this->logger->error('Stack trace: ' . $e->getTraceAsString());
            return false;
        }
    }

    /**
     * Migrate existing installations to add _templates folders
     * Idempotent: safe to run multiple times
     */
    public function migrateTemplatesFolders(): bool {
        try {
            $this->logger->info('Starting _templates folder migration');

            // Get the IntraVox groupfolder
            $folder = $this->getSharedFolder();

            // Create _templates folder for each language
            // Iterate only over admin-enabled languages. The existing
            // `catch (NotFoundException) { continue; }` skips languages whose
            // folder never existed, so a 1.5.x install upgrades without any
            // new folders being silently created.
            foreach ($this->languageService->getEnabledLanguages() as $lang) {
                try {
                    $langFolder = $folder->get($lang);
                    $this->logger->info("Checking language folder: {$lang}");

                    // Check if _templates folder exists
                    try {
                        $langFolder->get('_templates');
                        $this->logger->info("_templates folder already exists in {$lang}");
                    } catch (NotFoundException $e) {
                        // Create _templates folder
                        $langFolder->newFolder('_templates');
                        $this->logger->info("Created _templates folder in {$lang}");
                    }
                } catch (NotFoundException $e) {
                    $this->logger->warning("Language folder {$lang} not found, skipping");
                    continue;
                }
            }

            $this->logger->info('_templates folder migration completed successfully');
            return true;

        } catch (\Exception $e) {
            $this->logger->error('_templates folder migration failed: ' . $e->getMessage());
            $this->logger->error('Stack trace: ' . $e->getTraceAsString());
            return false;
        }
    }

    /**
     * Install default templates from demo-data/templates
     * Idempotent: skips templates that already exist
     *
     * @return array{success: bool, installed: int, skipped: int, error?: string}
     */
    public function installDefaultTemplates(): array {
        try {
            $this->logger->info('Starting default templates installation');

            $folder = $this->getSharedFolder();
            $appPath = $this->appManager->getAppPath('intravox');
            $templatesSourcePath = $appPath . '/demo-data/templates';

            if (!is_dir($templatesSourcePath)) {
                $this->logger->warning('Templates source folder not found: ' . $templatesSourcePath);
                return [
                    'success' => false,
                    'installed' => 0,
                    'skipped' => 0,
                    'error' => 'Templates source folder not found'
                ];
            }

            // Get list of template JSON files
            $templateFiles = glob($templatesSourcePath . '/*.json');
            if (empty($templateFiles)) {
                $this->logger->info('No template files found in source folder');
                return [
                    'success' => true,
                    'installed' => 0,
                    'skipped' => 0
                ];
            }

            $installed = 0;
            $skipped = 0;

            // Install templates only for languages that have a folder
            // Iterate only over admin-enabled languages. The existing
            // `catch (NotFoundException) { continue; }` skips languages whose
            // folder never existed, so a 1.5.x install upgrades without any
            // new folders being silently created.
            foreach ($this->languageService->getEnabledLanguages() as $lang) {
                try {
                    $langFolder = $folder->get($lang);

                    // Ensure _templates folder exists
                    try {
                        $templatesFolder = $langFolder->get('_templates');
                    } catch (NotFoundException $e) {
                        $templatesFolder = $langFolder->newFolder('_templates');
                        $this->logger->info("Created _templates folder in {$lang}");
                    }

                    // Install each template
                    foreach ($templateFiles as $templateFile) {
                        $templateId = basename($templateFile, '.json');

                        // Skip if template folder already exists
                        if ($templatesFolder->nodeExists($templateId)) {
                            $this->logger->debug("Template {$templateId} already exists in {$lang}, skipping");
                            $skipped++;
                            continue;
                        }

                        // Read and parse template JSON
                        $templateContent = file_get_contents($templateFile);
                        $templateData = json_decode($templateContent, true);

                        if (!$templateData) {
                            $this->logger->warning("Invalid JSON in template file: {$templateFile}");
                            continue;
                        }

                        // Update template metadata for this language
                        $templateData['language'] = $lang;
                        $templateData['uniqueId'] = 'template-' . $templateId . '-' . $lang;

                        // Create template folder
                        $templateFolder = $templatesFolder->newFolder($templateId);

                        // Create template JSON file
                        $templateFolder->newFile($templateId . '.json', json_encode($templateData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

                        // Copy _media folder if exists
                        $sourceMediaPath = $templatesSourcePath . '/_media';
                        if (is_dir($sourceMediaPath)) {
                            try {
                                $mediaFolder = $templateFolder->newFolder('_media');

                                // Get image references from template
                                $imageRefs = $this->extractImageReferencesFromTemplate($templateData);

                                // Copy only referenced images
                                foreach ($imageRefs as $imageName) {
                                    $sourceImage = $sourceMediaPath . '/' . $imageName;
                                    if (file_exists($sourceImage)) {
                                        $mediaFolder->newFile($imageName, file_get_contents($sourceImage));
                                    }
                                }
                            } catch (\Exception $e) {
                                $this->logger->warning("Failed to copy media for template {$templateId}: " . $e->getMessage());
                            }
                        }

                        $this->logger->info("Installed template {$templateId} in {$lang}");
                        $installed++;
                    }
                } catch (NotFoundException $e) {
                    $this->logger->warning("Language folder {$lang} not found, skipping");
                    continue;
                }
            }

            $this->logger->info("Default templates installation completed: {$installed} installed, {$skipped} skipped");
            return [
                'success' => true,
                'installed' => $installed,
                'skipped' => $skipped
            ];

        } catch (\Exception $e) {
            $this->logger->error('Default templates installation failed: ' . $e->getMessage());
            $this->logger->error('Stack trace: ' . $e->getTraceAsString());
            return [
                'success' => false,
                'installed' => 0,
                'skipped' => 0,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Extract image references from template data
     * Looks for 'src' properties in image widgets
     *
     * @param array $templateData
     * @return array List of image filenames
     */
    private function extractImageReferencesFromTemplate(array $templateData): array {
        $images = [];

        // Check layout rows
        if (isset($templateData['layout']['rows'])) {
            foreach ($templateData['layout']['rows'] as $row) {
                if (isset($row['widgets'])) {
                    foreach ($row['widgets'] as $widget) {
                        if (isset($widget['type']) && $widget['type'] === 'image' && isset($widget['src'])) {
                            $images[] = $widget['src'];
                        }
                    }
                }
            }
        }

        // Check side columns
        if (isset($templateData['layout']['sideColumns'])) {
            foreach ($templateData['layout']['sideColumns'] as $side) {
                if (isset($side['widgets'])) {
                    foreach ($side['widgets'] as $widget) {
                        if (isset($widget['type']) && $widget['type'] === 'image' && isset($widget['src'])) {
                            $images[] = $widget['src'];
                        }
                    }
                }
            }
        }

        return array_unique($images);
    }

}
