<?php
declare(strict_types=1);

namespace OCA\IntraVox\Command;

use OCA\IntraVox\Service\SetupService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class SetupCommand extends Command {
    private SetupService $setupService;

    public function __construct(SetupService $setupService) {
        parent::__construct();
        $this->setupService = $setupService;
    }

    protected function configure(): void {
        $this->setName('intravox:setup')
            ->setDescription('Setup IntraVox groupfolder structure');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int {
        $output->writeln('<info>Setting up IntraVox groupfolder...</info>');

        if ($this->setupService->setupSharedFolder()) {
            $output->writeln('<info>✓ IntraVox groupfolder created successfully</info>');
            $output->writeln('<info>✓ Default homepage created</info>');
            $output->writeln('<info>✓ Groups configured with proper permissions</info>');
            $output->writeln('');
            $output->writeln('<comment>The IntraVox groupfolder is now available in the Files app</comment>');
            $output->writeln('<comment>Admins in "IntraVox Admins" have full access</comment>');
            $output->writeln('<comment>Users in "IntraVox Users" have read-only access</comment>');
            return 0;
        } else {
            $output->writeln('<error>✗ Failed to setup IntraVox groupfolder</error>');
            $output->writeln('<error>  Please check logs for details</error>');
            $output->writeln('<error>  Make sure the groupfolders app is installed and enabled</error>');
            return 1;
        }
    }
}
