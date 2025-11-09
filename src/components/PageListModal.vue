<template>
  <NcModal @close="$emit('close')"
           :name="t('All pages')"
           size="normal">
    <div class="page-list-content">
      <div v-if="pages.length === 0" class="empty-state">
        <p>{{ t('No pages created yet.') }}</p>
      </div>

      <div v-else class="page-list">
        <NcListItem
          v-for="page in sortedPages"
          :key="page.id"
          :name="page.title"
          :details="formatDate(page.modified)"
          @click="$emit('select', page.id)"
        >
          <template #actions>
            <NcButton
              v-if="page.id !== 'home'"
              type="error"
              @click.stop="$emit('delete', page.id)"
              :aria-label="t('Delete')"
            >
              <template #icon>
                <Delete :size="20" />
              </template>
            </NcButton>
          </template>
        </NcListItem>
      </div>
    </div>
  </NcModal>
</template>

<script>
import { translate } from '@nextcloud/l10n';
import { NcModal, NcListItem, NcButton } from '@nextcloud/vue';
import Delete from 'vue-material-design-icons/Delete.vue';

export default {
  name: 'PageListModal',
  components: {
    NcModal,
    NcListItem,
    NcButton,
    Delete
  },
  props: {
    pages: {
      type: Array,
      required: true
    }
  },
  emits: ['close', 'select', 'delete'],
  computed: {
    sortedPages() {
      return [...this.pages].sort((a, b) => {
        // Home page always first
        if (a.id === 'home') return -1;
        if (b.id === 'home') return 1;
        // Then by modified date
        return (b.modified || 0) - (a.modified || 0);
      });
    }
  },
  methods: {
    t(key, vars = {}) {
      return translate('intravox', key, vars);
    },
    formatDate(timestamp) {
      if (!timestamp) return '';
      const date = new Date(timestamp * 1000);
      return date.toLocaleDateString('nl-NL', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
      });
    }
  }
};
</script>

<style scoped>
.page-list-content {
  padding: 20px;
}

.empty-state {
  text-align: center;
  padding: 40px;
  color: var(--color-text-maxcontrast);
}

.page-list {
  display: flex;
  flex-direction: column;
}
</style>
