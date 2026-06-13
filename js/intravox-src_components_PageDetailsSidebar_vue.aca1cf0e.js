"use strict";
(self["webpackChunknextcloud_intravox"] = self["webpackChunknextcloud_intravox"] || []).push([["src_components_PageDetailsSidebar_vue"],{

/***/ "./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/dist/stylePostLoader.js!./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/PageDetailsSidebar.vue?vue&type=style&index=0&id=df855e4e&scoped=true&lang=css"
/*!*******************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/dist/stylePostLoader.js!./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/PageDetailsSidebar.vue?vue&type=style&index=0&id=df855e4e&scoped=true&lang=css ***!
  \*******************************************************************************************************************************************************************************************************************************************************************/
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
/* NcAppSidebar handles its own styling */
.loading[data-v-df855e4e],
.empty-state[data-v-df855e4e] {
  text-align: center;
  padding: 20px;
  color: var(--color-text-maxcontrast);
}
.empty-state .hint[data-v-df855e4e] {
  font-size: 12px;
  margin-top: 8px;
}
.error-message[data-v-df855e4e] {
  padding: 12px;
  background: var(--color-error);
  color: white;
  border-radius: var(--border-radius);
  margin-bottom: 12px;
}
.version-list[data-v-df855e4e] {
  display: flex;
  flex-direction: column;
  gap: 12px;
}
.version-item[data-v-df855e4e] {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 12px;
  background: var(--color-background-hover);
  border-radius: var(--border-radius);
  transition: background 0.2s, border-color 0.2s;
  border: 2px solid transparent;
  cursor: pointer;
}
.version-item[data-v-df855e4e]:hover {
  background: var(--color-background-dark);
}
.version-item--selected[data-v-df855e4e] {
  background: var(--color-primary-element-light);
  border-color: var(--color-primary-element);
}
.version-info[data-v-df855e4e] {
  flex: 1;
  display: flex;
  flex-direction: column;
  gap: 6px;
}
.version-header[data-v-df855e4e] {
  display: flex;
  align-items: center;
  gap: 8px;
  flex-wrap: wrap;
}
.version-name[data-v-df855e4e] {
  font-weight: 600;
  font-size: 14px;
}
.version-author[data-v-df855e4e] {
  font-size: 12px;
  color: var(--color-text-maxcontrast);
}
.version-badge[data-v-df855e4e] {
  font-size: 11px;
  font-weight: 600;
  padding: 3px 10px;
  border-radius: var(--border-radius-pill, 20px);
  text-transform: uppercase;
  letter-spacing: 0.5px;
}
.version-badge--current[data-v-df855e4e] {
  background-color: var(--color-primary-element);
  color: var(--color-primary-element-text, white);
}

/* Current version item styling */
.version-item--current[data-v-df855e4e] {
  background: var(--color-primary-element-light);
  border-color: var(--color-primary-element);
}
.version-item--current[data-v-df855e4e]:hover {
  background: var(--color-primary-element-light);
}

/* Version history label */
.version-history-label[data-v-df855e4e] {
  font-size: 12px;
  font-weight: 600;
  color: var(--color-text-maxcontrast);
  text-transform: uppercase;
  letter-spacing: 0.5px;
  padding: 12px 0 8px 0;
  margin-top: 4px;
  border-top: 1px solid var(--color-border);
}
.version-details[data-v-df855e4e] {
  font-size: 12px;
  color: var(--color-text-maxcontrast);
}
.version-actions[data-v-df855e4e] {
  display: flex;
  gap: 4px;
  align-items: center;
}

/* Versions container */
.versions-container[data-v-df855e4e] {
  min-height: 200px;
  padding: 8px;
}

/* Metadata container */
.metadata-container[data-v-df855e4e] {
  padding: 12px;
}
.metadata-row[data-v-df855e4e] {
  display: flex;
  flex-direction: column;
  gap: 4px;
  padding: 12px 0;
  border-bottom: 1px solid var(--color-border);
}
.metadata-row[data-v-df855e4e]:last-child {
  border-bottom: none;
}
.metadata-label[data-v-df855e4e] {
  font-size: 12px;
  font-weight: 600;
  color: var(--color-text-maxcontrast);
}
.metadata-value[data-v-df855e4e] {
  font-size: 14px;
  color: var(--color-main-text);
  word-break: break-word;
}
.metadata-hint[data-v-df855e4e] {
  display: block;
  font-size: 12px;
  color: var(--color-text-maxcontrast);
  margin-top: 2px;
}
.metadata-path[data-v-df855e4e] {
  font-size: 12px;
  color: var(--color-text-maxcontrast);
}
.folder-link[data-v-df855e4e] {
  color: var(--color-primary-element);
  text-decoration: none;
  border-bottom: 1px dotted var(--color-primary-element);
  transition: border-bottom 0.2s;
}
.folder-link[data-v-df855e4e]:hover {
  border-bottom: 1px solid var(--color-primary-element);
}
.metadata-monospace[data-v-df855e4e] {
  font-family: monospace;
  font-size: 12px;
  background: var(--color-background-hover);
  padding: 4px 8px;
  border-radius: var(--border-radius);
}
.page-type-badge[data-v-df855e4e] {
  display: inline-block;
  padding: 4px 12px;
  border-radius: var(--border-radius-large);
  font-size: 12px;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}
.page-type-department[data-v-df855e4e] {
  background: #10b981;
  color: white;
}
.page-type-container[data-v-df855e4e] {
  background: #3b82f6;
  color: white;
}
.page-type-page[data-v-df855e4e] {
  background: var(--color-background-dark);
  color: var(--color-main-text);
}
.metadata-editable[data-v-df855e4e] {
  display: flex;
  flex-direction: column;
  gap: 8px;
}
.metadata-text[data-v-df855e4e] {
  padding: 4px;
  border-radius: var(--border-radius);
  transition: background 0.2s;
}
.metadata-text--editable[data-v-df855e4e] {
  cursor: pointer;
}
.metadata-text--editable[data-v-df855e4e]:hover {
  background: var(--color-background-hover);
}
.title-input[data-v-df855e4e] {
  width: 100%;
  padding: 8px;
  border: 1px solid var(--color-border);
  border-radius: var(--border-radius);
  font-size: 14px;
  background: var(--color-main-background);
  color: var(--color-main-text);
}
.title-input[data-v-df855e4e]:focus {
  outline: none;
  border-color: var(--color-primary-element);
}
.metadata-editable[data-v-df855e4e] .button-vue {
  margin-right: 8px;
}

/* MetaVox container */
.metavox-container[data-v-df855e4e] {
  padding: 12px;
  min-height: 200px;
}

/* Note: the NcAppSidebarTabs double-underline workaround lives in
 * css/main.css (loaded globally via Util::addStyle) — scoped CSS in a Vue
 * SFC can't reach the third-party data-v attribute selector. */
`, "",{"version":3,"sources":["webpack://./src/components/PageDetailsSidebar.vue"],"names":[],"mappings":";AAmwBA,yCAAyC;AAEzC;;EAEE,kBAAkB;EAClB,aAAa;EACb,oCAAoC;AACtC;AAEA;EACE,eAAe;EACf,eAAe;AACjB;AAEA;EACE,aAAa;EACb,8BAA8B;EAC9B,YAAY;EACZ,mCAAmC;EACnC,mBAAmB;AACrB;AAEA;EACE,aAAa;EACb,sBAAsB;EACtB,SAAS;AACX;AAEA;EACE,aAAa;EACb,8BAA8B;EAC9B,mBAAmB;EACnB,aAAa;EACb,yCAAyC;EACzC,mCAAmC;EACnC,8CAA8C;EAC9C,6BAA6B;EAC7B,eAAe;AACjB;AAEA;EACE,wCAAwC;AAC1C;AAEA;EACE,8CAA8C;EAC9C,0CAA0C;AAC5C;AAEA;EACE,OAAO;EACP,aAAa;EACb,sBAAsB;EACtB,QAAQ;AACV;AAEA;EACE,aAAa;EACb,mBAAmB;EACnB,QAAQ;EACR,eAAe;AACjB;AAEA;EACE,gBAAgB;EAChB,eAAe;AACjB;AAEA;EACE,eAAe;EACf,oCAAoC;AACtC;AAEA;EACE,eAAe;EACf,gBAAgB;EAChB,iBAAiB;EACjB,8CAA8C;EAC9C,yBAAyB;EACzB,qBAAqB;AACvB;AAEA;EACE,8CAA8C;EAC9C,+CAA+C;AACjD;;AAEA,iCAAiC;AACjC;EACE,8CAA8C;EAC9C,0CAA0C;AAC5C;AAEA;EACE,8CAA8C;AAChD;;AAEA,0BAA0B;AAC1B;EACE,eAAe;EACf,gBAAgB;EAChB,oCAAoC;EACpC,yBAAyB;EACzB,qBAAqB;EACrB,qBAAqB;EACrB,eAAe;EACf,yCAAyC;AAC3C;AAEA;EACE,eAAe;EACf,oCAAoC;AACtC;AAEA;EACE,aAAa;EACb,QAAQ;EACR,mBAAmB;AACrB;;AAEA,uBAAuB;AACvB;EACE,iBAAiB;EACjB,YAAY;AACd;;AAEA,uBAAuB;AACvB;EACE,aAAa;AACf;AAEA;EACE,aAAa;EACb,sBAAsB;EACtB,QAAQ;EACR,eAAe;EACf,4CAA4C;AAC9C;AAEA;EACE,mBAAmB;AACrB;AAEA;EACE,eAAe;EACf,gBAAgB;EAChB,oCAAoC;AACtC;AAEA;EACE,eAAe;EACf,6BAA6B;EAC7B,sBAAsB;AACxB;AAEA;EACE,cAAc;EACd,eAAe;EACf,oCAAoC;EACpC,eAAe;AACjB;AAEA;EACE,eAAe;EACf,oCAAoC;AACtC;AAEA;EACE,mCAAmC;EACnC,qBAAqB;EACrB,sDAAsD;EACtD,8BAA8B;AAChC;AAEA;EACE,qDAAqD;AACvD;AAEA;EACE,sBAAsB;EACtB,eAAe;EACf,yCAAyC;EACzC,gBAAgB;EAChB,mCAAmC;AACrC;AAEA;EACE,qBAAqB;EACrB,iBAAiB;EACjB,yCAAyC;EACzC,eAAe;EACf,gBAAgB;EAChB,yBAAyB;EACzB,qBAAqB;AACvB;AAEA;EACE,mBAAmB;EACnB,YAAY;AACd;AAEA;EACE,mBAAmB;EACnB,YAAY;AACd;AAEA;EACE,wCAAwC;EACxC,6BAA6B;AAC/B;AAEA;EACE,aAAa;EACb,sBAAsB;EACtB,QAAQ;AACV;AAEA;EACE,YAAY;EACZ,mCAAmC;EACnC,2BAA2B;AAC7B;AAEA;EACE,eAAe;AACjB;AAEA;EACE,yCAAyC;AAC3C;AAEA;EACE,WAAW;EACX,YAAY;EACZ,qCAAqC;EACrC,mCAAmC;EACnC,eAAe;EACf,wCAAwC;EACxC,6BAA6B;AAC/B;AAEA;EACE,aAAa;EACb,0CAA0C;AAC5C;AAEA;EACE,iBAAiB;AACnB;;AAEA,sBAAsB;AACtB;EACE,aAAa;EACb,iBAAiB;AACnB;;AAEA;;+DAE+D","sourcesContent":["<template>\r\n  <NcAppSidebar\r\n    v-if=\"isOpen\"\r\n    :name=\"pageName\"\r\n    :subtitle=\"pageSubtitle\"\r\n    :active.sync=\"activeTab\"\r\n    @update:active=\"onTabChange\"\r\n    @close=\"handleClose\"\r\n  >\r\n    <!-- Details Tab (Page Properties/File Info) -->\r\n    <NcAppSidebarTab\r\n      id=\"details-tab\"\r\n      :name=\"t('Details')\"\r\n      :order=\"1\"\r\n    >\r\n      <template #icon>\r\n        <InformationOutline :size=\"20\" />\r\n      </template>\r\n\r\n      <div v-if=\"loadingMetadata\" class=\"loading\">\r\n        {{ t('Loading properties...') }}\r\n      </div>\r\n\r\n      <div v-else-if=\"metadataError\" class=\"error-message\">\r\n        {{ metadataError }}\r\n      </div>\r\n\r\n      <div v-else-if=\"metadata\" class=\"metadata-container\">\r\n        <!-- Editable Title -->\r\n        <div class=\"metadata-row\">\r\n          <label class=\"metadata-label\">{{ t('Name') }}</label>\r\n          <div class=\"metadata-value metadata-editable\">\r\n            <input\r\n              v-if=\"editingTitle\"\r\n              v-model=\"editableTitle\"\r\n              type=\"text\"\r\n              class=\"title-input\"\r\n              @keydown.enter=\"saveTitle\"\r\n              @keydown.esc=\"cancelTitleEdit\"\r\n            />\r\n            <span v-else class=\"metadata-text\" :class=\"{ 'metadata-text--editable': canEditTitle }\" @click=\"startTitleEdit\">\r\n              {{ metadata.title || t('Untitled') }}\r\n            </span>\r\n            <NcButton\r\n              v-if=\"editingTitle\"\r\n              type=\"primary\"\r\n              @click=\"saveTitle\"\r\n              :disabled=\"savingTitle\"\r\n              size=\"small\"\r\n            >\r\n              {{ t('Save') }}\r\n            </NcButton>\r\n            <NcButton\r\n              v-if=\"editingTitle\"\r\n              type=\"secondary\"\r\n              @click=\"cancelTitleEdit\"\r\n              size=\"small\"\r\n            >\r\n              {{ t('Cancel') }}\r\n            </NcButton>\r\n          </div>\r\n        </div>\r\n\r\n        <!-- Modified -->\r\n        <div class=\"metadata-row\">\r\n          <label class=\"metadata-label\">{{ t('Modified') }}</label>\r\n          <div class=\"metadata-value\">\r\n            {{ metadata.modifiedRelative }}\r\n            <span class=\"metadata-hint\">{{ metadata.modifiedFormatted }}</span>\r\n          </div>\r\n        </div>\r\n\r\n        <!-- Path -->\r\n        <div class=\"metadata-row\">\r\n          <label class=\"metadata-label\">{{ t('Location') }}</label>\r\n          <div class=\"metadata-value metadata-path\">\r\n            <a :href=\"getFolderUrl(metadata.path)\" target=\"_blank\" rel=\"noopener noreferrer\" class=\"folder-link\">\r\n              {{ getDisplayPath(metadata.path) }}\r\n            </a>\r\n          </div>\r\n        </div>\r\n\r\n        <!-- Page Type -->\r\n        <div v-if=\"metadata.type\" class=\"metadata-row\">\r\n          <label class=\"metadata-label\">{{ t('Type') }}</label>\r\n          <div class=\"metadata-value\">\r\n            <span class=\"page-type-badge\" :class=\"`page-type-${metadata.type}`\">\r\n              {{ getPageTypeLabel(metadata.type) }}\r\n            </span>\r\n            <span v-if=\"metadata.hasChildren\" class=\"metadata-hint\">\r\n              {{ t('(has child pages)') }}\r\n            </span>\r\n          </div>\r\n        </div>\r\n\r\n        <!-- Unique ID (for sharing) -->\r\n        <div v-if=\"metadata.uniqueId\" class=\"metadata-row\">\r\n          <label class=\"metadata-label\">{{ t('UniqueId') }}</label>\r\n          <div class=\"metadata-value metadata-monospace\">{{ metadata.uniqueId }}</div>\r\n        </div>\r\n      </div>\r\n    </NcAppSidebarTab>\r\n\r\n    <!-- MetaVox Tab (only if MetaVox app is installed) -->\r\n    <NcAppSidebarTab\r\n      v-if=\"metaVoxInstalled\"\r\n      id=\"metavox-tab\"\r\n      :name=\"t('MetaVox')\"\r\n      :order=\"2\"\r\n    >\r\n      <template #icon>\r\n        <MetaVoxIcon :size=\"20\" />\r\n      </template>\r\n      <div ref=\"metavoxContainer\" class=\"metavox-container\">\r\n        <div v-if=\"loadingMetaVox\" class=\"loading\">\r\n          {{ t('Loading MetaVox...') }}\r\n        </div>\r\n        <div v-else-if=\"!metaVoxInstalled\" class=\"empty-state\">\r\n          <p>{{ t('MetaVox app is not installed') }}</p>\r\n        </div>\r\n      </div>\r\n    </NcAppSidebarTab>\r\n\r\n    <!-- Versions Tab - Uses IntraVox versions API (leverages Nextcloud/GroupFolders versioning) -->\r\n    <NcAppSidebarTab\r\n      id=\"versions-tab\"\r\n      :name=\"t('Versions')\"\r\n      :order=\"3\"\r\n    >\r\n      <template #icon>\r\n        <History :size=\"20\" />\r\n      </template>\r\n\r\n      <div class=\"versions-container\">\r\n        <div v-if=\"loadingVersions\" class=\"loading\">\r\n          {{ t('Loading versions...') }}\r\n        </div>\r\n\r\n        <div v-else-if=\"versionError\" class=\"error-message\">\r\n          {{ versionError }}\r\n        </div>\r\n\r\n        <div v-else-if=\"versions.length === 0\" class=\"empty-state\">\r\n          <p>{{ t('No versions available') }}</p>\r\n          <p class=\"hint\">{{ t('Versions are created automatically when you save changes') }}</p>\r\n        </div>\r\n\r\n        <div v-else class=\"version-list\">\r\n          <!-- Current version (the actual file, not from history) - like Nextcloud Files app -->\r\n          <div\r\n            class=\"version-item version-item--current\"\r\n            :class=\"{ 'version-item--selected': selectedVersion === null }\"\r\n            @click=\"selectCurrentVersion\"\r\n          >\r\n            <div class=\"version-info\">\r\n              <div class=\"version-header\">\r\n                <span class=\"version-name\">{{ t('Current version') }}</span>\r\n                <span v-if=\"currentVersionAuthor\" class=\"version-author\">{{ currentVersionAuthor }}</span>\r\n              </div>\r\n              <div class=\"version-details\">\r\n                {{ currentVersionDate }}<span v-if=\"currentVersionSize\"> · {{ currentVersionSize }}</span>\r\n              </div>\r\n            </div>\r\n          </div>\r\n\r\n          <!-- Version history -->\r\n          <div class=\"version-history-label\">\r\n            {{ t('Version history') }}\r\n          </div>\r\n\r\n          <div\r\n            v-for=\"version in versions\"\r\n            :key=\"version.timestamp\"\r\n            class=\"version-item\"\r\n            :class=\"{ 'version-item--selected': selectedVersion?.timestamp === version.timestamp }\"\r\n            @click=\"selectVersion(version)\"\r\n          >\r\n            <div class=\"version-info\">\r\n              <div class=\"version-header\">\r\n                <span class=\"version-name\">{{ version.label || t('Version') }}</span>\r\n                <span v-if=\"version.author\" class=\"version-author\">{{ version.author }}</span>\r\n              </div>\r\n              <div class=\"version-details\">\r\n                {{ version.relativeTime || version.formattedDate }} · {{ formatBytes(version.size) }}\r\n              </div>\r\n            </div>\r\n            <div class=\"version-actions\">\r\n              <NcButton\r\n                type=\"tertiary\"\r\n                :aria-label=\"t('Restore this version')\"\r\n                :title=\"t('Restore this version')\"\r\n                @click.stop=\"confirmRestoreVersion(version.timestamp)\"\r\n              >\r\n                <template #icon>\r\n                  <Restore :size=\"20\" />\r\n                </template>\r\n              </NcButton>\r\n            </div>\r\n          </div>\r\n        </div>\r\n      </div>\r\n    </NcAppSidebarTab>\r\n  </NcAppSidebar>\r\n\r\n  <!-- Restore Version Confirmation Dialog -->\r\n  <NcDialog\r\n    v-if=\"showRestoreDialog\"\r\n    :name=\"t('Restore Version')\"\r\n    :message=\"t('A backup of the current version will be created before restoring. Do you want to continue?')\"\r\n    :buttons=\"[\r\n      {\r\n        label: t('Cancel'),\r\n        callback: cancelRestore\r\n      },\r\n      {\r\n        label: t('Restore'),\r\n        type: 'primary',\r\n        callback: restoreVersion\r\n      }\r\n    ]\"\r\n    @close=\"cancelRestore\"\r\n  />\r\n\r\n</template>\r\n\r\n<script>\r\nimport { translate as t } from '@nextcloud/l10n';\r\nimport { generateUrl } from '@nextcloud/router';\r\nimport axios from '@nextcloud/axios';\r\nimport { showError, showSuccess } from '@nextcloud/dialogs';\r\nimport { NcAppSidebar, NcAppSidebarTab, NcButton, NcDialog } from '@nextcloud/vue';\r\nimport History from 'vue-material-design-icons/History.vue';\r\nimport InformationOutline from 'vue-material-design-icons/InformationOutline.vue';\r\nimport Restore from 'vue-material-design-icons/Restore.vue';\r\nimport MetaVoxIcon from './icons/MetaVoxIcon.vue';\r\n\r\nexport default {\r\n  name: 'PageDetailsSidebar',\r\n  components: {\r\n    NcAppSidebar,\r\n    NcAppSidebarTab,\r\n    NcButton,\r\n    NcDialog,\r\n    History,\r\n    InformationOutline,\r\n    MetaVoxIcon,\r\n    Restore\r\n  },\r\n  props: {\r\n    isOpen: {\r\n      type: Boolean,\r\n      default: false\r\n    },\r\n    pageId: {\r\n      type: String,\r\n      required: true\r\n    },\r\n    pageName: {\r\n      type: String,\r\n      default: ''\r\n    },\r\n    initialTab: {\r\n      type: String,\r\n      default: 'details-tab'\r\n    }\r\n  },\r\n  emits: ['close', 'version-restored', 'version-selected'],\r\n  data() {\r\n    return {\r\n      activeTab: 'details-tab',\r\n      versions: [],\r\n      currentVersion: null, // Current file metadata from API\r\n      loadingVersions: false,\r\n      versionError: null,\r\n      restoringVersion: false,\r\n      showRestoreDialog: false,\r\n      versionToRestore: null,\r\n      metadata: null,\r\n      loadingMetadata: false,\r\n      metadataError: null,\r\n      editingTitle: false,\r\n      editableTitle: '',\r\n      savingTitle: false,\r\n      metaVoxInstalled: false,\r\n      loadingMetaVox: false,\r\n      selectedVersion: null,\r\n      editingLabel: null,\r\n      editableLabel: '',\r\n      isRestoring: false, // Flag to prevent auto-select during restore\r\n      versionsLoaded: false // Flag for lazy loading\r\n    };\r\n  },\r\n  computed: {\r\n    pageSubtitle() {\r\n      return this.t('Page details and history');\r\n    },\r\n    canEditTitle() {\r\n      // Check write permission using Nextcloud's permissions\r\n      return this.metadata?.permissions?.canWrite ?? this.metadata?.canEdit ?? false;\r\n    },\r\n    currentVersionDate() {\r\n      // Use the relativeTime from currentVersion API response (like Files app)\r\n      if (this.currentVersion?.relativeTime) {\r\n        return this.currentVersion.relativeTime;\r\n      }\r\n      // Fallback to metadata if versions not loaded yet\r\n      if (this.metadata?.modifiedRelative) {\r\n        return this.metadata.modifiedRelative;\r\n      }\r\n      return this.t('Now');\r\n    },\r\n    currentVersionSize() {\r\n      // Size from currentVersion API response\r\n      if (this.currentVersion?.size) {\r\n        return this.formatBytes(this.currentVersion.size);\r\n      }\r\n      return null;\r\n    },\r\n    currentVersionAuthor() {\r\n      // Author from currentVersion API response\r\n      return this.currentVersion?.author || null;\r\n    }\r\n  },\r\n  watch: {\r\n    isOpen(newValue, oldValue) {\r\n      if (newValue && !oldValue) {\r\n        // Sidebar just opened - reset state and apply initialTab\r\n        this.versionsLoaded = false;\r\n        this.activeTab = this.initialTab || 'details-tab';\r\n        // Only load metadata (for details tab - default)\r\n        this.loadMetadata().catch(() => {});\r\n        this.checkMetaVoxInstalled();\r\n      }\r\n    },\r\n    pageId() {\r\n      // Note: Sidebar is closed on navigation (in App.vue), so this is a fallback\r\n      if (this.isOpen) {\r\n        this.versionsLoaded = false;\r\n        this.selectedVersion = null;\r\n        this.versions = [];\r\n        this.currentVersion = null;\r\n        this.metadata = null;\r\n      }\r\n    },\r\n    initialTab(newTab) {\r\n      // Only apply initialTab if sidebar is currently closed\r\n      // When sidebar is open, user controls the active tab\r\n      if (!this.isOpen && newTab) {\r\n        this.activeTab = newTab;\r\n      }\r\n    },\r\n    activeTab(newTab) {\r\n      // Load data when tab is activated (lazy loading)\r\n      if (newTab === 'versions-tab' && !this.versionsLoaded) {\r\n        this.loadVersions();\r\n        this.versionsLoaded = true;\r\n      } else if (newTab === 'details-tab' && !this.metadata) {\r\n        this.loadMetadata().catch(() => {});\r\n      } else if (newTab === 'metavox-tab' && this.metaVoxInstalled) {\r\n        this.$nextTick(() => {\r\n          this.dispatchMetaVoxUpdate();\r\n        });\r\n      }\r\n    },\r\n    versions(newVersions) {\r\n      // Auto-select first version if versions tab is active and versions just loaded\r\n      // BUT skip if we're in the middle of a restore operation OR already have a selection\r\n      if (this.activeTab === 'versions-tab' && newVersions.length > 0 && !this.isRestoring && !this.selectedVersion) {\r\n        this.autoSelectFirstVersion();\r\n      }\r\n    },\r\n    metaVoxInstalled(newValue) {\r\n      // If MetaVox just became available and the tab is already active, dispatch update\r\n      if (newValue && this.activeTab === 'metavox-tab') {\r\n        this.$nextTick(() => {\r\n          this.dispatchMetaVoxUpdate();\r\n        });\r\n      }\r\n    }\r\n  },\r\n  mounted() {\r\n    if (this.isOpen) {\r\n      // Only load metadata at mount (details tab is default)\r\n      this.loadMetadata().catch(() => {});\r\n      this.checkMetaVoxInstalled();\r\n    }\r\n\r\n    // Listen for page save events to refresh versions\r\n    window.addEventListener('intravox:page:saved', this.handlePageSaved);\r\n    // Listen for edit mode started to reset to current version\r\n    window.addEventListener('intravox:edit:started', this.handleEditStarted);\r\n  },\r\n  beforeUnmount() {\r\n    // Clean up event listeners\r\n    window.removeEventListener('intravox:page:saved', this.handlePageSaved);\r\n    window.removeEventListener('intravox:edit:started', this.handleEditStarted);\r\n  },\r\n  methods: {\r\n    t(key, vars = {}) {\r\n      return t('intravox', key, vars);\r\n    },\r\n    handleClose() {\r\n      this.$emit('close');\r\n    },\r\n    getDisplayPath(path) {\r\n      if (!path) {\r\n        return '';\r\n      }\r\n\r\n      // Extract folder path (remove filename)\r\n      const lastSlash = path.lastIndexOf('/');\r\n      const folderPath = lastSlash > 0 ? path.substring(0, lastSlash) : path;\r\n\r\n      // Get the groupfolder name from metadata\r\n      const groupfolderName = this.metadata?.mountPoint || 'IntraVox';\r\n\r\n      // Format 1: Direct groupfolder access (admin/internal)\r\n      // Path format: /__groupfolders/4/files/en/mission\r\n      // Display format: IntraVox/en/mission\r\n      if (folderPath.startsWith('/__groupfolders/')) {\r\n        // Extract the part after /__groupfolders/X/\r\n        const pathAfterGroupfolder = folderPath.replace(/^\\/__groupfolders\\/\\d+\\//, '');\r\n\r\n        // Remove 'files/' from the beginning if present (internal path structure)\r\n        const cleanPath = pathAfterGroupfolder.replace(/^files\\//, '');\r\n\r\n        // Prepend groupfolder name\r\n        return cleanPath ? `${groupfolderName}/${cleanPath}` : groupfolderName;\r\n      }\r\n\r\n      // Format 2: User-mounted groupfolder view (normal users)\r\n      // Path format: /user@email.com/files/IntraVox/en/mission\r\n      // Display format: IntraVox/en/mission\r\n      const userMountPattern = new RegExp(`^/[^/]+/files/${groupfolderName}/(.*)$`);\r\n      const userMatch = folderPath.match(userMountPattern);\r\n      if (userMatch) {\r\n        const relativePath = userMatch[1];\r\n        return relativePath ? `${groupfolderName}/${relativePath}` : groupfolderName;\r\n      }\r\n\r\n      return folderPath;\r\n    },\r\n    getFolderUrl(path) {\r\n      if (!path) {\r\n        return '#';\r\n      }\r\n\r\n      // Extract folder path (remove filename)\r\n      const lastSlash = path.lastIndexOf('/');\r\n      const folderPath = lastSlash > 0 ? path.substring(0, lastSlash) : path;\r\n\r\n      // Use the groupfolder name from metadata if available, otherwise default to 'IntraVox'\r\n      const groupfolderName = this.metadata?.mountPoint || 'IntraVox';\r\n\r\n      // Get the parent folder fileId from metadata\r\n      const fileId = this.metadata?.parentFolderId;\r\n\r\n      // Format 1: Direct groupfolder access (admin/internal)\r\n      // Path format: /__groupfolders/4/files/en/mission/page.json\r\n      // Target format: /apps/files/files/{parentFolderId}?dir=/IntraVox/en/mission\r\n      if (folderPath.startsWith('/__groupfolders/')) {\r\n        // Extract the part after /__groupfolders/X/\r\n        const pathAfterGroupfolder = folderPath.replace(/^\\/__groupfolders\\/\\d+\\//, '');\r\n\r\n        // Remove 'files/' from the beginning if present (internal path structure)\r\n        const cleanPath = pathAfterGroupfolder.replace(/^files\\//, '');\r\n\r\n        if (!fileId) {\r\n          return '#';\r\n        }\r\n\r\n        // Generate Files app URL with fileId and dir parameters\r\n        const filesPath = `/${groupfolderName}/${cleanPath}`;\r\n        return generateUrl('/apps/files/files/{fileId}?dir={dir}', {\r\n          fileId: fileId,\r\n          dir: filesPath\r\n        });\r\n      }\r\n\r\n      // Format 2: User-mounted groupfolder view (normal users)\r\n      // Path format: /user@email.com/files/IntraVox/en/mission\r\n      // Target format: /apps/files/files/{parentFolderId}?dir=/IntraVox/en/mission\r\n      const userMountPattern = new RegExp(`^/[^/]+/files/${groupfolderName}/(.*)$`);\r\n      const userMatch = folderPath.match(userMountPattern);\r\n      if (userMatch) {\r\n        const relativePath = userMatch[1];\r\n\r\n        if (!fileId) {\r\n          return '#';\r\n        }\r\n\r\n        const filesPath = relativePath ? `/${groupfolderName}/${relativePath}` : `/${groupfolderName}`;\r\n        return generateUrl('/apps/files/files/{fileId}?dir={dir}', {\r\n          fileId: fileId,\r\n          dir: filesPath\r\n        });\r\n      }\r\n\r\n      // Fallback for non-groupfolder paths\r\n      return generateUrl('/apps/files/?dir={dir}', { dir: folderPath });\r\n    },\r\n    onTabChange(newTabId) {\r\n      // Load versions when tab is activated (lazy loading)\r\n      if (newTabId === 'versions-tab' && !this.versionsLoaded) {\r\n        this.loadVersions();\r\n        this.versionsLoaded = true;\r\n      } else if (newTabId === 'metavox-tab' && this.metaVoxInstalled) {\r\n        this.$nextTick(() => {\r\n          this.dispatchMetaVoxUpdate();\r\n        });\r\n      }\r\n    },\r\n    async loadVersions() {\r\n      this.loadingVersions = true;\r\n      this.versionError = null;\r\n\r\n      try {\r\n        const url = generateUrl(`/apps/intravox/api/pages/${this.pageId}/versions`);\r\n        const response = await axios.get(url);\r\n\r\n        // API response structure: { currentVersion: {...}, versions: [...] }\r\n        if (response.data && typeof response.data === 'object' && 'versions' in response.data) {\r\n          this.currentVersion = response.data.currentVersion;\r\n          this.versions = response.data.versions;\r\n        } else {\r\n          // Backwards compatibility: old API returned array directly\r\n          this.versions = response.data;\r\n          this.currentVersion = null;\r\n        }\r\n      } catch (error) {\r\n        console.error('Failed to load versions:', error);\r\n        this.versionError = error.response?.data?.error || this.t('Failed to load version history');\r\n      } finally {\r\n        this.loadingVersions = false;\r\n      }\r\n    },\r\n    confirmRestoreVersion(timestamp) {\r\n      this.versionToRestore = timestamp;\r\n      this.showRestoreDialog = true;\r\n    },\r\n    cancelRestore() {\r\n      this.showRestoreDialog = false;\r\n      this.versionToRestore = null;\r\n    },\r\n    async restoreVersion() {\r\n      if (!this.versionToRestore) {\r\n        return;\r\n      }\r\n\r\n      this.restoringVersion = true;\r\n      this.isRestoring = true; // Prevent auto-select during restore\r\n      this.showRestoreDialog = false;\r\n\r\n      // Remember which version we're restoring\r\n      const restoredTimestamp = this.versionToRestore;\r\n\r\n      try {\r\n        const url = generateUrl(`/apps/intravox/api/pages/${this.pageId}/versions/${this.versionToRestore}`);\r\n        const response = await axios.post(url);\r\n\r\n        // Emit event to parent to reload the page\r\n        this.$emit('version-restored', response.data);\r\n\r\n        // Reload versions list\r\n        await this.loadVersions();\r\n\r\n        // Show the current page (which now contains the restored content)\r\n        // Don't select a historical version - that would show a preview\r\n        // instead of the actual restored page\r\n        this.$nextTick(() => {\r\n          this.selectCurrentVersion();\r\n          this.isRestoring = false;\r\n        });\r\n\r\n        this.versionToRestore = null;\r\n      } catch (error) {\r\n        console.error('Failed to restore version:', error);\r\n        showError(this.t('Failed to restore version: {error}', {\r\n          error: error.response?.data?.error || error.message\r\n        }));\r\n        this.isRestoring = false; // Reset flag on error too\r\n      } finally {\r\n        this.restoringVersion = false;\r\n      }\r\n    },\r\n    async loadMetadata() {\r\n      this.loadingMetadata = true;\r\n      this.metadataError = null;\r\n\r\n      try {\r\n        const url = generateUrl(`/apps/intravox/api/pages/${this.pageId}/metadata`);\r\n        const response = await axios.get(url);\r\n        this.metadata = response.data;\r\n      } catch (error) {\r\n        console.error('Failed to load metadata:', error);\r\n        this.metadataError = error.response?.data?.error || this.t('Failed to load properties');\r\n      } finally {\r\n        this.loadingMetadata = false;\r\n      }\r\n    },\r\n    startTitleEdit() {\r\n      // Check write permission using Nextcloud's permissions\r\n      const canWrite = this.metadata?.permissions?.canWrite ?? this.metadata?.canEdit ?? false;\r\n      if (!canWrite) {\r\n        return;\r\n      }\r\n      this.editableTitle = this.metadata.title;\r\n      this.editingTitle = true;\r\n      // Focus input after Vue updates the DOM\r\n      this.$nextTick(() => {\r\n        const input = this.$el.querySelector('.title-input');\r\n        if (input) {\r\n          input.focus();\r\n          input.select();\r\n        }\r\n      });\r\n    },\r\n    cancelTitleEdit() {\r\n      this.editingTitle = false;\r\n      this.editableTitle = '';\r\n    },\r\n    async saveTitle() {\r\n      if (!this.editableTitle.trim()) {\r\n        showError(this.t('Title cannot be empty'));\r\n        return;\r\n      }\r\n\r\n      this.savingTitle = true;\r\n\r\n      try {\r\n        const url = generateUrl(`/apps/intravox/api/pages/${this.pageId}/metadata`);\r\n        const response = await axios.put(url, {\r\n          title: this.editableTitle.trim()\r\n        });\r\n        this.metadata = response.data;\r\n        this.editingTitle = false;\r\n        showSuccess(this.t('Title updated'));\r\n      } catch (error) {\r\n        console.error('Failed to update title:', error);\r\n        showError(this.t('Failed to update title: {error}', {\r\n          error: error.response?.data?.error || error.message\r\n        }));\r\n      } finally {\r\n        this.savingTitle = false;\r\n      }\r\n    },\r\n    async checkMetaVoxInstalled() {\r\n      try {\r\n        const url = generateUrl('/apps/intravox/api/metavox/status');\r\n        const response = await axios.get(url);\r\n        this.metaVoxInstalled = response.data.installed === true;\r\n      } catch (error) {\r\n        this.metaVoxInstalled = false;\r\n      }\r\n    },\r\n    dispatchMetaVoxUpdate() {\r\n      if (!this.metaVoxInstalled) {\r\n        return;\r\n      }\r\n\r\n      if (!this.$refs.metavoxContainer) {\r\n        return;\r\n      }\r\n\r\n      // Dispatch custom event for MetaVox to listen to\r\n      const event = new CustomEvent('intravox:metavox:update', {\r\n        detail: {\r\n          pageId: this.pageId,\r\n          pageName: this.pageName,\r\n          container: this.$refs.metavoxContainer,\r\n          metadata: this.metadata\r\n        }\r\n      });\r\n      window.dispatchEvent(event);\r\n    },\r\n    formatBytes(bytes) {\r\n      if (bytes === 0) return '0 Bytes';\r\n      const k = 1024;\r\n      const sizes = ['Bytes', 'KB', 'MB', 'GB'];\r\n      const i = Math.floor(Math.log(bytes) / Math.log(k));\r\n      return Math.round(bytes / Math.pow(k, i) * 100) / 100 + ' ' + sizes[i];\r\n    },\r\n    startLabelEdit(version) {\r\n      this.editingLabel = version.timestamp;\r\n      this.editableLabel = version.label || '';\r\n    },\r\n    cancelLabelEdit() {\r\n      this.editingLabel = null;\r\n      this.editableLabel = '';\r\n    },\r\n    async saveVersionLabel(timestamp) {\r\n      if (!this.editableLabel.trim() && !this.versions.find(v => v.timestamp === timestamp)?.label) {\r\n        // No label to save and no existing label to remove\r\n        this.cancelLabelEdit();\r\n        return;\r\n      }\r\n\r\n      try {\r\n        const url = generateUrl(`/apps/intravox/api/pages/${this.pageId}/versions/${timestamp}/label`);\r\n        await axios.put(url, {\r\n          label: this.editableLabel.trim()\r\n        });\r\n\r\n        // Update local version\r\n        const version = this.versions.find(v => v.timestamp === timestamp);\r\n        if (version) {\r\n          version.label = this.editableLabel.trim() || null;\r\n        }\r\n\r\n        this.cancelLabelEdit();\r\n        showSuccess(this.t('Version label updated'));\r\n      } catch (error) {\r\n        showError(this.t('Failed to update version label: {error}', {\r\n          error: error.response?.data?.error || error.message\r\n        }));\r\n      }\r\n    },\r\n    selectCurrentVersion() {\r\n      // Clear version selection to show current page\r\n      this.selectedVersion = null;\r\n      // Emit event to clear version preview in parent\r\n      this.$emit('version-selected', {\r\n        version: null,\r\n        pageId: this.pageId\r\n      });\r\n    },\r\n    async selectVersion(version) {\r\n      this.selectedVersion = version;\r\n\r\n      // Emit event to parent component to show the version preview\r\n      this.$emit('version-selected', {\r\n        version,\r\n        pageId: this.pageId\r\n      });\r\n    },\r\n    getDepartmentMaxDepth() {\r\n      // Department pages have max depth 5\r\n      return 5;\r\n    },\r\n    getPageTypeLabel(type) {\r\n      const labels = {\r\n        'department': this.t('Department'),\r\n        'container': this.t('Container'),\r\n        'page': this.t('Page')\r\n      };\r\n      return labels[type] || type;\r\n    },\r\n    autoSelectFirstVersion() {\r\n      // Auto-select current version (not a history version)\r\n      this.$nextTick(() => {\r\n        this.selectCurrentVersion();\r\n      });\r\n    },\r\n    handlePageSaved(event) {\r\n      // Refresh versions when this page is saved (if versions were already loaded)\r\n      if (event.detail?.pageId === this.pageId && this.versionsLoaded) {\r\n        this.loadVersions();\r\n      }\r\n    },\r\n    handleEditStarted(event) {\r\n      // When edit mode starts, reset to current version selection\r\n      // User should always edit the current version, not a history version\r\n      if (event.detail?.pageId === this.pageId) {\r\n        this.selectCurrentVersion();\r\n      }\r\n    }\r\n  }\r\n};\r\n</script>\r\n\r\n<style scoped>\r\n/* NcAppSidebar handles its own styling */\r\n\r\n.loading,\r\n.empty-state {\r\n  text-align: center;\r\n  padding: 20px;\r\n  color: var(--color-text-maxcontrast);\r\n}\r\n\r\n.empty-state .hint {\r\n  font-size: 12px;\r\n  margin-top: 8px;\r\n}\r\n\r\n.error-message {\r\n  padding: 12px;\r\n  background: var(--color-error);\r\n  color: white;\r\n  border-radius: var(--border-radius);\r\n  margin-bottom: 12px;\r\n}\r\n\r\n.version-list {\r\n  display: flex;\r\n  flex-direction: column;\r\n  gap: 12px;\r\n}\r\n\r\n.version-item {\r\n  display: flex;\r\n  justify-content: space-between;\r\n  align-items: center;\r\n  padding: 12px;\r\n  background: var(--color-background-hover);\r\n  border-radius: var(--border-radius);\r\n  transition: background 0.2s, border-color 0.2s;\r\n  border: 2px solid transparent;\r\n  cursor: pointer;\r\n}\r\n\r\n.version-item:hover {\r\n  background: var(--color-background-dark);\r\n}\r\n\r\n.version-item--selected {\r\n  background: var(--color-primary-element-light);\r\n  border-color: var(--color-primary-element);\r\n}\r\n\r\n.version-info {\r\n  flex: 1;\r\n  display: flex;\r\n  flex-direction: column;\r\n  gap: 6px;\r\n}\r\n\r\n.version-header {\r\n  display: flex;\r\n  align-items: center;\r\n  gap: 8px;\r\n  flex-wrap: wrap;\r\n}\r\n\r\n.version-name {\r\n  font-weight: 600;\r\n  font-size: 14px;\r\n}\r\n\r\n.version-author {\r\n  font-size: 12px;\r\n  color: var(--color-text-maxcontrast);\r\n}\r\n\r\n.version-badge {\r\n  font-size: 11px;\r\n  font-weight: 600;\r\n  padding: 3px 10px;\r\n  border-radius: var(--border-radius-pill, 20px);\r\n  text-transform: uppercase;\r\n  letter-spacing: 0.5px;\r\n}\r\n\r\n.version-badge--current {\r\n  background-color: var(--color-primary-element);\r\n  color: var(--color-primary-element-text, white);\r\n}\r\n\r\n/* Current version item styling */\r\n.version-item--current {\r\n  background: var(--color-primary-element-light);\r\n  border-color: var(--color-primary-element);\r\n}\r\n\r\n.version-item--current:hover {\r\n  background: var(--color-primary-element-light);\r\n}\r\n\r\n/* Version history label */\r\n.version-history-label {\r\n  font-size: 12px;\r\n  font-weight: 600;\r\n  color: var(--color-text-maxcontrast);\r\n  text-transform: uppercase;\r\n  letter-spacing: 0.5px;\r\n  padding: 12px 0 8px 0;\r\n  margin-top: 4px;\r\n  border-top: 1px solid var(--color-border);\r\n}\r\n\r\n.version-details {\r\n  font-size: 12px;\r\n  color: var(--color-text-maxcontrast);\r\n}\r\n\r\n.version-actions {\r\n  display: flex;\r\n  gap: 4px;\r\n  align-items: center;\r\n}\r\n\r\n/* Versions container */\r\n.versions-container {\r\n  min-height: 200px;\r\n  padding: 8px;\r\n}\r\n\r\n/* Metadata container */\r\n.metadata-container {\r\n  padding: 12px;\r\n}\r\n\r\n.metadata-row {\r\n  display: flex;\r\n  flex-direction: column;\r\n  gap: 4px;\r\n  padding: 12px 0;\r\n  border-bottom: 1px solid var(--color-border);\r\n}\r\n\r\n.metadata-row:last-child {\r\n  border-bottom: none;\r\n}\r\n\r\n.metadata-label {\r\n  font-size: 12px;\r\n  font-weight: 600;\r\n  color: var(--color-text-maxcontrast);\r\n}\r\n\r\n.metadata-value {\r\n  font-size: 14px;\r\n  color: var(--color-main-text);\r\n  word-break: break-word;\r\n}\r\n\r\n.metadata-hint {\r\n  display: block;\r\n  font-size: 12px;\r\n  color: var(--color-text-maxcontrast);\r\n  margin-top: 2px;\r\n}\r\n\r\n.metadata-path {\r\n  font-size: 12px;\r\n  color: var(--color-text-maxcontrast);\r\n}\r\n\r\n.folder-link {\r\n  color: var(--color-primary-element);\r\n  text-decoration: none;\r\n  border-bottom: 1px dotted var(--color-primary-element);\r\n  transition: border-bottom 0.2s;\r\n}\r\n\r\n.folder-link:hover {\r\n  border-bottom: 1px solid var(--color-primary-element);\r\n}\r\n\r\n.metadata-monospace {\r\n  font-family: monospace;\r\n  font-size: 12px;\r\n  background: var(--color-background-hover);\r\n  padding: 4px 8px;\r\n  border-radius: var(--border-radius);\r\n}\r\n\r\n.page-type-badge {\r\n  display: inline-block;\r\n  padding: 4px 12px;\r\n  border-radius: var(--border-radius-large);\r\n  font-size: 12px;\r\n  font-weight: 600;\r\n  text-transform: uppercase;\r\n  letter-spacing: 0.5px;\r\n}\r\n\r\n.page-type-department {\r\n  background: #10b981;\r\n  color: white;\r\n}\r\n\r\n.page-type-container {\r\n  background: #3b82f6;\r\n  color: white;\r\n}\r\n\r\n.page-type-page {\r\n  background: var(--color-background-dark);\r\n  color: var(--color-main-text);\r\n}\r\n\r\n.metadata-editable {\r\n  display: flex;\r\n  flex-direction: column;\r\n  gap: 8px;\r\n}\r\n\r\n.metadata-text {\r\n  padding: 4px;\r\n  border-radius: var(--border-radius);\r\n  transition: background 0.2s;\r\n}\r\n\r\n.metadata-text--editable {\r\n  cursor: pointer;\r\n}\r\n\r\n.metadata-text--editable:hover {\r\n  background: var(--color-background-hover);\r\n}\r\n\r\n.title-input {\r\n  width: 100%;\r\n  padding: 8px;\r\n  border: 1px solid var(--color-border);\r\n  border-radius: var(--border-radius);\r\n  font-size: 14px;\r\n  background: var(--color-main-background);\r\n  color: var(--color-main-text);\r\n}\r\n\r\n.title-input:focus {\r\n  outline: none;\r\n  border-color: var(--color-primary-element);\r\n}\r\n\r\n.metadata-editable :deep(.button-vue) {\r\n  margin-right: 8px;\r\n}\r\n\r\n/* MetaVox container */\r\n.metavox-container {\r\n  padding: 12px;\r\n  min-height: 200px;\r\n}\r\n\r\n/* Note: the NcAppSidebarTabs double-underline workaround lives in\r\n * css/main.css (loaded globally via Util::addStyle) — scoped CSS in a Vue\r\n * SFC can't reach the third-party data-v attribute selector. */\r\n</style>\r\n"],"sourceRoot":""}]);
// Exports
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (___CSS_LOADER_EXPORT___);


/***/ },

/***/ "./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/dist/stylePostLoader.js!./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/icons/MetaVoxIcon.vue?vue&type=style&index=0&id=7ad755be&scoped=true&lang=css"
/*!******************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/dist/stylePostLoader.js!./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/icons/MetaVoxIcon.vue?vue&type=style&index=0&id=7ad755be&scoped=true&lang=css ***!
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
.metavox-icon[data-v-7ad755be] {
  display: inline-flex;
  align-self: center;
}
`, "",{"version":3,"sources":["webpack://./src/components/icons/MetaVoxIcon.vue"],"names":[],"mappings":";AA0BA;EACE,oBAAoB;EACpB,kBAAkB;AACpB","sourcesContent":["<template>\r\n  <span class=\"material-design-icon metavox-icon\">\r\n    <img :src=\"iconUrl\" :width=\"size\" :height=\"size\" alt=\"MetaVox\" />\r\n  </span>\r\n</template>\r\n\r\n<script>\r\nimport { imagePath } from '@nextcloud/router'\r\n\r\nexport default {\r\n  name: 'MetaVoxIcon',\r\n  props: {\r\n    size: {\r\n      type: Number,\r\n      default: 24\r\n    }\r\n  },\r\n  computed: {\r\n    iconUrl() {\r\n      return imagePath('metavox', 'app.svg')\r\n    }\r\n  }\r\n}\r\n</script>\r\n\r\n<style scoped>\r\n.metavox-icon {\r\n  display: inline-flex;\r\n  align-self: center;\r\n}\r\n</style>\r\n"],"sourceRoot":""}]);
// Exports
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (___CSS_LOADER_EXPORT___);


/***/ },

/***/ "./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/dist/stylePostLoader.js!./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/PageDetailsSidebar.vue?vue&type=style&index=0&id=df855e4e&scoped=true&lang=css"
/*!***********************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/dist/stylePostLoader.js!./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/PageDetailsSidebar.vue?vue&type=style&index=0&id=df855e4e&scoped=true&lang=css ***!
  \***********************************************************************************************************************************************************************************************************************************************************************************************************/
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
/* harmony import */ var _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_dist_stylePostLoader_js_node_modules_vue_loader_dist_index_js_ruleSet_1_rules_5_use_0_PageDetailsSidebar_vue_vue_type_style_index_0_id_df855e4e_scoped_true_lang_css__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! !!../../node_modules/css-loader/dist/cjs.js!../../node_modules/vue-loader/dist/stylePostLoader.js!../../node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./PageDetailsSidebar.vue?vue&type=style&index=0&id=df855e4e&scoped=true&lang=css */ "./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/dist/stylePostLoader.js!./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/PageDetailsSidebar.vue?vue&type=style&index=0&id=df855e4e&scoped=true&lang=css");

      
      
      
      
      
      
      
      
      

var options = {};

options.styleTagTransform = (_node_modules_style_loader_dist_runtime_styleTagTransform_js__WEBPACK_IMPORTED_MODULE_5___default());
options.setAttributes = (_node_modules_style_loader_dist_runtime_setAttributesWithoutAttributes_js__WEBPACK_IMPORTED_MODULE_3___default());

      options.insert = _node_modules_style_loader_dist_runtime_insertBySelector_js__WEBPACK_IMPORTED_MODULE_2___default().bind(null, "head");
    
options.domAPI = (_node_modules_style_loader_dist_runtime_styleDomAPI_js__WEBPACK_IMPORTED_MODULE_1___default());
options.insertStyleElement = (_node_modules_style_loader_dist_runtime_insertStyleElement_js__WEBPACK_IMPORTED_MODULE_4___default());

var update = _node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0___default()(_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_dist_stylePostLoader_js_node_modules_vue_loader_dist_index_js_ruleSet_1_rules_5_use_0_PageDetailsSidebar_vue_vue_type_style_index_0_id_df855e4e_scoped_true_lang_css__WEBPACK_IMPORTED_MODULE_6__["default"], options);




       /* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_dist_stylePostLoader_js_node_modules_vue_loader_dist_index_js_ruleSet_1_rules_5_use_0_PageDetailsSidebar_vue_vue_type_style_index_0_id_df855e4e_scoped_true_lang_css__WEBPACK_IMPORTED_MODULE_6__["default"] && _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_dist_stylePostLoader_js_node_modules_vue_loader_dist_index_js_ruleSet_1_rules_5_use_0_PageDetailsSidebar_vue_vue_type_style_index_0_id_df855e4e_scoped_true_lang_css__WEBPACK_IMPORTED_MODULE_6__["default"].locals ? _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_dist_stylePostLoader_js_node_modules_vue_loader_dist_index_js_ruleSet_1_rules_5_use_0_PageDetailsSidebar_vue_vue_type_style_index_0_id_df855e4e_scoped_true_lang_css__WEBPACK_IMPORTED_MODULE_6__["default"].locals : undefined);


/***/ },

/***/ "./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/dist/stylePostLoader.js!./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/icons/MetaVoxIcon.vue?vue&type=style&index=0&id=7ad755be&scoped=true&lang=css"
/*!**********************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/dist/stylePostLoader.js!./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/icons/MetaVoxIcon.vue?vue&type=style&index=0&id=7ad755be&scoped=true&lang=css ***!
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
/* harmony import */ var _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_dist_stylePostLoader_js_node_modules_vue_loader_dist_index_js_ruleSet_1_rules_5_use_0_MetaVoxIcon_vue_vue_type_style_index_0_id_7ad755be_scoped_true_lang_css__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! !!../../../node_modules/css-loader/dist/cjs.js!../../../node_modules/vue-loader/dist/stylePostLoader.js!../../../node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./MetaVoxIcon.vue?vue&type=style&index=0&id=7ad755be&scoped=true&lang=css */ "./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/dist/stylePostLoader.js!./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/icons/MetaVoxIcon.vue?vue&type=style&index=0&id=7ad755be&scoped=true&lang=css");

      
      
      
      
      
      
      
      
      

var options = {};

options.styleTagTransform = (_node_modules_style_loader_dist_runtime_styleTagTransform_js__WEBPACK_IMPORTED_MODULE_5___default());
options.setAttributes = (_node_modules_style_loader_dist_runtime_setAttributesWithoutAttributes_js__WEBPACK_IMPORTED_MODULE_3___default());

      options.insert = _node_modules_style_loader_dist_runtime_insertBySelector_js__WEBPACK_IMPORTED_MODULE_2___default().bind(null, "head");
    
options.domAPI = (_node_modules_style_loader_dist_runtime_styleDomAPI_js__WEBPACK_IMPORTED_MODULE_1___default());
options.insertStyleElement = (_node_modules_style_loader_dist_runtime_insertStyleElement_js__WEBPACK_IMPORTED_MODULE_4___default());

var update = _node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0___default()(_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_dist_stylePostLoader_js_node_modules_vue_loader_dist_index_js_ruleSet_1_rules_5_use_0_MetaVoxIcon_vue_vue_type_style_index_0_id_7ad755be_scoped_true_lang_css__WEBPACK_IMPORTED_MODULE_6__["default"], options);




       /* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_dist_stylePostLoader_js_node_modules_vue_loader_dist_index_js_ruleSet_1_rules_5_use_0_MetaVoxIcon_vue_vue_type_style_index_0_id_7ad755be_scoped_true_lang_css__WEBPACK_IMPORTED_MODULE_6__["default"] && _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_dist_stylePostLoader_js_node_modules_vue_loader_dist_index_js_ruleSet_1_rules_5_use_0_MetaVoxIcon_vue_vue_type_style_index_0_id_7ad755be_scoped_true_lang_css__WEBPACK_IMPORTED_MODULE_6__["default"].locals ? _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_dist_stylePostLoader_js_node_modules_vue_loader_dist_index_js_ruleSet_1_rules_5_use_0_MetaVoxIcon_vue_vue_type_style_index_0_id_7ad755be_scoped_true_lang_css__WEBPACK_IMPORTED_MODULE_6__["default"].locals : undefined);


/***/ },

/***/ "./src/components/PageDetailsSidebar.vue"
/*!***********************************************!*\
  !*** ./src/components/PageDetailsSidebar.vue ***!
  \***********************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _PageDetailsSidebar_vue_vue_type_template_id_df855e4e_scoped_true__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./PageDetailsSidebar.vue?vue&type=template&id=df855e4e&scoped=true */ "./src/components/PageDetailsSidebar.vue?vue&type=template&id=df855e4e&scoped=true");
/* harmony import */ var _PageDetailsSidebar_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./PageDetailsSidebar.vue?vue&type=script&lang=js */ "./src/components/PageDetailsSidebar.vue?vue&type=script&lang=js");
/* harmony import */ var _PageDetailsSidebar_vue_vue_type_style_index_0_id_df855e4e_scoped_true_lang_css__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./PageDetailsSidebar.vue?vue&type=style&index=0&id=df855e4e&scoped=true&lang=css */ "./src/components/PageDetailsSidebar.vue?vue&type=style&index=0&id=df855e4e&scoped=true&lang=css");
/* harmony import */ var _node_modules_vue_loader_dist_exportHelper_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ../../node_modules/vue-loader/dist/exportHelper.js */ "./node_modules/vue-loader/dist/exportHelper.js");




;


const __exports__ = /*#__PURE__*/(0,_node_modules_vue_loader_dist_exportHelper_js__WEBPACK_IMPORTED_MODULE_3__["default"])(_PageDetailsSidebar_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_1__["default"], [['render',_PageDetailsSidebar_vue_vue_type_template_id_df855e4e_scoped_true__WEBPACK_IMPORTED_MODULE_0__.render],['__scopeId',"data-v-df855e4e"],['__file',"src/components/PageDetailsSidebar.vue"]])
/* hot reload */
if (false) // removed by dead control flow
{}


/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (__exports__);

/***/ },

/***/ "./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/PageDetailsSidebar.vue?vue&type=script&lang=js"
/*!*******************************************************************************************************************************************!*\
  !*** ./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/PageDetailsSidebar.vue?vue&type=script&lang=js ***!
  \*******************************************************************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _nextcloud_l10n__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @nextcloud/l10n */ "./node_modules/@nextcloud/l10n/dist/index.mjs");
/* harmony import */ var _nextcloud_router__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @nextcloud/router */ "./node_modules/@nextcloud/router/dist/index.mjs");
/* harmony import */ var _nextcloud_axios__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @nextcloud/axios */ "./node_modules/@nextcloud/axios/dist/index.js");
/* harmony import */ var _nextcloud_dialogs__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! @nextcloud/dialogs */ "./node_modules/@nextcloud/dialogs/dist/index.mjs");
/* harmony import */ var _nextcloud_vue__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! @nextcloud/vue */ "./node_modules/@nextcloud/vue/dist/index.mjs");
/* harmony import */ var vue_material_design_icons_History_vue__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! vue-material-design-icons/History.vue */ "./node_modules/vue-material-design-icons/History.vue");
/* harmony import */ var vue_material_design_icons_InformationOutline_vue__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! vue-material-design-icons/InformationOutline.vue */ "./node_modules/vue-material-design-icons/InformationOutline.vue");
/* harmony import */ var vue_material_design_icons_Restore_vue__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! vue-material-design-icons/Restore.vue */ "./node_modules/vue-material-design-icons/Restore.vue");
/* harmony import */ var _icons_MetaVoxIcon_vue__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__(/*! ./icons/MetaVoxIcon.vue */ "./src/components/icons/MetaVoxIcon.vue");











/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = ({
  name: 'PageDetailsSidebar',
  components: {
    NcAppSidebar: _nextcloud_vue__WEBPACK_IMPORTED_MODULE_4__.NcAppSidebar,
    NcAppSidebarTab: _nextcloud_vue__WEBPACK_IMPORTED_MODULE_4__.NcAppSidebarTab,
    NcButton: _nextcloud_vue__WEBPACK_IMPORTED_MODULE_4__.NcButton,
    NcDialog: _nextcloud_vue__WEBPACK_IMPORTED_MODULE_4__.NcDialog,
    History: vue_material_design_icons_History_vue__WEBPACK_IMPORTED_MODULE_5__["default"],
    InformationOutline: vue_material_design_icons_InformationOutline_vue__WEBPACK_IMPORTED_MODULE_6__["default"],
    MetaVoxIcon: _icons_MetaVoxIcon_vue__WEBPACK_IMPORTED_MODULE_8__["default"],
    Restore: vue_material_design_icons_Restore_vue__WEBPACK_IMPORTED_MODULE_7__["default"]
  },
  props: {
    isOpen: {
      type: Boolean,
      default: false
    },
    pageId: {
      type: String,
      required: true
    },
    pageName: {
      type: String,
      default: ''
    },
    initialTab: {
      type: String,
      default: 'details-tab'
    }
  },
  emits: ['close', 'version-restored', 'version-selected'],
  data() {
    return {
      activeTab: 'details-tab',
      versions: [],
      currentVersion: null, // Current file metadata from API
      loadingVersions: false,
      versionError: null,
      restoringVersion: false,
      showRestoreDialog: false,
      versionToRestore: null,
      metadata: null,
      loadingMetadata: false,
      metadataError: null,
      editingTitle: false,
      editableTitle: '',
      savingTitle: false,
      metaVoxInstalled: false,
      loadingMetaVox: false,
      selectedVersion: null,
      editingLabel: null,
      editableLabel: '',
      isRestoring: false, // Flag to prevent auto-select during restore
      versionsLoaded: false // Flag for lazy loading
    };
  },
  computed: {
    pageSubtitle() {
      return this.t('Page details and history');
    },
    canEditTitle() {
      // Check write permission using Nextcloud's permissions
      return this.metadata?.permissions?.canWrite ?? this.metadata?.canEdit ?? false;
    },
    currentVersionDate() {
      // Use the relativeTime from currentVersion API response (like Files app)
      if (this.currentVersion?.relativeTime) {
        return this.currentVersion.relativeTime;
      }
      // Fallback to metadata if versions not loaded yet
      if (this.metadata?.modifiedRelative) {
        return this.metadata.modifiedRelative;
      }
      return this.t('Now');
    },
    currentVersionSize() {
      // Size from currentVersion API response
      if (this.currentVersion?.size) {
        return this.formatBytes(this.currentVersion.size);
      }
      return null;
    },
    currentVersionAuthor() {
      // Author from currentVersion API response
      return this.currentVersion?.author || null;
    }
  },
  watch: {
    isOpen(newValue, oldValue) {
      if (newValue && !oldValue) {
        // Sidebar just opened - reset state and apply initialTab
        this.versionsLoaded = false;
        this.activeTab = this.initialTab || 'details-tab';
        // Only load metadata (for details tab - default)
        this.loadMetadata().catch(() => {});
        this.checkMetaVoxInstalled();
      }
    },
    pageId() {
      // Note: Sidebar is closed on navigation (in App.vue), so this is a fallback
      if (this.isOpen) {
        this.versionsLoaded = false;
        this.selectedVersion = null;
        this.versions = [];
        this.currentVersion = null;
        this.metadata = null;
      }
    },
    initialTab(newTab) {
      // Only apply initialTab if sidebar is currently closed
      // When sidebar is open, user controls the active tab
      if (!this.isOpen && newTab) {
        this.activeTab = newTab;
      }
    },
    activeTab(newTab) {
      // Load data when tab is activated (lazy loading)
      if (newTab === 'versions-tab' && !this.versionsLoaded) {
        this.loadVersions();
        this.versionsLoaded = true;
      } else if (newTab === 'details-tab' && !this.metadata) {
        this.loadMetadata().catch(() => {});
      } else if (newTab === 'metavox-tab' && this.metaVoxInstalled) {
        this.$nextTick(() => {
          this.dispatchMetaVoxUpdate();
        });
      }
    },
    versions(newVersions) {
      // Auto-select first version if versions tab is active and versions just loaded
      // BUT skip if we're in the middle of a restore operation OR already have a selection
      if (this.activeTab === 'versions-tab' && newVersions.length > 0 && !this.isRestoring && !this.selectedVersion) {
        this.autoSelectFirstVersion();
      }
    },
    metaVoxInstalled(newValue) {
      // If MetaVox just became available and the tab is already active, dispatch update
      if (newValue && this.activeTab === 'metavox-tab') {
        this.$nextTick(() => {
          this.dispatchMetaVoxUpdate();
        });
      }
    }
  },
  mounted() {
    if (this.isOpen) {
      // Only load metadata at mount (details tab is default)
      this.loadMetadata().catch(() => {});
      this.checkMetaVoxInstalled();
    }

    // Listen for page save events to refresh versions
    window.addEventListener('intravox:page:saved', this.handlePageSaved);
    // Listen for edit mode started to reset to current version
    window.addEventListener('intravox:edit:started', this.handleEditStarted);
  },
  beforeUnmount() {
    // Clean up event listeners
    window.removeEventListener('intravox:page:saved', this.handlePageSaved);
    window.removeEventListener('intravox:edit:started', this.handleEditStarted);
  },
  methods: {
    t(key, vars = {}) {
      return (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_0__.translate)('intravox', key, vars);
    },
    handleClose() {
      this.$emit('close');
    },
    getDisplayPath(path) {
      if (!path) {
        return '';
      }

      // Extract folder path (remove filename)
      const lastSlash = path.lastIndexOf('/');
      const folderPath = lastSlash > 0 ? path.substring(0, lastSlash) : path;

      // Get the groupfolder name from metadata
      const groupfolderName = this.metadata?.mountPoint || 'IntraVox';

      // Format 1: Direct groupfolder access (admin/internal)
      // Path format: /__groupfolders/4/files/en/mission
      // Display format: IntraVox/en/mission
      if (folderPath.startsWith('/__groupfolders/')) {
        // Extract the part after /__groupfolders/X/
        const pathAfterGroupfolder = folderPath.replace(/^\/__groupfolders\/\d+\//, '');

        // Remove 'files/' from the beginning if present (internal path structure)
        const cleanPath = pathAfterGroupfolder.replace(/^files\//, '');

        // Prepend groupfolder name
        return cleanPath ? `${groupfolderName}/${cleanPath}` : groupfolderName;
      }

      // Format 2: User-mounted groupfolder view (normal users)
      // Path format: /user@email.com/files/IntraVox/en/mission
      // Display format: IntraVox/en/mission
      const userMountPattern = new RegExp(`^/[^/]+/files/${groupfolderName}/(.*)$`);
      const userMatch = folderPath.match(userMountPattern);
      if (userMatch) {
        const relativePath = userMatch[1];
        return relativePath ? `${groupfolderName}/${relativePath}` : groupfolderName;
      }

      return folderPath;
    },
    getFolderUrl(path) {
      if (!path) {
        return '#';
      }

      // Extract folder path (remove filename)
      const lastSlash = path.lastIndexOf('/');
      const folderPath = lastSlash > 0 ? path.substring(0, lastSlash) : path;

      // Use the groupfolder name from metadata if available, otherwise default to 'IntraVox'
      const groupfolderName = this.metadata?.mountPoint || 'IntraVox';

      // Get the parent folder fileId from metadata
      const fileId = this.metadata?.parentFolderId;

      // Format 1: Direct groupfolder access (admin/internal)
      // Path format: /__groupfolders/4/files/en/mission/page.json
      // Target format: /apps/files/files/{parentFolderId}?dir=/IntraVox/en/mission
      if (folderPath.startsWith('/__groupfolders/')) {
        // Extract the part after /__groupfolders/X/
        const pathAfterGroupfolder = folderPath.replace(/^\/__groupfolders\/\d+\//, '');

        // Remove 'files/' from the beginning if present (internal path structure)
        const cleanPath = pathAfterGroupfolder.replace(/^files\//, '');

        if (!fileId) {
          return '#';
        }

        // Generate Files app URL with fileId and dir parameters
        const filesPath = `/${groupfolderName}/${cleanPath}`;
        return (0,_nextcloud_router__WEBPACK_IMPORTED_MODULE_1__.generateUrl)('/apps/files/files/{fileId}?dir={dir}', {
          fileId: fileId,
          dir: filesPath
        });
      }

      // Format 2: User-mounted groupfolder view (normal users)
      // Path format: /user@email.com/files/IntraVox/en/mission
      // Target format: /apps/files/files/{parentFolderId}?dir=/IntraVox/en/mission
      const userMountPattern = new RegExp(`^/[^/]+/files/${groupfolderName}/(.*)$`);
      const userMatch = folderPath.match(userMountPattern);
      if (userMatch) {
        const relativePath = userMatch[1];

        if (!fileId) {
          return '#';
        }

        const filesPath = relativePath ? `/${groupfolderName}/${relativePath}` : `/${groupfolderName}`;
        return (0,_nextcloud_router__WEBPACK_IMPORTED_MODULE_1__.generateUrl)('/apps/files/files/{fileId}?dir={dir}', {
          fileId: fileId,
          dir: filesPath
        });
      }

      // Fallback for non-groupfolder paths
      return (0,_nextcloud_router__WEBPACK_IMPORTED_MODULE_1__.generateUrl)('/apps/files/?dir={dir}', { dir: folderPath });
    },
    onTabChange(newTabId) {
      // Load versions when tab is activated (lazy loading)
      if (newTabId === 'versions-tab' && !this.versionsLoaded) {
        this.loadVersions();
        this.versionsLoaded = true;
      } else if (newTabId === 'metavox-tab' && this.metaVoxInstalled) {
        this.$nextTick(() => {
          this.dispatchMetaVoxUpdate();
        });
      }
    },
    async loadVersions() {
      this.loadingVersions = true;
      this.versionError = null;

      try {
        const url = (0,_nextcloud_router__WEBPACK_IMPORTED_MODULE_1__.generateUrl)(`/apps/intravox/api/pages/${this.pageId}/versions`);
        const response = await _nextcloud_axios__WEBPACK_IMPORTED_MODULE_2__["default"].get(url);

        // API response structure: { currentVersion: {...}, versions: [...] }
        if (response.data && typeof response.data === 'object' && 'versions' in response.data) {
          this.currentVersion = response.data.currentVersion;
          this.versions = response.data.versions;
        } else {
          // Backwards compatibility: old API returned array directly
          this.versions = response.data;
          this.currentVersion = null;
        }
      } catch (error) {
        console.error('Failed to load versions:', error);
        this.versionError = error.response?.data?.error || this.t('Failed to load version history');
      } finally {
        this.loadingVersions = false;
      }
    },
    confirmRestoreVersion(timestamp) {
      this.versionToRestore = timestamp;
      this.showRestoreDialog = true;
    },
    cancelRestore() {
      this.showRestoreDialog = false;
      this.versionToRestore = null;
    },
    async restoreVersion() {
      if (!this.versionToRestore) {
        return;
      }

      this.restoringVersion = true;
      this.isRestoring = true; // Prevent auto-select during restore
      this.showRestoreDialog = false;

      // Remember which version we're restoring
      const restoredTimestamp = this.versionToRestore;

      try {
        const url = (0,_nextcloud_router__WEBPACK_IMPORTED_MODULE_1__.generateUrl)(`/apps/intravox/api/pages/${this.pageId}/versions/${this.versionToRestore}`);
        const response = await _nextcloud_axios__WEBPACK_IMPORTED_MODULE_2__["default"].post(url);

        // Emit event to parent to reload the page
        this.$emit('version-restored', response.data);

        // Reload versions list
        await this.loadVersions();

        // Show the current page (which now contains the restored content)
        // Don't select a historical version - that would show a preview
        // instead of the actual restored page
        this.$nextTick(() => {
          this.selectCurrentVersion();
          this.isRestoring = false;
        });

        this.versionToRestore = null;
      } catch (error) {
        console.error('Failed to restore version:', error);
        (0,_nextcloud_dialogs__WEBPACK_IMPORTED_MODULE_3__.showError)(this.t('Failed to restore version: {error}', {
          error: error.response?.data?.error || error.message
        }));
        this.isRestoring = false; // Reset flag on error too
      } finally {
        this.restoringVersion = false;
      }
    },
    async loadMetadata() {
      this.loadingMetadata = true;
      this.metadataError = null;

      try {
        const url = (0,_nextcloud_router__WEBPACK_IMPORTED_MODULE_1__.generateUrl)(`/apps/intravox/api/pages/${this.pageId}/metadata`);
        const response = await _nextcloud_axios__WEBPACK_IMPORTED_MODULE_2__["default"].get(url);
        this.metadata = response.data;
      } catch (error) {
        console.error('Failed to load metadata:', error);
        this.metadataError = error.response?.data?.error || this.t('Failed to load properties');
      } finally {
        this.loadingMetadata = false;
      }
    },
    startTitleEdit() {
      // Check write permission using Nextcloud's permissions
      const canWrite = this.metadata?.permissions?.canWrite ?? this.metadata?.canEdit ?? false;
      if (!canWrite) {
        return;
      }
      this.editableTitle = this.metadata.title;
      this.editingTitle = true;
      // Focus input after Vue updates the DOM
      this.$nextTick(() => {
        const input = this.$el.querySelector('.title-input');
        if (input) {
          input.focus();
          input.select();
        }
      });
    },
    cancelTitleEdit() {
      this.editingTitle = false;
      this.editableTitle = '';
    },
    async saveTitle() {
      if (!this.editableTitle.trim()) {
        (0,_nextcloud_dialogs__WEBPACK_IMPORTED_MODULE_3__.showError)(this.t('Title cannot be empty'));
        return;
      }

      this.savingTitle = true;

      try {
        const url = (0,_nextcloud_router__WEBPACK_IMPORTED_MODULE_1__.generateUrl)(`/apps/intravox/api/pages/${this.pageId}/metadata`);
        const response = await _nextcloud_axios__WEBPACK_IMPORTED_MODULE_2__["default"].put(url, {
          title: this.editableTitle.trim()
        });
        this.metadata = response.data;
        this.editingTitle = false;
        (0,_nextcloud_dialogs__WEBPACK_IMPORTED_MODULE_3__.showSuccess)(this.t('Title updated'));
      } catch (error) {
        console.error('Failed to update title:', error);
        (0,_nextcloud_dialogs__WEBPACK_IMPORTED_MODULE_3__.showError)(this.t('Failed to update title: {error}', {
          error: error.response?.data?.error || error.message
        }));
      } finally {
        this.savingTitle = false;
      }
    },
    async checkMetaVoxInstalled() {
      try {
        const url = (0,_nextcloud_router__WEBPACK_IMPORTED_MODULE_1__.generateUrl)('/apps/intravox/api/metavox/status');
        const response = await _nextcloud_axios__WEBPACK_IMPORTED_MODULE_2__["default"].get(url);
        this.metaVoxInstalled = response.data.installed === true;
      } catch (error) {
        this.metaVoxInstalled = false;
      }
    },
    dispatchMetaVoxUpdate() {
      if (!this.metaVoxInstalled) {
        return;
      }

      if (!this.$refs.metavoxContainer) {
        return;
      }

      // Dispatch custom event for MetaVox to listen to
      const event = new CustomEvent('intravox:metavox:update', {
        detail: {
          pageId: this.pageId,
          pageName: this.pageName,
          container: this.$refs.metavoxContainer,
          metadata: this.metadata
        }
      });
      window.dispatchEvent(event);
    },
    formatBytes(bytes) {
      if (bytes === 0) return '0 Bytes';
      const k = 1024;
      const sizes = ['Bytes', 'KB', 'MB', 'GB'];
      const i = Math.floor(Math.log(bytes) / Math.log(k));
      return Math.round(bytes / Math.pow(k, i) * 100) / 100 + ' ' + sizes[i];
    },
    startLabelEdit(version) {
      this.editingLabel = version.timestamp;
      this.editableLabel = version.label || '';
    },
    cancelLabelEdit() {
      this.editingLabel = null;
      this.editableLabel = '';
    },
    async saveVersionLabel(timestamp) {
      if (!this.editableLabel.trim() && !this.versions.find(v => v.timestamp === timestamp)?.label) {
        // No label to save and no existing label to remove
        this.cancelLabelEdit();
        return;
      }

      try {
        const url = (0,_nextcloud_router__WEBPACK_IMPORTED_MODULE_1__.generateUrl)(`/apps/intravox/api/pages/${this.pageId}/versions/${timestamp}/label`);
        await _nextcloud_axios__WEBPACK_IMPORTED_MODULE_2__["default"].put(url, {
          label: this.editableLabel.trim()
        });

        // Update local version
        const version = this.versions.find(v => v.timestamp === timestamp);
        if (version) {
          version.label = this.editableLabel.trim() || null;
        }

        this.cancelLabelEdit();
        (0,_nextcloud_dialogs__WEBPACK_IMPORTED_MODULE_3__.showSuccess)(this.t('Version label updated'));
      } catch (error) {
        (0,_nextcloud_dialogs__WEBPACK_IMPORTED_MODULE_3__.showError)(this.t('Failed to update version label: {error}', {
          error: error.response?.data?.error || error.message
        }));
      }
    },
    selectCurrentVersion() {
      // Clear version selection to show current page
      this.selectedVersion = null;
      // Emit event to clear version preview in parent
      this.$emit('version-selected', {
        version: null,
        pageId: this.pageId
      });
    },
    async selectVersion(version) {
      this.selectedVersion = version;

      // Emit event to parent component to show the version preview
      this.$emit('version-selected', {
        version,
        pageId: this.pageId
      });
    },
    getDepartmentMaxDepth() {
      // Department pages have max depth 5
      return 5;
    },
    getPageTypeLabel(type) {
      const labels = {
        'department': this.t('Department'),
        'container': this.t('Container'),
        'page': this.t('Page')
      };
      return labels[type] || type;
    },
    autoSelectFirstVersion() {
      // Auto-select current version (not a history version)
      this.$nextTick(() => {
        this.selectCurrentVersion();
      });
    },
    handlePageSaved(event) {
      // Refresh versions when this page is saved (if versions were already loaded)
      if (event.detail?.pageId === this.pageId && this.versionsLoaded) {
        this.loadVersions();
      }
    },
    handleEditStarted(event) {
      // When edit mode starts, reset to current version selection
      // User should always edit the current version, not a history version
      if (event.detail?.pageId === this.pageId) {
        this.selectCurrentVersion();
      }
    }
  }
});


/***/ },

/***/ "./src/components/icons/MetaVoxIcon.vue"
/*!**********************************************!*\
  !*** ./src/components/icons/MetaVoxIcon.vue ***!
  \**********************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _MetaVoxIcon_vue_vue_type_template_id_7ad755be_scoped_true__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./MetaVoxIcon.vue?vue&type=template&id=7ad755be&scoped=true */ "./src/components/icons/MetaVoxIcon.vue?vue&type=template&id=7ad755be&scoped=true");
/* harmony import */ var _MetaVoxIcon_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./MetaVoxIcon.vue?vue&type=script&lang=js */ "./src/components/icons/MetaVoxIcon.vue?vue&type=script&lang=js");
/* harmony import */ var _MetaVoxIcon_vue_vue_type_style_index_0_id_7ad755be_scoped_true_lang_css__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./MetaVoxIcon.vue?vue&type=style&index=0&id=7ad755be&scoped=true&lang=css */ "./src/components/icons/MetaVoxIcon.vue?vue&type=style&index=0&id=7ad755be&scoped=true&lang=css");
/* harmony import */ var _node_modules_vue_loader_dist_exportHelper_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ../../../node_modules/vue-loader/dist/exportHelper.js */ "./node_modules/vue-loader/dist/exportHelper.js");




;


const __exports__ = /*#__PURE__*/(0,_node_modules_vue_loader_dist_exportHelper_js__WEBPACK_IMPORTED_MODULE_3__["default"])(_MetaVoxIcon_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_1__["default"], [['render',_MetaVoxIcon_vue_vue_type_template_id_7ad755be_scoped_true__WEBPACK_IMPORTED_MODULE_0__.render],['__scopeId',"data-v-7ad755be"],['__file',"src/components/icons/MetaVoxIcon.vue"]])
/* hot reload */
if (false) // removed by dead control flow
{}


/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (__exports__);

/***/ },

/***/ "./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/icons/MetaVoxIcon.vue?vue&type=script&lang=js"
/*!******************************************************************************************************************************************!*\
  !*** ./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/icons/MetaVoxIcon.vue?vue&type=script&lang=js ***!
  \******************************************************************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _nextcloud_router__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @nextcloud/router */ "./node_modules/@nextcloud/router/dist/index.mjs");



/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = ({
  name: 'MetaVoxIcon',
  props: {
    size: {
      type: Number,
      default: 24
    }
  },
  computed: {
    iconUrl() {
      return (0,_nextcloud_router__WEBPACK_IMPORTED_MODULE_0__.imagePath)('metavox', 'app.svg')
    }
  }
});


/***/ },

/***/ "./src/components/PageDetailsSidebar.vue?vue&type=style&index=0&id=df855e4e&scoped=true&lang=css"
/*!*******************************************************************************************************!*\
  !*** ./src/components/PageDetailsSidebar.vue?vue&type=style&index=0&id=df855e4e&scoped=true&lang=css ***!
  \*******************************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _node_modules_style_loader_dist_cjs_js_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_dist_stylePostLoader_js_node_modules_vue_loader_dist_index_js_ruleSet_1_rules_5_use_0_PageDetailsSidebar_vue_vue_type_style_index_0_id_df855e4e_scoped_true_lang_css__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../node_modules/style-loader/dist/cjs.js!../../node_modules/css-loader/dist/cjs.js!../../node_modules/vue-loader/dist/stylePostLoader.js!../../node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./PageDetailsSidebar.vue?vue&type=style&index=0&id=df855e4e&scoped=true&lang=css */ "./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/dist/stylePostLoader.js!./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/PageDetailsSidebar.vue?vue&type=style&index=0&id=df855e4e&scoped=true&lang=css");


/***/ },

/***/ "./src/components/icons/MetaVoxIcon.vue?vue&type=style&index=0&id=7ad755be&scoped=true&lang=css"
/*!******************************************************************************************************!*\
  !*** ./src/components/icons/MetaVoxIcon.vue?vue&type=style&index=0&id=7ad755be&scoped=true&lang=css ***!
  \******************************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _node_modules_style_loader_dist_cjs_js_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_dist_stylePostLoader_js_node_modules_vue_loader_dist_index_js_ruleSet_1_rules_5_use_0_MetaVoxIcon_vue_vue_type_style_index_0_id_7ad755be_scoped_true_lang_css__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../node_modules/style-loader/dist/cjs.js!../../../node_modules/css-loader/dist/cjs.js!../../../node_modules/vue-loader/dist/stylePostLoader.js!../../../node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./MetaVoxIcon.vue?vue&type=style&index=0&id=7ad755be&scoped=true&lang=css */ "./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/dist/stylePostLoader.js!./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/icons/MetaVoxIcon.vue?vue&type=style&index=0&id=7ad755be&scoped=true&lang=css");


/***/ },

/***/ "./src/components/PageDetailsSidebar.vue?vue&type=script&lang=js"
/*!***********************************************************************!*\
  !*** ./src/components/PageDetailsSidebar.vue?vue&type=script&lang=js ***!
  \***********************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* reexport safe */ _node_modules_vue_loader_dist_index_js_ruleSet_1_rules_5_use_0_PageDetailsSidebar_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_0__["default"])
/* harmony export */ });
/* harmony import */ var _node_modules_vue_loader_dist_index_js_ruleSet_1_rules_5_use_0_PageDetailsSidebar_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./PageDetailsSidebar.vue?vue&type=script&lang=js */ "./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/PageDetailsSidebar.vue?vue&type=script&lang=js");
 

/***/ },

/***/ "./src/components/icons/MetaVoxIcon.vue?vue&type=script&lang=js"
/*!**********************************************************************!*\
  !*** ./src/components/icons/MetaVoxIcon.vue?vue&type=script&lang=js ***!
  \**********************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* reexport safe */ _node_modules_vue_loader_dist_index_js_ruleSet_1_rules_5_use_0_MetaVoxIcon_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_0__["default"])
/* harmony export */ });
/* harmony import */ var _node_modules_vue_loader_dist_index_js_ruleSet_1_rules_5_use_0_MetaVoxIcon_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./MetaVoxIcon.vue?vue&type=script&lang=js */ "./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/icons/MetaVoxIcon.vue?vue&type=script&lang=js");
 

/***/ },

/***/ "./src/components/PageDetailsSidebar.vue?vue&type=template&id=df855e4e&scoped=true"
/*!*****************************************************************************************!*\
  !*** ./src/components/PageDetailsSidebar.vue?vue&type=template&id=df855e4e&scoped=true ***!
  \*****************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   render: () => (/* reexport safe */ _node_modules_vue_loader_dist_templateLoader_js_ruleSet_1_rules_2_node_modules_vue_loader_dist_index_js_ruleSet_1_rules_5_use_0_PageDetailsSidebar_vue_vue_type_template_id_df855e4e_scoped_true__WEBPACK_IMPORTED_MODULE_0__.render)
/* harmony export */ });
/* harmony import */ var _node_modules_vue_loader_dist_templateLoader_js_ruleSet_1_rules_2_node_modules_vue_loader_dist_index_js_ruleSet_1_rules_5_use_0_PageDetailsSidebar_vue_vue_type_template_id_df855e4e_scoped_true__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../node_modules/vue-loader/dist/templateLoader.js??ruleSet[1].rules[2]!../../node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./PageDetailsSidebar.vue?vue&type=template&id=df855e4e&scoped=true */ "./node_modules/vue-loader/dist/templateLoader.js??ruleSet[1].rules[2]!./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/PageDetailsSidebar.vue?vue&type=template&id=df855e4e&scoped=true");


/***/ },

/***/ "./src/components/icons/MetaVoxIcon.vue?vue&type=template&id=7ad755be&scoped=true"
/*!****************************************************************************************!*\
  !*** ./src/components/icons/MetaVoxIcon.vue?vue&type=template&id=7ad755be&scoped=true ***!
  \****************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   render: () => (/* reexport safe */ _node_modules_vue_loader_dist_templateLoader_js_ruleSet_1_rules_2_node_modules_vue_loader_dist_index_js_ruleSet_1_rules_5_use_0_MetaVoxIcon_vue_vue_type_template_id_7ad755be_scoped_true__WEBPACK_IMPORTED_MODULE_0__.render)
/* harmony export */ });
/* harmony import */ var _node_modules_vue_loader_dist_templateLoader_js_ruleSet_1_rules_2_node_modules_vue_loader_dist_index_js_ruleSet_1_rules_5_use_0_MetaVoxIcon_vue_vue_type_template_id_7ad755be_scoped_true__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../node_modules/vue-loader/dist/templateLoader.js??ruleSet[1].rules[2]!../../../node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./MetaVoxIcon.vue?vue&type=template&id=7ad755be&scoped=true */ "./node_modules/vue-loader/dist/templateLoader.js??ruleSet[1].rules[2]!./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/icons/MetaVoxIcon.vue?vue&type=template&id=7ad755be&scoped=true");


/***/ },

/***/ "./node_modules/vue-loader/dist/templateLoader.js??ruleSet[1].rules[2]!./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/PageDetailsSidebar.vue?vue&type=template&id=df855e4e&scoped=true"
/*!***********************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/vue-loader/dist/templateLoader.js??ruleSet[1].rules[2]!./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/PageDetailsSidebar.vue?vue&type=template&id=df855e4e&scoped=true ***!
  \***********************************************************************************************************************************************************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   render: () => (/* binding */ render)
/* harmony export */ });
/* harmony import */ var vue__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! vue */ "./node_modules/vue/dist/vue.runtime.esm-bundler.js");


const _hoisted_1 = {
  key: 0,
  class: "loading"
}
const _hoisted_2 = {
  key: 1,
  class: "error-message"
}
const _hoisted_3 = {
  key: 2,
  class: "metadata-container"
}
const _hoisted_4 = { class: "metadata-row" }
const _hoisted_5 = { class: "metadata-label" }
const _hoisted_6 = { class: "metadata-value metadata-editable" }
const _hoisted_7 = { class: "metadata-row" }
const _hoisted_8 = { class: "metadata-label" }
const _hoisted_9 = { class: "metadata-value" }
const _hoisted_10 = { class: "metadata-hint" }
const _hoisted_11 = { class: "metadata-row" }
const _hoisted_12 = { class: "metadata-label" }
const _hoisted_13 = { class: "metadata-value metadata-path" }
const _hoisted_14 = ["href"]
const _hoisted_15 = {
  key: 0,
  class: "metadata-row"
}
const _hoisted_16 = { class: "metadata-label" }
const _hoisted_17 = { class: "metadata-value" }
const _hoisted_18 = {
  key: 0,
  class: "metadata-hint"
}
const _hoisted_19 = {
  key: 1,
  class: "metadata-row"
}
const _hoisted_20 = { class: "metadata-label" }
const _hoisted_21 = { class: "metadata-value metadata-monospace" }
const _hoisted_22 = {
  ref: "metavoxContainer",
  class: "metavox-container"
}
const _hoisted_23 = {
  key: 0,
  class: "loading"
}
const _hoisted_24 = {
  key: 1,
  class: "empty-state"
}
const _hoisted_25 = { class: "versions-container" }
const _hoisted_26 = {
  key: 0,
  class: "loading"
}
const _hoisted_27 = {
  key: 1,
  class: "error-message"
}
const _hoisted_28 = {
  key: 2,
  class: "empty-state"
}
const _hoisted_29 = { class: "hint" }
const _hoisted_30 = {
  key: 3,
  class: "version-list"
}
const _hoisted_31 = { class: "version-info" }
const _hoisted_32 = { class: "version-header" }
const _hoisted_33 = { class: "version-name" }
const _hoisted_34 = {
  key: 0,
  class: "version-author"
}
const _hoisted_35 = { class: "version-details" }
const _hoisted_36 = { key: 0 }
const _hoisted_37 = { class: "version-history-label" }
const _hoisted_38 = ["onClick"]
const _hoisted_39 = { class: "version-info" }
const _hoisted_40 = { class: "version-header" }
const _hoisted_41 = { class: "version-name" }
const _hoisted_42 = {
  key: 0,
  class: "version-author"
}
const _hoisted_43 = { class: "version-details" }
const _hoisted_44 = { class: "version-actions" }

function render(_ctx, _cache, $props, $setup, $data, $options) {
  const _component_InformationOutline = (0,vue__WEBPACK_IMPORTED_MODULE_0__.resolveComponent)("InformationOutline")
  const _component_NcButton = (0,vue__WEBPACK_IMPORTED_MODULE_0__.resolveComponent)("NcButton")
  const _component_NcAppSidebarTab = (0,vue__WEBPACK_IMPORTED_MODULE_0__.resolveComponent)("NcAppSidebarTab")
  const _component_MetaVoxIcon = (0,vue__WEBPACK_IMPORTED_MODULE_0__.resolveComponent)("MetaVoxIcon")
  const _component_History = (0,vue__WEBPACK_IMPORTED_MODULE_0__.resolveComponent)("History")
  const _component_Restore = (0,vue__WEBPACK_IMPORTED_MODULE_0__.resolveComponent)("Restore")
  const _component_NcAppSidebar = (0,vue__WEBPACK_IMPORTED_MODULE_0__.resolveComponent)("NcAppSidebar")
  const _component_NcDialog = (0,vue__WEBPACK_IMPORTED_MODULE_0__.resolveComponent)("NcDialog")

  return ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)(vue__WEBPACK_IMPORTED_MODULE_0__.Fragment, null, [
    ($props.isOpen)
      ? ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createBlock)(_component_NcAppSidebar, {
          key: 0,
          name: $props.pageName,
          subtitle: $options.pageSubtitle,
          active: $data.activeTab,
          "onUpdate:active": $options.onTabChange,
          onClose: $options.handleClose
        }, {
          default: (0,vue__WEBPACK_IMPORTED_MODULE_0__.withCtx)(() => [
            (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)(" Details Tab (Page Properties/File Info) "),
            (0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_NcAppSidebarTab, {
              id: "details-tab",
              name: $options.t('Details'),
              order: 1
            }, {
              icon: (0,vue__WEBPACK_IMPORTED_MODULE_0__.withCtx)(() => [
                (0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_InformationOutline, { size: 20 })
              ]),
              default: (0,vue__WEBPACK_IMPORTED_MODULE_0__.withCtx)(() => [
                ($data.loadingMetadata)
                  ? ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)("div", _hoisted_1, (0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)($options.t('Loading properties...')), 1 /* TEXT */))
                  : ($data.metadataError)
                    ? ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)("div", _hoisted_2, (0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)($data.metadataError), 1 /* TEXT */))
                    : ($data.metadata)
                      ? ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)("div", _hoisted_3, [
                          (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)(" Editable Title "),
                          (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("div", _hoisted_4, [
                            (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("label", _hoisted_5, (0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)($options.t('Name')), 1 /* TEXT */),
                            (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("div", _hoisted_6, [
                              ($data.editingTitle)
                                ? (0,vue__WEBPACK_IMPORTED_MODULE_0__.withDirectives)(((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)("input", {
                                    key: 0,
                                    "onUpdate:modelValue": _cache[0] || (_cache[0] = $event => (($data.editableTitle) = $event)),
                                    type: "text",
                                    class: "title-input",
                                    onKeydown: [
                                      _cache[1] || (_cache[1] = (0,vue__WEBPACK_IMPORTED_MODULE_0__.withKeys)((...args) => ($options.saveTitle && $options.saveTitle(...args)), ["enter"])),
                                      _cache[2] || (_cache[2] = (0,vue__WEBPACK_IMPORTED_MODULE_0__.withKeys)((...args) => ($options.cancelTitleEdit && $options.cancelTitleEdit(...args)), ["esc"]))
                                    ]
                                  }, null, 544 /* NEED_HYDRATION, NEED_PATCH */)), [
                                    [vue__WEBPACK_IMPORTED_MODULE_0__.vModelText, $data.editableTitle]
                                  ])
                                : ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)("span", {
                                    key: 1,
                                    class: (0,vue__WEBPACK_IMPORTED_MODULE_0__.normalizeClass)(["metadata-text", { 'metadata-text--editable': $options.canEditTitle }]),
                                    onClick: _cache[3] || (_cache[3] = (...args) => ($options.startTitleEdit && $options.startTitleEdit(...args)))
                                  }, (0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)($data.metadata.title || $options.t('Untitled')), 3 /* TEXT, CLASS */)),
                              ($data.editingTitle)
                                ? ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createBlock)(_component_NcButton, {
                                    key: 2,
                                    type: "primary",
                                    onClick: $options.saveTitle,
                                    disabled: $data.savingTitle,
                                    size: "small"
                                  }, {
                                    default: (0,vue__WEBPACK_IMPORTED_MODULE_0__.withCtx)(() => [
                                      (0,vue__WEBPACK_IMPORTED_MODULE_0__.createTextVNode)((0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)($options.t('Save')), 1 /* TEXT */)
                                    ]),
                                    _: 1 /* STABLE */
                                  }, 8 /* PROPS */, ["onClick", "disabled"]))
                                : (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)("v-if", true),
                              ($data.editingTitle)
                                ? ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createBlock)(_component_NcButton, {
                                    key: 3,
                                    type: "secondary",
                                    onClick: $options.cancelTitleEdit,
                                    size: "small"
                                  }, {
                                    default: (0,vue__WEBPACK_IMPORTED_MODULE_0__.withCtx)(() => [
                                      (0,vue__WEBPACK_IMPORTED_MODULE_0__.createTextVNode)((0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)($options.t('Cancel')), 1 /* TEXT */)
                                    ]),
                                    _: 1 /* STABLE */
                                  }, 8 /* PROPS */, ["onClick"]))
                                : (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)("v-if", true)
                            ])
                          ]),
                          (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)(" Modified "),
                          (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("div", _hoisted_7, [
                            (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("label", _hoisted_8, (0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)($options.t('Modified')), 1 /* TEXT */),
                            (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("div", _hoisted_9, [
                              (0,vue__WEBPACK_IMPORTED_MODULE_0__.createTextVNode)((0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)($data.metadata.modifiedRelative) + " ", 1 /* TEXT */),
                              (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("span", _hoisted_10, (0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)($data.metadata.modifiedFormatted), 1 /* TEXT */)
                            ])
                          ]),
                          (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)(" Path "),
                          (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("div", _hoisted_11, [
                            (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("label", _hoisted_12, (0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)($options.t('Location')), 1 /* TEXT */),
                            (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("div", _hoisted_13, [
                              (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("a", {
                                href: $options.getFolderUrl($data.metadata.path),
                                target: "_blank",
                                rel: "noopener noreferrer",
                                class: "folder-link"
                              }, (0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)($options.getDisplayPath($data.metadata.path)), 9 /* TEXT, PROPS */, _hoisted_14)
                            ])
                          ]),
                          (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)(" Page Type "),
                          ($data.metadata.type)
                            ? ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)("div", _hoisted_15, [
                                (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("label", _hoisted_16, (0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)($options.t('Type')), 1 /* TEXT */),
                                (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("div", _hoisted_17, [
                                  (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("span", {
                                    class: (0,vue__WEBPACK_IMPORTED_MODULE_0__.normalizeClass)(["page-type-badge", `page-type-${$data.metadata.type}`])
                                  }, (0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)($options.getPageTypeLabel($data.metadata.type)), 3 /* TEXT, CLASS */),
                                  ($data.metadata.hasChildren)
                                    ? ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)("span", _hoisted_18, (0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)($options.t('(has child pages)')), 1 /* TEXT */))
                                    : (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)("v-if", true)
                                ])
                              ]))
                            : (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)("v-if", true),
                          (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)(" Unique ID (for sharing) "),
                          ($data.metadata.uniqueId)
                            ? ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)("div", _hoisted_19, [
                                (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("label", _hoisted_20, (0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)($options.t('UniqueId')), 1 /* TEXT */),
                                (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("div", _hoisted_21, (0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)($data.metadata.uniqueId), 1 /* TEXT */)
                              ]))
                            : (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)("v-if", true)
                        ]))
                      : (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)("v-if", true)
              ]),
              _: 1 /* STABLE */
            }, 8 /* PROPS */, ["name"]),
            (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)(" MetaVox Tab (only if MetaVox app is installed) "),
            ($data.metaVoxInstalled)
              ? ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createBlock)(_component_NcAppSidebarTab, {
                  key: 0,
                  id: "metavox-tab",
                  name: $options.t('MetaVox'),
                  order: 2
                }, {
                  icon: (0,vue__WEBPACK_IMPORTED_MODULE_0__.withCtx)(() => [
                    (0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_MetaVoxIcon, { size: 20 })
                  ]),
                  default: (0,vue__WEBPACK_IMPORTED_MODULE_0__.withCtx)(() => [
                    (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("div", _hoisted_22, [
                      ($data.loadingMetaVox)
                        ? ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)("div", _hoisted_23, (0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)($options.t('Loading MetaVox...')), 1 /* TEXT */))
                        : (!$data.metaVoxInstalled)
                          ? ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)("div", _hoisted_24, [
                              (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("p", null, (0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)($options.t('MetaVox app is not installed')), 1 /* TEXT */)
                            ]))
                          : (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)("v-if", true)
                    ], 512 /* NEED_PATCH */)
                  ]),
                  _: 1 /* STABLE */
                }, 8 /* PROPS */, ["name"]))
              : (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)("v-if", true),
            (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)(" Versions Tab - Uses IntraVox versions API (leverages Nextcloud/GroupFolders versioning) "),
            (0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_NcAppSidebarTab, {
              id: "versions-tab",
              name: $options.t('Versions'),
              order: 3
            }, {
              icon: (0,vue__WEBPACK_IMPORTED_MODULE_0__.withCtx)(() => [
                (0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_History, { size: 20 })
              ]),
              default: (0,vue__WEBPACK_IMPORTED_MODULE_0__.withCtx)(() => [
                (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("div", _hoisted_25, [
                  ($data.loadingVersions)
                    ? ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)("div", _hoisted_26, (0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)($options.t('Loading versions...')), 1 /* TEXT */))
                    : ($data.versionError)
                      ? ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)("div", _hoisted_27, (0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)($data.versionError), 1 /* TEXT */))
                      : ($data.versions.length === 0)
                        ? ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)("div", _hoisted_28, [
                            (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("p", null, (0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)($options.t('No versions available')), 1 /* TEXT */),
                            (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("p", _hoisted_29, (0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)($options.t('Versions are created automatically when you save changes')), 1 /* TEXT */)
                          ]))
                        : ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)("div", _hoisted_30, [
                            (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)(" Current version (the actual file, not from history) - like Nextcloud Files app "),
                            (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("div", {
                              class: (0,vue__WEBPACK_IMPORTED_MODULE_0__.normalizeClass)(["version-item version-item--current", { 'version-item--selected': $data.selectedVersion === null }]),
                              onClick: _cache[4] || (_cache[4] = (...args) => ($options.selectCurrentVersion && $options.selectCurrentVersion(...args)))
                            }, [
                              (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("div", _hoisted_31, [
                                (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("div", _hoisted_32, [
                                  (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("span", _hoisted_33, (0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)($options.t('Current version')), 1 /* TEXT */),
                                  ($options.currentVersionAuthor)
                                    ? ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)("span", _hoisted_34, (0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)($options.currentVersionAuthor), 1 /* TEXT */))
                                    : (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)("v-if", true)
                                ]),
                                (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("div", _hoisted_35, [
                                  (0,vue__WEBPACK_IMPORTED_MODULE_0__.createTextVNode)((0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)($options.currentVersionDate), 1 /* TEXT */),
                                  ($options.currentVersionSize)
                                    ? ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)("span", _hoisted_36, " · " + (0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)($options.currentVersionSize), 1 /* TEXT */))
                                    : (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)("v-if", true)
                                ])
                              ])
                            ], 2 /* CLASS */),
                            (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)(" Version history "),
                            (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("div", _hoisted_37, (0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)($options.t('Version history')), 1 /* TEXT */),
                            ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(true), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)(vue__WEBPACK_IMPORTED_MODULE_0__.Fragment, null, (0,vue__WEBPACK_IMPORTED_MODULE_0__.renderList)($data.versions, (version) => {
                              return ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)("div", {
                                key: version.timestamp,
                                class: (0,vue__WEBPACK_IMPORTED_MODULE_0__.normalizeClass)(["version-item", { 'version-item--selected': $data.selectedVersion?.timestamp === version.timestamp }]),
                                onClick: $event => ($options.selectVersion(version))
                              }, [
                                (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("div", _hoisted_39, [
                                  (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("div", _hoisted_40, [
                                    (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("span", _hoisted_41, (0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)(version.label || $options.t('Version')), 1 /* TEXT */),
                                    (version.author)
                                      ? ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)("span", _hoisted_42, (0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)(version.author), 1 /* TEXT */))
                                      : (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)("v-if", true)
                                  ]),
                                  (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("div", _hoisted_43, (0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)(version.relativeTime || version.formattedDate) + " · " + (0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)($options.formatBytes(version.size)), 1 /* TEXT */)
                                ]),
                                (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("div", _hoisted_44, [
                                  (0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_NcButton, {
                                    type: "tertiary",
                                    "aria-label": $options.t('Restore this version'),
                                    title: $options.t('Restore this version'),
                                    onClick: (0,vue__WEBPACK_IMPORTED_MODULE_0__.withModifiers)($event => ($options.confirmRestoreVersion(version.timestamp)), ["stop"])
                                  }, {
                                    icon: (0,vue__WEBPACK_IMPORTED_MODULE_0__.withCtx)(() => [
                                      (0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_Restore, { size: 20 })
                                    ]),
                                    _: 1 /* STABLE */
                                  }, 8 /* PROPS */, ["aria-label", "title", "onClick"])
                                ])
                              ], 10 /* CLASS, PROPS */, _hoisted_38))
                            }), 128 /* KEYED_FRAGMENT */))
                          ]))
                ])
              ]),
              _: 1 /* STABLE */
            }, 8 /* PROPS */, ["name"])
          ]),
          _: 1 /* STABLE */
        }, 8 /* PROPS */, ["name", "subtitle", "active", "onUpdate:active", "onClose"]))
      : (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)("v-if", true),
    (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)(" Restore Version Confirmation Dialog "),
    ($data.showRestoreDialog)
      ? ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createBlock)(_component_NcDialog, {
          key: 1,
          name: $options.t('Restore Version'),
          message: $options.t('A backup of the current version will be created before restoring. Do you want to continue?'),
          buttons: [
      {
        label: $options.t('Cancel'),
        callback: $options.cancelRestore
      },
      {
        label: $options.t('Restore'),
        type: 'primary',
        callback: $options.restoreVersion
      }
    ],
          onClose: $options.cancelRestore
        }, null, 8 /* PROPS */, ["name", "message", "buttons", "onClose"]))
      : (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)("v-if", true)
  ], 64 /* STABLE_FRAGMENT */))
}

/***/ },

/***/ "./node_modules/vue-loader/dist/templateLoader.js??ruleSet[1].rules[2]!./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/icons/MetaVoxIcon.vue?vue&type=template&id=7ad755be&scoped=true"
/*!**********************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/vue-loader/dist/templateLoader.js??ruleSet[1].rules[2]!./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/icons/MetaVoxIcon.vue?vue&type=template&id=7ad755be&scoped=true ***!
  \**********************************************************************************************************************************************************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   render: () => (/* binding */ render)
/* harmony export */ });
/* harmony import */ var vue__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! vue */ "./node_modules/vue/dist/vue.runtime.esm-bundler.js");


const _hoisted_1 = { class: "material-design-icon metavox-icon" }
const _hoisted_2 = ["src", "width", "height"]

function render(_ctx, _cache, $props, $setup, $data, $options) {
  return ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)("span", _hoisted_1, [
    (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("img", {
      src: $options.iconUrl,
      width: $props.size,
      height: $props.size,
      alt: "MetaVox"
    }, null, 8 /* PROPS */, _hoisted_2)
  ]))
}

/***/ }

}]);
//# sourceMappingURL=intravox-src_components_PageDetailsSidebar_vue.aca1cf0e.js.map