"use strict";
(self["webpackChunknextcloud_intravox"] = self["webpackChunknextcloud_intravox"] || []).push([["src_components_PageSettingsModal_vue"],{

/***/ "./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/dist/stylePostLoader.js!./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/PageSettingsModal.vue?vue&type=style&index=0&id=009a970a&scoped=true&lang=css"
/*!******************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/dist/stylePostLoader.js!./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/PageSettingsModal.vue?vue&type=style&index=0&id=009a970a&scoped=true&lang=css ***!
  \******************************************************************************************************************************************************************************************************************************************************************/
(module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _node_modules_css_loader_dist_runtime_sourceMaps_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ../../node_modules/css-loader/dist/runtime/sourceMaps.js */ "./node_modules/css-loader/dist/runtime/sourceMaps.js");
/* harmony import */ var _node_modules_css_loader_dist_runtime_sourceMaps_js__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_node_modules_css_loader_dist_runtime_sourceMaps_js__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _node_modules_css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ../../node_modules/css-loader/dist/runtime/api.js */ "./node_modules/css-loader/dist/runtime/api.js");
/* harmony import */ var _node_modules_css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_node_modules_css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_1__);
// Imports


var ___CSS_LOADER_EXPORT___ = _node_modules_css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_1___default()((_node_modules_css_loader_dist_runtime_sourceMaps_js__WEBPACK_IMPORTED_MODULE_0___default()));
// Module
___CSS_LOADER_EXPORT___.push([module.id, `
.focus-trap[data-v-009a970a] {
	position: absolute;
	width: 0;
	height: 0;
	overflow: hidden;
}
.page-settings[data-v-009a970a] {
	padding: 16px 0;
}
.settings-section[data-v-009a970a] {
	padding: 0 16px;
}
.settings-section-header[data-v-009a970a] {
	display: flex;
	align-items: center;
	gap: 8px;
	margin: 0 0 8px 0;
	font-size: 16px;
	font-weight: 600;
}
.settings-icon[data-v-009a970a] {
	font-size: 20px;
}
.settings-section-desc[data-v-009a970a] {
	margin: 0 0 16px 0;
	font-size: 13px;
	color: var(--color-text-maxcontrast);
}
.setting-item[data-v-009a970a] {
	margin-bottom: 16px;
	padding: 12px;
	background: var(--color-background-hover);
	border-radius: var(--border-radius-large);
}
.setting-header[data-v-009a970a] {
	display: flex;
	align-items: center;
	justify-content: space-between;
	gap: 16px;
	margin-bottom: 4px;
}
.setting-label[data-v-009a970a] {
	font-weight: 500;
	color: var(--color-main-text);
}
.setting-status[data-v-009a970a] {
	font-size: 12px;
	color: var(--color-text-maxcontrast);
	font-style: italic;
}
.tri-state-select[data-v-009a970a] {
	min-width: 180px;
}
.setting-item--disabled[data-v-009a970a] {
	opacity: 0.6;
}
.setting-locked[data-v-009a970a] {
	padding: 6px 12px;
	font-size: 13px;
	color: var(--color-text-maxcontrast);
	background: var(--color-background-dark);
	border-radius: var(--border-radius);
	font-style: italic;
}
`, "",{"version":3,"sources":["webpack://./src/components/PageSettingsModal.vue"],"names":[],"mappings":";AAuOA;CACC,kBAAkB;CAClB,QAAQ;CACR,SAAS;CACT,gBAAgB;AACjB;AAEA;CACC,eAAe;AAChB;AAEA;CACC,eAAe;AAChB;AAEA;CACC,aAAa;CACb,mBAAmB;CACnB,QAAQ;CACR,iBAAiB;CACjB,eAAe;CACf,gBAAgB;AACjB;AAEA;CACC,eAAe;AAChB;AAEA;CACC,kBAAkB;CAClB,eAAe;CACf,oCAAoC;AACrC;AAEA;CACC,mBAAmB;CACnB,aAAa;CACb,yCAAyC;CACzC,yCAAyC;AAC1C;AAEA;CACC,aAAa;CACb,mBAAmB;CACnB,8BAA8B;CAC9B,SAAS;CACT,kBAAkB;AACnB;AAEA;CACC,gBAAgB;CAChB,6BAA6B;AAC9B;AAEA;CACC,eAAe;CACf,oCAAoC;CACpC,kBAAkB;AACnB;AAEA;CACC,gBAAgB;AACjB;AAEA;CACC,YAAY;AACb;AAEA;CACC,iBAAiB;CACjB,eAAe;CACf,oCAAoC;CACpC,wCAAwC;CACxC,mCAAmC;CACnC,kBAAkB;AACnB","sourcesContent":["<template>\n\t<NcDialog\n\t\t:open=\"true\"\n\t\t:name=\"t('intravox', 'Page Settings')\"\n\t\tsize=\"normal\"\n\t\t@close=\"$emit('close')\">\n\t\t<!-- Hidden element to capture initial focus and blur it -->\n\t\t<div ref=\"focusTrap\" tabindex=\"-1\" class=\"focus-trap\" />\n\t\t<div class=\"page-settings\">\n\t\t\t<!-- Engagement Section -->\n\t\t\t<div class=\"settings-section\">\n\t\t\t\t<h3 class=\"settings-section-header\">\n\t\t\t\t\t<span class=\"settings-icon\">💬</span>\n\t\t\t\t\t{{ t('intravox', 'Engagement') }}\n\t\t\t\t</h3>\n\t\t\t\t<p class=\"settings-section-desc\">\n\t\t\t\t\t{{ t('intravox', 'Override global engagement settings for this page.') }}\n\t\t\t\t</p>\n\n\t\t\t\t<!-- Allow Reactions -->\n\t\t\t\t<div class=\"setting-item\" :class=\"{ 'setting-item--disabled': !globalSettings.allowPageReactions }\">\n\t\t\t\t\t<div class=\"setting-header\">\n\t\t\t\t\t\t<span class=\"setting-label\">{{ t('intravox', 'Allow reactions on this page') }}</span>\n\t\t\t\t\t\t<NcSelect\n\t\t\t\t\t\t\tv-if=\"globalSettings.allowPageReactions\"\n\t\t\t\t\t\t\tv-model=\"localSettings.allowReactions\"\n\t\t\t\t\t\t\t:options=\"disableOnlyOptions\"\n\t\t\t\t\t\t\t:clearable=\"true\"\n\t\t\t\t\t\t\t:searchable=\"false\"\n\t\t\t\t\t\t\t:autofocus=\"false\"\n\t\t\t\t\t\t\t:placeholder=\"t('intravox', 'Use global setting')\"\n\t\t\t\t\t\t\tclass=\"tri-state-select\"\n\t\t\t\t\t\t\t@clear=\"localSettings.allowReactions = null\" />\n\t\t\t\t\t\t<span v-else class=\"setting-locked\">\n\t\t\t\t\t\t\t{{ t('intravox', 'Disabled globally') }}\n\t\t\t\t\t\t</span>\n\t\t\t\t\t</div>\n\t\t\t\t\t<span class=\"setting-status\">{{ getStatusText(localSettings.allowReactions, 'reactions') }}</span>\n\t\t\t\t</div>\n\n\t\t\t\t<!-- Allow Comments -->\n\t\t\t\t<div class=\"setting-item\" :class=\"{ 'setting-item--disabled': !globalSettings.allowComments }\">\n\t\t\t\t\t<div class=\"setting-header\">\n\t\t\t\t\t\t<span class=\"setting-label\">{{ t('intravox', 'Allow comments on this page') }}</span>\n\t\t\t\t\t\t<NcSelect\n\t\t\t\t\t\t\tv-if=\"globalSettings.allowComments\"\n\t\t\t\t\t\t\tv-model=\"localSettings.allowComments\"\n\t\t\t\t\t\t\t:options=\"disableOnlyOptions\"\n\t\t\t\t\t\t\t:clearable=\"true\"\n\t\t\t\t\t\t\t:searchable=\"false\"\n\t\t\t\t\t\t\t:autofocus=\"false\"\n\t\t\t\t\t\t\t:placeholder=\"t('intravox', 'Use global setting')\"\n\t\t\t\t\t\t\tclass=\"tri-state-select\"\n\t\t\t\t\t\t\t@clear=\"localSettings.allowComments = null\" />\n\t\t\t\t\t\t<span v-else class=\"setting-locked\">\n\t\t\t\t\t\t\t{{ t('intravox', 'Disabled globally') }}\n\t\t\t\t\t\t</span>\n\t\t\t\t\t</div>\n\t\t\t\t\t<span class=\"setting-status\">{{ getStatusText(localSettings.allowComments, 'comments') }}</span>\n\t\t\t\t</div>\n\n\t\t\t\t<!-- Allow Comment Reactions (only show if comments are enabled) -->\n\t\t\t\t<div v-if=\"showCommentReactionsOption\" class=\"setting-item\" :class=\"{ 'setting-item--disabled': !globalSettings.allowCommentReactions }\">\n\t\t\t\t\t<div class=\"setting-header\">\n\t\t\t\t\t\t<span class=\"setting-label\">{{ t('intravox', 'Allow reactions on comments') }}</span>\n\t\t\t\t\t\t<NcSelect\n\t\t\t\t\t\t\tv-if=\"globalSettings.allowCommentReactions\"\n\t\t\t\t\t\t\tv-model=\"localSettings.allowCommentReactions\"\n\t\t\t\t\t\t\t:options=\"disableOnlyOptions\"\n\t\t\t\t\t\t\t:clearable=\"true\"\n\t\t\t\t\t\t\t:searchable=\"false\"\n\t\t\t\t\t\t\t:autofocus=\"false\"\n\t\t\t\t\t\t\t:placeholder=\"t('intravox', 'Use global setting')\"\n\t\t\t\t\t\t\tclass=\"tri-state-select\"\n\t\t\t\t\t\t\t@clear=\"localSettings.allowCommentReactions = null\" />\n\t\t\t\t\t\t<span v-else class=\"setting-locked\">\n\t\t\t\t\t\t\t{{ t('intravox', 'Disabled globally') }}\n\t\t\t\t\t\t</span>\n\t\t\t\t\t</div>\n\t\t\t\t\t<span class=\"setting-status\">{{ getStatusText(localSettings.allowCommentReactions, 'commentReactions') }}</span>\n\t\t\t\t</div>\n\t\t\t</div>\n\t\t</div>\n\n\t\t<template #actions>\n\t\t\t<NcButton type=\"tertiary\" @click=\"$emit('close')\">\n\t\t\t\t{{ t('intravox', 'Cancel') }}\n\t\t\t</NcButton>\n\t\t\t<NcButton type=\"primary\" @click=\"save\">\n\t\t\t\t{{ t('intravox', 'Save') }}\n\t\t\t</NcButton>\n\t\t</template>\n\t</NcDialog>\n</template>\n\n<script>\nimport { NcDialog, NcButton, NcSelect } from '@nextcloud/vue'\nimport { translate as t } from '@nextcloud/l10n'\n\nexport default {\n\tname: 'PageSettingsModal',\n\tcomponents: {\n\t\tNcDialog,\n\t\tNcButton,\n\t\tNcSelect,\n\t},\n\tprops: {\n\t\tpageUniqueId: {\n\t\t\ttype: String,\n\t\t\trequired: true,\n\t\t},\n\t\tsettings: {\n\t\t\ttype: Object,\n\t\t\tdefault: () => ({\n\t\t\t\tallowReactions: null,\n\t\t\t\tallowComments: null,\n\t\t\t\tallowCommentReactions: null,\n\t\t\t}),\n\t\t},\n\t\tglobalSettings: {\n\t\t\ttype: Object,\n\t\t\tdefault: () => ({\n\t\t\t\tallowPageReactions: true,\n\t\t\t\tallowComments: true,\n\t\t\t\tallowCommentReactions: true,\n\t\t\t}),\n\t\t},\n\t},\n\temits: ['close', 'save'],\n\tdata() {\n\t\treturn {\n\t\t\tlocalSettings: {\n\t\t\t\tallowReactions: null,\n\t\t\t\tallowComments: null,\n\t\t\t\tallowCommentReactions: null,\n\t\t\t},\n\t\t}\n\t},\n\tcomputed: {\n\t\t// Only allow disabling per page when globally enabled\n\t\t// Cannot enable something that is globally disabled\n\t\tdisableOnlyOptions() {\n\t\t\treturn [\n\t\t\t\t{ value: false, label: t('intravox', 'Disabled') },\n\t\t\t]\n\t\t},\n\t\t// Check if comments are effectively enabled for this page\n\t\t// (globally enabled AND not disabled at page level)\n\t\tcommentsEffectivelyEnabled() {\n\t\t\t// If comments are globally disabled, they're off\n\t\t\tif (!this.globalSettings.allowComments) {\n\t\t\t\treturn false\n\t\t\t}\n\t\t\t// If comments are explicitly disabled for this page, they're off\n\t\t\t// Check both the option object format and direct value\n\t\t\tconst commentValue = this.localSettings.allowComments?.value ?? this.localSettings.allowComments\n\t\t\tif (commentValue === false) {\n\t\t\t\treturn false\n\t\t\t}\n\t\t\treturn true\n\t\t},\n\t\t// Only show comment reactions option if comments are enabled\n\t\tshowCommentReactionsOption() {\n\t\t\treturn this.commentsEffectivelyEnabled\n\t\t},\n\t},\n\tcreated() {\n\t\t// Initialize localSettings - null means use global setting (shown as placeholder)\n\t\t// Only set a value if page has an explicit override\n\t\tthis.localSettings.allowReactions = this.mapToOption(this.settings?.allowReactions)\n\t\tthis.localSettings.allowComments = this.mapToOption(this.settings?.allowComments)\n\t\tthis.localSettings.allowCommentReactions = this.mapToOption(this.settings?.allowCommentReactions)\n\t},\n\tmounted() {\n\t\t// Remove focus from any element after modal opens\n\t\tthis.$nextTick(() => {\n\t\t\tif (document.activeElement && document.activeElement !== document.body) {\n\t\t\t\tdocument.activeElement.blur()\n\t\t\t}\n\t\t})\n\t},\n\tmethods: {\n\t\tt(app, key, vars = {}) {\n\t\t\treturn t(app, key, vars)\n\t\t},\n\t\tmapToOption(value) {\n\t\t\t// null = no override, return null (will show placeholder)\n\t\t\tif (value === null || value === undefined) {\n\t\t\t\treturn null\n\t\t\t}\n\t\t\t// Only false (disabled) is a valid page-level override\n\t\t\t// true is not valid because you can't enable something globally disabled\n\t\t\tif (value === false) {\n\t\t\t\treturn this.disableOnlyOptions.find(opt => opt.value === false)\n\t\t\t}\n\t\t\treturn null\n\t\t},\n\t\tgetStatusText(option, type) {\n\t\t\tlet globalEnabled\n\t\t\tif (type === 'reactions') {\n\t\t\t\tglobalEnabled = this.globalSettings.allowPageReactions\n\t\t\t} else if (type === 'comments') {\n\t\t\t\tglobalEnabled = this.globalSettings.allowComments\n\t\t\t} else if (type === 'commentReactions') {\n\t\t\t\tglobalEnabled = this.globalSettings.allowCommentReactions\n\t\t\t}\n\n\t\t\t// If globally disabled, always show that\n\t\t\tif (!globalEnabled) {\n\t\t\t\treturn this.t('intravox', 'Disabled by administrator')\n\t\t\t}\n\n\t\t\tconst value = option?.value\n\t\t\tif (value === false) {\n\t\t\t\treturn this.t('intravox', 'Disabled for this page')\n\t\t\t}\n\t\t\t// null = inherit from global (which is enabled if we got here)\n\t\t\treturn this.t('intravox', 'Using global setting (enabled)')\n\t\t},\n\t\tsave() {\n\t\t\tthis.$emit('save', {\n\t\t\t\tallowReactions: this.localSettings.allowReactions?.value ?? null,\n\t\t\t\tallowComments: this.localSettings.allowComments?.value ?? null,\n\t\t\t\tallowCommentReactions: this.localSettings.allowCommentReactions?.value ?? null,\n\t\t\t})\n\t\t},\n\t},\n}\n</script>\n\n<style scoped>\n.focus-trap {\n\tposition: absolute;\n\twidth: 0;\n\theight: 0;\n\toverflow: hidden;\n}\n\n.page-settings {\n\tpadding: 16px 0;\n}\n\n.settings-section {\n\tpadding: 0 16px;\n}\n\n.settings-section-header {\n\tdisplay: flex;\n\talign-items: center;\n\tgap: 8px;\n\tmargin: 0 0 8px 0;\n\tfont-size: 16px;\n\tfont-weight: 600;\n}\n\n.settings-icon {\n\tfont-size: 20px;\n}\n\n.settings-section-desc {\n\tmargin: 0 0 16px 0;\n\tfont-size: 13px;\n\tcolor: var(--color-text-maxcontrast);\n}\n\n.setting-item {\n\tmargin-bottom: 16px;\n\tpadding: 12px;\n\tbackground: var(--color-background-hover);\n\tborder-radius: var(--border-radius-large);\n}\n\n.setting-header {\n\tdisplay: flex;\n\talign-items: center;\n\tjustify-content: space-between;\n\tgap: 16px;\n\tmargin-bottom: 4px;\n}\n\n.setting-label {\n\tfont-weight: 500;\n\tcolor: var(--color-main-text);\n}\n\n.setting-status {\n\tfont-size: 12px;\n\tcolor: var(--color-text-maxcontrast);\n\tfont-style: italic;\n}\n\n.tri-state-select {\n\tmin-width: 180px;\n}\n\n.setting-item--disabled {\n\topacity: 0.6;\n}\n\n.setting-locked {\n\tpadding: 6px 12px;\n\tfont-size: 13px;\n\tcolor: var(--color-text-maxcontrast);\n\tbackground: var(--color-background-dark);\n\tborder-radius: var(--border-radius);\n\tfont-style: italic;\n}\n</style>\n"],"sourceRoot":""}]);
// Exports
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (___CSS_LOADER_EXPORT___);


/***/ },

/***/ "./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/dist/stylePostLoader.js!./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/PageSettingsModal.vue?vue&type=style&index=0&id=009a970a&scoped=true&lang=css"
/*!**********************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/dist/stylePostLoader.js!./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/PageSettingsModal.vue?vue&type=style&index=0&id=009a970a&scoped=true&lang=css ***!
  \**********************************************************************************************************************************************************************************************************************************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! !../../node_modules/style-loader/dist/runtime/injectStylesIntoStyleTag.js */ "./node_modules/style-loader/dist/runtime/injectStylesIntoStyleTag.js");
/* harmony import */ var _node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _node_modules_style_loader_dist_runtime_styleDomAPI_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! !../../node_modules/style-loader/dist/runtime/styleDomAPI.js */ "./node_modules/style-loader/dist/runtime/styleDomAPI.js");
/* harmony import */ var _node_modules_style_loader_dist_runtime_styleDomAPI_js__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_node_modules_style_loader_dist_runtime_styleDomAPI_js__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _node_modules_style_loader_dist_runtime_insertBySelector_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! !../../node_modules/style-loader/dist/runtime/insertBySelector.js */ "./node_modules/style-loader/dist/runtime/insertBySelector.js");
/* harmony import */ var _node_modules_style_loader_dist_runtime_insertBySelector_js__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(_node_modules_style_loader_dist_runtime_insertBySelector_js__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var _node_modules_style_loader_dist_runtime_setAttributesWithoutAttributes_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! !../../node_modules/style-loader/dist/runtime/setAttributesWithoutAttributes.js */ "./node_modules/style-loader/dist/runtime/setAttributesWithoutAttributes.js");
/* harmony import */ var _node_modules_style_loader_dist_runtime_setAttributesWithoutAttributes_js__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(_node_modules_style_loader_dist_runtime_setAttributesWithoutAttributes_js__WEBPACK_IMPORTED_MODULE_3__);
/* harmony import */ var _node_modules_style_loader_dist_runtime_insertStyleElement_js__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! !../../node_modules/style-loader/dist/runtime/insertStyleElement.js */ "./node_modules/style-loader/dist/runtime/insertStyleElement.js");
/* harmony import */ var _node_modules_style_loader_dist_runtime_insertStyleElement_js__WEBPACK_IMPORTED_MODULE_4___default = /*#__PURE__*/__webpack_require__.n(_node_modules_style_loader_dist_runtime_insertStyleElement_js__WEBPACK_IMPORTED_MODULE_4__);
/* harmony import */ var _node_modules_style_loader_dist_runtime_styleTagTransform_js__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! !../../node_modules/style-loader/dist/runtime/styleTagTransform.js */ "./node_modules/style-loader/dist/runtime/styleTagTransform.js");
/* harmony import */ var _node_modules_style_loader_dist_runtime_styleTagTransform_js__WEBPACK_IMPORTED_MODULE_5___default = /*#__PURE__*/__webpack_require__.n(_node_modules_style_loader_dist_runtime_styleTagTransform_js__WEBPACK_IMPORTED_MODULE_5__);
/* harmony import */ var _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_dist_stylePostLoader_js_node_modules_vue_loader_dist_index_js_ruleSet_1_rules_5_use_0_PageSettingsModal_vue_vue_type_style_index_0_id_009a970a_scoped_true_lang_css__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! !!../../node_modules/css-loader/dist/cjs.js!../../node_modules/vue-loader/dist/stylePostLoader.js!../../node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./PageSettingsModal.vue?vue&type=style&index=0&id=009a970a&scoped=true&lang=css */ "./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/dist/stylePostLoader.js!./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/PageSettingsModal.vue?vue&type=style&index=0&id=009a970a&scoped=true&lang=css");

      
      
      
      
      
      
      
      
      

var options = {};

options.styleTagTransform = (_node_modules_style_loader_dist_runtime_styleTagTransform_js__WEBPACK_IMPORTED_MODULE_5___default());
options.setAttributes = (_node_modules_style_loader_dist_runtime_setAttributesWithoutAttributes_js__WEBPACK_IMPORTED_MODULE_3___default());

      options.insert = _node_modules_style_loader_dist_runtime_insertBySelector_js__WEBPACK_IMPORTED_MODULE_2___default().bind(null, "head");
    
options.domAPI = (_node_modules_style_loader_dist_runtime_styleDomAPI_js__WEBPACK_IMPORTED_MODULE_1___default());
options.insertStyleElement = (_node_modules_style_loader_dist_runtime_insertStyleElement_js__WEBPACK_IMPORTED_MODULE_4___default());

var update = _node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0___default()(_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_dist_stylePostLoader_js_node_modules_vue_loader_dist_index_js_ruleSet_1_rules_5_use_0_PageSettingsModal_vue_vue_type_style_index_0_id_009a970a_scoped_true_lang_css__WEBPACK_IMPORTED_MODULE_6__["default"], options);




       /* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_dist_stylePostLoader_js_node_modules_vue_loader_dist_index_js_ruleSet_1_rules_5_use_0_PageSettingsModal_vue_vue_type_style_index_0_id_009a970a_scoped_true_lang_css__WEBPACK_IMPORTED_MODULE_6__["default"] && _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_dist_stylePostLoader_js_node_modules_vue_loader_dist_index_js_ruleSet_1_rules_5_use_0_PageSettingsModal_vue_vue_type_style_index_0_id_009a970a_scoped_true_lang_css__WEBPACK_IMPORTED_MODULE_6__["default"].locals ? _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_dist_stylePostLoader_js_node_modules_vue_loader_dist_index_js_ruleSet_1_rules_5_use_0_PageSettingsModal_vue_vue_type_style_index_0_id_009a970a_scoped_true_lang_css__WEBPACK_IMPORTED_MODULE_6__["default"].locals : undefined);


/***/ },

/***/ "./src/components/PageSettingsModal.vue"
/*!**********************************************!*\
  !*** ./src/components/PageSettingsModal.vue ***!
  \**********************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _PageSettingsModal_vue_vue_type_template_id_009a970a_scoped_true__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./PageSettingsModal.vue?vue&type=template&id=009a970a&scoped=true */ "./src/components/PageSettingsModal.vue?vue&type=template&id=009a970a&scoped=true");
/* harmony import */ var _PageSettingsModal_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./PageSettingsModal.vue?vue&type=script&lang=js */ "./src/components/PageSettingsModal.vue?vue&type=script&lang=js");
/* harmony import */ var _PageSettingsModal_vue_vue_type_style_index_0_id_009a970a_scoped_true_lang_css__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./PageSettingsModal.vue?vue&type=style&index=0&id=009a970a&scoped=true&lang=css */ "./src/components/PageSettingsModal.vue?vue&type=style&index=0&id=009a970a&scoped=true&lang=css");
/* harmony import */ var _node_modules_vue_loader_dist_exportHelper_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ../../node_modules/vue-loader/dist/exportHelper.js */ "./node_modules/vue-loader/dist/exportHelper.js");




;


const __exports__ = /*#__PURE__*/(0,_node_modules_vue_loader_dist_exportHelper_js__WEBPACK_IMPORTED_MODULE_3__["default"])(_PageSettingsModal_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_1__["default"], [['render',_PageSettingsModal_vue_vue_type_template_id_009a970a_scoped_true__WEBPACK_IMPORTED_MODULE_0__.render],['__scopeId',"data-v-009a970a"],['__file',"src/components/PageSettingsModal.vue"]])
/* hot reload */
if (false) // removed by dead control flow
{}


/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (__exports__);

/***/ },

/***/ "./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/PageSettingsModal.vue?vue&type=script&lang=js"
/*!******************************************************************************************************************************************!*\
  !*** ./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/PageSettingsModal.vue?vue&type=script&lang=js ***!
  \******************************************************************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _nextcloud_vue__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @nextcloud/vue */ "./node_modules/@nextcloud/vue/dist/index.mjs");
/* harmony import */ var _nextcloud_l10n__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @nextcloud/l10n */ "./node_modules/@nextcloud/l10n/dist/index.mjs");




/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = ({
	name: 'PageSettingsModal',
	components: {
		NcDialog: _nextcloud_vue__WEBPACK_IMPORTED_MODULE_0__.NcDialog,
		NcButton: _nextcloud_vue__WEBPACK_IMPORTED_MODULE_0__.NcButton,
		NcSelect: _nextcloud_vue__WEBPACK_IMPORTED_MODULE_0__.NcSelect,
	},
	props: {
		pageUniqueId: {
			type: String,
			required: true,
		},
		settings: {
			type: Object,
			default: () => ({
				allowReactions: null,
				allowComments: null,
				allowCommentReactions: null,
			}),
		},
		globalSettings: {
			type: Object,
			default: () => ({
				allowPageReactions: true,
				allowComments: true,
				allowCommentReactions: true,
			}),
		},
	},
	emits: ['close', 'save'],
	data() {
		return {
			localSettings: {
				allowReactions: null,
				allowComments: null,
				allowCommentReactions: null,
			},
		}
	},
	computed: {
		// Only allow disabling per page when globally enabled
		// Cannot enable something that is globally disabled
		disableOnlyOptions() {
			return [
				{ value: false, label: (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_1__.translate)('intravox', 'Disabled') },
			]
		},
		// Check if comments are effectively enabled for this page
		// (globally enabled AND not disabled at page level)
		commentsEffectivelyEnabled() {
			// If comments are globally disabled, they're off
			if (!this.globalSettings.allowComments) {
				return false
			}
			// If comments are explicitly disabled for this page, they're off
			// Check both the option object format and direct value
			const commentValue = this.localSettings.allowComments?.value ?? this.localSettings.allowComments
			if (commentValue === false) {
				return false
			}
			return true
		},
		// Only show comment reactions option if comments are enabled
		showCommentReactionsOption() {
			return this.commentsEffectivelyEnabled
		},
	},
	created() {
		// Initialize localSettings - null means use global setting (shown as placeholder)
		// Only set a value if page has an explicit override
		this.localSettings.allowReactions = this.mapToOption(this.settings?.allowReactions)
		this.localSettings.allowComments = this.mapToOption(this.settings?.allowComments)
		this.localSettings.allowCommentReactions = this.mapToOption(this.settings?.allowCommentReactions)
	},
	mounted() {
		// Remove focus from any element after modal opens
		this.$nextTick(() => {
			if (document.activeElement && document.activeElement !== document.body) {
				document.activeElement.blur()
			}
		})
	},
	methods: {
		t(app, key, vars = {}) {
			return (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_1__.translate)(app, key, vars)
		},
		mapToOption(value) {
			// null = no override, return null (will show placeholder)
			if (value === null || value === undefined) {
				return null
			}
			// Only false (disabled) is a valid page-level override
			// true is not valid because you can't enable something globally disabled
			if (value === false) {
				return this.disableOnlyOptions.find(opt => opt.value === false)
			}
			return null
		},
		getStatusText(option, type) {
			let globalEnabled
			if (type === 'reactions') {
				globalEnabled = this.globalSettings.allowPageReactions
			} else if (type === 'comments') {
				globalEnabled = this.globalSettings.allowComments
			} else if (type === 'commentReactions') {
				globalEnabled = this.globalSettings.allowCommentReactions
			}

			// If globally disabled, always show that
			if (!globalEnabled) {
				return this.t('intravox', 'Disabled by administrator')
			}

			const value = option?.value
			if (value === false) {
				return this.t('intravox', 'Disabled for this page')
			}
			// null = inherit from global (which is enabled if we got here)
			return this.t('intravox', 'Using global setting (enabled)')
		},
		save() {
			this.$emit('save', {
				allowReactions: this.localSettings.allowReactions?.value ?? null,
				allowComments: this.localSettings.allowComments?.value ?? null,
				allowCommentReactions: this.localSettings.allowCommentReactions?.value ?? null,
			})
		},
	},
});


/***/ },

/***/ "./src/components/PageSettingsModal.vue?vue&type=style&index=0&id=009a970a&scoped=true&lang=css"
/*!******************************************************************************************************!*\
  !*** ./src/components/PageSettingsModal.vue?vue&type=style&index=0&id=009a970a&scoped=true&lang=css ***!
  \******************************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _node_modules_style_loader_dist_cjs_js_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_dist_stylePostLoader_js_node_modules_vue_loader_dist_index_js_ruleSet_1_rules_5_use_0_PageSettingsModal_vue_vue_type_style_index_0_id_009a970a_scoped_true_lang_css__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../node_modules/style-loader/dist/cjs.js!../../node_modules/css-loader/dist/cjs.js!../../node_modules/vue-loader/dist/stylePostLoader.js!../../node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./PageSettingsModal.vue?vue&type=style&index=0&id=009a970a&scoped=true&lang=css */ "./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/dist/stylePostLoader.js!./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/PageSettingsModal.vue?vue&type=style&index=0&id=009a970a&scoped=true&lang=css");


/***/ },

/***/ "./src/components/PageSettingsModal.vue?vue&type=script&lang=js"
/*!**********************************************************************!*\
  !*** ./src/components/PageSettingsModal.vue?vue&type=script&lang=js ***!
  \**********************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* reexport safe */ _node_modules_vue_loader_dist_index_js_ruleSet_1_rules_5_use_0_PageSettingsModal_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_0__["default"])
/* harmony export */ });
/* harmony import */ var _node_modules_vue_loader_dist_index_js_ruleSet_1_rules_5_use_0_PageSettingsModal_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./PageSettingsModal.vue?vue&type=script&lang=js */ "./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/PageSettingsModal.vue?vue&type=script&lang=js");
 

/***/ },

/***/ "./src/components/PageSettingsModal.vue?vue&type=template&id=009a970a&scoped=true"
/*!****************************************************************************************!*\
  !*** ./src/components/PageSettingsModal.vue?vue&type=template&id=009a970a&scoped=true ***!
  \****************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   render: () => (/* reexport safe */ _node_modules_vue_loader_dist_templateLoader_js_ruleSet_1_rules_2_node_modules_vue_loader_dist_index_js_ruleSet_1_rules_5_use_0_PageSettingsModal_vue_vue_type_template_id_009a970a_scoped_true__WEBPACK_IMPORTED_MODULE_0__.render)
/* harmony export */ });
/* harmony import */ var _node_modules_vue_loader_dist_templateLoader_js_ruleSet_1_rules_2_node_modules_vue_loader_dist_index_js_ruleSet_1_rules_5_use_0_PageSettingsModal_vue_vue_type_template_id_009a970a_scoped_true__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../node_modules/vue-loader/dist/templateLoader.js??ruleSet[1].rules[2]!../../node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./PageSettingsModal.vue?vue&type=template&id=009a970a&scoped=true */ "./node_modules/vue-loader/dist/templateLoader.js??ruleSet[1].rules[2]!./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/PageSettingsModal.vue?vue&type=template&id=009a970a&scoped=true");


/***/ },

/***/ "./node_modules/vue-loader/dist/templateLoader.js??ruleSet[1].rules[2]!./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/PageSettingsModal.vue?vue&type=template&id=009a970a&scoped=true"
/*!**********************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/vue-loader/dist/templateLoader.js??ruleSet[1].rules[2]!./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/PageSettingsModal.vue?vue&type=template&id=009a970a&scoped=true ***!
  \**********************************************************************************************************************************************************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   render: () => (/* binding */ render)
/* harmony export */ });
/* harmony import */ var vue__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! vue */ "./node_modules/vue/dist/vue.runtime.esm-bundler.js");


const _hoisted_1 = {
  ref: "focusTrap",
  tabindex: "-1",
  class: "focus-trap"
}
const _hoisted_2 = { class: "page-settings" }
const _hoisted_3 = { class: "settings-section" }
const _hoisted_4 = { class: "settings-section-header" }
const _hoisted_5 = { class: "settings-section-desc" }
const _hoisted_6 = { class: "setting-header" }
const _hoisted_7 = { class: "setting-label" }
const _hoisted_8 = {
  key: 1,
  class: "setting-locked"
}
const _hoisted_9 = { class: "setting-status" }
const _hoisted_10 = { class: "setting-header" }
const _hoisted_11 = { class: "setting-label" }
const _hoisted_12 = {
  key: 1,
  class: "setting-locked"
}
const _hoisted_13 = { class: "setting-status" }
const _hoisted_14 = { class: "setting-header" }
const _hoisted_15 = { class: "setting-label" }
const _hoisted_16 = {
  key: 1,
  class: "setting-locked"
}
const _hoisted_17 = { class: "setting-status" }

function render(_ctx, _cache, $props, $setup, $data, $options) {
  const _component_NcSelect = (0,vue__WEBPACK_IMPORTED_MODULE_0__.resolveComponent)("NcSelect")
  const _component_NcButton = (0,vue__WEBPACK_IMPORTED_MODULE_0__.resolveComponent)("NcButton")
  const _component_NcDialog = (0,vue__WEBPACK_IMPORTED_MODULE_0__.resolveComponent)("NcDialog")

  return ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createBlock)(_component_NcDialog, {
    open: true,
    name: $options.t('intravox', 'Page Settings'),
    size: "normal",
    onClose: _cache[7] || (_cache[7] = $event => (_ctx.$emit('close')))
  }, {
    actions: (0,vue__WEBPACK_IMPORTED_MODULE_0__.withCtx)(() => [
      (0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_NcButton, {
        type: "tertiary",
        onClick: _cache[6] || (_cache[6] = $event => (_ctx.$emit('close')))
      }, {
        default: (0,vue__WEBPACK_IMPORTED_MODULE_0__.withCtx)(() => [
          (0,vue__WEBPACK_IMPORTED_MODULE_0__.createTextVNode)((0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)($options.t('intravox', 'Cancel')), 1 /* TEXT */)
        ]),
        _: 1 /* STABLE */
      }),
      (0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_NcButton, {
        type: "primary",
        onClick: $options.save
      }, {
        default: (0,vue__WEBPACK_IMPORTED_MODULE_0__.withCtx)(() => [
          (0,vue__WEBPACK_IMPORTED_MODULE_0__.createTextVNode)((0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)($options.t('intravox', 'Save')), 1 /* TEXT */)
        ]),
        _: 1 /* STABLE */
      }, 8 /* PROPS */, ["onClick"])
    ]),
    default: (0,vue__WEBPACK_IMPORTED_MODULE_0__.withCtx)(() => [
      (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("div", _hoisted_1, null, 512 /* NEED_PATCH */),
      (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("div", _hoisted_2, [
        (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)(" Engagement Section "),
        (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("div", _hoisted_3, [
          (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("h3", _hoisted_4, [
            _cache[8] || (_cache[8] = (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("span", { class: "settings-icon" }, "💬", -1 /* CACHED */)),
            (0,vue__WEBPACK_IMPORTED_MODULE_0__.createTextVNode)(" " + (0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)($options.t('intravox', 'Engagement')), 1 /* TEXT */)
          ]),
          (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("p", _hoisted_5, (0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)($options.t('intravox', 'Override global engagement settings for this page.')), 1 /* TEXT */),
          (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)(" Allow Reactions "),
          (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("div", {
            class: (0,vue__WEBPACK_IMPORTED_MODULE_0__.normalizeClass)(["setting-item", { 'setting-item--disabled': !$props.globalSettings.allowPageReactions }])
          }, [
            (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("div", _hoisted_6, [
              (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("span", _hoisted_7, (0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)($options.t('intravox', 'Allow reactions on this page')), 1 /* TEXT */),
              ($props.globalSettings.allowPageReactions)
                ? ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createBlock)(_component_NcSelect, {
                    key: 0,
                    modelValue: $data.localSettings.allowReactions,
                    "onUpdate:modelValue": _cache[0] || (_cache[0] = $event => (($data.localSettings.allowReactions) = $event)),
                    options: $options.disableOnlyOptions,
                    clearable: true,
                    searchable: false,
                    autofocus: false,
                    placeholder: $options.t('intravox', 'Use global setting'),
                    class: "tri-state-select",
                    onClear: _cache[1] || (_cache[1] = $event => ($data.localSettings.allowReactions = null))
                  }, null, 8 /* PROPS */, ["modelValue", "options", "placeholder"]))
                : ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)("span", _hoisted_8, (0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)($options.t('intravox', 'Disabled globally')), 1 /* TEXT */))
            ]),
            (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("span", _hoisted_9, (0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)($options.getStatusText($data.localSettings.allowReactions, 'reactions')), 1 /* TEXT */)
          ], 2 /* CLASS */),
          (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)(" Allow Comments "),
          (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("div", {
            class: (0,vue__WEBPACK_IMPORTED_MODULE_0__.normalizeClass)(["setting-item", { 'setting-item--disabled': !$props.globalSettings.allowComments }])
          }, [
            (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("div", _hoisted_10, [
              (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("span", _hoisted_11, (0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)($options.t('intravox', 'Allow comments on this page')), 1 /* TEXT */),
              ($props.globalSettings.allowComments)
                ? ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createBlock)(_component_NcSelect, {
                    key: 0,
                    modelValue: $data.localSettings.allowComments,
                    "onUpdate:modelValue": _cache[2] || (_cache[2] = $event => (($data.localSettings.allowComments) = $event)),
                    options: $options.disableOnlyOptions,
                    clearable: true,
                    searchable: false,
                    autofocus: false,
                    placeholder: $options.t('intravox', 'Use global setting'),
                    class: "tri-state-select",
                    onClear: _cache[3] || (_cache[3] = $event => ($data.localSettings.allowComments = null))
                  }, null, 8 /* PROPS */, ["modelValue", "options", "placeholder"]))
                : ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)("span", _hoisted_12, (0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)($options.t('intravox', 'Disabled globally')), 1 /* TEXT */))
            ]),
            (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("span", _hoisted_13, (0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)($options.getStatusText($data.localSettings.allowComments, 'comments')), 1 /* TEXT */)
          ], 2 /* CLASS */),
          (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)(" Allow Comment Reactions (only show if comments are enabled) "),
          ($options.showCommentReactionsOption)
            ? ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)("div", {
                key: 0,
                class: (0,vue__WEBPACK_IMPORTED_MODULE_0__.normalizeClass)(["setting-item", { 'setting-item--disabled': !$props.globalSettings.allowCommentReactions }])
              }, [
                (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("div", _hoisted_14, [
                  (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("span", _hoisted_15, (0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)($options.t('intravox', 'Allow reactions on comments')), 1 /* TEXT */),
                  ($props.globalSettings.allowCommentReactions)
                    ? ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createBlock)(_component_NcSelect, {
                        key: 0,
                        modelValue: $data.localSettings.allowCommentReactions,
                        "onUpdate:modelValue": _cache[4] || (_cache[4] = $event => (($data.localSettings.allowCommentReactions) = $event)),
                        options: $options.disableOnlyOptions,
                        clearable: true,
                        searchable: false,
                        autofocus: false,
                        placeholder: $options.t('intravox', 'Use global setting'),
                        class: "tri-state-select",
                        onClear: _cache[5] || (_cache[5] = $event => ($data.localSettings.allowCommentReactions = null))
                      }, null, 8 /* PROPS */, ["modelValue", "options", "placeholder"]))
                    : ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)("span", _hoisted_16, (0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)($options.t('intravox', 'Disabled globally')), 1 /* TEXT */))
                ]),
                (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("span", _hoisted_17, (0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)($options.getStatusText($data.localSettings.allowCommentReactions, 'commentReactions')), 1 /* TEXT */)
              ], 2 /* CLASS */))
            : (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)("v-if", true)
        ])
      ])
    ]),
    _: 1 /* STABLE */
  }, 8 /* PROPS */, ["name"]))
}

/***/ }

}]);
//# sourceMappingURL=intravox-src_components_PageSettingsModal_vue.fb8fce3c.js.map