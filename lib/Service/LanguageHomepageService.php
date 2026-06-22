<?php
declare(strict_types=1);

namespace OCA\IntraVox\Service;

use OCP\Files\NotFoundException;
use Psr\Log\LoggerInterface;

/**
 * Creates (and removes) the per-language content folder inside the IntraVox
 * GroupFolder. Shared between the `intravox:create-language-homepages` OCC
 * command (bulk) and the LanguageController (on-demand, when an admin adds or
 * removes a language in the admin UI).
 *
 * Idempotent: if `home.json` already exists in the target language folder, the
 * create method returns without overwriting user content.
 *
 * Content is rendered in the requested language for the four bundled
 * translations (nl/en/de/fr) and falls back to English for all others — until
 * Transifex catches up with bundled defaults for those languages, this is
 * intentional and acceptable.
 *
 * The GroupFolder is reached via SetupService::getSharedFolder() (the same path
 * demo-data installation uses), NOT via a user folder — there is no dedicated
 * `intravox` system user, so getUserFolder('intravox') fails with "Backends
 * provided no user object" and the homepage was never actually written.
 */
class LanguageHomepageService {
    private SetupService $setupService;
    private LoggerInterface $logger;

    public function __construct(
        SetupService $setupService,
        LoggerInterface $logger
    ) {
        $this->setupService = $setupService;
        $this->logger = $logger;
    }

    /**
     * Result type for the operation.
     *
     * @return array{success: bool, created: bool, message: string}
     *   - success: did the call complete without error?
     *   - created: was a new home.json file actually written? (false when one already existed)
     *   - message: human-readable summary
     */
    public function createEmptyHomepage(string $lang): array {
        try {
            // getSharedFolder() IS the IntraVox content root (the GroupFolder's
            // `files` dir), so language folders live directly inside it.
            $contentRoot = $this->setupService->getSharedFolder();

            // Get-or-create the language folder.
            try {
                $langFolder = $contentRoot->get($lang);
            } catch (NotFoundException $e) {
                $langFolder = $contentRoot->newFolder($lang);
                $langFolder->newFolder('images');
            }

            // Idempotent: never overwrite existing user content.
            try {
                $langFolder->get('home.json');
                return [
                    'success' => true,
                    'created' => false,
                    'message' => "home.json already exists in {$lang}, skipped",
                ];
            } catch (NotFoundException $e) {
                $content = $this->getDefaultHomePageContent($lang);
                $file = $langFolder->newFile('home.json');
                $file->putContent(json_encode($content, JSON_PRETTY_PRINT));
            }

            // The new folder is written via the Files API on the GroupFolder's
            // own mount, but a user's mounted view of that GroupFolder reads from
            // the file cache — which isn't updated for the new path until a scan.
            // Without this, the just-added language is invisible until the next
            // background scan. Scan synchronously so it shows up immediately.
            $this->scanFolder($langFolder);

            return [
                'success' => true,
                'created' => true,
                'message' => "Created home.json for {$lang}",
            ];
        } catch (\Throwable $e) {
            $this->logger->error('[LanguageHomepageService] Failed to create homepage for ' . $lang . ': ' . $e->getMessage());
            return [
                'success' => false,
                'created' => false,
                'message' => 'Failed: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Remove a language's entire content folder from the IntraVox GroupFolder.
     * Deletes `<lang>/` and everything in it (pages, images). The folder goes to
     * the GroupFolder trash, so it can be restored from Files if needed.
     *
     * @return array{success: bool, removed: bool, message: string}
     *   - removed: was a folder actually deleted? (false when none existed)
     */
    public function removeLanguage(string $lang): array {
        try {
            $contentRoot = $this->setupService->getSharedFolder();

            if (!$contentRoot->nodeExists($lang)) {
                return [
                    'success' => true,
                    'removed' => false,
                    'message' => "No content folder for {$lang}, nothing to remove",
                ];
            }

            $contentRoot->get($lang)->delete();

            return [
                'success' => true,
                'removed' => true,
                'message' => "Removed content folder for {$lang}",
            ];
        } catch (\Throwable $e) {
            $this->logger->error('[LanguageHomepageService] Failed to remove language ' . $lang . ': ' . $e->getMessage());
            return [
                'success' => false,
                'removed' => false,
                'message' => 'Failed: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Synchronously scan a freshly written folder into the file cache, so it is
     * immediately visible from every user's mounted view of the GroupFolder.
     * Best-effort: logs and continues if the storage doesn't support scanning.
     */
    private function scanFolder(\OCP\Files\Folder $folder): void {
        try {
            $storage = $folder->getStorage();
            $scanner = $storage->getScanner();
            $scanner->scan($folder->getInternalPath(), \OCP\Files\Cache\IScanner::SCAN_RECURSIVE);
        } catch (\Throwable $e) {
            $this->logger->warning('[LanguageHomepageService] scan after create failed: ' . $e->getMessage());
        }
    }

    private function getDefaultHomePageContent(string $lang): array {
        $translations = [
            'nl' => [
                'title' => 'Welkom bij IntraVox',
                'heading' => 'Welkom bij IntraVox',
                'intro' => 'Dit is je organisatie intranet. Deze folder is een groupfolder waar admins standaard toegang toe hebben. Je kunt via Groepsmappen beheer andere groepen toegang geven.',
                'getting_started' => 'Aan de slag',
                'instructions' => '1. Klik op "Bewerken" om deze pagina aan te passen\n2. Klik op "+ Nieuwe Pagina" om meer pagina\'s toe te voegen\n3. Gebruik widgets om content toe te voegen\n4. Sleep widgets om je layout aan te passen\n\nDeze folder is niet gekoppeld aan een gebruikersaccount maar is een echte systeemfolder!',
            ],
            'en' => [
                'title' => 'Welcome to IntraVox',
                'heading' => 'Welcome to IntraVox',
                'intro' => 'This is your organization intranet. This folder is a group folder where admins have access by default. You can grant access to other groups via Group folders management.',
                'getting_started' => 'Getting Started',
                'instructions' => '1. Click "Edit" to modify this page\n2. Click "+ New Page" to add more pages\n3. Use widgets to add content\n4. Drag widgets to adjust your layout\n\nThis folder is not linked to a user account but is a real system folder!',
            ],
            'de' => [
                'title' => 'Willkommen bei IntraVox',
                'heading' => 'Willkommen bei IntraVox',
                'intro' => 'Dies ist Ihr Organisations-Intranet. Dieser Ordner ist ein Gruppenordner, auf den Administratoren standardmäßig Zugriff haben. Sie können anderen Gruppen über die Gruppenordnerverwaltung Zugriff gewähren.',
                'getting_started' => 'Erste Schritte',
                'instructions' => '1. Klicken Sie auf "Bearbeiten", um diese Seite anzupassen\n2. Klicken Sie auf "+ Neue Seite", um weitere Seiten hinzuzufügen\n3. Verwenden Sie Widgets, um Inhalte hinzuzufügen\n4. Ziehen Sie Widgets, um Ihr Layout anzupassen\n\nDieser Ordner ist nicht mit einem Benutzerkonto verknüpft, sondern ein echter Systemordner!',
            ],
            'fr' => [
                'title' => 'Bienvenue sur IntraVox',
                'heading' => 'Bienvenue sur IntraVox',
                'intro' => 'Ceci est l\'intranet de votre organisation. Ce dossier est un dossier de groupe auquel les administrateurs ont accès par défaut. Vous pouvez accorder l\'accès à d\'autres groupes via la gestion des dossiers de groupe.',
                'getting_started' => 'Pour commencer',
                'instructions' => '1. Cliquez sur "Modifier" pour modifier cette page\n2. Cliquez sur "+ Nouvelle page" pour ajouter plus de pages\n3. Utilisez des widgets pour ajouter du contenu\n4. Faites glisser les widgets pour ajuster votre mise en page\n\nCe dossier n\'est pas lié à un compte utilisateur mais est un véritable dossier système!',
            ],
        ];

        $t = $translations[$lang] ?? $translations['en'];

        return [
            'id' => 'home',
            'title' => $t['title'],
            // Marker: this homepage was auto-generated, not authored by an
            // editor. The landing-page fallback notice uses it to tell "real"
            // content apart from a placeholder. The marker is dropped the moment
            // an editor saves the page (validateAndSanitizePage whitelists keys
            // and never copies it through).
            '_generated' => true,
            'layout' => [
                'columns' => 2,
                'rows' => [
                    [
                        'widgets' => [
                            ['type' => 'heading', 'content' => $t['heading'], 'level' => 1, 'column' => 1, 'order' => 1],
                            ['type' => 'text',    'content' => $t['intro'],   'column' => 1, 'order' => 2],
                            ['type' => 'heading', 'content' => $t['getting_started'], 'level' => 2, 'column' => 2, 'order' => 1],
                            ['type' => 'text',    'content' => $t['instructions'], 'column' => 2, 'order' => 2],
                        ],
                    ],
                ],
            ],
            'created' => time(),
            'modified' => time(),
        ];
    }
}
