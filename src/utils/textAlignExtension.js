import { Extension } from '@tiptap/core'

/**
 * Custom TextAlign extension that uses CSS classes instead of inline styles.
 * This ensures alignment survives DOMPurify sanitization (which allows 'class'
 * but not 'style') and produces cleaner markdown output.
 */
export const TextAlign = Extension.create({
	name: 'textAlign',

	addOptions() {
		return {
			types: [],
			alignments: ['left', 'center', 'right'],
			defaultAlignment: 'left',
		}
	},

	addGlobalAttributes() {
		return [
			{
				types: this.options.types,
				attributes: {
					textAlign: {
						default: this.options.defaultAlignment,
						parseHTML: (element) => {
							// Check CSS classes first (our format)
							for (const alignment of this.options.alignments) {
								if (element.classList.contains(`text-align-${alignment}`)) {
									return alignment
								}
							}
							// Fallback: read inline style (copy-paste from Word/Docs)
							const styleAlignment = element.style.textAlign
							if (this.options.alignments.includes(styleAlignment)) {
								return styleAlignment
							}
							return this.options.defaultAlignment
						},
						renderHTML: (attributes) => {
							if (!attributes.textAlign || attributes.textAlign === this.options.defaultAlignment) {
								return {}
							}
							return { class: `text-align-${attributes.textAlign}` }
						},
					},
				},
			},
		]
	},

	addCommands() {
		return {
			setTextAlign: (alignment) => ({ commands }) => {
				if (!this.options.alignments.includes(alignment)) {
					return false
				}
				return this.options.types
					.map((type) => commands.updateAttributes(type, { textAlign: alignment }))
					.some((response) => response)
			},
			unsetTextAlign: () => ({ commands }) => {
				return this.options.types
					.map((type) => commands.resetAttributes(type, 'textAlign'))
					.some((response) => response)
			},
		}
	},

	addKeyboardShortcuts() {
		return {
			'Mod-Shift-l': () => this.editor.commands.setTextAlign('left'),
			'Mod-Shift-e': () => this.editor.commands.setTextAlign('center'),
			'Mod-Shift-r': () => this.editor.commands.setTextAlign('right'),
		}
	},
})
