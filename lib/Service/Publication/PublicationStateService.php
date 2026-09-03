<?php
declare(strict_types=1);

namespace OCA\IntraVox\Service\Publication;

use OCA\IntraVox\Service\PublicationSettingsService;
use OCP\IConfig;
use OCP\IUserSession;

/**
 * The scheduling side of publication: given a page and its MetaVox fields, decide
 * its live publish state (draft/published/scheduled/expired) and whether to hide
 * it from readers. Extracted verbatim from PageService (cluster U, Phase 3).
 *
 * Evaluated live ("lazy") so a scheduled page flips to published the moment its
 * publish time passes — no cron. The MetaVox DB access it needs is delegated to
 * MetaVoxGateway; this class owns only the date/timezone logic and the
 * admin-configured field names.
 */
class PublicationStateService {

    public function __construct(
        private PublicationSettingsService $publicationSettings,
        private IConfig $config,
        private IUserSession $userSession,
        private MetaVoxGateway $metaVoxGateway,
    ) {
    }

    /**
     * Effective publication state of a single page, combining the manual
     * draft/published status with the admin-configured MetaVox publish/expiration
     * date fields. Returns 'published' | 'draft' | 'scheduled' | 'expired'.
     * Only 'published' is visible to readers; the other three are hidden from
     * them but shown to users with write permission.
     *
     * @param array      $page        Page array (needs 'status' and 'fileId')
     * @param array|null $metaForFile Pre-fetched MetaVox fields for this file
     *                                (fieldName => value); looked up on demand when null.
     */
    public function effectivePublishState(array $page, ?array $metaForFile = null): string {
        $manualDraft = ($page['status'] ?? 'published') === 'draft';

        $publishField = $this->publicationSettings->getPublishDateField();
        $expireField = $this->publicationSettings->getExpirationDateField();

        // No scheduling configured, or MetaVox unavailable → the manual
        // draft/published flag governs, exactly as before.
        if ((empty($publishField) && empty($expireField)) || !$this->metaVoxGateway->isMetaVoxAvailable()) {
            return $manualDraft ? 'draft' : 'published';
        }

        $meta = $metaForFile;
        if ($meta === null) {
            $fileId = $page['fileId'] ?? null;
            $meta = $fileId ? ($this->metaVoxGateway->getMetaVoxDataForFiles([$fileId])[$fileId] ?? []) : [];
        }

        // Interpret the publish/expire dates AND "now" in one consistent instance
        // timezone. The MetaVox datetime-local input stores a naive local time
        // (e.g. "2026-08-04T15:57:00", no zone); the editor entered it in their
        // local time. Comparing that against a UTC "now" was off by the UTC
        // offset, so a page could read "Scheduled" when it was already live.
        $tz = $this->publicationTimezone();
        $now = new \DateTime('now', $tz);

        // Resolve the configured date values (field names are admin-configurable
        // in the IntraVox settings and may differ or be empty).
        $publishAt = (!empty($publishField) && !empty($meta[$publishField]))
            ? $this->parseDateTime((string)$meta[$publishField], $tz) : null;
        $expireAt = (!empty($expireField) && !empty($meta[$expireField]))
            ? $this->parseDateTime((string)$meta[$expireField], $tz) : null;

        // WordPress-style model: a Publish-on DATE, when set, governs publication
        // and overrides the manual draft flag — so you never get the confusing
        // "Draft badge + past publish date" combination. The manual draft only
        // applies when no publish date is set.
        if ($publishAt !== null) {
            if ($publishAt > $now) {
                return 'scheduled'; // future → not live yet (draft flag ignored)
            }
            // publish date has passed → published, subject only to expiration below.
        } elseif ($manualDraft) {
            return 'draft'; // no publish date → manual draft holds it back
        }

        // Expiration applies regardless of how the page became published.
        if ($expireAt !== null && $expireAt <= $now) {
            return 'expired';
        }

        return 'published';
    }

    /**
     * Whether a page must be hidden from a viewer WITHOUT write permission.
     * True for draft, scheduled (future) and expired pages.
     *
     * @param array      $page
     * @param array|null $metaForFile Optional pre-fetched MetaVox fields (see
     *                                effectivePublishState) to avoid N+1 queries.
     */
    public function isHiddenFromReaders(array $page, ?array $metaForFile = null): bool {
        return $this->effectivePublishState($page, $metaForFile) !== 'published';
    }

    /**
     * Whether a page has an active publish/expiration date (from the configured
     * MetaVox fields). When true, that date governs publication and the manual
     * draft/published toggle is overridden — the editor UI uses this to explain
     * why the toggle is showing the effective state instead of the raw status.
     *
     * @param array      $page
     * @param array|null $metaForFile Optional pre-fetched MetaVox fields.
     */
    public function hasPublicationDate(array $page, ?array $metaForFile = null): bool {
        $publishField = $this->publicationSettings->getPublishDateField();
        $expireField = $this->publicationSettings->getExpirationDateField();
        if ((empty($publishField) && empty($expireField)) || !$this->metaVoxGateway->isMetaVoxAvailable()) {
            return false;
        }
        $meta = $metaForFile;
        if ($meta === null) {
            $fileId = $page['fileId'] ?? null;
            $meta = $fileId ? ($this->metaVoxGateway->getMetaVoxDataForFiles([$fileId])[$fileId] ?? []) : [];
        }
        return (!empty($publishField) && !empty($meta[$publishField]))
            || (!empty($expireField) && !empty($meta[$expireField]));
    }

    /**
     * Public batch accessor for MetaVox fields, so list-context callers (page
     * loading, tree, search) can fetch once and hand per-page metadata to
     * effectivePublishState()/isHiddenFromReaders() — avoiding an N+1 query.
     * Returns [] when scheduling is not configured or the file list is empty.
     *
     * @param int[] $fileIds
     * @return array<int, array<string, string>> fileId => [fieldName => value]
     */
    public function publicationMetaForFiles(array $fileIds): array {
        $publishField = $this->publicationSettings->getPublishDateField();
        $expireField = $this->publicationSettings->getExpirationDateField();
        if ((empty($publishField) && empty($expireField)) || empty($fileIds)) {
            return [];
        }
        return $this->metaVoxGateway->getMetaVoxDataForFiles(array_values(array_filter($fileIds)));
    }

    /**
     * Time-aware date/time parse for publication scheduling. Unlike parseDate()
     * (which truncates to Y-m-d and made "today 03:25" count as already
     * published), this preserves the time component so a same-day schedule is
     * respected to the minute.
     *
     * The MetaVox datetime-local input stores a NAIVE local time with no zone
     * (e.g. "2026-08-04T15:57:00"); such values are interpreted in $tz (the
     * instance timezone). Values that carry an explicit offset ("…Z" / "+02:00")
     * keep their own zone.
     *
     * @param string             $dateStr
     * @param \DateTimeZone|null  $tz Timezone for naive values (default: instance).
     * @return \DateTime|null Parsed date/time, or null if unparseable.
     */
    private function parseDateTime(string $dateStr, ?\DateTimeZone $tz = null): ?\DateTime {
        $dateStr = trim($dateStr);
        if ($dateStr === '') {
            return null;
        }
        $tz = $tz ?? $this->publicationTimezone();

        $formats = [
            'Y-m-d\TH:i:s',   // ISO 8601 (naive): 2025-01-15T14:30:00
            'Y-m-d\TH:i',     // datetime-local input: 2025-01-15T14:30
            'Y-m-d H:i:s',    // 2025-01-15 14:30:00
            'Y-m-d H:i',      // 2025-01-15 14:30
            'd-m-Y H:i:s',    // European with time
            'd-m-Y H:i',
            'Y-m-d',          // date only → midnight
            'd-m-Y',
            'm/d/Y',
            'd/m/Y',
            'Y/m/d',
        ];

        // If the value carries an explicit zone/offset, honour it (don't force $tz).
        if (preg_match('/(Z|[+-]\d{2}:?\d{2})$/', $dateStr)) {
            try {
                return new \DateTime($dateStr);
            } catch (\Exception $e) {
                // fall through to format parsing
            }
        }

        foreach ($formats as $format) {
            // Parse naive values IN the instance timezone so the comparison
            // against "now" (also in $tz) is apples-to-apples.
            $date = \DateTime::createFromFormat($format, $dateStr, $tz);
            if ($date !== false) {
                // For date-only formats, createFromFormat keeps the current time;
                // normalise those to start-of-day so "publish on <date>" means
                // 00:00 of that day.
                if (!str_contains($format, 'H')) {
                    $date->setTime(0, 0, 0);
                }
                return $date;
            }
        }

        $timestamp = strtotime($dateStr);
        if ($timestamp !== false) {
            return (new \DateTime('now', $tz))->setTimestamp($timestamp);
        }

        return null;
    }

    /**
     * The timezone in which naive publication dates and "now" are compared.
     * Prefers an explicit instance timezone (NC system `logtimezone`), then the
     * current user's Nextcloud timezone (intranet ≈ org timezone), then the
     * server default. Consistent for logged-in users and anonymous visitors.
     */
    private function publicationTimezone(): \DateTimeZone {
        // 1. Admin-set instance timezone.
        $sys = (string)$this->config->getSystemValue('logtimezone', '');
        if ($sys !== '') {
            try { return new \DateTimeZone($sys); } catch (\Exception $e) {}
        }
        // 2. Current user's NC timezone (empty for anonymous share visitors).
        $uid = $this->userSession->getUser()?->getUID();
        if ($uid) {
            $userTz = (string)$this->config->getUserValue($uid, 'core', 'timezone', '');
            if ($userTz !== '') {
                try { return new \DateTimeZone($userTz); } catch (\Exception $e) {}
            }
        }
        // 3. Server default (PHP date.timezone).
        try {
            return new \DateTimeZone(date_default_timezone_get());
        } catch (\Exception $e) {
            return new \DateTimeZone('UTC');
        }
    }
}
