<template>
  <NcActions>
    <!-- Pages List (most used) -->
    <NcActionButton v-if="canPerformAction('viewPages')"
                    @click="$emit('show-pages')">
      <template #icon>
        <ViewList :size="20" />
      </template>
      {{ t('Pages') }}
    </NcActionButton>

    <!-- New Page (second most used) -->
    <NcActionButton v-if="canPerformAction('createPage')"
                    @click="$emit('create-page')">
      <template #icon>
        <Plus :size="20" />
      </template>
      {{ t('New Page') }}
    </NcActionButton>

    <!-- Details (information about current page) -->
    <NcActionButton v-if="!isEditMode"
                    :close-after-click="true"
                    @click="$emit('show-details')">
      <template #icon>
        <Information :size="20" />
      </template>
      {{ t('Details') }}
    </NcActionButton>

    <!-- Edit Navigation (admin action, less frequently used) -->
    <NcActionButton v-if="canPerformAction('editNavigation')"
                    @click="$emit('edit-navigation')">
      <template #icon>
        <Cog :size="20" />
      </template>
      {{ t('Edit Navigation') }}
    </NcActionButton>
  </NcActions>
</template>

<script>
import { translate as t } from '@nextcloud/l10n';
import { NcActions, NcActionButton } from '@nextcloud/vue';
import Cog from 'vue-material-design-icons/Cog.vue';
import ViewList from 'vue-material-design-icons/ViewList.vue';
import Plus from 'vue-material-design-icons/Plus.vue';
import Information from 'vue-material-design-icons/Information.vue';

export default {
  name: 'PageActionsMenu',
  components: {
    NcActions,
    NcActionButton,
    Cog,
    ViewList,
    Plus,
    Information
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
  emits: ['edit-navigation', 'show-pages', 'create-page', 'show-details'],
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
