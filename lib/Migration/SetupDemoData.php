<?php
declare(strict_types=1);

namespace OCA\IntraVox\Migration;

use OCA\IntraVox\Service\DemoDataService;
use OCA\IntraVox\Service\SetupService;
use OCP\Migration\IOutput;
use OCP\Migration\IRepairStep;
use Psr\Log\LoggerInterface;

/**
 * Repair step that runs on app install/update to setup demo data
 */
class SetupDemoData implements IRepairStep {
    private SetupService $setupService;
    private DemoDataService $demoDataService;
    private LoggerInterface $logger;

    public function __construct(
        SetupService $setupService,
        DemoDataService $demoDataService,
        LoggerInterface $logger
    ) {
        $this->setupService = $setupService;
        $this->demoDataService = $demoDataService;
        $this->logger = $logger;
    }

    public function getName(): string {
        return 'Setup IntraVox demo data';
    }

    public function run(IOutput $output): void {
        $output->info('Setting up IntraVox...');

        // Setup groupfolder structure
        try {
            if (!$this->setupService->setupSharedFolder()) {
                $output->warning('Could not setup IntraVox groupfolder - groupfolders app may not be installed');
                return;
            }
            $output->info('IntraVox groupfolder ready');
        } catch (\Exception $e) {
            $this->logger->error('[IntraVox] Setup failed: ' . $e->getMessage());
            $output->warning('Setup failed: ' . $e->getMessage());
            return;
        }

        // Import demo data if not already imported
        if (!$this->demoDataService->isDemoDataImported()) {
            $output->info('Importing demo data...');

            foreach (['nl', 'en'] as $language) {
                if ($this->demoDataService->hasBundledDemoData($language)) {
                    $result = $this->demoDataService->importBundledDemoData($language);
                    if ($result['success']) {
                        $output->info("{$language}: {$result['imported']} items imported");
                    } else {
                        $output->warning("{$language}: {$result['message']}");
                    }
                }
            }

            $output->info('Demo data import complete');
        } else {
            $output->info('Demo data already imported, skipping');
        }
    }
}
