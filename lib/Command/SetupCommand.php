<?php
declare(strict_types=1);

namespace OCA\IntraVox\Command;

use OCA\IntraVox\Service\SetupService;
use OCA\IntraVox\Service\DemoDataService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class SetupCommand extends Command {
    private SetupService $setupService;
    private DemoDataService $demoDataService;

    public function __construct(SetupService $setupService, DemoDataService $demoDataService) {
        parent::__construct();
        $this->setupService = $setupService;
        $this->demoDataService = $demoDataService;
    }

    protected function configure(): void {
        $this->setName('intravox:setup')
            ->setDescription('Setup IntraVox groupfolder structure and import demo data')
            ->addOption(
                'skip-demo',
                null,
                InputOption::VALUE_NONE,
                'Skip importing demo data'
            )
            ->addOption(
                'force-demo',
                null,
                InputOption::VALUE_NONE,
                'Force re-import of demo data (updates existing files)'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int {
        $output->writeln('<info>Setting up IntraVox groupfolder...</info>');

        if (!$this->setupService->setupSharedFolder()) {
            $output->writeln('<error>✗ Failed to setup IntraVox groupfolder</error>');
            $output->writeln('<error>  Please check logs for details</error>');
            $output->writeln('<error>  Make sure the groupfolders app is installed and enabled</error>');
            return 1;
        }

        $output->writeln('<info>✓ IntraVox groupfolder created successfully</info>');
        $output->writeln('<info>✓ Default homepage created</info>');
        $output->writeln('<info>✓ Groups configured with proper permissions</info>');

        // Import demo data unless skipped
        $skipDemo = $input->getOption('skip-demo');
        $forceDemo = $input->getOption('force-demo');
        $shouldImport = !$skipDemo && ($forceDemo || !$this->demoDataService->isDemoDataImported());

        if ($shouldImport) {
            $output->writeln('');
            $output->writeln('<info>Importing demo data...</info>');

            // Import both NL and EN demo data if available
            foreach (['nl', 'en'] as $language) {
                if ($this->demoDataService->hasBundledDemoData($language)) {
                    $output->writeln("<info>  Importing {$language} demo data...</info>");
                    $result = $this->demoDataService->importBundledDemoData($language);
                    if ($result['success']) {
                        $output->writeln("<info>  ✓ {$language}: {$result['imported']} items imported</info>");
                    } else {
                        $output->writeln("<comment>  ⚠ {$language}: {$result['message']}</comment>");
                    }
                }
            }

            $output->writeln('<info>✓ Demo data imported</info>');
        }

        // Ensure _resources folders exist AFTER demo import (demo import deletes language folders in overwrite mode)
        $output->writeln('');
        $output->writeln('<info>Ensuring _resources folders exist...</info>');
        if ($this->setupService->migrateResourcesFolders()) {
            $output->writeln('<info>✓ _resources folders verified/created</info>');
        } else {
            $output->writeln('<comment>⚠ Warning: _resources folder migration had issues (check logs)</comment>');
        }

        $output->writeln('');
        $output->writeln('<comment>The IntraVox groupfolder is now available in the Files app</comment>');
        $output->writeln('<comment>Admins in "IntraVox Admins" have full access</comment>');
        $output->writeln('<comment>Users in "IntraVox Users" have read-only access</comment>');
        return 0;
    }
}
