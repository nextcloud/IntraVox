<?php
declare(strict_types=1);

namespace OCA\IntraVox\Command;

use OCA\IntraVox\Service\SearchIndexService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ReindexSearchCommand extends Command {
    private SearchIndexService $searchIndexService;

    public function __construct(SearchIndexService $searchIndexService) {
        parent::__construct();
        $this->searchIndexService = $searchIndexService;
    }

    protected function configure(): void {
        $this
            ->setName('intravox:reindex')
            ->setDescription('Rebuild the IntraVox search index')
            ->addOption(
                'language',
                'l',
                InputOption::VALUE_OPTIONAL,
                'Language to index (default: en)',
                'en'
            )
            ->addOption(
                'clear',
                'c',
                InputOption::VALUE_NONE,
                'Clear index before reindexing'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int {
        $language = $input->getOption('language');
        $clear = $input->getOption('clear');

        if ($clear) {
            $output->writeln('<info>Clearing search index...</info>');
            if ($this->searchIndexService->clearIndex()) {
                $output->writeln('<info>Index cleared successfully</info>');
            } else {
                $output->writeln('<error>Failed to clear index</error>');
                return 1;
            }
        }

        $output->writeln("<info>Indexing pages for language: {$language}</info>");

        $result = $this->searchIndexService->indexAllPages($language);

        $output->writeln("<info>Indexed: {$result['indexed']} pages</info>");
        if ($result['errors'] > 0) {
            $output->writeln("<error>Errors: {$result['errors']}</error>");
        }

        $output->writeln('<info>Search index rebuilt successfully</info>');
        return 0;
    }
}
