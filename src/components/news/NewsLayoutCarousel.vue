<template>
  <div class="news-layout-carousel">
    <div class="carousel-container">
      <button
        class="carousel-nav carousel-nav--prev"
        :disabled="currentIndex === 0"
        @click="prev"
      >
        <ChevronLeft :size="24" />
      </button>

      <div class="carousel-viewport" ref="viewport">
        <div
          class="carousel-track"
          :style="trackStyle"
        >
          <div
            v-for="item in items"
            :key="item.uniqueId"
            class="carousel-slide"
          >
            <a
              :href="`#${item.uniqueId}`"
              class="carousel-item"
              @click.prevent="$emit('navigate', item.uniqueId)"
            >
              <div v-if="item.imagePath" class="carousel-image">
                <img :src="getImageUrl(item)" :alt="item.title" loading="lazy" />
              </div>
              <div class="carousel-content">
                <h4 class="carousel-title">{{ item.title }}</h4>
                <div v-if="widget.showDate !== false" class="carousel-date">
                  <CalendarBlank :size="14" />
                  <span>{{ item.modifiedFormatted }}</span>
                </div>
                <p v-if="widget.showExcerpt !== false && item.excerpt" class="carousel-excerpt">
                  {{ item.excerpt }}
                </p>
              </div>
            </a>
          </div>
        </div>
      </div>

      <button
        class="carousel-nav carousel-nav--next"
        :disabled="currentIndex >= items.length - 1"
        @click="next"
      >
        <ChevronRight :size="24" />
      </button>
    </div>

    <div class="carousel-dots" v-if="items.length > 1">
      <button
        v-for="(item, index) in items"
        :key="item.uniqueId"
        class="carousel-dot"
        :class="{ 'carousel-dot--active': index === currentIndex }"
        @click="goTo(index)"
      />
    </div>
  </div>
</template>

<script>
import { generateUrl } from '@nextcloud/router';
import ChevronLeft from 'vue-material-design-icons/ChevronLeft.vue';
import ChevronRight from 'vue-material-design-icons/ChevronRight.vue';
import CalendarBlank from 'vue-material-design-icons/CalendarBlank.vue';

export default {
  name: 'NewsLayoutCarousel',
  components: {
    ChevronLeft,
    ChevronRight,
    CalendarBlank,
  },
  props: {
    items: {
      type: Array,
      required: true,
    },
    widget: {
      type: Object,
      required: true,
    },
  },
  emits: ['navigate'],
  data() {
    return {
      currentIndex: 0,
    };
  },
  computed: {
    trackStyle() {
      return {
        transform: `translateX(-${this.currentIndex * 100}%)`,
      };
    },
  },
  methods: {
    getImageUrl(item) {
      if (!item.imagePath) return '';
      return generateUrl(item.imagePath);
    },
    prev() {
      if (this.currentIndex > 0) {
        this.currentIndex--;
      }
    },
    next() {
      if (this.currentIndex < this.items.length - 1) {
        this.currentIndex++;
      }
    },
    goTo(index) {
      this.currentIndex = index;
    },
  },
};
</script>

<style scoped>
.news-layout-carousel {
  width: 100%;
}

.carousel-container {
  display: flex;
  align-items: center;
  gap: 8px;
}

.carousel-nav {
  flex-shrink: 0;
  display: flex;
  align-items: center;
  justify-content: center;
  width: 40px;
  height: 40px;
  padding: 0;
  border: 1px solid var(--color-border);
  border-radius: 50%;
  background: var(--color-main-background);
  color: var(--color-main-text);
  cursor: pointer;
  transition: all 0.2s;
}

.carousel-nav:hover:not(:disabled) {
  background: var(--color-background-hover);
  border-color: var(--color-primary);
  color: var(--color-primary);
}

.carousel-nav:disabled {
  opacity: 0.3;
  cursor: not-allowed;
}

.carousel-viewport {
  flex: 1;
  overflow: hidden;
  border-radius: var(--border-radius-large);
}

.carousel-track {
  display: flex;
  transition: transform 0.3s ease;
}

.carousel-slide {
  flex: 0 0 100%;
  min-width: 0;
}

.carousel-item {
  display: block;
  text-decoration: none;
  color: inherit;
  background: var(--color-main-background);
  border: 1px solid var(--color-border);
  border-radius: var(--border-radius-large);
  overflow: hidden;
  transition: all 0.2s;
}

.carousel-item:hover {
  border-color: var(--color-primary);
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

.carousel-image {
  width: 100%;
  height: 200px;
  overflow: hidden;
  background: var(--color-background-dark);
}

.carousel-image img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.carousel-content {
  padding: 16px;
}

.carousel-title {
  margin: 0 0 8px 0;
  font-size: 18px;
  font-weight: 600;
  color: var(--color-main-text);
  line-height: 1.3;
}

.carousel-item:hover .carousel-title {
  color: var(--color-primary);
}

.carousel-date {
  display: flex;
  align-items: center;
  gap: 4px;
  margin-bottom: 8px;
  font-size: 13px;
  color: var(--color-text-maxcontrast);
}

.carousel-excerpt {
  margin: 0;
  font-size: 14px;
  color: var(--color-text-light);
  line-height: 1.5;
  overflow: hidden;
  text-overflow: ellipsis;
  display: -webkit-box;
  -webkit-line-clamp: 3;
  -webkit-box-orient: vertical;
}

.carousel-dots {
  display: flex;
  justify-content: center;
  gap: 8px;
  margin-top: 16px;
}

.carousel-dot {
  width: 10px;
  height: 10px;
  padding: 0;
  border: none;
  border-radius: 50%;
  background: var(--color-border-dark);
  cursor: pointer;
  transition: all 0.2s;
}

.carousel-dot:hover {
  background: var(--color-primary-element-light);
}

.carousel-dot--active {
  background: var(--color-primary);
  transform: scale(1.2);
}

@media (max-width: 600px) {
  .carousel-nav {
    width: 32px;
    height: 32px;
  }

  .carousel-image {
    height: 150px;
  }

  .carousel-title {
    font-size: 16px;
  }
}
</style>
