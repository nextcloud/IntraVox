<?php
declare(strict_types=1);

namespace OCA\IntraVox\Controller;

use OCA\IntraVox\AppInfo\Application;
use OCA\IntraVox\Service\PhotoStoryService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\Attribute\NoCSRFRequired;
use OCP\AppFramework\Http\DataResponse;
use OCP\IConfig;
use OCP\IL10N;
use OCP\IRequest;
use OCP\IUserSession;
use Psr\Log\LoggerInterface;

/**
 * FileStoryWidget controller — thin wrapper around PhotoStoryService that
 * lists DOCUMENTS rather than photos/videos. Shares all the heavy lifting
 * (SQL-paged enumeration, MetaVox filter+sort, ACL scoping) with the photo
 * widget; only the mimetype set differs.
 *
 * Routes:
 *   GET /api/file-story/files              — paged file list (timeline/list/grouped)
 *   GET /api/file-story/capabilities       — folder capabilities
 *   GET /api/file-story/metavox-fields     — same as photo-story but kept for endpoint-symmetry
 */
class FileStoryController extends Controller {
	public function __construct(
		string $appName,
		IRequest $request,
		private PhotoStoryService $service,
		private IConfig $config,
		private IUserSession $userSession,
		private IL10N $l10n,
		private LoggerInterface $logger,
	) {
		parent::__construct($appName, $request);
	}

	/**
	 * GET /api/file-story/files
	 *
	 * Mirrors PhotoStoryController::photos() but pins $mimeCategory to
	 * 'documents'. Returns a paged list of file rows enriched with NC core
	 * + MetaVox metadata.
	 */
	#[NoAdminRequired]
	#[NoCSRFRequired]
	public function files(): DataResponse {
		$folder = (string)$this->request->getParam('folder', '');
		$mode = (string)$this->request->getParam('mode', 'timeline');

		$offsetRaw = $this->request->getParam('offset', null);
		$offset = ($offsetRaw !== null && $offsetRaw !== '') ? max(0, (int)$offsetRaw) : 0;
		$pageSizeRaw = $this->request->getParam('pageSize', null);
		$pageSize = ($pageSizeRaw !== null && $pageSizeRaw !== '') ? max(1, min(500, (int)$pageSizeRaw)) : 100;
		// Configured cap from widget config ("Maximum documents"). Clamps total
		// results across paginated fetches — once offset+returned >= limit the
		// frontend infinite-scroll stops.
		$limitRaw = $this->request->getParam('limit', null);
		$limit = ($limitRaw !== null && $limitRaw !== '') ? max(1, (int)$limitRaw) : null;
		if ($limit !== null) {
			$remaining = $limit - $offset;
			if ($remaining <= 0) {
				return new DataResponse([
					'files' => [],
					'capabilities' => $this->service->getCapabilities(),
					'mode' => $mode,
					'pagination' => [
						'offset' => $offset, 'pageSize' => $pageSize,
						'total' => $limit, 'hasMore' => false,
						'sortOrder' => 'desc', 'truncated' => false,
					],
				]);
			}
			$pageSize = min($pageSize, $remaining);
		}
		$sortOrderRaw = (string)$this->request->getParam('sortOrder', 'desc');
		$sortOrder = ($sortOrderRaw === 'asc') ? 'asc' : 'desc';
		$sortByRaw = trim((string)$this->request->getParam('sortBy', 'mtime'));
		$sortBy = preg_match('/^[a-z][a-z0-9_]{0,63}$/i', $sortByRaw) ? $sortByRaw : 'mtime';
		$totalHintRaw = $this->request->getParam('total', null);
		$totalHint = ($totalHintRaw !== null && $totalHintRaw !== '' && $offset > 0) ? max(0, (int)$totalHintRaw) : null;
		// Granularity controls timeline bucket size — day for photos, month or
		// year for documents where per-day grouping is too fine-grained.
		$granularityRaw = (string)$this->request->getParam('granularity', 'day');
		$granularity = in_array($granularityRaw, ['day', 'month', 'year'], true) ? $granularityRaw : 'day';

		$filtersRaw = $this->request->getParam('filters', null);
		$filters = [];
		if ($filtersRaw !== null && $filtersRaw !== '') {
			if (is_string($filtersRaw) && strlen($filtersRaw) > 16384) {
				return new DataResponse(['error' => 'Filter payload too large'], Http::STATUS_BAD_REQUEST);
			}
			$decoded = is_string($filtersRaw) ? json_decode($filtersRaw, true) : $filtersRaw;
			if (is_array($decoded)) {
				$filters = $this->sanitizeFilters($decoded);
			}
		}

		$allowedModes = ['timeline', 'tiles', 'list', 'grouped'];
		if (!in_array($mode, $allowedModes, true)) {
			return new DataResponse(['error' => 'Invalid mode'], Http::STATUS_BAD_REQUEST);
		}
		if ($folder === '') {
			return new DataResponse(['error' => 'Folder is required'], Http::STATUS_BAD_REQUEST);
		}

		// Optional: which MetaVox field to group by in 'grouped' mode.
		$groupByRaw = trim((string)$this->request->getParam('groupBy', ''));
		$groupBy = ($mode === 'grouped' && preg_match('/^[a-z][a-z0-9_]{0,63}$/i', $groupByRaw)) ? $groupByRaw : '';

		// Federated source: MetaVox is unavailable cross-instance, so any
		// MetaVox filter the widget-config still carries from before would
		// silently filter out every file. Strip them. Same for group-by /
		// sort-by on a MetaVox field — fall back to mtime.
		$sourceIsFederated = $this->service->describeFederatedSource($folder) !== null;
		if ($sourceIsFederated) {
			$filters = [];
			if ($groupBy !== '' && $groupBy !== 'category') {
				$groupBy = 'category';
			}
			if (!in_array($sortBy, ['mtime', 'name', 'size'], true)) {
				$sortBy = 'mtime';
			}
		}

		try {
			$paged = $this->service->listPhotosPaged(
				$folder, $filters, $offset, $pageSize, $sortOrder, $totalHint, $sortBy, 'documents'
			);
			$files = $paged['photos'];
			$capabilities = $this->service->getCapabilities();

			// Apply the configured "Maximum documents" cap on the response.
			// Truncate the returned row-set and tell the frontend to stop
			// infinite-scrolling once we've delivered $limit rows.
			$effectiveTotal = (int)$paged['total'];
			$effectiveHasMore = (bool)$paged['hasMore'];
			if ($limit !== null) {
				if (count($files) > ($limit - $offset)) {
					$files = array_slice($files, 0, max(0, $limit - $offset));
				}
				if (($offset + count($files)) >= $limit) {
					$effectiveHasMore = false;
				}
				$effectiveTotal = min($effectiveTotal, $limit);
			}

			$payload = [
				'files' => $files,
				'capabilities' => $capabilities,
				'mode' => $mode,
				'pagination' => [
					'offset' => $paged['offset'],
					'pageSize' => $paged['pageSize'],
					'total' => $effectiveTotal,
					'hasMore' => $effectiveHasMore,
					'sortOrder' => $paged['sortOrder'],
					'truncated' => $paged['truncated'],
				],
			];

			if ($mode === 'timeline') {
				// For documents, mtime is the more meaningful default (most don't
				// carry a 'taken_at'). Use it unless sortBy explicitly picks taken_at.
				$dateField = ($sortBy === 'taken_at') ? 'taken_at' : 'mtime';
				$payload['timeline'] = $this->service->groupByTimeline($files, $sortOrder, $granularity, $dateField);
				$payload['granularity'] = $granularity;
			} elseif ($mode === 'grouped' && $groupBy !== '') {
				$payload['groups'] = $this->groupFilesByField($files, $groupBy);
			}

			$maxMtime = 0;
			foreach ($files as $f) {
				if (isset($f['mtime']) && $f['mtime'] > $maxMtime) {
					$maxMtime = (int)$f['mtime'];
				}
			}
			$user = $this->userSession->getUser();
			$etagSource = json_encode([
				'u' => $user !== null ? $user->getUID() : '',
				'w' => 'file-story',
				'f' => $folder, 'm' => $mode, 'gb' => $groupBy, 'gr' => $granularity,
				'o' => $offset, 'ps' => $pageSize, 's' => $sortOrder, 'sb' => $sortBy,
				'lim' => $limit,
				'flt' => $filters, 'n' => count($files), 't' => $maxMtime,
			], JSON_UNESCAPED_SLASHES);
			$etag = '"' . md5((string)$etagSource) . '"';

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
		} catch (\OCP\Files\NotFoundException $e) {
			return new DataResponse(
				[
					'error' => 'Folder not found',
					'reason' => 'folder_not_accessible',
					'files' => [],
					'capabilities' => null,
				],
				Http::STATUS_NOT_FOUND
			);
		} catch (\Throwable $e) {
			$this->logger->error('FileStoryController: files failed', [
				'folder' => $folder, 'mode' => $mode, 'error' => $e->getMessage(),
			]);
			return new DataResponse(['error' => 'Unable to load files'], Http::STATUS_INTERNAL_SERVER_ERROR);
		}
	}

	/**
	 * GET /api/file-story/capabilities
	 * Identical to PhotoStory but exposes MetaVox-fields-availability so the
	 * FileStoryWidget editor can decide whether to show the filter/sort UI.
	 */
	#[NoAdminRequired]
	#[NoCSRFRequired]
	public function capabilities(): DataResponse {
		try {
			$folder = trim((string)$this->request->getParam('folder', ''));
			$sourceFederated = false;
			$sourceFederatedInfo = null;
			if ($folder !== '') {
				$sourceFederatedInfo = $this->service->describeFederatedSource($folder);
				$sourceFederated = $sourceFederatedInfo !== null;
			}
			return new DataResponse([
				'capabilities' => $this->service->getCapabilities(),
				'metaVoxAvailable' => $this->service->isMetaVoxAvailable(),
				'sourceIsFederated' => $sourceFederated,
				'sourceFederatedInfo' => $sourceFederatedInfo,
			]);
		} catch (\Throwable $e) {
			$this->logger->error('FileStoryController: capabilities failed', ['error' => $e->getMessage()]);
			return new DataResponse(['error' => 'Unable to load capabilities'], Http::STATUS_INTERNAL_SERVER_ERROR);
		}
	}

	/**
	 * GET /api/file-story/metavox-fields
	 * Returns the same field-list the photo widget gets; FileStory uses it to
	 * populate filter-builder and group-by/sort-by dropdowns.
	 */
	#[NoAdminRequired]
	#[NoCSRFRequired]
	public function metaVoxFields(): DataResponse {
		try {
			$fields = $this->service->getMetaVoxFieldsForPhotoStory();
			return new DataResponse(['fields' => $fields, 'available' => $this->service->isMetaVoxAvailable()]);
		} catch (\Throwable $e) {
			$this->logger->error('FileStoryController: metaVoxFields failed', ['error' => $e->getMessage()]);
			return new DataResponse(['fields' => [], 'available' => false]);
		}
	}

	/**
	 * Group a list of file rows by the chosen MetaVox field value (or the
	 * synthetic 'category' = mime-major-type).
	 *
	 * @return array<int, array{key: string, label: string, count: int, files: array<int, array<string, mixed>>}>
	 */
	private function groupFilesByField(array $files, string $groupBy): array {
		$groups = [];
		foreach ($files as $f) {
			$key = $this->extractGroupKey($f, $groupBy);
			if (!isset($groups[$key])) {
				$groups[$key] = ['key' => $key, 'label' => $key, 'count' => 0, 'files' => []];
			}
			$groups[$key]['files'][] = $f;
			$groups[$key]['count']++;
		}
		// Stable order: by count descending, then label ascending.
		uasort($groups, function ($a, $b) {
			$c = $b['count'] <=> $a['count'];
			return $c !== 0 ? $c : strcasecmp($a['label'], $b['label']);
		});
		return array_values($groups);
	}

	private function extractGroupKey(array $file, string $groupBy): string {
		// Special pseudo-fields
		if ($groupBy === 'category') {
			$mime = (string)($file['mime'] ?? '');
			if (str_contains($mime, 'pdf')) return $this->l10n->t('PDF');
			if (str_contains($mime, 'word') || str_contains($mime, 'opendocument.text')) return $this->l10n->t('Text documents');
			if (str_contains($mime, 'sheet') || str_contains($mime, 'excel')) return $this->l10n->t('Spreadsheets');
			if (str_contains($mime, 'presentation') || str_contains($mime, 'powerpoint')) return $this->l10n->t('Presentations');
			if (str_contains($mime, 'markdown') || $mime === 'text/plain') return $this->l10n->t('Text');
			return $this->l10n->t('Other');
		}
		// Well-known photo fields stored at the row level
		if ($groupBy === 'country' && !empty($file['country'])) return (string)$file['country'];
		if ($groupBy === 'location' && !empty($file['location'])) return (string)$file['location'];
		// Generic MetaVox field via the row's meta dict
		if (isset($file['meta'][$groupBy]) && $file['meta'][$groupBy] !== '' && $file['meta'][$groupBy] !== null) {
			return (string)$file['meta'][$groupBy];
		}
		return $this->l10n->t('(no value)');
	}

	/**
	 * Sanitize the filter payload — mirror PhotoStoryController's logic.
	 * Whitelist operators, drop malformed rows.
	 */
	private function sanitizeFilters(array $raw): array {
		$allowedOps = ['equals', 'contains', 'in', 'year_equals'];
		$clean = [];
		$maxFilters = 32;
		$maxValueLen = 200;
		foreach ($raw as $entry) {
			if (count($clean) >= $maxFilters) break;
			if (!is_array($entry)) continue;
			$field = (string)($entry['field'] ?? '');
			$op = (string)($entry['op'] ?? '');
			$val = $entry['value'] ?? '';
			if ($field === '' || !preg_match('/^[a-z][a-z0-9_]{0,63}$/i', $field)) continue;
			if (!in_array($op, $allowedOps, true)) continue;
			// Coerce + length-cap value. Arrays only for op='in'.
			if (is_array($val)) {
				$coerced = [];
				foreach ($val as $v) {
					$s = is_scalar($v) ? trim((string)$v) : '';
					if ($s !== '') $coerced[] = mb_substr($s, 0, $maxValueLen);
					if (count($coerced) >= 64) break;
				}
				if (empty($coerced)) continue;
				$val = array_values($coerced);
			} else {
				$s = is_scalar($val) ? trim((string)$val) : '';
				if ($s === '') continue;
				$val = mb_substr($s, 0, $maxValueLen);
			}
			$clean[] = ['field' => $field, 'op' => $op, 'value' => $val];
		}
		return $clean;
	}
}
