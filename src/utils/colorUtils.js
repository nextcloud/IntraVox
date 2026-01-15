/**
 * Color utility functions for consistent background color handling across widgets
 */

/**
 * Standard dark background colors that require light/white text
 * These are CSS variable names used throughout the application
 */
export const DARK_BACKGROUNDS = [
  'var(--color-primary-element)',
  'var(--color-primary-element-light)', // Backwards compatibility for existing data
  'var(--color-primary)',
  'var(--color-error)',
  'var(--color-success)',
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
 * Get the effective background color for a widget
 * Widget's own backgroundColor takes precedence over row backgroundColor
 * @param {string} widgetBg - Widget's backgroundColor
 * @param {string} rowBg - Row's backgroundColor
 * @returns {string} - The effective background color to use
 */
export function getEffectiveBackgroundColor(widgetBg, rowBg) {
  return widgetBg || rowBg || '';
}
