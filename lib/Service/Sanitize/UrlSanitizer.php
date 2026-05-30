<?php
declare(strict_types=1);

namespace OCA\IntraVox\Service\Sanitize;

/**
 * Sanitize URLs used in page widgets (links, buttons, images-by-ref).
 *
 * Allowed schemes:
 *   - http(s) — external web links
 *   - mailto: — "email us" actions
 *   - tel:    — click-to-call on mobile
 *   - sms:    — click-to-text on mobile
 *   - /       — root-relative paths inside this Nextcloud instance
 *   - #       — same-page anchors
 *
 * Anything else (javascript:, data:, file:, xmpp:, matrix:, bare domains)
 * is replaced with an empty string so renderers can omit it gracefully.
 * mailto/tel/sms have no JavaScript execution path and are part of the
 * default URL allowlist of DOMPurify and HTMLPurifier.
 */
final class UrlSanitizer {
    public function sanitize(string $url): string {
        $url = filter_var($url, FILTER_SANITIZE_URL);
        if ($url === false || $url === '') {
            return '';
        }
        if (!preg_match('#^(https?://|mailto:|tel:|sms:|/|\#)#i', $url)) {
            return '';
        }
        return $url;
    }
}
