<?php
declare(strict_types=1);

namespace OCA\IntraVox\Command;

use OCA\IntraVox\Service\PublicShareService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Debug command to test share detection for a page
 *
 * Usage: occ intravox:debug-share <pageUniqueId> [language]
 */
class DebugShareCommand extends Command {
    public function __construct(
        private PublicShareService $publicShareService
    ) {
        parent::__construct();
    }

    protected function configure(): void {
        $this->setName('intravox:debug-share')
            ->setDescription('Debug share detection for a page')
            ->addArgument('pageUniqueId', InputArgument::REQUIRED, 'The page unique ID')
            ->addArgument('language', InputArgument::OPTIONAL, 'The language folder', 'en');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int {
        $pageUniqueId = $input->getArgument('pageUniqueId');
        $language = $input->getArgument('language');

        $output->writeln('<info>Testing share detection...</info>');
        $output->writeln('Page ID: ' . $pageUniqueId);
        $output->writeln('Language: ' . $language);
        $output->writeln('');

        $shareInfo = $this->publicShareService->getShareInfoForPage($pageUniqueId, $language, null);

        $output->writeln('<info>Share Info Result:</info>');
        $output->writeln(json_encode($shareInfo, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        return 0;
    }
}
