"use strict";
(self["webpackChunknextcloud_intravox"] = self["webpackChunknextcloud_intravox"] || []).push([["src_components_FeedSettings_vue"],{

/***/ "./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/dist/stylePostLoader.js!./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/FeedSettings.vue?vue&type=style&index=0&id=1cb34391&scoped=true&lang=css"
/*!*************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/dist/stylePostLoader.js!./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/FeedSettings.vue?vue&type=style&index=0&id=1cb34391&scoped=true&lang=css ***!
  \*************************************************************************************************************************************************************************************************************************************************************/
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
.feed-settings[data-v-1cb34391] {
  padding: 0 16px 16px;
}
.feed-settings__description[data-v-1cb34391] {
  color: var(--color-text-maxcontrast);
  margin-bottom: 16px;
}
.feed-settings__section[data-v-1cb34391] {
  margin-bottom: 16px;
}
.feed-settings__label[data-v-1cb34391] {
  font-weight: 600;
  margin: 0 0 8px 0;
  font-size: 14px;
}
.feed-settings__url-box[data-v-1cb34391] {
  display: flex;
  gap: 8px;
  align-items: center;
}
.feed-settings__url-input[data-v-1cb34391] {
  flex: 1;
  padding: 8px 12px;
  border: 1px solid var(--color-border);
  border-radius: var(--border-radius);
  background: var(--color-background-dark);
  font-family: monospace;
  font-size: 12px;
  color: var(--color-text-maxcontrast);
}
.feed-settings__meta[data-v-1cb34391] {
  margin-top: 4px;
  font-size: 12px;
  color: var(--color-text-maxcontrast);
}
`, "",{"version":3,"sources":["webpack://./src/components/FeedSettings.vue"],"names":[],"mappings":";AA2QA;EACE,oBAAoB;AACtB;AAEA;EACE,oCAAoC;EACpC,mBAAmB;AACrB;AAEA;EACE,mBAAmB;AACrB;AAEA;EACE,gBAAgB;EAChB,iBAAiB;EACjB,eAAe;AACjB;AAEA;EACE,aAAa;EACb,QAAQ;EACR,mBAAmB;AACrB;AAEA;EACE,OAAO;EACP,iBAAiB;EACjB,qCAAqC;EACrC,mCAAmC;EACnC,wCAAwC;EACxC,sBAAsB;EACtB,eAAe;EACf,oCAAoC;AACtC;AAEA;EACE,eAAe;EACf,eAAe;EACf,oCAAoC;AACtC","sourcesContent":["<template>\n  <NcDialog\n    :open=\"true\"\n    :name=\"t('RSS Feed')\"\n    size=\"normal\"\n    @close=\"$emit('close')\">\n\n    <div class=\"feed-settings\">\n      <!-- Admin disabled link sharing -->\n      <NcNoteCard v-if=\"linkSharingDisabled\" type=\"error\">\n        {{ t('RSS feeds are disabled. The administrator has turned off public link sharing in the Nextcloud Sharing settings.') }}\n      </NcNoteCard>\n\n      <template v-if=\"!linkSharingDisabled\">\n      <p class=\"feed-settings__description\">\n        {{ t('Generate a personal RSS feed URL to follow IntraVox updates in your favorite RSS reader. The feed only shows pages you have access to.') }}\n      </p>\n\n      <!-- Scope selection -->\n      <div class=\"feed-settings__section\">\n        <h4 class=\"feed-settings__label\">{{ t('Feed scope') }}</h4>\n        <NcCheckboxRadioSwitch\n          v-model=\"localConfig.scope\"\n          value=\"language\"\n          name=\"feed-scope\"\n          type=\"radio\">\n          {{ t('My language') }}\n        </NcCheckboxRadioSwitch>\n        <NcCheckboxRadioSwitch\n          v-model=\"localConfig.scope\"\n          value=\"all\"\n          name=\"feed-scope\"\n          type=\"radio\">\n          {{ t('All languages') }}\n        </NcCheckboxRadioSwitch>\n      </div>\n\n      <!-- Limit -->\n      <div class=\"feed-settings__section\">\n        <h4 class=\"feed-settings__label\">{{ t('Maximum items') }}</h4>\n        <NcSelect\n          v-model=\"localConfig.limit\"\n          :options=\"limitOptions\"\n          :clearable=\"false\"\n          :reduce=\"opt => opt\" />\n      </div>\n\n      <!-- Feed URL (shown after token is generated) -->\n      <div v-if=\"feedUrl\" class=\"feed-settings__section\">\n        <h4 class=\"feed-settings__label\">{{ t('Your feed URL') }}</h4>\n        <div class=\"feed-settings__url-box\">\n          <input\n            ref=\"urlInput\"\n            :value=\"feedUrl\"\n            class=\"feed-settings__url-input\"\n            readonly\n            @focus=\"$refs.urlInput.select()\" />\n          <NcButton\n            type=\"secondary\"\n            :aria-label=\"t('Copy URL')\"\n            @click=\"copyFeedUrl\">\n            <template #icon>\n              <ContentCopy :size=\"20\" />\n            </template>\n          </NcButton>\n        </div>\n        <p v-if=\"lastAccessed\" class=\"feed-settings__meta\">\n          {{ t('Last accessed: {date}', { date: lastAccessed }) }}\n        </p>\n      </div>\n\n      <!-- Warning about token secrecy -->\n      <NcNoteCard v-if=\"feedUrl\" type=\"warning\">\n        {{ t('This URL contains a personal token. Anyone with this link can read your feed. Do not share it publicly.') }}\n      </NcNoteCard>\n      </template>\n    </div>\n\n    <template v-if=\"!linkSharingDisabled\" #actions>\n      <NcButton v-if=\"feedUrl\"\n                type=\"error\"\n                @click=\"revokeToken\">\n        {{ t('Revoke') }}\n      </NcButton>\n      <NcButton v-if=\"feedUrl\"\n                type=\"secondary\"\n                @click=\"regenerateToken\">\n        {{ t('Regenerate') }}\n      </NcButton>\n      <NcButton v-if=\"!feedUrl\"\n                type=\"primary\"\n                :disabled=\"generating\"\n                @click=\"generateToken\">\n        {{ t('Generate Feed URL') }}\n      </NcButton>\n      <NcButton v-else\n                type=\"primary\"\n                :disabled=\"saving\"\n                @click=\"saveConfig\">\n        {{ t('Save settings') }}\n      </NcButton>\n    </template>\n  </NcDialog>\n</template>\n\n<script>\nimport axios from '@nextcloud/axios'\nimport { generateUrl } from '@nextcloud/router'\nimport { translate as t } from '@nextcloud/l10n'\nimport { showSuccess, showError } from '@nextcloud/dialogs'\nimport { NcDialog, NcButton, NcSelect, NcCheckboxRadioSwitch, NcNoteCard } from '@nextcloud/vue'\nimport ContentCopy from 'vue-material-design-icons/ContentCopy.vue'\n\nexport default {\n  name: 'FeedSettings',\n  components: {\n    NcDialog,\n    NcButton,\n    NcSelect,\n    NcCheckboxRadioSwitch,\n    NcNoteCard,\n    ContentCopy,\n  },\n  emits: ['close'],\n  data() {\n    return {\n      loading: true,\n      generating: false,\n      saving: false,\n      linkSharingDisabled: false,\n      feedUrl: null,\n      lastAccessed: null,\n      localConfig: {\n        scope: 'language',\n        limit: 20,\n      },\n      limitOptions: [10, 20, 30, 50],\n    }\n  },\n  async created() {\n    await this.loadToken()\n  },\n  methods: {\n    t(key, vars = {}) {\n      return t('intravox', key, vars)\n    },\n\n    async loadToken() {\n      this.loading = true\n      try {\n        const url = generateUrl('/apps/intravox/api/feed/token')\n        const response = await axios.get(url)\n        const data = response.data\n\n        if (data.linkSharingDisabled) {\n          this.linkSharingDisabled = true\n          return\n        }\n\n        if (data.hasToken) {\n          this.feedUrl = data.feedUrl\n          this.lastAccessed = data.last_accessed\n            ? new Date(data.last_accessed).toLocaleString()\n            : null\n          if (data.config) {\n            this.localConfig = {\n              scope: data.config.scope === 'all' ? 'all' : 'language',\n              limit: data.config.limit || 20,\n            }\n          }\n        }\n      } catch (err) {\n        showError(this.t('Failed to load feed settings'))\n      } finally {\n        this.loading = false\n      }\n    },\n\n    async generateToken() {\n      this.generating = true\n      try {\n        const url = generateUrl('/apps/intravox/api/feed/token')\n        const response = await axios.post(url, {\n          scope: this.localConfig.scope,\n          limit: this.localConfig.limit,\n        })\n        const data = response.data\n\n        if (data.hasToken) {\n          this.feedUrl = data.feedUrl\n          showSuccess(this.t('Feed URL generated'))\n        }\n      } catch (err) {\n        showError(this.t('Failed to generate feed URL'))\n      } finally {\n        this.generating = false\n      }\n    },\n\n    async regenerateToken() {\n      this.generating = true\n      try {\n        const url = generateUrl('/apps/intravox/api/feed/token')\n        const response = await axios.post(url, {\n          scope: this.localConfig.scope,\n          limit: this.localConfig.limit,\n        })\n        const data = response.data\n\n        if (data.hasToken) {\n          this.feedUrl = data.feedUrl\n          showSuccess(this.t('Feed URL regenerated. The old URL no longer works.'))\n        }\n      } catch (err) {\n        showError(this.t('Failed to regenerate feed URL'))\n      } finally {\n        this.generating = false\n      }\n    },\n\n    async revokeToken() {\n      try {\n        const url = generateUrl('/apps/intravox/api/feed/token')\n        await axios.delete(url)\n        this.feedUrl = null\n        this.lastAccessed = null\n        showSuccess(this.t('Feed URL revoked'))\n      } catch (err) {\n        showError(this.t('Failed to revoke feed URL'))\n      }\n    },\n\n    async saveConfig() {\n      this.saving = true\n      try {\n        const url = generateUrl('/apps/intravox/api/feed/config')\n        const response = await axios.put(url, {\n          scope: this.localConfig.scope,\n          limit: this.localConfig.limit,\n        })\n\n        if (response.data.feedUrl) {\n          this.feedUrl = response.data.feedUrl\n        }\n        showSuccess(this.t('Feed settings saved'))\n      } catch (err) {\n        showError(this.t('Failed to save feed settings'))\n      } finally {\n        this.saving = false\n      }\n    },\n\n    async copyFeedUrl() {\n      try {\n        await navigator.clipboard.writeText(this.feedUrl)\n        showSuccess(this.t('Feed URL copied to clipboard'))\n      } catch (err) {\n        // Fallback: select the input text\n        this.$refs.urlInput?.select()\n        showError(this.t('Could not copy automatically. Please copy the selected URL.'))\n      }\n    },\n  },\n}\n</script>\n\n<style scoped>\n.feed-settings {\n  padding: 0 16px 16px;\n}\n\n.feed-settings__description {\n  color: var(--color-text-maxcontrast);\n  margin-bottom: 16px;\n}\n\n.feed-settings__section {\n  margin-bottom: 16px;\n}\n\n.feed-settings__label {\n  font-weight: 600;\n  margin: 0 0 8px 0;\n  font-size: 14px;\n}\n\n.feed-settings__url-box {\n  display: flex;\n  gap: 8px;\n  align-items: center;\n}\n\n.feed-settings__url-input {\n  flex: 1;\n  padding: 8px 12px;\n  border: 1px solid var(--color-border);\n  border-radius: var(--border-radius);\n  background: var(--color-background-dark);\n  font-family: monospace;\n  font-size: 12px;\n  color: var(--color-text-maxcontrast);\n}\n\n.feed-settings__meta {\n  margin-top: 4px;\n  font-size: 12px;\n  color: var(--color-text-maxcontrast);\n}\n</style>\n"],"sourceRoot":""}]);
// Exports
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (___CSS_LOADER_EXPORT___);


/***/ },

/***/ "./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/dist/stylePostLoader.js!./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/FeedSettings.vue?vue&type=style&index=0&id=1cb34391&scoped=true&lang=css"
/*!*****************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/dist/stylePostLoader.js!./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/FeedSettings.vue?vue&type=style&index=0&id=1cb34391&scoped=true&lang=css ***!
  \*****************************************************************************************************************************************************************************************************************************************************************************************************/
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
/* harmony import */ var _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_dist_stylePostLoader_js_node_modules_vue_loader_dist_index_js_ruleSet_1_rules_5_use_0_FeedSettings_vue_vue_type_style_index_0_id_1cb34391_scoped_true_lang_css__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! !!../../node_modules/css-loader/dist/cjs.js!../../node_modules/vue-loader/dist/stylePostLoader.js!../../node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./FeedSettings.vue?vue&type=style&index=0&id=1cb34391&scoped=true&lang=css */ "./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/dist/stylePostLoader.js!./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/FeedSettings.vue?vue&type=style&index=0&id=1cb34391&scoped=true&lang=css");

      
      
      
      
      
      
      
      
      

var options = {};

options.styleTagTransform = (_node_modules_style_loader_dist_runtime_styleTagTransform_js__WEBPACK_IMPORTED_MODULE_5___default());
options.setAttributes = (_node_modules_style_loader_dist_runtime_setAttributesWithoutAttributes_js__WEBPACK_IMPORTED_MODULE_3___default());

      options.insert = _node_modules_style_loader_dist_runtime_insertBySelector_js__WEBPACK_IMPORTED_MODULE_2___default().bind(null, "head");
    
options.domAPI = (_node_modules_style_loader_dist_runtime_styleDomAPI_js__WEBPACK_IMPORTED_MODULE_1___default());
options.insertStyleElement = (_node_modules_style_loader_dist_runtime_insertStyleElement_js__WEBPACK_IMPORTED_MODULE_4___default());

var update = _node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0___default()(_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_dist_stylePostLoader_js_node_modules_vue_loader_dist_index_js_ruleSet_1_rules_5_use_0_FeedSettings_vue_vue_type_style_index_0_id_1cb34391_scoped_true_lang_css__WEBPACK_IMPORTED_MODULE_6__["default"], options);




       /* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_dist_stylePostLoader_js_node_modules_vue_loader_dist_index_js_ruleSet_1_rules_5_use_0_FeedSettings_vue_vue_type_style_index_0_id_1cb34391_scoped_true_lang_css__WEBPACK_IMPORTED_MODULE_6__["default"] && _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_dist_stylePostLoader_js_node_modules_vue_loader_dist_index_js_ruleSet_1_rules_5_use_0_FeedSettings_vue_vue_type_style_index_0_id_1cb34391_scoped_true_lang_css__WEBPACK_IMPORTED_MODULE_6__["default"].locals ? _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_dist_stylePostLoader_js_node_modules_vue_loader_dist_index_js_ruleSet_1_rules_5_use_0_FeedSettings_vue_vue_type_style_index_0_id_1cb34391_scoped_true_lang_css__WEBPACK_IMPORTED_MODULE_6__["default"].locals : undefined);


/***/ },

/***/ "./src/components/FeedSettings.vue"
/*!*****************************************!*\
  !*** ./src/components/FeedSettings.vue ***!
  \*****************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _FeedSettings_vue_vue_type_template_id_1cb34391_scoped_true__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./FeedSettings.vue?vue&type=template&id=1cb34391&scoped=true */ "./src/components/FeedSettings.vue?vue&type=template&id=1cb34391&scoped=true");
/* harmony import */ var _FeedSettings_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./FeedSettings.vue?vue&type=script&lang=js */ "./src/components/FeedSettings.vue?vue&type=script&lang=js");
/* harmony import */ var _FeedSettings_vue_vue_type_style_index_0_id_1cb34391_scoped_true_lang_css__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./FeedSettings.vue?vue&type=style&index=0&id=1cb34391&scoped=true&lang=css */ "./src/components/FeedSettings.vue?vue&type=style&index=0&id=1cb34391&scoped=true&lang=css");
/* harmony import */ var _node_modules_vue_loader_dist_exportHelper_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ../../node_modules/vue-loader/dist/exportHelper.js */ "./node_modules/vue-loader/dist/exportHelper.js");




;


const __exports__ = /*#__PURE__*/(0,_node_modules_vue_loader_dist_exportHelper_js__WEBPACK_IMPORTED_MODULE_3__["default"])(_FeedSettings_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_1__["default"], [['render',_FeedSettings_vue_vue_type_template_id_1cb34391_scoped_true__WEBPACK_IMPORTED_MODULE_0__.render],['__scopeId',"data-v-1cb34391"],['__file',"src/components/FeedSettings.vue"]])
/* hot reload */
if (false) // removed by dead control flow
{}


/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (__exports__);

/***/ },

/***/ "./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/FeedSettings.vue?vue&type=script&lang=js"
/*!*************************************************************************************************************************************!*\
  !*** ./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/FeedSettings.vue?vue&type=script&lang=js ***!
  \*************************************************************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _nextcloud_axios__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @nextcloud/axios */ "./node_modules/@nextcloud/axios/dist/index.js");
/* harmony import */ var _nextcloud_router__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @nextcloud/router */ "./node_modules/@nextcloud/router/dist/index.mjs");
/* harmony import */ var _nextcloud_l10n__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @nextcloud/l10n */ "./node_modules/@nextcloud/l10n/dist/index.mjs");
/* harmony import */ var _nextcloud_dialogs__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! @nextcloud/dialogs */ "./node_modules/@nextcloud/dialogs/dist/index.mjs");
/* harmony import */ var _nextcloud_vue__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! @nextcloud/vue */ "./node_modules/@nextcloud/vue/dist/index.mjs");
/* harmony import */ var vue_material_design_icons_ContentCopy_vue__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! vue-material-design-icons/ContentCopy.vue */ "./node_modules/vue-material-design-icons/ContentCopy.vue");








/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = ({
  name: 'FeedSettings',
  components: {
    NcDialog: _nextcloud_vue__WEBPACK_IMPORTED_MODULE_4__.NcDialog,
    NcButton: _nextcloud_vue__WEBPACK_IMPORTED_MODULE_4__.NcButton,
    NcSelect: _nextcloud_vue__WEBPACK_IMPORTED_MODULE_4__.NcSelect,
    NcCheckboxRadioSwitch: _nextcloud_vue__WEBPACK_IMPORTED_MODULE_4__.NcCheckboxRadioSwitch,
    NcNoteCard: _nextcloud_vue__WEBPACK_IMPORTED_MODULE_4__.NcNoteCard,
    ContentCopy: vue_material_design_icons_ContentCopy_vue__WEBPACK_IMPORTED_MODULE_5__["default"],
  },
  emits: ['close'],
  data() {
    return {
      loading: true,
      generating: false,
      saving: false,
      linkSharingDisabled: false,
      feedUrl: null,
      lastAccessed: null,
      localConfig: {
        scope: 'language',
        limit: 20,
      },
      limitOptions: [10, 20, 30, 50],
    }
  },
  async created() {
    await this.loadToken()
  },
  methods: {
    t(key, vars = {}) {
      return (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_2__.translate)('intravox', key, vars)
    },

    async loadToken() {
      this.loading = true
      try {
        const url = (0,_nextcloud_router__WEBPACK_IMPORTED_MODULE_1__.generateUrl)('/apps/intravox/api/feed/token')
        const response = await _nextcloud_axios__WEBPACK_IMPORTED_MODULE_0__["default"].get(url)
        const data = response.data

        if (data.linkSharingDisabled) {
          this.linkSharingDisabled = true
          return
        }

        if (data.hasToken) {
          this.feedUrl = data.feedUrl
          this.lastAccessed = data.last_accessed
            ? new Date(data.last_accessed).toLocaleString()
            : null
          if (data.config) {
            this.localConfig = {
              scope: data.config.scope === 'all' ? 'all' : 'language',
              limit: data.config.limit || 20,
            }
          }
        }
      } catch (err) {
        (0,_nextcloud_dialogs__WEBPACK_IMPORTED_MODULE_3__.showError)(this.t('Failed to load feed settings'))
      } finally {
        this.loading = false
      }
    },

    async generateToken() {
      this.generating = true
      try {
        const url = (0,_nextcloud_router__WEBPACK_IMPORTED_MODULE_1__.generateUrl)('/apps/intravox/api/feed/token')
        const response = await _nextcloud_axios__WEBPACK_IMPORTED_MODULE_0__["default"].post(url, {
          scope: this.localConfig.scope,
          limit: this.localConfig.limit,
        })
        const data = response.data

        if (data.hasToken) {
          this.feedUrl = data.feedUrl
          ;(0,_nextcloud_dialogs__WEBPACK_IMPORTED_MODULE_3__.showSuccess)(this.t('Feed URL generated'))
        }
      } catch (err) {
        (0,_nextcloud_dialogs__WEBPACK_IMPORTED_MODULE_3__.showError)(this.t('Failed to generate feed URL'))
      } finally {
        this.generating = false
      }
    },

    async regenerateToken() {
      this.generating = true
      try {
        const url = (0,_nextcloud_router__WEBPACK_IMPORTED_MODULE_1__.generateUrl)('/apps/intravox/api/feed/token')
        const response = await _nextcloud_axios__WEBPACK_IMPORTED_MODULE_0__["default"].post(url, {
          scope: this.localConfig.scope,
          limit: this.localConfig.limit,
        })
        const data = response.data

        if (data.hasToken) {
          this.feedUrl = data.feedUrl
          ;(0,_nextcloud_dialogs__WEBPACK_IMPORTED_MODULE_3__.showSuccess)(this.t('Feed URL regenerated. The old URL no longer works.'))
        }
      } catch (err) {
        (0,_nextcloud_dialogs__WEBPACK_IMPORTED_MODULE_3__.showError)(this.t('Failed to regenerate feed URL'))
      } finally {
        this.generating = false
      }
    },

    async revokeToken() {
      try {
        const url = (0,_nextcloud_router__WEBPACK_IMPORTED_MODULE_1__.generateUrl)('/apps/intravox/api/feed/token')
        await _nextcloud_axios__WEBPACK_IMPORTED_MODULE_0__["default"].delete(url)
        this.feedUrl = null
        this.lastAccessed = null
        ;(0,_nextcloud_dialogs__WEBPACK_IMPORTED_MODULE_3__.showSuccess)(this.t('Feed URL revoked'))
      } catch (err) {
        (0,_nextcloud_dialogs__WEBPACK_IMPORTED_MODULE_3__.showError)(this.t('Failed to revoke feed URL'))
      }
    },

    async saveConfig() {
      this.saving = true
      try {
        const url = (0,_nextcloud_router__WEBPACK_IMPORTED_MODULE_1__.generateUrl)('/apps/intravox/api/feed/config')
        const response = await _nextcloud_axios__WEBPACK_IMPORTED_MODULE_0__["default"].put(url, {
          scope: this.localConfig.scope,
          limit: this.localConfig.limit,
        })

        if (response.data.feedUrl) {
          this.feedUrl = response.data.feedUrl
        }
        (0,_nextcloud_dialogs__WEBPACK_IMPORTED_MODULE_3__.showSuccess)(this.t('Feed settings saved'))
      } catch (err) {
        (0,_nextcloud_dialogs__WEBPACK_IMPORTED_MODULE_3__.showError)(this.t('Failed to save feed settings'))
      } finally {
        this.saving = false
      }
    },

    async copyFeedUrl() {
      try {
        await navigator.clipboard.writeText(this.feedUrl)
        ;(0,_nextcloud_dialogs__WEBPACK_IMPORTED_MODULE_3__.showSuccess)(this.t('Feed URL copied to clipboard'))
      } catch (err) {
        // Fallback: select the input text
        this.$refs.urlInput?.select()
        ;(0,_nextcloud_dialogs__WEBPACK_IMPORTED_MODULE_3__.showError)(this.t('Could not copy automatically. Please copy the selected URL.'))
      }
    },
  },
});


/***/ },

/***/ "./src/components/FeedSettings.vue?vue&type=style&index=0&id=1cb34391&scoped=true&lang=css"
/*!*************************************************************************************************!*\
  !*** ./src/components/FeedSettings.vue?vue&type=style&index=0&id=1cb34391&scoped=true&lang=css ***!
  \*************************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _node_modules_style_loader_dist_cjs_js_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_dist_stylePostLoader_js_node_modules_vue_loader_dist_index_js_ruleSet_1_rules_5_use_0_FeedSettings_vue_vue_type_style_index_0_id_1cb34391_scoped_true_lang_css__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../node_modules/style-loader/dist/cjs.js!../../node_modules/css-loader/dist/cjs.js!../../node_modules/vue-loader/dist/stylePostLoader.js!../../node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./FeedSettings.vue?vue&type=style&index=0&id=1cb34391&scoped=true&lang=css */ "./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/dist/stylePostLoader.js!./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/FeedSettings.vue?vue&type=style&index=0&id=1cb34391&scoped=true&lang=css");


/***/ },

/***/ "./src/components/FeedSettings.vue?vue&type=script&lang=js"
/*!*****************************************************************!*\
  !*** ./src/components/FeedSettings.vue?vue&type=script&lang=js ***!
  \*****************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* reexport safe */ _node_modules_vue_loader_dist_index_js_ruleSet_1_rules_5_use_0_FeedSettings_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_0__["default"])
/* harmony export */ });
/* harmony import */ var _node_modules_vue_loader_dist_index_js_ruleSet_1_rules_5_use_0_FeedSettings_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./FeedSettings.vue?vue&type=script&lang=js */ "./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/FeedSettings.vue?vue&type=script&lang=js");
 

/***/ },

/***/ "./src/components/FeedSettings.vue?vue&type=template&id=1cb34391&scoped=true"
/*!***********************************************************************************!*\
  !*** ./src/components/FeedSettings.vue?vue&type=template&id=1cb34391&scoped=true ***!
  \***********************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   render: () => (/* reexport safe */ _node_modules_vue_loader_dist_templateLoader_js_ruleSet_1_rules_2_node_modules_vue_loader_dist_index_js_ruleSet_1_rules_5_use_0_FeedSettings_vue_vue_type_template_id_1cb34391_scoped_true__WEBPACK_IMPORTED_MODULE_0__.render)
/* harmony export */ });
/* harmony import */ var _node_modules_vue_loader_dist_templateLoader_js_ruleSet_1_rules_2_node_modules_vue_loader_dist_index_js_ruleSet_1_rules_5_use_0_FeedSettings_vue_vue_type_template_id_1cb34391_scoped_true__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../node_modules/vue-loader/dist/templateLoader.js??ruleSet[1].rules[2]!../../node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./FeedSettings.vue?vue&type=template&id=1cb34391&scoped=true */ "./node_modules/vue-loader/dist/templateLoader.js??ruleSet[1].rules[2]!./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/FeedSettings.vue?vue&type=template&id=1cb34391&scoped=true");


/***/ },

/***/ "./node_modules/vue-loader/dist/templateLoader.js??ruleSet[1].rules[2]!./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/FeedSettings.vue?vue&type=template&id=1cb34391&scoped=true"
/*!*****************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/vue-loader/dist/templateLoader.js??ruleSet[1].rules[2]!./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/FeedSettings.vue?vue&type=template&id=1cb34391&scoped=true ***!
  \*****************************************************************************************************************************************************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   render: () => (/* binding */ render)
/* harmony export */ });
/* harmony import */ var vue__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! vue */ "./node_modules/vue/dist/vue.runtime.esm-bundler.js");


const _hoisted_1 = { class: "feed-settings" }
const _hoisted_2 = { class: "feed-settings__description" }
const _hoisted_3 = { class: "feed-settings__section" }
const _hoisted_4 = { class: "feed-settings__label" }
const _hoisted_5 = { class: "feed-settings__section" }
const _hoisted_6 = { class: "feed-settings__label" }
const _hoisted_7 = {
  key: 0,
  class: "feed-settings__section"
}
const _hoisted_8 = { class: "feed-settings__label" }
const _hoisted_9 = { class: "feed-settings__url-box" }
const _hoisted_10 = ["value"]
const _hoisted_11 = {
  key: 0,
  class: "feed-settings__meta"
}

function render(_ctx, _cache, $props, $setup, $data, $options) {
  const _component_NcNoteCard = (0,vue__WEBPACK_IMPORTED_MODULE_0__.resolveComponent)("NcNoteCard")
  const _component_NcCheckboxRadioSwitch = (0,vue__WEBPACK_IMPORTED_MODULE_0__.resolveComponent)("NcCheckboxRadioSwitch")
  const _component_NcSelect = (0,vue__WEBPACK_IMPORTED_MODULE_0__.resolveComponent)("NcSelect")
  const _component_ContentCopy = (0,vue__WEBPACK_IMPORTED_MODULE_0__.resolveComponent)("ContentCopy")
  const _component_NcButton = (0,vue__WEBPACK_IMPORTED_MODULE_0__.resolveComponent)("NcButton")
  const _component_NcDialog = (0,vue__WEBPACK_IMPORTED_MODULE_0__.resolveComponent)("NcDialog")

  return ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createBlock)(_component_NcDialog, {
    open: true,
    name: $options.t('RSS Feed'),
    size: "normal",
    onClose: _cache[4] || (_cache[4] = $event => (_ctx.$emit('close')))
  }, (0,vue__WEBPACK_IMPORTED_MODULE_0__.createSlots)({
    default: (0,vue__WEBPACK_IMPORTED_MODULE_0__.withCtx)(() => [
      (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("div", _hoisted_1, [
        (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)(" Admin disabled link sharing "),
        ($data.linkSharingDisabled)
          ? ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createBlock)(_component_NcNoteCard, {
              key: 0,
              type: "error"
            }, {
              default: (0,vue__WEBPACK_IMPORTED_MODULE_0__.withCtx)(() => [
                (0,vue__WEBPACK_IMPORTED_MODULE_0__.createTextVNode)((0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)($options.t('RSS feeds are disabled. The administrator has turned off public link sharing in the Nextcloud Sharing settings.')), 1 /* TEXT */)
              ]),
              _: 1 /* STABLE */
            }))
          : (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)("v-if", true),
        (!$data.linkSharingDisabled)
          ? ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)(vue__WEBPACK_IMPORTED_MODULE_0__.Fragment, { key: 1 }, [
              (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("p", _hoisted_2, (0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)($options.t('Generate a personal RSS feed URL to follow IntraVox updates in your favorite RSS reader. The feed only shows pages you have access to.')), 1 /* TEXT */),
              (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)(" Scope selection "),
              (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("div", _hoisted_3, [
                (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("h4", _hoisted_4, (0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)($options.t('Feed scope')), 1 /* TEXT */),
                (0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_NcCheckboxRadioSwitch, {
                  modelValue: $data.localConfig.scope,
                  "onUpdate:modelValue": _cache[0] || (_cache[0] = $event => (($data.localConfig.scope) = $event)),
                  value: "language",
                  name: "feed-scope",
                  type: "radio"
                }, {
                  default: (0,vue__WEBPACK_IMPORTED_MODULE_0__.withCtx)(() => [
                    (0,vue__WEBPACK_IMPORTED_MODULE_0__.createTextVNode)((0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)($options.t('My language')), 1 /* TEXT */)
                  ]),
                  _: 1 /* STABLE */
                }, 8 /* PROPS */, ["modelValue"]),
                (0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_NcCheckboxRadioSwitch, {
                  modelValue: $data.localConfig.scope,
                  "onUpdate:modelValue": _cache[1] || (_cache[1] = $event => (($data.localConfig.scope) = $event)),
                  value: "all",
                  name: "feed-scope",
                  type: "radio"
                }, {
                  default: (0,vue__WEBPACK_IMPORTED_MODULE_0__.withCtx)(() => [
                    (0,vue__WEBPACK_IMPORTED_MODULE_0__.createTextVNode)((0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)($options.t('All languages')), 1 /* TEXT */)
                  ]),
                  _: 1 /* STABLE */
                }, 8 /* PROPS */, ["modelValue"])
              ]),
              (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)(" Limit "),
              (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("div", _hoisted_5, [
                (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("h4", _hoisted_6, (0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)($options.t('Maximum items')), 1 /* TEXT */),
                (0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_NcSelect, {
                  modelValue: $data.localConfig.limit,
                  "onUpdate:modelValue": _cache[2] || (_cache[2] = $event => (($data.localConfig.limit) = $event)),
                  options: $data.limitOptions,
                  clearable: false,
                  reduce: opt => opt
                }, null, 8 /* PROPS */, ["modelValue", "options", "reduce"])
              ]),
              (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)(" Feed URL (shown after token is generated) "),
              ($data.feedUrl)
                ? ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)("div", _hoisted_7, [
                    (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("h4", _hoisted_8, (0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)($options.t('Your feed URL')), 1 /* TEXT */),
                    (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("div", _hoisted_9, [
                      (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("input", {
                        ref: "urlInput",
                        value: $data.feedUrl,
                        class: "feed-settings__url-input",
                        readonly: "",
                        onFocus: _cache[3] || (_cache[3] = $event => (_ctx.$refs.urlInput.select()))
                      }, null, 40 /* PROPS, NEED_HYDRATION */, _hoisted_10),
                      (0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_NcButton, {
                        type: "secondary",
                        "aria-label": $options.t('Copy URL'),
                        onClick: $options.copyFeedUrl
                      }, {
                        icon: (0,vue__WEBPACK_IMPORTED_MODULE_0__.withCtx)(() => [
                          (0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_ContentCopy, { size: 20 })
                        ]),
                        _: 1 /* STABLE */
                      }, 8 /* PROPS */, ["aria-label", "onClick"])
                    ]),
                    ($data.lastAccessed)
                      ? ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)("p", _hoisted_11, (0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)($options.t('Last accessed: {date}', { date: $data.lastAccessed })), 1 /* TEXT */))
                      : (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)("v-if", true)
                  ]))
                : (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)("v-if", true),
              (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)(" Warning about token secrecy "),
              ($data.feedUrl)
                ? ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createBlock)(_component_NcNoteCard, {
                    key: 1,
                    type: "warning"
                  }, {
                    default: (0,vue__WEBPACK_IMPORTED_MODULE_0__.withCtx)(() => [
                      (0,vue__WEBPACK_IMPORTED_MODULE_0__.createTextVNode)((0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)($options.t('This URL contains a personal token. Anyone with this link can read your feed. Do not share it publicly.')), 1 /* TEXT */)
                    ]),
                    _: 1 /* STABLE */
                  }))
                : (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)("v-if", true)
            ], 64 /* STABLE_FRAGMENT */))
          : (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)("v-if", true)
      ])
    ]),
    _: 2 /* DYNAMIC */
  }, [
    (!$data.linkSharingDisabled)
      ? {
          name: "actions",
          fn: (0,vue__WEBPACK_IMPORTED_MODULE_0__.withCtx)(() => [
            ($data.feedUrl)
              ? ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createBlock)(_component_NcButton, {
                  key: 0,
                  type: "error",
                  onClick: $options.revokeToken
                }, {
                  default: (0,vue__WEBPACK_IMPORTED_MODULE_0__.withCtx)(() => [
                    (0,vue__WEBPACK_IMPORTED_MODULE_0__.createTextVNode)((0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)($options.t('Revoke')), 1 /* TEXT */)
                  ]),
                  _: 1 /* STABLE */
                }, 8 /* PROPS */, ["onClick"]))
              : (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)("v-if", true),
            ($data.feedUrl)
              ? ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createBlock)(_component_NcButton, {
                  key: 1,
                  type: "secondary",
                  onClick: $options.regenerateToken
                }, {
                  default: (0,vue__WEBPACK_IMPORTED_MODULE_0__.withCtx)(() => [
                    (0,vue__WEBPACK_IMPORTED_MODULE_0__.createTextVNode)((0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)($options.t('Regenerate')), 1 /* TEXT */)
                  ]),
                  _: 1 /* STABLE */
                }, 8 /* PROPS */, ["onClick"]))
              : (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)("v-if", true),
            (!$data.feedUrl)
              ? ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createBlock)(_component_NcButton, {
                  key: 2,
                  type: "primary",
                  disabled: $data.generating,
                  onClick: $options.generateToken
                }, {
                  default: (0,vue__WEBPACK_IMPORTED_MODULE_0__.withCtx)(() => [
                    (0,vue__WEBPACK_IMPORTED_MODULE_0__.createTextVNode)((0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)($options.t('Generate Feed URL')), 1 /* TEXT */)
                  ]),
                  _: 1 /* STABLE */
                }, 8 /* PROPS */, ["disabled", "onClick"]))
              : ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createBlock)(_component_NcButton, {
                  key: 3,
                  type: "primary",
                  disabled: $data.saving,
                  onClick: $options.saveConfig
                }, {
                  default: (0,vue__WEBPACK_IMPORTED_MODULE_0__.withCtx)(() => [
                    (0,vue__WEBPACK_IMPORTED_MODULE_0__.createTextVNode)((0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)($options.t('Save settings')), 1 /* TEXT */)
                  ]),
                  _: 1 /* STABLE */
                }, 8 /* PROPS */, ["disabled", "onClick"]))
          ]),
          key: "0"
        }
      : undefined
  ]), 1032 /* PROPS, DYNAMIC_SLOTS */, ["name"]))
}

/***/ }

}]);
//# sourceMappingURL=intravox-src_components_FeedSettings_vue.ce59b8cf.js.map