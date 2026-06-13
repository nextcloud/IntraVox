"use strict";
(self["webpackChunknextcloud_intravox"] = self["webpackChunknextcloud_intravox"] || []).push([["src_components_PhotoStoryDayMap_vue"],{

/***/ "./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/dist/stylePostLoader.js!./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/PhotoStoryDayMap.vue?vue&type=style&index=0&id=605ebada&scoped=true&lang=css"
/*!*****************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/dist/stylePostLoader.js!./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/PhotoStoryDayMap.vue?vue&type=style&index=0&id=605ebada&scoped=true&lang=css ***!
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
.ps-day-map[data-v-605ebada] {
  width: 100%;
  border-radius: var(--border-radius-large);
  overflow: hidden;
  background: var(--color-background-dark);
  margin-bottom: 12px;
  /* isolation creates an own stacking context so Leaflet's internal
     panes (default z-index 200-700) can't render over the sticky
     topbar (.intravox-topbar, z-index: 100) when scrolling. */
  position: relative;
  z-index: 0;
  isolation: isolate;
}
.ps-day-map-canvas[data-v-605ebada] {
  width: 100%;
  height: 100%;
}
.ps-day-map-state[data-v-605ebada],
.ps-day-map-fallback[data-v-605ebada] {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  width: 100%;
  height: 100%;
  font-size: 12px;
  color: var(--color-text-maxcontrast);
}
`, "",{"version":3,"sources":["webpack://./src/components/PhotoStoryDayMap.vue"],"names":[],"mappings":";AAuOA;EACE,WAAW;EACX,yCAAyC;EACzC,gBAAgB;EAChB,wCAAwC;EACxC,mBAAmB;EACnB;;8DAE4D;EAC5D,kBAAkB;EAClB,UAAU;EACV,kBAAkB;AACpB;AAEA;EACE,WAAW;EACX,YAAY;AACd;AAEA;;EAEE,aAAa;EACb,mBAAmB;EACnB,uBAAuB;EACvB,QAAQ;EACR,WAAW;EACX,YAAY;EACZ,eAAe;EACf,oCAAoC;AACtC","sourcesContent":["<template>\n  <div\n    v-if=\"hasPoints\"\n    class=\"ps-day-map\"\n    :style=\"{ height: height + 'px' }\"\n    @click.stop\n  >\n    <div v-if=\"!leaflet && !fallback\" class=\"ps-day-map-state\">\n      <NcLoadingIcon :size=\"16\" />\n    </div>\n    <div v-else-if=\"fallback\" class=\"ps-day-map-fallback\">\n      <MapMarker :size=\"16\" />\n      <span>{{ t('Map unavailable') }}</span>\n    </div>\n    <div v-else ref=\"mapEl\" class=\"ps-day-map-canvas\"></div>\n  </div>\n</template>\n\n<script>\nimport { translate as t } from '@nextcloud/l10n';\nimport { NcLoadingIcon } from '@nextcloud/vue';\nimport MapMarker from 'vue-material-design-icons/MapMarker.vue';\n\n// Module-level cache so we only import Leaflet once across all day-maps on a page\nlet leafletPromise = null;\nfunction loadLeafletModule() {\n  if (!leafletPromise) {\n    leafletPromise = Promise.all([\n      import('leaflet'),\n      import('leaflet/dist/leaflet.css'),\n    ]).then(([leaflet]) => leaflet.default || leaflet).catch(() => null);\n  }\n  return leafletPromise;\n}\n\nexport default {\n  name: 'PhotoStoryDayMap',\n  components: { NcLoadingIcon, MapMarker },\n  props: {\n    points: { type: Array, required: true },\n    height: { type: Number, default: 160 },\n    tileUrl: { type: String, default: 'https://tile.openstreetmap.org/{z}/{x}/{y}.png' },\n    attribution: { type: String, default: '© OSM' },\n    maxZoom: { type: Number, default: 19 },\n  },\n  emits: ['photo-click'],\n  data() {\n    return {\n      leaflet: null,\n      fallback: false,\n      mapInstance: null,\n      // Lazy-init: only instantiate Leaflet once this map scrolls near viewport.\n      // Prevents 30 simultaneous L.map() calls on initial paint of a long timeline.\n      hasIntersected: false,\n      observer: null,\n    };\n  },\n  computed: {\n    validPoints() {\n      return (this.points || []).filter(\n        p => p && typeof p.lat === 'number' && typeof p.lon === 'number'\n          && Number.isFinite(p.lat) && Number.isFinite(p.lon)\n      );\n    },\n    hasPoints() {\n      return this.validPoints.length > 0;\n    },\n  },\n  watch: {\n    points: {\n      deep: true,\n      handler(newP, oldP) {\n        if (!this.leaflet || !this.hasIntersected) return;\n        // Skip when nothing meaningful changed. Compare on count + first/last\n        // file_id; full deep comparison would be O(N) per render-tick.\n        const fp = (arr) => Array.isArray(arr)\n          ? `${arr.length}:${arr[0]?.file_id || ''}:${arr[arr.length - 1]?.file_id || ''}`\n          : '';\n        if (fp(newP) === fp(oldP)) return;\n        // Debounce: infinite-scroll page-merges can mutate points rapidly. Each\n        // initMap() tears down + re-creates the Leaflet instance (expensive).\n        clearTimeout(this._pointsDebounce);\n        this._pointsDebounce = setTimeout(() => {\n          this.initMap();\n        }, 250);\n      },\n    },\n  },\n  mounted() {\n    if (!this.hasPoints) return;\n    // Defer Leaflet init until the container is near the viewport.\n    // Without IntersectionObserver (older browsers) we fall back to immediate init.\n    if (typeof IntersectionObserver === 'undefined') {\n      this.activateMap();\n      return;\n    }\n    this.observer = new IntersectionObserver((entries) => {\n      for (const e of entries) {\n        if (e.isIntersecting) {\n          this.activateMap();\n          if (this.observer) {\n            this.observer.disconnect();\n            this.observer = null;\n          }\n          break;\n        }\n      }\n    }, { rootMargin: '500px' });\n    // Observe the component's root element (the .ps-day-map div).\n    this.observer.observe(this.$el);\n    // Safety net: if IO hasn't triggered within 1.5s (e.g. because the parent\n    // hadn't laid out yet at mount time, or the element is somehow non-observable),\n    // activate the map unconditionally. Better to render a few extra Leaflet\n    // instances than to silently fail.\n    this._activateFallbackTimer = setTimeout(() => {\n      if (!this.hasIntersected) {\n        this.activateMap();\n        if (this.observer) {\n          this.observer.disconnect();\n          this.observer = null;\n        }\n      }\n    }, 1500);\n  },\n  beforeUnmount() {\n    if (this._activateFallbackTimer) {\n      clearTimeout(this._activateFallbackTimer);\n      this._activateFallbackTimer = null;\n    }\n    if (this._pointsDebounce) {\n      clearTimeout(this._pointsDebounce);\n      this._pointsDebounce = null;\n    }\n    if (this.observer) {\n      this.observer.disconnect();\n      this.observer = null;\n    }\n    if (this.mapInstance) {\n      this.mapInstance.remove();\n      this.mapInstance = null;\n    }\n  },\n  methods: {\n    t(key, vars = {}) {\n      return t('intravox', key, vars);\n    },\n    async activateMap() {\n      if (this.hasIntersected) return;\n      this.hasIntersected = true;\n      const L = await loadLeafletModule();\n      if (!L) {\n        this.fallback = true;\n        return;\n      }\n      this.leaflet = L;\n      await this.$nextTick();\n      this.initMap();\n    },\n    initMap() {\n      if (!this.leaflet || !this.$refs.mapEl) return;\n      const L = this.leaflet;\n\n      if (this.mapInstance) {\n        this.mapInstance.remove();\n        this.mapInstance = null;\n      }\n\n      const pts = this.validPoints;\n      if (pts.length === 0) return;\n\n      // Initial center so the tile layer has a valid view to render against.\n      // Without this Leaflet skips tile loading until setView/fitBounds runs,\n      // which can leave the map blank in some browser/timing combinations.\n      const initialCenter = pts.length === 1 ? [pts[0].lat, pts[0].lon] : [pts[0].lat, pts[0].lon];\n      this.mapInstance = L.map(this.$refs.mapEl, {\n        scrollWheelZoom: false,\n        zoomControl: false,\n        attributionControl: false,\n        center: initialCenter,\n        zoom: 13,\n      });\n\n      L.tileLayer(this.tileUrl, {\n        attribution: this.attribution,\n        maxZoom: this.maxZoom || 19,\n        crossOrigin: true,\n        referrerPolicy: 'no-referrer-when-downgrade',\n      }).addTo(this.mapInstance);\n\n      const latlngs = [];\n      for (const p of pts) {\n        const ll = [p.lat, p.lon];\n        latlngs.push(ll);\n        const marker = L.circleMarker(ll, {\n          radius: 6,\n          color: '#fff',\n          weight: 2,\n          fillColor: '#1976d2',\n          fillOpacity: 0.9,\n        }).addTo(this.mapInstance);\n        if (p.file_id) {\n          marker.on('click', (e) => {\n            // Stop propagation so the parent section doesn't also pick it up\n            if (e.originalEvent) e.originalEvent.stopPropagation();\n            this.$emit('photo-click', p.file_id);\n          });\n        }\n        if (p.name) {\n          marker.bindTooltip(p.name, { direction: 'top' });\n        }\n      }\n\n      if (latlngs.length === 1) {\n        this.mapInstance.setView(latlngs[0], 14);\n      } else {\n        this.mapInstance.fitBounds(latlngs, { padding: [20, 20], maxZoom: 15 });\n      }\n\n      // Force Leaflet to recompute its container size after the next paint.\n      // Required when the component renders inside a layout that isn't fully\n      // sized at mount time (sticky sections, aspect-ratio grids, etc.).\n      const map = this.mapInstance;\n      requestAnimationFrame(() => {\n        try { map.invalidateSize(false); } catch (e) { /* map already removed */ }\n      });\n    },\n  },\n};\n</script>\n\n<style scoped>\n.ps-day-map {\n  width: 100%;\n  border-radius: var(--border-radius-large);\n  overflow: hidden;\n  background: var(--color-background-dark);\n  margin-bottom: 12px;\n  /* isolation creates an own stacking context so Leaflet's internal\n     panes (default z-index 200-700) can't render over the sticky\n     topbar (.intravox-topbar, z-index: 100) when scrolling. */\n  position: relative;\n  z-index: 0;\n  isolation: isolate;\n}\n\n.ps-day-map-canvas {\n  width: 100%;\n  height: 100%;\n}\n\n.ps-day-map-state,\n.ps-day-map-fallback {\n  display: flex;\n  align-items: center;\n  justify-content: center;\n  gap: 8px;\n  width: 100%;\n  height: 100%;\n  font-size: 12px;\n  color: var(--color-text-maxcontrast);\n}\n</style>\n"],"sourceRoot":""}]);
// Exports
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (___CSS_LOADER_EXPORT___);


/***/ },

/***/ "./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/dist/stylePostLoader.js!./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/PhotoStoryDayMap.vue?vue&type=style&index=0&id=605ebada&scoped=true&lang=css"
/*!*********************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/dist/stylePostLoader.js!./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/PhotoStoryDayMap.vue?vue&type=style&index=0&id=605ebada&scoped=true&lang=css ***!
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
/* harmony import */ var _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_dist_stylePostLoader_js_node_modules_vue_loader_dist_index_js_ruleSet_1_rules_5_use_0_PhotoStoryDayMap_vue_vue_type_style_index_0_id_605ebada_scoped_true_lang_css__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! !!../../node_modules/css-loader/dist/cjs.js!../../node_modules/vue-loader/dist/stylePostLoader.js!../../node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./PhotoStoryDayMap.vue?vue&type=style&index=0&id=605ebada&scoped=true&lang=css */ "./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/dist/stylePostLoader.js!./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/PhotoStoryDayMap.vue?vue&type=style&index=0&id=605ebada&scoped=true&lang=css");

      
      
      
      
      
      
      
      
      

var options = {};

options.styleTagTransform = (_node_modules_style_loader_dist_runtime_styleTagTransform_js__WEBPACK_IMPORTED_MODULE_5___default());
options.setAttributes = (_node_modules_style_loader_dist_runtime_setAttributesWithoutAttributes_js__WEBPACK_IMPORTED_MODULE_3___default());

      options.insert = _node_modules_style_loader_dist_runtime_insertBySelector_js__WEBPACK_IMPORTED_MODULE_2___default().bind(null, "head");
    
options.domAPI = (_node_modules_style_loader_dist_runtime_styleDomAPI_js__WEBPACK_IMPORTED_MODULE_1___default());
options.insertStyleElement = (_node_modules_style_loader_dist_runtime_insertStyleElement_js__WEBPACK_IMPORTED_MODULE_4___default());

var update = _node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0___default()(_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_dist_stylePostLoader_js_node_modules_vue_loader_dist_index_js_ruleSet_1_rules_5_use_0_PhotoStoryDayMap_vue_vue_type_style_index_0_id_605ebada_scoped_true_lang_css__WEBPACK_IMPORTED_MODULE_6__["default"], options);




       /* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_dist_stylePostLoader_js_node_modules_vue_loader_dist_index_js_ruleSet_1_rules_5_use_0_PhotoStoryDayMap_vue_vue_type_style_index_0_id_605ebada_scoped_true_lang_css__WEBPACK_IMPORTED_MODULE_6__["default"] && _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_dist_stylePostLoader_js_node_modules_vue_loader_dist_index_js_ruleSet_1_rules_5_use_0_PhotoStoryDayMap_vue_vue_type_style_index_0_id_605ebada_scoped_true_lang_css__WEBPACK_IMPORTED_MODULE_6__["default"].locals ? _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_dist_stylePostLoader_js_node_modules_vue_loader_dist_index_js_ruleSet_1_rules_5_use_0_PhotoStoryDayMap_vue_vue_type_style_index_0_id_605ebada_scoped_true_lang_css__WEBPACK_IMPORTED_MODULE_6__["default"].locals : undefined);


/***/ },

/***/ "./src/components/PhotoStoryDayMap.vue"
/*!*********************************************!*\
  !*** ./src/components/PhotoStoryDayMap.vue ***!
  \*********************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _PhotoStoryDayMap_vue_vue_type_template_id_605ebada_scoped_true__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./PhotoStoryDayMap.vue?vue&type=template&id=605ebada&scoped=true */ "./src/components/PhotoStoryDayMap.vue?vue&type=template&id=605ebada&scoped=true");
/* harmony import */ var _PhotoStoryDayMap_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./PhotoStoryDayMap.vue?vue&type=script&lang=js */ "./src/components/PhotoStoryDayMap.vue?vue&type=script&lang=js");
/* harmony import */ var _PhotoStoryDayMap_vue_vue_type_style_index_0_id_605ebada_scoped_true_lang_css__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./PhotoStoryDayMap.vue?vue&type=style&index=0&id=605ebada&scoped=true&lang=css */ "./src/components/PhotoStoryDayMap.vue?vue&type=style&index=0&id=605ebada&scoped=true&lang=css");
/* harmony import */ var _node_modules_vue_loader_dist_exportHelper_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ../../node_modules/vue-loader/dist/exportHelper.js */ "./node_modules/vue-loader/dist/exportHelper.js");




;


const __exports__ = /*#__PURE__*/(0,_node_modules_vue_loader_dist_exportHelper_js__WEBPACK_IMPORTED_MODULE_3__["default"])(_PhotoStoryDayMap_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_1__["default"], [['render',_PhotoStoryDayMap_vue_vue_type_template_id_605ebada_scoped_true__WEBPACK_IMPORTED_MODULE_0__.render],['__scopeId',"data-v-605ebada"],['__file',"src/components/PhotoStoryDayMap.vue"]])
/* hot reload */
if (false) // removed by dead control flow
{}


/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (__exports__);

/***/ },

/***/ "./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/PhotoStoryDayMap.vue?vue&type=script&lang=js"
/*!*****************************************************************************************************************************************!*\
  !*** ./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/PhotoStoryDayMap.vue?vue&type=script&lang=js ***!
  \*****************************************************************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _nextcloud_l10n__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @nextcloud/l10n */ "./node_modules/@nextcloud/l10n/dist/index.mjs");
/* harmony import */ var _nextcloud_vue__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @nextcloud/vue */ "./node_modules/@nextcloud/vue/dist/index.mjs");
/* harmony import */ var vue_material_design_icons_MapMarker_vue__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! vue-material-design-icons/MapMarker.vue */ "./node_modules/vue-material-design-icons/MapMarker.vue");





// Module-level cache so we only import Leaflet once across all day-maps on a page
let leafletPromise = null;
function loadLeafletModule() {
  if (!leafletPromise) {
    leafletPromise = Promise.all([
      __webpack_require__.e(/*! import() */ "vendors").then(__webpack_require__.t.bind(__webpack_require__, /*! leaflet */ "./node_modules/leaflet/dist/leaflet-src.js", 23)),
      __webpack_require__.e(/*! import() */ "vendors").then(__webpack_require__.bind(__webpack_require__, /*! leaflet/dist/leaflet.css */ "./node_modules/leaflet/dist/leaflet.css")),
    ]).then(([leaflet]) => leaflet.default || leaflet).catch(() => null);
  }
  return leafletPromise;
}

/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = ({
  name: 'PhotoStoryDayMap',
  components: { NcLoadingIcon: _nextcloud_vue__WEBPACK_IMPORTED_MODULE_1__.NcLoadingIcon, MapMarker: vue_material_design_icons_MapMarker_vue__WEBPACK_IMPORTED_MODULE_2__["default"] },
  props: {
    points: { type: Array, required: true },
    height: { type: Number, default: 160 },
    tileUrl: { type: String, default: 'https://tile.openstreetmap.org/{z}/{x}/{y}.png' },
    attribution: { type: String, default: '© OSM' },
    maxZoom: { type: Number, default: 19 },
  },
  emits: ['photo-click'],
  data() {
    return {
      leaflet: null,
      fallback: false,
      mapInstance: null,
      // Lazy-init: only instantiate Leaflet once this map scrolls near viewport.
      // Prevents 30 simultaneous L.map() calls on initial paint of a long timeline.
      hasIntersected: false,
      observer: null,
    };
  },
  computed: {
    validPoints() {
      return (this.points || []).filter(
        p => p && typeof p.lat === 'number' && typeof p.lon === 'number'
          && Number.isFinite(p.lat) && Number.isFinite(p.lon)
      );
    },
    hasPoints() {
      return this.validPoints.length > 0;
    },
  },
  watch: {
    points: {
      deep: true,
      handler(newP, oldP) {
        if (!this.leaflet || !this.hasIntersected) return;
        // Skip when nothing meaningful changed. Compare on count + first/last
        // file_id; full deep comparison would be O(N) per render-tick.
        const fp = (arr) => Array.isArray(arr)
          ? `${arr.length}:${arr[0]?.file_id || ''}:${arr[arr.length - 1]?.file_id || ''}`
          : '';
        if (fp(newP) === fp(oldP)) return;
        // Debounce: infinite-scroll page-merges can mutate points rapidly. Each
        // initMap() tears down + re-creates the Leaflet instance (expensive).
        clearTimeout(this._pointsDebounce);
        this._pointsDebounce = setTimeout(() => {
          this.initMap();
        }, 250);
      },
    },
  },
  mounted() {
    if (!this.hasPoints) return;
    // Defer Leaflet init until the container is near the viewport.
    // Without IntersectionObserver (older browsers) we fall back to immediate init.
    if (typeof IntersectionObserver === 'undefined') {
      this.activateMap();
      return;
    }
    this.observer = new IntersectionObserver((entries) => {
      for (const e of entries) {
        if (e.isIntersecting) {
          this.activateMap();
          if (this.observer) {
            this.observer.disconnect();
            this.observer = null;
          }
          break;
        }
      }
    }, { rootMargin: '500px' });
    // Observe the component's root element (the .ps-day-map div).
    this.observer.observe(this.$el);
    // Safety net: if IO hasn't triggered within 1.5s (e.g. because the parent
    // hadn't laid out yet at mount time, or the element is somehow non-observable),
    // activate the map unconditionally. Better to render a few extra Leaflet
    // instances than to silently fail.
    this._activateFallbackTimer = setTimeout(() => {
      if (!this.hasIntersected) {
        this.activateMap();
        if (this.observer) {
          this.observer.disconnect();
          this.observer = null;
        }
      }
    }, 1500);
  },
  beforeUnmount() {
    if (this._activateFallbackTimer) {
      clearTimeout(this._activateFallbackTimer);
      this._activateFallbackTimer = null;
    }
    if (this._pointsDebounce) {
      clearTimeout(this._pointsDebounce);
      this._pointsDebounce = null;
    }
    if (this.observer) {
      this.observer.disconnect();
      this.observer = null;
    }
    if (this.mapInstance) {
      this.mapInstance.remove();
      this.mapInstance = null;
    }
  },
  methods: {
    t(key, vars = {}) {
      return (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_0__.translate)('intravox', key, vars);
    },
    async activateMap() {
      if (this.hasIntersected) return;
      this.hasIntersected = true;
      const L = await loadLeafletModule();
      if (!L) {
        this.fallback = true;
        return;
      }
      this.leaflet = L;
      await this.$nextTick();
      this.initMap();
    },
    initMap() {
      if (!this.leaflet || !this.$refs.mapEl) return;
      const L = this.leaflet;

      if (this.mapInstance) {
        this.mapInstance.remove();
        this.mapInstance = null;
      }

      const pts = this.validPoints;
      if (pts.length === 0) return;

      // Initial center so the tile layer has a valid view to render against.
      // Without this Leaflet skips tile loading until setView/fitBounds runs,
      // which can leave the map blank in some browser/timing combinations.
      const initialCenter = pts.length === 1 ? [pts[0].lat, pts[0].lon] : [pts[0].lat, pts[0].lon];
      this.mapInstance = L.map(this.$refs.mapEl, {
        scrollWheelZoom: false,
        zoomControl: false,
        attributionControl: false,
        center: initialCenter,
        zoom: 13,
      });

      L.tileLayer(this.tileUrl, {
        attribution: this.attribution,
        maxZoom: this.maxZoom || 19,
        crossOrigin: true,
        referrerPolicy: 'no-referrer-when-downgrade',
      }).addTo(this.mapInstance);

      const latlngs = [];
      for (const p of pts) {
        const ll = [p.lat, p.lon];
        latlngs.push(ll);
        const marker = L.circleMarker(ll, {
          radius: 6,
          color: '#fff',
          weight: 2,
          fillColor: '#1976d2',
          fillOpacity: 0.9,
        }).addTo(this.mapInstance);
        if (p.file_id) {
          marker.on('click', (e) => {
            // Stop propagation so the parent section doesn't also pick it up
            if (e.originalEvent) e.originalEvent.stopPropagation();
            this.$emit('photo-click', p.file_id);
          });
        }
        if (p.name) {
          marker.bindTooltip(p.name, { direction: 'top' });
        }
      }

      if (latlngs.length === 1) {
        this.mapInstance.setView(latlngs[0], 14);
      } else {
        this.mapInstance.fitBounds(latlngs, { padding: [20, 20], maxZoom: 15 });
      }

      // Force Leaflet to recompute its container size after the next paint.
      // Required when the component renders inside a layout that isn't fully
      // sized at mount time (sticky sections, aspect-ratio grids, etc.).
      const map = this.mapInstance;
      requestAnimationFrame(() => {
        try { map.invalidateSize(false); } catch (e) { /* map already removed */ }
      });
    },
  },
});


/***/ },

/***/ "./src/components/PhotoStoryDayMap.vue?vue&type=style&index=0&id=605ebada&scoped=true&lang=css"
/*!*****************************************************************************************************!*\
  !*** ./src/components/PhotoStoryDayMap.vue?vue&type=style&index=0&id=605ebada&scoped=true&lang=css ***!
  \*****************************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _node_modules_style_loader_dist_cjs_js_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_dist_stylePostLoader_js_node_modules_vue_loader_dist_index_js_ruleSet_1_rules_5_use_0_PhotoStoryDayMap_vue_vue_type_style_index_0_id_605ebada_scoped_true_lang_css__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../node_modules/style-loader/dist/cjs.js!../../node_modules/css-loader/dist/cjs.js!../../node_modules/vue-loader/dist/stylePostLoader.js!../../node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./PhotoStoryDayMap.vue?vue&type=style&index=0&id=605ebada&scoped=true&lang=css */ "./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/dist/stylePostLoader.js!./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/PhotoStoryDayMap.vue?vue&type=style&index=0&id=605ebada&scoped=true&lang=css");


/***/ },

/***/ "./src/components/PhotoStoryDayMap.vue?vue&type=script&lang=js"
/*!*********************************************************************!*\
  !*** ./src/components/PhotoStoryDayMap.vue?vue&type=script&lang=js ***!
  \*********************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* reexport safe */ _node_modules_vue_loader_dist_index_js_ruleSet_1_rules_5_use_0_PhotoStoryDayMap_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_0__["default"])
/* harmony export */ });
/* harmony import */ var _node_modules_vue_loader_dist_index_js_ruleSet_1_rules_5_use_0_PhotoStoryDayMap_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./PhotoStoryDayMap.vue?vue&type=script&lang=js */ "./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/PhotoStoryDayMap.vue?vue&type=script&lang=js");
 

/***/ },

/***/ "./src/components/PhotoStoryDayMap.vue?vue&type=template&id=605ebada&scoped=true"
/*!***************************************************************************************!*\
  !*** ./src/components/PhotoStoryDayMap.vue?vue&type=template&id=605ebada&scoped=true ***!
  \***************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   render: () => (/* reexport safe */ _node_modules_vue_loader_dist_templateLoader_js_ruleSet_1_rules_2_node_modules_vue_loader_dist_index_js_ruleSet_1_rules_5_use_0_PhotoStoryDayMap_vue_vue_type_template_id_605ebada_scoped_true__WEBPACK_IMPORTED_MODULE_0__.render)
/* harmony export */ });
/* harmony import */ var _node_modules_vue_loader_dist_templateLoader_js_ruleSet_1_rules_2_node_modules_vue_loader_dist_index_js_ruleSet_1_rules_5_use_0_PhotoStoryDayMap_vue_vue_type_template_id_605ebada_scoped_true__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../node_modules/vue-loader/dist/templateLoader.js??ruleSet[1].rules[2]!../../node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./PhotoStoryDayMap.vue?vue&type=template&id=605ebada&scoped=true */ "./node_modules/vue-loader/dist/templateLoader.js??ruleSet[1].rules[2]!./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/PhotoStoryDayMap.vue?vue&type=template&id=605ebada&scoped=true");


/***/ },

/***/ "./node_modules/vue-loader/dist/templateLoader.js??ruleSet[1].rules[2]!./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/PhotoStoryDayMap.vue?vue&type=template&id=605ebada&scoped=true"
/*!*********************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/vue-loader/dist/templateLoader.js??ruleSet[1].rules[2]!./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/PhotoStoryDayMap.vue?vue&type=template&id=605ebada&scoped=true ***!
  \*********************************************************************************************************************************************************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   render: () => (/* binding */ render)
/* harmony export */ });
/* harmony import */ var vue__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! vue */ "./node_modules/vue/dist/vue.runtime.esm-bundler.js");


const _hoisted_1 = {
  key: 0,
  class: "ps-day-map-state"
}
const _hoisted_2 = {
  key: 1,
  class: "ps-day-map-fallback"
}
const _hoisted_3 = {
  key: 2,
  ref: "mapEl",
  class: "ps-day-map-canvas"
}

function render(_ctx, _cache, $props, $setup, $data, $options) {
  const _component_NcLoadingIcon = (0,vue__WEBPACK_IMPORTED_MODULE_0__.resolveComponent)("NcLoadingIcon")
  const _component_MapMarker = (0,vue__WEBPACK_IMPORTED_MODULE_0__.resolveComponent)("MapMarker")

  return ($options.hasPoints)
    ? ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)("div", {
        key: 0,
        class: "ps-day-map",
        style: (0,vue__WEBPACK_IMPORTED_MODULE_0__.normalizeStyle)({ height: $props.height + 'px' }),
        onClick: _cache[0] || (_cache[0] = (0,vue__WEBPACK_IMPORTED_MODULE_0__.withModifiers)(() => {}, ["stop"]))
      }, [
        (!$data.leaflet && !$data.fallback)
          ? ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)("div", _hoisted_1, [
              (0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_NcLoadingIcon, { size: 16 })
            ]))
          : ($data.fallback)
            ? ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)("div", _hoisted_2, [
                (0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_MapMarker, { size: 16 }),
                (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("span", null, (0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)($options.t('Map unavailable')), 1 /* TEXT */)
              ]))
            : ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)("div", _hoisted_3, null, 512 /* NEED_PATCH */))
      ], 4 /* STYLE */))
    : (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)("v-if", true)
}

/***/ }

}]);
//# sourceMappingURL=intravox-src_components_PhotoStoryDayMap_vue.a39a43d6.js.map