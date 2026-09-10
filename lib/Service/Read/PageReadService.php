<?php

declare(strict_types=1);

namespace OCA\IntraVox\Service\Read;

use OCA\IntraVox\Service\Cache\PageCacheService;
use OCA\IntraVox\Service\Locator\PageLocator;
use OCA\IntraVox\Service\Path\PageDataEnricher;
use OCA\IntraVox\Service\PermissionService;
use OCA\IntraVox\Service\Publication\MetaVoxGateway;
use OCA\IntraVox\Service\Sanitize\PageShapeSanitizer;
use OCA\IntraVox\Service\Util\PageIdUtils;
use Psr\Log\LoggerInterface;

/**
 * Reads a single page: resolves it (by uniqueId or legacy slug, across every
 * language folder — issue #90), reads and decodes it, backfills a missing
 * uniqueId, and returns the enriched+sanitized page. Owns the distributed
 * content cache and — critically — the #70 per-user recompute on a cache HIT
 * (permissions/canEdit/fileId/metaVoxAvailable/groupfolderId/translations are
 * NEVER served from the shared cache, they are recomputed fresh so one user's
 * canWrite can never leak to another) plus the matching strip before a write.
 *
 * Extracted verbatim from PageService::getPage() as the first step of dissolving
 * the god-class. The folder seams (getReadLanguageFolder, getIntraVoxFolder) come
 * from the injected FolderContext (the substrate); the two concerns that stay on
 * PageService (resolveTranslations, groupfolderIdForNode) go in as $this-bound
 * closures, so the 26 seam-subclasses keep intercepting through the closures
 * with zero test edits. PageDistributedHitRecomputeTest (#70), PageCrudReadTest
 * and PageServiceCrossLanguageTest (#90) pin the behaviour byte-for-byte.
 */
final class PageReadService {
    /**
     * @param \Closure(): MetaVoxGateway $metaVox lazily resolves the gateway
     * @param \Closure(): PageDataEnricher $enricher lazily resolves the enricher
     * @param \Closure(?string, ?string): array $resolveTranslations (group, uniqueId) -> ACL-filtered list
     *
     * Both are closures rather than built instances because building either
     * reads $userId (the enricher builds the MetaVox gateway too), and the
     * request-cache-hit path — the very first thing getPage() does — must return
     * without forcing that. Resolving them lazily keeps the hit path free of
     * $userId, matching the original inline getPage() timing exactly. For the
     * same reason FolderContext is only consulted AFTER the cache-hit return.
     */
    public function __construct(
        private PageCacheService $cache,
        private PageLocator $locator,
        private \Closure $metaVox,
        private \Closure $enricher,
        private PageShapeSanitizer $shape,
        private PermissionService $permissionService,
        private PageIdUtils $idUtils,
        private LoggerInterface $logger,
        private \OCA\IntraVox\Service\Folder\FolderContext $folders,
        private \Closure $resolveTranslations,
        private \OCA\IntraVox\Service\Util\GroupfolderResolver $groupfolders,
    ) {
    }

    public function getPage(string $id): array {
        // Check request-level cache first
        $cachedPage = $this->cache->getPageData($id);
        if ($cachedPage !== null) {
            return $cachedPage;
        }

        $folder = $this->folders->readLanguageFolder();
        $result = null;

        // The cross-language locate takes a lazy root (invoked per language
        // iteration inside the locator), so hand it a closure resolving the same
        // getIntraVoxFolder seam — byte-identical to the old $intraVoxFolder.
        $intraVoxRoot = fn(): \OCP\Files\Folder => $this->folders->intraVox();

        // Save original ID before sanitization
        $originalId = $id;

        // Check for uniqueId pattern BEFORE sanitization. The cross-language
        // scan inside locatePageAnyLanguage() lets feed links and shared links
        // resolve regardless of which language folder holds the page.
        if (strpos($originalId, 'page-') === 0) {
            $result = $this->locator->locatePageAnyLanguage($intraVoxRoot, $folder, $originalId);
            if (!$result) {
                $this->logger->warning('IntraVox: Not found by uniqueId', ['uniqueId' => $originalId]);
            }
        }

        // Only sanitize for legacy ID fallback
        if ($result === null) {
            $id = $this->idUtils->sanitizeId($originalId);
            $result = $this->locator->findPageById($folder, $id);
            // Slug links get the same cross-language treatment as uniqueId
            // links, so which kind of link a reader follows never decides
            // whether the page resolves.
            if ($result === null) {
                $result = $this->locator->locatePageBySlugAnyLanguage($intraVoxRoot, $folder, $id);
            }
        }

        if ($result === null) {
            throw new \Exception('Page not found');
        }

        $content = $result['file']->getContent();
        $data = json_decode($content, true);

        if (!$data) {
            throw new \Exception('Invalid page data');
        }

        // Ensure uniqueId exists for legacy pages
        if (!isset($data['uniqueId'])) {
            $data['uniqueId'] = 'page-' . $this->idUtils->generateUUID();
            // Save the page with the new uniqueId
            try {
                $result['file']->putContent(json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            } catch (\Exception $e) {
                // Failed to save uniqueId - page will work but won't have permanent link
            }
        }

        // Cache folder location using both uniqueId and pageId for fast image access
        $pageFolder = $result['folder'];
        $uniqueId = $data['uniqueId'];
        $this->cache->setPageFolder($uniqueId, $pageFolder);
        $this->cache->setPageFolder($originalId, $pageFolder);
        // $id is the method parameter (possibly reassigned to the sanitized id):
        // always set, so the original `if (isset($id))` guard was always true.
        $this->cache->setPageFolder($id, $pageFolder);

        // Distributed content cache. Key is content-addressable via mtime, so
        // invalidation is automatic — a write bumps mtime, the next read
        // misses cache and rebuilds. The sanitize+enrich pipeline is the
        // expensive part (~500 lines of widget processing); cache stores
        // the post-sanitize result keyed by `{uniqueId}_{mtime}`.
        $mtime = $result['file']->getMTime();
        $contentCacheKey = 'content_' . $uniqueId . '_' . $mtime;
        if ($this->cache->isDistributedAvailable()) {
            $cached = $this->cache->getDistributed($contentCacheKey);
            if (is_string($cached)) {
                $decoded = json_decode($cached, true);
                if (is_array($decoded)) {
                    // Permissions are per-user and are NOT stored in the shared
                    // distributed cache (see the set() below). Recompute them
                    // fresh on every hit so one user's canWrite can never leak to
                    // another (issue #70). $result['file']/['folder'] are already
                    // resolved above. This also overwrites any stale permissions
                    // baked in by pre-fix cache entries, so no flush is needed.
                    $decoded['permissions'] = $this->permissionService->permissionsForPage($result['folder'], $result['file']);
                    $decoded['canEdit'] = $result['file']->isUpdateable();
                    // fileId is user-independent but may be absent from older cache
                    // entries; ensure it's present so the publication gate works.
                    if (!isset($decoded['fileId']) && $result['file'] instanceof \OCP\Files\File) {
                        $decoded['fileId'] = $result['file']->getId();
                    }
                    // MetaVox availability is an install-wide fact and the
                    // groupfolder id is a property of the file's mount, so
                    // neither is cached — availability can change under a cache
                    // entry when the app is enabled or disabled, and entries
                    // written before these fields existed would otherwise never
                    // gain them. Both are cheap: an in-memory app-manager lookup
                    // and a regex over a path.
                    $decoded['metaVoxAvailable'] = ($this->metaVox)()->isMetaVoxAvailable();
                    if ($decoded['metaVoxAvailable'] && $result['file'] instanceof \OCP\Files\File) {
                        $decoded['groupfolderId'] = $this->groupfolders->forNode($result['file']);
                    }
                    // Translations are ACL-filtered per user (resolveTranslations
                    // skips group members the caller's mount does not grant), so
                    // one user's list must never be served to another. Stripped
                    // from the shared cache on write — recomputed here on every
                    // hit: one indexed query plus a filecache lookup per group
                    // member.
                    $decoded['translations'] = ($this->resolveTranslations)(
                        $decoded['translationGroup'] ?? null,
                        $decoded['uniqueId'] ?? null
                    );
                    $this->cache->setPageData($originalId, $decoded);
                    $this->cache->setPageData($uniqueId, $decoded);
                    return $decoded;
                }
            }
        }

        // Enrich with real-time path data. Pass the page file so canWrite/canEdit
        // are gated on the file the write path actually targets (issue #70).
        $data = ($this->enricher)()->enrich($data, $result['folder'], $result['file']);

        $sanitizedData = $this->shape->sanitizePage($data);

        // Cache the result for this request
        $this->cache->setPageData($originalId, $sanitizedData);
        if (isset($data['uniqueId'])) {
            $this->cache->setPageData($data['uniqueId'], $sanitizedData);
        }

        // Cache for cross-request reuse (1 hour TTL; older entries are
        // naturally orphaned when mtime changes, distributed-cache GC will
        // clean them up). The distributed cache is shared across users, so the
        // per-user permissions/canEdit are stripped before storing and are
        // recomputed on every read (issue #70). The user-independent enriched
        // fields (path/depth/parent/language/department) stay cached.
        if ($this->cache->isDistributedAvailable()) {
            $cacheable = $sanitizedData;
            // metaVoxAvailable is stripped for the same reason as permissions:
            // enabling or disabling the app must take effect immediately rather
            // than waiting out an hour-long cache entry. It is recomputed on
            // every read above.
            // translations joins the per-user list: it is ACL-filtered through
            // the caller's mount, so caching it would leak one user's view to
            // another. Recomputed on every cache hit above.
            unset($cacheable['permissions'], $cacheable['canEdit'], $cacheable['metaVoxAvailable'], $cacheable['translations']);
            $this->cache->setDistributed($contentCacheKey, json_encode($cacheable), PageCacheService::PAGE_CONTENT_TTL);
        }

        return $sanitizedData;
    }
}
