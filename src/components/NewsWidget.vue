<template>
  <div class="news-widget" :style="getContainerStyle()">
    <h3 v-if="widget.title" class="news-widget-title" :style="titleStyle">{{ widget.title }}</h3>

    <!-- Debug info (only in edit mode or when showDebug is true) -->
    <div v-if="showDebug" class="news-debug">
      <details>
        <summary>Debug Info</summary>
        <pre>{{ debugInfo }}</pre>
      </details>
    </div>

    <div v-if="loading" class="news-loading">
      <NcLoadingIcon :size="32" />
      <span>{{ t('Loading news...') }}</span>
    </div>

    <div v-else-if="error" class="news-error">
      <AlertCircle :size="24" />
      <span>{{ error }}</span>
      <pre v-if="errorDetails" class="error-details">{{ errorDetails }}</pre>
    </div>

    <div v-else-if="items.length === 0" class="news-empty">
      <Newspaper :size="32" />
      <p>{{ t('No news items found') }}</p>
      <small v-if="widget.sourcePageId || widget.sourcePath">{{ t('Source: {path}', { path: widget.sourcePageId || widget.sourcePath }) }}</small>
    </div>

    <component
      v-else
      :is="layoutComponent"
      :items="items"
      :widget="widget"
      :row-background-color="effectiveBackgroundColor"
      @navigate="handleNavigate"
    />
  </div>
</template>

<script>
import { NcLoadingIcon } from '@nextcloud/vue';
import { generateUrl } from '@nextcloud/router';
import { translate as t } from '@nextcloud/l10n';
import axios from '@nextcloud/axios';
import AlertCircle from 'vue-material-design-icons/AlertCircle.vue';
import Newspaper from 'vue-material-design-icons/Newspaper.vue';
import NewsLayoutList from './news/NewsLayoutList.vue';
import NewsLayoutGrid from './news/NewsLayoutGrid.vue';
import NewsLayoutCarousel from './news/NewsLayoutCarousel.vue';

export default {
  name: 'NewsWidget',
  components: {
    NcLoadingIcon,
    AlertCircle,
    Newspaper,
    NewsLayoutList,
    NewsLayoutGrid,
    NewsLayoutCarousel,
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
  emits: ['navigate'],
  data() {
    return {
      items: [],
      loading: true,
      error: null,
      errorDetails: null,
      apiResponse: null,
    };
  },
  computed: {
    layoutComponent() {
      const layouts = {
        list: NewsLayoutList,
        grid: NewsLayoutGrid,
        carousel: NewsLayoutCarousel,
      };
      return layouts[this.widget.layout] || NewsLayoutList;
    },
    effectiveBackgroundColor() {
      // Widget's own backgroundColor takes precedence over row background
      return this.widget.backgroundColor || this.rowBackgroundColor || '';
    },
    titleStyle() {
      // Map background colors to appropriate text colors for optimal contrast
      const bgColor = this.effectiveBackgroundColor;

      const colorMappings = {
        'var(--color-primary-element)': 'var(--color-primary-element-text)',
        'var(--color-primary-element-light)': 'var(--color-primary-element-light-text)',
        'var(--color-error)': 'var(--color-error-text)',
        'var(--color-warning)': 'var(--color-warning-text)',
        'var(--color-success)': 'var(--color-success-text)',
        'var(--color-background-dark)': 'var(--color-main-text)',
        'var(--color-background-hover)': 'var(--color-main-text)',
      };

      const textColor = colorMappings[bgColor];
      if (textColor) {
        return { color: textColor };
      }
      return {};
    },
    showDebug() {
      // Show debug in editable mode or when URL has ?debug=news
      return this.editable || (typeof window !== 'undefined' && window.location.search.includes('debug=news'));
    },
    debugInfo() {
      return JSON.stringify({
        widget: {
          type: this.widget.type,
          sourcePageId: this.widget.sourcePageId,
          sourcePath: this.widget.sourcePath,
          layout: this.widget.layout,
          limit: this.widget.limit,
          sortBy: this.widget.sortBy,
          sortOrder: this.widget.sortOrder,
          showImage: this.widget.showImage,
          showDate: this.widget.showDate,
          showExcerpt: this.widget.showExcerpt,
          filters: this.widget.filters,
          filterOperator: this.widget.filterOperator,
        },
        state: {
          loading: this.loading,
          error: this.error,
          itemCount: this.items.length,
        },
        apiResponse: this.apiResponse,
      }, null, 2);
    },
  },
  watch: {
    widget: {
      deep: true,
      handler() {
        this.fetchNews();
      },
    },
  },
  mounted() {
    this.fetchNews();
  },
  methods: {
    t(key, vars = {}) {
      return t('intravox', key, vars);
    },
    async fetchNews() {
      this.loading = true;
      this.error = null;
      this.errorDetails = null;
      this.apiResponse = null;

      try {
        const params = new URLSearchParams({
          filters: JSON.stringify(this.widget.filters || []),
          filterOperator: this.widget.filterOperator || 'AND',
          sortBy: this.widget.sortBy || 'modified',
          sortOrder: this.widget.sortOrder || 'desc',
          limit: this.widget.limit || 5,
          filterPublished: this.widget.filterPublished ? 'true' : 'false',
        });

        // Add sourcePageId if available (new), otherwise fall back to sourcePath (legacy)
        if (this.widget.sourcePageId) {
          params.append('sourcePageId', this.widget.sourcePageId);
        } else if (this.widget.sourcePath) {
          params.append('sourcePath', this.widget.sourcePath);
        }

        const url = this.shareToken
          ? generateUrl(`/apps/intravox/api/share/${this.shareToken}/news?${params}`)
          : generateUrl(`/apps/intravox/api/news?${params}`);
        const response = await axios.get(url);

        this.apiResponse = {
          status: response.status,
          total: response.data.total,
          metavoxAvailable: response.data.metavoxAvailable,
          itemCount: response.data.items?.length || 0,
        };

        this.items = response.data.items || [];
      } catch (err) {
        console.error('[NewsWidget] Failed to fetch news:', err);
        this.error = this.t('Failed to load news');
        this.errorDetails = err.response?.data?.error || err.message;
        this.apiResponse = {
          status: err.response?.status,
          error: err.message,
        };
      } finally {
        this.loading = false;
      }
    },
    handleNavigate(pageId) {
      this.$emit('navigate', pageId);
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
};
</script>

<style scoped>
.news-widget {
  width: 100%;
  margin: 12px 0;
  container-type: inline-size; /* Enable container queries for child layouts */
}

.news-widget-title {
  margin: 0 0 16px 0;
  font-size: 18px;
  font-weight: 600;
  color: var(--color-main-text);
}

.news-loading,
.news-error,
.news-empty {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 12px;
  padding: 40px 20px;
  color: var(--color-text-maxcontrast);
  text-align: center;
}

.news-error {
  color: var(--color-error);
}

.news-empty {
  background: var(--color-background-hover);
  border: 2px dashed var(--color-border);
  border-radius: var(--border-radius-large);
}

.news-empty p {
  margin: 0;
  font-size: 14px;
}

.news-empty small {
  margin-top: 8px;
  font-size: 12px;
  opacity: 0.7;
}

.news-debug {
  margin-bottom: 12px;
  padding: 8px;
  background: var(--color-background-dark);
  border: 1px solid var(--color-border);
  border-radius: var(--border-radius);
  font-size: 12px;
}

.news-debug summary {
  cursor: pointer;
  font-weight: 600;
  color: var(--color-primary);
}

.news-debug pre {
  margin: 8px 0 0 0;
  padding: 8px;
  background: var(--color-main-background);
  border-radius: var(--border-radius);
  overflow-x: auto;
  font-size: 11px;
  line-height: 1.4;
  white-space: pre-wrap;
  word-break: break-all;
}

.error-details {
  margin-top: 8px;
  padding: 8px;
  background: var(--color-background-dark);
  border-radius: var(--border-radius);
  font-size: 11px;
  color: var(--color-text-maxcontrast);
  white-space: pre-wrap;
  word-break: break-all;
}
</style>
