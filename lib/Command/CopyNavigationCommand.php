<?php
declare(strict_types=1);

namespace OCA\IntraVox\Command;

use OCA\IntraVox\Service\NavigationService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Psr\Log\LoggerInterface;

class CopyNavigationCommand extends Command {
    private const SUPPORTED_LANGUAGES = ['nl', 'en', 'de', 'fr'];

    private NavigationService $navigationService;
    private LoggerInterface $logger;

    public function __construct(
        NavigationService $navigationService,
        LoggerInterface $logger
    ) {
        parent::__construct();
        $this->navigationService = $navigationService;
        $this->logger = $logger;
    }

    protected function configure(): void {
        $this->setName('intravox:copy-navigation')
            ->setDescription('Copy navigation from one language to another')
            ->addArgument(
                'source',
                InputArgument::REQUIRED,
                'Source language code (nl, en, de, fr)'
            )
            ->addArgument(
                'target',
                InputArgument::REQUIRED,
                'Target language code (nl, en, de, fr) or "all" to copy to all languages'
            )
            ->addOption(
                'overwrite',
                'o',
                InputOption::VALUE_NONE,
                'Overwrite existing navigation in target language'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int {
        $source = $input->getArgument('source');
        $target = $input->getArgument('target');
        $overwrite = $input->getOption('overwrite');

        // Validate source language
        if (!in_array($source, self::SUPPORTED_LANGUAGES)) {
            $output->writeln("<error>Invalid source language: {$source}</error>");
            $output->writeln('<error>Supported languages: ' . implode(', ', self::SUPPORTED_LANGUAGES) . '</error>');
            return 1;
        }

        // Determine target languages
        $targetLanguages = [];
        if ($target === 'all') {
            $targetLanguages = array_filter(
                self::SUPPORTED_LANGUAGES,
                fn($lang) => $lang !== $source
            );
        } else {
            if (!in_array($target, self::SUPPORTED_LANGUAGES)) {
                $output->writeln("<error>Invalid target language: {$target}</error>");
                $output->writeln('<error>Supported languages: ' . implode(', ', self::SUPPORTED_LANGUAGES) . ' or "all"</error>');
                return 1;
            }
            if ($target === $source) {
                $output->writeln('<error>Source and target languages cannot be the same</error>');
                return 1;
            }
            $targetLanguages = [$target];
        }

        $output->writeln('<info>Copying navigation configuration...</info>');
        $output->writeln("<info>Source language: {$source}</info>");
        $output->writeln('<info>Target language(s): ' . implode(', ', $targetLanguages) . '</info>');
        $output->writeln('');

        try {
            // Get source navigation
            $output->writeln("<info>Loading navigation from {$source}...</info>");
            $sourceNavigation = $this->navigationService->getNavigation($source);

            $itemCount = count($sourceNavigation['items'] ?? []);
            if ($itemCount === 0) {
                $output->writeln("<comment>⚠ Warning: Source navigation in {$source} is empty</comment>");
            } else {
                $output->writeln("<info>✓ Loaded {$itemCount} navigation items from {$source}</info>");
            }
            $output->writeln('');

            // Copy to each target language
            $successCount = 0;
            $skippedCount = 0;
            foreach ($targetLanguages as $targetLang) {
                $output->writeln("<info>Copying to {$targetLang}...</info>");

                // Check if target already has navigation
                $targetNavigation = $this->navigationService->getNavigation($targetLang);
                $targetHasItems = !empty($targetNavigation['items']);

                if ($targetHasItems && !$overwrite) {
                    $output->writeln("<comment>  ⚠ Skipped: {$targetLang} already has navigation (use --overwrite to replace)</comment>");
                    $skippedCount++;
                    continue;
                }

                // Save navigation to target language
                $this->navigationService->saveNavigation($sourceNavigation, $targetLang);
                $output->writeln("<info>  ✓ Copied navigation to {$targetLang}</info>");
                $this->logger->info("Copied navigation from {$source} to {$targetLang}");
                $successCount++;
            }

            $output->writeln('');
            $output->writeln('<info>✓ Navigation copy completed!</info>');
            $output->writeln("<info>  Successfully copied to {$successCount} language(s)</info>");
            if ($skippedCount > 0) {
                $output->writeln("<comment>  Skipped {$skippedCount} language(s) (already have navigation)</comment>");
            }

            return 0;

        } catch (\Exception $e) {
            $output->writeln('<error>✗ Failed to copy navigation: ' . $e->getMessage() . '</error>');
            $this->logger->error('Navigation copy failed: ' . $e->getMessage());
            return 1;
        }
    }
}
