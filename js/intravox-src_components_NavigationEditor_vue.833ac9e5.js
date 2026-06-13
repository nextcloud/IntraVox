"use strict";
(self["webpackChunknextcloud_intravox"] = self["webpackChunknextcloud_intravox"] || []).push([["src_components_NavigationEditor_vue"],{

/***/ "./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/dist/stylePostLoader.js!./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/NavigationEditor.vue?vue&type=style&index=0&id=6d220851&scoped=true&lang=css"
/*!*****************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/dist/stylePostLoader.js!./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/NavigationEditor.vue?vue&type=style&index=0&id=6d220851&scoped=true&lang=css ***!
  \*****************************************************************************************************************************************************************************************************************************************************************/
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
.navigation-editor[data-v-6d220851] {
  padding: 16px;
  max-height: 70vh;
  overflow-y: auto;
}
.editor-section[data-v-6d220851] {
  margin-bottom: 12px;
}
.editor-section label[data-v-6d220851] {
  display: block;
  font-weight: 600;
  margin-bottom: 6px;
  color: var(--color-main-text);
}
.section-header[data-v-6d220851] {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 12px;
}
.section-header h3[data-v-6d220851] {
  margin: 0;
  font-size: 15px;
  font-weight: 600;
}
.empty-state[data-v-6d220851] {
  padding: 40px;
  text-align: center;
  color: var(--color-text-maxcontrast);
  background: var(--color-background-dark);
  border-radius: var(--border-radius);
  border: 2px dashed var(--color-border);
}
.navigation-list[data-v-6d220851] {
  display: flex;
  flex-direction: column;
  gap: 6px;
}
.modal-actions-top[data-v-6d220851] {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding-bottom: 16px;
  border-bottom: 1px solid var(--color-border);
  margin-bottom: 16px;
}
.type-selector-left[data-v-6d220851] {
  display: flex;
  align-items: center;
  gap: 12px;
}
.type-selector-left label[data-v-6d220851] {
  font-weight: 600;
  color: var(--color-main-text);
  white-space: nowrap;
}
.action-buttons[data-v-6d220851] {
  display: flex;
  gap: 8px;
}
`, "",{"version":3,"sources":["webpack://./src/components/NavigationEditor.vue"],"names":[],"mappings":";AAgSA;EACE,aAAa;EACb,gBAAgB;EAChB,gBAAgB;AAClB;AAEA;EACE,mBAAmB;AACrB;AAEA;EACE,cAAc;EACd,gBAAgB;EAChB,kBAAkB;EAClB,6BAA6B;AAC/B;AAEA;EACE,aAAa;EACb,8BAA8B;EAC9B,mBAAmB;EACnB,mBAAmB;AACrB;AAEA;EACE,SAAS;EACT,eAAe;EACf,gBAAgB;AAClB;AAEA;EACE,aAAa;EACb,kBAAkB;EAClB,oCAAoC;EACpC,wCAAwC;EACxC,mCAAmC;EACnC,sCAAsC;AACxC;AAEA;EACE,aAAa;EACb,sBAAsB;EACtB,QAAQ;AACV;AAEA;EACE,aAAa;EACb,8BAA8B;EAC9B,mBAAmB;EACnB,oBAAoB;EACpB,4CAA4C;EAC5C,mBAAmB;AACrB;AAEA;EACE,aAAa;EACb,mBAAmB;EACnB,SAAS;AACX;AAEA;EACE,gBAAgB;EAChB,6BAA6B;EAC7B,mBAAmB;AACrB;AAEA;EACE,aAAa;EACb,QAAQ;AACV","sourcesContent":["<template>\r\n  <NcModal @close=\"$emit('close')\"\r\n           :name=\"t('Edit Navigation')\"\r\n           size=\"large\"\r\n           class=\"navigation-editor-modal\">\r\n    <div class=\"navigation-editor\">\r\n      <!-- Hidden element to capture autofocus -->\r\n      <input type=\"text\" style=\"position: absolute; opacity: 0; pointer-events: none;\" autofocus aria-label=\"Focus trap\" />\r\n\r\n      <!-- Actions at top with type selector on left -->\r\n      <div class=\"modal-actions-top\">\r\n        <div class=\"type-selector-left\">\r\n          <label>{{ t('Navigation Type:') }}</label>\r\n          <NcSelect v-model=\"selectedType\"\r\n                    :options=\"typeOptions\"\r\n                    :placeholder=\"t('Select navigation type')\"\r\n                    :clearable=\"false\"\r\n                    @update:modelValue=\"updateType\" />\r\n        </div>\r\n        <div class=\"action-buttons\">\r\n          <NcButton @click=\"$emit('close')\" type=\"secondary\">\r\n            {{ t('Cancel') }}\r\n          </NcButton>\r\n          <NcButton @click=\"save\" type=\"primary\">\r\n            <template #icon>\r\n              <ContentSave :size=\"20\" />\r\n            </template>\r\n            {{ t('Save Navigation') }}\r\n          </NcButton>\r\n        </div>\r\n      </div>\r\n\r\n      <!-- Navigation Items -->\r\n      <div class=\"editor-section\">\r\n        <div class=\"section-header\">\r\n          <h3>{{ t('Navigation Items') }}</h3>\r\n          <NcButton @click=\"addTopLevelItem\" type=\"primary\">\r\n            <template #icon>\r\n              <Plus :size=\"20\" />\r\n            </template>\r\n            {{ t('Add Item') }}\r\n          </NcButton>\r\n        </div>\r\n\r\n        <div v-if=\"localNavigation.items.length === 0\" class=\"empty-state\">\r\n          {{ t('No navigation items yet. Click \"Add Item\" to create one.') }}\r\n        </div>\r\n\r\n        <!-- Drag and drop list -->\r\n        <draggable v-model=\"localNavigation.items\"\r\n                   item-key=\"id\"\r\n                   handle=\".drag-handle\"\r\n                   group=\"navigation-items\"\r\n                   class=\"navigation-list\">\r\n          <template #item=\"{element, index}\">\r\n            <NavigationItem :item=\"element\"\r\n                           :index=\"index\"\r\n                           :level=\"1\"\r\n                           :is-first=\"index === 0\"\r\n                           :sibling-count=\"localNavigation.items.length\"\r\n                           @update=\"updateItem\"\r\n                           @delete=\"deleteItem\"\r\n                           @add-child=\"addChildItem\"\r\n                           @promote=\"promoteItem\"\r\n                           @demote=\"demoteItem\" />\r\n          </template>\r\n        </draggable>\r\n      </div>\r\n    </div>\r\n  </NcModal>\r\n</template>\r\n\r\n<script>\r\nimport { translate as t } from '@nextcloud/l10n';\r\nimport { NcModal, NcButton, NcSelect } from '@nextcloud/vue';\r\nimport draggable from 'vuedraggable';\r\nimport Plus from 'vue-material-design-icons/Plus.vue';\r\nimport ContentSave from 'vue-material-design-icons/ContentSave.vue';\r\nimport NavigationItem from './NavigationItem.vue';\r\n\r\nexport default {\r\n  name: 'NavigationEditor',\r\n  components: {\r\n    NcModal,\r\n    NcButton,\r\n    NcSelect,\r\n    draggable,\r\n    Plus,\r\n    ContentSave,\r\n    NavigationItem\r\n  },\r\n  props: {\r\n    navigation: {\r\n      type: Object,\r\n      required: true\r\n    }\r\n  },\r\n  emits: ['close', 'save'],\r\n  data() {\r\n    return {\r\n      localNavigation: JSON.parse(JSON.stringify(this.navigation)),\r\n      typeOptions: [\r\n        { value: 'dropdown', label: t('intravox', 'Dropdown (Cascading)') },\r\n        { value: 'megamenu', label: t('intravox', 'Mega Menu') }\r\n      ],\r\n      lastAddedItemId: null\r\n    };\r\n  },\r\n  computed: {\r\n    selectedType() {\r\n      const type = this.localNavigation.type || 'dropdown';\r\n      return this.typeOptions.find(opt => opt.value === type);\r\n    }\r\n  },\r\n  methods: {\r\n    t(key, vars = {}) {\r\n      return t('intravox', key, vars);\r\n    },\r\n    updateType(option) {\r\n      if (option) {\r\n        this.localNavigation.type = option.value;\r\n      }\r\n    },\r\n    addTopLevelItem() {\r\n      const newId = `nav_${Date.now()}`;\r\n      this.localNavigation.items.push({\r\n        id: newId,\r\n        title: t('intravox', 'New Item'),\r\n        uniqueId: null,\r\n        url: null,\r\n        children: []\r\n      });\r\n      this.lastAddedItemId = newId;\r\n      this.$nextTick(() => {\r\n        this.focusNewItem(newId);\r\n      });\r\n    },\r\n    addChildItem(parentPath) {\r\n      const parent = this.getItemByPath(parentPath);\r\n      if (parent && parent.children.length < 10) {\r\n        const newId = `nav_${Date.now()}`;\r\n        parent.children.push({\r\n          id: newId,\r\n          title: t('intravox', 'New Item'),\r\n          uniqueId: null,\r\n          url: null,\r\n          children: []\r\n        });\r\n        this.lastAddedItemId = newId;\r\n        this.$nextTick(() => {\r\n          this.focusNewItem(newId);\r\n        });\r\n      }\r\n    },\r\n    focusNewItem(itemId) {\r\n      // Find the input element with data-item-id attribute\r\n      const input = this.$el.querySelector(`input[data-item-id=\"${itemId}\"]`);\r\n      if (input) {\r\n        input.focus();\r\n        input.select();\r\n      }\r\n    },\r\n    updateItem(path, updates) {\r\n      const item = this.getItemByPath(path);\r\n      if (item) {\r\n        Object.assign(item, updates);\r\n      }\r\n    },\r\n    deleteItem(path) {\r\n      const pathParts = path.split('.');\r\n      if (pathParts.length === 1) {\r\n        // Top level\r\n        const index = parseInt(pathParts[0]);\r\n        this.localNavigation.items.splice(index, 1);\r\n      } else {\r\n        // Nested\r\n        const parentPath = pathParts.slice(0, -1).join('.');\r\n        const parent = this.getItemByPath(parentPath);\r\n        const childIndex = parseInt(pathParts[pathParts.length - 1]);\r\n        if (parent && parent.children) {\r\n          parent.children.splice(childIndex, 1);\r\n        }\r\n      }\r\n    },\r\n    getItemByPath(path) {\r\n      const parts = path.split('.').map(p => parseInt(p));\r\n      let current = this.localNavigation.items[parts[0]];\r\n\r\n      for (let i = 1; i < parts.length; i++) {\r\n        if (!current || !current.children) return null;\r\n        current = current.children[parts[i]];\r\n      }\r\n\r\n      return current;\r\n    },\r\n    getParentAndIndex(path) {\r\n      const parts = path.split('.').map(p => parseInt(p));\r\n\r\n      if (parts.length === 1) {\r\n        // Top level item\r\n        return {\r\n          parent: null,\r\n          parentList: this.localNavigation.items,\r\n          index: parts[0]\r\n        };\r\n      }\r\n\r\n      // Nested item - get the parent\r\n      const parentPath = parts.slice(0, -1);\r\n      let parent = this.localNavigation.items[parentPath[0]];\r\n\r\n      for (let i = 1; i < parentPath.length; i++) {\r\n        if (!parent || !parent.children) return null;\r\n        parent = parent.children[parentPath[i]];\r\n      }\r\n\r\n      return {\r\n        parent,\r\n        parentList: parent.children,\r\n        index: parts[parts.length - 1]\r\n      };\r\n    },\r\n    promoteItem(path) {\r\n      // Move item up one level (to parent's level, after the parent)\r\n      const parts = path.split('.').map(p => parseInt(p));\r\n\r\n      if (parts.length === 1) {\r\n        // Already at top level, can't promote\r\n        return;\r\n      }\r\n\r\n      // Get the item to promote\r\n      const info = this.getParentAndIndex(path);\r\n      if (!info) return;\r\n\r\n      const item = info.parentList[info.index];\r\n\r\n      // Remove from current location\r\n      info.parentList.splice(info.index, 1);\r\n\r\n      // Find grandparent's list and parent's index\r\n      if (parts.length === 2) {\r\n        // Parent is at top level\r\n        const parentIndex = parts[0];\r\n        // Insert after parent in top level\r\n        this.localNavigation.items.splice(parentIndex + 1, 0, item);\r\n      } else {\r\n        // Parent is nested - get grandparent\r\n        const grandparentPath = parts.slice(0, -2).join('.');\r\n        const grandparentInfo = this.getParentAndIndex(parts.slice(0, -1).join('.'));\r\n        if (grandparentInfo) {\r\n          // Insert after parent in grandparent's children\r\n          grandparentInfo.parentList.splice(grandparentInfo.index + 1, 0, item);\r\n        }\r\n      }\r\n    },\r\n    demoteItem(path) {\r\n      // Make item a child of the item above it\r\n      const info = this.getParentAndIndex(path);\r\n      if (!info || info.index === 0) {\r\n        // First item in list, can't demote\r\n        return;\r\n      }\r\n\r\n      const item = info.parentList[info.index];\r\n      const siblingAbove = info.parentList[info.index - 1];\r\n\r\n      if (!siblingAbove) return;\r\n\r\n      // Initialize children array if needed\r\n      if (!siblingAbove.children) {\r\n        siblingAbove.children = [];\r\n      }\r\n\r\n      // Remove from current location\r\n      info.parentList.splice(info.index, 1);\r\n\r\n      // Add as last child of sibling above\r\n      siblingAbove.children.push(item);\r\n    },\r\n    save() {\r\n      this.$emit('save', this.localNavigation);\r\n    }\r\n  }\r\n};\r\n</script>\r\n\r\n<style scoped>\r\n.navigation-editor {\r\n  padding: 16px;\r\n  max-height: 70vh;\r\n  overflow-y: auto;\r\n}\r\n\r\n.editor-section {\r\n  margin-bottom: 12px;\r\n}\r\n\r\n.editor-section label {\r\n  display: block;\r\n  font-weight: 600;\r\n  margin-bottom: 6px;\r\n  color: var(--color-main-text);\r\n}\r\n\r\n.section-header {\r\n  display: flex;\r\n  justify-content: space-between;\r\n  align-items: center;\r\n  margin-bottom: 12px;\r\n}\r\n\r\n.section-header h3 {\r\n  margin: 0;\r\n  font-size: 15px;\r\n  font-weight: 600;\r\n}\r\n\r\n.empty-state {\r\n  padding: 40px;\r\n  text-align: center;\r\n  color: var(--color-text-maxcontrast);\r\n  background: var(--color-background-dark);\r\n  border-radius: var(--border-radius);\r\n  border: 2px dashed var(--color-border);\r\n}\r\n\r\n.navigation-list {\r\n  display: flex;\r\n  flex-direction: column;\r\n  gap: 6px;\r\n}\r\n\r\n.modal-actions-top {\r\n  display: flex;\r\n  justify-content: space-between;\r\n  align-items: center;\r\n  padding-bottom: 16px;\r\n  border-bottom: 1px solid var(--color-border);\r\n  margin-bottom: 16px;\r\n}\r\n\r\n.type-selector-left {\r\n  display: flex;\r\n  align-items: center;\r\n  gap: 12px;\r\n}\r\n\r\n.type-selector-left label {\r\n  font-weight: 600;\r\n  color: var(--color-main-text);\r\n  white-space: nowrap;\r\n}\r\n\r\n.action-buttons {\r\n  display: flex;\r\n  gap: 8px;\r\n}\r\n</style>\r\n"],"sourceRoot":""}]);
// Exports
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (___CSS_LOADER_EXPORT___);


/***/ },

/***/ "./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/dist/stylePostLoader.js!./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/NavigationItem.vue?vue&type=style&index=0&id=00873c12&scoped=true&lang=css"
/*!***************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/dist/stylePostLoader.js!./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/NavigationItem.vue?vue&type=style&index=0&id=00873c12&scoped=true&lang=css ***!
  \***************************************************************************************************************************************************************************************************************************************************************/
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
.navigation-item[data-v-00873c12] {
  background: var(--color-main-background);
  border: 1px solid var(--color-border);
  border-radius: var(--border-radius);
  padding: 8px;
}
.navigation-item.level-2[data-v-00873c12],
.navigation-item.level-3[data-v-00873c12] {
  margin-left: 24px;
  margin-top: 6px;
}
.item-content[data-v-00873c12] {
  display: flex;
  align-items: flex-start;
  gap: 8px;
}
.drag-handle[data-v-00873c12] {
  cursor: grab;
  color: var(--color-text-maxcontrast);
  padding: 2px;
  flex-shrink: 0;
  margin-top: 2px;
}
.drag-handle[data-v-00873c12]:active {
  cursor: grabbing;
}
.item-details[data-v-00873c12] {
  flex: 1;
  display: flex;
  flex-direction: column;
  gap: 6px;
}
.item-title-input[data-v-00873c12] {
  width: 100%;
  padding: 6px 10px;
  border: 1px solid var(--color-border);
  border-radius: var(--border-radius);
  font-size: 13px;
  font-weight: 500;
}
.item-link-options[data-v-00873c12] {
  display: flex;
  align-items: center;
  gap: 6px;
}
.link-selector[data-v-00873c12] {
  flex: 1;
  min-width: 180px;
}
.link-selector.is-disabled[data-v-00873c12] {
  opacity: 0.5;
  pointer-events: none;
}
.or-separator[data-v-00873c12] {
  color: var(--color-text-maxcontrast);
  font-size: 11px;
  text-transform: uppercase;
}
.url-section[data-v-00873c12] {
  display: flex;
  align-items: center;
  gap: 6px;
  flex: 1;
}
.url-input[data-v-00873c12] {
  flex: 1;
  padding: 6px 10px;
  border: 1px solid var(--color-border);
  border-radius: var(--border-radius);
  font-size: 13px;
}
.url-input.has-value[data-v-00873c12] {
  border-color: var(--color-primary-element);
  background: var(--color-primary-element-light);
}
.clear-url-btn[data-v-00873c12] {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 28px;
  height: 28px;
  padding: 0;
  border: none;
  background: var(--color-background-dark);
  border-radius: var(--border-radius);
  cursor: pointer;
  color: var(--color-text-maxcontrast);
  flex-shrink: 0;
}
.clear-url-btn[data-v-00873c12]:hover {
  background: var(--color-error);
  color: white;
}
.target-selector[data-v-00873c12] {
  min-width: 120px;
  flex-shrink: 0;
}
.item-actions[data-v-00873c12] {
  display: flex;
  gap: 2px;
  flex-shrink: 0;
  margin-top: 2px;
}
.children-list[data-v-00873c12] {
  margin-top: 6px;
  display: flex;
  flex-direction: column;
  gap: 6px;
}
`, "",{"version":3,"sources":["webpack://./src/components/NavigationItem.vue"],"names":[],"mappings":";AA0QA;EACE,wCAAwC;EACxC,qCAAqC;EACrC,mCAAmC;EACnC,YAAY;AACd;AAEA;;EAEE,iBAAiB;EACjB,eAAe;AACjB;AAEA;EACE,aAAa;EACb,uBAAuB;EACvB,QAAQ;AACV;AAEA;EACE,YAAY;EACZ,oCAAoC;EACpC,YAAY;EACZ,cAAc;EACd,eAAe;AACjB;AAEA;EACE,gBAAgB;AAClB;AAEA;EACE,OAAO;EACP,aAAa;EACb,sBAAsB;EACtB,QAAQ;AACV;AAEA;EACE,WAAW;EACX,iBAAiB;EACjB,qCAAqC;EACrC,mCAAmC;EACnC,eAAe;EACf,gBAAgB;AAClB;AAEA;EACE,aAAa;EACb,mBAAmB;EACnB,QAAQ;AACV;AAEA;EACE,OAAO;EACP,gBAAgB;AAClB;AAEA;EACE,YAAY;EACZ,oBAAoB;AACtB;AAEA;EACE,oCAAoC;EACpC,eAAe;EACf,yBAAyB;AAC3B;AAEA;EACE,aAAa;EACb,mBAAmB;EACnB,QAAQ;EACR,OAAO;AACT;AAEA;EACE,OAAO;EACP,iBAAiB;EACjB,qCAAqC;EACrC,mCAAmC;EACnC,eAAe;AACjB;AAEA;EACE,0CAA0C;EAC1C,8CAA8C;AAChD;AAEA;EACE,aAAa;EACb,mBAAmB;EACnB,uBAAuB;EACvB,WAAW;EACX,YAAY;EACZ,UAAU;EACV,YAAY;EACZ,wCAAwC;EACxC,mCAAmC;EACnC,eAAe;EACf,oCAAoC;EACpC,cAAc;AAChB;AAEA;EACE,8BAA8B;EAC9B,YAAY;AACd;AAEA;EACE,gBAAgB;EAChB,cAAc;AAChB;AAEA;EACE,aAAa;EACb,QAAQ;EACR,cAAc;EACd,eAAe;AACjB;AAEA;EACE,eAAe;EACf,aAAa;EACb,sBAAsB;EACtB,QAAQ;AACV","sourcesContent":["<template>\r\n  <div class=\"navigation-item\" :class=\"`level-${level}`\">\r\n    <div class=\"item-content\">\r\n      <!-- Drag Handle -->\r\n      <div class=\"drag-handle\">\r\n        <DragVertical :size=\"20\" />\r\n      </div>\r\n\r\n      <!-- Item Details -->\r\n      <div class=\"item-details\">\r\n        <input v-model=\"localItem.title\"\r\n               type=\"text\"\r\n               class=\"item-title-input\"\r\n               :placeholder=\"t('Item title')\"\r\n               :data-item-id=\"item.id\"\r\n               @input=\"emitUpdate\" />\r\n\r\n        <div class=\"item-link-options\">\r\n          <!-- Link to Page (disabled when URL is set) -->\r\n          <PageTreeSelect\r\n            :model-value=\"localItem.uniqueId\"\r\n            :placeholder=\"t('Link to page')\"\r\n            :disabled=\"!!localItem.url\"\r\n            @select=\"updatePageLink\"\r\n            class=\"link-selector\"\r\n            :class=\"{ 'is-disabled': !!localItem.url }\" />\r\n\r\n          <!-- OR Custom URL -->\r\n          <span class=\"or-separator\">{{ t('or') }}</span>\r\n\r\n          <div class=\"url-section\">\r\n            <input v-model=\"localItem.url\"\r\n                   type=\"url\"\r\n                   class=\"url-input\"\r\n                   :class=\"{ 'has-value': !!localItem.url }\"\r\n                   :placeholder=\"t('Custom URL')\"\r\n                   @input=\"onUrlInput\" />\r\n\r\n            <!-- Clear URL button -->\r\n            <button v-if=\"localItem.url\"\r\n                    type=\"button\"\r\n                    class=\"clear-url-btn\"\r\n                    @click=\"clearUrl\"\r\n                    :aria-label=\"t('Clear URL')\">\r\n              <Close :size=\"16\" />\r\n            </button>\r\n\r\n            <!-- Target selector (for URLs) -->\r\n            <NcSelect v-if=\"localItem.url\"\r\n                      :model-value=\"selectedTarget\"\r\n                      :options=\"targetOptions\"\r\n                      @update:modelValue=\"updateTarget\"\r\n                      class=\"target-selector\" />\r\n          </div>\r\n        </div>\r\n      </div>\r\n\r\n      <!-- Actions -->\r\n      <div class=\"item-actions\">\r\n        <!-- Promote: move item up one level (to parent's level) -->\r\n        <NcButton v-if=\"canPromote\"\r\n                  @click=\"$emit('promote', itemPath)\"\r\n                  type=\"tertiary\"\r\n                  :aria-label=\"t('Promote (move up one level)')\">\r\n          <template #icon>\r\n            <ChevronLeft :size=\"20\" />\r\n          </template>\r\n        </NcButton>\r\n\r\n        <!-- Demote: make item a child of the item above -->\r\n        <NcButton v-if=\"canDemote\"\r\n                  @click=\"$emit('demote', itemPath)\"\r\n                  type=\"tertiary\"\r\n                  :aria-label=\"t('Demote (make child of item above)')\">\r\n          <template #icon>\r\n            <ChevronRight :size=\"20\" />\r\n          </template>\r\n        </NcButton>\r\n\r\n        <NcButton v-if=\"level < 3\"\r\n                  @click=\"$emit('add-child', itemPath)\"\r\n                  type=\"tertiary\"\r\n                  :aria-label=\"t('Add sub-item')\">\r\n          <template #icon>\r\n            <Plus :size=\"20\" />\r\n          </template>\r\n        </NcButton>\r\n\r\n        <NcButton @click=\"$emit('delete', itemPath)\"\r\n                  type=\"error\"\r\n                  :aria-label=\"t('Delete item')\">\r\n          <template #icon>\r\n            <Delete :size=\"20\" />\r\n          </template>\r\n        </NcButton>\r\n      </div>\r\n    </div>\r\n\r\n    <!-- Children (recursive) -->\r\n    <draggable v-if=\"localItem.children && localItem.children.length > 0\"\r\n               v-model=\"localItem.children\"\r\n               item-key=\"id\"\r\n               handle=\".drag-handle\"\r\n               group=\"navigation-items\"\r\n               @change=\"onChildrenChange\"\r\n               class=\"children-list\">\r\n      <template #item=\"{element, index}\">\r\n        <NavigationItem :item=\"element\"\r\n                       :index=\"index\"\r\n                       :level=\"level + 1\"\r\n                       :parent-path=\"itemPath\"\r\n                       :is-first=\"index === 0\"\r\n                       :sibling-count=\"localItem.children.length\"\r\n                       @update=\"(path, updates) => $emit('update', path, updates)\"\r\n                       @delete=\"(path) => $emit('delete', path)\"\r\n                       @add-child=\"(path) => $emit('add-child', path)\"\r\n                       @promote=\"(path) => $emit('promote', path)\"\r\n                       @demote=\"(path) => $emit('demote', path)\" />\r\n      </template>\r\n    </draggable>\r\n  </div>\r\n</template>\r\n\r\n<script>\r\nimport { translate as t } from '@nextcloud/l10n';\r\nimport { NcButton, NcSelect } from '@nextcloud/vue';\r\nimport draggable from 'vuedraggable';\r\nimport DragVertical from 'vue-material-design-icons/DragVertical.vue';\r\nimport Plus from 'vue-material-design-icons/Plus.vue';\r\nimport Delete from 'vue-material-design-icons/Delete.vue';\r\nimport Close from 'vue-material-design-icons/Close.vue';\r\nimport ChevronLeft from 'vue-material-design-icons/ChevronLeft.vue';\r\nimport ChevronRight from 'vue-material-design-icons/ChevronRight.vue';\r\nimport PageTreeSelect from './PageTreeSelect.vue';\r\n\r\nexport default {\r\n  name: 'NavigationItem',\r\n  components: {\r\n    NcButton,\r\n    NcSelect,\r\n    draggable,\r\n    DragVertical,\r\n    Plus,\r\n    Delete,\r\n    Close,\r\n    ChevronLeft,\r\n    ChevronRight,\r\n    PageTreeSelect\r\n  },\r\n  props: {\r\n    item: {\r\n      type: Object,\r\n      required: true\r\n    },\r\n    index: {\r\n      type: Number,\r\n      required: true\r\n    },\r\n    level: {\r\n      type: Number,\r\n      default: 1\r\n    },\r\n    parentPath: {\r\n      type: String,\r\n      default: ''\r\n    },\r\n    isFirst: {\r\n      type: Boolean,\r\n      default: false\r\n    },\r\n    siblingCount: {\r\n      type: Number,\r\n      default: 1\r\n    }\r\n  },\r\n  emits: ['update', 'delete', 'add-child', 'promote', 'demote'],\r\n  data() {\r\n    return {\r\n      localItem: JSON.parse(JSON.stringify(this.item)),\r\n      targetOptions: [\r\n        { value: '_self', label: t('intravox', 'Same tab') },\r\n        { value: '_blank', label: t('intravox', 'New tab') }\r\n      ]\r\n    };\r\n  },\r\n  computed: {\r\n    itemPath() {\r\n      return this.parentPath\r\n        ? `${this.parentPath}.${this.index}`\r\n        : `${this.index}`;\r\n    },\r\n    selectedTarget() {\r\n      const target = this.localItem.target || '_self';\r\n      return this.targetOptions.find(opt => opt.value === target);\r\n    },\r\n    canPromote() {\r\n      // Can promote if not at top level (level > 1)\r\n      return this.level > 1;\r\n    },\r\n    canDemote() {\r\n      // Can demote if:\r\n      // - Not the first item in the list (needs a sibling above to become parent)\r\n      // - Current level < 3 (max depth)\r\n      return !this.isFirst && this.level < 3;\r\n    }\r\n  },\r\n  watch: {\r\n    item: {\r\n      deep: true,\r\n      handler(newItem) {\r\n        this.localItem = JSON.parse(JSON.stringify(newItem));\r\n      }\r\n    }\r\n  },\r\n  methods: {\r\n    t(key, vars = {}) {\r\n      return t('intravox', key, vars);\r\n    },\r\n    emitUpdate() {\r\n      this.$emit('update', this.itemPath, this.localItem);\r\n    },\r\n    updatePageLink(page) {\r\n      if (page) {\r\n        this.localItem.uniqueId = page.uniqueId;\r\n        this.localItem.url = null;\r\n\r\n        // Auto-fill title with page name if current title is empty or \"New Item\"\r\n        const currentTitle = this.localItem.title?.trim() || '';\r\n        const isNewItem = currentTitle === '' || currentTitle === this.t('New Item');\r\n        if (isNewItem) {\r\n          this.localItem.title = page.title;\r\n        }\r\n      } else {\r\n        this.localItem.uniqueId = null;\r\n      }\r\n      this.emitUpdate();\r\n    },\r\n    updateTarget(option) {\r\n      if (option) {\r\n        this.localItem.target = option.value;\r\n      } else {\r\n        this.localItem.target = '_self';\r\n      }\r\n      this.emitUpdate();\r\n    },\r\n    onUrlInput() {\r\n      // When URL is entered, clear the page selection\r\n      if (this.localItem.url && this.localItem.url.trim()) {\r\n        this.localItem.uniqueId = null;\r\n      }\r\n      this.emitUpdate();\r\n    },\r\n    clearUrl() {\r\n      this.localItem.url = null;\r\n      this.localItem.target = '_self';\r\n      this.emitUpdate();\r\n    },\r\n    onChildrenChange() {\r\n      // When children are reordered, emit update for this item\r\n      this.emitUpdate();\r\n    }\r\n  }\r\n};\r\n</script>\r\n\r\n<style scoped>\r\n.navigation-item {\r\n  background: var(--color-main-background);\r\n  border: 1px solid var(--color-border);\r\n  border-radius: var(--border-radius);\r\n  padding: 8px;\r\n}\r\n\r\n.navigation-item.level-2,\r\n.navigation-item.level-3 {\r\n  margin-left: 24px;\r\n  margin-top: 6px;\r\n}\r\n\r\n.item-content {\r\n  display: flex;\r\n  align-items: flex-start;\r\n  gap: 8px;\r\n}\r\n\r\n.drag-handle {\r\n  cursor: grab;\r\n  color: var(--color-text-maxcontrast);\r\n  padding: 2px;\r\n  flex-shrink: 0;\r\n  margin-top: 2px;\r\n}\r\n\r\n.drag-handle:active {\r\n  cursor: grabbing;\r\n}\r\n\r\n.item-details {\r\n  flex: 1;\r\n  display: flex;\r\n  flex-direction: column;\r\n  gap: 6px;\r\n}\r\n\r\n.item-title-input {\r\n  width: 100%;\r\n  padding: 6px 10px;\r\n  border: 1px solid var(--color-border);\r\n  border-radius: var(--border-radius);\r\n  font-size: 13px;\r\n  font-weight: 500;\r\n}\r\n\r\n.item-link-options {\r\n  display: flex;\r\n  align-items: center;\r\n  gap: 6px;\r\n}\r\n\r\n.link-selector {\r\n  flex: 1;\r\n  min-width: 180px;\r\n}\r\n\r\n.link-selector.is-disabled {\r\n  opacity: 0.5;\r\n  pointer-events: none;\r\n}\r\n\r\n.or-separator {\r\n  color: var(--color-text-maxcontrast);\r\n  font-size: 11px;\r\n  text-transform: uppercase;\r\n}\r\n\r\n.url-section {\r\n  display: flex;\r\n  align-items: center;\r\n  gap: 6px;\r\n  flex: 1;\r\n}\r\n\r\n.url-input {\r\n  flex: 1;\r\n  padding: 6px 10px;\r\n  border: 1px solid var(--color-border);\r\n  border-radius: var(--border-radius);\r\n  font-size: 13px;\r\n}\r\n\r\n.url-input.has-value {\r\n  border-color: var(--color-primary-element);\r\n  background: var(--color-primary-element-light);\r\n}\r\n\r\n.clear-url-btn {\r\n  display: flex;\r\n  align-items: center;\r\n  justify-content: center;\r\n  width: 28px;\r\n  height: 28px;\r\n  padding: 0;\r\n  border: none;\r\n  background: var(--color-background-dark);\r\n  border-radius: var(--border-radius);\r\n  cursor: pointer;\r\n  color: var(--color-text-maxcontrast);\r\n  flex-shrink: 0;\r\n}\r\n\r\n.clear-url-btn:hover {\r\n  background: var(--color-error);\r\n  color: white;\r\n}\r\n\r\n.target-selector {\r\n  min-width: 120px;\r\n  flex-shrink: 0;\r\n}\r\n\r\n.item-actions {\r\n  display: flex;\r\n  gap: 2px;\r\n  flex-shrink: 0;\r\n  margin-top: 2px;\r\n}\r\n\r\n.children-list {\r\n  margin-top: 6px;\r\n  display: flex;\r\n  flex-direction: column;\r\n  gap: 6px;\r\n}\r\n</style>\r\n"],"sourceRoot":""}]);
// Exports
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (___CSS_LOADER_EXPORT___);


/***/ },

/***/ "./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/dist/stylePostLoader.js!./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/NavigationEditor.vue?vue&type=style&index=0&id=6d220851&scoped=true&lang=css"
/*!*********************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/dist/stylePostLoader.js!./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/NavigationEditor.vue?vue&type=style&index=0&id=6d220851&scoped=true&lang=css ***!
  \*********************************************************************************************************************************************************************************************************************************************************************************************************/
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
/* harmony import */ var _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_dist_stylePostLoader_js_node_modules_vue_loader_dist_index_js_ruleSet_1_rules_5_use_0_NavigationEditor_vue_vue_type_style_index_0_id_6d220851_scoped_true_lang_css__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! !!../../node_modules/css-loader/dist/cjs.js!../../node_modules/vue-loader/dist/stylePostLoader.js!../../node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./NavigationEditor.vue?vue&type=style&index=0&id=6d220851&scoped=true&lang=css */ "./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/dist/stylePostLoader.js!./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/NavigationEditor.vue?vue&type=style&index=0&id=6d220851&scoped=true&lang=css");

      
      
      
      
      
      
      
      
      

var options = {};

options.styleTagTransform = (_node_modules_style_loader_dist_runtime_styleTagTransform_js__WEBPACK_IMPORTED_MODULE_5___default());
options.setAttributes = (_node_modules_style_loader_dist_runtime_setAttributesWithoutAttributes_js__WEBPACK_IMPORTED_MODULE_3___default());

      options.insert = _node_modules_style_loader_dist_runtime_insertBySelector_js__WEBPACK_IMPORTED_MODULE_2___default().bind(null, "head");
    
options.domAPI = (_node_modules_style_loader_dist_runtime_styleDomAPI_js__WEBPACK_IMPORTED_MODULE_1___default());
options.insertStyleElement = (_node_modules_style_loader_dist_runtime_insertStyleElement_js__WEBPACK_IMPORTED_MODULE_4___default());

var update = _node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0___default()(_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_dist_stylePostLoader_js_node_modules_vue_loader_dist_index_js_ruleSet_1_rules_5_use_0_NavigationEditor_vue_vue_type_style_index_0_id_6d220851_scoped_true_lang_css__WEBPACK_IMPORTED_MODULE_6__["default"], options);




       /* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_dist_stylePostLoader_js_node_modules_vue_loader_dist_index_js_ruleSet_1_rules_5_use_0_NavigationEditor_vue_vue_type_style_index_0_id_6d220851_scoped_true_lang_css__WEBPACK_IMPORTED_MODULE_6__["default"] && _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_dist_stylePostLoader_js_node_modules_vue_loader_dist_index_js_ruleSet_1_rules_5_use_0_NavigationEditor_vue_vue_type_style_index_0_id_6d220851_scoped_true_lang_css__WEBPACK_IMPORTED_MODULE_6__["default"].locals ? _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_dist_stylePostLoader_js_node_modules_vue_loader_dist_index_js_ruleSet_1_rules_5_use_0_NavigationEditor_vue_vue_type_style_index_0_id_6d220851_scoped_true_lang_css__WEBPACK_IMPORTED_MODULE_6__["default"].locals : undefined);


/***/ },

/***/ "./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/dist/stylePostLoader.js!./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/NavigationItem.vue?vue&type=style&index=0&id=00873c12&scoped=true&lang=css"
/*!*******************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/dist/stylePostLoader.js!./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/NavigationItem.vue?vue&type=style&index=0&id=00873c12&scoped=true&lang=css ***!
  \*******************************************************************************************************************************************************************************************************************************************************************************************************/
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
/* harmony import */ var _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_dist_stylePostLoader_js_node_modules_vue_loader_dist_index_js_ruleSet_1_rules_5_use_0_NavigationItem_vue_vue_type_style_index_0_id_00873c12_scoped_true_lang_css__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! !!../../node_modules/css-loader/dist/cjs.js!../../node_modules/vue-loader/dist/stylePostLoader.js!../../node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./NavigationItem.vue?vue&type=style&index=0&id=00873c12&scoped=true&lang=css */ "./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/dist/stylePostLoader.js!./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/NavigationItem.vue?vue&type=style&index=0&id=00873c12&scoped=true&lang=css");

      
      
      
      
      
      
      
      
      

var options = {};

options.styleTagTransform = (_node_modules_style_loader_dist_runtime_styleTagTransform_js__WEBPACK_IMPORTED_MODULE_5___default());
options.setAttributes = (_node_modules_style_loader_dist_runtime_setAttributesWithoutAttributes_js__WEBPACK_IMPORTED_MODULE_3___default());

      options.insert = _node_modules_style_loader_dist_runtime_insertBySelector_js__WEBPACK_IMPORTED_MODULE_2___default().bind(null, "head");
    
options.domAPI = (_node_modules_style_loader_dist_runtime_styleDomAPI_js__WEBPACK_IMPORTED_MODULE_1___default());
options.insertStyleElement = (_node_modules_style_loader_dist_runtime_insertStyleElement_js__WEBPACK_IMPORTED_MODULE_4___default());

var update = _node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0___default()(_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_dist_stylePostLoader_js_node_modules_vue_loader_dist_index_js_ruleSet_1_rules_5_use_0_NavigationItem_vue_vue_type_style_index_0_id_00873c12_scoped_true_lang_css__WEBPACK_IMPORTED_MODULE_6__["default"], options);




       /* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_dist_stylePostLoader_js_node_modules_vue_loader_dist_index_js_ruleSet_1_rules_5_use_0_NavigationItem_vue_vue_type_style_index_0_id_00873c12_scoped_true_lang_css__WEBPACK_IMPORTED_MODULE_6__["default"] && _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_dist_stylePostLoader_js_node_modules_vue_loader_dist_index_js_ruleSet_1_rules_5_use_0_NavigationItem_vue_vue_type_style_index_0_id_00873c12_scoped_true_lang_css__WEBPACK_IMPORTED_MODULE_6__["default"].locals ? _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_dist_stylePostLoader_js_node_modules_vue_loader_dist_index_js_ruleSet_1_rules_5_use_0_NavigationItem_vue_vue_type_style_index_0_id_00873c12_scoped_true_lang_css__WEBPACK_IMPORTED_MODULE_6__["default"].locals : undefined);


/***/ },

/***/ "./src/components/NavigationEditor.vue"
/*!*********************************************!*\
  !*** ./src/components/NavigationEditor.vue ***!
  \*********************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _NavigationEditor_vue_vue_type_template_id_6d220851_scoped_true__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./NavigationEditor.vue?vue&type=template&id=6d220851&scoped=true */ "./src/components/NavigationEditor.vue?vue&type=template&id=6d220851&scoped=true");
/* harmony import */ var _NavigationEditor_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./NavigationEditor.vue?vue&type=script&lang=js */ "./src/components/NavigationEditor.vue?vue&type=script&lang=js");
/* harmony import */ var _NavigationEditor_vue_vue_type_style_index_0_id_6d220851_scoped_true_lang_css__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./NavigationEditor.vue?vue&type=style&index=0&id=6d220851&scoped=true&lang=css */ "./src/components/NavigationEditor.vue?vue&type=style&index=0&id=6d220851&scoped=true&lang=css");
/* harmony import */ var _node_modules_vue_loader_dist_exportHelper_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ../../node_modules/vue-loader/dist/exportHelper.js */ "./node_modules/vue-loader/dist/exportHelper.js");




;


const __exports__ = /*#__PURE__*/(0,_node_modules_vue_loader_dist_exportHelper_js__WEBPACK_IMPORTED_MODULE_3__["default"])(_NavigationEditor_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_1__["default"], [['render',_NavigationEditor_vue_vue_type_template_id_6d220851_scoped_true__WEBPACK_IMPORTED_MODULE_0__.render],['__scopeId',"data-v-6d220851"],['__file',"src/components/NavigationEditor.vue"]])
/* hot reload */
if (false) // removed by dead control flow
{}


/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (__exports__);

/***/ },

/***/ "./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/NavigationEditor.vue?vue&type=script&lang=js"
/*!*****************************************************************************************************************************************!*\
  !*** ./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/NavigationEditor.vue?vue&type=script&lang=js ***!
  \*****************************************************************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _nextcloud_l10n__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @nextcloud/l10n */ "./node_modules/@nextcloud/l10n/dist/index.mjs");
/* harmony import */ var _nextcloud_vue__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @nextcloud/vue */ "./node_modules/@nextcloud/vue/dist/index.mjs");
/* harmony import */ var vuedraggable__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! vuedraggable */ "./node_modules/vuedraggable/dist/vuedraggable.umd.js");
/* harmony import */ var vuedraggable__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(vuedraggable__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var vue_material_design_icons_Plus_vue__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! vue-material-design-icons/Plus.vue */ "./node_modules/vue-material-design-icons/Plus.vue");
/* harmony import */ var vue_material_design_icons_ContentSave_vue__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! vue-material-design-icons/ContentSave.vue */ "./node_modules/vue-material-design-icons/ContentSave.vue");
/* harmony import */ var _NavigationItem_vue__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! ./NavigationItem.vue */ "./src/components/NavigationItem.vue");








/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = ({
  name: 'NavigationEditor',
  components: {
    NcModal: _nextcloud_vue__WEBPACK_IMPORTED_MODULE_1__.NcModal,
    NcButton: _nextcloud_vue__WEBPACK_IMPORTED_MODULE_1__.NcButton,
    NcSelect: _nextcloud_vue__WEBPACK_IMPORTED_MODULE_1__.NcSelect,
    draggable: (vuedraggable__WEBPACK_IMPORTED_MODULE_2___default()),
    Plus: vue_material_design_icons_Plus_vue__WEBPACK_IMPORTED_MODULE_3__["default"],
    ContentSave: vue_material_design_icons_ContentSave_vue__WEBPACK_IMPORTED_MODULE_4__["default"],
    NavigationItem: _NavigationItem_vue__WEBPACK_IMPORTED_MODULE_5__["default"]
  },
  props: {
    navigation: {
      type: Object,
      required: true
    }
  },
  emits: ['close', 'save'],
  data() {
    return {
      localNavigation: JSON.parse(JSON.stringify(this.navigation)),
      typeOptions: [
        { value: 'dropdown', label: (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_0__.translate)('intravox', 'Dropdown (Cascading)') },
        { value: 'megamenu', label: (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_0__.translate)('intravox', 'Mega Menu') }
      ],
      lastAddedItemId: null
    };
  },
  computed: {
    selectedType() {
      const type = this.localNavigation.type || 'dropdown';
      return this.typeOptions.find(opt => opt.value === type);
    }
  },
  methods: {
    t(key, vars = {}) {
      return (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_0__.translate)('intravox', key, vars);
    },
    updateType(option) {
      if (option) {
        this.localNavigation.type = option.value;
      }
    },
    addTopLevelItem() {
      const newId = `nav_${Date.now()}`;
      this.localNavigation.items.push({
        id: newId,
        title: (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_0__.translate)('intravox', 'New Item'),
        uniqueId: null,
        url: null,
        children: []
      });
      this.lastAddedItemId = newId;
      this.$nextTick(() => {
        this.focusNewItem(newId);
      });
    },
    addChildItem(parentPath) {
      const parent = this.getItemByPath(parentPath);
      if (parent && parent.children.length < 10) {
        const newId = `nav_${Date.now()}`;
        parent.children.push({
          id: newId,
          title: (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_0__.translate)('intravox', 'New Item'),
          uniqueId: null,
          url: null,
          children: []
        });
        this.lastAddedItemId = newId;
        this.$nextTick(() => {
          this.focusNewItem(newId);
        });
      }
    },
    focusNewItem(itemId) {
      // Find the input element with data-item-id attribute
      const input = this.$el.querySelector(`input[data-item-id="${itemId}"]`);
      if (input) {
        input.focus();
        input.select();
      }
    },
    updateItem(path, updates) {
      const item = this.getItemByPath(path);
      if (item) {
        Object.assign(item, updates);
      }
    },
    deleteItem(path) {
      const pathParts = path.split('.');
      if (pathParts.length === 1) {
        // Top level
        const index = parseInt(pathParts[0]);
        this.localNavigation.items.splice(index, 1);
      } else {
        // Nested
        const parentPath = pathParts.slice(0, -1).join('.');
        const parent = this.getItemByPath(parentPath);
        const childIndex = parseInt(pathParts[pathParts.length - 1]);
        if (parent && parent.children) {
          parent.children.splice(childIndex, 1);
        }
      }
    },
    getItemByPath(path) {
      const parts = path.split('.').map(p => parseInt(p));
      let current = this.localNavigation.items[parts[0]];

      for (let i = 1; i < parts.length; i++) {
        if (!current || !current.children) return null;
        current = current.children[parts[i]];
      }

      return current;
    },
    getParentAndIndex(path) {
      const parts = path.split('.').map(p => parseInt(p));

      if (parts.length === 1) {
        // Top level item
        return {
          parent: null,
          parentList: this.localNavigation.items,
          index: parts[0]
        };
      }

      // Nested item - get the parent
      const parentPath = parts.slice(0, -1);
      let parent = this.localNavigation.items[parentPath[0]];

      for (let i = 1; i < parentPath.length; i++) {
        if (!parent || !parent.children) return null;
        parent = parent.children[parentPath[i]];
      }

      return {
        parent,
        parentList: parent.children,
        index: parts[parts.length - 1]
      };
    },
    promoteItem(path) {
      // Move item up one level (to parent's level, after the parent)
      const parts = path.split('.').map(p => parseInt(p));

      if (parts.length === 1) {
        // Already at top level, can't promote
        return;
      }

      // Get the item to promote
      const info = this.getParentAndIndex(path);
      if (!info) return;

      const item = info.parentList[info.index];

      // Remove from current location
      info.parentList.splice(info.index, 1);

      // Find grandparent's list and parent's index
      if (parts.length === 2) {
        // Parent is at top level
        const parentIndex = parts[0];
        // Insert after parent in top level
        this.localNavigation.items.splice(parentIndex + 1, 0, item);
      } else {
        // Parent is nested - get grandparent
        const grandparentPath = parts.slice(0, -2).join('.');
        const grandparentInfo = this.getParentAndIndex(parts.slice(0, -1).join('.'));
        if (grandparentInfo) {
          // Insert after parent in grandparent's children
          grandparentInfo.parentList.splice(grandparentInfo.index + 1, 0, item);
        }
      }
    },
    demoteItem(path) {
      // Make item a child of the item above it
      const info = this.getParentAndIndex(path);
      if (!info || info.index === 0) {
        // First item in list, can't demote
        return;
      }

      const item = info.parentList[info.index];
      const siblingAbove = info.parentList[info.index - 1];

      if (!siblingAbove) return;

      // Initialize children array if needed
      if (!siblingAbove.children) {
        siblingAbove.children = [];
      }

      // Remove from current location
      info.parentList.splice(info.index, 1);

      // Add as last child of sibling above
      siblingAbove.children.push(item);
    },
    save() {
      this.$emit('save', this.localNavigation);
    }
  }
});


/***/ },

/***/ "./src/components/NavigationItem.vue"
/*!*******************************************!*\
  !*** ./src/components/NavigationItem.vue ***!
  \*******************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _NavigationItem_vue_vue_type_template_id_00873c12_scoped_true__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./NavigationItem.vue?vue&type=template&id=00873c12&scoped=true */ "./src/components/NavigationItem.vue?vue&type=template&id=00873c12&scoped=true");
/* harmony import */ var _NavigationItem_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./NavigationItem.vue?vue&type=script&lang=js */ "./src/components/NavigationItem.vue?vue&type=script&lang=js");
/* harmony import */ var _NavigationItem_vue_vue_type_style_index_0_id_00873c12_scoped_true_lang_css__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./NavigationItem.vue?vue&type=style&index=0&id=00873c12&scoped=true&lang=css */ "./src/components/NavigationItem.vue?vue&type=style&index=0&id=00873c12&scoped=true&lang=css");
/* harmony import */ var _node_modules_vue_loader_dist_exportHelper_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ../../node_modules/vue-loader/dist/exportHelper.js */ "./node_modules/vue-loader/dist/exportHelper.js");




;


const __exports__ = /*#__PURE__*/(0,_node_modules_vue_loader_dist_exportHelper_js__WEBPACK_IMPORTED_MODULE_3__["default"])(_NavigationItem_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_1__["default"], [['render',_NavigationItem_vue_vue_type_template_id_00873c12_scoped_true__WEBPACK_IMPORTED_MODULE_0__.render],['__scopeId',"data-v-00873c12"],['__file',"src/components/NavigationItem.vue"]])
/* hot reload */
if (false) // removed by dead control flow
{}


/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (__exports__);

/***/ },

/***/ "./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/NavigationItem.vue?vue&type=script&lang=js"
/*!***************************************************************************************************************************************!*\
  !*** ./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/NavigationItem.vue?vue&type=script&lang=js ***!
  \***************************************************************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _nextcloud_l10n__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @nextcloud/l10n */ "./node_modules/@nextcloud/l10n/dist/index.mjs");
/* harmony import */ var _nextcloud_vue__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @nextcloud/vue */ "./node_modules/@nextcloud/vue/dist/index.mjs");
/* harmony import */ var vuedraggable__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! vuedraggable */ "./node_modules/vuedraggable/dist/vuedraggable.umd.js");
/* harmony import */ var vuedraggable__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(vuedraggable__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var vue_material_design_icons_DragVertical_vue__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! vue-material-design-icons/DragVertical.vue */ "./node_modules/vue-material-design-icons/DragVertical.vue");
/* harmony import */ var vue_material_design_icons_Plus_vue__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! vue-material-design-icons/Plus.vue */ "./node_modules/vue-material-design-icons/Plus.vue");
/* harmony import */ var vue_material_design_icons_Delete_vue__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! vue-material-design-icons/Delete.vue */ "./node_modules/vue-material-design-icons/Delete.vue");
/* harmony import */ var vue_material_design_icons_Close_vue__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! vue-material-design-icons/Close.vue */ "./node_modules/vue-material-design-icons/Close.vue");
/* harmony import */ var vue_material_design_icons_ChevronLeft_vue__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! vue-material-design-icons/ChevronLeft.vue */ "./node_modules/vue-material-design-icons/ChevronLeft.vue");
/* harmony import */ var vue_material_design_icons_ChevronRight_vue__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__(/*! vue-material-design-icons/ChevronRight.vue */ "./node_modules/vue-material-design-icons/ChevronRight.vue");
/* harmony import */ var _PageTreeSelect_vue__WEBPACK_IMPORTED_MODULE_9__ = __webpack_require__(/*! ./PageTreeSelect.vue */ "./src/components/PageTreeSelect.vue");












/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = ({
  name: 'NavigationItem',
  components: {
    NcButton: _nextcloud_vue__WEBPACK_IMPORTED_MODULE_1__.NcButton,
    NcSelect: _nextcloud_vue__WEBPACK_IMPORTED_MODULE_1__.NcSelect,
    draggable: (vuedraggable__WEBPACK_IMPORTED_MODULE_2___default()),
    DragVertical: vue_material_design_icons_DragVertical_vue__WEBPACK_IMPORTED_MODULE_3__["default"],
    Plus: vue_material_design_icons_Plus_vue__WEBPACK_IMPORTED_MODULE_4__["default"],
    Delete: vue_material_design_icons_Delete_vue__WEBPACK_IMPORTED_MODULE_5__["default"],
    Close: vue_material_design_icons_Close_vue__WEBPACK_IMPORTED_MODULE_6__["default"],
    ChevronLeft: vue_material_design_icons_ChevronLeft_vue__WEBPACK_IMPORTED_MODULE_7__["default"],
    ChevronRight: vue_material_design_icons_ChevronRight_vue__WEBPACK_IMPORTED_MODULE_8__["default"],
    PageTreeSelect: _PageTreeSelect_vue__WEBPACK_IMPORTED_MODULE_9__["default"]
  },
  props: {
    item: {
      type: Object,
      required: true
    },
    index: {
      type: Number,
      required: true
    },
    level: {
      type: Number,
      default: 1
    },
    parentPath: {
      type: String,
      default: ''
    },
    isFirst: {
      type: Boolean,
      default: false
    },
    siblingCount: {
      type: Number,
      default: 1
    }
  },
  emits: ['update', 'delete', 'add-child', 'promote', 'demote'],
  data() {
    return {
      localItem: JSON.parse(JSON.stringify(this.item)),
      targetOptions: [
        { value: '_self', label: (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_0__.translate)('intravox', 'Same tab') },
        { value: '_blank', label: (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_0__.translate)('intravox', 'New tab') }
      ]
    };
  },
  computed: {
    itemPath() {
      return this.parentPath
        ? `${this.parentPath}.${this.index}`
        : `${this.index}`;
    },
    selectedTarget() {
      const target = this.localItem.target || '_self';
      return this.targetOptions.find(opt => opt.value === target);
    },
    canPromote() {
      // Can promote if not at top level (level > 1)
      return this.level > 1;
    },
    canDemote() {
      // Can demote if:
      // - Not the first item in the list (needs a sibling above to become parent)
      // - Current level < 3 (max depth)
      return !this.isFirst && this.level < 3;
    }
  },
  watch: {
    item: {
      deep: true,
      handler(newItem) {
        this.localItem = JSON.parse(JSON.stringify(newItem));
      }
    }
  },
  methods: {
    t(key, vars = {}) {
      return (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_0__.translate)('intravox', key, vars);
    },
    emitUpdate() {
      this.$emit('update', this.itemPath, this.localItem);
    },
    updatePageLink(page) {
      if (page) {
        this.localItem.uniqueId = page.uniqueId;
        this.localItem.url = null;

        // Auto-fill title with page name if current title is empty or "New Item"
        const currentTitle = this.localItem.title?.trim() || '';
        const isNewItem = currentTitle === '' || currentTitle === this.t('New Item');
        if (isNewItem) {
          this.localItem.title = page.title;
        }
      } else {
        this.localItem.uniqueId = null;
      }
      this.emitUpdate();
    },
    updateTarget(option) {
      if (option) {
        this.localItem.target = option.value;
      } else {
        this.localItem.target = '_self';
      }
      this.emitUpdate();
    },
    onUrlInput() {
      // When URL is entered, clear the page selection
      if (this.localItem.url && this.localItem.url.trim()) {
        this.localItem.uniqueId = null;
      }
      this.emitUpdate();
    },
    clearUrl() {
      this.localItem.url = null;
      this.localItem.target = '_self';
      this.emitUpdate();
    },
    onChildrenChange() {
      // When children are reordered, emit update for this item
      this.emitUpdate();
    }
  }
});


/***/ },

/***/ "./src/components/NavigationEditor.vue?vue&type=style&index=0&id=6d220851&scoped=true&lang=css"
/*!*****************************************************************************************************!*\
  !*** ./src/components/NavigationEditor.vue?vue&type=style&index=0&id=6d220851&scoped=true&lang=css ***!
  \*****************************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _node_modules_style_loader_dist_cjs_js_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_dist_stylePostLoader_js_node_modules_vue_loader_dist_index_js_ruleSet_1_rules_5_use_0_NavigationEditor_vue_vue_type_style_index_0_id_6d220851_scoped_true_lang_css__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../node_modules/style-loader/dist/cjs.js!../../node_modules/css-loader/dist/cjs.js!../../node_modules/vue-loader/dist/stylePostLoader.js!../../node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./NavigationEditor.vue?vue&type=style&index=0&id=6d220851&scoped=true&lang=css */ "./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/dist/stylePostLoader.js!./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/NavigationEditor.vue?vue&type=style&index=0&id=6d220851&scoped=true&lang=css");


/***/ },

/***/ "./src/components/NavigationItem.vue?vue&type=style&index=0&id=00873c12&scoped=true&lang=css"
/*!***************************************************************************************************!*\
  !*** ./src/components/NavigationItem.vue?vue&type=style&index=0&id=00873c12&scoped=true&lang=css ***!
  \***************************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _node_modules_style_loader_dist_cjs_js_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_dist_stylePostLoader_js_node_modules_vue_loader_dist_index_js_ruleSet_1_rules_5_use_0_NavigationItem_vue_vue_type_style_index_0_id_00873c12_scoped_true_lang_css__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../node_modules/style-loader/dist/cjs.js!../../node_modules/css-loader/dist/cjs.js!../../node_modules/vue-loader/dist/stylePostLoader.js!../../node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./NavigationItem.vue?vue&type=style&index=0&id=00873c12&scoped=true&lang=css */ "./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/dist/stylePostLoader.js!./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/NavigationItem.vue?vue&type=style&index=0&id=00873c12&scoped=true&lang=css");


/***/ },

/***/ "./src/components/NavigationEditor.vue?vue&type=script&lang=js"
/*!*********************************************************************!*\
  !*** ./src/components/NavigationEditor.vue?vue&type=script&lang=js ***!
  \*********************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* reexport safe */ _node_modules_vue_loader_dist_index_js_ruleSet_1_rules_5_use_0_NavigationEditor_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_0__["default"])
/* harmony export */ });
/* harmony import */ var _node_modules_vue_loader_dist_index_js_ruleSet_1_rules_5_use_0_NavigationEditor_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./NavigationEditor.vue?vue&type=script&lang=js */ "./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/NavigationEditor.vue?vue&type=script&lang=js");
 

/***/ },

/***/ "./src/components/NavigationItem.vue?vue&type=script&lang=js"
/*!*******************************************************************!*\
  !*** ./src/components/NavigationItem.vue?vue&type=script&lang=js ***!
  \*******************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* reexport safe */ _node_modules_vue_loader_dist_index_js_ruleSet_1_rules_5_use_0_NavigationItem_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_0__["default"])
/* harmony export */ });
/* harmony import */ var _node_modules_vue_loader_dist_index_js_ruleSet_1_rules_5_use_0_NavigationItem_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./NavigationItem.vue?vue&type=script&lang=js */ "./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/NavigationItem.vue?vue&type=script&lang=js");
 

/***/ },

/***/ "./src/components/NavigationEditor.vue?vue&type=template&id=6d220851&scoped=true"
/*!***************************************************************************************!*\
  !*** ./src/components/NavigationEditor.vue?vue&type=template&id=6d220851&scoped=true ***!
  \***************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   render: () => (/* reexport safe */ _node_modules_vue_loader_dist_templateLoader_js_ruleSet_1_rules_2_node_modules_vue_loader_dist_index_js_ruleSet_1_rules_5_use_0_NavigationEditor_vue_vue_type_template_id_6d220851_scoped_true__WEBPACK_IMPORTED_MODULE_0__.render)
/* harmony export */ });
/* harmony import */ var _node_modules_vue_loader_dist_templateLoader_js_ruleSet_1_rules_2_node_modules_vue_loader_dist_index_js_ruleSet_1_rules_5_use_0_NavigationEditor_vue_vue_type_template_id_6d220851_scoped_true__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../node_modules/vue-loader/dist/templateLoader.js??ruleSet[1].rules[2]!../../node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./NavigationEditor.vue?vue&type=template&id=6d220851&scoped=true */ "./node_modules/vue-loader/dist/templateLoader.js??ruleSet[1].rules[2]!./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/NavigationEditor.vue?vue&type=template&id=6d220851&scoped=true");


/***/ },

/***/ "./src/components/NavigationItem.vue?vue&type=template&id=00873c12&scoped=true"
/*!*************************************************************************************!*\
  !*** ./src/components/NavigationItem.vue?vue&type=template&id=00873c12&scoped=true ***!
  \*************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   render: () => (/* reexport safe */ _node_modules_vue_loader_dist_templateLoader_js_ruleSet_1_rules_2_node_modules_vue_loader_dist_index_js_ruleSet_1_rules_5_use_0_NavigationItem_vue_vue_type_template_id_00873c12_scoped_true__WEBPACK_IMPORTED_MODULE_0__.render)
/* harmony export */ });
/* harmony import */ var _node_modules_vue_loader_dist_templateLoader_js_ruleSet_1_rules_2_node_modules_vue_loader_dist_index_js_ruleSet_1_rules_5_use_0_NavigationItem_vue_vue_type_template_id_00873c12_scoped_true__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../node_modules/vue-loader/dist/templateLoader.js??ruleSet[1].rules[2]!../../node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./NavigationItem.vue?vue&type=template&id=00873c12&scoped=true */ "./node_modules/vue-loader/dist/templateLoader.js??ruleSet[1].rules[2]!./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/NavigationItem.vue?vue&type=template&id=00873c12&scoped=true");


/***/ },

/***/ "./node_modules/vue-loader/dist/templateLoader.js??ruleSet[1].rules[2]!./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/NavigationEditor.vue?vue&type=template&id=6d220851&scoped=true"
/*!*********************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/vue-loader/dist/templateLoader.js??ruleSet[1].rules[2]!./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/NavigationEditor.vue?vue&type=template&id=6d220851&scoped=true ***!
  \*********************************************************************************************************************************************************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   render: () => (/* binding */ render)
/* harmony export */ });
/* harmony import */ var vue__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! vue */ "./node_modules/vue/dist/vue.runtime.esm-bundler.js");


const _hoisted_1 = { class: "navigation-editor" }
const _hoisted_2 = { class: "modal-actions-top" }
const _hoisted_3 = { class: "type-selector-left" }
const _hoisted_4 = { class: "action-buttons" }
const _hoisted_5 = { class: "editor-section" }
const _hoisted_6 = { class: "section-header" }
const _hoisted_7 = {
  key: 0,
  class: "empty-state"
}

function render(_ctx, _cache, $props, $setup, $data, $options) {
  const _component_NcSelect = (0,vue__WEBPACK_IMPORTED_MODULE_0__.resolveComponent)("NcSelect")
  const _component_NcButton = (0,vue__WEBPACK_IMPORTED_MODULE_0__.resolveComponent)("NcButton")
  const _component_ContentSave = (0,vue__WEBPACK_IMPORTED_MODULE_0__.resolveComponent)("ContentSave")
  const _component_Plus = (0,vue__WEBPACK_IMPORTED_MODULE_0__.resolveComponent)("Plus")
  const _component_NavigationItem = (0,vue__WEBPACK_IMPORTED_MODULE_0__.resolveComponent)("NavigationItem")
  const _component_draggable = (0,vue__WEBPACK_IMPORTED_MODULE_0__.resolveComponent)("draggable")
  const _component_NcModal = (0,vue__WEBPACK_IMPORTED_MODULE_0__.resolveComponent)("NcModal")

  return ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createBlock)(_component_NcModal, {
    onClose: _cache[3] || (_cache[3] = $event => (_ctx.$emit('close'))),
    name: $options.t('Edit Navigation'),
    size: "large",
    class: "navigation-editor-modal"
  }, {
    default: (0,vue__WEBPACK_IMPORTED_MODULE_0__.withCtx)(() => [
      (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("div", _hoisted_1, [
        (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)(" Hidden element to capture autofocus "),
        _cache[4] || (_cache[4] = (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("input", {
          type: "text",
          style: {"position":"absolute","opacity":"0","pointer-events":"none"},
          autofocus: "",
          "aria-label": "Focus trap"
        }, null, -1 /* CACHED */)),
        (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)(" Actions at top with type selector on left "),
        (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("div", _hoisted_2, [
          (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("div", _hoisted_3, [
            (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("label", null, (0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)($options.t('Navigation Type:')), 1 /* TEXT */),
            (0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_NcSelect, {
              modelValue: $options.selectedType,
              "onUpdate:modelValue": [
                _cache[0] || (_cache[0] = $event => (($options.selectedType) = $event)),
                $options.updateType
              ],
              options: $data.typeOptions,
              placeholder: $options.t('Select navigation type'),
              clearable: false
            }, null, 8 /* PROPS */, ["modelValue", "options", "placeholder", "onUpdate:modelValue"])
          ]),
          (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("div", _hoisted_4, [
            (0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_NcButton, {
              onClick: _cache[1] || (_cache[1] = $event => (_ctx.$emit('close'))),
              type: "secondary"
            }, {
              default: (0,vue__WEBPACK_IMPORTED_MODULE_0__.withCtx)(() => [
                (0,vue__WEBPACK_IMPORTED_MODULE_0__.createTextVNode)((0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)($options.t('Cancel')), 1 /* TEXT */)
              ]),
              _: 1 /* STABLE */
            }),
            (0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_NcButton, {
              onClick: $options.save,
              type: "primary"
            }, {
              icon: (0,vue__WEBPACK_IMPORTED_MODULE_0__.withCtx)(() => [
                (0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_ContentSave, { size: 20 })
              ]),
              default: (0,vue__WEBPACK_IMPORTED_MODULE_0__.withCtx)(() => [
                (0,vue__WEBPACK_IMPORTED_MODULE_0__.createTextVNode)(" " + (0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)($options.t('Save Navigation')), 1 /* TEXT */)
              ]),
              _: 1 /* STABLE */
            }, 8 /* PROPS */, ["onClick"])
          ])
        ]),
        (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)(" Navigation Items "),
        (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("div", _hoisted_5, [
          (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("div", _hoisted_6, [
            (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("h3", null, (0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)($options.t('Navigation Items')), 1 /* TEXT */),
            (0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_NcButton, {
              onClick: $options.addTopLevelItem,
              type: "primary"
            }, {
              icon: (0,vue__WEBPACK_IMPORTED_MODULE_0__.withCtx)(() => [
                (0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_Plus, { size: 20 })
              ]),
              default: (0,vue__WEBPACK_IMPORTED_MODULE_0__.withCtx)(() => [
                (0,vue__WEBPACK_IMPORTED_MODULE_0__.createTextVNode)(" " + (0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)($options.t('Add Item')), 1 /* TEXT */)
              ]),
              _: 1 /* STABLE */
            }, 8 /* PROPS */, ["onClick"])
          ]),
          ($data.localNavigation.items.length === 0)
            ? ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)("div", _hoisted_7, (0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)($options.t('No navigation items yet. Click "Add Item" to create one.')), 1 /* TEXT */))
            : (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)("v-if", true),
          (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)(" Drag and drop list "),
          (0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_draggable, {
            modelValue: $data.localNavigation.items,
            "onUpdate:modelValue": _cache[2] || (_cache[2] = $event => (($data.localNavigation.items) = $event)),
            "item-key": "id",
            handle: ".drag-handle",
            group: "navigation-items",
            class: "navigation-list"
          }, {
            item: (0,vue__WEBPACK_IMPORTED_MODULE_0__.withCtx)(({element, index}) => [
              (0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_NavigationItem, {
                item: element,
                index: index,
                level: 1,
                "is-first": index === 0,
                "sibling-count": $data.localNavigation.items.length,
                onUpdate: $options.updateItem,
                onDelete: $options.deleteItem,
                onAddChild: $options.addChildItem,
                onPromote: $options.promoteItem,
                onDemote: $options.demoteItem
              }, null, 8 /* PROPS */, ["item", "index", "is-first", "sibling-count", "onUpdate", "onDelete", "onAddChild", "onPromote", "onDemote"])
            ]),
            _: 1 /* STABLE */
          }, 8 /* PROPS */, ["modelValue"])
        ])
      ])
    ]),
    _: 1 /* STABLE */
  }, 8 /* PROPS */, ["name"]))
}

/***/ },

/***/ "./node_modules/vue-loader/dist/templateLoader.js??ruleSet[1].rules[2]!./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/NavigationItem.vue?vue&type=template&id=00873c12&scoped=true"
/*!*******************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/vue-loader/dist/templateLoader.js??ruleSet[1].rules[2]!./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/NavigationItem.vue?vue&type=template&id=00873c12&scoped=true ***!
  \*******************************************************************************************************************************************************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   render: () => (/* binding */ render)
/* harmony export */ });
/* harmony import */ var vue__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! vue */ "./node_modules/vue/dist/vue.runtime.esm-bundler.js");


const _hoisted_1 = { class: "item-content" }
const _hoisted_2 = { class: "drag-handle" }
const _hoisted_3 = { class: "item-details" }
const _hoisted_4 = ["placeholder", "data-item-id"]
const _hoisted_5 = { class: "item-link-options" }
const _hoisted_6 = { class: "or-separator" }
const _hoisted_7 = { class: "url-section" }
const _hoisted_8 = ["placeholder"]
const _hoisted_9 = ["aria-label"]
const _hoisted_10 = { class: "item-actions" }

function render(_ctx, _cache, $props, $setup, $data, $options) {
  const _component_DragVertical = (0,vue__WEBPACK_IMPORTED_MODULE_0__.resolveComponent)("DragVertical")
  const _component_PageTreeSelect = (0,vue__WEBPACK_IMPORTED_MODULE_0__.resolveComponent)("PageTreeSelect")
  const _component_Close = (0,vue__WEBPACK_IMPORTED_MODULE_0__.resolveComponent)("Close")
  const _component_NcSelect = (0,vue__WEBPACK_IMPORTED_MODULE_0__.resolveComponent)("NcSelect")
  const _component_ChevronLeft = (0,vue__WEBPACK_IMPORTED_MODULE_0__.resolveComponent)("ChevronLeft")
  const _component_NcButton = (0,vue__WEBPACK_IMPORTED_MODULE_0__.resolveComponent)("NcButton")
  const _component_ChevronRight = (0,vue__WEBPACK_IMPORTED_MODULE_0__.resolveComponent)("ChevronRight")
  const _component_Plus = (0,vue__WEBPACK_IMPORTED_MODULE_0__.resolveComponent)("Plus")
  const _component_Delete = (0,vue__WEBPACK_IMPORTED_MODULE_0__.resolveComponent)("Delete")
  const _component_NavigationItem = (0,vue__WEBPACK_IMPORTED_MODULE_0__.resolveComponent)("NavigationItem", true)
  const _component_draggable = (0,vue__WEBPACK_IMPORTED_MODULE_0__.resolveComponent)("draggable")

  return ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)("div", {
    class: (0,vue__WEBPACK_IMPORTED_MODULE_0__.normalizeClass)(["navigation-item", `level-${$props.level}`])
  }, [
    (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("div", _hoisted_1, [
      (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)(" Drag Handle "),
      (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("div", _hoisted_2, [
        (0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_DragVertical, { size: 20 })
      ]),
      (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)(" Item Details "),
      (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("div", _hoisted_3, [
        (0,vue__WEBPACK_IMPORTED_MODULE_0__.withDirectives)((0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("input", {
          "onUpdate:modelValue": _cache[0] || (_cache[0] = $event => (($data.localItem.title) = $event)),
          type: "text",
          class: "item-title-input",
          placeholder: $options.t('Item title'),
          "data-item-id": $props.item.id,
          onInput: _cache[1] || (_cache[1] = (...args) => ($options.emitUpdate && $options.emitUpdate(...args)))
        }, null, 40 /* PROPS, NEED_HYDRATION */, _hoisted_4), [
          [vue__WEBPACK_IMPORTED_MODULE_0__.vModelText, $data.localItem.title]
        ]),
        (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("div", _hoisted_5, [
          (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)(" Link to Page (disabled when URL is set) "),
          (0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_PageTreeSelect, {
            "model-value": $data.localItem.uniqueId,
            placeholder: $options.t('Link to page'),
            disabled: !!$data.localItem.url,
            onSelect: $options.updatePageLink,
            class: (0,vue__WEBPACK_IMPORTED_MODULE_0__.normalizeClass)(["link-selector", { 'is-disabled': !!$data.localItem.url }])
          }, null, 8 /* PROPS */, ["model-value", "placeholder", "disabled", "onSelect", "class"]),
          (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)(" OR Custom URL "),
          (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("span", _hoisted_6, (0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)($options.t('or')), 1 /* TEXT */),
          (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("div", _hoisted_7, [
            (0,vue__WEBPACK_IMPORTED_MODULE_0__.withDirectives)((0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("input", {
              "onUpdate:modelValue": _cache[2] || (_cache[2] = $event => (($data.localItem.url) = $event)),
              type: "url",
              class: (0,vue__WEBPACK_IMPORTED_MODULE_0__.normalizeClass)(["url-input", { 'has-value': !!$data.localItem.url }]),
              placeholder: $options.t('Custom URL'),
              onInput: _cache[3] || (_cache[3] = (...args) => ($options.onUrlInput && $options.onUrlInput(...args)))
            }, null, 42 /* CLASS, PROPS, NEED_HYDRATION */, _hoisted_8), [
              [vue__WEBPACK_IMPORTED_MODULE_0__.vModelText, $data.localItem.url]
            ]),
            (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)(" Clear URL button "),
            ($data.localItem.url)
              ? ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)("button", {
                  key: 0,
                  type: "button",
                  class: "clear-url-btn",
                  onClick: _cache[4] || (_cache[4] = (...args) => ($options.clearUrl && $options.clearUrl(...args))),
                  "aria-label": $options.t('Clear URL')
                }, [
                  (0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_Close, { size: 16 })
                ], 8 /* PROPS */, _hoisted_9))
              : (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)("v-if", true),
            (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)(" Target selector (for URLs) "),
            ($data.localItem.url)
              ? ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createBlock)(_component_NcSelect, {
                  key: 1,
                  "model-value": $options.selectedTarget,
                  options: $data.targetOptions,
                  "onUpdate:modelValue": $options.updateTarget,
                  class: "target-selector"
                }, null, 8 /* PROPS */, ["model-value", "options", "onUpdate:modelValue"]))
              : (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)("v-if", true)
          ])
        ])
      ]),
      (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)(" Actions "),
      (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("div", _hoisted_10, [
        (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)(" Promote: move item up one level (to parent's level) "),
        ($options.canPromote)
          ? ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createBlock)(_component_NcButton, {
              key: 0,
              onClick: _cache[5] || (_cache[5] = $event => (_ctx.$emit('promote', $options.itemPath))),
              type: "tertiary",
              "aria-label": $options.t('Promote (move up one level)')
            }, {
              icon: (0,vue__WEBPACK_IMPORTED_MODULE_0__.withCtx)(() => [
                (0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_ChevronLeft, { size: 20 })
              ]),
              _: 1 /* STABLE */
            }, 8 /* PROPS */, ["aria-label"]))
          : (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)("v-if", true),
        (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)(" Demote: make item a child of the item above "),
        ($options.canDemote)
          ? ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createBlock)(_component_NcButton, {
              key: 1,
              onClick: _cache[6] || (_cache[6] = $event => (_ctx.$emit('demote', $options.itemPath))),
              type: "tertiary",
              "aria-label": $options.t('Demote (make child of item above)')
            }, {
              icon: (0,vue__WEBPACK_IMPORTED_MODULE_0__.withCtx)(() => [
                (0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_ChevronRight, { size: 20 })
              ]),
              _: 1 /* STABLE */
            }, 8 /* PROPS */, ["aria-label"]))
          : (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)("v-if", true),
        ($props.level < 3)
          ? ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createBlock)(_component_NcButton, {
              key: 2,
              onClick: _cache[7] || (_cache[7] = $event => (_ctx.$emit('add-child', $options.itemPath))),
              type: "tertiary",
              "aria-label": $options.t('Add sub-item')
            }, {
              icon: (0,vue__WEBPACK_IMPORTED_MODULE_0__.withCtx)(() => [
                (0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_Plus, { size: 20 })
              ]),
              _: 1 /* STABLE */
            }, 8 /* PROPS */, ["aria-label"]))
          : (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)("v-if", true),
        (0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_NcButton, {
          onClick: _cache[8] || (_cache[8] = $event => (_ctx.$emit('delete', $options.itemPath))),
          type: "error",
          "aria-label": $options.t('Delete item')
        }, {
          icon: (0,vue__WEBPACK_IMPORTED_MODULE_0__.withCtx)(() => [
            (0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_Delete, { size: 20 })
          ]),
          _: 1 /* STABLE */
        }, 8 /* PROPS */, ["aria-label"])
      ])
    ]),
    (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)(" Children (recursive) "),
    ($data.localItem.children && $data.localItem.children.length > 0)
      ? ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createBlock)(_component_draggable, {
          key: 0,
          modelValue: $data.localItem.children,
          "onUpdate:modelValue": _cache[14] || (_cache[14] = $event => (($data.localItem.children) = $event)),
          "item-key": "id",
          handle: ".drag-handle",
          group: "navigation-items",
          onChange: $options.onChildrenChange,
          class: "children-list"
        }, {
          item: (0,vue__WEBPACK_IMPORTED_MODULE_0__.withCtx)(({element, index}) => [
            (0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_NavigationItem, {
              item: element,
              index: index,
              level: $props.level + 1,
              "parent-path": $options.itemPath,
              "is-first": index === 0,
              "sibling-count": $data.localItem.children.length,
              onUpdate: _cache[9] || (_cache[9] = (path, updates) => _ctx.$emit('update', path, updates)),
              onDelete: _cache[10] || (_cache[10] = (path) => _ctx.$emit('delete', path)),
              onAddChild: _cache[11] || (_cache[11] = (path) => _ctx.$emit('add-child', path)),
              onPromote: _cache[12] || (_cache[12] = (path) => _ctx.$emit('promote', path)),
              onDemote: _cache[13] || (_cache[13] = (path) => _ctx.$emit('demote', path))
            }, null, 8 /* PROPS */, ["item", "index", "level", "parent-path", "is-first", "sibling-count"])
          ]),
          _: 1 /* STABLE */
        }, 8 /* PROPS */, ["modelValue", "onChange"]))
      : (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)("v-if", true)
  ], 2 /* CLASS */))
}

/***/ }

}]);
//# sourceMappingURL=intravox-src_components_NavigationEditor_vue.833ac9e5.js.map