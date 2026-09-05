<?php
declare(strict_types=1);

namespace OCA\IntraVox\Service\News;

use OCA\IntraVox\Service\Locator\PageLocator;
use OCA\IntraVox\Service\PermissionService;
use OCA\IntraVox\Service\Path\PagePathHelper;
use OCP\Files\Folder;
use Psr\Log\LoggerInterface;

/**
 * The News widget's domain service, extracted from PageService (service
 * split, PR-18): the recursive collect walk that turns page folders into
 * news items, the source-folder discovery behind the widget's folder
 * picker, the MetaVox filter engine, and the publication filter.
 *
 * The split's standing pattern holds: PageService LOCATES (which language
 * folder, which source page/folder, the IntraVox root for relative paths)
 * and owns the news result cache; this service walks and filters the nodes
 * it is handed.
 *
 * Two collaborations are passed IN rather than depended on, because they
 * belong to concerns wider than news:
 *   - the publication gate (effectivePublishState + the metadata prefetch)
 *     is shared with the tree, search and single-page reads, so it stays in
 *     PageService and arrives here as callables;
 *   - MetaVox field VALUES likewise, since the same lookup feeds search.
 * That keeps this service about news, not about republishing those rules.
 */
class NewsPageService {
    private PageLocator $locator;
    private PermissionService $permissionService;
    private NewsContentExtractor $newsContent;
    private LoggerInterface $logger;

    public function __construct(
        PageLocator $locator,
        PermissionService $permissionService,
        NewsContentExtractor $newsContent,
        LoggerInterface $logger
    ) {
        $this->locator = $locator;
        $this->permissionService = $permissionService;
        $this->newsContent = $newsContent;
        $this->logger = $logger;
    }

    /**
     * Recursively find news pages in a folder
     *
     * @param Folder $root      IntraVox root, for the items' relative paths
     * @param int    $maxCollect Hard cap on items to collect (0 = unlimited)
     */
    public function findNewsPagesInFolder(Folder $root, $folder, array &$pages, string $language, int $maxCollect = 0): void {
        foreach ($this->locator->cachedDirectoryListing($folder) as $item) {
            // Early-exit when we have collected enough items
            if ($maxCollect > 0 && count($pages) >= $maxCollect) {
                return;
            }
            if ($item->getType() !== \OCP\Files\FileInfo::TYPE_FOLDER) {
                continue;
            }

            $folderName = $item->getName();

            // Skip special folders
            if (PagePathHelper::isInfrastructureFolder($folderName)) {
                continue;
            }

            // Look for {foldername}.json inside the folder
            try {
                $jsonFile = $item->get($folderName . '.json');

                if (!$jsonFile->isReadable()) {
                    continue;
                }

                $content = $jsonFile instanceof \OCP\Files\File
                    ? $this->locator->cachedFileContent($jsonFile)
                    : @$jsonFile->getContent();

                if ($content === false || $content === null) {
                    continue;
                }

                $data = json_decode($content, true);

                if ($data && isset($data['uniqueId'], $data['title'])) {
                    // Get folder permissions
                    $folderPerms = $this->permissionService->getNodePermissions($item);

                    // Skip if user can't read
                    if (($folderPerms & 1) === 0) {
                        continue;
                    }

                    // Extract excerpt from first text widget
                    $excerpt = $this->newsContent->getExcerpt($data, 150);

                    // Find first image
                    $imageData = $this->newsContent->getFirstImage($data);
                    $imagePath = $this->imagePathFor($imageData, $data['uniqueId']);

                    // Build relative path
                    $relativePath = $this->locator->relativePathFromRoot($root, $item);

                    // Get file modification time
                    $modified = $jsonFile->getMTime();

                    // Format modified date in user's locale
                    $modifiedFormatted = $this->formatDateLocalized($modified, $language);

                    $pages[] = [
                        'uniqueId' => $data['uniqueId'],
                        'title' => $data['title'],
                        // Needed by the publication gate: without it every item
                        // looked "published" and drafts leaked into News lists.
                        'status' => $data['status'] ?? 'published',
                        'excerpt' => $excerpt,
                        'image' => $imageData ? $imageData['src'] : null,
                        'imagePath' => $imagePath,
                        'modified' => $modified,
                        'modifiedFormatted' => $modifiedFormatted,
                        'path' => $relativePath,
                        'fileId' => $jsonFile->getId(),
                        'permissions' => [
                            'canRead' => ($folderPerms & 1) !== 0,
                            // AND with the node capability so a read-only GroupFolder
                            // member is reported correctly (issue #70), consistent
                            // with permissionsFromNode().
                            'canWrite' => ($folderPerms & 2) !== 0 && $item->isUpdateable(),
                            'raw' => $folderPerms
                        ]
                    ];
                }
            } catch (\Exception $e) {
                // This folder doesn't contain a valid page
            } catch (\Throwable $e) {
                continue;
            }

            // Recursively search subfolders
            $this->findNewsPagesInFolder($root, $item, $pages, $language, $maxCollect);

            // Re-check limit after recursion to avoid scanning more siblings
            if ($maxCollect > 0 && count($pages) >= $maxCollect) {
                return;
            }
        }
    }

    /**
     * The news item for the widget's own source page, prepended to the list
     * so the picked page appears above the pages beneath it.
     *
     * Returns null when the page is unreadable for this user or cannot be
     * decoded — the widget then simply lists its children, which is what the
     * pre-split inline try/catch did.
     */
    public function buildSourcePageItem(array $sourcePageData, string $language): ?array {
        try {
            $content = $sourcePageData['file']->getContent();
            $data = json_decode($content, true);

            if (!$data || !isset($data['uniqueId'], $data['title'])) {
                return null;
            }

            // Get folder permissions
            $folderPerms = $this->permissionService->getNodePermissions($sourcePageData['folder']);

            // Only add if user can read
            if (($folderPerms & 1) === 0) {
                return null;
            }

            $excerpt = $this->newsContent->getExcerpt($data, 150);
            $imageData = $this->newsContent->getFirstImage($data);
            $imagePath = $this->imagePathFor($imageData, $data['uniqueId']);

            return [
                'uniqueId' => $data['uniqueId'],
                'title' => $data['title'],
                'status' => $data['status'] ?? 'published',
                'excerpt' => $excerpt,
                'image' => $imageData ? $imageData['src'] : null,
                'imagePath' => $imagePath,
                'modified' => $sourcePageData['file']->getMTime(),
                'modifiedFormatted' => $this->formatDateLocalized($sourcePageData['file']->getMTime(), $language),
                'path' => $sourcePageData['folder']->getPath(),
                'fileId' => $sourcePageData['file']->getId(),
            ];
        } catch (\Exception $e) {
            $this->logger->debug('News widget: Could not add source page to results', ['error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * The API path a news tile loads its hero image from: the shared
     * `_resources` library or the page's own `_media`, matching where the
     * widget stored it. Null when the page has no image.
     */
    private function imagePathFor(?array $imageData, string $uniqueId): ?string {
        if (!$imageData) {
            return null;
        }
        if (($imageData['mediaFolder'] ?? 'page') === 'resources') {
            return '/apps/intravox/api/resources/media/' . $imageData['src'];
        }
        return '/apps/intravox/api/pages/' . $uniqueId . '/media/' . $imageData['src'];
    }

    /**
     * Sort collected items and cut them to the widget's limit.
     *
     * @param string $sortBy    'title' or anything else (= modified)
     * @param string $sortOrder 'asc' or anything else (= desc)
     */
    public function sortAndLimit(array $pages, string $sortBy, string $sortOrder, int $limit): array {
        // Sort pages
        usort($pages, function($a, $b) use ($sortBy, $sortOrder) {
            if ($sortBy === 'title') {
                $cmp = strcasecmp($a['title'] ?? '', $b['title'] ?? '');
            } else {
                // Default: sort by modified
                $cmp = ($a['modified'] ?? 0) - ($b['modified'] ?? 0);
            }
            return $sortOrder === 'asc' ? $cmp : -$cmp;
        });

        // Limit results
        return array_slice($pages, 0, $limit);
    }

    /**
     * Apply MetaVox filters to pages
     *
     * @param array    $pages       Pages to filter
     * @param array    $filters     Filter definitions
     * @param string   $operator    'AND' or 'OR'
     * @param callable $fetchMetaVox fn(array $fileIds): array — fileId => (field => value).
     *   Injected because the same lookup serves search; see the class docblock.
     * @return array Filtered pages
     */
    public function applyMetaVoxFilters(array $pages, array $filters, string $operator, callable $fetchMetaVox): array {
        if (empty($filters)) {
            return $pages;
        }

        // Get file IDs from pages
        $fileIds = array_filter(array_column($pages, 'fileId'));
        if (empty($fileIds)) {
            return $pages;
        }

        // Fetch MetaVox data for all file IDs
        $metaVoxData = $fetchMetaVox($fileIds);

        // Filter pages based on MetaVox values
        return array_filter($pages, function($page) use ($filters, $operator, $metaVoxData) {
            $fileId = $page['fileId'] ?? null;
            if (!$fileId) {
                return $operator === 'OR'; // No fileId = no match for AND, possible match for OR
            }

            $meta = $metaVoxData[$fileId] ?? [];
            $results = [];

            foreach ($filters as $filter) {
                $fieldName = $filter['fieldName'] ?? '';
                $filterOperator = $filter['operator'] ?? 'equals';
                $filterValue = $filter['value'] ?? '';
                $filterValues = $filter['values'] ?? [];
                $actualValue = $meta[$fieldName] ?? null;

                // Use values array for operators that work with multiple values
                if (in_array($filterOperator, ['in', 'contains', 'contains_all']) && !empty($filterValues)) {
                    $filterValue = $filterValues;
                }

                $results[] = $this->matchesFilter($actualValue, $filterOperator, $filterValue);
            }

            if (empty($results)) {
                return true;
            }

            return $operator === 'AND'
                ? !in_array(false, $results, true)
                : in_array(true, $results, true);
        });
    }

    /**
     * The separator MetaVox joins a multiselect's chosen options with.
     *
     * Its own API takes an array and stores implode(';#', $value); every read
     * path in MetaVox explodes on the same token. IntraVox reads the
     * metavox_file_gf_meta table directly (there is no bulk endpoint for many
     * files), so it inherits the job of decoding this itself -- which is what
     * PhotoStoryService::splitMultiselect() already does.
     */
    private const MULTISELECT_DELIMITER = ';#';

    /**
     * Split a stored MetaVox value into the options it actually holds.
     *
     * A single-choice value has no separator and comes back as a one-element
     * list, so callers do not have to care which kind of field they have.
     *
     * @return array<int, string>
     */
    private function splitMultiselect(string $raw): array {
        $out = [];
        foreach (explode(self::MULTISELECT_DELIMITER, $raw) as $part) {
            $part = trim($part);
            if ($part !== '') {
                $out[] = $part;
            }
        }
        return $out;
    }

    /**
     * Whether the value holds at least one of the wanted options.
     *
     * Both sides may be lists: the stored value after a multiselect is decoded,
     * and the filter value whenever the editor wrote a values[] array. A plain
     * text field keeps its substring behaviour, which is what "contains" means
     * there.
     *
     * @param mixed $value       decoded stored value
     * @param mixed $filterValue single wanted value, or a list of them
     */
    private function containsAny($value, $filterValue): bool {
        $wanted = is_array($filterValue) ? array_values($filterValue) : [$filterValue];
        if ($wanted === []) {
            return false;
        }

        foreach ($wanted as $needle) {
            if (is_array($needle)) {
                continue;
            }

            if (is_array($value)) {
                if (in_array($needle, $value)) {
                    return true;
                }
                continue;
            }

            // Substring match keeps a text field working as before.
            if (is_string($value) && $needle !== '' && str_contains($value, (string)$needle)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if a value matches a filter
     *
     * MetaVox hands us the stored string, never an array: a multiselect
     * arrives as "Nieuws;#Intern". The array branches below were therefore
     * unreachable, and the widget's own default operator for a multiselect
     * field ('contains') fed that array straight into str_contains(), which is
     * a TypeError on PHP 8 -- the 500 in #111. 'in' and 'contains_all' did not
     * crash but silently returned false, so a correctly configured widget
     * showed "no news". Decoding the value up front is what makes all three
     * agree with what the editor promises.
     */
    public function matchesFilter($value, string $operator, $filterValue): bool {
        // Only the set-membership operators care about the individual options;
        // the date, number and checkbox ones want the raw stored value.
        if (is_string($value)
            && in_array($operator, ['contains', 'not_contains', 'in', 'contains_all'], true)
            && str_contains($value, self::MULTISELECT_DELIMITER)) {
            $value = $this->splitMultiselect($value);
        }

        switch ($operator) {
            // Text/general operators
            case 'equals':
                return $value === $filterValue;
            case 'contains':
                // "any of the chosen options is present". The editor writes a
                // values[] array for a multiselect field, so both sides can be
                // lists; comparing the whole array with in_array() matched
                // nothing, and str_contains() with an array needle was the
                // TypeError behind #111.
                return $this->containsAny($value, $filterValue);
            case 'not_contains':
                return !$this->containsAny($value, $filterValue);
            case 'in':
                $allowedValues = is_array($filterValue) ? $filterValue : [$filterValue];
                if (is_array($value)) {
                    // A multiselect is "one of" the allowed set when any of the
                    // options it holds is in that set.
                    return array_intersect($value, $allowedValues) !== [];
                }
                return in_array($value, $allowedValues);
            case 'not_empty':
                return !empty($value);
            case 'empty':
                return empty($value);

            // Date operators
            case 'before':
                $dateValue = $this->parseDate($value);
                $dateFilter = $this->parseDate($filterValue);
                if (!$dateValue || !$dateFilter) return false;
                return $dateValue < $dateFilter;
            case 'after':
                $dateValue = $this->parseDate($value);
                $dateFilter = $this->parseDate($filterValue);
                if (!$dateValue || !$dateFilter) return false;
                return $dateValue > $dateFilter;

            // Number operators
            case 'greater_than':
                if (!is_numeric($value) || !is_numeric($filterValue)) return false;
                return (float)$value > (float)$filterValue;
            case 'less_than':
                if (!is_numeric($value) || !is_numeric($filterValue)) return false;
                return (float)$value < (float)$filterValue;
            case 'greater_or_equal':
                if (!is_numeric($value) || !is_numeric($filterValue)) return false;
                return (float)$value >= (float)$filterValue;
            case 'less_or_equal':
                if (!is_numeric($value) || !is_numeric($filterValue)) return false;
                return (float)$value <= (float)$filterValue;

            // Checkbox operators
            case 'is_true':
                return $value === true || $value === 'true' || $value === '1' || $value === 1;
            case 'is_false':
                return $value === false || $value === 'false' || $value === '0' || $value === 0 || $value === '';

            // Multiselect operators
            case 'contains_all':
                if (!is_array($value)) return false;
                $requiredValues = is_array($filterValue) ? $filterValue : [$filterValue];
                foreach ($requiredValues as $required) {
                    if (!in_array($required, $value)) return false;
                }
                return true;

            default:
                return false;
        }
    }

    /**
     * Filter pages based on publication dates from MetaVox fields
     *
     * Logic: (Publish date is empty OR Publish date <= today)
     *    AND (Expiration date is empty OR Expiration date > today)
     *
     * The gate itself lives in PageService (it is shared with the tree,
     * search and single-page reads) and arrives as two callables: one to
     * prefetch the publication metadata for all file ids at once, one to
     * evaluate a single page against it.
     *
     * @param array    $pages           Pages to filter
     * @param callable $fetchPublicationMeta fn(array $fileIds): array
     * @param callable $publishState         fn(array $page, array $meta): string
     * @return array Filtered pages that are currently published
     */
    public function applyPublicationDateFilter(array $pages, callable $fetchPublicationMeta, callable $publishState): array {
        // Delegate to the shared, time-aware publication gate so a News list uses
        // exactly the same definition of "published" as the rest of the app
        // (this also fixes the earlier date-only comparison where "today 03:25"
        // already counted as published). A page is kept only when it is
        // effectively published right now — which includes hiding manual drafts,
        // even when no publication date fields are configured or MetaVox is
        // absent. Previously this returned early in those cases, so "show only
        // published pages" let drafts through.
        $metaVoxData = $fetchPublicationMeta(array_column($pages, 'fileId'));

        return array_values(array_filter($pages, function($page) use ($metaVoxData, $publishState) {
            $fileId = $page['fileId'] ?? null;
            // Without a fileId we cannot look up dates, but the page's own
            // draft/published status is still authoritative — don't let a draft
            // through just because its metadata is unavailable.
            $meta = $fileId ? ($metaVoxData[$fileId] ?? []) : [];
            return $publishState($page, $meta) === 'published';
        }));
    }

    /**
     * Get list of available source folders for the News widget
     * Returns top-level folders in the language folder that contain pages
     */
    public function getSourceFolders(Folder $languageFolder): array {
        $folders = [];

        foreach ($this->locator->cachedDirectoryListing($languageFolder) as $item) {
            if ($item->getType() !== \OCP\Files\FileInfo::TYPE_FOLDER) {
                continue;
            }

            $folderName = $item->getName();

            // Skip special folders
            if (PagePathHelper::isInfrastructureFolder($folderName)) {
                continue;
            }

            // Check if this folder contains any pages
            if ($this->folderContainsPages($item)) {
                $folders[] = [
                    'path' => $folderName,
                    'name' => $folderName,
                ];
            }
        }

        // Sort alphabetically
        usort($folders, function($a, $b) {
            return strcasecmp($a['name'], $b['name']);
        });

        return $folders;
    }

    /**
     * Check if a folder contains any pages (recursively)
     */
    public function folderContainsPages($folder): bool {
        $folderName = $folder->getName();

        // Check if this folder itself is a page
        try {
            $folder->get($folderName . '.json');
            return true;
        } catch (\OCP\Files\NotFoundException $e) {
            // Not a page folder
        }

        // Check subfolders
        foreach ($this->locator->cachedDirectoryListing($folder) as $item) {
            if ($item->getType() === \OCP\Files\FileInfo::TYPE_FOLDER) {
                $subFolderName = $item->getName();

                // Skip special folders
                if (PagePathHelper::isInfrastructureFolder($subFolderName)) {
                    continue;
                }

                if ($this->folderContainsPages($item)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Format a timestamp in a localized date format
     */
    public function formatDateLocalized(int $timestamp, string $language): string {
        $months = [
            'nl' => ['januari', 'februari', 'maart', 'april', 'mei', 'juni', 'juli', 'augustus', 'september', 'oktober', 'november', 'december'],
            'en' => ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'],
            'de' => ['Januar', 'Februar', 'März', 'April', 'Mai', 'Juni', 'Juli', 'August', 'September', 'Oktober', 'November', 'Dezember'],
            'fr' => ['janvier', 'février', 'mars', 'avril', 'mai', 'juin', 'juillet', 'août', 'septembre', 'octobre', 'novembre', 'décembre'],
        ];

        $monthNames = $months[$language] ?? $months['en'];
        $monthIndex = (int)date('n', $timestamp) - 1;
        $day = date('j', $timestamp);
        $year = date('Y', $timestamp);

        return "$day {$monthNames[$monthIndex]} $year";
    }

    /**
     * Parse a date string to Y-m-d format for comparison
     *
     * Moved here with its only caller, matchesFilter(). The publication gate
     * uses the time-aware parseDateTime() in PageService instead, which is a
     * deliberately stricter contract — a date-only comparison there once made
     * "today 03:25" count as already published.
     *
     * @param string $dateStr Date string in various formats
     * @return string|null Normalized date in Y-m-d format, or null if parsing failed
     */
    private function parseDate(string $dateStr): ?string {
        if (empty($dateStr)) {
            return null;
        }

        $dateStr = trim($dateStr);

        // Try common date formats
        $formats = [
            'Y-m-d',        // ISO format: 2025-01-15
            'd-m-Y',        // European: 15-01-2025
            'm/d/Y',        // US: 01/15/2025
            'd/m/Y',        // European with slash: 15/01/2025
            'Y/m/d',        // Alternative ISO: 2025/01/15
            'Y-m-d H:i:s',  // ISO with time: 2025-01-15 14:30:00
            'd-m-Y H:i:s',  // European with time
            'Y-m-d\TH:i:s', // ISO 8601: 2025-01-15T14:30:00
        ];

        foreach ($formats as $format) {
            $date = \DateTime::createFromFormat($format, $dateStr);
            if ($date !== false) {
                return $date->format('Y-m-d');
            }
        }

        // Try strtotime as fallback for natural language dates
        $timestamp = strtotime($dateStr);
        if ($timestamp !== false) {
            return date('Y-m-d', $timestamp);
        }

        return null;
    }
}
