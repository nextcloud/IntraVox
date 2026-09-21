<template>
  <div class="feed-widget" aria-live="polite">
    <!--
      Outside the loading/error/empty branches on purpose: a feed that is
      still loading or failed to load needs its name most of all. Inside,
      a broken feed would render as an anonymous error box, and on a page
      with several feeds side by side you could not tell which one broke.
    -->
    <h3 v-if="widget.title && widget.showTitle !== false" class="feed-widget-title" :style="titleStyle">
      {{ widget.title }}
    </h3>

    <div v-if="loading" class="feed-widget-loading" role="status">
      <NcLoadingIcon :size="32" />
      <p>{{ t('intravox', 'Loading feed …') }}</p>
    </div>

    <div v-else-if="error" class="feed-widget-error">
      <AlertCircle :size="32" />
      <p>{{ error }}</p>
    </div>

    <div v-else-if="items.length === 0" class="feed-widget-empty">
      <RssBox :size="32" />
      <p v-if="widget.filterKeyword">{{ t('intravox', 'No items match your filter.') }}</p>
      <p v-else>{{ t('intravox', 'No items found') }}</p>
    </div>

    <component
      v-else
      :is="layoutComponent"
      :items="items"
      :widget="widget"
      :feed-image="feedImage"
      :row-background-color="rowBackgroundColor"
    />
  </div>
</template>

<script>
import axios from '@nextcloud/axios';
import { translate } from '@nextcloud/l10n';
import { generateUrl } from '@nextcloud/router';
import { NcLoadingIcon } from '@nextcloud/vue';
import AlertCircle from 'vue-material-design-icons/AlertCircle.vue';
import RssBox from 'vue-material-design-icons/RssBox.vue';
import FeedLayoutList from './feed/FeedLayoutList.vue';
import FeedLayoutGrid from './feed/FeedLayoutGrid.vue';
import { titleStyleFor } from '../utils/colorUtils.js';

export default {
  name: 'FeedWidget',
  components: {
    NcLoadingIcon,
    AlertCircle,
    RssBox,
    FeedLayoutList,
    FeedLayoutGrid,
  },
  emits: ['feed-name'],
  props: {
    widget: {
      type: Object,
      required: true,
    },
    shareToken: {
      type: String,
      default: '',
    },
    pageId: {
      type: String,
      default: '',
    },
    rowBackgroundColor: {
      type: String,
      default: '',
    },
  },
  data() {
    return {
      items: [],
      feedImage: null,
      loading: true,
      error: null,
    };
  },
  computed: {
    /**
     * Title colour that survives a coloured row.
     *
     * Same mapping as NewsWidget/PeopleWidget/CalendarWidget: on a primary or
     * otherwise dark band the default text colour disappears, so the paired
     * *-text variable is used instead. Kept identical to those three on purpose
     * — a feed title on a primary row should not read differently from a news
     * title on the same row.
     */
    titleStyle() {
      const bgColor = this.widget.backgroundColor || this.rowBackgroundColor || '';
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
      return textColor ? { color: textColor } : {};
    },
    layoutComponent() {
      const layouts = {
        list: FeedLayoutList,
        grid: FeedLayoutGrid,
      };
      return layouts[this.widget.layout] || FeedLayoutList;
    },
    titleStyle() {
      return titleStyleFor(this.widget.backgroundColor, this.rowBackgroundColor);
    },
  },
  watch: {
    widget: {
      handler() {
        clearTimeout(this._debounceTimer);
        this._debounceTimer = setTimeout(() => this.fetchFeed(), 300);
      },
      deep: true,
    },
  },
  mounted() {
    if (typeof requestIdleCallback === 'function') {
      requestIdleCallback(() => this.fetchFeed());
    } else {
      this.fetchFeed();
    }
    // Auto-refresh every 15 minutes (matches backend cache TTL)
    this._refreshInterval = setInterval(() => this.fetchFeed(), 15 * 60 * 1000);
  },
  beforeUnmount() {
    clearTimeout(this._debounceTimer);
    clearInterval(this._refreshInterval);
  },
  methods: {
    t(app, text, vars) {
      return translate(app, text, vars);
    },
    async fetchFeed() {
      this.loading = true;
      this.error = null;

      try {
        let sourceType = this.widget.sourceType || 'rss';
        // Auto-detect: if a connectionId is set but sourceType is 'rss', treat as connection
        if (sourceType === 'rss' && this.widget.connectionId) {
          sourceType = 'connection';
        }
        const params = new URLSearchParams({
          sourceType,
          limit: String(this.widget.limit || 5),
        });

        if (sourceType === 'rss') {
          if (!this.widget.feedUrl) {
            this.items = [];
            this.loading = false;
            return;
          }
          params.append('url', this.widget.feedUrl);
        } else {
          if (!this.widget.connectionId) {
            this.items = [];
            this.loading = false;
            return;
          }
          params.append('connectionId', this.widget.connectionId);
          if (this.widget.courseId) {
            params.append('courseId', this.widget.courseId);
          }
          if (this.widget.contentType) {
            params.append('contentType', this.widget.contentType);
          }
          if (this.widget.jiraProject) {
            params.append('jiraProject', this.widget.jiraProject);
          }
          if (this.widget.moodleForumId) {
            params.append('moodleForumId', this.widget.moodleForumId);
          }
          if (this.widget.listId) {
            params.append('listId', this.widget.listId);
          }
        }

        // Sort and filter
        if (this.widget.sortBy) {
          params.append('sortBy', this.widget.sortBy);
        }
        if (this.widget.sortOrder) {
          params.append('sortOrder', this.widget.sortOrder);
        }
        if (this.widget.filterKeyword) {
          params.append('filterKeyword', this.widget.filterKeyword);
        }

        const url = this.shareToken
          ? generateUrl(`/apps/intravox/api/share/${this.shareToken}/feed/external?${params}`)
          : generateUrl(`/apps/intravox/api/feed/external?${params}`);

        const response = await axios.get(url);

        if (response.data.error) {
          const err = response.data.error;
          if (err.includes('inactive') || err.includes('disabled')) {
            this.error = this.t('intravox', 'This connection is currently disabled by an administrator.');
          } else if (err.includes('not found') || err.includes('404')) {
            this.error = this.t('intravox', 'Connection no longer exists. Please reconfigure this widget.');
          } else if (err.includes('token') || err.includes('401') || err.includes('Authentication')) {
            this.error = this.t('intravox', 'Authentication required. Please connect your account.');
          } else if (err.includes('403') || err.includes('Access denied')) {
            this.error = this.t('intravox', 'Access denied. Check the connection permissions.');
          } else if (err.includes('429') || err.includes('Rate limited')) {
            this.error = this.t('intravox', 'Too many requests. Please try again later.');
          } else {
            this.error = this.t('intravox', 'Could not load feed. Check the connection settings.');
          }
          this.items = [];
          this.feedImage = null;
        } else {
          this.items = response.data.items || [];
          this.feedImage = response.data.feedImage || null;
          // The feed's own <channel><title>. Only the editor listens, to offer
          // it as a suggestion for an empty widget title; the viewer ignores it.
          if (response.data.source) {
            this.$emit('feed-name', response.data.source);
          }
        }
      } catch (err) {
        this.error = this.t('intravox', 'Could not load feed. The external system may be unavailable.');
        this.items = [];
        this.feedImage = null;
      } finally {
        this.loading = false;
      }
    },
  },
};
</script>

<style scoped>
.feed-widget {
  width: 100%;
  min-width: 0;
  overflow: hidden;
}

/* Same size and rhythm as .news-widget-title and .people-widget-title, so a
   page that mixes widget types keeps one heading level visually. */
.feed-widget-title {
  margin: 0 0 16px 0;
  font-size: 18px;
  font-weight: 600;
  color: var(--color-main-text);
  /* A long feed name must not widen the column it sits in; the widget itself
     is min-width:0 for the same reason. */
  overflow-wrap: anywhere;
}

.feed-widget-loading,
.feed-widget-error,
.feed-widget-empty {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 32px;
  gap: 8px;
  color: var(--color-text-maxcontrast);
  text-align: center;
}

.feed-widget-error {
  color: var(--color-main-text);
  background: var(--color-error);
  background: color-mix(in srgb, var(--color-error) 10%, transparent);
  border: 1px solid color-mix(in srgb, var(--color-error) 30%, transparent);
  border-radius: var(--border-radius-large);
  padding: 20px 24px;
  margin: 8px;
}

.feed-widget-error p {
  margin: 0;
  font-size: 14px;
  color: var(--color-main-text);
}
</style>
