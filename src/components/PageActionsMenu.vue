<template>
  <NcActions ref="actions">
    <!-- New Page -->
    <NcActionButton v-if="canPerformAction('createPage')"
                    @click="emitAndClose('create-page')">
      <template #icon>
        <Plus :size="20" />
      </template>
      {{ t('intravox', 'New page') }}
    </NcActionButton>

    <!-- Edit Navigation (admin action, less frequently used) -->
    <NcActionButton v-if="canPerformAction('editNavigation')"
                    @click="emitAndClose('edit-navigation')">
      <template #icon>
        <Cog :size="20" />
      </template>
      {{ t('intravox', 'Edit navigation') }}
    </NcActionButton>

    <!-- Page Settings (for editors) -->
    <NcActionButton v-if="canPerformAction('editPage')"
                    @click="emitAndClose('page-settings')">
      <template #icon>
        <TuneVertical :size="20" />
      </template>
      {{ t('intravox', 'Page settings') }}
    </NcActionButton>

    <!-- Save as Template -->
    <NcActionButton v-if="canPerformAction('saveAsTemplate')"
                    @click="emitAndClose('save-as-template')">
      <template #icon>
        <FileDocumentMultipleOutline :size="20" />
      </template>
      {{ t('intravox', 'Save as template') }}
    </NcActionButton>

    <!-- RSS Feed -->
    <NcActionButton @click="emitAndClose('feed-settings')">
      <template #icon>
        <Rss :size="20" />
      </template>
      {{ t('intravox', 'RSS feed') }}
    </NcActionButton>

    <!-- Copy page -->
    <NcActionButton v-if="canPerformAction('createPage')"
                    @click="emitAndClose('copy-page')">
      <template #icon>
        <ContentCopy :size="20" />
      </template>
      {{ t('intravox', 'Copy page') }}
    </NcActionButton>

    <!-- Delete page (destructive; hidden for the homepage) -->
    <NcActionButton v-if="canPerformAction('deletePage') && !isHome"
                    class="action-delete-page"
                    @click="emitAndClose('delete-page')">
      <template #icon>
        <Delete :size="20" />
      </template>
      {{ t('intravox', 'Delete page') }}
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
import ContentCopy from 'vue-material-design-icons/ContentCopy.vue';
import Delete from 'vue-material-design-icons/Delete.vue';

export default {
  name: 'PageActionsMenu',
  components: {
    NcActions,
    NcActionButton,
    Cog,
    Plus,
    TuneVertical,
    FileDocumentMultipleOutline,
    Rss,
    ContentCopy,
    Delete
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
        deletePage: false
      })
    },
    isHome: {
      type: Boolean,
      default: false
    }
  },
  emits: ['edit-navigation', 'create-page', 'page-settings', 'save-as-template', 'feed-settings', 'copy-page', 'delete-page'],
  methods: {
    t(app, text, vars) {
      return translate(app, text, vars);
    },
    // Close the actions dropdown, then emit (the item opens a modal next).
    emitAndClose(eventName) {
      this.$refs.actions?.closeMenu?.();
      this.$emit(eventName);
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

/* Destructive action: tint the Delete page item red (NcActionButton has no
   danger variant). Matches the .tree-action--danger precedent. */
:deep(.action-delete-page .action-button__icon),
:deep(.action-delete-page .action-button__text) {
  color: var(--color-error);
}
</style>
