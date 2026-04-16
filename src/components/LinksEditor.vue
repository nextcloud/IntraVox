<template>
  <NcModal @close="$emit('close')"
           :name="t('Edit links')"
           size="large"
           class="links-editor-modal">
    <div class="links-editor-content">
      <!-- Container Settings -->
      <div class="form-section">
        <h3>{{ t('Container settings') }}</h3>

        <div class="form-group">
          <label for="links-grid-columns">{{ t('Grid columns:') }}</label>
          <select id="links-grid-columns" v-model.number="localWidget.columns" class="widget-input">
            <option :value="1">1 {{ t('column') }}</option>
            <option :value="2">2 {{ t('columns') }}</option>
            <option :value="3">3 {{ t('columns') }}</option>
            <option :value="4">4 {{ t('columns') }}</option>
          </select>
        </div>

        <div class="form-group">
          <label for="links-layout">{{ t('Layout:') }}</label>
          <select id="links-layout" v-model="localWidget.layout" class="widget-input">
            <option value="list">{{ t('List') }}</option>
            <option value="tiles">{{ t('Tiles') }}</option>
          </select>
        </div>

        <div class="form-group">
          <label>{{ t('Background color:') }}</label>
          <div class="color-presets">
            <button
              type="button"
              :class="{ active: !localWidget.backgroundColor }"
              @click="setBackgroundColor(null)"
              class="color-preset-btn"
            >
              <span class="color-swatch color-swatch--none"></span>
              {{ t('None') }}
            </button>
            <button
              type="button"
              :class="{ active: localWidget.backgroundColor === 'var(--color-background-hover)' }"
              @click="setBackgroundColor('var(--color-background-hover)')"
              class="color-preset-btn"
            >
              <span class="color-swatch color-swatch--light"></span>
              {{ t('Light') }}
            </button>
            <button
              type="button"
              :class="{ active: localWidget.backgroundColor === 'var(--color-primary-element-light)' }"
              @click="setBackgroundColor('var(--color-primary-element-light)')"
              class="color-preset-btn"
            >
              <span class="color-swatch color-swatch--accent"></span>
              {{ t('Accent') }}
            </button>
            <button
              type="button"
              :class="{ active: localWidget.backgroundColor === 'var(--color-primary-element)' }"
              @click="setBackgroundColor('var(--color-primary-element)')"
              class="color-preset-btn"
            >
              <span class="color-swatch color-swatch--primary"></span>
              {{ t('Primary') }}
            </button>
          </div>
        </div>
      </div>

      <!-- Links List -->
      <div class="form-section">
        <div class="section-header">
          <h3>{{ t('Links') }}</h3>
          <NcButton @click="addLink" type="primary">
            <template #icon>
              <Plus :size="20" />
            </template>
            {{ t('Add link') }}
          </NcButton>
        </div>

        <draggable
          v-if="localWidget.items && localWidget.items.length > 0"
          v-model="localWidget.items"
          item-key="id"
          handle=".drag-handle"
          class="links-list"
          :animation="200"
        >
          <template #item="{ element: link, index }">
            <div class="link-editor-item">
              <div class="drag-handle" :title="t('Drag to reorder')">
                <DragVertical :size="20" />
              </div>

              <div class="link-fields">
                <div v-if="localWidget.layout === 'tiles'" class="form-row">
                  <div class="form-group flex-1">
                    <label :for="`link-title-${index}`">{{ t('Title:') }}</label>
                    <input
                      :id="`link-title-${index}`"
                      v-model="link.title"
                      type="text"
                      :placeholder="t('Tile title')"
                      class="widget-input"
                    />
                  </div>
                </div>
                <div class="form-row">
                  <div class="form-group flex-1">
                    <label :for="`link-text-${index}`">{{ localWidget.layout === 'tiles' ? t('Subtitle:') : t('Link text:') }}</label>
                    <input
                      :id="`link-text-${index}`"
                      v-model="link.text"
                      type="text"
                      :placeholder="localWidget.layout === 'tiles' ? t('Short description') : t('Click here')"
                      class="widget-input"
                    />
                  </div>

                  <div class="form-group flex-1">
                    <label :for="`link-url-${index}`">{{ t('Link to:') }}</label>
                    <div class="link-target-options">
                      <PageTreeSelect
                        :model-value="link.uniqueId"
                        :placeholder="t('Select page')"
                        :disabled="!!link.url"
                        :clearable="true"
                        @select="updatePageLink(index, $event)"
                        class="page-selector"
                      />
                      <span class="or-separator">{{ t('or') }}</span>
                      <input
                        :id="`link-url-${index}`"
                        v-model="link.url"
                        type="url"
                        placeholder="https://example.com"
                        class="widget-input url-input"
                        :disabled="!!link.uniqueId"
                        @input="clearPageLink(index)"
                      />
                    </div>
                  </div>
                </div>

                <div class="form-row">
                  <div class="form-group flex-1">
                    <label>{{ t('Icon:') }}</label>
                    <NcSelect
                      v-model="link.iconObject"
                      :options="iconOptions"
                      :placeholder="t('Select an icon')"
                      :clearable="true"
                      @update:modelValue="updateLinkIcon(index, $event)"
                    >
                      <template #selected-option="option">
                        <div class="icon-option">
                          <component :is="getIconComponent(option.value)" :size="20" />
                          <span>{{ option.label }}</span>
                        </div>
                      </template>
                      <template #option="option">
                        <div class="icon-option">
                          <component :is="getIconComponent(option.value)" :size="20" />
                          <span>{{ option.label }}</span>
                        </div>
                      </template>
                    </NcSelect>
                  </div>

                  <div class="form-group flex-1">
                    <label>{{ t('Link background:') }}</label>
                    <div class="color-presets">
                      <button
                        type="button"
                        :class="{ active: !link.backgroundColor }"
                        @click="setLinkBackground(index, null)"
                        class="color-preset-btn small"
                      >
                        <span class="color-swatch color-swatch--none"></span>
                        {{ t('Default') }}
                      </button>
                      <button
                        type="button"
                        :class="{ active: link.backgroundColor === 'var(--color-background-hover)' }"
                        @click="setLinkBackground(index, 'var(--color-background-hover)')"
                        class="color-preset-btn small"
                      >
                        <span class="color-swatch color-swatch--light"></span>
                        {{ t('Light') }}
                      </button>
                      <button
                        type="button"
                        :class="{ active: link.backgroundColor === 'var(--color-primary-element)' }"
                        @click="setLinkBackground(index, 'var(--color-primary-element)')"
                        class="color-preset-btn small"
                      >
                        <span class="color-swatch color-swatch--primary"></span>
                        {{ t('Primary') }}
                      </button>
                    </div>
                  </div>
                </div>
              </div>

              <button
                type="button"
                @click="removeLink(index)"
                class="remove-link-btn"
                :title="t('Remove link')"
              >
                <Delete :size="20" />
              </button>
            </div>
          </template>
        </draggable>

        <div v-else class="empty-state">
          <ViewGrid :size="48" />
          <p>{{ t('No links yet. Click "Add link" to get started.') }}</p>
        </div>
      </div>

      <div class="modal-footer">
        <NcButton @click="$emit('close')" type="secondary">
          {{ t('Cancel') }}
        </NcButton>
        <NcButton @click="save" type="primary">
          {{ t('Save') }}
        </NcButton>
      </div>
    </div>
  </NcModal>
</template>

<script>
import { translate as t } from '@nextcloud/l10n';
import { NcButton, NcModal, NcSelect } from '@nextcloud/vue';
import Plus from 'vue-material-design-icons/Plus.vue';
import Delete from 'vue-material-design-icons/Delete.vue';
import ViewGrid from 'vue-material-design-icons/ViewGrid.vue';
import DragVertical from 'vue-material-design-icons/DragVertical.vue';
import PageTreeSelect from './PageTreeSelect.vue';
import draggable from 'vuedraggable';

// Import all available icons
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
import HelpCircle from 'vue-material-design-icons/HelpCircle.vue';
import AccountMultiple from 'vue-material-design-icons/AccountMultiple.vue';
import AccountTie from 'vue-material-design-icons/AccountTie.vue';
import RocketLaunch from 'vue-material-design-icons/RocketLaunch.vue';
import ShieldCheck from 'vue-material-design-icons/ShieldCheck.vue';
import OfficeBuilding from 'vue-material-design-icons/OfficeBuilding.vue';
import Newspaper from 'vue-material-design-icons/Newspaper.vue';
import Bullhorn from 'vue-material-design-icons/Bullhorn.vue';
import Forum from 'vue-material-design-icons/Forum.vue';
import Palette from 'vue-material-design-icons/Palette.vue';
import CodeTags from 'vue-material-design-icons/CodeTags.vue';
import Lifebuoy from 'vue-material-design-icons/Lifebuoy.vue';
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
import Contacts from 'vue-material-design-icons/Contacts.vue';
import MessageText from 'vue-material-design-icons/MessageText.vue';

export default {
  name: 'LinksEditor',
  components: {
    NcButton,
    NcModal,
    NcSelect,
    Plus,
    Delete,
    ViewGrid,
    DragVertical,
    PageTreeSelect,
    draggable,
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
    OpenInNew,
    HelpCircle,
    AccountMultiple,
    AccountTie,
    RocketLaunch,
    ShieldCheck,
    OfficeBuilding,
    Newspaper,
    Bullhorn,
    Forum,
    Palette,
    CodeTags,
    Lifebuoy,
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
    Contacts,
    MessageText
  },
  props: {
    widget: {
      type: Object,
      required: true
    }
  },
  emits: ['close', 'save'],
  data() {
    return {
      localWidget: JSON.parse(JSON.stringify(this.widget))
    };
  },
  computed: {
    iconOptions() {
      return [
        { value: 'home', label: this.t('Home') },
        { value: 'information', label: this.t('Information') },
        { value: 'email', label: this.t('Email') },
        { value: 'phone', label: this.t('Phone') },
        { value: 'web', label: this.t('Website') },
        { value: 'file-document', label: this.t('Document') },
        { value: 'download', label: this.t('Download') },
        { value: 'help', label: this.t('Help') },
        { value: 'cog', label: this.t('Settings') },
        { value: 'account', label: this.t('Account') },
        { value: 'account-group', label: this.t('Team') },
        { value: 'calendar', label: this.t('Calendar') },
        { value: 'chart-line', label: this.t('Analytics') },
        { value: 'briefcase', label: this.t('Business') },
        { value: 'school', label: this.t('Education') },
        { value: 'star', label: this.t('Favorite') },
        { value: 'heart', label: this.t('Like') },
        { value: 'lightbulb-on', label: this.t('Idea') },
        { value: 'rocket', label: this.t('Launch') },
        { value: 'rocket-launch', label: this.t('Rocket launch') },
        { value: 'archive', label: this.t('Archive') },
        { value: 'open-in-new', label: this.t('External link') },
        { value: 'help-circle', label: this.t('Help circle') },
        { value: 'account-multiple', label: this.t('People') },
        { value: 'account-tie', label: this.t('Manager') },
        { value: 'shield-check', label: this.t('Security') },
        { value: 'shield-account', label: this.t('Admin') },
        { value: 'office-building', label: this.t('Organization') },
        { value: 'newspaper', label: this.t('News') },
        { value: 'bullhorn', label: this.t('Announcement') },
        { value: 'forum', label: this.t('Forum') },
        { value: 'palette', label: this.t('Design') },
        { value: 'code-tags', label: this.t('Code') },
        { value: 'lifebuoy', label: this.t('Support') },
        { value: 'folder', label: this.t('Folder') },
        { value: 'folder-multiple', label: this.t('Folders') },
        { value: 'chat', label: this.t('Chat') },
        { value: 'message-text', label: this.t('Message') },
        { value: 'view-column', label: this.t('Kanban') },
        { value: 'form-select', label: this.t('Form') },
        { value: 'clipboard-check', label: this.t('Tasks') },
        { value: 'view-dashboard', label: this.t('Dashboard') },
        { value: 'image-multiple', label: this.t('Photos') },
        { value: 'draw', label: this.t('Draw') },
        { value: 'puzzle', label: this.t('Plugin') },
        { value: 'robot', label: this.t('AI / Robot') },
        { value: 'table-large', label: this.t('Table') },
        { value: 'book-open-variant', label: this.t('Knowledge base') },
        { value: 'poll', label: this.t('Poll') },
        { value: 'calendar-clock', label: this.t('Appointment') },
        { value: 'earth', label: this.t('Globe') },
        { value: 'apps', label: this.t('Apps') },
        { value: 'cloud', label: this.t('Cloud') },
        { value: 'information-outline', label: this.t('Info') },
        { value: 'contacts', label: this.t('Contacts') }
      ];
    }
  },
  mounted() {
    // Ensure items array exists
    if (!this.localWidget.items) {
      this.localWidget.items = [];
    }
    // Set default columns if not set
    if (!this.localWidget.columns) {
      this.localWidget.columns = 2;
    }
    // Set default layout if not set
    if (!this.localWidget.layout) {
      this.localWidget.layout = 'list';
    }
    // Initialize iconObject for each link from the icon string value
    // Also ensure text field exists (fallback from title for backwards compatibility)
    if (this.localWidget.items) {
      this.localWidget.items.forEach((link, index) => {
        // Ensure each item has an id for drag-and-drop
        if (!link.id) {
          link.id = Date.now() + index + Math.random().toString(36).substr(2, 9);
        }
        if (link.icon && !link.iconObject) {
          link.iconObject = this.iconOptions.find(opt => opt.value === link.icon);
        }
        // Ensure text field exists (use title as fallback for backwards compatibility)
        if (!link.text && link.title) {
          link.text = link.title;
        }
        // Ensure uniqueId field exists (for backwards compatibility with older links)
        if (link.uniqueId === undefined) {
          link.uniqueId = null;
        }
        // Convert legacy #uniqueId URLs to uniqueId field
        if (link.url && link.url.startsWith('#') && !link.uniqueId) {
          link.uniqueId = link.url.substring(1);
          link.url = '';
        }
      });
    }
  },
  methods: {
    t(key, vars = {}) {
      return t('intravox', key, vars);
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
        'rocket-launch': 'RocketLaunch',
        'archive': 'Archive',
        'open-in-new': 'OpenInNew',
        'help-circle': 'HelpCircle',
        'account-multiple': 'AccountMultiple',
        'account-tie': 'AccountTie',
        'shield-check': 'ShieldCheck',
        'shield-account': 'ShieldAccount',
        'office-building': 'OfficeBuilding',
        'newspaper': 'Newspaper',
        'bullhorn': 'Bullhorn',
        'forum': 'Forum',
        'palette': 'Palette',
        'code-tags': 'CodeTags',
        'lifebuoy': 'Lifebuoy',
        'folder': 'Folder',
        'folder-multiple': 'FolderMultiple',
        'chat': 'Chat',
        'message-text': 'MessageText',
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
        'apps': 'Apps',
        'cloud': 'Cloud',
        'information-outline': 'InformationOutline',
        'contacts': 'Contacts'
      };
      return iconMap[iconName] || 'OpenInNew';
    },
    addLink() {
      if (!this.localWidget.items) {
        this.localWidget.items = [];
      }
      this.localWidget.items.push({
        id: Date.now() + Math.random().toString(36).substr(2, 9),
        title: '',
        text: '',
        url: '',
        uniqueId: null,
        icon: 'open-in-new',
        iconObject: this.iconOptions.find(opt => opt.value === 'open-in-new'),
        backgroundColor: null
      });
    },
    updatePageLink(index, page) {
      if (page) {
        this.localWidget.items[index].uniqueId = page.uniqueId;
        this.localWidget.items[index].url = '';
        // Auto-fill text if empty
        if (!this.localWidget.items[index].text) {
          this.localWidget.items[index].text = page.title;
        }
      } else {
        this.localWidget.items[index].uniqueId = null;
      }
    },
    clearPageLink(index) {
      // Clear uniqueId when URL is entered
      if (this.localWidget.items[index].url) {
        this.localWidget.items[index].uniqueId = null;
      }
    },
    updateLinkIcon(index, iconObject) {
      // Sync the selected icon object back to the string value
      this.localWidget.items[index].icon = iconObject ? iconObject.value : null;
      this.localWidget.items[index].iconObject = iconObject;
    },
    removeLink(index) {
      this.localWidget.items.splice(index, 1);
    },
    setBackgroundColor(color) {
      this.localWidget.backgroundColor = color;
    },
    setLinkBackground(index, color) {
      this.localWidget.items[index].backgroundColor = color;
    },
    save() {
      // Clean up iconObject property before saving (it's only for NcSelect display)
      const cleanWidget = JSON.parse(JSON.stringify(this.localWidget));
      if (cleanWidget.items) {
        cleanWidget.items.forEach(link => {
          delete link.iconObject;
        });
      }
      this.$emit('save', cleanWidget);
      this.$emit('close');
    }
  }
};
</script>

<style scoped>
.links-editor-modal :deep(.modal-container) {
  max-width: 900px !important;
}

.links-editor-content {
  padding: 20px;
  max-height: 80vh;
  overflow-y: auto;
}

.form-section {
  margin-bottom: 30px;
  padding-bottom: 20px;
  border-bottom: 1px solid var(--color-border);
}

.form-section:last-of-type {
  border-bottom: none;
}

.form-section h3 {
  margin: 0 0 15px 0;
  font-size: 16px;
  font-weight: 600;
  color: var(--color-main-text);
}

.section-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 15px;
}

.section-header h3 {
  margin: 0;
}

.form-group {
  margin-bottom: 15px;
}

.form-group label {
  display: block;
  margin-bottom: 6px;
  font-weight: 500;
  font-size: 13px;
  color: var(--color-main-text);
}

.widget-input {
  width: 100%;
  padding: 8px 12px;
  border: 2px solid var(--color-border-dark);
  border-radius: var(--border-radius-large);
  background: var(--color-main-background);
  color: var(--color-main-text);
  font-size: 14px;
}

.widget-input:focus {
  outline: none;
  border-color: var(--color-primary-element);
}

.color-presets {
  display: flex;
  gap: 8px;
}

.color-preset-btn {
  flex: 1;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 6px;
  padding: 8px 10px;
  border: 2px solid var(--color-border);
  border-radius: var(--border-radius);
  background: var(--color-main-background);
  color: var(--color-main-text);
  cursor: pointer;
  font-size: 13px;
  transition: all 0.2s;
}

.color-preset-btn.small {
  padding: 6px 10px;
  font-size: 12px;
}

.color-preset-btn:hover {
  border-color: var(--color-primary-element-light);
}

.color-preset-btn.active {
  border-color: var(--color-primary);
  background: var(--color-primary-element-light);
}

.color-swatch {
  display: inline-block;
  width: 14px;
  height: 14px;
  border-radius: 50%;
  flex-shrink: 0;
  border: 1px solid var(--color-border-dark);
}

.color-swatch--none {
  background: var(--color-main-background);
  background-image: linear-gradient(135deg, transparent 45%, var(--color-error) 45%, var(--color-error) 55%, transparent 55%);
}

.color-swatch--light {
  background: var(--color-background-hover);
}

.color-swatch--accent {
  background: var(--color-primary-element-light);
  border-color: var(--color-primary-element);
}

.color-swatch--primary {
  background: var(--color-primary-element);
  border-color: var(--color-primary-element);
}

.links-list {
  display: flex;
  flex-direction: column;
  gap: 15px;
}

.link-editor-item {
  display: flex;
  gap: 15px;
  align-items: flex-start;
  padding: 15px;
  background: var(--color-background-hover);
  border: 1px solid var(--color-border);
  border-radius: var(--border-radius-large);
}

.drag-handle {
  flex-shrink: 0;
  cursor: grab;
  padding: 4px;
  color: var(--color-text-maxcontrast);
  border-radius: var(--border-radius);
  transition: all 0.2s;
}

.drag-handle:hover {
  background: var(--color-background-dark);
  color: var(--color-main-text);
}

.drag-handle:active {
  cursor: grabbing;
}

/* Sortable ghost styling (during drag) */
.sortable-ghost {
  opacity: 0.5;
  background: var(--color-primary-element-light);
}

.sortable-drag {
  opacity: 1;
}

.link-fields {
  flex: 1;
}

.form-row {
  display: flex;
  gap: 15px;
  margin-bottom: 10px;
}

.form-row:last-child {
  margin-bottom: 0;
}

.flex-1 {
  flex: 1;
}

.remove-link-btn {
  flex-shrink: 0;
  padding: 8px;
  background: var(--color-error-hover, rgba(229, 57, 53, 0.1));
  border: 1px solid var(--color-error);
  color: var(--color-error);
  cursor: pointer;
  border-radius: var(--border-radius);
  transition: all 0.2s;
}

.remove-link-btn:hover {
  background: var(--color-error);
  color: white;
}

.icon-option {
  display: flex;
  align-items: center;
  gap: 8px;
}

.link-target-options {
  display: flex;
  align-items: center;
  gap: 8px;
}

.or-separator {
  color: var(--color-text-lighter);
  font-size: 12px;
  text-transform: uppercase;
  flex-shrink: 0;
}

.page-selector {
  flex: 2;
  min-width: 250px;
}

.url-input {
  flex: 1;
  min-width: 180px;
}

.url-input:disabled,
.page-selector.is-disabled {
  opacity: 0.5;
  pointer-events: none;
}

.empty-state {
  text-align: center;
  padding: 40px 20px;
  color: var(--color-text-maxcontrast);
}

.empty-state svg {
  margin-bottom: 10px;
  color: var(--color-text-maxcontrast);
}

.empty-state p {
  margin: 0;
  font-size: 14px;
}

.modal-footer {
  display: flex;
  justify-content: flex-end;
  gap: 10px;
  padding-top: 20px;
  border-top: 1px solid var(--color-border);
  margin-top: 20px;
}

/* Mobile responsiveness */
@media (max-width: 768px) {
  .form-row {
    flex-direction: column;
    gap: 10px;
  }

  .link-editor-item {
    flex-direction: column;
  }

  .remove-link-btn {
    align-self: flex-end;
  }
}
</style>
