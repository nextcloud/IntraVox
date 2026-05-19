<?php
declare(strict_types=1);

namespace OCA\IntraVox\Service\Util;

/**
 * Small, pure utility functions extracted from PageService.
 *
 * - `sanitizeId` enforces the filesystem-safe character set for legacy
 *   path-based page IDs (the modern `page-{uuid}` IDs already match it).
 * - `generateUUID` produces an RFC 4122 v4 UUID without leaning on a
 *   third-party library — used for `page-{uuid}` and `template-{uuid}`.
 * - `parsePhpSize` reads `php.ini`-style size suffixes ("2M", "50K", …).
 * - `formatBytes` is the inverse: turn a byte count into a human label.
 *
 * Living here keeps PageService's surface area focused on page-domain
 * logic and lets these helpers gather independent tests + reuse without
 * dragging the constructor in.
 */
final class PageIdUtils {
    public function sanitizeId(string $id): string {
        $id = preg_replace('/[^a-zA-Z0-9_-]/', '', $id);
        if ($id === '') {
            throw new \InvalidArgumentException('Invalid page ID');
        }
        return $id;
    }

    /**
     * RFC 4122 v4 UUID (random-based). Output format: 8-4-4-4-12 hex digits.
     */
    public function generateUUID(): string {
        $data = random_bytes(16);
        $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80);
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }

    /**
     * Parse PHP-ini size notation. Supported suffixes: K, M, G
     * (case-insensitive). Bare numbers are read as bytes.
     */
    public function parsePhpSize(string $size): int {
        $size = trim($size);
        if ($size === '') {
            return 0;
        }
        $unit = strtoupper(substr($size, -1));
        $value = (int) substr($size, 0, -1);
        return match ($unit) {
            'G' => $value * 1024 * 1024 * 1024,
            'M' => $value * 1024 * 1024,
            'K' => $value * 1024,
            default => (int) $size,
        };
    }

    /**
     * Human-readable byte count: "1.5 MB", "812 KB", etc. Two decimals,
     * 1024-based units (matches what Nextcloud's Files UI shows).
     */
    public function formatBytes(int $bytes): string {
        if ($bytes < 1024) {
            return $bytes . ' B';
        }
        if ($bytes < 1024 * 1024) {
            return round($bytes / 1024, 2) . ' KB';
        }
        if ($bytes < 1024 * 1024 * 1024) {
            return round($bytes / (1024 * 1024), 2) . ' MB';
        }
        return round($bytes / (1024 * 1024 * 1024), 2) . ' GB';
    }
}
