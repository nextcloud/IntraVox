<template>
  <a
    :href="itemUrl"
    :data-agl-entity="item.entity || 'all'" :data-excom-only="item.excomOnly === true ? 'true' : 'false'"
    class="news-item"
    :class="[
      { 'news-item--compact': compact, 'news-item--no-image': !showImage || !item.imagePath },
      `news-item--bg-${itemBackground}`
    ]"
    @click="handleClick"
  >
    <div v-if="showImage && item.imagePath" class="news-item-image">
      <img :src="imageUrl" :alt="item.title" loading="lazy" />
    </div>
    <div class="news-item-content">
      <h4 class="news-item-title">{{ item.title }}</h4>
        <span v-if="entityLabel" class="agl-entity-badge" :data-entity="badgeEntity">{{ entityLabel }}</span>
      <div v-if="showDate" class="news-item-date">
        <CalendarBlank :size="14" />
        <span>{{ item.modifiedFormatted }}</span>
      </div>
      <p v-if="showExcerpt && item.excerpt" class="news-item-excerpt">
        {{ truncatedExcerpt }}
      </p>
    </div>
  </a>
</template>

<script>
import { generateUrl } from '@nextcloud/router';
import CalendarBlank from 'vue-material-design-icons/CalendarBlank.vue';
import { translate } from '@nextcloud/l10n';

export default {
  name: 'NewsItem',
  components: {
    CalendarBlank,
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
    showDate: {
      type: Boolean,
      default: true,
    },
    showExcerpt: {
      type: Boolean,
      default: true,
    },
    excerptLength: {
      type: Number,
      default: 100,
    },
    compact: {
      type: Boolean,
      default: false,
    },
    itemBackground: {
      type: String,
      default: 'default',
      validator: (value) => ['default', 'transparent', 'white', 'dark'].includes(value),
    },
  },
  emits: ['navigate'],
  computed: {
    badgeEntity() {
      return this.item.excomOnly ? 'excom' : (this.item.entity || 'all');
    },
    entityLabel() {
      return {
        all: translate('intravox', 'All Authorized'),
        'cloud-carib': translate('intravox', 'Cloud Carib'),
        'athena-cloud': translate('intravox', 'Athena Cloud'),
        excom: translate('intravox', 'ExCom'),
      }[this.badgeEntity] || '';
    },
    itemUrl() {
      return `#${this.item.uniqueId}`;
    },
    imageUrl() {
      if (!this.item.imagePath) return '';
      return generateUrl(this.item.imagePath);
    },
    truncatedExcerpt() {
      if (!this.item.excerpt) return '';
      if (this.item.excerpt.length <= this.excerptLength) {
        return this.item.excerpt;
      }
      return this.item.excerpt.substring(0, this.excerptLength) + '...';
    },
  },
  methods: {
    handleClick(event) {
      event.preventDefault();
      this.$emit('navigate', this.item.uniqueId);
    },
  },
};
</script>

<style scoped>
/* Base news-item styles */
.news-item {
  display: flex;
  gap: 16px;
  padding: 16px;
  border: 1px solid var(--color-border);
  border-radius: var(--border-radius-large);
  text-decoration: none;
  color: inherit;
  transition: all 0.2s ease;
  min-width: 0;
  overflow: hidden;
  box-sizing: border-box;
}

/* Default background (light gray) - for backwards compatibility */
.news-item--bg-default {
  background: var(--color-background-hover);
}

.news-item--bg-default:hover {
  border-color: var(--color-primary);
  background: var(--color-primary-element-light);
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
}

/* Transparent background - for "None" option, inherits parent background */
.news-item--bg-transparent {
  background: transparent;
}

.news-item--bg-transparent:hover {
  background: var(--color-background-hover);
  border-color: var(--color-primary);
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
}

/* White background - for "Light" container option */
.news-item--bg-white {
  background: var(--color-main-background);
}

.news-item--bg-white:hover {
  border-color: var(--color-primary);
  background: var(--color-primary-element-light);
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
}

/* Dark/Primary background - semi-transparent white for dark containers */
.news-item--bg-dark {
  background: var(--color-primary-element-light);
  border-color: var(--color-primary-element-light);
}

/* Stay on the LIGHT surface when hovering. --color-primary-element-hover is a
   dark blue, which flipped the card from light to dark while the text kept its
   dark light-surface colour (≈1.7:1 — unreadable). The light hover token keeps
   the card stable and readable (≈11.6:1 for the title). */
.news-item--bg-dark:hover {
  background: var(--color-primary-element-light-hover);
  border-color: var(--color-primary-element-light-hover);
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
}

/* The card itself is LIGHT (--color-primary-element-light) even though the
   container behind it is dark, so its text must use the matching light-surface
   token. Using --color-primary-element-text here painted white on a light card
   (≈1.2:1 contrast — effectively invisible, WCAG 2.1 AA needs 4.5:1).
   --color-primary-element-light-text is Nextcloud's designated partner for this
   surface and yields ≈13:1. Opacity is not used to de-emphasise text: it eats
   contrast: --color-text-maxcontrast is the accessible way to do that. */
.news-item--bg-dark .news-item-title {
  color: var(--color-primary-element-light-text);
}

.news-item--bg-dark:hover .news-item-title {
  color: var(--color-primary-element-light-text);
}

/* --color-text-maxcontrast only just clears AA on the resting card (4.57:1) and
   drops below it on the hover tint (4.09:1), so use the full text colour. These
   stay visually secondary through their smaller font size, not through a weaker
   contrast. */
.news-item--bg-dark .news-item-date {
  color: var(--color-main-text);
}

.news-item--bg-dark .news-item-excerpt {
  color: var(--color-main-text);
}

.news-item--compact {
  padding: 12px;
  gap: 12px;
}

.news-item--no-image {
  flex-direction: column;
}

.news-item-image {
  flex-shrink: 0;
  width: 120px;
  max-width: 100%;
  height: 80px;
  border-radius: var(--border-radius);
  overflow: hidden;
  background: var(--color-background-dark);
}

.news-item--compact .news-item-image {
  width: 80px;
  max-width: 100%;
  height: 60px;
}

.news-item-image img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.news-item-content {
  flex: 1;
  min-width: 0;
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.news-item-title {
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
}

.news-item--compact .news-item-title {
  font-size: 13px;
  -webkit-line-clamp: 1;
}

.news-item:hover .news-item-title {
  color: var(--color-primary);
}

/* Card on a dark container: keep the light-surface text colour on hover too.
   (This rule comes after the one above and would otherwise override it.) */
.news-item--bg-dark:hover .news-item-title {
  color: var(--color-primary-element-light-text);
}

.news-item-date {
  display: flex;
  align-items: center;
  gap: 4px;
  font-size: 12px;
  color: var(--color-text-maxcontrast);
}

.news-item-excerpt {
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

.news-item--compact .news-item-excerpt {
  display: none;
}

@media (max-width: 600px) {
  .news-item {
    flex-direction: column;
    gap: 12px;
  }

  .news-item-image {
    width: 100%;
    height: 160px;
  }
}
</style>
