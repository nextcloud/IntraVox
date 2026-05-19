<?php
declare(strict_types=1);

namespace OCA\IntraVox\Performance;

/**
 * Light request-scoped timing utility used to measure controller and service
 * latency. Designed to be called at the start and end of expensive operations
 * so we can collect p50/p95 baseline data before applying caching changes.
 *
 * Marks are stored as monotonic timestamps in seconds (float). Recordings
 * are kept in a static map, so they survive across instance boundaries within
 * a single request — convenient for measuring code paths that span multiple
 * service calls.
 */
final class RequestTimer {
    /** @var array<string, float> marker name => start time */
    private static array $marks = [];

    /** @var array<string, array<int, float>> name => list of durations (seconds) */
    private static array $recordings = [];

    public static function start(string $name): void {
        self::$marks[$name] = microtime(true);
    }

    /**
     * Stop a marker and record its duration. Returns the duration in seconds,
     * or null if start() was never called for this name.
     */
    public static function stop(string $name): ?float {
        if (!isset(self::$marks[$name])) {
            return null;
        }
        $duration = microtime(true) - self::$marks[$name];
        unset(self::$marks[$name]);
        self::$recordings[$name][] = $duration;
        return $duration;
    }

    /**
     * Return recorded durations for a given marker. Empty array if nothing
     * was recorded.
     *
     * @return array<int, float>
     */
    public static function getRecordings(string $name): array {
        return self::$recordings[$name] ?? [];
    }

    /**
     * Return all recordings keyed by marker name. Useful for emitting a
     * single log line at the end of a request.
     *
     * @return array<string, array<int, float>>
     */
    public static function getAllRecordings(): array {
        return self::$recordings;
    }

    /**
     * Reset state. Intended for tests.
     */
    public static function reset(): void {
        self::$marks = [];
        self::$recordings = [];
    }
}
