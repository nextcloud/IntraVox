<?php
declare(strict_types=1);

namespace OCA\IntraVox\Service\Sanitize;

/**
 * Sanitize user-provided HTML for safe rendering in pages.
 *
 * Allowed tags must mirror frontend src/utils/sanitization.js so the editor
 * (TipTap) and the saved-page rendering agree on what survives a save cycle.
 * Block-level + table + task-list elements are intentionally allowed because
 * TipTap emits them; XSS surface is mitigated by stripping event handlers,
 * javascript: URIs, and limiting CSS properties on inline style attributes.
 */
final class HtmlSanitizer {
    private const ALLOWED_TAGS =
        '<p><br><span><div>' .
        '<strong><b><em><i><u><s><del><mark><sub><sup>' .
        '<h1><h2><h3><h4><h5><h6>' .
        '<ul><ol><li>' .
        '<a>' .
        '<blockquote><pre><code>' .
        '<table><thead><tbody><tfoot><tr><th><td><caption><colgroup><col>' .
        '<input><label>';

    private const ALLOWED_STYLE_PROPERTIES = [
        'background-color',
        'text-align',
        'color',
        'width',
    ];

    public function sanitize(string $html): string {
        // Strip everything except the safe whitelist.
        $cleaned = strip_tags($html, self::ALLOWED_TAGS);

        // Remove event handlers and javascript: URIs that survived strip_tags.
        $cleaned = preg_replace('/on\w+\s*=\s*["\']?[^"\']*["\']?/i', '', $cleaned);
        $cleaned = preg_replace('/javascript:/i', '', $cleaned);

        // Allow only a curated set of CSS properties on inline style attributes,
        // and reject expressions / url() / javascript values.
        $cleaned = preg_replace_callback(
            '/style\s*=\s*["\']([^"\']*)["\']?/i',
            function ($matches): string {
                $allowed = [];
                preg_match_all('/([a-z-]+)\s*:\s*([^;]+)/i', $matches[1], $props, PREG_SET_ORDER);
                foreach ($props as $prop) {
                    $property = strtolower(trim($prop[1]));
                    $value = trim($prop[2]);
                    if (!in_array($property, self::ALLOWED_STYLE_PROPERTIES, true)) {
                        continue;
                    }
                    if (preg_match('/expression|javascript|url\s*\(/i', $value)) {
                        continue;
                    }
                    $allowed[] = $property . ': ' . $value;
                }
                return $allowed === [] ? '' : 'style="' . implode('; ', $allowed) . '"';
            },
            $cleaned
        );

        // Escape stray ampersands so the output is valid HTML.
        $cleaned = preg_replace('/&(?![a-z]+;|#[0-9]+;|#x[0-9a-f]+;)/i', '&amp;', $cleaned);

        return $cleaned;
    }

    /**
     * Repeatedly decode HTML entities until stable. Used to normalize
     * filter values that may have been encoded multiple times by editors
     * or by transport layers.
     */
    public function decodeEntitiesRecursive(string $value): string {
        $prev = null;
        while ($prev !== $value) {
            $prev = $value;
            $value = html_entity_decode($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        }
        return $value;
    }
}
