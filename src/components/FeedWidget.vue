<template>
  <div class="feed-widget" aria-live="polite">
    <div v-if="loading" class="feed-widget-loading" role="status">
      <NcLoadingIcon :size="32" />
      <p>{{ t('Loading feed...') }}</p>
    </div>

    <div v-else-if="error" class="feed-widget-error">
      <AlertCircle :size="32" />
      <p>{{ error }}</p>
    </div>

    <div v-else-if="items.length === 0" class="feed-widget-empty">
      <RssBox :size="32" />
      <p v-if="widget.filterKeyword">{{ t('No items match your filter.') }}</p>
      <p v-else>{{ t('No items found') }}</p>
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
import { generateUrl } from '@nextcloud/router';
import { NcLoadingIcon } from '@nextcloud/vue';
import AlertCircle from 'vue-material-design-icons/AlertCircle.vue';
import RssBox from 'vue-material-design-icons/RssBox.vue';
import FeedLayoutList from './feed/FeedLayoutList.vue';
import FeedLayoutGrid from './feed/FeedLayoutGrid.vue';

export default {
  name: 'FeedWidget',
  components: {
    NcLoadingIcon,
    AlertCircle,
    RssBox,
    FeedLayoutList,
    FeedLayoutGrid,
  },
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
    layoutComponent() {
      const layouts = {
        list: FeedLayoutList,
        grid: FeedLayoutGrid,
      };
      return layouts[this.widget.layout] || FeedLayoutList;
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
    t(text) {
      return window.t ? window.t('intravox', text) : text;
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
            this.error = this.t('This connection is currently disabled by an administrator.');
          } else if (err.includes('not found') || err.includes('404')) {
            this.error = this.t('Connection no longer exists. Please reconfigure this widget.');
          } else if (err.includes('token') || err.includes('401') || err.includes('Authentication')) {
            this.error = this.t('Authentication required. Please connect your account.');
          } else if (err.includes('403') || err.includes('Access denied')) {
            this.error = this.t('Access denied. Check the connection permissions.');
          } else if (err.includes('429') || err.includes('Rate limited')) {
            this.error = this.t('Too many requests. Please try again later.');
          } else {
            this.error = this.t('Could not load feed. Check the connection settings.');
          }
          this.items = [];
          this.feedImage = null;
        } else {
          this.items = response.data.items || [];
          this.feedImage = response.data.feedImage || null;
        }
      } catch (err) {
        this.error = this.t('Could not load feed. The external system may be unavailable.');
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
