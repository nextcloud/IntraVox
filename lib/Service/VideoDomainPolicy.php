<?php
declare(strict_types=1);

namespace OCA\IntraVox\Service;

/**
 * Which video platforms IntraVox recommends, tolerates, or discourages.
 *
 * Extracted from ApiController (controller split, PR-B), where 66 lines of
 * hardcoded platform names sat inside an HTTP controller. It is a policy table:
 * it decides nothing about the request, only about the domain.
 *
 * The levels are advisory and drive a warning in the admin UI; they do not block
 * anything. What blocks is the separate allowlist in the widget sanitiser.
 *
 *   1 recommended — privacy-friendly, mostly PeerTube instances and video.edu.nl
 *   2 commercial  — works fine, sets third-party cookies
 *   3 discouraged — tracking-heavy social platforms
 *
 * An UNKNOWN host is level 1, not level 3. That is deliberate and load-bearing:
 * self-hosted PeerTube instances are the common case in education and there is
 * no way to enumerate them, so an unrecognised domain is treated as one rather
 * than warned about.
 *
 * Matching is str_contains against the host, so "video.edu.nl" also matches a
 * subdomain of it. Carried over unchanged.
 */
final class VideoDomainPolicy {
    /**
     * @return array{category: string, level: int}
     */
    public function categorise(string $host): array {
        // Category 1: Recommended - privacy-friendly platforms
        $recommended = [
            'video.edu.nl',
            'peertube.tv',
            'framatube.org',
            'tilvids.com',
            'peertube.social',
            'video.ploud.fr',
            'diode.zone',
            'tube.privacytools.io',
            'peertube.debian.social',
            'video.linux.it',
            'video-dns.com', // mave.io — EU-hosted, cookieless, GDPR-compliant
        ];

        // Category 2: Commercial but relatively safe (business platforms)
        $commercial = [
            'vimeo.com',
            'wistia.com',
            'loom.com',
            'streamable.com',
            'bunny.net',
            'bunnycdn.com',
        ];

        // Category 3: Discouraged - major tracking/privacy concerns
        $discouraged = [
            'youtube.com',
            'youtu.be',
            'dailymotion.com',
            'tiktok.com',
            'facebook.com',
            'fb.watch',
            'instagram.com',
            'twitter.com',
            'x.com',
            'twitch.tv',
        ];

        foreach ($recommended as $pattern) {
            if (str_contains($host, $pattern)) {
                return ['category' => 'recommended', 'level' => 1];
            }
        }

        foreach ($commercial as $pattern) {
            if (str_contains($host, $pattern)) {
                return ['category' => 'commercial', 'level' => 2];
            }
        }

        foreach ($discouraged as $pattern) {
            if (str_contains($host, $pattern)) {
                return ['category' => 'discouraged', 'level' => 3];
            }
        }

        // Unknown domains - treat as custom PeerTube instances (allowed)
        return ['category' => 'custom', 'level' => 1];
    }
}
