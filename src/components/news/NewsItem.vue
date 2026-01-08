<template>
  <a
    :href="itemUrl"
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
  background: rgba(255, 255, 255, 0.1);
  border-color: rgba(255, 255, 255, 0.2);
}

.news-item--bg-dark:hover {
  background: rgba(255, 255, 255, 0.15);
  border-color: rgba(255, 255, 255, 0.4);
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
}

.news-item--bg-dark .news-item-title {
  color: var(--color-primary-element-text);
}

.news-item--bg-dark:hover .news-item-title {
  color: var(--color-primary-element-text);
}

.news-item--bg-dark .news-item-date {
  color: rgba(255, 255, 255, 0.8);
}

.news-item--bg-dark .news-item-excerpt {
  color: rgba(255, 255, 255, 0.8);
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
  height: 80px;
  border-radius: var(--border-radius);
  overflow: hidden;
  background: var(--color-background-dark);
}

.news-item--compact .news-item-image {
  width: 80px;
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

.news-item--compact .news-item-title {
  font-size: 14px;
  -webkit-line-clamp: 1;
}

.news-item:hover .news-item-title {
  color: var(--color-primary);
}

/* Dark mode: keep white text on hover */
.news-item--bg-dark:hover .news-item-title {
  color: var(--color-primary-element-text);
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
  color: var(--color-text-light);
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
