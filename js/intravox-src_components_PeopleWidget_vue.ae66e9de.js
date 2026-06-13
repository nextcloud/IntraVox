"use strict";
(self["webpackChunknextcloud_intravox"] = self["webpackChunknextcloud_intravox"] || []).push([["src_components_PeopleWidget_vue"],{

/***/ "./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/dist/stylePostLoader.js!./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/PeopleWidget.vue?vue&type=style&index=0&id=253d9463&scoped=true&lang=css"
/*!*************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/dist/stylePostLoader.js!./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/PeopleWidget.vue?vue&type=style&index=0&id=253d9463&scoped=true&lang=css ***!
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
.people-widget[data-v-253d9463] {
  width: 100%;
  margin: 12px 0;
  container-type: inline-size;
}
.people-widget-title[data-v-253d9463] {
  margin: 0 0 16px 0;
  font-size: 18px;
  font-weight: 600;
  color: var(--color-main-text);
}
.people-loading[data-v-253d9463],
.people-error[data-v-253d9463],
.people-empty[data-v-253d9463] {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 12px;
  padding: 40px 20px;
  color: var(--color-text-maxcontrast);
  text-align: center;
}
.people-error[data-v-253d9463] {
  color: var(--color-error);
}
.people-empty p[data-v-253d9463] {
  margin: 0;
  font-size: 14px;
}
.people-empty small[data-v-253d9463] {
  font-size: 12px;
  opacity: 0.7;
}
.people-footer[data-v-253d9463] {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 12px;
  padding: 16px 0 0;
  margin-top: 16px;
  border-top: 1px solid var(--color-border);
}
.people-count[data-v-253d9463] {
  font-size: 13px;
  color: inherit; /* Inherit from footerStyle */
  opacity: 0.85;
}

/* Dark background adjustments */
.people-footer.dark-background[data-v-253d9463] {
  border-top-color: rgba(255, 255, 255, 0.2);
}
.people-footer.dark-background .people-count[data-v-253d9463] {
  opacity: 0.9;
}
`, "",{"version":3,"sources":["webpack://./src/components/PeopleWidget.vue"],"names":[],"mappings":";AA6QA;EACE,WAAW;EACX,cAAc;EACd,2BAA2B;AAC7B;AAEA;EACE,kBAAkB;EAClB,eAAe;EACf,gBAAgB;EAChB,6BAA6B;AAC/B;AAEA;;;EAGE,aAAa;EACb,sBAAsB;EACtB,mBAAmB;EACnB,uBAAuB;EACvB,SAAS;EACT,kBAAkB;EAClB,oCAAoC;EACpC,kBAAkB;AACpB;AAEA;EACE,yBAAyB;AAC3B;AAEA;EACE,SAAS;EACT,eAAe;AACjB;AAEA;EACE,eAAe;EACf,YAAY;AACd;AAEA;EACE,aAAa;EACb,sBAAsB;EACtB,mBAAmB;EACnB,SAAS;EACT,iBAAiB;EACjB,gBAAgB;EAChB,yCAAyC;AAC3C;AAEA;EACE,eAAe;EACf,cAAc,EAAE,6BAA6B;EAC7C,aAAa;AACf;;AAEA,gCAAgC;AAChC;EACE,0CAA0C;AAC5C;AAEA;EACE,YAAY;AACd","sourcesContent":["<template>\r\n  <div class=\"people-widget\" :style=\"getContainerStyle()\">\r\n    <h3 v-if=\"widget.title\" class=\"people-widget-title\" :style=\"titleStyle\">\r\n      {{ widget.title }}\r\n    </h3>\r\n\r\n    <div v-if=\"loading && users.length === 0\" class=\"people-loading\">\r\n      <NcLoadingIcon :size=\"32\" />\r\n      <span>{{ t('Loading...') }}</span>\r\n    </div>\r\n\r\n    <div v-else-if=\"error\" class=\"people-error\">\r\n      <AlertCircle :size=\"24\" />\r\n      <span>{{ error }}</span>\r\n    </div>\r\n\r\n    <div v-else-if=\"users.length === 0\" class=\"people-empty\">\r\n      <AccountGroup :size=\"32\" />\r\n      <p>{{ t('No people found') }}</p>\r\n      <small v-if=\"widget.selectionMode === 'manual'\">\r\n        {{ t('Select people in edit mode') }}\r\n      </small>\r\n      <small v-else>\r\n        {{ t('No users match the current filters') }}\r\n      </small>\r\n    </div>\r\n\r\n    <template v-else>\r\n      <component\r\n        :is=\"layoutComponent\"\r\n        :users=\"users\"\r\n        :widget=\"widget\"\r\n        :row-background-color=\"effectiveBackgroundColor\"\r\n      />\r\n\r\n      <!-- Pagination footer -->\r\n      <div v-if=\"showPaginationFooter\" class=\"people-footer\" :class=\"{ 'dark-background': isDarkBackground }\" :style=\"footerStyle\">\r\n        <span class=\"people-count\">\r\n          {{ t('Showing {shown} of {total} people', { shown: users.length, total: total }) }}\r\n        </span>\r\n        <NcButton\r\n          v-if=\"hasMore\"\r\n          :type=\"isDarkBackground ? 'primary' : 'secondary'\"\r\n          :disabled=\"loadingMore\"\r\n          @click=\"loadMore\"\r\n        >\r\n          <template #icon>\r\n            <NcLoadingIcon v-if=\"loadingMore\" :size=\"20\" />\r\n          </template>\r\n          {{ loadingMore ? t('Loading...') : t('Show more') }}\r\n        </NcButton>\r\n      </div>\r\n    </template>\r\n  </div>\r\n</template>\r\n\r\n<script>\r\nimport { NcLoadingIcon, NcButton } from '@nextcloud/vue';\r\nimport { generateUrl } from '@nextcloud/router';\r\nimport { translate as t } from '@nextcloud/l10n';\r\nimport axios from '@nextcloud/axios';\r\nimport AlertCircle from 'vue-material-design-icons/AlertCircle.vue';\r\nimport AccountGroup from 'vue-material-design-icons/AccountGroup.vue';\r\nimport PeopleLayoutCard from './people/PeopleLayoutCard.vue';\r\nimport PeopleLayoutList from './people/PeopleLayoutList.vue';\r\nimport PeopleLayoutGrid from './people/PeopleLayoutGrid.vue';\r\n\r\nexport default {\r\n  name: 'PeopleWidget',\r\n  components: {\r\n    NcLoadingIcon,\r\n    NcButton,\r\n    AlertCircle,\r\n    AccountGroup,\r\n    PeopleLayoutCard,\r\n    PeopleLayoutList,\r\n    PeopleLayoutGrid,\r\n  },\r\n  props: {\r\n    widget: {\r\n      type: Object,\r\n      required: true,\r\n    },\r\n    editable: {\r\n      type: Boolean,\r\n      default: false,\r\n    },\r\n    rowBackgroundColor: {\r\n      type: String,\r\n      default: '',\r\n    },\r\n    shareToken: {\r\n      type: String,\r\n      default: '',\r\n    },\r\n    pageId: {\r\n      type: String,\r\n      default: '',\r\n    },\r\n  },\r\n  data() {\r\n    return {\r\n      users: [],\r\n      total: 0,\r\n      hasMore: false,\r\n      loading: true,\r\n      loadingMore: false,\r\n      error: null,\r\n    };\r\n  },\r\n  computed: {\r\n    layoutComponent() {\r\n      const layouts = {\r\n        card: PeopleLayoutCard,\r\n        list: PeopleLayoutList,\r\n        grid: PeopleLayoutGrid,\r\n      };\r\n      return layouts[this.widget.layout] || PeopleLayoutCard;\r\n    },\r\n    effectiveBackgroundColor() {\r\n      return this.widget.backgroundColor || this.rowBackgroundColor || '';\r\n    },\r\n    titleStyle() {\r\n      const bgColor = this.effectiveBackgroundColor;\r\n\r\n      const colorMappings = {\r\n        'var(--color-primary-element)': 'var(--color-primary-element-text)',\r\n        'var(--color-primary-element-light)': 'var(--color-primary-element-light-text)',\r\n        'var(--color-error)': 'var(--color-error-text)',\r\n        'var(--color-warning)': 'var(--color-warning-text)',\r\n        'var(--color-success)': 'var(--color-success-text)',\r\n        'var(--color-background-dark)': 'var(--color-main-text)',\r\n        'var(--color-background-hover)': 'var(--color-main-text)',\r\n      };\r\n\r\n      const textColor = colorMappings[bgColor];\r\n      if (textColor) {\r\n        return { color: textColor };\r\n      }\r\n      return {};\r\n    },\r\n    footerStyle() {\r\n      // Use same color mapping as title, plus border adjustment for dark backgrounds\r\n      const style = { ...this.titleStyle };\r\n      if (this.isDarkBackground) {\r\n        style.borderColor = 'rgba(255, 255, 255, 0.2)';\r\n      }\r\n      return style;\r\n    },\r\n    isDarkBackground() {\r\n      const bgColor = this.effectiveBackgroundColor;\r\n      // These background colors need light text\r\n      const darkBackgrounds = [\r\n        'var(--color-primary-element)',\r\n        'var(--color-error)',\r\n        'var(--color-warning)',\r\n        'var(--color-success)',\r\n      ];\r\n      return darkBackgrounds.includes(bgColor);\r\n    },\r\n    showPaginationFooter() {\r\n      // Show footer if there are more items than shown, or if we loaded additional items\r\n      // Also respect showPagination setting (default true)\r\n      const showPagination = this.widget.showPagination !== false;\r\n      return showPagination && this.total > 0 && (this.hasMore || this.users.length < this.total || this.users.length > (this.widget.limit || 50));\r\n    },\r\n  },\r\n  watch: {\r\n    widget: {\r\n      deep: true,\r\n      handler() {\r\n        clearTimeout(this._debounceTimer);\r\n        this._debounceTimer = setTimeout(() => {\r\n          // Reset pagination when widget config changes\r\n          this.users = [];\r\n          this.total = 0;\r\n          this.hasMore = false;\r\n          this.fetchPeople();\r\n        }, 300);\r\n      },\r\n    },\r\n  },\r\n  mounted() {\r\n    this.fetchPeople();\r\n  },\r\n  beforeUnmount() {\r\n    clearTimeout(this._debounceTimer);\r\n  },\r\n  methods: {\r\n    t(key, vars = {}) {\r\n      return t('intravox', key, vars);\r\n    },\r\n    async fetchPeople(offset = 0) {\r\n      if (offset === 0) {\r\n        this.loading = true;\r\n      }\r\n      this.error = null;\r\n\r\n      try {\r\n        const limit = this.widget.limit || 50;\r\n        const params = new URLSearchParams({\r\n          sortBy: this.widget.sortBy || 'displayName',\r\n          sortOrder: this.widget.sortOrder || 'asc',\r\n          limit: String(limit),\r\n          offset: String(offset),\r\n        });\r\n\r\n        // Manual mode: pass user IDs\r\n        if (this.widget.selectionMode === 'manual' && this.widget.selectedUsers?.length > 0) {\r\n          params.append('userIds', this.widget.selectedUsers.join(','));\r\n        }\r\n        // Filter mode: pass filters\r\n        else if (this.widget.selectionMode === 'filter' && this.widget.filters?.length > 0) {\r\n          params.append('filters', JSON.stringify(this.widget.filters));\r\n          params.append('filterOperator', this.widget.filterOperator || 'AND');\r\n        }\r\n        // No users configured\r\n        else {\r\n          this.users = [];\r\n          this.total = 0;\r\n          this.hasMore = false;\r\n          this.loading = false;\r\n          return;\r\n        }\r\n\r\n        const url = this.shareToken\r\n          ? generateUrl(`/apps/intravox/api/share/${this.shareToken}/people?${params}`)\r\n          : generateUrl(`/apps/intravox/api/people?${params}`);\r\n\r\n        const response = await axios.get(url);\r\n        const newUsers = response.data.users || [];\r\n\r\n        if (offset === 0) {\r\n          this.users = newUsers;\r\n        } else {\r\n          this.users = [...this.users, ...newUsers];\r\n        }\r\n\r\n        this.total = response.data.total || this.users.length;\r\n        this.hasMore = response.data.hasMore || false;\r\n      } catch (err) {\r\n        console.error('[PeopleWidget] Failed to fetch people:', err);\r\n        this.error = this.t('Failed to load people');\r\n      } finally {\r\n        this.loading = false;\r\n        this.loadingMore = false;\r\n      }\r\n    },\r\n    async loadMore() {\r\n      if (this.loadingMore || !this.hasMore) return;\r\n      this.loadingMore = true;\r\n      await this.fetchPeople(this.users.length);\r\n    },\r\n    getContainerStyle() {\r\n      const style = {};\r\n      if (this.widget.backgroundColor) {\r\n        style.backgroundColor = this.widget.backgroundColor;\r\n        style.padding = '20px';\r\n        style.borderRadius = 'var(--border-radius-large)';\r\n        // Negative margin-top pulls the widget up so title aligns with widgets without background\r\n        style.marginTop = '-8px';\r\n      }\r\n      return style;\r\n    },\r\n  },\r\n};\r\n</script>\r\n\r\n<style scoped>\r\n.people-widget {\r\n  width: 100%;\r\n  margin: 12px 0;\r\n  container-type: inline-size;\r\n}\r\n\r\n.people-widget-title {\r\n  margin: 0 0 16px 0;\r\n  font-size: 18px;\r\n  font-weight: 600;\r\n  color: var(--color-main-text);\r\n}\r\n\r\n.people-loading,\r\n.people-error,\r\n.people-empty {\r\n  display: flex;\r\n  flex-direction: column;\r\n  align-items: center;\r\n  justify-content: center;\r\n  gap: 12px;\r\n  padding: 40px 20px;\r\n  color: var(--color-text-maxcontrast);\r\n  text-align: center;\r\n}\r\n\r\n.people-error {\r\n  color: var(--color-error);\r\n}\r\n\r\n.people-empty p {\r\n  margin: 0;\r\n  font-size: 14px;\r\n}\r\n\r\n.people-empty small {\r\n  font-size: 12px;\r\n  opacity: 0.7;\r\n}\r\n\r\n.people-footer {\r\n  display: flex;\r\n  flex-direction: column;\r\n  align-items: center;\r\n  gap: 12px;\r\n  padding: 16px 0 0;\r\n  margin-top: 16px;\r\n  border-top: 1px solid var(--color-border);\r\n}\r\n\r\n.people-count {\r\n  font-size: 13px;\r\n  color: inherit; /* Inherit from footerStyle */\r\n  opacity: 0.85;\r\n}\r\n\r\n/* Dark background adjustments */\r\n.people-footer.dark-background {\r\n  border-top-color: rgba(255, 255, 255, 0.2);\r\n}\r\n\r\n.people-footer.dark-background .people-count {\r\n  opacity: 0.9;\r\n}\r\n</style>\r\n"],"sourceRoot":""}]);
// Exports
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (___CSS_LOADER_EXPORT___);


/***/ },

/***/ "./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/dist/stylePostLoader.js!./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/people/PeopleLayoutCard.vue?vue&type=style&index=0&id=5ec2ee89&scoped=true&lang=css"
/*!************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/dist/stylePostLoader.js!./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/people/PeopleLayoutCard.vue?vue&type=style&index=0&id=5ec2ee89&scoped=true&lang=css ***!
  \************************************************************************************************************************************************************************************************************************************************************************/
(module, __webpack_exports__, __webpack_require__) {

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
.people-layout-card[data-v-5ec2ee89] {
  display: grid;
  gap: 16px;
  grid-template-columns: repeat(3, 1fr);
}
@container (max-width: 900px) {
.people-layout-card[data-v-5ec2ee89] {
    grid-template-columns: repeat(2, 1fr) !important;
}
}
@container (max-width: 500px) {
.people-layout-card[data-v-5ec2ee89] {
    grid-template-columns: 1fr !important;
}
}

/* Very narrow containers (side columns) - always single column */
@container (max-width: 300px) {
.people-layout-card[data-v-5ec2ee89] {
    grid-template-columns: 1fr !important;
    gap: 12px;
}
}

/* Fallback for browsers without container query support */
@media (max-width: 900px) {
.people-layout-card[data-v-5ec2ee89] {
    grid-template-columns: repeat(2, 1fr) !important;
}
}
@media (max-width: 600px) {
.people-layout-card[data-v-5ec2ee89] {
    grid-template-columns: 1fr !important;
}
}
`, "",{"version":3,"sources":["webpack://./src/components/people/PeopleLayoutCard.vue"],"names":[],"mappings":";AAqEA;EACE,aAAa;EACb,SAAS;EACT,qCAAqC;AACvC;AAEA;AACE;IACE,gDAAgD;AAClD;AACF;AAEA;AACE;IACE,qCAAqC;AACvC;AACF;;AAEA,iEAAiE;AACjE;AACE;IACE,qCAAqC;IACrC,SAAS;AACX;AACF;;AAEA,0DAA0D;AAC1D;AACE;IACE,gDAAgD;AAClD;AACF;AAEA;AACE;IACE,qCAAqC;AACvC;AACF","sourcesContent":["<template>\r\n  <div class=\"people-layout-card\" :style=\"gridStyle\">\r\n    <PersonItem\r\n      v-for=\"user in users\"\r\n      :key=\"user.uid\"\r\n      :user=\"user\"\r\n      layout=\"card\"\r\n      :show-fields=\"widget.showFields || {}\"\r\n      :item-background=\"itemBackgroundMode\"\r\n      class=\"people-card-item\"\r\n    />\r\n  </div>\r\n</template>\r\n\r\n<script>\r\nimport PersonItem from './PersonItem.vue';\r\nimport { isDarkBackground as isDarkBg, isLightBackground as isLightBg, getEffectiveBackgroundColor } from '../../utils/colorUtils.js';\r\n\r\nexport default {\r\n  name: 'PeopleLayoutCard',\r\n  components: {\r\n    PersonItem,\r\n  },\r\n  props: {\r\n    users: {\r\n      type: Array,\r\n      required: true,\r\n    },\r\n    widget: {\r\n      type: Object,\r\n      required: true,\r\n    },\r\n    rowBackgroundColor: {\r\n      type: String,\r\n      default: '',\r\n    },\r\n  },\r\n  computed: {\r\n    gridStyle() {\r\n      const columns = this.widget.columns || 3;\r\n      return {\r\n        gridTemplateColumns: `repeat(${columns}, 1fr)`,\r\n      };\r\n    },\r\n    effectiveBackgroundColor() {\r\n      return getEffectiveBackgroundColor(this.widget.backgroundColor, this.rowBackgroundColor);\r\n    },\r\n    itemBackgroundMode() {\r\n      const containerBg = this.effectiveBackgroundColor;\r\n\r\n      if (!containerBg) {\r\n        return 'default';\r\n      }\r\n\r\n      if (isDarkBg(containerBg)) {\r\n        return 'dark';\r\n      }\r\n\r\n      if (isLightBg(containerBg)) {\r\n        return 'white';\r\n      }\r\n\r\n      return 'default';\r\n    },\r\n  },\r\n};\r\n</script>\r\n\r\n<style scoped>\r\n.people-layout-card {\r\n  display: grid;\r\n  gap: 16px;\r\n  grid-template-columns: repeat(3, 1fr);\r\n}\r\n\r\n@container (max-width: 900px) {\r\n  .people-layout-card {\r\n    grid-template-columns: repeat(2, 1fr) !important;\r\n  }\r\n}\r\n\r\n@container (max-width: 500px) {\r\n  .people-layout-card {\r\n    grid-template-columns: 1fr !important;\r\n  }\r\n}\r\n\r\n/* Very narrow containers (side columns) - always single column */\r\n@container (max-width: 300px) {\r\n  .people-layout-card {\r\n    grid-template-columns: 1fr !important;\r\n    gap: 12px;\r\n  }\r\n}\r\n\r\n/* Fallback for browsers without container query support */\r\n@media (max-width: 900px) {\r\n  .people-layout-card {\r\n    grid-template-columns: repeat(2, 1fr) !important;\r\n  }\r\n}\r\n\r\n@media (max-width: 600px) {\r\n  .people-layout-card {\r\n    grid-template-columns: 1fr !important;\r\n  }\r\n}\r\n</style>\r\n"],"sourceRoot":""}]);
// Exports
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (___CSS_LOADER_EXPORT___);


/***/ },

/***/ "./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/dist/stylePostLoader.js!./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/people/PeopleLayoutGrid.vue?vue&type=style&index=0&id=7235f3bf&scoped=true&lang=css"
/*!************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/dist/stylePostLoader.js!./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/people/PeopleLayoutGrid.vue?vue&type=style&index=0&id=7235f3bf&scoped=true&lang=css ***!
  \************************************************************************************************************************************************************************************************************************************************************************/
(module, __webpack_exports__, __webpack_require__) {

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
.people-layout-grid[data-v-7235f3bf] {
  display: grid;
  gap: 12px;
  grid-template-columns: repeat(4, 1fr);
  align-items: start; /* Align items to top to prevent uneven row heights */
}
@container (max-width: 800px) {
.people-layout-grid[data-v-7235f3bf] {
    grid-template-columns: repeat(3, 1fr) !important;
}
}
@container (max-width: 500px) {
.people-layout-grid[data-v-7235f3bf] {
    grid-template-columns: repeat(2, 1fr) !important;
}
}

/* Fallback for browsers without container query support */
@media (max-width: 900px) {
.people-layout-grid[data-v-7235f3bf] {
    grid-template-columns: repeat(3, 1fr) !important;
}
}
@media (max-width: 600px) {
.people-layout-grid[data-v-7235f3bf] {
    grid-template-columns: repeat(2, 1fr) !important;
}
}
`, "",{"version":3,"sources":["webpack://./src/components/people/PeopleLayoutGrid.vue"],"names":[],"mappings":";AAqEA;EACE,aAAa;EACb,SAAS;EACT,qCAAqC;EACrC,kBAAkB,EAAE,qDAAqD;AAC3E;AAEA;AACE;IACE,gDAAgD;AAClD;AACF;AAEA;AACE;IACE,gDAAgD;AAClD;AACF;;AAEA,0DAA0D;AAC1D;AACE;IACE,gDAAgD;AAClD;AACF;AAEA;AACE;IACE,gDAAgD;AAClD;AACF","sourcesContent":["<template>\r\n  <div class=\"people-layout-grid\" :style=\"gridStyle\">\r\n    <PersonItem\r\n      v-for=\"user in users\"\r\n      :key=\"user.uid\"\r\n      :user=\"user\"\r\n      layout=\"grid\"\r\n      :show-fields=\"widget.showFields || {}\"\r\n      :item-background=\"itemBackgroundMode\"\r\n      class=\"people-grid-item\"\r\n    />\r\n  </div>\r\n</template>\r\n\r\n<script>\r\nimport PersonItem from './PersonItem.vue';\r\nimport { isDarkBackground as isDarkBg, isLightBackground as isLightBg, getEffectiveBackgroundColor } from '../../utils/colorUtils.js';\r\n\r\nexport default {\r\n  name: 'PeopleLayoutGrid',\r\n  components: {\r\n    PersonItem,\r\n  },\r\n  props: {\r\n    users: {\r\n      type: Array,\r\n      required: true,\r\n    },\r\n    widget: {\r\n      type: Object,\r\n      required: true,\r\n    },\r\n    rowBackgroundColor: {\r\n      type: String,\r\n      default: '',\r\n    },\r\n  },\r\n  computed: {\r\n    gridStyle() {\r\n      const columns = this.widget.columns || 4;\r\n      return {\r\n        gridTemplateColumns: `repeat(${columns}, 1fr)`,\r\n      };\r\n    },\r\n    effectiveBackgroundColor() {\r\n      return getEffectiveBackgroundColor(this.widget.backgroundColor, this.rowBackgroundColor);\r\n    },\r\n    itemBackgroundMode() {\r\n      const containerBg = this.effectiveBackgroundColor;\r\n\r\n      if (!containerBg) {\r\n        return 'default';\r\n      }\r\n\r\n      if (isDarkBg(containerBg)) {\r\n        return 'dark';\r\n      }\r\n\r\n      if (isLightBg(containerBg)) {\r\n        return 'white';\r\n      }\r\n\r\n      return 'default';\r\n    },\r\n  },\r\n};\r\n</script>\r\n\r\n<style scoped>\r\n.people-layout-grid {\r\n  display: grid;\r\n  gap: 12px;\r\n  grid-template-columns: repeat(4, 1fr);\r\n  align-items: start; /* Align items to top to prevent uneven row heights */\r\n}\r\n\r\n@container (max-width: 800px) {\r\n  .people-layout-grid {\r\n    grid-template-columns: repeat(3, 1fr) !important;\r\n  }\r\n}\r\n\r\n@container (max-width: 500px) {\r\n  .people-layout-grid {\r\n    grid-template-columns: repeat(2, 1fr) !important;\r\n  }\r\n}\r\n\r\n/* Fallback for browsers without container query support */\r\n@media (max-width: 900px) {\r\n  .people-layout-grid {\r\n    grid-template-columns: repeat(3, 1fr) !important;\r\n  }\r\n}\r\n\r\n@media (max-width: 600px) {\r\n  .people-layout-grid {\r\n    grid-template-columns: repeat(2, 1fr) !important;\r\n  }\r\n}\r\n</style>\r\n"],"sourceRoot":""}]);
// Exports
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (___CSS_LOADER_EXPORT___);


/***/ },

/***/ "./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/dist/stylePostLoader.js!./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/people/PeopleLayoutList.vue?vue&type=style&index=0&id=a114a5d2&scoped=true&lang=css"
/*!************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/dist/stylePostLoader.js!./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/people/PeopleLayoutList.vue?vue&type=style&index=0&id=a114a5d2&scoped=true&lang=css ***!
  \************************************************************************************************************************************************************************************************************************************************************************/
(module, __webpack_exports__, __webpack_require__) {

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
.people-layout-list[data-v-a114a5d2] {
  display: flex;
  flex-direction: column;
  border-radius: var(--border-radius);
  overflow: hidden;
}
`, "",{"version":3,"sources":["webpack://./src/components/people/PeopleLayoutList.vue"],"names":[],"mappings":";AA+DA;EACE,aAAa;EACb,sBAAsB;EACtB,mCAAmC;EACnC,gBAAgB;AAClB","sourcesContent":["<template>\r\n  <div class=\"people-layout-list\">\r\n    <PersonItem\r\n      v-for=\"user in users\"\r\n      :key=\"user.uid\"\r\n      :user=\"user\"\r\n      layout=\"list\"\r\n      :show-fields=\"widget.showFields || {}\"\r\n      :item-background=\"itemBackgroundMode\"\r\n      class=\"people-list-item\"\r\n    />\r\n  </div>\r\n</template>\r\n\r\n<script>\r\nimport PersonItem from './PersonItem.vue';\r\nimport { isDarkBackground as isDarkBg, isLightBackground as isLightBg, getEffectiveBackgroundColor } from '../../utils/colorUtils.js';\r\n\r\nexport default {\r\n  name: 'PeopleLayoutList',\r\n  components: {\r\n    PersonItem,\r\n  },\r\n  props: {\r\n    users: {\r\n      type: Array,\r\n      required: true,\r\n    },\r\n    widget: {\r\n      type: Object,\r\n      required: true,\r\n    },\r\n    rowBackgroundColor: {\r\n      type: String,\r\n      default: '',\r\n    },\r\n  },\r\n  computed: {\r\n    effectiveBackgroundColor() {\r\n      return getEffectiveBackgroundColor(this.widget.backgroundColor, this.rowBackgroundColor);\r\n    },\r\n    itemBackgroundMode() {\r\n      const containerBg = this.effectiveBackgroundColor;\r\n\r\n      if (!containerBg) {\r\n        return 'transparent';\r\n      }\r\n\r\n      if (isDarkBg(containerBg)) {\r\n        return 'dark';\r\n      }\r\n\r\n      if (isLightBg(containerBg)) {\r\n        return 'white';\r\n      }\r\n\r\n      return 'transparent';\r\n    },\r\n  },\r\n};\r\n</script>\r\n\r\n<style scoped>\r\n.people-layout-list {\r\n  display: flex;\r\n  flex-direction: column;\r\n  border-radius: var(--border-radius);\r\n  overflow: hidden;\r\n}\r\n</style>\r\n"],"sourceRoot":""}]);
// Exports
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (___CSS_LOADER_EXPORT___);


/***/ },

/***/ "./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/dist/stylePostLoader.js!./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/people/PersonItem.vue?vue&type=style&index=0&id=e9a9c070&scoped=true&lang=css"
/*!******************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/dist/stylePostLoader.js!./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/people/PersonItem.vue?vue&type=style&index=0&id=e9a9c070&scoped=true&lang=css ***!
  \******************************************************************************************************************************************************************************************************************************************************************/
(module, __webpack_exports__, __webpack_require__) {

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
.person-item[data-v-e9a9c070] {
  display: flex;
  gap: 12px;
  padding: 16px;
  border-radius: var(--border-radius-large);
  transition: background-color 0.2s ease;
  container-type: inline-size;
}

/* Layout variants */
.person-item.layout-card[data-v-e9a9c070] {
  flex-direction: column;
  align-items: center;
  text-align: center;
  background: var(--color-background-hover);
  min-width: 0; /* Allow shrinking in flex/grid containers */
  overflow: hidden;
}
.person-item.layout-list[data-v-e9a9c070] {
  flex-direction: row;
  align-items: center;
  background: transparent;
  padding: 8px 12px;
  border-bottom: 1px solid var(--color-border);
}
.person-item.layout-list[data-v-e9a9c070]:last-child {
  border-bottom: none;
}
.person-item.layout-grid[data-v-e9a9c070] {
  flex-direction: column;
  align-items: center;
  text-align: center;
  padding: 12px;
  background: var(--color-background-hover);
}

/* Background modes */
.person-item.background-transparent[data-v-e9a9c070] {
  background: transparent;
}
.person-item.background-dark[data-v-e9a9c070] {
  background: rgba(255, 255, 255, 0.1);
}
.person-item.background-dark .person-name[data-v-e9a9c070],
.person-item.background-dark .person-role[data-v-e9a9c070],
.person-item.background-dark .person-headline[data-v-e9a9c070],
.person-item.background-dark .person-title[data-v-e9a9c070],
.person-item.background-dark .person-department[data-v-e9a9c070],
.person-item.background-dark .person-contact-item[data-v-e9a9c070],
.person-item.background-dark .person-social-item[data-v-e9a9c070],
.person-item.background-dark .person-biography[data-v-e9a9c070],
.person-item.background-dark .person-custom-field[data-v-e9a9c070],
.person-item.background-dark .custom-field-label[data-v-e9a9c070],
.person-item.background-dark .custom-field-value[data-v-e9a9c070] {
  color: var(--color-primary-element-text);
}
.person-item.background-dark .custom-field-label[data-v-e9a9c070] {
  opacity: 0.8;
}
.person-item.background-white[data-v-e9a9c070] {
  background: rgba(255, 255, 255, 0.9);
}
.person-item.background-default[data-v-e9a9c070] {
  background: var(--color-background-hover);
}

/* Avatar */
.person-avatar[data-v-e9a9c070] {
  flex-shrink: 0;
}
.layout-card .person-avatar[data-v-e9a9c070] {
  display: flex;
  justify-content: center;
}

/* Content */
.person-content[data-v-e9a9c070] {
  flex: 1;
  min-width: 0;
  max-width: 100%;
  overflow: hidden;
}
.layout-card .person-content[data-v-e9a9c070] {
  width: 100%;
}
.person-name[data-v-e9a9c070] {
  margin: 0 0 4px 0;
  font-size: 16px;
  font-weight: 600;
  color: var(--color-main-text);
  line-height: 1.3;
  word-break: break-word;
  overflow-wrap: break-word;
}
.layout-card .person-name[data-v-e9a9c070] {
  /* Allow name to wrap naturally in card layout */
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}
.person-pronouns[data-v-e9a9c070] {
  font-weight: 400;
  font-size: 14px;
  color: var(--color-text-maxcontrast);
}
.layout-grid .person-name[data-v-e9a9c070] {
  font-size: 14px;
  margin-top: 8px;
  /* Limit name to 2 lines for consistent grid item height */
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
  word-break: break-word;
}

/* Role - official job title */
.person-role[data-v-e9a9c070] {
  margin: 0 0 2px 0;
  font-size: 14px;
  font-weight: 500;
  color: var(--color-main-text);
  word-break: break-word;
  overflow-wrap: break-word;
}
.layout-card .person-role[data-v-e9a9c070] {
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}
.layout-grid .person-role[data-v-e9a9c070] {
  display: -webkit-box;
  -webkit-line-clamp: 1;
  -webkit-box-orient: vertical;
  overflow: hidden;
  word-break: break-word;
}

/* Headline - personal tagline */
.person-headline[data-v-e9a9c070] {
  margin: 0 0 2px 0;
  font-size: 13px;
  font-style: italic;
  color: var(--color-text-maxcontrast);
  word-break: break-word;
  overflow-wrap: break-word;
}
.layout-card .person-headline[data-v-e9a9c070] {
  display: -webkit-box;
  -webkit-line-clamp: 1;
  -webkit-box-orient: vertical;
  overflow: hidden;
}


/* Legacy .person-title for backwards compatibility */
.person-title[data-v-e9a9c070] {
  margin: 0 0 2px 0;
  font-size: 14px;
  color: var(--color-text-maxcontrast);
  word-break: break-word;
  overflow-wrap: break-word;
}
.layout-card .person-title[data-v-e9a9c070] {
  /* Allow title to wrap in card layout */
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}
.layout-grid .person-title[data-v-e9a9c070] {
  /* Limit title to 2 lines for consistent grid item height */
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
  word-break: break-word;
}
.person-department[data-v-e9a9c070] {
  margin: 0 0 8px 0;
  font-size: 13px;
  color: var(--color-text-maxcontrast);
  word-break: break-word;
  overflow-wrap: break-word;
}

/* Contact info */
.person-contact[data-v-e9a9c070] {
  display: flex;
  flex-direction: column;
  gap: 4px;
  margin-top: 8px;
}
.layout-card .person-contact[data-v-e9a9c070] {
  align-items: center;
}
.person-contact-item[data-v-e9a9c070] {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  color: var(--color-primary-element);
  text-decoration: none;
  font-size: 13px;
}
.person-contact-item.person-address[data-v-e9a9c070],
.person-contact-item.person-birthdate[data-v-e9a9c070] {
  color: var(--color-text-maxcontrast);
}
a.person-contact-item[data-v-e9a9c070]:hover {
  text-decoration: underline;
}
.person-contact-item span[data-v-e9a9c070] {
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
  max-width: 100%;
}

/* Compact card layout for narrow containers */
.layout-card .person-contact-item[data-v-e9a9c070] {
  max-width: 100%;
}
.layout-card .person-contact-item span[data-v-e9a9c070] {
  max-width: calc(100% - 22px); /* Account for icon width */
}

/* Social links */
.person-social[data-v-e9a9c070] {
  display: flex;
  gap: 8px;
  margin-top: 8px;
}
.layout-card .person-social[data-v-e9a9c070] {
  justify-content: center;
}
.person-social-item[data-v-e9a9c070] {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 32px;
  height: 32px;
  border-radius: 50%;
  background: var(--color-background-dark);
  color: var(--color-main-text);
  text-decoration: none;
  transition: background-color 0.2s ease;
}
.person-social-item[data-v-e9a9c070]:hover {
  background: var(--color-primary-element);
  color: var(--color-primary-element-text);
}

/* Biography */
.person-biography[data-v-e9a9c070] {
  margin: 12px 0 0 0;
  font-size: 13px;
  color: var(--color-text-maxcontrast);
  line-height: 1.5;
}

/* Custom fields (LDAP/OIDC) */
.person-custom-fields[data-v-e9a9c070] {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 6px 12px;
  margin-top: 10px;
  padding-top: 10px;
  border-top: 1px solid var(--color-border);
  width: 100%;
}
.layout-card .person-custom-fields[data-v-e9a9c070] {
  text-align: center;
}
.layout-list .person-custom-fields[data-v-e9a9c070] {
  grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
}
.person-custom-field[data-v-e9a9c070] {
  display: flex;
  flex-direction: column;
  gap: 1px;
  font-size: 11px;
  color: var(--color-text-maxcontrast);
  min-width: 0;
  overflow: hidden;
}
.custom-field-label[data-v-e9a9c070] {
  font-weight: 600;
  color: var(--color-text-light);
  font-size: 10px;
  text-transform: uppercase;
  letter-spacing: 0.3px;
}
.custom-field-value[data-v-e9a9c070] {
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
  font-size: 12px;
}

/* Hover effects */
.person-item.layout-card[data-v-e9a9c070]:hover,
.person-item.layout-grid[data-v-e9a9c070]:hover {
  background: var(--color-background-dark);
}
.person-item.layout-list[data-v-e9a9c070]:hover {
  background: var(--color-background-hover);
}
.person-item.background-dark[data-v-e9a9c070]:hover {
  background: rgba(255, 255, 255, 0.15);
}

/* ========================================
   NARROW CONTAINER OPTIMIZATIONS
   For side columns and very narrow widgets
   ======================================== */

/* Card layout in narrow containers */
@container (max-width: 280px) {
.person-item.layout-card[data-v-e9a9c070] {
    padding: 12px 10px;
    gap: 8px;
}
.person-item.layout-card .person-name[data-v-e9a9c070] {
    font-size: 14px;
}
.person-item.layout-card .person-role[data-v-e9a9c070],
  .person-item.layout-card .person-headline[data-v-e9a9c070],
  .person-item.layout-card .person-title[data-v-e9a9c070],
  .person-item.layout-card .person-department[data-v-e9a9c070] {
    font-size: 12px;
}
.person-item.layout-card .person-headline[data-v-e9a9c070] {
    display: none; /* Hide headline in narrow containers */
}
.person-item.layout-card .person-contact[data-v-e9a9c070] {
    margin-top: 6px;
    gap: 3px;
}
.person-item.layout-card .person-contact-item[data-v-e9a9c070] {
    font-size: 11px;
    gap: 4px;
}
.person-item.layout-card .person-contact-item span[data-v-e9a9c070] {
    max-width: calc(100% - 20px);
}

  /* Custom fields: single column in narrow containers */
.person-item.layout-card .person-custom-fields[data-v-e9a9c070] {
    grid-template-columns: 1fr;
    gap: 4px;
    margin-top: 8px;
    padding-top: 8px;
}
.person-item.layout-card .custom-field-label[data-v-e9a9c070] {
    font-size: 9px;
}
.person-item.layout-card .custom-field-value[data-v-e9a9c070] {
    font-size: 11px;
}

  /* Hide less important info in very narrow containers */
.person-item.layout-card .person-social[data-v-e9a9c070] {
    gap: 6px;
}
.person-item.layout-card .person-social-item[data-v-e9a9c070] {
    width: 28px;
    height: 28px;
}
.person-item.layout-card .person-biography[data-v-e9a9c070] {
    font-size: 11px;
    margin-top: 8px;
    -webkit-line-clamp: 3;
    display: -webkit-box;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
}

/* Even narrower containers (< 200px) */
@container (max-width: 200px) {
.person-item.layout-card[data-v-e9a9c070] {
    padding: 10px 8px;
}
.person-item.layout-card .person-name[data-v-e9a9c070] {
    font-size: 13px;
    -webkit-line-clamp: 1;
}
.person-item.layout-card .person-title[data-v-e9a9c070] {
    -webkit-line-clamp: 1;
}

  /* Hide department in very narrow to reduce clutter */
.person-item.layout-card .person-department[data-v-e9a9c070] {
    display: none;
}

  /* Only show email, hide other contact methods */
.person-item.layout-card .person-address[data-v-e9a9c070],
  .person-item.layout-card .person-contact-item[data-v-e9a9c070]:not(:first-child) {
    display: none;
}

  /* Hide custom fields and biography in very narrow */
.person-item.layout-card .person-custom-fields[data-v-e9a9c070],
  .person-item.layout-card .person-biography[data-v-e9a9c070] {
    display: none;
}
}

/* ========================================
   LIST LAYOUT IN NARROW CONTAINERS
   Transform to card-like stacked layout
   ======================================== */
@container (max-width: 300px) {
  /* Transform list to vertical card-like layout */
.person-item.layout-list[data-v-e9a9c070] {
    flex-direction: column;
    align-items: center;
    text-align: center;
    padding: 16px 12px;
    border-bottom: none;
    margin-bottom: 12px;
    background: rgba(255, 255, 255, 0.08);
    border-radius: var(--border-radius-large);
}
.person-item.layout-list[data-v-e9a9c070]:last-child {
    margin-bottom: 0;
}

  /* Center avatar */
.layout-list .person-avatar[data-v-e9a9c070] {
    margin-bottom: 8px;
}

  /* Center content */
.layout-list .person-content[data-v-e9a9c070] {
    width: 100%;
    text-align: center;
}

  /* Contact items centered */
.layout-list .person-contact[data-v-e9a9c070] {
    align-items: center;
}

  /* Custom fields single column */
.layout-list .person-custom-fields[data-v-e9a9c070] {
    grid-template-columns: 1fr;
    text-align: center;
}
}

`, "",{"version":3,"sources":["webpack://./src/components/people/PersonItem.vue"],"names":[],"mappings":";AA8UA;EACE,aAAa;EACb,SAAS;EACT,aAAa;EACb,yCAAyC;EACzC,sCAAsC;EACtC,2BAA2B;AAC7B;;AAEA,oBAAoB;AACpB;EACE,sBAAsB;EACtB,mBAAmB;EACnB,kBAAkB;EAClB,yCAAyC;EACzC,YAAY,EAAE,4CAA4C;EAC1D,gBAAgB;AAClB;AAEA;EACE,mBAAmB;EACnB,mBAAmB;EACnB,uBAAuB;EACvB,iBAAiB;EACjB,4CAA4C;AAC9C;AAEA;EACE,mBAAmB;AACrB;AAEA;EACE,sBAAsB;EACtB,mBAAmB;EACnB,kBAAkB;EAClB,aAAa;EACb,yCAAyC;AAC3C;;AAEA,qBAAqB;AACrB;EACE,uBAAuB;AACzB;AAEA;EACE,oCAAoC;AACtC;AAEA;;;;;;;;;;;EAWE,wCAAwC;AAC1C;AAEA;EACE,YAAY;AACd;AAEA;EACE,oCAAoC;AACtC;AAEA;EACE,yCAAyC;AAC3C;;AAEA,WAAW;AACX;EACE,cAAc;AAChB;AAEA;EACE,aAAa;EACb,uBAAuB;AACzB;;AAEA,YAAY;AACZ;EACE,OAAO;EACP,YAAY;EACZ,eAAe;EACf,gBAAgB;AAClB;AAEA;EACE,WAAW;AACb;AAEA;EACE,iBAAiB;EACjB,eAAe;EACf,gBAAgB;EAChB,6BAA6B;EAC7B,gBAAgB;EAChB,sBAAsB;EACtB,yBAAyB;AAC3B;AAEA;EACE,gDAAgD;EAChD,oBAAoB;EACpB,qBAAqB;EACrB,4BAA4B;EAC5B,gBAAgB;AAClB;AAEA;EACE,gBAAgB;EAChB,eAAe;EACf,oCAAoC;AACtC;AAEA;EACE,eAAe;EACf,eAAe;EACf,0DAA0D;EAC1D,oBAAoB;EACpB,qBAAqB;EACrB,4BAA4B;EAC5B,gBAAgB;EAChB,sBAAsB;AACxB;;AAEA,8BAA8B;AAC9B;EACE,iBAAiB;EACjB,eAAe;EACf,gBAAgB;EAChB,6BAA6B;EAC7B,sBAAsB;EACtB,yBAAyB;AAC3B;AAEA;EACE,oBAAoB;EACpB,qBAAqB;EACrB,4BAA4B;EAC5B,gBAAgB;AAClB;AAEA;EACE,oBAAoB;EACpB,qBAAqB;EACrB,4BAA4B;EAC5B,gBAAgB;EAChB,sBAAsB;AACxB;;AAEA,gCAAgC;AAChC;EACE,iBAAiB;EACjB,eAAe;EACf,kBAAkB;EAClB,oCAAoC;EACpC,sBAAsB;EACtB,yBAAyB;AAC3B;AAEA;EACE,oBAAoB;EACpB,qBAAqB;EACrB,4BAA4B;EAC5B,gBAAgB;AAClB;;;AAGA,qDAAqD;AACrD;EACE,iBAAiB;EACjB,eAAe;EACf,oCAAoC;EACpC,sBAAsB;EACtB,yBAAyB;AAC3B;AAEA;EACE,uCAAuC;EACvC,oBAAoB;EACpB,qBAAqB;EACrB,4BAA4B;EAC5B,gBAAgB;AAClB;AAEA;EACE,2DAA2D;EAC3D,oBAAoB;EACpB,qBAAqB;EACrB,4BAA4B;EAC5B,gBAAgB;EAChB,sBAAsB;AACxB;AAEA;EACE,iBAAiB;EACjB,eAAe;EACf,oCAAoC;EACpC,sBAAsB;EACtB,yBAAyB;AAC3B;;AAEA,iBAAiB;AACjB;EACE,aAAa;EACb,sBAAsB;EACtB,QAAQ;EACR,eAAe;AACjB;AAEA;EACE,mBAAmB;AACrB;AAEA;EACE,oBAAoB;EACpB,mBAAmB;EACnB,QAAQ;EACR,mCAAmC;EACnC,qBAAqB;EACrB,eAAe;AACjB;AAEA;;EAEE,oCAAoC;AACtC;AAEA;EACE,0BAA0B;AAC5B;AAEA;EACE,gBAAgB;EAChB,uBAAuB;EACvB,mBAAmB;EACnB,eAAe;AACjB;;AAEA,8CAA8C;AAC9C;EACE,eAAe;AACjB;AAEA;EACE,4BAA4B,EAAE,2BAA2B;AAC3D;;AAEA,iBAAiB;AACjB;EACE,aAAa;EACb,QAAQ;EACR,eAAe;AACjB;AAEA;EACE,uBAAuB;AACzB;AAEA;EACE,oBAAoB;EACpB,mBAAmB;EACnB,uBAAuB;EACvB,WAAW;EACX,YAAY;EACZ,kBAAkB;EAClB,wCAAwC;EACxC,6BAA6B;EAC7B,qBAAqB;EACrB,sCAAsC;AACxC;AAEA;EACE,wCAAwC;EACxC,wCAAwC;AAC1C;;AAEA,cAAc;AACd;EACE,kBAAkB;EAClB,eAAe;EACf,oCAAoC;EACpC,gBAAgB;AAClB;;AAEA,8BAA8B;AAC9B;EACE,aAAa;EACb,qCAAqC;EACrC,aAAa;EACb,gBAAgB;EAChB,iBAAiB;EACjB,yCAAyC;EACzC,WAAW;AACb;AAEA;EACE,kBAAkB;AACpB;AAEA;EACE,4DAA4D;AAC9D;AAEA;EACE,aAAa;EACb,sBAAsB;EACtB,QAAQ;EACR,eAAe;EACf,oCAAoC;EACpC,YAAY;EACZ,gBAAgB;AAClB;AAEA;EACE,gBAAgB;EAChB,8BAA8B;EAC9B,eAAe;EACf,yBAAyB;EACzB,qBAAqB;AACvB;AAEA;EACE,gBAAgB;EAChB,uBAAuB;EACvB,mBAAmB;EACnB,eAAe;AACjB;;AAEA,kBAAkB;AAClB;;EAEE,wCAAwC;AAC1C;AAEA;EACE,yCAAyC;AAC3C;AAEA;EACE,qCAAqC;AACvC;;AAEA;;;6CAG6C;;AAE7C,qCAAqC;AACrC;AACE;IACE,kBAAkB;IAClB,QAAQ;AACV;AAEA;IACE,eAAe;AACjB;AAEA;;;;IAIE,eAAe;AACjB;AAEA;IACE,aAAa,EAAE,uCAAuC;AACxD;AAEA;IACE,eAAe;IACf,QAAQ;AACV;AAEA;IACE,eAAe;IACf,QAAQ;AACV;AAEA;IACE,4BAA4B;AAC9B;;EAEA,sDAAsD;AACtD;IACE,0BAA0B;IAC1B,QAAQ;IACR,eAAe;IACf,gBAAgB;AAClB;AAEA;IACE,cAAc;AAChB;AAEA;IACE,eAAe;AACjB;;EAEA,uDAAuD;AACvD;IACE,QAAQ;AACV;AAEA;IACE,WAAW;IACX,YAAY;AACd;AAEA;IACE,eAAe;IACf,eAAe;IACf,qBAAqB;IACrB,oBAAoB;IACpB,4BAA4B;IAC5B,gBAAgB;AAClB;AACF;;AAEA,uCAAuC;AACvC;AACE;IACE,iBAAiB;AACnB;AAEA;IACE,eAAe;IACf,qBAAqB;AACvB;AAEA;IACE,qBAAqB;AACvB;;EAEA,qDAAqD;AACrD;IACE,aAAa;AACf;;EAEA,gDAAgD;AAChD;;IAEE,aAAa;AACf;;EAEA,oDAAoD;AACpD;;IAEE,aAAa;AACf;AACF;;AAEA;;;6CAG6C;AAE7C;EACE,gDAAgD;AAChD;IACE,sBAAsB;IACtB,mBAAmB;IACnB,kBAAkB;IAClB,kBAAkB;IAClB,mBAAmB;IACnB,mBAAmB;IACnB,qCAAqC;IACrC,yCAAyC;AAC3C;AAEA;IACE,gBAAgB;AAClB;;EAEA,kBAAkB;AAClB;IACE,kBAAkB;AACpB;;EAEA,mBAAmB;AACnB;IACE,WAAW;IACX,kBAAkB;AACpB;;EAEA,2BAA2B;AAC3B;IACE,mBAAmB;AACrB;;EAEA,gCAAgC;AAChC;IACE,0BAA0B;IAC1B,kBAAkB;AACpB;AACF","sourcesContent":["<template>\r\n  <div class=\"person-item\" :class=\"[`layout-${layout}`, itemBackgroundClass]\">\r\n    <!-- Avatar with status indicator -->\r\n    <div v-if=\"showField('avatar')\" class=\"person-avatar\">\r\n      <NcAvatar\r\n        :user=\"user.uid\"\r\n        :display-name=\"user.displayName\"\r\n        :size=\"avatarSize\"\r\n        :show-user-status=\"!!user.status\"\r\n        :show-user-status-compact=\"layout === 'list'\"\r\n      />\r\n    </div>\r\n\r\n    <!-- Content -->\r\n    <div class=\"person-content\">\r\n      <!-- Name and Pronouns -->\r\n      <h4 v-if=\"showField('displayName')\" class=\"person-name\">\r\n        {{ user.displayName }}\r\n        <span v-if=\"showField('pronouns') && user.pronouns\" class=\"person-pronouns\">({{ user.pronouns }})</span>\r\n      </h4>\r\n\r\n      <!-- Role (official job title) -->\r\n      <p v-if=\"showRole && user.role\" class=\"person-role\">\r\n        {{ user.role }}\r\n      </p>\r\n\r\n      <!-- Headline (personal tagline) -->\r\n      <p v-if=\"showHeadline && user.headline && user.headline !== user.role\" class=\"person-headline\">\r\n        {{ user.headline }}\r\n      </p>\r\n\r\n      <!-- Department/Organisation -->\r\n      <p v-if=\"showField('department') && (user.organisation || user.department)\" class=\"person-department\">\r\n        {{ user.organisation || user.department }}\r\n      </p>\r\n\r\n      <!-- Contact info -->\r\n      <div v-if=\"hasContactInfo\" class=\"person-contact\">\r\n        <a\r\n          v-if=\"showField('email') && user.email\"\r\n          :href=\"`mailto:${user.email}`\"\r\n          class=\"person-contact-item\"\r\n        >\r\n          <EmailOutline :size=\"16\" />\r\n          <span>{{ user.email }}</span>\r\n        </a>\r\n\r\n        <a\r\n          v-if=\"showField('phone') && user.phone\"\r\n          :href=\"`tel:${user.phone}`\"\r\n          class=\"person-contact-item\"\r\n        >\r\n          <Phone :size=\"16\" />\r\n          <span>{{ user.phone }}</span>\r\n        </a>\r\n\r\n        <span\r\n          v-if=\"showField('address') && user.address\"\r\n          class=\"person-contact-item person-address\"\r\n        >\r\n          <MapMarker :size=\"16\" />\r\n          <span>{{ user.address }}</span>\r\n        </span>\r\n\r\n        <a\r\n          v-if=\"showField('website') && user.website\"\r\n          :href=\"user.website\"\r\n          target=\"_blank\"\r\n          rel=\"noopener noreferrer\"\r\n          class=\"person-contact-item\"\r\n        >\r\n          <Web :size=\"16\" />\r\n          <span>{{ formatWebsite(user.website) }}</span>\r\n        </a>\r\n\r\n        <span\r\n          v-if=\"showField('birthdate') && user.birthdate\"\r\n          class=\"person-contact-item person-birthdate\"\r\n        >\r\n          <CakeVariant :size=\"16\" />\r\n          <span>{{ formatBirthdate(user.birthdate) }}</span>\r\n        </span>\r\n      </div>\r\n\r\n      <!-- Social links -->\r\n      <div v-if=\"hasSocialLinks\" class=\"person-social\">\r\n        <a\r\n          v-if=\"user.twitter\"\r\n          :href=\"getTwitterUrl(user.twitter)\"\r\n          target=\"_blank\"\r\n          rel=\"noopener noreferrer\"\r\n          class=\"person-social-item\"\r\n          :title=\"user.twitter\"\r\n        >\r\n          <Twitter :size=\"18\" />\r\n        </a>\r\n\r\n        <a\r\n          v-if=\"user.bluesky\"\r\n          :href=\"getBlueskyUrl(user.bluesky)\"\r\n          target=\"_blank\"\r\n          rel=\"noopener noreferrer\"\r\n          class=\"person-social-item\"\r\n          :title=\"user.bluesky\"\r\n        >\r\n          <CloudOutline :size=\"18\" />\r\n        </a>\r\n\r\n        <a\r\n          v-if=\"user.fediverse\"\r\n          :href=\"getFediverseUrl(user.fediverse)\"\r\n          target=\"_blank\"\r\n          rel=\"noopener noreferrer\"\r\n          class=\"person-social-item\"\r\n          :title=\"user.fediverse\"\r\n        >\r\n          <Mastodon :size=\"18\" />\r\n        </a>\r\n      </div>\r\n\r\n      <!-- Biography (only in card layout) -->\r\n      <p\r\n        v-if=\"layout === 'card' && showField('biography') && user.biography\"\r\n        class=\"person-biography\"\r\n      >\r\n        {{ truncateBio(user.biography) }}\r\n      </p>\r\n\r\n      <!-- Custom fields from LDAP/OIDC -->\r\n      <div\r\n        v-if=\"showField('customFields') && customFields.length > 0\"\r\n        class=\"person-custom-fields\"\r\n      >\r\n        <div\r\n          v-for=\"field in customFields\"\r\n          :key=\"field.key\"\r\n          class=\"person-custom-field\"\r\n        >\r\n          <span class=\"custom-field-label\">{{ field.label }}:</span>\r\n          <span class=\"custom-field-value\">{{ field.value }}</span>\r\n        </div>\r\n      </div>\r\n\r\n    </div>\r\n  </div>\r\n</template>\r\n\r\n<script>\r\nimport { NcAvatar } from '@nextcloud/vue';\r\nimport EmailOutline from 'vue-material-design-icons/EmailOutline.vue';\r\nimport Phone from 'vue-material-design-icons/Phone.vue';\r\nimport MapMarker from 'vue-material-design-icons/MapMarker.vue';\r\nimport Web from 'vue-material-design-icons/Web.vue';\r\nimport Twitter from 'vue-material-design-icons/Twitter.vue';\r\nimport CloudOutline from 'vue-material-design-icons/CloudOutline.vue';\r\nimport Mastodon from 'vue-material-design-icons/Mastodon.vue';\r\nimport CakeVariant from 'vue-material-design-icons/CakeVariant.vue';\r\n\r\nexport default {\r\n  name: 'PersonItem',\r\n  components: {\r\n    NcAvatar,\r\n    EmailOutline,\r\n    Phone,\r\n    MapMarker,\r\n    Web,\r\n    Twitter,\r\n    CloudOutline,\r\n    Mastodon,\r\n    CakeVariant,\r\n  },\r\n  props: {\r\n    user: {\r\n      type: Object,\r\n      required: true,\r\n    },\r\n    layout: {\r\n      type: String,\r\n      default: 'card',\r\n      validator: (value) => ['card', 'list', 'grid'].includes(value),\r\n    },\r\n    showFields: {\r\n      type: Object,\r\n      default: () => ({\r\n        // Basic information\r\n        avatar: true,\r\n        displayName: true,\r\n        pronouns: false,\r\n        role: true,        // Official job title\r\n        headline: false,   // Personal tagline\r\n        title: true,       // Legacy: for backwards compatibility\r\n        department: true,\r\n        // Contact\r\n        email: true,\r\n        phone: false,\r\n        address: false,\r\n        website: false,\r\n        birthdate: false,\r\n        // Extended\r\n        biography: false,\r\n        socialLinks: false,\r\n        customFields: false,\r\n      }),\r\n    },\r\n    itemBackground: {\r\n      type: String,\r\n      default: 'default',\r\n      validator: (value) => ['transparent', 'dark', 'white', 'default'].includes(value),\r\n    },\r\n  },\r\n  computed: {\r\n    avatarSize() {\r\n      switch (this.layout) {\r\n        case 'card':\r\n          return 80;\r\n        case 'list':\r\n          return 44;\r\n        case 'grid':\r\n          return 64;\r\n        default:\r\n          return 64;\r\n      }\r\n    },\r\n    itemBackgroundClass() {\r\n      return `background-${this.itemBackground}`;\r\n    },\r\n    hasContactInfo() {\r\n      return (this.showField('email') && this.user.email)\r\n        || (this.showField('phone') && this.user.phone)\r\n        || (this.showField('address') && this.user.address)\r\n        || (this.showField('website') && this.user.website)\r\n        || (this.showField('birthdate') && this.user.birthdate);\r\n    },\r\n    hasSocialLinks() {\r\n      // Support both new socialLinks and legacy twitter/fediverse fields\r\n      const showSocial = this.showField('socialLinks')\r\n        || this.showField('twitter')\r\n        || this.showField('fediverse');\r\n      return showSocial && (this.user.twitter || this.user.bluesky || this.user.fediverse);\r\n    },\r\n    showRole() {\r\n      return this.showField('role');\r\n    },\r\n    showHeadline() {\r\n      return this.showField('headline');\r\n    },\r\n    customFields() {\r\n      // Known/standard fields that are handled separately\r\n      // Include camelCase, lowercase, and underscore variants as backend uses propertyToKey()\r\n      // which converts PROPERTY_NAME to lowercase with underscores (e.g., profile_enabled)\r\n      const knownFields = [\r\n        'uid', 'displayName', 'displayname', 'pronouns', 'email', 'phone', 'address', 'website',\r\n        'twitter', 'bluesky', 'fediverse', 'organisation', 'department', 'role', 'birthdate',\r\n        'headline', 'biography', 'avatarUrl', 'groups', 'status',\r\n        // Nextcloud internal fields - all possible naming variants\r\n        'profileEnabled', 'profileenabled', 'profile_enabled',\r\n      ];\r\n\r\n      const fields = [];\r\n      for (const [key, value] of Object.entries(this.user)) {\r\n        // Skip known fields, null/empty values, and internal fields\r\n        if (knownFields.includes(key)) continue;\r\n        if (value === null || value === undefined || value === '') continue;\r\n        if (typeof value === 'object') continue; // Skip complex objects\r\n\r\n        // Create a readable label from the key\r\n        const label = key\r\n          .replace(/([A-Z])/g, ' $1') // camelCase to spaces\r\n          .replace(/[_-]/g, ' ')       // underscores/dashes to spaces\r\n          .replace(/^\\w/, c => c.toUpperCase()) // capitalize first letter\r\n          .trim();\r\n\r\n        fields.push({ key, label, value: String(value) });\r\n      }\r\n\r\n      return fields;\r\n    },\r\n  },\r\n  methods: {\r\n    showField(fieldName) {\r\n      return this.showFields[fieldName] !== false;\r\n    },\r\n    truncateBio(text, maxLength = 150) {\r\n      if (!text || text.length <= maxLength) return text;\r\n      return text.substring(0, maxLength).trim() + '...';\r\n    },\r\n    formatWebsite(url) {\r\n      // Remove protocol and trailing slash for cleaner display\r\n      return url.replace(/^https?:\\/\\//, '').replace(/\\/$/, '');\r\n    },\r\n    getTwitterUrl(handle) {\r\n      // Handle can be @username or just username\r\n      const username = handle.replace(/^@/, '');\r\n      return `https://x.com/${username}`;\r\n    },\r\n    getBlueskyUrl(handle) {\r\n      // Handle can be @user.bsky.social, user.bsky.social, or a full URL\r\n      if (handle.startsWith('http')) {\r\n        return handle;\r\n      }\r\n      const username = handle.replace(/^@/, '');\r\n      return `https://bsky.app/profile/${username}`;\r\n    },\r\n    formatBirthdate(dateStr) {\r\n      if (!dateStr) return '';\r\n      try {\r\n        const date = new Date(dateStr + 'T00:00:00');\r\n        if (isNaN(date.getTime())) {\r\n          return dateStr;\r\n        }\r\n        return date.toLocaleDateString(undefined, { month: 'long', day: 'numeric' });\r\n      } catch {\r\n        return dateStr;\r\n      }\r\n    },\r\n    getFediverseUrl(handle) {\r\n      // Handle format: @user@instance.social\r\n      if (handle.startsWith('@')) {\r\n        const parts = handle.substring(1).split('@');\r\n        if (parts.length === 2) {\r\n          return `https://${parts[1]}/@${parts[0]}`;\r\n        }\r\n      }\r\n      // If it's already a URL or unknown format, return as-is\r\n      if (handle.startsWith('http')) {\r\n        return handle;\r\n      }\r\n      return '#';\r\n    },\r\n  },\r\n};\r\n</script>\r\n\r\n<style scoped>\r\n.person-item {\r\n  display: flex;\r\n  gap: 12px;\r\n  padding: 16px;\r\n  border-radius: var(--border-radius-large);\r\n  transition: background-color 0.2s ease;\r\n  container-type: inline-size;\r\n}\r\n\r\n/* Layout variants */\r\n.person-item.layout-card {\r\n  flex-direction: column;\r\n  align-items: center;\r\n  text-align: center;\r\n  background: var(--color-background-hover);\r\n  min-width: 0; /* Allow shrinking in flex/grid containers */\r\n  overflow: hidden;\r\n}\r\n\r\n.person-item.layout-list {\r\n  flex-direction: row;\r\n  align-items: center;\r\n  background: transparent;\r\n  padding: 8px 12px;\r\n  border-bottom: 1px solid var(--color-border);\r\n}\r\n\r\n.person-item.layout-list:last-child {\r\n  border-bottom: none;\r\n}\r\n\r\n.person-item.layout-grid {\r\n  flex-direction: column;\r\n  align-items: center;\r\n  text-align: center;\r\n  padding: 12px;\r\n  background: var(--color-background-hover);\r\n}\r\n\r\n/* Background modes */\r\n.person-item.background-transparent {\r\n  background: transparent;\r\n}\r\n\r\n.person-item.background-dark {\r\n  background: rgba(255, 255, 255, 0.1);\r\n}\r\n\r\n.person-item.background-dark .person-name,\r\n.person-item.background-dark .person-role,\r\n.person-item.background-dark .person-headline,\r\n.person-item.background-dark .person-title,\r\n.person-item.background-dark .person-department,\r\n.person-item.background-dark .person-contact-item,\r\n.person-item.background-dark .person-social-item,\r\n.person-item.background-dark .person-biography,\r\n.person-item.background-dark .person-custom-field,\r\n.person-item.background-dark .custom-field-label,\r\n.person-item.background-dark .custom-field-value {\r\n  color: var(--color-primary-element-text);\r\n}\r\n\r\n.person-item.background-dark .custom-field-label {\r\n  opacity: 0.8;\r\n}\r\n\r\n.person-item.background-white {\r\n  background: rgba(255, 255, 255, 0.9);\r\n}\r\n\r\n.person-item.background-default {\r\n  background: var(--color-background-hover);\r\n}\r\n\r\n/* Avatar */\r\n.person-avatar {\r\n  flex-shrink: 0;\r\n}\r\n\r\n.layout-card .person-avatar {\r\n  display: flex;\r\n  justify-content: center;\r\n}\r\n\r\n/* Content */\r\n.person-content {\r\n  flex: 1;\r\n  min-width: 0;\r\n  max-width: 100%;\r\n  overflow: hidden;\r\n}\r\n\r\n.layout-card .person-content {\r\n  width: 100%;\r\n}\r\n\r\n.person-name {\r\n  margin: 0 0 4px 0;\r\n  font-size: 16px;\r\n  font-weight: 600;\r\n  color: var(--color-main-text);\r\n  line-height: 1.3;\r\n  word-break: break-word;\r\n  overflow-wrap: break-word;\r\n}\r\n\r\n.layout-card .person-name {\r\n  /* Allow name to wrap naturally in card layout */\r\n  display: -webkit-box;\r\n  -webkit-line-clamp: 2;\r\n  -webkit-box-orient: vertical;\r\n  overflow: hidden;\r\n}\r\n\r\n.person-pronouns {\r\n  font-weight: 400;\r\n  font-size: 14px;\r\n  color: var(--color-text-maxcontrast);\r\n}\r\n\r\n.layout-grid .person-name {\r\n  font-size: 14px;\r\n  margin-top: 8px;\r\n  /* Limit name to 2 lines for consistent grid item height */\r\n  display: -webkit-box;\r\n  -webkit-line-clamp: 2;\r\n  -webkit-box-orient: vertical;\r\n  overflow: hidden;\r\n  word-break: break-word;\r\n}\r\n\r\n/* Role - official job title */\r\n.person-role {\r\n  margin: 0 0 2px 0;\r\n  font-size: 14px;\r\n  font-weight: 500;\r\n  color: var(--color-main-text);\r\n  word-break: break-word;\r\n  overflow-wrap: break-word;\r\n}\r\n\r\n.layout-card .person-role {\r\n  display: -webkit-box;\r\n  -webkit-line-clamp: 2;\r\n  -webkit-box-orient: vertical;\r\n  overflow: hidden;\r\n}\r\n\r\n.layout-grid .person-role {\r\n  display: -webkit-box;\r\n  -webkit-line-clamp: 1;\r\n  -webkit-box-orient: vertical;\r\n  overflow: hidden;\r\n  word-break: break-word;\r\n}\r\n\r\n/* Headline - personal tagline */\r\n.person-headline {\r\n  margin: 0 0 2px 0;\r\n  font-size: 13px;\r\n  font-style: italic;\r\n  color: var(--color-text-maxcontrast);\r\n  word-break: break-word;\r\n  overflow-wrap: break-word;\r\n}\r\n\r\n.layout-card .person-headline {\r\n  display: -webkit-box;\r\n  -webkit-line-clamp: 1;\r\n  -webkit-box-orient: vertical;\r\n  overflow: hidden;\r\n}\r\n\r\n\r\n/* Legacy .person-title for backwards compatibility */\r\n.person-title {\r\n  margin: 0 0 2px 0;\r\n  font-size: 14px;\r\n  color: var(--color-text-maxcontrast);\r\n  word-break: break-word;\r\n  overflow-wrap: break-word;\r\n}\r\n\r\n.layout-card .person-title {\r\n  /* Allow title to wrap in card layout */\r\n  display: -webkit-box;\r\n  -webkit-line-clamp: 2;\r\n  -webkit-box-orient: vertical;\r\n  overflow: hidden;\r\n}\r\n\r\n.layout-grid .person-title {\r\n  /* Limit title to 2 lines for consistent grid item height */\r\n  display: -webkit-box;\r\n  -webkit-line-clamp: 2;\r\n  -webkit-box-orient: vertical;\r\n  overflow: hidden;\r\n  word-break: break-word;\r\n}\r\n\r\n.person-department {\r\n  margin: 0 0 8px 0;\r\n  font-size: 13px;\r\n  color: var(--color-text-maxcontrast);\r\n  word-break: break-word;\r\n  overflow-wrap: break-word;\r\n}\r\n\r\n/* Contact info */\r\n.person-contact {\r\n  display: flex;\r\n  flex-direction: column;\r\n  gap: 4px;\r\n  margin-top: 8px;\r\n}\r\n\r\n.layout-card .person-contact {\r\n  align-items: center;\r\n}\r\n\r\n.person-contact-item {\r\n  display: inline-flex;\r\n  align-items: center;\r\n  gap: 6px;\r\n  color: var(--color-primary-element);\r\n  text-decoration: none;\r\n  font-size: 13px;\r\n}\r\n\r\n.person-contact-item.person-address,\r\n.person-contact-item.person-birthdate {\r\n  color: var(--color-text-maxcontrast);\r\n}\r\n\r\na.person-contact-item:hover {\r\n  text-decoration: underline;\r\n}\r\n\r\n.person-contact-item span {\r\n  overflow: hidden;\r\n  text-overflow: ellipsis;\r\n  white-space: nowrap;\r\n  max-width: 100%;\r\n}\r\n\r\n/* Compact card layout for narrow containers */\r\n.layout-card .person-contact-item {\r\n  max-width: 100%;\r\n}\r\n\r\n.layout-card .person-contact-item span {\r\n  max-width: calc(100% - 22px); /* Account for icon width */\r\n}\r\n\r\n/* Social links */\r\n.person-social {\r\n  display: flex;\r\n  gap: 8px;\r\n  margin-top: 8px;\r\n}\r\n\r\n.layout-card .person-social {\r\n  justify-content: center;\r\n}\r\n\r\n.person-social-item {\r\n  display: inline-flex;\r\n  align-items: center;\r\n  justify-content: center;\r\n  width: 32px;\r\n  height: 32px;\r\n  border-radius: 50%;\r\n  background: var(--color-background-dark);\r\n  color: var(--color-main-text);\r\n  text-decoration: none;\r\n  transition: background-color 0.2s ease;\r\n}\r\n\r\n.person-social-item:hover {\r\n  background: var(--color-primary-element);\r\n  color: var(--color-primary-element-text);\r\n}\r\n\r\n/* Biography */\r\n.person-biography {\r\n  margin: 12px 0 0 0;\r\n  font-size: 13px;\r\n  color: var(--color-text-maxcontrast);\r\n  line-height: 1.5;\r\n}\r\n\r\n/* Custom fields (LDAP/OIDC) */\r\n.person-custom-fields {\r\n  display: grid;\r\n  grid-template-columns: repeat(2, 1fr);\r\n  gap: 6px 12px;\r\n  margin-top: 10px;\r\n  padding-top: 10px;\r\n  border-top: 1px solid var(--color-border);\r\n  width: 100%;\r\n}\r\n\r\n.layout-card .person-custom-fields {\r\n  text-align: center;\r\n}\r\n\r\n.layout-list .person-custom-fields {\r\n  grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));\r\n}\r\n\r\n.person-custom-field {\r\n  display: flex;\r\n  flex-direction: column;\r\n  gap: 1px;\r\n  font-size: 11px;\r\n  color: var(--color-text-maxcontrast);\r\n  min-width: 0;\r\n  overflow: hidden;\r\n}\r\n\r\n.custom-field-label {\r\n  font-weight: 600;\r\n  color: var(--color-text-light);\r\n  font-size: 10px;\r\n  text-transform: uppercase;\r\n  letter-spacing: 0.3px;\r\n}\r\n\r\n.custom-field-value {\r\n  overflow: hidden;\r\n  text-overflow: ellipsis;\r\n  white-space: nowrap;\r\n  font-size: 12px;\r\n}\r\n\r\n/* Hover effects */\r\n.person-item.layout-card:hover,\r\n.person-item.layout-grid:hover {\r\n  background: var(--color-background-dark);\r\n}\r\n\r\n.person-item.layout-list:hover {\r\n  background: var(--color-background-hover);\r\n}\r\n\r\n.person-item.background-dark:hover {\r\n  background: rgba(255, 255, 255, 0.15);\r\n}\r\n\r\n/* ========================================\r\n   NARROW CONTAINER OPTIMIZATIONS\r\n   For side columns and very narrow widgets\r\n   ======================================== */\r\n\r\n/* Card layout in narrow containers */\r\n@container (max-width: 280px) {\r\n  .person-item.layout-card {\r\n    padding: 12px 10px;\r\n    gap: 8px;\r\n  }\r\n\r\n  .person-item.layout-card .person-name {\r\n    font-size: 14px;\r\n  }\r\n\r\n  .person-item.layout-card .person-role,\r\n  .person-item.layout-card .person-headline,\r\n  .person-item.layout-card .person-title,\r\n  .person-item.layout-card .person-department {\r\n    font-size: 12px;\r\n  }\r\n\r\n  .person-item.layout-card .person-headline {\r\n    display: none; /* Hide headline in narrow containers */\r\n  }\r\n\r\n  .person-item.layout-card .person-contact {\r\n    margin-top: 6px;\r\n    gap: 3px;\r\n  }\r\n\r\n  .person-item.layout-card .person-contact-item {\r\n    font-size: 11px;\r\n    gap: 4px;\r\n  }\r\n\r\n  .person-item.layout-card .person-contact-item span {\r\n    max-width: calc(100% - 20px);\r\n  }\r\n\r\n  /* Custom fields: single column in narrow containers */\r\n  .person-item.layout-card .person-custom-fields {\r\n    grid-template-columns: 1fr;\r\n    gap: 4px;\r\n    margin-top: 8px;\r\n    padding-top: 8px;\r\n  }\r\n\r\n  .person-item.layout-card .custom-field-label {\r\n    font-size: 9px;\r\n  }\r\n\r\n  .person-item.layout-card .custom-field-value {\r\n    font-size: 11px;\r\n  }\r\n\r\n  /* Hide less important info in very narrow containers */\r\n  .person-item.layout-card .person-social {\r\n    gap: 6px;\r\n  }\r\n\r\n  .person-item.layout-card .person-social-item {\r\n    width: 28px;\r\n    height: 28px;\r\n  }\r\n\r\n  .person-item.layout-card .person-biography {\r\n    font-size: 11px;\r\n    margin-top: 8px;\r\n    -webkit-line-clamp: 3;\r\n    display: -webkit-box;\r\n    -webkit-box-orient: vertical;\r\n    overflow: hidden;\r\n  }\r\n}\r\n\r\n/* Even narrower containers (< 200px) */\r\n@container (max-width: 200px) {\r\n  .person-item.layout-card {\r\n    padding: 10px 8px;\r\n  }\r\n\r\n  .person-item.layout-card .person-name {\r\n    font-size: 13px;\r\n    -webkit-line-clamp: 1;\r\n  }\r\n\r\n  .person-item.layout-card .person-title {\r\n    -webkit-line-clamp: 1;\r\n  }\r\n\r\n  /* Hide department in very narrow to reduce clutter */\r\n  .person-item.layout-card .person-department {\r\n    display: none;\r\n  }\r\n\r\n  /* Only show email, hide other contact methods */\r\n  .person-item.layout-card .person-address,\r\n  .person-item.layout-card .person-contact-item:not(:first-child) {\r\n    display: none;\r\n  }\r\n\r\n  /* Hide custom fields and biography in very narrow */\r\n  .person-item.layout-card .person-custom-fields,\r\n  .person-item.layout-card .person-biography {\r\n    display: none;\r\n  }\r\n}\r\n\r\n/* ========================================\r\n   LIST LAYOUT IN NARROW CONTAINERS\r\n   Transform to card-like stacked layout\r\n   ======================================== */\r\n\r\n@container (max-width: 300px) {\r\n  /* Transform list to vertical card-like layout */\r\n  .person-item.layout-list {\r\n    flex-direction: column;\r\n    align-items: center;\r\n    text-align: center;\r\n    padding: 16px 12px;\r\n    border-bottom: none;\r\n    margin-bottom: 12px;\r\n    background: rgba(255, 255, 255, 0.08);\r\n    border-radius: var(--border-radius-large);\r\n  }\r\n\r\n  .person-item.layout-list:last-child {\r\n    margin-bottom: 0;\r\n  }\r\n\r\n  /* Center avatar */\r\n  .layout-list .person-avatar {\r\n    margin-bottom: 8px;\r\n  }\r\n\r\n  /* Center content */\r\n  .layout-list .person-content {\r\n    width: 100%;\r\n    text-align: center;\r\n  }\r\n\r\n  /* Contact items centered */\r\n  .layout-list .person-contact {\r\n    align-items: center;\r\n  }\r\n\r\n  /* Custom fields single column */\r\n  .layout-list .person-custom-fields {\r\n    grid-template-columns: 1fr;\r\n    text-align: center;\r\n  }\r\n}\r\n\r\n</style>\r\n"],"sourceRoot":""}]);
// Exports
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (___CSS_LOADER_EXPORT___);


/***/ },

/***/ "./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/dist/stylePostLoader.js!./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/PeopleWidget.vue?vue&type=style&index=0&id=253d9463&scoped=true&lang=css"
/*!*****************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/dist/stylePostLoader.js!./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/PeopleWidget.vue?vue&type=style&index=0&id=253d9463&scoped=true&lang=css ***!
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
/* harmony import */ var _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_dist_stylePostLoader_js_node_modules_vue_loader_dist_index_js_ruleSet_1_rules_5_use_0_PeopleWidget_vue_vue_type_style_index_0_id_253d9463_scoped_true_lang_css__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! !!../../node_modules/css-loader/dist/cjs.js!../../node_modules/vue-loader/dist/stylePostLoader.js!../../node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./PeopleWidget.vue?vue&type=style&index=0&id=253d9463&scoped=true&lang=css */ "./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/dist/stylePostLoader.js!./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/PeopleWidget.vue?vue&type=style&index=0&id=253d9463&scoped=true&lang=css");

      
      
      
      
      
      
      
      
      

var options = {};

options.styleTagTransform = (_node_modules_style_loader_dist_runtime_styleTagTransform_js__WEBPACK_IMPORTED_MODULE_5___default());
options.setAttributes = (_node_modules_style_loader_dist_runtime_setAttributesWithoutAttributes_js__WEBPACK_IMPORTED_MODULE_3___default());

      options.insert = _node_modules_style_loader_dist_runtime_insertBySelector_js__WEBPACK_IMPORTED_MODULE_2___default().bind(null, "head");
    
options.domAPI = (_node_modules_style_loader_dist_runtime_styleDomAPI_js__WEBPACK_IMPORTED_MODULE_1___default());
options.insertStyleElement = (_node_modules_style_loader_dist_runtime_insertStyleElement_js__WEBPACK_IMPORTED_MODULE_4___default());

var update = _node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0___default()(_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_dist_stylePostLoader_js_node_modules_vue_loader_dist_index_js_ruleSet_1_rules_5_use_0_PeopleWidget_vue_vue_type_style_index_0_id_253d9463_scoped_true_lang_css__WEBPACK_IMPORTED_MODULE_6__["default"], options);




       /* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_dist_stylePostLoader_js_node_modules_vue_loader_dist_index_js_ruleSet_1_rules_5_use_0_PeopleWidget_vue_vue_type_style_index_0_id_253d9463_scoped_true_lang_css__WEBPACK_IMPORTED_MODULE_6__["default"] && _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_dist_stylePostLoader_js_node_modules_vue_loader_dist_index_js_ruleSet_1_rules_5_use_0_PeopleWidget_vue_vue_type_style_index_0_id_253d9463_scoped_true_lang_css__WEBPACK_IMPORTED_MODULE_6__["default"].locals ? _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_dist_stylePostLoader_js_node_modules_vue_loader_dist_index_js_ruleSet_1_rules_5_use_0_PeopleWidget_vue_vue_type_style_index_0_id_253d9463_scoped_true_lang_css__WEBPACK_IMPORTED_MODULE_6__["default"].locals : undefined);


/***/ },

/***/ "./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/dist/stylePostLoader.js!./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/people/PeopleLayoutCard.vue?vue&type=style&index=0&id=5ec2ee89&scoped=true&lang=css"
/*!****************************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/dist/stylePostLoader.js!./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/people/PeopleLayoutCard.vue?vue&type=style&index=0&id=5ec2ee89&scoped=true&lang=css ***!
  \****************************************************************************************************************************************************************************************************************************************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

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
/* harmony import */ var _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_dist_stylePostLoader_js_node_modules_vue_loader_dist_index_js_ruleSet_1_rules_5_use_0_PeopleLayoutCard_vue_vue_type_style_index_0_id_5ec2ee89_scoped_true_lang_css__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! !!../../../node_modules/css-loader/dist/cjs.js!../../../node_modules/vue-loader/dist/stylePostLoader.js!../../../node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./PeopleLayoutCard.vue?vue&type=style&index=0&id=5ec2ee89&scoped=true&lang=css */ "./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/dist/stylePostLoader.js!./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/people/PeopleLayoutCard.vue?vue&type=style&index=0&id=5ec2ee89&scoped=true&lang=css");

      
      
      
      
      
      
      
      
      

var options = {};

options.styleTagTransform = (_node_modules_style_loader_dist_runtime_styleTagTransform_js__WEBPACK_IMPORTED_MODULE_5___default());
options.setAttributes = (_node_modules_style_loader_dist_runtime_setAttributesWithoutAttributes_js__WEBPACK_IMPORTED_MODULE_3___default());

      options.insert = _node_modules_style_loader_dist_runtime_insertBySelector_js__WEBPACK_IMPORTED_MODULE_2___default().bind(null, "head");
    
options.domAPI = (_node_modules_style_loader_dist_runtime_styleDomAPI_js__WEBPACK_IMPORTED_MODULE_1___default());
options.insertStyleElement = (_node_modules_style_loader_dist_runtime_insertStyleElement_js__WEBPACK_IMPORTED_MODULE_4___default());

var update = _node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0___default()(_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_dist_stylePostLoader_js_node_modules_vue_loader_dist_index_js_ruleSet_1_rules_5_use_0_PeopleLayoutCard_vue_vue_type_style_index_0_id_5ec2ee89_scoped_true_lang_css__WEBPACK_IMPORTED_MODULE_6__["default"], options);




       /* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_dist_stylePostLoader_js_node_modules_vue_loader_dist_index_js_ruleSet_1_rules_5_use_0_PeopleLayoutCard_vue_vue_type_style_index_0_id_5ec2ee89_scoped_true_lang_css__WEBPACK_IMPORTED_MODULE_6__["default"] && _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_dist_stylePostLoader_js_node_modules_vue_loader_dist_index_js_ruleSet_1_rules_5_use_0_PeopleLayoutCard_vue_vue_type_style_index_0_id_5ec2ee89_scoped_true_lang_css__WEBPACK_IMPORTED_MODULE_6__["default"].locals ? _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_dist_stylePostLoader_js_node_modules_vue_loader_dist_index_js_ruleSet_1_rules_5_use_0_PeopleLayoutCard_vue_vue_type_style_index_0_id_5ec2ee89_scoped_true_lang_css__WEBPACK_IMPORTED_MODULE_6__["default"].locals : undefined);


/***/ },

/***/ "./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/dist/stylePostLoader.js!./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/people/PeopleLayoutGrid.vue?vue&type=style&index=0&id=7235f3bf&scoped=true&lang=css"
/*!****************************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/dist/stylePostLoader.js!./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/people/PeopleLayoutGrid.vue?vue&type=style&index=0&id=7235f3bf&scoped=true&lang=css ***!
  \****************************************************************************************************************************************************************************************************************************************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

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
/* harmony import */ var _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_dist_stylePostLoader_js_node_modules_vue_loader_dist_index_js_ruleSet_1_rules_5_use_0_PeopleLayoutGrid_vue_vue_type_style_index_0_id_7235f3bf_scoped_true_lang_css__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! !!../../../node_modules/css-loader/dist/cjs.js!../../../node_modules/vue-loader/dist/stylePostLoader.js!../../../node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./PeopleLayoutGrid.vue?vue&type=style&index=0&id=7235f3bf&scoped=true&lang=css */ "./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/dist/stylePostLoader.js!./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/people/PeopleLayoutGrid.vue?vue&type=style&index=0&id=7235f3bf&scoped=true&lang=css");

      
      
      
      
      
      
      
      
      

var options = {};

options.styleTagTransform = (_node_modules_style_loader_dist_runtime_styleTagTransform_js__WEBPACK_IMPORTED_MODULE_5___default());
options.setAttributes = (_node_modules_style_loader_dist_runtime_setAttributesWithoutAttributes_js__WEBPACK_IMPORTED_MODULE_3___default());

      options.insert = _node_modules_style_loader_dist_runtime_insertBySelector_js__WEBPACK_IMPORTED_MODULE_2___default().bind(null, "head");
    
options.domAPI = (_node_modules_style_loader_dist_runtime_styleDomAPI_js__WEBPACK_IMPORTED_MODULE_1___default());
options.insertStyleElement = (_node_modules_style_loader_dist_runtime_insertStyleElement_js__WEBPACK_IMPORTED_MODULE_4___default());

var update = _node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0___default()(_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_dist_stylePostLoader_js_node_modules_vue_loader_dist_index_js_ruleSet_1_rules_5_use_0_PeopleLayoutGrid_vue_vue_type_style_index_0_id_7235f3bf_scoped_true_lang_css__WEBPACK_IMPORTED_MODULE_6__["default"], options);




       /* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_dist_stylePostLoader_js_node_modules_vue_loader_dist_index_js_ruleSet_1_rules_5_use_0_PeopleLayoutGrid_vue_vue_type_style_index_0_id_7235f3bf_scoped_true_lang_css__WEBPACK_IMPORTED_MODULE_6__["default"] && _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_dist_stylePostLoader_js_node_modules_vue_loader_dist_index_js_ruleSet_1_rules_5_use_0_PeopleLayoutGrid_vue_vue_type_style_index_0_id_7235f3bf_scoped_true_lang_css__WEBPACK_IMPORTED_MODULE_6__["default"].locals ? _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_dist_stylePostLoader_js_node_modules_vue_loader_dist_index_js_ruleSet_1_rules_5_use_0_PeopleLayoutGrid_vue_vue_type_style_index_0_id_7235f3bf_scoped_true_lang_css__WEBPACK_IMPORTED_MODULE_6__["default"].locals : undefined);


/***/ },

/***/ "./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/dist/stylePostLoader.js!./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/people/PeopleLayoutList.vue?vue&type=style&index=0&id=a114a5d2&scoped=true&lang=css"
/*!****************************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/dist/stylePostLoader.js!./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/people/PeopleLayoutList.vue?vue&type=style&index=0&id=a114a5d2&scoped=true&lang=css ***!
  \****************************************************************************************************************************************************************************************************************************************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

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
/* harmony import */ var _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_dist_stylePostLoader_js_node_modules_vue_loader_dist_index_js_ruleSet_1_rules_5_use_0_PeopleLayoutList_vue_vue_type_style_index_0_id_a114a5d2_scoped_true_lang_css__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! !!../../../node_modules/css-loader/dist/cjs.js!../../../node_modules/vue-loader/dist/stylePostLoader.js!../../../node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./PeopleLayoutList.vue?vue&type=style&index=0&id=a114a5d2&scoped=true&lang=css */ "./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/dist/stylePostLoader.js!./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/people/PeopleLayoutList.vue?vue&type=style&index=0&id=a114a5d2&scoped=true&lang=css");

      
      
      
      
      
      
      
      
      

var options = {};

options.styleTagTransform = (_node_modules_style_loader_dist_runtime_styleTagTransform_js__WEBPACK_IMPORTED_MODULE_5___default());
options.setAttributes = (_node_modules_style_loader_dist_runtime_setAttributesWithoutAttributes_js__WEBPACK_IMPORTED_MODULE_3___default());

      options.insert = _node_modules_style_loader_dist_runtime_insertBySelector_js__WEBPACK_IMPORTED_MODULE_2___default().bind(null, "head");
    
options.domAPI = (_node_modules_style_loader_dist_runtime_styleDomAPI_js__WEBPACK_IMPORTED_MODULE_1___default());
options.insertStyleElement = (_node_modules_style_loader_dist_runtime_insertStyleElement_js__WEBPACK_IMPORTED_MODULE_4___default());

var update = _node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0___default()(_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_dist_stylePostLoader_js_node_modules_vue_loader_dist_index_js_ruleSet_1_rules_5_use_0_PeopleLayoutList_vue_vue_type_style_index_0_id_a114a5d2_scoped_true_lang_css__WEBPACK_IMPORTED_MODULE_6__["default"], options);




       /* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_dist_stylePostLoader_js_node_modules_vue_loader_dist_index_js_ruleSet_1_rules_5_use_0_PeopleLayoutList_vue_vue_type_style_index_0_id_a114a5d2_scoped_true_lang_css__WEBPACK_IMPORTED_MODULE_6__["default"] && _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_dist_stylePostLoader_js_node_modules_vue_loader_dist_index_js_ruleSet_1_rules_5_use_0_PeopleLayoutList_vue_vue_type_style_index_0_id_a114a5d2_scoped_true_lang_css__WEBPACK_IMPORTED_MODULE_6__["default"].locals ? _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_dist_stylePostLoader_js_node_modules_vue_loader_dist_index_js_ruleSet_1_rules_5_use_0_PeopleLayoutList_vue_vue_type_style_index_0_id_a114a5d2_scoped_true_lang_css__WEBPACK_IMPORTED_MODULE_6__["default"].locals : undefined);


/***/ },

/***/ "./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/dist/stylePostLoader.js!./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/people/PersonItem.vue?vue&type=style&index=0&id=e9a9c070&scoped=true&lang=css"
/*!**********************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/dist/stylePostLoader.js!./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/people/PersonItem.vue?vue&type=style&index=0&id=e9a9c070&scoped=true&lang=css ***!
  \**********************************************************************************************************************************************************************************************************************************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

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
/* harmony import */ var _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_dist_stylePostLoader_js_node_modules_vue_loader_dist_index_js_ruleSet_1_rules_5_use_0_PersonItem_vue_vue_type_style_index_0_id_e9a9c070_scoped_true_lang_css__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! !!../../../node_modules/css-loader/dist/cjs.js!../../../node_modules/vue-loader/dist/stylePostLoader.js!../../../node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./PersonItem.vue?vue&type=style&index=0&id=e9a9c070&scoped=true&lang=css */ "./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/dist/stylePostLoader.js!./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/people/PersonItem.vue?vue&type=style&index=0&id=e9a9c070&scoped=true&lang=css");

      
      
      
      
      
      
      
      
      

var options = {};

options.styleTagTransform = (_node_modules_style_loader_dist_runtime_styleTagTransform_js__WEBPACK_IMPORTED_MODULE_5___default());
options.setAttributes = (_node_modules_style_loader_dist_runtime_setAttributesWithoutAttributes_js__WEBPACK_IMPORTED_MODULE_3___default());

      options.insert = _node_modules_style_loader_dist_runtime_insertBySelector_js__WEBPACK_IMPORTED_MODULE_2___default().bind(null, "head");
    
options.domAPI = (_node_modules_style_loader_dist_runtime_styleDomAPI_js__WEBPACK_IMPORTED_MODULE_1___default());
options.insertStyleElement = (_node_modules_style_loader_dist_runtime_insertStyleElement_js__WEBPACK_IMPORTED_MODULE_4___default());

var update = _node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0___default()(_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_dist_stylePostLoader_js_node_modules_vue_loader_dist_index_js_ruleSet_1_rules_5_use_0_PersonItem_vue_vue_type_style_index_0_id_e9a9c070_scoped_true_lang_css__WEBPACK_IMPORTED_MODULE_6__["default"], options);




       /* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_dist_stylePostLoader_js_node_modules_vue_loader_dist_index_js_ruleSet_1_rules_5_use_0_PersonItem_vue_vue_type_style_index_0_id_e9a9c070_scoped_true_lang_css__WEBPACK_IMPORTED_MODULE_6__["default"] && _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_dist_stylePostLoader_js_node_modules_vue_loader_dist_index_js_ruleSet_1_rules_5_use_0_PersonItem_vue_vue_type_style_index_0_id_e9a9c070_scoped_true_lang_css__WEBPACK_IMPORTED_MODULE_6__["default"].locals ? _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_dist_stylePostLoader_js_node_modules_vue_loader_dist_index_js_ruleSet_1_rules_5_use_0_PersonItem_vue_vue_type_style_index_0_id_e9a9c070_scoped_true_lang_css__WEBPACK_IMPORTED_MODULE_6__["default"].locals : undefined);


/***/ },

/***/ "./src/components/PeopleWidget.vue"
/*!*****************************************!*\
  !*** ./src/components/PeopleWidget.vue ***!
  \*****************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _PeopleWidget_vue_vue_type_template_id_253d9463_scoped_true__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./PeopleWidget.vue?vue&type=template&id=253d9463&scoped=true */ "./src/components/PeopleWidget.vue?vue&type=template&id=253d9463&scoped=true");
/* harmony import */ var _PeopleWidget_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./PeopleWidget.vue?vue&type=script&lang=js */ "./src/components/PeopleWidget.vue?vue&type=script&lang=js");
/* harmony import */ var _PeopleWidget_vue_vue_type_style_index_0_id_253d9463_scoped_true_lang_css__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./PeopleWidget.vue?vue&type=style&index=0&id=253d9463&scoped=true&lang=css */ "./src/components/PeopleWidget.vue?vue&type=style&index=0&id=253d9463&scoped=true&lang=css");
/* harmony import */ var _node_modules_vue_loader_dist_exportHelper_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ../../node_modules/vue-loader/dist/exportHelper.js */ "./node_modules/vue-loader/dist/exportHelper.js");




;


const __exports__ = /*#__PURE__*/(0,_node_modules_vue_loader_dist_exportHelper_js__WEBPACK_IMPORTED_MODULE_3__["default"])(_PeopleWidget_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_1__["default"], [['render',_PeopleWidget_vue_vue_type_template_id_253d9463_scoped_true__WEBPACK_IMPORTED_MODULE_0__.render],['__scopeId',"data-v-253d9463"],['__file',"src/components/PeopleWidget.vue"]])
/* hot reload */
if (false) // removed by dead control flow
{}


/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (__exports__);

/***/ },

/***/ "./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/PeopleWidget.vue?vue&type=script&lang=js"
/*!*************************************************************************************************************************************!*\
  !*** ./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/PeopleWidget.vue?vue&type=script&lang=js ***!
  \*************************************************************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _nextcloud_vue__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @nextcloud/vue */ "./node_modules/@nextcloud/vue/dist/index.mjs");
/* harmony import */ var _nextcloud_router__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @nextcloud/router */ "./node_modules/@nextcloud/router/dist/index.mjs");
/* harmony import */ var _nextcloud_l10n__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @nextcloud/l10n */ "./node_modules/@nextcloud/l10n/dist/index.mjs");
/* harmony import */ var _nextcloud_axios__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! @nextcloud/axios */ "./node_modules/@nextcloud/axios/dist/index.js");
/* harmony import */ var vue_material_design_icons_AlertCircle_vue__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! vue-material-design-icons/AlertCircle.vue */ "./node_modules/vue-material-design-icons/AlertCircle.vue");
/* harmony import */ var vue_material_design_icons_AccountGroup_vue__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! vue-material-design-icons/AccountGroup.vue */ "./node_modules/vue-material-design-icons/AccountGroup.vue");
/* harmony import */ var _people_PeopleLayoutCard_vue__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! ./people/PeopleLayoutCard.vue */ "./src/components/people/PeopleLayoutCard.vue");
/* harmony import */ var _people_PeopleLayoutList_vue__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! ./people/PeopleLayoutList.vue */ "./src/components/people/PeopleLayoutList.vue");
/* harmony import */ var _people_PeopleLayoutGrid_vue__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__(/*! ./people/PeopleLayoutGrid.vue */ "./src/components/people/PeopleLayoutGrid.vue");











/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = ({
  name: 'PeopleWidget',
  components: {
    NcLoadingIcon: _nextcloud_vue__WEBPACK_IMPORTED_MODULE_0__.NcLoadingIcon,
    NcButton: _nextcloud_vue__WEBPACK_IMPORTED_MODULE_0__.NcButton,
    AlertCircle: vue_material_design_icons_AlertCircle_vue__WEBPACK_IMPORTED_MODULE_4__["default"],
    AccountGroup: vue_material_design_icons_AccountGroup_vue__WEBPACK_IMPORTED_MODULE_5__["default"],
    PeopleLayoutCard: _people_PeopleLayoutCard_vue__WEBPACK_IMPORTED_MODULE_6__["default"],
    PeopleLayoutList: _people_PeopleLayoutList_vue__WEBPACK_IMPORTED_MODULE_7__["default"],
    PeopleLayoutGrid: _people_PeopleLayoutGrid_vue__WEBPACK_IMPORTED_MODULE_8__["default"],
  },
  props: {
    widget: {
      type: Object,
      required: true,
    },
    editable: {
      type: Boolean,
      default: false,
    },
    rowBackgroundColor: {
      type: String,
      default: '',
    },
    shareToken: {
      type: String,
      default: '',
    },
    pageId: {
      type: String,
      default: '',
    },
  },
  data() {
    return {
      users: [],
      total: 0,
      hasMore: false,
      loading: true,
      loadingMore: false,
      error: null,
    };
  },
  computed: {
    layoutComponent() {
      const layouts = {
        card: _people_PeopleLayoutCard_vue__WEBPACK_IMPORTED_MODULE_6__["default"],
        list: _people_PeopleLayoutList_vue__WEBPACK_IMPORTED_MODULE_7__["default"],
        grid: _people_PeopleLayoutGrid_vue__WEBPACK_IMPORTED_MODULE_8__["default"],
      };
      return layouts[this.widget.layout] || _people_PeopleLayoutCard_vue__WEBPACK_IMPORTED_MODULE_6__["default"];
    },
    effectiveBackgroundColor() {
      return this.widget.backgroundColor || this.rowBackgroundColor || '';
    },
    titleStyle() {
      const bgColor = this.effectiveBackgroundColor;

      const colorMappings = {
        'var(--color-primary-element)': 'var(--color-primary-element-text)',
        'var(--color-primary-element-light)': 'var(--color-primary-element-light-text)',
        'var(--color-error)': 'var(--color-error-text)',
        'var(--color-warning)': 'var(--color-warning-text)',
        'var(--color-success)': 'var(--color-success-text)',
        'var(--color-background-dark)': 'var(--color-main-text)',
        'var(--color-background-hover)': 'var(--color-main-text)',
      };

      const textColor = colorMappings[bgColor];
      if (textColor) {
        return { color: textColor };
      }
      return {};
    },
    footerStyle() {
      // Use same color mapping as title, plus border adjustment for dark backgrounds
      const style = { ...this.titleStyle };
      if (this.isDarkBackground) {
        style.borderColor = 'rgba(255, 255, 255, 0.2)';
      }
      return style;
    },
    isDarkBackground() {
      const bgColor = this.effectiveBackgroundColor;
      // These background colors need light text
      const darkBackgrounds = [
        'var(--color-primary-element)',
        'var(--color-error)',
        'var(--color-warning)',
        'var(--color-success)',
      ];
      return darkBackgrounds.includes(bgColor);
    },
    showPaginationFooter() {
      // Show footer if there are more items than shown, or if we loaded additional items
      // Also respect showPagination setting (default true)
      const showPagination = this.widget.showPagination !== false;
      return showPagination && this.total > 0 && (this.hasMore || this.users.length < this.total || this.users.length > (this.widget.limit || 50));
    },
  },
  watch: {
    widget: {
      deep: true,
      handler() {
        clearTimeout(this._debounceTimer);
        this._debounceTimer = setTimeout(() => {
          // Reset pagination when widget config changes
          this.users = [];
          this.total = 0;
          this.hasMore = false;
          this.fetchPeople();
        }, 300);
      },
    },
  },
  mounted() {
    this.fetchPeople();
  },
  beforeUnmount() {
    clearTimeout(this._debounceTimer);
  },
  methods: {
    t(key, vars = {}) {
      return (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_2__.translate)('intravox', key, vars);
    },
    async fetchPeople(offset = 0) {
      if (offset === 0) {
        this.loading = true;
      }
      this.error = null;

      try {
        const limit = this.widget.limit || 50;
        const params = new URLSearchParams({
          sortBy: this.widget.sortBy || 'displayName',
          sortOrder: this.widget.sortOrder || 'asc',
          limit: String(limit),
          offset: String(offset),
        });

        // Manual mode: pass user IDs
        if (this.widget.selectionMode === 'manual' && this.widget.selectedUsers?.length > 0) {
          params.append('userIds', this.widget.selectedUsers.join(','));
        }
        // Filter mode: pass filters
        else if (this.widget.selectionMode === 'filter' && this.widget.filters?.length > 0) {
          params.append('filters', JSON.stringify(this.widget.filters));
          params.append('filterOperator', this.widget.filterOperator || 'AND');
        }
        // No users configured
        else {
          this.users = [];
          this.total = 0;
          this.hasMore = false;
          this.loading = false;
          return;
        }

        const url = this.shareToken
          ? (0,_nextcloud_router__WEBPACK_IMPORTED_MODULE_1__.generateUrl)(`/apps/intravox/api/share/${this.shareToken}/people?${params}`)
          : (0,_nextcloud_router__WEBPACK_IMPORTED_MODULE_1__.generateUrl)(`/apps/intravox/api/people?${params}`);

        const response = await _nextcloud_axios__WEBPACK_IMPORTED_MODULE_3__["default"].get(url);
        const newUsers = response.data.users || [];

        if (offset === 0) {
          this.users = newUsers;
        } else {
          this.users = [...this.users, ...newUsers];
        }

        this.total = response.data.total || this.users.length;
        this.hasMore = response.data.hasMore || false;
      } catch (err) {
        console.error('[PeopleWidget] Failed to fetch people:', err);
        this.error = this.t('Failed to load people');
      } finally {
        this.loading = false;
        this.loadingMore = false;
      }
    },
    async loadMore() {
      if (this.loadingMore || !this.hasMore) return;
      this.loadingMore = true;
      await this.fetchPeople(this.users.length);
    },
    getContainerStyle() {
      const style = {};
      if (this.widget.backgroundColor) {
        style.backgroundColor = this.widget.backgroundColor;
        style.padding = '20px';
        style.borderRadius = 'var(--border-radius-large)';
        // Negative margin-top pulls the widget up so title aligns with widgets without background
        style.marginTop = '-8px';
      }
      return style;
    },
  },
});


/***/ },

/***/ "./src/components/people/PeopleLayoutCard.vue"
/*!****************************************************!*\
  !*** ./src/components/people/PeopleLayoutCard.vue ***!
  \****************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _PeopleLayoutCard_vue_vue_type_template_id_5ec2ee89_scoped_true__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./PeopleLayoutCard.vue?vue&type=template&id=5ec2ee89&scoped=true */ "./src/components/people/PeopleLayoutCard.vue?vue&type=template&id=5ec2ee89&scoped=true");
/* harmony import */ var _PeopleLayoutCard_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./PeopleLayoutCard.vue?vue&type=script&lang=js */ "./src/components/people/PeopleLayoutCard.vue?vue&type=script&lang=js");
/* harmony import */ var _PeopleLayoutCard_vue_vue_type_style_index_0_id_5ec2ee89_scoped_true_lang_css__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./PeopleLayoutCard.vue?vue&type=style&index=0&id=5ec2ee89&scoped=true&lang=css */ "./src/components/people/PeopleLayoutCard.vue?vue&type=style&index=0&id=5ec2ee89&scoped=true&lang=css");
/* harmony import */ var _node_modules_vue_loader_dist_exportHelper_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ../../../node_modules/vue-loader/dist/exportHelper.js */ "./node_modules/vue-loader/dist/exportHelper.js");




;


const __exports__ = /*#__PURE__*/(0,_node_modules_vue_loader_dist_exportHelper_js__WEBPACK_IMPORTED_MODULE_3__["default"])(_PeopleLayoutCard_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_1__["default"], [['render',_PeopleLayoutCard_vue_vue_type_template_id_5ec2ee89_scoped_true__WEBPACK_IMPORTED_MODULE_0__.render],['__scopeId',"data-v-5ec2ee89"],['__file',"src/components/people/PeopleLayoutCard.vue"]])
/* hot reload */
if (false) // removed by dead control flow
{}


/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (__exports__);

/***/ },

/***/ "./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/people/PeopleLayoutCard.vue?vue&type=script&lang=js"
/*!************************************************************************************************************************************************!*\
  !*** ./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/people/PeopleLayoutCard.vue?vue&type=script&lang=js ***!
  \************************************************************************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _PersonItem_vue__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./PersonItem.vue */ "./src/components/people/PersonItem.vue");
/* harmony import */ var _utils_colorUtils_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ../../utils/colorUtils.js */ "./src/utils/colorUtils.js");




/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = ({
  name: 'PeopleLayoutCard',
  components: {
    PersonItem: _PersonItem_vue__WEBPACK_IMPORTED_MODULE_0__["default"],
  },
  props: {
    users: {
      type: Array,
      required: true,
    },
    widget: {
      type: Object,
      required: true,
    },
    rowBackgroundColor: {
      type: String,
      default: '',
    },
  },
  computed: {
    gridStyle() {
      const columns = this.widget.columns || 3;
      return {
        gridTemplateColumns: `repeat(${columns}, 1fr)`,
      };
    },
    effectiveBackgroundColor() {
      return (0,_utils_colorUtils_js__WEBPACK_IMPORTED_MODULE_1__.getEffectiveBackgroundColor)(this.widget.backgroundColor, this.rowBackgroundColor);
    },
    itemBackgroundMode() {
      const containerBg = this.effectiveBackgroundColor;

      if (!containerBg) {
        return 'default';
      }

      if ((0,_utils_colorUtils_js__WEBPACK_IMPORTED_MODULE_1__.isDarkBackground)(containerBg)) {
        return 'dark';
      }

      if ((0,_utils_colorUtils_js__WEBPACK_IMPORTED_MODULE_1__.isLightBackground)(containerBg)) {
        return 'white';
      }

      return 'default';
    },
  },
});


/***/ },

/***/ "./src/components/people/PeopleLayoutGrid.vue"
/*!****************************************************!*\
  !*** ./src/components/people/PeopleLayoutGrid.vue ***!
  \****************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _PeopleLayoutGrid_vue_vue_type_template_id_7235f3bf_scoped_true__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./PeopleLayoutGrid.vue?vue&type=template&id=7235f3bf&scoped=true */ "./src/components/people/PeopleLayoutGrid.vue?vue&type=template&id=7235f3bf&scoped=true");
/* harmony import */ var _PeopleLayoutGrid_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./PeopleLayoutGrid.vue?vue&type=script&lang=js */ "./src/components/people/PeopleLayoutGrid.vue?vue&type=script&lang=js");
/* harmony import */ var _PeopleLayoutGrid_vue_vue_type_style_index_0_id_7235f3bf_scoped_true_lang_css__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./PeopleLayoutGrid.vue?vue&type=style&index=0&id=7235f3bf&scoped=true&lang=css */ "./src/components/people/PeopleLayoutGrid.vue?vue&type=style&index=0&id=7235f3bf&scoped=true&lang=css");
/* harmony import */ var _node_modules_vue_loader_dist_exportHelper_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ../../../node_modules/vue-loader/dist/exportHelper.js */ "./node_modules/vue-loader/dist/exportHelper.js");




;


const __exports__ = /*#__PURE__*/(0,_node_modules_vue_loader_dist_exportHelper_js__WEBPACK_IMPORTED_MODULE_3__["default"])(_PeopleLayoutGrid_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_1__["default"], [['render',_PeopleLayoutGrid_vue_vue_type_template_id_7235f3bf_scoped_true__WEBPACK_IMPORTED_MODULE_0__.render],['__scopeId',"data-v-7235f3bf"],['__file',"src/components/people/PeopleLayoutGrid.vue"]])
/* hot reload */
if (false) // removed by dead control flow
{}


/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (__exports__);

/***/ },

/***/ "./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/people/PeopleLayoutGrid.vue?vue&type=script&lang=js"
/*!************************************************************************************************************************************************!*\
  !*** ./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/people/PeopleLayoutGrid.vue?vue&type=script&lang=js ***!
  \************************************************************************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _PersonItem_vue__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./PersonItem.vue */ "./src/components/people/PersonItem.vue");
/* harmony import */ var _utils_colorUtils_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ../../utils/colorUtils.js */ "./src/utils/colorUtils.js");




/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = ({
  name: 'PeopleLayoutGrid',
  components: {
    PersonItem: _PersonItem_vue__WEBPACK_IMPORTED_MODULE_0__["default"],
  },
  props: {
    users: {
      type: Array,
      required: true,
    },
    widget: {
      type: Object,
      required: true,
    },
    rowBackgroundColor: {
      type: String,
      default: '',
    },
  },
  computed: {
    gridStyle() {
      const columns = this.widget.columns || 4;
      return {
        gridTemplateColumns: `repeat(${columns}, 1fr)`,
      };
    },
    effectiveBackgroundColor() {
      return (0,_utils_colorUtils_js__WEBPACK_IMPORTED_MODULE_1__.getEffectiveBackgroundColor)(this.widget.backgroundColor, this.rowBackgroundColor);
    },
    itemBackgroundMode() {
      const containerBg = this.effectiveBackgroundColor;

      if (!containerBg) {
        return 'default';
      }

      if ((0,_utils_colorUtils_js__WEBPACK_IMPORTED_MODULE_1__.isDarkBackground)(containerBg)) {
        return 'dark';
      }

      if ((0,_utils_colorUtils_js__WEBPACK_IMPORTED_MODULE_1__.isLightBackground)(containerBg)) {
        return 'white';
      }

      return 'default';
    },
  },
});


/***/ },

/***/ "./src/components/people/PeopleLayoutList.vue"
/*!****************************************************!*\
  !*** ./src/components/people/PeopleLayoutList.vue ***!
  \****************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _PeopleLayoutList_vue_vue_type_template_id_a114a5d2_scoped_true__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./PeopleLayoutList.vue?vue&type=template&id=a114a5d2&scoped=true */ "./src/components/people/PeopleLayoutList.vue?vue&type=template&id=a114a5d2&scoped=true");
/* harmony import */ var _PeopleLayoutList_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./PeopleLayoutList.vue?vue&type=script&lang=js */ "./src/components/people/PeopleLayoutList.vue?vue&type=script&lang=js");
/* harmony import */ var _PeopleLayoutList_vue_vue_type_style_index_0_id_a114a5d2_scoped_true_lang_css__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./PeopleLayoutList.vue?vue&type=style&index=0&id=a114a5d2&scoped=true&lang=css */ "./src/components/people/PeopleLayoutList.vue?vue&type=style&index=0&id=a114a5d2&scoped=true&lang=css");
/* harmony import */ var _node_modules_vue_loader_dist_exportHelper_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ../../../node_modules/vue-loader/dist/exportHelper.js */ "./node_modules/vue-loader/dist/exportHelper.js");




;


const __exports__ = /*#__PURE__*/(0,_node_modules_vue_loader_dist_exportHelper_js__WEBPACK_IMPORTED_MODULE_3__["default"])(_PeopleLayoutList_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_1__["default"], [['render',_PeopleLayoutList_vue_vue_type_template_id_a114a5d2_scoped_true__WEBPACK_IMPORTED_MODULE_0__.render],['__scopeId',"data-v-a114a5d2"],['__file',"src/components/people/PeopleLayoutList.vue"]])
/* hot reload */
if (false) // removed by dead control flow
{}


/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (__exports__);

/***/ },

/***/ "./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/people/PeopleLayoutList.vue?vue&type=script&lang=js"
/*!************************************************************************************************************************************************!*\
  !*** ./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/people/PeopleLayoutList.vue?vue&type=script&lang=js ***!
  \************************************************************************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _PersonItem_vue__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./PersonItem.vue */ "./src/components/people/PersonItem.vue");
/* harmony import */ var _utils_colorUtils_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ../../utils/colorUtils.js */ "./src/utils/colorUtils.js");




/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = ({
  name: 'PeopleLayoutList',
  components: {
    PersonItem: _PersonItem_vue__WEBPACK_IMPORTED_MODULE_0__["default"],
  },
  props: {
    users: {
      type: Array,
      required: true,
    },
    widget: {
      type: Object,
      required: true,
    },
    rowBackgroundColor: {
      type: String,
      default: '',
    },
  },
  computed: {
    effectiveBackgroundColor() {
      return (0,_utils_colorUtils_js__WEBPACK_IMPORTED_MODULE_1__.getEffectiveBackgroundColor)(this.widget.backgroundColor, this.rowBackgroundColor);
    },
    itemBackgroundMode() {
      const containerBg = this.effectiveBackgroundColor;

      if (!containerBg) {
        return 'transparent';
      }

      if ((0,_utils_colorUtils_js__WEBPACK_IMPORTED_MODULE_1__.isDarkBackground)(containerBg)) {
        return 'dark';
      }

      if ((0,_utils_colorUtils_js__WEBPACK_IMPORTED_MODULE_1__.isLightBackground)(containerBg)) {
        return 'white';
      }

      return 'transparent';
    },
  },
});


/***/ },

/***/ "./src/components/people/PersonItem.vue"
/*!**********************************************!*\
  !*** ./src/components/people/PersonItem.vue ***!
  \**********************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _PersonItem_vue_vue_type_template_id_e9a9c070_scoped_true__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./PersonItem.vue?vue&type=template&id=e9a9c070&scoped=true */ "./src/components/people/PersonItem.vue?vue&type=template&id=e9a9c070&scoped=true");
/* harmony import */ var _PersonItem_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./PersonItem.vue?vue&type=script&lang=js */ "./src/components/people/PersonItem.vue?vue&type=script&lang=js");
/* harmony import */ var _PersonItem_vue_vue_type_style_index_0_id_e9a9c070_scoped_true_lang_css__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./PersonItem.vue?vue&type=style&index=0&id=e9a9c070&scoped=true&lang=css */ "./src/components/people/PersonItem.vue?vue&type=style&index=0&id=e9a9c070&scoped=true&lang=css");
/* harmony import */ var _node_modules_vue_loader_dist_exportHelper_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ../../../node_modules/vue-loader/dist/exportHelper.js */ "./node_modules/vue-loader/dist/exportHelper.js");




;


const __exports__ = /*#__PURE__*/(0,_node_modules_vue_loader_dist_exportHelper_js__WEBPACK_IMPORTED_MODULE_3__["default"])(_PersonItem_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_1__["default"], [['render',_PersonItem_vue_vue_type_template_id_e9a9c070_scoped_true__WEBPACK_IMPORTED_MODULE_0__.render],['__scopeId',"data-v-e9a9c070"],['__file',"src/components/people/PersonItem.vue"]])
/* hot reload */
if (false) // removed by dead control flow
{}


/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (__exports__);

/***/ },

/***/ "./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/people/PersonItem.vue?vue&type=script&lang=js"
/*!******************************************************************************************************************************************!*\
  !*** ./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/people/PersonItem.vue?vue&type=script&lang=js ***!
  \******************************************************************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _nextcloud_vue__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @nextcloud/vue */ "./node_modules/@nextcloud/vue/dist/index.mjs");
/* harmony import */ var vue_material_design_icons_EmailOutline_vue__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! vue-material-design-icons/EmailOutline.vue */ "./node_modules/vue-material-design-icons/EmailOutline.vue");
/* harmony import */ var vue_material_design_icons_Phone_vue__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! vue-material-design-icons/Phone.vue */ "./node_modules/vue-material-design-icons/Phone.vue");
/* harmony import */ var vue_material_design_icons_MapMarker_vue__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! vue-material-design-icons/MapMarker.vue */ "./node_modules/vue-material-design-icons/MapMarker.vue");
/* harmony import */ var vue_material_design_icons_Web_vue__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! vue-material-design-icons/Web.vue */ "./node_modules/vue-material-design-icons/Web.vue");
/* harmony import */ var vue_material_design_icons_Twitter_vue__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! vue-material-design-icons/Twitter.vue */ "./node_modules/vue-material-design-icons/Twitter.vue");
/* harmony import */ var vue_material_design_icons_CloudOutline_vue__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! vue-material-design-icons/CloudOutline.vue */ "./node_modules/vue-material-design-icons/CloudOutline.vue");
/* harmony import */ var vue_material_design_icons_Mastodon_vue__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! vue-material-design-icons/Mastodon.vue */ "./node_modules/vue-material-design-icons/Mastodon.vue");
/* harmony import */ var vue_material_design_icons_CakeVariant_vue__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__(/*! vue-material-design-icons/CakeVariant.vue */ "./node_modules/vue-material-design-icons/CakeVariant.vue");











/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = ({
  name: 'PersonItem',
  components: {
    NcAvatar: _nextcloud_vue__WEBPACK_IMPORTED_MODULE_0__.NcAvatar,
    EmailOutline: vue_material_design_icons_EmailOutline_vue__WEBPACK_IMPORTED_MODULE_1__["default"],
    Phone: vue_material_design_icons_Phone_vue__WEBPACK_IMPORTED_MODULE_2__["default"],
    MapMarker: vue_material_design_icons_MapMarker_vue__WEBPACK_IMPORTED_MODULE_3__["default"],
    Web: vue_material_design_icons_Web_vue__WEBPACK_IMPORTED_MODULE_4__["default"],
    Twitter: vue_material_design_icons_Twitter_vue__WEBPACK_IMPORTED_MODULE_5__["default"],
    CloudOutline: vue_material_design_icons_CloudOutline_vue__WEBPACK_IMPORTED_MODULE_6__["default"],
    Mastodon: vue_material_design_icons_Mastodon_vue__WEBPACK_IMPORTED_MODULE_7__["default"],
    CakeVariant: vue_material_design_icons_CakeVariant_vue__WEBPACK_IMPORTED_MODULE_8__["default"],
  },
  props: {
    user: {
      type: Object,
      required: true,
    },
    layout: {
      type: String,
      default: 'card',
      validator: (value) => ['card', 'list', 'grid'].includes(value),
    },
    showFields: {
      type: Object,
      default: () => ({
        // Basic information
        avatar: true,
        displayName: true,
        pronouns: false,
        role: true,        // Official job title
        headline: false,   // Personal tagline
        title: true,       // Legacy: for backwards compatibility
        department: true,
        // Contact
        email: true,
        phone: false,
        address: false,
        website: false,
        birthdate: false,
        // Extended
        biography: false,
        socialLinks: false,
        customFields: false,
      }),
    },
    itemBackground: {
      type: String,
      default: 'default',
      validator: (value) => ['transparent', 'dark', 'white', 'default'].includes(value),
    },
  },
  computed: {
    avatarSize() {
      switch (this.layout) {
        case 'card':
          return 80;
        case 'list':
          return 44;
        case 'grid':
          return 64;
        default:
          return 64;
      }
    },
    itemBackgroundClass() {
      return `background-${this.itemBackground}`;
    },
    hasContactInfo() {
      return (this.showField('email') && this.user.email)
        || (this.showField('phone') && this.user.phone)
        || (this.showField('address') && this.user.address)
        || (this.showField('website') && this.user.website)
        || (this.showField('birthdate') && this.user.birthdate);
    },
    hasSocialLinks() {
      // Support both new socialLinks and legacy twitter/fediverse fields
      const showSocial = this.showField('socialLinks')
        || this.showField('twitter')
        || this.showField('fediverse');
      return showSocial && (this.user.twitter || this.user.bluesky || this.user.fediverse);
    },
    showRole() {
      return this.showField('role');
    },
    showHeadline() {
      return this.showField('headline');
    },
    customFields() {
      // Known/standard fields that are handled separately
      // Include camelCase, lowercase, and underscore variants as backend uses propertyToKey()
      // which converts PROPERTY_NAME to lowercase with underscores (e.g., profile_enabled)
      const knownFields = [
        'uid', 'displayName', 'displayname', 'pronouns', 'email', 'phone', 'address', 'website',
        'twitter', 'bluesky', 'fediverse', 'organisation', 'department', 'role', 'birthdate',
        'headline', 'biography', 'avatarUrl', 'groups', 'status',
        // Nextcloud internal fields - all possible naming variants
        'profileEnabled', 'profileenabled', 'profile_enabled',
      ];

      const fields = [];
      for (const [key, value] of Object.entries(this.user)) {
        // Skip known fields, null/empty values, and internal fields
        if (knownFields.includes(key)) continue;
        if (value === null || value === undefined || value === '') continue;
        if (typeof value === 'object') continue; // Skip complex objects

        // Create a readable label from the key
        const label = key
          .replace(/([A-Z])/g, ' $1') // camelCase to spaces
          .replace(/[_-]/g, ' ')       // underscores/dashes to spaces
          .replace(/^\w/, c => c.toUpperCase()) // capitalize first letter
          .trim();

        fields.push({ key, label, value: String(value) });
      }

      return fields;
    },
  },
  methods: {
    showField(fieldName) {
      return this.showFields[fieldName] !== false;
    },
    truncateBio(text, maxLength = 150) {
      if (!text || text.length <= maxLength) return text;
      return text.substring(0, maxLength).trim() + '...';
    },
    formatWebsite(url) {
      // Remove protocol and trailing slash for cleaner display
      return url.replace(/^https?:\/\//, '').replace(/\/$/, '');
    },
    getTwitterUrl(handle) {
      // Handle can be @username or just username
      const username = handle.replace(/^@/, '');
      return `https://x.com/${username}`;
    },
    getBlueskyUrl(handle) {
      // Handle can be @user.bsky.social, user.bsky.social, or a full URL
      if (handle.startsWith('http')) {
        return handle;
      }
      const username = handle.replace(/^@/, '');
      return `https://bsky.app/profile/${username}`;
    },
    formatBirthdate(dateStr) {
      if (!dateStr) return '';
      try {
        const date = new Date(dateStr + 'T00:00:00');
        if (isNaN(date.getTime())) {
          return dateStr;
        }
        return date.toLocaleDateString(undefined, { month: 'long', day: 'numeric' });
      } catch {
        return dateStr;
      }
    },
    getFediverseUrl(handle) {
      // Handle format: @user@instance.social
      if (handle.startsWith('@')) {
        const parts = handle.substring(1).split('@');
        if (parts.length === 2) {
          return `https://${parts[1]}/@${parts[0]}`;
        }
      }
      // If it's already a URL or unknown format, return as-is
      if (handle.startsWith('http')) {
        return handle;
      }
      return '#';
    },
  },
});


/***/ },

/***/ "./src/components/PeopleWidget.vue?vue&type=style&index=0&id=253d9463&scoped=true&lang=css"
/*!*************************************************************************************************!*\
  !*** ./src/components/PeopleWidget.vue?vue&type=style&index=0&id=253d9463&scoped=true&lang=css ***!
  \*************************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _node_modules_style_loader_dist_cjs_js_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_dist_stylePostLoader_js_node_modules_vue_loader_dist_index_js_ruleSet_1_rules_5_use_0_PeopleWidget_vue_vue_type_style_index_0_id_253d9463_scoped_true_lang_css__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../node_modules/style-loader/dist/cjs.js!../../node_modules/css-loader/dist/cjs.js!../../node_modules/vue-loader/dist/stylePostLoader.js!../../node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./PeopleWidget.vue?vue&type=style&index=0&id=253d9463&scoped=true&lang=css */ "./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/dist/stylePostLoader.js!./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/PeopleWidget.vue?vue&type=style&index=0&id=253d9463&scoped=true&lang=css");


/***/ },

/***/ "./src/components/people/PeopleLayoutCard.vue?vue&type=style&index=0&id=5ec2ee89&scoped=true&lang=css"
/*!************************************************************************************************************!*\
  !*** ./src/components/people/PeopleLayoutCard.vue?vue&type=style&index=0&id=5ec2ee89&scoped=true&lang=css ***!
  \************************************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _node_modules_style_loader_dist_cjs_js_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_dist_stylePostLoader_js_node_modules_vue_loader_dist_index_js_ruleSet_1_rules_5_use_0_PeopleLayoutCard_vue_vue_type_style_index_0_id_5ec2ee89_scoped_true_lang_css__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../node_modules/style-loader/dist/cjs.js!../../../node_modules/css-loader/dist/cjs.js!../../../node_modules/vue-loader/dist/stylePostLoader.js!../../../node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./PeopleLayoutCard.vue?vue&type=style&index=0&id=5ec2ee89&scoped=true&lang=css */ "./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/dist/stylePostLoader.js!./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/people/PeopleLayoutCard.vue?vue&type=style&index=0&id=5ec2ee89&scoped=true&lang=css");


/***/ },

/***/ "./src/components/people/PeopleLayoutGrid.vue?vue&type=style&index=0&id=7235f3bf&scoped=true&lang=css"
/*!************************************************************************************************************!*\
  !*** ./src/components/people/PeopleLayoutGrid.vue?vue&type=style&index=0&id=7235f3bf&scoped=true&lang=css ***!
  \************************************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _node_modules_style_loader_dist_cjs_js_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_dist_stylePostLoader_js_node_modules_vue_loader_dist_index_js_ruleSet_1_rules_5_use_0_PeopleLayoutGrid_vue_vue_type_style_index_0_id_7235f3bf_scoped_true_lang_css__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../node_modules/style-loader/dist/cjs.js!../../../node_modules/css-loader/dist/cjs.js!../../../node_modules/vue-loader/dist/stylePostLoader.js!../../../node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./PeopleLayoutGrid.vue?vue&type=style&index=0&id=7235f3bf&scoped=true&lang=css */ "./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/dist/stylePostLoader.js!./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/people/PeopleLayoutGrid.vue?vue&type=style&index=0&id=7235f3bf&scoped=true&lang=css");


/***/ },

/***/ "./src/components/people/PeopleLayoutList.vue?vue&type=style&index=0&id=a114a5d2&scoped=true&lang=css"
/*!************************************************************************************************************!*\
  !*** ./src/components/people/PeopleLayoutList.vue?vue&type=style&index=0&id=a114a5d2&scoped=true&lang=css ***!
  \************************************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _node_modules_style_loader_dist_cjs_js_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_dist_stylePostLoader_js_node_modules_vue_loader_dist_index_js_ruleSet_1_rules_5_use_0_PeopleLayoutList_vue_vue_type_style_index_0_id_a114a5d2_scoped_true_lang_css__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../node_modules/style-loader/dist/cjs.js!../../../node_modules/css-loader/dist/cjs.js!../../../node_modules/vue-loader/dist/stylePostLoader.js!../../../node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./PeopleLayoutList.vue?vue&type=style&index=0&id=a114a5d2&scoped=true&lang=css */ "./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/dist/stylePostLoader.js!./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/people/PeopleLayoutList.vue?vue&type=style&index=0&id=a114a5d2&scoped=true&lang=css");


/***/ },

/***/ "./src/components/people/PersonItem.vue?vue&type=style&index=0&id=e9a9c070&scoped=true&lang=css"
/*!******************************************************************************************************!*\
  !*** ./src/components/people/PersonItem.vue?vue&type=style&index=0&id=e9a9c070&scoped=true&lang=css ***!
  \******************************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _node_modules_style_loader_dist_cjs_js_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_dist_stylePostLoader_js_node_modules_vue_loader_dist_index_js_ruleSet_1_rules_5_use_0_PersonItem_vue_vue_type_style_index_0_id_e9a9c070_scoped_true_lang_css__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../node_modules/style-loader/dist/cjs.js!../../../node_modules/css-loader/dist/cjs.js!../../../node_modules/vue-loader/dist/stylePostLoader.js!../../../node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./PersonItem.vue?vue&type=style&index=0&id=e9a9c070&scoped=true&lang=css */ "./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/dist/stylePostLoader.js!./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/people/PersonItem.vue?vue&type=style&index=0&id=e9a9c070&scoped=true&lang=css");


/***/ },

/***/ "./src/components/PeopleWidget.vue?vue&type=script&lang=js"
/*!*****************************************************************!*\
  !*** ./src/components/PeopleWidget.vue?vue&type=script&lang=js ***!
  \*****************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* reexport safe */ _node_modules_vue_loader_dist_index_js_ruleSet_1_rules_5_use_0_PeopleWidget_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_0__["default"])
/* harmony export */ });
/* harmony import */ var _node_modules_vue_loader_dist_index_js_ruleSet_1_rules_5_use_0_PeopleWidget_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./PeopleWidget.vue?vue&type=script&lang=js */ "./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/PeopleWidget.vue?vue&type=script&lang=js");
 

/***/ },

/***/ "./src/components/people/PeopleLayoutCard.vue?vue&type=script&lang=js"
/*!****************************************************************************!*\
  !*** ./src/components/people/PeopleLayoutCard.vue?vue&type=script&lang=js ***!
  \****************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* reexport safe */ _node_modules_vue_loader_dist_index_js_ruleSet_1_rules_5_use_0_PeopleLayoutCard_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_0__["default"])
/* harmony export */ });
/* harmony import */ var _node_modules_vue_loader_dist_index_js_ruleSet_1_rules_5_use_0_PeopleLayoutCard_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./PeopleLayoutCard.vue?vue&type=script&lang=js */ "./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/people/PeopleLayoutCard.vue?vue&type=script&lang=js");
 

/***/ },

/***/ "./src/components/people/PeopleLayoutGrid.vue?vue&type=script&lang=js"
/*!****************************************************************************!*\
  !*** ./src/components/people/PeopleLayoutGrid.vue?vue&type=script&lang=js ***!
  \****************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* reexport safe */ _node_modules_vue_loader_dist_index_js_ruleSet_1_rules_5_use_0_PeopleLayoutGrid_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_0__["default"])
/* harmony export */ });
/* harmony import */ var _node_modules_vue_loader_dist_index_js_ruleSet_1_rules_5_use_0_PeopleLayoutGrid_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./PeopleLayoutGrid.vue?vue&type=script&lang=js */ "./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/people/PeopleLayoutGrid.vue?vue&type=script&lang=js");
 

/***/ },

/***/ "./src/components/people/PeopleLayoutList.vue?vue&type=script&lang=js"
/*!****************************************************************************!*\
  !*** ./src/components/people/PeopleLayoutList.vue?vue&type=script&lang=js ***!
  \****************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* reexport safe */ _node_modules_vue_loader_dist_index_js_ruleSet_1_rules_5_use_0_PeopleLayoutList_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_0__["default"])
/* harmony export */ });
/* harmony import */ var _node_modules_vue_loader_dist_index_js_ruleSet_1_rules_5_use_0_PeopleLayoutList_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./PeopleLayoutList.vue?vue&type=script&lang=js */ "./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/people/PeopleLayoutList.vue?vue&type=script&lang=js");
 

/***/ },

/***/ "./src/components/people/PersonItem.vue?vue&type=script&lang=js"
/*!**********************************************************************!*\
  !*** ./src/components/people/PersonItem.vue?vue&type=script&lang=js ***!
  \**********************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* reexport safe */ _node_modules_vue_loader_dist_index_js_ruleSet_1_rules_5_use_0_PersonItem_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_0__["default"])
/* harmony export */ });
/* harmony import */ var _node_modules_vue_loader_dist_index_js_ruleSet_1_rules_5_use_0_PersonItem_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./PersonItem.vue?vue&type=script&lang=js */ "./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/people/PersonItem.vue?vue&type=script&lang=js");
 

/***/ },

/***/ "./src/components/PeopleWidget.vue?vue&type=template&id=253d9463&scoped=true"
/*!***********************************************************************************!*\
  !*** ./src/components/PeopleWidget.vue?vue&type=template&id=253d9463&scoped=true ***!
  \***********************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   render: () => (/* reexport safe */ _node_modules_vue_loader_dist_templateLoader_js_ruleSet_1_rules_2_node_modules_vue_loader_dist_index_js_ruleSet_1_rules_5_use_0_PeopleWidget_vue_vue_type_template_id_253d9463_scoped_true__WEBPACK_IMPORTED_MODULE_0__.render)
/* harmony export */ });
/* harmony import */ var _node_modules_vue_loader_dist_templateLoader_js_ruleSet_1_rules_2_node_modules_vue_loader_dist_index_js_ruleSet_1_rules_5_use_0_PeopleWidget_vue_vue_type_template_id_253d9463_scoped_true__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../node_modules/vue-loader/dist/templateLoader.js??ruleSet[1].rules[2]!../../node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./PeopleWidget.vue?vue&type=template&id=253d9463&scoped=true */ "./node_modules/vue-loader/dist/templateLoader.js??ruleSet[1].rules[2]!./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/PeopleWidget.vue?vue&type=template&id=253d9463&scoped=true");


/***/ },

/***/ "./src/components/people/PeopleLayoutCard.vue?vue&type=template&id=5ec2ee89&scoped=true"
/*!**********************************************************************************************!*\
  !*** ./src/components/people/PeopleLayoutCard.vue?vue&type=template&id=5ec2ee89&scoped=true ***!
  \**********************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   render: () => (/* reexport safe */ _node_modules_vue_loader_dist_templateLoader_js_ruleSet_1_rules_2_node_modules_vue_loader_dist_index_js_ruleSet_1_rules_5_use_0_PeopleLayoutCard_vue_vue_type_template_id_5ec2ee89_scoped_true__WEBPACK_IMPORTED_MODULE_0__.render)
/* harmony export */ });
/* harmony import */ var _node_modules_vue_loader_dist_templateLoader_js_ruleSet_1_rules_2_node_modules_vue_loader_dist_index_js_ruleSet_1_rules_5_use_0_PeopleLayoutCard_vue_vue_type_template_id_5ec2ee89_scoped_true__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../node_modules/vue-loader/dist/templateLoader.js??ruleSet[1].rules[2]!../../../node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./PeopleLayoutCard.vue?vue&type=template&id=5ec2ee89&scoped=true */ "./node_modules/vue-loader/dist/templateLoader.js??ruleSet[1].rules[2]!./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/people/PeopleLayoutCard.vue?vue&type=template&id=5ec2ee89&scoped=true");


/***/ },

/***/ "./src/components/people/PeopleLayoutGrid.vue?vue&type=template&id=7235f3bf&scoped=true"
/*!**********************************************************************************************!*\
  !*** ./src/components/people/PeopleLayoutGrid.vue?vue&type=template&id=7235f3bf&scoped=true ***!
  \**********************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   render: () => (/* reexport safe */ _node_modules_vue_loader_dist_templateLoader_js_ruleSet_1_rules_2_node_modules_vue_loader_dist_index_js_ruleSet_1_rules_5_use_0_PeopleLayoutGrid_vue_vue_type_template_id_7235f3bf_scoped_true__WEBPACK_IMPORTED_MODULE_0__.render)
/* harmony export */ });
/* harmony import */ var _node_modules_vue_loader_dist_templateLoader_js_ruleSet_1_rules_2_node_modules_vue_loader_dist_index_js_ruleSet_1_rules_5_use_0_PeopleLayoutGrid_vue_vue_type_template_id_7235f3bf_scoped_true__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../node_modules/vue-loader/dist/templateLoader.js??ruleSet[1].rules[2]!../../../node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./PeopleLayoutGrid.vue?vue&type=template&id=7235f3bf&scoped=true */ "./node_modules/vue-loader/dist/templateLoader.js??ruleSet[1].rules[2]!./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/people/PeopleLayoutGrid.vue?vue&type=template&id=7235f3bf&scoped=true");


/***/ },

/***/ "./src/components/people/PeopleLayoutList.vue?vue&type=template&id=a114a5d2&scoped=true"
/*!**********************************************************************************************!*\
  !*** ./src/components/people/PeopleLayoutList.vue?vue&type=template&id=a114a5d2&scoped=true ***!
  \**********************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   render: () => (/* reexport safe */ _node_modules_vue_loader_dist_templateLoader_js_ruleSet_1_rules_2_node_modules_vue_loader_dist_index_js_ruleSet_1_rules_5_use_0_PeopleLayoutList_vue_vue_type_template_id_a114a5d2_scoped_true__WEBPACK_IMPORTED_MODULE_0__.render)
/* harmony export */ });
/* harmony import */ var _node_modules_vue_loader_dist_templateLoader_js_ruleSet_1_rules_2_node_modules_vue_loader_dist_index_js_ruleSet_1_rules_5_use_0_PeopleLayoutList_vue_vue_type_template_id_a114a5d2_scoped_true__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../node_modules/vue-loader/dist/templateLoader.js??ruleSet[1].rules[2]!../../../node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./PeopleLayoutList.vue?vue&type=template&id=a114a5d2&scoped=true */ "./node_modules/vue-loader/dist/templateLoader.js??ruleSet[1].rules[2]!./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/people/PeopleLayoutList.vue?vue&type=template&id=a114a5d2&scoped=true");


/***/ },

/***/ "./src/components/people/PersonItem.vue?vue&type=template&id=e9a9c070&scoped=true"
/*!****************************************************************************************!*\
  !*** ./src/components/people/PersonItem.vue?vue&type=template&id=e9a9c070&scoped=true ***!
  \****************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   render: () => (/* reexport safe */ _node_modules_vue_loader_dist_templateLoader_js_ruleSet_1_rules_2_node_modules_vue_loader_dist_index_js_ruleSet_1_rules_5_use_0_PersonItem_vue_vue_type_template_id_e9a9c070_scoped_true__WEBPACK_IMPORTED_MODULE_0__.render)
/* harmony export */ });
/* harmony import */ var _node_modules_vue_loader_dist_templateLoader_js_ruleSet_1_rules_2_node_modules_vue_loader_dist_index_js_ruleSet_1_rules_5_use_0_PersonItem_vue_vue_type_template_id_e9a9c070_scoped_true__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../node_modules/vue-loader/dist/templateLoader.js??ruleSet[1].rules[2]!../../../node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./PersonItem.vue?vue&type=template&id=e9a9c070&scoped=true */ "./node_modules/vue-loader/dist/templateLoader.js??ruleSet[1].rules[2]!./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/people/PersonItem.vue?vue&type=template&id=e9a9c070&scoped=true");


/***/ },

/***/ "./node_modules/vue-loader/dist/templateLoader.js??ruleSet[1].rules[2]!./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/PeopleWidget.vue?vue&type=template&id=253d9463&scoped=true"
/*!*****************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/vue-loader/dist/templateLoader.js??ruleSet[1].rules[2]!./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/PeopleWidget.vue?vue&type=template&id=253d9463&scoped=true ***!
  \*****************************************************************************************************************************************************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   render: () => (/* binding */ render)
/* harmony export */ });
/* harmony import */ var vue__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! vue */ "./node_modules/vue/dist/vue.runtime.esm-bundler.js");


const _hoisted_1 = {
  key: 1,
  class: "people-loading"
}
const _hoisted_2 = {
  key: 2,
  class: "people-error"
}
const _hoisted_3 = {
  key: 3,
  class: "people-empty"
}
const _hoisted_4 = { key: 0 }
const _hoisted_5 = { key: 1 }
const _hoisted_6 = { class: "people-count" }

function render(_ctx, _cache, $props, $setup, $data, $options) {
  const _component_NcLoadingIcon = (0,vue__WEBPACK_IMPORTED_MODULE_0__.resolveComponent)("NcLoadingIcon")
  const _component_AlertCircle = (0,vue__WEBPACK_IMPORTED_MODULE_0__.resolveComponent)("AlertCircle")
  const _component_AccountGroup = (0,vue__WEBPACK_IMPORTED_MODULE_0__.resolveComponent)("AccountGroup")
  const _component_NcButton = (0,vue__WEBPACK_IMPORTED_MODULE_0__.resolveComponent)("NcButton")

  return ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)("div", {
    class: "people-widget",
    style: (0,vue__WEBPACK_IMPORTED_MODULE_0__.normalizeStyle)($options.getContainerStyle())
  }, [
    ($props.widget.title)
      ? ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)("h3", {
          key: 0,
          class: "people-widget-title",
          style: (0,vue__WEBPACK_IMPORTED_MODULE_0__.normalizeStyle)($options.titleStyle)
        }, (0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)($props.widget.title), 5 /* TEXT, STYLE */))
      : (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)("v-if", true),
    ($data.loading && $data.users.length === 0)
      ? ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)("div", _hoisted_1, [
          (0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_NcLoadingIcon, { size: 32 }),
          (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("span", null, (0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)($options.t('Loading...')), 1 /* TEXT */)
        ]))
      : ($data.error)
        ? ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)("div", _hoisted_2, [
            (0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_AlertCircle, { size: 24 }),
            (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("span", null, (0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)($data.error), 1 /* TEXT */)
          ]))
        : ($data.users.length === 0)
          ? ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)("div", _hoisted_3, [
              (0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_AccountGroup, { size: 32 }),
              (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("p", null, (0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)($options.t('No people found')), 1 /* TEXT */),
              ($props.widget.selectionMode === 'manual')
                ? ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)("small", _hoisted_4, (0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)($options.t('Select people in edit mode')), 1 /* TEXT */))
                : ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)("small", _hoisted_5, (0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)($options.t('No users match the current filters')), 1 /* TEXT */))
            ]))
          : ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)(vue__WEBPACK_IMPORTED_MODULE_0__.Fragment, { key: 4 }, [
              ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createBlock)((0,vue__WEBPACK_IMPORTED_MODULE_0__.resolveDynamicComponent)($options.layoutComponent), {
                users: $data.users,
                widget: $props.widget,
                "row-background-color": $options.effectiveBackgroundColor
              }, null, 8 /* PROPS */, ["users", "widget", "row-background-color"])),
              (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)(" Pagination footer "),
              ($options.showPaginationFooter)
                ? ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)("div", {
                    key: 0,
                    class: (0,vue__WEBPACK_IMPORTED_MODULE_0__.normalizeClass)(["people-footer", { 'dark-background': $options.isDarkBackground }]),
                    style: (0,vue__WEBPACK_IMPORTED_MODULE_0__.normalizeStyle)($options.footerStyle)
                  }, [
                    (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("span", _hoisted_6, (0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)($options.t('Showing {shown} of {total} people', { shown: $data.users.length, total: $data.total })), 1 /* TEXT */),
                    ($data.hasMore)
                      ? ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createBlock)(_component_NcButton, {
                          key: 0,
                          type: $options.isDarkBackground ? 'primary' : 'secondary',
                          disabled: $data.loadingMore,
                          onClick: $options.loadMore
                        }, {
                          icon: (0,vue__WEBPACK_IMPORTED_MODULE_0__.withCtx)(() => [
                            ($data.loadingMore)
                              ? ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createBlock)(_component_NcLoadingIcon, {
                                  key: 0,
                                  size: 20
                                }))
                              : (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)("v-if", true)
                          ]),
                          default: (0,vue__WEBPACK_IMPORTED_MODULE_0__.withCtx)(() => [
                            (0,vue__WEBPACK_IMPORTED_MODULE_0__.createTextVNode)(" " + (0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)($data.loadingMore ? $options.t('Loading...') : $options.t('Show more')), 1 /* TEXT */)
                          ]),
                          _: 1 /* STABLE */
                        }, 8 /* PROPS */, ["type", "disabled", "onClick"]))
                      : (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)("v-if", true)
                  ], 6 /* CLASS, STYLE */))
                : (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)("v-if", true)
            ], 64 /* STABLE_FRAGMENT */))
  ], 4 /* STYLE */))
}

/***/ },

/***/ "./node_modules/vue-loader/dist/templateLoader.js??ruleSet[1].rules[2]!./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/people/PeopleLayoutCard.vue?vue&type=template&id=5ec2ee89&scoped=true"
/*!****************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/vue-loader/dist/templateLoader.js??ruleSet[1].rules[2]!./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/people/PeopleLayoutCard.vue?vue&type=template&id=5ec2ee89&scoped=true ***!
  \****************************************************************************************************************************************************************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   render: () => (/* binding */ render)
/* harmony export */ });
/* harmony import */ var vue__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! vue */ "./node_modules/vue/dist/vue.runtime.esm-bundler.js");


function render(_ctx, _cache, $props, $setup, $data, $options) {
  const _component_PersonItem = (0,vue__WEBPACK_IMPORTED_MODULE_0__.resolveComponent)("PersonItem")

  return ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)("div", {
    class: "people-layout-card",
    style: (0,vue__WEBPACK_IMPORTED_MODULE_0__.normalizeStyle)($options.gridStyle)
  }, [
    ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(true), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)(vue__WEBPACK_IMPORTED_MODULE_0__.Fragment, null, (0,vue__WEBPACK_IMPORTED_MODULE_0__.renderList)($props.users, (user) => {
      return ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createBlock)(_component_PersonItem, {
        key: user.uid,
        user: user,
        layout: "card",
        "show-fields": $props.widget.showFields || {},
        "item-background": $options.itemBackgroundMode,
        class: "people-card-item"
      }, null, 8 /* PROPS */, ["user", "show-fields", "item-background"]))
    }), 128 /* KEYED_FRAGMENT */))
  ], 4 /* STYLE */))
}

/***/ },

/***/ "./node_modules/vue-loader/dist/templateLoader.js??ruleSet[1].rules[2]!./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/people/PeopleLayoutGrid.vue?vue&type=template&id=7235f3bf&scoped=true"
/*!****************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/vue-loader/dist/templateLoader.js??ruleSet[1].rules[2]!./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/people/PeopleLayoutGrid.vue?vue&type=template&id=7235f3bf&scoped=true ***!
  \****************************************************************************************************************************************************************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   render: () => (/* binding */ render)
/* harmony export */ });
/* harmony import */ var vue__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! vue */ "./node_modules/vue/dist/vue.runtime.esm-bundler.js");


function render(_ctx, _cache, $props, $setup, $data, $options) {
  const _component_PersonItem = (0,vue__WEBPACK_IMPORTED_MODULE_0__.resolveComponent)("PersonItem")

  return ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)("div", {
    class: "people-layout-grid",
    style: (0,vue__WEBPACK_IMPORTED_MODULE_0__.normalizeStyle)($options.gridStyle)
  }, [
    ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(true), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)(vue__WEBPACK_IMPORTED_MODULE_0__.Fragment, null, (0,vue__WEBPACK_IMPORTED_MODULE_0__.renderList)($props.users, (user) => {
      return ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createBlock)(_component_PersonItem, {
        key: user.uid,
        user: user,
        layout: "grid",
        "show-fields": $props.widget.showFields || {},
        "item-background": $options.itemBackgroundMode,
        class: "people-grid-item"
      }, null, 8 /* PROPS */, ["user", "show-fields", "item-background"]))
    }), 128 /* KEYED_FRAGMENT */))
  ], 4 /* STYLE */))
}

/***/ },

/***/ "./node_modules/vue-loader/dist/templateLoader.js??ruleSet[1].rules[2]!./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/people/PeopleLayoutList.vue?vue&type=template&id=a114a5d2&scoped=true"
/*!****************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/vue-loader/dist/templateLoader.js??ruleSet[1].rules[2]!./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/people/PeopleLayoutList.vue?vue&type=template&id=a114a5d2&scoped=true ***!
  \****************************************************************************************************************************************************************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   render: () => (/* binding */ render)
/* harmony export */ });
/* harmony import */ var vue__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! vue */ "./node_modules/vue/dist/vue.runtime.esm-bundler.js");


const _hoisted_1 = { class: "people-layout-list" }

function render(_ctx, _cache, $props, $setup, $data, $options) {
  const _component_PersonItem = (0,vue__WEBPACK_IMPORTED_MODULE_0__.resolveComponent)("PersonItem")

  return ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)("div", _hoisted_1, [
    ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(true), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)(vue__WEBPACK_IMPORTED_MODULE_0__.Fragment, null, (0,vue__WEBPACK_IMPORTED_MODULE_0__.renderList)($props.users, (user) => {
      return ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createBlock)(_component_PersonItem, {
        key: user.uid,
        user: user,
        layout: "list",
        "show-fields": $props.widget.showFields || {},
        "item-background": $options.itemBackgroundMode,
        class: "people-list-item"
      }, null, 8 /* PROPS */, ["user", "show-fields", "item-background"]))
    }), 128 /* KEYED_FRAGMENT */))
  ]))
}

/***/ },

/***/ "./node_modules/vue-loader/dist/templateLoader.js??ruleSet[1].rules[2]!./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/people/PersonItem.vue?vue&type=template&id=e9a9c070&scoped=true"
/*!**********************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/vue-loader/dist/templateLoader.js??ruleSet[1].rules[2]!./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/people/PersonItem.vue?vue&type=template&id=e9a9c070&scoped=true ***!
  \**********************************************************************************************************************************************************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   render: () => (/* binding */ render)
/* harmony export */ });
/* harmony import */ var vue__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! vue */ "./node_modules/vue/dist/vue.runtime.esm-bundler.js");


const _hoisted_1 = {
  key: 0,
  class: "person-avatar"
}
const _hoisted_2 = { class: "person-content" }
const _hoisted_3 = {
  key: 0,
  class: "person-name"
}
const _hoisted_4 = {
  key: 0,
  class: "person-pronouns"
}
const _hoisted_5 = {
  key: 1,
  class: "person-role"
}
const _hoisted_6 = {
  key: 2,
  class: "person-headline"
}
const _hoisted_7 = {
  key: 3,
  class: "person-department"
}
const _hoisted_8 = {
  key: 4,
  class: "person-contact"
}
const _hoisted_9 = ["href"]
const _hoisted_10 = ["href"]
const _hoisted_11 = {
  key: 2,
  class: "person-contact-item person-address"
}
const _hoisted_12 = ["href"]
const _hoisted_13 = {
  key: 4,
  class: "person-contact-item person-birthdate"
}
const _hoisted_14 = {
  key: 5,
  class: "person-social"
}
const _hoisted_15 = ["href", "title"]
const _hoisted_16 = ["href", "title"]
const _hoisted_17 = ["href", "title"]
const _hoisted_18 = {
  key: 6,
  class: "person-biography"
}
const _hoisted_19 = {
  key: 7,
  class: "person-custom-fields"
}
const _hoisted_20 = { class: "custom-field-label" }
const _hoisted_21 = { class: "custom-field-value" }

function render(_ctx, _cache, $props, $setup, $data, $options) {
  const _component_NcAvatar = (0,vue__WEBPACK_IMPORTED_MODULE_0__.resolveComponent)("NcAvatar")
  const _component_EmailOutline = (0,vue__WEBPACK_IMPORTED_MODULE_0__.resolveComponent)("EmailOutline")
  const _component_Phone = (0,vue__WEBPACK_IMPORTED_MODULE_0__.resolveComponent)("Phone")
  const _component_MapMarker = (0,vue__WEBPACK_IMPORTED_MODULE_0__.resolveComponent)("MapMarker")
  const _component_Web = (0,vue__WEBPACK_IMPORTED_MODULE_0__.resolveComponent)("Web")
  const _component_CakeVariant = (0,vue__WEBPACK_IMPORTED_MODULE_0__.resolveComponent)("CakeVariant")
  const _component_Twitter = (0,vue__WEBPACK_IMPORTED_MODULE_0__.resolveComponent)("Twitter")
  const _component_CloudOutline = (0,vue__WEBPACK_IMPORTED_MODULE_0__.resolveComponent)("CloudOutline")
  const _component_Mastodon = (0,vue__WEBPACK_IMPORTED_MODULE_0__.resolveComponent)("Mastodon")

  return ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)("div", {
    class: (0,vue__WEBPACK_IMPORTED_MODULE_0__.normalizeClass)(["person-item", [`layout-${$props.layout}`, $options.itemBackgroundClass]])
  }, [
    (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)(" Avatar with status indicator "),
    ($options.showField('avatar'))
      ? ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)("div", _hoisted_1, [
          (0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_NcAvatar, {
            user: $props.user.uid,
            "display-name": $props.user.displayName,
            size: $options.avatarSize,
            "show-user-status": !!$props.user.status,
            "show-user-status-compact": $props.layout === 'list'
          }, null, 8 /* PROPS */, ["user", "display-name", "size", "show-user-status", "show-user-status-compact"])
        ]))
      : (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)("v-if", true),
    (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)(" Content "),
    (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("div", _hoisted_2, [
      (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)(" Name and Pronouns "),
      ($options.showField('displayName'))
        ? ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)("h4", _hoisted_3, [
            (0,vue__WEBPACK_IMPORTED_MODULE_0__.createTextVNode)((0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)($props.user.displayName) + " ", 1 /* TEXT */),
            ($options.showField('pronouns') && $props.user.pronouns)
              ? ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)("span", _hoisted_4, "(" + (0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)($props.user.pronouns) + ")", 1 /* TEXT */))
              : (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)("v-if", true)
          ]))
        : (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)("v-if", true),
      (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)(" Role (official job title) "),
      ($options.showRole && $props.user.role)
        ? ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)("p", _hoisted_5, (0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)($props.user.role), 1 /* TEXT */))
        : (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)("v-if", true),
      (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)(" Headline (personal tagline) "),
      ($options.showHeadline && $props.user.headline && $props.user.headline !== $props.user.role)
        ? ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)("p", _hoisted_6, (0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)($props.user.headline), 1 /* TEXT */))
        : (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)("v-if", true),
      (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)(" Department/Organisation "),
      ($options.showField('department') && ($props.user.organisation || $props.user.department))
        ? ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)("p", _hoisted_7, (0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)($props.user.organisation || $props.user.department), 1 /* TEXT */))
        : (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)("v-if", true),
      (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)(" Contact info "),
      ($options.hasContactInfo)
        ? ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)("div", _hoisted_8, [
            ($options.showField('email') && $props.user.email)
              ? ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)("a", {
                  key: 0,
                  href: `mailto:${$props.user.email}`,
                  class: "person-contact-item"
                }, [
                  (0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_EmailOutline, { size: 16 }),
                  (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("span", null, (0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)($props.user.email), 1 /* TEXT */)
                ], 8 /* PROPS */, _hoisted_9))
              : (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)("v-if", true),
            ($options.showField('phone') && $props.user.phone)
              ? ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)("a", {
                  key: 1,
                  href: `tel:${$props.user.phone}`,
                  class: "person-contact-item"
                }, [
                  (0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_Phone, { size: 16 }),
                  (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("span", null, (0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)($props.user.phone), 1 /* TEXT */)
                ], 8 /* PROPS */, _hoisted_10))
              : (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)("v-if", true),
            ($options.showField('address') && $props.user.address)
              ? ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)("span", _hoisted_11, [
                  (0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_MapMarker, { size: 16 }),
                  (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("span", null, (0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)($props.user.address), 1 /* TEXT */)
                ]))
              : (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)("v-if", true),
            ($options.showField('website') && $props.user.website)
              ? ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)("a", {
                  key: 3,
                  href: $props.user.website,
                  target: "_blank",
                  rel: "noopener noreferrer",
                  class: "person-contact-item"
                }, [
                  (0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_Web, { size: 16 }),
                  (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("span", null, (0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)($options.formatWebsite($props.user.website)), 1 /* TEXT */)
                ], 8 /* PROPS */, _hoisted_12))
              : (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)("v-if", true),
            ($options.showField('birthdate') && $props.user.birthdate)
              ? ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)("span", _hoisted_13, [
                  (0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_CakeVariant, { size: 16 }),
                  (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("span", null, (0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)($options.formatBirthdate($props.user.birthdate)), 1 /* TEXT */)
                ]))
              : (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)("v-if", true)
          ]))
        : (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)("v-if", true),
      (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)(" Social links "),
      ($options.hasSocialLinks)
        ? ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)("div", _hoisted_14, [
            ($props.user.twitter)
              ? ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)("a", {
                  key: 0,
                  href: $options.getTwitterUrl($props.user.twitter),
                  target: "_blank",
                  rel: "noopener noreferrer",
                  class: "person-social-item",
                  title: $props.user.twitter
                }, [
                  (0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_Twitter, { size: 18 })
                ], 8 /* PROPS */, _hoisted_15))
              : (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)("v-if", true),
            ($props.user.bluesky)
              ? ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)("a", {
                  key: 1,
                  href: $options.getBlueskyUrl($props.user.bluesky),
                  target: "_blank",
                  rel: "noopener noreferrer",
                  class: "person-social-item",
                  title: $props.user.bluesky
                }, [
                  (0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_CloudOutline, { size: 18 })
                ], 8 /* PROPS */, _hoisted_16))
              : (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)("v-if", true),
            ($props.user.fediverse)
              ? ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)("a", {
                  key: 2,
                  href: $options.getFediverseUrl($props.user.fediverse),
                  target: "_blank",
                  rel: "noopener noreferrer",
                  class: "person-social-item",
                  title: $props.user.fediverse
                }, [
                  (0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_Mastodon, { size: 18 })
                ], 8 /* PROPS */, _hoisted_17))
              : (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)("v-if", true)
          ]))
        : (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)("v-if", true),
      (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)(" Biography (only in card layout) "),
      ($props.layout === 'card' && $options.showField('biography') && $props.user.biography)
        ? ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)("p", _hoisted_18, (0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)($options.truncateBio($props.user.biography)), 1 /* TEXT */))
        : (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)("v-if", true),
      (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)(" Custom fields from LDAP/OIDC "),
      ($options.showField('customFields') && $options.customFields.length > 0)
        ? ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)("div", _hoisted_19, [
            ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(true), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)(vue__WEBPACK_IMPORTED_MODULE_0__.Fragment, null, (0,vue__WEBPACK_IMPORTED_MODULE_0__.renderList)($options.customFields, (field) => {
              return ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)("div", {
                key: field.key,
                class: "person-custom-field"
              }, [
                (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("span", _hoisted_20, (0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)(field.label) + ":", 1 /* TEXT */),
                (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("span", _hoisted_21, (0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)(field.value), 1 /* TEXT */)
              ]))
            }), 128 /* KEYED_FRAGMENT */))
          ]))
        : (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)("v-if", true)
    ])
  ], 2 /* CLASS */))
}

/***/ }

}]);
//# sourceMappingURL=intravox-src_components_PeopleWidget_vue.ae66e9de.js.map