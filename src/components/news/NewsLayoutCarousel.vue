<template>
  <div class="news-layout-carousel">
    <div class="carousel-container">
      <button
        class="carousel-nav carousel-nav--prev"
        :style="navButtonStyle"
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
              :style="itemStyle"
              @click.prevent="$emit('navigate', item.uniqueId)"
              @mouseenter="pauseAutoplay"
              @mouseleave="resumeAutoplay"
            >
              <div v-if="widget.showImage !== false && item.imagePath" class="carousel-image">
                <img :src="getImageUrl(item)" :alt="item.title" loading="lazy" />
              </div>
              <div class="carousel-content">
                <h4 class="carousel-title" :style="textStyle">{{ item.title }}</h4>
                <div v-if="widget.showDate !== false" class="carousel-date" :style="secondaryTextStyle">
                  <CalendarBlank :size="14" />
                  <span>{{ item.modifiedFormatted }}</span>
                </div>
                <p v-if="widget.showExcerpt !== false && item.excerpt" class="carousel-excerpt" :style="secondaryTextStyle">
                  {{ item.excerpt }}
                </p>
              </div>
            </a>
          </div>
        </div>
      </div>

      <button
        class="carousel-nav carousel-nav--next"
        :style="navButtonStyle"
        @click="next"
      >
        <ChevronRight :size="24" />
      </button>
    </div>

    <div class="carousel-indicators" v-if="items.length > 1">
      <button
        v-for="(item, index) in items"
        :key="item.uniqueId"
        class="carousel-indicator"
        :class="{ 'carousel-indicator--active': index === currentIndex }"
        :style="index === currentIndex ? activeIndicatorStyle : indicatorStyle"
        :aria-label="t('intravox', 'Go to slide {number}', { number: index + 1 })"
        :aria-current="index === currentIndex ? 'true' : 'false'"
        @click="goTo(index)"
      />
    </div>
  </div>
</template>

<script>
import { generateUrl } from '@nextcloud/router';
import { translate as t } from '@nextcloud/l10n';
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
    rowBackgroundColor: {
      type: String,
      default: '',
    },
  },
  emits: ['navigate'],
  data() {
    return {
      currentIndex: 0,
      autoplayTimer: null,
      isPaused: false,
    };
  },
  computed: {
    trackStyle() {
      return {
        transform: `translateX(-${this.currentIndex * 100}%)`,
      };
    },
    autoplayInterval() {
      // Default 5 seconds, configurable via widget.autoplayInterval
      return (this.widget.autoplayInterval || 5) * 1000;
    },
    isDarkBackground() {
      const darkBackgrounds = [
        'var(--color-primary-element)',
        'var(--color-error)',
        'var(--color-success)',
      ];
      return darkBackgrounds.includes(this.rowBackgroundColor);
    },
    textStyle() {
      if (this.isDarkBackground) {
        return { color: 'var(--color-primary-element-text)' };
      }
      return {};
    },
    secondaryTextStyle() {
      if (this.isDarkBackground) {
        return { color: 'rgba(255, 255, 255, 0.8)' };
      }
      return {};
    },
    itemStyle() {
      if (this.isDarkBackground) {
        return {
          background: 'rgba(255, 255, 255, 0.1)',
          borderColor: 'rgba(255, 255, 255, 0.2)',
        };
      }
      return {};
    },
    navButtonStyle() {
      if (this.isDarkBackground) {
        return {
          background: 'rgba(255, 255, 255, 0.2)',
          borderColor: 'rgba(255, 255, 255, 0.3)',
          color: 'var(--color-primary-element-text)',
        };
      }
      return {};
    },
    indicatorStyle() {
      if (this.isDarkBackground) {
        return {
          background: 'rgba(255, 255, 255, 0.3)',
        };
      }
      return {};
    },
    activeIndicatorStyle() {
      if (this.isDarkBackground) {
        return {
          background: 'var(--color-primary-element-text)',
        };
      }
      return {};
    },
  },
  watch: {
    items: {
      immediate: true,
      handler() {
        // Reset to first item when items change
        this.currentIndex = 0;
        this.startAutoplay();
      },
    },
    'widget.autoplayInterval': {
      handler() {
        // Restart autoplay when interval changes
        this.startAutoplay();
      },
    },
  },
  mounted() {
    this.startAutoplay();
  },
  beforeUnmount() {
    this.stopAutoplay();
  },
  methods: {
    t,
    getImageUrl(item) {
      if (!item.imagePath) return '';
      return generateUrl(item.imagePath);
    },
    prev() {
      if (this.currentIndex > 0) {
        this.currentIndex--;
      } else {
        // Loop to last item
        this.currentIndex = this.items.length - 1;
      }
      this.resetAutoplayTimer();
    },
    next() {
      if (this.currentIndex < this.items.length - 1) {
        this.currentIndex++;
      } else {
        // Loop to first item
        this.currentIndex = 0;
      }
      this.resetAutoplayTimer();
    },
    goTo(index) {
      this.currentIndex = index;
      this.resetAutoplayTimer();
    },
    startAutoplay() {
      this.stopAutoplay();
      if (this.items.length > 1 && this.widget.autoplayInterval > 0) {
        this.autoplayTimer = setInterval(() => {
          if (!this.isPaused) {
            this.next();
          }
        }, this.autoplayInterval);
      }
    },
    stopAutoplay() {
      if (this.autoplayTimer) {
        clearInterval(this.autoplayTimer);
        this.autoplayTimer = null;
      }
    },
    resetAutoplayTimer() {
      // Restart the timer after manual navigation
      this.startAutoplay();
    },
    pauseAutoplay() {
      this.isPaused = true;
    },
    resumeAutoplay() {
      this.isPaused = false;
    },
  },
};
</script>

<style scoped>
.news-layout-carousel {
  width: 100%;
  max-width: 100%;
  overflow: hidden;
}

.carousel-container {
  display: flex;
  align-items: center;
  gap: 8px;
  max-width: 100%;
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

.carousel-nav:hover {
  background: var(--color-background-hover);
  border-color: var(--color-primary);
  color: var(--color-primary);
}

.carousel-viewport {
  flex: 1;
  min-width: 0;
  overflow: hidden;
  border-radius: var(--border-radius-large);
}

.carousel-track {
  display: flex;
  transition: transform 0.3s ease;
  width: 100%;
}

.carousel-slide {
  flex: 0 0 100%;
  min-width: 0;
  width: 100%;
  box-sizing: border-box;
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

.carousel-indicators {
  display: flex;
  justify-content: center;
  gap: 10px;
  margin-top: 16px;
  padding: 8px 0;
}

.carousel-indicator {
  width: 10px;
  height: 10px;
  padding: 0;
  border: none;
  border-radius: 50%;
  background: var(--color-border-dark);
  cursor: pointer;
  transition: all 0.3s ease;
  position: relative;
}

/* Enlarge touch target without changing visual size */
.carousel-indicator::before {
  content: '';
  position: absolute;
  top: -8px;
  left: -8px;
  right: -8px;
  bottom: -8px;
}

.carousel-indicator:hover {
  background: var(--color-primary-element-light);
  transform: scale(1.2);
}

.carousel-indicator--active {
  width: 12px;
  height: 12px;
  background: var(--color-primary-element);
  box-shadow: 0 0 0 3px rgba(0, 130, 201, 0.2);
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
