<template>
  <div class="links-widget" :style="getContainerStyle()">
    <div v-if="visibleLinks.length > 0" class="links-grid" :style="getGridStyle()">
      <a
        v-for="(link, index) in visibleLinks"
        :key="link.uniqueId || link.url || `link-${index}`"
        :href="isDisabled(link) ? undefined : getLinkUrl(link)"
        :target="isInternalLink(link) ? '_self' : '_blank'"
        :rel="isInternalLink(link) ? '' : 'noopener noreferrer'"
        class="link-item"
        :class="[getLinkItemClass(link), { 'link-item--tile': isTileLayout, 'link-item--unavailable': isDisabled(link) }]"
        :style="getLinkStyle(link)"
        :data-agl-entity="link.entity || 'all'"
        :data-excom-only="link.excomOnly ? 'true' : 'false'"
        :aria-disabled="isDisabled(link) ? 'true' : undefined"
        :title="isDisabled(link) ? availabilityLabel(link) : undefined"
        @click="handleLinkClick($event, link)"
      >
        <component
          v-if="link.icon"
          :is="getIconComponent(link.icon)"
          :size="isTileLayout ? 36 : 24"
          class="link-icon"
        />
        <span v-if="isTileLayout && link.title && link.text" class="link-tile-content">
          <span class="link-tile-title" v-html="sanitizeHtml(link.title)"></span>
          <span class="link-tile-subtitle" v-html="sanitizeHtml(link.text)"></span>
          <span v-if="entityBadge(link)" class="agl-entity-badge" :data-entity="badgeEntity(link)">{{ entityBadge(link) }}</span>
          <span v-if="isDisabled(link)" class="link-unavailable">{{ availabilityLabel(link) }}</span>
        </span>
        <span v-else class="link-text" v-html="sanitizeHtml(link.text || link.title)"></span>
        <span v-if="!(isTileLayout && link.title && link.text) && entityBadge(link)" class="agl-entity-badge" :data-entity="badgeEntity(link)">{{ entityBadge(link) }}</span>
        <span v-if="isDisabled(link) && !(isTileLayout && link.title && link.text)" class="link-unavailable">{{ availabilityLabel(link) }}</span>
      </a>
    </div>
    <div v-else class="empty-links-placeholder">
      <ViewGrid :size="32" />
      <p>{{ widget.items?.length ? t('intravox', 'No links available in this workspace lens.') : t('intravox', 'No links configured. Click the edit button to add links.') }}</p>
    </div>
  </div>
</template>

<script>
import { openNativeSearch } from '../utils/aglGlobalSearch.js';
import { showError } from '@nextcloud/dialogs';
import { translate } from '@nextcloud/l10n';
import { loadState } from '@nextcloud/initial-state';
import { markdownToHtml } from '../utils/markdownSerializer.js';
import { isDarkBackground, getEffectiveBackgroundColor } from '../utils/colorUtils.js';
import { isVisibleInAglContext } from '../utils/aglBrandContext.js';

import ViewGrid from 'vue-material-design-icons/ViewGrid.vue';
import Home from 'vue-material-design-icons/Home.vue';
import Information from 'vue-material-design-icons/Information.vue';
import Email from 'vue-material-design-icons/Email.vue';
import Phone from 'vue-material-design-icons/Phone.vue';
import Web from 'vue-material-design-icons/Web.vue';
import FileDocument from 'vue-material-design-icons/FileDocument.vue';
import Download from 'vue-material-design-icons/Download.vue';
import Help from 'vue-material-design-icons/Help.vue';
import Cog from 'vue-material-design-icons/Cog.vue';
import Account from 'vue-material-design-icons/Account.vue';
import AccountGroup from 'vue-material-design-icons/AccountGroup.vue';
import AccountMultiple from 'vue-material-design-icons/AccountMultiple.vue';
import AccountTie from 'vue-material-design-icons/AccountTie.vue';
import Calendar from 'vue-material-design-icons/Calendar.vue';
import ChartLine from 'vue-material-design-icons/ChartLine.vue';
import Briefcase from 'vue-material-design-icons/Briefcase.vue';
import School from 'vue-material-design-icons/School.vue';
import Star from 'vue-material-design-icons/Star.vue';
import Heart from 'vue-material-design-icons/Heart.vue';
import LightbulbOn from 'vue-material-design-icons/LightbulbOn.vue';
import Rocket from 'vue-material-design-icons/Rocket.vue';
import RocketLaunch from 'vue-material-design-icons/RocketLaunch.vue';
import Archive from 'vue-material-design-icons/Archive.vue';
import OpenInNew from 'vue-material-design-icons/OpenInNew.vue';
import BookOpen from 'vue-material-design-icons/BookOpen.vue';
import HelpCircle from 'vue-material-design-icons/HelpCircle.vue';
import HandOkay from 'vue-material-design-icons/HandOkay.vue';
import ShieldCheck from 'vue-material-design-icons/ShieldCheck.vue';
import OpenSourceInitiative from 'vue-material-design-icons/OpenSourceInitiative.vue';
import Translate from 'vue-material-design-icons/Translate.vue';
import Responsive from 'vue-material-design-icons/Responsive.vue';
import OfficeBuilding from 'vue-material-design-icons/OfficeBuilding.vue';
import Newspaper from 'vue-material-design-icons/Newspaper.vue';
import Bullhorn from 'vue-material-design-icons/Bullhorn.vue';
import Forum from 'vue-material-design-icons/Forum.vue';
import Bug from 'vue-material-design-icons/Bug.vue';
import Github from 'vue-material-design-icons/Github.vue';
import Palette from 'vue-material-design-icons/Palette.vue';
import CodeTags from 'vue-material-design-icons/CodeTags.vue';
import Lifebuoy from 'vue-material-design-icons/Lifebuoy.vue';
import CurrencyUsd from 'vue-material-design-icons/CurrencyUsd.vue';
import Folder from 'vue-material-design-icons/Folder.vue';
import FolderMultiple from 'vue-material-design-icons/FolderMultiple.vue';
import Chat from 'vue-material-design-icons/Chat.vue';
import ViewColumn from 'vue-material-design-icons/ViewColumn.vue';
import FormSelect from 'vue-material-design-icons/FormSelect.vue';
import ClipboardCheck from 'vue-material-design-icons/ClipboardCheck.vue';
import ViewDashboard from 'vue-material-design-icons/ViewDashboard.vue';
import ImageMultiple from 'vue-material-design-icons/ImageMultiple.vue';
import Draw from 'vue-material-design-icons/Draw.vue';
import Puzzle from 'vue-material-design-icons/Puzzle.vue';
import Robot from 'vue-material-design-icons/Robot.vue';
import TableLarge from 'vue-material-design-icons/TableLarge.vue';
import BookOpenVariant from 'vue-material-design-icons/BookOpenVariant.vue';
import Poll from 'vue-material-design-icons/Poll.vue';
import CalendarClock from 'vue-material-design-icons/CalendarClock.vue';
import Earth from 'vue-material-design-icons/Earth.vue';
import ShieldAccount from 'vue-material-design-icons/ShieldAccount.vue';
import Apps from 'vue-material-design-icons/Apps.vue';
import Cloud from 'vue-material-design-icons/Cloud.vue';
import InformationOutline from 'vue-material-design-icons/InformationOutline.vue';
import LinkBoxVariant from 'vue-material-design-icons/LinkBoxVariant.vue';
import FormatText from 'vue-material-design-icons/FormatText.vue';
import Minus from 'vue-material-design-icons/Minus.vue';
import Contacts from 'vue-material-design-icons/Contacts.vue';
import MessageText from 'vue-material-design-icons/MessageText.vue';

export default {
  name: 'LinksWidget',
  components: {
    ViewGrid,
    Home,
    Information,
    Email,
    Phone,
    Web,
    FileDocument,
    Download,
    Help,
    Cog,
    Account,
    AccountGroup,
    AccountMultiple,
    AccountTie,
    Calendar,
    ChartLine,
    Briefcase,
    School,
    Star,
    Heart,
    LightbulbOn,
    Rocket,
    RocketLaunch,
    Archive,
    OpenInNew,
    BookOpen,
    HelpCircle,
    HandOkay,
    ShieldCheck,
    OpenSourceInitiative,
    Translate,
    Responsive,
    OfficeBuilding,
    Newspaper,
    Bullhorn,
    Forum,
    Bug,
    Github,
    Palette,
    CodeTags,
    Lifebuoy,
    CurrencyUsd,
    Folder,
    FolderMultiple,
    Chat,
    ViewColumn,
    FormSelect,
    ClipboardCheck,
    ViewDashboard,
    ImageMultiple,
    Draw,
    Puzzle,
    Robot,
    TableLarge,
    BookOpenVariant,
    Poll,
    CalendarClock,
    Earth,
    ShieldAccount,
    Apps,
    Cloud,
    InformationOutline,
    LinkBoxVariant,
    FormatText,
    Minus,
    Contacts,
    MessageText
  },
  props: {
    isPublic: { type: Boolean, default: false },
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
  data() {
    return {
      activeContext: document.documentElement.dataset.aglContext || 'all',
      excomEligible: false,
      appAvailability: loadState('intravox', 'appAvailability', {}),
    };
  },
  mounted() {
    this.excomEligible = !this.isPublic && document.documentElement.dataset.aglExcom === 'true';
    document.addEventListener('agl:context-change', this.onAglContextChange);
  },
  beforeUnmount() {
    document.removeEventListener('agl:context-change', this.onAglContextChange);
  },
  computed: {
    visibleLinks() { return (this.widget.items || []).filter(link => isVisibleInAglContext(link, this.activeContext, this.excomEligible)); },
    effectiveBackgroundColor() {
      return getEffectiveBackgroundColor(this.widget.backgroundColor, this.rowBackgroundColor);
    },
    isTileLayout() {
      return this.widget.layout === 'tiles';
    },
  },
  methods: {
    onAglContextChange(event) { this.activeContext = event.detail.context; this.excomEligible = !this.isPublic && event.detail.eligible === true; },
    badgeEntity(link) { return link.excomOnly ? 'excom' : (link.entity || 'all'); },
    isUnavailable(link) { return !!link.appId && this.appAvailability?.[link.appId] === false; },
    isUnverified(link) { return !!link.appId && this.appAvailability?.[link.appId] !== true && this.appAvailability?.[link.appId] !== false; },
    isDisabled(link) { return this.isUnavailable(link) || this.isUnverified(link); },
    availabilityLabel(link) { return this.isUnavailable(link) ? this.t('intravox', 'Not enabled') : this.t('intravox', 'Availability unconfirmed'); },
    entityBadge(link) { return { 'cloud-carib': this.t('intravox', 'Cloud Carib'), 'athena-cloud': this.t('intravox', 'Athena Cloud'), excom: this.t('intravox', 'ExCom') }[this.badgeEntity(link)] || ''; },
    t(app, text, vars) {
      return translate(app, text, vars);
    },
    sanitizeHtml(content) {
      if (!content) return '';
      return markdownToHtml(content);
    },
    getLinkItemClass(link) {
      const linkBg = link.backgroundColor;
      if (linkBg) {
        if (isDarkBackground(linkBg)) {
          return 'link-item--bg-dark';
        }
        return 'link-item--bg-light';
      }

      const containerBg = this.effectiveBackgroundColor;

      if (isDarkBackground(containerBg)) {
        return 'link-item--bg-on-dark';
      }

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
      if (link.uniqueId) {
        return `#${link.uniqueId}`;
      }
      return link.url || '#';
    },
    isInternalLink(link) {
      if (link.uniqueId) {
        return true;
      }
      return link.url && link.url.startsWith('#') && link.url.length > 1;
    },
    handleLinkClick(event, link) {
      if (this.isDisabled(link)) {
        event.preventDefault();
        event.stopPropagation();
        return;
      }
      if (link.action === 'global-search') {
        event.preventDefault();
        event.stopPropagation();
        this.openGlobalSearch();
        return;
      }
      if (this.isInternalLink(link)) {
        event.preventDefault();
        event.stopPropagation();
        const pageId = link.uniqueId || link.url.substring(1);
        this.$emit('navigate', pageId);
      }
    },
    openGlobalSearch() {
      if (!openNativeSearch()) showError(this.t('intravox', 'Global search is unavailable in this session.'));
    }
  }
};
</script>

<style scoped>
.links-widget {
  width: 100%;
  margin: 12px 0;
}

.links-grid {
  display: grid;
  gap: 12px;
  grid-template-columns: repeat(2, 1fr);
}

.link-item {
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

.link-item--unavailable { cursor: not-allowed; color: #34263a; background: #f0ebf1; border-color: #776b7b; }
.link-unavailable { display: inline-flex; align-self: center; padding: 3px 7px; border: 1px solid currentColor; border-radius: 999px; font-size: 10px; font-weight: 700; white-space: nowrap; }

/* Card shell */
.link-icon {
  flex-shrink: 0;
  color: var(--color-primary-element);
}

/* Transparent surface */
.link-item--bg-transparent {
  background: transparent;
}

.link-item--bg-transparent:hover {
  background: rgba(0, 0, 0, 0.04);
  border-color: var(--color-primary-element);
  box-shadow: 0 2px 6px rgba(0, 0, 0, 0.06);
}

/* Inverse surface */
.link-item--bg-on-dark {
  background: transparent;
  border-color: rgba(255, 255, 255, 0.2);
  color: var(--color-primary-element-text);
}

.link-item--bg-on-dark .link-icon {
  color: rgba(255, 255, 255, 0.85);
}

.link-item--bg-on-dark:hover {
  background: rgba(255, 255, 255, 0.12);
  border-color: rgba(255, 255, 255, 0.4);
  color: var(--color-primary-element-text);
  box-shadow: 0 2px 6px rgba(0, 0, 0, 0.15);
}

.link-item--bg-on-dark:hover .link-icon {
  color: var(--color-primary-element-text);
}

/* Light surface */
.link-item--bg-light {
  background: var(--color-background-hover);
  border-color: var(--color-border);
  color: var(--color-main-text);
}

.link-item--bg-light .link-icon {
  color: var(--color-primary-element);
}

.link-item--bg-light:hover {
  background: var(--color-primary-element-light);
  border-color: var(--color-primary-element);
  box-shadow: 0 2px 6px rgba(0, 0, 0, 0.08);
}

/* Accent surface */
.link-item--bg-dark {
  background: var(--color-primary-element);
  border-color: var(--color-primary-element);
  color: var(--color-primary-element-text);
}

.link-item--bg-dark .link-icon {
  color: rgba(255, 255, 255, 0.85);
}

.link-item--bg-dark:hover {
  filter: brightness(1.1);
  box-shadow: 0 2px 6px rgba(0, 0, 0, 0.15);
}

.link-item--bg-dark:hover .link-icon {
  color: var(--color-primary-element-text);
}

/* Tile layout */
.link-item--tile {
  flex-direction: column;
  text-align: center;
  padding: 20px 12px;
  min-height: 100px;
  justify-content: center;
  gap: 8px;
}

.link-item--tile .link-icon {
  margin-bottom: 4px;
}

/* Tile copy */
.link-tile-content {
  display: flex;
  flex-direction: column;
  gap: 2px;
}

.link-tile-title {
  font-weight: 600;
  font-size: 14px;
  overflow-wrap: break-word;
  word-break: break-word;
}

.link-tile-subtitle {
  font-size: 12px;
  opacity: 0.7;
  overflow-wrap: break-word;
  word-break: break-word;
}

.link-text {
  font-weight: 500;
  font-size: 14px;
  min-width: 0;
  overflow-wrap: break-word;
  word-break: break-word;
}

.empty-links-placeholder {
  text-align: center;
  padding: 40px 20px;
  background: var(--color-background-hover);
  border: 2px dashed var(--color-border);
  border-radius: var(--border-radius-large);
  color: var(--color-text-maxcontrast);
}

.empty-links-placeholder svg {
  margin-bottom: 10px;
  color: var(--color-text-maxcontrast);
}

.empty-links-placeholder p {
  margin: 0;
  font-size: 14px;
}

@media (max-width: 768px) {
  .links-grid {
    grid-template-columns: 1fr !important;
  }

  .links-grid:has(.link-item--tile) {
    grid-template-columns: repeat(2, 1fr) !important;
  }

  .link-item {
    padding: 14px;
  }

  .link-item--tile {
    padding: 16px 8px;
    min-height: 80px;
  }
}
</style>
