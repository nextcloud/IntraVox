<template>
  <NcActions>
    <!-- New Page -->
    <NcActionButton v-if="canPerformAction('createPage')"
                    @click="$emit('create-page')">
      <template #icon>
        <Plus :size="20" />
      </template>
      {{ t('New Page') }}
    </NcActionButton>

    <!-- Edit Navigation (admin action, less frequently used) -->
    <NcActionButton v-if="canPerformAction('editNavigation')"
                    @click="$emit('edit-navigation')">
      <template #icon>
        <Cog :size="20" />
      </template>
      {{ t('Edit Navigation') }}
    </NcActionButton>

    <!-- Page Settings (for editors) -->
    <NcActionButton v-if="canPerformAction('editPage')"
                    @click="$emit('page-settings')">
      <template #icon>
        <TuneVertical :size="20" />
      </template>
      {{ t('Page Settings') }}
    </NcActionButton>
  </NcActions>
</template>

<script>
import { translate as t } from '@nextcloud/l10n';
import { NcActions, NcActionButton } from '@nextcloud/vue';
import Cog from 'vue-material-design-icons/Cog.vue';
import Plus from 'vue-material-design-icons/Plus.vue';
import TuneVertical from 'vue-material-design-icons/TuneVertical.vue';

export default {
  name: 'PageActionsMenu',
  components: {
    NcActions,
    NcActionButton,
    Cog,
    Plus,
    TuneVertical
  },
  props: {
    isEditMode: {
      type: Boolean,
      default: false
    },
    permissions: {
      type: Object,
      default: () => ({
        editNavigation: false,
        viewPages: true,      // Everyone can view pages
        createPage: false,
        editPage: false,
        deletePage: false     // For future use
      })
    }
  },
  emits: ['edit-navigation', 'create-page', 'page-settings'],
  methods: {
    t(key, vars = {}) {
      return t('intravox', key, vars);
    },
    /**
     * Check if user can perform a specific action
     * This method can be extended in the future to include more complex logic
     * like role-based permissions (admin, editor, viewer)
     */
    canPerformAction(action) {
      return this.permissions[action] === true;
    }
  }
};
</script>

<style scoped>
/* NcActions component handles its own styling */
</style>
