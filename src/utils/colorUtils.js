/**
 * Color utility functions for consistent background color handling across widgets.
 *
 * Nextcloud CSS variable reference, measured against a running instance rather
 * than assumed from the variable names:
 * - --color-primary-element: the main theme color (dark blue), needs white text
 * - --color-primary-element-light: a light tint of the theme color, needs dark text
 * - --color-primary: alias for primary-element
 * - --color-error: #FFE7E7, a pale pink BACKGROUND tint, needs dark text
 * - --color-success: #D8F3DA, a pale green tint, needs dark text
 * - --color-warning: #FFEEC5, a pale amber tint, needs dark text
 * - --color-background-hover: light gray hover, needs dark text
 * - --color-background-dark: slightly darker gray, needs dark text
 *
 * The three status colours are the trap: their names suggest the saturated
 * red/green/amber that --color-*-text is drawn in, but the variables themselves
 * are the pale backgrounds those texts sit ON. White text on them measures
 * 1.15-1.18:1 -- invisible -- against 13.5:1 for dark text. Nextcloud ships the
 * matching --color-error-text / -success-text / -warning-text for the foreground.
 */

/**
 * Dark background colors that require light/white text.
 */
export const DARK_BACKGROUNDS = [
  'var(--color-primary-element)',
  'var(--color-primary)',
];

/**
 * Light background colors that require dark text.
 * These are explicitly "light" backgrounds, distinct from the default page background.
 */
export const LIGHT_BACKGROUNDS = [
  'var(--color-primary-element-light)',
  'var(--color-background-hover)',
  'var(--color-background-dark)',
  'var(--color-error)',
  'var(--color-success)',
  'var(--color-warning)',
];

/**
 * Check if a background color is considered "dark" and requires light text
 * @param {string} color - CSS color value (typically a CSS variable)
 * @returns {boolean} - True if the color is dark and needs white/light text
 */
export function isDarkBackground(color) {
  return DARK_BACKGROUNDS.includes(color);
}

/**
 * Check if a background color is a known light tint (not transparent/default)
 * @param {string} color - CSS color value
 * @returns {boolean}
 */
export function isLightBackground(color) {
  return LIGHT_BACKGROUNDS.includes(color);
}

/**
 * Get the effective background color for a widget
 * Widget's own backgroundColor takes precedence over row backgroundColor
 * @param {string} widgetBg - Widget's backgroundColor
 * @param {string} rowBg - Row's backgroundColor
 * @returns {string} - The effective background color to use
 */
export function getEffectiveBackgroundColor(widgetBg, rowBg) {
  return widgetBg || rowBg || '';
}
