<template>
  <div
    class="template-preview-card"
    :class="{ selected }"
    @click="$emit('select')"
  >
    <!-- Visual Preview - SVG Layout Visualization -->
    <div class="preview-visual">
      <svg class="layout-preview" viewBox="0 0 200 120" preserveAspectRatio="xMidYMid meet">
        <!-- Background -->
        <rect x="0" y="0" width="200" height="120" fill="#f8f9fa" />

        <!-- Header row (if present) -->
        <g v-if="template.preview?.hasHeaderRow">
          <rect x="4" y="4" width="192" height="24" rx="2" :fill="primaryColor" opacity="0.3" />
          <rect x="8" y="8" width="60" height="6" rx="1" fill="#6c757d" opacity="0.5" />
          <rect x="8" y="17" width="100" height="4" rx="1" fill="#6c757d" opacity="0.3" />
        </g>

        <!-- Main content area -->
        <g :transform="template.preview?.hasHeaderRow ? 'translate(0, 28)' : 'translate(0, 0)'">
          <!-- Columns visualization -->
          <g v-for="(col, index) in columnCount" :key="'col-' + index">
            <rect
              :x="4 + (index * (contentWidth / columnCount))"
              :y="4"
              :width="(contentWidth / columnCount) - 4"
              :height="contentHeight"
              rx="2"
              fill="white"
              stroke="#dee2e6"
              stroke-width="1"
            />
            <!-- Widget placeholders in column -->
            <g v-for="w in 3" :key="'widget-' + index + '-' + w">
              <rect
                :x="8 + (index * (contentWidth / columnCount))"
                :y="8 + ((w - 1) * 22)"
                :width="(contentWidth / columnCount) - 16"
                :height="18"
                rx="2"
                :fill="w === 1 ? primaryColor : '#e9ecef'"
                :opacity="w === 1 ? 0.2 : 0.6"
              />
              <rect
                :x="12 + (index * (contentWidth / columnCount))"
                :y="12 + ((w - 1) * 22)"
                :width="Math.min(40, (contentWidth / columnCount) - 24)"
                :height="4"
                rx="1"
                fill="#6c757d"
                opacity="0.4"
              />
              <rect
                :x="12 + (index * (contentWidth / columnCount))"
                :y="18 + ((w - 1) * 22)"
                :width="(contentWidth / columnCount) - 28"
                :height="3"
                rx="1"
                fill="#adb5bd"
                opacity="0.3"
              />
            </g>
          </g>

          <!-- Sidebar indicator -->
          <g v-if="template.preview?.hasSidebars">
            <rect x="168" y="4" width="28" height="88" rx="2" fill="#fff3cd" stroke="#ffc107" stroke-width="1" />
            <rect x="172" y="10" width="20" height="4" rx="1" fill="#856404" opacity="0.5" />
            <rect x="172" y="18" width="20" height="3" rx="1" fill="#856404" opacity="0.3" />
            <rect x="172" y="28" width="20" height="3" rx="1" fill="#856404" opacity="0.3" />
          </g>
        </g>

        <!-- Collapsible sections indicator -->
        <g v-if="template.preview?.hasCollapsible">
          <rect x="4" y="100" width="192" height="16" rx="2" fill="#e3f2fd" stroke="#90caf9" stroke-width="1" />
          <polygon points="12,106 18,110 12,114" fill="#1976d2" />
          <rect x="24" y="107" width="50" height="4" rx="1" fill="#1976d2" opacity="0.5" />
        </g>
      </svg>

      <!-- Widget type badges -->
      <div class="widget-badges">
        <span
          v-for="type in uniqueWidgetTypes.slice(0, 4)"
          :key="type"
          class="widget-badge"
          :title="type"
        >
          <component :is="getWidgetIcon(type)" :size="12" />
        </span>
        <span v-if="uniqueWidgetTypes.length > 4" class="widget-badge more">
          +{{ uniqueWidgetTypes.length - 4 }}
        </span>
      </div>
    </div>

    <!-- Info Section -->
    <div class="info-section">
      <strong class="title">{{ translatedTitle }}</strong>
      <p v-if="translatedDescription" class="description">
        {{ translatedDescription }}
      </p>
      <div class="meta">
        <span class="stat">
          <ViewGridOutline :size="12" />
          {{ columnCount }} {{ t('columns') }}
        </span>
        <span class="stat">
          <WidgetsOutline :size="12" />
          {{ template.preview?.widgetCount || 0 }}
        </span>
        <span
          class="complexity-badge"
          :data-complexity="template.preview?.complexity || 'simple'"
        >
          {{ complexityLabel }}
        </span>
      </div>
    </div>
  </div>
</template>

<script>
import { translate } from '@nextcloud/l10n';
import FormatTitle from 'vue-material-design-icons/FormatTitle.vue';
import TextBoxOutline from 'vue-material-design-icons/TextBoxOutline.vue';
import ImageOutline from 'vue-material-design-icons/ImageOutline.vue';
import VideoOutline from 'vue-material-design-icons/VideoOutline.vue';
import NewspaperVariantOutline from 'vue-material-design-icons/NewspaperVariantOutline.vue';
import LinkVariant from 'vue-material-design-icons/LinkVariant.vue';
import Minus from 'vue-material-design-icons/Minus.vue';
import ViewGridOutline from 'vue-material-design-icons/ViewGridOutline.vue';
import WidgetsOutline from 'vue-material-design-icons/WidgetsOutline.vue';

export default {
  name: 'TemplatePreviewCard',
  components: {
    FormatTitle,
    TextBoxOutline,
    ImageOutline,
    VideoOutline,
    NewspaperVariantOutline,
    LinkVariant,
    Minus,
    ViewGridOutline,
    WidgetsOutline
  },
  props: {
    template: {
      type: Object,
      required: true
    },
    selected: {
      type: Boolean,
      default: false
    }
  },
  emits: ['select'],
  computed: {
    columnCount() {
      return Math.min(this.template.preview?.columnCount || 1, 4);
    },
    contentWidth() {
      return this.template.preview?.hasSidebars ? 160 : 192;
    },
    contentHeight() {
      const hasHeader = this.template.preview?.hasHeaderRow;
      const hasCollapsible = this.template.preview?.hasCollapsible;
      let height = 88;
      if (hasHeader) height -= 0;
      if (hasCollapsible) height -= 20;
      return Math.max(height, 60);
    },
    uniqueWidgetTypes() {
      return this.template.preview?.widgetTypes || [];
    },
    primaryColor() {
      // Extract primary color from template or use default
      return '#0082c9';
    },
    complexityLabel() {
      const complexity = this.template.preview?.complexity || 'simple';
      const labels = {
        simple: this.t('Simple'),
        moderate: this.t('Medium'),
        complex: this.t('Advanced')
      };
      return labels[complexity] || complexity;
    },
    translatedTitle() {
      // Try to get translated title using template_<id>_title key
      const translationKey = `template_${this.template.id}_title`;
      const translated = this.t(translationKey);
      // If translation key returns the same string (not found), use original title
      return translated !== translationKey ? translated : (this.template.title || this.template.id);
    },
    translatedDescription() {
      // Try to get translated description using template_<id>_description key
      const translationKey = `template_${this.template.id}_description`;
      const translated = this.t(translationKey);
      // If translation key returns the same string (not found), use original description
      return translated !== translationKey ? translated : (this.template.description || '');
    }
  },
  methods: {
    t(key, vars = {}) {
      return translate('intravox', key, vars);
    },
    getWidgetIcon(type) {
      const icons = {
        heading: 'FormatTitle',
        text: 'TextBoxOutline',
        image: 'ImageOutline',
        video: 'VideoOutline',
        news: 'NewspaperVariantOutline',
        links: 'LinkVariant',
        divider: 'Minus'
      };
      return icons[type] || 'TextBoxOutline';
    }
  }
};
</script>

<style scoped>
.template-preview-card {
  display: flex;
  flex-direction: column;
  border: 2px solid var(--color-border);
  border-radius: var(--border-radius-large);
  overflow: hidden;
  cursor: pointer;
  transition: all 0.2s ease;
  background: var(--color-main-background);
  min-width: 200px;
}

.template-preview-card:hover {
  border-color: var(--color-primary);
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.12);
  transform: translateY(-3px);
}

.template-preview-card.selected {
  border-color: var(--color-primary-element);
  box-shadow: 0 0 0 3px var(--color-primary-element-light);
}

.preview-visual {
  height: 130px;
  background: linear-gradient(135deg, #f8f9fa 0%, #ffffff 100%);
  position: relative;
  overflow: hidden;
  border-bottom: 1px solid var(--color-border);
  padding: 8px;
}

.layout-preview {
  width: 100%;
  height: 100%;
  border-radius: 4px;
  box-shadow: inset 0 0 0 1px rgba(0, 0, 0, 0.05);
}

.widget-badges {
  position: absolute;
  bottom: 8px;
  right: 8px;
  display: flex;
  gap: 3px;
  align-items: center;
}

.widget-badge {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 22px;
  height: 22px;
  background: white;
  border: 1px solid var(--color-border);
  border-radius: 4px;
  color: var(--color-primary-element);
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.widget-badge.more {
  width: auto;
  padding: 0 6px;
  font-size: 10px;
  font-weight: 600;
  color: var(--color-text-maxcontrast);
}

.info-section {
  padding: 12px;
  flex: 1;
  display: flex;
  flex-direction: column;
  gap: 6px;
}

.title {
  font-size: 14px;
  color: var(--color-main-text);
  line-height: 1.3;
  word-break: break-word;
  margin: 0;
  font-weight: 600;
}

.description {
  font-size: 12px;
  color: var(--color-text-maxcontrast);
  margin: 0;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
  line-height: 1.4;
}

.meta {
  margin-top: auto;
  padding-top: 8px;
  display: flex;
  align-items: center;
  gap: 8px;
  flex-wrap: wrap;
}

.stat {
  display: flex;
  align-items: center;
  gap: 3px;
  font-size: 11px;
  color: var(--color-text-maxcontrast);
}

.complexity-badge {
  display: inline-block;
  padding: 2px 8px;
  border-radius: 10px;
  font-weight: 500;
  font-size: 10px;
  text-transform: uppercase;
  letter-spacing: 0.3px;
  margin-left: auto;
}

.complexity-badge[data-complexity="simple"] {
  background: #d4edda;
  color: #155724;
}

.complexity-badge[data-complexity="moderate"] {
  background: #fff3cd;
  color: #856404;
}

.complexity-badge[data-complexity="complex"] {
  background: #cce5ff;
  color: #004085;
}
</style>
