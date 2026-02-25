import en from './en.js';
import nl from './nl.js';
import de from './de.js';
import fr from './fr.js';

const jokesByLanguage = { en, nl, de, fr };

/**
 * Get jokes for a language, falling back to English.
 * @param {string} lang - Language code (e.g. 'nl', 'en', 'de', 'fr', 'nl_NL')
 * @returns {string[]} Array of joke strings
 */
export function getJokes(lang) {
	const normalized = lang?.split(/[-_]/)[0]?.toLowerCase() || 'en';
	return jokesByLanguage[normalized] || jokesByLanguage.en;
}
