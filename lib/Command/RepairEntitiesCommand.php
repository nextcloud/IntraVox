<?php
declare(strict_types=1);

namespace OCA\IntraVox\Command;

use OCA\IntraVox\Service\Folder\FolderContext;
use OCA\IntraVox\Service\Maintenance\PageMaintenanceService;
use OCP\IUserManager;
use OCP\IUserSession;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Repairs page data corrupted by the old sanitizeText(), which HTML-encoded
 * plain-text fields (e.g. a title "Collega's" was stored as "Collega&apos;s").
 * Decodes the entity-encoded title + widget text fields back to readable text.
 */
class RepairEntitiesCommand extends Command {
    private PageMaintenanceService $maintenance;
    private FolderContext $folders;
    private IUserSession $userSession;
    private IUserManager $userManager;

    public function __construct(
        // The repair walk lives in the MAINTAIN domain service; the IntraVox root
        // is resolved here through FolderContext — the exact `folders()->intraVox()`
        // the PageService::repairEntities delegator passed in.
        PageMaintenanceService $maintenance,
        FolderContext $folders,
        IUserSession $userSession,
        IUserManager $userManager
    ) {
        parent::__construct();
        $this->maintenance = $maintenance;
        $this->folders = $folders;
        $this->userSession = $userSession;
        $this->userManager = $userManager;
    }

    protected function configure(): void {
        $this->setName('intravox:repair-entities')
            ->setDescription('Decode HTML entities wrongly stored in page titles and text (e.g. "Collega&apos;s" → "Collega\'s")')
            ->addOption(
                'user',
                'u',
                InputOption::VALUE_REQUIRED,
                'User to run as (must have access to the IntraVox Team folder, e.g. admin)',
                'admin'
            )
            ->addOption(
                'dry-run',
                null,
                InputOption::VALUE_NONE,
                'Show what would change without writing any files'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int {
        $dryRun = (bool)$input->getOption('dry-run');

        // occ runs without a session; the repair reads the IntraVox Team folder
        // through a user's mounted view, so set one up (default: admin).
        $userId = (string)$input->getOption('user');
        $user = $this->userManager->get($userId);
        if ($user === null) {
            $output->writeln('<error>User not found: ' . $userId . '. Pass --user with a user that can access the IntraVox Team folder.</error>');
            return 1;
        }
        $this->userSession->setUser($user);

        if ($dryRun) {
            $output->writeln('<comment>Dry run — no files will be changed.</comment>');
        }

        try {
            $stats = $this->maintenance->repairEntities($this->folders->intraVox(), $dryRun);
        } catch (\Throwable $e) {
            $output->writeln('<error>Repair failed: ' . $e->getMessage() . '</error>');
            return 1;
        }

        foreach ($stats['files'] as $path) {
            $output->writeln('  ' . ($dryRun ? 'would fix' : 'fixed') . ': ' . $path);
        }

        $output->writeln(sprintf(
            '<info>%s %d of %d page file(s).</info>',
            $dryRun ? 'Would repair' : 'Repaired',
            $stats['changed'],
            $stats['scanned']
        ));

        return 0;
    }
}
