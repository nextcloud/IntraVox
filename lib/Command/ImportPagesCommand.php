<?php
declare(strict_types=1);

namespace OCA\IntraVox\Command;

use OCA\IntraVox\Service\SetupService;
use OCP\Files\IRootFolder;
use OCP\Files\NotFoundException;
use OCP\IUserSession;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ImportPagesCommand extends Command {
    private IRootFolder $rootFolder;
    private IUserSession $userSession;
    private SetupService $setupService;

    public function __construct(
        IRootFolder $rootFolder,
        IUserSession $userSession,
        SetupService $setupService
    ) {
        parent::__construct();
        $this->rootFolder = $rootFolder;
        $this->userSession = $userSession;
        $this->setupService = $setupService;
    }

    protected function configure(): void {
        $this->setName('intravox:import')
            ->setDescription('Import IntraVox pages from filesystem and register them in file cache')
            ->addArgument(
                'source',
                InputArgument::REQUIRED,
                'Source directory containing pages to import (e.g., /path/to/demo-data/en/)'
            )
            ->addOption(
                'language',
                'l',
                InputOption::VALUE_REQUIRED,
                'Target language code (e.g., en, nl)',
                'en'
            )
            ->addOption(
                'user',
                'u',
                InputOption::VALUE_REQUIRED,
                'User ID to use for file operations',
                'admin'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int {
        $sourcePath = $input->getArgument('source');
        $language = $input->getOption('language');
        $userId = $input->getOption('user');

        // Validate source directory
        if (!is_dir($sourcePath)) {
            $output->writeln("<error>Source directory not found: {$sourcePath}</error>");
            return 1;
        }

        $output->writeln("<info>Importing IntraVox pages...</info>");
        $output->writeln("<info>Source: {$sourcePath}</info>");
        $output->writeln("<info>Language: {$language}</info>");
        $output->writeln("");

        // Set user context
        $userManager = \OC::$server->getUserManager();
        $user = $userManager->get($userId);
        if (!$user) {
            $output->writeln("<error>User not found: {$userId}</error>");
            return 1;
        }
        $this->userSession->setUser($user);

        // Get language folder
        try {
            $sharedFolder = $this->setupService->getSharedFolder();

            // Get or create language folder
            if (!$sharedFolder->nodeExists($language)) {
                $languageFolder = $sharedFolder->newFolder($language);
                $output->writeln("<info>Created language folder: {$language}</info>");
            } else {
                $languageFolder = $sharedFolder->get($language);
            }
        } catch (\Exception $e) {
            $output->writeln("<error>Failed to get language folder: {$e->getMessage()}</error>");
            return 1;
        }

        $imported = 0;
        $errors = 0;

        // Import home.json if exists
        $homeJsonPath = $sourcePath . '/home.json';
        if (file_exists($homeJsonPath)) {
            if ($this->importFile($homeJsonPath, $languageFolder, 'home.json', $output)) {
                $imported++;
            } else {
                $errors++;
            }
        }

        // Import navigation.json if exists
        $navJsonPath = $sourcePath . '/navigation.json';
        if (file_exists($navJsonPath)) {
            if ($this->importFile($navJsonPath, $languageFolder, 'navigation.json', $output)) {
                $imported++;
            } else {
                $errors++;
            }
        }

        // Import footer.json if exists
        $footerJsonPath = $sourcePath . '/footer.json';
        if (file_exists($footerJsonPath)) {
            if ($this->importFile($footerJsonPath, $languageFolder, 'footer.json', $output)) {
                $imported++;
            } else {
                $errors++;
            }
        }

        // Import root images folder if exists
        $imagesSourcePath = $sourcePath . '/images';
        if (is_dir($imagesSourcePath)) {
            try {
                // Create images folder in language root
                if (!$languageFolder->nodeExists('images')) {
                    $imagesFolder = $languageFolder->newFolder('images');
                    $output->writeln("<info>Created folder: images/</info>");
                } else {
                    $imagesFolder = $languageFolder->get('images');
                }

                // Import all images from root images folder
                $imageFiles = scandir($imagesSourcePath);
                foreach ($imageFiles as $imageFile) {
                    if ($imageFile === '.' || $imageFile === '..' || $imageFile === 'README.md') {
                        continue;
                    }

                    $imageSourcePath = $imagesSourcePath . '/' . $imageFile;
                    if (is_file($imageSourcePath)) {
                        $imageContent = file_get_contents($imageSourcePath);
                        if ($imageContent !== false) {
                            if ($imagesFolder->nodeExists($imageFile)) {
                                $imgFile = $imagesFolder->get($imageFile);
                                $imgFile->putContent($imageContent);
                            } else {
                                $imagesFolder->newFile($imageFile, $imageContent);
                            }
                            $output->writeln("<info>  Image: images/{$imageFile}</info>");
                        }
                    }
                }
            } catch (\Exception $e) {
                $output->writeln("<error>Failed to import root images folder: {$e->getMessage()}</error>");
            }
        }

        // Import page folders recursively
        $result = $this->importPageFolders($sourcePath, $languageFolder, '', $output);
        $imported += $result['imported'];
        $errors += $result['errors'];

        $output->writeln("");
        $output->writeln("<info>Import complete!</info>");
        $output->writeln("<info>Successfully imported: {$imported} pages</info>");

        if ($errors > 0) {
            $output->writeln("<error>Errors: {$errors}</error>");
            return 1;
        }

        $output->writeln("");
        $output->writeln("<comment>Pages are now registered in Nextcloud's file cache and ready to use in IntraVox!</comment>");

        return 0;
    }

    private function importFile($sourcePath, $targetFolder, $filename, OutputInterface $output): bool {
        try {
            $content = file_get_contents($sourcePath);

            if ($content === false) {
                $output->writeln("<error>Failed to read {$sourcePath}</error>");
                return false;
            }

            if ($targetFolder->nodeExists($filename)) {
                $file = $targetFolder->get($filename);
                $file->putContent($content);
                $output->writeln("<info>Updated: {$filename}</info>");
            } else {
                $targetFolder->newFile($filename, $content);
                $output->writeln("<info>Created: {$filename}</info>");
            }

            return true;
        } catch (\Exception $e) {
            $output->writeln("<error>Failed to import {$filename}: {$e->getMessage()}</error>");
            return false;
        }
    }

    /**
     * Recursively import page folders and their subfolders
     *
     * @param string $sourcePath Source directory path
     * @param \OCP\Files\Folder $targetFolder Target folder in Nextcloud
     * @param string $relativePath Current relative path (for nested folders)
     * @param OutputInterface $output Console output
     * @return array Array with 'imported' and 'errors' counts
     */
    private function importPageFolders($sourcePath, $targetFolder, $relativePath, OutputInterface $output): array {
        $imported = 0;
        $errors = 0;

        $entries = scandir($sourcePath);
        foreach ($entries as $entry) {
            if ($entry === '.' || $entry === '..' || $entry === 'images') {
                continue;
            }

            $entryPath = $sourcePath . '/' . $entry;
            if (!is_dir($entryPath)) {
                continue;
            }

            // This is a page folder
            $pageId = $entry;
            $pageJsonPath = $entryPath . '/' . $pageId . '.json';

            if (!file_exists($pageJsonPath)) {
                // No JSON file, skip this directory
                continue;
            }

            // Build the display path for output
            $displayPath = $relativePath ? $relativePath . '/' . $pageId : $pageId;

            // Create page folder in target
            try {
                if (!$targetFolder->nodeExists($pageId)) {
                    $pageFolder = $targetFolder->newFolder($pageId);
                    $output->writeln("<info>Created folder: {$displayPath}/</info>");
                } else {
                    $pageFolder = $targetFolder->get($pageId);
                }

                // Create images subfolder
                if (!$pageFolder->nodeExists('images')) {
                    $pageFolder->newFolder('images');
                    $output->writeln("<info>Created folder: {$displayPath}/images/</info>");
                }

                // Import page JSON
                $jsonContent = file_get_contents($pageJsonPath);
                if ($jsonContent === false) {
                    $output->writeln("<error>Failed to read {$pageJsonPath}</error>");
                    $errors++;
                    continue;
                }

                $pageData = json_decode($jsonContent, true);
                if ($pageData === null) {
                    $output->writeln("<error>Invalid JSON in {$pageJsonPath}</error>");
                    $errors++;
                    continue;
                }

                // Ensure uniqueId exists
                if (!isset($pageData['uniqueId'])) {
                    $pageData['uniqueId'] = 'page-' . bin2hex(random_bytes(8));
                    $jsonContent = json_encode($pageData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
                    $output->writeln("<comment>Added uniqueId: {$pageData['uniqueId']}</comment>");
                }

                $jsonFileName = $pageId . '.json';
                if ($pageFolder->nodeExists($jsonFileName)) {
                    $jsonFile = $pageFolder->get($jsonFileName);
                    $jsonFile->putContent($jsonContent);
                    $output->writeln("<info>Updated: {$displayPath}/{$jsonFileName}</info>");
                } else {
                    $pageFolder->newFile($jsonFileName, $jsonContent);
                    $output->writeln("<info>Created: {$displayPath}/{$jsonFileName}</info>");
                }

                // Import images if they exist
                $imagesSourcePath = $entryPath . '/images';
                if (is_dir($imagesSourcePath)) {
                    $imageFiles = scandir($imagesSourcePath);
                    foreach ($imageFiles as $imageFile) {
                        if ($imageFile === '.' || $imageFile === '..') {
                            continue;
                        }

                        $imageSourcePath = $imagesSourcePath . '/' . $imageFile;
                        if (is_file($imageSourcePath)) {
                            $imagesFolder = $pageFolder->get('images');
                            $imageContent = file_get_contents($imageSourcePath);

                            if ($imagesFolder->nodeExists($imageFile)) {
                                $imgFile = $imagesFolder->get($imageFile);
                                $imgFile->putContent($imageContent);
                            } else {
                                $imagesFolder->newFile($imageFile, $imageContent);
                            }
                            $output->writeln("<info>  Image: {$displayPath}/images/{$imageFile}</info>");
                        }
                    }
                }

                $imported++;

                // Recursively import subfolders
                $subResult = $this->importPageFolders($entryPath, $pageFolder, $displayPath, $output);
                $imported += $subResult['imported'];
                $errors += $subResult['errors'];

            } catch (\Exception $e) {
                $output->writeln("<error>Failed to import {$displayPath}: {$e->getMessage()}</error>");
                $errors++;
            }
        }

        return ['imported' => $imported, 'errors' => $errors];
    }
}
