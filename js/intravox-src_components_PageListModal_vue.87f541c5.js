"use strict";
(self["webpackChunknextcloud_intravox"] = self["webpackChunknextcloud_intravox"] || []).push([["src_components_PageListModal_vue"],{

/***/ "./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/dist/stylePostLoader.js!./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/PageListModal.vue?vue&type=style&index=0&id=2a35cee0&scoped=true&lang=css"
/*!**************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/dist/stylePostLoader.js!./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/PageListModal.vue?vue&type=style&index=0&id=2a35cee0&scoped=true&lang=css ***!
  \**************************************************************************************************************************************************************************************************************************************************************/
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
.page-list-content[data-v-2a35cee0] {
  padding: 20px;
}
.empty-state[data-v-2a35cee0] {
  text-align: center;
  padding: 40px;
  color: var(--color-text-maxcontrast);
}
.page-list[data-v-2a35cee0] {
  display: flex;
  flex-direction: column;
}
`, "",{"version":3,"sources":["webpack://./src/components/PageListModal.vue"],"names":[],"mappings":";AAsFA;EACE,aAAa;AACf;AAEA;EACE,kBAAkB;EAClB,aAAa;EACb,oCAAoC;AACtC;AAEA;EACE,aAAa;EACb,sBAAsB;AACxB","sourcesContent":["<template>\r\n  <NcModal @close=\"$emit('close')\"\r\n           :name=\"t('All pages')\"\r\n           size=\"normal\">\r\n    <div class=\"page-list-content\">\r\n      <div v-if=\"pages.length === 0\" class=\"empty-state\">\r\n        <p>{{ t('No pages created yet.') }}</p>\r\n      </div>\r\n\r\n      <div v-else class=\"page-list\">\r\n        <NcListItem\r\n          v-for=\"page in sortedPages\"\r\n          :key=\"page.uniqueId\"\r\n          :name=\"page.title\"\r\n          :details=\"formatDate(page.modified)\"\r\n          @click=\"$emit('select', page.uniqueId)\"\r\n        >\r\n          <template #actions>\r\n            <NcButton\r\n              v-if=\"page.uniqueId !== 'home'\"\r\n              type=\"error\"\r\n              @click.stop=\"$emit('delete', page.uniqueId)\"\r\n              :aria-label=\"t('Delete')\"\r\n            >\r\n              <template #icon>\r\n                <Delete :size=\"20\" />\r\n              </template>\r\n            </NcButton>\r\n          </template>\r\n        </NcListItem>\r\n      </div>\r\n    </div>\r\n  </NcModal>\r\n</template>\r\n\r\n<script>\r\nimport { translate } from '@nextcloud/l10n';\r\nimport { NcModal, NcListItem, NcButton } from '@nextcloud/vue';\r\nimport Delete from 'vue-material-design-icons/Delete.vue';\r\n\r\nexport default {\r\n  name: 'PageListModal',\r\n  components: {\r\n    NcModal,\r\n    NcListItem,\r\n    NcButton,\r\n    Delete\r\n  },\r\n  props: {\r\n    pages: {\r\n      type: Array,\r\n      required: true\r\n    }\r\n  },\r\n  emits: ['close', 'select', 'delete'],\r\n  computed: {\r\n    sortedPages() {\r\n      return [...this.pages].sort((a, b) => {\r\n        // Home page always first\r\n        if (a.uniqueId === 'home') return -1;\r\n        if (b.uniqueId === 'home') return 1;\r\n        // Then by modified date\r\n        return (b.modified || 0) - (a.modified || 0);\r\n      });\r\n    }\r\n  },\r\n  methods: {\r\n    t(key, vars = {}) {\r\n      return translate('intravox', key, vars);\r\n    },\r\n    formatDate(timestamp) {\r\n      if (!timestamp) return '';\r\n      const date = new Date(timestamp * 1000);\r\n      return date.toLocaleDateString('nl-NL', {\r\n        year: 'numeric',\r\n        month: 'long',\r\n        day: 'numeric',\r\n        hour: '2-digit',\r\n        minute: '2-digit'\r\n      });\r\n    }\r\n  }\r\n};\r\n</script>\r\n\r\n<style scoped>\r\n.page-list-content {\r\n  padding: 20px;\r\n}\r\n\r\n.empty-state {\r\n  text-align: center;\r\n  padding: 40px;\r\n  color: var(--color-text-maxcontrast);\r\n}\r\n\r\n.page-list {\r\n  display: flex;\r\n  flex-direction: column;\r\n}\r\n</style>\r\n"],"sourceRoot":""}]);
// Exports
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (___CSS_LOADER_EXPORT___);


/***/ },

/***/ "./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/dist/stylePostLoader.js!./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/PageListModal.vue?vue&type=style&index=0&id=2a35cee0&scoped=true&lang=css"
/*!******************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/dist/stylePostLoader.js!./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/PageListModal.vue?vue&type=style&index=0&id=2a35cee0&scoped=true&lang=css ***!
  \******************************************************************************************************************************************************************************************************************************************************************************************************/
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
/* harmony import */ var _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_dist_stylePostLoader_js_node_modules_vue_loader_dist_index_js_ruleSet_1_rules_5_use_0_PageListModal_vue_vue_type_style_index_0_id_2a35cee0_scoped_true_lang_css__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! !!../../node_modules/css-loader/dist/cjs.js!../../node_modules/vue-loader/dist/stylePostLoader.js!../../node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./PageListModal.vue?vue&type=style&index=0&id=2a35cee0&scoped=true&lang=css */ "./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/dist/stylePostLoader.js!./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/PageListModal.vue?vue&type=style&index=0&id=2a35cee0&scoped=true&lang=css");

      
      
      
      
      
      
      
      
      

var options = {};

options.styleTagTransform = (_node_modules_style_loader_dist_runtime_styleTagTransform_js__WEBPACK_IMPORTED_MODULE_5___default());
options.setAttributes = (_node_modules_style_loader_dist_runtime_setAttributesWithoutAttributes_js__WEBPACK_IMPORTED_MODULE_3___default());

      options.insert = _node_modules_style_loader_dist_runtime_insertBySelector_js__WEBPACK_IMPORTED_MODULE_2___default().bind(null, "head");
    
options.domAPI = (_node_modules_style_loader_dist_runtime_styleDomAPI_js__WEBPACK_IMPORTED_MODULE_1___default());
options.insertStyleElement = (_node_modules_style_loader_dist_runtime_insertStyleElement_js__WEBPACK_IMPORTED_MODULE_4___default());

var update = _node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0___default()(_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_dist_stylePostLoader_js_node_modules_vue_loader_dist_index_js_ruleSet_1_rules_5_use_0_PageListModal_vue_vue_type_style_index_0_id_2a35cee0_scoped_true_lang_css__WEBPACK_IMPORTED_MODULE_6__["default"], options);




       /* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_dist_stylePostLoader_js_node_modules_vue_loader_dist_index_js_ruleSet_1_rules_5_use_0_PageListModal_vue_vue_type_style_index_0_id_2a35cee0_scoped_true_lang_css__WEBPACK_IMPORTED_MODULE_6__["default"] && _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_dist_stylePostLoader_js_node_modules_vue_loader_dist_index_js_ruleSet_1_rules_5_use_0_PageListModal_vue_vue_type_style_index_0_id_2a35cee0_scoped_true_lang_css__WEBPACK_IMPORTED_MODULE_6__["default"].locals ? _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_dist_stylePostLoader_js_node_modules_vue_loader_dist_index_js_ruleSet_1_rules_5_use_0_PageListModal_vue_vue_type_style_index_0_id_2a35cee0_scoped_true_lang_css__WEBPACK_IMPORTED_MODULE_6__["default"].locals : undefined);


/***/ },

/***/ "./src/components/PageListModal.vue"
/*!******************************************!*\
  !*** ./src/components/PageListModal.vue ***!
  \******************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _PageListModal_vue_vue_type_template_id_2a35cee0_scoped_true__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./PageListModal.vue?vue&type=template&id=2a35cee0&scoped=true */ "./src/components/PageListModal.vue?vue&type=template&id=2a35cee0&scoped=true");
/* harmony import */ var _PageListModal_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./PageListModal.vue?vue&type=script&lang=js */ "./src/components/PageListModal.vue?vue&type=script&lang=js");
/* harmony import */ var _PageListModal_vue_vue_type_style_index_0_id_2a35cee0_scoped_true_lang_css__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./PageListModal.vue?vue&type=style&index=0&id=2a35cee0&scoped=true&lang=css */ "./src/components/PageListModal.vue?vue&type=style&index=0&id=2a35cee0&scoped=true&lang=css");
/* harmony import */ var _node_modules_vue_loader_dist_exportHelper_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ../../node_modules/vue-loader/dist/exportHelper.js */ "./node_modules/vue-loader/dist/exportHelper.js");




;


const __exports__ = /*#__PURE__*/(0,_node_modules_vue_loader_dist_exportHelper_js__WEBPACK_IMPORTED_MODULE_3__["default"])(_PageListModal_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_1__["default"], [['render',_PageListModal_vue_vue_type_template_id_2a35cee0_scoped_true__WEBPACK_IMPORTED_MODULE_0__.render],['__scopeId',"data-v-2a35cee0"],['__file',"src/components/PageListModal.vue"]])
/* hot reload */
if (false) // removed by dead control flow
{}


/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (__exports__);

/***/ },

/***/ "./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/PageListModal.vue?vue&type=script&lang=js"
/*!**************************************************************************************************************************************!*\
  !*** ./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/PageListModal.vue?vue&type=script&lang=js ***!
  \**************************************************************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _nextcloud_l10n__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @nextcloud/l10n */ "./node_modules/@nextcloud/l10n/dist/index.mjs");
/* harmony import */ var _nextcloud_vue__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @nextcloud/vue */ "./node_modules/@nextcloud/vue/dist/index.mjs");
/* harmony import */ var vue_material_design_icons_Delete_vue__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! vue-material-design-icons/Delete.vue */ "./node_modules/vue-material-design-icons/Delete.vue");





/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = ({
  name: 'PageListModal',
  components: {
    NcModal: _nextcloud_vue__WEBPACK_IMPORTED_MODULE_1__.NcModal,
    NcListItem: _nextcloud_vue__WEBPACK_IMPORTED_MODULE_1__.NcListItem,
    NcButton: _nextcloud_vue__WEBPACK_IMPORTED_MODULE_1__.NcButton,
    Delete: vue_material_design_icons_Delete_vue__WEBPACK_IMPORTED_MODULE_2__["default"]
  },
  props: {
    pages: {
      type: Array,
      required: true
    }
  },
  emits: ['close', 'select', 'delete'],
  computed: {
    sortedPages() {
      return [...this.pages].sort((a, b) => {
        // Home page always first
        if (a.uniqueId === 'home') return -1;
        if (b.uniqueId === 'home') return 1;
        // Then by modified date
        return (b.modified || 0) - (a.modified || 0);
      });
    }
  },
  methods: {
    t(key, vars = {}) {
      return (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_0__.translate)('intravox', key, vars);
    },
    formatDate(timestamp) {
      if (!timestamp) return '';
      const date = new Date(timestamp * 1000);
      return date.toLocaleDateString('nl-NL', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
      });
    }
  }
});


/***/ },

/***/ "./src/components/PageListModal.vue?vue&type=style&index=0&id=2a35cee0&scoped=true&lang=css"
/*!**************************************************************************************************!*\
  !*** ./src/components/PageListModal.vue?vue&type=style&index=0&id=2a35cee0&scoped=true&lang=css ***!
  \**************************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _node_modules_style_loader_dist_cjs_js_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_dist_stylePostLoader_js_node_modules_vue_loader_dist_index_js_ruleSet_1_rules_5_use_0_PageListModal_vue_vue_type_style_index_0_id_2a35cee0_scoped_true_lang_css__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../node_modules/style-loader/dist/cjs.js!../../node_modules/css-loader/dist/cjs.js!../../node_modules/vue-loader/dist/stylePostLoader.js!../../node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./PageListModal.vue?vue&type=style&index=0&id=2a35cee0&scoped=true&lang=css */ "./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/dist/stylePostLoader.js!./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/PageListModal.vue?vue&type=style&index=0&id=2a35cee0&scoped=true&lang=css");


/***/ },

/***/ "./src/components/PageListModal.vue?vue&type=script&lang=js"
/*!******************************************************************!*\
  !*** ./src/components/PageListModal.vue?vue&type=script&lang=js ***!
  \******************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* reexport safe */ _node_modules_vue_loader_dist_index_js_ruleSet_1_rules_5_use_0_PageListModal_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_0__["default"])
/* harmony export */ });
/* harmony import */ var _node_modules_vue_loader_dist_index_js_ruleSet_1_rules_5_use_0_PageListModal_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./PageListModal.vue?vue&type=script&lang=js */ "./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/PageListModal.vue?vue&type=script&lang=js");
 

/***/ },

/***/ "./src/components/PageListModal.vue?vue&type=template&id=2a35cee0&scoped=true"
/*!************************************************************************************!*\
  !*** ./src/components/PageListModal.vue?vue&type=template&id=2a35cee0&scoped=true ***!
  \************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   render: () => (/* reexport safe */ _node_modules_vue_loader_dist_templateLoader_js_ruleSet_1_rules_2_node_modules_vue_loader_dist_index_js_ruleSet_1_rules_5_use_0_PageListModal_vue_vue_type_template_id_2a35cee0_scoped_true__WEBPACK_IMPORTED_MODULE_0__.render)
/* harmony export */ });
/* harmony import */ var _node_modules_vue_loader_dist_templateLoader_js_ruleSet_1_rules_2_node_modules_vue_loader_dist_index_js_ruleSet_1_rules_5_use_0_PageListModal_vue_vue_type_template_id_2a35cee0_scoped_true__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../node_modules/vue-loader/dist/templateLoader.js??ruleSet[1].rules[2]!../../node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./PageListModal.vue?vue&type=template&id=2a35cee0&scoped=true */ "./node_modules/vue-loader/dist/templateLoader.js??ruleSet[1].rules[2]!./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/PageListModal.vue?vue&type=template&id=2a35cee0&scoped=true");


/***/ },

/***/ "./node_modules/vue-loader/dist/templateLoader.js??ruleSet[1].rules[2]!./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/PageListModal.vue?vue&type=template&id=2a35cee0&scoped=true"
/*!******************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/vue-loader/dist/templateLoader.js??ruleSet[1].rules[2]!./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/PageListModal.vue?vue&type=template&id=2a35cee0&scoped=true ***!
  \******************************************************************************************************************************************************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   render: () => (/* binding */ render)
/* harmony export */ });
/* harmony import */ var vue__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! vue */ "./node_modules/vue/dist/vue.runtime.esm-bundler.js");


const _hoisted_1 = { class: "page-list-content" }
const _hoisted_2 = {
  key: 0,
  class: "empty-state"
}
const _hoisted_3 = {
  key: 1,
  class: "page-list"
}

function render(_ctx, _cache, $props, $setup, $data, $options) {
  const _component_Delete = (0,vue__WEBPACK_IMPORTED_MODULE_0__.resolveComponent)("Delete")
  const _component_NcButton = (0,vue__WEBPACK_IMPORTED_MODULE_0__.resolveComponent)("NcButton")
  const _component_NcListItem = (0,vue__WEBPACK_IMPORTED_MODULE_0__.resolveComponent)("NcListItem")
  const _component_NcModal = (0,vue__WEBPACK_IMPORTED_MODULE_0__.resolveComponent)("NcModal")

  return ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createBlock)(_component_NcModal, {
    onClose: _cache[0] || (_cache[0] = $event => (_ctx.$emit('close'))),
    name: $options.t('All pages'),
    size: "normal"
  }, {
    default: (0,vue__WEBPACK_IMPORTED_MODULE_0__.withCtx)(() => [
      (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("div", _hoisted_1, [
        ($props.pages.length === 0)
          ? ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)("div", _hoisted_2, [
              (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("p", null, (0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)($options.t('No pages created yet.')), 1 /* TEXT */)
            ]))
          : ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)("div", _hoisted_3, [
              ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(true), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)(vue__WEBPACK_IMPORTED_MODULE_0__.Fragment, null, (0,vue__WEBPACK_IMPORTED_MODULE_0__.renderList)($options.sortedPages, (page) => {
                return ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createBlock)(_component_NcListItem, {
                  key: page.uniqueId,
                  name: page.title,
                  details: $options.formatDate(page.modified),
                  onClick: $event => (_ctx.$emit('select', page.uniqueId))
                }, {
                  actions: (0,vue__WEBPACK_IMPORTED_MODULE_0__.withCtx)(() => [
                    (page.uniqueId !== 'home')
                      ? ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createBlock)(_component_NcButton, {
                          key: 0,
                          type: "error",
                          onClick: (0,vue__WEBPACK_IMPORTED_MODULE_0__.withModifiers)($event => (_ctx.$emit('delete', page.uniqueId)), ["stop"]),
                          "aria-label": $options.t('Delete')
                        }, {
                          icon: (0,vue__WEBPACK_IMPORTED_MODULE_0__.withCtx)(() => [
                            (0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_Delete, { size: 20 })
                          ]),
                          _: 1 /* STABLE */
                        }, 8 /* PROPS */, ["onClick", "aria-label"]))
                      : (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)("v-if", true)
                  ]),
                  _: 2 /* DYNAMIC */
                }, 1032 /* PROPS, DYNAMIC_SLOTS */, ["name", "details", "onClick"]))
              }), 128 /* KEYED_FRAGMENT */))
            ]))
      ])
    ]),
    _: 1 /* STABLE */
  }, 8 /* PROPS */, ["name"]))
}

/***/ }

}]);
//# sourceMappingURL=intravox-src_components_PageListModal_vue.87f541c5.js.map