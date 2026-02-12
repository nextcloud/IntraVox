<template>
  <div class="people-widget" :style="getContainerStyle()">
    <h3 v-if="widget.title" class="people-widget-title" :style="titleStyle">
      {{ widget.title }}
    </h3>

    <div v-if="loading && users.length === 0" class="people-loading">
      <NcLoadingIcon :size="32" />
      <span>{{ t('Loading...') }}</span>
    </div>

    <div v-else-if="error" class="people-error">
      <AlertCircle :size="24" />
      <span>{{ error }}</span>
    </div>

    <div v-else-if="users.length === 0" class="people-empty">
      <AccountGroup :size="32" />
      <p>{{ t('No people found') }}</p>
      <small v-if="widget.selectionMode === 'manual'">
        {{ t('Select people in edit mode') }}
      </small>
      <small v-else>
        {{ t('No users match the current filters') }}
      </small>
    </div>

    <template v-else>
      <component
        :is="layoutComponent"
        :users="users"
        :widget="widget"
        :row-background-color="effectiveBackgroundColor"
      />

      <!-- Pagination footer -->
      <div v-if="showPaginationFooter" class="people-footer" :class="{ 'dark-background': isDarkBackground }" :style="footerStyle">
        <span class="people-count">
          {{ t('Showing {shown} of {total} people', { shown: users.length, total: total }) }}
        </span>
        <NcButton
          v-if="hasMore"
          :type="isDarkBackground ? 'primary' : 'secondary'"
          :disabled="loadingMore"
          @click="loadMore"
        >
          <template #icon>
            <NcLoadingIcon v-if="loadingMore" :size="20" />
          </template>
          {{ loadingMore ? t('Loading...') : t('Show more') }}
        </NcButton>
      </div>
    </template>
  </div>
</template>

<script>
import { NcLoadingIcon, NcButton } from '@nextcloud/vue';
import { generateUrl } from '@nextcloud/router';
import { translate as t } from '@nextcloud/l10n';
import axios from '@nextcloud/axios';
import AlertCircle from 'vue-material-design-icons/AlertCircle.vue';
import AccountGroup from 'vue-material-design-icons/AccountGroup.vue';
import PeopleLayoutCard from './people/PeopleLayoutCard.vue';
import PeopleLayoutList from './people/PeopleLayoutList.vue';
import PeopleLayoutGrid from './people/PeopleLayoutGrid.vue';

export default {
  name: 'PeopleWidget',
  components: {
    NcLoadingIcon,
    NcButton,
    AlertCircle,
    AccountGroup,
    PeopleLayoutCard,
    PeopleLayoutList,
    PeopleLayoutGrid,
  },
  props: {
    widget: {
      type: Object,
      required: true,
    },
    editable: {
      type: Boolean,
      default: false,
    },
    rowBackgroundColor: {
      type: String,
      default: '',
    },
    shareToken: {
      type: String,
      default: '',
    },
    pageId: {
      type: String,
      default: '',
    },
  },
  data() {
    return {
      users: [],
      total: 0,
      hasMore: false,
      loading: true,
      loadingMore: false,
      error: null,
    };
  },
  computed: {
    layoutComponent() {
      const layouts = {
        card: PeopleLayoutCard,
        list: PeopleLayoutList,
        grid: PeopleLayoutGrid,
      };
      return layouts[this.widget.layout] || PeopleLayoutCard;
    },
    effectiveBackgroundColor() {
      return this.widget.backgroundColor || this.rowBackgroundColor || '';
    },
    titleStyle() {
      const bgColor = this.effectiveBackgroundColor;

      const colorMappings = {
        'var(--color-primary-element)': 'var(--color-primary-element-text)',
        'var(--color-primary-element-light)': 'var(--color-primary-element-light-text)',
        'var(--color-error)': 'var(--color-error-text)',
        'var(--color-warning)': 'var(--color-warning-text)',
        'var(--color-success)': 'var(--color-success-text)',
        'var(--color-background-dark)': 'var(--color-main-text)',
        'var(--color-background-hover)': 'var(--color-main-text)',
      };

      const textColor = colorMappings[bgColor];
      if (textColor) {
        return { color: textColor };
      }
      return {};
    },
    footerStyle() {
      // Use same color mapping as title, plus border adjustment for dark backgrounds
      const style = { ...this.titleStyle };
      if (this.isDarkBackground) {
        style.borderColor = 'rgba(255, 255, 255, 0.2)';
      }
      return style;
    },
    isDarkBackground() {
      const bgColor = this.effectiveBackgroundColor;
      // These background colors need light text
      const darkBackgrounds = [
        'var(--color-primary-element)',
        'var(--color-error)',
        'var(--color-warning)',
        'var(--color-success)',
      ];
      return darkBackgrounds.includes(bgColor);
    },
    showPaginationFooter() {
      // Show footer if there are more items than shown, or if we loaded additional items
      // Also respect showPagination setting (default true)
      const showPagination = this.widget.showPagination !== false;
      return showPagination && this.total > 0 && (this.hasMore || this.users.length < this.total || this.users.length > (this.widget.limit || 50));
    },
  },
  watch: {
    widget: {
      deep: true,
      handler() {
        // Reset pagination when widget config changes
        this.users = [];
        this.total = 0;
        this.hasMore = false;
        this.fetchPeople();
      },
    },
  },
  mounted() {
    this.fetchPeople();
  },
  methods: {
    t(key, vars = {}) {
      return t('intravox', key, vars);
    },
    async fetchPeople(offset = 0) {
      if (offset === 0) {
        this.loading = true;
      }
      this.error = null;

      try {
        const limit = this.widget.limit || 50;
        const params = new URLSearchParams({
          sortBy: this.widget.sortBy || 'displayName',
          sortOrder: this.widget.sortOrder || 'asc',
          limit: String(limit),
          offset: String(offset),
        });

        // Manual mode: pass user IDs
        if (this.widget.selectionMode === 'manual' && this.widget.selectedUsers?.length > 0) {
          params.append('userIds', this.widget.selectedUsers.join(','));
        }
        // Filter mode: pass filters
        else if (this.widget.selectionMode === 'filter' && this.widget.filters?.length > 0) {
          params.append('filters', JSON.stringify(this.widget.filters));
          params.append('filterOperator', this.widget.filterOperator || 'AND');
        }
        // No users configured
        else {
          this.users = [];
          this.total = 0;
          this.hasMore = false;
          this.loading = false;
          return;
        }

        const url = this.shareToken
          ? generateUrl(`/apps/intravox/api/share/${this.shareToken}/people?${params}`)
          : generateUrl(`/apps/intravox/api/people?${params}`);

        const response = await axios.get(url);
        const newUsers = response.data.users || [];

        if (offset === 0) {
          this.users = newUsers;
        } else {
          this.users = [...this.users, ...newUsers];
        }

        this.total = response.data.total || this.users.length;
        this.hasMore = response.data.hasMore || false;
      } catch (err) {
        console.error('[PeopleWidget] Failed to fetch people:', err);
        this.error = this.t('Failed to load people');
      } finally {
        this.loading = false;
        this.loadingMore = false;
      }
    },
    async loadMore() {
      if (this.loadingMore || !this.hasMore) return;
      this.loadingMore = true;
      await this.fetchPeople(this.users.length);
    },
    getContainerStyle() {
      const style = {};
      if (this.widget.backgroundColor) {
        style.backgroundColor = this.widget.backgroundColor;
        style.padding = '20px';
        style.borderRadius = 'var(--border-radius-large)';
        // Negative margin-top pulls the widget up so title aligns with widgets without background
        style.marginTop = '-8px';
      }
      return style;
    },
  },
};
</script>

<style scoped>
.people-widget {
  width: 100%;
  margin: 12px 0;
  container-type: inline-size;
}

.people-widget-title {
  margin: 0 0 16px 0;
  font-size: 18px;
  font-weight: 600;
  color: var(--color-main-text);
}

.people-loading,
.people-error,
.people-empty {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 12px;
  padding: 40px 20px;
  color: var(--color-text-maxcontrast);
  text-align: center;
}

.people-error {
  color: var(--color-error);
}

.people-empty p {
  margin: 0;
  font-size: 14px;
}

.people-empty small {
  font-size: 12px;
  opacity: 0.7;
}

.people-footer {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 12px;
  padding: 16px 0 0;
  margin-top: 16px;
  border-top: 1px solid var(--color-border);
}

.people-count {
  font-size: 13px;
  color: inherit; /* Inherit from footerStyle */
  opacity: 0.85;
}

/* Dark background adjustments */
.people-footer.dark-background {
  border-top-color: rgba(255, 255, 255, 0.2);
}

.people-footer.dark-background .people-count {
  opacity: 0.9;
}
</style>
