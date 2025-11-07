<?php
declare(strict_types=1);

namespace OCA\IntraVox\Command;

use OCA\IntraVox\Service\SetupService;
use OCP\Files\IRootFolder;
use OCP\Files\NotFoundException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Psr\Log\LoggerInterface;

class CreateLanguageHomepagesCommand extends Command {
    private const INTRAVOX_USER = 'intravox';
    private const INTRAVOX_FOLDER = 'IntraVox';
    private const SUPPORTED_LANGUAGES = ['nl', 'en', 'de', 'fr'];

    private IRootFolder $rootFolder;
    private SetupService $setupService;
    private LoggerInterface $logger;

    public function __construct(
        IRootFolder $rootFolder,
        SetupService $setupService,
        LoggerInterface $logger
    ) {
        parent::__construct();
        $this->rootFolder = $rootFolder;
        $this->setupService = $setupService;
        $this->logger = $logger;
    }

    protected function configure(): void {
        $this->setName('intravox:create-language-homepages')
            ->setDescription('Create home.json for all supported languages');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int {
        $output->writeln('<info>Creating language-specific homepages...</info>');

        try {
            // Get intravox user folder
            $userFolder = $this->rootFolder->getUserFolder(self::INTRAVOX_USER);
            $intraVoxFolder = $userFolder->get(self::INTRAVOX_FOLDER);

            foreach (self::SUPPORTED_LANGUAGES as $lang) {
                $output->writeln("<info>Processing language: {$lang}</info>");

                try {
                    $langFolder = $intraVoxFolder->get($lang);
                } catch (NotFoundException $e) {
                    $output->writeln("  <comment>Creating {$lang} folder...</comment>");
                    $langFolder = $intraVoxFolder->newFolder($lang);

                    // Create subdirectories
                    $langFolder->newFolder('images');
                    $langFolder->newFolder('files');
                }

                // Check if home.json already exists
                try {
                    $langFolder->get('home.json');
                    $output->writeln("  <comment>home.json already exists, skipping</comment>");
                    continue;
                } catch (NotFoundException $e) {
                    // Create home.json
                    $content = $this->getDefaultHomePageContent($lang);
                    $file = $langFolder->newFile('home.json');
                    $file->putContent(json_encode($content, JSON_PRETTY_PRINT));
                    $output->writeln("  <info>✓ Created home.json</info>");
                }
            }

            $output->writeln('');
            $output->writeln('<info>✓ All language homepages created successfully!</info>');
            return 0;

        } catch (\Exception $e) {
            $output->writeln('<error>✗ Failed: ' . $e->getMessage() . '</error>');
            $this->logger->error('Failed to create language homepages: ' . $e->getMessage());
            return 1;
        }
    }

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

        $t = $translations[$lang] ?? $translations['en'];

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
}
