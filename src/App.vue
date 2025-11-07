<template>
  <div id="intravox-app">
    <!-- Header with page title and actions -->
    <div class="intravox-header">
      <div class="header-left">
        <h1 v-if="!isEditMode">{{ currentPage?.title || 'IntraVox' }}</h1>
        <input
          v-else
          v-model="editableTitle"
          type="text"
          class="page-title-input"
          :placeholder="t('Page title')"
        />
      </div>
      <div class="header-right">
        <NcButton v-if="!isEditMode"
                  @click="startEditMode"
                  type="primary"
                  :aria-label="t('Edit page')">
          <template #icon>
            <Pencil :size="20" />
          </template>
          {{ t('Edit') }}
        </NcButton>
        <template v-else>
          <NcButton @click="saveAndExitEditMode"
                    type="primary"
                    :aria-label="t('Save changes')">
            <template #icon>
              <ContentSave :size="20" />
            </template>
            {{ t('Save') }}
          </NcButton>
          <NcButton @click="cancelEditMode"
                    type="secondary"
                    :aria-label="t('Cancel editing')">
            <template #icon>
              <Close :size="20" />
            </template>
            {{ t('Cancel') }}
          </NcButton>
        </template>
        <NcButton @click="showPageList"
                  type="secondary"
                  :aria-label="t('Show page list')">
          <template #icon>
            <ViewList :size="20" />
          </template>
          {{ t('Pages') }}
        </NcButton>
        <NcButton @click="createNewPage"
                  type="secondary"
                  :aria-label="t('Create new page')">
          <template #icon>
            <Plus :size="20" />
          </template>
          {{ t('New Page') }}
        </NcButton>
      </div>
    </div>

    <!-- Navigation -->
    <div class="intravox-nav-bar">
      <Navigation :items="navigation.items"
                  :type="navigation.type"
                  :can-edit="canEditNavigation"
                  @navigate="navigateToItem"
                  @edit="showNavigationEditor = true" />
    </div>

    <div v-if="loading" class="loading">
      {{ t('Loading...') }}
    </div>

    <div v-else-if="error" class="error">
      {{ error }}
    </div>

    <div v-else class="intravox-content">
      <PageViewer
        v-if="!isEditMode && currentPage"
        :page="currentPage"
      />
      <PageEditor
        v-else-if="isEditMode && currentPage"
        :page="currentPage"
        @update="updatePage"
      />
    </div>

    <PageListModal
      v-if="showPages"
      :pages="pages"
      @close="showPages = false"
      @select="selectPage"
      @delete="deletePage"
    />

    <NewPageModal
      v-if="showNewPageModal"
      @close="showNewPageModal = false"
      @create="handleCreatePage"
    />

    <NavigationEditor
      v-if="showNavigationEditor"
      :navigation="navigation"
      :pages="pages"
      @close="showNavigationEditor = false"
      @save="saveNavigation"
    />
  </div>
</template>

<script>
import axios from '@nextcloud/axios';
import { generateUrl } from '@nextcloud/router';
import { translate as t } from '@nextcloud/l10n';
import { showSuccess, showError } from '@nextcloud/dialogs';
import { NcButton } from '@nextcloud/vue';
import Pencil from 'vue-material-design-icons/Pencil.vue';
import ContentSave from 'vue-material-design-icons/ContentSave.vue';
import Close from 'vue-material-design-icons/Close.vue';
import ViewList from 'vue-material-design-icons/ViewList.vue';
import Plus from 'vue-material-design-icons/Plus.vue';
import PageViewer from './components/PageViewer.vue';
import PageEditor from './components/PageEditor.vue';
import PageListModal from './components/PageListModal.vue';
import NewPageModal from './components/NewPageModal.vue';
import Navigation from './components/Navigation.vue';
import NavigationEditor from './components/NavigationEditor.vue';

export default {
  name: 'App',
  components: {
    NcButton,
    Pencil,
    ContentSave,
    Close,
    ViewList,
    Plus,
    PageViewer,
    PageEditor,
    PageListModal,
    NewPageModal,
    Navigation,
    NavigationEditor
  },
  data() {
    return {
      pages: [],
      currentPage: null,
      originalPage: null, // For rollback
      editableTitle: '',
      isEditMode: false,
      loading: true,
      error: null,
      showPages: false,
      showNewPageModal: false,
      navigation: {
        type: 'dropdown',
        items: []
      },
      canEditNavigation: false,
      showNavigationEditor: false,
      currentLanguage: document.documentElement.lang || 'en'
    };
  },
  mounted() {
    this.loadPages();
    this.loadNavigation();

    // Poll for language changes (Nextcloud reloads the page, but we check after navigation)
    this.languageCheckInterval = setInterval(() => {
      const newLanguage = document.documentElement.lang || 'en';
      if (newLanguage !== this.currentLanguage) {
        console.log(`IntraVox: Language changed from ${this.currentLanguage} to ${newLanguage}`);
        this.currentLanguage = newLanguage;
        this.handleLanguageChange();
      }
    }, 1000);

    // Also use MutationObserver to watch for HTML lang attribute changes
    this.langObserver = new MutationObserver(() => {
      const newLanguage = document.documentElement.lang || 'en';
      if (newLanguage !== this.currentLanguage) {
        console.log(`IntraVox: Language changed from ${this.currentLanguage} to ${newLanguage}`);
        this.currentLanguage = newLanguage;
        this.handleLanguageChange();
      }
    });

    this.langObserver.observe(document.documentElement, {
      attributes: true,
      attributeFilter: ['lang']
    });
  },
  beforeUnmount() {
    if (this.languageCheckInterval) {
      clearInterval(this.languageCheckInterval);
    }
    if (this.langObserver) {
      this.langObserver.disconnect();
    }
  },
  methods: {
    t(key, vars = {}) {
      return t('intravox', key, vars);
    },
    async loadPages() {
      try {
        console.log('IntraVox: Loading pages...');
        this.loading = true;
        const response = await axios.get(generateUrl('/apps/intravox/api/pages'));
        console.log('IntraVox: API response:', response);
        this.pages = response.data;
        console.log('IntraVox: Pages loaded:', this.pages);

        // Load first page (home or first available)
        if (this.pages.length > 0) {
          const homePage = this.pages.find(p => p.id === 'home') || this.pages[0];
          console.log('IntraVox: Loading home page:', homePage.id);
          await this.selectPage(homePage.id);
        } else {
          console.warn('IntraVox: No pages found!');
          this.error = this.t('No pages found. Please run the setup command first.');
        }
      } catch (err) {
        console.error('IntraVox: Error loading pages:', err);
        this.error = this.t('Could not load pages: {error}', { error: err.message });
      } finally {
        this.loading = false;
      }
    },
    async selectPage(pageId) {
      try {
        console.log('IntraVox: Selecting page:', pageId);
        this.loading = true;
        const response = await axios.get(generateUrl(`/apps/intravox/api/pages/${pageId}`));
        console.log('IntraVox: Page data:', response.data);
        this.currentPage = response.data;
        this.isEditMode = false;
        this.showPages = false;
      } catch (err) {
        console.error('IntraVox: Error selecting page:', err);
        showError(this.t('Could not load page: {error}', { error: err.message }));
      } finally {
        this.loading = false;
      }
    },
    startEditMode() {
      // Store original state for rollback
      this.originalPage = JSON.parse(JSON.stringify(this.currentPage));
      this.editableTitle = this.currentPage?.title || '';
      this.isEditMode = true;
      console.log('IntraVox: Edit mode started, original state saved');
    },
    async saveAndExitEditMode() {
      // Update title if changed
      if (this.editableTitle && this.editableTitle !== this.currentPage?.title) {
        this.currentPage.title = this.editableTitle;
      }
      await this.savePage();
      this.isEditMode = false;
      this.originalPage = null;
    },
    cancelEditMode() {
      // Rollback to original state
      if (this.originalPage) {
        this.currentPage = JSON.parse(JSON.stringify(this.originalPage));
        console.log('IntraVox: Rolled back to original state');
      }
      this.isEditMode = false;
      this.originalPage = null;
      showSuccess(this.t('Changes cancelled'));
    },
    async savePage() {
      try {
        await axios.put(
          generateUrl(`/apps/intravox/api/pages/${this.currentPage.id}`),
          this.currentPage
        );
        showSuccess(this.t('Page saved'));
        // Don't reload pages - just update in place to avoid exit from edit mode
      } catch (err) {
        showError(this.t('Could not save page: {error}', { error: err.message }));
      }
    },
    async updatePage(updatedPage) {
      this.currentPage = updatedPage;
      // No auto-save - only save when user clicks Save button
    },
    createNewPage() {
      this.showNewPageModal = true;
    },
    async handleCreatePage(title) {
      if (!title) return;

      const id = title.toLowerCase()
        .replace(/[^a-z0-9]+/g, '-')
        .replace(/^-+|-+$/g, '');

      if (!id) {
        showError(this.t('Invalid page title'));
        return;
      }

      try {
        const newPage = {
          id: id,
          title: title,
          layout: {
            columns: 1,
            rows: [
              {
                widgets: [
                  {
                    type: 'heading',
                    content: title,
                    level: 1,
                    column: 1,
                    order: 1
                  }
                ]
              }
            ]
          }
        };

        await axios.post(generateUrl('/apps/intravox/api/pages'), newPage);
        showSuccess(this.t('Page created'));
        await this.loadPages();
        await this.selectPage(id);
      } catch (err) {
        showError(this.t('Could not create page: {error}', { error: err.message }));
      }
    },
    async deletePage(pageId) {
      if (!confirm(this.t('Are you sure you want to delete this page?'))) {
        return;
      }

      try {
        await axios.delete(generateUrl(`/apps/intravox/api/pages/${pageId}`));
        showSuccess(this.t('Page deleted'));
        await this.loadPages();

        if (this.currentPage?.id === pageId) {
          this.currentPage = null;
          if (this.pages.length > 0) {
            await this.selectPage(this.pages[0].id);
          }
        }
      } catch (err) {
        showError(this.t('Could not delete page: {error}', { error: err.message }));
      }
    },
    showPageList() {
      this.showPages = true;
    },
    async loadNavigation() {
      try {
        console.log('IntraVox: Loading navigation...');
        console.log('IntraVox: Current browser language:', navigator.language);
        console.log('IntraVox: Current HTML lang:', document.documentElement.lang);
        const response = await axios.get(generateUrl('/apps/intravox/api/navigation'));
        console.log('IntraVox: Navigation response:', response.data);
        console.log('IntraVox: Server detected language:', response.data.language);
        this.navigation = response.data.navigation;
        this.canEditNavigation = response.data.canEdit;
        console.log('IntraVox: Navigation loaded with', this.navigation.items?.length || 0, 'items');
        console.log('IntraVox: Can edit navigation:', this.canEditNavigation);
      } catch (err) {
        console.error('IntraVox: Could not load navigation:', err);
        // Provide default empty navigation
        this.navigation = {
          type: 'dropdown',
          items: []
        };
        this.canEditNavigation = false;
      }
    },
    async saveNavigation(navigation) {
      try {
        console.log('IntraVox: Saving navigation...', navigation);
        const response = await axios.post(generateUrl('/apps/intravox/api/navigation'), navigation);
        console.log('IntraVox: Navigation saved successfully', response.data);
        this.navigation = response.data.navigation;
        this.showNavigationEditor = false;
        showSuccess(this.t('Navigation saved'));
      } catch (err) {
        console.error('IntraVox: Could not save navigation:', err);
        showError(this.t('Could not save navigation: {error}', { error: err.message }));
      }
    },
    navigateToItem(item) {
      if (item.pageId) {
        this.selectPage(item.pageId);
      } else if (item.url) {
        if (item.url.startsWith('http') || item.url.startsWith('//')) {
          window.open(item.url, '_blank');
        } else {
          window.location.href = item.url;
        }
      }
    },
    handleLanguageChange() {
      console.log('IntraVox: Language changed, reloading navigation and pages...');
      // Reload navigation and pages for the new language
      this.loadNavigation();
      this.loadPages();
      // Force Vue to re-render all translated strings
      this.$forceUpdate();
    }
  }
};
</script>

<style scoped>
#intravox-app {
  width: 100%;
  min-height: 100vh;
  background: var(--color-main-background);
}

/* Navigation Bar */
.intravox-nav-bar {
  background: var(--color-main-background);
  border-bottom: 1px solid var(--color-border);
  min-height: 50px;
  display: flex;
  align-items: center;
}

/* Header */
.intravox-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 20px;
  background: var(--color-main-background);
  border-bottom: 1px solid var(--color-border);
}

.header-left h1 {
  margin: 0;
  font-size: 24px;
  color: var(--color-main-text);
}

.page-title-input {
  margin: 0;
  padding: 8px 12px;
  font-size: 24px;
  font-weight: bold;
  color: var(--color-main-text);
  background: var(--color-main-background);
  border: 2px solid var(--color-border-dark);
  border-radius: 3px;
  min-width: 300px;
  box-sizing: border-box;
}

.page-title-input:focus {
  outline: none;
  border-color: var(--color-primary);
}

.header-right {
  display: flex;
  gap: 10px;
  align-items: center;
}

.intravox-content {
  padding: 20px;
  max-width: min(1600px, 95vw);
  margin: 0 auto;
  width: 100%;
}

.loading, .error {
  padding: 40px;
  text-align: center;
  font-size: 16px;
  color: var(--color-text-maxcontrast);
}

.error {
  color: var(--color-error);
}
</style>
