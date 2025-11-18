<template>
  <NcModal
    v-if="show"
    :name="t('Search Results')"
    size="large"
    @close="$emit('close')"
  >
    <div class="search-results-container">
      <!-- Header with query info -->
      <div class="search-header">
        <h2>{{ t('Search Results for "{query}"', { query: searchQuery }) }}</h2>
        <p v-if="!loading && results.length > 0" class="result-count">
          {{ t('{count} result(s) found', { count: results.length }) }}
        </p>
      </div>

      <!-- Loading State -->
      <div v-if="loading" class="loading-state">
        <NcLoadingIcon :size="64" />
        <p>{{ t('Searching...') }}</p>
      </div>

      <!-- Empty State -->
      <NcEmptyContent
        v-else-if="results.length === 0"
        :name="t('No results found')"
      >
        <template #icon>
          <Magnify :size="64" />
        </template>
        <template #description>
          {{ t('Try different keywords or check your spelling') }}
        </template>
      </NcEmptyContent>

      <!-- Results List -->
      <div v-else class="results-list">
        <div
          v-for="result in results"
          :key="result.id"
          class="result-item"
          @click="selectResult(result)"
        >
          <div class="result-header">
            <h3 class="result-title">{{ result.title }}</h3>
            <span class="result-score">{{ t('Relevance: {score}', { score: result.score }) }}</span>
          </div>

          <div v-if="result.path" class="result-path">
            <FolderOutline :size="16" />
            <span>{{ result.path }}</span>
          </div>

          <div v-if="result.matches && result.matches.length > 0" class="result-matches">
            <div
              v-for="(match, index) in result.matches"
              :key="index"
              :class="['match-item', `match-${match.type}`]"
            >
              <span class="match-label">{{ getMatchLabel(match.type) }}:</span>
              <span class="match-text">{{ match.text }}</span>
            </div>
          </div>
        </div>
      </div>
    </div>
  </NcModal>
</template>

<script>
import { NcModal, NcEmptyContent, NcLoadingIcon } from '@nextcloud/vue';
import Magnify from 'vue-material-design-icons/Magnify.vue';
import FolderOutline from 'vue-material-design-icons/FolderOutline.vue';
import { translate as t } from '@nextcloud/l10n';

export default {
  name: 'SearchResults',
  components: {
    NcModal,
    NcEmptyContent,
    NcLoadingIcon,
    Magnify,
    FolderOutline
  },
  props: {
    show: {
      type: Boolean,
      default: false
    },
    results: {
      type: Array,
      default: () => []
    },
    searchQuery: {
      type: String,
      default: ''
    },
    loading: {
      type: Boolean,
      default: false
    }
  },
  emits: ['close', 'select'],
  methods: {
    t(key, vars = {}) {
      return t('intravox', key, vars);
    },
    selectResult(result) {
      this.$emit('select', result);
      this.$emit('close');
    },
    getMatchLabel(type) {
      const labels = {
        title: this.t('Title'),
        heading: this.t('Heading'),
        content: this.t('Content')
      };
      return labels[type] || type;
    }
  }
};
</script>

<style scoped>
.search-results-container {
  padding: 20px;
  min-height: 400px;
  max-height: 70vh;
  display: flex;
  flex-direction: column;
}

.search-header {
  margin-bottom: 20px;
  padding-bottom: 16px;
  border-bottom: 1px solid var(--color-border);
}

.search-header h2 {
  margin: 0 0 8px 0;
  font-size: 24px;
  font-weight: 600;
}

.result-count {
  margin: 0;
  font-size: 14px;
  color: var(--color-text-maxcontrast);
}

.loading-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 60px 20px;
  gap: 16px;
  color: var(--color-text-maxcontrast);
}

.results-list {
  flex: 1;
  overflow-y: auto;
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.result-item {
  padding: 16px;
  background: var(--color-background-hover);
  border-radius: var(--border-radius-large);
  cursor: pointer;
  transition: all 0.2s;
  border: 2px solid transparent;
}

.result-item:hover {
  background: var(--color-background-dark);
  border-color: var(--color-primary-element);
}

.result-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 8px;
  gap: 12px;
}

.result-title {
  margin: 0;
  font-size: 18px;
  font-weight: 600;
  color: var(--color-main-text);
  flex: 1;
}

.result-score {
  font-size: 12px;
  padding: 4px 8px;
  background: var(--color-primary-element-light);
  color: var(--color-primary-element-text);
  border-radius: var(--border-radius);
  white-space: nowrap;
}

.result-path {
  display: flex;
  align-items: center;
  gap: 6px;
  margin-bottom: 12px;
  font-size: 13px;
  color: var(--color-text-maxcontrast);
}

.result-matches {
  display: flex;
  flex-direction: column;
  gap: 8px;
  margin-top: 12px;
  padding-top: 12px;
  border-top: 1px solid var(--color-border);
}

.match-item {
  display: flex;
  gap: 8px;
  font-size: 14px;
  line-height: 1.5;
}

.match-label {
  font-weight: 600;
  color: var(--color-text-maxcontrast);
  text-transform: capitalize;
  min-width: 80px;
}

.match-text {
  flex: 1;
  color: var(--color-main-text);
}

.match-title .match-text {
  font-weight: 600;
}

.match-heading .match-text {
  font-style: italic;
}

.match-content .match-text {
  color: var(--color-text-maxcontrast);
}

/* Mobile adjustments */
@media (max-width: 768px) {
  .search-results-container {
    padding: 12px;
  }

  .search-header h2 {
    font-size: 20px;
  }

  .result-header {
    flex-direction: column;
    align-items: flex-start;
  }

  .result-score {
    align-self: flex-end;
  }

  .match-item {
    flex-direction: column;
    gap: 4px;
  }

  .match-label {
    min-width: auto;
  }
}
</style>
