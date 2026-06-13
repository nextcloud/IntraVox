(self["webpackChunknextcloud_intravox"] = self["webpackChunknextcloud_intravox"] || []).push([["shared"],{

/***/ "./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/dist/stylePostLoader.js!./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/FeedWidget.vue?vue&type=style&index=0&id=642986b2&scoped=true&lang=css"
/*!***********************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/dist/stylePostLoader.js!./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/FeedWidget.vue?vue&type=style&index=0&id=642986b2&scoped=true&lang=css ***!
  \***********************************************************************************************************************************************************************************************************************************************************/
(module, __webpack_exports__, __webpack_require__) {

"use strict";
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
.feed-widget[data-v-642986b2] {
  width: 100%;
  min-width: 0;
  overflow: hidden;
}
.feed-widget-loading[data-v-642986b2],
.feed-widget-error[data-v-642986b2],
.feed-widget-empty[data-v-642986b2] {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 32px;
  gap: 8px;
  color: var(--color-text-maxcontrast);
  text-align: center;
}
.feed-widget-error[data-v-642986b2] {
  color: var(--color-main-text);
  background: var(--color-error);
  background: color-mix(in srgb, var(--color-error) 10%, transparent);
  border: 1px solid color-mix(in srgb, var(--color-error) 30%, transparent);
  border-radius: var(--border-radius-large);
  padding: 20px 24px;
  margin: 8px;
}
.feed-widget-error p[data-v-642986b2] {
  margin: 0;
  font-size: 14px;
  color: var(--color-main-text);
}
`, "",{"version":3,"sources":["webpack://./src/components/FeedWidget.vue"],"names":[],"mappings":";AA6MA;EACE,WAAW;EACX,YAAY;EACZ,gBAAgB;AAClB;AAEA;;;EAGE,aAAa;EACb,sBAAsB;EACtB,mBAAmB;EACnB,uBAAuB;EACvB,aAAa;EACb,QAAQ;EACR,oCAAoC;EACpC,kBAAkB;AACpB;AAEA;EACE,6BAA6B;EAC7B,8BAA8B;EAC9B,mEAAmE;EACnE,yEAAyE;EACzE,yCAAyC;EACzC,kBAAkB;EAClB,WAAW;AACb;AAEA;EACE,SAAS;EACT,eAAe;EACf,6BAA6B;AAC/B","sourcesContent":["<template>\n  <div class=\"feed-widget\" aria-live=\"polite\">\n    <div v-if=\"loading\" class=\"feed-widget-loading\" role=\"status\">\n      <NcLoadingIcon :size=\"32\" />\n      <p>{{ t('Loading feed...') }}</p>\n    </div>\n\n    <div v-else-if=\"error\" class=\"feed-widget-error\">\n      <AlertCircle :size=\"32\" />\n      <p>{{ error }}</p>\n    </div>\n\n    <div v-else-if=\"items.length === 0\" class=\"feed-widget-empty\">\n      <RssBox :size=\"32\" />\n      <p v-if=\"widget.filterKeyword\">{{ t('No items match your filter.') }}</p>\n      <p v-else>{{ t('No items found') }}</p>\n    </div>\n\n    <component\n      v-else\n      :is=\"layoutComponent\"\n      :items=\"items\"\n      :widget=\"widget\"\n      :feed-image=\"feedImage\"\n      :row-background-color=\"rowBackgroundColor\"\n    />\n  </div>\n</template>\n\n<script>\nimport axios from '@nextcloud/axios';\nimport { generateUrl } from '@nextcloud/router';\nimport { NcLoadingIcon } from '@nextcloud/vue';\nimport AlertCircle from 'vue-material-design-icons/AlertCircle.vue';\nimport RssBox from 'vue-material-design-icons/RssBox.vue';\nimport FeedLayoutList from './feed/FeedLayoutList.vue';\nimport FeedLayoutGrid from './feed/FeedLayoutGrid.vue';\n\nexport default {\n  name: 'FeedWidget',\n  components: {\n    NcLoadingIcon,\n    AlertCircle,\n    RssBox,\n    FeedLayoutList,\n    FeedLayoutGrid,\n  },\n  props: {\n    widget: {\n      type: Object,\n      required: true,\n    },\n    shareToken: {\n      type: String,\n      default: '',\n    },\n    pageId: {\n      type: String,\n      default: '',\n    },\n    rowBackgroundColor: {\n      type: String,\n      default: '',\n    },\n  },\n  data() {\n    return {\n      items: [],\n      feedImage: null,\n      loading: true,\n      error: null,\n    };\n  },\n  computed: {\n    layoutComponent() {\n      const layouts = {\n        list: FeedLayoutList,\n        grid: FeedLayoutGrid,\n      };\n      return layouts[this.widget.layout] || FeedLayoutList;\n    },\n  },\n  watch: {\n    widget: {\n      handler() {\n        clearTimeout(this._debounceTimer);\n        this._debounceTimer = setTimeout(() => this.fetchFeed(), 300);\n      },\n      deep: true,\n    },\n  },\n  mounted() {\n    if (typeof requestIdleCallback === 'function') {\n      requestIdleCallback(() => this.fetchFeed());\n    } else {\n      this.fetchFeed();\n    }\n    // Auto-refresh every 15 minutes (matches backend cache TTL)\n    this._refreshInterval = setInterval(() => this.fetchFeed(), 15 * 60 * 1000);\n  },\n  beforeUnmount() {\n    clearTimeout(this._debounceTimer);\n    clearInterval(this._refreshInterval);\n  },\n  methods: {\n    t(text) {\n      return window.t ? window.t('intravox', text) : text;\n    },\n    async fetchFeed() {\n      this.loading = true;\n      this.error = null;\n\n      try {\n        let sourceType = this.widget.sourceType || 'rss';\n        // Auto-detect: if a connectionId is set but sourceType is 'rss', treat as connection\n        if (sourceType === 'rss' && this.widget.connectionId) {\n          sourceType = 'connection';\n        }\n        const params = new URLSearchParams({\n          sourceType,\n          limit: String(this.widget.limit || 5),\n        });\n\n        if (sourceType === 'rss') {\n          if (!this.widget.feedUrl) {\n            this.items = [];\n            this.loading = false;\n            return;\n          }\n          params.append('url', this.widget.feedUrl);\n        } else {\n          if (!this.widget.connectionId) {\n            this.items = [];\n            this.loading = false;\n            return;\n          }\n          params.append('connectionId', this.widget.connectionId);\n          if (this.widget.courseId) {\n            params.append('courseId', this.widget.courseId);\n          }\n          if (this.widget.contentType) {\n            params.append('contentType', this.widget.contentType);\n          }\n          if (this.widget.jiraProject) {\n            params.append('jiraProject', this.widget.jiraProject);\n          }\n          if (this.widget.moodleForumId) {\n            params.append('moodleForumId', this.widget.moodleForumId);\n          }\n          if (this.widget.listId) {\n            params.append('listId', this.widget.listId);\n          }\n        }\n\n        // Sort and filter\n        if (this.widget.sortBy) {\n          params.append('sortBy', this.widget.sortBy);\n        }\n        if (this.widget.sortOrder) {\n          params.append('sortOrder', this.widget.sortOrder);\n        }\n        if (this.widget.filterKeyword) {\n          params.append('filterKeyword', this.widget.filterKeyword);\n        }\n\n        const url = this.shareToken\n          ? generateUrl(`/apps/intravox/api/share/${this.shareToken}/feed/external?${params}`)\n          : generateUrl(`/apps/intravox/api/feed/external?${params}`);\n\n        const response = await axios.get(url);\n\n        if (response.data.error) {\n          const err = response.data.error;\n          if (err.includes('inactive') || err.includes('disabled')) {\n            this.error = this.t('This connection is currently disabled by an administrator.');\n          } else if (err.includes('not found') || err.includes('404')) {\n            this.error = this.t('Connection no longer exists. Please reconfigure this widget.');\n          } else if (err.includes('token') || err.includes('401') || err.includes('Authentication')) {\n            this.error = this.t('Authentication required. Please connect your account.');\n          } else if (err.includes('403') || err.includes('Access denied')) {\n            this.error = this.t('Access denied. Check the connection permissions.');\n          } else if (err.includes('429') || err.includes('Rate limited')) {\n            this.error = this.t('Too many requests. Please try again later.');\n          } else {\n            this.error = this.t('Could not load feed. Check the connection settings.');\n          }\n          this.items = [];\n          this.feedImage = null;\n        } else {\n          this.items = response.data.items || [];\n          this.feedImage = response.data.feedImage || null;\n        }\n      } catch (err) {\n        this.error = this.t('Could not load feed. The external system may be unavailable.');\n        this.items = [];\n        this.feedImage = null;\n      } finally {\n        this.loading = false;\n      }\n    },\n  },\n};\n</script>\n\n<style scoped>\n.feed-widget {\n  width: 100%;\n  min-width: 0;\n  overflow: hidden;\n}\n\n.feed-widget-loading,\n.feed-widget-error,\n.feed-widget-empty {\n  display: flex;\n  flex-direction: column;\n  align-items: center;\n  justify-content: center;\n  padding: 32px;\n  gap: 8px;\n  color: var(--color-text-maxcontrast);\n  text-align: center;\n}\n\n.feed-widget-error {\n  color: var(--color-main-text);\n  background: var(--color-error);\n  background: color-mix(in srgb, var(--color-error) 10%, transparent);\n  border: 1px solid color-mix(in srgb, var(--color-error) 30%, transparent);\n  border-radius: var(--border-radius-large);\n  padding: 20px 24px;\n  margin: 8px;\n}\n\n.feed-widget-error p {\n  margin: 0;\n  font-size: 14px;\n  color: var(--color-main-text);\n}\n</style>\n"],"sourceRoot":""}]);
// Exports
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (___CSS_LOADER_EXPORT___);


/***/ },

/***/ "./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/dist/stylePostLoader.js!./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/InlineTextEditor.vue?vue&type=style&index=0&id=0a2d6723&scoped=true&lang=css"
/*!*****************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/dist/stylePostLoader.js!./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/InlineTextEditor.vue?vue&type=style&index=0&id=0a2d6723&scoped=true&lang=css ***!
  \*****************************************************************************************************************************************************************************************************************************************************************/
(module, __webpack_exports__, __webpack_require__) {

"use strict";
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
.inline-text-editor[data-v-0a2d6723] {
  position: relative;
  width: 100%;
  display: block;
  background: transparent;
}

/* Floating Toolbar */
.text-menubar[data-v-0a2d6723] {
  position: sticky;
  top: 0;
  z-index: 1000;
  display: flex;
  align-items: center;
  flex-wrap: wrap;
  gap: 4px;
  padding: 8px;
  background: var(--color-main-background);
  border: 1px solid var(--color-border);
  border-radius: var(--border-radius);
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
  margin-bottom: 8px;
  max-width: 100%;
  box-sizing: border-box;
}
.menubar-button[data-v-0a2d6723] {
  padding: 6px 10px;
  background: transparent;
  border: 1px solid transparent;
  border-radius: var(--border-radius);
  cursor: pointer;
  font-size: 14px;
  color: var(--color-main-text);
  transition: all 0.2s ease;
  display: flex;
  align-items: center;
  justify-content: center;
  min-width: 32px;
  height: 32px;
}
.menubar-button[data-v-0a2d6723]:hover {
  background: var(--color-background-hover);
  border-color: var(--color-border);
}
.menubar-button.is-active[data-v-0a2d6723] {
  background: var(--color-primary-element);
  border-color: var(--color-primary-element);
  color: var(--color-primary-element-text);
}

/* Dropdown Containers */
.heading-dropdown[data-v-0a2d6723],
.table-dropdown[data-v-0a2d6723],
.alignment-dropdown[data-v-0a2d6723] {
  position: relative;
}
.heading-button[data-v-0a2d6723],
.alignment-button[data-v-0a2d6723] {
  min-width: 90px !important;
  gap: 4px;
}
.alignment-button[data-v-0a2d6723] {
  min-width: 60px !important;
}

/* Dropdown Menu */
.dropdown-menu[data-v-0a2d6723] {
  position: absolute;
  top: 100%;
  left: 0;
  margin-top: 4px;
  background: var(--color-main-background);
  border: 1px solid var(--color-border);
  border-radius: var(--border-radius);
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
  z-index: 1001;
  min-width: 140px;
  max-width: min(280px, calc(100vw - 32px));
  padding: 4px 0;
}
.table-menu[data-v-0a2d6723] {
  min-width: 180px;
}

/* Compact Toolbar Mode */
.text-menubar--compact[data-v-0a2d6723] {
  padding: 4px;
  gap: 2px;
}
.text-menubar--compact .menubar-button[data-v-0a2d6723] {
  padding: 4px 6px;
  min-width: 28px;
  height: 28px;
}

/* More dropdown for compact mode */
.more-dropdown[data-v-0a2d6723] {
  position: relative;
}
.more-menu[data-v-0a2d6723] {
  position: fixed;
  min-width: 180px;
  max-width: min(280px, calc(100vw - 32px));
  max-height: calc(100vh - 100px);
  overflow-y: auto;
  z-index: 1002;
}
.dropdown-menu-item[data-v-0a2d6723] {
  display: flex;
  align-items: center;
  gap: 8px;
  width: 100%;
  padding: 8px 12px;
  background: transparent;
  border: none;
  text-align: left;
  cursor: pointer;
  font-size: 14px;
  color: var(--color-main-text);
  transition: background-color 0.15s ease;
}
.dropdown-menu-item[data-v-0a2d6723]:hover {
  background: var(--color-background-hover);
}
.dropdown-menu-item.is-active[data-v-0a2d6723] {
  background: var(--color-primary-element);
  color: var(--color-primary-element-text);
  font-weight: 600;
}
.dropdown-menu-item--danger[data-v-0a2d6723] {
  color: #c9302c;
  font-weight: 500;
}
.dropdown-menu-item--danger[data-v-0a2d6723]:hover {
  background: #c9302c;
  color: white;
}
.dropdown-divider[data-v-0a2d6723] {
  height: 1px;
  background: var(--color-border);
  margin: 4px 0;
}

/* Section label inside a dropdown — small, muted heading above a row of pills */
.dropdown-menu-section-label[data-v-0a2d6723] {
  font-size: 11px;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.04em;
  color: var(--color-text-maxcontrast, #6b7280);
  padding: 6px 12px 2px;
}

/* Horizontal row of compact buttons inside a dropdown (width presets, align) */
.dropdown-menu-row[data-v-0a2d6723] {
  display: flex;
  gap: 4px;
  padding: 2px 8px 6px;
  flex-wrap: wrap;
}
.dropdown-menu-pill[data-v-0a2d6723] {
  flex: 1 1 auto;
  min-width: 36px;
  padding: 4px 6px;
  border: 1px solid var(--color-border, #ccc);
  background: var(--color-main-background, #fff);
  color: inherit;
  border-radius: 4px;
  font-size: 12px;
  cursor: pointer;
  display: inline-flex;
  align-items: center;
  justify-content: center;
}
.dropdown-menu-pill[data-v-0a2d6723]:hover {
  background: var(--color-background-hover, #f3f4f6);
}
.dropdown-menu-pill.is-active[data-v-0a2d6723] {
  background: var(--color-primary-element, #2563eb);
  color: var(--color-primary-element-text, #fff);
  border-color: var(--color-primary-element, #2563eb);
}
.menubar-divider[data-v-0a2d6723] {
  width: 1px;
  height: 24px;
  background: var(--color-border);
  margin: 0 4px;
}

/* Link Modal */
.link-modal-content[data-v-0a2d6723] {
  padding: 20px;
  display: flex;
  flex-direction: column;
  gap: 16px;
}
.form-group[data-v-0a2d6723] {
  display: flex;
  flex-direction: column;
  gap: 8px;
}
.link-modal-content label[data-v-0a2d6723] {
  font-weight: 600;
  color: var(--color-main-text);
}
.link-input[data-v-0a2d6723] {
  width: 100%;
  padding: 8px 12px;
  border: 1px solid var(--color-border);
  border-radius: var(--border-radius);
  font-size: 14px;
  color: var(--color-main-text);
  background: var(--color-main-background);
}
.link-input[data-v-0a2d6723]:focus {
  outline: none;
  border-color: var(--color-primary-element);
}
.modal-buttons[data-v-0a2d6723] {
  display: flex;
  gap: 8px;
  justify-content: flex-end;
}

/* Editor Content */
.editor-content[data-v-0a2d6723] {
  width: 100%;
  display: block;
  background: transparent;
  overflow-x: auto;
}
.editor-content[data-v-0a2d6723] .ProseMirror {
  outline: none;
  min-height: 50px;
  padding: 12px;
  border-radius: var(--border-radius);
  transition: background-color 0.2s ease;
  width: 100%;
  box-sizing: border-box;
  color: inherit;
  background: transparent;
}
.is-focused .editor-content[data-v-0a2d6723] .ProseMirror {
  background: transparent;
}

/* Text Selection - uses CSS variables from parent Widget for contrast */
.editor-content[data-v-0a2d6723] .ProseMirror ::selection {
  background: var(--widget-selection-bg, var(--color-primary-element-light));
  color: var(--widget-selection-text, var(--color-main-text));
}
.editor-content[data-v-0a2d6723] .ProseMirror ::-moz-selection {
  background: var(--widget-selection-bg, var(--color-primary-element-light));
  color: var(--widget-selection-text, var(--color-main-text));
}

/* Placeholder */
.editor-content[data-v-0a2d6723] .ProseMirror p.is-editor-empty:first-child::before {
  content: attr(data-placeholder);
  float: left;
  color: var(--widget-placeholder-color, var(--color-text-maxcontrast));
  pointer-events: none;
  height: 0;
}

/* Typography */
.editor-content[data-v-0a2d6723] .ProseMirror p {
  margin: 0 0 0.5em 0;
  width: 100%;
  color: inherit !important;
}
.editor-content[data-v-0a2d6723] .ProseMirror p:last-child {
  margin-bottom: 0;
}
.editor-content[data-v-0a2d6723] .ProseMirror h1,
.editor-content[data-v-0a2d6723] .ProseMirror h2,
.editor-content[data-v-0a2d6723] .ProseMirror h3,
.editor-content[data-v-0a2d6723] .ProseMirror h4,
.editor-content[data-v-0a2d6723] .ProseMirror h5,
.editor-content[data-v-0a2d6723] .ProseMirror h6 {
  margin: 0.5em 0;
  font-weight: 600;
  color: inherit !important;
}
.editor-content[data-v-0a2d6723] .ProseMirror h1 { font-size: 32px;
}
.editor-content[data-v-0a2d6723] .ProseMirror h2 { font-size: 28px;
}
.editor-content[data-v-0a2d6723] .ProseMirror h3 { font-size: 24px;
}
.editor-content[data-v-0a2d6723] .ProseMirror h4 { font-size: 20px;
}
.editor-content[data-v-0a2d6723] .ProseMirror h5 { font-size: 18px;
}
.editor-content[data-v-0a2d6723] .ProseMirror h6 { font-size: 16px;
}

/* Text alignment */
.editor-content[data-v-0a2d6723] .ProseMirror .text-align-center { text-align: center;
}
.editor-content[data-v-0a2d6723] .ProseMirror .text-align-right { text-align: right;
}
.editor-content[data-v-0a2d6723] .ProseMirror ul,
.editor-content[data-v-0a2d6723] .ProseMirror ol {
  padding-left: 1.5em;
  margin: 0.5em 0;
  width: 100%;
  list-style-position: outside;
  color: inherit !important;
}
.editor-content[data-v-0a2d6723] .ProseMirror ul {
  list-style-type: disc;
}
.editor-content[data-v-0a2d6723] .ProseMirror ol {
  list-style-type: decimal;
}
.editor-content[data-v-0a2d6723] .ProseMirror li {
  margin: 0.25em 0;
  display: list-item;
  color: inherit !important;
}

/* Nested lists - indentation en list-style hierarchy */
.editor-content[data-v-0a2d6723] .ProseMirror li > ul,
.editor-content[data-v-0a2d6723] .ProseMirror li > ol {
  margin: 0.25em 0;
  padding-left: 1.5em;
}

/* Ordered list nesting: 1. → a. → i. → 1. */
.editor-content[data-v-0a2d6723] .ProseMirror ol ol {
  list-style-type: lower-alpha;
}
.editor-content[data-v-0a2d6723] .ProseMirror ol ol ol {
  list-style-type: lower-roman;
}
.editor-content[data-v-0a2d6723] .ProseMirror ol ol ol ol {
  list-style-type: decimal;
}

/* Unordered list nesting: • → ○ → ▪ → • */
.editor-content[data-v-0a2d6723] .ProseMirror ul ul {
  list-style-type: circle;
}
.editor-content[data-v-0a2d6723] .ProseMirror ul ul ul {
  list-style-type: square;
}
.editor-content[data-v-0a2d6723] .ProseMirror ul ul ul ul {
  list-style-type: disc;
}
.editor-content[data-v-0a2d6723] .ProseMirror strong {
  font-weight: bold;
  color: inherit !important;
}
.editor-content[data-v-0a2d6723] .ProseMirror em {
  font-style: italic;
  color: inherit !important;
}
.editor-content[data-v-0a2d6723] .ProseMirror u {
  text-decoration: underline;
  color: inherit !important;
}
.editor-content[data-v-0a2d6723] .ProseMirror s {
  text-decoration: line-through;
  color: inherit !important;
}

/* Links - uses CSS variables from parent Widget for contrast */
.editor-content[data-v-0a2d6723] .ProseMirror a {
  color: var(--widget-link-color, var(--color-primary-element));
  text-decoration: underline;
  cursor: pointer;
}
.editor-content[data-v-0a2d6723] .ProseMirror a:hover {
  color: var(--widget-link-hover-color, var(--color-primary-element-hover));
  text-decoration: none;
}

/* Blockquote */
.editor-content[data-v-0a2d6723] .ProseMirror blockquote {
  border-left: 4px solid var(--color-primary-element);
  padding-left: 1em;
  margin: 1em 0;
  color: inherit !important;
  font-style: italic;
}

/* Code */
.editor-content[data-v-0a2d6723] .ProseMirror code {
  background: var(--color-background-dark);
  padding: 2px 6px;
  border-radius: var(--border-radius);
  font-family: 'Courier New', Courier, monospace;
  font-size: 0.9em;
  color: inherit !important;
}

/* Code Block */
.editor-content[data-v-0a2d6723] .ProseMirror pre {
  background: var(--color-background-dark);
  padding: 12px;
  border-radius: var(--border-radius-large);
  overflow-x: auto;
  margin: 1em 0;
}
.editor-content[data-v-0a2d6723] .ProseMirror pre code {
  background: transparent;
  padding: 0;
  font-family: 'Courier New', Courier, monospace;
  color: inherit !important;
}

/* Tables wider than the page column scroll horizontally inside this wrapper
   instead of pushing the page sideways (TipTap injects the wrapper). */
.editor-content[data-v-0a2d6723] .ProseMirror .tableWrapper {
  overflow-x: auto;
  max-width: 100%;
  margin: 1em 0;
}
.editor-content[data-v-0a2d6723] .ProseMirror table {
  border-collapse: collapse;
  table-layout: fixed;
  min-width: 100%;
  margin: 1em 0;
}
.editor-content[data-v-0a2d6723] .ProseMirror .tableWrapper > table {
  margin: 0;
}

/* When the user sets an explicit width via the Table extension, drop the
   min-width: 100% so the table can shrink to that width. */
.editor-content[data-v-0a2d6723] .ProseMirror table[style*="width:"] {
  min-width: 0;
}

/* Right-edge resize handle for the active table. The TableResizeHandle plugin
   adds the data attribute via a node decoration; this CSS gives the handle a
   visible affordance so editors notice they can drag-resize the table. */
.editor-content[data-v-0a2d6723] .ProseMirror table[data-table-resize-active] {
  position: relative;
}
.editor-content[data-v-0a2d6723] .ProseMirror table[data-table-resize-active]::after {
  content: '';
  position: absolute;
  top: 0;
  right: -4px;
  bottom: 0;
  width: 8px;
  background: linear-gradient(to right, transparent 0, var(--color-primary-element, #2563eb) 50%, transparent 100%);
  opacity: 0.35;
  pointer-events: none;
}
.editor-content[data-v-0a2d6723] .ProseMirror table[data-table-resize-active]:hover::after {
  opacity: 0.7;
}

/* Mirrors the read-mode wrap policy in Widget.vue: cells shrink to their
   column share, content wraps anywhere if needed, white-space: normal
   overrides a Nextcloud core nowrap rule. */
.editor-content[data-v-0a2d6723] .ProseMirror table td,
.editor-content[data-v-0a2d6723] .ProseMirror table th {
  border: 1px solid var(--color-border-dark, #bbb);
  padding: 8px 12px;
  vertical-align: top;
  box-sizing: border-box;
  position: relative;
  color: inherit !important;
  overflow-wrap: anywhere;
  min-width: 0;
  hyphens: auto;
  white-space: normal;
}
.editor-content[data-v-0a2d6723] .ProseMirror table td > *,
.editor-content[data-v-0a2d6723] .ProseMirror table th > * {
  overflow-wrap: anywhere;
  min-width: 0;
  max-width: 100%;
  white-space: normal;
}
.editor-content[data-v-0a2d6723] .ProseMirror table .selectedCell {
  background: var(--color-primary-element-light);
}

/* Column resize handle — subtle line on cell border */
.editor-content[data-v-0a2d6723] .ProseMirror table .column-resize-handle {
  position: absolute;
  right: -2px;
  top: 0;
  bottom: 0;
  width: 4px;
  background-color: var(--color-primary-element);
  pointer-events: none;
  z-index: 10;
}

/* Task List */
.editor-content[data-v-0a2d6723] .ProseMirror ul[data-type="taskList"] {
  list-style: none;
  padding-left: 0;
}
.editor-content[data-v-0a2d6723] .ProseMirror ul[data-type="taskList"] li {
  display: flex;
  align-items: flex-start;
  color: inherit !important;
}
.editor-content[data-v-0a2d6723] .ProseMirror ul[data-type="taskList"] li > label {
  flex: 0 0 auto;
  margin-right: 0.5em;
  user-select: none;
}
.editor-content[data-v-0a2d6723] .ProseMirror ul[data-type="taskList"] li > div {
  flex: 1 1 auto;
}
.editor-content[data-v-0a2d6723] .ProseMirror ul[data-type="taskList"] input[type="checkbox"] {
  cursor: pointer;
  width: 1.2em;
  height: 1.2em;
}

/* Horizontal Rule */
.editor-content[data-v-0a2d6723] .ProseMirror hr {
  border: none;
  border-top: 2px solid var(--color-border);
  margin: 2em 0;
}

/* Mobile styles */
@media (max-width: 480px) {
.text-menubar[data-v-0a2d6723] {
    padding: 6px;
    gap: 2px;
}
.menubar-button[data-v-0a2d6723] {
    padding: 4px 6px;
    min-width: 28px;
    height: 28px;
}
.menubar-divider[data-v-0a2d6723] {
    display: none; /* Hide dividers on mobile to save space */
}
.heading-button[data-v-0a2d6723] {
    min-width: 70px !important;
    font-size: 12px;
}
.dropdown-menu[data-v-0a2d6723] {
    min-width: 120px;
    max-width: calc(100vw - 32px);
}
.dropdown-menu-item[data-v-0a2d6723] {
    padding: 6px 10px;
    font-size: 13px;
}
}
@media (max-width: 360px) {
.text-menubar[data-v-0a2d6723] {
    padding: 4px;
}
.menubar-button[data-v-0a2d6723] {
    padding: 3px 5px;
    min-width: 26px;
    height: 26px;
}
.heading-button[data-v-0a2d6723] {
    min-width: 60px !important;
    font-size: 11px;
}
}
`, "",{"version":3,"sources":["webpack://./src/components/InlineTextEditor.vue"],"names":[],"mappings":";AAggCA;EACE,kBAAkB;EAClB,WAAW;EACX,cAAc;EACd,uBAAuB;AACzB;;AAEA,qBAAqB;AACrB;EACE,gBAAgB;EAChB,MAAM;EACN,aAAa;EACb,aAAa;EACb,mBAAmB;EACnB,eAAe;EACf,QAAQ;EACR,YAAY;EACZ,wCAAwC;EACxC,qCAAqC;EACrC,mCAAmC;EACnC,yCAAyC;EACzC,kBAAkB;EAClB,eAAe;EACf,sBAAsB;AACxB;AAEA;EACE,iBAAiB;EACjB,uBAAuB;EACvB,6BAA6B;EAC7B,mCAAmC;EACnC,eAAe;EACf,eAAe;EACf,6BAA6B;EAC7B,yBAAyB;EACzB,aAAa;EACb,mBAAmB;EACnB,uBAAuB;EACvB,eAAe;EACf,YAAY;AACd;AAEA;EACE,yCAAyC;EACzC,iCAAiC;AACnC;AAEA;EACE,wCAAwC;EACxC,0CAA0C;EAC1C,wCAAwC;AAC1C;;AAEA,wBAAwB;AACxB;;;EAGE,kBAAkB;AACpB;AAEA;;EAEE,0BAA0B;EAC1B,QAAQ;AACV;AAEA;EACE,0BAA0B;AAC5B;;AAEA,kBAAkB;AAClB;EACE,kBAAkB;EAClB,SAAS;EACT,OAAO;EACP,eAAe;EACf,wCAAwC;EACxC,qCAAqC;EACrC,mCAAmC;EACnC,0CAA0C;EAC1C,aAAa;EACb,gBAAgB;EAChB,yCAAyC;EACzC,cAAc;AAChB;AAEA;EACE,gBAAgB;AAClB;;AAEA,yBAAyB;AACzB;EACE,YAAY;EACZ,QAAQ;AACV;AAEA;EACE,gBAAgB;EAChB,eAAe;EACf,YAAY;AACd;;AAEA,mCAAmC;AACnC;EACE,kBAAkB;AACpB;AAEA;EACE,eAAe;EACf,gBAAgB;EAChB,yCAAyC;EACzC,+BAA+B;EAC/B,gBAAgB;EAChB,aAAa;AACf;AAEA;EACE,aAAa;EACb,mBAAmB;EACnB,QAAQ;EACR,WAAW;EACX,iBAAiB;EACjB,uBAAuB;EACvB,YAAY;EACZ,gBAAgB;EAChB,eAAe;EACf,eAAe;EACf,6BAA6B;EAC7B,uCAAuC;AACzC;AAEA;EACE,yCAAyC;AAC3C;AAEA;EACE,wCAAwC;EACxC,wCAAwC;EACxC,gBAAgB;AAClB;AAEA;EACE,cAAc;EACd,gBAAgB;AAClB;AAEA;EACE,mBAAmB;EACnB,YAAY;AACd;AAEA;EACE,WAAW;EACX,+BAA+B;EAC/B,aAAa;AACf;;AAEA,gFAAgF;AAChF;EACE,eAAe;EACf,gBAAgB;EAChB,yBAAyB;EACzB,sBAAsB;EACtB,6CAA6C;EAC7C,qBAAqB;AACvB;;AAEA,+EAA+E;AAC/E;EACE,aAAa;EACb,QAAQ;EACR,oBAAoB;EACpB,eAAe;AACjB;AAEA;EACE,cAAc;EACd,eAAe;EACf,gBAAgB;EAChB,2CAA2C;EAC3C,8CAA8C;EAC9C,cAAc;EACd,kBAAkB;EAClB,eAAe;EACf,eAAe;EACf,oBAAoB;EACpB,mBAAmB;EACnB,uBAAuB;AACzB;AAEA;EACE,kDAAkD;AACpD;AAEA;EACE,iDAAiD;EACjD,8CAA8C;EAC9C,mDAAmD;AACrD;AAEA;EACE,UAAU;EACV,YAAY;EACZ,+BAA+B;EAC/B,aAAa;AACf;;AAEA,eAAe;AACf;EACE,aAAa;EACb,aAAa;EACb,sBAAsB;EACtB,SAAS;AACX;AAEA;EACE,aAAa;EACb,sBAAsB;EACtB,QAAQ;AACV;AAEA;EACE,gBAAgB;EAChB,6BAA6B;AAC/B;AAEA;EACE,WAAW;EACX,iBAAiB;EACjB,qCAAqC;EACrC,mCAAmC;EACnC,eAAe;EACf,6BAA6B;EAC7B,wCAAwC;AAC1C;AAEA;EACE,aAAa;EACb,0CAA0C;AAC5C;AAEA;EACE,aAAa;EACb,QAAQ;EACR,yBAAyB;AAC3B;;AAEA,mBAAmB;AACnB;EACE,WAAW;EACX,cAAc;EACd,uBAAuB;EACvB,gBAAgB;AAClB;AAEA;EACE,aAAa;EACb,gBAAgB;EAChB,aAAa;EACb,mCAAmC;EACnC,sCAAsC;EACtC,WAAW;EACX,sBAAsB;EACtB,cAAc;EACd,uBAAuB;AACzB;AAEA;EACE,uBAAuB;AACzB;;AAEA,wEAAwE;AACxE;EACE,0EAA0E;EAC1E,2DAA2D;AAC7D;AAEA;EACE,0EAA0E;EAC1E,2DAA2D;AAC7D;;AAEA,gBAAgB;AAChB;EACE,+BAA+B;EAC/B,WAAW;EACX,qEAAqE;EACrE,oBAAoB;EACpB,SAAS;AACX;;AAEA,eAAe;AACf;EACE,mBAAmB;EACnB,WAAW;EACX,yBAAyB;AAC3B;AAEA;EACE,gBAAgB;AAClB;AAEA;;;;;;EAME,eAAe;EACf,gBAAgB;EAChB,yBAAyB;AAC3B;AAEA,mDAAyC,eAAe;AAAE;AAC1D,mDAAyC,eAAe;AAAE;AAC1D,mDAAyC,eAAe;AAAE;AAC1D,mDAAyC,eAAe;AAAE;AAC1D,mDAAyC,eAAe;AAAE;AAC1D,mDAAyC,eAAe;AAAE;;AAE1D,mBAAmB;AACnB,mEAAyD,kBAAkB;AAAE;AAC7E,kEAAwD,iBAAiB;AAAE;AAE3E;;EAEE,mBAAmB;EACnB,eAAe;EACf,WAAW;EACX,4BAA4B;EAC5B,yBAAyB;AAC3B;AAEA;EACE,qBAAqB;AACvB;AAEA;EACE,wBAAwB;AAC1B;AAEA;EACE,gBAAgB;EAChB,kBAAkB;EAClB,yBAAyB;AAC3B;;AAEA,uDAAuD;AACvD;;EAEE,gBAAgB;EAChB,mBAAmB;AACrB;;AAEA,4CAA4C;AAC5C;EACE,4BAA4B;AAC9B;AACA;EACE,4BAA4B;AAC9B;AACA;EACE,wBAAwB;AAC1B;;AAEA,0CAA0C;AAC1C;EACE,uBAAuB;AACzB;AACA;EACE,uBAAuB;AACzB;AACA;EACE,qBAAqB;AACvB;AAEA;EACE,iBAAiB;EACjB,yBAAyB;AAC3B;AAEA;EACE,kBAAkB;EAClB,yBAAyB;AAC3B;AAEA;EACE,0BAA0B;EAC1B,yBAAyB;AAC3B;AAEA;EACE,6BAA6B;EAC7B,yBAAyB;AAC3B;;AAEA,+DAA+D;AAC/D;EACE,6DAA6D;EAC7D,0BAA0B;EAC1B,eAAe;AACjB;AAEA;EACE,yEAAyE;EACzE,qBAAqB;AACvB;;AAEA,eAAe;AACf;EACE,mDAAmD;EACnD,iBAAiB;EACjB,aAAa;EACb,yBAAyB;EACzB,kBAAkB;AACpB;;AAEA,SAAS;AACT;EACE,wCAAwC;EACxC,gBAAgB;EAChB,mCAAmC;EACnC,8CAA8C;EAC9C,gBAAgB;EAChB,yBAAyB;AAC3B;;AAEA,eAAe;AACf;EACE,wCAAwC;EACxC,aAAa;EACb,yCAAyC;EACzC,gBAAgB;EAChB,aAAa;AACf;AAEA;EACE,uBAAuB;EACvB,UAAU;EACV,8CAA8C;EAC9C,yBAAyB;AAC3B;;AAEA;uEACuE;AACvE;EACE,gBAAgB;EAChB,eAAe;EACf,aAAa;AACf;AAEA;EACE,yBAAyB;EACzB,mBAAmB;EACnB,eAAe;EACf,aAAa;AACf;AAEA;EACE,SAAS;AACX;;AAEA;2DAC2D;AAC3D;EACE,YAAY;AACd;;AAEA;;yEAEyE;AACzE;EACE,kBAAkB;AACpB;AAEA;EACE,WAAW;EACX,kBAAkB;EAClB,MAAM;EACN,WAAW;EACX,SAAS;EACT,UAAU;EACV,iHAAiH;EACjH,aAAa;EACb,oBAAoB;AACtB;AAEA;EACE,YAAY;AACd;;AAEA;;4CAE4C;AAC5C;;EAEE,gDAAgD;EAChD,iBAAiB;EACjB,mBAAmB;EACnB,sBAAsB;EACtB,kBAAkB;EAClB,yBAAyB;EACzB,uBAAuB;EACvB,YAAY;EACZ,aAAa;EACb,mBAAmB;AACrB;AAEA;;EAEE,uBAAuB;EACvB,YAAY;EACZ,eAAe;EACf,mBAAmB;AACrB;AAEA;EACE,8CAA8C;AAChD;;AAEA,sDAAsD;AACtD;EACE,kBAAkB;EAClB,WAAW;EACX,MAAM;EACN,SAAS;EACT,UAAU;EACV,8CAA8C;EAC9C,oBAAoB;EACpB,WAAW;AACb;;AAEA,cAAc;AACd;EACE,gBAAgB;EAChB,eAAe;AACjB;AAEA;EACE,aAAa;EACb,uBAAuB;EACvB,yBAAyB;AAC3B;AAEA;EACE,cAAc;EACd,mBAAmB;EACnB,iBAAiB;AACnB;AAEA;EACE,cAAc;AAChB;AAEA;EACE,eAAe;EACf,YAAY;EACZ,aAAa;AACf;;AAEA,oBAAoB;AACpB;EACE,YAAY;EACZ,yCAAyC;EACzC,aAAa;AACf;;AAEA,kBAAkB;AAClB;AACE;IACE,YAAY;IACZ,QAAQ;AACV;AAEA;IACE,gBAAgB;IAChB,eAAe;IACf,YAAY;AACd;AAEA;IACE,aAAa,EAAE,0CAA0C;AAC3D;AAEA;IACE,0BAA0B;IAC1B,eAAe;AACjB;AAEA;IACE,gBAAgB;IAChB,6BAA6B;AAC/B;AAEA;IACE,iBAAiB;IACjB,eAAe;AACjB;AACF;AAEA;AACE;IACE,YAAY;AACd;AAEA;IACE,gBAAgB;IAChB,eAAe;IACf,YAAY;AACd;AAEA;IACE,0BAA0B;IAC1B,eAAe;AACjB;AACF","sourcesContent":["<template>\r\n  <div class=\"inline-text-editor\" :class=\"{ 'is-focused': isFocused }\">\r\n    <!-- Floating Toolbar -->\r\n    <div v-if=\"editor && isFocused && showToolbar\" class=\"text-menubar\" :class=\"{ 'text-menubar--compact': compact }\">\r\n      <!-- Text Formatting - Always visible -->\r\n      <button\r\n        type=\"button\"\r\n        @mousedown.prevent=\"editor.chain().focus().toggleBold().run()\"\r\n        :class=\"{ 'is-active': editor.isActive('bold') }\"\r\n        :title=\"t('Bold (Ctrl+B)')\"\r\n        :aria-label=\"t('Bold (Ctrl+B)')\"\r\n        class=\"menubar-button\"\r\n      >\r\n        <FormatBold :size=\"18\" />\r\n      </button>\r\n      <button\r\n        type=\"button\"\r\n        @mousedown.prevent=\"editor.chain().focus().toggleItalic().run()\"\r\n        :class=\"{ 'is-active': editor.isActive('italic') }\"\r\n        :title=\"t('Italic (Ctrl+I)')\"\r\n        :aria-label=\"t('Italic (Ctrl+I)')\"\r\n        class=\"menubar-button\"\r\n      >\r\n        <FormatItalic :size=\"18\" />\r\n      </button>\r\n      <button\r\n        type=\"button\"\r\n        @mousedown.prevent=\"editor.chain().focus().toggleUnderline().run()\"\r\n        :class=\"{ 'is-active': editor.isActive('underline') }\"\r\n        :title=\"t('Underline (Ctrl+U)')\"\r\n        :aria-label=\"t('Underline (Ctrl+U)')\"\r\n        class=\"menubar-button\"\r\n      >\r\n        <FormatUnderline :size=\"18\" />\r\n      </button>\r\n\r\n      <!-- COMPACT MODE: \"More\" dropdown with all other options -->\r\n      <div v-if=\"compact\" class=\"more-dropdown\">\r\n        <button\r\n          ref=\"moreButton\"\r\n          type=\"button\"\r\n          @mousedown.prevent=\"toggleMoreMenu\"\r\n          :title=\"t('More options')\"\r\n          :aria-label=\"t('More options')\"\r\n          class=\"menubar-button\"\r\n        >\r\n          <DotsHorizontal :size=\"18\" />\r\n        </button>\r\n        <div v-if=\"showMoreMenu\" class=\"dropdown-menu more-menu\">\r\n          <!-- Strikethrough -->\r\n          <button\r\n            type=\"button\"\r\n            @mousedown.prevent=\"editor.chain().focus().toggleStrike().run(); showMoreMenu = false\"\r\n            :class=\"{ 'is-active': editor.isActive('strike') }\"\r\n            class=\"dropdown-menu-item\"\r\n          >\r\n            <FormatStrikethrough :size=\"16\" />\r\n            {{ t('Strikethrough') }}\r\n          </button>\r\n          <div class=\"dropdown-divider\"></div>\r\n          <!-- Headings -->\r\n          <button\r\n            v-for=\"level in [0, 1, 2, 3, 4]\"\r\n            :key=\"level\"\r\n            type=\"button\"\r\n            @mousedown.prevent=\"setHeadingLevel(level); showMoreMenu = false\"\r\n            :class=\"{ 'is-active': isHeadingLevel(level) }\"\r\n            class=\"dropdown-menu-item\"\r\n          >\r\n            {{ getHeadingLabel(level) }}\r\n          </button>\r\n          <div class=\"dropdown-divider\"></div>\r\n          <!-- Lists & Blockquote -->\r\n          <button\r\n            type=\"button\"\r\n            @mousedown.prevent=\"editor.chain().focus().toggleBulletList().run(); showMoreMenu = false\"\r\n            :class=\"{ 'is-active': editor.isActive('bulletList') }\"\r\n            class=\"dropdown-menu-item\"\r\n          >\r\n            <FormatListBulleted :size=\"16\" />\r\n            {{ t('Bullet list') }}\r\n          </button>\r\n          <button\r\n            type=\"button\"\r\n            @mousedown.prevent=\"editor.chain().focus().toggleOrderedList().run(); showMoreMenu = false\"\r\n            :class=\"{ 'is-active': editor.isActive('orderedList') }\"\r\n            class=\"dropdown-menu-item\"\r\n          >\r\n            <FormatListNumbered :size=\"16\" />\r\n            {{ t('Numbered list') }}\r\n          </button>\r\n          <button\r\n            type=\"button\"\r\n            @mousedown.prevent=\"editor.chain().focus().toggleBlockquote().run(); showMoreMenu = false\"\r\n            :class=\"{ 'is-active': editor.isActive('blockquote') }\"\r\n            class=\"dropdown-menu-item\"\r\n          >\r\n            <FormatQuoteClose :size=\"16\" />\r\n            {{ t('Blockquote') }}\r\n          </button>\r\n          <div class=\"dropdown-divider\"></div>\r\n          <!-- Text Alignment -->\r\n          <button\r\n            type=\"button\"\r\n            @mousedown.prevent=\"setAlignment('left'); showMoreMenu = false\"\r\n            :class=\"{ 'is-active': isAlignmentActive('left') }\"\r\n            class=\"dropdown-menu-item\"\r\n          >\r\n            <FormatAlignLeft :size=\"16\" />\r\n            {{ t('Align left') }}\r\n          </button>\r\n          <button\r\n            type=\"button\"\r\n            @mousedown.prevent=\"setAlignment('center'); showMoreMenu = false\"\r\n            :class=\"{ 'is-active': isAlignmentActive('center') }\"\r\n            class=\"dropdown-menu-item\"\r\n          >\r\n            <FormatAlignCenter :size=\"16\" />\r\n            {{ t('Align center') }}\r\n          </button>\r\n          <button\r\n            type=\"button\"\r\n            @mousedown.prevent=\"setAlignment('right'); showMoreMenu = false\"\r\n            :class=\"{ 'is-active': isAlignmentActive('right') }\"\r\n            class=\"dropdown-menu-item\"\r\n          >\r\n            <FormatAlignRight :size=\"16\" />\r\n            {{ t('Align right') }}\r\n          </button>\r\n          <div class=\"dropdown-divider\"></div>\r\n          <!-- Link -->\r\n          <button\r\n            type=\"button\"\r\n            @mousedown.prevent=\"showLinkModalHandler(); showMoreMenu = false\"\r\n            :class=\"{ 'is-active': editor.isActive('link') }\"\r\n            class=\"dropdown-menu-item\"\r\n          >\r\n            <LinkVariant :size=\"16\" />\r\n            {{ t('Link') }}\r\n          </button>\r\n          <!-- Table -->\r\n          <button\r\n            type=\"button\"\r\n            @mousedown.prevent=\"insertTable(); showMoreMenu = false\"\r\n            class=\"dropdown-menu-item\"\r\n          >\r\n            <TableIcon :size=\"16\" />\r\n            {{ t('Insert table') }}\r\n          </button>\r\n          <!-- Table editing options when in table -->\r\n          <template v-if=\"editor.isActive('table')\">\r\n            <div class=\"dropdown-divider\"></div>\r\n            <button\r\n              type=\"button\"\r\n              @mousedown.prevent=\"addRowAfter(); showMoreMenu = false\"\r\n              class=\"dropdown-menu-item\"\r\n            >\r\n              <TableRowPlusAfter :size=\"16\" />\r\n              {{ t('Add row') }}\r\n            </button>\r\n            <button\r\n              type=\"button\"\r\n              @mousedown.prevent=\"addColumnAfter(); showMoreMenu = false\"\r\n              class=\"dropdown-menu-item\"\r\n            >\r\n              <TableColumnPlusAfter :size=\"16\" />\r\n              {{ t('Add column') }}\r\n            </button>\r\n            <button\r\n              type=\"button\"\r\n              @mousedown.prevent=\"deleteTable(); showMoreMenu = false\"\r\n              class=\"dropdown-menu-item dropdown-menu-item--danger\"\r\n            >\r\n              <TableRemove :size=\"16\" />\r\n              {{ t('Delete table') }}\r\n            </button>\r\n          </template>\r\n        </div>\r\n      </div>\r\n\r\n      <!-- FULL MODE: All buttons visible -->\r\n      <!-- Group 1: Inline formatting | Group 2: Block structure | Group 3: Alignment | Group 4: Insert -->\r\n      <template v-else>\r\n        <button\r\n          type=\"button\"\r\n          @mousedown.prevent=\"editor.chain().focus().toggleStrike().run()\"\r\n          :class=\"{ 'is-active': editor.isActive('strike') }\"\r\n          :title=\"t('Strikethrough')\"\r\n          :aria-label=\"t('Strikethrough')\"\r\n          class=\"menubar-button\"\r\n        >\r\n          <FormatStrikethrough :size=\"18\" />\r\n        </button>\r\n\r\n        <span class=\"menubar-divider\"></span>\r\n\r\n        <!-- Group 2: Block structure -->\r\n        <!-- Heading Dropdown -->\r\n        <div class=\"heading-dropdown\">\r\n          <button\r\n            type=\"button\"\r\n            @mousedown.prevent=\"toggleHeadingMenu\"\r\n            class=\"menubar-button heading-button\"\r\n            :title=\"t('Heading')\"\r\n            :aria-label=\"t('Heading')\"\r\n          >\r\n            {{ getCurrentHeadingLabel() }}\r\n            <ChevronDown :size=\"14\" />\r\n          </button>\r\n          <div v-if=\"showHeadingMenu\" class=\"dropdown-menu\">\r\n            <button\r\n              v-for=\"level in [0, 1, 2, 3, 4]\"\r\n              :key=\"level\"\r\n              type=\"button\"\r\n              @mousedown.prevent=\"setHeadingLevel(level)\"\r\n              :class=\"{ 'is-active': isHeadingLevel(level) }\"\r\n              class=\"dropdown-menu-item\"\r\n            >\r\n              {{ getHeadingLabel(level) }}\r\n            </button>\r\n          </div>\r\n        </div>\r\n\r\n        <!-- Lists -->\r\n        <button\r\n          type=\"button\"\r\n          @mousedown.prevent=\"editor.chain().focus().toggleBulletList().run()\"\r\n          :class=\"{ 'is-active': editor.isActive('bulletList') }\"\r\n          :title=\"t('Bullet list')\"\r\n          :aria-label=\"t('Bullet list')\"\r\n          class=\"menubar-button\"\r\n        >\r\n          <FormatListBulleted :size=\"18\" />\r\n        </button>\r\n        <button\r\n          type=\"button\"\r\n          @mousedown.prevent=\"editor.chain().focus().toggleOrderedList().run()\"\r\n          :class=\"{ 'is-active': editor.isActive('orderedList') }\"\r\n          :title=\"t('Numbered list')\"\r\n          :aria-label=\"t('Numbered list')\"\r\n          class=\"menubar-button\"\r\n        >\r\n          <FormatListNumbered :size=\"18\" />\r\n        </button>\r\n\r\n        <!-- Blockquote -->\r\n        <button\r\n          type=\"button\"\r\n          @mousedown.prevent=\"editor.chain().focus().toggleBlockquote().run()\"\r\n          :class=\"{ 'is-active': editor.isActive('blockquote') }\"\r\n          :title=\"t('Blockquote')\"\r\n          :aria-label=\"t('Blockquote')\"\r\n          class=\"menubar-button\"\r\n        >\r\n          <FormatQuoteClose :size=\"18\" />\r\n        </button>\r\n\r\n        <span class=\"menubar-divider\"></span>\r\n\r\n        <!-- Group 3: Alignment Dropdown -->\r\n        <div class=\"alignment-dropdown\">\r\n          <button\r\n            type=\"button\"\r\n            @mousedown.prevent=\"toggleAlignmentMenu\"\r\n            class=\"menubar-button alignment-button\"\r\n            :title=\"t('Text alignment')\"\r\n            :aria-label=\"t('Text alignment')\"\r\n          >\r\n            <component :is=\"currentAlignmentIcon\" :size=\"18\" />\r\n            <ChevronDown :size=\"14\" />\r\n          </button>\r\n          <div v-if=\"showAlignmentMenu\" class=\"dropdown-menu\">\r\n            <button\r\n              v-for=\"align in ['left', 'center', 'right']\"\r\n              :key=\"align\"\r\n              type=\"button\"\r\n              @mousedown.prevent=\"setAlignment(align)\"\r\n              :class=\"{ 'is-active': isAlignmentActive(align) }\"\r\n              class=\"dropdown-menu-item\"\r\n            >\r\n              <component :is=\"alignmentIcons[align]\" :size=\"16\" />\r\n              {{ getAlignmentLabel(align) }}\r\n            </button>\r\n          </div>\r\n        </div>\r\n\r\n        <span class=\"menubar-divider\"></span>\r\n\r\n        <!-- Group 4: Insert actions -->\r\n        <!-- Link -->\r\n        <button\r\n          type=\"button\"\r\n          @mousedown.prevent=\"showLinkModalHandler\"\r\n          :class=\"{ 'is-active': editor.isActive('link') }\"\r\n          :title=\"t('Insert link')\"\r\n          :aria-label=\"t('Insert link')\"\r\n          class=\"menubar-button\"\r\n        >\r\n          <LinkVariant :size=\"18\" />\r\n        </button>\r\n\r\n        <!-- Table Dropdown -->\r\n        <div class=\"table-dropdown\">\r\n          <button\r\n            type=\"button\"\r\n            @mousedown.prevent=\"toggleTableMenu\"\r\n            :class=\"{ 'is-active': editor.isActive('table') }\"\r\n            :title=\"t('Table')\"\r\n            :aria-label=\"t('Table')\"\r\n            class=\"menubar-button\"\r\n          >\r\n            <TableIcon :size=\"18\" />\r\n            <ChevronDown :size=\"14\" />\r\n          </button>\r\n          <div v-if=\"showTableMenu\" class=\"dropdown-menu table-menu\">\r\n            <button\r\n              type=\"button\"\r\n              @mousedown.prevent=\"insertTable\"\r\n              class=\"dropdown-menu-item\"\r\n            >\r\n              <TableIcon :size=\"16\" />\r\n              {{ t('Insert table') }}\r\n            </button>\r\n            <template v-if=\"editor.isActive('table')\">\r\n              <div class=\"dropdown-divider\"></div>\r\n              <button\r\n                type=\"button\"\r\n                @mousedown.prevent=\"addRowBefore\"\r\n                class=\"dropdown-menu-item\"\r\n              >\r\n                <TableRowPlusBefore :size=\"16\" />\r\n                {{ t('Add row above') }}\r\n              </button>\r\n              <button\r\n                type=\"button\"\r\n                @mousedown.prevent=\"addRowAfter\"\r\n                class=\"dropdown-menu-item\"\r\n              >\r\n                <TableRowPlusAfter :size=\"16\" />\r\n                {{ t('Add row below') }}\r\n              </button>\r\n              <button\r\n                type=\"button\"\r\n                @mousedown.prevent=\"addColumnBefore\"\r\n                class=\"dropdown-menu-item\"\r\n              >\r\n                <TableColumnPlusBefore :size=\"16\" />\r\n                {{ t('Add column left') }}\r\n              </button>\r\n              <button\r\n                type=\"button\"\r\n                @mousedown.prevent=\"addColumnAfter\"\r\n                class=\"dropdown-menu-item\"\r\n              >\r\n                <TableColumnPlusAfter :size=\"16\" />\r\n                {{ t('Add column right') }}\r\n              </button>\r\n              <div class=\"dropdown-divider\"></div>\r\n              <div class=\"dropdown-menu-section-label\">{{ t('Width') }}</div>\r\n              <div class=\"dropdown-menu-row\">\r\n                <button\r\n                  v-for=\"w in tableWidthPresets\"\r\n                  :key=\"w\"\r\n                  type=\"button\"\r\n                  @mousedown.prevent=\"setTableWidth(w)\"\r\n                  :class=\"{ 'is-active': isTableWidth(w) }\"\r\n                  class=\"dropdown-menu-pill\"\r\n                >\r\n                  {{ w === null ? t('Auto') : w }}\r\n                </button>\r\n              </div>\r\n              <div class=\"dropdown-menu-section-label\">{{ t('Alignment') }}</div>\r\n              <div class=\"dropdown-menu-row\">\r\n                <button\r\n                  type=\"button\"\r\n                  @mousedown.prevent=\"setTableAlign('left')\"\r\n                  :class=\"{ 'is-active': isTableAlign('left') }\"\r\n                  :title=\"t('Align left')\"\r\n                  class=\"dropdown-menu-pill\"\r\n                >\r\n                  <FormatAlignLeft :size=\"16\" />\r\n                </button>\r\n                <button\r\n                  type=\"button\"\r\n                  @mousedown.prevent=\"setTableAlign('center')\"\r\n                  :class=\"{ 'is-active': isTableAlign('center') }\"\r\n                  :title=\"t('Align center')\"\r\n                  class=\"dropdown-menu-pill\"\r\n                >\r\n                  <FormatAlignCenter :size=\"16\" />\r\n                </button>\r\n                <button\r\n                  type=\"button\"\r\n                  @mousedown.prevent=\"setTableAlign('right')\"\r\n                  :class=\"{ 'is-active': isTableAlign('right') }\"\r\n                  :title=\"t('Align right')\"\r\n                  class=\"dropdown-menu-pill\"\r\n                >\r\n                  <FormatAlignRight :size=\"16\" />\r\n                </button>\r\n              </div>\r\n              <div class=\"dropdown-divider\"></div>\r\n              <button\r\n                type=\"button\"\r\n                @mousedown.prevent=\"deleteRow\"\r\n                class=\"dropdown-menu-item dropdown-menu-item--danger\"\r\n              >\r\n                <TableRowRemove :size=\"16\" />\r\n                {{ t('Delete row') }}\r\n              </button>\r\n              <button\r\n                type=\"button\"\r\n                @mousedown.prevent=\"deleteColumn\"\r\n                class=\"dropdown-menu-item dropdown-menu-item--danger\"\r\n              >\r\n                <TableColumnRemove :size=\"16\" />\r\n                {{ t('Delete column') }}\r\n              </button>\r\n              <button\r\n                type=\"button\"\r\n                @mousedown.prevent=\"deleteTable\"\r\n                class=\"dropdown-menu-item dropdown-menu-item--danger\"\r\n              >\r\n                <TableRemove :size=\"16\" />\r\n                {{ t('Delete table') }}\r\n              </button>\r\n            </template>\r\n          </div>\r\n        </div>\r\n      </template>\r\n    </div>\r\n\r\n    <!-- Editor Content -->\r\n    <component :is=\"editorContentComponent\" v-if=\"editorContentComponent && editor\" :editor=\"editor\" class=\"editor-content\" />\r\n\r\n    <!-- Link Modal -->\r\n    <NcModal v-if=\"showLinkModal\" @close=\"closeLinkModal\" :name=\"t('Insert link')\" size=\"normal\">\r\n      <div class=\"link-modal-content\">\r\n        <div class=\"form-group\">\r\n          <label for=\"link-text\">{{ t('Text:') }}</label>\r\n          <input\r\n            id=\"link-text\"\r\n            v-model=\"linkText\"\r\n            type=\"text\"\r\n            :placeholder=\"t('Link text')\"\r\n            class=\"link-input\"\r\n            @keyup.enter=\"applyLink\"\r\n          />\r\n        </div>\r\n        <div class=\"form-group\">\r\n          <label for=\"link-url\">{{ t('URL:') }}</label>\r\n          <input\r\n            id=\"link-url\"\r\n            v-model=\"linkUrl\"\r\n            type=\"url\"\r\n            :placeholder=\"t('https://example.com')\"\r\n            class=\"link-input\"\r\n            @keyup.enter=\"applyLink\"\r\n          />\r\n        </div>\r\n        <div class=\"modal-buttons\">\r\n          <NcButton @click=\"closeLinkModal\" type=\"secondary\">\r\n            {{ t('Cancel') }}\r\n          </NcButton>\r\n          <NcButton @click=\"removeLink\" v-if=\"editor && editor.isActive('link')\" type=\"error\">\r\n            {{ t('Remove link') }}\r\n          </NcButton>\r\n          <NcButton @click=\"applyLink\" type=\"primary\">\r\n            {{ t('Apply') }}\r\n          </NcButton>\r\n        </div>\r\n      </div>\r\n    </NcModal>\r\n  </div>\r\n</template>\r\n\r\n<script>\r\nimport { translate as t } from '@nextcloud/l10n';\r\nimport { NcModal, NcButton } from '@nextcloud/vue';\r\nimport { markdownToHtml, htmlToMarkdown, cleanMarkdown } from '../utils/markdownSerializer.js';\r\n\r\n// TipTap and extensions are loaded lazily on first editor mount to reduce initial bundle size (~500KB)\r\nlet _tiptapModules = null;\r\nasync function loadTipTap() {\r\n    if (_tiptapModules) return _tiptapModules;\r\n    const [vue3, starterKit, underline, link, placeholder, table, tableRow, tableHeader, tableCell, textAlign, dummyText, tableResize] = await Promise.all([\r\n        import('@tiptap/vue-3'),\r\n        import('@tiptap/starter-kit'),\r\n        import('@tiptap/extension-underline'),\r\n        import('@tiptap/extension-link'),\r\n        import('@tiptap/extension-placeholder'),\r\n        import('@tiptap/extension-table'),\r\n        import('@tiptap/extension-table-row'),\r\n        import('@tiptap/extension-table-header'),\r\n        import('@tiptap/extension-table-cell'),\r\n        import('../utils/textAlignExtension.js'),\r\n        import('../utils/dummyTextGenerator.js'),\r\n        import('../utils/tableResizeHandle.js'),\r\n    ]);\r\n    _tiptapModules = {\r\n        Editor: vue3.Editor,\r\n        EditorContent: vue3.EditorContent,\r\n        StarterKit: starterKit.default,\r\n        Underline: underline.default,\r\n        Link: link.default,\r\n        Placeholder: placeholder.default,\r\n        Table: table.Table,\r\n        TableRow: tableRow.TableRow,\r\n        TableHeader: tableHeader.TableHeader,\r\n        TableCell: tableCell.TableCell,\r\n        TextAlign: textAlign.TextAlign,\r\n        DummyTextExtension: dummyText.DummyTextExtension,\r\n        TableResizeHandle: tableResize.TableResizeHandle,\r\n    };\r\n    return _tiptapModules;\r\n}\r\n\r\n// Material Design Icons\r\nimport FormatBold from 'vue-material-design-icons/FormatBold.vue';\r\nimport FormatItalic from 'vue-material-design-icons/FormatItalic.vue';\r\nimport FormatUnderline from 'vue-material-design-icons/FormatUnderline.vue';\r\nimport FormatStrikethrough from 'vue-material-design-icons/FormatStrikethrough.vue';\r\nimport FormatListBulleted from 'vue-material-design-icons/FormatListBulleted.vue';\r\nimport FormatListNumbered from 'vue-material-design-icons/FormatListNumbered.vue';\r\nimport LinkVariant from 'vue-material-design-icons/LinkVariant.vue';\r\nimport TableIcon from 'vue-material-design-icons/Table.vue';\r\nimport TableRowPlusAfter from 'vue-material-design-icons/TableRowPlusAfter.vue';\r\nimport TableRowPlusBefore from 'vue-material-design-icons/TableRowPlusBefore.vue';\r\nimport TableColumnPlusAfter from 'vue-material-design-icons/TableColumnPlusAfter.vue';\r\nimport TableColumnPlusBefore from 'vue-material-design-icons/TableColumnPlusBefore.vue';\r\nimport TableRowRemove from 'vue-material-design-icons/TableRowRemove.vue';\r\nimport TableColumnRemove from 'vue-material-design-icons/TableColumnRemove.vue';\r\nimport TableRemove from 'vue-material-design-icons/TableRemove.vue';\r\nimport FormatAlignLeft from 'vue-material-design-icons/FormatAlignLeft.vue';\r\nimport FormatAlignCenter from 'vue-material-design-icons/FormatAlignCenter.vue';\r\nimport FormatAlignRight from 'vue-material-design-icons/FormatAlignRight.vue';\r\nimport FormatQuoteClose from 'vue-material-design-icons/FormatQuoteClose.vue';\r\nimport ChevronDown from 'vue-material-design-icons/ChevronDown.vue';\r\nimport DotsHorizontal from 'vue-material-design-icons/DotsHorizontal.vue';\r\n\r\nexport default {\r\n  name: 'InlineTextEditor',\r\n  components: {\r\n    NcModal,\r\n    NcButton,\r\n    FormatBold,\r\n    FormatItalic,\r\n    FormatUnderline,\r\n    FormatStrikethrough,\r\n    FormatListBulleted,\r\n    FormatListNumbered,\r\n    LinkVariant,\r\n    TableIcon,\r\n    TableRowPlusAfter,\r\n    TableRowPlusBefore,\r\n    TableColumnPlusAfter,\r\n    TableColumnPlusBefore,\r\n    TableRowRemove,\r\n    TableColumnRemove,\r\n    TableRemove,\r\n    FormatAlignLeft,\r\n    FormatAlignCenter,\r\n    FormatAlignRight,\r\n    FormatQuoteClose,\r\n    ChevronDown,\r\n    DotsHorizontal\r\n  },\r\n  props: {\r\n    modelValue: {\r\n      type: String,\r\n      default: ''\r\n    },\r\n    editable: {\r\n      type: Boolean,\r\n      default: true\r\n    },\r\n    placeholder: {\r\n      type: String,\r\n      default: ''\r\n    },\r\n    compact: {\r\n      type: Boolean,\r\n      default: false\r\n    }\r\n  },\r\n  emits: ['update:modelValue', 'focus', 'blur'],\r\n  data() {\r\n    return {\r\n      editor: null,\r\n      editorContentComponent: null,\r\n      isFocused: false,\r\n      showToolbar: true,\r\n      showLinkModal: false,\r\n      linkUrl: '',\r\n      linkText: '',\r\n      showHeadingMenu: false,\r\n      showTableMenu: false,\r\n      // Width presets shown in the table dropdown — null means \"auto\"\r\n      // (default: fill the widget container).\r\n      tableWidthPresets: [null, '25%', '50%', '75%', '100%'],\r\n      showAlignmentMenu: false,\r\n      showMoreMenu: false,\r\n      alignmentIcons: {\r\n        left: FormatAlignLeft,\r\n        center: FormatAlignCenter,\r\n        right: FormatAlignRight,\r\n      }\r\n    };\r\n  },\r\n  async mounted() {\r\n    // Lazy-load TipTap (~500KB) only when editor is actually mounted\r\n    const modules = await loadTipTap();\r\n    this.editorContentComponent = modules.EditorContent;\r\n\r\n    // Convert Markdown to HTML for TipTap editor\r\n    const htmlContent = markdownToHtml(this.modelValue);\r\n\r\n    this.editor = new modules.Editor({\r\n      content: htmlContent,\r\n      editable: this.editable,\r\n      extensions: [\r\n        modules.StarterKit.configure({\r\n          // Disable built-in extensions we configure separately\r\n          link: false,\r\n          underline: false,\r\n        }),\r\n        modules.Underline,\r\n        modules.Link.configure({\r\n          openOnClick: false,\r\n          HTMLAttributes: {\r\n            target: '_blank',\r\n            rel: 'noopener noreferrer'\r\n          }\r\n        }),\r\n        modules.Placeholder.configure({\r\n          placeholder: this.placeholder || this.t('Enter text...')\r\n        }),\r\n        // Custom Table node — adds `width` and `align` attributes so editors\r\n        // can make a table narrower than the widget container and align it\r\n        // (left, center, right). The base Table.renderHTML composes the colgroup\r\n        // and a default `style` for column-resize; if we hand it a `style`\r\n        // here it short-circuits and uses ours, which is what we want for\r\n        // explicit width/align. We deliberately keep the parent renderHTML so\r\n        // the colgroup keeps working — only the attribute-level renderHTMLs\r\n        // contribute to the merged style.\r\n        modules.Table.extend({\r\n          addAttributes() {\r\n            const parent = this.parent?.() || {};\r\n            return {\r\n              ...parent,\r\n              width: {\r\n                default: null,\r\n                parseHTML: (el) => {\r\n                  const w = (el.style && el.style.width) || el.getAttribute('width') || null;\r\n                  return w && w !== '100%' ? w : null;\r\n                },\r\n                renderHTML: (attrs) => (attrs.width ? { style: `width: ${attrs.width}` } : {}),\r\n              },\r\n              align: {\r\n                default: null,\r\n                parseHTML: (el) => {\r\n                  if (!el.style) return null;\r\n                  const ml = el.style.marginLeft;\r\n                  const mr = el.style.marginRight;\r\n                  if (ml === 'auto' && mr === 'auto') return 'center';\r\n                  if (ml === 'auto' && mr !== 'auto') return 'right';\r\n                  if (mr === 'auto' && ml !== 'auto') return 'left';\r\n                  return null;\r\n                },\r\n                renderHTML: (attrs) => {\r\n                  if (attrs.align === 'center') return { style: 'margin-left: auto; margin-right: auto' };\r\n                  if (attrs.align === 'right') return { style: 'margin-left: auto; margin-right: 0' };\r\n                  if (attrs.align === 'left') return { style: 'margin-left: 0; margin-right: auto' };\r\n                  return {};\r\n                },\r\n              },\r\n            };\r\n          },\r\n        }).configure({\r\n          resizable: true,\r\n          handleWidth: 4,\r\n          // 80px is wide enough for ~3-4 short words plus padding; below\r\n          // that the cell becomes effectively unusable for prose content.\r\n          cellMinWidth: 80,\r\n          lastColumnResizable: true,\r\n        }),\r\n        modules.TableRow,\r\n        modules.TableHeader,\r\n        modules.TableCell,\r\n        modules.TableResizeHandle,\r\n        modules.TextAlign.configure({\r\n          types: ['heading', 'paragraph'],\r\n          alignments: ['left', 'center', 'right'],\r\n          defaultAlignment: 'left',\r\n        }),\r\n        modules.DummyTextExtension,\r\n      ],\r\n      onUpdate: () => {\r\n        // Convert HTML back to Markdown for storage\r\n        const html = this.editor.getHTML();\r\n        const markdown = cleanMarkdown(htmlToMarkdown(html));\r\n        this.$emit('update:modelValue', markdown);\r\n      },\r\n      onFocus: () => {\r\n        this.isFocused = true;\r\n        this.$emit('focus');\r\n\r\n        // Prevent automatic select-all: place cursor at end instead\r\n        // Use setTimeout to let the browser finish its default behavior first\r\n        setTimeout(() => {\r\n          if (!this.editor) return;\r\n          const { state } = this.editor;\r\n          const docSize = state.doc.content.size;\r\n          // If entire content is selected (selectAll behavior), deselect and place cursor at end\r\n          if (state.selection.from <= 1 && state.selection.to >= docSize - 1 && docSize > 2) {\r\n            this.editor.commands.setTextSelection(docSize - 1);\r\n          }\r\n        }, 0);\r\n      },\r\n      onBlur: () => {\r\n        this.isFocused = false;\r\n        this.$emit('blur');\r\n      }\r\n    });\r\n\r\n    // Close heading menu when clicking outside\r\n    document.addEventListener('click', this.handleClickOutside);\r\n  },\r\n  beforeUnmount() {\r\n    if (this.editor) {\r\n      this.editor.destroy();\r\n    }\r\n    document.removeEventListener('click', this.handleClickOutside);\r\n  },\r\n  watch: {\r\n    modelValue(newValue) {\r\n      // Skip update if editor is focused (user is typing)\r\n      if (this.isFocused) {\r\n        return;\r\n      }\r\n\r\n      // Convert Markdown to HTML for comparison\r\n      const htmlContent = markdownToHtml(newValue);\r\n      const currentHtml = this.editor.getHTML();\r\n\r\n      // Only update if content actually changed\r\n      if (currentHtml !== htmlContent) {\r\n        // Save cursor position\r\n        const { from, to } = this.editor.state.selection;\r\n\r\n        // Update content (converted to HTML)\r\n        this.editor.commands.setContent(htmlContent, false);\r\n\r\n        // Restore cursor position if possible\r\n        try {\r\n          this.editor.commands.setTextSelection({ from, to });\r\n        } catch (e) {\r\n          // If restoration fails (content changed too much), just continue\r\n        }\r\n      }\r\n    },\r\n    editable(newValue) {\r\n      this.editor.setEditable(newValue);\r\n    }\r\n  },\r\n  computed: {\r\n    currentAlignmentIcon() {\r\n      if (this.editor?.isActive({ textAlign: 'center' })) return FormatAlignCenter;\r\n      if (this.editor?.isActive({ textAlign: 'right' })) return FormatAlignRight;\r\n      return FormatAlignLeft;\r\n    },\r\n  },\r\n  methods: {\r\n    t(key, vars = {}) {\r\n      return t('intravox', key, vars);\r\n    },\r\n    getCurrentHeadingLevel() {\r\n      if (!this.editor) return 0;\r\n      for (let level = 1; level <= 6; level++) {\r\n        if (this.editor.isActive('heading', { level })) {\r\n          return level;\r\n        }\r\n      }\r\n      return 0;\r\n    },\r\n    getCurrentHeadingLabel() {\r\n      const level = this.getCurrentHeadingLevel();\r\n      return this.getHeadingLabel(level);\r\n    },\r\n    getHeadingLabel(level) {\r\n      if (level === 0) return this.t('Paragraph');\r\n      return this.t(`H${level}`);\r\n    },\r\n    isHeadingLevel(level) {\r\n      return this.getCurrentHeadingLevel() === level;\r\n    },\r\n    toggleHeadingMenu() {\r\n      this.showHeadingMenu = !this.showHeadingMenu;\r\n      this.showAlignmentMenu = false;\r\n      this.showTableMenu = false;\r\n    },\r\n    setHeadingLevel(level) {\r\n      if (level === 0) {\r\n        this.editor.chain().focus().setParagraph().run();\r\n      } else {\r\n        this.editor.chain().focus().setHeading({ level }).run();\r\n      }\r\n      this.showHeadingMenu = false;\r\n    },\r\n    showLinkModalHandler() {\r\n      const { from, to } = this.editor.state.selection;\r\n      const selectedText = this.editor.state.doc.textBetween(from, to, ' ');\r\n\r\n      const previousUrl = this.editor.getAttributes('link').href;\r\n      this.linkUrl = previousUrl || '';\r\n      this.linkText = selectedText || '';\r\n      this.showLinkModal = true;\r\n    },\r\n    closeLinkModal() {\r\n      this.showLinkModal = false;\r\n      this.linkUrl = '';\r\n      this.linkText = '';\r\n      this.editor.chain().focus().run();\r\n    },\r\n    applyLink() {\r\n      if (this.linkUrl === '') {\r\n        this.removeLink();\r\n        return;\r\n      }\r\n\r\n      // Use linkText if provided, otherwise use URL\r\n      const displayText = this.linkText || this.linkUrl;\r\n\r\n      // Get current selection\r\n      const { from, to } = this.editor.state.selection;\r\n\r\n      // If no text is selected or we want to replace it, insert new link\r\n      if (from === to) {\r\n        // Insert new link at cursor position\r\n        this.editor\r\n          .chain()\r\n          .focus()\r\n          .insertContent({\r\n            type: 'text',\r\n            marks: [\r\n              {\r\n                type: 'link',\r\n                attrs: {\r\n                  href: this.linkUrl,\r\n                  target: '_blank',\r\n                  rel: 'noopener noreferrer'\r\n                }\r\n              }\r\n            ],\r\n            text: displayText\r\n          })\r\n          .run();\r\n      } else {\r\n        // Replace selected text with link\r\n        this.editor\r\n          .chain()\r\n          .focus()\r\n          .deleteSelection()\r\n          .insertContent({\r\n            type: 'text',\r\n            marks: [\r\n              {\r\n                type: 'link',\r\n                attrs: {\r\n                  href: this.linkUrl,\r\n                  target: '_blank',\r\n                  rel: 'noopener noreferrer'\r\n                }\r\n              }\r\n            ],\r\n            text: displayText\r\n          })\r\n          .run();\r\n      }\r\n\r\n      this.closeLinkModal();\r\n    },\r\n    removeLink() {\r\n      this.editor\r\n        .chain()\r\n        .focus()\r\n        .extendMarkRange('link')\r\n        .unsetLink()\r\n        .run();\r\n\r\n      this.closeLinkModal();\r\n    },\r\n    handleClickOutside(event) {\r\n      // Close heading menu if clicking outside the dropdown\r\n      if (!event.target.closest('.heading-dropdown')) {\r\n        this.showHeadingMenu = false;\r\n      }\r\n      // Close table menu if clicking outside the dropdown\r\n      if (!event.target.closest('.table-dropdown')) {\r\n        this.showTableMenu = false;\r\n      }\r\n      // Close alignment menu if clicking outside the dropdown\r\n      if (!event.target.closest('.alignment-dropdown')) {\r\n        this.showAlignmentMenu = false;\r\n      }\r\n      // Close more menu if clicking outside the dropdown\r\n      if (!event.target.closest('.more-dropdown')) {\r\n        this.showMoreMenu = false;\r\n      }\r\n    },\r\n    toggleMoreMenu() {\r\n      this.showMoreMenu = !this.showMoreMenu;\r\n      if (this.showMoreMenu) {\r\n        this.$nextTick(() => {\r\n          const btn = this.$refs.moreButton;\r\n          const menu = this.$el.querySelector('.more-menu');\r\n          if (btn && menu) {\r\n            const rect = btn.getBoundingClientRect();\r\n            menu.style.top = (rect.bottom + 4) + 'px';\r\n            menu.style.left = rect.left + 'px';\r\n            // Constrain height to available viewport space\r\n            const availableHeight = window.innerHeight - rect.bottom - 16;\r\n            if (availableHeight < menu.scrollHeight) {\r\n              menu.style.maxHeight = availableHeight + 'px';\r\n            }\r\n          }\r\n        });\r\n      }\r\n    },\r\n    toggleTableMenu() {\r\n      this.showTableMenu = !this.showTableMenu;\r\n      this.showHeadingMenu = false;\r\n      this.showAlignmentMenu = false;\r\n    },\r\n    toggleAlignmentMenu() {\r\n      this.showAlignmentMenu = !this.showAlignmentMenu;\r\n      this.showHeadingMenu = false;\r\n      this.showTableMenu = false;\r\n    },\r\n    setAlignment(align) {\r\n      if (align === 'left') {\r\n        this.editor.chain().focus().unsetTextAlign().run();\r\n      } else {\r\n        this.editor.chain().focus().setTextAlign(align).run();\r\n      }\r\n      this.showAlignmentMenu = false;\r\n    },\r\n    isAlignmentActive(alignment) {\r\n      if (!this.editor) return false;\r\n      if (alignment === 'left') {\r\n        return !this.editor.isActive({ textAlign: 'center' })\r\n            && !this.editor.isActive({ textAlign: 'right' });\r\n      }\r\n      return this.editor.isActive({ textAlign: alignment });\r\n    },\r\n    getAlignmentLabel(align) {\r\n      const labels = {\r\n        left: this.t('Align left'),\r\n        center: this.t('Align center'),\r\n        right: this.t('Align right'),\r\n      };\r\n      return labels[align];\r\n    },\r\n    insertTable() {\r\n      this.editor.chain().focus().insertTable({ rows: 3, cols: 3, withHeaderRow: true }).run();\r\n      this.showTableMenu = false;\r\n    },\r\n    addRowBefore() {\r\n      this.editor.chain().focus().addRowBefore().run();\r\n      this.showTableMenu = false;\r\n    },\r\n    addRowAfter() {\r\n      this.editor.chain().focus().addRowAfter().run();\r\n      this.showTableMenu = false;\r\n    },\r\n    addColumnBefore() {\r\n      this.editor.chain().focus().addColumnBefore().run();\r\n      this.showTableMenu = false;\r\n    },\r\n    addColumnAfter() {\r\n      this.editor.chain().focus().addColumnAfter().run();\r\n      this.showTableMenu = false;\r\n    },\r\n    deleteRow() {\r\n      this.editor.chain().focus().deleteRow().run();\r\n      this.showTableMenu = false;\r\n    },\r\n    deleteColumn() {\r\n      this.editor.chain().focus().deleteColumn().run();\r\n      this.showTableMenu = false;\r\n    },\r\n    deleteTable() {\r\n      this.editor.chain().focus().deleteTable().run();\r\n      this.showTableMenu = false;\r\n    },\r\n\r\n    /**\r\n     * Set the table width. `value` is a CSS length (e.g. '50%', '600px') or\r\n     * null to clear the explicit width and fall back to the default\r\n     * (full-container width with TipTap's column-resize behaviour).\r\n     */\r\n    setTableWidth(value) {\r\n      this.editor.chain().focus().updateAttributes('table', { width: value }).run();\r\n    },\r\n    isTableWidth(value) {\r\n      if (!this.editor || !this.editor.isActive('table')) return false;\r\n      return this.editor.getAttributes('table').width === value;\r\n    },\r\n\r\n    /**\r\n     * Set the table alignment via CSS margin shorthand. Pass null to clear.\r\n     */\r\n    setTableAlign(value) {\r\n      this.editor.chain().focus().updateAttributes('table', { align: value }).run();\r\n    },\r\n    isTableAlign(value) {\r\n      if (!this.editor || !this.editor.isActive('table')) return false;\r\n      return this.editor.getAttributes('table').align === value;\r\n    },\r\n  }\r\n};\r\n</script>\r\n\r\n<style scoped>\r\n.inline-text-editor {\r\n  position: relative;\r\n  width: 100%;\r\n  display: block;\r\n  background: transparent;\r\n}\r\n\r\n/* Floating Toolbar */\r\n.text-menubar {\r\n  position: sticky;\r\n  top: 0;\r\n  z-index: 1000;\r\n  display: flex;\r\n  align-items: center;\r\n  flex-wrap: wrap;\r\n  gap: 4px;\r\n  padding: 8px;\r\n  background: var(--color-main-background);\r\n  border: 1px solid var(--color-border);\r\n  border-radius: var(--border-radius);\r\n  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);\r\n  margin-bottom: 8px;\r\n  max-width: 100%;\r\n  box-sizing: border-box;\r\n}\r\n\r\n.menubar-button {\r\n  padding: 6px 10px;\r\n  background: transparent;\r\n  border: 1px solid transparent;\r\n  border-radius: var(--border-radius);\r\n  cursor: pointer;\r\n  font-size: 14px;\r\n  color: var(--color-main-text);\r\n  transition: all 0.2s ease;\r\n  display: flex;\r\n  align-items: center;\r\n  justify-content: center;\r\n  min-width: 32px;\r\n  height: 32px;\r\n}\r\n\r\n.menubar-button:hover {\r\n  background: var(--color-background-hover);\r\n  border-color: var(--color-border);\r\n}\r\n\r\n.menubar-button.is-active {\r\n  background: var(--color-primary-element);\r\n  border-color: var(--color-primary-element);\r\n  color: var(--color-primary-element-text);\r\n}\r\n\r\n/* Dropdown Containers */\r\n.heading-dropdown,\r\n.table-dropdown,\r\n.alignment-dropdown {\r\n  position: relative;\r\n}\r\n\r\n.heading-button,\r\n.alignment-button {\r\n  min-width: 90px !important;\r\n  gap: 4px;\r\n}\r\n\r\n.alignment-button {\r\n  min-width: 60px !important;\r\n}\r\n\r\n/* Dropdown Menu */\r\n.dropdown-menu {\r\n  position: absolute;\r\n  top: 100%;\r\n  left: 0;\r\n  margin-top: 4px;\r\n  background: var(--color-main-background);\r\n  border: 1px solid var(--color-border);\r\n  border-radius: var(--border-radius);\r\n  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);\r\n  z-index: 1001;\r\n  min-width: 140px;\r\n  max-width: min(280px, calc(100vw - 32px));\r\n  padding: 4px 0;\r\n}\r\n\r\n.table-menu {\r\n  min-width: 180px;\r\n}\r\n\r\n/* Compact Toolbar Mode */\r\n.text-menubar--compact {\r\n  padding: 4px;\r\n  gap: 2px;\r\n}\r\n\r\n.text-menubar--compact .menubar-button {\r\n  padding: 4px 6px;\r\n  min-width: 28px;\r\n  height: 28px;\r\n}\r\n\r\n/* More dropdown for compact mode */\r\n.more-dropdown {\r\n  position: relative;\r\n}\r\n\r\n.more-menu {\r\n  position: fixed;\r\n  min-width: 180px;\r\n  max-width: min(280px, calc(100vw - 32px));\r\n  max-height: calc(100vh - 100px);\r\n  overflow-y: auto;\r\n  z-index: 1002;\r\n}\r\n\r\n.dropdown-menu-item {\r\n  display: flex;\r\n  align-items: center;\r\n  gap: 8px;\r\n  width: 100%;\r\n  padding: 8px 12px;\r\n  background: transparent;\r\n  border: none;\r\n  text-align: left;\r\n  cursor: pointer;\r\n  font-size: 14px;\r\n  color: var(--color-main-text);\r\n  transition: background-color 0.15s ease;\r\n}\r\n\r\n.dropdown-menu-item:hover {\r\n  background: var(--color-background-hover);\r\n}\r\n\r\n.dropdown-menu-item.is-active {\r\n  background: var(--color-primary-element);\r\n  color: var(--color-primary-element-text);\r\n  font-weight: 600;\r\n}\r\n\r\n.dropdown-menu-item--danger {\r\n  color: #c9302c;\r\n  font-weight: 500;\r\n}\r\n\r\n.dropdown-menu-item--danger:hover {\r\n  background: #c9302c;\r\n  color: white;\r\n}\r\n\r\n.dropdown-divider {\r\n  height: 1px;\r\n  background: var(--color-border);\r\n  margin: 4px 0;\r\n}\r\n\r\n/* Section label inside a dropdown — small, muted heading above a row of pills */\r\n.dropdown-menu-section-label {\r\n  font-size: 11px;\r\n  font-weight: 600;\r\n  text-transform: uppercase;\r\n  letter-spacing: 0.04em;\r\n  color: var(--color-text-maxcontrast, #6b7280);\r\n  padding: 6px 12px 2px;\r\n}\r\n\r\n/* Horizontal row of compact buttons inside a dropdown (width presets, align) */\r\n.dropdown-menu-row {\r\n  display: flex;\r\n  gap: 4px;\r\n  padding: 2px 8px 6px;\r\n  flex-wrap: wrap;\r\n}\r\n\r\n.dropdown-menu-pill {\r\n  flex: 1 1 auto;\r\n  min-width: 36px;\r\n  padding: 4px 6px;\r\n  border: 1px solid var(--color-border, #ccc);\r\n  background: var(--color-main-background, #fff);\r\n  color: inherit;\r\n  border-radius: 4px;\r\n  font-size: 12px;\r\n  cursor: pointer;\r\n  display: inline-flex;\r\n  align-items: center;\r\n  justify-content: center;\r\n}\r\n\r\n.dropdown-menu-pill:hover {\r\n  background: var(--color-background-hover, #f3f4f6);\r\n}\r\n\r\n.dropdown-menu-pill.is-active {\r\n  background: var(--color-primary-element, #2563eb);\r\n  color: var(--color-primary-element-text, #fff);\r\n  border-color: var(--color-primary-element, #2563eb);\r\n}\r\n\r\n.menubar-divider {\r\n  width: 1px;\r\n  height: 24px;\r\n  background: var(--color-border);\r\n  margin: 0 4px;\r\n}\r\n\r\n/* Link Modal */\r\n.link-modal-content {\r\n  padding: 20px;\r\n  display: flex;\r\n  flex-direction: column;\r\n  gap: 16px;\r\n}\r\n\r\n.form-group {\r\n  display: flex;\r\n  flex-direction: column;\r\n  gap: 8px;\r\n}\r\n\r\n.link-modal-content label {\r\n  font-weight: 600;\r\n  color: var(--color-main-text);\r\n}\r\n\r\n.link-input {\r\n  width: 100%;\r\n  padding: 8px 12px;\r\n  border: 1px solid var(--color-border);\r\n  border-radius: var(--border-radius);\r\n  font-size: 14px;\r\n  color: var(--color-main-text);\r\n  background: var(--color-main-background);\r\n}\r\n\r\n.link-input:focus {\r\n  outline: none;\r\n  border-color: var(--color-primary-element);\r\n}\r\n\r\n.modal-buttons {\r\n  display: flex;\r\n  gap: 8px;\r\n  justify-content: flex-end;\r\n}\r\n\r\n/* Editor Content */\r\n.editor-content {\r\n  width: 100%;\r\n  display: block;\r\n  background: transparent;\r\n  overflow-x: auto;\r\n}\r\n\r\n.editor-content :deep(.ProseMirror) {\r\n  outline: none;\r\n  min-height: 50px;\r\n  padding: 12px;\r\n  border-radius: var(--border-radius);\r\n  transition: background-color 0.2s ease;\r\n  width: 100%;\r\n  box-sizing: border-box;\r\n  color: inherit;\r\n  background: transparent;\r\n}\r\n\r\n.is-focused .editor-content :deep(.ProseMirror) {\r\n  background: transparent;\r\n}\r\n\r\n/* Text Selection - uses CSS variables from parent Widget for contrast */\r\n.editor-content :deep(.ProseMirror ::selection) {\r\n  background: var(--widget-selection-bg, var(--color-primary-element-light));\r\n  color: var(--widget-selection-text, var(--color-main-text));\r\n}\r\n\r\n.editor-content :deep(.ProseMirror ::-moz-selection) {\r\n  background: var(--widget-selection-bg, var(--color-primary-element-light));\r\n  color: var(--widget-selection-text, var(--color-main-text));\r\n}\r\n\r\n/* Placeholder */\r\n.editor-content :deep(.ProseMirror p.is-editor-empty:first-child::before) {\r\n  content: attr(data-placeholder);\r\n  float: left;\r\n  color: var(--widget-placeholder-color, var(--color-text-maxcontrast));\r\n  pointer-events: none;\r\n  height: 0;\r\n}\r\n\r\n/* Typography */\r\n.editor-content :deep(.ProseMirror p) {\r\n  margin: 0 0 0.5em 0;\r\n  width: 100%;\r\n  color: inherit !important;\r\n}\r\n\r\n.editor-content :deep(.ProseMirror p:last-child) {\r\n  margin-bottom: 0;\r\n}\r\n\r\n.editor-content :deep(.ProseMirror h1),\r\n.editor-content :deep(.ProseMirror h2),\r\n.editor-content :deep(.ProseMirror h3),\r\n.editor-content :deep(.ProseMirror h4),\r\n.editor-content :deep(.ProseMirror h5),\r\n.editor-content :deep(.ProseMirror h6) {\r\n  margin: 0.5em 0;\r\n  font-weight: 600;\r\n  color: inherit !important;\r\n}\r\n\r\n.editor-content :deep(.ProseMirror h1) { font-size: 32px; }\r\n.editor-content :deep(.ProseMirror h2) { font-size: 28px; }\r\n.editor-content :deep(.ProseMirror h3) { font-size: 24px; }\r\n.editor-content :deep(.ProseMirror h4) { font-size: 20px; }\r\n.editor-content :deep(.ProseMirror h5) { font-size: 18px; }\r\n.editor-content :deep(.ProseMirror h6) { font-size: 16px; }\r\n\r\n/* Text alignment */\r\n.editor-content :deep(.ProseMirror .text-align-center) { text-align: center; }\r\n.editor-content :deep(.ProseMirror .text-align-right) { text-align: right; }\r\n\r\n.editor-content :deep(.ProseMirror ul),\r\n.editor-content :deep(.ProseMirror ol) {\r\n  padding-left: 1.5em;\r\n  margin: 0.5em 0;\r\n  width: 100%;\r\n  list-style-position: outside;\r\n  color: inherit !important;\r\n}\r\n\r\n.editor-content :deep(.ProseMirror ul) {\r\n  list-style-type: disc;\r\n}\r\n\r\n.editor-content :deep(.ProseMirror ol) {\r\n  list-style-type: decimal;\r\n}\r\n\r\n.editor-content :deep(.ProseMirror li) {\r\n  margin: 0.25em 0;\r\n  display: list-item;\r\n  color: inherit !important;\r\n}\r\n\r\n/* Nested lists - indentation en list-style hierarchy */\r\n.editor-content :deep(.ProseMirror li > ul),\r\n.editor-content :deep(.ProseMirror li > ol) {\r\n  margin: 0.25em 0;\r\n  padding-left: 1.5em;\r\n}\r\n\r\n/* Ordered list nesting: 1. → a. → i. → 1. */\r\n.editor-content :deep(.ProseMirror ol ol) {\r\n  list-style-type: lower-alpha;\r\n}\r\n.editor-content :deep(.ProseMirror ol ol ol) {\r\n  list-style-type: lower-roman;\r\n}\r\n.editor-content :deep(.ProseMirror ol ol ol ol) {\r\n  list-style-type: decimal;\r\n}\r\n\r\n/* Unordered list nesting: • → ○ → ▪ → • */\r\n.editor-content :deep(.ProseMirror ul ul) {\r\n  list-style-type: circle;\r\n}\r\n.editor-content :deep(.ProseMirror ul ul ul) {\r\n  list-style-type: square;\r\n}\r\n.editor-content :deep(.ProseMirror ul ul ul ul) {\r\n  list-style-type: disc;\r\n}\r\n\r\n.editor-content :deep(.ProseMirror strong) {\r\n  font-weight: bold;\r\n  color: inherit !important;\r\n}\r\n\r\n.editor-content :deep(.ProseMirror em) {\r\n  font-style: italic;\r\n  color: inherit !important;\r\n}\r\n\r\n.editor-content :deep(.ProseMirror u) {\r\n  text-decoration: underline;\r\n  color: inherit !important;\r\n}\r\n\r\n.editor-content :deep(.ProseMirror s) {\r\n  text-decoration: line-through;\r\n  color: inherit !important;\r\n}\r\n\r\n/* Links - uses CSS variables from parent Widget for contrast */\r\n.editor-content :deep(.ProseMirror a) {\r\n  color: var(--widget-link-color, var(--color-primary-element));\r\n  text-decoration: underline;\r\n  cursor: pointer;\r\n}\r\n\r\n.editor-content :deep(.ProseMirror a:hover) {\r\n  color: var(--widget-link-hover-color, var(--color-primary-element-hover));\r\n  text-decoration: none;\r\n}\r\n\r\n/* Blockquote */\r\n.editor-content :deep(.ProseMirror blockquote) {\r\n  border-left: 4px solid var(--color-primary-element);\r\n  padding-left: 1em;\r\n  margin: 1em 0;\r\n  color: inherit !important;\r\n  font-style: italic;\r\n}\r\n\r\n/* Code */\r\n.editor-content :deep(.ProseMirror code) {\r\n  background: var(--color-background-dark);\r\n  padding: 2px 6px;\r\n  border-radius: var(--border-radius);\r\n  font-family: 'Courier New', Courier, monospace;\r\n  font-size: 0.9em;\r\n  color: inherit !important;\r\n}\r\n\r\n/* Code Block */\r\n.editor-content :deep(.ProseMirror pre) {\r\n  background: var(--color-background-dark);\r\n  padding: 12px;\r\n  border-radius: var(--border-radius-large);\r\n  overflow-x: auto;\r\n  margin: 1em 0;\r\n}\r\n\r\n.editor-content :deep(.ProseMirror pre code) {\r\n  background: transparent;\r\n  padding: 0;\r\n  font-family: 'Courier New', Courier, monospace;\r\n  color: inherit !important;\r\n}\r\n\r\n/* Tables wider than the page column scroll horizontally inside this wrapper\r\n   instead of pushing the page sideways (TipTap injects the wrapper). */\r\n.editor-content :deep(.ProseMirror .tableWrapper) {\r\n  overflow-x: auto;\r\n  max-width: 100%;\r\n  margin: 1em 0;\r\n}\r\n\r\n.editor-content :deep(.ProseMirror table) {\r\n  border-collapse: collapse;\r\n  table-layout: fixed;\r\n  min-width: 100%;\r\n  margin: 1em 0;\r\n}\r\n\r\n.editor-content :deep(.ProseMirror .tableWrapper > table) {\r\n  margin: 0;\r\n}\r\n\r\n/* When the user sets an explicit width via the Table extension, drop the\r\n   min-width: 100% so the table can shrink to that width. */\r\n.editor-content :deep(.ProseMirror table[style*=\"width:\"]) {\r\n  min-width: 0;\r\n}\r\n\r\n/* Right-edge resize handle for the active table. The TableResizeHandle plugin\r\n   adds the data attribute via a node decoration; this CSS gives the handle a\r\n   visible affordance so editors notice they can drag-resize the table. */\r\n.editor-content :deep(.ProseMirror table[data-table-resize-active]) {\r\n  position: relative;\r\n}\r\n\r\n.editor-content :deep(.ProseMirror table[data-table-resize-active])::after {\r\n  content: '';\r\n  position: absolute;\r\n  top: 0;\r\n  right: -4px;\r\n  bottom: 0;\r\n  width: 8px;\r\n  background: linear-gradient(to right, transparent 0, var(--color-primary-element, #2563eb) 50%, transparent 100%);\r\n  opacity: 0.35;\r\n  pointer-events: none;\r\n}\r\n\r\n.editor-content :deep(.ProseMirror table[data-table-resize-active]):hover::after {\r\n  opacity: 0.7;\r\n}\r\n\r\n/* Mirrors the read-mode wrap policy in Widget.vue: cells shrink to their\r\n   column share, content wraps anywhere if needed, white-space: normal\r\n   overrides a Nextcloud core nowrap rule. */\r\n.editor-content :deep(.ProseMirror table td),\r\n.editor-content :deep(.ProseMirror table th) {\r\n  border: 1px solid var(--color-border-dark, #bbb);\r\n  padding: 8px 12px;\r\n  vertical-align: top;\r\n  box-sizing: border-box;\r\n  position: relative;\r\n  color: inherit !important;\r\n  overflow-wrap: anywhere;\r\n  min-width: 0;\r\n  hyphens: auto;\r\n  white-space: normal;\r\n}\r\n\r\n.editor-content :deep(.ProseMirror table td > *),\r\n.editor-content :deep(.ProseMirror table th > *) {\r\n  overflow-wrap: anywhere;\r\n  min-width: 0;\r\n  max-width: 100%;\r\n  white-space: normal;\r\n}\r\n\r\n.editor-content :deep(.ProseMirror table .selectedCell) {\r\n  background: var(--color-primary-element-light);\r\n}\r\n\r\n/* Column resize handle — subtle line on cell border */\r\n.editor-content :deep(.ProseMirror table .column-resize-handle) {\r\n  position: absolute;\r\n  right: -2px;\r\n  top: 0;\r\n  bottom: 0;\r\n  width: 4px;\r\n  background-color: var(--color-primary-element);\r\n  pointer-events: none;\r\n  z-index: 10;\r\n}\r\n\r\n/* Task List */\r\n.editor-content :deep(.ProseMirror ul[data-type=\"taskList\"]) {\r\n  list-style: none;\r\n  padding-left: 0;\r\n}\r\n\r\n.editor-content :deep(.ProseMirror ul[data-type=\"taskList\"] li) {\r\n  display: flex;\r\n  align-items: flex-start;\r\n  color: inherit !important;\r\n}\r\n\r\n.editor-content :deep(.ProseMirror ul[data-type=\"taskList\"] li > label) {\r\n  flex: 0 0 auto;\r\n  margin-right: 0.5em;\r\n  user-select: none;\r\n}\r\n\r\n.editor-content :deep(.ProseMirror ul[data-type=\"taskList\"] li > div) {\r\n  flex: 1 1 auto;\r\n}\r\n\r\n.editor-content :deep(.ProseMirror ul[data-type=\"taskList\"] input[type=\"checkbox\"]) {\r\n  cursor: pointer;\r\n  width: 1.2em;\r\n  height: 1.2em;\r\n}\r\n\r\n/* Horizontal Rule */\r\n.editor-content :deep(.ProseMirror hr) {\r\n  border: none;\r\n  border-top: 2px solid var(--color-border);\r\n  margin: 2em 0;\r\n}\r\n\r\n/* Mobile styles */\r\n@media (max-width: 480px) {\r\n  .text-menubar {\r\n    padding: 6px;\r\n    gap: 2px;\r\n  }\r\n\r\n  .menubar-button {\r\n    padding: 4px 6px;\r\n    min-width: 28px;\r\n    height: 28px;\r\n  }\r\n\r\n  .menubar-divider {\r\n    display: none; /* Hide dividers on mobile to save space */\r\n  }\r\n\r\n  .heading-button {\r\n    min-width: 70px !important;\r\n    font-size: 12px;\r\n  }\r\n\r\n  .dropdown-menu {\r\n    min-width: 120px;\r\n    max-width: calc(100vw - 32px);\r\n  }\r\n\r\n  .dropdown-menu-item {\r\n    padding: 6px 10px;\r\n    font-size: 13px;\r\n  }\r\n}\r\n\r\n@media (max-width: 360px) {\r\n  .text-menubar {\r\n    padding: 4px;\r\n  }\r\n\r\n  .menubar-button {\r\n    padding: 3px 5px;\r\n    min-width: 26px;\r\n    height: 26px;\r\n  }\r\n\r\n  .heading-button {\r\n    min-width: 60px !important;\r\n    font-size: 11px;\r\n  }\r\n}\r\n</style>\r\n"],"sourceRoot":""}]);
// Exports
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (___CSS_LOADER_EXPORT___);


/***/ },

/***/ "./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/dist/stylePostLoader.js!./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/PageTreeSelect.vue?vue&type=style&index=0&id=5efccfd9&scoped=true&lang=css"
/*!***************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/dist/stylePostLoader.js!./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/PageTreeSelect.vue?vue&type=style&index=0&id=5efccfd9&scoped=true&lang=css ***!
  \***************************************************************************************************************************************************************************************************************************************************************/
(module, __webpack_exports__, __webpack_require__) {

"use strict";
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
.page-tree-select[data-v-5efccfd9] {
  position: relative;
  width: 100%;
}
.select-trigger[data-v-5efccfd9] {
  display: flex;
  align-items: center;
  justify-content: space-between;
  width: 100%;
  padding: 6px 10px;
  background: var(--color-main-background);
  border: 1px solid var(--color-border);
  border-radius: var(--border-radius);
  cursor: pointer;
  text-align: left;
  font-size: 13px;
  min-height: 34px;
  gap: 8px;
}
.select-trigger[data-v-5efccfd9]:hover:not(:disabled) {
  border-color: var(--color-primary-element);
}
.select-trigger.is-disabled[data-v-5efccfd9] {
  opacity: 0.5;
  cursor: not-allowed;
  background: var(--color-background-dark);
}
.select-trigger.is-open[data-v-5efccfd9] {
  border-color: var(--color-primary-element);
  border-bottom-left-radius: 0;
  border-bottom-right-radius: 0;
}
.selected-value[data-v-5efccfd9] {
  display: flex;
  align-items: center;
  gap: 6px;
  flex: 1;
  min-width: 0;
  overflow: hidden;
}
.selected-icon[data-v-5efccfd9] {
  flex-shrink: 0;
  color: var(--color-primary-element);
}
.selected-path[data-v-5efccfd9] {
  color: var(--color-text-maxcontrast);
  font-size: 12px;
  white-space: nowrap;
}
.selected-title[data-v-5efccfd9] {
  font-weight: 500;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}
.placeholder[data-v-5efccfd9] {
  color: var(--color-text-maxcontrast);
}
.trigger-icon[data-v-5efccfd9] {
  flex-shrink: 0;
  color: var(--color-text-maxcontrast);
  transition: transform 0.2s;
}
.trigger-icon.rotated[data-v-5efccfd9] {
  transform: rotate(180deg);
}
.select-dropdown[data-v-5efccfd9] {
  position: absolute;
  top: 100%;
  left: 0;
  right: 0;
  background: var(--color-main-background);
  border: 1px solid var(--color-primary-element);
  border-top: none;
  border-radius: 0 0 var(--border-radius) var(--border-radius);
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
  z-index: 1000;
  max-height: 350px;
  display: flex;
  flex-direction: column;
}
.search-wrapper[data-v-5efccfd9] {
  display: flex;
  align-items: center;
  padding: 8px;
  border-bottom: 1px solid var(--color-border);
  gap: 8px;
}
.search-icon[data-v-5efccfd9] {
  flex-shrink: 0;
  color: var(--color-text-maxcontrast);
}
.search-input[data-v-5efccfd9] {
  flex: 1;
  border: none;
  background: none;
  font-size: 13px;
  outline: none;
  padding: 4px 0;
}
.search-input[data-v-5efccfd9]::placeholder {
  color: var(--color-text-maxcontrast);
}
.clear-search[data-v-5efccfd9] {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 20px;
  height: 20px;
  padding: 0;
  border: none;
  background: var(--color-background-dark);
  border-radius: 50%;
  cursor: pointer;
  color: var(--color-text-maxcontrast);
}
.clear-search[data-v-5efccfd9]:hover {
  background: var(--color-error);
  color: white;
}
.tree-wrapper[data-v-5efccfd9] {
  flex: 1;
  overflow-y: auto;
  padding: 8px;
  max-height: 250px;
}
.loading-state[data-v-5efccfd9],
.empty-state[data-v-5efccfd9] {
  padding: 20px;
  text-align: center;
  color: var(--color-text-maxcontrast);
}
.tree-list[data-v-5efccfd9] {
  list-style: none;
  margin: 0;
  padding: 0;
}
.clear-selection[data-v-5efccfd9] {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 6px;
  padding: 8px;
  border: none;
  border-top: 1px solid var(--color-border);
  background: var(--color-background-dark);
  cursor: pointer;
  font-size: 12px;
  color: var(--color-text-maxcontrast);
}
.clear-selection[data-v-5efccfd9]:hover {
  background: var(--color-error-hover);
  color: var(--color-error);
}
`, "",{"version":3,"sources":["webpack://./src/components/PageTreeSelect.vue"],"names":[],"mappings":";AAgXA;EACE,kBAAkB;EAClB,WAAW;AACb;AAEA;EACE,aAAa;EACb,mBAAmB;EACnB,8BAA8B;EAC9B,WAAW;EACX,iBAAiB;EACjB,wCAAwC;EACxC,qCAAqC;EACrC,mCAAmC;EACnC,eAAe;EACf,gBAAgB;EAChB,eAAe;EACf,gBAAgB;EAChB,QAAQ;AACV;AAEA;EACE,0CAA0C;AAC5C;AAEA;EACE,YAAY;EACZ,mBAAmB;EACnB,wCAAwC;AAC1C;AAEA;EACE,0CAA0C;EAC1C,4BAA4B;EAC5B,6BAA6B;AAC/B;AAEA;EACE,aAAa;EACb,mBAAmB;EACnB,QAAQ;EACR,OAAO;EACP,YAAY;EACZ,gBAAgB;AAClB;AAEA;EACE,cAAc;EACd,mCAAmC;AACrC;AAEA;EACE,oCAAoC;EACpC,eAAe;EACf,mBAAmB;AACrB;AAEA;EACE,gBAAgB;EAChB,mBAAmB;EACnB,gBAAgB;EAChB,uBAAuB;AACzB;AAEA;EACE,oCAAoC;AACtC;AAEA;EACE,cAAc;EACd,oCAAoC;EACpC,0BAA0B;AAC5B;AAEA;EACE,yBAAyB;AAC3B;AAEA;EACE,kBAAkB;EAClB,SAAS;EACT,OAAO;EACP,QAAQ;EACR,wCAAwC;EACxC,8CAA8C;EAC9C,gBAAgB;EAChB,4DAA4D;EAC5D,0CAA0C;EAC1C,aAAa;EACb,iBAAiB;EACjB,aAAa;EACb,sBAAsB;AACxB;AAEA;EACE,aAAa;EACb,mBAAmB;EACnB,YAAY;EACZ,4CAA4C;EAC5C,QAAQ;AACV;AAEA;EACE,cAAc;EACd,oCAAoC;AACtC;AAEA;EACE,OAAO;EACP,YAAY;EACZ,gBAAgB;EAChB,eAAe;EACf,aAAa;EACb,cAAc;AAChB;AAEA;EACE,oCAAoC;AACtC;AAEA;EACE,aAAa;EACb,mBAAmB;EACnB,uBAAuB;EACvB,WAAW;EACX,YAAY;EACZ,UAAU;EACV,YAAY;EACZ,wCAAwC;EACxC,kBAAkB;EAClB,eAAe;EACf,oCAAoC;AACtC;AAEA;EACE,8BAA8B;EAC9B,YAAY;AACd;AAEA;EACE,OAAO;EACP,gBAAgB;EAChB,YAAY;EACZ,iBAAiB;AACnB;AAEA;;EAEE,aAAa;EACb,kBAAkB;EAClB,oCAAoC;AACtC;AAEA;EACE,gBAAgB;EAChB,SAAS;EACT,UAAU;AACZ;AAEA;EACE,aAAa;EACb,mBAAmB;EACnB,uBAAuB;EACvB,QAAQ;EACR,YAAY;EACZ,YAAY;EACZ,yCAAyC;EACzC,wCAAwC;EACxC,eAAe;EACf,eAAe;EACf,oCAAoC;AACtC;AAEA;EACE,oCAAoC;EACpC,yBAAyB;AAC3B","sourcesContent":["<template>\r\n  <div class=\"page-tree-select\" ref=\"selectContainer\">\r\n    <!-- Selected value / trigger -->\r\n    <button\r\n      type=\"button\"\r\n      class=\"select-trigger\"\r\n      :class=\"{ 'is-open': isOpen, 'has-value': selectedPage, 'is-disabled': disabled }\"\r\n      :disabled=\"disabled\"\r\n      role=\"combobox\"\r\n      :aria-expanded=\"isOpen\"\r\n      aria-haspopup=\"listbox\"\r\n      @click=\"toggleDropdown\"\r\n      ref=\"trigger\"\r\n    >\r\n      <span v-if=\"selectedPage\" class=\"selected-value\">\r\n        <FileDocument :size=\"16\" class=\"selected-icon\" />\r\n        <span class=\"selected-path\" v-if=\"selectedPath\">{{ selectedPath }} /</span>\r\n        <span class=\"selected-title\">{{ selectedPage.title }}</span>\r\n      </span>\r\n      <span v-else class=\"placeholder\">{{ placeholder }}</span>\r\n      <ChevronDown :size=\"18\" class=\"trigger-icon\" :class=\"{ 'rotated': isOpen }\" />\r\n    </button>\r\n\r\n    <!-- Dropdown -->\r\n    <div v-if=\"isOpen\" class=\"select-dropdown\" ref=\"dropdown\">\r\n      <!-- Search input -->\r\n      <div class=\"search-wrapper\">\r\n        <Magnify :size=\"16\" class=\"search-icon\" />\r\n        <input\r\n          ref=\"searchInput\"\r\n          v-model=\"searchQuery\"\r\n          type=\"text\"\r\n          class=\"search-input\"\r\n          :placeholder=\"t('Search pages...')\"\r\n          :aria-label=\"t('Search pages')\"\r\n          @keydown.escape=\"closeDropdown\"\r\n          @keydown.down.prevent=\"focusNext\"\r\n          @keydown.up.prevent=\"focusPrev\"\r\n          @keydown.enter.prevent=\"selectFocused\"\r\n        />\r\n        <button v-if=\"searchQuery\" class=\"clear-search\" @click=\"searchQuery = ''\" :aria-label=\"t('Clear search')\">\r\n          <Close :size=\"14\" />\r\n        </button>\r\n      </div>\r\n\r\n      <!-- Tree list -->\r\n      <div class=\"tree-wrapper\" ref=\"treeWrapper\">\r\n        <div v-if=\"loading\" class=\"loading-state\">\r\n          <NcLoadingIcon :size=\"24\" />\r\n        </div>\r\n\r\n        <div v-else-if=\"filteredTree.length === 0\" class=\"empty-state\">\r\n          {{ searchQuery ? t('No pages found') : t('No pages available') }}\r\n        </div>\r\n\r\n        <ul v-else class=\"tree-list\" role=\"listbox\">\r\n          <PageTreeSelectItem\r\n            v-for=\"item in filteredTree\"\r\n            :key=\"item.uniqueId\"\r\n            :item=\"item\"\r\n            :expanded-nodes=\"expandedNodes\"\r\n            :selected-id=\"modelValue\"\r\n            :focused-id=\"focusedId\"\r\n            :search-query=\"searchQuery\"\r\n            @toggle=\"toggleNode\"\r\n            @select=\"selectPage\"\r\n            @focus=\"focusedId = $event\"\r\n          />\r\n        </ul>\r\n      </div>\r\n\r\n      <!-- Clear selection -->\r\n      <button v-if=\"selectedPage && clearable\" class=\"clear-selection\" @click=\"clearSelection\">\r\n        <Close :size=\"16\" />\r\n        {{ t('Clear selection') }}\r\n      </button>\r\n    </div>\r\n  </div>\r\n</template>\r\n\r\n<script>\r\nimport { translate } from '@nextcloud/l10n';\r\nimport { NcLoadingIcon } from '@nextcloud/vue';\r\nimport axios from '@nextcloud/axios';\r\nimport { generateUrl } from '@nextcloud/router';\r\nimport ChevronDown from 'vue-material-design-icons/ChevronDown.vue';\r\nimport FileDocument from 'vue-material-design-icons/FileDocument.vue';\r\nimport Magnify from 'vue-material-design-icons/Magnify.vue';\r\nimport Close from 'vue-material-design-icons/Close.vue';\r\nimport PageTreeSelectItem from './PageTreeSelectItem.vue';\r\n\r\nexport default {\r\n  name: 'PageTreeSelect',\r\n  components: {\r\n    NcLoadingIcon,\r\n    ChevronDown,\r\n    FileDocument,\r\n    Magnify,\r\n    Close,\r\n    PageTreeSelectItem\r\n  },\r\n  props: {\r\n    modelValue: {\r\n      type: String,\r\n      default: null\r\n    },\r\n    placeholder: {\r\n      type: String,\r\n      default: 'Select a page'\r\n    },\r\n    clearable: {\r\n      type: Boolean,\r\n      default: true\r\n    },\r\n    disabled: {\r\n      type: Boolean,\r\n      default: false\r\n    },\r\n    language: {\r\n      type: String,\r\n      default: null\r\n    },\r\n    /**\r\n     * Restrict the dropdown to the subtree rooted at this page uniqueId.\r\n     * Useful for picking a child page within a known section (e.g. a\r\n     * teamhub builder that only cares about pages under /teamhub/).\r\n     * When null, the full tree is shown.\r\n     */\r\n    rootPageId: {\r\n      type: String,\r\n      default: null\r\n    }\r\n  },\r\n  emits: ['update:modelValue', 'select'],\r\n  data() {\r\n    return {\r\n      isOpen: false,\r\n      loading: false,\r\n      tree: [],\r\n      expandedNodes: new Set(),\r\n      searchQuery: '',\r\n      focusedId: null,\r\n      flatPages: []\r\n    };\r\n  },\r\n  computed: {\r\n    selectedPage() {\r\n      if (!this.modelValue) return null;\r\n      return this.flatPages.find(p => p.uniqueId === this.modelValue);\r\n    },\r\n    selectedPath() {\r\n      if (!this.selectedPage || !this.selectedPage.path) return null;\r\n      return this.selectedPage.path;\r\n    },\r\n    filteredTree() {\r\n      if (!this.searchQuery.trim()) {\r\n        return this.tree;\r\n      }\r\n      const query = this.searchQuery.toLowerCase();\r\n      return this.filterTree(this.tree, query);\r\n    },\r\n    focusableItems() {\r\n      // Get flat list of visible items for keyboard navigation\r\n      return this.getFlatVisibleItems(this.filteredTree);\r\n    }\r\n  },\r\n  watch: {\r\n    isOpen(open) {\r\n      if (open) {\r\n        this.$nextTick(() => {\r\n          this.$refs.searchInput?.focus();\r\n          // Expand path to selected item\r\n          if (this.modelValue) {\r\n            this.expandPathToItem(this.tree, this.modelValue);\r\n          }\r\n        });\r\n      } else {\r\n        this.searchQuery = '';\r\n        this.focusedId = null;\r\n      }\r\n    },\r\n    language() {\r\n      // Reload tree when language changes\r\n      this.loadTree();\r\n    },\r\n    rootPageId() {\r\n      // Switching the subtree anchor means we need a different slice\r\n      // of the tree — the backend handles the filtering, we just refetch.\r\n      this.loadTree();\r\n    },\r\n    modelValue: {\r\n      immediate: true,\r\n      handler(newValue) {\r\n        // If we have a value but tree is not loaded yet, load it\r\n        if (newValue && this.flatPages.length === 0 && !this.loading) {\r\n          this.loadTree();\r\n        }\r\n      }\r\n    }\r\n  },\r\n  mounted() {\r\n    document.addEventListener('click', this.handleClickOutside);\r\n    this.loadTree();\r\n  },\r\n  beforeUnmount() {\r\n    document.removeEventListener('click', this.handleClickOutside);\r\n  },\r\n  methods: {\r\n    t(key, vars = {}) {\r\n      return translate('intravox', key, vars);\r\n    },\r\n    async loadTree() {\r\n      this.loading = true;\r\n      try {\r\n        const params = {};\r\n        if (this.language) {\r\n          params.language = this.language;\r\n        }\r\n        if (this.rootPageId) {\r\n          params.rootPageId = this.rootPageId;\r\n        }\r\n        const response = await axios.get(generateUrl('/apps/intravox/api/pages/tree'), { params });\r\n        const tree = response.data.tree || [];\r\n\r\n        this.tree = tree;\r\n        this.flatPages = this.flattenTree(this.tree, '');\r\n\r\n        // Auto-expand first level\r\n        this.tree.forEach(item => {\r\n          if (item.children && item.children.length > 0) {\r\n            this.expandedNodes.add(item.uniqueId);\r\n          }\r\n        });\r\n      } catch (err) {\r\n        console.error('PageTreeSelect: Error loading tree:', err);\r\n      } finally {\r\n        this.loading = false;\r\n      }\r\n    },\r\n    flattenTree(nodes, parentPath) {\r\n      const result = [];\r\n      for (const node of nodes) {\r\n        const path = parentPath;\r\n        result.push({\r\n          uniqueId: node.uniqueId,\r\n          title: node.title,\r\n          path: path,\r\n          hasChildren: node.children && node.children.length > 0\r\n        });\r\n        if (node.children && node.children.length > 0) {\r\n          const childPath = parentPath ? `${parentPath} / ${node.title}` : node.title;\r\n          result.push(...this.flattenTree(node.children, childPath));\r\n        }\r\n      }\r\n      return result;\r\n    },\r\n    filterTree(nodes, query) {\r\n      const result = [];\r\n      for (const node of nodes) {\r\n        const matches = node.title.toLowerCase().includes(query);\r\n        const filteredChildren = node.children ? this.filterTree(node.children, query) : [];\r\n\r\n        if (matches || filteredChildren.length > 0) {\r\n          result.push({\r\n            ...node,\r\n            children: filteredChildren,\r\n            matchesSearch: matches\r\n          });\r\n          // Auto-expand nodes that have matching children\r\n          if (filteredChildren.length > 0) {\r\n            this.expandedNodes.add(node.uniqueId);\r\n          }\r\n        }\r\n      }\r\n      return result;\r\n    },\r\n    getFlatVisibleItems(nodes) {\r\n      const result = [];\r\n      for (const node of nodes) {\r\n        result.push(node);\r\n        if (node.children && node.children.length > 0 && this.expandedNodes.has(node.uniqueId)) {\r\n          result.push(...this.getFlatVisibleItems(node.children));\r\n        }\r\n      }\r\n      return result;\r\n    },\r\n    expandPathToItem(nodes, targetId) {\r\n      for (const node of nodes) {\r\n        if (node.uniqueId === targetId) {\r\n          return true;\r\n        }\r\n        if (node.children && node.children.length > 0) {\r\n          if (this.expandPathToItem(node.children, targetId)) {\r\n            this.expandedNodes.add(node.uniqueId);\r\n            return true;\r\n          }\r\n        }\r\n      }\r\n      return false;\r\n    },\r\n    toggleDropdown() {\r\n      this.isOpen = !this.isOpen;\r\n    },\r\n    closeDropdown() {\r\n      this.isOpen = false;\r\n    },\r\n    toggleNode(uniqueId) {\r\n      if (this.expandedNodes.has(uniqueId)) {\r\n        this.expandedNodes.delete(uniqueId);\r\n      } else {\r\n        this.expandedNodes.add(uniqueId);\r\n      }\r\n      this.expandedNodes = new Set(this.expandedNodes);\r\n    },\r\n    selectPage(page) {\r\n      this.$emit('update:modelValue', page.uniqueId);\r\n      this.$emit('select', page);\r\n      this.closeDropdown();\r\n    },\r\n    clearSelection() {\r\n      this.$emit('update:modelValue', null);\r\n      this.$emit('select', null);\r\n      this.closeDropdown();\r\n    },\r\n    handleClickOutside(event) {\r\n      if (this.$refs.selectContainer && !this.$refs.selectContainer.contains(event.target)) {\r\n        this.closeDropdown();\r\n      }\r\n    },\r\n    focusNext() {\r\n      const items = this.focusableItems;\r\n      if (items.length === 0) return;\r\n\r\n      const currentIndex = items.findIndex(i => i.uniqueId === this.focusedId);\r\n      const nextIndex = currentIndex < items.length - 1 ? currentIndex + 1 : 0;\r\n      this.focusedId = items[nextIndex].uniqueId;\r\n      this.scrollToFocused();\r\n    },\r\n    focusPrev() {\r\n      const items = this.focusableItems;\r\n      if (items.length === 0) return;\r\n\r\n      const currentIndex = items.findIndex(i => i.uniqueId === this.focusedId);\r\n      const prevIndex = currentIndex > 0 ? currentIndex - 1 : items.length - 1;\r\n      this.focusedId = items[prevIndex].uniqueId;\r\n      this.scrollToFocused();\r\n    },\r\n    selectFocused() {\r\n      if (this.focusedId) {\r\n        const page = this.flatPages.find(p => p.uniqueId === this.focusedId);\r\n        if (page) {\r\n          this.selectPage(page);\r\n        }\r\n      }\r\n    },\r\n    scrollToFocused() {\r\n      this.$nextTick(() => {\r\n        const focusedEl = this.$refs.treeWrapper?.querySelector('.is-focused');\r\n        if (focusedEl) {\r\n          focusedEl.scrollIntoView({ block: 'nearest' });\r\n        }\r\n      });\r\n    }\r\n  }\r\n};\r\n</script>\r\n\r\n<style scoped>\r\n.page-tree-select {\r\n  position: relative;\r\n  width: 100%;\r\n}\r\n\r\n.select-trigger {\r\n  display: flex;\r\n  align-items: center;\r\n  justify-content: space-between;\r\n  width: 100%;\r\n  padding: 6px 10px;\r\n  background: var(--color-main-background);\r\n  border: 1px solid var(--color-border);\r\n  border-radius: var(--border-radius);\r\n  cursor: pointer;\r\n  text-align: left;\r\n  font-size: 13px;\r\n  min-height: 34px;\r\n  gap: 8px;\r\n}\r\n\r\n.select-trigger:hover:not(:disabled) {\r\n  border-color: var(--color-primary-element);\r\n}\r\n\r\n.select-trigger.is-disabled {\r\n  opacity: 0.5;\r\n  cursor: not-allowed;\r\n  background: var(--color-background-dark);\r\n}\r\n\r\n.select-trigger.is-open {\r\n  border-color: var(--color-primary-element);\r\n  border-bottom-left-radius: 0;\r\n  border-bottom-right-radius: 0;\r\n}\r\n\r\n.selected-value {\r\n  display: flex;\r\n  align-items: center;\r\n  gap: 6px;\r\n  flex: 1;\r\n  min-width: 0;\r\n  overflow: hidden;\r\n}\r\n\r\n.selected-icon {\r\n  flex-shrink: 0;\r\n  color: var(--color-primary-element);\r\n}\r\n\r\n.selected-path {\r\n  color: var(--color-text-maxcontrast);\r\n  font-size: 12px;\r\n  white-space: nowrap;\r\n}\r\n\r\n.selected-title {\r\n  font-weight: 500;\r\n  white-space: nowrap;\r\n  overflow: hidden;\r\n  text-overflow: ellipsis;\r\n}\r\n\r\n.placeholder {\r\n  color: var(--color-text-maxcontrast);\r\n}\r\n\r\n.trigger-icon {\r\n  flex-shrink: 0;\r\n  color: var(--color-text-maxcontrast);\r\n  transition: transform 0.2s;\r\n}\r\n\r\n.trigger-icon.rotated {\r\n  transform: rotate(180deg);\r\n}\r\n\r\n.select-dropdown {\r\n  position: absolute;\r\n  top: 100%;\r\n  left: 0;\r\n  right: 0;\r\n  background: var(--color-main-background);\r\n  border: 1px solid var(--color-primary-element);\r\n  border-top: none;\r\n  border-radius: 0 0 var(--border-radius) var(--border-radius);\r\n  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);\r\n  z-index: 1000;\r\n  max-height: 350px;\r\n  display: flex;\r\n  flex-direction: column;\r\n}\r\n\r\n.search-wrapper {\r\n  display: flex;\r\n  align-items: center;\r\n  padding: 8px;\r\n  border-bottom: 1px solid var(--color-border);\r\n  gap: 8px;\r\n}\r\n\r\n.search-icon {\r\n  flex-shrink: 0;\r\n  color: var(--color-text-maxcontrast);\r\n}\r\n\r\n.search-input {\r\n  flex: 1;\r\n  border: none;\r\n  background: none;\r\n  font-size: 13px;\r\n  outline: none;\r\n  padding: 4px 0;\r\n}\r\n\r\n.search-input::placeholder {\r\n  color: var(--color-text-maxcontrast);\r\n}\r\n\r\n.clear-search {\r\n  display: flex;\r\n  align-items: center;\r\n  justify-content: center;\r\n  width: 20px;\r\n  height: 20px;\r\n  padding: 0;\r\n  border: none;\r\n  background: var(--color-background-dark);\r\n  border-radius: 50%;\r\n  cursor: pointer;\r\n  color: var(--color-text-maxcontrast);\r\n}\r\n\r\n.clear-search:hover {\r\n  background: var(--color-error);\r\n  color: white;\r\n}\r\n\r\n.tree-wrapper {\r\n  flex: 1;\r\n  overflow-y: auto;\r\n  padding: 8px;\r\n  max-height: 250px;\r\n}\r\n\r\n.loading-state,\r\n.empty-state {\r\n  padding: 20px;\r\n  text-align: center;\r\n  color: var(--color-text-maxcontrast);\r\n}\r\n\r\n.tree-list {\r\n  list-style: none;\r\n  margin: 0;\r\n  padding: 0;\r\n}\r\n\r\n.clear-selection {\r\n  display: flex;\r\n  align-items: center;\r\n  justify-content: center;\r\n  gap: 6px;\r\n  padding: 8px;\r\n  border: none;\r\n  border-top: 1px solid var(--color-border);\r\n  background: var(--color-background-dark);\r\n  cursor: pointer;\r\n  font-size: 12px;\r\n  color: var(--color-text-maxcontrast);\r\n}\r\n\r\n.clear-selection:hover {\r\n  background: var(--color-error-hover);\r\n  color: var(--color-error);\r\n}\r\n</style>\r\n"],"sourceRoot":""}]);
// Exports
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (___CSS_LOADER_EXPORT___);


/***/ },

/***/ "./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/dist/stylePostLoader.js!./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/PageTreeSelectItem.vue?vue&type=style&index=0&id=621c650c&scoped=true&lang=css"
/*!*******************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/dist/stylePostLoader.js!./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/PageTreeSelectItem.vue?vue&type=style&index=0&id=621c650c&scoped=true&lang=css ***!
  \*******************************************************************************************************************************************************************************************************************************************************************/
(module, __webpack_exports__, __webpack_require__) {

"use strict";
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
.tree-select-item[data-v-621c650c] {
  list-style: none;
}
.item-row[data-v-621c650c] {
  display: flex;
  align-items: center;
  border-radius: 4px;
  margin: 1px 0;
}
.item-row[data-v-621c650c]:hover {
  background: var(--color-background-hover);
}
.item-row.is-focused[data-v-621c650c] {
  background: var(--color-background-hover);
  outline: 2px solid var(--color-primary-element);
  outline-offset: -2px;
}
.item-row.is-selected[data-v-621c650c] {
  background: var(--color-primary-element-light);
}
.item-row.matches-search .item-title[data-v-621c650c] {
  font-weight: 600;
}
.expand-toggle[data-v-621c650c] {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 22px;
  height: 22px;
  padding: 0;
  margin: 0;
  background: none;
  border: none;
  cursor: pointer;
  color: var(--color-text-maxcontrast);
  border-radius: 4px;
  flex-shrink: 0;
}
.expand-toggle[data-v-621c650c]:hover {
  background: var(--color-background-dark);
  color: var(--color-main-text);
}
.toggle-spacer[data-v-621c650c] {
  width: 22px;
  flex-shrink: 0;
}
.item-content[data-v-621c650c] {
  display: flex;
  align-items: center;
  gap: 6px;
  flex: 1;
  padding: 5px 8px 5px 4px;
  background: none;
  border: none;
  cursor: pointer;
  text-align: left;
  color: var(--color-main-text);
  font-size: 13px;
  min-width: 0;
}
.item-icon[data-v-621c650c] {
  flex-shrink: 0;
  color: var(--color-text-maxcontrast);
}
.item-icon.folder-icon[data-v-621c650c] {
  color: var(--color-primary-element);
}
.item-title[data-v-621c650c] {
  flex: 1;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}
.check-icon[data-v-621c650c] {
  flex-shrink: 0;
  color: var(--color-primary-element);
}
.children-list[data-v-621c650c] {
  list-style: none;
  margin: 0;
  padding: 0 0 0 22px;
}
.tree-show-more[data-v-621c650c] {
  list-style: none;
}
.show-more-button[data-v-621c650c] {
  display: block;
  width: 100%;
  padding: 5px 8px 5px 28px;
  background: none;
  border: none;
  cursor: pointer;
  text-align: left;
  color: var(--color-primary-element);
  font-size: 12px;
  border-radius: 4px;
}
.show-more-button[data-v-621c650c]:hover {
  background: var(--color-background-hover);
}
`, "",{"version":3,"sources":["webpack://./src/components/PageTreeSelectItem.vue"],"names":[],"mappings":";AAiIA;EACE,gBAAgB;AAClB;AAEA;EACE,aAAa;EACb,mBAAmB;EACnB,kBAAkB;EAClB,aAAa;AACf;AAEA;EACE,yCAAyC;AAC3C;AAEA;EACE,yCAAyC;EACzC,+CAA+C;EAC/C,oBAAoB;AACtB;AAEA;EACE,8CAA8C;AAChD;AAEA;EACE,gBAAgB;AAClB;AAEA;EACE,aAAa;EACb,mBAAmB;EACnB,uBAAuB;EACvB,WAAW;EACX,YAAY;EACZ,UAAU;EACV,SAAS;EACT,gBAAgB;EAChB,YAAY;EACZ,eAAe;EACf,oCAAoC;EACpC,kBAAkB;EAClB,cAAc;AAChB;AAEA;EACE,wCAAwC;EACxC,6BAA6B;AAC/B;AAEA;EACE,WAAW;EACX,cAAc;AAChB;AAEA;EACE,aAAa;EACb,mBAAmB;EACnB,QAAQ;EACR,OAAO;EACP,wBAAwB;EACxB,gBAAgB;EAChB,YAAY;EACZ,eAAe;EACf,gBAAgB;EAChB,6BAA6B;EAC7B,eAAe;EACf,YAAY;AACd;AAEA;EACE,cAAc;EACd,oCAAoC;AACtC;AAEA;EACE,mCAAmC;AACrC;AAEA;EACE,OAAO;EACP,gBAAgB;EAChB,uBAAuB;EACvB,mBAAmB;AACrB;AAEA;EACE,cAAc;EACd,mCAAmC;AACrC;AAEA;EACE,gBAAgB;EAChB,SAAS;EACT,mBAAmB;AACrB;AAEA;EACE,gBAAgB;AAClB;AAEA;EACE,cAAc;EACd,WAAW;EACX,yBAAyB;EACzB,gBAAgB;EAChB,YAAY;EACZ,eAAe;EACf,gBAAgB;EAChB,mCAAmC;EACnC,eAAe;EACf,kBAAkB;AACpB;AAEA;EACE,yCAAyC;AAC3C","sourcesContent":["<template>\r\n  <li class=\"tree-select-item\">\r\n    <div\r\n      class=\"item-row\"\r\n      :class=\"{\r\n        'is-selected': isSelected,\r\n        'is-focused': isFocused,\r\n        'matches-search': item.matchesSearch\r\n      }\"\r\n      @mouseenter=\"$emit('focus', item.uniqueId)\"\r\n    >\r\n      <!-- Expand/collapse toggle -->\r\n      <button\r\n        v-if=\"hasChildren\"\r\n        class=\"expand-toggle\"\r\n        @click.stop=\"$emit('toggle', item.uniqueId)\"\r\n        :aria-label=\"isExpanded ? 'Collapse' : 'Expand'\"\r\n      >\r\n        <ChevronRight v-if=\"!isExpanded\" :size=\"16\" />\r\n        <ChevronDown v-else :size=\"16\" />\r\n      </button>\r\n      <span v-else class=\"toggle-spacer\"></span>\r\n\r\n      <!-- Page item -->\r\n      <button class=\"item-content\" @click=\"$emit('select', item)\">\r\n        <FolderOutline v-if=\"hasChildren\" :size=\"16\" class=\"item-icon folder-icon\" />\r\n        <FileDocument v-else :size=\"16\" class=\"item-icon\" />\r\n        <span class=\"item-title\">{{ item.title }}</span>\r\n        <Check v-if=\"isSelected\" :size=\"16\" class=\"check-icon\" />\r\n      </button>\r\n    </div>\r\n\r\n    <!-- Children (progressive rendering) -->\r\n    <ul v-if=\"hasChildren && isExpanded\" class=\"children-list\">\r\n      <PageTreeSelectItem\r\n        v-for=\"child in visibleChildren\"\r\n        :key=\"child.uniqueId\"\r\n        :item=\"child\"\r\n        :expanded-nodes=\"expandedNodes\"\r\n        :selected-id=\"selectedId\"\r\n        :focused-id=\"focusedId\"\r\n        :search-query=\"searchQuery\"\r\n        @toggle=\"(id) => $emit('toggle', id)\"\r\n        @select=\"(page) => $emit('select', page)\"\r\n        @focus=\"(id) => $emit('focus', id)\"\r\n      />\r\n      <li v-if=\"hasMoreChildren\" class=\"tree-show-more\">\r\n        <button class=\"show-more-button\" @click=\"showMoreChildren\">\r\n          Show {{ item.children.length - visibleChildCount }} more...\r\n        </button>\r\n      </li>\r\n    </ul>\r\n  </li>\r\n</template>\r\n\r\n<script>\r\nimport ChevronRight from 'vue-material-design-icons/ChevronRight.vue';\r\nimport ChevronDown from 'vue-material-design-icons/ChevronDown.vue';\r\nimport FileDocument from 'vue-material-design-icons/FileDocument.vue';\r\nimport FolderOutline from 'vue-material-design-icons/FolderOutline.vue';\r\nimport Check from 'vue-material-design-icons/Check.vue';\r\n\r\nexport default {\r\n  name: 'PageTreeSelectItem',\r\n  components: {\r\n    ChevronRight,\r\n    ChevronDown,\r\n    FileDocument,\r\n    FolderOutline,\r\n    Check\r\n  },\r\n  props: {\r\n    item: {\r\n      type: Object,\r\n      required: true\r\n    },\r\n    expandedNodes: {\r\n      type: Set,\r\n      required: true\r\n    },\r\n    selectedId: {\r\n      type: String,\r\n      default: null\r\n    },\r\n    focusedId: {\r\n      type: String,\r\n      default: null\r\n    },\r\n    searchQuery: {\r\n      type: String,\r\n      default: ''\r\n    }\r\n  },\r\n  emits: ['toggle', 'select', 'focus'],\r\n  data() {\r\n    return {\r\n      visibleChildCount: 50,\r\n    };\r\n  },\r\n  computed: {\r\n    hasChildren() {\r\n      return this.item.children && this.item.children.length > 0;\r\n    },\r\n    isExpanded() {\r\n      return this.expandedNodes.has(this.item.uniqueId);\r\n    },\r\n    isSelected() {\r\n      return this.item.uniqueId === this.selectedId;\r\n    },\r\n    isFocused() {\r\n      return this.item.uniqueId === this.focusedId;\r\n    },\r\n    visibleChildren() {\r\n      if (!this.hasChildren) return [];\r\n      return this.item.children.slice(0, this.visibleChildCount);\r\n    },\r\n    hasMoreChildren() {\r\n      return this.hasChildren && this.item.children.length > this.visibleChildCount;\r\n    },\r\n  },\r\n  methods: {\r\n    showMoreChildren() {\r\n      this.visibleChildCount += 50;\r\n    },\r\n  },\r\n};\r\n</script>\r\n\r\n<style scoped>\r\n.tree-select-item {\r\n  list-style: none;\r\n}\r\n\r\n.item-row {\r\n  display: flex;\r\n  align-items: center;\r\n  border-radius: 4px;\r\n  margin: 1px 0;\r\n}\r\n\r\n.item-row:hover {\r\n  background: var(--color-background-hover);\r\n}\r\n\r\n.item-row.is-focused {\r\n  background: var(--color-background-hover);\r\n  outline: 2px solid var(--color-primary-element);\r\n  outline-offset: -2px;\r\n}\r\n\r\n.item-row.is-selected {\r\n  background: var(--color-primary-element-light);\r\n}\r\n\r\n.item-row.matches-search .item-title {\r\n  font-weight: 600;\r\n}\r\n\r\n.expand-toggle {\r\n  display: flex;\r\n  align-items: center;\r\n  justify-content: center;\r\n  width: 22px;\r\n  height: 22px;\r\n  padding: 0;\r\n  margin: 0;\r\n  background: none;\r\n  border: none;\r\n  cursor: pointer;\r\n  color: var(--color-text-maxcontrast);\r\n  border-radius: 4px;\r\n  flex-shrink: 0;\r\n}\r\n\r\n.expand-toggle:hover {\r\n  background: var(--color-background-dark);\r\n  color: var(--color-main-text);\r\n}\r\n\r\n.toggle-spacer {\r\n  width: 22px;\r\n  flex-shrink: 0;\r\n}\r\n\r\n.item-content {\r\n  display: flex;\r\n  align-items: center;\r\n  gap: 6px;\r\n  flex: 1;\r\n  padding: 5px 8px 5px 4px;\r\n  background: none;\r\n  border: none;\r\n  cursor: pointer;\r\n  text-align: left;\r\n  color: var(--color-main-text);\r\n  font-size: 13px;\r\n  min-width: 0;\r\n}\r\n\r\n.item-icon {\r\n  flex-shrink: 0;\r\n  color: var(--color-text-maxcontrast);\r\n}\r\n\r\n.item-icon.folder-icon {\r\n  color: var(--color-primary-element);\r\n}\r\n\r\n.item-title {\r\n  flex: 1;\r\n  overflow: hidden;\r\n  text-overflow: ellipsis;\r\n  white-space: nowrap;\r\n}\r\n\r\n.check-icon {\r\n  flex-shrink: 0;\r\n  color: var(--color-primary-element);\r\n}\r\n\r\n.children-list {\r\n  list-style: none;\r\n  margin: 0;\r\n  padding: 0 0 0 22px;\r\n}\r\n\r\n.tree-show-more {\r\n  list-style: none;\r\n}\r\n\r\n.show-more-button {\r\n  display: block;\r\n  width: 100%;\r\n  padding: 5px 8px 5px 28px;\r\n  background: none;\r\n  border: none;\r\n  cursor: pointer;\r\n  text-align: left;\r\n  color: var(--color-primary-element);\r\n  font-size: 12px;\r\n  border-radius: 4px;\r\n}\r\n\r\n.show-more-button:hover {\r\n  background: var(--color-background-hover);\r\n}\r\n</style>\r\n"],"sourceRoot":""}]);
// Exports
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (___CSS_LOADER_EXPORT___);


/***/ },

/***/ "./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/dist/stylePostLoader.js!./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/feed/FeedItem.vue?vue&type=style&index=0&id=82e10b3c&scoped=true&lang=css"
/*!**************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/dist/stylePostLoader.js!./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/feed/FeedItem.vue?vue&type=style&index=0&id=82e10b3c&scoped=true&lang=css ***!
  \**************************************************************************************************************************************************************************************************************************************************************/
(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _node_modules_css_loader_dist_runtime_sourceMaps_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ../../../node_modules/css-loader/dist/runtime/sourceMaps.js */ "./node_modules/css-loader/dist/runtime/sourceMaps.js");
/* harmony import */ var _node_modules_css_loader_dist_runtime_sourceMaps_js__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_node_modules_css_loader_dist_runtime_sourceMaps_js__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _node_modules_css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ../../../node_modules/css-loader/dist/runtime/api.js */ "./node_modules/css-loader/dist/runtime/api.js");
/* harmony import */ var _node_modules_css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_node_modules_css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_1__);
// Imports


var ___CSS_LOADER_EXPORT___ = _node_modules_css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_1___default()((_node_modules_css_loader_dist_runtime_sourceMaps_js__WEBPACK_IMPORTED_MODULE_0___default()));
// Module
___CSS_LOADER_EXPORT___.push([module.id, `
.feed-item[data-v-82e10b3c] {
  display: flex;
  gap: 16px;
  padding: 16px;
  border: 1px solid var(--color-border);
  border-radius: var(--border-radius-large);
  text-decoration: none;
  color: inherit;
  transition: all 0.2s ease;
  position: relative;
  min-width: 0;
  overflow: hidden;
  box-sizing: border-box;
}
.feed-item[data-v-82e10b3c]:focus-visible {
  outline: 2px solid var(--color-primary);
  outline-offset: 2px;
}
.feed-item--bg-default[data-v-82e10b3c] {
  background: var(--color-background-hover);
}
.feed-item--bg-default[data-v-82e10b3c]:hover {
  border-color: var(--color-primary);
  background: var(--color-primary-element-light);
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
}
.feed-item--bg-transparent[data-v-82e10b3c] {
  background: transparent;
}
.feed-item--bg-transparent[data-v-82e10b3c]:hover {
  background: var(--color-background-hover);
  border-color: var(--color-primary);
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
}
.feed-item--bg-white[data-v-82e10b3c] {
  background: var(--color-main-background);
}
.feed-item--bg-white[data-v-82e10b3c]:hover {
  border-color: var(--color-primary);
  background: var(--color-primary-element-light);
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
}
.feed-item--bg-dark[data-v-82e10b3c] {
  background: rgba(255, 255, 255, 0.15);
  border-color: rgba(255, 255, 255, 0.2);
}
.feed-item--bg-dark[data-v-82e10b3c]:hover {
  background: rgba(255, 255, 255, 0.25);
  border-color: rgba(255, 255, 255, 0.35);
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
}
.feed-item--bg-dark .feed-item-title[data-v-82e10b3c] {
  color: white;
}
.feed-item--bg-dark:hover .feed-item-title[data-v-82e10b3c] {
  color: white;
}
.feed-item--bg-dark .feed-item-meta[data-v-82e10b3c],
.feed-item--bg-dark .feed-item-excerpt[data-v-82e10b3c] {
  color: rgba(255, 255, 255, 0.85);
}
.feed-item--compact[data-v-82e10b3c] {
  padding: 12px;
  gap: 12px;
}
.feed-item--no-image[data-v-82e10b3c] {
  flex-direction: column;
}
.feed-item-image[data-v-82e10b3c] {
  flex-shrink: 0;
  width: 120px;
  max-width: 100%;
  height: 80px;
  border-radius: var(--border-radius);
  overflow: hidden;
  background: var(--color-background-dark);
}
.feed-item--compact .feed-item-image[data-v-82e10b3c] {
  width: 80px;
  height: 60px;
}
.feed-item-image img[data-v-82e10b3c] {
  width: 100%;
  height: 100%;
  object-fit: cover;
}
.feed-item-feed-icon[data-v-82e10b3c] {
  flex-shrink: 0;
  width: 40px;
  height: 40px;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 8px;
  overflow: hidden;
  align-self: flex-start;
  margin-top: 2px;
  background: var(--color-background-dark);
}
.feed-item--compact .feed-item-feed-icon[data-v-82e10b3c] {
  width: 32px;
  height: 32px;
  border-radius: 6px;
}
.feed-item-feed-icon img[data-v-82e10b3c] {
  width: 100%;
  height: 100%;
  object-fit: contain;
}
.feed-item-fallback[data-v-82e10b3c] {
  flex-shrink: 0;
  width: 40px;
  height: 40px;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 1px;
  color: white;
  border-radius: 8px;
  align-self: flex-start;
  margin-top: 2px;
}
.feed-item--compact .feed-item-fallback[data-v-82e10b3c] {
  width: 32px;
  height: 32px;
  border-radius: 6px;
}
.feed-item-fallback-icon[data-v-82e10b3c] {
  opacity: 0.9;
}
.feed-item-fallback-label[data-v-82e10b3c] {
  font-size: 7px;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.3px;
  opacity: 0.9;
  line-height: 1;
}
.feed-item-content[data-v-82e10b3c] {
  flex: 1;
  min-width: 0;
  display: flex;
  flex-direction: column;
  gap: 4px;
}
.feed-item-title[data-v-82e10b3c] {
  margin: 0;
  font-size: 14px;
  font-weight: 600;
  color: var(--color-main-text);
  line-height: 1.3;
  overflow: hidden;
  text-overflow: ellipsis;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow-wrap: break-word;
  word-break: break-word;
}
.feed-item--compact .feed-item-title[data-v-82e10b3c] {
  font-size: 13px;
  -webkit-line-clamp: 1;
}
.feed-item:hover .feed-item-title[data-v-82e10b3c] {
  color: var(--color-primary);
}
.feed-item--bg-dark:hover .feed-item-title[data-v-82e10b3c] {
  color: var(--color-primary-element-text);
}
.feed-item-meta[data-v-82e10b3c] {
  display: flex;
  align-items: center;
  gap: 12px;
  font-size: 12px;
  color: var(--color-text-maxcontrast);
  flex-wrap: wrap;
}
.feed-item-date[data-v-82e10b3c] {
  display: flex;
  align-items: center;
  gap: 4px;
}
.feed-item-source[data-v-82e10b3c] {
  font-style: italic;
}
.feed-item-excerpt[data-v-82e10b3c] {
  margin: 4px 0 0 0;
  font-size: 13px;
  color: var(--color-text-maxcontrast);
  line-height: 1.4;
  overflow: hidden;
  text-overflow: ellipsis;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
}
.feed-item--compact .feed-item-excerpt[data-v-82e10b3c] {
  display: none;
}
.feed-item-external-icon[data-v-82e10b3c] {
  position: absolute;
  top: 12px;
  right: 12px;
  color: var(--color-text-maxcontrast);
  opacity: 0;
  transition: opacity 0.2s;
}
.feed-item:hover .feed-item-external-icon[data-v-82e10b3c] {
  opacity: 1;
}

/* Container query: medium width (250-400px) — compact mode */
@container (max-width: 400px) {
.feed-item[data-v-82e10b3c] {
    padding: 12px;
    gap: 10px;
}
.feed-item-image[data-v-82e10b3c] {
    width: 80px;
    height: 60px;
}
.feed-item-title[data-v-82e10b3c] {
    font-size: 13px;
}
.feed-item-meta[data-v-82e10b3c] {
    font-size: 11px;
    gap: 8px;
}
.feed-item-excerpt[data-v-82e10b3c] {
    display: none;
}
}

/* Container query: narrow width (<250px) — compact vertical for photos only */
@container (max-width: 250px) {
.feed-item[data-v-82e10b3c] {
    padding: 10px;
    gap: 8px;
}
.feed-item-title[data-v-82e10b3c] {
    font-size: 12px;
}
.feed-item-meta[data-v-82e10b3c] {
    font-size: 10px;
}
}
@media (max-width: 600px) {
.feed-item[data-v-82e10b3c] {
    flex-direction: column;
    gap: 12px;
}
.feed-item-image[data-v-82e10b3c] {
    width: 100%;
    height: 160px;
}
}
`, "",{"version":3,"sources":["webpack://./src/components/feed/FeedItem.vue"],"names":[],"mappings":";AA6LA;EACE,aAAa;EACb,SAAS;EACT,aAAa;EACb,qCAAqC;EACrC,yCAAyC;EACzC,qBAAqB;EACrB,cAAc;EACd,yBAAyB;EACzB,kBAAkB;EAClB,YAAY;EACZ,gBAAgB;EAChB,sBAAsB;AACxB;AAEA;EACE,uCAAuC;EACvC,mBAAmB;AACrB;AAEA;EACE,yCAAyC;AAC3C;AAEA;EACE,kCAAkC;EAClC,8CAA8C;EAC9C,yCAAyC;AAC3C;AAEA;EACE,uBAAuB;AACzB;AAEA;EACE,yCAAyC;EACzC,kCAAkC;EAClC,yCAAyC;AAC3C;AAEA;EACE,wCAAwC;AAC1C;AAEA;EACE,kCAAkC;EAClC,8CAA8C;EAC9C,yCAAyC;AAC3C;AAEA;EACE,qCAAqC;EACrC,sCAAsC;AACxC;AAEA;EACE,qCAAqC;EACrC,uCAAuC;EACvC,yCAAyC;AAC3C;AAEA;EACE,YAAY;AACd;AAEA;EACE,YAAY;AACd;AAEA;;EAEE,gCAAgC;AAClC;AAEA;EACE,aAAa;EACb,SAAS;AACX;AAEA;EACE,sBAAsB;AACxB;AAEA;EACE,cAAc;EACd,YAAY;EACZ,eAAe;EACf,YAAY;EACZ,mCAAmC;EACnC,gBAAgB;EAChB,wCAAwC;AAC1C;AAEA;EACE,WAAW;EACX,YAAY;AACd;AAEA;EACE,WAAW;EACX,YAAY;EACZ,iBAAiB;AACnB;AAEA;EACE,cAAc;EACd,WAAW;EACX,YAAY;EACZ,aAAa;EACb,mBAAmB;EACnB,uBAAuB;EACvB,kBAAkB;EAClB,gBAAgB;EAChB,sBAAsB;EACtB,eAAe;EACf,wCAAwC;AAC1C;AAEA;EACE,WAAW;EACX,YAAY;EACZ,kBAAkB;AACpB;AAEA;EACE,WAAW;EACX,YAAY;EACZ,mBAAmB;AACrB;AAEA;EACE,cAAc;EACd,WAAW;EACX,YAAY;EACZ,aAAa;EACb,sBAAsB;EACtB,mBAAmB;EACnB,uBAAuB;EACvB,QAAQ;EACR,YAAY;EACZ,kBAAkB;EAClB,sBAAsB;EACtB,eAAe;AACjB;AAEA;EACE,WAAW;EACX,YAAY;EACZ,kBAAkB;AACpB;AAEA;EACE,YAAY;AACd;AAEA;EACE,cAAc;EACd,gBAAgB;EAChB,yBAAyB;EACzB,qBAAqB;EACrB,YAAY;EACZ,cAAc;AAChB;AAEA;EACE,OAAO;EACP,YAAY;EACZ,aAAa;EACb,sBAAsB;EACtB,QAAQ;AACV;AAEA;EACE,SAAS;EACT,eAAe;EACf,gBAAgB;EAChB,6BAA6B;EAC7B,gBAAgB;EAChB,gBAAgB;EAChB,uBAAuB;EACvB,oBAAoB;EACpB,qBAAqB;EACrB,4BAA4B;EAC5B,yBAAyB;EACzB,sBAAsB;AACxB;AAEA;EACE,eAAe;EACf,qBAAqB;AACvB;AAEA;EACE,2BAA2B;AAC7B;AAEA;EACE,wCAAwC;AAC1C;AAEA;EACE,aAAa;EACb,mBAAmB;EACnB,SAAS;EACT,eAAe;EACf,oCAAoC;EACpC,eAAe;AACjB;AAEA;EACE,aAAa;EACb,mBAAmB;EACnB,QAAQ;AACV;AAEA;EACE,kBAAkB;AACpB;AAEA;EACE,iBAAiB;EACjB,eAAe;EACf,oCAAoC;EACpC,gBAAgB;EAChB,gBAAgB;EAChB,uBAAuB;EACvB,oBAAoB;EACpB,qBAAqB;EACrB,4BAA4B;AAC9B;AAEA;EACE,aAAa;AACf;AAEA;EACE,kBAAkB;EAClB,SAAS;EACT,WAAW;EACX,oCAAoC;EACpC,UAAU;EACV,wBAAwB;AAC1B;AAEA;EACE,UAAU;AACZ;;AAEA,6DAA6D;AAC7D;AACE;IACE,aAAa;IACb,SAAS;AACX;AAEA;IACE,WAAW;IACX,YAAY;AACd;AAEA;IACE,eAAe;AACjB;AAEA;IACE,eAAe;IACf,QAAQ;AACV;AAEA;IACE,aAAa;AACf;AACF;;AAEA,8EAA8E;AAC9E;AACE;IACE,aAAa;IACb,QAAQ;AACV;AAEA;IACE,eAAe;AACjB;AAEA;IACE,eAAe;AACjB;AACF;AAEA;AACE;IACE,sBAAsB;IACtB,SAAS;AACX;AAEA;IACE,WAAW;IACX,aAAa;AACf;AACF","sourcesContent":["<template>\n  <a\n    :href=\"item.url\"\n    class=\"feed-item\"\n    :aria-label=\"item.title + (formattedDate ? ' — ' + formattedDate : '')\"\n    :class=\"[\n      { 'feed-item--compact': compact, 'feed-item--no-image': !showImage || (!item.image && (!feedImage || feedImageError) && !fallbackMeta) },\n      `feed-item--bg-${itemBackground}`\n    ]\"\n    :target=\"openInNewTab ? '_blank' : '_self'\"\n    :rel=\"openInNewTab ? 'noopener noreferrer' : undefined\"\n  >\n    <div v-if=\"showImage && item.image\" class=\"feed-item-image\">\n      <img :src=\"item.image\" :alt=\"item.title\" loading=\"lazy\" referrerpolicy=\"no-referrer\" />\n    </div>\n    <div v-else-if=\"showImage && feedImage && !feedImageError\" class=\"feed-item-feed-icon\">\n      <img :src=\"feedImage\" :alt=\"item.source || ''\" loading=\"lazy\" referrerpolicy=\"no-referrer\" @error=\"feedImageError = true\" />\n    </div>\n    <div v-else-if=\"showImage && fallbackMeta\" class=\"feed-item-fallback\" :style=\"{ backgroundColor: fallbackMeta.color }\">\n      <component :is=\"fallbackMeta.icon\" :size=\"compact ? 20 : 22\" class=\"feed-item-fallback-icon\" />\n      <span v-if=\"item.fileType\" class=\"feed-item-fallback-label\">{{ item.fileType.toUpperCase() }}</span>\n    </div>\n    <div class=\"feed-item-content\">\n      <h4 class=\"feed-item-title\">{{ item.title }}</h4>\n      <div class=\"feed-item-meta\">\n        <span v-if=\"showDate && item.date\" class=\"feed-item-date\">\n          <CalendarBlank :size=\"14\" />\n          <span>{{ formattedDate }}</span>\n        </span>\n        <span v-if=\"showSource && item.source\" class=\"feed-item-source\">\n          {{ item.source }}\n        </span>\n        <span v-if=\"item.author\" class=\"feed-item-author\">\n          {{ item.author }}\n        </span>\n      </div>\n      <p v-if=\"showExcerpt && item.excerpt\" class=\"feed-item-excerpt\">\n        {{ truncatedExcerpt }}\n      </p>\n    </div>\n    <OpenInNew v-if=\"openInNewTab\" :size=\"14\" class=\"feed-item-external-icon\" />\n  </a>\n</template>\n\n<script>\nimport CalendarBlank from 'vue-material-design-icons/CalendarBlank.vue';\nimport OpenInNew from 'vue-material-design-icons/OpenInNew.vue';\nimport FileWord from 'vue-material-design-icons/FileWord.vue';\nimport FileExcel from 'vue-material-design-icons/FileExcel.vue';\nimport FilePowerpoint from 'vue-material-design-icons/FilePowerpoint.vue';\nimport FilePdfBox from 'vue-material-design-icons/FilePdfBox.vue';\nimport FileImage from 'vue-material-design-icons/FileImage.vue';\nimport FileVideo from 'vue-material-design-icons/FileVideo.vue';\nimport FileDocument from 'vue-material-design-icons/FileDocument.vue';\nimport BugOutline from 'vue-material-design-icons/BugOutline.vue';\nimport BookOpenPageVariant from 'vue-material-design-icons/BookOpenPageVariant.vue';\nimport MicrosoftSharepoint from 'vue-material-design-icons/MicrosoftSharepoint.vue';\nimport ClipboardText from 'vue-material-design-icons/ClipboardText.vue';\nimport SchoolOutline from 'vue-material-design-icons/SchoolOutline.vue';\nimport RssBox from 'vue-material-design-icons/RssBox.vue';\nimport ViewDashboard from 'vue-material-design-icons/ViewDashboard.vue';\n\nconst FILE_TYPE_MAP = {\n  doc: { color: '#2B579A', icon: FileWord },\n  docx: { color: '#2B579A', icon: FileWord },\n  xls: { color: '#217346', icon: FileExcel },\n  xlsx: { color: '#217346', icon: FileExcel },\n  csv: { color: '#217346', icon: FileExcel },\n  ppt: { color: '#D24726', icon: FilePowerpoint },\n  pptx: { color: '#D24726', icon: FilePowerpoint },\n  pdf: { color: '#E2574C', icon: FilePdfBox },\n  jpg: { color: '#7B83EB', icon: FileImage },\n  jpeg: { color: '#7B83EB', icon: FileImage },\n  png: { color: '#7B83EB', icon: FileImage },\n  gif: { color: '#7B83EB', icon: FileImage },\n  webp: { color: '#7B83EB', icon: FileImage },\n  svg: { color: '#7B83EB', icon: FileImage },\n  mp4: { color: '#8764B8', icon: FileVideo },\n  mov: { color: '#8764B8', icon: FileVideo },\n  avi: { color: '#8764B8', icon: FileVideo },\n  webm: { color: '#8764B8', icon: FileVideo },\n};\n\nconst CONNECTION_TYPE_MAP = {\n  jira: { color: '#0052CC', icon: BugOutline },\n  confluence: { color: '#1868DB', icon: BookOpenPageVariant },\n  sharepoint: { color: '#038387', icon: MicrosoftSharepoint },\n  openproject: { color: '#1A67A3', icon: ClipboardText },\n  moodle: { color: '#F98012', icon: SchoolOutline },\n  canvas: { color: '#E03E2D', icon: SchoolOutline },\n  brightspace: { color: '#F5A623', icon: SchoolOutline },\n  rss: { color: '#F26522', icon: RssBox },\n  custom: { color: '#6C757D', icon: ViewDashboard },\n};\n\nexport default {\n  name: 'FeedItem',\n  components: {\n    CalendarBlank,\n    OpenInNew,\n    FileWord, FileExcel, FilePowerpoint, FilePdfBox, FileImage, FileVideo, FileDocument,\n    BugOutline, BookOpenPageVariant, MicrosoftSharepoint, ClipboardText, SchoolOutline, RssBox, ViewDashboard,\n  },\n  data() {\n    return {\n      feedImageError: false,\n    };\n  },\n  props: {\n    item: {\n      type: Object,\n      required: true,\n    },\n    showImage: {\n      type: Boolean,\n      default: true,\n    },\n    feedImage: {\n      type: String,\n      default: null,\n    },\n    showDate: {\n      type: Boolean,\n      default: true,\n    },\n    showExcerpt: {\n      type: Boolean,\n      default: true,\n    },\n    showSource: {\n      type: Boolean,\n      default: false,\n    },\n    excerptLength: {\n      type: Number,\n      default: 150,\n    },\n    compact: {\n      type: Boolean,\n      default: false,\n    },\n    openInNewTab: {\n      type: Boolean,\n      default: true,\n    },\n    itemBackground: {\n      type: String,\n      default: 'default',\n      validator: (value) => ['default', 'transparent', 'white', 'dark'].includes(value),\n    },\n  },\n  computed: {\n    formattedDate() {\n      if (!this.item.date) return '';\n      try {\n        const date = new Date(this.item.date);\n        const locale = document.documentElement.lang || undefined;\n        return date.toLocaleDateString(locale, {\n          year: 'numeric',\n          month: 'short',\n          day: 'numeric',\n        });\n      } catch {\n        return this.item.date;\n      }\n    },\n    fallbackMeta() {\n      if (this.item.image) return null;\n      if (this.feedImage && !this.feedImageError) return null;\n      if (this.item.fileType && FILE_TYPE_MAP[this.item.fileType]) {\n        return FILE_TYPE_MAP[this.item.fileType];\n      }\n      if (this.item.connectionType && CONNECTION_TYPE_MAP[this.item.connectionType]) {\n        return CONNECTION_TYPE_MAP[this.item.connectionType];\n      }\n      return { color: '#6C757D', icon: FileDocument };\n    },\n    truncatedExcerpt() {\n      if (!this.item.excerpt) return '';\n      if (this.item.excerpt.length <= this.excerptLength) {\n        return this.item.excerpt;\n      }\n      return this.item.excerpt.substring(0, this.excerptLength) + '...';\n    },\n  },\n};\n</script>\n\n<style scoped>\n.feed-item {\n  display: flex;\n  gap: 16px;\n  padding: 16px;\n  border: 1px solid var(--color-border);\n  border-radius: var(--border-radius-large);\n  text-decoration: none;\n  color: inherit;\n  transition: all 0.2s ease;\n  position: relative;\n  min-width: 0;\n  overflow: hidden;\n  box-sizing: border-box;\n}\n\n.feed-item:focus-visible {\n  outline: 2px solid var(--color-primary);\n  outline-offset: 2px;\n}\n\n.feed-item--bg-default {\n  background: var(--color-background-hover);\n}\n\n.feed-item--bg-default:hover {\n  border-color: var(--color-primary);\n  background: var(--color-primary-element-light);\n  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);\n}\n\n.feed-item--bg-transparent {\n  background: transparent;\n}\n\n.feed-item--bg-transparent:hover {\n  background: var(--color-background-hover);\n  border-color: var(--color-primary);\n  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);\n}\n\n.feed-item--bg-white {\n  background: var(--color-main-background);\n}\n\n.feed-item--bg-white:hover {\n  border-color: var(--color-primary);\n  background: var(--color-primary-element-light);\n  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);\n}\n\n.feed-item--bg-dark {\n  background: rgba(255, 255, 255, 0.15);\n  border-color: rgba(255, 255, 255, 0.2);\n}\n\n.feed-item--bg-dark:hover {\n  background: rgba(255, 255, 255, 0.25);\n  border-color: rgba(255, 255, 255, 0.35);\n  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);\n}\n\n.feed-item--bg-dark .feed-item-title {\n  color: white;\n}\n\n.feed-item--bg-dark:hover .feed-item-title {\n  color: white;\n}\n\n.feed-item--bg-dark .feed-item-meta,\n.feed-item--bg-dark .feed-item-excerpt {\n  color: rgba(255, 255, 255, 0.85);\n}\n\n.feed-item--compact {\n  padding: 12px;\n  gap: 12px;\n}\n\n.feed-item--no-image {\n  flex-direction: column;\n}\n\n.feed-item-image {\n  flex-shrink: 0;\n  width: 120px;\n  max-width: 100%;\n  height: 80px;\n  border-radius: var(--border-radius);\n  overflow: hidden;\n  background: var(--color-background-dark);\n}\n\n.feed-item--compact .feed-item-image {\n  width: 80px;\n  height: 60px;\n}\n\n.feed-item-image img {\n  width: 100%;\n  height: 100%;\n  object-fit: cover;\n}\n\n.feed-item-feed-icon {\n  flex-shrink: 0;\n  width: 40px;\n  height: 40px;\n  display: flex;\n  align-items: center;\n  justify-content: center;\n  border-radius: 8px;\n  overflow: hidden;\n  align-self: flex-start;\n  margin-top: 2px;\n  background: var(--color-background-dark);\n}\n\n.feed-item--compact .feed-item-feed-icon {\n  width: 32px;\n  height: 32px;\n  border-radius: 6px;\n}\n\n.feed-item-feed-icon img {\n  width: 100%;\n  height: 100%;\n  object-fit: contain;\n}\n\n.feed-item-fallback {\n  flex-shrink: 0;\n  width: 40px;\n  height: 40px;\n  display: flex;\n  flex-direction: column;\n  align-items: center;\n  justify-content: center;\n  gap: 1px;\n  color: white;\n  border-radius: 8px;\n  align-self: flex-start;\n  margin-top: 2px;\n}\n\n.feed-item--compact .feed-item-fallback {\n  width: 32px;\n  height: 32px;\n  border-radius: 6px;\n}\n\n.feed-item-fallback-icon {\n  opacity: 0.9;\n}\n\n.feed-item-fallback-label {\n  font-size: 7px;\n  font-weight: 700;\n  text-transform: uppercase;\n  letter-spacing: 0.3px;\n  opacity: 0.9;\n  line-height: 1;\n}\n\n.feed-item-content {\n  flex: 1;\n  min-width: 0;\n  display: flex;\n  flex-direction: column;\n  gap: 4px;\n}\n\n.feed-item-title {\n  margin: 0;\n  font-size: 14px;\n  font-weight: 600;\n  color: var(--color-main-text);\n  line-height: 1.3;\n  overflow: hidden;\n  text-overflow: ellipsis;\n  display: -webkit-box;\n  -webkit-line-clamp: 2;\n  -webkit-box-orient: vertical;\n  overflow-wrap: break-word;\n  word-break: break-word;\n}\n\n.feed-item--compact .feed-item-title {\n  font-size: 13px;\n  -webkit-line-clamp: 1;\n}\n\n.feed-item:hover .feed-item-title {\n  color: var(--color-primary);\n}\n\n.feed-item--bg-dark:hover .feed-item-title {\n  color: var(--color-primary-element-text);\n}\n\n.feed-item-meta {\n  display: flex;\n  align-items: center;\n  gap: 12px;\n  font-size: 12px;\n  color: var(--color-text-maxcontrast);\n  flex-wrap: wrap;\n}\n\n.feed-item-date {\n  display: flex;\n  align-items: center;\n  gap: 4px;\n}\n\n.feed-item-source {\n  font-style: italic;\n}\n\n.feed-item-excerpt {\n  margin: 4px 0 0 0;\n  font-size: 13px;\n  color: var(--color-text-maxcontrast);\n  line-height: 1.4;\n  overflow: hidden;\n  text-overflow: ellipsis;\n  display: -webkit-box;\n  -webkit-line-clamp: 2;\n  -webkit-box-orient: vertical;\n}\n\n.feed-item--compact .feed-item-excerpt {\n  display: none;\n}\n\n.feed-item-external-icon {\n  position: absolute;\n  top: 12px;\n  right: 12px;\n  color: var(--color-text-maxcontrast);\n  opacity: 0;\n  transition: opacity 0.2s;\n}\n\n.feed-item:hover .feed-item-external-icon {\n  opacity: 1;\n}\n\n/* Container query: medium width (250-400px) — compact mode */\n@container (max-width: 400px) {\n  .feed-item {\n    padding: 12px;\n    gap: 10px;\n  }\n\n  .feed-item-image {\n    width: 80px;\n    height: 60px;\n  }\n\n  .feed-item-title {\n    font-size: 13px;\n  }\n\n  .feed-item-meta {\n    font-size: 11px;\n    gap: 8px;\n  }\n\n  .feed-item-excerpt {\n    display: none;\n  }\n}\n\n/* Container query: narrow width (<250px) — compact vertical for photos only */\n@container (max-width: 250px) {\n  .feed-item {\n    padding: 10px;\n    gap: 8px;\n  }\n\n  .feed-item-title {\n    font-size: 12px;\n  }\n\n  .feed-item-meta {\n    font-size: 10px;\n  }\n}\n\n@media (max-width: 600px) {\n  .feed-item {\n    flex-direction: column;\n    gap: 12px;\n  }\n\n  .feed-item-image {\n    width: 100%;\n    height: 160px;\n  }\n}\n</style>\n"],"sourceRoot":""}]);
// Exports
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (___CSS_LOADER_EXPORT___);


/***/ },

/***/ "./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/dist/stylePostLoader.js!./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/feed/FeedLayoutGrid.vue?vue&type=style&index=0&id=5b0e091f&scoped=true&lang=css"
/*!********************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/dist/stylePostLoader.js!./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/feed/FeedLayoutGrid.vue?vue&type=style&index=0&id=5b0e091f&scoped=true&lang=css ***!
  \********************************************************************************************************************************************************************************************************************************************************************/
(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _node_modules_css_loader_dist_runtime_sourceMaps_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ../../../node_modules/css-loader/dist/runtime/sourceMaps.js */ "./node_modules/css-loader/dist/runtime/sourceMaps.js");
/* harmony import */ var _node_modules_css_loader_dist_runtime_sourceMaps_js__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_node_modules_css_loader_dist_runtime_sourceMaps_js__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _node_modules_css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ../../../node_modules/css-loader/dist/runtime/api.js */ "./node_modules/css-loader/dist/runtime/api.js");
/* harmony import */ var _node_modules_css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_node_modules_css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_1__);
// Imports


var ___CSS_LOADER_EXPORT___ = _node_modules_css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_1___default()((_node_modules_css_loader_dist_runtime_sourceMaps_js__WEBPACK_IMPORTED_MODULE_0___default()));
// Module
___CSS_LOADER_EXPORT___.push([module.id, `
.feed-layout-grid[data-v-5b0e091f] {
  display: grid;
  gap: 16px;
  min-width: 0;
  overflow: hidden;
  container-type: inline-size;
}
@container (max-width: 500px) {
.feed-layout-grid[data-v-5b0e091f] {
    grid-template-columns: 1fr !important;
}
}
@container (min-width: 501px) and (max-width: 800px) {
.feed-layout-grid[data-v-5b0e091f] {
    grid-template-columns: repeat(2, 1fr) !important;
}
}
@media (max-width: 600px) {
.feed-layout-grid[data-v-5b0e091f] {
    grid-template-columns: 1fr !important;
}
}
`, "",{"version":3,"sources":["webpack://./src/components/feed/FeedLayoutGrid.vue"],"names":[],"mappings":";AAoEA;EACE,aAAa;EACb,SAAS;EACT,YAAY;EACZ,gBAAgB;EAChB,2BAA2B;AAC7B;AAEA;AACE;IACE,qCAAqC;AACvC;AACF;AAEA;AACE;IACE,gDAAgD;AAClD;AACF;AAEA;AACE;IACE,qCAAqC;AACvC;AACF","sourcesContent":["<template>\n  <div class=\"feed-layout-grid\" :style=\"gridStyle\">\n    <FeedItem\n      v-for=\"item in items\"\n      :key=\"item.id\"\n      :item=\"item\"\n      :show-image=\"widget.showImage !== false\"\n      :show-date=\"widget.showDate !== false\"\n      :show-excerpt=\"widget.showExcerpt !== false\"\n      :show-source=\"widget.showSource || false\"\n      :excerpt-length=\"widget.excerptLength || 150\"\n      :open-in-new-tab=\"widget.openInNewTab !== false\"\n      :item-background=\"itemBackgroundMode\"\n      :feed-image=\"feedImage\"\n      :compact=\"true\"\n    />\n  </div>\n</template>\n\n<script>\nimport FeedItem from './FeedItem.vue';\nimport { isDarkBackground as isDarkBg, isLightBackground as isLightBg } from '../../utils/colorUtils.js';\n\nexport default {\n  name: 'FeedLayoutGrid',\n  components: {\n    FeedItem,\n  },\n  props: {\n    items: {\n      type: Array,\n      required: true,\n    },\n    widget: {\n      type: Object,\n      required: true,\n    },\n    feedImage: {\n      type: String,\n      default: null,\n    },\n    rowBackgroundColor: {\n      type: String,\n      default: '',\n    },\n  },\n  computed: {\n    gridStyle() {\n      const columns = Math.max(2, Math.min(Number(this.widget.columns) || 3, 4));\n      return {\n        gridTemplateColumns: `repeat(${columns}, 1fr)`,\n      };\n    },\n    effectiveBackgroundColor() {\n      return this.widget.backgroundColor || this.rowBackgroundColor || '';\n    },\n    itemBackgroundMode() {\n      const containerBg = this.effectiveBackgroundColor;\n      if (!containerBg) return 'default';\n      if (isDarkBg(containerBg)) return 'dark';\n      if (isLightBg(containerBg)) return 'white';\n      return 'default';\n    },\n  },\n};\n</script>\n\n<style scoped>\n.feed-layout-grid {\n  display: grid;\n  gap: 16px;\n  min-width: 0;\n  overflow: hidden;\n  container-type: inline-size;\n}\n\n@container (max-width: 500px) {\n  .feed-layout-grid {\n    grid-template-columns: 1fr !important;\n  }\n}\n\n@container (min-width: 501px) and (max-width: 800px) {\n  .feed-layout-grid {\n    grid-template-columns: repeat(2, 1fr) !important;\n  }\n}\n\n@media (max-width: 600px) {\n  .feed-layout-grid {\n    grid-template-columns: 1fr !important;\n  }\n}\n</style>\n"],"sourceRoot":""}]);
// Exports
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (___CSS_LOADER_EXPORT___);


/***/ },

/***/ "./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/dist/stylePostLoader.js!./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/feed/FeedLayoutList.vue?vue&type=style&index=0&id=cf647b12&scoped=true&lang=css"
/*!********************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/dist/stylePostLoader.js!./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/feed/FeedLayoutList.vue?vue&type=style&index=0&id=cf647b12&scoped=true&lang=css ***!
  \********************************************************************************************************************************************************************************************************************************************************************/
(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _node_modules_css_loader_dist_runtime_sourceMaps_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ../../../node_modules/css-loader/dist/runtime/sourceMaps.js */ "./node_modules/css-loader/dist/runtime/sourceMaps.js");
/* harmony import */ var _node_modules_css_loader_dist_runtime_sourceMaps_js__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_node_modules_css_loader_dist_runtime_sourceMaps_js__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _node_modules_css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ../../../node_modules/css-loader/dist/runtime/api.js */ "./node_modules/css-loader/dist/runtime/api.js");
/* harmony import */ var _node_modules_css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_node_modules_css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_1__);
// Imports


var ___CSS_LOADER_EXPORT___ = _node_modules_css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_1___default()((_node_modules_css_loader_dist_runtime_sourceMaps_js__WEBPACK_IMPORTED_MODULE_0___default()));
// Module
___CSS_LOADER_EXPORT___.push([module.id, `
.feed-layout-list[data-v-cf647b12] {
  display: flex;
  flex-direction: column;
  gap: 12px;
  min-width: 0;
  overflow: hidden;
  container-type: inline-size;
}
`, "",{"version":3,"sources":["webpack://./src/components/feed/FeedLayoutList.vue"],"names":[],"mappings":";AA6DA;EACE,aAAa;EACb,sBAAsB;EACtB,SAAS;EACT,YAAY;EACZ,gBAAgB;EAChB,2BAA2B;AAC7B","sourcesContent":["<template>\n  <div class=\"feed-layout-list\">\n    <FeedItem\n      v-for=\"item in items\"\n      :key=\"item.id\"\n      :item=\"item\"\n      :show-image=\"widget.showImage !== false\"\n      :show-date=\"widget.showDate !== false\"\n      :show-excerpt=\"widget.showExcerpt !== false\"\n      :show-source=\"widget.showSource || false\"\n      :excerpt-length=\"widget.excerptLength || 150\"\n      :open-in-new-tab=\"widget.openInNewTab !== false\"\n      :item-background=\"itemBackgroundMode\"\n      :feed-image=\"feedImage\"\n    />\n  </div>\n</template>\n\n<script>\nimport FeedItem from './FeedItem.vue';\nimport { isDarkBackground as isDarkBg, isLightBackground as isLightBg } from '../../utils/colorUtils.js';\n\nexport default {\n  name: 'FeedLayoutList',\n  components: {\n    FeedItem,\n  },\n  props: {\n    items: {\n      type: Array,\n      required: true,\n    },\n    widget: {\n      type: Object,\n      required: true,\n    },\n    feedImage: {\n      type: String,\n      default: null,\n    },\n    rowBackgroundColor: {\n      type: String,\n      default: '',\n    },\n  },\n  computed: {\n    effectiveBackgroundColor() {\n      return this.widget.backgroundColor || this.rowBackgroundColor || '';\n    },\n    itemBackgroundMode() {\n      const containerBg = this.effectiveBackgroundColor;\n      if (!containerBg) return 'default';\n      if (isDarkBg(containerBg)) return 'dark';\n      if (isLightBg(containerBg)) return 'white';\n      return 'default';\n    },\n  },\n};\n</script>\n\n<style scoped>\n.feed-layout-list {\n  display: flex;\n  flex-direction: column;\n  gap: 12px;\n  min-width: 0;\n  overflow: hidden;\n  container-type: inline-size;\n}\n</style>\n"],"sourceRoot":""}]);
// Exports
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (___CSS_LOADER_EXPORT___);


/***/ },

/***/ "./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/dist/stylePostLoader.js!./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/FeedWidget.vue?vue&type=style&index=0&id=642986b2&scoped=true&lang=css"
/*!***************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/dist/stylePostLoader.js!./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/FeedWidget.vue?vue&type=style&index=0&id=642986b2&scoped=true&lang=css ***!
  \***************************************************************************************************************************************************************************************************************************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
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
/* harmony import */ var _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_dist_stylePostLoader_js_node_modules_vue_loader_dist_index_js_ruleSet_1_rules_5_use_0_FeedWidget_vue_vue_type_style_index_0_id_642986b2_scoped_true_lang_css__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! !!../../node_modules/css-loader/dist/cjs.js!../../node_modules/vue-loader/dist/stylePostLoader.js!../../node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./FeedWidget.vue?vue&type=style&index=0&id=642986b2&scoped=true&lang=css */ "./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/dist/stylePostLoader.js!./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/FeedWidget.vue?vue&type=style&index=0&id=642986b2&scoped=true&lang=css");

      
      
      
      
      
      
      
      
      

var options = {};

options.styleTagTransform = (_node_modules_style_loader_dist_runtime_styleTagTransform_js__WEBPACK_IMPORTED_MODULE_5___default());
options.setAttributes = (_node_modules_style_loader_dist_runtime_setAttributesWithoutAttributes_js__WEBPACK_IMPORTED_MODULE_3___default());

      options.insert = _node_modules_style_loader_dist_runtime_insertBySelector_js__WEBPACK_IMPORTED_MODULE_2___default().bind(null, "head");
    
options.domAPI = (_node_modules_style_loader_dist_runtime_styleDomAPI_js__WEBPACK_IMPORTED_MODULE_1___default());
options.insertStyleElement = (_node_modules_style_loader_dist_runtime_insertStyleElement_js__WEBPACK_IMPORTED_MODULE_4___default());

var update = _node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0___default()(_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_dist_stylePostLoader_js_node_modules_vue_loader_dist_index_js_ruleSet_1_rules_5_use_0_FeedWidget_vue_vue_type_style_index_0_id_642986b2_scoped_true_lang_css__WEBPACK_IMPORTED_MODULE_6__["default"], options);




       /* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_dist_stylePostLoader_js_node_modules_vue_loader_dist_index_js_ruleSet_1_rules_5_use_0_FeedWidget_vue_vue_type_style_index_0_id_642986b2_scoped_true_lang_css__WEBPACK_IMPORTED_MODULE_6__["default"] && _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_dist_stylePostLoader_js_node_modules_vue_loader_dist_index_js_ruleSet_1_rules_5_use_0_FeedWidget_vue_vue_type_style_index_0_id_642986b2_scoped_true_lang_css__WEBPACK_IMPORTED_MODULE_6__["default"].locals ? _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_dist_stylePostLoader_js_node_modules_vue_loader_dist_index_js_ruleSet_1_rules_5_use_0_FeedWidget_vue_vue_type_style_index_0_id_642986b2_scoped_true_lang_css__WEBPACK_IMPORTED_MODULE_6__["default"].locals : undefined);


/***/ },

/***/ "./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/dist/stylePostLoader.js!./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/InlineTextEditor.vue?vue&type=style&index=0&id=0a2d6723&scoped=true&lang=css"
/*!*********************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/dist/stylePostLoader.js!./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/InlineTextEditor.vue?vue&type=style&index=0&id=0a2d6723&scoped=true&lang=css ***!
  \*********************************************************************************************************************************************************************************************************************************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
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
/* harmony import */ var _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_dist_stylePostLoader_js_node_modules_vue_loader_dist_index_js_ruleSet_1_rules_5_use_0_InlineTextEditor_vue_vue_type_style_index_0_id_0a2d6723_scoped_true_lang_css__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! !!../../node_modules/css-loader/dist/cjs.js!../../node_modules/vue-loader/dist/stylePostLoader.js!../../node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./InlineTextEditor.vue?vue&type=style&index=0&id=0a2d6723&scoped=true&lang=css */ "./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/dist/stylePostLoader.js!./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/InlineTextEditor.vue?vue&type=style&index=0&id=0a2d6723&scoped=true&lang=css");

      
      
      
      
      
      
      
      
      

var options = {};

options.styleTagTransform = (_node_modules_style_loader_dist_runtime_styleTagTransform_js__WEBPACK_IMPORTED_MODULE_5___default());
options.setAttributes = (_node_modules_style_loader_dist_runtime_setAttributesWithoutAttributes_js__WEBPACK_IMPORTED_MODULE_3___default());

      options.insert = _node_modules_style_loader_dist_runtime_insertBySelector_js__WEBPACK_IMPORTED_MODULE_2___default().bind(null, "head");
    
options.domAPI = (_node_modules_style_loader_dist_runtime_styleDomAPI_js__WEBPACK_IMPORTED_MODULE_1___default());
options.insertStyleElement = (_node_modules_style_loader_dist_runtime_insertStyleElement_js__WEBPACK_IMPORTED_MODULE_4___default());

var update = _node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0___default()(_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_dist_stylePostLoader_js_node_modules_vue_loader_dist_index_js_ruleSet_1_rules_5_use_0_InlineTextEditor_vue_vue_type_style_index_0_id_0a2d6723_scoped_true_lang_css__WEBPACK_IMPORTED_MODULE_6__["default"], options);




       /* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_dist_stylePostLoader_js_node_modules_vue_loader_dist_index_js_ruleSet_1_rules_5_use_0_InlineTextEditor_vue_vue_type_style_index_0_id_0a2d6723_scoped_true_lang_css__WEBPACK_IMPORTED_MODULE_6__["default"] && _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_dist_stylePostLoader_js_node_modules_vue_loader_dist_index_js_ruleSet_1_rules_5_use_0_InlineTextEditor_vue_vue_type_style_index_0_id_0a2d6723_scoped_true_lang_css__WEBPACK_IMPORTED_MODULE_6__["default"].locals ? _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_dist_stylePostLoader_js_node_modules_vue_loader_dist_index_js_ruleSet_1_rules_5_use_0_InlineTextEditor_vue_vue_type_style_index_0_id_0a2d6723_scoped_true_lang_css__WEBPACK_IMPORTED_MODULE_6__["default"].locals : undefined);


/***/ },

/***/ "./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/dist/stylePostLoader.js!./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/PageTreeSelect.vue?vue&type=style&index=0&id=5efccfd9&scoped=true&lang=css"
/*!*******************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/dist/stylePostLoader.js!./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/PageTreeSelect.vue?vue&type=style&index=0&id=5efccfd9&scoped=true&lang=css ***!
  \*******************************************************************************************************************************************************************************************************************************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
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
/* harmony import */ var _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_dist_stylePostLoader_js_node_modules_vue_loader_dist_index_js_ruleSet_1_rules_5_use_0_PageTreeSelect_vue_vue_type_style_index_0_id_5efccfd9_scoped_true_lang_css__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! !!../../node_modules/css-loader/dist/cjs.js!../../node_modules/vue-loader/dist/stylePostLoader.js!../../node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./PageTreeSelect.vue?vue&type=style&index=0&id=5efccfd9&scoped=true&lang=css */ "./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/dist/stylePostLoader.js!./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/PageTreeSelect.vue?vue&type=style&index=0&id=5efccfd9&scoped=true&lang=css");

      
      
      
      
      
      
      
      
      

var options = {};

options.styleTagTransform = (_node_modules_style_loader_dist_runtime_styleTagTransform_js__WEBPACK_IMPORTED_MODULE_5___default());
options.setAttributes = (_node_modules_style_loader_dist_runtime_setAttributesWithoutAttributes_js__WEBPACK_IMPORTED_MODULE_3___default());

      options.insert = _node_modules_style_loader_dist_runtime_insertBySelector_js__WEBPACK_IMPORTED_MODULE_2___default().bind(null, "head");
    
options.domAPI = (_node_modules_style_loader_dist_runtime_styleDomAPI_js__WEBPACK_IMPORTED_MODULE_1___default());
options.insertStyleElement = (_node_modules_style_loader_dist_runtime_insertStyleElement_js__WEBPACK_IMPORTED_MODULE_4___default());

var update = _node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0___default()(_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_dist_stylePostLoader_js_node_modules_vue_loader_dist_index_js_ruleSet_1_rules_5_use_0_PageTreeSelect_vue_vue_type_style_index_0_id_5efccfd9_scoped_true_lang_css__WEBPACK_IMPORTED_MODULE_6__["default"], options);




       /* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_dist_stylePostLoader_js_node_modules_vue_loader_dist_index_js_ruleSet_1_rules_5_use_0_PageTreeSelect_vue_vue_type_style_index_0_id_5efccfd9_scoped_true_lang_css__WEBPACK_IMPORTED_MODULE_6__["default"] && _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_dist_stylePostLoader_js_node_modules_vue_loader_dist_index_js_ruleSet_1_rules_5_use_0_PageTreeSelect_vue_vue_type_style_index_0_id_5efccfd9_scoped_true_lang_css__WEBPACK_IMPORTED_MODULE_6__["default"].locals ? _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_dist_stylePostLoader_js_node_modules_vue_loader_dist_index_js_ruleSet_1_rules_5_use_0_PageTreeSelect_vue_vue_type_style_index_0_id_5efccfd9_scoped_true_lang_css__WEBPACK_IMPORTED_MODULE_6__["default"].locals : undefined);


/***/ },

/***/ "./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/dist/stylePostLoader.js!./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/PageTreeSelectItem.vue?vue&type=style&index=0&id=621c650c&scoped=true&lang=css"
/*!***********************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/dist/stylePostLoader.js!./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/PageTreeSelectItem.vue?vue&type=style&index=0&id=621c650c&scoped=true&lang=css ***!
  \***********************************************************************************************************************************************************************************************************************************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
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
/* harmony import */ var _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_dist_stylePostLoader_js_node_modules_vue_loader_dist_index_js_ruleSet_1_rules_5_use_0_PageTreeSelectItem_vue_vue_type_style_index_0_id_621c650c_scoped_true_lang_css__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! !!../../node_modules/css-loader/dist/cjs.js!../../node_modules/vue-loader/dist/stylePostLoader.js!../../node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./PageTreeSelectItem.vue?vue&type=style&index=0&id=621c650c&scoped=true&lang=css */ "./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/dist/stylePostLoader.js!./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/PageTreeSelectItem.vue?vue&type=style&index=0&id=621c650c&scoped=true&lang=css");

      
      
      
      
      
      
      
      
      

var options = {};

options.styleTagTransform = (_node_modules_style_loader_dist_runtime_styleTagTransform_js__WEBPACK_IMPORTED_MODULE_5___default());
options.setAttributes = (_node_modules_style_loader_dist_runtime_setAttributesWithoutAttributes_js__WEBPACK_IMPORTED_MODULE_3___default());

      options.insert = _node_modules_style_loader_dist_runtime_insertBySelector_js__WEBPACK_IMPORTED_MODULE_2___default().bind(null, "head");
    
options.domAPI = (_node_modules_style_loader_dist_runtime_styleDomAPI_js__WEBPACK_IMPORTED_MODULE_1___default());
options.insertStyleElement = (_node_modules_style_loader_dist_runtime_insertStyleElement_js__WEBPACK_IMPORTED_MODULE_4___default());

var update = _node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0___default()(_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_dist_stylePostLoader_js_node_modules_vue_loader_dist_index_js_ruleSet_1_rules_5_use_0_PageTreeSelectItem_vue_vue_type_style_index_0_id_621c650c_scoped_true_lang_css__WEBPACK_IMPORTED_MODULE_6__["default"], options);




       /* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_dist_stylePostLoader_js_node_modules_vue_loader_dist_index_js_ruleSet_1_rules_5_use_0_PageTreeSelectItem_vue_vue_type_style_index_0_id_621c650c_scoped_true_lang_css__WEBPACK_IMPORTED_MODULE_6__["default"] && _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_dist_stylePostLoader_js_node_modules_vue_loader_dist_index_js_ruleSet_1_rules_5_use_0_PageTreeSelectItem_vue_vue_type_style_index_0_id_621c650c_scoped_true_lang_css__WEBPACK_IMPORTED_MODULE_6__["default"].locals ? _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_dist_stylePostLoader_js_node_modules_vue_loader_dist_index_js_ruleSet_1_rules_5_use_0_PageTreeSelectItem_vue_vue_type_style_index_0_id_621c650c_scoped_true_lang_css__WEBPACK_IMPORTED_MODULE_6__["default"].locals : undefined);


/***/ },

/***/ "./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/dist/stylePostLoader.js!./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/feed/FeedItem.vue?vue&type=style&index=0&id=82e10b3c&scoped=true&lang=css"
/*!******************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/dist/stylePostLoader.js!./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/feed/FeedItem.vue?vue&type=style&index=0&id=82e10b3c&scoped=true&lang=css ***!
  \******************************************************************************************************************************************************************************************************************************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! !../../../node_modules/style-loader/dist/runtime/injectStylesIntoStyleTag.js */ "./node_modules/style-loader/dist/runtime/injectStylesIntoStyleTag.js");
/* harmony import */ var _node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _node_modules_style_loader_dist_runtime_styleDomAPI_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! !../../../node_modules/style-loader/dist/runtime/styleDomAPI.js */ "./node_modules/style-loader/dist/runtime/styleDomAPI.js");
/* harmony import */ var _node_modules_style_loader_dist_runtime_styleDomAPI_js__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_node_modules_style_loader_dist_runtime_styleDomAPI_js__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _node_modules_style_loader_dist_runtime_insertBySelector_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! !../../../node_modules/style-loader/dist/runtime/insertBySelector.js */ "./node_modules/style-loader/dist/runtime/insertBySelector.js");
/* harmony import */ var _node_modules_style_loader_dist_runtime_insertBySelector_js__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(_node_modules_style_loader_dist_runtime_insertBySelector_js__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var _node_modules_style_loader_dist_runtime_setAttributesWithoutAttributes_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! !../../../node_modules/style-loader/dist/runtime/setAttributesWithoutAttributes.js */ "./node_modules/style-loader/dist/runtime/setAttributesWithoutAttributes.js");
/* harmony import */ var _node_modules_style_loader_dist_runtime_setAttributesWithoutAttributes_js__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(_node_modules_style_loader_dist_runtime_setAttributesWithoutAttributes_js__WEBPACK_IMPORTED_MODULE_3__);
/* harmony import */ var _node_modules_style_loader_dist_runtime_insertStyleElement_js__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! !../../../node_modules/style-loader/dist/runtime/insertStyleElement.js */ "./node_modules/style-loader/dist/runtime/insertStyleElement.js");
/* harmony import */ var _node_modules_style_loader_dist_runtime_insertStyleElement_js__WEBPACK_IMPORTED_MODULE_4___default = /*#__PURE__*/__webpack_require__.n(_node_modules_style_loader_dist_runtime_insertStyleElement_js__WEBPACK_IMPORTED_MODULE_4__);
/* harmony import */ var _node_modules_style_loader_dist_runtime_styleTagTransform_js__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! !../../../node_modules/style-loader/dist/runtime/styleTagTransform.js */ "./node_modules/style-loader/dist/runtime/styleTagTransform.js");
/* harmony import */ var _node_modules_style_loader_dist_runtime_styleTagTransform_js__WEBPACK_IMPORTED_MODULE_5___default = /*#__PURE__*/__webpack_require__.n(_node_modules_style_loader_dist_runtime_styleTagTransform_js__WEBPACK_IMPORTED_MODULE_5__);
/* harmony import */ var _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_dist_stylePostLoader_js_node_modules_vue_loader_dist_index_js_ruleSet_1_rules_5_use_0_FeedItem_vue_vue_type_style_index_0_id_82e10b3c_scoped_true_lang_css__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! !!../../../node_modules/css-loader/dist/cjs.js!../../../node_modules/vue-loader/dist/stylePostLoader.js!../../../node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./FeedItem.vue?vue&type=style&index=0&id=82e10b3c&scoped=true&lang=css */ "./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/dist/stylePostLoader.js!./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/feed/FeedItem.vue?vue&type=style&index=0&id=82e10b3c&scoped=true&lang=css");

      
      
      
      
      
      
      
      
      

var options = {};

options.styleTagTransform = (_node_modules_style_loader_dist_runtime_styleTagTransform_js__WEBPACK_IMPORTED_MODULE_5___default());
options.setAttributes = (_node_modules_style_loader_dist_runtime_setAttributesWithoutAttributes_js__WEBPACK_IMPORTED_MODULE_3___default());

      options.insert = _node_modules_style_loader_dist_runtime_insertBySelector_js__WEBPACK_IMPORTED_MODULE_2___default().bind(null, "head");
    
options.domAPI = (_node_modules_style_loader_dist_runtime_styleDomAPI_js__WEBPACK_IMPORTED_MODULE_1___default());
options.insertStyleElement = (_node_modules_style_loader_dist_runtime_insertStyleElement_js__WEBPACK_IMPORTED_MODULE_4___default());

var update = _node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0___default()(_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_dist_stylePostLoader_js_node_modules_vue_loader_dist_index_js_ruleSet_1_rules_5_use_0_FeedItem_vue_vue_type_style_index_0_id_82e10b3c_scoped_true_lang_css__WEBPACK_IMPORTED_MODULE_6__["default"], options);




       /* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_dist_stylePostLoader_js_node_modules_vue_loader_dist_index_js_ruleSet_1_rules_5_use_0_FeedItem_vue_vue_type_style_index_0_id_82e10b3c_scoped_true_lang_css__WEBPACK_IMPORTED_MODULE_6__["default"] && _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_dist_stylePostLoader_js_node_modules_vue_loader_dist_index_js_ruleSet_1_rules_5_use_0_FeedItem_vue_vue_type_style_index_0_id_82e10b3c_scoped_true_lang_css__WEBPACK_IMPORTED_MODULE_6__["default"].locals ? _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_dist_stylePostLoader_js_node_modules_vue_loader_dist_index_js_ruleSet_1_rules_5_use_0_FeedItem_vue_vue_type_style_index_0_id_82e10b3c_scoped_true_lang_css__WEBPACK_IMPORTED_MODULE_6__["default"].locals : undefined);


/***/ },

/***/ "./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/dist/stylePostLoader.js!./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/feed/FeedLayoutGrid.vue?vue&type=style&index=0&id=5b0e091f&scoped=true&lang=css"
/*!************************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/dist/stylePostLoader.js!./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/feed/FeedLayoutGrid.vue?vue&type=style&index=0&id=5b0e091f&scoped=true&lang=css ***!
  \************************************************************************************************************************************************************************************************************************************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! !../../../node_modules/style-loader/dist/runtime/injectStylesIntoStyleTag.js */ "./node_modules/style-loader/dist/runtime/injectStylesIntoStyleTag.js");
/* harmony import */ var _node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _node_modules_style_loader_dist_runtime_styleDomAPI_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! !../../../node_modules/style-loader/dist/runtime/styleDomAPI.js */ "./node_modules/style-loader/dist/runtime/styleDomAPI.js");
/* harmony import */ var _node_modules_style_loader_dist_runtime_styleDomAPI_js__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_node_modules_style_loader_dist_runtime_styleDomAPI_js__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _node_modules_style_loader_dist_runtime_insertBySelector_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! !../../../node_modules/style-loader/dist/runtime/insertBySelector.js */ "./node_modules/style-loader/dist/runtime/insertBySelector.js");
/* harmony import */ var _node_modules_style_loader_dist_runtime_insertBySelector_js__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(_node_modules_style_loader_dist_runtime_insertBySelector_js__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var _node_modules_style_loader_dist_runtime_setAttributesWithoutAttributes_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! !../../../node_modules/style-loader/dist/runtime/setAttributesWithoutAttributes.js */ "./node_modules/style-loader/dist/runtime/setAttributesWithoutAttributes.js");
/* harmony import */ var _node_modules_style_loader_dist_runtime_setAttributesWithoutAttributes_js__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(_node_modules_style_loader_dist_runtime_setAttributesWithoutAttributes_js__WEBPACK_IMPORTED_MODULE_3__);
/* harmony import */ var _node_modules_style_loader_dist_runtime_insertStyleElement_js__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! !../../../node_modules/style-loader/dist/runtime/insertStyleElement.js */ "./node_modules/style-loader/dist/runtime/insertStyleElement.js");
/* harmony import */ var _node_modules_style_loader_dist_runtime_insertStyleElement_js__WEBPACK_IMPORTED_MODULE_4___default = /*#__PURE__*/__webpack_require__.n(_node_modules_style_loader_dist_runtime_insertStyleElement_js__WEBPACK_IMPORTED_MODULE_4__);
/* harmony import */ var _node_modules_style_loader_dist_runtime_styleTagTransform_js__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! !../../../node_modules/style-loader/dist/runtime/styleTagTransform.js */ "./node_modules/style-loader/dist/runtime/styleTagTransform.js");
/* harmony import */ var _node_modules_style_loader_dist_runtime_styleTagTransform_js__WEBPACK_IMPORTED_MODULE_5___default = /*#__PURE__*/__webpack_require__.n(_node_modules_style_loader_dist_runtime_styleTagTransform_js__WEBPACK_IMPORTED_MODULE_5__);
/* harmony import */ var _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_dist_stylePostLoader_js_node_modules_vue_loader_dist_index_js_ruleSet_1_rules_5_use_0_FeedLayoutGrid_vue_vue_type_style_index_0_id_5b0e091f_scoped_true_lang_css__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! !!../../../node_modules/css-loader/dist/cjs.js!../../../node_modules/vue-loader/dist/stylePostLoader.js!../../../node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./FeedLayoutGrid.vue?vue&type=style&index=0&id=5b0e091f&scoped=true&lang=css */ "./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/dist/stylePostLoader.js!./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/feed/FeedLayoutGrid.vue?vue&type=style&index=0&id=5b0e091f&scoped=true&lang=css");

      
      
      
      
      
      
      
      
      

var options = {};

options.styleTagTransform = (_node_modules_style_loader_dist_runtime_styleTagTransform_js__WEBPACK_IMPORTED_MODULE_5___default());
options.setAttributes = (_node_modules_style_loader_dist_runtime_setAttributesWithoutAttributes_js__WEBPACK_IMPORTED_MODULE_3___default());

      options.insert = _node_modules_style_loader_dist_runtime_insertBySelector_js__WEBPACK_IMPORTED_MODULE_2___default().bind(null, "head");
    
options.domAPI = (_node_modules_style_loader_dist_runtime_styleDomAPI_js__WEBPACK_IMPORTED_MODULE_1___default());
options.insertStyleElement = (_node_modules_style_loader_dist_runtime_insertStyleElement_js__WEBPACK_IMPORTED_MODULE_4___default());

var update = _node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0___default()(_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_dist_stylePostLoader_js_node_modules_vue_loader_dist_index_js_ruleSet_1_rules_5_use_0_FeedLayoutGrid_vue_vue_type_style_index_0_id_5b0e091f_scoped_true_lang_css__WEBPACK_IMPORTED_MODULE_6__["default"], options);




       /* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_dist_stylePostLoader_js_node_modules_vue_loader_dist_index_js_ruleSet_1_rules_5_use_0_FeedLayoutGrid_vue_vue_type_style_index_0_id_5b0e091f_scoped_true_lang_css__WEBPACK_IMPORTED_MODULE_6__["default"] && _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_dist_stylePostLoader_js_node_modules_vue_loader_dist_index_js_ruleSet_1_rules_5_use_0_FeedLayoutGrid_vue_vue_type_style_index_0_id_5b0e091f_scoped_true_lang_css__WEBPACK_IMPORTED_MODULE_6__["default"].locals ? _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_dist_stylePostLoader_js_node_modules_vue_loader_dist_index_js_ruleSet_1_rules_5_use_0_FeedLayoutGrid_vue_vue_type_style_index_0_id_5b0e091f_scoped_true_lang_css__WEBPACK_IMPORTED_MODULE_6__["default"].locals : undefined);


/***/ },

/***/ "./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/dist/stylePostLoader.js!./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/feed/FeedLayoutList.vue?vue&type=style&index=0&id=cf647b12&scoped=true&lang=css"
/*!************************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/dist/stylePostLoader.js!./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/feed/FeedLayoutList.vue?vue&type=style&index=0&id=cf647b12&scoped=true&lang=css ***!
  \************************************************************************************************************************************************************************************************************************************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! !../../../node_modules/style-loader/dist/runtime/injectStylesIntoStyleTag.js */ "./node_modules/style-loader/dist/runtime/injectStylesIntoStyleTag.js");
/* harmony import */ var _node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _node_modules_style_loader_dist_runtime_styleDomAPI_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! !../../../node_modules/style-loader/dist/runtime/styleDomAPI.js */ "./node_modules/style-loader/dist/runtime/styleDomAPI.js");
/* harmony import */ var _node_modules_style_loader_dist_runtime_styleDomAPI_js__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_node_modules_style_loader_dist_runtime_styleDomAPI_js__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _node_modules_style_loader_dist_runtime_insertBySelector_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! !../../../node_modules/style-loader/dist/runtime/insertBySelector.js */ "./node_modules/style-loader/dist/runtime/insertBySelector.js");
/* harmony import */ var _node_modules_style_loader_dist_runtime_insertBySelector_js__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(_node_modules_style_loader_dist_runtime_insertBySelector_js__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var _node_modules_style_loader_dist_runtime_setAttributesWithoutAttributes_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! !../../../node_modules/style-loader/dist/runtime/setAttributesWithoutAttributes.js */ "./node_modules/style-loader/dist/runtime/setAttributesWithoutAttributes.js");
/* harmony import */ var _node_modules_style_loader_dist_runtime_setAttributesWithoutAttributes_js__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(_node_modules_style_loader_dist_runtime_setAttributesWithoutAttributes_js__WEBPACK_IMPORTED_MODULE_3__);
/* harmony import */ var _node_modules_style_loader_dist_runtime_insertStyleElement_js__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! !../../../node_modules/style-loader/dist/runtime/insertStyleElement.js */ "./node_modules/style-loader/dist/runtime/insertStyleElement.js");
/* harmony import */ var _node_modules_style_loader_dist_runtime_insertStyleElement_js__WEBPACK_IMPORTED_MODULE_4___default = /*#__PURE__*/__webpack_require__.n(_node_modules_style_loader_dist_runtime_insertStyleElement_js__WEBPACK_IMPORTED_MODULE_4__);
/* harmony import */ var _node_modules_style_loader_dist_runtime_styleTagTransform_js__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! !../../../node_modules/style-loader/dist/runtime/styleTagTransform.js */ "./node_modules/style-loader/dist/runtime/styleTagTransform.js");
/* harmony import */ var _node_modules_style_loader_dist_runtime_styleTagTransform_js__WEBPACK_IMPORTED_MODULE_5___default = /*#__PURE__*/__webpack_require__.n(_node_modules_style_loader_dist_runtime_styleTagTransform_js__WEBPACK_IMPORTED_MODULE_5__);
/* harmony import */ var _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_dist_stylePostLoader_js_node_modules_vue_loader_dist_index_js_ruleSet_1_rules_5_use_0_FeedLayoutList_vue_vue_type_style_index_0_id_cf647b12_scoped_true_lang_css__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! !!../../../node_modules/css-loader/dist/cjs.js!../../../node_modules/vue-loader/dist/stylePostLoader.js!../../../node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./FeedLayoutList.vue?vue&type=style&index=0&id=cf647b12&scoped=true&lang=css */ "./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/dist/stylePostLoader.js!./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/feed/FeedLayoutList.vue?vue&type=style&index=0&id=cf647b12&scoped=true&lang=css");

      
      
      
      
      
      
      
      
      

var options = {};

options.styleTagTransform = (_node_modules_style_loader_dist_runtime_styleTagTransform_js__WEBPACK_IMPORTED_MODULE_5___default());
options.setAttributes = (_node_modules_style_loader_dist_runtime_setAttributesWithoutAttributes_js__WEBPACK_IMPORTED_MODULE_3___default());

      options.insert = _node_modules_style_loader_dist_runtime_insertBySelector_js__WEBPACK_IMPORTED_MODULE_2___default().bind(null, "head");
    
options.domAPI = (_node_modules_style_loader_dist_runtime_styleDomAPI_js__WEBPACK_IMPORTED_MODULE_1___default());
options.insertStyleElement = (_node_modules_style_loader_dist_runtime_insertStyleElement_js__WEBPACK_IMPORTED_MODULE_4___default());

var update = _node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0___default()(_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_dist_stylePostLoader_js_node_modules_vue_loader_dist_index_js_ruleSet_1_rules_5_use_0_FeedLayoutList_vue_vue_type_style_index_0_id_cf647b12_scoped_true_lang_css__WEBPACK_IMPORTED_MODULE_6__["default"], options);




       /* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_dist_stylePostLoader_js_node_modules_vue_loader_dist_index_js_ruleSet_1_rules_5_use_0_FeedLayoutList_vue_vue_type_style_index_0_id_cf647b12_scoped_true_lang_css__WEBPACK_IMPORTED_MODULE_6__["default"] && _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_dist_stylePostLoader_js_node_modules_vue_loader_dist_index_js_ruleSet_1_rules_5_use_0_FeedLayoutList_vue_vue_type_style_index_0_id_cf647b12_scoped_true_lang_css__WEBPACK_IMPORTED_MODULE_6__["default"].locals ? _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_dist_stylePostLoader_js_node_modules_vue_loader_dist_index_js_ruleSet_1_rules_5_use_0_FeedLayoutList_vue_vue_type_style_index_0_id_cf647b12_scoped_true_lang_css__WEBPACK_IMPORTED_MODULE_6__["default"].locals : undefined);


/***/ },

/***/ "./src/components/FeedWidget.vue"
/*!***************************************!*\
  !*** ./src/components/FeedWidget.vue ***!
  \***************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _FeedWidget_vue_vue_type_template_id_642986b2_scoped_true__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./FeedWidget.vue?vue&type=template&id=642986b2&scoped=true */ "./src/components/FeedWidget.vue?vue&type=template&id=642986b2&scoped=true");
/* harmony import */ var _FeedWidget_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./FeedWidget.vue?vue&type=script&lang=js */ "./src/components/FeedWidget.vue?vue&type=script&lang=js");
/* harmony import */ var _FeedWidget_vue_vue_type_style_index_0_id_642986b2_scoped_true_lang_css__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./FeedWidget.vue?vue&type=style&index=0&id=642986b2&scoped=true&lang=css */ "./src/components/FeedWidget.vue?vue&type=style&index=0&id=642986b2&scoped=true&lang=css");
/* harmony import */ var _node_modules_vue_loader_dist_exportHelper_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ../../node_modules/vue-loader/dist/exportHelper.js */ "./node_modules/vue-loader/dist/exportHelper.js");




;


const __exports__ = /*#__PURE__*/(0,_node_modules_vue_loader_dist_exportHelper_js__WEBPACK_IMPORTED_MODULE_3__["default"])(_FeedWidget_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_1__["default"], [['render',_FeedWidget_vue_vue_type_template_id_642986b2_scoped_true__WEBPACK_IMPORTED_MODULE_0__.render],['__scopeId',"data-v-642986b2"],['__file',"src/components/FeedWidget.vue"]])
/* hot reload */
if (false) // removed by dead control flow
{}


/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (__exports__);

/***/ },

/***/ "./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/FeedWidget.vue?vue&type=script&lang=js"
/*!***********************************************************************************************************************************!*\
  !*** ./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/FeedWidget.vue?vue&type=script&lang=js ***!
  \***********************************************************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _nextcloud_axios__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @nextcloud/axios */ "./node_modules/@nextcloud/axios/dist/index.js");
/* harmony import */ var _nextcloud_router__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @nextcloud/router */ "./node_modules/@nextcloud/router/dist/index.mjs");
/* harmony import */ var _nextcloud_vue__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @nextcloud/vue */ "./node_modules/@nextcloud/vue/dist/index.mjs");
/* harmony import */ var vue_material_design_icons_AlertCircle_vue__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! vue-material-design-icons/AlertCircle.vue */ "./node_modules/vue-material-design-icons/AlertCircle.vue");
/* harmony import */ var vue_material_design_icons_RssBox_vue__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! vue-material-design-icons/RssBox.vue */ "./node_modules/vue-material-design-icons/RssBox.vue");
/* harmony import */ var _feed_FeedLayoutList_vue__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! ./feed/FeedLayoutList.vue */ "./src/components/feed/FeedLayoutList.vue");
/* harmony import */ var _feed_FeedLayoutGrid_vue__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! ./feed/FeedLayoutGrid.vue */ "./src/components/feed/FeedLayoutGrid.vue");









/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = ({
  name: 'FeedWidget',
  components: {
    NcLoadingIcon: _nextcloud_vue__WEBPACK_IMPORTED_MODULE_2__.NcLoadingIcon,
    AlertCircle: vue_material_design_icons_AlertCircle_vue__WEBPACK_IMPORTED_MODULE_3__["default"],
    RssBox: vue_material_design_icons_RssBox_vue__WEBPACK_IMPORTED_MODULE_4__["default"],
    FeedLayoutList: _feed_FeedLayoutList_vue__WEBPACK_IMPORTED_MODULE_5__["default"],
    FeedLayoutGrid: _feed_FeedLayoutGrid_vue__WEBPACK_IMPORTED_MODULE_6__["default"],
  },
  props: {
    widget: {
      type: Object,
      required: true,
    },
    shareToken: {
      type: String,
      default: '',
    },
    pageId: {
      type: String,
      default: '',
    },
    rowBackgroundColor: {
      type: String,
      default: '',
    },
  },
  data() {
    return {
      items: [],
      feedImage: null,
      loading: true,
      error: null,
    };
  },
  computed: {
    layoutComponent() {
      const layouts = {
        list: _feed_FeedLayoutList_vue__WEBPACK_IMPORTED_MODULE_5__["default"],
        grid: _feed_FeedLayoutGrid_vue__WEBPACK_IMPORTED_MODULE_6__["default"],
      };
      return layouts[this.widget.layout] || _feed_FeedLayoutList_vue__WEBPACK_IMPORTED_MODULE_5__["default"];
    },
  },
  watch: {
    widget: {
      handler() {
        clearTimeout(this._debounceTimer);
        this._debounceTimer = setTimeout(() => this.fetchFeed(), 300);
      },
      deep: true,
    },
  },
  mounted() {
    if (typeof requestIdleCallback === 'function') {
      requestIdleCallback(() => this.fetchFeed());
    } else {
      this.fetchFeed();
    }
    // Auto-refresh every 15 minutes (matches backend cache TTL)
    this._refreshInterval = setInterval(() => this.fetchFeed(), 15 * 60 * 1000);
  },
  beforeUnmount() {
    clearTimeout(this._debounceTimer);
    clearInterval(this._refreshInterval);
  },
  methods: {
    t(text) {
      return window.t ? window.t('intravox', text) : text;
    },
    async fetchFeed() {
      this.loading = true;
      this.error = null;

      try {
        let sourceType = this.widget.sourceType || 'rss';
        // Auto-detect: if a connectionId is set but sourceType is 'rss', treat as connection
        if (sourceType === 'rss' && this.widget.connectionId) {
          sourceType = 'connection';
        }
        const params = new URLSearchParams({
          sourceType,
          limit: String(this.widget.limit || 5),
        });

        if (sourceType === 'rss') {
          if (!this.widget.feedUrl) {
            this.items = [];
            this.loading = false;
            return;
          }
          params.append('url', this.widget.feedUrl);
        } else {
          if (!this.widget.connectionId) {
            this.items = [];
            this.loading = false;
            return;
          }
          params.append('connectionId', this.widget.connectionId);
          if (this.widget.courseId) {
            params.append('courseId', this.widget.courseId);
          }
          if (this.widget.contentType) {
            params.append('contentType', this.widget.contentType);
          }
          if (this.widget.jiraProject) {
            params.append('jiraProject', this.widget.jiraProject);
          }
          if (this.widget.moodleForumId) {
            params.append('moodleForumId', this.widget.moodleForumId);
          }
          if (this.widget.listId) {
            params.append('listId', this.widget.listId);
          }
        }

        // Sort and filter
        if (this.widget.sortBy) {
          params.append('sortBy', this.widget.sortBy);
        }
        if (this.widget.sortOrder) {
          params.append('sortOrder', this.widget.sortOrder);
        }
        if (this.widget.filterKeyword) {
          params.append('filterKeyword', this.widget.filterKeyword);
        }

        const url = this.shareToken
          ? (0,_nextcloud_router__WEBPACK_IMPORTED_MODULE_1__.generateUrl)(`/apps/intravox/api/share/${this.shareToken}/feed/external?${params}`)
          : (0,_nextcloud_router__WEBPACK_IMPORTED_MODULE_1__.generateUrl)(`/apps/intravox/api/feed/external?${params}`);

        const response = await _nextcloud_axios__WEBPACK_IMPORTED_MODULE_0__["default"].get(url);

        if (response.data.error) {
          const err = response.data.error;
          if (err.includes('inactive') || err.includes('disabled')) {
            this.error = this.t('This connection is currently disabled by an administrator.');
          } else if (err.includes('not found') || err.includes('404')) {
            this.error = this.t('Connection no longer exists. Please reconfigure this widget.');
          } else if (err.includes('token') || err.includes('401') || err.includes('Authentication')) {
            this.error = this.t('Authentication required. Please connect your account.');
          } else if (err.includes('403') || err.includes('Access denied')) {
            this.error = this.t('Access denied. Check the connection permissions.');
          } else if (err.includes('429') || err.includes('Rate limited')) {
            this.error = this.t('Too many requests. Please try again later.');
          } else {
            this.error = this.t('Could not load feed. Check the connection settings.');
          }
          this.items = [];
          this.feedImage = null;
        } else {
          this.items = response.data.items || [];
          this.feedImage = response.data.feedImage || null;
        }
      } catch (err) {
        this.error = this.t('Could not load feed. The external system may be unavailable.');
        this.items = [];
        this.feedImage = null;
      } finally {
        this.loading = false;
      }
    },
  },
});


/***/ },

/***/ "./src/components/InlineTextEditor.vue"
/*!*********************************************!*\
  !*** ./src/components/InlineTextEditor.vue ***!
  \*********************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _InlineTextEditor_vue_vue_type_template_id_0a2d6723_scoped_true__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./InlineTextEditor.vue?vue&type=template&id=0a2d6723&scoped=true */ "./src/components/InlineTextEditor.vue?vue&type=template&id=0a2d6723&scoped=true");
/* harmony import */ var _InlineTextEditor_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./InlineTextEditor.vue?vue&type=script&lang=js */ "./src/components/InlineTextEditor.vue?vue&type=script&lang=js");
/* harmony import */ var _InlineTextEditor_vue_vue_type_style_index_0_id_0a2d6723_scoped_true_lang_css__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./InlineTextEditor.vue?vue&type=style&index=0&id=0a2d6723&scoped=true&lang=css */ "./src/components/InlineTextEditor.vue?vue&type=style&index=0&id=0a2d6723&scoped=true&lang=css");
/* harmony import */ var _node_modules_vue_loader_dist_exportHelper_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ../../node_modules/vue-loader/dist/exportHelper.js */ "./node_modules/vue-loader/dist/exportHelper.js");




;


const __exports__ = /*#__PURE__*/(0,_node_modules_vue_loader_dist_exportHelper_js__WEBPACK_IMPORTED_MODULE_3__["default"])(_InlineTextEditor_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_1__["default"], [['render',_InlineTextEditor_vue_vue_type_template_id_0a2d6723_scoped_true__WEBPACK_IMPORTED_MODULE_0__.render],['__scopeId',"data-v-0a2d6723"],['__file',"src/components/InlineTextEditor.vue"]])
/* hot reload */
if (false) // removed by dead control flow
{}


/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (__exports__);

/***/ },

/***/ "./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/InlineTextEditor.vue?vue&type=script&lang=js"
/*!*****************************************************************************************************************************************!*\
  !*** ./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/InlineTextEditor.vue?vue&type=script&lang=js ***!
  \*****************************************************************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _nextcloud_l10n__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @nextcloud/l10n */ "./node_modules/@nextcloud/l10n/dist/index.mjs");
/* harmony import */ var _nextcloud_vue__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @nextcloud/vue */ "./node_modules/@nextcloud/vue/dist/index.mjs");
/* harmony import */ var _utils_markdownSerializer_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ../utils/markdownSerializer.js */ "./src/utils/markdownSerializer.js");
/* harmony import */ var vue_material_design_icons_FormatBold_vue__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! vue-material-design-icons/FormatBold.vue */ "./node_modules/vue-material-design-icons/FormatBold.vue");
/* harmony import */ var vue_material_design_icons_FormatItalic_vue__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! vue-material-design-icons/FormatItalic.vue */ "./node_modules/vue-material-design-icons/FormatItalic.vue");
/* harmony import */ var vue_material_design_icons_FormatUnderline_vue__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! vue-material-design-icons/FormatUnderline.vue */ "./node_modules/vue-material-design-icons/FormatUnderline.vue");
/* harmony import */ var vue_material_design_icons_FormatStrikethrough_vue__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! vue-material-design-icons/FormatStrikethrough.vue */ "./node_modules/vue-material-design-icons/FormatStrikethrough.vue");
/* harmony import */ var vue_material_design_icons_FormatListBulleted_vue__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! vue-material-design-icons/FormatListBulleted.vue */ "./node_modules/vue-material-design-icons/FormatListBulleted.vue");
/* harmony import */ var vue_material_design_icons_FormatListNumbered_vue__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__(/*! vue-material-design-icons/FormatListNumbered.vue */ "./node_modules/vue-material-design-icons/FormatListNumbered.vue");
/* harmony import */ var vue_material_design_icons_LinkVariant_vue__WEBPACK_IMPORTED_MODULE_9__ = __webpack_require__(/*! vue-material-design-icons/LinkVariant.vue */ "./node_modules/vue-material-design-icons/LinkVariant.vue");
/* harmony import */ var vue_material_design_icons_Table_vue__WEBPACK_IMPORTED_MODULE_10__ = __webpack_require__(/*! vue-material-design-icons/Table.vue */ "./node_modules/vue-material-design-icons/Table.vue");
/* harmony import */ var vue_material_design_icons_TableRowPlusAfter_vue__WEBPACK_IMPORTED_MODULE_11__ = __webpack_require__(/*! vue-material-design-icons/TableRowPlusAfter.vue */ "./node_modules/vue-material-design-icons/TableRowPlusAfter.vue");
/* harmony import */ var vue_material_design_icons_TableRowPlusBefore_vue__WEBPACK_IMPORTED_MODULE_12__ = __webpack_require__(/*! vue-material-design-icons/TableRowPlusBefore.vue */ "./node_modules/vue-material-design-icons/TableRowPlusBefore.vue");
/* harmony import */ var vue_material_design_icons_TableColumnPlusAfter_vue__WEBPACK_IMPORTED_MODULE_13__ = __webpack_require__(/*! vue-material-design-icons/TableColumnPlusAfter.vue */ "./node_modules/vue-material-design-icons/TableColumnPlusAfter.vue");
/* harmony import */ var vue_material_design_icons_TableColumnPlusBefore_vue__WEBPACK_IMPORTED_MODULE_14__ = __webpack_require__(/*! vue-material-design-icons/TableColumnPlusBefore.vue */ "./node_modules/vue-material-design-icons/TableColumnPlusBefore.vue");
/* harmony import */ var vue_material_design_icons_TableRowRemove_vue__WEBPACK_IMPORTED_MODULE_15__ = __webpack_require__(/*! vue-material-design-icons/TableRowRemove.vue */ "./node_modules/vue-material-design-icons/TableRowRemove.vue");
/* harmony import */ var vue_material_design_icons_TableColumnRemove_vue__WEBPACK_IMPORTED_MODULE_16__ = __webpack_require__(/*! vue-material-design-icons/TableColumnRemove.vue */ "./node_modules/vue-material-design-icons/TableColumnRemove.vue");
/* harmony import */ var vue_material_design_icons_TableRemove_vue__WEBPACK_IMPORTED_MODULE_17__ = __webpack_require__(/*! vue-material-design-icons/TableRemove.vue */ "./node_modules/vue-material-design-icons/TableRemove.vue");
/* harmony import */ var vue_material_design_icons_FormatAlignLeft_vue__WEBPACK_IMPORTED_MODULE_18__ = __webpack_require__(/*! vue-material-design-icons/FormatAlignLeft.vue */ "./node_modules/vue-material-design-icons/FormatAlignLeft.vue");
/* harmony import */ var vue_material_design_icons_FormatAlignCenter_vue__WEBPACK_IMPORTED_MODULE_19__ = __webpack_require__(/*! vue-material-design-icons/FormatAlignCenter.vue */ "./node_modules/vue-material-design-icons/FormatAlignCenter.vue");
/* harmony import */ var vue_material_design_icons_FormatAlignRight_vue__WEBPACK_IMPORTED_MODULE_20__ = __webpack_require__(/*! vue-material-design-icons/FormatAlignRight.vue */ "./node_modules/vue-material-design-icons/FormatAlignRight.vue");
/* harmony import */ var vue_material_design_icons_FormatQuoteClose_vue__WEBPACK_IMPORTED_MODULE_21__ = __webpack_require__(/*! vue-material-design-icons/FormatQuoteClose.vue */ "./node_modules/vue-material-design-icons/FormatQuoteClose.vue");
/* harmony import */ var vue_material_design_icons_ChevronDown_vue__WEBPACK_IMPORTED_MODULE_22__ = __webpack_require__(/*! vue-material-design-icons/ChevronDown.vue */ "./node_modules/vue-material-design-icons/ChevronDown.vue");
/* harmony import */ var vue_material_design_icons_DotsHorizontal_vue__WEBPACK_IMPORTED_MODULE_23__ = __webpack_require__(/*! vue-material-design-icons/DotsHorizontal.vue */ "./node_modules/vue-material-design-icons/DotsHorizontal.vue");





// TipTap and extensions are loaded lazily on first editor mount to reduce initial bundle size (~500KB)
let _tiptapModules = null;
async function loadTipTap() {
    if (_tiptapModules) return _tiptapModules;
    const [vue3, starterKit, underline, link, placeholder, table, tableRow, tableHeader, tableCell, textAlign, dummyText, tableResize] = await Promise.all([
        __webpack_require__.e(/*! import() */ "vendors").then(__webpack_require__.bind(__webpack_require__, /*! @tiptap/vue-3 */ "./node_modules/@tiptap/vue-3/dist/index.js")),
        __webpack_require__.e(/*! import() */ "vendors").then(__webpack_require__.bind(__webpack_require__, /*! @tiptap/starter-kit */ "./node_modules/@tiptap/starter-kit/dist/index.js")),
        __webpack_require__.e(/*! import() */ "vendors").then(__webpack_require__.bind(__webpack_require__, /*! @tiptap/extension-underline */ "./node_modules/@tiptap/extension-underline/dist/index.js")),
        __webpack_require__.e(/*! import() */ "vendors").then(__webpack_require__.bind(__webpack_require__, /*! @tiptap/extension-link */ "./node_modules/@tiptap/extension-link/dist/index.js")),
        __webpack_require__.e(/*! import() */ "vendors").then(__webpack_require__.bind(__webpack_require__, /*! @tiptap/extension-placeholder */ "./node_modules/@tiptap/extension-placeholder/dist/index.js")),
        __webpack_require__.e(/*! import() */ "vendors").then(__webpack_require__.bind(__webpack_require__, /*! @tiptap/extension-table */ "./node_modules/@tiptap/extension-table/dist/index.js")),
        __webpack_require__.e(/*! import() */ "vendors").then(__webpack_require__.bind(__webpack_require__, /*! @tiptap/extension-table-row */ "./node_modules/@tiptap/extension-table-row/dist/index.js")),
        __webpack_require__.e(/*! import() */ "vendors").then(__webpack_require__.bind(__webpack_require__, /*! @tiptap/extension-table-header */ "./node_modules/@tiptap/extension-table-header/dist/index.js")),
        __webpack_require__.e(/*! import() */ "vendors").then(__webpack_require__.bind(__webpack_require__, /*! @tiptap/extension-table-cell */ "./node_modules/@tiptap/extension-table-cell/dist/index.js")),
        Promise.all(/*! import() */[__webpack_require__.e("vendors"), __webpack_require__.e("src_utils_textAlignExtension_js")]).then(__webpack_require__.bind(__webpack_require__, /*! ../utils/textAlignExtension.js */ "./src/utils/textAlignExtension.js")),
        Promise.all(/*! import() */[__webpack_require__.e("vendors"), __webpack_require__.e("src_utils_dummyTextGenerator_js")]).then(__webpack_require__.bind(__webpack_require__, /*! ../utils/dummyTextGenerator.js */ "./src/utils/dummyTextGenerator.js")),
        Promise.all(/*! import() */[__webpack_require__.e("vendors"), __webpack_require__.e("src_utils_tableResizeHandle_js")]).then(__webpack_require__.bind(__webpack_require__, /*! ../utils/tableResizeHandle.js */ "./src/utils/tableResizeHandle.js")),
    ]);
    _tiptapModules = {
        Editor: vue3.Editor,
        EditorContent: vue3.EditorContent,
        StarterKit: starterKit.default,
        Underline: underline.default,
        Link: link.default,
        Placeholder: placeholder.default,
        Table: table.Table,
        TableRow: tableRow.TableRow,
        TableHeader: tableHeader.TableHeader,
        TableCell: tableCell.TableCell,
        TextAlign: textAlign.TextAlign,
        DummyTextExtension: dummyText.DummyTextExtension,
        TableResizeHandle: tableResize.TableResizeHandle,
    };
    return _tiptapModules;
}

// Material Design Icons






















/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = ({
  name: 'InlineTextEditor',
  components: {
    NcModal: _nextcloud_vue__WEBPACK_IMPORTED_MODULE_1__.NcModal,
    NcButton: _nextcloud_vue__WEBPACK_IMPORTED_MODULE_1__.NcButton,
    FormatBold: vue_material_design_icons_FormatBold_vue__WEBPACK_IMPORTED_MODULE_3__["default"],
    FormatItalic: vue_material_design_icons_FormatItalic_vue__WEBPACK_IMPORTED_MODULE_4__["default"],
    FormatUnderline: vue_material_design_icons_FormatUnderline_vue__WEBPACK_IMPORTED_MODULE_5__["default"],
    FormatStrikethrough: vue_material_design_icons_FormatStrikethrough_vue__WEBPACK_IMPORTED_MODULE_6__["default"],
    FormatListBulleted: vue_material_design_icons_FormatListBulleted_vue__WEBPACK_IMPORTED_MODULE_7__["default"],
    FormatListNumbered: vue_material_design_icons_FormatListNumbered_vue__WEBPACK_IMPORTED_MODULE_8__["default"],
    LinkVariant: vue_material_design_icons_LinkVariant_vue__WEBPACK_IMPORTED_MODULE_9__["default"],
    TableIcon: vue_material_design_icons_Table_vue__WEBPACK_IMPORTED_MODULE_10__["default"],
    TableRowPlusAfter: vue_material_design_icons_TableRowPlusAfter_vue__WEBPACK_IMPORTED_MODULE_11__["default"],
    TableRowPlusBefore: vue_material_design_icons_TableRowPlusBefore_vue__WEBPACK_IMPORTED_MODULE_12__["default"],
    TableColumnPlusAfter: vue_material_design_icons_TableColumnPlusAfter_vue__WEBPACK_IMPORTED_MODULE_13__["default"],
    TableColumnPlusBefore: vue_material_design_icons_TableColumnPlusBefore_vue__WEBPACK_IMPORTED_MODULE_14__["default"],
    TableRowRemove: vue_material_design_icons_TableRowRemove_vue__WEBPACK_IMPORTED_MODULE_15__["default"],
    TableColumnRemove: vue_material_design_icons_TableColumnRemove_vue__WEBPACK_IMPORTED_MODULE_16__["default"],
    TableRemove: vue_material_design_icons_TableRemove_vue__WEBPACK_IMPORTED_MODULE_17__["default"],
    FormatAlignLeft: vue_material_design_icons_FormatAlignLeft_vue__WEBPACK_IMPORTED_MODULE_18__["default"],
    FormatAlignCenter: vue_material_design_icons_FormatAlignCenter_vue__WEBPACK_IMPORTED_MODULE_19__["default"],
    FormatAlignRight: vue_material_design_icons_FormatAlignRight_vue__WEBPACK_IMPORTED_MODULE_20__["default"],
    FormatQuoteClose: vue_material_design_icons_FormatQuoteClose_vue__WEBPACK_IMPORTED_MODULE_21__["default"],
    ChevronDown: vue_material_design_icons_ChevronDown_vue__WEBPACK_IMPORTED_MODULE_22__["default"],
    DotsHorizontal: vue_material_design_icons_DotsHorizontal_vue__WEBPACK_IMPORTED_MODULE_23__["default"]
  },
  props: {
    modelValue: {
      type: String,
      default: ''
    },
    editable: {
      type: Boolean,
      default: true
    },
    placeholder: {
      type: String,
      default: ''
    },
    compact: {
      type: Boolean,
      default: false
    }
  },
  emits: ['update:modelValue', 'focus', 'blur'],
  data() {
    return {
      editor: null,
      editorContentComponent: null,
      isFocused: false,
      showToolbar: true,
      showLinkModal: false,
      linkUrl: '',
      linkText: '',
      showHeadingMenu: false,
      showTableMenu: false,
      // Width presets shown in the table dropdown — null means "auto"
      // (default: fill the widget container).
      tableWidthPresets: [null, '25%', '50%', '75%', '100%'],
      showAlignmentMenu: false,
      showMoreMenu: false,
      alignmentIcons: {
        left: vue_material_design_icons_FormatAlignLeft_vue__WEBPACK_IMPORTED_MODULE_18__["default"],
        center: vue_material_design_icons_FormatAlignCenter_vue__WEBPACK_IMPORTED_MODULE_19__["default"],
        right: vue_material_design_icons_FormatAlignRight_vue__WEBPACK_IMPORTED_MODULE_20__["default"],
      }
    };
  },
  async mounted() {
    // Lazy-load TipTap (~500KB) only when editor is actually mounted
    const modules = await loadTipTap();
    this.editorContentComponent = modules.EditorContent;

    // Convert Markdown to HTML for TipTap editor
    const htmlContent = (0,_utils_markdownSerializer_js__WEBPACK_IMPORTED_MODULE_2__.markdownToHtml)(this.modelValue);

    this.editor = new modules.Editor({
      content: htmlContent,
      editable: this.editable,
      extensions: [
        modules.StarterKit.configure({
          // Disable built-in extensions we configure separately
          link: false,
          underline: false,
        }),
        modules.Underline,
        modules.Link.configure({
          openOnClick: false,
          HTMLAttributes: {
            target: '_blank',
            rel: 'noopener noreferrer'
          }
        }),
        modules.Placeholder.configure({
          placeholder: this.placeholder || this.t('Enter text...')
        }),
        // Custom Table node — adds `width` and `align` attributes so editors
        // can make a table narrower than the widget container and align it
        // (left, center, right). The base Table.renderHTML composes the colgroup
        // and a default `style` for column-resize; if we hand it a `style`
        // here it short-circuits and uses ours, which is what we want for
        // explicit width/align. We deliberately keep the parent renderHTML so
        // the colgroup keeps working — only the attribute-level renderHTMLs
        // contribute to the merged style.
        modules.Table.extend({
          addAttributes() {
            const parent = this.parent?.() || {};
            return {
              ...parent,
              width: {
                default: null,
                parseHTML: (el) => {
                  const w = (el.style && el.style.width) || el.getAttribute('width') || null;
                  return w && w !== '100%' ? w : null;
                },
                renderHTML: (attrs) => (attrs.width ? { style: `width: ${attrs.width}` } : {}),
              },
              align: {
                default: null,
                parseHTML: (el) => {
                  if (!el.style) return null;
                  const ml = el.style.marginLeft;
                  const mr = el.style.marginRight;
                  if (ml === 'auto' && mr === 'auto') return 'center';
                  if (ml === 'auto' && mr !== 'auto') return 'right';
                  if (mr === 'auto' && ml !== 'auto') return 'left';
                  return null;
                },
                renderHTML: (attrs) => {
                  if (attrs.align === 'center') return { style: 'margin-left: auto; margin-right: auto' };
                  if (attrs.align === 'right') return { style: 'margin-left: auto; margin-right: 0' };
                  if (attrs.align === 'left') return { style: 'margin-left: 0; margin-right: auto' };
                  return {};
                },
              },
            };
          },
        }).configure({
          resizable: true,
          handleWidth: 4,
          // 80px is wide enough for ~3-4 short words plus padding; below
          // that the cell becomes effectively unusable for prose content.
          cellMinWidth: 80,
          lastColumnResizable: true,
        }),
        modules.TableRow,
        modules.TableHeader,
        modules.TableCell,
        modules.TableResizeHandle,
        modules.TextAlign.configure({
          types: ['heading', 'paragraph'],
          alignments: ['left', 'center', 'right'],
          defaultAlignment: 'left',
        }),
        modules.DummyTextExtension,
      ],
      onUpdate: () => {
        // Convert HTML back to Markdown for storage
        const html = this.editor.getHTML();
        const markdown = (0,_utils_markdownSerializer_js__WEBPACK_IMPORTED_MODULE_2__.cleanMarkdown)((0,_utils_markdownSerializer_js__WEBPACK_IMPORTED_MODULE_2__.htmlToMarkdown)(html));
        this.$emit('update:modelValue', markdown);
      },
      onFocus: () => {
        this.isFocused = true;
        this.$emit('focus');

        // Prevent automatic select-all: place cursor at end instead
        // Use setTimeout to let the browser finish its default behavior first
        setTimeout(() => {
          if (!this.editor) return;
          const { state } = this.editor;
          const docSize = state.doc.content.size;
          // If entire content is selected (selectAll behavior), deselect and place cursor at end
          if (state.selection.from <= 1 && state.selection.to >= docSize - 1 && docSize > 2) {
            this.editor.commands.setTextSelection(docSize - 1);
          }
        }, 0);
      },
      onBlur: () => {
        this.isFocused = false;
        this.$emit('blur');
      }
    });

    // Close heading menu when clicking outside
    document.addEventListener('click', this.handleClickOutside);
  },
  beforeUnmount() {
    if (this.editor) {
      this.editor.destroy();
    }
    document.removeEventListener('click', this.handleClickOutside);
  },
  watch: {
    modelValue(newValue) {
      // Skip update if editor is focused (user is typing)
      if (this.isFocused) {
        return;
      }

      // Convert Markdown to HTML for comparison
      const htmlContent = (0,_utils_markdownSerializer_js__WEBPACK_IMPORTED_MODULE_2__.markdownToHtml)(newValue);
      const currentHtml = this.editor.getHTML();

      // Only update if content actually changed
      if (currentHtml !== htmlContent) {
        // Save cursor position
        const { from, to } = this.editor.state.selection;

        // Update content (converted to HTML)
        this.editor.commands.setContent(htmlContent, false);

        // Restore cursor position if possible
        try {
          this.editor.commands.setTextSelection({ from, to });
        } catch (e) {
          // If restoration fails (content changed too much), just continue
        }
      }
    },
    editable(newValue) {
      this.editor.setEditable(newValue);
    }
  },
  computed: {
    currentAlignmentIcon() {
      if (this.editor?.isActive({ textAlign: 'center' })) return vue_material_design_icons_FormatAlignCenter_vue__WEBPACK_IMPORTED_MODULE_19__["default"];
      if (this.editor?.isActive({ textAlign: 'right' })) return vue_material_design_icons_FormatAlignRight_vue__WEBPACK_IMPORTED_MODULE_20__["default"];
      return vue_material_design_icons_FormatAlignLeft_vue__WEBPACK_IMPORTED_MODULE_18__["default"];
    },
  },
  methods: {
    t(key, vars = {}) {
      return (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_0__.translate)('intravox', key, vars);
    },
    getCurrentHeadingLevel() {
      if (!this.editor) return 0;
      for (let level = 1; level <= 6; level++) {
        if (this.editor.isActive('heading', { level })) {
          return level;
        }
      }
      return 0;
    },
    getCurrentHeadingLabel() {
      const level = this.getCurrentHeadingLevel();
      return this.getHeadingLabel(level);
    },
    getHeadingLabel(level) {
      if (level === 0) return this.t('Paragraph');
      return this.t(`H${level}`);
    },
    isHeadingLevel(level) {
      return this.getCurrentHeadingLevel() === level;
    },
    toggleHeadingMenu() {
      this.showHeadingMenu = !this.showHeadingMenu;
      this.showAlignmentMenu = false;
      this.showTableMenu = false;
    },
    setHeadingLevel(level) {
      if (level === 0) {
        this.editor.chain().focus().setParagraph().run();
      } else {
        this.editor.chain().focus().setHeading({ level }).run();
      }
      this.showHeadingMenu = false;
    },
    showLinkModalHandler() {
      const { from, to } = this.editor.state.selection;
      const selectedText = this.editor.state.doc.textBetween(from, to, ' ');

      const previousUrl = this.editor.getAttributes('link').href;
      this.linkUrl = previousUrl || '';
      this.linkText = selectedText || '';
      this.showLinkModal = true;
    },
    closeLinkModal() {
      this.showLinkModal = false;
      this.linkUrl = '';
      this.linkText = '';
      this.editor.chain().focus().run();
    },
    applyLink() {
      if (this.linkUrl === '') {
        this.removeLink();
        return;
      }

      // Use linkText if provided, otherwise use URL
      const displayText = this.linkText || this.linkUrl;

      // Get current selection
      const { from, to } = this.editor.state.selection;

      // If no text is selected or we want to replace it, insert new link
      if (from === to) {
        // Insert new link at cursor position
        this.editor
          .chain()
          .focus()
          .insertContent({
            type: 'text',
            marks: [
              {
                type: 'link',
                attrs: {
                  href: this.linkUrl,
                  target: '_blank',
                  rel: 'noopener noreferrer'
                }
              }
            ],
            text: displayText
          })
          .run();
      } else {
        // Replace selected text with link
        this.editor
          .chain()
          .focus()
          .deleteSelection()
          .insertContent({
            type: 'text',
            marks: [
              {
                type: 'link',
                attrs: {
                  href: this.linkUrl,
                  target: '_blank',
                  rel: 'noopener noreferrer'
                }
              }
            ],
            text: displayText
          })
          .run();
      }

      this.closeLinkModal();
    },
    removeLink() {
      this.editor
        .chain()
        .focus()
        .extendMarkRange('link')
        .unsetLink()
        .run();

      this.closeLinkModal();
    },
    handleClickOutside(event) {
      // Close heading menu if clicking outside the dropdown
      if (!event.target.closest('.heading-dropdown')) {
        this.showHeadingMenu = false;
      }
      // Close table menu if clicking outside the dropdown
      if (!event.target.closest('.table-dropdown')) {
        this.showTableMenu = false;
      }
      // Close alignment menu if clicking outside the dropdown
      if (!event.target.closest('.alignment-dropdown')) {
        this.showAlignmentMenu = false;
      }
      // Close more menu if clicking outside the dropdown
      if (!event.target.closest('.more-dropdown')) {
        this.showMoreMenu = false;
      }
    },
    toggleMoreMenu() {
      this.showMoreMenu = !this.showMoreMenu;
      if (this.showMoreMenu) {
        this.$nextTick(() => {
          const btn = this.$refs.moreButton;
          const menu = this.$el.querySelector('.more-menu');
          if (btn && menu) {
            const rect = btn.getBoundingClientRect();
            menu.style.top = (rect.bottom + 4) + 'px';
            menu.style.left = rect.left + 'px';
            // Constrain height to available viewport space
            const availableHeight = window.innerHeight - rect.bottom - 16;
            if (availableHeight < menu.scrollHeight) {
              menu.style.maxHeight = availableHeight + 'px';
            }
          }
        });
      }
    },
    toggleTableMenu() {
      this.showTableMenu = !this.showTableMenu;
      this.showHeadingMenu = false;
      this.showAlignmentMenu = false;
    },
    toggleAlignmentMenu() {
      this.showAlignmentMenu = !this.showAlignmentMenu;
      this.showHeadingMenu = false;
      this.showTableMenu = false;
    },
    setAlignment(align) {
      if (align === 'left') {
        this.editor.chain().focus().unsetTextAlign().run();
      } else {
        this.editor.chain().focus().setTextAlign(align).run();
      }
      this.showAlignmentMenu = false;
    },
    isAlignmentActive(alignment) {
      if (!this.editor) return false;
      if (alignment === 'left') {
        return !this.editor.isActive({ textAlign: 'center' })
            && !this.editor.isActive({ textAlign: 'right' });
      }
      return this.editor.isActive({ textAlign: alignment });
    },
    getAlignmentLabel(align) {
      const labels = {
        left: this.t('Align left'),
        center: this.t('Align center'),
        right: this.t('Align right'),
      };
      return labels[align];
    },
    insertTable() {
      this.editor.chain().focus().insertTable({ rows: 3, cols: 3, withHeaderRow: true }).run();
      this.showTableMenu = false;
    },
    addRowBefore() {
      this.editor.chain().focus().addRowBefore().run();
      this.showTableMenu = false;
    },
    addRowAfter() {
      this.editor.chain().focus().addRowAfter().run();
      this.showTableMenu = false;
    },
    addColumnBefore() {
      this.editor.chain().focus().addColumnBefore().run();
      this.showTableMenu = false;
    },
    addColumnAfter() {
      this.editor.chain().focus().addColumnAfter().run();
      this.showTableMenu = false;
    },
    deleteRow() {
      this.editor.chain().focus().deleteRow().run();
      this.showTableMenu = false;
    },
    deleteColumn() {
      this.editor.chain().focus().deleteColumn().run();
      this.showTableMenu = false;
    },
    deleteTable() {
      this.editor.chain().focus().deleteTable().run();
      this.showTableMenu = false;
    },

    /**
     * Set the table width. `value` is a CSS length (e.g. '50%', '600px') or
     * null to clear the explicit width and fall back to the default
     * (full-container width with TipTap's column-resize behaviour).
     */
    setTableWidth(value) {
      this.editor.chain().focus().updateAttributes('table', { width: value }).run();
    },
    isTableWidth(value) {
      if (!this.editor || !this.editor.isActive('table')) return false;
      return this.editor.getAttributes('table').width === value;
    },

    /**
     * Set the table alignment via CSS margin shorthand. Pass null to clear.
     */
    setTableAlign(value) {
      this.editor.chain().focus().updateAttributes('table', { align: value }).run();
    },
    isTableAlign(value) {
      if (!this.editor || !this.editor.isActive('table')) return false;
      return this.editor.getAttributes('table').align === value;
    },
  }
});


/***/ },

/***/ "./src/components/PageTreeSelect.vue"
/*!*******************************************!*\
  !*** ./src/components/PageTreeSelect.vue ***!
  \*******************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _PageTreeSelect_vue_vue_type_template_id_5efccfd9_scoped_true__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./PageTreeSelect.vue?vue&type=template&id=5efccfd9&scoped=true */ "./src/components/PageTreeSelect.vue?vue&type=template&id=5efccfd9&scoped=true");
/* harmony import */ var _PageTreeSelect_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./PageTreeSelect.vue?vue&type=script&lang=js */ "./src/components/PageTreeSelect.vue?vue&type=script&lang=js");
/* harmony import */ var _PageTreeSelect_vue_vue_type_style_index_0_id_5efccfd9_scoped_true_lang_css__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./PageTreeSelect.vue?vue&type=style&index=0&id=5efccfd9&scoped=true&lang=css */ "./src/components/PageTreeSelect.vue?vue&type=style&index=0&id=5efccfd9&scoped=true&lang=css");
/* harmony import */ var _node_modules_vue_loader_dist_exportHelper_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ../../node_modules/vue-loader/dist/exportHelper.js */ "./node_modules/vue-loader/dist/exportHelper.js");




;


const __exports__ = /*#__PURE__*/(0,_node_modules_vue_loader_dist_exportHelper_js__WEBPACK_IMPORTED_MODULE_3__["default"])(_PageTreeSelect_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_1__["default"], [['render',_PageTreeSelect_vue_vue_type_template_id_5efccfd9_scoped_true__WEBPACK_IMPORTED_MODULE_0__.render],['__scopeId',"data-v-5efccfd9"],['__file',"src/components/PageTreeSelect.vue"]])
/* hot reload */
if (false) // removed by dead control flow
{}


/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (__exports__);

/***/ },

/***/ "./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/PageTreeSelect.vue?vue&type=script&lang=js"
/*!***************************************************************************************************************************************!*\
  !*** ./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/PageTreeSelect.vue?vue&type=script&lang=js ***!
  \***************************************************************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _nextcloud_l10n__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @nextcloud/l10n */ "./node_modules/@nextcloud/l10n/dist/index.mjs");
/* harmony import */ var _nextcloud_vue__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @nextcloud/vue */ "./node_modules/@nextcloud/vue/dist/index.mjs");
/* harmony import */ var _nextcloud_axios__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @nextcloud/axios */ "./node_modules/@nextcloud/axios/dist/index.js");
/* harmony import */ var _nextcloud_router__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! @nextcloud/router */ "./node_modules/@nextcloud/router/dist/index.mjs");
/* harmony import */ var vue_material_design_icons_ChevronDown_vue__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! vue-material-design-icons/ChevronDown.vue */ "./node_modules/vue-material-design-icons/ChevronDown.vue");
/* harmony import */ var vue_material_design_icons_FileDocument_vue__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! vue-material-design-icons/FileDocument.vue */ "./node_modules/vue-material-design-icons/FileDocument.vue");
/* harmony import */ var vue_material_design_icons_Magnify_vue__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! vue-material-design-icons/Magnify.vue */ "./node_modules/vue-material-design-icons/Magnify.vue");
/* harmony import */ var vue_material_design_icons_Close_vue__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! vue-material-design-icons/Close.vue */ "./node_modules/vue-material-design-icons/Close.vue");
/* harmony import */ var _PageTreeSelectItem_vue__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__(/*! ./PageTreeSelectItem.vue */ "./src/components/PageTreeSelectItem.vue");











/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = ({
  name: 'PageTreeSelect',
  components: {
    NcLoadingIcon: _nextcloud_vue__WEBPACK_IMPORTED_MODULE_1__.NcLoadingIcon,
    ChevronDown: vue_material_design_icons_ChevronDown_vue__WEBPACK_IMPORTED_MODULE_4__["default"],
    FileDocument: vue_material_design_icons_FileDocument_vue__WEBPACK_IMPORTED_MODULE_5__["default"],
    Magnify: vue_material_design_icons_Magnify_vue__WEBPACK_IMPORTED_MODULE_6__["default"],
    Close: vue_material_design_icons_Close_vue__WEBPACK_IMPORTED_MODULE_7__["default"],
    PageTreeSelectItem: _PageTreeSelectItem_vue__WEBPACK_IMPORTED_MODULE_8__["default"]
  },
  props: {
    modelValue: {
      type: String,
      default: null
    },
    placeholder: {
      type: String,
      default: 'Select a page'
    },
    clearable: {
      type: Boolean,
      default: true
    },
    disabled: {
      type: Boolean,
      default: false
    },
    language: {
      type: String,
      default: null
    },
    /**
     * Restrict the dropdown to the subtree rooted at this page uniqueId.
     * Useful for picking a child page within a known section (e.g. a
     * teamhub builder that only cares about pages under /teamhub/).
     * When null, the full tree is shown.
     */
    rootPageId: {
      type: String,
      default: null
    }
  },
  emits: ['update:modelValue', 'select'],
  data() {
    return {
      isOpen: false,
      loading: false,
      tree: [],
      expandedNodes: new Set(),
      searchQuery: '',
      focusedId: null,
      flatPages: []
    };
  },
  computed: {
    selectedPage() {
      if (!this.modelValue) return null;
      return this.flatPages.find(p => p.uniqueId === this.modelValue);
    },
    selectedPath() {
      if (!this.selectedPage || !this.selectedPage.path) return null;
      return this.selectedPage.path;
    },
    filteredTree() {
      if (!this.searchQuery.trim()) {
        return this.tree;
      }
      const query = this.searchQuery.toLowerCase();
      return this.filterTree(this.tree, query);
    },
    focusableItems() {
      // Get flat list of visible items for keyboard navigation
      return this.getFlatVisibleItems(this.filteredTree);
    }
  },
  watch: {
    isOpen(open) {
      if (open) {
        this.$nextTick(() => {
          this.$refs.searchInput?.focus();
          // Expand path to selected item
          if (this.modelValue) {
            this.expandPathToItem(this.tree, this.modelValue);
          }
        });
      } else {
        this.searchQuery = '';
        this.focusedId = null;
      }
    },
    language() {
      // Reload tree when language changes
      this.loadTree();
    },
    rootPageId() {
      // Switching the subtree anchor means we need a different slice
      // of the tree — the backend handles the filtering, we just refetch.
      this.loadTree();
    },
    modelValue: {
      immediate: true,
      handler(newValue) {
        // If we have a value but tree is not loaded yet, load it
        if (newValue && this.flatPages.length === 0 && !this.loading) {
          this.loadTree();
        }
      }
    }
  },
  mounted() {
    document.addEventListener('click', this.handleClickOutside);
    this.loadTree();
  },
  beforeUnmount() {
    document.removeEventListener('click', this.handleClickOutside);
  },
  methods: {
    t(key, vars = {}) {
      return (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_0__.translate)('intravox', key, vars);
    },
    async loadTree() {
      this.loading = true;
      try {
        const params = {};
        if (this.language) {
          params.language = this.language;
        }
        if (this.rootPageId) {
          params.rootPageId = this.rootPageId;
        }
        const response = await _nextcloud_axios__WEBPACK_IMPORTED_MODULE_2__["default"].get((0,_nextcloud_router__WEBPACK_IMPORTED_MODULE_3__.generateUrl)('/apps/intravox/api/pages/tree'), { params });
        const tree = response.data.tree || [];

        this.tree = tree;
        this.flatPages = this.flattenTree(this.tree, '');

        // Auto-expand first level
        this.tree.forEach(item => {
          if (item.children && item.children.length > 0) {
            this.expandedNodes.add(item.uniqueId);
          }
        });
      } catch (err) {
        console.error('PageTreeSelect: Error loading tree:', err);
      } finally {
        this.loading = false;
      }
    },
    flattenTree(nodes, parentPath) {
      const result = [];
      for (const node of nodes) {
        const path = parentPath;
        result.push({
          uniqueId: node.uniqueId,
          title: node.title,
          path: path,
          hasChildren: node.children && node.children.length > 0
        });
        if (node.children && node.children.length > 0) {
          const childPath = parentPath ? `${parentPath} / ${node.title}` : node.title;
          result.push(...this.flattenTree(node.children, childPath));
        }
      }
      return result;
    },
    filterTree(nodes, query) {
      const result = [];
      for (const node of nodes) {
        const matches = node.title.toLowerCase().includes(query);
        const filteredChildren = node.children ? this.filterTree(node.children, query) : [];

        if (matches || filteredChildren.length > 0) {
          result.push({
            ...node,
            children: filteredChildren,
            matchesSearch: matches
          });
          // Auto-expand nodes that have matching children
          if (filteredChildren.length > 0) {
            this.expandedNodes.add(node.uniqueId);
          }
        }
      }
      return result;
    },
    getFlatVisibleItems(nodes) {
      const result = [];
      for (const node of nodes) {
        result.push(node);
        if (node.children && node.children.length > 0 && this.expandedNodes.has(node.uniqueId)) {
          result.push(...this.getFlatVisibleItems(node.children));
        }
      }
      return result;
    },
    expandPathToItem(nodes, targetId) {
      for (const node of nodes) {
        if (node.uniqueId === targetId) {
          return true;
        }
        if (node.children && node.children.length > 0) {
          if (this.expandPathToItem(node.children, targetId)) {
            this.expandedNodes.add(node.uniqueId);
            return true;
          }
        }
      }
      return false;
    },
    toggleDropdown() {
      this.isOpen = !this.isOpen;
    },
    closeDropdown() {
      this.isOpen = false;
    },
    toggleNode(uniqueId) {
      if (this.expandedNodes.has(uniqueId)) {
        this.expandedNodes.delete(uniqueId);
      } else {
        this.expandedNodes.add(uniqueId);
      }
      this.expandedNodes = new Set(this.expandedNodes);
    },
    selectPage(page) {
      this.$emit('update:modelValue', page.uniqueId);
      this.$emit('select', page);
      this.closeDropdown();
    },
    clearSelection() {
      this.$emit('update:modelValue', null);
      this.$emit('select', null);
      this.closeDropdown();
    },
    handleClickOutside(event) {
      if (this.$refs.selectContainer && !this.$refs.selectContainer.contains(event.target)) {
        this.closeDropdown();
      }
    },
    focusNext() {
      const items = this.focusableItems;
      if (items.length === 0) return;

      const currentIndex = items.findIndex(i => i.uniqueId === this.focusedId);
      const nextIndex = currentIndex < items.length - 1 ? currentIndex + 1 : 0;
      this.focusedId = items[nextIndex].uniqueId;
      this.scrollToFocused();
    },
    focusPrev() {
      const items = this.focusableItems;
      if (items.length === 0) return;

      const currentIndex = items.findIndex(i => i.uniqueId === this.focusedId);
      const prevIndex = currentIndex > 0 ? currentIndex - 1 : items.length - 1;
      this.focusedId = items[prevIndex].uniqueId;
      this.scrollToFocused();
    },
    selectFocused() {
      if (this.focusedId) {
        const page = this.flatPages.find(p => p.uniqueId === this.focusedId);
        if (page) {
          this.selectPage(page);
        }
      }
    },
    scrollToFocused() {
      this.$nextTick(() => {
        const focusedEl = this.$refs.treeWrapper?.querySelector('.is-focused');
        if (focusedEl) {
          focusedEl.scrollIntoView({ block: 'nearest' });
        }
      });
    }
  }
});


/***/ },

/***/ "./src/components/PageTreeSelectItem.vue"
/*!***********************************************!*\
  !*** ./src/components/PageTreeSelectItem.vue ***!
  \***********************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _PageTreeSelectItem_vue_vue_type_template_id_621c650c_scoped_true__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./PageTreeSelectItem.vue?vue&type=template&id=621c650c&scoped=true */ "./src/components/PageTreeSelectItem.vue?vue&type=template&id=621c650c&scoped=true");
/* harmony import */ var _PageTreeSelectItem_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./PageTreeSelectItem.vue?vue&type=script&lang=js */ "./src/components/PageTreeSelectItem.vue?vue&type=script&lang=js");
/* harmony import */ var _PageTreeSelectItem_vue_vue_type_style_index_0_id_621c650c_scoped_true_lang_css__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./PageTreeSelectItem.vue?vue&type=style&index=0&id=621c650c&scoped=true&lang=css */ "./src/components/PageTreeSelectItem.vue?vue&type=style&index=0&id=621c650c&scoped=true&lang=css");
/* harmony import */ var _node_modules_vue_loader_dist_exportHelper_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ../../node_modules/vue-loader/dist/exportHelper.js */ "./node_modules/vue-loader/dist/exportHelper.js");




;


const __exports__ = /*#__PURE__*/(0,_node_modules_vue_loader_dist_exportHelper_js__WEBPACK_IMPORTED_MODULE_3__["default"])(_PageTreeSelectItem_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_1__["default"], [['render',_PageTreeSelectItem_vue_vue_type_template_id_621c650c_scoped_true__WEBPACK_IMPORTED_MODULE_0__.render],['__scopeId',"data-v-621c650c"],['__file',"src/components/PageTreeSelectItem.vue"]])
/* hot reload */
if (false) // removed by dead control flow
{}


/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (__exports__);

/***/ },

/***/ "./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/PageTreeSelectItem.vue?vue&type=script&lang=js"
/*!*******************************************************************************************************************************************!*\
  !*** ./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/PageTreeSelectItem.vue?vue&type=script&lang=js ***!
  \*******************************************************************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var vue_material_design_icons_ChevronRight_vue__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! vue-material-design-icons/ChevronRight.vue */ "./node_modules/vue-material-design-icons/ChevronRight.vue");
/* harmony import */ var vue_material_design_icons_ChevronDown_vue__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! vue-material-design-icons/ChevronDown.vue */ "./node_modules/vue-material-design-icons/ChevronDown.vue");
/* harmony import */ var vue_material_design_icons_FileDocument_vue__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! vue-material-design-icons/FileDocument.vue */ "./node_modules/vue-material-design-icons/FileDocument.vue");
/* harmony import */ var vue_material_design_icons_FolderOutline_vue__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! vue-material-design-icons/FolderOutline.vue */ "./node_modules/vue-material-design-icons/FolderOutline.vue");
/* harmony import */ var vue_material_design_icons_Check_vue__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! vue-material-design-icons/Check.vue */ "./node_modules/vue-material-design-icons/Check.vue");







/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = ({
  name: 'PageTreeSelectItem',
  components: {
    ChevronRight: vue_material_design_icons_ChevronRight_vue__WEBPACK_IMPORTED_MODULE_0__["default"],
    ChevronDown: vue_material_design_icons_ChevronDown_vue__WEBPACK_IMPORTED_MODULE_1__["default"],
    FileDocument: vue_material_design_icons_FileDocument_vue__WEBPACK_IMPORTED_MODULE_2__["default"],
    FolderOutline: vue_material_design_icons_FolderOutline_vue__WEBPACK_IMPORTED_MODULE_3__["default"],
    Check: vue_material_design_icons_Check_vue__WEBPACK_IMPORTED_MODULE_4__["default"]
  },
  props: {
    item: {
      type: Object,
      required: true
    },
    expandedNodes: {
      type: Set,
      required: true
    },
    selectedId: {
      type: String,
      default: null
    },
    focusedId: {
      type: String,
      default: null
    },
    searchQuery: {
      type: String,
      default: ''
    }
  },
  emits: ['toggle', 'select', 'focus'],
  data() {
    return {
      visibleChildCount: 50,
    };
  },
  computed: {
    hasChildren() {
      return this.item.children && this.item.children.length > 0;
    },
    isExpanded() {
      return this.expandedNodes.has(this.item.uniqueId);
    },
    isSelected() {
      return this.item.uniqueId === this.selectedId;
    },
    isFocused() {
      return this.item.uniqueId === this.focusedId;
    },
    visibleChildren() {
      if (!this.hasChildren) return [];
      return this.item.children.slice(0, this.visibleChildCount);
    },
    hasMoreChildren() {
      return this.hasChildren && this.item.children.length > this.visibleChildCount;
    },
  },
  methods: {
    showMoreChildren() {
      this.visibleChildCount += 50;
    },
  },
});


/***/ },

/***/ "./src/components/feed/FeedItem.vue"
/*!******************************************!*\
  !*** ./src/components/feed/FeedItem.vue ***!
  \******************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _FeedItem_vue_vue_type_template_id_82e10b3c_scoped_true__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./FeedItem.vue?vue&type=template&id=82e10b3c&scoped=true */ "./src/components/feed/FeedItem.vue?vue&type=template&id=82e10b3c&scoped=true");
/* harmony import */ var _FeedItem_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./FeedItem.vue?vue&type=script&lang=js */ "./src/components/feed/FeedItem.vue?vue&type=script&lang=js");
/* harmony import */ var _FeedItem_vue_vue_type_style_index_0_id_82e10b3c_scoped_true_lang_css__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./FeedItem.vue?vue&type=style&index=0&id=82e10b3c&scoped=true&lang=css */ "./src/components/feed/FeedItem.vue?vue&type=style&index=0&id=82e10b3c&scoped=true&lang=css");
/* harmony import */ var _node_modules_vue_loader_dist_exportHelper_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ../../../node_modules/vue-loader/dist/exportHelper.js */ "./node_modules/vue-loader/dist/exportHelper.js");




;


const __exports__ = /*#__PURE__*/(0,_node_modules_vue_loader_dist_exportHelper_js__WEBPACK_IMPORTED_MODULE_3__["default"])(_FeedItem_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_1__["default"], [['render',_FeedItem_vue_vue_type_template_id_82e10b3c_scoped_true__WEBPACK_IMPORTED_MODULE_0__.render],['__scopeId',"data-v-82e10b3c"],['__file',"src/components/feed/FeedItem.vue"]])
/* hot reload */
if (false) // removed by dead control flow
{}


/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (__exports__);

/***/ },

/***/ "./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/feed/FeedItem.vue?vue&type=script&lang=js"
/*!**************************************************************************************************************************************!*\
  !*** ./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/feed/FeedItem.vue?vue&type=script&lang=js ***!
  \**************************************************************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var vue_material_design_icons_CalendarBlank_vue__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! vue-material-design-icons/CalendarBlank.vue */ "./node_modules/vue-material-design-icons/CalendarBlank.vue");
/* harmony import */ var vue_material_design_icons_OpenInNew_vue__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! vue-material-design-icons/OpenInNew.vue */ "./node_modules/vue-material-design-icons/OpenInNew.vue");
/* harmony import */ var vue_material_design_icons_FileWord_vue__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! vue-material-design-icons/FileWord.vue */ "./node_modules/vue-material-design-icons/FileWord.vue");
/* harmony import */ var vue_material_design_icons_FileExcel_vue__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! vue-material-design-icons/FileExcel.vue */ "./node_modules/vue-material-design-icons/FileExcel.vue");
/* harmony import */ var vue_material_design_icons_FilePowerpoint_vue__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! vue-material-design-icons/FilePowerpoint.vue */ "./node_modules/vue-material-design-icons/FilePowerpoint.vue");
/* harmony import */ var vue_material_design_icons_FilePdfBox_vue__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! vue-material-design-icons/FilePdfBox.vue */ "./node_modules/vue-material-design-icons/FilePdfBox.vue");
/* harmony import */ var vue_material_design_icons_FileImage_vue__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! vue-material-design-icons/FileImage.vue */ "./node_modules/vue-material-design-icons/FileImage.vue");
/* harmony import */ var vue_material_design_icons_FileVideo_vue__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! vue-material-design-icons/FileVideo.vue */ "./node_modules/vue-material-design-icons/FileVideo.vue");
/* harmony import */ var vue_material_design_icons_FileDocument_vue__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__(/*! vue-material-design-icons/FileDocument.vue */ "./node_modules/vue-material-design-icons/FileDocument.vue");
/* harmony import */ var vue_material_design_icons_BugOutline_vue__WEBPACK_IMPORTED_MODULE_9__ = __webpack_require__(/*! vue-material-design-icons/BugOutline.vue */ "./node_modules/vue-material-design-icons/BugOutline.vue");
/* harmony import */ var vue_material_design_icons_BookOpenPageVariant_vue__WEBPACK_IMPORTED_MODULE_10__ = __webpack_require__(/*! vue-material-design-icons/BookOpenPageVariant.vue */ "./node_modules/vue-material-design-icons/BookOpenPageVariant.vue");
/* harmony import */ var vue_material_design_icons_MicrosoftSharepoint_vue__WEBPACK_IMPORTED_MODULE_11__ = __webpack_require__(/*! vue-material-design-icons/MicrosoftSharepoint.vue */ "./node_modules/vue-material-design-icons/MicrosoftSharepoint.vue");
/* harmony import */ var vue_material_design_icons_ClipboardText_vue__WEBPACK_IMPORTED_MODULE_12__ = __webpack_require__(/*! vue-material-design-icons/ClipboardText.vue */ "./node_modules/vue-material-design-icons/ClipboardText.vue");
/* harmony import */ var vue_material_design_icons_SchoolOutline_vue__WEBPACK_IMPORTED_MODULE_13__ = __webpack_require__(/*! vue-material-design-icons/SchoolOutline.vue */ "./node_modules/vue-material-design-icons/SchoolOutline.vue");
/* harmony import */ var vue_material_design_icons_RssBox_vue__WEBPACK_IMPORTED_MODULE_14__ = __webpack_require__(/*! vue-material-design-icons/RssBox.vue */ "./node_modules/vue-material-design-icons/RssBox.vue");
/* harmony import */ var vue_material_design_icons_ViewDashboard_vue__WEBPACK_IMPORTED_MODULE_15__ = __webpack_require__(/*! vue-material-design-icons/ViewDashboard.vue */ "./node_modules/vue-material-design-icons/ViewDashboard.vue");


















const FILE_TYPE_MAP = {
  doc: { color: '#2B579A', icon: vue_material_design_icons_FileWord_vue__WEBPACK_IMPORTED_MODULE_2__["default"] },
  docx: { color: '#2B579A', icon: vue_material_design_icons_FileWord_vue__WEBPACK_IMPORTED_MODULE_2__["default"] },
  xls: { color: '#217346', icon: vue_material_design_icons_FileExcel_vue__WEBPACK_IMPORTED_MODULE_3__["default"] },
  xlsx: { color: '#217346', icon: vue_material_design_icons_FileExcel_vue__WEBPACK_IMPORTED_MODULE_3__["default"] },
  csv: { color: '#217346', icon: vue_material_design_icons_FileExcel_vue__WEBPACK_IMPORTED_MODULE_3__["default"] },
  ppt: { color: '#D24726', icon: vue_material_design_icons_FilePowerpoint_vue__WEBPACK_IMPORTED_MODULE_4__["default"] },
  pptx: { color: '#D24726', icon: vue_material_design_icons_FilePowerpoint_vue__WEBPACK_IMPORTED_MODULE_4__["default"] },
  pdf: { color: '#E2574C', icon: vue_material_design_icons_FilePdfBox_vue__WEBPACK_IMPORTED_MODULE_5__["default"] },
  jpg: { color: '#7B83EB', icon: vue_material_design_icons_FileImage_vue__WEBPACK_IMPORTED_MODULE_6__["default"] },
  jpeg: { color: '#7B83EB', icon: vue_material_design_icons_FileImage_vue__WEBPACK_IMPORTED_MODULE_6__["default"] },
  png: { color: '#7B83EB', icon: vue_material_design_icons_FileImage_vue__WEBPACK_IMPORTED_MODULE_6__["default"] },
  gif: { color: '#7B83EB', icon: vue_material_design_icons_FileImage_vue__WEBPACK_IMPORTED_MODULE_6__["default"] },
  webp: { color: '#7B83EB', icon: vue_material_design_icons_FileImage_vue__WEBPACK_IMPORTED_MODULE_6__["default"] },
  svg: { color: '#7B83EB', icon: vue_material_design_icons_FileImage_vue__WEBPACK_IMPORTED_MODULE_6__["default"] },
  mp4: { color: '#8764B8', icon: vue_material_design_icons_FileVideo_vue__WEBPACK_IMPORTED_MODULE_7__["default"] },
  mov: { color: '#8764B8', icon: vue_material_design_icons_FileVideo_vue__WEBPACK_IMPORTED_MODULE_7__["default"] },
  avi: { color: '#8764B8', icon: vue_material_design_icons_FileVideo_vue__WEBPACK_IMPORTED_MODULE_7__["default"] },
  webm: { color: '#8764B8', icon: vue_material_design_icons_FileVideo_vue__WEBPACK_IMPORTED_MODULE_7__["default"] },
};

const CONNECTION_TYPE_MAP = {
  jira: { color: '#0052CC', icon: vue_material_design_icons_BugOutline_vue__WEBPACK_IMPORTED_MODULE_9__["default"] },
  confluence: { color: '#1868DB', icon: vue_material_design_icons_BookOpenPageVariant_vue__WEBPACK_IMPORTED_MODULE_10__["default"] },
  sharepoint: { color: '#038387', icon: vue_material_design_icons_MicrosoftSharepoint_vue__WEBPACK_IMPORTED_MODULE_11__["default"] },
  openproject: { color: '#1A67A3', icon: vue_material_design_icons_ClipboardText_vue__WEBPACK_IMPORTED_MODULE_12__["default"] },
  moodle: { color: '#F98012', icon: vue_material_design_icons_SchoolOutline_vue__WEBPACK_IMPORTED_MODULE_13__["default"] },
  canvas: { color: '#E03E2D', icon: vue_material_design_icons_SchoolOutline_vue__WEBPACK_IMPORTED_MODULE_13__["default"] },
  brightspace: { color: '#F5A623', icon: vue_material_design_icons_SchoolOutline_vue__WEBPACK_IMPORTED_MODULE_13__["default"] },
  rss: { color: '#F26522', icon: vue_material_design_icons_RssBox_vue__WEBPACK_IMPORTED_MODULE_14__["default"] },
  custom: { color: '#6C757D', icon: vue_material_design_icons_ViewDashboard_vue__WEBPACK_IMPORTED_MODULE_15__["default"] },
};

/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = ({
  name: 'FeedItem',
  components: {
    CalendarBlank: vue_material_design_icons_CalendarBlank_vue__WEBPACK_IMPORTED_MODULE_0__["default"],
    OpenInNew: vue_material_design_icons_OpenInNew_vue__WEBPACK_IMPORTED_MODULE_1__["default"],
    FileWord: vue_material_design_icons_FileWord_vue__WEBPACK_IMPORTED_MODULE_2__["default"], FileExcel: vue_material_design_icons_FileExcel_vue__WEBPACK_IMPORTED_MODULE_3__["default"], FilePowerpoint: vue_material_design_icons_FilePowerpoint_vue__WEBPACK_IMPORTED_MODULE_4__["default"], FilePdfBox: vue_material_design_icons_FilePdfBox_vue__WEBPACK_IMPORTED_MODULE_5__["default"], FileImage: vue_material_design_icons_FileImage_vue__WEBPACK_IMPORTED_MODULE_6__["default"], FileVideo: vue_material_design_icons_FileVideo_vue__WEBPACK_IMPORTED_MODULE_7__["default"], FileDocument: vue_material_design_icons_FileDocument_vue__WEBPACK_IMPORTED_MODULE_8__["default"],
    BugOutline: vue_material_design_icons_BugOutline_vue__WEBPACK_IMPORTED_MODULE_9__["default"], BookOpenPageVariant: vue_material_design_icons_BookOpenPageVariant_vue__WEBPACK_IMPORTED_MODULE_10__["default"], MicrosoftSharepoint: vue_material_design_icons_MicrosoftSharepoint_vue__WEBPACK_IMPORTED_MODULE_11__["default"], ClipboardText: vue_material_design_icons_ClipboardText_vue__WEBPACK_IMPORTED_MODULE_12__["default"], SchoolOutline: vue_material_design_icons_SchoolOutline_vue__WEBPACK_IMPORTED_MODULE_13__["default"], RssBox: vue_material_design_icons_RssBox_vue__WEBPACK_IMPORTED_MODULE_14__["default"], ViewDashboard: vue_material_design_icons_ViewDashboard_vue__WEBPACK_IMPORTED_MODULE_15__["default"],
  },
  data() {
    return {
      feedImageError: false,
    };
  },
  props: {
    item: {
      type: Object,
      required: true,
    },
    showImage: {
      type: Boolean,
      default: true,
    },
    feedImage: {
      type: String,
      default: null,
    },
    showDate: {
      type: Boolean,
      default: true,
    },
    showExcerpt: {
      type: Boolean,
      default: true,
    },
    showSource: {
      type: Boolean,
      default: false,
    },
    excerptLength: {
      type: Number,
      default: 150,
    },
    compact: {
      type: Boolean,
      default: false,
    },
    openInNewTab: {
      type: Boolean,
      default: true,
    },
    itemBackground: {
      type: String,
      default: 'default',
      validator: (value) => ['default', 'transparent', 'white', 'dark'].includes(value),
    },
  },
  computed: {
    formattedDate() {
      if (!this.item.date) return '';
      try {
        const date = new Date(this.item.date);
        const locale = document.documentElement.lang || undefined;
        return date.toLocaleDateString(locale, {
          year: 'numeric',
          month: 'short',
          day: 'numeric',
        });
      } catch {
        return this.item.date;
      }
    },
    fallbackMeta() {
      if (this.item.image) return null;
      if (this.feedImage && !this.feedImageError) return null;
      if (this.item.fileType && FILE_TYPE_MAP[this.item.fileType]) {
        return FILE_TYPE_MAP[this.item.fileType];
      }
      if (this.item.connectionType && CONNECTION_TYPE_MAP[this.item.connectionType]) {
        return CONNECTION_TYPE_MAP[this.item.connectionType];
      }
      return { color: '#6C757D', icon: vue_material_design_icons_FileDocument_vue__WEBPACK_IMPORTED_MODULE_8__["default"] };
    },
    truncatedExcerpt() {
      if (!this.item.excerpt) return '';
      if (this.item.excerpt.length <= this.excerptLength) {
        return this.item.excerpt;
      }
      return this.item.excerpt.substring(0, this.excerptLength) + '...';
    },
  },
});


/***/ },

/***/ "./src/components/feed/FeedLayoutGrid.vue"
/*!************************************************!*\
  !*** ./src/components/feed/FeedLayoutGrid.vue ***!
  \************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _FeedLayoutGrid_vue_vue_type_template_id_5b0e091f_scoped_true__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./FeedLayoutGrid.vue?vue&type=template&id=5b0e091f&scoped=true */ "./src/components/feed/FeedLayoutGrid.vue?vue&type=template&id=5b0e091f&scoped=true");
/* harmony import */ var _FeedLayoutGrid_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./FeedLayoutGrid.vue?vue&type=script&lang=js */ "./src/components/feed/FeedLayoutGrid.vue?vue&type=script&lang=js");
/* harmony import */ var _FeedLayoutGrid_vue_vue_type_style_index_0_id_5b0e091f_scoped_true_lang_css__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./FeedLayoutGrid.vue?vue&type=style&index=0&id=5b0e091f&scoped=true&lang=css */ "./src/components/feed/FeedLayoutGrid.vue?vue&type=style&index=0&id=5b0e091f&scoped=true&lang=css");
/* harmony import */ var _node_modules_vue_loader_dist_exportHelper_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ../../../node_modules/vue-loader/dist/exportHelper.js */ "./node_modules/vue-loader/dist/exportHelper.js");




;


const __exports__ = /*#__PURE__*/(0,_node_modules_vue_loader_dist_exportHelper_js__WEBPACK_IMPORTED_MODULE_3__["default"])(_FeedLayoutGrid_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_1__["default"], [['render',_FeedLayoutGrid_vue_vue_type_template_id_5b0e091f_scoped_true__WEBPACK_IMPORTED_MODULE_0__.render],['__scopeId',"data-v-5b0e091f"],['__file',"src/components/feed/FeedLayoutGrid.vue"]])
/* hot reload */
if (false) // removed by dead control flow
{}


/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (__exports__);

/***/ },

/***/ "./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/feed/FeedLayoutGrid.vue?vue&type=script&lang=js"
/*!********************************************************************************************************************************************!*\
  !*** ./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/feed/FeedLayoutGrid.vue?vue&type=script&lang=js ***!
  \********************************************************************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _FeedItem_vue__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./FeedItem.vue */ "./src/components/feed/FeedItem.vue");
/* harmony import */ var _utils_colorUtils_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ../../utils/colorUtils.js */ "./src/utils/colorUtils.js");




/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = ({
  name: 'FeedLayoutGrid',
  components: {
    FeedItem: _FeedItem_vue__WEBPACK_IMPORTED_MODULE_0__["default"],
  },
  props: {
    items: {
      type: Array,
      required: true,
    },
    widget: {
      type: Object,
      required: true,
    },
    feedImage: {
      type: String,
      default: null,
    },
    rowBackgroundColor: {
      type: String,
      default: '',
    },
  },
  computed: {
    gridStyle() {
      const columns = Math.max(2, Math.min(Number(this.widget.columns) || 3, 4));
      return {
        gridTemplateColumns: `repeat(${columns}, 1fr)`,
      };
    },
    effectiveBackgroundColor() {
      return this.widget.backgroundColor || this.rowBackgroundColor || '';
    },
    itemBackgroundMode() {
      const containerBg = this.effectiveBackgroundColor;
      if (!containerBg) return 'default';
      if ((0,_utils_colorUtils_js__WEBPACK_IMPORTED_MODULE_1__.isDarkBackground)(containerBg)) return 'dark';
      if ((0,_utils_colorUtils_js__WEBPACK_IMPORTED_MODULE_1__.isLightBackground)(containerBg)) return 'white';
      return 'default';
    },
  },
});


/***/ },

/***/ "./src/components/feed/FeedLayoutList.vue"
/*!************************************************!*\
  !*** ./src/components/feed/FeedLayoutList.vue ***!
  \************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _FeedLayoutList_vue_vue_type_template_id_cf647b12_scoped_true__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./FeedLayoutList.vue?vue&type=template&id=cf647b12&scoped=true */ "./src/components/feed/FeedLayoutList.vue?vue&type=template&id=cf647b12&scoped=true");
/* harmony import */ var _FeedLayoutList_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./FeedLayoutList.vue?vue&type=script&lang=js */ "./src/components/feed/FeedLayoutList.vue?vue&type=script&lang=js");
/* harmony import */ var _FeedLayoutList_vue_vue_type_style_index_0_id_cf647b12_scoped_true_lang_css__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./FeedLayoutList.vue?vue&type=style&index=0&id=cf647b12&scoped=true&lang=css */ "./src/components/feed/FeedLayoutList.vue?vue&type=style&index=0&id=cf647b12&scoped=true&lang=css");
/* harmony import */ var _node_modules_vue_loader_dist_exportHelper_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ../../../node_modules/vue-loader/dist/exportHelper.js */ "./node_modules/vue-loader/dist/exportHelper.js");




;


const __exports__ = /*#__PURE__*/(0,_node_modules_vue_loader_dist_exportHelper_js__WEBPACK_IMPORTED_MODULE_3__["default"])(_FeedLayoutList_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_1__["default"], [['render',_FeedLayoutList_vue_vue_type_template_id_cf647b12_scoped_true__WEBPACK_IMPORTED_MODULE_0__.render],['__scopeId',"data-v-cf647b12"],['__file',"src/components/feed/FeedLayoutList.vue"]])
/* hot reload */
if (false) // removed by dead control flow
{}


/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (__exports__);

/***/ },

/***/ "./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/feed/FeedLayoutList.vue?vue&type=script&lang=js"
/*!********************************************************************************************************************************************!*\
  !*** ./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/feed/FeedLayoutList.vue?vue&type=script&lang=js ***!
  \********************************************************************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _FeedItem_vue__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./FeedItem.vue */ "./src/components/feed/FeedItem.vue");
/* harmony import */ var _utils_colorUtils_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ../../utils/colorUtils.js */ "./src/utils/colorUtils.js");




/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = ({
  name: 'FeedLayoutList',
  components: {
    FeedItem: _FeedItem_vue__WEBPACK_IMPORTED_MODULE_0__["default"],
  },
  props: {
    items: {
      type: Array,
      required: true,
    },
    widget: {
      type: Object,
      required: true,
    },
    feedImage: {
      type: String,
      default: null,
    },
    rowBackgroundColor: {
      type: String,
      default: '',
    },
  },
  computed: {
    effectiveBackgroundColor() {
      return this.widget.backgroundColor || this.rowBackgroundColor || '';
    },
    itemBackgroundMode() {
      const containerBg = this.effectiveBackgroundColor;
      if (!containerBg) return 'default';
      if ((0,_utils_colorUtils_js__WEBPACK_IMPORTED_MODULE_1__.isDarkBackground)(containerBg)) return 'dark';
      if ((0,_utils_colorUtils_js__WEBPACK_IMPORTED_MODULE_1__.isLightBackground)(containerBg)) return 'white';
      return 'default';
    },
  },
});


/***/ },

/***/ "./src/components/FeedWidget.vue?vue&type=style&index=0&id=642986b2&scoped=true&lang=css"
/*!***********************************************************************************************!*\
  !*** ./src/components/FeedWidget.vue?vue&type=style&index=0&id=642986b2&scoped=true&lang=css ***!
  \***********************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _node_modules_style_loader_dist_cjs_js_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_dist_stylePostLoader_js_node_modules_vue_loader_dist_index_js_ruleSet_1_rules_5_use_0_FeedWidget_vue_vue_type_style_index_0_id_642986b2_scoped_true_lang_css__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../node_modules/style-loader/dist/cjs.js!../../node_modules/css-loader/dist/cjs.js!../../node_modules/vue-loader/dist/stylePostLoader.js!../../node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./FeedWidget.vue?vue&type=style&index=0&id=642986b2&scoped=true&lang=css */ "./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/dist/stylePostLoader.js!./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/FeedWidget.vue?vue&type=style&index=0&id=642986b2&scoped=true&lang=css");


/***/ },

/***/ "./src/components/InlineTextEditor.vue?vue&type=style&index=0&id=0a2d6723&scoped=true&lang=css"
/*!*****************************************************************************************************!*\
  !*** ./src/components/InlineTextEditor.vue?vue&type=style&index=0&id=0a2d6723&scoped=true&lang=css ***!
  \*****************************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _node_modules_style_loader_dist_cjs_js_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_dist_stylePostLoader_js_node_modules_vue_loader_dist_index_js_ruleSet_1_rules_5_use_0_InlineTextEditor_vue_vue_type_style_index_0_id_0a2d6723_scoped_true_lang_css__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../node_modules/style-loader/dist/cjs.js!../../node_modules/css-loader/dist/cjs.js!../../node_modules/vue-loader/dist/stylePostLoader.js!../../node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./InlineTextEditor.vue?vue&type=style&index=0&id=0a2d6723&scoped=true&lang=css */ "./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/dist/stylePostLoader.js!./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/InlineTextEditor.vue?vue&type=style&index=0&id=0a2d6723&scoped=true&lang=css");


/***/ },

/***/ "./src/components/PageTreeSelect.vue?vue&type=style&index=0&id=5efccfd9&scoped=true&lang=css"
/*!***************************************************************************************************!*\
  !*** ./src/components/PageTreeSelect.vue?vue&type=style&index=0&id=5efccfd9&scoped=true&lang=css ***!
  \***************************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _node_modules_style_loader_dist_cjs_js_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_dist_stylePostLoader_js_node_modules_vue_loader_dist_index_js_ruleSet_1_rules_5_use_0_PageTreeSelect_vue_vue_type_style_index_0_id_5efccfd9_scoped_true_lang_css__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../node_modules/style-loader/dist/cjs.js!../../node_modules/css-loader/dist/cjs.js!../../node_modules/vue-loader/dist/stylePostLoader.js!../../node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./PageTreeSelect.vue?vue&type=style&index=0&id=5efccfd9&scoped=true&lang=css */ "./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/dist/stylePostLoader.js!./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/PageTreeSelect.vue?vue&type=style&index=0&id=5efccfd9&scoped=true&lang=css");


/***/ },

/***/ "./src/components/PageTreeSelectItem.vue?vue&type=style&index=0&id=621c650c&scoped=true&lang=css"
/*!*******************************************************************************************************!*\
  !*** ./src/components/PageTreeSelectItem.vue?vue&type=style&index=0&id=621c650c&scoped=true&lang=css ***!
  \*******************************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _node_modules_style_loader_dist_cjs_js_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_dist_stylePostLoader_js_node_modules_vue_loader_dist_index_js_ruleSet_1_rules_5_use_0_PageTreeSelectItem_vue_vue_type_style_index_0_id_621c650c_scoped_true_lang_css__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../node_modules/style-loader/dist/cjs.js!../../node_modules/css-loader/dist/cjs.js!../../node_modules/vue-loader/dist/stylePostLoader.js!../../node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./PageTreeSelectItem.vue?vue&type=style&index=0&id=621c650c&scoped=true&lang=css */ "./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/dist/stylePostLoader.js!./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/PageTreeSelectItem.vue?vue&type=style&index=0&id=621c650c&scoped=true&lang=css");


/***/ },

/***/ "./src/components/feed/FeedItem.vue?vue&type=style&index=0&id=82e10b3c&scoped=true&lang=css"
/*!**************************************************************************************************!*\
  !*** ./src/components/feed/FeedItem.vue?vue&type=style&index=0&id=82e10b3c&scoped=true&lang=css ***!
  \**************************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _node_modules_style_loader_dist_cjs_js_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_dist_stylePostLoader_js_node_modules_vue_loader_dist_index_js_ruleSet_1_rules_5_use_0_FeedItem_vue_vue_type_style_index_0_id_82e10b3c_scoped_true_lang_css__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../node_modules/style-loader/dist/cjs.js!../../../node_modules/css-loader/dist/cjs.js!../../../node_modules/vue-loader/dist/stylePostLoader.js!../../../node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./FeedItem.vue?vue&type=style&index=0&id=82e10b3c&scoped=true&lang=css */ "./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/dist/stylePostLoader.js!./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/feed/FeedItem.vue?vue&type=style&index=0&id=82e10b3c&scoped=true&lang=css");


/***/ },

/***/ "./src/components/feed/FeedLayoutGrid.vue?vue&type=style&index=0&id=5b0e091f&scoped=true&lang=css"
/*!********************************************************************************************************!*\
  !*** ./src/components/feed/FeedLayoutGrid.vue?vue&type=style&index=0&id=5b0e091f&scoped=true&lang=css ***!
  \********************************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _node_modules_style_loader_dist_cjs_js_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_dist_stylePostLoader_js_node_modules_vue_loader_dist_index_js_ruleSet_1_rules_5_use_0_FeedLayoutGrid_vue_vue_type_style_index_0_id_5b0e091f_scoped_true_lang_css__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../node_modules/style-loader/dist/cjs.js!../../../node_modules/css-loader/dist/cjs.js!../../../node_modules/vue-loader/dist/stylePostLoader.js!../../../node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./FeedLayoutGrid.vue?vue&type=style&index=0&id=5b0e091f&scoped=true&lang=css */ "./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/dist/stylePostLoader.js!./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/feed/FeedLayoutGrid.vue?vue&type=style&index=0&id=5b0e091f&scoped=true&lang=css");


/***/ },

/***/ "./src/components/feed/FeedLayoutList.vue?vue&type=style&index=0&id=cf647b12&scoped=true&lang=css"
/*!********************************************************************************************************!*\
  !*** ./src/components/feed/FeedLayoutList.vue?vue&type=style&index=0&id=cf647b12&scoped=true&lang=css ***!
  \********************************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _node_modules_style_loader_dist_cjs_js_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_dist_stylePostLoader_js_node_modules_vue_loader_dist_index_js_ruleSet_1_rules_5_use_0_FeedLayoutList_vue_vue_type_style_index_0_id_cf647b12_scoped_true_lang_css__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../node_modules/style-loader/dist/cjs.js!../../../node_modules/css-loader/dist/cjs.js!../../../node_modules/vue-loader/dist/stylePostLoader.js!../../../node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./FeedLayoutList.vue?vue&type=style&index=0&id=cf647b12&scoped=true&lang=css */ "./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/dist/stylePostLoader.js!./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/feed/FeedLayoutList.vue?vue&type=style&index=0&id=cf647b12&scoped=true&lang=css");


/***/ },

/***/ "./src/components/FeedWidget.vue?vue&type=script&lang=js"
/*!***************************************************************!*\
  !*** ./src/components/FeedWidget.vue?vue&type=script&lang=js ***!
  \***************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* reexport safe */ _node_modules_vue_loader_dist_index_js_ruleSet_1_rules_5_use_0_FeedWidget_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_0__["default"])
/* harmony export */ });
/* harmony import */ var _node_modules_vue_loader_dist_index_js_ruleSet_1_rules_5_use_0_FeedWidget_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./FeedWidget.vue?vue&type=script&lang=js */ "./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/FeedWidget.vue?vue&type=script&lang=js");
 

/***/ },

/***/ "./src/components/InlineTextEditor.vue?vue&type=script&lang=js"
/*!*********************************************************************!*\
  !*** ./src/components/InlineTextEditor.vue?vue&type=script&lang=js ***!
  \*********************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* reexport safe */ _node_modules_vue_loader_dist_index_js_ruleSet_1_rules_5_use_0_InlineTextEditor_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_0__["default"])
/* harmony export */ });
/* harmony import */ var _node_modules_vue_loader_dist_index_js_ruleSet_1_rules_5_use_0_InlineTextEditor_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./InlineTextEditor.vue?vue&type=script&lang=js */ "./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/InlineTextEditor.vue?vue&type=script&lang=js");
 

/***/ },

/***/ "./src/components/PageTreeSelect.vue?vue&type=script&lang=js"
/*!*******************************************************************!*\
  !*** ./src/components/PageTreeSelect.vue?vue&type=script&lang=js ***!
  \*******************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* reexport safe */ _node_modules_vue_loader_dist_index_js_ruleSet_1_rules_5_use_0_PageTreeSelect_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_0__["default"])
/* harmony export */ });
/* harmony import */ var _node_modules_vue_loader_dist_index_js_ruleSet_1_rules_5_use_0_PageTreeSelect_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./PageTreeSelect.vue?vue&type=script&lang=js */ "./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/PageTreeSelect.vue?vue&type=script&lang=js");
 

/***/ },

/***/ "./src/components/PageTreeSelectItem.vue?vue&type=script&lang=js"
/*!***********************************************************************!*\
  !*** ./src/components/PageTreeSelectItem.vue?vue&type=script&lang=js ***!
  \***********************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* reexport safe */ _node_modules_vue_loader_dist_index_js_ruleSet_1_rules_5_use_0_PageTreeSelectItem_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_0__["default"])
/* harmony export */ });
/* harmony import */ var _node_modules_vue_loader_dist_index_js_ruleSet_1_rules_5_use_0_PageTreeSelectItem_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./PageTreeSelectItem.vue?vue&type=script&lang=js */ "./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/PageTreeSelectItem.vue?vue&type=script&lang=js");
 

/***/ },

/***/ "./src/components/feed/FeedItem.vue?vue&type=script&lang=js"
/*!******************************************************************!*\
  !*** ./src/components/feed/FeedItem.vue?vue&type=script&lang=js ***!
  \******************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* reexport safe */ _node_modules_vue_loader_dist_index_js_ruleSet_1_rules_5_use_0_FeedItem_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_0__["default"])
/* harmony export */ });
/* harmony import */ var _node_modules_vue_loader_dist_index_js_ruleSet_1_rules_5_use_0_FeedItem_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./FeedItem.vue?vue&type=script&lang=js */ "./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/feed/FeedItem.vue?vue&type=script&lang=js");
 

/***/ },

/***/ "./src/components/feed/FeedLayoutGrid.vue?vue&type=script&lang=js"
/*!************************************************************************!*\
  !*** ./src/components/feed/FeedLayoutGrid.vue?vue&type=script&lang=js ***!
  \************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* reexport safe */ _node_modules_vue_loader_dist_index_js_ruleSet_1_rules_5_use_0_FeedLayoutGrid_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_0__["default"])
/* harmony export */ });
/* harmony import */ var _node_modules_vue_loader_dist_index_js_ruleSet_1_rules_5_use_0_FeedLayoutGrid_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./FeedLayoutGrid.vue?vue&type=script&lang=js */ "./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/feed/FeedLayoutGrid.vue?vue&type=script&lang=js");
 

/***/ },

/***/ "./src/components/feed/FeedLayoutList.vue?vue&type=script&lang=js"
/*!************************************************************************!*\
  !*** ./src/components/feed/FeedLayoutList.vue?vue&type=script&lang=js ***!
  \************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* reexport safe */ _node_modules_vue_loader_dist_index_js_ruleSet_1_rules_5_use_0_FeedLayoutList_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_0__["default"])
/* harmony export */ });
/* harmony import */ var _node_modules_vue_loader_dist_index_js_ruleSet_1_rules_5_use_0_FeedLayoutList_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./FeedLayoutList.vue?vue&type=script&lang=js */ "./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/feed/FeedLayoutList.vue?vue&type=script&lang=js");
 

/***/ },

/***/ "./src/components/FeedWidget.vue?vue&type=template&id=642986b2&scoped=true"
/*!*********************************************************************************!*\
  !*** ./src/components/FeedWidget.vue?vue&type=template&id=642986b2&scoped=true ***!
  \*********************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   render: () => (/* reexport safe */ _node_modules_vue_loader_dist_templateLoader_js_ruleSet_1_rules_2_node_modules_vue_loader_dist_index_js_ruleSet_1_rules_5_use_0_FeedWidget_vue_vue_type_template_id_642986b2_scoped_true__WEBPACK_IMPORTED_MODULE_0__.render)
/* harmony export */ });
/* harmony import */ var _node_modules_vue_loader_dist_templateLoader_js_ruleSet_1_rules_2_node_modules_vue_loader_dist_index_js_ruleSet_1_rules_5_use_0_FeedWidget_vue_vue_type_template_id_642986b2_scoped_true__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../node_modules/vue-loader/dist/templateLoader.js??ruleSet[1].rules[2]!../../node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./FeedWidget.vue?vue&type=template&id=642986b2&scoped=true */ "./node_modules/vue-loader/dist/templateLoader.js??ruleSet[1].rules[2]!./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/FeedWidget.vue?vue&type=template&id=642986b2&scoped=true");


/***/ },

/***/ "./src/components/InlineTextEditor.vue?vue&type=template&id=0a2d6723&scoped=true"
/*!***************************************************************************************!*\
  !*** ./src/components/InlineTextEditor.vue?vue&type=template&id=0a2d6723&scoped=true ***!
  \***************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   render: () => (/* reexport safe */ _node_modules_vue_loader_dist_templateLoader_js_ruleSet_1_rules_2_node_modules_vue_loader_dist_index_js_ruleSet_1_rules_5_use_0_InlineTextEditor_vue_vue_type_template_id_0a2d6723_scoped_true__WEBPACK_IMPORTED_MODULE_0__.render)
/* harmony export */ });
/* harmony import */ var _node_modules_vue_loader_dist_templateLoader_js_ruleSet_1_rules_2_node_modules_vue_loader_dist_index_js_ruleSet_1_rules_5_use_0_InlineTextEditor_vue_vue_type_template_id_0a2d6723_scoped_true__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../node_modules/vue-loader/dist/templateLoader.js??ruleSet[1].rules[2]!../../node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./InlineTextEditor.vue?vue&type=template&id=0a2d6723&scoped=true */ "./node_modules/vue-loader/dist/templateLoader.js??ruleSet[1].rules[2]!./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/InlineTextEditor.vue?vue&type=template&id=0a2d6723&scoped=true");


/***/ },

/***/ "./src/components/PageTreeSelect.vue?vue&type=template&id=5efccfd9&scoped=true"
/*!*************************************************************************************!*\
  !*** ./src/components/PageTreeSelect.vue?vue&type=template&id=5efccfd9&scoped=true ***!
  \*************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   render: () => (/* reexport safe */ _node_modules_vue_loader_dist_templateLoader_js_ruleSet_1_rules_2_node_modules_vue_loader_dist_index_js_ruleSet_1_rules_5_use_0_PageTreeSelect_vue_vue_type_template_id_5efccfd9_scoped_true__WEBPACK_IMPORTED_MODULE_0__.render)
/* harmony export */ });
/* harmony import */ var _node_modules_vue_loader_dist_templateLoader_js_ruleSet_1_rules_2_node_modules_vue_loader_dist_index_js_ruleSet_1_rules_5_use_0_PageTreeSelect_vue_vue_type_template_id_5efccfd9_scoped_true__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../node_modules/vue-loader/dist/templateLoader.js??ruleSet[1].rules[2]!../../node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./PageTreeSelect.vue?vue&type=template&id=5efccfd9&scoped=true */ "./node_modules/vue-loader/dist/templateLoader.js??ruleSet[1].rules[2]!./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/PageTreeSelect.vue?vue&type=template&id=5efccfd9&scoped=true");


/***/ },

/***/ "./src/components/PageTreeSelectItem.vue?vue&type=template&id=621c650c&scoped=true"
/*!*****************************************************************************************!*\
  !*** ./src/components/PageTreeSelectItem.vue?vue&type=template&id=621c650c&scoped=true ***!
  \*****************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   render: () => (/* reexport safe */ _node_modules_vue_loader_dist_templateLoader_js_ruleSet_1_rules_2_node_modules_vue_loader_dist_index_js_ruleSet_1_rules_5_use_0_PageTreeSelectItem_vue_vue_type_template_id_621c650c_scoped_true__WEBPACK_IMPORTED_MODULE_0__.render)
/* harmony export */ });
/* harmony import */ var _node_modules_vue_loader_dist_templateLoader_js_ruleSet_1_rules_2_node_modules_vue_loader_dist_index_js_ruleSet_1_rules_5_use_0_PageTreeSelectItem_vue_vue_type_template_id_621c650c_scoped_true__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../node_modules/vue-loader/dist/templateLoader.js??ruleSet[1].rules[2]!../../node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./PageTreeSelectItem.vue?vue&type=template&id=621c650c&scoped=true */ "./node_modules/vue-loader/dist/templateLoader.js??ruleSet[1].rules[2]!./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/PageTreeSelectItem.vue?vue&type=template&id=621c650c&scoped=true");


/***/ },

/***/ "./src/components/feed/FeedItem.vue?vue&type=template&id=82e10b3c&scoped=true"
/*!************************************************************************************!*\
  !*** ./src/components/feed/FeedItem.vue?vue&type=template&id=82e10b3c&scoped=true ***!
  \************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   render: () => (/* reexport safe */ _node_modules_vue_loader_dist_templateLoader_js_ruleSet_1_rules_2_node_modules_vue_loader_dist_index_js_ruleSet_1_rules_5_use_0_FeedItem_vue_vue_type_template_id_82e10b3c_scoped_true__WEBPACK_IMPORTED_MODULE_0__.render)
/* harmony export */ });
/* harmony import */ var _node_modules_vue_loader_dist_templateLoader_js_ruleSet_1_rules_2_node_modules_vue_loader_dist_index_js_ruleSet_1_rules_5_use_0_FeedItem_vue_vue_type_template_id_82e10b3c_scoped_true__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../node_modules/vue-loader/dist/templateLoader.js??ruleSet[1].rules[2]!../../../node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./FeedItem.vue?vue&type=template&id=82e10b3c&scoped=true */ "./node_modules/vue-loader/dist/templateLoader.js??ruleSet[1].rules[2]!./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/feed/FeedItem.vue?vue&type=template&id=82e10b3c&scoped=true");


/***/ },

/***/ "./src/components/feed/FeedLayoutGrid.vue?vue&type=template&id=5b0e091f&scoped=true"
/*!******************************************************************************************!*\
  !*** ./src/components/feed/FeedLayoutGrid.vue?vue&type=template&id=5b0e091f&scoped=true ***!
  \******************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   render: () => (/* reexport safe */ _node_modules_vue_loader_dist_templateLoader_js_ruleSet_1_rules_2_node_modules_vue_loader_dist_index_js_ruleSet_1_rules_5_use_0_FeedLayoutGrid_vue_vue_type_template_id_5b0e091f_scoped_true__WEBPACK_IMPORTED_MODULE_0__.render)
/* harmony export */ });
/* harmony import */ var _node_modules_vue_loader_dist_templateLoader_js_ruleSet_1_rules_2_node_modules_vue_loader_dist_index_js_ruleSet_1_rules_5_use_0_FeedLayoutGrid_vue_vue_type_template_id_5b0e091f_scoped_true__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../node_modules/vue-loader/dist/templateLoader.js??ruleSet[1].rules[2]!../../../node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./FeedLayoutGrid.vue?vue&type=template&id=5b0e091f&scoped=true */ "./node_modules/vue-loader/dist/templateLoader.js??ruleSet[1].rules[2]!./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/feed/FeedLayoutGrid.vue?vue&type=template&id=5b0e091f&scoped=true");


/***/ },

/***/ "./src/components/feed/FeedLayoutList.vue?vue&type=template&id=cf647b12&scoped=true"
/*!******************************************************************************************!*\
  !*** ./src/components/feed/FeedLayoutList.vue?vue&type=template&id=cf647b12&scoped=true ***!
  \******************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   render: () => (/* reexport safe */ _node_modules_vue_loader_dist_templateLoader_js_ruleSet_1_rules_2_node_modules_vue_loader_dist_index_js_ruleSet_1_rules_5_use_0_FeedLayoutList_vue_vue_type_template_id_cf647b12_scoped_true__WEBPACK_IMPORTED_MODULE_0__.render)
/* harmony export */ });
/* harmony import */ var _node_modules_vue_loader_dist_templateLoader_js_ruleSet_1_rules_2_node_modules_vue_loader_dist_index_js_ruleSet_1_rules_5_use_0_FeedLayoutList_vue_vue_type_template_id_cf647b12_scoped_true__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../node_modules/vue-loader/dist/templateLoader.js??ruleSet[1].rules[2]!../../../node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./FeedLayoutList.vue?vue&type=template&id=cf647b12&scoped=true */ "./node_modules/vue-loader/dist/templateLoader.js??ruleSet[1].rules[2]!./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/feed/FeedLayoutList.vue?vue&type=template&id=cf647b12&scoped=true");


/***/ },

/***/ "./node_modules/vue-loader/dist/templateLoader.js??ruleSet[1].rules[2]!./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/FeedWidget.vue?vue&type=template&id=642986b2&scoped=true"
/*!***************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/vue-loader/dist/templateLoader.js??ruleSet[1].rules[2]!./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/FeedWidget.vue?vue&type=template&id=642986b2&scoped=true ***!
  \***************************************************************************************************************************************************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   render: () => (/* binding */ render)
/* harmony export */ });
/* harmony import */ var vue__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! vue */ "./node_modules/vue/dist/vue.runtime.esm-bundler.js");


const _hoisted_1 = {
  class: "feed-widget",
  "aria-live": "polite"
}
const _hoisted_2 = {
  key: 0,
  class: "feed-widget-loading",
  role: "status"
}
const _hoisted_3 = {
  key: 1,
  class: "feed-widget-error"
}
const _hoisted_4 = {
  key: 2,
  class: "feed-widget-empty"
}
const _hoisted_5 = { key: 0 }
const _hoisted_6 = { key: 1 }

function render(_ctx, _cache, $props, $setup, $data, $options) {
  const _component_NcLoadingIcon = (0,vue__WEBPACK_IMPORTED_MODULE_0__.resolveComponent)("NcLoadingIcon")
  const _component_AlertCircle = (0,vue__WEBPACK_IMPORTED_MODULE_0__.resolveComponent)("AlertCircle")
  const _component_RssBox = (0,vue__WEBPACK_IMPORTED_MODULE_0__.resolveComponent)("RssBox")

  return ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)("div", _hoisted_1, [
    ($data.loading)
      ? ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)("div", _hoisted_2, [
          (0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_NcLoadingIcon, { size: 32 }),
          (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("p", null, (0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)($options.t('Loading feed...')), 1 /* TEXT */)
        ]))
      : ($data.error)
        ? ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)("div", _hoisted_3, [
            (0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_AlertCircle, { size: 32 }),
            (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("p", null, (0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)($data.error), 1 /* TEXT */)
          ]))
        : ($data.items.length === 0)
          ? ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)("div", _hoisted_4, [
              (0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_RssBox, { size: 32 }),
              ($props.widget.filterKeyword)
                ? ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)("p", _hoisted_5, (0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)($options.t('No items match your filter.')), 1 /* TEXT */))
                : ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)("p", _hoisted_6, (0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)($options.t('No items found')), 1 /* TEXT */))
            ]))
          : ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createBlock)((0,vue__WEBPACK_IMPORTED_MODULE_0__.resolveDynamicComponent)($options.layoutComponent), {
              key: 3,
              items: $data.items,
              widget: $props.widget,
              "feed-image": $data.feedImage,
              "row-background-color": $props.rowBackgroundColor
            }, null, 8 /* PROPS */, ["items", "widget", "feed-image", "row-background-color"]))
  ]))
}

/***/ },

/***/ "./node_modules/vue-loader/dist/templateLoader.js??ruleSet[1].rules[2]!./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/InlineTextEditor.vue?vue&type=template&id=0a2d6723&scoped=true"
/*!*********************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/vue-loader/dist/templateLoader.js??ruleSet[1].rules[2]!./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/InlineTextEditor.vue?vue&type=template&id=0a2d6723&scoped=true ***!
  \*********************************************************************************************************************************************************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   render: () => (/* binding */ render)
/* harmony export */ });
/* harmony import */ var vue__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! vue */ "./node_modules/vue/dist/vue.runtime.esm-bundler.js");


const _hoisted_1 = ["title", "aria-label"]
const _hoisted_2 = ["title", "aria-label"]
const _hoisted_3 = ["title", "aria-label"]
const _hoisted_4 = {
  key: 0,
  class: "more-dropdown"
}
const _hoisted_5 = ["title", "aria-label"]
const _hoisted_6 = {
  key: 0,
  class: "dropdown-menu more-menu"
}
const _hoisted_7 = ["onMousedown"]
const _hoisted_8 = ["title", "aria-label"]
const _hoisted_9 = { class: "heading-dropdown" }
const _hoisted_10 = ["title", "aria-label"]
const _hoisted_11 = {
  key: 0,
  class: "dropdown-menu"
}
const _hoisted_12 = ["onMousedown"]
const _hoisted_13 = ["title", "aria-label"]
const _hoisted_14 = ["title", "aria-label"]
const _hoisted_15 = ["title", "aria-label"]
const _hoisted_16 = { class: "alignment-dropdown" }
const _hoisted_17 = ["title", "aria-label"]
const _hoisted_18 = {
  key: 0,
  class: "dropdown-menu"
}
const _hoisted_19 = ["onMousedown"]
const _hoisted_20 = ["title", "aria-label"]
const _hoisted_21 = { class: "table-dropdown" }
const _hoisted_22 = ["title", "aria-label"]
const _hoisted_23 = {
  key: 0,
  class: "dropdown-menu table-menu"
}
const _hoisted_24 = { class: "dropdown-menu-section-label" }
const _hoisted_25 = { class: "dropdown-menu-row" }
const _hoisted_26 = ["onMousedown"]
const _hoisted_27 = { class: "dropdown-menu-section-label" }
const _hoisted_28 = { class: "dropdown-menu-row" }
const _hoisted_29 = ["title"]
const _hoisted_30 = ["title"]
const _hoisted_31 = ["title"]
const _hoisted_32 = { class: "link-modal-content" }
const _hoisted_33 = { class: "form-group" }
const _hoisted_34 = { for: "link-text" }
const _hoisted_35 = ["placeholder"]
const _hoisted_36 = { class: "form-group" }
const _hoisted_37 = { for: "link-url" }
const _hoisted_38 = ["placeholder"]
const _hoisted_39 = { class: "modal-buttons" }

function render(_ctx, _cache, $props, $setup, $data, $options) {
  const _component_FormatBold = (0,vue__WEBPACK_IMPORTED_MODULE_0__.resolveComponent)("FormatBold")
  const _component_FormatItalic = (0,vue__WEBPACK_IMPORTED_MODULE_0__.resolveComponent)("FormatItalic")
  const _component_FormatUnderline = (0,vue__WEBPACK_IMPORTED_MODULE_0__.resolveComponent)("FormatUnderline")
  const _component_DotsHorizontal = (0,vue__WEBPACK_IMPORTED_MODULE_0__.resolveComponent)("DotsHorizontal")
  const _component_FormatStrikethrough = (0,vue__WEBPACK_IMPORTED_MODULE_0__.resolveComponent)("FormatStrikethrough")
  const _component_FormatListBulleted = (0,vue__WEBPACK_IMPORTED_MODULE_0__.resolveComponent)("FormatListBulleted")
  const _component_FormatListNumbered = (0,vue__WEBPACK_IMPORTED_MODULE_0__.resolveComponent)("FormatListNumbered")
  const _component_FormatQuoteClose = (0,vue__WEBPACK_IMPORTED_MODULE_0__.resolveComponent)("FormatQuoteClose")
  const _component_FormatAlignLeft = (0,vue__WEBPACK_IMPORTED_MODULE_0__.resolveComponent)("FormatAlignLeft")
  const _component_FormatAlignCenter = (0,vue__WEBPACK_IMPORTED_MODULE_0__.resolveComponent)("FormatAlignCenter")
  const _component_FormatAlignRight = (0,vue__WEBPACK_IMPORTED_MODULE_0__.resolveComponent)("FormatAlignRight")
  const _component_LinkVariant = (0,vue__WEBPACK_IMPORTED_MODULE_0__.resolveComponent)("LinkVariant")
  const _component_TableIcon = (0,vue__WEBPACK_IMPORTED_MODULE_0__.resolveComponent)("TableIcon")
  const _component_TableRowPlusAfter = (0,vue__WEBPACK_IMPORTED_MODULE_0__.resolveComponent)("TableRowPlusAfter")
  const _component_TableColumnPlusAfter = (0,vue__WEBPACK_IMPORTED_MODULE_0__.resolveComponent)("TableColumnPlusAfter")
  const _component_TableRemove = (0,vue__WEBPACK_IMPORTED_MODULE_0__.resolveComponent)("TableRemove")
  const _component_ChevronDown = (0,vue__WEBPACK_IMPORTED_MODULE_0__.resolveComponent)("ChevronDown")
  const _component_TableRowPlusBefore = (0,vue__WEBPACK_IMPORTED_MODULE_0__.resolveComponent)("TableRowPlusBefore")
  const _component_TableColumnPlusBefore = (0,vue__WEBPACK_IMPORTED_MODULE_0__.resolveComponent)("TableColumnPlusBefore")
  const _component_TableRowRemove = (0,vue__WEBPACK_IMPORTED_MODULE_0__.resolveComponent)("TableRowRemove")
  const _component_TableColumnRemove = (0,vue__WEBPACK_IMPORTED_MODULE_0__.resolveComponent)("TableColumnRemove")
  const _component_NcButton = (0,vue__WEBPACK_IMPORTED_MODULE_0__.resolveComponent)("NcButton")
  const _component_NcModal = (0,vue__WEBPACK_IMPORTED_MODULE_0__.resolveComponent)("NcModal")

  return ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)("div", {
    class: (0,vue__WEBPACK_IMPORTED_MODULE_0__.normalizeClass)(["inline-text-editor", { 'is-focused': $data.isFocused }])
  }, [
    (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)(" Floating Toolbar "),
    ($data.editor && $data.isFocused && $data.showToolbar)
      ? ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)("div", {
          key: 0,
          class: (0,vue__WEBPACK_IMPORTED_MODULE_0__.normalizeClass)(["text-menubar", { 'text-menubar--compact': $props.compact }])
        }, [
          (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)(" Text Formatting - Always visible "),
          (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("button", {
            type: "button",
            onMousedown: _cache[0] || (_cache[0] = (0,vue__WEBPACK_IMPORTED_MODULE_0__.withModifiers)($event => ($data.editor.chain().focus().toggleBold().run()), ["prevent"])),
            class: (0,vue__WEBPACK_IMPORTED_MODULE_0__.normalizeClass)([{ 'is-active': $data.editor.isActive('bold') }, "menubar-button"]),
            title: $options.t('Bold (Ctrl+B)'),
            "aria-label": $options.t('Bold (Ctrl+B)')
          }, [
            (0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_FormatBold, { size: 18 })
          ], 42 /* CLASS, PROPS, NEED_HYDRATION */, _hoisted_1),
          (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("button", {
            type: "button",
            onMousedown: _cache[1] || (_cache[1] = (0,vue__WEBPACK_IMPORTED_MODULE_0__.withModifiers)($event => ($data.editor.chain().focus().toggleItalic().run()), ["prevent"])),
            class: (0,vue__WEBPACK_IMPORTED_MODULE_0__.normalizeClass)([{ 'is-active': $data.editor.isActive('italic') }, "menubar-button"]),
            title: $options.t('Italic (Ctrl+I)'),
            "aria-label": $options.t('Italic (Ctrl+I)')
          }, [
            (0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_FormatItalic, { size: 18 })
          ], 42 /* CLASS, PROPS, NEED_HYDRATION */, _hoisted_2),
          (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("button", {
            type: "button",
            onMousedown: _cache[2] || (_cache[2] = (0,vue__WEBPACK_IMPORTED_MODULE_0__.withModifiers)($event => ($data.editor.chain().focus().toggleUnderline().run()), ["prevent"])),
            class: (0,vue__WEBPACK_IMPORTED_MODULE_0__.normalizeClass)([{ 'is-active': $data.editor.isActive('underline') }, "menubar-button"]),
            title: $options.t('Underline (Ctrl+U)'),
            "aria-label": $options.t('Underline (Ctrl+U)')
          }, [
            (0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_FormatUnderline, { size: 18 })
          ], 42 /* CLASS, PROPS, NEED_HYDRATION */, _hoisted_3),
          (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)(" COMPACT MODE: \"More\" dropdown with all other options "),
          ($props.compact)
            ? ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)("div", _hoisted_4, [
                (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("button", {
                  ref: "moreButton",
                  type: "button",
                  onMousedown: _cache[3] || (_cache[3] = (0,vue__WEBPACK_IMPORTED_MODULE_0__.withModifiers)((...args) => ($options.toggleMoreMenu && $options.toggleMoreMenu(...args)), ["prevent"])),
                  title: $options.t('More options'),
                  "aria-label": $options.t('More options'),
                  class: "menubar-button"
                }, [
                  (0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_DotsHorizontal, { size: 18 })
                ], 40 /* PROPS, NEED_HYDRATION */, _hoisted_5),
                ($data.showMoreMenu)
                  ? ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)("div", _hoisted_6, [
                      (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)(" Strikethrough "),
                      (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("button", {
                        type: "button",
                        onMousedown: _cache[4] || (_cache[4] = (0,vue__WEBPACK_IMPORTED_MODULE_0__.withModifiers)($event => {$data.editor.chain().focus().toggleStrike().run(); $data.showMoreMenu = false}, ["prevent"])),
                        class: (0,vue__WEBPACK_IMPORTED_MODULE_0__.normalizeClass)([{ 'is-active': $data.editor.isActive('strike') }, "dropdown-menu-item"])
                      }, [
                        (0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_FormatStrikethrough, { size: 16 }),
                        (0,vue__WEBPACK_IMPORTED_MODULE_0__.createTextVNode)(" " + (0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)($options.t('Strikethrough')), 1 /* TEXT */)
                      ], 34 /* CLASS, NEED_HYDRATION */),
                      _cache[40] || (_cache[40] = (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("div", { class: "dropdown-divider" }, null, -1 /* CACHED */)),
                      (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)(" Headings "),
                      ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)(vue__WEBPACK_IMPORTED_MODULE_0__.Fragment, null, (0,vue__WEBPACK_IMPORTED_MODULE_0__.renderList)([0, 1, 2, 3, 4], (level) => {
                        return (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("button", {
                          key: level,
                          type: "button",
                          onMousedown: (0,vue__WEBPACK_IMPORTED_MODULE_0__.withModifiers)($event => {$options.setHeadingLevel(level); $data.showMoreMenu = false}, ["prevent"]),
                          class: (0,vue__WEBPACK_IMPORTED_MODULE_0__.normalizeClass)([{ 'is-active': $options.isHeadingLevel(level) }, "dropdown-menu-item"])
                        }, (0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)($options.getHeadingLabel(level)), 43 /* TEXT, CLASS, PROPS, NEED_HYDRATION */, _hoisted_7)
                      }), 64 /* STABLE_FRAGMENT */)),
                      _cache[41] || (_cache[41] = (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("div", { class: "dropdown-divider" }, null, -1 /* CACHED */)),
                      (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)(" Lists & Blockquote "),
                      (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("button", {
                        type: "button",
                        onMousedown: _cache[5] || (_cache[5] = (0,vue__WEBPACK_IMPORTED_MODULE_0__.withModifiers)($event => {$data.editor.chain().focus().toggleBulletList().run(); $data.showMoreMenu = false}, ["prevent"])),
                        class: (0,vue__WEBPACK_IMPORTED_MODULE_0__.normalizeClass)([{ 'is-active': $data.editor.isActive('bulletList') }, "dropdown-menu-item"])
                      }, [
                        (0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_FormatListBulleted, { size: 16 }),
                        (0,vue__WEBPACK_IMPORTED_MODULE_0__.createTextVNode)(" " + (0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)($options.t('Bullet list')), 1 /* TEXT */)
                      ], 34 /* CLASS, NEED_HYDRATION */),
                      (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("button", {
                        type: "button",
                        onMousedown: _cache[6] || (_cache[6] = (0,vue__WEBPACK_IMPORTED_MODULE_0__.withModifiers)($event => {$data.editor.chain().focus().toggleOrderedList().run(); $data.showMoreMenu = false}, ["prevent"])),
                        class: (0,vue__WEBPACK_IMPORTED_MODULE_0__.normalizeClass)([{ 'is-active': $data.editor.isActive('orderedList') }, "dropdown-menu-item"])
                      }, [
                        (0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_FormatListNumbered, { size: 16 }),
                        (0,vue__WEBPACK_IMPORTED_MODULE_0__.createTextVNode)(" " + (0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)($options.t('Numbered list')), 1 /* TEXT */)
                      ], 34 /* CLASS, NEED_HYDRATION */),
                      (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("button", {
                        type: "button",
                        onMousedown: _cache[7] || (_cache[7] = (0,vue__WEBPACK_IMPORTED_MODULE_0__.withModifiers)($event => {$data.editor.chain().focus().toggleBlockquote().run(); $data.showMoreMenu = false}, ["prevent"])),
                        class: (0,vue__WEBPACK_IMPORTED_MODULE_0__.normalizeClass)([{ 'is-active': $data.editor.isActive('blockquote') }, "dropdown-menu-item"])
                      }, [
                        (0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_FormatQuoteClose, { size: 16 }),
                        (0,vue__WEBPACK_IMPORTED_MODULE_0__.createTextVNode)(" " + (0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)($options.t('Blockquote')), 1 /* TEXT */)
                      ], 34 /* CLASS, NEED_HYDRATION */),
                      _cache[42] || (_cache[42] = (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("div", { class: "dropdown-divider" }, null, -1 /* CACHED */)),
                      (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)(" Text Alignment "),
                      (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("button", {
                        type: "button",
                        onMousedown: _cache[8] || (_cache[8] = (0,vue__WEBPACK_IMPORTED_MODULE_0__.withModifiers)($event => {$options.setAlignment('left'); $data.showMoreMenu = false}, ["prevent"])),
                        class: (0,vue__WEBPACK_IMPORTED_MODULE_0__.normalizeClass)([{ 'is-active': $options.isAlignmentActive('left') }, "dropdown-menu-item"])
                      }, [
                        (0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_FormatAlignLeft, { size: 16 }),
                        (0,vue__WEBPACK_IMPORTED_MODULE_0__.createTextVNode)(" " + (0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)($options.t('Align left')), 1 /* TEXT */)
                      ], 34 /* CLASS, NEED_HYDRATION */),
                      (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("button", {
                        type: "button",
                        onMousedown: _cache[9] || (_cache[9] = (0,vue__WEBPACK_IMPORTED_MODULE_0__.withModifiers)($event => {$options.setAlignment('center'); $data.showMoreMenu = false}, ["prevent"])),
                        class: (0,vue__WEBPACK_IMPORTED_MODULE_0__.normalizeClass)([{ 'is-active': $options.isAlignmentActive('center') }, "dropdown-menu-item"])
                      }, [
                        (0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_FormatAlignCenter, { size: 16 }),
                        (0,vue__WEBPACK_IMPORTED_MODULE_0__.createTextVNode)(" " + (0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)($options.t('Align center')), 1 /* TEXT */)
                      ], 34 /* CLASS, NEED_HYDRATION */),
                      (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("button", {
                        type: "button",
                        onMousedown: _cache[10] || (_cache[10] = (0,vue__WEBPACK_IMPORTED_MODULE_0__.withModifiers)($event => {$options.setAlignment('right'); $data.showMoreMenu = false}, ["prevent"])),
                        class: (0,vue__WEBPACK_IMPORTED_MODULE_0__.normalizeClass)([{ 'is-active': $options.isAlignmentActive('right') }, "dropdown-menu-item"])
                      }, [
                        (0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_FormatAlignRight, { size: 16 }),
                        (0,vue__WEBPACK_IMPORTED_MODULE_0__.createTextVNode)(" " + (0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)($options.t('Align right')), 1 /* TEXT */)
                      ], 34 /* CLASS, NEED_HYDRATION */),
                      _cache[43] || (_cache[43] = (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("div", { class: "dropdown-divider" }, null, -1 /* CACHED */)),
                      (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)(" Link "),
                      (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("button", {
                        type: "button",
                        onMousedown: _cache[11] || (_cache[11] = (0,vue__WEBPACK_IMPORTED_MODULE_0__.withModifiers)($event => {$options.showLinkModalHandler(); $data.showMoreMenu = false}, ["prevent"])),
                        class: (0,vue__WEBPACK_IMPORTED_MODULE_0__.normalizeClass)([{ 'is-active': $data.editor.isActive('link') }, "dropdown-menu-item"])
                      }, [
                        (0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_LinkVariant, { size: 16 }),
                        (0,vue__WEBPACK_IMPORTED_MODULE_0__.createTextVNode)(" " + (0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)($options.t('Link')), 1 /* TEXT */)
                      ], 34 /* CLASS, NEED_HYDRATION */),
                      (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)(" Table "),
                      (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("button", {
                        type: "button",
                        onMousedown: _cache[12] || (_cache[12] = (0,vue__WEBPACK_IMPORTED_MODULE_0__.withModifiers)($event => {$options.insertTable(); $data.showMoreMenu = false}, ["prevent"])),
                        class: "dropdown-menu-item"
                      }, [
                        (0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_TableIcon, { size: 16 }),
                        (0,vue__WEBPACK_IMPORTED_MODULE_0__.createTextVNode)(" " + (0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)($options.t('Insert table')), 1 /* TEXT */)
                      ], 32 /* NEED_HYDRATION */),
                      (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)(" Table editing options when in table "),
                      ($data.editor.isActive('table'))
                        ? ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)(vue__WEBPACK_IMPORTED_MODULE_0__.Fragment, { key: 0 }, [
                            _cache[39] || (_cache[39] = (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("div", { class: "dropdown-divider" }, null, -1 /* CACHED */)),
                            (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("button", {
                              type: "button",
                              onMousedown: _cache[13] || (_cache[13] = (0,vue__WEBPACK_IMPORTED_MODULE_0__.withModifiers)($event => {$options.addRowAfter(); $data.showMoreMenu = false}, ["prevent"])),
                              class: "dropdown-menu-item"
                            }, [
                              (0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_TableRowPlusAfter, { size: 16 }),
                              (0,vue__WEBPACK_IMPORTED_MODULE_0__.createTextVNode)(" " + (0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)($options.t('Add row')), 1 /* TEXT */)
                            ], 32 /* NEED_HYDRATION */),
                            (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("button", {
                              type: "button",
                              onMousedown: _cache[14] || (_cache[14] = (0,vue__WEBPACK_IMPORTED_MODULE_0__.withModifiers)($event => {$options.addColumnAfter(); $data.showMoreMenu = false}, ["prevent"])),
                              class: "dropdown-menu-item"
                            }, [
                              (0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_TableColumnPlusAfter, { size: 16 }),
                              (0,vue__WEBPACK_IMPORTED_MODULE_0__.createTextVNode)(" " + (0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)($options.t('Add column')), 1 /* TEXT */)
                            ], 32 /* NEED_HYDRATION */),
                            (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("button", {
                              type: "button",
                              onMousedown: _cache[15] || (_cache[15] = (0,vue__WEBPACK_IMPORTED_MODULE_0__.withModifiers)($event => {$options.deleteTable(); $data.showMoreMenu = false}, ["prevent"])),
                              class: "dropdown-menu-item dropdown-menu-item--danger"
                            }, [
                              (0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_TableRemove, { size: 16 }),
                              (0,vue__WEBPACK_IMPORTED_MODULE_0__.createTextVNode)(" " + (0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)($options.t('Delete table')), 1 /* TEXT */)
                            ], 32 /* NEED_HYDRATION */)
                          ], 64 /* STABLE_FRAGMENT */))
                        : (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)("v-if", true)
                    ]))
                  : (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)("v-if", true)
              ]))
            : ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)(vue__WEBPACK_IMPORTED_MODULE_0__.Fragment, { key: 1 }, [
                (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)(" FULL MODE: All buttons visible "),
                (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)(" Group 1: Inline formatting | Group 2: Block structure | Group 3: Alignment | Group 4: Insert "),
                (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("button", {
                  type: "button",
                  onMousedown: _cache[16] || (_cache[16] = (0,vue__WEBPACK_IMPORTED_MODULE_0__.withModifiers)($event => ($data.editor.chain().focus().toggleStrike().run()), ["prevent"])),
                  class: (0,vue__WEBPACK_IMPORTED_MODULE_0__.normalizeClass)([{ 'is-active': $data.editor.isActive('strike') }, "menubar-button"]),
                  title: $options.t('Strikethrough'),
                  "aria-label": $options.t('Strikethrough')
                }, [
                  (0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_FormatStrikethrough, { size: 18 })
                ], 42 /* CLASS, PROPS, NEED_HYDRATION */, _hoisted_8),
                _cache[47] || (_cache[47] = (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("span", { class: "menubar-divider" }, null, -1 /* CACHED */)),
                (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)(" Group 2: Block structure "),
                (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)(" Heading Dropdown "),
                (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("div", _hoisted_9, [
                  (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("button", {
                    type: "button",
                    onMousedown: _cache[17] || (_cache[17] = (0,vue__WEBPACK_IMPORTED_MODULE_0__.withModifiers)((...args) => ($options.toggleHeadingMenu && $options.toggleHeadingMenu(...args)), ["prevent"])),
                    class: "menubar-button heading-button",
                    title: $options.t('Heading'),
                    "aria-label": $options.t('Heading')
                  }, [
                    (0,vue__WEBPACK_IMPORTED_MODULE_0__.createTextVNode)((0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)($options.getCurrentHeadingLabel()) + " ", 1 /* TEXT */),
                    (0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_ChevronDown, { size: 14 })
                  ], 40 /* PROPS, NEED_HYDRATION */, _hoisted_10),
                  ($data.showHeadingMenu)
                    ? ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)("div", _hoisted_11, [
                        ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)(vue__WEBPACK_IMPORTED_MODULE_0__.Fragment, null, (0,vue__WEBPACK_IMPORTED_MODULE_0__.renderList)([0, 1, 2, 3, 4], (level) => {
                          return (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("button", {
                            key: level,
                            type: "button",
                            onMousedown: (0,vue__WEBPACK_IMPORTED_MODULE_0__.withModifiers)($event => ($options.setHeadingLevel(level)), ["prevent"]),
                            class: (0,vue__WEBPACK_IMPORTED_MODULE_0__.normalizeClass)([{ 'is-active': $options.isHeadingLevel(level) }, "dropdown-menu-item"])
                          }, (0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)($options.getHeadingLabel(level)), 43 /* TEXT, CLASS, PROPS, NEED_HYDRATION */, _hoisted_12)
                        }), 64 /* STABLE_FRAGMENT */))
                      ]))
                    : (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)("v-if", true)
                ]),
                (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)(" Lists "),
                (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("button", {
                  type: "button",
                  onMousedown: _cache[18] || (_cache[18] = (0,vue__WEBPACK_IMPORTED_MODULE_0__.withModifiers)($event => ($data.editor.chain().focus().toggleBulletList().run()), ["prevent"])),
                  class: (0,vue__WEBPACK_IMPORTED_MODULE_0__.normalizeClass)([{ 'is-active': $data.editor.isActive('bulletList') }, "menubar-button"]),
                  title: $options.t('Bullet list'),
                  "aria-label": $options.t('Bullet list')
                }, [
                  (0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_FormatListBulleted, { size: 18 })
                ], 42 /* CLASS, PROPS, NEED_HYDRATION */, _hoisted_13),
                (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("button", {
                  type: "button",
                  onMousedown: _cache[19] || (_cache[19] = (0,vue__WEBPACK_IMPORTED_MODULE_0__.withModifiers)($event => ($data.editor.chain().focus().toggleOrderedList().run()), ["prevent"])),
                  class: (0,vue__WEBPACK_IMPORTED_MODULE_0__.normalizeClass)([{ 'is-active': $data.editor.isActive('orderedList') }, "menubar-button"]),
                  title: $options.t('Numbered list'),
                  "aria-label": $options.t('Numbered list')
                }, [
                  (0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_FormatListNumbered, { size: 18 })
                ], 42 /* CLASS, PROPS, NEED_HYDRATION */, _hoisted_14),
                (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)(" Blockquote "),
                (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("button", {
                  type: "button",
                  onMousedown: _cache[20] || (_cache[20] = (0,vue__WEBPACK_IMPORTED_MODULE_0__.withModifiers)($event => ($data.editor.chain().focus().toggleBlockquote().run()), ["prevent"])),
                  class: (0,vue__WEBPACK_IMPORTED_MODULE_0__.normalizeClass)([{ 'is-active': $data.editor.isActive('blockquote') }, "menubar-button"]),
                  title: $options.t('Blockquote'),
                  "aria-label": $options.t('Blockquote')
                }, [
                  (0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_FormatQuoteClose, { size: 18 })
                ], 42 /* CLASS, PROPS, NEED_HYDRATION */, _hoisted_15),
                _cache[48] || (_cache[48] = (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("span", { class: "menubar-divider" }, null, -1 /* CACHED */)),
                (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)(" Group 3: Alignment Dropdown "),
                (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("div", _hoisted_16, [
                  (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("button", {
                    type: "button",
                    onMousedown: _cache[21] || (_cache[21] = (0,vue__WEBPACK_IMPORTED_MODULE_0__.withModifiers)((...args) => ($options.toggleAlignmentMenu && $options.toggleAlignmentMenu(...args)), ["prevent"])),
                    class: "menubar-button alignment-button",
                    title: $options.t('Text alignment'),
                    "aria-label": $options.t('Text alignment')
                  }, [
                    ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createBlock)((0,vue__WEBPACK_IMPORTED_MODULE_0__.resolveDynamicComponent)($options.currentAlignmentIcon), { size: 18 })),
                    (0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_ChevronDown, { size: 14 })
                  ], 40 /* PROPS, NEED_HYDRATION */, _hoisted_17),
                  ($data.showAlignmentMenu)
                    ? ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)("div", _hoisted_18, [
                        ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)(vue__WEBPACK_IMPORTED_MODULE_0__.Fragment, null, (0,vue__WEBPACK_IMPORTED_MODULE_0__.renderList)(['left', 'center', 'right'], (align) => {
                          return (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("button", {
                            key: align,
                            type: "button",
                            onMousedown: (0,vue__WEBPACK_IMPORTED_MODULE_0__.withModifiers)($event => ($options.setAlignment(align)), ["prevent"]),
                            class: (0,vue__WEBPACK_IMPORTED_MODULE_0__.normalizeClass)([{ 'is-active': $options.isAlignmentActive(align) }, "dropdown-menu-item"])
                          }, [
                            ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createBlock)((0,vue__WEBPACK_IMPORTED_MODULE_0__.resolveDynamicComponent)($data.alignmentIcons[align]), { size: 16 })),
                            (0,vue__WEBPACK_IMPORTED_MODULE_0__.createTextVNode)(" " + (0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)($options.getAlignmentLabel(align)), 1 /* TEXT */)
                          ], 42 /* CLASS, PROPS, NEED_HYDRATION */, _hoisted_19)
                        }), 64 /* STABLE_FRAGMENT */))
                      ]))
                    : (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)("v-if", true)
                ]),
                _cache[49] || (_cache[49] = (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("span", { class: "menubar-divider" }, null, -1 /* CACHED */)),
                (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)(" Group 4: Insert actions "),
                (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)(" Link "),
                (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("button", {
                  type: "button",
                  onMousedown: _cache[22] || (_cache[22] = (0,vue__WEBPACK_IMPORTED_MODULE_0__.withModifiers)((...args) => ($options.showLinkModalHandler && $options.showLinkModalHandler(...args)), ["prevent"])),
                  class: (0,vue__WEBPACK_IMPORTED_MODULE_0__.normalizeClass)([{ 'is-active': $data.editor.isActive('link') }, "menubar-button"]),
                  title: $options.t('Insert link'),
                  "aria-label": $options.t('Insert link')
                }, [
                  (0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_LinkVariant, { size: 18 })
                ], 42 /* CLASS, PROPS, NEED_HYDRATION */, _hoisted_20),
                (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)(" Table Dropdown "),
                (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("div", _hoisted_21, [
                  (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("button", {
                    type: "button",
                    onMousedown: _cache[23] || (_cache[23] = (0,vue__WEBPACK_IMPORTED_MODULE_0__.withModifiers)((...args) => ($options.toggleTableMenu && $options.toggleTableMenu(...args)), ["prevent"])),
                    class: (0,vue__WEBPACK_IMPORTED_MODULE_0__.normalizeClass)([{ 'is-active': $data.editor.isActive('table') }, "menubar-button"]),
                    title: $options.t('Table'),
                    "aria-label": $options.t('Table')
                  }, [
                    (0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_TableIcon, { size: 18 }),
                    (0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_ChevronDown, { size: 14 })
                  ], 42 /* CLASS, PROPS, NEED_HYDRATION */, _hoisted_22),
                  ($data.showTableMenu)
                    ? ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)("div", _hoisted_23, [
                        (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("button", {
                          type: "button",
                          onMousedown: _cache[24] || (_cache[24] = (0,vue__WEBPACK_IMPORTED_MODULE_0__.withModifiers)((...args) => ($options.insertTable && $options.insertTable(...args)), ["prevent"])),
                          class: "dropdown-menu-item"
                        }, [
                          (0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_TableIcon, { size: 16 }),
                          (0,vue__WEBPACK_IMPORTED_MODULE_0__.createTextVNode)(" " + (0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)($options.t('Insert table')), 1 /* TEXT */)
                        ], 32 /* NEED_HYDRATION */),
                        ($data.editor.isActive('table'))
                          ? ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)(vue__WEBPACK_IMPORTED_MODULE_0__.Fragment, { key: 0 }, [
                              _cache[44] || (_cache[44] = (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("div", { class: "dropdown-divider" }, null, -1 /* CACHED */)),
                              (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("button", {
                                type: "button",
                                onMousedown: _cache[25] || (_cache[25] = (0,vue__WEBPACK_IMPORTED_MODULE_0__.withModifiers)((...args) => ($options.addRowBefore && $options.addRowBefore(...args)), ["prevent"])),
                                class: "dropdown-menu-item"
                              }, [
                                (0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_TableRowPlusBefore, { size: 16 }),
                                (0,vue__WEBPACK_IMPORTED_MODULE_0__.createTextVNode)(" " + (0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)($options.t('Add row above')), 1 /* TEXT */)
                              ], 32 /* NEED_HYDRATION */),
                              (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("button", {
                                type: "button",
                                onMousedown: _cache[26] || (_cache[26] = (0,vue__WEBPACK_IMPORTED_MODULE_0__.withModifiers)((...args) => ($options.addRowAfter && $options.addRowAfter(...args)), ["prevent"])),
                                class: "dropdown-menu-item"
                              }, [
                                (0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_TableRowPlusAfter, { size: 16 }),
                                (0,vue__WEBPACK_IMPORTED_MODULE_0__.createTextVNode)(" " + (0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)($options.t('Add row below')), 1 /* TEXT */)
                              ], 32 /* NEED_HYDRATION */),
                              (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("button", {
                                type: "button",
                                onMousedown: _cache[27] || (_cache[27] = (0,vue__WEBPACK_IMPORTED_MODULE_0__.withModifiers)((...args) => ($options.addColumnBefore && $options.addColumnBefore(...args)), ["prevent"])),
                                class: "dropdown-menu-item"
                              }, [
                                (0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_TableColumnPlusBefore, { size: 16 }),
                                (0,vue__WEBPACK_IMPORTED_MODULE_0__.createTextVNode)(" " + (0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)($options.t('Add column left')), 1 /* TEXT */)
                              ], 32 /* NEED_HYDRATION */),
                              (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("button", {
                                type: "button",
                                onMousedown: _cache[28] || (_cache[28] = (0,vue__WEBPACK_IMPORTED_MODULE_0__.withModifiers)((...args) => ($options.addColumnAfter && $options.addColumnAfter(...args)), ["prevent"])),
                                class: "dropdown-menu-item"
                              }, [
                                (0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_TableColumnPlusAfter, { size: 16 }),
                                (0,vue__WEBPACK_IMPORTED_MODULE_0__.createTextVNode)(" " + (0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)($options.t('Add column right')), 1 /* TEXT */)
                              ], 32 /* NEED_HYDRATION */),
                              _cache[45] || (_cache[45] = (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("div", { class: "dropdown-divider" }, null, -1 /* CACHED */)),
                              (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("div", _hoisted_24, (0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)($options.t('Width')), 1 /* TEXT */),
                              (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("div", _hoisted_25, [
                                ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(true), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)(vue__WEBPACK_IMPORTED_MODULE_0__.Fragment, null, (0,vue__WEBPACK_IMPORTED_MODULE_0__.renderList)($data.tableWidthPresets, (w) => {
                                  return ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)("button", {
                                    key: w,
                                    type: "button",
                                    onMousedown: (0,vue__WEBPACK_IMPORTED_MODULE_0__.withModifiers)($event => ($options.setTableWidth(w)), ["prevent"]),
                                    class: (0,vue__WEBPACK_IMPORTED_MODULE_0__.normalizeClass)([{ 'is-active': $options.isTableWidth(w) }, "dropdown-menu-pill"])
                                  }, (0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)(w === null ? $options.t('Auto') : w), 43 /* TEXT, CLASS, PROPS, NEED_HYDRATION */, _hoisted_26))
                                }), 128 /* KEYED_FRAGMENT */))
                              ]),
                              (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("div", _hoisted_27, (0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)($options.t('Alignment')), 1 /* TEXT */),
                              (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("div", _hoisted_28, [
                                (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("button", {
                                  type: "button",
                                  onMousedown: _cache[29] || (_cache[29] = (0,vue__WEBPACK_IMPORTED_MODULE_0__.withModifiers)($event => ($options.setTableAlign('left')), ["prevent"])),
                                  class: (0,vue__WEBPACK_IMPORTED_MODULE_0__.normalizeClass)([{ 'is-active': $options.isTableAlign('left') }, "dropdown-menu-pill"]),
                                  title: $options.t('Align left')
                                }, [
                                  (0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_FormatAlignLeft, { size: 16 })
                                ], 42 /* CLASS, PROPS, NEED_HYDRATION */, _hoisted_29),
                                (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("button", {
                                  type: "button",
                                  onMousedown: _cache[30] || (_cache[30] = (0,vue__WEBPACK_IMPORTED_MODULE_0__.withModifiers)($event => ($options.setTableAlign('center')), ["prevent"])),
                                  class: (0,vue__WEBPACK_IMPORTED_MODULE_0__.normalizeClass)([{ 'is-active': $options.isTableAlign('center') }, "dropdown-menu-pill"]),
                                  title: $options.t('Align center')
                                }, [
                                  (0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_FormatAlignCenter, { size: 16 })
                                ], 42 /* CLASS, PROPS, NEED_HYDRATION */, _hoisted_30),
                                (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("button", {
                                  type: "button",
                                  onMousedown: _cache[31] || (_cache[31] = (0,vue__WEBPACK_IMPORTED_MODULE_0__.withModifiers)($event => ($options.setTableAlign('right')), ["prevent"])),
                                  class: (0,vue__WEBPACK_IMPORTED_MODULE_0__.normalizeClass)([{ 'is-active': $options.isTableAlign('right') }, "dropdown-menu-pill"]),
                                  title: $options.t('Align right')
                                }, [
                                  (0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_FormatAlignRight, { size: 16 })
                                ], 42 /* CLASS, PROPS, NEED_HYDRATION */, _hoisted_31)
                              ]),
                              _cache[46] || (_cache[46] = (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("div", { class: "dropdown-divider" }, null, -1 /* CACHED */)),
                              (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("button", {
                                type: "button",
                                onMousedown: _cache[32] || (_cache[32] = (0,vue__WEBPACK_IMPORTED_MODULE_0__.withModifiers)((...args) => ($options.deleteRow && $options.deleteRow(...args)), ["prevent"])),
                                class: "dropdown-menu-item dropdown-menu-item--danger"
                              }, [
                                (0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_TableRowRemove, { size: 16 }),
                                (0,vue__WEBPACK_IMPORTED_MODULE_0__.createTextVNode)(" " + (0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)($options.t('Delete row')), 1 /* TEXT */)
                              ], 32 /* NEED_HYDRATION */),
                              (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("button", {
                                type: "button",
                                onMousedown: _cache[33] || (_cache[33] = (0,vue__WEBPACK_IMPORTED_MODULE_0__.withModifiers)((...args) => ($options.deleteColumn && $options.deleteColumn(...args)), ["prevent"])),
                                class: "dropdown-menu-item dropdown-menu-item--danger"
                              }, [
                                (0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_TableColumnRemove, { size: 16 }),
                                (0,vue__WEBPACK_IMPORTED_MODULE_0__.createTextVNode)(" " + (0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)($options.t('Delete column')), 1 /* TEXT */)
                              ], 32 /* NEED_HYDRATION */),
                              (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("button", {
                                type: "button",
                                onMousedown: _cache[34] || (_cache[34] = (0,vue__WEBPACK_IMPORTED_MODULE_0__.withModifiers)((...args) => ($options.deleteTable && $options.deleteTable(...args)), ["prevent"])),
                                class: "dropdown-menu-item dropdown-menu-item--danger"
                              }, [
                                (0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_TableRemove, { size: 16 }),
                                (0,vue__WEBPACK_IMPORTED_MODULE_0__.createTextVNode)(" " + (0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)($options.t('Delete table')), 1 /* TEXT */)
                              ], 32 /* NEED_HYDRATION */)
                            ], 64 /* STABLE_FRAGMENT */))
                          : (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)("v-if", true)
                      ]))
                    : (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)("v-if", true)
                ])
              ], 64 /* STABLE_FRAGMENT */))
        ], 2 /* CLASS */))
      : (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)("v-if", true),
    (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)(" Editor Content "),
    ($data.editorContentComponent && $data.editor)
      ? ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createBlock)((0,vue__WEBPACK_IMPORTED_MODULE_0__.resolveDynamicComponent)($data.editorContentComponent), {
          key: 1,
          editor: $data.editor,
          class: "editor-content"
        }, null, 8 /* PROPS */, ["editor"]))
      : (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)("v-if", true),
    (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)(" Link Modal "),
    ($data.showLinkModal)
      ? ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createBlock)(_component_NcModal, {
          key: 2,
          onClose: $options.closeLinkModal,
          name: $options.t('Insert link'),
          size: "normal"
        }, {
          default: (0,vue__WEBPACK_IMPORTED_MODULE_0__.withCtx)(() => [
            (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("div", _hoisted_32, [
              (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("div", _hoisted_33, [
                (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("label", _hoisted_34, (0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)($options.t('Text:')), 1 /* TEXT */),
                (0,vue__WEBPACK_IMPORTED_MODULE_0__.withDirectives)((0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("input", {
                  id: "link-text",
                  "onUpdate:modelValue": _cache[35] || (_cache[35] = $event => (($data.linkText) = $event)),
                  type: "text",
                  placeholder: $options.t('Link text'),
                  class: "link-input",
                  onKeyup: _cache[36] || (_cache[36] = (0,vue__WEBPACK_IMPORTED_MODULE_0__.withKeys)((...args) => ($options.applyLink && $options.applyLink(...args)), ["enter"]))
                }, null, 40 /* PROPS, NEED_HYDRATION */, _hoisted_35), [
                  [vue__WEBPACK_IMPORTED_MODULE_0__.vModelText, $data.linkText]
                ])
              ]),
              (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("div", _hoisted_36, [
                (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("label", _hoisted_37, (0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)($options.t('URL:')), 1 /* TEXT */),
                (0,vue__WEBPACK_IMPORTED_MODULE_0__.withDirectives)((0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("input", {
                  id: "link-url",
                  "onUpdate:modelValue": _cache[37] || (_cache[37] = $event => (($data.linkUrl) = $event)),
                  type: "url",
                  placeholder: $options.t('https://example.com'),
                  class: "link-input",
                  onKeyup: _cache[38] || (_cache[38] = (0,vue__WEBPACK_IMPORTED_MODULE_0__.withKeys)((...args) => ($options.applyLink && $options.applyLink(...args)), ["enter"]))
                }, null, 40 /* PROPS, NEED_HYDRATION */, _hoisted_38), [
                  [vue__WEBPACK_IMPORTED_MODULE_0__.vModelText, $data.linkUrl]
                ])
              ]),
              (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("div", _hoisted_39, [
                (0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_NcButton, {
                  onClick: $options.closeLinkModal,
                  type: "secondary"
                }, {
                  default: (0,vue__WEBPACK_IMPORTED_MODULE_0__.withCtx)(() => [
                    (0,vue__WEBPACK_IMPORTED_MODULE_0__.createTextVNode)((0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)($options.t('Cancel')), 1 /* TEXT */)
                  ]),
                  _: 1 /* STABLE */
                }, 8 /* PROPS */, ["onClick"]),
                ($data.editor && $data.editor.isActive('link'))
                  ? ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createBlock)(_component_NcButton, {
                      key: 0,
                      onClick: $options.removeLink,
                      type: "error"
                    }, {
                      default: (0,vue__WEBPACK_IMPORTED_MODULE_0__.withCtx)(() => [
                        (0,vue__WEBPACK_IMPORTED_MODULE_0__.createTextVNode)((0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)($options.t('Remove link')), 1 /* TEXT */)
                      ]),
                      _: 1 /* STABLE */
                    }, 8 /* PROPS */, ["onClick"]))
                  : (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)("v-if", true),
                (0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_NcButton, {
                  onClick: $options.applyLink,
                  type: "primary"
                }, {
                  default: (0,vue__WEBPACK_IMPORTED_MODULE_0__.withCtx)(() => [
                    (0,vue__WEBPACK_IMPORTED_MODULE_0__.createTextVNode)((0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)($options.t('Apply')), 1 /* TEXT */)
                  ]),
                  _: 1 /* STABLE */
                }, 8 /* PROPS */, ["onClick"])
              ])
            ])
          ]),
          _: 1 /* STABLE */
        }, 8 /* PROPS */, ["onClose", "name"]))
      : (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)("v-if", true)
  ], 2 /* CLASS */))
}

/***/ },

/***/ "./node_modules/vue-loader/dist/templateLoader.js??ruleSet[1].rules[2]!./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/PageTreeSelect.vue?vue&type=template&id=5efccfd9&scoped=true"
/*!*******************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/vue-loader/dist/templateLoader.js??ruleSet[1].rules[2]!./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/PageTreeSelect.vue?vue&type=template&id=5efccfd9&scoped=true ***!
  \*******************************************************************************************************************************************************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   render: () => (/* binding */ render)
/* harmony export */ });
/* harmony import */ var vue__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! vue */ "./node_modules/vue/dist/vue.runtime.esm-bundler.js");


const _hoisted_1 = {
  class: "page-tree-select",
  ref: "selectContainer"
}
const _hoisted_2 = ["disabled", "aria-expanded"]
const _hoisted_3 = {
  key: 0,
  class: "selected-value"
}
const _hoisted_4 = {
  key: 0,
  class: "selected-path"
}
const _hoisted_5 = { class: "selected-title" }
const _hoisted_6 = {
  key: 1,
  class: "placeholder"
}
const _hoisted_7 = {
  key: 0,
  class: "select-dropdown",
  ref: "dropdown"
}
const _hoisted_8 = { class: "search-wrapper" }
const _hoisted_9 = ["placeholder", "aria-label"]
const _hoisted_10 = ["aria-label"]
const _hoisted_11 = {
  class: "tree-wrapper",
  ref: "treeWrapper"
}
const _hoisted_12 = {
  key: 0,
  class: "loading-state"
}
const _hoisted_13 = {
  key: 1,
  class: "empty-state"
}
const _hoisted_14 = {
  key: 2,
  class: "tree-list",
  role: "listbox"
}

function render(_ctx, _cache, $props, $setup, $data, $options) {
  const _component_FileDocument = (0,vue__WEBPACK_IMPORTED_MODULE_0__.resolveComponent)("FileDocument")
  const _component_ChevronDown = (0,vue__WEBPACK_IMPORTED_MODULE_0__.resolveComponent)("ChevronDown")
  const _component_Magnify = (0,vue__WEBPACK_IMPORTED_MODULE_0__.resolveComponent)("Magnify")
  const _component_Close = (0,vue__WEBPACK_IMPORTED_MODULE_0__.resolveComponent)("Close")
  const _component_NcLoadingIcon = (0,vue__WEBPACK_IMPORTED_MODULE_0__.resolveComponent)("NcLoadingIcon")
  const _component_PageTreeSelectItem = (0,vue__WEBPACK_IMPORTED_MODULE_0__.resolveComponent)("PageTreeSelectItem")

  return ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)("div", _hoisted_1, [
    (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)(" Selected value / trigger "),
    (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("button", {
      type: "button",
      class: (0,vue__WEBPACK_IMPORTED_MODULE_0__.normalizeClass)(["select-trigger", { 'is-open': $data.isOpen, 'has-value': $options.selectedPage, 'is-disabled': $props.disabled }]),
      disabled: $props.disabled,
      role: "combobox",
      "aria-expanded": $data.isOpen,
      "aria-haspopup": "listbox",
      onClick: _cache[0] || (_cache[0] = (...args) => ($options.toggleDropdown && $options.toggleDropdown(...args))),
      ref: "trigger"
    }, [
      ($options.selectedPage)
        ? ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)("span", _hoisted_3, [
            (0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_FileDocument, {
              size: 16,
              class: "selected-icon"
            }),
            ($options.selectedPath)
              ? ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)("span", _hoisted_4, (0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)($options.selectedPath) + " /", 1 /* TEXT */))
              : (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)("v-if", true),
            (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("span", _hoisted_5, (0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)($options.selectedPage.title), 1 /* TEXT */)
          ]))
        : ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)("span", _hoisted_6, (0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)($props.placeholder), 1 /* TEXT */)),
      (0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_ChevronDown, {
        size: 18,
        class: (0,vue__WEBPACK_IMPORTED_MODULE_0__.normalizeClass)(["trigger-icon", { 'rotated': $data.isOpen }])
      }, null, 8 /* PROPS */, ["class"])
    ], 10 /* CLASS, PROPS */, _hoisted_2),
    (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)(" Dropdown "),
    ($data.isOpen)
      ? ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)("div", _hoisted_7, [
          (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)(" Search input "),
          (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("div", _hoisted_8, [
            (0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_Magnify, {
              size: 16,
              class: "search-icon"
            }),
            (0,vue__WEBPACK_IMPORTED_MODULE_0__.withDirectives)((0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("input", {
              ref: "searchInput",
              "onUpdate:modelValue": _cache[1] || (_cache[1] = $event => (($data.searchQuery) = $event)),
              type: "text",
              class: "search-input",
              placeholder: $options.t('Search pages...'),
              "aria-label": $options.t('Search pages'),
              onKeydown: [
                _cache[2] || (_cache[2] = (0,vue__WEBPACK_IMPORTED_MODULE_0__.withKeys)((...args) => ($options.closeDropdown && $options.closeDropdown(...args)), ["escape"])),
                _cache[3] || (_cache[3] = (0,vue__WEBPACK_IMPORTED_MODULE_0__.withKeys)((0,vue__WEBPACK_IMPORTED_MODULE_0__.withModifiers)((...args) => ($options.focusNext && $options.focusNext(...args)), ["prevent"]), ["down"])),
                _cache[4] || (_cache[4] = (0,vue__WEBPACK_IMPORTED_MODULE_0__.withKeys)((0,vue__WEBPACK_IMPORTED_MODULE_0__.withModifiers)((...args) => ($options.focusPrev && $options.focusPrev(...args)), ["prevent"]), ["up"])),
                _cache[5] || (_cache[5] = (0,vue__WEBPACK_IMPORTED_MODULE_0__.withKeys)((0,vue__WEBPACK_IMPORTED_MODULE_0__.withModifiers)((...args) => ($options.selectFocused && $options.selectFocused(...args)), ["prevent"]), ["enter"]))
              ]
            }, null, 40 /* PROPS, NEED_HYDRATION */, _hoisted_9), [
              [vue__WEBPACK_IMPORTED_MODULE_0__.vModelText, $data.searchQuery]
            ]),
            ($data.searchQuery)
              ? ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)("button", {
                  key: 0,
                  class: "clear-search",
                  onClick: _cache[6] || (_cache[6] = $event => ($data.searchQuery = '')),
                  "aria-label": $options.t('Clear search')
                }, [
                  (0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_Close, { size: 14 })
                ], 8 /* PROPS */, _hoisted_10))
              : (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)("v-if", true)
          ]),
          (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)(" Tree list "),
          (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("div", _hoisted_11, [
            ($data.loading)
              ? ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)("div", _hoisted_12, [
                  (0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_NcLoadingIcon, { size: 24 })
                ]))
              : ($options.filteredTree.length === 0)
                ? ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)("div", _hoisted_13, (0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)($data.searchQuery ? $options.t('No pages found') : $options.t('No pages available')), 1 /* TEXT */))
                : ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)("ul", _hoisted_14, [
                    ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(true), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)(vue__WEBPACK_IMPORTED_MODULE_0__.Fragment, null, (0,vue__WEBPACK_IMPORTED_MODULE_0__.renderList)($options.filteredTree, (item) => {
                      return ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createBlock)(_component_PageTreeSelectItem, {
                        key: item.uniqueId,
                        item: item,
                        "expanded-nodes": $data.expandedNodes,
                        "selected-id": $props.modelValue,
                        "focused-id": $data.focusedId,
                        "search-query": $data.searchQuery,
                        onToggle: $options.toggleNode,
                        onSelect: $options.selectPage,
                        onFocus: _cache[7] || (_cache[7] = $event => ($data.focusedId = $event))
                      }, null, 8 /* PROPS */, ["item", "expanded-nodes", "selected-id", "focused-id", "search-query", "onToggle", "onSelect"]))
                    }), 128 /* KEYED_FRAGMENT */))
                  ]))
          ], 512 /* NEED_PATCH */),
          (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)(" Clear selection "),
          ($options.selectedPage && $props.clearable)
            ? ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)("button", {
                key: 0,
                class: "clear-selection",
                onClick: _cache[8] || (_cache[8] = (...args) => ($options.clearSelection && $options.clearSelection(...args)))
              }, [
                (0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_Close, { size: 16 }),
                (0,vue__WEBPACK_IMPORTED_MODULE_0__.createTextVNode)(" " + (0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)($options.t('Clear selection')), 1 /* TEXT */)
              ]))
            : (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)("v-if", true)
        ], 512 /* NEED_PATCH */))
      : (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)("v-if", true)
  ], 512 /* NEED_PATCH */))
}

/***/ },

/***/ "./node_modules/vue-loader/dist/templateLoader.js??ruleSet[1].rules[2]!./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/PageTreeSelectItem.vue?vue&type=template&id=621c650c&scoped=true"
/*!***********************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/vue-loader/dist/templateLoader.js??ruleSet[1].rules[2]!./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/PageTreeSelectItem.vue?vue&type=template&id=621c650c&scoped=true ***!
  \***********************************************************************************************************************************************************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   render: () => (/* binding */ render)
/* harmony export */ });
/* harmony import */ var vue__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! vue */ "./node_modules/vue/dist/vue.runtime.esm-bundler.js");


const _hoisted_1 = { class: "tree-select-item" }
const _hoisted_2 = ["aria-label"]
const _hoisted_3 = {
  key: 1,
  class: "toggle-spacer"
}
const _hoisted_4 = { class: "item-title" }
const _hoisted_5 = {
  key: 0,
  class: "children-list"
}
const _hoisted_6 = {
  key: 0,
  class: "tree-show-more"
}

function render(_ctx, _cache, $props, $setup, $data, $options) {
  const _component_ChevronRight = (0,vue__WEBPACK_IMPORTED_MODULE_0__.resolveComponent)("ChevronRight")
  const _component_ChevronDown = (0,vue__WEBPACK_IMPORTED_MODULE_0__.resolveComponent)("ChevronDown")
  const _component_FolderOutline = (0,vue__WEBPACK_IMPORTED_MODULE_0__.resolveComponent)("FolderOutline")
  const _component_FileDocument = (0,vue__WEBPACK_IMPORTED_MODULE_0__.resolveComponent)("FileDocument")
  const _component_Check = (0,vue__WEBPACK_IMPORTED_MODULE_0__.resolveComponent)("Check")
  const _component_PageTreeSelectItem = (0,vue__WEBPACK_IMPORTED_MODULE_0__.resolveComponent)("PageTreeSelectItem", true)

  return ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)("li", _hoisted_1, [
    (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("div", {
      class: (0,vue__WEBPACK_IMPORTED_MODULE_0__.normalizeClass)(["item-row", {
        'is-selected': $options.isSelected,
        'is-focused': $options.isFocused,
        'matches-search': $props.item.matchesSearch
      }]),
      onMouseenter: _cache[2] || (_cache[2] = $event => (_ctx.$emit('focus', $props.item.uniqueId)))
    }, [
      (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)(" Expand/collapse toggle "),
      ($options.hasChildren)
        ? ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)("button", {
            key: 0,
            class: "expand-toggle",
            onClick: _cache[0] || (_cache[0] = (0,vue__WEBPACK_IMPORTED_MODULE_0__.withModifiers)($event => (_ctx.$emit('toggle', $props.item.uniqueId)), ["stop"])),
            "aria-label": $options.isExpanded ? 'Collapse' : 'Expand'
          }, [
            (!$options.isExpanded)
              ? ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createBlock)(_component_ChevronRight, {
                  key: 0,
                  size: 16
                }))
              : ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createBlock)(_component_ChevronDown, {
                  key: 1,
                  size: 16
                }))
          ], 8 /* PROPS */, _hoisted_2))
        : ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)("span", _hoisted_3)),
      (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)(" Page item "),
      (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("button", {
        class: "item-content",
        onClick: _cache[1] || (_cache[1] = $event => (_ctx.$emit('select', $props.item)))
      }, [
        ($options.hasChildren)
          ? ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createBlock)(_component_FolderOutline, {
              key: 0,
              size: 16,
              class: "item-icon folder-icon"
            }))
          : ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createBlock)(_component_FileDocument, {
              key: 1,
              size: 16,
              class: "item-icon"
            })),
        (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("span", _hoisted_4, (0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)($props.item.title), 1 /* TEXT */),
        ($options.isSelected)
          ? ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createBlock)(_component_Check, {
              key: 2,
              size: 16,
              class: "check-icon"
            }))
          : (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)("v-if", true)
      ])
    ], 34 /* CLASS, NEED_HYDRATION */),
    (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)(" Children (progressive rendering) "),
    ($options.hasChildren && $options.isExpanded)
      ? ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)("ul", _hoisted_5, [
          ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(true), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)(vue__WEBPACK_IMPORTED_MODULE_0__.Fragment, null, (0,vue__WEBPACK_IMPORTED_MODULE_0__.renderList)($options.visibleChildren, (child) => {
            return ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createBlock)(_component_PageTreeSelectItem, {
              key: child.uniqueId,
              item: child,
              "expanded-nodes": $props.expandedNodes,
              "selected-id": $props.selectedId,
              "focused-id": $props.focusedId,
              "search-query": $props.searchQuery,
              onToggle: _cache[3] || (_cache[3] = (id) => _ctx.$emit('toggle', id)),
              onSelect: _cache[4] || (_cache[4] = (page) => _ctx.$emit('select', page)),
              onFocus: _cache[5] || (_cache[5] = (id) => _ctx.$emit('focus', id))
            }, null, 8 /* PROPS */, ["item", "expanded-nodes", "selected-id", "focused-id", "search-query"]))
          }), 128 /* KEYED_FRAGMENT */)),
          ($options.hasMoreChildren)
            ? ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)("li", _hoisted_6, [
                (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("button", {
                  class: "show-more-button",
                  onClick: _cache[6] || (_cache[6] = (...args) => ($options.showMoreChildren && $options.showMoreChildren(...args)))
                }, " Show " + (0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)($props.item.children.length - $data.visibleChildCount) + " more... ", 1 /* TEXT */)
              ]))
            : (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)("v-if", true)
        ]))
      : (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)("v-if", true)
  ]))
}

/***/ },

/***/ "./node_modules/vue-loader/dist/templateLoader.js??ruleSet[1].rules[2]!./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/feed/FeedItem.vue?vue&type=template&id=82e10b3c&scoped=true"
/*!******************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/vue-loader/dist/templateLoader.js??ruleSet[1].rules[2]!./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/feed/FeedItem.vue?vue&type=template&id=82e10b3c&scoped=true ***!
  \******************************************************************************************************************************************************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   render: () => (/* binding */ render)
/* harmony export */ });
/* harmony import */ var vue__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! vue */ "./node_modules/vue/dist/vue.runtime.esm-bundler.js");


const _hoisted_1 = ["href", "aria-label", "target", "rel"]
const _hoisted_2 = {
  key: 0,
  class: "feed-item-image"
}
const _hoisted_3 = ["src", "alt"]
const _hoisted_4 = {
  key: 1,
  class: "feed-item-feed-icon"
}
const _hoisted_5 = ["src", "alt"]
const _hoisted_6 = {
  key: 0,
  class: "feed-item-fallback-label"
}
const _hoisted_7 = { class: "feed-item-content" }
const _hoisted_8 = { class: "feed-item-title" }
const _hoisted_9 = { class: "feed-item-meta" }
const _hoisted_10 = {
  key: 0,
  class: "feed-item-date"
}
const _hoisted_11 = {
  key: 1,
  class: "feed-item-source"
}
const _hoisted_12 = {
  key: 2,
  class: "feed-item-author"
}
const _hoisted_13 = {
  key: 0,
  class: "feed-item-excerpt"
}

function render(_ctx, _cache, $props, $setup, $data, $options) {
  const _component_CalendarBlank = (0,vue__WEBPACK_IMPORTED_MODULE_0__.resolveComponent)("CalendarBlank")
  const _component_OpenInNew = (0,vue__WEBPACK_IMPORTED_MODULE_0__.resolveComponent)("OpenInNew")

  return ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)("a", {
    href: $props.item.url,
    class: (0,vue__WEBPACK_IMPORTED_MODULE_0__.normalizeClass)(["feed-item", [
      { 'feed-item--compact': $props.compact, 'feed-item--no-image': !$props.showImage || (!$props.item.image && (!$props.feedImage || $data.feedImageError) && !$options.fallbackMeta) },
      `feed-item--bg-${$props.itemBackground}`
    ]]),
    "aria-label": $props.item.title + ($options.formattedDate ? ' — ' + $options.formattedDate : ''),
    target: $props.openInNewTab ? '_blank' : '_self',
    rel: $props.openInNewTab ? 'noopener noreferrer' : undefined
  }, [
    ($props.showImage && $props.item.image)
      ? ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)("div", _hoisted_2, [
          (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("img", {
            src: $props.item.image,
            alt: $props.item.title,
            loading: "lazy",
            referrerpolicy: "no-referrer"
          }, null, 8 /* PROPS */, _hoisted_3)
        ]))
      : ($props.showImage && $props.feedImage && !$data.feedImageError)
        ? ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)("div", _hoisted_4, [
            (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("img", {
              src: $props.feedImage,
              alt: $props.item.source || '',
              loading: "lazy",
              referrerpolicy: "no-referrer",
              onError: _cache[0] || (_cache[0] = $event => ($data.feedImageError = true))
            }, null, 40 /* PROPS, NEED_HYDRATION */, _hoisted_5)
          ]))
        : ($props.showImage && $options.fallbackMeta)
          ? ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)("div", {
              key: 2,
              class: "feed-item-fallback",
              style: (0,vue__WEBPACK_IMPORTED_MODULE_0__.normalizeStyle)({ backgroundColor: $options.fallbackMeta.color })
            }, [
              ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createBlock)((0,vue__WEBPACK_IMPORTED_MODULE_0__.resolveDynamicComponent)($options.fallbackMeta.icon), {
                size: $props.compact ? 20 : 22,
                class: "feed-item-fallback-icon"
              }, null, 8 /* PROPS */, ["size"])),
              ($props.item.fileType)
                ? ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)("span", _hoisted_6, (0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)($props.item.fileType.toUpperCase()), 1 /* TEXT */))
                : (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)("v-if", true)
            ], 4 /* STYLE */))
          : (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)("v-if", true),
    (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("div", _hoisted_7, [
      (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("h4", _hoisted_8, (0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)($props.item.title), 1 /* TEXT */),
      (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("div", _hoisted_9, [
        ($props.showDate && $props.item.date)
          ? ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)("span", _hoisted_10, [
              (0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_CalendarBlank, { size: 14 }),
              (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("span", null, (0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)($options.formattedDate), 1 /* TEXT */)
            ]))
          : (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)("v-if", true),
        ($props.showSource && $props.item.source)
          ? ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)("span", _hoisted_11, (0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)($props.item.source), 1 /* TEXT */))
          : (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)("v-if", true),
        ($props.item.author)
          ? ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)("span", _hoisted_12, (0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)($props.item.author), 1 /* TEXT */))
          : (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)("v-if", true)
      ]),
      ($props.showExcerpt && $props.item.excerpt)
        ? ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)("p", _hoisted_13, (0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)($options.truncatedExcerpt), 1 /* TEXT */))
        : (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)("v-if", true)
    ]),
    ($props.openInNewTab)
      ? ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createBlock)(_component_OpenInNew, {
          key: 3,
          size: 14,
          class: "feed-item-external-icon"
        }))
      : (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)("v-if", true)
  ], 10 /* CLASS, PROPS */, _hoisted_1))
}

/***/ },

/***/ "./node_modules/vue-loader/dist/templateLoader.js??ruleSet[1].rules[2]!./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/feed/FeedLayoutGrid.vue?vue&type=template&id=5b0e091f&scoped=true"
/*!************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/vue-loader/dist/templateLoader.js??ruleSet[1].rules[2]!./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/feed/FeedLayoutGrid.vue?vue&type=template&id=5b0e091f&scoped=true ***!
  \************************************************************************************************************************************************************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   render: () => (/* binding */ render)
/* harmony export */ });
/* harmony import */ var vue__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! vue */ "./node_modules/vue/dist/vue.runtime.esm-bundler.js");


function render(_ctx, _cache, $props, $setup, $data, $options) {
  const _component_FeedItem = (0,vue__WEBPACK_IMPORTED_MODULE_0__.resolveComponent)("FeedItem")

  return ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)("div", {
    class: "feed-layout-grid",
    style: (0,vue__WEBPACK_IMPORTED_MODULE_0__.normalizeStyle)($options.gridStyle)
  }, [
    ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(true), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)(vue__WEBPACK_IMPORTED_MODULE_0__.Fragment, null, (0,vue__WEBPACK_IMPORTED_MODULE_0__.renderList)($props.items, (item) => {
      return ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createBlock)(_component_FeedItem, {
        key: item.id,
        item: item,
        "show-image": $props.widget.showImage !== false,
        "show-date": $props.widget.showDate !== false,
        "show-excerpt": $props.widget.showExcerpt !== false,
        "show-source": $props.widget.showSource || false,
        "excerpt-length": $props.widget.excerptLength || 150,
        "open-in-new-tab": $props.widget.openInNewTab !== false,
        "item-background": $options.itemBackgroundMode,
        "feed-image": $props.feedImage,
        compact: true
      }, null, 8 /* PROPS */, ["item", "show-image", "show-date", "show-excerpt", "show-source", "excerpt-length", "open-in-new-tab", "item-background", "feed-image"]))
    }), 128 /* KEYED_FRAGMENT */))
  ], 4 /* STYLE */))
}

/***/ },

/***/ "./node_modules/vue-loader/dist/templateLoader.js??ruleSet[1].rules[2]!./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/feed/FeedLayoutList.vue?vue&type=template&id=cf647b12&scoped=true"
/*!************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/vue-loader/dist/templateLoader.js??ruleSet[1].rules[2]!./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/feed/FeedLayoutList.vue?vue&type=template&id=cf647b12&scoped=true ***!
  \************************************************************************************************************************************************************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   render: () => (/* binding */ render)
/* harmony export */ });
/* harmony import */ var vue__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! vue */ "./node_modules/vue/dist/vue.runtime.esm-bundler.js");


const _hoisted_1 = { class: "feed-layout-list" }

function render(_ctx, _cache, $props, $setup, $data, $options) {
  const _component_FeedItem = (0,vue__WEBPACK_IMPORTED_MODULE_0__.resolveComponent)("FeedItem")

  return ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)("div", _hoisted_1, [
    ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(true), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)(vue__WEBPACK_IMPORTED_MODULE_0__.Fragment, null, (0,vue__WEBPACK_IMPORTED_MODULE_0__.renderList)($props.items, (item) => {
      return ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createBlock)(_component_FeedItem, {
        key: item.id,
        item: item,
        "show-image": $props.widget.showImage !== false,
        "show-date": $props.widget.showDate !== false,
        "show-excerpt": $props.widget.showExcerpt !== false,
        "show-source": $props.widget.showSource || false,
        "excerpt-length": $props.widget.excerptLength || 150,
        "open-in-new-tab": $props.widget.openInNewTab !== false,
        "item-background": $options.itemBackgroundMode,
        "feed-image": $props.feedImage
      }, null, 8 /* PROPS */, ["item", "show-image", "show-date", "show-excerpt", "show-source", "excerpt-length", "open-in-new-tab", "item-background", "feed-image"]))
    }), 128 /* KEYED_FRAGMENT */))
  ]))
}

/***/ },

/***/ "./src/utils/colorUtils.js"
/*!*********************************!*\
  !*** ./src/utils/colorUtils.js ***!
  \*********************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   DARK_BACKGROUNDS: () => (/* binding */ DARK_BACKGROUNDS),
/* harmony export */   LIGHT_BACKGROUNDS: () => (/* binding */ LIGHT_BACKGROUNDS),
/* harmony export */   getEffectiveBackgroundColor: () => (/* binding */ getEffectiveBackgroundColor),
/* harmony export */   isDarkBackground: () => (/* binding */ isDarkBackground),
/* harmony export */   isLightBackground: () => (/* binding */ isLightBackground)
/* harmony export */ });
/**
 * Color utility functions for consistent background color handling across widgets.
 *
 * Nextcloud CSS variable reference:
 * - --color-primary-element: the main theme color (typically dark blue), needs white text
 * - --color-primary-element-light: a light tint of the theme color, needs dark text
 * - --color-primary: alias for primary-element
 * - --color-error: red, needs white text
 * - --color-success: green, needs white text
 * - --color-warning: amber/orange, needs dark text
 * - --color-background-hover: light gray hover, needs dark text
 * - --color-background-dark: slightly darker gray, needs dark text
 */

/**
 * Dark background colors that require light/white text.
 */
const DARK_BACKGROUNDS = [
  'var(--color-primary-element)',
  'var(--color-primary)',
  'var(--color-error)',
  'var(--color-success)',
];

/**
 * Light background colors that require dark text.
 * These are explicitly "light" backgrounds, distinct from the default page background.
 */
const LIGHT_BACKGROUNDS = [
  'var(--color-primary-element-light)',
  'var(--color-background-hover)',
  'var(--color-background-dark)',
  'var(--color-warning)',
];

/**
 * Check if a background color is considered "dark" and requires light text
 * @param {string} color - CSS color value (typically a CSS variable)
 * @returns {boolean} - True if the color is dark and needs white/light text
 */
function isDarkBackground(color) {
  return DARK_BACKGROUNDS.includes(color);
}

/**
 * Check if a background color is a known light tint (not transparent/default)
 * @param {string} color - CSS color value
 * @returns {boolean}
 */
function isLightBackground(color) {
  return LIGHT_BACKGROUNDS.includes(color);
}

/**
 * Get the effective background color for a widget
 * Widget's own backgroundColor takes precedence over row backgroundColor
 * @param {string} widgetBg - Widget's backgroundColor
 * @param {string} rowBg - Row's backgroundColor
 * @returns {string} - The effective background color to use
 */
function getEffectiveBackgroundColor(widgetBg, rowBg) {
  return widgetBg || rowBg || '';
}


/***/ },

/***/ "?37ae"
/*!************************!*\
  !*** stream (ignored) ***!
  \************************/
() {

/* (ignored) */

/***/ },

/***/ "?5bcd"
/*!********************************!*\
  !*** string_decoder (ignored) ***!
  \********************************/
() {

/* (ignored) */

/***/ }

}]);
//# sourceMappingURL=intravox-shared.js.map