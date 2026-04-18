<template>
  <a
    :href="item.url"
    class="feed-item"
    :class="[
      { 'feed-item--compact': compact, 'feed-item--no-image': !showImage || !item.image },
      `feed-item--bg-${itemBackground}`
    ]"
    :target="openInNewTab ? '_blank' : '_self'"
    :rel="openInNewTab ? 'noopener noreferrer' : undefined"
  >
    <div v-if="showImage && item.image" class="feed-item-image">
      <img :src="item.image" :alt="item.title" loading="lazy" referrerpolicy="no-referrer" />
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
    <OpenInNew v-if="openInNewTab" :size="14" class="feed-item-external-icon" />
  </a>
</template>

<script>
import CalendarBlank from 'vue-material-design-icons/CalendarBlank.vue';
import OpenInNew from 'vue-material-design-icons/OpenInNew.vue';

export default {
  name: 'FeedItem',
  components: {
    CalendarBlank,
    OpenInNew,
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
  computed: {
    formattedDate() {
      if (!this.item.date) return '';
      try {
        const date = new Date(this.item.date);
        return date.toLocaleDateString(undefined, {
          year: 'numeric',
          month: 'short',
          day: 'numeric',
        });
      } catch {
        return this.item.date;
      }
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
  background: rgba(255, 255, 255, 0.1);
  border-color: rgba(255, 255, 255, 0.2);
}

.feed-item--bg-dark:hover {
  background: rgba(255, 255, 255, 0.15);
  border-color: rgba(255, 255, 255, 0.4);
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
}

.feed-item--bg-dark .feed-item-title {
  color: var(--color-primary-element-text);
}

.feed-item--bg-dark:hover .feed-item-title {
  color: var(--color-primary-element-text);
}

.feed-item--bg-dark .feed-item-meta,
.feed-item--bg-dark .feed-item-excerpt {
  color: rgba(255, 255, 255, 0.8);
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

.feed-item-content {
  flex: 1;
  min-width: 0;
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.feed-item-title {
  margin: 0;
  font-size: 15px;
  font-weight: 600;
  color: var(--color-main-text);
  line-height: 1.3;
  overflow: hidden;
  text-overflow: ellipsis;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
}

.feed-item--compact .feed-item-title {
  font-size: 14px;
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
  color: var(--color-text-light);
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

.feed-item:hover .feed-item-external-icon {
  opacity: 1;
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
