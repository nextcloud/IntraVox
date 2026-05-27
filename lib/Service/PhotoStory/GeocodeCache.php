<?php
declare(strict_types=1);

namespace OCA\IntraVox\Service\PhotoStory;

use OCA\IntraVox\AppInfo\Application;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\Http\Client\IClientService;
use OCP\IConfig;
use OCP\IDBConnection;
use Psr\Log\LoggerInterface;

/**
 * IntraVox-owned reverse-geocode cache backed by oc_intravox_geocode_cache.
 *
 * Coordinates are rounded to 4 decimals (~11 m grid) before lookup so nearby
 * shots share a single Nominatim hit. Respects Nominatim's 1 req/sec usage
 * policy via a per-process timestamp guard.
 */
class GeocodeCache {
    private const TABLE = 'intravox_geocode_cache';
    private const RATE_LIMIT_SECONDS = 1.1;
    private const HTTP_TIMEOUT = 15;

    /** @var float monotonic-style timestamp of the last Nominatim call in this process */
    private float $lastRequestAt = 0.0;
    private int $consecutiveFailures = 0;
    private bool $disabledForRun = false;

    public function __construct(
        private IDBConnection $db,
        private IConfig $config,
        private IClientService $clientService,
        private LoggerInterface $logger,
    ) {}

    /**
     * DB-only cache hit. Returns null on miss.
     *
     * @return array{poi: ?string, place: ?string, city: ?string, country: ?string, country_code: ?string}|null
     */
    public function lookup(float $lat, float $lon): ?array {
        [$latR, $lonR] = $this->roundCoords($lat, $lon);
        try {
            $qb = $this->db->getQueryBuilder();
            $qb->select('poi', 'place', 'city', 'country', 'country_code')
                ->from(self::TABLE)
                ->where($qb->expr()->eq('lat_rounded', $qb->createNamedParameter($latR)))
                ->andWhere($qb->expr()->eq('lon_rounded', $qb->createNamedParameter($lonR)))
                ->setMaxResults(1);
            $result = $qb->executeQuery();
            $row = $result->fetch();
            $result->closeCursor();
            if (!$row) {
                return null;
            }
            return [
                'poi' => $this->nullable($row['poi'] ?? null),
                'place' => $this->nullable($row['place'] ?? null),
                'city' => $this->nullable($row['city'] ?? null),
                'country' => $this->nullable($row['country'] ?? null),
                'country_code' => $this->nullable($row['country_code'] ?? null),
            ];
        } catch (\Throwable $e) {
            $this->logger->warning('GeocodeCache: lookup failed: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Returns cached result if present; otherwise calls Nominatim and persists.
     *
     * @return array{poi: ?string, place: ?string, city: ?string, country: ?string, country_code: ?string}|null
     */
    public function fetch(float $lat, float $lon, bool $useCache = true): ?array {
        if ($useCache) {
            $cached = $this->lookup($lat, $lon);
            if ($cached !== null) {
                return $cached;
            }
        }
        if ($this->disabledForRun) {
            return null;
        }

        $this->respectRateLimit();

        $endpoint = $this->config->getAppValue(
            Application::APP_ID,
            'photostory.geocode.endpoint',
            'https://nominatim.openstreetmap.org/reverse',
        );
        $userAgent = $this->config->getAppValue(
            Application::APP_ID,
            'photostory.geocode.user_agent',
            'IntraVox-PhotoStory/1.0',
        );

        try {
            $client = $this->clientService->newClient();
            $response = $client->get($endpoint, [
                'query' => [
                    'lat' => number_format($lat, 6, '.', ''),
                    'lon' => number_format($lon, 6, '.', ''),
                    'format' => 'json',
                    'zoom' => 16,
                    'addressdetails' => 1,
                    'accept-language' => 'nl',
                ],
                'headers' => [
                    'User-Agent' => $userAgent,
                    'Accept' => 'application/json',
                ],
                'timeout' => self::HTTP_TIMEOUT,
            ]);
            $this->lastRequestAt = microtime(true);
        } catch (\Throwable $e) {
            $this->consecutiveFailures++;
            $this->logger->warning('GeocodeCache: Nominatim request failed: ' . $e->getMessage());
            if ($this->consecutiveFailures >= 3) {
                $this->disabledForRun = true;
                $this->logger->warning('GeocodeCache: disabling geocoding for the rest of this request after 3 failures');
            }
            return null;
        }

        $body = (string)$response->getBody();
        try {
            $json = json_decode($body, true, 512, JSON_THROW_ON_ERROR);
        } catch (\Throwable $e) {
            $this->logger->warning('GeocodeCache: malformed JSON from Nominatim: ' . $e->getMessage());
            return null;
        }
        if (!is_array($json)) {
            return null;
        }

        $parsed = $this->parseNominatimResponse($json);
        $this->store($lat, $lon, $parsed, $body);
        $this->consecutiveFailures = 0;
        return $parsed;
    }

    /**
     * Enrich each photo dict with a `location_display` string derived from its GPS coords.
     *
     * Strategy: prefer existing MetaVox `location` UNLESS the admin enabled
     * `prefer_poi_over_metavox` AND the geocode returned a more-specific POI.
     * Otherwise fall back to poi > place > city > country.
     *
     * @param array<int, array<string, mixed>> $photos
     * @return array<int, array<string, mixed>>
     */
    public function enrichPhotos(array $photos, bool $allowNetworkFetches = false): array {
        if (empty($photos)) {
            return $photos;
        }

        $enabled = $this->config->getAppValue(
            Application::APP_ID,
            'photostory.geocode.enabled',
            'yes',
        ) !== 'no';
        if (!$enabled) {
            // Even without geocoding, mirror existing location to location_display
            // so the frontend can rely on a single field name.
            foreach ($photos as &$p) {
                if (!array_key_exists('location_display', $p)) {
                    $p['location_display'] = !empty($p['location']) ? (string)$p['location'] : null;
                }
            }
            return $photos;
        }

        $preferPoi = $this->config->getAppValue(
            Application::APP_ID,
            'photostory.geocode.prefer_poi_over_metavox',
            'no',
        ) === 'yes';

        // Collect unique coords (rounded) to avoid duplicate fetches across the batch
        $byKey = [];
        foreach ($photos as $idx => $p) {
            $gps = $p['gps'] ?? null;
            if (!is_array($gps) || !isset($gps['lat'], $gps['lon'])) {
                continue;
            }
            [$latR, $lonR] = $this->roundCoords((float)$gps['lat'], (float)$gps['lon']);
            $key = $latR . ',' . $lonR;
            $byKey[$key][] = $idx;
        }

        // Two-pass: cache-only first for all keys, then optionally a capped
        // number of network fetches for cache-misses. HTTP requests pass
        // $allowNetworkFetches=false to keep the request-thread non-blocking;
        // misses are queued for the GeocodeWarmupJob background worker. CLI
        // backfills + the warmup job pass $allowNetworkFetches=true.
        $maxNewFetches = (int)$this->config->getAppValue(
            Application::APP_ID,
            'photostory.geocode.max_new_per_request',
            '20',
        );
        $resolved = [];
        $misses = [];
        foreach (array_keys($byKey) as $key) {
            [$latR, $lonR] = array_map('floatval', explode(',', $key, 2));
            $cached = $this->lookup($latR, $lonR);
            if ($cached !== null) {
                $resolved[$key] = $cached;
            } else {
                $resolved[$key] = null;
                $misses[] = [$key, $latR, $lonR];
            }
        }

        if ($allowNetworkFetches) {
            $fetched = 0;
            foreach ($misses as [$key, $latR, $lonR]) {
                if ($this->disabledForRun || $fetched >= $maxNewFetches) {
                    break;
                }
                try {
                    $resolved[$key] = $this->fetch($latR, $lonR, false);
                    $fetched++;
                } catch (\Throwable $e) {
                    $this->logger->warning('GeocodeCache: enrichPhotos fetch failed for ' . $key . ': ' . $e->getMessage());
                }
            }
        } elseif (!empty($misses)) {
            // Queue misses for the background job. Cheap: append unique
            // (lat,lon) keys to an appconfig-stored JSON list, capped at 500.
            $this->enqueueMisses($misses);
        }

        foreach ($photos as &$p) {
            $existing = !empty($p['location']) ? (string)$p['location'] : null;
            $gps = $p['gps'] ?? null;
            $geo = null;
            if (is_array($gps) && isset($gps['lat'], $gps['lon'])) {
                [$latR, $lonR] = $this->roundCoords((float)$gps['lat'], (float)$gps['lon']);
                $key = $latR . ',' . $lonR;
                $geo = $resolved[$key] ?? null;
            }

            $display = null;
            if ($existing !== null && !$preferPoi) {
                $display = $existing;
            } elseif ($existing !== null && $preferPoi && is_array($geo) && !empty($geo['poi'])) {
                $display = (string)$geo['poi'];
            } elseif ($existing !== null) {
                $display = $existing;
            } elseif (is_array($geo)) {
                $display = $geo['poi'] ?? $geo['place'] ?? $geo['city'] ?? $geo['country'] ?? null;
            }

            $p['location_display'] = $display !== null ? (string)$display : null;
            // Stash the structured geocode for downstream consumers (country_code etc.)
            if (is_array($geo)) {
                $p['geocode'] = $geo;
            }
        }
        unset($p);

        return $photos;
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Internal helpers
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Pull POI / place / city / country / country_code out of a Nominatim JSON payload.
     *
     * POI wins over place/city when an `address.tourism|historic|attraction|museum|building`
     * tag is present, otherwise we degrade to suburb/neighbourhood, then city/town/village,
     * then country.
     *
     * @param array<string, mixed> $json
     * @return array{poi: ?string, place: ?string, city: ?string, country: ?string, country_code: ?string}
     */
    private function parseNominatimResponse(array $json): array {
        $addr = is_array($json['address'] ?? null) ? $json['address'] : [];

        $poi = null;
        foreach (['tourism', 'historic', 'attraction', 'museum', 'building'] as $tag) {
            if (!empty($addr[$tag]) && is_string($addr[$tag])) {
                $poi = (string)$addr[$tag];
                break;
            }
        }
        // Some POIs leak into top-level "name" field instead of address.*
        if ($poi === null && !empty($json['name']) && is_string($json['name'])) {
            // Only accept top-level name when class hints at a POI-style feature
            $klass = (string)($json['class'] ?? '');
            $type = (string)($json['type'] ?? '');
            $poiClasses = ['tourism', 'historic', 'amenity', 'leisure', 'building'];
            if (in_array($klass, $poiClasses, true) || in_array($type, $poiClasses, true)) {
                $poi = (string)$json['name'];
            }
        }

        $place = null;
        foreach (['suburb', 'neighbourhood', 'quarter', 'borough'] as $tag) {
            if (!empty($addr[$tag]) && is_string($addr[$tag])) {
                $place = (string)$addr[$tag];
                break;
            }
        }

        $city = null;
        foreach (['city', 'town', 'village', 'municipality', 'hamlet'] as $tag) {
            if (!empty($addr[$tag]) && is_string($addr[$tag])) {
                $city = (string)$addr[$tag];
                break;
            }
        }

        $country = isset($addr['country']) && is_string($addr['country']) ? (string)$addr['country'] : null;
        $countryCode = null;
        if (isset($addr['country_code']) && is_string($addr['country_code']) && $addr['country_code'] !== '') {
            $countryCode = strtoupper(substr((string)$addr['country_code'], 0, 2));
        }

        return [
            'poi' => $poi,
            'place' => $place,
            'city' => $city,
            'country' => $country,
            'country_code' => $countryCode,
        ];
    }

    /**
     * Persist a freshly-fetched result. Uses INSERT-or-ignore semantics via the
     * unique (lat_rounded, lon_rounded) index so a concurrent writer doesn't
     * blow us up.
     *
     * @param array{poi: ?string, place: ?string, city: ?string, country: ?string, country_code: ?string} $parsed
     */
    private function store(float $lat, float $lon, array $parsed, string $rawJson): void {
        [$latR, $lonR] = $this->roundCoords($lat, $lon);
        try {
            $qb = $this->db->getQueryBuilder();
            $qb->insert(self::TABLE)
                ->values([
                    'lat_rounded' => $qb->createNamedParameter($latR),
                    'lon_rounded' => $qb->createNamedParameter($lonR),
                    'poi' => $qb->createNamedParameter($parsed['poi']),
                    'place' => $qb->createNamedParameter($parsed['place']),
                    'city' => $qb->createNamedParameter($parsed['city']),
                    'country' => $qb->createNamedParameter($parsed['country']),
                    'country_code' => $qb->createNamedParameter($parsed['country_code']),
                    'raw_json' => $qb->createNamedParameter($rawJson),
                    'fetched_at' => $qb->createNamedParameter(time(), IQueryBuilder::PARAM_INT),
                ]);
            $qb->executeStatement();
        } catch (\Throwable $e) {
            // Likely a unique-key conflict from a race; that's fine, the row exists.
            $this->logger->debug('GeocodeCache: store insert skipped: ' . $e->getMessage());
        }
    }

    private function respectRateLimit(): void {
        $now = microtime(true);
        $delta = $now - $this->lastRequestAt;
        if ($this->lastRequestAt > 0.0 && $delta < self::RATE_LIMIT_SECONDS) {
            usleep((int)round((self::RATE_LIMIT_SECONDS - $delta) * 1_000_000));
        }
    }

    /**
     * @return array{0: string, 1: string} numeric strings with 4 decimals, suitable for DECIMAL(7,4) compare
     */
    private function roundCoords(float $lat, float $lon): array {
        return [
            number_format(round($lat, 4), 4, '.', ''),
            number_format(round($lon, 4), 4, '.', ''),
        ];
    }

    private function nullable(mixed $val): ?string {
        if ($val === null) {
            return null;
        }
        $s = (string)$val;
        return $s === '' ? null : $s;
    }

    private const QUEUE_KEY = 'photostory.geocode.queue';
    private const QUEUE_MAX = 500;

    /**
     * Append cache-miss coords to the background-job queue. The list is
     * stored as JSON in appconfig — cheap and avoids a dedicated table.
     * Capped at QUEUE_MAX to bound storage; old entries are LRU-evicted.
     *
     * @param array<int, array{0: string, 1: float, 2: float}> $misses
     */
    private function enqueueMisses(array $misses): void {
        try {
            $existing = $this->config->getAppValue(Application::APP_ID, self::QUEUE_KEY, '[]');
            $queue = json_decode($existing, true);
            if (!is_array($queue)) {
                $queue = [];
            }
            $byKey = [];
            foreach ($queue as $entry) {
                if (is_array($entry) && isset($entry['k'])) {
                    $byKey[(string)$entry['k']] = $entry;
                }
            }
            $now = time();
            foreach ($misses as [$key, $latR, $lonR]) {
                $byKey[$key] = ['k' => $key, 'lat' => (float)$latR, 'lon' => (float)$lonR, 't' => $now];
            }
            // Keep the most-recent QUEUE_MAX entries.
            if (count($byKey) > self::QUEUE_MAX) {
                uasort($byKey, fn($a, $b) => ($b['t'] ?? 0) <=> ($a['t'] ?? 0));
                $byKey = array_slice($byKey, 0, self::QUEUE_MAX, true);
            }
            $this->config->setAppValue(Application::APP_ID, self::QUEUE_KEY, json_encode(array_values($byKey)));
        } catch (\Throwable $e) {
            $this->logger->warning('GeocodeCache: enqueueMisses failed: ' . $e->getMessage());
        }
    }

    /**
     * Background-job entry point: drain the queue, fetching geocodes until
     * the rate-limit-bounded budget is spent. Called by GeocodeWarmupJob.
     *
     * @return array{processed: int, queued: int, failures: int}
     */
    public function processQueue(int $maxFetches = 50): array {
        $existing = $this->config->getAppValue(Application::APP_ID, self::QUEUE_KEY, '[]');
        $queue = json_decode($existing, true);
        if (!is_array($queue) || empty($queue)) {
            return ['processed' => 0, 'queued' => 0, 'failures' => 0];
        }
        $processed = 0;
        $failures = 0;
        $remaining = [];
        foreach ($queue as $entry) {
            if (!is_array($entry) || !isset($entry['lat'], $entry['lon'])) {
                continue;
            }
            if ($processed >= $maxFetches || $this->disabledForRun) {
                $remaining[] = $entry;
                continue;
            }
            try {
                $this->fetch((float)$entry['lat'], (float)$entry['lon'], false);
                $processed++;
            } catch (\Throwable $e) {
                $failures++;
                // Keep failed entry in queue for retry on next run.
                $remaining[] = $entry;
            }
        }
        $this->config->setAppValue(Application::APP_ID, self::QUEUE_KEY, json_encode($remaining));
        return ['processed' => $processed, 'queued' => count($remaining), 'failures' => $failures];
    }
}
