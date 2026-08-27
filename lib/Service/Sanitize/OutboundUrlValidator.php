<?php
declare(strict_types=1);

namespace OCA\IntraVox\Service\Sanitize;

/**
 * The one place an outbound URL is cleared for fetching. (SSRF)
 *
 * There used to be two of these. FeedReaderService::validateUrl was hardened
 * after `http://[::1]/` was found to reach the fetcher; ExternalIcsService kept
 * the original, and its own docblock never learned about it. The two had
 * already drifted into a security difference rather than a stylistic one:
 *
 *   $ips = gethostbynamel($host);
 *   if (is_array($ips)) { ...check... }
 *
 * gethostbynamel() returns A records and nothing else. It is false when a name
 * does not resolve, and false again when a host publishes AAAA records only —
 * both landed in the else that was not there, so the guard skipped precisely
 * the hosts most worth guarding.
 *
 * Measured against the old code on a live server, so the scope is known rather
 * than assumed. A bare IPv4 literal was NOT a hole: gethostbynamel('127.0.0.1')
 * hands back the address itself, which then failed the range check. The three
 * real gaps were:
 *   - IPv6 literals    — `https://[::1]/` went straight through;
 *   - AAAA-only hosts  — ipv6.google.com: no A records, four AAAA, check skipped;
 *   - unresolvable     — a .invalid name, check skipped.
 * Hence both the IP-literal branch and the AAAA lookup below.
 *
 * That mattered more for ICS than for feeds: the ICS URL arrives as the
 * `externalIcsUrls` request parameter on PublicShareController::getEventsByShare,
 * which is #[PublicPage]. No login required to aim it at the internal network.
 *
 * Scheme policy differs per caller and is a parameter, not a default: feeds
 * accept http and https, ICS accepts https only. Consolidating must not quietly
 * widen the stricter of the two.
 *
 * Nextcloud's IClientService also enforces allow_local_address, which is a
 * second layer against DNS rebinding. This class is the first.
 */
final class OutboundUrlValidator {
    public const SCHEMES_HTTP = ['http', 'https'];
    public const SCHEMES_HTTPS_ONLY = ['https'];

    /**
     * @param string[] $allowedSchemes one of the SCHEMES_* constants
     * @throws \InvalidArgumentException when the URL must not be fetched
     */
    public function validate(string $url, array $allowedSchemes = self::SCHEMES_HTTP, string $label = 'URL'): void {
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            throw new \InvalidArgumentException('Invalid ' . $label);
        }

        $scheme = parse_url($url, PHP_URL_SCHEME);
        if (!is_string($scheme) || !in_array(strtolower($scheme), $allowedSchemes, true)) {
            throw new \InvalidArgumentException(
                $allowedSchemes === self::SCHEMES_HTTPS_ONLY
                    ? 'Only HTTPS URLs are allowed for ' . $label
                    : 'Only HTTP(S) URLs are supported'
            );
        }

        $host = parse_url($url, PHP_URL_HOST);
        if (!is_string($host) || $host === '') {
            throw new \InvalidArgumentException('Invalid ' . $label);
        }

        // Fail closed. See the class docblock for what the old `if (is_array($ips))`
        // let through.
        $literal = trim($host, '[]');
        if (filter_var($literal, FILTER_VALIDATE_IP) !== false) {
            $ips = [$literal];
        } else {
            $ips = gethostbynamel($host) ?: [];
            foreach (@dns_get_record($host, DNS_AAAA) ?: [] as $record) {
                if (isset($record['ipv6'])) {
                    $ips[] = $record['ipv6'];
                }
            }
            if ($ips === []) {
                throw new \InvalidArgumentException('Could not resolve the host for this ' . $label);
            }
        }

        // Every resolved address, not just the first, so a rebinding answer that
        // mixes a public and a private address is refused on the private one.
        foreach ($ips as $ip) {
            if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false) {
                throw new \InvalidArgumentException('URLs pointing to private or reserved IP addresses are not allowed');
            }
        }
    }
}
