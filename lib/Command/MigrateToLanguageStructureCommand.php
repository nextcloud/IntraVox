<?php
declare(strict_types=1);

namespace OCA\IntraVox\Command;

use OCP\Files\IRootFolder;
use OCP\Files\NotFoundException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Psr\Log\LoggerInterface;

class MigrateToLanguageStructureCommand extends Command {
    private const INTRAVOX_USER = 'intravox';
    private const INTRAVOX_FOLDER = 'IntraVox';
    private const LANGUAGE_FOLDERS = ['nl', 'en', 'de', 'fr'];

    private IRootFolder $rootFolder;
    private LoggerInterface $logger;

    public function __construct(
        IRootFolder $rootFolder,
        LoggerInterface $logger
    ) {
        parent::__construct();
        $this->rootFolder = $rootFolder;
        $this->logger = $logger;
    }

    protected function configure(): void {
        $this->setName('intravox:migrate-language-structure')
            ->setDescription('Migrate existing IntraVox content to language folder structure')
            ->addOption(
                'target-lang',
                't',
                InputOption::VALUE_OPTIONAL,
                'Target language for existing content (default: nl)',
                'nl'
            )
            ->addOption(
                'dry-run',
                'd',
                InputOption::VALUE_NONE,
                'Show what would be migrated without making changes'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int {
        $targetLang = $input->getOption('target-lang');
        $dryRun = $input->getOption('dry-run');

        if (!in_array($targetLang, self::LANGUAGE_FOLDERS)) {
            $output->writeln("<error>Invalid target language: {$targetLang}</error>");
            $output->writeln('<error>Supported languages: ' . implode(', ', self::LANGUAGE_FOLDERS) . '</error>');
            return 1;
        }

        if ($dryRun) {
            $output->writeln('<info>DRY RUN MODE - No changes will be made</info>');
        }

        $output->writeln('<info>Starting migration to language folder structure...</info>');
        $output->writeln("<info>Target language: {$targetLang}</info>");

        try {
            // Get intravox user folder
            $userFolder = $this->rootFolder->getUserFolder(self::INTRAVOX_USER);
            $intraVoxFolder = $userFolder->get(self::INTRAVOX_FOLDER);

            // Check if language folders already exist
            $hasLanguageFolders = false;
            foreach (self::LANGUAGE_FOLDERS as $lang) {
                try {
                    $intraVoxFolder->get($lang);
                    $hasLanguageFolders = true;
                    break;
                } catch (NotFoundException $e) {
                    // Language folder doesn't exist yet
                }
            }

            if ($hasLanguageFolders) {
                $output->writeln('<comment>⚠ Language folders already exist. Skipping migration to avoid data loss.</comment>');
                $output->writeln('<comment>  Please manually review the folder structure.</comment>');
                return 0;
            }

            // Get list of items to migrate
            $itemsToMigrate = [];
            foreach ($intraVoxFolder->getDirectoryListing() as $node) {
                $nodeName = $node->getName();
                // Skip language folders and backup files
                if (!in_array($nodeName, self::LANGUAGE_FOLDERS) && !str_ends_with($nodeName, '.backup')) {
                    $itemsToMigrate[] = $node;
                }
            }

            if (empty($itemsToMigrate)) {
                $output->writeln('<info>No content found to migrate.</info>');
                return 0;
            }

            $output->writeln('<info>Found ' . count($itemsToMigrate) . ' items to migrate:</info>');
            foreach ($itemsToMigrate as $item) {
                $type = $item->getType() === \OCP\Files\FileInfo::TYPE_FOLDER ? '[DIR]' : '[FILE]';
                $output->writeln("  {$type} {$item->getName()}");
            }

            if ($dryRun) {
                $output->writeln('');
                $output->writeln('<info>DRY RUN: Would create language folders and move content to ' . $targetLang . '/</info>');
                return 0;
            }

            // Create target language folder
            $output->writeln('');
            $output->writeln("<info>Creating {$targetLang}/ folder...</info>");
            try {
                $targetFolder = $intraVoxFolder->get($targetLang);
                $output->writeln("<comment>  ⚠ {$targetLang}/ folder already exists</comment>");
            } catch (NotFoundException $e) {
                $targetFolder = $intraVoxFolder->newFolder($targetLang);
                $output->writeln("<info>  ✓ Created {$targetLang}/ folder</info>");
            }

            // Move items to target language folder
            $output->writeln('');
            $output->writeln('<info>Moving content...</info>');
            foreach ($itemsToMigrate as $item) {
                $itemName = $item->getName();
                try {
                    // Check if item already exists in target
                    try {
                        $targetFolder->get($itemName);
                        $output->writeln("  ⚠ Skipping {$itemName} (already exists in {$targetLang}/)");
                        continue;
                    } catch (NotFoundException $e) {
                        // Item doesn't exist, proceed with move
                    }

                    $item->move($targetFolder->getPath() . '/' . $itemName);
                    $output->writeln("  ✓ Moved {$itemName} to {$targetLang}/");
                    $this->logger->info("Migrated {$itemName} to {$targetLang}/");
                } catch (\Exception $e) {
                    $output->writeln("  ✗ Failed to move {$itemName}: " . $e->getMessage());
                    $this->logger->error("Failed to migrate {$itemName}: " . $e->getMessage());
                }
            }

            // Create other language folders (empty)
            $output->writeln('');
            $output->writeln('<info>Creating empty language folders...</info>');
            foreach (self::LANGUAGE_FOLDERS as $lang) {
                if ($lang === $targetLang) {
                    continue; // Already created
                }

                try {
                    $intraVoxFolder->get($lang);
                    $output->writeln("  ⚠ {$lang}/ folder already exists");
                } catch (NotFoundException $e) {
                    $langFolder = $intraVoxFolder->newFolder($lang);
                    // Create images subdirectory
                    $langFolder->newFolder('images');
                    $output->writeln("  ✓ Created {$lang}/ folder with images subdirectory");
                }
            }

            $output->writeln('');
            $output->writeln('<info>✓ Migration completed successfully!</info>');
            $output->writeln('<info>  All content has been moved to ' . $targetLang . '/ folder</info>');
            $output->writeln('<info>  Other language folders have been created with basic structure</info>');

            return 0;

        } catch (\Exception $e) {
            $output->writeln('<error>✗ Migration failed: ' . $e->getMessage() . '</error>');
            $this->logger->error('Migration failed: ' . $e->getMessage());
            return 1;
        }
    }
}
