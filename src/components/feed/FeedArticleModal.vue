<template>
  <NcModal
    size="large"
    :name="item.title"
    @close="$emit('close')"
  >
    <div class="feed-article">
      <!--
        The source line sits above the title, not beside it. As a flex sibling
        it took a column of its own: measured at 630px modal width, an 85px
        link cost the headline 101px over the full height of the header. A
        headline is the widest thing on the page and should get that width.

        The link out still appears twice — measured over 50 articles the median
        runs to three screens and half need three or more, so one only at the
        end is unreachable without scrolling past the whole piece. Here it is
        part of the dateline a reader scans anyway.
      -->
      <header class="feed-article-header">
        <p class="feed-article-dateline">
          <a
            v-if="item.url"
            :href="item.url"
            target="_blank"
            rel="noopener noreferrer"
            class="feed-article-source-top"
          >
            {{ feedSource || item.source || t('intravox', 'Website') }}
            <OpenInNew :size="13" />
          </a>
          <span v-if="item.url && meta" class="feed-article-dot">·</span>
          <span v-if="meta">{{ meta }}</span>
        </p>
        <h2 class="feed-article-title">{{ item.title }}</h2>
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
    /**
     * The feed's name, for the dateline.
     *
     * RSS puts this on the channel, not on each item, so the widget reads it
     * once and passes it down — item.source is empty for most feeds.
     */
    feedSource: {
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
/*
 * Styled to read as an article, not as a text field.
 *
 * Everything here is a Nextcloud theme variable rather than a fixed colour, so
 * the modal follows the instance's theme and dark mode without a second set of
 * rules. What is not themed is the measure and the rhythm: those belong to
 * reading, and the defaults of a UI component are tuned for forms.
 */
.feed-article {
  /* Centred with a generous measure: the container is as wide as the screen
     allows, the text is as wide as is comfortable to read. */
  max-width: 68ch;
  margin: 0 auto;
  padding: 32px 24px 24px;
  max-height: 80vh;
  overflow-y: auto;
  color: var(--color-main-text);
}

.feed-article-header {
  margin-bottom: 24px;
}

/*
 * Source and date above the title, the way a newspaper sets a dateline. Small
 * and quiet: it orients the reader before the headline, then gets out of the
 * way.
 */
.feed-article-dateline {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: 6px;
  margin: 0 0 8px 0;
  font-size: 13px;
  color: var(--color-text-maxcontrast);
}

.feed-article-source-top {
  display: inline-flex;
  align-items: center;
  gap: 4px;
  color: var(--color-primary-element);
  font-weight: 500;
  text-decoration: none;
}

.feed-article-source-top:hover,
.feed-article-source-top:focus-visible {
  text-decoration: underline;
}

.feed-article-dot {
  opacity: 0.6;
}

.feed-article-title {
  margin: 0;
  /* Larger and tighter than a UI heading: this is the one thing on screen
     that should look like a headline. */
  font-size: 28px;
  font-weight: 700;
  line-height: 1.25;
  letter-spacing: -0.01em;
  color: var(--color-main-text);
}

.feed-article-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 8px;
  padding: 48px 0;
  color: var(--color-text-maxcontrast);
}

.feed-article-body {
  /* 17px rather than the 14px a UI uses: a paragraph read start to finish
     wants a larger type size than a label glanced at. */
  font-size: 17px;
  line-height: 1.7;
}

.feed-article-body :deep(p) {
  margin: 0 0 1.1em 0;
}

/* The opening paragraph carries the piece; a standfirst weight says so. */
.feed-article-body :deep(p:first-of-type) {
  font-size: 19px;
  line-height: 1.6;
  color: var(--color-main-text);
}

.feed-article-body :deep(h1),
.feed-article-body :deep(h2),
.feed-article-body :deep(h3),
.feed-article-body :deep(h4) {
  margin: 1.8em 0 0.5em;
  font-weight: 600;
  line-height: 1.3;
}

.feed-article-body :deep(h1),
.feed-article-body :deep(h2) { font-size: 21px; }
.feed-article-body :deep(h3),
.feed-article-body :deep(h4) { font-size: 18px; }

.feed-article-body :deep(a) {
  color: var(--color-primary-element);
  text-decoration: underline;
  text-underline-offset: 2px;
}

.feed-article-body :deep(ul),
.feed-article-body :deep(ol) {
  margin: 0 0 1.1em 0;
  padding-inline-start: 1.4em;
}

.feed-article-body :deep(li) {
  margin-bottom: 0.4em;
}

/* A pull quote, not an indented block of code. */
.feed-article-body :deep(blockquote) {
  margin: 1.5em 0;
  padding: 4px 0 4px 20px;
  border-inline-start: 3px solid var(--color-primary-element);
  font-size: 18px;
  font-style: italic;
  color: var(--color-text-maxcontrast);
}

.feed-article-body :deep(blockquote p:last-child) {
  margin-bottom: 0;
}

.feed-article-body :deep(pre) {
  overflow-x: auto;
  margin: 1.2em 0;
  padding: 14px 16px;
  background: var(--color-background-dark);
  border-radius: var(--border-radius-large);
  font-size: 14px;
  line-height: 1.5;
}

.feed-article-body :deep(code) {
  padding: 2px 5px;
  background: var(--color-background-dark);
  border-radius: var(--border-radius);
  font-size: 0.9em;
}

.feed-article-body :deep(pre code) {
  padding: 0;
  background: none;
}

.feed-article-body :deep(table) {
  display: block;
  overflow-x: auto;
  max-width: 100%;
  margin: 1.2em 0;
  border-collapse: collapse;
  font-size: 15px;
}

.feed-article-body :deep(th),
.feed-article-body :deep(td) {
  padding: 8px 12px;
  border: 1px solid var(--color-border);
  text-align: start;
}

.feed-article-body :deep(th) {
  background: var(--color-background-hover);
  font-weight: 600;
}

.feed-article-body :deep(hr) {
  margin: 2em 0;
  border: none;
  border-top: 1px solid var(--color-border);
}

.feed-article-footer {
  margin-top: 32px;
  padding-top: 20px;
  border-top: 1px solid var(--color-border);
}

.feed-article-source-link {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  padding: 8px 16px;
  background: var(--color-primary-element);
  border-radius: var(--border-radius-pill, 100px);
  color: var(--color-primary-element-text);
  font-weight: 500;
  text-decoration: none;
}

.feed-article-source-link:hover,
.feed-article-source-link:focus-visible {
  background: var(--color-primary-element-hover, var(--color-primary-element));
  opacity: 0.9;
}

@media (max-width: 600px) {
  .feed-article {
    padding: 20px 16px 16px;
    max-height: 88vh;
  }

  .feed-article-title {
    font-size: 22px;
  }

  .feed-article-body {
    font-size: 16px;
  }

  .feed-article-body :deep(p:first-of-type) {
    font-size: 17px;
  }
}
</style>
