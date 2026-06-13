"use strict";
(self["webpackChunknextcloud_intravox"] = self["webpackChunknextcloud_intravox"] || []).push([["src_components_FileStoryWidget_vue"],{

/***/ "./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/dist/stylePostLoader.js!./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/FileStoryWidget.vue?vue&type=style&index=0&id=b4593806&scoped=true&lang=css"
/*!****************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/dist/stylePostLoader.js!./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/FileStoryWidget.vue?vue&type=style&index=0&id=b4593806&scoped=true&lang=css ***!
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
.file-story-widget[data-v-b4593806] {
  width: 100%;
  margin: 12px 0;
}
.fs-loading[data-v-b4593806], .fs-error[data-v-b4593806], .fs-empty[data-v-b4593806] {
  display: flex;
  align-items: center;
  justify-content: center;
  flex-direction: column;
  gap: 8px;
  padding: 32px 16px;
  color: var(--fs-text-muted, var(--color-text-maxcontrast));
  font-size: 13px;
  background: var(--color-background-hover);
  border-radius: var(--border-radius-large);
}
.fs-empty-hint[data-v-b4593806] {
  display: block;
  max-width: 480px;
  margin-top: 8px;
  line-height: 1.4;
  text-align: center;
}
.fs-skeleton-row[data-v-b4593806] {
  width: 100%;
  height: 48px;
  background: var(--color-background-dark);
  border-radius: var(--border-radius);
  margin-bottom: 4px;
  animation: fs-pulse-b4593806 1.4s ease-in-out infinite;
}
@keyframes fs-pulse-b4593806 {
0%, 100% { opacity: 0.55;
}
50% { opacity: 0.85;
}
}
.fs-day[data-v-b4593806], .fs-group[data-v-b4593806] {
  margin-bottom: 16px;
}

/* Transparent date headers — inherit the row/widget background instead of
 * forcing a white block over a colored row. The hairline border separates
 * sections visually without an opaque band. */
.fs-day-header[data-v-b4593806], .fs-group-header[data-v-b4593806] {
  display: flex;
  align-items: baseline;
  gap: 10px;
  padding: 10px 4px 4px;
  margin-top: 4px;
  border-bottom: 1px solid var(--color-border);
  background: transparent;
}
.fs-day-date[data-v-b4593806], .fs-group-label[data-v-b4593806] {
  margin: 0;
  font-size: 14px;
  font-weight: 600;
  color: var(--fs-text, var(--color-main-text));
  /* Slightly muted weight in the header so it doesn't compete with filenames
   * but stays readable in both light and dark themes via --color-main-text. */
  letter-spacing: 0.01em;
}
.fs-day-count[data-v-b4593806], .fs-group-count[data-v-b4593806] {
  font-size: 11px;
  color: var(--fs-text-muted, var(--color-text-maxcontrast));
  /* color-mix tint of the accent color — adapts to theming, no fixed values.
   * Falls back to background-hover if color-mix is not supported (NC32+ targets
   * Chromium 111+ / Firefox 113+ / Safari 16.2+ which all support it). */
  background: color-mix(in srgb, var(--color-primary-element) 14%, transparent);
  padding: 2px 8px;
  border-radius: 999px;
}
.fs-list[data-v-b4593806] {
  list-style: none;
  margin: 0;
  padding: 0;
}
.fs-row[data-v-b4593806] {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 8px 8px;
  border-radius: var(--border-radius);
  cursor: pointer;
  transition: background 0.12s ease;
}
.fs-row[data-v-b4593806]:hover, .fs-row[data-v-b4593806]:focus {
  background: var(--color-background-hover);
  outline: none;
}
[data-v-b4593806] .fs-row-icon {
  width: 32px;
  height: 32px;
  flex-shrink: 0;
  border-radius: 4px;
}
[data-v-b4593806] .fs-row-icon--placeholder {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  font-size: 22px;
  background: var(--color-background-dark);
}
.fs-row-main[data-v-b4593806] {
  flex: 1;
  min-width: 0;
}
.fs-row-name[data-v-b4593806] {
  font-size: 14px;
  color: var(--fs-text, var(--color-main-text));
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  display: flex;
  align-items: center;
  gap: 6px;
}

/* Subtle cloud-badge next to filenames that live in federated shares.
   Communicates "this came from another Nextcloud — MetaVox metadata not
   reachable" without breaking the file-rendering layout. */
[data-v-b4593806] .fs-federated-badge {
  display: inline-flex;
  align-items: center;
  flex-shrink: 0;
  color: var(--fs-text-muted, var(--color-text-maxcontrast));
  opacity: 0.7;
}
[data-v-b4593806] .fs-federated-badge:hover {
  opacity: 1;
}
.fs-row-meta[data-v-b4593806] {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
  font-size: 11px;
  color: var(--fs-text-muted, var(--color-text-maxcontrast));
  margin-top: 2px;
}

/* Tiles mode: grid of preview thumbnails. Responsive via auto-fill so tile
   counts adjust to the column width — same masonry feel as a photo gallery
   but with document covers instead of photos. */
.fs-tiles[data-v-b4593806] {
  display: grid;
  /* Driven by tilesGridStyle() — falls back to medium when not set. */
  grid-template-columns: repeat(auto-fill, minmax(var(--fs-tile-min, 180px), 1fr));
  gap: var(--fs-tile-gap, 14px);
}
.fs-tile[data-v-b4593806] {
  display: flex;
  flex-direction: column;
  border: 1px solid var(--color-border);
  border-radius: var(--border-radius-large);
  background: var(--color-main-background);
  cursor: pointer;
  overflow: hidden;
  transition: box-shadow 0.12s ease, transform 0.12s ease;
}
.fs-tile[data-v-b4593806]:hover, .fs-tile[data-v-b4593806]:focus {
  box-shadow: 0 4px 14px rgba(0, 0, 0, 0.12);
  transform: translateY(-1px);
  outline: none;
}
.fs-tile-preview[data-v-b4593806] {
  position: relative;
  width: 100%;
  aspect-ratio: 3 / 4;          /* A4-ish portrait — most docs */
  background: var(--color-background-dark);
  overflow: hidden;
}

/* Img sits on top of the fallback; when NC returns 404 the img is unloaded
   and the fallback (mime-icon + extension) shows through. */
.fs-tile-img[data-v-b4593806] {
  position: absolute;
  inset: 0;
  width: 100%;
  height: 100%;
  object-fit: cover;
  display: block;
  z-index: 1;
}
.fs-tile-fallback[data-v-b4593806] {
  position: absolute;
  inset: 0;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 8px;
  color: var(--fs-text-muted, var(--color-text-maxcontrast));
  z-index: 0;
}
.fs-tile-ext[data-v-b4593806] {
  font-size: 12px;
  font-weight: 600;
  letter-spacing: 0.05em;
}
.fs-tile-body[data-v-b4593806] {
  padding: 8px 10px 10px;
  display: flex;
  flex-direction: column;
  gap: 4px;
}
.fs-tile-name[data-v-b4593806] {
  font-size: 13px;
  font-weight: 500;
  color: var(--fs-text, var(--color-main-text));
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  display: flex;
  align-items: center;
  gap: 6px;
}
.fs-tile-meta[data-v-b4593806] {
  display: flex;
  flex-wrap: wrap;
  gap: 6px;
  font-size: 11px;
  color: var(--fs-text-muted, var(--color-text-maxcontrast));
}
.fs-scroll-sentinel[data-v-b4593806] {
  display: flex;
  justify-content: center;
  align-items: center;
  min-height: 48px;
  padding: 12px 0;
}
.fs-loading-more[data-v-b4593806] {
  font-size: 12px;
  color: var(--fs-text-muted, var(--color-text-maxcontrast));
}
.fs-truncated[data-v-b4593806] {
  margin: 12px 0;
  padding: 8px 12px;
  background: var(--color-warning-background);
  border: 1px solid var(--color-warning);
  border-radius: 6px;
  font-size: 12px;
  color: var(--fs-text-muted, var(--color-text-maxcontrast));
}
@media (prefers-reduced-motion: reduce) {
.fs-skeleton-row[data-v-b4593806] {
    animation: none;
}
}

/* When the row background is dark (e.g. --color-primary-element), the widget
   sits on a saturated surface. Lift muted text, soften hover/border, and keep
   skeletons/tile-cards readable against the colored backdrop. */
.file-story-widget.fs--on-dark .fs-day-count[data-v-b4593806],
.file-story-widget.fs--on-dark .fs-group-count[data-v-b4593806] {
  background: rgba(255, 255, 255, 0.18);
  color: var(--fs-text, #fff);
}
.file-story-widget.fs--on-dark .fs-row[data-v-b4593806]:hover,
.file-story-widget.fs--on-dark .fs-row[data-v-b4593806]:focus {
  background: rgba(255, 255, 255, 0.12);
}
.file-story-widget.fs--on-dark .fs-day-header[data-v-b4593806],
.file-story-widget.fs--on-dark .fs-group-header[data-v-b4593806] {
  border-bottom-color: rgba(255, 255, 255, 0.25);
}
.file-story-widget.fs--on-dark .fs-row-meta[data-v-b4593806],
.file-story-widget.fs--on-dark[data-v-b4593806] .fs-federated-badge {
  opacity: 0.82;
}
.file-story-widget.fs--on-dark .fs-skeleton-row[data-v-b4593806] {
  background: rgba(255, 255, 255, 0.12);
}

/* Tiles on a dark row: tinted-glass card so the white tile stops "cutting a
   hole" in the colored background. Filename stays white (--fs-text), reads on
   the translucent backdrop because there's enough contrast against both the
   row-bg and the soft white tint. */
.file-story-widget.fs--on-dark .fs-tile[data-v-b4593806] {
  background: rgba(255, 255, 255, 0.14);
  border-color: rgba(255, 255, 255, 0.22);
}
.file-story-widget.fs--on-dark .fs-tile[data-v-b4593806]:hover,
.file-story-widget.fs--on-dark .fs-tile[data-v-b4593806]:focus {
  background: rgba(255, 255, 255, 0.2);
  box-shadow: 0 4px 14px rgba(0, 0, 0, 0.25);
}
.file-story-widget.fs--on-dark .fs-tile-preview[data-v-b4593806] {
  background: rgba(255, 255, 255, 0.08);
}

/* Mime-icon in the placeholder uses currentColor for the SVG paths via NC's
   icon system — lifting opacity is enough to keep the icon visible on the
   tinted tile surface without a stark grey square. */
.file-story-widget.fs--on-dark[data-v-b4593806] .fs-row-icon--placeholder,
.file-story-widget.fs--on-dark .fs-tile-fallback[data-v-b4593806] {
  background: transparent;
  color: var(--fs-text, #fff);
  opacity: 0.88;
}
`, "",{"version":3,"sources":["webpack://./src/components/FileStoryWidget.vue"],"names":[],"mappings":";AA2sBA;EACE,WAAW;EACX,cAAc;AAChB;AAEA;EACE,aAAa;EACb,mBAAmB;EACnB,uBAAuB;EACvB,sBAAsB;EACtB,QAAQ;EACR,kBAAkB;EAClB,0DAA0D;EAC1D,eAAe;EACf,yCAAyC;EACzC,yCAAyC;AAC3C;AAEA;EACE,cAAc;EACd,gBAAgB;EAChB,eAAe;EACf,gBAAgB;EAChB,kBAAkB;AACpB;AAEA;EACE,WAAW;EACX,YAAY;EACZ,wCAAwC;EACxC,mCAAmC;EACnC,kBAAkB;EAClB,sDAA6C;AAC/C;AAEA;AACE,WAAW,aAAa;AAAE;AAC1B,MAAM,aAAa;AAAE;AACvB;AAEA;EACE,mBAAmB;AACrB;;AAEA;;8CAE8C;AAC9C;EACE,aAAa;EACb,qBAAqB;EACrB,SAAS;EACT,qBAAqB;EACrB,eAAe;EACf,4CAA4C;EAC5C,uBAAuB;AACzB;AAEA;EACE,SAAS;EACT,eAAe;EACf,gBAAgB;EAChB,6CAA6C;EAC7C;8EAC4E;EAC5E,sBAAsB;AACxB;AAEA;EACE,eAAe;EACf,0DAA0D;EAC1D;;yEAEuE;EACvE,6EAA6E;EAC7E,gBAAgB;EAChB,oBAAoB;AACtB;AAEA;EACE,gBAAgB;EAChB,SAAS;EACT,UAAU;AACZ;AAEA;EACE,aAAa;EACb,mBAAmB;EACnB,SAAS;EACT,gBAAgB;EAChB,mCAAmC;EACnC,eAAe;EACf,iCAAiC;AACnC;AAEA;EACE,yCAAyC;EACzC,aAAa;AACf;AAEA;EACE,WAAW;EACX,YAAY;EACZ,cAAc;EACd,kBAAkB;AACpB;AAEA;EACE,oBAAoB;EACpB,mBAAmB;EACnB,uBAAuB;EACvB,eAAe;EACf,wCAAwC;AAC1C;AAEA;EACE,OAAO;EACP,YAAY;AACd;AAEA;EACE,eAAe;EACf,6CAA6C;EAC7C,mBAAmB;EACnB,gBAAgB;EAChB,uBAAuB;EACvB,aAAa;EACb,mBAAmB;EACnB,QAAQ;AACV;;AAEA;;2DAE2D;AAC3D;EACE,oBAAoB;EACpB,mBAAmB;EACnB,cAAc;EACd,0DAA0D;EAC1D,YAAY;AACd;AAEA;EACE,UAAU;AACZ;AAEA;EACE,aAAa;EACb,eAAe;EACf,QAAQ;EACR,eAAe;EACf,0DAA0D;EAC1D,eAAe;AACjB;;AAEA;;gDAEgD;AAChD;EACE,aAAa;EACb,oEAAoE;EACpE,gFAAgF;EAChF,6BAA6B;AAC/B;AAEA;EACE,aAAa;EACb,sBAAsB;EACtB,qCAAqC;EACrC,yCAAyC;EACzC,wCAAwC;EACxC,eAAe;EACf,gBAAgB;EAChB,uDAAuD;AACzD;AAEA;EACE,0CAA0C;EAC1C,2BAA2B;EAC3B,aAAa;AACf;AAEA;EACE,kBAAkB;EAClB,WAAW;EACX,mBAAmB,WAAW,gCAAgC;EAC9D,wCAAwC;EACxC,gBAAgB;AAClB;;AAEA;4DAC4D;AAC5D;EACE,kBAAkB;EAClB,QAAQ;EACR,WAAW;EACX,YAAY;EACZ,iBAAiB;EACjB,cAAc;EACd,UAAU;AACZ;AAEA;EACE,kBAAkB;EAClB,QAAQ;EACR,aAAa;EACb,sBAAsB;EACtB,mBAAmB;EACnB,uBAAuB;EACvB,QAAQ;EACR,0DAA0D;EAC1D,UAAU;AACZ;AAEA;EACE,eAAe;EACf,gBAAgB;EAChB,sBAAsB;AACxB;AAEA;EACE,sBAAsB;EACtB,aAAa;EACb,sBAAsB;EACtB,QAAQ;AACV;AAEA;EACE,eAAe;EACf,gBAAgB;EAChB,6CAA6C;EAC7C,mBAAmB;EACnB,gBAAgB;EAChB,uBAAuB;EACvB,aAAa;EACb,mBAAmB;EACnB,QAAQ;AACV;AAEA;EACE,aAAa;EACb,eAAe;EACf,QAAQ;EACR,eAAe;EACf,0DAA0D;AAC5D;AAEA;EACE,aAAa;EACb,uBAAuB;EACvB,mBAAmB;EACnB,gBAAgB;EAChB,eAAe;AACjB;AAEA;EACE,eAAe;EACf,0DAA0D;AAC5D;AAEA;EACE,cAAc;EACd,iBAAiB;EACjB,2CAA2C;EAC3C,sCAAsC;EACtC,kBAAkB;EAClB,eAAe;EACf,0DAA0D;AAC5D;AAEA;AACE;IACE,eAAe;AACjB;AACF;;AAEA;;gEAEgE;AAChE;;EAEE,qCAAqC;EACrC,2BAA2B;AAC7B;AAEA;;EAEE,qCAAqC;AACvC;AAEA;;EAEE,8CAA8C;AAChD;AAEA;;EAEE,aAAa;AACf;AAEA;EACE,qCAAqC;AACvC;;AAEA;;;oCAGoC;AACpC;EACE,qCAAqC;EACrC,uCAAuC;AACzC;AAEA;;EAEE,oCAAoC;EACpC,0CAA0C;AAC5C;AAEA;EACE,qCAAqC;AACvC;;AAEA;;qDAEqD;AACrD;;EAEE,uBAAuB;EACvB,2BAA2B;EAC3B,aAAa;AACf","sourcesContent":["<template>\n  <div class=\"file-story-widget\" :class=\"rowBgClass\" :style=\"rowBgStyle\">\n    <!-- Loading -->\n    <div v-if=\"loading\" class=\"fs-loading\" role=\"status\" aria-live=\"polite\" :aria-label=\"t('Loading documents')\">\n      <div v-for=\"i in 5\" :key=\"i\" class=\"fs-skeleton-row\" aria-hidden=\"true\"></div>\n    </div>\n\n    <!-- Error -->\n    <div v-else-if=\"error\" class=\"fs-error\" role=\"alert\">\n      <AlertCircle :size=\"20\" aria-hidden=\"true\" />\n      <span>{{ error }}</span>\n      <NcButton type=\"secondary\" @click=\"fetch()\">\n        {{ t('Retry') }}\n      </NcButton>\n    </div>\n\n    <!-- No-access: minimal locked placeholder; deliberately hides the folder\n         name to avoid leaking the existence of a path the viewer can't see. -->\n    <div v-else-if=\"!files.length && accessReason === 'folder_not_accessible'\" class=\"fs-empty fs-empty--locked\" role=\"status\">\n      <LockOutline :size=\"28\" aria-hidden=\"true\" />\n      <p>{{ t('You do not have access to this widget') }}</p>\n    </div>\n\n    <!-- Empty (accessible folder but no documents to show) -->\n    <div v-else-if=\"!files.length\" class=\"fs-empty\" role=\"status\">\n      <FileDocumentOutline :size=\"28\" aria-hidden=\"true\" />\n      <p>{{ emptyMessage }}</p>\n      <small v-if=\"config.folderPath\">{{ config.folderPath }}</small>\n      <small v-if=\"showScanHint\" class=\"fs-empty-hint\">\n        {{ t('If you can see files in the Files app but not here, the file index may be out of sync. Ask an admin to run \"occ files:scan\" for this folder.') }}\n      </small>\n    </div>\n\n    <!-- Timeline mode -->\n    <div v-else-if=\"effectiveMode === 'timeline'\" class=\"fs-timeline\">\n      <section\n        v-for=\"day in timelineDays\"\n        :key=\"day.date\"\n        class=\"fs-day\"\n      >\n        <header class=\"fs-day-header\">\n          <h4 class=\"fs-day-date\">{{ day.label || formatLongDate(day.date) }}</h4>\n          <span class=\"fs-day-count\">{{ day.photos.length }}</span>\n        </header>\n        <ul class=\"fs-list\">\n          <li\n            v-for=\"file in day.photos\"\n            :key=\"file.file_id\"\n            class=\"fs-row\"\n            tabindex=\"0\"\n            role=\"button\"\n            :aria-label=\"t('Open {name}', { name: file.name })\"\n            @click=\"openFile(file)\"\n            @keydown.enter=\"openFile(file)\"\n            @keydown.space.prevent=\"openFile(file)\"\n          >\n            <FileTypeIcon :mime=\"file.mime\" />\n            <div class=\"fs-row-main\">\n              <div class=\"fs-row-name\">\n                {{ file.name }}\n                <CloudOutline\n                  v-if=\"file.is_federated\"\n                  :size=\"13\"\n                  class=\"fs-federated-badge\"\n                  role=\"img\"\n                  :aria-label=\"t('From federated share — MetaVox metadata not available')\"\n                  :title=\"t('From federated share — MetaVox metadata not available')\"\n                />\n              </div>\n              <div class=\"fs-row-meta\">\n                <span v-if=\"showColumn('date')\" class=\"fs-row-date\">{{ formatTime(file.mtime) }}</span>\n                <span v-if=\"showColumn('size')\" class=\"fs-row-size\">{{ formatSize(file.size) }}</span>\n                <span v-if=\"showColumn('path')\" class=\"fs-row-path\">{{ file.path }}</span>\n              </div>\n            </div>\n          </li>\n        </ul>\n      </section>\n    </div>\n\n    <!-- Tiles mode — visual grid with preview thumbnails -->\n    <div v-else-if=\"effectiveMode === 'tiles'\" class=\"fs-tiles\" :style=\"tilesGridStyle\" role=\"list\">\n      <div\n        v-for=\"file in files\"\n        :key=\"file.file_id\"\n        class=\"fs-tile\"\n        tabindex=\"0\"\n        role=\"button\"\n        :aria-label=\"t('Open {name}', { name: file.name })\"\n        @click=\"openFile(file)\"\n        @keydown.enter=\"openFile(file)\"\n        @keydown.space.prevent=\"openFile(file)\"\n      >\n        <div class=\"fs-tile-preview\">\n          <!-- Always render the mime-icon fallback. When NC has a usable\n               preview, the <img> on top hides it; on 404 the <img> stays\n               unloaded and the fallback shows through. More robust than\n               relying on @error event firing across all browsers. -->\n          <div class=\"fs-tile-fallback\">\n            <FileTypeIcon :mime=\"file.mime\" />\n            <span class=\"fs-tile-ext\">{{ fileExt(file.name) }}</span>\n          </div>\n          <img\n            v-if=\"!tilePreviewErrors[file.file_id]\"\n            :src=\"previewUrl(file, 400)\"\n            alt=\"\"\n            loading=\"lazy\"\n            decoding=\"async\"\n            class=\"fs-tile-img\"\n            @error=\"markPreviewError(file.file_id)\"\n          />\n        </div>\n        <div class=\"fs-tile-body\">\n          <div class=\"fs-tile-name\" :title=\"file.name\">\n            {{ file.name }}\n            <CloudOutline\n              v-if=\"file.is_federated\"\n              :size=\"12\"\n              class=\"fs-federated-badge\"\n              role=\"img\"\n              :aria-label=\"t('From federated share — MetaVox metadata not available')\"\n              :title=\"t('From federated share — MetaVox metadata not available')\"\n            />\n          </div>\n          <div class=\"fs-tile-meta\">\n            <span v-if=\"showColumn('date')\">{{ formatLongDate(rowDate(file)) }}</span>\n            <span v-if=\"showColumn('size')\">{{ formatSize(file.size) }}</span>\n          </div>\n        </div>\n      </div>\n    </div>\n\n    <!-- List mode (flat) -->\n    <div v-else-if=\"effectiveMode === 'list'\" class=\"fs-list-mode\">\n      <ul class=\"fs-list\">\n        <li\n          v-for=\"file in files\"\n          :key=\"file.file_id\"\n          class=\"fs-row\"\n          tabindex=\"0\"\n          role=\"button\"\n          :aria-label=\"t('Open {name}', { name: file.name })\"\n          @click=\"openFile(file)\"\n          @keydown.enter=\"openFile(file)\"\n        >\n          <FileTypeIcon :mime=\"file.mime\" />\n          <div class=\"fs-row-main\">\n            <div class=\"fs-row-name\">\n                {{ file.name }}\n                <CloudOutline\n                  v-if=\"file.is_federated\"\n                  :size=\"13\"\n                  class=\"fs-federated-badge\"\n                  role=\"img\"\n                  :aria-label=\"t('From federated share — MetaVox metadata not available')\"\n                  :title=\"t('From federated share — MetaVox metadata not available')\"\n                />\n              </div>\n            <div class=\"fs-row-meta\">\n              <span v-if=\"showColumn('date')\">{{ formatLongDateTime(file.mtime) }}</span>\n              <span v-if=\"showColumn('size')\">{{ formatSize(file.size) }}</span>\n              <span v-if=\"showColumn('path')\">{{ file.path }}</span>\n            </div>\n          </div>\n        </li>\n      </ul>\n    </div>\n\n    <!-- Grouped mode -->\n    <div v-else-if=\"effectiveMode === 'grouped'\" class=\"fs-grouped\">\n      <section\n        v-for=\"group in groups\"\n        :key=\"group.key\"\n        class=\"fs-group\"\n      >\n        <header class=\"fs-group-header\">\n          <h4 class=\"fs-group-label\">{{ group.label }}</h4>\n          <span class=\"fs-group-count\">{{ group.count }}</span>\n        </header>\n        <ul class=\"fs-list\">\n          <li\n            v-for=\"file in group.files\"\n            :key=\"file.file_id\"\n            class=\"fs-row\"\n            tabindex=\"0\"\n            role=\"button\"\n            :aria-label=\"t('Open {name}', { name: file.name })\"\n            @click=\"openFile(file)\"\n            @keydown.enter=\"openFile(file)\"\n            @keydown.space.prevent=\"openFile(file)\"\n          >\n            <FileTypeIcon :mime=\"file.mime\" />\n            <div class=\"fs-row-main\">\n              <div class=\"fs-row-name\">\n                {{ file.name }}\n                <CloudOutline\n                  v-if=\"file.is_federated\"\n                  :size=\"13\"\n                  class=\"fs-federated-badge\"\n                  role=\"img\"\n                  :aria-label=\"t('From federated share — MetaVox metadata not available')\"\n                  :title=\"t('From federated share — MetaVox metadata not available')\"\n                />\n              </div>\n              <div class=\"fs-row-meta\">\n                <span>{{ formatTime(file.mtime) }}</span>\n                <span>{{ formatSize(file.size) }}</span>\n              </div>\n            </div>\n          </li>\n        </ul>\n      </section>\n    </div>\n\n    <!-- Infinite-scroll sentinel -->\n    <div\n      v-if=\"pagination.hasMore\"\n      ref=\"scrollSentinel\"\n      class=\"fs-scroll-sentinel\"\n    >\n      <span v-if=\"loadingMore\" class=\"fs-loading-more\">{{ t('Loading more…') }}</span>\n    </div>\n\n    <!-- Truncated notice -->\n    <div v-if=\"pagination.truncated\" class=\"fs-truncated\">\n      {{ t('Showing first {n} documents. Use filters or a more specific folder.', { n: String(pagination.total) }) }}\n    </div>\n  </div>\n</template>\n\n<script>\nimport { defineAsyncComponent, h } from 'vue';\nimport axios from '@nextcloud/axios';\nimport { generateUrl } from '@nextcloud/router';\nimport { translate as t, getCanonicalLocale } from '@nextcloud/l10n';\nimport { NcButton } from '@nextcloud/vue';\nimport AlertCircle from 'vue-material-design-icons/AlertCircle.vue';\nimport FileDocumentOutline from 'vue-material-design-icons/FileDocumentOutline.vue';\nimport CloudOutline from 'vue-material-design-icons/CloudOutline.vue';\nimport LockOutline from 'vue-material-design-icons/LockOutline.vue';\n\n// FileTypeIcon renders a small mime-icon. Uses NC's `OC.MimeType.getIconUrl()`\n// when available (returns a path to NC's built-in icon SVG), falls back to a\n// neutral document icon otherwise.\nconst FileTypeIcon = {\n  name: 'FileTypeIcon',\n  props: { mime: { type: String, default: '' } },\n  setup(props) {\n    return () => {\n      let iconUrl = null;\n      try {\n        if (typeof window !== 'undefined' && window.OC && window.OC.MimeType && typeof window.OC.MimeType.getIconUrl === 'function') {\n          iconUrl = window.OC.MimeType.getIconUrl(props.mime || 'application/octet-stream');\n        }\n      } catch (e) { /* ignore */ }\n      if (iconUrl) {\n        return h('img', { src: iconUrl, class: 'fs-row-icon', alt: '', loading: 'lazy', width: 32, height: 32 });\n      }\n      // Fallback: plain neutral document icon\n      return h('span', { class: 'fs-row-icon fs-row-icon--placeholder' }, '📄');\n    };\n  },\n};\n\nexport default {\n  name: 'FileStoryWidget',\n  components: { AlertCircle, FileDocumentOutline, CloudOutline, LockOutline, FileTypeIcon, NcButton },\n  props: {\n    widget: { type: Object, required: true },\n    rowBackgroundColor: { type: String, default: '' },\n  },\n  data() {\n    return {\n      files: [],\n      timeline: [],\n      groups: [],\n      capabilities: null,\n      loading: true,\n      error: null,\n      // Set to 'folder_not_accessible' when the backend returns a 404 with\n      // that reason — used by emptyMessage()/showScanHint() to surface a\n      // permission-aware empty-state instead of the generic \"no documents\".\n      accessReason: null,\n      pagination: { offset: 0, pageSize: 100, total: 0, hasMore: false, truncated: false },\n      loadingMore: false,\n      _scrollObserver: null,\n      _abortController: null,\n      _lastEtag: null,\n      // Map file_id → true when its NC preview-thumbnail 404'd. Used in tiles\n      // mode to fall back to a mime-icon without flashing a broken image.\n      tilePreviewErrors: {},\n    };\n  },\n  computed: {\n    config() { return this.widget.config || {}; },\n    rowBgClass() {\n      const bg = String(this.rowBackgroundColor || '').trim();\n      if (!bg || bg === 'transparent') {\n        return '';\n      }\n      const dark = new Set([\n        'var(--color-primary-element)',\n        'var(--color-primary)',\n        'var(--color-error)',\n        'var(--color-success)',\n        'var(--color-warning)',\n      ]);\n      if (dark.has(bg)) {\n        return 'fs--on-dark';\n      }\n      return 'fs--on-tinted';\n    },\n    rowBgStyle() {\n      const bg = String(this.rowBackgroundColor || '').trim();\n      if (this.rowBgClass !== 'fs--on-dark') {\n        return {};\n      }\n      // Paired text color for the row background, matches Widget.vue's WCAG map.\n      const textMap = {\n        'var(--color-primary-element)': 'var(--color-primary-element-text)',\n        'var(--color-primary)': 'var(--color-primary-text)',\n        'var(--color-error)': 'var(--color-error-text)',\n        'var(--color-success)': 'var(--color-success-text)',\n        'var(--color-warning)': 'var(--color-warning-text)',\n      };\n      const text = textMap[bg] || '#fff';\n      return {\n        '--fs-text': text,\n        '--fs-text-muted': text,\n      };\n    },\n    effectiveMode() {\n      const allowed = ['timeline', 'tiles', 'list', 'grouped'];\n      return allowed.includes(this.config.mode) ? this.config.mode : 'timeline';\n    },\n    timelineDays() {\n      return this.timeline || [];\n    },\n    /**\n     * Context-aware empty-state message. Distinguishes \"folder not set yet\"\n     * (user just dropped in the widget) from \"filter matches nothing\" (user\n     * tuned filters too narrow) from generic \"folder is empty\".\n     */\n    emptyMessage() {\n      if (!this.config.folderPath) {\n        return t('intravox', 'No folder selected');\n      }\n      if (this.accessReason === 'folder_not_accessible') {\n        return t('intravox', 'You do not have access to this folder');\n      }\n      const hasFilters = Array.isArray(this.config.metaVoxFilters) && this.config.metaVoxFilters.length > 0;\n      if (hasFilters) {\n        return t('intravox', 'No documents match the current filters');\n      }\n      return t('intravox', 'No documents to show.');\n    },\n    /**\n     * Only show the file-index hint when the user picked a normal folder and\n     * got zero results — not when filters are active (more likely cause: no\n     * match) and not when the user lacks access (occ files:scan won't help).\n     */\n    showScanHint() {\n      if (!this.config.folderPath) return false;\n      if (this.accessReason === 'folder_not_accessible') return false;\n      const hasFilters = Array.isArray(this.config.metaVoxFilters) && this.config.metaVoxFilters.length > 0;\n      return !hasFilters;\n    },\n    fetchKey() {\n      const c = this.config || {};\n      return JSON.stringify({\n        f: c.folderPath || '',\n        m: c.mode || 'timeline',\n        gb: c.groupBy || '',\n        gr: c.granularity || 'day',\n        l: c.limit ?? null,\n        s: c.sortOrder || 'desc',\n        sb: c.sortBy || 'mtime',\n        flt: Array.isArray(c.metaVoxFilters) ? c.metaVoxFilters : [],\n      });\n    },\n    /**\n     * CSS custom properties that drive the tile grid sizing. Discrete steps\n     * because pixel-precise control is rarely useful and a 3-button picker\n     * keeps the editor UI compact.\n     */\n    tilesGridStyle() {\n      const size = this.config.tileSize || 'medium';\n      switch (size) {\n        case 'small':\n          return { '--fs-tile-min': '120px', '--fs-tile-gap': '10px' };\n        case 'large':\n          return { '--fs-tile-min': '260px', '--fs-tile-gap': '18px' };\n        case 'medium':\n        default:\n          return { '--fs-tile-min': '180px', '--fs-tile-gap': '14px' };\n      }\n    },\n  },\n  watch: {\n    fetchKey() {\n      // 700 ms debounce so quick edit-mode mutations (folder pick + mode flip\n      // + sort change in rapid succession) don't stack four heavy filecache\n      // queries on the Apache worker pool and cause 503s on the subsequent\n      // page save. Each in-flight fetch holds a worker until its SQL returns.\n      clearTimeout(this._debounce);\n      this._debounce = setTimeout(() => this.fetch(), 700);\n    },\n  },\n  mounted() {\n    if (typeof requestIdleCallback === 'function') {\n      this._idleHandle = requestIdleCallback(() => this.fetch());\n    } else {\n      this.fetch();\n    }\n  },\n  beforeUnmount() {\n    clearTimeout(this._debounce);\n    if (this._idleHandle && typeof cancelIdleCallback === 'function') {\n      cancelIdleCallback(this._idleHandle);\n    }\n    if (this._scrollObserver) {\n      this._scrollObserver.disconnect();\n      this._scrollObserver = null;\n    }\n    if (this._abortController) {\n      this._abortController.abort();\n      this._abortController = null;\n    }\n  },\n  methods: {\n    t(key, vars = {}) {\n      return t('intravox', key, vars);\n    },\n    async fetch() {\n      if (!this.config.folderPath) {\n        this.files = [];\n        this.timeline = [];\n        this.groups = [];\n        this.loading = false;\n        return;\n      }\n      // Cancel any pending fetch/fetchMore so we don't race a stale response\n      // overwriting fresh data after a rapid config change.\n      if (this._abortController) {\n        this._abortController.abort();\n      }\n      this._abortController = new AbortController();\n      const signal = this._abortController.signal;\n\n      this.pagination = { offset: 0, pageSize: 100, total: 0, hasMore: false, truncated: false };\n      this.loading = true;\n      this.error = null;\n      this.accessReason = null;\n      try {\n        const params = this.buildParams(0);\n        const url = generateUrl(`/apps/intravox/api/file-story/files?${params.toString()}`);\n        const headers = {};\n        if (this._lastEtag) headers['If-None-Match'] = this._lastEtag;\n        const res = await axios.get(url, { headers, signal, validateStatus: s => (s >= 200 && s < 300) || s === 304 || s === 404 });\n        if (res.status === 304) {\n          this.loading = false;\n          return;\n        }\n        if (res.status === 404) {\n          this.files = [];\n          this.timeline = [];\n          this.groups = [];\n          this.capabilities = null;\n          this.accessReason = res.data?.reason || null;\n          this.loading = false;\n          return;\n        }\n        const d = res.data || {};\n        this.files = d.files || [];\n        this.timeline = d.timeline || [];\n        this.groups = d.groups || [];\n        this.capabilities = d.capabilities || null;\n        this.warmFederatedPreviews(this.files);\n        if (d.pagination) {\n          this.pagination = {\n            offset: d.pagination.offset || 0,\n            pageSize: d.pagination.pageSize || 100,\n            total: d.pagination.total || this.files.length,\n            hasMore: !!d.pagination.hasMore,\n            truncated: !!d.pagination.truncated,\n          };\n        }\n        if (res.headers && res.headers.etag) this._lastEtag = res.headers.etag;\n        this.$nextTick(() => this.setupScrollSentinel());\n      } catch (err) {\n        if (axios.isCancel?.(err) || err?.name === 'CanceledError' || err?.code === 'ERR_CANCELED') {\n          return; // superseded by newer request\n        }\n        console.error('[FileStoryWidget] fetch failed:', err);\n        this.error = err.response?.data?.error || err.message || this.t('Failed to load documents');\n      } finally {\n        this.loading = false;\n      }\n    },\n    buildParams(offset) {\n      const params = new URLSearchParams({ mode: this.effectiveMode });\n      const c = this.config || {};\n      if (c.folderPath) params.append('folder', c.folderPath);\n      if (Array.isArray(c.metaVoxFilters) && c.metaVoxFilters.length) {\n        params.append('filters', JSON.stringify(c.metaVoxFilters));\n      }\n      if (this.effectiveMode === 'grouped' && c.groupBy) {\n        params.append('groupBy', String(c.groupBy));\n      }\n      if (this.effectiveMode === 'timeline' && c.granularity) {\n        params.append('granularity', String(c.granularity));\n      }\n      if (c.limit) params.append('limit', String(c.limit));\n      params.append('offset', String(offset));\n      params.append('pageSize', String(this.pagination.pageSize || 100));\n      params.append('sortOrder', c.sortOrder || 'desc');\n      params.append('sortBy', c.sortBy || 'mtime');\n      if (offset > 0 && this.pagination.total > 0) {\n        params.append('total', String(this.pagination.total));\n      }\n      return params;\n    },\n    async fetchMore() {\n      if (this.loadingMore || !this.pagination.hasMore) return;\n      this.loadingMore = true;\n      const signal = this._abortController?.signal;\n      try {\n        const nextOffset = this.pagination.offset + this.pagination.pageSize;\n        const params = this.buildParams(nextOffset);\n        const url = generateUrl(`/apps/intravox/api/file-story/files?${params.toString()}`);\n        const res = await axios.get(url, { signal, validateStatus: s => s >= 200 && s < 300 });\n        const d = res.data || {};\n        const newFiles = d.files || [];\n        this.files = this.files.concat(newFiles);\n        this.warmFederatedPreviews(newFiles);\n\n        // Merge incoming timeline-days + re-sort by date so bucket order is\n        // chronologically consistent regardless of pagination boundaries.\n        if (Array.isArray(d.timeline) && d.timeline.length) {\n          const byDate = new Map();\n          for (const day of this.timeline) byDate.set(day.date, day);\n          for (const day of d.timeline) {\n            if (byDate.has(day.date)) {\n              const existing = byDate.get(day.date);\n              existing.photos = existing.photos.concat(day.photos || []);\n            } else {\n              byDate.set(day.date, day);\n            }\n          }\n          const ord = this.config.sortOrder || 'desc';\n          this.timeline = Array.from(byDate.values()).sort((a, b) => ord === 'desc'\n            ? b.date.localeCompare(a.date)\n            : a.date.localeCompare(b.date));\n        }\n\n        if (d.pagination) {\n          this.pagination = {\n            offset: d.pagination.offset || nextOffset,\n            pageSize: d.pagination.pageSize || this.pagination.pageSize,\n            total: d.pagination.total || this.pagination.total,\n            hasMore: !!d.pagination.hasMore,\n            truncated: !!d.pagination.truncated,\n          };\n        }\n      } catch (err) {\n        if (axios.isCancel?.(err) || err?.name === 'CanceledError' || err?.code === 'ERR_CANCELED') {\n          return;\n        }\n        console.error('[FileStoryWidget] fetchMore failed:', err);\n      } finally {\n        this.loadingMore = false;\n      }\n    },\n    setupScrollSentinel() {\n      if (this._scrollObserver) {\n        this._scrollObserver.disconnect();\n        this._scrollObserver = null;\n      }\n      if (!this.pagination.hasMore || typeof IntersectionObserver === 'undefined') return;\n      const sentinel = this.$refs.scrollSentinel;\n      if (!sentinel) return;\n      this._scrollObserver = new IntersectionObserver((entries) => {\n        for (const e of entries) {\n          if (e.isIntersecting) {\n            this.fetchMore().then(() => {\n              if (!this.pagination.hasMore && this._scrollObserver) {\n                this._scrollObserver.disconnect();\n                this._scrollObserver = null;\n              }\n            });\n            break;\n          }\n        }\n      }, { rootMargin: '600px' });\n      this._scrollObserver.observe(sentinel);\n    },\n    openFile(file) {\n      // Always open in a new tab via NC's /f/<id> handler — it resolves the\n      // right mount per-user (personal storage, GroupFolders in either legacy\n      // shared-storage or per-folder jail mode, federated shares).\n      const url = generateUrl(`/f/${file.file_id}`);\n      window.open(url, '_blank', 'noopener');\n    },\n    formatLongDate(dateStr) {\n      if (!dateStr) return '';\n      const d = new Date(dateStr);\n      if (Number.isNaN(d.getTime())) return dateStr;\n      return d.toLocaleDateString(getCanonicalLocale(), {\n        weekday: 'long',\n        day: 'numeric',\n        month: 'long',\n        year: 'numeric',\n      });\n    },\n    formatLongDateTime(mtimeSec) {\n      if (!mtimeSec) return '';\n      const d = new Date(mtimeSec * 1000);\n      try {\n        return d.toLocaleString(getCanonicalLocale(), { day: 'numeric', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' });\n      } catch (e) { return d.toISOString(); }\n    },\n    formatTime(mtimeSec) {\n      if (!mtimeSec) return '';\n      const d = new Date(mtimeSec * 1000);\n      try {\n        return d.toLocaleTimeString(getCanonicalLocale(), { hour: '2-digit', minute: '2-digit' });\n      } catch (e) { return ''; }\n    },\n    formatSize(bytes) {\n      if (!bytes) return '';\n      const units = ['B', 'KB', 'MB', 'GB'];\n      let i = 0;\n      let v = Number(bytes);\n      while (v >= 1024 && i < units.length - 1) { v /= 1024; i++; }\n      return `${v.toFixed(v >= 10 || i === 0 ? 0 : 1)} ${units[i]}`;\n    },\n    /**\n     * Fire-and-forget warmup of the federated-preview cache. Called after\n     * each fetch/fetchMore so the IntraVox proxy can pre-fetch thumbnails\n     * from the owning NC instance before the user scrolls to each tile.\n     * Caps + dedup live server-side; we just hand over the fileIds.\n     */\n    warmFederatedPreviews(files) {\n      try {\n        if (!Array.isArray(files) || files.length === 0) return;\n        const fids = files\n          .filter(f => f && f.is_federated && f.file_id)\n          .map(f => f.file_id);\n        if (fids.length === 0) return;\n        const url = generateUrl('/apps/intravox/api/preview/warmup');\n        axios.post(url, { file_ids: fids }).catch(() => {});\n      } catch (_) { /* never block render on prewarm */ }\n    },\n    /**\n     * Build the URL for the preview-thumbnail endpoint. Local files go\n     * straight to NC's `/core/preview` (fast, hits NC's own preview cache).\n     * Federated files route through IntraVox's proxy because NC's core\n     * preview pipeline can't reach the remote storage on its own.\n     */\n    previewUrl(file, size = 400) {\n      if (file && file.is_federated) {\n        return generateUrl(`/apps/intravox/api/preview?file_id=${file.file_id}&x=${size}&y=${size}`);\n      }\n      const fid = (file && typeof file === 'object') ? file.file_id : file;\n      return generateUrl(`/core/preview?fileId=${fid}&x=${size}&y=${size}&a=true`);\n    },\n    markPreviewError(fileId) {\n      // Vue 3 reactivity tracks property additions on a plain {} reliably\n      // when the parent object is itself reactive, but a direct\n      // `this.tilePreviewErrors[id] = true` can race with the v-for loop\n      // re-evaluating before the mutation propagates. Reassign the whole\n      // object so the v-if condition re-evaluates deterministically.\n      this.tilePreviewErrors = { ...this.tilePreviewErrors, [fileId]: true };\n    },\n    /**\n     * Uppercase file extension for placeholder rendering when no preview is\n     * available. \"Contract Q4.pdf\" → \"PDF\". Empty for files without extension.\n     */\n    fileExt(name) {\n      if (!name || typeof name !== 'string') return '';\n      const dot = name.lastIndexOf('.');\n      if (dot <= 0 || dot === name.length - 1) return '';\n      return name.slice(dot + 1).toUpperCase().slice(0, 5);\n    },\n    /**\n     * Whether a given column-key is enabled in the widget config. Default:\n     * date is shown, other columns are opt-in.\n     */\n    showColumn(key) {\n      const cols = this.config.visibleColumns;\n      if (!Array.isArray(cols)) {\n        return key === 'date';\n      }\n      return cols.includes(key);\n    },\n    /**\n     * Pick the right timestamp for row display based on user preference.\n     * Falls back gracefully when the preferred timestamp isn't on the row.\n     */\n    rowDate(file) {\n      const pref = this.config.dateField || 'mtime';\n      if (pref === 'created' && file.created_at) return file.created_at;\n      if (pref === 'taken_at' && file.taken_at) return file.taken_at;\n      // mtime is unix seconds; the formatter accepts both ISO strings and\n      // unix-seconds via timestamp branching, so cast to a Date-friendly form.\n      if (file.mtime) {\n        return new Date(file.mtime * 1000).toISOString().slice(0, 10);\n      }\n      return null;\n    },\n  },\n};\n</script>\n\n<style scoped>\n.file-story-widget {\n  width: 100%;\n  margin: 12px 0;\n}\n\n.fs-loading, .fs-error, .fs-empty {\n  display: flex;\n  align-items: center;\n  justify-content: center;\n  flex-direction: column;\n  gap: 8px;\n  padding: 32px 16px;\n  color: var(--fs-text-muted, var(--color-text-maxcontrast));\n  font-size: 13px;\n  background: var(--color-background-hover);\n  border-radius: var(--border-radius-large);\n}\n\n.fs-empty-hint {\n  display: block;\n  max-width: 480px;\n  margin-top: 8px;\n  line-height: 1.4;\n  text-align: center;\n}\n\n.fs-skeleton-row {\n  width: 100%;\n  height: 48px;\n  background: var(--color-background-dark);\n  border-radius: var(--border-radius);\n  margin-bottom: 4px;\n  animation: fs-pulse 1.4s ease-in-out infinite;\n}\n\n@keyframes fs-pulse {\n  0%, 100% { opacity: 0.55; }\n  50% { opacity: 0.85; }\n}\n\n.fs-day, .fs-group {\n  margin-bottom: 16px;\n}\n\n/* Transparent date headers — inherit the row/widget background instead of\n * forcing a white block over a colored row. The hairline border separates\n * sections visually without an opaque band. */\n.fs-day-header, .fs-group-header {\n  display: flex;\n  align-items: baseline;\n  gap: 10px;\n  padding: 10px 4px 4px;\n  margin-top: 4px;\n  border-bottom: 1px solid var(--color-border);\n  background: transparent;\n}\n\n.fs-day-date, .fs-group-label {\n  margin: 0;\n  font-size: 14px;\n  font-weight: 600;\n  color: var(--fs-text, var(--color-main-text));\n  /* Slightly muted weight in the header so it doesn't compete with filenames\n   * but stays readable in both light and dark themes via --color-main-text. */\n  letter-spacing: 0.01em;\n}\n\n.fs-day-count, .fs-group-count {\n  font-size: 11px;\n  color: var(--fs-text-muted, var(--color-text-maxcontrast));\n  /* color-mix tint of the accent color — adapts to theming, no fixed values.\n   * Falls back to background-hover if color-mix is not supported (NC32+ targets\n   * Chromium 111+ / Firefox 113+ / Safari 16.2+ which all support it). */\n  background: color-mix(in srgb, var(--color-primary-element) 14%, transparent);\n  padding: 2px 8px;\n  border-radius: 999px;\n}\n\n.fs-list {\n  list-style: none;\n  margin: 0;\n  padding: 0;\n}\n\n.fs-row {\n  display: flex;\n  align-items: center;\n  gap: 12px;\n  padding: 8px 8px;\n  border-radius: var(--border-radius);\n  cursor: pointer;\n  transition: background 0.12s ease;\n}\n\n.fs-row:hover, .fs-row:focus {\n  background: var(--color-background-hover);\n  outline: none;\n}\n\n:deep(.fs-row-icon) {\n  width: 32px;\n  height: 32px;\n  flex-shrink: 0;\n  border-radius: 4px;\n}\n\n:deep(.fs-row-icon--placeholder) {\n  display: inline-flex;\n  align-items: center;\n  justify-content: center;\n  font-size: 22px;\n  background: var(--color-background-dark);\n}\n\n.fs-row-main {\n  flex: 1;\n  min-width: 0;\n}\n\n.fs-row-name {\n  font-size: 14px;\n  color: var(--fs-text, var(--color-main-text));\n  white-space: nowrap;\n  overflow: hidden;\n  text-overflow: ellipsis;\n  display: flex;\n  align-items: center;\n  gap: 6px;\n}\n\n/* Subtle cloud-badge next to filenames that live in federated shares.\n   Communicates \"this came from another Nextcloud — MetaVox metadata not\n   reachable\" without breaking the file-rendering layout. */\n:deep(.fs-federated-badge) {\n  display: inline-flex;\n  align-items: center;\n  flex-shrink: 0;\n  color: var(--fs-text-muted, var(--color-text-maxcontrast));\n  opacity: 0.7;\n}\n\n:deep(.fs-federated-badge:hover) {\n  opacity: 1;\n}\n\n.fs-row-meta {\n  display: flex;\n  flex-wrap: wrap;\n  gap: 8px;\n  font-size: 11px;\n  color: var(--fs-text-muted, var(--color-text-maxcontrast));\n  margin-top: 2px;\n}\n\n/* Tiles mode: grid of preview thumbnails. Responsive via auto-fill so tile\n   counts adjust to the column width — same masonry feel as a photo gallery\n   but with document covers instead of photos. */\n.fs-tiles {\n  display: grid;\n  /* Driven by tilesGridStyle() — falls back to medium when not set. */\n  grid-template-columns: repeat(auto-fill, minmax(var(--fs-tile-min, 180px), 1fr));\n  gap: var(--fs-tile-gap, 14px);\n}\n\n.fs-tile {\n  display: flex;\n  flex-direction: column;\n  border: 1px solid var(--color-border);\n  border-radius: var(--border-radius-large);\n  background: var(--color-main-background);\n  cursor: pointer;\n  overflow: hidden;\n  transition: box-shadow 0.12s ease, transform 0.12s ease;\n}\n\n.fs-tile:hover, .fs-tile:focus {\n  box-shadow: 0 4px 14px rgba(0, 0, 0, 0.12);\n  transform: translateY(-1px);\n  outline: none;\n}\n\n.fs-tile-preview {\n  position: relative;\n  width: 100%;\n  aspect-ratio: 3 / 4;          /* A4-ish portrait — most docs */\n  background: var(--color-background-dark);\n  overflow: hidden;\n}\n\n/* Img sits on top of the fallback; when NC returns 404 the img is unloaded\n   and the fallback (mime-icon + extension) shows through. */\n.fs-tile-img {\n  position: absolute;\n  inset: 0;\n  width: 100%;\n  height: 100%;\n  object-fit: cover;\n  display: block;\n  z-index: 1;\n}\n\n.fs-tile-fallback {\n  position: absolute;\n  inset: 0;\n  display: flex;\n  flex-direction: column;\n  align-items: center;\n  justify-content: center;\n  gap: 8px;\n  color: var(--fs-text-muted, var(--color-text-maxcontrast));\n  z-index: 0;\n}\n\n.fs-tile-ext {\n  font-size: 12px;\n  font-weight: 600;\n  letter-spacing: 0.05em;\n}\n\n.fs-tile-body {\n  padding: 8px 10px 10px;\n  display: flex;\n  flex-direction: column;\n  gap: 4px;\n}\n\n.fs-tile-name {\n  font-size: 13px;\n  font-weight: 500;\n  color: var(--fs-text, var(--color-main-text));\n  white-space: nowrap;\n  overflow: hidden;\n  text-overflow: ellipsis;\n  display: flex;\n  align-items: center;\n  gap: 6px;\n}\n\n.fs-tile-meta {\n  display: flex;\n  flex-wrap: wrap;\n  gap: 6px;\n  font-size: 11px;\n  color: var(--fs-text-muted, var(--color-text-maxcontrast));\n}\n\n.fs-scroll-sentinel {\n  display: flex;\n  justify-content: center;\n  align-items: center;\n  min-height: 48px;\n  padding: 12px 0;\n}\n\n.fs-loading-more {\n  font-size: 12px;\n  color: var(--fs-text-muted, var(--color-text-maxcontrast));\n}\n\n.fs-truncated {\n  margin: 12px 0;\n  padding: 8px 12px;\n  background: var(--color-warning-background);\n  border: 1px solid var(--color-warning);\n  border-radius: 6px;\n  font-size: 12px;\n  color: var(--fs-text-muted, var(--color-text-maxcontrast));\n}\n\n@media (prefers-reduced-motion: reduce) {\n  .fs-skeleton-row {\n    animation: none;\n  }\n}\n\n/* When the row background is dark (e.g. --color-primary-element), the widget\n   sits on a saturated surface. Lift muted text, soften hover/border, and keep\n   skeletons/tile-cards readable against the colored backdrop. */\n.file-story-widget.fs--on-dark .fs-day-count,\n.file-story-widget.fs--on-dark .fs-group-count {\n  background: rgba(255, 255, 255, 0.18);\n  color: var(--fs-text, #fff);\n}\n\n.file-story-widget.fs--on-dark .fs-row:hover,\n.file-story-widget.fs--on-dark .fs-row:focus {\n  background: rgba(255, 255, 255, 0.12);\n}\n\n.file-story-widget.fs--on-dark .fs-day-header,\n.file-story-widget.fs--on-dark .fs-group-header {\n  border-bottom-color: rgba(255, 255, 255, 0.25);\n}\n\n.file-story-widget.fs--on-dark .fs-row-meta,\n.file-story-widget.fs--on-dark :deep(.fs-federated-badge) {\n  opacity: 0.82;\n}\n\n.file-story-widget.fs--on-dark .fs-skeleton-row {\n  background: rgba(255, 255, 255, 0.12);\n}\n\n/* Tiles on a dark row: tinted-glass card so the white tile stops \"cutting a\n   hole\" in the colored background. Filename stays white (--fs-text), reads on\n   the translucent backdrop because there's enough contrast against both the\n   row-bg and the soft white tint. */\n.file-story-widget.fs--on-dark .fs-tile {\n  background: rgba(255, 255, 255, 0.14);\n  border-color: rgba(255, 255, 255, 0.22);\n}\n\n.file-story-widget.fs--on-dark .fs-tile:hover,\n.file-story-widget.fs--on-dark .fs-tile:focus {\n  background: rgba(255, 255, 255, 0.2);\n  box-shadow: 0 4px 14px rgba(0, 0, 0, 0.25);\n}\n\n.file-story-widget.fs--on-dark .fs-tile-preview {\n  background: rgba(255, 255, 255, 0.08);\n}\n\n/* Mime-icon in the placeholder uses currentColor for the SVG paths via NC's\n   icon system — lifting opacity is enough to keep the icon visible on the\n   tinted tile surface without a stark grey square. */\n.file-story-widget.fs--on-dark :deep(.fs-row-icon--placeholder),\n.file-story-widget.fs--on-dark .fs-tile-fallback {\n  background: transparent;\n  color: var(--fs-text, #fff);\n  opacity: 0.88;\n}\n</style>\n"],"sourceRoot":""}]);
// Exports
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (___CSS_LOADER_EXPORT___);


/***/ },

/***/ "./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/dist/stylePostLoader.js!./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/FileStoryWidget.vue?vue&type=style&index=0&id=b4593806&scoped=true&lang=css"
/*!********************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/dist/stylePostLoader.js!./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/FileStoryWidget.vue?vue&type=style&index=0&id=b4593806&scoped=true&lang=css ***!
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
/* harmony import */ var _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_dist_stylePostLoader_js_node_modules_vue_loader_dist_index_js_ruleSet_1_rules_5_use_0_FileStoryWidget_vue_vue_type_style_index_0_id_b4593806_scoped_true_lang_css__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! !!../../node_modules/css-loader/dist/cjs.js!../../node_modules/vue-loader/dist/stylePostLoader.js!../../node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./FileStoryWidget.vue?vue&type=style&index=0&id=b4593806&scoped=true&lang=css */ "./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/dist/stylePostLoader.js!./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/FileStoryWidget.vue?vue&type=style&index=0&id=b4593806&scoped=true&lang=css");

      
      
      
      
      
      
      
      
      

var options = {};

options.styleTagTransform = (_node_modules_style_loader_dist_runtime_styleTagTransform_js__WEBPACK_IMPORTED_MODULE_5___default());
options.setAttributes = (_node_modules_style_loader_dist_runtime_setAttributesWithoutAttributes_js__WEBPACK_IMPORTED_MODULE_3___default());

      options.insert = _node_modules_style_loader_dist_runtime_insertBySelector_js__WEBPACK_IMPORTED_MODULE_2___default().bind(null, "head");
    
options.domAPI = (_node_modules_style_loader_dist_runtime_styleDomAPI_js__WEBPACK_IMPORTED_MODULE_1___default());
options.insertStyleElement = (_node_modules_style_loader_dist_runtime_insertStyleElement_js__WEBPACK_IMPORTED_MODULE_4___default());

var update = _node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0___default()(_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_dist_stylePostLoader_js_node_modules_vue_loader_dist_index_js_ruleSet_1_rules_5_use_0_FileStoryWidget_vue_vue_type_style_index_0_id_b4593806_scoped_true_lang_css__WEBPACK_IMPORTED_MODULE_6__["default"], options);




       /* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_dist_stylePostLoader_js_node_modules_vue_loader_dist_index_js_ruleSet_1_rules_5_use_0_FileStoryWidget_vue_vue_type_style_index_0_id_b4593806_scoped_true_lang_css__WEBPACK_IMPORTED_MODULE_6__["default"] && _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_dist_stylePostLoader_js_node_modules_vue_loader_dist_index_js_ruleSet_1_rules_5_use_0_FileStoryWidget_vue_vue_type_style_index_0_id_b4593806_scoped_true_lang_css__WEBPACK_IMPORTED_MODULE_6__["default"].locals ? _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_dist_stylePostLoader_js_node_modules_vue_loader_dist_index_js_ruleSet_1_rules_5_use_0_FileStoryWidget_vue_vue_type_style_index_0_id_b4593806_scoped_true_lang_css__WEBPACK_IMPORTED_MODULE_6__["default"].locals : undefined);


/***/ },

/***/ "./src/components/FileStoryWidget.vue"
/*!********************************************!*\
  !*** ./src/components/FileStoryWidget.vue ***!
  \********************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _FileStoryWidget_vue_vue_type_template_id_b4593806_scoped_true__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./FileStoryWidget.vue?vue&type=template&id=b4593806&scoped=true */ "./src/components/FileStoryWidget.vue?vue&type=template&id=b4593806&scoped=true");
/* harmony import */ var _FileStoryWidget_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./FileStoryWidget.vue?vue&type=script&lang=js */ "./src/components/FileStoryWidget.vue?vue&type=script&lang=js");
/* harmony import */ var _FileStoryWidget_vue_vue_type_style_index_0_id_b4593806_scoped_true_lang_css__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./FileStoryWidget.vue?vue&type=style&index=0&id=b4593806&scoped=true&lang=css */ "./src/components/FileStoryWidget.vue?vue&type=style&index=0&id=b4593806&scoped=true&lang=css");
/* harmony import */ var _node_modules_vue_loader_dist_exportHelper_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ../../node_modules/vue-loader/dist/exportHelper.js */ "./node_modules/vue-loader/dist/exportHelper.js");




;


const __exports__ = /*#__PURE__*/(0,_node_modules_vue_loader_dist_exportHelper_js__WEBPACK_IMPORTED_MODULE_3__["default"])(_FileStoryWidget_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_1__["default"], [['render',_FileStoryWidget_vue_vue_type_template_id_b4593806_scoped_true__WEBPACK_IMPORTED_MODULE_0__.render],['__scopeId',"data-v-b4593806"],['__file',"src/components/FileStoryWidget.vue"]])
/* hot reload */
if (false) // removed by dead control flow
{}


/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (__exports__);

/***/ },

/***/ "./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/FileStoryWidget.vue?vue&type=script&lang=js"
/*!****************************************************************************************************************************************!*\
  !*** ./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/FileStoryWidget.vue?vue&type=script&lang=js ***!
  \****************************************************************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var vue__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! vue */ "./node_modules/vue/dist/vue.runtime.esm-bundler.js");
/* harmony import */ var _nextcloud_axios__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @nextcloud/axios */ "./node_modules/@nextcloud/axios/dist/index.js");
/* harmony import */ var _nextcloud_router__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @nextcloud/router */ "./node_modules/@nextcloud/router/dist/index.mjs");
/* harmony import */ var _nextcloud_l10n__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! @nextcloud/l10n */ "./node_modules/@nextcloud/l10n/dist/index.mjs");
/* harmony import */ var _nextcloud_vue__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! @nextcloud/vue */ "./node_modules/@nextcloud/vue/dist/index.mjs");
/* harmony import */ var vue_material_design_icons_AlertCircle_vue__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! vue-material-design-icons/AlertCircle.vue */ "./node_modules/vue-material-design-icons/AlertCircle.vue");
/* harmony import */ var vue_material_design_icons_FileDocumentOutline_vue__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! vue-material-design-icons/FileDocumentOutline.vue */ "./node_modules/vue-material-design-icons/FileDocumentOutline.vue");
/* harmony import */ var vue_material_design_icons_CloudOutline_vue__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! vue-material-design-icons/CloudOutline.vue */ "./node_modules/vue-material-design-icons/CloudOutline.vue");
/* harmony import */ var vue_material_design_icons_LockOutline_vue__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__(/*! vue-material-design-icons/LockOutline.vue */ "./node_modules/vue-material-design-icons/LockOutline.vue");











// FileTypeIcon renders a small mime-icon. Uses NC's `OC.MimeType.getIconUrl()`
// when available (returns a path to NC's built-in icon SVG), falls back to a
// neutral document icon otherwise.
const FileTypeIcon = {
  name: 'FileTypeIcon',
  props: { mime: { type: String, default: '' } },
  setup(props) {
    return () => {
      let iconUrl = null;
      try {
        if (typeof window !== 'undefined' && window.OC && window.OC.MimeType && typeof window.OC.MimeType.getIconUrl === 'function') {
          iconUrl = window.OC.MimeType.getIconUrl(props.mime || 'application/octet-stream');
        }
      } catch (e) { /* ignore */ }
      if (iconUrl) {
        return (0,vue__WEBPACK_IMPORTED_MODULE_0__.h)('img', { src: iconUrl, class: 'fs-row-icon', alt: '', loading: 'lazy', width: 32, height: 32 });
      }
      // Fallback: plain neutral document icon
      return (0,vue__WEBPACK_IMPORTED_MODULE_0__.h)('span', { class: 'fs-row-icon fs-row-icon--placeholder' }, '📄');
    };
  },
};

/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = ({
  name: 'FileStoryWidget',
  components: { AlertCircle: vue_material_design_icons_AlertCircle_vue__WEBPACK_IMPORTED_MODULE_5__["default"], FileDocumentOutline: vue_material_design_icons_FileDocumentOutline_vue__WEBPACK_IMPORTED_MODULE_6__["default"], CloudOutline: vue_material_design_icons_CloudOutline_vue__WEBPACK_IMPORTED_MODULE_7__["default"], LockOutline: vue_material_design_icons_LockOutline_vue__WEBPACK_IMPORTED_MODULE_8__["default"], FileTypeIcon, NcButton: _nextcloud_vue__WEBPACK_IMPORTED_MODULE_4__.NcButton },
  props: {
    widget: { type: Object, required: true },
    rowBackgroundColor: { type: String, default: '' },
  },
  data() {
    return {
      files: [],
      timeline: [],
      groups: [],
      capabilities: null,
      loading: true,
      error: null,
      // Set to 'folder_not_accessible' when the backend returns a 404 with
      // that reason — used by emptyMessage()/showScanHint() to surface a
      // permission-aware empty-state instead of the generic "no documents".
      accessReason: null,
      pagination: { offset: 0, pageSize: 100, total: 0, hasMore: false, truncated: false },
      loadingMore: false,
      _scrollObserver: null,
      _abortController: null,
      _lastEtag: null,
      // Map file_id → true when its NC preview-thumbnail 404'd. Used in tiles
      // mode to fall back to a mime-icon without flashing a broken image.
      tilePreviewErrors: {},
    };
  },
  computed: {
    config() { return this.widget.config || {}; },
    rowBgClass() {
      const bg = String(this.rowBackgroundColor || '').trim();
      if (!bg || bg === 'transparent') {
        return '';
      }
      const dark = new Set([
        'var(--color-primary-element)',
        'var(--color-primary)',
        'var(--color-error)',
        'var(--color-success)',
        'var(--color-warning)',
      ]);
      if (dark.has(bg)) {
        return 'fs--on-dark';
      }
      return 'fs--on-tinted';
    },
    rowBgStyle() {
      const bg = String(this.rowBackgroundColor || '').trim();
      if (this.rowBgClass !== 'fs--on-dark') {
        return {};
      }
      // Paired text color for the row background, matches Widget.vue's WCAG map.
      const textMap = {
        'var(--color-primary-element)': 'var(--color-primary-element-text)',
        'var(--color-primary)': 'var(--color-primary-text)',
        'var(--color-error)': 'var(--color-error-text)',
        'var(--color-success)': 'var(--color-success-text)',
        'var(--color-warning)': 'var(--color-warning-text)',
      };
      const text = textMap[bg] || '#fff';
      return {
        '--fs-text': text,
        '--fs-text-muted': text,
      };
    },
    effectiveMode() {
      const allowed = ['timeline', 'tiles', 'list', 'grouped'];
      return allowed.includes(this.config.mode) ? this.config.mode : 'timeline';
    },
    timelineDays() {
      return this.timeline || [];
    },
    /**
     * Context-aware empty-state message. Distinguishes "folder not set yet"
     * (user just dropped in the widget) from "filter matches nothing" (user
     * tuned filters too narrow) from generic "folder is empty".
     */
    emptyMessage() {
      if (!this.config.folderPath) {
        return (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_3__.translate)('intravox', 'No folder selected');
      }
      if (this.accessReason === 'folder_not_accessible') {
        return (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_3__.translate)('intravox', 'You do not have access to this folder');
      }
      const hasFilters = Array.isArray(this.config.metaVoxFilters) && this.config.metaVoxFilters.length > 0;
      if (hasFilters) {
        return (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_3__.translate)('intravox', 'No documents match the current filters');
      }
      return (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_3__.translate)('intravox', 'No documents to show.');
    },
    /**
     * Only show the file-index hint when the user picked a normal folder and
     * got zero results — not when filters are active (more likely cause: no
     * match) and not when the user lacks access (occ files:scan won't help).
     */
    showScanHint() {
      if (!this.config.folderPath) return false;
      if (this.accessReason === 'folder_not_accessible') return false;
      const hasFilters = Array.isArray(this.config.metaVoxFilters) && this.config.metaVoxFilters.length > 0;
      return !hasFilters;
    },
    fetchKey() {
      const c = this.config || {};
      return JSON.stringify({
        f: c.folderPath || '',
        m: c.mode || 'timeline',
        gb: c.groupBy || '',
        gr: c.granularity || 'day',
        l: c.limit ?? null,
        s: c.sortOrder || 'desc',
        sb: c.sortBy || 'mtime',
        flt: Array.isArray(c.metaVoxFilters) ? c.metaVoxFilters : [],
      });
    },
    /**
     * CSS custom properties that drive the tile grid sizing. Discrete steps
     * because pixel-precise control is rarely useful and a 3-button picker
     * keeps the editor UI compact.
     */
    tilesGridStyle() {
      const size = this.config.tileSize || 'medium';
      switch (size) {
        case 'small':
          return { '--fs-tile-min': '120px', '--fs-tile-gap': '10px' };
        case 'large':
          return { '--fs-tile-min': '260px', '--fs-tile-gap': '18px' };
        case 'medium':
        default:
          return { '--fs-tile-min': '180px', '--fs-tile-gap': '14px' };
      }
    },
  },
  watch: {
    fetchKey() {
      // 700 ms debounce so quick edit-mode mutations (folder pick + mode flip
      // + sort change in rapid succession) don't stack four heavy filecache
      // queries on the Apache worker pool and cause 503s on the subsequent
      // page save. Each in-flight fetch holds a worker until its SQL returns.
      clearTimeout(this._debounce);
      this._debounce = setTimeout(() => this.fetch(), 700);
    },
  },
  mounted() {
    if (typeof requestIdleCallback === 'function') {
      this._idleHandle = requestIdleCallback(() => this.fetch());
    } else {
      this.fetch();
    }
  },
  beforeUnmount() {
    clearTimeout(this._debounce);
    if (this._idleHandle && typeof cancelIdleCallback === 'function') {
      cancelIdleCallback(this._idleHandle);
    }
    if (this._scrollObserver) {
      this._scrollObserver.disconnect();
      this._scrollObserver = null;
    }
    if (this._abortController) {
      this._abortController.abort();
      this._abortController = null;
    }
  },
  methods: {
    t(key, vars = {}) {
      return (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_3__.translate)('intravox', key, vars);
    },
    async fetch() {
      if (!this.config.folderPath) {
        this.files = [];
        this.timeline = [];
        this.groups = [];
        this.loading = false;
        return;
      }
      // Cancel any pending fetch/fetchMore so we don't race a stale response
      // overwriting fresh data after a rapid config change.
      if (this._abortController) {
        this._abortController.abort();
      }
      this._abortController = new AbortController();
      const signal = this._abortController.signal;

      this.pagination = { offset: 0, pageSize: 100, total: 0, hasMore: false, truncated: false };
      this.loading = true;
      this.error = null;
      this.accessReason = null;
      try {
        const params = this.buildParams(0);
        const url = (0,_nextcloud_router__WEBPACK_IMPORTED_MODULE_2__.generateUrl)(`/apps/intravox/api/file-story/files?${params.toString()}`);
        const headers = {};
        if (this._lastEtag) headers['If-None-Match'] = this._lastEtag;
        const res = await _nextcloud_axios__WEBPACK_IMPORTED_MODULE_1__["default"].get(url, { headers, signal, validateStatus: s => (s >= 200 && s < 300) || s === 304 || s === 404 });
        if (res.status === 304) {
          this.loading = false;
          return;
        }
        if (res.status === 404) {
          this.files = [];
          this.timeline = [];
          this.groups = [];
          this.capabilities = null;
          this.accessReason = res.data?.reason || null;
          this.loading = false;
          return;
        }
        const d = res.data || {};
        this.files = d.files || [];
        this.timeline = d.timeline || [];
        this.groups = d.groups || [];
        this.capabilities = d.capabilities || null;
        this.warmFederatedPreviews(this.files);
        if (d.pagination) {
          this.pagination = {
            offset: d.pagination.offset || 0,
            pageSize: d.pagination.pageSize || 100,
            total: d.pagination.total || this.files.length,
            hasMore: !!d.pagination.hasMore,
            truncated: !!d.pagination.truncated,
          };
        }
        if (res.headers && res.headers.etag) this._lastEtag = res.headers.etag;
        this.$nextTick(() => this.setupScrollSentinel());
      } catch (err) {
        if (_nextcloud_axios__WEBPACK_IMPORTED_MODULE_1__["default"].isCancel?.(err) || err?.name === 'CanceledError' || err?.code === 'ERR_CANCELED') {
          return; // superseded by newer request
        }
        console.error('[FileStoryWidget] fetch failed:', err);
        this.error = err.response?.data?.error || err.message || this.t('Failed to load documents');
      } finally {
        this.loading = false;
      }
    },
    buildParams(offset) {
      const params = new URLSearchParams({ mode: this.effectiveMode });
      const c = this.config || {};
      if (c.folderPath) params.append('folder', c.folderPath);
      if (Array.isArray(c.metaVoxFilters) && c.metaVoxFilters.length) {
        params.append('filters', JSON.stringify(c.metaVoxFilters));
      }
      if (this.effectiveMode === 'grouped' && c.groupBy) {
        params.append('groupBy', String(c.groupBy));
      }
      if (this.effectiveMode === 'timeline' && c.granularity) {
        params.append('granularity', String(c.granularity));
      }
      if (c.limit) params.append('limit', String(c.limit));
      params.append('offset', String(offset));
      params.append('pageSize', String(this.pagination.pageSize || 100));
      params.append('sortOrder', c.sortOrder || 'desc');
      params.append('sortBy', c.sortBy || 'mtime');
      if (offset > 0 && this.pagination.total > 0) {
        params.append('total', String(this.pagination.total));
      }
      return params;
    },
    async fetchMore() {
      if (this.loadingMore || !this.pagination.hasMore) return;
      this.loadingMore = true;
      const signal = this._abortController?.signal;
      try {
        const nextOffset = this.pagination.offset + this.pagination.pageSize;
        const params = this.buildParams(nextOffset);
        const url = (0,_nextcloud_router__WEBPACK_IMPORTED_MODULE_2__.generateUrl)(`/apps/intravox/api/file-story/files?${params.toString()}`);
        const res = await _nextcloud_axios__WEBPACK_IMPORTED_MODULE_1__["default"].get(url, { signal, validateStatus: s => s >= 200 && s < 300 });
        const d = res.data || {};
        const newFiles = d.files || [];
        this.files = this.files.concat(newFiles);
        this.warmFederatedPreviews(newFiles);

        // Merge incoming timeline-days + re-sort by date so bucket order is
        // chronologically consistent regardless of pagination boundaries.
        if (Array.isArray(d.timeline) && d.timeline.length) {
          const byDate = new Map();
          for (const day of this.timeline) byDate.set(day.date, day);
          for (const day of d.timeline) {
            if (byDate.has(day.date)) {
              const existing = byDate.get(day.date);
              existing.photos = existing.photos.concat(day.photos || []);
            } else {
              byDate.set(day.date, day);
            }
          }
          const ord = this.config.sortOrder || 'desc';
          this.timeline = Array.from(byDate.values()).sort((a, b) => ord === 'desc'
            ? b.date.localeCompare(a.date)
            : a.date.localeCompare(b.date));
        }

        if (d.pagination) {
          this.pagination = {
            offset: d.pagination.offset || nextOffset,
            pageSize: d.pagination.pageSize || this.pagination.pageSize,
            total: d.pagination.total || this.pagination.total,
            hasMore: !!d.pagination.hasMore,
            truncated: !!d.pagination.truncated,
          };
        }
      } catch (err) {
        if (_nextcloud_axios__WEBPACK_IMPORTED_MODULE_1__["default"].isCancel?.(err) || err?.name === 'CanceledError' || err?.code === 'ERR_CANCELED') {
          return;
        }
        console.error('[FileStoryWidget] fetchMore failed:', err);
      } finally {
        this.loadingMore = false;
      }
    },
    setupScrollSentinel() {
      if (this._scrollObserver) {
        this._scrollObserver.disconnect();
        this._scrollObserver = null;
      }
      if (!this.pagination.hasMore || typeof IntersectionObserver === 'undefined') return;
      const sentinel = this.$refs.scrollSentinel;
      if (!sentinel) return;
      this._scrollObserver = new IntersectionObserver((entries) => {
        for (const e of entries) {
          if (e.isIntersecting) {
            this.fetchMore().then(() => {
              if (!this.pagination.hasMore && this._scrollObserver) {
                this._scrollObserver.disconnect();
                this._scrollObserver = null;
              }
            });
            break;
          }
        }
      }, { rootMargin: '600px' });
      this._scrollObserver.observe(sentinel);
    },
    openFile(file) {
      // Always open in a new tab via NC's /f/<id> handler — it resolves the
      // right mount per-user (personal storage, GroupFolders in either legacy
      // shared-storage or per-folder jail mode, federated shares).
      const url = (0,_nextcloud_router__WEBPACK_IMPORTED_MODULE_2__.generateUrl)(`/f/${file.file_id}`);
      window.open(url, '_blank', 'noopener');
    },
    formatLongDate(dateStr) {
      if (!dateStr) return '';
      const d = new Date(dateStr);
      if (Number.isNaN(d.getTime())) return dateStr;
      return d.toLocaleDateString((0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_3__.getCanonicalLocale)(), {
        weekday: 'long',
        day: 'numeric',
        month: 'long',
        year: 'numeric',
      });
    },
    formatLongDateTime(mtimeSec) {
      if (!mtimeSec) return '';
      const d = new Date(mtimeSec * 1000);
      try {
        return d.toLocaleString((0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_3__.getCanonicalLocale)(), { day: 'numeric', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' });
      } catch (e) { return d.toISOString(); }
    },
    formatTime(mtimeSec) {
      if (!mtimeSec) return '';
      const d = new Date(mtimeSec * 1000);
      try {
        return d.toLocaleTimeString((0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_3__.getCanonicalLocale)(), { hour: '2-digit', minute: '2-digit' });
      } catch (e) { return ''; }
    },
    formatSize(bytes) {
      if (!bytes) return '';
      const units = ['B', 'KB', 'MB', 'GB'];
      let i = 0;
      let v = Number(bytes);
      while (v >= 1024 && i < units.length - 1) { v /= 1024; i++; }
      return `${v.toFixed(v >= 10 || i === 0 ? 0 : 1)} ${units[i]}`;
    },
    /**
     * Fire-and-forget warmup of the federated-preview cache. Called after
     * each fetch/fetchMore so the IntraVox proxy can pre-fetch thumbnails
     * from the owning NC instance before the user scrolls to each tile.
     * Caps + dedup live server-side; we just hand over the fileIds.
     */
    warmFederatedPreviews(files) {
      try {
        if (!Array.isArray(files) || files.length === 0) return;
        const fids = files
          .filter(f => f && f.is_federated && f.file_id)
          .map(f => f.file_id);
        if (fids.length === 0) return;
        const url = (0,_nextcloud_router__WEBPACK_IMPORTED_MODULE_2__.generateUrl)('/apps/intravox/api/preview/warmup');
        _nextcloud_axios__WEBPACK_IMPORTED_MODULE_1__["default"].post(url, { file_ids: fids }).catch(() => {});
      } catch (_) { /* never block render on prewarm */ }
    },
    /**
     * Build the URL for the preview-thumbnail endpoint. Local files go
     * straight to NC's `/core/preview` (fast, hits NC's own preview cache).
     * Federated files route through IntraVox's proxy because NC's core
     * preview pipeline can't reach the remote storage on its own.
     */
    previewUrl(file, size = 400) {
      if (file && file.is_federated) {
        return (0,_nextcloud_router__WEBPACK_IMPORTED_MODULE_2__.generateUrl)(`/apps/intravox/api/preview?file_id=${file.file_id}&x=${size}&y=${size}`);
      }
      const fid = (file && typeof file === 'object') ? file.file_id : file;
      return (0,_nextcloud_router__WEBPACK_IMPORTED_MODULE_2__.generateUrl)(`/core/preview?fileId=${fid}&x=${size}&y=${size}&a=true`);
    },
    markPreviewError(fileId) {
      // Vue 3 reactivity tracks property additions on a plain {} reliably
      // when the parent object is itself reactive, but a direct
      // `this.tilePreviewErrors[id] = true` can race with the v-for loop
      // re-evaluating before the mutation propagates. Reassign the whole
      // object so the v-if condition re-evaluates deterministically.
      this.tilePreviewErrors = { ...this.tilePreviewErrors, [fileId]: true };
    },
    /**
     * Uppercase file extension for placeholder rendering when no preview is
     * available. "Contract Q4.pdf" → "PDF". Empty for files without extension.
     */
    fileExt(name) {
      if (!name || typeof name !== 'string') return '';
      const dot = name.lastIndexOf('.');
      if (dot <= 0 || dot === name.length - 1) return '';
      return name.slice(dot + 1).toUpperCase().slice(0, 5);
    },
    /**
     * Whether a given column-key is enabled in the widget config. Default:
     * date is shown, other columns are opt-in.
     */
    showColumn(key) {
      const cols = this.config.visibleColumns;
      if (!Array.isArray(cols)) {
        return key === 'date';
      }
      return cols.includes(key);
    },
    /**
     * Pick the right timestamp for row display based on user preference.
     * Falls back gracefully when the preferred timestamp isn't on the row.
     */
    rowDate(file) {
      const pref = this.config.dateField || 'mtime';
      if (pref === 'created' && file.created_at) return file.created_at;
      if (pref === 'taken_at' && file.taken_at) return file.taken_at;
      // mtime is unix seconds; the formatter accepts both ISO strings and
      // unix-seconds via timestamp branching, so cast to a Date-friendly form.
      if (file.mtime) {
        return new Date(file.mtime * 1000).toISOString().slice(0, 10);
      }
      return null;
    },
  },
});


/***/ },

/***/ "./src/components/FileStoryWidget.vue?vue&type=style&index=0&id=b4593806&scoped=true&lang=css"
/*!****************************************************************************************************!*\
  !*** ./src/components/FileStoryWidget.vue?vue&type=style&index=0&id=b4593806&scoped=true&lang=css ***!
  \****************************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _node_modules_style_loader_dist_cjs_js_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_dist_stylePostLoader_js_node_modules_vue_loader_dist_index_js_ruleSet_1_rules_5_use_0_FileStoryWidget_vue_vue_type_style_index_0_id_b4593806_scoped_true_lang_css__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../node_modules/style-loader/dist/cjs.js!../../node_modules/css-loader/dist/cjs.js!../../node_modules/vue-loader/dist/stylePostLoader.js!../../node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./FileStoryWidget.vue?vue&type=style&index=0&id=b4593806&scoped=true&lang=css */ "./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/dist/stylePostLoader.js!./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/FileStoryWidget.vue?vue&type=style&index=0&id=b4593806&scoped=true&lang=css");


/***/ },

/***/ "./src/components/FileStoryWidget.vue?vue&type=script&lang=js"
/*!********************************************************************!*\
  !*** ./src/components/FileStoryWidget.vue?vue&type=script&lang=js ***!
  \********************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* reexport safe */ _node_modules_vue_loader_dist_index_js_ruleSet_1_rules_5_use_0_FileStoryWidget_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_0__["default"])
/* harmony export */ });
/* harmony import */ var _node_modules_vue_loader_dist_index_js_ruleSet_1_rules_5_use_0_FileStoryWidget_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./FileStoryWidget.vue?vue&type=script&lang=js */ "./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/FileStoryWidget.vue?vue&type=script&lang=js");
 

/***/ },

/***/ "./src/components/FileStoryWidget.vue?vue&type=template&id=b4593806&scoped=true"
/*!**************************************************************************************!*\
  !*** ./src/components/FileStoryWidget.vue?vue&type=template&id=b4593806&scoped=true ***!
  \**************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   render: () => (/* reexport safe */ _node_modules_vue_loader_dist_templateLoader_js_ruleSet_1_rules_2_node_modules_vue_loader_dist_index_js_ruleSet_1_rules_5_use_0_FileStoryWidget_vue_vue_type_template_id_b4593806_scoped_true__WEBPACK_IMPORTED_MODULE_0__.render)
/* harmony export */ });
/* harmony import */ var _node_modules_vue_loader_dist_templateLoader_js_ruleSet_1_rules_2_node_modules_vue_loader_dist_index_js_ruleSet_1_rules_5_use_0_FileStoryWidget_vue_vue_type_template_id_b4593806_scoped_true__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../node_modules/vue-loader/dist/templateLoader.js??ruleSet[1].rules[2]!../../node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./FileStoryWidget.vue?vue&type=template&id=b4593806&scoped=true */ "./node_modules/vue-loader/dist/templateLoader.js??ruleSet[1].rules[2]!./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/FileStoryWidget.vue?vue&type=template&id=b4593806&scoped=true");


/***/ },

/***/ "./node_modules/vue-loader/dist/templateLoader.js??ruleSet[1].rules[2]!./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/FileStoryWidget.vue?vue&type=template&id=b4593806&scoped=true"
/*!********************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/vue-loader/dist/templateLoader.js??ruleSet[1].rules[2]!./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/FileStoryWidget.vue?vue&type=template&id=b4593806&scoped=true ***!
  \********************************************************************************************************************************************************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   render: () => (/* binding */ render)
/* harmony export */ });
/* harmony import */ var vue__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! vue */ "./node_modules/vue/dist/vue.runtime.esm-bundler.js");


const _hoisted_1 = ["aria-label"]
const _hoisted_2 = {
  class: "fs-error",
  role: "alert"
}
const _hoisted_3 = {
  class: "fs-empty fs-empty--locked",
  role: "status"
}
const _hoisted_4 = {
  class: "fs-empty",
  role: "status"
}
const _hoisted_5 = { key: 0 }
const _hoisted_6 = {
  key: 1,
  class: "fs-empty-hint"
}
const _hoisted_7 = { class: "fs-timeline" }
const _hoisted_8 = { class: "fs-day-header" }
const _hoisted_9 = { class: "fs-day-date" }
const _hoisted_10 = { class: "fs-day-count" }
const _hoisted_11 = { class: "fs-list" }
const _hoisted_12 = ["aria-label", "onClick", "onKeydown"]
const _hoisted_13 = { class: "fs-row-main" }
const _hoisted_14 = { class: "fs-row-name" }
const _hoisted_15 = { class: "fs-row-meta" }
const _hoisted_16 = {
  key: 0,
  class: "fs-row-date"
}
const _hoisted_17 = {
  key: 1,
  class: "fs-row-size"
}
const _hoisted_18 = {
  key: 2,
  class: "fs-row-path"
}
const _hoisted_19 = ["aria-label", "onClick", "onKeydown"]
const _hoisted_20 = { class: "fs-tile-preview" }
const _hoisted_21 = { class: "fs-tile-fallback" }
const _hoisted_22 = { class: "fs-tile-ext" }
const _hoisted_23 = ["src", "onError"]
const _hoisted_24 = { class: "fs-tile-body" }
const _hoisted_25 = ["title"]
const _hoisted_26 = { class: "fs-tile-meta" }
const _hoisted_27 = { key: 0 }
const _hoisted_28 = { key: 1 }
const _hoisted_29 = { class: "fs-list-mode" }
const _hoisted_30 = { class: "fs-list" }
const _hoisted_31 = ["aria-label", "onClick", "onKeydown"]
const _hoisted_32 = { class: "fs-row-main" }
const _hoisted_33 = { class: "fs-row-name" }
const _hoisted_34 = { class: "fs-row-meta" }
const _hoisted_35 = { key: 0 }
const _hoisted_36 = { key: 1 }
const _hoisted_37 = { key: 2 }
const _hoisted_38 = { class: "fs-grouped" }
const _hoisted_39 = { class: "fs-group-header" }
const _hoisted_40 = { class: "fs-group-label" }
const _hoisted_41 = { class: "fs-group-count" }
const _hoisted_42 = { class: "fs-list" }
const _hoisted_43 = ["aria-label", "onClick", "onKeydown"]
const _hoisted_44 = { class: "fs-row-main" }
const _hoisted_45 = { class: "fs-row-name" }
const _hoisted_46 = { class: "fs-row-meta" }
const _hoisted_47 = {
  key: 8,
  ref: "scrollSentinel",
  class: "fs-scroll-sentinel"
}
const _hoisted_48 = {
  key: 0,
  class: "fs-loading-more"
}
const _hoisted_49 = {
  key: 9,
  class: "fs-truncated"
}

function render(_ctx, _cache, $props, $setup, $data, $options) {
  const _component_AlertCircle = (0,vue__WEBPACK_IMPORTED_MODULE_0__.resolveComponent)("AlertCircle")
  const _component_NcButton = (0,vue__WEBPACK_IMPORTED_MODULE_0__.resolveComponent)("NcButton")
  const _component_LockOutline = (0,vue__WEBPACK_IMPORTED_MODULE_0__.resolveComponent)("LockOutline")
  const _component_FileDocumentOutline = (0,vue__WEBPACK_IMPORTED_MODULE_0__.resolveComponent)("FileDocumentOutline")
  const _component_FileTypeIcon = (0,vue__WEBPACK_IMPORTED_MODULE_0__.resolveComponent)("FileTypeIcon")
  const _component_CloudOutline = (0,vue__WEBPACK_IMPORTED_MODULE_0__.resolveComponent)("CloudOutline")

  return ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)("div", {
    class: (0,vue__WEBPACK_IMPORTED_MODULE_0__.normalizeClass)(["file-story-widget", $options.rowBgClass]),
    style: (0,vue__WEBPACK_IMPORTED_MODULE_0__.normalizeStyle)($options.rowBgStyle)
  }, [
    (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)(" Loading "),
    ($data.loading)
      ? ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)("div", {
          key: 0,
          class: "fs-loading",
          role: "status",
          "aria-live": "polite",
          "aria-label": $options.t('Loading documents')
        }, [
          ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)(vue__WEBPACK_IMPORTED_MODULE_0__.Fragment, null, (0,vue__WEBPACK_IMPORTED_MODULE_0__.renderList)(5, (i) => {
            return (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("div", {
              key: i,
              class: "fs-skeleton-row",
              "aria-hidden": "true"
            })
          }), 64 /* STABLE_FRAGMENT */))
        ], 8 /* PROPS */, _hoisted_1))
      : ($data.error)
        ? ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)(vue__WEBPACK_IMPORTED_MODULE_0__.Fragment, { key: 1 }, [
            (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)(" Error "),
            (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("div", _hoisted_2, [
              (0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_AlertCircle, {
                size: 20,
                "aria-hidden": "true"
              }),
              (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("span", null, (0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)($data.error), 1 /* TEXT */),
              (0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_NcButton, {
                type: "secondary",
                onClick: _cache[0] || (_cache[0] = $event => ($options.fetch()))
              }, {
                default: (0,vue__WEBPACK_IMPORTED_MODULE_0__.withCtx)(() => [
                  (0,vue__WEBPACK_IMPORTED_MODULE_0__.createTextVNode)((0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)($options.t('Retry')), 1 /* TEXT */)
                ]),
                _: 1 /* STABLE */
              })
            ])
          ], 2112 /* STABLE_FRAGMENT, DEV_ROOT_FRAGMENT */))
        : (!$data.files.length && $data.accessReason === 'folder_not_accessible')
          ? ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)(vue__WEBPACK_IMPORTED_MODULE_0__.Fragment, { key: 2 }, [
              (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)(" No-access: minimal locked placeholder; deliberately hides the folder\n         name to avoid leaking the existence of a path the viewer can't see. "),
              (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("div", _hoisted_3, [
                (0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_LockOutline, {
                  size: 28,
                  "aria-hidden": "true"
                }),
                (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("p", null, (0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)($options.t('You do not have access to this widget')), 1 /* TEXT */)
              ])
            ], 2112 /* STABLE_FRAGMENT, DEV_ROOT_FRAGMENT */))
          : (!$data.files.length)
            ? ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)(vue__WEBPACK_IMPORTED_MODULE_0__.Fragment, { key: 3 }, [
                (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)(" Empty (accessible folder but no documents to show) "),
                (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("div", _hoisted_4, [
                  (0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_FileDocumentOutline, {
                    size: 28,
                    "aria-hidden": "true"
                  }),
                  (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("p", null, (0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)($options.emptyMessage), 1 /* TEXT */),
                  ($options.config.folderPath)
                    ? ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)("small", _hoisted_5, (0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)($options.config.folderPath), 1 /* TEXT */))
                    : (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)("v-if", true),
                  ($options.showScanHint)
                    ? ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)("small", _hoisted_6, (0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)($options.t('If you can see files in the Files app but not here, the file index may be out of sync. Ask an admin to run "occ files:scan" for this folder.')), 1 /* TEXT */))
                    : (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)("v-if", true)
                ])
              ], 2112 /* STABLE_FRAGMENT, DEV_ROOT_FRAGMENT */))
            : ($options.effectiveMode === 'timeline')
              ? ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)(vue__WEBPACK_IMPORTED_MODULE_0__.Fragment, { key: 4 }, [
                  (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)(" Timeline mode "),
                  (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("div", _hoisted_7, [
                    ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(true), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)(vue__WEBPACK_IMPORTED_MODULE_0__.Fragment, null, (0,vue__WEBPACK_IMPORTED_MODULE_0__.renderList)($options.timelineDays, (day) => {
                      return ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)("section", {
                        key: day.date,
                        class: "fs-day"
                      }, [
                        (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("header", _hoisted_8, [
                          (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("h4", _hoisted_9, (0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)(day.label || $options.formatLongDate(day.date)), 1 /* TEXT */),
                          (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("span", _hoisted_10, (0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)(day.photos.length), 1 /* TEXT */)
                        ]),
                        (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("ul", _hoisted_11, [
                          ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(true), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)(vue__WEBPACK_IMPORTED_MODULE_0__.Fragment, null, (0,vue__WEBPACK_IMPORTED_MODULE_0__.renderList)(day.photos, (file) => {
                            return ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)("li", {
                              key: file.file_id,
                              class: "fs-row",
                              tabindex: "0",
                              role: "button",
                              "aria-label": $options.t('Open {name}', { name: file.name }),
                              onClick: $event => ($options.openFile(file)),
                              onKeydown: [
                                (0,vue__WEBPACK_IMPORTED_MODULE_0__.withKeys)($event => ($options.openFile(file)), ["enter"]),
                                (0,vue__WEBPACK_IMPORTED_MODULE_0__.withKeys)((0,vue__WEBPACK_IMPORTED_MODULE_0__.withModifiers)($event => ($options.openFile(file)), ["prevent"]), ["space"])
                              ]
                            }, [
                              (0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_FileTypeIcon, {
                                mime: file.mime
                              }, null, 8 /* PROPS */, ["mime"]),
                              (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("div", _hoisted_13, [
                                (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("div", _hoisted_14, [
                                  (0,vue__WEBPACK_IMPORTED_MODULE_0__.createTextVNode)((0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)(file.name) + " ", 1 /* TEXT */),
                                  (file.is_federated)
                                    ? ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createBlock)(_component_CloudOutline, {
                                        key: 0,
                                        size: 13,
                                        class: "fs-federated-badge",
                                        role: "img",
                                        "aria-label": $options.t('From federated share — MetaVox metadata not available'),
                                        title: $options.t('From federated share — MetaVox metadata not available')
                                      }, null, 8 /* PROPS */, ["aria-label", "title"]))
                                    : (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)("v-if", true)
                                ]),
                                (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("div", _hoisted_15, [
                                  ($options.showColumn('date'))
                                    ? ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)("span", _hoisted_16, (0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)($options.formatTime(file.mtime)), 1 /* TEXT */))
                                    : (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)("v-if", true),
                                  ($options.showColumn('size'))
                                    ? ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)("span", _hoisted_17, (0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)($options.formatSize(file.size)), 1 /* TEXT */))
                                    : (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)("v-if", true),
                                  ($options.showColumn('path'))
                                    ? ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)("span", _hoisted_18, (0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)(file.path), 1 /* TEXT */))
                                    : (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)("v-if", true)
                                ])
                              ])
                            ], 40 /* PROPS, NEED_HYDRATION */, _hoisted_12))
                          }), 128 /* KEYED_FRAGMENT */))
                        ])
                      ]))
                    }), 128 /* KEYED_FRAGMENT */))
                  ])
                ], 2112 /* STABLE_FRAGMENT, DEV_ROOT_FRAGMENT */))
              : ($options.effectiveMode === 'tiles')
                ? ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)(vue__WEBPACK_IMPORTED_MODULE_0__.Fragment, { key: 5 }, [
                    (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)(" Tiles mode — visual grid with preview thumbnails "),
                    (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("div", {
                      class: "fs-tiles",
                      style: (0,vue__WEBPACK_IMPORTED_MODULE_0__.normalizeStyle)($options.tilesGridStyle),
                      role: "list"
                    }, [
                      ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(true), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)(vue__WEBPACK_IMPORTED_MODULE_0__.Fragment, null, (0,vue__WEBPACK_IMPORTED_MODULE_0__.renderList)($data.files, (file) => {
                        return ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)("div", {
                          key: file.file_id,
                          class: "fs-tile",
                          tabindex: "0",
                          role: "button",
                          "aria-label": $options.t('Open {name}', { name: file.name }),
                          onClick: $event => ($options.openFile(file)),
                          onKeydown: [
                            (0,vue__WEBPACK_IMPORTED_MODULE_0__.withKeys)($event => ($options.openFile(file)), ["enter"]),
                            (0,vue__WEBPACK_IMPORTED_MODULE_0__.withKeys)((0,vue__WEBPACK_IMPORTED_MODULE_0__.withModifiers)($event => ($options.openFile(file)), ["prevent"]), ["space"])
                          ]
                        }, [
                          (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("div", _hoisted_20, [
                            (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)(" Always render the mime-icon fallback. When NC has a usable\n               preview, the <img> on top hides it; on 404 the <img> stays\n               unloaded and the fallback shows through. More robust than\n               relying on @error event firing across all browsers. "),
                            (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("div", _hoisted_21, [
                              (0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_FileTypeIcon, {
                                mime: file.mime
                              }, null, 8 /* PROPS */, ["mime"]),
                              (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("span", _hoisted_22, (0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)($options.fileExt(file.name)), 1 /* TEXT */)
                            ]),
                            (!$data.tilePreviewErrors[file.file_id])
                              ? ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)("img", {
                                  key: 0,
                                  src: $options.previewUrl(file, 400),
                                  alt: "",
                                  loading: "lazy",
                                  decoding: "async",
                                  class: "fs-tile-img",
                                  onError: $event => ($options.markPreviewError(file.file_id))
                                }, null, 40 /* PROPS, NEED_HYDRATION */, _hoisted_23))
                              : (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)("v-if", true)
                          ]),
                          (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("div", _hoisted_24, [
                            (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("div", {
                              class: "fs-tile-name",
                              title: file.name
                            }, [
                              (0,vue__WEBPACK_IMPORTED_MODULE_0__.createTextVNode)((0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)(file.name) + " ", 1 /* TEXT */),
                              (file.is_federated)
                                ? ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createBlock)(_component_CloudOutline, {
                                    key: 0,
                                    size: 12,
                                    class: "fs-federated-badge",
                                    role: "img",
                                    "aria-label": $options.t('From federated share — MetaVox metadata not available'),
                                    title: $options.t('From federated share — MetaVox metadata not available')
                                  }, null, 8 /* PROPS */, ["aria-label", "title"]))
                                : (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)("v-if", true)
                            ], 8 /* PROPS */, _hoisted_25),
                            (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("div", _hoisted_26, [
                              ($options.showColumn('date'))
                                ? ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)("span", _hoisted_27, (0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)($options.formatLongDate($options.rowDate(file))), 1 /* TEXT */))
                                : (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)("v-if", true),
                              ($options.showColumn('size'))
                                ? ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)("span", _hoisted_28, (0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)($options.formatSize(file.size)), 1 /* TEXT */))
                                : (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)("v-if", true)
                            ])
                          ])
                        ], 40 /* PROPS, NEED_HYDRATION */, _hoisted_19))
                      }), 128 /* KEYED_FRAGMENT */))
                    ], 4 /* STYLE */)
                  ], 2112 /* STABLE_FRAGMENT, DEV_ROOT_FRAGMENT */))
                : ($options.effectiveMode === 'list')
                  ? ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)(vue__WEBPACK_IMPORTED_MODULE_0__.Fragment, { key: 6 }, [
                      (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)(" List mode (flat) "),
                      (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("div", _hoisted_29, [
                        (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("ul", _hoisted_30, [
                          ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(true), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)(vue__WEBPACK_IMPORTED_MODULE_0__.Fragment, null, (0,vue__WEBPACK_IMPORTED_MODULE_0__.renderList)($data.files, (file) => {
                            return ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)("li", {
                              key: file.file_id,
                              class: "fs-row",
                              tabindex: "0",
                              role: "button",
                              "aria-label": $options.t('Open {name}', { name: file.name }),
                              onClick: $event => ($options.openFile(file)),
                              onKeydown: (0,vue__WEBPACK_IMPORTED_MODULE_0__.withKeys)($event => ($options.openFile(file)), ["enter"])
                            }, [
                              (0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_FileTypeIcon, {
                                mime: file.mime
                              }, null, 8 /* PROPS */, ["mime"]),
                              (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("div", _hoisted_32, [
                                (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("div", _hoisted_33, [
                                  (0,vue__WEBPACK_IMPORTED_MODULE_0__.createTextVNode)((0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)(file.name) + " ", 1 /* TEXT */),
                                  (file.is_federated)
                                    ? ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createBlock)(_component_CloudOutline, {
                                        key: 0,
                                        size: 13,
                                        class: "fs-federated-badge",
                                        role: "img",
                                        "aria-label": $options.t('From federated share — MetaVox metadata not available'),
                                        title: $options.t('From federated share — MetaVox metadata not available')
                                      }, null, 8 /* PROPS */, ["aria-label", "title"]))
                                    : (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)("v-if", true)
                                ]),
                                (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("div", _hoisted_34, [
                                  ($options.showColumn('date'))
                                    ? ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)("span", _hoisted_35, (0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)($options.formatLongDateTime(file.mtime)), 1 /* TEXT */))
                                    : (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)("v-if", true),
                                  ($options.showColumn('size'))
                                    ? ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)("span", _hoisted_36, (0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)($options.formatSize(file.size)), 1 /* TEXT */))
                                    : (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)("v-if", true),
                                  ($options.showColumn('path'))
                                    ? ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)("span", _hoisted_37, (0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)(file.path), 1 /* TEXT */))
                                    : (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)("v-if", true)
                                ])
                              ])
                            ], 40 /* PROPS, NEED_HYDRATION */, _hoisted_31))
                          }), 128 /* KEYED_FRAGMENT */))
                        ])
                      ])
                    ], 2112 /* STABLE_FRAGMENT, DEV_ROOT_FRAGMENT */))
                  : ($options.effectiveMode === 'grouped')
                    ? ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)(vue__WEBPACK_IMPORTED_MODULE_0__.Fragment, { key: 7 }, [
                        (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)(" Grouped mode "),
                        (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("div", _hoisted_38, [
                          ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(true), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)(vue__WEBPACK_IMPORTED_MODULE_0__.Fragment, null, (0,vue__WEBPACK_IMPORTED_MODULE_0__.renderList)($data.groups, (group) => {
                            return ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)("section", {
                              key: group.key,
                              class: "fs-group"
                            }, [
                              (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("header", _hoisted_39, [
                                (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("h4", _hoisted_40, (0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)(group.label), 1 /* TEXT */),
                                (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("span", _hoisted_41, (0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)(group.count), 1 /* TEXT */)
                              ]),
                              (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("ul", _hoisted_42, [
                                ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(true), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)(vue__WEBPACK_IMPORTED_MODULE_0__.Fragment, null, (0,vue__WEBPACK_IMPORTED_MODULE_0__.renderList)(group.files, (file) => {
                                  return ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)("li", {
                                    key: file.file_id,
                                    class: "fs-row",
                                    tabindex: "0",
                                    role: "button",
                                    "aria-label": $options.t('Open {name}', { name: file.name }),
                                    onClick: $event => ($options.openFile(file)),
                                    onKeydown: [
                                      (0,vue__WEBPACK_IMPORTED_MODULE_0__.withKeys)($event => ($options.openFile(file)), ["enter"]),
                                      (0,vue__WEBPACK_IMPORTED_MODULE_0__.withKeys)((0,vue__WEBPACK_IMPORTED_MODULE_0__.withModifiers)($event => ($options.openFile(file)), ["prevent"]), ["space"])
                                    ]
                                  }, [
                                    (0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_FileTypeIcon, {
                                      mime: file.mime
                                    }, null, 8 /* PROPS */, ["mime"]),
                                    (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("div", _hoisted_44, [
                                      (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("div", _hoisted_45, [
                                        (0,vue__WEBPACK_IMPORTED_MODULE_0__.createTextVNode)((0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)(file.name) + " ", 1 /* TEXT */),
                                        (file.is_federated)
                                          ? ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createBlock)(_component_CloudOutline, {
                                              key: 0,
                                              size: 13,
                                              class: "fs-federated-badge",
                                              role: "img",
                                              "aria-label": $options.t('From federated share — MetaVox metadata not available'),
                                              title: $options.t('From federated share — MetaVox metadata not available')
                                            }, null, 8 /* PROPS */, ["aria-label", "title"]))
                                          : (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)("v-if", true)
                                      ]),
                                      (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("div", _hoisted_46, [
                                        (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("span", null, (0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)($options.formatTime(file.mtime)), 1 /* TEXT */),
                                        (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("span", null, (0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)($options.formatSize(file.size)), 1 /* TEXT */)
                                      ])
                                    ])
                                  ], 40 /* PROPS, NEED_HYDRATION */, _hoisted_43))
                                }), 128 /* KEYED_FRAGMENT */))
                              ])
                            ]))
                          }), 128 /* KEYED_FRAGMENT */))
                        ])
                      ], 2112 /* STABLE_FRAGMENT, DEV_ROOT_FRAGMENT */))
                    : (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)("v-if", true),
    (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)(" Infinite-scroll sentinel "),
    ($data.pagination.hasMore)
      ? ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)("div", _hoisted_47, [
          ($data.loadingMore)
            ? ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)("span", _hoisted_48, (0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)($options.t('Loading more…')), 1 /* TEXT */))
            : (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)("v-if", true)
        ], 512 /* NEED_PATCH */))
      : (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)("v-if", true),
    (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)(" Truncated notice "),
    ($data.pagination.truncated)
      ? ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)("div", _hoisted_49, (0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)($options.t('Showing first {n} documents. Use filters or a more specific folder.', { n: String($data.pagination.total) })), 1 /* TEXT */))
      : (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)("v-if", true)
  ], 6 /* CLASS, STYLE */))
}

/***/ }

}]);
//# sourceMappingURL=intravox-src_components_FileStoryWidget_vue.9fa1a15a.js.map