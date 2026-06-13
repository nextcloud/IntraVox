"use strict";
(self["webpackChunknextcloud_intravox"] = self["webpackChunknextcloud_intravox"] || []).push([["src_components_CalendarWidget_vue"],{

/***/ "./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/dist/stylePostLoader.js!./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/CalendarWidget.vue?vue&type=style&index=0&id=72048852&scoped=true&lang=css"
/*!***************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/dist/stylePostLoader.js!./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/CalendarWidget.vue?vue&type=style&index=0&id=72048852&scoped=true&lang=css ***!
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
.calendar-widget[data-v-72048852] {
  width: 100%;
  margin: 12px 0;
  container-type: inline-size;
}
.calendar-widget-title[data-v-72048852] {
  margin: 0 0 16px 0;
  font-size: 18px;
  font-weight: 600;
  color: var(--color-main-text);
}
.calendar-loading[data-v-72048852],
.calendar-error[data-v-72048852],
.calendar-empty[data-v-72048852] {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 12px;
  padding: 40px 20px;
  color: var(--color-text-maxcontrast);
  text-align: center;
}
.calendar-error[data-v-72048852] {
  color: var(--color-error);
}
.calendar-empty[data-v-72048852] {
  background: var(--color-background-hover);
  border: 2px dashed var(--color-border);
  border-radius: var(--border-radius-large);
}
.calendar-empty p[data-v-72048852] {
  margin: 0;
  font-size: 14px;
}
.calendar-empty small[data-v-72048852] {
  margin-top: 4px;
  font-size: 12px;
  opacity: 0.7;
}
.calendar-event-list[data-v-72048852] {
  display: grid;
  grid-template-columns: 1fr;
  gap: 8px;
}
.calendar-event[data-v-72048852] {
  display: flex;
  align-items: flex-start;
  gap: 12px;
  padding: 8px;
  border-radius: var(--border-radius-large);
  transition: background 0.15s;
  text-decoration: none;
  color: inherit;
  cursor: pointer;
}
.calendar-event[data-v-72048852]:hover {
  background: var(--color-background-hover);
}
.calendar-event[data-v-72048852]:focus-visible {
  outline: 2px solid var(--color-primary-element);
  outline-offset: 2px;
}

/* Date badge - colored square with day number + month */
.event-date-badge[data-v-72048852] {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  width: 48px;
  height: 48px;
  min-width: 48px;
  border-radius: var(--border-radius-large);
  color: var(--color-primary-element-text);
  flex-shrink: 0;
}
.event-date-day[data-v-72048852] {
  font-size: 18px;
  font-weight: 600;
  line-height: 1.1;
}
.event-date-month[data-v-72048852] {
  font-size: 11px;
  font-weight: 500;
  text-transform: uppercase;
  opacity: 0.85;
  line-height: 1.2;
}

/* Event details - right side */
.event-details[data-v-72048852] {
  display: flex;
  flex-direction: column;
  gap: 1px;
  min-width: 0;
  padding-top: 2px;
}
.event-proximity[data-v-72048852] {
  font-size: 11px;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.3px;
}
.event-title[data-v-72048852] {
  font-size: 14px;
  font-weight: 600;
  color: var(--color-main-text);
  line-height: 1.3;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}
.event-time[data-v-72048852] {
  font-size: 12px;
  color: var(--color-text-maxcontrast);
}
.event-location[data-v-72048852] {
  font-size: 12px;
  color: var(--color-text-maxcontrast);
  display: -webkit-box;
  -webkit-line-clamp: 1;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

/* Dark background theme (Primary color) */
.has-dark-background .event-title[data-v-72048852] {
  color: var(--color-primary-element-text);
}
.has-dark-background .event-time[data-v-72048852],
.has-dark-background .event-location[data-v-72048852] {
  color: var(--color-primary-element-text);
  opacity: 0.75;
}
.has-dark-background .event-proximity[data-v-72048852] {
  color: var(--color-primary-element-text) !important;
  opacity: 0.9;
}
.has-dark-background .calendar-event[data-v-72048852]:hover {
  background: rgba(255, 255, 255, 0.1);
}
.has-dark-background .calendar-loading[data-v-72048852],
.has-dark-background .calendar-empty[data-v-72048852] {
  color: var(--color-primary-element-text);
}
.has-dark-background .calendar-empty[data-v-72048852] {
  background: rgba(255, 255, 255, 0.1);
  border-color: rgba(255, 255, 255, 0.3);
}

/* Light background adjustments */
.has-background:not(.has-dark-background) .calendar-empty[data-v-72048852] {
  background: var(--color-main-background);
}

/* Wide mode - 2 columns */
@container (min-width: 500px) {
.calendar-event-list[data-v-72048852] {
    grid-template-columns: repeat(2, 1fr);
}
}

/* Extra wide mode - 3 columns */
@container (min-width: 800px) {
.calendar-event-list[data-v-72048852] {
    grid-template-columns: repeat(3, 1fr);
}
}

/* Compact mode for narrow containers (side columns ~200-300px) */
@container (max-width: 299px) {
.event-date-badge[data-v-72048852] {
    width: 40px;
    height: 40px;
    min-width: 40px;
    border-radius: 6px;
}
.event-date-day[data-v-72048852] {
    font-size: 16px;
}
.event-date-month[data-v-72048852] {
    font-size: 10px;
}
.calendar-event[data-v-72048852] {
    gap: 8px;
    padding: 6px 4px;
}
.event-title[data-v-72048852] {
    font-size: 13px;
}
.event-time[data-v-72048852] {
    font-size: 11px;
}
.event-location[data-v-72048852] {
    display: none;
}
.event-proximity[data-v-72048852] {
    font-size: 10px;
}
}
`, "",{"version":3,"sources":["webpack://./src/components/CalendarWidget.vue"],"names":[],"mappings":";AA2TA;EACE,WAAW;EACX,cAAc;EACd,2BAA2B;AAC7B;AAEA;EACE,kBAAkB;EAClB,eAAe;EACf,gBAAgB;EAChB,6BAA6B;AAC/B;AAEA;;;EAGE,aAAa;EACb,sBAAsB;EACtB,mBAAmB;EACnB,uBAAuB;EACvB,SAAS;EACT,kBAAkB;EAClB,oCAAoC;EACpC,kBAAkB;AACpB;AAEA;EACE,yBAAyB;AAC3B;AAEA;EACE,yCAAyC;EACzC,sCAAsC;EACtC,yCAAyC;AAC3C;AAEA;EACE,SAAS;EACT,eAAe;AACjB;AAEA;EACE,eAAe;EACf,eAAe;EACf,YAAY;AACd;AAEA;EACE,aAAa;EACb,0BAA0B;EAC1B,QAAQ;AACV;AAEA;EACE,aAAa;EACb,uBAAuB;EACvB,SAAS;EACT,YAAY;EACZ,yCAAyC;EACzC,4BAA4B;EAC5B,qBAAqB;EACrB,cAAc;EACd,eAAe;AACjB;AAEA;EACE,yCAAyC;AAC3C;AAEA;EACE,+CAA+C;EAC/C,mBAAmB;AACrB;;AAEA,wDAAwD;AACxD;EACE,aAAa;EACb,sBAAsB;EACtB,mBAAmB;EACnB,uBAAuB;EACvB,WAAW;EACX,YAAY;EACZ,eAAe;EACf,yCAAyC;EACzC,wCAAwC;EACxC,cAAc;AAChB;AAEA;EACE,eAAe;EACf,gBAAgB;EAChB,gBAAgB;AAClB;AAEA;EACE,eAAe;EACf,gBAAgB;EAChB,yBAAyB;EACzB,aAAa;EACb,gBAAgB;AAClB;;AAEA,+BAA+B;AAC/B;EACE,aAAa;EACb,sBAAsB;EACtB,QAAQ;EACR,YAAY;EACZ,gBAAgB;AAClB;AAEA;EACE,eAAe;EACf,gBAAgB;EAChB,yBAAyB;EACzB,qBAAqB;AACvB;AAEA;EACE,eAAe;EACf,gBAAgB;EAChB,6BAA6B;EAC7B,gBAAgB;EAChB,oBAAoB;EACpB,qBAAqB;EACrB,4BAA4B;EAC5B,gBAAgB;AAClB;AAEA;EACE,eAAe;EACf,oCAAoC;AACtC;AAEA;EACE,eAAe;EACf,oCAAoC;EACpC,oBAAoB;EACpB,qBAAqB;EACrB,4BAA4B;EAC5B,gBAAgB;AAClB;;AAEA,0CAA0C;AAC1C;EACE,wCAAwC;AAC1C;AAEA;;EAEE,wCAAwC;EACxC,aAAa;AACf;AAEA;EACE,mDAAmD;EACnD,YAAY;AACd;AAEA;EACE,oCAAoC;AACtC;AAEA;;EAEE,wCAAwC;AAC1C;AAEA;EACE,oCAAoC;EACpC,sCAAsC;AACxC;;AAEA,iCAAiC;AACjC;EACE,wCAAwC;AAC1C;;AAEA,0BAA0B;AAC1B;AACE;IACE,qCAAqC;AACvC;AACF;;AAEA,gCAAgC;AAChC;AACE;IACE,qCAAqC;AACvC;AACF;;AAEA,iEAAiE;AACjE;AACE;IACE,WAAW;IACX,YAAY;IACZ,eAAe;IACf,kBAAkB;AACpB;AAEA;IACE,eAAe;AACjB;AAEA;IACE,eAAe;AACjB;AAEA;IACE,QAAQ;IACR,gBAAgB;AAClB;AAEA;IACE,eAAe;AACjB;AAEA;IACE,eAAe;AACjB;AAEA;IACE,aAAa;AACf;AAEA;IACE,eAAe;AACjB;AACF","sourcesContent":["<template>\n  <div class=\"calendar-widget\" :class=\"{ 'has-background': !!effectiveBackgroundColor, 'has-dark-background': hasDarkBackground }\" :style=\"getContainerStyle()\">\n    <h3 v-if=\"widget.title\" class=\"calendar-widget-title\" :style=\"titleStyle\">{{ widget.title }}</h3>\n\n    <div v-if=\"loading\" class=\"calendar-loading\" role=\"status\" aria-live=\"polite\">\n      <NcLoadingIcon :size=\"32\" />\n      <span>{{ t('Loading events...') }}</span>\n    </div>\n\n    <div v-else-if=\"error\" class=\"calendar-error\" role=\"alert\">\n      <AlertCircle :size=\"24\" />\n      <span>{{ error }}</span>\n    </div>\n\n    <div v-else-if=\"!hasCalendars\" class=\"calendar-empty\">\n      <CalendarIcon :size=\"32\" />\n      <p>{{ t('No calendar selected') }}</p>\n      <small>{{ t('Select one or more calendars in the widget editor') }}</small>\n    </div>\n\n    <div v-else-if=\"events.length === 0\" class=\"calendar-empty\">\n      <CalendarIcon :size=\"32\" />\n      <p>{{ t('No upcoming events') }}</p>\n    </div>\n\n    <div v-else class=\"calendar-event-list\">\n      <component\n        :is=\"getEventUrl(event) ? 'a' : 'div'\"\n        v-for=\"event in events\"\n        :key=\"event.uid\"\n        :href=\"getEventUrl(event) || undefined\"\n        :target=\"event.isExternal && getEventUrl(event) ? '_blank' : undefined\"\n        :rel=\"event.isExternal && getEventUrl(event) ? 'noopener noreferrer' : undefined\"\n        class=\"calendar-event\"\n        :class=\"{ 'calendar-event--no-link': !getEventUrl(event) }\"\n      >\n        <div class=\"event-date-badge\" :style=\"{ backgroundColor: event.calendarColor }\">\n          <span class=\"event-date-day\">{{ getDayNumber(event.start) }}</span>\n          <span class=\"event-date-month\">{{ getMonthAbbr(event.start) }}</span>\n        </div>\n        <div class=\"event-details\">\n          <span v-if=\"getProximityLabel(event.start)\" class=\"event-proximity\" :style=\"{ color: event.calendarColor }\">\n            {{ getProximityLabel(event.start) }}\n          </span>\n          <span class=\"event-title\">{{ event.summary }}</span>\n          <span v-if=\"widget.showTime !== false\" class=\"event-time\">\n            <template v-if=\"event.isAllDay\">{{ t('All day') }}</template>\n            <template v-else>\n              {{ formatTime(event.start) }}<template v-if=\"event.end\"> - {{ formatTime(event.end) }}</template>\n            </template>\n          </span>\n          <span v-if=\"widget.showLocation && event.location\" class=\"event-location\">\n            {{ event.location }}\n          </span>\n        </div>\n      </component>\n    </div>\n  </div>\n</template>\n\n<script>\nimport { NcLoadingIcon } from '@nextcloud/vue';\nimport { generateUrl } from '@nextcloud/router';\nimport { translate as t } from '@nextcloud/l10n';\nimport axios from '@nextcloud/axios';\nimport AlertCircle from 'vue-material-design-icons/AlertCircle.vue';\nimport CalendarIcon from 'vue-material-design-icons/Calendar.vue';\n\nexport default {\n  name: 'CalendarWidget',\n  components: {\n    NcLoadingIcon,\n    AlertCircle,\n    CalendarIcon,\n  },\n  props: {\n    widget: {\n      type: Object,\n      required: true,\n    },\n    editable: {\n      type: Boolean,\n      default: false,\n    },\n    rowBackgroundColor: {\n      type: String,\n      default: '',\n    },\n    shareToken: {\n      type: String,\n      default: '',\n    },\n    pageId: {\n      type: String,\n      default: '',\n    },\n  },\n  data() {\n    return {\n      events: [],\n      loading: true,\n      error: null,\n    };\n  },\n  computed: {\n    hasCalendars() {\n      return (this.widget.calendarIds && this.widget.calendarIds.length > 0)\n        || (this.widget.externalIcsUrls && this.widget.externalIcsUrls.length > 0);\n    },\n    effectiveBackgroundColor() {\n      return this.widget.backgroundColor || this.rowBackgroundColor || '';\n    },\n    hasDarkBackground() {\n      return this.effectiveBackgroundColor === 'var(--color-primary-element)';\n    },\n    titleStyle() {\n      const bgColor = this.effectiveBackgroundColor;\n      const colorMappings = {\n        'var(--color-primary-element)': 'var(--color-primary-element-text)',\n        'var(--color-primary-element-light)': 'var(--color-primary-element-light-text)',\n        'var(--color-background-dark)': 'var(--color-main-text)',\n        'var(--color-background-hover)': 'var(--color-main-text)',\n      };\n      const textColor = colorMappings[bgColor];\n      if (textColor) {\n        return { color: textColor };\n      }\n      return {};\n    },\n  },\n  watch: {\n    widget: {\n      deep: true,\n      handler() {\n        this.fetchEvents();\n      },\n    },\n  },\n  mounted() {\n    this.fetchEvents();\n  },\n  methods: {\n    t(key, vars = {}) {\n      return t('intravox', key, vars);\n    },\n    async fetchEvents() {\n      if (!this.hasCalendars) {\n        this.loading = false;\n        return;\n      }\n\n      this.loading = true;\n      this.error = null;\n\n      try {\n        const { rangeStart, rangeEnd } = this.getDateRange();\n\n        const params = new URLSearchParams();\n        params.set('calendarIds', (this.widget.calendarIds || []).join(','));\n        if (this.widget.externalIcsUrls && this.widget.externalIcsUrls.length > 0) {\n          params.set('externalIcsUrls', JSON.stringify(this.widget.externalIcsUrls));\n        }\n        params.set('rangeStart', rangeStart);\n        params.set('rangeEnd', rangeEnd);\n        params.set('limit', String(this.widget.limit || 5));\n\n        const url = this.shareToken\n          ? generateUrl(`/apps/intravox/api/share/${this.shareToken}/calendar/events?${params}`)\n          : generateUrl(`/apps/intravox/api/calendar/events?${params}`);\n\n        const response = await axios.get(url);\n        this.events = response.data.events || [];\n      } catch (err) {\n        console.error('[CalendarWidget] Failed to fetch events:', err);\n        this.error = this.t('Failed to load events');\n      } finally {\n        this.loading = false;\n      }\n    },\n    getDateRange() {\n      const now = new Date();\n      let rangeStart = now.toISOString();\n      let rangeEnd;\n\n      switch (this.widget.dateRange) {\n        case 'this_week': {\n          const startOfWeek = new Date(now);\n          startOfWeek.setDate(now.getDate() - now.getDay() + 1);\n          startOfWeek.setHours(0, 0, 0, 0);\n          rangeStart = startOfWeek.toISOString();\n          const endOfWeek = new Date(startOfWeek);\n          endOfWeek.setDate(startOfWeek.getDate() + 7);\n          rangeEnd = endOfWeek.toISOString();\n          break;\n        }\n        case 'next_two_weeks': {\n          const end = new Date(now);\n          end.setDate(now.getDate() + 14);\n          rangeEnd = end.toISOString();\n          break;\n        }\n        case 'this_month': {\n          const startOfMonth = new Date(now.getFullYear(), now.getMonth(), 1);\n          rangeStart = startOfMonth.toISOString();\n          const endOfMonth = new Date(now.getFullYear(), now.getMonth() + 1, 0, 23, 59, 59);\n          rangeEnd = endOfMonth.toISOString();\n          break;\n        }\n        case 'next_three_months': {\n          const end = new Date(now);\n          end.setMonth(now.getMonth() + 3);\n          rangeEnd = end.toISOString();\n          break;\n        }\n        case 'next_six_months': {\n          const end = new Date(now);\n          end.setMonth(now.getMonth() + 6);\n          rangeEnd = end.toISOString();\n          break;\n        }\n        case 'next_year': {\n          const end = new Date(now);\n          end.setFullYear(now.getFullYear() + 1);\n          rangeEnd = end.toISOString();\n          break;\n        }\n        case 'past_week': {\n          const start = new Date(now);\n          start.setDate(now.getDate() - 7);\n          rangeStart = start.toISOString();\n          rangeEnd = now.toISOString();\n          break;\n        }\n        case 'past_month': {\n          const start = new Date(now);\n          start.setMonth(now.getMonth() - 1);\n          rangeStart = start.toISOString();\n          rangeEnd = now.toISOString();\n          break;\n        }\n        case 'past_three_months': {\n          const start = new Date(now);\n          start.setMonth(now.getMonth() - 3);\n          rangeStart = start.toISOString();\n          rangeEnd = now.toISOString();\n          break;\n        }\n        case 'upcoming':\n        default: {\n          const end = new Date(now);\n          end.setDate(now.getDate() + 30);\n          rangeEnd = end.toISOString();\n          break;\n        }\n      }\n\n      return { rangeStart, rangeEnd };\n    },\n    isPastRange() {\n      return ['past_week', 'past_month', 'past_three_months'].includes(this.widget.dateRange);\n    },\n    getDayNumber(dateStr) {\n      if (!dateStr) return '';\n      return new Date(dateStr).getDate();\n    },\n    getMonthAbbr(dateStr) {\n      if (!dateStr) return '';\n      return new Date(dateStr).toLocaleDateString(undefined, { month: 'short' }).toUpperCase();\n    },\n    getProximityLabel(dateStr) {\n      if (!dateStr) return null;\n      const date = new Date(dateStr);\n      const now = new Date();\n      const today = new Date(now.getFullYear(), now.getMonth(), now.getDate());\n      const eventDay = new Date(date.getFullYear(), date.getMonth(), date.getDate());\n      const diffDays = Math.round((eventDay - today) / (1000 * 60 * 60 * 24));\n\n      if (diffDays === 0) return this.t('Today');\n      if (diffDays === 1) return this.t('Tomorrow');\n      return null;\n    },\n    formatTime(dateStr) {\n      if (!dateStr) return '';\n      const date = new Date(dateStr);\n      return date.toLocaleTimeString(undefined, {\n        hour: '2-digit',\n        minute: '2-digit',\n      });\n    },\n    getEventUrl(event) {\n      // External events: use their URL if available, otherwise no link\n      if (event.isExternal) {\n        return event.url || undefined;\n      }\n      // In public share mode, calendar app is not available\n      if (this.shareToken) return undefined;\n      if (!event.start) return undefined;\n      const date = new Date(event.start);\n      const dateStr = date.toISOString().split('T')[0];\n      return generateUrl(`/apps/calendar/timeGridDay/${dateStr}`);\n    },\n    getContainerStyle() {\n      const style = {};\n      if (this.widget.backgroundColor) {\n        style.backgroundColor = this.widget.backgroundColor;\n        style.padding = '20px';\n        style.borderRadius = 'var(--border-radius-large)';\n      }\n      return style;\n    },\n  },\n};\n</script>\n\n<style scoped>\n.calendar-widget {\n  width: 100%;\n  margin: 12px 0;\n  container-type: inline-size;\n}\n\n.calendar-widget-title {\n  margin: 0 0 16px 0;\n  font-size: 18px;\n  font-weight: 600;\n  color: var(--color-main-text);\n}\n\n.calendar-loading,\n.calendar-error,\n.calendar-empty {\n  display: flex;\n  flex-direction: column;\n  align-items: center;\n  justify-content: center;\n  gap: 12px;\n  padding: 40px 20px;\n  color: var(--color-text-maxcontrast);\n  text-align: center;\n}\n\n.calendar-error {\n  color: var(--color-error);\n}\n\n.calendar-empty {\n  background: var(--color-background-hover);\n  border: 2px dashed var(--color-border);\n  border-radius: var(--border-radius-large);\n}\n\n.calendar-empty p {\n  margin: 0;\n  font-size: 14px;\n}\n\n.calendar-empty small {\n  margin-top: 4px;\n  font-size: 12px;\n  opacity: 0.7;\n}\n\n.calendar-event-list {\n  display: grid;\n  grid-template-columns: 1fr;\n  gap: 8px;\n}\n\n.calendar-event {\n  display: flex;\n  align-items: flex-start;\n  gap: 12px;\n  padding: 8px;\n  border-radius: var(--border-radius-large);\n  transition: background 0.15s;\n  text-decoration: none;\n  color: inherit;\n  cursor: pointer;\n}\n\n.calendar-event:hover {\n  background: var(--color-background-hover);\n}\n\n.calendar-event:focus-visible {\n  outline: 2px solid var(--color-primary-element);\n  outline-offset: 2px;\n}\n\n/* Date badge - colored square with day number + month */\n.event-date-badge {\n  display: flex;\n  flex-direction: column;\n  align-items: center;\n  justify-content: center;\n  width: 48px;\n  height: 48px;\n  min-width: 48px;\n  border-radius: var(--border-radius-large);\n  color: var(--color-primary-element-text);\n  flex-shrink: 0;\n}\n\n.event-date-day {\n  font-size: 18px;\n  font-weight: 600;\n  line-height: 1.1;\n}\n\n.event-date-month {\n  font-size: 11px;\n  font-weight: 500;\n  text-transform: uppercase;\n  opacity: 0.85;\n  line-height: 1.2;\n}\n\n/* Event details - right side */\n.event-details {\n  display: flex;\n  flex-direction: column;\n  gap: 1px;\n  min-width: 0;\n  padding-top: 2px;\n}\n\n.event-proximity {\n  font-size: 11px;\n  font-weight: 600;\n  text-transform: uppercase;\n  letter-spacing: 0.3px;\n}\n\n.event-title {\n  font-size: 14px;\n  font-weight: 600;\n  color: var(--color-main-text);\n  line-height: 1.3;\n  display: -webkit-box;\n  -webkit-line-clamp: 2;\n  -webkit-box-orient: vertical;\n  overflow: hidden;\n}\n\n.event-time {\n  font-size: 12px;\n  color: var(--color-text-maxcontrast);\n}\n\n.event-location {\n  font-size: 12px;\n  color: var(--color-text-maxcontrast);\n  display: -webkit-box;\n  -webkit-line-clamp: 1;\n  -webkit-box-orient: vertical;\n  overflow: hidden;\n}\n\n/* Dark background theme (Primary color) */\n.has-dark-background .event-title {\n  color: var(--color-primary-element-text);\n}\n\n.has-dark-background .event-time,\n.has-dark-background .event-location {\n  color: var(--color-primary-element-text);\n  opacity: 0.75;\n}\n\n.has-dark-background .event-proximity {\n  color: var(--color-primary-element-text) !important;\n  opacity: 0.9;\n}\n\n.has-dark-background .calendar-event:hover {\n  background: rgba(255, 255, 255, 0.1);\n}\n\n.has-dark-background .calendar-loading,\n.has-dark-background .calendar-empty {\n  color: var(--color-primary-element-text);\n}\n\n.has-dark-background .calendar-empty {\n  background: rgba(255, 255, 255, 0.1);\n  border-color: rgba(255, 255, 255, 0.3);\n}\n\n/* Light background adjustments */\n.has-background:not(.has-dark-background) .calendar-empty {\n  background: var(--color-main-background);\n}\n\n/* Wide mode - 2 columns */\n@container (min-width: 500px) {\n  .calendar-event-list {\n    grid-template-columns: repeat(2, 1fr);\n  }\n}\n\n/* Extra wide mode - 3 columns */\n@container (min-width: 800px) {\n  .calendar-event-list {\n    grid-template-columns: repeat(3, 1fr);\n  }\n}\n\n/* Compact mode for narrow containers (side columns ~200-300px) */\n@container (max-width: 299px) {\n  .event-date-badge {\n    width: 40px;\n    height: 40px;\n    min-width: 40px;\n    border-radius: 6px;\n  }\n\n  .event-date-day {\n    font-size: 16px;\n  }\n\n  .event-date-month {\n    font-size: 10px;\n  }\n\n  .calendar-event {\n    gap: 8px;\n    padding: 6px 4px;\n  }\n\n  .event-title {\n    font-size: 13px;\n  }\n\n  .event-time {\n    font-size: 11px;\n  }\n\n  .event-location {\n    display: none;\n  }\n\n  .event-proximity {\n    font-size: 10px;\n  }\n}\n</style>\n"],"sourceRoot":""}]);
// Exports
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (___CSS_LOADER_EXPORT___);


/***/ },

/***/ "./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/dist/stylePostLoader.js!./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/CalendarWidget.vue?vue&type=style&index=0&id=72048852&scoped=true&lang=css"
/*!*******************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/dist/stylePostLoader.js!./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/CalendarWidget.vue?vue&type=style&index=0&id=72048852&scoped=true&lang=css ***!
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
/* harmony import */ var _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_dist_stylePostLoader_js_node_modules_vue_loader_dist_index_js_ruleSet_1_rules_5_use_0_CalendarWidget_vue_vue_type_style_index_0_id_72048852_scoped_true_lang_css__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! !!../../node_modules/css-loader/dist/cjs.js!../../node_modules/vue-loader/dist/stylePostLoader.js!../../node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./CalendarWidget.vue?vue&type=style&index=0&id=72048852&scoped=true&lang=css */ "./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/dist/stylePostLoader.js!./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/CalendarWidget.vue?vue&type=style&index=0&id=72048852&scoped=true&lang=css");

      
      
      
      
      
      
      
      
      

var options = {};

options.styleTagTransform = (_node_modules_style_loader_dist_runtime_styleTagTransform_js__WEBPACK_IMPORTED_MODULE_5___default());
options.setAttributes = (_node_modules_style_loader_dist_runtime_setAttributesWithoutAttributes_js__WEBPACK_IMPORTED_MODULE_3___default());

      options.insert = _node_modules_style_loader_dist_runtime_insertBySelector_js__WEBPACK_IMPORTED_MODULE_2___default().bind(null, "head");
    
options.domAPI = (_node_modules_style_loader_dist_runtime_styleDomAPI_js__WEBPACK_IMPORTED_MODULE_1___default());
options.insertStyleElement = (_node_modules_style_loader_dist_runtime_insertStyleElement_js__WEBPACK_IMPORTED_MODULE_4___default());

var update = _node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0___default()(_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_dist_stylePostLoader_js_node_modules_vue_loader_dist_index_js_ruleSet_1_rules_5_use_0_CalendarWidget_vue_vue_type_style_index_0_id_72048852_scoped_true_lang_css__WEBPACK_IMPORTED_MODULE_6__["default"], options);




       /* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_dist_stylePostLoader_js_node_modules_vue_loader_dist_index_js_ruleSet_1_rules_5_use_0_CalendarWidget_vue_vue_type_style_index_0_id_72048852_scoped_true_lang_css__WEBPACK_IMPORTED_MODULE_6__["default"] && _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_dist_stylePostLoader_js_node_modules_vue_loader_dist_index_js_ruleSet_1_rules_5_use_0_CalendarWidget_vue_vue_type_style_index_0_id_72048852_scoped_true_lang_css__WEBPACK_IMPORTED_MODULE_6__["default"].locals ? _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_dist_stylePostLoader_js_node_modules_vue_loader_dist_index_js_ruleSet_1_rules_5_use_0_CalendarWidget_vue_vue_type_style_index_0_id_72048852_scoped_true_lang_css__WEBPACK_IMPORTED_MODULE_6__["default"].locals : undefined);


/***/ },

/***/ "./src/components/CalendarWidget.vue"
/*!*******************************************!*\
  !*** ./src/components/CalendarWidget.vue ***!
  \*******************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _CalendarWidget_vue_vue_type_template_id_72048852_scoped_true__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./CalendarWidget.vue?vue&type=template&id=72048852&scoped=true */ "./src/components/CalendarWidget.vue?vue&type=template&id=72048852&scoped=true");
/* harmony import */ var _CalendarWidget_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./CalendarWidget.vue?vue&type=script&lang=js */ "./src/components/CalendarWidget.vue?vue&type=script&lang=js");
/* harmony import */ var _CalendarWidget_vue_vue_type_style_index_0_id_72048852_scoped_true_lang_css__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./CalendarWidget.vue?vue&type=style&index=0&id=72048852&scoped=true&lang=css */ "./src/components/CalendarWidget.vue?vue&type=style&index=0&id=72048852&scoped=true&lang=css");
/* harmony import */ var _node_modules_vue_loader_dist_exportHelper_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ../../node_modules/vue-loader/dist/exportHelper.js */ "./node_modules/vue-loader/dist/exportHelper.js");




;


const __exports__ = /*#__PURE__*/(0,_node_modules_vue_loader_dist_exportHelper_js__WEBPACK_IMPORTED_MODULE_3__["default"])(_CalendarWidget_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_1__["default"], [['render',_CalendarWidget_vue_vue_type_template_id_72048852_scoped_true__WEBPACK_IMPORTED_MODULE_0__.render],['__scopeId',"data-v-72048852"],['__file',"src/components/CalendarWidget.vue"]])
/* hot reload */
if (false) // removed by dead control flow
{}


/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (__exports__);

/***/ },

/***/ "./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/CalendarWidget.vue?vue&type=script&lang=js"
/*!***************************************************************************************************************************************!*\
  !*** ./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/CalendarWidget.vue?vue&type=script&lang=js ***!
  \***************************************************************************************************************************************/
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
/* harmony import */ var vue_material_design_icons_Calendar_vue__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! vue-material-design-icons/Calendar.vue */ "./node_modules/vue-material-design-icons/Calendar.vue");








/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = ({
  name: 'CalendarWidget',
  components: {
    NcLoadingIcon: _nextcloud_vue__WEBPACK_IMPORTED_MODULE_0__.NcLoadingIcon,
    AlertCircle: vue_material_design_icons_AlertCircle_vue__WEBPACK_IMPORTED_MODULE_4__["default"],
    CalendarIcon: vue_material_design_icons_Calendar_vue__WEBPACK_IMPORTED_MODULE_5__["default"],
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
      events: [],
      loading: true,
      error: null,
    };
  },
  computed: {
    hasCalendars() {
      return (this.widget.calendarIds && this.widget.calendarIds.length > 0)
        || (this.widget.externalIcsUrls && this.widget.externalIcsUrls.length > 0);
    },
    effectiveBackgroundColor() {
      return this.widget.backgroundColor || this.rowBackgroundColor || '';
    },
    hasDarkBackground() {
      return this.effectiveBackgroundColor === 'var(--color-primary-element)';
    },
    titleStyle() {
      const bgColor = this.effectiveBackgroundColor;
      const colorMappings = {
        'var(--color-primary-element)': 'var(--color-primary-element-text)',
        'var(--color-primary-element-light)': 'var(--color-primary-element-light-text)',
        'var(--color-background-dark)': 'var(--color-main-text)',
        'var(--color-background-hover)': 'var(--color-main-text)',
      };
      const textColor = colorMappings[bgColor];
      if (textColor) {
        return { color: textColor };
      }
      return {};
    },
  },
  watch: {
    widget: {
      deep: true,
      handler() {
        this.fetchEvents();
      },
    },
  },
  mounted() {
    this.fetchEvents();
  },
  methods: {
    t(key, vars = {}) {
      return (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_2__.translate)('intravox', key, vars);
    },
    async fetchEvents() {
      if (!this.hasCalendars) {
        this.loading = false;
        return;
      }

      this.loading = true;
      this.error = null;

      try {
        const { rangeStart, rangeEnd } = this.getDateRange();

        const params = new URLSearchParams();
        params.set('calendarIds', (this.widget.calendarIds || []).join(','));
        if (this.widget.externalIcsUrls && this.widget.externalIcsUrls.length > 0) {
          params.set('externalIcsUrls', JSON.stringify(this.widget.externalIcsUrls));
        }
        params.set('rangeStart', rangeStart);
        params.set('rangeEnd', rangeEnd);
        params.set('limit', String(this.widget.limit || 5));

        const url = this.shareToken
          ? (0,_nextcloud_router__WEBPACK_IMPORTED_MODULE_1__.generateUrl)(`/apps/intravox/api/share/${this.shareToken}/calendar/events?${params}`)
          : (0,_nextcloud_router__WEBPACK_IMPORTED_MODULE_1__.generateUrl)(`/apps/intravox/api/calendar/events?${params}`);

        const response = await _nextcloud_axios__WEBPACK_IMPORTED_MODULE_3__["default"].get(url);
        this.events = response.data.events || [];
      } catch (err) {
        console.error('[CalendarWidget] Failed to fetch events:', err);
        this.error = this.t('Failed to load events');
      } finally {
        this.loading = false;
      }
    },
    getDateRange() {
      const now = new Date();
      let rangeStart = now.toISOString();
      let rangeEnd;

      switch (this.widget.dateRange) {
        case 'this_week': {
          const startOfWeek = new Date(now);
          startOfWeek.setDate(now.getDate() - now.getDay() + 1);
          startOfWeek.setHours(0, 0, 0, 0);
          rangeStart = startOfWeek.toISOString();
          const endOfWeek = new Date(startOfWeek);
          endOfWeek.setDate(startOfWeek.getDate() + 7);
          rangeEnd = endOfWeek.toISOString();
          break;
        }
        case 'next_two_weeks': {
          const end = new Date(now);
          end.setDate(now.getDate() + 14);
          rangeEnd = end.toISOString();
          break;
        }
        case 'this_month': {
          const startOfMonth = new Date(now.getFullYear(), now.getMonth(), 1);
          rangeStart = startOfMonth.toISOString();
          const endOfMonth = new Date(now.getFullYear(), now.getMonth() + 1, 0, 23, 59, 59);
          rangeEnd = endOfMonth.toISOString();
          break;
        }
        case 'next_three_months': {
          const end = new Date(now);
          end.setMonth(now.getMonth() + 3);
          rangeEnd = end.toISOString();
          break;
        }
        case 'next_six_months': {
          const end = new Date(now);
          end.setMonth(now.getMonth() + 6);
          rangeEnd = end.toISOString();
          break;
        }
        case 'next_year': {
          const end = new Date(now);
          end.setFullYear(now.getFullYear() + 1);
          rangeEnd = end.toISOString();
          break;
        }
        case 'past_week': {
          const start = new Date(now);
          start.setDate(now.getDate() - 7);
          rangeStart = start.toISOString();
          rangeEnd = now.toISOString();
          break;
        }
        case 'past_month': {
          const start = new Date(now);
          start.setMonth(now.getMonth() - 1);
          rangeStart = start.toISOString();
          rangeEnd = now.toISOString();
          break;
        }
        case 'past_three_months': {
          const start = new Date(now);
          start.setMonth(now.getMonth() - 3);
          rangeStart = start.toISOString();
          rangeEnd = now.toISOString();
          break;
        }
        case 'upcoming':
        default: {
          const end = new Date(now);
          end.setDate(now.getDate() + 30);
          rangeEnd = end.toISOString();
          break;
        }
      }

      return { rangeStart, rangeEnd };
    },
    isPastRange() {
      return ['past_week', 'past_month', 'past_three_months'].includes(this.widget.dateRange);
    },
    getDayNumber(dateStr) {
      if (!dateStr) return '';
      return new Date(dateStr).getDate();
    },
    getMonthAbbr(dateStr) {
      if (!dateStr) return '';
      return new Date(dateStr).toLocaleDateString(undefined, { month: 'short' }).toUpperCase();
    },
    getProximityLabel(dateStr) {
      if (!dateStr) return null;
      const date = new Date(dateStr);
      const now = new Date();
      const today = new Date(now.getFullYear(), now.getMonth(), now.getDate());
      const eventDay = new Date(date.getFullYear(), date.getMonth(), date.getDate());
      const diffDays = Math.round((eventDay - today) / (1000 * 60 * 60 * 24));

      if (diffDays === 0) return this.t('Today');
      if (diffDays === 1) return this.t('Tomorrow');
      return null;
    },
    formatTime(dateStr) {
      if (!dateStr) return '';
      const date = new Date(dateStr);
      return date.toLocaleTimeString(undefined, {
        hour: '2-digit',
        minute: '2-digit',
      });
    },
    getEventUrl(event) {
      // External events: use their URL if available, otherwise no link
      if (event.isExternal) {
        return event.url || undefined;
      }
      // In public share mode, calendar app is not available
      if (this.shareToken) return undefined;
      if (!event.start) return undefined;
      const date = new Date(event.start);
      const dateStr = date.toISOString().split('T')[0];
      return (0,_nextcloud_router__WEBPACK_IMPORTED_MODULE_1__.generateUrl)(`/apps/calendar/timeGridDay/${dateStr}`);
    },
    getContainerStyle() {
      const style = {};
      if (this.widget.backgroundColor) {
        style.backgroundColor = this.widget.backgroundColor;
        style.padding = '20px';
        style.borderRadius = 'var(--border-radius-large)';
      }
      return style;
    },
  },
});


/***/ },

/***/ "./src/components/CalendarWidget.vue?vue&type=style&index=0&id=72048852&scoped=true&lang=css"
/*!***************************************************************************************************!*\
  !*** ./src/components/CalendarWidget.vue?vue&type=style&index=0&id=72048852&scoped=true&lang=css ***!
  \***************************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _node_modules_style_loader_dist_cjs_js_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_dist_stylePostLoader_js_node_modules_vue_loader_dist_index_js_ruleSet_1_rules_5_use_0_CalendarWidget_vue_vue_type_style_index_0_id_72048852_scoped_true_lang_css__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../node_modules/style-loader/dist/cjs.js!../../node_modules/css-loader/dist/cjs.js!../../node_modules/vue-loader/dist/stylePostLoader.js!../../node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./CalendarWidget.vue?vue&type=style&index=0&id=72048852&scoped=true&lang=css */ "./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/dist/stylePostLoader.js!./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/CalendarWidget.vue?vue&type=style&index=0&id=72048852&scoped=true&lang=css");


/***/ },

/***/ "./src/components/CalendarWidget.vue?vue&type=script&lang=js"
/*!*******************************************************************!*\
  !*** ./src/components/CalendarWidget.vue?vue&type=script&lang=js ***!
  \*******************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* reexport safe */ _node_modules_vue_loader_dist_index_js_ruleSet_1_rules_5_use_0_CalendarWidget_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_0__["default"])
/* harmony export */ });
/* harmony import */ var _node_modules_vue_loader_dist_index_js_ruleSet_1_rules_5_use_0_CalendarWidget_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./CalendarWidget.vue?vue&type=script&lang=js */ "./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/CalendarWidget.vue?vue&type=script&lang=js");
 

/***/ },

/***/ "./src/components/CalendarWidget.vue?vue&type=template&id=72048852&scoped=true"
/*!*************************************************************************************!*\
  !*** ./src/components/CalendarWidget.vue?vue&type=template&id=72048852&scoped=true ***!
  \*************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   render: () => (/* reexport safe */ _node_modules_vue_loader_dist_templateLoader_js_ruleSet_1_rules_2_node_modules_vue_loader_dist_index_js_ruleSet_1_rules_5_use_0_CalendarWidget_vue_vue_type_template_id_72048852_scoped_true__WEBPACK_IMPORTED_MODULE_0__.render)
/* harmony export */ });
/* harmony import */ var _node_modules_vue_loader_dist_templateLoader_js_ruleSet_1_rules_2_node_modules_vue_loader_dist_index_js_ruleSet_1_rules_5_use_0_CalendarWidget_vue_vue_type_template_id_72048852_scoped_true__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../node_modules/vue-loader/dist/templateLoader.js??ruleSet[1].rules[2]!../../node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./CalendarWidget.vue?vue&type=template&id=72048852&scoped=true */ "./node_modules/vue-loader/dist/templateLoader.js??ruleSet[1].rules[2]!./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/CalendarWidget.vue?vue&type=template&id=72048852&scoped=true");


/***/ },

/***/ "./node_modules/vue-loader/dist/templateLoader.js??ruleSet[1].rules[2]!./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/CalendarWidget.vue?vue&type=template&id=72048852&scoped=true"
/*!*******************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/vue-loader/dist/templateLoader.js??ruleSet[1].rules[2]!./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/CalendarWidget.vue?vue&type=template&id=72048852&scoped=true ***!
  \*******************************************************************************************************************************************************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   render: () => (/* binding */ render)
/* harmony export */ });
/* harmony import */ var vue__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! vue */ "./node_modules/vue/dist/vue.runtime.esm-bundler.js");


const _hoisted_1 = {
  key: 1,
  class: "calendar-loading",
  role: "status",
  "aria-live": "polite"
}
const _hoisted_2 = {
  key: 2,
  class: "calendar-error",
  role: "alert"
}
const _hoisted_3 = {
  key: 3,
  class: "calendar-empty"
}
const _hoisted_4 = {
  key: 4,
  class: "calendar-empty"
}
const _hoisted_5 = {
  key: 5,
  class: "calendar-event-list"
}
const _hoisted_6 = { class: "event-date-day" }
const _hoisted_7 = { class: "event-date-month" }
const _hoisted_8 = { class: "event-details" }
const _hoisted_9 = { class: "event-title" }
const _hoisted_10 = {
  key: 1,
  class: "event-time"
}
const _hoisted_11 = {
  key: 2,
  class: "event-location"
}

function render(_ctx, _cache, $props, $setup, $data, $options) {
  const _component_NcLoadingIcon = (0,vue__WEBPACK_IMPORTED_MODULE_0__.resolveComponent)("NcLoadingIcon")
  const _component_AlertCircle = (0,vue__WEBPACK_IMPORTED_MODULE_0__.resolveComponent)("AlertCircle")
  const _component_CalendarIcon = (0,vue__WEBPACK_IMPORTED_MODULE_0__.resolveComponent)("CalendarIcon")

  return ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)("div", {
    class: (0,vue__WEBPACK_IMPORTED_MODULE_0__.normalizeClass)(["calendar-widget", { 'has-background': !!$options.effectiveBackgroundColor, 'has-dark-background': $options.hasDarkBackground }]),
    style: (0,vue__WEBPACK_IMPORTED_MODULE_0__.normalizeStyle)($options.getContainerStyle())
  }, [
    ($props.widget.title)
      ? ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)("h3", {
          key: 0,
          class: "calendar-widget-title",
          style: (0,vue__WEBPACK_IMPORTED_MODULE_0__.normalizeStyle)($options.titleStyle)
        }, (0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)($props.widget.title), 5 /* TEXT, STYLE */))
      : (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)("v-if", true),
    ($data.loading)
      ? ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)("div", _hoisted_1, [
          (0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_NcLoadingIcon, { size: 32 }),
          (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("span", null, (0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)($options.t('Loading events...')), 1 /* TEXT */)
        ]))
      : ($data.error)
        ? ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)("div", _hoisted_2, [
            (0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_AlertCircle, { size: 24 }),
            (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("span", null, (0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)($data.error), 1 /* TEXT */)
          ]))
        : (!$options.hasCalendars)
          ? ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)("div", _hoisted_3, [
              (0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_CalendarIcon, { size: 32 }),
              (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("p", null, (0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)($options.t('No calendar selected')), 1 /* TEXT */),
              (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("small", null, (0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)($options.t('Select one or more calendars in the widget editor')), 1 /* TEXT */)
            ]))
          : ($data.events.length === 0)
            ? ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)("div", _hoisted_4, [
                (0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_CalendarIcon, { size: 32 }),
                (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("p", null, (0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)($options.t('No upcoming events')), 1 /* TEXT */)
              ]))
            : ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)("div", _hoisted_5, [
                ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(true), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)(vue__WEBPACK_IMPORTED_MODULE_0__.Fragment, null, (0,vue__WEBPACK_IMPORTED_MODULE_0__.renderList)($data.events, (event) => {
                  return ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createBlock)((0,vue__WEBPACK_IMPORTED_MODULE_0__.resolveDynamicComponent)($options.getEventUrl(event) ? 'a' : 'div'), {
                    key: event.uid,
                    href: $options.getEventUrl(event) || undefined,
                    target: event.isExternal && $options.getEventUrl(event) ? '_blank' : undefined,
                    rel: event.isExternal && $options.getEventUrl(event) ? 'noopener noreferrer' : undefined,
                    class: (0,vue__WEBPACK_IMPORTED_MODULE_0__.normalizeClass)(["calendar-event", { 'calendar-event--no-link': !$options.getEventUrl(event) }])
                  }, {
                    default: (0,vue__WEBPACK_IMPORTED_MODULE_0__.withCtx)(() => [
                      (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("div", {
                        class: "event-date-badge",
                        style: (0,vue__WEBPACK_IMPORTED_MODULE_0__.normalizeStyle)({ backgroundColor: event.calendarColor })
                      }, [
                        (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("span", _hoisted_6, (0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)($options.getDayNumber(event.start)), 1 /* TEXT */),
                        (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("span", _hoisted_7, (0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)($options.getMonthAbbr(event.start)), 1 /* TEXT */)
                      ], 4 /* STYLE */),
                      (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("div", _hoisted_8, [
                        ($options.getProximityLabel(event.start))
                          ? ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)("span", {
                              key: 0,
                              class: "event-proximity",
                              style: (0,vue__WEBPACK_IMPORTED_MODULE_0__.normalizeStyle)({ color: event.calendarColor })
                            }, (0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)($options.getProximityLabel(event.start)), 5 /* TEXT, STYLE */))
                          : (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)("v-if", true),
                        (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("span", _hoisted_9, (0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)(event.summary), 1 /* TEXT */),
                        ($props.widget.showTime !== false)
                          ? ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)("span", _hoisted_10, [
                              (event.isAllDay)
                                ? ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)(vue__WEBPACK_IMPORTED_MODULE_0__.Fragment, { key: 0 }, [
                                    (0,vue__WEBPACK_IMPORTED_MODULE_0__.createTextVNode)((0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)($options.t('All day')), 1 /* TEXT */)
                                  ], 64 /* STABLE_FRAGMENT */))
                                : ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)(vue__WEBPACK_IMPORTED_MODULE_0__.Fragment, { key: 1 }, [
                                    (0,vue__WEBPACK_IMPORTED_MODULE_0__.createTextVNode)((0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)($options.formatTime(event.start)), 1 /* TEXT */),
                                    (event.end)
                                      ? ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)(vue__WEBPACK_IMPORTED_MODULE_0__.Fragment, { key: 0 }, [
                                          (0,vue__WEBPACK_IMPORTED_MODULE_0__.createTextVNode)(" - " + (0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)($options.formatTime(event.end)), 1 /* TEXT */)
                                        ], 64 /* STABLE_FRAGMENT */))
                                      : (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)("v-if", true)
                                  ], 64 /* STABLE_FRAGMENT */))
                            ]))
                          : (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)("v-if", true),
                        ($props.widget.showLocation && event.location)
                          ? ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)("span", _hoisted_11, (0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)(event.location), 1 /* TEXT */))
                          : (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)("v-if", true)
                      ])
                    ]),
                    _: 2 /* DYNAMIC */
                  }, 1032 /* PROPS, DYNAMIC_SLOTS */, ["href", "target", "rel", "class"]))
                }), 128 /* KEYED_FRAGMENT */))
              ]))
  ], 6 /* CLASS, STYLE */))
}

/***/ }

}]);
//# sourceMappingURL=intravox-src_components_CalendarWidget_vue.cd1be272.js.map