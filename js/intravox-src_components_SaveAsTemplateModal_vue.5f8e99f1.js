"use strict";
(self["webpackChunknextcloud_intravox"] = self["webpackChunknextcloud_intravox"] || []).push([["src_components_SaveAsTemplateModal_vue"],{

/***/ "./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/dist/stylePostLoader.js!./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/SaveAsTemplateModal.vue?vue&type=style&index=0&id=21be3ff8&scoped=true&lang=css"
/*!********************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/dist/stylePostLoader.js!./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/SaveAsTemplateModal.vue?vue&type=style&index=0&id=21be3ff8&scoped=true&lang=css ***!
  \********************************************************************************************************************************************************************************************************************************************************************/
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
.save-template-modal-content[data-v-21be3ff8] {
  padding: 20px;
  display: flex;
  flex-direction: column;
  gap: 16px;
}
.modal-description[data-v-21be3ff8] {
  margin: 0;
  color: var(--color-text-maxcontrast);
  font-size: 14px;
}
.form-group[data-v-21be3ff8] {
  display: flex;
  flex-direction: column;
  gap: 6px;
}
.form-group label[data-v-21be3ff8] {
  font-weight: 500;
  font-size: 14px;
  color: var(--color-main-text);
}
.form-group .optional[data-v-21be3ff8] {
  font-weight: normal;
  color: var(--color-text-maxcontrast);
}
.template-input[data-v-21be3ff8] {
  width: 100%;
  padding: 10px;
  font-size: 14px;
  color: var(--color-main-text);
  background: var(--color-main-background);
  border: 2px solid var(--color-border-dark);
  border-radius: var(--border-radius-large);
  box-sizing: border-box;
}
.template-input[data-v-21be3ff8]:focus {
  outline: none;
  border-color: var(--color-primary);
}
.template-textarea[data-v-21be3ff8] {
  resize: vertical;
  min-height: 60px;
  font-family: inherit;
}
.error-message[data-v-21be3ff8] {
  padding: 10px;
  background: var(--color-error);
  color: white;
  border-radius: var(--border-radius);
  font-size: 14px;
}
.modal-buttons[data-v-21be3ff8] {
  display: flex;
  justify-content: flex-end;
  gap: 10px;
  margin-top: 8px;
}
`, "",{"version":3,"sources":["webpack://./src/components/SaveAsTemplateModal.vue"],"names":[],"mappings":";AAqIA;EACE,aAAa;EACb,aAAa;EACb,sBAAsB;EACtB,SAAS;AACX;AAEA;EACE,SAAS;EACT,oCAAoC;EACpC,eAAe;AACjB;AAEA;EACE,aAAa;EACb,sBAAsB;EACtB,QAAQ;AACV;AAEA;EACE,gBAAgB;EAChB,eAAe;EACf,6BAA6B;AAC/B;AAEA;EACE,mBAAmB;EACnB,oCAAoC;AACtC;AAEA;EACE,WAAW;EACX,aAAa;EACb,eAAe;EACf,6BAA6B;EAC7B,wCAAwC;EACxC,0CAA0C;EAC1C,yCAAyC;EACzC,sBAAsB;AACxB;AAEA;EACE,aAAa;EACb,kCAAkC;AACpC;AAEA;EACE,gBAAgB;EAChB,gBAAgB;EAChB,oBAAoB;AACtB;AAEA;EACE,aAAa;EACb,8BAA8B;EAC9B,YAAY;EACZ,mCAAmC;EACnC,eAAe;AACjB;AAEA;EACE,aAAa;EACb,yBAAyB;EACzB,SAAS;EACT,eAAe;AACjB","sourcesContent":["<template>\r\n  <NcModal @close=\"$emit('close')\"\r\n           :name=\"t('Save as Template')\"\r\n           size=\"small\">\r\n    <div class=\"save-template-modal-content\">\r\n      <p class=\"modal-description\">{{ t('Create a reusable template from this page') }}</p>\r\n\r\n      <div class=\"form-group\">\r\n        <label for=\"template-title\">{{ t('Template name') }}</label>\r\n        <input\r\n          id=\"template-title\"\r\n          ref=\"titleInput\"\r\n          v-model=\"templateTitle\"\r\n          type=\"text\"\r\n          class=\"template-input\"\r\n          :placeholder=\"t('Enter template name')\"\r\n          @keyup.enter=\"saveTemplate\"\r\n        />\r\n      </div>\r\n\r\n      <div class=\"form-group\">\r\n        <label for=\"template-description\">{{ t('Description') }} <span class=\"optional\">{{ t('(optional)') }}</span></label>\r\n        <textarea\r\n          id=\"template-description\"\r\n          v-model=\"templateDescription\"\r\n          class=\"template-input template-textarea\"\r\n          :placeholder=\"t('Describe what this template is for')\"\r\n          rows=\"3\"\r\n        ></textarea>\r\n      </div>\r\n\r\n      <div v-if=\"error\" class=\"error-message\">\r\n        {{ error }}\r\n      </div>\r\n\r\n      <div class=\"modal-buttons\">\r\n        <NcButton @click=\"$emit('close')\" type=\"secondary\" :disabled=\"saving\">\r\n          {{ t('Cancel') }}\r\n        </NcButton>\r\n        <NcButton @click=\"saveTemplate\" type=\"primary\" :disabled=\"!canSave || saving\">\r\n          <template #icon>\r\n            <NcLoadingIcon v-if=\"saving\" :size=\"20\" />\r\n          </template>\r\n          {{ saving ? t('Saving...') : t('Save Template') }}\r\n        </NcButton>\r\n      </div>\r\n    </div>\r\n  </NcModal>\r\n</template>\r\n\r\n<script>\r\nimport { translate } from '@nextcloud/l10n';\r\nimport { generateUrl } from '@nextcloud/router';\r\nimport axios from '@nextcloud/axios';\r\nimport { NcModal, NcButton, NcLoadingIcon } from '@nextcloud/vue';\r\n\r\nexport default {\r\n  name: 'SaveAsTemplateModal',\r\n  components: {\r\n    NcModal,\r\n    NcButton,\r\n    NcLoadingIcon\r\n  },\r\n  props: {\r\n    pageUniqueId: {\r\n      type: String,\r\n      required: true\r\n    },\r\n    pageTitle: {\r\n      type: String,\r\n      default: ''\r\n    }\r\n  },\r\n  emits: ['close', 'saved'],\r\n  data() {\r\n    return {\r\n      templateTitle: '',\r\n      templateDescription: '',\r\n      saving: false,\r\n      error: null\r\n    };\r\n  },\r\n  computed: {\r\n    canSave() {\r\n      return this.templateTitle.trim().length > 0;\r\n    }\r\n  },\r\n  mounted() {\r\n    // Pre-fill with page title + \" Template\"\r\n    this.templateTitle = this.pageTitle ? `${this.pageTitle} Template` : '';\r\n\r\n    // Auto-focus the input field when modal opens\r\n    this.$nextTick(() => {\r\n      this.$refs.titleInput?.focus();\r\n      this.$refs.titleInput?.select();\r\n    });\r\n  },\r\n  methods: {\r\n    t(key, vars = {}) {\r\n      return translate('intravox', key, vars);\r\n    },\r\n    async saveTemplate() {\r\n      if (!this.canSave || this.saving) return;\r\n\r\n      this.saving = true;\r\n      this.error = null;\r\n\r\n      try {\r\n        const url = generateUrl('/apps/intravox/api/templates');\r\n        const response = await axios.post(url, {\r\n          pageUniqueId: this.pageUniqueId,\r\n          templateTitle: this.templateTitle.trim(),\r\n          templateDescription: this.templateDescription.trim() || null\r\n        });\r\n\r\n        if (response.data.success) {\r\n          this.$emit('saved', response.data.template);\r\n          this.$emit('close');\r\n        } else {\r\n          this.error = response.data.error || this.t('Failed to save template');\r\n        }\r\n      } catch (err) {\r\n        console.error('Failed to save template:', err);\r\n        this.error = err.response?.data?.error || this.t('Failed to save template');\r\n      } finally {\r\n        this.saving = false;\r\n      }\r\n    }\r\n  }\r\n};\r\n</script>\r\n\r\n<style scoped>\r\n.save-template-modal-content {\r\n  padding: 20px;\r\n  display: flex;\r\n  flex-direction: column;\r\n  gap: 16px;\r\n}\r\n\r\n.modal-description {\r\n  margin: 0;\r\n  color: var(--color-text-maxcontrast);\r\n  font-size: 14px;\r\n}\r\n\r\n.form-group {\r\n  display: flex;\r\n  flex-direction: column;\r\n  gap: 6px;\r\n}\r\n\r\n.form-group label {\r\n  font-weight: 500;\r\n  font-size: 14px;\r\n  color: var(--color-main-text);\r\n}\r\n\r\n.form-group .optional {\r\n  font-weight: normal;\r\n  color: var(--color-text-maxcontrast);\r\n}\r\n\r\n.template-input {\r\n  width: 100%;\r\n  padding: 10px;\r\n  font-size: 14px;\r\n  color: var(--color-main-text);\r\n  background: var(--color-main-background);\r\n  border: 2px solid var(--color-border-dark);\r\n  border-radius: var(--border-radius-large);\r\n  box-sizing: border-box;\r\n}\r\n\r\n.template-input:focus {\r\n  outline: none;\r\n  border-color: var(--color-primary);\r\n}\r\n\r\n.template-textarea {\r\n  resize: vertical;\r\n  min-height: 60px;\r\n  font-family: inherit;\r\n}\r\n\r\n.error-message {\r\n  padding: 10px;\r\n  background: var(--color-error);\r\n  color: white;\r\n  border-radius: var(--border-radius);\r\n  font-size: 14px;\r\n}\r\n\r\n.modal-buttons {\r\n  display: flex;\r\n  justify-content: flex-end;\r\n  gap: 10px;\r\n  margin-top: 8px;\r\n}\r\n</style>\r\n"],"sourceRoot":""}]);
// Exports
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (___CSS_LOADER_EXPORT___);


/***/ },

/***/ "./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/dist/stylePostLoader.js!./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/SaveAsTemplateModal.vue?vue&type=style&index=0&id=21be3ff8&scoped=true&lang=css"
/*!************************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/dist/stylePostLoader.js!./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/SaveAsTemplateModal.vue?vue&type=style&index=0&id=21be3ff8&scoped=true&lang=css ***!
  \************************************************************************************************************************************************************************************************************************************************************************************************************/
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
/* harmony import */ var _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_dist_stylePostLoader_js_node_modules_vue_loader_dist_index_js_ruleSet_1_rules_5_use_0_SaveAsTemplateModal_vue_vue_type_style_index_0_id_21be3ff8_scoped_true_lang_css__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! !!../../node_modules/css-loader/dist/cjs.js!../../node_modules/vue-loader/dist/stylePostLoader.js!../../node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./SaveAsTemplateModal.vue?vue&type=style&index=0&id=21be3ff8&scoped=true&lang=css */ "./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/dist/stylePostLoader.js!./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/SaveAsTemplateModal.vue?vue&type=style&index=0&id=21be3ff8&scoped=true&lang=css");

      
      
      
      
      
      
      
      
      

var options = {};

options.styleTagTransform = (_node_modules_style_loader_dist_runtime_styleTagTransform_js__WEBPACK_IMPORTED_MODULE_5___default());
options.setAttributes = (_node_modules_style_loader_dist_runtime_setAttributesWithoutAttributes_js__WEBPACK_IMPORTED_MODULE_3___default());

      options.insert = _node_modules_style_loader_dist_runtime_insertBySelector_js__WEBPACK_IMPORTED_MODULE_2___default().bind(null, "head");
    
options.domAPI = (_node_modules_style_loader_dist_runtime_styleDomAPI_js__WEBPACK_IMPORTED_MODULE_1___default());
options.insertStyleElement = (_node_modules_style_loader_dist_runtime_insertStyleElement_js__WEBPACK_IMPORTED_MODULE_4___default());

var update = _node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0___default()(_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_dist_stylePostLoader_js_node_modules_vue_loader_dist_index_js_ruleSet_1_rules_5_use_0_SaveAsTemplateModal_vue_vue_type_style_index_0_id_21be3ff8_scoped_true_lang_css__WEBPACK_IMPORTED_MODULE_6__["default"], options);




       /* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_dist_stylePostLoader_js_node_modules_vue_loader_dist_index_js_ruleSet_1_rules_5_use_0_SaveAsTemplateModal_vue_vue_type_style_index_0_id_21be3ff8_scoped_true_lang_css__WEBPACK_IMPORTED_MODULE_6__["default"] && _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_dist_stylePostLoader_js_node_modules_vue_loader_dist_index_js_ruleSet_1_rules_5_use_0_SaveAsTemplateModal_vue_vue_type_style_index_0_id_21be3ff8_scoped_true_lang_css__WEBPACK_IMPORTED_MODULE_6__["default"].locals ? _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_dist_stylePostLoader_js_node_modules_vue_loader_dist_index_js_ruleSet_1_rules_5_use_0_SaveAsTemplateModal_vue_vue_type_style_index_0_id_21be3ff8_scoped_true_lang_css__WEBPACK_IMPORTED_MODULE_6__["default"].locals : undefined);


/***/ },

/***/ "./src/components/SaveAsTemplateModal.vue"
/*!************************************************!*\
  !*** ./src/components/SaveAsTemplateModal.vue ***!
  \************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _SaveAsTemplateModal_vue_vue_type_template_id_21be3ff8_scoped_true__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./SaveAsTemplateModal.vue?vue&type=template&id=21be3ff8&scoped=true */ "./src/components/SaveAsTemplateModal.vue?vue&type=template&id=21be3ff8&scoped=true");
/* harmony import */ var _SaveAsTemplateModal_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./SaveAsTemplateModal.vue?vue&type=script&lang=js */ "./src/components/SaveAsTemplateModal.vue?vue&type=script&lang=js");
/* harmony import */ var _SaveAsTemplateModal_vue_vue_type_style_index_0_id_21be3ff8_scoped_true_lang_css__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./SaveAsTemplateModal.vue?vue&type=style&index=0&id=21be3ff8&scoped=true&lang=css */ "./src/components/SaveAsTemplateModal.vue?vue&type=style&index=0&id=21be3ff8&scoped=true&lang=css");
/* harmony import */ var _node_modules_vue_loader_dist_exportHelper_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ../../node_modules/vue-loader/dist/exportHelper.js */ "./node_modules/vue-loader/dist/exportHelper.js");




;


const __exports__ = /*#__PURE__*/(0,_node_modules_vue_loader_dist_exportHelper_js__WEBPACK_IMPORTED_MODULE_3__["default"])(_SaveAsTemplateModal_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_1__["default"], [['render',_SaveAsTemplateModal_vue_vue_type_template_id_21be3ff8_scoped_true__WEBPACK_IMPORTED_MODULE_0__.render],['__scopeId',"data-v-21be3ff8"],['__file',"src/components/SaveAsTemplateModal.vue"]])
/* hot reload */
if (false) // removed by dead control flow
{}


/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (__exports__);

/***/ },

/***/ "./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/SaveAsTemplateModal.vue?vue&type=script&lang=js"
/*!********************************************************************************************************************************************!*\
  !*** ./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/SaveAsTemplateModal.vue?vue&type=script&lang=js ***!
  \********************************************************************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _nextcloud_l10n__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @nextcloud/l10n */ "./node_modules/@nextcloud/l10n/dist/index.mjs");
/* harmony import */ var _nextcloud_router__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @nextcloud/router */ "./node_modules/@nextcloud/router/dist/index.mjs");
/* harmony import */ var _nextcloud_axios__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @nextcloud/axios */ "./node_modules/@nextcloud/axios/dist/index.js");
/* harmony import */ var _nextcloud_vue__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! @nextcloud/vue */ "./node_modules/@nextcloud/vue/dist/index.mjs");






/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = ({
  name: 'SaveAsTemplateModal',
  components: {
    NcModal: _nextcloud_vue__WEBPACK_IMPORTED_MODULE_3__.NcModal,
    NcButton: _nextcloud_vue__WEBPACK_IMPORTED_MODULE_3__.NcButton,
    NcLoadingIcon: _nextcloud_vue__WEBPACK_IMPORTED_MODULE_3__.NcLoadingIcon
  },
  props: {
    pageUniqueId: {
      type: String,
      required: true
    },
    pageTitle: {
      type: String,
      default: ''
    }
  },
  emits: ['close', 'saved'],
  data() {
    return {
      templateTitle: '',
      templateDescription: '',
      saving: false,
      error: null
    };
  },
  computed: {
    canSave() {
      return this.templateTitle.trim().length > 0;
    }
  },
  mounted() {
    // Pre-fill with page title + " Template"
    this.templateTitle = this.pageTitle ? `${this.pageTitle} Template` : '';

    // Auto-focus the input field when modal opens
    this.$nextTick(() => {
      this.$refs.titleInput?.focus();
      this.$refs.titleInput?.select();
    });
  },
  methods: {
    t(key, vars = {}) {
      return (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_0__.translate)('intravox', key, vars);
    },
    async saveTemplate() {
      if (!this.canSave || this.saving) return;

      this.saving = true;
      this.error = null;

      try {
        const url = (0,_nextcloud_router__WEBPACK_IMPORTED_MODULE_1__.generateUrl)('/apps/intravox/api/templates');
        const response = await _nextcloud_axios__WEBPACK_IMPORTED_MODULE_2__["default"].post(url, {
          pageUniqueId: this.pageUniqueId,
          templateTitle: this.templateTitle.trim(),
          templateDescription: this.templateDescription.trim() || null
        });

        if (response.data.success) {
          this.$emit('saved', response.data.template);
          this.$emit('close');
        } else {
          this.error = response.data.error || this.t('Failed to save template');
        }
      } catch (err) {
        console.error('Failed to save template:', err);
        this.error = err.response?.data?.error || this.t('Failed to save template');
      } finally {
        this.saving = false;
      }
    }
  }
});


/***/ },

/***/ "./src/components/SaveAsTemplateModal.vue?vue&type=style&index=0&id=21be3ff8&scoped=true&lang=css"
/*!********************************************************************************************************!*\
  !*** ./src/components/SaveAsTemplateModal.vue?vue&type=style&index=0&id=21be3ff8&scoped=true&lang=css ***!
  \********************************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _node_modules_style_loader_dist_cjs_js_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_dist_stylePostLoader_js_node_modules_vue_loader_dist_index_js_ruleSet_1_rules_5_use_0_SaveAsTemplateModal_vue_vue_type_style_index_0_id_21be3ff8_scoped_true_lang_css__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../node_modules/style-loader/dist/cjs.js!../../node_modules/css-loader/dist/cjs.js!../../node_modules/vue-loader/dist/stylePostLoader.js!../../node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./SaveAsTemplateModal.vue?vue&type=style&index=0&id=21be3ff8&scoped=true&lang=css */ "./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/dist/stylePostLoader.js!./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/SaveAsTemplateModal.vue?vue&type=style&index=0&id=21be3ff8&scoped=true&lang=css");


/***/ },

/***/ "./src/components/SaveAsTemplateModal.vue?vue&type=script&lang=js"
/*!************************************************************************!*\
  !*** ./src/components/SaveAsTemplateModal.vue?vue&type=script&lang=js ***!
  \************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* reexport safe */ _node_modules_vue_loader_dist_index_js_ruleSet_1_rules_5_use_0_SaveAsTemplateModal_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_0__["default"])
/* harmony export */ });
/* harmony import */ var _node_modules_vue_loader_dist_index_js_ruleSet_1_rules_5_use_0_SaveAsTemplateModal_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./SaveAsTemplateModal.vue?vue&type=script&lang=js */ "./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/SaveAsTemplateModal.vue?vue&type=script&lang=js");
 

/***/ },

/***/ "./src/components/SaveAsTemplateModal.vue?vue&type=template&id=21be3ff8&scoped=true"
/*!******************************************************************************************!*\
  !*** ./src/components/SaveAsTemplateModal.vue?vue&type=template&id=21be3ff8&scoped=true ***!
  \******************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   render: () => (/* reexport safe */ _node_modules_vue_loader_dist_templateLoader_js_ruleSet_1_rules_2_node_modules_vue_loader_dist_index_js_ruleSet_1_rules_5_use_0_SaveAsTemplateModal_vue_vue_type_template_id_21be3ff8_scoped_true__WEBPACK_IMPORTED_MODULE_0__.render)
/* harmony export */ });
/* harmony import */ var _node_modules_vue_loader_dist_templateLoader_js_ruleSet_1_rules_2_node_modules_vue_loader_dist_index_js_ruleSet_1_rules_5_use_0_SaveAsTemplateModal_vue_vue_type_template_id_21be3ff8_scoped_true__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../node_modules/vue-loader/dist/templateLoader.js??ruleSet[1].rules[2]!../../node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./SaveAsTemplateModal.vue?vue&type=template&id=21be3ff8&scoped=true */ "./node_modules/vue-loader/dist/templateLoader.js??ruleSet[1].rules[2]!./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/SaveAsTemplateModal.vue?vue&type=template&id=21be3ff8&scoped=true");


/***/ },

/***/ "./node_modules/vue-loader/dist/templateLoader.js??ruleSet[1].rules[2]!./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/SaveAsTemplateModal.vue?vue&type=template&id=21be3ff8&scoped=true"
/*!************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/vue-loader/dist/templateLoader.js??ruleSet[1].rules[2]!./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/SaveAsTemplateModal.vue?vue&type=template&id=21be3ff8&scoped=true ***!
  \************************************************************************************************************************************************************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   render: () => (/* binding */ render)
/* harmony export */ });
/* harmony import */ var vue__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! vue */ "./node_modules/vue/dist/vue.runtime.esm-bundler.js");


const _hoisted_1 = { class: "save-template-modal-content" }
const _hoisted_2 = { class: "modal-description" }
const _hoisted_3 = { class: "form-group" }
const _hoisted_4 = { for: "template-title" }
const _hoisted_5 = ["placeholder"]
const _hoisted_6 = { class: "form-group" }
const _hoisted_7 = { for: "template-description" }
const _hoisted_8 = { class: "optional" }
const _hoisted_9 = ["placeholder"]
const _hoisted_10 = {
  key: 0,
  class: "error-message"
}
const _hoisted_11 = { class: "modal-buttons" }

function render(_ctx, _cache, $props, $setup, $data, $options) {
  const _component_NcButton = (0,vue__WEBPACK_IMPORTED_MODULE_0__.resolveComponent)("NcButton")
  const _component_NcLoadingIcon = (0,vue__WEBPACK_IMPORTED_MODULE_0__.resolveComponent)("NcLoadingIcon")
  const _component_NcModal = (0,vue__WEBPACK_IMPORTED_MODULE_0__.resolveComponent)("NcModal")

  return ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createBlock)(_component_NcModal, {
    onClose: _cache[4] || (_cache[4] = $event => (_ctx.$emit('close'))),
    name: $options.t('Save as Template'),
    size: "small"
  }, {
    default: (0,vue__WEBPACK_IMPORTED_MODULE_0__.withCtx)(() => [
      (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("div", _hoisted_1, [
        (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("p", _hoisted_2, (0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)($options.t('Create a reusable template from this page')), 1 /* TEXT */),
        (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("div", _hoisted_3, [
          (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("label", _hoisted_4, (0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)($options.t('Template name')), 1 /* TEXT */),
          (0,vue__WEBPACK_IMPORTED_MODULE_0__.withDirectives)((0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("input", {
            id: "template-title",
            ref: "titleInput",
            "onUpdate:modelValue": _cache[0] || (_cache[0] = $event => (($data.templateTitle) = $event)),
            type: "text",
            class: "template-input",
            placeholder: $options.t('Enter template name'),
            onKeyup: _cache[1] || (_cache[1] = (0,vue__WEBPACK_IMPORTED_MODULE_0__.withKeys)((...args) => ($options.saveTemplate && $options.saveTemplate(...args)), ["enter"]))
          }, null, 40 /* PROPS, NEED_HYDRATION */, _hoisted_5), [
            [vue__WEBPACK_IMPORTED_MODULE_0__.vModelText, $data.templateTitle]
          ])
        ]),
        (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("div", _hoisted_6, [
          (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("label", _hoisted_7, [
            (0,vue__WEBPACK_IMPORTED_MODULE_0__.createTextVNode)((0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)($options.t('Description')) + " ", 1 /* TEXT */),
            (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("span", _hoisted_8, (0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)($options.t('(optional)')), 1 /* TEXT */)
          ]),
          (0,vue__WEBPACK_IMPORTED_MODULE_0__.withDirectives)((0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("textarea", {
            id: "template-description",
            "onUpdate:modelValue": _cache[2] || (_cache[2] = $event => (($data.templateDescription) = $event)),
            class: "template-input template-textarea",
            placeholder: $options.t('Describe what this template is for'),
            rows: "3"
          }, null, 8 /* PROPS */, _hoisted_9), [
            [vue__WEBPACK_IMPORTED_MODULE_0__.vModelText, $data.templateDescription]
          ])
        ]),
        ($data.error)
          ? ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)("div", _hoisted_10, (0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)($data.error), 1 /* TEXT */))
          : (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)("v-if", true),
        (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("div", _hoisted_11, [
          (0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_NcButton, {
            onClick: _cache[3] || (_cache[3] = $event => (_ctx.$emit('close'))),
            type: "secondary",
            disabled: $data.saving
          }, {
            default: (0,vue__WEBPACK_IMPORTED_MODULE_0__.withCtx)(() => [
              (0,vue__WEBPACK_IMPORTED_MODULE_0__.createTextVNode)((0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)($options.t('Cancel')), 1 /* TEXT */)
            ]),
            _: 1 /* STABLE */
          }, 8 /* PROPS */, ["disabled"]),
          (0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_NcButton, {
            onClick: $options.saveTemplate,
            type: "primary",
            disabled: !$options.canSave || $data.saving
          }, {
            icon: (0,vue__WEBPACK_IMPORTED_MODULE_0__.withCtx)(() => [
              ($data.saving)
                ? ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createBlock)(_component_NcLoadingIcon, {
                    key: 0,
                    size: 20
                  }))
                : (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)("v-if", true)
            ]),
            default: (0,vue__WEBPACK_IMPORTED_MODULE_0__.withCtx)(() => [
              (0,vue__WEBPACK_IMPORTED_MODULE_0__.createTextVNode)(" " + (0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)($data.saving ? $options.t('Saving...') : $options.t('Save Template')), 1 /* TEXT */)
            ]),
            _: 1 /* STABLE */
          }, 8 /* PROPS */, ["onClick", "disabled"])
        ])
      ])
    ]),
    _: 1 /* STABLE */
  }, 8 /* PROPS */, ["name"]))
}

/***/ }

}]);
//# sourceMappingURL=intravox-src_components_SaveAsTemplateModal_vue.5f8e99f1.js.map