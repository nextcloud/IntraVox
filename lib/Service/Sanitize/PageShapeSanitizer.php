<?php

declare(strict_types=1);

namespace OCA\IntraVox\Service\Sanitize;

use OCA\IntraVox\Constants;
use OCP\IConfig;
use Psr\Log\LoggerInterface;

/**
 * Sanitize the *shape* of a page: its layout, rows, columns and widgets.
 *
 * Where the neighbouring sanitizers each guard one primitive (HTML, a URL, a
 * colour, an uploaded file), this service owns the composite rules — which
 * widget types exist, which keys each type may carry, and what a well-formed
 * page document looks like. It was carved out of PageService, where it was the
 * single largest cluster; sanitizeWidget() alone was 672 lines.
 *
 * Two properties make this safe to isolate, and worth keeping isolated:
 *
 * - It is pure. Nothing here touches the filesystem, the caches, permissions or
 *   the page locator. Input is an array, output is an array.
 * - It is the last gate before page JSON is persisted. A key this class does not
 *   enumerate is dropped on save, silently — the failure mode that once ate
 *   showPagination for several releases. That is why the rules live in one
 *   auditable place instead of spread across the write path.
 */
final class PageShapeSanitizer {
    private const ALLOWED_WIDGET_TYPES = ['text', 'heading', 'image', 'links', 'divider', 'video', 'news', 'people', 'calendar', 'feed', 'photo-story', 'file-story'];
    private const MAX_COLUMNS = 5;

    /**
     * Map of video platform domains to their embed domains.
     * When a user enters youtube.com, the frontend converts it to youtube-nocookie.com.
     * This mapping allows the whitelist check to recognize both.
     */
    private const VIDEO_DOMAIN_ALIASES = [
        // YouTube watch URLs → youtube-nocookie.com embed
        'www.youtube.com' => 'www.youtube-nocookie.com',
        'youtube.com' => 'www.youtube-nocookie.com',
        'm.youtube.com' => 'www.youtube-nocookie.com',
        // Vimeo watch URLs → player.vimeo.com embed
        'www.vimeo.com' => 'player.vimeo.com',
        'vimeo.com' => 'player.vimeo.com',
    ];

    /**
     * Base domains whose subdomains are ALL allowed when the base domain is on
     * the whitelist. Needed for providers that give each customer/space its own
     * subdomain — e.g. mave.io serves iframes from space-{hash}.video-dns.com,
     * so a single fixed allowlist entry can never match every space.
     *
     * Matching is boundary-safe (see sanitizeVideoEmbedUrl): the host must equal
     * the base OR end with '.' . $base, so evilvideo-dns.com and
     * video-dns.com.attacker.com are NOT matched.
     *
     * Keep this in sync with WILDCARD_VIDEO_DOMAINS in
     * src/components/WidgetEditor.vue (frontend Save-gate).
     */
    private const WILDCARD_VIDEO_DOMAINS = ['video-dns.com'];

    public function __construct(
        private readonly IConfig $config,
        private readonly LoggerInterface $logger,
        private readonly HtmlSanitizer $htmlSanitizer,
        private readonly UrlSanitizer $urlSanitizer,
        private readonly ColorSanitizer $colorSanitizer,
    ) {
    }

    /**
     * Validate and sanitize page data
     */
    public function validateAndSanitizePage(array $data): array {
        // Note: 'id' is NOT stored in JSON - the folder name IS the id
        $sanitized = [
            'title' => $this->sanitizeText($data['title']),
            'layout' => [
                'columns' => 1, // Default to 1 column
                'rows' => []
            ]
        ];

        // Preserve uniqueId if provided (for internal references)
        if (isset($data['uniqueId'])) {
            $sanitized['uniqueId'] = $data['uniqueId'];
        }

        // Translation group: the shared id linking the language versions of one
        // page. This is a strict whitelist, so an unlisted field is silently
        // dropped on every save — without this line a page would lose its
        // translation links the moment anyone edited it.
        //
        // Format-checked rather than sanitised: it is an identifier we generate
        // ('tg-' + UUID), never user prose, so anything that does not look like
        // one is dropped rather than escaped. Absence is valid and means the
        // page is not linked to any other language.
        if (isset($data['translationGroup'])
            && is_string($data['translationGroup'])
            && preg_match('/^tg-[a-f0-9-]{36}$/', $data['translationGroup'])
        ) {
            $sanitized['translationGroup'] = $data['translationGroup'];
        }

        // Provenance: where this page came from when it was migrated in.
        //
        // Same reason as translationGroup above — the whitelist is strict, so
        // without these two lines the first edit after a migration silently
        // erases the link back to the source, and with it any hope of a delta
        // run or of verifying what was imported.
        //
        // sourceUniqueId is the id in the system the page came from. The name is
        // fixed by plan-multisite-uitvoering.md P3, which mints fresh page ids on
        // import and carries this alongside; a second name for the same idea is
        // exactly the kind of thing that is cheap to avoid now and expensive
        // later. Length-capped and character-checked rather than escaped: it is
        // an identifier, never prose.
        if (isset($data['sourceUniqueId'])
            && is_string($data['sourceUniqueId'])
            && $data['sourceUniqueId'] !== ''
            && mb_strlen($data['sourceUniqueId']) <= 255
            && preg_match('/^[\w.:@\/-]+$/u', $data['sourceUniqueId'])
        ) {
            $sanitized['sourceUniqueId'] = $data['sourceUniqueId'];
        }

        // sourceUrl is where a human can go and look at the original. Validated as
        // a URL and restricted to http(s) so a migration cannot park a javascript:
        // or data: URI in page metadata that some later view renders as a link.
        if (isset($data['sourceUrl'])
            && is_string($data['sourceUrl'])
            && mb_strlen($data['sourceUrl']) <= 2048
            && filter_var($data['sourceUrl'], FILTER_VALIDATE_URL) !== false
            && in_array(parse_url($data['sourceUrl'], PHP_URL_SCHEME), ['http', 'https'], true)
        ) {
            $sanitized['sourceUrl'] = $data['sourceUrl'];
        }

        // Preserve settings object (engagement settings for comments/reactions)
        if (isset($data['settings']) && is_array($data['settings'])) {
            $sanitized['settings'] = [
                'allowReactions' => isset($data['settings']['allowReactions']) ? (bool)$data['settings']['allowReactions'] : true,
                'allowComments' => isset($data['settings']['allowComments']) ? (bool)$data['settings']['allowComments'] : true,
                'allowCommentReactions' => isset($data['settings']['allowCommentReactions']) ? (bool)$data['settings']['allowCommentReactions'] : true,
            ];
        }

        // Preserve page status (draft/published). Default to "published" for backward compatibility.
        if (isset($data['status']) && in_array($data['status'], ['draft', 'published'], true)) {
            $sanitized['status'] = $data['status'];
        }

        // Sibling sort order within the parent (issue #69). Integer; absence
        // means "legacy" — the comparator keeps such pages in filesystem order.
        if (isset($data['order']) && is_numeric($data['order'])) {
            $sanitized['order'] = (int)$data['order'];
        }

        if (isset($data['layout']['rows']) && is_array($data['layout']['rows'])) {
            foreach ($data['layout']['rows'] as $row) {
                if (isset($row['widgets']) && is_array($row['widgets'])) {
                    $sanitizedWidgets = [];

                    foreach ($row['widgets'] as $widget) {
                        $sanitizedWidget = $this->sanitizeWidget($widget);
                        if ($sanitizedWidget) {
                            $sanitizedWidgets[] = $sanitizedWidget;
                        } else {
                            // Log when widgets are dropped for debugging
                            $this->logger->warning('[validateAndSanitizePage] Widget dropped during validation', [
                                'type' => $widget['type'] ?? 'unknown',
                                'reason' => 'validation_failed'
                            ]);
                        }
                    }

                    $sanitizedRow = ['widgets' => $sanitizedWidgets];

                    // Preserve row ID if set (needed for collapsible state tracking)
                    if (isset($row['id'])) {
                        $sanitizedRow['id'] = $this->sanitizeText($row['id']);
                    }

                    // Preserve row-specific column count if set
                    if (isset($row['columns'])) {
                        $sanitizedRow['columns'] = $this->validateColumns($row['columns']);
                    }

                    // Preserve row background color if set
                    if (isset($row['backgroundColor'])) {
                        $sanitizedRow['backgroundColor'] = $this->colorSanitizer->sanitize($row['backgroundColor']);
                    }

                    // Preserve collapsible row settings
                    if (isset($row['collapsible'])) {
                        $sanitizedRow['collapsible'] = (bool)$row['collapsible'];
                    }
                    if (isset($row['sectionTitle'])) {
                        $sanitizedRow['sectionTitle'] = $this->sanitizeText($row['sectionTitle']);
                    }
                    if (isset($row['defaultCollapsed'])) {
                        $sanitizedRow['defaultCollapsed'] = (bool)$row['defaultCollapsed'];
                    }

                    // Keep row if it has widgets OR a background color OR is collapsible (don't silently drop empty styled/collapsible rows)
                    if (!empty($sanitizedWidgets) || !empty($sanitizedRow['backgroundColor']) || !empty($sanitizedRow['collapsible'])) {
                        $sanitized['layout']['rows'][] = $sanitizedRow;
                    }
                }
            }
        }

        // Validate and sanitize side columns
        if (isset($data['layout']['sideColumns']) && is_array($data['layout']['sideColumns'])) {
            $sanitized['layout']['sideColumns'] = $this->sanitizeSideColumns($data['layout']['sideColumns']);
        }

        // Validate and sanitize header row
        if (isset($data['layout']['headerRow']) && is_array($data['layout']['headerRow'])) {
            $sanitized['layout']['headerRow'] = $this->sanitizeHeaderRow($data['layout']['headerRow']);
        }

        return $sanitized;
    }

    /**
     * Sanitize page for output (decode HTML entities for display)
     */
    public function sanitizePage(array $data): array {
        // Re-sanitize widgets on every read to apply current whitelist settings
        // This ensures blocked video domains are marked correctly even if the
        // whitelist changed after the page was saved

        if (isset($data['layout']['rows']) && is_array($data['layout']['rows'])) {
            foreach ($data['layout']['rows'] as $rowIndex => $row) {
                if (isset($row['widgets']) && is_array($row['widgets'])) {
                    foreach ($row['widgets'] as $widgetIndex => $widget) {
                        if (($widget['type'] ?? '') === 'video') {
                            $sanitized = $this->sanitizeWidget($widget);
                            if ($sanitized) {
                                $data['layout']['rows'][$rowIndex]['widgets'][$widgetIndex] = $sanitized;
                            }
                        } elseif (($widget['type'] ?? '') === 'people') {
                            $this->decodeFilterValues($data['layout']['rows'][$rowIndex]['widgets'][$widgetIndex]);
                        }
                    }
                }
            }
        }

        // Also sanitize side columns
        if (isset($data['layout']['sideColumns']) && is_array($data['layout']['sideColumns'])) {
            foreach (['left', 'right'] as $side) {
                if (isset($data['layout']['sideColumns'][$side]['widgets']) && is_array($data['layout']['sideColumns'][$side]['widgets'])) {
                    foreach ($data['layout']['sideColumns'][$side]['widgets'] as $widgetIndex => $widget) {
                        if (($widget['type'] ?? '') === 'video') {
                            $sanitized = $this->sanitizeWidget($widget);
                            if ($sanitized) {
                                $data['layout']['sideColumns'][$side]['widgets'][$widgetIndex] = $sanitized;
                            }
                        } elseif (($widget['type'] ?? '') === 'people') {
                            $this->decodeFilterValues($data['layout']['sideColumns'][$side]['widgets'][$widgetIndex]);
                        }
                    }
                }
            }
        }

        // Also sanitize header row
        if (isset($data['layout']['headerRow']['widgets']) && is_array($data['layout']['headerRow']['widgets'])) {
            foreach ($data['layout']['headerRow']['widgets'] as $widgetIndex => $widget) {
                if (($widget['type'] ?? '') === 'video') {
                    $sanitized = $this->sanitizeWidget($widget);
                    if ($sanitized) {
                        $data['layout']['headerRow']['widgets'][$widgetIndex] = $sanitized;
                    }
                } elseif (($widget['type'] ?? '') === 'people') {
                    $this->decodeFilterValues($data['layout']['headerRow']['widgets'][$widgetIndex]);
                }
            }
        }

        return $data;
    }

    public function sanitizeWidget(array $widget): ?array {
        if (!isset($widget['type']) || !in_array($widget['type'], self::ALLOWED_WIDGET_TYPES)) {
            return null;
        }

        $sanitized = [
            'type' => $widget['type'],
            'column' => max(1, min((int)($widget['column'] ?? 1), self::MAX_COLUMNS)),
            'order' => (int)($widget['order'] ?? 1)
        ];

        // Preserve widget ID if present (needed for frontend to identify widgets)
        if (isset($widget['id'])) {
            $sanitized['id'] = $this->sanitizeText($widget['id']);
        }

        switch ($widget['type']) {
            case 'text':
                // Text widgets now contain HTML from rich text editor - sanitize HTML not text
                $sanitized['content'] = $this->htmlSanitizer->sanitize($widget['content'] ?? '');
                break;

            case 'heading':
                $sanitized['content'] = $this->sanitizeText($widget['content'] ?? '');
                $sanitized['level'] = max(1, min((int)($widget['level'] ?? 2), 6));
                break;

            case 'image':
                $sanitized['src'] = $this->sanitizePath($widget['src'] ?? '');
                $sanitized['alt'] = $this->sanitizeText($widget['alt'] ?? '');
                // Preserve optional image properties
                if (isset($widget['width'])) {
                    $sanitized['width'] = $this->sanitizeText((string)($widget['width'] ?? ''));
                }
                if (isset($widget['objectFit'])) {
                    $allowedFits = ['cover', 'contain', 'fill', 'none', 'scale-down'];
                    $sanitized['objectFit'] = in_array($widget['objectFit'], $allowedFits) ? $widget['objectFit'] : 'cover';
                }
                if (isset($widget['objectPosition'])) {
                    $allowedPositions = ['center', 'top', 'bottom', 'left', 'right'];
                    $sanitized['objectPosition'] = in_array($widget['objectPosition'], $allowedPositions) ? $widget['objectPosition'] : 'center';
                }
                // Preserve mediaFolder property (for _resources folder media)
                if (isset($widget['mediaFolder'])) {
                    $allowedFolders = ['page', 'resources'];
                    $sanitized['mediaFolder'] = in_array($widget['mediaFolder'], $allowedFolders) ? $widget['mediaFolder'] : 'page';
                }
                // Preserve image link properties
                if (isset($widget['linkType'])) {
                    $allowedLinkTypes = ['none', 'internal', 'external'];
                    $sanitized['linkType'] = in_array($widget['linkType'], $allowedLinkTypes) ? $widget['linkType'] : 'none';
                }
                if (isset($widget['linkUrl'])) {
                    $sanitized['linkUrl'] = $this->urlSanitizer->sanitize($widget['linkUrl']);
                }
                if (isset($widget['linkPageId'])) {
                    $sanitized['linkPageId'] = $this->sanitizeText($widget['linkPageId']);
                }
                break;

            case 'links':
                $sanitized['items'] = [];
                if (isset($widget['items']) && is_array($widget['items'])) {
                    foreach ($widget['items'] as $link) {
                        $sanitizedLink = [];
                        // Preserve title if present
                        if (isset($link['title'])) {
                            $sanitizedLink['title'] = $this->sanitizeText($link['title']);
                        }
                        // Use sanitizeHtml for link text to allow HTML entities and formatting
                        $sanitizedLink['text'] = $this->htmlSanitizer->sanitize($link['text'] ?? '');
                        $sanitizedLink['url'] = $this->urlSanitizer->sanitize($link['url'] ?? '');
                        $sanitizedLink['icon'] = $this->sanitizeText($link['icon'] ?? '');
                        // Preserve uniqueId for internal page links
                        if (isset($link['uniqueId']) && !empty($link['uniqueId'])) {
                            $sanitizedLink['uniqueId'] = $this->sanitizeText($link['uniqueId']);
                        }
                        // Preserve target attribute
                        if (isset($link['target'])) {
                            $allowedTargets = ['_self', '_blank'];
                            $sanitizedLink['target'] = in_array($link['target'], $allowedTargets) ? $link['target'] : '_self';
                        }
                        if (isset($link['backgroundColor'])) {
                            $sanitizedLink['backgroundColor'] = $this->colorSanitizer->sanitize($link['backgroundColor']);
                        }
                        $sanitized['items'][] = $sanitizedLink;
                    }
                }
                $sanitized['columns'] = max(1, min((int)($widget['columns'] ?? 2), 4));
                if (isset($widget['layout'])) {
                    $allowedLayouts = ['list', 'tiles'];
                    $sanitized['layout'] = in_array($widget['layout'], $allowedLayouts) ? $widget['layout'] : 'list';
                }
                if (isset($widget['backgroundColor'])) {
                    $sanitized['backgroundColor'] = $this->colorSanitizer->sanitize($widget['backgroundColor']);
                }
                break;

            case 'divider':
                // Preserve divider styling properties
                if (isset($widget['style'])) {
                    $allowedStyles = ['solid', 'dashed', 'dotted'];
                    $sanitized['style'] = in_array($widget['style'], $allowedStyles) ? $widget['style'] : 'solid';
                }
                if (isset($widget['color'])) {
                    $sanitized['color'] = $this->colorSanitizer->sanitize($widget['color']);
                }
                if (isset($widget['height'])) {
                    // Allow valid CSS height values like "2px", "1rem", etc.
                    $sanitized['height'] = preg_match('/^\d+(px|rem|em|%)$/', $widget['height'])
                        ? $widget['height']
                        : '2px';
                }
                break;

            case 'video':
                // Video widget - embed URL or local file
                // Supports: 'embed' (generic URL), 'local' (uploaded file)
                // Legacy 'peertube' is treated as 'embed' for backwards compatibility
                $provider = ($widget['provider'] ?? 'embed') === 'local' ? 'local' : 'embed';
                $sanitized['provider'] = $provider;
                $sanitized['title'] = $this->sanitizeText($widget['title'] ?? '');

                if ($provider === 'embed') {
                    // FIX: Check src (embed URL) first, then fallback to originalSrc
                    // The frontend converts youtube.com → youtube-nocookie.com in src
                    // but preserves the original URL in originalSrc
                    // We need to validate src (the embed URL) against the whitelist
                    $srcUrl = $widget['src'] ?? '';
                    $originalUrl = $widget['originalSrc'] ?? '';

                    // Validate src first (converted embed URL), fallback to originalSrc
                    $urlToValidate = !empty($srcUrl) ? $srcUrl : $originalUrl;
                    $sanitizedUrl = $this->sanitizeVideoEmbedUrl($urlToValidate);

                    if ($sanitizedUrl === '' && !empty($originalUrl)) {
                        // URL was blocked - preserve original URL so it can work again
                        // if admin adds the domain to whitelist later
                        $sanitized['src'] = '';
                        $sanitized['originalSrc'] = $originalUrl; // Preserve for later
                        $sanitized['blocked'] = true;
                        // Show blockedDomain based on what we validated
                        $blockedHost = !empty($srcUrl)
                            ? parse_url($srcUrl, PHP_URL_HOST)
                            : parse_url($originalUrl, PHP_URL_HOST);
                        $sanitized['blockedDomain'] = $blockedHost ?? '';
                    } else {
                        $sanitized['src'] = $sanitizedUrl;
                        $sanitized['originalSrc'] = $originalUrl ?: $sanitizedUrl; // Always preserve original
                        $sanitized['blocked'] = false;
                    }
                } else {
                    // Local video file - sanitize path
                    $sanitized['src'] = $this->sanitizePath($widget['src'] ?? '');
                    $sanitized['blocked'] = false;
                }

                // Preserve mediaFolder property (for _resources folder media)
                if (isset($widget['mediaFolder'])) {
                    $allowedFolders = ['page', 'resources'];
                    $sanitized['mediaFolder'] = in_array($widget['mediaFolder'], $allowedFolders) ? $widget['mediaFolder'] : 'page';
                }

                // Playback options (boolean values)
                $sanitized['autoplay'] = (bool) ($widget['autoplay'] ?? false);
                $sanitized['loop'] = (bool) ($widget['loop'] ?? false);
                $sanitized['muted'] = (bool) ($widget['muted'] ?? false);
                break;

            case 'news':
                // News widget - displays pages from a folder with optional MetaVox filters
                $sanitized['title'] = $this->sanitizeText($widget['title'] ?? '');
                $sanitized['sourcePath'] = $this->sanitizePath($widget['sourcePath'] ?? '');
                // sourcePageId is the uniqueId of the source page/folder (new PageTreeSelect approach)
                $sanitized['sourcePageId'] = isset($widget['sourcePageId']) && !empty($widget['sourcePageId'])
                    ? preg_replace('/[^a-zA-Z0-9_-]/', '', $widget['sourcePageId'])
                    : null;

                // Layout options
                $allowedLayouts = ['list', 'grid', 'carousel'];
                $sanitized['layout'] = in_array($widget['layout'] ?? 'list', $allowedLayouts)
                    ? ($widget['layout'] ?? 'list')
                    : 'list';

                // Grid columns (2-4)
                $sanitized['columns'] = max(2, min((int)($widget['columns'] ?? 3), 4));

                // Limit (1-20 items)
                $sanitized['limit'] = max(1, min((int)($widget['limit'] ?? 5), 20));

                // Sort options
                $allowedSortBy = ['modified', 'title'];
                $sanitized['sortBy'] = in_array($widget['sortBy'] ?? 'modified', $allowedSortBy)
                    ? ($widget['sortBy'] ?? 'modified')
                    : 'modified';

                $allowedSortOrder = ['asc', 'desc'];
                $sanitized['sortOrder'] = in_array($widget['sortOrder'] ?? 'desc', $allowedSortOrder)
                    ? ($widget['sortOrder'] ?? 'desc')
                    : 'desc';

                // Display options (booleans)
                $sanitized['showImage'] = (bool)($widget['showImage'] ?? true);
                $sanitized['showDate'] = (bool)($widget['showDate'] ?? true);
                $sanitized['showExcerpt'] = (bool)($widget['showExcerpt'] ?? true);
                $sanitized['excerptLength'] = max(50, min((int)($widget['excerptLength'] ?? 100), 500));

                // Carousel autoplay interval (0-30 seconds, 0 = disabled)
                $sanitized['autoplayInterval'] = max(0, min((int)($widget['autoplayInterval'] ?? 5), 30));

                // Background color — editor exposes a three-option toggle (default /
                // hover / primary). Validated against the same whitelist used for
                // rows and link items.
                if (isset($widget['backgroundColor'])) {
                    $sanitized['backgroundColor'] = $this->colorSanitizer->sanitize($widget['backgroundColor']);
                }

                // MetaVox filters
                $sanitized['filters'] = [];
                if (isset($widget['filters']) && is_array($widget['filters'])) {
                    foreach ($widget['filters'] as $filter) {
                        if (isset($filter['fieldName']) && !empty($filter['fieldName'])) {
                            $allowedOperators = [
                                // Text
                                'equals', 'not_equals', 'contains', 'not_contains',
                                'in', 'not_in',
                                // Empty
                                'not_empty', 'empty',
                                // Date
                                'before', 'after',
                                // Number
                                'greater_than', 'less_than', 'greater_or_equal', 'less_or_equal',
                                // Checkbox
                                'is_true', 'is_false',
                                // Multiselect
                                'contains_all',
                            ];
                            $sanitizedFilter = [
                                'fieldName' => $this->sanitizeText($filter['fieldName']),
                                'operator' => in_array($filter['operator'] ?? 'equals', $allowedOperators)
                                    ? $filter['operator']
                                    : 'equals',
                                'value' => $this->sanitizeText((string)($filter['value'] ?? '')),
                                'values' => [],
                            ];

                            // Sanitize values array (for 'in', 'contains', 'contains_all' operators)
                            if (isset($filter['values']) && is_array($filter['values'])) {
                                $sanitizedFilter['values'] = array_map(
                                    fn($v) => $this->sanitizeText((string)$v),
                                    $filter['values']
                                );
                            }

                            $sanitized['filters'][] = $sanitizedFilter;
                        }
                    }
                }

                $allowedFilterOperators = ['AND', 'OR'];
                $sanitized['filterOperator'] = in_array($widget['filterOperator'] ?? 'AND', $allowedFilterOperators)
                    ? ($widget['filterOperator'] ?? 'AND')
                    : 'AND';

                // Publication date filter (show only published pages)
                $sanitized['filterPublished'] = (bool)($widget['filterPublished'] ?? false);
                break;

            case 'people':
                // People widget - displays user profiles
                $sanitized['title'] = $this->sanitizeText($widget['title'] ?? '');

                // Selection mode
                $allowedModes = ['manual', 'filter'];
                $sanitized['selectionMode'] = in_array($widget['selectionMode'] ?? 'manual', $allowedModes)
                    ? ($widget['selectionMode'] ?? 'manual')
                    : 'manual';

                // Selected users (array of user IDs for manual mode)
                $sanitized['selectedUsers'] = [];
                if (isset($widget['selectedUsers']) && is_array($widget['selectedUsers'])) {
                    foreach ($widget['selectedUsers'] as $userId) {
                        // User IDs are alphanumeric strings
                        $sanitizedUserId = preg_replace('/[^a-zA-Z0-9_.@\-]/', '', (string)$userId);
                        if (!empty($sanitizedUserId)) {
                            $sanitized['selectedUsers'][] = $sanitizedUserId;
                        }
                    }
                }

                // Filters (for filter mode)
                $sanitized['filters'] = [];
                if (isset($widget['filters']) && is_array($widget['filters'])) {
                    foreach ($widget['filters'] as $filter) {
                        if (isset($filter['fieldName']) && !empty($filter['fieldName'])) {
                            $allowedOperators = [
                                'equals', 'not_equals', 'contains', 'not_contains',
                                'in', 'not_in', 'not_empty', 'empty',
                                // Date operators
                                'is_today', 'within_next_days', 'before', 'after',
                            ];
                            $sanitizedFilter = [
                                'fieldName' => $this->sanitizeText($filter['fieldName']),
                                'operator' => in_array($filter['operator'] ?? 'equals', $allowedOperators)
                                    ? $filter['operator']
                                    : 'equals',
                                'value' => $this->sanitizeFilterValue((string)($filter['value'] ?? '')),
                                'values' => [],
                            ];

                            // Sanitize values array (for 'in' operator)
                            if (isset($filter['values']) && is_array($filter['values'])) {
                                $sanitizedFilter['values'] = array_map(
                                    fn($v) => $this->sanitizeFilterValue((string)$v),
                                    $filter['values']
                                );
                            }

                            $sanitized['filters'][] = $sanitizedFilter;
                        }
                    }
                }

                $allowedFilterOperators = ['AND', 'OR'];
                $sanitized['filterOperator'] = in_array($widget['filterOperator'] ?? 'AND', $allowedFilterOperators)
                    ? ($widget['filterOperator'] ?? 'AND')
                    : 'AND';

                // Layout options
                $allowedLayouts = ['card', 'list', 'grid'];
                $sanitized['layout'] = in_array($widget['layout'] ?? 'card', $allowedLayouts)
                    ? ($widget['layout'] ?? 'card')
                    : 'card';

                // Grid/card columns (2-4)
                $sanitized['columns'] = max(2, min((int)($widget['columns'] ?? 3), 4));

                // Limit (1-50 people)
                $sanitized['limit'] = max(1, min((int)($widget['limit'] ?? 12), 50));

                // Sort options
                $allowedSortBy = ['displayName', 'email'];
                $sanitized['sortBy'] = in_array($widget['sortBy'] ?? 'displayName', $allowedSortBy)
                    ? ($widget['sortBy'] ?? 'displayName')
                    : 'displayName';

                $allowedSortOrder = ['asc', 'desc'];
                $sanitized['sortOrder'] = in_array($widget['sortOrder'] ?? 'asc', $allowedSortOrder)
                    ? ($widget['sortOrder'] ?? 'asc')
                    : 'asc';

                // Display options (showFields object)
                $sanitized['showFields'] = [
                    // Basic information
                    'avatar' => (bool)($widget['showFields']['avatar'] ?? true),
                    'displayName' => (bool)($widget['showFields']['displayName'] ?? true),
                    'pronouns' => (bool)($widget['showFields']['pronouns'] ?? false),
                    'role' => (bool)($widget['showFields']['role'] ?? true),
                    'headline' => (bool)($widget['showFields']['headline'] ?? false),
                    'department' => (bool)($widget['showFields']['department'] ?? true),
                    'title' => (bool)($widget['showFields']['title'] ?? ($widget['showFields']['role'] ?? true)),
                    // Contact
                    'email' => (bool)($widget['showFields']['email'] ?? true),
                    'phone' => (bool)($widget['showFields']['phone'] ?? false),
                    'address' => (bool)($widget['showFields']['address'] ?? false),
                    'website' => (bool)($widget['showFields']['website'] ?? false),
                    'birthdate' => (bool)($widget['showFields']['birthdate'] ?? false),
                    // Extended
                    'biography' => (bool)($widget['showFields']['biography'] ?? false),
                    'socialLinks' => (bool)($widget['showFields']['socialLinks'] ?? false),
                    'customFields' => (bool)($widget['showFields']['customFields'] ?? false),
                ];

                // Pagination toggle. Read by PeopleWidget.vue but never
                // persisted here, so it silently reset on every save.
                $sanitized['showPagination'] = ($widget['showPagination'] ?? true) !== false;

                // Viewer-side facet configuration
                $sanitized['viewerFilters'] = $this->sanitizeViewerFilters(
                    $widget['viewerFilters'] ?? null,
                    '/^[a-z][a-z0-9_]{0,63}$/i'
                );

                // Background color
                if (isset($widget['backgroundColor'])) {
                    $sanitized['backgroundColor'] = $this->colorSanitizer->sanitize($widget['backgroundColor']);
                }
                break;

            case 'calendar':
                // Calendar widget - displays events from shared calendars
                $sanitized['title'] = $this->sanitizeText($widget['title'] ?? '');

                // Calendar keys (array of strings)
                $sanitized['calendarIds'] = [];
                if (isset($widget['calendarIds']) && is_array($widget['calendarIds'])) {
                    foreach ($widget['calendarIds'] as $id) {
                        $strId = trim((string) $id);
                        if ($strId !== '') {
                            $sanitized['calendarIds'][] = $strId;
                        }
                    }
                }

                // External ICS URLs (array of HTTPS URLs, max 5)
                $sanitized['externalIcsUrls'] = [];
                if (isset($widget['externalIcsUrls']) && is_array($widget['externalIcsUrls'])) {
                    foreach (array_slice($widget['externalIcsUrls'], 0, 5) as $url) {
                        $url = trim((string) $url);
                        if ($url !== '' && filter_var($url, FILTER_VALIDATE_URL) && parse_url($url, PHP_URL_SCHEME) === 'https') {
                            $sanitized['externalIcsUrls'][] = $url;
                        }
                    }
                }

                // Date range
                $allowedRanges = ['upcoming', 'this_week', 'next_two_weeks', 'this_month', 'next_three_months', 'next_six_months', 'next_year', 'past_week', 'past_month', 'past_three_months'];
                $sanitized['dateRange'] = in_array($widget['dateRange'] ?? 'upcoming', $allowedRanges)
                    ? ($widget['dateRange'] ?? 'upcoming')
                    : 'upcoming';

                // Limit (1-20 events)
                $sanitized['limit'] = max(1, min((int) ($widget['limit'] ?? 5), 20));

                // Display options
                $sanitized['showTime'] = (bool) ($widget['showTime'] ?? true);
                $sanitized['showLocation'] = (bool) ($widget['showLocation'] ?? false);

                // Background color
                if (isset($widget['backgroundColor'])) {
                    $sanitized['backgroundColor'] = $this->colorSanitizer->sanitize($widget['backgroundColor']);
                }
                break;

            case 'photo-story':
                $config = is_array($widget['config'] ?? null) ? $widget['config'] : [];
                $sanitizedConfig = [];
                $sanitizedConfig['folderPath'] = $this->sanitizeFolderPath($config['folderPath'] ?? '');
                $allowedModes = ['timeline', 'highlights', 'grid', 'on-this-day'];
                $sanitizedConfig['mode'] = in_array($config['mode'] ?? 'timeline', $allowedModes, true)
                    ? ($config['mode'] ?? 'timeline')
                    : 'timeline';
                // Long-list handling: infinite scroll (default) or page buttons.
                $sanitizedConfig['paginationMode'] = (($config['paginationMode'] ?? 'infinite') === 'pages')
                    ? 'pages' : 'infinite';
                // Photos per page in page-buttons mode. Separate from `limit`,
                // which stays the total cap across the whole list.
                if (isset($config['pageSize']) && $config['pageSize'] !== '' && $config['pageSize'] !== null) {
                    $sanitizedConfig['pageSize'] = max(1, min((int)$config['pageSize'], 500));
                }
                if (isset($config['limit']) && $config['limit'] !== '' && $config['limit'] !== null) {
                    $sanitizedConfig['limit'] = max(1, min((int)$config['limit'], 500));
                }
                $sanitizedConfig['columns'] = max(2, min((int)($config['columns'] ?? 3), 5));
                $sanitizedConfig['showCaptions'] = !isset($config['showCaptions']) || (bool)$config['showCaptions'];
                $sanitizedConfig['showMap'] = !empty($config['showMap']);
                // Phase 2.8 — per-day mini-map. Default true so existing pages get them.
                $sanitizedConfig['showDayMaps'] = !isset($config['showDayMaps']) || (bool)$config['showDayMaps'];

                // Sort direction. Default 'desc' (newest first).
                $sanitizedConfig['sortOrder'] = (($config['sortOrder'] ?? 'desc') === 'asc') ? 'asc' : 'desc';

                // Sort key. Accepts file-level columns (mtime/name/size), the
                // virtual 'taken_at' (NC core), or any MetaVox field name. Pattern
                // restriction prevents nonsense input from reaching the backend
                // where it would be ignored anyway, but keeps the page-JSON tidy.
                $rawSortBy = (string)($config['sortBy'] ?? 'mtime');
                $sanitizedConfig['sortBy'] = preg_match('/^[a-z][a-z0-9_]{0,63}$/i', $rawSortBy)
                    ? $rawSortBy : 'mtime';

                // Phase 2.4 — cross-folder mode + MetaVox filter rows.
                $sanitizedConfig['allMetaVoxFolders'] = !empty($config['allMetaVoxFolders']);
                $rawFilters = is_array($config['metaVoxFilters'] ?? null) ? $config['metaVoxFilters'] : [];
                $allowedOps = ['equals', 'contains', 'in', 'year_equals'];
                $cleanFilters = [];
                foreach ($rawFilters as $entry) {
                    if (!is_array($entry)) {
                        continue;
                    }
                    $field = isset($entry['field']) ? (string)$entry['field'] : '';
                    $op = isset($entry['op']) ? (string)$entry['op'] : '';
                    $val = $entry['value'] ?? '';
                    if ($field === '' || !preg_match('/^exif_[a-z_]+$/', $field)) {
                        continue;
                    }
                    if (!in_array($op, $allowedOps, true)) {
                        continue;
                    }
                    if (is_array($val)) {
                        $coerced = [];
                        foreach ($val as $v) {
                            $s = is_scalar($v) ? trim((string)$v) : '';
                            if ($s !== '') {
                                $coerced[] = mb_substr($s, 0, 200);
                            }
                        }
                        if (empty($coerced)) {
                            continue;
                        }
                        $val = array_values($coerced);
                    } else {
                        $s = is_scalar($val) ? trim((string)$val) : '';
                        if ($s === '') {
                            continue;
                        }
                        $val = mb_substr($s, 0, 200);
                    }
                    $cleanFilters[] = ['field' => $field, 'op' => $op, 'value' => $val];
                }
                $sanitizedConfig['metaVoxFilters'] = $cleanFilters;

                // Visual style (already used in the editor but wasn't persisted yet — add it here)
                $allowedStyles = ['magazine', 'apple', 'travelogue'];
                $sanitizedConfig['style'] = in_array($config['style'] ?? 'apple', $allowedStyles, true)
                    ? ($config['style'] ?? 'apple')
                    : 'apple';

                $sanitized['config'] = $sanitizedConfig;
                break;

            case 'file-story':
                // FileStoryWidget — documents counterpart of photo-story.
                // Lighter sanitization since it has fewer config knobs (no map,
                // no visual styles, no day-maps, no cross-folder mode).
                $config = is_array($widget['config'] ?? null) ? $widget['config'] : [];
                $sanitizedConfig = [];
                $sanitizedConfig['folderPath'] = $this->sanitizeFolderPath($config['folderPath'] ?? '');
                $allowedModes = ['timeline', 'tiles', 'list', 'grouped'];
                $sanitizedConfig['mode'] = in_array($config['mode'] ?? 'timeline', $allowedModes, true)
                    ? ($config['mode'] ?? 'timeline') : 'timeline';
                // Long-list handling: infinite scroll (default) or page buttons (#78).
                $sanitizedConfig['paginationMode'] = (($config['paginationMode'] ?? 'infinite') === 'pages')
                    ? 'pages' : 'infinite';
                // Documents per page in page-buttons mode. Separate from `limit`,
                // which stays the total cap across the whole list (#78).
                if (isset($config['pageSize']) && $config['pageSize'] !== '' && $config['pageSize'] !== null) {
                    $sanitizedConfig['pageSize'] = max(1, min((int)$config['pageSize'], 500));
                }
                if (isset($config['limit']) && $config['limit'] !== '' && $config['limit'] !== null) {
                    $sanitizedConfig['limit'] = max(1, min((int)$config['limit'], 500));
                }
                $sanitizedConfig['sortOrder'] = (($config['sortOrder'] ?? 'desc') === 'asc') ? 'asc' : 'desc';
                $rawSortBy = (string)($config['sortBy'] ?? 'mtime');
                $sanitizedConfig['sortBy'] = preg_match('/^[a-z][a-z0-9_]{0,63}$/i', $rawSortBy)
                    ? $rawSortBy : 'mtime';
                $rawGroupBy = (string)($config['groupBy'] ?? 'category');
                $sanitizedConfig['groupBy'] = preg_match('/^[a-z][a-z0-9_]{0,63}$/i', $rawGroupBy)
                    ? $rawGroupBy : 'category';

                // Timeline granularity: day / month / year. Default "month" for
                // documents because per-day buckets are usually too fine here.
                $rawGran = (string)($config['granularity'] ?? 'month');
                $sanitizedConfig['granularity'] = in_array($rawGran, ['day', 'month', 'year'], true)
                    ? $rawGran : 'month';

                // Date field preference: which timestamp to display in the row.
                $rawDateField = (string)($config['dateField'] ?? 'mtime');
                $sanitizedConfig['dateField'] = in_array($rawDateField, ['mtime', 'taken_at', 'created'], true)
                    ? $rawDateField : 'mtime';

                // Visible columns: whitelist-filter the user-supplied list.
                $allowedCols = ['date', 'size', 'path'];
                $rawCols = is_array($config['visibleColumns'] ?? null) ? $config['visibleColumns'] : ['date'];
                $cleanCols = [];
                foreach ($rawCols as $col) {
                    if (is_string($col) && in_array($col, $allowedCols, true) && !in_array($col, $cleanCols, true)) {
                        $cleanCols[] = $col;
                    }
                }
                $sanitizedConfig['visibleColumns'] = $cleanCols;

                // Tile size — only meaningful in tiles-mode but persisted across
                // modes so toggling between modes keeps the user's previous choice.
                $rawTileSize = (string)($config['tileSize'] ?? 'medium');
                $sanitizedConfig['tileSize'] = in_array($rawTileSize, ['small', 'medium', 'large'], true)
                    ? $rawTileSize : 'medium';

                // Reuse the photo-story filter sanitization (same shape).
                $rawFilters = is_array($config['metaVoxFilters'] ?? null) ? $config['metaVoxFilters'] : [];
                $allowedOps = ['equals', 'contains', 'in', 'year_equals'];
                $cleanFilters = [];
                foreach ($rawFilters as $entry) {
                    if (!is_array($entry)) continue;
                    $field = isset($entry['field']) ? (string)$entry['field'] : '';
                    $op = isset($entry['op']) ? (string)$entry['op'] : '';
                    $val = $entry['value'] ?? '';
                    if ($field === '' || !preg_match('/^[a-z][a-z0-9_]{0,63}$/i', $field)) continue;
                    if (!in_array($op, $allowedOps, true)) continue;
                    if (is_array($val)) {
                        $coerced = [];
                        foreach ($val as $v) {
                            $s = is_scalar($v) ? trim((string)$v) : '';
                            if ($s !== '') $coerced[] = mb_substr($s, 0, 200);
                        }
                        if (empty($coerced)) continue;
                        $val = array_values($coerced);
                    } else {
                        $s = is_scalar($val) ? trim((string)$val) : '';
                        if ($s === '') continue;
                        $val = mb_substr($s, 0, 200);
                    }
                    $cleanFilters[] = ['field' => $field, 'op' => $op, 'value' => $val];
                }
                $sanitizedConfig['metaVoxFilters'] = $cleanFilters;

                $sanitized['config'] = $sanitizedConfig;
                break;

            case 'feed':
                // Feed widget - displays items from external RSS/Atom feeds or LMS APIs
                $sanitized['title'] = $this->sanitizeText($widget['title'] ?? '');

                // Source type — dynamically accept configured LMS types
                $configuredTypes = array_unique(array_column(
                    // Literal app id: Application::APP_ID would pull in the app framework.
                    json_decode($this->config->getAppValue('intravox', 'feed_connections', '[]'), true) ?: [],
                    'type'
                ));
                $allowedSourceTypes = array_unique(array_merge(['rss', 'connection'], $configuredTypes));
                $sanitized['sourceType'] = in_array($widget['sourceType'] ?? 'rss', $allowedSourceTypes)
                    ? ($widget['sourceType'] ?? 'rss')
                    : 'rss';

                // Feed URL (for RSS type)
                $sanitized['feedUrl'] = $this->sanitizeText($widget['feedUrl'] ?? '');

                // LMS connection ID and course ID
                $sanitized['connectionId'] = $this->sanitizeText($widget['connectionId'] ?? '');
                $sanitized['courseId'] = $this->sanitizeText($widget['courseId'] ?? '');

                // Content type (for LMS types)
                $allowedContentTypes = ['', 'news', 'my-courses', 'deadlines', 'courses', 'assignments', 'open', 'overdue', 'milestones', 'recently-updated', 'pages', 'documents', 'list', 'bugs', 'recent', 'created-recent'];
                $sanitized['contentType'] = in_array($widget['contentType'] ?? '', $allowedContentTypes, true)
                    ? ($widget['contentType'] ?? '')
                    : '';

                // SharePoint list/library ID, Jira project key, Moodle forum ID
                $sanitized['listId'] = $this->sanitizeText($widget['listId'] ?? '');
                $sanitized['jiraProject'] = $this->sanitizeText($widget['jiraProject'] ?? '');
                $sanitized['moodleForumId'] = $this->sanitizeText($widget['moodleForumId'] ?? '');

                // Layout
                $sanitized['layout'] = in_array($widget['layout'] ?? 'list', ['list', 'grid'])
                    ? ($widget['layout'] ?? 'list')
                    : 'list';

                // Columns (for grid layout, 2-4)
                $sanitized['columns'] = max(2, min((int) ($widget['columns'] ?? 3), 4));

                // Limit (1-20 items)
                $sanitized['limit'] = max(1, min((int) ($widget['limit'] ?? 5), 20));

                // Display options
                $sanitized['showImage'] = (bool) ($widget['showImage'] ?? true);
                $sanitized['showDate'] = (bool) ($widget['showDate'] ?? true);
                $sanitized['showExcerpt'] = (bool) ($widget['showExcerpt'] ?? true);
                $sanitized['showSource'] = (bool) ($widget['showSource'] ?? false);
                $sanitized['excerptLength'] = max(50, min((int) ($widget['excerptLength'] ?? 150), 500));
                $sanitized['openInNewTab'] = (bool) ($widget['openInNewTab'] ?? true);

                // Sort and filter
                $sanitized['sortBy'] = in_array($widget['sortBy'] ?? 'date', ['date', 'title'], true) ? ($widget['sortBy'] ?? 'date') : 'date';
                $sanitized['sortOrder'] = in_array($widget['sortOrder'] ?? 'desc', ['asc', 'desc'], true) ? ($widget['sortOrder'] ?? 'desc') : 'desc';
                $filterKeyword = trim((string) ($widget['filterKeyword'] ?? ''));
                $sanitized['filterKeyword'] = mb_substr($filterKeyword, 0, 100);

                // Background color
                if (isset($widget['backgroundColor'])) {
                    $sanitized['backgroundColor'] = $this->colorSanitizer->sanitize($widget['backgroundColor']);
                }
                break;
        }

        return $sanitized;
    }

    /**
     * Sanitize side columns data
     */
    private function sanitizeSideColumns(array $sideColumns): array {
        $sanitized = [];

        foreach (['left', 'right'] as $side) {
            if (isset($sideColumns[$side]) && is_array($sideColumns[$side])) {
                $sideData = $sideColumns[$side];

                $sanitizedSide = [
                    'enabled' => !empty($sideData['enabled']),
                    'backgroundColor' => isset($sideData['backgroundColor'])
                        ? $this->colorSanitizer->sanitize($sideData['backgroundColor'])
                        : '',
                    'widgets' => []
                ];

                // Sanitize widgets in this side column
                if (isset($sideData['widgets']) && is_array($sideData['widgets'])) {
                    foreach ($sideData['widgets'] as $widget) {
                        $sanitizedWidget = $this->sanitizeWidget($widget);
                        if ($sanitizedWidget) {
                            $sanitizedSide['widgets'][] = $sanitizedWidget;
                        }
                    }
                }

                $sanitized[$side] = $sanitizedSide;
            }
        }

        return $sanitized;
    }

    /**
     * Sanitize header row data
     */
    private function sanitizeHeaderRow(array $headerRow): array {
        $sanitized = [
            'enabled' => !empty($headerRow['enabled']),
            'backgroundColor' => isset($headerRow['backgroundColor'])
                ? $this->colorSanitizer->sanitize($headerRow['backgroundColor'])
                : '',
            'widgets' => []
        ];

        // Sanitize widgets in header row
        if (isset($headerRow['widgets']) && is_array($headerRow['widgets'])) {
            foreach ($headerRow['widgets'] as $widget) {
                $sanitizedWidget = $this->sanitizeWidget($widget);
                if ($sanitizedWidget) {
                    $sanitized['widgets'][] = $sanitizedWidget;
                }
            }
        }

        return $sanitized;
    }

    /**
     * Sanitize widget data
     */
    /**
     * Sanitize a widget's viewer-side facet configuration.
     *
     * One helper shared by every faceted widget type. Four copies is how
     * they drift, and a key that is not enumerated here disappears on the
     * first save with no error at all — the same failure mode that quietly
     * ate `showPagination`.
     *
     * @param mixed $raw
     * @param string $fieldPattern regex a facet field name must match
     */
    public function sanitizeViewerFilters($raw, string $fieldPattern): array {
        $default = [
            'enabled' => false,
            'facets' => [],
            'searchFields' => [],
            'searchEnabled' => true,
            'layout' => 'sidebar',
        ];

        if (!is_array($raw)) {
            return $default;
        }

        $facets = [];
        if (isset($raw['facets']) && is_array($raw['facets'])) {
            foreach ($raw['facets'] as $entry) {
                if (count($facets) >= 12) {
                    break;
                }

                // Accept both a bare field name and a full config object.
                $field = is_array($entry) ? (string)($entry['field'] ?? '') : (string)$entry;
                $field = trim($field);
                if ($field === '' || !preg_match($fieldPattern, $field)) {
                    continue;
                }

                $limit = is_array($entry) ? (int)($entry['limit'] ?? 8) : 8;
                $limit = max(5, min($limit, 100));

                $facets[] = [
                    'field' => $field,
                    'label' => is_array($entry) ? $this->sanitizeText((string)($entry['label'] ?? '')) : '',
                    'limit' => $limit,
                    'collapsed' => is_array($entry) && ($entry['collapsed'] ?? false) === true,
                ];
            }
        }

        $searchFields = [];
        if (isset($raw['searchFields']) && is_array($raw['searchFields'])) {
            foreach ($raw['searchFields'] as $entry) {
                if (count($searchFields) >= 8) {
                    break;
                }
                $field = trim((string)$entry);
                if ($field !== '' && preg_match($fieldPattern, $field)) {
                    $searchFields[] = $field;
                }
            }
        }

        return [
            'enabled' => ($raw['enabled'] ?? false) === true,
            'facets' => $facets,
            'searchFields' => array_values(array_unique($searchFields)),
            'searchEnabled' => ($raw['searchEnabled'] ?? true) !== false,
            'layout' => ($raw['layout'] ?? 'sidebar') === 'top' ? 'top' : 'sidebar',
        ];
    }

    /**
     * Decode HTML entities in people widget filter values.
     * Fixes data corrupted by prior use of sanitizeText() (htmlspecialchars)
     * on filter values that are used for programmatic comparison.
     */
    private function decodeFilterValues(array &$widget): void {
        if (!isset($widget['filters']) || !is_array($widget['filters'])) {
            return;
        }
        foreach ($widget['filters'] as &$filter) {
            if (isset($filter['value']) && is_string($filter['value'])) {
                $filter['value'] = $this->htmlSanitizer->decodeEntitiesRecursive($filter['value']);
            }
            if (isset($filter['values']) && is_array($filter['values'])) {
                $filter['values'] = array_map(
                    fn($v) => is_string($v) ? $this->htmlSanitizer->decodeEntitiesRecursive($v) : $v,
                    $filter['values']
                );
            }
        }
    }

    /**
     * Sanitize text content (prevent XSS)
     */
    public function sanitizeText(string $text): string {
        // Plain-text fields (page titles, widget titles, alt text, link labels,
        // …) are rendered in text contexts where the frontend escapes on output,
        // so we must NOT HTML-encode here — doing so stored apostrophes as
        // "&apos;", ampersands as "&amp;" etc. and showed the literal entities
        // in the UI (e.g. "Collega&apos;s"). Strip tags and control characters
        // so no markup can survive, but keep the text human-readable. Escaping
        // is the responsibility of each output sink (Vue escapes automatically;
        // the RSS/XML and HTML-export emitters escape at emit time).
        $text = strip_tags($text);
        $text = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $text);
        return trim($text);
    }

    /**
     * Sanitize a filter value for safe storage.
     * Unlike sanitizeText(), this does NOT HTML-encode because filter values
     * are used for programmatic comparison against raw user profile data.
     */
    private function sanitizeFilterValue(string $value): string {
        $value = strip_tags($value);
        $value = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $value);
        return trim($value);
    }

    public function sanitizePath(string $path): string {
        // Allow empty paths (used for news widget sourcePath to indicate "all pages")
        if (empty($path)) {
            return '';
        }

        // 1. Check for null bytes (can bypass extension checks)
        if (strpos($path, "\0") !== false) {
            throw new \InvalidArgumentException('Null bytes not allowed in path');
        }

        // 2. Unicode normalization (prevent NFD/NFC attacks)
        if (class_exists('Normalizer')) {
            $normalized = \Normalizer::normalize($path, \Normalizer::FORM_C);
            if ($normalized === false) {
                throw new \InvalidArgumentException('Invalid unicode sequence in path');
            }
            $path = $normalized;
        }

        // 3. Convert backslashes to forward slashes
        $path = str_replace('\\', '/', $path);

        // 4. Remove leading/trailing slashes
        $path = trim($path, '/');

        // 5. If path becomes empty after trimming, return empty
        if (empty($path)) {
            return '';
        }

        // 6. Detect directory traversal attempts
        if (strpos($path, '..') !== false) {
            throw new \InvalidArgumentException('Path traversal not allowed');
        }

        // 7. Validate path segments
        $segments = explode('/', $path);
        foreach ($segments as $segment) {
            // Empty segments (double slashes)
            if (empty($segment) || $segment === '.' || $segment === '..') {
                throw new \InvalidArgumentException('Invalid path segment');
            }

            // Hidden files (starting with dot)
            if (substr($segment, 0, 1) === '.') {
                throw new \InvalidArgumentException('Hidden files not allowed');
            }

            // Block executable PHP extensions
            if (preg_match('/\.(php|phtml|php[345]|phar|phps|pht)$/i', $segment)) {
                throw new \InvalidArgumentException('Executable files not allowed');
            }
        }

        return $path;
    }

    /**
     * Sanitize file path - prevent directory traversal and other path attacks
     *
     * Security checks:
     * - Null byte injection
     * - Unicode normalization (NFD/NFC attacks)
     * - Directory traversal (..)
     * - Backslash conversion
     * - Hidden files (starting with .)
     * - Executable file extensions
     *
     * @param string $path User-provided path
     * @return string Safe path
     * @throws \InvalidArgumentException if path is malicious
     */
    /**
     * Sanitize a folder-path that may be the root ("/"). PhotoStory and
     * FileStory widgets treat "/" as "the whole user drive" — a meaningful
     * value that must survive persistence. The generic sanitizePath() strips
     * leading/trailing slashes and would collapse "/" to "" (= "no folder
     * selected"), so widgets that allow root selection use this wrapper.
     */
    public function sanitizeFolderPath(string $path): string {
        $trimmed = trim($path);
        if ($trimmed === '/' || $trimmed === '\\') {
            return '/';
        }
        return $this->sanitizePath($path);
    }

    /**
     * Sanitize video embed URL
     * Validates against configured whitelist of allowed domains
     * Supports: YouTube, Vimeo, PeerTube, Dailymotion, Twitch, TikTok, etc.
     */
    public function sanitizeVideoEmbedUrl(string $url): string {
        if (empty($url)) {
            return '';
        }

        // Must be HTTPS
        if (!str_starts_with($url, 'https://')) {
            return '';
        }

        // Parse URL
        $parsed = parse_url($url);
        if (!$parsed || !isset($parsed['scheme']) || !isset($parsed['host']) || !isset($parsed['path'])) {
            return '';
        }

        // Check against whitelist
        $allowedDomains = $this->getAllowedVideoDomains();
        $host = $parsed['host'];

        // Check if this host has an alias (e.g., youtube.com → youtube-nocookie.com)
        $embedHost = self::VIDEO_DOMAIN_ALIASES[$host] ?? null;

        $isAllowed = false;
        foreach ($allowedDomains as $allowedDomain) {
            $allowedHost = parse_url($allowedDomain, PHP_URL_HOST);
            // Match either the original host OR its embed alias
            if ($host === $allowedHost || ($embedHost && $embedHost === $allowedHost)) {
                $isAllowed = true;
                break;
            }
            // Wildcard base domains (e.g. video-dns.com) also match any of their
            // subdomains. Boundary-safe: only true subdomains (leading '.') match.
            if (in_array($allowedHost, self::WILDCARD_VIDEO_DOMAINS, true)
                && str_ends_with($host, '.' . $allowedHost)) {
                $isAllowed = true;
                break;
            }
        }

        if (!$isAllowed) {
            $this->logger->warning('Video domain not in whitelist: ' . $host);
            return '';
        }

        // Convert watch URLs to embed URLs for known platforms
        // YouTube: https://www.youtube.com/watch?v=VIDEO_ID → https://www.youtube-nocookie.com/embed/VIDEO_ID
        if (in_array($host, ['www.youtube.com', 'youtube.com', 'm.youtube.com'])) {
            parse_str($parsed['query'] ?? '', $queryParams);
            $videoId = $queryParams['v'] ?? null;
            if ($videoId) {
                return 'https://www.youtube-nocookie.com/embed/' . urlencode($videoId);
            }
            // If already an embed URL or other format, pass through
            if (str_contains($parsed['path'], '/embed/')) {
                return 'https://www.youtube-nocookie.com' . $parsed['path'];
            }
        }

        // Vimeo: https://vimeo.com/VIDEO_ID → https://player.vimeo.com/video/VIDEO_ID
        if (in_array($host, ['www.vimeo.com', 'vimeo.com'])) {
            // Extract video ID from path like /123456789 or /123456789?h=xxxxx
            if (preg_match('#^/(\d+)#', $parsed['path'], $matches)) {
                $videoId = $matches[1];
                $embedUrl = 'https://player.vimeo.com/video/' . $videoId;
                // Preserve hash parameter for unlisted videos
                if (isset($parsed['query'])) {
                    parse_str($parsed['query'], $queryParams);
                    if (isset($queryParams['h'])) {
                        $embedUrl .= '?h=' . urlencode($queryParams['h']);
                    }
                }
                return $embedUrl;
            }
        }

        // For PeerTube URLs, enforce privacy settings
        if (str_contains($parsed['path'], '/videos/embed/')) {
            $cleanUrl = $parsed['scheme'] . '://' . $parsed['host'] . $parsed['path'];
            return $cleanUrl . '?p2p=0&peertubeLink=0';
        }

        // For other platforms, return the embed URL with existing query params
        $cleanUrl = $parsed['scheme'] . '://' . $parsed['host'] . $parsed['path'];
        if (isset($parsed['query'])) {
            $cleanUrl .= '?' . $parsed['query'];
        }
        return $cleanUrl;
    }

    /**
     * Get allowed video domains from config
     * @return array List of allowed HTTPS domains
     */
    private function getAllowedVideoDomains(): array {
        $domains = $this->config->getAppValue(
            'intravox',
            'video_domains',
            Constants::getDefaultVideoDomainsJson()
        );

        // Decode the stored JSON
        $decoded = json_decode($domains, true);

        // Only use defaults if JSON decode FAILED (null), not for empty array
        // This allows admins to explicitly block all video embeds by removing all domains
        if ($decoded === null) {
            return Constants::DEFAULT_VIDEO_DOMAINS;
        }

        return $decoded;
    }

    /**
     * Validate column count
     */
    private function validateColumns(int $columns): int {
        return max(1, min($columns, self::MAX_COLUMNS));
    }
}
