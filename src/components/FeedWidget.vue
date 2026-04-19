<template>
  <div class="feed-widget">
    <div v-if="loading" class="feed-widget-loading">
      <NcLoadingIcon :size="32" />
      <p>{{ t('Loading feed...') }}</p>
    </div>

    <div v-else-if="error" class="feed-widget-error">
      <AlertCircle :size="32" />
      <p>{{ error }}</p>
    </div>

    <div v-else-if="items.length === 0" class="feed-widget-empty">
      <RssBox :size="32" />
      <p>{{ t('No items found') }}</p>
    </div>

    <component
      v-else
      :is="layoutComponent"
      :items="items"
      :widget="widget"
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
    'widget.sourceType'() {
      this.fetchFeed();
    },
    'widget.feedUrl'() {
      this.fetchFeed();
    },
    'widget.connectionId'() {
      this.fetchFeed();
    },
    'widget.courseId'() {
      this.fetchFeed();
    },
    'widget.contentType'() {
      this.fetchFeed();
    },
    'widget.limit'() {
      this.fetchFeed();
    },
    'widget.sortBy'() {
      this.fetchFeed();
    },
    'widget.sortOrder'() {
      this.fetchFeed();
    },
    'widget.filterKeyword'() {
      this.fetchFeed();
    },
  },
  mounted() {
    this.fetchFeed();
  },
  methods: {
    t(text) {
      return window.t ? window.t('intravox', text) : text;
    },
    async fetchFeed() {
      this.loading = true;
      this.error = null;

      try {
        const sourceType = this.widget.sourceType || 'rss';
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
          this.error = response.data.error;
          this.items = [];
        } else {
          this.items = response.data.items || [];
        }
      } catch (err) {
        console.error('[FeedWidget] Failed to fetch feed:', err);
        this.error = this.t('Failed to load feed');
        this.items = [];
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
  color: var(--color-error);
}
</style>
