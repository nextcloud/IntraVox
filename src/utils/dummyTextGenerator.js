import { Extension } from '@tiptap/core';
import { Plugin, PluginKey } from '@tiptap/pm/state';
import { getJokes } from '../data/jokes/index.js';

/**
 * Dummy Text Generator — Easter Egg for IntraVox Text Widget
 *
 * Type =dadjokes(3,5) or =lorem(2,4) on an empty line
 * and press Enter to generate dummy content.
 *
 * Inspired by Microsoft Word's =rand(p,s) command.
 */

const loremSentences = [
	'Lorem ipsum dolor sit amet, consectetur adipiscing elit.',
	'Sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.',
	'Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris.',
	'Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore.',
	'Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia.',
	'Nemo enim ipsam voluptatem quia voluptas sit aspernatur aut odit aut fugit.',
	'Neque porro quisquam est, qui dolorem ipsum quia dolor sit amet.',
	'Ut enim ad minima veniam, quis nostrum exercitationem ullam corporis.',
	'Quis autem vel eum iure reprehenderit qui in ea voluptate velit esse.',
	'At vero eos et accusamus et iusto odio dignissimos ducimus qui blanditiis.',
	'Nam libero tempore, cum soluta nobis est eligendi optio cumque nihil impedit.',
	'Temporibus autem quibusdam et aut officiis debitis aut rerum necessitatibus.',
	'Itaque earum rerum hic tenetur a sapiente delectus, ut aut reiciendis.',
	'Nulla facilisi morbi tempus iaculis urna id volutpat lacus laoreet.',
	'Viverra accumsan in nisl nisi scelerisque eu ultrices vitae auctor.',
	'Pellentesque habitant morbi tristique senectus et netus et malesuada fames.',
	'Cras tincidunt lobortis feugiat vivamus at augue eget arcu dictum.',
	'Ultrices sagittis orci a scelerisque purus semper eget duis at.',
	'Amet consectetur adipiscing elit pellentesque habitant morbi tristique senectus.',
	'Faucibus interdum posuere lorem ipsum dolor sit amet consectetur adipiscing.',
];

const sectionLabels = {
	en: 'Dad Jokes',
	nl: 'Flauwe Grappen',
	de: 'Flachwitze',
	fr: 'Blagues de Papa',
};

/**
 * Localized labels for lorem ipsum rich formatting sections.
 */
const loremLabels = {
	en: {
		heading: 'Section',
		quote: 'Notable Quote',
		items: 'Key Points',
		table: 'Overview',
		colA: 'Category',
		colB: 'Description',
		colC: 'Status',
		statuses: ['Active', 'Pending', 'Complete', 'In Review', 'Scheduled'],
		steps: 'Steps',
		note: 'Additional Notes',
	},
	nl: {
		heading: 'Sectie',
		quote: 'Opvallend Citaat',
		items: 'Kernpunten',
		table: 'Overzicht',
		colA: 'Categorie',
		colB: 'Beschrijving',
		colC: 'Status',
		statuses: ['Actief', 'In afwachting', 'Voltooid', 'In beoordeling', 'Gepland'],
		steps: 'Stappen',
		note: 'Aanvullende Opmerkingen',
	},
	de: {
		heading: 'Abschnitt',
		quote: 'Bemerkenswertes Zitat',
		items: 'Kernpunkte',
		table: 'Übersicht',
		colA: 'Kategorie',
		colB: 'Beschreibung',
		colC: 'Status',
		statuses: ['Aktiv', 'Ausstehend', 'Abgeschlossen', 'In Prüfung', 'Geplant'],
		steps: 'Schritte',
		note: 'Zusätzliche Hinweise',
	},
	fr: {
		heading: 'Section',
		quote: 'Citation Notable',
		items: 'Points Clés',
		table: 'Aperçu',
		colA: 'Catégorie',
		colB: 'Description',
		colC: 'Statut',
		statuses: ['Actif', 'En attente', 'Terminé', 'En révision', 'Planifié'],
		steps: 'Étapes',
		note: 'Notes Complémentaires',
	},
};

function getLoremLabels(lang) {
	const normalized = lang?.split(/[-_]/)[0]?.toLowerCase() || 'en';
	return loremLabels[normalized] || loremLabels.en;
}

/**
 * Escape HTML special characters
 */
function escapeHtml(text) {
	return text
		.replace(/&/g, '&amp;')
		.replace(/</g, '&lt;')
		.replace(/>/g, '&gt;')
		.replace(/"/g, '&quot;');
}

/**
 * Get localized section label for dad jokes
 */
function getSectionLabel(lang) {
	const normalized = lang?.split(/[-_]/)[0]?.toLowerCase() || 'en';
	return sectionLabels[normalized] || sectionLabels.en;
}

/**
 * Split a joke into setup and punchline for rich formatting.
 * Question-answer jokes split on "?", statement jokes on first comma/period.
 */
function splitJoke(joke) {
	const questionIndex = joke.indexOf('?');
	if (questionIndex !== -1 && questionIndex < joke.length - 1) {
		return {
			setup: joke.substring(0, questionIndex + 1),
			punchline: joke.substring(questionIndex + 1).trim(),
		};
	}

	// Statement jokes: split on first period, comma+space, or semicolon in the first 60%
	const midpoint = Math.floor(joke.length * 0.6);
	for (const delimiter of ['. ', ', ', '; ']) {
		const idx = joke.indexOf(delimiter);
		if (idx !== -1 && idx < midpoint) {
			return {
				setup: joke.substring(0, idx + delimiter.trimEnd().length),
				punchline: joke.substring(idx + delimiter.length).trim(),
			};
		}
	}

	// Fallback: no split
	return { setup: joke, punchline: '' };
}

/**
 * Shuffle array using Fisher-Yates algorithm (returns new array)
 */
function shuffle(arr) {
	const a = [...arr];
	for (let i = a.length - 1; i > 0; i--) {
		const j = Math.floor(Math.random() * (i + 1));
		[a[i], a[j]] = [a[j], a[i]];
	}
	return a;
}

/**
 * Parse a dummy text command like =dadjokes(3,5) or =lorem()
 * Returns null if text is not a valid command.
 */
export function matchDummyCommand(text) {
	const match = text.trim().match(/^=(dadjokes|lorem)\((\d*),?\s*(\d*)\)$/i);
	if (!match) return null;

	const type = match[1].toLowerCase();
	const paragraphs = Math.min(parseInt(match[2]) || 3, 20);
	const sentences = Math.min(parseInt(match[3]) || 3, 20);

	return { type, paragraphs, sentences };
}

/**
 * Generate a single lorem sentence with inline formatting marks.
 * Applies bold, italic, underline, strikethrough, code, or link to a fragment.
 */
function formatLoremSentence(sentence, markType) {
	const escaped = escapeHtml(sentence);
	// Find a word boundary roughly in the middle to wrap a fragment
	const words = escaped.split(' ');
	if (words.length < 4) return escaped;

	const start = Math.floor(words.length * 0.25);
	const end = Math.min(start + 2, words.length - 1);
	const before = words.slice(0, start).join(' ');
	const fragment = words.slice(start, end).join(' ');
	const after = words.slice(end).join(' ');

	switch (markType) {
	case 'strong':
		return `${before} <strong>${fragment}</strong> ${after}`;
	case 'em':
		return `${before} <em>${fragment}</em> ${after}`;
	case 'u':
		return `${before} <u>${fragment}</u> ${after}`;
	case 's':
		return `${before} <s>${fragment}</s> ${after}`;
	case 'code':
		return `${before} <code>${fragment}</code> ${after}`;
	default:
		return escaped;
	}
}

/**
 * Generate dummy text as HTML with rich formatting.
 * Both commands showcase the text widget's formatting capabilities.
 *
 * =dadjokes: headings, numbered lists, bold/italic
 * =lorem: headings, blockquotes, bullet lists, tables, ordered lists, inline marks
 *
 * @param {object} command - { type, paragraphs, sentences }
 * @param {string} [lang='en'] - Language code
 * @returns {string} HTML string
 */
export function generateDummyText(command, lang = 'en') {
	const { type, paragraphs, sentences } = command;

	if (type === 'lorem') {
		return generateRichLorem(paragraphs, sentences, lang);
	}

	// Dad jokes: rich formatting
	return generateDadJokes(paragraphs, sentences, lang);
}

/**
 * Generate rich Lorem Ipsum content that showcases all text widget formatting.
 * Each paragraph uses a different formatting pattern that rotates through:
 * 1. h2 heading + paragraph with bold/italic
 * 2. blockquote
 * 3. bullet list (ul)
 * 4. table with header
 * 5. h3 heading + ordered list (ol)
 * 6. paragraph with mixed inline marks (code, underline, strikethrough)
 */
function generateRichLorem(paragraphs, sentences, lang) {
	const shuffled = shuffle(loremSentences);
	const labels = getLoremLabels(lang);
	let idx = 0;
	let sectionNum = 0;
	let html = '';

	function nextSentences(count) {
		const result = [];
		for (let i = 0; i < count; i++) {
			result.push(shuffled[idx % shuffled.length]);
			idx++;
		}
		return result;
	}

	for (let p = 0; p < paragraphs; p++) {
		const pattern = p % 6;
		sectionNum++;

		switch (pattern) {
		case 0: {
			// h2 heading + paragraph with bold and italic fragments
			const sents = nextSentences(sentences);
			html += `<h2>${escapeHtml(labels.heading)} ${sectionNum}</h2>`;
			html += '<p>';
			html += sents.map((s, i) => {
				if (i === 0) return formatLoremSentence(s, 'strong');
				if (i === 1) return formatLoremSentence(s, 'em');
				return escapeHtml(s);
			}).join(' ');
			html += '</p>';
			break;
		}
		case 1: {
			// blockquote
			const sents = nextSentences(sentences);
			html += '<blockquote><p>';
			html += sents.map(s => escapeHtml(s)).join(' ');
			html += '</p></blockquote>';
			break;
		}
		case 2: {
			// h3 heading + unordered list
			const sents = nextSentences(sentences);
			html += `<h3>${escapeHtml(labels.items)}</h3>`;
			html += '<ul>';
			html += sents.map(s => `<li>${formatLoremSentence(s, 'strong')}</li>`).join('');
			html += '</ul>';
			break;
		}
		case 3: {
			// table with header and data rows
			const rowCount = Math.max(sentences, 2);
			const sents = nextSentences(rowCount);
			const statuses = shuffle(labels.statuses);
			html += `<h3>${escapeHtml(labels.table)}</h3>`;
			html += '<table><thead><tr>';
			html += `<th>${escapeHtml(labels.colA)}</th>`;
			html += `<th>${escapeHtml(labels.colB)}</th>`;
			html += `<th>${escapeHtml(labels.colC)}</th>`;
			html += '</tr></thead><tbody>';
			for (let r = 0; r < rowCount; r++) {
				const words = sents[r].split(' ');
				const cat = words.slice(0, 2).join(' ').replace(/[.,]/g, '');
				const desc = words.slice(2).join(' ');
				html += '<tr>';
				html += `<td><strong>${escapeHtml(cat)}</strong></td>`;
				html += `<td>${escapeHtml(desc)}</td>`;
				html += `<td><em>${escapeHtml(statuses[r % statuses.length])}</em></td>`;
				html += '</tr>';
			}
			html += '</tbody></table>';
			break;
		}
		case 4: {
			// h3 heading + ordered list
			const sents = nextSentences(sentences);
			html += `<h3>${escapeHtml(labels.steps)}</h3>`;
			html += '<ol>';
			html += sents.map(s => `<li>${formatLoremSentence(s, 'em')}</li>`).join('');
			html += '</ol>';
			break;
		}
		case 5: {
			// paragraph with mixed inline marks: code, underline, strikethrough
			const sents = nextSentences(Math.max(sentences, 3));
			html += `<h3>${escapeHtml(labels.note)}</h3>`;
			html += '<p>';
			html += sents.map((s, i) => {
				const marks = ['code', 'u', 's', 'strong', 'em'];
				return formatLoremSentence(s, marks[i % marks.length]);
			}).join(' ');
			html += '</p>';
			break;
		}
		}
	}

	return html;
}

/**
 * Generate dad jokes with rich formatting.
 */
function generateDadJokes(paragraphs, sentences, lang) {
	const jokes = getJokes(lang);
	const shuffled = shuffle(jokes);
	const label = escapeHtml(getSectionLabel(lang));
	let idx = 0;
	let html = '';

	for (let p = 0; p < paragraphs; p++) {
		html += `<h3>${label} #${p + 1}</h3>`;
		html += '<ol>';
		for (let s = 0; s < sentences; s++) {
			const joke = shuffled[idx % shuffled.length];
			const { setup, punchline } = splitJoke(joke);
			if (punchline) {
				html += `<li><strong>${escapeHtml(setup)}</strong> <em>${escapeHtml(punchline)}</em></li>`;
			} else {
				html += `<li><strong>${escapeHtml(setup)}</strong></li>`;
			}
			idx++;
		}
		html += '</ol>';
	}

	return html;
}

/**
 * TipTap Extension: DummyTextGenerator
 *
 * Uses a ProseMirror plugin to intercept Enter key with high priority,
 * before StarterKit's handlers. Checks if the current paragraph contains
 * a dummy text command (=dadjokes(), =lorem()) and replaces it
 * with generated content.
 */
export const DummyTextExtension = Extension.create({
	name: 'dummyTextGenerator',

	addProseMirrorPlugins() {
		const editor = this.editor;

		return [
			new Plugin({
				key: new PluginKey('dummyTextGenerator'),
				props: {
					handleKeyDown(view, event) {
						if (event.key !== 'Enter') return false;

						const { state } = view;
						const { $from } = state.selection;

						// Only handle paragraphs
						const node = $from.parent;
						if (node.type.name !== 'paragraph') return false;

						const text = node.textContent;
						const command = matchDummyCommand(text);
						if (!command) return false;

						// Prevent the default Enter
						event.preventDefault();

						// Find the position range of the current paragraph
						const from = $from.before();
						const to = $from.after();

						// Detect language from Nextcloud's document lang attribute
						const lang = document.documentElement.lang || 'en';

						// Generate rich HTML content
						const html = generateDummyText(command, lang);

						// Replace the command paragraph with generated content
						editor.chain()
							.focus()
							.deleteRange({ from, to })
							.insertContentAt(from, html)
							.run();

						return true;
					},
				},
			}),
		];
	},
});
