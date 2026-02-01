/**
 * Color utility functions for consistent background color handling across widgets.
 *
 * Nextcloud CSS variable reference:
 * - --color-primary-element: the main theme color (typically dark blue), needs white text
 * - --color-primary-element-light: a light tint of the theme color, needs dark text
 * - --color-primary: alias for primary-element
 * - --color-error: red, needs white text
 * - --color-success: green, needs white text
 * - --color-warning: amber/orange, needs dark text
 * - --color-background-hover: light gray hover, needs dark text
 * - --color-background-dark: slightly darker gray, needs dark text
 */

/**
 * Dark background colors that require light/white text.
 */
export const DARK_BACKGROUNDS = [
  'var(--color-primary-element)',
  'var(--color-primary)',
  'var(--color-error)',
  'var(--color-success)',
];

/**
 * Light background colors that require dark text.
 * These are explicitly "light" backgrounds, distinct from the default page background.
 */
export const LIGHT_BACKGROUNDS = [
  'var(--color-primary-element-light)',
  'var(--color-background-hover)',
  'var(--color-background-dark)',
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
