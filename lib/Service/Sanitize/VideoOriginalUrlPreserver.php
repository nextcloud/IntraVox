<?php
declare(strict_types=1);

namespace OCA\IntraVox\Service\Sanitize;

/**
 * Preserve a video widget's originalSrc across a page save (cluster B, Phase 5).
 *
 * When a page is re-saved, a video whose src/originalSrc was dropped (e.g. because
 * the domain whitelist changed and the widget was blocked) has its originalSrc
 * carried over from the previously-saved version, so the URL is never lost and can
 * be re-enabled if the admin whitelists the domain again.
 *
 * Pure array transformation with no collaborators, extracted verbatim from
 * PageService; stateless, so no constructor.
 */
class VideoOriginalUrlPreserver {

    /**
     * Return $newData with each video widget's originalSrc restored from the
     * matching widget in $existingData (matched by widget id).
     *
     * @param array<string, mixed> $newData
     * @param array<string, mixed> $existingData
     * @return array<string, mixed>
     */
    public function preserve(array $newData, array $existingData): array {
        // Build a map of existing video widgets by their ID
        $existingVideos = [];
        $this->collectVideoWidgets($existingData, $existingVideos);

        // Update new data with preserved originalSrc values
        $this->updateVideoWidgetsWithOriginalUrls($newData, $existingVideos);

        return $newData;
    }

    /**
     * Collect all video widgets from page data into a map keyed by widget ID
     */
    private function collectVideoWidgets(array $data, array &$videos): void {
        // Process main rows
        if (isset($data['layout']['rows']) && is_array($data['layout']['rows'])) {
            foreach ($data['layout']['rows'] as $row) {
                if (isset($row['widgets']) && is_array($row['widgets'])) {
                    foreach ($row['widgets'] as $widget) {
                        if (($widget['type'] ?? '') === 'video' && isset($widget['id'])) {
                            $videos[$widget['id']] = $widget;
                        }
                    }
                }
            }
        }

        // Process side columns
        if (isset($data['layout']['sideColumns']) && is_array($data['layout']['sideColumns'])) {
            foreach (['left', 'right'] as $side) {
                if (isset($data['layout']['sideColumns'][$side]['widgets']) && is_array($data['layout']['sideColumns'][$side]['widgets'])) {
                    foreach ($data['layout']['sideColumns'][$side]['widgets'] as $widget) {
                        if (($widget['type'] ?? '') === 'video' && isset($widget['id'])) {
                            $videos[$widget['id']] = $widget;
                        }
                    }
                }
            }
        }

        // Process header row
        if (isset($data['layout']['headerRow']['widgets']) && is_array($data['layout']['headerRow']['widgets'])) {
            foreach ($data['layout']['headerRow']['widgets'] as $widget) {
                if (($widget['type'] ?? '') === 'video' && isset($widget['id'])) {
                    $videos[$widget['id']] = $widget;
                }
            }
        }
    }

    /**
     * Update video widgets in new data with originalSrc from existing widgets
     */
    private function updateVideoWidgetsWithOriginalUrls(array &$data, array $existingVideos): void {
        // Process main rows
        if (isset($data['layout']['rows']) && is_array($data['layout']['rows'])) {
            foreach ($data['layout']['rows'] as $rowIndex => &$row) {
                if (isset($row['widgets']) && is_array($row['widgets'])) {
                    foreach ($row['widgets'] as $widgetIndex => &$widget) {
                        $this->preserveWidgetOriginalUrl($widget, $existingVideos);
                    }
                }
            }
        }

        // Process side columns
        if (isset($data['layout']['sideColumns']) && is_array($data['layout']['sideColumns'])) {
            foreach (['left', 'right'] as $side) {
                if (isset($data['layout']['sideColumns'][$side]['widgets']) && is_array($data['layout']['sideColumns'][$side]['widgets'])) {
                    foreach ($data['layout']['sideColumns'][$side]['widgets'] as $widgetIndex => &$widget) {
                        $this->preserveWidgetOriginalUrl($widget, $existingVideos);
                    }
                }
            }
        }

        // Process header row
        if (isset($data['layout']['headerRow']['widgets']) && is_array($data['layout']['headerRow']['widgets'])) {
            foreach ($data['layout']['headerRow']['widgets'] as $widgetIndex => &$widget) {
                $this->preserveWidgetOriginalUrl($widget, $existingVideos);
            }
        }
    }

    /**
     * Preserve originalSrc for a single video widget
     */
    private function preserveWidgetOriginalUrl(array &$widget, array $existingVideos): void {
        if (($widget['type'] ?? '') !== 'video') {
            return;
        }

        // Skip local videos - they don't have originalSrc
        if (($widget['provider'] ?? '') === 'local') {
            return;
        }

        $widgetId = $widget['id'] ?? null;
        if ($widgetId && isset($existingVideos[$widgetId])) {
            $existing = $existingVideos[$widgetId];

            // If the new widget has no src or originalSrc, but the existing one does,
            // preserve the originalSrc so the URL isn't lost
            $newSrc = $widget['src'] ?? '';
            $newOriginalSrc = $widget['originalSrc'] ?? '';
            $existingOriginalSrc = $existing['originalSrc'] ?? '';
            $existingSrc = $existing['src'] ?? '';

            // Preserve originalSrc: use existing originalSrc if new one is empty
            if (empty($newOriginalSrc)) {
                if (!empty($existingOriginalSrc)) {
                    $widget['originalSrc'] = $existingOriginalSrc;
                } elseif (!empty($existingSrc)) {
                    // Fallback: use existing src as originalSrc
                    $widget['originalSrc'] = $existingSrc;
                }
            }

            // If new src is empty but we have originalSrc, keep it for re-validation
            if (empty($newSrc) && !empty($widget['originalSrc'] ?? '')) {
                // The sanitizeWidget function will re-validate against current whitelist
                // and either allow it (setting src) or block it (keeping blocked=true)
            }
        }
    }
}
