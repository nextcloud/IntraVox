<?php
declare(strict_types=1);

namespace OCA\IntraVox\Service\Sanitize;

/**
 * Presentation metadata only. These values never grant a Nextcloud permission.
 */
final class WorkspaceMetadata {
    public static function item(array $item): array {
        return [
            'entity' => in_array($item['entity'] ?? '', ['all', 'cloud-carib', 'athena-cloud', 'excom'], true)
                ? $item['entity'] : 'all',
            'excomOnly' => ($item['excomOnly'] ?? false) === true,
        ];
    }

    public static function organogram(array $widget): array {
        return [
            'title' => strip_tags(is_string($widget['title'] ?? null) ? $widget['title'] : ''),
            'defaultExpanded' => ($widget['defaultExpanded'] ?? true) === true,
            'showCompanies' => ($widget['showCompanies'] ?? true) === true,
        ];
    }
}
