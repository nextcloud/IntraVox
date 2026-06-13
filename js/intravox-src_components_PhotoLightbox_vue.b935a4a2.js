"use strict";
(self["webpackChunknextcloud_intravox"] = self["webpackChunknextcloud_intravox"] || []).push([["src_components_PhotoLightbox_vue"],{

/***/ "./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/dist/stylePostLoader.js!./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/PhotoLightbox.vue?vue&type=style&index=0&id=3d8b81c7&scoped=true&lang=css"
/*!**************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/dist/stylePostLoader.js!./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/PhotoLightbox.vue?vue&type=style&index=0&id=3d8b81c7&scoped=true&lang=css ***!
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
.ps-lb-sr-only[data-v-3d8b81c7] {
  position: absolute;
  width: 1px;
  height: 1px;
  padding: 0;
  margin: -1px;
  overflow: hidden;
  clip: rect(0, 0, 0, 0);
  white-space: nowrap;
  border: 0;
}
.ps-lightbox[data-v-3d8b81c7] {
  position: fixed;
  inset: 0;
  /* Above Nextcloud header (z-index 2000) and most NC overlays. */
  z-index: 100000;
  background: rgba(0, 0, 0, 0.97);
  display: flex;
  flex-direction: column;
  color: #fff;
  outline: none;
}
.ps-lb-topbar[data-v-3d8b81c7] {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 12px 16px;
  /* Respect iOS notch / Android status bar in fullscreen. */
  padding-top: max(12px, env(safe-area-inset-top));
  padding-left: max(16px, env(safe-area-inset-left));
  padding-right: max(16px, env(safe-area-inset-right));
  background: linear-gradient(180deg, rgba(0, 0, 0, 0.75), transparent);
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  z-index: 2;
}
.ps-lb-counter[data-v-3d8b81c7] {
  font-size: 14px;
  font-variant-numeric: tabular-nums;
  opacity: 0.8;
}
.ps-lb-actions[data-v-3d8b81c7] {
  display: flex;
  align-items: center;
  gap: 8px;
}
.ps-lb-icon-btn[data-v-3d8b81c7] {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 40px;
  height: 40px;
  border-radius: 50%;
  border: none;
  background: rgba(255, 255, 255, 0.08);
  color: #fff;
  cursor: pointer;
  transition: background 0.15s;
}
.ps-lb-icon-btn[data-v-3d8b81c7]:hover {
  background: rgba(255, 255, 255, 0.18);
}
.ps-lb-speed[data-v-3d8b81c7] {
  padding: 6px 8px;
  background: rgba(255, 255, 255, 0.1);
  color: #fff;
  border: 1px solid rgba(255, 255, 255, 0.2);
  border-radius: var(--border-radius);
  font-size: 13px;
}
.ps-lb-stage[data-v-3d8b81c7] {
  flex: 1;
  display: flex;
  align-items: center;
  justify-content: center;
  position: relative;
  padding: 60px 60px;
  overflow: hidden;
}
.ps-lb-image-wrap[data-v-3d8b81c7] {
  width: 100%;
  height: 100%;
  display: flex;
  align-items: center;
  justify-content: center;
}
.ps-lb-image[data-v-3d8b81c7] {
  max-width: 95vw;
  max-height: 90vh;
  object-fit: contain;
  display: block;
}

/* Ken Burns effect during slideshow */
.ps-lb-kenburns .ps-lb-image[data-v-3d8b81c7] {
  animation: ps-kenburns-3d8b81c7 4s cubic-bezier(0.22, 0.61, 0.36, 1) forwards;
}
@keyframes ps-kenburns-3d8b81c7 {
from {
    transform: scale(1);
    opacity: 0;
}
10% {
    opacity: 1;
}
to {
    transform: scale(1.1) translate(2%, -2%);
    opacity: 1;
}
}
.ps-lb-nav[data-v-3d8b81c7] {
  position: absolute;
  top: 50%;
  transform: translateY(-50%);
  width: 56px;
  height: 56px;
  border-radius: 50%;
  border: none;
  background: rgba(255, 255, 255, 0.08);
  color: #fff;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: background 0.15s;
}
.ps-lb-nav[data-v-3d8b81c7]:hover:not(:disabled) {
  background: rgba(255, 255, 255, 0.2);
}
.ps-lb-nav[data-v-3d8b81c7]:disabled {
  opacity: 0.25;
  cursor: not-allowed;
}
.ps-lb-nav--prev[data-v-3d8b81c7] {
  left: 12px;
}
.ps-lb-nav--next[data-v-3d8b81c7] {
  right: 12px;
}

/* Date + location pill (always-visible left-bottom).
   Background is opaque enough to stay readable over any photo. */
.ps-lb-pill[data-v-3d8b81c7] {
  position: absolute;
  left: 24px;
  bottom: 24px;
  background: rgba(0, 0, 0, 0.62);
  backdrop-filter: blur(14px) saturate(140%);
  -webkit-backdrop-filter: blur(14px) saturate(140%);
  border: 1px solid rgba(255, 255, 255, 0.08);
  border-radius: 14px;
  padding: 14px 20px;
  color: #fff;
  z-index: 3;
  box-shadow: 0 8px 28px rgba(0, 0, 0, 0.55);
  cursor: pointer;
  transition: background 0.2s ease, transform 0.2s ease;
  max-width: 70vw;
  text-align: left;
  font: inherit;
}
.ps-lb-pill[data-v-3d8b81c7]:disabled {
  cursor: default;
  opacity: 0.9;
}
.ps-lb-pill[data-v-3d8b81c7]:hover:not(:disabled) {
  background: rgba(0, 0, 0, 0.78);
  transform: translateY(-2px);
}
.ps-lb-pill-date[data-v-3d8b81c7] {
  font-size: 18px;
  font-weight: 600;
  letter-spacing: -0.01em;
  line-height: 1.2;
  /* Last-resort fallback if backdrop-filter is unsupported. */
  text-shadow: 0 1px 3px rgba(0, 0, 0, 0.6);
}
.ps-lb-pill-date--fallback[data-v-3d8b81c7] {
  color: rgba(255, 255, 255, 0.85);
  font-weight: 500;
}
.ps-lb-pill-time[data-v-3d8b81c7] {
  font-weight: 400;
  opacity: 0.8;
}
.ps-lb-pill-loc[data-v-3d8b81c7] {
  display: flex;
  align-items: center;
  gap: 4px;
  margin-top: 6px;
  font-size: 14px;
  color: rgba(255, 255, 255, 0.85);
}
.ps-lb-pill-loc span[data-v-3d8b81c7] {
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

/* Mini-map flyout */
.ps-lb-minimap[data-v-3d8b81c7] {
  position: absolute;
  left: 24px;
  bottom: 110px;
  width: 380px;
  max-width: calc(100vw - 48px);
  background: rgba(20, 20, 20, 0.95);
  backdrop-filter: blur(12px);
  -webkit-backdrop-filter: blur(12px);
  border-radius: 14px;
  overflow: hidden;
  z-index: 4;
  box-shadow: 0 10px 40px rgba(0, 0, 0, 0.6);
  display: flex;
  flex-direction: column;
}
.ps-lb-minimap-header[data-v-3d8b81c7] {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 10px 14px;
  color: #fff;
  font-size: 13px;
  border-bottom: 1px solid rgba(255, 255, 255, 0.08);
}
.ps-lb-minimap-frame[data-v-3d8b81c7] {
  width: 100%;
  height: 260px;
  border: 0;
  display: block;
  background: #1a1a1a;
}
.ps-lb-minimap-link[data-v-3d8b81c7] {
  padding: 10px 14px;
  font-size: 12px;
  color: rgba(255, 255, 255, 0.85);
  text-decoration: none;
  border-top: 1px solid rgba(255, 255, 255, 0.08);
}
.ps-lb-minimap-link[data-v-3d8b81c7]:hover {
  color: #fff;
  background: rgba(255, 255, 255, 0.05);
}

/* Details flyout (collapsible) */
.ps-lb-details[data-v-3d8b81c7] {
  position: absolute;
  right: 24px;
  bottom: 24px;
  width: 340px;
  max-width: calc(100vw - 48px);
  background: rgba(20, 20, 20, 0.92);
  backdrop-filter: blur(12px);
  -webkit-backdrop-filter: blur(12px);
  border-radius: 14px;
  padding: 16px 20px;
  color: #fff;
  z-index: 3;
  box-shadow: 0 10px 40px rgba(0, 0, 0, 0.5);
  max-height: 60vh;
  overflow-y: auto;
}
.ps-lb-details-header[data-v-3d8b81c7] {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin: 0 0 12px 0;
}
.ps-lb-details-header h3[data-v-3d8b81c7] {
  margin: 0;
  font-size: 15px;
  font-weight: 600;
}
.ps-lb-info-list[data-v-3d8b81c7] {
  margin: 0;
  display: grid;
  grid-template-columns: max-content 1fr;
  gap: 8px 12px;
  font-size: 13px;
}
.ps-lb-info-list dt[data-v-3d8b81c7] {
  font-weight: 600;
  color: rgba(255, 255, 255, 0.6);
}
.ps-lb-info-list dd[data-v-3d8b81c7] {
  margin: 0;
  word-break: break-word;
}
.ps-lb-info-filename[data-v-3d8b81c7] {
  font-family: var(--font-face, monospace);
  font-size: 12px;
  opacity: 0.85;
}
.ps-lb-filename-link[data-v-3d8b81c7] {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 4px 8px;
  margin: -4px -8px;
  background: transparent;
  border: none;
  border-radius: 6px;
  color: #fff;
  font: inherit;
  text-align: left;
  cursor: pointer;
  word-break: break-all;
  transition: background 0.15s;
}
.ps-lb-filename-link[data-v-3d8b81c7]:hover {
  background: rgba(255, 255, 255, 0.1);
  color: var(--color-primary-element-light, #6bb4ff);
}
.ps-lb-filename-link[data-v-3d8b81c7]:focus-visible {
  outline: 2px solid var(--color-primary-element, #0082c9);
  outline-offset: 1px;
}
.ps-lb-chip[data-v-3d8b81c7] {
  display: inline-block;
  padding: 2px 8px;
  margin: 2px 4px 2px 0;
  background: rgba(255, 255, 255, 0.12);
  border-radius: 999px;
  font-size: 12px;
}
@media (max-width: 600px) {
.ps-lb-stage[data-v-3d8b81c7] {
    padding: 60px 8px;
}
.ps-lb-nav[data-v-3d8b81c7] {
    width: 44px;
    height: 44px;
}
.ps-lb-pill[data-v-3d8b81c7] {
    left: 12px;
    right: 12px;
    bottom: 12px;
    max-width: none;
}
.ps-lb-pill-date[data-v-3d8b81c7] {
    font-size: 16px;
}
.ps-lb-minimap[data-v-3d8b81c7] {
    left: 12px;
    right: 12px;
    bottom: 110px;
    width: auto;
    max-width: none;
}
.ps-lb-details[data-v-3d8b81c7] {
    left: 12px;
    right: 12px;
    bottom: 12px;
    width: auto;
    max-width: none;
    max-height: 50vh;
}
}
@media (prefers-reduced-motion: reduce) {
.ps-lb-kenburns .ps-lb-image[data-v-3d8b81c7] {
    animation: none;
}
.ps-lb-pill[data-v-3d8b81c7] {
    transition: none;
}
}
`, "",{"version":3,"sources":["webpack://./src/components/PhotoLightbox.vue"],"names":[],"mappings":";AAsiBA;EACE,kBAAkB;EAClB,UAAU;EACV,WAAW;EACX,UAAU;EACV,YAAY;EACZ,gBAAgB;EAChB,sBAAsB;EACtB,mBAAmB;EACnB,SAAS;AACX;AAEA;EACE,eAAe;EACf,QAAQ;EACR,gEAAgE;EAChE,eAAe;EACf,+BAA+B;EAC/B,aAAa;EACb,sBAAsB;EACtB,WAAW;EACX,aAAa;AACf;AAEA;EACE,aAAa;EACb,8BAA8B;EAC9B,mBAAmB;EACnB,kBAAkB;EAClB,0DAA0D;EAC1D,gDAAgD;EAChD,kDAAkD;EAClD,oDAAoD;EACpD,qEAAqE;EACrE,kBAAkB;EAClB,MAAM;EACN,OAAO;EACP,QAAQ;EACR,UAAU;AACZ;AAEA;EACE,eAAe;EACf,kCAAkC;EAClC,YAAY;AACd;AAEA;EACE,aAAa;EACb,mBAAmB;EACnB,QAAQ;AACV;AAEA;EACE,oBAAoB;EACpB,mBAAmB;EACnB,uBAAuB;EACvB,WAAW;EACX,YAAY;EACZ,kBAAkB;EAClB,YAAY;EACZ,qCAAqC;EACrC,WAAW;EACX,eAAe;EACf,4BAA4B;AAC9B;AAEA;EACE,qCAAqC;AACvC;AAEA;EACE,gBAAgB;EAChB,oCAAoC;EACpC,WAAW;EACX,0CAA0C;EAC1C,mCAAmC;EACnC,eAAe;AACjB;AAEA;EACE,OAAO;EACP,aAAa;EACb,mBAAmB;EACnB,uBAAuB;EACvB,kBAAkB;EAClB,kBAAkB;EAClB,gBAAgB;AAClB;AAEA;EACE,WAAW;EACX,YAAY;EACZ,aAAa;EACb,mBAAmB;EACnB,uBAAuB;AACzB;AAEA;EACE,eAAe;EACf,gBAAgB;EAChB,mBAAmB;EACnB,cAAc;AAChB;;AAEA,sCAAsC;AACtC;EACE,6EAAoE;AACtE;AAEA;AACE;IACE,mBAAmB;IACnB,UAAU;AACZ;AACA;IACE,UAAU;AACZ;AACA;IACE,wCAAwC;IACxC,UAAU;AACZ;AACF;AAEA;EACE,kBAAkB;EAClB,QAAQ;EACR,2BAA2B;EAC3B,WAAW;EACX,YAAY;EACZ,kBAAkB;EAClB,YAAY;EACZ,qCAAqC;EACrC,WAAW;EACX,eAAe;EACf,aAAa;EACb,mBAAmB;EACnB,uBAAuB;EACvB,4BAA4B;AAC9B;AAEA;EACE,oCAAoC;AACtC;AAEA;EACE,aAAa;EACb,mBAAmB;AACrB;AAEA;EACE,UAAU;AACZ;AAEA;EACE,WAAW;AACb;;AAEA;iEACiE;AACjE;EACE,kBAAkB;EAClB,UAAU;EACV,YAAY;EACZ,+BAA+B;EAC/B,0CAA0C;EAC1C,kDAAkD;EAClD,2CAA2C;EAC3C,mBAAmB;EACnB,kBAAkB;EAClB,WAAW;EACX,UAAU;EACV,0CAA0C;EAC1C,eAAe;EACf,qDAAqD;EACrD,eAAe;EACf,gBAAgB;EAChB,aAAa;AACf;AAEA;EACE,eAAe;EACf,YAAY;AACd;AAEA;EACE,+BAA+B;EAC/B,2BAA2B;AAC7B;AAEA;EACE,eAAe;EACf,gBAAgB;EAChB,uBAAuB;EACvB,gBAAgB;EAChB,4DAA4D;EAC5D,yCAAyC;AAC3C;AAEA;EACE,gCAAgC;EAChC,gBAAgB;AAClB;AAEA;EACE,gBAAgB;EAChB,YAAY;AACd;AAEA;EACE,aAAa;EACb,mBAAmB;EACnB,QAAQ;EACR,eAAe;EACf,eAAe;EACf,gCAAgC;AAClC;AAEA;EACE,mBAAmB;EACnB,gBAAgB;EAChB,uBAAuB;AACzB;;AAEA,oBAAoB;AACpB;EACE,kBAAkB;EAClB,UAAU;EACV,aAAa;EACb,YAAY;EACZ,6BAA6B;EAC7B,kCAAkC;EAClC,2BAA2B;EAC3B,mCAAmC;EACnC,mBAAmB;EACnB,gBAAgB;EAChB,UAAU;EACV,0CAA0C;EAC1C,aAAa;EACb,sBAAsB;AACxB;AAEA;EACE,aAAa;EACb,mBAAmB;EACnB,8BAA8B;EAC9B,kBAAkB;EAClB,WAAW;EACX,eAAe;EACf,kDAAkD;AACpD;AAEA;EACE,WAAW;EACX,aAAa;EACb,SAAS;EACT,cAAc;EACd,mBAAmB;AACrB;AAEA;EACE,kBAAkB;EAClB,eAAe;EACf,gCAAgC;EAChC,qBAAqB;EACrB,+CAA+C;AACjD;AAEA;EACE,WAAW;EACX,qCAAqC;AACvC;;AAEA,iCAAiC;AACjC;EACE,kBAAkB;EAClB,WAAW;EACX,YAAY;EACZ,YAAY;EACZ,6BAA6B;EAC7B,kCAAkC;EAClC,2BAA2B;EAC3B,mCAAmC;EACnC,mBAAmB;EACnB,kBAAkB;EAClB,WAAW;EACX,UAAU;EACV,0CAA0C;EAC1C,gBAAgB;EAChB,gBAAgB;AAClB;AAEA;EACE,aAAa;EACb,mBAAmB;EACnB,8BAA8B;EAC9B,kBAAkB;AACpB;AAEA;EACE,SAAS;EACT,eAAe;EACf,gBAAgB;AAClB;AAEA;EACE,SAAS;EACT,aAAa;EACb,sCAAsC;EACtC,aAAa;EACb,eAAe;AACjB;AAEA;EACE,gBAAgB;EAChB,+BAA+B;AACjC;AAEA;EACE,SAAS;EACT,sBAAsB;AACxB;AAEA;EACE,wCAAwC;EACxC,eAAe;EACf,aAAa;AACf;AAEA;EACE,oBAAoB;EACpB,mBAAmB;EACnB,QAAQ;EACR,gBAAgB;EAChB,iBAAiB;EACjB,uBAAuB;EACvB,YAAY;EACZ,kBAAkB;EAClB,WAAW;EACX,aAAa;EACb,gBAAgB;EAChB,eAAe;EACf,qBAAqB;EACrB,4BAA4B;AAC9B;AAEA;EACE,oCAAoC;EACpC,kDAAkD;AACpD;AAEA;EACE,wDAAwD;EACxD,mBAAmB;AACrB;AAEA;EACE,qBAAqB;EACrB,gBAAgB;EAChB,qBAAqB;EACrB,qCAAqC;EACrC,oBAAoB;EACpB,eAAe;AACjB;AAEA;AACE;IACE,iBAAiB;AACnB;AAEA;IACE,WAAW;IACX,YAAY;AACd;AAEA;IACE,UAAU;IACV,WAAW;IACX,YAAY;IACZ,eAAe;AACjB;AAEA;IACE,eAAe;AACjB;AAEA;IACE,UAAU;IACV,WAAW;IACX,aAAa;IACb,WAAW;IACX,eAAe;AACjB;AAEA;IACE,UAAU;IACV,WAAW;IACX,YAAY;IACZ,WAAW;IACX,eAAe;IACf,gBAAgB;AAClB;AACF;AAEA;AACE;IACE,eAAe;AACjB;AACA;IACE,gBAAgB;AAClB;AACF","sourcesContent":["<template>\n  <Teleport to=\"body\">\n  <div\n    v-if=\"visible\"\n    class=\"ps-lightbox\"\n    role=\"dialog\"\n    aria-modal=\"true\"\n    tabindex=\"-1\"\n    ref=\"root\"\n    @click.self=\"close\"\n    @keydown=\"onKey\"\n    @touchstart=\"onTouchStart\"\n    @touchend=\"onTouchEnd\"\n  >\n    <!-- Top bar -->\n    <header class=\"ps-lb-topbar\">\n      <div class=\"ps-lb-counter\" aria-live=\"polite\" aria-atomic=\"true\">\n        <span class=\"ps-lb-sr-only\">{{ t('Photo') }}</span>\n        {{ index + 1 }} / {{ photos.length }}\n      </div>\n      <div class=\"ps-lb-actions\">\n        <button\n          class=\"ps-lb-icon-btn\"\n          :aria-label=\"slideshowOn ? t('Pause slideshow') : t('Start slideshow')\"\n          :aria-pressed=\"slideshowOn ? 'true' : 'false'\"\n          :title=\"slideshowOn ? t('Pause slideshow') : t('Start slideshow')\"\n          @click=\"toggleSlideshow\"\n        >\n          <Pause v-if=\"slideshowOn\" :size=\"20\" />\n          <Play v-else :size=\"20\" />\n        </button>\n        <label v-if=\"slideshowOn\" class=\"ps-lb-sr-only\" for=\"ps-lb-speed-select\">{{ t('Slideshow speed') }}</label>\n        <select\n          v-if=\"slideshowOn\"\n          id=\"ps-lb-speed-select\"\n          v-model.number=\"speed\"\n          class=\"ps-lb-speed\"\n          :aria-label=\"t('Slideshow speed')\"\n        >\n          <option :value=\"6000\">{{ t('Slow') }}</option>\n          <option :value=\"4000\">{{ t('Normal') }}</option>\n          <option :value=\"2500\">{{ t('Fast') }}</option>\n        </select>\n        <button\n          v-if=\"canOpenInFiles()\"\n          class=\"ps-lb-icon-btn\"\n          :aria-label=\"t('Show in Files')\"\n          :title=\"t('Show in Files')\"\n          @click=\"openInFiles\"\n        >\n          <FolderOpen :size=\"20\" />\n        </button>\n        <button\n          class=\"ps-lb-icon-btn\"\n          :aria-label=\"t('Toggle info panel')\"\n          :aria-pressed=\"infoOpen ? 'true' : 'false'\"\n          :title=\"t('Toggle info panel')\"\n          @click=\"infoOpen = !infoOpen\"\n        >\n          <InformationOutline :size=\"20\" />\n        </button>\n        <button\n          class=\"ps-lb-icon-btn\"\n          :aria-label=\"t('Close lightbox')\"\n          :title=\"t('Close lightbox')\"\n          @click=\"close\"\n        >\n          <Close :size=\"22\" />\n        </button>\n      </div>\n    </header>\n\n    <!-- Main content -->\n    <div class=\"ps-lb-stage\">\n      <button\n        class=\"ps-lb-nav ps-lb-nav--prev\"\n        :disabled=\"photos.length < 2\"\n        :aria-label=\"t('Previous photo')\"\n        :title=\"t('Previous photo')\"\n        @click.stop=\"prev\"\n      >\n        <ChevronLeft :size=\"36\" />\n      </button>\n\n      <div class=\"ps-lb-image-wrap\" :class=\"{ 'ps-lb-kenburns': slideshowOn && !isVideo(currentPhoto) }\">\n        <video\n          v-if=\"currentPhoto && isVideo(currentPhoto)\"\n          :key=\"'v' + currentPhoto.file_id\"\n          :src=\"videoUrl(currentPhoto)\"\n          :poster=\"fullUrl(currentPhoto)\"\n          class=\"ps-lb-image ps-lb-video\"\n          controls\n          autoplay\n          playsinline\n          preload=\"metadata\"\n        />\n        <img\n          v-else-if=\"currentPhoto\"\n          :key=\"currentPhoto.file_id\"\n          :src=\"fullUrl(currentPhoto)\"\n          :alt=\"currentPhoto.location_display || currentPhoto.location || t('Photo {n} of {total}', { n: index + 1, total: photos.length })\"\n          class=\"ps-lb-image\"\n        />\n      </div>\n\n      <button\n        class=\"ps-lb-nav ps-lb-nav--next\"\n        :disabled=\"photos.length < 2\"\n        :aria-label=\"t('Next photo')\"\n        :title=\"t('Next photo')\"\n        @click.stop=\"next\"\n      >\n        <ChevronRight :size=\"36\" />\n      </button>\n    </div>\n\n    <!-- Date + location pill (always visible, left-bottom) -->\n    <button\n      v-if=\"currentPhoto\"\n      type=\"button\"\n      class=\"ps-lb-pill\"\n      :aria-label=\"t('Toggle location map')\"\n      :aria-pressed=\"miniMapOpen ? 'true' : 'false'\"\n      :disabled=\"!currentPhoto.gps\"\n      @click.stop=\"onPillClick\"\n    >\n      <div class=\"ps-lb-pill-date\" v-if=\"currentPhoto.taken_at\">\n        {{ formatLongDate(currentPhoto.taken_at) }}\n        <span class=\"ps-lb-pill-time\">· {{ formatTime(currentPhoto.taken_at) }}</span>\n      </div>\n      <div v-else class=\"ps-lb-pill-date ps-lb-pill-date--fallback\">\n        {{ t('No date in EXIF') }}\n      </div>\n      <div v-if=\"locationLabel\" class=\"ps-lb-pill-loc\">\n        <MapMarker :size=\"14\" />\n        <span>{{ locationLabel }}</span>\n      </div>\n    </button>\n\n    <!-- Mini-map flyout (toggled by clicking pill) -->\n    <aside v-if=\"miniMapOpen && currentPhoto && currentPhoto.gps\" class=\"ps-lb-minimap\" @click.stop>\n      <div class=\"ps-lb-minimap-header\">\n        <span>{{ locationLabel || t('Location') }}</span>\n        <button\n          class=\"ps-lb-icon-btn\"\n          :aria-label=\"t('Close map')\"\n          :title=\"t('Close map')\"\n          @click=\"miniMapOpen = false\"\n        >\n          <Close :size=\"18\" />\n        </button>\n      </div>\n      <iframe\n        :src=\"osmEmbedUrl\"\n        class=\"ps-lb-minimap-frame\"\n        loading=\"lazy\"\n        referrerpolicy=\"no-referrer-when-downgrade\"\n        :title=\"t('Location map')\"\n      ></iframe>\n      <a\n        v-if=\"osmExternalUrl\"\n        :href=\"osmExternalUrl\"\n        target=\"_blank\"\n        rel=\"noopener noreferrer\"\n        class=\"ps-lb-minimap-link\"\n      >\n        {{ t('View larger map') }}\n      </a>\n    </aside>\n\n    <!-- Details flyout (people/subjects/camera/filename) -->\n    <aside v-if=\"infoOpen && currentPhoto\" class=\"ps-lb-details\" @click.stop>\n      <header class=\"ps-lb-details-header\">\n        <h3>{{ t('Details') }}</h3>\n        <button\n          class=\"ps-lb-icon-btn\"\n          :aria-label=\"t('Close details')\"\n          :title=\"t('Close details')\"\n          @click=\"infoOpen = false\"\n        >\n          <Close :size=\"18\" />\n        </button>\n      </header>\n      <dl class=\"ps-lb-info-list\">\n        <template v-if=\"hasPeople\">\n          <dt>{{ t('People') }}</dt>\n          <dd>\n            <span v-for=\"p in currentPhoto.people\" :key=\"p\" class=\"ps-lb-chip\">{{ p }}</span>\n          </dd>\n        </template>\n        <template v-if=\"hasSubjects\">\n          <dt>{{ t('Subjects') }}</dt>\n          <dd>\n            <span v-for=\"s in currentPhoto.subjects\" :key=\"s\" class=\"ps-lb-chip\">{{ s }}</span>\n          </dd>\n        </template>\n        <template v-if=\"currentPhoto.camera\">\n          <dt>{{ t('Camera') }}</dt>\n          <dd>{{ currentPhoto.camera }}</dd>\n        </template>\n        <template v-if=\"currentPhoto.name\">\n          <dt>{{ t('File') }}</dt>\n          <dd class=\"ps-lb-info-filename\">\n            <button\n              v-if=\"canOpenInFiles()\"\n              type=\"button\"\n              class=\"ps-lb-filename-link\"\n              :title=\"t('Show in Files')\"\n              @click=\"openInFiles\"\n            >\n              <span>{{ currentPhoto.name }}</span>\n              <OpenInNew :size=\"14\" />\n            </button>\n            <span v-else>{{ currentPhoto.name }}</span>\n          </dd>\n        </template>\n      </dl>\n    </aside>\n  </div>\n  </Teleport>\n</template>\n\n<script>\nimport { generateUrl } from '@nextcloud/router';\nimport { translate as t, getCanonicalLocale } from '@nextcloud/l10n';\nimport ChevronLeft from 'vue-material-design-icons/ChevronLeft.vue';\nimport ChevronRight from 'vue-material-design-icons/ChevronRight.vue';\nimport Close from 'vue-material-design-icons/Close.vue';\nimport Play from 'vue-material-design-icons/Play.vue';\nimport Pause from 'vue-material-design-icons/Pause.vue';\nimport InformationOutline from 'vue-material-design-icons/InformationOutline.vue';\nimport MapMarker from 'vue-material-design-icons/MapMarker.vue';\nimport FolderOpen from 'vue-material-design-icons/FolderOpen.vue';\nimport OpenInNew from 'vue-material-design-icons/OpenInNew.vue';\n\nexport default {\n  name: 'PhotoLightbox',\n  components: {\n    ChevronLeft,\n    ChevronRight,\n    Close,\n    Play,\n    Pause,\n    InformationOutline,\n    MapMarker,\n    FolderOpen,\n    OpenInNew,\n  },\n  props: {\n    photos: { type: Array, required: true },\n    startIndex: { type: Number, default: 0 },\n    visible: { type: Boolean, default: false },\n  },\n  emits: ['close'],\n  data() {\n    return {\n      index: this.startIndex,\n      infoOpen: false,\n      miniMapOpen: false,\n      slideshowOn: false,\n      speed: 4000,\n      slideshowTimer: null,\n      touchStartX: 0,\n      touchStartY: 0,\n    };\n  },\n  computed: {\n    currentPhoto() {\n      return this.photos[this.index] || null;\n    },\n    hasPeople() {\n      return Array.isArray(this.currentPhoto?.people) && this.currentPhoto.people.length > 0;\n    },\n    hasSubjects() {\n      return Array.isArray(this.currentPhoto?.subjects) && this.currentPhoto.subjects.length > 0;\n    },\n    locationLabel() {\n      const p = this.currentPhoto;\n      if (!p) return '';\n      if (p.location_display) return p.location_display;\n      if (p.location) {\n        return p.country && !p.location.includes(p.country)\n          ? `${p.location}, ${p.country}`\n          : p.location;\n      }\n      return p.country || '';\n    },\n    osmEmbedUrl() {\n      const gps = this.currentPhoto?.gps;\n      if (!gps) return '';\n      const { lat, lon } = gps;\n      const d = 0.005;\n      const bbox = `${lon - d},${lat - d},${lon + d},${lat + d}`;\n      return `https://www.openstreetmap.org/export/embed.html?bbox=${bbox}&layer=mapnik&marker=${lat},${lon}`;\n    },\n    osmExternalUrl() {\n      const gps = this.currentPhoto?.gps;\n      if (!gps) return '';\n      return `https://www.openstreetmap.org/?mlat=${gps.lat}&mlon=${gps.lon}#map=15/${gps.lat}/${gps.lon}`;\n    },\n  },\n  watch: {\n    visible(v) {\n      if (v) {\n        this.index = this.startIndex;\n        this.lockBodyScroll();\n        this.$nextTick(() => {\n          this.$refs.root?.focus();\n        });\n      } else {\n        this.stopSlideshow();\n        this.unlockBodyScroll();\n      }\n    },\n    speed() {\n      if (this.slideshowOn) {\n        this.stopSlideshow();\n        this.startSlideshow();\n      }\n    },\n    index() {\n      // Close transient panels when navigating to next/prev photo\n      this.miniMapOpen = false;\n    },\n  },\n  mounted() {\n    // Remember the element that opened the lightbox so we can restore focus\n    // when it closes — required for keyboard users (WCAG 2.4.3).\n    this._previouslyFocused = document.activeElement instanceof HTMLElement\n      ? document.activeElement\n      : null;\n    this.index = this.startIndex;\n    if (this.visible) this.lockBodyScroll();\n    this.$nextTick(() => {\n      this.$refs.root?.focus();\n    });\n  },\n  beforeUnmount() {\n    this.stopSlideshow();\n    this.unlockBodyScroll();\n    // Restore focus to the element that triggered the lightbox.\n    if (this._previouslyFocused && typeof this._previouslyFocused.focus === 'function') {\n      try { this._previouslyFocused.focus(); } catch (e) { /* ignore */ }\n      this._previouslyFocused = null;\n    }\n  },\n  methods: {\n    t(key, vars = {}) {\n      return t('intravox', key, vars);\n    },\n    fullUrl(photo) {\n      // High-resolution image OR video poster-frame. Federated photos\n      // route through IntraVox's preview proxy because NC core can't\n      // generate previews for files on remote storages.\n      if (photo && photo.is_federated) {\n        return generateUrl(`/apps/intravox/api/preview?file_id=${photo.file_id}&x=2048&y=2048`);\n      }\n      return generateUrl(`/core/preview?fileId=${photo.file_id}&x=2048&y=2048&a=true`);\n    },\n    isVideo(photo) {\n      if (!photo) return false;\n      const mime = String(photo.mime || '');\n      if (mime.startsWith('video/')) return true;\n      return /\\.(mov|mp4|m4v|avi|mkv|webm|wmv|mpg|mpeg|3gp)$/i.test(photo.name || '');\n    },\n    videoUrl(photo) {\n      // Stream via our own endpoint — handles ACL + range requests + groupfolder\n      // path resolution without us having to construct a WebDAV URL client-side.\n      return generateUrl(`/apps/intravox/api/photo-story/video?file_id=${photo.file_id}`);\n    },\n    formatDateTime(iso) {\n      if (!iso) return '';\n      try {\n        const d = new Date(iso);\n        return d.toLocaleString(getCanonicalLocale(), {\n          day: 'numeric',\n          month: 'long',\n          year: 'numeric',\n          hour: '2-digit',\n          minute: '2-digit',\n        });\n      } catch (e) {\n        return iso;\n      }\n    },\n    formatLongDate(iso) {\n      if (!iso) return '';\n      try {\n        const d = new Date(iso);\n        return d.toLocaleDateString(getCanonicalLocale(), {\n          weekday: 'long',\n          day: 'numeric',\n          month: 'long',\n          year: 'numeric',\n        });\n      } catch (e) {\n        return iso;\n      }\n    },\n    formatTime(iso) {\n      if (!iso) return '';\n      try {\n        const d = new Date(iso);\n        return d.toLocaleTimeString(getCanonicalLocale(), { hour: '2-digit', minute: '2-digit' });\n      } catch (e) {\n        return '';\n      }\n    },\n    onPillClick() {\n      // Toggle mini-map if GPS available; otherwise no-op\n      if (this.currentPhoto?.gps) {\n        this.miniMapOpen = !this.miniMapOpen;\n      }\n    },\n    openInFiles() {\n      const p = this.currentPhoto;\n      if (!p || !p.file_id) return;\n      // Server-side endpoint resolves the user-relative parent path and 302s\n      // to the Files app. Necessary because `path` in the API response is\n      // storage-internal (no mountpoint prefix for federated/groupfolder\n      // shares), so building the URL client-side breaks for those mounts.\n      const url = generateUrl('/apps/intravox/api/photo-story/open-in-files?file_id={id}', {\n        id: String(p.file_id),\n      });\n      window.open(url, '_blank', 'noopener');\n    },\n    canOpenInFiles() {\n      return !!this.currentPhoto?.file_id;\n    },\n    lockBodyScroll() {\n      if (this._bodyLocked) return;\n      this._prevBodyOverflow = document.body.style.overflow;\n      document.body.style.overflow = 'hidden';\n      this._bodyLocked = true;\n    },\n    unlockBodyScroll() {\n      if (!this._bodyLocked) return;\n      document.body.style.overflow = this._prevBodyOverflow || '';\n      this._bodyLocked = false;\n    },\n    next() {\n      if (!this.photos.length) return;\n      this.index = (this.index + 1) % this.photos.length;\n    },\n    prev() {\n      if (!this.photos.length) return;\n      this.index = (this.index - 1 + this.photos.length) % this.photos.length;\n    },\n    close() {\n      this.stopSlideshow();\n      this.$emit('close');\n    },\n    onKey(e) {\n      // Focus trap: Tab should cycle within the lightbox, never escape it.\n      if (e.key === 'Tab') {\n        this.trapTab(e);\n        return;\n      }\n      switch (e.key) {\n        case 'ArrowRight':\n          this.next();\n          break;\n        case 'ArrowLeft':\n          this.prev();\n          break;\n        case 'Home':\n          this.index = 0;\n          break;\n        case 'End':\n          this.index = Math.max(0, this.photos.length - 1);\n          break;\n        case 'Escape':\n          this.close();\n          break;\n        case ' ':\n          e.preventDefault();\n          this.toggleSlideshow();\n          break;\n        default:\n          return;\n      }\n    },\n    trapTab(e) {\n      const root = this.$refs.root;\n      if (!root) return;\n      const focusables = root.querySelectorAll(\n        'a[href], button:not([disabled]), select:not([disabled]), [tabindex]:not([tabindex=\"-1\"])'\n      );\n      if (focusables.length === 0) {\n        e.preventDefault();\n        return;\n      }\n      const first = focusables[0];\n      const last = focusables[focusables.length - 1];\n      const active = document.activeElement;\n      if (e.shiftKey && (active === first || active === root)) {\n        e.preventDefault();\n        last.focus();\n      } else if (!e.shiftKey && active === last) {\n        e.preventDefault();\n        first.focus();\n      }\n    },\n    onTouchStart(e) {\n      const t0 = e.touches?.[0];\n      if (!t0) return;\n      this.touchStartX = t0.clientX;\n      this.touchStartY = t0.clientY;\n    },\n    onTouchEnd(e) {\n      const t1 = e.changedTouches?.[0];\n      if (!t1) return;\n      const dx = t1.clientX - this.touchStartX;\n      const dy = t1.clientY - this.touchStartY;\n      // Horizontal swipe over 60px and dominant axis → prev/next\n      if (Math.abs(dx) > 60 && Math.abs(dx) > Math.abs(dy)) {\n        if (dx < 0) this.next();\n        else this.prev();\n        return;\n      }\n      // Vertical swipe-down over 100px → close (matches iOS/Android Photos UX)\n      if (dy > 100 && Math.abs(dy) > Math.abs(dx)) {\n        this.close();\n      }\n    },\n    toggleSlideshow() {\n      if (this.slideshowOn) {\n        this.stopSlideshow();\n      } else {\n        this.startSlideshow();\n      }\n    },\n    startSlideshow() {\n      this.slideshowOn = true;\n      this.slideshowTimer = setInterval(() => {\n        this.next();\n      }, this.speed);\n    },\n    stopSlideshow() {\n      this.slideshowOn = false;\n      if (this.slideshowTimer) {\n        clearInterval(this.slideshowTimer);\n        this.slideshowTimer = null;\n      }\n    },\n  },\n};\n</script>\n\n<style scoped>\n.ps-lb-sr-only {\n  position: absolute;\n  width: 1px;\n  height: 1px;\n  padding: 0;\n  margin: -1px;\n  overflow: hidden;\n  clip: rect(0, 0, 0, 0);\n  white-space: nowrap;\n  border: 0;\n}\n\n.ps-lightbox {\n  position: fixed;\n  inset: 0;\n  /* Above Nextcloud header (z-index 2000) and most NC overlays. */\n  z-index: 100000;\n  background: rgba(0, 0, 0, 0.97);\n  display: flex;\n  flex-direction: column;\n  color: #fff;\n  outline: none;\n}\n\n.ps-lb-topbar {\n  display: flex;\n  justify-content: space-between;\n  align-items: center;\n  padding: 12px 16px;\n  /* Respect iOS notch / Android status bar in fullscreen. */\n  padding-top: max(12px, env(safe-area-inset-top));\n  padding-left: max(16px, env(safe-area-inset-left));\n  padding-right: max(16px, env(safe-area-inset-right));\n  background: linear-gradient(180deg, rgba(0, 0, 0, 0.75), transparent);\n  position: absolute;\n  top: 0;\n  left: 0;\n  right: 0;\n  z-index: 2;\n}\n\n.ps-lb-counter {\n  font-size: 14px;\n  font-variant-numeric: tabular-nums;\n  opacity: 0.8;\n}\n\n.ps-lb-actions {\n  display: flex;\n  align-items: center;\n  gap: 8px;\n}\n\n.ps-lb-icon-btn {\n  display: inline-flex;\n  align-items: center;\n  justify-content: center;\n  width: 40px;\n  height: 40px;\n  border-radius: 50%;\n  border: none;\n  background: rgba(255, 255, 255, 0.08);\n  color: #fff;\n  cursor: pointer;\n  transition: background 0.15s;\n}\n\n.ps-lb-icon-btn:hover {\n  background: rgba(255, 255, 255, 0.18);\n}\n\n.ps-lb-speed {\n  padding: 6px 8px;\n  background: rgba(255, 255, 255, 0.1);\n  color: #fff;\n  border: 1px solid rgba(255, 255, 255, 0.2);\n  border-radius: var(--border-radius);\n  font-size: 13px;\n}\n\n.ps-lb-stage {\n  flex: 1;\n  display: flex;\n  align-items: center;\n  justify-content: center;\n  position: relative;\n  padding: 60px 60px;\n  overflow: hidden;\n}\n\n.ps-lb-image-wrap {\n  width: 100%;\n  height: 100%;\n  display: flex;\n  align-items: center;\n  justify-content: center;\n}\n\n.ps-lb-image {\n  max-width: 95vw;\n  max-height: 90vh;\n  object-fit: contain;\n  display: block;\n}\n\n/* Ken Burns effect during slideshow */\n.ps-lb-kenburns .ps-lb-image {\n  animation: ps-kenburns 4s cubic-bezier(0.22, 0.61, 0.36, 1) forwards;\n}\n\n@keyframes ps-kenburns {\n  from {\n    transform: scale(1);\n    opacity: 0;\n  }\n  10% {\n    opacity: 1;\n  }\n  to {\n    transform: scale(1.1) translate(2%, -2%);\n    opacity: 1;\n  }\n}\n\n.ps-lb-nav {\n  position: absolute;\n  top: 50%;\n  transform: translateY(-50%);\n  width: 56px;\n  height: 56px;\n  border-radius: 50%;\n  border: none;\n  background: rgba(255, 255, 255, 0.08);\n  color: #fff;\n  cursor: pointer;\n  display: flex;\n  align-items: center;\n  justify-content: center;\n  transition: background 0.15s;\n}\n\n.ps-lb-nav:hover:not(:disabled) {\n  background: rgba(255, 255, 255, 0.2);\n}\n\n.ps-lb-nav:disabled {\n  opacity: 0.25;\n  cursor: not-allowed;\n}\n\n.ps-lb-nav--prev {\n  left: 12px;\n}\n\n.ps-lb-nav--next {\n  right: 12px;\n}\n\n/* Date + location pill (always-visible left-bottom).\n   Background is opaque enough to stay readable over any photo. */\n.ps-lb-pill {\n  position: absolute;\n  left: 24px;\n  bottom: 24px;\n  background: rgba(0, 0, 0, 0.62);\n  backdrop-filter: blur(14px) saturate(140%);\n  -webkit-backdrop-filter: blur(14px) saturate(140%);\n  border: 1px solid rgba(255, 255, 255, 0.08);\n  border-radius: 14px;\n  padding: 14px 20px;\n  color: #fff;\n  z-index: 3;\n  box-shadow: 0 8px 28px rgba(0, 0, 0, 0.55);\n  cursor: pointer;\n  transition: background 0.2s ease, transform 0.2s ease;\n  max-width: 70vw;\n  text-align: left;\n  font: inherit;\n}\n\n.ps-lb-pill:disabled {\n  cursor: default;\n  opacity: 0.9;\n}\n\n.ps-lb-pill:hover:not(:disabled) {\n  background: rgba(0, 0, 0, 0.78);\n  transform: translateY(-2px);\n}\n\n.ps-lb-pill-date {\n  font-size: 18px;\n  font-weight: 600;\n  letter-spacing: -0.01em;\n  line-height: 1.2;\n  /* Last-resort fallback if backdrop-filter is unsupported. */\n  text-shadow: 0 1px 3px rgba(0, 0, 0, 0.6);\n}\n\n.ps-lb-pill-date--fallback {\n  color: rgba(255, 255, 255, 0.85);\n  font-weight: 500;\n}\n\n.ps-lb-pill-time {\n  font-weight: 400;\n  opacity: 0.8;\n}\n\n.ps-lb-pill-loc {\n  display: flex;\n  align-items: center;\n  gap: 4px;\n  margin-top: 6px;\n  font-size: 14px;\n  color: rgba(255, 255, 255, 0.85);\n}\n\n.ps-lb-pill-loc span {\n  white-space: nowrap;\n  overflow: hidden;\n  text-overflow: ellipsis;\n}\n\n/* Mini-map flyout */\n.ps-lb-minimap {\n  position: absolute;\n  left: 24px;\n  bottom: 110px;\n  width: 380px;\n  max-width: calc(100vw - 48px);\n  background: rgba(20, 20, 20, 0.95);\n  backdrop-filter: blur(12px);\n  -webkit-backdrop-filter: blur(12px);\n  border-radius: 14px;\n  overflow: hidden;\n  z-index: 4;\n  box-shadow: 0 10px 40px rgba(0, 0, 0, 0.6);\n  display: flex;\n  flex-direction: column;\n}\n\n.ps-lb-minimap-header {\n  display: flex;\n  align-items: center;\n  justify-content: space-between;\n  padding: 10px 14px;\n  color: #fff;\n  font-size: 13px;\n  border-bottom: 1px solid rgba(255, 255, 255, 0.08);\n}\n\n.ps-lb-minimap-frame {\n  width: 100%;\n  height: 260px;\n  border: 0;\n  display: block;\n  background: #1a1a1a;\n}\n\n.ps-lb-minimap-link {\n  padding: 10px 14px;\n  font-size: 12px;\n  color: rgba(255, 255, 255, 0.85);\n  text-decoration: none;\n  border-top: 1px solid rgba(255, 255, 255, 0.08);\n}\n\n.ps-lb-minimap-link:hover {\n  color: #fff;\n  background: rgba(255, 255, 255, 0.05);\n}\n\n/* Details flyout (collapsible) */\n.ps-lb-details {\n  position: absolute;\n  right: 24px;\n  bottom: 24px;\n  width: 340px;\n  max-width: calc(100vw - 48px);\n  background: rgba(20, 20, 20, 0.92);\n  backdrop-filter: blur(12px);\n  -webkit-backdrop-filter: blur(12px);\n  border-radius: 14px;\n  padding: 16px 20px;\n  color: #fff;\n  z-index: 3;\n  box-shadow: 0 10px 40px rgba(0, 0, 0, 0.5);\n  max-height: 60vh;\n  overflow-y: auto;\n}\n\n.ps-lb-details-header {\n  display: flex;\n  align-items: center;\n  justify-content: space-between;\n  margin: 0 0 12px 0;\n}\n\n.ps-lb-details-header h3 {\n  margin: 0;\n  font-size: 15px;\n  font-weight: 600;\n}\n\n.ps-lb-info-list {\n  margin: 0;\n  display: grid;\n  grid-template-columns: max-content 1fr;\n  gap: 8px 12px;\n  font-size: 13px;\n}\n\n.ps-lb-info-list dt {\n  font-weight: 600;\n  color: rgba(255, 255, 255, 0.6);\n}\n\n.ps-lb-info-list dd {\n  margin: 0;\n  word-break: break-word;\n}\n\n.ps-lb-info-filename {\n  font-family: var(--font-face, monospace);\n  font-size: 12px;\n  opacity: 0.85;\n}\n\n.ps-lb-filename-link {\n  display: inline-flex;\n  align-items: center;\n  gap: 6px;\n  padding: 4px 8px;\n  margin: -4px -8px;\n  background: transparent;\n  border: none;\n  border-radius: 6px;\n  color: #fff;\n  font: inherit;\n  text-align: left;\n  cursor: pointer;\n  word-break: break-all;\n  transition: background 0.15s;\n}\n\n.ps-lb-filename-link:hover {\n  background: rgba(255, 255, 255, 0.1);\n  color: var(--color-primary-element-light, #6bb4ff);\n}\n\n.ps-lb-filename-link:focus-visible {\n  outline: 2px solid var(--color-primary-element, #0082c9);\n  outline-offset: 1px;\n}\n\n.ps-lb-chip {\n  display: inline-block;\n  padding: 2px 8px;\n  margin: 2px 4px 2px 0;\n  background: rgba(255, 255, 255, 0.12);\n  border-radius: 999px;\n  font-size: 12px;\n}\n\n@media (max-width: 600px) {\n  .ps-lb-stage {\n    padding: 60px 8px;\n  }\n\n  .ps-lb-nav {\n    width: 44px;\n    height: 44px;\n  }\n\n  .ps-lb-pill {\n    left: 12px;\n    right: 12px;\n    bottom: 12px;\n    max-width: none;\n  }\n\n  .ps-lb-pill-date {\n    font-size: 16px;\n  }\n\n  .ps-lb-minimap {\n    left: 12px;\n    right: 12px;\n    bottom: 110px;\n    width: auto;\n    max-width: none;\n  }\n\n  .ps-lb-details {\n    left: 12px;\n    right: 12px;\n    bottom: 12px;\n    width: auto;\n    max-width: none;\n    max-height: 50vh;\n  }\n}\n\n@media (prefers-reduced-motion: reduce) {\n  .ps-lb-kenburns .ps-lb-image {\n    animation: none;\n  }\n  .ps-lb-pill {\n    transition: none;\n  }\n}\n</style>\n"],"sourceRoot":""}]);
// Exports
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (___CSS_LOADER_EXPORT___);


/***/ },

/***/ "./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/dist/stylePostLoader.js!./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/PhotoLightbox.vue?vue&type=style&index=0&id=3d8b81c7&scoped=true&lang=css"
/*!******************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/dist/stylePostLoader.js!./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/PhotoLightbox.vue?vue&type=style&index=0&id=3d8b81c7&scoped=true&lang=css ***!
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
/* harmony import */ var _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_dist_stylePostLoader_js_node_modules_vue_loader_dist_index_js_ruleSet_1_rules_5_use_0_PhotoLightbox_vue_vue_type_style_index_0_id_3d8b81c7_scoped_true_lang_css__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! !!../../node_modules/css-loader/dist/cjs.js!../../node_modules/vue-loader/dist/stylePostLoader.js!../../node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./PhotoLightbox.vue?vue&type=style&index=0&id=3d8b81c7&scoped=true&lang=css */ "./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/dist/stylePostLoader.js!./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/PhotoLightbox.vue?vue&type=style&index=0&id=3d8b81c7&scoped=true&lang=css");

      
      
      
      
      
      
      
      
      

var options = {};

options.styleTagTransform = (_node_modules_style_loader_dist_runtime_styleTagTransform_js__WEBPACK_IMPORTED_MODULE_5___default());
options.setAttributes = (_node_modules_style_loader_dist_runtime_setAttributesWithoutAttributes_js__WEBPACK_IMPORTED_MODULE_3___default());

      options.insert = _node_modules_style_loader_dist_runtime_insertBySelector_js__WEBPACK_IMPORTED_MODULE_2___default().bind(null, "head");
    
options.domAPI = (_node_modules_style_loader_dist_runtime_styleDomAPI_js__WEBPACK_IMPORTED_MODULE_1___default());
options.insertStyleElement = (_node_modules_style_loader_dist_runtime_insertStyleElement_js__WEBPACK_IMPORTED_MODULE_4___default());

var update = _node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0___default()(_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_dist_stylePostLoader_js_node_modules_vue_loader_dist_index_js_ruleSet_1_rules_5_use_0_PhotoLightbox_vue_vue_type_style_index_0_id_3d8b81c7_scoped_true_lang_css__WEBPACK_IMPORTED_MODULE_6__["default"], options);




       /* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_dist_stylePostLoader_js_node_modules_vue_loader_dist_index_js_ruleSet_1_rules_5_use_0_PhotoLightbox_vue_vue_type_style_index_0_id_3d8b81c7_scoped_true_lang_css__WEBPACK_IMPORTED_MODULE_6__["default"] && _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_dist_stylePostLoader_js_node_modules_vue_loader_dist_index_js_ruleSet_1_rules_5_use_0_PhotoLightbox_vue_vue_type_style_index_0_id_3d8b81c7_scoped_true_lang_css__WEBPACK_IMPORTED_MODULE_6__["default"].locals ? _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_dist_stylePostLoader_js_node_modules_vue_loader_dist_index_js_ruleSet_1_rules_5_use_0_PhotoLightbox_vue_vue_type_style_index_0_id_3d8b81c7_scoped_true_lang_css__WEBPACK_IMPORTED_MODULE_6__["default"].locals : undefined);


/***/ },

/***/ "./src/components/PhotoLightbox.vue"
/*!******************************************!*\
  !*** ./src/components/PhotoLightbox.vue ***!
  \******************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _PhotoLightbox_vue_vue_type_template_id_3d8b81c7_scoped_true__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./PhotoLightbox.vue?vue&type=template&id=3d8b81c7&scoped=true */ "./src/components/PhotoLightbox.vue?vue&type=template&id=3d8b81c7&scoped=true");
/* harmony import */ var _PhotoLightbox_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./PhotoLightbox.vue?vue&type=script&lang=js */ "./src/components/PhotoLightbox.vue?vue&type=script&lang=js");
/* harmony import */ var _PhotoLightbox_vue_vue_type_style_index_0_id_3d8b81c7_scoped_true_lang_css__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./PhotoLightbox.vue?vue&type=style&index=0&id=3d8b81c7&scoped=true&lang=css */ "./src/components/PhotoLightbox.vue?vue&type=style&index=0&id=3d8b81c7&scoped=true&lang=css");
/* harmony import */ var _node_modules_vue_loader_dist_exportHelper_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ../../node_modules/vue-loader/dist/exportHelper.js */ "./node_modules/vue-loader/dist/exportHelper.js");




;


const __exports__ = /*#__PURE__*/(0,_node_modules_vue_loader_dist_exportHelper_js__WEBPACK_IMPORTED_MODULE_3__["default"])(_PhotoLightbox_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_1__["default"], [['render',_PhotoLightbox_vue_vue_type_template_id_3d8b81c7_scoped_true__WEBPACK_IMPORTED_MODULE_0__.render],['__scopeId',"data-v-3d8b81c7"],['__file',"src/components/PhotoLightbox.vue"]])
/* hot reload */
if (false) // removed by dead control flow
{}


/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (__exports__);

/***/ },

/***/ "./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/PhotoLightbox.vue?vue&type=script&lang=js"
/*!**************************************************************************************************************************************!*\
  !*** ./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/PhotoLightbox.vue?vue&type=script&lang=js ***!
  \**************************************************************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _nextcloud_router__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @nextcloud/router */ "./node_modules/@nextcloud/router/dist/index.mjs");
/* harmony import */ var _nextcloud_l10n__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @nextcloud/l10n */ "./node_modules/@nextcloud/l10n/dist/index.mjs");
/* harmony import */ var vue_material_design_icons_ChevronLeft_vue__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! vue-material-design-icons/ChevronLeft.vue */ "./node_modules/vue-material-design-icons/ChevronLeft.vue");
/* harmony import */ var vue_material_design_icons_ChevronRight_vue__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! vue-material-design-icons/ChevronRight.vue */ "./node_modules/vue-material-design-icons/ChevronRight.vue");
/* harmony import */ var vue_material_design_icons_Close_vue__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! vue-material-design-icons/Close.vue */ "./node_modules/vue-material-design-icons/Close.vue");
/* harmony import */ var vue_material_design_icons_Play_vue__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! vue-material-design-icons/Play.vue */ "./node_modules/vue-material-design-icons/Play.vue");
/* harmony import */ var vue_material_design_icons_Pause_vue__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! vue-material-design-icons/Pause.vue */ "./node_modules/vue-material-design-icons/Pause.vue");
/* harmony import */ var vue_material_design_icons_InformationOutline_vue__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! vue-material-design-icons/InformationOutline.vue */ "./node_modules/vue-material-design-icons/InformationOutline.vue");
/* harmony import */ var vue_material_design_icons_MapMarker_vue__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__(/*! vue-material-design-icons/MapMarker.vue */ "./node_modules/vue-material-design-icons/MapMarker.vue");
/* harmony import */ var vue_material_design_icons_FolderOpen_vue__WEBPACK_IMPORTED_MODULE_9__ = __webpack_require__(/*! vue-material-design-icons/FolderOpen.vue */ "./node_modules/vue-material-design-icons/FolderOpen.vue");
/* harmony import */ var vue_material_design_icons_OpenInNew_vue__WEBPACK_IMPORTED_MODULE_10__ = __webpack_require__(/*! vue-material-design-icons/OpenInNew.vue */ "./node_modules/vue-material-design-icons/OpenInNew.vue");













/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = ({
  name: 'PhotoLightbox',
  components: {
    ChevronLeft: vue_material_design_icons_ChevronLeft_vue__WEBPACK_IMPORTED_MODULE_2__["default"],
    ChevronRight: vue_material_design_icons_ChevronRight_vue__WEBPACK_IMPORTED_MODULE_3__["default"],
    Close: vue_material_design_icons_Close_vue__WEBPACK_IMPORTED_MODULE_4__["default"],
    Play: vue_material_design_icons_Play_vue__WEBPACK_IMPORTED_MODULE_5__["default"],
    Pause: vue_material_design_icons_Pause_vue__WEBPACK_IMPORTED_MODULE_6__["default"],
    InformationOutline: vue_material_design_icons_InformationOutline_vue__WEBPACK_IMPORTED_MODULE_7__["default"],
    MapMarker: vue_material_design_icons_MapMarker_vue__WEBPACK_IMPORTED_MODULE_8__["default"],
    FolderOpen: vue_material_design_icons_FolderOpen_vue__WEBPACK_IMPORTED_MODULE_9__["default"],
    OpenInNew: vue_material_design_icons_OpenInNew_vue__WEBPACK_IMPORTED_MODULE_10__["default"],
  },
  props: {
    photos: { type: Array, required: true },
    startIndex: { type: Number, default: 0 },
    visible: { type: Boolean, default: false },
  },
  emits: ['close'],
  data() {
    return {
      index: this.startIndex,
      infoOpen: false,
      miniMapOpen: false,
      slideshowOn: false,
      speed: 4000,
      slideshowTimer: null,
      touchStartX: 0,
      touchStartY: 0,
    };
  },
  computed: {
    currentPhoto() {
      return this.photos[this.index] || null;
    },
    hasPeople() {
      return Array.isArray(this.currentPhoto?.people) && this.currentPhoto.people.length > 0;
    },
    hasSubjects() {
      return Array.isArray(this.currentPhoto?.subjects) && this.currentPhoto.subjects.length > 0;
    },
    locationLabel() {
      const p = this.currentPhoto;
      if (!p) return '';
      if (p.location_display) return p.location_display;
      if (p.location) {
        return p.country && !p.location.includes(p.country)
          ? `${p.location}, ${p.country}`
          : p.location;
      }
      return p.country || '';
    },
    osmEmbedUrl() {
      const gps = this.currentPhoto?.gps;
      if (!gps) return '';
      const { lat, lon } = gps;
      const d = 0.005;
      const bbox = `${lon - d},${lat - d},${lon + d},${lat + d}`;
      return `https://www.openstreetmap.org/export/embed.html?bbox=${bbox}&layer=mapnik&marker=${lat},${lon}`;
    },
    osmExternalUrl() {
      const gps = this.currentPhoto?.gps;
      if (!gps) return '';
      return `https://www.openstreetmap.org/?mlat=${gps.lat}&mlon=${gps.lon}#map=15/${gps.lat}/${gps.lon}`;
    },
  },
  watch: {
    visible(v) {
      if (v) {
        this.index = this.startIndex;
        this.lockBodyScroll();
        this.$nextTick(() => {
          this.$refs.root?.focus();
        });
      } else {
        this.stopSlideshow();
        this.unlockBodyScroll();
      }
    },
    speed() {
      if (this.slideshowOn) {
        this.stopSlideshow();
        this.startSlideshow();
      }
    },
    index() {
      // Close transient panels when navigating to next/prev photo
      this.miniMapOpen = false;
    },
  },
  mounted() {
    // Remember the element that opened the lightbox so we can restore focus
    // when it closes — required for keyboard users (WCAG 2.4.3).
    this._previouslyFocused = document.activeElement instanceof HTMLElement
      ? document.activeElement
      : null;
    this.index = this.startIndex;
    if (this.visible) this.lockBodyScroll();
    this.$nextTick(() => {
      this.$refs.root?.focus();
    });
  },
  beforeUnmount() {
    this.stopSlideshow();
    this.unlockBodyScroll();
    // Restore focus to the element that triggered the lightbox.
    if (this._previouslyFocused && typeof this._previouslyFocused.focus === 'function') {
      try { this._previouslyFocused.focus(); } catch (e) { /* ignore */ }
      this._previouslyFocused = null;
    }
  },
  methods: {
    t(key, vars = {}) {
      return (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_1__.translate)('intravox', key, vars);
    },
    fullUrl(photo) {
      // High-resolution image OR video poster-frame. Federated photos
      // route through IntraVox's preview proxy because NC core can't
      // generate previews for files on remote storages.
      if (photo && photo.is_federated) {
        return (0,_nextcloud_router__WEBPACK_IMPORTED_MODULE_0__.generateUrl)(`/apps/intravox/api/preview?file_id=${photo.file_id}&x=2048&y=2048`);
      }
      return (0,_nextcloud_router__WEBPACK_IMPORTED_MODULE_0__.generateUrl)(`/core/preview?fileId=${photo.file_id}&x=2048&y=2048&a=true`);
    },
    isVideo(photo) {
      if (!photo) return false;
      const mime = String(photo.mime || '');
      if (mime.startsWith('video/')) return true;
      return /\.(mov|mp4|m4v|avi|mkv|webm|wmv|mpg|mpeg|3gp)$/i.test(photo.name || '');
    },
    videoUrl(photo) {
      // Stream via our own endpoint — handles ACL + range requests + groupfolder
      // path resolution without us having to construct a WebDAV URL client-side.
      return (0,_nextcloud_router__WEBPACK_IMPORTED_MODULE_0__.generateUrl)(`/apps/intravox/api/photo-story/video?file_id=${photo.file_id}`);
    },
    formatDateTime(iso) {
      if (!iso) return '';
      try {
        const d = new Date(iso);
        return d.toLocaleString((0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_1__.getCanonicalLocale)(), {
          day: 'numeric',
          month: 'long',
          year: 'numeric',
          hour: '2-digit',
          minute: '2-digit',
        });
      } catch (e) {
        return iso;
      }
    },
    formatLongDate(iso) {
      if (!iso) return '';
      try {
        const d = new Date(iso);
        return d.toLocaleDateString((0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_1__.getCanonicalLocale)(), {
          weekday: 'long',
          day: 'numeric',
          month: 'long',
          year: 'numeric',
        });
      } catch (e) {
        return iso;
      }
    },
    formatTime(iso) {
      if (!iso) return '';
      try {
        const d = new Date(iso);
        return d.toLocaleTimeString((0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_1__.getCanonicalLocale)(), { hour: '2-digit', minute: '2-digit' });
      } catch (e) {
        return '';
      }
    },
    onPillClick() {
      // Toggle mini-map if GPS available; otherwise no-op
      if (this.currentPhoto?.gps) {
        this.miniMapOpen = !this.miniMapOpen;
      }
    },
    openInFiles() {
      const p = this.currentPhoto;
      if (!p || !p.file_id) return;
      // Server-side endpoint resolves the user-relative parent path and 302s
      // to the Files app. Necessary because `path` in the API response is
      // storage-internal (no mountpoint prefix for federated/groupfolder
      // shares), so building the URL client-side breaks for those mounts.
      const url = (0,_nextcloud_router__WEBPACK_IMPORTED_MODULE_0__.generateUrl)('/apps/intravox/api/photo-story/open-in-files?file_id={id}', {
        id: String(p.file_id),
      });
      window.open(url, '_blank', 'noopener');
    },
    canOpenInFiles() {
      return !!this.currentPhoto?.file_id;
    },
    lockBodyScroll() {
      if (this._bodyLocked) return;
      this._prevBodyOverflow = document.body.style.overflow;
      document.body.style.overflow = 'hidden';
      this._bodyLocked = true;
    },
    unlockBodyScroll() {
      if (!this._bodyLocked) return;
      document.body.style.overflow = this._prevBodyOverflow || '';
      this._bodyLocked = false;
    },
    next() {
      if (!this.photos.length) return;
      this.index = (this.index + 1) % this.photos.length;
    },
    prev() {
      if (!this.photos.length) return;
      this.index = (this.index - 1 + this.photos.length) % this.photos.length;
    },
    close() {
      this.stopSlideshow();
      this.$emit('close');
    },
    onKey(e) {
      // Focus trap: Tab should cycle within the lightbox, never escape it.
      if (e.key === 'Tab') {
        this.trapTab(e);
        return;
      }
      switch (e.key) {
        case 'ArrowRight':
          this.next();
          break;
        case 'ArrowLeft':
          this.prev();
          break;
        case 'Home':
          this.index = 0;
          break;
        case 'End':
          this.index = Math.max(0, this.photos.length - 1);
          break;
        case 'Escape':
          this.close();
          break;
        case ' ':
          e.preventDefault();
          this.toggleSlideshow();
          break;
        default:
          return;
      }
    },
    trapTab(e) {
      const root = this.$refs.root;
      if (!root) return;
      const focusables = root.querySelectorAll(
        'a[href], button:not([disabled]), select:not([disabled]), [tabindex]:not([tabindex="-1"])'
      );
      if (focusables.length === 0) {
        e.preventDefault();
        return;
      }
      const first = focusables[0];
      const last = focusables[focusables.length - 1];
      const active = document.activeElement;
      if (e.shiftKey && (active === first || active === root)) {
        e.preventDefault();
        last.focus();
      } else if (!e.shiftKey && active === last) {
        e.preventDefault();
        first.focus();
      }
    },
    onTouchStart(e) {
      const t0 = e.touches?.[0];
      if (!t0) return;
      this.touchStartX = t0.clientX;
      this.touchStartY = t0.clientY;
    },
    onTouchEnd(e) {
      const t1 = e.changedTouches?.[0];
      if (!t1) return;
      const dx = t1.clientX - this.touchStartX;
      const dy = t1.clientY - this.touchStartY;
      // Horizontal swipe over 60px and dominant axis → prev/next
      if (Math.abs(dx) > 60 && Math.abs(dx) > Math.abs(dy)) {
        if (dx < 0) this.next();
        else this.prev();
        return;
      }
      // Vertical swipe-down over 100px → close (matches iOS/Android Photos UX)
      if (dy > 100 && Math.abs(dy) > Math.abs(dx)) {
        this.close();
      }
    },
    toggleSlideshow() {
      if (this.slideshowOn) {
        this.stopSlideshow();
      } else {
        this.startSlideshow();
      }
    },
    startSlideshow() {
      this.slideshowOn = true;
      this.slideshowTimer = setInterval(() => {
        this.next();
      }, this.speed);
    },
    stopSlideshow() {
      this.slideshowOn = false;
      if (this.slideshowTimer) {
        clearInterval(this.slideshowTimer);
        this.slideshowTimer = null;
      }
    },
  },
});


/***/ },

/***/ "./src/components/PhotoLightbox.vue?vue&type=style&index=0&id=3d8b81c7&scoped=true&lang=css"
/*!**************************************************************************************************!*\
  !*** ./src/components/PhotoLightbox.vue?vue&type=style&index=0&id=3d8b81c7&scoped=true&lang=css ***!
  \**************************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _node_modules_style_loader_dist_cjs_js_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_dist_stylePostLoader_js_node_modules_vue_loader_dist_index_js_ruleSet_1_rules_5_use_0_PhotoLightbox_vue_vue_type_style_index_0_id_3d8b81c7_scoped_true_lang_css__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../node_modules/style-loader/dist/cjs.js!../../node_modules/css-loader/dist/cjs.js!../../node_modules/vue-loader/dist/stylePostLoader.js!../../node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./PhotoLightbox.vue?vue&type=style&index=0&id=3d8b81c7&scoped=true&lang=css */ "./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/dist/stylePostLoader.js!./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/PhotoLightbox.vue?vue&type=style&index=0&id=3d8b81c7&scoped=true&lang=css");


/***/ },

/***/ "./src/components/PhotoLightbox.vue?vue&type=script&lang=js"
/*!******************************************************************!*\
  !*** ./src/components/PhotoLightbox.vue?vue&type=script&lang=js ***!
  \******************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* reexport safe */ _node_modules_vue_loader_dist_index_js_ruleSet_1_rules_5_use_0_PhotoLightbox_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_0__["default"])
/* harmony export */ });
/* harmony import */ var _node_modules_vue_loader_dist_index_js_ruleSet_1_rules_5_use_0_PhotoLightbox_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./PhotoLightbox.vue?vue&type=script&lang=js */ "./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/PhotoLightbox.vue?vue&type=script&lang=js");
 

/***/ },

/***/ "./src/components/PhotoLightbox.vue?vue&type=template&id=3d8b81c7&scoped=true"
/*!************************************************************************************!*\
  !*** ./src/components/PhotoLightbox.vue?vue&type=template&id=3d8b81c7&scoped=true ***!
  \************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   render: () => (/* reexport safe */ _node_modules_vue_loader_dist_templateLoader_js_ruleSet_1_rules_2_node_modules_vue_loader_dist_index_js_ruleSet_1_rules_5_use_0_PhotoLightbox_vue_vue_type_template_id_3d8b81c7_scoped_true__WEBPACK_IMPORTED_MODULE_0__.render)
/* harmony export */ });
/* harmony import */ var _node_modules_vue_loader_dist_templateLoader_js_ruleSet_1_rules_2_node_modules_vue_loader_dist_index_js_ruleSet_1_rules_5_use_0_PhotoLightbox_vue_vue_type_template_id_3d8b81c7_scoped_true__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../node_modules/vue-loader/dist/templateLoader.js??ruleSet[1].rules[2]!../../node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./PhotoLightbox.vue?vue&type=template&id=3d8b81c7&scoped=true */ "./node_modules/vue-loader/dist/templateLoader.js??ruleSet[1].rules[2]!./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/PhotoLightbox.vue?vue&type=template&id=3d8b81c7&scoped=true");


/***/ },

/***/ "./node_modules/vue-loader/dist/templateLoader.js??ruleSet[1].rules[2]!./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/PhotoLightbox.vue?vue&type=template&id=3d8b81c7&scoped=true"
/*!******************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/vue-loader/dist/templateLoader.js??ruleSet[1].rules[2]!./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/PhotoLightbox.vue?vue&type=template&id=3d8b81c7&scoped=true ***!
  \******************************************************************************************************************************************************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   render: () => (/* binding */ render)
/* harmony export */ });
/* harmony import */ var vue__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! vue */ "./node_modules/vue/dist/vue.runtime.esm-bundler.js");


const _hoisted_1 = { class: "ps-lb-topbar" }
const _hoisted_2 = {
  class: "ps-lb-counter",
  "aria-live": "polite",
  "aria-atomic": "true"
}
const _hoisted_3 = { class: "ps-lb-sr-only" }
const _hoisted_4 = { class: "ps-lb-actions" }
const _hoisted_5 = ["aria-label", "aria-pressed", "title"]
const _hoisted_6 = {
  key: 0,
  class: "ps-lb-sr-only",
  for: "ps-lb-speed-select"
}
const _hoisted_7 = ["aria-label"]
const _hoisted_8 = { value: 6000 }
const _hoisted_9 = { value: 4000 }
const _hoisted_10 = { value: 2500 }
const _hoisted_11 = ["aria-label", "title"]
const _hoisted_12 = ["aria-label", "aria-pressed", "title"]
const _hoisted_13 = ["aria-label", "title"]
const _hoisted_14 = { class: "ps-lb-stage" }
const _hoisted_15 = ["disabled", "aria-label", "title"]
const _hoisted_16 = ["src", "poster"]
const _hoisted_17 = ["src", "alt"]
const _hoisted_18 = ["disabled", "aria-label", "title"]
const _hoisted_19 = ["aria-label", "aria-pressed", "disabled"]
const _hoisted_20 = {
  key: 0,
  class: "ps-lb-pill-date"
}
const _hoisted_21 = { class: "ps-lb-pill-time" }
const _hoisted_22 = {
  key: 1,
  class: "ps-lb-pill-date ps-lb-pill-date--fallback"
}
const _hoisted_23 = {
  key: 2,
  class: "ps-lb-pill-loc"
}
const _hoisted_24 = { class: "ps-lb-minimap-header" }
const _hoisted_25 = ["aria-label", "title"]
const _hoisted_26 = ["src", "title"]
const _hoisted_27 = ["href"]
const _hoisted_28 = { class: "ps-lb-details-header" }
const _hoisted_29 = ["aria-label", "title"]
const _hoisted_30 = { class: "ps-lb-info-list" }
const _hoisted_31 = { class: "ps-lb-info-filename" }
const _hoisted_32 = ["title"]
const _hoisted_33 = { key: 1 }

function render(_ctx, _cache, $props, $setup, $data, $options) {
  const _component_Pause = (0,vue__WEBPACK_IMPORTED_MODULE_0__.resolveComponent)("Pause")
  const _component_Play = (0,vue__WEBPACK_IMPORTED_MODULE_0__.resolveComponent)("Play")
  const _component_FolderOpen = (0,vue__WEBPACK_IMPORTED_MODULE_0__.resolveComponent)("FolderOpen")
  const _component_InformationOutline = (0,vue__WEBPACK_IMPORTED_MODULE_0__.resolveComponent)("InformationOutline")
  const _component_Close = (0,vue__WEBPACK_IMPORTED_MODULE_0__.resolveComponent)("Close")
  const _component_ChevronLeft = (0,vue__WEBPACK_IMPORTED_MODULE_0__.resolveComponent)("ChevronLeft")
  const _component_ChevronRight = (0,vue__WEBPACK_IMPORTED_MODULE_0__.resolveComponent)("ChevronRight")
  const _component_MapMarker = (0,vue__WEBPACK_IMPORTED_MODULE_0__.resolveComponent)("MapMarker")
  const _component_OpenInNew = (0,vue__WEBPACK_IMPORTED_MODULE_0__.resolveComponent)("OpenInNew")

  return ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createBlock)(vue__WEBPACK_IMPORTED_MODULE_0__.Teleport, { to: "body" }, [
    ($props.visible)
      ? ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)("div", {
          key: 0,
          class: "ps-lightbox",
          role: "dialog",
          "aria-modal": "true",
          tabindex: "-1",
          ref: "root",
          onClick: _cache[13] || (_cache[13] = (0,vue__WEBPACK_IMPORTED_MODULE_0__.withModifiers)((...args) => ($options.close && $options.close(...args)), ["self"])),
          onKeydown: _cache[14] || (_cache[14] = (...args) => ($options.onKey && $options.onKey(...args))),
          onTouchstart: _cache[15] || (_cache[15] = (...args) => ($options.onTouchStart && $options.onTouchStart(...args))),
          onTouchend: _cache[16] || (_cache[16] = (...args) => ($options.onTouchEnd && $options.onTouchEnd(...args)))
        }, [
          (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)(" Top bar "),
          (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("header", _hoisted_1, [
            (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("div", _hoisted_2, [
              (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("span", _hoisted_3, (0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)($options.t('Photo')), 1 /* TEXT */),
              (0,vue__WEBPACK_IMPORTED_MODULE_0__.createTextVNode)(" " + (0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)($data.index + 1) + " / " + (0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)($props.photos.length), 1 /* TEXT */)
            ]),
            (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("div", _hoisted_4, [
              (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("button", {
                class: "ps-lb-icon-btn",
                "aria-label": $data.slideshowOn ? $options.t('Pause slideshow') : $options.t('Start slideshow'),
                "aria-pressed": $data.slideshowOn ? 'true' : 'false',
                title: $data.slideshowOn ? $options.t('Pause slideshow') : $options.t('Start slideshow'),
                onClick: _cache[0] || (_cache[0] = (...args) => ($options.toggleSlideshow && $options.toggleSlideshow(...args)))
              }, [
                ($data.slideshowOn)
                  ? ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createBlock)(_component_Pause, {
                      key: 0,
                      size: 20
                    }))
                  : ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createBlock)(_component_Play, {
                      key: 1,
                      size: 20
                    }))
              ], 8 /* PROPS */, _hoisted_5),
              ($data.slideshowOn)
                ? ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)("label", _hoisted_6, (0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)($options.t('Slideshow speed')), 1 /* TEXT */))
                : (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)("v-if", true),
              ($data.slideshowOn)
                ? (0,vue__WEBPACK_IMPORTED_MODULE_0__.withDirectives)(((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)("select", {
                    key: 1,
                    id: "ps-lb-speed-select",
                    "onUpdate:modelValue": _cache[1] || (_cache[1] = $event => (($data.speed) = $event)),
                    class: "ps-lb-speed",
                    "aria-label": $options.t('Slideshow speed')
                  }, [
                    (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("option", _hoisted_8, (0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)($options.t('Slow')), 1 /* TEXT */),
                    (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("option", _hoisted_9, (0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)($options.t('Normal')), 1 /* TEXT */),
                    (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("option", _hoisted_10, (0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)($options.t('Fast')), 1 /* TEXT */)
                  ], 8 /* PROPS */, _hoisted_7)), [
                    [
                      vue__WEBPACK_IMPORTED_MODULE_0__.vModelSelect,
                      $data.speed,
                      void 0,
                      { number: true }
                    ]
                  ])
                : (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)("v-if", true),
              ($options.canOpenInFiles())
                ? ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)("button", {
                    key: 2,
                    class: "ps-lb-icon-btn",
                    "aria-label": $options.t('Show in Files'),
                    title: $options.t('Show in Files'),
                    onClick: _cache[2] || (_cache[2] = (...args) => ($options.openInFiles && $options.openInFiles(...args)))
                  }, [
                    (0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_FolderOpen, { size: 20 })
                  ], 8 /* PROPS */, _hoisted_11))
                : (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)("v-if", true),
              (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("button", {
                class: "ps-lb-icon-btn",
                "aria-label": $options.t('Toggle info panel'),
                "aria-pressed": $data.infoOpen ? 'true' : 'false',
                title: $options.t('Toggle info panel'),
                onClick: _cache[3] || (_cache[3] = $event => ($data.infoOpen = !$data.infoOpen))
              }, [
                (0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_InformationOutline, { size: 20 })
              ], 8 /* PROPS */, _hoisted_12),
              (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("button", {
                class: "ps-lb-icon-btn",
                "aria-label": $options.t('Close lightbox'),
                title: $options.t('Close lightbox'),
                onClick: _cache[4] || (_cache[4] = (...args) => ($options.close && $options.close(...args)))
              }, [
                (0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_Close, { size: 22 })
              ], 8 /* PROPS */, _hoisted_13)
            ])
          ]),
          (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)(" Main content "),
          (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("div", _hoisted_14, [
            (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("button", {
              class: "ps-lb-nav ps-lb-nav--prev",
              disabled: $props.photos.length < 2,
              "aria-label": $options.t('Previous photo'),
              title: $options.t('Previous photo'),
              onClick: _cache[5] || (_cache[5] = (0,vue__WEBPACK_IMPORTED_MODULE_0__.withModifiers)((...args) => ($options.prev && $options.prev(...args)), ["stop"]))
            }, [
              (0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_ChevronLeft, { size: 36 })
            ], 8 /* PROPS */, _hoisted_15),
            (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("div", {
              class: (0,vue__WEBPACK_IMPORTED_MODULE_0__.normalizeClass)(["ps-lb-image-wrap", { 'ps-lb-kenburns': $data.slideshowOn && !$options.isVideo($options.currentPhoto) }])
            }, [
              ($options.currentPhoto && $options.isVideo($options.currentPhoto))
                ? ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)("video", {
                    key: 'v' + $options.currentPhoto.file_id,
                    src: $options.videoUrl($options.currentPhoto),
                    poster: $options.fullUrl($options.currentPhoto),
                    class: "ps-lb-image ps-lb-video",
                    controls: "",
                    autoplay: "",
                    playsinline: "",
                    preload: "metadata"
                  }, null, 8 /* PROPS */, _hoisted_16))
                : ($options.currentPhoto)
                  ? ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)("img", {
                      key: $options.currentPhoto.file_id,
                      src: $options.fullUrl($options.currentPhoto),
                      alt: $options.currentPhoto.location_display || $options.currentPhoto.location || $options.t('Photo {n} of {total}', { n: $data.index + 1, total: $props.photos.length }),
                      class: "ps-lb-image"
                    }, null, 8 /* PROPS */, _hoisted_17))
                  : (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)("v-if", true)
            ], 2 /* CLASS */),
            (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("button", {
              class: "ps-lb-nav ps-lb-nav--next",
              disabled: $props.photos.length < 2,
              "aria-label": $options.t('Next photo'),
              title: $options.t('Next photo'),
              onClick: _cache[6] || (_cache[6] = (0,vue__WEBPACK_IMPORTED_MODULE_0__.withModifiers)((...args) => ($options.next && $options.next(...args)), ["stop"]))
            }, [
              (0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_ChevronRight, { size: 36 })
            ], 8 /* PROPS */, _hoisted_18)
          ]),
          (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)(" Date + location pill (always visible, left-bottom) "),
          ($options.currentPhoto)
            ? ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)("button", {
                key: 0,
                type: "button",
                class: "ps-lb-pill",
                "aria-label": $options.t('Toggle location map'),
                "aria-pressed": $data.miniMapOpen ? 'true' : 'false',
                disabled: !$options.currentPhoto.gps,
                onClick: _cache[7] || (_cache[7] = (0,vue__WEBPACK_IMPORTED_MODULE_0__.withModifiers)((...args) => ($options.onPillClick && $options.onPillClick(...args)), ["stop"]))
              }, [
                ($options.currentPhoto.taken_at)
                  ? ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)("div", _hoisted_20, [
                      (0,vue__WEBPACK_IMPORTED_MODULE_0__.createTextVNode)((0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)($options.formatLongDate($options.currentPhoto.taken_at)) + " ", 1 /* TEXT */),
                      (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("span", _hoisted_21, "· " + (0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)($options.formatTime($options.currentPhoto.taken_at)), 1 /* TEXT */)
                    ]))
                  : ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)("div", _hoisted_22, (0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)($options.t('No date in EXIF')), 1 /* TEXT */)),
                ($options.locationLabel)
                  ? ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)("div", _hoisted_23, [
                      (0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_MapMarker, { size: 14 }),
                      (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("span", null, (0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)($options.locationLabel), 1 /* TEXT */)
                    ]))
                  : (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)("v-if", true)
              ], 8 /* PROPS */, _hoisted_19))
            : (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)("v-if", true),
          (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)(" Mini-map flyout (toggled by clicking pill) "),
          ($data.miniMapOpen && $options.currentPhoto && $options.currentPhoto.gps)
            ? ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)("aside", {
                key: 1,
                class: "ps-lb-minimap",
                onClick: _cache[9] || (_cache[9] = (0,vue__WEBPACK_IMPORTED_MODULE_0__.withModifiers)(() => {}, ["stop"]))
              }, [
                (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("div", _hoisted_24, [
                  (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("span", null, (0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)($options.locationLabel || $options.t('Location')), 1 /* TEXT */),
                  (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("button", {
                    class: "ps-lb-icon-btn",
                    "aria-label": $options.t('Close map'),
                    title: $options.t('Close map'),
                    onClick: _cache[8] || (_cache[8] = $event => ($data.miniMapOpen = false))
                  }, [
                    (0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_Close, { size: 18 })
                  ], 8 /* PROPS */, _hoisted_25)
                ]),
                (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("iframe", {
                  src: $options.osmEmbedUrl,
                  class: "ps-lb-minimap-frame",
                  loading: "lazy",
                  referrerpolicy: "no-referrer-when-downgrade",
                  title: $options.t('Location map')
                }, null, 8 /* PROPS */, _hoisted_26),
                ($options.osmExternalUrl)
                  ? ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)("a", {
                      key: 0,
                      href: $options.osmExternalUrl,
                      target: "_blank",
                      rel: "noopener noreferrer",
                      class: "ps-lb-minimap-link"
                    }, (0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)($options.t('View larger map')), 9 /* TEXT, PROPS */, _hoisted_27))
                  : (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)("v-if", true)
              ]))
            : (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)("v-if", true),
          (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)(" Details flyout (people/subjects/camera/filename) "),
          ($data.infoOpen && $options.currentPhoto)
            ? ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)("aside", {
                key: 2,
                class: "ps-lb-details",
                onClick: _cache[12] || (_cache[12] = (0,vue__WEBPACK_IMPORTED_MODULE_0__.withModifiers)(() => {}, ["stop"]))
              }, [
                (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("header", _hoisted_28, [
                  (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("h3", null, (0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)($options.t('Details')), 1 /* TEXT */),
                  (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("button", {
                    class: "ps-lb-icon-btn",
                    "aria-label": $options.t('Close details'),
                    title: $options.t('Close details'),
                    onClick: _cache[10] || (_cache[10] = $event => ($data.infoOpen = false))
                  }, [
                    (0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_Close, { size: 18 })
                  ], 8 /* PROPS */, _hoisted_29)
                ]),
                (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("dl", _hoisted_30, [
                  ($options.hasPeople)
                    ? ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)(vue__WEBPACK_IMPORTED_MODULE_0__.Fragment, { key: 0 }, [
                        (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("dt", null, (0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)($options.t('People')), 1 /* TEXT */),
                        (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("dd", null, [
                          ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(true), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)(vue__WEBPACK_IMPORTED_MODULE_0__.Fragment, null, (0,vue__WEBPACK_IMPORTED_MODULE_0__.renderList)($options.currentPhoto.people, (p) => {
                            return ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)("span", {
                              key: p,
                              class: "ps-lb-chip"
                            }, (0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)(p), 1 /* TEXT */))
                          }), 128 /* KEYED_FRAGMENT */))
                        ])
                      ], 64 /* STABLE_FRAGMENT */))
                    : (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)("v-if", true),
                  ($options.hasSubjects)
                    ? ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)(vue__WEBPACK_IMPORTED_MODULE_0__.Fragment, { key: 1 }, [
                        (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("dt", null, (0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)($options.t('Subjects')), 1 /* TEXT */),
                        (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("dd", null, [
                          ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(true), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)(vue__WEBPACK_IMPORTED_MODULE_0__.Fragment, null, (0,vue__WEBPACK_IMPORTED_MODULE_0__.renderList)($options.currentPhoto.subjects, (s) => {
                            return ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)("span", {
                              key: s,
                              class: "ps-lb-chip"
                            }, (0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)(s), 1 /* TEXT */))
                          }), 128 /* KEYED_FRAGMENT */))
                        ])
                      ], 64 /* STABLE_FRAGMENT */))
                    : (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)("v-if", true),
                  ($options.currentPhoto.camera)
                    ? ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)(vue__WEBPACK_IMPORTED_MODULE_0__.Fragment, { key: 2 }, [
                        (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("dt", null, (0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)($options.t('Camera')), 1 /* TEXT */),
                        (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("dd", null, (0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)($options.currentPhoto.camera), 1 /* TEXT */)
                      ], 64 /* STABLE_FRAGMENT */))
                    : (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)("v-if", true),
                  ($options.currentPhoto.name)
                    ? ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)(vue__WEBPACK_IMPORTED_MODULE_0__.Fragment, { key: 3 }, [
                        (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("dt", null, (0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)($options.t('File')), 1 /* TEXT */),
                        (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("dd", _hoisted_31, [
                          ($options.canOpenInFiles())
                            ? ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)("button", {
                                key: 0,
                                type: "button",
                                class: "ps-lb-filename-link",
                                title: $options.t('Show in Files'),
                                onClick: _cache[11] || (_cache[11] = (...args) => ($options.openInFiles && $options.openInFiles(...args)))
                              }, [
                                (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("span", null, (0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)($options.currentPhoto.name), 1 /* TEXT */),
                                (0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_OpenInNew, { size: 14 })
                              ], 8 /* PROPS */, _hoisted_32))
                            : ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)("span", _hoisted_33, (0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)($options.currentPhoto.name), 1 /* TEXT */))
                        ])
                      ], 64 /* STABLE_FRAGMENT */))
                    : (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)("v-if", true)
                ])
              ]))
            : (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)("v-if", true)
        ], 544 /* NEED_HYDRATION, NEED_PATCH */))
      : (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)("v-if", true)
  ]))
}

/***/ }

}]);
//# sourceMappingURL=intravox-src_components_PhotoLightbox_vue.b935a4a2.js.map