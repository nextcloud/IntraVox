/******/ (() => { // webpackBootstrap
/******/ 	"use strict";
/******/ 	var __webpack_modules__ = ({

/***/ "./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/dist/stylePostLoader.js!./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/App.vue?vue&type=style&index=0&id=7ba5bd90&scoped=true&lang=css"
/*!*****************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/dist/stylePostLoader.js!./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/App.vue?vue&type=style&index=0&id=7ba5bd90&scoped=true&lang=css ***!
  \*****************************************************************************************************************************************************************************************************************************************/
(module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _node_modules_css_loader_dist_runtime_sourceMaps_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ../node_modules/css-loader/dist/runtime/sourceMaps.js */ "./node_modules/css-loader/dist/runtime/sourceMaps.js");
/* harmony import */ var _node_modules_css_loader_dist_runtime_sourceMaps_js__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_node_modules_css_loader_dist_runtime_sourceMaps_js__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _node_modules_css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ../node_modules/css-loader/dist/runtime/api.js */ "./node_modules/css-loader/dist/runtime/api.js");
/* harmony import */ var _node_modules_css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_node_modules_css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_1__);
// Imports


var ___CSS_LOADER_EXPORT___ = _node_modules_css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_1___default()((_node_modules_css_loader_dist_runtime_sourceMaps_js__WEBPACK_IMPORTED_MODULE_0___default()));
// Module
___CSS_LOADER_EXPORT___.push([module.id, `
#intravox-app[data-v-7ba5bd90] {
  width: 100%;
  max-width: 100vw;
  min-height: 100vh;
  background: var(--color-main-background);
  overflow-x: clip;
  box-sizing: border-box;
}

/* Navigation Bar */
.intravox-nav-bar[data-v-7ba5bd90] {
  background: var(--color-main-background);
  border-bottom: 1px solid var(--color-border);
  min-height: 50px;
  display: flex;
  align-items: center;
  width: 100%;
  box-sizing: border-box;
  position: relative;
  overflow: visible;
}

/* App Content Wrapper - contains main content and sidebar */
.app-content-wrapper[data-v-7ba5bd90] {
  display: flex;
  position: relative;
  flex: 1;
  min-height: 0;
  width: 100%;
}

/* Sticky topbar wrapping header + nav. Keeps page navigation in reach
   on long pages (e.g. Photo Story timelines with hundreds of photos). */
.intravox-topbar[data-v-7ba5bd90] {
  position: sticky;
  top: 0;
  z-index: 100;
  background: var(--color-main-background);
}

/* Header */
.intravox-header[data-v-7ba5bd90] {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 20px;
  background: var(--color-main-background);
  border-bottom: 1px solid var(--color-border);
  gap: 20px;
  width: 100%;
  box-sizing: border-box;
}
.header-left[data-v-7ba5bd90] {
  flex: 1 1 auto;
  min-width: 0;
  overflow: visible;
}
.header-right[data-v-7ba5bd90] {
  flex: 0 0 auto;
  display: flex;
  gap: 10px;
  align-items: center;
}
.draft-badge.draft[data-v-7ba5bd90] {
  display: inline-block;
  padding: 4px 12px;
  border-radius: 12px;
  font-size: 12px;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  background: var(--color-warning-light, #fff3cd);
  color: var(--color-warning-text, #6d5003);
  border: 1px solid var(--color-warning, #ffc107);
}
.page-lock-indicator[data-v-7ba5bd90] {
  color: var(--color-warning-text);
  font-size: 13px;
  white-space: nowrap;
}
.header-left h1[data-v-7ba5bd90] {
  margin: 0;
  font-size: 24px;
  color: var(--color-main-text);
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}
.page-title-input[data-v-7ba5bd90] {
  margin: 0;
  padding: 8px 12px;
  font-size: 24px;
  font-weight: bold;
  color: var(--color-main-text);
  background: var(--color-main-background);
  border: 2px solid var(--color-border-dark);
  border-radius: 3px;
  min-width: 300px;
  box-sizing: border-box;
}
.page-title-input[data-v-7ba5bd90]:focus {
  outline: none;
  border-color: var(--color-primary);
}
.intravox-content[data-v-7ba5bd90] {
  padding: 20px;
  padding-bottom: 60px; /* Extra space at bottom */
  max-width: min(1600px, 95vw);
  margin: 0 auto;
  width: 100%;
  min-height: calc(100vh - 200px); /* Ensure content takes up space */
  box-sizing: border-box;
  flex: 1;
  overflow-y: auto;
}

/* Breadcrumb row with Details button */
.breadcrumb-row[data-v-7ba5bd90] {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 8px;
  gap: 16px;
}
.breadcrumb-spacer[data-v-7ba5bd90] {
  flex: 1;
}
.details-btn[data-v-7ba5bd90] {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 36px;
  height: 36px;
  padding: 0;
  background: none;
  border: none;
  border-radius: var(--border-radius);
  color: var(--color-main-text);
  cursor: pointer;
  transition: background-color 0.1s ease;
  flex-shrink: 0;
}
.details-btn[data-v-7ba5bd90]:hover {
  background-color: var(--color-background-hover);
}
.details-btn[data-v-7ba5bd90]:active {
  background-color: var(--color-primary-element-light);
}
.details-btn-disabled[data-v-7ba5bd90],
.details-btn[data-v-7ba5bd90]:disabled {
  opacity: 0.4;
  cursor: default;
  pointer-events: none;
}
.loading[data-v-7ba5bd90], .error[data-v-7ba5bd90] {
  padding: 40px;
  text-align: center;
  font-size: 16px;
  color: var(--color-text-maxcontrast);
  flex: 1;
}
.error[data-v-7ba5bd90] {
  color: var(--color-error);
}

/* Mobile styles */
@media (max-width: 768px) {
#intravox-app[data-v-7ba5bd90] {
    overflow-x: clip;
}
.intravox-header[data-v-7ba5bd90] {
    padding: 12px 8px;
    width: 100%;
    box-sizing: border-box;
}
.intravox-header h1[data-v-7ba5bd90] {
    font-size: 18px;
}
.intravox-nav-bar[data-v-7ba5bd90] {
    width: 100%;
    box-sizing: border-box;
}
.intravox-content[data-v-7ba5bd90] {
    padding: 8px;
    padding-bottom: 40px;
    max-width: 100%;
    width: 100%;
    box-sizing: border-box;
}
.page-title-input[data-v-7ba5bd90] {
    min-width: 0;
    width: 100%;
    max-width: 250px;
    font-size: 18px;
}
.header-right[data-v-7ba5bd90] {
    gap: 4px;
}
}
`, "",{"version":3,"sources":["webpack://./src/App.vue"],"names":[],"mappings":";AAq2CA;EACE,WAAW;EACX,gBAAgB;EAChB,iBAAiB;EACjB,wCAAwC;EACxC,gBAAgB;EAChB,sBAAsB;AACxB;;AAEA,mBAAmB;AACnB;EACE,wCAAwC;EACxC,4CAA4C;EAC5C,gBAAgB;EAChB,aAAa;EACb,mBAAmB;EACnB,WAAW;EACX,sBAAsB;EACtB,kBAAkB;EAClB,iBAAiB;AACnB;;AAEA,4DAA4D;AAC5D;EACE,aAAa;EACb,kBAAkB;EAClB,OAAO;EACP,aAAa;EACb,WAAW;AACb;;AAEA;wEACwE;AACxE;EACE,gBAAgB;EAChB,MAAM;EACN,YAAY;EACZ,wCAAwC;AAC1C;;AAEA,WAAW;AACX;EACE,aAAa;EACb,8BAA8B;EAC9B,mBAAmB;EACnB,aAAa;EACb,wCAAwC;EACxC,4CAA4C;EAC5C,SAAS;EACT,WAAW;EACX,sBAAsB;AACxB;AAEA;EACE,cAAc;EACd,YAAY;EACZ,iBAAiB;AACnB;AAEA;EACE,cAAc;EACd,aAAa;EACb,SAAS;EACT,mBAAmB;AACrB;AAEA;EACE,qBAAqB;EACrB,iBAAiB;EACjB,mBAAmB;EACnB,eAAe;EACf,gBAAgB;EAChB,yBAAyB;EACzB,qBAAqB;EACrB,+CAA+C;EAC/C,yCAAyC;EACzC,+CAA+C;AACjD;AAEA;EACE,gCAAgC;EAChC,eAAe;EACf,mBAAmB;AACrB;AAEA;EACE,SAAS;EACT,eAAe;EACf,6BAA6B;EAC7B,mBAAmB;EACnB,gBAAgB;EAChB,uBAAuB;AACzB;AAEA;EACE,SAAS;EACT,iBAAiB;EACjB,eAAe;EACf,iBAAiB;EACjB,6BAA6B;EAC7B,wCAAwC;EACxC,0CAA0C;EAC1C,kBAAkB;EAClB,gBAAgB;EAChB,sBAAsB;AACxB;AAEA;EACE,aAAa;EACb,kCAAkC;AACpC;AAEA;EACE,aAAa;EACb,oBAAoB,EAAE,0BAA0B;EAChD,4BAA4B;EAC5B,cAAc;EACd,WAAW;EACX,+BAA+B,EAAE,kCAAkC;EACnE,sBAAsB;EACtB,OAAO;EACP,gBAAgB;AAClB;;AAEA,uCAAuC;AACvC;EACE,aAAa;EACb,mBAAmB;EACnB,8BAA8B;EAC9B,kBAAkB;EAClB,SAAS;AACX;AAEA;EACE,OAAO;AACT;AAEA;EACE,aAAa;EACb,mBAAmB;EACnB,uBAAuB;EACvB,WAAW;EACX,YAAY;EACZ,UAAU;EACV,gBAAgB;EAChB,YAAY;EACZ,mCAAmC;EACnC,6BAA6B;EAC7B,eAAe;EACf,sCAAsC;EACtC,cAAc;AAChB;AAEA;EACE,+CAA+C;AACjD;AAEA;EACE,oDAAoD;AACtD;AAEA;;EAEE,YAAY;EACZ,eAAe;EACf,oBAAoB;AACtB;AAEA;EACE,aAAa;EACb,kBAAkB;EAClB,eAAe;EACf,oCAAoC;EACpC,OAAO;AACT;AAEA;EACE,yBAAyB;AAC3B;;AAEA,kBAAkB;AAClB;AACE;IACE,gBAAgB;AAClB;AAEA;IACE,iBAAiB;IACjB,WAAW;IACX,sBAAsB;AACxB;AAEA;IACE,eAAe;AACjB;AAEA;IACE,WAAW;IACX,sBAAsB;AACxB;AAEA;IACE,YAAY;IACZ,oBAAoB;IACpB,eAAe;IACf,WAAW;IACX,sBAAsB;AACxB;AAEA;IACE,YAAY;IACZ,WAAW;IACX,gBAAgB;IAChB,eAAe;AACjB;AAEA;IACE,QAAQ;AACV;AACF","sourcesContent":["<template>\r\n  <div id=\"intravox-app\">\r\n    <a href=\"#intravox-main-content\" class=\"skip-link\">{{ t('Skip to main content') }}</a>\r\n\r\n    <!-- Sticky topbar wraps header + navigation so navigating between pages\r\n         doesn't require scrolling all the way back up on long pages. -->\r\n    <div class=\"intravox-topbar\">\r\n\r\n    <!-- Header with page title and actions -->\r\n    <header class=\"intravox-header\">\r\n      <div class=\"header-left\">\r\n        <h1 v-if=\"!isEditMode\">{{ currentPage?.title || 'IntraVox' }}</h1>\r\n        <input\r\n          v-else\r\n          v-model=\"editableTitle\"\r\n          type=\"text\"\r\n          class=\"page-title-input\"\r\n          :placeholder=\"t('Page title')\"\r\n          :aria-label=\"t('Page title')\"\r\n        />\r\n      </div>\r\n\r\n      <div class=\"header-right\">\r\n        <!-- Draft indicator (visible to editors in view mode) -->\r\n        <span v-if=\"!isEditMode && currentPage?.status === 'draft'\" class=\"draft-badge draft\">\r\n          {{ t('Draft') }}\r\n        </span>\r\n\r\n        <!-- Share Button (only visible when NC share exists) -->\r\n        <ShareButton v-if=\"!isEditMode && currentPage?.uniqueId\"\r\n                     :page-unique-id=\"currentPage.uniqueId\"\r\n                     :page-title=\"currentPage.title\"\r\n                     :language=\"currentLanguage\" />\r\n\r\n        <!-- Lock indicator (when another user is editing this page) -->\r\n        <span v-if=\"!isEditMode && pageLock\" class=\"page-lock-indicator\">\r\n          {{ t('{displayName} is editing this page', { displayName: pageLock.displayName }) }}\r\n          <NcButton v-if=\"canEditNavigation\"\r\n                    @click=\"forceUnlock\"\r\n                    type=\"tertiary\"\r\n                    :aria-label=\"t('Unlock')\">\r\n            {{ t('Unlock') }}\r\n          </NcButton>\r\n        </span>\r\n\r\n        <!-- Edit Page Button (only visible when user has edit permissions for this page) -->\r\n        <NcButton v-if=\"!isEditMode && canEditCurrentPage\"\r\n                  @click=\"startEditMode\"\r\n                  type=\"secondary\"\r\n                  :disabled=\"!!pageLock\"\r\n                  :aria-label=\"t('Edit this page')\">\r\n          <template #icon>\r\n            <Pencil :size=\"20\" />\r\n          </template>\r\n          {{ t('Edit Page') }}\r\n        </NcButton>\r\n\r\n        <!-- Page Actions Menu (3-dot menu) -->\r\n        <PageActionsMenu v-if=\"!isEditMode\"\r\n                         :is-edit-mode=\"isEditMode\"\r\n                         :permissions=\"pagePermissions\"\r\n                         @edit-navigation=\"showNavigationEditor = true\"\r\n                         @create-page=\"createNewPage\"\r\n                         @page-settings=\"showPageSettingsModal = true\"\r\n                         @save-as-template=\"showSaveAsTemplateModal = true\"\r\n                         @feed-settings=\"showFeedSettings = true\" />\r\n\r\n        <!-- Edit Mode Actions (Save/Cancel) -->\r\n        <template v-else>\r\n          <NcButton @click=\"toggleDraftStatus\"\r\n                    :type=\"currentPage?.status === 'draft' ? 'warning' : 'secondary'\"\r\n                    :aria-label=\"currentPage?.status === 'draft' ? t('Draft — click to publish') : t('Published — click to unpublish')\">\r\n            <template #icon>\r\n              <EyeOff :size=\"20\" v-if=\"currentPage?.status === 'draft'\" />\r\n              <Eye :size=\"20\" v-else />\r\n            </template>\r\n            {{ currentPage?.status === 'draft' ? t('Draft') : t('Published') }}\r\n          </NcButton>\r\n          <NcButton @click=\"cancelEditMode\"\r\n                    type=\"secondary\"\r\n                    :aria-label=\"t('Cancel editing')\">\r\n            <template #icon>\r\n              <Close :size=\"20\" />\r\n            </template>\r\n            {{ t('Cancel') }}\r\n          </NcButton>\r\n          <NcButton @click=\"saveAndExitEditMode\"\r\n                    type=\"primary\"\r\n                    :aria-label=\"t('Save changes')\">\r\n            <template #icon>\r\n              <ContentSave :size=\"20\" />\r\n            </template>\r\n            {{ t('Save') }}\r\n          </NcButton>\r\n        </template>\r\n      </div>\r\n    </header>\r\n\r\n    <!-- Navigation -->\r\n    <div class=\"intravox-nav-bar\">\r\n      <Navigation :items=\"navigation.items\"\r\n                  :type=\"navigation.type\"\r\n                  @navigate=\"navigateToItem\"\r\n                  @show-tree=\"showPageTree = true\" />\r\n    </div>\r\n\r\n    </div><!-- /.intravox-topbar -->\r\n\r\n    <!-- Main content area with sidebar -->\r\n    <div class=\"app-content-wrapper\">\r\n      <div v-if=\"loading\" class=\"loading\" role=\"status\" aria-live=\"polite\">\r\n        {{ t('Loading...') }}\r\n      </div>\r\n\r\n      <!-- Welcome screen when no pages exist (first install) -->\r\n      <WelcomeScreen v-else-if=\"showWelcomeScreen\" />\r\n\r\n      <div v-else-if=\"error\" class=\"error\" role=\"alert\">\r\n        {{ error }}\r\n      </div>\r\n\r\n      <main v-else class=\"intravox-content\" id=\"intravox-main-content\">\r\n        <!-- Breadcrumb row with Details button -->\r\n        <div v-if=\"breadcrumb.length > 0 || !isEditMode\" class=\"breadcrumb-row\">\r\n          <Breadcrumb v-if=\"breadcrumb.length > 0\"\r\n                      :breadcrumb=\"breadcrumb\"\r\n                      @navigate=\"selectPage\" />\r\n          <div v-else class=\"breadcrumb-spacer\"></div>\r\n          <button v-if=\"!isEditMode\"\r\n                  class=\"details-btn\"\r\n                  :class=\"{ 'details-btn-disabled': showDetailsSidebar }\"\r\n                  :disabled=\"showDetailsSidebar\"\r\n                  @click=\"showDetailsSidebar = true\"\r\n                  :aria-label=\"t('Details')\"\r\n                  :title=\"t('Details')\">\r\n            <Information :size=\"20\" />\r\n          </button>\r\n        </div>\r\n\r\n        <PageViewer\r\n          v-if=\"!isEditMode && displayPage\"\r\n          :page=\"displayPage\"\r\n          :engagement-settings=\"globalEngagementSettings\"\r\n          @navigate=\"selectPage\"\r\n        />\r\n        <PageEditor\r\n          v-else-if=\"isEditMode && currentPage\"\r\n          :page=\"currentPage\"\r\n          @update=\"updatePage\"\r\n        />\r\n      </main>\r\n\r\n      <!-- Page Details Sidebar (inside content wrapper) -->\r\n      <PageDetailsSidebar\r\n        v-show=\"currentPage && !loading && !error\"\r\n        :is-open=\"showDetailsSidebar\"\r\n        :page-id=\"currentPage?.uniqueId\"\r\n        :page-name=\"currentPage?.title || t('Untitled Page')\"\r\n        :initial-tab=\"sidebarInitialTab\"\r\n        @close=\"handleCloseSidebar\"\r\n        @version-restored=\"handleVersionRestored\"\r\n        @version-selected=\"handleVersionSelected\"\r\n      />\r\n    </div>\r\n\r\n    <PageListModal\r\n      v-if=\"showPages\"\r\n      :pages=\"pages\"\r\n      @close=\"showPages = false\"\r\n      @select=\"selectPage\"\r\n      @delete=\"deletePage\"\r\n    />\r\n\r\n    <PageTreeModal\r\n      v-if=\"showPageTree\"\r\n      :current-page-id=\"currentPage?.uniqueId\"\r\n      @close=\"showPageTree = false\"\r\n      @navigate=\"selectPage\"\r\n    />\r\n\r\n    <NewPageModal\r\n      v-if=\"showNewPageModal\"\r\n      :current-page-path=\"currentPage?.path || null\"\r\n      @close=\"showNewPageModal = false\"\r\n      @create=\"handleCreatePage\"\r\n      @create-from-template=\"handleCreatePageFromTemplate\"\r\n    />\r\n\r\n    <NavigationEditor\r\n      v-if=\"showNavigationEditor\"\r\n      :navigation=\"navigation\"\r\n      :pages=\"pages\"\r\n      @close=\"showNavigationEditor = false\"\r\n      @save=\"saveNavigation\"\r\n    />\r\n\r\n    <PageSettingsModal\r\n      v-if=\"showPageSettingsModal\"\r\n      :page-unique-id=\"currentPage?.uniqueId\"\r\n      :settings=\"currentPage?.settings || {}\"\r\n      :global-settings=\"globalEngagementSettings\"\r\n      @close=\"showPageSettingsModal = false\"\r\n      @save=\"handlePageSettingsSave\"\r\n    />\r\n\r\n    <SaveAsTemplateModal\r\n      v-if=\"showSaveAsTemplateModal\"\r\n      :page-unique-id=\"currentPage?.uniqueId\"\r\n      :page-title=\"currentPage?.title\"\r\n      @close=\"showSaveAsTemplateModal = false\"\r\n      @saved=\"handleTemplateSaved\"\r\n    />\r\n\r\n    <FeedSettings\r\n      v-if=\"showFeedSettings\"\r\n      @close=\"showFeedSettings = false\"\r\n    />\r\n\r\n    <!-- Footer -->\r\n    <Footer\r\n      v-if=\"!loading && !error\"\r\n      :footer-content=\"footerContent\"\r\n      :can-edit=\"canEditFooter\"\r\n      :is-home-page=\"isCurrentPageHome\"\r\n      @save=\"handleFooterSave\"\r\n      @navigate=\"selectPage\"\r\n    />\r\n\r\n  </div>\r\n</template>\r\n\r\n<script>\r\nimport axios from '@nextcloud/axios';\r\nimport { generateUrl } from '@nextcloud/router';\r\nimport { translate as t } from '@nextcloud/l10n';\r\nimport { showSuccess, showError } from '@nextcloud/dialogs';\r\nimport { NcButton } from '@nextcloud/vue';\r\nimport ContentSave from 'vue-material-design-icons/ContentSave.vue';\r\nimport Close from 'vue-material-design-icons/Close.vue';\r\nimport Eye from 'vue-material-design-icons/Eye.vue';\r\nimport EyeOff from 'vue-material-design-icons/EyeOff.vue';\r\nimport Pencil from 'vue-material-design-icons/Pencil.vue';\r\nimport Information from 'vue-material-design-icons/Information.vue';\r\nimport { defineAsyncComponent } from 'vue';\r\nimport PageViewer from './components/PageViewer.vue';\r\nimport Navigation from './components/Navigation.vue';\r\nimport Footer from './components/Footer.vue';\r\nimport PageActionsMenu from './components/PageActionsMenu.vue';\r\nimport Breadcrumb from './components/Breadcrumb.vue';\r\nimport ShareButton from './components/ShareButton.vue';\r\nimport CacheService from './services/CacheService.js';\r\nimport './metavox-integration.js'; // Load MetaVox integration\r\n\r\n// Lazy-loaded components (only loaded when needed)\r\n// This reduces initial bundle size and improves first load performance\r\nconst PageEditor = defineAsyncComponent(() => import('./components/PageEditor.vue'));\r\nconst PageListModal = defineAsyncComponent(() => import('./components/PageListModal.vue'));\r\nconst PageTreeModal = defineAsyncComponent(() => import('./components/PageTreeModal.vue'));\r\nconst NewPageModal = defineAsyncComponent(() => import('./components/NewPageModal.vue'));\r\nconst NavigationEditor = defineAsyncComponent(() => import('./components/NavigationEditor.vue'));\r\nconst PageDetailsSidebar = defineAsyncComponent(() => import('./components/PageDetailsSidebar.vue'));\r\nconst WelcomeScreen = defineAsyncComponent(() => import('./components/WelcomeScreen.vue'));\r\nconst PageSettingsModal = defineAsyncComponent(() => import('./components/PageSettingsModal.vue'));\r\nconst SaveAsTemplateModal = defineAsyncComponent(() => import('./components/SaveAsTemplateModal.vue'));\r\nconst FeedSettings = defineAsyncComponent(() => import('./components/FeedSettings.vue'));\r\n\r\n// Helper function to find home page\r\nconst findHomePage = (pages) => {\r\n  // Try to find page with slug \"home\" or filename containing \"home\"\r\n  return pages.find(p => p.slug === 'home' || p.path?.toLowerCase().includes('/home.json')) || pages[0];\r\n};\r\n\r\nexport default {\r\n  name: 'App',\r\n  components: {\r\n    NcButton,\r\n    ContentSave,\r\n    Close,\r\n    Eye,\r\n    EyeOff,\r\n    Pencil,\r\n    Information,\r\n    PageViewer,\r\n    PageEditor,\r\n    PageListModal,\r\n    PageTreeModal,\r\n    NewPageModal,\r\n    Navigation,\r\n    NavigationEditor,\r\n    Footer,\r\n    PageActionsMenu,\r\n    PageDetailsSidebar,\r\n    Breadcrumb,\r\n    WelcomeScreen,\r\n    PageSettingsModal,\r\n    SaveAsTemplateModal,\r\n    ShareButton,\r\n    FeedSettings\r\n  },\r\n  data() {\r\n    return {\r\n      pages: [],\r\n      currentPage: null,\r\n      originalPage: null, // For rollback\r\n      editableTitle: '',\r\n      isEditMode: false,\r\n      loading: true,\r\n      error: null,\r\n      showPages: false,\r\n      showPageTree: false,\r\n      showNewPageModal: false,\r\n      showSaveAsTemplateModal: false,\r\n      showDetailsSidebar: false,\r\n      breadcrumb: [],\r\n      navigation: {\r\n        type: 'dropdown',\r\n        items: []\r\n      },\r\n      canEditNavigation: false,\r\n      showNavigationEditor: false,\r\n      currentLanguage: document.documentElement.lang || 'en',\r\n      footerContent: '',\r\n      canEditFooter: false,\r\n      // Version preview state\r\n      selectedVersion: null,\r\n      versionPage: null,\r\n      loadingVersion: false,\r\n      // Sidebar state\r\n      sidebarInitialTab: 'details-tab',\r\n      // Page settings modal\r\n      showPageSettingsModal: false,\r\n      // RSS Feed settings modal\r\n      showFeedSettings: false,\r\n      // Page locking\r\n      pageLock: null,\r\n      lockHeartbeatTimer: null,\r\n      // Global engagement settings (loaded from API)\r\n      globalEngagementSettings: {\r\n        allowPageReactions: true,\r\n        allowComments: true,\r\n        allowCommentReactions: true,\r\n        singleReactionPerUser: true\r\n      }\r\n    };\r\n  },\r\n  computed: {\r\n    /**\r\n     * Page permissions based on folder-level ACLs from GroupFolder\r\n     * Uses Nextcloud's permission system to determine what the user can do\r\n     * Permissions are retrieved from the API for each page\r\n     */\r\n    pagePermissions() {\r\n      const perms = this.currentPage?.permissions || {};\r\n      return {\r\n        editNavigation: this.canEditNavigation,\r\n        viewPages: perms.canRead !== false,  // Default to true if not specified\r\n        // Creating a page requires both write and create permissions\r\n        createPage: (perms.canWrite && perms.canCreate) || false,\r\n        editPage: perms.canWrite || false,\r\n        deletePage: perms.canDelete || false,\r\n        // Save as template requires read on the page (to copy content)\r\n        // Note: The backend also checks if user can write to _templates folder\r\n        saveAsTemplate: perms.canRead !== false\r\n      };\r\n    },\r\n    /**\r\n     * Helper to check if user can edit the current page\r\n     */\r\n    canEditCurrentPage() {\r\n      return this.currentPage?.permissions?.canWrite || false;\r\n    },\r\n    /**\r\n     * Returns the page to display - either the version preview or the current page\r\n     */\r\n    displayPage() {\r\n      return this.versionPage || this.currentPage;\r\n    },\r\n    /**\r\n     * Check if we're currently showing a version preview\r\n     */\r\n    isShowingVersion() {\r\n      return this.versionPage !== null;\r\n    },\r\n    /**\r\n     * Check if the current page is a home page\r\n     * A page is considered home if it's at the language root level\r\n     * This includes both \"nl/home\" and \"nl\" paths\r\n     */\r\n    isCurrentPageHome() {\r\n      if (!this.currentPage || !this.currentPage.path) {\r\n        return false;\r\n      }\r\n      const pathParts = this.currentPage.path.split('/').filter(p => p);\r\n\r\n      // Home page can be either:\r\n      // 1. Just language code: \"nl\" (length === 1)\r\n      // 2. Language + home folder: \"nl/home\" (length === 2 && last part is \"home\")\r\n      const isHome = pathParts.length === 1 ||\r\n                     (pathParts.length === 2 && pathParts[1] === 'home');\r\n      return isHome;\r\n    },\r\n    /**\r\n     * Show welcome screen when no pages exist (first-time install)\r\n     */\r\n    showWelcomeScreen() {\r\n      return !this.loading && this.pages.length === 0 && !this.error;\r\n    }\r\n  },\r\n  async mounted() {\r\n    // Load pages, navigation, footer, and settings in parallel\r\n    try {\r\n      await Promise.all([\r\n        this.loadPages(),\r\n        this.loadNavigation(),\r\n        this.loadFooter(),\r\n        this.loadEngagementSettings()\r\n      ]);\r\n    } catch (err) {\r\n      // Errors are handled in individual loaders\r\n    }\r\n\r\n    // Setup hash-based navigation\r\n    window.addEventListener('hashchange', this.handleHashChange);\r\n\r\n    // Release page lock on tab/window close\r\n    window.addEventListener('beforeunload', this.handleBeforeUnload);\r\n\r\n    // Use MutationObserver to watch for HTML lang attribute changes\r\n    // (more efficient than setInterval polling)\r\n    this.langObserver = new MutationObserver(() => {\r\n      const newLanguage = document.documentElement.lang || 'en';\r\n      if (newLanguage !== this.currentLanguage) {\r\n        this.currentLanguage = newLanguage;\r\n        this.handleLanguageChange();\r\n      }\r\n    });\r\n\r\n    this.langObserver.observe(document.documentElement, {\r\n      attributes: true,\r\n      attributeFilter: ['lang']\r\n    });\r\n  },\r\n  beforeUnmount() {\r\n    window.removeEventListener('hashchange', this.handleHashChange);\r\n    window.removeEventListener('beforeunload', this.handleBeforeUnload);\r\n    this.stopLockHeartbeat();\r\n    if (this.langObserver) {\r\n      this.langObserver.disconnect();\r\n    }\r\n  },\r\n  methods: {\r\n    t(key, vars = {}) {\r\n      return t('intravox', key, vars);\r\n    },\r\n    async loadPages() {\r\n      try {\r\n        // Check cache first\r\n        const cacheKey = 'pages-list';\r\n        const cached = CacheService.get(cacheKey);\r\n        if (cached) {\r\n          this.pages = cached;\r\n          this.loading = false;\r\n          // Continue loading in background to update cache\r\n        } else {\r\n          this.loading = true;\r\n        }\r\n\r\n        const response = await axios.get(generateUrl('/apps/intravox/api/pages'));\r\n        this.pages = response.data;\r\n\r\n        // Update cache\r\n        CacheService.set('pages-list', response.data);\r\n\r\n        if (this.pages.length > 0) {\r\n          // Check if we came from a /p/{uniqueId} URL\r\n          const appElement = document.getElementById('app-intravox');\r\n          const uniqueIdFromUrl = appElement?.dataset.uniqueId;\r\n\r\n          // Check URL hash for page to load\r\n          const hash = window.location.hash;\r\n          let targetPage = null;\r\n          let foundInHash = false;\r\n\r\n          // Priority 1: uniqueId from URL path\r\n          if (uniqueIdFromUrl) {\r\n            targetPage = this.pages.find(p => p.uniqueId === uniqueIdFromUrl);\r\n            if (targetPage) {\r\n              foundInHash = true;\r\n            }\r\n          }\r\n\r\n          // Priority 2: Hash in URL\r\n          if (!targetPage && hash) {\r\n            const pageIdentifier = hash.substring(1); // Remove '#'\r\n\r\n            // Try to find page by ID or uniqueId\r\n            targetPage = this.pages.find(p => p.uniqueId === pageIdentifier || p.uniqueId === pageIdentifier);\r\n            if (targetPage) {\r\n              foundInHash = true;\r\n            }\r\n          }\r\n\r\n          // Fall back to home page if no hash or page not found\r\n          if (!targetPage) {\r\n            targetPage = findHomePage(this.pages);\r\n          }\r\n\r\n          // Validate targetPage has a uniqueId before selecting\r\n          if (!targetPage || !targetPage.uniqueId) {\r\n            console.error('IntraVox: No valid page found to load', { targetPage, pages: this.pages });\r\n            this.error = this.t('No valid pages found. Pages might be missing uniqueId.');\r\n            return;\r\n          }\r\n\r\n          // Only update URL if we didn't find the page in the hash (i.e., we're loading default/home)\r\n          await this.selectPage(targetPage.uniqueId, !foundInHash);\r\n        }\r\n        // If no pages found, don't set error - the welcome screen will be shown instead\r\n      } catch (err) {\r\n        console.error('IntraVox: Error loading pages:', err);\r\n        this.error = this.t('Could not load pages: {error}', { error: err.message });\r\n      } finally {\r\n        this.loading = false;\r\n      }\r\n    },\r\n    async selectPage(pageId, updateUrl = true) {\r\n      try {\r\n        // Validate pageId\r\n        if (!pageId || pageId === 'undefined') {\r\n          console.error('IntraVox: Cannot select page with invalid ID:', pageId);\r\n          showError(this.t('Invalid page ID'));\r\n          return;\r\n        }\r\n\r\n        // Release any active lock when navigating away from a page being edited\r\n        if (this.isEditMode && this.currentPage?.uniqueId !== pageId) {\r\n          await this.releaseLock();\r\n          if (this.originalPage) {\r\n            this.currentPage = structuredClone(this.originalPage);\r\n          }\r\n          this.isEditMode = false;\r\n          this.originalPage = null;\r\n        }\r\n\r\n        // Close sidebar when navigating to a different page\r\n        if (this.showDetailsSidebar && this.currentPage?.uniqueId !== pageId) {\r\n          this.showDetailsSidebar = false;\r\n          this.sidebarInitialTab = 'details-tab';\r\n        }\r\n\r\n        // Check cache first\r\n        const cacheKey = `page-${pageId}`;\r\n        const cached = CacheService.get(cacheKey);\r\n\r\n        // Clear version preview when navigating to a different page\r\n        this.clearVersionPreview();\r\n\r\n        if (cached) {\r\n          // Show cached data immediately - no waiting\r\n          this.currentPage = cached;\r\n          this.breadcrumb = cached.breadcrumb || [];\r\n          this.isEditMode = false;\r\n          this.showPages = false;\r\n          this.loading = false;\r\n\r\n          // Update URL and metadata immediately from cache\r\n          if (updateUrl && this.currentPage) {\r\n            const pageIdentifier = this.currentPage.uniqueId;\r\n            const newHash = `#${pageIdentifier}`;\r\n            if (window.location.hash !== newHash) {\r\n              window.location.hash = newHash;\r\n            }\r\n          }\r\n          this.updatePageMetadata();\r\n\r\n          // Check if this page is locked by another user\r\n          this.checkPageLock(pageId);\r\n\r\n          // Smart background refresh - only if cache is older than 2 minutes\r\n          const cacheAge = CacheService.getAge(cacheKey);\r\n          const minRefreshAge = 2 * 60 * 1000; // 2 minutes\r\n          if (!cacheAge || cacheAge > minRefreshAge) {\r\n            this.refreshPageInBackground(pageId, cacheKey);\r\n          }\r\n          return;\r\n        }\r\n\r\n        // No cache - fetch page and lock status in parallel\r\n        this.loading = true;\r\n        const [response] = await Promise.all([\r\n          axios.get(generateUrl(`/apps/intravox/api/pages/${pageId}`)),\r\n          this.checkPageLock(pageId),\r\n        ]);\r\n        this.currentPage = response.data;\r\n\r\n        // Breadcrumb is now included in page response\r\n        if (response.data.breadcrumb) {\r\n          this.breadcrumb = response.data.breadcrumb;\r\n        }\r\n\r\n        this.isEditMode = false;\r\n        this.showPages = false;\r\n\r\n        // Update cache\r\n        CacheService.set(cacheKey, response.data);\r\n\r\n        // Update URL hash if requested - use uniqueId for permanent links\r\n        if (updateUrl && this.currentPage) {\r\n          const pageIdentifier = this.currentPage.uniqueId;\r\n          const newHash = `#${pageIdentifier}`;\r\n          if (window.location.hash !== newHash) {\r\n            window.location.hash = newHash;\r\n          }\r\n        }\r\n\r\n        // Update page title and meta tags for better link previews\r\n        this.updatePageMetadata();\r\n      } catch (err) {\r\n        console.error('IntraVox: Error selecting page:', err);\r\n        showError(this.t('Could not load page: {error}', { error: err.message }));\r\n      } finally {\r\n        this.loading = false;\r\n      }\r\n    },\r\n    /**\r\n     * Refresh page data in background without blocking UI\r\n     * Used when showing cached data first\r\n     */\r\n    async refreshPageInBackground(pageId, cacheKey) {\r\n      try {\r\n        const response = await axios.get(generateUrl(`/apps/intravox/api/pages/${pageId}`));\r\n        // Only update if user is still on the same page\r\n        if (this.currentPage && this.currentPage.uniqueId === response.data.uniqueId) {\r\n          this.currentPage = response.data;\r\n          if (response.data.breadcrumb) {\r\n            this.breadcrumb = response.data.breadcrumb;\r\n          }\r\n        }\r\n        // Always update cache\r\n        CacheService.set(cacheKey, response.data);\r\n      } catch (err) {\r\n        // Silent fail for background refresh - user already has cached data\r\n      }\r\n    },\r\n    updatePageMetadata() {\r\n      if (!this.currentPage) return;\r\n\r\n      // Update document title\r\n      document.title = `${this.currentPage.title} - IntraVox`;\r\n\r\n      // Update or create Open Graph meta tags for better link previews\r\n      this.updateMetaTag('og:title', this.currentPage.title);\r\n      this.updateMetaTag('og:type', 'article');\r\n      this.updateMetaTag('og:url', window.location.href);\r\n      this.updateMetaTag('og:site_name', 'IntraVox');\r\n\r\n      // Twitter Card tags\r\n      this.updateMetaTag('twitter:card', 'summary', 'name');\r\n      this.updateMetaTag('twitter:title', this.currentPage.title, 'name');\r\n    },\r\n    updateMetaTag(property, content, attrName = 'property') {\r\n      // Try to find existing meta tag\r\n      let meta = document.querySelector(`meta[${attrName}=\"${property}\"]`);\r\n\r\n      if (!meta) {\r\n        // Create new meta tag if it doesn't exist\r\n        meta = document.createElement('meta');\r\n        meta.setAttribute(attrName, property);\r\n        document.head.appendChild(meta);\r\n      }\r\n\r\n      meta.setAttribute('content', content);\r\n    },\r\n    async startEditMode() {\r\n      const pageId = this.currentPage?.uniqueId;\r\n      if (!pageId) return;\r\n\r\n      try {\r\n        // Acquire page lock before entering edit mode\r\n        const lockUrl = generateUrl(`/apps/intravox/api/pages/${pageId}/lock`);\r\n        const response = await axios.post(lockUrl);\r\n\r\n        if (response.data.success) {\r\n          // Lock acquired — enter edit mode\r\n          this.originalPage = structuredClone(this.currentPage);\r\n          this.editableTitle = this.currentPage?.title || '';\r\n          this.isEditMode = true;\r\n          this.pageLock = null;\r\n\r\n          // Clear any version preview - user should edit current version\r\n          this.clearVersionPreview();\r\n\r\n          // Start heartbeat to keep lock alive\r\n          this.startLockHeartbeat(pageId);\r\n\r\n          // Notify sidebar to reset to current version selection\r\n          window.dispatchEvent(new CustomEvent('intravox:edit:started', {\r\n            detail: { pageId }\r\n          }));\r\n        }\r\n      } catch (err) {\r\n        if (err.response?.status === 409) {\r\n          // Lock denied — another user is editing\r\n          const lock = err.response.data.lock;\r\n          this.pageLock = lock;\r\n          showError(this.t('{displayName} is editing this page', {\r\n            displayName: lock?.displayName || 'Someone',\r\n          }));\r\n        } else {\r\n          showError(this.t('Could not start editing: {error}', { error: err.message }));\r\n        }\r\n      }\r\n    },\r\n    async saveAndExitEditMode() {\r\n      try {\r\n        // Update title if changed\r\n        if (this.editableTitle && this.editableTitle !== this.currentPage?.title) {\r\n          this.currentPage.title = this.editableTitle;\r\n        }\r\n\r\n        await this.savePage();\r\n\r\n        this.isEditMode = false;\r\n        this.originalPage = null;\r\n\r\n        // Release lock after successful save\r\n        await this.releaseLock();\r\n      } catch (err) {\r\n        console.error('[saveAndExitEditMode] Error:', err);\r\n        showError(this.t('Failed to save: {error}', { error: err.message }));\r\n      }\r\n    },\r\n    cancelEditMode() {\r\n      // Rollback to original state\r\n      if (this.originalPage) {\r\n        this.currentPage = structuredClone(this.originalPage);\r\n      }\r\n      this.isEditMode = false;\r\n      this.originalPage = null;\r\n      showSuccess(this.t('Changes cancelled'));\r\n\r\n      // Release lock after cancelling\r\n      this.releaseLock();\r\n    },\r\n    toggleDraftStatus() {\r\n      if (!this.currentPage) return;\r\n      this.currentPage.status = this.currentPage.status === 'draft' ? 'published' : 'draft';\r\n    },\r\n    /**\r\n     * Check if a page is locked by another user (called on page load)\r\n     */\r\n    async checkPageLock(pageId) {\r\n      try {\r\n        const url = generateUrl(`/apps/intravox/api/pages/${pageId}/lock`);\r\n        const response = await axios.get(url);\r\n        this.pageLock = response.data.lock || null;\r\n      } catch (err) {\r\n        this.pageLock = null;\r\n      }\r\n    },\r\n    /**\r\n     * Release the current page lock\r\n     */\r\n    async releaseLock() {\r\n      this.stopLockHeartbeat();\r\n      const pageId = this.currentPage?.uniqueId;\r\n      if (!pageId) return;\r\n\r\n      try {\r\n        const url = generateUrl(`/apps/intravox/api/pages/${pageId}/lock`);\r\n        await axios.delete(url);\r\n        this.pageLock = null;\r\n      } catch (err) {\r\n        // Best effort — lock will auto-expire after 15 minutes\r\n        console.warn('[IntraVox] Failed to release lock:', err.message);\r\n      }\r\n    },\r\n    /**\r\n     * Start heartbeat to keep the page lock alive\r\n     */\r\n    startLockHeartbeat(pageId) {\r\n      this.stopLockHeartbeat();\r\n      this.lockHeartbeatTimer = setInterval(async () => {\r\n        try {\r\n          const url = generateUrl(`/apps/intravox/api/pages/${pageId}/lock`);\r\n          const response = await axios.put(url);\r\n          if (!response.data.success) {\r\n            showError(this.t('Your edit lock has expired. Please save your work.'));\r\n            this.stopLockHeartbeat();\r\n          }\r\n        } catch (err) {\r\n          if (err.response?.status === 409) {\r\n            showError(this.t('Your edit lock has expired. Please save your work.'));\r\n            this.stopLockHeartbeat();\r\n          }\r\n          // On network error, keep trying — lock expires after 15 min\r\n        }\r\n      }, 60 * 1000); // Every 60 seconds\r\n    },\r\n    /**\r\n     * Stop the lock heartbeat interval\r\n     */\r\n    stopLockHeartbeat() {\r\n      if (this.lockHeartbeatTimer) {\r\n        clearInterval(this.lockHeartbeatTimer);\r\n        this.lockHeartbeatTimer = null;\r\n      }\r\n    },\r\n    /**\r\n     * Release lock when the browser tab/window is being closed\r\n     */\r\n    handleBeforeUnload() {\r\n      if (this.isEditMode && this.currentPage?.uniqueId) {\r\n        const url = generateUrl(`/apps/intravox/api/pages/${this.currentPage.uniqueId}/lock`);\r\n        fetch(window.location.origin + url, {\r\n          method: 'DELETE',\r\n          keepalive: true,\r\n          headers: { requesttoken: OC.requestToken },\r\n        });\r\n      }\r\n    },\r\n    /**\r\n     * Force-unlock a page (admin only)\r\n     */\r\n    async forceUnlock() {\r\n      const pageId = this.currentPage?.uniqueId;\r\n      const lockedBy = this.pageLock?.displayName || 'Someone';\r\n      if (!pageId) return;\r\n\r\n      if (!confirm(this.t('Are you sure you want to unlock this page? {displayName} may lose unsaved changes.', { displayName: lockedBy }))) {\r\n        return;\r\n      }\r\n\r\n      try {\r\n        const url = generateUrl(`/apps/intravox/api/pages/${pageId}/lock/force-release`);\r\n        await axios.post(url);\r\n        this.pageLock = null;\r\n        showSuccess(this.t('Page unlocked'));\r\n      } catch (err) {\r\n        showError(this.t('Could not unlock page: {error}', { error: err.message }));\r\n      }\r\n    },\r\n    async savePage() {\r\n      if (!this.currentPage || !this.currentPage.uniqueId) {\r\n        throw new Error('No page to save');\r\n      }\r\n\r\n      // Ensure uniqueId exists (for legacy pages that don't have it yet)\r\n      if (!this.currentPage.uniqueId) {\r\n        this.currentPage.uniqueId = this.generateUniqueId();\r\n      }\r\n\r\n      const url = generateUrl(`/apps/intravox/api/pages/${this.currentPage.uniqueId}`);\r\n\r\n      try {\r\n        await axios.put(url, this.currentPage);\r\n\r\n        // Invalidate cache for this page and pages list\r\n        CacheService.delete(`page-${this.currentPage.uniqueId}`);\r\n        CacheService.delete('pages-list');\r\n\r\n        showSuccess(this.t('Page saved'));\r\n\r\n        // Dispatch event to notify sidebar that a new version was created\r\n        window.dispatchEvent(new CustomEvent('intravox:page:saved', {\r\n          detail: { pageId: this.currentPage.uniqueId }\r\n        }));\r\n      } catch (err) {\r\n        console.error('[savePage] Error:', err);\r\n        const errorMsg = err.response?.data?.error || err.message || 'Unknown error';\r\n        showError(this.t('Could not save page: {error}', { error: errorMsg }));\r\n        throw err;\r\n      }\r\n    },\r\n    async updatePage(updatedPage) {\r\n      // Use structuredClone to ensure Vue reactivity captures all nested changes\r\n      this.currentPage = structuredClone(updatedPage);\r\n    },\r\n    createNewPage() {\r\n      this.showNewPageModal = true;\r\n    },\r\n    generateSlug(title) {\r\n      // Convert title to URL-friendly slug\r\n      return title.toLowerCase()\r\n        .normalize('NFD')\r\n        .replace(/[\\u0300-\\u036f]/g, '') // Remove diacritics\r\n        .replace(/[^a-z0-9]+/g, '-')\r\n        .replace(/^-+|-+$/g, '');\r\n    },\r\n    generateUniqueId() {\r\n      // Generate a UUID v4 for guaranteed uniqueness across servers\r\n      // This ensures no conflicts during migrations or multi-server scenarios\r\n      return `page-${crypto.randomUUID()}`;\r\n    },\r\n    async handleCreatePage(data) {\r\n      // Support both old format (string) and new format (object)\r\n      const title = typeof data === 'string' ? data : data.title;\r\n      const addToNavigation = typeof data === 'object' ? data.addToNavigation : false;\r\n\r\n      if (!title) return;\r\n\r\n      // Generate slug from title for folder name (readable)\r\n      const slug = this.generateSlug(title);\r\n\r\n      if (!slug) {\r\n        showError(this.t('Invalid page title'));\r\n        return;\r\n      }\r\n\r\n      // Generate unique ID for internal references\r\n      const uniqueId = this.generateUniqueId();\r\n\r\n      try {\r\n        const newPage = {\r\n          id: slug, // Use slug as the page ID (folder name)\r\n          uniqueId: uniqueId, // Store unique ID for internal references\r\n          title: title,\r\n          status: 'draft', // New pages start as draft\r\n          layout: {\r\n            columns: 1,\r\n            rows: [\r\n              {\r\n                widgets: [\r\n                  {\r\n                    id: 'widget-1',\r\n                    type: 'heading',\r\n                    content: title,\r\n                    level: 1,\r\n                    column: 1,\r\n                    order: 1\r\n                  }\r\n                ]\r\n              }\r\n            ]\r\n          }\r\n        };\r\n\r\n        // If we have a current page, create the new page as a child of the current page\r\n        // by using the current page's path as the parent path\r\n        if (this.currentPage && this.currentPage.path) {\r\n          // Use the current page's full path as the parent path\r\n          // This creates the new page as a child of the current page\r\n          newPage.parentPath = this.currentPage.path;\r\n        }\r\n\r\n        await axios.post(generateUrl('/apps/intravox/api/pages'), newPage);\r\n        showSuccess(this.t('Page created'));\r\n\r\n        // Reload pages first so the new page is in the array\r\n        await this.loadPages();\r\n\r\n        // Add to navigation if requested (after pages are loaded)\r\n        if (addToNavigation) {\r\n          await this.addPageToNavigation(slug, title);\r\n        }\r\n\r\n        await this.selectPage(slug);\r\n        // Open the new page in edit mode (with lock)\r\n        await this.startEditMode();\r\n      } catch (err) {\r\n        showError(this.t('Could not create page: {error}', { error: err.message }));\r\n      }\r\n    },\r\n    async handleCreatePageFromTemplate(data) {\r\n      const { templateId, title } = data;\r\n\r\n      if (!templateId || !title) {\r\n        showError(this.t('Missing template or title'));\r\n        return;\r\n      }\r\n\r\n      try {\r\n        const url = generateUrl('/apps/intravox/api/pages/from-template');\r\n        const response = await axios.post(url, {\r\n          templateId,\r\n          pageTitle: title,\r\n          parentPath: this.currentPage?.path || null\r\n        });\r\n\r\n        if (response.data.success) {\r\n          showSuccess(this.t('Page created from template'));\r\n\r\n          const newPage = response.data.page;\r\n\r\n          if (newPage?.uniqueId) {\r\n            // The backend already returned the freshly created page through\r\n            // PageService::getPage() (which runs enrichWithPathData + the\r\n            // sanitize pipeline) so we have a fully populated payload right\r\n            // here. Trying to re-fetch via selectPage() can race with\r\n            // pageDataCache / parent folder listings that haven't yet\r\n            // refreshed and intermittently 404 — sending the user back to\r\n            // the home page. Use the response directly and warm both the\r\n            // local pages array and the frontend CacheService so any\r\n            // subsequent navigation hits an immediate cache.\r\n\r\n            // 1. Append to local pages list (no loadPages refresh — that\r\n            //    re-runs its tail-end selectPage(home) which would steal\r\n            //    focus from the new page).\r\n            if (!this.pages.find(p => p.uniqueId === newPage.uniqueId)) {\r\n              this.pages.push({\r\n                uniqueId: newPage.uniqueId,\r\n                title: newPage.title,\r\n                path: newPage.path || null,\r\n                status: newPage.status || 'draft',\r\n                modified: newPage.modified || Math.floor(Date.now() / 1000),\r\n                permissions: newPage.permissions || { canRead: true, canWrite: true },\r\n              });\r\n              CacheService.delete('pages-list');\r\n            }\r\n\r\n            // 2. Warm the per-page cache with the backend response so\r\n            //    selectPage() finds it without an API roundtrip.\r\n            CacheService.set(`page-${newPage.uniqueId}`, newPage);\r\n\r\n            // 3. Update URL hash before navigating so any concurrent\r\n            //    page-list refresh doesn't redirect us home.\r\n            window.location.hash = `#${newPage.uniqueId}`;\r\n\r\n            // 4. Mount the editor.\r\n            this.currentPage = newPage;\r\n            this.breadcrumb = newPage.breadcrumb || [];\r\n            this.isEditMode = false;\r\n            this.showPages = false;\r\n\r\n            await this.$nextTick();\r\n            await this.startEditMode();\r\n          }\r\n        } else {\r\n          showError(this.t('Could not create page: {error}', { error: response.data.error || 'Unknown error' }));\r\n        }\r\n      } catch (err) {\r\n        console.error('[handleCreatePageFromTemplate] Error:', err);\r\n        showError(this.t('Could not create page from template: {error}', { error: err.message }));\r\n      }\r\n    },\r\n    handleTemplateSaved(template) {\r\n      showSuccess(this.t('Template saved: {name}', { name: template.title }));\r\n    },\r\n    async addPageToNavigation(pageId, pageTitle) {\r\n      try {\r\n        // Get the full page path to determine parent hierarchy\r\n        const page = this.pages.find(p => p.uniqueId === pageId);\r\n        if (!page) {\r\n          throw new Error('Page not found');\r\n        }\r\n\r\n        // Ensure navigation structure exists\r\n        if (!this.navigation || !this.navigation.items) {\r\n          showError(this.t('Navigation structure is invalid. Please reload the page.'));\r\n          return;\r\n        }\r\n\r\n        // Build the path hierarchy from the page's path\r\n        // Example path: \"en/team-sales/campaigns\" -> [\"team-sales\", \"campaigns\"]\r\n        const pathParts = page.path ? page.path.split('/').filter(part =>\r\n          part && !['en', 'nl', 'de', 'fr', 'departments'].includes(part)\r\n        ) : [];\r\n\r\n        // Helper function to find or create navigation item\r\n        const findOrCreateNavItem = (items, pageId, title) => {\r\n          let item = items.find(i => i.pageId === pageId);\r\n          if (!item) {\r\n            item = {\r\n              id: pageId,\r\n              title: title,\r\n              pageId: pageId,\r\n              url: null,\r\n              target: null,\r\n              children: []\r\n            };\r\n            items.push(item);\r\n          }\r\n          return item;\r\n        };\r\n\r\n        // Build the navigation hierarchy\r\n        let currentLevel = this.navigation.items;\r\n\r\n        // Handle root-level pages (pages with no parent path)\r\n        if (pathParts.length === 0) {\r\n          const newNavItem = {\r\n            id: pageId,\r\n            title: pageTitle,\r\n            pageId: pageId,\r\n            url: null,\r\n            target: null,\r\n            children: []\r\n          };\r\n\r\n          // Check if it already exists at root level\r\n          const existingIndex = currentLevel.findIndex(i => i.pageId === pageId);\r\n          if (existingIndex >= 0) {\r\n            currentLevel[existingIndex] = newNavItem;\r\n          } else {\r\n            currentLevel.push(newNavItem);\r\n          }\r\n        } else {\r\n          // For each part of the path except the last one (which is our new page)\r\n          for (let i = 0; i < pathParts.length; i++) {\r\n            const isLastPart = (i === pathParts.length - 1);\r\n            const partPageId = pathParts[i];\r\n\r\n            // Find the page to get its title\r\n            const partPage = this.pages.find(p => p.uniqueId === partPageId);\r\n            const partTitle = partPage ? partPage.title : partPageId;\r\n\r\n            // If this is the last part and it matches our pageId, this is our page\r\n            if (isLastPart && partPageId === pageId) {\r\n              const newNavItem = {\r\n                id: pageId,\r\n                title: pageTitle,\r\n                pageId: pageId,\r\n                url: null,\r\n                target: null,\r\n                children: []\r\n              };\r\n\r\n              const existingIndex = currentLevel.findIndex(i => i.pageId === pageId);\r\n              if (existingIndex >= 0) {\r\n                currentLevel[existingIndex] = newNavItem;\r\n              } else {\r\n                currentLevel.push(newNavItem);\r\n              }\r\n            } else {\r\n              // This is a parent, find or create it\r\n              const parentNavItem = findOrCreateNavItem(currentLevel, partPageId, partTitle);\r\n              currentLevel = parentNavItem.children;\r\n            }\r\n          }\r\n        }\r\n\r\n        // Save navigation (send the full navigation structure with type and items)\r\n        await axios.post(generateUrl('/apps/intravox/api/navigation'), {\r\n          navigation: this.navigation\r\n        });\r\n\r\n        showSuccess(this.t('Added to navigation'));\r\n      } catch (err) {\r\n        showError(this.t('Failed to add page to navigation'));\r\n      }\r\n    },\r\n    async deletePage(pageId) {\r\n      if (!confirm(this.t('Are you sure you want to delete this page?'))) {\r\n        return;\r\n      }\r\n\r\n      try {\r\n        await axios.delete(generateUrl(`/apps/intravox/api/pages/${pageId}`));\r\n        showSuccess(this.t('Page deleted'));\r\n        await this.loadPages();\r\n\r\n        if (this.currentPage?.uniqueId === pageId) {\r\n          this.currentPage = null;\r\n          if (this.pages.length > 0) {\r\n            await this.selectPage(this.pages[0].uniqueId);\r\n          }\r\n        }\r\n      } catch (err) {\r\n        showError(this.t('Could not delete page: {error}', { error: err.message }));\r\n      }\r\n    },\r\n    showPageList() {\r\n      this.showPages = true;\r\n    },\r\n    async loadNavigation() {\r\n      try {\r\n        const url = generateUrl('/apps/intravox/api/navigation');\r\n        const response = await axios.get(url);\r\n        this.navigation = response.data.navigation;\r\n        // Use permissions object if available, fall back to canEdit for backwards compatibility\r\n        const perms = response.data.permissions || {};\r\n        this.canEditNavigation = perms.canWrite ?? response.data.canEdit ?? false;\r\n      } catch (err) {\r\n        // Provide default empty navigation\r\n        this.navigation = {\r\n          type: 'dropdown',\r\n          items: []\r\n        };\r\n        this.canEditNavigation = false;\r\n      }\r\n    },\r\n    async saveNavigation(navigation) {\r\n      try {\r\n        const response = await axios.post(generateUrl('/apps/intravox/api/navigation'), navigation);\r\n        this.navigation = response.data.navigation;\r\n        this.showNavigationEditor = false;\r\n        showSuccess(this.t('Navigation saved'));\r\n      } catch (err) {\r\n        showError(this.t('Could not save navigation: {error}', { error: err.message }));\r\n      }\r\n    },\r\n    async loadFooter() {\r\n      try {\r\n        const response = await axios.get(generateUrl('/apps/intravox/api/footer'));\r\n        this.footerContent = response.data.content;\r\n        // Use permissions object if available, fall back to canEdit for backwards compatibility\r\n        const perms = response.data.permissions || {};\r\n        this.canEditFooter = perms.canWrite ?? response.data.canEdit ?? false;\r\n      } catch (err) {\r\n        this.footerContent = '';\r\n        this.canEditFooter = false;\r\n      }\r\n    },\r\n    async handleFooterSave(content) {\r\n      try {\r\n        const response = await axios.post(generateUrl('/apps/intravox/api/footer'), {\r\n          content: content\r\n        });\r\n        this.footerContent = response.data.content;\r\n        showSuccess(this.t('Footer saved'));\r\n      } catch (err) {\r\n        showError(this.t('Could not save footer: {error}', { error: err.message }));\r\n      }\r\n    },\r\n    navigateToItem(item) {\r\n      if (item.uniqueId) {\r\n        this.selectPage(item.uniqueId);\r\n      } else if (item.pageId) {\r\n        // Legacy support for old pageId\r\n        this.selectPage(item.pageId);\r\n      } else if (item.url) {\r\n        if (item.url.startsWith('http') || item.url.startsWith('//')) {\r\n          window.open(item.url, '_blank');\r\n        } else {\r\n          window.location.href = item.url;\r\n        }\r\n      }\r\n    },\r\n    handleLanguageChange() {\r\n      // Only reload navigation - pages structure doesn't change with language\r\n      this.loadNavigation();\r\n      // Force Vue to re-render all translated strings\r\n      this.$forceUpdate();\r\n    },\r\n    async handleVersionRestored(restoredPageData) {\r\n      showSuccess(this.t('Version restored successfully'));\r\n\r\n      // Reload pages list to update timestamps, but stay on current page\r\n      const currentPageId = restoredPageData.uniqueId || this.currentPage?.uniqueId;\r\n\r\n      try {\r\n        // Invalidate cache for this page\r\n        CacheService.delete(`page-${currentPageId}`);\r\n        CacheService.delete('pages-list');\r\n\r\n        // Reload pages list\r\n        const response = await axios.get(generateUrl('/apps/intravox/api/pages'));\r\n        this.pages = response.data;\r\n\r\n        // Re-fetch the page to get the fully restored content\r\n        await this.selectPage(currentPageId, false);\r\n\r\n        // Ensure version preview is cleared - prevents race condition\r\n        // where sidebar's version-selected event could re-set versionPage\r\n        this.clearVersionPreview();\r\n\r\n        // Open sidebar with Versions tab\r\n        this.sidebarInitialTab = 'versions-tab';\r\n        this.showDetailsSidebar = true;\r\n      } catch (err) {\r\n        console.error('IntraVox: Error reloading pages after restore:', err);\r\n      }\r\n    },\r\n    handleHashChange() {\r\n      // Handle URL hash changes for navigation\r\n      const hash = window.location.hash;\r\n      if (!hash || hash === '#') {\r\n        // No hash, load home page\r\n        const homePage = findHomePage(this.pages);\r\n        if (homePage) {\r\n          this.selectPage(homePage.uniqueId, false);\r\n        }\r\n        return;\r\n      }\r\n\r\n      const pageIdentifier = hash.substring(1); // Remove '#'\r\n\r\n      // Find page by uniqueId\r\n      const targetPage = this.pages.find(p => p.uniqueId === pageIdentifier);\r\n\r\n      if (targetPage) {\r\n        // Don't update URL since we're already responding to a hash change\r\n        this.selectPage(targetPage.uniqueId, false);\r\n      } else {\r\n        // Fall back to home\r\n        const homePage = findHomePage(this.pages);\r\n        if (homePage) {\r\n          this.selectPage(homePage.uniqueId, true);\r\n        }\r\n      }\r\n    },\r\n    async handleVersionSelected(data) {\r\n      const { version, pageId } = data;\r\n\r\n      // If version is null, clear the preview (show current page)\r\n      if (!version) {\r\n        this.clearVersionPreview();\r\n        return;\r\n      }\r\n\r\n      this.selectedVersion = version;\r\n      this.loadingVersion = true;\r\n\r\n      try {\r\n        // Fetch the version content\r\n        const url = generateUrl(`/apps/intravox/api/pages/${pageId}/versions/${version.timestamp}/content`);\r\n        const response = await axios.get(url);\r\n\r\n        // Parse the JSON content from the version\r\n        const versionJson = JSON.parse(response.data.content);\r\n\r\n        // Create a page object from the version data\r\n        this.versionPage = {\r\n          ...versionJson,\r\n          id: pageId,\r\n          // Add a visual indicator that this is a version\r\n          title: `${versionJson.title || this.currentPage.title} (${this.formatVersionDate(version.timestamp)})`\r\n        };\r\n      } catch (err) {\r\n        showError(this.t('Could not load version: {error}', { error: err.message }));\r\n        this.selectedVersion = null;\r\n        this.versionPage = null;\r\n      } finally {\r\n        this.loadingVersion = false;\r\n      }\r\n    },\r\n    clearVersionPreview() {\r\n      this.selectedVersion = null;\r\n      this.versionPage = null;\r\n    },\r\n    formatVersionDate(timestamp) {\r\n      const date = new Date(timestamp * 1000);\r\n      return date.toLocaleString();\r\n    },\r\n    handleCloseSidebar() {\r\n      this.showDetailsSidebar = false;\r\n      // Reset to default tab when closing\r\n      this.sidebarInitialTab = 'details-tab';\r\n      // Clear version preview when closing sidebar\r\n      this.clearVersionPreview();\r\n    },\r\n    async loadEngagementSettings() {\r\n      try {\r\n        const cached = CacheService.get('engagement-settings');\r\n        if (cached) {\r\n          this.globalEngagementSettings = cached;\r\n          return;\r\n        }\r\n        const response = await axios.get(generateUrl('/apps/intravox/api/settings/engagement'));\r\n        this.globalEngagementSettings = response.data;\r\n        CacheService.set('engagement-settings', response.data);\r\n      } catch (err) {\r\n        // Silent fail - use defaults\r\n      }\r\n    },\r\n    async handlePageSettingsSave(settings) {\r\n      if (!this.currentPage) return;\r\n\r\n      // Initialize settings object if it doesn't exist\r\n      if (!this.currentPage.settings) {\r\n        this.currentPage.settings = {};\r\n      }\r\n\r\n      // Update page settings\r\n      this.currentPage.settings.allowReactions = settings.allowReactions;\r\n      this.currentPage.settings.allowComments = settings.allowComments;\r\n      this.currentPage.settings.allowCommentReactions = settings.allowCommentReactions;\r\n\r\n      try {\r\n        await this.savePage();\r\n        this.showPageSettingsModal = false;\r\n        showSuccess(this.t('Page settings saved'));\r\n      } catch (err) {\r\n        showError(this.t('Could not save page settings: {error}', { error: err.message }));\r\n      }\r\n    }\r\n  }\r\n};\r\n</script>\r\n\r\n<style scoped>\r\n#intravox-app {\r\n  width: 100%;\r\n  max-width: 100vw;\r\n  min-height: 100vh;\r\n  background: var(--color-main-background);\r\n  overflow-x: clip;\r\n  box-sizing: border-box;\r\n}\r\n\r\n/* Navigation Bar */\r\n.intravox-nav-bar {\r\n  background: var(--color-main-background);\r\n  border-bottom: 1px solid var(--color-border);\r\n  min-height: 50px;\r\n  display: flex;\r\n  align-items: center;\r\n  width: 100%;\r\n  box-sizing: border-box;\r\n  position: relative;\r\n  overflow: visible;\r\n}\r\n\r\n/* App Content Wrapper - contains main content and sidebar */\r\n.app-content-wrapper {\r\n  display: flex;\r\n  position: relative;\r\n  flex: 1;\r\n  min-height: 0;\r\n  width: 100%;\r\n}\r\n\r\n/* Sticky topbar wrapping header + nav. Keeps page navigation in reach\r\n   on long pages (e.g. Photo Story timelines with hundreds of photos). */\r\n.intravox-topbar {\r\n  position: sticky;\r\n  top: 0;\r\n  z-index: 100;\r\n  background: var(--color-main-background);\r\n}\r\n\r\n/* Header */\r\n.intravox-header {\r\n  display: flex;\r\n  justify-content: space-between;\r\n  align-items: center;\r\n  padding: 20px;\r\n  background: var(--color-main-background);\r\n  border-bottom: 1px solid var(--color-border);\r\n  gap: 20px;\r\n  width: 100%;\r\n  box-sizing: border-box;\r\n}\r\n\r\n.header-left {\r\n  flex: 1 1 auto;\r\n  min-width: 0;\r\n  overflow: visible;\r\n}\r\n\r\n.header-right {\r\n  flex: 0 0 auto;\r\n  display: flex;\r\n  gap: 10px;\r\n  align-items: center;\r\n}\r\n\r\n.draft-badge.draft {\r\n  display: inline-block;\r\n  padding: 4px 12px;\r\n  border-radius: 12px;\r\n  font-size: 12px;\r\n  font-weight: 600;\r\n  text-transform: uppercase;\r\n  letter-spacing: 0.5px;\r\n  background: var(--color-warning-light, #fff3cd);\r\n  color: var(--color-warning-text, #6d5003);\r\n  border: 1px solid var(--color-warning, #ffc107);\r\n}\r\n\r\n.page-lock-indicator {\r\n  color: var(--color-warning-text);\r\n  font-size: 13px;\r\n  white-space: nowrap;\r\n}\r\n\r\n.header-left h1 {\r\n  margin: 0;\r\n  font-size: 24px;\r\n  color: var(--color-main-text);\r\n  white-space: nowrap;\r\n  overflow: hidden;\r\n  text-overflow: ellipsis;\r\n}\r\n\r\n.page-title-input {\r\n  margin: 0;\r\n  padding: 8px 12px;\r\n  font-size: 24px;\r\n  font-weight: bold;\r\n  color: var(--color-main-text);\r\n  background: var(--color-main-background);\r\n  border: 2px solid var(--color-border-dark);\r\n  border-radius: 3px;\r\n  min-width: 300px;\r\n  box-sizing: border-box;\r\n}\r\n\r\n.page-title-input:focus {\r\n  outline: none;\r\n  border-color: var(--color-primary);\r\n}\r\n\r\n.intravox-content {\r\n  padding: 20px;\r\n  padding-bottom: 60px; /* Extra space at bottom */\r\n  max-width: min(1600px, 95vw);\r\n  margin: 0 auto;\r\n  width: 100%;\r\n  min-height: calc(100vh - 200px); /* Ensure content takes up space */\r\n  box-sizing: border-box;\r\n  flex: 1;\r\n  overflow-y: auto;\r\n}\r\n\r\n/* Breadcrumb row with Details button */\r\n.breadcrumb-row {\r\n  display: flex;\r\n  align-items: center;\r\n  justify-content: space-between;\r\n  margin-bottom: 8px;\r\n  gap: 16px;\r\n}\r\n\r\n.breadcrumb-spacer {\r\n  flex: 1;\r\n}\r\n\r\n.details-btn {\r\n  display: flex;\r\n  align-items: center;\r\n  justify-content: center;\r\n  width: 36px;\r\n  height: 36px;\r\n  padding: 0;\r\n  background: none;\r\n  border: none;\r\n  border-radius: var(--border-radius);\r\n  color: var(--color-main-text);\r\n  cursor: pointer;\r\n  transition: background-color 0.1s ease;\r\n  flex-shrink: 0;\r\n}\r\n\r\n.details-btn:hover {\r\n  background-color: var(--color-background-hover);\r\n}\r\n\r\n.details-btn:active {\r\n  background-color: var(--color-primary-element-light);\r\n}\r\n\r\n.details-btn-disabled,\r\n.details-btn:disabled {\r\n  opacity: 0.4;\r\n  cursor: default;\r\n  pointer-events: none;\r\n}\r\n\r\n.loading, .error {\r\n  padding: 40px;\r\n  text-align: center;\r\n  font-size: 16px;\r\n  color: var(--color-text-maxcontrast);\r\n  flex: 1;\r\n}\r\n\r\n.error {\r\n  color: var(--color-error);\r\n}\r\n\r\n/* Mobile styles */\r\n@media (max-width: 768px) {\r\n  #intravox-app {\r\n    overflow-x: clip;\r\n  }\r\n\r\n  .intravox-header {\r\n    padding: 12px 8px;\r\n    width: 100%;\r\n    box-sizing: border-box;\r\n  }\r\n\r\n  .intravox-header h1 {\r\n    font-size: 18px;\r\n  }\r\n\r\n  .intravox-nav-bar {\r\n    width: 100%;\r\n    box-sizing: border-box;\r\n  }\r\n\r\n  .intravox-content {\r\n    padding: 8px;\r\n    padding-bottom: 40px;\r\n    max-width: 100%;\r\n    width: 100%;\r\n    box-sizing: border-box;\r\n  }\r\n\r\n  .page-title-input {\r\n    min-width: 0;\r\n    width: 100%;\r\n    max-width: 250px;\r\n    font-size: 18px;\r\n  }\r\n\r\n  .header-right {\r\n    gap: 4px;\r\n  }\r\n}\r\n</style>\r\n"],"sourceRoot":""}]);
// Exports
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (___CSS_LOADER_EXPORT___);


/***/ },

/***/ "./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/dist/stylePostLoader.js!./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/Breadcrumb.vue?vue&type=style&index=0&id=6f46de9a&scoped=true&lang=css"
/*!***********************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/dist/stylePostLoader.js!./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/Breadcrumb.vue?vue&type=style&index=0&id=6f46de9a&scoped=true&lang=css ***!
  \***********************************************************************************************************************************************************************************************************************************************************/
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
.breadcrumb[data-v-6f46de9a] {
	padding: 8px 0;
	margin-bottom: 8px;
	border-bottom: 1px solid var(--color-border);
}
.breadcrumb-list[data-v-6f46de9a] {
	display: flex;
	align-items: center;
	list-style: none;
	padding: 0;
	margin: 0;
	flex-wrap: wrap;
	gap: 4px;
}
.breadcrumb-item[data-v-6f46de9a] {
	display: flex;
	align-items: center;
	font-size: 14px;
}
.breadcrumb-link[data-v-6f46de9a] {
	color: var(--color-primary-element);
	text-decoration: none;
	padding: 4px 8px;
	border-radius: var(--border-radius);
	transition: background-color 0.1s ease;
}
.breadcrumb-link[data-v-6f46de9a]:hover {
	background-color: var(--color-background-hover);
}
.breadcrumb-current-page[data-v-6f46de9a] {
	font-weight: 600;
	color: var(--color-main-text);
}
.breadcrumb-separator[data-v-6f46de9a] {
	margin: 0 4px;
	color: var(--color-text-lighter);
	flex-shrink: 0;
}

/* Mobile responsive */
@media (max-width: 768px) {
.breadcrumb-list[data-v-6f46de9a] {
		font-size: 13px;
}
.breadcrumb-link[data-v-6f46de9a],
	.breadcrumb-current[data-v-6f46de9a] {
		padding: 2px 6px;
}
}
`, "",{"version":3,"sources":["webpack://./src/components/Breadcrumb.vue"],"names":[],"mappings":";AAuDA;CACC,cAAc;CACd,kBAAkB;CAClB,4CAA4C;AAC7C;AAEA;CACC,aAAa;CACb,mBAAmB;CACnB,gBAAgB;CAChB,UAAU;CACV,SAAS;CACT,eAAe;CACf,QAAQ;AACT;AAEA;CACC,aAAa;CACb,mBAAmB;CACnB,eAAe;AAChB;AAEA;CACC,mCAAmC;CACnC,qBAAqB;CACrB,gBAAgB;CAChB,mCAAmC;CACnC,sCAAsC;AACvC;AAEA;CACC,+CAA+C;AAChD;AAEA;CACC,gBAAgB;CAChB,6BAA6B;AAC9B;AAEA;CACC,aAAa;CACb,gCAAgC;CAChC,cAAc;AACf;;AAEA,sBAAsB;AACtB;AACC;EACC,eAAe;AAChB;AAEA;;EAEC,gBAAgB;AACjB;AACD","sourcesContent":["<template>\r\n\t<nav v-if=\"breadcrumb && breadcrumb.length > 0\" class=\"breadcrumb\" aria-label=\"Breadcrumb\">\r\n\t\t<ol class=\"breadcrumb-list\">\r\n\t\t\t<li v-for=\"(item, index) in breadcrumbItems\"\r\n\t\t\t    :key=\"item.id\"\r\n\t\t\t    class=\"breadcrumb-item\">\r\n\r\n\t\t\t\t<a :href=\"item.url\"\r\n\t\t\t\t   @click.prevent=\"navigateToPage(item.uniqueId || item.id)\"\r\n\t\t\t\t   :class=\"{'breadcrumb-link': true, 'breadcrumb-current-page': item.current}\"\r\n\t\t\t\t   :aria-current=\"item.current ? 'page' : undefined\">\r\n\t\t\t\t\t{{ item.title }}\r\n\t\t\t\t</a>\r\n\r\n\t\t\t\t<ChevronRight v-if=\"index < breadcrumbItems.length - 1\"\r\n\t\t\t\t              :size=\"16\"\r\n\t\t\t\t              class=\"breadcrumb-separator\" />\r\n\t\t\t</li>\r\n\t\t</ol>\r\n\t</nav>\r\n</template>\r\n\r\n<script>\r\nimport ChevronRight from 'vue-material-design-icons/ChevronRight.vue'\r\n\r\nexport default {\r\n\tname: 'Breadcrumb',\r\n\tcomponents: {\r\n\t\tChevronRight\r\n\t},\r\n\tprops: {\r\n\t\tbreadcrumb: {\r\n\t\t\ttype: Array,\r\n\t\t\trequired: true,\r\n\t\t\tdefault: () => []\r\n\t\t}\r\n\t},\r\n\tcomputed: {\r\n\t\t// Return all breadcrumb items including the current page\r\n\t\tbreadcrumbItems() {\r\n\t\t\tif (!this.breadcrumb || this.breadcrumb.length === 0) {\r\n\t\t\t\treturn []\r\n\t\t\t}\r\n\t\t\treturn this.breadcrumb\r\n\t\t}\r\n\t},\r\n\tmethods: {\r\n\t\tnavigateToPage(pageId) {\r\n\t\t\tthis.$emit('navigate', pageId)\r\n\t\t}\r\n\t}\r\n}\r\n</script>\r\n\r\n<style scoped>\r\n.breadcrumb {\r\n\tpadding: 8px 0;\r\n\tmargin-bottom: 8px;\r\n\tborder-bottom: 1px solid var(--color-border);\r\n}\r\n\r\n.breadcrumb-list {\r\n\tdisplay: flex;\r\n\talign-items: center;\r\n\tlist-style: none;\r\n\tpadding: 0;\r\n\tmargin: 0;\r\n\tflex-wrap: wrap;\r\n\tgap: 4px;\r\n}\r\n\r\n.breadcrumb-item {\r\n\tdisplay: flex;\r\n\talign-items: center;\r\n\tfont-size: 14px;\r\n}\r\n\r\n.breadcrumb-link {\r\n\tcolor: var(--color-primary-element);\r\n\ttext-decoration: none;\r\n\tpadding: 4px 8px;\r\n\tborder-radius: var(--border-radius);\r\n\ttransition: background-color 0.1s ease;\r\n}\r\n\r\n.breadcrumb-link:hover {\r\n\tbackground-color: var(--color-background-hover);\r\n}\r\n\r\n.breadcrumb-current-page {\r\n\tfont-weight: 600;\r\n\tcolor: var(--color-main-text);\r\n}\r\n\r\n.breadcrumb-separator {\r\n\tmargin: 0 4px;\r\n\tcolor: var(--color-text-lighter);\r\n\tflex-shrink: 0;\r\n}\r\n\r\n/* Mobile responsive */\r\n@media (max-width: 768px) {\r\n\t.breadcrumb-list {\r\n\t\tfont-size: 13px;\r\n\t}\r\n\r\n\t.breadcrumb-link,\r\n\t.breadcrumb-current {\r\n\t\tpadding: 2px 6px;\r\n\t}\r\n}\r\n</style>\r\n"],"sourceRoot":""}]);
// Exports
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (___CSS_LOADER_EXPORT___);


/***/ },

/***/ "./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/dist/stylePostLoader.js!./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/Footer.vue?vue&type=style&index=0&id=40ab164b&scoped=true&lang=css"
/*!*******************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/dist/stylePostLoader.js!./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/Footer.vue?vue&type=style&index=0&id=40ab164b&scoped=true&lang=css ***!
  \*******************************************************************************************************************************************************************************************************************************************************/
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
.intravox-footer[data-v-40ab164b] {
  width: 100%;
  max-width: 100vw;
  background: var(--color-main-background);
  box-sizing: border-box;
  overflow-x: hidden;
}
.footer-wrapper[data-v-40ab164b] {
  max-width: min(1600px, 95vw);
  margin: 0 auto;
  padding: 0 20px 60px 20px;
  box-sizing: border-box;
}
.footer-content-and-actions[data-v-40ab164b] {
  display: flex;
  align-items: flex-start;
  gap: 20px;
}
.footer-content-wrapper[data-v-40ab164b] {
  flex: 1;
}
.footer-content[data-v-40ab164b] {
  padding: 16px;
  color: var(--color-text-maxcontrast);
  font-size: 14px;
  line-height: 1.6;
  text-align: center;
}
.footer-actions[data-v-40ab164b] {
  flex-shrink: 0;
  align-self: flex-start;
}
.footer-edit-actions[data-v-40ab164b] {
  display: flex;
  gap: 12px;
  flex-shrink: 0;
}
.footer-content[data-v-40ab164b] p {
  margin: 0.5em 0;
  color: inherit !important;
}
.footer-content[data-v-40ab164b] a {
  color: var(--color-primary);
  text-decoration: none;
}
.footer-content[data-v-40ab164b] a:hover {
  text-decoration: underline;
}
.footer-content[data-v-40ab164b] strong,
.footer-content[data-v-40ab164b] em,
.footer-content[data-v-40ab164b] u,
.footer-content[data-v-40ab164b] s,
.footer-content[data-v-40ab164b] ul,
.footer-content[data-v-40ab164b] ol,
.footer-content[data-v-40ab164b] li {
  color: inherit !important;
}

/* Text alignment */
.footer-content[data-v-40ab164b] .text-align-center { text-align: center;
}
.footer-content[data-v-40ab164b] .text-align-right { text-align: right;
}

/* Blockquote */
.footer-content[data-v-40ab164b] blockquote {
  border-left: 4px solid var(--color-primary-element);
  padding-left: 1em;
  margin: 1em 0;
  color: inherit !important;
  font-style: italic;
}
.footer-editor[data-v-40ab164b] {
  min-height: 100px;
  background: var(--color-main-background);
  border: 1px solid var(--color-border);
  border-radius: var(--border-radius);
  padding: 12px;
}

/* Mobile styles */
@media (max-width: 768px) {
.footer-wrapper[data-v-40ab164b] {
    padding: 0 8px 40px 8px;
    max-width: 100%;
}
.footer-content-and-actions[data-v-40ab164b] {
    flex-direction: column;
    gap: 12px;
}
.footer-actions[data-v-40ab164b],
  .footer-edit-actions[data-v-40ab164b] {
    width: 100%;
    justify-content: flex-start;
}
.footer-content[data-v-40ab164b] {
    padding: 12px 8px;
    font-size: 13px;
}
}
`, "",{"version":3,"sources":["webpack://./src/components/Footer.vue"],"names":[],"mappings":";AA8KA;EACE,WAAW;EACX,gBAAgB;EAChB,wCAAwC;EACxC,sBAAsB;EACtB,kBAAkB;AACpB;AAEA;EACE,4BAA4B;EAC5B,cAAc;EACd,yBAAyB;EACzB,sBAAsB;AACxB;AAEA;EACE,aAAa;EACb,uBAAuB;EACvB,SAAS;AACX;AAEA;EACE,OAAO;AACT;AAEA;EACE,aAAa;EACb,oCAAoC;EACpC,eAAe;EACf,gBAAgB;EAChB,kBAAkB;AACpB;AAEA;EACE,cAAc;EACd,sBAAsB;AACxB;AAEA;EACE,aAAa;EACb,SAAS;EACT,cAAc;AAChB;AAEA;EACE,eAAe;EACf,yBAAyB;AAC3B;AAEA;EACE,2BAA2B;EAC3B,qBAAqB;AACvB;AAEA;EACE,0BAA0B;AAC5B;AAEA;;;;;;;EAOE,yBAAyB;AAC3B;;AAEA,mBAAmB;AACnB,sDAA4C,kBAAkB;AAAE;AAChE,qDAA2C,iBAAiB;AAAE;;AAE9D,eAAe;AACf;EACE,mDAAmD;EACnD,iBAAiB;EACjB,aAAa;EACb,yBAAyB;EACzB,kBAAkB;AACpB;AAEA;EACE,iBAAiB;EACjB,wCAAwC;EACxC,qCAAqC;EACrC,mCAAmC;EACnC,aAAa;AACf;;AAEA,kBAAkB;AAClB;AACE;IACE,uBAAuB;IACvB,eAAe;AACjB;AAEA;IACE,sBAAsB;IACtB,SAAS;AACX;AAEA;;IAEE,WAAW;IACX,2BAA2B;AAC7B;AAEA;IACE,iBAAiB;IACjB,eAAe;AACjB;AACF","sourcesContent":["<template>\r\n  <footer class=\"intravox-footer\">\r\n    <div class=\"footer-wrapper\">\r\n      <div class=\"footer-content-and-actions\">\r\n        <!-- Footer Editor or Content -->\r\n        <div class=\"footer-content-wrapper\">\r\n          <InlineTextEditor\r\n            v-if=\"isEditingFooter\"\r\n            v-model=\"editableContent\"\r\n            :editable=\"true\"\r\n            :placeholder=\"t('Enter footer content...')\"\r\n            class=\"footer-editor\"\r\n          />\r\n          <div v-else class=\"footer-content\" v-html=\"footerHtml\"></div>\r\n        </div>\r\n\r\n        <!-- 3-dot Action Menu (shown when not editing) -->\r\n        <div v-if=\"canEdit && isHomePage && !isEditingFooter\" class=\"footer-actions\">\r\n          <NcActions>\r\n            <NcActionButton @click=\"startEditFooter\">\r\n              <template #icon>\r\n                <Pencil :size=\"20\" />\r\n              </template>\r\n              {{ t('Edit footer') }}\r\n            </NcActionButton>\r\n          </NcActions>\r\n        </div>\r\n\r\n        <!-- Save/Cancel Buttons (shown when editing) -->\r\n        <div v-if=\"isEditingFooter\" class=\"footer-edit-actions\">\r\n          <NcButton\r\n            @click=\"cancelEditFooter\"\r\n            :aria-label=\"t('Cancel')\"\r\n          >\r\n            <template #icon>\r\n              <Close :size=\"20\" />\r\n            </template>\r\n            {{ t('Cancel') }}\r\n          </NcButton>\r\n          <NcButton\r\n            type=\"primary\"\r\n            @click=\"saveFooter\"\r\n            :aria-label=\"t('Save')\"\r\n          >\r\n            <template #icon>\r\n              <ContentSave :size=\"20\" />\r\n            </template>\r\n            {{ t('Save') }}\r\n          </NcButton>\r\n        </div>\r\n      </div>\r\n    </div>\r\n  </footer>\r\n</template>\r\n\r\n<script>\r\nimport { translate as t } from '@nextcloud/l10n';\r\nimport { showSuccess } from '@nextcloud/dialogs';\r\nimport { defineAsyncComponent } from 'vue';\r\nimport { NcButton, NcActions, NcActionButton } from '@nextcloud/vue';\r\nimport Pencil from 'vue-material-design-icons/Pencil.vue';\r\nimport Close from 'vue-material-design-icons/Close.vue';\r\nimport ContentSave from 'vue-material-design-icons/ContentSave.vue';\r\nimport { markdownToHtml } from '../utils/markdownSerializer.js';\r\n\r\n// Async to match Widget.vue's strategy.\r\nconst InlineTextEditor = defineAsyncComponent(() => import('./InlineTextEditor.vue'));\r\n\r\nexport default {\r\n  name: 'Footer',\r\n  components: {\r\n    NcButton,\r\n    NcActions,\r\n    NcActionButton,\r\n    Pencil,\r\n    Close,\r\n    ContentSave,\r\n    InlineTextEditor\r\n  },\r\n  props: {\r\n    footerContent: {\r\n      type: String,\r\n      default: ''\r\n    },\r\n    canEdit: {\r\n      type: Boolean,\r\n      default: false\r\n    },\r\n    isHomePage: {\r\n      type: Boolean,\r\n      default: false\r\n    }\r\n  },\r\n  emits: ['save', 'navigate'],\r\n  data() {\r\n    return {\r\n      isEditingFooter: false,\r\n      editableContent: '',\r\n      originalContent: ''\r\n    };\r\n  },\r\n  mounted() {\r\n    this.attachLinkHandlers();\r\n  },\r\n  updated() {\r\n    // Reattach handlers when content changes\r\n    if (!this.isEditingFooter) {\r\n      this.$nextTick(() => {\r\n        this.attachLinkHandlers();\r\n      });\r\n    }\r\n  },\r\n  computed: {\r\n    footerHtml() {\r\n      // Convert markdown to HTML for display\r\n      return markdownToHtml(this.footerContent || '');\r\n    }\r\n  },\r\n  methods: {\r\n    t(key, vars = {}) {\r\n      return t('intravox', key, vars);\r\n    },\r\n    startEditFooter() {\r\n      this.originalContent = this.footerContent;\r\n      this.editableContent = this.footerContent;\r\n      this.isEditingFooter = true;\r\n    },\r\n    cancelEditFooter() {\r\n      this.isEditingFooter = false;\r\n      this.editableContent = '';\r\n      this.originalContent = '';\r\n      showSuccess(this.t('Changes cancelled'));\r\n    },\r\n    saveFooter() {\r\n      // Emit save event to parent - parent will handle API call and notifications\r\n      this.$emit('save', this.editableContent);\r\n      this.isEditingFooter = false;\r\n      this.originalContent = '';\r\n    },\r\n    attachLinkHandlers() {\r\n      // Get all links in the footer content\r\n      const footerEl = this.$el?.querySelector('.footer-content');\r\n      if (!footerEl) return;\r\n\r\n      const links = footerEl.querySelectorAll('a');\r\n      links.forEach(link => {\r\n        const href = link.getAttribute('href');\r\n\r\n        // Only handle internal navigation links (those starting with #)\r\n        if (href && href.startsWith('#') && href.length > 1) {\r\n          // Remove any existing click handler\r\n          const oldHandler = link._clickHandler;\r\n          if (oldHandler) {\r\n            link.removeEventListener('click', oldHandler);\r\n          }\r\n\r\n          // Create and store new handler\r\n          const clickHandler = (e) => {\r\n            e.preventDefault();\r\n            // Remove the # and emit the pageId\r\n            const pageId = href.substring(1);\r\n            this.$emit('navigate', pageId);\r\n          };\r\n\r\n          link._clickHandler = clickHandler;\r\n          link.addEventListener('click', clickHandler);\r\n        }\r\n      });\r\n    }\r\n  }\r\n};\r\n</script>\r\n\r\n<style scoped>\r\n.intravox-footer {\r\n  width: 100%;\r\n  max-width: 100vw;\r\n  background: var(--color-main-background);\r\n  box-sizing: border-box;\r\n  overflow-x: hidden;\r\n}\r\n\r\n.footer-wrapper {\r\n  max-width: min(1600px, 95vw);\r\n  margin: 0 auto;\r\n  padding: 0 20px 60px 20px;\r\n  box-sizing: border-box;\r\n}\r\n\r\n.footer-content-and-actions {\r\n  display: flex;\r\n  align-items: flex-start;\r\n  gap: 20px;\r\n}\r\n\r\n.footer-content-wrapper {\r\n  flex: 1;\r\n}\r\n\r\n.footer-content {\r\n  padding: 16px;\r\n  color: var(--color-text-maxcontrast);\r\n  font-size: 14px;\r\n  line-height: 1.6;\r\n  text-align: center;\r\n}\r\n\r\n.footer-actions {\r\n  flex-shrink: 0;\r\n  align-self: flex-start;\r\n}\r\n\r\n.footer-edit-actions {\r\n  display: flex;\r\n  gap: 12px;\r\n  flex-shrink: 0;\r\n}\r\n\r\n.footer-content :deep(p) {\r\n  margin: 0.5em 0;\r\n  color: inherit !important;\r\n}\r\n\r\n.footer-content :deep(a) {\r\n  color: var(--color-primary);\r\n  text-decoration: none;\r\n}\r\n\r\n.footer-content :deep(a:hover) {\r\n  text-decoration: underline;\r\n}\r\n\r\n.footer-content :deep(strong),\r\n.footer-content :deep(em),\r\n.footer-content :deep(u),\r\n.footer-content :deep(s),\r\n.footer-content :deep(ul),\r\n.footer-content :deep(ol),\r\n.footer-content :deep(li) {\r\n  color: inherit !important;\r\n}\r\n\r\n/* Text alignment */\r\n.footer-content :deep(.text-align-center) { text-align: center; }\r\n.footer-content :deep(.text-align-right) { text-align: right; }\r\n\r\n/* Blockquote */\r\n.footer-content :deep(blockquote) {\r\n  border-left: 4px solid var(--color-primary-element);\r\n  padding-left: 1em;\r\n  margin: 1em 0;\r\n  color: inherit !important;\r\n  font-style: italic;\r\n}\r\n\r\n.footer-editor {\r\n  min-height: 100px;\r\n  background: var(--color-main-background);\r\n  border: 1px solid var(--color-border);\r\n  border-radius: var(--border-radius);\r\n  padding: 12px;\r\n}\r\n\r\n/* Mobile styles */\r\n@media (max-width: 768px) {\r\n  .footer-wrapper {\r\n    padding: 0 8px 40px 8px;\r\n    max-width: 100%;\r\n  }\r\n\r\n  .footer-content-and-actions {\r\n    flex-direction: column;\r\n    gap: 12px;\r\n  }\r\n\r\n  .footer-actions,\r\n  .footer-edit-actions {\r\n    width: 100%;\r\n    justify-content: flex-start;\r\n  }\r\n\r\n  .footer-content {\r\n    padding: 12px 8px;\r\n    font-size: 13px;\r\n  }\r\n}\r\n</style>\r\n"],"sourceRoot":""}]);
// Exports
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (___CSS_LOADER_EXPORT___);


/***/ },

/***/ "./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/dist/stylePostLoader.js!./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/Navigation.vue?vue&type=style&index=0&id=81440b78&scoped=true&lang=css"
/*!***********************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/dist/stylePostLoader.js!./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/Navigation.vue?vue&type=style&index=0&id=81440b78&scoped=true&lang=css ***!
  \***********************************************************************************************************************************************************************************************************************************************************/
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
.intravox-navigation[data-v-81440b78] {
  display: flex;
  align-items: center;
  width: 100%;
  padding: 0 16px;
}

/* Page Tree Button */
.page-tree-btn[data-v-81440b78] {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 36px;
  height: 36px;
  padding: 0;
  margin-right: 8px;
  background: none;
  border: none;
  border-radius: var(--border-radius);
  color: var(--color-main-text);
  cursor: pointer;
  transition: background-color 0.1s ease;
  flex-shrink: 0;
}
.page-tree-btn[data-v-81440b78]:hover {
  background-color: var(--color-background-hover);
}
.page-tree-btn[data-v-81440b78]:active {
  background-color: var(--color-primary-element-light);
}

/* Mobile Navigation */
.mobile-nav[data-v-81440b78] {
  display: none;
}
.mobile-nav-level-2[data-v-81440b78] .action-button__longtext {
  padding-left: 32px !important;
  font-size: 14px !important;
  color: var(--color-text-maxcontrast) !important;
  font-weight: 400 !important;
}
.mobile-nav-level-3[data-v-81440b78] .action-button__longtext {
  padding-left: 56px !important;
  font-size: 13px !important;
  color: var(--color-text-maxcontrast) !important;
  font-weight: 300 !important;
}
.mobile-nav-level-4[data-v-81440b78] .action-button__longtext {
  padding-left: 80px !important;
  font-size: 12px !important;
  color: var(--color-text-maxcontrast) !important;
  font-weight: 300 !important;
}

/* Mobile expand button - larger clickable area */
.mobile-expand-btn[data-v-81440b78] {
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 8px;
  margin: -8px;
  border-radius: var(--border-radius);
  cursor: pointer;
  transition: background-color 0.1s ease;
}
.mobile-expand-btn[data-v-81440b78]:hover {
  background-color: var(--color-background-dark);
}
.mobile-expand-btn[data-v-81440b78]:active {
  background-color: var(--color-primary-element-light);
}

/* Hover effects for mobile items */
.mobile-nav[data-v-81440b78] .action-button:hover {
  background-color: var(--color-background-hover) !important;
}

/* Desktop Navigation */
.desktop-nav[data-v-81440b78] {
  display: flex;
  align-items: center;
  gap: 4px;
  flex: 1;
  overflow-x: auto; /* Enable horizontal scrolling */
  overflow-y: visible; /* Allow dropdown menus to be visible */
  scrollbar-width: thin; /* Firefox */
  -webkit-overflow-scrolling: touch; /* Smooth scrolling on iOS */
}

/* Scrollbar styling for desktop navigation */
.desktop-nav[data-v-81440b78]::-webkit-scrollbar {
  height: 4px;
}
.desktop-nav[data-v-81440b78]::-webkit-scrollbar-track {
  background: transparent;
}
.desktop-nav[data-v-81440b78]::-webkit-scrollbar-thumb {
  background: var(--color-border-dark);
  border-radius: 2px;
}
.desktop-nav[data-v-81440b78]::-webkit-scrollbar-thumb:hover {
  background: var(--color-text-maxcontrast);
}

/* Dropdown Navigation */
.desktop-dropdown-nav[data-v-81440b78] {
  gap: 0;
}
.dropdown-item[data-v-81440b78] {
  position: relative;
}
.dropdown-trigger[data-v-81440b78] {
  display: flex;
  align-items: center;
  gap: 4px;
  padding: 10px 16px;
  color: var(--color-main-text);
  text-decoration: none;
  font-weight: 600;
  white-space: nowrap;
  border-radius: var(--border-radius-large);
  transition: background-color 0.1s ease, opacity 0.1s ease;
  position: relative;
}
.dropdown-trigger[data-v-81440b78]:hover {
  background-color: var(--color-background-hover);
}
.dropdown-trigger[data-v-81440b78]:active {
  background-color: var(--color-primary-element-light);
}
.dropdown-trigger .chevron-icon[data-v-81440b78] {
  transition: transform 0.2s ease;
}
.dropdown-item:hover .chevron-icon[data-v-81440b78] {
  transform: rotate(180deg);
}
.dropdown-menu[data-v-81440b78] {
  position: fixed; /* Changed from absolute to fixed to escape parent overflow */
  top: auto; /* Will be set by JavaScript */
  left: auto; /* Will be set by JavaScript */
  min-width: 250px;
  max-width: 350px;
  margin-top: 4px;
  background: var(--color-main-background);
  border: 1px solid var(--color-border);
  border-radius: var(--border-radius-large);
  box-shadow: 0 2px 8px var(--color-box-shadow);
  z-index: 10010; /* Above Nextcloud sidebar (z-index ~2000) */
  padding: 8px;
}
.dropdown-content[data-v-81440b78] {
  display: flex;
  flex-direction: column;
  gap: 4px;
}
.dropdown-section[data-v-81440b78] {
  display: flex;
  flex-direction: column;
  gap: 4px;
  padding: 4px 0;
}
.dropdown-section + .dropdown-section[data-v-81440b78] {
  border-top: 1px solid var(--color-border);
  margin-top: 4px;
  padding-top: 8px;
}
.dropdown-section-header-wrapper[data-v-81440b78] {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 8px;
}
.dropdown-section-header[data-v-81440b78] {
  font-weight: 600;
  font-size: 14px;
  color: var(--color-main-text);
  padding: 8px 12px;
  text-decoration: none;
  border-radius: var(--border-radius);
  transition: background-color 0.1s ease;
  flex: 1;
}
.dropdown-section-header.clickable[data-v-81440b78] {
  cursor: pointer;
}
.dropdown-section-header.clickable[data-v-81440b78]:hover {
  background-color: var(--color-background-hover);
}
a.dropdown-section-header[data-v-81440b78]:hover {
  background-color: var(--color-background-hover);
}
a.dropdown-section-header[data-v-81440b78]:active,
.dropdown-section-header.clickable[data-v-81440b78]:active {
  background-color: var(--color-primary-element-light);
}
.dropdown-toggle-btn[data-v-81440b78] {
  background: none;
  border: none;
  padding: 8px;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  color: var(--color-main-text);
  border-radius: var(--border-radius);
  transition: background-color 0.1s ease;
  flex-shrink: 0;
}
.dropdown-toggle-btn[data-v-81440b78]:hover {
  background-color: var(--color-background-hover);
}
.dropdown-toggle-btn[data-v-81440b78]:active {
  background-color: var(--color-primary-element-light);
}
.dropdown-section-items[data-v-81440b78] {
  display: flex;
  flex-direction: column;
  gap: 2px;
  padding-left: 8px;
}
.dropdown-section-item[data-v-81440b78] {
  display: block;
  padding: 8px 12px;
  color: var(--color-main-text);
  text-decoration: none;
  font-size: 13px;
  border-radius: var(--border-radius);
  transition: background-color 0.1s ease;
}
.dropdown-section-item[data-v-81440b78]:hover {
  background-color: var(--color-background-hover);
}
.dropdown-section-item[data-v-81440b78]:active {
  background-color: var(--color-primary-element-light);
}
.dropdown-item-link[data-v-81440b78] {
  display: block;
  padding: 8px 12px;
  color: var(--color-main-text);
  text-decoration: none;
  font-size: 14px;
  font-weight: 500;
  border-radius: var(--border-radius);
  transition: background-color 0.1s ease;
}
.dropdown-item-link[data-v-81440b78]:hover {
  background-color: var(--color-background-hover);
}
.dropdown-item-link[data-v-81440b78]:active {
  background-color: var(--color-primary-element-light);
}
.nav-link[data-v-81440b78] {
  padding: 10px 16px;
  color: var(--color-main-text);
  text-decoration: none;
  border-radius: var(--border-radius-large);
  font-weight: 600;
  white-space: nowrap;
  transition: background-color 0.1s ease, opacity 0.1s ease;
  display: flex;
  align-items: center;
  gap: 4px;
  position: relative;
}
.nav-link[data-v-81440b78]:hover {
  background-color: var(--color-background-hover);
}
.nav-link[data-v-81440b78]:active {
  background-color: var(--color-primary-element-light);
}

/* Mega Menu Navigation */
.desktop-megamenu-nav[data-v-81440b78] {
  gap: 0;
}
.megamenu-item[data-v-81440b78] {
  position: relative;
}
.megamenu-trigger[data-v-81440b78] {
  display: flex;
  align-items: center;
  gap: 4px;
  padding: 10px 16px;
  color: var(--color-main-text);
  text-decoration: none;
  font-weight: 600;
  white-space: nowrap;
  border-radius: var(--border-radius-large);
  transition: background-color 0.1s ease, opacity 0.1s ease;
  position: relative;
}
.megamenu-trigger[data-v-81440b78]:hover {
  background-color: var(--color-background-hover);
}
.megamenu-trigger[data-v-81440b78]:active {
  background-color: var(--color-primary-element-light);
}
.megamenu-trigger .chevron-icon[data-v-81440b78] {
  transition: transform 0.2s ease;
}
.megamenu-item:hover .chevron-icon[data-v-81440b78] {
  transform: rotate(180deg);
}
.megamenu-dropdown[data-v-81440b78] {
  position: fixed; /* Changed from absolute to fixed to escape parent overflow */
  top: auto; /* Will be set by JavaScript */
  left: auto; /* Will be set by JavaScript */
  min-width: 600px;
  max-width: 900px;
  margin-top: 4px;
  background: var(--color-main-background);
  border: 1px solid var(--color-border);
  border-radius: var(--border-radius-large);
  box-shadow: 0 4px 16px var(--color-box-shadow);
  z-index: 10010; /* Above Nextcloud sidebar (z-index ~2000) */
  padding: 24px;
}
.megamenu-grid[data-v-81440b78] {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
  gap: 32px 32px;
}
.megamenu-column[data-v-81440b78] {
  display: flex;
  flex-direction: column;
  gap: 0;
  padding: 16px 0;
  min-width: 200px;
}
.megamenu-column[data-v-81440b78]:last-child {
  padding-right: 0;
}
.megamenu-column-header[data-v-81440b78] {
  font-weight: 700;
  font-size: 13px;
  color: var(--color-main-text);
  padding: 0 0 12px 0;
  text-decoration: none;
  border-bottom: 2px solid var(--color-primary-element);
  margin-bottom: 12px;
  transition: color 0.1s ease;
  text-transform: none;
  letter-spacing: normal;
}
a.megamenu-column-header[data-v-81440b78]:hover {
  color: var(--color-primary-element);
}
a.megamenu-column-header[data-v-81440b78]:active {
  color: var(--color-primary-element);
}
.megamenu-list[data-v-81440b78] {
  list-style: none;
  padding: 0;
  margin: 0;
  display: flex;
  flex-direction: column;
  gap: 0;
}
.megamenu-list-item[data-v-81440b78] {
  display: block;
  padding: 10px 0;
  color: var(--color-text-maxcontrast);
  text-decoration: none;
  font-size: 14px;
  font-weight: 400;
  transition: color 0.1s ease;
  line-height: 1.4;
}
.megamenu-list-item[data-v-81440b78]:hover {
  color: var(--color-main-text);
}
.megamenu-list-item[data-v-81440b78]:active {
  color: var(--color-primary-element);
}
.megamenu-list-item.has-children[data-v-81440b78] {
  font-weight: 500;
}
.megamenu-sublist[data-v-81440b78] {
  list-style: none;
  padding: 0 0 0 16px;
  margin: 4px 0 0 0;
  display: flex;
  flex-direction: column;
  gap: 0;
}
.megamenu-sublist-item[data-v-81440b78] {
  display: block;
  padding: 8px 0;
  color: var(--color-text-lighter);
  text-decoration: none;
  font-size: 13px;
  font-weight: 400;
  transition: color 0.1s ease;
  line-height: 1.4;
}
.megamenu-sublist-item[data-v-81440b78]:hover {
  color: var(--color-main-text);
}
.megamenu-sublist-item[data-v-81440b78]:active {
  color: var(--color-primary-element);
}

/* Responsive - Show mobile menu on smaller screens */
@media (max-width: 768px) {
.mobile-nav[data-v-81440b78] {
    display: block;
}
.desktop-nav[data-v-81440b78] {
    display: none !important;
}
}
`, "",{"version":3,"sources":["webpack://./src/components/Navigation.vue"],"names":[],"mappings":";AAscA;EACE,aAAa;EACb,mBAAmB;EACnB,WAAW;EACX,eAAe;AACjB;;AAEA,qBAAqB;AACrB;EACE,aAAa;EACb,mBAAmB;EACnB,uBAAuB;EACvB,WAAW;EACX,YAAY;EACZ,UAAU;EACV,iBAAiB;EACjB,gBAAgB;EAChB,YAAY;EACZ,mCAAmC;EACnC,6BAA6B;EAC7B,eAAe;EACf,sCAAsC;EACtC,cAAc;AAChB;AAEA;EACE,+CAA+C;AACjD;AAEA;EACE,oDAAoD;AACtD;;AAEA,sBAAsB;AACtB;EACE,aAAa;AACf;AAEA;EACE,6BAA6B;EAC7B,0BAA0B;EAC1B,+CAA+C;EAC/C,2BAA2B;AAC7B;AAEA;EACE,6BAA6B;EAC7B,0BAA0B;EAC1B,+CAA+C;EAC/C,2BAA2B;AAC7B;AAEA;EACE,6BAA6B;EAC7B,0BAA0B;EAC1B,+CAA+C;EAC/C,2BAA2B;AAC7B;;AAEA,iDAAiD;AACjD;EACE,aAAa;EACb,mBAAmB;EACnB,uBAAuB;EACvB,YAAY;EACZ,YAAY;EACZ,mCAAmC;EACnC,eAAe;EACf,sCAAsC;AACxC;AAEA;EACE,8CAA8C;AAChD;AAEA;EACE,oDAAoD;AACtD;;AAEA,mCAAmC;AACnC;EACE,0DAA0D;AAC5D;;AAEA,uBAAuB;AACvB;EACE,aAAa;EACb,mBAAmB;EACnB,QAAQ;EACR,OAAO;EACP,gBAAgB,EAAE,gCAAgC;EAClD,mBAAmB,EAAE,uCAAuC;EAC5D,qBAAqB,EAAE,YAAY;EACnC,iCAAiC,EAAE,4BAA4B;AACjE;;AAEA,6CAA6C;AAC7C;EACE,WAAW;AACb;AAEA;EACE,uBAAuB;AACzB;AAEA;EACE,oCAAoC;EACpC,kBAAkB;AACpB;AAEA;EACE,yCAAyC;AAC3C;;AAEA,wBAAwB;AACxB;EACE,MAAM;AACR;AAEA;EACE,kBAAkB;AACpB;AAEA;EACE,aAAa;EACb,mBAAmB;EACnB,QAAQ;EACR,kBAAkB;EAClB,6BAA6B;EAC7B,qBAAqB;EACrB,gBAAgB;EAChB,mBAAmB;EACnB,yCAAyC;EACzC,yDAAyD;EACzD,kBAAkB;AACpB;AAEA;EACE,+CAA+C;AACjD;AAEA;EACE,oDAAoD;AACtD;AAEA;EACE,+BAA+B;AACjC;AAEA;EACE,yBAAyB;AAC3B;AAEA;EACE,eAAe,EAAE,6DAA6D;EAC9E,SAAS,EAAE,8BAA8B;EACzC,UAAU,EAAE,8BAA8B;EAC1C,gBAAgB;EAChB,gBAAgB;EAChB,eAAe;EACf,wCAAwC;EACxC,qCAAqC;EACrC,yCAAyC;EACzC,6CAA6C;EAC7C,cAAc,EAAE,4CAA4C;EAC5D,YAAY;AACd;AAEA;EACE,aAAa;EACb,sBAAsB;EACtB,QAAQ;AACV;AAEA;EACE,aAAa;EACb,sBAAsB;EACtB,QAAQ;EACR,cAAc;AAChB;AAEA;EACE,yCAAyC;EACzC,eAAe;EACf,gBAAgB;AAClB;AAEA;EACE,aAAa;EACb,mBAAmB;EACnB,8BAA8B;EAC9B,QAAQ;AACV;AAEA;EACE,gBAAgB;EAChB,eAAe;EACf,6BAA6B;EAC7B,iBAAiB;EACjB,qBAAqB;EACrB,mCAAmC;EACnC,sCAAsC;EACtC,OAAO;AACT;AAEA;EACE,eAAe;AACjB;AAEA;EACE,+CAA+C;AACjD;AAEA;EACE,+CAA+C;AACjD;AAEA;;EAEE,oDAAoD;AACtD;AAEA;EACE,gBAAgB;EAChB,YAAY;EACZ,YAAY;EACZ,eAAe;EACf,aAAa;EACb,mBAAmB;EACnB,uBAAuB;EACvB,6BAA6B;EAC7B,mCAAmC;EACnC,sCAAsC;EACtC,cAAc;AAChB;AAEA;EACE,+CAA+C;AACjD;AAEA;EACE,oDAAoD;AACtD;AAEA;EACE,aAAa;EACb,sBAAsB;EACtB,QAAQ;EACR,iBAAiB;AACnB;AAEA;EACE,cAAc;EACd,iBAAiB;EACjB,6BAA6B;EAC7B,qBAAqB;EACrB,eAAe;EACf,mCAAmC;EACnC,sCAAsC;AACxC;AAEA;EACE,+CAA+C;AACjD;AAEA;EACE,oDAAoD;AACtD;AAEA;EACE,cAAc;EACd,iBAAiB;EACjB,6BAA6B;EAC7B,qBAAqB;EACrB,eAAe;EACf,gBAAgB;EAChB,mCAAmC;EACnC,sCAAsC;AACxC;AAEA;EACE,+CAA+C;AACjD;AAEA;EACE,oDAAoD;AACtD;AAEA;EACE,kBAAkB;EAClB,6BAA6B;EAC7B,qBAAqB;EACrB,yCAAyC;EACzC,gBAAgB;EAChB,mBAAmB;EACnB,yDAAyD;EACzD,aAAa;EACb,mBAAmB;EACnB,QAAQ;EACR,kBAAkB;AACpB;AAEA;EACE,+CAA+C;AACjD;AAEA;EACE,oDAAoD;AACtD;;AAEA,yBAAyB;AACzB;EACE,MAAM;AACR;AAEA;EACE,kBAAkB;AACpB;AAEA;EACE,aAAa;EACb,mBAAmB;EACnB,QAAQ;EACR,kBAAkB;EAClB,6BAA6B;EAC7B,qBAAqB;EACrB,gBAAgB;EAChB,mBAAmB;EACnB,yCAAyC;EACzC,yDAAyD;EACzD,kBAAkB;AACpB;AAEA;EACE,+CAA+C;AACjD;AAEA;EACE,oDAAoD;AACtD;AAEA;EACE,+BAA+B;AACjC;AAEA;EACE,yBAAyB;AAC3B;AAEA;EACE,eAAe,EAAE,6DAA6D;EAC9E,SAAS,EAAE,8BAA8B;EACzC,UAAU,EAAE,8BAA8B;EAC1C,gBAAgB;EAChB,gBAAgB;EAChB,eAAe;EACf,wCAAwC;EACxC,qCAAqC;EACrC,yCAAyC;EACzC,8CAA8C;EAC9C,cAAc,EAAE,4CAA4C;EAC5D,aAAa;AACf;AAEA;EACE,aAAa;EACb,4DAA4D;EAC5D,cAAc;AAChB;AAEA;EACE,aAAa;EACb,sBAAsB;EACtB,MAAM;EACN,eAAe;EACf,gBAAgB;AAClB;AAEA;EACE,gBAAgB;AAClB;AAEA;EACE,gBAAgB;EAChB,eAAe;EACf,6BAA6B;EAC7B,mBAAmB;EACnB,qBAAqB;EACrB,qDAAqD;EACrD,mBAAmB;EACnB,2BAA2B;EAC3B,oBAAoB;EACpB,sBAAsB;AACxB;AAEA;EACE,mCAAmC;AACrC;AAEA;EACE,mCAAmC;AACrC;AAEA;EACE,gBAAgB;EAChB,UAAU;EACV,SAAS;EACT,aAAa;EACb,sBAAsB;EACtB,MAAM;AACR;AAEA;EACE,cAAc;EACd,eAAe;EACf,oCAAoC;EACpC,qBAAqB;EACrB,eAAe;EACf,gBAAgB;EAChB,2BAA2B;EAC3B,gBAAgB;AAClB;AAEA;EACE,6BAA6B;AAC/B;AAEA;EACE,mCAAmC;AACrC;AAEA;EACE,gBAAgB;AAClB;AAEA;EACE,gBAAgB;EAChB,mBAAmB;EACnB,iBAAiB;EACjB,aAAa;EACb,sBAAsB;EACtB,MAAM;AACR;AAEA;EACE,cAAc;EACd,cAAc;EACd,gCAAgC;EAChC,qBAAqB;EACrB,eAAe;EACf,gBAAgB;EAChB,2BAA2B;EAC3B,gBAAgB;AAClB;AAEA;EACE,6BAA6B;AAC/B;AAEA;EACE,mCAAmC;AACrC;;AAEA,qDAAqD;AACrD;AACE;IACE,cAAc;AAChB;AAEA;IACE,wBAAwB;AAC1B;AACF","sourcesContent":["<template>\r\n  <nav class=\"intravox-navigation\">\r\n    <!-- Page Structure Button (left of navigation) -->\r\n    <button class=\"page-tree-btn\"\r\n            @click=\"handleTreeClick\"\r\n            :aria-label=\"t('Page structure')\"\r\n            :title=\"t('Page structure')\">\r\n      <FileTree :size=\"20\" />\r\n    </button>\r\n\r\n    <!-- Mobile hamburger menu -->\r\n    <div class=\"mobile-nav\">\r\n      <NcActions>\r\n        <template #icon>\r\n          <Menu :size=\"20\" />\r\n        </template>\r\n        <template v-for=\"item in items\" :key=\"getItemKey(item)\">\r\n          <!-- Top level items with children -->\r\n          <template v-if=\"item.children && item.children.length > 0\">\r\n            <!-- Title navigates, chevron expands -->\r\n            <NcActionButton @click=\"handleMobileItemClick(item)\">\r\n              <template #icon>\r\n                <span class=\"mobile-expand-btn\" @click.stop=\"toggleMobileItem(getItemKey(item))\">\r\n                  <ChevronRight v-if=\"!isMobileItemExpanded(getItemKey(item))\" :size=\"20\" />\r\n                  <ChevronDown v-else :size=\"20\" />\r\n                </span>\r\n              </template>\r\n              {{ decodeHtmlEntities(item.title) }}\r\n            </NcActionButton>\r\n\r\n            <!-- Level 2 items -->\r\n            <template v-if=\"isMobileItemExpanded(getItemKey(item))\">\r\n              <template v-for=\"child in item.children\" :key=\"getItemKey(child)\">\r\n                <!-- Level 2 with children -->\r\n                <NcActionButton v-if=\"child.children && child.children.length > 0\"\r\n                               @click=\"handleMobileItemClick(child)\"\r\n                               class=\"mobile-nav-level-2\">\r\n                  <template #icon>\r\n                    <span class=\"mobile-expand-btn\" @click.stop=\"toggleMobileItem(getItemKey(child))\">\r\n                      <ChevronRight v-if=\"!isMobileItemExpanded(getItemKey(child))\" :size=\"20\" />\r\n                      <ChevronDown v-else :size=\"20\" />\r\n                    </span>\r\n                  </template>\r\n                  {{ decodeHtmlEntities(child.title) }}\r\n                </NcActionButton>\r\n\r\n                <!-- Level 2 without children -->\r\n                <NcActionButton v-else\r\n                               @click=\"handleItemClick(child)\"\r\n                               class=\"mobile-nav-level-2\">\r\n                  {{ decodeHtmlEntities(child.title) }}\r\n                </NcActionButton>\r\n\r\n                <!-- Level 3 items -->\r\n                <template v-if=\"child.children && child.children.length > 0 && isMobileItemExpanded(getItemKey(child))\">\r\n                  <!-- Level 3 with children -->\r\n                  <template v-for=\"grandchild in child.children\" :key=\"getItemKey(grandchild)\">\r\n                    <NcActionButton v-if=\"grandchild.children && grandchild.children.length > 0\"\r\n                                   @click=\"handleMobileItemClick(grandchild)\"\r\n                                   class=\"mobile-nav-level-3\">\r\n                      <template #icon>\r\n                        <span class=\"mobile-expand-btn\" @click.stop=\"toggleMobileItem(getItemKey(grandchild))\">\r\n                          <ChevronRight v-if=\"!isMobileItemExpanded(getItemKey(grandchild))\" :size=\"20\" />\r\n                          <ChevronDown v-else :size=\"20\" />\r\n                        </span>\r\n                      </template>\r\n                      {{ decodeHtmlEntities(grandchild.title) }}\r\n                    </NcActionButton>\r\n\r\n                    <!-- Level 3 without children -->\r\n                    <NcActionButton v-else\r\n                                   @click=\"handleItemClick(grandchild)\"\r\n                                   class=\"mobile-nav-level-3\">\r\n                      {{ decodeHtmlEntities(grandchild.title) }}\r\n                    </NcActionButton>\r\n\r\n                    <!-- Level 4 items -->\r\n                    <template v-if=\"grandchild.children && grandchild.children.length > 0 && isMobileItemExpanded(getItemKey(grandchild))\">\r\n                      <NcActionButton v-for=\"greatGrandchild in grandchild.children\"\r\n                                     :key=\"getItemKey(greatGrandchild)\"\r\n                                     @click=\"handleItemClick(greatGrandchild)\"\r\n                                     class=\"mobile-nav-level-4\">\r\n                        {{ decodeHtmlEntities(greatGrandchild.title) }}\r\n                      </NcActionButton>\r\n                    </template>\r\n                  </template>\r\n                </template>\r\n              </template>\r\n            </template>\r\n          </template>\r\n\r\n          <!-- Top level items without children -->\r\n          <NcActionButton v-else @click=\"handleItemClick(item)\">\r\n            {{ decodeHtmlEntities(item.title) }}\r\n          </NcActionButton>\r\n        </template>\r\n      </NcActions>\r\n    </div>\r\n\r\n    <!-- Desktop Navigation - Cascading Dropdown -->\r\n    <div v-if=\"type === 'dropdown'\" class=\"desktop-nav desktop-dropdown-nav\">\r\n      <template v-for=\"item in items\" :key=\"getItemKey(item)\">\r\n        <!-- Top level with children -->\r\n        <div v-if=\"item.children && item.children.length > 0\"\r\n             class=\"dropdown-item\"\r\n             @mouseenter=\"showDropdown(item, $event)\"\r\n             @mouseleave=\"hideDropdown\">\r\n          <a :href=\"getItemUrl(item)\"\r\n             :target=\"item.target || '_self'\"\r\n             @click.prevent=\"handleItemClick(item)\"\r\n             class=\"dropdown-trigger\"\r\n             aria-haspopup=\"true\"\r\n             :aria-expanded=\"activeDropdown === getItemKey(item)\">\r\n            {{ decodeHtmlEntities(item.title) }}\r\n            <ChevronDown :size=\"16\" class=\"chevron-icon\" />\r\n          </a>\r\n\r\n          <!-- Dropdown menu -->\r\n          <div v-if=\"activeDropdown === getItemKey(item)\"\r\n               class=\"dropdown-menu\"\r\n               @mouseenter=\"keepDropdownOpen\"\r\n               @mouseleave=\"hideDropdown\">\r\n            <div class=\"dropdown-content\">\r\n              <!-- Level 2 items -->\r\n              <template v-for=\"child in item.children\" :key=\"getItemKey(child)\">\r\n                <!-- Level 2 with children -->\r\n                <div v-if=\"child.children && child.children.length > 0\" class=\"dropdown-section\">\r\n                  <!-- Section header (Level 2) - clickable to expand/collapse -->\r\n                  <div class=\"dropdown-section-header-wrapper\">\r\n                    <a v-if=\"child.uniqueId || child.url\"\r\n                       :href=\"getItemUrl(child)\"\r\n                       :target=\"child.target || '_self'\"\r\n                       @click.prevent=\"handleItemClick(child)\"\r\n                       class=\"dropdown-section-header\">\r\n                      {{ decodeHtmlEntities(child.title) }}\r\n                    </a>\r\n                    <div v-else\r\n                         class=\"dropdown-section-header clickable\"\r\n                         @click=\"toggleDropdownSection(getItemKey(child))\">\r\n                      {{ decodeHtmlEntities(child.title) }}\r\n                    </div>\r\n                    <button class=\"dropdown-toggle-btn\"\r\n                            @click=\"toggleDropdownSection(getItemKey(child))\"\r\n                            :aria-label=\"isDropdownSectionExpanded(getItemKey(child)) ? 'Collapse' : 'Expand'\">\r\n                      <ChevronDown v-if=\"isDropdownSectionExpanded(getItemKey(child))\" :size=\"16\" />\r\n                      <ChevronRight v-else :size=\"16\" />\r\n                    </button>\r\n                  </div>\r\n\r\n                  <!-- Level 3 items - only show when expanded -->\r\n                  <div v-if=\"isDropdownSectionExpanded(getItemKey(child))\" class=\"dropdown-section-items\">\r\n                    <a v-for=\"grandchild in child.children\"\r\n                       :key=\"getItemKey(grandchild)\"\r\n                       :href=\"getItemUrl(grandchild)\"\r\n                       :target=\"grandchild.target || '_self'\"\r\n                       @click.prevent=\"handleItemClick(grandchild)\"\r\n                       class=\"dropdown-section-item\">\r\n                      {{ decodeHtmlEntities(grandchild.title) }}\r\n                    </a>\r\n                  </div>\r\n                </div>\r\n\r\n                <!-- Level 2 without children -->\r\n                <a v-else\r\n                   :href=\"getItemUrl(child)\"\r\n                   :target=\"child.target || '_self'\"\r\n                   @click.prevent=\"handleItemClick(child)\"\r\n                   class=\"dropdown-item-link\">\r\n                  {{ decodeHtmlEntities(child.title) }}\r\n                </a>\r\n              </template>\r\n            </div>\r\n          </div>\r\n        </div>\r\n\r\n        <!-- Top level without children - regular link -->\r\n        <a v-else\r\n           :href=\"getItemUrl(item)\"\r\n           :target=\"item.target || '_self'\"\r\n           @click.prevent=\"handleItemClick(item)\"\r\n           class=\"nav-link\">\r\n          {{ decodeHtmlEntities(item.title) }}\r\n        </a>\r\n      </template>\r\n    </div>\r\n\r\n    <!-- Desktop Navigation - Mega Menu -->\r\n    <div v-else-if=\"type === 'megamenu'\" class=\"desktop-nav desktop-megamenu-nav\">\r\n      <template v-for=\"item in items\" :key=\"getItemKey(item)\">\r\n        <!-- Top level with children -->\r\n        <div v-if=\"item.children && item.children.length > 0\"\r\n             class=\"megamenu-item\"\r\n             @mouseenter=\"showMegaMenu(item, $event)\"\r\n             @mouseleave=\"hideMegaMenu\">\r\n          <a :href=\"getItemUrl(item)\"\r\n             :target=\"item.target || '_self'\"\r\n             @click.prevent=\"handleItemClick(item)\"\r\n             class=\"megamenu-trigger\"\r\n             aria-haspopup=\"true\"\r\n             :aria-expanded=\"activeMegaMenu === getItemKey(item)\">\r\n            {{ decodeHtmlEntities(item.title) }}\r\n            <ChevronDown :size=\"16\" class=\"chevron-icon\" />\r\n          </a>\r\n\r\n          <!-- Mega menu dropdown -->\r\n          <div v-if=\"activeMegaMenu === getItemKey(item)\"\r\n               class=\"megamenu-dropdown\"\r\n               @mouseenter=\"keepMegaMenuOpen\"\r\n               @mouseleave=\"hideMegaMenu\">\r\n            <div class=\"megamenu-grid\">\r\n              <!-- Level 2 columns -->\r\n              <div v-for=\"child in item.children\"\r\n                   :key=\"getItemKey(child)\"\r\n                   class=\"megamenu-column\">\r\n                <!-- Column header (Level 2) -->\r\n                <a v-if=\"child.uniqueId || child.url\"\r\n                   :href=\"getItemUrl(child)\"\r\n                   :target=\"child.target || '_self'\"\r\n                   @click.prevent=\"handleItemClick(child)\"\r\n                   class=\"megamenu-column-header\">\r\n                  {{ decodeHtmlEntities(child.title) }}\r\n                </a>\r\n                <div v-else class=\"megamenu-column-header\">\r\n                  {{ decodeHtmlEntities(child.title) }}\r\n                </div>\r\n\r\n                <!-- Level 3 items -->\r\n                <ul v-if=\"child.children && child.children.length > 0\" class=\"megamenu-list\">\r\n                  <li v-for=\"grandchild in child.children\" :key=\"getItemKey(grandchild)\">\r\n                    <a :href=\"getItemUrl(grandchild)\"\r\n                       :target=\"grandchild.target || '_self'\"\r\n                       @click.prevent=\"handleItemClick(grandchild)\"\r\n                       class=\"megamenu-list-item\"\r\n                       :class=\"{ 'has-children': grandchild.children && grandchild.children.length > 0 }\">\r\n                      {{ decodeHtmlEntities(grandchild.title) }}\r\n                    </a>\r\n\r\n                    <!-- Level 4 items (nested under level 3) -->\r\n                    <ul v-if=\"grandchild.children && grandchild.children.length > 0\" class=\"megamenu-sublist\">\r\n                      <li v-for=\"greatGrandchild in grandchild.children\" :key=\"getItemKey(greatGrandchild)\">\r\n                        <a :href=\"getItemUrl(greatGrandchild)\"\r\n                           :target=\"greatGrandchild.target || '_self'\"\r\n                           @click.prevent=\"handleItemClick(greatGrandchild)\"\r\n                           class=\"megamenu-sublist-item\">\r\n                          {{ decodeHtmlEntities(greatGrandchild.title) }}\r\n                        </a>\r\n                      </li>\r\n                    </ul>\r\n                  </li>\r\n                </ul>\r\n              </div>\r\n            </div>\r\n          </div>\r\n        </div>\r\n\r\n        <!-- Top level without children -->\r\n        <a v-else\r\n           :href=\"getItemUrl(item)\"\r\n           :target=\"item.target || '_self'\"\r\n           @click.prevent=\"handleItemClick(item)\"\r\n           class=\"nav-link\">\r\n          {{ decodeHtmlEntities(item.title) }}\r\n        </a>\r\n      </template>\r\n    </div>\r\n  </nav>\r\n</template>\r\n\r\n<script>\r\nimport { translate as t } from '@nextcloud/l10n';\r\nimport { NcActions, NcActionButton } from '@nextcloud/vue';\r\nimport ChevronDown from 'vue-material-design-icons/ChevronDown.vue';\r\nimport ChevronRight from 'vue-material-design-icons/ChevronRight.vue';\r\nimport Menu from 'vue-material-design-icons/Menu.vue';\r\nimport FileTree from 'vue-material-design-icons/FileTree.vue';\r\n\r\nexport default {\r\n  name: 'Navigation',\r\n  components: {\r\n    NcActions,\r\n    NcActionButton,\r\n    ChevronDown,\r\n    ChevronRight,\r\n    Menu,\r\n    FileTree\r\n  },\r\n  props: {\r\n    items: {\r\n      type: Array,\r\n      default: () => []\r\n    },\r\n    type: {\r\n      type: String,\r\n      default: 'dropdown',\r\n      validator: (value) => ['dropdown', 'megamenu'].includes(value)\r\n    },\r\n    isPublic: {\r\n      type: Boolean,\r\n      default: false\r\n    }\r\n  },\r\n  emits: ['navigate', 'show-tree'],\r\n  data() {\r\n    return {\r\n      // Mobile menu state\r\n      mobileExpandedItems: [],\r\n      // Dropdown state\r\n      activeDropdown: null,\r\n      dropdownTimeout: null,\r\n      dropdownExpandedSections: [],\r\n      // Mega menu state\r\n      activeMegaMenu: null,\r\n      megaMenuTimeout: null,\r\n      // Track current trigger element for repositioning on scroll\r\n      currentTriggerElement: null\r\n    };\r\n  },\r\n  mounted() {\r\n    // Add scroll listener to reposition dropdown menus during scroll\r\n    window.addEventListener('scroll', this.updateDropdownPosition, true);\r\n  },\r\n  beforeUnmount() {\r\n    // Clean up scroll listener\r\n    window.removeEventListener('scroll', this.updateDropdownPosition, true);\r\n  },\r\n  methods: {\r\n    t(key, vars = {}) {\r\n      return t('intravox', key, vars);\r\n    },\r\n    getItemKey(item) {\r\n      // Generate a unique key for Vue's :key attribute\r\n      return item.uniqueId || item.title.replace(/\\s+/g, '-').toLowerCase();\r\n    },\r\n    decodeHtmlEntities(text) {\r\n      if (!text) return '';\r\n      const textarea = document.createElement('textarea');\r\n      textarea.innerHTML = text;\r\n      return textarea.value;\r\n    },\r\n    getItemUrl(item) {\r\n      if (item.url) {\r\n        return item.url;\r\n      }\r\n      if (item.uniqueId) {\r\n        return `#${item.uniqueId}`;\r\n      }\r\n      return '#';\r\n    },\r\n    handleTreeClick(event) {\r\n      this.$emit('show-tree');\r\n    },\r\n    handleItemClick(item) {\r\n      this.$emit('navigate', item);\r\n      this.hideDropdown();\r\n      this.hideMegaMenu();\r\n    },\r\n    handleMobileItemClick(item) {\r\n      // Only navigate if item has a link (uniqueId or url)\r\n      if (item.uniqueId || item.url) {\r\n        this.$emit('navigate', item);\r\n      } else {\r\n        // If no link, toggle expand instead\r\n        this.toggleMobileItem(this.getItemKey(item));\r\n      }\r\n    },\r\n    // Dropdown methods\r\n    showDropdown(item, event) {\r\n      if (this.dropdownTimeout) {\r\n        clearTimeout(this.dropdownTimeout);\r\n      }\r\n      this.activeDropdown = this.getItemKey(item);\r\n      this.currentTriggerElement = event?.currentTarget;\r\n\r\n      // Position dropdown menu below trigger using fixed positioning\r\n      this.$nextTick(() => {\r\n        this.updateDropdownPosition();\r\n      });\r\n    },\r\n    keepDropdownOpen() {\r\n      if (this.dropdownTimeout) {\r\n        clearTimeout(this.dropdownTimeout);\r\n      }\r\n    },\r\n    hideDropdown() {\r\n      this.dropdownTimeout = setTimeout(() => {\r\n        this.activeDropdown = null;\r\n        this.dropdownExpandedSections = [];\r\n        this.currentTriggerElement = null;\r\n      }, 200);\r\n    },\r\n    updateDropdownPosition() {\r\n      if (!this.currentTriggerElement) return;\r\n\r\n      const trigger = this.currentTriggerElement;\r\n      const menu = trigger.querySelector('.dropdown-menu, .megamenu-dropdown');\r\n\r\n      if (trigger && menu) {\r\n        const rect = trigger.getBoundingClientRect();\r\n        menu.style.top = `${rect.bottom + 4}px`; // 4px margin\r\n        menu.style.left = `${rect.left}px`;\r\n      }\r\n    },\r\n    toggleDropdownSection(sectionId) {\r\n      const index = this.dropdownExpandedSections.indexOf(sectionId);\r\n      if (index > -1) {\r\n        this.dropdownExpandedSections.splice(index, 1);\r\n      } else {\r\n        this.dropdownExpandedSections.push(sectionId);\r\n      }\r\n    },\r\n    isDropdownSectionExpanded(sectionId) {\r\n      return this.dropdownExpandedSections.includes(sectionId);\r\n    },\r\n    // Mobile menu methods\r\n    toggleMobileItem(itemId) {\r\n      const index = this.mobileExpandedItems.indexOf(itemId);\r\n      if (index > -1) {\r\n        this.mobileExpandedItems.splice(index, 1);\r\n      } else {\r\n        this.mobileExpandedItems.push(itemId);\r\n      }\r\n    },\r\n    isMobileItemExpanded(itemId) {\r\n      return this.mobileExpandedItems.includes(itemId);\r\n    },\r\n    // Mega menu methods\r\n    showMegaMenu(item, event) {\r\n      if (this.megaMenuTimeout) {\r\n        clearTimeout(this.megaMenuTimeout);\r\n      }\r\n      this.activeMegaMenu = this.getItemKey(item);\r\n      this.currentTriggerElement = event?.currentTarget;\r\n\r\n      // Position megamenu below trigger using fixed positioning\r\n      this.$nextTick(() => {\r\n        this.updateDropdownPosition();\r\n      });\r\n    },\r\n    keepMegaMenuOpen() {\r\n      if (this.megaMenuTimeout) {\r\n        clearTimeout(this.megaMenuTimeout);\r\n      }\r\n    },\r\n    hideMegaMenu() {\r\n      this.megaMenuTimeout = setTimeout(() => {\r\n        this.activeMegaMenu = null;\r\n        this.currentTriggerElement = null;\r\n      }, 200);\r\n    }\r\n  }\r\n};\r\n</script>\r\n\r\n<style scoped>\r\n.intravox-navigation {\r\n  display: flex;\r\n  align-items: center;\r\n  width: 100%;\r\n  padding: 0 16px;\r\n}\r\n\r\n/* Page Tree Button */\r\n.page-tree-btn {\r\n  display: flex;\r\n  align-items: center;\r\n  justify-content: center;\r\n  width: 36px;\r\n  height: 36px;\r\n  padding: 0;\r\n  margin-right: 8px;\r\n  background: none;\r\n  border: none;\r\n  border-radius: var(--border-radius);\r\n  color: var(--color-main-text);\r\n  cursor: pointer;\r\n  transition: background-color 0.1s ease;\r\n  flex-shrink: 0;\r\n}\r\n\r\n.page-tree-btn:hover {\r\n  background-color: var(--color-background-hover);\r\n}\r\n\r\n.page-tree-btn:active {\r\n  background-color: var(--color-primary-element-light);\r\n}\r\n\r\n/* Mobile Navigation */\r\n.mobile-nav {\r\n  display: none;\r\n}\r\n\r\n.mobile-nav-level-2 :deep(.action-button__longtext) {\r\n  padding-left: 32px !important;\r\n  font-size: 14px !important;\r\n  color: var(--color-text-maxcontrast) !important;\r\n  font-weight: 400 !important;\r\n}\r\n\r\n.mobile-nav-level-3 :deep(.action-button__longtext) {\r\n  padding-left: 56px !important;\r\n  font-size: 13px !important;\r\n  color: var(--color-text-maxcontrast) !important;\r\n  font-weight: 300 !important;\r\n}\r\n\r\n.mobile-nav-level-4 :deep(.action-button__longtext) {\r\n  padding-left: 80px !important;\r\n  font-size: 12px !important;\r\n  color: var(--color-text-maxcontrast) !important;\r\n  font-weight: 300 !important;\r\n}\r\n\r\n/* Mobile expand button - larger clickable area */\r\n.mobile-expand-btn {\r\n  display: flex;\r\n  align-items: center;\r\n  justify-content: center;\r\n  padding: 8px;\r\n  margin: -8px;\r\n  border-radius: var(--border-radius);\r\n  cursor: pointer;\r\n  transition: background-color 0.1s ease;\r\n}\r\n\r\n.mobile-expand-btn:hover {\r\n  background-color: var(--color-background-dark);\r\n}\r\n\r\n.mobile-expand-btn:active {\r\n  background-color: var(--color-primary-element-light);\r\n}\r\n\r\n/* Hover effects for mobile items */\r\n.mobile-nav :deep(.action-button):hover {\r\n  background-color: var(--color-background-hover) !important;\r\n}\r\n\r\n/* Desktop Navigation */\r\n.desktop-nav {\r\n  display: flex;\r\n  align-items: center;\r\n  gap: 4px;\r\n  flex: 1;\r\n  overflow-x: auto; /* Enable horizontal scrolling */\r\n  overflow-y: visible; /* Allow dropdown menus to be visible */\r\n  scrollbar-width: thin; /* Firefox */\r\n  -webkit-overflow-scrolling: touch; /* Smooth scrolling on iOS */\r\n}\r\n\r\n/* Scrollbar styling for desktop navigation */\r\n.desktop-nav::-webkit-scrollbar {\r\n  height: 4px;\r\n}\r\n\r\n.desktop-nav::-webkit-scrollbar-track {\r\n  background: transparent;\r\n}\r\n\r\n.desktop-nav::-webkit-scrollbar-thumb {\r\n  background: var(--color-border-dark);\r\n  border-radius: 2px;\r\n}\r\n\r\n.desktop-nav::-webkit-scrollbar-thumb:hover {\r\n  background: var(--color-text-maxcontrast);\r\n}\r\n\r\n/* Dropdown Navigation */\r\n.desktop-dropdown-nav {\r\n  gap: 0;\r\n}\r\n\r\n.dropdown-item {\r\n  position: relative;\r\n}\r\n\r\n.dropdown-trigger {\r\n  display: flex;\r\n  align-items: center;\r\n  gap: 4px;\r\n  padding: 10px 16px;\r\n  color: var(--color-main-text);\r\n  text-decoration: none;\r\n  font-weight: 600;\r\n  white-space: nowrap;\r\n  border-radius: var(--border-radius-large);\r\n  transition: background-color 0.1s ease, opacity 0.1s ease;\r\n  position: relative;\r\n}\r\n\r\n.dropdown-trigger:hover {\r\n  background-color: var(--color-background-hover);\r\n}\r\n\r\n.dropdown-trigger:active {\r\n  background-color: var(--color-primary-element-light);\r\n}\r\n\r\n.dropdown-trigger .chevron-icon {\r\n  transition: transform 0.2s ease;\r\n}\r\n\r\n.dropdown-item:hover .chevron-icon {\r\n  transform: rotate(180deg);\r\n}\r\n\r\n.dropdown-menu {\r\n  position: fixed; /* Changed from absolute to fixed to escape parent overflow */\r\n  top: auto; /* Will be set by JavaScript */\r\n  left: auto; /* Will be set by JavaScript */\r\n  min-width: 250px;\r\n  max-width: 350px;\r\n  margin-top: 4px;\r\n  background: var(--color-main-background);\r\n  border: 1px solid var(--color-border);\r\n  border-radius: var(--border-radius-large);\r\n  box-shadow: 0 2px 8px var(--color-box-shadow);\r\n  z-index: 10010; /* Above Nextcloud sidebar (z-index ~2000) */\r\n  padding: 8px;\r\n}\r\n\r\n.dropdown-content {\r\n  display: flex;\r\n  flex-direction: column;\r\n  gap: 4px;\r\n}\r\n\r\n.dropdown-section {\r\n  display: flex;\r\n  flex-direction: column;\r\n  gap: 4px;\r\n  padding: 4px 0;\r\n}\r\n\r\n.dropdown-section + .dropdown-section {\r\n  border-top: 1px solid var(--color-border);\r\n  margin-top: 4px;\r\n  padding-top: 8px;\r\n}\r\n\r\n.dropdown-section-header-wrapper {\r\n  display: flex;\r\n  align-items: center;\r\n  justify-content: space-between;\r\n  gap: 8px;\r\n}\r\n\r\n.dropdown-section-header {\r\n  font-weight: 600;\r\n  font-size: 14px;\r\n  color: var(--color-main-text);\r\n  padding: 8px 12px;\r\n  text-decoration: none;\r\n  border-radius: var(--border-radius);\r\n  transition: background-color 0.1s ease;\r\n  flex: 1;\r\n}\r\n\r\n.dropdown-section-header.clickable {\r\n  cursor: pointer;\r\n}\r\n\r\n.dropdown-section-header.clickable:hover {\r\n  background-color: var(--color-background-hover);\r\n}\r\n\r\na.dropdown-section-header:hover {\r\n  background-color: var(--color-background-hover);\r\n}\r\n\r\na.dropdown-section-header:active,\r\n.dropdown-section-header.clickable:active {\r\n  background-color: var(--color-primary-element-light);\r\n}\r\n\r\n.dropdown-toggle-btn {\r\n  background: none;\r\n  border: none;\r\n  padding: 8px;\r\n  cursor: pointer;\r\n  display: flex;\r\n  align-items: center;\r\n  justify-content: center;\r\n  color: var(--color-main-text);\r\n  border-radius: var(--border-radius);\r\n  transition: background-color 0.1s ease;\r\n  flex-shrink: 0;\r\n}\r\n\r\n.dropdown-toggle-btn:hover {\r\n  background-color: var(--color-background-hover);\r\n}\r\n\r\n.dropdown-toggle-btn:active {\r\n  background-color: var(--color-primary-element-light);\r\n}\r\n\r\n.dropdown-section-items {\r\n  display: flex;\r\n  flex-direction: column;\r\n  gap: 2px;\r\n  padding-left: 8px;\r\n}\r\n\r\n.dropdown-section-item {\r\n  display: block;\r\n  padding: 8px 12px;\r\n  color: var(--color-main-text);\r\n  text-decoration: none;\r\n  font-size: 13px;\r\n  border-radius: var(--border-radius);\r\n  transition: background-color 0.1s ease;\r\n}\r\n\r\n.dropdown-section-item:hover {\r\n  background-color: var(--color-background-hover);\r\n}\r\n\r\n.dropdown-section-item:active {\r\n  background-color: var(--color-primary-element-light);\r\n}\r\n\r\n.dropdown-item-link {\r\n  display: block;\r\n  padding: 8px 12px;\r\n  color: var(--color-main-text);\r\n  text-decoration: none;\r\n  font-size: 14px;\r\n  font-weight: 500;\r\n  border-radius: var(--border-radius);\r\n  transition: background-color 0.1s ease;\r\n}\r\n\r\n.dropdown-item-link:hover {\r\n  background-color: var(--color-background-hover);\r\n}\r\n\r\n.dropdown-item-link:active {\r\n  background-color: var(--color-primary-element-light);\r\n}\r\n\r\n.nav-link {\r\n  padding: 10px 16px;\r\n  color: var(--color-main-text);\r\n  text-decoration: none;\r\n  border-radius: var(--border-radius-large);\r\n  font-weight: 600;\r\n  white-space: nowrap;\r\n  transition: background-color 0.1s ease, opacity 0.1s ease;\r\n  display: flex;\r\n  align-items: center;\r\n  gap: 4px;\r\n  position: relative;\r\n}\r\n\r\n.nav-link:hover {\r\n  background-color: var(--color-background-hover);\r\n}\r\n\r\n.nav-link:active {\r\n  background-color: var(--color-primary-element-light);\r\n}\r\n\r\n/* Mega Menu Navigation */\r\n.desktop-megamenu-nav {\r\n  gap: 0;\r\n}\r\n\r\n.megamenu-item {\r\n  position: relative;\r\n}\r\n\r\n.megamenu-trigger {\r\n  display: flex;\r\n  align-items: center;\r\n  gap: 4px;\r\n  padding: 10px 16px;\r\n  color: var(--color-main-text);\r\n  text-decoration: none;\r\n  font-weight: 600;\r\n  white-space: nowrap;\r\n  border-radius: var(--border-radius-large);\r\n  transition: background-color 0.1s ease, opacity 0.1s ease;\r\n  position: relative;\r\n}\r\n\r\n.megamenu-trigger:hover {\r\n  background-color: var(--color-background-hover);\r\n}\r\n\r\n.megamenu-trigger:active {\r\n  background-color: var(--color-primary-element-light);\r\n}\r\n\r\n.megamenu-trigger .chevron-icon {\r\n  transition: transform 0.2s ease;\r\n}\r\n\r\n.megamenu-item:hover .chevron-icon {\r\n  transform: rotate(180deg);\r\n}\r\n\r\n.megamenu-dropdown {\r\n  position: fixed; /* Changed from absolute to fixed to escape parent overflow */\r\n  top: auto; /* Will be set by JavaScript */\r\n  left: auto; /* Will be set by JavaScript */\r\n  min-width: 600px;\r\n  max-width: 900px;\r\n  margin-top: 4px;\r\n  background: var(--color-main-background);\r\n  border: 1px solid var(--color-border);\r\n  border-radius: var(--border-radius-large);\r\n  box-shadow: 0 4px 16px var(--color-box-shadow);\r\n  z-index: 10010; /* Above Nextcloud sidebar (z-index ~2000) */\r\n  padding: 24px;\r\n}\r\n\r\n.megamenu-grid {\r\n  display: grid;\r\n  grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));\r\n  gap: 32px 32px;\r\n}\r\n\r\n.megamenu-column {\r\n  display: flex;\r\n  flex-direction: column;\r\n  gap: 0;\r\n  padding: 16px 0;\r\n  min-width: 200px;\r\n}\r\n\r\n.megamenu-column:last-child {\r\n  padding-right: 0;\r\n}\r\n\r\n.megamenu-column-header {\r\n  font-weight: 700;\r\n  font-size: 13px;\r\n  color: var(--color-main-text);\r\n  padding: 0 0 12px 0;\r\n  text-decoration: none;\r\n  border-bottom: 2px solid var(--color-primary-element);\r\n  margin-bottom: 12px;\r\n  transition: color 0.1s ease;\r\n  text-transform: none;\r\n  letter-spacing: normal;\r\n}\r\n\r\na.megamenu-column-header:hover {\r\n  color: var(--color-primary-element);\r\n}\r\n\r\na.megamenu-column-header:active {\r\n  color: var(--color-primary-element);\r\n}\r\n\r\n.megamenu-list {\r\n  list-style: none;\r\n  padding: 0;\r\n  margin: 0;\r\n  display: flex;\r\n  flex-direction: column;\r\n  gap: 0;\r\n}\r\n\r\n.megamenu-list-item {\r\n  display: block;\r\n  padding: 10px 0;\r\n  color: var(--color-text-maxcontrast);\r\n  text-decoration: none;\r\n  font-size: 14px;\r\n  font-weight: 400;\r\n  transition: color 0.1s ease;\r\n  line-height: 1.4;\r\n}\r\n\r\n.megamenu-list-item:hover {\r\n  color: var(--color-main-text);\r\n}\r\n\r\n.megamenu-list-item:active {\r\n  color: var(--color-primary-element);\r\n}\r\n\r\n.megamenu-list-item.has-children {\r\n  font-weight: 500;\r\n}\r\n\r\n.megamenu-sublist {\r\n  list-style: none;\r\n  padding: 0 0 0 16px;\r\n  margin: 4px 0 0 0;\r\n  display: flex;\r\n  flex-direction: column;\r\n  gap: 0;\r\n}\r\n\r\n.megamenu-sublist-item {\r\n  display: block;\r\n  padding: 8px 0;\r\n  color: var(--color-text-lighter);\r\n  text-decoration: none;\r\n  font-size: 13px;\r\n  font-weight: 400;\r\n  transition: color 0.1s ease;\r\n  line-height: 1.4;\r\n}\r\n\r\n.megamenu-sublist-item:hover {\r\n  color: var(--color-main-text);\r\n}\r\n\r\n.megamenu-sublist-item:active {\r\n  color: var(--color-primary-element);\r\n}\r\n\r\n/* Responsive - Show mobile menu on smaller screens */\r\n@media (max-width: 768px) {\r\n  .mobile-nav {\r\n    display: block;\r\n  }\r\n\r\n  .desktop-nav {\r\n    display: none !important;\r\n  }\r\n}\r\n</style>\r\n"],"sourceRoot":""}]);
// Exports
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (___CSS_LOADER_EXPORT___);


/***/ },

/***/ "./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/dist/stylePostLoader.js!./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/NoShareDialog.vue?vue&type=style&index=0&id=a9f8e2f4&scoped=true&lang=css"
/*!**************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/dist/stylePostLoader.js!./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/NoShareDialog.vue?vue&type=style&index=0&id=a9f8e2f4&scoped=true&lang=css ***!
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
.share-dialog[data-v-a9f8e2f4] {
  padding: 16px;
}
.share-scope[data-v-a9f8e2f4] {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 12px;
  background: var(--color-background-hover);
  border-radius: var(--border-radius);
  margin-bottom: 16px;
}
.scope-label[data-v-a9f8e2f4] {
  color: var(--color-text-maxcontrast);
}
.share-explanation[data-v-a9f8e2f4] {
  font-size: 14px;
  color: var(--color-main-text);
  margin-bottom: 16px;
  line-height: 1.5;
}
.share-manage-link[data-v-a9f8e2f4] {
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 13px;
  color: var(--color-primary-element);
  text-decoration: none;
  padding: 8px 12px;
  border-radius: var(--border-radius);
  transition: background-color 0.1s ease;
}
.share-manage-link[data-v-a9f8e2f4]:hover {
  background-color: var(--color-background-hover);
}
.manage-text[data-v-a9f8e2f4] {
  flex: 1;
}
.manage-arrow[data-v-a9f8e2f4] {
  opacity: 0.5;
}
`, "",{"version":3,"sources":["webpack://./src/components/NoShareDialog.vue"],"names":[],"mappings":";AAqGA;EACE,aAAa;AACf;AAEA;EACE,aAAa;EACb,mBAAmB;EACnB,QAAQ;EACR,aAAa;EACb,yCAAyC;EACzC,mCAAmC;EACnC,mBAAmB;AACrB;AAEA;EACE,oCAAoC;AACtC;AAEA;EACE,eAAe;EACf,6BAA6B;EAC7B,mBAAmB;EACnB,gBAAgB;AAClB;AAEA;EACE,aAAa;EACb,mBAAmB;EACnB,QAAQ;EACR,eAAe;EACf,mCAAmC;EACnC,qBAAqB;EACrB,iBAAiB;EACjB,mCAAmC;EACnC,sCAAsC;AACxC;AAEA;EACE,+CAA+C;AACjD;AAEA;EACE,OAAO;AACT;AAEA;EACE,YAAY;AACd","sourcesContent":["<template>\r\n  <NcDialog :name=\"t('Share this page')\"\r\n            :can-close=\"true\"\r\n            @close=\"$emit('close')\">\r\n    <div class=\"share-dialog\">\r\n      <!-- Status indicator -->\r\n      <div class=\"share-scope\">\r\n        <LinkVariantOff :size=\"20\" />\r\n        <span class=\"scope-label\">\r\n          {{ t('This page is not shared publicly') }}\r\n        </span>\r\n      </div>\r\n\r\n      <!-- NC link sharing disabled by admin -->\r\n      <template v-if=\"isDisabledByAdmin\">\r\n        <NcNoteCard type=\"warning\">\r\n          {{ t('Public link sharing is disabled by the administrator.') }}\r\n        </NcNoteCard>\r\n        <p class=\"share-explanation\">\r\n          {{ t('To enable anonymous access to IntraVox pages, an administrator must first enable \"Allow users to share via link and emails\" in the Nextcloud Sharing settings.') }}\r\n        </p>\r\n        <a :href=\"adminSharingUrl\" target=\"_blank\" class=\"share-manage-link\">\r\n          <CogOutline :size=\"16\" />\r\n          <span class=\"manage-text\">\r\n            {{ t('Open Sharing settings') }}\r\n          </span>\r\n          <OpenInNew :size=\"14\" class=\"manage-arrow\" />\r\n        </a>\r\n      </template>\r\n\r\n      <!-- No share exists, but sharing is possible -->\r\n      <template v-else>\r\n        <p class=\"share-explanation\">\r\n          {{ t('To make this page accessible without login, create a public share link in the Files app.') }}\r\n        </p>\r\n        <a v-if=\"filesUrl\" :href=\"filesUrl\" target=\"_blank\" class=\"share-manage-link\">\r\n          <FolderOutline :size=\"16\" />\r\n          <span class=\"manage-text\">\r\n            {{ t('Open in Files') }}\r\n          </span>\r\n          <OpenInNew :size=\"14\" class=\"manage-arrow\" />\r\n        </a>\r\n      </template>\r\n    </div>\r\n  </NcDialog>\r\n</template>\r\n\r\n<script>\r\nimport { translate as t } from '@nextcloud/l10n';\r\nimport { generateUrl } from '@nextcloud/router';\r\nimport { NcDialog, NcNoteCard } from '@nextcloud/vue';\r\nimport LinkVariantOff from 'vue-material-design-icons/LinkVariantOff.vue';\r\nimport FolderOutline from 'vue-material-design-icons/FolderOutline.vue';\r\nimport CogOutline from 'vue-material-design-icons/CogOutline.vue';\r\nimport OpenInNew from 'vue-material-design-icons/OpenInNew.vue';\r\n\r\nexport default {\r\n  name: 'NoShareDialog',\r\n  components: {\r\n    NcDialog,\r\n    NcNoteCard,\r\n    LinkVariantOff,\r\n    FolderOutline,\r\n    CogOutline,\r\n    OpenInNew\r\n  },\r\n  props: {\r\n    shareInfo: {\r\n      type: Object,\r\n      required: true\r\n    },\r\n    pageTitle: {\r\n      type: String,\r\n      default: ''\r\n    }\r\n  },\r\n  emits: ['close'],\r\n  computed: {\r\n    isDisabledByAdmin() {\r\n      return this.shareInfo?.reason === 'nc_disabled';\r\n    },\r\n    filesUrl() {\r\n      const url = this.shareInfo?.filesUrl || '';\r\n      if (!url) return null;\r\n      const [path, query] = url.split('?');\r\n      const base = generateUrl(path);\r\n      return query ? base + '?' + query : base;\r\n    },\r\n    adminSharingUrl() {\r\n      return generateUrl('/settings/admin/sharing');\r\n    }\r\n  },\r\n  methods: {\r\n    t(key, vars = {}) {\r\n      return t('intravox', key, vars);\r\n    }\r\n  }\r\n};\r\n</script>\r\n\r\n<style scoped>\r\n.share-dialog {\r\n  padding: 16px;\r\n}\r\n\r\n.share-scope {\r\n  display: flex;\r\n  align-items: center;\r\n  gap: 8px;\r\n  padding: 12px;\r\n  background: var(--color-background-hover);\r\n  border-radius: var(--border-radius);\r\n  margin-bottom: 16px;\r\n}\r\n\r\n.scope-label {\r\n  color: var(--color-text-maxcontrast);\r\n}\r\n\r\n.share-explanation {\r\n  font-size: 14px;\r\n  color: var(--color-main-text);\r\n  margin-bottom: 16px;\r\n  line-height: 1.5;\r\n}\r\n\r\n.share-manage-link {\r\n  display: flex;\r\n  align-items: center;\r\n  gap: 8px;\r\n  font-size: 13px;\r\n  color: var(--color-primary-element);\r\n  text-decoration: none;\r\n  padding: 8px 12px;\r\n  border-radius: var(--border-radius);\r\n  transition: background-color 0.1s ease;\r\n}\r\n\r\n.share-manage-link:hover {\r\n  background-color: var(--color-background-hover);\r\n}\r\n\r\n.manage-text {\r\n  flex: 1;\r\n}\r\n\r\n.manage-arrow {\r\n  opacity: 0.5;\r\n}\r\n</style>\r\n"],"sourceRoot":""}]);
// Exports
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (___CSS_LOADER_EXPORT___);


/***/ },

/***/ "./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/dist/stylePostLoader.js!./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/PageActionsMenu.vue?vue&type=style&index=0&id=457bfd6d&scoped=true&lang=css"
/*!****************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/dist/stylePostLoader.js!./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/PageActionsMenu.vue?vue&type=style&index=0&id=457bfd6d&scoped=true&lang=css ***!
  \****************************************************************************************************************************************************************************************************************************************************************/
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
/* NcActions component handles its own styling */
`, "",{"version":3,"sources":["webpack://./src/components/PageActionsMenu.vue"],"names":[],"mappings":";AAsGA,gDAAgD","sourcesContent":["<template>\r\n  <NcActions>\r\n    <!-- New Page -->\r\n    <NcActionButton v-if=\"canPerformAction('createPage')\"\r\n                    @click=\"$emit('create-page')\">\r\n      <template #icon>\r\n        <Plus :size=\"20\" />\r\n      </template>\r\n      {{ t('New Page') }}\r\n    </NcActionButton>\r\n\r\n    <!-- Edit Navigation (admin action, less frequently used) -->\r\n    <NcActionButton v-if=\"canPerformAction('editNavigation')\"\r\n                    @click=\"$emit('edit-navigation')\">\r\n      <template #icon>\r\n        <Cog :size=\"20\" />\r\n      </template>\r\n      {{ t('Edit Navigation') }}\r\n    </NcActionButton>\r\n\r\n    <!-- Page Settings (for editors) -->\r\n    <NcActionButton v-if=\"canPerformAction('editPage')\"\r\n                    @click=\"$emit('page-settings')\">\r\n      <template #icon>\r\n        <TuneVertical :size=\"20\" />\r\n      </template>\r\n      {{ t('Page Settings') }}\r\n    </NcActionButton>\r\n\r\n    <!-- Save as Template -->\r\n    <NcActionButton v-if=\"canPerformAction('saveAsTemplate')\"\r\n                    @click=\"$emit('save-as-template')\">\r\n      <template #icon>\r\n        <FileDocumentMultipleOutline :size=\"20\" />\r\n      </template>\r\n      {{ t('Save as Template') }}\r\n    </NcActionButton>\r\n\r\n    <!-- RSS Feed -->\r\n    <NcActionButton @click=\"$emit('feed-settings')\">\r\n      <template #icon>\r\n        <Rss :size=\"20\" />\r\n      </template>\r\n      {{ t('RSS Feed') }}\r\n    </NcActionButton>\r\n  </NcActions>\r\n</template>\r\n\r\n<script>\r\nimport { translate as t } from '@nextcloud/l10n';\r\nimport { NcActions, NcActionButton } from '@nextcloud/vue';\r\nimport Cog from 'vue-material-design-icons/Cog.vue';\r\nimport Plus from 'vue-material-design-icons/Plus.vue';\r\nimport TuneVertical from 'vue-material-design-icons/TuneVertical.vue';\r\nimport FileDocumentMultipleOutline from 'vue-material-design-icons/FileDocumentMultipleOutline.vue';\r\nimport Rss from 'vue-material-design-icons/Rss.vue';\r\n\r\nexport default {\r\n  name: 'PageActionsMenu',\r\n  components: {\r\n    NcActions,\r\n    NcActionButton,\r\n    Cog,\r\n    Plus,\r\n    TuneVertical,\r\n    FileDocumentMultipleOutline,\r\n    Rss\r\n  },\r\n  props: {\r\n    isEditMode: {\r\n      type: Boolean,\r\n      default: false\r\n    },\r\n    permissions: {\r\n      type: Object,\r\n      default: () => ({\r\n        editNavigation: false,\r\n        viewPages: true,      // Everyone can view pages\r\n        createPage: false,\r\n        editPage: false,\r\n        deletePage: false     // For future use\r\n      })\r\n    }\r\n  },\r\n  emits: ['edit-navigation', 'create-page', 'page-settings', 'save-as-template', 'feed-settings'],\r\n  methods: {\r\n    t(key, vars = {}) {\r\n      return t('intravox', key, vars);\r\n    },\r\n    /**\r\n     * Check if user can perform a specific action\r\n     * This method can be extended in the future to include more complex logic\r\n     * like role-based permissions (admin, editor, viewer)\r\n     */\r\n    canPerformAction(action) {\r\n      return this.permissions[action] === true;\r\n    }\r\n  }\r\n};\r\n</script>\r\n\r\n<style scoped>\r\n/* NcActions component handles its own styling */\r\n</style>\r\n"],"sourceRoot":""}]);
// Exports
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (___CSS_LOADER_EXPORT___);


/***/ },

/***/ "./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/dist/stylePostLoader.js!./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/PageViewer.vue?vue&type=style&index=0&id=3a22e99e&scoped=true&lang=css"
/*!***********************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/dist/stylePostLoader.js!./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/PageViewer.vue?vue&type=style&index=0&id=3a22e99e&scoped=true&lang=css ***!
  \***********************************************************************************************************************************************************************************************************************************************************/
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
.page-viewer-wrapper[data-v-3a22e99e] {
  display: flex;
  flex-direction: column;
  width: 100%;
}
.page-viewer-container[data-v-3a22e99e] {
  display: flex;
  gap: 16px;
  width: 100%;
  max-width: 100%;
  box-sizing: border-box;
}
.page-viewer[data-v-3a22e99e] {
  flex: 1;
  min-width: 0;
  width: 100%;
  max-width: 100%;
  box-sizing: border-box;
}

/* Header Row Styles */
.header-row[data-v-3a22e99e] {
  width: 100%;
  padding: 16px;
  margin-bottom: 16px;
  border-radius: var(--border-radius-container-large);
  /* No default background - controlled by inline style via getHeaderRowStyle() */
  box-sizing: border-box;
  display: flex;
  flex-direction: column;
  gap: 12px;
}
.side-column[data-v-3a22e99e] {
  flex-shrink: 0;
  width: 250px;
  min-width: 200px;
  max-width: 300px;
  padding: 16px;
  border-radius: var(--border-radius-container-large);
  /* No default background - controlled by inline style via getSideColumnStyle() */
  box-sizing: border-box;
  display: flex;
  flex-direction: column;
  gap: 12px;
  align-self: flex-start;
}
.page-row[data-v-3a22e99e] {
  margin-bottom: 12px;
  padding: 16px;
  border-radius: var(--border-radius-container-large);
  box-sizing: border-box;
  /* Without these, a wide child (table with min-content larger than its
     column track) can blow the row past the viewport. min-width: 0 lets
     the row shrink below its min-content, max-width: 100% keeps it inside
     its parent. */
  min-width: 0;
  max-width: 100%;
}

/* Collapsible row styling */
.page-row.collapsible-row[data-v-3a22e99e] {
  padding: 0;
  overflow: hidden;
}
.row-section-header[data-v-3a22e99e] {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 12px 16px;
  background: rgba(0, 0, 0, 0.1);
  cursor: pointer;
  user-select: none;
  transition: background-color 0.2s ease;
  color: inherit;
}
.row-section-header[data-v-3a22e99e]:hover {
  background: rgba(0, 0, 0, 0.15);
}
.row-section-header .section-chevron[data-v-3a22e99e] {
  flex-shrink: 0;
  color: inherit;
  opacity: 0.7;
  transition: transform 0.2s ease;
}
.row-section-header .section-title[data-v-3a22e99e] {
  font-weight: 600;
  font-size: 1.1em;
  color: inherit;
}

/* Collapsed state header styling */
.row-section-header.collapsed[data-v-3a22e99e] {
  border-radius: var(--border-radius-container-large);
}

/* Row content container */
.row-content[data-v-3a22e99e] {
  padding: 16px;
  min-width: 0;
  max-width: 100%;
  box-sizing: border-box;
}
.page-row.collapsible-row .row-content[data-v-3a22e99e] {
  padding-top: 12px;
}
.row-content.collapsed[data-v-3a22e99e] {
  display: none;
}
.page-grid[data-v-3a22e99e] {
  display: grid;
  gap: 12px;
  width: 100%;
  box-sizing: border-box;
  align-items: start;
}
.page-column[data-v-3a22e99e] {
  min-height: 50px;
  box-sizing: border-box;
  min-width: 0;
  overflow: hidden;
  display: flex;
  flex-direction: column;
  gap: 12px;
}

/* Reactions & Comments Section */
.reactions-comments-section[data-v-3a22e99e] {
  margin-top: 24px;
  padding: 0 16px;
  width: 100%;
  max-width: 100%;
}

/* Mobile styles */
@media (max-width: 768px) {
.page-viewer-wrapper[data-v-3a22e99e] {
    padding: 0 8px;
}
.page-viewer-container[data-v-3a22e99e] {
    flex-direction: column;
}
.header-row[data-v-3a22e99e] {
    border-radius: var(--border-radius-container-large);
}
.side-column[data-v-3a22e99e] {
    width: 100%;
    min-width: 100%;
    max-width: 100%;
    order: 1; /* Side columns go below main content on mobile */
    border-radius: var(--border-radius-container-large);
}
.side-column-left[data-v-3a22e99e] {
    order: -1; /* Left column stays above on mobile */
}
.page-viewer[data-v-3a22e99e] {
    overflow-x: hidden; /* Prevent horizontal scroll */
    width: 100%;
    max-width: 100%;
    order: 0;
}
.page-row[data-v-3a22e99e] {
    margin-bottom: 16px;
    padding: 16px 12px;
    border-radius: var(--border-radius-container-large) !important;
    width: 100%;
    max-width: 100%;
    box-sizing: border-box;
    overflow: hidden; /* Ensures border-radius is visible */
}
.page-row.collapsible-row[data-v-3a22e99e] {
    border-radius: var(--border-radius-container-large) !important;
    overflow: hidden;
}
.row-section-header[data-v-3a22e99e] {
    border-radius: 0; /* Header takes full width inside row */
}
.row-section-header.collapsed[data-v-3a22e99e] {
    border-radius: 0; /* Parent row handles border-radius */
}
.page-grid[data-v-3a22e99e] {
    gap: 12px;
    grid-template-columns: 1fr !important;
    width: 100%;
    max-width: 100%;
}
.page-column[data-v-3a22e99e] {
    overflow-x: hidden; /* Prevent images from overflowing */
    width: 100%;
    max-width: 100%;
    min-width: 0;
}
}
`, "",{"version":3,"sources":["webpack://./src/components/PageViewer.vue"],"names":[],"mappings":";AAyWA;EACE,aAAa;EACb,sBAAsB;EACtB,WAAW;AACb;AAEA;EACE,aAAa;EACb,SAAS;EACT,WAAW;EACX,eAAe;EACf,sBAAsB;AACxB;AAEA;EACE,OAAO;EACP,YAAY;EACZ,WAAW;EACX,eAAe;EACf,sBAAsB;AACxB;;AAEA,sBAAsB;AACtB;EACE,WAAW;EACX,aAAa;EACb,mBAAmB;EACnB,mDAAmD;EACnD,+EAA+E;EAC/E,sBAAsB;EACtB,aAAa;EACb,sBAAsB;EACtB,SAAS;AACX;AAEA;EACE,cAAc;EACd,YAAY;EACZ,gBAAgB;EAChB,gBAAgB;EAChB,aAAa;EACb,mDAAmD;EACnD,gFAAgF;EAChF,sBAAsB;EACtB,aAAa;EACb,sBAAsB;EACtB,SAAS;EACT,sBAAsB;AACxB;AAEA;EACE,mBAAmB;EACnB,aAAa;EACb,mDAAmD;EACnD,sBAAsB;EACtB;;;kBAGgB;EAChB,YAAY;EACZ,eAAe;AACjB;;AAEA,4BAA4B;AAC5B;EACE,UAAU;EACV,gBAAgB;AAClB;AAEA;EACE,aAAa;EACb,mBAAmB;EACnB,QAAQ;EACR,kBAAkB;EAClB,8BAA8B;EAC9B,eAAe;EACf,iBAAiB;EACjB,sCAAsC;EACtC,cAAc;AAChB;AAEA;EACE,+BAA+B;AACjC;AAEA;EACE,cAAc;EACd,cAAc;EACd,YAAY;EACZ,+BAA+B;AACjC;AAEA;EACE,gBAAgB;EAChB,gBAAgB;EAChB,cAAc;AAChB;;AAEA,mCAAmC;AACnC;EACE,mDAAmD;AACrD;;AAEA,0BAA0B;AAC1B;EACE,aAAa;EACb,YAAY;EACZ,eAAe;EACf,sBAAsB;AACxB;AAEA;EACE,iBAAiB;AACnB;AAEA;EACE,aAAa;AACf;AAEA;EACE,aAAa;EACb,SAAS;EACT,WAAW;EACX,sBAAsB;EACtB,kBAAkB;AACpB;AAEA;EACE,gBAAgB;EAChB,sBAAsB;EACtB,YAAY;EACZ,gBAAgB;EAChB,aAAa;EACb,sBAAsB;EACtB,SAAS;AACX;;AAEA,iCAAiC;AACjC;EACE,gBAAgB;EAChB,eAAe;EACf,WAAW;EACX,eAAe;AACjB;;AAEA,kBAAkB;AAClB;AACE;IACE,cAAc;AAChB;AAEA;IACE,sBAAsB;AACxB;AAEA;IACE,mDAAmD;AACrD;AAEA;IACE,WAAW;IACX,eAAe;IACf,eAAe;IACf,QAAQ,EAAE,iDAAiD;IAC3D,mDAAmD;AACrD;AAEA;IACE,SAAS,EAAE,sCAAsC;AACnD;AAEA;IACE,kBAAkB,EAAE,8BAA8B;IAClD,WAAW;IACX,eAAe;IACf,QAAQ;AACV;AAEA;IACE,mBAAmB;IACnB,kBAAkB;IAClB,8DAA8D;IAC9D,WAAW;IACX,eAAe;IACf,sBAAsB;IACtB,gBAAgB,EAAE,qCAAqC;AACzD;AAEA;IACE,8DAA8D;IAC9D,gBAAgB;AAClB;AAEA;IACE,gBAAgB,EAAE,uCAAuC;AAC3D;AAEA;IACE,gBAAgB,EAAE,qCAAqC;AACzD;AAEA;IACE,SAAS;IACT,qCAAqC;IACrC,WAAW;IACX,eAAe;AACjB;AAEA;IACE,kBAAkB,EAAE,oCAAoC;IACxD,WAAW;IACX,eAAe;IACf,YAAY;AACd;AACF","sourcesContent":["<template>\r\n  <div class=\"page-viewer-wrapper\">\r\n    <!-- Header Row (spans full width above everything) -->\r\n    <div v-if=\"hasHeaderRow\"\r\n         class=\"header-row\"\r\n         :style=\"getHeaderRowStyle()\">\r\n      <Widget\r\n        v-for=\"widget in getHeaderRowWidgets()\"\r\n        :key=\"widget.id || widget.order\"\r\n        :widget=\"widget\"\r\n        :page-id=\"page.uniqueId\"\r\n        :editable=\"false\"\r\n        :row-background-color=\"page.layout.headerRow.backgroundColor || ''\"\r\n        :share-token=\"shareToken\"\r\n        @navigate=\"$emit('navigate', $event)\"\r\n      />\r\n    </div>\r\n\r\n  <div class=\"page-viewer-container\"\r\n       :class=\"{ 'has-left-column': hasLeftColumn, 'has-right-column': hasRightColumn }\">\r\n    <!-- Left Side Column -->\r\n    <div v-if=\"hasLeftColumn\"\r\n         class=\"side-column side-column-left\"\r\n         :style=\"getSideColumnStyle('left')\">\r\n      <Widget\r\n        v-for=\"widget in getSideColumnWidgets('left')\"\r\n        :key=\"widget.id || widget.order\"\r\n        :widget=\"widget\"\r\n        :page-id=\"page.uniqueId\"\r\n        :editable=\"false\"\r\n        :row-background-color=\"page.layout.sideColumns.left.backgroundColor || ''\"\r\n        :share-token=\"shareToken\"\r\n        @navigate=\"$emit('navigate', $event)\"\r\n      />\r\n    </div>\r\n\r\n    <!-- Main Content -->\r\n    <div class=\"page-viewer\">\r\n      <div\r\n        v-for=\"(row, rowIndex) in page.layout.rows\"\r\n        :key=\"rowIndex\"\r\n        class=\"page-row\"\r\n        :class=\"{ 'collapsible-row': row.collapsible }\"\r\n        :style=\"getRowStyle(row)\"\r\n      >\r\n        <!-- Section Header (only for collapsible rows) -->\r\n        <div\r\n          v-if=\"row.collapsible\"\r\n          class=\"row-section-header\"\r\n          :class=\"{ collapsed: isRowCollapsed(row) }\"\r\n          @click=\"toggleRowCollapsed(row)\"\r\n        >\r\n          <ChevronDown v-if=\"!isRowCollapsed(row)\" :size=\"20\" class=\"section-chevron\" />\r\n          <ChevronRight v-else :size=\"20\" class=\"section-chevron\" />\r\n          <span class=\"section-title\">{{ row.sectionTitle || 'Sectie' }}</span>\r\n        </div>\r\n\r\n        <!-- Row Content (collapsible) -->\r\n        <div\r\n          class=\"row-content\"\r\n          :class=\"{ collapsed: row.collapsible && isRowCollapsed(row) }\"\r\n          v-show=\"!row.collapsible || !isRowCollapsed(row)\"\r\n        >\r\n          <div\r\n            class=\"page-grid\"\r\n            :style=\"{ gridTemplateColumns: `repeat(${(row.columns || page.layout.columns) ?? 1}, minmax(0, 1fr))` }\"\r\n          >\r\n            <div\r\n              v-for=\"column in ((row.columns || page.layout.columns) ?? 1)\"\r\n              :key=\"column\"\r\n              class=\"page-column\"\r\n            >\r\n              <Widget\r\n                v-for=\"widget in getWidgetsForColumn(row, column)\"\r\n                :key=\"widget.order\"\r\n                :widget=\"widget\"\r\n                :page-id=\"page.uniqueId\"\r\n                :editable=\"false\"\r\n                :row-background-color=\"row.backgroundColor || ''\"\r\n                :share-token=\"shareToken\"\r\n                @navigate=\"$emit('navigate', $event)\"\r\n              />\r\n            </div>\r\n          </div>\r\n        </div>\r\n      </div>\r\n    </div>\r\n\r\n    <!-- Right Side Column -->\r\n    <div v-if=\"hasRightColumn\"\r\n         class=\"side-column side-column-right\"\r\n         :style=\"getSideColumnStyle('right')\">\r\n      <Widget\r\n        v-for=\"widget in getSideColumnWidgets('right')\"\r\n        :key=\"widget.id || widget.order\"\r\n        :widget=\"widget\"\r\n        :page-id=\"page.uniqueId\"\r\n        :editable=\"false\"\r\n        :row-background-color=\"page.layout.sideColumns.right.backgroundColor || ''\"\r\n        :share-token=\"shareToken\"\r\n        @navigate=\"$emit('navigate', $event)\"\r\n      />\r\n    </div>\r\n  </div>\r\n\r\n    <!-- Reactions & Comments Section (full width, below all columns) -->\r\n    <div v-if=\"showReactions || showComments\" class=\"reactions-comments-section\">\r\n      <ReactionBar\r\n        v-if=\"showReactions\"\r\n        :page-id=\"page.uniqueId\"\r\n        :reactions=\"pageReactions\"\r\n        :user-reactions=\"userReactions\"\r\n        @update=\"handleReactionsUpdate\"\r\n      />\r\n      <CommentSection\r\n        v-if=\"showComments\"\r\n        :page-id=\"page.uniqueId\"\r\n        :allow-comment-reactions=\"allowCommentReactions\"\r\n      />\r\n    </div>\r\n  </div>\r\n</template>\r\n\r\n<script>\r\nimport Widget from './Widget.vue';\r\nimport ReactionBar from './reactions/ReactionBar.vue';\r\nimport CommentSection from './reactions/CommentSection.vue';\r\nimport { getPageReactions } from '../services/CommentService.js';\r\nimport ChevronDown from 'vue-material-design-icons/ChevronDown.vue';\r\nimport ChevronRight from 'vue-material-design-icons/ChevronRight.vue';\r\n\r\nexport default {\r\n  name: 'PageViewer',\r\n  components: {\r\n    Widget,\r\n    ReactionBar,\r\n    CommentSection,\r\n    ChevronDown,\r\n    ChevronRight\r\n  },\r\n  props: {\r\n    page: {\r\n      type: Object,\r\n      required: true\r\n    },\r\n    engagementSettings: {\r\n      type: Object,\r\n      default: () => ({\r\n        allowPageReactions: true,\r\n        allowComments: true,\r\n        allowCommentReactions: true,\r\n        singleReactionPerUser: true\r\n      })\r\n    },\r\n    shareToken: {\r\n      type: String,\r\n      default: ''\r\n    },\r\n    isPublic: {\r\n      type: Boolean,\r\n      default: false\r\n    }\r\n  },\r\n  emits: ['navigate'],\r\n  data() {\r\n    return {\r\n      pageReactions: {},\r\n      userReactions: [],\r\n      collapsedRows: {} // Track collapsed state for collapsible rows in view mode\r\n    };\r\n  },\r\n  computed: {\r\n    hasHeaderRow() {\r\n      return this.page?.layout?.headerRow?.enabled &&\r\n             this.page.layout.headerRow.widgets?.length > 0;\r\n    },\r\n    hasLeftColumn() {\r\n      return this.page?.layout?.sideColumns?.left?.enabled &&\r\n             this.page.layout.sideColumns.left.widgets?.length > 0;\r\n    },\r\n    hasRightColumn() {\r\n      return this.page?.layout?.sideColumns?.right?.enabled &&\r\n             this.page.layout.sideColumns.right.widgets?.length > 0;\r\n    },\r\n    /**\r\n     * Check if reactions are allowed on this page\r\n     * Takes into account both global settings and page-level overrides\r\n     */\r\n    showReactions() {\r\n      // Global setting off = no reactions anywhere\r\n      if (!this.engagementSettings.allowPageReactions) {\r\n        return false;\r\n      }\r\n      // Check page-level override\r\n      const pageSetting = this.page?.settings?.allowReactions;\r\n      if (pageSetting === false) {\r\n        return false;\r\n      }\r\n      // Default: show reactions\r\n      return true;\r\n    },\r\n    /**\r\n     * Check if comments are allowed on this page\r\n     * Takes into account both global settings and page-level overrides\r\n     */\r\n    showComments() {\r\n      // Global setting off = no comments anywhere\r\n      if (!this.engagementSettings.allowComments) {\r\n        return false;\r\n      }\r\n      // Check page-level override\r\n      const pageSetting = this.page?.settings?.allowComments;\r\n      if (pageSetting === false) {\r\n        return false;\r\n      }\r\n      // Default: show comments\r\n      return true;\r\n    },\r\n    /**\r\n     * Check if reactions on comments are allowed\r\n     * Takes into account both global settings and page-level overrides\r\n     */\r\n    allowCommentReactions() {\r\n      // Global setting off = no comment reactions anywhere\r\n      if (!this.engagementSettings.allowCommentReactions) {\r\n        return false;\r\n      }\r\n      // Check page-level override\r\n      const pageSetting = this.page?.settings?.allowCommentReactions;\r\n      if (pageSetting === false) {\r\n        return false;\r\n      }\r\n      // Default: allow comment reactions\r\n      return true;\r\n    }\r\n  },\r\n  methods: {\r\n    getWidgetsForColumn(row, column) {\r\n      if (!row.widgets) return [];\r\n\r\n      // Filter widgets by column and sort by order\r\n      return row.widgets\r\n        .filter(w => w.column === column)\r\n        .sort((a, b) => a.order - b.order);\r\n    },\r\n    isRowCollapsed(row) {\r\n      // Check if this collapsible row is currently collapsed in view mode\r\n      if (!row.collapsible) return false;\r\n      // Use row.id as key, fallback to row's index position\r\n      const rowKey = row.id || `row-${this.page.layout.rows.indexOf(row)}`;\r\n      // Use stored state if available, otherwise use defaultCollapsed setting\r\n      return this.collapsedRows[rowKey] ?? row.defaultCollapsed ?? false;\r\n    },\r\n    toggleRowCollapsed(row) {\r\n      if (!row.collapsible) return;\r\n      const rowKey = row.id || `row-${this.page.layout.rows.indexOf(row)}`;\r\n      // Get current state (from stored state, or defaultCollapsed, or false)\r\n      const currentState = this.collapsedRows[rowKey] ?? row.defaultCollapsed ?? false;\r\n      this.collapsedRows = {\r\n        ...this.collapsedRows,\r\n        [rowKey]: !currentState\r\n      };\r\n    },\r\n    getSideColumnWidgets(side) {\r\n      const sideColumn = this.page?.layout?.sideColumns?.[side];\r\n      if (!sideColumn || !sideColumn.widgets) return [];\r\n      return [...sideColumn.widgets].sort((a, b) => (a.order || 0) - (b.order || 0));\r\n    },\r\n    getRowStyle(row) {\r\n      const style = {};\r\n\r\n      if (row.backgroundColor) {\r\n        style.backgroundColor = row.backgroundColor;\r\n\r\n        // Set text color based on background color\r\n        // Only Primary (dark green) needs white text\r\n        if (row.backgroundColor === 'var(--color-primary-element)') {\r\n          style.color = 'var(--color-primary-element-text)';\r\n        } else {\r\n          // Light backgrounds: use default dark text\r\n          style.color = 'var(--color-main-text)';\r\n        }\r\n      }\r\n\r\n      return style;\r\n    },\r\n    getSideColumnStyle(side) {\r\n      const sideColumn = this.page?.layout?.sideColumns?.[side];\r\n      if (!sideColumn) return {};\r\n\r\n      const style = {};\r\n\r\n      if (sideColumn.backgroundColor) {\r\n        style.backgroundColor = sideColumn.backgroundColor;\r\n\r\n        // Set text color based on background color\r\n        if (sideColumn.backgroundColor === 'var(--color-primary-element)') {\r\n          style.color = 'var(--color-primary-element-text)';\r\n        } else {\r\n          style.color = 'var(--color-main-text)';\r\n        }\r\n      } else {\r\n        // Explicitly set transparent to override CSS default\r\n        style.backgroundColor = 'transparent';\r\n      }\r\n\r\n      return style;\r\n    },\r\n    getHeaderRowWidgets() {\r\n      const headerRow = this.page?.layout?.headerRow;\r\n      if (!headerRow || !headerRow.widgets) return [];\r\n      return [...headerRow.widgets].sort((a, b) => (a.order || 0) - (b.order || 0));\r\n    },\r\n    getHeaderRowStyle() {\r\n      const headerRow = this.page?.layout?.headerRow;\r\n      if (!headerRow) return {};\r\n\r\n      const style = {};\r\n\r\n      if (headerRow.backgroundColor) {\r\n        style.backgroundColor = headerRow.backgroundColor;\r\n\r\n        if (headerRow.backgroundColor === 'var(--color-primary-element)') {\r\n          style.color = 'var(--color-primary-element-text)';\r\n        } else {\r\n          style.color = 'var(--color-main-text)';\r\n        }\r\n      } else {\r\n        // Explicitly set transparent to override CSS default\r\n        style.backgroundColor = 'transparent';\r\n      }\r\n\r\n      return style;\r\n    },\r\n    async loadReactions() {\r\n      if (!this.page?.uniqueId) return;\r\n      try {\r\n        const result = await getPageReactions(this.page.uniqueId);\r\n        this.pageReactions = result.reactions || {};\r\n        this.userReactions = result.userReactions || [];\r\n      } catch (error) {\r\n        console.error('Failed to load reactions:', error);\r\n      }\r\n    },\r\n    handleReactionsUpdate(result) {\r\n      this.pageReactions = result.reactions || {};\r\n      this.userReactions = result.userReactions || [];\r\n    }\r\n  },\r\n  watch: {\r\n    'page.uniqueId': {\r\n      immediate: true,\r\n      handler() {\r\n        this.loadReactions();\r\n      }\r\n    }\r\n  }\r\n};\r\n</script>\r\n\r\n<style scoped>\r\n.page-viewer-wrapper {\r\n  display: flex;\r\n  flex-direction: column;\r\n  width: 100%;\r\n}\r\n\r\n.page-viewer-container {\r\n  display: flex;\r\n  gap: 16px;\r\n  width: 100%;\r\n  max-width: 100%;\r\n  box-sizing: border-box;\r\n}\r\n\r\n.page-viewer {\r\n  flex: 1;\r\n  min-width: 0;\r\n  width: 100%;\r\n  max-width: 100%;\r\n  box-sizing: border-box;\r\n}\r\n\r\n/* Header Row Styles */\r\n.header-row {\r\n  width: 100%;\r\n  padding: 16px;\r\n  margin-bottom: 16px;\r\n  border-radius: var(--border-radius-container-large);\r\n  /* No default background - controlled by inline style via getHeaderRowStyle() */\r\n  box-sizing: border-box;\r\n  display: flex;\r\n  flex-direction: column;\r\n  gap: 12px;\r\n}\r\n\r\n.side-column {\r\n  flex-shrink: 0;\r\n  width: 250px;\r\n  min-width: 200px;\r\n  max-width: 300px;\r\n  padding: 16px;\r\n  border-radius: var(--border-radius-container-large);\r\n  /* No default background - controlled by inline style via getSideColumnStyle() */\r\n  box-sizing: border-box;\r\n  display: flex;\r\n  flex-direction: column;\r\n  gap: 12px;\r\n  align-self: flex-start;\r\n}\r\n\r\n.page-row {\r\n  margin-bottom: 12px;\r\n  padding: 16px;\r\n  border-radius: var(--border-radius-container-large);\r\n  box-sizing: border-box;\r\n  /* Without these, a wide child (table with min-content larger than its\r\n     column track) can blow the row past the viewport. min-width: 0 lets\r\n     the row shrink below its min-content, max-width: 100% keeps it inside\r\n     its parent. */\r\n  min-width: 0;\r\n  max-width: 100%;\r\n}\r\n\r\n/* Collapsible row styling */\r\n.page-row.collapsible-row {\r\n  padding: 0;\r\n  overflow: hidden;\r\n}\r\n\r\n.row-section-header {\r\n  display: flex;\r\n  align-items: center;\r\n  gap: 8px;\r\n  padding: 12px 16px;\r\n  background: rgba(0, 0, 0, 0.1);\r\n  cursor: pointer;\r\n  user-select: none;\r\n  transition: background-color 0.2s ease;\r\n  color: inherit;\r\n}\r\n\r\n.row-section-header:hover {\r\n  background: rgba(0, 0, 0, 0.15);\r\n}\r\n\r\n.row-section-header .section-chevron {\r\n  flex-shrink: 0;\r\n  color: inherit;\r\n  opacity: 0.7;\r\n  transition: transform 0.2s ease;\r\n}\r\n\r\n.row-section-header .section-title {\r\n  font-weight: 600;\r\n  font-size: 1.1em;\r\n  color: inherit;\r\n}\r\n\r\n/* Collapsed state header styling */\r\n.row-section-header.collapsed {\r\n  border-radius: var(--border-radius-container-large);\r\n}\r\n\r\n/* Row content container */\r\n.row-content {\r\n  padding: 16px;\r\n  min-width: 0;\r\n  max-width: 100%;\r\n  box-sizing: border-box;\r\n}\r\n\r\n.page-row.collapsible-row .row-content {\r\n  padding-top: 12px;\r\n}\r\n\r\n.row-content.collapsed {\r\n  display: none;\r\n}\r\n\r\n.page-grid {\r\n  display: grid;\r\n  gap: 12px;\r\n  width: 100%;\r\n  box-sizing: border-box;\r\n  align-items: start;\r\n}\r\n\r\n.page-column {\r\n  min-height: 50px;\r\n  box-sizing: border-box;\r\n  min-width: 0;\r\n  overflow: hidden;\r\n  display: flex;\r\n  flex-direction: column;\r\n  gap: 12px;\r\n}\r\n\r\n/* Reactions & Comments Section */\r\n.reactions-comments-section {\r\n  margin-top: 24px;\r\n  padding: 0 16px;\r\n  width: 100%;\r\n  max-width: 100%;\r\n}\r\n\r\n/* Mobile styles */\r\n@media (max-width: 768px) {\r\n  .page-viewer-wrapper {\r\n    padding: 0 8px;\r\n  }\r\n\r\n  .page-viewer-container {\r\n    flex-direction: column;\r\n  }\r\n\r\n  .header-row {\r\n    border-radius: var(--border-radius-container-large);\r\n  }\r\n\r\n  .side-column {\r\n    width: 100%;\r\n    min-width: 100%;\r\n    max-width: 100%;\r\n    order: 1; /* Side columns go below main content on mobile */\r\n    border-radius: var(--border-radius-container-large);\r\n  }\r\n\r\n  .side-column-left {\r\n    order: -1; /* Left column stays above on mobile */\r\n  }\r\n\r\n  .page-viewer {\r\n    overflow-x: hidden; /* Prevent horizontal scroll */\r\n    width: 100%;\r\n    max-width: 100%;\r\n    order: 0;\r\n  }\r\n\r\n  .page-row {\r\n    margin-bottom: 16px;\r\n    padding: 16px 12px;\r\n    border-radius: var(--border-radius-container-large) !important;\r\n    width: 100%;\r\n    max-width: 100%;\r\n    box-sizing: border-box;\r\n    overflow: hidden; /* Ensures border-radius is visible */\r\n  }\r\n\r\n  .page-row.collapsible-row {\r\n    border-radius: var(--border-radius-container-large) !important;\r\n    overflow: hidden;\r\n  }\r\n\r\n  .row-section-header {\r\n    border-radius: 0; /* Header takes full width inside row */\r\n  }\r\n\r\n  .row-section-header.collapsed {\r\n    border-radius: 0; /* Parent row handles border-radius */\r\n  }\r\n\r\n  .page-grid {\r\n    gap: 12px;\r\n    grid-template-columns: 1fr !important;\r\n    width: 100%;\r\n    max-width: 100%;\r\n  }\r\n\r\n  .page-column {\r\n    overflow-x: hidden; /* Prevent images from overflowing */\r\n    width: 100%;\r\n    max-width: 100%;\r\n    min-width: 0;\r\n  }\r\n}\r\n</style>\r\n"],"sourceRoot":""}]);
// Exports
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (___CSS_LOADER_EXPORT___);


/***/ },

/***/ "./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/dist/stylePostLoader.js!./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/PublicPageView.vue?vue&type=style&index=0&id=5157670d&scoped=true&lang=css"
/*!***************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/dist/stylePostLoader.js!./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/PublicPageView.vue?vue&type=style&index=0&id=5157670d&scoped=true&lang=css ***!
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
#intravox-public-page[data-v-5157670d] {
	min-height: 100vh;
	background: var(--color-main-background, #fff);
	display: flex;
	flex-direction: column;
}
.public-header[data-v-5157670d] {
	padding: 24px 20px;
	background: var(--color-main-background, #fff);
	border-bottom: 1px solid var(--color-border, #eee);
}
.page-title[data-v-5157670d] {
	margin: 0;
	font-size: 28px;
	font-weight: 600;
	color: var(--color-main-text, #333);
	max-width: min(1600px, 95vw);
	margin: 0 auto;
}
.public-nav-bar[data-v-5157670d] {
	background: var(--color-main-background, #fff);
	border-bottom: 1px solid var(--color-border, #eee);
	padding: 0 20px;
}
.public-breadcrumb[data-v-5157670d] {
	padding: 0 20px;
	max-width: min(1600px, 95vw);
	margin: 0 auto;
	width: 100%;
	box-sizing: border-box;
}
.public-content[data-v-5157670d] {
	flex: 1;
	padding: 20px;
	padding-bottom: 60px;
	max-width: min(1600px, 95vw);
	margin: 0 auto;
	width: 100%;
	box-sizing: border-box;
}
.public-footer[data-v-5157670d] {
	border-top: 1px solid var(--color-border, #eee);
	padding: 20px;
	background: var(--color-background-dark, #f5f5f5);
}
.public-loading[data-v-5157670d] {
	display: flex;
	flex-direction: column;
	align-items: center;
	justify-content: center;
	min-height: 100vh;
	gap: 16px;
	color: var(--color-text-maxcontrast, #666);
}
.loading-spinner[data-v-5157670d] {
	width: 32px;
	height: 32px;
	border: 3px solid var(--color-border, #eee);
	border-top-color: var(--color-primary, #0082c9);
	border-radius: 50%;
	animation: spin-5157670d 1s linear infinite;
}
@keyframes spin-5157670d {
to {
		transform: rotate(360deg);
}
}
.public-error[data-v-5157670d] {
	display: flex;
	flex-direction: column;
	align-items: center;
	justify-content: center;
	min-height: 100vh;
	text-align: center;
	padding: 20px;
}
.public-error h1[data-v-5157670d] {
	font-size: 24px;
	color: var(--color-main-text, #333);
	margin-bottom: 8px;
}
.public-error p[data-v-5157670d] {
	color: var(--color-text-maxcontrast, #666);
}
.public-password-challenge[data-v-5157670d] {
	display: flex;
	justify-content: center;
	align-items: center;
	min-height: 100vh;
}
.password-container[data-v-5157670d] {
	text-align: center;
	padding: 40px;
	background: var(--color-main-background, #fff);
	border-radius: 8px;
	box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
	max-width: 400px;
	width: 90%;
}
.password-container .lock-icon[data-v-5157670d] {
	font-size: 48px;
	margin-bottom: 10px;
	color: var(--color-text-maxcontrast, #999);
}
.password-container h2[data-v-5157670d] {
	font-size: 22px;
	margin: 10px 0 8px;
	color: var(--color-main-text, #333);
}
.password-container p[data-v-5157670d] {
	color: var(--color-text-maxcontrast, #666);
	line-height: 1.5;
	margin: 0 0 24px;
	font-size: 14px;
}
.password-container form[data-v-5157670d] {
	display: flex;
	flex-direction: column;
	gap: 12px;
}
.password-container input[type="password"][data-v-5157670d] {
	padding: 10px 14px;
	border: 1px solid var(--color-border, #ddd);
	border-radius: 6px;
	font-size: 14px;
	outline: none;
	transition: border-color 0.2s;
}
.password-container input[type="password"][data-v-5157670d]:focus {
	border-color: var(--color-primary, #0082c9);
}
.password-container button[type="submit"][data-v-5157670d] {
	padding: 10px 14px;
	background: var(--color-primary, #0082c9);
	color: #fff;
	border: none;
	border-radius: 6px;
	font-size: 14px;
	font-weight: 600;
	cursor: pointer;
	transition: background 0.2s;
}
.password-container button[type="submit"][data-v-5157670d]:hover {
	background: var(--color-primary-hover, #006ba7);
}
.password-container button[type="submit"][data-v-5157670d]:disabled {
	opacity: 0.6;
	cursor: not-allowed;
}
.password-error[data-v-5157670d] {
	color: var(--color-error, #e9322d) !important;
	font-size: 13px !important;
	margin: -4px 0 0 !important;
}
@media (max-width: 768px) {
.public-header[data-v-5157670d] {
		padding: 16px 12px;
}
.page-title[data-v-5157670d] {
		font-size: 22px;
}
.public-content[data-v-5157670d] {
		padding: 16px 12px;
}
}
`, "",{"version":3,"sources":["webpack://./src/components/PublicPageView.vue"],"names":[],"mappings":";AA8VA;CACC,iBAAiB;CACjB,8CAA8C;CAC9C,aAAa;CACb,sBAAsB;AACvB;AAEA;CACC,kBAAkB;CAClB,8CAA8C;CAC9C,kDAAkD;AACnD;AAEA;CACC,SAAS;CACT,eAAe;CACf,gBAAgB;CAChB,mCAAmC;CACnC,4BAA4B;CAC5B,cAAc;AACf;AAEA;CACC,8CAA8C;CAC9C,kDAAkD;CAClD,eAAe;AAChB;AAEA;CACC,eAAe;CACf,4BAA4B;CAC5B,cAAc;CACd,WAAW;CACX,sBAAsB;AACvB;AAEA;CACC,OAAO;CACP,aAAa;CACb,oBAAoB;CACpB,4BAA4B;CAC5B,cAAc;CACd,WAAW;CACX,sBAAsB;AACvB;AAEA;CACC,+CAA+C;CAC/C,aAAa;CACb,iDAAiD;AAClD;AAEA;CACC,aAAa;CACb,sBAAsB;CACtB,mBAAmB;CACnB,uBAAuB;CACvB,iBAAiB;CACjB,SAAS;CACT,0CAA0C;AAC3C;AAEA;CACC,WAAW;CACX,YAAY;CACZ,2CAA2C;CAC3C,+CAA+C;CAC/C,kBAAkB;CAClB,2CAAkC;AACnC;AAEA;AACC;EACC,yBAAyB;AAC1B;AACD;AAEA;CACC,aAAa;CACb,sBAAsB;CACtB,mBAAmB;CACnB,uBAAuB;CACvB,iBAAiB;CACjB,kBAAkB;CAClB,aAAa;AACd;AAEA;CACC,eAAe;CACf,mCAAmC;CACnC,kBAAkB;AACnB;AAEA;CACC,0CAA0C;AAC3C;AAEA;CACC,aAAa;CACb,uBAAuB;CACvB,mBAAmB;CACnB,iBAAiB;AAClB;AAEA;CACC,kBAAkB;CAClB,aAAa;CACb,8CAA8C;CAC9C,kBAAkB;CAClB,yCAAyC;CACzC,gBAAgB;CAChB,UAAU;AACX;AAEA;CACC,eAAe;CACf,mBAAmB;CACnB,0CAA0C;AAC3C;AAEA;CACC,eAAe;CACf,kBAAkB;CAClB,mCAAmC;AACpC;AAEA;CACC,0CAA0C;CAC1C,gBAAgB;CAChB,gBAAgB;CAChB,eAAe;AAChB;AAEA;CACC,aAAa;CACb,sBAAsB;CACtB,SAAS;AACV;AAEA;CACC,kBAAkB;CAClB,2CAA2C;CAC3C,kBAAkB;CAClB,eAAe;CACf,aAAa;CACb,6BAA6B;AAC9B;AAEA;CACC,2CAA2C;AAC5C;AAEA;CACC,kBAAkB;CAClB,yCAAyC;CACzC,WAAW;CACX,YAAY;CACZ,kBAAkB;CAClB,eAAe;CACf,gBAAgB;CAChB,eAAe;CACf,2BAA2B;AAC5B;AAEA;CACC,+CAA+C;AAChD;AAEA;CACC,YAAY;CACZ,mBAAmB;AACpB;AAEA;CACC,6CAA6C;CAC7C,0BAA0B;CAC1B,2BAA2B;AAC5B;AAEA;AACC;EACC,kBAAkB;AACnB;AAEA;EACC,eAAe;AAChB;AAEA;EACC,kBAAkB;AACnB;AAED","sourcesContent":["<template>\n\t<div id=\"intravox-public-page\">\n\t\t<a href=\"#intravox-main-content\" class=\"skip-link\">{{ t('intravox', 'Skip to main content') }}</a>\n\n\t\t<!-- Loading state -->\n\t\t<div v-if=\"loading\" class=\"public-loading\" role=\"status\" aria-live=\"polite\">\n\t\t\t<div class=\"loading-spinner\" />\n\t\t\t<span>{{ t('intravox', 'Loading...') }}</span>\n\t\t</div>\n\n\t\t<!-- Password required state -->\n\t\t<div v-else-if=\"passwordRequired\" class=\"public-password-challenge\">\n\t\t\t<div class=\"password-container\">\n\t\t\t\t<div class=\"lock-icon\">&#128274;</div>\n\t\t\t\t<h2>{{ t('intravox', 'Password Required') }}</h2>\n\t\t\t\t<p>{{ t('intravox', 'This shared page is password protected. Enter the password to continue.') }}</p>\n\t\t\t\t<form @submit.prevent=\"submitPassword\">\n\t\t\t\t\t<label for=\"intravox-public-password\" class=\"visually-hidden\">{{ t('intravox', 'Password') }}</label>\n\t\t\t\t\t<input\n\t\t\t\t\t\tid=\"intravox-public-password\"\n\t\t\t\t\t\tref=\"passwordInput\"\n\t\t\t\t\t\tv-model=\"password\"\n\t\t\t\t\t\ttype=\"password\"\n\t\t\t\t\t\t:placeholder=\"t('intravox', 'Password')\"\n\t\t\t\t\t\tautocomplete=\"off\"\n\t\t\t\t\t\trequired>\n\t\t\t\t\t<p v-if=\"passwordError\" class=\"password-error\" role=\"alert\">\n\t\t\t\t\t\t{{ t('intravox', 'Incorrect password. Please try again.') }}\n\t\t\t\t\t</p>\n\t\t\t\t\t<button type=\"submit\" :disabled=\"submittingPassword\">\n\t\t\t\t\t\t{{ t('intravox', 'Unlock') }}\n\t\t\t\t\t</button>\n\t\t\t\t</form>\n\t\t\t</div>\n\t\t</div>\n\n\t\t<!-- Error state -->\n\t\t<div v-else-if=\"error\" class=\"public-error\" role=\"alert\">\n\t\t\t<h1>{{ t('intravox', 'Page Not Available') }}</h1>\n\t\t\t<p>{{ error }}</p>\n\t\t</div>\n\n\t\t<!-- Page content -->\n\t\t<template v-else-if=\"pageData\">\n\t\t\t<!-- Header -->\n\t\t\t<header class=\"public-header\">\n\t\t\t\t<h1 class=\"page-title\">{{ pageData.title }}</h1>\n\t\t\t</header>\n\n\t\t\t<!-- Navigation (if available) -->\n\t\t\t<div v-if=\"navigation && navigation.items && navigation.items.length > 0\" class=\"public-nav-bar\">\n\t\t\t\t<Navigation\n\t\t\t\t\t:items=\"navigation.items\"\n\t\t\t\t\t:type=\"navigation.type\"\n\t\t\t\t\t:is-public=\"true\"\n\t\t\t\t\t@navigate=\"handleNavigation\"\n\t\t\t\t\t@show-tree=\"showPageTree = true\" />\n\t\t\t</div>\n\n\t\t\t<!-- Breadcrumb -->\n\t\t\t<div v-if=\"breadcrumb.length > 0\" class=\"public-breadcrumb\">\n\t\t\t\t<Breadcrumb\n\t\t\t\t\t:breadcrumb=\"breadcrumb\"\n\t\t\t\t\t@navigate=\"handleBreadcrumbNavigate\" />\n\t\t\t</div>\n\n\t\t\t<!-- Page Tree Modal -->\n\t\t\t<PageTreeModal\n\t\t\t\tv-if=\"showPageTree\"\n\t\t\t\t:share-token=\"token\"\n\t\t\t\t:current-page-id=\"currentPageUniqueId\"\n\t\t\t\t@navigate=\"handleTreeNavigate\"\n\t\t\t\t@close=\"showPageTree = false\" />\n\n\t\t\t<!-- Main content -->\n\t\t\t<main class=\"public-content\" id=\"intravox-main-content\">\n\t\t\t\t<PageViewer\n\t\t\t\t\t:page=\"pageData\"\n\t\t\t\t\t:is-public=\"true\"\n\t\t\t\t\t:share-token=\"token\"\n\t\t\t\t\t:engagement-settings=\"engagementSettings\"\n\t\t\t\t\t@navigate=\"handleNavigation\" />\n\t\t\t</main>\n\n\t\t\t<!-- Footer -->\n\t\t\t<div v-if=\"footerContent\" class=\"public-footer\">\n\t\t\t\t<Footer\n\t\t\t\t\t:footer-content=\"footerContent\"\n\t\t\t\t\t:can-edit=\"false\"\n\t\t\t\t\t:is-public=\"true\" />\n\t\t\t</div>\n\t\t</template>\n\t</div>\n</template>\n\n<script>\nimport { defineAsyncComponent } from 'vue'\nimport { translate as t } from '@nextcloud/l10n'\nimport { generateUrl } from '@nextcloud/router'\nimport axios from '@nextcloud/axios'\nimport PageViewer from './PageViewer.vue'\nimport Navigation from './Navigation.vue'\nimport Footer from './Footer.vue'\nimport Breadcrumb from './Breadcrumb.vue'\n\nexport default {\n\tname: 'PublicPageView',\n\tcomponents: {\n\t\tPageViewer,\n\t\tNavigation,\n\t\tFooter,\n\t\tBreadcrumb,\n\t\t// Async to match App.vue's strategy. See scripts/check-import-consistency.js.\n\t\tPageTreeModal: defineAsyncComponent(() => import('./PageTreeModal.vue')),\n\t},\n\tprops: {\n\t\tinitialPageData: {\n\t\t\ttype: Object,\n\t\t\tdefault: null,\n\t\t},\n\t\ttoken: {\n\t\t\ttype: String,\n\t\t\trequired: true,\n\t\t},\n\t\tpageUniqueId: {\n\t\t\ttype: String,\n\t\t\tdefault: '',\n\t\t},\n\t},\n\tdata() {\n\t\treturn {\n\t\t\tpageData: null,\n\t\t\tnavigation: null,\n\t\t\tfooterContent: '',\n\t\t\tbreadcrumb: [],\n\t\t\tshowPageTree: false,\n\t\t\tloading: true,\n\t\t\terror: null,\n\t\t\tpasswordRequired: false,\n\t\t\tpassword: '',\n\t\t\tpasswordError: false,\n\t\t\tsubmittingPassword: false,\n\t\t\t// Disabled engagement settings for public pages\n\t\t\tengagementSettings: {\n\t\t\t\tallowPageReactions: false,\n\t\t\t\tallowComments: false,\n\t\t\t\tallowCommentReactions: false,\n\t\t\t},\n\t\t}\n\t},\n\tcomputed: {\n\t\tcurrentPageUniqueId() {\n\t\t\treturn this.pageData?.uniqueId || this.pageUniqueId || ''\n\t\t},\n\t},\n\tasync mounted() {\n\t\t// Use initial data if provided (from PHP template)\n\t\tif (this.initialPageData) {\n\t\t\tthis.pageData = this.initialPageData\n\t\t\tthis.loading = false\n\t\t}\n\n\t\t// Load page data from API (or refresh)\n\t\tawait this.loadPage()\n\n\t\t// Load navigation filtered by share scope\n\t\tif (this.pageData && this.token) {\n\t\t\tawait this.loadNavigation()\n\t\t}\n\t},\n\tmethods: {\n\t\tt(app, key, vars = {}) {\n\t\t\treturn t(app, key, vars)\n\t\t},\n\t\tasync loadPage() {\n\t\t\ttry {\n\t\t\t\t// Determine page to load: prop > query param > hash > homepage fallback\n\t\t\t\tlet uniqueId = this.pageUniqueId || this.getPageIdFromUrl()\n\t\t\t\tif (!uniqueId) {\n\t\t\t\t\t// No page specified — try to load the homepage from the page tree\n\t\t\t\t\tuniqueId = await this.getHomepageUniqueId()\n\t\t\t\t\tif (!uniqueId) {\n\t\t\t\t\t\tthis.error = t('intravox', 'No page specified.')\n\t\t\t\t\t\tthis.loading = false\n\t\t\t\t\t\treturn\n\t\t\t\t\t}\n\t\t\t\t}\n\n\t\t\t\tconst response = await axios.get(\n\t\t\t\t\tgenerateUrl('/apps/intravox/api/share/{token}/page/{uniqueId}', {\n\t\t\t\t\t\ttoken: this.token,\n\t\t\t\t\t\tuniqueId,\n\t\t\t\t\t}),\n\t\t\t\t)\n\t\t\t\tthis.pageData = response.data\n\t\t\t\tthis.breadcrumb = response.data.breadcrumb || []\n\t\t\t\tthis.error = null\n\t\t\t\t// Update URL hash for back/forward navigation\n\t\t\t\tthis.updateUrl(uniqueId)\n\t\t\t} catch (err) {\n\t\t\t\tif (err.response?.status === 401 && err.response?.data?.passwordRequired) {\n\t\t\t\t\tthis.passwordRequired = true\n\t\t\t\t\tthis.$nextTick(() => this.$refs.passwordInput?.focus())\n\t\t\t\t} else if (!this.pageData) {\n\t\t\t\t\tthis.error = t('intravox', 'This page is not available or the share link has expired.')\n\t\t\t\t}\n\t\t\t} finally {\n\t\t\t\tthis.loading = false\n\t\t\t}\n\t\t},\n\t\tgetPageIdFromUrl() {\n\t\t\t// Check ?page= query parameter first (survives chat apps, email clients)\n\t\t\tconst params = new URLSearchParams(window.location.search)\n\t\t\tconst pageFromQuery = params.get('page')\n\t\t\tif (pageFromQuery) {\n\t\t\t\treturn pageFromQuery\n\t\t\t}\n\t\t\t// Fall back to hash (legacy URLs)\n\t\t\tconst hash = window.location.hash\n\t\t\tif (hash.startsWith('#page-')) {\n\t\t\t\treturn hash.substring(1)\n\t\t\t}\n\t\t\tif (hash.length > 1) {\n\t\t\t\treturn hash.substring(1)\n\t\t\t}\n\t\t\treturn ''\n\t\t},\n\t\tasync getHomepageUniqueId() {\n\t\t\ttry {\n\t\t\t\tconst response = await axios.get(\n\t\t\t\t\tgenerateUrl('/apps/intravox/api/share/{token}/tree', { token: this.token }),\n\t\t\t\t)\n\t\t\t\tconst tree = response.data?.tree || []\n\t\t\t\t// First item in the tree is typically the homepage\n\t\t\t\tif (tree.length > 0 && tree[0].uniqueId) {\n\t\t\t\t\treturn tree[0].uniqueId\n\t\t\t\t}\n\t\t\t} catch (err) {\n\t\t\t\t// Page tree is optional, fail silently\n\t\t\t}\n\t\t\treturn ''\n\t\t},\n\t\tupdateUrl(uniqueId) {\n\t\t\tif (uniqueId) {\n\t\t\t\t// Keep ?page= as the canonical format (survives sharing via chat/email)\n\t\t\t\t// Remove any legacy hash fragment\n\t\t\t\tconst url = new URL(window.location.href)\n\t\t\t\turl.searchParams.set('page', uniqueId)\n\t\t\t\turl.hash = ''\n\t\t\t\twindow.history.replaceState(null, '', url.toString())\n\t\t\t}\n\t\t},\n\t\tasync loadNavigation() {\n\t\t\ttry {\n\t\t\t\tconst response = await axios.get(\n\t\t\t\t\tgenerateUrl('/apps/intravox/api/share/{token}/navigation', { token: this.token }),\n\t\t\t\t)\n\t\t\t\tthis.navigation = response.data.navigation\n\t\t\t} catch (err) {\n\t\t\t\t// Navigation is optional, fail silently\n\t\t\t\tthis.navigation = null\n\t\t\t}\n\t\t},\n\t\tasync loadFooter() {\n\t\t\ttry {\n\t\t\t\tconst response = await axios.get(\n\t\t\t\t\tgenerateUrl('/apps/intravox/public/api/footer/{token}', { token: this.token }),\n\t\t\t\t)\n\t\t\t\tthis.footerContent = response.data.content || ''\n\t\t\t} catch (err) {\n\t\t\t\t// Footer is optional, fail silently\n\t\t\t\tthis.footerContent = ''\n\t\t\t}\n\t\t},\n\t\thandleNavigation(item) {\n\t\t\tif (item.uniqueId) {\n\t\t\t\tthis.loadPageByUniqueId(item.uniqueId)\n\t\t\t} else if (item.url) {\n\t\t\t\tif (item.url.startsWith('http') || item.url.startsWith('//')) {\n\t\t\t\t\twindow.open(item.url, item.target || '_blank')\n\t\t\t\t} else {\n\t\t\t\t\twindow.location.href = item.url\n\t\t\t\t}\n\t\t\t}\n\t\t},\n\t\thandleBreadcrumbNavigate(pageId) {\n\t\t\tif (pageId) {\n\t\t\t\tthis.loadPageByUniqueId(pageId)\n\t\t\t}\n\t\t},\n\t\thandleTreeNavigate(uniqueId) {\n\t\t\tthis.showPageTree = false\n\t\t\tif (uniqueId) {\n\t\t\t\tthis.loadPageByUniqueId(uniqueId)\n\t\t\t}\n\t\t},\n\t\tasync loadPageByUniqueId(uniqueId) {\n\t\t\ttry {\n\t\t\t\tthis.loading = true\n\t\t\t\tconst response = await axios.get(\n\t\t\t\t\tgenerateUrl('/apps/intravox/api/share/{token}/page/{uniqueId}', {\n\t\t\t\t\t\ttoken: this.token,\n\t\t\t\t\t\tuniqueId,\n\t\t\t\t\t}),\n\t\t\t\t)\n\t\t\t\tthis.pageData = response.data\n\t\t\t\tthis.breadcrumb = response.data.breadcrumb || []\n\t\t\t\tthis.error = null\n\t\t\t\tthis.updateUrl(uniqueId)\n\t\t\t\twindow.scrollTo(0, 0)\n\t\t\t} catch (err) {\n\t\t\t\tif (err.response?.status === 401 && err.response?.data?.passwordRequired) {\n\t\t\t\t\tthis.passwordRequired = true\n\t\t\t\t\tthis.$nextTick(() => this.$refs.passwordInput?.focus())\n\t\t\t\t} else {\n\t\t\t\t\tthis.error = t('intravox', 'This page is not available or the share link has expired.')\n\t\t\t\t}\n\t\t\t} finally {\n\t\t\t\tthis.loading = false\n\t\t\t}\n\t\t},\n\t\tasync submitPassword() {\n\t\t\tthis.submittingPassword = true\n\t\t\tthis.passwordError = false\n\t\t\ttry {\n\t\t\t\tconst authenticateUrl = generateUrl('/apps/intravox/s/{shareToken}/authenticate', {\n\t\t\t\t\tshareToken: this.token,\n\t\t\t\t})\n\t\t\t\tawait axios.post(authenticateUrl, {\n\t\t\t\t\tpassword: this.password,\n\t\t\t\t}, {\n\t\t\t\t\theaders: { 'Content-Type': 'application/x-www-form-urlencoded' },\n\t\t\t\t\tmaxRedirects: 0,\n\t\t\t\t\tvalidateStatus: (status) => status < 400,\n\t\t\t\t})\n\t\t\t\t// Password accepted — reload the page to pick up the session cookie\n\t\t\t\twindow.location.reload()\n\t\t\t} catch (err) {\n\t\t\t\tthis.passwordError = true\n\t\t\t\tthis.password = ''\n\t\t\t\tthis.$nextTick(() => this.$refs.passwordInput?.focus())\n\t\t\t} finally {\n\t\t\t\tthis.submittingPassword = false\n\t\t\t}\n\t\t},\n\t},\n}\n</script>\n\n<style scoped>\n#intravox-public-page {\n\tmin-height: 100vh;\n\tbackground: var(--color-main-background, #fff);\n\tdisplay: flex;\n\tflex-direction: column;\n}\n\n.public-header {\n\tpadding: 24px 20px;\n\tbackground: var(--color-main-background, #fff);\n\tborder-bottom: 1px solid var(--color-border, #eee);\n}\n\n.page-title {\n\tmargin: 0;\n\tfont-size: 28px;\n\tfont-weight: 600;\n\tcolor: var(--color-main-text, #333);\n\tmax-width: min(1600px, 95vw);\n\tmargin: 0 auto;\n}\n\n.public-nav-bar {\n\tbackground: var(--color-main-background, #fff);\n\tborder-bottom: 1px solid var(--color-border, #eee);\n\tpadding: 0 20px;\n}\n\n.public-breadcrumb {\n\tpadding: 0 20px;\n\tmax-width: min(1600px, 95vw);\n\tmargin: 0 auto;\n\twidth: 100%;\n\tbox-sizing: border-box;\n}\n\n.public-content {\n\tflex: 1;\n\tpadding: 20px;\n\tpadding-bottom: 60px;\n\tmax-width: min(1600px, 95vw);\n\tmargin: 0 auto;\n\twidth: 100%;\n\tbox-sizing: border-box;\n}\n\n.public-footer {\n\tborder-top: 1px solid var(--color-border, #eee);\n\tpadding: 20px;\n\tbackground: var(--color-background-dark, #f5f5f5);\n}\n\n.public-loading {\n\tdisplay: flex;\n\tflex-direction: column;\n\talign-items: center;\n\tjustify-content: center;\n\tmin-height: 100vh;\n\tgap: 16px;\n\tcolor: var(--color-text-maxcontrast, #666);\n}\n\n.loading-spinner {\n\twidth: 32px;\n\theight: 32px;\n\tborder: 3px solid var(--color-border, #eee);\n\tborder-top-color: var(--color-primary, #0082c9);\n\tborder-radius: 50%;\n\tanimation: spin 1s linear infinite;\n}\n\n@keyframes spin {\n\tto {\n\t\ttransform: rotate(360deg);\n\t}\n}\n\n.public-error {\n\tdisplay: flex;\n\tflex-direction: column;\n\talign-items: center;\n\tjustify-content: center;\n\tmin-height: 100vh;\n\ttext-align: center;\n\tpadding: 20px;\n}\n\n.public-error h1 {\n\tfont-size: 24px;\n\tcolor: var(--color-main-text, #333);\n\tmargin-bottom: 8px;\n}\n\n.public-error p {\n\tcolor: var(--color-text-maxcontrast, #666);\n}\n\n.public-password-challenge {\n\tdisplay: flex;\n\tjustify-content: center;\n\talign-items: center;\n\tmin-height: 100vh;\n}\n\n.password-container {\n\ttext-align: center;\n\tpadding: 40px;\n\tbackground: var(--color-main-background, #fff);\n\tborder-radius: 8px;\n\tbox-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);\n\tmax-width: 400px;\n\twidth: 90%;\n}\n\n.password-container .lock-icon {\n\tfont-size: 48px;\n\tmargin-bottom: 10px;\n\tcolor: var(--color-text-maxcontrast, #999);\n}\n\n.password-container h2 {\n\tfont-size: 22px;\n\tmargin: 10px 0 8px;\n\tcolor: var(--color-main-text, #333);\n}\n\n.password-container p {\n\tcolor: var(--color-text-maxcontrast, #666);\n\tline-height: 1.5;\n\tmargin: 0 0 24px;\n\tfont-size: 14px;\n}\n\n.password-container form {\n\tdisplay: flex;\n\tflex-direction: column;\n\tgap: 12px;\n}\n\n.password-container input[type=\"password\"] {\n\tpadding: 10px 14px;\n\tborder: 1px solid var(--color-border, #ddd);\n\tborder-radius: 6px;\n\tfont-size: 14px;\n\toutline: none;\n\ttransition: border-color 0.2s;\n}\n\n.password-container input[type=\"password\"]:focus {\n\tborder-color: var(--color-primary, #0082c9);\n}\n\n.password-container button[type=\"submit\"] {\n\tpadding: 10px 14px;\n\tbackground: var(--color-primary, #0082c9);\n\tcolor: #fff;\n\tborder: none;\n\tborder-radius: 6px;\n\tfont-size: 14px;\n\tfont-weight: 600;\n\tcursor: pointer;\n\ttransition: background 0.2s;\n}\n\n.password-container button[type=\"submit\"]:hover {\n\tbackground: var(--color-primary-hover, #006ba7);\n}\n\n.password-container button[type=\"submit\"]:disabled {\n\topacity: 0.6;\n\tcursor: not-allowed;\n}\n\n.password-error {\n\tcolor: var(--color-error, #e9322d) !important;\n\tfont-size: 13px !important;\n\tmargin: -4px 0 0 !important;\n}\n\n@media (max-width: 768px) {\n\t.public-header {\n\t\tpadding: 16px 12px;\n\t}\n\n\t.page-title {\n\t\tfont-size: 22px;\n\t}\n\n\t.public-content {\n\t\tpadding: 16px 12px;\n\t}\n\n}\n</style>\n"],"sourceRoot":""}]);
// Exports
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (___CSS_LOADER_EXPORT___);


/***/ },

/***/ "./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/dist/stylePostLoader.js!./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/ShareButton.vue?vue&type=style&index=0&id=29169d71&scoped=true&lang=css"
/*!************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/dist/stylePostLoader.js!./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/ShareButton.vue?vue&type=style&index=0&id=29169d71&scoped=true&lang=css ***!
  \************************************************************************************************************************************************************************************************************************************************************/
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
.share-button-container[data-v-29169d71] {
  display: inline-flex;
  align-items: center;
}
.share-button-active[data-v-29169d71] {
  color: var(--color-primary-element) !important;
}
.share-button-inactive[data-v-29169d71] {
  color: var(--color-text-maxcontrast) !important;
}
`, "",{"version":3,"sources":["webpack://./src/components/ShareButton.vue"],"names":[],"mappings":";AAkIA;EACE,oBAAoB;EACpB,mBAAmB;AACrB;AAEA;EACE,8CAA8C;AAChD;AAEA;EACE,+CAA+C;AACjD","sourcesContent":["<template>\n  <div v-if=\"shareInfo !== null\" class=\"share-button-container\">\n    <!-- Active share: primary colored icon -->\n    <NcButton v-if=\"hasShare\"\n              type=\"tertiary\"\n              :aria-label=\"t('Public link')\"\n              :title=\"t('This page is shared via a public link')\"\n              class=\"share-button-active\"\n              @click=\"showDialog = true\">\n      <template #icon>\n        <LinkVariant :size=\"20\" />\n      </template>\n    </NcButton>\n\n    <!-- No share: muted icon -->\n    <NcButton v-else\n              type=\"tertiary\"\n              :aria-label=\"t('Share this page')\"\n              :title=\"t('Share this page')\"\n              class=\"share-button-inactive\"\n              @click=\"showNoShareDialog = true\">\n      <template #icon>\n        <LinkVariantOff :size=\"20\" />\n      </template>\n    </NcButton>\n\n    <ShareDialog\n      v-if=\"showDialog\"\n      :share-info=\"shareInfo\"\n      :page-title=\"pageTitle\"\n      @close=\"showDialog = false\"\n    />\n\n    <NoShareDialog\n      v-if=\"showNoShareDialog\"\n      :share-info=\"shareInfo\"\n      :page-title=\"pageTitle\"\n      @close=\"showNoShareDialog = false\"\n    />\n  </div>\n</template>\n\n<script>\nimport axios from '@nextcloud/axios';\nimport { generateUrl } from '@nextcloud/router';\nimport { translate as t } from '@nextcloud/l10n';\nimport { NcButton } from '@nextcloud/vue';\nimport LinkVariant from 'vue-material-design-icons/LinkVariant.vue';\nimport LinkVariantOff from 'vue-material-design-icons/LinkVariantOff.vue';\nimport ShareDialog from './ShareDialog.vue';\nimport NoShareDialog from './NoShareDialog.vue';\n\nexport default {\n  name: 'ShareButton',\n  components: {\n    NcButton,\n    LinkVariant,\n    LinkVariantOff,\n    ShareDialog,\n    NoShareDialog\n  },\n  props: {\n    pageUniqueId: {\n      type: String,\n      required: true\n    },\n    pageTitle: {\n      type: String,\n      default: ''\n    },\n    language: {\n      type: String,\n      default: 'en'\n    }\n  },\n  data() {\n    return {\n      shareInfo: null,\n      loading: false,\n      showDialog: false,\n      showNoShareDialog: false,\n      error: null\n    };\n  },\n  computed: {\n    hasShare() {\n      return this.shareInfo?.hasShare === true;\n    }\n  },\n  watch: {\n    pageUniqueId: {\n      immediate: true,\n      handler(newId) {\n        if (newId) {\n          this.loadShareInfo();\n        }\n      }\n    }\n  },\n  methods: {\n    t(key, vars = {}) {\n      return t('intravox', key, vars);\n    },\n    async loadShareInfo() {\n      if (!this.pageUniqueId) {\n        return;\n      }\n\n      this.loading = true;\n      this.error = null;\n\n      try {\n        const response = await axios.get(\n          generateUrl('/apps/intravox/api/pages/{uniqueId}/share-info', {\n            uniqueId: this.pageUniqueId\n          })\n        );\n        this.shareInfo = response.data;\n      } catch (err) {\n        this.shareInfo = null;\n        this.error = err.message;\n      } finally {\n        this.loading = false;\n      }\n    }\n  }\n};\n</script>\n\n<style scoped>\n.share-button-container {\n  display: inline-flex;\n  align-items: center;\n}\n\n.share-button-active {\n  color: var(--color-primary-element) !important;\n}\n\n.share-button-inactive {\n  color: var(--color-text-maxcontrast) !important;\n}\n</style>\n"],"sourceRoot":""}]);
// Exports
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (___CSS_LOADER_EXPORT___);


/***/ },

/***/ "./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/dist/stylePostLoader.js!./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/ShareDialog.vue?vue&type=style&index=0&id=7dad0ef2&scoped=true&lang=css"
/*!************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/dist/stylePostLoader.js!./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/ShareDialog.vue?vue&type=style&index=0&id=7dad0ef2&scoped=true&lang=css ***!
  \************************************************************************************************************************************************************************************************************************************************************/
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
.share-dialog[data-v-7dad0ef2] {
  padding: 16px;
}
.share-scope[data-v-7dad0ef2] {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 12px;
  background: var(--color-background-hover);
  border-radius: var(--border-radius);
  margin-bottom: 16px;
}
.scope-label[data-v-7dad0ef2] {
  color: var(--color-text-maxcontrast);
}
.share-password-badge[data-v-7dad0ef2] {
  display: flex;
  align-items: flex-start;
  gap: 8px;
  padding: 10px 12px;
  background: var(--color-warning-hover, #fff3cd);
  border-radius: var(--border-radius);
  margin-bottom: 16px;
  flex-wrap: wrap;
}
.share-password-badge .password-label[data-v-7dad0ef2] {
  font-weight: 600;
  font-size: 13px;
  color: var(--color-main-text);
}
.share-password-badge .password-hint[data-v-7dad0ef2] {
  font-size: 12px;
  color: var(--color-text-maxcontrast);
  width: 100%;
  padding-left: 24px;
}
.share-copy-row[data-v-7dad0ef2] {
  margin-bottom: 16px;
}
.share-navigation[data-v-7dad0ef2] {
  margin-bottom: 16px;
}
.share-navigation h4[data-v-7dad0ef2] {
  font-size: 14px;
  font-weight: 600;
  margin-bottom: 8px;
  color: var(--color-text-maxcontrast);
}
.nav-tree[data-v-7dad0ef2] {
  margin: 0;
  padding-left: 0;
  list-style: none;
  font-size: 13px;
}
.nav-tree-item[data-v-7dad0ef2] {
  margin: 2px 0;
  color: var(--color-main-text);
}
.nav-tree-children[data-v-7dad0ef2] {
  margin: 0;
  padding-left: 20px;
  list-style: none;
}
.nav-tree-child[data-v-7dad0ef2] {
  margin: 2px 0;
  color: var(--color-text-maxcontrast);
  position: relative;
  padding-left: 14px;
}
.nav-tree-child[data-v-7dad0ef2]::before {
  content: '';
  position: absolute;
  left: 0;
  top: 0;
  bottom: 50%;
  width: 8px;
  border-left: 1px solid var(--color-border-dark);
  border-bottom: 1px solid var(--color-border-dark);
}
.nav-tree-child[data-v-7dad0ef2]:last-child::before {
  border-left-style: solid;
}
.share-info-note[data-v-7dad0ef2] {
  margin-bottom: 16px;
}
.share-info-note p[data-v-7dad0ef2] {
  margin: 0;
}
.share-manage-link[data-v-7dad0ef2] {
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 13px;
  color: var(--color-primary-element);
  text-decoration: none;
  padding: 8px 12px;
  border-radius: var(--border-radius);
  transition: background-color 0.1s ease;
}
.share-manage-link[data-v-7dad0ef2]:hover {
  background-color: var(--color-background-hover);
}
.manage-text[data-v-7dad0ef2] {
  flex: 1;
}
.manage-arrow[data-v-7dad0ef2] {
  opacity: 0.5;
}
`, "",{"version":3,"sources":["webpack://./src/components/ShareDialog.vue"],"names":[],"mappings":";AAmJA;EACE,aAAa;AACf;AAEA;EACE,aAAa;EACb,mBAAmB;EACnB,QAAQ;EACR,aAAa;EACb,yCAAyC;EACzC,mCAAmC;EACnC,mBAAmB;AACrB;AAEA;EACE,oCAAoC;AACtC;AAEA;EACE,aAAa;EACb,uBAAuB;EACvB,QAAQ;EACR,kBAAkB;EAClB,+CAA+C;EAC/C,mCAAmC;EACnC,mBAAmB;EACnB,eAAe;AACjB;AAEA;EACE,gBAAgB;EAChB,eAAe;EACf,6BAA6B;AAC/B;AAEA;EACE,eAAe;EACf,oCAAoC;EACpC,WAAW;EACX,kBAAkB;AACpB;AAEA;EACE,mBAAmB;AACrB;AAEA;EACE,mBAAmB;AACrB;AAEA;EACE,eAAe;EACf,gBAAgB;EAChB,kBAAkB;EAClB,oCAAoC;AACtC;AAEA;EACE,SAAS;EACT,eAAe;EACf,gBAAgB;EAChB,eAAe;AACjB;AAEA;EACE,aAAa;EACb,6BAA6B;AAC/B;AAEA;EACE,SAAS;EACT,kBAAkB;EAClB,gBAAgB;AAClB;AAEA;EACE,aAAa;EACb,oCAAoC;EACpC,kBAAkB;EAClB,kBAAkB;AACpB;AAEA;EACE,WAAW;EACX,kBAAkB;EAClB,OAAO;EACP,MAAM;EACN,WAAW;EACX,UAAU;EACV,+CAA+C;EAC/C,iDAAiD;AACnD;AAEA;EACE,wBAAwB;AAC1B;AAEA;EACE,mBAAmB;AACrB;AAEA;EACE,SAAS;AACX;AAEA;EACE,aAAa;EACb,mBAAmB;EACnB,QAAQ;EACR,eAAe;EACf,mCAAmC;EACnC,qBAAqB;EACrB,iBAAiB;EACjB,mCAAmC;EACnC,sCAAsC;AACxC;AAEA;EACE,+CAA+C;AACjD;AAEA;EACE,OAAO;AACT;AAEA;EACE,YAAY;AACd","sourcesContent":["<template>\n  <NcDialog :name=\"t('Public Link')\"\n            :can-close=\"true\"\n            @close=\"$emit('close')\">\n    <div class=\"share-dialog\">\n      <!-- Scope indicator -->\n      <div class=\"share-scope\">\n        <FolderOutline v-if=\"shareInfo.scope === 'folder'\" :size=\"20\" />\n        <FileDocumentOutline v-else :size=\"20\" />\n        <span class=\"scope-label\">\n          <template v-if=\"shareInfo.scope === 'page'\">\n            {{ t('This link shares only this page') }}\n          </template>\n          <template v-else-if=\"shareInfo.isLanguageRoot\">\n            {{ t('This link shares all {language} pages', { language: shareInfo.scopeLabel }) }}\n          </template>\n          <template v-else>\n            {{ t('This link shares the \"{section}\" section', { section: shareInfo.scopeLabel || shareInfo.scopeName }) }}\n          </template>\n        </span>\n      </div>\n\n      <!-- Password protected indicator -->\n      <div v-if=\"shareInfo.hasPassword\" class=\"share-password-badge\">\n        <LockOutline :size=\"16\" />\n        <span class=\"password-label\">{{ t('Password protected') }}</span>\n        <span class=\"password-hint\">{{ t('Visitors must enter a password to access this link. Manage in Files.') }}</span>\n      </div>\n\n      <!-- Copy link button -->\n      <div class=\"share-copy-row\">\n        <NcButton type=\"secondary\"\n                  wide\n                  @click=\"copyUrl\">\n          <template #icon>\n            <ContentCopy :size=\"20\" />\n          </template>\n          {{ t('Copy public link') }}\n        </NcButton>\n      </div>\n\n      <!-- Navigation tree (for folder shares) -->\n      <div v-if=\"shareInfo.scope === 'folder' && shareInfo.navigation?.length > 0\" class=\"share-navigation\">\n        <h4>{{ t('Includes these pages:') }}</h4>\n        <ul class=\"nav-tree\">\n          <li v-for=\"item in shareInfo.navigation\" :key=\"item.title\" class=\"nav-tree-item\">\n            {{ item.title }}\n            <ul v-if=\"item.children?.length > 0\" class=\"nav-tree-children\">\n              <li v-for=\"child in item.children\" :key=\"child.title\" class=\"nav-tree-child\">\n                {{ child.title }}\n              </li>\n            </ul>\n          </li>\n        </ul>\n      </div>\n\n      <!-- Read-only warning -->\n      <NcNoteCard type=\"info\" class=\"share-info-note\">\n        <p>{{ t('Visitors can only VIEW this content. Editing via this link is not possible, even if the Files share allows it.') }}</p>\n      </NcNoteCard>\n\n      <!-- Manage link - clickable -->\n      <a :href=\"filesUrl\" target=\"_blank\" class=\"share-manage-link\">\n        <FolderOutline :size=\"16\" />\n        <span class=\"manage-text\">\n          {{ t('Manage share in Files') }}\n        </span>\n        <OpenInNew :size=\"14\" class=\"manage-arrow\" />\n      </a>\n    </div>\n  </NcDialog>\n</template>\n\n<script>\nimport { translate as t } from '@nextcloud/l10n';\nimport { generateUrl } from '@nextcloud/router';\nimport { showSuccess, showError } from '@nextcloud/dialogs';\nimport { NcDialog, NcButton, NcNoteCard } from '@nextcloud/vue';\nimport ContentCopy from 'vue-material-design-icons/ContentCopy.vue';\nimport FolderOutline from 'vue-material-design-icons/FolderOutline.vue';\nimport FileDocumentOutline from 'vue-material-design-icons/FileDocumentOutline.vue';\nimport LockOutline from 'vue-material-design-icons/LockOutline.vue';\nimport OpenInNew from 'vue-material-design-icons/OpenInNew.vue';\n\nexport default {\n  name: 'ShareDialog',\n  components: {\n    NcDialog,\n    NcButton,\n    NcNoteCard,\n    ContentCopy,\n    FolderOutline,\n    FileDocumentOutline,\n    LockOutline,\n    OpenInNew\n  },\n  props: {\n    shareInfo: {\n      type: Object,\n      required: true\n    },\n    pageTitle: {\n      type: String,\n      default: ''\n    }\n  },\n  emits: ['close'],\n  computed: {\n    publicUrl() {\n      const baseUrl = window.location.origin;\n      return baseUrl + this.shareInfo.publicUrl;\n    },\n    filesUrl() {\n      // Split path and query, use generateUrl for the path part only\n      const url = this.shareInfo.filesUrl || '';\n      const [path, query] = url.split('?');\n      const base = generateUrl(path);\n      return query ? base + '?' + query : base;\n    }\n  },\n  methods: {\n    t(key, vars = {}) {\n      return t('intravox', key, vars);\n    },\n    async copyUrl() {\n      try {\n        await navigator.clipboard.writeText(this.publicUrl);\n        showSuccess(this.t('Link copied to clipboard'));\n      } catch (err) {\n        const textArea = document.createElement('textarea');\n        textArea.value = this.publicUrl;\n        document.body.appendChild(textArea);\n        textArea.select();\n        try {\n          document.execCommand('copy');\n          showSuccess(this.t('Link copied to clipboard'));\n        } catch (e) {\n          showError(this.t('Failed to copy link'));\n        }\n        document.body.removeChild(textArea);\n      }\n    }\n  }\n};\n</script>\n\n<style scoped>\n.share-dialog {\n  padding: 16px;\n}\n\n.share-scope {\n  display: flex;\n  align-items: center;\n  gap: 8px;\n  padding: 12px;\n  background: var(--color-background-hover);\n  border-radius: var(--border-radius);\n  margin-bottom: 16px;\n}\n\n.scope-label {\n  color: var(--color-text-maxcontrast);\n}\n\n.share-password-badge {\n  display: flex;\n  align-items: flex-start;\n  gap: 8px;\n  padding: 10px 12px;\n  background: var(--color-warning-hover, #fff3cd);\n  border-radius: var(--border-radius);\n  margin-bottom: 16px;\n  flex-wrap: wrap;\n}\n\n.share-password-badge .password-label {\n  font-weight: 600;\n  font-size: 13px;\n  color: var(--color-main-text);\n}\n\n.share-password-badge .password-hint {\n  font-size: 12px;\n  color: var(--color-text-maxcontrast);\n  width: 100%;\n  padding-left: 24px;\n}\n\n.share-copy-row {\n  margin-bottom: 16px;\n}\n\n.share-navigation {\n  margin-bottom: 16px;\n}\n\n.share-navigation h4 {\n  font-size: 14px;\n  font-weight: 600;\n  margin-bottom: 8px;\n  color: var(--color-text-maxcontrast);\n}\n\n.nav-tree {\n  margin: 0;\n  padding-left: 0;\n  list-style: none;\n  font-size: 13px;\n}\n\n.nav-tree-item {\n  margin: 2px 0;\n  color: var(--color-main-text);\n}\n\n.nav-tree-children {\n  margin: 0;\n  padding-left: 20px;\n  list-style: none;\n}\n\n.nav-tree-child {\n  margin: 2px 0;\n  color: var(--color-text-maxcontrast);\n  position: relative;\n  padding-left: 14px;\n}\n\n.nav-tree-child::before {\n  content: '';\n  position: absolute;\n  left: 0;\n  top: 0;\n  bottom: 50%;\n  width: 8px;\n  border-left: 1px solid var(--color-border-dark);\n  border-bottom: 1px solid var(--color-border-dark);\n}\n\n.nav-tree-child:last-child::before {\n  border-left-style: solid;\n}\n\n.share-info-note {\n  margin-bottom: 16px;\n}\n\n.share-info-note p {\n  margin: 0;\n}\n\n.share-manage-link {\n  display: flex;\n  align-items: center;\n  gap: 8px;\n  font-size: 13px;\n  color: var(--color-primary-element);\n  text-decoration: none;\n  padding: 8px 12px;\n  border-radius: var(--border-radius);\n  transition: background-color 0.1s ease;\n}\n\n.share-manage-link:hover {\n  background-color: var(--color-background-hover);\n}\n\n.manage-text {\n  flex: 1;\n}\n\n.manage-arrow {\n  opacity: 0.5;\n}\n</style>\n"],"sourceRoot":""}]);
// Exports
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (___CSS_LOADER_EXPORT___);


/***/ },

/***/ "./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/dist/stylePostLoader.js!./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/Widget.vue?vue&type=style&index=0&id=4ca18318&scoped=true&lang=css"
/*!*******************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/dist/stylePostLoader.js!./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/Widget.vue?vue&type=style&index=0&id=4ca18318&scoped=true&lang=css ***!
  \*******************************************************************************************************************************************************************************************************************************************************/
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
.widget[data-v-4ca18318] {
  width: 100%;
  max-width: 100%;
  box-sizing: border-box;
}

/* Text Widget */
.widget-text[data-v-4ca18318] {
  width: 100%;
  color: inherit;
  line-height: 1.5;
  margin-top: 0;
  margin-bottom: 12px;
  /* Geen overflow-x: auto hier — een tabel die buiten de widget loopt is een
     bug die we willen oplossen, niet wegscrollen. Wide tabellen krijgen wel
     hun eigen scroll via .tableWrapper. */
}
.widget-text[data-v-4ca18318] p {
  margin: 0 0 0.5em 0;
  color: inherit !important;
}
.widget-text[data-v-4ca18318] p:last-child {
  margin-bottom: 0;
}
.widget-text[data-v-4ca18318] ul,
.widget-text[data-v-4ca18318] ol {
  padding-left: 1.5em;
  margin: 0.5em 0;
  list-style-position: outside;
  color: inherit !important;
}
.widget-text[data-v-4ca18318] ul {
  list-style-type: disc;
}
.widget-text[data-v-4ca18318] ol {
  list-style-type: decimal;
}

/* Nested lists - proper indentation and numbering */
.widget-text[data-v-4ca18318] li > ul,
.widget-text[data-v-4ca18318] li > ol {
  margin: 0.25em 0;
  padding-left: 1.5em;
}
.widget-text[data-v-4ca18318] ol ol {
  list-style-type: lower-alpha;
}
.widget-text[data-v-4ca18318] ol ol ol {
  list-style-type: lower-roman;
}
.widget-text[data-v-4ca18318] ul ul {
  list-style-type: circle;
}
.widget-text[data-v-4ca18318] ul ul ul {
  list-style-type: square;
}
.widget-text[data-v-4ca18318] li {
  margin: 0.25em 0;
  color: inherit !important;
  display: list-item;
}
.widget-text[data-v-4ca18318] strong {
  font-weight: bold;
  color: inherit !important;
}
.widget-text[data-v-4ca18318] em {
  font-style: italic;
  color: inherit !important;
}
.widget-text[data-v-4ca18318] u {
  text-decoration: underline;
  color: inherit !important;
}
.widget-text[data-v-4ca18318] s {
  text-decoration: line-through;
  color: inherit !important;
}

/* Links - use dynamic CSS variables for contrast on all backgrounds */
.widget-text[data-v-4ca18318] a {
  color: var(--widget-link-color, var(--color-primary-element));
  text-decoration: underline;
}
.widget-text[data-v-4ca18318] a:hover {
  color: var(--widget-link-hover-color, var(--color-primary-element-hover));
}

/* Text selection - use dynamic CSS variables for contrast */
.widget-text[data-v-4ca18318] ::selection {
  background: var(--widget-selection-bg, var(--color-primary-element-light));
  color: var(--widget-selection-text, var(--color-main-text));
}
.widget-text[data-v-4ca18318] ::-moz-selection {
  background: var(--widget-selection-bg, var(--color-primary-element-light));
  color: var(--widget-selection-text, var(--color-main-text));
}
.widget-text[data-v-4ca18318] h1,
.widget-text[data-v-4ca18318] h2,
.widget-text[data-v-4ca18318] h3,
.widget-text[data-v-4ca18318] h4,
.widget-text[data-v-4ca18318] h5,
.widget-text[data-v-4ca18318] h6 {
  margin: 0.5em 0 !important;
  font-weight: 600 !important;
  color: inherit !important;
}
.widget-text[data-v-4ca18318] h1 { font-size: 32px !important;
}
.widget-text[data-v-4ca18318] h2 { font-size: 28px !important;
}
.widget-text[data-v-4ca18318] h3 { font-size: 24px !important;
}
.widget-text[data-v-4ca18318] h4 { font-size: 20px !important;
}
.widget-text[data-v-4ca18318] h5 { font-size: 18px !important;
}
.widget-text[data-v-4ca18318] h6 { font-size: 16px !important;
}

/* Text alignment */
.widget-text[data-v-4ca18318] .text-align-center { text-align: center;
}
.widget-text[data-v-4ca18318] .text-align-right { text-align: right;
}

/* Blockquote */
.widget-text[data-v-4ca18318] blockquote {
  border-left: 4px solid var(--color-primary-element);
  padding-left: 1em;
  margin: 1em 0;
  color: inherit !important;
  font-style: italic;
}

/* Heading Widget */
.widget-heading[data-v-4ca18318] {
  margin: 16px 0 16px 0;
  color: inherit;
  font-weight: 600;
}
.widget-heading h1[data-v-4ca18318] { font-size: 32px;
}
.widget-heading h2[data-v-4ca18318] { font-size: 28px;
}
.widget-heading h3[data-v-4ca18318] { font-size: 24px;
}
.widget-heading h4[data-v-4ca18318] { font-size: 20px;
}
.widget-heading h5[data-v-4ca18318] { font-size: 18px;
}
.widget-heading h6[data-v-4ca18318] { font-size: 16px;
}

/* Image Widget */
.widget-image[data-v-4ca18318] {
  width: 100%;
  max-width: 100%;
  box-sizing: border-box;
  margin: 12px 0;
}
.widget-image img[data-v-4ca18318] {
  max-width: 100%;
  width: 100%;
  height: auto;
  border-radius: var(--border-radius-container-large);
  display: block;
  object-fit: contain;
}
.widget-image .placeholder[data-v-4ca18318] {
  padding: 40px;
  background: var(--color-background-dark);
  border: 2px dashed var(--color-border);
  border-radius: var(--border-radius-large);
  text-align: center;
  color: var(--color-text-maxcontrast);
}

/* Clickable image link */
.widget-image .image-link[data-v-4ca18318] {
  display: block;
  text-decoration: none;
  border-radius: var(--border-radius-container-large);
  transition: transform 0.2s ease, box-shadow 0.2s ease;
}
.widget-image .image-link[data-v-4ca18318]:hover {
  transform: scale(1.01);
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
}
.widget-image .image-link[data-v-4ca18318]:focus {
  outline: 2px solid var(--color-primary);
  outline-offset: 2px;
}
.widget-image .image-link img[data-v-4ca18318] {
  cursor: pointer;
}

/* Divider Widget */
.widget-divider[data-v-4ca18318] {
  width: 100%;
  height: 2px;
  margin: 16px 0;
  border: none;
}

/* Video Widget */
.widget-video[data-v-4ca18318] {
  width: 100%;
  margin: 12px 0;
}
.video-container[data-v-4ca18318] {
  position: relative;
  width: 100%;
  padding-bottom: 56.25%; /* 16:9 aspect ratio */
  background: var(--color-background-dark);
  border-radius: var(--border-radius-container-large);
  overflow: hidden;
}
.video-container iframe[data-v-4ca18318],
.video-container video[data-v-4ca18318] {
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  border: none;
}
.video-local video[data-v-4ca18318] {
  object-fit: contain;
}
.video-title[data-v-4ca18318] {
  margin-top: 8px;
  font-size: 14px;
  text-align: center;
  opacity: 0.8;
  color: inherit;
}

/* Video embed wrapper */
.video-embed-wrapper[data-v-4ca18318] {
  width: 100%;
}

/* Video error overlay for local videos */
.video-error-overlay[data-v-4ca18318] {
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 20px;
  box-sizing: border-box;
  background: rgba(0, 0, 0, 0.85);
  z-index: 10;
  text-align: center;
}
.error-message[data-v-4ca18318] {
  font-size: 16px;
  font-weight: 600;
  color: #fff;
  margin: 0 0 8px 0;
}
.error-reason[data-v-4ca18318] {
  font-size: 13px;
  color: rgba(255, 255, 255, 0.8);
  margin: 0;
  max-width: 400px;
}
.widget-video .placeholder[data-v-4ca18318] {
  padding: 40px;
  background: var(--color-background-dark);
  border: 2px dashed var(--color-border);
  border-radius: var(--border-radius-large);
  text-align: center;
  color: var(--color-text-maxcontrast);
}

/* Blocked video message - matches WidgetEditor preview styling */
.video-blocked[data-v-4ca18318] {
  padding: 24px;
  background: var(--color-background-hover);
  border: 2px dashed var(--color-warning);
  border-radius: var(--border-radius-large);
  text-align: center;
}
.video-blocked-icon[data-v-4ca18318] {
  font-size: 48px;
  line-height: 1;
  margin-bottom: 12px;
  filter: grayscale(0);
}
.video-blocked-title[data-v-4ca18318] {
  font-size: 16px;
  font-weight: 600;
  color: var(--color-warning-text);
  background-color: var(--color-warning);
  display: inline-block;
  padding: 4px 12px;
  border-radius: var(--border-radius);
  margin: 0 0 12px 0;
}
.video-blocked-message[data-v-4ca18318] {
  font-size: 14px;
  color: var(--color-main-text);
  margin: 0 0 4px 0;
  max-width: 400px;
  margin-left: auto;
  margin-right: auto;
}
.video-blocked-url[data-v-4ca18318] {
  margin: 12px 0;
  padding: 8px 12px;
  background: var(--color-background-dark);
  border-radius: var(--border-radius);
  max-width: 100%;
  overflow: hidden;
}
.video-blocked-url .url-label[data-v-4ca18318] {
  font-size: 12px;
  font-weight: 600;
  color: var(--color-text-maxcontrast);
  margin-right: 8px;
}
.video-blocked-url .url-value[data-v-4ca18318] {
  font-size: 12px;
  font-family: var(--font-monospace, monospace);
  color: var(--color-main-text);
  word-break: break-all;
  background: none;
  padding: 0;
}
.video-blocked-hint[data-v-4ca18318] {
  font-size: 13px;
  color: var(--color-text-maxcontrast);
  margin: 0;
  font-style: italic;
}

/* All widget-text tables: fixed layout pins each column to its share of the
   table width so a long token in one cell can never push other columns out.
   max-width is a belt-and-braces guarantee that the table cannot grow past
   its widget container, regardless of any nested min-content child. */
.widget-text[data-v-4ca18318] table {
  table-layout: fixed;
  width: 100%;
  max-width: 100%;
  border-collapse: collapse;
  margin: 1em 0;
  background: transparent;
  color: inherit;
}

/* Horizontal scroll wrapper added by hydrateTables in markdownSerializer.js.
   Same class name as TipTap's edit-mode wrapper for consistency. */
.widget-text[data-v-4ca18318] .tableWrapper {
  overflow-x: auto;
  margin: 1em 0;
}
.widget-text[data-v-4ca18318] .tableWrapper > table {
  margin: 0;
}
.widget-text[data-v-4ca18318] th,
.widget-text[data-v-4ca18318] td {
  border: 1px solid var(--color-border-dark, #bbb);
  padding: 8px 12px;
  text-align: left;
  vertical-align: top;
  box-sizing: border-box;
  color: inherit;
  background: transparent;
  /* min-width: 0 lets fixed-layout cells shrink to their column share;
     white-space: normal overrides a Nextcloud core nowrap rule. */
  overflow-wrap: anywhere;
  min-width: 0;
  hyphens: auto;
  white-space: normal;
}

/* Pin cell children to the column width so wrap actually engages. */
.widget-text[data-v-4ca18318] td > *,
.widget-text[data-v-4ca18318] th > * {
  overflow-wrap: anywhere;
  min-width: 0;
  max-width: 100%;
  white-space: normal;
}

/* Geen hover effect op rijen */
.widget-text[data-v-4ca18318] tr,
.widget-text[data-v-4ca18318] tr:hover {
  background: transparent !important;
}

/* Details/Collapsible Section in text widgets - inherits colors from widget */
.widget-text[data-v-4ca18318] details,
.widget-text[data-v-4ca18318] details.intravox-details {
  border: 1px solid currentColor;
  border-radius: var(--border-radius, 4px);
  margin: 1em 0;
  background: transparent;
  color: inherit;
  opacity: 0.9;
}
.widget-text[data-v-4ca18318] details summary {
  cursor: pointer;
  padding: 0.75em 1em 0.75em 2.5em;
  background: rgba(128, 128, 128, 0.15);
  font-weight: 700;
  user-select: none;
  display: block;
  list-style: none;
  color: inherit;
  position: relative;
  min-height: 1.5em;
  line-height: 1.5;
  border-radius: var(--border-radius, 4px) var(--border-radius, 4px) 0 0;
}
.widget-text[data-v-4ca18318] details:not([open]) summary {
  border-radius: var(--border-radius, 4px);
}
.widget-text[data-v-4ca18318] details summary::before {
  content: '▸';
  font-size: 1em;
  position: absolute;
  left: 0.75em;
  top: 50%;
  transform: translateY(-50%);
}
.widget-text[data-v-4ca18318] details[open] summary::before {
  content: '▾';
}
.widget-text[data-v-4ca18318] details > *:not(summary) {
  padding: 0.75em 1em;
  color: inherit;
}

/* Table inside details - contained within the padded content area */
.widget-text[data-v-4ca18318] details > table {
  margin: 0.75em 1em;
  width: calc(100% - 2em);
  border-collapse: collapse;
}
.widget-text[data-v-4ca18318] details > *:not(summary) table {
  margin: 0;
  width: 100%;
  border-collapse: collapse;
}
.widget-text[data-v-4ca18318] details th,
.widget-text[data-v-4ca18318] details td {
  border: 1px solid var(--color-border-dark, #bbb);
  padding: 8px 12px;
  text-align: left;
  vertical-align: top;
  box-sizing: border-box;
  color: inherit;
  background: transparent;
  overflow-wrap: break-word;
  word-wrap: break-word;
}
.widget-text[data-v-4ca18318] details p:first-child,
.widget-text[data-v-4ca18318] details > *:not(summary) p:first-child {
  margin-top: 0;
}
.widget-text[data-v-4ca18318] details p:last-child,
.widget-text[data-v-4ca18318] details > *:not(summary) p:last-child {
  margin-bottom: 0;
}

/* Lists inside details - proper styling */
.widget-text[data-v-4ca18318] details ul,
.widget-text[data-v-4ca18318] details ol {
  padding-left: 1.5em;
  margin: 0.5em 0;
  list-style-position: outside;
}
.widget-text[data-v-4ca18318] details ul {
  list-style-type: disc;
}
.widget-text[data-v-4ca18318] details ol {
  list-style-type: decimal;
}
.widget-text[data-v-4ca18318] details li {
  display: list-item;
  margin: 0.25em 0;
}

/* Nested lists inside details */
.widget-text[data-v-4ca18318] details li > ul,
.widget-text[data-v-4ca18318] details li > ol {
  margin: 0.25em 0;
  padding-left: 1.5em;
}
.widget-text[data-v-4ca18318] details ol ol {
  list-style-type: lower-alpha;
}
.widget-text[data-v-4ca18318] details ol ol ol {
  list-style-type: lower-roman;
}
.widget-text[data-v-4ca18318] details ol ol ol ol {
  list-style-type: decimal;
}
.widget-text[data-v-4ca18318] details ul ul {
  list-style-type: circle;
}
.widget-text[data-v-4ca18318] details ul ul ul {
  list-style-type: square;
}
.widget-text[data-v-4ca18318] details ul ul ul ul {
  list-style-type: disc;
}

/* Remove default marker */
.widget-text[data-v-4ca18318] details summary::-webkit-details-marker {
  display: none;
}
.widget-text[data-v-4ca18318] details summary::marker {
  display: none;
}

/* Unknown Widget */
.widget-unknown[data-v-4ca18318] {
  padding: 20px;
  background: var(--color-error);
  color: white;
  border-radius: var(--border-radius-large);
  text-align: center;
}

/* Mobile styles */
@media (max-width: 768px) {
.widget-text[data-v-4ca18318] h1 { font-size: 24px !important;
}
.widget-text[data-v-4ca18318] h2 { font-size: 22px !important;
}
.widget-text[data-v-4ca18318] h3 { font-size: 20px !important;
}
.widget-text[data-v-4ca18318] h4 { font-size: 18px !important;
}
.widget-text[data-v-4ca18318] h5 { font-size: 16px !important;
}
.widget-text[data-v-4ca18318] h6 { font-size: 14px !important;
}
.widget-heading h1[data-v-4ca18318] { font-size: 24px;
}
.widget-heading h2[data-v-4ca18318] { font-size: 22px;
}
.widget-heading h3[data-v-4ca18318] { font-size: 20px;
}
.widget-heading h4[data-v-4ca18318] { font-size: 18px;
}
.widget-heading h5[data-v-4ca18318] { font-size: 16px;
}
.widget-heading h6[data-v-4ca18318] { font-size: 14px;
}
.widget-text[data-v-4ca18318] {
    font-size: 14px;
}
.widget-image img[data-v-4ca18318] {
    max-width: 100%;
    width: 100%;
    height: auto;
    object-fit: contain;
}
}
`, "",{"version":3,"sources":["webpack://./src/components/Widget.vue"],"names":[],"mappings":";AA6pBA;EACE,WAAW;EACX,eAAe;EACf,sBAAsB;AACxB;;AAEA,gBAAgB;AAChB;EACE,WAAW;EACX,cAAc;EACd,gBAAgB;EAChB,aAAa;EACb,mBAAmB;EACnB;;0CAEwC;AAC1C;AAEA;EACE,mBAAmB;EACnB,yBAAyB;AAC3B;AAEA;EACE,gBAAgB;AAClB;AAEA;;EAEE,mBAAmB;EACnB,eAAe;EACf,4BAA4B;EAC5B,yBAAyB;AAC3B;AAEA;EACE,qBAAqB;AACvB;AAEA;EACE,wBAAwB;AAC1B;;AAEA,oDAAoD;AACpD;;EAEE,gBAAgB;EAChB,mBAAmB;AACrB;AAEA;EACE,4BAA4B;AAC9B;AAEA;EACE,4BAA4B;AAC9B;AAEA;EACE,uBAAuB;AACzB;AAEA;EACE,uBAAuB;AACzB;AAEA;EACE,gBAAgB;EAChB,yBAAyB;EACzB,kBAAkB;AACpB;AAEA;EACE,iBAAiB;EACjB,yBAAyB;AAC3B;AAEA;EACE,kBAAkB;EAClB,yBAAyB;AAC3B;AAEA;EACE,0BAA0B;EAC1B,yBAAyB;AAC3B;AAEA;EACE,6BAA6B;EAC7B,yBAAyB;AAC3B;;AAEA,sEAAsE;AACtE;EACE,6DAA6D;EAC7D,0BAA0B;AAC5B;AAEA;EACE,yEAAyE;AAC3E;;AAEA,4DAA4D;AAC5D;EACE,0EAA0E;EAC1E,2DAA2D;AAC7D;AAEA;EACE,0EAA0E;EAC1E,2DAA2D;AAC7D;AAEA;;;;;;EAME,0BAA0B;EAC1B,2BAA2B;EAC3B,yBAAyB;AAC3B;AAEA,mCAAyB,0BAA0B;AAAE;AACrD,mCAAyB,0BAA0B;AAAE;AACrD,mCAAyB,0BAA0B;AAAE;AACrD,mCAAyB,0BAA0B;AAAE;AACrD,mCAAyB,0BAA0B;AAAE;AACrD,mCAAyB,0BAA0B;AAAE;;AAErD,mBAAmB;AACnB,mDAAyC,kBAAkB;AAAE;AAC7D,kDAAwC,iBAAiB;AAAE;;AAE3D,eAAe;AACf;EACE,mDAAmD;EACnD,iBAAiB;EACjB,aAAa;EACb,yBAAyB;EACzB,kBAAkB;AACpB;;AAEA,mBAAmB;AACnB;EACE,qBAAqB;EACrB,cAAc;EACd,gBAAgB;AAClB;AAEA,sCAAqB,eAAe;AAAE;AACtC,sCAAqB,eAAe;AAAE;AACtC,sCAAqB,eAAe;AAAE;AACtC,sCAAqB,eAAe;AAAE;AACtC,sCAAqB,eAAe;AAAE;AACtC,sCAAqB,eAAe;AAAE;;AAEtC,iBAAiB;AACjB;EACE,WAAW;EACX,eAAe;EACf,sBAAsB;EACtB,cAAc;AAChB;AAEA;EACE,eAAe;EACf,WAAW;EACX,YAAY;EACZ,mDAAmD;EACnD,cAAc;EACd,mBAAmB;AACrB;AAEA;EACE,aAAa;EACb,wCAAwC;EACxC,sCAAsC;EACtC,yCAAyC;EACzC,kBAAkB;EAClB,oCAAoC;AACtC;;AAEA,yBAAyB;AACzB;EACE,cAAc;EACd,qBAAqB;EACrB,mDAAmD;EACnD,qDAAqD;AACvD;AAEA;EACE,sBAAsB;EACtB,0CAA0C;AAC5C;AAEA;EACE,uCAAuC;EACvC,mBAAmB;AACrB;AAEA;EACE,eAAe;AACjB;;AAEA,mBAAmB;AACnB;EACE,WAAW;EACX,WAAW;EACX,cAAc;EACd,YAAY;AACd;;AAEA,iBAAiB;AACjB;EACE,WAAW;EACX,cAAc;AAChB;AAEA;EACE,kBAAkB;EAClB,WAAW;EACX,sBAAsB,EAAE,sBAAsB;EAC9C,wCAAwC;EACxC,mDAAmD;EACnD,gBAAgB;AAClB;AAEA;;EAEE,kBAAkB;EAClB,MAAM;EACN,OAAO;EACP,WAAW;EACX,YAAY;EACZ,YAAY;AACd;AAEA;EACE,mBAAmB;AACrB;AAEA;EACE,eAAe;EACf,eAAe;EACf,kBAAkB;EAClB,YAAY;EACZ,cAAc;AAChB;;AAEA,wBAAwB;AACxB;EACE,WAAW;AACb;;AAEA,yCAAyC;AACzC;EACE,kBAAkB;EAClB,MAAM;EACN,OAAO;EACP,WAAW;EACX,YAAY;EACZ,aAAa;EACb,sBAAsB;EACtB,mBAAmB;EACnB,uBAAuB;EACvB,aAAa;EACb,sBAAsB;EACtB,+BAA+B;EAC/B,WAAW;EACX,kBAAkB;AACpB;AAEA;EACE,eAAe;EACf,gBAAgB;EAChB,WAAW;EACX,iBAAiB;AACnB;AAEA;EACE,eAAe;EACf,+BAA+B;EAC/B,SAAS;EACT,gBAAgB;AAClB;AAEA;EACE,aAAa;EACb,wCAAwC;EACxC,sCAAsC;EACtC,yCAAyC;EACzC,kBAAkB;EAClB,oCAAoC;AACtC;;AAEA,iEAAiE;AACjE;EACE,aAAa;EACb,yCAAyC;EACzC,uCAAuC;EACvC,yCAAyC;EACzC,kBAAkB;AACpB;AAEA;EACE,eAAe;EACf,cAAc;EACd,mBAAmB;EACnB,oBAAoB;AACtB;AAEA;EACE,eAAe;EACf,gBAAgB;EAChB,gCAAgC;EAChC,sCAAsC;EACtC,qBAAqB;EACrB,iBAAiB;EACjB,mCAAmC;EACnC,kBAAkB;AACpB;AAEA;EACE,eAAe;EACf,6BAA6B;EAC7B,iBAAiB;EACjB,gBAAgB;EAChB,iBAAiB;EACjB,kBAAkB;AACpB;AAEA;EACE,cAAc;EACd,iBAAiB;EACjB,wCAAwC;EACxC,mCAAmC;EACnC,eAAe;EACf,gBAAgB;AAClB;AAEA;EACE,eAAe;EACf,gBAAgB;EAChB,oCAAoC;EACpC,iBAAiB;AACnB;AAEA;EACE,eAAe;EACf,6CAA6C;EAC7C,6BAA6B;EAC7B,qBAAqB;EACrB,gBAAgB;EAChB,UAAU;AACZ;AAEA;EACE,eAAe;EACf,oCAAoC;EACpC,SAAS;EACT,kBAAkB;AACpB;;AAEA;;;sEAGsE;AACtE;EACE,mBAAmB;EACnB,WAAW;EACX,eAAe;EACf,yBAAyB;EACzB,aAAa;EACb,uBAAuB;EACvB,cAAc;AAChB;;AAEA;mEACmE;AACnE;EACE,gBAAgB;EAChB,aAAa;AACf;AAEA;EACE,SAAS;AACX;AAEA;;EAEE,gDAAgD;EAChD,iBAAiB;EACjB,gBAAgB;EAChB,mBAAmB;EACnB,sBAAsB;EACtB,cAAc;EACd,uBAAuB;EACvB;kEACgE;EAChE,uBAAuB;EACvB,YAAY;EACZ,aAAa;EACb,mBAAmB;AACrB;;AAEA,oEAAoE;AACpE;;EAEE,uBAAuB;EACvB,YAAY;EACZ,eAAe;EACf,mBAAmB;AACrB;;AAEA,+BAA+B;AAC/B;;EAEE,kCAAkC;AACpC;;AAEA,8EAA8E;AAC9E;;EAEE,8BAA8B;EAC9B,wCAAwC;EACxC,aAAa;EACb,uBAAuB;EACvB,cAAc;EACd,YAAY;AACd;AAEA;EACE,eAAe;EACf,gCAAgC;EAChC,qCAAqC;EACrC,gBAAgB;EAChB,iBAAiB;EACjB,cAAc;EACd,gBAAgB;EAChB,cAAc;EACd,kBAAkB;EAClB,iBAAiB;EACjB,gBAAgB;EAChB,sEAAsE;AACxE;AAEA;EACE,wCAAwC;AAC1C;AAEA;EACE,YAAY;EACZ,cAAc;EACd,kBAAkB;EAClB,YAAY;EACZ,QAAQ;EACR,2BAA2B;AAC7B;AAEA;EACE,YAAY;AACd;AAEA;EACE,mBAAmB;EACnB,cAAc;AAChB;;AAEA,oEAAoE;AACpE;EACE,kBAAkB;EAClB,uBAAuB;EACvB,yBAAyB;AAC3B;AAEA;EACE,SAAS;EACT,WAAW;EACX,yBAAyB;AAC3B;AAEA;;EAEE,gDAAgD;EAChD,iBAAiB;EACjB,gBAAgB;EAChB,mBAAmB;EACnB,sBAAsB;EACtB,cAAc;EACd,uBAAuB;EACvB,yBAAyB;EACzB,qBAAqB;AACvB;AAEA;;EAEE,aAAa;AACf;AAEA;;EAEE,gBAAgB;AAClB;;AAEA,0CAA0C;AAC1C;;EAEE,mBAAmB;EACnB,eAAe;EACf,4BAA4B;AAC9B;AAEA;EACE,qBAAqB;AACvB;AAEA;EACE,wBAAwB;AAC1B;AAEA;EACE,kBAAkB;EAClB,gBAAgB;AAClB;;AAEA,gCAAgC;AAChC;;EAEE,gBAAgB;EAChB,mBAAmB;AACrB;AAEA;EACE,4BAA4B;AAC9B;AACA;EACE,4BAA4B;AAC9B;AACA;EACE,wBAAwB;AAC1B;AAEA;EACE,uBAAuB;AACzB;AACA;EACE,uBAAuB;AACzB;AACA;EACE,qBAAqB;AACvB;;AAEA,0BAA0B;AAC1B;EACE,aAAa;AACf;AAEA;EACE,aAAa;AACf;;AAEA,mBAAmB;AACnB;EACE,aAAa;EACb,8BAA8B;EAC9B,YAAY;EACZ,yCAAyC;EACzC,kBAAkB;AACpB;;AAEA,kBAAkB;AAClB;AACE,mCAAyB,0BAA0B;AAAE;AACrD,mCAAyB,0BAA0B;AAAE;AACrD,mCAAyB,0BAA0B;AAAE;AACrD,mCAAyB,0BAA0B;AAAE;AACrD,mCAAyB,0BAA0B;AAAE;AACrD,mCAAyB,0BAA0B;AAAE;AAErD,sCAAqB,eAAe;AAAE;AACtC,sCAAqB,eAAe;AAAE;AACtC,sCAAqB,eAAe;AAAE;AACtC,sCAAqB,eAAe;AAAE;AACtC,sCAAqB,eAAe;AAAE;AACtC,sCAAqB,eAAe;AAAE;AAEtC;IACE,eAAe;AACjB;AAEA;IACE,eAAe;IACf,WAAW;IACX,YAAY;IACZ,mBAAmB;AACrB;AACF","sourcesContent":["<template>\r\n  <div class=\"widget\" :class=\"`widget-${widget.type}`\" :style=\"textColorStyle\">\r\n    <!-- Text Widget with Inline Editing -->\r\n    <div v-if=\"widget.type === 'text'\" class=\"widget-text\">\r\n      <InlineTextEditor\r\n        v-if=\"editable\"\r\n        v-model=\"localContent\"\r\n        :editable=\"true\"\r\n        :compact=\"isCompactMode\"\r\n        :placeholder=\"t('Enter text...')\"\r\n        @focus=\"$emit('focus')\"\r\n        @blur=\"onBlur\"\r\n      />\r\n      <div v-else v-html=\"sanitizeHtml(widget.content || '')\"></div>\r\n    </div>\r\n\r\n    <!-- Heading Widget -->\r\n    <component\r\n      v-else-if=\"widget.type === 'heading'\"\r\n      :is=\"`h${widget.level}`\"\r\n      class=\"widget-heading\"\r\n      v-html=\"sanitizedContent\"\r\n    ></component>\r\n\r\n    <!-- Image Widget -->\r\n    <div v-else-if=\"widget.type === 'image'\" class=\"widget-image\">\r\n      <!-- Image with link -->\r\n      <a\r\n        v-if=\"widget.src && hasImageLink\"\r\n        :href=\"getImageLinkHref()\"\r\n        :target=\"getImageLinkTarget()\"\r\n        :rel=\"getImageLinkRel()\"\r\n        class=\"image-link\"\r\n        @click=\"handleImageLinkClick\"\r\n      >\r\n        <img\r\n          :src=\"getImageUrl(widget.src)\"\r\n          :alt=\"widget.alt\"\r\n          :style=\"getImageStyle()\"\r\n          loading=\"lazy\"\r\n        />\r\n      </a>\r\n      <!-- Image without link -->\r\n      <img\r\n        v-else-if=\"widget.src\"\r\n        :src=\"getImageUrl(widget.src)\"\r\n        :alt=\"widget.alt\"\r\n        :style=\"getImageStyle()\"\r\n        loading=\"lazy\"\r\n      />\r\n      <div v-else class=\"placeholder\">\r\n        <span>{{ t('No image selected') }}</span>\r\n      </div>\r\n    </div>\r\n\r\n    <!-- Links Widget (Grid of Links) -->\r\n    <LinksWidget\r\n      v-else-if=\"widget.type === 'links'\"\r\n      :widget=\"widget\"\r\n      :row-background-color=\"rowBackgroundColor\"\r\n      @navigate=\"$emit('navigate', $event)\"\r\n    />\r\n\r\n    <!-- Divider Widget -->\r\n    <div\r\n      v-else-if=\"widget.type === 'divider'\"\r\n      class=\"widget-divider\"\r\n      :style=\"getDividerStyle()\"\r\n    ></div>\r\n\r\n    <!-- News Widget -->\r\n    <NewsWidget\r\n      v-else-if=\"widget.type === 'news'\"\r\n      :widget=\"widget\"\r\n      :share-token=\"shareToken\"\r\n      :page-id=\"pageId\"\r\n      :row-background-color=\"rowBackgroundColor\"\r\n      @navigate=\"$emit('navigate', $event)\"\r\n    />\r\n\r\n    <!-- People Widget -->\r\n    <PeopleWidget\r\n      v-else-if=\"widget.type === 'people'\"\r\n      :widget=\"widget\"\r\n      :share-token=\"shareToken\"\r\n      :page-id=\"pageId\"\r\n      :row-background-color=\"rowBackgroundColor\"\r\n    />\r\n\r\n    <!-- Calendar Widget -->\r\n    <CalendarWidget\r\n      v-else-if=\"widget.type === 'calendar'\"\r\n      :widget=\"widget\"\r\n      :share-token=\"shareToken\"\r\n      :page-id=\"pageId\"\r\n      :row-background-color=\"rowBackgroundColor\"\r\n    />\r\n\r\n    <!-- Feed Widget -->\r\n    <FeedWidget\r\n      v-else-if=\"widget.type === 'feed'\"\r\n      :widget=\"widget\"\r\n      :share-token=\"shareToken\"\r\n      :page-id=\"pageId\"\r\n      :row-background-color=\"rowBackgroundColor\"\r\n    />\r\n\r\n    <!-- Photo Story Widget -->\r\n    <PhotoStoryWidget\r\n      v-else-if=\"widget.type === 'photo-story'\"\r\n      :widget=\"widget\"\r\n      :row-background-color=\"rowBackgroundColor\"\r\n    />\r\n\r\n    <!-- File Story Widget -->\r\n    <FileStoryWidget\r\n      v-else-if=\"widget.type === 'file-story'\"\r\n      :widget=\"widget\"\r\n      :row-background-color=\"rowBackgroundColor\"\r\n    />\r\n\r\n    <!-- Video Widget -->\r\n    <div v-else-if=\"widget.type === 'video'\" class=\"widget-video\">\r\n      <!-- Blocked Video - domain not in whitelist -->\r\n      <div v-if=\"widget.blocked\" class=\"video-blocked\">\r\n        <div class=\"video-blocked-icon\">🚫</div>\r\n        <p class=\"video-blocked-title\">{{ t('Video service not allowed') }}</p>\r\n        <p v-if=\"widget.originalSrc\" class=\"video-blocked-url\">\r\n          <span class=\"url-label\">URL:</span>\r\n          <code class=\"url-value\">{{ widget.originalSrc }}</code>\r\n        </p>\r\n        <p v-else-if=\"widget.blockedDomain\" class=\"video-blocked-url\">\r\n          <span class=\"url-label\">{{ t('Domain') }}:</span>\r\n          <code class=\"url-value\">{{ widget.blockedDomain }}</code>\r\n        </p>\r\n        <p class=\"video-blocked-hint\">\r\n          {{ t('Please contact your administrator if you need access to this video service.') }}\r\n        </p>\r\n      </div>\r\n\r\n      <!-- Embed Video (YouTube, Vimeo, PeerTube, etc.) -->\r\n      <div v-else-if=\"widget.provider !== 'local' && widget.src\" class=\"video-embed-wrapper\">\r\n        <div class=\"video-container\">\r\n          <iframe\r\n            :src=\"getEmbedUrl(widget)\"\r\n            :title=\"widget.title || t('Video')\"\r\n            frameborder=\"0\"\r\n            allow=\"autoplay; encrypted-media; picture-in-picture; fullscreen\"\r\n            sandbox=\"allow-scripts allow-same-origin allow-presentation allow-popups\"\r\n            allowfullscreen\r\n            loading=\"lazy\"\r\n            referrerpolicy=\"strict-origin-when-cross-origin\"\r\n            @load=\"onVideoEmbedLoad\"\r\n            @error=\"onVideoEmbedError\"\r\n          ></iframe>\r\n        </div>\r\n      </div>\r\n\r\n      <!-- Local Video File -->\r\n      <div v-else-if=\"widget.provider === 'local' && widget.src\" class=\"video-container video-local\">\r\n        <video\r\n          :src=\"getVideoUrl(widget.src)\"\r\n          :title=\"widget.title\"\r\n          controls\r\n          playsinline\r\n          :autoplay=\"widget.autoplay\"\r\n          :loop=\"widget.loop\"\r\n          :muted=\"widget.muted || widget.autoplay\"\r\n          :preload=\"widget.autoplay ? 'auto' : 'metadata'\"\r\n          @error=\"onLocalVideoError\"\r\n        >\r\n          {{ t('Your browser does not support HTML5 video.') }}\r\n        </video>\r\n        <!-- Error message for local videos -->\r\n        <div v-if=\"localVideoError\" class=\"video-error-overlay\">\r\n          <p class=\"error-message\">{{ t('Video cannot be played') }}</p>\r\n          <p class=\"error-reason\">{{ localVideoError }}</p>\r\n        </div>\r\n      </div>\r\n\r\n      <!-- Placeholder -->\r\n      <div v-else class=\"placeholder\">\r\n        <span>{{ t('No video selected') }}</span>\r\n      </div>\r\n\r\n      <p v-if=\"widget.title && !widget.blocked\" class=\"video-title\">{{ widget.title }}</p>\r\n    </div>\r\n\r\n    <!-- Unknown Widget Type -->\r\n    <div v-else class=\"widget-unknown\">\r\n      {{ t('Unknown widget type: {type}', { type: widget.type }) }}\r\n    </div>\r\n  </div>\r\n</template>\r\n\r\n<script>\r\nimport { defineAsyncComponent } from 'vue';\r\nimport { translate as t } from '@nextcloud/l10n';\r\nimport { generateUrl } from '@nextcloud/router';\r\nimport { markdownToHtml } from '../utils/markdownSerializer.js';\r\n\r\nexport default {\r\n  name: 'Widget',\r\n  components: {\r\n    InlineTextEditor: defineAsyncComponent(() => import('./InlineTextEditor.vue')),\r\n    LinksWidget: defineAsyncComponent(() => import('./LinksWidget.vue')),\r\n    NewsWidget: defineAsyncComponent(() => import('./NewsWidget.vue')),\r\n    PeopleWidget: defineAsyncComponent(() => import('./PeopleWidget.vue')),\r\n    CalendarWidget: defineAsyncComponent(() => import('./CalendarWidget.vue')),\r\n    FeedWidget: defineAsyncComponent(() => import('./FeedWidget.vue')),\r\n    PhotoStoryWidget: defineAsyncComponent(() => import('./PhotoStoryWidget.vue')),\r\n    FileStoryWidget: defineAsyncComponent(() => import('./FileStoryWidget.vue')),\r\n  },\r\n  props: {\r\n    widget: {\r\n      type: Object,\r\n      required: true\r\n    },\r\n    pageId: {\r\n      type: String,\r\n      required: true\r\n    },\r\n    editable: {\r\n      type: Boolean,\r\n      default: false\r\n    },\r\n    rowBackgroundColor: {\r\n      type: String,\r\n      default: ''\r\n    },\r\n    shareToken: {\r\n      type: String,\r\n      default: ''\r\n    }\r\n  },\r\n  emits: ['update', 'focus', 'blur', 'navigate'],\r\n  data() {\r\n    return {\r\n      localContent: this.widget.content || '',\r\n      localVideoError: null,\r\n      isCompactMode: false,\r\n      resizeObserver: null\r\n    };\r\n  },\r\n  mounted() {\r\n    // Auto-detect compact mode based on container width\r\n    this.resizeObserver = new ResizeObserver(entries => {\r\n      const width = entries[0].contentRect.width;\r\n      this.isCompactMode = width < 400;\r\n    });\r\n    this.resizeObserver.observe(this.$el);\r\n  },\r\n  beforeUnmount() {\r\n    if (this.resizeObserver) {\r\n      this.resizeObserver.disconnect();\r\n    }\r\n  },\r\n  computed: {\r\n    sanitizedContent() {\r\n      return this.sanitizeHtml(this.widget.content || '');\r\n    },\r\n    hasImageLink() {\r\n      // Check if image has a valid link configured\r\n      if (this.widget.linkType === 'internal') {\r\n        return !!this.widget.linkPageId;\r\n      }\r\n      if (this.widget.linkType === 'external') {\r\n        return !!this.widget.linkUrl;\r\n      }\r\n      return false;\r\n    },\r\n    textColorStyle() {\r\n      // Map background colors to their matching text/link/selection colors\r\n      // Each background has paired colors for optimal contrast (WCAG 2.1 compliant)\r\n      const bgColor = this.rowBackgroundColor || '';\r\n\r\n      // Define color mappings for text, links, and selection\r\n      const colorMappings = {\r\n        'var(--color-primary-element)': {\r\n          text: 'var(--color-primary-element-text)',\r\n          link: 'var(--color-primary-element-text)', // White links on dark blue\r\n          linkHover: 'rgba(255, 255, 255, 0.8)',\r\n          selection: 'rgba(255, 255, 255, 0.3)',\r\n          selectionText: 'var(--color-primary-element-text)',\r\n          placeholder: 'rgba(255, 255, 255, 0.5)'\r\n        },\r\n        'var(--color-primary-element-light)': {\r\n          text: 'var(--color-primary-element-light-text)',\r\n          link: 'var(--color-primary-element)', // Primary blue links on light blue\r\n          linkHover: 'var(--color-primary-element-hover)',\r\n          selection: 'var(--color-primary-element)',\r\n          selectionText: 'var(--color-primary-element-text)',\r\n          placeholder: 'var(--color-text-maxcontrast)'\r\n        },\r\n        'var(--color-error)': {\r\n          text: 'var(--color-error-text)',\r\n          link: 'var(--color-error-text)',\r\n          linkHover: 'rgba(255, 255, 255, 0.8)',\r\n          selection: 'rgba(255, 255, 255, 0.3)',\r\n          selectionText: 'var(--color-error-text)',\r\n          placeholder: 'rgba(255, 255, 255, 0.5)'\r\n        },\r\n        'var(--color-warning)': {\r\n          text: 'var(--color-warning-text)',\r\n          link: 'var(--color-warning-text)',\r\n          linkHover: 'rgba(0, 0, 0, 0.7)',\r\n          selection: 'rgba(0, 0, 0, 0.2)',\r\n          selectionText: 'var(--color-warning-text)',\r\n          placeholder: 'var(--color-text-maxcontrast)'\r\n        },\r\n        'var(--color-success)': {\r\n          text: 'var(--color-success-text)',\r\n          link: 'var(--color-success-text)',\r\n          linkHover: 'rgba(255, 255, 255, 0.8)',\r\n          selection: 'rgba(255, 255, 255, 0.3)',\r\n          selectionText: 'var(--color-success-text)',\r\n          placeholder: 'rgba(255, 255, 255, 0.5)'\r\n        },\r\n        'var(--color-background-dark)': {\r\n          text: 'var(--color-main-text)',\r\n          link: 'var(--color-primary-element)',\r\n          linkHover: 'var(--color-primary-element-hover)',\r\n          selection: 'var(--color-primary-element-light)',\r\n          selectionText: 'var(--color-main-text)',\r\n          placeholder: 'var(--color-text-maxcontrast)'\r\n        },\r\n        'var(--color-background-hover)': {\r\n          text: 'var(--color-main-text)',\r\n          link: 'var(--color-primary-element)',\r\n          linkHover: 'var(--color-primary-element-hover)',\r\n          selection: 'var(--color-primary-element-light)',\r\n          selectionText: 'var(--color-main-text)',\r\n          placeholder: 'var(--color-text-maxcontrast)'\r\n        }\r\n      };\r\n\r\n      // Default colors for no background or unknown backgrounds\r\n      const defaultColors = {\r\n        text: 'var(--color-main-text)',\r\n        link: 'var(--color-primary-element)',\r\n        linkHover: 'var(--color-primary-element-hover)',\r\n        selection: 'var(--color-primary-element-light)',\r\n        selectionText: 'var(--color-main-text)',\r\n        placeholder: 'var(--color-text-maxcontrast)'\r\n      };\r\n\r\n      const colors = colorMappings[bgColor] || defaultColors;\r\n\r\n      return {\r\n        'color': colors.text,\r\n        '--widget-link-color': colors.link,\r\n        '--widget-link-hover-color': colors.linkHover,\r\n        '--widget-selection-bg': colors.selection,\r\n        '--widget-selection-text': colors.selectionText,\r\n        '--widget-placeholder-color': colors.placeholder\r\n      };\r\n    }\r\n  },\r\n  watch: {\r\n    'widget.content'(newValue) {\r\n      this.localContent = newValue || '';\r\n    },\r\n    localContent(newValue) {\r\n      // Emit update immediately when content changes (not just on blur)\r\n      if (newValue !== this.widget.content) {\r\n        this.$emit('update', {\r\n          ...this.widget,\r\n          content: newValue\r\n        });\r\n      }\r\n    }\r\n  },\r\n  methods: {\r\n    t(key, vars = {}) {\r\n      return t('intravox', key, vars);\r\n    },\r\n    sanitizeHtml(content) {\r\n      if (!content) return '';\r\n      // markdownToHtml handles sanitization and table hydration (colgroup\r\n      // normalization + .tableWrapper for horizontal scroll).\r\n      return markdownToHtml(content);\r\n    },\r\n    onBlur() {\r\n      // No need to save here anymore - watcher handles it\r\n      this.$emit('blur');\r\n    },\r\n    getImageUrl(filename) {\r\n      if (!filename) return '';\r\n\r\n      // Check mediaFolder property (new format)\r\n      if (this.widget.mediaFolder === 'resources') {\r\n        // If we have a share token, use the public endpoint\r\n        if (this.shareToken) {\r\n          return generateUrl(`/apps/intravox/api/share/${this.shareToken}/resources/media/${filename}`);\r\n        }\r\n        return generateUrl(`/apps/intravox/api/resources/media/${filename}`);\r\n      }\r\n\r\n      // Remove legacy prefixes if present\r\n      const cleanFilename = filename.replace(/^(📷 images\\/|images\\/|_media\\/)/, '');\r\n      // If we have a share token, use the public share endpoint\r\n      if (this.shareToken) {\r\n        return generateUrl(`/apps/intravox/api/share/${this.shareToken}/page/${this.pageId}/media/${cleanFilename}`);\r\n      }\r\n      // Media served via unified API (default: page media)\r\n      return generateUrl(`/apps/intravox/api/pages/${this.pageId}/media/${cleanFilename}`);\r\n    },\r\n    getVideoUrl(filename) {\r\n      if (!filename) return '';\r\n\r\n      // Check mediaFolder property (new format)\r\n      if (this.widget.mediaFolder === 'resources') {\r\n        // If we have a share token, use the public endpoint\r\n        if (this.shareToken) {\r\n          return generateUrl(`/apps/intravox/api/share/${this.shareToken}/resources/media/${filename}`);\r\n        }\r\n        return generateUrl(`/apps/intravox/api/resources/media/${filename}`);\r\n      }\r\n\r\n      // Remove legacy prefixes if present\r\n      const cleanFilename = filename.replace(/^(videos\\/|_media\\/)/, '');\r\n      // If we have a share token, use the public share endpoint\r\n      if (this.shareToken) {\r\n        return generateUrl(`/apps/intravox/api/share/${this.shareToken}/page/${this.pageId}/media/${cleanFilename}`);\r\n      }\r\n      // Media served via unified API (default: page media)\r\n      return generateUrl(`/apps/intravox/api/pages/${this.pageId}/media/${cleanFilename}`);\r\n    },\r\n    getImageStyle() {\r\n      const style = {};\r\n\r\n      if (this.widget.width) {\r\n        // Custom width - height auto maintains aspect ratio\r\n        style.width = `${this.widget.width}px`;\r\n        style.height = 'auto';\r\n        style.maxWidth = '100%'; // Don't overflow container\r\n      } else {\r\n        // Full width - natural aspect ratio with cropping\r\n        style.width = '100%';\r\n        style.height = 'auto';\r\n        style.maxHeight = '500px';\r\n        style.objectFit = 'cover';\r\n\r\n        // Set vertical position for cropping\r\n        const position = this.widget.objectPosition || 'center';\r\n        style.objectPosition = `center ${position}`;\r\n      }\r\n\r\n      return style;\r\n    },\r\n    getDividerStyle() {\r\n      const style = {};\r\n      const bgColor = this.rowBackgroundColor || '';\r\n\r\n      // Dark backgrounds get light divider, light backgrounds get primary divider\r\n      const darkBackgrounds = [\r\n        'var(--color-primary-element)',\r\n        'var(--color-error)',\r\n        'var(--color-warning)',\r\n        'var(--color-success)',\r\n      ];\r\n\r\n      if (darkBackgrounds.includes(bgColor)) {\r\n        style.background = 'var(--color-primary-element-light)';\r\n      } else {\r\n        style.background = 'var(--color-primary-element)';\r\n      }\r\n\r\n      return style;\r\n    },\r\n    getEmbedUrl(widget) {\r\n      if (!widget.src) return '';\r\n\r\n      let url = widget.src;\r\n\r\n      // Parse URL to add/modify parameters\r\n      try {\r\n        const urlObj = new URL(url);\r\n        const hostname = urlObj.hostname.toLowerCase();\r\n\r\n        // YouTube\r\n        if (hostname.includes('youtube.com') || hostname.includes('youtube-nocookie.com')) {\r\n          if (widget.autoplay) {\r\n            urlObj.searchParams.set('autoplay', '1');\r\n            urlObj.searchParams.set('mute', '1'); // Required for autoplay\r\n          }\r\n          if (widget.loop) {\r\n            urlObj.searchParams.set('loop', '1');\r\n            // YouTube loop requires playlist parameter with video ID\r\n            const videoId = urlObj.pathname.split('/').pop();\r\n            if (videoId) {\r\n              urlObj.searchParams.set('playlist', videoId);\r\n            }\r\n          }\r\n        }\r\n        // Vimeo\r\n        else if (hostname.includes('vimeo.com')) {\r\n          if (widget.autoplay) {\r\n            urlObj.searchParams.set('autoplay', '1');\r\n            urlObj.searchParams.set('muted', '1');\r\n          }\r\n          if (widget.loop) {\r\n            urlObj.searchParams.set('loop', '1');\r\n          }\r\n        }\r\n        // PeerTube\r\n        else if (urlObj.pathname.includes('/videos/embed/')) {\r\n          if (widget.autoplay) {\r\n            urlObj.searchParams.set('autoplay', '1');\r\n            urlObj.searchParams.set('muted', '1');\r\n          }\r\n          if (widget.loop) {\r\n            urlObj.searchParams.set('loop', '1');\r\n          }\r\n        }\r\n        // Dailymotion\r\n        else if (hostname.includes('dailymotion.com')) {\r\n          if (widget.autoplay) {\r\n            urlObj.searchParams.set('autoplay', '1');\r\n            urlObj.searchParams.set('mute', '1');\r\n          }\r\n          // Dailymotion doesn't have a loop parameter in embed\r\n        }\r\n        // Twitch\r\n        else if (hostname.includes('twitch.tv')) {\r\n          if (widget.autoplay) {\r\n            urlObj.searchParams.set('autoplay', 'true');\r\n            urlObj.searchParams.set('muted', 'true');\r\n          }\r\n        }\r\n        // Generic fallback - try common parameter names\r\n        else {\r\n          if (widget.autoplay) {\r\n            urlObj.searchParams.set('autoplay', '1');\r\n          }\r\n          if (widget.loop) {\r\n            urlObj.searchParams.set('loop', '1');\r\n          }\r\n        }\r\n\r\n        return urlObj.toString();\r\n      } catch (e) {\r\n        // Invalid URL, return as-is\r\n        return url;\r\n      }\r\n    },\r\n    getVideoPlatformName(src) {\r\n      if (!src) return 'video platform';\r\n      try {\r\n        const url = new URL(src);\r\n        const hostname = url.hostname.toLowerCase();\r\n\r\n        if (hostname.includes('youtube.com') || hostname.includes('youtube-nocookie.com')) {\r\n          return 'YouTube';\r\n        } else if (hostname.includes('vimeo.com')) {\r\n          return 'Vimeo';\r\n        } else if (hostname.includes('dailymotion.com')) {\r\n          return 'Dailymotion';\r\n        } else if (hostname.includes('twitch.tv')) {\r\n          return 'Twitch';\r\n        } else if (url.pathname.includes('/videos/embed/')) {\r\n          // PeerTube instances - use domain name\r\n          return hostname.replace('www.', '');\r\n        } else {\r\n          return hostname.replace('www.', '');\r\n        }\r\n      } catch (e) {\r\n        return 'video platform';\r\n      }\r\n    },\r\n    getOriginalVideoUrl(src) {\r\n      if (!src) return '#';\r\n      try {\r\n        const url = new URL(src);\r\n        const hostname = url.hostname.toLowerCase();\r\n\r\n        // Convert embed URLs back to watch URLs\r\n        if (hostname.includes('youtube.com') || hostname.includes('youtube-nocookie.com')) {\r\n          // YouTube embed: /embed/VIDEO_ID -> watch?v=VIDEO_ID\r\n          const videoId = url.pathname.replace('/embed/', '');\r\n          return `https://www.youtube.com/watch?v=${videoId}`;\r\n        } else if (hostname.includes('player.vimeo.com')) {\r\n          // Vimeo player: /video/VIDEO_ID -> vimeo.com/VIDEO_ID\r\n          const videoId = url.pathname.replace('/video/', '');\r\n          return `https://vimeo.com/${videoId}`;\r\n        } else if (hostname.includes('dailymotion.com')) {\r\n          // Dailymotion embed: /embed/video/VIDEO_ID -> dailymotion.com/video/VIDEO_ID\r\n          const videoId = url.pathname.replace('/embed/video/', '');\r\n          return `https://www.dailymotion.com/video/${videoId}`;\r\n        } else if (url.pathname.includes('/videos/embed/')) {\r\n          // PeerTube: /videos/embed/VIDEO_ID -> /videos/watch/VIDEO_ID\r\n          return src.replace('/videos/embed/', '/videos/watch/');\r\n        }\r\n        // Return original for unknown platforms\r\n        return src;\r\n      } catch (e) {\r\n        return src || '#';\r\n      }\r\n    },\r\n    onVideoEmbedLoad() {\r\n      // Video iframe loaded successfully - no action needed\r\n    },\r\n    onVideoEmbedError() {\r\n      // iframe errors are limited - silent fail\r\n    },\r\n    onLocalVideoError(event) {\r\n      const video = event.target;\r\n      const error = video.error;\r\n\r\n      if (error) {\r\n        switch (error.code) {\r\n          case MediaError.MEDIA_ERR_ABORTED:\r\n            this.localVideoError = this.t('Video playback was aborted.');\r\n            break;\r\n          case MediaError.MEDIA_ERR_NETWORK:\r\n            this.localVideoError = this.t('A network error occurred while loading the video.');\r\n            break;\r\n          case MediaError.MEDIA_ERR_DECODE:\r\n            this.localVideoError = this.t('The video file is corrupted or uses an unsupported format.');\r\n            break;\r\n          case MediaError.MEDIA_ERR_SRC_NOT_SUPPORTED:\r\n            this.localVideoError = this.t('The video format is not supported by your browser.');\r\n            break;\r\n          default:\r\n            this.localVideoError = this.t('An unknown error occurred while playing the video.');\r\n        }\r\n      }\r\n    },\r\n    getImageLinkHref() {\r\n      // Return the appropriate href for the image link\r\n      if (this.widget.linkType === 'internal' && this.widget.linkPageId) {\r\n        return `#${this.widget.linkPageId}`;\r\n      }\r\n      if (this.widget.linkType === 'external' && this.widget.linkUrl) {\r\n        return this.widget.linkUrl;\r\n      }\r\n      return '#';\r\n    },\r\n    getImageLinkTarget() {\r\n      // Internal links always open in same tab\r\n      if (this.widget.linkType === 'internal') {\r\n        return '_self';\r\n      }\r\n      // External links: check linkNewTab setting (defaults to true for backwards compatibility)\r\n      if (this.widget.linkType === 'external') {\r\n        return this.widget.linkNewTab !== false ? '_blank' : '_self';\r\n      }\r\n      return '_self';\r\n    },\r\n    getImageLinkRel() {\r\n      // Only add noopener noreferrer when opening in new tab\r\n      if (this.widget.linkType === 'external' && this.widget.linkNewTab !== false) {\r\n        return 'noopener noreferrer';\r\n      }\r\n      return '';\r\n    },\r\n    handleImageLinkClick(event) {\r\n      // Handle internal link navigation\r\n      if (this.widget.linkType === 'internal' && this.widget.linkPageId) {\r\n        event.preventDefault();\r\n        this.$emit('navigate', this.widget.linkPageId);\r\n      }\r\n      // External links follow their default behavior based on target attribute\r\n    }\r\n  }\r\n};\r\n</script>\r\n\r\n<style scoped>\r\n.widget {\r\n  width: 100%;\r\n  max-width: 100%;\r\n  box-sizing: border-box;\r\n}\r\n\r\n/* Text Widget */\r\n.widget-text {\r\n  width: 100%;\r\n  color: inherit;\r\n  line-height: 1.5;\r\n  margin-top: 0;\r\n  margin-bottom: 12px;\r\n  /* Geen overflow-x: auto hier — een tabel die buiten de widget loopt is een\r\n     bug die we willen oplossen, niet wegscrollen. Wide tabellen krijgen wel\r\n     hun eigen scroll via .tableWrapper. */\r\n}\r\n\r\n.widget-text :deep(p) {\r\n  margin: 0 0 0.5em 0;\r\n  color: inherit !important;\r\n}\r\n\r\n.widget-text :deep(p:last-child) {\r\n  margin-bottom: 0;\r\n}\r\n\r\n.widget-text :deep(ul),\r\n.widget-text :deep(ol) {\r\n  padding-left: 1.5em;\r\n  margin: 0.5em 0;\r\n  list-style-position: outside;\r\n  color: inherit !important;\r\n}\r\n\r\n.widget-text :deep(ul) {\r\n  list-style-type: disc;\r\n}\r\n\r\n.widget-text :deep(ol) {\r\n  list-style-type: decimal;\r\n}\r\n\r\n/* Nested lists - proper indentation and numbering */\r\n.widget-text :deep(li > ul),\r\n.widget-text :deep(li > ol) {\r\n  margin: 0.25em 0;\r\n  padding-left: 1.5em;\r\n}\r\n\r\n.widget-text :deep(ol ol) {\r\n  list-style-type: lower-alpha;\r\n}\r\n\r\n.widget-text :deep(ol ol ol) {\r\n  list-style-type: lower-roman;\r\n}\r\n\r\n.widget-text :deep(ul ul) {\r\n  list-style-type: circle;\r\n}\r\n\r\n.widget-text :deep(ul ul ul) {\r\n  list-style-type: square;\r\n}\r\n\r\n.widget-text :deep(li) {\r\n  margin: 0.25em 0;\r\n  color: inherit !important;\r\n  display: list-item;\r\n}\r\n\r\n.widget-text :deep(strong) {\r\n  font-weight: bold;\r\n  color: inherit !important;\r\n}\r\n\r\n.widget-text :deep(em) {\r\n  font-style: italic;\r\n  color: inherit !important;\r\n}\r\n\r\n.widget-text :deep(u) {\r\n  text-decoration: underline;\r\n  color: inherit !important;\r\n}\r\n\r\n.widget-text :deep(s) {\r\n  text-decoration: line-through;\r\n  color: inherit !important;\r\n}\r\n\r\n/* Links - use dynamic CSS variables for contrast on all backgrounds */\r\n.widget-text :deep(a) {\r\n  color: var(--widget-link-color, var(--color-primary-element));\r\n  text-decoration: underline;\r\n}\r\n\r\n.widget-text :deep(a:hover) {\r\n  color: var(--widget-link-hover-color, var(--color-primary-element-hover));\r\n}\r\n\r\n/* Text selection - use dynamic CSS variables for contrast */\r\n.widget-text :deep(::selection) {\r\n  background: var(--widget-selection-bg, var(--color-primary-element-light));\r\n  color: var(--widget-selection-text, var(--color-main-text));\r\n}\r\n\r\n.widget-text :deep(::-moz-selection) {\r\n  background: var(--widget-selection-bg, var(--color-primary-element-light));\r\n  color: var(--widget-selection-text, var(--color-main-text));\r\n}\r\n\r\n.widget-text :deep(h1),\r\n.widget-text :deep(h2),\r\n.widget-text :deep(h3),\r\n.widget-text :deep(h4),\r\n.widget-text :deep(h5),\r\n.widget-text :deep(h6) {\r\n  margin: 0.5em 0 !important;\r\n  font-weight: 600 !important;\r\n  color: inherit !important;\r\n}\r\n\r\n.widget-text :deep(h1) { font-size: 32px !important; }\r\n.widget-text :deep(h2) { font-size: 28px !important; }\r\n.widget-text :deep(h3) { font-size: 24px !important; }\r\n.widget-text :deep(h4) { font-size: 20px !important; }\r\n.widget-text :deep(h5) { font-size: 18px !important; }\r\n.widget-text :deep(h6) { font-size: 16px !important; }\r\n\r\n/* Text alignment */\r\n.widget-text :deep(.text-align-center) { text-align: center; }\r\n.widget-text :deep(.text-align-right) { text-align: right; }\r\n\r\n/* Blockquote */\r\n.widget-text :deep(blockquote) {\r\n  border-left: 4px solid var(--color-primary-element);\r\n  padding-left: 1em;\r\n  margin: 1em 0;\r\n  color: inherit !important;\r\n  font-style: italic;\r\n}\r\n\r\n/* Heading Widget */\r\n.widget-heading {\r\n  margin: 16px 0 16px 0;\r\n  color: inherit;\r\n  font-weight: 600;\r\n}\r\n\r\n.widget-heading h1 { font-size: 32px; }\r\n.widget-heading h2 { font-size: 28px; }\r\n.widget-heading h3 { font-size: 24px; }\r\n.widget-heading h4 { font-size: 20px; }\r\n.widget-heading h5 { font-size: 18px; }\r\n.widget-heading h6 { font-size: 16px; }\r\n\r\n/* Image Widget */\r\n.widget-image {\r\n  width: 100%;\r\n  max-width: 100%;\r\n  box-sizing: border-box;\r\n  margin: 12px 0;\r\n}\r\n\r\n.widget-image img {\r\n  max-width: 100%;\r\n  width: 100%;\r\n  height: auto;\r\n  border-radius: var(--border-radius-container-large);\r\n  display: block;\r\n  object-fit: contain;\r\n}\r\n\r\n.widget-image .placeholder {\r\n  padding: 40px;\r\n  background: var(--color-background-dark);\r\n  border: 2px dashed var(--color-border);\r\n  border-radius: var(--border-radius-large);\r\n  text-align: center;\r\n  color: var(--color-text-maxcontrast);\r\n}\r\n\r\n/* Clickable image link */\r\n.widget-image .image-link {\r\n  display: block;\r\n  text-decoration: none;\r\n  border-radius: var(--border-radius-container-large);\r\n  transition: transform 0.2s ease, box-shadow 0.2s ease;\r\n}\r\n\r\n.widget-image .image-link:hover {\r\n  transform: scale(1.01);\r\n  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);\r\n}\r\n\r\n.widget-image .image-link:focus {\r\n  outline: 2px solid var(--color-primary);\r\n  outline-offset: 2px;\r\n}\r\n\r\n.widget-image .image-link img {\r\n  cursor: pointer;\r\n}\r\n\r\n/* Divider Widget */\r\n.widget-divider {\r\n  width: 100%;\r\n  height: 2px;\r\n  margin: 16px 0;\r\n  border: none;\r\n}\r\n\r\n/* Video Widget */\r\n.widget-video {\r\n  width: 100%;\r\n  margin: 12px 0;\r\n}\r\n\r\n.video-container {\r\n  position: relative;\r\n  width: 100%;\r\n  padding-bottom: 56.25%; /* 16:9 aspect ratio */\r\n  background: var(--color-background-dark);\r\n  border-radius: var(--border-radius-container-large);\r\n  overflow: hidden;\r\n}\r\n\r\n.video-container iframe,\r\n.video-container video {\r\n  position: absolute;\r\n  top: 0;\r\n  left: 0;\r\n  width: 100%;\r\n  height: 100%;\r\n  border: none;\r\n}\r\n\r\n.video-local video {\r\n  object-fit: contain;\r\n}\r\n\r\n.video-title {\r\n  margin-top: 8px;\r\n  font-size: 14px;\r\n  text-align: center;\r\n  opacity: 0.8;\r\n  color: inherit;\r\n}\r\n\r\n/* Video embed wrapper */\r\n.video-embed-wrapper {\r\n  width: 100%;\r\n}\r\n\r\n/* Video error overlay for local videos */\r\n.video-error-overlay {\r\n  position: absolute;\r\n  top: 0;\r\n  left: 0;\r\n  width: 100%;\r\n  height: 100%;\r\n  display: flex;\r\n  flex-direction: column;\r\n  align-items: center;\r\n  justify-content: center;\r\n  padding: 20px;\r\n  box-sizing: border-box;\r\n  background: rgba(0, 0, 0, 0.85);\r\n  z-index: 10;\r\n  text-align: center;\r\n}\r\n\r\n.error-message {\r\n  font-size: 16px;\r\n  font-weight: 600;\r\n  color: #fff;\r\n  margin: 0 0 8px 0;\r\n}\r\n\r\n.error-reason {\r\n  font-size: 13px;\r\n  color: rgba(255, 255, 255, 0.8);\r\n  margin: 0;\r\n  max-width: 400px;\r\n}\r\n\r\n.widget-video .placeholder {\r\n  padding: 40px;\r\n  background: var(--color-background-dark);\r\n  border: 2px dashed var(--color-border);\r\n  border-radius: var(--border-radius-large);\r\n  text-align: center;\r\n  color: var(--color-text-maxcontrast);\r\n}\r\n\r\n/* Blocked video message - matches WidgetEditor preview styling */\r\n.video-blocked {\r\n  padding: 24px;\r\n  background: var(--color-background-hover);\r\n  border: 2px dashed var(--color-warning);\r\n  border-radius: var(--border-radius-large);\r\n  text-align: center;\r\n}\r\n\r\n.video-blocked-icon {\r\n  font-size: 48px;\r\n  line-height: 1;\r\n  margin-bottom: 12px;\r\n  filter: grayscale(0);\r\n}\r\n\r\n.video-blocked-title {\r\n  font-size: 16px;\r\n  font-weight: 600;\r\n  color: var(--color-warning-text);\r\n  background-color: var(--color-warning);\r\n  display: inline-block;\r\n  padding: 4px 12px;\r\n  border-radius: var(--border-radius);\r\n  margin: 0 0 12px 0;\r\n}\r\n\r\n.video-blocked-message {\r\n  font-size: 14px;\r\n  color: var(--color-main-text);\r\n  margin: 0 0 4px 0;\r\n  max-width: 400px;\r\n  margin-left: auto;\r\n  margin-right: auto;\r\n}\r\n\r\n.video-blocked-url {\r\n  margin: 12px 0;\r\n  padding: 8px 12px;\r\n  background: var(--color-background-dark);\r\n  border-radius: var(--border-radius);\r\n  max-width: 100%;\r\n  overflow: hidden;\r\n}\r\n\r\n.video-blocked-url .url-label {\r\n  font-size: 12px;\r\n  font-weight: 600;\r\n  color: var(--color-text-maxcontrast);\r\n  margin-right: 8px;\r\n}\r\n\r\n.video-blocked-url .url-value {\r\n  font-size: 12px;\r\n  font-family: var(--font-monospace, monospace);\r\n  color: var(--color-main-text);\r\n  word-break: break-all;\r\n  background: none;\r\n  padding: 0;\r\n}\r\n\r\n.video-blocked-hint {\r\n  font-size: 13px;\r\n  color: var(--color-text-maxcontrast);\r\n  margin: 0;\r\n  font-style: italic;\r\n}\r\n\r\n/* All widget-text tables: fixed layout pins each column to its share of the\r\n   table width so a long token in one cell can never push other columns out.\r\n   max-width is a belt-and-braces guarantee that the table cannot grow past\r\n   its widget container, regardless of any nested min-content child. */\r\n.widget-text :deep(table) {\r\n  table-layout: fixed;\r\n  width: 100%;\r\n  max-width: 100%;\r\n  border-collapse: collapse;\r\n  margin: 1em 0;\r\n  background: transparent;\r\n  color: inherit;\r\n}\r\n\r\n/* Horizontal scroll wrapper added by hydrateTables in markdownSerializer.js.\r\n   Same class name as TipTap's edit-mode wrapper for consistency. */\r\n.widget-text :deep(.tableWrapper) {\r\n  overflow-x: auto;\r\n  margin: 1em 0;\r\n}\r\n\r\n.widget-text :deep(.tableWrapper > table) {\r\n  margin: 0;\r\n}\r\n\r\n.widget-text :deep(th),\r\n.widget-text :deep(td) {\r\n  border: 1px solid var(--color-border-dark, #bbb);\r\n  padding: 8px 12px;\r\n  text-align: left;\r\n  vertical-align: top;\r\n  box-sizing: border-box;\r\n  color: inherit;\r\n  background: transparent;\r\n  /* min-width: 0 lets fixed-layout cells shrink to their column share;\r\n     white-space: normal overrides a Nextcloud core nowrap rule. */\r\n  overflow-wrap: anywhere;\r\n  min-width: 0;\r\n  hyphens: auto;\r\n  white-space: normal;\r\n}\r\n\r\n/* Pin cell children to the column width so wrap actually engages. */\r\n.widget-text :deep(td > *),\r\n.widget-text :deep(th > *) {\r\n  overflow-wrap: anywhere;\r\n  min-width: 0;\r\n  max-width: 100%;\r\n  white-space: normal;\r\n}\r\n\r\n/* Geen hover effect op rijen */\r\n.widget-text :deep(tr),\r\n.widget-text :deep(tr:hover) {\r\n  background: transparent !important;\r\n}\r\n\r\n/* Details/Collapsible Section in text widgets - inherits colors from widget */\r\n.widget-text :deep(details),\r\n.widget-text :deep(details.intravox-details) {\r\n  border: 1px solid currentColor;\r\n  border-radius: var(--border-radius, 4px);\r\n  margin: 1em 0;\r\n  background: transparent;\r\n  color: inherit;\r\n  opacity: 0.9;\r\n}\r\n\r\n.widget-text :deep(details summary) {\r\n  cursor: pointer;\r\n  padding: 0.75em 1em 0.75em 2.5em;\r\n  background: rgba(128, 128, 128, 0.15);\r\n  font-weight: 700;\r\n  user-select: none;\r\n  display: block;\r\n  list-style: none;\r\n  color: inherit;\r\n  position: relative;\r\n  min-height: 1.5em;\r\n  line-height: 1.5;\r\n  border-radius: var(--border-radius, 4px) var(--border-radius, 4px) 0 0;\r\n}\r\n\r\n.widget-text :deep(details:not([open]) summary) {\r\n  border-radius: var(--border-radius, 4px);\r\n}\r\n\r\n.widget-text :deep(details summary::before) {\r\n  content: '▸';\r\n  font-size: 1em;\r\n  position: absolute;\r\n  left: 0.75em;\r\n  top: 50%;\r\n  transform: translateY(-50%);\r\n}\r\n\r\n.widget-text :deep(details[open] summary::before) {\r\n  content: '▾';\r\n}\r\n\r\n.widget-text :deep(details > *:not(summary)) {\r\n  padding: 0.75em 1em;\r\n  color: inherit;\r\n}\r\n\r\n/* Table inside details - contained within the padded content area */\r\n.widget-text :deep(details > table) {\r\n  margin: 0.75em 1em;\r\n  width: calc(100% - 2em);\r\n  border-collapse: collapse;\r\n}\r\n\r\n.widget-text :deep(details > *:not(summary) table) {\r\n  margin: 0;\r\n  width: 100%;\r\n  border-collapse: collapse;\r\n}\r\n\r\n.widget-text :deep(details th),\r\n.widget-text :deep(details td) {\r\n  border: 1px solid var(--color-border-dark, #bbb);\r\n  padding: 8px 12px;\r\n  text-align: left;\r\n  vertical-align: top;\r\n  box-sizing: border-box;\r\n  color: inherit;\r\n  background: transparent;\r\n  overflow-wrap: break-word;\r\n  word-wrap: break-word;\r\n}\r\n\r\n.widget-text :deep(details p:first-child),\r\n.widget-text :deep(details > *:not(summary) p:first-child) {\r\n  margin-top: 0;\r\n}\r\n\r\n.widget-text :deep(details p:last-child),\r\n.widget-text :deep(details > *:not(summary) p:last-child) {\r\n  margin-bottom: 0;\r\n}\r\n\r\n/* Lists inside details - proper styling */\r\n.widget-text :deep(details ul),\r\n.widget-text :deep(details ol) {\r\n  padding-left: 1.5em;\r\n  margin: 0.5em 0;\r\n  list-style-position: outside;\r\n}\r\n\r\n.widget-text :deep(details ul) {\r\n  list-style-type: disc;\r\n}\r\n\r\n.widget-text :deep(details ol) {\r\n  list-style-type: decimal;\r\n}\r\n\r\n.widget-text :deep(details li) {\r\n  display: list-item;\r\n  margin: 0.25em 0;\r\n}\r\n\r\n/* Nested lists inside details */\r\n.widget-text :deep(details li > ul),\r\n.widget-text :deep(details li > ol) {\r\n  margin: 0.25em 0;\r\n  padding-left: 1.5em;\r\n}\r\n\r\n.widget-text :deep(details ol ol) {\r\n  list-style-type: lower-alpha;\r\n}\r\n.widget-text :deep(details ol ol ol) {\r\n  list-style-type: lower-roman;\r\n}\r\n.widget-text :deep(details ol ol ol ol) {\r\n  list-style-type: decimal;\r\n}\r\n\r\n.widget-text :deep(details ul ul) {\r\n  list-style-type: circle;\r\n}\r\n.widget-text :deep(details ul ul ul) {\r\n  list-style-type: square;\r\n}\r\n.widget-text :deep(details ul ul ul ul) {\r\n  list-style-type: disc;\r\n}\r\n\r\n/* Remove default marker */\r\n.widget-text :deep(details summary::-webkit-details-marker) {\r\n  display: none;\r\n}\r\n\r\n.widget-text :deep(details summary::marker) {\r\n  display: none;\r\n}\r\n\r\n/* Unknown Widget */\r\n.widget-unknown {\r\n  padding: 20px;\r\n  background: var(--color-error);\r\n  color: white;\r\n  border-radius: var(--border-radius-large);\r\n  text-align: center;\r\n}\r\n\r\n/* Mobile styles */\r\n@media (max-width: 768px) {\r\n  .widget-text :deep(h1) { font-size: 24px !important; }\r\n  .widget-text :deep(h2) { font-size: 22px !important; }\r\n  .widget-text :deep(h3) { font-size: 20px !important; }\r\n  .widget-text :deep(h4) { font-size: 18px !important; }\r\n  .widget-text :deep(h5) { font-size: 16px !important; }\r\n  .widget-text :deep(h6) { font-size: 14px !important; }\r\n\r\n  .widget-heading h1 { font-size: 24px; }\r\n  .widget-heading h2 { font-size: 22px; }\r\n  .widget-heading h3 { font-size: 20px; }\r\n  .widget-heading h4 { font-size: 18px; }\r\n  .widget-heading h5 { font-size: 16px; }\r\n  .widget-heading h6 { font-size: 14px; }\r\n\r\n  .widget-text {\r\n    font-size: 14px;\r\n  }\r\n\r\n  .widget-image img {\r\n    max-width: 100%;\r\n    width: 100%;\r\n    height: auto;\r\n    object-fit: contain;\r\n  }\r\n}\r\n</style>\r\n"],"sourceRoot":""}]);
// Exports
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (___CSS_LOADER_EXPORT___);


/***/ },

/***/ "./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/dist/stylePostLoader.js!./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/reactions/CommentItem.vue?vue&type=style&index=0&id=6211c46d&scoped=true&lang=css"
/*!**********************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/dist/stylePostLoader.js!./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/reactions/CommentItem.vue?vue&type=style&index=0&id=6211c46d&scoped=true&lang=css ***!
  \**********************************************************************************************************************************************************************************************************************************************************************/
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
.comment-item[data-v-6211c46d] {
	padding: 12px 0;
	border-bottom: 1px solid var(--color-border);
}
.comment-item--reply[data-v-6211c46d] {
	padding: 8px 0;
	border-bottom: none;
	margin-left: 40px;
}
.comment-item__header[data-v-6211c46d] {
	display: flex;
	align-items: center;
	gap: 8px;
	margin-bottom: 8px;
}
.comment-item__meta[data-v-6211c46d] {
	flex: 1;
	display: flex;
	align-items: baseline;
	gap: 8px;
}
.comment-item__author[data-v-6211c46d] {
	font-weight: 600;
	color: var(--color-main-text);
}
.comment-item__time[data-v-6211c46d] {
	font-size: 12px;
	color: var(--color-text-maxcontrast);
}
.comment-item__edited[data-v-6211c46d] {
	font-size: 12px;
	font-style: italic;
	color: var(--color-text-maxcontrast);
}
.comment-item__body[data-v-6211c46d] {
	margin-left: 40px;
}
.comment-item--reply .comment-item__body[data-v-6211c46d] {
	margin-left: 36px;
}
.comment-item__message[data-v-6211c46d] {
	margin: 0;
	white-space: pre-wrap;
	word-break: break-word;
}
.comment-item__edit[data-v-6211c46d] {
	display: flex;
	flex-direction: column;
	gap: 8px;
}
.comment-item__edit-input[data-v-6211c46d] {
	width: 100%;
	padding: 8px;
	border: 1px solid var(--color-border);
	border-radius: var(--border-radius);
	resize: vertical;
	font-family: inherit;
	font-size: inherit;
}
.comment-item__edit-input[data-v-6211c46d]:focus {
	border-color: var(--color-primary-element);
	outline: none;
}
.comment-item__edit-actions[data-v-6211c46d] {
	display: flex;
	justify-content: flex-end;
	gap: 8px;
}
.comment-item__footer[data-v-6211c46d] {
	display: flex;
	align-items: center;
	justify-content: flex-start;
	gap: 8px;
	margin-left: 40px;
	margin-top: 8px;
}
.comment-item--reply .comment-item__footer[data-v-6211c46d] {
	margin-left: 36px;
}
.comment-item__reactions[data-v-6211c46d] {
	display: flex;
	flex-wrap: wrap;
	gap: 4px;
}
.comment-item__reaction[data-v-6211c46d] {
	padding: 2px 8px;
	font-size: 12px;
	border: 1px solid var(--color-border);
	border-radius: 12px;
	background: transparent;
	cursor: pointer;
	transition: all 0.1s ease;
}
.comment-item__reaction[data-v-6211c46d]:hover {
	background: var(--color-background-hover);
}
.comment-item__reaction--active[data-v-6211c46d] {
	background: var(--color-primary-element-light);
	border-color: var(--color-primary-element);
}
.comment-item__buttons[data-v-6211c46d] {
	display: flex;
	gap: 4px;
}
.comment-item__replies[data-v-6211c46d] {
	margin-top: 8px;
}
`, "",{"version":3,"sources":["webpack://./src/components/reactions/CommentItem.vue"],"names":[],"mappings":";AAgQA;CACC,eAAe;CACf,4CAA4C;AAC7C;AAEA;CACC,cAAc;CACd,mBAAmB;CACnB,iBAAiB;AAClB;AAEA;CACC,aAAa;CACb,mBAAmB;CACnB,QAAQ;CACR,kBAAkB;AACnB;AAEA;CACC,OAAO;CACP,aAAa;CACb,qBAAqB;CACrB,QAAQ;AACT;AAEA;CACC,gBAAgB;CAChB,6BAA6B;AAC9B;AAEA;CACC,eAAe;CACf,oCAAoC;AACrC;AAEA;CACC,eAAe;CACf,kBAAkB;CAClB,oCAAoC;AACrC;AAEA;CACC,iBAAiB;AAClB;AAEA;CACC,iBAAiB;AAClB;AAEA;CACC,SAAS;CACT,qBAAqB;CACrB,sBAAsB;AACvB;AAEA;CACC,aAAa;CACb,sBAAsB;CACtB,QAAQ;AACT;AAEA;CACC,WAAW;CACX,YAAY;CACZ,qCAAqC;CACrC,mCAAmC;CACnC,gBAAgB;CAChB,oBAAoB;CACpB,kBAAkB;AACnB;AAEA;CACC,0CAA0C;CAC1C,aAAa;AACd;AAEA;CACC,aAAa;CACb,yBAAyB;CACzB,QAAQ;AACT;AAEA;CACC,aAAa;CACb,mBAAmB;CACnB,2BAA2B;CAC3B,QAAQ;CACR,iBAAiB;CACjB,eAAe;AAChB;AAEA;CACC,iBAAiB;AAClB;AAEA;CACC,aAAa;CACb,eAAe;CACf,QAAQ;AACT;AAEA;CACC,gBAAgB;CAChB,eAAe;CACf,qCAAqC;CACrC,mBAAmB;CACnB,uBAAuB;CACvB,eAAe;CACf,yBAAyB;AAC1B;AAEA;CACC,yCAAyC;AAC1C;AAEA;CACC,8CAA8C;CAC9C,0CAA0C;AAC3C;AAEA;CACC,aAAa;CACb,QAAQ;AACT;AAEA;CACC,eAAe;AAChB","sourcesContent":["<template>\n\t<div class=\"comment-item\" :class=\"{ 'comment-item--reply': isReply }\">\n\t\t<div class=\"comment-item__header\">\n\t\t\t<NcAvatar :user=\"comment.userId\" :display-name=\"comment.displayName\" :size=\"isReply ? 28 : 32\" />\n\t\t\t<div class=\"comment-item__meta\">\n\t\t\t\t<span class=\"comment-item__author\">{{ comment.displayName }}</span>\n\t\t\t\t<span class=\"comment-item__time\" :title=\"formattedDate\">{{ relativeTime }}</span>\n\t\t\t\t<span v-if=\"comment.isEdited\" class=\"comment-item__edited\">({{ t('intravox', 'edited') }})</span>\n\t\t\t</div>\n\t\t\t<div v-if=\"canModify\" class=\"comment-item__actions\">\n\t\t\t\t<NcActions>\n\t\t\t\t\t<NcActionButton @click=\"startEdit\">\n\t\t\t\t\t\t<template #icon>\n\t\t\t\t\t\t\t<Pencil :size=\"20\" />\n\t\t\t\t\t\t</template>\n\t\t\t\t\t\t{{ t('intravox', 'Edit') }}\n\t\t\t\t\t</NcActionButton>\n\t\t\t\t\t<NcActionButton @click=\"confirmDelete\">\n\t\t\t\t\t\t<template #icon>\n\t\t\t\t\t\t\t<Delete :size=\"20\" />\n\t\t\t\t\t\t</template>\n\t\t\t\t\t\t{{ t('intravox', 'Delete') }}\n\t\t\t\t\t</NcActionButton>\n\t\t\t\t</NcActions>\n\t\t\t</div>\n\t\t</div>\n\n\t\t<div class=\"comment-item__body\">\n\t\t\t<!-- Edit mode -->\n\t\t\t<div v-if=\"editing\" class=\"comment-item__edit\">\n\t\t\t\t<textarea\n\t\t\t\t\tref=\"editInput\"\n\t\t\t\t\tv-model=\"editMessage\"\n\t\t\t\t\tclass=\"comment-item__edit-input\"\n\t\t\t\t\trows=\"3\"\n\t\t\t\t\t@keydown.enter.ctrl=\"saveEdit\"\n\t\t\t\t\t@keydown.escape=\"cancelEdit\" />\n\t\t\t\t<div class=\"comment-item__edit-actions\">\n\t\t\t\t\t<NcButton type=\"tertiary\" @click=\"cancelEdit\">\n\t\t\t\t\t\t{{ t('intravox', 'Cancel') }}\n\t\t\t\t\t</NcButton>\n\t\t\t\t\t<NcButton type=\"primary\" :disabled=\"!editMessage.trim()\" @click=\"saveEdit\">\n\t\t\t\t\t\t{{ t('intravox', 'Save') }}\n\t\t\t\t\t</NcButton>\n\t\t\t\t</div>\n\t\t\t</div>\n\n\t\t\t<!-- View mode -->\n\t\t\t<p v-else class=\"comment-item__message\">{{ comment.message }}</p>\n\t\t</div>\n\n\t\t<!-- Reactions on comment -->\n\t\t<div class=\"comment-item__footer\">\n\t\t\t<!-- Buttons on the left -->\n\t\t\t<div class=\"comment-item__buttons\">\n\t\t\t\t<NcButton v-if=\"!isReply\" type=\"tertiary\" @click=\"$emit('reply')\">\n\t\t\t\t\t<template #icon>\n\t\t\t\t\t\t<Reply :size=\"16\" />\n\t\t\t\t\t</template>\n\t\t\t\t\t{{ t('intravox', 'Reply') }}\n\t\t\t\t</NcButton>\n\t\t\t\t<ReactionPicker\n\t\t\t\t\tv-if=\"allowReactions\"\n\t\t\t\t\tv-model:visible=\"showReactionPicker\"\n\t\t\t\t\t:selected-emojis=\"localUserReactions\"\n\t\t\t\t\t@select=\"addReaction\">\n\t\t\t\t\t<template #trigger>\n\t\t\t\t\t\t<NcButton type=\"tertiary\" :aria-label=\"t('intravox', 'Add reaction')\">\n\t\t\t\t\t\t\t<template #icon>\n\t\t\t\t\t\t\t\t<EmoticonHappyOutline :size=\"16\" />\n\t\t\t\t\t\t\t</template>\n\t\t\t\t\t\t</NcButton>\n\t\t\t\t\t</template>\n\t\t\t\t</ReactionPicker>\n\t\t\t</div>\n\t\t\t<!-- Reactions after buttons (always show existing reactions, even if adding new is disabled) -->\n\t\t\t<div v-if=\"hasReactions\" class=\"comment-item__reactions\">\n\t\t\t\t<button\n\t\t\t\t\tv-for=\"(count, emoji) in comment.reactions\"\n\t\t\t\t\t:key=\"emoji\"\n\t\t\t\t\tclass=\"comment-item__reaction\"\n\t\t\t\t\t:class=\"{ 'comment-item__reaction--active': isUserReaction(emoji) }\"\n\t\t\t\t\t:disabled=\"!allowReactions\"\n\t\t\t\t\t@click=\"allowReactions && toggleReaction(emoji)\">\n\t\t\t\t\t{{ emoji }} {{ count }}\n\t\t\t\t</button>\n\t\t\t</div>\n\t\t</div>\n\n\t\t<!-- Replies (only for top-level comments) -->\n\t\t<div v-if=\"!isReply && comment.replies && comment.replies.length > 0\" class=\"comment-item__replies\">\n\t\t\t<CommentItem\n\t\t\t\tv-for=\"reply in comment.replies\"\n\t\t\t\t:key=\"reply.id\"\n\t\t\t\t:comment=\"reply\"\n\t\t\t\t:current-user-id=\"currentUserId\"\n\t\t\t\t:is-reply=\"true\"\n\t\t\t\t:allow-reactions=\"allowReactions\"\n\t\t\t\t@update=\"$emit('update', $event)\"\n\t\t\t\t@delete=\"$emit('delete', $event)\" />\n\t\t</div>\n\t</div>\n</template>\n\n<script>\nimport { NcAvatar, NcButton, NcActions, NcActionButton } from '@nextcloud/vue'\nimport Pencil from 'vue-material-design-icons/Pencil.vue'\nimport Delete from 'vue-material-design-icons/Delete.vue'\nimport Reply from 'vue-material-design-icons/Reply.vue'\nimport EmoticonHappyOutline from 'vue-material-design-icons/EmoticonHappyOutline.vue'\nimport ReactionPicker from './ReactionPicker.vue'\nimport { updateComment, deleteComment, toggleCommentReaction } from '../../services/CommentService.js'\n\nexport default {\n\tname: 'CommentItem',\n\tcomponents: {\n\t\tNcAvatar,\n\t\tNcButton,\n\t\tNcActions,\n\t\tNcActionButton,\n\t\tPencil,\n\t\tDelete,\n\t\tReply,\n\t\tEmoticonHappyOutline,\n\t\tReactionPicker,\n\t},\n\tprops: {\n\t\tcomment: {\n\t\t\ttype: Object,\n\t\t\trequired: true,\n\t\t},\n\t\tcurrentUserId: {\n\t\t\ttype: String,\n\t\t\tdefault: '',\n\t\t},\n\t\tisReply: {\n\t\t\ttype: Boolean,\n\t\t\tdefault: false,\n\t\t},\n\t\tallowReactions: {\n\t\t\ttype: Boolean,\n\t\t\tdefault: true,\n\t\t},\n\t},\n\temits: ['update', 'delete', 'reply'],\n\tdata() {\n\t\treturn {\n\t\t\tediting: false,\n\t\t\teditMessage: '',\n\t\t\tshowReactionPicker: false,\n\t\t\t// Initialize from prop, will be updated locally on toggle\n\t\t\tlocalUserReactions: [],\n\t\t}\n\t},\n\twatch: {\n\t\t'comment.userReactions': {\n\t\t\timmediate: true,\n\t\t\thandler(newVal) {\n\t\t\t\t// Sync from backend when comment data changes\n\t\t\t\tthis.localUserReactions = newVal || []\n\t\t\t},\n\t\t},\n\t},\n\tcomputed: {\n\t\tcanModify() {\n\t\t\treturn this.comment.userId === this.currentUserId\n\t\t},\n\t\thasReactions() {\n\t\t\treturn this.comment.reactions && Object.keys(this.comment.reactions).length > 0\n\t\t},\n\t\tformattedDate() {\n\t\t\treturn new Date(this.comment.createdAt).toLocaleString()\n\t\t},\n\t\trelativeTime() {\n\t\t\tconst date = new Date(this.comment.createdAt)\n\t\t\tconst now = new Date()\n\t\t\tconst diffMs = now - date\n\t\t\tconst diffMins = Math.floor(diffMs / 60000)\n\t\t\tconst diffHours = Math.floor(diffMs / 3600000)\n\t\t\tconst diffDays = Math.floor(diffMs / 86400000)\n\n\t\t\tif (diffMins < 1) return this.t('intravox', 'just now')\n\t\t\tif (diffMins < 60) return this.t('intravox', '{minutes} min ago', { minutes: diffMins })\n\t\t\tif (diffHours < 24) return this.t('intravox', '{hours} hours ago', { hours: diffHours })\n\t\t\tif (diffDays < 7) return this.t('intravox', '{days} days ago', { days: diffDays })\n\t\t\treturn date.toLocaleDateString()\n\t\t},\n\t},\n\tmethods: {\n\t\tstartEdit() {\n\t\t\tthis.editMessage = this.comment.message\n\t\t\tthis.editing = true\n\t\t\tthis.$nextTick(() => {\n\t\t\t\tthis.$refs.editInput?.focus()\n\t\t\t})\n\t\t},\n\t\tcancelEdit() {\n\t\t\tthis.editing = false\n\t\t\tthis.editMessage = ''\n\t\t},\n\t\tasync saveEdit() {\n\t\t\tif (!this.editMessage.trim()) return\n\n\t\t\ttry {\n\t\t\t\tconst updated = await updateComment(this.comment.id, this.editMessage)\n\t\t\t\tthis.$emit('update', updated)\n\t\t\t\tthis.editing = false\n\t\t\t} catch (error) {\n\t\t\t\tconsole.error('Failed to update comment:', error)\n\t\t\t}\n\t\t},\n\t\tasync confirmDelete() {\n\t\t\tif (confirm(this.t('intravox', 'Are you sure you want to delete this comment?'))) {\n\t\t\t\ttry {\n\t\t\t\t\tawait deleteComment(this.comment.id)\n\t\t\t\t\tthis.$emit('delete', this.comment.id)\n\t\t\t\t} catch (error) {\n\t\t\t\t\tconsole.error('Failed to delete comment:', error)\n\t\t\t\t}\n\t\t\t}\n\t\t},\n\t\tisUserReaction(emoji) {\n\t\t\treturn this.localUserReactions.includes(emoji)\n\t\t},\n\t\tasync toggleReaction(emoji) {\n\t\t\ttry {\n\t\t\t\tconst result = await toggleCommentReaction(\n\t\t\t\t\tthis.comment.id,\n\t\t\t\t\temoji,\n\t\t\t\t\tthis.localUserReactions,\n\t\t\t\t)\n\t\t\t\tthis.localUserReactions = result.userReactions || []\n\t\t\t\t// Update comment reactions\n\t\t\t\tthis.comment.reactions = result.reactions\n\t\t\t} catch (error) {\n\t\t\t\tconsole.error('Failed to toggle reaction:', error)\n\t\t\t}\n\t\t},\n\t\tasync addReaction(emoji) {\n\t\t\tawait this.toggleReaction(emoji)\n\t\t},\n\t\tt(app, str, params = {}) {\n\t\t\tif (window.t) {\n\t\t\t\treturn window.t(app, str, params)\n\t\t\t}\n\t\t\tlet result = str\n\t\t\tfor (const [key, value] of Object.entries(params)) {\n\t\t\t\tresult = result.replace(`{${key}}`, value)\n\t\t\t}\n\t\t\treturn result\n\t\t},\n\t},\n}\n</script>\n\n<style scoped>\n.comment-item {\n\tpadding: 12px 0;\n\tborder-bottom: 1px solid var(--color-border);\n}\n\n.comment-item--reply {\n\tpadding: 8px 0;\n\tborder-bottom: none;\n\tmargin-left: 40px;\n}\n\n.comment-item__header {\n\tdisplay: flex;\n\talign-items: center;\n\tgap: 8px;\n\tmargin-bottom: 8px;\n}\n\n.comment-item__meta {\n\tflex: 1;\n\tdisplay: flex;\n\talign-items: baseline;\n\tgap: 8px;\n}\n\n.comment-item__author {\n\tfont-weight: 600;\n\tcolor: var(--color-main-text);\n}\n\n.comment-item__time {\n\tfont-size: 12px;\n\tcolor: var(--color-text-maxcontrast);\n}\n\n.comment-item__edited {\n\tfont-size: 12px;\n\tfont-style: italic;\n\tcolor: var(--color-text-maxcontrast);\n}\n\n.comment-item__body {\n\tmargin-left: 40px;\n}\n\n.comment-item--reply .comment-item__body {\n\tmargin-left: 36px;\n}\n\n.comment-item__message {\n\tmargin: 0;\n\twhite-space: pre-wrap;\n\tword-break: break-word;\n}\n\n.comment-item__edit {\n\tdisplay: flex;\n\tflex-direction: column;\n\tgap: 8px;\n}\n\n.comment-item__edit-input {\n\twidth: 100%;\n\tpadding: 8px;\n\tborder: 1px solid var(--color-border);\n\tborder-radius: var(--border-radius);\n\tresize: vertical;\n\tfont-family: inherit;\n\tfont-size: inherit;\n}\n\n.comment-item__edit-input:focus {\n\tborder-color: var(--color-primary-element);\n\toutline: none;\n}\n\n.comment-item__edit-actions {\n\tdisplay: flex;\n\tjustify-content: flex-end;\n\tgap: 8px;\n}\n\n.comment-item__footer {\n\tdisplay: flex;\n\talign-items: center;\n\tjustify-content: flex-start;\n\tgap: 8px;\n\tmargin-left: 40px;\n\tmargin-top: 8px;\n}\n\n.comment-item--reply .comment-item__footer {\n\tmargin-left: 36px;\n}\n\n.comment-item__reactions {\n\tdisplay: flex;\n\tflex-wrap: wrap;\n\tgap: 4px;\n}\n\n.comment-item__reaction {\n\tpadding: 2px 8px;\n\tfont-size: 12px;\n\tborder: 1px solid var(--color-border);\n\tborder-radius: 12px;\n\tbackground: transparent;\n\tcursor: pointer;\n\ttransition: all 0.1s ease;\n}\n\n.comment-item__reaction:hover {\n\tbackground: var(--color-background-hover);\n}\n\n.comment-item__reaction--active {\n\tbackground: var(--color-primary-element-light);\n\tborder-color: var(--color-primary-element);\n}\n\n.comment-item__buttons {\n\tdisplay: flex;\n\tgap: 4px;\n}\n\n.comment-item__replies {\n\tmargin-top: 8px;\n}\n</style>\n"],"sourceRoot":""}]);
// Exports
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (___CSS_LOADER_EXPORT___);


/***/ },

/***/ "./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/dist/stylePostLoader.js!./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/reactions/CommentSection.vue?vue&type=style&index=0&id=7504410a&scoped=true&lang=css"
/*!*************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/dist/stylePostLoader.js!./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/reactions/CommentSection.vue?vue&type=style&index=0&id=7504410a&scoped=true&lang=css ***!
  \*************************************************************************************************************************************************************************************************************************************************************************/
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
.comment-section[data-v-7504410a] {
	margin-top: 24px;
	padding-top: 24px;
	border-top: 1px solid var(--color-border);
}
.comment-section__header[data-v-7504410a] {
	display: flex;
	align-items: center;
	justify-content: space-between;
	margin-bottom: 16px;
}
.comment-section__title[data-v-7504410a] {
	display: flex;
	align-items: center;
	gap: 8px;
	margin: 0;
	font-size: 18px;
	font-weight: 600;
}
.comment-section__count[data-v-7504410a] {
	font-weight: 400;
	color: var(--color-text-maxcontrast);
}
.comment-section__sort[data-v-7504410a] {
	width: 150px;
}
.comment-section__loading[data-v-7504410a] {
	display: flex;
	justify-content: center;
	padding: 32px;
}
.comment-section__empty[data-v-7504410a] {
	text-align: center;
	padding: 32px;
	color: var(--color-text-maxcontrast);
}
.comment-section__list[data-v-7504410a] {
	margin-bottom: 24px;
}
.comment-section__replying[data-v-7504410a] {
	display: flex;
	align-items: center;
	justify-content: space-between;
	padding: 8px 12px;
	margin-bottom: 8px;
	background: var(--color-background-hover);
	border-radius: var(--border-radius);
	font-size: 13px;
	color: var(--color-text-maxcontrast);
}
.comment-section__input[data-v-7504410a] {
	display: flex;
	gap: 12px;
	align-items: flex-start;
}
.comment-section__input-wrapper[data-v-7504410a] {
	flex: 1;
	display: flex;
	flex-direction: column;
	gap: 8px;
}
.comment-section__textarea[data-v-7504410a] {
	width: 100%;
	padding: 12px;
	border: 1px solid var(--color-border);
	border-radius: var(--border-radius);
	resize: vertical;
	font-family: inherit;
	font-size: inherit;
	min-height: 60px;
}
.comment-section__textarea[data-v-7504410a]:focus {
	border-color: var(--color-primary-element);
	outline: none;
}
.comment-section__textarea[data-v-7504410a]::placeholder {
	color: var(--color-text-maxcontrast);
}
.comment-section__submit[data-v-7504410a] {
	display: flex;
	align-items: center;
	justify-content: space-between;
}
.comment-section__hint[data-v-7504410a] {
	font-size: 12px;
	color: var(--color-text-maxcontrast);
}

/* Inline reply styles */
.comment-wrapper[data-v-7504410a] {
	/* Wrapper for comment + inline reply */
}
.comment-section__inline-reply[data-v-7504410a] {
	display: flex;
	gap: 10px;
	align-items: flex-start;
	margin-left: 40px;
	margin-top: 8px;
	padding: 12px;
	background: var(--color-background-hover);
	border-radius: var(--border-radius-large);
	border-left: 3px solid var(--color-primary-element);
}
.comment-section__inline-reply-wrapper[data-v-7504410a] {
	flex: 1;
	display: flex;
	flex-direction: column;
	gap: 8px;
}
.comment-section__replying-indicator[data-v-7504410a] {
	display: flex;
	align-items: center;
	justify-content: space-between;
	font-size: 13px;
	color: var(--color-text-maxcontrast);
}
.comment-section__reply-textarea[data-v-7504410a] {
	width: 100%;
	padding: 10px;
	border: 1px solid var(--color-border);
	border-radius: var(--border-radius);
	resize: vertical;
	font-family: inherit;
	font-size: 14px;
	min-height: 50px;
	background: var(--color-main-background);
}
.comment-section__reply-textarea[data-v-7504410a]:focus {
	border-color: var(--color-primary-element);
	outline: none;
}
.comment-section__reply-textarea[data-v-7504410a]::placeholder {
	color: var(--color-text-maxcontrast);
}
.comment-section__reply-actions[data-v-7504410a] {
	display: flex;
	justify-content: flex-end;
	gap: 8px;
}
`, "",{"version":3,"sources":["webpack://./src/components/reactions/CommentSection.vue"],"names":[],"mappings":";AAoTA;CACC,gBAAgB;CAChB,iBAAiB;CACjB,yCAAyC;AAC1C;AAEA;CACC,aAAa;CACb,mBAAmB;CACnB,8BAA8B;CAC9B,mBAAmB;AACpB;AAEA;CACC,aAAa;CACb,mBAAmB;CACnB,QAAQ;CACR,SAAS;CACT,eAAe;CACf,gBAAgB;AACjB;AAEA;CACC,gBAAgB;CAChB,oCAAoC;AACrC;AAEA;CACC,YAAY;AACb;AAEA;CACC,aAAa;CACb,uBAAuB;CACvB,aAAa;AACd;AAEA;CACC,kBAAkB;CAClB,aAAa;CACb,oCAAoC;AACrC;AAEA;CACC,mBAAmB;AACpB;AAEA;CACC,aAAa;CACb,mBAAmB;CACnB,8BAA8B;CAC9B,iBAAiB;CACjB,kBAAkB;CAClB,yCAAyC;CACzC,mCAAmC;CACnC,eAAe;CACf,oCAAoC;AACrC;AAEA;CACC,aAAa;CACb,SAAS;CACT,uBAAuB;AACxB;AAEA;CACC,OAAO;CACP,aAAa;CACb,sBAAsB;CACtB,QAAQ;AACT;AAEA;CACC,WAAW;CACX,aAAa;CACb,qCAAqC;CACrC,mCAAmC;CACnC,gBAAgB;CAChB,oBAAoB;CACpB,kBAAkB;CAClB,gBAAgB;AACjB;AAEA;CACC,0CAA0C;CAC1C,aAAa;AACd;AAEA;CACC,oCAAoC;AACrC;AAEA;CACC,aAAa;CACb,mBAAmB;CACnB,8BAA8B;AAC/B;AAEA;CACC,eAAe;CACf,oCAAoC;AACrC;;AAEA,wBAAwB;AACxB;CACC,uCAAuC;AACxC;AAEA;CACC,aAAa;CACb,SAAS;CACT,uBAAuB;CACvB,iBAAiB;CACjB,eAAe;CACf,aAAa;CACb,yCAAyC;CACzC,yCAAyC;CACzC,mDAAmD;AACpD;AAEA;CACC,OAAO;CACP,aAAa;CACb,sBAAsB;CACtB,QAAQ;AACT;AAEA;CACC,aAAa;CACb,mBAAmB;CACnB,8BAA8B;CAC9B,eAAe;CACf,oCAAoC;AACrC;AAEA;CACC,WAAW;CACX,aAAa;CACb,qCAAqC;CACrC,mCAAmC;CACnC,gBAAgB;CAChB,oBAAoB;CACpB,eAAe;CACf,gBAAgB;CAChB,wCAAwC;AACzC;AAEA;CACC,0CAA0C;CAC1C,aAAa;AACd;AAEA;CACC,oCAAoC;AACrC;AAEA;CACC,aAAa;CACb,yBAAyB;CACzB,QAAQ;AACT","sourcesContent":["<template>\n\t<div class=\"comment-section\">\n\t\t<div class=\"comment-section__header\">\n\t\t\t<h3 class=\"comment-section__title\">\n\t\t\t\t<CommentTextOutline :size=\"20\" />\n\t\t\t\t{{ t('intravox', 'Comments') }}\n\t\t\t\t<span v-if=\"totalComments > 0\" class=\"comment-section__count\">({{ totalComments }})</span>\n\t\t\t</h3>\n\t\t\t<NcSelect\n\t\t\t\tv-model=\"sortOrder\"\n\t\t\t\t:options=\"sortOptions\"\n\t\t\t\t:clearable=\"false\"\n\t\t\t\t:searchable=\"false\"\n\t\t\t\t:aria-label=\"t('intravox', 'Sort comments')\"\n\t\t\t\tclass=\"comment-section__sort\" />\n\t\t</div>\n\n\t\t<!-- New comment input (at top) -->\n\t\t<div class=\"comment-section__input\">\n\t\t\t<NcAvatar :user=\"currentUserId\" :size=\"32\" />\n\t\t\t<div class=\"comment-section__input-wrapper\">\n\t\t\t\t<textarea\n\t\t\t\t\tref=\"commentInput\"\n\t\t\t\t\tv-model=\"newComment\"\n\t\t\t\t\tclass=\"comment-section__textarea\"\n\t\t\t\t\t:placeholder=\"t('intravox', 'Write a comment...')\"\n\t\t\t\t\t:aria-label=\"t('intravox', 'Write a comment')\"\n\t\t\t\t\trows=\"2\"\n\t\t\t\t\t@keydown.enter.ctrl=\"submitComment\"\n\t\t\t\t\t@focus=\"inputFocused = true\"\n\t\t\t\t\t@blur=\"inputFocused = false\" />\n\t\t\t\t<div v-if=\"inputFocused || newComment.trim()\" class=\"comment-section__submit\">\n\t\t\t\t\t<span class=\"comment-section__hint\">{{ t('intravox', 'Ctrl+Enter to send') }}</span>\n\t\t\t\t\t<NcButton\n\t\t\t\t\t\ttype=\"primary\"\n\t\t\t\t\t\t:disabled=\"!newComment.trim() || submitting\"\n\t\t\t\t\t\t@click=\"submitComment\">\n\t\t\t\t\t\t<template #icon>\n\t\t\t\t\t\t\t<Send :size=\"20\" />\n\t\t\t\t\t\t</template>\n\t\t\t\t\t\t{{ t('intravox', 'Send') }}\n\t\t\t\t\t</NcButton>\n\t\t\t\t</div>\n\t\t\t</div>\n\t\t</div>\n\n\t\t<!-- Comment list -->\n\t\t<div v-if=\"loading\" class=\"comment-section__loading\">\n\t\t\t<NcLoadingIcon :size=\"32\" />\n\t\t</div>\n\n\t\t<div v-else-if=\"comments.length === 0\" class=\"comment-section__empty\">\n\t\t\t<p>{{ t('intravox', 'No comments yet. Be the first to comment!') }}</p>\n\t\t</div>\n\n\t\t<div v-else class=\"comment-section__list\">\n\t\t\t<div v-for=\"comment in sortedComments\" :key=\"comment.id\" class=\"comment-wrapper\">\n\t\t\t\t<CommentItem\n\t\t\t\t\t:comment=\"comment\"\n\t\t\t\t\t:current-user-id=\"currentUserId\"\n\t\t\t\t\t:allow-reactions=\"allowCommentReactions\"\n\t\t\t\t\t@reply=\"startReply(comment)\"\n\t\t\t\t\t@update=\"handleCommentUpdate\"\n\t\t\t\t\t@delete=\"handleCommentDelete\" />\n\n\t\t\t\t<!-- Inline reply input - shows below the comment being replied to -->\n\t\t\t\t<div v-if=\"replyingTo && replyingTo.id === comment.id\" class=\"comment-section__inline-reply\">\n\t\t\t\t\t<NcAvatar :user=\"currentUserId\" :size=\"28\" />\n\t\t\t\t\t<div class=\"comment-section__inline-reply-wrapper\">\n\t\t\t\t\t\t<div class=\"comment-section__replying-indicator\">\n\t\t\t\t\t\t\t<span>{{ t('intravox', 'Replying to {name}', { name: replyingTo.displayName }) }}</span>\n\t\t\t\t\t\t\t<NcButton type=\"tertiary\" @click=\"cancelReply\">\n\t\t\t\t\t\t\t\t<template #icon>\n\t\t\t\t\t\t\t\t\t<Close :size=\"16\" />\n\t\t\t\t\t\t\t\t</template>\n\t\t\t\t\t\t\t</NcButton>\n\t\t\t\t\t\t</div>\n\t\t\t\t\t\t<textarea\n\t\t\t\t\t\t\tref=\"replyInput\"\n\t\t\t\t\t\t\tv-model=\"replyText\"\n\t\t\t\t\t\t\tclass=\"comment-section__reply-textarea\"\n\t\t\t\t\t\t\t:placeholder=\"t('intravox', 'Write a reply...')\"\n\t\t\t\t\t\t\t:aria-label=\"t('intravox', 'Write a reply')\"\n\t\t\t\t\t\t\trows=\"2\"\n\t\t\t\t\t\t\t@keydown.enter.ctrl=\"submitReply\"\n\t\t\t\t\t\t\t@keydown.escape=\"cancelReply\" />\n\t\t\t\t\t\t<div class=\"comment-section__reply-actions\">\n\t\t\t\t\t\t\t<NcButton type=\"tertiary\" @click=\"cancelReply\">\n\t\t\t\t\t\t\t\t{{ t('intravox', 'Cancel') }}\n\t\t\t\t\t\t\t</NcButton>\n\t\t\t\t\t\t\t<NcButton\n\t\t\t\t\t\t\t\ttype=\"primary\"\n\t\t\t\t\t\t\t\t:disabled=\"!replyText.trim() || submitting\"\n\t\t\t\t\t\t\t\t@click=\"submitReply\">\n\t\t\t\t\t\t\t\t{{ t('intravox', 'Reply') }}\n\t\t\t\t\t\t\t</NcButton>\n\t\t\t\t\t\t</div>\n\t\t\t\t\t</div>\n\t\t\t\t</div>\n\t\t\t</div>\n\t\t</div>\n\t</div>\n</template>\n\n<script>\nimport { NcAvatar, NcButton, NcSelect, NcLoadingIcon } from '@nextcloud/vue'\nimport CommentTextOutline from 'vue-material-design-icons/CommentTextOutline.vue'\nimport Send from 'vue-material-design-icons/Send.vue'\nimport Close from 'vue-material-design-icons/Close.vue'\nimport CommentItem from './CommentItem.vue'\nimport { getComments, createComment } from '../../services/CommentService.js'\nimport { getCurrentUser } from '@nextcloud/auth'\n\nexport default {\n\tname: 'CommentSection',\n\tcomponents: {\n\t\tNcAvatar,\n\t\tNcButton,\n\t\tNcSelect,\n\t\tNcLoadingIcon,\n\t\tCommentTextOutline,\n\t\tSend,\n\t\tClose,\n\t\tCommentItem,\n\t},\n\tprops: {\n\t\tpageId: {\n\t\t\ttype: String,\n\t\t\trequired: true,\n\t\t},\n\t\tallowCommentReactions: {\n\t\t\ttype: Boolean,\n\t\t\tdefault: true,\n\t\t},\n\t},\n\tdata() {\n\t\treturn {\n\t\t\tcomments: [],\n\t\t\ttotalComments: 0,\n\t\t\tloading: true,\n\t\t\tsubmitting: false,\n\t\t\tnewComment: '',\n\t\t\treplyingTo: null,\n\t\t\treplyText: '',\n\t\t\tinputFocused: false,\n\t\t\tsortOrder: { value: 'newest', label: this.t('intravox', 'Newest first') },\n\t\t\tsortOptions: [\n\t\t\t\t{ value: 'newest', label: this.t('intravox', 'Newest first') },\n\t\t\t\t{ value: 'oldest', label: this.t('intravox', 'Oldest first') },\n\t\t\t],\n\t\t}\n\t},\n\tcomputed: {\n\t\tcurrentUserId() {\n\t\t\treturn getCurrentUser()?.uid || ''\n\t\t},\n\t\tsortedComments() {\n\t\t\tconst sorted = [...this.comments]\n\t\t\tif (this.sortOrder.value === 'newest') {\n\t\t\t\tsorted.sort((a, b) => new Date(b.createdAt) - new Date(a.createdAt))\n\t\t\t} else {\n\t\t\t\tsorted.sort((a, b) => new Date(a.createdAt) - new Date(b.createdAt))\n\t\t\t}\n\t\t\treturn sorted\n\t\t},\n\t},\n\twatch: {\n\t\tpageId: {\n\t\t\timmediate: true,\n\t\t\thandler() {\n\t\t\t\tthis.loadComments()\n\t\t\t},\n\t\t},\n\t},\n\tmethods: {\n\t\tasync loadComments() {\n\t\t\tthis.loading = true\n\t\t\ttry {\n\t\t\t\tconst result = await getComments(this.pageId)\n\t\t\t\tthis.comments = result.comments || []\n\t\t\t\tthis.totalComments = result.total || 0\n\t\t\t} catch (error) {\n\t\t\t\tconsole.error('Failed to load comments:', error)\n\t\t\t\tthis.comments = []\n\t\t\t\tthis.totalComments = 0\n\t\t\t} finally {\n\t\t\t\tthis.loading = false\n\t\t\t}\n\t\t},\n\t\tasync submitComment() {\n\t\t\tif (!this.newComment.trim() || this.submitting) return\n\n\t\t\tthis.submitting = true\n\t\t\ttry {\n\t\t\t\tconst parentId = this.replyingTo?.id || null\n\t\t\t\tconst comment = await createComment(this.pageId, this.newComment, parentId)\n\n\t\t\t\tif (parentId) {\n\t\t\t\t\t// Add reply to parent comment\n\t\t\t\t\tconst parent = this.comments.find(c => c.id === parentId)\n\t\t\t\t\tif (parent) {\n\t\t\t\t\t\tif (!parent.replies) parent.replies = []\n\t\t\t\t\t\tparent.replies.push(comment)\n\t\t\t\t\t}\n\t\t\t\t} else {\n\t\t\t\t\t// Add new top-level comment\n\t\t\t\t\tthis.comments.unshift(comment)\n\t\t\t\t}\n\n\t\t\t\tthis.totalComments++\n\t\t\t\tthis.newComment = ''\n\t\t\t\tthis.replyingTo = null\n\t\t\t} catch (error) {\n\t\t\t\tconsole.error('Failed to create comment:', error)\n\t\t\t} finally {\n\t\t\t\tthis.submitting = false\n\t\t\t}\n\t\t},\n\t\tstartReply(comment) {\n\t\t\tthis.replyingTo = comment\n\t\t\tthis.replyText = ''\n\t\t\tthis.$nextTick(() => {\n\t\t\t\tthis.$refs.replyInput?.focus()\n\t\t\t})\n\t\t},\n\t\tcancelReply() {\n\t\t\tthis.replyingTo = null\n\t\t\tthis.replyText = ''\n\t\t},\n\t\tasync submitReply() {\n\t\t\tif (!this.replyText.trim() || this.submitting || !this.replyingTo) return\n\n\t\t\tthis.submitting = true\n\t\t\ttry {\n\t\t\t\tconst parentId = this.replyingTo.id\n\t\t\t\tconst reply = await createComment(this.pageId, this.replyText, parentId)\n\n\t\t\t\t// Add reply to parent comment\n\t\t\t\tconst parent = this.comments.find(c => c.id === parentId)\n\t\t\t\tif (parent) {\n\t\t\t\t\tif (!parent.replies) parent.replies = []\n\t\t\t\t\tparent.replies.push(reply)\n\t\t\t\t}\n\n\t\t\t\tthis.totalComments++\n\t\t\t\tthis.replyText = ''\n\t\t\t\tthis.replyingTo = null\n\t\t\t} catch (error) {\n\t\t\t\tconsole.error('Failed to create reply:', error)\n\t\t\t} finally {\n\t\t\t\tthis.submitting = false\n\t\t\t}\n\t\t},\n\t\thandleCommentUpdate(updatedComment) {\n\t\t\t// Update in comments list\n\t\t\tconst index = this.comments.findIndex(c => c.id === updatedComment.id)\n\t\t\tif (index !== -1) {\n\t\t\t\tthis.comments[index] = { ...this.comments[index], ...updatedComment }\n\t\t\t} else {\n\t\t\t\t// Check in replies\n\t\t\t\tfor (const comment of this.comments) {\n\t\t\t\t\tif (comment.replies) {\n\t\t\t\t\t\tconst replyIndex = comment.replies.findIndex(r => r.id === updatedComment.id)\n\t\t\t\t\t\tif (replyIndex !== -1) {\n\t\t\t\t\t\t\tcomment.replies[replyIndex] = { ...comment.replies[replyIndex], ...updatedComment }\n\t\t\t\t\t\t\tbreak\n\t\t\t\t\t\t}\n\t\t\t\t\t}\n\t\t\t\t}\n\t\t\t}\n\t\t},\n\t\thandleCommentDelete(commentId) {\n\t\t\t// Remove from comments list\n\t\t\tconst index = this.comments.findIndex(c => c.id === commentId)\n\t\t\tif (index !== -1) {\n\t\t\t\t// Count parent + all replies (backend deletes them too)\n\t\t\t\tconst deletedCount = 1 + (this.comments[index].replies?.length || 0)\n\t\t\t\tthis.comments.splice(index, 1)\n\t\t\t\tthis.totalComments -= deletedCount\n\t\t\t} else {\n\t\t\t\t// Check in replies (single reply deleted)\n\t\t\t\tfor (const comment of this.comments) {\n\t\t\t\t\tif (comment.replies) {\n\t\t\t\t\t\tconst replyIndex = comment.replies.findIndex(r => r.id === commentId)\n\t\t\t\t\t\tif (replyIndex !== -1) {\n\t\t\t\t\t\t\tcomment.replies.splice(replyIndex, 1)\n\t\t\t\t\t\t\tthis.totalComments--\n\t\t\t\t\t\t\tbreak\n\t\t\t\t\t\t}\n\t\t\t\t\t}\n\t\t\t\t}\n\t\t\t}\n\t\t},\n\t\tt(app, str, params = {}) {\n\t\t\tif (window.t) {\n\t\t\t\treturn window.t(app, str, params)\n\t\t\t}\n\t\t\tlet result = str\n\t\t\tfor (const [key, value] of Object.entries(params)) {\n\t\t\t\tresult = result.replace(`{${key}}`, value)\n\t\t\t}\n\t\t\treturn result\n\t\t},\n\t},\n}\n</script>\n\n<style scoped>\n.comment-section {\n\tmargin-top: 24px;\n\tpadding-top: 24px;\n\tborder-top: 1px solid var(--color-border);\n}\n\n.comment-section__header {\n\tdisplay: flex;\n\talign-items: center;\n\tjustify-content: space-between;\n\tmargin-bottom: 16px;\n}\n\n.comment-section__title {\n\tdisplay: flex;\n\talign-items: center;\n\tgap: 8px;\n\tmargin: 0;\n\tfont-size: 18px;\n\tfont-weight: 600;\n}\n\n.comment-section__count {\n\tfont-weight: 400;\n\tcolor: var(--color-text-maxcontrast);\n}\n\n.comment-section__sort {\n\twidth: 150px;\n}\n\n.comment-section__loading {\n\tdisplay: flex;\n\tjustify-content: center;\n\tpadding: 32px;\n}\n\n.comment-section__empty {\n\ttext-align: center;\n\tpadding: 32px;\n\tcolor: var(--color-text-maxcontrast);\n}\n\n.comment-section__list {\n\tmargin-bottom: 24px;\n}\n\n.comment-section__replying {\n\tdisplay: flex;\n\talign-items: center;\n\tjustify-content: space-between;\n\tpadding: 8px 12px;\n\tmargin-bottom: 8px;\n\tbackground: var(--color-background-hover);\n\tborder-radius: var(--border-radius);\n\tfont-size: 13px;\n\tcolor: var(--color-text-maxcontrast);\n}\n\n.comment-section__input {\n\tdisplay: flex;\n\tgap: 12px;\n\talign-items: flex-start;\n}\n\n.comment-section__input-wrapper {\n\tflex: 1;\n\tdisplay: flex;\n\tflex-direction: column;\n\tgap: 8px;\n}\n\n.comment-section__textarea {\n\twidth: 100%;\n\tpadding: 12px;\n\tborder: 1px solid var(--color-border);\n\tborder-radius: var(--border-radius);\n\tresize: vertical;\n\tfont-family: inherit;\n\tfont-size: inherit;\n\tmin-height: 60px;\n}\n\n.comment-section__textarea:focus {\n\tborder-color: var(--color-primary-element);\n\toutline: none;\n}\n\n.comment-section__textarea::placeholder {\n\tcolor: var(--color-text-maxcontrast);\n}\n\n.comment-section__submit {\n\tdisplay: flex;\n\talign-items: center;\n\tjustify-content: space-between;\n}\n\n.comment-section__hint {\n\tfont-size: 12px;\n\tcolor: var(--color-text-maxcontrast);\n}\n\n/* Inline reply styles */\n.comment-wrapper {\n\t/* Wrapper for comment + inline reply */\n}\n\n.comment-section__inline-reply {\n\tdisplay: flex;\n\tgap: 10px;\n\talign-items: flex-start;\n\tmargin-left: 40px;\n\tmargin-top: 8px;\n\tpadding: 12px;\n\tbackground: var(--color-background-hover);\n\tborder-radius: var(--border-radius-large);\n\tborder-left: 3px solid var(--color-primary-element);\n}\n\n.comment-section__inline-reply-wrapper {\n\tflex: 1;\n\tdisplay: flex;\n\tflex-direction: column;\n\tgap: 8px;\n}\n\n.comment-section__replying-indicator {\n\tdisplay: flex;\n\talign-items: center;\n\tjustify-content: space-between;\n\tfont-size: 13px;\n\tcolor: var(--color-text-maxcontrast);\n}\n\n.comment-section__reply-textarea {\n\twidth: 100%;\n\tpadding: 10px;\n\tborder: 1px solid var(--color-border);\n\tborder-radius: var(--border-radius);\n\tresize: vertical;\n\tfont-family: inherit;\n\tfont-size: 14px;\n\tmin-height: 50px;\n\tbackground: var(--color-main-background);\n}\n\n.comment-section__reply-textarea:focus {\n\tborder-color: var(--color-primary-element);\n\toutline: none;\n}\n\n.comment-section__reply-textarea::placeholder {\n\tcolor: var(--color-text-maxcontrast);\n}\n\n.comment-section__reply-actions {\n\tdisplay: flex;\n\tjustify-content: flex-end;\n\tgap: 8px;\n}\n</style>\n"],"sourceRoot":""}]);
// Exports
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (___CSS_LOADER_EXPORT___);


/***/ },

/***/ "./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/dist/stylePostLoader.js!./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/reactions/ReactionBar.vue?vue&type=style&index=0&id=0ed66f85&scoped=true&lang=css"
/*!**********************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/dist/stylePostLoader.js!./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/reactions/ReactionBar.vue?vue&type=style&index=0&id=0ed66f85&scoped=true&lang=css ***!
  \**********************************************************************************************************************************************************************************************************************************************************************/
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
.reaction-bar[data-v-0ed66f85] {
	display: flex;
	align-items: center;
	flex-wrap: wrap;
	gap: 8px;
	padding: 12px 0;
}
.reaction-bar__reactions[data-v-0ed66f85] {
	display: flex;
	flex-wrap: wrap;
	gap: 6px;
}
.reaction-bar__reaction[data-v-0ed66f85] {
	display: flex;
	align-items: center;
	gap: 4px;
	padding: 4px 10px;
	border: 1px solid var(--color-border);
	border-radius: 16px;
	background: var(--color-main-background);
	cursor: pointer;
	transition: all 0.1s ease;
}
.reaction-bar__reaction[data-v-0ed66f85]:hover {
	background: var(--color-background-hover);
	border-color: var(--color-border-dark);
}
.reaction-bar__reaction--active[data-v-0ed66f85] {
	background: var(--color-primary-element-light);
	border-color: var(--color-primary-element);
}
.reaction-bar__reaction--active[data-v-0ed66f85]:hover {
	background: var(--color-primary-element-light);
}
.reaction-bar__emoji[data-v-0ed66f85] {
	font-size: 16px;
}
.reaction-bar__count[data-v-0ed66f85] {
	font-size: 13px;
	color: var(--color-text-maxcontrast);
}
`, "",{"version":3,"sources":["webpack://./src/components/reactions/ReactionBar.vue"],"names":[],"mappings":";AAgHA;CACC,aAAa;CACb,mBAAmB;CACnB,eAAe;CACf,QAAQ;CACR,eAAe;AAChB;AAEA;CACC,aAAa;CACb,eAAe;CACf,QAAQ;AACT;AAEA;CACC,aAAa;CACb,mBAAmB;CACnB,QAAQ;CACR,iBAAiB;CACjB,qCAAqC;CACrC,mBAAmB;CACnB,wCAAwC;CACxC,eAAe;CACf,yBAAyB;AAC1B;AAEA;CACC,yCAAyC;CACzC,sCAAsC;AACvC;AAEA;CACC,8CAA8C;CAC9C,0CAA0C;AAC3C;AAEA;CACC,8CAA8C;AAC/C;AAEA;CACC,eAAe;AAChB;AAEA;CACC,eAAe;CACf,oCAAoC;AACrC","sourcesContent":["<template>\n\t<div class=\"reaction-bar\">\n\t\t<!-- Existing reactions -->\n\t\t<div class=\"reaction-bar__reactions\">\n\t\t\t<button\n\t\t\t\tv-for=\"(count, emoji) in reactions\"\n\t\t\t\t:key=\"emoji\"\n\t\t\t\tclass=\"reaction-bar__reaction\"\n\t\t\t\t:class=\"{ 'reaction-bar__reaction--active': userReactions.includes(emoji) }\"\n\t\t\t\t:title=\"getReactionTitle(emoji, count)\"\n\t\t\t\t@click=\"toggleReaction(emoji)\">\n\t\t\t\t<span class=\"reaction-bar__emoji\">{{ emoji }}</span>\n\t\t\t\t<span class=\"reaction-bar__count\">{{ count }}</span>\n\t\t\t</button>\n\t\t</div>\n\n\t\t<!-- Add reaction button -->\n\t\t<ReactionPicker\n\t\t\tv-model:visible=\"showPicker\"\n\t\t\t:selected-emojis=\"userReactions\"\n\t\t\t@select=\"addReaction\">\n\t\t\t<template #trigger>\n\t\t\t\t<NcButton\n\t\t\t\t\ttype=\"tertiary\"\n\t\t\t\t\tclass=\"reaction-bar__add\"\n\t\t\t\t\t:aria-label=\"t('intravox', 'Add reaction')\">\n\t\t\t\t\t<template #icon>\n\t\t\t\t\t\t<EmoticonHappyOutline :size=\"20\" />\n\t\t\t\t\t</template>\n\t\t\t\t\t<template v-if=\"Object.keys(reactions).length === 0\">\n\t\t\t\t\t\t{{ t('intravox', 'React') }}\n\t\t\t\t\t</template>\n\t\t\t\t</NcButton>\n\t\t\t</template>\n\t\t</ReactionPicker>\n\t</div>\n</template>\n\n<script>\nimport { NcButton } from '@nextcloud/vue'\nimport EmoticonHappyOutline from 'vue-material-design-icons/EmoticonHappyOutline.vue'\nimport ReactionPicker from './ReactionPicker.vue'\nimport { togglePageReaction } from '../../services/CommentService.js'\n\nexport default {\n\tname: 'ReactionBar',\n\tcomponents: {\n\t\tNcButton,\n\t\tEmoticonHappyOutline,\n\t\tReactionPicker,\n\t},\n\tprops: {\n\t\tpageId: {\n\t\t\ttype: String,\n\t\t\trequired: true,\n\t\t},\n\t\treactions: {\n\t\t\ttype: Object,\n\t\t\tdefault: () => ({}),\n\t\t},\n\t\tuserReactions: {\n\t\t\ttype: Array,\n\t\t\tdefault: () => [],\n\t\t},\n\t},\n\temits: ['update'],\n\tdata() {\n\t\treturn {\n\t\t\tshowPicker: false,\n\t\t\tloading: false,\n\t\t}\n\t},\n\tmethods: {\n\t\tasync toggleReaction(emoji) {\n\t\t\tif (this.loading) return\n\t\t\tthis.loading = true\n\n\t\t\ttry {\n\t\t\t\tconst result = await togglePageReaction(this.pageId, emoji, this.userReactions)\n\t\t\t\tthis.$emit('update', result)\n\t\t\t} catch (error) {\n\t\t\t\tconsole.error('Failed to toggle reaction:', error)\n\t\t\t} finally {\n\t\t\t\tthis.loading = false\n\t\t\t}\n\t\t},\n\t\tasync addReaction(emoji) {\n\t\t\tawait this.toggleReaction(emoji)\n\t\t},\n\t\tgetReactionTitle(emoji, count) {\n\t\t\tconst isActive = this.userReactions.includes(emoji)\n\t\t\tif (isActive) {\n\t\t\t\treturn this.t('intravox', 'You and {count} others reacted with {emoji}', { count: count - 1, emoji })\n\t\t\t}\n\t\t\treturn this.t('intravox', '{count} people reacted with {emoji}', { count, emoji })\n\t\t},\n\t\tt(app, str, params = {}) {\n\t\t\tif (window.t) {\n\t\t\t\treturn window.t(app, str, params)\n\t\t\t}\n\t\t\t// Fallback: simple parameter replacement\n\t\t\tlet result = str\n\t\t\tfor (const [key, value] of Object.entries(params)) {\n\t\t\t\tresult = result.replace(`{${key}}`, value)\n\t\t\t}\n\t\t\treturn result\n\t\t},\n\t},\n}\n</script>\n\n<style scoped>\n.reaction-bar {\n\tdisplay: flex;\n\talign-items: center;\n\tflex-wrap: wrap;\n\tgap: 8px;\n\tpadding: 12px 0;\n}\n\n.reaction-bar__reactions {\n\tdisplay: flex;\n\tflex-wrap: wrap;\n\tgap: 6px;\n}\n\n.reaction-bar__reaction {\n\tdisplay: flex;\n\talign-items: center;\n\tgap: 4px;\n\tpadding: 4px 10px;\n\tborder: 1px solid var(--color-border);\n\tborder-radius: 16px;\n\tbackground: var(--color-main-background);\n\tcursor: pointer;\n\ttransition: all 0.1s ease;\n}\n\n.reaction-bar__reaction:hover {\n\tbackground: var(--color-background-hover);\n\tborder-color: var(--color-border-dark);\n}\n\n.reaction-bar__reaction--active {\n\tbackground: var(--color-primary-element-light);\n\tborder-color: var(--color-primary-element);\n}\n\n.reaction-bar__reaction--active:hover {\n\tbackground: var(--color-primary-element-light);\n}\n\n.reaction-bar__emoji {\n\tfont-size: 16px;\n}\n\n.reaction-bar__count {\n\tfont-size: 13px;\n\tcolor: var(--color-text-maxcontrast);\n}\n</style>\n"],"sourceRoot":""}]);
// Exports
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (___CSS_LOADER_EXPORT___);


/***/ },

/***/ "./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/dist/stylePostLoader.js!./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/reactions/ReactionPicker.vue?vue&type=style&index=0&id=11a9eb8c&scoped=true&lang=css"
/*!*************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/dist/stylePostLoader.js!./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/reactions/ReactionPicker.vue?vue&type=style&index=0&id=11a9eb8c&scoped=true&lang=css ***!
  \*************************************************************************************************************************************************************************************************************************************************************************/
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
.reaction-picker[data-v-11a9eb8c] {
	padding: 8px;
}
.reaction-picker__grid[data-v-11a9eb8c] {
	display: grid;
	grid-template-columns: repeat(6, 1fr);
	gap: 4px;
}
.reaction-picker__emoji[data-v-11a9eb8c] {
	display: flex;
	align-items: center;
	justify-content: center;
	width: 36px;
	height: 36px;
	font-size: 20px;
	border: none;
	background: transparent;
	border-radius: var(--border-radius);
	cursor: pointer;
	transition: background-color 0.1s ease;
}
.reaction-picker__emoji[data-v-11a9eb8c]:hover {
	background-color: var(--color-background-hover);
}
.reaction-picker__emoji--selected[data-v-11a9eb8c] {
	background-color: var(--color-primary-element-light);
}
`, "",{"version":3,"sources":["webpack://./src/components/reactions/ReactionPicker.vue"],"names":[],"mappings":";AAwEA;CACC,YAAY;AACb;AAEA;CACC,aAAa;CACb,qCAAqC;CACrC,QAAQ;AACT;AAEA;CACC,aAAa;CACb,mBAAmB;CACnB,uBAAuB;CACvB,WAAW;CACX,YAAY;CACZ,eAAe;CACf,YAAY;CACZ,uBAAuB;CACvB,mCAAmC;CACnC,eAAe;CACf,sCAAsC;AACvC;AAEA;CACC,+CAA+C;AAChD;AAEA;CACC,oDAAoD;AACrD","sourcesContent":["<template>\n\t<NcPopover :shown=\"visible\" @update:shown=\"$emit('update:visible', $event)\">\n\t\t<template #trigger>\n\t\t\t<slot name=\"trigger\">\n\t\t\t\t<NcButton type=\"tertiary\" :aria-label=\"t('intravox', 'Add reaction')\">\n\t\t\t\t\t<template #icon>\n\t\t\t\t\t\t<Plus :size=\"20\" />\n\t\t\t\t\t</template>\n\t\t\t\t</NcButton>\n\t\t\t</slot>\n\t\t</template>\n\t\t<div class=\"reaction-picker\">\n\t\t\t<div class=\"reaction-picker__grid\">\n\t\t\t\t<button\n\t\t\t\t\tv-for=\"emoji in commonEmojis\"\n\t\t\t\t\t:key=\"emoji\"\n\t\t\t\t\tclass=\"reaction-picker__emoji\"\n\t\t\t\t\t:class=\"{ 'reaction-picker__emoji--selected': selectedEmojis.includes(emoji) }\"\n\t\t\t\t\t:title=\"emoji\"\n\t\t\t\t\t@click=\"selectEmoji(emoji)\">\n\t\t\t\t\t{{ emoji }}\n\t\t\t\t</button>\n\t\t\t</div>\n\t\t</div>\n\t</NcPopover>\n</template>\n\n<script>\nimport { NcButton, NcPopover } from '@nextcloud/vue'\nimport Plus from 'vue-material-design-icons/Plus.vue'\n\nexport default {\n\tname: 'ReactionPicker',\n\tcomponents: {\n\t\tNcButton,\n\t\tNcPopover,\n\t\tPlus,\n\t},\n\tprops: {\n\t\tvisible: {\n\t\t\ttype: Boolean,\n\t\t\tdefault: false,\n\t\t},\n\t\tselectedEmojis: {\n\t\t\ttype: Array,\n\t\t\tdefault: () => [],\n\t\t},\n\t},\n\temits: ['update:visible', 'select'],\n\tdata() {\n\t\treturn {\n\t\t\t// Common emoji reactions for quick access\n\t\t\tcommonEmojis: [\n\t\t\t\t'👍', '👎', '❤️', '🎉', '😊', '😢',\n\t\t\t\t'😮', '😂', '🤔', '👏', '🙏', '💪',\n\t\t\t\t'✅', '⭐', '🔥', '💯', '👀', '🚀',\n\t\t\t],\n\t\t}\n\t},\n\tmethods: {\n\t\tselectEmoji(emoji) {\n\t\t\tthis.$emit('select', emoji)\n\t\t\tthis.$emit('update:visible', false)\n\t\t},\n\t\tt(app, str) {\n\t\t\treturn window.t ? window.t(app, str) : str\n\t\t},\n\t},\n}\n</script>\n\n<style scoped>\n.reaction-picker {\n\tpadding: 8px;\n}\n\n.reaction-picker__grid {\n\tdisplay: grid;\n\tgrid-template-columns: repeat(6, 1fr);\n\tgap: 4px;\n}\n\n.reaction-picker__emoji {\n\tdisplay: flex;\n\talign-items: center;\n\tjustify-content: center;\n\twidth: 36px;\n\theight: 36px;\n\tfont-size: 20px;\n\tborder: none;\n\tbackground: transparent;\n\tborder-radius: var(--border-radius);\n\tcursor: pointer;\n\ttransition: background-color 0.1s ease;\n}\n\n.reaction-picker__emoji:hover {\n\tbackground-color: var(--color-background-hover);\n}\n\n.reaction-picker__emoji--selected {\n\tbackground-color: var(--color-primary-element-light);\n}\n</style>\n"],"sourceRoot":""}]);
// Exports
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (___CSS_LOADER_EXPORT___);


/***/ },

/***/ "./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/dist/stylePostLoader.js!./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/App.vue?vue&type=style&index=0&id=7ba5bd90&scoped=true&lang=css"
/*!*********************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/dist/stylePostLoader.js!./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/App.vue?vue&type=style&index=0&id=7ba5bd90&scoped=true&lang=css ***!
  \*********************************************************************************************************************************************************************************************************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! !../node_modules/style-loader/dist/runtime/injectStylesIntoStyleTag.js */ "./node_modules/style-loader/dist/runtime/injectStylesIntoStyleTag.js");
/* harmony import */ var _node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _node_modules_style_loader_dist_runtime_styleDomAPI_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! !../node_modules/style-loader/dist/runtime/styleDomAPI.js */ "./node_modules/style-loader/dist/runtime/styleDomAPI.js");
/* harmony import */ var _node_modules_style_loader_dist_runtime_styleDomAPI_js__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_node_modules_style_loader_dist_runtime_styleDomAPI_js__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _node_modules_style_loader_dist_runtime_insertBySelector_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! !../node_modules/style-loader/dist/runtime/insertBySelector.js */ "./node_modules/style-loader/dist/runtime/insertBySelector.js");
/* harmony import */ var _node_modules_style_loader_dist_runtime_insertBySelector_js__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(_node_modules_style_loader_dist_runtime_insertBySelector_js__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var _node_modules_style_loader_dist_runtime_setAttributesWithoutAttributes_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! !../node_modules/style-loader/dist/runtime/setAttributesWithoutAttributes.js */ "./node_modules/style-loader/dist/runtime/setAttributesWithoutAttributes.js");
/* harmony import */ var _node_modules_style_loader_dist_runtime_setAttributesWithoutAttributes_js__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(_node_modules_style_loader_dist_runtime_setAttributesWithoutAttributes_js__WEBPACK_IMPORTED_MODULE_3__);
/* harmony import */ var _node_modules_style_loader_dist_runtime_insertStyleElement_js__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! !../node_modules/style-loader/dist/runtime/insertStyleElement.js */ "./node_modules/style-loader/dist/runtime/insertStyleElement.js");
/* harmony import */ var _node_modules_style_loader_dist_runtime_insertStyleElement_js__WEBPACK_IMPORTED_MODULE_4___default = /*#__PURE__*/__webpack_require__.n(_node_modules_style_loader_dist_runtime_insertStyleElement_js__WEBPACK_IMPORTED_MODULE_4__);
/* harmony import */ var _node_modules_style_loader_dist_runtime_styleTagTransform_js__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! !../node_modules/style-loader/dist/runtime/styleTagTransform.js */ "./node_modules/style-loader/dist/runtime/styleTagTransform.js");
/* harmony import */ var _node_modules_style_loader_dist_runtime_styleTagTransform_js__WEBPACK_IMPORTED_MODULE_5___default = /*#__PURE__*/__webpack_require__.n(_node_modules_style_loader_dist_runtime_styleTagTransform_js__WEBPACK_IMPORTED_MODULE_5__);
/* harmony import */ var _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_dist_stylePostLoader_js_node_modules_vue_loader_dist_index_js_ruleSet_1_rules_5_use_0_App_vue_vue_type_style_index_0_id_7ba5bd90_scoped_true_lang_css__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! !!../node_modules/css-loader/dist/cjs.js!../node_modules/vue-loader/dist/stylePostLoader.js!../node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./App.vue?vue&type=style&index=0&id=7ba5bd90&scoped=true&lang=css */ "./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/dist/stylePostLoader.js!./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/App.vue?vue&type=style&index=0&id=7ba5bd90&scoped=true&lang=css");

      
      
      
      
      
      
      
      
      

var options = {};

options.styleTagTransform = (_node_modules_style_loader_dist_runtime_styleTagTransform_js__WEBPACK_IMPORTED_MODULE_5___default());
options.setAttributes = (_node_modules_style_loader_dist_runtime_setAttributesWithoutAttributes_js__WEBPACK_IMPORTED_MODULE_3___default());

      options.insert = _node_modules_style_loader_dist_runtime_insertBySelector_js__WEBPACK_IMPORTED_MODULE_2___default().bind(null, "head");
    
options.domAPI = (_node_modules_style_loader_dist_runtime_styleDomAPI_js__WEBPACK_IMPORTED_MODULE_1___default());
options.insertStyleElement = (_node_modules_style_loader_dist_runtime_insertStyleElement_js__WEBPACK_IMPORTED_MODULE_4___default());

var update = _node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0___default()(_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_dist_stylePostLoader_js_node_modules_vue_loader_dist_index_js_ruleSet_1_rules_5_use_0_App_vue_vue_type_style_index_0_id_7ba5bd90_scoped_true_lang_css__WEBPACK_IMPORTED_MODULE_6__["default"], options);




       /* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_dist_stylePostLoader_js_node_modules_vue_loader_dist_index_js_ruleSet_1_rules_5_use_0_App_vue_vue_type_style_index_0_id_7ba5bd90_scoped_true_lang_css__WEBPACK_IMPORTED_MODULE_6__["default"] && _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_dist_stylePostLoader_js_node_modules_vue_loader_dist_index_js_ruleSet_1_rules_5_use_0_App_vue_vue_type_style_index_0_id_7ba5bd90_scoped_true_lang_css__WEBPACK_IMPORTED_MODULE_6__["default"].locals ? _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_dist_stylePostLoader_js_node_modules_vue_loader_dist_index_js_ruleSet_1_rules_5_use_0_App_vue_vue_type_style_index_0_id_7ba5bd90_scoped_true_lang_css__WEBPACK_IMPORTED_MODULE_6__["default"].locals : undefined);


/***/ },

/***/ "./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/dist/stylePostLoader.js!./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/Breadcrumb.vue?vue&type=style&index=0&id=6f46de9a&scoped=true&lang=css"
/*!***************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/dist/stylePostLoader.js!./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/Breadcrumb.vue?vue&type=style&index=0&id=6f46de9a&scoped=true&lang=css ***!
  \***************************************************************************************************************************************************************************************************************************************************************************************************/
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
/* harmony import */ var _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_dist_stylePostLoader_js_node_modules_vue_loader_dist_index_js_ruleSet_1_rules_5_use_0_Breadcrumb_vue_vue_type_style_index_0_id_6f46de9a_scoped_true_lang_css__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! !!../../node_modules/css-loader/dist/cjs.js!../../node_modules/vue-loader/dist/stylePostLoader.js!../../node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./Breadcrumb.vue?vue&type=style&index=0&id=6f46de9a&scoped=true&lang=css */ "./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/dist/stylePostLoader.js!./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/Breadcrumb.vue?vue&type=style&index=0&id=6f46de9a&scoped=true&lang=css");

      
      
      
      
      
      
      
      
      

var options = {};

options.styleTagTransform = (_node_modules_style_loader_dist_runtime_styleTagTransform_js__WEBPACK_IMPORTED_MODULE_5___default());
options.setAttributes = (_node_modules_style_loader_dist_runtime_setAttributesWithoutAttributes_js__WEBPACK_IMPORTED_MODULE_3___default());

      options.insert = _node_modules_style_loader_dist_runtime_insertBySelector_js__WEBPACK_IMPORTED_MODULE_2___default().bind(null, "head");
    
options.domAPI = (_node_modules_style_loader_dist_runtime_styleDomAPI_js__WEBPACK_IMPORTED_MODULE_1___default());
options.insertStyleElement = (_node_modules_style_loader_dist_runtime_insertStyleElement_js__WEBPACK_IMPORTED_MODULE_4___default());

var update = _node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0___default()(_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_dist_stylePostLoader_js_node_modules_vue_loader_dist_index_js_ruleSet_1_rules_5_use_0_Breadcrumb_vue_vue_type_style_index_0_id_6f46de9a_scoped_true_lang_css__WEBPACK_IMPORTED_MODULE_6__["default"], options);




       /* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_dist_stylePostLoader_js_node_modules_vue_loader_dist_index_js_ruleSet_1_rules_5_use_0_Breadcrumb_vue_vue_type_style_index_0_id_6f46de9a_scoped_true_lang_css__WEBPACK_IMPORTED_MODULE_6__["default"] && _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_dist_stylePostLoader_js_node_modules_vue_loader_dist_index_js_ruleSet_1_rules_5_use_0_Breadcrumb_vue_vue_type_style_index_0_id_6f46de9a_scoped_true_lang_css__WEBPACK_IMPORTED_MODULE_6__["default"].locals ? _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_dist_stylePostLoader_js_node_modules_vue_loader_dist_index_js_ruleSet_1_rules_5_use_0_Breadcrumb_vue_vue_type_style_index_0_id_6f46de9a_scoped_true_lang_css__WEBPACK_IMPORTED_MODULE_6__["default"].locals : undefined);


/***/ },

/***/ "./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/dist/stylePostLoader.js!./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/Footer.vue?vue&type=style&index=0&id=40ab164b&scoped=true&lang=css"
/*!***********************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/dist/stylePostLoader.js!./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/Footer.vue?vue&type=style&index=0&id=40ab164b&scoped=true&lang=css ***!
  \***********************************************************************************************************************************************************************************************************************************************************************************************/
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
/* harmony import */ var _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_dist_stylePostLoader_js_node_modules_vue_loader_dist_index_js_ruleSet_1_rules_5_use_0_Footer_vue_vue_type_style_index_0_id_40ab164b_scoped_true_lang_css__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! !!../../node_modules/css-loader/dist/cjs.js!../../node_modules/vue-loader/dist/stylePostLoader.js!../../node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./Footer.vue?vue&type=style&index=0&id=40ab164b&scoped=true&lang=css */ "./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/dist/stylePostLoader.js!./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/Footer.vue?vue&type=style&index=0&id=40ab164b&scoped=true&lang=css");

      
      
      
      
      
      
      
      
      

var options = {};

options.styleTagTransform = (_node_modules_style_loader_dist_runtime_styleTagTransform_js__WEBPACK_IMPORTED_MODULE_5___default());
options.setAttributes = (_node_modules_style_loader_dist_runtime_setAttributesWithoutAttributes_js__WEBPACK_IMPORTED_MODULE_3___default());

      options.insert = _node_modules_style_loader_dist_runtime_insertBySelector_js__WEBPACK_IMPORTED_MODULE_2___default().bind(null, "head");
    
options.domAPI = (_node_modules_style_loader_dist_runtime_styleDomAPI_js__WEBPACK_IMPORTED_MODULE_1___default());
options.insertStyleElement = (_node_modules_style_loader_dist_runtime_insertStyleElement_js__WEBPACK_IMPORTED_MODULE_4___default());

var update = _node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0___default()(_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_dist_stylePostLoader_js_node_modules_vue_loader_dist_index_js_ruleSet_1_rules_5_use_0_Footer_vue_vue_type_style_index_0_id_40ab164b_scoped_true_lang_css__WEBPACK_IMPORTED_MODULE_6__["default"], options);




       /* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_dist_stylePostLoader_js_node_modules_vue_loader_dist_index_js_ruleSet_1_rules_5_use_0_Footer_vue_vue_type_style_index_0_id_40ab164b_scoped_true_lang_css__WEBPACK_IMPORTED_MODULE_6__["default"] && _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_dist_stylePostLoader_js_node_modules_vue_loader_dist_index_js_ruleSet_1_rules_5_use_0_Footer_vue_vue_type_style_index_0_id_40ab164b_scoped_true_lang_css__WEBPACK_IMPORTED_MODULE_6__["default"].locals ? _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_dist_stylePostLoader_js_node_modules_vue_loader_dist_index_js_ruleSet_1_rules_5_use_0_Footer_vue_vue_type_style_index_0_id_40ab164b_scoped_true_lang_css__WEBPACK_IMPORTED_MODULE_6__["default"].locals : undefined);


/***/ },

/***/ "./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/dist/stylePostLoader.js!./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/Navigation.vue?vue&type=style&index=0&id=81440b78&scoped=true&lang=css"
/*!***************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/dist/stylePostLoader.js!./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/Navigation.vue?vue&type=style&index=0&id=81440b78&scoped=true&lang=css ***!
  \***************************************************************************************************************************************************************************************************************************************************************************************************/
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
/* harmony import */ var _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_dist_stylePostLoader_js_node_modules_vue_loader_dist_index_js_ruleSet_1_rules_5_use_0_Navigation_vue_vue_type_style_index_0_id_81440b78_scoped_true_lang_css__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! !!../../node_modules/css-loader/dist/cjs.js!../../node_modules/vue-loader/dist/stylePostLoader.js!../../node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./Navigation.vue?vue&type=style&index=0&id=81440b78&scoped=true&lang=css */ "./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/dist/stylePostLoader.js!./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/Navigation.vue?vue&type=style&index=0&id=81440b78&scoped=true&lang=css");

      
      
      
      
      
      
      
      
      

var options = {};

options.styleTagTransform = (_node_modules_style_loader_dist_runtime_styleTagTransform_js__WEBPACK_IMPORTED_MODULE_5___default());
options.setAttributes = (_node_modules_style_loader_dist_runtime_setAttributesWithoutAttributes_js__WEBPACK_IMPORTED_MODULE_3___default());

      options.insert = _node_modules_style_loader_dist_runtime_insertBySelector_js__WEBPACK_IMPORTED_MODULE_2___default().bind(null, "head");
    
options.domAPI = (_node_modules_style_loader_dist_runtime_styleDomAPI_js__WEBPACK_IMPORTED_MODULE_1___default());
options.insertStyleElement = (_node_modules_style_loader_dist_runtime_insertStyleElement_js__WEBPACK_IMPORTED_MODULE_4___default());

var update = _node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0___default()(_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_dist_stylePostLoader_js_node_modules_vue_loader_dist_index_js_ruleSet_1_rules_5_use_0_Navigation_vue_vue_type_style_index_0_id_81440b78_scoped_true_lang_css__WEBPACK_IMPORTED_MODULE_6__["default"], options);




       /* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_dist_stylePostLoader_js_node_modules_vue_loader_dist_index_js_ruleSet_1_rules_5_use_0_Navigation_vue_vue_type_style_index_0_id_81440b78_scoped_true_lang_css__WEBPACK_IMPORTED_MODULE_6__["default"] && _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_dist_stylePostLoader_js_node_modules_vue_loader_dist_index_js_ruleSet_1_rules_5_use_0_Navigation_vue_vue_type_style_index_0_id_81440b78_scoped_true_lang_css__WEBPACK_IMPORTED_MODULE_6__["default"].locals ? _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_dist_stylePostLoader_js_node_modules_vue_loader_dist_index_js_ruleSet_1_rules_5_use_0_Navigation_vue_vue_type_style_index_0_id_81440b78_scoped_true_lang_css__WEBPACK_IMPORTED_MODULE_6__["default"].locals : undefined);


/***/ },

/***/ "./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/dist/stylePostLoader.js!./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/NoShareDialog.vue?vue&type=style&index=0&id=a9f8e2f4&scoped=true&lang=css"
/*!******************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/dist/stylePostLoader.js!./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/NoShareDialog.vue?vue&type=style&index=0&id=a9f8e2f4&scoped=true&lang=css ***!
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
/* harmony import */ var _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_dist_stylePostLoader_js_node_modules_vue_loader_dist_index_js_ruleSet_1_rules_5_use_0_NoShareDialog_vue_vue_type_style_index_0_id_a9f8e2f4_scoped_true_lang_css__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! !!../../node_modules/css-loader/dist/cjs.js!../../node_modules/vue-loader/dist/stylePostLoader.js!../../node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./NoShareDialog.vue?vue&type=style&index=0&id=a9f8e2f4&scoped=true&lang=css */ "./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/dist/stylePostLoader.js!./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/NoShareDialog.vue?vue&type=style&index=0&id=a9f8e2f4&scoped=true&lang=css");

      
      
      
      
      
      
      
      
      

var options = {};

options.styleTagTransform = (_node_modules_style_loader_dist_runtime_styleTagTransform_js__WEBPACK_IMPORTED_MODULE_5___default());
options.setAttributes = (_node_modules_style_loader_dist_runtime_setAttributesWithoutAttributes_js__WEBPACK_IMPORTED_MODULE_3___default());

      options.insert = _node_modules_style_loader_dist_runtime_insertBySelector_js__WEBPACK_IMPORTED_MODULE_2___default().bind(null, "head");
    
options.domAPI = (_node_modules_style_loader_dist_runtime_styleDomAPI_js__WEBPACK_IMPORTED_MODULE_1___default());
options.insertStyleElement = (_node_modules_style_loader_dist_runtime_insertStyleElement_js__WEBPACK_IMPORTED_MODULE_4___default());

var update = _node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0___default()(_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_dist_stylePostLoader_js_node_modules_vue_loader_dist_index_js_ruleSet_1_rules_5_use_0_NoShareDialog_vue_vue_type_style_index_0_id_a9f8e2f4_scoped_true_lang_css__WEBPACK_IMPORTED_MODULE_6__["default"], options);




       /* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_dist_stylePostLoader_js_node_modules_vue_loader_dist_index_js_ruleSet_1_rules_5_use_0_NoShareDialog_vue_vue_type_style_index_0_id_a9f8e2f4_scoped_true_lang_css__WEBPACK_IMPORTED_MODULE_6__["default"] && _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_dist_stylePostLoader_js_node_modules_vue_loader_dist_index_js_ruleSet_1_rules_5_use_0_NoShareDialog_vue_vue_type_style_index_0_id_a9f8e2f4_scoped_true_lang_css__WEBPACK_IMPORTED_MODULE_6__["default"].locals ? _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_dist_stylePostLoader_js_node_modules_vue_loader_dist_index_js_ruleSet_1_rules_5_use_0_NoShareDialog_vue_vue_type_style_index_0_id_a9f8e2f4_scoped_true_lang_css__WEBPACK_IMPORTED_MODULE_6__["default"].locals : undefined);


/***/ },

/***/ "./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/dist/stylePostLoader.js!./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/PageActionsMenu.vue?vue&type=style&index=0&id=457bfd6d&scoped=true&lang=css"
/*!********************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/dist/stylePostLoader.js!./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/PageActionsMenu.vue?vue&type=style&index=0&id=457bfd6d&scoped=true&lang=css ***!
  \********************************************************************************************************************************************************************************************************************************************************************************************************/
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
/* harmony import */ var _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_dist_stylePostLoader_js_node_modules_vue_loader_dist_index_js_ruleSet_1_rules_5_use_0_PageActionsMenu_vue_vue_type_style_index_0_id_457bfd6d_scoped_true_lang_css__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! !!../../node_modules/css-loader/dist/cjs.js!../../node_modules/vue-loader/dist/stylePostLoader.js!../../node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./PageActionsMenu.vue?vue&type=style&index=0&id=457bfd6d&scoped=true&lang=css */ "./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/dist/stylePostLoader.js!./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/PageActionsMenu.vue?vue&type=style&index=0&id=457bfd6d&scoped=true&lang=css");

      
      
      
      
      
      
      
      
      

var options = {};

options.styleTagTransform = (_node_modules_style_loader_dist_runtime_styleTagTransform_js__WEBPACK_IMPORTED_MODULE_5___default());
options.setAttributes = (_node_modules_style_loader_dist_runtime_setAttributesWithoutAttributes_js__WEBPACK_IMPORTED_MODULE_3___default());

      options.insert = _node_modules_style_loader_dist_runtime_insertBySelector_js__WEBPACK_IMPORTED_MODULE_2___default().bind(null, "head");
    
options.domAPI = (_node_modules_style_loader_dist_runtime_styleDomAPI_js__WEBPACK_IMPORTED_MODULE_1___default());
options.insertStyleElement = (_node_modules_style_loader_dist_runtime_insertStyleElement_js__WEBPACK_IMPORTED_MODULE_4___default());

var update = _node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0___default()(_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_dist_stylePostLoader_js_node_modules_vue_loader_dist_index_js_ruleSet_1_rules_5_use_0_PageActionsMenu_vue_vue_type_style_index_0_id_457bfd6d_scoped_true_lang_css__WEBPACK_IMPORTED_MODULE_6__["default"], options);




       /* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_dist_stylePostLoader_js_node_modules_vue_loader_dist_index_js_ruleSet_1_rules_5_use_0_PageActionsMenu_vue_vue_type_style_index_0_id_457bfd6d_scoped_true_lang_css__WEBPACK_IMPORTED_MODULE_6__["default"] && _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_dist_stylePostLoader_js_node_modules_vue_loader_dist_index_js_ruleSet_1_rules_5_use_0_PageActionsMenu_vue_vue_type_style_index_0_id_457bfd6d_scoped_true_lang_css__WEBPACK_IMPORTED_MODULE_6__["default"].locals ? _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_dist_stylePostLoader_js_node_modules_vue_loader_dist_index_js_ruleSet_1_rules_5_use_0_PageActionsMenu_vue_vue_type_style_index_0_id_457bfd6d_scoped_true_lang_css__WEBPACK_IMPORTED_MODULE_6__["default"].locals : undefined);


/***/ },

/***/ "./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/dist/stylePostLoader.js!./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/PageViewer.vue?vue&type=style&index=0&id=3a22e99e&scoped=true&lang=css"
/*!***************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/dist/stylePostLoader.js!./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/PageViewer.vue?vue&type=style&index=0&id=3a22e99e&scoped=true&lang=css ***!
  \***************************************************************************************************************************************************************************************************************************************************************************************************/
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
/* harmony import */ var _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_dist_stylePostLoader_js_node_modules_vue_loader_dist_index_js_ruleSet_1_rules_5_use_0_PageViewer_vue_vue_type_style_index_0_id_3a22e99e_scoped_true_lang_css__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! !!../../node_modules/css-loader/dist/cjs.js!../../node_modules/vue-loader/dist/stylePostLoader.js!../../node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./PageViewer.vue?vue&type=style&index=0&id=3a22e99e&scoped=true&lang=css */ "./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/dist/stylePostLoader.js!./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/PageViewer.vue?vue&type=style&index=0&id=3a22e99e&scoped=true&lang=css");

      
      
      
      
      
      
      
      
      

var options = {};

options.styleTagTransform = (_node_modules_style_loader_dist_runtime_styleTagTransform_js__WEBPACK_IMPORTED_MODULE_5___default());
options.setAttributes = (_node_modules_style_loader_dist_runtime_setAttributesWithoutAttributes_js__WEBPACK_IMPORTED_MODULE_3___default());

      options.insert = _node_modules_style_loader_dist_runtime_insertBySelector_js__WEBPACK_IMPORTED_MODULE_2___default().bind(null, "head");
    
options.domAPI = (_node_modules_style_loader_dist_runtime_styleDomAPI_js__WEBPACK_IMPORTED_MODULE_1___default());
options.insertStyleElement = (_node_modules_style_loader_dist_runtime_insertStyleElement_js__WEBPACK_IMPORTED_MODULE_4___default());

var update = _node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0___default()(_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_dist_stylePostLoader_js_node_modules_vue_loader_dist_index_js_ruleSet_1_rules_5_use_0_PageViewer_vue_vue_type_style_index_0_id_3a22e99e_scoped_true_lang_css__WEBPACK_IMPORTED_MODULE_6__["default"], options);




       /* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_dist_stylePostLoader_js_node_modules_vue_loader_dist_index_js_ruleSet_1_rules_5_use_0_PageViewer_vue_vue_type_style_index_0_id_3a22e99e_scoped_true_lang_css__WEBPACK_IMPORTED_MODULE_6__["default"] && _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_dist_stylePostLoader_js_node_modules_vue_loader_dist_index_js_ruleSet_1_rules_5_use_0_PageViewer_vue_vue_type_style_index_0_id_3a22e99e_scoped_true_lang_css__WEBPACK_IMPORTED_MODULE_6__["default"].locals ? _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_dist_stylePostLoader_js_node_modules_vue_loader_dist_index_js_ruleSet_1_rules_5_use_0_PageViewer_vue_vue_type_style_index_0_id_3a22e99e_scoped_true_lang_css__WEBPACK_IMPORTED_MODULE_6__["default"].locals : undefined);


/***/ },

/***/ "./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/dist/stylePostLoader.js!./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/PublicPageView.vue?vue&type=style&index=0&id=5157670d&scoped=true&lang=css"
/*!*******************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/dist/stylePostLoader.js!./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/PublicPageView.vue?vue&type=style&index=0&id=5157670d&scoped=true&lang=css ***!
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
/* harmony import */ var _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_dist_stylePostLoader_js_node_modules_vue_loader_dist_index_js_ruleSet_1_rules_5_use_0_PublicPageView_vue_vue_type_style_index_0_id_5157670d_scoped_true_lang_css__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! !!../../node_modules/css-loader/dist/cjs.js!../../node_modules/vue-loader/dist/stylePostLoader.js!../../node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./PublicPageView.vue?vue&type=style&index=0&id=5157670d&scoped=true&lang=css */ "./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/dist/stylePostLoader.js!./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/PublicPageView.vue?vue&type=style&index=0&id=5157670d&scoped=true&lang=css");

      
      
      
      
      
      
      
      
      

var options = {};

options.styleTagTransform = (_node_modules_style_loader_dist_runtime_styleTagTransform_js__WEBPACK_IMPORTED_MODULE_5___default());
options.setAttributes = (_node_modules_style_loader_dist_runtime_setAttributesWithoutAttributes_js__WEBPACK_IMPORTED_MODULE_3___default());

      options.insert = _node_modules_style_loader_dist_runtime_insertBySelector_js__WEBPACK_IMPORTED_MODULE_2___default().bind(null, "head");
    
options.domAPI = (_node_modules_style_loader_dist_runtime_styleDomAPI_js__WEBPACK_IMPORTED_MODULE_1___default());
options.insertStyleElement = (_node_modules_style_loader_dist_runtime_insertStyleElement_js__WEBPACK_IMPORTED_MODULE_4___default());

var update = _node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0___default()(_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_dist_stylePostLoader_js_node_modules_vue_loader_dist_index_js_ruleSet_1_rules_5_use_0_PublicPageView_vue_vue_type_style_index_0_id_5157670d_scoped_true_lang_css__WEBPACK_IMPORTED_MODULE_6__["default"], options);




       /* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_dist_stylePostLoader_js_node_modules_vue_loader_dist_index_js_ruleSet_1_rules_5_use_0_PublicPageView_vue_vue_type_style_index_0_id_5157670d_scoped_true_lang_css__WEBPACK_IMPORTED_MODULE_6__["default"] && _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_dist_stylePostLoader_js_node_modules_vue_loader_dist_index_js_ruleSet_1_rules_5_use_0_PublicPageView_vue_vue_type_style_index_0_id_5157670d_scoped_true_lang_css__WEBPACK_IMPORTED_MODULE_6__["default"].locals ? _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_dist_stylePostLoader_js_node_modules_vue_loader_dist_index_js_ruleSet_1_rules_5_use_0_PublicPageView_vue_vue_type_style_index_0_id_5157670d_scoped_true_lang_css__WEBPACK_IMPORTED_MODULE_6__["default"].locals : undefined);


/***/ },

/***/ "./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/dist/stylePostLoader.js!./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/ShareButton.vue?vue&type=style&index=0&id=29169d71&scoped=true&lang=css"
/*!****************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/dist/stylePostLoader.js!./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/ShareButton.vue?vue&type=style&index=0&id=29169d71&scoped=true&lang=css ***!
  \****************************************************************************************************************************************************************************************************************************************************************************************************/
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
/* harmony import */ var _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_dist_stylePostLoader_js_node_modules_vue_loader_dist_index_js_ruleSet_1_rules_5_use_0_ShareButton_vue_vue_type_style_index_0_id_29169d71_scoped_true_lang_css__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! !!../../node_modules/css-loader/dist/cjs.js!../../node_modules/vue-loader/dist/stylePostLoader.js!../../node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./ShareButton.vue?vue&type=style&index=0&id=29169d71&scoped=true&lang=css */ "./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/dist/stylePostLoader.js!./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/ShareButton.vue?vue&type=style&index=0&id=29169d71&scoped=true&lang=css");

      
      
      
      
      
      
      
      
      

var options = {};

options.styleTagTransform = (_node_modules_style_loader_dist_runtime_styleTagTransform_js__WEBPACK_IMPORTED_MODULE_5___default());
options.setAttributes = (_node_modules_style_loader_dist_runtime_setAttributesWithoutAttributes_js__WEBPACK_IMPORTED_MODULE_3___default());

      options.insert = _node_modules_style_loader_dist_runtime_insertBySelector_js__WEBPACK_IMPORTED_MODULE_2___default().bind(null, "head");
    
options.domAPI = (_node_modules_style_loader_dist_runtime_styleDomAPI_js__WEBPACK_IMPORTED_MODULE_1___default());
options.insertStyleElement = (_node_modules_style_loader_dist_runtime_insertStyleElement_js__WEBPACK_IMPORTED_MODULE_4___default());

var update = _node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0___default()(_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_dist_stylePostLoader_js_node_modules_vue_loader_dist_index_js_ruleSet_1_rules_5_use_0_ShareButton_vue_vue_type_style_index_0_id_29169d71_scoped_true_lang_css__WEBPACK_IMPORTED_MODULE_6__["default"], options);




       /* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_dist_stylePostLoader_js_node_modules_vue_loader_dist_index_js_ruleSet_1_rules_5_use_0_ShareButton_vue_vue_type_style_index_0_id_29169d71_scoped_true_lang_css__WEBPACK_IMPORTED_MODULE_6__["default"] && _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_dist_stylePostLoader_js_node_modules_vue_loader_dist_index_js_ruleSet_1_rules_5_use_0_ShareButton_vue_vue_type_style_index_0_id_29169d71_scoped_true_lang_css__WEBPACK_IMPORTED_MODULE_6__["default"].locals ? _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_dist_stylePostLoader_js_node_modules_vue_loader_dist_index_js_ruleSet_1_rules_5_use_0_ShareButton_vue_vue_type_style_index_0_id_29169d71_scoped_true_lang_css__WEBPACK_IMPORTED_MODULE_6__["default"].locals : undefined);


/***/ },

/***/ "./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/dist/stylePostLoader.js!./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/ShareDialog.vue?vue&type=style&index=0&id=7dad0ef2&scoped=true&lang=css"
/*!****************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/dist/stylePostLoader.js!./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/ShareDialog.vue?vue&type=style&index=0&id=7dad0ef2&scoped=true&lang=css ***!
  \****************************************************************************************************************************************************************************************************************************************************************************************************/
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
/* harmony import */ var _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_dist_stylePostLoader_js_node_modules_vue_loader_dist_index_js_ruleSet_1_rules_5_use_0_ShareDialog_vue_vue_type_style_index_0_id_7dad0ef2_scoped_true_lang_css__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! !!../../node_modules/css-loader/dist/cjs.js!../../node_modules/vue-loader/dist/stylePostLoader.js!../../node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./ShareDialog.vue?vue&type=style&index=0&id=7dad0ef2&scoped=true&lang=css */ "./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/dist/stylePostLoader.js!./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/ShareDialog.vue?vue&type=style&index=0&id=7dad0ef2&scoped=true&lang=css");

      
      
      
      
      
      
      
      
      

var options = {};

options.styleTagTransform = (_node_modules_style_loader_dist_runtime_styleTagTransform_js__WEBPACK_IMPORTED_MODULE_5___default());
options.setAttributes = (_node_modules_style_loader_dist_runtime_setAttributesWithoutAttributes_js__WEBPACK_IMPORTED_MODULE_3___default());

      options.insert = _node_modules_style_loader_dist_runtime_insertBySelector_js__WEBPACK_IMPORTED_MODULE_2___default().bind(null, "head");
    
options.domAPI = (_node_modules_style_loader_dist_runtime_styleDomAPI_js__WEBPACK_IMPORTED_MODULE_1___default());
options.insertStyleElement = (_node_modules_style_loader_dist_runtime_insertStyleElement_js__WEBPACK_IMPORTED_MODULE_4___default());

var update = _node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0___default()(_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_dist_stylePostLoader_js_node_modules_vue_loader_dist_index_js_ruleSet_1_rules_5_use_0_ShareDialog_vue_vue_type_style_index_0_id_7dad0ef2_scoped_true_lang_css__WEBPACK_IMPORTED_MODULE_6__["default"], options);




       /* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_dist_stylePostLoader_js_node_modules_vue_loader_dist_index_js_ruleSet_1_rules_5_use_0_ShareDialog_vue_vue_type_style_index_0_id_7dad0ef2_scoped_true_lang_css__WEBPACK_IMPORTED_MODULE_6__["default"] && _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_dist_stylePostLoader_js_node_modules_vue_loader_dist_index_js_ruleSet_1_rules_5_use_0_ShareDialog_vue_vue_type_style_index_0_id_7dad0ef2_scoped_true_lang_css__WEBPACK_IMPORTED_MODULE_6__["default"].locals ? _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_dist_stylePostLoader_js_node_modules_vue_loader_dist_index_js_ruleSet_1_rules_5_use_0_ShareDialog_vue_vue_type_style_index_0_id_7dad0ef2_scoped_true_lang_css__WEBPACK_IMPORTED_MODULE_6__["default"].locals : undefined);


/***/ },

/***/ "./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/dist/stylePostLoader.js!./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/Widget.vue?vue&type=style&index=0&id=4ca18318&scoped=true&lang=css"
/*!***********************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/dist/stylePostLoader.js!./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/Widget.vue?vue&type=style&index=0&id=4ca18318&scoped=true&lang=css ***!
  \***********************************************************************************************************************************************************************************************************************************************************************************************/
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
/* harmony import */ var _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_dist_stylePostLoader_js_node_modules_vue_loader_dist_index_js_ruleSet_1_rules_5_use_0_Widget_vue_vue_type_style_index_0_id_4ca18318_scoped_true_lang_css__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! !!../../node_modules/css-loader/dist/cjs.js!../../node_modules/vue-loader/dist/stylePostLoader.js!../../node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./Widget.vue?vue&type=style&index=0&id=4ca18318&scoped=true&lang=css */ "./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/dist/stylePostLoader.js!./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/Widget.vue?vue&type=style&index=0&id=4ca18318&scoped=true&lang=css");

      
      
      
      
      
      
      
      
      

var options = {};

options.styleTagTransform = (_node_modules_style_loader_dist_runtime_styleTagTransform_js__WEBPACK_IMPORTED_MODULE_5___default());
options.setAttributes = (_node_modules_style_loader_dist_runtime_setAttributesWithoutAttributes_js__WEBPACK_IMPORTED_MODULE_3___default());

      options.insert = _node_modules_style_loader_dist_runtime_insertBySelector_js__WEBPACK_IMPORTED_MODULE_2___default().bind(null, "head");
    
options.domAPI = (_node_modules_style_loader_dist_runtime_styleDomAPI_js__WEBPACK_IMPORTED_MODULE_1___default());
options.insertStyleElement = (_node_modules_style_loader_dist_runtime_insertStyleElement_js__WEBPACK_IMPORTED_MODULE_4___default());

var update = _node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0___default()(_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_dist_stylePostLoader_js_node_modules_vue_loader_dist_index_js_ruleSet_1_rules_5_use_0_Widget_vue_vue_type_style_index_0_id_4ca18318_scoped_true_lang_css__WEBPACK_IMPORTED_MODULE_6__["default"], options);




       /* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_dist_stylePostLoader_js_node_modules_vue_loader_dist_index_js_ruleSet_1_rules_5_use_0_Widget_vue_vue_type_style_index_0_id_4ca18318_scoped_true_lang_css__WEBPACK_IMPORTED_MODULE_6__["default"] && _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_dist_stylePostLoader_js_node_modules_vue_loader_dist_index_js_ruleSet_1_rules_5_use_0_Widget_vue_vue_type_style_index_0_id_4ca18318_scoped_true_lang_css__WEBPACK_IMPORTED_MODULE_6__["default"].locals ? _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_dist_stylePostLoader_js_node_modules_vue_loader_dist_index_js_ruleSet_1_rules_5_use_0_Widget_vue_vue_type_style_index_0_id_4ca18318_scoped_true_lang_css__WEBPACK_IMPORTED_MODULE_6__["default"].locals : undefined);


/***/ },

/***/ "./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/dist/stylePostLoader.js!./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/reactions/CommentItem.vue?vue&type=style&index=0&id=6211c46d&scoped=true&lang=css"
/*!**************************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/dist/stylePostLoader.js!./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/reactions/CommentItem.vue?vue&type=style&index=0&id=6211c46d&scoped=true&lang=css ***!
  \**************************************************************************************************************************************************************************************************************************************************************************************************************/
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
/* harmony import */ var _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_dist_stylePostLoader_js_node_modules_vue_loader_dist_index_js_ruleSet_1_rules_5_use_0_CommentItem_vue_vue_type_style_index_0_id_6211c46d_scoped_true_lang_css__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! !!../../../node_modules/css-loader/dist/cjs.js!../../../node_modules/vue-loader/dist/stylePostLoader.js!../../../node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./CommentItem.vue?vue&type=style&index=0&id=6211c46d&scoped=true&lang=css */ "./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/dist/stylePostLoader.js!./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/reactions/CommentItem.vue?vue&type=style&index=0&id=6211c46d&scoped=true&lang=css");

      
      
      
      
      
      
      
      
      

var options = {};

options.styleTagTransform = (_node_modules_style_loader_dist_runtime_styleTagTransform_js__WEBPACK_IMPORTED_MODULE_5___default());
options.setAttributes = (_node_modules_style_loader_dist_runtime_setAttributesWithoutAttributes_js__WEBPACK_IMPORTED_MODULE_3___default());

      options.insert = _node_modules_style_loader_dist_runtime_insertBySelector_js__WEBPACK_IMPORTED_MODULE_2___default().bind(null, "head");
    
options.domAPI = (_node_modules_style_loader_dist_runtime_styleDomAPI_js__WEBPACK_IMPORTED_MODULE_1___default());
options.insertStyleElement = (_node_modules_style_loader_dist_runtime_insertStyleElement_js__WEBPACK_IMPORTED_MODULE_4___default());

var update = _node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0___default()(_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_dist_stylePostLoader_js_node_modules_vue_loader_dist_index_js_ruleSet_1_rules_5_use_0_CommentItem_vue_vue_type_style_index_0_id_6211c46d_scoped_true_lang_css__WEBPACK_IMPORTED_MODULE_6__["default"], options);




       /* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_dist_stylePostLoader_js_node_modules_vue_loader_dist_index_js_ruleSet_1_rules_5_use_0_CommentItem_vue_vue_type_style_index_0_id_6211c46d_scoped_true_lang_css__WEBPACK_IMPORTED_MODULE_6__["default"] && _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_dist_stylePostLoader_js_node_modules_vue_loader_dist_index_js_ruleSet_1_rules_5_use_0_CommentItem_vue_vue_type_style_index_0_id_6211c46d_scoped_true_lang_css__WEBPACK_IMPORTED_MODULE_6__["default"].locals ? _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_dist_stylePostLoader_js_node_modules_vue_loader_dist_index_js_ruleSet_1_rules_5_use_0_CommentItem_vue_vue_type_style_index_0_id_6211c46d_scoped_true_lang_css__WEBPACK_IMPORTED_MODULE_6__["default"].locals : undefined);


/***/ },

/***/ "./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/dist/stylePostLoader.js!./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/reactions/CommentSection.vue?vue&type=style&index=0&id=7504410a&scoped=true&lang=css"
/*!*****************************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/dist/stylePostLoader.js!./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/reactions/CommentSection.vue?vue&type=style&index=0&id=7504410a&scoped=true&lang=css ***!
  \*****************************************************************************************************************************************************************************************************************************************************************************************************************/
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
/* harmony import */ var _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_dist_stylePostLoader_js_node_modules_vue_loader_dist_index_js_ruleSet_1_rules_5_use_0_CommentSection_vue_vue_type_style_index_0_id_7504410a_scoped_true_lang_css__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! !!../../../node_modules/css-loader/dist/cjs.js!../../../node_modules/vue-loader/dist/stylePostLoader.js!../../../node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./CommentSection.vue?vue&type=style&index=0&id=7504410a&scoped=true&lang=css */ "./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/dist/stylePostLoader.js!./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/reactions/CommentSection.vue?vue&type=style&index=0&id=7504410a&scoped=true&lang=css");

      
      
      
      
      
      
      
      
      

var options = {};

options.styleTagTransform = (_node_modules_style_loader_dist_runtime_styleTagTransform_js__WEBPACK_IMPORTED_MODULE_5___default());
options.setAttributes = (_node_modules_style_loader_dist_runtime_setAttributesWithoutAttributes_js__WEBPACK_IMPORTED_MODULE_3___default());

      options.insert = _node_modules_style_loader_dist_runtime_insertBySelector_js__WEBPACK_IMPORTED_MODULE_2___default().bind(null, "head");
    
options.domAPI = (_node_modules_style_loader_dist_runtime_styleDomAPI_js__WEBPACK_IMPORTED_MODULE_1___default());
options.insertStyleElement = (_node_modules_style_loader_dist_runtime_insertStyleElement_js__WEBPACK_IMPORTED_MODULE_4___default());

var update = _node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0___default()(_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_dist_stylePostLoader_js_node_modules_vue_loader_dist_index_js_ruleSet_1_rules_5_use_0_CommentSection_vue_vue_type_style_index_0_id_7504410a_scoped_true_lang_css__WEBPACK_IMPORTED_MODULE_6__["default"], options);




       /* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_dist_stylePostLoader_js_node_modules_vue_loader_dist_index_js_ruleSet_1_rules_5_use_0_CommentSection_vue_vue_type_style_index_0_id_7504410a_scoped_true_lang_css__WEBPACK_IMPORTED_MODULE_6__["default"] && _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_dist_stylePostLoader_js_node_modules_vue_loader_dist_index_js_ruleSet_1_rules_5_use_0_CommentSection_vue_vue_type_style_index_0_id_7504410a_scoped_true_lang_css__WEBPACK_IMPORTED_MODULE_6__["default"].locals ? _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_dist_stylePostLoader_js_node_modules_vue_loader_dist_index_js_ruleSet_1_rules_5_use_0_CommentSection_vue_vue_type_style_index_0_id_7504410a_scoped_true_lang_css__WEBPACK_IMPORTED_MODULE_6__["default"].locals : undefined);


/***/ },

/***/ "./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/dist/stylePostLoader.js!./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/reactions/ReactionBar.vue?vue&type=style&index=0&id=0ed66f85&scoped=true&lang=css"
/*!**************************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/dist/stylePostLoader.js!./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/reactions/ReactionBar.vue?vue&type=style&index=0&id=0ed66f85&scoped=true&lang=css ***!
  \**************************************************************************************************************************************************************************************************************************************************************************************************************/
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
/* harmony import */ var _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_dist_stylePostLoader_js_node_modules_vue_loader_dist_index_js_ruleSet_1_rules_5_use_0_ReactionBar_vue_vue_type_style_index_0_id_0ed66f85_scoped_true_lang_css__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! !!../../../node_modules/css-loader/dist/cjs.js!../../../node_modules/vue-loader/dist/stylePostLoader.js!../../../node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./ReactionBar.vue?vue&type=style&index=0&id=0ed66f85&scoped=true&lang=css */ "./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/dist/stylePostLoader.js!./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/reactions/ReactionBar.vue?vue&type=style&index=0&id=0ed66f85&scoped=true&lang=css");

      
      
      
      
      
      
      
      
      

var options = {};

options.styleTagTransform = (_node_modules_style_loader_dist_runtime_styleTagTransform_js__WEBPACK_IMPORTED_MODULE_5___default());
options.setAttributes = (_node_modules_style_loader_dist_runtime_setAttributesWithoutAttributes_js__WEBPACK_IMPORTED_MODULE_3___default());

      options.insert = _node_modules_style_loader_dist_runtime_insertBySelector_js__WEBPACK_IMPORTED_MODULE_2___default().bind(null, "head");
    
options.domAPI = (_node_modules_style_loader_dist_runtime_styleDomAPI_js__WEBPACK_IMPORTED_MODULE_1___default());
options.insertStyleElement = (_node_modules_style_loader_dist_runtime_insertStyleElement_js__WEBPACK_IMPORTED_MODULE_4___default());

var update = _node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0___default()(_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_dist_stylePostLoader_js_node_modules_vue_loader_dist_index_js_ruleSet_1_rules_5_use_0_ReactionBar_vue_vue_type_style_index_0_id_0ed66f85_scoped_true_lang_css__WEBPACK_IMPORTED_MODULE_6__["default"], options);




       /* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_dist_stylePostLoader_js_node_modules_vue_loader_dist_index_js_ruleSet_1_rules_5_use_0_ReactionBar_vue_vue_type_style_index_0_id_0ed66f85_scoped_true_lang_css__WEBPACK_IMPORTED_MODULE_6__["default"] && _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_dist_stylePostLoader_js_node_modules_vue_loader_dist_index_js_ruleSet_1_rules_5_use_0_ReactionBar_vue_vue_type_style_index_0_id_0ed66f85_scoped_true_lang_css__WEBPACK_IMPORTED_MODULE_6__["default"].locals ? _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_dist_stylePostLoader_js_node_modules_vue_loader_dist_index_js_ruleSet_1_rules_5_use_0_ReactionBar_vue_vue_type_style_index_0_id_0ed66f85_scoped_true_lang_css__WEBPACK_IMPORTED_MODULE_6__["default"].locals : undefined);


/***/ },

/***/ "./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/dist/stylePostLoader.js!./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/reactions/ReactionPicker.vue?vue&type=style&index=0&id=11a9eb8c&scoped=true&lang=css"
/*!*****************************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/dist/stylePostLoader.js!./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/reactions/ReactionPicker.vue?vue&type=style&index=0&id=11a9eb8c&scoped=true&lang=css ***!
  \*****************************************************************************************************************************************************************************************************************************************************************************************************************/
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
/* harmony import */ var _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_dist_stylePostLoader_js_node_modules_vue_loader_dist_index_js_ruleSet_1_rules_5_use_0_ReactionPicker_vue_vue_type_style_index_0_id_11a9eb8c_scoped_true_lang_css__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! !!../../../node_modules/css-loader/dist/cjs.js!../../../node_modules/vue-loader/dist/stylePostLoader.js!../../../node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./ReactionPicker.vue?vue&type=style&index=0&id=11a9eb8c&scoped=true&lang=css */ "./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/dist/stylePostLoader.js!./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/reactions/ReactionPicker.vue?vue&type=style&index=0&id=11a9eb8c&scoped=true&lang=css");

      
      
      
      
      
      
      
      
      

var options = {};

options.styleTagTransform = (_node_modules_style_loader_dist_runtime_styleTagTransform_js__WEBPACK_IMPORTED_MODULE_5___default());
options.setAttributes = (_node_modules_style_loader_dist_runtime_setAttributesWithoutAttributes_js__WEBPACK_IMPORTED_MODULE_3___default());

      options.insert = _node_modules_style_loader_dist_runtime_insertBySelector_js__WEBPACK_IMPORTED_MODULE_2___default().bind(null, "head");
    
options.domAPI = (_node_modules_style_loader_dist_runtime_styleDomAPI_js__WEBPACK_IMPORTED_MODULE_1___default());
options.insertStyleElement = (_node_modules_style_loader_dist_runtime_insertStyleElement_js__WEBPACK_IMPORTED_MODULE_4___default());

var update = _node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0___default()(_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_dist_stylePostLoader_js_node_modules_vue_loader_dist_index_js_ruleSet_1_rules_5_use_0_ReactionPicker_vue_vue_type_style_index_0_id_11a9eb8c_scoped_true_lang_css__WEBPACK_IMPORTED_MODULE_6__["default"], options);




       /* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_dist_stylePostLoader_js_node_modules_vue_loader_dist_index_js_ruleSet_1_rules_5_use_0_ReactionPicker_vue_vue_type_style_index_0_id_11a9eb8c_scoped_true_lang_css__WEBPACK_IMPORTED_MODULE_6__["default"] && _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_dist_stylePostLoader_js_node_modules_vue_loader_dist_index_js_ruleSet_1_rules_5_use_0_ReactionPicker_vue_vue_type_style_index_0_id_11a9eb8c_scoped_true_lang_css__WEBPACK_IMPORTED_MODULE_6__["default"].locals ? _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_dist_stylePostLoader_js_node_modules_vue_loader_dist_index_js_ruleSet_1_rules_5_use_0_ReactionPicker_vue_vue_type_style_index_0_id_11a9eb8c_scoped_true_lang_css__WEBPACK_IMPORTED_MODULE_6__["default"].locals : undefined);


/***/ },

/***/ "./src/App.vue"
/*!*********************!*\
  !*** ./src/App.vue ***!
  \*********************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _App_vue_vue_type_template_id_7ba5bd90_scoped_true__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./App.vue?vue&type=template&id=7ba5bd90&scoped=true */ "./src/App.vue?vue&type=template&id=7ba5bd90&scoped=true");
/* harmony import */ var _App_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./App.vue?vue&type=script&lang=js */ "./src/App.vue?vue&type=script&lang=js");
/* harmony import */ var _App_vue_vue_type_style_index_0_id_7ba5bd90_scoped_true_lang_css__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./App.vue?vue&type=style&index=0&id=7ba5bd90&scoped=true&lang=css */ "./src/App.vue?vue&type=style&index=0&id=7ba5bd90&scoped=true&lang=css");
/* harmony import */ var _node_modules_vue_loader_dist_exportHelper_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ../node_modules/vue-loader/dist/exportHelper.js */ "./node_modules/vue-loader/dist/exportHelper.js");




;


const __exports__ = /*#__PURE__*/(0,_node_modules_vue_loader_dist_exportHelper_js__WEBPACK_IMPORTED_MODULE_3__["default"])(_App_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_1__["default"], [['render',_App_vue_vue_type_template_id_7ba5bd90_scoped_true__WEBPACK_IMPORTED_MODULE_0__.render],['__scopeId',"data-v-7ba5bd90"],['__file',"src/App.vue"]])
/* hot reload */
if (false) // removed by dead control flow
{}


/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (__exports__);

/***/ },

/***/ "./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/App.vue?vue&type=script&lang=js"
/*!*****************************************************************************************************************!*\
  !*** ./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/App.vue?vue&type=script&lang=js ***!
  \*****************************************************************************************************************/
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
/* harmony import */ var vue_material_design_icons_ContentSave_vue__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! vue-material-design-icons/ContentSave.vue */ "./node_modules/vue-material-design-icons/ContentSave.vue");
/* harmony import */ var vue_material_design_icons_Close_vue__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! vue-material-design-icons/Close.vue */ "./node_modules/vue-material-design-icons/Close.vue");
/* harmony import */ var vue_material_design_icons_Eye_vue__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! vue-material-design-icons/Eye.vue */ "./node_modules/vue-material-design-icons/Eye.vue");
/* harmony import */ var vue_material_design_icons_EyeOff_vue__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__(/*! vue-material-design-icons/EyeOff.vue */ "./node_modules/vue-material-design-icons/EyeOff.vue");
/* harmony import */ var vue_material_design_icons_Pencil_vue__WEBPACK_IMPORTED_MODULE_9__ = __webpack_require__(/*! vue-material-design-icons/Pencil.vue */ "./node_modules/vue-material-design-icons/Pencil.vue");
/* harmony import */ var vue_material_design_icons_Information_vue__WEBPACK_IMPORTED_MODULE_10__ = __webpack_require__(/*! vue-material-design-icons/Information.vue */ "./node_modules/vue-material-design-icons/Information.vue");
/* harmony import */ var vue__WEBPACK_IMPORTED_MODULE_11__ = __webpack_require__(/*! vue */ "./node_modules/vue/dist/vue.runtime.esm-bundler.js");
/* harmony import */ var _components_PageViewer_vue__WEBPACK_IMPORTED_MODULE_12__ = __webpack_require__(/*! ./components/PageViewer.vue */ "./src/components/PageViewer.vue");
/* harmony import */ var _components_Navigation_vue__WEBPACK_IMPORTED_MODULE_13__ = __webpack_require__(/*! ./components/Navigation.vue */ "./src/components/Navigation.vue");
/* harmony import */ var _components_Footer_vue__WEBPACK_IMPORTED_MODULE_14__ = __webpack_require__(/*! ./components/Footer.vue */ "./src/components/Footer.vue");
/* harmony import */ var _components_PageActionsMenu_vue__WEBPACK_IMPORTED_MODULE_15__ = __webpack_require__(/*! ./components/PageActionsMenu.vue */ "./src/components/PageActionsMenu.vue");
/* harmony import */ var _components_Breadcrumb_vue__WEBPACK_IMPORTED_MODULE_16__ = __webpack_require__(/*! ./components/Breadcrumb.vue */ "./src/components/Breadcrumb.vue");
/* harmony import */ var _components_ShareButton_vue__WEBPACK_IMPORTED_MODULE_17__ = __webpack_require__(/*! ./components/ShareButton.vue */ "./src/components/ShareButton.vue");
/* harmony import */ var _services_CacheService_js__WEBPACK_IMPORTED_MODULE_18__ = __webpack_require__(/*! ./services/CacheService.js */ "./src/services/CacheService.js");
/* harmony import */ var _metavox_integration_js__WEBPACK_IMPORTED_MODULE_19__ = __webpack_require__(/*! ./metavox-integration.js */ "./src/metavox-integration.js");




















 // Load MetaVox integration

// Lazy-loaded components (only loaded when needed)
// This reduces initial bundle size and improves first load performance
const PageEditor = (0,vue__WEBPACK_IMPORTED_MODULE_11__.defineAsyncComponent)(() => Promise.all(/*! import() */[__webpack_require__.e("vendors"), __webpack_require__.e("shared"), __webpack_require__.e("src_components_PageEditor_vue")]).then(__webpack_require__.bind(__webpack_require__, /*! ./components/PageEditor.vue */ "./src/components/PageEditor.vue")));
const PageListModal = (0,vue__WEBPACK_IMPORTED_MODULE_11__.defineAsyncComponent)(() => __webpack_require__.e(/*! import() */ "src_components_PageListModal_vue").then(__webpack_require__.bind(__webpack_require__, /*! ./components/PageListModal.vue */ "./src/components/PageListModal.vue")));
const PageTreeModal = (0,vue__WEBPACK_IMPORTED_MODULE_11__.defineAsyncComponent)(() => Promise.all(/*! import() */[__webpack_require__.e("vendors"), __webpack_require__.e("src_components_PageTreeModal_vue")]).then(__webpack_require__.bind(__webpack_require__, /*! ./components/PageTreeModal.vue */ "./src/components/PageTreeModal.vue")));
const NewPageModal = (0,vue__WEBPACK_IMPORTED_MODULE_11__.defineAsyncComponent)(() => Promise.all(/*! import() */[__webpack_require__.e("vendors"), __webpack_require__.e("src_components_NewPageModal_vue")]).then(__webpack_require__.bind(__webpack_require__, /*! ./components/NewPageModal.vue */ "./src/components/NewPageModal.vue")));
const NavigationEditor = (0,vue__WEBPACK_IMPORTED_MODULE_11__.defineAsyncComponent)(() => Promise.all(/*! import() */[__webpack_require__.e("vendors"), __webpack_require__.e("shared"), __webpack_require__.e("src_components_NavigationEditor_vue")]).then(__webpack_require__.bind(__webpack_require__, /*! ./components/NavigationEditor.vue */ "./src/components/NavigationEditor.vue")));
const PageDetailsSidebar = (0,vue__WEBPACK_IMPORTED_MODULE_11__.defineAsyncComponent)(() => Promise.all(/*! import() */[__webpack_require__.e("vendors"), __webpack_require__.e("src_components_PageDetailsSidebar_vue")]).then(__webpack_require__.bind(__webpack_require__, /*! ./components/PageDetailsSidebar.vue */ "./src/components/PageDetailsSidebar.vue")));
const WelcomeScreen = (0,vue__WEBPACK_IMPORTED_MODULE_11__.defineAsyncComponent)(() => Promise.all(/*! import() */[__webpack_require__.e("vendors"), __webpack_require__.e("src_components_WelcomeScreen_vue")]).then(__webpack_require__.bind(__webpack_require__, /*! ./components/WelcomeScreen.vue */ "./src/components/WelcomeScreen.vue")));
const PageSettingsModal = (0,vue__WEBPACK_IMPORTED_MODULE_11__.defineAsyncComponent)(() => __webpack_require__.e(/*! import() */ "src_components_PageSettingsModal_vue").then(__webpack_require__.bind(__webpack_require__, /*! ./components/PageSettingsModal.vue */ "./src/components/PageSettingsModal.vue")));
const SaveAsTemplateModal = (0,vue__WEBPACK_IMPORTED_MODULE_11__.defineAsyncComponent)(() => __webpack_require__.e(/*! import() */ "src_components_SaveAsTemplateModal_vue").then(__webpack_require__.bind(__webpack_require__, /*! ./components/SaveAsTemplateModal.vue */ "./src/components/SaveAsTemplateModal.vue")));
const FeedSettings = (0,vue__WEBPACK_IMPORTED_MODULE_11__.defineAsyncComponent)(() => __webpack_require__.e(/*! import() */ "src_components_FeedSettings_vue").then(__webpack_require__.bind(__webpack_require__, /*! ./components/FeedSettings.vue */ "./src/components/FeedSettings.vue")));

// Helper function to find home page
const findHomePage = (pages) => {
  // Try to find page with slug "home" or filename containing "home"
  return pages.find(p => p.slug === 'home' || p.path?.toLowerCase().includes('/home.json')) || pages[0];
};

/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = ({
  name: 'App',
  components: {
    NcButton: _nextcloud_vue__WEBPACK_IMPORTED_MODULE_4__.NcButton,
    ContentSave: vue_material_design_icons_ContentSave_vue__WEBPACK_IMPORTED_MODULE_5__["default"],
    Close: vue_material_design_icons_Close_vue__WEBPACK_IMPORTED_MODULE_6__["default"],
    Eye: vue_material_design_icons_Eye_vue__WEBPACK_IMPORTED_MODULE_7__["default"],
    EyeOff: vue_material_design_icons_EyeOff_vue__WEBPACK_IMPORTED_MODULE_8__["default"],
    Pencil: vue_material_design_icons_Pencil_vue__WEBPACK_IMPORTED_MODULE_9__["default"],
    Information: vue_material_design_icons_Information_vue__WEBPACK_IMPORTED_MODULE_10__["default"],
    PageViewer: _components_PageViewer_vue__WEBPACK_IMPORTED_MODULE_12__["default"],
    PageEditor,
    PageListModal,
    PageTreeModal,
    NewPageModal,
    Navigation: _components_Navigation_vue__WEBPACK_IMPORTED_MODULE_13__["default"],
    NavigationEditor,
    Footer: _components_Footer_vue__WEBPACK_IMPORTED_MODULE_14__["default"],
    PageActionsMenu: _components_PageActionsMenu_vue__WEBPACK_IMPORTED_MODULE_15__["default"],
    PageDetailsSidebar,
    Breadcrumb: _components_Breadcrumb_vue__WEBPACK_IMPORTED_MODULE_16__["default"],
    WelcomeScreen,
    PageSettingsModal,
    SaveAsTemplateModal,
    ShareButton: _components_ShareButton_vue__WEBPACK_IMPORTED_MODULE_17__["default"],
    FeedSettings
  },
  data() {
    return {
      pages: [],
      currentPage: null,
      originalPage: null, // For rollback
      editableTitle: '',
      isEditMode: false,
      loading: true,
      error: null,
      showPages: false,
      showPageTree: false,
      showNewPageModal: false,
      showSaveAsTemplateModal: false,
      showDetailsSidebar: false,
      breadcrumb: [],
      navigation: {
        type: 'dropdown',
        items: []
      },
      canEditNavigation: false,
      showNavigationEditor: false,
      currentLanguage: document.documentElement.lang || 'en',
      footerContent: '',
      canEditFooter: false,
      // Version preview state
      selectedVersion: null,
      versionPage: null,
      loadingVersion: false,
      // Sidebar state
      sidebarInitialTab: 'details-tab',
      // Page settings modal
      showPageSettingsModal: false,
      // RSS Feed settings modal
      showFeedSettings: false,
      // Page locking
      pageLock: null,
      lockHeartbeatTimer: null,
      // Global engagement settings (loaded from API)
      globalEngagementSettings: {
        allowPageReactions: true,
        allowComments: true,
        allowCommentReactions: true,
        singleReactionPerUser: true
      }
    };
  },
  computed: {
    /**
     * Page permissions based on folder-level ACLs from GroupFolder
     * Uses Nextcloud's permission system to determine what the user can do
     * Permissions are retrieved from the API for each page
     */
    pagePermissions() {
      const perms = this.currentPage?.permissions || {};
      return {
        editNavigation: this.canEditNavigation,
        viewPages: perms.canRead !== false,  // Default to true if not specified
        // Creating a page requires both write and create permissions
        createPage: (perms.canWrite && perms.canCreate) || false,
        editPage: perms.canWrite || false,
        deletePage: perms.canDelete || false,
        // Save as template requires read on the page (to copy content)
        // Note: The backend also checks if user can write to _templates folder
        saveAsTemplate: perms.canRead !== false
      };
    },
    /**
     * Helper to check if user can edit the current page
     */
    canEditCurrentPage() {
      return this.currentPage?.permissions?.canWrite || false;
    },
    /**
     * Returns the page to display - either the version preview or the current page
     */
    displayPage() {
      return this.versionPage || this.currentPage;
    },
    /**
     * Check if we're currently showing a version preview
     */
    isShowingVersion() {
      return this.versionPage !== null;
    },
    /**
     * Check if the current page is a home page
     * A page is considered home if it's at the language root level
     * This includes both "nl/home" and "nl" paths
     */
    isCurrentPageHome() {
      if (!this.currentPage || !this.currentPage.path) {
        return false;
      }
      const pathParts = this.currentPage.path.split('/').filter(p => p);

      // Home page can be either:
      // 1. Just language code: "nl" (length === 1)
      // 2. Language + home folder: "nl/home" (length === 2 && last part is "home")
      const isHome = pathParts.length === 1 ||
                     (pathParts.length === 2 && pathParts[1] === 'home');
      return isHome;
    },
    /**
     * Show welcome screen when no pages exist (first-time install)
     */
    showWelcomeScreen() {
      return !this.loading && this.pages.length === 0 && !this.error;
    }
  },
  async mounted() {
    // Load pages, navigation, footer, and settings in parallel
    try {
      await Promise.all([
        this.loadPages(),
        this.loadNavigation(),
        this.loadFooter(),
        this.loadEngagementSettings()
      ]);
    } catch (err) {
      // Errors are handled in individual loaders
    }

    // Setup hash-based navigation
    window.addEventListener('hashchange', this.handleHashChange);

    // Release page lock on tab/window close
    window.addEventListener('beforeunload', this.handleBeforeUnload);

    // Use MutationObserver to watch for HTML lang attribute changes
    // (more efficient than setInterval polling)
    this.langObserver = new MutationObserver(() => {
      const newLanguage = document.documentElement.lang || 'en';
      if (newLanguage !== this.currentLanguage) {
        this.currentLanguage = newLanguage;
        this.handleLanguageChange();
      }
    });

    this.langObserver.observe(document.documentElement, {
      attributes: true,
      attributeFilter: ['lang']
    });
  },
  beforeUnmount() {
    window.removeEventListener('hashchange', this.handleHashChange);
    window.removeEventListener('beforeunload', this.handleBeforeUnload);
    this.stopLockHeartbeat();
    if (this.langObserver) {
      this.langObserver.disconnect();
    }
  },
  methods: {
    t(key, vars = {}) {
      return (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_2__.translate)('intravox', key, vars);
    },
    async loadPages() {
      try {
        // Check cache first
        const cacheKey = 'pages-list';
        const cached = _services_CacheService_js__WEBPACK_IMPORTED_MODULE_18__["default"].get(cacheKey);
        if (cached) {
          this.pages = cached;
          this.loading = false;
          // Continue loading in background to update cache
        } else {
          this.loading = true;
        }

        const response = await _nextcloud_axios__WEBPACK_IMPORTED_MODULE_0__["default"].get((0,_nextcloud_router__WEBPACK_IMPORTED_MODULE_1__.generateUrl)('/apps/intravox/api/pages'));
        this.pages = response.data;

        // Update cache
        _services_CacheService_js__WEBPACK_IMPORTED_MODULE_18__["default"].set('pages-list', response.data);

        if (this.pages.length > 0) {
          // Check if we came from a /p/{uniqueId} URL
          const appElement = document.getElementById('app-intravox');
          const uniqueIdFromUrl = appElement?.dataset.uniqueId;

          // Check URL hash for page to load
          const hash = window.location.hash;
          let targetPage = null;
          let foundInHash = false;

          // Priority 1: uniqueId from URL path
          if (uniqueIdFromUrl) {
            targetPage = this.pages.find(p => p.uniqueId === uniqueIdFromUrl);
            if (targetPage) {
              foundInHash = true;
            }
          }

          // Priority 2: Hash in URL
          if (!targetPage && hash) {
            const pageIdentifier = hash.substring(1); // Remove '#'

            // Try to find page by ID or uniqueId
            targetPage = this.pages.find(p => p.uniqueId === pageIdentifier || p.uniqueId === pageIdentifier);
            if (targetPage) {
              foundInHash = true;
            }
          }

          // Fall back to home page if no hash or page not found
          if (!targetPage) {
            targetPage = findHomePage(this.pages);
          }

          // Validate targetPage has a uniqueId before selecting
          if (!targetPage || !targetPage.uniqueId) {
            console.error('IntraVox: No valid page found to load', { targetPage, pages: this.pages });
            this.error = this.t('No valid pages found. Pages might be missing uniqueId.');
            return;
          }

          // Only update URL if we didn't find the page in the hash (i.e., we're loading default/home)
          await this.selectPage(targetPage.uniqueId, !foundInHash);
        }
        // If no pages found, don't set error - the welcome screen will be shown instead
      } catch (err) {
        console.error('IntraVox: Error loading pages:', err);
        this.error = this.t('Could not load pages: {error}', { error: err.message });
      } finally {
        this.loading = false;
      }
    },
    async selectPage(pageId, updateUrl = true) {
      try {
        // Validate pageId
        if (!pageId || pageId === 'undefined') {
          console.error('IntraVox: Cannot select page with invalid ID:', pageId);
          (0,_nextcloud_dialogs__WEBPACK_IMPORTED_MODULE_3__.showError)(this.t('Invalid page ID'));
          return;
        }

        // Release any active lock when navigating away from a page being edited
        if (this.isEditMode && this.currentPage?.uniqueId !== pageId) {
          await this.releaseLock();
          if (this.originalPage) {
            this.currentPage = structuredClone(this.originalPage);
          }
          this.isEditMode = false;
          this.originalPage = null;
        }

        // Close sidebar when navigating to a different page
        if (this.showDetailsSidebar && this.currentPage?.uniqueId !== pageId) {
          this.showDetailsSidebar = false;
          this.sidebarInitialTab = 'details-tab';
        }

        // Check cache first
        const cacheKey = `page-${pageId}`;
        const cached = _services_CacheService_js__WEBPACK_IMPORTED_MODULE_18__["default"].get(cacheKey);

        // Clear version preview when navigating to a different page
        this.clearVersionPreview();

        if (cached) {
          // Show cached data immediately - no waiting
          this.currentPage = cached;
          this.breadcrumb = cached.breadcrumb || [];
          this.isEditMode = false;
          this.showPages = false;
          this.loading = false;

          // Update URL and metadata immediately from cache
          if (updateUrl && this.currentPage) {
            const pageIdentifier = this.currentPage.uniqueId;
            const newHash = `#${pageIdentifier}`;
            if (window.location.hash !== newHash) {
              window.location.hash = newHash;
            }
          }
          this.updatePageMetadata();

          // Check if this page is locked by another user
          this.checkPageLock(pageId);

          // Smart background refresh - only if cache is older than 2 minutes
          const cacheAge = _services_CacheService_js__WEBPACK_IMPORTED_MODULE_18__["default"].getAge(cacheKey);
          const minRefreshAge = 2 * 60 * 1000; // 2 minutes
          if (!cacheAge || cacheAge > minRefreshAge) {
            this.refreshPageInBackground(pageId, cacheKey);
          }
          return;
        }

        // No cache - fetch page and lock status in parallel
        this.loading = true;
        const [response] = await Promise.all([
          _nextcloud_axios__WEBPACK_IMPORTED_MODULE_0__["default"].get((0,_nextcloud_router__WEBPACK_IMPORTED_MODULE_1__.generateUrl)(`/apps/intravox/api/pages/${pageId}`)),
          this.checkPageLock(pageId),
        ]);
        this.currentPage = response.data;

        // Breadcrumb is now included in page response
        if (response.data.breadcrumb) {
          this.breadcrumb = response.data.breadcrumb;
        }

        this.isEditMode = false;
        this.showPages = false;

        // Update cache
        _services_CacheService_js__WEBPACK_IMPORTED_MODULE_18__["default"].set(cacheKey, response.data);

        // Update URL hash if requested - use uniqueId for permanent links
        if (updateUrl && this.currentPage) {
          const pageIdentifier = this.currentPage.uniqueId;
          const newHash = `#${pageIdentifier}`;
          if (window.location.hash !== newHash) {
            window.location.hash = newHash;
          }
        }

        // Update page title and meta tags for better link previews
        this.updatePageMetadata();
      } catch (err) {
        console.error('IntraVox: Error selecting page:', err);
        (0,_nextcloud_dialogs__WEBPACK_IMPORTED_MODULE_3__.showError)(this.t('Could not load page: {error}', { error: err.message }));
      } finally {
        this.loading = false;
      }
    },
    /**
     * Refresh page data in background without blocking UI
     * Used when showing cached data first
     */
    async refreshPageInBackground(pageId, cacheKey) {
      try {
        const response = await _nextcloud_axios__WEBPACK_IMPORTED_MODULE_0__["default"].get((0,_nextcloud_router__WEBPACK_IMPORTED_MODULE_1__.generateUrl)(`/apps/intravox/api/pages/${pageId}`));
        // Only update if user is still on the same page
        if (this.currentPage && this.currentPage.uniqueId === response.data.uniqueId) {
          this.currentPage = response.data;
          if (response.data.breadcrumb) {
            this.breadcrumb = response.data.breadcrumb;
          }
        }
        // Always update cache
        _services_CacheService_js__WEBPACK_IMPORTED_MODULE_18__["default"].set(cacheKey, response.data);
      } catch (err) {
        // Silent fail for background refresh - user already has cached data
      }
    },
    updatePageMetadata() {
      if (!this.currentPage) return;

      // Update document title
      document.title = `${this.currentPage.title} - IntraVox`;

      // Update or create Open Graph meta tags for better link previews
      this.updateMetaTag('og:title', this.currentPage.title);
      this.updateMetaTag('og:type', 'article');
      this.updateMetaTag('og:url', window.location.href);
      this.updateMetaTag('og:site_name', 'IntraVox');

      // Twitter Card tags
      this.updateMetaTag('twitter:card', 'summary', 'name');
      this.updateMetaTag('twitter:title', this.currentPage.title, 'name');
    },
    updateMetaTag(property, content, attrName = 'property') {
      // Try to find existing meta tag
      let meta = document.querySelector(`meta[${attrName}="${property}"]`);

      if (!meta) {
        // Create new meta tag if it doesn't exist
        meta = document.createElement('meta');
        meta.setAttribute(attrName, property);
        document.head.appendChild(meta);
      }

      meta.setAttribute('content', content);
    },
    async startEditMode() {
      const pageId = this.currentPage?.uniqueId;
      if (!pageId) return;

      try {
        // Acquire page lock before entering edit mode
        const lockUrl = (0,_nextcloud_router__WEBPACK_IMPORTED_MODULE_1__.generateUrl)(`/apps/intravox/api/pages/${pageId}/lock`);
        const response = await _nextcloud_axios__WEBPACK_IMPORTED_MODULE_0__["default"].post(lockUrl);

        if (response.data.success) {
          // Lock acquired — enter edit mode
          this.originalPage = structuredClone(this.currentPage);
          this.editableTitle = this.currentPage?.title || '';
          this.isEditMode = true;
          this.pageLock = null;

          // Clear any version preview - user should edit current version
          this.clearVersionPreview();

          // Start heartbeat to keep lock alive
          this.startLockHeartbeat(pageId);

          // Notify sidebar to reset to current version selection
          window.dispatchEvent(new CustomEvent('intravox:edit:started', {
            detail: { pageId }
          }));
        }
      } catch (err) {
        if (err.response?.status === 409) {
          // Lock denied — another user is editing
          const lock = err.response.data.lock;
          this.pageLock = lock;
          (0,_nextcloud_dialogs__WEBPACK_IMPORTED_MODULE_3__.showError)(this.t('{displayName} is editing this page', {
            displayName: lock?.displayName || 'Someone',
          }));
        } else {
          (0,_nextcloud_dialogs__WEBPACK_IMPORTED_MODULE_3__.showError)(this.t('Could not start editing: {error}', { error: err.message }));
        }
      }
    },
    async saveAndExitEditMode() {
      try {
        // Update title if changed
        if (this.editableTitle && this.editableTitle !== this.currentPage?.title) {
          this.currentPage.title = this.editableTitle;
        }

        await this.savePage();

        this.isEditMode = false;
        this.originalPage = null;

        // Release lock after successful save
        await this.releaseLock();
      } catch (err) {
        console.error('[saveAndExitEditMode] Error:', err);
        (0,_nextcloud_dialogs__WEBPACK_IMPORTED_MODULE_3__.showError)(this.t('Failed to save: {error}', { error: err.message }));
      }
    },
    cancelEditMode() {
      // Rollback to original state
      if (this.originalPage) {
        this.currentPage = structuredClone(this.originalPage);
      }
      this.isEditMode = false;
      this.originalPage = null;
      (0,_nextcloud_dialogs__WEBPACK_IMPORTED_MODULE_3__.showSuccess)(this.t('Changes cancelled'));

      // Release lock after cancelling
      this.releaseLock();
    },
    toggleDraftStatus() {
      if (!this.currentPage) return;
      this.currentPage.status = this.currentPage.status === 'draft' ? 'published' : 'draft';
    },
    /**
     * Check if a page is locked by another user (called on page load)
     */
    async checkPageLock(pageId) {
      try {
        const url = (0,_nextcloud_router__WEBPACK_IMPORTED_MODULE_1__.generateUrl)(`/apps/intravox/api/pages/${pageId}/lock`);
        const response = await _nextcloud_axios__WEBPACK_IMPORTED_MODULE_0__["default"].get(url);
        this.pageLock = response.data.lock || null;
      } catch (err) {
        this.pageLock = null;
      }
    },
    /**
     * Release the current page lock
     */
    async releaseLock() {
      this.stopLockHeartbeat();
      const pageId = this.currentPage?.uniqueId;
      if (!pageId) return;

      try {
        const url = (0,_nextcloud_router__WEBPACK_IMPORTED_MODULE_1__.generateUrl)(`/apps/intravox/api/pages/${pageId}/lock`);
        await _nextcloud_axios__WEBPACK_IMPORTED_MODULE_0__["default"].delete(url);
        this.pageLock = null;
      } catch (err) {
        // Best effort — lock will auto-expire after 15 minutes
        console.warn('[IntraVox] Failed to release lock:', err.message);
      }
    },
    /**
     * Start heartbeat to keep the page lock alive
     */
    startLockHeartbeat(pageId) {
      this.stopLockHeartbeat();
      this.lockHeartbeatTimer = setInterval(async () => {
        try {
          const url = (0,_nextcloud_router__WEBPACK_IMPORTED_MODULE_1__.generateUrl)(`/apps/intravox/api/pages/${pageId}/lock`);
          const response = await _nextcloud_axios__WEBPACK_IMPORTED_MODULE_0__["default"].put(url);
          if (!response.data.success) {
            (0,_nextcloud_dialogs__WEBPACK_IMPORTED_MODULE_3__.showError)(this.t('Your edit lock has expired. Please save your work.'));
            this.stopLockHeartbeat();
          }
        } catch (err) {
          if (err.response?.status === 409) {
            (0,_nextcloud_dialogs__WEBPACK_IMPORTED_MODULE_3__.showError)(this.t('Your edit lock has expired. Please save your work.'));
            this.stopLockHeartbeat();
          }
          // On network error, keep trying — lock expires after 15 min
        }
      }, 60 * 1000); // Every 60 seconds
    },
    /**
     * Stop the lock heartbeat interval
     */
    stopLockHeartbeat() {
      if (this.lockHeartbeatTimer) {
        clearInterval(this.lockHeartbeatTimer);
        this.lockHeartbeatTimer = null;
      }
    },
    /**
     * Release lock when the browser tab/window is being closed
     */
    handleBeforeUnload() {
      if (this.isEditMode && this.currentPage?.uniqueId) {
        const url = (0,_nextcloud_router__WEBPACK_IMPORTED_MODULE_1__.generateUrl)(`/apps/intravox/api/pages/${this.currentPage.uniqueId}/lock`);
        fetch(window.location.origin + url, {
          method: 'DELETE',
          keepalive: true,
          headers: { requesttoken: OC.requestToken },
        });
      }
    },
    /**
     * Force-unlock a page (admin only)
     */
    async forceUnlock() {
      const pageId = this.currentPage?.uniqueId;
      const lockedBy = this.pageLock?.displayName || 'Someone';
      if (!pageId) return;

      if (!confirm(this.t('Are you sure you want to unlock this page? {displayName} may lose unsaved changes.', { displayName: lockedBy }))) {
        return;
      }

      try {
        const url = (0,_nextcloud_router__WEBPACK_IMPORTED_MODULE_1__.generateUrl)(`/apps/intravox/api/pages/${pageId}/lock/force-release`);
        await _nextcloud_axios__WEBPACK_IMPORTED_MODULE_0__["default"].post(url);
        this.pageLock = null;
        (0,_nextcloud_dialogs__WEBPACK_IMPORTED_MODULE_3__.showSuccess)(this.t('Page unlocked'));
      } catch (err) {
        (0,_nextcloud_dialogs__WEBPACK_IMPORTED_MODULE_3__.showError)(this.t('Could not unlock page: {error}', { error: err.message }));
      }
    },
    async savePage() {
      if (!this.currentPage || !this.currentPage.uniqueId) {
        throw new Error('No page to save');
      }

      // Ensure uniqueId exists (for legacy pages that don't have it yet)
      if (!this.currentPage.uniqueId) {
        this.currentPage.uniqueId = this.generateUniqueId();
      }

      const url = (0,_nextcloud_router__WEBPACK_IMPORTED_MODULE_1__.generateUrl)(`/apps/intravox/api/pages/${this.currentPage.uniqueId}`);

      try {
        await _nextcloud_axios__WEBPACK_IMPORTED_MODULE_0__["default"].put(url, this.currentPage);

        // Invalidate cache for this page and pages list
        _services_CacheService_js__WEBPACK_IMPORTED_MODULE_18__["default"].delete(`page-${this.currentPage.uniqueId}`);
        _services_CacheService_js__WEBPACK_IMPORTED_MODULE_18__["default"].delete('pages-list');

        (0,_nextcloud_dialogs__WEBPACK_IMPORTED_MODULE_3__.showSuccess)(this.t('Page saved'));

        // Dispatch event to notify sidebar that a new version was created
        window.dispatchEvent(new CustomEvent('intravox:page:saved', {
          detail: { pageId: this.currentPage.uniqueId }
        }));
      } catch (err) {
        console.error('[savePage] Error:', err);
        const errorMsg = err.response?.data?.error || err.message || 'Unknown error';
        (0,_nextcloud_dialogs__WEBPACK_IMPORTED_MODULE_3__.showError)(this.t('Could not save page: {error}', { error: errorMsg }));
        throw err;
      }
    },
    async updatePage(updatedPage) {
      // Use structuredClone to ensure Vue reactivity captures all nested changes
      this.currentPage = structuredClone(updatedPage);
    },
    createNewPage() {
      this.showNewPageModal = true;
    },
    generateSlug(title) {
      // Convert title to URL-friendly slug
      return title.toLowerCase()
        .normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '') // Remove diacritics
        .replace(/[^a-z0-9]+/g, '-')
        .replace(/^-+|-+$/g, '');
    },
    generateUniqueId() {
      // Generate a UUID v4 for guaranteed uniqueness across servers
      // This ensures no conflicts during migrations or multi-server scenarios
      return `page-${crypto.randomUUID()}`;
    },
    async handleCreatePage(data) {
      // Support both old format (string) and new format (object)
      const title = typeof data === 'string' ? data : data.title;
      const addToNavigation = typeof data === 'object' ? data.addToNavigation : false;

      if (!title) return;

      // Generate slug from title for folder name (readable)
      const slug = this.generateSlug(title);

      if (!slug) {
        (0,_nextcloud_dialogs__WEBPACK_IMPORTED_MODULE_3__.showError)(this.t('Invalid page title'));
        return;
      }

      // Generate unique ID for internal references
      const uniqueId = this.generateUniqueId();

      try {
        const newPage = {
          id: slug, // Use slug as the page ID (folder name)
          uniqueId: uniqueId, // Store unique ID for internal references
          title: title,
          status: 'draft', // New pages start as draft
          layout: {
            columns: 1,
            rows: [
              {
                widgets: [
                  {
                    id: 'widget-1',
                    type: 'heading',
                    content: title,
                    level: 1,
                    column: 1,
                    order: 1
                  }
                ]
              }
            ]
          }
        };

        // If we have a current page, create the new page as a child of the current page
        // by using the current page's path as the parent path
        if (this.currentPage && this.currentPage.path) {
          // Use the current page's full path as the parent path
          // This creates the new page as a child of the current page
          newPage.parentPath = this.currentPage.path;
        }

        await _nextcloud_axios__WEBPACK_IMPORTED_MODULE_0__["default"].post((0,_nextcloud_router__WEBPACK_IMPORTED_MODULE_1__.generateUrl)('/apps/intravox/api/pages'), newPage);
        (0,_nextcloud_dialogs__WEBPACK_IMPORTED_MODULE_3__.showSuccess)(this.t('Page created'));

        // Reload pages first so the new page is in the array
        await this.loadPages();

        // Add to navigation if requested (after pages are loaded)
        if (addToNavigation) {
          await this.addPageToNavigation(slug, title);
        }

        await this.selectPage(slug);
        // Open the new page in edit mode (with lock)
        await this.startEditMode();
      } catch (err) {
        (0,_nextcloud_dialogs__WEBPACK_IMPORTED_MODULE_3__.showError)(this.t('Could not create page: {error}', { error: err.message }));
      }
    },
    async handleCreatePageFromTemplate(data) {
      const { templateId, title } = data;

      if (!templateId || !title) {
        (0,_nextcloud_dialogs__WEBPACK_IMPORTED_MODULE_3__.showError)(this.t('Missing template or title'));
        return;
      }

      try {
        const url = (0,_nextcloud_router__WEBPACK_IMPORTED_MODULE_1__.generateUrl)('/apps/intravox/api/pages/from-template');
        const response = await _nextcloud_axios__WEBPACK_IMPORTED_MODULE_0__["default"].post(url, {
          templateId,
          pageTitle: title,
          parentPath: this.currentPage?.path || null
        });

        if (response.data.success) {
          (0,_nextcloud_dialogs__WEBPACK_IMPORTED_MODULE_3__.showSuccess)(this.t('Page created from template'));

          const newPage = response.data.page;

          if (newPage?.uniqueId) {
            // The backend already returned the freshly created page through
            // PageService::getPage() (which runs enrichWithPathData + the
            // sanitize pipeline) so we have a fully populated payload right
            // here. Trying to re-fetch via selectPage() can race with
            // pageDataCache / parent folder listings that haven't yet
            // refreshed and intermittently 404 — sending the user back to
            // the home page. Use the response directly and warm both the
            // local pages array and the frontend CacheService so any
            // subsequent navigation hits an immediate cache.

            // 1. Append to local pages list (no loadPages refresh — that
            //    re-runs its tail-end selectPage(home) which would steal
            //    focus from the new page).
            if (!this.pages.find(p => p.uniqueId === newPage.uniqueId)) {
              this.pages.push({
                uniqueId: newPage.uniqueId,
                title: newPage.title,
                path: newPage.path || null,
                status: newPage.status || 'draft',
                modified: newPage.modified || Math.floor(Date.now() / 1000),
                permissions: newPage.permissions || { canRead: true, canWrite: true },
              });
              _services_CacheService_js__WEBPACK_IMPORTED_MODULE_18__["default"].delete('pages-list');
            }

            // 2. Warm the per-page cache with the backend response so
            //    selectPage() finds it without an API roundtrip.
            _services_CacheService_js__WEBPACK_IMPORTED_MODULE_18__["default"].set(`page-${newPage.uniqueId}`, newPage);

            // 3. Update URL hash before navigating so any concurrent
            //    page-list refresh doesn't redirect us home.
            window.location.hash = `#${newPage.uniqueId}`;

            // 4. Mount the editor.
            this.currentPage = newPage;
            this.breadcrumb = newPage.breadcrumb || [];
            this.isEditMode = false;
            this.showPages = false;

            await this.$nextTick();
            await this.startEditMode();
          }
        } else {
          (0,_nextcloud_dialogs__WEBPACK_IMPORTED_MODULE_3__.showError)(this.t('Could not create page: {error}', { error: response.data.error || 'Unknown error' }));
        }
      } catch (err) {
        console.error('[handleCreatePageFromTemplate] Error:', err);
        (0,_nextcloud_dialogs__WEBPACK_IMPORTED_MODULE_3__.showError)(this.t('Could not create page from template: {error}', { error: err.message }));
      }
    },
    handleTemplateSaved(template) {
      (0,_nextcloud_dialogs__WEBPACK_IMPORTED_MODULE_3__.showSuccess)(this.t('Template saved: {name}', { name: template.title }));
    },
    async addPageToNavigation(pageId, pageTitle) {
      try {
        // Get the full page path to determine parent hierarchy
        const page = this.pages.find(p => p.uniqueId === pageId);
        if (!page) {
          throw new Error('Page not found');
        }

        // Ensure navigation structure exists
        if (!this.navigation || !this.navigation.items) {
          (0,_nextcloud_dialogs__WEBPACK_IMPORTED_MODULE_3__.showError)(this.t('Navigation structure is invalid. Please reload the page.'));
          return;
        }

        // Build the path hierarchy from the page's path
        // Example path: "en/team-sales/campaigns" -> ["team-sales", "campaigns"]
        const pathParts = page.path ? page.path.split('/').filter(part =>
          part && !['en', 'nl', 'de', 'fr', 'departments'].includes(part)
        ) : [];

        // Helper function to find or create navigation item
        const findOrCreateNavItem = (items, pageId, title) => {
          let item = items.find(i => i.pageId === pageId);
          if (!item) {
            item = {
              id: pageId,
              title: title,
              pageId: pageId,
              url: null,
              target: null,
              children: []
            };
            items.push(item);
          }
          return item;
        };

        // Build the navigation hierarchy
        let currentLevel = this.navigation.items;

        // Handle root-level pages (pages with no parent path)
        if (pathParts.length === 0) {
          const newNavItem = {
            id: pageId,
            title: pageTitle,
            pageId: pageId,
            url: null,
            target: null,
            children: []
          };

          // Check if it already exists at root level
          const existingIndex = currentLevel.findIndex(i => i.pageId === pageId);
          if (existingIndex >= 0) {
            currentLevel[existingIndex] = newNavItem;
          } else {
            currentLevel.push(newNavItem);
          }
        } else {
          // For each part of the path except the last one (which is our new page)
          for (let i = 0; i < pathParts.length; i++) {
            const isLastPart = (i === pathParts.length - 1);
            const partPageId = pathParts[i];

            // Find the page to get its title
            const partPage = this.pages.find(p => p.uniqueId === partPageId);
            const partTitle = partPage ? partPage.title : partPageId;

            // If this is the last part and it matches our pageId, this is our page
            if (isLastPart && partPageId === pageId) {
              const newNavItem = {
                id: pageId,
                title: pageTitle,
                pageId: pageId,
                url: null,
                target: null,
                children: []
              };

              const existingIndex = currentLevel.findIndex(i => i.pageId === pageId);
              if (existingIndex >= 0) {
                currentLevel[existingIndex] = newNavItem;
              } else {
                currentLevel.push(newNavItem);
              }
            } else {
              // This is a parent, find or create it
              const parentNavItem = findOrCreateNavItem(currentLevel, partPageId, partTitle);
              currentLevel = parentNavItem.children;
            }
          }
        }

        // Save navigation (send the full navigation structure with type and items)
        await _nextcloud_axios__WEBPACK_IMPORTED_MODULE_0__["default"].post((0,_nextcloud_router__WEBPACK_IMPORTED_MODULE_1__.generateUrl)('/apps/intravox/api/navigation'), {
          navigation: this.navigation
        });

        (0,_nextcloud_dialogs__WEBPACK_IMPORTED_MODULE_3__.showSuccess)(this.t('Added to navigation'));
      } catch (err) {
        (0,_nextcloud_dialogs__WEBPACK_IMPORTED_MODULE_3__.showError)(this.t('Failed to add page to navigation'));
      }
    },
    async deletePage(pageId) {
      if (!confirm(this.t('Are you sure you want to delete this page?'))) {
        return;
      }

      try {
        await _nextcloud_axios__WEBPACK_IMPORTED_MODULE_0__["default"].delete((0,_nextcloud_router__WEBPACK_IMPORTED_MODULE_1__.generateUrl)(`/apps/intravox/api/pages/${pageId}`));
        (0,_nextcloud_dialogs__WEBPACK_IMPORTED_MODULE_3__.showSuccess)(this.t('Page deleted'));
        await this.loadPages();

        if (this.currentPage?.uniqueId === pageId) {
          this.currentPage = null;
          if (this.pages.length > 0) {
            await this.selectPage(this.pages[0].uniqueId);
          }
        }
      } catch (err) {
        (0,_nextcloud_dialogs__WEBPACK_IMPORTED_MODULE_3__.showError)(this.t('Could not delete page: {error}', { error: err.message }));
      }
    },
    showPageList() {
      this.showPages = true;
    },
    async loadNavigation() {
      try {
        const url = (0,_nextcloud_router__WEBPACK_IMPORTED_MODULE_1__.generateUrl)('/apps/intravox/api/navigation');
        const response = await _nextcloud_axios__WEBPACK_IMPORTED_MODULE_0__["default"].get(url);
        this.navigation = response.data.navigation;
        // Use permissions object if available, fall back to canEdit for backwards compatibility
        const perms = response.data.permissions || {};
        this.canEditNavigation = perms.canWrite ?? response.data.canEdit ?? false;
      } catch (err) {
        // Provide default empty navigation
        this.navigation = {
          type: 'dropdown',
          items: []
        };
        this.canEditNavigation = false;
      }
    },
    async saveNavigation(navigation) {
      try {
        const response = await _nextcloud_axios__WEBPACK_IMPORTED_MODULE_0__["default"].post((0,_nextcloud_router__WEBPACK_IMPORTED_MODULE_1__.generateUrl)('/apps/intravox/api/navigation'), navigation);
        this.navigation = response.data.navigation;
        this.showNavigationEditor = false;
        (0,_nextcloud_dialogs__WEBPACK_IMPORTED_MODULE_3__.showSuccess)(this.t('Navigation saved'));
      } catch (err) {
        (0,_nextcloud_dialogs__WEBPACK_IMPORTED_MODULE_3__.showError)(this.t('Could not save navigation: {error}', { error: err.message }));
      }
    },
    async loadFooter() {
      try {
        const response = await _nextcloud_axios__WEBPACK_IMPORTED_MODULE_0__["default"].get((0,_nextcloud_router__WEBPACK_IMPORTED_MODULE_1__.generateUrl)('/apps/intravox/api/footer'));
        this.footerContent = response.data.content;
        // Use permissions object if available, fall back to canEdit for backwards compatibility
        const perms = response.data.permissions || {};
        this.canEditFooter = perms.canWrite ?? response.data.canEdit ?? false;
      } catch (err) {
        this.footerContent = '';
        this.canEditFooter = false;
      }
    },
    async handleFooterSave(content) {
      try {
        const response = await _nextcloud_axios__WEBPACK_IMPORTED_MODULE_0__["default"].post((0,_nextcloud_router__WEBPACK_IMPORTED_MODULE_1__.generateUrl)('/apps/intravox/api/footer'), {
          content: content
        });
        this.footerContent = response.data.content;
        (0,_nextcloud_dialogs__WEBPACK_IMPORTED_MODULE_3__.showSuccess)(this.t('Footer saved'));
      } catch (err) {
        (0,_nextcloud_dialogs__WEBPACK_IMPORTED_MODULE_3__.showError)(this.t('Could not save footer: {error}', { error: err.message }));
      }
    },
    navigateToItem(item) {
      if (item.uniqueId) {
        this.selectPage(item.uniqueId);
      } else if (item.pageId) {
        // Legacy support for old pageId
        this.selectPage(item.pageId);
      } else if (item.url) {
        if (item.url.startsWith('http') || item.url.startsWith('//')) {
          window.open(item.url, '_blank');
        } else {
          window.location.href = item.url;
        }
      }
    },
    handleLanguageChange() {
      // Only reload navigation - pages structure doesn't change with language
      this.loadNavigation();
      // Force Vue to re-render all translated strings
      this.$forceUpdate();
    },
    async handleVersionRestored(restoredPageData) {
      (0,_nextcloud_dialogs__WEBPACK_IMPORTED_MODULE_3__.showSuccess)(this.t('Version restored successfully'));

      // Reload pages list to update timestamps, but stay on current page
      const currentPageId = restoredPageData.uniqueId || this.currentPage?.uniqueId;

      try {
        // Invalidate cache for this page
        _services_CacheService_js__WEBPACK_IMPORTED_MODULE_18__["default"].delete(`page-${currentPageId}`);
        _services_CacheService_js__WEBPACK_IMPORTED_MODULE_18__["default"].delete('pages-list');

        // Reload pages list
        const response = await _nextcloud_axios__WEBPACK_IMPORTED_MODULE_0__["default"].get((0,_nextcloud_router__WEBPACK_IMPORTED_MODULE_1__.generateUrl)('/apps/intravox/api/pages'));
        this.pages = response.data;

        // Re-fetch the page to get the fully restored content
        await this.selectPage(currentPageId, false);

        // Ensure version preview is cleared - prevents race condition
        // where sidebar's version-selected event could re-set versionPage
        this.clearVersionPreview();

        // Open sidebar with Versions tab
        this.sidebarInitialTab = 'versions-tab';
        this.showDetailsSidebar = true;
      } catch (err) {
        console.error('IntraVox: Error reloading pages after restore:', err);
      }
    },
    handleHashChange() {
      // Handle URL hash changes for navigation
      const hash = window.location.hash;
      if (!hash || hash === '#') {
        // No hash, load home page
        const homePage = findHomePage(this.pages);
        if (homePage) {
          this.selectPage(homePage.uniqueId, false);
        }
        return;
      }

      const pageIdentifier = hash.substring(1); // Remove '#'

      // Find page by uniqueId
      const targetPage = this.pages.find(p => p.uniqueId === pageIdentifier);

      if (targetPage) {
        // Don't update URL since we're already responding to a hash change
        this.selectPage(targetPage.uniqueId, false);
      } else {
        // Fall back to home
        const homePage = findHomePage(this.pages);
        if (homePage) {
          this.selectPage(homePage.uniqueId, true);
        }
      }
    },
    async handleVersionSelected(data) {
      const { version, pageId } = data;

      // If version is null, clear the preview (show current page)
      if (!version) {
        this.clearVersionPreview();
        return;
      }

      this.selectedVersion = version;
      this.loadingVersion = true;

      try {
        // Fetch the version content
        const url = (0,_nextcloud_router__WEBPACK_IMPORTED_MODULE_1__.generateUrl)(`/apps/intravox/api/pages/${pageId}/versions/${version.timestamp}/content`);
        const response = await _nextcloud_axios__WEBPACK_IMPORTED_MODULE_0__["default"].get(url);

        // Parse the JSON content from the version
        const versionJson = JSON.parse(response.data.content);

        // Create a page object from the version data
        this.versionPage = {
          ...versionJson,
          id: pageId,
          // Add a visual indicator that this is a version
          title: `${versionJson.title || this.currentPage.title} (${this.formatVersionDate(version.timestamp)})`
        };
      } catch (err) {
        (0,_nextcloud_dialogs__WEBPACK_IMPORTED_MODULE_3__.showError)(this.t('Could not load version: {error}', { error: err.message }));
        this.selectedVersion = null;
        this.versionPage = null;
      } finally {
        this.loadingVersion = false;
      }
    },
    clearVersionPreview() {
      this.selectedVersion = null;
      this.versionPage = null;
    },
    formatVersionDate(timestamp) {
      const date = new Date(timestamp * 1000);
      return date.toLocaleString();
    },
    handleCloseSidebar() {
      this.showDetailsSidebar = false;
      // Reset to default tab when closing
      this.sidebarInitialTab = 'details-tab';
      // Clear version preview when closing sidebar
      this.clearVersionPreview();
    },
    async loadEngagementSettings() {
      try {
        const cached = _services_CacheService_js__WEBPACK_IMPORTED_MODULE_18__["default"].get('engagement-settings');
        if (cached) {
          this.globalEngagementSettings = cached;
          return;
        }
        const response = await _nextcloud_axios__WEBPACK_IMPORTED_MODULE_0__["default"].get((0,_nextcloud_router__WEBPACK_IMPORTED_MODULE_1__.generateUrl)('/apps/intravox/api/settings/engagement'));
        this.globalEngagementSettings = response.data;
        _services_CacheService_js__WEBPACK_IMPORTED_MODULE_18__["default"].set('engagement-settings', response.data);
      } catch (err) {
        // Silent fail - use defaults
      }
    },
    async handlePageSettingsSave(settings) {
      if (!this.currentPage) return;

      // Initialize settings object if it doesn't exist
      if (!this.currentPage.settings) {
        this.currentPage.settings = {};
      }

      // Update page settings
      this.currentPage.settings.allowReactions = settings.allowReactions;
      this.currentPage.settings.allowComments = settings.allowComments;
      this.currentPage.settings.allowCommentReactions = settings.allowCommentReactions;

      try {
        await this.savePage();
        this.showPageSettingsModal = false;
        (0,_nextcloud_dialogs__WEBPACK_IMPORTED_MODULE_3__.showSuccess)(this.t('Page settings saved'));
      } catch (err) {
        (0,_nextcloud_dialogs__WEBPACK_IMPORTED_MODULE_3__.showError)(this.t('Could not save page settings: {error}', { error: err.message }));
      }
    }
  }
});


/***/ },

/***/ "./src/components/Breadcrumb.vue"
/*!***************************************!*\
  !*** ./src/components/Breadcrumb.vue ***!
  \***************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _Breadcrumb_vue_vue_type_template_id_6f46de9a_scoped_true__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./Breadcrumb.vue?vue&type=template&id=6f46de9a&scoped=true */ "./src/components/Breadcrumb.vue?vue&type=template&id=6f46de9a&scoped=true");
/* harmony import */ var _Breadcrumb_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./Breadcrumb.vue?vue&type=script&lang=js */ "./src/components/Breadcrumb.vue?vue&type=script&lang=js");
/* harmony import */ var _Breadcrumb_vue_vue_type_style_index_0_id_6f46de9a_scoped_true_lang_css__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./Breadcrumb.vue?vue&type=style&index=0&id=6f46de9a&scoped=true&lang=css */ "./src/components/Breadcrumb.vue?vue&type=style&index=0&id=6f46de9a&scoped=true&lang=css");
/* harmony import */ var _node_modules_vue_loader_dist_exportHelper_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ../../node_modules/vue-loader/dist/exportHelper.js */ "./node_modules/vue-loader/dist/exportHelper.js");




;


const __exports__ = /*#__PURE__*/(0,_node_modules_vue_loader_dist_exportHelper_js__WEBPACK_IMPORTED_MODULE_3__["default"])(_Breadcrumb_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_1__["default"], [['render',_Breadcrumb_vue_vue_type_template_id_6f46de9a_scoped_true__WEBPACK_IMPORTED_MODULE_0__.render],['__scopeId',"data-v-6f46de9a"],['__file',"src/components/Breadcrumb.vue"]])
/* hot reload */
if (false) // removed by dead control flow
{}


/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (__exports__);

/***/ },

/***/ "./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/Breadcrumb.vue?vue&type=script&lang=js"
/*!***********************************************************************************************************************************!*\
  !*** ./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/Breadcrumb.vue?vue&type=script&lang=js ***!
  \***********************************************************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var vue_material_design_icons_ChevronRight_vue__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! vue-material-design-icons/ChevronRight.vue */ "./node_modules/vue-material-design-icons/ChevronRight.vue");



/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = ({
	name: 'Breadcrumb',
	components: {
		ChevronRight: vue_material_design_icons_ChevronRight_vue__WEBPACK_IMPORTED_MODULE_0__["default"]
	},
	props: {
		breadcrumb: {
			type: Array,
			required: true,
			default: () => []
		}
	},
	computed: {
		// Return all breadcrumb items including the current page
		breadcrumbItems() {
			if (!this.breadcrumb || this.breadcrumb.length === 0) {
				return []
			}
			return this.breadcrumb
		}
	},
	methods: {
		navigateToPage(pageId) {
			this.$emit('navigate', pageId)
		}
	}
});


/***/ },

/***/ "./src/components/Footer.vue"
/*!***********************************!*\
  !*** ./src/components/Footer.vue ***!
  \***********************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _Footer_vue_vue_type_template_id_40ab164b_scoped_true__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./Footer.vue?vue&type=template&id=40ab164b&scoped=true */ "./src/components/Footer.vue?vue&type=template&id=40ab164b&scoped=true");
/* harmony import */ var _Footer_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./Footer.vue?vue&type=script&lang=js */ "./src/components/Footer.vue?vue&type=script&lang=js");
/* harmony import */ var _Footer_vue_vue_type_style_index_0_id_40ab164b_scoped_true_lang_css__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./Footer.vue?vue&type=style&index=0&id=40ab164b&scoped=true&lang=css */ "./src/components/Footer.vue?vue&type=style&index=0&id=40ab164b&scoped=true&lang=css");
/* harmony import */ var _node_modules_vue_loader_dist_exportHelper_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ../../node_modules/vue-loader/dist/exportHelper.js */ "./node_modules/vue-loader/dist/exportHelper.js");




;


const __exports__ = /*#__PURE__*/(0,_node_modules_vue_loader_dist_exportHelper_js__WEBPACK_IMPORTED_MODULE_3__["default"])(_Footer_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_1__["default"], [['render',_Footer_vue_vue_type_template_id_40ab164b_scoped_true__WEBPACK_IMPORTED_MODULE_0__.render],['__scopeId',"data-v-40ab164b"],['__file',"src/components/Footer.vue"]])
/* hot reload */
if (false) // removed by dead control flow
{}


/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (__exports__);

/***/ },

/***/ "./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/Footer.vue?vue&type=script&lang=js"
/*!*******************************************************************************************************************************!*\
  !*** ./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/Footer.vue?vue&type=script&lang=js ***!
  \*******************************************************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _nextcloud_l10n__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @nextcloud/l10n */ "./node_modules/@nextcloud/l10n/dist/index.mjs");
/* harmony import */ var _nextcloud_dialogs__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @nextcloud/dialogs */ "./node_modules/@nextcloud/dialogs/dist/index.mjs");
/* harmony import */ var vue__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! vue */ "./node_modules/vue/dist/vue.runtime.esm-bundler.js");
/* harmony import */ var _nextcloud_vue__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! @nextcloud/vue */ "./node_modules/@nextcloud/vue/dist/index.mjs");
/* harmony import */ var vue_material_design_icons_Pencil_vue__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! vue-material-design-icons/Pencil.vue */ "./node_modules/vue-material-design-icons/Pencil.vue");
/* harmony import */ var vue_material_design_icons_Close_vue__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! vue-material-design-icons/Close.vue */ "./node_modules/vue-material-design-icons/Close.vue");
/* harmony import */ var vue_material_design_icons_ContentSave_vue__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! vue-material-design-icons/ContentSave.vue */ "./node_modules/vue-material-design-icons/ContentSave.vue");
/* harmony import */ var _utils_markdownSerializer_js__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! ../utils/markdownSerializer.js */ "./src/utils/markdownSerializer.js");










// Async to match Widget.vue's strategy.
const InlineTextEditor = (0,vue__WEBPACK_IMPORTED_MODULE_2__.defineAsyncComponent)(() => Promise.all(/*! import() */[__webpack_require__.e("vendors"), __webpack_require__.e("shared")]).then(__webpack_require__.bind(__webpack_require__, /*! ./InlineTextEditor.vue */ "./src/components/InlineTextEditor.vue")));

/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = ({
  name: 'Footer',
  components: {
    NcButton: _nextcloud_vue__WEBPACK_IMPORTED_MODULE_3__.NcButton,
    NcActions: _nextcloud_vue__WEBPACK_IMPORTED_MODULE_3__.NcActions,
    NcActionButton: _nextcloud_vue__WEBPACK_IMPORTED_MODULE_3__.NcActionButton,
    Pencil: vue_material_design_icons_Pencil_vue__WEBPACK_IMPORTED_MODULE_4__["default"],
    Close: vue_material_design_icons_Close_vue__WEBPACK_IMPORTED_MODULE_5__["default"],
    ContentSave: vue_material_design_icons_ContentSave_vue__WEBPACK_IMPORTED_MODULE_6__["default"],
    InlineTextEditor
  },
  props: {
    footerContent: {
      type: String,
      default: ''
    },
    canEdit: {
      type: Boolean,
      default: false
    },
    isHomePage: {
      type: Boolean,
      default: false
    }
  },
  emits: ['save', 'navigate'],
  data() {
    return {
      isEditingFooter: false,
      editableContent: '',
      originalContent: ''
    };
  },
  mounted() {
    this.attachLinkHandlers();
  },
  updated() {
    // Reattach handlers when content changes
    if (!this.isEditingFooter) {
      this.$nextTick(() => {
        this.attachLinkHandlers();
      });
    }
  },
  computed: {
    footerHtml() {
      // Convert markdown to HTML for display
      return (0,_utils_markdownSerializer_js__WEBPACK_IMPORTED_MODULE_7__.markdownToHtml)(this.footerContent || '');
    }
  },
  methods: {
    t(key, vars = {}) {
      return (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_0__.translate)('intravox', key, vars);
    },
    startEditFooter() {
      this.originalContent = this.footerContent;
      this.editableContent = this.footerContent;
      this.isEditingFooter = true;
    },
    cancelEditFooter() {
      this.isEditingFooter = false;
      this.editableContent = '';
      this.originalContent = '';
      (0,_nextcloud_dialogs__WEBPACK_IMPORTED_MODULE_1__.showSuccess)(this.t('Changes cancelled'));
    },
    saveFooter() {
      // Emit save event to parent - parent will handle API call and notifications
      this.$emit('save', this.editableContent);
      this.isEditingFooter = false;
      this.originalContent = '';
    },
    attachLinkHandlers() {
      // Get all links in the footer content
      const footerEl = this.$el?.querySelector('.footer-content');
      if (!footerEl) return;

      const links = footerEl.querySelectorAll('a');
      links.forEach(link => {
        const href = link.getAttribute('href');

        // Only handle internal navigation links (those starting with #)
        if (href && href.startsWith('#') && href.length > 1) {
          // Remove any existing click handler
          const oldHandler = link._clickHandler;
          if (oldHandler) {
            link.removeEventListener('click', oldHandler);
          }

          // Create and store new handler
          const clickHandler = (e) => {
            e.preventDefault();
            // Remove the # and emit the pageId
            const pageId = href.substring(1);
            this.$emit('navigate', pageId);
          };

          link._clickHandler = clickHandler;
          link.addEventListener('click', clickHandler);
        }
      });
    }
  }
});


/***/ },

/***/ "./src/components/Navigation.vue"
/*!***************************************!*\
  !*** ./src/components/Navigation.vue ***!
  \***************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _Navigation_vue_vue_type_template_id_81440b78_scoped_true__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./Navigation.vue?vue&type=template&id=81440b78&scoped=true */ "./src/components/Navigation.vue?vue&type=template&id=81440b78&scoped=true");
/* harmony import */ var _Navigation_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./Navigation.vue?vue&type=script&lang=js */ "./src/components/Navigation.vue?vue&type=script&lang=js");
/* harmony import */ var _Navigation_vue_vue_type_style_index_0_id_81440b78_scoped_true_lang_css__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./Navigation.vue?vue&type=style&index=0&id=81440b78&scoped=true&lang=css */ "./src/components/Navigation.vue?vue&type=style&index=0&id=81440b78&scoped=true&lang=css");
/* harmony import */ var _node_modules_vue_loader_dist_exportHelper_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ../../node_modules/vue-loader/dist/exportHelper.js */ "./node_modules/vue-loader/dist/exportHelper.js");




;


const __exports__ = /*#__PURE__*/(0,_node_modules_vue_loader_dist_exportHelper_js__WEBPACK_IMPORTED_MODULE_3__["default"])(_Navigation_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_1__["default"], [['render',_Navigation_vue_vue_type_template_id_81440b78_scoped_true__WEBPACK_IMPORTED_MODULE_0__.render],['__scopeId',"data-v-81440b78"],['__file',"src/components/Navigation.vue"]])
/* hot reload */
if (false) // removed by dead control flow
{}


/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (__exports__);

/***/ },

/***/ "./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/Navigation.vue?vue&type=script&lang=js"
/*!***********************************************************************************************************************************!*\
  !*** ./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/Navigation.vue?vue&type=script&lang=js ***!
  \***********************************************************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _nextcloud_l10n__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @nextcloud/l10n */ "./node_modules/@nextcloud/l10n/dist/index.mjs");
/* harmony import */ var _nextcloud_vue__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @nextcloud/vue */ "./node_modules/@nextcloud/vue/dist/index.mjs");
/* harmony import */ var vue_material_design_icons_ChevronDown_vue__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! vue-material-design-icons/ChevronDown.vue */ "./node_modules/vue-material-design-icons/ChevronDown.vue");
/* harmony import */ var vue_material_design_icons_ChevronRight_vue__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! vue-material-design-icons/ChevronRight.vue */ "./node_modules/vue-material-design-icons/ChevronRight.vue");
/* harmony import */ var vue_material_design_icons_Menu_vue__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! vue-material-design-icons/Menu.vue */ "./node_modules/vue-material-design-icons/Menu.vue");
/* harmony import */ var vue_material_design_icons_FileTree_vue__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! vue-material-design-icons/FileTree.vue */ "./node_modules/vue-material-design-icons/FileTree.vue");








/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = ({
  name: 'Navigation',
  components: {
    NcActions: _nextcloud_vue__WEBPACK_IMPORTED_MODULE_1__.NcActions,
    NcActionButton: _nextcloud_vue__WEBPACK_IMPORTED_MODULE_1__.NcActionButton,
    ChevronDown: vue_material_design_icons_ChevronDown_vue__WEBPACK_IMPORTED_MODULE_2__["default"],
    ChevronRight: vue_material_design_icons_ChevronRight_vue__WEBPACK_IMPORTED_MODULE_3__["default"],
    Menu: vue_material_design_icons_Menu_vue__WEBPACK_IMPORTED_MODULE_4__["default"],
    FileTree: vue_material_design_icons_FileTree_vue__WEBPACK_IMPORTED_MODULE_5__["default"]
  },
  props: {
    items: {
      type: Array,
      default: () => []
    },
    type: {
      type: String,
      default: 'dropdown',
      validator: (value) => ['dropdown', 'megamenu'].includes(value)
    },
    isPublic: {
      type: Boolean,
      default: false
    }
  },
  emits: ['navigate', 'show-tree'],
  data() {
    return {
      // Mobile menu state
      mobileExpandedItems: [],
      // Dropdown state
      activeDropdown: null,
      dropdownTimeout: null,
      dropdownExpandedSections: [],
      // Mega menu state
      activeMegaMenu: null,
      megaMenuTimeout: null,
      // Track current trigger element for repositioning on scroll
      currentTriggerElement: null
    };
  },
  mounted() {
    // Add scroll listener to reposition dropdown menus during scroll
    window.addEventListener('scroll', this.updateDropdownPosition, true);
  },
  beforeUnmount() {
    // Clean up scroll listener
    window.removeEventListener('scroll', this.updateDropdownPosition, true);
  },
  methods: {
    t(key, vars = {}) {
      return (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_0__.translate)('intravox', key, vars);
    },
    getItemKey(item) {
      // Generate a unique key for Vue's :key attribute
      return item.uniqueId || item.title.replace(/\s+/g, '-').toLowerCase();
    },
    decodeHtmlEntities(text) {
      if (!text) return '';
      const textarea = document.createElement('textarea');
      textarea.innerHTML = text;
      return textarea.value;
    },
    getItemUrl(item) {
      if (item.url) {
        return item.url;
      }
      if (item.uniqueId) {
        return `#${item.uniqueId}`;
      }
      return '#';
    },
    handleTreeClick(event) {
      this.$emit('show-tree');
    },
    handleItemClick(item) {
      this.$emit('navigate', item);
      this.hideDropdown();
      this.hideMegaMenu();
    },
    handleMobileItemClick(item) {
      // Only navigate if item has a link (uniqueId or url)
      if (item.uniqueId || item.url) {
        this.$emit('navigate', item);
      } else {
        // If no link, toggle expand instead
        this.toggleMobileItem(this.getItemKey(item));
      }
    },
    // Dropdown methods
    showDropdown(item, event) {
      if (this.dropdownTimeout) {
        clearTimeout(this.dropdownTimeout);
      }
      this.activeDropdown = this.getItemKey(item);
      this.currentTriggerElement = event?.currentTarget;

      // Position dropdown menu below trigger using fixed positioning
      this.$nextTick(() => {
        this.updateDropdownPosition();
      });
    },
    keepDropdownOpen() {
      if (this.dropdownTimeout) {
        clearTimeout(this.dropdownTimeout);
      }
    },
    hideDropdown() {
      this.dropdownTimeout = setTimeout(() => {
        this.activeDropdown = null;
        this.dropdownExpandedSections = [];
        this.currentTriggerElement = null;
      }, 200);
    },
    updateDropdownPosition() {
      if (!this.currentTriggerElement) return;

      const trigger = this.currentTriggerElement;
      const menu = trigger.querySelector('.dropdown-menu, .megamenu-dropdown');

      if (trigger && menu) {
        const rect = trigger.getBoundingClientRect();
        menu.style.top = `${rect.bottom + 4}px`; // 4px margin
        menu.style.left = `${rect.left}px`;
      }
    },
    toggleDropdownSection(sectionId) {
      const index = this.dropdownExpandedSections.indexOf(sectionId);
      if (index > -1) {
        this.dropdownExpandedSections.splice(index, 1);
      } else {
        this.dropdownExpandedSections.push(sectionId);
      }
    },
    isDropdownSectionExpanded(sectionId) {
      return this.dropdownExpandedSections.includes(sectionId);
    },
    // Mobile menu methods
    toggleMobileItem(itemId) {
      const index = this.mobileExpandedItems.indexOf(itemId);
      if (index > -1) {
        this.mobileExpandedItems.splice(index, 1);
      } else {
        this.mobileExpandedItems.push(itemId);
      }
    },
    isMobileItemExpanded(itemId) {
      return this.mobileExpandedItems.includes(itemId);
    },
    // Mega menu methods
    showMegaMenu(item, event) {
      if (this.megaMenuTimeout) {
        clearTimeout(this.megaMenuTimeout);
      }
      this.activeMegaMenu = this.getItemKey(item);
      this.currentTriggerElement = event?.currentTarget;

      // Position megamenu below trigger using fixed positioning
      this.$nextTick(() => {
        this.updateDropdownPosition();
      });
    },
    keepMegaMenuOpen() {
      if (this.megaMenuTimeout) {
        clearTimeout(this.megaMenuTimeout);
      }
    },
    hideMegaMenu() {
      this.megaMenuTimeout = setTimeout(() => {
        this.activeMegaMenu = null;
        this.currentTriggerElement = null;
      }, 200);
    }
  }
});


/***/ },

/***/ "./src/components/NoShareDialog.vue"
/*!******************************************!*\
  !*** ./src/components/NoShareDialog.vue ***!
  \******************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _NoShareDialog_vue_vue_type_template_id_a9f8e2f4_scoped_true__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./NoShareDialog.vue?vue&type=template&id=a9f8e2f4&scoped=true */ "./src/components/NoShareDialog.vue?vue&type=template&id=a9f8e2f4&scoped=true");
/* harmony import */ var _NoShareDialog_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./NoShareDialog.vue?vue&type=script&lang=js */ "./src/components/NoShareDialog.vue?vue&type=script&lang=js");
/* harmony import */ var _NoShareDialog_vue_vue_type_style_index_0_id_a9f8e2f4_scoped_true_lang_css__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./NoShareDialog.vue?vue&type=style&index=0&id=a9f8e2f4&scoped=true&lang=css */ "./src/components/NoShareDialog.vue?vue&type=style&index=0&id=a9f8e2f4&scoped=true&lang=css");
/* harmony import */ var _node_modules_vue_loader_dist_exportHelper_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ../../node_modules/vue-loader/dist/exportHelper.js */ "./node_modules/vue-loader/dist/exportHelper.js");




;


const __exports__ = /*#__PURE__*/(0,_node_modules_vue_loader_dist_exportHelper_js__WEBPACK_IMPORTED_MODULE_3__["default"])(_NoShareDialog_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_1__["default"], [['render',_NoShareDialog_vue_vue_type_template_id_a9f8e2f4_scoped_true__WEBPACK_IMPORTED_MODULE_0__.render],['__scopeId',"data-v-a9f8e2f4"],['__file',"src/components/NoShareDialog.vue"]])
/* hot reload */
if (false) // removed by dead control flow
{}


/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (__exports__);

/***/ },

/***/ "./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/NoShareDialog.vue?vue&type=script&lang=js"
/*!**************************************************************************************************************************************!*\
  !*** ./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/NoShareDialog.vue?vue&type=script&lang=js ***!
  \**************************************************************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _nextcloud_l10n__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @nextcloud/l10n */ "./node_modules/@nextcloud/l10n/dist/index.mjs");
/* harmony import */ var _nextcloud_router__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @nextcloud/router */ "./node_modules/@nextcloud/router/dist/index.mjs");
/* harmony import */ var _nextcloud_vue__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @nextcloud/vue */ "./node_modules/@nextcloud/vue/dist/index.mjs");
/* harmony import */ var vue_material_design_icons_LinkVariantOff_vue__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! vue-material-design-icons/LinkVariantOff.vue */ "./node_modules/vue-material-design-icons/LinkVariantOff.vue");
/* harmony import */ var vue_material_design_icons_FolderOutline_vue__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! vue-material-design-icons/FolderOutline.vue */ "./node_modules/vue-material-design-icons/FolderOutline.vue");
/* harmony import */ var vue_material_design_icons_CogOutline_vue__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! vue-material-design-icons/CogOutline.vue */ "./node_modules/vue-material-design-icons/CogOutline.vue");
/* harmony import */ var vue_material_design_icons_OpenInNew_vue__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! vue-material-design-icons/OpenInNew.vue */ "./node_modules/vue-material-design-icons/OpenInNew.vue");









/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = ({
  name: 'NoShareDialog',
  components: {
    NcDialog: _nextcloud_vue__WEBPACK_IMPORTED_MODULE_2__.NcDialog,
    NcNoteCard: _nextcloud_vue__WEBPACK_IMPORTED_MODULE_2__.NcNoteCard,
    LinkVariantOff: vue_material_design_icons_LinkVariantOff_vue__WEBPACK_IMPORTED_MODULE_3__["default"],
    FolderOutline: vue_material_design_icons_FolderOutline_vue__WEBPACK_IMPORTED_MODULE_4__["default"],
    CogOutline: vue_material_design_icons_CogOutline_vue__WEBPACK_IMPORTED_MODULE_5__["default"],
    OpenInNew: vue_material_design_icons_OpenInNew_vue__WEBPACK_IMPORTED_MODULE_6__["default"]
  },
  props: {
    shareInfo: {
      type: Object,
      required: true
    },
    pageTitle: {
      type: String,
      default: ''
    }
  },
  emits: ['close'],
  computed: {
    isDisabledByAdmin() {
      return this.shareInfo?.reason === 'nc_disabled';
    },
    filesUrl() {
      const url = this.shareInfo?.filesUrl || '';
      if (!url) return null;
      const [path, query] = url.split('?');
      const base = (0,_nextcloud_router__WEBPACK_IMPORTED_MODULE_1__.generateUrl)(path);
      return query ? base + '?' + query : base;
    },
    adminSharingUrl() {
      return (0,_nextcloud_router__WEBPACK_IMPORTED_MODULE_1__.generateUrl)('/settings/admin/sharing');
    }
  },
  methods: {
    t(key, vars = {}) {
      return (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_0__.translate)('intravox', key, vars);
    }
  }
});


/***/ },

/***/ "./src/components/PageActionsMenu.vue"
/*!********************************************!*\
  !*** ./src/components/PageActionsMenu.vue ***!
  \********************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _PageActionsMenu_vue_vue_type_template_id_457bfd6d_scoped_true__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./PageActionsMenu.vue?vue&type=template&id=457bfd6d&scoped=true */ "./src/components/PageActionsMenu.vue?vue&type=template&id=457bfd6d&scoped=true");
/* harmony import */ var _PageActionsMenu_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./PageActionsMenu.vue?vue&type=script&lang=js */ "./src/components/PageActionsMenu.vue?vue&type=script&lang=js");
/* harmony import */ var _PageActionsMenu_vue_vue_type_style_index_0_id_457bfd6d_scoped_true_lang_css__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./PageActionsMenu.vue?vue&type=style&index=0&id=457bfd6d&scoped=true&lang=css */ "./src/components/PageActionsMenu.vue?vue&type=style&index=0&id=457bfd6d&scoped=true&lang=css");
/* harmony import */ var _node_modules_vue_loader_dist_exportHelper_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ../../node_modules/vue-loader/dist/exportHelper.js */ "./node_modules/vue-loader/dist/exportHelper.js");




;


const __exports__ = /*#__PURE__*/(0,_node_modules_vue_loader_dist_exportHelper_js__WEBPACK_IMPORTED_MODULE_3__["default"])(_PageActionsMenu_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_1__["default"], [['render',_PageActionsMenu_vue_vue_type_template_id_457bfd6d_scoped_true__WEBPACK_IMPORTED_MODULE_0__.render],['__scopeId',"data-v-457bfd6d"],['__file',"src/components/PageActionsMenu.vue"]])
/* hot reload */
if (false) // removed by dead control flow
{}


/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (__exports__);

/***/ },

/***/ "./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/PageActionsMenu.vue?vue&type=script&lang=js"
/*!****************************************************************************************************************************************!*\
  !*** ./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/PageActionsMenu.vue?vue&type=script&lang=js ***!
  \****************************************************************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _nextcloud_l10n__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @nextcloud/l10n */ "./node_modules/@nextcloud/l10n/dist/index.mjs");
/* harmony import */ var _nextcloud_vue__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @nextcloud/vue */ "./node_modules/@nextcloud/vue/dist/index.mjs");
/* harmony import */ var vue_material_design_icons_Cog_vue__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! vue-material-design-icons/Cog.vue */ "./node_modules/vue-material-design-icons/Cog.vue");
/* harmony import */ var vue_material_design_icons_Plus_vue__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! vue-material-design-icons/Plus.vue */ "./node_modules/vue-material-design-icons/Plus.vue");
/* harmony import */ var vue_material_design_icons_TuneVertical_vue__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! vue-material-design-icons/TuneVertical.vue */ "./node_modules/vue-material-design-icons/TuneVertical.vue");
/* harmony import */ var vue_material_design_icons_FileDocumentMultipleOutline_vue__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! vue-material-design-icons/FileDocumentMultipleOutline.vue */ "./node_modules/vue-material-design-icons/FileDocumentMultipleOutline.vue");
/* harmony import */ var vue_material_design_icons_Rss_vue__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! vue-material-design-icons/Rss.vue */ "./node_modules/vue-material-design-icons/Rss.vue");









/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = ({
  name: 'PageActionsMenu',
  components: {
    NcActions: _nextcloud_vue__WEBPACK_IMPORTED_MODULE_1__.NcActions,
    NcActionButton: _nextcloud_vue__WEBPACK_IMPORTED_MODULE_1__.NcActionButton,
    Cog: vue_material_design_icons_Cog_vue__WEBPACK_IMPORTED_MODULE_2__["default"],
    Plus: vue_material_design_icons_Plus_vue__WEBPACK_IMPORTED_MODULE_3__["default"],
    TuneVertical: vue_material_design_icons_TuneVertical_vue__WEBPACK_IMPORTED_MODULE_4__["default"],
    FileDocumentMultipleOutline: vue_material_design_icons_FileDocumentMultipleOutline_vue__WEBPACK_IMPORTED_MODULE_5__["default"],
    Rss: vue_material_design_icons_Rss_vue__WEBPACK_IMPORTED_MODULE_6__["default"]
  },
  props: {
    isEditMode: {
      type: Boolean,
      default: false
    },
    permissions: {
      type: Object,
      default: () => ({
        editNavigation: false,
        viewPages: true,      // Everyone can view pages
        createPage: false,
        editPage: false,
        deletePage: false     // For future use
      })
    }
  },
  emits: ['edit-navigation', 'create-page', 'page-settings', 'save-as-template', 'feed-settings'],
  methods: {
    t(key, vars = {}) {
      return (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_0__.translate)('intravox', key, vars);
    },
    /**
     * Check if user can perform a specific action
     * This method can be extended in the future to include more complex logic
     * like role-based permissions (admin, editor, viewer)
     */
    canPerformAction(action) {
      return this.permissions[action] === true;
    }
  }
});


/***/ },

/***/ "./src/components/PageViewer.vue"
/*!***************************************!*\
  !*** ./src/components/PageViewer.vue ***!
  \***************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _PageViewer_vue_vue_type_template_id_3a22e99e_scoped_true__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./PageViewer.vue?vue&type=template&id=3a22e99e&scoped=true */ "./src/components/PageViewer.vue?vue&type=template&id=3a22e99e&scoped=true");
/* harmony import */ var _PageViewer_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./PageViewer.vue?vue&type=script&lang=js */ "./src/components/PageViewer.vue?vue&type=script&lang=js");
/* harmony import */ var _PageViewer_vue_vue_type_style_index_0_id_3a22e99e_scoped_true_lang_css__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./PageViewer.vue?vue&type=style&index=0&id=3a22e99e&scoped=true&lang=css */ "./src/components/PageViewer.vue?vue&type=style&index=0&id=3a22e99e&scoped=true&lang=css");
/* harmony import */ var _node_modules_vue_loader_dist_exportHelper_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ../../node_modules/vue-loader/dist/exportHelper.js */ "./node_modules/vue-loader/dist/exportHelper.js");




;


const __exports__ = /*#__PURE__*/(0,_node_modules_vue_loader_dist_exportHelper_js__WEBPACK_IMPORTED_MODULE_3__["default"])(_PageViewer_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_1__["default"], [['render',_PageViewer_vue_vue_type_template_id_3a22e99e_scoped_true__WEBPACK_IMPORTED_MODULE_0__.render],['__scopeId',"data-v-3a22e99e"],['__file',"src/components/PageViewer.vue"]])
/* hot reload */
if (false) // removed by dead control flow
{}


/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (__exports__);

/***/ },

/***/ "./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/PageViewer.vue?vue&type=script&lang=js"
/*!***********************************************************************************************************************************!*\
  !*** ./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/PageViewer.vue?vue&type=script&lang=js ***!
  \***********************************************************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _Widget_vue__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./Widget.vue */ "./src/components/Widget.vue");
/* harmony import */ var _reactions_ReactionBar_vue__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./reactions/ReactionBar.vue */ "./src/components/reactions/ReactionBar.vue");
/* harmony import */ var _reactions_CommentSection_vue__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./reactions/CommentSection.vue */ "./src/components/reactions/CommentSection.vue");
/* harmony import */ var _services_CommentService_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ../services/CommentService.js */ "./src/services/CommentService.js");
/* harmony import */ var vue_material_design_icons_ChevronDown_vue__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! vue-material-design-icons/ChevronDown.vue */ "./node_modules/vue-material-design-icons/ChevronDown.vue");
/* harmony import */ var vue_material_design_icons_ChevronRight_vue__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! vue-material-design-icons/ChevronRight.vue */ "./node_modules/vue-material-design-icons/ChevronRight.vue");








/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = ({
  name: 'PageViewer',
  components: {
    Widget: _Widget_vue__WEBPACK_IMPORTED_MODULE_0__["default"],
    ReactionBar: _reactions_ReactionBar_vue__WEBPACK_IMPORTED_MODULE_1__["default"],
    CommentSection: _reactions_CommentSection_vue__WEBPACK_IMPORTED_MODULE_2__["default"],
    ChevronDown: vue_material_design_icons_ChevronDown_vue__WEBPACK_IMPORTED_MODULE_4__["default"],
    ChevronRight: vue_material_design_icons_ChevronRight_vue__WEBPACK_IMPORTED_MODULE_5__["default"]
  },
  props: {
    page: {
      type: Object,
      required: true
    },
    engagementSettings: {
      type: Object,
      default: () => ({
        allowPageReactions: true,
        allowComments: true,
        allowCommentReactions: true,
        singleReactionPerUser: true
      })
    },
    shareToken: {
      type: String,
      default: ''
    },
    isPublic: {
      type: Boolean,
      default: false
    }
  },
  emits: ['navigate'],
  data() {
    return {
      pageReactions: {},
      userReactions: [],
      collapsedRows: {} // Track collapsed state for collapsible rows in view mode
    };
  },
  computed: {
    hasHeaderRow() {
      return this.page?.layout?.headerRow?.enabled &&
             this.page.layout.headerRow.widgets?.length > 0;
    },
    hasLeftColumn() {
      return this.page?.layout?.sideColumns?.left?.enabled &&
             this.page.layout.sideColumns.left.widgets?.length > 0;
    },
    hasRightColumn() {
      return this.page?.layout?.sideColumns?.right?.enabled &&
             this.page.layout.sideColumns.right.widgets?.length > 0;
    },
    /**
     * Check if reactions are allowed on this page
     * Takes into account both global settings and page-level overrides
     */
    showReactions() {
      // Global setting off = no reactions anywhere
      if (!this.engagementSettings.allowPageReactions) {
        return false;
      }
      // Check page-level override
      const pageSetting = this.page?.settings?.allowReactions;
      if (pageSetting === false) {
        return false;
      }
      // Default: show reactions
      return true;
    },
    /**
     * Check if comments are allowed on this page
     * Takes into account both global settings and page-level overrides
     */
    showComments() {
      // Global setting off = no comments anywhere
      if (!this.engagementSettings.allowComments) {
        return false;
      }
      // Check page-level override
      const pageSetting = this.page?.settings?.allowComments;
      if (pageSetting === false) {
        return false;
      }
      // Default: show comments
      return true;
    },
    /**
     * Check if reactions on comments are allowed
     * Takes into account both global settings and page-level overrides
     */
    allowCommentReactions() {
      // Global setting off = no comment reactions anywhere
      if (!this.engagementSettings.allowCommentReactions) {
        return false;
      }
      // Check page-level override
      const pageSetting = this.page?.settings?.allowCommentReactions;
      if (pageSetting === false) {
        return false;
      }
      // Default: allow comment reactions
      return true;
    }
  },
  methods: {
    getWidgetsForColumn(row, column) {
      if (!row.widgets) return [];

      // Filter widgets by column and sort by order
      return row.widgets
        .filter(w => w.column === column)
        .sort((a, b) => a.order - b.order);
    },
    isRowCollapsed(row) {
      // Check if this collapsible row is currently collapsed in view mode
      if (!row.collapsible) return false;
      // Use row.id as key, fallback to row's index position
      const rowKey = row.id || `row-${this.page.layout.rows.indexOf(row)}`;
      // Use stored state if available, otherwise use defaultCollapsed setting
      return this.collapsedRows[rowKey] ?? row.defaultCollapsed ?? false;
    },
    toggleRowCollapsed(row) {
      if (!row.collapsible) return;
      const rowKey = row.id || `row-${this.page.layout.rows.indexOf(row)}`;
      // Get current state (from stored state, or defaultCollapsed, or false)
      const currentState = this.collapsedRows[rowKey] ?? row.defaultCollapsed ?? false;
      this.collapsedRows = {
        ...this.collapsedRows,
        [rowKey]: !currentState
      };
    },
    getSideColumnWidgets(side) {
      const sideColumn = this.page?.layout?.sideColumns?.[side];
      if (!sideColumn || !sideColumn.widgets) return [];
      return [...sideColumn.widgets].sort((a, b) => (a.order || 0) - (b.order || 0));
    },
    getRowStyle(row) {
      const style = {};

      if (row.backgroundColor) {
        style.backgroundColor = row.backgroundColor;

        // Set text color based on background color
        // Only Primary (dark green) needs white text
        if (row.backgroundColor === 'var(--color-primary-element)') {
          style.color = 'var(--color-primary-element-text)';
        } else {
          // Light backgrounds: use default dark text
          style.color = 'var(--color-main-text)';
        }
      }

      return style;
    },
    getSideColumnStyle(side) {
      const sideColumn = this.page?.layout?.sideColumns?.[side];
      if (!sideColumn) return {};

      const style = {};

      if (sideColumn.backgroundColor) {
        style.backgroundColor = sideColumn.backgroundColor;

        // Set text color based on background color
        if (sideColumn.backgroundColor === 'var(--color-primary-element)') {
          style.color = 'var(--color-primary-element-text)';
        } else {
          style.color = 'var(--color-main-text)';
        }
      } else {
        // Explicitly set transparent to override CSS default
        style.backgroundColor = 'transparent';
      }

      return style;
    },
    getHeaderRowWidgets() {
      const headerRow = this.page?.layout?.headerRow;
      if (!headerRow || !headerRow.widgets) return [];
      return [...headerRow.widgets].sort((a, b) => (a.order || 0) - (b.order || 0));
    },
    getHeaderRowStyle() {
      const headerRow = this.page?.layout?.headerRow;
      if (!headerRow) return {};

      const style = {};

      if (headerRow.backgroundColor) {
        style.backgroundColor = headerRow.backgroundColor;

        if (headerRow.backgroundColor === 'var(--color-primary-element)') {
          style.color = 'var(--color-primary-element-text)';
        } else {
          style.color = 'var(--color-main-text)';
        }
      } else {
        // Explicitly set transparent to override CSS default
        style.backgroundColor = 'transparent';
      }

      return style;
    },
    async loadReactions() {
      if (!this.page?.uniqueId) return;
      try {
        const result = await (0,_services_CommentService_js__WEBPACK_IMPORTED_MODULE_3__.getPageReactions)(this.page.uniqueId);
        this.pageReactions = result.reactions || {};
        this.userReactions = result.userReactions || [];
      } catch (error) {
        console.error('Failed to load reactions:', error);
      }
    },
    handleReactionsUpdate(result) {
      this.pageReactions = result.reactions || {};
      this.userReactions = result.userReactions || [];
    }
  },
  watch: {
    'page.uniqueId': {
      immediate: true,
      handler() {
        this.loadReactions();
      }
    }
  }
});


/***/ },

/***/ "./src/components/PublicPageView.vue"
/*!*******************************************!*\
  !*** ./src/components/PublicPageView.vue ***!
  \*******************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _PublicPageView_vue_vue_type_template_id_5157670d_scoped_true__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./PublicPageView.vue?vue&type=template&id=5157670d&scoped=true */ "./src/components/PublicPageView.vue?vue&type=template&id=5157670d&scoped=true");
/* harmony import */ var _PublicPageView_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./PublicPageView.vue?vue&type=script&lang=js */ "./src/components/PublicPageView.vue?vue&type=script&lang=js");
/* harmony import */ var _PublicPageView_vue_vue_type_style_index_0_id_5157670d_scoped_true_lang_css__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./PublicPageView.vue?vue&type=style&index=0&id=5157670d&scoped=true&lang=css */ "./src/components/PublicPageView.vue?vue&type=style&index=0&id=5157670d&scoped=true&lang=css");
/* harmony import */ var _node_modules_vue_loader_dist_exportHelper_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ../../node_modules/vue-loader/dist/exportHelper.js */ "./node_modules/vue-loader/dist/exportHelper.js");




;


const __exports__ = /*#__PURE__*/(0,_node_modules_vue_loader_dist_exportHelper_js__WEBPACK_IMPORTED_MODULE_3__["default"])(_PublicPageView_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_1__["default"], [['render',_PublicPageView_vue_vue_type_template_id_5157670d_scoped_true__WEBPACK_IMPORTED_MODULE_0__.render],['__scopeId',"data-v-5157670d"],['__file',"src/components/PublicPageView.vue"]])
/* hot reload */
if (false) // removed by dead control flow
{}


/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (__exports__);

/***/ },

/***/ "./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/PublicPageView.vue?vue&type=script&lang=js"
/*!***************************************************************************************************************************************!*\
  !*** ./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/PublicPageView.vue?vue&type=script&lang=js ***!
  \***************************************************************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var vue__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! vue */ "./node_modules/vue/dist/vue.runtime.esm-bundler.js");
/* harmony import */ var _nextcloud_l10n__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @nextcloud/l10n */ "./node_modules/@nextcloud/l10n/dist/index.mjs");
/* harmony import */ var _nextcloud_router__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @nextcloud/router */ "./node_modules/@nextcloud/router/dist/index.mjs");
/* harmony import */ var _nextcloud_axios__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! @nextcloud/axios */ "./node_modules/@nextcloud/axios/dist/index.js");
/* harmony import */ var _PageViewer_vue__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ./PageViewer.vue */ "./src/components/PageViewer.vue");
/* harmony import */ var _Navigation_vue__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! ./Navigation.vue */ "./src/components/Navigation.vue");
/* harmony import */ var _Footer_vue__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! ./Footer.vue */ "./src/components/Footer.vue");
/* harmony import */ var _Breadcrumb_vue__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! ./Breadcrumb.vue */ "./src/components/Breadcrumb.vue");










/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = ({
	name: 'PublicPageView',
	components: {
		PageViewer: _PageViewer_vue__WEBPACK_IMPORTED_MODULE_4__["default"],
		Navigation: _Navigation_vue__WEBPACK_IMPORTED_MODULE_5__["default"],
		Footer: _Footer_vue__WEBPACK_IMPORTED_MODULE_6__["default"],
		Breadcrumb: _Breadcrumb_vue__WEBPACK_IMPORTED_MODULE_7__["default"],
		// Async to match App.vue's strategy. See scripts/check-import-consistency.js.
		PageTreeModal: (0,vue__WEBPACK_IMPORTED_MODULE_0__.defineAsyncComponent)(() => Promise.all(/*! import() */[__webpack_require__.e("vendors"), __webpack_require__.e("src_components_PageTreeModal_vue")]).then(__webpack_require__.bind(__webpack_require__, /*! ./PageTreeModal.vue */ "./src/components/PageTreeModal.vue"))),
	},
	props: {
		initialPageData: {
			type: Object,
			default: null,
		},
		token: {
			type: String,
			required: true,
		},
		pageUniqueId: {
			type: String,
			default: '',
		},
	},
	data() {
		return {
			pageData: null,
			navigation: null,
			footerContent: '',
			breadcrumb: [],
			showPageTree: false,
			loading: true,
			error: null,
			passwordRequired: false,
			password: '',
			passwordError: false,
			submittingPassword: false,
			// Disabled engagement settings for public pages
			engagementSettings: {
				allowPageReactions: false,
				allowComments: false,
				allowCommentReactions: false,
			},
		}
	},
	computed: {
		currentPageUniqueId() {
			return this.pageData?.uniqueId || this.pageUniqueId || ''
		},
	},
	async mounted() {
		// Use initial data if provided (from PHP template)
		if (this.initialPageData) {
			this.pageData = this.initialPageData
			this.loading = false
		}

		// Load page data from API (or refresh)
		await this.loadPage()

		// Load navigation filtered by share scope
		if (this.pageData && this.token) {
			await this.loadNavigation()
		}
	},
	methods: {
		t(app, key, vars = {}) {
			return (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_1__.translate)(app, key, vars)
		},
		async loadPage() {
			try {
				// Determine page to load: prop > query param > hash > homepage fallback
				let uniqueId = this.pageUniqueId || this.getPageIdFromUrl()
				if (!uniqueId) {
					// No page specified — try to load the homepage from the page tree
					uniqueId = await this.getHomepageUniqueId()
					if (!uniqueId) {
						this.error = (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_1__.translate)('intravox', 'No page specified.')
						this.loading = false
						return
					}
				}

				const response = await _nextcloud_axios__WEBPACK_IMPORTED_MODULE_3__["default"].get(
					(0,_nextcloud_router__WEBPACK_IMPORTED_MODULE_2__.generateUrl)('/apps/intravox/api/share/{token}/page/{uniqueId}', {
						token: this.token,
						uniqueId,
					}),
				)
				this.pageData = response.data
				this.breadcrumb = response.data.breadcrumb || []
				this.error = null
				// Update URL hash for back/forward navigation
				this.updateUrl(uniqueId)
			} catch (err) {
				if (err.response?.status === 401 && err.response?.data?.passwordRequired) {
					this.passwordRequired = true
					this.$nextTick(() => this.$refs.passwordInput?.focus())
				} else if (!this.pageData) {
					this.error = (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_1__.translate)('intravox', 'This page is not available or the share link has expired.')
				}
			} finally {
				this.loading = false
			}
		},
		getPageIdFromUrl() {
			// Check ?page= query parameter first (survives chat apps, email clients)
			const params = new URLSearchParams(window.location.search)
			const pageFromQuery = params.get('page')
			if (pageFromQuery) {
				return pageFromQuery
			}
			// Fall back to hash (legacy URLs)
			const hash = window.location.hash
			if (hash.startsWith('#page-')) {
				return hash.substring(1)
			}
			if (hash.length > 1) {
				return hash.substring(1)
			}
			return ''
		},
		async getHomepageUniqueId() {
			try {
				const response = await _nextcloud_axios__WEBPACK_IMPORTED_MODULE_3__["default"].get(
					(0,_nextcloud_router__WEBPACK_IMPORTED_MODULE_2__.generateUrl)('/apps/intravox/api/share/{token}/tree', { token: this.token }),
				)
				const tree = response.data?.tree || []
				// First item in the tree is typically the homepage
				if (tree.length > 0 && tree[0].uniqueId) {
					return tree[0].uniqueId
				}
			} catch (err) {
				// Page tree is optional, fail silently
			}
			return ''
		},
		updateUrl(uniqueId) {
			if (uniqueId) {
				// Keep ?page= as the canonical format (survives sharing via chat/email)
				// Remove any legacy hash fragment
				const url = new URL(window.location.href)
				url.searchParams.set('page', uniqueId)
				url.hash = ''
				window.history.replaceState(null, '', url.toString())
			}
		},
		async loadNavigation() {
			try {
				const response = await _nextcloud_axios__WEBPACK_IMPORTED_MODULE_3__["default"].get(
					(0,_nextcloud_router__WEBPACK_IMPORTED_MODULE_2__.generateUrl)('/apps/intravox/api/share/{token}/navigation', { token: this.token }),
				)
				this.navigation = response.data.navigation
			} catch (err) {
				// Navigation is optional, fail silently
				this.navigation = null
			}
		},
		async loadFooter() {
			try {
				const response = await _nextcloud_axios__WEBPACK_IMPORTED_MODULE_3__["default"].get(
					(0,_nextcloud_router__WEBPACK_IMPORTED_MODULE_2__.generateUrl)('/apps/intravox/public/api/footer/{token}', { token: this.token }),
				)
				this.footerContent = response.data.content || ''
			} catch (err) {
				// Footer is optional, fail silently
				this.footerContent = ''
			}
		},
		handleNavigation(item) {
			if (item.uniqueId) {
				this.loadPageByUniqueId(item.uniqueId)
			} else if (item.url) {
				if (item.url.startsWith('http') || item.url.startsWith('//')) {
					window.open(item.url, item.target || '_blank')
				} else {
					window.location.href = item.url
				}
			}
		},
		handleBreadcrumbNavigate(pageId) {
			if (pageId) {
				this.loadPageByUniqueId(pageId)
			}
		},
		handleTreeNavigate(uniqueId) {
			this.showPageTree = false
			if (uniqueId) {
				this.loadPageByUniqueId(uniqueId)
			}
		},
		async loadPageByUniqueId(uniqueId) {
			try {
				this.loading = true
				const response = await _nextcloud_axios__WEBPACK_IMPORTED_MODULE_3__["default"].get(
					(0,_nextcloud_router__WEBPACK_IMPORTED_MODULE_2__.generateUrl)('/apps/intravox/api/share/{token}/page/{uniqueId}', {
						token: this.token,
						uniqueId,
					}),
				)
				this.pageData = response.data
				this.breadcrumb = response.data.breadcrumb || []
				this.error = null
				this.updateUrl(uniqueId)
				window.scrollTo(0, 0)
			} catch (err) {
				if (err.response?.status === 401 && err.response?.data?.passwordRequired) {
					this.passwordRequired = true
					this.$nextTick(() => this.$refs.passwordInput?.focus())
				} else {
					this.error = (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_1__.translate)('intravox', 'This page is not available or the share link has expired.')
				}
			} finally {
				this.loading = false
			}
		},
		async submitPassword() {
			this.submittingPassword = true
			this.passwordError = false
			try {
				const authenticateUrl = (0,_nextcloud_router__WEBPACK_IMPORTED_MODULE_2__.generateUrl)('/apps/intravox/s/{shareToken}/authenticate', {
					shareToken: this.token,
				})
				await _nextcloud_axios__WEBPACK_IMPORTED_MODULE_3__["default"].post(authenticateUrl, {
					password: this.password,
				}, {
					headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
					maxRedirects: 0,
					validateStatus: (status) => status < 400,
				})
				// Password accepted — reload the page to pick up the session cookie
				window.location.reload()
			} catch (err) {
				this.passwordError = true
				this.password = ''
				this.$nextTick(() => this.$refs.passwordInput?.focus())
			} finally {
				this.submittingPassword = false
			}
		},
	},
});


/***/ },

/***/ "./src/components/ShareButton.vue"
/*!****************************************!*\
  !*** ./src/components/ShareButton.vue ***!
  \****************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _ShareButton_vue_vue_type_template_id_29169d71_scoped_true__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./ShareButton.vue?vue&type=template&id=29169d71&scoped=true */ "./src/components/ShareButton.vue?vue&type=template&id=29169d71&scoped=true");
/* harmony import */ var _ShareButton_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./ShareButton.vue?vue&type=script&lang=js */ "./src/components/ShareButton.vue?vue&type=script&lang=js");
/* harmony import */ var _ShareButton_vue_vue_type_style_index_0_id_29169d71_scoped_true_lang_css__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./ShareButton.vue?vue&type=style&index=0&id=29169d71&scoped=true&lang=css */ "./src/components/ShareButton.vue?vue&type=style&index=0&id=29169d71&scoped=true&lang=css");
/* harmony import */ var _node_modules_vue_loader_dist_exportHelper_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ../../node_modules/vue-loader/dist/exportHelper.js */ "./node_modules/vue-loader/dist/exportHelper.js");




;


const __exports__ = /*#__PURE__*/(0,_node_modules_vue_loader_dist_exportHelper_js__WEBPACK_IMPORTED_MODULE_3__["default"])(_ShareButton_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_1__["default"], [['render',_ShareButton_vue_vue_type_template_id_29169d71_scoped_true__WEBPACK_IMPORTED_MODULE_0__.render],['__scopeId',"data-v-29169d71"],['__file',"src/components/ShareButton.vue"]])
/* hot reload */
if (false) // removed by dead control flow
{}


/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (__exports__);

/***/ },

/***/ "./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/ShareButton.vue?vue&type=script&lang=js"
/*!************************************************************************************************************************************!*\
  !*** ./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/ShareButton.vue?vue&type=script&lang=js ***!
  \************************************************************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _nextcloud_axios__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @nextcloud/axios */ "./node_modules/@nextcloud/axios/dist/index.js");
/* harmony import */ var _nextcloud_router__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @nextcloud/router */ "./node_modules/@nextcloud/router/dist/index.mjs");
/* harmony import */ var _nextcloud_l10n__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @nextcloud/l10n */ "./node_modules/@nextcloud/l10n/dist/index.mjs");
/* harmony import */ var _nextcloud_vue__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! @nextcloud/vue */ "./node_modules/@nextcloud/vue/dist/index.mjs");
/* harmony import */ var vue_material_design_icons_LinkVariant_vue__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! vue-material-design-icons/LinkVariant.vue */ "./node_modules/vue-material-design-icons/LinkVariant.vue");
/* harmony import */ var vue_material_design_icons_LinkVariantOff_vue__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! vue-material-design-icons/LinkVariantOff.vue */ "./node_modules/vue-material-design-icons/LinkVariantOff.vue");
/* harmony import */ var _ShareDialog_vue__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! ./ShareDialog.vue */ "./src/components/ShareDialog.vue");
/* harmony import */ var _NoShareDialog_vue__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! ./NoShareDialog.vue */ "./src/components/NoShareDialog.vue");










/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = ({
  name: 'ShareButton',
  components: {
    NcButton: _nextcloud_vue__WEBPACK_IMPORTED_MODULE_3__.NcButton,
    LinkVariant: vue_material_design_icons_LinkVariant_vue__WEBPACK_IMPORTED_MODULE_4__["default"],
    LinkVariantOff: vue_material_design_icons_LinkVariantOff_vue__WEBPACK_IMPORTED_MODULE_5__["default"],
    ShareDialog: _ShareDialog_vue__WEBPACK_IMPORTED_MODULE_6__["default"],
    NoShareDialog: _NoShareDialog_vue__WEBPACK_IMPORTED_MODULE_7__["default"]
  },
  props: {
    pageUniqueId: {
      type: String,
      required: true
    },
    pageTitle: {
      type: String,
      default: ''
    },
    language: {
      type: String,
      default: 'en'
    }
  },
  data() {
    return {
      shareInfo: null,
      loading: false,
      showDialog: false,
      showNoShareDialog: false,
      error: null
    };
  },
  computed: {
    hasShare() {
      return this.shareInfo?.hasShare === true;
    }
  },
  watch: {
    pageUniqueId: {
      immediate: true,
      handler(newId) {
        if (newId) {
          this.loadShareInfo();
        }
      }
    }
  },
  methods: {
    t(key, vars = {}) {
      return (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_2__.translate)('intravox', key, vars);
    },
    async loadShareInfo() {
      if (!this.pageUniqueId) {
        return;
      }

      this.loading = true;
      this.error = null;

      try {
        const response = await _nextcloud_axios__WEBPACK_IMPORTED_MODULE_0__["default"].get(
          (0,_nextcloud_router__WEBPACK_IMPORTED_MODULE_1__.generateUrl)('/apps/intravox/api/pages/{uniqueId}/share-info', {
            uniqueId: this.pageUniqueId
          })
        );
        this.shareInfo = response.data;
      } catch (err) {
        this.shareInfo = null;
        this.error = err.message;
      } finally {
        this.loading = false;
      }
    }
  }
});


/***/ },

/***/ "./src/components/ShareDialog.vue"
/*!****************************************!*\
  !*** ./src/components/ShareDialog.vue ***!
  \****************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _ShareDialog_vue_vue_type_template_id_7dad0ef2_scoped_true__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./ShareDialog.vue?vue&type=template&id=7dad0ef2&scoped=true */ "./src/components/ShareDialog.vue?vue&type=template&id=7dad0ef2&scoped=true");
/* harmony import */ var _ShareDialog_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./ShareDialog.vue?vue&type=script&lang=js */ "./src/components/ShareDialog.vue?vue&type=script&lang=js");
/* harmony import */ var _ShareDialog_vue_vue_type_style_index_0_id_7dad0ef2_scoped_true_lang_css__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./ShareDialog.vue?vue&type=style&index=0&id=7dad0ef2&scoped=true&lang=css */ "./src/components/ShareDialog.vue?vue&type=style&index=0&id=7dad0ef2&scoped=true&lang=css");
/* harmony import */ var _node_modules_vue_loader_dist_exportHelper_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ../../node_modules/vue-loader/dist/exportHelper.js */ "./node_modules/vue-loader/dist/exportHelper.js");




;


const __exports__ = /*#__PURE__*/(0,_node_modules_vue_loader_dist_exportHelper_js__WEBPACK_IMPORTED_MODULE_3__["default"])(_ShareDialog_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_1__["default"], [['render',_ShareDialog_vue_vue_type_template_id_7dad0ef2_scoped_true__WEBPACK_IMPORTED_MODULE_0__.render],['__scopeId',"data-v-7dad0ef2"],['__file',"src/components/ShareDialog.vue"]])
/* hot reload */
if (false) // removed by dead control flow
{}


/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (__exports__);

/***/ },

/***/ "./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/ShareDialog.vue?vue&type=script&lang=js"
/*!************************************************************************************************************************************!*\
  !*** ./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/ShareDialog.vue?vue&type=script&lang=js ***!
  \************************************************************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _nextcloud_l10n__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @nextcloud/l10n */ "./node_modules/@nextcloud/l10n/dist/index.mjs");
/* harmony import */ var _nextcloud_router__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @nextcloud/router */ "./node_modules/@nextcloud/router/dist/index.mjs");
/* harmony import */ var _nextcloud_dialogs__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @nextcloud/dialogs */ "./node_modules/@nextcloud/dialogs/dist/index.mjs");
/* harmony import */ var _nextcloud_vue__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! @nextcloud/vue */ "./node_modules/@nextcloud/vue/dist/index.mjs");
/* harmony import */ var vue_material_design_icons_ContentCopy_vue__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! vue-material-design-icons/ContentCopy.vue */ "./node_modules/vue-material-design-icons/ContentCopy.vue");
/* harmony import */ var vue_material_design_icons_FolderOutline_vue__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! vue-material-design-icons/FolderOutline.vue */ "./node_modules/vue-material-design-icons/FolderOutline.vue");
/* harmony import */ var vue_material_design_icons_FileDocumentOutline_vue__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! vue-material-design-icons/FileDocumentOutline.vue */ "./node_modules/vue-material-design-icons/FileDocumentOutline.vue");
/* harmony import */ var vue_material_design_icons_LockOutline_vue__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! vue-material-design-icons/LockOutline.vue */ "./node_modules/vue-material-design-icons/LockOutline.vue");
/* harmony import */ var vue_material_design_icons_OpenInNew_vue__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__(/*! vue-material-design-icons/OpenInNew.vue */ "./node_modules/vue-material-design-icons/OpenInNew.vue");











/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = ({
  name: 'ShareDialog',
  components: {
    NcDialog: _nextcloud_vue__WEBPACK_IMPORTED_MODULE_3__.NcDialog,
    NcButton: _nextcloud_vue__WEBPACK_IMPORTED_MODULE_3__.NcButton,
    NcNoteCard: _nextcloud_vue__WEBPACK_IMPORTED_MODULE_3__.NcNoteCard,
    ContentCopy: vue_material_design_icons_ContentCopy_vue__WEBPACK_IMPORTED_MODULE_4__["default"],
    FolderOutline: vue_material_design_icons_FolderOutline_vue__WEBPACK_IMPORTED_MODULE_5__["default"],
    FileDocumentOutline: vue_material_design_icons_FileDocumentOutline_vue__WEBPACK_IMPORTED_MODULE_6__["default"],
    LockOutline: vue_material_design_icons_LockOutline_vue__WEBPACK_IMPORTED_MODULE_7__["default"],
    OpenInNew: vue_material_design_icons_OpenInNew_vue__WEBPACK_IMPORTED_MODULE_8__["default"]
  },
  props: {
    shareInfo: {
      type: Object,
      required: true
    },
    pageTitle: {
      type: String,
      default: ''
    }
  },
  emits: ['close'],
  computed: {
    publicUrl() {
      const baseUrl = window.location.origin;
      return baseUrl + this.shareInfo.publicUrl;
    },
    filesUrl() {
      // Split path and query, use generateUrl for the path part only
      const url = this.shareInfo.filesUrl || '';
      const [path, query] = url.split('?');
      const base = (0,_nextcloud_router__WEBPACK_IMPORTED_MODULE_1__.generateUrl)(path);
      return query ? base + '?' + query : base;
    }
  },
  methods: {
    t(key, vars = {}) {
      return (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_0__.translate)('intravox', key, vars);
    },
    async copyUrl() {
      try {
        await navigator.clipboard.writeText(this.publicUrl);
        (0,_nextcloud_dialogs__WEBPACK_IMPORTED_MODULE_2__.showSuccess)(this.t('Link copied to clipboard'));
      } catch (err) {
        const textArea = document.createElement('textarea');
        textArea.value = this.publicUrl;
        document.body.appendChild(textArea);
        textArea.select();
        try {
          document.execCommand('copy');
          (0,_nextcloud_dialogs__WEBPACK_IMPORTED_MODULE_2__.showSuccess)(this.t('Link copied to clipboard'));
        } catch (e) {
          (0,_nextcloud_dialogs__WEBPACK_IMPORTED_MODULE_2__.showError)(this.t('Failed to copy link'));
        }
        document.body.removeChild(textArea);
      }
    }
  }
});


/***/ },

/***/ "./src/components/Widget.vue"
/*!***********************************!*\
  !*** ./src/components/Widget.vue ***!
  \***********************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _Widget_vue_vue_type_template_id_4ca18318_scoped_true__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./Widget.vue?vue&type=template&id=4ca18318&scoped=true */ "./src/components/Widget.vue?vue&type=template&id=4ca18318&scoped=true");
/* harmony import */ var _Widget_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./Widget.vue?vue&type=script&lang=js */ "./src/components/Widget.vue?vue&type=script&lang=js");
/* harmony import */ var _Widget_vue_vue_type_style_index_0_id_4ca18318_scoped_true_lang_css__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./Widget.vue?vue&type=style&index=0&id=4ca18318&scoped=true&lang=css */ "./src/components/Widget.vue?vue&type=style&index=0&id=4ca18318&scoped=true&lang=css");
/* harmony import */ var _node_modules_vue_loader_dist_exportHelper_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ../../node_modules/vue-loader/dist/exportHelper.js */ "./node_modules/vue-loader/dist/exportHelper.js");




;


const __exports__ = /*#__PURE__*/(0,_node_modules_vue_loader_dist_exportHelper_js__WEBPACK_IMPORTED_MODULE_3__["default"])(_Widget_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_1__["default"], [['render',_Widget_vue_vue_type_template_id_4ca18318_scoped_true__WEBPACK_IMPORTED_MODULE_0__.render],['__scopeId',"data-v-4ca18318"],['__file',"src/components/Widget.vue"]])
/* hot reload */
if (false) // removed by dead control flow
{}


/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (__exports__);

/***/ },

/***/ "./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/Widget.vue?vue&type=script&lang=js"
/*!*******************************************************************************************************************************!*\
  !*** ./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/Widget.vue?vue&type=script&lang=js ***!
  \*******************************************************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var vue__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! vue */ "./node_modules/vue/dist/vue.runtime.esm-bundler.js");
/* harmony import */ var _nextcloud_l10n__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @nextcloud/l10n */ "./node_modules/@nextcloud/l10n/dist/index.mjs");
/* harmony import */ var _nextcloud_router__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @nextcloud/router */ "./node_modules/@nextcloud/router/dist/index.mjs");
/* harmony import */ var _utils_markdownSerializer_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ../utils/markdownSerializer.js */ "./src/utils/markdownSerializer.js");






/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = ({
  name: 'Widget',
  components: {
    InlineTextEditor: (0,vue__WEBPACK_IMPORTED_MODULE_0__.defineAsyncComponent)(() => Promise.all(/*! import() */[__webpack_require__.e("vendors"), __webpack_require__.e("shared")]).then(__webpack_require__.bind(__webpack_require__, /*! ./InlineTextEditor.vue */ "./src/components/InlineTextEditor.vue"))),
    LinksWidget: (0,vue__WEBPACK_IMPORTED_MODULE_0__.defineAsyncComponent)(() => Promise.all(/*! import() */[__webpack_require__.e("vendors"), __webpack_require__.e("shared"), __webpack_require__.e("src_components_LinksWidget_vue")]).then(__webpack_require__.bind(__webpack_require__, /*! ./LinksWidget.vue */ "./src/components/LinksWidget.vue"))),
    NewsWidget: (0,vue__WEBPACK_IMPORTED_MODULE_0__.defineAsyncComponent)(() => Promise.all(/*! import() */[__webpack_require__.e("vendors"), __webpack_require__.e("shared"), __webpack_require__.e("src_components_NewsWidget_vue")]).then(__webpack_require__.bind(__webpack_require__, /*! ./NewsWidget.vue */ "./src/components/NewsWidget.vue"))),
    PeopleWidget: (0,vue__WEBPACK_IMPORTED_MODULE_0__.defineAsyncComponent)(() => Promise.all(/*! import() */[__webpack_require__.e("vendors"), __webpack_require__.e("shared"), __webpack_require__.e("src_components_PeopleWidget_vue")]).then(__webpack_require__.bind(__webpack_require__, /*! ./PeopleWidget.vue */ "./src/components/PeopleWidget.vue"))),
    CalendarWidget: (0,vue__WEBPACK_IMPORTED_MODULE_0__.defineAsyncComponent)(() => Promise.all(/*! import() */[__webpack_require__.e("vendors"), __webpack_require__.e("src_components_CalendarWidget_vue")]).then(__webpack_require__.bind(__webpack_require__, /*! ./CalendarWidget.vue */ "./src/components/CalendarWidget.vue"))),
    FeedWidget: (0,vue__WEBPACK_IMPORTED_MODULE_0__.defineAsyncComponent)(() => Promise.all(/*! import() */[__webpack_require__.e("vendors"), __webpack_require__.e("shared")]).then(__webpack_require__.bind(__webpack_require__, /*! ./FeedWidget.vue */ "./src/components/FeedWidget.vue"))),
    PhotoStoryWidget: (0,vue__WEBPACK_IMPORTED_MODULE_0__.defineAsyncComponent)(() => Promise.all(/*! import() */[__webpack_require__.e("vendors"), __webpack_require__.e("src_components_PhotoStoryWidget_vue")]).then(__webpack_require__.bind(__webpack_require__, /*! ./PhotoStoryWidget.vue */ "./src/components/PhotoStoryWidget.vue"))),
    FileStoryWidget: (0,vue__WEBPACK_IMPORTED_MODULE_0__.defineAsyncComponent)(() => Promise.all(/*! import() */[__webpack_require__.e("vendors"), __webpack_require__.e("src_components_FileStoryWidget_vue")]).then(__webpack_require__.bind(__webpack_require__, /*! ./FileStoryWidget.vue */ "./src/components/FileStoryWidget.vue"))),
  },
  props: {
    widget: {
      type: Object,
      required: true
    },
    pageId: {
      type: String,
      required: true
    },
    editable: {
      type: Boolean,
      default: false
    },
    rowBackgroundColor: {
      type: String,
      default: ''
    },
    shareToken: {
      type: String,
      default: ''
    }
  },
  emits: ['update', 'focus', 'blur', 'navigate'],
  data() {
    return {
      localContent: this.widget.content || '',
      localVideoError: null,
      isCompactMode: false,
      resizeObserver: null
    };
  },
  mounted() {
    // Auto-detect compact mode based on container width
    this.resizeObserver = new ResizeObserver(entries => {
      const width = entries[0].contentRect.width;
      this.isCompactMode = width < 400;
    });
    this.resizeObserver.observe(this.$el);
  },
  beforeUnmount() {
    if (this.resizeObserver) {
      this.resizeObserver.disconnect();
    }
  },
  computed: {
    sanitizedContent() {
      return this.sanitizeHtml(this.widget.content || '');
    },
    hasImageLink() {
      // Check if image has a valid link configured
      if (this.widget.linkType === 'internal') {
        return !!this.widget.linkPageId;
      }
      if (this.widget.linkType === 'external') {
        return !!this.widget.linkUrl;
      }
      return false;
    },
    textColorStyle() {
      // Map background colors to their matching text/link/selection colors
      // Each background has paired colors for optimal contrast (WCAG 2.1 compliant)
      const bgColor = this.rowBackgroundColor || '';

      // Define color mappings for text, links, and selection
      const colorMappings = {
        'var(--color-primary-element)': {
          text: 'var(--color-primary-element-text)',
          link: 'var(--color-primary-element-text)', // White links on dark blue
          linkHover: 'rgba(255, 255, 255, 0.8)',
          selection: 'rgba(255, 255, 255, 0.3)',
          selectionText: 'var(--color-primary-element-text)',
          placeholder: 'rgba(255, 255, 255, 0.5)'
        },
        'var(--color-primary-element-light)': {
          text: 'var(--color-primary-element-light-text)',
          link: 'var(--color-primary-element)', // Primary blue links on light blue
          linkHover: 'var(--color-primary-element-hover)',
          selection: 'var(--color-primary-element)',
          selectionText: 'var(--color-primary-element-text)',
          placeholder: 'var(--color-text-maxcontrast)'
        },
        'var(--color-error)': {
          text: 'var(--color-error-text)',
          link: 'var(--color-error-text)',
          linkHover: 'rgba(255, 255, 255, 0.8)',
          selection: 'rgba(255, 255, 255, 0.3)',
          selectionText: 'var(--color-error-text)',
          placeholder: 'rgba(255, 255, 255, 0.5)'
        },
        'var(--color-warning)': {
          text: 'var(--color-warning-text)',
          link: 'var(--color-warning-text)',
          linkHover: 'rgba(0, 0, 0, 0.7)',
          selection: 'rgba(0, 0, 0, 0.2)',
          selectionText: 'var(--color-warning-text)',
          placeholder: 'var(--color-text-maxcontrast)'
        },
        'var(--color-success)': {
          text: 'var(--color-success-text)',
          link: 'var(--color-success-text)',
          linkHover: 'rgba(255, 255, 255, 0.8)',
          selection: 'rgba(255, 255, 255, 0.3)',
          selectionText: 'var(--color-success-text)',
          placeholder: 'rgba(255, 255, 255, 0.5)'
        },
        'var(--color-background-dark)': {
          text: 'var(--color-main-text)',
          link: 'var(--color-primary-element)',
          linkHover: 'var(--color-primary-element-hover)',
          selection: 'var(--color-primary-element-light)',
          selectionText: 'var(--color-main-text)',
          placeholder: 'var(--color-text-maxcontrast)'
        },
        'var(--color-background-hover)': {
          text: 'var(--color-main-text)',
          link: 'var(--color-primary-element)',
          linkHover: 'var(--color-primary-element-hover)',
          selection: 'var(--color-primary-element-light)',
          selectionText: 'var(--color-main-text)',
          placeholder: 'var(--color-text-maxcontrast)'
        }
      };

      // Default colors for no background or unknown backgrounds
      const defaultColors = {
        text: 'var(--color-main-text)',
        link: 'var(--color-primary-element)',
        linkHover: 'var(--color-primary-element-hover)',
        selection: 'var(--color-primary-element-light)',
        selectionText: 'var(--color-main-text)',
        placeholder: 'var(--color-text-maxcontrast)'
      };

      const colors = colorMappings[bgColor] || defaultColors;

      return {
        'color': colors.text,
        '--widget-link-color': colors.link,
        '--widget-link-hover-color': colors.linkHover,
        '--widget-selection-bg': colors.selection,
        '--widget-selection-text': colors.selectionText,
        '--widget-placeholder-color': colors.placeholder
      };
    }
  },
  watch: {
    'widget.content'(newValue) {
      this.localContent = newValue || '';
    },
    localContent(newValue) {
      // Emit update immediately when content changes (not just on blur)
      if (newValue !== this.widget.content) {
        this.$emit('update', {
          ...this.widget,
          content: newValue
        });
      }
    }
  },
  methods: {
    t(key, vars = {}) {
      return (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_1__.translate)('intravox', key, vars);
    },
    sanitizeHtml(content) {
      if (!content) return '';
      // markdownToHtml handles sanitization and table hydration (colgroup
      // normalization + .tableWrapper for horizontal scroll).
      return (0,_utils_markdownSerializer_js__WEBPACK_IMPORTED_MODULE_3__.markdownToHtml)(content);
    },
    onBlur() {
      // No need to save here anymore - watcher handles it
      this.$emit('blur');
    },
    getImageUrl(filename) {
      if (!filename) return '';

      // Check mediaFolder property (new format)
      if (this.widget.mediaFolder === 'resources') {
        // If we have a share token, use the public endpoint
        if (this.shareToken) {
          return (0,_nextcloud_router__WEBPACK_IMPORTED_MODULE_2__.generateUrl)(`/apps/intravox/api/share/${this.shareToken}/resources/media/${filename}`);
        }
        return (0,_nextcloud_router__WEBPACK_IMPORTED_MODULE_2__.generateUrl)(`/apps/intravox/api/resources/media/${filename}`);
      }

      // Remove legacy prefixes if present
      const cleanFilename = filename.replace(/^(📷 images\/|images\/|_media\/)/, '');
      // If we have a share token, use the public share endpoint
      if (this.shareToken) {
        return (0,_nextcloud_router__WEBPACK_IMPORTED_MODULE_2__.generateUrl)(`/apps/intravox/api/share/${this.shareToken}/page/${this.pageId}/media/${cleanFilename}`);
      }
      // Media served via unified API (default: page media)
      return (0,_nextcloud_router__WEBPACK_IMPORTED_MODULE_2__.generateUrl)(`/apps/intravox/api/pages/${this.pageId}/media/${cleanFilename}`);
    },
    getVideoUrl(filename) {
      if (!filename) return '';

      // Check mediaFolder property (new format)
      if (this.widget.mediaFolder === 'resources') {
        // If we have a share token, use the public endpoint
        if (this.shareToken) {
          return (0,_nextcloud_router__WEBPACK_IMPORTED_MODULE_2__.generateUrl)(`/apps/intravox/api/share/${this.shareToken}/resources/media/${filename}`);
        }
        return (0,_nextcloud_router__WEBPACK_IMPORTED_MODULE_2__.generateUrl)(`/apps/intravox/api/resources/media/${filename}`);
      }

      // Remove legacy prefixes if present
      const cleanFilename = filename.replace(/^(videos\/|_media\/)/, '');
      // If we have a share token, use the public share endpoint
      if (this.shareToken) {
        return (0,_nextcloud_router__WEBPACK_IMPORTED_MODULE_2__.generateUrl)(`/apps/intravox/api/share/${this.shareToken}/page/${this.pageId}/media/${cleanFilename}`);
      }
      // Media served via unified API (default: page media)
      return (0,_nextcloud_router__WEBPACK_IMPORTED_MODULE_2__.generateUrl)(`/apps/intravox/api/pages/${this.pageId}/media/${cleanFilename}`);
    },
    getImageStyle() {
      const style = {};

      if (this.widget.width) {
        // Custom width - height auto maintains aspect ratio
        style.width = `${this.widget.width}px`;
        style.height = 'auto';
        style.maxWidth = '100%'; // Don't overflow container
      } else {
        // Full width - natural aspect ratio with cropping
        style.width = '100%';
        style.height = 'auto';
        style.maxHeight = '500px';
        style.objectFit = 'cover';

        // Set vertical position for cropping
        const position = this.widget.objectPosition || 'center';
        style.objectPosition = `center ${position}`;
      }

      return style;
    },
    getDividerStyle() {
      const style = {};
      const bgColor = this.rowBackgroundColor || '';

      // Dark backgrounds get light divider, light backgrounds get primary divider
      const darkBackgrounds = [
        'var(--color-primary-element)',
        'var(--color-error)',
        'var(--color-warning)',
        'var(--color-success)',
      ];

      if (darkBackgrounds.includes(bgColor)) {
        style.background = 'var(--color-primary-element-light)';
      } else {
        style.background = 'var(--color-primary-element)';
      }

      return style;
    },
    getEmbedUrl(widget) {
      if (!widget.src) return '';

      let url = widget.src;

      // Parse URL to add/modify parameters
      try {
        const urlObj = new URL(url);
        const hostname = urlObj.hostname.toLowerCase();

        // YouTube
        if (hostname.includes('youtube.com') || hostname.includes('youtube-nocookie.com')) {
          if (widget.autoplay) {
            urlObj.searchParams.set('autoplay', '1');
            urlObj.searchParams.set('mute', '1'); // Required for autoplay
          }
          if (widget.loop) {
            urlObj.searchParams.set('loop', '1');
            // YouTube loop requires playlist parameter with video ID
            const videoId = urlObj.pathname.split('/').pop();
            if (videoId) {
              urlObj.searchParams.set('playlist', videoId);
            }
          }
        }
        // Vimeo
        else if (hostname.includes('vimeo.com')) {
          if (widget.autoplay) {
            urlObj.searchParams.set('autoplay', '1');
            urlObj.searchParams.set('muted', '1');
          }
          if (widget.loop) {
            urlObj.searchParams.set('loop', '1');
          }
        }
        // PeerTube
        else if (urlObj.pathname.includes('/videos/embed/')) {
          if (widget.autoplay) {
            urlObj.searchParams.set('autoplay', '1');
            urlObj.searchParams.set('muted', '1');
          }
          if (widget.loop) {
            urlObj.searchParams.set('loop', '1');
          }
        }
        // Dailymotion
        else if (hostname.includes('dailymotion.com')) {
          if (widget.autoplay) {
            urlObj.searchParams.set('autoplay', '1');
            urlObj.searchParams.set('mute', '1');
          }
          // Dailymotion doesn't have a loop parameter in embed
        }
        // Twitch
        else if (hostname.includes('twitch.tv')) {
          if (widget.autoplay) {
            urlObj.searchParams.set('autoplay', 'true');
            urlObj.searchParams.set('muted', 'true');
          }
        }
        // Generic fallback - try common parameter names
        else {
          if (widget.autoplay) {
            urlObj.searchParams.set('autoplay', '1');
          }
          if (widget.loop) {
            urlObj.searchParams.set('loop', '1');
          }
        }

        return urlObj.toString();
      } catch (e) {
        // Invalid URL, return as-is
        return url;
      }
    },
    getVideoPlatformName(src) {
      if (!src) return 'video platform';
      try {
        const url = new URL(src);
        const hostname = url.hostname.toLowerCase();

        if (hostname.includes('youtube.com') || hostname.includes('youtube-nocookie.com')) {
          return 'YouTube';
        } else if (hostname.includes('vimeo.com')) {
          return 'Vimeo';
        } else if (hostname.includes('dailymotion.com')) {
          return 'Dailymotion';
        } else if (hostname.includes('twitch.tv')) {
          return 'Twitch';
        } else if (url.pathname.includes('/videos/embed/')) {
          // PeerTube instances - use domain name
          return hostname.replace('www.', '');
        } else {
          return hostname.replace('www.', '');
        }
      } catch (e) {
        return 'video platform';
      }
    },
    getOriginalVideoUrl(src) {
      if (!src) return '#';
      try {
        const url = new URL(src);
        const hostname = url.hostname.toLowerCase();

        // Convert embed URLs back to watch URLs
        if (hostname.includes('youtube.com') || hostname.includes('youtube-nocookie.com')) {
          // YouTube embed: /embed/VIDEO_ID -> watch?v=VIDEO_ID
          const videoId = url.pathname.replace('/embed/', '');
          return `https://www.youtube.com/watch?v=${videoId}`;
        } else if (hostname.includes('player.vimeo.com')) {
          // Vimeo player: /video/VIDEO_ID -> vimeo.com/VIDEO_ID
          const videoId = url.pathname.replace('/video/', '');
          return `https://vimeo.com/${videoId}`;
        } else if (hostname.includes('dailymotion.com')) {
          // Dailymotion embed: /embed/video/VIDEO_ID -> dailymotion.com/video/VIDEO_ID
          const videoId = url.pathname.replace('/embed/video/', '');
          return `https://www.dailymotion.com/video/${videoId}`;
        } else if (url.pathname.includes('/videos/embed/')) {
          // PeerTube: /videos/embed/VIDEO_ID -> /videos/watch/VIDEO_ID
          return src.replace('/videos/embed/', '/videos/watch/');
        }
        // Return original for unknown platforms
        return src;
      } catch (e) {
        return src || '#';
      }
    },
    onVideoEmbedLoad() {
      // Video iframe loaded successfully - no action needed
    },
    onVideoEmbedError() {
      // iframe errors are limited - silent fail
    },
    onLocalVideoError(event) {
      const video = event.target;
      const error = video.error;

      if (error) {
        switch (error.code) {
          case MediaError.MEDIA_ERR_ABORTED:
            this.localVideoError = this.t('Video playback was aborted.');
            break;
          case MediaError.MEDIA_ERR_NETWORK:
            this.localVideoError = this.t('A network error occurred while loading the video.');
            break;
          case MediaError.MEDIA_ERR_DECODE:
            this.localVideoError = this.t('The video file is corrupted or uses an unsupported format.');
            break;
          case MediaError.MEDIA_ERR_SRC_NOT_SUPPORTED:
            this.localVideoError = this.t('The video format is not supported by your browser.');
            break;
          default:
            this.localVideoError = this.t('An unknown error occurred while playing the video.');
        }
      }
    },
    getImageLinkHref() {
      // Return the appropriate href for the image link
      if (this.widget.linkType === 'internal' && this.widget.linkPageId) {
        return `#${this.widget.linkPageId}`;
      }
      if (this.widget.linkType === 'external' && this.widget.linkUrl) {
        return this.widget.linkUrl;
      }
      return '#';
    },
    getImageLinkTarget() {
      // Internal links always open in same tab
      if (this.widget.linkType === 'internal') {
        return '_self';
      }
      // External links: check linkNewTab setting (defaults to true for backwards compatibility)
      if (this.widget.linkType === 'external') {
        return this.widget.linkNewTab !== false ? '_blank' : '_self';
      }
      return '_self';
    },
    getImageLinkRel() {
      // Only add noopener noreferrer when opening in new tab
      if (this.widget.linkType === 'external' && this.widget.linkNewTab !== false) {
        return 'noopener noreferrer';
      }
      return '';
    },
    handleImageLinkClick(event) {
      // Handle internal link navigation
      if (this.widget.linkType === 'internal' && this.widget.linkPageId) {
        event.preventDefault();
        this.$emit('navigate', this.widget.linkPageId);
      }
      // External links follow their default behavior based on target attribute
    }
  }
});


/***/ },

/***/ "./src/components/reactions/CommentItem.vue"
/*!**************************************************!*\
  !*** ./src/components/reactions/CommentItem.vue ***!
  \**************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _CommentItem_vue_vue_type_template_id_6211c46d_scoped_true__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./CommentItem.vue?vue&type=template&id=6211c46d&scoped=true */ "./src/components/reactions/CommentItem.vue?vue&type=template&id=6211c46d&scoped=true");
/* harmony import */ var _CommentItem_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./CommentItem.vue?vue&type=script&lang=js */ "./src/components/reactions/CommentItem.vue?vue&type=script&lang=js");
/* harmony import */ var _CommentItem_vue_vue_type_style_index_0_id_6211c46d_scoped_true_lang_css__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./CommentItem.vue?vue&type=style&index=0&id=6211c46d&scoped=true&lang=css */ "./src/components/reactions/CommentItem.vue?vue&type=style&index=0&id=6211c46d&scoped=true&lang=css");
/* harmony import */ var _node_modules_vue_loader_dist_exportHelper_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ../../../node_modules/vue-loader/dist/exportHelper.js */ "./node_modules/vue-loader/dist/exportHelper.js");




;


const __exports__ = /*#__PURE__*/(0,_node_modules_vue_loader_dist_exportHelper_js__WEBPACK_IMPORTED_MODULE_3__["default"])(_CommentItem_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_1__["default"], [['render',_CommentItem_vue_vue_type_template_id_6211c46d_scoped_true__WEBPACK_IMPORTED_MODULE_0__.render],['__scopeId',"data-v-6211c46d"],['__file',"src/components/reactions/CommentItem.vue"]])
/* hot reload */
if (false) // removed by dead control flow
{}


/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (__exports__);

/***/ },

/***/ "./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/reactions/CommentItem.vue?vue&type=script&lang=js"
/*!**********************************************************************************************************************************************!*\
  !*** ./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/reactions/CommentItem.vue?vue&type=script&lang=js ***!
  \**********************************************************************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _nextcloud_vue__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @nextcloud/vue */ "./node_modules/@nextcloud/vue/dist/index.mjs");
/* harmony import */ var vue_material_design_icons_Pencil_vue__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! vue-material-design-icons/Pencil.vue */ "./node_modules/vue-material-design-icons/Pencil.vue");
/* harmony import */ var vue_material_design_icons_Delete_vue__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! vue-material-design-icons/Delete.vue */ "./node_modules/vue-material-design-icons/Delete.vue");
/* harmony import */ var vue_material_design_icons_Reply_vue__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! vue-material-design-icons/Reply.vue */ "./node_modules/vue-material-design-icons/Reply.vue");
/* harmony import */ var vue_material_design_icons_EmoticonHappyOutline_vue__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! vue-material-design-icons/EmoticonHappyOutline.vue */ "./node_modules/vue-material-design-icons/EmoticonHappyOutline.vue");
/* harmony import */ var _ReactionPicker_vue__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! ./ReactionPicker.vue */ "./src/components/reactions/ReactionPicker.vue");
/* harmony import */ var _services_CommentService_js__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! ../../services/CommentService.js */ "./src/services/CommentService.js");









/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = ({
	name: 'CommentItem',
	components: {
		NcAvatar: _nextcloud_vue__WEBPACK_IMPORTED_MODULE_0__.NcAvatar,
		NcButton: _nextcloud_vue__WEBPACK_IMPORTED_MODULE_0__.NcButton,
		NcActions: _nextcloud_vue__WEBPACK_IMPORTED_MODULE_0__.NcActions,
		NcActionButton: _nextcloud_vue__WEBPACK_IMPORTED_MODULE_0__.NcActionButton,
		Pencil: vue_material_design_icons_Pencil_vue__WEBPACK_IMPORTED_MODULE_1__["default"],
		Delete: vue_material_design_icons_Delete_vue__WEBPACK_IMPORTED_MODULE_2__["default"],
		Reply: vue_material_design_icons_Reply_vue__WEBPACK_IMPORTED_MODULE_3__["default"],
		EmoticonHappyOutline: vue_material_design_icons_EmoticonHappyOutline_vue__WEBPACK_IMPORTED_MODULE_4__["default"],
		ReactionPicker: _ReactionPicker_vue__WEBPACK_IMPORTED_MODULE_5__["default"],
	},
	props: {
		comment: {
			type: Object,
			required: true,
		},
		currentUserId: {
			type: String,
			default: '',
		},
		isReply: {
			type: Boolean,
			default: false,
		},
		allowReactions: {
			type: Boolean,
			default: true,
		},
	},
	emits: ['update', 'delete', 'reply'],
	data() {
		return {
			editing: false,
			editMessage: '',
			showReactionPicker: false,
			// Initialize from prop, will be updated locally on toggle
			localUserReactions: [],
		}
	},
	watch: {
		'comment.userReactions': {
			immediate: true,
			handler(newVal) {
				// Sync from backend when comment data changes
				this.localUserReactions = newVal || []
			},
		},
	},
	computed: {
		canModify() {
			return this.comment.userId === this.currentUserId
		},
		hasReactions() {
			return this.comment.reactions && Object.keys(this.comment.reactions).length > 0
		},
		formattedDate() {
			return new Date(this.comment.createdAt).toLocaleString()
		},
		relativeTime() {
			const date = new Date(this.comment.createdAt)
			const now = new Date()
			const diffMs = now - date
			const diffMins = Math.floor(diffMs / 60000)
			const diffHours = Math.floor(diffMs / 3600000)
			const diffDays = Math.floor(diffMs / 86400000)

			if (diffMins < 1) return this.t('intravox', 'just now')
			if (diffMins < 60) return this.t('intravox', '{minutes} min ago', { minutes: diffMins })
			if (diffHours < 24) return this.t('intravox', '{hours} hours ago', { hours: diffHours })
			if (diffDays < 7) return this.t('intravox', '{days} days ago', { days: diffDays })
			return date.toLocaleDateString()
		},
	},
	methods: {
		startEdit() {
			this.editMessage = this.comment.message
			this.editing = true
			this.$nextTick(() => {
				this.$refs.editInput?.focus()
			})
		},
		cancelEdit() {
			this.editing = false
			this.editMessage = ''
		},
		async saveEdit() {
			if (!this.editMessage.trim()) return

			try {
				const updated = await (0,_services_CommentService_js__WEBPACK_IMPORTED_MODULE_6__.updateComment)(this.comment.id, this.editMessage)
				this.$emit('update', updated)
				this.editing = false
			} catch (error) {
				console.error('Failed to update comment:', error)
			}
		},
		async confirmDelete() {
			if (confirm(this.t('intravox', 'Are you sure you want to delete this comment?'))) {
				try {
					await (0,_services_CommentService_js__WEBPACK_IMPORTED_MODULE_6__.deleteComment)(this.comment.id)
					this.$emit('delete', this.comment.id)
				} catch (error) {
					console.error('Failed to delete comment:', error)
				}
			}
		},
		isUserReaction(emoji) {
			return this.localUserReactions.includes(emoji)
		},
		async toggleReaction(emoji) {
			try {
				const result = await (0,_services_CommentService_js__WEBPACK_IMPORTED_MODULE_6__.toggleCommentReaction)(
					this.comment.id,
					emoji,
					this.localUserReactions,
				)
				this.localUserReactions = result.userReactions || []
				// Update comment reactions
				this.comment.reactions = result.reactions
			} catch (error) {
				console.error('Failed to toggle reaction:', error)
			}
		},
		async addReaction(emoji) {
			await this.toggleReaction(emoji)
		},
		t(app, str, params = {}) {
			if (window.t) {
				return window.t(app, str, params)
			}
			let result = str
			for (const [key, value] of Object.entries(params)) {
				result = result.replace(`{${key}}`, value)
			}
			return result
		},
	},
});


/***/ },

/***/ "./src/components/reactions/CommentSection.vue"
/*!*****************************************************!*\
  !*** ./src/components/reactions/CommentSection.vue ***!
  \*****************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _CommentSection_vue_vue_type_template_id_7504410a_scoped_true__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./CommentSection.vue?vue&type=template&id=7504410a&scoped=true */ "./src/components/reactions/CommentSection.vue?vue&type=template&id=7504410a&scoped=true");
/* harmony import */ var _CommentSection_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./CommentSection.vue?vue&type=script&lang=js */ "./src/components/reactions/CommentSection.vue?vue&type=script&lang=js");
/* harmony import */ var _CommentSection_vue_vue_type_style_index_0_id_7504410a_scoped_true_lang_css__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./CommentSection.vue?vue&type=style&index=0&id=7504410a&scoped=true&lang=css */ "./src/components/reactions/CommentSection.vue?vue&type=style&index=0&id=7504410a&scoped=true&lang=css");
/* harmony import */ var _node_modules_vue_loader_dist_exportHelper_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ../../../node_modules/vue-loader/dist/exportHelper.js */ "./node_modules/vue-loader/dist/exportHelper.js");




;


const __exports__ = /*#__PURE__*/(0,_node_modules_vue_loader_dist_exportHelper_js__WEBPACK_IMPORTED_MODULE_3__["default"])(_CommentSection_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_1__["default"], [['render',_CommentSection_vue_vue_type_template_id_7504410a_scoped_true__WEBPACK_IMPORTED_MODULE_0__.render],['__scopeId',"data-v-7504410a"],['__file',"src/components/reactions/CommentSection.vue"]])
/* hot reload */
if (false) // removed by dead control flow
{}


/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (__exports__);

/***/ },

/***/ "./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/reactions/CommentSection.vue?vue&type=script&lang=js"
/*!*************************************************************************************************************************************************!*\
  !*** ./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/reactions/CommentSection.vue?vue&type=script&lang=js ***!
  \*************************************************************************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _nextcloud_vue__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @nextcloud/vue */ "./node_modules/@nextcloud/vue/dist/index.mjs");
/* harmony import */ var vue_material_design_icons_CommentTextOutline_vue__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! vue-material-design-icons/CommentTextOutline.vue */ "./node_modules/vue-material-design-icons/CommentTextOutline.vue");
/* harmony import */ var vue_material_design_icons_Send_vue__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! vue-material-design-icons/Send.vue */ "./node_modules/vue-material-design-icons/Send.vue");
/* harmony import */ var vue_material_design_icons_Close_vue__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! vue-material-design-icons/Close.vue */ "./node_modules/vue-material-design-icons/Close.vue");
/* harmony import */ var _CommentItem_vue__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ./CommentItem.vue */ "./src/components/reactions/CommentItem.vue");
/* harmony import */ var _services_CommentService_js__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! ../../services/CommentService.js */ "./src/services/CommentService.js");
/* harmony import */ var _nextcloud_auth__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! @nextcloud/auth */ "./node_modules/@nextcloud/auth/dist/index.mjs");









/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = ({
	name: 'CommentSection',
	components: {
		NcAvatar: _nextcloud_vue__WEBPACK_IMPORTED_MODULE_0__.NcAvatar,
		NcButton: _nextcloud_vue__WEBPACK_IMPORTED_MODULE_0__.NcButton,
		NcSelect: _nextcloud_vue__WEBPACK_IMPORTED_MODULE_0__.NcSelect,
		NcLoadingIcon: _nextcloud_vue__WEBPACK_IMPORTED_MODULE_0__.NcLoadingIcon,
		CommentTextOutline: vue_material_design_icons_CommentTextOutline_vue__WEBPACK_IMPORTED_MODULE_1__["default"],
		Send: vue_material_design_icons_Send_vue__WEBPACK_IMPORTED_MODULE_2__["default"],
		Close: vue_material_design_icons_Close_vue__WEBPACK_IMPORTED_MODULE_3__["default"],
		CommentItem: _CommentItem_vue__WEBPACK_IMPORTED_MODULE_4__["default"],
	},
	props: {
		pageId: {
			type: String,
			required: true,
		},
		allowCommentReactions: {
			type: Boolean,
			default: true,
		},
	},
	data() {
		return {
			comments: [],
			totalComments: 0,
			loading: true,
			submitting: false,
			newComment: '',
			replyingTo: null,
			replyText: '',
			inputFocused: false,
			sortOrder: { value: 'newest', label: this.t('intravox', 'Newest first') },
			sortOptions: [
				{ value: 'newest', label: this.t('intravox', 'Newest first') },
				{ value: 'oldest', label: this.t('intravox', 'Oldest first') },
			],
		}
	},
	computed: {
		currentUserId() {
			return (0,_nextcloud_auth__WEBPACK_IMPORTED_MODULE_6__.getCurrentUser)()?.uid || ''
		},
		sortedComments() {
			const sorted = [...this.comments]
			if (this.sortOrder.value === 'newest') {
				sorted.sort((a, b) => new Date(b.createdAt) - new Date(a.createdAt))
			} else {
				sorted.sort((a, b) => new Date(a.createdAt) - new Date(b.createdAt))
			}
			return sorted
		},
	},
	watch: {
		pageId: {
			immediate: true,
			handler() {
				this.loadComments()
			},
		},
	},
	methods: {
		async loadComments() {
			this.loading = true
			try {
				const result = await (0,_services_CommentService_js__WEBPACK_IMPORTED_MODULE_5__.getComments)(this.pageId)
				this.comments = result.comments || []
				this.totalComments = result.total || 0
			} catch (error) {
				console.error('Failed to load comments:', error)
				this.comments = []
				this.totalComments = 0
			} finally {
				this.loading = false
			}
		},
		async submitComment() {
			if (!this.newComment.trim() || this.submitting) return

			this.submitting = true
			try {
				const parentId = this.replyingTo?.id || null
				const comment = await (0,_services_CommentService_js__WEBPACK_IMPORTED_MODULE_5__.createComment)(this.pageId, this.newComment, parentId)

				if (parentId) {
					// Add reply to parent comment
					const parent = this.comments.find(c => c.id === parentId)
					if (parent) {
						if (!parent.replies) parent.replies = []
						parent.replies.push(comment)
					}
				} else {
					// Add new top-level comment
					this.comments.unshift(comment)
				}

				this.totalComments++
				this.newComment = ''
				this.replyingTo = null
			} catch (error) {
				console.error('Failed to create comment:', error)
			} finally {
				this.submitting = false
			}
		},
		startReply(comment) {
			this.replyingTo = comment
			this.replyText = ''
			this.$nextTick(() => {
				this.$refs.replyInput?.focus()
			})
		},
		cancelReply() {
			this.replyingTo = null
			this.replyText = ''
		},
		async submitReply() {
			if (!this.replyText.trim() || this.submitting || !this.replyingTo) return

			this.submitting = true
			try {
				const parentId = this.replyingTo.id
				const reply = await (0,_services_CommentService_js__WEBPACK_IMPORTED_MODULE_5__.createComment)(this.pageId, this.replyText, parentId)

				// Add reply to parent comment
				const parent = this.comments.find(c => c.id === parentId)
				if (parent) {
					if (!parent.replies) parent.replies = []
					parent.replies.push(reply)
				}

				this.totalComments++
				this.replyText = ''
				this.replyingTo = null
			} catch (error) {
				console.error('Failed to create reply:', error)
			} finally {
				this.submitting = false
			}
		},
		handleCommentUpdate(updatedComment) {
			// Update in comments list
			const index = this.comments.findIndex(c => c.id === updatedComment.id)
			if (index !== -1) {
				this.comments[index] = { ...this.comments[index], ...updatedComment }
			} else {
				// Check in replies
				for (const comment of this.comments) {
					if (comment.replies) {
						const replyIndex = comment.replies.findIndex(r => r.id === updatedComment.id)
						if (replyIndex !== -1) {
							comment.replies[replyIndex] = { ...comment.replies[replyIndex], ...updatedComment }
							break
						}
					}
				}
			}
		},
		handleCommentDelete(commentId) {
			// Remove from comments list
			const index = this.comments.findIndex(c => c.id === commentId)
			if (index !== -1) {
				// Count parent + all replies (backend deletes them too)
				const deletedCount = 1 + (this.comments[index].replies?.length || 0)
				this.comments.splice(index, 1)
				this.totalComments -= deletedCount
			} else {
				// Check in replies (single reply deleted)
				for (const comment of this.comments) {
					if (comment.replies) {
						const replyIndex = comment.replies.findIndex(r => r.id === commentId)
						if (replyIndex !== -1) {
							comment.replies.splice(replyIndex, 1)
							this.totalComments--
							break
						}
					}
				}
			}
		},
		t(app, str, params = {}) {
			if (window.t) {
				return window.t(app, str, params)
			}
			let result = str
			for (const [key, value] of Object.entries(params)) {
				result = result.replace(`{${key}}`, value)
			}
			return result
		},
	},
});


/***/ },

/***/ "./src/components/reactions/ReactionBar.vue"
/*!**************************************************!*\
  !*** ./src/components/reactions/ReactionBar.vue ***!
  \**************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _ReactionBar_vue_vue_type_template_id_0ed66f85_scoped_true__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./ReactionBar.vue?vue&type=template&id=0ed66f85&scoped=true */ "./src/components/reactions/ReactionBar.vue?vue&type=template&id=0ed66f85&scoped=true");
/* harmony import */ var _ReactionBar_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./ReactionBar.vue?vue&type=script&lang=js */ "./src/components/reactions/ReactionBar.vue?vue&type=script&lang=js");
/* harmony import */ var _ReactionBar_vue_vue_type_style_index_0_id_0ed66f85_scoped_true_lang_css__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./ReactionBar.vue?vue&type=style&index=0&id=0ed66f85&scoped=true&lang=css */ "./src/components/reactions/ReactionBar.vue?vue&type=style&index=0&id=0ed66f85&scoped=true&lang=css");
/* harmony import */ var _node_modules_vue_loader_dist_exportHelper_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ../../../node_modules/vue-loader/dist/exportHelper.js */ "./node_modules/vue-loader/dist/exportHelper.js");




;


const __exports__ = /*#__PURE__*/(0,_node_modules_vue_loader_dist_exportHelper_js__WEBPACK_IMPORTED_MODULE_3__["default"])(_ReactionBar_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_1__["default"], [['render',_ReactionBar_vue_vue_type_template_id_0ed66f85_scoped_true__WEBPACK_IMPORTED_MODULE_0__.render],['__scopeId',"data-v-0ed66f85"],['__file',"src/components/reactions/ReactionBar.vue"]])
/* hot reload */
if (false) // removed by dead control flow
{}


/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (__exports__);

/***/ },

/***/ "./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/reactions/ReactionBar.vue?vue&type=script&lang=js"
/*!**********************************************************************************************************************************************!*\
  !*** ./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/reactions/ReactionBar.vue?vue&type=script&lang=js ***!
  \**********************************************************************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _nextcloud_vue__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @nextcloud/vue */ "./node_modules/@nextcloud/vue/dist/index.mjs");
/* harmony import */ var vue_material_design_icons_EmoticonHappyOutline_vue__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! vue-material-design-icons/EmoticonHappyOutline.vue */ "./node_modules/vue-material-design-icons/EmoticonHappyOutline.vue");
/* harmony import */ var _ReactionPicker_vue__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./ReactionPicker.vue */ "./src/components/reactions/ReactionPicker.vue");
/* harmony import */ var _services_CommentService_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ../../services/CommentService.js */ "./src/services/CommentService.js");






/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = ({
	name: 'ReactionBar',
	components: {
		NcButton: _nextcloud_vue__WEBPACK_IMPORTED_MODULE_0__.NcButton,
		EmoticonHappyOutline: vue_material_design_icons_EmoticonHappyOutline_vue__WEBPACK_IMPORTED_MODULE_1__["default"],
		ReactionPicker: _ReactionPicker_vue__WEBPACK_IMPORTED_MODULE_2__["default"],
	},
	props: {
		pageId: {
			type: String,
			required: true,
		},
		reactions: {
			type: Object,
			default: () => ({}),
		},
		userReactions: {
			type: Array,
			default: () => [],
		},
	},
	emits: ['update'],
	data() {
		return {
			showPicker: false,
			loading: false,
		}
	},
	methods: {
		async toggleReaction(emoji) {
			if (this.loading) return
			this.loading = true

			try {
				const result = await (0,_services_CommentService_js__WEBPACK_IMPORTED_MODULE_3__.togglePageReaction)(this.pageId, emoji, this.userReactions)
				this.$emit('update', result)
			} catch (error) {
				console.error('Failed to toggle reaction:', error)
			} finally {
				this.loading = false
			}
		},
		async addReaction(emoji) {
			await this.toggleReaction(emoji)
		},
		getReactionTitle(emoji, count) {
			const isActive = this.userReactions.includes(emoji)
			if (isActive) {
				return this.t('intravox', 'You and {count} others reacted with {emoji}', { count: count - 1, emoji })
			}
			return this.t('intravox', '{count} people reacted with {emoji}', { count, emoji })
		},
		t(app, str, params = {}) {
			if (window.t) {
				return window.t(app, str, params)
			}
			// Fallback: simple parameter replacement
			let result = str
			for (const [key, value] of Object.entries(params)) {
				result = result.replace(`{${key}}`, value)
			}
			return result
		},
	},
});


/***/ },

/***/ "./src/components/reactions/ReactionPicker.vue"
/*!*****************************************************!*\
  !*** ./src/components/reactions/ReactionPicker.vue ***!
  \*****************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _ReactionPicker_vue_vue_type_template_id_11a9eb8c_scoped_true__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./ReactionPicker.vue?vue&type=template&id=11a9eb8c&scoped=true */ "./src/components/reactions/ReactionPicker.vue?vue&type=template&id=11a9eb8c&scoped=true");
/* harmony import */ var _ReactionPicker_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./ReactionPicker.vue?vue&type=script&lang=js */ "./src/components/reactions/ReactionPicker.vue?vue&type=script&lang=js");
/* harmony import */ var _ReactionPicker_vue_vue_type_style_index_0_id_11a9eb8c_scoped_true_lang_css__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./ReactionPicker.vue?vue&type=style&index=0&id=11a9eb8c&scoped=true&lang=css */ "./src/components/reactions/ReactionPicker.vue?vue&type=style&index=0&id=11a9eb8c&scoped=true&lang=css");
/* harmony import */ var _node_modules_vue_loader_dist_exportHelper_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ../../../node_modules/vue-loader/dist/exportHelper.js */ "./node_modules/vue-loader/dist/exportHelper.js");




;


const __exports__ = /*#__PURE__*/(0,_node_modules_vue_loader_dist_exportHelper_js__WEBPACK_IMPORTED_MODULE_3__["default"])(_ReactionPicker_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_1__["default"], [['render',_ReactionPicker_vue_vue_type_template_id_11a9eb8c_scoped_true__WEBPACK_IMPORTED_MODULE_0__.render],['__scopeId',"data-v-11a9eb8c"],['__file',"src/components/reactions/ReactionPicker.vue"]])
/* hot reload */
if (false) // removed by dead control flow
{}


/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (__exports__);

/***/ },

/***/ "./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/reactions/ReactionPicker.vue?vue&type=script&lang=js"
/*!*************************************************************************************************************************************************!*\
  !*** ./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/reactions/ReactionPicker.vue?vue&type=script&lang=js ***!
  \*************************************************************************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _nextcloud_vue__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @nextcloud/vue */ "./node_modules/@nextcloud/vue/dist/index.mjs");
/* harmony import */ var vue_material_design_icons_Plus_vue__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! vue-material-design-icons/Plus.vue */ "./node_modules/vue-material-design-icons/Plus.vue");




/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = ({
	name: 'ReactionPicker',
	components: {
		NcButton: _nextcloud_vue__WEBPACK_IMPORTED_MODULE_0__.NcButton,
		NcPopover: _nextcloud_vue__WEBPACK_IMPORTED_MODULE_0__.NcPopover,
		Plus: vue_material_design_icons_Plus_vue__WEBPACK_IMPORTED_MODULE_1__["default"],
	},
	props: {
		visible: {
			type: Boolean,
			default: false,
		},
		selectedEmojis: {
			type: Array,
			default: () => [],
		},
	},
	emits: ['update:visible', 'select'],
	data() {
		return {
			// Common emoji reactions for quick access
			commonEmojis: [
				'👍', '👎', '❤️', '🎉', '😊', '😢',
				'😮', '😂', '🤔', '👏', '🙏', '💪',
				'✅', '⭐', '🔥', '💯', '👀', '🚀',
			],
		}
	},
	methods: {
		selectEmoji(emoji) {
			this.$emit('select', emoji)
			this.$emit('update:visible', false)
		},
		t(app, str) {
			return window.t ? window.t(app, str) : str
		},
	},
});


/***/ },

/***/ "./src/App.vue?vue&type=style&index=0&id=7ba5bd90&scoped=true&lang=css"
/*!*****************************************************************************!*\
  !*** ./src/App.vue?vue&type=style&index=0&id=7ba5bd90&scoped=true&lang=css ***!
  \*****************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _node_modules_style_loader_dist_cjs_js_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_dist_stylePostLoader_js_node_modules_vue_loader_dist_index_js_ruleSet_1_rules_5_use_0_App_vue_vue_type_style_index_0_id_7ba5bd90_scoped_true_lang_css__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../node_modules/style-loader/dist/cjs.js!../node_modules/css-loader/dist/cjs.js!../node_modules/vue-loader/dist/stylePostLoader.js!../node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./App.vue?vue&type=style&index=0&id=7ba5bd90&scoped=true&lang=css */ "./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/dist/stylePostLoader.js!./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/App.vue?vue&type=style&index=0&id=7ba5bd90&scoped=true&lang=css");


/***/ },

/***/ "./src/components/Breadcrumb.vue?vue&type=style&index=0&id=6f46de9a&scoped=true&lang=css"
/*!***********************************************************************************************!*\
  !*** ./src/components/Breadcrumb.vue?vue&type=style&index=0&id=6f46de9a&scoped=true&lang=css ***!
  \***********************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _node_modules_style_loader_dist_cjs_js_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_dist_stylePostLoader_js_node_modules_vue_loader_dist_index_js_ruleSet_1_rules_5_use_0_Breadcrumb_vue_vue_type_style_index_0_id_6f46de9a_scoped_true_lang_css__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../node_modules/style-loader/dist/cjs.js!../../node_modules/css-loader/dist/cjs.js!../../node_modules/vue-loader/dist/stylePostLoader.js!../../node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./Breadcrumb.vue?vue&type=style&index=0&id=6f46de9a&scoped=true&lang=css */ "./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/dist/stylePostLoader.js!./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/Breadcrumb.vue?vue&type=style&index=0&id=6f46de9a&scoped=true&lang=css");


/***/ },

/***/ "./src/components/Footer.vue?vue&type=style&index=0&id=40ab164b&scoped=true&lang=css"
/*!*******************************************************************************************!*\
  !*** ./src/components/Footer.vue?vue&type=style&index=0&id=40ab164b&scoped=true&lang=css ***!
  \*******************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _node_modules_style_loader_dist_cjs_js_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_dist_stylePostLoader_js_node_modules_vue_loader_dist_index_js_ruleSet_1_rules_5_use_0_Footer_vue_vue_type_style_index_0_id_40ab164b_scoped_true_lang_css__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../node_modules/style-loader/dist/cjs.js!../../node_modules/css-loader/dist/cjs.js!../../node_modules/vue-loader/dist/stylePostLoader.js!../../node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./Footer.vue?vue&type=style&index=0&id=40ab164b&scoped=true&lang=css */ "./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/dist/stylePostLoader.js!./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/Footer.vue?vue&type=style&index=0&id=40ab164b&scoped=true&lang=css");


/***/ },

/***/ "./src/components/Navigation.vue?vue&type=style&index=0&id=81440b78&scoped=true&lang=css"
/*!***********************************************************************************************!*\
  !*** ./src/components/Navigation.vue?vue&type=style&index=0&id=81440b78&scoped=true&lang=css ***!
  \***********************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _node_modules_style_loader_dist_cjs_js_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_dist_stylePostLoader_js_node_modules_vue_loader_dist_index_js_ruleSet_1_rules_5_use_0_Navigation_vue_vue_type_style_index_0_id_81440b78_scoped_true_lang_css__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../node_modules/style-loader/dist/cjs.js!../../node_modules/css-loader/dist/cjs.js!../../node_modules/vue-loader/dist/stylePostLoader.js!../../node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./Navigation.vue?vue&type=style&index=0&id=81440b78&scoped=true&lang=css */ "./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/dist/stylePostLoader.js!./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/Navigation.vue?vue&type=style&index=0&id=81440b78&scoped=true&lang=css");


/***/ },

/***/ "./src/components/NoShareDialog.vue?vue&type=style&index=0&id=a9f8e2f4&scoped=true&lang=css"
/*!**************************************************************************************************!*\
  !*** ./src/components/NoShareDialog.vue?vue&type=style&index=0&id=a9f8e2f4&scoped=true&lang=css ***!
  \**************************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _node_modules_style_loader_dist_cjs_js_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_dist_stylePostLoader_js_node_modules_vue_loader_dist_index_js_ruleSet_1_rules_5_use_0_NoShareDialog_vue_vue_type_style_index_0_id_a9f8e2f4_scoped_true_lang_css__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../node_modules/style-loader/dist/cjs.js!../../node_modules/css-loader/dist/cjs.js!../../node_modules/vue-loader/dist/stylePostLoader.js!../../node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./NoShareDialog.vue?vue&type=style&index=0&id=a9f8e2f4&scoped=true&lang=css */ "./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/dist/stylePostLoader.js!./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/NoShareDialog.vue?vue&type=style&index=0&id=a9f8e2f4&scoped=true&lang=css");


/***/ },

/***/ "./src/components/PageActionsMenu.vue?vue&type=style&index=0&id=457bfd6d&scoped=true&lang=css"
/*!****************************************************************************************************!*\
  !*** ./src/components/PageActionsMenu.vue?vue&type=style&index=0&id=457bfd6d&scoped=true&lang=css ***!
  \****************************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _node_modules_style_loader_dist_cjs_js_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_dist_stylePostLoader_js_node_modules_vue_loader_dist_index_js_ruleSet_1_rules_5_use_0_PageActionsMenu_vue_vue_type_style_index_0_id_457bfd6d_scoped_true_lang_css__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../node_modules/style-loader/dist/cjs.js!../../node_modules/css-loader/dist/cjs.js!../../node_modules/vue-loader/dist/stylePostLoader.js!../../node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./PageActionsMenu.vue?vue&type=style&index=0&id=457bfd6d&scoped=true&lang=css */ "./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/dist/stylePostLoader.js!./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/PageActionsMenu.vue?vue&type=style&index=0&id=457bfd6d&scoped=true&lang=css");


/***/ },

/***/ "./src/components/PageViewer.vue?vue&type=style&index=0&id=3a22e99e&scoped=true&lang=css"
/*!***********************************************************************************************!*\
  !*** ./src/components/PageViewer.vue?vue&type=style&index=0&id=3a22e99e&scoped=true&lang=css ***!
  \***********************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _node_modules_style_loader_dist_cjs_js_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_dist_stylePostLoader_js_node_modules_vue_loader_dist_index_js_ruleSet_1_rules_5_use_0_PageViewer_vue_vue_type_style_index_0_id_3a22e99e_scoped_true_lang_css__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../node_modules/style-loader/dist/cjs.js!../../node_modules/css-loader/dist/cjs.js!../../node_modules/vue-loader/dist/stylePostLoader.js!../../node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./PageViewer.vue?vue&type=style&index=0&id=3a22e99e&scoped=true&lang=css */ "./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/dist/stylePostLoader.js!./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/PageViewer.vue?vue&type=style&index=0&id=3a22e99e&scoped=true&lang=css");


/***/ },

/***/ "./src/components/PublicPageView.vue?vue&type=style&index=0&id=5157670d&scoped=true&lang=css"
/*!***************************************************************************************************!*\
  !*** ./src/components/PublicPageView.vue?vue&type=style&index=0&id=5157670d&scoped=true&lang=css ***!
  \***************************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _node_modules_style_loader_dist_cjs_js_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_dist_stylePostLoader_js_node_modules_vue_loader_dist_index_js_ruleSet_1_rules_5_use_0_PublicPageView_vue_vue_type_style_index_0_id_5157670d_scoped_true_lang_css__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../node_modules/style-loader/dist/cjs.js!../../node_modules/css-loader/dist/cjs.js!../../node_modules/vue-loader/dist/stylePostLoader.js!../../node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./PublicPageView.vue?vue&type=style&index=0&id=5157670d&scoped=true&lang=css */ "./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/dist/stylePostLoader.js!./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/PublicPageView.vue?vue&type=style&index=0&id=5157670d&scoped=true&lang=css");


/***/ },

/***/ "./src/components/ShareButton.vue?vue&type=style&index=0&id=29169d71&scoped=true&lang=css"
/*!************************************************************************************************!*\
  !*** ./src/components/ShareButton.vue?vue&type=style&index=0&id=29169d71&scoped=true&lang=css ***!
  \************************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _node_modules_style_loader_dist_cjs_js_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_dist_stylePostLoader_js_node_modules_vue_loader_dist_index_js_ruleSet_1_rules_5_use_0_ShareButton_vue_vue_type_style_index_0_id_29169d71_scoped_true_lang_css__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../node_modules/style-loader/dist/cjs.js!../../node_modules/css-loader/dist/cjs.js!../../node_modules/vue-loader/dist/stylePostLoader.js!../../node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./ShareButton.vue?vue&type=style&index=0&id=29169d71&scoped=true&lang=css */ "./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/dist/stylePostLoader.js!./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/ShareButton.vue?vue&type=style&index=0&id=29169d71&scoped=true&lang=css");


/***/ },

/***/ "./src/components/ShareDialog.vue?vue&type=style&index=0&id=7dad0ef2&scoped=true&lang=css"
/*!************************************************************************************************!*\
  !*** ./src/components/ShareDialog.vue?vue&type=style&index=0&id=7dad0ef2&scoped=true&lang=css ***!
  \************************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _node_modules_style_loader_dist_cjs_js_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_dist_stylePostLoader_js_node_modules_vue_loader_dist_index_js_ruleSet_1_rules_5_use_0_ShareDialog_vue_vue_type_style_index_0_id_7dad0ef2_scoped_true_lang_css__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../node_modules/style-loader/dist/cjs.js!../../node_modules/css-loader/dist/cjs.js!../../node_modules/vue-loader/dist/stylePostLoader.js!../../node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./ShareDialog.vue?vue&type=style&index=0&id=7dad0ef2&scoped=true&lang=css */ "./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/dist/stylePostLoader.js!./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/ShareDialog.vue?vue&type=style&index=0&id=7dad0ef2&scoped=true&lang=css");


/***/ },

/***/ "./src/components/Widget.vue?vue&type=style&index=0&id=4ca18318&scoped=true&lang=css"
/*!*******************************************************************************************!*\
  !*** ./src/components/Widget.vue?vue&type=style&index=0&id=4ca18318&scoped=true&lang=css ***!
  \*******************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _node_modules_style_loader_dist_cjs_js_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_dist_stylePostLoader_js_node_modules_vue_loader_dist_index_js_ruleSet_1_rules_5_use_0_Widget_vue_vue_type_style_index_0_id_4ca18318_scoped_true_lang_css__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../node_modules/style-loader/dist/cjs.js!../../node_modules/css-loader/dist/cjs.js!../../node_modules/vue-loader/dist/stylePostLoader.js!../../node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./Widget.vue?vue&type=style&index=0&id=4ca18318&scoped=true&lang=css */ "./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/dist/stylePostLoader.js!./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/Widget.vue?vue&type=style&index=0&id=4ca18318&scoped=true&lang=css");


/***/ },

/***/ "./src/components/reactions/CommentItem.vue?vue&type=style&index=0&id=6211c46d&scoped=true&lang=css"
/*!**********************************************************************************************************!*\
  !*** ./src/components/reactions/CommentItem.vue?vue&type=style&index=0&id=6211c46d&scoped=true&lang=css ***!
  \**********************************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _node_modules_style_loader_dist_cjs_js_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_dist_stylePostLoader_js_node_modules_vue_loader_dist_index_js_ruleSet_1_rules_5_use_0_CommentItem_vue_vue_type_style_index_0_id_6211c46d_scoped_true_lang_css__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../node_modules/style-loader/dist/cjs.js!../../../node_modules/css-loader/dist/cjs.js!../../../node_modules/vue-loader/dist/stylePostLoader.js!../../../node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./CommentItem.vue?vue&type=style&index=0&id=6211c46d&scoped=true&lang=css */ "./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/dist/stylePostLoader.js!./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/reactions/CommentItem.vue?vue&type=style&index=0&id=6211c46d&scoped=true&lang=css");


/***/ },

/***/ "./src/components/reactions/CommentSection.vue?vue&type=style&index=0&id=7504410a&scoped=true&lang=css"
/*!*************************************************************************************************************!*\
  !*** ./src/components/reactions/CommentSection.vue?vue&type=style&index=0&id=7504410a&scoped=true&lang=css ***!
  \*************************************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _node_modules_style_loader_dist_cjs_js_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_dist_stylePostLoader_js_node_modules_vue_loader_dist_index_js_ruleSet_1_rules_5_use_0_CommentSection_vue_vue_type_style_index_0_id_7504410a_scoped_true_lang_css__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../node_modules/style-loader/dist/cjs.js!../../../node_modules/css-loader/dist/cjs.js!../../../node_modules/vue-loader/dist/stylePostLoader.js!../../../node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./CommentSection.vue?vue&type=style&index=0&id=7504410a&scoped=true&lang=css */ "./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/dist/stylePostLoader.js!./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/reactions/CommentSection.vue?vue&type=style&index=0&id=7504410a&scoped=true&lang=css");


/***/ },

/***/ "./src/components/reactions/ReactionBar.vue?vue&type=style&index=0&id=0ed66f85&scoped=true&lang=css"
/*!**********************************************************************************************************!*\
  !*** ./src/components/reactions/ReactionBar.vue?vue&type=style&index=0&id=0ed66f85&scoped=true&lang=css ***!
  \**********************************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _node_modules_style_loader_dist_cjs_js_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_dist_stylePostLoader_js_node_modules_vue_loader_dist_index_js_ruleSet_1_rules_5_use_0_ReactionBar_vue_vue_type_style_index_0_id_0ed66f85_scoped_true_lang_css__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../node_modules/style-loader/dist/cjs.js!../../../node_modules/css-loader/dist/cjs.js!../../../node_modules/vue-loader/dist/stylePostLoader.js!../../../node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./ReactionBar.vue?vue&type=style&index=0&id=0ed66f85&scoped=true&lang=css */ "./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/dist/stylePostLoader.js!./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/reactions/ReactionBar.vue?vue&type=style&index=0&id=0ed66f85&scoped=true&lang=css");


/***/ },

/***/ "./src/components/reactions/ReactionPicker.vue?vue&type=style&index=0&id=11a9eb8c&scoped=true&lang=css"
/*!*************************************************************************************************************!*\
  !*** ./src/components/reactions/ReactionPicker.vue?vue&type=style&index=0&id=11a9eb8c&scoped=true&lang=css ***!
  \*************************************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _node_modules_style_loader_dist_cjs_js_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_dist_stylePostLoader_js_node_modules_vue_loader_dist_index_js_ruleSet_1_rules_5_use_0_ReactionPicker_vue_vue_type_style_index_0_id_11a9eb8c_scoped_true_lang_css__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../node_modules/style-loader/dist/cjs.js!../../../node_modules/css-loader/dist/cjs.js!../../../node_modules/vue-loader/dist/stylePostLoader.js!../../../node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./ReactionPicker.vue?vue&type=style&index=0&id=11a9eb8c&scoped=true&lang=css */ "./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/dist/stylePostLoader.js!./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/reactions/ReactionPicker.vue?vue&type=style&index=0&id=11a9eb8c&scoped=true&lang=css");


/***/ },

/***/ "./src/App.vue?vue&type=script&lang=js"
/*!*********************************************!*\
  !*** ./src/App.vue?vue&type=script&lang=js ***!
  \*********************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* reexport safe */ _node_modules_vue_loader_dist_index_js_ruleSet_1_rules_5_use_0_App_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_0__["default"])
/* harmony export */ });
/* harmony import */ var _node_modules_vue_loader_dist_index_js_ruleSet_1_rules_5_use_0_App_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./App.vue?vue&type=script&lang=js */ "./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/App.vue?vue&type=script&lang=js");
 

/***/ },

/***/ "./src/components/Breadcrumb.vue?vue&type=script&lang=js"
/*!***************************************************************!*\
  !*** ./src/components/Breadcrumb.vue?vue&type=script&lang=js ***!
  \***************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* reexport safe */ _node_modules_vue_loader_dist_index_js_ruleSet_1_rules_5_use_0_Breadcrumb_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_0__["default"])
/* harmony export */ });
/* harmony import */ var _node_modules_vue_loader_dist_index_js_ruleSet_1_rules_5_use_0_Breadcrumb_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./Breadcrumb.vue?vue&type=script&lang=js */ "./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/Breadcrumb.vue?vue&type=script&lang=js");
 

/***/ },

/***/ "./src/components/Footer.vue?vue&type=script&lang=js"
/*!***********************************************************!*\
  !*** ./src/components/Footer.vue?vue&type=script&lang=js ***!
  \***********************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* reexport safe */ _node_modules_vue_loader_dist_index_js_ruleSet_1_rules_5_use_0_Footer_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_0__["default"])
/* harmony export */ });
/* harmony import */ var _node_modules_vue_loader_dist_index_js_ruleSet_1_rules_5_use_0_Footer_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./Footer.vue?vue&type=script&lang=js */ "./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/Footer.vue?vue&type=script&lang=js");
 

/***/ },

/***/ "./src/components/Navigation.vue?vue&type=script&lang=js"
/*!***************************************************************!*\
  !*** ./src/components/Navigation.vue?vue&type=script&lang=js ***!
  \***************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* reexport safe */ _node_modules_vue_loader_dist_index_js_ruleSet_1_rules_5_use_0_Navigation_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_0__["default"])
/* harmony export */ });
/* harmony import */ var _node_modules_vue_loader_dist_index_js_ruleSet_1_rules_5_use_0_Navigation_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./Navigation.vue?vue&type=script&lang=js */ "./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/Navigation.vue?vue&type=script&lang=js");
 

/***/ },

/***/ "./src/components/NoShareDialog.vue?vue&type=script&lang=js"
/*!******************************************************************!*\
  !*** ./src/components/NoShareDialog.vue?vue&type=script&lang=js ***!
  \******************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* reexport safe */ _node_modules_vue_loader_dist_index_js_ruleSet_1_rules_5_use_0_NoShareDialog_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_0__["default"])
/* harmony export */ });
/* harmony import */ var _node_modules_vue_loader_dist_index_js_ruleSet_1_rules_5_use_0_NoShareDialog_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./NoShareDialog.vue?vue&type=script&lang=js */ "./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/NoShareDialog.vue?vue&type=script&lang=js");
 

/***/ },

/***/ "./src/components/PageActionsMenu.vue?vue&type=script&lang=js"
/*!********************************************************************!*\
  !*** ./src/components/PageActionsMenu.vue?vue&type=script&lang=js ***!
  \********************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* reexport safe */ _node_modules_vue_loader_dist_index_js_ruleSet_1_rules_5_use_0_PageActionsMenu_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_0__["default"])
/* harmony export */ });
/* harmony import */ var _node_modules_vue_loader_dist_index_js_ruleSet_1_rules_5_use_0_PageActionsMenu_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./PageActionsMenu.vue?vue&type=script&lang=js */ "./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/PageActionsMenu.vue?vue&type=script&lang=js");
 

/***/ },

/***/ "./src/components/PageViewer.vue?vue&type=script&lang=js"
/*!***************************************************************!*\
  !*** ./src/components/PageViewer.vue?vue&type=script&lang=js ***!
  \***************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* reexport safe */ _node_modules_vue_loader_dist_index_js_ruleSet_1_rules_5_use_0_PageViewer_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_0__["default"])
/* harmony export */ });
/* harmony import */ var _node_modules_vue_loader_dist_index_js_ruleSet_1_rules_5_use_0_PageViewer_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./PageViewer.vue?vue&type=script&lang=js */ "./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/PageViewer.vue?vue&type=script&lang=js");
 

/***/ },

/***/ "./src/components/PublicPageView.vue?vue&type=script&lang=js"
/*!*******************************************************************!*\
  !*** ./src/components/PublicPageView.vue?vue&type=script&lang=js ***!
  \*******************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* reexport safe */ _node_modules_vue_loader_dist_index_js_ruleSet_1_rules_5_use_0_PublicPageView_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_0__["default"])
/* harmony export */ });
/* harmony import */ var _node_modules_vue_loader_dist_index_js_ruleSet_1_rules_5_use_0_PublicPageView_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./PublicPageView.vue?vue&type=script&lang=js */ "./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/PublicPageView.vue?vue&type=script&lang=js");
 

/***/ },

/***/ "./src/components/ShareButton.vue?vue&type=script&lang=js"
/*!****************************************************************!*\
  !*** ./src/components/ShareButton.vue?vue&type=script&lang=js ***!
  \****************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* reexport safe */ _node_modules_vue_loader_dist_index_js_ruleSet_1_rules_5_use_0_ShareButton_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_0__["default"])
/* harmony export */ });
/* harmony import */ var _node_modules_vue_loader_dist_index_js_ruleSet_1_rules_5_use_0_ShareButton_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./ShareButton.vue?vue&type=script&lang=js */ "./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/ShareButton.vue?vue&type=script&lang=js");
 

/***/ },

/***/ "./src/components/ShareDialog.vue?vue&type=script&lang=js"
/*!****************************************************************!*\
  !*** ./src/components/ShareDialog.vue?vue&type=script&lang=js ***!
  \****************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* reexport safe */ _node_modules_vue_loader_dist_index_js_ruleSet_1_rules_5_use_0_ShareDialog_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_0__["default"])
/* harmony export */ });
/* harmony import */ var _node_modules_vue_loader_dist_index_js_ruleSet_1_rules_5_use_0_ShareDialog_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./ShareDialog.vue?vue&type=script&lang=js */ "./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/ShareDialog.vue?vue&type=script&lang=js");
 

/***/ },

/***/ "./src/components/Widget.vue?vue&type=script&lang=js"
/*!***********************************************************!*\
  !*** ./src/components/Widget.vue?vue&type=script&lang=js ***!
  \***********************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* reexport safe */ _node_modules_vue_loader_dist_index_js_ruleSet_1_rules_5_use_0_Widget_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_0__["default"])
/* harmony export */ });
/* harmony import */ var _node_modules_vue_loader_dist_index_js_ruleSet_1_rules_5_use_0_Widget_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./Widget.vue?vue&type=script&lang=js */ "./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/Widget.vue?vue&type=script&lang=js");
 

/***/ },

/***/ "./src/components/reactions/CommentItem.vue?vue&type=script&lang=js"
/*!**************************************************************************!*\
  !*** ./src/components/reactions/CommentItem.vue?vue&type=script&lang=js ***!
  \**************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* reexport safe */ _node_modules_vue_loader_dist_index_js_ruleSet_1_rules_5_use_0_CommentItem_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_0__["default"])
/* harmony export */ });
/* harmony import */ var _node_modules_vue_loader_dist_index_js_ruleSet_1_rules_5_use_0_CommentItem_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./CommentItem.vue?vue&type=script&lang=js */ "./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/reactions/CommentItem.vue?vue&type=script&lang=js");
 

/***/ },

/***/ "./src/components/reactions/CommentSection.vue?vue&type=script&lang=js"
/*!*****************************************************************************!*\
  !*** ./src/components/reactions/CommentSection.vue?vue&type=script&lang=js ***!
  \*****************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* reexport safe */ _node_modules_vue_loader_dist_index_js_ruleSet_1_rules_5_use_0_CommentSection_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_0__["default"])
/* harmony export */ });
/* harmony import */ var _node_modules_vue_loader_dist_index_js_ruleSet_1_rules_5_use_0_CommentSection_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./CommentSection.vue?vue&type=script&lang=js */ "./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/reactions/CommentSection.vue?vue&type=script&lang=js");
 

/***/ },

/***/ "./src/components/reactions/ReactionBar.vue?vue&type=script&lang=js"
/*!**************************************************************************!*\
  !*** ./src/components/reactions/ReactionBar.vue?vue&type=script&lang=js ***!
  \**************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* reexport safe */ _node_modules_vue_loader_dist_index_js_ruleSet_1_rules_5_use_0_ReactionBar_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_0__["default"])
/* harmony export */ });
/* harmony import */ var _node_modules_vue_loader_dist_index_js_ruleSet_1_rules_5_use_0_ReactionBar_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./ReactionBar.vue?vue&type=script&lang=js */ "./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/reactions/ReactionBar.vue?vue&type=script&lang=js");
 

/***/ },

/***/ "./src/components/reactions/ReactionPicker.vue?vue&type=script&lang=js"
/*!*****************************************************************************!*\
  !*** ./src/components/reactions/ReactionPicker.vue?vue&type=script&lang=js ***!
  \*****************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* reexport safe */ _node_modules_vue_loader_dist_index_js_ruleSet_1_rules_5_use_0_ReactionPicker_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_0__["default"])
/* harmony export */ });
/* harmony import */ var _node_modules_vue_loader_dist_index_js_ruleSet_1_rules_5_use_0_ReactionPicker_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./ReactionPicker.vue?vue&type=script&lang=js */ "./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/reactions/ReactionPicker.vue?vue&type=script&lang=js");
 

/***/ },

/***/ "./src/App.vue?vue&type=template&id=7ba5bd90&scoped=true"
/*!***************************************************************!*\
  !*** ./src/App.vue?vue&type=template&id=7ba5bd90&scoped=true ***!
  \***************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   render: () => (/* reexport safe */ _node_modules_vue_loader_dist_templateLoader_js_ruleSet_1_rules_2_node_modules_vue_loader_dist_index_js_ruleSet_1_rules_5_use_0_App_vue_vue_type_template_id_7ba5bd90_scoped_true__WEBPACK_IMPORTED_MODULE_0__.render)
/* harmony export */ });
/* harmony import */ var _node_modules_vue_loader_dist_templateLoader_js_ruleSet_1_rules_2_node_modules_vue_loader_dist_index_js_ruleSet_1_rules_5_use_0_App_vue_vue_type_template_id_7ba5bd90_scoped_true__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../node_modules/vue-loader/dist/templateLoader.js??ruleSet[1].rules[2]!../node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./App.vue?vue&type=template&id=7ba5bd90&scoped=true */ "./node_modules/vue-loader/dist/templateLoader.js??ruleSet[1].rules[2]!./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/App.vue?vue&type=template&id=7ba5bd90&scoped=true");


/***/ },

/***/ "./src/components/Breadcrumb.vue?vue&type=template&id=6f46de9a&scoped=true"
/*!*********************************************************************************!*\
  !*** ./src/components/Breadcrumb.vue?vue&type=template&id=6f46de9a&scoped=true ***!
  \*********************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   render: () => (/* reexport safe */ _node_modules_vue_loader_dist_templateLoader_js_ruleSet_1_rules_2_node_modules_vue_loader_dist_index_js_ruleSet_1_rules_5_use_0_Breadcrumb_vue_vue_type_template_id_6f46de9a_scoped_true__WEBPACK_IMPORTED_MODULE_0__.render)
/* harmony export */ });
/* harmony import */ var _node_modules_vue_loader_dist_templateLoader_js_ruleSet_1_rules_2_node_modules_vue_loader_dist_index_js_ruleSet_1_rules_5_use_0_Breadcrumb_vue_vue_type_template_id_6f46de9a_scoped_true__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../node_modules/vue-loader/dist/templateLoader.js??ruleSet[1].rules[2]!../../node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./Breadcrumb.vue?vue&type=template&id=6f46de9a&scoped=true */ "./node_modules/vue-loader/dist/templateLoader.js??ruleSet[1].rules[2]!./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/Breadcrumb.vue?vue&type=template&id=6f46de9a&scoped=true");


/***/ },

/***/ "./src/components/Footer.vue?vue&type=template&id=40ab164b&scoped=true"
/*!*****************************************************************************!*\
  !*** ./src/components/Footer.vue?vue&type=template&id=40ab164b&scoped=true ***!
  \*****************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   render: () => (/* reexport safe */ _node_modules_vue_loader_dist_templateLoader_js_ruleSet_1_rules_2_node_modules_vue_loader_dist_index_js_ruleSet_1_rules_5_use_0_Footer_vue_vue_type_template_id_40ab164b_scoped_true__WEBPACK_IMPORTED_MODULE_0__.render)
/* harmony export */ });
/* harmony import */ var _node_modules_vue_loader_dist_templateLoader_js_ruleSet_1_rules_2_node_modules_vue_loader_dist_index_js_ruleSet_1_rules_5_use_0_Footer_vue_vue_type_template_id_40ab164b_scoped_true__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../node_modules/vue-loader/dist/templateLoader.js??ruleSet[1].rules[2]!../../node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./Footer.vue?vue&type=template&id=40ab164b&scoped=true */ "./node_modules/vue-loader/dist/templateLoader.js??ruleSet[1].rules[2]!./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/Footer.vue?vue&type=template&id=40ab164b&scoped=true");


/***/ },

/***/ "./src/components/Navigation.vue?vue&type=template&id=81440b78&scoped=true"
/*!*********************************************************************************!*\
  !*** ./src/components/Navigation.vue?vue&type=template&id=81440b78&scoped=true ***!
  \*********************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   render: () => (/* reexport safe */ _node_modules_vue_loader_dist_templateLoader_js_ruleSet_1_rules_2_node_modules_vue_loader_dist_index_js_ruleSet_1_rules_5_use_0_Navigation_vue_vue_type_template_id_81440b78_scoped_true__WEBPACK_IMPORTED_MODULE_0__.render)
/* harmony export */ });
/* harmony import */ var _node_modules_vue_loader_dist_templateLoader_js_ruleSet_1_rules_2_node_modules_vue_loader_dist_index_js_ruleSet_1_rules_5_use_0_Navigation_vue_vue_type_template_id_81440b78_scoped_true__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../node_modules/vue-loader/dist/templateLoader.js??ruleSet[1].rules[2]!../../node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./Navigation.vue?vue&type=template&id=81440b78&scoped=true */ "./node_modules/vue-loader/dist/templateLoader.js??ruleSet[1].rules[2]!./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/Navigation.vue?vue&type=template&id=81440b78&scoped=true");


/***/ },

/***/ "./src/components/NoShareDialog.vue?vue&type=template&id=a9f8e2f4&scoped=true"
/*!************************************************************************************!*\
  !*** ./src/components/NoShareDialog.vue?vue&type=template&id=a9f8e2f4&scoped=true ***!
  \************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   render: () => (/* reexport safe */ _node_modules_vue_loader_dist_templateLoader_js_ruleSet_1_rules_2_node_modules_vue_loader_dist_index_js_ruleSet_1_rules_5_use_0_NoShareDialog_vue_vue_type_template_id_a9f8e2f4_scoped_true__WEBPACK_IMPORTED_MODULE_0__.render)
/* harmony export */ });
/* harmony import */ var _node_modules_vue_loader_dist_templateLoader_js_ruleSet_1_rules_2_node_modules_vue_loader_dist_index_js_ruleSet_1_rules_5_use_0_NoShareDialog_vue_vue_type_template_id_a9f8e2f4_scoped_true__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../node_modules/vue-loader/dist/templateLoader.js??ruleSet[1].rules[2]!../../node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./NoShareDialog.vue?vue&type=template&id=a9f8e2f4&scoped=true */ "./node_modules/vue-loader/dist/templateLoader.js??ruleSet[1].rules[2]!./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/NoShareDialog.vue?vue&type=template&id=a9f8e2f4&scoped=true");


/***/ },

/***/ "./src/components/PageActionsMenu.vue?vue&type=template&id=457bfd6d&scoped=true"
/*!**************************************************************************************!*\
  !*** ./src/components/PageActionsMenu.vue?vue&type=template&id=457bfd6d&scoped=true ***!
  \**************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   render: () => (/* reexport safe */ _node_modules_vue_loader_dist_templateLoader_js_ruleSet_1_rules_2_node_modules_vue_loader_dist_index_js_ruleSet_1_rules_5_use_0_PageActionsMenu_vue_vue_type_template_id_457bfd6d_scoped_true__WEBPACK_IMPORTED_MODULE_0__.render)
/* harmony export */ });
/* harmony import */ var _node_modules_vue_loader_dist_templateLoader_js_ruleSet_1_rules_2_node_modules_vue_loader_dist_index_js_ruleSet_1_rules_5_use_0_PageActionsMenu_vue_vue_type_template_id_457bfd6d_scoped_true__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../node_modules/vue-loader/dist/templateLoader.js??ruleSet[1].rules[2]!../../node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./PageActionsMenu.vue?vue&type=template&id=457bfd6d&scoped=true */ "./node_modules/vue-loader/dist/templateLoader.js??ruleSet[1].rules[2]!./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/PageActionsMenu.vue?vue&type=template&id=457bfd6d&scoped=true");


/***/ },

/***/ "./src/components/PageViewer.vue?vue&type=template&id=3a22e99e&scoped=true"
/*!*********************************************************************************!*\
  !*** ./src/components/PageViewer.vue?vue&type=template&id=3a22e99e&scoped=true ***!
  \*********************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   render: () => (/* reexport safe */ _node_modules_vue_loader_dist_templateLoader_js_ruleSet_1_rules_2_node_modules_vue_loader_dist_index_js_ruleSet_1_rules_5_use_0_PageViewer_vue_vue_type_template_id_3a22e99e_scoped_true__WEBPACK_IMPORTED_MODULE_0__.render)
/* harmony export */ });
/* harmony import */ var _node_modules_vue_loader_dist_templateLoader_js_ruleSet_1_rules_2_node_modules_vue_loader_dist_index_js_ruleSet_1_rules_5_use_0_PageViewer_vue_vue_type_template_id_3a22e99e_scoped_true__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../node_modules/vue-loader/dist/templateLoader.js??ruleSet[1].rules[2]!../../node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./PageViewer.vue?vue&type=template&id=3a22e99e&scoped=true */ "./node_modules/vue-loader/dist/templateLoader.js??ruleSet[1].rules[2]!./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/PageViewer.vue?vue&type=template&id=3a22e99e&scoped=true");


/***/ },

/***/ "./src/components/PublicPageView.vue?vue&type=template&id=5157670d&scoped=true"
/*!*************************************************************************************!*\
  !*** ./src/components/PublicPageView.vue?vue&type=template&id=5157670d&scoped=true ***!
  \*************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   render: () => (/* reexport safe */ _node_modules_vue_loader_dist_templateLoader_js_ruleSet_1_rules_2_node_modules_vue_loader_dist_index_js_ruleSet_1_rules_5_use_0_PublicPageView_vue_vue_type_template_id_5157670d_scoped_true__WEBPACK_IMPORTED_MODULE_0__.render)
/* harmony export */ });
/* harmony import */ var _node_modules_vue_loader_dist_templateLoader_js_ruleSet_1_rules_2_node_modules_vue_loader_dist_index_js_ruleSet_1_rules_5_use_0_PublicPageView_vue_vue_type_template_id_5157670d_scoped_true__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../node_modules/vue-loader/dist/templateLoader.js??ruleSet[1].rules[2]!../../node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./PublicPageView.vue?vue&type=template&id=5157670d&scoped=true */ "./node_modules/vue-loader/dist/templateLoader.js??ruleSet[1].rules[2]!./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/PublicPageView.vue?vue&type=template&id=5157670d&scoped=true");


/***/ },

/***/ "./src/components/ShareButton.vue?vue&type=template&id=29169d71&scoped=true"
/*!**********************************************************************************!*\
  !*** ./src/components/ShareButton.vue?vue&type=template&id=29169d71&scoped=true ***!
  \**********************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   render: () => (/* reexport safe */ _node_modules_vue_loader_dist_templateLoader_js_ruleSet_1_rules_2_node_modules_vue_loader_dist_index_js_ruleSet_1_rules_5_use_0_ShareButton_vue_vue_type_template_id_29169d71_scoped_true__WEBPACK_IMPORTED_MODULE_0__.render)
/* harmony export */ });
/* harmony import */ var _node_modules_vue_loader_dist_templateLoader_js_ruleSet_1_rules_2_node_modules_vue_loader_dist_index_js_ruleSet_1_rules_5_use_0_ShareButton_vue_vue_type_template_id_29169d71_scoped_true__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../node_modules/vue-loader/dist/templateLoader.js??ruleSet[1].rules[2]!../../node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./ShareButton.vue?vue&type=template&id=29169d71&scoped=true */ "./node_modules/vue-loader/dist/templateLoader.js??ruleSet[1].rules[2]!./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/ShareButton.vue?vue&type=template&id=29169d71&scoped=true");


/***/ },

/***/ "./src/components/ShareDialog.vue?vue&type=template&id=7dad0ef2&scoped=true"
/*!**********************************************************************************!*\
  !*** ./src/components/ShareDialog.vue?vue&type=template&id=7dad0ef2&scoped=true ***!
  \**********************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   render: () => (/* reexport safe */ _node_modules_vue_loader_dist_templateLoader_js_ruleSet_1_rules_2_node_modules_vue_loader_dist_index_js_ruleSet_1_rules_5_use_0_ShareDialog_vue_vue_type_template_id_7dad0ef2_scoped_true__WEBPACK_IMPORTED_MODULE_0__.render)
/* harmony export */ });
/* harmony import */ var _node_modules_vue_loader_dist_templateLoader_js_ruleSet_1_rules_2_node_modules_vue_loader_dist_index_js_ruleSet_1_rules_5_use_0_ShareDialog_vue_vue_type_template_id_7dad0ef2_scoped_true__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../node_modules/vue-loader/dist/templateLoader.js??ruleSet[1].rules[2]!../../node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./ShareDialog.vue?vue&type=template&id=7dad0ef2&scoped=true */ "./node_modules/vue-loader/dist/templateLoader.js??ruleSet[1].rules[2]!./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/ShareDialog.vue?vue&type=template&id=7dad0ef2&scoped=true");


/***/ },

/***/ "./src/components/Widget.vue?vue&type=template&id=4ca18318&scoped=true"
/*!*****************************************************************************!*\
  !*** ./src/components/Widget.vue?vue&type=template&id=4ca18318&scoped=true ***!
  \*****************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   render: () => (/* reexport safe */ _node_modules_vue_loader_dist_templateLoader_js_ruleSet_1_rules_2_node_modules_vue_loader_dist_index_js_ruleSet_1_rules_5_use_0_Widget_vue_vue_type_template_id_4ca18318_scoped_true__WEBPACK_IMPORTED_MODULE_0__.render)
/* harmony export */ });
/* harmony import */ var _node_modules_vue_loader_dist_templateLoader_js_ruleSet_1_rules_2_node_modules_vue_loader_dist_index_js_ruleSet_1_rules_5_use_0_Widget_vue_vue_type_template_id_4ca18318_scoped_true__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../node_modules/vue-loader/dist/templateLoader.js??ruleSet[1].rules[2]!../../node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./Widget.vue?vue&type=template&id=4ca18318&scoped=true */ "./node_modules/vue-loader/dist/templateLoader.js??ruleSet[1].rules[2]!./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/Widget.vue?vue&type=template&id=4ca18318&scoped=true");


/***/ },

/***/ "./src/components/reactions/CommentItem.vue?vue&type=template&id=6211c46d&scoped=true"
/*!********************************************************************************************!*\
  !*** ./src/components/reactions/CommentItem.vue?vue&type=template&id=6211c46d&scoped=true ***!
  \********************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   render: () => (/* reexport safe */ _node_modules_vue_loader_dist_templateLoader_js_ruleSet_1_rules_2_node_modules_vue_loader_dist_index_js_ruleSet_1_rules_5_use_0_CommentItem_vue_vue_type_template_id_6211c46d_scoped_true__WEBPACK_IMPORTED_MODULE_0__.render)
/* harmony export */ });
/* harmony import */ var _node_modules_vue_loader_dist_templateLoader_js_ruleSet_1_rules_2_node_modules_vue_loader_dist_index_js_ruleSet_1_rules_5_use_0_CommentItem_vue_vue_type_template_id_6211c46d_scoped_true__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../node_modules/vue-loader/dist/templateLoader.js??ruleSet[1].rules[2]!../../../node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./CommentItem.vue?vue&type=template&id=6211c46d&scoped=true */ "./node_modules/vue-loader/dist/templateLoader.js??ruleSet[1].rules[2]!./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/reactions/CommentItem.vue?vue&type=template&id=6211c46d&scoped=true");


/***/ },

/***/ "./src/components/reactions/CommentSection.vue?vue&type=template&id=7504410a&scoped=true"
/*!***********************************************************************************************!*\
  !*** ./src/components/reactions/CommentSection.vue?vue&type=template&id=7504410a&scoped=true ***!
  \***********************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   render: () => (/* reexport safe */ _node_modules_vue_loader_dist_templateLoader_js_ruleSet_1_rules_2_node_modules_vue_loader_dist_index_js_ruleSet_1_rules_5_use_0_CommentSection_vue_vue_type_template_id_7504410a_scoped_true__WEBPACK_IMPORTED_MODULE_0__.render)
/* harmony export */ });
/* harmony import */ var _node_modules_vue_loader_dist_templateLoader_js_ruleSet_1_rules_2_node_modules_vue_loader_dist_index_js_ruleSet_1_rules_5_use_0_CommentSection_vue_vue_type_template_id_7504410a_scoped_true__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../node_modules/vue-loader/dist/templateLoader.js??ruleSet[1].rules[2]!../../../node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./CommentSection.vue?vue&type=template&id=7504410a&scoped=true */ "./node_modules/vue-loader/dist/templateLoader.js??ruleSet[1].rules[2]!./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/reactions/CommentSection.vue?vue&type=template&id=7504410a&scoped=true");


/***/ },

/***/ "./src/components/reactions/ReactionBar.vue?vue&type=template&id=0ed66f85&scoped=true"
/*!********************************************************************************************!*\
  !*** ./src/components/reactions/ReactionBar.vue?vue&type=template&id=0ed66f85&scoped=true ***!
  \********************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   render: () => (/* reexport safe */ _node_modules_vue_loader_dist_templateLoader_js_ruleSet_1_rules_2_node_modules_vue_loader_dist_index_js_ruleSet_1_rules_5_use_0_ReactionBar_vue_vue_type_template_id_0ed66f85_scoped_true__WEBPACK_IMPORTED_MODULE_0__.render)
/* harmony export */ });
/* harmony import */ var _node_modules_vue_loader_dist_templateLoader_js_ruleSet_1_rules_2_node_modules_vue_loader_dist_index_js_ruleSet_1_rules_5_use_0_ReactionBar_vue_vue_type_template_id_0ed66f85_scoped_true__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../node_modules/vue-loader/dist/templateLoader.js??ruleSet[1].rules[2]!../../../node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./ReactionBar.vue?vue&type=template&id=0ed66f85&scoped=true */ "./node_modules/vue-loader/dist/templateLoader.js??ruleSet[1].rules[2]!./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/reactions/ReactionBar.vue?vue&type=template&id=0ed66f85&scoped=true");


/***/ },

/***/ "./src/components/reactions/ReactionPicker.vue?vue&type=template&id=11a9eb8c&scoped=true"
/*!***********************************************************************************************!*\
  !*** ./src/components/reactions/ReactionPicker.vue?vue&type=template&id=11a9eb8c&scoped=true ***!
  \***********************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   render: () => (/* reexport safe */ _node_modules_vue_loader_dist_templateLoader_js_ruleSet_1_rules_2_node_modules_vue_loader_dist_index_js_ruleSet_1_rules_5_use_0_ReactionPicker_vue_vue_type_template_id_11a9eb8c_scoped_true__WEBPACK_IMPORTED_MODULE_0__.render)
/* harmony export */ });
/* harmony import */ var _node_modules_vue_loader_dist_templateLoader_js_ruleSet_1_rules_2_node_modules_vue_loader_dist_index_js_ruleSet_1_rules_5_use_0_ReactionPicker_vue_vue_type_template_id_11a9eb8c_scoped_true__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../node_modules/vue-loader/dist/templateLoader.js??ruleSet[1].rules[2]!../../../node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./ReactionPicker.vue?vue&type=template&id=11a9eb8c&scoped=true */ "./node_modules/vue-loader/dist/templateLoader.js??ruleSet[1].rules[2]!./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/reactions/ReactionPicker.vue?vue&type=template&id=11a9eb8c&scoped=true");


/***/ },

/***/ "./node_modules/vue-loader/dist/templateLoader.js??ruleSet[1].rules[2]!./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/App.vue?vue&type=template&id=7ba5bd90&scoped=true"
/*!*********************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/vue-loader/dist/templateLoader.js??ruleSet[1].rules[2]!./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/App.vue?vue&type=template&id=7ba5bd90&scoped=true ***!
  \*********************************************************************************************************************************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   render: () => (/* binding */ render)
/* harmony export */ });
/* harmony import */ var vue__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! vue */ "./node_modules/vue/dist/vue.runtime.esm-bundler.js");


const _hoisted_1 = { id: "intravox-app" }
const _hoisted_2 = {
  href: "#intravox-main-content",
  class: "skip-link"
}
const _hoisted_3 = { class: "intravox-topbar" }
const _hoisted_4 = { class: "intravox-header" }
const _hoisted_5 = { class: "header-left" }
const _hoisted_6 = { key: 0 }
const _hoisted_7 = ["placeholder", "aria-label"]
const _hoisted_8 = { class: "header-right" }
const _hoisted_9 = {
  key: 0,
  class: "draft-badge draft"
}
const _hoisted_10 = {
  key: 2,
  class: "page-lock-indicator"
}
const _hoisted_11 = { class: "intravox-nav-bar" }
const _hoisted_12 = { class: "app-content-wrapper" }
const _hoisted_13 = {
  key: 0,
  class: "loading",
  role: "status",
  "aria-live": "polite"
}
const _hoisted_14 = {
  key: 2,
  class: "error",
  role: "alert"
}
const _hoisted_15 = {
  key: 3,
  class: "intravox-content",
  id: "intravox-main-content"
}
const _hoisted_16 = {
  key: 0,
  class: "breadcrumb-row"
}
const _hoisted_17 = {
  key: 1,
  class: "breadcrumb-spacer"
}
const _hoisted_18 = ["disabled", "aria-label", "title"]

function render(_ctx, _cache, $props, $setup, $data, $options) {
  const _component_ShareButton = (0,vue__WEBPACK_IMPORTED_MODULE_0__.resolveComponent)("ShareButton")
  const _component_NcButton = (0,vue__WEBPACK_IMPORTED_MODULE_0__.resolveComponent)("NcButton")
  const _component_Pencil = (0,vue__WEBPACK_IMPORTED_MODULE_0__.resolveComponent)("Pencil")
  const _component_PageActionsMenu = (0,vue__WEBPACK_IMPORTED_MODULE_0__.resolveComponent)("PageActionsMenu")
  const _component_EyeOff = (0,vue__WEBPACK_IMPORTED_MODULE_0__.resolveComponent)("EyeOff")
  const _component_Eye = (0,vue__WEBPACK_IMPORTED_MODULE_0__.resolveComponent)("Eye")
  const _component_Close = (0,vue__WEBPACK_IMPORTED_MODULE_0__.resolveComponent)("Close")
  const _component_ContentSave = (0,vue__WEBPACK_IMPORTED_MODULE_0__.resolveComponent)("ContentSave")
  const _component_Navigation = (0,vue__WEBPACK_IMPORTED_MODULE_0__.resolveComponent)("Navigation")
  const _component_WelcomeScreen = (0,vue__WEBPACK_IMPORTED_MODULE_0__.resolveComponent)("WelcomeScreen")
  const _component_Breadcrumb = (0,vue__WEBPACK_IMPORTED_MODULE_0__.resolveComponent)("Breadcrumb")
  const _component_Information = (0,vue__WEBPACK_IMPORTED_MODULE_0__.resolveComponent)("Information")
  const _component_PageViewer = (0,vue__WEBPACK_IMPORTED_MODULE_0__.resolveComponent)("PageViewer")
  const _component_PageEditor = (0,vue__WEBPACK_IMPORTED_MODULE_0__.resolveComponent)("PageEditor")
  const _component_PageDetailsSidebar = (0,vue__WEBPACK_IMPORTED_MODULE_0__.resolveComponent)("PageDetailsSidebar")
  const _component_PageListModal = (0,vue__WEBPACK_IMPORTED_MODULE_0__.resolveComponent)("PageListModal")
  const _component_PageTreeModal = (0,vue__WEBPACK_IMPORTED_MODULE_0__.resolveComponent)("PageTreeModal")
  const _component_NewPageModal = (0,vue__WEBPACK_IMPORTED_MODULE_0__.resolveComponent)("NewPageModal")
  const _component_NavigationEditor = (0,vue__WEBPACK_IMPORTED_MODULE_0__.resolveComponent)("NavigationEditor")
  const _component_PageSettingsModal = (0,vue__WEBPACK_IMPORTED_MODULE_0__.resolveComponent)("PageSettingsModal")
  const _component_SaveAsTemplateModal = (0,vue__WEBPACK_IMPORTED_MODULE_0__.resolveComponent)("SaveAsTemplateModal")
  const _component_FeedSettings = (0,vue__WEBPACK_IMPORTED_MODULE_0__.resolveComponent)("FeedSettings")
  const _component_Footer = (0,vue__WEBPACK_IMPORTED_MODULE_0__.resolveComponent)("Footer")

  return ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)("div", _hoisted_1, [
    (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("a", _hoisted_2, (0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)($options.t('Skip to main content')), 1 /* TEXT */),
    (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)(" Sticky topbar wraps header + navigation so navigating between pages\r\n         doesn't require scrolling all the way back up on long pages. "),
    (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("div", _hoisted_3, [
      (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)(" Header with page title and actions "),
      (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("header", _hoisted_4, [
        (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("div", _hoisted_5, [
          (!$data.isEditMode)
            ? ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)("h1", _hoisted_6, (0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)($data.currentPage?.title || 'IntraVox'), 1 /* TEXT */))
            : (0,vue__WEBPACK_IMPORTED_MODULE_0__.withDirectives)(((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)("input", {
                key: 1,
                "onUpdate:modelValue": _cache[0] || (_cache[0] = $event => (($data.editableTitle) = $event)),
                type: "text",
                class: "page-title-input",
                placeholder: $options.t('Page title'),
                "aria-label": $options.t('Page title')
              }, null, 8 /* PROPS */, _hoisted_7)), [
                [vue__WEBPACK_IMPORTED_MODULE_0__.vModelText, $data.editableTitle]
              ])
        ]),
        (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("div", _hoisted_8, [
          (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)(" Draft indicator (visible to editors in view mode) "),
          (!$data.isEditMode && $data.currentPage?.status === 'draft')
            ? ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)("span", _hoisted_9, (0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)($options.t('Draft')), 1 /* TEXT */))
            : (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)("v-if", true),
          (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)(" Share Button (only visible when NC share exists) "),
          (!$data.isEditMode && $data.currentPage?.uniqueId)
            ? ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createBlock)(_component_ShareButton, {
                key: 1,
                "page-unique-id": $data.currentPage.uniqueId,
                "page-title": $data.currentPage.title,
                language: $data.currentLanguage
              }, null, 8 /* PROPS */, ["page-unique-id", "page-title", "language"]))
            : (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)("v-if", true),
          (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)(" Lock indicator (when another user is editing this page) "),
          (!$data.isEditMode && $data.pageLock)
            ? ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)("span", _hoisted_10, [
                (0,vue__WEBPACK_IMPORTED_MODULE_0__.createTextVNode)((0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)($options.t('{displayName} is editing this page', { displayName: $data.pageLock.displayName })) + " ", 1 /* TEXT */),
                ($data.canEditNavigation)
                  ? ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createBlock)(_component_NcButton, {
                      key: 0,
                      onClick: $options.forceUnlock,
                      type: "tertiary",
                      "aria-label": $options.t('Unlock')
                    }, {
                      default: (0,vue__WEBPACK_IMPORTED_MODULE_0__.withCtx)(() => [
                        (0,vue__WEBPACK_IMPORTED_MODULE_0__.createTextVNode)((0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)($options.t('Unlock')), 1 /* TEXT */)
                      ]),
                      _: 1 /* STABLE */
                    }, 8 /* PROPS */, ["onClick", "aria-label"]))
                  : (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)("v-if", true)
              ]))
            : (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)("v-if", true),
          (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)(" Edit Page Button (only visible when user has edit permissions for this page) "),
          (!$data.isEditMode && $options.canEditCurrentPage)
            ? ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createBlock)(_component_NcButton, {
                key: 3,
                onClick: $options.startEditMode,
                type: "secondary",
                disabled: !!$data.pageLock,
                "aria-label": $options.t('Edit this page')
              }, {
                icon: (0,vue__WEBPACK_IMPORTED_MODULE_0__.withCtx)(() => [
                  (0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_Pencil, { size: 20 })
                ]),
                default: (0,vue__WEBPACK_IMPORTED_MODULE_0__.withCtx)(() => [
                  (0,vue__WEBPACK_IMPORTED_MODULE_0__.createTextVNode)(" " + (0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)($options.t('Edit Page')), 1 /* TEXT */)
                ]),
                _: 1 /* STABLE */
              }, 8 /* PROPS */, ["onClick", "disabled", "aria-label"]))
            : (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)("v-if", true),
          (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)(" Page Actions Menu (3-dot menu) "),
          (!$data.isEditMode)
            ? ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createBlock)(_component_PageActionsMenu, {
                key: 4,
                "is-edit-mode": $data.isEditMode,
                permissions: $options.pagePermissions,
                onEditNavigation: _cache[1] || (_cache[1] = $event => ($data.showNavigationEditor = true)),
                onCreatePage: $options.createNewPage,
                onPageSettings: _cache[2] || (_cache[2] = $event => ($data.showPageSettingsModal = true)),
                onSaveAsTemplate: _cache[3] || (_cache[3] = $event => ($data.showSaveAsTemplateModal = true)),
                onFeedSettings: _cache[4] || (_cache[4] = $event => ($data.showFeedSettings = true))
              }, null, 8 /* PROPS */, ["is-edit-mode", "permissions", "onCreatePage"]))
            : ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)(vue__WEBPACK_IMPORTED_MODULE_0__.Fragment, { key: 5 }, [
                (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)(" Edit Mode Actions (Save/Cancel) "),
                (0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_NcButton, {
                  onClick: $options.toggleDraftStatus,
                  type: $data.currentPage?.status === 'draft' ? 'warning' : 'secondary',
                  "aria-label": $data.currentPage?.status === 'draft' ? $options.t('Draft — click to publish') : $options.t('Published — click to unpublish')
                }, {
                  icon: (0,vue__WEBPACK_IMPORTED_MODULE_0__.withCtx)(() => [
                    ($data.currentPage?.status === 'draft')
                      ? ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createBlock)(_component_EyeOff, {
                          key: 0,
                          size: 20
                        }))
                      : ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createBlock)(_component_Eye, {
                          key: 1,
                          size: 20
                        }))
                  ]),
                  default: (0,vue__WEBPACK_IMPORTED_MODULE_0__.withCtx)(() => [
                    (0,vue__WEBPACK_IMPORTED_MODULE_0__.createTextVNode)(" " + (0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)($data.currentPage?.status === 'draft' ? $options.t('Draft') : $options.t('Published')), 1 /* TEXT */)
                  ]),
                  _: 1 /* STABLE */
                }, 8 /* PROPS */, ["onClick", "type", "aria-label"]),
                (0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_NcButton, {
                  onClick: $options.cancelEditMode,
                  type: "secondary",
                  "aria-label": $options.t('Cancel editing')
                }, {
                  icon: (0,vue__WEBPACK_IMPORTED_MODULE_0__.withCtx)(() => [
                    (0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_Close, { size: 20 })
                  ]),
                  default: (0,vue__WEBPACK_IMPORTED_MODULE_0__.withCtx)(() => [
                    (0,vue__WEBPACK_IMPORTED_MODULE_0__.createTextVNode)(" " + (0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)($options.t('Cancel')), 1 /* TEXT */)
                  ]),
                  _: 1 /* STABLE */
                }, 8 /* PROPS */, ["onClick", "aria-label"]),
                (0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_NcButton, {
                  onClick: $options.saveAndExitEditMode,
                  type: "primary",
                  "aria-label": $options.t('Save changes')
                }, {
                  icon: (0,vue__WEBPACK_IMPORTED_MODULE_0__.withCtx)(() => [
                    (0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_ContentSave, { size: 20 })
                  ]),
                  default: (0,vue__WEBPACK_IMPORTED_MODULE_0__.withCtx)(() => [
                    (0,vue__WEBPACK_IMPORTED_MODULE_0__.createTextVNode)(" " + (0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)($options.t('Save')), 1 /* TEXT */)
                  ]),
                  _: 1 /* STABLE */
                }, 8 /* PROPS */, ["onClick", "aria-label"])
              ], 64 /* STABLE_FRAGMENT */))
        ])
      ]),
      (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)(" Navigation "),
      (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("div", _hoisted_11, [
        (0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_Navigation, {
          items: $data.navigation.items,
          type: $data.navigation.type,
          onNavigate: $options.navigateToItem,
          onShowTree: _cache[5] || (_cache[5] = $event => ($data.showPageTree = true))
        }, null, 8 /* PROPS */, ["items", "type", "onNavigate"])
      ])
    ]),
    (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)(" /.intravox-topbar "),
    (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)(" Main content area with sidebar "),
    (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("div", _hoisted_12, [
      ($data.loading)
        ? ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)("div", _hoisted_13, (0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)($options.t('Loading...')), 1 /* TEXT */))
        : ($options.showWelcomeScreen)
          ? ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)(vue__WEBPACK_IMPORTED_MODULE_0__.Fragment, { key: 1 }, [
              (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)(" Welcome screen when no pages exist (first install) "),
              (0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_WelcomeScreen)
            ], 2112 /* STABLE_FRAGMENT, DEV_ROOT_FRAGMENT */))
          : ($data.error)
            ? ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)("div", _hoisted_14, (0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)($data.error), 1 /* TEXT */))
            : ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)("main", _hoisted_15, [
                (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)(" Breadcrumb row with Details button "),
                ($data.breadcrumb.length > 0 || !$data.isEditMode)
                  ? ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)("div", _hoisted_16, [
                      ($data.breadcrumb.length > 0)
                        ? ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createBlock)(_component_Breadcrumb, {
                            key: 0,
                            breadcrumb: $data.breadcrumb,
                            onNavigate: $options.selectPage
                          }, null, 8 /* PROPS */, ["breadcrumb", "onNavigate"]))
                        : ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)("div", _hoisted_17)),
                      (!$data.isEditMode)
                        ? ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)("button", {
                            key: 2,
                            class: (0,vue__WEBPACK_IMPORTED_MODULE_0__.normalizeClass)(["details-btn", { 'details-btn-disabled': $data.showDetailsSidebar }]),
                            disabled: $data.showDetailsSidebar,
                            onClick: _cache[6] || (_cache[6] = $event => ($data.showDetailsSidebar = true)),
                            "aria-label": $options.t('Details'),
                            title: $options.t('Details')
                          }, [
                            (0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_Information, { size: 20 })
                          ], 10 /* CLASS, PROPS */, _hoisted_18))
                        : (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)("v-if", true)
                    ]))
                  : (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)("v-if", true),
                (!$data.isEditMode && $options.displayPage)
                  ? ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createBlock)(_component_PageViewer, {
                      key: 1,
                      page: $options.displayPage,
                      "engagement-settings": $data.globalEngagementSettings,
                      onNavigate: $options.selectPage
                    }, null, 8 /* PROPS */, ["page", "engagement-settings", "onNavigate"]))
                  : ($data.isEditMode && $data.currentPage)
                    ? ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createBlock)(_component_PageEditor, {
                        key: 2,
                        page: $data.currentPage,
                        onUpdate: $options.updatePage
                      }, null, 8 /* PROPS */, ["page", "onUpdate"]))
                    : (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)("v-if", true)
              ])),
      (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)(" Page Details Sidebar (inside content wrapper) "),
      (0,vue__WEBPACK_IMPORTED_MODULE_0__.withDirectives)((0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_PageDetailsSidebar, {
        "is-open": $data.showDetailsSidebar,
        "page-id": $data.currentPage?.uniqueId,
        "page-name": $data.currentPage?.title || $options.t('Untitled Page'),
        "initial-tab": $data.sidebarInitialTab,
        onClose: $options.handleCloseSidebar,
        onVersionRestored: $options.handleVersionRestored,
        onVersionSelected: $options.handleVersionSelected
      }, null, 8 /* PROPS */, ["is-open", "page-id", "page-name", "initial-tab", "onClose", "onVersionRestored", "onVersionSelected"]), [
        [vue__WEBPACK_IMPORTED_MODULE_0__.vShow, $data.currentPage && !$data.loading && !$data.error]
      ])
    ]),
    ($data.showPages)
      ? ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createBlock)(_component_PageListModal, {
          key: 0,
          pages: $data.pages,
          onClose: _cache[7] || (_cache[7] = $event => ($data.showPages = false)),
          onSelect: $options.selectPage,
          onDelete: $options.deletePage
        }, null, 8 /* PROPS */, ["pages", "onSelect", "onDelete"]))
      : (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)("v-if", true),
    ($data.showPageTree)
      ? ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createBlock)(_component_PageTreeModal, {
          key: 1,
          "current-page-id": $data.currentPage?.uniqueId,
          onClose: _cache[8] || (_cache[8] = $event => ($data.showPageTree = false)),
          onNavigate: $options.selectPage
        }, null, 8 /* PROPS */, ["current-page-id", "onNavigate"]))
      : (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)("v-if", true),
    ($data.showNewPageModal)
      ? ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createBlock)(_component_NewPageModal, {
          key: 2,
          "current-page-path": $data.currentPage?.path || null,
          onClose: _cache[9] || (_cache[9] = $event => ($data.showNewPageModal = false)),
          onCreate: $options.handleCreatePage,
          onCreateFromTemplate: $options.handleCreatePageFromTemplate
        }, null, 8 /* PROPS */, ["current-page-path", "onCreate", "onCreateFromTemplate"]))
      : (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)("v-if", true),
    ($data.showNavigationEditor)
      ? ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createBlock)(_component_NavigationEditor, {
          key: 3,
          navigation: $data.navigation,
          pages: $data.pages,
          onClose: _cache[10] || (_cache[10] = $event => ($data.showNavigationEditor = false)),
          onSave: $options.saveNavigation
        }, null, 8 /* PROPS */, ["navigation", "pages", "onSave"]))
      : (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)("v-if", true),
    ($data.showPageSettingsModal)
      ? ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createBlock)(_component_PageSettingsModal, {
          key: 4,
          "page-unique-id": $data.currentPage?.uniqueId,
          settings: $data.currentPage?.settings || {},
          "global-settings": $data.globalEngagementSettings,
          onClose: _cache[11] || (_cache[11] = $event => ($data.showPageSettingsModal = false)),
          onSave: $options.handlePageSettingsSave
        }, null, 8 /* PROPS */, ["page-unique-id", "settings", "global-settings", "onSave"]))
      : (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)("v-if", true),
    ($data.showSaveAsTemplateModal)
      ? ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createBlock)(_component_SaveAsTemplateModal, {
          key: 5,
          "page-unique-id": $data.currentPage?.uniqueId,
          "page-title": $data.currentPage?.title,
          onClose: _cache[12] || (_cache[12] = $event => ($data.showSaveAsTemplateModal = false)),
          onSaved: $options.handleTemplateSaved
        }, null, 8 /* PROPS */, ["page-unique-id", "page-title", "onSaved"]))
      : (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)("v-if", true),
    ($data.showFeedSettings)
      ? ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createBlock)(_component_FeedSettings, {
          key: 6,
          onClose: _cache[13] || (_cache[13] = $event => ($data.showFeedSettings = false))
        }))
      : (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)("v-if", true),
    (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)(" Footer "),
    (!$data.loading && !$data.error)
      ? ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createBlock)(_component_Footer, {
          key: 7,
          "footer-content": $data.footerContent,
          "can-edit": $data.canEditFooter,
          "is-home-page": $options.isCurrentPageHome,
          onSave: $options.handleFooterSave,
          onNavigate: $options.selectPage
        }, null, 8 /* PROPS */, ["footer-content", "can-edit", "is-home-page", "onSave", "onNavigate"]))
      : (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)("v-if", true)
  ]))
}

/***/ },

/***/ "./node_modules/vue-loader/dist/templateLoader.js??ruleSet[1].rules[2]!./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/Breadcrumb.vue?vue&type=template&id=6f46de9a&scoped=true"
/*!***************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/vue-loader/dist/templateLoader.js??ruleSet[1].rules[2]!./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/Breadcrumb.vue?vue&type=template&id=6f46de9a&scoped=true ***!
  \***************************************************************************************************************************************************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   render: () => (/* binding */ render)
/* harmony export */ });
/* harmony import */ var vue__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! vue */ "./node_modules/vue/dist/vue.runtime.esm-bundler.js");


const _hoisted_1 = {
  key: 0,
  class: "breadcrumb",
  "aria-label": "Breadcrumb"
}
const _hoisted_2 = { class: "breadcrumb-list" }
const _hoisted_3 = ["href", "onClick", "aria-current"]

function render(_ctx, _cache, $props, $setup, $data, $options) {
  const _component_ChevronRight = (0,vue__WEBPACK_IMPORTED_MODULE_0__.resolveComponent)("ChevronRight")

  return ($props.breadcrumb && $props.breadcrumb.length > 0)
    ? ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)("nav", _hoisted_1, [
        (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("ol", _hoisted_2, [
          ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(true), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)(vue__WEBPACK_IMPORTED_MODULE_0__.Fragment, null, (0,vue__WEBPACK_IMPORTED_MODULE_0__.renderList)($options.breadcrumbItems, (item, index) => {
            return ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)("li", {
              key: item.id,
              class: "breadcrumb-item"
            }, [
              (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("a", {
                href: item.url,
                onClick: (0,vue__WEBPACK_IMPORTED_MODULE_0__.withModifiers)($event => ($options.navigateToPage(item.uniqueId || item.id)), ["prevent"]),
                class: (0,vue__WEBPACK_IMPORTED_MODULE_0__.normalizeClass)({'breadcrumb-link': true, 'breadcrumb-current-page': item.current}),
                "aria-current": item.current ? 'page' : undefined
              }, (0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)(item.title), 11 /* TEXT, CLASS, PROPS */, _hoisted_3),
              (index < $options.breadcrumbItems.length - 1)
                ? ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createBlock)(_component_ChevronRight, {
                    key: 0,
                    size: 16,
                    class: "breadcrumb-separator"
                  }))
                : (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)("v-if", true)
            ]))
          }), 128 /* KEYED_FRAGMENT */))
        ])
      ]))
    : (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)("v-if", true)
}

/***/ },

/***/ "./node_modules/vue-loader/dist/templateLoader.js??ruleSet[1].rules[2]!./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/Footer.vue?vue&type=template&id=40ab164b&scoped=true"
/*!***********************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/vue-loader/dist/templateLoader.js??ruleSet[1].rules[2]!./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/Footer.vue?vue&type=template&id=40ab164b&scoped=true ***!
  \***********************************************************************************************************************************************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   render: () => (/* binding */ render)
/* harmony export */ });
/* harmony import */ var vue__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! vue */ "./node_modules/vue/dist/vue.runtime.esm-bundler.js");


const _hoisted_1 = { class: "intravox-footer" }
const _hoisted_2 = { class: "footer-wrapper" }
const _hoisted_3 = { class: "footer-content-and-actions" }
const _hoisted_4 = { class: "footer-content-wrapper" }
const _hoisted_5 = ["innerHTML"]
const _hoisted_6 = {
  key: 0,
  class: "footer-actions"
}
const _hoisted_7 = {
  key: 1,
  class: "footer-edit-actions"
}

function render(_ctx, _cache, $props, $setup, $data, $options) {
  const _component_InlineTextEditor = (0,vue__WEBPACK_IMPORTED_MODULE_0__.resolveComponent)("InlineTextEditor")
  const _component_Pencil = (0,vue__WEBPACK_IMPORTED_MODULE_0__.resolveComponent)("Pencil")
  const _component_NcActionButton = (0,vue__WEBPACK_IMPORTED_MODULE_0__.resolveComponent)("NcActionButton")
  const _component_NcActions = (0,vue__WEBPACK_IMPORTED_MODULE_0__.resolveComponent)("NcActions")
  const _component_Close = (0,vue__WEBPACK_IMPORTED_MODULE_0__.resolveComponent)("Close")
  const _component_NcButton = (0,vue__WEBPACK_IMPORTED_MODULE_0__.resolveComponent)("NcButton")
  const _component_ContentSave = (0,vue__WEBPACK_IMPORTED_MODULE_0__.resolveComponent)("ContentSave")

  return ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)("footer", _hoisted_1, [
    (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("div", _hoisted_2, [
      (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("div", _hoisted_3, [
        (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)(" Footer Editor or Content "),
        (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("div", _hoisted_4, [
          ($data.isEditingFooter)
            ? ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createBlock)(_component_InlineTextEditor, {
                key: 0,
                modelValue: $data.editableContent,
                "onUpdate:modelValue": _cache[0] || (_cache[0] = $event => (($data.editableContent) = $event)),
                editable: true,
                placeholder: $options.t('Enter footer content...'),
                class: "footer-editor"
              }, null, 8 /* PROPS */, ["modelValue", "placeholder"]))
            : ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)("div", {
                key: 1,
                class: "footer-content",
                innerHTML: $options.footerHtml
              }, null, 8 /* PROPS */, _hoisted_5))
        ]),
        (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)(" 3-dot Action Menu (shown when not editing) "),
        ($props.canEdit && $props.isHomePage && !$data.isEditingFooter)
          ? ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)("div", _hoisted_6, [
              (0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_NcActions, null, {
                default: (0,vue__WEBPACK_IMPORTED_MODULE_0__.withCtx)(() => [
                  (0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_NcActionButton, { onClick: $options.startEditFooter }, {
                    icon: (0,vue__WEBPACK_IMPORTED_MODULE_0__.withCtx)(() => [
                      (0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_Pencil, { size: 20 })
                    ]),
                    default: (0,vue__WEBPACK_IMPORTED_MODULE_0__.withCtx)(() => [
                      (0,vue__WEBPACK_IMPORTED_MODULE_0__.createTextVNode)(" " + (0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)($options.t('Edit footer')), 1 /* TEXT */)
                    ]),
                    _: 1 /* STABLE */
                  }, 8 /* PROPS */, ["onClick"])
                ]),
                _: 1 /* STABLE */
              })
            ]))
          : (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)("v-if", true),
        (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)(" Save/Cancel Buttons (shown when editing) "),
        ($data.isEditingFooter)
          ? ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)("div", _hoisted_7, [
              (0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_NcButton, {
                onClick: $options.cancelEditFooter,
                "aria-label": $options.t('Cancel')
              }, {
                icon: (0,vue__WEBPACK_IMPORTED_MODULE_0__.withCtx)(() => [
                  (0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_Close, { size: 20 })
                ]),
                default: (0,vue__WEBPACK_IMPORTED_MODULE_0__.withCtx)(() => [
                  (0,vue__WEBPACK_IMPORTED_MODULE_0__.createTextVNode)(" " + (0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)($options.t('Cancel')), 1 /* TEXT */)
                ]),
                _: 1 /* STABLE */
              }, 8 /* PROPS */, ["onClick", "aria-label"]),
              (0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_NcButton, {
                type: "primary",
                onClick: $options.saveFooter,
                "aria-label": $options.t('Save')
              }, {
                icon: (0,vue__WEBPACK_IMPORTED_MODULE_0__.withCtx)(() => [
                  (0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_ContentSave, { size: 20 })
                ]),
                default: (0,vue__WEBPACK_IMPORTED_MODULE_0__.withCtx)(() => [
                  (0,vue__WEBPACK_IMPORTED_MODULE_0__.createTextVNode)(" " + (0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)($options.t('Save')), 1 /* TEXT */)
                ]),
                _: 1 /* STABLE */
              }, 8 /* PROPS */, ["onClick", "aria-label"])
            ]))
          : (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)("v-if", true)
      ])
    ])
  ]))
}

/***/ },

/***/ "./node_modules/vue-loader/dist/templateLoader.js??ruleSet[1].rules[2]!./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/Navigation.vue?vue&type=template&id=81440b78&scoped=true"
/*!***************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/vue-loader/dist/templateLoader.js??ruleSet[1].rules[2]!./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/Navigation.vue?vue&type=template&id=81440b78&scoped=true ***!
  \***************************************************************************************************************************************************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   render: () => (/* binding */ render)
/* harmony export */ });
/* harmony import */ var vue__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! vue */ "./node_modules/vue/dist/vue.runtime.esm-bundler.js");


const _hoisted_1 = { class: "intravox-navigation" }
const _hoisted_2 = ["aria-label", "title"]
const _hoisted_3 = { class: "mobile-nav" }
const _hoisted_4 = ["onClick"]
const _hoisted_5 = ["onClick"]
const _hoisted_6 = ["onClick"]
const _hoisted_7 = {
  key: 0,
  class: "desktop-nav desktop-dropdown-nav"
}
const _hoisted_8 = ["onMouseenter"]
const _hoisted_9 = ["href", "target", "onClick", "aria-expanded"]
const _hoisted_10 = { class: "dropdown-content" }
const _hoisted_11 = {
  key: 0,
  class: "dropdown-section"
}
const _hoisted_12 = { class: "dropdown-section-header-wrapper" }
const _hoisted_13 = ["href", "target", "onClick"]
const _hoisted_14 = ["onClick"]
const _hoisted_15 = ["onClick", "aria-label"]
const _hoisted_16 = {
  key: 0,
  class: "dropdown-section-items"
}
const _hoisted_17 = ["href", "target", "onClick"]
const _hoisted_18 = ["href", "target", "onClick"]
const _hoisted_19 = ["href", "target", "onClick"]
const _hoisted_20 = { class: "desktop-nav desktop-megamenu-nav" }
const _hoisted_21 = ["onMouseenter"]
const _hoisted_22 = ["href", "target", "onClick", "aria-expanded"]
const _hoisted_23 = { class: "megamenu-grid" }
const _hoisted_24 = ["href", "target", "onClick"]
const _hoisted_25 = {
  key: 1,
  class: "megamenu-column-header"
}
const _hoisted_26 = {
  key: 2,
  class: "megamenu-list"
}
const _hoisted_27 = ["href", "target", "onClick"]
const _hoisted_28 = {
  key: 0,
  class: "megamenu-sublist"
}
const _hoisted_29 = ["href", "target", "onClick"]
const _hoisted_30 = ["href", "target", "onClick"]

function render(_ctx, _cache, $props, $setup, $data, $options) {
  const _component_FileTree = (0,vue__WEBPACK_IMPORTED_MODULE_0__.resolveComponent)("FileTree")
  const _component_Menu = (0,vue__WEBPACK_IMPORTED_MODULE_0__.resolveComponent)("Menu")
  const _component_ChevronRight = (0,vue__WEBPACK_IMPORTED_MODULE_0__.resolveComponent)("ChevronRight")
  const _component_ChevronDown = (0,vue__WEBPACK_IMPORTED_MODULE_0__.resolveComponent)("ChevronDown")
  const _component_NcActionButton = (0,vue__WEBPACK_IMPORTED_MODULE_0__.resolveComponent)("NcActionButton")
  const _component_NcActions = (0,vue__WEBPACK_IMPORTED_MODULE_0__.resolveComponent)("NcActions")

  return ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)("nav", _hoisted_1, [
    (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)(" Page Structure Button (left of navigation) "),
    (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("button", {
      class: "page-tree-btn",
      onClick: _cache[0] || (_cache[0] = (...args) => ($options.handleTreeClick && $options.handleTreeClick(...args))),
      "aria-label": $options.t('Page structure'),
      title: $options.t('Page structure')
    }, [
      (0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_FileTree, { size: 20 })
    ], 8 /* PROPS */, _hoisted_2),
    (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)(" Mobile hamburger menu "),
    (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("div", _hoisted_3, [
      (0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_NcActions, null, {
        icon: (0,vue__WEBPACK_IMPORTED_MODULE_0__.withCtx)(() => [
          (0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_Menu, { size: 20 })
        ]),
        default: (0,vue__WEBPACK_IMPORTED_MODULE_0__.withCtx)(() => [
          ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(true), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)(vue__WEBPACK_IMPORTED_MODULE_0__.Fragment, null, (0,vue__WEBPACK_IMPORTED_MODULE_0__.renderList)($props.items, (item) => {
            return ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)(vue__WEBPACK_IMPORTED_MODULE_0__.Fragment, {
              key: $options.getItemKey(item)
            }, [
              (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)(" Top level items with children "),
              (item.children && item.children.length > 0)
                ? ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)(vue__WEBPACK_IMPORTED_MODULE_0__.Fragment, { key: 0 }, [
                    (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)(" Title navigates, chevron expands "),
                    (0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_NcActionButton, {
                      onClick: $event => ($options.handleMobileItemClick(item))
                    }, {
                      icon: (0,vue__WEBPACK_IMPORTED_MODULE_0__.withCtx)(() => [
                        (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("span", {
                          class: "mobile-expand-btn",
                          onClick: (0,vue__WEBPACK_IMPORTED_MODULE_0__.withModifiers)($event => ($options.toggleMobileItem($options.getItemKey(item))), ["stop"])
                        }, [
                          (!$options.isMobileItemExpanded($options.getItemKey(item)))
                            ? ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createBlock)(_component_ChevronRight, {
                                key: 0,
                                size: 20
                              }))
                            : ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createBlock)(_component_ChevronDown, {
                                key: 1,
                                size: 20
                              }))
                        ], 8 /* PROPS */, _hoisted_4)
                      ]),
                      default: (0,vue__WEBPACK_IMPORTED_MODULE_0__.withCtx)(() => [
                        (0,vue__WEBPACK_IMPORTED_MODULE_0__.createTextVNode)(" " + (0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)($options.decodeHtmlEntities(item.title)), 1 /* TEXT */)
                      ]),
                      _: 2 /* DYNAMIC */
                    }, 1032 /* PROPS, DYNAMIC_SLOTS */, ["onClick"]),
                    (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)(" Level 2 items "),
                    ($options.isMobileItemExpanded($options.getItemKey(item)))
                      ? ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(true), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)(vue__WEBPACK_IMPORTED_MODULE_0__.Fragment, { key: 0 }, (0,vue__WEBPACK_IMPORTED_MODULE_0__.renderList)(item.children, (child) => {
                          return ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)(vue__WEBPACK_IMPORTED_MODULE_0__.Fragment, {
                            key: $options.getItemKey(child)
                          }, [
                            (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)(" Level 2 with children "),
                            (child.children && child.children.length > 0)
                              ? ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createBlock)(_component_NcActionButton, {
                                  key: 0,
                                  onClick: $event => ($options.handleMobileItemClick(child)),
                                  class: "mobile-nav-level-2"
                                }, {
                                  icon: (0,vue__WEBPACK_IMPORTED_MODULE_0__.withCtx)(() => [
                                    (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("span", {
                                      class: "mobile-expand-btn",
                                      onClick: (0,vue__WEBPACK_IMPORTED_MODULE_0__.withModifiers)($event => ($options.toggleMobileItem($options.getItemKey(child))), ["stop"])
                                    }, [
                                      (!$options.isMobileItemExpanded($options.getItemKey(child)))
                                        ? ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createBlock)(_component_ChevronRight, {
                                            key: 0,
                                            size: 20
                                          }))
                                        : ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createBlock)(_component_ChevronDown, {
                                            key: 1,
                                            size: 20
                                          }))
                                    ], 8 /* PROPS */, _hoisted_5)
                                  ]),
                                  default: (0,vue__WEBPACK_IMPORTED_MODULE_0__.withCtx)(() => [
                                    (0,vue__WEBPACK_IMPORTED_MODULE_0__.createTextVNode)(" " + (0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)($options.decodeHtmlEntities(child.title)), 1 /* TEXT */)
                                  ]),
                                  _: 2 /* DYNAMIC */
                                }, 1032 /* PROPS, DYNAMIC_SLOTS */, ["onClick"]))
                              : ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)(vue__WEBPACK_IMPORTED_MODULE_0__.Fragment, { key: 1 }, [
                                  (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)(" Level 2 without children "),
                                  (0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_NcActionButton, {
                                    onClick: $event => ($options.handleItemClick(child)),
                                    class: "mobile-nav-level-2"
                                  }, {
                                    default: (0,vue__WEBPACK_IMPORTED_MODULE_0__.withCtx)(() => [
                                      (0,vue__WEBPACK_IMPORTED_MODULE_0__.createTextVNode)((0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)($options.decodeHtmlEntities(child.title)), 1 /* TEXT */)
                                    ]),
                                    _: 2 /* DYNAMIC */
                                  }, 1032 /* PROPS, DYNAMIC_SLOTS */, ["onClick"])
                                ], 2112 /* STABLE_FRAGMENT, DEV_ROOT_FRAGMENT */)),
                            (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)(" Level 3 items "),
                            (child.children && child.children.length > 0 && $options.isMobileItemExpanded($options.getItemKey(child)))
                              ? ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)(vue__WEBPACK_IMPORTED_MODULE_0__.Fragment, { key: 2 }, [
                                  (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)(" Level 3 with children "),
                                  ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(true), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)(vue__WEBPACK_IMPORTED_MODULE_0__.Fragment, null, (0,vue__WEBPACK_IMPORTED_MODULE_0__.renderList)(child.children, (grandchild) => {
                                    return ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)(vue__WEBPACK_IMPORTED_MODULE_0__.Fragment, {
                                      key: $options.getItemKey(grandchild)
                                    }, [
                                      (grandchild.children && grandchild.children.length > 0)
                                        ? ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createBlock)(_component_NcActionButton, {
                                            key: 0,
                                            onClick: $event => ($options.handleMobileItemClick(grandchild)),
                                            class: "mobile-nav-level-3"
                                          }, {
                                            icon: (0,vue__WEBPACK_IMPORTED_MODULE_0__.withCtx)(() => [
                                              (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("span", {
                                                class: "mobile-expand-btn",
                                                onClick: (0,vue__WEBPACK_IMPORTED_MODULE_0__.withModifiers)($event => ($options.toggleMobileItem($options.getItemKey(grandchild))), ["stop"])
                                              }, [
                                                (!$options.isMobileItemExpanded($options.getItemKey(grandchild)))
                                                  ? ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createBlock)(_component_ChevronRight, {
                                                      key: 0,
                                                      size: 20
                                                    }))
                                                  : ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createBlock)(_component_ChevronDown, {
                                                      key: 1,
                                                      size: 20
                                                    }))
                                              ], 8 /* PROPS */, _hoisted_6)
                                            ]),
                                            default: (0,vue__WEBPACK_IMPORTED_MODULE_0__.withCtx)(() => [
                                              (0,vue__WEBPACK_IMPORTED_MODULE_0__.createTextVNode)(" " + (0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)($options.decodeHtmlEntities(grandchild.title)), 1 /* TEXT */)
                                            ]),
                                            _: 2 /* DYNAMIC */
                                          }, 1032 /* PROPS, DYNAMIC_SLOTS */, ["onClick"]))
                                        : ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)(vue__WEBPACK_IMPORTED_MODULE_0__.Fragment, { key: 1 }, [
                                            (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)(" Level 3 without children "),
                                            (0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_NcActionButton, {
                                              onClick: $event => ($options.handleItemClick(grandchild)),
                                              class: "mobile-nav-level-3"
                                            }, {
                                              default: (0,vue__WEBPACK_IMPORTED_MODULE_0__.withCtx)(() => [
                                                (0,vue__WEBPACK_IMPORTED_MODULE_0__.createTextVNode)((0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)($options.decodeHtmlEntities(grandchild.title)), 1 /* TEXT */)
                                              ]),
                                              _: 2 /* DYNAMIC */
                                            }, 1032 /* PROPS, DYNAMIC_SLOTS */, ["onClick"])
                                          ], 2112 /* STABLE_FRAGMENT, DEV_ROOT_FRAGMENT */)),
                                      (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)(" Level 4 items "),
                                      (grandchild.children && grandchild.children.length > 0 && $options.isMobileItemExpanded($options.getItemKey(grandchild)))
                                        ? ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(true), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)(vue__WEBPACK_IMPORTED_MODULE_0__.Fragment, { key: 2 }, (0,vue__WEBPACK_IMPORTED_MODULE_0__.renderList)(grandchild.children, (greatGrandchild) => {
                                            return ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createBlock)(_component_NcActionButton, {
                                              key: $options.getItemKey(greatGrandchild),
                                              onClick: $event => ($options.handleItemClick(greatGrandchild)),
                                              class: "mobile-nav-level-4"
                                            }, {
                                              default: (0,vue__WEBPACK_IMPORTED_MODULE_0__.withCtx)(() => [
                                                (0,vue__WEBPACK_IMPORTED_MODULE_0__.createTextVNode)((0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)($options.decodeHtmlEntities(greatGrandchild.title)), 1 /* TEXT */)
                                              ]),
                                              _: 2 /* DYNAMIC */
                                            }, 1032 /* PROPS, DYNAMIC_SLOTS */, ["onClick"]))
                                          }), 128 /* KEYED_FRAGMENT */))
                                        : (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)("v-if", true)
                                    ], 64 /* STABLE_FRAGMENT */))
                                  }), 128 /* KEYED_FRAGMENT */))
                                ], 64 /* STABLE_FRAGMENT */))
                              : (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)("v-if", true)
                          ], 64 /* STABLE_FRAGMENT */))
                        }), 128 /* KEYED_FRAGMENT */))
                      : (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)("v-if", true)
                  ], 64 /* STABLE_FRAGMENT */))
                : ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)(vue__WEBPACK_IMPORTED_MODULE_0__.Fragment, { key: 1 }, [
                    (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)(" Top level items without children "),
                    (0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_NcActionButton, {
                      onClick: $event => ($options.handleItemClick(item))
                    }, {
                      default: (0,vue__WEBPACK_IMPORTED_MODULE_0__.withCtx)(() => [
                        (0,vue__WEBPACK_IMPORTED_MODULE_0__.createTextVNode)((0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)($options.decodeHtmlEntities(item.title)), 1 /* TEXT */)
                      ]),
                      _: 2 /* DYNAMIC */
                    }, 1032 /* PROPS, DYNAMIC_SLOTS */, ["onClick"])
                  ], 2112 /* STABLE_FRAGMENT, DEV_ROOT_FRAGMENT */))
            ], 64 /* STABLE_FRAGMENT */))
          }), 128 /* KEYED_FRAGMENT */))
        ]),
        _: 1 /* STABLE */
      })
    ]),
    (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)(" Desktop Navigation - Cascading Dropdown "),
    ($props.type === 'dropdown')
      ? ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)("div", _hoisted_7, [
          ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(true), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)(vue__WEBPACK_IMPORTED_MODULE_0__.Fragment, null, (0,vue__WEBPACK_IMPORTED_MODULE_0__.renderList)($props.items, (item) => {
            return ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)(vue__WEBPACK_IMPORTED_MODULE_0__.Fragment, {
              key: $options.getItemKey(item)
            }, [
              (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)(" Top level with children "),
              (item.children && item.children.length > 0)
                ? ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)("div", {
                    key: 0,
                    class: "dropdown-item",
                    onMouseenter: $event => ($options.showDropdown(item, $event)),
                    onMouseleave: _cache[3] || (_cache[3] = (...args) => ($options.hideDropdown && $options.hideDropdown(...args)))
                  }, [
                    (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("a", {
                      href: $options.getItemUrl(item),
                      target: item.target || '_self',
                      onClick: (0,vue__WEBPACK_IMPORTED_MODULE_0__.withModifiers)($event => ($options.handleItemClick(item)), ["prevent"]),
                      class: "dropdown-trigger",
                      "aria-haspopup": "true",
                      "aria-expanded": $data.activeDropdown === $options.getItemKey(item)
                    }, [
                      (0,vue__WEBPACK_IMPORTED_MODULE_0__.createTextVNode)((0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)($options.decodeHtmlEntities(item.title)) + " ", 1 /* TEXT */),
                      (0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_ChevronDown, {
                        size: 16,
                        class: "chevron-icon"
                      })
                    ], 8 /* PROPS */, _hoisted_9),
                    (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)(" Dropdown menu "),
                    ($data.activeDropdown === $options.getItemKey(item))
                      ? ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)("div", {
                          key: 0,
                          class: "dropdown-menu",
                          onMouseenter: _cache[1] || (_cache[1] = (...args) => ($options.keepDropdownOpen && $options.keepDropdownOpen(...args))),
                          onMouseleave: _cache[2] || (_cache[2] = (...args) => ($options.hideDropdown && $options.hideDropdown(...args)))
                        }, [
                          (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("div", _hoisted_10, [
                            (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)(" Level 2 items "),
                            ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(true), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)(vue__WEBPACK_IMPORTED_MODULE_0__.Fragment, null, (0,vue__WEBPACK_IMPORTED_MODULE_0__.renderList)(item.children, (child) => {
                              return ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)(vue__WEBPACK_IMPORTED_MODULE_0__.Fragment, {
                                key: $options.getItemKey(child)
                              }, [
                                (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)(" Level 2 with children "),
                                (child.children && child.children.length > 0)
                                  ? ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)("div", _hoisted_11, [
                                      (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)(" Section header (Level 2) - clickable to expand/collapse "),
                                      (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("div", _hoisted_12, [
                                        (child.uniqueId || child.url)
                                          ? ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)("a", {
                                              key: 0,
                                              href: $options.getItemUrl(child),
                                              target: child.target || '_self',
                                              onClick: (0,vue__WEBPACK_IMPORTED_MODULE_0__.withModifiers)($event => ($options.handleItemClick(child)), ["prevent"]),
                                              class: "dropdown-section-header"
                                            }, (0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)($options.decodeHtmlEntities(child.title)), 9 /* TEXT, PROPS */, _hoisted_13))
                                          : ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)("div", {
                                              key: 1,
                                              class: "dropdown-section-header clickable",
                                              onClick: $event => ($options.toggleDropdownSection($options.getItemKey(child)))
                                            }, (0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)($options.decodeHtmlEntities(child.title)), 9 /* TEXT, PROPS */, _hoisted_14)),
                                        (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("button", {
                                          class: "dropdown-toggle-btn",
                                          onClick: $event => ($options.toggleDropdownSection($options.getItemKey(child))),
                                          "aria-label": $options.isDropdownSectionExpanded($options.getItemKey(child)) ? 'Collapse' : 'Expand'
                                        }, [
                                          ($options.isDropdownSectionExpanded($options.getItemKey(child)))
                                            ? ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createBlock)(_component_ChevronDown, {
                                                key: 0,
                                                size: 16
                                              }))
                                            : ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createBlock)(_component_ChevronRight, {
                                                key: 1,
                                                size: 16
                                              }))
                                        ], 8 /* PROPS */, _hoisted_15)
                                      ]),
                                      (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)(" Level 3 items - only show when expanded "),
                                      ($options.isDropdownSectionExpanded($options.getItemKey(child)))
                                        ? ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)("div", _hoisted_16, [
                                            ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(true), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)(vue__WEBPACK_IMPORTED_MODULE_0__.Fragment, null, (0,vue__WEBPACK_IMPORTED_MODULE_0__.renderList)(child.children, (grandchild) => {
                                              return ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)("a", {
                                                key: $options.getItemKey(grandchild),
                                                href: $options.getItemUrl(grandchild),
                                                target: grandchild.target || '_self',
                                                onClick: (0,vue__WEBPACK_IMPORTED_MODULE_0__.withModifiers)($event => ($options.handleItemClick(grandchild)), ["prevent"]),
                                                class: "dropdown-section-item"
                                              }, (0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)($options.decodeHtmlEntities(grandchild.title)), 9 /* TEXT, PROPS */, _hoisted_17))
                                            }), 128 /* KEYED_FRAGMENT */))
                                          ]))
                                        : (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)("v-if", true)
                                    ]))
                                  : ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)(vue__WEBPACK_IMPORTED_MODULE_0__.Fragment, { key: 1 }, [
                                      (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)(" Level 2 without children "),
                                      (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("a", {
                                        href: $options.getItemUrl(child),
                                        target: child.target || '_self',
                                        onClick: (0,vue__WEBPACK_IMPORTED_MODULE_0__.withModifiers)($event => ($options.handleItemClick(child)), ["prevent"]),
                                        class: "dropdown-item-link"
                                      }, (0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)($options.decodeHtmlEntities(child.title)), 9 /* TEXT, PROPS */, _hoisted_18)
                                    ], 2112 /* STABLE_FRAGMENT, DEV_ROOT_FRAGMENT */))
                              ], 64 /* STABLE_FRAGMENT */))
                            }), 128 /* KEYED_FRAGMENT */))
                          ])
                        ], 32 /* NEED_HYDRATION */))
                      : (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)("v-if", true)
                  ], 40 /* PROPS, NEED_HYDRATION */, _hoisted_8))
                : ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)(vue__WEBPACK_IMPORTED_MODULE_0__.Fragment, { key: 1 }, [
                    (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)(" Top level without children - regular link "),
                    (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("a", {
                      href: $options.getItemUrl(item),
                      target: item.target || '_self',
                      onClick: (0,vue__WEBPACK_IMPORTED_MODULE_0__.withModifiers)($event => ($options.handleItemClick(item)), ["prevent"]),
                      class: "nav-link"
                    }, (0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)($options.decodeHtmlEntities(item.title)), 9 /* TEXT, PROPS */, _hoisted_19)
                  ], 2112 /* STABLE_FRAGMENT, DEV_ROOT_FRAGMENT */))
            ], 64 /* STABLE_FRAGMENT */))
          }), 128 /* KEYED_FRAGMENT */))
        ]))
      : ($props.type === 'megamenu')
        ? ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)(vue__WEBPACK_IMPORTED_MODULE_0__.Fragment, { key: 1 }, [
            (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)(" Desktop Navigation - Mega Menu "),
            (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("div", _hoisted_20, [
              ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(true), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)(vue__WEBPACK_IMPORTED_MODULE_0__.Fragment, null, (0,vue__WEBPACK_IMPORTED_MODULE_0__.renderList)($props.items, (item) => {
                return ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)(vue__WEBPACK_IMPORTED_MODULE_0__.Fragment, {
                  key: $options.getItemKey(item)
                }, [
                  (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)(" Top level with children "),
                  (item.children && item.children.length > 0)
                    ? ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)("div", {
                        key: 0,
                        class: "megamenu-item",
                        onMouseenter: $event => ($options.showMegaMenu(item, $event)),
                        onMouseleave: _cache[6] || (_cache[6] = (...args) => ($options.hideMegaMenu && $options.hideMegaMenu(...args)))
                      }, [
                        (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("a", {
                          href: $options.getItemUrl(item),
                          target: item.target || '_self',
                          onClick: (0,vue__WEBPACK_IMPORTED_MODULE_0__.withModifiers)($event => ($options.handleItemClick(item)), ["prevent"]),
                          class: "megamenu-trigger",
                          "aria-haspopup": "true",
                          "aria-expanded": $data.activeMegaMenu === $options.getItemKey(item)
                        }, [
                          (0,vue__WEBPACK_IMPORTED_MODULE_0__.createTextVNode)((0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)($options.decodeHtmlEntities(item.title)) + " ", 1 /* TEXT */),
                          (0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_ChevronDown, {
                            size: 16,
                            class: "chevron-icon"
                          })
                        ], 8 /* PROPS */, _hoisted_22),
                        (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)(" Mega menu dropdown "),
                        ($data.activeMegaMenu === $options.getItemKey(item))
                          ? ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)("div", {
                              key: 0,
                              class: "megamenu-dropdown",
                              onMouseenter: _cache[4] || (_cache[4] = (...args) => ($options.keepMegaMenuOpen && $options.keepMegaMenuOpen(...args))),
                              onMouseleave: _cache[5] || (_cache[5] = (...args) => ($options.hideMegaMenu && $options.hideMegaMenu(...args)))
                            }, [
                              (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("div", _hoisted_23, [
                                (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)(" Level 2 columns "),
                                ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(true), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)(vue__WEBPACK_IMPORTED_MODULE_0__.Fragment, null, (0,vue__WEBPACK_IMPORTED_MODULE_0__.renderList)(item.children, (child) => {
                                  return ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)("div", {
                                    key: $options.getItemKey(child),
                                    class: "megamenu-column"
                                  }, [
                                    (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)(" Column header (Level 2) "),
                                    (child.uniqueId || child.url)
                                      ? ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)("a", {
                                          key: 0,
                                          href: $options.getItemUrl(child),
                                          target: child.target || '_self',
                                          onClick: (0,vue__WEBPACK_IMPORTED_MODULE_0__.withModifiers)($event => ($options.handleItemClick(child)), ["prevent"]),
                                          class: "megamenu-column-header"
                                        }, (0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)($options.decodeHtmlEntities(child.title)), 9 /* TEXT, PROPS */, _hoisted_24))
                                      : ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)("div", _hoisted_25, (0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)($options.decodeHtmlEntities(child.title)), 1 /* TEXT */)),
                                    (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)(" Level 3 items "),
                                    (child.children && child.children.length > 0)
                                      ? ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)("ul", _hoisted_26, [
                                          ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(true), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)(vue__WEBPACK_IMPORTED_MODULE_0__.Fragment, null, (0,vue__WEBPACK_IMPORTED_MODULE_0__.renderList)(child.children, (grandchild) => {
                                            return ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)("li", {
                                              key: $options.getItemKey(grandchild)
                                            }, [
                                              (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("a", {
                                                href: $options.getItemUrl(grandchild),
                                                target: grandchild.target || '_self',
                                                onClick: (0,vue__WEBPACK_IMPORTED_MODULE_0__.withModifiers)($event => ($options.handleItemClick(grandchild)), ["prevent"]),
                                                class: (0,vue__WEBPACK_IMPORTED_MODULE_0__.normalizeClass)(["megamenu-list-item", { 'has-children': grandchild.children && grandchild.children.length > 0 }])
                                              }, (0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)($options.decodeHtmlEntities(grandchild.title)), 11 /* TEXT, CLASS, PROPS */, _hoisted_27),
                                              (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)(" Level 4 items (nested under level 3) "),
                                              (grandchild.children && grandchild.children.length > 0)
                                                ? ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)("ul", _hoisted_28, [
                                                    ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(true), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)(vue__WEBPACK_IMPORTED_MODULE_0__.Fragment, null, (0,vue__WEBPACK_IMPORTED_MODULE_0__.renderList)(grandchild.children, (greatGrandchild) => {
                                                      return ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)("li", {
                                                        key: $options.getItemKey(greatGrandchild)
                                                      }, [
                                                        (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("a", {
                                                          href: $options.getItemUrl(greatGrandchild),
                                                          target: greatGrandchild.target || '_self',
                                                          onClick: (0,vue__WEBPACK_IMPORTED_MODULE_0__.withModifiers)($event => ($options.handleItemClick(greatGrandchild)), ["prevent"]),
                                                          class: "megamenu-sublist-item"
                                                        }, (0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)($options.decodeHtmlEntities(greatGrandchild.title)), 9 /* TEXT, PROPS */, _hoisted_29)
                                                      ]))
                                                    }), 128 /* KEYED_FRAGMENT */))
                                                  ]))
                                                : (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)("v-if", true)
                                            ]))
                                          }), 128 /* KEYED_FRAGMENT */))
                                        ]))
                                      : (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)("v-if", true)
                                  ]))
                                }), 128 /* KEYED_FRAGMENT */))
                              ])
                            ], 32 /* NEED_HYDRATION */))
                          : (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)("v-if", true)
                      ], 40 /* PROPS, NEED_HYDRATION */, _hoisted_21))
                    : ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)(vue__WEBPACK_IMPORTED_MODULE_0__.Fragment, { key: 1 }, [
                        (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)(" Top level without children "),
                        (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("a", {
                          href: $options.getItemUrl(item),
                          target: item.target || '_self',
                          onClick: (0,vue__WEBPACK_IMPORTED_MODULE_0__.withModifiers)($event => ($options.handleItemClick(item)), ["prevent"]),
                          class: "nav-link"
                        }, (0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)($options.decodeHtmlEntities(item.title)), 9 /* TEXT, PROPS */, _hoisted_30)
                      ], 2112 /* STABLE_FRAGMENT, DEV_ROOT_FRAGMENT */))
                ], 64 /* STABLE_FRAGMENT */))
              }), 128 /* KEYED_FRAGMENT */))
            ])
          ], 2112 /* STABLE_FRAGMENT, DEV_ROOT_FRAGMENT */))
        : (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)("v-if", true)
  ]))
}

/***/ },

/***/ "./node_modules/vue-loader/dist/templateLoader.js??ruleSet[1].rules[2]!./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/NoShareDialog.vue?vue&type=template&id=a9f8e2f4&scoped=true"
/*!******************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/vue-loader/dist/templateLoader.js??ruleSet[1].rules[2]!./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/NoShareDialog.vue?vue&type=template&id=a9f8e2f4&scoped=true ***!
  \******************************************************************************************************************************************************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   render: () => (/* binding */ render)
/* harmony export */ });
/* harmony import */ var vue__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! vue */ "./node_modules/vue/dist/vue.runtime.esm-bundler.js");


const _hoisted_1 = { class: "share-dialog" }
const _hoisted_2 = { class: "share-scope" }
const _hoisted_3 = { class: "scope-label" }
const _hoisted_4 = { class: "share-explanation" }
const _hoisted_5 = ["href"]
const _hoisted_6 = { class: "manage-text" }
const _hoisted_7 = { class: "share-explanation" }
const _hoisted_8 = ["href"]
const _hoisted_9 = { class: "manage-text" }

function render(_ctx, _cache, $props, $setup, $data, $options) {
  const _component_LinkVariantOff = (0,vue__WEBPACK_IMPORTED_MODULE_0__.resolveComponent)("LinkVariantOff")
  const _component_NcNoteCard = (0,vue__WEBPACK_IMPORTED_MODULE_0__.resolveComponent)("NcNoteCard")
  const _component_CogOutline = (0,vue__WEBPACK_IMPORTED_MODULE_0__.resolveComponent)("CogOutline")
  const _component_OpenInNew = (0,vue__WEBPACK_IMPORTED_MODULE_0__.resolveComponent)("OpenInNew")
  const _component_FolderOutline = (0,vue__WEBPACK_IMPORTED_MODULE_0__.resolveComponent)("FolderOutline")
  const _component_NcDialog = (0,vue__WEBPACK_IMPORTED_MODULE_0__.resolveComponent)("NcDialog")

  return ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createBlock)(_component_NcDialog, {
    name: $options.t('Share this page'),
    "can-close": true,
    onClose: _cache[0] || (_cache[0] = $event => (_ctx.$emit('close')))
  }, {
    default: (0,vue__WEBPACK_IMPORTED_MODULE_0__.withCtx)(() => [
      (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("div", _hoisted_1, [
        (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)(" Status indicator "),
        (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("div", _hoisted_2, [
          (0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_LinkVariantOff, { size: 20 }),
          (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("span", _hoisted_3, (0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)($options.t('This page is not shared publicly')), 1 /* TEXT */)
        ]),
        (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)(" NC link sharing disabled by admin "),
        ($options.isDisabledByAdmin)
          ? ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)(vue__WEBPACK_IMPORTED_MODULE_0__.Fragment, { key: 0 }, [
              (0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_NcNoteCard, { type: "warning" }, {
                default: (0,vue__WEBPACK_IMPORTED_MODULE_0__.withCtx)(() => [
                  (0,vue__WEBPACK_IMPORTED_MODULE_0__.createTextVNode)((0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)($options.t('Public link sharing is disabled by the administrator.')), 1 /* TEXT */)
                ]),
                _: 1 /* STABLE */
              }),
              (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("p", _hoisted_4, (0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)($options.t('To enable anonymous access to IntraVox pages, an administrator must first enable "Allow users to share via link and emails" in the Nextcloud Sharing settings.')), 1 /* TEXT */),
              (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("a", {
                href: $options.adminSharingUrl,
                target: "_blank",
                class: "share-manage-link"
              }, [
                (0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_CogOutline, { size: 16 }),
                (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("span", _hoisted_6, (0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)($options.t('Open Sharing settings')), 1 /* TEXT */),
                (0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_OpenInNew, {
                  size: 14,
                  class: "manage-arrow"
                })
              ], 8 /* PROPS */, _hoisted_5)
            ], 64 /* STABLE_FRAGMENT */))
          : ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)(vue__WEBPACK_IMPORTED_MODULE_0__.Fragment, { key: 1 }, [
              (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)(" No share exists, but sharing is possible "),
              (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("p", _hoisted_7, (0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)($options.t('To make this page accessible without login, create a public share link in the Files app.')), 1 /* TEXT */),
              ($options.filesUrl)
                ? ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)("a", {
                    key: 0,
                    href: $options.filesUrl,
                    target: "_blank",
                    class: "share-manage-link"
                  }, [
                    (0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_FolderOutline, { size: 16 }),
                    (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("span", _hoisted_9, (0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)($options.t('Open in Files')), 1 /* TEXT */),
                    (0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_OpenInNew, {
                      size: 14,
                      class: "manage-arrow"
                    })
                  ], 8 /* PROPS */, _hoisted_8))
                : (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)("v-if", true)
            ], 64 /* STABLE_FRAGMENT */))
      ])
    ]),
    _: 1 /* STABLE */
  }, 8 /* PROPS */, ["name"]))
}

/***/ },

/***/ "./node_modules/vue-loader/dist/templateLoader.js??ruleSet[1].rules[2]!./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/PageActionsMenu.vue?vue&type=template&id=457bfd6d&scoped=true"
/*!********************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/vue-loader/dist/templateLoader.js??ruleSet[1].rules[2]!./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/PageActionsMenu.vue?vue&type=template&id=457bfd6d&scoped=true ***!
  \********************************************************************************************************************************************************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   render: () => (/* binding */ render)
/* harmony export */ });
/* harmony import */ var vue__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! vue */ "./node_modules/vue/dist/vue.runtime.esm-bundler.js");


function render(_ctx, _cache, $props, $setup, $data, $options) {
  const _component_Plus = (0,vue__WEBPACK_IMPORTED_MODULE_0__.resolveComponent)("Plus")
  const _component_NcActionButton = (0,vue__WEBPACK_IMPORTED_MODULE_0__.resolveComponent)("NcActionButton")
  const _component_Cog = (0,vue__WEBPACK_IMPORTED_MODULE_0__.resolveComponent)("Cog")
  const _component_TuneVertical = (0,vue__WEBPACK_IMPORTED_MODULE_0__.resolveComponent)("TuneVertical")
  const _component_FileDocumentMultipleOutline = (0,vue__WEBPACK_IMPORTED_MODULE_0__.resolveComponent)("FileDocumentMultipleOutline")
  const _component_Rss = (0,vue__WEBPACK_IMPORTED_MODULE_0__.resolveComponent)("Rss")
  const _component_NcActions = (0,vue__WEBPACK_IMPORTED_MODULE_0__.resolveComponent)("NcActions")

  return ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createBlock)(_component_NcActions, null, {
    default: (0,vue__WEBPACK_IMPORTED_MODULE_0__.withCtx)(() => [
      (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)(" New Page "),
      ($options.canPerformAction('createPage'))
        ? ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createBlock)(_component_NcActionButton, {
            key: 0,
            onClick: _cache[0] || (_cache[0] = $event => (_ctx.$emit('create-page')))
          }, {
            icon: (0,vue__WEBPACK_IMPORTED_MODULE_0__.withCtx)(() => [
              (0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_Plus, { size: 20 })
            ]),
            default: (0,vue__WEBPACK_IMPORTED_MODULE_0__.withCtx)(() => [
              (0,vue__WEBPACK_IMPORTED_MODULE_0__.createTextVNode)(" " + (0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)($options.t('New Page')), 1 /* TEXT */)
            ]),
            _: 1 /* STABLE */
          }))
        : (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)("v-if", true),
      (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)(" Edit Navigation (admin action, less frequently used) "),
      ($options.canPerformAction('editNavigation'))
        ? ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createBlock)(_component_NcActionButton, {
            key: 1,
            onClick: _cache[1] || (_cache[1] = $event => (_ctx.$emit('edit-navigation')))
          }, {
            icon: (0,vue__WEBPACK_IMPORTED_MODULE_0__.withCtx)(() => [
              (0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_Cog, { size: 20 })
            ]),
            default: (0,vue__WEBPACK_IMPORTED_MODULE_0__.withCtx)(() => [
              (0,vue__WEBPACK_IMPORTED_MODULE_0__.createTextVNode)(" " + (0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)($options.t('Edit Navigation')), 1 /* TEXT */)
            ]),
            _: 1 /* STABLE */
          }))
        : (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)("v-if", true),
      (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)(" Page Settings (for editors) "),
      ($options.canPerformAction('editPage'))
        ? ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createBlock)(_component_NcActionButton, {
            key: 2,
            onClick: _cache[2] || (_cache[2] = $event => (_ctx.$emit('page-settings')))
          }, {
            icon: (0,vue__WEBPACK_IMPORTED_MODULE_0__.withCtx)(() => [
              (0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_TuneVertical, { size: 20 })
            ]),
            default: (0,vue__WEBPACK_IMPORTED_MODULE_0__.withCtx)(() => [
              (0,vue__WEBPACK_IMPORTED_MODULE_0__.createTextVNode)(" " + (0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)($options.t('Page Settings')), 1 /* TEXT */)
            ]),
            _: 1 /* STABLE */
          }))
        : (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)("v-if", true),
      (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)(" Save as Template "),
      ($options.canPerformAction('saveAsTemplate'))
        ? ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createBlock)(_component_NcActionButton, {
            key: 3,
            onClick: _cache[3] || (_cache[3] = $event => (_ctx.$emit('save-as-template')))
          }, {
            icon: (0,vue__WEBPACK_IMPORTED_MODULE_0__.withCtx)(() => [
              (0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_FileDocumentMultipleOutline, { size: 20 })
            ]),
            default: (0,vue__WEBPACK_IMPORTED_MODULE_0__.withCtx)(() => [
              (0,vue__WEBPACK_IMPORTED_MODULE_0__.createTextVNode)(" " + (0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)($options.t('Save as Template')), 1 /* TEXT */)
            ]),
            _: 1 /* STABLE */
          }))
        : (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)("v-if", true),
      (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)(" RSS Feed "),
      (0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_NcActionButton, {
        onClick: _cache[4] || (_cache[4] = $event => (_ctx.$emit('feed-settings')))
      }, {
        icon: (0,vue__WEBPACK_IMPORTED_MODULE_0__.withCtx)(() => [
          (0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_Rss, { size: 20 })
        ]),
        default: (0,vue__WEBPACK_IMPORTED_MODULE_0__.withCtx)(() => [
          (0,vue__WEBPACK_IMPORTED_MODULE_0__.createTextVNode)(" " + (0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)($options.t('RSS Feed')), 1 /* TEXT */)
        ]),
        _: 1 /* STABLE */
      })
    ]),
    _: 1 /* STABLE */
  }))
}

/***/ },

/***/ "./node_modules/vue-loader/dist/templateLoader.js??ruleSet[1].rules[2]!./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/PageViewer.vue?vue&type=template&id=3a22e99e&scoped=true"
/*!***************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/vue-loader/dist/templateLoader.js??ruleSet[1].rules[2]!./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/PageViewer.vue?vue&type=template&id=3a22e99e&scoped=true ***!
  \***************************************************************************************************************************************************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   render: () => (/* binding */ render)
/* harmony export */ });
/* harmony import */ var vue__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! vue */ "./node_modules/vue/dist/vue.runtime.esm-bundler.js");


const _hoisted_1 = { class: "page-viewer-wrapper" }
const _hoisted_2 = { class: "page-viewer" }
const _hoisted_3 = ["onClick"]
const _hoisted_4 = { class: "section-title" }
const _hoisted_5 = {
  key: 1,
  class: "reactions-comments-section"
}

function render(_ctx, _cache, $props, $setup, $data, $options) {
  const _component_Widget = (0,vue__WEBPACK_IMPORTED_MODULE_0__.resolveComponent)("Widget")
  const _component_ChevronDown = (0,vue__WEBPACK_IMPORTED_MODULE_0__.resolveComponent)("ChevronDown")
  const _component_ChevronRight = (0,vue__WEBPACK_IMPORTED_MODULE_0__.resolveComponent)("ChevronRight")
  const _component_ReactionBar = (0,vue__WEBPACK_IMPORTED_MODULE_0__.resolveComponent)("ReactionBar")
  const _component_CommentSection = (0,vue__WEBPACK_IMPORTED_MODULE_0__.resolveComponent)("CommentSection")

  return ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)("div", _hoisted_1, [
    (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)(" Header Row (spans full width above everything) "),
    ($options.hasHeaderRow)
      ? ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)("div", {
          key: 0,
          class: "header-row",
          style: (0,vue__WEBPACK_IMPORTED_MODULE_0__.normalizeStyle)($options.getHeaderRowStyle())
        }, [
          ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(true), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)(vue__WEBPACK_IMPORTED_MODULE_0__.Fragment, null, (0,vue__WEBPACK_IMPORTED_MODULE_0__.renderList)($options.getHeaderRowWidgets(), (widget) => {
            return ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createBlock)(_component_Widget, {
              key: widget.id || widget.order,
              widget: widget,
              "page-id": $props.page.uniqueId,
              editable: false,
              "row-background-color": $props.page.layout.headerRow.backgroundColor || '',
              "share-token": $props.shareToken,
              onNavigate: _cache[0] || (_cache[0] = $event => (_ctx.$emit('navigate', $event)))
            }, null, 8 /* PROPS */, ["widget", "page-id", "row-background-color", "share-token"]))
          }), 128 /* KEYED_FRAGMENT */))
        ], 4 /* STYLE */))
      : (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)("v-if", true),
    (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("div", {
      class: (0,vue__WEBPACK_IMPORTED_MODULE_0__.normalizeClass)(["page-viewer-container", { 'has-left-column': $options.hasLeftColumn, 'has-right-column': $options.hasRightColumn }])
    }, [
      (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)(" Left Side Column "),
      ($options.hasLeftColumn)
        ? ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)("div", {
            key: 0,
            class: "side-column side-column-left",
            style: (0,vue__WEBPACK_IMPORTED_MODULE_0__.normalizeStyle)($options.getSideColumnStyle('left'))
          }, [
            ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(true), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)(vue__WEBPACK_IMPORTED_MODULE_0__.Fragment, null, (0,vue__WEBPACK_IMPORTED_MODULE_0__.renderList)($options.getSideColumnWidgets('left'), (widget) => {
              return ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createBlock)(_component_Widget, {
                key: widget.id || widget.order,
                widget: widget,
                "page-id": $props.page.uniqueId,
                editable: false,
                "row-background-color": $props.page.layout.sideColumns.left.backgroundColor || '',
                "share-token": $props.shareToken,
                onNavigate: _cache[1] || (_cache[1] = $event => (_ctx.$emit('navigate', $event)))
              }, null, 8 /* PROPS */, ["widget", "page-id", "row-background-color", "share-token"]))
            }), 128 /* KEYED_FRAGMENT */))
          ], 4 /* STYLE */))
        : (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)("v-if", true),
      (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)(" Main Content "),
      (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("div", _hoisted_2, [
        ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(true), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)(vue__WEBPACK_IMPORTED_MODULE_0__.Fragment, null, (0,vue__WEBPACK_IMPORTED_MODULE_0__.renderList)($props.page.layout.rows, (row, rowIndex) => {
          return ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)("div", {
            key: rowIndex,
            class: (0,vue__WEBPACK_IMPORTED_MODULE_0__.normalizeClass)(["page-row", { 'collapsible-row': row.collapsible }]),
            style: (0,vue__WEBPACK_IMPORTED_MODULE_0__.normalizeStyle)($options.getRowStyle(row))
          }, [
            (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)(" Section Header (only for collapsible rows) "),
            (row.collapsible)
              ? ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)("div", {
                  key: 0,
                  class: (0,vue__WEBPACK_IMPORTED_MODULE_0__.normalizeClass)(["row-section-header", { collapsed: $options.isRowCollapsed(row) }]),
                  onClick: $event => ($options.toggleRowCollapsed(row))
                }, [
                  (!$options.isRowCollapsed(row))
                    ? ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createBlock)(_component_ChevronDown, {
                        key: 0,
                        size: 20,
                        class: "section-chevron"
                      }))
                    : ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createBlock)(_component_ChevronRight, {
                        key: 1,
                        size: 20,
                        class: "section-chevron"
                      })),
                  (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("span", _hoisted_4, (0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)(row.sectionTitle || 'Sectie'), 1 /* TEXT */)
                ], 10 /* CLASS, PROPS */, _hoisted_3))
              : (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)("v-if", true),
            (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)(" Row Content (collapsible) "),
            (0,vue__WEBPACK_IMPORTED_MODULE_0__.withDirectives)((0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("div", {
              class: (0,vue__WEBPACK_IMPORTED_MODULE_0__.normalizeClass)(["row-content", { collapsed: row.collapsible && $options.isRowCollapsed(row) }])
            }, [
              (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("div", {
                class: "page-grid",
                style: (0,vue__WEBPACK_IMPORTED_MODULE_0__.normalizeStyle)({ gridTemplateColumns: `repeat(${(row.columns || $props.page.layout.columns) ?? 1}, minmax(0, 1fr))` })
              }, [
                ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(true), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)(vue__WEBPACK_IMPORTED_MODULE_0__.Fragment, null, (0,vue__WEBPACK_IMPORTED_MODULE_0__.renderList)(((row.columns || $props.page.layout.columns) ?? 1), (column) => {
                  return ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)("div", {
                    key: column,
                    class: "page-column"
                  }, [
                    ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(true), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)(vue__WEBPACK_IMPORTED_MODULE_0__.Fragment, null, (0,vue__WEBPACK_IMPORTED_MODULE_0__.renderList)($options.getWidgetsForColumn(row, column), (widget) => {
                      return ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createBlock)(_component_Widget, {
                        key: widget.order,
                        widget: widget,
                        "page-id": $props.page.uniqueId,
                        editable: false,
                        "row-background-color": row.backgroundColor || '',
                        "share-token": $props.shareToken,
                        onNavigate: _cache[2] || (_cache[2] = $event => (_ctx.$emit('navigate', $event)))
                      }, null, 8 /* PROPS */, ["widget", "page-id", "row-background-color", "share-token"]))
                    }), 128 /* KEYED_FRAGMENT */))
                  ]))
                }), 128 /* KEYED_FRAGMENT */))
              ], 4 /* STYLE */)
            ], 2 /* CLASS */), [
              [vue__WEBPACK_IMPORTED_MODULE_0__.vShow, !row.collapsible || !$options.isRowCollapsed(row)]
            ])
          ], 6 /* CLASS, STYLE */))
        }), 128 /* KEYED_FRAGMENT */))
      ]),
      (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)(" Right Side Column "),
      ($options.hasRightColumn)
        ? ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)("div", {
            key: 1,
            class: "side-column side-column-right",
            style: (0,vue__WEBPACK_IMPORTED_MODULE_0__.normalizeStyle)($options.getSideColumnStyle('right'))
          }, [
            ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(true), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)(vue__WEBPACK_IMPORTED_MODULE_0__.Fragment, null, (0,vue__WEBPACK_IMPORTED_MODULE_0__.renderList)($options.getSideColumnWidgets('right'), (widget) => {
              return ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createBlock)(_component_Widget, {
                key: widget.id || widget.order,
                widget: widget,
                "page-id": $props.page.uniqueId,
                editable: false,
                "row-background-color": $props.page.layout.sideColumns.right.backgroundColor || '',
                "share-token": $props.shareToken,
                onNavigate: _cache[3] || (_cache[3] = $event => (_ctx.$emit('navigate', $event)))
              }, null, 8 /* PROPS */, ["widget", "page-id", "row-background-color", "share-token"]))
            }), 128 /* KEYED_FRAGMENT */))
          ], 4 /* STYLE */))
        : (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)("v-if", true)
    ], 2 /* CLASS */),
    (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)(" Reactions & Comments Section (full width, below all columns) "),
    ($options.showReactions || $options.showComments)
      ? ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)("div", _hoisted_5, [
          ($options.showReactions)
            ? ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createBlock)(_component_ReactionBar, {
                key: 0,
                "page-id": $props.page.uniqueId,
                reactions: $data.pageReactions,
                "user-reactions": $data.userReactions,
                onUpdate: $options.handleReactionsUpdate
              }, null, 8 /* PROPS */, ["page-id", "reactions", "user-reactions", "onUpdate"]))
            : (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)("v-if", true),
          ($options.showComments)
            ? ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createBlock)(_component_CommentSection, {
                key: 1,
                "page-id": $props.page.uniqueId,
                "allow-comment-reactions": $options.allowCommentReactions
              }, null, 8 /* PROPS */, ["page-id", "allow-comment-reactions"]))
            : (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)("v-if", true)
        ]))
      : (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)("v-if", true)
  ]))
}

/***/ },

/***/ "./node_modules/vue-loader/dist/templateLoader.js??ruleSet[1].rules[2]!./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/PublicPageView.vue?vue&type=template&id=5157670d&scoped=true"
/*!*******************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/vue-loader/dist/templateLoader.js??ruleSet[1].rules[2]!./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/PublicPageView.vue?vue&type=template&id=5157670d&scoped=true ***!
  \*******************************************************************************************************************************************************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   render: () => (/* binding */ render)
/* harmony export */ });
/* harmony import */ var vue__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! vue */ "./node_modules/vue/dist/vue.runtime.esm-bundler.js");


const _hoisted_1 = { id: "intravox-public-page" }
const _hoisted_2 = {
  href: "#intravox-main-content",
  class: "skip-link"
}
const _hoisted_3 = {
  key: 0,
  class: "public-loading",
  role: "status",
  "aria-live": "polite"
}
const _hoisted_4 = { class: "public-password-challenge" }
const _hoisted_5 = { class: "password-container" }
const _hoisted_6 = {
  for: "intravox-public-password",
  class: "visually-hidden"
}
const _hoisted_7 = ["placeholder"]
const _hoisted_8 = {
  key: 0,
  class: "password-error",
  role: "alert"
}
const _hoisted_9 = ["disabled"]
const _hoisted_10 = {
  class: "public-error",
  role: "alert"
}
const _hoisted_11 = { class: "public-header" }
const _hoisted_12 = { class: "page-title" }
const _hoisted_13 = {
  key: 0,
  class: "public-nav-bar"
}
const _hoisted_14 = {
  key: 1,
  class: "public-breadcrumb"
}
const _hoisted_15 = {
  class: "public-content",
  id: "intravox-main-content"
}
const _hoisted_16 = {
  key: 3,
  class: "public-footer"
}

function render(_ctx, _cache, $props, $setup, $data, $options) {
  const _component_Navigation = (0,vue__WEBPACK_IMPORTED_MODULE_0__.resolveComponent)("Navigation")
  const _component_Breadcrumb = (0,vue__WEBPACK_IMPORTED_MODULE_0__.resolveComponent)("Breadcrumb")
  const _component_PageTreeModal = (0,vue__WEBPACK_IMPORTED_MODULE_0__.resolveComponent)("PageTreeModal")
  const _component_PageViewer = (0,vue__WEBPACK_IMPORTED_MODULE_0__.resolveComponent)("PageViewer")
  const _component_Footer = (0,vue__WEBPACK_IMPORTED_MODULE_0__.resolveComponent)("Footer")

  return ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)("div", _hoisted_1, [
    (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("a", _hoisted_2, (0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)($options.t('intravox', 'Skip to main content')), 1 /* TEXT */),
    (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)(" Loading state "),
    ($data.loading)
      ? ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)("div", _hoisted_3, [
          _cache[4] || (_cache[4] = (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("div", { class: "loading-spinner" }, null, -1 /* CACHED */)),
          (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("span", null, (0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)($options.t('intravox', 'Loading...')), 1 /* TEXT */)
        ]))
      : ($data.passwordRequired)
        ? ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)(vue__WEBPACK_IMPORTED_MODULE_0__.Fragment, { key: 1 }, [
            (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)(" Password required state "),
            (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("div", _hoisted_4, [
              (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("div", _hoisted_5, [
                _cache[5] || (_cache[5] = (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("div", { class: "lock-icon" }, "🔒", -1 /* CACHED */)),
                (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("h2", null, (0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)($options.t('intravox', 'Password Required')), 1 /* TEXT */),
                (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("p", null, (0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)($options.t('intravox', 'This shared page is password protected. Enter the password to continue.')), 1 /* TEXT */),
                (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("form", {
                  onSubmit: _cache[1] || (_cache[1] = (0,vue__WEBPACK_IMPORTED_MODULE_0__.withModifiers)((...args) => ($options.submitPassword && $options.submitPassword(...args)), ["prevent"]))
                }, [
                  (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("label", _hoisted_6, (0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)($options.t('intravox', 'Password')), 1 /* TEXT */),
                  (0,vue__WEBPACK_IMPORTED_MODULE_0__.withDirectives)((0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("input", {
                    id: "intravox-public-password",
                    ref: "passwordInput",
                    "onUpdate:modelValue": _cache[0] || (_cache[0] = $event => (($data.password) = $event)),
                    type: "password",
                    placeholder: $options.t('intravox', 'Password'),
                    autocomplete: "off",
                    required: ""
                  }, null, 8 /* PROPS */, _hoisted_7), [
                    [vue__WEBPACK_IMPORTED_MODULE_0__.vModelText, $data.password]
                  ]),
                  ($data.passwordError)
                    ? ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)("p", _hoisted_8, (0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)($options.t('intravox', 'Incorrect password. Please try again.')), 1 /* TEXT */))
                    : (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)("v-if", true),
                  (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("button", {
                    type: "submit",
                    disabled: $data.submittingPassword
                  }, (0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)($options.t('intravox', 'Unlock')), 9 /* TEXT, PROPS */, _hoisted_9)
                ], 32 /* NEED_HYDRATION */)
              ])
            ])
          ], 2112 /* STABLE_FRAGMENT, DEV_ROOT_FRAGMENT */))
        : ($data.error)
          ? ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)(vue__WEBPACK_IMPORTED_MODULE_0__.Fragment, { key: 2 }, [
              (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)(" Error state "),
              (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("div", _hoisted_10, [
                (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("h1", null, (0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)($options.t('intravox', 'Page Not Available')), 1 /* TEXT */),
                (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("p", null, (0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)($data.error), 1 /* TEXT */)
              ])
            ], 2112 /* STABLE_FRAGMENT, DEV_ROOT_FRAGMENT */))
          : ($data.pageData)
            ? ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)(vue__WEBPACK_IMPORTED_MODULE_0__.Fragment, { key: 3 }, [
                (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)(" Page content "),
                (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)(" Header "),
                (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("header", _hoisted_11, [
                  (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("h1", _hoisted_12, (0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)($data.pageData.title), 1 /* TEXT */)
                ]),
                (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)(" Navigation (if available) "),
                ($data.navigation && $data.navigation.items && $data.navigation.items.length > 0)
                  ? ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)("div", _hoisted_13, [
                      (0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_Navigation, {
                        items: $data.navigation.items,
                        type: $data.navigation.type,
                        "is-public": true,
                        onNavigate: $options.handleNavigation,
                        onShowTree: _cache[2] || (_cache[2] = $event => ($data.showPageTree = true))
                      }, null, 8 /* PROPS */, ["items", "type", "onNavigate"])
                    ]))
                  : (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)("v-if", true),
                (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)(" Breadcrumb "),
                ($data.breadcrumb.length > 0)
                  ? ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)("div", _hoisted_14, [
                      (0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_Breadcrumb, {
                        breadcrumb: $data.breadcrumb,
                        onNavigate: $options.handleBreadcrumbNavigate
                      }, null, 8 /* PROPS */, ["breadcrumb", "onNavigate"])
                    ]))
                  : (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)("v-if", true),
                (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)(" Page Tree Modal "),
                ($data.showPageTree)
                  ? ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createBlock)(_component_PageTreeModal, {
                      key: 2,
                      "share-token": $props.token,
                      "current-page-id": $options.currentPageUniqueId,
                      onNavigate: $options.handleTreeNavigate,
                      onClose: _cache[3] || (_cache[3] = $event => ($data.showPageTree = false))
                    }, null, 8 /* PROPS */, ["share-token", "current-page-id", "onNavigate"]))
                  : (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)("v-if", true),
                (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)(" Main content "),
                (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("main", _hoisted_15, [
                  (0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_PageViewer, {
                    page: $data.pageData,
                    "is-public": true,
                    "share-token": $props.token,
                    "engagement-settings": $data.engagementSettings,
                    onNavigate: $options.handleNavigation
                  }, null, 8 /* PROPS */, ["page", "share-token", "engagement-settings", "onNavigate"])
                ]),
                (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)(" Footer "),
                ($data.footerContent)
                  ? ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)("div", _hoisted_16, [
                      (0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_Footer, {
                        "footer-content": $data.footerContent,
                        "can-edit": false,
                        "is-public": true
                      }, null, 8 /* PROPS */, ["footer-content"])
                    ]))
                  : (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)("v-if", true)
              ], 64 /* STABLE_FRAGMENT */))
            : (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)("v-if", true)
  ]))
}

/***/ },

/***/ "./node_modules/vue-loader/dist/templateLoader.js??ruleSet[1].rules[2]!./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/ShareButton.vue?vue&type=template&id=29169d71&scoped=true"
/*!****************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/vue-loader/dist/templateLoader.js??ruleSet[1].rules[2]!./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/ShareButton.vue?vue&type=template&id=29169d71&scoped=true ***!
  \****************************************************************************************************************************************************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   render: () => (/* binding */ render)
/* harmony export */ });
/* harmony import */ var vue__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! vue */ "./node_modules/vue/dist/vue.runtime.esm-bundler.js");


const _hoisted_1 = {
  key: 0,
  class: "share-button-container"
}

function render(_ctx, _cache, $props, $setup, $data, $options) {
  const _component_LinkVariant = (0,vue__WEBPACK_IMPORTED_MODULE_0__.resolveComponent)("LinkVariant")
  const _component_NcButton = (0,vue__WEBPACK_IMPORTED_MODULE_0__.resolveComponent)("NcButton")
  const _component_LinkVariantOff = (0,vue__WEBPACK_IMPORTED_MODULE_0__.resolveComponent)("LinkVariantOff")
  const _component_ShareDialog = (0,vue__WEBPACK_IMPORTED_MODULE_0__.resolveComponent)("ShareDialog")
  const _component_NoShareDialog = (0,vue__WEBPACK_IMPORTED_MODULE_0__.resolveComponent)("NoShareDialog")

  return ($data.shareInfo !== null)
    ? ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)("div", _hoisted_1, [
        (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)(" Active share: primary colored icon "),
        ($options.hasShare)
          ? ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createBlock)(_component_NcButton, {
              key: 0,
              type: "tertiary",
              "aria-label": $options.t('Public link'),
              title: $options.t('This page is shared via a public link'),
              class: "share-button-active",
              onClick: _cache[0] || (_cache[0] = $event => ($data.showDialog = true))
            }, {
              icon: (0,vue__WEBPACK_IMPORTED_MODULE_0__.withCtx)(() => [
                (0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_LinkVariant, { size: 20 })
              ]),
              _: 1 /* STABLE */
            }, 8 /* PROPS */, ["aria-label", "title"]))
          : ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)(vue__WEBPACK_IMPORTED_MODULE_0__.Fragment, { key: 1 }, [
              (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)(" No share: muted icon "),
              (0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_NcButton, {
                type: "tertiary",
                "aria-label": $options.t('Share this page'),
                title: $options.t('Share this page'),
                class: "share-button-inactive",
                onClick: _cache[1] || (_cache[1] = $event => ($data.showNoShareDialog = true))
              }, {
                icon: (0,vue__WEBPACK_IMPORTED_MODULE_0__.withCtx)(() => [
                  (0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_LinkVariantOff, { size: 20 })
                ]),
                _: 1 /* STABLE */
              }, 8 /* PROPS */, ["aria-label", "title"])
            ], 2112 /* STABLE_FRAGMENT, DEV_ROOT_FRAGMENT */)),
        ($data.showDialog)
          ? ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createBlock)(_component_ShareDialog, {
              key: 2,
              "share-info": $data.shareInfo,
              "page-title": $props.pageTitle,
              onClose: _cache[2] || (_cache[2] = $event => ($data.showDialog = false))
            }, null, 8 /* PROPS */, ["share-info", "page-title"]))
          : (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)("v-if", true),
        ($data.showNoShareDialog)
          ? ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createBlock)(_component_NoShareDialog, {
              key: 3,
              "share-info": $data.shareInfo,
              "page-title": $props.pageTitle,
              onClose: _cache[3] || (_cache[3] = $event => ($data.showNoShareDialog = false))
            }, null, 8 /* PROPS */, ["share-info", "page-title"]))
          : (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)("v-if", true)
      ]))
    : (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)("v-if", true)
}

/***/ },

/***/ "./node_modules/vue-loader/dist/templateLoader.js??ruleSet[1].rules[2]!./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/ShareDialog.vue?vue&type=template&id=7dad0ef2&scoped=true"
/*!****************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/vue-loader/dist/templateLoader.js??ruleSet[1].rules[2]!./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/ShareDialog.vue?vue&type=template&id=7dad0ef2&scoped=true ***!
  \****************************************************************************************************************************************************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   render: () => (/* binding */ render)
/* harmony export */ });
/* harmony import */ var vue__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! vue */ "./node_modules/vue/dist/vue.runtime.esm-bundler.js");


const _hoisted_1 = { class: "share-dialog" }
const _hoisted_2 = { class: "share-scope" }
const _hoisted_3 = { class: "scope-label" }
const _hoisted_4 = {
  key: 0,
  class: "share-password-badge"
}
const _hoisted_5 = { class: "password-label" }
const _hoisted_6 = { class: "password-hint" }
const _hoisted_7 = { class: "share-copy-row" }
const _hoisted_8 = {
  key: 1,
  class: "share-navigation"
}
const _hoisted_9 = { class: "nav-tree" }
const _hoisted_10 = {
  key: 0,
  class: "nav-tree-children"
}
const _hoisted_11 = ["href"]
const _hoisted_12 = { class: "manage-text" }

function render(_ctx, _cache, $props, $setup, $data, $options) {
  const _component_FolderOutline = (0,vue__WEBPACK_IMPORTED_MODULE_0__.resolveComponent)("FolderOutline")
  const _component_FileDocumentOutline = (0,vue__WEBPACK_IMPORTED_MODULE_0__.resolveComponent)("FileDocumentOutline")
  const _component_LockOutline = (0,vue__WEBPACK_IMPORTED_MODULE_0__.resolveComponent)("LockOutline")
  const _component_ContentCopy = (0,vue__WEBPACK_IMPORTED_MODULE_0__.resolveComponent)("ContentCopy")
  const _component_NcButton = (0,vue__WEBPACK_IMPORTED_MODULE_0__.resolveComponent)("NcButton")
  const _component_NcNoteCard = (0,vue__WEBPACK_IMPORTED_MODULE_0__.resolveComponent)("NcNoteCard")
  const _component_OpenInNew = (0,vue__WEBPACK_IMPORTED_MODULE_0__.resolveComponent)("OpenInNew")
  const _component_NcDialog = (0,vue__WEBPACK_IMPORTED_MODULE_0__.resolveComponent)("NcDialog")

  return ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createBlock)(_component_NcDialog, {
    name: $options.t('Public Link'),
    "can-close": true,
    onClose: _cache[0] || (_cache[0] = $event => (_ctx.$emit('close')))
  }, {
    default: (0,vue__WEBPACK_IMPORTED_MODULE_0__.withCtx)(() => [
      (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("div", _hoisted_1, [
        (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)(" Scope indicator "),
        (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("div", _hoisted_2, [
          ($props.shareInfo.scope === 'folder')
            ? ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createBlock)(_component_FolderOutline, {
                key: 0,
                size: 20
              }))
            : ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createBlock)(_component_FileDocumentOutline, {
                key: 1,
                size: 20
              })),
          (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("span", _hoisted_3, [
            ($props.shareInfo.scope === 'page')
              ? ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)(vue__WEBPACK_IMPORTED_MODULE_0__.Fragment, { key: 0 }, [
                  (0,vue__WEBPACK_IMPORTED_MODULE_0__.createTextVNode)((0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)($options.t('This link shares only this page')), 1 /* TEXT */)
                ], 64 /* STABLE_FRAGMENT */))
              : ($props.shareInfo.isLanguageRoot)
                ? ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)(vue__WEBPACK_IMPORTED_MODULE_0__.Fragment, { key: 1 }, [
                    (0,vue__WEBPACK_IMPORTED_MODULE_0__.createTextVNode)((0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)($options.t('This link shares all {language} pages', { language: $props.shareInfo.scopeLabel })), 1 /* TEXT */)
                  ], 64 /* STABLE_FRAGMENT */))
                : ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)(vue__WEBPACK_IMPORTED_MODULE_0__.Fragment, { key: 2 }, [
                    (0,vue__WEBPACK_IMPORTED_MODULE_0__.createTextVNode)((0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)($options.t('This link shares the "{section}" section', { section: $props.shareInfo.scopeLabel || $props.shareInfo.scopeName })), 1 /* TEXT */)
                  ], 64 /* STABLE_FRAGMENT */))
          ])
        ]),
        (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)(" Password protected indicator "),
        ($props.shareInfo.hasPassword)
          ? ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)("div", _hoisted_4, [
              (0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_LockOutline, { size: 16 }),
              (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("span", _hoisted_5, (0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)($options.t('Password protected')), 1 /* TEXT */),
              (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("span", _hoisted_6, (0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)($options.t('Visitors must enter a password to access this link. Manage in Files.')), 1 /* TEXT */)
            ]))
          : (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)("v-if", true),
        (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)(" Copy link button "),
        (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("div", _hoisted_7, [
          (0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_NcButton, {
            type: "secondary",
            wide: "",
            onClick: $options.copyUrl
          }, {
            icon: (0,vue__WEBPACK_IMPORTED_MODULE_0__.withCtx)(() => [
              (0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_ContentCopy, { size: 20 })
            ]),
            default: (0,vue__WEBPACK_IMPORTED_MODULE_0__.withCtx)(() => [
              (0,vue__WEBPACK_IMPORTED_MODULE_0__.createTextVNode)(" " + (0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)($options.t('Copy public link')), 1 /* TEXT */)
            ]),
            _: 1 /* STABLE */
          }, 8 /* PROPS */, ["onClick"])
        ]),
        (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)(" Navigation tree (for folder shares) "),
        ($props.shareInfo.scope === 'folder' && $props.shareInfo.navigation?.length > 0)
          ? ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)("div", _hoisted_8, [
              (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("h4", null, (0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)($options.t('Includes these pages:')), 1 /* TEXT */),
              (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("ul", _hoisted_9, [
                ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(true), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)(vue__WEBPACK_IMPORTED_MODULE_0__.Fragment, null, (0,vue__WEBPACK_IMPORTED_MODULE_0__.renderList)($props.shareInfo.navigation, (item) => {
                  return ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)("li", {
                    key: item.title,
                    class: "nav-tree-item"
                  }, [
                    (0,vue__WEBPACK_IMPORTED_MODULE_0__.createTextVNode)((0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)(item.title) + " ", 1 /* TEXT */),
                    (item.children?.length > 0)
                      ? ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)("ul", _hoisted_10, [
                          ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(true), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)(vue__WEBPACK_IMPORTED_MODULE_0__.Fragment, null, (0,vue__WEBPACK_IMPORTED_MODULE_0__.renderList)(item.children, (child) => {
                            return ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)("li", {
                              key: child.title,
                              class: "nav-tree-child"
                            }, (0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)(child.title), 1 /* TEXT */))
                          }), 128 /* KEYED_FRAGMENT */))
                        ]))
                      : (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)("v-if", true)
                  ]))
                }), 128 /* KEYED_FRAGMENT */))
              ])
            ]))
          : (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)("v-if", true),
        (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)(" Read-only warning "),
        (0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_NcNoteCard, {
          type: "info",
          class: "share-info-note"
        }, {
          default: (0,vue__WEBPACK_IMPORTED_MODULE_0__.withCtx)(() => [
            (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("p", null, (0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)($options.t('Visitors can only VIEW this content. Editing via this link is not possible, even if the Files share allows it.')), 1 /* TEXT */)
          ]),
          _: 1 /* STABLE */
        }),
        (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)(" Manage link - clickable "),
        (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("a", {
          href: $options.filesUrl,
          target: "_blank",
          class: "share-manage-link"
        }, [
          (0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_FolderOutline, { size: 16 }),
          (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("span", _hoisted_12, (0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)($options.t('Manage share in Files')), 1 /* TEXT */),
          (0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_OpenInNew, {
            size: 14,
            class: "manage-arrow"
          })
        ], 8 /* PROPS */, _hoisted_11)
      ])
    ]),
    _: 1 /* STABLE */
  }, 8 /* PROPS */, ["name"]))
}

/***/ },

/***/ "./node_modules/vue-loader/dist/templateLoader.js??ruleSet[1].rules[2]!./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/Widget.vue?vue&type=template&id=4ca18318&scoped=true"
/*!***********************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/vue-loader/dist/templateLoader.js??ruleSet[1].rules[2]!./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/Widget.vue?vue&type=template&id=4ca18318&scoped=true ***!
  \***********************************************************************************************************************************************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   render: () => (/* binding */ render)
/* harmony export */ });
/* harmony import */ var vue__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! vue */ "./node_modules/vue/dist/vue.runtime.esm-bundler.js");


const _hoisted_1 = {
  key: 0,
  class: "widget-text"
}
const _hoisted_2 = ["innerHTML"]
const _hoisted_3 = { class: "widget-image" }
const _hoisted_4 = ["href", "target", "rel"]
const _hoisted_5 = ["src", "alt"]
const _hoisted_6 = ["src", "alt"]
const _hoisted_7 = {
  key: 2,
  class: "placeholder"
}
const _hoisted_8 = { class: "widget-video" }
const _hoisted_9 = {
  key: 0,
  class: "video-blocked"
}
const _hoisted_10 = { class: "video-blocked-title" }
const _hoisted_11 = {
  key: 0,
  class: "video-blocked-url"
}
const _hoisted_12 = { class: "url-value" }
const _hoisted_13 = {
  key: 1,
  class: "video-blocked-url"
}
const _hoisted_14 = { class: "url-label" }
const _hoisted_15 = { class: "url-value" }
const _hoisted_16 = { class: "video-blocked-hint" }
const _hoisted_17 = { class: "video-embed-wrapper" }
const _hoisted_18 = { class: "video-container" }
const _hoisted_19 = ["src", "title"]
const _hoisted_20 = { class: "video-container video-local" }
const _hoisted_21 = ["src", "title", "autoplay", "loop", "muted", "preload"]
const _hoisted_22 = {
  key: 0,
  class: "video-error-overlay"
}
const _hoisted_23 = { class: "error-message" }
const _hoisted_24 = { class: "error-reason" }
const _hoisted_25 = { class: "placeholder" }
const _hoisted_26 = {
  key: 4,
  class: "video-title"
}
const _hoisted_27 = { class: "widget-unknown" }

function render(_ctx, _cache, $props, $setup, $data, $options) {
  const _component_InlineTextEditor = (0,vue__WEBPACK_IMPORTED_MODULE_0__.resolveComponent)("InlineTextEditor")
  const _component_LinksWidget = (0,vue__WEBPACK_IMPORTED_MODULE_0__.resolveComponent)("LinksWidget")
  const _component_NewsWidget = (0,vue__WEBPACK_IMPORTED_MODULE_0__.resolveComponent)("NewsWidget")
  const _component_PeopleWidget = (0,vue__WEBPACK_IMPORTED_MODULE_0__.resolveComponent)("PeopleWidget")
  const _component_CalendarWidget = (0,vue__WEBPACK_IMPORTED_MODULE_0__.resolveComponent)("CalendarWidget")
  const _component_FeedWidget = (0,vue__WEBPACK_IMPORTED_MODULE_0__.resolveComponent)("FeedWidget")
  const _component_PhotoStoryWidget = (0,vue__WEBPACK_IMPORTED_MODULE_0__.resolveComponent)("PhotoStoryWidget")
  const _component_FileStoryWidget = (0,vue__WEBPACK_IMPORTED_MODULE_0__.resolveComponent)("FileStoryWidget")

  return ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)("div", {
    class: (0,vue__WEBPACK_IMPORTED_MODULE_0__.normalizeClass)(["widget", `widget-${$props.widget.type}`]),
    style: (0,vue__WEBPACK_IMPORTED_MODULE_0__.normalizeStyle)($options.textColorStyle)
  }, [
    (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)(" Text Widget with Inline Editing "),
    ($props.widget.type === 'text')
      ? ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)("div", _hoisted_1, [
          ($props.editable)
            ? ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createBlock)(_component_InlineTextEditor, {
                key: 0,
                modelValue: $data.localContent,
                "onUpdate:modelValue": _cache[0] || (_cache[0] = $event => (($data.localContent) = $event)),
                editable: true,
                compact: $data.isCompactMode,
                placeholder: $options.t('Enter text...'),
                onFocus: _cache[1] || (_cache[1] = $event => (_ctx.$emit('focus'))),
                onBlur: $options.onBlur
              }, null, 8 /* PROPS */, ["modelValue", "compact", "placeholder", "onBlur"]))
            : ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)("div", {
                key: 1,
                innerHTML: $options.sanitizeHtml($props.widget.content || '')
              }, null, 8 /* PROPS */, _hoisted_2))
        ]))
      : ($props.widget.type === 'heading')
        ? ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)(vue__WEBPACK_IMPORTED_MODULE_0__.Fragment, { key: 1 }, [
            (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)(" Heading Widget "),
            ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createBlock)((0,vue__WEBPACK_IMPORTED_MODULE_0__.resolveDynamicComponent)(`h${$props.widget.level}`), {
              class: "widget-heading",
              innerHTML: $options.sanitizedContent
            }, null, 8 /* PROPS */, ["innerHTML"]))
          ], 2112 /* STABLE_FRAGMENT, DEV_ROOT_FRAGMENT */))
        : ($props.widget.type === 'image')
          ? ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)(vue__WEBPACK_IMPORTED_MODULE_0__.Fragment, { key: 2 }, [
              (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)(" Image Widget "),
              (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("div", _hoisted_3, [
                (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)(" Image with link "),
                ($props.widget.src && $options.hasImageLink)
                  ? ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)("a", {
                      key: 0,
                      href: $options.getImageLinkHref(),
                      target: $options.getImageLinkTarget(),
                      rel: $options.getImageLinkRel(),
                      class: "image-link",
                      onClick: _cache[2] || (_cache[2] = (...args) => ($options.handleImageLinkClick && $options.handleImageLinkClick(...args)))
                    }, [
                      (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("img", {
                        src: $options.getImageUrl($props.widget.src),
                        alt: $props.widget.alt,
                        style: (0,vue__WEBPACK_IMPORTED_MODULE_0__.normalizeStyle)($options.getImageStyle()),
                        loading: "lazy"
                      }, null, 12 /* STYLE, PROPS */, _hoisted_5)
                    ], 8 /* PROPS */, _hoisted_4))
                  : ($props.widget.src)
                    ? ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)(vue__WEBPACK_IMPORTED_MODULE_0__.Fragment, { key: 1 }, [
                        (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)(" Image without link "),
                        (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("img", {
                          src: $options.getImageUrl($props.widget.src),
                          alt: $props.widget.alt,
                          style: (0,vue__WEBPACK_IMPORTED_MODULE_0__.normalizeStyle)($options.getImageStyle()),
                          loading: "lazy"
                        }, null, 12 /* STYLE, PROPS */, _hoisted_6)
                      ], 2112 /* STABLE_FRAGMENT, DEV_ROOT_FRAGMENT */))
                    : ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)("div", _hoisted_7, [
                        (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("span", null, (0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)($options.t('No image selected')), 1 /* TEXT */)
                      ]))
              ])
            ], 2112 /* STABLE_FRAGMENT, DEV_ROOT_FRAGMENT */))
          : ($props.widget.type === 'links')
            ? ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)(vue__WEBPACK_IMPORTED_MODULE_0__.Fragment, { key: 3 }, [
                (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)(" Links Widget (Grid of Links) "),
                (0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_LinksWidget, {
                  widget: $props.widget,
                  "row-background-color": $props.rowBackgroundColor,
                  onNavigate: _cache[3] || (_cache[3] = $event => (_ctx.$emit('navigate', $event)))
                }, null, 8 /* PROPS */, ["widget", "row-background-color"])
              ], 2112 /* STABLE_FRAGMENT, DEV_ROOT_FRAGMENT */))
            : ($props.widget.type === 'divider')
              ? ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)(vue__WEBPACK_IMPORTED_MODULE_0__.Fragment, { key: 4 }, [
                  (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)(" Divider Widget "),
                  (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("div", {
                    class: "widget-divider",
                    style: (0,vue__WEBPACK_IMPORTED_MODULE_0__.normalizeStyle)($options.getDividerStyle())
                  }, null, 4 /* STYLE */)
                ], 2112 /* STABLE_FRAGMENT, DEV_ROOT_FRAGMENT */))
              : ($props.widget.type === 'news')
                ? ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)(vue__WEBPACK_IMPORTED_MODULE_0__.Fragment, { key: 5 }, [
                    (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)(" News Widget "),
                    (0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_NewsWidget, {
                      widget: $props.widget,
                      "share-token": $props.shareToken,
                      "page-id": $props.pageId,
                      "row-background-color": $props.rowBackgroundColor,
                      onNavigate: _cache[4] || (_cache[4] = $event => (_ctx.$emit('navigate', $event)))
                    }, null, 8 /* PROPS */, ["widget", "share-token", "page-id", "row-background-color"])
                  ], 2112 /* STABLE_FRAGMENT, DEV_ROOT_FRAGMENT */))
                : ($props.widget.type === 'people')
                  ? ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)(vue__WEBPACK_IMPORTED_MODULE_0__.Fragment, { key: 6 }, [
                      (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)(" People Widget "),
                      (0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_PeopleWidget, {
                        widget: $props.widget,
                        "share-token": $props.shareToken,
                        "page-id": $props.pageId,
                        "row-background-color": $props.rowBackgroundColor
                      }, null, 8 /* PROPS */, ["widget", "share-token", "page-id", "row-background-color"])
                    ], 2112 /* STABLE_FRAGMENT, DEV_ROOT_FRAGMENT */))
                  : ($props.widget.type === 'calendar')
                    ? ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)(vue__WEBPACK_IMPORTED_MODULE_0__.Fragment, { key: 7 }, [
                        (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)(" Calendar Widget "),
                        (0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_CalendarWidget, {
                          widget: $props.widget,
                          "share-token": $props.shareToken,
                          "page-id": $props.pageId,
                          "row-background-color": $props.rowBackgroundColor
                        }, null, 8 /* PROPS */, ["widget", "share-token", "page-id", "row-background-color"])
                      ], 2112 /* STABLE_FRAGMENT, DEV_ROOT_FRAGMENT */))
                    : ($props.widget.type === 'feed')
                      ? ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)(vue__WEBPACK_IMPORTED_MODULE_0__.Fragment, { key: 8 }, [
                          (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)(" Feed Widget "),
                          (0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_FeedWidget, {
                            widget: $props.widget,
                            "share-token": $props.shareToken,
                            "page-id": $props.pageId,
                            "row-background-color": $props.rowBackgroundColor
                          }, null, 8 /* PROPS */, ["widget", "share-token", "page-id", "row-background-color"])
                        ], 2112 /* STABLE_FRAGMENT, DEV_ROOT_FRAGMENT */))
                      : ($props.widget.type === 'photo-story')
                        ? ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)(vue__WEBPACK_IMPORTED_MODULE_0__.Fragment, { key: 9 }, [
                            (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)(" Photo Story Widget "),
                            (0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_PhotoStoryWidget, {
                              widget: $props.widget,
                              "row-background-color": $props.rowBackgroundColor
                            }, null, 8 /* PROPS */, ["widget", "row-background-color"])
                          ], 2112 /* STABLE_FRAGMENT, DEV_ROOT_FRAGMENT */))
                        : ($props.widget.type === 'file-story')
                          ? ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)(vue__WEBPACK_IMPORTED_MODULE_0__.Fragment, { key: 10 }, [
                              (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)(" File Story Widget "),
                              (0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_FileStoryWidget, {
                                widget: $props.widget,
                                "row-background-color": $props.rowBackgroundColor
                              }, null, 8 /* PROPS */, ["widget", "row-background-color"])
                            ], 2112 /* STABLE_FRAGMENT, DEV_ROOT_FRAGMENT */))
                          : ($props.widget.type === 'video')
                            ? ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)(vue__WEBPACK_IMPORTED_MODULE_0__.Fragment, { key: 11 }, [
                                (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)(" Video Widget "),
                                (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("div", _hoisted_8, [
                                  (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)(" Blocked Video - domain not in whitelist "),
                                  ($props.widget.blocked)
                                    ? ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)("div", _hoisted_9, [
                                        _cache[9] || (_cache[9] = (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("div", { class: "video-blocked-icon" }, "🚫", -1 /* CACHED */)),
                                        (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("p", _hoisted_10, (0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)($options.t('Video service not allowed')), 1 /* TEXT */),
                                        ($props.widget.originalSrc)
                                          ? ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)("p", _hoisted_11, [
                                              _cache[8] || (_cache[8] = (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("span", { class: "url-label" }, "URL:", -1 /* CACHED */)),
                                              (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("code", _hoisted_12, (0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)($props.widget.originalSrc), 1 /* TEXT */)
                                            ]))
                                          : ($props.widget.blockedDomain)
                                            ? ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)("p", _hoisted_13, [
                                                (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("span", _hoisted_14, (0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)($options.t('Domain')) + ":", 1 /* TEXT */),
                                                (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("code", _hoisted_15, (0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)($props.widget.blockedDomain), 1 /* TEXT */)
                                              ]))
                                            : (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)("v-if", true),
                                        (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("p", _hoisted_16, (0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)($options.t('Please contact your administrator if you need access to this video service.')), 1 /* TEXT */)
                                      ]))
                                    : ($props.widget.provider !== 'local' && $props.widget.src)
                                      ? ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)(vue__WEBPACK_IMPORTED_MODULE_0__.Fragment, { key: 1 }, [
                                          (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)(" Embed Video (YouTube, Vimeo, PeerTube, etc.) "),
                                          (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("div", _hoisted_17, [
                                            (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("div", _hoisted_18, [
                                              (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("iframe", {
                                                src: $options.getEmbedUrl($props.widget),
                                                title: $props.widget.title || $options.t('Video'),
                                                frameborder: "0",
                                                allow: "autoplay; encrypted-media; picture-in-picture; fullscreen",
                                                sandbox: "allow-scripts allow-same-origin allow-presentation allow-popups",
                                                allowfullscreen: "",
                                                loading: "lazy",
                                                referrerpolicy: "strict-origin-when-cross-origin",
                                                onLoad: _cache[5] || (_cache[5] = (...args) => ($options.onVideoEmbedLoad && $options.onVideoEmbedLoad(...args))),
                                                onError: _cache[6] || (_cache[6] = (...args) => ($options.onVideoEmbedError && $options.onVideoEmbedError(...args)))
                                              }, null, 40 /* PROPS, NEED_HYDRATION */, _hoisted_19)
                                            ])
                                          ])
                                        ], 2112 /* STABLE_FRAGMENT, DEV_ROOT_FRAGMENT */))
                                      : ($props.widget.provider === 'local' && $props.widget.src)
                                        ? ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)(vue__WEBPACK_IMPORTED_MODULE_0__.Fragment, { key: 2 }, [
                                            (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)(" Local Video File "),
                                            (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("div", _hoisted_20, [
                                              (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("video", {
                                                src: $options.getVideoUrl($props.widget.src),
                                                title: $props.widget.title,
                                                controls: "",
                                                playsinline: "",
                                                autoplay: $props.widget.autoplay,
                                                loop: $props.widget.loop,
                                                muted: $props.widget.muted || $props.widget.autoplay,
                                                preload: $props.widget.autoplay ? 'auto' : 'metadata',
                                                onError: _cache[7] || (_cache[7] = (...args) => ($options.onLocalVideoError && $options.onLocalVideoError(...args)))
                                              }, (0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)($options.t('Your browser does not support HTML5 video.')), 41 /* TEXT, PROPS, NEED_HYDRATION */, _hoisted_21),
                                              (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)(" Error message for local videos "),
                                              ($data.localVideoError)
                                                ? ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)("div", _hoisted_22, [
                                                    (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("p", _hoisted_23, (0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)($options.t('Video cannot be played')), 1 /* TEXT */),
                                                    (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("p", _hoisted_24, (0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)($data.localVideoError), 1 /* TEXT */)
                                                  ]))
                                                : (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)("v-if", true)
                                            ])
                                          ], 2112 /* STABLE_FRAGMENT, DEV_ROOT_FRAGMENT */))
                                        : ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)(vue__WEBPACK_IMPORTED_MODULE_0__.Fragment, { key: 3 }, [
                                            (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)(" Placeholder "),
                                            (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("div", _hoisted_25, [
                                              (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("span", null, (0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)($options.t('No video selected')), 1 /* TEXT */)
                                            ])
                                          ], 2112 /* STABLE_FRAGMENT, DEV_ROOT_FRAGMENT */)),
                                  ($props.widget.title && !$props.widget.blocked)
                                    ? ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)("p", _hoisted_26, (0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)($props.widget.title), 1 /* TEXT */))
                                    : (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)("v-if", true)
                                ])
                              ], 2112 /* STABLE_FRAGMENT, DEV_ROOT_FRAGMENT */))
                            : ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)(vue__WEBPACK_IMPORTED_MODULE_0__.Fragment, { key: 12 }, [
                                (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)(" Unknown Widget Type "),
                                (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("div", _hoisted_27, (0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)($options.t('Unknown widget type: {type}', { type: $props.widget.type })), 1 /* TEXT */)
                              ], 2112 /* STABLE_FRAGMENT, DEV_ROOT_FRAGMENT */))
  ], 6 /* CLASS, STYLE */))
}

/***/ },

/***/ "./node_modules/vue-loader/dist/templateLoader.js??ruleSet[1].rules[2]!./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/reactions/CommentItem.vue?vue&type=template&id=6211c46d&scoped=true"
/*!**************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/vue-loader/dist/templateLoader.js??ruleSet[1].rules[2]!./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/reactions/CommentItem.vue?vue&type=template&id=6211c46d&scoped=true ***!
  \**************************************************************************************************************************************************************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   render: () => (/* binding */ render)
/* harmony export */ });
/* harmony import */ var vue__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! vue */ "./node_modules/vue/dist/vue.runtime.esm-bundler.js");


const _hoisted_1 = { class: "comment-item__header" }
const _hoisted_2 = { class: "comment-item__meta" }
const _hoisted_3 = { class: "comment-item__author" }
const _hoisted_4 = ["title"]
const _hoisted_5 = {
  key: 0,
  class: "comment-item__edited"
}
const _hoisted_6 = {
  key: 0,
  class: "comment-item__actions"
}
const _hoisted_7 = { class: "comment-item__body" }
const _hoisted_8 = {
  key: 0,
  class: "comment-item__edit"
}
const _hoisted_9 = { class: "comment-item__edit-actions" }
const _hoisted_10 = { class: "comment-item__message" }
const _hoisted_11 = { class: "comment-item__footer" }
const _hoisted_12 = { class: "comment-item__buttons" }
const _hoisted_13 = {
  key: 0,
  class: "comment-item__reactions"
}
const _hoisted_14 = ["disabled", "onClick"]
const _hoisted_15 = {
  key: 0,
  class: "comment-item__replies"
}

function render(_ctx, _cache, $props, $setup, $data, $options) {
  const _component_NcAvatar = (0,vue__WEBPACK_IMPORTED_MODULE_0__.resolveComponent)("NcAvatar")
  const _component_Pencil = (0,vue__WEBPACK_IMPORTED_MODULE_0__.resolveComponent)("Pencil")
  const _component_NcActionButton = (0,vue__WEBPACK_IMPORTED_MODULE_0__.resolveComponent)("NcActionButton")
  const _component_Delete = (0,vue__WEBPACK_IMPORTED_MODULE_0__.resolveComponent)("Delete")
  const _component_NcActions = (0,vue__WEBPACK_IMPORTED_MODULE_0__.resolveComponent)("NcActions")
  const _component_NcButton = (0,vue__WEBPACK_IMPORTED_MODULE_0__.resolveComponent)("NcButton")
  const _component_Reply = (0,vue__WEBPACK_IMPORTED_MODULE_0__.resolveComponent)("Reply")
  const _component_EmoticonHappyOutline = (0,vue__WEBPACK_IMPORTED_MODULE_0__.resolveComponent)("EmoticonHappyOutline")
  const _component_ReactionPicker = (0,vue__WEBPACK_IMPORTED_MODULE_0__.resolveComponent)("ReactionPicker")
  const _component_CommentItem = (0,vue__WEBPACK_IMPORTED_MODULE_0__.resolveComponent)("CommentItem", true)

  return ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)("div", {
    class: (0,vue__WEBPACK_IMPORTED_MODULE_0__.normalizeClass)(["comment-item", { 'comment-item--reply': $props.isReply }])
  }, [
    (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("div", _hoisted_1, [
      (0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_NcAvatar, {
        user: $props.comment.userId,
        "display-name": $props.comment.displayName,
        size: $props.isReply ? 28 : 32
      }, null, 8 /* PROPS */, ["user", "display-name", "size"]),
      (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("div", _hoisted_2, [
        (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("span", _hoisted_3, (0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)($props.comment.displayName), 1 /* TEXT */),
        (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("span", {
          class: "comment-item__time",
          title: $options.formattedDate
        }, (0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)($options.relativeTime), 9 /* TEXT, PROPS */, _hoisted_4),
        ($props.comment.isEdited)
          ? ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)("span", _hoisted_5, "(" + (0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)($options.t('intravox', 'edited')) + ")", 1 /* TEXT */))
          : (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)("v-if", true)
      ]),
      ($options.canModify)
        ? ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)("div", _hoisted_6, [
            (0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_NcActions, null, {
              default: (0,vue__WEBPACK_IMPORTED_MODULE_0__.withCtx)(() => [
                (0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_NcActionButton, { onClick: $options.startEdit }, {
                  icon: (0,vue__WEBPACK_IMPORTED_MODULE_0__.withCtx)(() => [
                    (0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_Pencil, { size: 20 })
                  ]),
                  default: (0,vue__WEBPACK_IMPORTED_MODULE_0__.withCtx)(() => [
                    (0,vue__WEBPACK_IMPORTED_MODULE_0__.createTextVNode)(" " + (0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)($options.t('intravox', 'Edit')), 1 /* TEXT */)
                  ]),
                  _: 1 /* STABLE */
                }, 8 /* PROPS */, ["onClick"]),
                (0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_NcActionButton, { onClick: $options.confirmDelete }, {
                  icon: (0,vue__WEBPACK_IMPORTED_MODULE_0__.withCtx)(() => [
                    (0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_Delete, { size: 20 })
                  ]),
                  default: (0,vue__WEBPACK_IMPORTED_MODULE_0__.withCtx)(() => [
                    (0,vue__WEBPACK_IMPORTED_MODULE_0__.createTextVNode)(" " + (0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)($options.t('intravox', 'Delete')), 1 /* TEXT */)
                  ]),
                  _: 1 /* STABLE */
                }, 8 /* PROPS */, ["onClick"])
              ]),
              _: 1 /* STABLE */
            })
          ]))
        : (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)("v-if", true)
    ]),
    (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("div", _hoisted_7, [
      (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)(" Edit mode "),
      ($data.editing)
        ? ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)("div", _hoisted_8, [
            (0,vue__WEBPACK_IMPORTED_MODULE_0__.withDirectives)((0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("textarea", {
              ref: "editInput",
              "onUpdate:modelValue": _cache[0] || (_cache[0] = $event => (($data.editMessage) = $event)),
              class: "comment-item__edit-input",
              rows: "3",
              onKeydown: [
                _cache[1] || (_cache[1] = (0,vue__WEBPACK_IMPORTED_MODULE_0__.withKeys)((0,vue__WEBPACK_IMPORTED_MODULE_0__.withModifiers)((...args) => ($options.saveEdit && $options.saveEdit(...args)), ["ctrl"]), ["enter"])),
                _cache[2] || (_cache[2] = (0,vue__WEBPACK_IMPORTED_MODULE_0__.withKeys)((...args) => ($options.cancelEdit && $options.cancelEdit(...args)), ["escape"]))
              ]
            }, null, 544 /* NEED_HYDRATION, NEED_PATCH */), [
              [vue__WEBPACK_IMPORTED_MODULE_0__.vModelText, $data.editMessage]
            ]),
            (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("div", _hoisted_9, [
              (0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_NcButton, {
                type: "tertiary",
                onClick: $options.cancelEdit
              }, {
                default: (0,vue__WEBPACK_IMPORTED_MODULE_0__.withCtx)(() => [
                  (0,vue__WEBPACK_IMPORTED_MODULE_0__.createTextVNode)((0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)($options.t('intravox', 'Cancel')), 1 /* TEXT */)
                ]),
                _: 1 /* STABLE */
              }, 8 /* PROPS */, ["onClick"]),
              (0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_NcButton, {
                type: "primary",
                disabled: !$data.editMessage.trim(),
                onClick: $options.saveEdit
              }, {
                default: (0,vue__WEBPACK_IMPORTED_MODULE_0__.withCtx)(() => [
                  (0,vue__WEBPACK_IMPORTED_MODULE_0__.createTextVNode)((0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)($options.t('intravox', 'Save')), 1 /* TEXT */)
                ]),
                _: 1 /* STABLE */
              }, 8 /* PROPS */, ["disabled", "onClick"])
            ])
          ]))
        : ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)(vue__WEBPACK_IMPORTED_MODULE_0__.Fragment, { key: 1 }, [
            (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)(" View mode "),
            (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("p", _hoisted_10, (0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)($props.comment.message), 1 /* TEXT */)
          ], 2112 /* STABLE_FRAGMENT, DEV_ROOT_FRAGMENT */))
    ]),
    (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)(" Reactions on comment "),
    (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("div", _hoisted_11, [
      (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)(" Buttons on the left "),
      (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("div", _hoisted_12, [
        (!$props.isReply)
          ? ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createBlock)(_component_NcButton, {
              key: 0,
              type: "tertiary",
              onClick: _cache[3] || (_cache[3] = $event => (_ctx.$emit('reply')))
            }, {
              icon: (0,vue__WEBPACK_IMPORTED_MODULE_0__.withCtx)(() => [
                (0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_Reply, { size: 16 })
              ]),
              default: (0,vue__WEBPACK_IMPORTED_MODULE_0__.withCtx)(() => [
                (0,vue__WEBPACK_IMPORTED_MODULE_0__.createTextVNode)(" " + (0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)($options.t('intravox', 'Reply')), 1 /* TEXT */)
              ]),
              _: 1 /* STABLE */
            }))
          : (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)("v-if", true),
        ($props.allowReactions)
          ? ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createBlock)(_component_ReactionPicker, {
              key: 1,
              visible: $data.showReactionPicker,
              "onUpdate:visible": _cache[4] || (_cache[4] = $event => (($data.showReactionPicker) = $event)),
              "selected-emojis": $data.localUserReactions,
              onSelect: $options.addReaction
            }, {
              trigger: (0,vue__WEBPACK_IMPORTED_MODULE_0__.withCtx)(() => [
                (0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_NcButton, {
                  type: "tertiary",
                  "aria-label": $options.t('intravox', 'Add reaction')
                }, {
                  icon: (0,vue__WEBPACK_IMPORTED_MODULE_0__.withCtx)(() => [
                    (0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_EmoticonHappyOutline, { size: 16 })
                  ]),
                  _: 1 /* STABLE */
                }, 8 /* PROPS */, ["aria-label"])
              ]),
              _: 1 /* STABLE */
            }, 8 /* PROPS */, ["visible", "selected-emojis", "onSelect"]))
          : (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)("v-if", true)
      ]),
      (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)(" Reactions after buttons (always show existing reactions, even if adding new is disabled) "),
      ($options.hasReactions)
        ? ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)("div", _hoisted_13, [
            ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(true), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)(vue__WEBPACK_IMPORTED_MODULE_0__.Fragment, null, (0,vue__WEBPACK_IMPORTED_MODULE_0__.renderList)($props.comment.reactions, (count, emoji) => {
              return ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)("button", {
                key: emoji,
                class: (0,vue__WEBPACK_IMPORTED_MODULE_0__.normalizeClass)(["comment-item__reaction", { 'comment-item__reaction--active': $options.isUserReaction(emoji) }]),
                disabled: !$props.allowReactions,
                onClick: $event => ($props.allowReactions && $options.toggleReaction(emoji))
              }, (0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)(emoji) + " " + (0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)(count), 11 /* TEXT, CLASS, PROPS */, _hoisted_14))
            }), 128 /* KEYED_FRAGMENT */))
          ]))
        : (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)("v-if", true)
    ]),
    (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)(" Replies (only for top-level comments) "),
    (!$props.isReply && $props.comment.replies && $props.comment.replies.length > 0)
      ? ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)("div", _hoisted_15, [
          ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(true), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)(vue__WEBPACK_IMPORTED_MODULE_0__.Fragment, null, (0,vue__WEBPACK_IMPORTED_MODULE_0__.renderList)($props.comment.replies, (reply) => {
            return ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createBlock)(_component_CommentItem, {
              key: reply.id,
              comment: reply,
              "current-user-id": $props.currentUserId,
              "is-reply": true,
              "allow-reactions": $props.allowReactions,
              onUpdate: _cache[5] || (_cache[5] = $event => (_ctx.$emit('update', $event))),
              onDelete: _cache[6] || (_cache[6] = $event => (_ctx.$emit('delete', $event)))
            }, null, 8 /* PROPS */, ["comment", "current-user-id", "allow-reactions"]))
          }), 128 /* KEYED_FRAGMENT */))
        ]))
      : (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)("v-if", true)
  ], 2 /* CLASS */))
}

/***/ },

/***/ "./node_modules/vue-loader/dist/templateLoader.js??ruleSet[1].rules[2]!./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/reactions/CommentSection.vue?vue&type=template&id=7504410a&scoped=true"
/*!*****************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/vue-loader/dist/templateLoader.js??ruleSet[1].rules[2]!./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/reactions/CommentSection.vue?vue&type=template&id=7504410a&scoped=true ***!
  \*****************************************************************************************************************************************************************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   render: () => (/* binding */ render)
/* harmony export */ });
/* harmony import */ var vue__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! vue */ "./node_modules/vue/dist/vue.runtime.esm-bundler.js");


const _hoisted_1 = { class: "comment-section" }
const _hoisted_2 = { class: "comment-section__header" }
const _hoisted_3 = { class: "comment-section__title" }
const _hoisted_4 = {
  key: 0,
  class: "comment-section__count"
}
const _hoisted_5 = { class: "comment-section__input" }
const _hoisted_6 = { class: "comment-section__input-wrapper" }
const _hoisted_7 = ["placeholder", "aria-label"]
const _hoisted_8 = {
  key: 0,
  class: "comment-section__submit"
}
const _hoisted_9 = { class: "comment-section__hint" }
const _hoisted_10 = {
  key: 0,
  class: "comment-section__loading"
}
const _hoisted_11 = {
  key: 1,
  class: "comment-section__empty"
}
const _hoisted_12 = {
  key: 2,
  class: "comment-section__list"
}
const _hoisted_13 = {
  key: 0,
  class: "comment-section__inline-reply"
}
const _hoisted_14 = { class: "comment-section__inline-reply-wrapper" }
const _hoisted_15 = { class: "comment-section__replying-indicator" }
const _hoisted_16 = ["placeholder", "aria-label"]
const _hoisted_17 = { class: "comment-section__reply-actions" }

function render(_ctx, _cache, $props, $setup, $data, $options) {
  const _component_CommentTextOutline = (0,vue__WEBPACK_IMPORTED_MODULE_0__.resolveComponent)("CommentTextOutline")
  const _component_NcSelect = (0,vue__WEBPACK_IMPORTED_MODULE_0__.resolveComponent)("NcSelect")
  const _component_NcAvatar = (0,vue__WEBPACK_IMPORTED_MODULE_0__.resolveComponent)("NcAvatar")
  const _component_Send = (0,vue__WEBPACK_IMPORTED_MODULE_0__.resolveComponent)("Send")
  const _component_NcButton = (0,vue__WEBPACK_IMPORTED_MODULE_0__.resolveComponent)("NcButton")
  const _component_NcLoadingIcon = (0,vue__WEBPACK_IMPORTED_MODULE_0__.resolveComponent)("NcLoadingIcon")
  const _component_CommentItem = (0,vue__WEBPACK_IMPORTED_MODULE_0__.resolveComponent)("CommentItem")
  const _component_Close = (0,vue__WEBPACK_IMPORTED_MODULE_0__.resolveComponent)("Close")

  return ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)("div", _hoisted_1, [
    (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("div", _hoisted_2, [
      (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("h3", _hoisted_3, [
        (0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_CommentTextOutline, { size: 20 }),
        (0,vue__WEBPACK_IMPORTED_MODULE_0__.createTextVNode)(" " + (0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)($options.t('intravox', 'Comments')) + " ", 1 /* TEXT */),
        ($data.totalComments > 0)
          ? ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)("span", _hoisted_4, "(" + (0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)($data.totalComments) + ")", 1 /* TEXT */))
          : (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)("v-if", true)
      ]),
      (0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_NcSelect, {
        modelValue: $data.sortOrder,
        "onUpdate:modelValue": _cache[0] || (_cache[0] = $event => (($data.sortOrder) = $event)),
        options: $data.sortOptions,
        clearable: false,
        searchable: false,
        "aria-label": $options.t('intravox', 'Sort comments'),
        class: "comment-section__sort"
      }, null, 8 /* PROPS */, ["modelValue", "options", "aria-label"])
    ]),
    (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)(" New comment input (at top) "),
    (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("div", _hoisted_5, [
      (0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_NcAvatar, {
        user: $options.currentUserId,
        size: 32
      }, null, 8 /* PROPS */, ["user"]),
      (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("div", _hoisted_6, [
        (0,vue__WEBPACK_IMPORTED_MODULE_0__.withDirectives)((0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("textarea", {
          ref: "commentInput",
          "onUpdate:modelValue": _cache[1] || (_cache[1] = $event => (($data.newComment) = $event)),
          class: "comment-section__textarea",
          placeholder: $options.t('intravox', 'Write a comment...'),
          "aria-label": $options.t('intravox', 'Write a comment'),
          rows: "2",
          onKeydown: _cache[2] || (_cache[2] = (0,vue__WEBPACK_IMPORTED_MODULE_0__.withKeys)((0,vue__WEBPACK_IMPORTED_MODULE_0__.withModifiers)((...args) => ($options.submitComment && $options.submitComment(...args)), ["ctrl"]), ["enter"])),
          onFocus: _cache[3] || (_cache[3] = $event => ($data.inputFocused = true)),
          onBlur: _cache[4] || (_cache[4] = $event => ($data.inputFocused = false))
        }, null, 40 /* PROPS, NEED_HYDRATION */, _hoisted_7), [
          [vue__WEBPACK_IMPORTED_MODULE_0__.vModelText, $data.newComment]
        ]),
        ($data.inputFocused || $data.newComment.trim())
          ? ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)("div", _hoisted_8, [
              (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("span", _hoisted_9, (0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)($options.t('intravox', 'Ctrl+Enter to send')), 1 /* TEXT */),
              (0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_NcButton, {
                type: "primary",
                disabled: !$data.newComment.trim() || $data.submitting,
                onClick: $options.submitComment
              }, {
                icon: (0,vue__WEBPACK_IMPORTED_MODULE_0__.withCtx)(() => [
                  (0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_Send, { size: 20 })
                ]),
                default: (0,vue__WEBPACK_IMPORTED_MODULE_0__.withCtx)(() => [
                  (0,vue__WEBPACK_IMPORTED_MODULE_0__.createTextVNode)(" " + (0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)($options.t('intravox', 'Send')), 1 /* TEXT */)
                ]),
                _: 1 /* STABLE */
              }, 8 /* PROPS */, ["disabled", "onClick"])
            ]))
          : (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)("v-if", true)
      ])
    ]),
    (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)(" Comment list "),
    ($data.loading)
      ? ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)("div", _hoisted_10, [
          (0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_NcLoadingIcon, { size: 32 })
        ]))
      : ($data.comments.length === 0)
        ? ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)("div", _hoisted_11, [
            (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("p", null, (0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)($options.t('intravox', 'No comments yet. Be the first to comment!')), 1 /* TEXT */)
          ]))
        : ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)("div", _hoisted_12, [
            ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(true), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)(vue__WEBPACK_IMPORTED_MODULE_0__.Fragment, null, (0,vue__WEBPACK_IMPORTED_MODULE_0__.renderList)($options.sortedComments, (comment) => {
              return ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)("div", {
                key: comment.id,
                class: "comment-wrapper"
              }, [
                (0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_CommentItem, {
                  comment: comment,
                  "current-user-id": $options.currentUserId,
                  "allow-reactions": $props.allowCommentReactions,
                  onReply: $event => ($options.startReply(comment)),
                  onUpdate: $options.handleCommentUpdate,
                  onDelete: $options.handleCommentDelete
                }, null, 8 /* PROPS */, ["comment", "current-user-id", "allow-reactions", "onReply", "onUpdate", "onDelete"]),
                (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)(" Inline reply input - shows below the comment being replied to "),
                ($data.replyingTo && $data.replyingTo.id === comment.id)
                  ? ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)("div", _hoisted_13, [
                      (0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_NcAvatar, {
                        user: $options.currentUserId,
                        size: 28
                      }, null, 8 /* PROPS */, ["user"]),
                      (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("div", _hoisted_14, [
                        (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("div", _hoisted_15, [
                          (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("span", null, (0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)($options.t('intravox', 'Replying to {name}', { name: $data.replyingTo.displayName })), 1 /* TEXT */),
                          (0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_NcButton, {
                            type: "tertiary",
                            onClick: $options.cancelReply
                          }, {
                            icon: (0,vue__WEBPACK_IMPORTED_MODULE_0__.withCtx)(() => [
                              (0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_Close, { size: 16 })
                            ]),
                            _: 1 /* STABLE */
                          }, 8 /* PROPS */, ["onClick"])
                        ]),
                        (0,vue__WEBPACK_IMPORTED_MODULE_0__.withDirectives)((0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("textarea", {
                          ref_for: true,
                          ref: "replyInput",
                          "onUpdate:modelValue": _cache[5] || (_cache[5] = $event => (($data.replyText) = $event)),
                          class: "comment-section__reply-textarea",
                          placeholder: $options.t('intravox', 'Write a reply...'),
                          "aria-label": $options.t('intravox', 'Write a reply'),
                          rows: "2",
                          onKeydown: [
                            _cache[6] || (_cache[6] = (0,vue__WEBPACK_IMPORTED_MODULE_0__.withKeys)((0,vue__WEBPACK_IMPORTED_MODULE_0__.withModifiers)((...args) => ($options.submitReply && $options.submitReply(...args)), ["ctrl"]), ["enter"])),
                            _cache[7] || (_cache[7] = (0,vue__WEBPACK_IMPORTED_MODULE_0__.withKeys)((...args) => ($options.cancelReply && $options.cancelReply(...args)), ["escape"]))
                          ]
                        }, null, 40 /* PROPS, NEED_HYDRATION */, _hoisted_16), [
                          [vue__WEBPACK_IMPORTED_MODULE_0__.vModelText, $data.replyText]
                        ]),
                        (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("div", _hoisted_17, [
                          (0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_NcButton, {
                            type: "tertiary",
                            onClick: $options.cancelReply
                          }, {
                            default: (0,vue__WEBPACK_IMPORTED_MODULE_0__.withCtx)(() => [
                              (0,vue__WEBPACK_IMPORTED_MODULE_0__.createTextVNode)((0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)($options.t('intravox', 'Cancel')), 1 /* TEXT */)
                            ]),
                            _: 1 /* STABLE */
                          }, 8 /* PROPS */, ["onClick"]),
                          (0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_NcButton, {
                            type: "primary",
                            disabled: !$data.replyText.trim() || $data.submitting,
                            onClick: $options.submitReply
                          }, {
                            default: (0,vue__WEBPACK_IMPORTED_MODULE_0__.withCtx)(() => [
                              (0,vue__WEBPACK_IMPORTED_MODULE_0__.createTextVNode)((0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)($options.t('intravox', 'Reply')), 1 /* TEXT */)
                            ]),
                            _: 1 /* STABLE */
                          }, 8 /* PROPS */, ["disabled", "onClick"])
                        ])
                      ])
                    ]))
                  : (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)("v-if", true)
              ]))
            }), 128 /* KEYED_FRAGMENT */))
          ]))
  ]))
}

/***/ },

/***/ "./node_modules/vue-loader/dist/templateLoader.js??ruleSet[1].rules[2]!./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/reactions/ReactionBar.vue?vue&type=template&id=0ed66f85&scoped=true"
/*!**************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/vue-loader/dist/templateLoader.js??ruleSet[1].rules[2]!./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/reactions/ReactionBar.vue?vue&type=template&id=0ed66f85&scoped=true ***!
  \**************************************************************************************************************************************************************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   render: () => (/* binding */ render)
/* harmony export */ });
/* harmony import */ var vue__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! vue */ "./node_modules/vue/dist/vue.runtime.esm-bundler.js");


const _hoisted_1 = { class: "reaction-bar" }
const _hoisted_2 = { class: "reaction-bar__reactions" }
const _hoisted_3 = ["title", "onClick"]
const _hoisted_4 = { class: "reaction-bar__emoji" }
const _hoisted_5 = { class: "reaction-bar__count" }

function render(_ctx, _cache, $props, $setup, $data, $options) {
  const _component_EmoticonHappyOutline = (0,vue__WEBPACK_IMPORTED_MODULE_0__.resolveComponent)("EmoticonHappyOutline")
  const _component_NcButton = (0,vue__WEBPACK_IMPORTED_MODULE_0__.resolveComponent)("NcButton")
  const _component_ReactionPicker = (0,vue__WEBPACK_IMPORTED_MODULE_0__.resolveComponent)("ReactionPicker")

  return ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)("div", _hoisted_1, [
    (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)(" Existing reactions "),
    (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("div", _hoisted_2, [
      ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(true), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)(vue__WEBPACK_IMPORTED_MODULE_0__.Fragment, null, (0,vue__WEBPACK_IMPORTED_MODULE_0__.renderList)($props.reactions, (count, emoji) => {
        return ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)("button", {
          key: emoji,
          class: (0,vue__WEBPACK_IMPORTED_MODULE_0__.normalizeClass)(["reaction-bar__reaction", { 'reaction-bar__reaction--active': $props.userReactions.includes(emoji) }]),
          title: $options.getReactionTitle(emoji, count),
          onClick: $event => ($options.toggleReaction(emoji))
        }, [
          (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("span", _hoisted_4, (0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)(emoji), 1 /* TEXT */),
          (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("span", _hoisted_5, (0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)(count), 1 /* TEXT */)
        ], 10 /* CLASS, PROPS */, _hoisted_3))
      }), 128 /* KEYED_FRAGMENT */))
    ]),
    (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)(" Add reaction button "),
    (0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_ReactionPicker, {
      visible: $data.showPicker,
      "onUpdate:visible": _cache[0] || (_cache[0] = $event => (($data.showPicker) = $event)),
      "selected-emojis": $props.userReactions,
      onSelect: $options.addReaction
    }, {
      trigger: (0,vue__WEBPACK_IMPORTED_MODULE_0__.withCtx)(() => [
        (0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_NcButton, {
          type: "tertiary",
          class: "reaction-bar__add",
          "aria-label": $options.t('intravox', 'Add reaction')
        }, {
          icon: (0,vue__WEBPACK_IMPORTED_MODULE_0__.withCtx)(() => [
            (0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_EmoticonHappyOutline, { size: 20 })
          ]),
          default: (0,vue__WEBPACK_IMPORTED_MODULE_0__.withCtx)(() => [
            (Object.keys($props.reactions).length === 0)
              ? ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)(vue__WEBPACK_IMPORTED_MODULE_0__.Fragment, { key: 0 }, [
                  (0,vue__WEBPACK_IMPORTED_MODULE_0__.createTextVNode)((0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)($options.t('intravox', 'React')), 1 /* TEXT */)
                ], 64 /* STABLE_FRAGMENT */))
              : (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)("v-if", true)
          ]),
          _: 1 /* STABLE */
        }, 8 /* PROPS */, ["aria-label"])
      ]),
      _: 1 /* STABLE */
    }, 8 /* PROPS */, ["visible", "selected-emojis", "onSelect"])
  ]))
}

/***/ },

/***/ "./node_modules/vue-loader/dist/templateLoader.js??ruleSet[1].rules[2]!./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/reactions/ReactionPicker.vue?vue&type=template&id=11a9eb8c&scoped=true"
/*!*****************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/vue-loader/dist/templateLoader.js??ruleSet[1].rules[2]!./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/reactions/ReactionPicker.vue?vue&type=template&id=11a9eb8c&scoped=true ***!
  \*****************************************************************************************************************************************************************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   render: () => (/* binding */ render)
/* harmony export */ });
/* harmony import */ var vue__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! vue */ "./node_modules/vue/dist/vue.runtime.esm-bundler.js");


const _hoisted_1 = { class: "reaction-picker" }
const _hoisted_2 = { class: "reaction-picker__grid" }
const _hoisted_3 = ["title", "onClick"]

function render(_ctx, _cache, $props, $setup, $data, $options) {
  const _component_Plus = (0,vue__WEBPACK_IMPORTED_MODULE_0__.resolveComponent)("Plus")
  const _component_NcButton = (0,vue__WEBPACK_IMPORTED_MODULE_0__.resolveComponent)("NcButton")
  const _component_NcPopover = (0,vue__WEBPACK_IMPORTED_MODULE_0__.resolveComponent)("NcPopover")

  return ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createBlock)(_component_NcPopover, {
    shown: $props.visible,
    "onUpdate:shown": _cache[0] || (_cache[0] = $event => (_ctx.$emit('update:visible', $event)))
  }, {
    trigger: (0,vue__WEBPACK_IMPORTED_MODULE_0__.withCtx)(() => [
      (0,vue__WEBPACK_IMPORTED_MODULE_0__.renderSlot)(_ctx.$slots, "trigger", {}, () => [
        (0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_NcButton, {
          type: "tertiary",
          "aria-label": $options.t('intravox', 'Add reaction')
        }, {
          icon: (0,vue__WEBPACK_IMPORTED_MODULE_0__.withCtx)(() => [
            (0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_Plus, { size: 20 })
          ]),
          _: 1 /* STABLE */
        }, 8 /* PROPS */, ["aria-label"])
      ], true)
    ]),
    default: (0,vue__WEBPACK_IMPORTED_MODULE_0__.withCtx)(() => [
      (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("div", _hoisted_1, [
        (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("div", _hoisted_2, [
          ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(true), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)(vue__WEBPACK_IMPORTED_MODULE_0__.Fragment, null, (0,vue__WEBPACK_IMPORTED_MODULE_0__.renderList)($data.commonEmojis, (emoji) => {
            return ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)("button", {
              key: emoji,
              class: (0,vue__WEBPACK_IMPORTED_MODULE_0__.normalizeClass)(["reaction-picker__emoji", { 'reaction-picker__emoji--selected': $props.selectedEmojis.includes(emoji) }]),
              title: emoji,
              onClick: $event => ($options.selectEmoji(emoji))
            }, (0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)(emoji), 11 /* TEXT, CLASS, PROPS */, _hoisted_3))
          }), 128 /* KEYED_FRAGMENT */))
        ])
      ])
    ]),
    _: 3 /* FORWARDED */
  }, 8 /* PROPS */, ["shown"]))
}

/***/ },

/***/ "./src/main.js"
/*!*********************!*\
  !*** ./src/main.js ***!
  \*********************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony import */ var vue__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! vue */ "./node_modules/vue/dist/vue.runtime.esm-bundler.js");
/* harmony import */ var _nextcloud_l10n__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @nextcloud/l10n */ "./node_modules/@nextcloud/l10n/dist/index.mjs");
/* harmony import */ var _App_vue__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./App.vue */ "./src/App.vue");
/* harmony import */ var _components_PublicPageView_vue__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ./components/PublicPageView.vue */ "./src/components/PublicPageView.vue");





// Get the root element
const rootElement = document.getElementById('app-intravox');

// Check if this is a public page view (token-based sharing of internal pages)
// Can be indicated by:
// 1. data-is-public="true" attribute on root element (set by PHP)
// 2. URL path /s/{token} (share access route)
// 3. ?share= query parameter (legacy, may not work due to NC routing)
const urlParams = new URLSearchParams(window.location.search);
const pathMatch = window.location.pathname.match(/\/apps\/intravox\/s\/([a-zA-Z0-9]+)/);
const shareToken = rootElement?.dataset?.token || (pathMatch ? pathMatch[1] : null) || urlParams.get('share') || '';
const isPublic = rootElement?.dataset?.isPublic === 'true' || (shareToken && !document.querySelector('#user-menu'));

let app;

if (isPublic && shareToken) {
	// Public page mode - render simplified view (token-based sharing)
	// Priority: ?page= query param (survives chat apps/email) > #hash (legacy)
	const pageFromQuery = urlParams.get('page') || '';
	const hash = window.location.hash;
	const pageFromHash = hash.startsWith('#page-') ? hash.substring(1) : (hash.length > 1 ? hash.substring(1) : '');
	const pageUniqueId = pageFromQuery || pageFromHash;

	app = (0,vue__WEBPACK_IMPORTED_MODULE_0__.createApp)(_components_PublicPageView_vue__WEBPACK_IMPORTED_MODULE_3__["default"], {
		token: shareToken,
		pageUniqueId,
	});
} else {
	// Normal authenticated mode
	app = (0,vue__WEBPACK_IMPORTED_MODULE_0__.createApp)(_App_vue__WEBPACK_IMPORTED_MODULE_2__["default"]);
}

// Make translation functions globally available
app.config.globalProperties.$t = (text, vars = {}) => (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_1__.translate)('intravox', text, vars);
app.config.globalProperties.$n = (singular, plural, count, vars = {}) => (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_1__.translatePlural)('intravox', singular, plural, count, vars);

app.mount('#app-intravox');


/***/ },

/***/ "./src/metavox-integration.js"
/*!************************************!*\
  !*** ./src/metavox-integration.js ***!
  \************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   checkMetaVoxAvailability: () => (/* binding */ checkMetaVoxAvailability),
/* harmony export */   initMetaVoxIntegration: () => (/* binding */ initMetaVoxIntegration)
/* harmony export */ });
/**
 * IntraVox MetaVox Integration Plugin
 * Sets up communication between IntraVox and MetaVox sidebar tab
 *
 * Supports two MetaVox registration paths:
 * - NC33+: MetaVox registers via window._nc_files_scope.v4_0.filesSidebarTabs (custom element)
 * - NC31-32: MetaVox registers via OCA.Files.Sidebar.registerTab (legacy mount/update API)
 *
 * Note: MetaVox scripts are loaded by the PHP backend via Util::addScript()
 * This file handles the sidebar mock and event communication for MetaVox integration.
 *
 * Versions are handled directly by IntraVox using the internal versions API,
 * which leverages Nextcloud's GroupFolders versioning backend.
 */

// Get Nextcloud webroot
function getWebRoot() {
    if (typeof OC !== 'undefined' && OC.webroot !== undefined) {
        return OC.webroot;
    }
    return '';
}

// Check if MetaVox is available (for API checks)
async function checkMetaVoxAvailability() {
    try {
        const url = getWebRoot() + '/apps/intravox/api/metavox/status';
        const response = await fetch(url);
        const data = await response.json();
        return data.installed === true;
    } catch (error) {
        return false;
    }
}

// Create a mock Files Sidebar API for MetaVox to register with (NC31-32 legacy path)
// Only used on IntraVox pages where the real Files sidebar doesn't exist
function createFilesSidebarMock() {
    if (!window.OCA) {
        window.OCA = {};
    }
    if (!window.OCA.Files) {
        window.OCA.Files = {};
    }

    // If real sidebar exists (Files app), find MetaVox tab from there
    if (window.OCA.Files.Sidebar && window.OCA.Files.Sidebar._tabs) {
        const metavoxTab = window.OCA.Files.Sidebar._tabs.find(
            tab => tab.id === 'metavox-metadata' || tab.id === 'metavox'
        );
        if (metavoxTab) {
            window._metaVoxTab = metavoxTab;
        }
        return; // Don't create mock, use existing sidebar
    }

    // Create mock sidebar only if it doesn't exist (IntraVox pages)
    if (!window.OCA.Files.Sidebar) {
        // Mock Tab class that MetaVox uses to create tabs
        class MockTab {
            constructor(options) {
                this.id = options.id;
                this.name = options.name;
                this.iconSvg = options.iconSvg;
                this.mount = options.mount;
                this.update = options.update;
                this.destroy = options.destroy;
                this.enabled = options.enabled;
            }
        }

        window.OCA.Files.Sidebar = {
            _tabs: [],
            Tab: MockTab,
            registerTab(tab) {
                this._tabs.push(tab);
                // Store MetaVox tab for IntraVox to use
                if (tab.id === 'metavox-metadata' || tab.id === 'metavox') {
                    window._metaVoxTab = tab;
                    window._metaVoxTabType = 'legacy';
                }
            },
            open(path) {},
            close() {},
            setActiveTab(tabId) {}
        };
    }
}

/**
 * Check if MetaVox registered via NC33+ scoped globals API.
 * On NC33, MetaVox writes to window._nc_files_scope.v4_0.filesSidebarTabs
 * instead of using OCA.Files.Sidebar.registerTab. We need to detect this
 * and set up _metaVoxTab accordingly.
 */
function checkNC33ScopedGlobals() {
    const scope = window._nc_files_scope?.v4_0;
    if (!scope?.filesSidebarTabs) {
        return false;
    }

    const metavoxEntry = scope.filesSidebarTabs.get('metavox');
    if (!metavoxEntry) {
        return false;
    }

    // Store a reference so we know to use the NC33 custom element path
    window._metaVoxTab = metavoxEntry;
    window._metaVoxTabType = 'nc33';
    return true;
}

// Get groupfolder name from groupfolder ID
async function getGroupfolderName(groupfolderId) {
    try {
        const url = getWebRoot() + '/apps/metavox/api/groupfolders';
        const response = await fetch(url, {
            method: 'GET',
            headers: {
                'Content-Type': 'application/json',
                'requesttoken': OC.requestToken
            }
        });

        if (response.ok) {
            const groupfolders = await response.json();
            const gf = groupfolders.find(g => g.id === parseInt(groupfolderId));
            if (gf) {
                return gf.mount_point;
            }
        }
    } catch (error) {
        // Silently fallback
    }
    return 'IntraVox';
}

/**
 * Build a fileInfo object from IntraVox page metadata.
 * This object mimics what the Nextcloud Files app provides to sidebar tabs.
 */
async function buildFileInfo(pageId, pageName, metadata) {
    const fullPath = metadata?.path || `/IntraVox/${pageName}`;
    const groupfolderMatch = fullPath.match(/\/__groupfolders\/(\d+)/);
    const groupfolderId = groupfolderMatch ? groupfolderMatch[1] : null;

    const groupfolderName = groupfolderId ? await getGroupfolderName(groupfolderId) : 'IntraVox';

    // MetaVox expects path in format /GroupfolderName/... not /__groupfolders/X/...
    let path = fullPath;
    if (fullPath.startsWith('/__groupfolders/')) {
        const pathAfterGroupfolder = fullPath.replace(/\/__groupfolders\/\d+\//, '');
        path = `/${groupfolderName}/${pathAfterGroupfolder}`;
    }

    const rawPermissions = metadata?.permissions?.raw ?? 1;

    return {
        id: metadata?.fileId || pageId,
        name: pageName,
        path: path,
        mimetype: 'application/json',
        size: metadata?.size || 0,
        mtime: metadata?.modified || Date.now(),
        permissions: rawPermissions,
        shareTypes: [],
        mountType: 'group',
        type: 'file',
        isMountRoot: false,
        mountPoint: groupfolderName
    };
}

/**
 * Mount MetaVox using the legacy API (NC31-32).
 * Calls the tab's mount() function directly with a fileInfo object.
 */
async function mountLegacy(container, fileInfo) {
    if (typeof window._metaVoxTab.mount === 'function') {
        container.innerHTML = '';
        await window._metaVoxTab.mount(container, fileInfo, {
            rootPath: '/IntraVox',
            isActive: true
        });
    }
}

/**
 * Mount MetaVox using the NC33+ custom element API.
 * Creates a <metavox-sidebar-tab> element and sets its properties,
 * mimicking how NC33's sidebar renders registered tabs.
 */
async function mountNC33(container, fileInfo) {
    const tabDef = window._metaVoxTab;

    // Ensure the custom element is initialized (calls onInit which defines the element)
    if (tabDef.onInit && !customElements.get(tabDef.tagName || 'metavox-sidebar-tab')) {
        await tabDef.onInit();
    }

    const tagName = tabDef.tagName || 'metavox-sidebar-tab';

    container.innerHTML = '';
    const el = document.createElement(tagName);

    // NC33 MetaVox expects a Node object with specific properties
    // Create a mock Node that matches what @nextcloud/files provides
    const mockNode = {
        fileid: fileInfo.id,
        basename: fileInfo.name,
        path: fileInfo.path,
        mime: fileInfo.mimetype,
        size: fileInfo.size,
        mtime: fileInfo.mtime,
        permissions: fileInfo.permissions,
        type: fileInfo.type,
        attributes: {
            mountType: fileInfo.mountType,
            mountPoint: fileInfo.mountPoint,
            'is-mount-root': false,
        }
    };

    // Set properties on the custom element (NC33 uses property setters, not attributes)
    el.node = mockNode;
    el.active = true;

    container.appendChild(el);
}

// Setup event listeners for IntraVox-MetaVox communication
function setupMetaVoxEventListeners() {
    window.addEventListener('intravox:metavox:update', async (event) => {
        if (!event.detail.container) {
            return;
        }

        // If _metaVoxTab isn't set yet, try to find it from NC33 scoped globals
        if (!window._metaVoxTab) {
            checkNC33ScopedGlobals();
        }

        if (!window._metaVoxTab) {
            return;
        }

        const { pageId, pageName, container, metadata } = event.detail;

        try {
            const fileInfo = await buildFileInfo(pageId, pageName, metadata);

            if (window._metaVoxTabType === 'nc33') {
                await mountNC33(container, fileInfo);
            } else {
                await mountLegacy(container, fileInfo);
            }
        } catch (error) {
            console.error('[IntraVox] Error mounting MetaVox:', error);
        }
    });
}

// Initialize MetaVox integration
// MetaVox scripts are loaded by PHP backend, we just need to setup the mock and listeners
function initMetaVoxIntegration() {
    // Create the sidebar mock so MetaVox can register its tab (NC31-32 legacy path)
    createFilesSidebarMock();

    // Also check if MetaVox already registered via NC33 scoped globals
    checkNC33ScopedGlobals();

    // If MetaVox hasn't registered yet (script loading order), poll for it
    if (!window._metaVoxTab) {
        let attempts = 0;
        const maxAttempts = 30; // 3 seconds max
        const pollInterval = setInterval(() => {
            attempts++;
            // Check both registration paths
            if (window._metaVoxTab || checkNC33ScopedGlobals() || attempts >= maxAttempts) {
                clearInterval(pollInterval);
            }
        }, 100);
    }

    // Setup event listeners for MetaVox communication
    setupMetaVoxEventListeners();
}

// Initialize when DOM is ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initMetaVoxIntegration);
} else {
    initMetaVoxIntegration();
}




/***/ },

/***/ "./src/services/CacheService.js"
/*!**************************************!*\
  !*** ./src/services/CacheService.js ***!
  \**************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/**
 * Enhanced cache service with localStorage persistence for IntraVox
 * Caches pages, navigation, and breadcrumbs to reduce API calls
 */
class CacheService {
  constructor() {
    this.cache = new Map();
    this.ttl = 5 * 60 * 1000; // 5 minutes default TTL
    this.persistentKeys = ['pages-list', 'navigation', 'footer'];
    this.storagePrefix = 'intravox_cache_';

    // Restore from localStorage on init
    this.restoreFromStorage();
  }

  /**
   * Restore cached items from localStorage
   */
  restoreFromStorage() {
    try {
      this.persistentKeys.forEach(key => {
        const stored = localStorage.getItem(this.storagePrefix + key);
        if (stored) {
          const { value, expiry } = JSON.parse(stored);
          if (Date.now() < expiry) {
            this.cache.set(key, { value, expiry });
          } else {
            localStorage.removeItem(this.storagePrefix + key);
          }
        }
      });
    } catch (e) {
      // localStorage not available or corrupt - silent fail
    }
  }

  /**
   * Get item from cache
   * @param {string} key - Cache key
   * @returns {any|null} Cached value or null if expired/not found
   */
  get(key) {
    const item = this.cache.get(key);

    if (!item) {
      return null;
    }

    // Check if expired
    if (Date.now() > item.expiry) {
      this.cache.delete(key);
      return null;
    }

    return item.value;
  }

  /**
   * Get age of cached item in milliseconds
   * @param {string} key - Cache key
   * @returns {number|null} Age in ms, or null if not cached
   */
  getAge(key) {
    const item = this.cache.get(key);
    if (!item) return null;

    // Calculate age: current time minus when it was set
    // expiry = setTime + ttl, so setTime = expiry - ttl
    const setTime = item.expiry - this.ttl;
    return Date.now() - setTime;
  }

  /**
   * Set item in cache
   * @param {string} key - Cache key
   * @param {any} value - Value to cache
   * @param {number} ttl - Time to live in milliseconds (optional)
   */
  set(key, value, ttl = this.ttl) {
    const item = {
      value,
      expiry: Date.now() + ttl
    };
    this.cache.set(key, item);

    // Persist important keys to localStorage with LRU eviction on quota
    // pressure: when the browser refuses our write we drop the oldest
    // persistent entry and retry once, rather than silently losing the
    // current set call.
    if (this.persistentKeys.includes(key)) {
      try {
        localStorage.setItem(this.storagePrefix + key, JSON.stringify(item));
      } catch (e) {
        if (e && e.name === 'QuotaExceededError') {
          this.evictOldestPersistent();
          try {
            localStorage.setItem(this.storagePrefix + key, JSON.stringify(item));
          } catch (e2) {
            // Quota still full — give up silently, in-memory cache still holds it
          }
        }
        // Other errors (localStorage disabled, etc) — silent fail
      }
    }
  }

  /**
   * Remove the persistent entry with the soonest expiry. Used by set()
   * when the browser rejects a write due to storage quota.
   */
  evictOldestPersistent() {
    let oldestKey = null
    let oldestExpiry = Infinity
    this.persistentKeys.forEach((k) => {
      try {
        const stored = localStorage.getItem(this.storagePrefix + k);
        if (!stored) return;
        const parsed = JSON.parse(stored);
        if (parsed && parsed.expiry < oldestExpiry) {
          oldestExpiry = parsed.expiry;
          oldestKey = k;
        }
      } catch (e) {
        // Skip corrupt entries
      }
    });
    if (oldestKey) {
      try {
        localStorage.removeItem(this.storagePrefix + oldestKey);
      } catch (e) {
        // localStorage gone — nothing left to do
      }
    }
  }

  /**
   * Check if key exists and is not expired
   * @param {string} key - Cache key
   * @returns {boolean}
   */
  has(key) {
    return this.get(key) !== null;
  }

  /**
   * Remove item from cache
   * @param {string} key - Cache key
   */
  delete(key) {
    this.cache.delete(key);
    if (this.persistentKeys.includes(key)) {
      try {
        localStorage.removeItem(this.storagePrefix + key);
      } catch (e) {
        // localStorage not available
      }
    }
  }

  /**
   * Clear entire cache
   */
  clear() {
    this.cache.clear();
    try {
      this.persistentKeys.forEach(key => {
        localStorage.removeItem(this.storagePrefix + key);
      });
    } catch (e) {
      // localStorage not available
    }
  }

  /**
   * Invalidate cache items by prefix
   * @param {string} prefix - Key prefix to invalidate
   */
  invalidateByPrefix(prefix) {
    const keys = Array.from(this.cache.keys());
    keys.forEach(key => {
      if (key.startsWith(prefix)) {
        this.delete(key);
      }
    });
  }

  /**
   * Get cache statistics
   * @returns {object} Cache stats
   */
  getStats() {
    return {
      size: this.cache.size,
      keys: Array.from(this.cache.keys())
    };
  }
}

// Export singleton instance
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (new CacheService());


/***/ },

/***/ "./src/services/CommentService.js"
/*!****************************************!*\
  !*** ./src/services/CommentService.js ***!
  \****************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   addCommentReaction: () => (/* binding */ addCommentReaction),
/* harmony export */   addPageReaction: () => (/* binding */ addPageReaction),
/* harmony export */   createComment: () => (/* binding */ createComment),
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__),
/* harmony export */   deleteComment: () => (/* binding */ deleteComment),
/* harmony export */   getCommentReactions: () => (/* binding */ getCommentReactions),
/* harmony export */   getComments: () => (/* binding */ getComments),
/* harmony export */   getEngagementSettings: () => (/* binding */ getEngagementSettings),
/* harmony export */   getPageReactions: () => (/* binding */ getPageReactions),
/* harmony export */   removeCommentReaction: () => (/* binding */ removeCommentReaction),
/* harmony export */   removePageReaction: () => (/* binding */ removePageReaction),
/* harmony export */   toggleCommentReaction: () => (/* binding */ toggleCommentReaction),
/* harmony export */   togglePageReaction: () => (/* binding */ togglePageReaction),
/* harmony export */   updateComment: () => (/* binding */ updateComment)
/* harmony export */ });
/* harmony import */ var _nextcloud_axios__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @nextcloud/axios */ "./node_modules/@nextcloud/axios/dist/index.js");
/* harmony import */ var _nextcloud_router__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @nextcloud/router */ "./node_modules/@nextcloud/router/dist/index.mjs");
/**
 * Comment Service - API client for IntraVox comments and reactions
 *
 * Uses Nextcloud's native Comments API via REST wrapper
 */




const BASE_URL = '/apps/intravox/api'

/**
 * Get comments for a page
 *
 * @param {string} pageId - The uniqueId of the page
 * @param {number} limit - Maximum number of comments (default 50)
 * @param {number} offset - Pagination offset (default 0)
 * @returns {Promise<{comments: Array, total: number}>}
 */
async function getComments(pageId, limit = 50, offset = 0) {
	const url = (0,_nextcloud_router__WEBPACK_IMPORTED_MODULE_1__.generateUrl)(`${BASE_URL}/pages/{pageId}/comments`, { pageId })
	const response = await _nextcloud_axios__WEBPACK_IMPORTED_MODULE_0__["default"].get(url, { params: { limit, offset } })
	return response.data
}

/**
 * Create a new comment
 *
 * @param {string} pageId - The uniqueId of the page
 * @param {string} message - The comment message
 * @param {string|null} parentId - Parent comment ID for replies (optional)
 * @returns {Promise<Object>} The created comment
 */
async function createComment(pageId, message, parentId = null) {
	const url = (0,_nextcloud_router__WEBPACK_IMPORTED_MODULE_1__.generateUrl)(`${BASE_URL}/pages/{pageId}/comments`, { pageId })
	const response = await _nextcloud_axios__WEBPACK_IMPORTED_MODULE_0__["default"].post(url, { message, parentId })
	return response.data
}

/**
 * Update an existing comment
 *
 * @param {string} commentId - The comment ID
 * @param {string} message - The new message
 * @returns {Promise<Object>} The updated comment
 */
async function updateComment(commentId, message) {
	const url = (0,_nextcloud_router__WEBPACK_IMPORTED_MODULE_1__.generateUrl)(`${BASE_URL}/comments/{commentId}`, { commentId })
	const response = await _nextcloud_axios__WEBPACK_IMPORTED_MODULE_0__["default"].put(url, { message })
	return response.data
}

/**
 * Delete a comment
 *
 * @param {string} commentId - The comment ID
 * @returns {Promise<void>}
 */
async function deleteComment(commentId) {
	const url = (0,_nextcloud_router__WEBPACK_IMPORTED_MODULE_1__.generateUrl)(`${BASE_URL}/comments/{commentId}`, { commentId })
	await _nextcloud_axios__WEBPACK_IMPORTED_MODULE_0__["default"].delete(url)
}

/**
 * Get reactions for a page
 *
 * @param {string} pageId - The uniqueId of the page
 * @returns {Promise<{reactions: Object, userReactions: Array}>}
 */
async function getPageReactions(pageId) {
	const url = (0,_nextcloud_router__WEBPACK_IMPORTED_MODULE_1__.generateUrl)(`${BASE_URL}/pages/{pageId}/reactions`, { pageId })
	const response = await _nextcloud_axios__WEBPACK_IMPORTED_MODULE_0__["default"].get(url)
	return response.data
}

/**
 * Add a reaction to a page
 *
 * @param {string} pageId - The uniqueId of the page
 * @param {string} emoji - The emoji reaction
 * @returns {Promise<{reactions: Object, userReactions: Array}>}
 */
async function addPageReaction(pageId, emoji) {
	const url = (0,_nextcloud_router__WEBPACK_IMPORTED_MODULE_1__.generateUrl)(`${BASE_URL}/pages/{pageId}/reactions/{emoji}`, { pageId, emoji })
	const response = await _nextcloud_axios__WEBPACK_IMPORTED_MODULE_0__["default"].post(url)
	return response.data
}

/**
 * Remove a reaction from a page
 *
 * @param {string} pageId - The uniqueId of the page
 * @param {string} emoji - The emoji reaction
 * @returns {Promise<{reactions: Object, userReactions: Array}>}
 */
async function removePageReaction(pageId, emoji) {
	const url = (0,_nextcloud_router__WEBPACK_IMPORTED_MODULE_1__.generateUrl)(`${BASE_URL}/pages/{pageId}/reactions/{emoji}`, { pageId, emoji })
	const response = await _nextcloud_axios__WEBPACK_IMPORTED_MODULE_0__["default"].delete(url)
	return response.data
}

/**
 * Toggle a reaction on a page (add if not exists, remove if exists)
 *
 * @param {string} pageId - The uniqueId of the page
 * @param {string} emoji - The emoji reaction
 * @param {Array} userReactions - Current user reactions
 * @returns {Promise<{reactions: Object, userReactions: Array}>}
 */
async function togglePageReaction(pageId, emoji, userReactions = []) {
	if (userReactions.includes(emoji)) {
		return removePageReaction(pageId, emoji)
	}
	return addPageReaction(pageId, emoji)
}

/**
 * Get reactions for a comment
 *
 * @param {string} commentId - The comment ID
 * @returns {Promise<{reactions: Object, userReactions: Array}>}
 */
async function getCommentReactions(commentId) {
	const url = (0,_nextcloud_router__WEBPACK_IMPORTED_MODULE_1__.generateUrl)(`${BASE_URL}/comments/{commentId}/reactions`, { commentId })
	const response = await _nextcloud_axios__WEBPACK_IMPORTED_MODULE_0__["default"].get(url)
	return response.data
}

/**
 * Add a reaction to a comment
 *
 * @param {string} commentId - The comment ID
 * @param {string} emoji - The emoji reaction
 * @returns {Promise<{reactions: Object, userReactions: Array}>}
 */
async function addCommentReaction(commentId, emoji) {
	const url = (0,_nextcloud_router__WEBPACK_IMPORTED_MODULE_1__.generateUrl)(`${BASE_URL}/comments/{commentId}/reactions/{emoji}`, { commentId, emoji })
	const response = await _nextcloud_axios__WEBPACK_IMPORTED_MODULE_0__["default"].post(url)
	return response.data
}

/**
 * Remove a reaction from a comment
 *
 * @param {string} commentId - The comment ID
 * @param {string} emoji - The emoji reaction
 * @returns {Promise<{reactions: Object, userReactions: Array}>}
 */
async function removeCommentReaction(commentId, emoji) {
	const url = (0,_nextcloud_router__WEBPACK_IMPORTED_MODULE_1__.generateUrl)(`${BASE_URL}/comments/{commentId}/reactions/{emoji}`, { commentId, emoji })
	const response = await _nextcloud_axios__WEBPACK_IMPORTED_MODULE_0__["default"].delete(url)
	return response.data
}

/**
 * Toggle a reaction on a comment
 *
 * @param {string} commentId - The comment ID
 * @param {string} emoji - The emoji reaction
 * @param {Array} userReactions - Current user reactions
 * @returns {Promise<{reactions: Object, userReactions: Array}>}
 */
async function toggleCommentReaction(commentId, emoji, userReactions = []) {
	if (userReactions.includes(emoji)) {
		return removeCommentReaction(commentId, emoji)
	}
	return addCommentReaction(commentId, emoji)
}

/**
 * Get engagement settings (reactions & comments configuration)
 *
 * @returns {Promise<{allowPageReactions: boolean, allowComments: boolean, allowCommentReactions: boolean, singleReactionPerUser: boolean}>}
 */
async function getEngagementSettings() {
	const url = (0,_nextcloud_router__WEBPACK_IMPORTED_MODULE_1__.generateUrl)(`${BASE_URL}/settings/engagement`)
	const response = await _nextcloud_axios__WEBPACK_IMPORTED_MODULE_0__["default"].get(url)
	return response.data
}

/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = ({
	getComments,
	createComment,
	updateComment,
	deleteComment,
	getPageReactions,
	addPageReaction,
	removePageReaction,
	togglePageReaction,
	getCommentReactions,
	addCommentReaction,
	removeCommentReaction,
	toggleCommentReaction,
	getEngagementSettings,
});


/***/ },

/***/ "./src/utils/markdownSerializer.js"
/*!*****************************************!*\
  !*** ./src/utils/markdownSerializer.js ***!
  \*****************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   cleanMarkdown: () => (/* binding */ cleanMarkdown),
/* harmony export */   htmlToMarkdown: () => (/* binding */ htmlToMarkdown),
/* harmony export */   markdownToHtml: () => (/* binding */ markdownToHtml)
/* harmony export */ });
/* harmony import */ var marked__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! marked */ "./node_modules/marked/lib/marked.esm.js");
/* harmony import */ var dompurify__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! dompurify */ "./node_modules/dompurify/dist/purify.es.mjs");
/**
 * Markdown Serializer for TipTap
 * Converts between TipTap JSON/HTML and Markdown
 */




// Configure marked for GFM (GitHub Flavored Markdown) with tables
// Use synchronous parsing to avoid Promise issues
marked__WEBPACK_IMPORTED_MODULE_0__.marked.use({
  gfm: true,
  breaks: true,
  async: false  // Force synchronous parsing
});

/**
 * Simple LRU cache for markdown parsing results
 * Avoids re-parsing the same content on every render
 */
const markdownCache = new Map();
const MAX_CACHE_SIZE = 100;

/**
 * Escape HTML special characters to prevent XSS and raw markdown display
 */
function escapeHtml(text) {
  const map = {
    '&': '&amp;',
    '<': '&lt;',
    '>': '&gt;',
    '"': '&quot;',
    "'": '&#039;'
  };
  return text.replace(/[&<>"']/g, char => map[char]);
}

/**
 * Normalize markdown to ensure tables parse correctly
 * Tables need blank lines before them, but NOT between rows
 */
function normalizeMarkdownTables(markdown) {
  const lines = markdown.split('\n');
  const result = [];
  let inTable = false;

  for (let i = 0; i < lines.length; i++) {
    const line = lines[i];
    const isTableRow = line.trim().startsWith('|') && line.trim().endsWith('|');
    const prevLine = result.length > 0 ? result[result.length - 1] : '';
    const prevIsTableRow = prevLine.trim().startsWith('|') && prevLine.trim().endsWith('|');
    const prevIsEmpty = prevLine.trim() === '';

    if (isTableRow && !inTable) {
      // Starting a new table - ensure blank line before it (unless at start or already blank)
      if (result.length > 0 && !prevIsEmpty) {
        result.push('');
      }
      inTable = true;
    } else if (!isTableRow && inTable) {
      // Leaving a table
      inTable = false;
    }

    result.push(line);
  }

  return result.join('\n');
}

/**
 * Preserve empty lines in markdown by converting them to zero-width space paragraphs
 * This prevents marked from collapsing multiple blank lines into one
 *
 * In Markdown, multiple blank lines are treated as a single paragraph break.
 * But users expect their empty lines to be preserved for visual spacing.
 *
 * Solution: Replace each "extra" empty line with a paragraph containing a zero-width space.
 * This creates actual <p> tags that render as empty visual lines.
 */
function preserveEmptyLines(markdown) {
  const lines = markdown.split('\n');
  const result = [];
  let consecutiveEmpty = 0;

  for (let i = 0; i < lines.length; i++) {
    const line = lines[i];
    const isEmpty = line.trim() === '';

    if (isEmpty) {
      consecutiveEmpty++;
      // First empty line is the normal paragraph separator (keep as is)
      // Additional empty lines need to become actual content to be preserved
      if (consecutiveEmpty > 1) {
        // Check if the next non-empty line starts a table.
        // Tables need a real blank line before them (\n\n), not a ZWS paragraph,
        // otherwise marked won't parse them as tables.
        let nextNonEmpty = '';
        for (let j = i + 1; j < lines.length; j++) {
          if (lines[j].trim() !== '') {
            nextNonEmpty = lines[j].trim();
            break;
          }
        }
        const nextIsTable = nextNonEmpty.startsWith('|') && nextNonEmpty.endsWith('|');
        if (nextIsTable) {
          result.push('');
        } else {
          result.push('\u200B');
        }
      } else {
        result.push(line);
      }
    } else {
      consecutiveEmpty = 0;
      result.push(line);
    }
  }

  return result.join('\n');
}

/**
 * Convert Markdown to HTML for TipTap
 * OPTIMIZED: Results are cached to avoid repeated parsing
 */
/**
 * Read-mode table post-processor. Runs after DOMPurify on the rendered HTML.
 *
 * - Strips TipTap's auto-generated `style="width: …px"` from <table> (TipTap
 *   writes that on every save based on summed colwidths) and rebuilds a
 *   minimal style from the user-set `data-table-width` / `data-table-align`
 *   attributes set by the custom Table extension.
 * - Collects column widths from cell `data-colwidth`/`colwidth` attributes
 *   AND from any existing <col style="width: Xpx">, then converts them to
 *   percentages so the table always fits its container — pixel widths can
 *   sum higher than the container in narrow page-rows and would otherwise
 *   overflow even with `table-layout: fixed`.
 * - Wraps every table in a `<div class="tableWrapper">` (same class TipTap
 *   uses in edit-mode) so wide tables get horizontal scroll inside the widget
 *   instead of pushing the page layout sideways.
 *
 * Idempotent: a tableWrapper already in place is reused.
 */
function hydrateTables(html) {
  if (!html || html.indexOf('<table') === -1) return html;
  const doc = new DOMParser().parseFromString(`<div>${html}</div>`, 'text/html');
  const root = doc.body.firstElementChild;
  if (!root) return html;

  for (const table of Array.from(root.querySelectorAll('table'))) {
    // 1. Reset <table> inline style; rebuild only from user-set attributes.
    const userWidth = table.getAttribute('data-table-width');
    const userAlign = table.getAttribute('data-table-align');
    const styles = [];
    if (userWidth) styles.push(`width: ${userWidth}`);
    if (userAlign === 'center') styles.push('margin-left: auto', 'margin-right: auto');
    else if (userAlign === 'right') styles.push('margin-left: auto', 'margin-right: 0');
    else if (userAlign === 'left') styles.push('margin-left: 0', 'margin-right: auto');
    if (styles.length > 0) table.setAttribute('style', styles.join('; '));
    else table.removeAttribute('style');

    // 2. Normalize colgroup. Read widths from BOTH cell-level data-colwidth and
    //    existing <col style="width:Xpx">, convert to percentages so the table
    //    always fits its container regardless of stored pixel widths.
    const existingColgroup = table.querySelector('colgroup');
    const existingCols = existingColgroup ? Array.from(existingColgroup.children) : [];
    const firstRow = table.querySelector('tr');
    if (firstRow) {
      const cells = Array.from(firstRow.children);
      const widths = cells.map((cell, i) => {
        // Modern data-colwidth or legacy colwidth on the cell
        const cellRaw = cell.getAttribute('data-colwidth') || cell.getAttribute('colwidth');
        if (cellRaw) {
          const first = String(cellRaw).split(',')[0].trim();
          const px = Number.parseInt(first, 10);
          if (Number.isFinite(px) && px > 0) return px;
        }
        // Fallback to existing <col style="width: Xpx">
        const col = existingCols[i];
        if (col) {
          const m = (col.getAttribute('style') || '').match(/width\s*:\s*(\d+(?:\.\d+)?)\s*px/);
          if (m) {
            const px = Number.parseFloat(m[1]);
            if (Number.isFinite(px) && px > 0) return px;
          }
        }
        return null;
      });

      if (widths.some((w) => w !== null)) {
        // Convert to percentages relative to total share. Empty cols get a
        // proportional fallback share so a 3-col table with 1 empty col still
        // sums to 100%.
        const knownSum = widths.reduce((s, w) => s + (w ?? 0), 0);
        const knownCount = widths.filter((w) => w !== null).length;
        const emptyCount = widths.length - knownCount;
        const totalShare = knownSum + emptyCount * (knownSum / Math.max(1, knownCount));
        const colgroup = doc.createElement('colgroup');
        for (const w of widths) {
          const col = doc.createElement('col');
          if (w !== null) {
            col.setAttribute('style', `width: ${(w / totalShare * 100).toFixed(2)}%`);
          } else if (knownCount > 0) {
            col.setAttribute('style', `width: ${(knownSum / knownCount / totalShare * 100).toFixed(2)}%`);
          }
          colgroup.appendChild(col);
        }
        if (existingColgroup) existingColgroup.replaceWith(colgroup);
        else table.insertBefore(colgroup, table.firstChild);
      } else if (existingColgroup) {
        // Empty <col>s without widths — drop the colgroup so CSS distributes evenly.
        const allEmpty = existingCols.every((c) => !c.getAttribute('style') && !c.getAttribute('width'));
        if (allEmpty) existingColgroup.remove();
      }
    }

    // 3. Wrap in scroll container (idempotent).
    const parent = table.parentElement;
    if (!parent || !parent.classList.contains('tableWrapper')) {
      const wrap = doc.createElement('div');
      wrap.className = 'tableWrapper';
      table.parentNode.insertBefore(wrap, table);
      wrap.appendChild(table);
    }
  }

  return root.innerHTML;
}

function markdownToHtml(markdown) {
  if (!markdown) return '';

  // Check cache first
  if (markdownCache.has(markdown)) {
    return markdownCache.get(markdown);
  }

  try {
    // Normalize tables: ensure blank line before table, but not between rows
    const normalizedMarkdown = normalizeMarkdownTables(markdown);

    // Preserve empty lines by converting them to special markers before parsing
    // Markdown collapses multiple blank lines into one, but we want to keep them
    // Strategy: Replace sequences of empty lines with placeholder paragraphs
    const preservedMarkdown = preserveEmptyLines(normalizedMarkdown);

    // Parse markdown (GFM configured globally above)
    let html = marked__WEBPACK_IMPORTED_MODULE_0__.marked.parse(preservedMarkdown);

    // Validate that marked returned HTML, not raw markdown
    // If the result looks like raw markdown (contains unprocessed emphasis markers),
    // escape it to prevent asterisks from showing up.
    // Skip this check if content is already HTML (starts with <) — marked passes
    // HTML blocks through unchanged, which is correct behavior, not a parse failure.
    if (typeof html !== 'string' || (html === preservedMarkdown && !html.trimStart().startsWith('<'))) {
      // Parse failed silently - escape the content to prevent raw markdown display
      const escaped = escapeHtml(markdown);
      return `<p>${escaped}</p>`;
    }

    // Convert alignment comment markers to CSS classes on the following element.
    // Format: <!-- align:center -->\n<p>text</p> → <p class="text-align-center">text</p>
    // Also handle legacy HTML block format: <p class="text-align-center">text</p>
    html = html.replace(/<!--\s*align:(center|right)\s*-->\s*<(p|h[1-6])>/g,
      (_, align, tag) => `<${tag} class="text-align-${align}">`);

    const sanitized = dompurify__WEBPACK_IMPORTED_MODULE_1__["default"].sanitize(html, {
      ALLOWED_TAGS: ['p', 'br', 'strong', 'em', 'u', 's', 'ul', 'ol', 'li', 'a', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'blockquote', 'code', 'pre', 'hr',
                     'table', 'thead', 'tbody', 'tr', 'th', 'td', 'colgroup', 'col',
                     'details', 'summary', 'details-content', 'div'],
      ALLOWED_ATTR: ['href', 'target', 'rel', 'class', 'open', 'style', 'colspan', 'rowspan',
                     'colwidth', 'data-colwidth', 'data-table-width', 'data-table-align']
    });

    // Post-process tables for read-mode (colgroup hydration + scroll wrapper).
    const result = hydrateTables(sanitized);

    // Store in cache with LRU eviction
    if (markdownCache.size >= MAX_CACHE_SIZE) {
      const firstKey = markdownCache.keys().next().value;
      markdownCache.delete(firstKey);
    }
    markdownCache.set(markdown, result);

    return result;
  } catch (err) {
    console.error('Markdown parsing error:', err);
    // On error, escape the content to prevent raw markdown/asterisks from showing
    const escaped = escapeHtml(markdown);
    return `<p>${escaped}</p>`;
  }
}

/**
 * Convert HTML to Markdown
 * Simplified conversion that handles the most common cases
 */
function htmlToMarkdown(html) {
  if (!html) return '';

  // Create a temporary element to parse HTML
  const temp = document.createElement('div');
  temp.innerHTML = html;

  return nodeToMarkdown(temp);
}

function nodeToMarkdown(node, listDepth = 0) {
  let markdown = '';

  for (const child of node.childNodes) {
    if (child.nodeType === Node.TEXT_NODE) {
      markdown += child.textContent;
    } else if (child.nodeType === Node.ELEMENT_NODE) {
      markdown += elementToMarkdown(child, listDepth);
    }
  }

  return markdown;
}

/**
 * Check if element has a non-default text alignment class
 * Returns 'center' or 'right', or null for default (left)
 */
function getAlignmentFromClass(element) {
  if (element.classList?.contains('text-align-center')) return 'center';
  if (element.classList?.contains('text-align-right')) return 'right';
  return null;
}

function elementToMarkdown(element, listDepth = 0) {
  const tag = element.tagName.toLowerCase();

  // Check for TipTap NodeView data-type attributes first
  const dataType = element.getAttribute('data-type');
  if (dataType === 'details') {
    return convertTipTapDetails(element);
  }
  if (dataType === 'detailsContent') {
    // Content is handled by convertTipTapDetails
    return nodeToMarkdown(element, listDepth);
  }

  const content = nodeToMarkdown(element, listDepth);

  switch (tag) {
    case 'p': {
      // Empty paragraph (with or without <br>) should produce a blank line
      // content will be '\n' if it contains only <br>, or '' if truly empty
      // Zero-width space (\u200B) is used as a placeholder for preserved empty lines
      const trimmedContent = content.replace(/\u200B/g, '').trim();
      if (trimmedContent === '' || trimmedContent === '\n') {
        return '\n\n';
      }
      const pAlign = getAlignmentFromClass(element);
      if (pAlign) {
        // Store aligned content as HTML block with innerHTML (not markdown).
        // We use innerHTML because marked doesn't process markdown syntax
        // inside HTML blocks — so the content must already be HTML.
        return `<p class="text-align-${pAlign}">${element.innerHTML}</p>\n\n`;
      }
      return content.replace(/\u200B/g, '') + '\n\n';
    }

    case 'br':
      return '\n';

    case 'strong':
    case 'b':
      // Use HTML tags when content contains HTML (e.g., <u>) to avoid
      // mixing markdown emphasis markers with HTML tags, which breaks parsing
      if (content.includes('<')) {
        return `<strong>${content}</strong>`;
      }
      return `**${content}**`;

    case 'em':
    case 'i':
      if (content.includes('<')) {
        return `<em>${content}</em>`;
      }
      return `*${content}*`;

    case 'u':
      // Markdown doesn't have native underline, use HTML
      return `<u>${content}</u>`;

    case 's':
    case 'del':
    case 'strike':
      return `~~${content}~~`;

    case 'h1': {
      const h1Align = getAlignmentFromClass(element);
      if (h1Align) return `<h1 class="text-align-${h1Align}">${element.innerHTML}</h1>\n\n`;
      return `# ${content}\n\n`;
    }

    case 'h2': {
      const h2Align = getAlignmentFromClass(element);
      if (h2Align) return `<h2 class="text-align-${h2Align}">${element.innerHTML}</h2>\n\n`;
      return `## ${content}\n\n`;
    }

    case 'h3': {
      const h3Align = getAlignmentFromClass(element);
      if (h3Align) return `<h3 class="text-align-${h3Align}">${element.innerHTML}</h3>\n\n`;
      return `### ${content}\n\n`;
    }

    case 'h4': {
      const h4Align = getAlignmentFromClass(element);
      if (h4Align) return `<h4 class="text-align-${h4Align}">${element.innerHTML}</h4>\n\n`;
      return `#### ${content}\n\n`;
    }

    case 'h5': {
      const h5Align = getAlignmentFromClass(element);
      if (h5Align) return `<h5 class="text-align-${h5Align}">${element.innerHTML}</h5>\n\n`;
      return `##### ${content}\n\n`;
    }

    case 'h6': {
      const h6Align = getAlignmentFromClass(element);
      if (h6Align) return `<h6 class="text-align-${h6Align}">${element.innerHTML}</h6>\n\n`;
      return `###### ${content}\n\n`;
    }

    case 'ul':
      return convertList(element, listDepth, false);

    case 'ol':
      return convertList(element, listDepth, true);

    case 'li':
      // List items are handled by convertList
      return content;

    case 'a':
      const href = element.getAttribute('href') || '';
      return `[${content}](${href})`;

    case 'blockquote':
      return content.split('\n').map(line => `> ${line}`).join('\n') + '\n\n';

    case 'code':
      return `\`${content}\``;

    case 'pre':
      // TipTap wraps code blocks as <pre><code>content</code></pre>
      // Get raw text content to avoid double-processing the nested <code> tag
      const codeElement = element.querySelector('code');
      const codeContent = codeElement ? codeElement.textContent : element.textContent;
      return `\`\`\`\n${codeContent}\n\`\`\`\n\n`;

    case 'hr':
      return '---\n\n';

    case 'table':
      return convertTable(element);

    case 'input':
      // Task list checkbox
      if (element.getAttribute('type') === 'checkbox') {
        return element.checked ? '[x] ' : '[ ] ';
      }
      return '';

    case 'button':
      // Skip buttons (e.g., TipTap details toggle button)
      return '';

    case 'details':
      // Preserve details as raw HTML in markdown (no native markdown syntax for this)
      return convertDetails(element);

    case 'summary':
      // Summary is handled by convertDetails
      return content;

    case 'details-content':
      // TipTap uses custom element for details content, convert it
      return content;

    default:
      return content;
  }
}

/**
 * Convert TipTap NodeView details structure to HTML
 * TipTap renders: <div data-type="details"> with <summary> and <div data-type="detailsContent">
 *
 * Content inside details is stored as HTML (not markdown) because:
 * 1. Markdown parsers don't parse content inside HTML tags
 * 2. This ensures tables, lists, etc. render correctly inside details
 */
function convertTipTapDetails(detailsElement) {
  // TipTap structure: div[data-type="details"] > button + div > summary + div[data-type="detailsContent"]
  const summary = detailsElement.querySelector('summary');
  const content = detailsElement.querySelector('[data-type="detailsContent"]');

  const summaryText = summary ? summary.textContent.trim() : 'Details';

  // Get the inner HTML of content, preserving HTML structure for tables, lists, etc.
  // We need to get the actual HTML content, not convert to markdown
  let contentHtml = '';
  if (content) {
    // Clone the content to avoid modifying the original
    const clone = content.cloneNode(true);
    contentHtml = clone.innerHTML.trim();
  }

  // Convert to standard HTML <details> for markdown storage
  // Content is stored as HTML to preserve tables, formatting, etc.
  return `\n\n<details>\n<summary>${summaryText}</summary>\n\n${contentHtml}\n\n</details>\n\n`;
}

/**
 * Convert native HTML details element to markdown-embedded HTML
 * Since markdown doesn't have native collapsible syntax, we preserve the HTML
 * Content is stored as HTML to preserve tables, formatting, etc.
 */
function convertDetails(detailsElement) {
  const summary = detailsElement.querySelector('summary');
  const summaryText = summary ? summary.textContent.trim() : 'Details';

  // Get HTML content from children that are not summary
  let contentHtml = '';
  for (const child of detailsElement.children) {
    if (child.tagName.toLowerCase() !== 'summary') {
      contentHtml += child.outerHTML;
    }
  }
  contentHtml = contentHtml.trim();

  return `\n\n<details>\n<summary>${summaryText}</summary>\n\n${contentHtml}\n\n</details>\n\n`;
}

function convertTable(tableElement) {
  // Store tables as HTML to preserve column widths, line breaks,
  // and formatting that GFM markdown cannot represent
  return tableElement.outerHTML + '\n\n';
}

function convertList(listElement, listDepth, ordered) {
  let markdown = '';
  const items = listElement.querySelectorAll(':scope > li');

  items.forEach((li, index) => {
    // Use 3 spaces for indentation (works better with marked parser for nested lists)
    const indent = '   '.repeat(listDepth);
    const bullet = ordered ? `${index + 1}.` : '-';

    // Separate text content from nested lists
    let textContent = '';
    let nestedLists = '';

    for (const child of li.childNodes) {
      if (child.nodeType === Node.TEXT_NODE) {
        textContent += child.textContent;
      } else if (child.nodeType === Node.ELEMENT_NODE) {
        const tagName = child.tagName.toLowerCase();
        if (tagName === 'ul' || tagName === 'ol') {
          // Process nested list separately
          nestedLists += convertList(child, listDepth + 1, tagName === 'ol');
        } else {
          textContent += nodeToMarkdown(child, listDepth + 1);
        }
      }
    }

    markdown += `${indent}${bullet} ${textContent.trim()}\n`;
    if (nestedLists) {
      markdown += nestedLists;
    }
  });

  if (listDepth === 0) {
    markdown += '\n';
  }

  return markdown;
}

/**
 * Clean up markdown formatting
 * Normalizes spacing around tables to prevent doubling on each save
 */
function cleanMarkdown(markdown) {
  if (!markdown) return '';

  // Normalize: collapse 4+ consecutive newlines into exactly 3.
  // \n\n = standard paragraph break (no visual blank line)
  // \n\n\n = one visual blank line (user intentionally added spacing)
  // \n\n\n\n+ = too many — TipTap adds phantom <p> between block nodes on each
  //   save cycle, which generates an extra \n\n. Cap at \n\n\n to stop growth.
  let cleaned = markdown.replace(/\n{4,}/g, '\n\n\n');

  return cleaned.trim();
}


/***/ }

/******/ 	});
/************************************************************************/
/******/ 	// The module cache
/******/ 	var __webpack_module_cache__ = {};
/******/ 	
/******/ 	// The require function
/******/ 	function __webpack_require__(moduleId) {
/******/ 		// Check if module is in cache
/******/ 		var cachedModule = __webpack_module_cache__[moduleId];
/******/ 		if (cachedModule !== undefined) {
/******/ 			return cachedModule.exports;
/******/ 		}
/******/ 		// Check if module exists (development only)
/******/ 		if (__webpack_modules__[moduleId] === undefined) {
/******/ 			var e = new Error("Cannot find module '" + moduleId + "'");
/******/ 			e.code = 'MODULE_NOT_FOUND';
/******/ 			throw e;
/******/ 		}
/******/ 		// Create a new module (and put it into the cache)
/******/ 		var module = __webpack_module_cache__[moduleId] = {
/******/ 			id: moduleId,
/******/ 			// no module.loaded needed
/******/ 			exports: {}
/******/ 		};
/******/ 	
/******/ 		// Execute the module function
/******/ 		__webpack_modules__[moduleId].call(module.exports, module, module.exports, __webpack_require__);
/******/ 	
/******/ 		// Return the exports of the module
/******/ 		return module.exports;
/******/ 	}
/******/ 	
/******/ 	// expose the modules object (__webpack_modules__)
/******/ 	__webpack_require__.m = __webpack_modules__;
/******/ 	
/************************************************************************/
/******/ 	/* webpack/runtime/chunk loaded */
/******/ 	(() => {
/******/ 		var deferred = [];
/******/ 		__webpack_require__.O = (result, chunkIds, fn, priority) => {
/******/ 			if(chunkIds) {
/******/ 				priority = priority || 0;
/******/ 				for(var i = deferred.length; i > 0 && deferred[i - 1][2] > priority; i--) deferred[i] = deferred[i - 1];
/******/ 				deferred[i] = [chunkIds, fn, priority];
/******/ 				return;
/******/ 			}
/******/ 			var notFulfilled = Infinity;
/******/ 			for (var i = 0; i < deferred.length; i++) {
/******/ 				var [chunkIds, fn, priority] = deferred[i];
/******/ 				var fulfilled = true;
/******/ 				for (var j = 0; j < chunkIds.length; j++) {
/******/ 					if ((priority & 1 === 0 || notFulfilled >= priority) && Object.keys(__webpack_require__.O).every((key) => (__webpack_require__.O[key](chunkIds[j])))) {
/******/ 						chunkIds.splice(j--, 1);
/******/ 					} else {
/******/ 						fulfilled = false;
/******/ 						if(priority < notFulfilled) notFulfilled = priority;
/******/ 					}
/******/ 				}
/******/ 				if(fulfilled) {
/******/ 					deferred.splice(i--, 1)
/******/ 					var r = fn();
/******/ 					if (r !== undefined) result = r;
/******/ 				}
/******/ 			}
/******/ 			return result;
/******/ 		};
/******/ 	})();
/******/ 	
/******/ 	/* webpack/runtime/compat get default export */
/******/ 	(() => {
/******/ 		// getDefaultExport function for compatibility with non-harmony modules
/******/ 		__webpack_require__.n = (module) => {
/******/ 			var getter = module && module.__esModule ?
/******/ 				() => (module['default']) :
/******/ 				() => (module);
/******/ 			__webpack_require__.d(getter, { a: getter });
/******/ 			return getter;
/******/ 		};
/******/ 	})();
/******/ 	
/******/ 	/* webpack/runtime/create fake namespace object */
/******/ 	(() => {
/******/ 		var getProto = Object.getPrototypeOf ? (obj) => (Object.getPrototypeOf(obj)) : (obj) => (obj.__proto__);
/******/ 		var leafPrototypes;
/******/ 		// create a fake namespace object
/******/ 		// mode & 1: value is a module id, require it
/******/ 		// mode & 2: merge all properties of value into the ns
/******/ 		// mode & 4: return value when already ns object
/******/ 		// mode & 16: return value when it's Promise-like
/******/ 		// mode & 8|1: behave like require
/******/ 		__webpack_require__.t = function(value, mode) {
/******/ 			if(mode & 1) value = this(value);
/******/ 			if(mode & 8) return value;
/******/ 			if(typeof value === 'object' && value) {
/******/ 				if((mode & 4) && value.__esModule) return value;
/******/ 				if((mode & 16) && typeof value.then === 'function') return value;
/******/ 			}
/******/ 			var ns = Object.create(null);
/******/ 			__webpack_require__.r(ns);
/******/ 			var def = {};
/******/ 			leafPrototypes = leafPrototypes || [null, getProto({}), getProto([]), getProto(getProto)];
/******/ 			for(var current = mode & 2 && value; (typeof current == 'object' || typeof current == 'function') && !~leafPrototypes.indexOf(current); current = getProto(current)) {
/******/ 				Object.getOwnPropertyNames(current).forEach((key) => (def[key] = () => (value[key])));
/******/ 			}
/******/ 			def['default'] = () => (value);
/******/ 			__webpack_require__.d(ns, def);
/******/ 			return ns;
/******/ 		};
/******/ 	})();
/******/ 	
/******/ 	/* webpack/runtime/define property getters */
/******/ 	(() => {
/******/ 		// define getter functions for harmony exports
/******/ 		__webpack_require__.d = (exports, definition) => {
/******/ 			for(var key in definition) {
/******/ 				if(__webpack_require__.o(definition, key) && !__webpack_require__.o(exports, key)) {
/******/ 					Object.defineProperty(exports, key, { enumerable: true, get: definition[key] });
/******/ 				}
/******/ 			}
/******/ 		};
/******/ 	})();
/******/ 	
/******/ 	/* webpack/runtime/ensure chunk */
/******/ 	(() => {
/******/ 		__webpack_require__.f = {};
/******/ 		// This file contains only the entry chunk.
/******/ 		// The chunk loading function for additional chunks
/******/ 		__webpack_require__.e = (chunkId) => {
/******/ 			return Promise.all(Object.keys(__webpack_require__.f).reduce((promises, key) => {
/******/ 				__webpack_require__.f[key](chunkId, promises);
/******/ 				return promises;
/******/ 			}, []));
/******/ 		};
/******/ 	})();
/******/ 	
/******/ 	/* webpack/runtime/get javascript chunk filename */
/******/ 	(() => {
/******/ 		// This function allow to reference async chunks
/******/ 		__webpack_require__.u = (chunkId) => {
/******/ 			// return url for filenames not based on template
/******/ 			if (chunkId === "shared") return "intravox-" + chunkId + ".js";
/******/ 			// return url for filenames based on template
/******/ 			return "intravox-" + chunkId + "." + {"src_components_PageEditor_vue":"121b8f9f","src_components_PageListModal_vue":"87f541c5","src_components_PageTreeModal_vue":"6466c0fb","src_components_NewPageModal_vue":"dafeb3fc","src_components_NavigationEditor_vue":"833ac9e5","src_components_PageDetailsSidebar_vue":"aca1cf0e","src_components_WelcomeScreen_vue":"8e616241","src_components_PageSettingsModal_vue":"fb8fce3c","src_components_SaveAsTemplateModal_vue":"5f8e99f1","src_components_FeedSettings_vue":"ce59b8cf","src_components_LinksWidget_vue":"c97c01d5","src_components_NewsWidget_vue":"e3ee6249","src_components_PeopleWidget_vue":"ae66e9de","src_components_CalendarWidget_vue":"cd1be272","src_components_PhotoStoryWidget_vue":"b1946e8f","src_components_FileStoryWidget_vue":"9fa1a15a","src_utils_textAlignExtension_js":"7aea01d2","src_utils_dummyTextGenerator_js":"146b8aad","src_utils_tableResizeHandle_js":"e2902005","src_components_PhotoLightbox_vue":"b935a4a2","src_components_PhotoStoryMap_vue":"78bbe923","src_components_PhotoStoryDayMap_vue":"a39a43d6"}[chunkId] + ".js";
/******/ 		};
/******/ 	})();
/******/ 	
/******/ 	/* webpack/runtime/global */
/******/ 	(() => {
/******/ 		__webpack_require__.g = (function() {
/******/ 			if (typeof globalThis === 'object') return globalThis;
/******/ 			try {
/******/ 				return this || new Function('return this')();
/******/ 			} catch (e) {
/******/ 				if (typeof window === 'object') return window;
/******/ 			}
/******/ 		})();
/******/ 	})();
/******/ 	
/******/ 	/* webpack/runtime/hasOwnProperty shorthand */
/******/ 	(() => {
/******/ 		__webpack_require__.o = (obj, prop) => (Object.prototype.hasOwnProperty.call(obj, prop))
/******/ 	})();
/******/ 	
/******/ 	/* webpack/runtime/load script */
/******/ 	(() => {
/******/ 		var inProgress = {};
/******/ 		var dataWebpackPrefix = "nextcloud-intravox:";
/******/ 		// loadScript function to load a script via script tag
/******/ 		__webpack_require__.l = (url, done, key, chunkId) => {
/******/ 			if(inProgress[url]) { inProgress[url].push(done); return; }
/******/ 			var script, needAttach;
/******/ 			if(key !== undefined) {
/******/ 				var scripts = document.getElementsByTagName("script");
/******/ 				for(var i = 0; i < scripts.length; i++) {
/******/ 					var s = scripts[i];
/******/ 					if(s.getAttribute("src") == url || s.getAttribute("data-webpack") == dataWebpackPrefix + key) { script = s; break; }
/******/ 				}
/******/ 			}
/******/ 			if(!script) {
/******/ 				needAttach = true;
/******/ 				script = document.createElement('script');
/******/ 		
/******/ 				script.charset = 'utf-8';
/******/ 				if (__webpack_require__.nc) {
/******/ 					script.setAttribute("nonce", __webpack_require__.nc);
/******/ 				}
/******/ 				script.setAttribute("data-webpack", dataWebpackPrefix + key);
/******/ 		
/******/ 				script.src = url;
/******/ 			}
/******/ 			inProgress[url] = [done];
/******/ 			var onScriptComplete = (prev, event) => {
/******/ 				// avoid mem leaks in IE.
/******/ 				script.onerror = script.onload = null;
/******/ 				clearTimeout(timeout);
/******/ 				var doneFns = inProgress[url];
/******/ 				delete inProgress[url];
/******/ 				script.parentNode && script.parentNode.removeChild(script);
/******/ 				doneFns && doneFns.forEach((fn) => (fn(event)));
/******/ 				if(prev) return prev(event);
/******/ 			}
/******/ 			var timeout = setTimeout(onScriptComplete.bind(null, undefined, { type: 'timeout', target: script }), 120000);
/******/ 			script.onerror = onScriptComplete.bind(null, script.onerror);
/******/ 			script.onload = onScriptComplete.bind(null, script.onload);
/******/ 			needAttach && document.head.appendChild(script);
/******/ 		};
/******/ 	})();
/******/ 	
/******/ 	/* webpack/runtime/make namespace object */
/******/ 	(() => {
/******/ 		// define __esModule on exports
/******/ 		__webpack_require__.r = (exports) => {
/******/ 			if(typeof Symbol !== 'undefined' && Symbol.toStringTag) {
/******/ 				Object.defineProperty(exports, Symbol.toStringTag, { value: 'Module' });
/******/ 			}
/******/ 			Object.defineProperty(exports, '__esModule', { value: true });
/******/ 		};
/******/ 	})();
/******/ 	
/******/ 	/* webpack/runtime/publicPath */
/******/ 	(() => {
/******/ 		var scriptUrl;
/******/ 		if (__webpack_require__.g.importScripts) scriptUrl = __webpack_require__.g.location + "";
/******/ 		var document = __webpack_require__.g.document;
/******/ 		if (!scriptUrl && document) {
/******/ 			if (document.currentScript && document.currentScript.tagName.toUpperCase() === 'SCRIPT')
/******/ 				scriptUrl = document.currentScript.src;
/******/ 			if (!scriptUrl) {
/******/ 				var scripts = document.getElementsByTagName("script");
/******/ 				if(scripts.length) {
/******/ 					var i = scripts.length - 1;
/******/ 					while (i > -1 && (!scriptUrl || !/^http(s?):/.test(scriptUrl))) scriptUrl = scripts[i--].src;
/******/ 				}
/******/ 			}
/******/ 		}
/******/ 		// When supporting browsers where an automatic publicPath is not supported you must specify an output.publicPath manually via configuration
/******/ 		// or pass an empty string ("") and set the __webpack_public_path__ variable from your code to use your own logic.
/******/ 		if (!scriptUrl) throw new Error("Automatic publicPath is not supported in this browser");
/******/ 		scriptUrl = scriptUrl.replace(/^blob:/, "").replace(/#.*$/, "").replace(/\?.*$/, "").replace(/\/[^\/]+$/, "/");
/******/ 		__webpack_require__.p = scriptUrl;
/******/ 	})();
/******/ 	
/******/ 	/* webpack/runtime/jsonp chunk loading */
/******/ 	(() => {
/******/ 		__webpack_require__.b = (typeof document !== 'undefined' && document.baseURI) || self.location.href;
/******/ 		
/******/ 		// object to store loaded and loading chunks
/******/ 		// undefined = chunk not loaded, null = chunk preloaded/prefetched
/******/ 		// [resolve, reject, Promise] = chunk loading, 0 = chunk loaded
/******/ 		var installedChunks = {
/******/ 			"main": 0
/******/ 		};
/******/ 		
/******/ 		__webpack_require__.f.j = (chunkId, promises) => {
/******/ 				// JSONP chunk loading for javascript
/******/ 				var installedChunkData = __webpack_require__.o(installedChunks, chunkId) ? installedChunks[chunkId] : undefined;
/******/ 				if(installedChunkData !== 0) { // 0 means "already installed".
/******/ 		
/******/ 					// a Promise means "currently loading".
/******/ 					if(installedChunkData) {
/******/ 						promises.push(installedChunkData[2]);
/******/ 					} else {
/******/ 						if(true) { // all chunks have JS
/******/ 							// setup Promise in chunk cache
/******/ 							var promise = new Promise((resolve, reject) => (installedChunkData = installedChunks[chunkId] = [resolve, reject]));
/******/ 							promises.push(installedChunkData[2] = promise);
/******/ 		
/******/ 							// start chunk loading
/******/ 							var url = __webpack_require__.p + __webpack_require__.u(chunkId);
/******/ 							// create error before stack unwound to get useful stacktrace later
/******/ 							var error = new Error();
/******/ 							var loadingEnded = (event) => {
/******/ 								if(__webpack_require__.o(installedChunks, chunkId)) {
/******/ 									installedChunkData = installedChunks[chunkId];
/******/ 									if(installedChunkData !== 0) installedChunks[chunkId] = undefined;
/******/ 									if(installedChunkData) {
/******/ 										var errorType = event && (event.type === 'load' ? 'missing' : event.type);
/******/ 										var realSrc = event && event.target && event.target.src;
/******/ 										error.message = 'Loading chunk ' + chunkId + ' failed.\n(' + errorType + ': ' + realSrc + ')';
/******/ 										error.name = 'ChunkLoadError';
/******/ 										error.type = errorType;
/******/ 										error.request = realSrc;
/******/ 										installedChunkData[1](error);
/******/ 									}
/******/ 								}
/******/ 							};
/******/ 							__webpack_require__.l(url, loadingEnded, "chunk-" + chunkId, chunkId);
/******/ 						}
/******/ 					}
/******/ 				}
/******/ 		};
/******/ 		
/******/ 		// no prefetching
/******/ 		
/******/ 		// no preloaded
/******/ 		
/******/ 		// no HMR
/******/ 		
/******/ 		// no HMR manifest
/******/ 		
/******/ 		__webpack_require__.O.j = (chunkId) => (installedChunks[chunkId] === 0);
/******/ 		
/******/ 		// install a JSONP callback for chunk loading
/******/ 		var webpackJsonpCallback = (parentChunkLoadingFunction, data) => {
/******/ 			var [chunkIds, moreModules, runtime] = data;
/******/ 			// add "moreModules" to the modules object,
/******/ 			// then flag all "chunkIds" as loaded and fire callback
/******/ 			var moduleId, chunkId, i = 0;
/******/ 			if(chunkIds.some((id) => (installedChunks[id] !== 0))) {
/******/ 				for(moduleId in moreModules) {
/******/ 					if(__webpack_require__.o(moreModules, moduleId)) {
/******/ 						__webpack_require__.m[moduleId] = moreModules[moduleId];
/******/ 					}
/******/ 				}
/******/ 				if(runtime) var result = runtime(__webpack_require__);
/******/ 			}
/******/ 			if(parentChunkLoadingFunction) parentChunkLoadingFunction(data);
/******/ 			for(;i < chunkIds.length; i++) {
/******/ 				chunkId = chunkIds[i];
/******/ 				if(__webpack_require__.o(installedChunks, chunkId) && installedChunks[chunkId]) {
/******/ 					installedChunks[chunkId][0]();
/******/ 				}
/******/ 				installedChunks[chunkId] = 0;
/******/ 			}
/******/ 			return __webpack_require__.O(result);
/******/ 		}
/******/ 		
/******/ 		var chunkLoadingGlobal = self["webpackChunknextcloud_intravox"] = self["webpackChunknextcloud_intravox"] || [];
/******/ 		chunkLoadingGlobal.forEach(webpackJsonpCallback.bind(null, 0));
/******/ 		chunkLoadingGlobal.push = webpackJsonpCallback.bind(null, chunkLoadingGlobal.push.bind(chunkLoadingGlobal));
/******/ 	})();
/******/ 	
/******/ 	/* webpack/runtime/nonce */
/******/ 	(() => {
/******/ 		__webpack_require__.nc = undefined;
/******/ 	})();
/******/ 	
/************************************************************************/
/******/ 	
/******/ 	// startup
/******/ 	// Load entry module and return exports
/******/ 	// This entry module depends on other loaded chunks and execution need to be delayed
/******/ 	var __webpack_exports__ = __webpack_require__.O(undefined, ["vendors"], () => (__webpack_require__("./src/main.js")))
/******/ 	__webpack_exports__ = __webpack_require__.O(__webpack_exports__);
/******/ 	
/******/ })()
;
//# sourceMappingURL=intravox-main.js.map