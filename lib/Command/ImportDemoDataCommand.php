<?php
declare(strict_types=1);

namespace OCA\IntraVox\Command;

use OCA\IntraVox\Service\DemoDataService;
use OCP\IUserSession;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * OCC command to import demo data
 *
 * Usage:
 *   occ intravox:import-demo                    # Import Dutch demo data
 *   occ intravox:import-demo --language=en     # Import English demo data
 *   occ intravox:import-demo -l nl             # Import Dutch demo data
 */
class ImportDemoDataCommand extends Command {
    private DemoDataService $demoDataService;
    private IUserSession $userSession;

    public function __construct(
        DemoDataService $demoDataService,
        IUserSession $userSession
    ) {
        parent::__construct();
        $this->demoDataService = $demoDataService;
        $this->userSession = $userSession;
    }

    protected function configure(): void {
        $this->setName('intravox:import-demo')
            ->setDescription('Import IntraVox demo data from remote source')
            ->addOption(
                'language',
                'l',
                InputOption::VALUE_REQUIRED,
                'Language code for demo data (nl, en)',
                'nl'
            )
            ->addOption(
                'user',
                'u',
                InputOption::VALUE_REQUIRED,
                'User ID to use for file operations',
                'admin'
            )
            ->addOption(
                'force',
                'f',
                InputOption::VALUE_NONE,
                'Force import even if demo data was already imported'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int {
        $language = $input->getOption('language');
        $userId = $input->getOption('user');
        $force = $input->getOption('force');

        $output->writeln('<info>IntraVox Demo Data Import</info>');
        $output->writeln('<info>=========================</info>');
        $output->writeln('');

        // Check if already imported
        if (!$force && $this->demoDataService->isDemoDataImported()) {
            $output->writeln('<comment>Demo data has already been imported.</comment>');
            $output->writeln('<comment>Use --force to re-import.</comment>');
            return 0;
        }

        // Set user context
        $userManager = \OC::$server->getUserManager();
        $user = $userManager->get($userId);
        if (!$user) {
            $output->writeln("<error>User not found: {$userId}</error>");
            return 1;
        }
        $this->userSession->setUser($user);

        $output->writeln("<info>Language: {$language}</info>");
        $output->writeln("<info>User: {$userId}</info>");
        $output->writeln('');
        $output->writeln('<info>Downloading demo data from GitHub...</info>');
        $output->writeln('');

        // Import demo data
        $result = $this->demoDataService->importDemoData($language);

        if ($result['success']) {
            $output->writeln('<info>' . $result['message'] . '</info>');
            $output->writeln('');
            $output->writeln('<comment>Demo data has been imported successfully!</comment>');
            $output->writeln('<comment>The demo pages are now available in IntraVox.</comment>');
            return 0;
        } else {
            $output->writeln('<error>' . $result['message'] . '</error>');
            return 1;
        }
    }
}
