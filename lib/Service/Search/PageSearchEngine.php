<?php

declare(strict_types=1);

namespace OCA\IntraVox\Service\Search;

use OCA\IntraVox\Service\Publication\MetaVoxGateway;

/**
 * Scores a set of already-loaded pages against a query and returns the ranked,
 * capped result list. This is the in-memory half of searchPages() — the
 * page-discovery walk (listPagesWithContent) stays on PageService and feeds the
 * result in, so the engine never touches the filesystem or the folder seams.
 *
 * Extracted verbatim from PageService::searchPages() (Phase "search"): the
 * scoring weights (title 10, uniqueId 5, MetaVox 7, widget scores via
 * PageSearchHelper), the 3-match cap with an uncapped matchCount, the
 * score-descending sort and the top-20 limit are unchanged. PageSearchTest pins
 * the behaviour end-to-end through the PageService facade.
 *
 * The MetaVox gateway is injected (not rebuilt) so the request-scoped memo it
 * shares with getMetaVoxDataForFiles/searchPages stays a single instance.
 */
class PageSearchEngine {
    public function __construct(
        private PageSearchHelper $searchHelper,
        private MetaVoxGateway $metaVox,
    ) {
    }

    /**
     * @param list<array> $pagesWithContent pages as listPagesWithContent() yields
     * @return list<array> ranked result descriptors, top 20
     */
    public function search(array $pagesWithContent, string $query): array {
        $results = [];
        $query = mb_strtolower($query);

        // MetaVox metadata is stored alongside the file, not inside the page
        // JSON, so a page tagged "Stad: Luik" is invisible to a content-only
        // search. Batch-load it for every page in one query (no N+1) and treat
        // it as an additional match source below.
        $metaVoxData = $this->metaVox->getMetaVoxDataForFiles(
            array_values(array_filter(array_column($pagesWithContent, 'fileId')))
        );
        $metaVoxLabels = empty($metaVoxData) ? [] : $this->metaVox->getMetaVoxFieldLabels();

        foreach ($pagesWithContent as $pageData) {
            $matches = [];
            $score = 0;

            // Skip pages without uniqueId
            if (!isset($pageData['uniqueId']) || empty($pageData['uniqueId'])) {
                continue;
            }

            // Search in title (higher weight)
            if (isset($pageData['title']) && mb_stripos($pageData['title'], $query) !== false) {
                $score += 10;
                $matches[] = [
                    'type' => 'title',
                    'text' => $pageData['title']
                ];
            }

            // Search in uniqueId (medium weight)
            if (mb_stripos($pageData['uniqueId'], $query) !== false) {
                $score += 5;
            }

            // Search in content - layout is already loaded
            // Collect all widgets from all layout areas
            $allWidgets = [];

            // Main rows
            if (isset($pageData['layout']['rows'])) {
                foreach ($pageData['layout']['rows'] as $row) {
                    if (isset($row['widgets'])) {
                        $allWidgets = array_merge($allWidgets, $row['widgets']);
                    }
                }
            }

            // Header row
            if (isset($pageData['layout']['headerRow']['widgets'])) {
                $allWidgets = array_merge($allWidgets, $pageData['layout']['headerRow']['widgets']);
            }

            // Side columns
            if (isset($pageData['layout']['sideColumns']['left']['widgets'])) {
                $allWidgets = array_merge($allWidgets, $pageData['layout']['sideColumns']['left']['widgets']);
            }
            if (isset($pageData['layout']['sideColumns']['right']['widgets'])) {
                $allWidgets = array_merge($allWidgets, $pageData['layout']['sideColumns']['right']['widgets']);
            }

            // Search through all collected widgets
            foreach ($allWidgets as $widget) {
                $widgetMatches = $this->searchHelper->searchWidget($widget, $query);
                foreach ($widgetMatches as $match) {
                    $score += $match['score'];
                    $matches[] = [
                        'type' => $match['type'],
                        'text' => $match['text']
                    ];
                }
            }

            // Search MetaVox metadata (Stad, Thema, ...). Scored between title
            // (10) and plain content so a metadata hit ranks meaningfully but
            // never outranks the page actually being named after the term.
            $fileId = $pageData['fileId'] ?? null;
            $pageMeta = $fileId !== null ? ($metaVoxData[$fileId] ?? []) : [];
            $metaMatches = $this->metaVox->searchMetaVoxValues(
                $pageMeta,
                $query,
                $metaVoxLabels,
                $fileId !== null ? $this->metaVox->groupfolderIdForFile($fileId) : null
            );
            if (!empty($metaMatches)) {
                $score += 7;
                // The subline mirrors MetaVox's own format so results read the
                // same in both providers: "Label: value" joined with " • ",
                // matching field first, capped at 3 fields.
                $matches[] = [
                    'type' => 'metadata',
                    'text' => $metaMatches['subline'],
                ];
            }

            // If we have matches, add to results
            if ($score > 0) {
                $results[] = [
                    // The empty-uniqueId `continue` above guarantees this key is
                    // present and non-empty here, so the old `?? null` was dead
                    // code — dropping it is behaviour-identical (phpstan flags it
                    // once the body lives in its own class).
                    'uniqueId' => $pageData['uniqueId'],
                    'title' => $pageData['title'] ?? 'Untitled',
                    'path' => $pageData['path'] ?? '',
                    'score' => $score,
                    'matches' => array_slice($matches, 0, 3), // Limit to 3 matches per page
                    'matchCount' => count($matches)
                ];
            }
        }

        // Sort by score (highest first)
        usort($results, function($a, $b) {
            return $b['score'] - $a['score'];
        });

        // Limit to top 20 results
        return array_slice($results, 0, 20);
    }
}
