<?php
declare(strict_types=1);

namespace OCA\IntraVox\Service\News;

/**
 * Pure content-derivation helpers for the news-widget feed.
 *
 * News tiles need a short text preview and (optionally) a hero image.
 * Producing either of those is a structural walk over the page-layout
 * JSON, independent of filesystem or permissions — perfect fit for an
 * isolated, testable service.
 *
 * Stateless. The same input always produces the same output.
 */
final class NewsContentExtractor {
    /**
     * Find the first non-empty text widget in the page layout, strip
     * markup + markdown, collapse whitespace and truncate at a word
     * boundary near the requested length. Returns '' when there is
     * no text content to extract.
     */
    public function getExcerpt(array $pageData, int $length = 150): string {
        $rows = $pageData['layout']['rows'] ?? null;
        if (!is_array($rows)) {
            return '';
        }

        foreach ($rows as $row) {
            $widgets = $row['widgets'] ?? null;
            if (!is_array($widgets)) {
                continue;
            }
            foreach ($widgets as $widget) {
                if (($widget['type'] ?? '') !== 'text' || empty($widget['content'])) {
                    continue;
                }
                $text = strip_tags($widget['content']);
                $text = $this->stripMarkdown($text);
                $text = preg_replace('/\s+/', ' ', $text);
                $text = trim($text);
                if ($text === '') {
                    continue;
                }
                return $this->truncateAtWordBoundary($text, $length);
            }
        }
        return '';
    }

    /**
     * Locate the first image widget — header row first, then main rows —
     * and return its src + mediaFolder reference, or null when the page
     * has no images.
     *
     * @return array{src: string, mediaFolder: string}|null
     */
    public function getFirstImage(array $pageData): ?array {
        $headerWidgets = $pageData['layout']['headerRow']['widgets'] ?? null;
        if (is_array($headerWidgets)) {
            foreach ($headerWidgets as $widget) {
                $image = $this->imageFromWidget($widget);
                if ($image !== null) {
                    return $image;
                }
            }
        }

        $rows = $pageData['layout']['rows'] ?? null;
        if (is_array($rows)) {
            foreach ($rows as $row) {
                $widgets = $row['widgets'] ?? null;
                if (!is_array($widgets)) {
                    continue;
                }
                foreach ($widgets as $widget) {
                    $image = $this->imageFromWidget($widget);
                    if ($image !== null) {
                        return $image;
                    }
                }
            }
        }

        return null;
    }

    /**
     * Strip the subset of markdown that survives `strip_tags()`. Aims
     * for clean reading-order text, not 100% CommonMark coverage.
     */
    public function stripMarkdown(string $text): string {
        $patterns = [
            '/\*\*(.+?)\*\*/',         // bold **
            '/__(.+?)__/',             // bold __
            '/\*(.+?)\*/',             // italic *
            '/_(.+?)_/',               // italic _
            '/~~(.+?)~~/',             // strikethrough
            '/\[([^\]]+)\]\([^)]+\)/', // [text](url)
            '/!\[([^\]]*)\]\([^)]+\)/', // ![alt](url)
            '/`([^`]+)`/',             // inline `code`
        ];
        $text = preg_replace($patterns, '$1', $text);

        $lineLevel = [
            '/^#{1,6}\s+/m',        // headers
            '/^>\s+/m',             // blockquote
            '/^[\-\*\+]\s+/m',      // unordered list bullet
            '/^\d+\.\s+/m',         // ordered list marker
            '/^[\-\*_]{3,}\s*$/m',  // horizontal rule
        ];
        return preg_replace($lineLevel, '', $text);
    }

    /**
     * @return array{src: string, mediaFolder: string}|null
     */
    private function imageFromWidget(array $widget): ?array {
        if (($widget['type'] ?? '') !== 'image' || empty($widget['src'])) {
            return null;
        }
        return [
            'src' => $widget['src'],
            'mediaFolder' => $widget['mediaFolder'] ?? 'page',
        ];
    }

    private function truncateAtWordBoundary(string $text, int $length): string {
        if (mb_strlen($text) <= $length) {
            return $text;
        }
        $text = mb_substr($text, 0, $length);
        $lastSpace = mb_strrpos($text, ' ');
        if ($lastSpace !== false && $lastSpace > $length * 0.7) {
            $text = mb_substr($text, 0, $lastSpace);
        }
        return $text . '...';
    }
}
