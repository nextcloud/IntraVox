"use strict";
(self["webpackChunknextcloud_intravox"] = self["webpackChunknextcloud_intravox"] || []).push([["src_components_LinksWidget_vue"],{

/***/ "./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/dist/stylePostLoader.js!./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/LinksWidget.vue?vue&type=style&index=0&id=79936edd&scoped=true&lang=css"
/*!************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/dist/stylePostLoader.js!./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/LinksWidget.vue?vue&type=style&index=0&id=79936edd&scoped=true&lang=css ***!
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
.links-widget[data-v-79936edd] {
  width: 100%;
  margin: 12px 0;
}
.links-grid[data-v-79936edd] {
  display: grid;
  gap: 12px;
  grid-template-columns: repeat(2, 1fr);
}

/* Base link-item styles */
.link-item[data-v-79936edd] {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 16px;
  min-width: 0;
  border: 1px solid var(--color-border);
  border-radius: var(--border-radius-container-large);
  color: var(--color-main-text);
  text-decoration: none;
  transition: background 0.2s, border-color 0.2s, box-shadow 0.2s;
  cursor: pointer;
}
.link-icon[data-v-79936edd] {
  flex-shrink: 0;
  color: var(--color-primary-element);
}

/* Transparent — default: blends into container/row background */
.link-item--bg-transparent[data-v-79936edd] {
  background: transparent;
}
.link-item--bg-transparent[data-v-79936edd]:hover {
  background: rgba(0, 0, 0, 0.04);
  border-color: var(--color-primary-element);
  box-shadow: 0 2px 6px rgba(0, 0, 0, 0.06);
}

/* On dark container — transparent links with white text/icons */
.link-item--bg-on-dark[data-v-79936edd] {
  background: transparent;
  border-color: rgba(255, 255, 255, 0.2);
  color: var(--color-primary-element-text);
}
.link-item--bg-on-dark .link-icon[data-v-79936edd] {
  color: rgba(255, 255, 255, 0.85);
}
.link-item--bg-on-dark[data-v-79936edd]:hover {
  background: rgba(255, 255, 255, 0.12);
  border-color: rgba(255, 255, 255, 0.4);
  color: var(--color-primary-element-text);
  box-shadow: 0 2px 6px rgba(0, 0, 0, 0.15);
}
.link-item--bg-on-dark:hover .link-icon[data-v-79936edd] {
  color: var(--color-primary-element-text);
}

/* Explicit light background — individual link set to Light */
.link-item--bg-light[data-v-79936edd] {
  background: var(--color-background-hover);
  border-color: var(--color-border);
  color: var(--color-main-text);
}
.link-item--bg-light .link-icon[data-v-79936edd] {
  color: var(--color-primary-element);
}
.link-item--bg-light[data-v-79936edd]:hover {
  background: var(--color-primary-element-light);
  border-color: var(--color-primary-element);
  box-shadow: 0 2px 6px rgba(0, 0, 0, 0.08);
}

/* Explicit dark background — individual link set to Primary */
.link-item--bg-dark[data-v-79936edd] {
  background: var(--color-primary-element);
  border-color: var(--color-primary-element);
  color: var(--color-primary-element-text);
}
.link-item--bg-dark .link-icon[data-v-79936edd] {
  color: rgba(255, 255, 255, 0.85);
}
.link-item--bg-dark[data-v-79936edd]:hover {
  filter: brightness(1.1);
  box-shadow: 0 2px 6px rgba(0, 0, 0, 0.15);
}
.link-item--bg-dark:hover .link-icon[data-v-79936edd] {
  color: var(--color-primary-element-text);
}

/* Tile layout — centered icon above text */
.link-item--tile[data-v-79936edd] {
  flex-direction: column;
  text-align: center;
  padding: 20px 12px;
  min-height: 100px;
  justify-content: center;
  gap: 8px;
}
.link-item--tile .link-icon[data-v-79936edd] {
  margin-bottom: 4px;
}
.link-tile-content[data-v-79936edd] {
  display: flex;
  flex-direction: column;
  gap: 2px;
}
.link-tile-title[data-v-79936edd] {
  font-weight: 600;
  font-size: 14px;
  overflow-wrap: break-word;
  word-break: break-word;
}
.link-tile-subtitle[data-v-79936edd] {
  font-size: 12px;
  opacity: 0.7;
  overflow-wrap: break-word;
  word-break: break-word;
}
.link-text[data-v-79936edd] {
  font-weight: 500;
  font-size: 14px;
  min-width: 0;
  overflow-wrap: break-word;
  word-break: break-word;
}

/* Empty placeholder */
.empty-links-placeholder[data-v-79936edd] {
  text-align: center;
  padding: 40px 20px;
  background: var(--color-background-hover);
  border: 2px dashed var(--color-border);
  border-radius: var(--border-radius-large);
  color: var(--color-text-maxcontrast);
}
.empty-links-placeholder svg[data-v-79936edd] {
  margin-bottom: 10px;
  color: var(--color-text-maxcontrast);
}
.empty-links-placeholder p[data-v-79936edd] {
  margin: 0;
  font-size: 14px;
}

/* Mobile responsiveness */
@media (max-width: 768px) {
.links-grid[data-v-79936edd] {
    grid-template-columns: 1fr !important;
}
.links-grid[data-v-79936edd]:has(.link-item--tile) {
    grid-template-columns: repeat(2, 1fr) !important;
}
.link-item[data-v-79936edd] {
    padding: 14px;
}
.link-item--tile[data-v-79936edd] {
    padding: 16px 8px;
    min-height: 80px;
}
}
`, "",{"version":3,"sources":["webpack://./src/components/LinksWidget.vue"],"names":[],"mappings":";AAiWA;EACE,WAAW;EACX,cAAc;AAChB;AAEA;EACE,aAAa;EACb,SAAS;EACT,qCAAqC;AACvC;;AAEA,0BAA0B;AAC1B;EACE,aAAa;EACb,mBAAmB;EACnB,SAAS;EACT,aAAa;EACb,YAAY;EACZ,qCAAqC;EACrC,mDAAmD;EACnD,6BAA6B;EAC7B,qBAAqB;EACrB,+DAA+D;EAC/D,eAAe;AACjB;AAEA;EACE,cAAc;EACd,mCAAmC;AACrC;;AAEA,gEAAgE;AAChE;EACE,uBAAuB;AACzB;AAEA;EACE,+BAA+B;EAC/B,0CAA0C;EAC1C,yCAAyC;AAC3C;;AAEA,gEAAgE;AAChE;EACE,uBAAuB;EACvB,sCAAsC;EACtC,wCAAwC;AAC1C;AAEA;EACE,gCAAgC;AAClC;AAEA;EACE,qCAAqC;EACrC,sCAAsC;EACtC,wCAAwC;EACxC,yCAAyC;AAC3C;AAEA;EACE,wCAAwC;AAC1C;;AAEA,6DAA6D;AAC7D;EACE,yCAAyC;EACzC,iCAAiC;EACjC,6BAA6B;AAC/B;AAEA;EACE,mCAAmC;AACrC;AAEA;EACE,8CAA8C;EAC9C,0CAA0C;EAC1C,yCAAyC;AAC3C;;AAEA,8DAA8D;AAC9D;EACE,wCAAwC;EACxC,0CAA0C;EAC1C,wCAAwC;AAC1C;AAEA;EACE,gCAAgC;AAClC;AAEA;EACE,uBAAuB;EACvB,yCAAyC;AAC3C;AAEA;EACE,wCAAwC;AAC1C;;AAEA,2CAA2C;AAC3C;EACE,sBAAsB;EACtB,kBAAkB;EAClB,kBAAkB;EAClB,iBAAiB;EACjB,uBAAuB;EACvB,QAAQ;AACV;AAEA;EACE,kBAAkB;AACpB;AAEA;EACE,aAAa;EACb,sBAAsB;EACtB,QAAQ;AACV;AAEA;EACE,gBAAgB;EAChB,eAAe;EACf,yBAAyB;EACzB,sBAAsB;AACxB;AAEA;EACE,eAAe;EACf,YAAY;EACZ,yBAAyB;EACzB,sBAAsB;AACxB;AAEA;EACE,gBAAgB;EAChB,eAAe;EACf,YAAY;EACZ,yBAAyB;EACzB,sBAAsB;AACxB;;AAEA,sBAAsB;AACtB;EACE,kBAAkB;EAClB,kBAAkB;EAClB,yCAAyC;EACzC,sCAAsC;EACtC,yCAAyC;EACzC,oCAAoC;AACtC;AAEA;EACE,mBAAmB;EACnB,oCAAoC;AACtC;AAEA;EACE,SAAS;EACT,eAAe;AACjB;;AAEA,0BAA0B;AAC1B;AACE;IACE,qCAAqC;AACvC;AAEA;IACE,gDAAgD;AAClD;AAEA;IACE,aAAa;AACf;AAEA;IACE,iBAAiB;IACjB,gBAAgB;AAClB;AACF","sourcesContent":["<template>\n  <div class=\"links-widget\" :style=\"getContainerStyle()\">\n    <div v-if=\"widget.items && widget.items.length > 0\" class=\"links-grid\" :style=\"getGridStyle()\">\n      <a\n        v-for=\"(link, index) in widget.items\"\n        :key=\"link.uniqueId || link.url || `link-${index}`\"\n        :href=\"getLinkUrl(link)\"\n        :target=\"isInternalLink(link) ? '_self' : '_blank'\"\n        :rel=\"isInternalLink(link) ? '' : 'noopener noreferrer'\"\n        class=\"link-item\"\n        :class=\"[getLinkItemClass(link), { 'link-item--tile': isTileLayout }]\"\n        :style=\"getLinkStyle(link)\"\n        @click=\"handleLinkClick($event, link)\"\n      >\n        <component\n          v-if=\"link.icon\"\n          :is=\"getIconComponent(link.icon)\"\n          :size=\"isTileLayout ? 36 : 24\"\n          class=\"link-icon\"\n        />\n        <span v-if=\"isTileLayout && link.title && link.text\" class=\"link-tile-content\">\n          <span class=\"link-tile-title\" v-html=\"sanitizeHtml(link.title)\"></span>\n          <span class=\"link-tile-subtitle\" v-html=\"sanitizeHtml(link.text)\"></span>\n        </span>\n        <span v-else class=\"link-text\" v-html=\"sanitizeHtml(link.text || link.title)\"></span>\n      </a>\n    </div>\n    <div v-else class=\"empty-links-placeholder\">\n      <ViewGrid :size=\"32\" />\n      <p>{{ t('No links configured. Click the edit button to add links.') }}</p>\n    </div>\n  </div>\n</template>\n\n<script>\nimport { translate as t } from '@nextcloud/l10n';\nimport { markdownToHtml } from '../utils/markdownSerializer.js';\nimport { isDarkBackground, getEffectiveBackgroundColor } from '../utils/colorUtils.js';\n\n// Import common Material Design Icons\nimport ViewGrid from 'vue-material-design-icons/ViewGrid.vue';\nimport Home from 'vue-material-design-icons/Home.vue';\nimport Information from 'vue-material-design-icons/Information.vue';\nimport Email from 'vue-material-design-icons/Email.vue';\nimport Phone from 'vue-material-design-icons/Phone.vue';\nimport Web from 'vue-material-design-icons/Web.vue';\nimport FileDocument from 'vue-material-design-icons/FileDocument.vue';\nimport Download from 'vue-material-design-icons/Download.vue';\nimport Help from 'vue-material-design-icons/Help.vue';\nimport Cog from 'vue-material-design-icons/Cog.vue';\nimport Account from 'vue-material-design-icons/Account.vue';\nimport AccountGroup from 'vue-material-design-icons/AccountGroup.vue';\nimport AccountMultiple from 'vue-material-design-icons/AccountMultiple.vue';\nimport AccountTie from 'vue-material-design-icons/AccountTie.vue';\nimport Calendar from 'vue-material-design-icons/Calendar.vue';\nimport ChartLine from 'vue-material-design-icons/ChartLine.vue';\nimport Briefcase from 'vue-material-design-icons/Briefcase.vue';\nimport School from 'vue-material-design-icons/School.vue';\nimport Star from 'vue-material-design-icons/Star.vue';\nimport Heart from 'vue-material-design-icons/Heart.vue';\nimport LightbulbOn from 'vue-material-design-icons/LightbulbOn.vue';\nimport Rocket from 'vue-material-design-icons/Rocket.vue';\nimport RocketLaunch from 'vue-material-design-icons/RocketLaunch.vue';\nimport Archive from 'vue-material-design-icons/Archive.vue';\nimport OpenInNew from 'vue-material-design-icons/OpenInNew.vue';\nimport BookOpen from 'vue-material-design-icons/BookOpen.vue';\nimport HelpCircle from 'vue-material-design-icons/HelpCircle.vue';\nimport HandOkay from 'vue-material-design-icons/HandOkay.vue';\nimport ShieldCheck from 'vue-material-design-icons/ShieldCheck.vue';\nimport OpenSourceInitiative from 'vue-material-design-icons/OpenSourceInitiative.vue';\nimport Translate from 'vue-material-design-icons/Translate.vue';\nimport Responsive from 'vue-material-design-icons/Responsive.vue';\nimport OfficeBuilding from 'vue-material-design-icons/OfficeBuilding.vue';\nimport Newspaper from 'vue-material-design-icons/Newspaper.vue';\nimport Bullhorn from 'vue-material-design-icons/Bullhorn.vue';\nimport Forum from 'vue-material-design-icons/Forum.vue';\nimport Bug from 'vue-material-design-icons/Bug.vue';\nimport Github from 'vue-material-design-icons/Github.vue';\nimport Palette from 'vue-material-design-icons/Palette.vue';\nimport CodeTags from 'vue-material-design-icons/CodeTags.vue';\nimport Lifebuoy from 'vue-material-design-icons/Lifebuoy.vue';\nimport CurrencyUsd from 'vue-material-design-icons/CurrencyUsd.vue';\nimport Folder from 'vue-material-design-icons/Folder.vue';\nimport FolderMultiple from 'vue-material-design-icons/FolderMultiple.vue';\nimport Chat from 'vue-material-design-icons/Chat.vue';\nimport ViewColumn from 'vue-material-design-icons/ViewColumn.vue';\nimport FormSelect from 'vue-material-design-icons/FormSelect.vue';\nimport ClipboardCheck from 'vue-material-design-icons/ClipboardCheck.vue';\nimport ViewDashboard from 'vue-material-design-icons/ViewDashboard.vue';\nimport ImageMultiple from 'vue-material-design-icons/ImageMultiple.vue';\nimport Draw from 'vue-material-design-icons/Draw.vue';\nimport Puzzle from 'vue-material-design-icons/Puzzle.vue';\nimport Robot from 'vue-material-design-icons/Robot.vue';\nimport TableLarge from 'vue-material-design-icons/TableLarge.vue';\nimport BookOpenVariant from 'vue-material-design-icons/BookOpenVariant.vue';\nimport Poll from 'vue-material-design-icons/Poll.vue';\nimport CalendarClock from 'vue-material-design-icons/CalendarClock.vue';\nimport Earth from 'vue-material-design-icons/Earth.vue';\nimport ShieldAccount from 'vue-material-design-icons/ShieldAccount.vue';\nimport Apps from 'vue-material-design-icons/Apps.vue';\nimport Cloud from 'vue-material-design-icons/Cloud.vue';\nimport InformationOutline from 'vue-material-design-icons/InformationOutline.vue';\nimport LinkBoxVariant from 'vue-material-design-icons/LinkBoxVariant.vue';\nimport FormatText from 'vue-material-design-icons/FormatText.vue';\nimport Minus from 'vue-material-design-icons/Minus.vue';\nimport Contacts from 'vue-material-design-icons/Contacts.vue';\nimport MessageText from 'vue-material-design-icons/MessageText.vue';\n\nexport default {\n  name: 'LinksWidget',\n  components: {\n    ViewGrid,\n    Home,\n    Information,\n    Email,\n    Phone,\n    Web,\n    FileDocument,\n    Download,\n    Help,\n    Cog,\n    Account,\n    AccountGroup,\n    AccountMultiple,\n    AccountTie,\n    Calendar,\n    ChartLine,\n    Briefcase,\n    School,\n    Star,\n    Heart,\n    LightbulbOn,\n    Rocket,\n    RocketLaunch,\n    Archive,\n    OpenInNew,\n    BookOpen,\n    HelpCircle,\n    HandOkay,\n    ShieldCheck,\n    OpenSourceInitiative,\n    Translate,\n    Responsive,\n    OfficeBuilding,\n    Newspaper,\n    Bullhorn,\n    Forum,\n    Bug,\n    Github,\n    Palette,\n    CodeTags,\n    Lifebuoy,\n    CurrencyUsd,\n    Folder,\n    FolderMultiple,\n    Chat,\n    ViewColumn,\n    FormSelect,\n    ClipboardCheck,\n    ViewDashboard,\n    ImageMultiple,\n    Draw,\n    Puzzle,\n    Robot,\n    TableLarge,\n    BookOpenVariant,\n    Poll,\n    CalendarClock,\n    Earth,\n    ShieldAccount,\n    Apps,\n    Cloud,\n    InformationOutline,\n    LinkBoxVariant,\n    FormatText,\n    Minus,\n    Contacts,\n    MessageText\n  },\n  props: {\n    widget: {\n      type: Object,\n      required: true\n    },\n    rowBackgroundColor: {\n      type: String,\n      default: ''\n    }\n  },\n  emits: ['navigate'],\n  computed: {\n    effectiveBackgroundColor() {\n      return getEffectiveBackgroundColor(this.widget.backgroundColor, this.rowBackgroundColor);\n    },\n    isTileLayout() {\n      return this.widget.layout === 'tiles';\n    },\n  },\n  methods: {\n    t(key, vars = {}) {\n      return t('intravox', key, vars);\n    },\n    sanitizeHtml(content) {\n      if (!content) return '';\n      // Use the markdown serializer to convert markdown to HTML\n      return markdownToHtml(content);\n    },\n    getLinkItemClass(link) {\n      // Individual link background takes precedence\n      const linkBg = link.backgroundColor;\n      if (linkBg) {\n        if (isDarkBackground(linkBg)) {\n          return 'link-item--bg-dark';\n        }\n        return 'link-item--bg-light';\n      }\n\n      // No individual link background → blend into container/row background\n      const containerBg = this.effectiveBackgroundColor;\n\n      if (isDarkBackground(containerBg)) {\n        // Dark container → transparent links with white text\n        return 'link-item--bg-on-dark';\n      }\n\n      // Default: transparent links that blend into their background\n      return 'link-item--bg-transparent';\n    },\n    getIconComponent(iconName) {\n      const iconMap = {\n        'home': 'Home',\n        'information': 'Information',\n        'email': 'Email',\n        'phone': 'Phone',\n        'web': 'Web',\n        'file-document': 'FileDocument',\n        'download': 'Download',\n        'help': 'Help',\n        'help-circle': 'HelpCircle',\n        'cog': 'Cog',\n        'account': 'Account',\n        'account-group': 'AccountGroup',\n        'account-multiple': 'AccountMultiple',\n        'account-tie': 'AccountTie',\n        'calendar': 'Calendar',\n        'chart-line': 'ChartLine',\n        'briefcase': 'Briefcase',\n        'school': 'School',\n        'star': 'Star',\n        'heart': 'Heart',\n        'lightbulb-on': 'LightbulbOn',\n        'rocket': 'Rocket',\n        'rocket-launch': 'RocketLaunch',\n        'archive': 'Archive',\n        'open-in-new': 'OpenInNew',\n        'book-open': 'BookOpen',\n        'hand-okay': 'HandOkay',\n        'shield-check': 'ShieldCheck',\n        'open-source-initiative': 'OpenSourceInitiative',\n        'translate': 'Translate',\n        'responsive': 'Responsive',\n        'office-building': 'OfficeBuilding',\n        'newspaper': 'Newspaper',\n        'bullhorn': 'Bullhorn',\n        'forum': 'Forum',\n        'bug': 'Bug',\n        'github': 'Github',\n        'palette': 'Palette',\n        'code-tags': 'CodeTags',\n        'lifebuoy': 'Lifebuoy',\n        'currency-usd': 'CurrencyUsd',\n        'folder': 'Folder',\n        'folder-multiple': 'FolderMultiple',\n        'chat': 'Chat',\n        'view-column': 'ViewColumn',\n        'form-select': 'FormSelect',\n        'clipboard-check': 'ClipboardCheck',\n        'view-dashboard': 'ViewDashboard',\n        'image-multiple': 'ImageMultiple',\n        'draw': 'Draw',\n        'puzzle': 'Puzzle',\n        'robot': 'Robot',\n        'table-large': 'TableLarge',\n        'book-open-variant': 'BookOpenVariant',\n        'poll': 'Poll',\n        'calendar-clock': 'CalendarClock',\n        'earth': 'Earth',\n        'shield-account': 'ShieldAccount',\n        'apps': 'Apps',\n        'cloud': 'Cloud',\n        'information-outline': 'InformationOutline',\n        'link-box-variant': 'LinkBoxVariant',\n        'format-text': 'FormatText',\n        'minus': 'Minus',\n        'contacts': 'Contacts',\n        'message-text': 'MessageText'\n      };\n      return iconMap[iconName] || 'OpenInNew';\n    },\n    getContainerStyle() {\n      const style = {};\n      if (this.widget.backgroundColor) {\n        style.backgroundColor = this.widget.backgroundColor;\n        style.padding = '20px';\n        style.borderRadius = 'var(--border-radius-large)';\n      }\n      return style;\n    },\n    getGridStyle() {\n      const columns = this.widget.columns || 2;\n      const minWidth = Math.floor(100 / columns);\n      return {\n        gridTemplateColumns: `repeat(auto-fill, minmax(max(100px, calc(${minWidth}% - 12px)), 1fr))`\n      };\n    },\n    getLinkStyle(link) {\n      const style = {};\n      if (link.backgroundColor) {\n        style.backgroundColor = link.backgroundColor;\n      }\n      return style;\n    },\n    getLinkUrl(link) {\n      // If link has a uniqueId (internal page), return hash URL\n      if (link.uniqueId) {\n        return `#${link.uniqueId}`;\n      }\n      // Otherwise return the external URL\n      return link.url || '#';\n    },\n    isInternalLink(link) {\n      // Check if it's an internal navigation link (has uniqueId or URL starts with #)\n      if (link.uniqueId) {\n        return true;\n      }\n      return link.url && link.url.startsWith('#') && link.url.length > 1;\n    },\n    handleLinkClick(event, link) {\n      // If it's an internal link, prevent default and emit navigate event\n      if (this.isInternalLink(link)) {\n        event.preventDefault();\n        event.stopPropagation();\n        // Get the pageId from uniqueId or from URL hash\n        const pageId = link.uniqueId || link.url.substring(1);\n        this.$emit('navigate', pageId);\n      }\n      // For external links, the default behavior (open in new tab) will happen\n    }\n  }\n};\n</script>\n\n<style scoped>\n.links-widget {\n  width: 100%;\n  margin: 12px 0;\n}\n\n.links-grid {\n  display: grid;\n  gap: 12px;\n  grid-template-columns: repeat(2, 1fr);\n}\n\n/* Base link-item styles */\n.link-item {\n  display: flex;\n  align-items: center;\n  gap: 12px;\n  padding: 16px;\n  min-width: 0;\n  border: 1px solid var(--color-border);\n  border-radius: var(--border-radius-container-large);\n  color: var(--color-main-text);\n  text-decoration: none;\n  transition: background 0.2s, border-color 0.2s, box-shadow 0.2s;\n  cursor: pointer;\n}\n\n.link-icon {\n  flex-shrink: 0;\n  color: var(--color-primary-element);\n}\n\n/* Transparent — default: blends into container/row background */\n.link-item--bg-transparent {\n  background: transparent;\n}\n\n.link-item--bg-transparent:hover {\n  background: rgba(0, 0, 0, 0.04);\n  border-color: var(--color-primary-element);\n  box-shadow: 0 2px 6px rgba(0, 0, 0, 0.06);\n}\n\n/* On dark container — transparent links with white text/icons */\n.link-item--bg-on-dark {\n  background: transparent;\n  border-color: rgba(255, 255, 255, 0.2);\n  color: var(--color-primary-element-text);\n}\n\n.link-item--bg-on-dark .link-icon {\n  color: rgba(255, 255, 255, 0.85);\n}\n\n.link-item--bg-on-dark:hover {\n  background: rgba(255, 255, 255, 0.12);\n  border-color: rgba(255, 255, 255, 0.4);\n  color: var(--color-primary-element-text);\n  box-shadow: 0 2px 6px rgba(0, 0, 0, 0.15);\n}\n\n.link-item--bg-on-dark:hover .link-icon {\n  color: var(--color-primary-element-text);\n}\n\n/* Explicit light background — individual link set to Light */\n.link-item--bg-light {\n  background: var(--color-background-hover);\n  border-color: var(--color-border);\n  color: var(--color-main-text);\n}\n\n.link-item--bg-light .link-icon {\n  color: var(--color-primary-element);\n}\n\n.link-item--bg-light:hover {\n  background: var(--color-primary-element-light);\n  border-color: var(--color-primary-element);\n  box-shadow: 0 2px 6px rgba(0, 0, 0, 0.08);\n}\n\n/* Explicit dark background — individual link set to Primary */\n.link-item--bg-dark {\n  background: var(--color-primary-element);\n  border-color: var(--color-primary-element);\n  color: var(--color-primary-element-text);\n}\n\n.link-item--bg-dark .link-icon {\n  color: rgba(255, 255, 255, 0.85);\n}\n\n.link-item--bg-dark:hover {\n  filter: brightness(1.1);\n  box-shadow: 0 2px 6px rgba(0, 0, 0, 0.15);\n}\n\n.link-item--bg-dark:hover .link-icon {\n  color: var(--color-primary-element-text);\n}\n\n/* Tile layout — centered icon above text */\n.link-item--tile {\n  flex-direction: column;\n  text-align: center;\n  padding: 20px 12px;\n  min-height: 100px;\n  justify-content: center;\n  gap: 8px;\n}\n\n.link-item--tile .link-icon {\n  margin-bottom: 4px;\n}\n\n.link-tile-content {\n  display: flex;\n  flex-direction: column;\n  gap: 2px;\n}\n\n.link-tile-title {\n  font-weight: 600;\n  font-size: 14px;\n  overflow-wrap: break-word;\n  word-break: break-word;\n}\n\n.link-tile-subtitle {\n  font-size: 12px;\n  opacity: 0.7;\n  overflow-wrap: break-word;\n  word-break: break-word;\n}\n\n.link-text {\n  font-weight: 500;\n  font-size: 14px;\n  min-width: 0;\n  overflow-wrap: break-word;\n  word-break: break-word;\n}\n\n/* Empty placeholder */\n.empty-links-placeholder {\n  text-align: center;\n  padding: 40px 20px;\n  background: var(--color-background-hover);\n  border: 2px dashed var(--color-border);\n  border-radius: var(--border-radius-large);\n  color: var(--color-text-maxcontrast);\n}\n\n.empty-links-placeholder svg {\n  margin-bottom: 10px;\n  color: var(--color-text-maxcontrast);\n}\n\n.empty-links-placeholder p {\n  margin: 0;\n  font-size: 14px;\n}\n\n/* Mobile responsiveness */\n@media (max-width: 768px) {\n  .links-grid {\n    grid-template-columns: 1fr !important;\n  }\n\n  .links-grid:has(.link-item--tile) {\n    grid-template-columns: repeat(2, 1fr) !important;\n  }\n\n  .link-item {\n    padding: 14px;\n  }\n\n  .link-item--tile {\n    padding: 16px 8px;\n    min-height: 80px;\n  }\n}\n</style>\n"],"sourceRoot":""}]);
// Exports
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (___CSS_LOADER_EXPORT___);


/***/ },

/***/ "./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/dist/stylePostLoader.js!./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/LinksWidget.vue?vue&type=style&index=0&id=79936edd&scoped=true&lang=css"
/*!****************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/dist/stylePostLoader.js!./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/LinksWidget.vue?vue&type=style&index=0&id=79936edd&scoped=true&lang=css ***!
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
/* harmony import */ var _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_dist_stylePostLoader_js_node_modules_vue_loader_dist_index_js_ruleSet_1_rules_5_use_0_LinksWidget_vue_vue_type_style_index_0_id_79936edd_scoped_true_lang_css__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! !!../../node_modules/css-loader/dist/cjs.js!../../node_modules/vue-loader/dist/stylePostLoader.js!../../node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./LinksWidget.vue?vue&type=style&index=0&id=79936edd&scoped=true&lang=css */ "./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/dist/stylePostLoader.js!./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/LinksWidget.vue?vue&type=style&index=0&id=79936edd&scoped=true&lang=css");

      
      
      
      
      
      
      
      
      

var options = {};

options.styleTagTransform = (_node_modules_style_loader_dist_runtime_styleTagTransform_js__WEBPACK_IMPORTED_MODULE_5___default());
options.setAttributes = (_node_modules_style_loader_dist_runtime_setAttributesWithoutAttributes_js__WEBPACK_IMPORTED_MODULE_3___default());

      options.insert = _node_modules_style_loader_dist_runtime_insertBySelector_js__WEBPACK_IMPORTED_MODULE_2___default().bind(null, "head");
    
options.domAPI = (_node_modules_style_loader_dist_runtime_styleDomAPI_js__WEBPACK_IMPORTED_MODULE_1___default());
options.insertStyleElement = (_node_modules_style_loader_dist_runtime_insertStyleElement_js__WEBPACK_IMPORTED_MODULE_4___default());

var update = _node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0___default()(_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_dist_stylePostLoader_js_node_modules_vue_loader_dist_index_js_ruleSet_1_rules_5_use_0_LinksWidget_vue_vue_type_style_index_0_id_79936edd_scoped_true_lang_css__WEBPACK_IMPORTED_MODULE_6__["default"], options);




       /* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_dist_stylePostLoader_js_node_modules_vue_loader_dist_index_js_ruleSet_1_rules_5_use_0_LinksWidget_vue_vue_type_style_index_0_id_79936edd_scoped_true_lang_css__WEBPACK_IMPORTED_MODULE_6__["default"] && _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_dist_stylePostLoader_js_node_modules_vue_loader_dist_index_js_ruleSet_1_rules_5_use_0_LinksWidget_vue_vue_type_style_index_0_id_79936edd_scoped_true_lang_css__WEBPACK_IMPORTED_MODULE_6__["default"].locals ? _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_dist_stylePostLoader_js_node_modules_vue_loader_dist_index_js_ruleSet_1_rules_5_use_0_LinksWidget_vue_vue_type_style_index_0_id_79936edd_scoped_true_lang_css__WEBPACK_IMPORTED_MODULE_6__["default"].locals : undefined);


/***/ },

/***/ "./src/components/LinksWidget.vue"
/*!****************************************!*\
  !*** ./src/components/LinksWidget.vue ***!
  \****************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _LinksWidget_vue_vue_type_template_id_79936edd_scoped_true__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./LinksWidget.vue?vue&type=template&id=79936edd&scoped=true */ "./src/components/LinksWidget.vue?vue&type=template&id=79936edd&scoped=true");
/* harmony import */ var _LinksWidget_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./LinksWidget.vue?vue&type=script&lang=js */ "./src/components/LinksWidget.vue?vue&type=script&lang=js");
/* harmony import */ var _LinksWidget_vue_vue_type_style_index_0_id_79936edd_scoped_true_lang_css__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./LinksWidget.vue?vue&type=style&index=0&id=79936edd&scoped=true&lang=css */ "./src/components/LinksWidget.vue?vue&type=style&index=0&id=79936edd&scoped=true&lang=css");
/* harmony import */ var _node_modules_vue_loader_dist_exportHelper_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ../../node_modules/vue-loader/dist/exportHelper.js */ "./node_modules/vue-loader/dist/exportHelper.js");




;


const __exports__ = /*#__PURE__*/(0,_node_modules_vue_loader_dist_exportHelper_js__WEBPACK_IMPORTED_MODULE_3__["default"])(_LinksWidget_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_1__["default"], [['render',_LinksWidget_vue_vue_type_template_id_79936edd_scoped_true__WEBPACK_IMPORTED_MODULE_0__.render],['__scopeId',"data-v-79936edd"],['__file',"src/components/LinksWidget.vue"]])
/* hot reload */
if (false) // removed by dead control flow
{}


/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (__exports__);

/***/ },

/***/ "./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/LinksWidget.vue?vue&type=script&lang=js"
/*!************************************************************************************************************************************!*\
  !*** ./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/LinksWidget.vue?vue&type=script&lang=js ***!
  \************************************************************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _nextcloud_l10n__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @nextcloud/l10n */ "./node_modules/@nextcloud/l10n/dist/index.mjs");
/* harmony import */ var _utils_markdownSerializer_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ../utils/markdownSerializer.js */ "./src/utils/markdownSerializer.js");
/* harmony import */ var _utils_colorUtils_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ../utils/colorUtils.js */ "./src/utils/colorUtils.js");
/* harmony import */ var vue_material_design_icons_ViewGrid_vue__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! vue-material-design-icons/ViewGrid.vue */ "./node_modules/vue-material-design-icons/ViewGrid.vue");
/* harmony import */ var vue_material_design_icons_Home_vue__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! vue-material-design-icons/Home.vue */ "./node_modules/vue-material-design-icons/Home.vue");
/* harmony import */ var vue_material_design_icons_Information_vue__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! vue-material-design-icons/Information.vue */ "./node_modules/vue-material-design-icons/Information.vue");
/* harmony import */ var vue_material_design_icons_Email_vue__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! vue-material-design-icons/Email.vue */ "./node_modules/vue-material-design-icons/Email.vue");
/* harmony import */ var vue_material_design_icons_Phone_vue__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! vue-material-design-icons/Phone.vue */ "./node_modules/vue-material-design-icons/Phone.vue");
/* harmony import */ var vue_material_design_icons_Web_vue__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__(/*! vue-material-design-icons/Web.vue */ "./node_modules/vue-material-design-icons/Web.vue");
/* harmony import */ var vue_material_design_icons_FileDocument_vue__WEBPACK_IMPORTED_MODULE_9__ = __webpack_require__(/*! vue-material-design-icons/FileDocument.vue */ "./node_modules/vue-material-design-icons/FileDocument.vue");
/* harmony import */ var vue_material_design_icons_Download_vue__WEBPACK_IMPORTED_MODULE_10__ = __webpack_require__(/*! vue-material-design-icons/Download.vue */ "./node_modules/vue-material-design-icons/Download.vue");
/* harmony import */ var vue_material_design_icons_Help_vue__WEBPACK_IMPORTED_MODULE_11__ = __webpack_require__(/*! vue-material-design-icons/Help.vue */ "./node_modules/vue-material-design-icons/Help.vue");
/* harmony import */ var vue_material_design_icons_Cog_vue__WEBPACK_IMPORTED_MODULE_12__ = __webpack_require__(/*! vue-material-design-icons/Cog.vue */ "./node_modules/vue-material-design-icons/Cog.vue");
/* harmony import */ var vue_material_design_icons_Account_vue__WEBPACK_IMPORTED_MODULE_13__ = __webpack_require__(/*! vue-material-design-icons/Account.vue */ "./node_modules/vue-material-design-icons/Account.vue");
/* harmony import */ var vue_material_design_icons_AccountGroup_vue__WEBPACK_IMPORTED_MODULE_14__ = __webpack_require__(/*! vue-material-design-icons/AccountGroup.vue */ "./node_modules/vue-material-design-icons/AccountGroup.vue");
/* harmony import */ var vue_material_design_icons_AccountMultiple_vue__WEBPACK_IMPORTED_MODULE_15__ = __webpack_require__(/*! vue-material-design-icons/AccountMultiple.vue */ "./node_modules/vue-material-design-icons/AccountMultiple.vue");
/* harmony import */ var vue_material_design_icons_AccountTie_vue__WEBPACK_IMPORTED_MODULE_16__ = __webpack_require__(/*! vue-material-design-icons/AccountTie.vue */ "./node_modules/vue-material-design-icons/AccountTie.vue");
/* harmony import */ var vue_material_design_icons_Calendar_vue__WEBPACK_IMPORTED_MODULE_17__ = __webpack_require__(/*! vue-material-design-icons/Calendar.vue */ "./node_modules/vue-material-design-icons/Calendar.vue");
/* harmony import */ var vue_material_design_icons_ChartLine_vue__WEBPACK_IMPORTED_MODULE_18__ = __webpack_require__(/*! vue-material-design-icons/ChartLine.vue */ "./node_modules/vue-material-design-icons/ChartLine.vue");
/* harmony import */ var vue_material_design_icons_Briefcase_vue__WEBPACK_IMPORTED_MODULE_19__ = __webpack_require__(/*! vue-material-design-icons/Briefcase.vue */ "./node_modules/vue-material-design-icons/Briefcase.vue");
/* harmony import */ var vue_material_design_icons_School_vue__WEBPACK_IMPORTED_MODULE_20__ = __webpack_require__(/*! vue-material-design-icons/School.vue */ "./node_modules/vue-material-design-icons/School.vue");
/* harmony import */ var vue_material_design_icons_Star_vue__WEBPACK_IMPORTED_MODULE_21__ = __webpack_require__(/*! vue-material-design-icons/Star.vue */ "./node_modules/vue-material-design-icons/Star.vue");
/* harmony import */ var vue_material_design_icons_Heart_vue__WEBPACK_IMPORTED_MODULE_22__ = __webpack_require__(/*! vue-material-design-icons/Heart.vue */ "./node_modules/vue-material-design-icons/Heart.vue");
/* harmony import */ var vue_material_design_icons_LightbulbOn_vue__WEBPACK_IMPORTED_MODULE_23__ = __webpack_require__(/*! vue-material-design-icons/LightbulbOn.vue */ "./node_modules/vue-material-design-icons/LightbulbOn.vue");
/* harmony import */ var vue_material_design_icons_Rocket_vue__WEBPACK_IMPORTED_MODULE_24__ = __webpack_require__(/*! vue-material-design-icons/Rocket.vue */ "./node_modules/vue-material-design-icons/Rocket.vue");
/* harmony import */ var vue_material_design_icons_RocketLaunch_vue__WEBPACK_IMPORTED_MODULE_25__ = __webpack_require__(/*! vue-material-design-icons/RocketLaunch.vue */ "./node_modules/vue-material-design-icons/RocketLaunch.vue");
/* harmony import */ var vue_material_design_icons_Archive_vue__WEBPACK_IMPORTED_MODULE_26__ = __webpack_require__(/*! vue-material-design-icons/Archive.vue */ "./node_modules/vue-material-design-icons/Archive.vue");
/* harmony import */ var vue_material_design_icons_OpenInNew_vue__WEBPACK_IMPORTED_MODULE_27__ = __webpack_require__(/*! vue-material-design-icons/OpenInNew.vue */ "./node_modules/vue-material-design-icons/OpenInNew.vue");
/* harmony import */ var vue_material_design_icons_BookOpen_vue__WEBPACK_IMPORTED_MODULE_28__ = __webpack_require__(/*! vue-material-design-icons/BookOpen.vue */ "./node_modules/vue-material-design-icons/BookOpen.vue");
/* harmony import */ var vue_material_design_icons_HelpCircle_vue__WEBPACK_IMPORTED_MODULE_29__ = __webpack_require__(/*! vue-material-design-icons/HelpCircle.vue */ "./node_modules/vue-material-design-icons/HelpCircle.vue");
/* harmony import */ var vue_material_design_icons_HandOkay_vue__WEBPACK_IMPORTED_MODULE_30__ = __webpack_require__(/*! vue-material-design-icons/HandOkay.vue */ "./node_modules/vue-material-design-icons/HandOkay.vue");
/* harmony import */ var vue_material_design_icons_ShieldCheck_vue__WEBPACK_IMPORTED_MODULE_31__ = __webpack_require__(/*! vue-material-design-icons/ShieldCheck.vue */ "./node_modules/vue-material-design-icons/ShieldCheck.vue");
/* harmony import */ var vue_material_design_icons_OpenSourceInitiative_vue__WEBPACK_IMPORTED_MODULE_32__ = __webpack_require__(/*! vue-material-design-icons/OpenSourceInitiative.vue */ "./node_modules/vue-material-design-icons/OpenSourceInitiative.vue");
/* harmony import */ var vue_material_design_icons_Translate_vue__WEBPACK_IMPORTED_MODULE_33__ = __webpack_require__(/*! vue-material-design-icons/Translate.vue */ "./node_modules/vue-material-design-icons/Translate.vue");
/* harmony import */ var vue_material_design_icons_Responsive_vue__WEBPACK_IMPORTED_MODULE_34__ = __webpack_require__(/*! vue-material-design-icons/Responsive.vue */ "./node_modules/vue-material-design-icons/Responsive.vue");
/* harmony import */ var vue_material_design_icons_OfficeBuilding_vue__WEBPACK_IMPORTED_MODULE_35__ = __webpack_require__(/*! vue-material-design-icons/OfficeBuilding.vue */ "./node_modules/vue-material-design-icons/OfficeBuilding.vue");
/* harmony import */ var vue_material_design_icons_Newspaper_vue__WEBPACK_IMPORTED_MODULE_36__ = __webpack_require__(/*! vue-material-design-icons/Newspaper.vue */ "./node_modules/vue-material-design-icons/Newspaper.vue");
/* harmony import */ var vue_material_design_icons_Bullhorn_vue__WEBPACK_IMPORTED_MODULE_37__ = __webpack_require__(/*! vue-material-design-icons/Bullhorn.vue */ "./node_modules/vue-material-design-icons/Bullhorn.vue");
/* harmony import */ var vue_material_design_icons_Forum_vue__WEBPACK_IMPORTED_MODULE_38__ = __webpack_require__(/*! vue-material-design-icons/Forum.vue */ "./node_modules/vue-material-design-icons/Forum.vue");
/* harmony import */ var vue_material_design_icons_Bug_vue__WEBPACK_IMPORTED_MODULE_39__ = __webpack_require__(/*! vue-material-design-icons/Bug.vue */ "./node_modules/vue-material-design-icons/Bug.vue");
/* harmony import */ var vue_material_design_icons_Github_vue__WEBPACK_IMPORTED_MODULE_40__ = __webpack_require__(/*! vue-material-design-icons/Github.vue */ "./node_modules/vue-material-design-icons/Github.vue");
/* harmony import */ var vue_material_design_icons_Palette_vue__WEBPACK_IMPORTED_MODULE_41__ = __webpack_require__(/*! vue-material-design-icons/Palette.vue */ "./node_modules/vue-material-design-icons/Palette.vue");
/* harmony import */ var vue_material_design_icons_CodeTags_vue__WEBPACK_IMPORTED_MODULE_42__ = __webpack_require__(/*! vue-material-design-icons/CodeTags.vue */ "./node_modules/vue-material-design-icons/CodeTags.vue");
/* harmony import */ var vue_material_design_icons_Lifebuoy_vue__WEBPACK_IMPORTED_MODULE_43__ = __webpack_require__(/*! vue-material-design-icons/Lifebuoy.vue */ "./node_modules/vue-material-design-icons/Lifebuoy.vue");
/* harmony import */ var vue_material_design_icons_CurrencyUsd_vue__WEBPACK_IMPORTED_MODULE_44__ = __webpack_require__(/*! vue-material-design-icons/CurrencyUsd.vue */ "./node_modules/vue-material-design-icons/CurrencyUsd.vue");
/* harmony import */ var vue_material_design_icons_Folder_vue__WEBPACK_IMPORTED_MODULE_45__ = __webpack_require__(/*! vue-material-design-icons/Folder.vue */ "./node_modules/vue-material-design-icons/Folder.vue");
/* harmony import */ var vue_material_design_icons_FolderMultiple_vue__WEBPACK_IMPORTED_MODULE_46__ = __webpack_require__(/*! vue-material-design-icons/FolderMultiple.vue */ "./node_modules/vue-material-design-icons/FolderMultiple.vue");
/* harmony import */ var vue_material_design_icons_Chat_vue__WEBPACK_IMPORTED_MODULE_47__ = __webpack_require__(/*! vue-material-design-icons/Chat.vue */ "./node_modules/vue-material-design-icons/Chat.vue");
/* harmony import */ var vue_material_design_icons_ViewColumn_vue__WEBPACK_IMPORTED_MODULE_48__ = __webpack_require__(/*! vue-material-design-icons/ViewColumn.vue */ "./node_modules/vue-material-design-icons/ViewColumn.vue");
/* harmony import */ var vue_material_design_icons_FormSelect_vue__WEBPACK_IMPORTED_MODULE_49__ = __webpack_require__(/*! vue-material-design-icons/FormSelect.vue */ "./node_modules/vue-material-design-icons/FormSelect.vue");
/* harmony import */ var vue_material_design_icons_ClipboardCheck_vue__WEBPACK_IMPORTED_MODULE_50__ = __webpack_require__(/*! vue-material-design-icons/ClipboardCheck.vue */ "./node_modules/vue-material-design-icons/ClipboardCheck.vue");
/* harmony import */ var vue_material_design_icons_ViewDashboard_vue__WEBPACK_IMPORTED_MODULE_51__ = __webpack_require__(/*! vue-material-design-icons/ViewDashboard.vue */ "./node_modules/vue-material-design-icons/ViewDashboard.vue");
/* harmony import */ var vue_material_design_icons_ImageMultiple_vue__WEBPACK_IMPORTED_MODULE_52__ = __webpack_require__(/*! vue-material-design-icons/ImageMultiple.vue */ "./node_modules/vue-material-design-icons/ImageMultiple.vue");
/* harmony import */ var vue_material_design_icons_Draw_vue__WEBPACK_IMPORTED_MODULE_53__ = __webpack_require__(/*! vue-material-design-icons/Draw.vue */ "./node_modules/vue-material-design-icons/Draw.vue");
/* harmony import */ var vue_material_design_icons_Puzzle_vue__WEBPACK_IMPORTED_MODULE_54__ = __webpack_require__(/*! vue-material-design-icons/Puzzle.vue */ "./node_modules/vue-material-design-icons/Puzzle.vue");
/* harmony import */ var vue_material_design_icons_Robot_vue__WEBPACK_IMPORTED_MODULE_55__ = __webpack_require__(/*! vue-material-design-icons/Robot.vue */ "./node_modules/vue-material-design-icons/Robot.vue");
/* harmony import */ var vue_material_design_icons_TableLarge_vue__WEBPACK_IMPORTED_MODULE_56__ = __webpack_require__(/*! vue-material-design-icons/TableLarge.vue */ "./node_modules/vue-material-design-icons/TableLarge.vue");
/* harmony import */ var vue_material_design_icons_BookOpenVariant_vue__WEBPACK_IMPORTED_MODULE_57__ = __webpack_require__(/*! vue-material-design-icons/BookOpenVariant.vue */ "./node_modules/vue-material-design-icons/BookOpenVariant.vue");
/* harmony import */ var vue_material_design_icons_Poll_vue__WEBPACK_IMPORTED_MODULE_58__ = __webpack_require__(/*! vue-material-design-icons/Poll.vue */ "./node_modules/vue-material-design-icons/Poll.vue");
/* harmony import */ var vue_material_design_icons_CalendarClock_vue__WEBPACK_IMPORTED_MODULE_59__ = __webpack_require__(/*! vue-material-design-icons/CalendarClock.vue */ "./node_modules/vue-material-design-icons/CalendarClock.vue");
/* harmony import */ var vue_material_design_icons_Earth_vue__WEBPACK_IMPORTED_MODULE_60__ = __webpack_require__(/*! vue-material-design-icons/Earth.vue */ "./node_modules/vue-material-design-icons/Earth.vue");
/* harmony import */ var vue_material_design_icons_ShieldAccount_vue__WEBPACK_IMPORTED_MODULE_61__ = __webpack_require__(/*! vue-material-design-icons/ShieldAccount.vue */ "./node_modules/vue-material-design-icons/ShieldAccount.vue");
/* harmony import */ var vue_material_design_icons_Apps_vue__WEBPACK_IMPORTED_MODULE_62__ = __webpack_require__(/*! vue-material-design-icons/Apps.vue */ "./node_modules/vue-material-design-icons/Apps.vue");
/* harmony import */ var vue_material_design_icons_Cloud_vue__WEBPACK_IMPORTED_MODULE_63__ = __webpack_require__(/*! vue-material-design-icons/Cloud.vue */ "./node_modules/vue-material-design-icons/Cloud.vue");
/* harmony import */ var vue_material_design_icons_InformationOutline_vue__WEBPACK_IMPORTED_MODULE_64__ = __webpack_require__(/*! vue-material-design-icons/InformationOutline.vue */ "./node_modules/vue-material-design-icons/InformationOutline.vue");
/* harmony import */ var vue_material_design_icons_LinkBoxVariant_vue__WEBPACK_IMPORTED_MODULE_65__ = __webpack_require__(/*! vue-material-design-icons/LinkBoxVariant.vue */ "./node_modules/vue-material-design-icons/LinkBoxVariant.vue");
/* harmony import */ var vue_material_design_icons_FormatText_vue__WEBPACK_IMPORTED_MODULE_66__ = __webpack_require__(/*! vue-material-design-icons/FormatText.vue */ "./node_modules/vue-material-design-icons/FormatText.vue");
/* harmony import */ var vue_material_design_icons_Minus_vue__WEBPACK_IMPORTED_MODULE_67__ = __webpack_require__(/*! vue-material-design-icons/Minus.vue */ "./node_modules/vue-material-design-icons/Minus.vue");
/* harmony import */ var vue_material_design_icons_Contacts_vue__WEBPACK_IMPORTED_MODULE_68__ = __webpack_require__(/*! vue-material-design-icons/Contacts.vue */ "./node_modules/vue-material-design-icons/Contacts.vue");
/* harmony import */ var vue_material_design_icons_MessageText_vue__WEBPACK_IMPORTED_MODULE_69__ = __webpack_require__(/*! vue-material-design-icons/MessageText.vue */ "./node_modules/vue-material-design-icons/MessageText.vue");





// Import common Material Design Icons




































































/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = ({
  name: 'LinksWidget',
  components: {
    ViewGrid: vue_material_design_icons_ViewGrid_vue__WEBPACK_IMPORTED_MODULE_3__["default"],
    Home: vue_material_design_icons_Home_vue__WEBPACK_IMPORTED_MODULE_4__["default"],
    Information: vue_material_design_icons_Information_vue__WEBPACK_IMPORTED_MODULE_5__["default"],
    Email: vue_material_design_icons_Email_vue__WEBPACK_IMPORTED_MODULE_6__["default"],
    Phone: vue_material_design_icons_Phone_vue__WEBPACK_IMPORTED_MODULE_7__["default"],
    Web: vue_material_design_icons_Web_vue__WEBPACK_IMPORTED_MODULE_8__["default"],
    FileDocument: vue_material_design_icons_FileDocument_vue__WEBPACK_IMPORTED_MODULE_9__["default"],
    Download: vue_material_design_icons_Download_vue__WEBPACK_IMPORTED_MODULE_10__["default"],
    Help: vue_material_design_icons_Help_vue__WEBPACK_IMPORTED_MODULE_11__["default"],
    Cog: vue_material_design_icons_Cog_vue__WEBPACK_IMPORTED_MODULE_12__["default"],
    Account: vue_material_design_icons_Account_vue__WEBPACK_IMPORTED_MODULE_13__["default"],
    AccountGroup: vue_material_design_icons_AccountGroup_vue__WEBPACK_IMPORTED_MODULE_14__["default"],
    AccountMultiple: vue_material_design_icons_AccountMultiple_vue__WEBPACK_IMPORTED_MODULE_15__["default"],
    AccountTie: vue_material_design_icons_AccountTie_vue__WEBPACK_IMPORTED_MODULE_16__["default"],
    Calendar: vue_material_design_icons_Calendar_vue__WEBPACK_IMPORTED_MODULE_17__["default"],
    ChartLine: vue_material_design_icons_ChartLine_vue__WEBPACK_IMPORTED_MODULE_18__["default"],
    Briefcase: vue_material_design_icons_Briefcase_vue__WEBPACK_IMPORTED_MODULE_19__["default"],
    School: vue_material_design_icons_School_vue__WEBPACK_IMPORTED_MODULE_20__["default"],
    Star: vue_material_design_icons_Star_vue__WEBPACK_IMPORTED_MODULE_21__["default"],
    Heart: vue_material_design_icons_Heart_vue__WEBPACK_IMPORTED_MODULE_22__["default"],
    LightbulbOn: vue_material_design_icons_LightbulbOn_vue__WEBPACK_IMPORTED_MODULE_23__["default"],
    Rocket: vue_material_design_icons_Rocket_vue__WEBPACK_IMPORTED_MODULE_24__["default"],
    RocketLaunch: vue_material_design_icons_RocketLaunch_vue__WEBPACK_IMPORTED_MODULE_25__["default"],
    Archive: vue_material_design_icons_Archive_vue__WEBPACK_IMPORTED_MODULE_26__["default"],
    OpenInNew: vue_material_design_icons_OpenInNew_vue__WEBPACK_IMPORTED_MODULE_27__["default"],
    BookOpen: vue_material_design_icons_BookOpen_vue__WEBPACK_IMPORTED_MODULE_28__["default"],
    HelpCircle: vue_material_design_icons_HelpCircle_vue__WEBPACK_IMPORTED_MODULE_29__["default"],
    HandOkay: vue_material_design_icons_HandOkay_vue__WEBPACK_IMPORTED_MODULE_30__["default"],
    ShieldCheck: vue_material_design_icons_ShieldCheck_vue__WEBPACK_IMPORTED_MODULE_31__["default"],
    OpenSourceInitiative: vue_material_design_icons_OpenSourceInitiative_vue__WEBPACK_IMPORTED_MODULE_32__["default"],
    Translate: vue_material_design_icons_Translate_vue__WEBPACK_IMPORTED_MODULE_33__["default"],
    Responsive: vue_material_design_icons_Responsive_vue__WEBPACK_IMPORTED_MODULE_34__["default"],
    OfficeBuilding: vue_material_design_icons_OfficeBuilding_vue__WEBPACK_IMPORTED_MODULE_35__["default"],
    Newspaper: vue_material_design_icons_Newspaper_vue__WEBPACK_IMPORTED_MODULE_36__["default"],
    Bullhorn: vue_material_design_icons_Bullhorn_vue__WEBPACK_IMPORTED_MODULE_37__["default"],
    Forum: vue_material_design_icons_Forum_vue__WEBPACK_IMPORTED_MODULE_38__["default"],
    Bug: vue_material_design_icons_Bug_vue__WEBPACK_IMPORTED_MODULE_39__["default"],
    Github: vue_material_design_icons_Github_vue__WEBPACK_IMPORTED_MODULE_40__["default"],
    Palette: vue_material_design_icons_Palette_vue__WEBPACK_IMPORTED_MODULE_41__["default"],
    CodeTags: vue_material_design_icons_CodeTags_vue__WEBPACK_IMPORTED_MODULE_42__["default"],
    Lifebuoy: vue_material_design_icons_Lifebuoy_vue__WEBPACK_IMPORTED_MODULE_43__["default"],
    CurrencyUsd: vue_material_design_icons_CurrencyUsd_vue__WEBPACK_IMPORTED_MODULE_44__["default"],
    Folder: vue_material_design_icons_Folder_vue__WEBPACK_IMPORTED_MODULE_45__["default"],
    FolderMultiple: vue_material_design_icons_FolderMultiple_vue__WEBPACK_IMPORTED_MODULE_46__["default"],
    Chat: vue_material_design_icons_Chat_vue__WEBPACK_IMPORTED_MODULE_47__["default"],
    ViewColumn: vue_material_design_icons_ViewColumn_vue__WEBPACK_IMPORTED_MODULE_48__["default"],
    FormSelect: vue_material_design_icons_FormSelect_vue__WEBPACK_IMPORTED_MODULE_49__["default"],
    ClipboardCheck: vue_material_design_icons_ClipboardCheck_vue__WEBPACK_IMPORTED_MODULE_50__["default"],
    ViewDashboard: vue_material_design_icons_ViewDashboard_vue__WEBPACK_IMPORTED_MODULE_51__["default"],
    ImageMultiple: vue_material_design_icons_ImageMultiple_vue__WEBPACK_IMPORTED_MODULE_52__["default"],
    Draw: vue_material_design_icons_Draw_vue__WEBPACK_IMPORTED_MODULE_53__["default"],
    Puzzle: vue_material_design_icons_Puzzle_vue__WEBPACK_IMPORTED_MODULE_54__["default"],
    Robot: vue_material_design_icons_Robot_vue__WEBPACK_IMPORTED_MODULE_55__["default"],
    TableLarge: vue_material_design_icons_TableLarge_vue__WEBPACK_IMPORTED_MODULE_56__["default"],
    BookOpenVariant: vue_material_design_icons_BookOpenVariant_vue__WEBPACK_IMPORTED_MODULE_57__["default"],
    Poll: vue_material_design_icons_Poll_vue__WEBPACK_IMPORTED_MODULE_58__["default"],
    CalendarClock: vue_material_design_icons_CalendarClock_vue__WEBPACK_IMPORTED_MODULE_59__["default"],
    Earth: vue_material_design_icons_Earth_vue__WEBPACK_IMPORTED_MODULE_60__["default"],
    ShieldAccount: vue_material_design_icons_ShieldAccount_vue__WEBPACK_IMPORTED_MODULE_61__["default"],
    Apps: vue_material_design_icons_Apps_vue__WEBPACK_IMPORTED_MODULE_62__["default"],
    Cloud: vue_material_design_icons_Cloud_vue__WEBPACK_IMPORTED_MODULE_63__["default"],
    InformationOutline: vue_material_design_icons_InformationOutline_vue__WEBPACK_IMPORTED_MODULE_64__["default"],
    LinkBoxVariant: vue_material_design_icons_LinkBoxVariant_vue__WEBPACK_IMPORTED_MODULE_65__["default"],
    FormatText: vue_material_design_icons_FormatText_vue__WEBPACK_IMPORTED_MODULE_66__["default"],
    Minus: vue_material_design_icons_Minus_vue__WEBPACK_IMPORTED_MODULE_67__["default"],
    Contacts: vue_material_design_icons_Contacts_vue__WEBPACK_IMPORTED_MODULE_68__["default"],
    MessageText: vue_material_design_icons_MessageText_vue__WEBPACK_IMPORTED_MODULE_69__["default"]
  },
  props: {
    widget: {
      type: Object,
      required: true
    },
    rowBackgroundColor: {
      type: String,
      default: ''
    }
  },
  emits: ['navigate'],
  computed: {
    effectiveBackgroundColor() {
      return (0,_utils_colorUtils_js__WEBPACK_IMPORTED_MODULE_2__.getEffectiveBackgroundColor)(this.widget.backgroundColor, this.rowBackgroundColor);
    },
    isTileLayout() {
      return this.widget.layout === 'tiles';
    },
  },
  methods: {
    t(key, vars = {}) {
      return (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_0__.translate)('intravox', key, vars);
    },
    sanitizeHtml(content) {
      if (!content) return '';
      // Use the markdown serializer to convert markdown to HTML
      return (0,_utils_markdownSerializer_js__WEBPACK_IMPORTED_MODULE_1__.markdownToHtml)(content);
    },
    getLinkItemClass(link) {
      // Individual link background takes precedence
      const linkBg = link.backgroundColor;
      if (linkBg) {
        if ((0,_utils_colorUtils_js__WEBPACK_IMPORTED_MODULE_2__.isDarkBackground)(linkBg)) {
          return 'link-item--bg-dark';
        }
        return 'link-item--bg-light';
      }

      // No individual link background → blend into container/row background
      const containerBg = this.effectiveBackgroundColor;

      if ((0,_utils_colorUtils_js__WEBPACK_IMPORTED_MODULE_2__.isDarkBackground)(containerBg)) {
        // Dark container → transparent links with white text
        return 'link-item--bg-on-dark';
      }

      // Default: transparent links that blend into their background
      return 'link-item--bg-transparent';
    },
    getIconComponent(iconName) {
      const iconMap = {
        'home': 'Home',
        'information': 'Information',
        'email': 'Email',
        'phone': 'Phone',
        'web': 'Web',
        'file-document': 'FileDocument',
        'download': 'Download',
        'help': 'Help',
        'help-circle': 'HelpCircle',
        'cog': 'Cog',
        'account': 'Account',
        'account-group': 'AccountGroup',
        'account-multiple': 'AccountMultiple',
        'account-tie': 'AccountTie',
        'calendar': 'Calendar',
        'chart-line': 'ChartLine',
        'briefcase': 'Briefcase',
        'school': 'School',
        'star': 'Star',
        'heart': 'Heart',
        'lightbulb-on': 'LightbulbOn',
        'rocket': 'Rocket',
        'rocket-launch': 'RocketLaunch',
        'archive': 'Archive',
        'open-in-new': 'OpenInNew',
        'book-open': 'BookOpen',
        'hand-okay': 'HandOkay',
        'shield-check': 'ShieldCheck',
        'open-source-initiative': 'OpenSourceInitiative',
        'translate': 'Translate',
        'responsive': 'Responsive',
        'office-building': 'OfficeBuilding',
        'newspaper': 'Newspaper',
        'bullhorn': 'Bullhorn',
        'forum': 'Forum',
        'bug': 'Bug',
        'github': 'Github',
        'palette': 'Palette',
        'code-tags': 'CodeTags',
        'lifebuoy': 'Lifebuoy',
        'currency-usd': 'CurrencyUsd',
        'folder': 'Folder',
        'folder-multiple': 'FolderMultiple',
        'chat': 'Chat',
        'view-column': 'ViewColumn',
        'form-select': 'FormSelect',
        'clipboard-check': 'ClipboardCheck',
        'view-dashboard': 'ViewDashboard',
        'image-multiple': 'ImageMultiple',
        'draw': 'Draw',
        'puzzle': 'Puzzle',
        'robot': 'Robot',
        'table-large': 'TableLarge',
        'book-open-variant': 'BookOpenVariant',
        'poll': 'Poll',
        'calendar-clock': 'CalendarClock',
        'earth': 'Earth',
        'shield-account': 'ShieldAccount',
        'apps': 'Apps',
        'cloud': 'Cloud',
        'information-outline': 'InformationOutline',
        'link-box-variant': 'LinkBoxVariant',
        'format-text': 'FormatText',
        'minus': 'Minus',
        'contacts': 'Contacts',
        'message-text': 'MessageText'
      };
      return iconMap[iconName] || 'OpenInNew';
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
    getGridStyle() {
      const columns = this.widget.columns || 2;
      const minWidth = Math.floor(100 / columns);
      return {
        gridTemplateColumns: `repeat(auto-fill, minmax(max(100px, calc(${minWidth}% - 12px)), 1fr))`
      };
    },
    getLinkStyle(link) {
      const style = {};
      if (link.backgroundColor) {
        style.backgroundColor = link.backgroundColor;
      }
      return style;
    },
    getLinkUrl(link) {
      // If link has a uniqueId (internal page), return hash URL
      if (link.uniqueId) {
        return `#${link.uniqueId}`;
      }
      // Otherwise return the external URL
      return link.url || '#';
    },
    isInternalLink(link) {
      // Check if it's an internal navigation link (has uniqueId or URL starts with #)
      if (link.uniqueId) {
        return true;
      }
      return link.url && link.url.startsWith('#') && link.url.length > 1;
    },
    handleLinkClick(event, link) {
      // If it's an internal link, prevent default and emit navigate event
      if (this.isInternalLink(link)) {
        event.preventDefault();
        event.stopPropagation();
        // Get the pageId from uniqueId or from URL hash
        const pageId = link.uniqueId || link.url.substring(1);
        this.$emit('navigate', pageId);
      }
      // For external links, the default behavior (open in new tab) will happen
    }
  }
});


/***/ },

/***/ "./src/components/LinksWidget.vue?vue&type=style&index=0&id=79936edd&scoped=true&lang=css"
/*!************************************************************************************************!*\
  !*** ./src/components/LinksWidget.vue?vue&type=style&index=0&id=79936edd&scoped=true&lang=css ***!
  \************************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _node_modules_style_loader_dist_cjs_js_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_dist_stylePostLoader_js_node_modules_vue_loader_dist_index_js_ruleSet_1_rules_5_use_0_LinksWidget_vue_vue_type_style_index_0_id_79936edd_scoped_true_lang_css__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../node_modules/style-loader/dist/cjs.js!../../node_modules/css-loader/dist/cjs.js!../../node_modules/vue-loader/dist/stylePostLoader.js!../../node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./LinksWidget.vue?vue&type=style&index=0&id=79936edd&scoped=true&lang=css */ "./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/dist/stylePostLoader.js!./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/LinksWidget.vue?vue&type=style&index=0&id=79936edd&scoped=true&lang=css");


/***/ },

/***/ "./src/components/LinksWidget.vue?vue&type=script&lang=js"
/*!****************************************************************!*\
  !*** ./src/components/LinksWidget.vue?vue&type=script&lang=js ***!
  \****************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* reexport safe */ _node_modules_vue_loader_dist_index_js_ruleSet_1_rules_5_use_0_LinksWidget_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_0__["default"])
/* harmony export */ });
/* harmony import */ var _node_modules_vue_loader_dist_index_js_ruleSet_1_rules_5_use_0_LinksWidget_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./LinksWidget.vue?vue&type=script&lang=js */ "./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/LinksWidget.vue?vue&type=script&lang=js");
 

/***/ },

/***/ "./src/components/LinksWidget.vue?vue&type=template&id=79936edd&scoped=true"
/*!**********************************************************************************!*\
  !*** ./src/components/LinksWidget.vue?vue&type=template&id=79936edd&scoped=true ***!
  \**********************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   render: () => (/* reexport safe */ _node_modules_vue_loader_dist_templateLoader_js_ruleSet_1_rules_2_node_modules_vue_loader_dist_index_js_ruleSet_1_rules_5_use_0_LinksWidget_vue_vue_type_template_id_79936edd_scoped_true__WEBPACK_IMPORTED_MODULE_0__.render)
/* harmony export */ });
/* harmony import */ var _node_modules_vue_loader_dist_templateLoader_js_ruleSet_1_rules_2_node_modules_vue_loader_dist_index_js_ruleSet_1_rules_5_use_0_LinksWidget_vue_vue_type_template_id_79936edd_scoped_true__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../node_modules/vue-loader/dist/templateLoader.js??ruleSet[1].rules[2]!../../node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./LinksWidget.vue?vue&type=template&id=79936edd&scoped=true */ "./node_modules/vue-loader/dist/templateLoader.js??ruleSet[1].rules[2]!./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/LinksWidget.vue?vue&type=template&id=79936edd&scoped=true");


/***/ },

/***/ "./node_modules/vue-loader/dist/templateLoader.js??ruleSet[1].rules[2]!./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/LinksWidget.vue?vue&type=template&id=79936edd&scoped=true"
/*!****************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/vue-loader/dist/templateLoader.js??ruleSet[1].rules[2]!./node_modules/vue-loader/dist/index.js??ruleSet[1].rules[5].use[0]!./src/components/LinksWidget.vue?vue&type=template&id=79936edd&scoped=true ***!
  \****************************************************************************************************************************************************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   render: () => (/* binding */ render)
/* harmony export */ });
/* harmony import */ var vue__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! vue */ "./node_modules/vue/dist/vue.runtime.esm-bundler.js");


const _hoisted_1 = ["href", "target", "rel", "onClick"]
const _hoisted_2 = {
  key: 1,
  class: "link-tile-content"
}
const _hoisted_3 = ["innerHTML"]
const _hoisted_4 = ["innerHTML"]
const _hoisted_5 = ["innerHTML"]
const _hoisted_6 = {
  key: 1,
  class: "empty-links-placeholder"
}

function render(_ctx, _cache, $props, $setup, $data, $options) {
  const _component_ViewGrid = (0,vue__WEBPACK_IMPORTED_MODULE_0__.resolveComponent)("ViewGrid")

  return ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)("div", {
    class: "links-widget",
    style: (0,vue__WEBPACK_IMPORTED_MODULE_0__.normalizeStyle)($options.getContainerStyle())
  }, [
    ($props.widget.items && $props.widget.items.length > 0)
      ? ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)("div", {
          key: 0,
          class: "links-grid",
          style: (0,vue__WEBPACK_IMPORTED_MODULE_0__.normalizeStyle)($options.getGridStyle())
        }, [
          ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(true), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)(vue__WEBPACK_IMPORTED_MODULE_0__.Fragment, null, (0,vue__WEBPACK_IMPORTED_MODULE_0__.renderList)($props.widget.items, (link, index) => {
            return ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)("a", {
              key: link.uniqueId || link.url || `link-${index}`,
              href: $options.getLinkUrl(link),
              target: $options.isInternalLink(link) ? '_self' : '_blank',
              rel: $options.isInternalLink(link) ? '' : 'noopener noreferrer',
              class: (0,vue__WEBPACK_IMPORTED_MODULE_0__.normalizeClass)(["link-item", [$options.getLinkItemClass(link), { 'link-item--tile': $options.isTileLayout }]]),
              style: (0,vue__WEBPACK_IMPORTED_MODULE_0__.normalizeStyle)($options.getLinkStyle(link)),
              onClick: $event => ($options.handleLinkClick($event, link))
            }, [
              (link.icon)
                ? ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createBlock)((0,vue__WEBPACK_IMPORTED_MODULE_0__.resolveDynamicComponent)($options.getIconComponent(link.icon)), {
                    key: 0,
                    size: $options.isTileLayout ? 36 : 24,
                    class: "link-icon"
                  }, null, 8 /* PROPS */, ["size"]))
                : (0,vue__WEBPACK_IMPORTED_MODULE_0__.createCommentVNode)("v-if", true),
              ($options.isTileLayout && link.title && link.text)
                ? ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)("span", _hoisted_2, [
                    (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("span", {
                      class: "link-tile-title",
                      innerHTML: $options.sanitizeHtml(link.title)
                    }, null, 8 /* PROPS */, _hoisted_3),
                    (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("span", {
                      class: "link-tile-subtitle",
                      innerHTML: $options.sanitizeHtml(link.text)
                    }, null, 8 /* PROPS */, _hoisted_4)
                  ]))
                : ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)("span", {
                    key: 2,
                    class: "link-text",
                    innerHTML: $options.sanitizeHtml(link.text || link.title)
                  }, null, 8 /* PROPS */, _hoisted_5))
            ], 14 /* CLASS, STYLE, PROPS */, _hoisted_1))
          }), 128 /* KEYED_FRAGMENT */))
        ], 4 /* STYLE */))
      : ((0,vue__WEBPACK_IMPORTED_MODULE_0__.openBlock)(), (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementBlock)("div", _hoisted_6, [
          (0,vue__WEBPACK_IMPORTED_MODULE_0__.createVNode)(_component_ViewGrid, { size: 32 }),
          (0,vue__WEBPACK_IMPORTED_MODULE_0__.createElementVNode)("p", null, (0,vue__WEBPACK_IMPORTED_MODULE_0__.toDisplayString)($options.t('No links configured. Click the edit button to add links.')), 1 /* TEXT */)
        ]))
  ], 4 /* STYLE */))
}

/***/ }

}]);
//# sourceMappingURL=intravox-src_components_LinksWidget_vue.c97c01d5.js.map