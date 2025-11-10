<template>
  <NcActions>
    <!-- Edit Navigation -->
    <NcActionButton v-if="canPerformAction('editNavigation')"
                    @click="$emit('edit-navigation')">
      <template #icon>
        <Cog :size="20" />
      </template>
      {{ t('Edit Navigation') }}
    </NcActionButton>

    <!-- Pages List -->
    <NcActionButton v-if="canPerformAction('viewPages')"
                    @click="$emit('show-pages')">
      <template #icon>
        <ViewList :size="20" />
      </template>
      {{ t('Pages') }}
    </NcActionButton>

    <!-- New Page -->
    <NcActionButton v-if="canPerformAction('createPage')"
                    @click="$emit('create-page')">
      <template #icon>
        <Plus :size="20" />
      </template>
      {{ t('New Page') }}
    </NcActionButton>

    <!-- Edit Page (only when not in edit mode) -->
    <NcActionButton v-if="!isEditMode && canPerformAction('editPage')"
                    @click="$emit('start-edit')">
      <template #icon>
        <Pencil :size="20" />
      </template>
      {{ t('Edit Page') }}
    </NcActionButton>
  </NcActions>
</template>

<script>
import { translate as t } from '@nextcloud/l10n';
import { NcActions, NcActionButton } from '@nextcloud/vue';
import Cog from 'vue-material-design-icons/Cog.vue';
import ViewList from 'vue-material-design-icons/ViewList.vue';
import Plus from 'vue-material-design-icons/Plus.vue';
import Pencil from 'vue-material-design-icons/Pencil.vue';

export default {
  name: 'PageActionsMenu',
  components: {
    NcActions,
    NcActionButton,
    Cog,
    ViewList,
    Plus,
    Pencil
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
  emits: ['edit-navigation', 'show-pages', 'create-page', 'start-edit'],
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
