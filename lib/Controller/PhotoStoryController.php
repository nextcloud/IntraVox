<?php
declare(strict_types=1);

namespace OCA\IntraVox\Controller;

use OCA\IntraVox\AppInfo\Application;
use OCA\IntraVox\Http\VideoRangeResponse;
use OCA\IntraVox\Service\PhotoStoryService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\Attribute\NoCSRFRequired;
use OCP\AppFramework\Http\DataResponse;
use OCP\Files\File;
use OCP\Files\IRootFolder;
use OCP\IConfig;
use OCP\IRequest;
use OCP\IUserSession;
use Psr\Log\LoggerInterface;

class PhotoStoryController extends Controller {
	/** Allowed MetaVox filter operators (mirror PhotoStoryService::ALLOWED_OPS). */
	private const ALLOWED_OPS = ['equals', 'contains', 'in', 'year_equals'];

	public function __construct(
		string $appName,
		IRequest $request,
		private PhotoStoryService $service,
		private IConfig $config,
		private IRootFolder $rootFolder,
		private IUserSession $userSession,
		private LoggerInterface $logger,
	) {
		parent::__construct($appName, $request);
	}

	/**
	 * Build an ETag scoped to the current user. Critical: without the user-id
	 * the same `folder` path resolves to different files for different users
	 * (groupfolder ACLs, personal vs shared), so two users could collide on the
	 * same ETag and receive each other's 304-cached responses through shared
	 * caches or a misconfigured proxy.
	 *
	 * @param array<string, mixed> $parts
	 */
	private function buildEtag(array $parts): string {
		$user = $this->userSession->getUser();
		$parts['u'] = $user !== null ? $user->getUID() : '';
		// Sort filter rows so cosmetic re-ordering doesn't invalidate the cache.
		if (isset($parts['flt']) && is_array($parts['flt'])) {
			$flt = $parts['flt'];
			usort($flt, function ($a, $b) {
				$af = (string)($a['field'] ?? '') . '|' . (string)($a['op'] ?? '');
				$bf = (string)($b['field'] ?? '') . '|' . (string)($b['op'] ?? '');
				return strcmp($af, $bf);
			});
			$parts['flt'] = $flt;
		}
		return '"' . md5((string)json_encode($parts, JSON_UNESCAPED_SLASHES)) . '"';
	}

	/**
	 * Build the map-settings payload (tile URL, attribution, max-zoom, global enabled-flag).
	 *
	 * Centralized here so /capabilities, /photos, and /clusters all return the same shape.
	 *
	 * @return array{enabled: bool, tile_url: string, attribution: string, max_zoom: int}
	 */
	private function mapSettings(): array {
		$enabled = $this->config->getAppValue(
			Application::APP_ID,
			'photostory.map.enabled',
			'yes',
		) !== 'no';
		$tileUrl = $this->config->getAppValue(
			Application::APP_ID,
			'photostory.tiles.url',
			'https://tile.openstreetmap.org/{z}/{x}/{y}.png',
		);
		$attribution = $this->config->getAppValue(
			Application::APP_ID,
			'photostory.tiles.attribution',
			'© OpenStreetMap contributors',
		);
		$maxZoom = (int)$this->config->getAppValue(
			Application::APP_ID,
			'photostory.tiles.max_zoom',
			'19',
		);
		return [
			'enabled' => $enabled,
			'tile_url' => $tileUrl,
			'attribution' => $attribution,
			'max_zoom' => $maxZoom,
		];
	}

	/**
	 * GET /api/photo-story/photos?folder=<path>&mode=...&filters=<json>&limit=N
	 * folder is optional in cross-folder mode (requires MetaVox + filters).
	 */
	#[NoAdminRequired]
	#[NoCSRFRequired]
	public function photos(): DataResponse {
		$folder = (string)$this->request->getParam('folder', '');
		$mode = (string)$this->request->getParam('mode', 'timeline');
		$limitRaw = $this->request->getParam('limit', null);
		$limit = ($limitRaw !== null && $limitRaw !== '') ? (int)$limitRaw : null;

		// Pagination params (folder + timeline/grid mode only). Frontend infinite-scrolls.
		$offsetRaw = $this->request->getParam('offset', null);
		$offset = ($offsetRaw !== null && $offsetRaw !== '') ? max(0, (int)$offsetRaw) : 0;
		$pageSizeRaw = $this->request->getParam('pageSize', null);
		$pageSize = ($pageSizeRaw !== null && $pageSizeRaw !== '') ? max(1, min(500, (int)$pageSizeRaw)) : 200;
		$sortOrderRaw = (string)$this->request->getParam('sortOrder', 'desc');
		$sortOrder = ($sortOrderRaw === 'asc') ? 'asc' : 'desc';
		// Sort-by: file column (mtime/name/size), NC core taken_at, or any MetaVox
		// field name. Validation happens in the service (whitelisting for file
		// columns, MetaVox fields are matched against fetched data so injection
		// is structurally impossible — it only affects which row property gets
		// compared).
		$sortByRaw = trim((string)$this->request->getParam('sortBy', 'mtime'));
		$sortBy = preg_match('/^[a-z][a-z0-9_]{0,63}$/i', $sortByRaw) ? $sortByRaw : 'mtime';
		// Frontend can pass the total it received from the first page to avoid a
		// full COUNT(*) on each subsequent fetchMore() call. Untrusted — capped
		// downstream and only used as a hint, not a security boundary.
		$totalHintRaw = $this->request->getParam('total', null);
		$totalHint = ($totalHintRaw !== null && $totalHintRaw !== '' && $offset > 0) ? max(0, (int)$totalHintRaw) : null;

		// Default-true: hide RAW sidecars when a JPG/HEIC variant exists. Existing
		// widgets without this param get the dedup, which matches the rollout
		// intent. Explicit "0"/"false"/"no" disables it; everything else keeps
		// the default.
		$dedupRaw = true;
		$dedupRawRaw = $this->request->getParam('hideRawDuplicates', null);
		if ($dedupRawRaw !== null && $dedupRawRaw !== '') {
			$dedupRaw = !in_array(strtolower((string)$dedupRawRaw), ['0', 'false', 'no', 'off'], true);
		}

		$filtersRaw = $this->request->getParam('filters', null);
		$filters = [];
		if ($filtersRaw !== null && $filtersRaw !== '') {
			// Hard cap to prevent JSON-decode bombs. 16KB fits ~50 filter rows
			// of reasonable length; the sanitizer caps the row-count anyway.
			if (is_string($filtersRaw) && strlen($filtersRaw) > 16384) {
				return new DataResponse(['error' => 'Filter payload too large'], Http::STATUS_BAD_REQUEST);
			}
			$decoded = is_string($filtersRaw) ? json_decode($filtersRaw, true) : $filtersRaw;
			if (is_array($decoded)) {
				$filters = $this->sanitizeFilters($decoded);
			}
		}

		$allowedModes = ['timeline', 'highlights', 'grid', 'on-this-day'];
		if (!in_array($mode, $allowedModes, true)) {
			return new DataResponse(
				['error' => 'Invalid mode'],
				Http::STATUS_BAD_REQUEST
			);
		}

		// Folder is optional in cross-folder mode, but the legacy on-this-day path still needs a folder
		// (we don't currently support cross-folder on-this-day; it's a niche case).
		if ($folder === '' && $mode === 'on-this-day') {
			return new DataResponse(
				['error' => 'on-this-day mode requires a folder'],
				Http::STATUS_BAD_REQUEST
			);
		}

		try {
			$folderName = $this->extractFolderName($folder);
			$effectiveFolder = $folder !== '' ? $folder : null;

			if ($mode === 'on-this-day') {
				$photos = $this->service->onThisDay($folder, new \DateTimeImmutable('now'));
				return new DataResponse([
					'photos' => $photos,
					'capabilities' => $this->service->getCapabilities(),
					'title' => $this->service->suggestTitle($photos, $folderName),
					'mode' => $mode,
				]);
			}

			// Folder-mode timeline, grid AND highlights go through the paged path.
			// For highlights we pull up to 2000 candidates via listPhotosPaged and
			// then score that pool — full-library scoring is not needed and risks
			// loading 50k Node objects into memory.
			$usePaged = ($effectiveFolder !== null && in_array($mode, ['timeline', 'grid', 'highlights'], true));

			if ($usePaged) {
				// Highlights: pull a larger pool (max 2000) in a single page so the
				// score+pick logic has enough candidates without loading every photo.
				$effectivePageSize = ($mode === 'highlights') ? 2000 : $pageSize;
				$paged = $this->service->listPhotosPaged($effectiveFolder, $filters, $offset, $effectivePageSize, $sortOrder, $totalHint, $sortBy, 'photos', $dedupRaw);
				$photos = $paged['photos'];
				$capabilities = $this->service->getCapabilities();
				$title = $this->service->suggestTitle($photos, $folderName);

				$payload = [
					'photos' => $photos,
					'capabilities' => $capabilities,
					'map' => $this->mapSettings(),
					'title' => $title,
					'mode' => $mode,
					'pagination' => [
						'offset' => $paged['offset'],
						'pageSize' => $paged['pageSize'],
						'total' => $paged['total'],
						'hasMore' => $paged['hasMore'],
						'sortOrder' => $paged['sortOrder'],
						'truncated' => $paged['truncated'],
					],
				];

				if ($mode === 'timeline') {
					// Match bucket-sort with paged SQL sort: use sortBy as dateField
					// so days are chronologically monotonic across pages.
					$dateField = ($sortBy === 'mtime') ? 'mtime' : 'taken_at';
					$payload['timeline'] = $this->service->groupByTimeline($photos, $sortOrder, 'day', $dateField);
				} elseif ($mode === 'highlights') {
					$max = $limit ?? 30;
					$payload['highlights'] = $this->service->selectHighlights($photos, $max);
				}

				$maxMtime = 0;
				foreach ($photos as $p) {
					if (isset($p['mtime']) && $p['mtime'] > $maxMtime) {
						$maxMtime = (int)$p['mtime'];
					}
				}
				$etag = $this->buildEtag([
					'f' => $folder, 'm' => $mode, 'l' => $limit,
					'o' => $offset, 'ps' => $pageSize, 's' => $sortOrder, 'sb' => $sortBy,
					'flt' => $filters, 'n' => count($photos), 't' => $maxMtime,
					'dr' => $dedupRaw ? 1 : 0,
				]);

				$ifNoneMatch = $this->request->getHeader('If-None-Match');
				if ($ifNoneMatch !== '' && trim($ifNoneMatch) === $etag) {
					$resp = new DataResponse(null, Http::STATUS_NOT_MODIFIED);
					$resp->addHeader('ETag', $etag);
					$resp->addHeader('Cache-Control', 'private, must-revalidate');
					return $resp;
				}
				$resp = new DataResponse($payload);
				$resp->addHeader('ETag', $etag);
				if ($maxMtime > 0) {
					$resp->addHeader('Last-Modified', gmdate('D, d M Y H:i:s', $maxMtime) . ' GMT');
				}
				$resp->addHeader('Cache-Control', 'private, must-revalidate');
				return $resp;
			}

			// Legacy path: highlights, cross-folder. Still loads the full set into memory.
			$photos = $this->service->listPhotos($effectiveFolder, $filters, $limit);
			$capabilities = $this->service->getCapabilities();
			$title = $this->service->suggestTitle($photos, $folderName);

			$payload = [
				'photos' => $photos,
				'capabilities' => $capabilities,
				'map' => $this->mapSettings(),
				'title' => $title,
				'mode' => $mode,
			];

			if ($mode === 'timeline') {
				$payload['timeline'] = $this->service->groupByTimeline($photos, 'asc', 'day', 'taken_at');
			} elseif ($mode === 'highlights') {
				$max = $limit ?? 30;
				$payload['highlights'] = $this->service->selectHighlights($photos, $max);
			}

			$maxMtime = 0;
			foreach ($photos as $p) {
				if (isset($p['mtime']) && $p['mtime'] > $maxMtime) {
					$maxMtime = (int)$p['mtime'];
				}
			}
			$etag = $this->buildEtag([
				'f' => $folder, 'm' => $mode, 'l' => $limit,
				'flt' => $filters, 'n' => count($photos), 't' => $maxMtime,
			]);

			$ifNoneMatch = $this->request->getHeader('If-None-Match');
			if ($ifNoneMatch !== '' && trim($ifNoneMatch) === $etag) {
				$resp = new DataResponse(null, Http::STATUS_NOT_MODIFIED);
				$resp->addHeader('ETag', $etag);
				if ($maxMtime > 0) {
					$resp->addHeader('Last-Modified', gmdate('D, d M Y H:i:s', $maxMtime) . ' GMT');
				}
				$resp->addHeader('Cache-Control', 'private, must-revalidate');
				return $resp;
			}

			$resp = new DataResponse($payload);
			$resp->addHeader('ETag', $etag);
			if ($maxMtime > 0) {
				$resp->addHeader('Last-Modified', gmdate('D, d M Y H:i:s', $maxMtime) . ' GMT');
			}
			$resp->addHeader('Cache-Control', 'private, must-revalidate');
			return $resp;
		} catch (\OCP\Files\NotFoundException $e) {
			// Folder doesn't exist for this user (renamed, deleted, never
			// existed, OR the defensive guard in PhotoStoryService caught a
			// silent resolve-to-root for an unauthorised subpath). Return 404
			// + empty payload so the widget renders the "no access" empty-state.
			return new DataResponse(
				[
					'error' => 'Folder not found',
					'reason' => 'folder_not_accessible',
					'photos' => [],
					'capabilities' => null,
				],
				Http::STATUS_NOT_FOUND
			);
		} catch (\Throwable $e) {
			$this->logger->error('PhotoStoryController: photos failed', [
				'folder' => $folder,
				'mode' => $mode,
				'filters' => $filters,
				'error' => $e->getMessage(),
			]);
			return new DataResponse(
				['error' => 'Unable to load photos'],
				Http::STATUS_INTERNAL_SERVER_ERROR
			);
		}
	}

	/**
	 * GET /api/photo-story/metavox-fields
	 * Returns `exif_*` field definitions + their distinct-value options for the filter-builder.
	 */
	#[NoAdminRequired]
	#[NoCSRFRequired]
	public function metaVoxFields(): DataResponse {
		try {
			if (!$this->service->isMetaVoxAvailable()) {
				return new DataResponse(['fields' => [], 'available' => false]);
			}
			$fields = $this->service->getMetaVoxFieldsForPhotoStory();
			return new DataResponse([
				'fields' => $fields,
				'available' => true,
			]);
		} catch (\Throwable $e) {
			$this->logger->error('PhotoStoryController: metaVoxFields failed', [
				'error' => $e->getMessage(),
			]);
			return new DataResponse([
				'fields' => [],
				'available' => false,
				'error' => $e->getMessage(),
			]);
		}
	}

	/**
	 * Validate + coerce a list of filter rows. Drops any row that's malformed.
	 *
	 * @param array<int, mixed> $raw
	 * @return array<int, array{field: string, op: string, value: mixed}>
	 */
	private function sanitizeFilters(array $raw): array {
		$out = [];
		foreach ($raw as $entry) {
			if (!is_array($entry)) {
				continue;
			}
			$field = isset($entry['field']) ? (string)$entry['field'] : '';
			$op = isset($entry['op']) ? (string)$entry['op'] : '';
			$value = $entry['value'] ?? '';

			if ($field === '' || !preg_match('/^exif_[a-z_]+$/', $field)) {
				continue;
			}
			if (!in_array($op, self::ALLOWED_OPS, true)) {
				continue;
			}

			if (is_array($value)) {
				$coerced = [];
				foreach ($value as $v) {
					$s = is_scalar($v) ? trim((string)$v) : '';
					if ($s !== '') {
						$coerced[] = mb_substr($s, 0, 200);
					}
				}
				if (empty($coerced)) {
					continue;
				}
				$value = array_values($coerced);
			} else {
				$s = is_scalar($value) ? trim((string)$value) : '';
				if ($s === '') {
					continue;
				}
				$value = mb_substr($s, 0, 200);
			}

			$out[] = ['field' => $field, 'op' => $op, 'value' => $value];
		}
		return $out;
	}

	/**
	 * GET /api/photo-story/photo-exif?file_id=N
	 * Lazy EXIF lookup for a single file — used by the lightbox details pane
	 * when MetaVox didn't supply camera/gps/taken_at fields.
	 */
	#[NoAdminRequired]
	#[NoCSRFRequired]
	public function photoExif(): DataResponse {
		$fileId = (int)$this->request->getParam('file_id', 0);
		if ($fileId <= 0) {
			return new DataResponse(['error' => 'Missing or invalid file_id'], Http::STATUS_BAD_REQUEST);
		}
		try {
			$data = $this->service->getExifForFile($fileId);
			return new DataResponse($data);
		} catch (\Throwable $e) {
			$this->logger->info('PhotoStoryController: photoExif failed', [
				'file_id' => $fileId,
				'error' => $e->getMessage(),
			]);
			return new DataResponse(['error' => 'File not found or no EXIF data'], Http::STATUS_NOT_FOUND);
		}
	}

	/**
	 * GET /api/photo-story/video?file_id=N
	 *
	 * Streams a video file's bytes to the browser. The video tag in the lightbox
	 * points at this endpoint instead of trying to construct a WebDAV URL — that
	 * avoids the path-resolution headache for groupfolder-mounted files.
	 *
	 * ACL is enforced via $userFolder->getById() — only files the current user
	 * can read are returned. Range requests (HTTP 206) for seeking are handled
	 * by NC's StreamResponse + Apache/nginx upstream.
	 */
	#[NoAdminRequired]
	#[NoCSRFRequired]
	public function video(): \OCP\AppFramework\Http\Response {
		$fileId = (int)$this->request->getParam('file_id', 0);
		if ($fileId <= 0) {
			return new DataResponse(['error' => 'Missing or invalid file_id'], Http::STATUS_BAD_REQUEST);
		}
		$user = $this->userSession->getUser();
		if ($user === null) {
			return new DataResponse(['error' => 'Authentication required'], Http::STATUS_UNAUTHORIZED);
		}
		try {
			$userFolder = $this->rootFolder->getUserFolder($user->getUID());
			$nodes = $userFolder->getById($fileId);
			if (empty($nodes)) {
				return new DataResponse(['error' => 'Not found or no access'], Http::STATUS_NOT_FOUND);
			}
			$node = $nodes[0];
			if (!($node instanceof File)) {
				return new DataResponse(['error' => 'Not a file'], Http::STATUS_BAD_REQUEST);
			}
			$mime = (string)$node->getMimeType();
			if (!str_starts_with($mime, 'video/')) {
				return new DataResponse(['error' => 'Not a video'], Http::STATUS_BAD_REQUEST);
			}

			$size = (int)$node->getSize();
			$rangeHeader = (string)$this->request->getHeader('Range');

			if ($rangeHeader !== '' && preg_match('/^bytes=(\d*)-(\d*)$/', $rangeHeader, $m)) {
				// Parse and honour the Range request — required for scrubbing /
				// seek-by-time in <video>. Without this every seek downloads from
				// byte 0 and pins a php-fpm worker for the whole file.
				$start = $m[1] === '' ? null : (int)$m[1];
				$end = $m[2] === '' ? null : (int)$m[2];

				if ($start === null && $end !== null) {
					// "bytes=-500" → last 500 bytes.
					$start = max(0, $size - $end);
					$end = $size - 1;
				} else {
					if ($start === null) {
						$start = 0;
					}
					if ($end === null || $end >= $size) {
						$end = $size - 1;
					}
				}

				if ($start < 0 || $start >= $size || $end < $start) {
					$resp = new DataResponse(['error' => 'Invalid range'], Http::STATUS_REQUESTED_RANGE_NOT_SATISFIABLE);
					$resp->addHeader('Content-Range', 'bytes */' . $size);
					return $resp;
				}

				$length = ($end - $start) + 1;
				$fh = $node->fopen('rb');
				if (!is_resource($fh)) {
					return new DataResponse(['error' => 'Cannot open file'], Http::STATUS_INTERNAL_SERVER_ERROR);
				}

				$resp = new VideoRangeResponse($fh, $start, $length, $mime);
				$resp->setStatus(Http::STATUS_PARTIAL_CONTENT);
				$resp->addHeader('Content-Type', $mime);
				$resp->addHeader('Content-Length', (string)$length);
				$resp->addHeader('Content-Range', sprintf('bytes %d-%d/%d', $start, $end, $size));
				$resp->addHeader('Accept-Ranges', 'bytes');
				$resp->addHeader('Cache-Control', 'private, max-age=3600');
				return $resp;
			}

			// No Range header: full file. Browsers will typically send Range later
			// for scrubbing, so advertise Accept-Ranges.
			$fh = $node->fopen('rb');
			if (!is_resource($fh)) {
				return new DataResponse(['error' => 'Cannot open file'], Http::STATUS_INTERNAL_SERVER_ERROR);
			}
			$resp = new VideoRangeResponse($fh, 0, $size, $mime);
			$resp->addHeader('Content-Type', $mime);
			$resp->addHeader('Content-Length', (string)$size);
			$resp->addHeader('Accept-Ranges', 'bytes');
			$resp->addHeader('Cache-Control', 'private, max-age=3600');
			return $resp;
		} catch (\Throwable $e) {
			$this->logger->warning('PhotoStoryController: video stream failed', [
				'file_id' => $fileId,
				'error' => $e->getMessage(),
			]);
			return new DataResponse(['error' => 'Unable to stream video'], Http::STATUS_INTERNAL_SERVER_ERROR);
		}
	}

	/**
	 * GET /api/photo-story/clusters?folder=<path>
	 */
	#[NoAdminRequired]
	#[NoCSRFRequired]
	public function clusters(): DataResponse {
		$folder = (string)$this->request->getParam('folder', '');
		if ($folder === '') {
			return new DataResponse(
				['error' => 'Missing folder parameter'],
				Http::STATUS_BAD_REQUEST
			);
		}

		try {
			// Paged enumeration capped at 5000 photos. Cluster generation is
			// effectively useless beyond a few thousand markers (Leaflet would
			// drop FPS); the prior unpaged path could OOM on 50k+ folders.
			$paged = $this->service->listPhotosPaged($folder, [], 0, 5000, 'desc', null, 'mtime');
			$clusters = $this->service->getLocationClusters($paged['photos']);
			return new DataResponse([
				'clusters' => $clusters,
				'capabilities' => $this->service->getCapabilities(),
				'map' => $this->mapSettings(),
				'truncated' => !empty($paged['truncated']),
			]);
		} catch (\Throwable $e) {
			$this->logger->error('PhotoStoryController: clusters failed', [
				'folder' => $folder,
				'error' => $e->getMessage(),
			]);
			return new DataResponse(
				['error' => 'Unable to load location clusters'],
				Http::STATUS_INTERNAL_SERVER_ERROR
			);
		}
	}

	/**
	 * GET /api/photo-story/capabilities
	 */
	#[NoAdminRequired]
	#[NoCSRFRequired]
	public function capabilities(): DataResponse {
		try {
			return new DataResponse([
				'capabilities' => $this->service->getCapabilities(),
				'metaVoxAvailable' => $this->service->isMetaVoxAvailable(),
				'map' => $this->mapSettings(),
			]);
		} catch (\Throwable $e) {
			$this->logger->error('PhotoStoryController: capabilities failed', [
				'error' => $e->getMessage(),
			]);
			return new DataResponse(
				['error' => 'Unable to load capabilities'],
				Http::STATUS_INTERNAL_SERVER_ERROR
			);
		}
	}

	private function extractFolderName(string $folderPath): string {
		$trimmed = rtrim($folderPath, '/');
		if ($trimmed === '') {
			return '';
		}
		$parts = explode('/', $trimmed);
		return (string)end($parts);
	}
}
