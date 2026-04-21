<?php

declare(strict_types=1);

namespace OCA\IntraVox\Service;

use OCP\Http\Client\IClientService;
use OCP\ICacheFactory;
use OCP\ICache;
use Psr\Log\LoggerInterface;
use Sabre\VObject\Component\VCalendar;
use Sabre\VObject\Reader;

/**
 * Fetches and parses external ICS calendar feeds.
 * Events are cached and returned in the same format as CalendarService.
 */
class ExternalIcsService {
    private const CACHE_TTL = 1800; // 30 minutes
    private const HTTP_TIMEOUT = 10;
    private const MAX_RESPONSE_SIZE = 1048576; // 1 MB

    private ?ICache $cache = null;

    public function __construct(
        private IClientService $httpClient,
        private ICacheFactory $cacheFactory,
        private LoggerInterface $logger,
    ) {
        if ($this->cacheFactory->isAvailable()) {
            $this->cache = $this->cacheFactory->createDistributed('intravox-ics');
        }
    }

    /**
     * Fetch events from an external ICS URL within a time range.
     *
     * @param string $url HTTPS URL to an ICS feed
     * @param \DateTimeImmutable $rangeStart
     * @param \DateTimeImmutable $rangeEnd
     * @return array List of events in CalendarService format
     */
    public function getEvents(string $url, \DateTimeImmutable $rangeStart, \DateTimeImmutable $rangeEnd): array {
        $this->validateUrl($url);

        $icsData = $this->fetchIcs($url);
        if ($icsData === null) {
            return [];
        }

        return $this->parseIcs($icsData, $rangeStart, $rangeEnd, $url);
    }

    /**
     * Fetch ICS data with caching.
     */
    private function fetchIcs(string $url): ?string {
        $cacheKey = 'ics_' . md5($url);

        if ($this->cache !== null) {
            $cached = $this->cache->get($cacheKey);
            if ($cached !== null) {
                return $cached;
            }
        }

        try {
            $client = $this->httpClient->newClient();
            $response = $client->get($url, [
                'timeout' => self::HTTP_TIMEOUT,
                'headers' => [
                    'Accept' => 'text/calendar, application/calendar+json',
                    'User-Agent' => 'IntraVox/1.0 (Nextcloud Calendar Widget)',
                ],
            ]);

            $body = $response->getBody();
            if (strlen($body) > self::MAX_RESPONSE_SIZE) {
                $this->logger->warning('IntraVox: ICS feed too large', ['url' => $this->maskUrl($url)]);
                return null;
            }

            if ($this->cache !== null) {
                $this->cache->set($cacheKey, $body, self::CACHE_TTL);
            }

            return $body;
        } catch (\Exception $e) {
            $this->logger->error('IntraVox: Failed to fetch ICS feed', [
                'url' => $this->maskUrl($url),
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Parse ICS data into event arrays, expanding recurring events.
     */
    private function parseIcs(string $icsData, \DateTimeImmutable $rangeStart, \DateTimeImmutable $rangeEnd, string $sourceUrl): array {
        try {
            $vcalendar = Reader::read($icsData);
            if (!$vcalendar instanceof VCalendar) {
                return [];
            }

            // Get calendar name and base URL for display/linking
            $calendarName = (string) ($vcalendar->{'X-WR-CALNAME'} ?? parse_url($sourceUrl, PHP_URL_HOST) ?? '');
            $sourceBaseUrl = parse_url($sourceUrl, PHP_URL_SCHEME) . '://' . parse_url($sourceUrl, PHP_URL_HOST);

            // Expand recurring events
            $vcalendar = $vcalendar->expand(
                new \DateTime($rangeStart->format('Y-m-d H:i:s'), new \DateTimeZone('UTC')),
                new \DateTime($rangeEnd->format('Y-m-d H:i:s'), new \DateTimeZone('UTC'))
            );

            $events = [];
            foreach ($vcalendar->VEVENT ?? [] as $vevent) {
                $eventClass = strtoupper((string) ($vevent->CLASS ?? 'PUBLIC'));
                if ($eventClass === 'PRIVATE' || $eventClass === 'CONFIDENTIAL') {
                    continue;
                }

                $event = [
                    'uid' => (string) ($vevent->UID ?? ''),
                    'summary' => (string) ($vevent->SUMMARY ?? ''),
                    'location' => (string) ($vevent->LOCATION ?? ''),
                    'url' => (string) ($vevent->URL ?? '') ?: $this->buildEventUrl($vevent, $sourceBaseUrl),
                    'calendarName' => $calendarName,
                    'calendarColor' => '#8B5CF6', // distinct purple for external feeds
                    'isExternal' => true,
                ];

                if ($vevent->DTSTART) {
                    $dt = $vevent->DTSTART->getDateTime();
                    $event['start'] = $dt->format('c');
                    $event['isAllDay'] = !$vevent->DTSTART->hasTime();
                }

                if ($vevent->DTEND) {
                    $event['end'] = $vevent->DTEND->getDateTime()->format('c');
                } elseif ($vevent->DURATION && isset($dt)) {
                    $event['end'] = $dt->add($vevent->DURATION->getDateInterval())->format('c');
                }

                $event['uid'] = $event['uid'] . '-' . ($event['start'] ?? '');
                $events[] = $event;
            }

            return $events;
        } catch (\Exception $e) {
            $this->logger->warning('IntraVox: Failed to parse ICS feed', [
                'url' => $this->maskUrl($sourceUrl),
                'error' => $e->getMessage(),
            ]);
            return [];
        }
    }

    /**
     * Try to build a direct event URL from the UID.
     *
     * Supported UID formats:
     * - Brightspace: "{orgUnitId}-{eventId}@{host}" → /d2l/le/calendar/{orgUnitId}/event/{eventId}/detailsview
     * - Moodle: "{eventId}@{host}" → /calendar/view.php?view=day#event_{eventId}
     * - Unknown: falls back to base URL of the ICS feed
     */
    private function buildEventUrl(\Sabre\VObject\Component\VEvent $vevent, string $sourceBaseUrl): string {
        $uid = (string) ($vevent->UID ?? '');
        $host = parse_url($sourceBaseUrl, PHP_URL_HOST) ?? '';

        // Brightspace: "6606-70434@hetrynow.brightspace.com"
        if (preg_match('/^(\d+)-(\d+)@(.+)$/', $uid, $m) && str_contains($m[3], $host)) {
            return $sourceBaseUrl . '/d2l/le/calendar/' . $m[1] . '/event/' . $m[2] . '/detailsview';
        }

        // Moodle: "6@moodle.rikdekker.nl"
        if (preg_match('/^(\d+)@(.+)$/', $uid, $m) && str_contains($m[2], $host)) {
            $timestamp = '';
            if ($vevent->DTSTART) {
                $timestamp = '&time=' . $vevent->DTSTART->getDateTime()->getTimestamp();
            }
            return $sourceBaseUrl . '/calendar/view.php?view=day' . $timestamp . '#event_' . $m[1];
        }

        return $sourceBaseUrl;
    }

    private function validateUrl(string $url): void {
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            throw new \InvalidArgumentException('Invalid ICS URL');
        }
        $scheme = parse_url($url, PHP_URL_SCHEME);
        if ($scheme !== 'https') {
            throw new \InvalidArgumentException('Only HTTPS URLs are allowed for ICS feeds');
        }

        // SSRF protection: block private/internal IP ranges
        $host = parse_url($url, PHP_URL_HOST);
        if ($host !== null) {
            $ips = gethostbynamel($host);
            if (is_array($ips)) {
                foreach ($ips as $ip) {
                    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false) {
                        throw new \InvalidArgumentException('URLs pointing to private or reserved IP addresses are not allowed');
                    }
                }
            }
        }
    }

    /**
     * Mask URL for logging (hide tokens in query strings).
     */
    private function maskUrl(string $url): string {
        $parsed = parse_url($url);
        return ($parsed['scheme'] ?? '') . '://' . ($parsed['host'] ?? '') . ($parsed['path'] ?? '') . '?***';
    }
}
