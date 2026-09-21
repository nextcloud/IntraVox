<template>
  <!--
    No URL means a <span>, not an <a>: href="" resolves to the current document,
    so items without a link navigated back to the page they sat on (#114).
  -->
  <component
    :is="item.url ? 'a' : 'span'"
    :href="item.url || undefined"
    class="feed-item"
    :aria-label="item.title + (formattedDate ? ' — ' + formattedDate : '')"
    :class="[
      { 'feed-item--compact': compact, 'feed-item--no-image': !showImage || (!item.image && (!feedImage || feedImageError) && !fallbackMeta) },
      { 'feed-item--no-link': !item.url },
      { 'feed-item--has-article': item.hasArticle },
      `feed-item--bg-${itemBackground}`
    ]"
    :target="item.url && openInNewTab ? '_blank' : undefined"
    :rel="item.url && openInNewTab ? 'noopener noreferrer' : undefined"
    @click="onClick"
  >
    <div v-if="showImage && item.image" class="feed-item-image">
      <img :src="item.image" :alt="item.title" loading="lazy" referrerpolicy="no-referrer" />
    </div>
    <div v-else-if="showImage && feedImage && !feedImageError" class="feed-item-feed-icon">
      <img :src="feedImage" :alt="item.source || ''" loading="lazy" referrerpolicy="no-referrer" @error="feedImageError = true" />
    </div>
    <div v-else-if="showImage && fallbackMeta" class="feed-item-fallback" :style="{ backgroundColor: fallbackMeta.color }">
      <component :is="fallbackMeta.icon" :size="compact ? 20 : 22" class="feed-item-fallback-icon" />
      <span v-if="item.fileType" class="feed-item-fallback-label">{{ item.fileType.toUpperCase() }}</span>
    </div>
    <div class="feed-item-content">
      <h4 class="feed-item-title">{{ item.title }}</h4>
      <div class="feed-item-meta">
        <span v-if="showDate && item.date" class="feed-item-date">
          <CalendarBlank :size="14" />
          <span>{{ formattedDate }}</span>
        </span>
        <span v-if="showSource && item.source" class="feed-item-source">
          {{ item.source }}
        </span>
        <span v-if="item.author" class="feed-item-author">
          {{ item.author }}
        </span>
      </div>
      <p v-if="showExcerpt && item.excerpt" class="feed-item-excerpt">
        {{ truncatedExcerpt }}
      </p>
    </div>
    <!--
      One icon, not two controls. An item that carries an article opens it in
      place; one without keeps linking out, exactly as before. A separate
      button under every item cost 42px each — 18% of an item's height, and
      redundant besides: 82% of items measured carry a body, so the button was
      the rule rather than the exception.
    -->
    <!--
      The tooltip sits on a wrapping span, not on the icon component:
      vue-material-design-icons takes `title` as a prop and renders it as a
      <title> inside the SVG, which is not a hover tooltip. Measured — the
      attribute came back null on the element.
    -->
    <span
      v-if="item.hasArticle"
      class="feed-item-article-icon"
      :title="t('intravox', 'Opens here, without leaving the page')"
    >
      <TextBoxOutline :size="16" />
    </span>
    <OpenInNew v-else-if="openInNewTab" :size="14" class="feed-item-external-icon" />
  </component>
</template>

<script>
import CalendarBlank from 'vue-material-design-icons/CalendarBlank.vue';
import OpenInNew from 'vue-material-design-icons/OpenInNew.vue';
import TextBoxOutline from 'vue-material-design-icons/TextBoxOutline.vue';
import { translate } from '@nextcloud/l10n';
import FileWord from 'vue-material-design-icons/FileWord.vue';
import FileExcel from 'vue-material-design-icons/FileExcel.vue';
import FilePowerpoint from 'vue-material-design-icons/FilePowerpoint.vue';
import FilePdfBox from 'vue-material-design-icons/FilePdfBox.vue';
import FileImage from 'vue-material-design-icons/FileImage.vue';
import FileVideo from 'vue-material-design-icons/FileVideo.vue';
import FileDocument from 'vue-material-design-icons/FileDocument.vue';
import BugOutline from 'vue-material-design-icons/BugOutline.vue';
import BookOpenPageVariant from 'vue-material-design-icons/BookOpenPageVariant.vue';
import MicrosoftSharepoint from 'vue-material-design-icons/MicrosoftSharepoint.vue';
import ClipboardText from 'vue-material-design-icons/ClipboardText.vue';
import SchoolOutline from 'vue-material-design-icons/SchoolOutline.vue';
import RssBox from 'vue-material-design-icons/RssBox.vue';
import ViewDashboard from 'vue-material-design-icons/ViewDashboard.vue';

const FILE_TYPE_MAP = {
  doc: { color: '#2B579A', icon: FileWord },
  docx: { color: '#2B579A', icon: FileWord },
  xls: { color: '#217346', icon: FileExcel },
  xlsx: { color: '#217346', icon: FileExcel },
  csv: { color: '#217346', icon: FileExcel },
  ppt: { color: '#D24726', icon: FilePowerpoint },
  pptx: { color: '#D24726', icon: FilePowerpoint },
  pdf: { color: '#E2574C', icon: FilePdfBox },
  jpg: { color: '#7B83EB', icon: FileImage },
  jpeg: { color: '#7B83EB', icon: FileImage },
  png: { color: '#7B83EB', icon: FileImage },
  gif: { color: '#7B83EB', icon: FileImage },
  webp: { color: '#7B83EB', icon: FileImage },
  svg: { color: '#7B83EB', icon: FileImage },
  mp4: { color: '#8764B8', icon: FileVideo },
  mov: { color: '#8764B8', icon: FileVideo },
  avi: { color: '#8764B8', icon: FileVideo },
  webm: { color: '#8764B8', icon: FileVideo },
};

const CONNECTION_TYPE_MAP = {
  jira: { color: '#0052CC', icon: BugOutline },
  confluence: { color: '#1868DB', icon: BookOpenPageVariant },
  sharepoint: { color: '#038387', icon: MicrosoftSharepoint },
  openproject: { color: '#1A67A3', icon: ClipboardText },
  moodle: { color: '#F98012', icon: SchoolOutline },
  canvas: { color: '#E03E2D', icon: SchoolOutline },
  brightspace: { color: '#F5A623', icon: SchoolOutline },
  rss: { color: '#F26522', icon: RssBox },
  custom: { color: '#6C757D', icon: ViewDashboard },
};

export default {
  name: 'FeedItem',
  components: {
    CalendarBlank,
    OpenInNew,
    TextBoxOutline,
    FileWord, FileExcel, FilePowerpoint, FilePdfBox, FileImage, FileVideo, FileDocument,
    BugOutline, BookOpenPageVariant, MicrosoftSharepoint, ClipboardText, SchoolOutline, RssBox, ViewDashboard,
  },
  emits: ['open-article'],
  data() {
    return {
      feedImageError: false,
    };
  },
  props: {
    item: {
      type: Object,
      required: true,
    },
    showImage: {
      type: Boolean,
      default: true,
    },
    feedImage: {
      type: String,
      default: null,
    },
    showDate: {
      type: Boolean,
      default: true,
    },
    showExcerpt: {
      type: Boolean,
      default: true,
    },
    showSource: {
      type: Boolean,
      default: false,
    },
    excerptLength: {
      type: Number,
      default: 150,
    },
    compact: {
      type: Boolean,
      default: false,
    },
    openInNewTab: {
      type: Boolean,
      default: true,
    },
    itemBackground: {
      type: String,
      default: 'default',
      validator: (value) => ['default', 'transparent', 'white', 'dark'].includes(value),
    },
  },
  methods: {
    t: translate,
    /**
     * Open the article in place, but only for a plain left click.
     *
     * Middle click, ctrl/cmd click and shift click keep doing what they do to
     * any link — a new tab or window. Swallowing those would break an
     * expectation people carry to every link on the web, and the href stays on
     * the element so the browser can honour them.
     */
    onClick(event) {
      if (!this.item.hasArticle) {
        return;
      }
      if (event.metaKey || event.ctrlKey || event.shiftKey || event.altKey || event.button !== 0) {
        return;
      }
      event.preventDefault();
      this.$emit('open-article', this.item);
    },
  },
  computed: {
    formattedDate() {
      if (!this.item.date) return '';
      try {
        const date = new Date(this.item.date);
        const locale = document.documentElement.lang || undefined;
        return date.toLocaleDateString(locale, {
          year: 'numeric',
          month: 'short',
          day: 'numeric',
        });
      } catch {
        return this.item.date;
      }
    },
    fallbackMeta() {
      if (this.item.image) return null;
      if (this.feedImage && !this.feedImageError) return null;
      if (this.item.fileType && FILE_TYPE_MAP[this.item.fileType]) {
        return FILE_TYPE_MAP[this.item.fileType];
      }
      if (this.item.connectionType && CONNECTION_TYPE_MAP[this.item.connectionType]) {
        return CONNECTION_TYPE_MAP[this.item.connectionType];
      }
      return { color: '#6C757D', icon: FileDocument };
    },
    truncatedExcerpt() {
      if (!this.item.excerpt) return '';
      if (this.item.excerpt.length <= this.excerptLength) {
        return this.item.excerpt;
      }
      return this.item.excerpt.substring(0, this.excerptLength) + '...';
    },
  },
};
</script>

<style scoped>
.feed-item {
  display: flex;
  gap: 16px;
  padding: 16px;
  border: 1px solid var(--color-border);
  border-radius: var(--border-radius-large);
  text-decoration: none;
  color: inherit;
  transition: all 0.2s ease;
  position: relative;
  min-width: 0;
  overflow: hidden;
  box-sizing: border-box;
}

.feed-item:focus-visible {
  outline: 2px solid var(--color-primary);
  outline-offset: 2px;
}

.feed-item--bg-default {
  background: var(--color-background-hover);
}

.feed-item--bg-default:hover {
  border-color: var(--color-primary);
  background: var(--color-primary-element-light);
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
}

.feed-item--bg-transparent {
  background: transparent;
}

.feed-item--bg-transparent:hover {
  background: var(--color-background-hover);
  border-color: var(--color-primary);
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
}

.feed-item--bg-white {
  background: var(--color-main-background);
}

.feed-item--bg-white:hover {
  border-color: var(--color-primary);
  background: var(--color-primary-element-light);
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
}

.feed-item--bg-dark {
  background: rgba(255, 255, 255, 0.15);
  border-color: rgba(255, 255, 255, 0.2);
}

.feed-item--bg-dark:hover {
  background: rgba(255, 255, 255, 0.25);
  border-color: rgba(255, 255, 255, 0.35);
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
}

.feed-item--bg-dark .feed-item-title {
  color: white;
}

.feed-item--bg-dark:hover .feed-item-title {
  color: white;
}

.feed-item--bg-dark .feed-item-meta,
.feed-item--bg-dark .feed-item-excerpt {
  color: rgba(255, 255, 255, 0.85);
}

.feed-item--compact {
  padding: 12px;
  gap: 12px;
}

.feed-item--no-image {
  flex-direction: column;
}

.feed-item-image {
  flex-shrink: 0;
  width: 120px;
  max-width: 100%;
  height: 80px;
  border-radius: var(--border-radius);
  overflow: hidden;
  background: var(--color-background-dark);
}

.feed-item--compact .feed-item-image {
  width: 80px;
  height: 60px;
}

.feed-item-image img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.feed-item-feed-icon {
  flex-shrink: 0;
  width: 40px;
  height: 40px;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 8px;
  overflow: hidden;
  align-self: flex-start;
  margin-top: 2px;
  background: var(--color-background-dark);
}

.feed-item--compact .feed-item-feed-icon {
  width: 32px;
  height: 32px;
  border-radius: 6px;
}

.feed-item-feed-icon img {
  width: 100%;
  height: 100%;
  object-fit: contain;
}

.feed-item-fallback {
  flex-shrink: 0;
  width: 40px;
  height: 40px;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 1px;
  color: white;
  border-radius: 8px;
  align-self: flex-start;
  margin-top: 2px;
}

.feed-item--compact .feed-item-fallback {
  width: 32px;
  height: 32px;
  border-radius: 6px;
}

.feed-item-fallback-icon {
  opacity: 0.9;
}

.feed-item-fallback-label {
  font-size: 7px;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.3px;
  opacity: 0.9;
  line-height: 1;
}

.feed-item-content {
  flex: 1;
  min-width: 0;
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.feed-item-title {
  margin: 0;
  font-size: 14px;
  font-weight: 600;
  color: var(--color-main-text);
  line-height: 1.3;
  overflow: hidden;
  text-overflow: ellipsis;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow-wrap: break-word;
  word-break: break-word;
}

.feed-item--compact .feed-item-title {
  font-size: 13px;
  -webkit-line-clamp: 1;
}

.feed-item:hover .feed-item-title {
  color: var(--color-primary);
}

.feed-item--bg-dark:hover .feed-item-title {
  color: var(--color-primary-element-text);
}

.feed-item-meta {
  display: flex;
  align-items: center;
  gap: 12px;
  font-size: 12px;
  color: var(--color-text-maxcontrast);
  flex-wrap: wrap;
}

.feed-item-date {
  display: flex;
  align-items: center;
  gap: 4px;
}

.feed-item-source {
  font-style: italic;
}

.feed-item-excerpt {
  margin: 4px 0 0 0;
  font-size: 13px;
  color: var(--color-text-maxcontrast);
  line-height: 1.4;
  overflow: hidden;
  text-overflow: ellipsis;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
}

.feed-item--compact .feed-item-excerpt {
  display: none;
}

.feed-item-external-icon {
  position: absolute;
  top: 12px;
  right: 12px;
  color: var(--color-text-maxcontrast);
  opacity: 0;
  transition: opacity 0.2s;
}

/*
 * The article marker, unlike the external-link icon, is always visible.
 *
 * That difference is the point: the link icon only confirms on hover what a
 * link already does, while this one tells you *before* you click that the item
 * behaves differently — it opens here instead of sending you to the site. An
 * affordance you can only discover by hovering is no affordance on a phone,
 * where there is no hover at all.
 */
/*
 * An item that opens in place gets a left edge in the primary colour.
 *
 * The hover state alone would not do: on a touch screen there is none, and the
 * reader deserves to know which of two behaviours a tap will produce before
 * they tap. The border is 3px and only on the inline-start edge, so it reads as
 * a marker rather than as a selected state.
 */
.feed-item--has-article {
  border-inline-start: 3px solid var(--color-primary-element);
}

.feed-item--has-article:hover {
  background: var(--color-primary-element-light);
}

.feed-item-article-icon {
  position: absolute;
  top: 12px;
  right: 12px;
  color: var(--color-primary-element);
  opacity: 0.65;
  transition: opacity 0.2s, transform 0.2s;
}

.feed-item:hover .feed-item-article-icon {
  opacity: 1;
  transform: scale(1.12);
}

/* On a coloured band the primary colour disappears; use the paired text tone. */
.feed-item--bg-dark .feed-item-article-icon {
  color: var(--color-primary-element-text);
}

.feed-item:hover .feed-item-external-icon {
  opacity: 1;
}

/* Container query: medium width (250-400px) — compact mode */
@container (max-width: 400px) {
  .feed-item {
    padding: 12px;
    gap: 10px;
  }

  .feed-item-image {
    width: 80px;
    height: 60px;
  }

  .feed-item-title {
    font-size: 13px;
  }

  .feed-item-meta {
    font-size: 11px;
    gap: 8px;
  }

  .feed-item-excerpt {
    display: none;
  }
}

/* Container query: narrow width (<250px) — compact vertical for photos only */
@container (max-width: 250px) {
  .feed-item {
    padding: 10px;
    gap: 8px;
  }

  .feed-item-title {
    font-size: 12px;
  }

  .feed-item-meta {
    font-size: 10px;
  }
}

@media (max-width: 600px) {
  .feed-item {
    flex-direction: column;
    gap: 12px;
  }

  .feed-item-image {
    width: 100%;
    height: 160px;
  }
}




</style>
