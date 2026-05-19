<?php
declare(strict_types=1);

namespace OCA\IntraVox\Service\Sanitize;

/**
 * Sanitize URLs used in page widgets (links, buttons, images-by-ref).
 *
 * Allowed schemes: http, https, relative paths, and hash anchors. Anything
 * else is replaced with an empty string so renderers can omit it gracefully.
 */
final class UrlSanitizer {
    public function sanitize(string $url): string {
        $url = filter_var($url, FILTER_SANITIZE_URL);
        if ($url === false || $url === '') {
            return '';
        }
        if (!preg_match('#^(https?://|/|\#)#i', $url)) {
            return '';
        }
        return $url;
    }
}
