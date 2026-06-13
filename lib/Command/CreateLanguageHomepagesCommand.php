<?php
declare(strict_types=1);

namespace OCA\IntraVox\Command;

use OCA\IntraVox\Service\LanguageHomepageService;
use OCA\IntraVox\Service\LanguageService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Psr\Log\LoggerInterface;

class CreateLanguageHomepagesCommand extends Command {
    private LanguageService $languageService;
    private LanguageHomepageService $homepageService;
    private LoggerInterface $logger;

    public function __construct(
        LanguageService $languageService,
        LanguageHomepageService $homepageService,
        LoggerInterface $logger
    ) {
        parent::__construct();
        $this->languageService = $languageService;
        $this->homepageService = $homepageService;
        $this->logger = $logger;
    }

    protected function configure(): void {
        $this->setName('intravox:create-language-homepages')
            ->setDescription('Create home.json for every admin-enabled language (idempotent)');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int {
        $output->writeln('<info>Creating language-specific homepages...</info>');

        $languages = $this->languageService->getEnabledLanguages();
        $output->writeln('<info>Enabled languages: ' . implode(', ', $languages) . '</info>');

        $errors = 0;
        foreach ($languages as $lang) {
            $output->writeln("<info>Processing language: {$lang}</info>");
            $result = $this->homepageService->createEmptyHomepage($lang);

            if (!$result['success']) {
                $output->writeln('  <error>' . $result['message'] . '</error>');
                $errors++;
                continue;
            }

            if ($result['created']) {
                $output->writeln('  <info>✓ ' . $result['message'] . '</info>');
            } else {
                $output->writeln('  <comment>' . $result['message'] . '</comment>');
            }
        }

        $output->writeln('');
        if ($errors > 0) {
            $output->writeln("<error>✗ Completed with {$errors} error(s)</error>");
            return 1;
        }
        $output->writeln('<info>✓ All language homepages created successfully!</info>');
        return 0;
    }
}
