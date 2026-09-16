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
 * Rebuilds the page index from the page files on disk.
 *
 * The index is derived data — the JSON files in the IntraVox Team folder are
 * the source of truth. Anything that writes those files outside IntraVox
 * (a restore, a manual copy in the Files app, `occ files:scan`, an upgrade
 * from a version that did not index a write path) leaves the index stale, and
 * a stale index has no other cure than editing the database by hand.
 *
 * Run this after a restore or migration, or whenever pages behave as if they
 * are missing from listings and search while the files are plainly there.
 */
class ReindexPagesCommand extends Command {
    public function __construct(
        // The rebuild walk lives in the MAINTAIN domain service; the write-target
        // root is resolved here through FolderContext — the exact
        // `folders()->intraVox()` the PageService::rebuildIndex delegator passed in.
        private PageMaintenanceService $maintenance,
        private FolderContext $folders,
        private IUserSession $userSession,
        private IUserManager $userManager
    ) {
        parent::__construct();
    }

    protected function configure(): void {
        $this->setName('intravox:reindex')
            ->setDescription('Rebuild the page index from the page files on disk')
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
                'Report what would be indexed without changing the index'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int {
        $dryRun = (bool)$input->getOption('dry-run');

        // occ runs without a session; the rebuild reads the Team folder
        // through a user's mounted view, so set one up (default: admin).
        $userId = (string)$input->getOption('user');
        $user = $this->userManager->get($userId);
        if ($user === null) {
            $output->writeln(
                '<error>User not found: ' . $userId
                . '. Pass --user with a user that can access the IntraVox Team folder.</error>'
            );
            return 1;
        }
        $this->userSession->setUser($user);

        if ($dryRun) {
            $output->writeln('<comment>Dry run — the index will not be changed.</comment>');
        }

        try {
            $stats = $this->maintenance->rebuildIndex($this->folders->intraVox(), $dryRun);
        } catch (\Throwable $e) {
            $output->writeln('<error>Reindex failed: ' . $e->getMessage() . '</error>');
            return 1;
        }

        foreach ($stats['languages'] as $lang => $count) {
            $output->writeln(sprintf('  %-6s %d page(s)', $lang, $count));
        }

        if (empty($stats['languages'])) {
            $output->writeln(
                '<comment>No language folders found. Is the IntraVox Team folder set up,'
                . ' and can ' . $userId . ' see it?</comment>'
            );
        }

        $output->writeln(sprintf(
            '<info>%s %d of %d page file(s) across %d language(s).</info>',
            $dryRun ? 'Would index' : 'Indexed',
            $stats['indexed'],
            $stats['scanned'],
            count($stats['languages'])
        ));

        // Scanned-but-not-indexed means JSON files without a uniqueId. That is
        // normal for stray files, but a large gap points at malformed pages.
        $skipped = $stats['scanned'] - $stats['indexed'];
        if ($skipped > 0) {
            $output->writeln(sprintf(
                '<comment>%d file(s) had no uniqueId and were skipped.</comment>',
                $skipped
            ));
        }

        return 0;
    }
}
