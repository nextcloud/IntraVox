<?php
declare(strict_types=1);

namespace OCA\IntraVox\Service\Search;

/**
 * Pure in-memory search helpers for IntraVox pages.
 *
 * `searchWidget` walks a single widget structure and returns ranked
 * match descriptors; `extractSnippet` produces the highlight-style
 * text surrounding a query hit. The actual filesystem-walk that
 * collects pages stays in PageService — these are the
 * format/scoring concerns extracted so they can evolve independently
 * (e.g. add new widget types to the search index without touching
 * page-discovery code).
 */
final class PageSearchHelper {
    /**
     * Return matches found within a widget, scored higher for more
     * prominent elements (heading > content > image-alt). Caller is
     * expected to lower-case the query first so we can use
     * mb_stripos() exclusively.
     *
     * @param array $widget Decoded widget structure
     * @param string $query Lowercased search query
     * @return array<int, array{type: string, text: string, score: int}>
     */
    public function searchWidget(array $widget, string $query): array {
        $matches = [];
        $type = $widget['type'] ?? '';

        if ($type === 'text' && isset($widget['content']) && mb_stripos($widget['content'], $query) !== false) {
            $matches[] = [
                'type' => 'content',
                'text' => $this->extractSnippet($widget['content'], $query),
                'score' => 3,
            ];
        }

        if ($type === 'heading' && isset($widget['content']) && mb_stripos($widget['content'], $query) !== false) {
            $matches[] = [
                'type' => 'heading',
                'text' => $widget['content'],
                'score' => 5,
            ];
        }

        if ($type === 'image' && !empty($widget['alt']) && mb_stripos($widget['alt'], $query) !== false) {
            $matches[] = [
                'type' => 'image',
                'text' => $widget['alt'],
                'score' => 2,
            ];
        }

        if ($type === 'links' && !empty($widget['items'])) {
            foreach ($widget['items'] as $link) {
                $linkTitle = $link['title'] ?? '';
                $linkText = strip_tags($link['text'] ?? '');
                $linkSearchText = $linkTitle . ' ' . $linkText;
                if (mb_stripos($linkSearchText, $query) === false) {
                    continue;
                }
                $displayText = $linkTitle;
                if ($linkText !== '') {
                    $displayText .= ' - ' . $this->extractSnippet($linkText, $query, 60);
                }
                $matches[] = [
                    'type' => 'link',
                    'text' => $displayText,
                    'score' => 3,
                ];
            }
        }

        if ($type === 'file' && !empty($widget['name']) && mb_stripos($widget['name'], $query) !== false) {
            $matches[] = [
                'type' => 'file',
                'text' => $widget['name'],
                'score' => 2,
            ];
        }

        if ($type === 'video' && !empty($widget['title']) && mb_stripos($widget['title'], $query) !== false) {
            $matches[] = [
                'type' => 'video',
                'text' => $widget['title'],
                'score' => 2,
            ];
        }

        return $matches;
    }

    /**
     * Cut a snippet of `$contextLength` characters around the first
     * occurrence of `$query` in `$text`. Ellipses signal truncation on
     * either side; returns an opening snippet (no leading "...") when
     * the match isn't found, so callers can still render *something*.
     */
    public function extractSnippet(string $text, string $query, int $contextLength = 100): string {
        $text = strip_tags($text);
        $text = preg_replace('/[*_~`#]/', '', $text);

        $pos = mb_stripos($text, $query);
        if ($pos === false) {
            return mb_substr($text, 0, $contextLength) . '...';
        }

        $start = max(0, $pos - (int) ($contextLength / 2));
        $snippet = mb_substr($text, $start, $contextLength);

        $prefix = $start > 0 ? '...' : '';
        $suffix = (mb_strlen($text) > $start + $contextLength) ? '...' : '';

        return $prefix . trim($snippet) . $suffix;
    }
}
