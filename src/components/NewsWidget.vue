<template>
  <div class="news-widget">
    <h3 v-if="widget.title" class="news-widget-title">{{ widget.title }}</h3>

    <div v-if="loading" class="news-loading">
      <NcLoadingIcon :size="32" />
      <span>{{ t('Loading news...') }}</span>
    </div>

    <div v-else-if="error" class="news-error">
      <AlertCircle :size="24" />
      <span>{{ error }}</span>
    </div>

    <div v-else-if="items.length === 0" class="news-empty">
      <Newspaper :size="32" />
      <p>{{ t('No news items found') }}</p>
    </div>

    <component
      v-else
      :is="layoutComponent"
      :items="items"
      :widget="widget"
      @navigate="handleNavigate"
    />
  </div>
</template>

<script>
import { NcLoadingIcon } from '@nextcloud/vue';
import { generateUrl } from '@nextcloud/router';
import { translate as t } from '@nextcloud/l10n';
import axios from '@nextcloud/axios';
import AlertCircle from 'vue-material-design-icons/AlertCircle.vue';
import Newspaper from 'vue-material-design-icons/Newspaper.vue';
import NewsLayoutList from './news/NewsLayoutList.vue';
import NewsLayoutGrid from './news/NewsLayoutGrid.vue';
import NewsLayoutCarousel from './news/NewsLayoutCarousel.vue';

export default {
  name: 'NewsWidget',
  components: {
    NcLoadingIcon,
    AlertCircle,
    Newspaper,
    NewsLayoutList,
    NewsLayoutGrid,
    NewsLayoutCarousel,
  },
  props: {
    widget: {
      type: Object,
      required: true,
    },
  },
  emits: ['navigate'],
  data() {
    return {
      items: [],
      loading: true,
      error: null,
    };
  },
  computed: {
    layoutComponent() {
      const layouts = {
        list: 'NewsLayoutList',
        grid: 'NewsLayoutGrid',
        carousel: 'NewsLayoutCarousel',
      };
      return layouts[this.widget.layout] || 'NewsLayoutList';
    },
  },
  watch: {
    widget: {
      deep: true,
      handler() {
        this.fetchNews();
      },
    },
  },
  mounted() {
    this.fetchNews();
  },
  methods: {
    t(key, vars = {}) {
      return t('intravox', key, vars);
    },
    async fetchNews() {
      this.loading = true;
      this.error = null;

      try {
        const params = new URLSearchParams({
          sourcePath: this.widget.sourcePath || '',
          filters: JSON.stringify(this.widget.filters || []),
          filterOperator: this.widget.filterOperator || 'AND',
          sortBy: this.widget.sortBy || 'modified',
          sortOrder: this.widget.sortOrder || 'desc',
          limit: this.widget.limit || 5,
        });

        const response = await axios.get(
          generateUrl(`/apps/intravox/api/news?${params}`)
        );

        this.items = response.data.items || [];
      } catch (err) {
        console.error('Failed to fetch news:', err);
        this.error = this.t('Failed to load news');
      } finally {
        this.loading = false;
      }
    },
    handleNavigate(pageId) {
      this.$emit('navigate', pageId);
    },
  },
};
</script>

<style scoped>
.news-widget {
  width: 100%;
  margin: 12px 0;
}

.news-widget-title {
  margin: 0 0 16px 0;
  font-size: 18px;
  font-weight: 600;
  color: var(--color-main-text);
}

.news-loading,
.news-error,
.news-empty {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 12px;
  padding: 40px 20px;
  color: var(--color-text-maxcontrast);
  text-align: center;
}

.news-error {
  color: var(--color-error);
}

.news-empty {
  background: var(--color-background-hover);
  border: 2px dashed var(--color-border);
  border-radius: var(--border-radius-large);
}

.news-empty p {
  margin: 0;
  font-size: 14px;
}
</style>
