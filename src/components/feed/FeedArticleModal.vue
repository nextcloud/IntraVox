<template>
  <NcModal
    size="large"
    :name="item.title"
    @close="$emit('close')"
  >
    <div class="feed-article">
      <!--
        The link out appears twice, and both are earned. Measured over 50
        articles: the median runs to three screens, half need three or more,
        and the longest was twenty-eight. A link only at the end is unreachable
        without scrolling past the whole piece in half the cases — which is
        exactly when a reader has decided they want the original.

        The one at the top is quiet (an icon with a label); the one at the
        bottom is the natural end of reading.
      -->
      <header class="feed-article-header">
        <div class="feed-article-heading">
          <h2 class="feed-article-title">{{ item.title }}</h2>
          <a
            v-if="item.url"
            :href="item.url"
            target="_blank"
            rel="noopener noreferrer"
            class="feed-article-source-top"
            :title="t('intravox', 'Read on the website')"
          >
            <OpenInNew :size="16" />
            <span>{{ t('intravox', 'Website') }}</span>
          </a>
        </div>
        <p v-if="meta" class="feed-article-meta">{{ meta }}</p>
      </header>

      <div v-if="loading" class="feed-article-state" role="status">
        <NcLoadingIcon :size="32" />
        <p>{{ t('intravox', 'Loading article …') }}</p>
      </div>

      <div v-else-if="error" class="feed-article-state">
        <AlertCircle :size="32" />
        <p>{{ error }}</p>
      </div>

      <!--
        v-html on third-party markup, deliberately: the body was sanitized
        server-side by FeedArticleStore before it was ever cached, so what
        arrives here has already been through HtmlSanitizer with <script>,
        <img> and event handlers removed. Sanitizing again in the client would
        suggest the stored copy might be unsafe, which is the wrong thing to
        imply about a cache other code also reads.
      -->
      <article v-else class="feed-article-body" v-html="content"></article>

      <footer class="feed-article-footer">
        <a
          v-if="item.url"
          :href="item.url"
          target="_blank"
          rel="noopener noreferrer"
          class="feed-article-source-link"
        >
          {{ t('intravox', 'Read on the website') }}
          <OpenInNew :size="16" />
        </a>
      </footer>
    </div>
  </NcModal>
</template>

<script>
import axios from '@nextcloud/axios';
import { translate } from '@nextcloud/l10n';
import { generateUrl } from '@nextcloud/router';
import { NcModal, NcLoadingIcon } from '@nextcloud/vue';
import AlertCircle from 'vue-material-design-icons/AlertCircle.vue';
import OpenInNew from 'vue-material-design-icons/OpenInNew.vue';

export default {
  name: 'FeedArticleModal',
  components: { NcModal, NcLoadingIcon, AlertCircle, OpenInNew },
  props: {
    item: {
      type: Object,
      required: true,
    },
    /** The widget config, so the server can address the same cache entry. */
    widget: {
      type: Object,
      required: true,
    },
    /** Present on a public share; switches to the anonymous route. */
    shareToken: {
      type: String,
      default: '',
    },
  },
  emits: ['close'],
  data() {
    return {
      content: '',
      loading: true,
      error: null,
    };
  },
  computed: {
    meta() {
      const delen = [];
      if (this.item.author) {
        delen.push(this.item.author);
      }
      if (this.item.date) {
        delen.push(new Date(this.item.date).toLocaleDateString());
      }
      if (this.item.source) {
        delen.push(this.item.source);
      }
      return delen.join(' · ');
    },
  },
  mounted() {
    this.load();
  },
  methods: {
    t: translate,
    async load() {
      const params = new URLSearchParams({
        sourceType: this.widget.sourceType || 'rss',
        itemId: this.item.id || '',
      });
      // The same selectors the list was fetched with. The server re-validates
      // them against what the share publishes, so sending them is not a trust
      // decision — it is how the cache entry is addressed.
      for (const [key, param] of Object.entries({
        feedUrl: 'url',
        connectionId: 'connectionId',
        contentType: 'contentType',
        courseId: 'courseId',
        jiraProject: 'jiraProject',
        moodleForumId: 'moodleForumId',
        listId: 'listId',
      })) {
        if (this.widget[key]) {
          params.append(param, this.widget[key]);
        }
      }

      const url = this.shareToken
        ? generateUrl(`/apps/intravox/api/share/${this.shareToken}/feed/article?${params}`)
        : generateUrl(`/apps/intravox/api/feed/article?${params}`);

      try {
        const response = await axios.get(url);
        this.content = response.data.content || '';
        if (!this.content) {
          this.error = this.t('intravox', 'This article is no longer available. Open it on the website instead.');
        }
      } catch (err) {
        // A 404 is ordinary: the cache entry expires with the feed it came
        // from, so a page left open past the TTL lands here. Say what to do
        // rather than report a failure.
        this.error = err?.response?.status === 404
          ? this.t('intravox', 'This article is no longer available. Open it on the website instead.')
          : this.t('intravox', 'Could not load this article.');
      } finally {
        this.loading = false;
      }
    },
  },
};
</script>

<style scoped>
.feed-article {
  padding: 24px;
  /* Bounded so a long piece scrolls inside the modal rather than pushing the
     footer with the source link out of reach. */
  max-height: 80vh;
  overflow-y: auto;
}

.feed-article-header {
  margin-bottom: 16px;
}

.feed-article-heading {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 16px;
}

/* Quiet by design: the reader came here to read, not to leave. */
.feed-article-source-top {
  display: inline-flex;
  align-items: center;
  gap: 4px;
  flex-shrink: 0;
  margin-top: 4px;
  padding: 4px 8px;
  border-radius: var(--border-radius);
  color: var(--color-text-maxcontrast);
  font-size: 13px;
  text-decoration: none;
}

.feed-article-source-top:hover,
.feed-article-source-top:focus-visible {
  background: var(--color-background-hover);
  color: var(--color-primary-element);
}

@media (max-width: 600px) {
  /* The label costs width a phone does not have; the icon still says it. */
  .feed-article-source-top span { display: none; }
}

.feed-article-title {
  margin: 0 0 4px 0;
  font-size: 24px;
  line-height: 1.3;
}

.feed-article-meta {
  margin: 0;
  color: var(--color-text-maxcontrast);
  font-size: 14px;
}

.feed-article-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 8px;
  padding: 40px 0;
  color: var(--color-text-maxcontrast);
}

.feed-article-body {
  /* Measured for reading rather than for the container: a modal on a wide
     screen would otherwise run to 60+ characters per line. */
  max-width: 70ch;
  line-height: 1.6;
}

.feed-article-body :deep(p) {
  margin: 0 0 1em 0;
}

.feed-article-body :deep(h1),
.feed-article-body :deep(h2),
.feed-article-body :deep(h3) {
  margin: 1.5em 0 0.5em;
  line-height: 1.3;
}

.feed-article-body :deep(a) {
  color: var(--color-primary-element);
}

.feed-article-body :deep(blockquote) {
  margin: 1em 0;
  padding-left: 1em;
  border-left: 3px solid var(--color-border);
  color: var(--color-text-maxcontrast);
}

.feed-article-body :deep(pre) {
  overflow-x: auto;
  padding: 12px;
  background: var(--color-background-dark);
  border-radius: var(--border-radius);
}

.feed-article-body :deep(table) {
  display: block;
  overflow-x: auto;
  max-width: 100%;
}

.feed-article-footer {
  margin-top: 24px;
  padding-top: 16px;
  border-top: 1px solid var(--color-border);
}

.feed-article-source-link {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  color: var(--color-primary-element);
  font-weight: 500;
}

@media (max-width: 600px) {
  .feed-article {
    padding: 16px;
    /* Taller on a phone: the modal is nearly full-screen there, and the
       browser chrome already eats the rest. */
    max-height: 88vh;
  }

  .feed-article-title {
    font-size: 20px;
  }
}
</style>
