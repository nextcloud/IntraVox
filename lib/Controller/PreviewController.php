<?php
declare(strict_types=1);

namespace OCA\IntraVox\Controller;

use OCA\IntraVox\AppInfo\Application;
use OCA\IntraVox\Service\FederatedPreviewService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\Attribute\NoCSRFRequired;
use OCP\AppFramework\Http\Attribute\UserRateThrottle;
use OCP\AppFramework\Http\DataDisplayResponse;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\Http\RedirectResponse;
use OCP\Files\IRootFolder;
use OCP\Files\Node;
use OCP\Http\Client\IClientService;
use OCP\IDBConnection;
use OCP\Files\IMimeTypeDetector;
use OCP\IRequest;
use OCP\IURLGenerator;
use OCP\IUserSession;
use Psr\Log\LoggerInterface;

/**
 * Shared preview-proxy controller used by both PhotoStory and FileStory.
 *
 * NC core `/core/preview` can't generate thumbnails for files living on a
 * federated mount: the preview providers (Image, Office, PDF) need a local
 * path or a Collabora/LibreOffice-rendered conversion that the remote NC
 * already has cached but ours doesn't. Result: 404, broken tile in the
 * widget.
 *
 * This endpoint closes the gap WITHOUT pulling the federated file body to
 * our side. For local files it 302-redirects to NC's own `/core/preview`
 * (so we don't add latency or duplicate NC's preview cache). For federated
 * files it delegates to FederatedPreviewService, which handles the cache,
 * in-flight deduplication and per-remote concurrency cap.
 *
 * Scaling characteristics for federated content:
 *  - Cache hit (`{fileId}-{etag}-{x}-{y}`): ~1 ms, never leaves our server.
 *  - In-flight dedup: if 50 users hit the same uncached preview together,
 *    1 outbound call is performed; the other 49 wait for the cache.
 *  - Per-remote concurrency cap (8 by default): a stampede against one
 *    remote NC is capped so the remote's IP-throttle isn't tripped.
 */
class PreviewController extends Controller {

	public function __construct(
		string $appName,
		IRequest $request,
		private IRootFolder $rootFolder,
		private IUserSession $userSession,
		private FederatedPreviewService $federatedPreview,
		private IDBConnection $db,
		private IURLGenerator $urlGenerator,
		private IMimeTypeDetector $mimeTypeDetector,
		private LoggerInterface $logger,
	) {
		parent::__construct($appName, $request);
	}

	/**
	 * GET /api/preview?file_id=<int>&x=<int>&y=<int>
	 *
	 * Routes to one of three paths depending on the file:
	 *   - file unreachable for this user → mime-icon redirect (302)
	 *   - file is local → 302 redirect to /core/preview (no proxying)
	 *   - file is federated → FederatedPreviewService (cache → dedup → cap → fetch)
	 */
	#[NoAdminRequired]
	#[NoCSRFRequired]
	// Per-user throttle. A tile-grid with 200 federated files generates ~200
	// calls in one view; legitimate browsing of 3 pages back-to-back stays
	// comfortably under 600/min. Sustained 10 req/s is well below what NC's
	// other preview-style endpoints allow, but enough to throttle an account
	// trying to hammer a remote NC via our proxy.
	#[UserRateThrottle(limit: 600, period: 60)]
	public function fetch(): \OCP\AppFramework\Http\Response {
		$fileIdRaw = $this->request->getParam('file_id', null);
		$xRaw = $this->request->getParam('x', '400');
		$yRaw = $this->request->getParam('y', '400');

		if ($fileIdRaw === null || $fileIdRaw === '') {
			return new DataResponse(['error' => 'file_id required'], Http::STATUS_BAD_REQUEST);
		}
		$fileId = (int)$fileIdRaw;
		$x = max(32, min(2048, (int)$xRaw));
		$y = max(32, min(2048, (int)$yRaw));

		$user = $this->userSession->getUser();
		if ($user === null) {
			return new DataResponse(['error' => 'auth required'], Http::STATUS_UNAUTHORIZED);
		}

		try {
			$userFolder = $this->rootFolder->getUserFolder($user->getUID());
			$node = $userFolder->getFirstNodeById($fileId);
		} catch (\Throwable $e) {
			$this->logger->debug('PreviewController: node lookup failed', ['fid' => $fileId, 'err' => $e->getMessage()]);
			return $this->mimeIconRedirect(null);
		}

		if ($node === null) {
			return $this->mimeIconRedirect(null);
		}

		$federatedInfo = $this->resolveFederatedShare($node);

		// Local file: bounce to NC core preview. Keeps NC's own cache hot,
		// avoids the proxy overhead for the 99% case.
		if ($federatedInfo === null) {
			$coreUrl = $this->urlGenerator->linkToRoute('core.Preview.getPreviewByFileId', []) .
				'?fileId=' . $fileId . '&x=' . $x . '&y=' . $y . '&a=true';
			return new RedirectResponse($coreUrl);
		}

		$bytes = $this->federatedPreview->getPreview($fileId, (string)$node->getEtag(), $x, $y, $federatedInfo);
		if ($bytes === null) {
			return $this->mimeIconRedirect($node);
		}
		return $this->imageResponse($bytes, 'image/png');
	}

	/**
	 * Resolve which federated share a node belongs to, or null when it's
	 * local. We look up the share by storage-id rather than walking the
	 * storage-wrapper chain because production wrappers (Trashbin,
	 * Availability) frequently hide the External\Storage class from a
	 * naive get_class() walk.
	 *
	 * Storage IDs for federated shares are `shared::md5(token + '@' +
	 * remote_url_without_trailing_slash)`. We compute the expected id for
	 * every accepted incoming share the user has and find the match.
	 *
	 * @return array{remote: string, token: string, remotePath: string}|null
	 */
	private function resolveFederatedShare(Node $node): ?array {
		try {
			$storage = $node->getStorage();
			$storageId = (string)$storage->getId();

			if (!preg_match('/^shared::[a-f0-9]{32}$/', $storageId)) {
				return null;
			}

			$user = $this->userSession->getUser();
			if ($user === null) {
				return null;
			}

			$qb = $this->db->getQueryBuilder();
			$qb->select('remote', 'share_token', 'mountpoint')
				->from('share_external')
				->where($qb->expr()->eq('user', $qb->createNamedParameter($user->getUID())))
				->andWhere($qb->expr()->eq('accepted', $qb->createNamedParameter(1, \OCP\DB\QueryBuilder\IQueryBuilder::PARAM_INT)));
			$rows = $qb->executeQuery()->fetchAll();

			$matched = null;
			foreach ($rows as $row) {
				// External\Storage::getId() strips the trailing slash via
				// CloudId; oc_share_external typically stores it WITH slash.
				$normalisedRemote = rtrim((string)$row['remote'], '/');
				$expected = 'shared::' . md5(((string)$row['share_token']) . '@' . $normalisedRemote);
				if ($expected === $storageId) {
					$matched = $row;
					break;
				}
			}
			if ($matched === null) {
				return null;
			}

			$mountPath = '/' . trim((string)$matched['mountpoint'], '/');
			$fullVirtualPath = $node->getPath();
			$marker = '/files' . $mountPath;
			$idx = strpos($fullVirtualPath, $marker);
			if ($idx === false) {
				return null;
			}
			$remotePath = '/' . ltrim(substr($fullVirtualPath, $idx + strlen($marker)), '/');

			return [
				'remote' => rtrim((string)$matched['remote'], '/'),
				'token' => (string)$matched['share_token'],
				'remotePath' => $remotePath,
			];
		} catch (\Throwable $e) {
			$this->logger->debug('PreviewController: resolveFederatedShare failed', ['err' => $e->getMessage()]);
			return null;
		}
	}

	/**
	 * POST /api/preview/warmup with body `{"file_ids":[..]}`.
	 *
	 * Pre-warm the federated-preview cache for a list of fileIds. Designed to
	 * be called once per tile-grid render: the frontend collects every
	 * `is_federated: true` fileId from a paged response and asks us to warm
	 * them. Returns immediately after queuing — we cap how much real work
	 * happens synchronously so a 200-tile grid doesn't keep the page-load
	 * blocked. The endpoint is best-effort: a fileId that fails (no
	 * permission, remote down, cap hit) silently skips. The next per-tile
	 * GET /api/preview will produce the icon-fallback.
	 *
	 * Throttled hard because each call may spawn outbound HTTPS work. The
	 * 600/min cap of `fetch()` is per-request, so a single warmup call can
	 * still trigger up to its own MAX_BATCH outbound calls before returning.
	 */
	#[NoAdminRequired]
	#[NoCSRFRequired]
	#[UserRateThrottle(limit: 60, period: 60)]
	public function warmup(): DataResponse {
		$raw = $this->request->getParam('file_ids', null);
		if (is_string($raw)) {
			$decoded = json_decode($raw, true);
			$ids = is_array($decoded) ? $decoded : [];
		} elseif (is_array($raw)) {
			$ids = $raw;
		} else {
			$ids = [];
		}
		if (empty($ids)) {
			return new DataResponse(['warmed' => 0, 'queued' => 0]);
		}

		$user = $this->userSession->getUser();
		if ($user === null) {
			return new DataResponse(['error' => 'auth required'], Http::STATUS_UNAUTHORIZED);
		}

		// Cap synchronous work so a 1000-id warmup doesn't block the response
		// for half a minute. Anything beyond the cap will warm naturally when
		// the user scrolls to that tile (cold-fetch path).
		$maxBatch = 16;
		$ids = array_slice(array_values(array_unique(array_map('intval', $ids))), 0, $maxBatch);

		$userFolder = $this->rootFolder->getUserFolder($user->getUID());
		$warmed = 0;
		$skipped = 0;

		foreach ($ids as $fileId) {
			if ($fileId <= 0) {
				$skipped++;
				continue;
			}
			try {
				$node = $userFolder->getFirstNodeById($fileId);
				if ($node === null) {
					$skipped++;
					continue;
				}
				$fed = $this->resolveFederatedShare($node);
				if ($fed === null) {
					// Local file — already covered by NC's own preview cache.
					$skipped++;
					continue;
				}
				$bytes = $this->federatedPreview->getPreview(
					$fileId, (string)$node->getEtag(), 400, 400, $fed
				);
				if ($bytes !== null) {
					$warmed++;
				} else {
					$skipped++;
				}
			} catch (\Throwable $e) {
				$this->logger->debug('PreviewController: warmup item failed', [
					'fid' => $fileId,
					'err' => $e->getMessage(),
				]);
				$skipped++;
			}
		}

		return new DataResponse(['warmed' => $warmed, 'skipped' => $skipped]);
	}

	private function imageResponse(string $bytes, string $contentType): DataDisplayResponse {
		// 24h browser cache. The URL embeds the file-id and the response is
		// immutable for that key — the etag changes when the file changes.
		return new DataDisplayResponse($bytes, Http::STATUS_OK, [
			'Content-Type' => $contentType,
			'Cache-Control' => 'private, max-age=86400',
		]);
	}

	/**
	 * Fall back to NC's mime-icon URL when no preview can be produced.
	 * The browser sees a 302 and ends up with a recognisable file-type SVG
	 * instead of a broken-image placeholder.
	 *
	 * IMPORTANT: when `$node` is null (file not visible to this user), we
	 * always emit the generic icon — never the icon that would be specific
	 * to that file's mime-type. Doing otherwise would leak existence of
	 * arbitrary file-ids to authenticated attackers.
	 */
	private function mimeIconRedirect(?Node $node): RedirectResponse {
		$mime = $node !== null ? (string)$node->getMimeType() : 'application/octet-stream';
		try {
			$url = $this->mimeTypeDetector->mimeTypeIcon($mime);
		} catch (\Throwable $e) {
			$url = $this->urlGenerator->imagePath('core', 'filetypes/file.svg');
		}
		return new RedirectResponse($url);
	}
}
