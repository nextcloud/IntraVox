<?php
declare(strict_types=1);

namespace OCA\IntraVox\Service\Sanitize;

/**
 * Sanitize background / accent colors used in widget styling. Allows:
 *   - Nextcloud theme CSS variables (limited safe list)
 *   - Hex colors (#RGB / #RRGGBB)
 *   - rgb() / rgba()
 *   - 'transparent' and the empty string (= default)
 *
 * Anything else (named colors, custom CSS variables, expressions) is
 * replaced with the empty string so the renderer falls back to defaults.
 */
final class ColorSanitizer {
    private const ALLOWED_NAMED_VALUES = [
        'var(--color-primary-element)',
        'var(--color-primary-element-light)',
        'var(--color-background-hover)',
        'var(--color-border)',
        'transparent',
        'rgba(255,255,255,0.3)',
    ];

    public function sanitize(string $color): string {
        if ($color === '') {
            return '';
        }
        if (in_array($color, self::ALLOWED_NAMED_VALUES, true)) {
            return $color;
        }
        if (preg_match('/^#([0-9A-Fa-f]{3}|[0-9A-Fa-f]{6})$/', $color)) {
            return $color;
        }
        if (preg_match('/^rgba?\(\s*\d{1,3}\s*,\s*\d{1,3}\s*,\s*\d{1,3}\s*(,\s*[\d.]+\s*)?\)$/', $color)) {
            return $color;
        }
        return '';
    }
}
