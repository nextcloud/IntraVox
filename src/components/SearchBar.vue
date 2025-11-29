<template>
  <div class="search-bar">
    <NcTextField
      :value.sync="searchQuery"
      :label="t('Search pages')"
      :placeholder="t('Search...')"
      @update:value="onSearchInput"
      @keydown.enter="performSearch"
      @keydown.esc="clearSearch"
    >
      <template #trailing-button-icon>
        <Magnify v-if="!searchQuery" :size="20" />
        <Close v-else :size="20" @click="clearSearch" />
      </template>
    </NcTextField>
  </div>
</template>

<script>
import { NcTextField } from '@nextcloud/vue';
import Magnify from 'vue-material-design-icons/Magnify.vue';
import Close from 'vue-material-design-icons/Close.vue';
import { translate as t } from '@nextcloud/l10n';

export default {
  name: 'SearchBar',
  components: {
    NcTextField,
    Magnify,
    Close
  },
  props: {
    value: {
      type: String,
      default: ''
    }
  },
  emits: ['search', 'clear'],
  data() {
    return {
      searchQuery: this.value,
      searchTimeout: null
    };
  },
  watch: {
    value(newValue) {
      this.searchQuery = newValue;
    }
  },
  methods: {
    t(key) {
      return t('intravox', key);
    },
    onSearchInput(value) {
      this.searchQuery = value;

      // Clear existing timeout
      if (this.searchTimeout) {
        clearTimeout(this.searchTimeout);
      }

      // Don't search if query is too short
      if (value.length < 2) {
        if (value.length === 0) {
          this.$emit('clear');
        }
        return;
      }

      // Debounce search (wait 300ms after user stops typing)
      this.searchTimeout = setTimeout(() => {
        this.performSearch();
      }, 300);
    },
    performSearch() {
      if (this.searchQuery.length >= 2) {
        this.$emit('search', this.searchQuery);
      }
    },
    clearSearch() {
      this.searchQuery = '';
      this.$emit('clear');
    }
  }
};
</script>

<style scoped>
.search-bar {
  flex: 1;
  max-width: 400px;
}

/* Mobile adjustments */
@media (max-width: 768px) {
  .search-bar {
    max-width: none;
    width: 100%;
  }
}
</style>
