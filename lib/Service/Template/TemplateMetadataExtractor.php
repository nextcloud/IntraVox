<?php
declare(strict_types=1);

namespace OCA\IntraVox\Service\Template;

/**
 * Distil a preview-friendly metadata summary from a raw template/page
 * structure. Used by the template gallery to show "what does this look
 * like" badges (column count, widget mix, complexity) without rendering
 * the full page.
 *
 * Pure function: a `layout` array in, a flat summary array out. Living
 * here rather than in PageService means the template gallery's preview
 * logic can evolve (e.g. new complexity buckets, new widget summaries)
 * without touching anything that handles real page storage.
 */
final class TemplateMetadataExtractor {
    /**
     * @param array $content Decoded template/page JSON (expects layout.rows + layout.sideColumns)
     * @return array{
     *     hasHeaderRow: bool,
     *     columnCount: int,
     *     rowCount: int,
     *     widgetTypes: array<int, string>,
     *     widgetCount: int,
     *     backgroundColor: string,
     *     hasSidebars: bool,
     *     hasCollapsible: bool,
     *     complexity: string,
     * }
     */
    public function extract(array $content): array {
        $layout = $content['layout'] ?? [];
        $rows = $layout['rows'] ?? [];
        $sideColumns = $layout['sideColumns'] ?? [];

        $widgetTypes = [];
        $widgetCount = 0;
        $maxColumns = 1;
        $hasCollapsible = false;
        $firstBackgroundColor = '';

        foreach ($rows as $row) {
            $rowColumns = $row['columns'] ?? 1;
            $maxColumns = max($maxColumns, $rowColumns);

            if (!empty($row['collapsible'])) {
                $hasCollapsible = true;
            }
            if ($firstBackgroundColor === '' && !empty($row['backgroundColor'])) {
                $firstBackgroundColor = $row['backgroundColor'];
            }
            if (isset($row['widgets'])) {
                foreach ($row['widgets'] as $widget) {
                    if (isset($widget['type'])) {
                        $widgetTypes[] = $widget['type'];
                        $widgetCount++;
                    }
                }
            }
        }

        $hasSidebars = false;
        foreach ($sideColumns as $column) {
            if (!empty($column['enabled']) && !empty($column['widgets'])) {
                $hasSidebars = true;
                foreach ($column['widgets'] as $widget) {
                    if (isset($widget['type'])) {
                        $widgetTypes[] = $widget['type'];
                        $widgetCount++;
                    }
                }
            }
        }

        $complexity = 'simple';
        if ($widgetCount > 10 || $hasCollapsible || $hasSidebars) {
            $complexity = 'complex';
        } elseif ($widgetCount > 5 || $maxColumns > 2) {
            $complexity = 'moderate';
        }

        return [
            'hasHeaderRow' => isset($rows[0]) && !empty($rows[0]['backgroundColor']),
            'columnCount' => $maxColumns,
            'rowCount' => count($rows),
            'widgetTypes' => array_values(array_unique($widgetTypes)),
            'widgetCount' => $widgetCount,
            'backgroundColor' => $firstBackgroundColor,
            'hasSidebars' => $hasSidebars,
            'hasCollapsible' => $hasCollapsible,
            'complexity' => $complexity,
        ];
    }
}
