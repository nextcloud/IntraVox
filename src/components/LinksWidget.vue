<template>
  <div class="links-widget" :style="getContainerStyle()">
    <div v-if="widget.items && widget.items.length > 0" class="links-grid" :style="getGridStyle()">
      <a
        v-for="(link, index) in widget.items"
        :key="index"
        :href="link.url"
        target="_blank"
        rel="noopener noreferrer"
        class="link-item"
        :style="getLinkStyle(link)"
      >
        <component
          v-if="link.icon"
          :is="getIconComponent(link.icon)"
          :size="24"
          class="link-icon"
        />
        <span class="link-text" v-html="sanitizeHtml(link.text)"></span>
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
import Calendar from 'vue-material-design-icons/Calendar.vue';
import ChartLine from 'vue-material-design-icons/ChartLine.vue';
import Briefcase from 'vue-material-design-icons/Briefcase.vue';
import School from 'vue-material-design-icons/School.vue';
import Star from 'vue-material-design-icons/Star.vue';
import Heart from 'vue-material-design-icons/Heart.vue';
import LightbulbOn from 'vue-material-design-icons/LightbulbOn.vue';
import Rocket from 'vue-material-design-icons/Rocket.vue';
import Archive from 'vue-material-design-icons/Archive.vue';
import OpenInNew from 'vue-material-design-icons/OpenInNew.vue';

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
    Calendar,
    ChartLine,
    Briefcase,
    School,
    Star,
    Heart,
    LightbulbOn,
    Rocket,
    Archive,
    OpenInNew
  },
  props: {
    widget: {
      type: Object,
      required: true
    }
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
        'cog': 'Cog',
        'account': 'Account',
        'account-group': 'AccountGroup',
        'calendar': 'Calendar',
        'chart-line': 'ChartLine',
        'briefcase': 'Briefcase',
        'school': 'School',
        'star': 'Star',
        'heart': 'Heart',
        'lightbulb-on': 'LightbulbOn',
        'rocket': 'Rocket',
        'archive': 'Archive',
        'open-in-new': 'OpenInNew'
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
    }
  }
};
</script>

<style scoped>
.links-widget {
  width: 100%;
  margin: 8px 0;
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
  background: var(--color-background-hover);
  border: 1px solid var(--color-border);
  border-radius: var(--border-radius-container-large);
  color: var(--color-main-text);
  text-decoration: none;
  transition: all 0.2s;
  cursor: pointer;
}

.link-item:hover {
  background: var(--color-primary-element-light);
  border-color: var(--color-primary);
  color: var(--color-primary);
  box-shadow: 0 2px 6px rgba(0, 0, 0, 0.1);
}

.link-icon {
  flex-shrink: 0;
  color: var(--color-text-maxcontrast);
}

.link-item:hover .link-icon {
  color: var(--color-primary);
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
