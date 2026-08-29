<?php
declare(strict_types=1);

namespace OCA\IntraVox\Service\People;

use OCA\IntraVox\Service\Filter\FilterSpec;
use OCP\ICache;
use Psr\Log\LoggerInterface;

/**
 * Caching a cohort: which people match a set of filters, and their facet
 * counts.
 *
 * Extracted from UserService (service split, fase 2). What lives here is the
 * cache protocol -- read, lock, rebuild, write fresh and stale -- and nothing
 * about what a person IS.
 *
 * The scan itself and the cache context stay in UserService and arrive as
 * CALLABLES, the same way NewsPageService receives the publication gate. That
 * is deliberate: scanCohort() reaches into account properties, group
 * membership and AccountScopePolicy, and moving it would drag the profile
 * layer along or force a second copy of propertyToKey()/propertyVisible().
 * The caller SCANS, this class REMEMBERS.
 *
 * The stale-while-revalidate behaviour is the reason this is worth its own
 * class. On an LDAP instance $group->getUsers() can take 10-30 seconds; without
 * the lock every concurrent reader during a cache miss starts its own scan,
 * which is not a slow page but an outage.
 */
class CohortCache {
    /**
     * Rebuild lock lifetime (2 minutes).
     *
     * Long enough for a slow LDAP scan, short enough that a crashed request
     * cannot wedge the cohort for long.
     */
    private const COHORT_LOCK_TTL = 120;

    /** Registry of recently-built cohort recipes, for the warmup job. */
    private const COHORT_REGISTRY_KEY = 'cohort_registry_v1';
    private const COHORT_REGISTRY_TTL = 86400;
    private const COHORT_REGISTRY_MAX = 50;

    /**
     * Cache TTL for cohort snapshots (15 minutes).
     *
     * Deliberately shorter than FILTER_CACHE_TTL: a stale *number* beside a
     * checkbox is far more noticeable, and less forgivable, than a stale list.
     */
    private const COHORT_CACHE_TTL = 900;

    /**
     * How long the stale fallback copy lives (1 hour).
     *
     * Deliberately longer than COHORT_CACHE_TTL: its whole job is to still be
     * there when the fresh entry has expired, so a concurrent reader has
     * something to be served while one request rebuilds.
     */
    private const COHORT_STALE_TTL = 3600;

    /**
     * @param callable(array,string,array,?string=):CohortSnapshot $scanCohort
     *        does the actual scan; lives in UserService because it needs the
     *        account, group and scope layers.
     * @param callable():array{audience: string, groupHash: string} $recipeContext
     *        who the recipe was built for, so the warmup job rebuilds it for the
     *        same audience rather than for whoever happens to run the cron.
     * @param callable():string $cacheContext
     *        the audience the cache key is scoped to, so one group's snapshot
     *        is never served to another.
     */
    public function __construct(
        private ?ICache $cache,
        private LoggerInterface $logger,
        private $scanCohort,
        private $cacheContext,
        private $recipeContext,
    ) {
    }

    /**
     * Build (or reuse) the compact cohort the editor config selects.
     */
    public function buildCohortSnapshot(
        array $editorFilters,
        string $operator,
        array $facetFields,
        array $searchFields,
        string $sortBy
    ): CohortSnapshot {
        $neededFields = array_values(array_unique(array_merge(
            $facetFields,
            array_map(static fn($f): string => FilterSpec::aliasField((string)$f), $searchFields),
            array_map(static fn(array $f): string => $f['field'], $editorFilters),
            [FilterSpec::aliasField($sortBy)]
        )));

        $cacheKey = 'cohort_v1_' . ($this->cacheContext)() . '_' . md5(json_encode([
            $editorFilters, $operator, $neededFields,
        ]));

        // Fresh entry: serve it and we are done.
        $snapshot = $this->readSnapshotFromCache($cacheKey);
        if ($snapshot !== null) {
            return $snapshot;
        }

        // Stale-while-revalidate. Without this, every concurrent reader that
        // arrives during a cache miss starts its own full scan. On an LDAP
        // instance where $group->getUsers() takes 10-30 s that is not a slow
        // page, it is an outage: fifty readers means fifty simultaneous
        // scans. Only the lock holder rebuilds; everyone else is served the
        // previous snapshot, which is at most a few minutes old.
        $stale = $this->readSnapshotFromCache($cacheKey . '_stale');
        $lockKey = $cacheKey . '_lock';

        if (!$this->acquireRebuildLock($lockKey)) {
            if ($stale !== null) {
                return $stale;
            }
            // Nothing to fall back on (first ever request for this cohort):
            // build it rather than show the viewer an empty widget.
        }

        try {
            $snapshot = ($this->scanCohort)($editorFilters, $operator, $neededFields);
            $this->rememberCohortRecipe($cacheKey, $editorFilters, $operator, $neededFields);

            if ($this->cache !== null) {
                $payload = json_encode($snapshot->jsonSerializeForCache());
                // Shorter TTL than the legacy filter cache: a stale *number*
                // next to a checkbox is far more visible than a stale list.
                $this->cache->set($cacheKey, $payload, self::COHORT_CACHE_TTL);
                // The stale copy outlives the fresh one on purpose — it is
                // what the next cache miss serves while rebuilding.
                $this->cache->set($cacheKey . '_stale', $payload, self::COHORT_STALE_TTL);
            }

            return $snapshot;
        } finally {
            $this->releaseRebuildLock($lockKey);
        }
    }
    /**
     * Remember how a cohort was built, so a background job can rebuild it
     * before anyone waits for it.
     *
     * Only the recipe is stored, never the result: the snapshot itself lives
     * under its own key with its own TTL, and duplicating it here would mean
     * two copies that can disagree.
     */
    public function rememberCohortRecipe(string $cacheKey, array $editorFilters, string $operator, array $neededFields): void {
        if ($this->cache === null) {
            return;
        }

        try {
            $known = json_decode((string)$this->cache->get(self::COHORT_REGISTRY_KEY), true);
            $known = is_array($known) ? $known : [];

            $known[$cacheKey] = [
                'filters' => $editorFilters,
                'operator' => $operator,
                'fields' => $neededFields,
                'audience' => ($this->recipeContext)()['audience'],
                'groupHash' => ($this->recipeContext)()['groupHash'],
            ];

            // Bounded: keep the most recent entries only, so a busy instance
            // cannot grow this without limit.
            if (count($known) > self::COHORT_REGISTRY_MAX) {
                $known = array_slice($known, -self::COHORT_REGISTRY_MAX, null, true);
            }

            $this->cache->set(self::COHORT_REGISTRY_KEY, json_encode($known), self::COHORT_REGISTRY_TTL);
        } catch (\Throwable $e) {
            // Warmup is an optimisation; never let it break a real request.
        }
    }
    /**
     * Rebuild recently-used cohorts. Called by PeopleCohortWarmupJob.
     *
     * Only rebuilds cohorts whose fresh entry has expired, so a run that
     * finds everything warm costs one cache read.
     *
     * @return array{considered: int, rebuilt: int}
     */
    public function warmCohorts(): array {
        if ($this->cache === null) {
            return ['considered' => 0, 'rebuilt' => 0];
        }

        $known = json_decode((string)$this->cache->get(self::COHORT_REGISTRY_KEY), true);
        if (!is_array($known)) {
            return ['considered' => 0, 'rebuilt' => 0];
        }

        $rebuilt = 0;

        foreach ($known as $cacheKey => $recipe) {
            if (!is_array($recipe)) {
                continue;
            }
            // Still fresh: nothing to do.
            if ($this->readSnapshotFromCache((string)$cacheKey) !== null) {
                continue;
            }
            // Someone is already rebuilding it.
            if (!$this->acquireRebuildLock($cacheKey . '_lock')) {
                continue;
            }

            // Rebuild for the audience the cohort was recorded under, not the
            // one this process happens to be running as. The job has no
            // session, so without this every logged-in cohort came back
            // anonymous — Local fields stripped, custom fields absent — and
            // was written straight over the logged-in cache key, emptying the
            // viewer's filter panel until a real request rebuilt it.
            $audience = (string)($recipe['audience'] ?? '');
            if ($audience === '') {
                continue;
            }

            try {
                $snapshot = ($this->scanCohort)(
                    is_array($recipe['filters'] ?? null) ? $recipe['filters'] : [],
                    (string)($recipe['operator'] ?? 'AND'),
                    is_array($recipe['fields'] ?? null) ? $recipe['fields'] : [],
                    $audience
                );

                $payload = json_encode($snapshot->jsonSerializeForCache());
                $this->cache->set((string)$cacheKey, $payload, self::COHORT_CACHE_TTL);
                $this->cache->set($cacheKey . '_stale', $payload, self::COHORT_STALE_TTL);
                $rebuilt++;
            } catch (\Throwable $e) {
                $this->logger->warning('IntraVox: cohort warmup failed: ' . $e->getMessage());
            } finally {
                $this->releaseRebuildLock($cacheKey . '_lock');
            }
        }

        return ['considered' => count($known), 'rebuilt' => $rebuilt];
    }
    /**
     * Read and decode a snapshot, or null when absent/unusable.
     */
    public function readSnapshotFromCache(string $key): ?CohortSnapshot {
        if ($this->cache === null) {
            return null;
        }

        $cached = $this->cache->get($key);
        if (!is_string($cached)) {
            return null;
        }

        return CohortSnapshot::fromCache(json_decode($cached, true));
    }
    /**
     * Try to become the one request that rebuilds this cohort.
     *
     * Uses ICache::add(), which only succeeds when the key does not exist —
     * an atomic test-and-set on every distributed backend Nextcloud ships.
     * Returns true when there is no cache at all, so a cacheless instance
     * simply behaves as it did before.
     */
    public function acquireRebuildLock(string $lockKey): bool {
        if ($this->cache === null) {
            return true;
        }

        // add() is atomic set-if-absent and lives on IMemcache, not on ICache:
        // Redis and APCu have it, the database backend does not. Without it
        // there is no lock to take, and the correct answer is to let the
        // rebuild proceed -- a duplicate scan is far better than no cohort.
        if (!method_exists($this->cache, 'add')) {
            return true;
        }

        try {
            /** @var \OCP\IMemcache $cache */
            $cache = $this->cache;
            return $cache->add($lockKey, '1', self::COHORT_LOCK_TTL);
        } catch (\Throwable $e) {
            // A backend that has add() but refuses must not block the rebuild.
            return true;
        }
    }
    public function releaseRebuildLock(string $lockKey): void {
        if ($this->cache === null) {
            return;
        }

        try {
            $this->cache->remove($lockKey);
        } catch (\Throwable $e) {
            // The TTL will clear it.
        }
    }
}
