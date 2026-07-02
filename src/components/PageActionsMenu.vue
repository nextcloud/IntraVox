<template>
  <NcActions>
    <!-- New Page -->
    <NcActionButton v-if="canPerformAction('createPage')"
                    @click="$emit('create-page')">
      <template #icon>
        <Plus :size="20" />
      </template>
      {{ t('intravox', 'New page') }}
    </NcActionButton>

    <!-- Edit Navigation (admin action, less frequently used) -->
    <NcActionButton v-if="canPerformAction('editNavigation')"
                    @click="$emit('edit-navigation')">
      <template #icon>
        <Cog :size="20" />
      </template>
      {{ t('intravox', 'Edit navigation') }}
    </NcActionButton>

    <!-- Page Settings (for editors) -->
    <NcActionButton v-if="canPerformAction('editPage')"
                    @click="$emit('page-settings')">
      <template #icon>
        <TuneVertical :size="20" />
      </template>
      {{ t('intravox', 'Page settings') }}
    </NcActionButton>

    <!-- Save as Template -->
    <NcActionButton v-if="canPerformAction('saveAsTemplate')"
                    @click="$emit('save-as-template')">
      <template #icon>
        <FileDocumentMultipleOutline :size="20" />
      </template>
      {{ t('intravox', 'Save as template') }}
    </NcActionButton>

    <!-- RSS Feed -->
    <NcActionButton @click="$emit('feed-settings')">
      <template #icon>
        <Rss :size="20" />
      </template>
      {{ t('intravox', 'RSS feed') }}
    </NcActionButton>
  </NcActions>
</template>

<script>
import { translate, translatePlural } from '@nextcloud/l10n';
import { NcActions, NcActionButton } from '@nextcloud/vue';
import Cog from 'vue-material-design-icons/Cog.vue';
import Plus from 'vue-material-design-icons/Plus.vue';
import TuneVertical from 'vue-material-design-icons/TuneVertical.vue';
import FileDocumentMultipleOutline from 'vue-material-design-icons/FileDocumentMultipleOutline.vue';
import Rss from 'vue-material-design-icons/Rss.vue';

export default {
  name: 'PageActionsMenu',
  components: {
    NcActions,
    NcActionButton,
    Cog,
    Plus,
    TuneVertical,
    FileDocumentMultipleOutline,
    Rss
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
  emits: ['edit-navigation', 'create-page', 'page-settings', 'save-as-template', 'feed-settings'],
  methods: {
    t(app, text, vars) {
      return translate(app, text, vars);
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
