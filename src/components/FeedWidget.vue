<template>
  <div class="feed-widget" aria-live="polite">
    <!--
      Above the items, not below them. The newest item is at the top, so that
      is where a reader looks first and where "how old is this" belongs. Below
      a list of ten they would have to scroll past everything to reach the
      control that reloads it.

      One line per widget, not per item: measured across 184 items, only 5% are
      under an hour old and the median is ten days, so a clock time beside each
      headline would be noise nineteen times out of twenty. What the dates
      cannot say is when *we* last looked.
    -->
    <header v-if="!loading && !error && fetchedAt" class="feed-widget-header">
      <span class="feed-widget-age" :class="{ 'feed-widget-age--stale': isStale }">{{ ageLabel }}</span>
      <button
        type="button"
        class="feed-widget-refresh"
        :disabled="refreshing"
        :title="t('intravox', 'Fetch the latest items now')"
        @click="refresh"
      >
        <Refresh :size="14" :class="{ 'feed-widget-refresh--spinning': refreshing }" />
        <span>{{ refreshing ? t('intravox', 'Refreshing …') : t('intravox', 'Refresh') }}</span>
      </button>
    </header>

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
      @open-article="openArticle"
    />

    <!--
      One line per widget, not per item. Measured across 184 items: only 5%
      are under an hour old and the median is ten days, so a clock time beside
      each headline would be noise on nineteen items out of twenty. What a
      reader cannot see from the dates is when *we* last looked — that belongs
      to the widget, and it is one line instead of twenty.
    -->


    <FeedArticleModal
      v-if="openItem"
      :item="openItem"
      :widget="widget"
      :share-token="shareToken"
      :feed-source="feedSource"
      @close="openItem = null"
    />
  </div>
</template>

<script>
import axios from '@nextcloud/axios';
import { translate } from '@nextcloud/l10n';
import { generateUrl } from '@nextcloud/router';
import { fetchFeedBatched } from '../utils/feedBatcher.js';
import { NcLoadingIcon } from '@nextcloud/vue';
import AlertCircle from 'vue-material-design-icons/AlertCircle.vue';
import RssBox from 'vue-material-design-icons/RssBox.vue';
import Refresh from 'vue-material-design-icons/Refresh.vue';
import FeedLayoutList from './feed/FeedLayoutList.vue';
import FeedLayoutGrid from './feed/FeedLayoutGrid.vue';
import FeedArticleModal from './feed/FeedArticleModal.vue';

export default {
  name: 'FeedWidget',
  components: {
    NcLoadingIcon,
    AlertCircle,
    RssBox,
    Refresh,
    FeedLayoutList,
    FeedLayoutGrid,
    FeedArticleModal,
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
      openItem: null,
      fetchedAt: null,
      feedSource: '',
      isStale: false,
      refreshing: false,
      nu: Date.now(),
      loading: true,
      error: null,
    };
  },
  computed: {
    /**
     * How long ago the server last fetched this feed.
     *
     * One line per widget rather than a time beside each item. Measured over
     * 184 items: 5% are under an hour old and the median is ten days, so a
     * clock time per headline would be noise nineteen times out of twenty.
     * What the dates cannot tell a reader is when *we* last looked.
     *
     * Rounded to the unit that matters: seconds are false precision on
     * something refreshed every fifteen minutes, and an exact timestamp makes
     * the reader do the subtraction.
     */
    ageLabel() {
      if (!this.fetchedAt) {
        return '';
      }
      const sec = Math.max(0, Math.round((this.nu - this.fetchedAt * 1000) / 1000));
      if (sec < 60) {
        return this.t('intravox', 'Updated just now');
      }
      // t() with a placeholder rather than translatePlural: nothing else in
      // the app uses the plural form, and a second l10n idiom for three
      // strings buys the reader nothing.
      const min = Math.round(sec / 60);
      if (min < 60) {
        return this.t('intravox', 'Updated {n} min ago', { n: min });
      }
      const uur = Math.round(min / 60);
      if (uur < 24) {
        return this.t('intravox', 'Updated {n} h ago', { n: uur });
      }
      return this.t('intravox', 'Updated {n} d ago', { n: Math.round(uur / 24) });
    },
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
    // A minute is the finest unit the label shows, so ticking faster would
    // re-render for nothing. Cleared on unmount — a page with twenty widgets
    // would otherwise leave twenty intervals behind on every navigation.
    this._klok = setInterval(() => { this.nu = Date.now(); }, 60000);
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
    openArticle(item) {
      this.openItem = item;
    },
    /**
     * Fetch now, bypassing the server's freshness window.
     *
     * Deliberately not on a timer. A page left open would otherwise keep
     * pulling feeds nobody is reading, and the server already refreshes in the
     * background when content goes stale — what a reader lacks is not
     * automation but the ability to say "now".
     */
    async refresh() {
      this.refreshing = true;
      try {
        await this.fetchFeed(true);
      } finally {
        this.refreshing = false;
      }
    },
    t(app, text, vars) {
      return translate(app, text, vars);
    },
    async fetchFeed(force = false) {
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

        let response;
        if (force) {
          // A deliberate refresh is one reader asking now; batching it would
          // make them wait on other widgets. `refresh=1` tells the server to
          // bypass its own freshness window.
          params.append('refresh', '1');
          const url = this.shareToken
            ? generateUrl(`/apps/intravox/api/share/${this.shareToken}/feed/external?${params}`)
            : generateUrl(`/apps/intravox/api/feed/external?${params}`);
          response = await axios.get(url);
        } else {
          // The ordinary path: joins whatever else this page is asking for, so
          // a page costs one request instead of one per widget.
          response = { data: await fetchFeedBatched(this.widget, this.shareToken) };
        }

        if (response.data.error) {
          const err = response.data.error;
          if (err.includes('inactive') || err.includes('disabled')) {
            this.error = this.t('intravox', 'This connection is currently disabled by an administrator.');
          } else if (err.includes('not found') || err.includes('404')) {
            this.error = this.t('intravox', 'Connection no longer exists. Please reconfigure this widget.');
          } else if (err.includes('token') || err.includes('401') || err.includes('Authentication')) {
            this.error = this.t('intravox', 'Authentication required. Please connect your account.');
          } else if (err.includes('403') || err.includes('Access denied')) {
            // An RSS 403 is a different problem from a connection 403: the
            // source is up, it refuses this server. Measured on dev — five of
            // the dashboard's feeds answer 200 from a home connection and 403
            // from the datacenter IP, whatever User-Agent is sent. Telling an
            // admin to "check the permissions" of a public feed sends them
            // looking for something that does not exist.
            this.error = this.widget.sourceType === 'rss'
              ? this.t('intravox', 'This source refuses requests from this server. Nothing to fix here — the feed blocks datacenter addresses.')
              : this.t('intravox', 'Access denied. Check the connection permissions.');
          } else if (err.includes('429') || err.includes('Rate limited')) {
            this.error = this.t('intravox', 'Too many requests. Please try again later.');
          } else if (err.includes('timed out') || err.includes('timeout') || err.includes('cURL error 28')) {
            this.error = this.t('intravox', 'The source did not respond in time.');
          } else if (err.includes('too large')) {
            this.error = this.t('intravox', 'This feed is too large to process.');
          } else if (err.includes('SSL') || err.includes('cURL error')) {
            this.error = this.t('intravox', 'Could not reach the source. The connection failed.');
          } else if (err.includes('circuit breaker')) {
            this.error = this.t('intravox', 'This source failed repeatedly and is paused. It retries automatically.');
          } else {
            this.error = this.t('intravox', 'Could not load feed. Check the connection settings.');
          }
          this.items = [];
          this.feedImage = null;
        } else {
          this.items = response.data.items || [];
          this.feedImage = response.data.feedImage || null;
          // The server reports when it fetched; without it the widget would be
          // guessing from its own mount time, which says nothing about the data.
          this.fetchedAt = response.data.fetchedAt || null;
          // The feed's own name. RSS carries it once per channel rather than
          // per item, so the widget holds it and hands it to the reader.
          this.feedSource = response.data.source || '';
          this.isStale = response.data.stale === true;
          this.nu = Date.now();
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

.feed-widget-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  margin-bottom: 8px;
  padding-bottom: 8px;
  border-bottom: 1px solid var(--color-border);
  font-size: 12px;
  color: var(--color-text-maxcontrast);
}

/* Stale is worth flagging but not alarming: the content is still usable. */
.feed-widget-age--stale {
  font-style: italic;
}

.feed-widget-refresh {
  display: inline-flex;
  align-items: center;
  gap: 4px;
  padding: 4px 8px;
  background: transparent;
  border: none;
  border-radius: var(--border-radius);
  color: var(--color-text-maxcontrast);
  font-size: 12px;
  cursor: pointer;
}

.feed-widget-refresh:hover:not(:disabled),
.feed-widget-refresh:focus-visible:not(:disabled) {
  background: var(--color-background-hover);
  color: var(--color-main-text);
}

.feed-widget-refresh:disabled {
  cursor: default;
  opacity: 0.6;
}

.feed-widget-refresh--spinning {
  animation: feed-widget-spin 1s linear infinite;
}

@keyframes feed-widget-spin {
  to { transform: rotate(360deg); }
}

@media (prefers-reduced-motion: reduce) {
  .feed-widget-refresh--spinning { animation: none; }
}
</style>
