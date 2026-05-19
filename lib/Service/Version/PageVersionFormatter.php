<?php
declare(strict_types=1);

namespace OCA\IntraVox\Service\Version;

use OCA\Files_Versions\Versions\IVersion;

/**
 * Pure formatting helpers for IVersion entries returned by Files_Versions.
 *
 * Extracted from PageService so the version-display logic can be tested
 * in isolation: relative-time formatting is deterministic given a clock,
 * and the metadata accessors only depend on what the version backend
 * exposes (GroupVersion has getMetadata, plain IVersion does not).
 *
 * Stateless — safe to share between requests.
 */
final class PageVersionFormatter {
    /**
     * Convert IVersion entries to the API response shape used by the
     * IntraVox frontend (newest first).
     *
     * @param array<int, IVersion> $versions
     * @return array<int, array{
     *     id: int,
     *     timestamp: int,
     *     size: int,
     *     author: string|null,
     *     label: string|null,
     *     formattedDate: string,
     *     relativeTime: string,
     * }>
     */
    public function formatVersions(array $versions): array {
        $formatted = [];
        foreach ($versions as $version) {
            $timestamp = $version->getTimestamp();
            $formatted[] = [
                'id' => $timestamp,
                'timestamp' => $timestamp,
                'size' => $version->getSize(),
                'author' => $this->getAuthor($version),
                'label' => $this->getLabel($version),
                'formattedDate' => $this->formatRelativeTime($timestamp),
                'relativeTime' => $this->formatRelativeTime($timestamp),
            ];
        }

        usort($formatted, static fn($a, $b) => $b['timestamp'] - $a['timestamp']);
        return $formatted;
    }

    /**
     * Author from the version metadata when supported by the backend.
     * Plain IVersion has no metadata; GroupVersion does. Returns null
     * when not available — callers should fall back to file ownership.
     */
    public function getAuthor(IVersion $version): ?string {
        if (method_exists($version, 'getMetadata')) {
            $metadata = $version->getMetadata();
            return $metadata['author'] ?? null;
        }
        return null;
    }

    /**
     * Custom label set on a version (user-supplied). Same metadata-only
     * caveat as getAuthor().
     */
    public function getLabel(IVersion $version): ?string {
        if (method_exists($version, 'getMetadata')) {
            $metadata = $version->getMetadata();
            return $metadata['label'] ?? null;
        }
        return null;
    }

    /**
     * Format a unix timestamp the way the Nextcloud Files app does:
     * "36 sec. ago", "2 min. ago", "3 days ago" up to one week, then
     * the absolute date.
     *
     * Pure with respect to the supplied `$now`, which defaults to the
     * current time. Tests pass an explicit `$now` to keep assertions
     * deterministic.
     */
    public function formatRelativeTime(int $timestamp, ?int $now = null): string {
        $now ??= time();
        $diff = $now - $timestamp;

        if ($diff < 0) {
            return 'just now';
        }
        if ($diff < 60) {
            return $diff . ' sec. ago';
        }
        if ($diff < 3600) {
            $mins = (int) floor($diff / 60);
            return $mins . ' min. ago';
        }
        if ($diff < 86400) {
            $hours = (int) floor($diff / 3600);
            return $hours . ' hour' . ($hours > 1 ? 's' : '') . ' ago';
        }
        if ($diff < 604800) {
            $days = (int) floor($diff / 86400);
            return $days . ' day' . ($days > 1 ? 's' : '') . ' ago';
        }

        $date = new \DateTime();
        $date->setTimestamp($timestamp);
        return $date->format('j M Y');
    }
}
