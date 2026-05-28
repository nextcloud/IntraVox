<?php
declare(strict_types=1);

namespace OCA\IntraVox\Service;

use OCA\IntraVox\AppInfo\Application;
use OCA\IntraVox\Service\PhotoStory\ExifReader;
use OCA\IntraVox\Service\PhotoStory\GeocodeCache;
use OCA\IntraVox\Service\PhotoStory\NCFilesMetadataReader;
use OCP\App\IAppManager;
use OCP\Files\Folder;
use OCP\Files\IMimeTypeLoader;
use OCP\Files\IRootFolder;
use OCP\IConfig;
use OCP\IL10N;
use OCP\IDBConnection;
use OCP\ICache;
use OCP\ICacheFactory;
use OCP\IUserSession;
use Psr\Log\LoggerInterface;

/**
 * Photo Story backend: lists photos in a user folder, enriches with MetaVox (if present)
 * or EXIF (fallback), and exposes timeline / highlights / cluster / on-this-day operations.
 */
class PhotoStoryService {
	/**
	 * Photo + video mimetypes — the default content set for PhotoStoryWidget.
	 * Kept as MEDIA_MIMES for backwards-compatibility; new code should prefer
	 * `mimesForCategory()` for category-aware lookup.
	 */
	private const MEDIA_MIMES = [
		// Common web image formats
		'image/jpeg',
		'image/png',
		'image/webp',
		'image/gif',
		'image/svg+xml',
		'image/bmp',
		// Photography-grade formats
		'image/heic',
		'image/heif',
		'image/tiff',
		// Raw camera formats
		'image/x-canon-cr2',
		'image/x-canon-cr3',
		'image/x-nikon-nef',
		'image/x-sony-arw',
		'image/x-adobe-dng',
		'image/x-dcraw',
		// Video
		'video/quicktime',
		'video/mp4',
		'video/x-msvideo',
		'video/mpeg',
		'video/x-ms-wmv',
		'video/webm',
	];

	/**
	 * Document mimetypes for FileStoryWidget. Covers the formats users
	 * typically want to surface in an intranet "documents on this team"-style
	 * widget. Deliberately excludes images/video to avoid overlap with the
	 * photo widget.
	 */
	private const DOC_MIMES = [
		'application/pdf',
		// Office Open XML
		'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
		'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
		'application/vnd.openxmlformats-officedocument.presentationml.presentation',
		// Legacy Office binaries
		'application/msword',
		'application/vnd.ms-excel',
		'application/vnd.ms-powerpoint',
		// OpenDocument
		'application/vnd.oasis.opendocument.text',
		'application/vnd.oasis.opendocument.spreadsheet',
		'application/vnd.oasis.opendocument.presentation',
		// Text + markdown
		'text/markdown',
		'text/plain',
		'text/csv',
		'text/html',
		// Misc structured docs
		'application/rtf',
		'application/epub+zip',
	];

	private const HIGH_VALUE_SUBJECTS = [
		'Architecture', 'Landscape', 'Portrait', 'People',
		'Building', 'Nature', 'Animal', 'Cat', 'Dog', 'Food',
	];

	/**
	 * Translatable month names. Index 1..12. Strings stay in English here so the
	 * gettext extractor picks them up; runtime translation happens via $this->l10n.
	 */
	private const MONTH_KEYS = [
		1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April',
		5 => 'May', 6 => 'June', 7 => 'July', 8 => 'August',
		9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December',
	];

	private function localizedMonth(int $month): string {
		$key = self::MONTH_KEYS[$month] ?? '';
		// IL10N->t() handles the active user-locale lookup via NC's translation
		// chain. Strings without an active translation fall through to English,
		// which matches the App Store expectation for unstaffed locales.
		return $this->l10n->t($key);
	}

	/** Hard cap on photos returned in cross-folder mode to avoid runaway queries. */
	private const CROSS_FOLDER_HARD_CAP = 5000;

	/** Allowed filter operators (mirrored in PhotoStoryController::sanitizeFilters). */
	private const ALLOWED_OPS = ['equals', 'contains', 'in', 'year_equals'];

	/**
	 * "Well-known" MetaVox fields that PhotoStoryWidget knows how to map into
	 * special photo-row slots (taken_at, location, country, camera, people,
	 * subjects). These are always fetched alongside any extra fields the
	 * caller asks for, so the photo-row stays consistent regardless of which
	 * MetaVox fields the user happens to have configured.
	 *
	 * Other fields (custom user-created ones like project, status, author)
	 * are fetched on-demand and exposed via the generic `meta` dict on the
	 * photo row.
	 */
	private const WELL_KNOWN_PHOTO_FIELDS = [
		'exif_taken_at', 'exif_location', 'exif_country',
		'exif_camera', 'exif_people', 'exif_subjects',
	];

	private ?bool $metaVoxAvailable = null;

	/** @var mixed|null Lazy-loaded MetaVox FieldService (class-typed at runtime only). */
	private mixed $fieldServiceCache = null;
	private bool $fieldServiceResolved = false;

	/** Request-level cache for getMetaVoxFieldsForPhotoStory(). */
	private ?array $cachedExifFields = null;

	/** Distributed cache for cross-request memoization of expensive computations. */
	private ?ICache $cache = null;

	/** Request-level counter for eager-EXIF cap (Phase 2.9.A / 3.0). */
	private int $exifEagerCount = 0;

	public function __construct(
		private IDBConnection $db,
		private IAppManager $appManager,
		private IRootFolder $rootFolder,
		private IUserSession $userSession,
		private ExifReader $exifReader,
		private GeocodeCache $geocodeCache,
		private NCFilesMetadataReader $ncMeta,
		private IMimeTypeLoader $mimeTypeLoader,
		private IConfig $config,
		private ICacheFactory $cacheFactory,
		private IL10N $l10n,
		private LoggerInterface $logger,
	) {
		// createDistributed() returns Redis when configured, APCu otherwise,
		// and a NoOp cache in dev/test setups. Never null.
		$this->cache = $this->cacheFactory->createDistributed('intravox.photostory.');
	}

	/**
	 * MetaVox availability with request-level caching.
	 * Mirrors MetaVoxImportService::isAvailable() pattern.
	 */
	public function isMetaVoxAvailable(): bool {
		if ($this->metaVoxAvailable === null) {
			try {
				$this->metaVoxAvailable =
					$this->appManager->isInstalled('metavox')
					&& $this->appManager->isEnabledForUser('metavox');
			} catch (\Throwable $e) {
				$this->logger->debug('PhotoStoryService: MetaVox detection failed: ' . $e->getMessage());
				$this->metaVoxAvailable = false;
			}
		}
		return $this->metaVoxAvailable;
	}

	/**
	 * Lazily resolve MetaVox's FieldService through the server container.
	 * Returns null when MetaVox isn't installed/enabled or the service can't
	 * be instantiated (e.g. version mismatch). The lookup happens at most
	 * once per request thanks to $fieldServiceResolved.
	 *
	 * Pattern mirrors MetaVoxImportService — direct DI would force a hard
	 * dependency on MetaVox which we explicitly want to avoid.
	 */
	private function getMetaVoxFieldService(): mixed {
		if ($this->fieldServiceResolved) {
			return $this->fieldServiceCache;
		}
		$this->fieldServiceResolved = true;
		if (!$this->isMetaVoxAvailable()) {
			return $this->fieldServiceCache = null;
		}
		try {
			$this->fieldServiceCache = \OC::$server->get(\OCA\MetaVox\Service\FieldService::class);
		} catch (\Throwable $e) {
			$this->logger->debug('PhotoStoryService: MetaVox FieldService not loadable: ' . $e->getMessage());
			$this->fieldServiceCache = null;
		}
		return $this->fieldServiceCache;
	}

	/**
	 * @return array{hasDate: bool, hasLocation: bool, hasPeople: bool, hasSubjects: bool}
	 */
	public function getCapabilities(): array {
		$mv = $this->isMetaVoxAvailable();
		return [
			'hasDate' => true,
			'hasLocation' => true,  // exiftool/PEL can extract GPS too
			'hasPeople' => $mv,
			'hasSubjects' => $mv,
		];
	}

	/**
	 * Public capability-helper for the editor: returns federated-share info
	 * for a given folder path, or null when the folder is local. Used by
	 * FileStoryWidgetEditor to show an info-message when the user picks
	 * a federated mount as source (MetaVox values aren't reachable on
	 * remote instances; we render gracefully without them).
	 *
	 * @return ?array{remote: string, mount_point: string}
	 */
	public function describeFederatedSource(string $folderPath): ?array {
		$user = $this->userSession->getUser();
		if ($user === null) {
			return null;
		}
		try {
			$userFolder = $this->rootFolder->getUserFolder($user->getUID());
			$node = $userFolder->get($folderPath);
			if (!($node instanceof Folder)) {
				return null;
			}
			return $this->extractFederatedShareInfo($node);
		} catch (\Throwable $e) {
			return null;
		}
	}

	/**
	 * List media files, enriched with metadata.
	 *
	 * Two operating modes:
	 *  - Folder-driven: $folderPath given. Lists media in that folder; if MetaVox available
	 *    + filters provided, also applies the filters as a post-filter step.
	 *  - MetaVox-driven cross-folder: $folderPath null/empty. Requires MetaVox + at least
	 *    one filter; queries oc_metavox_file_gf_meta for matching file_ids across all
	 *    groupfolders, then permission-checks each via $userFolder->getById().
	 *
	 * @param string|null $folderPath Optional folder; null/empty = cross-folder mode (MetaVox required)
	 * @param array<int, array{field: string, op: string, value: mixed}> $metaVoxFilters
	 * @return array<int, array<string, mixed>>
	 */
	public function listPhotos(?string $folderPath = null, array $metaVoxFilters = [], ?int $limit = null): array {
		$user = $this->userSession->getUser();
		if ($user === null) {
			throw new \RuntimeException('Authentication required');
		}
		$userId = $user->getUID();
		$userFolder = $this->rootFolder->getUserFolder($userId);

		// Reset eager-EXIF counter at the start of each listPhotos() call.
		$this->exifEagerCount = 0;

		$folderPath = ($folderPath !== null) ? trim($folderPath) : '';
		$crossFolderMode = ($folderPath === '');

		if ($crossFolderMode) {
			if (!$this->isMetaVoxAvailable()) {
				throw new \RuntimeException('Cross-folder mode requires MetaVox');
			}
			if (empty($metaVoxFilters)) {
				throw new \RuntimeException('At least one filter required in cross-folder mode');
			}
			return $this->listPhotosCrossFolder($metaVoxFilters, $limit, $userFolder);
		}

		return $this->listPhotosFolder($folderPath, $metaVoxFilters, $limit, $userFolder);
	}

	/**
	 * Paged variant of listPhotos for folder-driven mode.
	 *
	 * Returns one slice of the folder's media files, sorted server-side. Critical
	 * for libraries with thousands of photos: avoids loading 5000+ rows into the
	 * payload (browser freeze) and avoids hitting NC QueryBuilder's IN-list cap
	 * downstream — only the slice is enriched with NC core / MetaVox metadata.
	 *
	 * Cross-folder mode and on-this-day still go through listPhotos() for now.
	 *
	 * @return array{photos: array<int, array<string, mixed>>, total: int, offset: int, pageSize: int, hasMore: bool, sortOrder: string, truncated: bool}
	 */
	public function listPhotosPaged(
		string $folderPath,
		array $metaVoxFilters = [],
		int $offset = 0,
		int $pageSize = 200,
		string $sortOrder = 'desc',
		?int $knownTotal = null,
		string $sortBy = 'mtime',
		string $mimeCategory = 'photos',
	): array {
		$user = $this->userSession->getUser();
		if ($user === null) {
			throw new \RuntimeException('Authentication required');
		}
		$userFolder = $this->rootFolder->getUserFolder($user->getUID());
		$this->exifEagerCount = 0;

		$folderPath = trim($folderPath);
		if ($folderPath === '') {
			throw new \RuntimeException('Paged mode requires a folder path');
		}
		if ($offset < 0) {
			$offset = 0;
		}
		$pageSize = max(1, min($pageSize, 500));
		$sortOrder = ($sortOrder === 'asc') ? 'asc' : 'desc';

		$node = $userFolder->get($folderPath);
		if (!($node instanceof Folder)) {
			throw new \RuntimeException('Folder not found: ' . $folderPath);
		}

		// SQL-based enumeration on oc_filecache. Avoids the catastrophic O(N) node
		// instantiation cost of $folder->getDirectoryListing() recursion — that was
		// the cause of the production hang on /Foto/Albums (50k+ files, 21 min CPU
		// per worker). SQL with a path-prefix LIKE + ORDER BY mtime + LIMIT/OFFSET
		// runs in milliseconds on indexed columns.
		//
		// When the user asks for "/" (root) we need to query EVERY mount the user
		// has — personal storage + each groupfolder mount = one storage_id each.
		// Pick the right scope set: single-scope for sub-folders, multi-scope for /.
		$scopes = $this->resolveSearchScopes($node, $folderPath);
		if (empty($scopes)) {
			throw new \RuntimeException('Unable to resolve storage for folder: ' . $folderPath);
		}

		$mimeIds = $this->resolveMediaMimeIds($mimeCategory);
		if (empty($mimeIds)) {
			// No known media mimetypes registered — shouldn't happen on a real NC install.
			return [
				'photos' => [], 'total' => 0, 'offset' => $offset, 'pageSize' => $pageSize,
				'hasMore' => false, 'sortOrder' => $sortOrder, 'truncated' => false,
			];
		}

		// Hard caps: refuse to count or page beyond 200k files in one folder tree.
		// At that scale you should use cross-folder/filter mode instead.
		$hardCap = 200000;

		// Count only when we don't have it from the caller (first page) — saves a
		// full COUNT(*) scan on each subsequent fetchMore() call.
		if ($knownTotal !== null && $knownTotal >= 0) {
			$total = min($knownTotal, $hardCap);
			$truncated = $knownTotal >= $hardCap;
		} else {
			$scopeKey = '';
			foreach ($scopes as $s) {
				$scopeKey .= $s[0] . ':' . md5($s[1]) . '|';
			}
			$countCacheKey = sprintf('pscount:%s:%s', md5($scopeKey), md5(implode(',', $mimeIds)));
			$cached = $this->cache?->get($countCacheKey);
			if (is_string($cached)) {
				$decoded = json_decode($cached, true);
				if (is_array($decoded) && isset($decoded[0], $decoded[1])) {
					[$total, $truncated] = [(int)$decoded[0], (bool)$decoded[1]];
				}
			}
			if (!isset($total)) {
				[$total, $truncated] = $this->countMediaAcrossScopes($scopes, $mimeIds, $hardCap);
				$this->cache?->set($countCacheKey, json_encode([$total, $truncated]), 60);
			}
		}

		$fileLevelSortColumns = [
			'mtime' => 'mtime',
			'name' => 'name',
			'size' => 'size',
		];
		$metaSortKey = null;
		if (!array_key_exists($sortBy, $fileLevelSortColumns)) {
			$metaSortKey = $sortBy;
		}
		$sqlSortColumn = $fileLevelSortColumns[$sortBy] ?? 'mtime';

		// Fast-path decision: when there are MetaVox filters OR the sort is on
		// a metadata key, we cannot rely on per-page SQL ordering alone — those
		// would only match/sort within the current 200-file window. Instead we
		// pre-fetch ALL matching file_ids globally from MetaVox (filter + sort
		// in one query), then paginate over that list via chunked SQL IN. Keeps
		// the SQL-paged speed for the common no-filter case, gives globally-
		// correct results when filters/meta-sort are active.
		$hasFilters = !empty($metaVoxFilters) && $this->isMetaVoxAvailable();
		$needsMetaPath = $hasFilters || ($metaSortKey !== null);

		if ($needsMetaPath) {
			return $this->buildPagedResponseViaMetaVox(
				$scopes, $mimeIds, $node, $userFolder, $metaVoxFilters, $metaSortKey,
				$offset, $pageSize, $sortOrder, $sqlSortColumn, $total, $truncated
			);
		}

		$rawRows = $this->fetchMediaPageAcrossScopes(
			$scopes, $mimeIds, $offset, $pageSize, $sortOrder, $sqlSortColumn
		);
		$hasMore = ($offset + count($rawRows)) < $total;

		if (empty($rawRows)) {
			return [
				'photos' => [], 'total' => $total, 'offset' => $offset, 'pageSize' => $pageSize,
				'hasMore' => false, 'sortOrder' => $sortOrder, 'truncated' => $truncated,
			];
		}

		// Strip the storage's internal-path prefix to expose user-visible paths
		// (e.g. "files/Foto/Albums/IMG_1.jpg" → "Foto/Albums/IMG_1.jpg" for personal
		// storage; for groupfolders the consumer doesn't care about the prefix).
		$slicePrimitives = array_map(function (array $r) {
			return [
				'file_id' => (int)$r['fileid'],
				'storage_id' => (int)($r['storage'] ?? 0),
				'path' => (string)$r['path'],
				'name' => (string)$r['name'],
				'mtime' => (int)$r['mtime'],
				'mime' => (string)$r['mimetype_name'],
				'size' => (int)$r['size'],
			];
		}, $rawRows);

		$sliceIds = array_map(fn(array $r) => $r['file_id'], $slicePrimitives);

		$ncMeta = $this->ncMeta->read($sliceIds);
		$mvMeta = [];
		if ($this->isMetaVoxAvailable()) {
			$gfId = $this->extractGroupfolderId($node);
			if ($gfId !== null && $gfId > 0) {
				// Union of filter-fields + sort-by (if metadata-based) to widen the MetaVox fetch.
				$extraFields = $this->fieldNamesFromFilters($metaVoxFilters);
				if ($metaSortKey !== null && !in_array($metaSortKey, $extraFields, true)) {
					$extraFields[] = $metaSortKey;
				}
				$mvMeta = $this->fetchMetaVoxMeta($sliceIds, $gfId, $extraFields);
			}
		}

		$rows = [];
		foreach ($slicePrimitives as $prim) {
			$fid = $prim['file_id'];
			$rows[] = $this->buildPhotoRow($prim, $mvMeta[$fid] ?? [], $ncMeta[$fid] ?? []);
		}

		if (!empty($metaVoxFilters) && $this->isMetaVoxAvailable()) {
			$rows = array_values(array_filter(
				$rows,
				fn(array $row) => $this->photoMatchesFilters($row, $mvMeta[$row['file_id']] ?? [], $metaVoxFilters)
			));
		}

		// PHP-side sort on metadata keys (NC core taken_at, or any MetaVox field).
		// Note: this reorders WITHIN the current page only — true global meta-sort
		// would require fetching all candidates first which defeats pagination.
		// For Timeline mode this is fine because mtime-grouped days roughly align
		// with taken_at; for Grid mode it's a "best-effort" sort that the user
		// will perceive as correct within each scroll segment.
		if ($metaSortKey !== null) {
			$mvMetaForRows = $mvMeta;
			usort($rows, function (array $a, array $b) use ($metaSortKey, $mvMetaForRows, $sortOrder) {
				$va = $this->extractMetaSortValue($a, $mvMetaForRows[$a['file_id']] ?? [], $metaSortKey);
				$vb = $this->extractMetaSortValue($b, $mvMetaForRows[$b['file_id']] ?? [], $metaSortKey);
				$cmp = $this->compareSortValues($va, $vb);
				return $sortOrder === 'desc' ? -$cmp : $cmp;
			});
		}

		$rows = $this->enrichGeocode($rows);

		return [
			'photos' => $rows,
			'total' => $total,
			'offset' => $offset,
			'pageSize' => $pageSize,
			'hasMore' => $hasMore,
			'sortOrder' => $sortOrder,
			'truncated' => $truncated,
		];
	}

	/**
	 * Resolve a folder Node to (storageId, internalPath).
	 * Internal-path is what oc_filecache stores in the `path` column.
	 *
	 * @return array{0: int|null, 1: string|null}
	 */
	private function extractStorageAndPath(Folder $node): array {
		try {
			$storage = $node->getStorage();
			$storageId = $storage->getCache()->getNumericStorageId();
			$internalPath = $node->getInternalPath();
			return [(int)$storageId, (string)$internalPath];
		} catch (\Throwable $e) {
			$this->logger->warning('PhotoStoryService: extractStorageAndPath failed: ' . $e->getMessage());
			return [null, null];
		}
	}

	/**
	 * Resolve which (storage_id, internal_path) scopes the given folder node
	 * spans. For a normal sub-folder this is a single scope. For the user's
	 * virtual root ("/"), this is every mount the user has — personal storage
	 * plus each groupfolder mount, each living in a different storage_id.
	 *
	 * @return array<int, array{0: int, 1: string}>
	 */
	private function resolveSearchScopes(Folder $node, string $requestedPath): array {
		$rootCase = ($requestedPath === '' || $requestedPath === '/');

		if (!$rootCase) {
			[$sid, $ip] = $this->extractStorageAndPath($node);
			if ($sid === null || $ip === null) {
				return [];
			}
			return [[$sid, $ip]];
		}

		// Root-of-user-tree case: enumerate every mount the user has access to.
		// We use $userFolder->getDirectoryListing() to get the top-level
		// children (personal "files" + each groupfolder mountpoint), then read
		// each one's storage_id + internal-path. That's typically 1–20 mounts
		// — cheap and covers cross-storage correctly without raw mount-cache
		// queries.
		try {
			$user = $this->userSession->getUser();
			if ($user === null) {
				return [];
			}
			$userFolder = $this->rootFolder->getUserFolder($user->getUID());

			// Map storage_id → internalPath. A mount's root has either an empty
			// internalPath (whole storage) or "files" (NC personal storage root
			// within a user-account-storage). We keep the broadest scope per
			// storage to avoid duplicates if multiple mounts share a storage.
			$byStorage = [];
			[$rootSid, $rootIp] = $this->extractStorageAndPath($userFolder);
			if ($rootSid !== null && $rootIp !== null) {
				$byStorage[$rootSid] = $rootIp;
			}
			foreach ($userFolder->getDirectoryListing() as $child) {
				if (!($child instanceof Folder)) {
					continue;
				}
				try {
					[$sid, $ip] = $this->extractStorageAndPath($child);
					if ($sid === null || $ip === null) {
						continue;
					}
					// If we already have a broader (shorter) path for this storage, keep it.
					if (!isset($byStorage[$sid]) || strlen($ip) < strlen($byStorage[$sid])) {
						$byStorage[$sid] = $ip;
					}
				} catch (\Throwable $e) {
					$this->logger->debug('PhotoStoryService: scope-enum child failed: ' . $e->getMessage());
				}
			}
			$out = [];
			foreach ($byStorage as $sid => $ip) {
				$out[] = [(int)$sid, (string)$ip];
			}
			return $out;
		} catch (\Throwable $e) {
			$this->logger->warning('PhotoStoryService: resolveSearchScopes failed: ' . $e->getMessage());
			// Fallback: just the user-folder's own storage scope.
			[$sid, $ip] = $this->extractStorageAndPath($node);
			if ($sid !== null && $ip !== null) {
				return [[$sid, $ip]];
			}
			return [];
		}
	}

	/**
	 * Count media files across multiple (storage, path) scopes. Sums COUNT(*)
	 * per scope; returns [total, truncated] like countMediaInTree.
	 *
	 * @param array<int, array{0: int, 1: string}> $scopes
	 * @return array{0: int, 1: bool}
	 */
	private function countMediaAcrossScopes(array $scopes, array $mimeIds, int $hardCap): array {
		$total = 0;
		$truncated = false;
		foreach ($scopes as [$sid, $ip]) {
			$remaining = max(0, $hardCap - $total);
			if ($remaining === 0) {
				$truncated = true;
				break;
			}
			[$c, $t] = $this->countMediaInTree($sid, $ip, $mimeIds, $remaining);
			$total += $c;
			if ($t) {
				$truncated = true;
			}
		}
		if ($total > $hardCap) {
			$total = $hardCap;
			$truncated = true;
		}
		return [$total, $truncated];
	}

	/**
	 * Fetch a paged slice across multiple scopes. Each scope is queried
	 * independently with its own LIMIT, then results are merged + globally
	 * resorted in PHP + sliced to pageSize. For typical "/" use (5–20 mounts,
	 * pageSize 100–200), this is sub-second.
	 *
	 * @param array<int, array{0: int, 1: string}> $scopes
	 * @return array<int, array<string, mixed>>
	 */
	private function fetchMediaPageAcrossScopes(
		array $scopes, array $mimeIds, int $offset, int $pageSize, string $sortOrder, string $sortColumn
	): array {
		if (count($scopes) === 1) {
			// Single-scope fast path — identical to old single-storage behavior.
			[$sid, $ip] = $scopes[0];
			return $this->fetchMediaPageFromFilecache($sid, $ip, $mimeIds, $offset, $pageSize, $sortOrder, $sortColumn);
		}

		// Multi-scope: over-fetch from each (offset+pageSize rows), merge, resort, slice.
		// Per-scope over-fetch is necessary because the SQL ORDER BY only applies
		// within one scope; we need enough headroom to re-sort globally.
		$fetch = $offset + $pageSize;
		$merged = [];
		foreach ($scopes as [$sid, $ip]) {
			$rows = $this->fetchMediaPageFromFilecache($sid, $ip, $mimeIds, 0, $fetch, $sortOrder, $sortColumn);
			foreach ($rows as $r) {
				$merged[] = $r;
			}
		}

		// Re-sort the merged set.
		$desc = ($sortOrder === 'desc');
		usort($merged, function ($a, $b) use ($sortColumn, $desc) {
			$va = $a[$sortColumn] ?? null;
			$vb = $b[$sortColumn] ?? null;
			if (is_numeric($va) && is_numeric($vb)) {
				$cmp = (float)$va <=> (float)$vb;
			} else {
				$cmp = strcasecmp((string)$va, (string)$vb);
			}
			return $desc ? -$cmp : $cmp;
		});

		return array_slice($merged, $offset, $pageSize);
	}

	/**
	 * Filter-and-sort-aware paged response. Used when the caller specified
	 * MetaVox filters or a metadata-sort key — both require pre-resolution of
	 * the matching file_id set BEFORE we can paginate sensibly. The naive
	 * approach (fetch 200, filter to 3) was the source of "filter is leaking
	 * results" complaints; this one is globally correct.
	 *
	 * Strategy:
	 *  1. Query oc_metavox_file_gf_meta with the filter predicates + sort field
	 *     (in one SELECT) → list of (file_id, sort_value) tuples.
	 *  2. Restrict to file_ids that live within the scope's storages + path
	 *     prefixes + media mimetypes (a single SQL with IN-clause + LIKE).
	 *  3. Sort the resulting list in PHP by the chosen sort-key + direction.
	 *  4. Paginate via array_slice, then re-fetch the slice's full filecache
	 *     rows + meta in the normal way for response shaping.
	 *
	 * @param array<int, array{0: int, 1: string}> $scopes
	 * @param int[] $mimeIds
	 * @param array<int, array{field: string, op: string, value: mixed}> $metaVoxFilters
	 * @return array{photos: array, total: int, offset: int, pageSize: int, hasMore: bool, sortOrder: string, truncated: bool}
	 */
	private function buildPagedResponseViaMetaVox(
		array $scopes,
		array $mimeIds,
		Folder $node,
		Folder $userFolder,
		array $metaVoxFilters,
		?string $metaSortKey,
		int $offset,
		int $pageSize,
		string $sortOrder,
		string $sqlSortColumn,
		int $fallbackTotal,
		bool $fallbackTruncated
	): array {
		// Step 1: find file_ids matching the filters (via the existing cross-folder helper).
		// When there are no filters but only a meta-sort, we still need every
		// candidate file_id within the scope — generate that list from filecache
		// directly (mtime-sorted, no LIMIT).
		if (!empty($metaVoxFilters)) {
			$matchingIds = $this->queryFileIdsMatchingFilters($metaVoxFilters);
		} else {
			$matchingIds = $this->collectAllFileIdsInScope($scopes, $mimeIds);
		}
		if (empty($matchingIds)) {
			return [
				'photos' => [], 'total' => 0, 'offset' => $offset, 'pageSize' => $pageSize,
				'hasMore' => false, 'sortOrder' => $sortOrder, 'truncated' => false,
			];
		}

		// Step 2: intersect with files that are inside our scopes AND match
		// the requested mimetype set. This is "are these file_ids actually in
		// /test or /Foto/Albums and a document/photo mime?".
		$inScopeIds = $this->filterFileIdsByScope($matchingIds, $scopes, $mimeIds);
		if (empty($inScopeIds)) {
			return [
				'photos' => [], 'total' => 0, 'offset' => $offset, 'pageSize' => $pageSize,
				'hasMore' => false, 'sortOrder' => $sortOrder, 'truncated' => false,
			];
		}

		// Step 3: determine sort order. Meta-sort needs the value per file_id
		// from MetaVox; file-sort needs the value from filecache (mtime/name/size).
		// Cap at 20k after-filter results to keep PHP-side sort fast.
		$globalCap = 20000;
		$truncated = $fallbackTruncated;
		if (count($inScopeIds) > $globalCap) {
			$inScopeIds = array_slice($inScopeIds, 0, $globalCap);
			$truncated = true;
		}

		$sortKey = $metaSortKey ?? $sqlSortColumn;
		$idsSorted = $this->sortFileIds($inScopeIds, $sortKey, $sortOrder, $metaSortKey !== null);

		$total = count($idsSorted);
		$slice = array_slice($idsSorted, $offset, $pageSize);
		$hasMore = ($offset + count($slice)) < $total;

		if (empty($slice)) {
			return [
				'photos' => [], 'total' => $total, 'offset' => $offset, 'pageSize' => $pageSize,
				'hasMore' => false, 'sortOrder' => $sortOrder, 'truncated' => $truncated,
			];
		}

		// Step 4: ACL guard. filterFileIdsByScope() restricts to storage+path-prefix
		// (cheap, structural), but inside a groupfolder NC supports per-sub-path
		// permissions. Do a per-file getById() check on the slice only (page-sized,
		// max 200) to ensure the user actually has read access. Files they cannot
		// read get dropped before hydration.
		$acceptedIds = [];
		foreach ($slice as $id) {
			try {
				$nodes = $userFolder->getById((int)$id);
				if (!empty($nodes)) {
					$acceptedIds[(int)$id] = true;
				}
			} catch (\Throwable $e) {
				// Defensive: a single bad id should not break the page.
			}
		}

		// Step 5: hydrate the accepted slice with full file info.
		$rawRows = $this->fetchFilecacheRowsByIds(array_keys($acceptedIds));
		// Preserve sort order: rawRows came back in arbitrary SQL order.
		$byId = [];
		foreach ($rawRows as $r) {
			$byId[(int)$r['fileid']] = $r;
		}
		$ordered = [];
		foreach ($slice as $id) {
			if (isset($acceptedIds[(int)$id]) && isset($byId[(int)$id])) {
				$ordered[] = $byId[(int)$id];
			}
		}

		$slicePrimitives = array_map(function (array $r) {
			return [
				'file_id' => (int)$r['fileid'],
				'storage_id' => (int)($r['storage'] ?? 0),
				'path' => (string)$r['path'],
				'name' => (string)$r['name'],
				'mtime' => (int)$r['mtime'],
				'mime' => (string)$r['mimetype_name'],
				'size' => (int)$r['size'],
			];
		}, $ordered);

		$sliceIds = array_map(fn(array $r) => $r['file_id'], $slicePrimitives);
		$ncMeta = $this->ncMeta->read($sliceIds);
		$mvMeta = $this->fetchMetaVoxMetaAllGroupfolders(
			$sliceIds,
			array_merge($this->fieldNamesFromFilters($metaVoxFilters), $metaSortKey !== null ? [$metaSortKey] : [])
		);

		$rows = [];
		foreach ($slicePrimitives as $prim) {
			$fid = $prim['file_id'];
			$rows[] = $this->buildPhotoRow($prim, $mvMeta[$fid] ?? [], $ncMeta[$fid] ?? []);
		}

		$rows = $this->enrichGeocode($rows);

		return [
			'photos' => $rows,
			'total' => $total,
			'offset' => $offset,
			'pageSize' => $pageSize,
			'hasMore' => $hasMore,
			'sortOrder' => $sortOrder,
			'truncated' => $truncated,
		];
	}

	/**
	 * Collect every file_id in the given scope's storages + path-prefixes +
	 * media mimetypes. Used when there are no filters but a meta-sort is
	 * requested — we still need the full candidate set before sorting.
	 *
	 * @param array<int, array{0: int, 1: string}> $scopes
	 * @param int[] $mimeIds
	 * @return int[]
	 */
	private function collectAllFileIdsInScope(array $scopes, array $mimeIds): array {
		$out = [];
		foreach ($scopes as [$sid, $ip]) {
			$prefix = $ip === '' ? '' : ($ip . '/');
			$like = $this->escapeLikePattern($prefix) . '%';
			try {
				$qb = $this->db->getQueryBuilder();
				$qb->select('fileid')
					->from('filecache')
					->where($qb->expr()->eq('storage', $qb->createNamedParameter($sid, \OCP\DB\QueryBuilder\IQueryBuilder::PARAM_INT)))
					->andWhere($qb->expr()->like('path', $qb->createNamedParameter($like)))
					->andWhere($qb->expr()->in('mimetype', $qb->createNamedParameter($mimeIds, \OCP\DB\QueryBuilder\IQueryBuilder::PARAM_INT_ARRAY)));
				$r = $qb->executeQuery();
				while ($row = $r->fetch()) {
					$out[] = (int)$row['fileid'];
				}
				$r->closeCursor();
			} catch (\Throwable $e) {
				$this->logger->warning('PhotoStoryService: collectAllFileIdsInScope failed: ' . $e->getMessage());
			}
		}
		return array_values(array_unique($out));
	}

	/**
	 * Given a list of file_ids (typically from MetaVox), drop any that aren't
	 * inside the given (storage, path-prefix) scopes or don't match the media
	 * mime-set. Done in chunks of 500 to respect NC's IN-list cap.
	 *
	 * @param int[] $fileIds
	 * @param array<int, array{0: int, 1: string}> $scopes
	 * @param int[] $mimeIds
	 * @return int[]
	 */
	private function filterFileIdsByScope(array $fileIds, array $scopes, array $mimeIds): array {
		if (empty($fileIds) || empty($scopes)) {
			return [];
		}
		$out = [];
		// Collapse scopes into one ORed clause per chunk so we issue
		// max(ceil(N/500)) queries instead of (scopes × chunks).
		foreach (array_chunk(array_values(array_unique(array_map('intval', $fileIds))), 500) as $chunk) {
			try {
				$qb = $this->db->getQueryBuilder();
				$qb->select('fileid')
					->from('filecache')
					->where($qb->expr()->in('fileid', $qb->createNamedParameter($chunk, \OCP\DB\QueryBuilder\IQueryBuilder::PARAM_INT_ARRAY)))
					->andWhere($qb->expr()->in('mimetype', $qb->createNamedParameter($mimeIds, \OCP\DB\QueryBuilder\IQueryBuilder::PARAM_INT_ARRAY)));

				// (storage = sid1 AND path LIKE 'p1/%') OR (storage = sid2 AND path LIKE 'p2/%') OR ...
				$scopeOrs = [];
				foreach ($scopes as [$sid, $ip]) {
					$prefix = $ip === '' ? '' : ($ip . '/');
					$like = $this->escapeLikePattern($prefix) . '%';
					$scopeOrs[] = $qb->expr()->andX(
						$qb->expr()->eq('storage', $qb->createNamedParameter($sid, \OCP\DB\QueryBuilder\IQueryBuilder::PARAM_INT)),
						$qb->expr()->like('path', $qb->createNamedParameter($like))
					);
				}
				if (count($scopeOrs) === 1) {
					$qb->andWhere($scopeOrs[0]);
				} else {
					$qb->andWhere($qb->expr()->orX(...$scopeOrs));
				}

				$r = $qb->executeQuery();
				while ($row = $r->fetch()) {
					$out[] = (int)$row['fileid'];
				}
				$r->closeCursor();
			} catch (\Throwable $e) {
				$this->logger->warning('PhotoStoryService: filterFileIdsByScope failed: ' . $e->getMessage());
			}
		}
		return array_values(array_unique($out));
	}

	/**
	 * Sort a list of file_ids by either a filecache column (mtime/name/size)
	 * or a MetaVox field name. Returns the ids in sorted order.
	 *
	 * @param int[] $fileIds
	 * @return int[]
	 */
	private function sortFileIds(array $fileIds, string $sortKey, string $sortOrder, bool $isMetaSort): array {
		if (empty($fileIds)) {
			return [];
		}
		$values = [];
		if ($isMetaSort) {
			// Pull sort values from MetaVox in chunks.
			foreach (array_chunk(array_values(array_unique(array_map('intval', $fileIds))), 500) as $chunk) {
				try {
					$qb = $this->db->getQueryBuilder();
					$qb->select('file_id', 'field_value')
						->from('metavox_file_gf_meta')
						->where($qb->expr()->in('file_id', $qb->createNamedParameter($chunk, \OCP\DB\QueryBuilder\IQueryBuilder::PARAM_INT_ARRAY)))
						->andWhere($qb->expr()->eq('field_name', $qb->createNamedParameter($sortKey)));
					$r = $qb->executeQuery();
					while ($row = $r->fetch()) {
						$values[(int)$row['file_id']] = $row['field_value'];
					}
					$r->closeCursor();
				} catch (\Throwable $e) {
					$this->logger->warning('PhotoStoryService: sortFileIds meta-query failed: ' . $e->getMessage());
				}
			}
		} else {
			// Pull sort values from filecache (mtime/name/size).
			$col = in_array($sortKey, ['mtime', 'name', 'size'], true) ? $sortKey : 'mtime';
			foreach (array_chunk(array_values(array_unique(array_map('intval', $fileIds))), 500) as $chunk) {
				try {
					$qb = $this->db->getQueryBuilder();
					$qb->select('fileid', $col)
						->from('filecache')
						->where($qb->expr()->in('fileid', $qb->createNamedParameter($chunk, \OCP\DB\QueryBuilder\IQueryBuilder::PARAM_INT_ARRAY)));
					$r = $qb->executeQuery();
					while ($row = $r->fetch()) {
						$values[(int)$row['fileid']] = $row[$col];
					}
					$r->closeCursor();
				} catch (\Throwable $e) {
					$this->logger->warning('PhotoStoryService: sortFileIds fc-query failed: ' . $e->getMessage());
				}
			}
		}

		$desc = ($sortOrder === 'desc');
		usort($fileIds, function ($a, $b) use ($values, $desc) {
			$va = $values[(int)$a] ?? null;
			$vb = $values[(int)$b] ?? null;
			if ($va === null && $vb === null) return 0;
			if ($va === null) return 1;  // nulls last
			if ($vb === null) return -1;
			if (is_numeric($va) && is_numeric($vb)) {
				$cmp = (float)$va <=> (float)$vb;
			} else {
				$cmp = strcasecmp((string)$va, (string)$vb);
			}
			return $desc ? -$cmp : $cmp;
		});
		return $fileIds;
	}

	/**
	 * Fetch full filecache rows (with mime-name) for a list of file_ids. Used
	 * to hydrate the slice after filter+sort resolution.
	 *
	 * @param int[] $fileIds
	 * @return array<int, array<string, mixed>>
	 */
	private function fetchFilecacheRowsByIds(array $fileIds): array {
		if (empty($fileIds)) {
			return [];
		}
		$out = [];
		foreach (array_chunk(array_values(array_unique(array_map('intval', $fileIds))), 500) as $chunk) {
			try {
				$qb = $this->db->getQueryBuilder();
				$qb->select('fc.fileid', 'fc.storage', 'fc.path', 'fc.name', 'fc.mtime', 'fc.mimetype', 'fc.size', 'mt.mimetype as mimetype_name')
					->from('filecache', 'fc')
					->leftJoin('fc', 'mimetypes', 'mt', $qb->expr()->eq('fc.mimetype', 'mt.id'))
					->where($qb->expr()->in('fc.fileid', $qb->createNamedParameter($chunk, \OCP\DB\QueryBuilder\IQueryBuilder::PARAM_INT_ARRAY)));
				$r = $qb->executeQuery();
				while ($row = $r->fetch()) {
					$out[] = $row;
				}
				$r->closeCursor();
			} catch (\Throwable $e) {
				$this->logger->warning('PhotoStoryService: fetchFilecacheRowsByIds failed: ' . $e->getMessage());
			}
		}
		return $out;
	}

	/**
	 * Map a content-category name to the mimetype list. Categories drive which
	 * widget calls this service:
	 *   - 'photos'    → photo + video (MEDIA_MIMES)
	 *   - 'documents' → PDFs, Office, ODF, markdown, plain text (DOC_MIMES)
	 *   - 'all'       → union of both
	 *
	 * @return array<int, string>
	 */
	private function mimesForCategory(string $category): array {
		switch ($category) {
			case 'documents':
				return self::DOC_MIMES;
			case 'all':
				return array_values(array_unique(array_merge(self::MEDIA_MIMES, self::DOC_MIMES)));
			case 'photos':
			default:
				return self::MEDIA_MIMES;
		}
	}

	/**
	 * Resolve the numeric mimetype IDs for a given category. Cached per
	 * category since IMimeTypeLoader does a DB-backed lookup per mime.
	 *
	 * @return int[]
	 */
	private function resolveMediaMimeIds(string $category = 'photos'): array {
		static $cache = [];
		if (isset($cache[$category])) {
			return $cache[$category];
		}
		$ids = [];
		foreach ($this->mimesForCategory($category) as $mime) {
			try {
				$id = $this->mimeTypeLoader->getId($mime);
				if ($id > 0) {
					$ids[] = $id;
				}
			} catch (\Throwable $e) {
				// Mime not registered — skip.
			}
		}
		$cache[$category] = array_values(array_unique($ids));
		return $cache[$category];
	}

	/**
	 * Count media files in a folder tree using oc_filecache. Returns
	 * [total, truncated]. Truncated is true when the count hits the hard cap.
	 *
	 * @param int[] $mimeIds
	 * @return array{0: int, 1: bool}
	 */
	private function countMediaInTree(int $storageId, string $internalPath, array $mimeIds, int $hardCap): array {
		// path LIKE 'files/Foto/Albums/%' OR path = 'files/Foto/Albums'.
		// (No `OR path = base` here — we only count files inside, not the dir itself.)
		$prefix = $internalPath === '' ? '' : ($internalPath . '/');
		$like = $this->escapeLikePattern($prefix) . '%';

		// MariaDB's COUNT(*) over an indexed LIKE-prefix + IN mimetype is fast
		// even on 200k+ row trees (sub-second). No subqueries needed.
		try {
			$qb = $this->db->getQueryBuilder();
			$qb->select($qb->func()->count('*', 'cnt'))
				->from('filecache')
				->where($qb->expr()->eq('storage', $qb->createNamedParameter($storageId, \OCP\DB\QueryBuilder\IQueryBuilder::PARAM_INT)))
				->andWhere($qb->expr()->like('path', $qb->createNamedParameter($like)))
				->andWhere($qb->expr()->in('mimetype', $qb->createNamedParameter($mimeIds, \OCP\DB\QueryBuilder\IQueryBuilder::PARAM_INT_ARRAY)));
			$r = $qb->executeQuery();
			$row = $r->fetch();
			$r->closeCursor();
			$total = (int)($row['cnt'] ?? 0);
			$truncated = false;
			if ($total > $hardCap) {
				$total = $hardCap;
				$truncated = true;
			}
			return [$total, $truncated];
		} catch (\Throwable $e) {
			$this->logger->warning('PhotoStoryService: count query failed: ' . $e->getMessage());
			return [0, false];
		}
	}

	/**
	 * Fetch one page of media files directly from oc_filecache, joined with
	 * oc_mimetypes for the mime-name string.
	 *
	 * Returns rows with: fileid, path, name, mtime, mimetype (id), mimetype_name, size.
	 *
	 * @param int[] $mimeIds
	 * @return array<int, array<string, mixed>>
	 */
	private function fetchMediaPageFromFilecache(
		int $storageId, string $internalPath, array $mimeIds, int $offset, int $pageSize, string $sortOrder,
		string $sortColumn = 'mtime'
	): array {
		$prefix = $internalPath === '' ? '' : ($internalPath . '/');
		$like = $this->escapeLikePattern($prefix) . '%';

		// Whitelist the SQL column to prevent injection; only allow oc_filecache
		// columns we know can be safely ordered on.
		$allowedColumns = ['mtime', 'name', 'size'];
		if (!in_array($sortColumn, $allowedColumns, true)) {
			$sortColumn = 'mtime';
		}

		try {
			$qb = $this->db->getQueryBuilder();
			$qb->select('fc.fileid', 'fc.storage', 'fc.path', 'fc.name', 'fc.mtime', 'fc.mimetype', 'fc.size', 'mt.mimetype as mimetype_name')
				->from('filecache', 'fc')
				->leftJoin('fc', 'mimetypes', 'mt', $qb->expr()->eq('fc.mimetype', 'mt.id'))
				->where($qb->expr()->eq('fc.storage', $qb->createNamedParameter($storageId, \OCP\DB\QueryBuilder\IQueryBuilder::PARAM_INT)))
				->andWhere($qb->expr()->like('fc.path', $qb->createNamedParameter($like)))
				->andWhere($qb->expr()->in('fc.mimetype', $qb->createNamedParameter($mimeIds, \OCP\DB\QueryBuilder\IQueryBuilder::PARAM_INT_ARRAY)))
				->orderBy('fc.' . $sortColumn, $sortOrder === 'desc' ? 'DESC' : 'ASC')
				->setFirstResult(max(0, $offset))
				->setMaxResults(max(1, $pageSize));

			$r = $qb->executeQuery();
			$rows = [];
			while ($row = $r->fetch()) {
				$rows[] = $row;
			}
			$r->closeCursor();
			return $rows;
		} catch (\Throwable $e) {
			$this->logger->error('PhotoStoryService: page query failed: ' . $e->getMessage());
			return [];
		}
	}

	/**
	 * Escape LIKE wildcards (%, _, \) using NC's dialect-correct helper.
	 * `IDBConnection::escapeLikeParameter` knows which escape char each
	 * supported backend (MariaDB/PostgreSQL/Oracle/SQLite) expects, so the
	 * pattern stays portable.
	 */
	private function escapeLikePattern(string $s): string {
		return $this->db->escapeLikeParameter($s);
	}

	/**
	 * Folder-driven photo listing. Existing flow + optional post-filter on MetaVox values.
	 *
	 * @param array<int, array{field: string, op: string, value: mixed}> $metaVoxFilters
	 * @return array<int, array<string, mixed>>
	 */
	private function listPhotosFolder(
		string $folderPath,
		array $metaVoxFilters,
		?int $limit,
		\OCP\Files\Folder $userFolder,
	): array {
		$node = $userFolder->get($folderPath);
		if (!($node instanceof Folder)) {
			throw new \RuntimeException('Folder not found: ' . $folderPath);
		}

		// Collect candidate files recursively (subfolders included)
		$files = [];
		$this->collectMediaRecursive($node, $files);

		// Sort by mtime ascending (oldest → newest). Stable across subfolders so a
		// $limit cap doesn't arbitrarily favour alphabetic naming when a folder
		// spans multiple years. EXIF date isn't available here yet (cheaper to
		// stay on filesystem metadata for this pre-pass).
		usort($files, fn($a, $b) => $a->getMTime() <=> $b->getMTime());

		// NOTE: don't slice with $limit yet — we may filter further below.

		$fileIds = array_map(fn($f) => $f->getId(), $files);

		// Pass 1: NC core FilesMetadata (NC 28+). Primary source-of-truth for
		// EXIF date/GPS/camera. Batch read in one shot.
		$ncMeta = !empty($fileIds) ? $this->ncMeta->read($fileIds) : [];

		// Pass 2: MetaVox custom fields (people, subjects, custom location-string,
		// custom country, etc.). Secondary — adds to what NC core provides.
		$mvMeta = [];
		$groupfolderId = null;
		if ($this->isMetaVoxAvailable() && !empty($files)) {
			$groupfolderId = $this->extractGroupfolderId($node);
			if ($groupfolderId !== null && $groupfolderId > 0) {
				$mvMeta = $this->fetchMetaVoxMeta($fileIds, $groupfolderId, $this->fieldNamesFromFilters($metaVoxFilters));
			}
		}

		$result = [];
		foreach ($files as $file) {
			$fid = $file->getId();
			$row = $this->buildPhotoRow($file, $mvMeta[$fid] ?? [], $ncMeta[$fid] ?? []);
			$result[] = $row;
		}

		// Apply MetaVox filters as a post-filter step (folder mode).
		if (!empty($metaVoxFilters) && $this->isMetaVoxAvailable()) {
			$result = array_values(array_filter(
				$result,
				fn(array $row) => $this->photoMatchesFilters($row, $mvMeta[$row['file_id']] ?? [], $metaVoxFilters)
			));
		}

		// Apply limit AFTER filtering
		if ($limit !== null && $limit > 0 && count($result) > $limit) {
			$result = array_slice($result, 0, $limit);
		}

		return $this->enrichGeocode($result);
	}

	/**
	 * Walk a folder tree, appending all media files to $out by reference.
	 * Depth-capped at 8 to avoid pathological symlink loops; soft-capped at 50k
	 * files to keep server-side enumeration bounded. The slice/metadata enrichment
	 * downstream is paged, so 50k file objects is the per-request memory ceiling.
	 *
	 * @param \OCP\Files\File[] $out
	 */
	private function collectMediaRecursive(\OCP\Files\Folder $folder, array &$out, int $depth = 0, ?bool &$truncated = null): void {
		$cap = 50000;
		if ($depth > 8 || count($out) >= $cap) {
			if ($truncated !== null && count($out) >= $cap) {
				$truncated = true;
			}
			return;
		}
		foreach ($folder->getDirectoryListing() as $child) {
			if (count($out) >= $cap) {
				if ($truncated !== null) {
					$truncated = true;
				}
				return;
			}
			if ($child instanceof \OCP\Files\Folder) {
				$this->collectMediaRecursive($child, $out, $depth + 1, $truncated);
				continue;
			}
			$mime = (string)$child->getMimeType();
			if (in_array($mime, self::MEDIA_MIMES, true)) {
				$out[] = $child;
			}
		}
	}

	/**
	 * Cross-folder mode: query oc_metavox_file_gf_meta for all matching file_ids,
	 * then permission-check each via $userFolder->getById().
	 *
	 * @param array<int, array{field: string, op: string, value: mixed}> $metaVoxFilters
	 * @return array<int, array<string, mixed>>
	 */
	private function listPhotosCrossFolder(
		array $metaVoxFilters,
		?int $limit,
		\OCP\Files\Folder $userFolder,
	): array {
		// Step 1: find all file_ids in MetaVox metadata matching ALL filters (AND).
		$candidateIds = $this->queryFileIdsMatchingFilters($metaVoxFilters);

		if (empty($candidateIds)) {
			return [];
		}
		if (count($candidateIds) > self::CROSS_FOLDER_HARD_CAP) {
			throw new \RuntimeException(sprintf(
				'Too many results (%d). Add more filters to narrow the selection (hard cap: %d).',
				count($candidateIds),
				self::CROSS_FOLDER_HARD_CAP
			));
		}

		// Step 2: batch-load metadata from both sources.
		$ncMeta = $this->ncMeta->read($candidateIds);  // NC core primary
		$mvMeta = $this->fetchMetaVoxMetaAllGroupfolders($candidateIds, $this->fieldNamesFromFilters($metaVoxFilters));

		// Step 3: permission-check each file via $userFolder->getById(), chunked.
		$result = [];
		$chunks = array_chunk($candidateIds, 100);
		foreach ($chunks as $chunk) {
			foreach ($chunk as $fileId) {
				try {
					$nodes = $userFolder->getById((int)$fileId);
				} catch (\Throwable $e) {
					// Treat as inaccessible; skip silently
					continue;
				}
				if (empty($nodes)) {
					continue;
				}
				// Use the first readable node
				$file = null;
				foreach ($nodes as $n) {
					if ($n instanceof Folder) {
						continue;
					}
					$mime = (string)$n->getMimeType();
					if (!in_array($mime, self::MEDIA_MIMES, true)) {
						continue;
					}
					$file = $n;
					break;
				}
				if ($file === null) {
					continue;
				}
				$result[] = $this->buildPhotoRow($file, $mvMeta[(int)$fileId] ?? [], $ncMeta[(int)$fileId] ?? []);

				if ($limit !== null && $limit > 0 && count($result) >= $limit) {
					break 2;
				}
			}
		}

		return $this->enrichGeocode($result);
	}

	/**
	 * Build one photo dict from a File node + metadata from both sources.
	 *
	 * Source priority for EXIF-like fields (Phase 3.1):
	 * 1. **Nextcloud core FilesMetadata** (NC 28+) — taken_at, gps, camera, width/height.
	 *    Always preferred when present. Indexed by NC's own scanner, no app-specific
	 *    field-name mapping required.
	 * 2. **MetaVox custom fields** — location-string, country, people, subjects, plus
	 *    overrides for taken_at/camera when an admin renamed the standard fields.
	 *    Complementary, not duplicate. Only used to FILL fields the NC core didn't.
	 * 3. **Eager EXIF fallback** — opt-in via `photostory.exif.eager_in_request`,
	 *    capped at N files per request. Only relevant when neither NC nor MetaVox
	 *    has indexed the file (very old uploads on systems without a scan run).
	 *
	 * @param array<string, string> $mv MetaVox field_name => value
	 * @param array<string, mixed> $nc NC core normalized payload (taken_at, gps, camera, width, height)
	 * @return array<string, mixed>
	 */
	private function buildPhotoRow($file, array $mv, array $nc = []): array {
		// Accept either an OCP\Files\File-like Node OR a plain array of the
		// primitive fields we need. The SQL-paged path uses arrays so we
		// don't instantiate full Node objects for thousands of files.
		$storageId = 0;
		if (is_array($file)) {
			$id = (int)($file['file_id'] ?? 0);
			$storageId = (int)($file['storage_id'] ?? 0);
			$path = (string)($file['path'] ?? '');
			$name = (string)($file['name'] ?? '');
			$mtime = (int)($file['mtime'] ?? 0);
			$mime = (string)($file['mime'] ?? '');
			$size = (int)($file['size'] ?? 0);
		} else {
			$id = $file->getId();
			$path = $file->getPath();
			$name = $file->getName();
			$mtime = $file->getMTime();
			$mime = (string)$file->getMimeType();
			$size = (int)$file->getSize();
			try {
				$storageId = (int)$file->getStorage()->getCache()->getNumericStorageId();
			} catch (\Throwable $e) {
				// Best-effort — leave storageId at 0 if storage lookup fails.
			}
		}
		// Federated detection — cached per storage_id within the request.
		// Returns false for personal/groupfolder storages; true only for files
		// that live in OCA\Files_Sharing\External\Storage or an equivalent
		// federated mount. Frontend uses this to render a cloud-badge hint.
		$isFederated = $storageId > 0 ? $this->isStorageIdFederated($storageId) : false;

		$row = [
			'file_id' => $id,
			'path' => $path,
			'name' => $name,
			'mtime' => $mtime,
			'mime' => $mime,
			'size' => $size,
			'taken_at' => null,
			'location' => null,
			'location_display' => null,
			'country' => null,
			'camera' => null,
			'people' => [],
			'subjects' => [],
			'gps' => null,
			'width' => null,
			'height' => null,
			// Generic dict for any MetaVox field outside the WELL_KNOWN_PHOTO_FIELDS
			// set. Lets the frontend filter/sort on custom user-created fields
			// (project, status, author, etc.) without IntraVox knowing them by name.
			'meta' => [],
			// True when this file lives in a federated (incoming OCM) share.
			// MetaVox values aren't available for those files; frontend may
			// render a visual hint and the editor may show an info-message.
			'is_federated' => $isFederated,
		];

		// Pass A: NC core FilesMetadata (primary). Highest priority for EXIF-derived fields.
		if (!empty($nc)) {
			if (isset($nc['taken_at']) && $nc['taken_at'] instanceof \DateTimeInterface) {
				$row['taken_at'] = $nc['taken_at']->format(\DateTimeInterface::ATOM);
			}
			if (isset($nc['gps']) && is_array($nc['gps'])) {
				$row['gps'] = ['lat' => (float)$nc['gps']['lat'], 'lon' => (float)$nc['gps']['lon']];
			}
			if (!empty($nc['camera'])) {
				$row['camera'] = (string)$nc['camera'];
			}
			if (isset($nc['width'])) {
				$row['width'] = (int)$nc['width'];
			}
			if (isset($nc['height'])) {
				$row['height'] = (int)$nc['height'];
			}
		}

		// Pass B: MetaVox custom fields (secondary). Fills gaps + adds custom fields
		// (people, subjects, location-string) that NC core doesn't track.
		if (!empty($mv)) {
			if ($row['taken_at'] === null && !empty($mv['exif_taken_at'])) {
				$dt = $this->parseFlexibleDate((string)$mv['exif_taken_at']);
				if ($dt !== null) {
					$row['taken_at'] = $dt->format(\DateTimeInterface::ATOM);
				}
			}
			if (!empty($mv['exif_location'])) {
				$row['location'] = (string)$mv['exif_location'];
			}
			if (!empty($mv['exif_country'])) {
				$row['country'] = (string)$mv['exif_country'];
			}
			if ($row['camera'] === null && !empty($mv['exif_camera'])) {
				$row['camera'] = (string)$mv['exif_camera'];
			}
			if (!empty($mv['exif_people'])) {
				$row['people'] = $this->splitMultiselect((string)$mv['exif_people']);
			}
			if (!empty($mv['exif_subjects'])) {
				$row['subjects'] = $this->splitMultiselect((string)$mv['exif_subjects']);
			}
			// Any field outside WELL_KNOWN_PHOTO_FIELDS becomes part of the generic
			// meta dict — preserves the value untouched so the frontend can filter,
			// sort, or render it however it wants.
			foreach ($mv as $fieldName => $fieldValue) {
				if (in_array($fieldName, self::WELL_KNOWN_PHOTO_FIELDS, true)) {
					continue;
				}
				$row['meta'][(string)$fieldName] = $fieldValue;
			}
		}

		// EXIF-fallback strategy (Phase 3.1):
		// With NC core FilesMetadata (Pass A) covering taken_at/gps/camera for every
		// scanned file, this fallback is rarely needed. It stays as opt-in safety net
		// for installations where the NC scanner hasn't run yet, or for legacy files.
		//
		// Production setups should run `occ files:scan --all` once to populate
		// `oc_files_metadata` for every existing photo. After that, the eager flag
		// becomes a no-op and the widget runs purely on indexed data.
		$eagerExif = $this->config->getAppValue(
			Application::APP_ID,
			'photostory.exif.eager_in_request',
			'no',
		) === 'yes';
		$eagerCap = (int)$this->config->getAppValue(
			Application::APP_ID,
			'photostory.exif.eager_cap',
			'200',
		);
		// Track eager-EXIF reads so far this request (counter is property-level).
		if ($eagerExif && $this->exifEagerCount >= $eagerCap) {
			$eagerExif = false;  // Cap reached; remaining files stay sparse.
		}
		if ($eagerExif) {
			$this->exifEagerCount++;
			$needsExif = $row['taken_at'] === null
				|| $row['camera'] === null
				|| ($row['location'] === null && $row['gps'] === null);
			if ($needsExif) {
				$localPath = $this->safeGetLocalFile($file);
				if ($localPath !== null) {
					$exif = $this->exifReader->read($localPath);
					if ($row['taken_at'] === null && isset($exif['taken_at']) && $exif['taken_at'] instanceof \DateTimeInterface) {
						$row['taken_at'] = $exif['taken_at']->format(\DateTimeInterface::ATOM);
					}
					if ($row['camera'] === null && !empty($exif['camera'])) {
						$row['camera'] = (string)$exif['camera'];
					}
					if (isset($exif['gps_lat'], $exif['gps_lon'])) {
						$row['gps'] = [
							'lat' => (float)$exif['gps_lat'],
							'lon' => (float)$exif['gps_lon'],
						];
					}
					if (!empty($exif['mime'])) {
						$row['mime'] = (string)$exif['mime'];
					}
				}
			}
		}

		return $row;
	}

	/**
	 * Lazy EXIF lookup for a single file. Used by the lightbox "Details" pane to
	 * fetch camera/lens/gps when MetaVox didn't supply them — without the cost of
	 * a full-folder eager EXIF pass.
	 *
	 * Returns the same shape as ExifReader::read() plus the file_id and resolved
	 * camera/gps fields ready for UI consumption.
	 *
	 * @return array{file_id: int, taken_at: ?string, camera: ?string, gps: ?array{lat: float, lon: float}, mime: ?string}
	 */
	public function getExifForFile(int $fileId): array {
		$user = $this->userSession->getUser();
		if ($user === null) {
			throw new \RuntimeException('Authentication required');
		}
		$userFolder = $this->rootFolder->getUserFolder($user->getUID());
		$nodes = $userFolder->getById($fileId);
		$file = null;
		foreach ($nodes as $n) {
			if ($n instanceof \OCP\Files\File) {
				$file = $n;
				break;
			}
		}
		if ($file === null) {
			throw new \RuntimeException('File not found or not accessible');
		}

		$out = [
			'file_id' => $fileId,
			'taken_at' => null,
			'camera' => null,
			'gps' => null,
			'mime' => (string)$file->getMimeType(),
		];

		// Try NC core FilesMetadata first — cheap (single DB row) and authoritative
		// for all files that NC's scanner has seen.
		$nc = $this->ncMeta->readOne($fileId);
		if (isset($nc['taken_at']) && $nc['taken_at'] instanceof \DateTimeInterface) {
			$out['taken_at'] = $nc['taken_at']->format(\DateTimeInterface::ATOM);
		}
		if (!empty($nc['camera'])) {
			$out['camera'] = (string)$nc['camera'];
		}
		if (isset($nc['gps']) && is_array($nc['gps'])) {
			$out['gps'] = ['lat' => (float)$nc['gps']['lat'], 'lon' => (float)$nc['gps']['lon']];
		}

		// If NC core didn't fill everything (very rare on scanned installations),
		// fall back to on-disk EXIF reader for the missing fields.
		$needsExif = $out['taken_at'] === null || $out['camera'] === null || $out['gps'] === null;
		if ($needsExif) {
			$localPath = $this->safeGetLocalFile($file);
			if ($localPath !== null) {
				$exif = $this->exifReader->read($localPath);
				if ($out['taken_at'] === null && isset($exif['taken_at']) && $exif['taken_at'] instanceof \DateTimeInterface) {
					$out['taken_at'] = $exif['taken_at']->format(\DateTimeInterface::ATOM);
				}
				if ($out['camera'] === null && !empty($exif['camera'])) {
					$out['camera'] = (string)$exif['camera'];
				}
				if ($out['gps'] === null && isset($exif['gps_lat'], $exif['gps_lon'])) {
					$out['gps'] = [
						'lat' => (float)$exif['gps_lat'],
						'lon' => (float)$exif['gps_lon'],
					];
				}
				if (!empty($exif['mime'])) {
					$out['mime'] = (string)$exif['mime'];
				}
			}
		}
		return $out;
	}

	/**
	 * Geocode enrichment shared between folder + cross-folder modes.
	 *
	 * @param array<int, array<string, mixed>> $result
	 * @return array<int, array<string, mixed>>
	 */
	private function enrichGeocode(array $result): array {
		// HTTP requests pass allowNetworkFetches=false → cache-only, misses are
		// queued for the GeocodeWarmupJob. Disabled-mode is handled inside
		// enrichPhotos (it still mirrors location → location_display).
		try {
			return $this->geocodeCache->enrichPhotos($result, false);
		} catch (\Throwable $e) {
			$this->logger->warning('PhotoStoryService: geocode enrichment failed: ' . $e->getMessage());
			return $result;
		}
	}

	/**
	 * Group photos by date (taken_at ?? mtime) into NL-formatted day buckets.
	 *
	 * @param array<int, array<string, mixed>> $photos
	 * @return array<int, array{date: string, label: string, location_summary: ?string, photos: array<int, array<string, mixed>>}>
	 */
	public function groupByTimeline(array $photos, string $sortOrder = 'asc', string $granularity = 'day', string $dateField = 'taken_at'): array {
		$sortOrder = ($sortOrder === 'desc') ? 'desc' : 'asc';
		$granularity = in_array($granularity, ['day', 'month', 'year'], true) ? $granularity : 'day';
		// dateField selects the column the timeline buckets by — must match the
		// sortBy the caller used in listPhotosPaged or buckets land out of order
		// (e.g. SQL sort on mtime + bucket sort on taken_at produces non-monotonic
		// day-headers because some photos' taken_at predates their mtime).
		$dateField = in_array($dateField, ['taken_at', 'mtime'], true) ? $dateField : 'taken_at';
		$cacheKey = 'tl:' . $this->photosFingerprint($photos) . ':' . $sortOrder . ':' . $granularity . ':' . $dateField;
		$hit = $this->cache?->get($cacheKey);
		if (is_string($hit)) {
			$decoded = json_decode($hit, true);
			if (is_array($decoded)) {
				return $decoded;
			}
		}
		$result = $this->computeTimeline($photos, $sortOrder, $granularity, $dateField);
		$this->cache?->set($cacheKey, json_encode($result, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE), 3600);
		return $result;
	}

	/**
	 * Fingerprint of a photo set for cache-key construction.
	 * Stable as long as the same files are present with the same mtimes.
	 */
	private function photosFingerprint(array $photos): string {
		$n = count($photos);
		$maxMtime = 0;
		$idSum = 0;
		foreach ($photos as $p) {
			if (isset($p['mtime']) && $p['mtime'] > $maxMtime) {
				$maxMtime = (int)$p['mtime'];
			}
			if (isset($p['file_id'])) {
				$idSum = ($idSum + (int)$p['file_id']) & 0x7fffffff;
			}
		}
		return $n . ':' . $maxMtime . ':' . $idSum;
	}

	private function computeTimeline(array $photos, string $sortOrder = 'asc', string $granularity = 'day', string $dateField = 'taken_at'): array {
		$resolve = ($dateField === 'mtime')
			? fn(array $p): int => (int)($p['mtime'] ?? 0)
			: fn(array $p): int => $this->resolveTimestamp($p);

		if ($sortOrder === 'desc') {
			usort($photos, fn($a, $b) => $resolve($b) <=> $resolve($a));
		} else {
			usort($photos, fn($a, $b) => $resolve($a) <=> $resolve($b));
		}

		$buckets = [];
		foreach ($photos as $photo) {
			$ts = $resolve($photo);
			$dt = (new \DateTimeImmutable('@' . $ts))->setTimezone(new \DateTimeZone(date_default_timezone_get()));

			// Bucket key + display label depend on the chosen granularity.
			// "day"   → 2026-05-08 / "Donderdag 8 mei 2026"   — natural for photos
			// "month" → 2026-05    / "Mei 2026"              — natural for docs (Q-reports, newsletters)
			// "year"  → 2026       / "2026"                  — broad archive view
			switch ($granularity) {
				case 'month':
					$dateKey = $dt->format('Y-m');
					$label = $this->localizedMonth((int)$dt->format('n')) . ' ' . $dt->format('Y');
					break;
				case 'year':
					$dateKey = $dt->format('Y');
					$label = $dateKey;
					break;
				case 'day':
				default:
					$dateKey = $dt->format('Y-m-d');
					$label = $this->formatDateLong($dt);
					break;
			}

			if (!isset($buckets[$dateKey])) {
				$buckets[$dateKey] = [
					'date' => $dateKey,
					'label' => $label,
					'location_summary' => null,
					'photos' => [],
					'_locations' => [],
				];
			}
			$buckets[$dateKey]['photos'][] = $photo;
			$locForBucket = !empty($photo['location_display'])
				? (string)$photo['location_display']
				: (!empty($photo['location']) ? (string)$photo['location'] : null);
			if ($locForBucket !== null) {
				$buckets[$dateKey]['_locations'][$locForBucket] = ($buckets[$dateKey]['_locations'][$locForBucket] ?? 0) + 1;
			}
		}

		$out = [];
		foreach ($buckets as $bucket) {
			$summary = null;
			if (!empty($bucket['_locations'])) {
				arsort($bucket['_locations']);
				$summary = (string)array_key_first($bucket['_locations']);
			}
			unset($bucket['_locations']);
			$bucket['location_summary'] = $summary;
			$out[] = $bucket;
		}

		return $out;
	}

	/**
	 * Multi-stage highlight selector: burst-dedup, score, diversity, temporal spread.
	 *
	 * @param array<int, array<string, mixed>> $photos
	 * @return array<int, array<string, mixed>>
	 */
	public function selectHighlights(array $photos, int $max = 30): array {
		if (empty($photos)) {
			return [];
		}
		// Memoize: deterministic over the photo set + max-N.
		$cacheKey = 'hl:' . $this->photosFingerprint($photos) . ':m=' . $max;
		$hit = $this->cache?->get($cacheKey);
		if (is_string($hit)) {
			$decoded = json_decode($hit, true);
			if (is_array($decoded)) {
				return $decoded;
			}
		}
		$result = $this->computeHighlights($photos, $max);
		$this->cache?->set($cacheKey, json_encode($result, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE), 3600);
		return $result;
	}

	private function computeHighlights(array $photos, int $max): array {
		// Stage 1: burst dedup — files within 3s of same camera, keep largest by size
		$deduped = $this->dedupBursts($photos);

		// Stage 2: scoring
		$sizeStats = $this->computeSizeStats($deduped);
		$locationCounts = [];
		foreach ($deduped as $p) {
			if (!empty($p['location'])) {
				$loc = (string)$p['location'];
				$locationCounts[$loc] = ($locationCounts[$loc] ?? 0) + 1;
			}
		}

		$scored = [];
		foreach ($deduped as $p) {
			$score = 1.0;
			$people = is_array($p['people'] ?? null) ? $p['people'] : [];
			$subjects = is_array($p['subjects'] ?? null) ? $p['subjects'] : [];

			$score += 0.3 * count($people);

			foreach ($subjects as $s) {
				if (in_array($s, self::HIGH_VALUE_SUBJECTS, true)) {
					$score += 0.2;
				}
			}

			if (!empty($p['location'])) {
				$loc = (string)$p['location'];
				if (($locationCounts[$loc] ?? 0) === 1) {
					$score += 0.5;
				}
			}

			$size = (int)($p['size'] ?? 0);
			if ($sizeStats['stddev'] > 0) {
				$z = ($size - $sizeStats['mean']) / $sizeStats['stddev'];
				$score += 0.1 * $z;
			}

			$scored[] = ['photo' => $p, 'score' => $score];
		}

		// Sort by score desc
		usort($scored, fn($a, $b) => $b['score'] <=> $a['score']);

		// Stage 3: diversity pass — greedy with penalty for repeated (location, top_subject) clusters
		$selected = [];
		$clusterCount = [];
		foreach ($scored as $entry) {
			$photo = $entry['photo'];
			$cluster = $this->clusterKey($photo);
			$current = $clusterCount[$cluster] ?? 0;
			if ($current >= 2) {
				// Skip; too many already in this cluster
				continue;
			}
			$selected[] = $photo;
			$clusterCount[$cluster] = $current + 1;
			if (count($selected) >= $max) {
				break;
			}
		}

		// If we ran out, top-up by ignoring cluster cap
		if (count($selected) < $max) {
			$selectedIds = [];
			foreach ($selected as $s) {
				$selectedIds[(int)$s['file_id']] = true;
			}
			foreach ($scored as $entry) {
				if (count($selected) >= $max) {
					break;
				}
				$photo = $entry['photo'];
				$fid = (int)$photo['file_id'];
				if (isset($selectedIds[$fid])) {
					continue;
				}
				$selected[] = $photo;
				$selectedIds[$fid] = true;
			}
		}

		// Stage 4: temporal spread — try to ensure selection spans total_days / N
		$selected = $this->temporalSpread($selected, $scored, $max);

		// Final sort by taken_at
		usort($selected, function ($a, $b) {
			return $this->resolveTimestamp($a) <=> $this->resolveTimestamp($b);
		});

		return $selected;
	}

	/**
	 * Group photos by location label, or by rounded GPS coords if no label.
	 *
	 * @param array<int, array<string, mixed>> $photos
	 * @return array<int, array{location: string, count: int, gps: ?array{lat: float, lon: float}, photo_ids: array<int, int>}>
	 */
	public function getLocationClusters(array $photos): array {
		$clusters = [];
		foreach ($photos as $p) {
			$label = $p['location'] ?? null;
			$gps = $p['gps'] ?? null;

			if (is_string($label) && $label !== '') {
				$key = 'L:' . $label;
				$displayLabel = $label;
				$gpsForCluster = is_array($gps) ? $gps : null;
			} elseif (is_array($gps) && isset($gps['lat'], $gps['lon'])) {
				// Round to ~1km grid (3 decimals ~ 100m, 2 decimals ~ 1km)
				$latR = round((float)$gps['lat'], 2);
				$lonR = round((float)$gps['lon'], 2);
				$key = sprintf('G:%.2f,%.2f', $latR, $lonR);
				$displayLabel = sprintf('%.4f, %.4f', $latR, $lonR);
				$gpsForCluster = ['lat' => $latR, 'lon' => $lonR];
			} else {
				continue;
			}

			if (!isset($clusters[$key])) {
				$clusters[$key] = [
					'location' => $displayLabel,
					'count' => 0,
					'gps' => $gpsForCluster,
					'photo_ids' => [],
				];
			}
			$clusters[$key]['count']++;
			$clusters[$key]['photo_ids'][] = (int)$p['file_id'];

			// If we get a GPS later for a label-only cluster, fill it in
			if ($clusters[$key]['gps'] === null && is_array($gps) && isset($gps['lat'], $gps['lon'])) {
				$clusters[$key]['gps'] = ['lat' => (float)$gps['lat'], 'lon' => (float)$gps['lon']];
			}
		}

		return array_values($clusters);
	}

	/**
	 * Heuristic title generation: location+month, country+year, or "Best of <year>".
	 *
	 * @param array<int, array<string, mixed>> $photos
	 */
	public function suggestTitle(array $photos, string $folderName = ''): string {
		if (empty($photos)) {
			return $folderName !== '' ? $folderName : 'Photo Story';
		}

		$countries = [];
		$locations = [];
		$months = [];
		$years = [];
		$timestamps = [];

		foreach ($photos as $p) {
			if (!empty($p['country'])) {
				$countries[(string)$p['country']] = true;
			}
			if (!empty($p['location'])) {
				$locations[(string)$p['location']] = ($locations[(string)$p['location']] ?? 0) + 1;
			}
			$ts = $this->resolveTimestamp($p);
			$timestamps[] = $ts;
			$dt = (new \DateTimeImmutable('@' . $ts))->setTimezone(new \DateTimeZone(date_default_timezone_get()));
			$months[$dt->format('Y-m')] = true;
			$years[$dt->format('Y')] = true;
		}

		$countryCount = count($countries);
		$monthCount = count($months);
		$yearCount = count($years);
		$daySpan = 0;
		if (!empty($timestamps)) {
			$daySpan = (int)floor((max($timestamps) - min($timestamps)) / 86400);
		}

		// Single country + single month → "<location> — <month> <year>"
		if ($countryCount === 1 && $monthCount === 1 && !empty($locations)) {
			arsort($locations);
			$topLoc = (string)array_key_first($locations);
			$monthKey = (string)array_key_first($months);
			$dt = \DateTimeImmutable::createFromFormat('Y-m', $monthKey);
			if ($dt !== false) {
				$month = $this->localizedMonth((int)$dt->format('n'));
				return sprintf('%s — %s %s', $topLoc, $month, $dt->format('Y'));
			}
		}

		// Single country, multiple months → "<country> <year>"
		if ($countryCount === 1 && $monthCount > 1) {
			$country = (string)array_key_first($countries);
			if ($yearCount === 1) {
				return sprintf('%s %s', $country, (string)array_key_first($years));
			}
			return $country;
		}

		// Span > 30 days and multiple countries → "Best of <year>"
		if ($daySpan > 30 && $countryCount > 1 && $yearCount === 1) {
			return 'Best of ' . (string)array_key_first($years);
		}

		// Fallback to foldername (or generic)
		return $folderName !== '' ? $folderName : 'Photo Story';
	}

	/**
	 * "On this day": photos whose taken_at month+day match $today; sorted year-desc.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	public function onThisDay(string $folderPath, \DateTimeInterface $today): array {
		$mmdd = $today->format('m-d');
		$matches = [];
		$processed = 0;
		$offset = 0;
		$pageSize = 500;
		// Safety caps: stop after scanning 5000 photos OR after collecting 200 matches.
		// On-this-day visualisation rarely shows more than a year-strip of ~10–30 items,
		// so 200 is generous. Prevents OOM on 50k+ libraries.
		$processedCap = 5000;
		$matchCap = 200;

		while ($processed < $processedCap && count($matches) < $matchCap) {
			$paged = $this->listPhotosPaged($folderPath, [], $offset, $pageSize, 'desc', null, 'mtime');
			$batch = $paged['photos'];
			if (empty($batch)) {
				break;
			}
			foreach ($batch as $p) {
				$processed++;
				if (empty($p['taken_at'])) {
					continue;
				}
				try {
					$dt = new \DateTimeImmutable((string)$p['taken_at']);
				} catch (\Throwable $e) {
					continue;
				}
				if ($dt->format('m-d') === $mmdd) {
					$matches[] = $p;
					if (count($matches) >= $matchCap) break;
				}
			}
			if (empty($paged['hasMore'])) {
				break;
			}
			$offset += $pageSize;
		}

		usort($matches, function ($a, $b) {
			$ta = new \DateTimeImmutable((string)$a['taken_at']);
			$tb = new \DateTimeImmutable((string)$b['taken_at']);
			return $tb->format('Y') <=> $ta->format('Y');
		});

		return $matches;
	}

	// ─────────────────────────────────────────────────────────────────────────
	// Internal helpers
	// ─────────────────────────────────────────────────────────────────────────

	/**
	 * Batch-fetch MetaVox field values for a set of file ids in a single query.
	 *
	 * @param array<int, int> $fileIds
	 * @return array<int, array<string, string>> file_id => [field_name => value]
	 */
	/**
	 * Look up the value to sort a row by, given a sort key (NC-core key or
	 * MetaVox field name). Returns null when no value is available.
	 *
	 * @param array<string, mixed> $row Built photo row (from buildPhotoRow)
	 * @param array<string, string> $mvForRow Raw MetaVox fields for this file
	 */
	private function extractMetaSortValue(array $row, array $mvForRow, string $key): mixed {
		// NC-core "taken_at" lives on the row directly. Fallback to mv exif_taken_at.
		if ($key === 'taken_at') {
			if (!empty($row['taken_at'])) {
				return (string)$row['taken_at'];
			}
			if (!empty($mvForRow['exif_taken_at'])) {
				return (string)$mvForRow['exif_taken_at'];
			}
			return null;
		}
		// Well-known photo fields that have a dedicated row slot.
		if ($key === 'exif_location') {
			return $row['location'] ?? null;
		}
		if ($key === 'exif_country') {
			return $row['country'] ?? null;
		}
		if ($key === 'exif_camera') {
			return $row['camera'] ?? null;
		}
		// Generic MetaVox field — first try the raw mv table, then the meta dict.
		if (array_key_exists($key, $mvForRow)) {
			return $mvForRow[$key];
		}
		if (isset($row['meta'][$key])) {
			return $row['meta'][$key];
		}
		return null;
	}

	/**
	 * Compare two sort-values. nulls sort last, strings case-insensitive,
	 * numerics natural.
	 */
	private function compareSortValues(mixed $a, mixed $b): int {
		// nulls always go to the end regardless of direction
		if ($a === null && $b === null) return 0;
		if ($a === null) return 1;
		if ($b === null) return -1;
		if (is_numeric($a) && is_numeric($b)) {
			return (float)$a <=> (float)$b;
		}
		return strcasecmp((string)$a, (string)$b);
	}

	/**
	 * Extract unique field names from a list of filter rows. Used to widen
	 * the MetaVox fetch beyond the WELL_KNOWN_PHOTO_FIELDS baseline so user-
	 * configured filters on custom fields (project, status, author, etc.)
	 * actually have values to match against.
	 *
	 * @param array<int, array{field?: string, op?: string, value?: mixed}> $filters
	 * @return array<int, string>
	 */
	private function fieldNamesFromFilters(array $filters): array {
		$names = [];
		foreach ($filters as $f) {
			if (is_array($f) && !empty($f['field']) && is_string($f['field'])) {
				$names[] = $f['field'];
			}
		}
		return array_values(array_unique($names));
	}

	private function fetchMetaVoxMeta(array $fileIds, int $groupfolderId, array $extraFieldNames = []): array {
		if (empty($fileIds)) {
			return [];
		}

		// Union of well-known photo fields (driving special UI like day-headers,
		// maps, highlight-scoring) and any extra fields the caller needs for
		// filtering or sorting. Deduplicated.
		$fieldsOfInterest = array_values(array_unique(array_merge(
			self::WELL_KNOWN_PHOTO_FIELDS,
			array_filter(array_map('strval', $extraFieldNames), fn($n) => $n !== '')
		)));

		// Chunk file_ids to stay within NC QueryBuilder's IN-list cap (1000 on Oracle).
		$out = [];
		foreach (array_chunk(array_values(array_unique(array_map('intval', $fileIds))), 500) as $chunk) {
			try {
				$qb = $this->db->getQueryBuilder();
				$qb->select('file_id', 'field_name', 'field_value')
					->from('metavox_file_gf_meta')
					->where($qb->expr()->in('file_id', $qb->createNamedParameter($chunk, \OCP\DB\QueryBuilder\IQueryBuilder::PARAM_INT_ARRAY)))
					->andWhere($qb->expr()->eq('groupfolder_id', $qb->createNamedParameter($groupfolderId, \OCP\DB\QueryBuilder\IQueryBuilder::PARAM_INT)))
					->andWhere($qb->expr()->in('field_name', $qb->createNamedParameter($fieldsOfInterest, \OCP\DB\QueryBuilder\IQueryBuilder::PARAM_STR_ARRAY)));

				$result = $qb->executeQuery();
				while ($row = $result->fetch()) {
					$fid = (int)$row['file_id'];
					$fname = (string)$row['field_name'];
					$fval = (string)$row['field_value'];
					if (!isset($out[$fid])) {
						$out[$fid] = [];
					}
					$out[$fid][$fname] = $fval;
				}
				$result->closeCursor();
			} catch (\Throwable $e) {
				$this->logger->warning('PhotoStoryService: MetaVox query chunk failed: ' . $e->getMessage());
			}
		}
		return $out;
	}

	/**
	 * Cross-folder variant: batch-fetch MetaVox values without filtering by groupfolder_id.
	 * Permission scoping happens later via $userFolder->getById().
	 *
	 * @param array<int, int> $fileIds
	 * @return array<int, array<string, string>>
	 */
	private function fetchMetaVoxMetaAllGroupfolders(array $fileIds, array $extraFieldNames = []): array {
		if (empty($fileIds)) {
			return [];
		}

		$fieldsOfInterest = array_values(array_unique(array_merge(
			self::WELL_KNOWN_PHOTO_FIELDS,
			array_filter(array_map('strval', $extraFieldNames), fn($n) => $n !== '')
		)));

		// Chunk file_ids to respect NC QueryBuilder's IN-list cap.
		$out = [];
		foreach (array_chunk(array_values(array_unique(array_map('intval', $fileIds))), 500) as $chunk) {
			try {
				$qb = $this->db->getQueryBuilder();
				$qb->select('file_id', 'field_name', 'field_value')
					->from('metavox_file_gf_meta')
					->where($qb->expr()->in('file_id', $qb->createNamedParameter($chunk, \OCP\DB\QueryBuilder\IQueryBuilder::PARAM_INT_ARRAY)))
					->andWhere($qb->expr()->in('field_name', $qb->createNamedParameter($fieldsOfInterest, \OCP\DB\QueryBuilder\IQueryBuilder::PARAM_STR_ARRAY)));

				$result = $qb->executeQuery();
				while ($row = $result->fetch()) {
					$fid = (int)$row['file_id'];
					$fname = (string)$row['field_name'];
					$fval = (string)$row['field_value'];
					if (!isset($out[$fid])) {
						$out[$fid] = [];
					}
					// If same field exists across multiple groupfolders, keep the first (any-folder match).
					if (!isset($out[$fid][$fname])) {
						$out[$fid][$fname] = $fval;
					}
				}
				$result->closeCursor();
			} catch (\Throwable $e) {
				$this->logger->warning('PhotoStoryService: cross-folder MetaVox query chunk failed: ' . $e->getMessage());
			}
		}
		return $out;
	}

	/**
	 * Find file_ids whose MetaVox metadata matches ALL given filters (AND).
	 * Permission scoping is applied later via $userFolder->getById().
	 *
	 * @param array<int, array{field: string, op: string, value: mixed}> $filters
	 * @return array<int, int>
	 */
	private function queryFileIdsMatchingFilters(array $filters): array {
		if (empty($filters)) {
			return [];
		}

		$matchingIdSets = [];

		foreach ($filters as $filter) {
			$field = (string)($filter['field'] ?? '');
			$op = (string)($filter['op'] ?? 'equals');
			$value = $filter['value'] ?? '';

			if ($field === '' || !in_array($op, self::ALLOWED_OPS, true)) {
				// Be defensive — controller should already sanitize, but ignore bad rows here.
				continue;
			}

			try {
				$qb = $this->db->getQueryBuilder();
				$qb->select('file_id')
					->from('metavox_file_gf_meta')
					->where($qb->expr()->eq('field_name', $qb->createNamedParameter($field)));

				switch ($op) {
					case 'equals':
						$qb->andWhere($qb->expr()->eq('field_value', $qb->createNamedParameter((string)$value)));
						break;
					case 'contains':
						$like = '%' . $this->escapeLikePattern((string)$value) . '%';
						$qb->andWhere($qb->expr()->like('field_value', $qb->createNamedParameter($like)));
						break;
					case 'in':
						$values = is_array($value) ? array_map('strval', $value) : [(string)$value];
						$values = array_values(array_filter($values, fn($v) => $v !== ''));
						if (empty($values)) {
							$matchingIdSets[] = [];
							continue 2;
						}
						$qb->andWhere($qb->expr()->in('field_value', $qb->createNamedParameter($values, \OCP\DB\QueryBuilder\IQueryBuilder::PARAM_STR_ARRAY)));
						break;
					case 'year_equals':
						$year = (string)$value;
						if (!preg_match('/^\d{4}$/', $year)) {
							$matchingIdSets[] = [];
							continue 2;
						}
						$like = $year . '%';
						$qb->andWhere($qb->expr()->like('field_value', $qb->createNamedParameter($like)));
						break;
				}

				$result = $qb->executeQuery();
				$ids = [];
				while ($row = $result->fetch()) {
					$ids[(int)$row['file_id']] = true;
				}
				$result->closeCursor();
				$matchingIdSets[] = array_keys($ids);
			} catch (\Throwable $e) {
				$this->logger->warning('PhotoStoryService: filter query failed: ' . $e->getMessage());
				return [];
			}
		}

		if (empty($matchingIdSets)) {
			return [];
		}

		// AND across all filter result sets via array_intersect.
		$intersection = $matchingIdSets[0];
		for ($i = 1, $n = count($matchingIdSets); $i < $n; $i++) {
			$intersection = array_intersect($intersection, $matchingIdSets[$i]);
			if (empty($intersection)) {
				return [];
			}
		}

		return array_values(array_map('intval', $intersection));
	}

	/**
	 * Check whether a photo's MetaVox values satisfy the given filters (AND).
	 *
	 * @param array<string, mixed> $row
	 * @param array<string, string> $mv field_name => value
	 * @param array<int, array{field: string, op: string, value: mixed}> $filters
	 */
	private function photoMatchesFilters(array $row, array $mv, array $filters): bool {
		foreach ($filters as $filter) {
			$field = (string)($filter['field'] ?? '');
			$op = (string)($filter['op'] ?? 'equals');
			$value = $filter['value'] ?? '';

			if ($field === '' || !in_array($op, self::ALLOWED_OPS, true)) {
				continue;
			}

			$actual = (string)($mv[$field] ?? '');

			switch ($op) {
				case 'equals':
					if ($actual !== (string)$value) {
						return false;
					}
					break;
				case 'contains':
					if ((string)$value === '' || stripos($actual, (string)$value) === false) {
						return false;
					}
					break;
				case 'in':
					$values = is_array($value) ? array_map('strval', $value) : [(string)$value];
					if (!in_array($actual, $values, true)) {
						return false;
					}
					break;
				case 'year_equals':
					$year = (string)$value;
					if (!preg_match('/^\d{4}$/', $year) || strpos($actual, $year) !== 0) {
						return false;
					}
					break;
			}
		}
		return true;
	}


	/**
	 * Discover all `exif_*` MetaVox fields visible to the current user (any groupfolder).
	 * Returns field info + the top-100 most common values per field for the filter-builder dropdown.
	 *
	 * Strategy: query oc_metavox_file_gf_meta DISTINCT on field_name (so we don't depend on
	 * MetaVox FieldService — keeps the cross-folder query independent of which group's
	 * field is "assigned"). For multiselect-ish fields (people/subjects), split `;#` first.
	 *
	 * @return array<int, array{field_name: string, field_label: string, field_type: string, options: array<int, string>}>
	 */
	public function getMetaVoxFieldsForPhotoStory(): array {
		if ($this->cachedExifFields !== null) {
			return $this->cachedExifFields;
		}

		if (!$this->isMetaVoxAvailable()) {
			return $this->cachedExifFields = [];
		}

		// Scope: prefer groupfolder-bound fields (security via gf-ACL), but fall
		// back on global MetaVox file-level fields when the user has no group-
		// folders or the current source folder lives in personal storage. The
		// global fallback is safe because MetaVox file-level fields are scoped
		// at the file level — only files the user can read return values.
		$accessibleGfIds = $this->getAccessibleGroupfolderIds();

		$user = $this->userSession->getUser();
		$cacheKey = 'psfields:' . ($user ? $user->getUID() : '_anon') . ':' . md5(implode(',', $accessibleGfIds));
		if ($this->cache !== null) {
			$hit = $this->cache->get($cacheKey);
			if (is_string($hit)) {
				$decoded = json_decode($hit, true);
				if (is_array($decoded)) {
					return $this->cachedExifFields = $decoded;
				}
			}
		}

		$fieldService = $this->getMetaVoxFieldService();
		if ($fieldService === null) {
			$this->logger->debug('PhotoStoryService: FieldService unavailable');
			return $this->cachedExifFields = [];
		}

		$multiTypes = ['multiselect', 'tags'];
		$byName = [];

		// Pass 1: collect groupfolder-bound assigned fields (when user has gf access).
		if (!empty($accessibleGfIds) && method_exists($fieldService, 'getAssignedFileFieldsForGroupfolder')) {
			foreach ($accessibleGfIds as $gfId) {
				try {
					$fields = $fieldService->getAssignedFileFieldsForGroupfolder((int)$gfId);
				} catch (\Throwable $e) {
					$this->logger->debug('PhotoStoryService: getAssignedFileFieldsForGroupfolder(' . $gfId . ') failed: ' . $e->getMessage());
					continue;
				}
				if (!is_array($fields)) {
					continue;
				}
				foreach ($fields as $f) {
					if (!is_array($f) || empty($f['field_name'])) {
						continue;
					}
					$name = (string)$f['field_name'];
					if (isset($byName[$name])) {
						continue;
					}
					$byName[$name] = [
						'field_name' => $name,
						'field_label' => (string)($f['field_label'] ?? $name),
						'field_type' => (string)($f['field_type'] ?? 'text'),
						'field_options' => is_array($f['field_options'] ?? null) ? $f['field_options'] : [],
					];
				}
			}
		}

		// Pass 2: union with global file-level fields. These are the fields a
		// user has defined in MetaVox without binding them to a specific group-
		// folder — applicable to personal-storage folders and to any file the
		// user can read. Filter on applies_to_groupfolder=0 to exclude folder-
		// level fields (which don't apply to individual files).
		if (method_exists($fieldService, 'getAllFields')) {
			try {
				$all = $fieldService->getAllFields();
				if (is_array($all)) {
					foreach ($all as $f) {
						if (!is_array($f) || empty($f['field_name'])) {
							continue;
						}
						// Skip folder-level fields — they describe the folder itself, not files in it.
						if ((int)($f['applies_to_groupfolder'] ?? 0) !== 0) {
							continue;
						}
						$name = (string)$f['field_name'];
						if (isset($byName[$name])) {
							continue;
						}
						$byName[$name] = [
							'field_name' => $name,
							'field_label' => (string)($f['field_label'] ?? $name),
							'field_type' => (string)($f['field_type'] ?? 'text'),
							'field_options' => is_array($f['field_options'] ?? null) ? $f['field_options'] : [],
						];
					}
				}
			} catch (\Throwable $e) {
				$this->logger->debug('PhotoStoryService: getAllFields() failed: ' . $e->getMessage());
			}
		}

		$out = [];
		foreach ($byName as $name => $def) {
			$type = $def['field_type'];
			$isMulti = in_array($type, $multiTypes, true);

			// Options dropdown logic per type:
			//  - select/multiselect with defined field_options → use those
			//  - text/multi without field_options → fetch top-100 distinct values from DB
			//  - date/number/checkbox/user/filelink/url → no options dropdown, free input
			$options = [];
			if (in_array($type, ['select', 'multiselect', 'dropdown'], true) && !empty($def['field_options'])) {
				$options = array_values(array_map('strval', $def['field_options']));
			} elseif (in_array($type, ['text', 'multiselect', 'tags'], true)) {
				// Free-text: top-N most common values seen in the data for typeahead.
				$options = $this->fetchTopValuesForField($name, $isMulti, $accessibleGfIds);
			}

			$out[] = [
				'field_name' => $name,
				'field_label' => $def['field_label'],
				'field_type' => $type,
				'options' => $options,
			];
		}

		// Stable order: alphabetical by label for consistent UI rendering.
		usort($out, fn($a, $b) => strcasecmp($a['field_label'], $b['field_label']));

		if ($this->cache !== null) {
			$this->cache->set($cacheKey, json_encode($out, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE), 600);
		}

		return $this->cachedExifFields = $out;
	}

	/**
	 * Groupfolder ids the current user can access (read permission >=). Used
	 * to scope all "global" MetaVox value queries that would otherwise leak
	 * data across users.
	 *
	 * @return int[]
	 */
	private function getAccessibleGroupfolderIds(): array {
		$user = $this->userSession->getUser();
		if ($user === null) {
			return [];
		}
		try {
			if (!$this->appManager->isEnabledForUser('groupfolders')) {
				return [];
			}
			/** @var \OCA\GroupFolders\Folder\FolderManager $fm */
			$fm = \OC::$server->get(\OCA\GroupFolders\Folder\FolderManager::class);
			$folders = $fm->getFoldersForUser($user);
			$ids = [];
			foreach ($folders as $entry) {
				// FolderManager evolved across NC versions / GroupFolders releases:
				//   - older: plain assoc-array with key 'folder_id'
				//   - newer: FolderDefinition / FolderDefinitionWithPermissions object
				//     with public `id` property and/or `getId()`
				$id = 0;
				if (is_array($entry)) {
					$id = (int)($entry['folder_id'] ?? $entry['id'] ?? 0);
				} elseif (is_object($entry)) {
					if (method_exists($entry, 'getId')) {
						$id = (int)$entry->getId();
					} elseif (property_exists($entry, 'id')) {
						$id = (int)$entry->id;
					} elseif (property_exists($entry, 'folder_id')) {
						$id = (int)$entry->folder_id;
					}
				}
				if ($id > 0) {
					$ids[] = $id;
				}
			}
			return array_values(array_unique($ids));
		} catch (\Throwable $e) {
			$this->logger->warning('PhotoStoryService: getAccessibleGroupfolderIds failed: ' . $e->getMessage());
			return [];
		}
	}

	/**
	 * Fetch the top-100 most common values for a given field_name, scoped to
	 * the groupfolder-ids the current user can access. For non-multiselect
	 * fields the aggregation happens in SQL (GROUP BY field_value); multiselect
	 * fields still split in PHP because `;#` is not a SQL-aggregatable value.
	 *
	 * @param int[] $accessibleGfIds
	 * @return array<int, string>
	 */
	private function fetchTopValuesForField(string $fieldName, bool $isMultiselect, array $accessibleGfIds): array {
		// Personal-storage / no-groupfolder context: include rows where
		// groupfolder_id=0 (MetaVox's convention for file-level fields not
		// tied to a specific groupfolder). When the user has actual gf access,
		// merge gf=0 with their accessible gf-ids so values from both scopes
		// are surfaced.
		$scopeIds = !empty($accessibleGfIds) ? array_values(array_unique(array_merge($accessibleGfIds, [0]))) : [0];

		try {
			$qb = $this->db->getQueryBuilder();
			if ($isMultiselect) {
				// Multiselect: need raw values to split client-side.
				$qb->select('field_value')
					->from('metavox_file_gf_meta')
					->where($qb->expr()->eq('field_name', $qb->createNamedParameter($fieldName)))
					->andWhere($qb->expr()->neq('field_value', $qb->createNamedParameter('')))
					->andWhere($qb->expr()->in(
						'groupfolder_id',
						$qb->createNamedParameter($scopeIds, \OCP\DB\QueryBuilder\IQueryBuilder::PARAM_INT_ARRAY)
					));
				$result = $qb->executeQuery();
				$counts = [];
				while ($row = $result->fetch()) {
					$raw = (string)$row['field_value'];
					foreach ($this->splitMultiselect($raw) as $piece) {
						if ($piece !== '') {
							$counts[$piece] = ($counts[$piece] ?? 0) + 1;
						}
					}
				}
				$result->closeCursor();
				arsort($counts);
				return array_slice(array_keys($counts), 0, 100);
			}

			// Single-value: SQL-side GROUP BY + COUNT + ORDER BY + LIMIT.
			$qb->select('field_value', $qb->func()->count('*', 'cnt'))
				->from('metavox_file_gf_meta')
				->where($qb->expr()->eq('field_name', $qb->createNamedParameter($fieldName)))
				->andWhere($qb->expr()->neq('field_value', $qb->createNamedParameter('')))
				->andWhere($qb->expr()->in(
					'groupfolder_id',
					$qb->createNamedParameter($scopeIds, \OCP\DB\QueryBuilder\IQueryBuilder::PARAM_INT_ARRAY)
				))
				->groupBy('field_value')
				->orderBy('cnt', 'DESC')
				->setMaxResults(100);
			$result = $qb->executeQuery();
			$values = [];
			while ($row = $result->fetch()) {
				$values[] = (string)$row['field_value'];
			}
			$result->closeCursor();
			return $values;
		} catch (\Throwable $e) {
			$this->logger->warning('PhotoStoryService: top-values query failed for ' . $fieldName . ': ' . $e->getMessage());
			return [];
		}
	}

	/**
	 * Resolve the groupfolder id for a folder node, or null when the node is
	 * not inside a groupfolder mount.
	 *
	 * Strategy (in order of preference):
	 *  1. Typed: if the storage is an instance of GroupFolderStorage, ask it
	 *     directly via its public `getFolderId()`. Stable across NC versions.
	 *  2. Path: the canonical `__groupfolders/<id>/...` pattern in either the
	 *     full path or the internal path. Works when class_exists() is false
	 *     (GroupFolders app not yet loaded into the autoloader) or when a
	 *     wrapper hides the underlying storage type.
	 *  3. Storage-id: legacy `group_folder::<id>` form as a last-resort regex.
	 */
	/**
	 * Request-scoped memo. Walking the storage-wrapper chain is cheap (max 10
	 * iterations) but `getFolderId()` can hit the DB on some NC versions, and
	 * `listPhotosPaged` calls this once per page fetch on the same node.
	 *
	 * @var array<int, ?int> Keyed by node file_id.
	 */
	private array $groupfolderIdCache = [];

	private function extractGroupfolderId(Folder $node): ?int {
		$cacheKey = (int)$node->getId();
		if (array_key_exists($cacheKey, $this->groupfolderIdCache)) {
			return $this->groupfolderIdCache[$cacheKey];
		}
		$result = $this->extractGroupfolderIdUncached($node);
		return $this->groupfolderIdCache[$cacheKey] = $result;
	}

	private function extractGroupfolderIdUncached(Folder $node): ?int {
		try {
			// (1) Walk through Storage wrappers (Trashbin, PermissionsMask,
			// encryption wrappers, etc.) until we find a GroupFolder storage
			// or run out of wrappers. NC's storage stack normally looks like
			// `Files_Trashbin\Storage → PermissionsMask → GroupFolderStorage`
			// so we cannot just check the outermost type.
			$storage = $node->getStorage();
			$visited = 0;
			while ($storage !== null && $visited++ < 10) {
				if (method_exists($storage, 'getFolderId')) {
					try {
						$id = (int)$storage->getFolderId();
						if ($id > 0) {
							return $id;
						}
					} catch (\Throwable $e) {
						// Method exists but blew up — keep unwrapping.
					}
				}
				if (method_exists($storage, 'getWrapperStorage')) {
					$inner = $storage->getWrapperStorage();
					if ($inner === $storage || $inner === null) {
						break;
					}
					$storage = $inner;
					continue;
				}
				break;
			}

			// (2) Path-pattern fallback — internal-path on the inner mount is
			// often `__groupfolders/<id>/...`. Less reliable on personal-storage
			// edge cases but harmless to try.
			if (preg_match('#/__groupfolders/(\d+)/#', $node->getPath(), $matches)) {
				return (int)$matches[1];
			}
			$internal = $node->getInternalPath();
			if (preg_match('#__groupfolders/(\d+)#', $internal, $m)) {
				return (int)$m[1];
			}

			// (3) Storage-id last-resort.
			$storage = $node->getStorage();
			if (method_exists($storage, 'getId')) {
				$sid = (string)$storage->getId();
				if (preg_match('/group_folder.*?(\d+)/', $sid, $m)) {
					return (int)$m[1];
				}
			}
		} catch (\Throwable $e) {
			$this->logger->debug('PhotoStoryService: groupfolder id extraction failed: ' . $e->getMessage());
		}
		return null;
	}

	/**
	 * Detect whether a given storage (or any of its wrapped inner storages)
	 * is a federated/external share originating on a remote Nextcloud.
	 *
	 * Federated shares use OCA\Files_Sharing\External\Storage. Like with
	 * groupfolders, the actual class is buried under wrappers (Trashbin,
	 * PermissionsMask, encryption), so we walk the wrapper chain.
	 *
	 * Guarded by class_exists() so the code keeps loading on instances
	 * without the Files_Sharing app (rare but theoretically possible).
	 */
	private function isFederatedShareStorage($storage): bool {
		if ($storage === null) {
			return false;
		}
		// The class name is the source of truth — both \OCA\Files_Sharing\External\Storage
		// (incoming) and \OCA\FederatedFileSharing\OCM\... (modern OCM) live in slightly
		// different paths across NC versions. We match by partial class name to stay
		// resilient to those rename rounds.
		$visited = 0;
		while ($storage !== null && $visited++ < 10) {
			$cls = get_class($storage);
			if (str_contains($cls, 'Files_Sharing\\External\\Storage')
				|| str_contains($cls, 'FederatedFileSharing\\')
				|| str_contains($cls, 'SharingExternal\\Storage')) {
				return true;
			}
			if (method_exists($storage, 'getWrapperStorage')) {
				$inner = $storage->getWrapperStorage();
				if ($inner === $storage || $inner === null) {
					break;
				}
				$storage = $inner;
				continue;
			}
			break;
		}
		return false;
	}

	/**
	 * Set of federated storage_ids, eagerly computed on first call via a
	 * single SQL JOIN. Null = not yet computed. After computation: a flat
	 * lookup array keyed by numeric storage_id with `true` for every
	 * federated mount.
	 *
	 * @var array<int, true>|null Request-scoped memo.
	 */
	private ?array $federatedStorageIds = null;

	/**
	 * Is this storage_id pointing at an incoming federated share?
	 *
	 * Implementation: one SQL on first call that pre-loads every federated
	 * storage_id (joining `oc_storages.id = 'shared::' || share_external.mountpoint_hash`).
	 * Subsequent calls are O(1) hash lookup. This avoids the expensive
	 * MountManager->findIn('/') enumeration per call that the first cut used.
	 */
	private function isStorageIdFederated(int $storageId): bool {
		if ($this->federatedStorageIds === null) {
			$this->federatedStorageIds = $this->loadFederatedStorageIds();
		}
		return isset($this->federatedStorageIds[$storageId]);
	}

	/**
	 * One-shot lookup of every federated storage on this NC instance.
	 *
	 * NC's `\OCA\Files_Sharing\External\Storage::__construct()` writes storage
	 * id = `shared::md5(token + '@' + cloudId->getRemote())`, where the cloud-id
	 * strips a trailing slash from the remote. We mirror that in PHP because
	 * MD5+CONCAT in SQL is dialect-fragile across MySQL/PostgreSQL/SQLite.
	 *
	 * Two cheap queries per request (oc_share_external is usually < 50 rows
	 * even on busy instances; oc_storages.id has a UNIQUE index).
	 *
	 * @return array<int, true>
	 */
	private function loadFederatedStorageIds(): array {
		$result = [];
		try {
			// Step 1: every accepted incoming federated share.
			$qb = $this->db->getQueryBuilder();
			$qb->select('share_token', 'remote')
				->from('share_external')
				->where($qb->expr()->eq('accepted', $qb->createNamedParameter(1, \OCP\DB\QueryBuilder\IQueryBuilder::PARAM_INT)));
			$rs = $qb->executeQuery();
			$expectedIds = [];
			while ($row = $rs->fetch()) {
				$remote = rtrim((string)$row['remote'], '/');
				$token = (string)$row['share_token'];
				if ($token === '' || $remote === '') continue;
				$expectedIds[] = 'shared::' . md5($token . '@' . $remote);
			}
			$rs->closeCursor();
			if (empty($expectedIds)) return $result;

			// Step 2: resolve those storage strings to numeric storage_ids.
			$qb2 = $this->db->getQueryBuilder();
			$qb2->select('numeric_id')
				->from('storages')
				->where($qb2->expr()->in('id', $qb2->createNamedParameter($expectedIds, \OCP\DB\QueryBuilder\IQueryBuilder::PARAM_STR_ARRAY)));
			$rs2 = $qb2->executeQuery();
			while ($row = $rs2->fetch()) {
				$result[(int)$row['numeric_id']] = true;
			}
			$rs2->closeCursor();
		} catch (\Throwable $e) {
			$this->logger->debug('PhotoStoryService: loadFederatedStorageIds failed: ' . $e->getMessage());
		}
		return $result;
	}

	/**
	 * Extract a hint about the remote source of a federated share — used
	 * for the editor info-message. Returns null if the node isn't federated.
	 *
	 * @return ?array{remote: string, mount_point: string}
	 */
	private function extractFederatedShareInfo(Folder $node): ?array {
		try {
			$storage = $node->getStorage();
			if (!$this->isFederatedShareStorage($storage)) {
				return null;
			}
			$remote = '';
			// Storage class typically exposes getRemote() / getName() on
			// the External\Storage class. Best-effort — failure leaves
			// remote as empty string.
			while ($storage !== null) {
				if (method_exists($storage, 'getRemote')) {
					try {
						$remote = (string)$storage->getRemote();
						if ($remote !== '') break;
					} catch (\Throwable $e) {
					}
				}
				if (method_exists($storage, 'getWrapperStorage')) {
					$inner = $storage->getWrapperStorage();
					if ($inner === $storage || $inner === null) break;
					$storage = $inner;
					continue;
				}
				break;
			}
			return [
				'remote' => $remote,
				'mount_point' => (string)$node->getPath(),
			];
		} catch (\Throwable $e) {
			return null;
		}
	}

	private function safeGetLocalFile($file): ?string {
		try {
			$storage = $file->getStorage();
			$internal = $file->getInternalPath();
			$local = $storage->getLocalFile($internal);
			if (is_string($local) && $local !== '' && is_readable($local)) {
				return $local;
			}
		} catch (\Throwable $e) {
			$this->logger->debug('PhotoStoryService: getLocalFile failed: ' . $e->getMessage());
		}
		return null;
	}

	/**
	 * @param array<int, array<string, mixed>> $photos
	 * @return array<int, array<string, mixed>>
	 */
	private function dedupBursts(array $photos): array {
		// Sort by (camera, taken_at)
		$sortable = $photos;
		usort($sortable, function ($a, $b) {
			$ca = (string)($a['camera'] ?? '');
			$cb = (string)($b['camera'] ?? '');
			$cmp = strcmp($ca, $cb);
			if ($cmp !== 0) {
				return $cmp;
			}
			return $this->resolveTimestamp($a) <=> $this->resolveTimestamp($b);
		});

		$kept = [];
		$burstWinner = null;
		$burstCamera = null;
		$burstAnchor = null;

		foreach ($sortable as $p) {
			$cam = (string)($p['camera'] ?? '');
			$ts = $this->resolveTimestamp($p);

			if ($burstWinner === null) {
				$burstWinner = $p;
				$burstCamera = $cam;
				$burstAnchor = $ts;
				continue;
			}

			if ($cam === $burstCamera && abs($ts - $burstAnchor) <= 3) {
				// Same burst — keep largest
				if ((int)($p['size'] ?? 0) > (int)($burstWinner['size'] ?? 0)) {
					$burstWinner = $p;
				}
				// Note: don't advance anchor on every entry — anchor stays so a 3s window holds
			} else {
				$kept[] = $burstWinner;
				$burstWinner = $p;
				$burstCamera = $cam;
				$burstAnchor = $ts;
			}
		}
		if ($burstWinner !== null) {
			$kept[] = $burstWinner;
		}
		return $kept;
	}

	/**
	 * @param array<int, array<string, mixed>> $photos
	 * @return array{mean: float, stddev: float}
	 */
	private function computeSizeStats(array $photos): array {
		$sizes = array_map(fn($p) => (float)($p['size'] ?? 0), $photos);
		$n = count($sizes);
		if ($n === 0) {
			return ['mean' => 0.0, 'stddev' => 0.0];
		}
		$mean = array_sum($sizes) / $n;
		$variance = 0.0;
		foreach ($sizes as $s) {
			$variance += ($s - $mean) ** 2;
		}
		$variance /= $n;
		return ['mean' => $mean, 'stddev' => sqrt($variance)];
	}

	/**
	 * @param array<string, mixed> $photo
	 */
	private function clusterKey(array $photo): string {
		$loc = (string)($photo['location'] ?? '');
		$subjects = is_array($photo['subjects'] ?? null) ? $photo['subjects'] : [];
		$topSubject = !empty($subjects) ? (string)$subjects[0] : '';
		if ($loc === '' && $topSubject === '') {
			return '__none__:' . (int)($photo['file_id'] ?? 0);
		}
		return $loc . '|' . $topSubject;
	}

	/**
	 * Ensure the selection spans at least total_days / N days; swap closely-grouped
	 * photos out for under-represented days when possible.
	 *
	 * @param array<int, array<string, mixed>> $selected
	 * @param array<int, array{photo: array<string, mixed>, score: float}> $allScored
	 * @return array<int, array<string, mixed>>
	 */
	private function temporalSpread(array $selected, array $allScored, int $max): array {
		if (count($selected) < 2) {
			return $selected;
		}

		// Compute day buckets in current selection
		$selectedByDay = [];
		$selectedIds = [];
		foreach ($selected as $p) {
			$day = $this->dayKey($p);
			$selectedByDay[$day][] = $p;
			$selectedIds[(int)$p['file_id']] = true;
		}

		// Compute day buckets across all candidates
		$allByDay = [];
		foreach ($allScored as $entry) {
			$day = $this->dayKey($entry['photo']);
			$allByDay[$day][] = $entry;
		}

		$allDays = array_keys($allByDay);
		if (count($allDays) <= 1) {
			return $selected;
		}

		$totalDays = count($allDays);
		$targetDays = (int)max(1, floor($totalDays / max($max, 1)) * $max);
		// If selection already covers enough distinct days, done
		if (count($selectedByDay) >= min($totalDays, $max)) {
			return $selected;
		}

		// Find over-represented and under-represented days
		sort($allDays);
		foreach ($allDays as $day) {
			if (isset($selectedByDay[$day])) {
				continue;
			}
			// Find the highest-scoring photo from this day not yet selected
			$candidate = null;
			foreach ($allByDay[$day] as $entry) {
				$fid = (int)$entry['photo']['file_id'];
				if (!isset($selectedIds[$fid])) {
					$candidate = $entry['photo'];
					break;
				}
			}
			if ($candidate === null) {
				continue;
			}

			// Find an over-represented day to swap from (3+ in same day, take lowest-scoring)
			$swapDay = null;
			$swapCount = 0;
			foreach ($selectedByDay as $d => $list) {
				if (count($list) > $swapCount && count($list) >= 3) {
					$swapDay = $d;
					$swapCount = count($list);
				}
			}
			if ($swapDay === null) {
				continue;
			}

			// Remove the last (lowest-priority since selection was score-sorted earlier) from $swapDay
			$victim = array_pop($selectedByDay[$swapDay]);
			if ($victim !== null) {
				unset($selectedIds[(int)$victim['file_id']]);
			}
			$selectedByDay[$day] = [$candidate];
			$selectedIds[(int)$candidate['file_id']] = true;
		}

		// Flatten
		$out = [];
		foreach ($selectedByDay as $list) {
			foreach ($list as $p) {
				$out[] = $p;
			}
		}
		return $out;
	}

	/**
	 * @param array<string, mixed> $photo
	 */
	private function dayKey(array $photo): string {
		$ts = $this->resolveTimestamp($photo);
		return (new \DateTimeImmutable('@' . $ts))
			->setTimezone(new \DateTimeZone(date_default_timezone_get()))
			->format('Y-m-d');
	}

	/**
	 * @param array<string, mixed> $photo
	 */
	private function resolveTimestamp(array $photo): int {
		if (!empty($photo['taken_at'])) {
			try {
				return (new \DateTimeImmutable((string)$photo['taken_at']))->getTimestamp();
			} catch (\Throwable $e) {
				// fall through
			}
		}
		return (int)($photo['mtime'] ?? 0);
	}

	private function parseFlexibleDate(string $raw): ?\DateTimeImmutable {
		$raw = trim($raw);
		if ($raw === '') {
			return null;
		}
		$dt = \DateTimeImmutable::createFromFormat('Y:m:d H:i:s', $raw);
		if ($dt !== false) {
			return $dt;
		}
		try {
			return new \DateTimeImmutable($raw);
		} catch (\Throwable $e) {
			return null;
		}
	}

	/**
	 * @return array<int, string>
	 */
	private function splitMultiselect(string $raw): array {
		$raw = trim($raw);
		if ($raw === '') {
			return [];
		}
		$parts = explode(';#', $raw);
		$out = [];
		foreach ($parts as $part) {
			$p = trim($part);
			if ($p !== '') {
				$out[] = $p;
			}
		}
		return $out;
	}

	private function formatDateLong(\DateTimeImmutable $dt): string {
		$day = (int)$dt->format('j');
		$month = $this->localizedMonth((int)$dt->format('n'));
		$year = $dt->format('Y');
		return sprintf('%d %s %s', $day, $month, $year);
	}
}
