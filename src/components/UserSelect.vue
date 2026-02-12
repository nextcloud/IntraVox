<template>
  <div class="user-select">
    <!-- Search input -->
    <div class="user-search">
      <Magnify :size="16" class="search-icon" />
      <input
        v-model="searchQuery"
        type="text"
        :placeholder="t('Search users...')"
        class="search-input"
        @input="debouncedSearch"
        @focus="showResults = true"
        @keydown.escape="showResults = false"
      />
      <NcLoadingIcon v-if="searching" :size="16" class="search-loading" />
    </div>

    <!-- Search results dropdown -->
    <div v-if="showResults && searchResults.length > 0" class="search-results">
      <div
        v-for="user in searchResults"
        :key="user.uid"
        class="search-result-item"
        :class="{ disabled: isSelected(user.uid) }"
        @click="addUser(user)"
      >
        <NcAvatar :user="user.uid" :display-name="user.displayName" :size="28" />
        <div class="user-info">
          <span class="user-name">{{ user.displayName }}</span>
          <span v-if="user.email" class="user-email">{{ user.email }}</span>
        </div>
        <Check v-if="isSelected(user.uid)" :size="16" class="selected-check" />
      </div>
    </div>

    <!-- No results message -->
    <div v-if="showResults && searchQuery.length >= 2 && !searching && searchResults.length === 0" class="search-no-results">
      {{ t('No users found') }}
    </div>

    <!-- Selected users list -->
    <div v-if="selectedUsers.length > 0" class="selected-users">
      <div
        v-for="user in selectedUsers"
        :key="user.uid"
        class="selected-user"
      >
        <NcAvatar :user="user.uid" :display-name="user.displayName" :size="24" />
        <span class="selected-user-name">{{ user.displayName }}</span>
        <button
          type="button"
          class="remove-user"
          @click="removeUser(user.uid)"
          :title="t('Remove')"
        >
          <Close :size="14" />
        </button>
      </div>
    </div>

    <!-- Empty state -->
    <div v-else class="selected-users-empty">
      <AccountMultiple :size="20" />
      <span>{{ t('No users selected') }}</span>
    </div>
  </div>
</template>

<script>
import { NcAvatar, NcLoadingIcon } from '@nextcloud/vue';
import { generateUrl } from '@nextcloud/router';
import { translate as t } from '@nextcloud/l10n';
import axios from '@nextcloud/axios';
import Magnify from 'vue-material-design-icons/Magnify.vue';
import Close from 'vue-material-design-icons/Close.vue';
import Check from 'vue-material-design-icons/Check.vue';
import AccountMultiple from 'vue-material-design-icons/AccountMultiple.vue';

export default {
  name: 'UserSelect',
  components: {
    NcAvatar,
    NcLoadingIcon,
    Magnify,
    Close,
    Check,
    AccountMultiple,
  },
  props: {
    modelValue: {
      type: Array,
      default: () => [],
    },
  },
  emits: ['update:modelValue'],
  data() {
    return {
      searchQuery: '',
      searchResults: [],
      searching: false,
      showResults: false,
      selectedUsers: [],
      debounceTimeout: null,
    };
  },
  watch: {
    modelValue: {
      immediate: true,
      handler(newVal) {
        // Load user profiles for selected IDs
        this.loadSelectedUsers(newVal || []);
      },
    },
  },
  mounted() {
    // Close dropdown when clicking outside
    document.addEventListener('click', this.handleClickOutside);
  },
  beforeUnmount() {
    document.removeEventListener('click', this.handleClickOutside);
  },
  methods: {
    t(key, vars = {}) {
      return t('intravox', key, vars);
    },
    handleClickOutside(event) {
      if (!this.$el.contains(event.target)) {
        this.showResults = false;
      }
    },
    debouncedSearch() {
      clearTimeout(this.debounceTimeout);
      this.debounceTimeout = setTimeout(() => {
        this.search();
      }, 300);
    },
    async search() {
      if (this.searchQuery.length < 2) {
        this.searchResults = [];
        return;
      }

      this.searching = true;
      try {
        const url = generateUrl('/apps/intravox/api/users/search');
        const response = await axios.get(url, {
          params: { query: this.searchQuery, limit: 10 },
        });
        this.searchResults = response.data.users || [];
        this.showResults = true;
      } catch (err) {
        console.error('[UserSelect] Search failed:', err);
        this.searchResults = [];
      } finally {
        this.searching = false;
      }
    },
    async loadSelectedUsers(userIds) {
      if (!userIds || userIds.length === 0) {
        this.selectedUsers = [];
        return;
      }

      // Check if we already have all users loaded
      const loadedIds = this.selectedUsers.map(u => u.uid);
      const needsLoad = userIds.some(id => !loadedIds.includes(id));

      if (!needsLoad && userIds.length === this.selectedUsers.length) {
        return;
      }

      try {
        const url = generateUrl('/apps/intravox/api/users');
        const response = await axios.post(url, { userIds });
        this.selectedUsers = response.data.users || [];
      } catch (err) {
        console.error('[UserSelect] Failed to load users:', err);
      }
    },
    isSelected(userId) {
      return this.modelValue.includes(userId);
    },
    addUser(user) {
      if (this.isSelected(user.uid)) {
        return;
      }

      const newSelection = [...this.modelValue, user.uid];
      this.$emit('update:modelValue', newSelection);

      // Add to local selected users
      this.selectedUsers.push(user);

      // Clear search
      this.searchQuery = '';
      this.searchResults = [];
      this.showResults = false;
    },
    removeUser(userId) {
      const newSelection = this.modelValue.filter(id => id !== userId);
      this.$emit('update:modelValue', newSelection);

      // Remove from local selected users
      this.selectedUsers = this.selectedUsers.filter(u => u.uid !== userId);
    },
  },
};
</script>

<style scoped>
.user-select {
  position: relative;
}

/* Search input */
.user-search {
  position: relative;
  display: flex;
  align-items: center;
}

.search-icon {
  position: absolute;
  left: 10px;
  color: var(--color-text-maxcontrast);
  pointer-events: none;
}

.search-input {
  width: 100%;
  padding: 8px 12px 8px 34px;
  border: 1px solid var(--color-border);
  border-radius: var(--border-radius);
  background: var(--color-main-background);
  font-size: 14px;
}

.search-input:focus {
  border-color: var(--color-primary-element);
  outline: none;
}

.search-loading {
  position: absolute;
  right: 10px;
}

/* Search results */
.search-results {
  position: absolute;
  top: 100%;
  left: 0;
  right: 0;
  margin-top: 4px;
  max-height: 250px;
  overflow-y: auto;
  background: var(--color-main-background);
  border: 1px solid var(--color-border);
  border-radius: var(--border-radius);
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
  z-index: 100;
}

.search-result-item {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 8px 12px;
  cursor: pointer;
  transition: background-color 0.15s ease;
}

.search-result-item:hover:not(.disabled) {
  background: var(--color-background-hover);
}

.search-result-item.disabled {
  opacity: 0.6;
  cursor: default;
}

.user-info {
  flex: 1;
  min-width: 0;
  display: flex;
  flex-direction: column;
}

.user-name {
  font-size: 14px;
  font-weight: 500;
  color: var(--color-main-text);
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.user-email {
  font-size: 12px;
  color: var(--color-text-maxcontrast);
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.selected-check {
  color: var(--color-success);
}

.search-no-results {
  position: absolute;
  top: 100%;
  left: 0;
  right: 0;
  margin-top: 4px;
  padding: 12px;
  background: var(--color-main-background);
  border: 1px solid var(--color-border);
  border-radius: var(--border-radius);
  color: var(--color-text-maxcontrast);
  text-align: center;
  font-size: 13px;
  z-index: 100;
}

/* Selected users */
.selected-users {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
  margin-top: 12px;
}

.selected-user {
  display: flex;
  align-items: center;
  gap: 6px;
  padding: 4px 8px;
  background: var(--color-background-dark);
  border-radius: 20px;
  font-size: 13px;
}

.selected-user-name {
  color: var(--color-main-text);
  max-width: 150px;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.remove-user {
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 2px;
  border: none;
  background: none;
  color: var(--color-text-maxcontrast);
  cursor: pointer;
  border-radius: 50%;
  transition: background-color 0.15s ease;
}

.remove-user:hover {
  background: var(--color-background-hover);
  color: var(--color-error);
}

.selected-users-empty {
  display: flex;
  align-items: center;
  gap: 8px;
  margin-top: 12px;
  padding: 12px;
  color: var(--color-text-maxcontrast);
  background: var(--color-background-hover);
  border-radius: var(--border-radius);
  font-size: 13px;
}
</style>
