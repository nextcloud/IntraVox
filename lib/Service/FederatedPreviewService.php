<?php
declare(strict_types=1);

namespace OCA\IntraVox\Service;

use OCA\IntraVox\AppInfo\Application;
use OCP\Files\IAppData;
use OCP\Files\NotFoundException;
use OCP\Files\SimpleFS\ISimpleFolder;
use OCP\Http\Client\IClientService;
use OCP\ICacheFactory;
use Psr\Log\LoggerInterface;

/**
 * Backend for the federated-preview proxy. Lives separately from the HTTP
 * controller so we can unit-test the dedup/cap logic without spinning up an
 * NC request, and so a background pre-warm job can reuse it.
 *
 * Three protection layers stack here:
 *
 *  1. **Appdata cache** keyed by (fileId, etag, x, y). Identical re-requests
 *     never leave this server. Cache entries are immutable for the key —
 *     stale ones become garbage-collectable when the etag advances.
 *
 *  2. **In-flight deduplication.** When N concurrent requests target the same
 *     uncached (fileId, etag, x, y), only the first PHP process performs the
 *     remote HTTPS call. Others wait briefly for the cache to materialise. We
 *     implement this with an atomic `add` on NC's distributed cache (Redis /
 *     APCu) — works across worker processes, no DB churn.
 *
 *  3. **Per-remote concurrency cap.** A semaphore (sliding counter in the
 *     distributed cache) bounds the number of simultaneous outbound calls per
 *     remote host. Protects the remote NC from a stampede when, say, 50
 *     IntraVox users open the same federated folder at once. When the cap is
 *     hit we serve the mime-icon fallback rather than queueing — a degraded
 *     experience for that single tile, but the remote stays healthy.
 */
class FederatedPreviewService {

	/** Subfolder of NC appdata where cached previews land. */
	private const CACHE_BUCKET = 'federated-preview';

	/** HTTP request timeout to the remote NC. */
	private const REMOTE_TIMEOUT_SECONDS = 8;
	private const REMOTE_CONNECT_TIMEOUT_SECONDS = 3;

	/**
	 * Max time a deduplicated waiter sleeps before giving up and serving a
	 * fallback. Tile-grids load in parallel; humans don't tolerate >2s tiles.
	 */
	private const DEDUP_WAIT_TIMEOUT_MS = 2000;
	private const DEDUP_POLL_INTERVAL_MS = 50;

	/**
	 * In-flight lock TTL. Long enough for a slow remote (Collabora cold
	 * start), short enough to recover if a worker crashed mid-fetch.
	 */
	private const INFLIGHT_LOCK_TTL_SECONDS = 15;

	/**
	 * Max concurrent outbound calls per remote host. NC's typical
	 * bruteforce / IP throttle on `publicpreview` is in the low-tens; we
	 * stay well below that ceiling so a busy IntraVox-server doesn't trip
	 * the remote's own protection.
	 */
	private const PER_REMOTE_CONCURRENCY_CAP = 8;
	private const PER_REMOTE_SLOT_TTL_SECONDS = 30;

	private \OCP\ICache $lockCache;
	private \OCP\ICache $semaphoreCache;

	public function __construct(
		ICacheFactory $cacheFactory,
		private IClientService $httpClient,
		private IAppData $appData,
		private LoggerInterface $logger,
	) {
		$this->lockCache = $cacheFactory->createDistributed('intravox-fedpreview-lock');
		$this->semaphoreCache = $cacheFactory->createDistributed('intravox-fedpreview-sema');
	}

	/**
	 * Fetch (or serve from cache) the preview bytes for a federated file.
	 * Returns the raw image bytes, or null if no preview can be produced.
	 *
	 * @param array{remote: string, token: string, remotePath: string} $federated
	 */
	public function getPreview(int $fileId, string $etag, int $x, int $y, array $federated): ?string {
		// Layer 1: cache hit?
		$cached = $this->readFromCache($fileId, $etag, $x, $y);
		if ($cached !== null) {
			return $cached;
		}

		// Layer 2: in-flight dedup. The winner of `add` performs the remote
		// fetch; others wait for the cache to materialise.
		$lockKey = $this->cacheKey($fileId, $etag, $x, $y);
		if (!$this->lockCache->add($lockKey, '1', self::INFLIGHT_LOCK_TTL_SECONDS)) {
			$waited = $this->waitForCache($fileId, $etag, $x, $y);
			if ($waited !== null) {
				return $waited;
			}
			// Lock holder didn't materialise the cache in time. Fall through:
			// we try the remote ourselves rather than serve a fallback, but we
			// don't compete for the lock (next request will).
		}

		try {
			// Layer 3: per-remote semaphore. Reserve a slot or back off.
			$slot = $this->acquireSlot($federated['remote']);
			if ($slot === null) {
				$this->logger->info('FederatedPreview: per-remote cap hit, serving fallback', [
					'remote' => $federated['remote'],
					'fid' => $fileId,
				]);
				return null;
			}

			try {
				$bytes = $this->fetchRemote($federated, $x, $y);
				if ($bytes !== null) {
					try {
						$this->writeToCache($fileId, $etag, $x, $y, $bytes);
					} catch (\Throwable $e) {
						$this->logger->warning('FederatedPreview: cache write failed', [
							'fid' => $fileId,
							'err' => $e->getMessage(),
						]);
					}
				}
				return $bytes;
			} finally {
				$this->releaseSlot($federated['remote'], $slot);
			}
		} finally {
			$this->lockCache->remove($lockKey);
		}
	}

	private function waitForCache(int $fileId, string $etag, int $x, int $y): ?string {
		$deadline = (int)(microtime(true) * 1000) + self::DEDUP_WAIT_TIMEOUT_MS;
		while ((int)(microtime(true) * 1000) < $deadline) {
			usleep(self::DEDUP_POLL_INTERVAL_MS * 1000);
			$bytes = $this->readFromCache($fileId, $etag, $x, $y);
			if ($bytes !== null) {
				return $bytes;
			}
		}
		return null;
	}

	/**
	 * Try to take a slot in the per-remote semaphore. Returns a token
	 * identifying the slot on success, null when the cap is hit.
	 *
	 * Implementation: store a flat list of `[slotId => acquiredAt]` under one
	 * key per remote, with optimistic concurrency via `cas()` when available.
	 * NC's ICache doesn't expose cas() universally, so we fall back to a
	 * race-tolerant counter — over-counting is harmless (caps stay near the
	 * configured number), under-counting can briefly let the cap be exceeded
	 * (cleared on the next TTL window).
	 */
	private function acquireSlot(string $remote): ?string {
		$key = 'slots:' . md5($remote);
		$now = time();
		$cutoff = $now - self::PER_REMOTE_SLOT_TTL_SECONDS;

		$raw = $this->semaphoreCache->get($key);
		$slots = is_string($raw) ? (json_decode($raw, true) ?: []) : (is_array($raw) ? $raw : []);
		// Expire stale entries (slot holders that crashed without releasing).
		$slots = array_filter($slots, fn($ts) => $ts >= $cutoff);

		if (count($slots) >= self::PER_REMOTE_CONCURRENCY_CAP) {
			return null;
		}

		$slotId = bin2hex(random_bytes(8));
		$slots[$slotId] = $now;
		$this->semaphoreCache->set($key, json_encode($slots), self::PER_REMOTE_SLOT_TTL_SECONDS);
		return $slotId;
	}

	private function releaseSlot(string $remote, string $slotId): void {
		$key = 'slots:' . md5($remote);
		$raw = $this->semaphoreCache->get($key);
		$slots = is_string($raw) ? (json_decode($raw, true) ?: []) : (is_array($raw) ? $raw : []);
		unset($slots[$slotId]);
		if (empty($slots)) {
			$this->semaphoreCache->remove($key);
		} else {
			$this->semaphoreCache->set($key, json_encode($slots), self::PER_REMOTE_SLOT_TTL_SECONDS);
		}
	}

	private function fetchRemote(array $federated, int $x, int $y): ?string {
		$url = $federated['remote']
			. '/index.php/apps/files_sharing/publicpreview/'
			. rawurlencode($federated['token'])
			. '?x=' . $x
			. '&y=' . $y
			. '&a=true'
			. '&file=' . rawurlencode($federated['remotePath']);

		try {
			$client = $this->httpClient->newClient();
			$response = $client->get($url, [
				'timeout' => self::REMOTE_TIMEOUT_SECONDS,
				'connect_timeout' => self::REMOTE_CONNECT_TIMEOUT_SECONDS,
				'headers' => [
					'User-Agent' => 'IntraVox-Preview-Proxy/' . Application::APP_ID,
				],
				'allow_redirects' => false,
			]);
			if ($response->getStatusCode() !== 200) {
				return null;
			}
			$ctype = strtolower((string)$response->getHeader('Content-Type'));
			if ($ctype === '' || !str_starts_with($ctype, 'image/')) {
				return null;
			}
			$bytes = (string)$response->getBody();
			return $bytes !== '' ? $bytes : null;
		} catch (\Throwable $e) {
			$this->logger->debug('FederatedPreview: remote fetch failed', [
				'url' => $url,
				'err' => $e->getMessage(),
			]);
			return null;
		}
	}

	private function cacheKey(int $fileId, string $etag, int $x, int $y): string {
		$safeEtag = preg_replace('/[^a-zA-Z0-9]/', '', $etag) ?? '';
		if ($safeEtag === '') {
			$safeEtag = '0';
		}
		return sprintf('%d-%s-%d-%d.png', $fileId, $safeEtag, $x, $y);
	}

	private function bucket(): ISimpleFolder {
		try {
			return $this->appData->getFolder(self::CACHE_BUCKET);
		} catch (NotFoundException $e) {
			return $this->appData->newFolder(self::CACHE_BUCKET);
		}
	}

	private function readFromCache(int $fileId, string $etag, int $x, int $y): ?string {
		try {
			$file = $this->bucket()->getFile($this->cacheKey($fileId, $etag, $x, $y));
			return $file->getContent();
		} catch (NotFoundException $e) {
			return null;
		} catch (\Throwable $e) {
			$this->logger->warning('FederatedPreview: cache read failed', [
				'fid' => $fileId,
				'err' => $e->getMessage(),
			]);
			return null;
		}
	}

	private function writeToCache(int $fileId, string $etag, int $x, int $y, string $bytes): void {
		$bucket = $this->bucket();
		$key = $this->cacheKey($fileId, $etag, $x, $y);
		try {
			$file = $bucket->getFile($key);
		} catch (NotFoundException $e) {
			$file = $bucket->newFile($key);
		}
		$file->putContent($bytes);
	}
}
