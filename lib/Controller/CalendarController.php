<?php

declare(strict_types=1);

namespace OCA\IntraVox\Controller;

use OCA\IntraVox\Service\CalendarService;
use OCA\IntraVox\Service\PublicShareService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\DataResponse;
use OCP\IRequest;
use Psr\Log\LoggerInterface;

class CalendarController extends Controller {
    public function __construct(
        string $appName,
        IRequest $request,
        private CalendarService $calendarService,
        private PublicShareService $publicShareService,
        private LoggerInterface $logger,
        private ?string $userId = null,
    ) {
        parent::__construct($appName, $request);
    }

    /**
     * Get available calendars for the current user
     *
     * @NoAdminRequired
     * @NoCSRFRequired
     *
     * @return DataResponse
     */
    public function getCalendars(): DataResponse {
        if ($this->userId === null) {
            return new DataResponse(
                ['error' => 'Authentication required'],
                Http::STATUS_UNAUTHORIZED
            );
        }

        try {
            $calendars = $this->calendarService->getCalendarsForUser($this->userId);

            return new DataResponse([
                'calendars' => $calendars,
            ]);
        } catch (\Exception $e) {
            $this->logger->error('IntraVox: Error getting calendars', [
                'error' => $e->getMessage(),
            ]);
            return new DataResponse(
                ['error' => 'Failed to get calendars'],
                Http::STATUS_INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     * Get events from one or more calendars
     *
     * @NoAdminRequired
     * @NoCSRFRequired
     *
     * @return DataResponse
     */
    public function getEvents(): DataResponse {
        if ($this->userId === null) {
            return new DataResponse(
                ['error' => 'Authentication required'],
                Http::STATUS_UNAUTHORIZED
            );
        }

        try {
            $calendarIdsParam = $this->request->getParam('calendarIds', '');
            $rangeStart = $this->request->getParam('rangeStart', '');
            $rangeEnd = $this->request->getParam('rangeEnd', '');
            $limit = (int) $this->request->getParam('limit', 5);

            // Parse external ICS URLs
            $externalIcsUrls = $this->parseExternalIcsUrls($this->request->getParam('externalIcsUrls', ''));

            if (empty($calendarIdsParam) && empty($externalIcsUrls)) {
                return new DataResponse(['events' => []]);
            }

            // Parse calendar keys (string identifiers)
            $calendarIds = array_filter(explode(',', (string) $calendarIdsParam), fn($s) => $s !== '');
            $limit = min(max($limit, 1), 20);

            // Validate date parameters
            try {
                $start = new \DateTimeImmutable($rangeStart ?: 'now');
                $end = new \DateTimeImmutable($rangeEnd ?: '+30 days');
            } catch (\Exception $e) {
                return new DataResponse(
                    ['error' => 'Invalid date format'],
                    Http::STATUS_BAD_REQUEST
                );
            }

            // Cap date range to 1 year max to prevent excessive recurring event expansion
            $maxEnd = $start->modify('+1 year');
            if ($end > $maxEnd) {
                $end = $maxEnd;
            }

            $events = $this->calendarService->getEvents($this->userId, $calendarIds, $start, $end, $limit, $externalIcsUrls);

            return new DataResponse([
                'events' => $events,
            ]);
        } catch (\Exception $e) {
            $this->logger->error('IntraVox: Error getting calendar events', [
                'error' => $e->getMessage(),
            ]);
            return new DataResponse(
                ['error' => 'Failed to get calendar events'],
                Http::STATUS_INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     * Get events via public share link
     *
     * @PublicPage
     * @NoCSRFRequired
     *
     * @param string $token Share token
     * @return DataResponse
     */
    public function getEventsByShare(string $token): DataResponse {
        try {
            // Validate share token
            $share = $this->publicShareService->getShareByToken($token);
            if ($share === null) {
                return new DataResponse(
                    ['error' => 'Invalid or expired share token'],
                    Http::STATUS_FORBIDDEN
                );
            }

            $calendarIdsParam = $this->request->getParam('calendarIds', '');
            $rangeStart = $this->request->getParam('rangeStart', '');
            $rangeEnd = $this->request->getParam('rangeEnd', '');
            $limit = (int) $this->request->getParam('limit', 5);

            // Parse external ICS URLs
            $externalIcsUrls = $this->parseExternalIcsUrls($this->request->getParam('externalIcsUrls', ''));

            if (empty($calendarIdsParam) && empty($externalIcsUrls)) {
                return new DataResponse(['events' => []]);
            }

            // Parse calendar keys (string identifiers)
            $calendarIds = array_filter(explode(',', (string) $calendarIdsParam), fn($s) => $s !== '');
            $limit = min(max($limit, 1), 20);

            // Validate date parameters
            try {
                $start = new \DateTimeImmutable($rangeStart ?: 'now');
                $end = new \DateTimeImmutable($rangeEnd ?: '+30 days');
            } catch (\Exception $e) {
                return new DataResponse(
                    ['error' => 'Invalid date format'],
                    Http::STATUS_BAD_REQUEST
                );
            }

            // Cap date range to 1 year max
            $maxEnd = $start->modify('+1 year');
            if ($end > $maxEnd) {
                $end = $maxEnd;
            }

            // Use the share owner's context to fetch calendar events
            $ownerId = $share->getShareOwner();
            if ($ownerId === null) {
                return new DataResponse(
                    ['error' => 'Could not determine share owner'],
                    Http::STATUS_INTERNAL_SERVER_ERROR
                );
            }

            $events = $this->calendarService->getEvents($ownerId, $calendarIds, $start, $end, $limit, $externalIcsUrls);

            return new DataResponse([
                'events' => $events,
            ]);
        } catch (\Exception $e) {
            $this->logger->error('IntraVox: Error getting calendar events by share', [
                'token' => substr($token, 0, 8) . '...',
                'error' => $e->getMessage(),
            ]);
            return new DataResponse(
                ['error' => 'Failed to get calendar events'],
                Http::STATUS_INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     * Parse and validate external ICS URLs from request parameter.
     *
     * @return string[] Valid HTTPS URLs (max 5)
     */
    private function parseExternalIcsUrls(string $param): array {
        if (empty($param)) {
            return [];
        }

        $urls = json_decode($param, true);
        if (!is_array($urls)) {
            return [];
        }

        $valid = [];
        foreach (array_slice($urls, 0, 5) as $url) {
            if (is_string($url) && filter_var($url, FILTER_VALIDATE_URL) && parse_url($url, PHP_URL_SCHEME) === 'https') {
                $valid[] = $url;
            }
        }

        return $valid;
    }
}
