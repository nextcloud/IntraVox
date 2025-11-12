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
        <!-- Page Actions Menu (3-dot menu) -->
        <PageActionsMenu v-if="!isEditMode"
                         :is-edit-mode="isEditMode"
                         :permissions="pagePermissions"
                         @edit-navigation="showNavigationEditor = true"
                         @show-pages="showPageList"
                         @create-page="createNewPage"
                         @show-details="showDetailsSidebar = true"
                         @start-edit="startEditMode" />

        <!-- Edit Mode Actions (Save/Cancel) -->
        <template v-else>
          <NcButton @click="cancelEditMode"
                    type="secondary"
                    :aria-label="t('Cancel editing')">
            <template #icon>
              <Close :size="20" />
            </template>
            {{ t('Cancel') }}
          </NcButton>
          <NcButton @click="saveAndExitEditMode"
                    type="primary"
                    :aria-label="t('Save changes')">
            <template #icon>
              <ContentSave :size="20" />
            </template>
            {{ t('Save') }}
          </NcButton>
        </template>
      </div>
    </div>

    <!-- Navigation -->
    <div class="intravox-nav-bar">
      <Navigation :items="navigation.items"
                  :type="navigation.type"
                  @navigate="navigateToItem" />
    </div>

    <!-- Main content area with sidebar -->
    <div class="app-content-wrapper">
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

      <!-- Page Details Sidebar (inside content wrapper) -->
      <PageDetailsSidebar
        v-if="currentPage && !loading && !error"
        :is-open="showDetailsSidebar"
        :page-id="currentPage.id"
        :page-name="currentPage.title || t('Untitled Page')"
        @close="showDetailsSidebar = false"
        @version-restored="handleVersionRestored"
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

    <!-- Footer -->
    <Footer
      v-if="!loading && !error"
      :footer-content="footerContent"
      :can-edit="canEditFooter"
      :is-home-page="currentPage?.id === 'home'"
      @save="handleFooterSave"
    />

  </div>
</template>

<script>
import axios from '@nextcloud/axios';
import { generateUrl } from '@nextcloud/router';
import { translate as t } from '@nextcloud/l10n';
import { showSuccess, showError } from '@nextcloud/dialogs';
import { NcButton } from '@nextcloud/vue';
import ContentSave from 'vue-material-design-icons/ContentSave.vue';
import Close from 'vue-material-design-icons/Close.vue';
import PageViewer from './components/PageViewer.vue';
import PageEditor from './components/PageEditor.vue';
import PageListModal from './components/PageListModal.vue';
import NewPageModal from './components/NewPageModal.vue';
import Navigation from './components/Navigation.vue';
import NavigationEditor from './components/NavigationEditor.vue';
import Footer from './components/Footer.vue';
import PageActionsMenu from './components/PageActionsMenu.vue';
import PageDetailsSidebar from './components/PageDetailsSidebar.vue';
import './metavox-integration.js'; // Load MetaVox integration

export default {
  name: 'App',
  components: {
    NcButton,
    ContentSave,
    Close,
    PageViewer,
    PageEditor,
    PageListModal,
    NewPageModal,
    Navigation,
    NavigationEditor,
    Footer,
    PageActionsMenu,
    PageDetailsSidebar
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
      showDetailsSidebar: false,
      navigation: {
        type: 'dropdown',
        items: []
      },
      canEditNavigation: false,
      showNavigationEditor: false,
      currentLanguage: document.documentElement.lang || 'en',
      footerContent: '',
      canEditFooter: false
    };
  },
  computed: {
    /**
     * Page permissions based on current user's roles
     * This can be extended to support different user roles:
     * - admin: all permissions
     * - editor: can edit pages, create pages, view pages
     * - viewer: can only view pages
     */
    pagePermissions() {
      return {
        editNavigation: this.canEditNavigation,
        viewPages: true,  // Everyone can view pages
        createPage: this.canEditNavigation,  // For now, same as nav edit permission
        editPage: this.canEditNavigation,    // For now, same as nav edit permission
        deletePage: this.canEditNavigation   // For now, same as nav edit permission
      };
    }
  },
  mounted() {
    this.loadPages();
    this.loadNavigation();
    this.loadFooter();

    // Setup hash-based navigation
    window.addEventListener('hashchange', this.handleHashChange);

    // Poll for language changes (Nextcloud reloads the page, but we check after navigation)
    this.languageCheckInterval = setInterval(() => {
      const newLanguage = document.documentElement.lang || 'en';
      if (newLanguage !== this.currentLanguage) {
        this.currentLanguage = newLanguage;
        this.handleLanguageChange();
      }
    }, 1000);

    // Also use MutationObserver to watch for HTML lang attribute changes
    this.langObserver = new MutationObserver(() => {
      const newLanguage = document.documentElement.lang || 'en';
      if (newLanguage !== this.currentLanguage) {
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
    window.removeEventListener('hashchange', this.handleHashChange);
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
        this.loading = true;
        const response = await axios.get(generateUrl('/apps/intravox/api/pages'));
        this.pages = response.data;

        if (this.pages.length > 0) {
          // Check if we came from a /p/{uniqueId} URL
          const appElement = document.getElementById('app-intravox');
          const uniqueIdFromUrl = appElement?.dataset.uniqueId;

          // Check URL hash for page to load
          const hash = window.location.hash;
          let targetPage = null;
          let foundInHash = false;

          // Priority 1: uniqueId from URL path
          if (uniqueIdFromUrl) {
            targetPage = this.pages.find(p => p.uniqueId === uniqueIdFromUrl);
            if (targetPage) {
              foundInHash = true;
            }
          }

          // Priority 2: Hash in URL
          if (!targetPage && hash) {
            const pageIdentifier = hash.substring(1); // Remove '#'

            // Try to find page by ID or uniqueId
            targetPage = this.pages.find(p => p.id === pageIdentifier || p.uniqueId === pageIdentifier);
            if (targetPage) {
              foundInHash = true;
            }
          }

          // Fall back to home page if no hash or page not found
          if (!targetPage) {
            targetPage = this.pages.find(p => p.id === 'home') || this.pages[0];
          }

          // Only update URL if we didn't find the page in the hash (i.e., we're loading default/home)
          await this.selectPage(targetPage.id, !foundInHash);
        } else {
          this.error = this.t('No pages found. Please run the setup command first.');
        }
      } catch (err) {
        console.error('IntraVox: Error loading pages:', err);
        this.error = this.t('Could not load pages: {error}', { error: err.message });
      } finally {
        this.loading = false;
      }
    },
    async selectPage(pageId, updateUrl = true) {
      try {
        this.loading = true;
        const response = await axios.get(generateUrl(`/apps/intravox/api/pages/${pageId}`));
        this.currentPage = response.data;
        this.isEditMode = false;
        this.showPages = false;

        // Update URL hash if requested - use uniqueId for permanent links
        // Fall back to id if uniqueId is not available (legacy pages)
        if (updateUrl && this.currentPage) {
          const pageIdentifier = this.currentPage.uniqueId || this.currentPage.id;
          const newHash = `#${pageIdentifier}`;
          if (window.location.hash !== newHash) {
            window.location.hash = newHash;
          }
        }

        // Update page title and meta tags for better link previews
        this.updatePageMetadata();
      } catch (err) {
        console.error('IntraVox: Error selecting page:', err);
        showError(this.t('Could not load page: {error}', { error: err.message }));
      } finally {
        this.loading = false;
      }
    },
    updatePageMetadata() {
      if (!this.currentPage) return;

      // Update document title
      document.title = `${this.currentPage.title} - IntraVox`;

      // Update or create Open Graph meta tags for better link previews
      this.updateMetaTag('og:title', this.currentPage.title);
      this.updateMetaTag('og:type', 'article');
      this.updateMetaTag('og:url', window.location.href);
      this.updateMetaTag('og:site_name', 'IntraVox');

      // Twitter Card tags
      this.updateMetaTag('twitter:card', 'summary', 'name');
      this.updateMetaTag('twitter:title', this.currentPage.title, 'name');
    },
    updateMetaTag(property, content, attrName = 'property') {
      // Try to find existing meta tag
      let meta = document.querySelector(`meta[${attrName}="${property}"]`);

      if (!meta) {
        // Create new meta tag if it doesn't exist
        meta = document.createElement('meta');
        meta.setAttribute(attrName, property);
        document.head.appendChild(meta);
      }

      meta.setAttribute('content', content);
    },
    startEditMode() {
      // Store original state for rollback
      this.originalPage = JSON.parse(JSON.stringify(this.currentPage));
      this.editableTitle = this.currentPage?.title || '';
      this.isEditMode = true;
    },
    async saveAndExitEditMode() {
      try {
        // Update title if changed
        if (this.editableTitle && this.editableTitle !== this.currentPage?.title) {
          this.currentPage.title = this.editableTitle;
        }

        await this.savePage();

        this.isEditMode = false;
        this.originalPage = null;
      } catch (err) {
        console.error('[saveAndExitEditMode] Error:', err);
        showError(this.t('Failed to save: {error}', { error: err.message }));
      }
    },
    cancelEditMode() {
      // Rollback to original state
      if (this.originalPage) {
        this.currentPage = JSON.parse(JSON.stringify(this.originalPage));
      }
      this.isEditMode = false;
      this.originalPage = null;
      showSuccess(this.t('Changes cancelled'));
    },
    async savePage() {
      if (!this.currentPage || !this.currentPage.id) {
        throw new Error('No page to save');
      }

      // Ensure uniqueId exists (for legacy pages that don't have it yet)
      if (!this.currentPage.uniqueId) {
        this.currentPage.uniqueId = this.generateUniqueId();
      }

      const url = generateUrl(`/apps/intravox/api/pages/${this.currentPage.id}`);

      try {
        await axios.put(url, this.currentPage);
        showSuccess(this.t('Page saved'));
      } catch (err) {
        console.error('[savePage] Error:', err);
        const errorMsg = err.response?.data?.error || err.message || 'Unknown error';
        showError(this.t('Could not save page: {error}', { error: errorMsg }));
        throw err;
      }
    },
    async updatePage(updatedPage) {
      this.currentPage = updatedPage;
      // No auto-save - only save when user clicks Save button
    },
    createNewPage() {
      this.showNewPageModal = true;
    },
    generateSlug(title) {
      // Convert title to URL-friendly slug
      return title.toLowerCase()
        .normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '') // Remove diacritics
        .replace(/[^a-z0-9]+/g, '-')
        .replace(/^-+|-+$/g, '');
    },
    generateUniqueId() {
      // Generate a UUID v4 for guaranteed uniqueness across servers
      // This ensures no conflicts during migrations or multi-server scenarios
      return `page-${crypto.randomUUID()}`;
    },
    async handleCreatePage(title) {
      if (!title) return;

      // Generate slug from title for folder name (readable)
      const slug = this.generateSlug(title);

      if (!slug) {
        showError(this.t('Invalid page title'));
        return;
      }

      // Generate unique ID for internal references
      const uniqueId = this.generateUniqueId();

      try {
        const newPage = {
          id: slug, // Use slug as the page ID (folder name)
          uniqueId: uniqueId, // Store unique ID for internal references
          title: title,
          layout: {
            columns: 1,
            rows: [
              {
                widgets: [
                  {
                    id: 'widget-1',
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
        await this.selectPage(slug);
        // Open the new page in edit mode
        this.isEditMode = true;
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
        const response = await axios.get(generateUrl('/apps/intravox/api/navigation'));
        this.navigation = response.data.navigation;
        this.canEditNavigation = response.data.canEdit;
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
        const response = await axios.post(generateUrl('/apps/intravox/api/navigation'), navigation);
        this.navigation = response.data.navigation;
        this.showNavigationEditor = false;
        showSuccess(this.t('Navigation saved'));
      } catch (err) {
        console.error('IntraVox: Could not save navigation:', err);
        showError(this.t('Could not save navigation: {error}', { error: err.message }));
      }
    },
    async loadFooter() {
      try {
        const response = await axios.get(generateUrl('/apps/intravox/api/footer'));
        this.footerContent = response.data.content;
        this.canEditFooter = response.data.canEdit;
      } catch (err) {
        console.error('IntraVox: Could not load footer:', err);
        this.footerContent = '';
        this.canEditFooter = false;
      }
    },
    async handleFooterSave(content) {
      try {
        const response = await axios.post(generateUrl('/apps/intravox/api/footer'), {
          content: content
        });
        this.footerContent = response.data.content;
        showSuccess(this.t('Footer saved'));
      } catch (err) {
        console.error('IntraVox: Could not save footer:', err);
        showError(this.t('Could not save footer: {error}', { error: err.message }));
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
      // Reload navigation and pages for the new language
      this.loadNavigation();
      this.loadPages();
      // this.loadFooter(); // Temporarily disabled
      // Force Vue to re-render all translated strings
      this.$forceUpdate();
    },
    async handleVersionRestored(restoredPageData) {
      // Update current page with restored version
      this.currentPage = restoredPageData;
      showSuccess(this.t('Version restored successfully'));

      // Reload pages list to update timestamps, but stay on current page
      const currentPageId = this.currentPage.id;
      try {
        const response = await axios.get(generateUrl('/apps/intravox/api/pages'));
        this.pages = response.data;
        // Re-select the current page to refresh it without changing the URL
        await this.selectPage(currentPageId, false);
      } catch (err) {
        console.error('IntraVox: Error reloading pages after restore:', err);
      }
    },
    handleHashChange() {
      // Handle URL hash changes for navigation
      const hash = window.location.hash;
      if (!hash || hash === '#') {
        // No hash, load home page
        this.selectPage('home', false);
        return;
      }

      const pageIdentifier = hash.substring(1); // Remove '#'

      // Find page by ID or uniqueId
      const targetPage = this.pages.find(p => p.id === pageIdentifier || p.uniqueId === pageIdentifier);

      if (targetPage) {
        // Don't update URL since we're already responding to a hash change
        this.selectPage(targetPage.id, false);
      } else {
        // Fall back to home
        this.selectPage('home', true);
      }
    }
  }
};
</script>

<style scoped>
#intravox-app {
  width: 100%;
  max-width: 100vw;
  min-height: 100vh;
  background: var(--color-main-background);
  overflow-x: hidden;
  box-sizing: border-box;
}

/* Navigation Bar */
.intravox-nav-bar {
  background: var(--color-main-background);
  border-bottom: 1px solid var(--color-border);
  min-height: 50px;
  display: flex;
  align-items: center;
  width: 100%;
  box-sizing: border-box;
  position: relative;
  overflow: visible;
}

/* App Content Wrapper - contains main content and sidebar */
.app-content-wrapper {
  display: flex;
  position: relative;
  flex: 1;
  min-height: 0;
  width: 100%;
}

/* Header */
.intravox-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 20px;
  background: var(--color-main-background);
  border-bottom: 1px solid var(--color-border);
  gap: 20px;
  width: 100%;
  box-sizing: border-box;
}

.header-left {
  flex: 0 1 auto;
  min-width: 0;
}

.header-right {
  flex: 0 0 auto;
  margin-left: auto;
  display: flex;
  gap: 10px;
  align-items: center;
}

/* When screen is narrow, move actions next to title */
@media (max-width: 1200px) {
  .header-right {
    margin-left: 0;
  }
}

.header-left h1 {
  margin: 0;
  font-size: 24px;
  color: var(--color-main-text);
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
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

.intravox-content {
  padding: 20px;
  padding-bottom: 60px; /* Extra space at bottom */
  max-width: min(1600px, 95vw);
  margin: 0 auto;
  width: 100%;
  min-height: calc(100vh - 200px); /* Ensure content takes up space */
  box-sizing: border-box;
  flex: 1;
  overflow-y: auto;
}

.loading, .error {
  padding: 40px;
  text-align: center;
  font-size: 16px;
  color: var(--color-text-maxcontrast);
  flex: 1;
}

.error {
  color: var(--color-error);
}

/* Mobile styles */
@media (max-width: 768px) {
  #intravox-app {
    overflow-x: hidden;
  }

  .intravox-header {
    padding: 12px 8px;
    width: 100%;
    box-sizing: border-box;
  }

  .intravox-header h1 {
    font-size: 18px;
  }

  .intravox-nav-bar {
    width: 100%;
    box-sizing: border-box;
  }

  .intravox-content {
    padding: 8px;
    padding-bottom: 40px;
    max-width: 100%;
    width: 100%;
    box-sizing: border-box;
  }

  .page-title-input {
    min-width: 0;
    width: 100%;
    max-width: 250px;
    font-size: 18px;
  }

  .header-right {
    gap: 4px;
  }
}
</style>
