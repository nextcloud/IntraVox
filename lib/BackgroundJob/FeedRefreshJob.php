<?php
declare(strict_types=1);

namespace OCA\IntraVox\BackgroundJob;

use OCA\IntraVox\AppInfo\Application;
use OCA\IntraVox\Service\FeedReaderService;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\BackgroundJob\TimedJob;
use OCP\ICacheFactory;
use OCP\ICache;
use OCP\IConfig;
use Psr\Log\LoggerInterface;

/**
 * Background job that proactively refreshes popular RSS feeds before TTL expiry.
 *
 * Includes a circuit breaker: after consecutive failures for a source,
 * the source is temporarily marked as "down" and skipped.
 */
class FeedRefreshJob extends TimedJob {
    private const INTERVAL_MINUTES = 10;
    private const CIRCUIT_BREAKER_THRESHOLD = 3;
    private const CIRCUIT_BREAKER_COOLDOWN = 300; // 5 minutes

    private ?ICache $cache = null;

    public function __construct(
        ITimeFactory $time,
        private FeedReaderService $feedReaderService,
        private IConfig $config,
        private LoggerInterface $logger,
        ICacheFactory $cacheFactory,
    ) {
        parent::__construct($time);
        $this->setInterval(self::INTERVAL_MINUTES * 60);

        if ($cacheFactory->isAvailable()) {
            $this->cache = $cacheFactory->createDistributed('intravox-feeds');
        }
    }

    protected function run($argument): void {
        // Get configured RSS feed URLs from all widget configs stored in pages
        // For now, refresh connections that are configured
        $connections = $this->feedReaderService->getConnections();

        foreach ($connections as $connection) {
            $connectionId = $connection['id'] ?? '';
            if (empty($connectionId)) {
                continue;
            }

            // Check circuit breaker
            if ($this->isCircuitOpen($connectionId)) {
                $this->logger->debug('IntraVox FeedRefresh: Skipping connection ' . $connectionId . ' (circuit breaker open)');
                continue;
            }

            try {
                // Refresh the feed (this populates the cache for subsequent user requests)
                $config = ['connectionId' => $connectionId];
                $type = $connection['type'] ?? 'custom';
                $this->feedReaderService->fetchFeed($type, $config, 10, null);

                // Reset failure counter on success
                $this->resetFailures($connectionId);
            } catch (\Exception $e) {
                $this->recordFailure($connectionId);
                $this->logger->warning('IntraVox FeedRefresh: Failed to refresh connection ' . $connectionId, [
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    private function isCircuitOpen(string $sourceId): bool {
        if ($this->cache === null) {
            return false;
        }
        $key = 'circuit_' . md5($sourceId);
        $data = $this->cache->get($key);
        if ($data === null) {
            return false;
        }
        $decoded = json_decode($data, true);
        if (!is_array($decoded)) {
            return false;
        }
        return ($decoded['failures'] ?? 0) >= self::CIRCUIT_BREAKER_THRESHOLD;
    }

    private function recordFailure(string $sourceId): void {
        if ($this->cache === null) {
            return;
        }
        $key = 'circuit_' . md5($sourceId);
        $data = $this->cache->get($key);
        $decoded = $data !== null ? json_decode($data, true) : [];
        $failures = ($decoded['failures'] ?? 0) + 1;
        $this->cache->set($key, json_encode(['failures' => $failures]), self::CIRCUIT_BREAKER_COOLDOWN);
    }

    private function resetFailures(string $sourceId): void {
        if ($this->cache === null) {
            return;
        }
        $this->cache->remove('circuit_' . md5($sourceId));
    }
}
