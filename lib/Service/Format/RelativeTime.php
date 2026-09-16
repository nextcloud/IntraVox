<?php

declare(strict_types=1);

namespace OCA\IntraVox\Service\Format;

/**
 * Formats a unix timestamp as a coarse "N units ago" English relative string
 * ("just now", "3 minutes ago", "2 years ago").
 *
 * A pure, stateless helper extracted verbatim from PageService::getRelativeTime()
 * — it had no dependency on any page state, so it does not belong on the page
 * service. PageMetadataTest pins the output through getPageMetadata().
 */
final class RelativeTime {
    public function format(int $timestamp): string {
        $diff = time() - $timestamp;

        if ($diff < 60) {
            return 'just now';
        } elseif ($diff < 3600) {
            $minutes = floor($diff / 60);
            return $minutes . ' minute' . ($minutes > 1 ? 's' : '') . ' ago';
        } elseif ($diff < 86400) {
            $hours = floor($diff / 3600);
            return $hours . ' hour' . ($hours > 1 ? 's' : '') . ' ago';
        } elseif ($diff < 2592000) {
            $days = floor($diff / 86400);
            return $days . ' day' . ($days > 1 ? 's' : '') . ' ago';
        } elseif ($diff < 31536000) {
            $months = floor($diff / 2592000);
            return $months . ' month' . ($months > 1 ? 's' : '') . ' ago';
        } else {
            $years = floor($diff / 31536000);
            return $years . ' year' . ($years > 1 ? 's' : '') . ' ago';
        }
    }
}
