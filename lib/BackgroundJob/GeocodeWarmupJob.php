<?php
declare(strict_types=1);

namespace OCA\IntraVox\BackgroundJob;

use OCA\IntraVox\Service\PhotoStory\GeocodeCache;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\BackgroundJob\TimedJob;
use Psr\Log\LoggerInterface;

/**
 * Periodically drain the PhotoStory geocode-miss queue, fetching Nominatim
 * lookups in the background instead of blocking the request thread.
 *
 * The queue is populated by GeocodeCache::enqueueMisses() whenever an HTTP
 * request needs a coord that isn't in the cache. This job runs every 15
 * minutes and processes up to 50 fetches per run (50 × 1.1s ≈ 55s, well
 * within a sensible cron-slot).
 */
class GeocodeWarmupJob extends TimedJob {
	private const INTERVAL = 15 * 60; // 15 minutes
	private const MAX_FETCHES_PER_RUN = 50;

	public function __construct(
		ITimeFactory $time,
		private GeocodeCache $geocodeCache,
		private LoggerInterface $logger,
	) {
		parent::__construct($time);
		$this->setInterval(self::INTERVAL);
	}

	protected function run($argument): void {
		try {
			$result = $this->geocodeCache->processQueue(self::MAX_FETCHES_PER_RUN);
			if ($result['processed'] > 0 || $result['failures'] > 0) {
				$this->logger->info('GeocodeWarmupJob: processed={p} remaining={r} failures={f}', [
					'p' => $result['processed'],
					'r' => $result['queued'],
					'f' => $result['failures'],
				]);
			}
		} catch (\Throwable $e) {
			$this->logger->warning('GeocodeWarmupJob: run failed: ' . $e->getMessage());
		}
	}
}
