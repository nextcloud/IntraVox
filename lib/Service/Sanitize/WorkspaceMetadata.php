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

    public static function page(array $page): array {
        $metadata = [];
        foreach (['hidePageTitleBar', 'disableSocial'] as $key) {
            if (is_bool($page[$key] ?? null)) {
                $metadata[$key] = $page[$key];
            }
        }
        if (isset($page['entity']) || isset($page['excomOnly'])) {
            $metadata += self::item($page);
        }
        if (isset($page['settings']) && is_array($page['settings'])) {
            $metadata['settings'] = [];
            foreach (['allowReactions', 'allowComments', 'allowCommentReactions'] as $key) {
                $metadata['settings'][$key] = is_bool($page['settings'][$key] ?? null) ? $page['settings'][$key] : null;
            }
        }
        return $metadata;
    }
}
