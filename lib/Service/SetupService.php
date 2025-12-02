<?php
declare(strict_types=1);

namespace OCA\IntraVox\Service;

use OCP\Files\IRootFolder;
use OCP\Files\NotFoundException;
use OCP\Files\Folder;
use OCP\IConfig;
use OCP\IUserSession;
use OCP\Share\IManager as IShareManager;
use OCP\Share\IShare;
use OCP\IGroupManager;
use Psr\Log\LoggerInterface;
use Symfony\Component\Process\Process;

class SetupService {
    private const GROUPFOLDER_NAME = 'IntraVox';
    private const ADMIN_GROUP = 'IntraVox Admins';
    private const USER_GROUP = 'IntraVox Users';
    private const SUPPORTED_LANGUAGES = ['nl', 'en', 'de', 'fr'];
    private const DEFAULT_LANGUAGE = 'en';

    private IRootFolder $rootFolder;
    private IConfig $config;
    private LoggerInterface $logger;
    private IUserSession $userSession;
    private IShareManager $shareManager;
    private IGroupManager $groupManager;
    private string $nextcloudPath;

    public function __construct(
        IRootFolder $rootFolder,
        IConfig $config,
        LoggerInterface $logger,
        IUserSession $userSession,
        IShareManager $shareManager,
        IGroupManager $groupManager
    ) {
        $this->rootFolder = $rootFolder;
        $this->config = $config;
        $this->logger = $logger;
        $this->userSession = $userSession;
        $this->shareManager = $shareManager;
        $this->groupManager = $groupManager;

        // Get Nextcloud path from environment or use default
        $this->nextcloudPath = '/var/www/nextcloud';
    }

    /**
     * Setup IntraVox groupfolder
     */
    public function setupSharedFolder(): bool {
        try {
            $this->logger->info('=== STEP 1: Starting IntraVox groupfolder setup ===');

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
                return false;
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
                return false;
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
            return true;

        } catch (\Exception $e) {
            $this->logger->error('=== SETUP FAILED: Exception caught ===');
            $this->logger->error('Failed to setup IntraVox groupfolder: ' . $e->getMessage());
            $this->logger->error('Stack trace: ' . $e->getTraceAsString());
            return false;
        }
    }

    /**
     * Ensure required groups exist and add current user to admin group
     */
    private function ensureGroupsExist(): void {
        foreach ([self::ADMIN_GROUP, self::USER_GROUP] as $groupId) {
            $this->logger->info("Checking if group exists: {$groupId}");
            if (!$this->groupManager->groupExists($groupId)) {
                $this->logger->info("Group does not exist, creating: {$groupId}");
                $this->groupManager->createGroup($groupId);
                $this->logger->info("Created group: {$groupId}");
            } else {
                $this->logger->info("Group already exists: {$groupId}");
            }
        }

        // Add current user to IntraVox Admins group for full permissions (including share and delete)
        $currentUser = $this->userSession->getUser();
        if ($currentUser !== null) {
            $adminGroup = $this->groupManager->get(self::ADMIN_GROUP);
            if ($adminGroup !== null && !$adminGroup->inGroup($currentUser)) {
                $adminGroup->addUser($currentUser);
                $this->logger->info("Added current user '{$currentUser->getUID()}' to " . self::ADMIN_GROUP . " group for full permissions");
            }
        }
    }

    /**
     * Create or get existing groupfolder
     */
    private function createOrGetGroupfolder(): ?int {
        try {
            $this->logger->info('Checking if groupfolders app is enabled...');
            // Check if groupfolders app is enabled
            if (!\OC::$server->getAppManager()->isEnabledForUser('groupfolders')) {
                $this->logger->error('Groupfolders app is not enabled');
                return null;
            }
            $this->logger->info('Groupfolders app is enabled');

            // Try to use groupfolders API
            $this->logger->info('Getting FolderManager instance...');
            $groupfolderManager = \OC::$server->get(\OCA\GroupFolders\Folder\FolderManager::class);
            $this->logger->info('FolderManager instance obtained');

            // Check if groupfolder already exists - use the one with highest ID
            $this->logger->info('Fetching all groupfolders...');
            $folders = $groupfolderManager->getAllFolders();
            $this->logger->info('Found ' . count($folders) . ' existing groupfolders');

            $existingFolderId = null;
            $highestId = 0;

            foreach ($folders as $id => $folderData) {
                $this->logger->info("Checking groupfolder ID: {$id}");
                // Handle FolderDefinitionWithMappings object
                if (is_object($folderData)) {
                    // Try property access first (mountPoint), then method if available
                    $mountPoint = property_exists($folderData, 'mountPoint') ? $folderData->mountPoint :
                                 (method_exists($folderData, 'getMountPoint') ? $folderData->getMountPoint() : null);
                } else {
                    $mountPoint = $folderData['mount_point'] ?? null;
                }
                $this->logger->info("Mount point: {$mountPoint}");

                // Find the IntraVox folder with the highest ID
                if ($mountPoint === self::GROUPFOLDER_NAME && $id > $highestId) {
                    $this->logger->info("Found matching groupfolder with ID: {$id}");
                    $existingFolderId = $id;
                    $highestId = $id;
                }
            }

            if ($existingFolderId !== null) {
                $this->logger->info("Using existing groupfolder with ID: {$existingFolderId}");
                return $existingFolderId;
            }

            // Create new groupfolder
            $this->logger->info('No existing groupfolder found, creating new one...');
            $folderId = $groupfolderManager->createFolder(self::GROUPFOLDER_NAME);
            $this->logger->info("Created groupfolder with ID: {$folderId}");

            return $folderId;

        } catch (\Exception $e) {
            $this->logger->error('Exception in createOrGetGroupfolder: ' . $e->getMessage());
            $this->logger->error('Stack trace: ' . $e->getTraceAsString());
            return null;
        }
    }

    /**
     * Configure groupfolder permissions for groups
     */
    private function configureGroupfolderPermissions(int $folderId): void {
        try {
            $this->logger->info("Getting FolderManager for permissions configuration...");
            $groupfolderManager = \OC::$server->get(\OCA\GroupFolders\Folder\FolderManager::class);

            // Add standard Nextcloud admin group with full permissions
            $this->logger->info("Adding 'admin' group to groupfolder {$folderId}...");
            $groupfolderManager->addApplicableGroup($folderId, 'admin');
            $this->logger->info("Setting full permissions for 'admin' group...");
            $groupfolderManager->setGroupPermissions($folderId, 'admin', \OCP\Constants::PERMISSION_ALL);
            $this->logger->info("Granted full permissions to: admin");

            // Add IntraVox admin group with full permissions
            $this->logger->info("Adding " . self::ADMIN_GROUP . " to groupfolder {$folderId}...");
            $groupfolderManager->addApplicableGroup($folderId, self::ADMIN_GROUP);
            $this->logger->info("Setting full permissions for " . self::ADMIN_GROUP . "...");
            $groupfolderManager->setGroupPermissions($folderId, self::ADMIN_GROUP, \OCP\Constants::PERMISSION_ALL);
            $this->logger->info("Granted full permissions to: " . self::ADMIN_GROUP);

            // Add user group with read permissions
            $this->logger->info("Adding " . self::USER_GROUP . " to groupfolder {$folderId}...");
            $groupfolderManager->addApplicableGroup($folderId, self::USER_GROUP);
            $this->logger->info("Setting read permissions for " . self::USER_GROUP . "...");
            $groupfolderManager->setGroupPermissions($folderId, self::USER_GROUP, \OCP\Constants::PERMISSION_READ);
            $this->logger->info("Granted read permissions to: " . self::USER_GROUP);

        } catch (\Exception $e) {
            $this->logger->error('Exception in configureGroupfolderPermissions: ' . $e->getMessage());
            $this->logger->error('Stack trace: ' . $e->getTraceAsString());
        }
    }

    /**
     * Get groupfolder object by ID
     */
    private function getGroupfolderObject(int $folderId) {
        try {
            $this->logger->info("Getting groupfolder object for ID: {$folderId}");
            // Get the actual folder object from the root
            // Groupfolders are stored in __groupfolders/<id>/files
            // The /files subdirectory is the actual user-facing mount point
            $groupfoldersRoot = $this->rootFolder->get('__groupfolders');
            $groupfolderMeta = $groupfoldersRoot->get((string)$folderId);
            $folder = $groupfolderMeta->get('files');

            $this->logger->info('Successfully accessed groupfolder files directory');
            return $folder;
        } catch (\Exception $e) {
            $this->logger->error('Failed to get groupfolder object: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Get the IntraVox groupfolder
     */
    public function getSharedFolder() {
        try {
            $this->logger->info('[getSharedFolder] Starting to get IntraVox groupfolder');

            // Get groupfolder manager
            $this->logger->info('[getSharedFolder] Getting FolderManager instance');
            $groupfolderManager = \OC::$server->get(\OCA\GroupFolders\Folder\FolderManager::class);
            $this->logger->info('[getSharedFolder] Got FolderManager instance');

            // Find IntraVox groupfolder - use the one with highest ID (most recent)
            $this->logger->info('[getSharedFolder] Getting all folders');
            $folders = $groupfolderManager->getAllFolders();
            $this->logger->info('[getSharedFolder] Found ' . count($folders) . ' groupfolders');
            $folderId = null;
            $highestId = 0;

            foreach ($folders as $id => $folderData) {
                $this->logger->info("[getSharedFolder] Checking folder ID: {$id}");
                // Handle FolderDefinitionWithMappings object
                if (is_object($folderData)) {
                    $className = get_class($folderData);
                    $this->logger->info("[getSharedFolder] Folder data is object of class: {$className}");
                    // Try property access first (mountPoint), then method if available
                    $mountPoint = property_exists($folderData, 'mountPoint') ? $folderData->mountPoint :
                                 (method_exists($folderData, 'getMountPoint') ? $folderData->getMountPoint() : null);
                } else {
                    $this->logger->info("[getSharedFolder] Folder data is array");
                    $mountPoint = $folderData['mount_point'] ?? null;
                }

                $this->logger->info("[getSharedFolder] Mount point: '{$mountPoint}', looking for: '" . self::GROUPFOLDER_NAME . "'");

                // Find the IntraVox folder with the highest ID (most recent)
                if ($mountPoint === self::GROUPFOLDER_NAME && $id > $highestId) {
                    $this->logger->info("[getSharedFolder] Found matching groupfolder with ID: {$id}");
                    $folderId = $id;
                    $highestId = $id;
                }
            }

            if ($folderId === null) {
                $this->logger->error('[getSharedFolder] No matching groupfolder found');
                throw new \Exception('IntraVox groupfolder not found. Please run intravox:setup command first.');
            }

            $this->logger->info("[getSharedFolder] Using groupfolder ID: {$folderId}");

            // Use the new getGroupfolderObject method to avoid recursion
            $this->logger->info("[getSharedFolder] Getting groupfolder object for ID: {$folderId}");
            $result = $this->getGroupfolderObject($folderId);
            $this->logger->info('[getSharedFolder] Successfully got groupfolder object');
            return $result;

        } catch (\Exception $e) {
            $this->logger->error('[getSharedFolder] Exception: ' . $e->getMessage());
            $this->logger->error('[getSharedFolder] Stack trace: ' . $e->getTraceAsString());
            throw new \Exception('IntraVox groupfolder not accessible. Error: ' . $e->getMessage());
        }
    }

    /**
     * Create default content in the IntraVox folder
     */
    private function createDefaultContent($folder): void {
        try {
            // Create language folders
            foreach (self::SUPPORTED_LANGUAGES as $lang) {
                try {
                    $langFolder = $folder->get($lang);
                    $this->logger->info("Language folder '{$lang}' already exists");
                } catch (NotFoundException $e) {
                    $langFolder = $folder->newFolder($lang);
                    $this->logger->info("Created language folder: {$lang}");
                }

                // Create subdirectories in each language folder
                try {
                    $langFolder->get('images');
                } catch (NotFoundException $e) {
                    $langFolder->newFolder('images');
                    $this->logger->info("Created images folder in {$lang}");
                }

                // Create default homepage for each language
                try {
                    $langFolder->get('home.json');
                    $this->logger->info("home.json already exists in {$lang}, skipping");
                } catch (NotFoundException $e) {
                    $this->createDefaultHomePageViaAPI($langFolder, $lang);
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
     * Get the IntraVox groupfolder ID
     * @throws \Exception if groupfolder not found
     */
    public function getGroupFolderId(): int {
        try {
            $this->logger->info('[getGroupFolderId] Getting groupfolder ID');

            // Get groupfolder manager
            $groupfolderManager = \OC::$server->get(\OCA\GroupFolders\Folder\FolderManager::class);

            // Find IntraVox groupfolder - use the one with highest ID
            $folders = $groupfolderManager->getAllFolders();
            $folderId = null;
            $highestId = 0;

            foreach ($folders as $id => $folderData) {
                // Handle FolderDefinitionWithMappings object
                if (is_object($folderData)) {
                    $mountPoint = property_exists($folderData, 'mountPoint') ? $folderData->mountPoint :
                                 (method_exists($folderData, 'getMountPoint') ? $folderData->getMountPoint() : null);
                } else {
                    $mountPoint = $folderData['mount_point'] ?? null;
                }

                // Find the IntraVox folder with the highest ID
                if ($mountPoint === self::GROUPFOLDER_NAME && $id > $highestId) {
                    $folderId = $id;
                    $highestId = $id;
                }
            }

            if ($folderId === null) {
                throw new \Exception('IntraVox groupfolder not found');
            }

            $this->logger->info('[getGroupFolderId] Found groupfolder ID: ' . $folderId);
            return $folderId;

        } catch (\Exception $e) {
            $this->logger->error('[getGroupFolderId] Exception: ' . $e->getMessage());
            throw new \Exception('Failed to get groupfolder ID: ' . $e->getMessage());
        }
    }

    /**
     * Get the IntraVox groupfolder name
     */
    public function getGroupFolderName(): string {
        return self::GROUPFOLDER_NAME;
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

}
