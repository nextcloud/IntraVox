<template>
  <NcModal
    size="large"
    :name="item.title"
    class="feed-article-modal"
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
 * Colours, radii and spacing come from Nextcloud theme variables, so the modal
 * follows the instance theme and dark mode without a second set of rules. What
 * the theme has no answer for is the measure and the rhythm of running text —
 * a UI component's defaults are tuned for forms — so those are set here and
 * nothing else is.
 */

/*
 * Narrow the container, do not centre inside it.
 *
 * size="large" gives 900px while the text wants ~68ch. Centring a 642px column
 * in a 900px box left 258px of empty modal and put the scrollbar an inch away
 * from the text it scrolls. Sizing the container is also how the rest of the
 * app does it — see WidgetEditor and LinksEditor.
 */
.feed-article-modal :deep(.modal-container) {
  max-width: 760px;
}

/* The scroll belongs to the modal's own content box, so the scrollbar runs
   along the modal edge rather than inside an inner div. */
.feed-article-modal :deep(.modal-container__content) {
  max-height: 85vh;
  overflow-y: auto;
}

.feed-article {
  padding: 28px 32px 32px;
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
  /* Nextcloud's own h2 scale. It was 28px/700 with tightened tracking, which
     read well but was an invention — the theme has a heading size and this
     modal should look like it belongs to the app, not to a newspaper. */
  font-size: 20px;
  font-weight: 600;
  line-height: 1.3;
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
  /* The one place this departs from UI defaults, and the only one worth it: a
     paragraph read start to finish needs more size and leading than a label
     glanced at. 16px is Nextcloud's body size; 1.7 is the leading.
     Everything else — colour, weight, headings — follows the theme. */
  font-size: 16px;
  line-height: 1.7;
}

.feed-article-body :deep(p) {
  margin: 0 0 1.1em 0;
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
.feed-article-body :deep(h2) { font-size: 18px; }
.feed-article-body :deep(h3),
.feed-article-body :deep(h4) { font-size: 16px; }

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
  padding: 4px 0 4px 16px;
  border-inline-start: 4px solid var(--color-border);
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
    padding: 20px 16px 24px;
  }

  .feed-article-title {
    font-size: 18px;
  }
}
</style>
