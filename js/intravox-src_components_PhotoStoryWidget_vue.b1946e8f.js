"use strict";
(self["webpackChunknextcloud_intravox"] = self["webpackChunknextcloud_intravox"] || []).push([["src_components_PhotoStoryWidget_vue"],{

/***/ "./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/dist/stylePostLoader.js!./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/PhotoStoryWidget.vue?vue&type=style&index=0&id=745c96b7&scoped=true&lang=css"
/*!*****************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/dist/stylePostLoader.js!./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/PhotoStoryWidget.vue?vue&type=style&index=0&id=745c96b7&scoped=true&lang=css ***!
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
.photo-story-widget[data-v-745c96b7] {
  width: 100%;
  margin: 12px 0;
  container-type: inline-size;
  position: relative;
}
.ps-scroll-sentinel[data-v-745c96b7] {
  display: flex;
  justify-content: center;
  align-items: center;
  min-height: 60px;
  padding: 24px 0;
}
.ps-loading-more[data-v-745c96b7] {
  font-size: 13px;
  color: var(--ps-text-muted, var(--color-text-maxcontrast));
}
.ps-truncated[data-v-745c96b7] {
  margin: 16px 0;
  padding: 10px 14px;
  background: var(--color-warning-background);
  border: 1px solid var(--color-warning);
  border-radius: 8px;
  font-size: 13px;
  color: var(--ps-text-muted, var(--color-text-maxcontrast));
}

/* Year-jump scrubber: floats top-right inside the widget, sticky to viewport */
.ps-year-scrubber[data-v-745c96b7] {
  position: sticky;
  top: 80px;
  float: right;
  margin: 0 0 0 12px;
  display: flex;
  flex-direction: column;
  gap: 2px;
  padding: 6px 4px;
  /* Theme-aware translucent backdrop (dark mode keeps the same blur but
     against a dark NC background rather than hard-coded white). */
  background: color-mix(in srgb, var(--color-main-background) 70%, transparent);
  backdrop-filter: blur(8px);
  -webkit-backdrop-filter: blur(8px);
  border-radius: 999px;
  z-index: 2;
  font-size: 11px;
  font-weight: 600;
  color: var(--ps-text-muted, var(--color-text-maxcontrast));
}
.ps-year-btn[data-v-745c96b7] {
  background: none;
  border: 0;
  cursor: pointer;
  padding: 4px 8px;
  border-radius: 999px;
  color: inherit;
  font: inherit;
  transition: background 0.15s ease, color 0.15s ease;
}
.ps-year-btn[data-v-745c96b7]:hover {
  background: var(--color-primary);
  color: var(--color-primary-text);
}
@container (max-width: 500px) {
.ps-year-scrubber[data-v-745c96b7] {
    display: none;
}
}
.ps-map-embed[data-v-745c96b7] {
  margin-bottom: 16px;
}
.ps-loading[data-v-745c96b7] {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
  gap: 8px;
}
.ps-skeleton-tile[data-v-745c96b7] {
  aspect-ratio: 1 / 1;
  background: var(--color-background-hover);
  border-radius: var(--border-radius);
  animation: ps-pulse-745c96b7 1.5s infinite ease-in-out;
}
@keyframes ps-pulse-745c96b7 {
0%, 100% { opacity: 0.6;
}
50% { opacity: 1;
}
}
.ps-error[data-v-745c96b7],
.ps-empty[data-v-745c96b7] {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 12px;
  padding: 40px 20px;
  color: var(--ps-text-muted, var(--color-text-maxcontrast));
  text-align: center;
}
.ps-error[data-v-745c96b7] {
  color: var(--color-error);
}
.ps-empty[data-v-745c96b7] {
  background: var(--color-background-hover);
  border: 2px dashed var(--color-border);
  border-radius: var(--border-radius-large);
}
.ps-empty small[data-v-745c96b7] {
  font-size: 12px;
  opacity: 0.7;
}
.ps-empty-hint[data-v-745c96b7] {
  /* Secondary hint that appears below the empty-state path. Slightly larger
   * line-height + max-width so the longer text wraps nicely. */
  display: block;
  max-width: 480px;
  margin-top: 8px;
  line-height: 1.4;
}

/* Timeline / on-this-day day section */
.ps-day[data-v-745c96b7],
.ps-year[data-v-745c96b7] {
  margin-bottom: 24px;
}
.ps-day-header[data-v-745c96b7] {
  margin-bottom: 8px;
}
.ps-day-label[data-v-745c96b7] {
  margin: 0;
  font-size: 16px;
  font-weight: 600;
  color: var(--ps-text, var(--color-main-text));
}
.ps-day-location[data-v-745c96b7] {
  margin: 2px 0 0 0;
  font-size: 12px;
  color: var(--ps-text-muted, var(--color-text-maxcontrast));
}

/* Grids */
.ps-grid[data-v-745c96b7] {
  display: grid;
  gap: 8px;
}
.ps-grid--responsive[data-v-745c96b7] {
  grid-template-columns: repeat(3, 1fr);
}
@container (max-width: 700px) {
.ps-grid--responsive[data-v-745c96b7] {
    grid-template-columns: repeat(2, 1fr);
}
}
@container (max-width: 400px) {
.ps-grid--responsive[data-v-745c96b7] {
    grid-template-columns: 1fr;
}
}
.ps-grid--two[data-v-745c96b7] {
  grid-template-columns: repeat(2, 1fr);
}
@container (max-width: 500px) {
.ps-grid--two[data-v-745c96b7] {
    grid-template-columns: 1fr;
}
}
.ps-grid--masonry[data-v-745c96b7] {
  grid-template-columns: repeat(var(--ps-grid-cols, 3), 1fr);
  grid-auto-rows: 120px;
}
@container (max-width: 700px) {
.ps-grid--masonry[data-v-745c96b7] {
    grid-template-columns: repeat(2, 1fr);
}
}
@container (max-width: 400px) {
.ps-grid--masonry[data-v-745c96b7] {
    grid-template-columns: 1fr;
}
}

/* Photo tile */
[data-v-745c96b7] .ps-tile {
  position: relative;
  margin: 0;
  border-radius: var(--border-radius-large);
  overflow: hidden;
  cursor: pointer;
  background: var(--color-background-dark);
  transition: transform 0.2s ease, box-shadow 0.2s ease;
  aspect-ratio: 1 / 1;
  height: 100%;
}
[data-v-745c96b7] .ps-grid--masonry .ps-tile {
  aspect-ratio: unset;
}
[data-v-745c96b7] .ps-tile:hover,[data-v-745c96b7] .ps-tile:focus-visible {
  transform: scale(1.02);
  box-shadow: 0 4px 14px rgba(0, 0, 0, 0.15);
  outline: none;
}
[data-v-745c96b7] .ps-tile-img {
  width: 100%;
  height: 100%;
  object-fit: cover;
  display: block;
}

/* Video tile: centered play-button overlay on top of the preview / placeholder */
[data-v-745c96b7] .ps-tile-video-badge {
  position: absolute;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%);
  width: 44px;
  height: 44px;
  border-radius: 50%;
  background: rgba(0, 0, 0, 0.55);
  color: #fff;
  display: flex;
  align-items: center;
  justify-content: center;
  pointer-events: none;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.35);
  transition: transform 0.15s ease, background 0.15s ease;
}
[data-v-745c96b7] .ps-tile:hover .ps-tile-video-badge,[data-v-745c96b7] .ps-tile:focus .ps-tile-video-badge {
  background: rgba(0, 0, 0, 0.75);
  transform: translate(-50%, -50%) scale(1.08);
}
[data-v-745c96b7] .ps-tile-video-badge svg {
  margin-left: 2px; /* visual centering — play triangle leans right */
}
[data-v-745c96b7] .ps-tile-caption {
  position: absolute;
  left: 0;
  right: 0;
  bottom: 0;
  padding: 8px 10px;
  font-size: 12px;
  background: linear-gradient(180deg, transparent, rgba(0, 0, 0, 0.65));
  color: #fff;
  opacity: 0;
  transition: opacity 0.2s ease;
}
[data-v-745c96b7] .ps-tile:hover .ps-tile-caption,[data-v-745c96b7] .ps-tile:focus-visible .ps-tile-caption {
  opacity: 1;
}

/* Placeholder for files without a thumbnail (RAW without preview-provider, missing previews) */
[data-v-745c96b7] .ps-tile--placeholder {
  background: linear-gradient(135deg, var(--color-background-dark) 0%, var(--color-main-background) 100%);
  border: 1px solid var(--color-border);
}
[data-v-745c96b7] .ps-tile-placeholder {
  width: 100%;
  height: 100%;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 8px;
  padding: 12px;
  text-align: center;
  color: var(--ps-text, var(--color-main-text));
}
[data-v-745c96b7] .ps-tile-placeholder-badge {
  font-family: var(--font-face, monospace);
  font-size: 11px;
  font-weight: 700;
  letter-spacing: 1px;
  padding: 4px 10px;
  border-radius: 999px;
  background: var(--color-primary-element);
  color: var(--color-primary-element-text);
}
[data-v-745c96b7] .ps-tile-placeholder-meta {
  display: flex;
  flex-direction: column;
  gap: 2px;
  font-size: 11px;
  line-height: 1.3;
  word-break: break-word;
  max-width: 100%;
}
[data-v-745c96b7] .ps-tile-placeholder-meta strong {
  font-size: 12px;
  font-weight: 600;
}
[data-v-745c96b7] .ps-tile-placeholder-meta span {
  color: var(--ps-text-muted, var(--color-text-maxcontrast));
}
[data-v-745c96b7] .ps-tile-placeholder-meta small {
  color: var(--ps-text-muted, var(--color-text-maxcontrast));
  font-size: 10px;
}

/* Highlights hero */
.ps-highlights[data-v-745c96b7] {
  display: flex;
  flex-direction: column;
  gap: 16px;
}
.ps-highlight-hero[data-v-745c96b7] {
  position: relative;
  width: 100%;
  max-height: 45vh;
  aspect-ratio: 16 / 9;
  overflow: hidden;
  border-radius: var(--border-radius-large);
  cursor: pointer;
}
.ps-hero-img[data-v-745c96b7] {
  width: 100%;
  height: 100%;
  max-height: 45vh;
  object-fit: cover;
  display: block;
  transition: transform 0.4s ease;
}
.ps-highlight-hero:hover .ps-hero-img[data-v-745c96b7] {
  transform: scale(1.02);
}
.ps-hero-caption[data-v-745c96b7] {
  position: absolute;
  left: 0;
  right: 0;
  bottom: 0;
  padding: 20px;
  background: linear-gradient(180deg, transparent, rgba(0, 0, 0, 0.75));
  color: #fff;
  display: flex;
  flex-direction: column;
  gap: 2px;
}
.ps-hero-caption strong[data-v-745c96b7] {
  font-size: 18px;
}
.ps-hero-caption span[data-v-745c96b7] {
  font-size: 13px;
  opacity: 0.85;
}

/* =========================================================================
   Timeline visual styles (Phase 2.2)
   ========================================================================= */

/* ---- Magazine ---- */
.ps-timeline--magazine[data-v-745c96b7] {
  font-family: 'Georgia', 'Source Serif Pro', 'Iowan Old Style', serif;
}
.ps-day-mag[data-v-745c96b7] {
  margin-bottom: 80px;
}

/* Transparent header — inherits the row/widget background instead of forcing
 * a white block over a themed row. Hairline underline keeps the visual
 * separator. See file-story-widget.vue::fs-day-header for the same pattern. */
.ps-day-mag-header[data-v-745c96b7] {
  background: transparent;
  text-align: center;
  padding: 16px 0 12px;
  margin-bottom: 24px;
  border-bottom: 1px solid var(--color-border);
}

/* Mini-map styling — Magazine: editorial wide banner */
.ps-day-mag-map[data-v-745c96b7] {
  margin: 0 0 28px;
  box-shadow: 0 2px 6px rgba(0, 0, 0, 0.08);
}

/* Mini-map styling — Apple: small, tucked above header */
.ps-day-apple-map[data-v-745c96b7] {
  margin: 0 0 12px;
  border-radius: 8px;
}

/* Mini-map styling — Travelogue: between header + grid, reisdagboek-feel */
.ps-day-trav-map[data-v-745c96b7] {
  margin: 4px 0 12px;
  box-shadow: 0 1px 4px rgba(0, 0, 0, 0.1);
}
.ps-day-mag-date[data-v-745c96b7] {
  margin: 0;
  font-size: 32px;
  font-weight: 400;
  letter-spacing: -0.02em;
  color: var(--ps-text, var(--color-main-text));
  font-family: inherit;
}
.ps-day-mag-loc[data-v-745c96b7] {
  margin: 4px 0 0 0;
  font-style: italic;
  color: var(--ps-text-muted, var(--color-text-maxcontrast));
  font-size: 16px;
}
.ps-mag-grid[data-v-745c96b7] {
  display: grid;
  grid-template-columns: repeat(6, 1fr);
  gap: 24px;
}
@container (max-width: 900px) {
.ps-mag-grid[data-v-745c96b7] {
    grid-template-columns: repeat(4, 1fr);
}
}
@container (max-width: 600px) {
.ps-mag-grid[data-v-745c96b7] {
    grid-template-columns: repeat(2, 1fr);
    gap: 12px;
}
.ps-mag-tile[data-v-745c96b7] {
    /* On narrow widths a 3-column span overflows; clamp to grid-width */
    grid-column: span 2 !important;
}
}
.ps-mag-tile[data-v-745c96b7] {
  margin: 0;
  aspect-ratio: 4 / 3;
  overflow: hidden;
  border-radius: 2px; /* editorial look — almost square corners */
  cursor: pointer;
  background: var(--color-background-dark);
  transition: transform 0.25s ease, box-shadow 0.25s ease;
}
.ps-mag-tile[data-v-745c96b7]:hover,
.ps-mag-tile[data-v-745c96b7]:focus-visible {
  transform: scale(1.01);
  box-shadow: 0 6px 22px rgba(0, 0, 0, 0.18);
  outline: none;
}
.ps-mag-img[data-v-745c96b7] {
  width: 100%;
  height: 100%;
  object-fit: cover;
  display: block;
}

/* ---- Apple ---- */
.ps-timeline--apple[data-v-745c96b7] {
  /* inherit system font — do not override */
}
.ps-day-apple[data-v-745c96b7] {
  margin-bottom: 32px;
}
.ps-day-apple-header[data-v-745c96b7] {
  background: transparent;
  display: flex;
  justify-content: space-between;
  align-items: baseline;
  gap: 12px;
  padding: 10px 4px 4px;
  margin-top: 4px;
  margin-bottom: 12px;
  border-bottom: 1px solid var(--color-border);
}
.ps-day-apple-date[data-v-745c96b7] {
  margin: 0;
  font-size: 22px;
  font-weight: 600;
  color: var(--ps-text, var(--color-main-text));
}
.ps-day-apple-loc[data-v-745c96b7] {
  font-size: 13px;
  color: var(--ps-text-muted, var(--color-text-maxcontrast));
  display: inline-flex;
  align-items: center;
  gap: 4px;
}
.ps-apple-grid[data-v-745c96b7] {
  display: grid;
  grid-template-columns: repeat(5, 1fr);
  gap: 4px;
}
@container (max-width: 900px) {
.ps-apple-grid[data-v-745c96b7] {
    grid-template-columns: repeat(4, 1fr);
}
}
@container (max-width: 600px) {
.ps-apple-grid[data-v-745c96b7] {
    grid-template-columns: repeat(3, 1fr);
}
}
[data-v-745c96b7] .ps-timeline--apple .ps-tile {
  aspect-ratio: 1 / 1;
  border-radius: 6px;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.08);
  overflow: hidden;
}

/* ---- Travelogue (Polarsteps) ---- */
.ps-timeline--travelogue[data-v-745c96b7] {
  /* outer styling lives on each day section so the rail aligns per-day */
}
.ps-day-trav[data-v-745c96b7] {
  display: grid;
  grid-template-columns: 60px 1fr;
  gap: 16px;
  margin-bottom: 24px;
}
.ps-trav-rail[data-v-745c96b7] {
  position: relative;
}
.ps-trav-rail[data-v-745c96b7]::before {
  content: '';
  position: absolute;
  top: 0;
  bottom: -24px; /* extend into the gap so the line continues between days */
  left: 30px;
  width: 2px;
  background: var(--color-border);
}
.ps-trav-bullet[data-v-745c96b7] {
  width: 14px;
  height: 14px;
  border-radius: 50%;
  background: var(--color-primary);
  position: absolute;
  left: 24px;
  top: 14px;
  box-shadow: 0 0 0 4px var(--color-main-background);
  z-index: 1;
}
.ps-trav-content[data-v-745c96b7] {
  min-width: 0;
}
.ps-day-trav-header[data-v-745c96b7] {
  background: transparent;
  padding: 10px 0 4px;
  margin-top: 4px;
  margin-bottom: 4px;
  border-bottom: 1px solid var(--color-border);
}
.ps-day-trav-date[data-v-745c96b7] {
  margin: 0 0 4px 0;
  font-size: 18px;
  font-weight: 600;
  color: var(--ps-text, var(--color-main-text));
}
.ps-day-trav-loc[data-v-745c96b7] {
  margin: 0 0 12px 0;
  font-size: 13px;
  color: var(--ps-text-muted, var(--color-text-maxcontrast));
  display: flex;
  align-items: center;
  gap: 4px;
}
.ps-trav-grid[data-v-745c96b7] {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 8px;
}
@container (max-width: 500px) {
.ps-day-trav[data-v-745c96b7] {
    grid-template-columns: 40px 1fr;
    gap: 8px;
}
.ps-trav-rail[data-v-745c96b7]::before {
    left: 20px;
}
.ps-trav-bullet[data-v-745c96b7] {
    left: 14px;
}
.ps-trav-grid[data-v-745c96b7] {
    grid-template-columns: 1fr;
}
}
@media (prefers-reduced-motion: reduce) {
.ps-tile-skeleton[data-v-745c96b7],
  .ps-day-skeleton[data-v-745c96b7] {
    animation: none;
}
}

/* Dark row-bg contrast lifts. Photo tiles already have their own surfaces
   (image previews, gradient overlays), so the widget only needs to recolor
   the day/section headers and adjust separators to stay legible. */
.photo-story-widget.ps--on-dark[data-v-745c96b7] .ps-day-header,
.photo-story-widget.ps--on-dark[data-v-745c96b7] .ps-section-header {
  border-bottom-color: rgba(255, 255, 255, 0.25);
}
.photo-story-widget.ps--on-dark[data-v-745c96b7] .ps-day-location,
.photo-story-widget.ps--on-dark[data-v-745c96b7] .ps-day-mag-loc,
.photo-story-widget.ps--on-dark[data-v-745c96b7] .ps-day-apple-loc,
.photo-story-widget.ps--on-dark[data-v-745c96b7] .ps-day-trav-loc {
  opacity: 0.82;
}
.photo-story-widget.ps--on-dark[data-v-745c96b7] .ps-tile-skeleton,
.photo-story-widget.ps--on-dark[data-v-745c96b7] .ps-day-skeleton {
  background: rgba(255, 255, 255, 0.12);
}
`, "",{"version":3,"sources":["webpack://./src/components/PhotoStoryWidget.vue"],"names":[],"mappings":";AAk+BA;EACE,WAAW;EACX,cAAc;EACd,2BAA2B;EAC3B,kBAAkB;AACpB;AAEA;EACE,aAAa;EACb,uBAAuB;EACvB,mBAAmB;EACnB,gBAAgB;EAChB,eAAe;AACjB;AAEA;EACE,eAAe;EACf,0DAA0D;AAC5D;AAEA;EACE,cAAc;EACd,kBAAkB;EAClB,2CAA2C;EAC3C,sCAAsC;EACtC,kBAAkB;EAClB,eAAe;EACf,0DAA0D;AAC5D;;AAEA,+EAA+E;AAC/E;EACE,gBAAgB;EAChB,SAAS;EACT,YAAY;EACZ,kBAAkB;EAClB,aAAa;EACb,sBAAsB;EACtB,QAAQ;EACR,gBAAgB;EAChB;kEACgE;EAChE,6EAA6E;EAC7E,0BAA0B;EAC1B,kCAAkC;EAClC,oBAAoB;EACpB,UAAU;EACV,eAAe;EACf,gBAAgB;EAChB,0DAA0D;AAC5D;AAEA;EACE,gBAAgB;EAChB,SAAS;EACT,eAAe;EACf,gBAAgB;EAChB,oBAAoB;EACpB,cAAc;EACd,aAAa;EACb,mDAAmD;AACrD;AAEA;EACE,gCAAgC;EAChC,gCAAgC;AAClC;AAEA;AACE;IACE,aAAa;AACf;AACF;AAEA;EACE,mBAAmB;AACrB;AAEA;EACE,aAAa;EACb,4DAA4D;EAC5D,QAAQ;AACV;AAEA;EACE,mBAAmB;EACnB,yCAAyC;EACzC,mCAAmC;EACnC,sDAA6C;AAC/C;AAEA;AACE,WAAW,YAAY;AAAE;AACzB,MAAM,UAAU;AAAE;AACpB;AAEA;;EAEE,aAAa;EACb,sBAAsB;EACtB,mBAAmB;EACnB,uBAAuB;EACvB,SAAS;EACT,kBAAkB;EAClB,0DAA0D;EAC1D,kBAAkB;AACpB;AAEA;EACE,yBAAyB;AAC3B;AAEA;EACE,yCAAyC;EACzC,sCAAsC;EACtC,yCAAyC;AAC3C;AAEA;EACE,eAAe;EACf,YAAY;AACd;AAEA;EACE;+DAC6D;EAC7D,cAAc;EACd,gBAAgB;EAChB,eAAe;EACf,gBAAgB;AAClB;;AAEA,uCAAuC;AACvC;;EAEE,mBAAmB;AACrB;AAEA;EACE,kBAAkB;AACpB;AAEA;EACE,SAAS;EACT,eAAe;EACf,gBAAgB;EAChB,6CAA6C;AAC/C;AAEA;EACE,iBAAiB;EACjB,eAAe;EACf,0DAA0D;AAC5D;;AAEA,UAAU;AACV;EACE,aAAa;EACb,QAAQ;AACV;AAEA;EACE,qCAAqC;AACvC;AAEA;AACE;IACE,qCAAqC;AACvC;AACF;AAEA;AACE;IACE,0BAA0B;AAC5B;AACF;AAEA;EACE,qCAAqC;AACvC;AAEA;AACE;IACE,0BAA0B;AAC5B;AACF;AAEA;EACE,0DAA0D;EAC1D,qBAAqB;AACvB;AAEA;AACE;IACE,qCAAqC;AACvC;AACF;AAEA;AACE;IACE,0BAA0B;AAC5B;AACF;;AAEA,eAAe;AACf;EACE,kBAAkB;EAClB,SAAS;EACT,yCAAyC;EACzC,gBAAgB;EAChB,eAAe;EACf,wCAAwC;EACxC,qDAAqD;EACrD,mBAAmB;EACnB,YAAY;AACd;AAEA;EACE,mBAAmB;AACrB;AAEA;EAEE,sBAAsB;EACtB,0CAA0C;EAC1C,aAAa;AACf;AAEA;EACE,WAAW;EACX,YAAY;EACZ,iBAAiB;EACjB,cAAc;AAChB;;AAEA,iFAAiF;AACjF;EACE,kBAAkB;EAClB,QAAQ;EACR,SAAS;EACT,gCAAgC;EAChC,WAAW;EACX,YAAY;EACZ,kBAAkB;EAClB,+BAA+B;EAC/B,WAAW;EACX,aAAa;EACb,mBAAmB;EACnB,uBAAuB;EACvB,oBAAoB;EACpB,yCAAyC;EACzC,uDAAuD;AACzD;AAEA;EAEE,+BAA+B;EAC/B,4CAA4C;AAC9C;AAEA;EACE,gBAAgB,EAAE,iDAAiD;AACrE;AAEA;EACE,kBAAkB;EAClB,OAAO;EACP,QAAQ;EACR,SAAS;EACT,iBAAiB;EACjB,eAAe;EACf,qEAAqE;EACrE,WAAW;EACX,UAAU;EACV,6BAA6B;AAC/B;AAEA;EAEE,UAAU;AACZ;;AAEA,+FAA+F;AAC/F;EACE,uGAAuG;EACvG,qCAAqC;AACvC;AAEA;EACE,WAAW;EACX,YAAY;EACZ,aAAa;EACb,sBAAsB;EACtB,mBAAmB;EACnB,uBAAuB;EACvB,QAAQ;EACR,aAAa;EACb,kBAAkB;EAClB,6CAA6C;AAC/C;AAEA;EACE,wCAAwC;EACxC,eAAe;EACf,gBAAgB;EAChB,mBAAmB;EACnB,iBAAiB;EACjB,oBAAoB;EACpB,wCAAwC;EACxC,wCAAwC;AAC1C;AAEA;EACE,aAAa;EACb,sBAAsB;EACtB,QAAQ;EACR,eAAe;EACf,gBAAgB;EAChB,sBAAsB;EACtB,eAAe;AACjB;AAEA;EACE,eAAe;EACf,gBAAgB;AAClB;AAEA;EACE,0DAA0D;AAC5D;AAEA;EACE,0DAA0D;EAC1D,eAAe;AACjB;;AAEA,oBAAoB;AACpB;EACE,aAAa;EACb,sBAAsB;EACtB,SAAS;AACX;AAEA;EACE,kBAAkB;EAClB,WAAW;EACX,gBAAgB;EAChB,oBAAoB;EACpB,gBAAgB;EAChB,yCAAyC;EACzC,eAAe;AACjB;AAEA;EACE,WAAW;EACX,YAAY;EACZ,gBAAgB;EAChB,iBAAiB;EACjB,cAAc;EACd,+BAA+B;AACjC;AAEA;EACE,sBAAsB;AACxB;AAEA;EACE,kBAAkB;EAClB,OAAO;EACP,QAAQ;EACR,SAAS;EACT,aAAa;EACb,qEAAqE;EACrE,WAAW;EACX,aAAa;EACb,sBAAsB;EACtB,QAAQ;AACV;AAEA;EACE,eAAe;AACjB;AAEA;EACE,eAAe;EACf,aAAa;AACf;;AAEA;;8EAE8E;;AAE9E,uBAAuB;AACvB;EACE,oEAAoE;AACtE;AAEA;EACE,mBAAmB;AACrB;;AAEA;;8EAE8E;AAC9E;EACE,uBAAuB;EACvB,kBAAkB;EAClB,oBAAoB;EACpB,mBAAmB;EACnB,4CAA4C;AAC9C;;AAEA,uDAAuD;AACvD;EACE,gBAAgB;EAChB,yCAAyC;AAC3C;;AAEA,yDAAyD;AACzD;EACE,gBAAgB;EAChB,kBAAkB;AACpB;;AAEA,2EAA2E;AAC3E;EACE,kBAAkB;EAClB,wCAAwC;AAC1C;AAEA;EACE,SAAS;EACT,eAAe;EACf,gBAAgB;EAChB,uBAAuB;EACvB,6CAA6C;EAC7C,oBAAoB;AACtB;AAEA;EACE,iBAAiB;EACjB,kBAAkB;EAClB,0DAA0D;EAC1D,eAAe;AACjB;AAEA;EACE,aAAa;EACb,qCAAqC;EACrC,SAAS;AACX;AAEA;AACE;IACE,qCAAqC;AACvC;AACF;AAEA;AACE;IACE,qCAAqC;IACrC,SAAS;AACX;AACA;IACE,oEAAoE;IACpE,8BAA8B;AAChC;AACF;AAEA;EACE,SAAS;EACT,mBAAmB;EACnB,gBAAgB;EAChB,kBAAkB,EAAE,2CAA2C;EAC/D,eAAe;EACf,wCAAwC;EACxC,uDAAuD;AACzD;AAEA;;EAEE,sBAAsB;EACtB,0CAA0C;EAC1C,aAAa;AACf;AAEA;EACE,WAAW;EACX,YAAY;EACZ,iBAAiB;EACjB,cAAc;AAChB;;AAEA,oBAAoB;AACpB;EACE,0CAA0C;AAC5C;AAEA;EACE,mBAAmB;AACrB;AAEA;EACE,uBAAuB;EACvB,aAAa;EACb,8BAA8B;EAC9B,qBAAqB;EACrB,SAAS;EACT,qBAAqB;EACrB,eAAe;EACf,mBAAmB;EACnB,4CAA4C;AAC9C;AAEA;EACE,SAAS;EACT,eAAe;EACf,gBAAgB;EAChB,6CAA6C;AAC/C;AAEA;EACE,eAAe;EACf,0DAA0D;EAC1D,oBAAoB;EACpB,mBAAmB;EACnB,QAAQ;AACV;AAEA;EACE,aAAa;EACb,qCAAqC;EACrC,QAAQ;AACV;AAEA;AACE;IACE,qCAAqC;AACvC;AACF;AAEA;AACE;IACE,qCAAqC;AACvC;AACF;AAEA;EACE,mBAAmB;EACnB,kBAAkB;EAClB,yCAAyC;EACzC,gBAAgB;AAClB;;AAEA,sCAAsC;AACtC;EACE,uEAAuE;AACzE;AAEA;EACE,aAAa;EACb,+BAA+B;EAC/B,SAAS;EACT,mBAAmB;AACrB;AAEA;EACE,kBAAkB;AACpB;AAEA;EACE,WAAW;EACX,kBAAkB;EAClB,MAAM;EACN,aAAa,EAAE,2DAA2D;EAC1E,UAAU;EACV,UAAU;EACV,+BAA+B;AACjC;AAEA;EACE,WAAW;EACX,YAAY;EACZ,kBAAkB;EAClB,gCAAgC;EAChC,kBAAkB;EAClB,UAAU;EACV,SAAS;EACT,kDAAkD;EAClD,UAAU;AACZ;AAEA;EACE,YAAY;AACd;AAEA;EACE,uBAAuB;EACvB,mBAAmB;EACnB,eAAe;EACf,kBAAkB;EAClB,4CAA4C;AAC9C;AAEA;EACE,iBAAiB;EACjB,eAAe;EACf,gBAAgB;EAChB,6CAA6C;AAC/C;AAEA;EACE,kBAAkB;EAClB,eAAe;EACf,0DAA0D;EAC1D,aAAa;EACb,mBAAmB;EACnB,QAAQ;AACV;AAEA;EACE,aAAa;EACb,qCAAqC;EACrC,QAAQ;AACV;AAEA;AACE;IACE,+BAA+B;IAC/B,QAAQ;AACV;AACA;IACE,UAAU;AACZ;AACA;IACE,UAAU;AACZ;AACA;IACE,0BAA0B;AAC5B;AACF;AAEA;AACE;;IAEE,eAAe;AACjB;AACF;;AAEA;;mEAEmE;AACnE;;EAEE,8CAA8C;AAChD;AAEA;;;;EAIE,aAAa;AACf;AAEA;;EAEE,qCAAqC;AACvC","sourcesContent":["<template>\n  <div class=\"photo-story-widget\" :class=\"rowBgClass\" :style=\"rowBgStyle\">\n    <!-- Optional map at top -->\n    <PhotoStoryMap\n      v-if=\"config.showMap && config.folderPath && capabilities && capabilities.hasLocation && mapSettings.enabled\"\n      :folder-path=\"config.folderPath\"\n      class=\"ps-map-embed\"\n      @cluster-click=\"onClusterClick\"\n    />\n\n    <!-- Loading skeleton -->\n    <div v-if=\"loading\" class=\"ps-loading\" role=\"status\" aria-live=\"polite\" :aria-label=\"t('Loading photos')\">\n      <div v-for=\"i in 6\" :key=\"i\" class=\"ps-skeleton-tile\" aria-hidden=\"true\"></div>\n    </div>\n\n    <!-- Error -->\n    <div v-else-if=\"error\" class=\"ps-error\" role=\"alert\">\n      <AlertCircle :size=\"24\" aria-hidden=\"true\" />\n      <span>{{ error }}</span>\n      <NcButton type=\"secondary\" @click=\"fetch()\">\n        {{ t('Retry') }}\n      </NcButton>\n    </div>\n\n    <!-- No-access: minimal locked placeholder; deliberately hides the folder\n         name to avoid leaking the existence of a path the viewer can't see. -->\n    <div v-else-if=\"!photos.length && accessReason === 'folder_not_accessible'\" class=\"ps-empty ps-empty--locked\" role=\"status\">\n      <LockOutline :size=\"32\" aria-hidden=\"true\" />\n      <p>{{ t('You do not have access to this widget') }}</p>\n    </div>\n\n    <!-- Empty (accessible folder but no photos to show) -->\n    <div v-else-if=\"!photos.length\" class=\"ps-empty\" role=\"status\">\n      <ImageMultiple :size=\"32\" aria-hidden=\"true\" />\n      <p>{{ emptyMessage }}</p>\n      <small v-if=\"config.folderPath\">{{ config.folderPath }}</small>\n      <small v-if=\"showScanHint\" class=\"ps-empty-hint\">\n        {{ t('If you can see photos in the Files app but not here, the file index may be out of sync. Ask an admin to run \"occ files:scan\" for this folder.') }}\n      </small>\n    </div>\n\n    <!-- Timeline mode — Magazine style -->\n    <div\n      v-else-if=\"effectiveMode === 'timeline' && effectiveStyle === 'magazine'\"\n      class=\"ps-timeline ps-timeline--magazine\"\n    >\n      <section v-for=\"day in timelineDays\" :key=\"day.date\" :data-date=\"day.date\" class=\"ps-day-mag\">\n        <header class=\"ps-day-mag-header\">\n          <h2 class=\"ps-day-mag-date\">{{ formatLongDate(day.date) }}</h2>\n          <p v-if=\"day.location_summary\" class=\"ps-day-mag-loc\">{{ day.location_summary }}</p>\n        </header>\n        <PhotoStoryDayMap\n          v-if=\"showDayMaps() && day._hasGps && mapSettings.enabled\"\n          :tile-url=\"mapSettings.tile_url\"\n          :attribution=\"mapSettings.attribution\"\n          :max-zoom=\"mapSettings.max_zoom\"\n          :points=\"day._gpsPoints\"\n          :height=\"180\"\n          class=\"ps-day-mag-map\"\n          @photo-click=\"openLightboxByFileId($event)\"\n        />\n        <div class=\"ps-mag-grid\">\n          <figure\n            v-for=\"(photo, idx) in day.photos\"\n            :key=\"photo.file_id\"\n            class=\"ps-mag-tile\"\n            :style=\"magazineTileStyle(photo)\"\n            tabindex=\"0\"\n            role=\"button\"\n            :aria-label=\"t('Open photo {name}', { name: photo.name })\"\n            @click=\"openLightbox(globalIndex(day.photos, idx))\"\n            @keydown.enter=\"openLightbox(globalIndex(day.photos, idx))\"\n            @keydown.space.prevent=\"openLightbox(globalIndex(day.photos, idx))\"\n          >\n            <img\n              :src=\"previewUrl(photo, 800)\"\n              :alt=\"photo.location_display || photo.location || photo.name\"\n              loading=\"lazy\"\n              decoding=\"async\"\n              class=\"ps-mag-img\"\n            />\n          </figure>\n        </div>\n      </section>\n    </div>\n\n    <!-- Timeline mode — Apple style (default) -->\n    <div\n      v-else-if=\"effectiveMode === 'timeline' && effectiveStyle === 'apple'\"\n      class=\"ps-timeline ps-timeline--apple\"\n    >\n      <section v-for=\"day in timelineDays\" :key=\"day.date\" :data-date=\"day.date\" class=\"ps-day-apple\">\n        <header class=\"ps-day-apple-header\">\n          <h3 class=\"ps-day-apple-date\">{{ formatLongDate(day.date) }}</h3>\n          <span v-if=\"day.location_summary\" class=\"ps-day-apple-loc\">\n            <MapMarker :size=\"14\" />\n            {{ day.location_summary }}\n          </span>\n        </header>\n        <PhotoStoryDayMap\n          v-if=\"showDayMaps() && day._hasGps && mapSettings.enabled\"\n          :tile-url=\"mapSettings.tile_url\"\n          :attribution=\"mapSettings.attribution\"\n          :max-zoom=\"mapSettings.max_zoom\"\n          :points=\"day._gpsPoints\"\n          :height=\"140\"\n          class=\"ps-day-apple-map\"\n          @photo-click=\"openLightboxByFileId($event)\"\n        />\n        <div class=\"ps-apple-grid\">\n          <PhotoTile\n            v-for=\"(photo, idx) in day.photos\"\n            :key=\"photo.file_id\"\n            :photo=\"photo\"\n            :show-caption=\"false\"\n            @click=\"openLightbox(globalIndex(day.photos, idx))\"\n          />\n        </div>\n      </section>\n    </div>\n\n    <!-- Timeline mode — Travelogue style (Polarsteps-like rail) -->\n    <div\n      v-else-if=\"effectiveMode === 'timeline' && effectiveStyle === 'travelogue'\"\n      class=\"ps-timeline ps-timeline--travelogue\"\n    >\n      <section v-for=\"day in timelineDays\" :key=\"day.date\" :data-date=\"day.date\" class=\"ps-day-trav\">\n        <div class=\"ps-trav-rail\">\n          <div class=\"ps-trav-bullet\"></div>\n        </div>\n        <div class=\"ps-trav-content\">\n          <header class=\"ps-day-trav-header\">\n            <h3 class=\"ps-day-trav-date\">{{ formatLongDate(day.date) }}</h3>\n            <p v-if=\"day.location_summary\" class=\"ps-day-trav-loc\">\n              <MapMarker :size=\"14\" />\n              {{ day.location_summary }}\n            </p>\n          </header>\n          <PhotoStoryDayMap\n            v-if=\"showDayMaps() && day._hasGps && mapSettings.enabled\"\n            :tile-url=\"mapSettings.tile_url\"\n            :attribution=\"mapSettings.attribution\"\n            :max-zoom=\"mapSettings.max_zoom\"\n            :points=\"day._gpsPoints\"\n            :height=\"150\"\n            class=\"ps-day-trav-map\"\n            @photo-click=\"openLightboxByFileId($event)\"\n          />\n          <div class=\"ps-trav-grid\">\n            <PhotoTile\n              v-for=\"(photo, idx) in day.photos\"\n              :key=\"photo.file_id\"\n              :photo=\"photo\"\n              :show-caption=\"false\"\n              @click=\"openLightbox(globalIndex(day.photos, idx))\"\n            />\n          </div>\n        </div>\n      </section>\n    </div>\n\n    <!-- Highlights mode -->\n    <div v-else-if=\"effectiveMode === 'highlights'\" class=\"ps-highlights\">\n      <div\n        v-if=\"highlights.length\"\n        class=\"ps-highlight-hero\"\n        tabindex=\"0\"\n        role=\"button\"\n        :aria-label=\"t('Open photo {name}', { name: highlights[0].location || highlights[0].name })\"\n        @click=\"openLightbox(0)\"\n        @keydown.enter=\"openLightbox(0)\"\n        @keydown.space.prevent=\"openLightbox(0)\"\n      >\n        <img\n          :src=\"previewUrl(highlights[0], 1600)\"\n          :alt=\"highlights[0].location_display || highlights[0].location || highlights[0].name\"\n          loading=\"lazy\"\n          decoding=\"async\"\n          class=\"ps-hero-img\"\n        />\n        <div v-if=\"config.showCaptions !== false\" class=\"ps-hero-caption\">\n          <strong>{{ highlights[0].location || highlights[0].name }}</strong>\n          <span v-if=\"highlights[0].taken_at\">{{ formatDate(highlights[0].taken_at) }}</span>\n        </div>\n      </div>\n      <div class=\"ps-grid ps-grid--two\">\n        <PhotoTile\n          v-for=\"(photo, idx) in highlights.slice(1)\"\n          :key=\"photo.file_id\"\n          :photo=\"photo\"\n          :show-caption=\"config.showCaptions !== false\"\n          @click=\"openLightbox(idx + 1)\"\n        />\n      </div>\n    </div>\n\n    <!-- Grid mode -->\n    <div\n      v-else-if=\"effectiveMode === 'grid'\"\n      class=\"ps-grid ps-grid--masonry\"\n      :style=\"gridStyle\"\n    >\n      <PhotoTile\n        v-for=\"(photo, idx) in photos\"\n        :key=\"photo.file_id\"\n        :photo=\"photo\"\n        :show-caption=\"config.showCaptions !== false\"\n        :style=\"masonryItemStyle(photo)\"\n        @click=\"openLightbox(idx)\"\n      />\n    </div>\n\n    <!-- On this day mode -->\n    <div v-else-if=\"effectiveMode === 'on-this-day'\" class=\"ps-on-this-day\">\n      <section\n        v-for=\"(group, year) in onThisDayGroups\"\n        :key=\"year\"\n        class=\"ps-year\"\n      >\n        <header class=\"ps-day-header\">\n          <h4 class=\"ps-day-label\">{{ year }}</h4>\n        </header>\n        <div class=\"ps-grid ps-grid--responsive\">\n          <PhotoTile\n            v-for=\"photo in group\"\n            :key=\"photo.file_id\"\n            :photo=\"photo\"\n            :show-caption=\"config.showCaptions !== false\"\n            @click=\"openLightbox(photos.indexOf(photo))\"\n          />\n        </div>\n      </section>\n    </div>\n\n    <!-- Year-jump scrubber (Timeline-only, only if >1 year) -->\n    <aside\n      v-if=\"effectiveMode === 'timeline' && yearScrubber.length > 1\"\n      class=\"ps-year-scrubber\"\n      :aria-label=\"t('Jump to year')\"\n    >\n      <button\n        v-for=\"y in yearScrubber\"\n        :key=\"y\"\n        type=\"button\"\n        class=\"ps-year-btn\"\n        :title=\"t('Jump to {year}', { year: String(y) })\"\n        @click=\"scrollToYear(y)\"\n      >\n        {{ y }}\n      </button>\n    </aside>\n\n    <!-- Infinite-scroll sentinel + load-more indicator (folder-mode timeline/grid only) -->\n    <div\n      v-if=\"isPagedMode() && pagination.hasMore\"\n      ref=\"scrollSentinel\"\n      class=\"ps-scroll-sentinel\"\n      aria-hidden=\"true\"\n    >\n      <span v-if=\"loadingMore\" class=\"ps-loading-more\">{{ t('Loading more photos…') }}</span>\n    </div>\n\n    <!-- Truncation notice when server hit its hard cap -->\n    <div v-if=\"pagination.truncated\" class=\"ps-truncated\">\n      {{ t('Showing first {n} photos. Use filters or a more specific folder to narrow the selection.', { n: String(pagination.total) }) }}\n    </div>\n\n    <!-- Lightbox -->\n    <PhotoLightbox\n      v-if=\"lightboxVisible\"\n      :photos=\"photos\"\n      :start-index=\"lightboxIndex\"\n      :visible=\"lightboxVisible\"\n      @close=\"lightboxVisible = false\"\n    />\n  </div>\n</template>\n\n<script>\nimport { defineAsyncComponent } from 'vue';\nimport axios from '@nextcloud/axios';\nimport { generateUrl } from '@nextcloud/router';\nimport { translate as t, getCanonicalLocale } from '@nextcloud/l10n';\nimport { NcButton } from '@nextcloud/vue';\nimport AlertCircle from 'vue-material-design-icons/AlertCircle.vue';\nimport ImageMultiple from 'vue-material-design-icons/ImageMultiple.vue';\nimport MapMarker from 'vue-material-design-icons/MapMarker.vue';\nimport LockOutline from 'vue-material-design-icons/LockOutline.vue';\n\nimport { h, ref } from 'vue';\nconst PhotoTile = {\n  name: 'PhotoTile',\n  props: {\n    photo: { type: Object, required: true },\n    showCaption: { type: Boolean, default: true },\n  },\n  emits: ['click'],\n  setup(props, { emit }) {\n    const hasError = ref(false);\n    return () => {\n      // Route federated photos through IntraVox's preview proxy; local\n      // photos go straight to NC core preview to reuse NC's preview cache.\n      const src = props.photo.is_federated\n        ? generateUrl(`/apps/intravox/api/preview?file_id=${props.photo.file_id}&x=512&y=512`)\n        : generateUrl(`/core/preview?fileId=${props.photo.file_id}&x=512&y=512&a=true`);\n      const caption = props.photo.location_display || props.photo.location || '';\n      const dateStr = props.photo.taken_at\n        ? new Date(props.photo.taken_at).toLocaleDateString(getCanonicalLocale(), { day: 'numeric', month: 'short', year: 'numeric' })\n        : '';\n\n      const mime = String(props.photo.mime || '');\n      const isVideo = mime.startsWith('video/')\n        || /\\.(mov|mp4|m4v|avi|mkv|webm|wmv|mpg|mpeg|3gp)$/i.test(props.photo.name || '');\n      const isRawMime = mime.match(/x-(canon|nikon|sony|adobe|fuji|olympus|panasonic|pentax|samsung|sigma|dcraw)/i)\n        || /\\.(cr2|cr3|nef|arw|dng|raf|orf|rw2|pef|srw|x3f)$/i.test(props.photo.name || '');\n      let formatLabel;\n      if (isVideo) formatLabel = 'VIDEO';\n      else if (isRawMime) formatLabel = 'RAW';\n      else formatLabel = (props.photo.name || '').split('.').pop()?.toUpperCase() || '';\n\n      const tileChildren = [];\n\n      if (hasError.value) {\n        // Fallback placeholder when NC has no preview (e.g. video without ffmpeg server-side)\n        tileChildren.push(\n          h('div', { class: 'ps-tile-placeholder' }, [\n            h('div', { class: 'ps-tile-placeholder-badge' }, formatLabel),\n            h('div', { class: 'ps-tile-placeholder-meta' }, [\n              h('strong', null, props.photo.name),\n              dateStr ? h('span', null, dateStr) : null,\n              caption ? h('small', null, caption) : null,\n            ].filter(Boolean)),\n          ])\n        );\n      } else {\n        // Meaningful alt: prefer location-based description over filename.\n        // Decorative inside an already-labelled role=button parent would be\n        // tempting but screen-readers may treat the figure label loosely;\n        // keep a concise alt for image-only assistive contexts.\n        const altText = caption || props.photo.location || props.photo.name || '';\n        tileChildren.push(\n          h('img', {\n            src,\n            alt: altText,\n            loading: 'lazy',\n            decoding: 'async',\n            class: 'ps-tile-img',\n            onError: () => { hasError.value = true; },\n          })\n        );\n      }\n\n      // Play-button overlay for video tiles (visible on both successful preview and placeholder)\n      if (isVideo) {\n        tileChildren.push(\n          h('div', { class: 'ps-tile-video-badge', 'aria-hidden': 'true' }, [\n            h('svg', { width: 22, height: 22, viewBox: '0 0 24 24', fill: 'currentColor' }, [\n              h('path', { d: 'M8 5v14l11-7z' }),\n            ]),\n          ])\n        );\n      }\n\n      if (props.showCaption && caption && !hasError.value) {\n        tileChildren.push(h('figcaption', { class: 'ps-tile-caption' }, caption));\n      }\n\n      const accessibleLabel = caption || props.photo.name || 'photo';\n      return h('figure', {\n        class: ['ps-tile', { 'ps-tile--placeholder': hasError.value, 'ps-tile--video': isVideo }],\n        tabindex: 0,\n        role: 'button',\n        'aria-label': accessibleLabel,\n        onClick: () => emit('click'),\n        onKeydown: (e) => {\n          if (e.key === 'Enter' || e.key === ' ' || e.key === 'Spacebar') {\n            e.preventDefault();\n            emit('click');\n          }\n        },\n      }, tileChildren);\n    };\n  },\n};\n\nexport default {\n  name: 'PhotoStoryWidget',\n  components: {\n    AlertCircle,\n    ImageMultiple,\n    LockOutline,\n    NcButton,\n    MapMarker,\n    PhotoTile,\n    PhotoLightbox: defineAsyncComponent(() => import('./PhotoLightbox.vue')),\n    PhotoStoryMap: defineAsyncComponent(() => import('./PhotoStoryMap.vue')),\n    PhotoStoryDayMap: defineAsyncComponent(() => import('./PhotoStoryDayMap.vue')),\n  },\n  props: {\n    widget: { type: Object, required: true },\n    rowBackgroundColor: { type: String, default: '' },\n  },\n  emits: ['open-lightbox'],\n  data() {\n    return {\n      photos: [],\n      timeline: [],\n      highlights: [],\n      capabilities: null,\n      // Map settings from the backend (Phase 3.0): admin-configurable tile URL,\n      // attribution, max-zoom, plus a global enabled-flag.\n      mapSettings: {\n        enabled: true,\n        tile_url: 'https://tile.openstreetmap.org/{z}/{x}/{y}.png',\n        attribution: '© OpenStreetMap contributors',\n        max_zoom: 19,\n      },\n      loading: true,\n      error: null,\n      // Set to 'folder_not_accessible' when the backend returns a 404 with\n      // that reason — used by emptyMessage()/showScanHint() to surface a\n      // permission-aware empty-state instead of the generic \"no photos\".\n      accessReason: null,\n      lightboxVisible: false,\n      lightboxIndex: 0,\n      // Client-side memo keyed by fetchKey — instant restore when switching\n      // between modes (e.g. Timeline → Grid → Timeline) within the same session.\n      _payloadCache: new Map(),\n      // ETag from the last successful fetch (Phase 2.9.B2). Sent back as\n      // If-None-Match so the server can return 304 instead of recomputing.\n      _lastEtag: null,\n      // Pagination state for folder-mode timeline + grid.\n      pagination: {\n        offset: 0,\n        pageSize: 200,\n        total: 0,\n        hasMore: false,\n        truncated: false,\n      },\n      loadingMore: false,\n      _scrollObserver: null,\n      _abortController: null,\n    };\n  },\n  computed: {\n    config() {\n      return this.widget.config || {};\n    },\n    rowBgClass() {\n      const bg = String(this.rowBackgroundColor || '').trim();\n      if (!bg || bg === 'transparent') {\n        return '';\n      }\n      const dark = new Set([\n        'var(--color-primary-element)',\n        'var(--color-primary)',\n        'var(--color-error)',\n        'var(--color-success)',\n        'var(--color-warning)',\n      ]);\n      return dark.has(bg) ? 'ps--on-dark' : 'ps--on-tinted';\n    },\n    rowBgStyle() {\n      if (this.rowBgClass !== 'ps--on-dark') {\n        return {};\n      }\n      const textMap = {\n        'var(--color-primary-element)': 'var(--color-primary-element-text)',\n        'var(--color-primary)': 'var(--color-primary-text)',\n        'var(--color-error)': 'var(--color-error-text)',\n        'var(--color-success)': 'var(--color-success-text)',\n        'var(--color-warning)': 'var(--color-warning-text)',\n      };\n      const bg = String(this.rowBackgroundColor || '').trim();\n      const text = textMap[bg] || '#fff';\n      return {\n        '--ps-text': text,\n        '--ps-text-muted': text,\n      };\n    },\n    effectiveMode() {\n      const m = this.config.mode || 'timeline';\n      const allowed = ['timeline', 'highlights', 'grid', 'on-this-day'];\n      return allowed.includes(m) ? m : 'timeline';\n    },\n    effectiveStyle() {\n      const allowed = ['magazine', 'apple', 'travelogue'];\n      const s = this.config.style || 'apple';\n      return allowed.includes(s) ? s : 'apple';\n    },\n    emptyMessage() {\n      const crossFolder = !!this.config.allMetaVoxFolders;\n      const hasFilters = Array.isArray(this.config.metaVoxFilters) && this.config.metaVoxFilters.length > 0;\n      if (crossFolder && !hasFilters) {\n        return t('intravox', 'Add a filter to start');\n      }\n      if (!this.config.folderPath && !crossFolder) {\n        return t('intravox', 'No folder selected');\n      }\n      if (this.accessReason === 'folder_not_accessible') {\n        return t('intravox', 'You do not have access to this folder');\n      }\n      if (this.effectiveMode === 'on-this-day') {\n        return t('intravox', 'No photos taken on this day in previous years');\n      }\n      return t('intravox', 'No photos found');\n    },\n    /**\n     * Show the \"files may not be indexed yet\" hint only when it actually fits\n     * the scenario. Hiding it for filtered queries / on-this-day / cross-folder\n     * / no-access (each has its own most-likely explanation), showing it only\n     * when the user picked a normal folder and got zero results back.\n     */\n    showScanHint() {\n      const crossFolder = !!this.config.allMetaVoxFolders;\n      const hasFilters = Array.isArray(this.config.metaVoxFilters) && this.config.metaVoxFilters.length > 0;\n      if (crossFolder || hasFilters) return false;\n      if (!this.config.folderPath) return false;\n      if (this.accessReason === 'folder_not_accessible') return false;\n      if (this.effectiveMode === 'on-this-day') return false;\n      return true;\n    },\n    gridStyle() {\n      const cols = Math.min(Math.max(this.config.columns || 3, 2), 5);\n      return { '--ps-grid-cols': cols };\n    },\n    onThisDayGroups() {\n      // Photos already sorted year-desc by backend\n      const out = {};\n      for (const p of this.photos) {\n        if (!p.taken_at) continue;\n        const year = String(new Date(p.taken_at).getFullYear());\n        if (!out[year]) out[year] = [];\n        out[year].push(p);\n      }\n      return out;\n    },\n    timelineDays() {\n      // Backend already returns days in the requested sortOrder (paged path).\n      // We precompute `_gpsPoints` and `_hasGps` per day so the template doesn't\n      // call helper methods inside v-for — those create a new array per render\n      // pass and trick PhotoStoryDayMap into thinking its `points` prop changed,\n      // which re-instantiates Leaflet on every parent re-render.\n      let days;\n      if (this.timeline && this.timeline.length) {\n        days = this.timeline;\n      } else if (this.photos.length) {\n        const fmt = new Intl.DateTimeFormat(getCanonicalLocale(), { day: 'numeric', month: 'long', year: 'numeric' });\n        const sample = this.photos[0];\n        const ts = sample.taken_at ? new Date(sample.taken_at) : new Date(sample.mtime * 1000);\n        days = [{\n          date: ts.toISOString().slice(0, 10),\n          label: fmt.format(ts),\n          location_summary: null,\n          photos: this.photos,\n        }];\n      } else {\n        return [];\n      }\n      return days.map(day => {\n        const points = (day.photos || [])\n          .filter(p => p && p.gps && Number.isFinite(p.gps.lat) && Number.isFinite(p.gps.lon))\n          .map(p => ({ lat: p.gps.lat, lon: p.gps.lon, file_id: p.file_id, name: p.name }));\n        return { ...day, _gpsPoints: points, _hasGps: points.length > 0 };\n      });\n    },\n    // Stable key over only the request-changing config fields. Watching this\n    // (instead of the whole widget) prevents cosmetic editor changes (style,\n    // columns, toggles, title) from re-fetching the photo list.\n    fetchKey() {\n      const c = this.config || {};\n      return JSON.stringify({\n        f: c.folderPath || '',\n        m: c.mode || 'timeline',\n        l: c.limit ?? null,\n        s: c.sortOrder || 'desc',\n        sb: c.sortBy || 'mtime',\n        x: !!c.allMetaVoxFolders,\n        flt: Array.isArray(c.metaVoxFilters) ? c.metaVoxFilters : [],\n        dr: c.hideRawDuplicates !== false,\n      });\n    },\n    yearScrubber() {\n      // Distinct years present in the timeline, descending (newest first)\n      const years = new Set();\n      for (const day of this.timelineDays) {\n        const y = parseInt((day.date || '').slice(0, 4), 10);\n        if (Number.isFinite(y)) years.add(y);\n      }\n      return Array.from(years).sort((a, b) => b - a);\n    },\n  },\n  watch: {\n    // Only re-fetch when a DATA-relevant field changes (folder, mode, filters, limit).\n    // Cosmetic fields (style, columns, captions, map toggles, title) reuse the\n    // existing `photos` array — no HTTP round-trip needed.\n    fetchKey: {\n      handler(newKey, oldKey) {\n        if (newKey === oldKey) return;\n        // 700 ms debounce — see FileStoryWidget for the rationale. Prevents\n        // edit-mode mutation storms from stacking expensive listPhotos calls\n        // on Apache workers and crashing the subsequent save with 503.\n        clearTimeout(this._debounce);\n        this._debounce = setTimeout(() => this.fetch(), 700);\n      },\n    },\n  },\n  mounted() {\n    if (typeof requestIdleCallback === 'function') {\n      this._idleHandle = requestIdleCallback(() => this.fetch());\n    } else {\n      this.fetch();\n    }\n  },\n  beforeUnmount() {\n    clearTimeout(this._debounce);\n    if (this._idleHandle && typeof cancelIdleCallback === 'function') {\n      cancelIdleCallback(this._idleHandle);\n      this._idleHandle = null;\n    }\n    if (this._scrollObserver) {\n      this._scrollObserver.disconnect();\n      this._scrollObserver = null;\n    }\n    if (this._abortController) {\n      this._abortController.abort();\n      this._abortController = null;\n    }\n  },\n  methods: {\n    t(key, vars = {}) {\n      return t('intravox', key, vars);\n    },\n    // Whether the backend will paginate this request. Mirrors controller logic:\n    // only folder-mode timeline + grid go through the paged path.\n    isPagedMode() {\n      const crossFolder = !!this.config.allMetaVoxFolders;\n      const m = this.effectiveMode;\n      return !crossFolder && !!this.config.folderPath && (m === 'timeline' || m === 'grid');\n    },\n    async fetch() {\n      const crossFolder = !!this.config.allMetaVoxFolders;\n      const hasFilters = Array.isArray(this.config.metaVoxFilters) && this.config.metaVoxFilters.length > 0;\n\n      // Cross-folder mode but no filters yet → empty state.\n      if (crossFolder && !hasFilters) {\n        this.photos = [];\n        this.timeline = [];\n        this.highlights = [];\n        this.loading = false;\n        return;\n      }\n      // No folder picked + not in cross-folder mode → empty state.\n      if (!crossFolder && !this.config.folderPath) {\n        this.photos = [];\n        this.timeline = [];\n        this.highlights = [];\n        this.loading = false;\n        return;\n      }\n\n      // Client-side memo for non-paged modes only. Paged mode appends pages\n      // into `this.photos` via fetchMore(); restoring the cached first-page\n      // payload would silently drop the user's accumulated infinite-scroll\n      // state — so we always re-fetch in paged mode and rely on the server's\n      // 304 (via If-None-Match) to keep it cheap when nothing changed.\n      const key = this.fetchKey;\n      if (!this.isPagedMode()) {\n        const cached = this._payloadCache.get(key);\n        if (cached) {\n          this.photos = cached.photos || [];\n          this.timeline = cached.timeline || [];\n          this.highlights = cached.highlights || [];\n          this.capabilities = cached.capabilities || null;\n          this.pagination = cached.pagination || { offset: 0, pageSize: 200, total: 0, hasMore: false, truncated: false };\n          this.loading = false;\n          return;\n        }\n      }\n\n      // Cancel any pending fetch/fetchMore so we don't race a stale response.\n      if (this._abortController) {\n        this._abortController.abort();\n      }\n      this._abortController = new AbortController();\n      const signal = this._abortController.signal;\n\n      // Reset pagination for first page\n      this.pagination = { offset: 0, pageSize: 200, total: 0, hasMore: false, truncated: false };\n\n      this.loading = true;\n      this.error = null;\n      this.accessReason = null;\n      try {\n        const params = this.buildPhotosParams(0);\n        const url = generateUrl(`/apps/intravox/api/photo-story/photos?${params.toString()}`);\n        const headers = {};\n        if (this._lastEtag) {\n          headers['If-None-Match'] = this._lastEtag;\n        }\n        const res = await axios.get(url, { headers, signal, validateStatus: s => (s >= 200 && s < 300) || s === 304 || s === 404 });\n        if (res.status === 304) {\n          this.loading = false;\n          return;\n        }\n        if (res.status === 404) {\n          // Folder doesn't exist for this user — empty state, not error.\n          this.photos = [];\n          this.timeline = [];\n          this.highlights = [];\n          this.capabilities = null;\n          this.accessReason = res.data?.reason || null;\n          this.loading = false;\n          return;\n        }\n        const data = res.data || {};\n        this.photos = data.photos || [];\n        this.timeline = data.timeline || [];\n        this.highlights = data.highlights || [];\n        this.capabilities = data.capabilities || null;\n        this.warmFederatedPreviews(this.photos);\n        if (data.map && typeof data.map === 'object') {\n          this.mapSettings = { ...this.mapSettings, ...data.map };\n        }\n        if (data.pagination) {\n          this.pagination = {\n            offset: data.pagination.offset || 0,\n            pageSize: data.pagination.pageSize || 200,\n            total: data.pagination.total || this.photos.length,\n            hasMore: !!data.pagination.hasMore,\n            truncated: !!data.pagination.truncated,\n          };\n        }\n        if (res.headers && res.headers.etag) {\n          this._lastEtag = res.headers.etag;\n        }\n        if (!this.isPagedMode()) {\n          if (this._payloadCache.size >= 8) {\n            const oldestKey = this._payloadCache.keys().next().value;\n            this._payloadCache.delete(oldestKey);\n          }\n          this._payloadCache.set(key, {\n            photos: this.photos,\n            timeline: this.timeline,\n            highlights: this.highlights,\n            capabilities: this.capabilities,\n            pagination: { ...this.pagination },\n          });\n        }\n        // Activate infinite-scroll observer when more pages are available.\n        this.$nextTick(() => this.setupScrollSentinel());\n      } catch (err) {\n        if (axios.isCancel?.(err) || err?.name === 'CanceledError' || err?.code === 'ERR_CANCELED') {\n          return;\n        }\n        console.error('[PhotoStoryWidget] fetch failed:', err);\n        this.error = err.response?.data?.error || err.message || this.t('Failed to load photos');\n      } finally {\n        this.loading = false;\n      }\n    },\n    buildPhotosParams(offset = 0) {\n      const params = new URLSearchParams({ mode: this.effectiveMode });\n      const crossFolder = !!this.config.allMetaVoxFolders;\n      const hasFilters = Array.isArray(this.config.metaVoxFilters) && this.config.metaVoxFilters.length > 0;\n      if (this.config.folderPath && !crossFolder) {\n        params.append('folder', this.config.folderPath);\n      }\n      if (hasFilters) {\n        params.append('filters', JSON.stringify(this.config.metaVoxFilters));\n      }\n      if (this.config.limit) {\n        params.append('limit', String(this.config.limit));\n      }\n      if (this.isPagedMode()) {\n        params.append('offset', String(offset));\n        params.append('pageSize', String(this.pagination.pageSize || 200));\n        params.append('sortOrder', this.config.sortOrder || 'desc');\n        params.append('sortBy', this.config.sortBy || 'mtime');\n        // Pass the total from the first page so the backend can skip the\n        // expensive COUNT(*) on subsequent fetchMore() calls.\n        if (offset > 0 && this.pagination.total > 0) {\n          params.append('total', String(this.pagination.total));\n        }\n      }\n      // Only send the param when the user explicitly opted out. Backend\n      // defaults to dedup=on, so omitting matches existing widgets.\n      if (this.config.hideRawDuplicates === false) {\n        params.append('hideRawDuplicates', '0');\n      }\n      return params;\n    },\n    async fetchMore() {\n      if (this.loadingMore || !this.pagination.hasMore || !this.isPagedMode()) return;\n      this.loadingMore = true;\n      const signal = this._abortController?.signal;\n      try {\n        const nextOffset = this.pagination.offset + this.pagination.pageSize;\n        const params = this.buildPhotosParams(nextOffset);\n        const url = generateUrl(`/apps/intravox/api/photo-story/photos?${params.toString()}`);\n        const res = await axios.get(url, { signal, validateStatus: s => s >= 200 && s < 300 });\n        const data = res.data || {};\n        const newPhotos = data.photos || [];\n        this.photos = this.photos.concat(newPhotos);\n        this.warmFederatedPreviews(newPhotos);\n\n        // Merge incoming timeline-days into existing timeline. Backend returns\n        // grouped days for THIS page; we merge by date key, then explicitly\n        // re-sort by date so cross-page bucket-order is always chronological\n        // (insertion-order is wrong when a later page reaches back into earlier\n        // dates — happens when SQL mtime-sort and bucket sort disagree, or with\n        // mixed taken_at/mtime fallbacks).\n        if (Array.isArray(data.timeline) && data.timeline.length) {\n          const byDate = new Map();\n          for (const d of this.timeline) byDate.set(d.date, d);\n          for (const d of data.timeline) {\n            if (byDate.has(d.date)) {\n              const existing = byDate.get(d.date);\n              existing.photos = existing.photos.concat(d.photos || []);\n            } else {\n              byDate.set(d.date, d);\n            }\n          }\n          const ord = this.config.sortOrder || 'desc';\n          this.timeline = Array.from(byDate.values()).sort((a, b) => ord === 'desc'\n            ? b.date.localeCompare(a.date)\n            : a.date.localeCompare(b.date));\n        }\n\n        if (data.pagination) {\n          this.pagination = {\n            offset: data.pagination.offset || nextOffset,\n            pageSize: data.pagination.pageSize || this.pagination.pageSize,\n            total: data.pagination.total || this.pagination.total,\n            hasMore: !!data.pagination.hasMore,\n            truncated: !!data.pagination.truncated,\n          };\n        }\n      } catch (err) {\n        if (axios.isCancel?.(err) || err?.name === 'CanceledError' || err?.code === 'ERR_CANCELED') {\n          return;\n        }\n        console.error('[PhotoStoryWidget] fetchMore failed:', err);\n      } finally {\n        this.loadingMore = false;\n      }\n    },\n    setupScrollSentinel() {\n      // Tear down previous observer (e.g. on re-fetch)\n      if (this._scrollObserver) {\n        this._scrollObserver.disconnect();\n        this._scrollObserver = null;\n      }\n      if (!this.pagination.hasMore || typeof IntersectionObserver === 'undefined') return;\n      const sentinel = this.$refs.scrollSentinel;\n      if (!sentinel) return;\n      this._scrollObserver = new IntersectionObserver((entries) => {\n        for (const e of entries) {\n          if (e.isIntersecting) {\n            this.fetchMore().then(() => {\n              // After loading next page, reconnect or disconnect based on hasMore\n              if (!this.pagination.hasMore && this._scrollObserver) {\n                this._scrollObserver.disconnect();\n                this._scrollObserver = null;\n              }\n            });\n            break;\n          }\n        }\n      }, { rootMargin: '800px' });\n      this._scrollObserver.observe(sentinel);\n    },\n    /**\n     * Best-effort pre-warm of the federated-preview cache. Picks every\n     * `is_federated: true` photo from a newly loaded page and POSTs the\n     * fileIds to the warmup endpoint. Backend handles cap, dedup and\n     * outbound concurrency limit; we don't await the response.\n     */\n    warmFederatedPreviews(photos) {\n      try {\n        if (!Array.isArray(photos) || photos.length === 0) return;\n        const fids = photos\n          .filter(p => p && p.is_federated && p.file_id)\n          .map(p => p.file_id);\n        if (fids.length === 0) return;\n        const url = generateUrl('/apps/intravox/api/preview/warmup');\n        axios.post(url, { file_ids: fids }).catch(() => {});\n      } catch (_) {\n        // Pre-warm is fire-and-forget — never block render on it.\n      }\n    },\n    previewUrl(photo, size = 512) {\n      // Federated files cannot be served by NC core preview (the providers\n      // can't reach the remote storage), so route them through IntraVox's\n      // preview-proxy. Local files keep the direct /core/preview path.\n      if (photo && photo.is_federated) {\n        return generateUrl(`/apps/intravox/api/preview?file_id=${photo.file_id}&x=${size}&y=${size}`);\n      }\n      const fid = (photo && typeof photo === 'object') ? photo.file_id : photo;\n      return generateUrl(`/core/preview?fileId=${fid}&x=${size}&y=${size}&a=true`);\n    },\n    formatDate(iso) {\n      if (!iso) return '';\n      try {\n        const d = new Date(iso);\n        return d.toLocaleDateString(getCanonicalLocale(), { day: 'numeric', month: 'long', year: 'numeric' });\n      } catch (e) {\n        return iso;\n      }\n    },\n    // Locale-aware long-date formatter for sticky day-headers (all three styles).\n    // Year is always included so days from different years stay visually distinct.\n    formatLongDate(dateStr) {\n      if (!dateStr) return '';\n      const d = new Date(dateStr);\n      if (Number.isNaN(d.getTime())) return dateStr;\n      return d.toLocaleDateString(getCanonicalLocale(), {\n        weekday: 'long',\n        day: 'numeric',\n        month: 'long',\n        year: 'numeric',\n      });\n    },\n    // Deterministic pseudo-random column-span for magazine style (no width/height yet in photo dict).\n    // Hash on file_id so the same photo always lands in the same cell — no flicker on re-render.\n    magazineTileStyle(photo) {\n      const id = Number(photo.file_id) || 0;\n      const span = (id % 3) + 1; // 1, 2 or 3 columns\n      return { gridColumn: `span ${span}` };\n    },\n    // For timeline mode: map photo to its index in the global photos array\n    globalIndex(dayPhotos, idx) {\n      const target = dayPhotos[idx];\n      if (!target) return 0;\n      const i = this.photos.findIndex(p => p.file_id === target.file_id);\n      return i >= 0 ? i : 0;\n    },\n    // Mini-map helpers (Phase 2.8): show a per-day Leaflet map above each day-header.\n    // Day-level _hasGps / _gpsPoints are precomputed in the timelineDays computed.\n    showDayMaps() {\n      return this.config.showDayMaps !== false;\n    },\n    openLightboxByFileId(fileId) {\n      const idx = this.photos.findIndex(p => p.file_id === fileId);\n      if (idx >= 0) this.openLightbox(idx);\n    },\n    scrollToYear(year) {\n      // Find the first day-section whose date starts with that year and scroll to it\n      const root = this.$el;\n      if (!root) return;\n      const sections = root.querySelectorAll('.ps-timeline section[class^=\"ps-day-\"]');\n      const prefix = String(year);\n      for (const sec of sections) {\n        // Sections use :key=\"day.date\"; we need to match by their first descendant date label.\n        const dateAttr = sec.dataset.date || '';\n        if (dateAttr.startsWith(prefix)) {\n          sec.scrollIntoView({ behavior: 'smooth', block: 'start' });\n          return;\n        }\n      }\n      // Fallback: search inside sections' first header text for the year\n      for (const sec of sections) {\n        const h = sec.querySelector('h2, h3, h4');\n        if (h && h.textContent.includes(prefix)) {\n          sec.scrollIntoView({ behavior: 'smooth', block: 'start' });\n          return;\n        }\n      }\n    },\n    // Pseudo-random masonry: 1 in ~3 photos spans 2 rows for visual rhythm\n    masonryItemStyle(photo) {\n      const mtime = photo.mtime || photo.file_id || 0;\n      const tall = (mtime % 3) === 0;\n      return tall ? { gridRowEnd: 'span 2' } : {};\n    },\n    openLightbox(index) {\n      this.lightboxIndex = index;\n      this.lightboxVisible = true;\n      this.$emit('open-lightbox', { index, photos: this.photos });\n    },\n    onClusterClick(payload) {\n      // Open lightbox on first matching photo in cluster\n      const ids = payload?.photo_ids || [];\n      if (!ids.length) return;\n      const idx = this.photos.findIndex(p => ids.includes(p.file_id));\n      if (idx >= 0) {\n        this.openLightbox(idx);\n      }\n    },\n  },\n};\n</script>\n\n<style scoped>\n.photo-story-widget {\n  width: 100%;\n  margin: 12px 0;\n  container-type: inline-size;\n  position: relative;\n}\n\n.ps-scroll-sentinel {\n  display: flex;\n  justify-content: center;\n  align-items: center;\n  min-height: 60px;\n  padding: 24px 0;\n}\n\n.ps-loading-more {\n  font-size: 13px;\n  color: var(--ps-text-muted, var(--color-text-maxcontrast));\n}\n\n.ps-truncated {\n  margin: 16px 0;\n  padding: 10px 14px;\n  background: var(--color-warning-background);\n  border: 1px solid var(--color-warning);\n  border-radius: 8px;\n  font-size: 13px;\n  color: var(--ps-text-muted, var(--color-text-maxcontrast));\n}\n\n/* Year-jump scrubber: floats top-right inside the widget, sticky to viewport */\n.ps-year-scrubber {\n  position: sticky;\n  top: 80px;\n  float: right;\n  margin: 0 0 0 12px;\n  display: flex;\n  flex-direction: column;\n  gap: 2px;\n  padding: 6px 4px;\n  /* Theme-aware translucent backdrop (dark mode keeps the same blur but\n     against a dark NC background rather than hard-coded white). */\n  background: color-mix(in srgb, var(--color-main-background) 70%, transparent);\n  backdrop-filter: blur(8px);\n  -webkit-backdrop-filter: blur(8px);\n  border-radius: 999px;\n  z-index: 2;\n  font-size: 11px;\n  font-weight: 600;\n  color: var(--ps-text-muted, var(--color-text-maxcontrast));\n}\n\n.ps-year-btn {\n  background: none;\n  border: 0;\n  cursor: pointer;\n  padding: 4px 8px;\n  border-radius: 999px;\n  color: inherit;\n  font: inherit;\n  transition: background 0.15s ease, color 0.15s ease;\n}\n\n.ps-year-btn:hover {\n  background: var(--color-primary);\n  color: var(--color-primary-text);\n}\n\n@container (max-width: 500px) {\n  .ps-year-scrubber {\n    display: none;\n  }\n}\n\n.ps-map-embed {\n  margin-bottom: 16px;\n}\n\n.ps-loading {\n  display: grid;\n  grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));\n  gap: 8px;\n}\n\n.ps-skeleton-tile {\n  aspect-ratio: 1 / 1;\n  background: var(--color-background-hover);\n  border-radius: var(--border-radius);\n  animation: ps-pulse 1.5s infinite ease-in-out;\n}\n\n@keyframes ps-pulse {\n  0%, 100% { opacity: 0.6; }\n  50% { opacity: 1; }\n}\n\n.ps-error,\n.ps-empty {\n  display: flex;\n  flex-direction: column;\n  align-items: center;\n  justify-content: center;\n  gap: 12px;\n  padding: 40px 20px;\n  color: var(--ps-text-muted, var(--color-text-maxcontrast));\n  text-align: center;\n}\n\n.ps-error {\n  color: var(--color-error);\n}\n\n.ps-empty {\n  background: var(--color-background-hover);\n  border: 2px dashed var(--color-border);\n  border-radius: var(--border-radius-large);\n}\n\n.ps-empty small {\n  font-size: 12px;\n  opacity: 0.7;\n}\n\n.ps-empty-hint {\n  /* Secondary hint that appears below the empty-state path. Slightly larger\n   * line-height + max-width so the longer text wraps nicely. */\n  display: block;\n  max-width: 480px;\n  margin-top: 8px;\n  line-height: 1.4;\n}\n\n/* Timeline / on-this-day day section */\n.ps-day,\n.ps-year {\n  margin-bottom: 24px;\n}\n\n.ps-day-header {\n  margin-bottom: 8px;\n}\n\n.ps-day-label {\n  margin: 0;\n  font-size: 16px;\n  font-weight: 600;\n  color: var(--ps-text, var(--color-main-text));\n}\n\n.ps-day-location {\n  margin: 2px 0 0 0;\n  font-size: 12px;\n  color: var(--ps-text-muted, var(--color-text-maxcontrast));\n}\n\n/* Grids */\n.ps-grid {\n  display: grid;\n  gap: 8px;\n}\n\n.ps-grid--responsive {\n  grid-template-columns: repeat(3, 1fr);\n}\n\n@container (max-width: 700px) {\n  .ps-grid--responsive {\n    grid-template-columns: repeat(2, 1fr);\n  }\n}\n\n@container (max-width: 400px) {\n  .ps-grid--responsive {\n    grid-template-columns: 1fr;\n  }\n}\n\n.ps-grid--two {\n  grid-template-columns: repeat(2, 1fr);\n}\n\n@container (max-width: 500px) {\n  .ps-grid--two {\n    grid-template-columns: 1fr;\n  }\n}\n\n.ps-grid--masonry {\n  grid-template-columns: repeat(var(--ps-grid-cols, 3), 1fr);\n  grid-auto-rows: 120px;\n}\n\n@container (max-width: 700px) {\n  .ps-grid--masonry {\n    grid-template-columns: repeat(2, 1fr);\n  }\n}\n\n@container (max-width: 400px) {\n  .ps-grid--masonry {\n    grid-template-columns: 1fr;\n  }\n}\n\n/* Photo tile */\n:deep(.ps-tile) {\n  position: relative;\n  margin: 0;\n  border-radius: var(--border-radius-large);\n  overflow: hidden;\n  cursor: pointer;\n  background: var(--color-background-dark);\n  transition: transform 0.2s ease, box-shadow 0.2s ease;\n  aspect-ratio: 1 / 1;\n  height: 100%;\n}\n\n:deep(.ps-grid--masonry .ps-tile) {\n  aspect-ratio: unset;\n}\n\n:deep(.ps-tile:hover),\n:deep(.ps-tile:focus-visible) {\n  transform: scale(1.02);\n  box-shadow: 0 4px 14px rgba(0, 0, 0, 0.15);\n  outline: none;\n}\n\n:deep(.ps-tile-img) {\n  width: 100%;\n  height: 100%;\n  object-fit: cover;\n  display: block;\n}\n\n/* Video tile: centered play-button overlay on top of the preview / placeholder */\n:deep(.ps-tile-video-badge) {\n  position: absolute;\n  top: 50%;\n  left: 50%;\n  transform: translate(-50%, -50%);\n  width: 44px;\n  height: 44px;\n  border-radius: 50%;\n  background: rgba(0, 0, 0, 0.55);\n  color: #fff;\n  display: flex;\n  align-items: center;\n  justify-content: center;\n  pointer-events: none;\n  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.35);\n  transition: transform 0.15s ease, background 0.15s ease;\n}\n\n:deep(.ps-tile:hover .ps-tile-video-badge),\n:deep(.ps-tile:focus .ps-tile-video-badge) {\n  background: rgba(0, 0, 0, 0.75);\n  transform: translate(-50%, -50%) scale(1.08);\n}\n\n:deep(.ps-tile-video-badge svg) {\n  margin-left: 2px; /* visual centering — play triangle leans right */\n}\n\n:deep(.ps-tile-caption) {\n  position: absolute;\n  left: 0;\n  right: 0;\n  bottom: 0;\n  padding: 8px 10px;\n  font-size: 12px;\n  background: linear-gradient(180deg, transparent, rgba(0, 0, 0, 0.65));\n  color: #fff;\n  opacity: 0;\n  transition: opacity 0.2s ease;\n}\n\n:deep(.ps-tile:hover .ps-tile-caption),\n:deep(.ps-tile:focus-visible .ps-tile-caption) {\n  opacity: 1;\n}\n\n/* Placeholder for files without a thumbnail (RAW without preview-provider, missing previews) */\n:deep(.ps-tile--placeholder) {\n  background: linear-gradient(135deg, var(--color-background-dark) 0%, var(--color-main-background) 100%);\n  border: 1px solid var(--color-border);\n}\n\n:deep(.ps-tile-placeholder) {\n  width: 100%;\n  height: 100%;\n  display: flex;\n  flex-direction: column;\n  align-items: center;\n  justify-content: center;\n  gap: 8px;\n  padding: 12px;\n  text-align: center;\n  color: var(--ps-text, var(--color-main-text));\n}\n\n:deep(.ps-tile-placeholder-badge) {\n  font-family: var(--font-face, monospace);\n  font-size: 11px;\n  font-weight: 700;\n  letter-spacing: 1px;\n  padding: 4px 10px;\n  border-radius: 999px;\n  background: var(--color-primary-element);\n  color: var(--color-primary-element-text);\n}\n\n:deep(.ps-tile-placeholder-meta) {\n  display: flex;\n  flex-direction: column;\n  gap: 2px;\n  font-size: 11px;\n  line-height: 1.3;\n  word-break: break-word;\n  max-width: 100%;\n}\n\n:deep(.ps-tile-placeholder-meta strong) {\n  font-size: 12px;\n  font-weight: 600;\n}\n\n:deep(.ps-tile-placeholder-meta span) {\n  color: var(--ps-text-muted, var(--color-text-maxcontrast));\n}\n\n:deep(.ps-tile-placeholder-meta small) {\n  color: var(--ps-text-muted, var(--color-text-maxcontrast));\n  font-size: 10px;\n}\n\n/* Highlights hero */\n.ps-highlights {\n  display: flex;\n  flex-direction: column;\n  gap: 16px;\n}\n\n.ps-highlight-hero {\n  position: relative;\n  width: 100%;\n  max-height: 45vh;\n  aspect-ratio: 16 / 9;\n  overflow: hidden;\n  border-radius: var(--border-radius-large);\n  cursor: pointer;\n}\n\n.ps-hero-img {\n  width: 100%;\n  height: 100%;\n  max-height: 45vh;\n  object-fit: cover;\n  display: block;\n  transition: transform 0.4s ease;\n}\n\n.ps-highlight-hero:hover .ps-hero-img {\n  transform: scale(1.02);\n}\n\n.ps-hero-caption {\n  position: absolute;\n  left: 0;\n  right: 0;\n  bottom: 0;\n  padding: 20px;\n  background: linear-gradient(180deg, transparent, rgba(0, 0, 0, 0.75));\n  color: #fff;\n  display: flex;\n  flex-direction: column;\n  gap: 2px;\n}\n\n.ps-hero-caption strong {\n  font-size: 18px;\n}\n\n.ps-hero-caption span {\n  font-size: 13px;\n  opacity: 0.85;\n}\n\n/* =========================================================================\n   Timeline visual styles (Phase 2.2)\n   ========================================================================= */\n\n/* ---- Magazine ---- */\n.ps-timeline--magazine {\n  font-family: 'Georgia', 'Source Serif Pro', 'Iowan Old Style', serif;\n}\n\n.ps-day-mag {\n  margin-bottom: 80px;\n}\n\n/* Transparent header — inherits the row/widget background instead of forcing\n * a white block over a themed row. Hairline underline keeps the visual\n * separator. See file-story-widget.vue::fs-day-header for the same pattern. */\n.ps-day-mag-header {\n  background: transparent;\n  text-align: center;\n  padding: 16px 0 12px;\n  margin-bottom: 24px;\n  border-bottom: 1px solid var(--color-border);\n}\n\n/* Mini-map styling — Magazine: editorial wide banner */\n.ps-day-mag-map {\n  margin: 0 0 28px;\n  box-shadow: 0 2px 6px rgba(0, 0, 0, 0.08);\n}\n\n/* Mini-map styling — Apple: small, tucked above header */\n.ps-day-apple-map {\n  margin: 0 0 12px;\n  border-radius: 8px;\n}\n\n/* Mini-map styling — Travelogue: between header + grid, reisdagboek-feel */\n.ps-day-trav-map {\n  margin: 4px 0 12px;\n  box-shadow: 0 1px 4px rgba(0, 0, 0, 0.1);\n}\n\n.ps-day-mag-date {\n  margin: 0;\n  font-size: 32px;\n  font-weight: 400;\n  letter-spacing: -0.02em;\n  color: var(--ps-text, var(--color-main-text));\n  font-family: inherit;\n}\n\n.ps-day-mag-loc {\n  margin: 4px 0 0 0;\n  font-style: italic;\n  color: var(--ps-text-muted, var(--color-text-maxcontrast));\n  font-size: 16px;\n}\n\n.ps-mag-grid {\n  display: grid;\n  grid-template-columns: repeat(6, 1fr);\n  gap: 24px;\n}\n\n@container (max-width: 900px) {\n  .ps-mag-grid {\n    grid-template-columns: repeat(4, 1fr);\n  }\n}\n\n@container (max-width: 600px) {\n  .ps-mag-grid {\n    grid-template-columns: repeat(2, 1fr);\n    gap: 12px;\n  }\n  .ps-mag-tile {\n    /* On narrow widths a 3-column span overflows; clamp to grid-width */\n    grid-column: span 2 !important;\n  }\n}\n\n.ps-mag-tile {\n  margin: 0;\n  aspect-ratio: 4 / 3;\n  overflow: hidden;\n  border-radius: 2px; /* editorial look — almost square corners */\n  cursor: pointer;\n  background: var(--color-background-dark);\n  transition: transform 0.25s ease, box-shadow 0.25s ease;\n}\n\n.ps-mag-tile:hover,\n.ps-mag-tile:focus-visible {\n  transform: scale(1.01);\n  box-shadow: 0 6px 22px rgba(0, 0, 0, 0.18);\n  outline: none;\n}\n\n.ps-mag-img {\n  width: 100%;\n  height: 100%;\n  object-fit: cover;\n  display: block;\n}\n\n/* ---- Apple ---- */\n.ps-timeline--apple {\n  /* inherit system font — do not override */\n}\n\n.ps-day-apple {\n  margin-bottom: 32px;\n}\n\n.ps-day-apple-header {\n  background: transparent;\n  display: flex;\n  justify-content: space-between;\n  align-items: baseline;\n  gap: 12px;\n  padding: 10px 4px 4px;\n  margin-top: 4px;\n  margin-bottom: 12px;\n  border-bottom: 1px solid var(--color-border);\n}\n\n.ps-day-apple-date {\n  margin: 0;\n  font-size: 22px;\n  font-weight: 600;\n  color: var(--ps-text, var(--color-main-text));\n}\n\n.ps-day-apple-loc {\n  font-size: 13px;\n  color: var(--ps-text-muted, var(--color-text-maxcontrast));\n  display: inline-flex;\n  align-items: center;\n  gap: 4px;\n}\n\n.ps-apple-grid {\n  display: grid;\n  grid-template-columns: repeat(5, 1fr);\n  gap: 4px;\n}\n\n@container (max-width: 900px) {\n  .ps-apple-grid {\n    grid-template-columns: repeat(4, 1fr);\n  }\n}\n\n@container (max-width: 600px) {\n  .ps-apple-grid {\n    grid-template-columns: repeat(3, 1fr);\n  }\n}\n\n:deep(.ps-timeline--apple .ps-tile) {\n  aspect-ratio: 1 / 1;\n  border-radius: 6px;\n  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.08);\n  overflow: hidden;\n}\n\n/* ---- Travelogue (Polarsteps) ---- */\n.ps-timeline--travelogue {\n  /* outer styling lives on each day section so the rail aligns per-day */\n}\n\n.ps-day-trav {\n  display: grid;\n  grid-template-columns: 60px 1fr;\n  gap: 16px;\n  margin-bottom: 24px;\n}\n\n.ps-trav-rail {\n  position: relative;\n}\n\n.ps-trav-rail::before {\n  content: '';\n  position: absolute;\n  top: 0;\n  bottom: -24px; /* extend into the gap so the line continues between days */\n  left: 30px;\n  width: 2px;\n  background: var(--color-border);\n}\n\n.ps-trav-bullet {\n  width: 14px;\n  height: 14px;\n  border-radius: 50%;\n  background: var(--color-primary);\n  position: absolute;\n  left: 24px;\n  top: 14px;\n  box-shadow: 0 0 0 4px var(--color-main-background);\n  z-index: 1;\n}\n\n.ps-trav-content {\n  min-width: 0;\n}\n\n.ps-day-trav-header {\n  background: transparent;\n  padding: 10px 0 4px;\n  margin-top: 4px;\n  margin-bottom: 4px;\n  border-bottom: 1px solid var(--color-border);\n}\n\n.ps-day-trav-date {\n  margin: 0 0 4px 0;\n  font-size: 18px;\n  font-weight: 600;\n  color: var(--ps-text, var(--color-main-text));\n}\n\n.ps-day-trav-loc {\n  margin: 0 0 12px 0;\n  font-size: 13px;\n  color: var(--ps-text-muted, var(--color-text-maxcontrast));\n  display: flex;\n  align-items: center;\n  gap: 4px;\n}\n\n.ps-trav-grid {\n  display: grid;\n  grid-template-columns: repeat(2, 1fr);\n  gap: 8px;\n}\n\n@container (max-width: 500px) {\n  .ps-day-trav {\n    grid-template-columns: 40px 1fr;\n    gap: 8px;\n  }\n  .ps-trav-rail::before {\n    left: 20px;\n  }\n  .ps-trav-bullet {\n    left: 14px;\n  }\n  .ps-trav-grid {\n    grid-template-columns: 1fr;\n  }\n}\n\n@media (prefers-reduced-motion: reduce) {\n  .ps-tile-skeleton,\n  .ps-day-skeleton {\n    animation: none;\n  }\n}\n\n/* Dark row-bg contrast lifts. Photo tiles already have their own surfaces\n   (image previews, gradient overlays), so the widget only needs to recolor\n   the day/section headers and adjust separators to stay legible. */\n.photo-story-widget.ps--on-dark :deep(.ps-day-header),\n.photo-story-widget.ps--on-dark :deep(.ps-section-header) {\n  border-bottom-color: rgba(255, 255, 255, 0.25);\n}\n\n.photo-story-widget.ps--on-dark :deep(.ps-day-location),\n.photo-story-widget.ps--on-dark :deep(.ps-day-mag-loc),\n.photo-story-widget.ps--on-dark :deep(.ps-day-apple-loc),\n.photo-story-widget.ps--on-dark :deep(.ps-day-trav-loc) {\n  opacity: 0.82;\n}\n\n.photo-story-widget.ps--on-dark :deep(.ps-tile-skeleton),\n.photo-story-widget.ps--on-dark :deep(.ps-day-skeleton) {\n  background: rgba(255, 255, 255, 0.12);\n}\n</style>\n"],"sourceRoot":""}]);
// Exports
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (___CSS_LOADER_EXPORT___);


/***/ },

/***/ "./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/dist/stylePostLoader.js!./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/PhotoStoryWidget.vue?vue&type=style&index=0&id=745c96b7&scoped=true&lang=css"
/*!*********************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/dist/stylePostLoader.js!./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/PhotoStoryWidget.vue?vue&type=style&index=0&id=745c96b7&scoped=true&lang=css ***!
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
/* harmony import */ var _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_dist_stylePostLoader_js_node_modules_vue_loader_dist_index_js_ruleSet_1_rules_5_use_0_PhotoStoryWidget_vue_vue_type_style_index_0_id_745c96b7_scoped_true_lang_css__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! !!../../node_modules/css-loader/dist/cjs.js!../../node_modules/vue-loader/dist/stylePostLoader.js!../../node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./PhotoStoryWidget.vue?vue&type=style&index=0&id=745c96b7&scoped=true&lang=css */ "./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/dist/stylePostLoader.js!./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/PhotoStoryWidget.vue?vue&type=style&index=0&id=745c96b7&scoped=true&lang=css");

      
      
      
      
      
      
      
      
      

var options = {};

options.styleTagTransform = (_node_modules_style_loader_dist_runtime_styleTagTransform_js__WEBPACK_IMPORTED_MODULE_5___default());
options.setAttributes = (_node_modules_style_loader_dist_runtime_setAttributesWithoutAttributes_js__WEBPACK_IMPORTED_MODULE_3___default());

      options.insert = _node_modules_style_loader_dist_runtime_insertBySelector_js__WEBPACK_IMPORTED_MODULE_2___default().bind(null, "head");
    
options.domAPI = (_node_modules_style_loader_dist_runtime_styleDomAPI_js__WEBPACK_IMPORTED_MODULE_1___default());
options.insertStyleElement = (_node_modules_style_loader_dist_runtime_insertStyleElement_js__WEBPACK_IMPORTED_MODULE_4___default());

var update = _node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0___default()(_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_dist_stylePostLoader_js_node_modules_vue_loader_dist_index_js_ruleSet_1_rules_5_use_0_PhotoStoryWidget_vue_vue_type_style_index_0_id_745c96b7_scoped_true_lang_css__WEBPACK_IMPORTED_MODULE_6__["default"], options);




       /* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_dist_stylePostLoader_js_node_modules_vue_loader_dist_index_js_ruleSet_1_rules_5_use_0_PhotoStoryWidget_vue_vue_type_style_index_0_id_745c96b7_scoped_true_lang_css__WEBPACK_IMPORTED_MODULE_6__["default"] && _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_dist_stylePostLoader_js_node_modules_vue_loader_dist_index_js_ruleSet_1_rules_5_use_0_PhotoStoryWidget_vue_vue_type_style_index_0_id_745c96b7_scoped_true_lang_css__WEBPACK_IMPORTED_MODULE_6__["default"].locals ? _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_dist_stylePostLoader_js_node_modules_vue_loader_dist_index_js_ruleSet_1_rules_5_use_0_PhotoStoryWidget_vue_vue_type_style_index_0_id_745c96b7_scoped_true_lang_css__WEBPACK_IMPORTED_MODULE_6__["default"].locals : undefined);


/***/ },

/***/ "./src/components/PhotoStoryWidget.vue"
/*!*********************************************!*\
  !*** ./src/components/PhotoStoryWidget.vue ***!
  \*********************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _PhotoStoryWidget_vue_vue_type_template_id_745c96b7_scoped_true__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./PhotoStoryWidget.vue?vue&type=template&id=745c96b7&scoped=true */ "./src/components/PhotoStoryWidget.vue?vue&type=template&id=745c96b7&scoped=true");
/* harmony import */ var _PhotoStoryWidget_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./PhotoStoryWidget.vue?vue&type=script&lang=js */ "./src/components/PhotoStoryWidget.vue?vue&type=script&lang=js");
/* harmony import */ var _PhotoStoryWidget_vue_vue_type_style_index_0_id_745c96b7_scoped_true_lang_css__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./PhotoStoryWidget.vue?vue&type=style&index=0&id=745c96b7&scoped=true&lang=css */ "./src/components/PhotoStoryWidget.vue?vue&type=style&index=0&id=745c96b7&scoped=true&lang=css");
/* harmony import */ var _node_modules_vue_loader_dist_exportHelper_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ../../node_modules/vue-loader/dist/exportHelper.js */ "./node_modules/vue-loader/dist/exportHelper.js");




;


const __exports__ = /*#__PURE__*/(0,_node_modules_vue_loader_dist_exportHelper_js__WEBPACK_IMPORTED_MODULE_3__["default"])(_PhotoStoryWidget_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_1__["default"], [['render',_PhotoStoryWidget_vue_vue_type_template_id_745c96b7_scoped_true__WEBPACK_IMPORTED_MODULE_0__.render],['__scopeId',"data-v-745c96b7"],['__file',"src/components/PhotoStoryWidget.vue"]])
/* hot reload */
if (false) // removed by dead control flow
{}


/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (__exports__);

/***/ },

/***/ "./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/PhotoStoryWidget.vue?vue&type=script&lang=js"
/*!*****************************************************************************************************************************************!*\
  !*** ./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/PhotoStoryWidget.vue?vue&type=script&lang=js ***!
  \*****************************************************************************************************************************************/
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
/* harmony import */ var vue_material_design_icons_ImageMultiple_vue__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! vue-material-design-icons/ImageMultiple.vue */ "./node_modules/vue-material-design-icons/ImageMultiple.vue");
/* harmony import */ var vue_material_design_icons_MapMarker_vue__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! vue-material-design-icons/MapMarker.vue */ "./node_modules/vue-material-design-icons/MapMarker.vue");
/* harmony import */ var vue_material_design_icons_LockOutline_vue__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__(/*! vue-material-design-icons/LockOutline.vue */ "./node_modules/vue-material-design-icons/LockOutline.vue");












const PhotoTile = {
  name: 'PhotoTile',
  props: {
    photo: { type: Object, required: true },
    showCaption: { type: Boolean, default: true },
  },
  emits: ['click'],
  setup(props, { emit }) {
    const hasError = (0,vue__WEBPACK_IMPORTED_MODULE_0__.ref)(false);
    return () => {
      // Route federated photos through IntraVox's preview proxy; local
      // photos go straight to NC core preview to reuse NC's preview cache.
      const src = props.photo.is_federated
        ? (0,_nextcloud_router__WEBPACK_IMPORTED_MODULE_2__.generateUrl)(`/apps/intravox/api/preview?file_id=${props.photo.file_id}&x=512&y=512`)
        : (0,_nextcloud_router__WEBPACK_IMPORTED_MODULE_2__.generateUrl)(`/core/preview?fileId=${props.photo.file_id}&x=512&y=512&a=true`);
      const caption = props.photo.location_display || props.photo.location || '';
      const dateStr = props.photo.taken_at
        ? new Date(props.photo.taken_at).toLocaleDateString((0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_3__.getCanonicalLocale)(), { day: 'numeric', month: 'short', year: 'numeric' })
        : '';

      const mime = String(props.photo.mime || '');
      const isVideo = mime.startsWith('video/')
        || /\.(mov|mp4|m4v|avi|mkv|webm|wmv|mpg|mpeg|3gp)$/i.test(props.photo.name || '');
      const isRawMime = mime.match(/x-(canon|nikon|sony|adobe|fuji|olympus|panasonic|pentax|samsung|sigma|dcraw)/i)
        || /\.(cr2|cr3|nef|arw|dng|raf|orf|rw2|pef|srw|x3f)$/i.test(props.photo.name || '');
      let formatLabel;
      if (isVideo) formatLabel = 'VIDEO';
      else if (isRawMime) formatLabel = 'RAW';
      else formatLabel = (props.photo.name || '').split('.').pop()?.toUpperCase() || '';

      const tileChildren = [];

      if (hasError.value) {
        // Fallback placeholder when NC has no preview (e.g. video without ffmpeg server-side)
        tileChildren.push(
          (0,vue__WEBPACK_IMPORTED_MODULE_0__.h)('div', { class: 'ps-tile-placeholder' }, [
            (0,vue__WEBPACK_IMPORTED_MODULE_0__.h)('div', { class: 'ps-tile-placeholder-badge' }, formatLabel),
            (0,vue__WEBPACK_IMPORTED_MODULE_0__.h)('div', { class: 'ps-tile-placeholder-meta' }, [
              (0,vue__WEBPACK_IMPORTED_MODULE_0__.h)('strong', null, props.photo.name),
              dateStr ? (0,vue__WEBPACK_IMPORTED_MODULE_0__.h)('span', null, dateStr) : null,
              caption ? (0,vue__WEBPACK_IMPORTED_MODULE_0__.h)('small', null, caption) : null,
            ].filter(Boolean)),
          ])
        );
      } else {
        // Meaningful alt: prefer location-based description over filename.
        // Decorative inside an already-labelled role=button parent would be
        // tempting but screen-readers may treat the figure label loosely;
        // keep a concise alt for image-only assistive contexts.
        const altText = caption || props.photo.location || props.photo.name || '';
        tileChildren.push(
          (0,vue__WEBPACK_IMPORTED_MODULE_0__.h)('img', {
            src,
            alt: altText,
            loading: 'lazy',
            decoding: 'async',
            class: 'ps-tile-img',
            onError: () => { hasError.value = true; },
          })
        );
      }

      // Play-button overlay for video tiles (visible on both successful preview and placeholder)
      if (isVideo) {
        tileChildren.push(
          (0,vue__WEBPACK_IMPORTED_MODULE_0__.h)('div', { class: 'ps-tile-video-badge', 'aria-hidden': 'true' }, [
            (0,vue__WEBPACK_IMPORTED_MODULE_0__.h)('svg', { width: 22, height: 22, viewBox: '0 0 24 24', fill: 'currentColor' }, [
              (0,vue__WEBPACK_IMPORTED_MODULE_0__.h)('path', { d: 'M8 5v14l11-7z' }),
            ]),
          ])
        );
      }

      if (props.showCaption && caption && !hasError.value) {
        tileChildren.push((0,vue__WEBPACK_IMPORTED_MODULE_0__.h)('figcaption', { class: 'ps-tile-caption' }, caption));
      }

      const accessibleLabel = caption || props.photo.name || 'photo';
      return (0,vue__WEBPACK_IMPORTED_MODULE_0__.h)('figure', {
        class: ['ps-tile', { 'ps-tile--placeholder': hasError.value, 'ps-tile--video': isVideo }],
        tabindex: 0,
        role: 'button',
        'aria-label': accessibleLabel,
        onClick: () => emit('click'),
        onKeydown: (e) => {
          if (e.key === 'Enter' || e.key === ' ' || e.key === 'Spacebar') {
            e.preventDefault();
            emit('click');
          }
        },
      }, tileChildren);
    };
  },
};

/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = ({
  name: 'PhotoStoryWidget',
  components: {
    AlertCircle: vue_material_design_icons_AlertCircle_vue__WEBPACK_IMPORTED_MODULE_5__["default"],
    ImageMultiple: vue_material_design_icons_ImageMultiple_vue__WEBPACK_IMPORTED_MODULE_6__["default"],
    LockOutline: vue_material_design_icons_LockOutline_vue__WEBPACK_IMPORTED_MODULE_8__["default"],
    NcButton: _nextcloud_vue__WEBPACK_IMPORTED_MODULE_4__.NcButton,
    MapMarker: vue_material_design_icons_MapMarker_vue__WEBPACK_IMPORTED_MODULE_7__["default"],
    PhotoTile,
    PhotoLightbox: (0,vue__WEBPACK_IMPORTED_MODULE_0__.defineAsyncComponent)(() => Promise.all(/*! import() */[__webpack_require__.e("vendors"), __webpack_require__.e("src_components_PhotoLightbox_vue")]).then(__webpack_require__.bind(__webpack_require__, /*! ./PhotoLightbox.vue */ "./src/components/PhotoLightbox.vue"))),
    PhotoStoryMap: (0,vue__WEBPACK_IMPORTED_MODULE_0__.defineAsyncComponent)(() => Promise.all(/*! import() */[__webpack_require__.e("vendors"), __webpack_require__.e("src_components_PhotoStoryMap_vue")]).then(__webpack_require__.bind(__webpack_require__, /*! ./PhotoStoryMap.vue */ "./src/components/PhotoStoryMap.vue"))),
    PhotoStoryDayMap: (0,vue__WEBPACK_IMPORTED_MODULE_0__.defineAsyncComponent)(() => __webpack_require__.e(/*! import() */ "src_components_PhotoStoryDayMap_vue").then(__webpack_require__.bind(__webpack_require__, /*! ./PhotoStoryDayMap.vue */ "./src/components/PhotoStoryDayMap.vue"))),
  },
  props: {
    widget: { type: Object, required: true },
    rowBackgroundColor: { type: String, default: '' },
  },
  emits: ['open-lightbox'],
  data() {
    return {
      photos: [],
      timeline: [],
      highlights: [],
      capabilities: null,
      // Map settings from the backend (Phase 3.0): admin-configurable tile URL,
      // attribution, max-zoom, plus a global enabled-flag.
      mapSettings: {
        enabled: true,
        tile_url: 'https://tile.openstreetmap.org/{z}/{x}/{y}.png',
        attribution: '© OpenStreetMap contributors',
        max_zoom: 19,
      },
      loading: true,
      error: null,
      // Set to 'folder_not_accessible' when the backend returns a 404 with
      // that reason — used by emptyMessage()/showScanHint() to surface a
      // permission-aware empty-state instead of the generic "no photos".
      accessReason: null,
      lightboxVisible: false,
      lightboxIndex: 0,
      // Client-side memo keyed by fetchKey — instant restore when switching
      // between modes (e.g. Timeline → Grid → Timeline) within the same session.
      _payloadCache: new Map(),
      // ETag from the last successful fetch (Phase 2.9.B2). Sent back as
      // If-None-Match so the server can return 304 instead of recomputing.
      _lastEtag: null,
      // Pagination state for folder-mode timeline + grid.
      pagination: {
        offset: 0,
        pageSize: 200,
        total: 0,
        hasMore: false,
        truncated: false,
      },
      loadingMore: false,
      _scrollObserver: null,
      _abortController: null,
    };
  },
  computed: {
    config() {
      return this.widget.config || {};
    },
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
      return dark.has(bg) ? 'ps--on-dark' : 'ps--on-tinted';
    },
    rowBgStyle() {
      if (this.rowBgClass !== 'ps--on-dark') {
        return {};
      }
      const textMap = {
        'var(--color-primary-element)': 'var(--color-primary-element-text)',
        'var(--color-primary)': 'var(--color-primary-text)',
        'var(--color-error)': 'var(--color-error-text)',
        'var(--color-success)': 'var(--color-success-text)',
        'var(--color-warning)': 'var(--color-warning-text)',
      };
      const bg = String(this.rowBackgroundColor || '').trim();
      const text = textMap[bg] || '#fff';
      return {
        '--ps-text': text,
        '--ps-text-muted': text,
      };
    },
    effectiveMode() {
      const m = this.config.mode || 'timeline';
      const allowed = ['timeline', 'highlights', 'grid', 'on-this-day'];
      return allowed.includes(m) ? m : 'timeline';
    },
    effectiveStyle() {
      const allowed = ['magazine', 'apple', 'travelogue'];
      const s = this.config.style || 'apple';
      return allowed.includes(s) ? s : 'apple';
    },
    emptyMessage() {
      const crossFolder = !!this.config.allMetaVoxFolders;
      const hasFilters = Array.isArray(this.config.metaVoxFilters) && this.config.metaVoxFilters.length > 0;
      if (crossFolder && !hasFilters) {
        return (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_3__.translate)('intravox', 'Add a filter to start');
      }
      if (!this.config.folderPath && !crossFolder) {
        return (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_3__.translate)('intravox', 'No folder selected');
      }
      if (this.accessReason === 'folder_not_accessible') {
        return (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_3__.translate)('intravox', 'You do not have access to this folder');
      }
      if (this.effectiveMode === 'on-this-day') {
        return (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_3__.translate)('intravox', 'No photos taken on this day in previous years');
      }
      return (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_3__.translate)('intravox', 'No photos found');
    },
    /**
     * Show the "files may not be indexed yet" hint only when it actually fits
     * the scenario. Hiding it for filtered queries / on-this-day / cross-folder
     * / no-access (each has its own most-likely explanation), showing it only
     * when the user picked a normal folder and got zero results back.
     */
    showScanHint() {
      const crossFolder = !!this.config.allMetaVoxFolders;
      const hasFilters = Array.isArray(this.config.metaVoxFilters) && this.config.metaVoxFilters.length > 0;
      if (crossFolder || hasFilters) return false;
      if (!this.config.folderPath) return false;
      if (this.accessReason === 'folder_not_accessible') return false;
      if (this.effectiveMode === 'on-this-day') return false;
      return true;
    },
    gridStyle() {
      const cols = Math.min(Math.max(this.config.columns || 3, 2), 5);
      return { '--ps-grid-cols': cols };
    },
    onThisDayGroups() {
      // Photos already sorted year-desc by backend
      const out = {};
      for (const p of this.photos) {
        if (!p.taken_at) continue;
        const year = String(new Date(p.taken_at).getFullYear());
        if (!out[year]) out[year] = [];
        out[year].push(p);
      }
      return out;
    },
    timelineDays() {
      // Backend already returns days in the requested sortOrder (paged path).
      // We precompute `_gpsPoints` and `_hasGps` per day so the template doesn't
      // call helper methods inside v-for — those create a new array per render
      // pass and trick PhotoStoryDayMap into thinking its `points` prop changed,
      // which re-instantiates Leaflet on every parent re-render.
      let days;
      if (this.timeline && this.timeline.length) {
        days = this.timeline;
      } else if (this.photos.length) {
        const fmt = new Intl.DateTimeFormat((0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_3__.getCanonicalLocale)(), { day: 'numeric', month: 'long', year: 'numeric' });
        const sample = this.photos[0];
        const ts = sample.taken_at ? new Date(sample.taken_at) : new Date(sample.mtime * 1000);
        days = [{
          date: ts.toISOString().slice(0, 10),
          label: fmt.format(ts),
          location_summary: null,
          photos: this.photos,
        }];
      } else {
        return [];
      }
      return days.map(day => {
        const points = (day.photos || [])
          .filter(p => p && p.gps && Number.isFinite(p.gps.lat) && Number.isFinite(p.gps.lon))
          .map(p => ({ lat: p.gps.lat, lon: p.gps.lon, file_id: p.file_id, name: p.name }));
        return { ...day, _gpsPoints: points, _hasGps: points.length > 0 };
      });
    },
    // Stable key over only the request-changing config fields. Watching this
    // (instead of the whole widget) prevents cosmetic editor changes (style,
    // columns, toggles, title) from re-fetching the photo list.
    fetchKey() {
      const c = this.config || {};
      return JSON.stringify({
        f: c.folderPath || '',
        m: c.mode || 'timeline',
        l: c.limit ?? null,
        s: c.sortOrder || 'desc',
        sb: c.sortBy || 'mtime',
        x: !!c.allMetaVoxFolders,
        flt: Array.isArray(c.metaVoxFilters) ? c.metaVoxFilters : [],
        dr: c.hideRawDuplicates !== false,
      });
    },
    yearScrubber() {
      // Distinct years present in the timeline, descending (newest first)
      const years = new Set();
      for (const day of this.timelineDays) {
        const y = parseInt((day.date || '').slice(0, 4), 10);
        if (Number.isFinite(y)) years.add(y);
      }
      return Array.from(years).sort((a, b) => b - a);
    },
  },
  watch: {
    // Only re-fetch when a DATA-relevant field changes (folder, mode, filters, limit).
    // Cosmetic fields (style, columns, captions, map toggles, title) reuse the
    // existing `photos` array — no HTTP round-trip needed.
    fetchKey: {
      handler(newKey, oldKey) {
        if (newKey === oldKey) return;
        // 700 ms debounce — see FileStoryWidget for the rationale. Prevents
        // edit-mode mutation storms from stacking expensive listPhotos calls
        // on Apache workers and crashing the subsequent save with 503.
        clearTimeout(this._debounce);
        this._debounce = setTimeout(() => this.fetch(), 700);
      },
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
      this._idleHandle = null;
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
    // Whether the backend will paginate this request. Mirrors controller logic:
    // only folder-mode timeline + grid go through the paged path.
    isPagedMode() {
      const crossFolder = !!this.config.allMetaVoxFolders;
      const m = this.effectiveMode;
      return !crossFolder && !!this.config.folderPath && (m === 'timeline' || m === 'grid');
    },
    async fetch() {
      const crossFolder = !!this.config.allMetaVoxFolders;
      const hasFilters = Array.isArray(this.config.metaVoxFilters) && this.config.metaVoxFilters.length > 0;

      // Cross-folder mode but no filters yet → empty state.
      if (crossFolder && !hasFilters) {
        this.photos = [];
        this.timeline = [];
        this.highlights = [];
        this.loading = false;
        return;
      }
      // No folder picked + not in cross-folder mode → empty state.
      if (!crossFolder && !this.config.folderPath) {
        this.photos = [];
        this.timeline = [];
        this.highlights = [];
        this.loading = false;
        return;
      }

      // Client-side memo for non-paged modes only. Paged mode appends pages
      // into `this.photos` via fetchMore(); restoring the cached first-page
      // payload would silently drop the user's accumulated infinite-scroll
      // state — so we always re-fetch in paged mode and rely on the server's
      // 304 (via If-None-Match) to keep it cheap when nothing changed.
      const key = this.fetchKey;
      if (!this.isPagedMode()) {
        const cached = this._payloadCache.get(key);
        if (cached) {
          this.photos = cached.photos || [];
          this.timeline = cached.timeline || [];
          this.highlights = cached.highlights || [];
          this.capabilities = cached.capabilities || null;
          this.pagination = cached.pagination || { offset: 0, pageSize: 200, total: 0, hasMore: false, truncated: false };
          this.loading = false;
          return;
        }
      }

      // Cancel any pending fetch/fetchMore so we don't race a stale response.
      if (this._abortController) {
        this._abortController.abort();
      }
      this._abortController = new AbortController();
      const signal = this._abortController.signal;

      // Reset pagination for first page
      this.pagination = { offset: 0, pageSize: 200, total: 0, hasMore: false, truncated: false };

      this.loading = true;
      this.error = null;
      this.accessReason = null;
      try {
        const params = this.buildPhotosParams(0);
        const url = (0,_nextcloud_router__WEBPACK_IMPORTED_MODULE_2__.generateUrl)(`/apps/intravox/api/photo-story/photos?${params.toString()}`);
        const headers = {};
        if (this._lastEtag) {
          headers['If-None-Match'] = this._lastEtag;
        }
        const res = await _nextcloud_axios__WEBPACK_IMPORTED_MODULE_1__["default"].get(url, { headers, signal, validateStatus: s => (s >= 200 && s < 300) || s === 304 || s === 404 });
        if (res.status === 304) {
          this.loading = false;
          return;
        }
        if (res.status === 404) {
          // Folder doesn't exist for this user — empty state, not error.
          this.photos = [];
          this.timeline = [];
          this.highlights = [];
          this.capabilities = null;
          this.accessReason = res.data?.reason || null;
          this.loading = false;
          return;
        }
        const data = res.data || {};
        this.photos = data.photos || [];
        this.timeline = data.timeline || [];
        this.highlights = data.highlights || [];
        this.capabilities = data.capabilities || null;
        this.warmFederatedPreviews(this.photos);
        if (data.map && typeof data.map === 'object') {
          this.mapSettings = { ...this.mapSettings, ...data.map };
        }
        if (data.pagination) {
          this.pagination = {
            offset: data.pagination.offset || 0,
            pageSize: data.pagination.pageSize || 200,
            total: data.pagination.total || this.photos.length,
            hasMore: !!data.pagination.hasMore,
            truncated: !!data.pagination.truncated,
          };
        }
        if (res.headers && res.headers.etag) {
          this._lastEtag = res.headers.etag;
        }
        if (!this.isPagedMode()) {
          if (this._payloadCache.size >= 8) {
            const oldestKey = this._payloadCache.keys().next().value;
            this._payloadCache.delete(oldestKey);
          }
          this._payloadCache.set(key, {
            photos: this.photos,
            timeline: this.timeline,
            highlights: this.highlights,
            capabilities: this.capabilities,
            pagination: { ...this.pagination },
          });
        }
        // Activate infinite-scroll observer when more pages are available.
        this.$nextTick(() => this.setupScrollSentinel());
      } catch (err) {
        if (_nextcloud_axios__WEBPACK_IMPORTED_MODULE_1__["default"].isCancel?.(err) || err?.name === 'CanceledError' || err?.code === 'ERR_CANCELED') {
          return;
        }
        console.error('[PhotoStoryWidget] fetch failed:', err);
        this.error = err.response?.data?.error || err.message || this.t('Failed to load photos');
      } finally {
        this.loading = false;
      }
    },
    buildPhotosParams(offset = 0) {
      const params = new URLSearchParams({ mode: this.effectiveMode });
      const crossFolder = !!this.config.allMetaVoxFolders;
      const hasFilters = Array.isArray(this.config.metaVoxFilters) && this.config.metaVoxFilters.length > 0;
      if (this.config.folderPath && !crossFolder) {
        params.append('folder', this.config.folderPath);
      }
      if (hasFilters) {
        params.append('filters', JSON.stringify(this.config.metaVoxFilters));
      }
      if (this.config.limit) {
        params.append('limit', String(this.config.limit));
      }
      if (this.isPagedMode()) {
        params.append('offset', String(offset));
        params.append('pageSize', String(this.pagination.pageSize || 200));
        params.append('sortOrder', this.config.sortOrder || 'desc');
        params.append('sortBy', this.config.sortBy || 'mtime');
        // Pass the total from the first page so the backend can skip the
        // expensive COUNT(*) on subsequent fetchMore() calls.
        if (offset > 0 && this.pagination.total > 0) {
          params.append('total', String(this.pagination.total));
        }
      }
      // Only send the param when the user explicitly opted out. Backend
      // defaults to dedup=on, so omitting matches existing widgets.
      if (this.config.hideRawDuplicates === false) {
        params.append('hideRawDuplicates', '0');
      }
      return params;
    },
    async fetchMore() {
      if (this.loadingMore || !this.pagination.hasMore || !this.isPagedMode()) return;
      this.loadingMore = true;
      const signal = this._abortController?.signal;
      try {
        const nextOffset = this.pagination.offset + this.pagination.pageSize;
        const params = this.buildPhotosParams(nextOffset);
        const url = (0,_nextcloud_router__WEBPACK_IMPORTED_MODULE_2__.generateUrl)(`/apps/intravox/api/photo-story/photos?${params.toString()}`);
        const res = await _nextcloud_axios__WEBPACK_IMPORTED_MODULE_1__["default"].get(url, { signal, validateStatus: s => s >= 200 && s < 300 });
        const data = res.data || {};
        const newPhotos = data.photos || [];
        this.photos = this.photos.concat(newPhotos);
        this.warmFederatedPreviews(newPhotos);

        // Merge incoming timeline-days into existing timeline. Backend returns
        // grouped days for THIS page; we merge by date key, then explicitly
        // re-sort by date so cross-page bucket-order is always chronological
        // (insertion-order is wrong when a later page reaches back into earlier
        // dates — happens when SQL mtime-sort and bucket sort disagree, or with
        // mixed taken_at/mtime fallbacks).
        if (Array.isArray(data.timeline) && data.timeline.length) {
          const byDate = new Map();
          for (const d of this.timeline) byDate.set(d.date, d);
          for (const d of data.timeline) {
            if (byDate.has(d.date)) {
              const existing = byDate.get(d.date);
              existing.photos = existing.photos.concat(d.photos || []);
            } else {
              byDate.set(d.date, d);
            }
          }
          const ord = this.config.sortOrder || 'desc';
          this.timeline = Array.from(byDate.values()).sort((a, b) => ord === 'desc'
            ? b.date.localeCompare(a.date)
            : a.date.localeCompare(b.date));
        }

        if (data.pagination) {
          this.pagination = {
            offset: data.pagination.offset || nextOffset,
            pageSize: data.pagination.pageSize || this.pagination.pageSize,
            total: data.pagination.total || this.pagination.total,
            hasMore: !!data.pagination.hasMore,
            truncated: !!data.pagination.truncated,
          };
        }
      } catch (err) {
        if (_nextcloud_axios__WEBPACK_IMPORTED_MODULE_1__["default"].isCancel?.(err) || err?.name === 'CanceledError' || err?.code === 'ERR_CANCELED') {
          return;
        }
        console.error('[PhotoStoryWidget] fetchMore failed:', err);
      } finally {
        this.loadingMore = false;
      }
    },
    setupScrollSentinel() {
      // Tear down previous observer (e.g. on re-fetch)
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
              // After loading next page, reconnect or disconnect based on hasMore
              if (!this.pagination.hasMore && this._scrollObserver) {
                this._scrollObserver.disconnect();
                this._scrollObserver = null;
              }
            });
            break;
          }
        }
      }, { rootMargin: '800px' });
      this._scrollObserver.observe(sentinel);
    },
    /**
     * Best-effort pre-warm of the federated-preview cache. Picks every
     * `is_federated: true` photo from a newly loaded page and POSTs the
     * fileIds to the warmup endpoint. Backend handles cap, dedup and
     * outbound concurrency limit; we don't await the response.
     */
    warmFederatedPreviews(photos) {
      try {
        if (!Array.isArray(photos) || photos.length === 0) return;
        const fids = photos
          .filter(p => p && p.is_federated && p.file_id)
          .map(p => p.file_id);
        if (fids.length === 0) return;
        const url = (0,_nextcloud_router__WEBPACK_IMPORTED_MODULE_2__.generateUrl)('/apps/intravox/api/preview/warmup');
        _nextcloud_axios__WEBPACK_IMPORTED_MODULE_1__["default"].post(url, { file_ids: fids }).catch(() => {});
      } catch (_) {
        // Pre-warm is fire-and-forget — never block render on it.
      }
    },
    previewUrl(photo, size = 512) {
      // Federated files cannot be served by NC core preview (the providers
      // can't reach the remote storage), so route them through IntraVox's
      // preview-proxy. Local files keep the direct /core/preview path.
      if (photo && photo.is_federated) {
        return (0,_nextcloud_router__WEBPACK_IMPORTED_MODULE_2__.generateUrl)(`/apps/intravox/api/preview?file_id=${photo.file_id}&x=${size}&y=${size}`);
      }
      const fid = (photo && typeof photo === 'object') ? photo.file_id : photo;
      return (0,_nextcloud_router__WEBPACK_IMPORTED_MODULE_2__.generateUrl)(`/core/preview?fileId=${fid}&x=${size}&y=${size}&a=true`);
    },
    formatDate(iso) {
      if (!iso) return '';
      try {
        const d = new Date(iso);
        return d.toLocaleDateString((0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_3__.getCanonicalLocale)(), { day: 'numeric', month: 'long', year: 'numeric' });
      } catch (e) {
        return iso;
      }
    },
    // Locale-aware long-date formatter for sticky day-headers (all three styles).
    // Year is always included so days from different years stay visually distinct.
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
    // Deterministic pseudo-random column-span for magazine style (no width/height yet in photo dict).
    // Hash on file_id so the same photo always lands in the same cell — no flicker on re-render.
    magazineTileStyle(photo) {
      const id = Number(photo.file_id) || 0;
      const span = (id % 3) + 1; // 1, 2 or 3 columns
      return { gridColumn: `span ${span}` };
    },
    // For timeline mode: map photo to its index in the global photos array
    globalIndex(dayPhotos, idx) {
      const target = dayPhotos[idx];
      if (!target) return 0;
      const i = this.photos.findIndex(p => p.file_id === target.file_id);
      return i >= 0 ? i : 0;
    },
    // Mini-map helpers (Phase 2.8): show a per-day Leaflet map above each day-header.
    // Day-level _hasGps / _gpsPoints are precomputed in the timelineDays computed.
    showDayMaps() {
      return this.config.showDayMaps !== false;
    },
    openLightboxByFileId(fileId) {
      const idx = this.photos.findIndex(p => p.file_id === fileId);
      if (idx >= 0) this.openLightbox(idx);
    },
    scrollToYear(year) {
      // Find the first day-section whose date starts with that year and scroll to it
      const root = this.$el;
      if (!root) return;
      const sections = root.querySelectorAll('.ps-timeline section[class^="ps-day-"]');
      const prefix = String(year);
      for (const sec of sections) {
        // Sections use :key="day.date"; we need to match by their first descendant date label.
        const dateAttr = sec.dataset.date || '';
        if (dateAttr.startsWith(prefix)) {
          sec.scrollIntoView({ behavior: 'smooth', block: 'start' });
          return;
        }
      }
      // Fallback: search inside sections' first header text for the year
      for (const sec of sections) {
        const h = sec.querySelector('h2, h3, h4');
        if (h && h.textContent.includes(prefix)) {
          sec.scrollIntoView({ behavior: 'smooth', block: 'start' });
          return;
        }
      }
    },
    // Pseudo-random masonry: 1 in ~3 photos spans 2 rows for visual rhythm
    masonryItemStyle(photo) {
      const mtime = photo.mtime || photo.file_id || 0;
      const tall = (mtime % 3) === 0;
      return tall ? { gridRowEnd: 'span 2' } : {};
    },
    openLightbox(index) {
      this.lightboxIndex = index;
      this.lightboxVisible = true;
      this.$emit('open-lightbox', { index, photos: this.photos });
    },
    onClusterClick(payload) {
      // Open lightbox on first matching photo in cluster
      const ids = payload?.photo_ids || [];
      if (!ids.length) return;
      const idx = this.photos.findIndex(p => ids.includes(p.file_id));
      if (idx >= 0) {
        this.openLightbox(idx);
      }
    },
  },
});


/***/ },

/***/ "./src/components/PhotoStoryWidget.vue?vue&type=style&index=0&id=745c96b7&scoped=true&lang=css"
/*!*****************************************************************************************************!*\
  !*** ./src/components/PhotoStoryWidget.vue?vue&type=style&index=0&id=745c96b7&scoped=true&lang=css ***!
  \*****************************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _node_modules_style_loader_dist_cjs_js_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_dist_stylePostLoader_js_node_modules_vue_loader_dist_index_js_ruleSet_1_rules_5_use_0_PhotoStoryWidget_vue_vue_type_style_index_0_id_745c96b7_scoped_true_lang_css__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../node_modules/style-loader/dist/cjs.js!../../node_modules/css-loader/dist/cjs.js!../../node_modules/vue-loader/dist/stylePostLoader.js!../../node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./PhotoStoryWidget.vue?vue&type=style&index=0&id=745c96b7&scoped=true&lang=css */ "./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/dist/stylePostLoader.js!./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/PhotoStoryWidget.vue?vue&type=style&index=0&id=745c96b7&scoped=true&lang=css");


/***/ },

/***/ "./src/components/PhotoStoryWidget.vue?vue&type=script&lang=js"
/*!*********************************************************************!*\
  !*** ./src/components/PhotoStoryWidget.vue?vue&type=script&lang=js ***!
  \*********************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* reexport safe */ _node_modules_vue_loader_dist_index_js_ruleSet_1_rules_5_use_0_PhotoStoryWidget_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_0__["default"])
/* harmony export */ });
/* harmony import */ var _node_modules_vue_loader_dist_index_js_ruleSet_1_rules_5_use_0_PhotoStoryWidget_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./PhotoStoryWidget.vue?vue&type=script&lang=js */ "./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/PhotoStoryWidget.vue?vue&type=script&lang=js");
 

/***/ },

/***/ "./src/components/PhotoStoryWidget.vue?vue&type=template&id=745c96b7&scoped=true"
/*!***************************************************************************************!*\
  !*** ./src/components/PhotoStoryWidget.vue?vue&type=template&id=745c96b7&scoped=true ***!
  \***************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   render: () => (/* reexport safe */ _node_modules_vue_loader_dist_templateLoader_js_ruleSet_1_rules_2_node_modules_vue_loader_dist_index_js_ruleSet_1_rules_5_use_0_PhotoStoryWidget_vue_vue_type_template_id_745c96b7_scoped_true__WEBPACK_IMPORTED_MODULE_0__.render)
/* harmony export */ });
/* harmony import */ var _node_modules_vue_loader_dist_templateLoader_js_ruleSet_1_rules_2_node_modules_vue_loader_dist_index_js_ruleSet_1_rules_5_use_0_PhotoStoryWidget_vue_vue_type_template_id_745c96b7_scoped_true__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../node_modules/vue-loader/dist/templateLoader.js??ruleSet[1].rules[2]!../../node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./PhotoStoryWidget.vue?vue&type=template&id=745c96b7&scoped=true */ "./node_modules/vue-loader/dist/templateLoader.js??ruleSet[1].rules[2]!./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/PhotoStoryWidget.vue?vue&type=template&id=745c96b7&scoped=true");


/***/ },

/***/ "./node_modules/vue-loader/dist/templateLoader.js??ruleSet[1].rules[2]!./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/PhotoStoryWidget.vue?vue&type=template&id=745c96b7&scoped=true"
/*!*********************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/vue-loader/dist/templateLoader.js??ruleSet[1].rules[2]!./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/PhotoStoryWidget.vue?vue&type=template&id=745c96b7&scoped=true ***!
  \*********************************************************************************************************************************************************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   render: () => (/* binding */ render)
/* harmony export */ });
/* harmony import */ var vue__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! vue */ "./node_modules/vue/dist/vue.runtime.esm-bundler.js");


const _hoisted_1 = ["aria-label"]
const _hoisted_2 = {
  class: "ps-error",
  role: "alert"
}
const _hoisted_3 = {
  class: "ps-empty ps-empty--locked",
  role: "status"
}
const _hoisted_4 = {
  class: "ps-empty",
  role: "status"
}
const _hoisted_5 = { key: 0 }
const _hoisted_6 = {
  key: 1,
  class: "ps-empty-hint"
}
const _hoisted_7 = { class: "ps-timeline ps-timeline--magazine" }
const _hoisted_8 = ["data-date"]
const _hoisted_9 = { class: "ps-day-mag-header" }
const _hoisted_10 = { class: "ps-day-mag-date" }
const _hoisted_11 = {
  key: 0,
  class: "ps-day-mag-loc"
}
const _hoisted_12 = { class: "ps-mag-grid" }
const _hoisted_13 = ["aria-label", "onClick", "onKeydown"]
const _hoisted_14 = ["src", "alt"]
const _hoisted_15 = { class: "ps-timeline ps-timeline--apple" }
const _hoisted_16 = ["data-date"]
const _hoisted_17 = { class: "ps-day-apple-header" }
const _hoisted_18 = { class: "ps-day-apple-date" }
const _hoisted_19 = {
  key: 0,
  class: "ps-day-apple-loc"
}
const _hoisted_20 = { class: "ps-apple-grid" }
const _hoisted_21 = { class: "ps-timeline ps-timeline--travelogue" }
const _hoisted_22 = ["data-date"]
const _hoisted_23 = { class: "ps-trav-content" }
const _hoisted_24 = { class: "ps-day-trav-header" }
const _hoisted_25 = { class: "ps-day-trav-date" }
const _hoisted_26 = {
  key: 0,
  class: "ps-day-trav-loc"
}
const _hoisted_27 = { class: "ps-trav-grid" }
const _hoisted_28 = { class: "ps-highlights" }
const _hoisted_29 = ["aria-label"]
const _hoisted_30 = ["src", "alt"]
const _hoisted_31 = {
  key: 0,
  class: "ps-hero-caption"
}
const _hoisted_32 = { key: 0 }
const _hoisted_33 = { class: "ps-grid ps-grid--two" }
const _hoisted_34 = { class: "ps-on-this-day" }
const _hoisted_35 = { class: "ps-day-header" }
const _hoisted_36 = { class: "ps-day-label" }
const _hoisted_37 = { class: "ps-grid ps-grid--responsive" }
const _hoisted_38 = ["aria-label"]
const _hoisted_39 = ["title", "onClick"]
const _hoisted_40 = {
  key: 12,
  ref: "scrollSentinel",
  class: "ps-scroll-sentinel",
  "aria-hidden": "true"
}
const _hoisted_41 = {
  key: 0,
  class: "ps-loading-more"
}
const _hoisted_42 = {
  key: 13,
  class: "ps-truncated"
}

function render(_ctx, _cache, $props, $setup, $data, $options) {
  const _component_PhotoStoryMap = (0,vue__WEBPACK_IMPORTED_MODULE_0__.resolveComponent)("PhotoStoryMap")
  const _component_AlertCircle = (0,vue__WEBPACK_IMPORTED_MODULE_0__.resolveComponent)("AlertCircle")
  const _component_NcButton = (0,vue__WEBPACK_IMPORTED_MODULE_0__.resolveComponent)("NcButton")
  const _component_LockOutline = (0,vue__WEBPACK_IMPORTED_MODULE_0__.resolveComponent)("LockOutline")
  const _component_ImageMultiple = (0,vue__WEBPACK_IMPORTED_MODULE_0__.resolveComponent)("ImageMultiple")
  const _component_PhotoStoryDayMap = (0,vue__WEBPACK_IMPORTED_MODULE_0__.resolveComponent)("PhotoStoryDayMap")
  const _component_MapMarker = (0,vue__WEBPACK_IMPORTED_MODULE_0__.resolveComponent)("MapMarker")
  const _component_PhotoTile = (0,vue__WEBPACK_IMPORTED_MODULE_0__.resolveComponent)("PhotoTile")
  const _component_PhotoLightbox = (0,vue__WEBPACK_IMPORTED_MODULE_0__.resolveComponent)("PhotoLightbox")

  return ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)("div", {
    class: (0,vue__WEBPACK_IMPORTED_MODULE_0__.normalizeClass)(["photo-story-widget", $options.rowBgClass]),
    style: (0,vue__WEBPACK_IMPORTED_MODULE_0__.normalizeStyle)($options.rowBgStyle)
  }, [
    (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)(" Optional map at top "),
    ($options.config.showMap && $options.config.folderPath && $data.capabilities && $data.capabilities.hasLocation && $data.mapSettings.enabled)
      ? ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createBlock)(_component_PhotoStoryMap, {
          key: 0,
          "folder-path": $options.config.folderPath,
          class: "ps-map-embed",
          onClusterClick: $options.onClusterClick
        }, null, 8 /* PROPS */, ["folder-path", "onClusterClick"]))
      : (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)("v-if", true),
    (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)(" Loading skeleton "),
    ($data.loading)
      ? ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)("div", {
          key: 1,
          class: "ps-loading",
          role: "status",
          "aria-live": "polite",
          "aria-label": $options.t('Loading photos')
        }, [
          ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)(vue__WEBPACK_IMPORTED_MODULE_0__.Fragment, null, (0,vue__WEBPACK_IMPORTED_MODULE_0__.renderList)(6, (i) => {
            return (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("div", {
              key: i,
              class: "ps-skeleton-tile",
              "aria-hidden": "true"
            })
          }), 64 /* STABLE_FRAGMENT */))
        ], 8 /* PROPS */, _hoisted_1))
      : ($data.error)
        ? ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)(vue__WEBPACK_IMPORTED_MODULE_0__.Fragment, { key: 2 }, [
            (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)(" Error "),
            (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("div", _hoisted_2, [
              (0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_AlertCircle, {
                size: 24,
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
        : (!$data.photos.length && $data.accessReason === 'folder_not_accessible')
          ? ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)(vue__WEBPACK_IMPORTED_MODULE_0__.Fragment, { key: 3 }, [
              (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)(" No-access: minimal locked placeholder; deliberately hides the folder\n         name to avoid leaking the existence of a path the viewer can't see. "),
              (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("div", _hoisted_3, [
                (0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_LockOutline, {
                  size: 32,
                  "aria-hidden": "true"
                }),
                (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("p", null, (0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)($options.t('You do not have access to this widget')), 1 /* TEXT */)
              ])
            ], 2112 /* STABLE_FRAGMENT, DEV_ROOT_FRAGMENT */))
          : (!$data.photos.length)
            ? ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)(vue__WEBPACK_IMPORTED_MODULE_0__.Fragment, { key: 4 }, [
                (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)(" Empty (accessible folder but no photos to show) "),
                (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("div", _hoisted_4, [
                  (0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_ImageMultiple, {
                    size: 32,
                    "aria-hidden": "true"
                  }),
                  (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("p", null, (0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)($options.emptyMessage), 1 /* TEXT */),
                  ($options.config.folderPath)
                    ? ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)("small", _hoisted_5, (0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)($options.config.folderPath), 1 /* TEXT */))
                    : (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)("v-if", true),
                  ($options.showScanHint)
                    ? ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)("small", _hoisted_6, (0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)($options.t('If you can see photos in the Files app but not here, the file index may be out of sync. Ask an admin to run "occ files:scan" for this folder.')), 1 /* TEXT */))
                    : (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)("v-if", true)
                ])
              ], 2112 /* STABLE_FRAGMENT, DEV_ROOT_FRAGMENT */))
            : ($options.effectiveMode === 'timeline' && $options.effectiveStyle === 'magazine')
              ? ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)(vue__WEBPACK_IMPORTED_MODULE_0__.Fragment, { key: 5 }, [
                  (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)(" Timeline mode — Magazine style "),
                  (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("div", _hoisted_7, [
                    ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(true), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)(vue__WEBPACK_IMPORTED_MODULE_0__.Fragment, null, (0,vue__WEBPACK_IMPORTED_MODULE_0__.renderList)($options.timelineDays, (day) => {
                      return ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)("section", {
                        key: day.date,
                        "data-date": day.date,
                        class: "ps-day-mag"
                      }, [
                        (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("header", _hoisted_9, [
                          (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("h2", _hoisted_10, (0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)($options.formatLongDate(day.date)), 1 /* TEXT */),
                          (day.location_summary)
                            ? ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)("p", _hoisted_11, (0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)(day.location_summary), 1 /* TEXT */))
                            : (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)("v-if", true)
                        ]),
                        ($options.showDayMaps() && day._hasGps && $data.mapSettings.enabled)
                          ? ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createBlock)(_component_PhotoStoryDayMap, {
                              key: 0,
                              "tile-url": $data.mapSettings.tile_url,
                              attribution: $data.mapSettings.attribution,
                              "max-zoom": $data.mapSettings.max_zoom,
                              points: day._gpsPoints,
                              height: 180,
                              class: "ps-day-mag-map",
                              onPhotoClick: _cache[1] || (_cache[1] = $event => ($options.openLightboxByFileId($event)))
                            }, null, 8 /* PROPS */, ["tile-url", "attribution", "max-zoom", "points"]))
                          : (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)("v-if", true),
                        (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("div", _hoisted_12, [
                          ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(true), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)(vue__WEBPACK_IMPORTED_MODULE_0__.Fragment, null, (0,vue__WEBPACK_IMPORTED_MODULE_0__.renderList)(day.photos, (photo, idx) => {
                            return ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)("figure", {
                              key: photo.file_id,
                              class: "ps-mag-tile",
                              style: (0,vue__WEBPACK_IMPORTED_MODULE_0__.normalizeStyle)($options.magazineTileStyle(photo)),
                              tabindex: "0",
                              role: "button",
                              "aria-label": $options.t('Open photo {name}', { name: photo.name }),
                              onClick: $event => ($options.openLightbox($options.globalIndex(day.photos, idx))),
                              onKeydown: [
                                (0,vue__WEBPACK_IMPORTED_MODULE_0__.withKeys)($event => ($options.openLightbox($options.globalIndex(day.photos, idx))), ["enter"]),
                                (0,vue__WEBPACK_IMPORTED_MODULE_0__.withKeys)((0,vue__WEBPACK_IMPORTED_MODULE_0__.withModifiers)($event => ($options.openLightbox($options.globalIndex(day.photos, idx))), ["prevent"]), ["space"])
                              ]
                            }, [
                              (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("img", {
                                src: $options.previewUrl(photo, 800),
                                alt: photo.location_display || photo.location || photo.name,
                                loading: "lazy",
                                decoding: "async",
                                class: "ps-mag-img"
                              }, null, 8 /* PROPS */, _hoisted_14)
                            ], 44 /* STYLE, PROPS, NEED_HYDRATION */, _hoisted_13))
                          }), 128 /* KEYED_FRAGMENT */))
                        ])
                      ], 8 /* PROPS */, _hoisted_8))
                    }), 128 /* KEYED_FRAGMENT */))
                  ])
                ], 2112 /* STABLE_FRAGMENT, DEV_ROOT_FRAGMENT */))
              : ($options.effectiveMode === 'timeline' && $options.effectiveStyle === 'apple')
                ? ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)(vue__WEBPACK_IMPORTED_MODULE_0__.Fragment, { key: 6 }, [
                    (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)(" Timeline mode — Apple style (default) "),
                    (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("div", _hoisted_15, [
                      ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(true), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)(vue__WEBPACK_IMPORTED_MODULE_0__.Fragment, null, (0,vue__WEBPACK_IMPORTED_MODULE_0__.renderList)($options.timelineDays, (day) => {
                        return ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)("section", {
                          key: day.date,
                          "data-date": day.date,
                          class: "ps-day-apple"
                        }, [
                          (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("header", _hoisted_17, [
                            (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("h3", _hoisted_18, (0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)($options.formatLongDate(day.date)), 1 /* TEXT */),
                            (day.location_summary)
                              ? ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)("span", _hoisted_19, [
                                  (0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_MapMarker, { size: 14 }),
                                  (0,vue__WEBPACK_IMPORTED_MODULE_0__.createTextVNode)(" " + (0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)(day.location_summary), 1 /* TEXT */)
                                ]))
                              : (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)("v-if", true)
                          ]),
                          ($options.showDayMaps() && day._hasGps && $data.mapSettings.enabled)
                            ? ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createBlock)(_component_PhotoStoryDayMap, {
                                key: 0,
                                "tile-url": $data.mapSettings.tile_url,
                                attribution: $data.mapSettings.attribution,
                                "max-zoom": $data.mapSettings.max_zoom,
                                points: day._gpsPoints,
                                height: 140,
                                class: "ps-day-apple-map",
                                onPhotoClick: _cache[2] || (_cache[2] = $event => ($options.openLightboxByFileId($event)))
                              }, null, 8 /* PROPS */, ["tile-url", "attribution", "max-zoom", "points"]))
                            : (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)("v-if", true),
                          (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("div", _hoisted_20, [
                            ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(true), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)(vue__WEBPACK_IMPORTED_MODULE_0__.Fragment, null, (0,vue__WEBPACK_IMPORTED_MODULE_0__.renderList)(day.photos, (photo, idx) => {
                              return ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createBlock)(_component_PhotoTile, {
                                key: photo.file_id,
                                photo: photo,
                                "show-caption": false,
                                onClick: $event => ($options.openLightbox($options.globalIndex(day.photos, idx)))
                              }, null, 8 /* PROPS */, ["photo", "onClick"]))
                            }), 128 /* KEYED_FRAGMENT */))
                          ])
                        ], 8 /* PROPS */, _hoisted_16))
                      }), 128 /* KEYED_FRAGMENT */))
                    ])
                  ], 2112 /* STABLE_FRAGMENT, DEV_ROOT_FRAGMENT */))
                : ($options.effectiveMode === 'timeline' && $options.effectiveStyle === 'travelogue')
                  ? ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)(vue__WEBPACK_IMPORTED_MODULE_0__.Fragment, { key: 7 }, [
                      (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)(" Timeline mode — Travelogue style (Polarsteps-like rail) "),
                      (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("div", _hoisted_21, [
                        ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(true), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)(vue__WEBPACK_IMPORTED_MODULE_0__.Fragment, null, (0,vue__WEBPACK_IMPORTED_MODULE_0__.renderList)($options.timelineDays, (day) => {
                          return ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)("section", {
                            key: day.date,
                            "data-date": day.date,
                            class: "ps-day-trav"
                          }, [
                            _cache[8] || (_cache[8] = (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("div", { class: "ps-trav-rail" }, [
                              (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("div", { class: "ps-trav-bullet" })
                            ], -1 /* CACHED */)),
                            (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("div", _hoisted_23, [
                              (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("header", _hoisted_24, [
                                (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("h3", _hoisted_25, (0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)($options.formatLongDate(day.date)), 1 /* TEXT */),
                                (day.location_summary)
                                  ? ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)("p", _hoisted_26, [
                                      (0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_MapMarker, { size: 14 }),
                                      (0,vue__WEBPACK_IMPORTED_MODULE_0__.createTextVNode)(" " + (0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)(day.location_summary), 1 /* TEXT */)
                                    ]))
                                  : (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)("v-if", true)
                              ]),
                              ($options.showDayMaps() && day._hasGps && $data.mapSettings.enabled)
                                ? ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createBlock)(_component_PhotoStoryDayMap, {
                                    key: 0,
                                    "tile-url": $data.mapSettings.tile_url,
                                    attribution: $data.mapSettings.attribution,
                                    "max-zoom": $data.mapSettings.max_zoom,
                                    points: day._gpsPoints,
                                    height: 150,
                                    class: "ps-day-trav-map",
                                    onPhotoClick: _cache[3] || (_cache[3] = $event => ($options.openLightboxByFileId($event)))
                                  }, null, 8 /* PROPS */, ["tile-url", "attribution", "max-zoom", "points"]))
                                : (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)("v-if", true),
                              (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("div", _hoisted_27, [
                                ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(true), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)(vue__WEBPACK_IMPORTED_MODULE_0__.Fragment, null, (0,vue__WEBPACK_IMPORTED_MODULE_0__.renderList)(day.photos, (photo, idx) => {
                                  return ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createBlock)(_component_PhotoTile, {
                                    key: photo.file_id,
                                    photo: photo,
                                    "show-caption": false,
                                    onClick: $event => ($options.openLightbox($options.globalIndex(day.photos, idx)))
                                  }, null, 8 /* PROPS */, ["photo", "onClick"]))
                                }), 128 /* KEYED_FRAGMENT */))
                              ])
                            ])
                          ], 8 /* PROPS */, _hoisted_22))
                        }), 128 /* KEYED_FRAGMENT */))
                      ])
                    ], 2112 /* STABLE_FRAGMENT, DEV_ROOT_FRAGMENT */))
                  : ($options.effectiveMode === 'highlights')
                    ? ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)(vue__WEBPACK_IMPORTED_MODULE_0__.Fragment, { key: 8 }, [
                        (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)(" Highlights mode "),
                        (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("div", _hoisted_28, [
                          ($data.highlights.length)
                            ? ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)("div", {
                                key: 0,
                                class: "ps-highlight-hero",
                                tabindex: "0",
                                role: "button",
                                "aria-label": $options.t('Open photo {name}', { name: $data.highlights[0].location || $data.highlights[0].name }),
                                onClick: _cache[4] || (_cache[4] = $event => ($options.openLightbox(0))),
                                onKeydown: [
                                  _cache[5] || (_cache[5] = (0,vue__WEBPACK_IMPORTED_MODULE_0__.withKeys)($event => ($options.openLightbox(0)), ["enter"])),
                                  _cache[6] || (_cache[6] = (0,vue__WEBPACK_IMPORTED_MODULE_0__.withKeys)((0,vue__WEBPACK_IMPORTED_MODULE_0__.withModifiers)($event => ($options.openLightbox(0)), ["prevent"]), ["space"]))
                                ]
                              }, [
                                (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("img", {
                                  src: $options.previewUrl($data.highlights[0], 1600),
                                  alt: $data.highlights[0].location_display || $data.highlights[0].location || $data.highlights[0].name,
                                  loading: "lazy",
                                  decoding: "async",
                                  class: "ps-hero-img"
                                }, null, 8 /* PROPS */, _hoisted_30),
                                ($options.config.showCaptions !== false)
                                  ? ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)("div", _hoisted_31, [
                                      (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("strong", null, (0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)($data.highlights[0].location || $data.highlights[0].name), 1 /* TEXT */),
                                      ($data.highlights[0].taken_at)
                                        ? ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)("span", _hoisted_32, (0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)($options.formatDate($data.highlights[0].taken_at)), 1 /* TEXT */))
                                        : (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)("v-if", true)
                                    ]))
                                  : (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)("v-if", true)
                              ], 40 /* PROPS, NEED_HYDRATION */, _hoisted_29))
                            : (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)("v-if", true),
                          (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("div", _hoisted_33, [
                            ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(true), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)(vue__WEBPACK_IMPORTED_MODULE_0__.Fragment, null, (0,vue__WEBPACK_IMPORTED_MODULE_0__.renderList)($data.highlights.slice(1), (photo, idx) => {
                              return ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createBlock)(_component_PhotoTile, {
                                key: photo.file_id,
                                photo: photo,
                                "show-caption": $options.config.showCaptions !== false,
                                onClick: $event => ($options.openLightbox(idx + 1))
                              }, null, 8 /* PROPS */, ["photo", "show-caption", "onClick"]))
                            }), 128 /* KEYED_FRAGMENT */))
                          ])
                        ])
                      ], 2112 /* STABLE_FRAGMENT, DEV_ROOT_FRAGMENT */))
                    : ($options.effectiveMode === 'grid')
                      ? ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)(vue__WEBPACK_IMPORTED_MODULE_0__.Fragment, { key: 9 }, [
                          (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)(" Grid mode "),
                          (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("div", {
                            class: "ps-grid ps-grid--masonry",
                            style: (0,vue__WEBPACK_IMPORTED_MODULE_0__.normalizeStyle)($options.gridStyle)
                          }, [
                            ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(true), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)(vue__WEBPACK_IMPORTED_MODULE_0__.Fragment, null, (0,vue__WEBPACK_IMPORTED_MODULE_0__.renderList)($data.photos, (photo, idx) => {
                              return ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createBlock)(_component_PhotoTile, {
                                key: photo.file_id,
                                photo: photo,
                                "show-caption": $options.config.showCaptions !== false,
                                style: (0,vue__WEBPACK_IMPORTED_MODULE_0__.normalizeStyle)($options.masonryItemStyle(photo)),
                                onClick: $event => ($options.openLightbox(idx))
                              }, null, 8 /* PROPS */, ["photo", "show-caption", "style", "onClick"]))
                            }), 128 /* KEYED_FRAGMENT */))
                          ], 4 /* STYLE */)
                        ], 2112 /* STABLE_FRAGMENT, DEV_ROOT_FRAGMENT */))
                      : ($options.effectiveMode === 'on-this-day')
                        ? ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)(vue__WEBPACK_IMPORTED_MODULE_0__.Fragment, { key: 10 }, [
                            (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)(" On this day mode "),
                            (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("div", _hoisted_34, [
                              ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(true), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)(vue__WEBPACK_IMPORTED_MODULE_0__.Fragment, null, (0,vue__WEBPACK_IMPORTED_MODULE_0__.renderList)($options.onThisDayGroups, (group, year) => {
                                return ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)("section", {
                                  key: year,
                                  class: "ps-year"
                                }, [
                                  (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("header", _hoisted_35, [
                                    (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("h4", _hoisted_36, (0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)(year), 1 /* TEXT */)
                                  ]),
                                  (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("div", _hoisted_37, [
                                    ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(true), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)(vue__WEBPACK_IMPORTED_MODULE_0__.Fragment, null, (0,vue__WEBPACK_IMPORTED_MODULE_0__.renderList)(group, (photo) => {
                                      return ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createBlock)(_component_PhotoTile, {
                                        key: photo.file_id,
                                        photo: photo,
                                        "show-caption": $options.config.showCaptions !== false,
                                        onClick: $event => ($options.openLightbox($data.photos.indexOf(photo)))
                                      }, null, 8 /* PROPS */, ["photo", "show-caption", "onClick"]))
                                    }), 128 /* KEYED_FRAGMENT */))
                                  ])
                                ]))
                              }), 128 /* KEYED_FRAGMENT */))
                            ])
                          ], 2112 /* STABLE_FRAGMENT, DEV_ROOT_FRAGMENT */))
                        : (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)("v-if", true),
    (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)(" Year-jump scrubber (Timeline-only, only if >1 year) "),
    ($options.effectiveMode === 'timeline' && $options.yearScrubber.length > 1)
      ? ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)("aside", {
          key: 11,
          class: "ps-year-scrubber",
          "aria-label": $options.t('Jump to year')
        }, [
          ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(true), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)(vue__WEBPACK_IMPORTED_MODULE_0__.Fragment, null, (0,vue__WEBPACK_IMPORTED_MODULE_0__.renderList)($options.yearScrubber, (y) => {
            return ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)("button", {
              key: y,
              type: "button",
              class: "ps-year-btn",
              title: $options.t('Jump to {year}', { year: String(y) }),
              onClick: $event => ($options.scrollToYear(y))
            }, (0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)(y), 9 /* TEXT, PROPS */, _hoisted_39))
          }), 128 /* KEYED_FRAGMENT */))
        ], 8 /* PROPS */, _hoisted_38))
      : (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)("v-if", true),
    (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)(" Infinite-scroll sentinel + load-more indicator (folder-mode timeline/grid only) "),
    ($options.isPagedMode() && $data.pagination.hasMore)
      ? ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)("div", _hoisted_40, [
          ($data.loadingMore)
            ? ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)("span", _hoisted_41, (0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)($options.t('Loading more photos…')), 1 /* TEXT */))
            : (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)("v-if", true)
        ], 512 /* NEED_PATCH */))
      : (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)("v-if", true),
    (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)(" Truncation notice when server hit its hard cap "),
    ($data.pagination.truncated)
      ? ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)("div", _hoisted_42, (0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)($options.t('Showing first {n} photos. Use filters or a more specific folder to narrow the selection.', { n: String($data.pagination.total) })), 1 /* TEXT */))
      : (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)("v-if", true),
    (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)(" Lightbox "),
    ($data.lightboxVisible)
      ? ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createBlock)(_component_PhotoLightbox, {
          key: 14,
          photos: $data.photos,
          "start-index": $data.lightboxIndex,
          visible: $data.lightboxVisible,
          onClose: _cache[7] || (_cache[7] = $event => ($data.lightboxVisible = false))
        }, null, 8 /* PROPS */, ["photos", "start-index", "visible"]))
      : (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)("v-if", true)
  ], 6 /* CLASS, STYLE */))
}

/***/ }

}]);
//# sourceMappingURL=intravox-src_components_PhotoStoryWidget_vue.b1946e8f.js.map