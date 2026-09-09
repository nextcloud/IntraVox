<?php

declare(strict_types=1);

namespace OCA\IntraVox\Service\News;

use OCA\IntraVox\Service\Cache\PageCacheService;
use OCA\IntraVox\Service\Folder\FolderContext;
use OCA\IntraVox\Service\GroupContextService;
use OCA\IntraVox\Service\Publication\MetaVoxGateway;
use OCA\IntraVox\Service\Publication\PublicationStateService;
use OCP\Files\NotFoundException;
use Psr\Log\LoggerInterface;

/**
 * The NEWS-widget orchestration carved out of the PageService god-class: the
 * dashboard news endpoint that collects pages under a source folder, filters
 * them (MetaVox + publication status), sorts/limits, and serves the result
 * behind a group-scoped distributed cache.
 *
 * The collect/sort/filter/format MECHANICS live in the NewsPageService engine
 * (ctor-injected); this service is the orchestrator over it plus the version-
 * counter cache glue (a news-only concern: the key format and NEWS_TTL are used
 * by no other domain). Page resolution stays on PageService — the page-lookup
 * helper is shared far beyond news — and arrives as a bound closure.
 *
 * NOTE on the version counter: the `news_version_{lang}` key is READ-ONLY —
 * nothing writes or increments it, so the version stays 0. Invalidation works
 * by a blanket namespace flush (PageService's cache-clear ->
 * PermissionService::clearDistributedCache), not by the counter bump the naming
 * suggests. This is pre-existing behaviour, reproduced here verbatim.
 */
final class NewsWidgetService {

    /**
     * @param \Closure(\OCP\Files\Folder, string): ?array $locatePage resolve a
     *        page by uniqueId within a folder.
     */
    public function __construct(
        private NewsPageService $news,
        private PageCacheService $cache,
        private GroupContextService $groupContext,
        private MetaVoxGateway $metaVox,
        private PublicationStateService $publicationState,
        private FolderContext $folders,
        private LoggerInterface $logger,
        private \Closure $locatePage,
    ) {
    }

    public function getNewsPages(
        string $sourcePath = '',
        array $filters = [],
        string $filterOperator = 'AND',
        int $limit = 5,
        string $sortBy = 'modified',
        string $sortOrder = 'desc',
        ?string $sourcePageId = null,
        bool $filterPublished = false
    ): array {
        $folder = $this->folders->readLanguageFolder();
        $pages = [];
        // Match the served language (recommended-language fallback, #75) so
        // the news cache key and date localisation agree with the folder.
        $language = $this->folders->effectiveLanguage() ?? $this->folders->userLanguage();

        // Version-counter cache: the news widget result depends on all pages in
        // the source folder plus user-supplied filters/sort/limit, plus the
        // user's group context (permissions). We don't want to rebuild on every
        // dashboard render, but invalidation must be instant on any page write.
        //
        // Strategy: a per-language counter that PageService's cache-clear bumps
        // on every mutation. Cache entries embed the current counter value;
        // after a bump, every old entry is unreachable (no reader looks under
        // the stale counter), so they age out via TTL without ever serving
        // stale data. Plan B4 from the roadmap.
        $newsVersionKey = 'news_version_' . $language;
        $newsVersion = 0;
        $newsCacheKey = null;
        if ($this->cache->isDistributedAvailable()) {
            $newsVersion = (int) ($this->cache->getDistributed($newsVersionKey) ?? 0);
            $paramHash = md5(json_encode([
                $sourcePath, $filters, $filterOperator, $limit, $sortBy,
                $sortOrder, $sourcePageId, $filterPublished,
            ]));
            $newsCacheKey = 'news_' . $language . '_' . $this->groupContext->getGroupHash()
                . '_v' . $newsVersion . '_' . $paramHash;
            $cached = $this->cache->getDistributed($newsCacheKey);
            if (is_string($cached)) {
                $decoded = json_decode($cached, true);
                if (is_array($decoded)) {
                    return $decoded;
                }
            }
        }

        // If sourcePageId is provided, find that page and use its folder as source
        // Also include the selected page itself in the results
        $sourcePageData = null;
        if (!empty($sourcePageId)) {
            try {
                $result = ($this->locatePage)($folder, $sourcePageId);
                if ($result && isset($result['folder'])) {
                    $folder = $result['folder'];
                    // Store the source page data to include it in results
                    if (isset($result['file'])) {
                        $sourcePageData = $result;
                    }
                } else {
                    $this->logger->warning('News widget: Source page not found', ['sourcePageId' => $sourcePageId]);
                    return ['items' => [], 'total' => 0, 'metavoxAvailable' => $this->metaVox->isMetaVoxAvailable()];
                }
            } catch (\Exception $e) {
                $this->logger->warning('News widget: Error finding source page', ['sourcePageId' => $sourcePageId, 'error' => $e->getMessage()]);
                return ['items' => [], 'total' => 0, 'metavoxAvailable' => $this->metaVox->isMetaVoxAvailable()];
            }
        }
        // Legacy: If sourcePath is provided (but no sourcePageId), navigate to that folder
        elseif (!empty($sourcePath)) {
            $sourcePath = trim($sourcePath, '/');
            try {
                $folder = $folder->get($sourcePath);
            } catch (NotFoundException $e) {
                $this->logger->warning('News widget: Source folder not found', ['path' => $sourcePath]);
                return ['items' => [], 'total' => 0, 'metavoxAvailable' => $this->metaVox->isMetaVoxAvailable()];
            }
        }

        // Recursively collect pages from the source folder.
        // Pass a hard cap to allow early-exit and prevent unbounded filesystem scans.
        $collectLimit = max($limit * 4, 200); // collect enough for filtering/sorting, cap at 200 minimum
        $this->news->findNewsPagesInFolder($this->folders->intraVox(), $folder, $pages, $language, $collectLimit);

        // Add the selected source page itself to the results (if sourcePageId
        // was provided). $sourcePageData is only ever set when its 'file' key was
        // present, so the null check alone is sufficient (the redundant
        // isset($sourcePageData['file']) the pre-carve code carried is dropped —
        // byte-identical, since $sourcePageData !== null already implies it).
        if ($sourcePageData !== null) {
            $newsItem = $this->news->buildSourcePageItem($sourcePageData, $language);
            if ($newsItem !== null) {
                // Add to beginning of pages array (it's the "parent" page)
                array_unshift($pages, $newsItem);
            }
        }

        // Apply MetaVox filters if any and if MetaVox is available
        if (!empty($filters) && $this->metaVox->isMetaVoxAvailable()) {
            $pages = $this->news->applyMetaVoxFilters(
                $pages,
                $filters,
                $filterOperator,
                fn(array $fileIds): array => $this->metaVox->getMetaVoxDataForFiles($fileIds)
            );
        }

        // Apply the publication filter when the widget asks for published pages
        // only. Not gated on MetaVox: the manual draft/published status must be
        // honoured even when no publication date fields are configured.
        if ($filterPublished) {
            $pages = $this->news->applyPublicationDateFilter(
                $pages,
                fn(array $fileIds): array => $this->publicationState->publicationMetaForFiles($fileIds),
                fn(array $page, array $meta): string => $this->publicationState->effectivePublishState($page, $meta)
            );
        }

        $total = count($pages);

        $pages = $this->news->sortAndLimit($pages, $sortBy, $sortOrder, $limit);

        $result = [
            'items' => $pages,
            'total' => $total,
            'metavoxAvailable' => $this->metaVox->isMetaVoxAvailable(),
        ];

        // Cache for 5 minutes — the version-counter scheme makes correctness
        // independent of TTL (a counter bump renders this entry unreachable),
        // so the TTL only bounds memory growth from orphaned entries.
        if ($this->cache->isDistributedAvailable() && $newsCacheKey !== null) {
            $this->cache->setDistributed($newsCacheKey, json_encode($result), PageCacheService::NEWS_TTL);
        }

        return $result;
    }
}
