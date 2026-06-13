"use strict";
(self["webpackChunknextcloud_intravox"] = self["webpackChunknextcloud_intravox"] || []).push([["src_utils_textAlignExtension_js"],{

/***/ "./src/utils/textAlignExtension.js"
/*!*****************************************!*\
  !*** ./src/utils/textAlignExtension.js ***!
  \*****************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   TextAlign: () => (/* binding */ TextAlign)
/* harmony export */ });
/* harmony import */ var _tiptap_core__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @tiptap/core */ "./node_modules/@tiptap/core/dist/index.js");


/**
 * Custom TextAlign extension that uses CSS classes instead of inline styles.
 * This ensures alignment survives DOMPurify sanitization (which allows 'class'
 * but not 'style') and produces cleaner markdown output.
 */
const TextAlign = _tiptap_core__WEBPACK_IMPORTED_MODULE_0__.Extension.create({
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


/***/ }

}]);
//# sourceMappingURL=intravox-src_utils_textAlignExtension_js.7aea01d2.js.map