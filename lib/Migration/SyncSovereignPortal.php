<?php
declare(strict_types=1);

namespace OCA\IntraVox\Migration;

use OCA\IntraVox\Service\SovereignPortalSync;
use OCP\Migration\IOutput;
use OCP\Migration\IRepairStep;

/** Runs after native setup on install/update, so code and CMS travel together. */
final class SyncSovereignPortal implements IRepairStep {
    public function __construct(private SovereignPortalSync $sync) {}

    public function getName(): string {
        return 'Synchronize packaged Sovereign 365 Gold Master portal';
    }

    public function run(IOutput $output): void {
        // Deliberately propagate failures: an incomplete deployment must not appear successful.
        $result = $this->sync->sync();
        $output->info($result['noop']
            ? 'Sovereign 365 portal content already up to date'
            : sprintf('Sovereign 365 portal synchronized: %d files changed; sha256 %s', $result['changed'], $result['hash']));
    }
}
