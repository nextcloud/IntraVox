<?php
declare(strict_types=1);

namespace OCA\IntraVox\Command;

use OCA\IntraVox\Service\SovereignPortalSync;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Formatter\OutputFormatter;

final class SyncSovereignPortalCommand extends Command {
    public function __construct(private SovereignPortalSync $sync) {
        parent::__construct();
    }

    protected function configure(): void {
        $this->setName('intravox:sync-sovereign-portal')
            ->setDescription('Back up and synchronize the packaged Sovereign 365 Gold Master portal')
            ->addOption('force', null, InputOption::VALUE_NONE, 'Reapply the bundle even when its hash matches');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int {
        try {
            $result = $this->sync->sync((bool)$input->getOption('force'));
            if ($result['noop']) {
                $output->writeln('Sovereign 365 portal content already up to date');
                return 0;
            }
            $output->writeln(sprintf('Sovereign 365 portal synchronized: %d files changed; sha256 %s; private snapshot %s',
                $result['changed'], $result['hash'], $result['backup']));
            return 0;
        } catch (\Throwable $e) {
            $output->writeln('<error>Sovereign 365 sync failed: ' . OutputFormatter::escape($e->getMessage()) . '</error>');
            return 1;
        }
    }
}
