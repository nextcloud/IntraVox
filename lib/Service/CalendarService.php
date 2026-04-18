<?php

declare(strict_types=1);

namespace OCA\IntraVox\Service;

use OCP\Calendar\IManager;
use OCP\Calendar\ICalendar;
use Psr\Log\LoggerInterface;

class CalendarService {
    public function __construct(
        private IManager $calendarManager,
        private ExternalIcsService $externalIcsService,
        private LoggerInterface $logger,
    ) {
    }

    /**
     * Get all calendars for a user (personal + shared, excluding ICS subscriptions).
     * ICS subscriptions are handled separately via the external ICS URL feature.
     */
    public function getCalendarsForUser(string $userId): array {
        $principalUri = 'principals/users/' . $userId;
        $calendars = $this->calendarManager->getCalendarsForPrincipal($principalUri);

        return array_values(array_filter(array_map(function (ICalendar $calendar) {
            if ($calendar->isDeleted()) {
                return null;
            }
            if (method_exists($calendar, 'isEnabled') && $calendar->isEnabled() === false) {
                return null;
            }
            // Hide ICS subscriptions — these are managed via the external ICS URL field instead
            if (str_contains(get_class($calendar), 'Subscription')) {
                return null;
            }

            return [
                'id' => $calendar->getKey(),
                'uri' => $calendar->getUri(),
                'displayName' => $calendar->getDisplayName() ?? $calendar->getUri(),
                'color' => self::sanitizeColor($calendar->getDisplayColor() ?? '#0082c9'),
                'isReadOnly' => $calendar->getPermissions() === 1,
            ];
        }, $calendars)));
    }

    /**
     * Validate and sanitize a color value to prevent CSS injection
     */
    private static function sanitizeColor(string $color): string {
        if (preg_match('/^#[0-9a-fA-F]{3,8}$/', $color)) {
            return $color;
        }
        return '#0082c9';
    }

    /**
     * Get events from multiple calendars within a time range, merged and sorted.
     * Works with both regular calendars and ICS subscriptions.
     *
     * @param string $userId
     * @param string[] $calendarKeys Calendar keys (from getKey())
     * @param \DateTimeImmutable $rangeStart
     * @param \DateTimeImmutable $rangeEnd
     * @param int $limit
     * @return array
     */
    public function getEvents(string $userId, array $calendarKeys, \DateTimeImmutable $rangeStart, \DateTimeImmutable $rangeEnd, int $limit = 5, array $externalIcsUrls = []): array {
        $principalUri = 'principals/users/' . $userId;
        $calendars = $this->calendarManager->getCalendarsForPrincipal($principalUri);

        // Build map of key => ICalendar
        $calendarMap = [];
        foreach ($calendars as $calendar) {
            $calendarMap[$calendar->getKey()] = $calendar;
        }

        $allEvents = [];

        foreach ($calendarKeys as $key) {
            if (!isset($calendarMap[$key])) {
                continue;
            }

            $calendar = $calendarMap[$key];
            if ($calendar->isDeleted()) {
                continue;
            }

            try {
                $results = $calendar->search('', [], [
                    'timerange' => [
                        'start' => $rangeStart,
                        'end' => $rangeEnd,
                    ],
                ], $limit * 3);

                $calendarColor = self::sanitizeColor($calendar->getDisplayColor() ?? '#0082c9');
                $calendarName = $calendar->getDisplayName() ?? '';

                foreach ($results as $result) {
                    foreach ($result['objects'] as $object) {
                        $events = $this->parseSearchResult($object, $calendarColor, $calendarName, $rangeStart, $rangeEnd);
                        $allEvents = array_merge($allEvents, $events);
                    }
                }
            } catch (\Exception $e) {
                $this->logger->error('IntraVox: Failed to search calendar', [
                    'calendarKey' => $key,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // Fetch events from external ICS feeds
        foreach ($externalIcsUrls as $icsUrl) {
            try {
                $icsEvents = $this->externalIcsService->getEvents($icsUrl, $rangeStart, $rangeEnd);
                $allEvents = array_merge($allEvents, $icsEvents);
            } catch (\Exception $e) {
                $this->logger->warning('IntraVox: Failed to fetch external ICS', [
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // Sort by start date ascending
        usort($allEvents, fn($a, $b) => strcmp($a['start'] ?? '', $b['start'] ?? ''));

        return array_slice($allEvents, 0, $limit);
    }

    /**
     * Parse a search result object into event arrays.
     */
    private function parseSearchResult(array $object, string $calendarColor, string $calendarName, \DateTimeImmutable $rangeStart, \DateTimeImmutable $rangeEnd): array {
        $events = [];

        $uid = (string) ($object['UID'][0] ?? '');
        $summary = (string) ($object['SUMMARY'][0] ?? '');
        $location = (string) ($object['LOCATION'][0] ?? '');

        // Skip private/confidential events
        $class = strtoupper((string) ($object['CLASS'][0] ?? 'PUBLIC'));
        if ($class === 'PRIVATE' || $class === 'CONFIDENTIAL') {
            return [];
        }

        // Handle DTSTART — can be a DateTime or an array of DateTimes for recurring events
        $dtStarts = $object['DTSTART'] ?? [];
        if (empty($dtStarts)) {
            return [];
        }

        // For non-recurring events, wrap in array
        if (!is_array($dtStarts) || $dtStarts instanceof \DateTimeInterface) {
            $dtStarts = [$dtStarts];
        }

        $dtEnds = $object['DTEND'] ?? [];
        if (!is_array($dtEnds) || $dtEnds instanceof \DateTimeInterface) {
            $dtEnds = [$dtEnds];
        }

        // The search result may return a single occurrence or multiple
        // For single events: DTSTART is [DateTime, params]
        // We need to handle both formats
        $start = null;
        $end = null;
        $isAllDay = false;

        if (isset($dtStarts[0]) && $dtStarts[0] instanceof \DateTimeInterface) {
            $start = $dtStarts[0];
            $isAllDay = isset($dtStarts[1]['VALUE']) && $dtStarts[1]['VALUE'] === 'DATE';
        } elseif (isset($dtStarts[0]) && is_string($dtStarts[0])) {
            try {
                $start = new \DateTimeImmutable($dtStarts[0]);
            } catch (\Exception $e) {
                return [];
            }
        }

        if ($start === null) {
            return [];
        }

        if (isset($dtEnds[0]) && $dtEnds[0] instanceof \DateTimeInterface) {
            $end = $dtEnds[0];
        } elseif (isset($dtEnds[0]) && is_string($dtEnds[0])) {
            try {
                $end = new \DateTimeImmutable($dtEnds[0]);
            } catch (\Exception $e) {
                // no end time
            }
        }

        // Filter to time range
        if ($start > $rangeEnd || ($end !== null && $end < $rangeStart)) {
            return [];
        }

        $event = [
            'uid' => $uid . '-' . $start->format('c'),
            'summary' => $summary,
            'location' => $location,
            'start' => $start->format('c'),
            'isAllDay' => $isAllDay,
            'calendarColor' => $calendarColor,
            'calendarName' => $calendarName,
        ];

        if ($end !== null) {
            $event['end'] = $end->format('c');
        }

        $events[] = $event;
        return $events;
    }
}
