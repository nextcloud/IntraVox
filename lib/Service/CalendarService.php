<?php

declare(strict_types=1);

namespace OCA\IntraVox\Service;

use OCA\DAV\CalDAV\CalDavBackend;
use Psr\Log\LoggerInterface;
use Sabre\VObject\Component\VCalendar;
use Sabre\VObject\Reader;

class CalendarService {
    public function __construct(
        private CalDavBackend $calDavBackend,
        private LoggerInterface $logger,
    ) {
    }

    /**
     * Get all calendars for a user (personal + shared)
     */
    public function getCalendarsForUser(string $userId): array {
        $principalUri = 'principals/users/' . $userId;
        $calendars = $this->calDavBackend->getCalendarsForUser($principalUri);

        return array_map(function ($calendar) {
            return [
                'id' => (int) $calendar['id'],
                'uri' => $calendar['uri'],
                'displayName' => $calendar['{DAV:}displayname'] ?? $calendar['uri'],
                'color' => self::sanitizeColor($calendar['{http://apple.com/ns/ical/}calendar-color'] ?? '#0082c9'),
                'isReadOnly' => isset($calendar['{http://owncloud.org/ns}read-only']) && $calendar['{http://owncloud.org/ns}read-only'],
            ];
        }, $calendars);
    }

    /**
     * Validate and sanitize a color value to prevent CSS injection
     */
    private static function sanitizeColor(string $color): string {
        // Accept hex colors (#rgb, #rrggbb, #rrggbbaa)
        if (preg_match('/^#[0-9a-fA-F]{3,8}$/', $color)) {
            return $color;
        }
        return '#0082c9';
    }

    /**
     * Get events from multiple calendars within a time range, merged and sorted
     *
     * @param string $userId
     * @param int[] $calendarIds
     * @param \DateTimeImmutable $rangeStart
     * @param \DateTimeImmutable $rangeEnd
     * @param int $limit
     * @return array
     */
    public function getEvents(string $userId, array $calendarIds, \DateTimeImmutable $rangeStart, \DateTimeImmutable $rangeEnd, int $limit = 5): array {
        // Build a map of calendarId => color for enriching events
        $calendarMap = [];
        $userCalendars = $this->getCalendarsForUser($userId);
        foreach ($userCalendars as $cal) {
            $calendarMap[$cal['id']] = $cal;
        }

        // Validate that user has access to all requested calendars
        $validCalendarIds = array_filter($calendarIds, function ($id) use ($calendarMap) {
            return isset($calendarMap[$id]);
        });

        if (empty($validCalendarIds)) {
            return [];
        }

        $allEvents = [];

        foreach ($validCalendarIds as $calendarId) {
            $events = $this->getCalendarEvents($calendarId, $rangeStart, $rangeEnd);
            $calendarColor = $calendarMap[$calendarId]['color'] ?? '#0082c9';
            $calendarName = $calendarMap[$calendarId]['displayName'] ?? '';

            foreach ($events as &$event) {
                $event['calendarColor'] = $calendarColor;
                $event['calendarName'] = $calendarName;
            }

            $allEvents = array_merge($allEvents, $events);
        }

        // Sort by start date ascending
        usort($allEvents, function ($a, $b) {
            $startA = $a['start'] ?? '';
            $startB = $b['start'] ?? '';
            return strcmp($startA, $startB);
        });

        // Apply limit
        return array_slice($allEvents, 0, $limit);
    }

    /**
     * Get events from a single calendar using calendarQuery with time-range filter.
     * Recurring events are expanded into individual occurrences.
     */
    private function getCalendarEvents(int $calendarId, \DateTimeImmutable $rangeStart, \DateTimeImmutable $rangeEnd): array {
        $filters = [
            'name' => 'VCALENDAR',
            'comp-filters' => [
                [
                    'name' => 'VEVENT',
                    'comp-filters' => [],
                    'prop-filters' => [],
                    'is-not-defined' => false,
                    'time-range' => [
                        'start' => $rangeStart,
                        'end' => $rangeEnd,
                    ],
                ],
            ],
            'prop-filters' => [],
            'is-not-defined' => false,
            'time-range' => null,
        ];

        try {
            $uris = $this->calDavBackend->calendarQuery($calendarId, $filters);
        } catch (\Exception $e) {
            $this->logger->error('IntraVox: Failed to query calendar', [
                'calendarId' => $calendarId,
                'error' => $e->getMessage(),
            ]);
            return [];
        }

        $events = [];
        foreach ($uris as $uri) {
            $eventData = $this->calDavBackend->getCalendarObject($calendarId, $uri);
            if ($eventData === null) {
                continue;
            }

            $parsed = $this->parseEvent($eventData, $rangeStart, $rangeEnd);
            foreach ($parsed as $event) {
                $events[] = $event;
            }
        }

        return $events;
    }

    /**
     * Parse iCalendar data into event arrays.
     * Recurring events are expanded into individual occurrences within the time range.
     *
     * @return array[] Array of event arrays (multiple for recurring events)
     */
    private function parseEvent(array $eventData, \DateTimeImmutable $rangeStart, \DateTimeImmutable $rangeEnd): array {
        try {
            $vcalendar = Reader::read($eventData['calendardata']);
            if (!$vcalendar instanceof VCalendar) {
                return [];
            }

            // Expand recurring events into individual occurrences
            $vcalendar = $vcalendar->expand(
                new \DateTime($rangeStart->format('Y-m-d H:i:s'), new \DateTimeZone('UTC')),
                new \DateTime($rangeEnd->format('Y-m-d H:i:s'), new \DateTimeZone('UTC'))
            );

            $events = [];
            foreach ($vcalendar->VEVENT ?? [] as $vevent) {
                // Skip private/confidential events
                $eventClass = strtoupper((string) ($vevent->CLASS ?? 'PUBLIC'));
                if ($eventClass === 'PRIVATE' || $eventClass === 'CONFIDENTIAL') {
                    continue;
                }

                $event = [
                    'uid' => (string) ($vevent->UID ?? ''),
                    'summary' => (string) ($vevent->SUMMARY ?? ''),
                    'location' => (string) ($vevent->LOCATION ?? ''),
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

                // Add unique key for recurring occurrences (uid + start date)
                $event['uid'] = $event['uid'] . '-' . ($event['start'] ?? '');

                $events[] = $event;
            }

            return $events;
        } catch (\Exception $e) {
            $this->logger->warning('IntraVox: Failed to parse calendar event', [
                'error' => $e->getMessage(),
            ]);
            return [];
        }
    }
}
