<template>
  <div class="links-widget" :style="getContainerStyle()">
    <div v-if="widget.items && widget.items.length > 0" class="links-grid" :style="getGridStyle()">
      <a
        v-for="(link, index) in widget.items"
        :key="link.uniqueId || link.url || `link-${index}`"
        :href="getLinkUrl(link)"
        :target="isInternalLink(link) ? '_self' : '_blank'"
        :rel="isInternalLink(link) ? '' : 'noopener noreferrer'"
        class="link-item"
        :class="getLinkItemClass(link)"
        :style="getLinkStyle(link)"
        @click="handleLinkClick($event, link)"
      >
        <component
          v-if="link.icon"
          :is="getIconComponent(link.icon)"
          :size="24"
          class="link-icon"
        />
        <span class="link-text" v-html="sanitizeHtml(link.text || link.title)"></span>
      </a>
    </div>
    <div v-else class="empty-links-placeholder">
      <ViewGrid :size="32" />
      <p>{{ t('No links configured. Click the edit button to add links.') }}</p>
    </div>
  </div>
</template>

<script>
import { translate as t } from '@nextcloud/l10n';
import { markdownToHtml } from '../utils/markdownSerializer.js';
import { isDarkBackground, getEffectiveBackgroundColor } from '../utils/colorUtils.js';

// Import common Material Design Icons
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
    CurrencyUsd
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
      return getEffectiveBackgroundColor(this.widget.backgroundColor, this.rowBackgroundColor);
    },
  },
  methods: {
    t(key, vars = {}) {
      return t('intravox', key, vars);
    },
    sanitizeHtml(content) {
      if (!content) return '';
      // Use the markdown serializer to convert markdown to HTML
      return markdownToHtml(content);
    },
    getLinkItemClass(link) {
      // Individual link background takes precedence
      const linkBg = link.backgroundColor;
      if (linkBg) {
        if (isDarkBackground(linkBg)) {
          return 'link-item--bg-dark';
        }
        return 'link-item--bg-light';
      }

      // No individual link background → blend into container/row background
      const containerBg = this.effectiveBackgroundColor;

      if (isDarkBackground(containerBg)) {
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
        'currency-usd': 'CurrencyUsd'
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
      return {
        gridTemplateColumns: `repeat(${columns}, 1fr)`
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

/* Base link-item styles */
.link-item {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 16px;
  border: 1px solid var(--color-border);
  border-radius: var(--border-radius-container-large);
  color: var(--color-main-text);
  text-decoration: none;
  transition: background 0.2s, border-color 0.2s, box-shadow 0.2s;
  cursor: pointer;
}

.link-icon {
  flex-shrink: 0;
  color: var(--color-primary-element);
}

/* Transparent — default: blends into container/row background */
.link-item--bg-transparent {
  background: transparent;
}

.link-item--bg-transparent:hover {
  background: rgba(0, 0, 0, 0.04);
  border-color: var(--color-primary-element);
  box-shadow: 0 2px 6px rgba(0, 0, 0, 0.06);
}

/* On dark container — transparent links with white text/icons */
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

/* Explicit light background — individual link set to Light */
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

/* Explicit dark background — individual link set to Primary */
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

.link-text {
  font-weight: 500;
  font-size: 14px;
}

/* Empty placeholder */
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

/* Mobile responsiveness */
@media (max-width: 768px) {
  .links-grid {
    grid-template-columns: 1fr !important;
  }

  .link-item {
    padding: 14px;
  }
}
</style>
