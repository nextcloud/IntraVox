<?php

declare(strict_types=1);

namespace OCA\IntraVox;

/**
 * Central constants for IntraVox app
 *
 * This file contains all default values that are used across multiple files.
 * Having them in one place ensures consistency and easier maintenance.
 */
class Constants {
    /**
     * Default video domains whitelist
     *
     * These are privacy-friendly video platforms that are enabled by default.
     * Admins can modify this list via settings to add/remove domains.
     *
     * - youtube-nocookie.com: Privacy-enhanced YouTube embed, no tracking cookies
     * - player.vimeo.com: Professional, ad-free player, GDPR-compliant
     */
    public const DEFAULT_VIDEO_DOMAINS = [
        'https://www.youtube-nocookie.com',
        'https://player.vimeo.com'
    ];

    /**
     * Get default video domains as JSON string
     * For backwards compatibility with existing config storage
     */
    public static function getDefaultVideoDomainsJson(): string {
        return json_encode(self::DEFAULT_VIDEO_DOMAINS);
    }
}
