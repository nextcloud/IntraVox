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
        <!-- Edit Page Button (only visible when user has edit permissions for this page) -->
        <NcButton v-if="!isEditMode && canEditCurrentPage"
                  @click="startEditMode"
                  type="secondary"
                  :aria-label="t('Edit this page')">
          <template #icon>
            <Pencil :size="20" />
          </template>
          {{ t('Edit Page') }}
        </NcButton>

        <!-- Page Actions Menu (3-dot menu) -->
        <PageActionsMenu v-if="!isEditMode"
                         :is-edit-mode="isEditMode"
                         :permissions="pagePermissions"
                         @edit-navigation="showNavigationEditor = true"
                         @create-page="createNewPage" />

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
                  @navigate="navigateToItem"
                  @show-tree="showPageTree = true" />
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
        <!-- Breadcrumb row with Details button -->
        <div v-if="breadcrumb.length > 0 || !isEditMode" class="breadcrumb-row">
          <Breadcrumb v-if="breadcrumb.length > 0"
                      :breadcrumb="breadcrumb"
                      @navigate="selectPage" />
          <div v-else class="breadcrumb-spacer"></div>
          <button v-if="!isEditMode"
                  class="details-btn"
                  :class="{ 'details-btn-disabled': showDetailsSidebar }"
                  :disabled="showDetailsSidebar"
                  @click="showDetailsSidebar = true"
                  :aria-label="t('Details')"
                  :title="t('Details')">
            <Information :size="20" />
          </button>
        </div>

        <PageViewer
          v-if="!isEditMode && displayPage"
          :page="displayPage"
          @navigate="selectPage"
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
        :page-id="currentPage.uniqueId"
        :page-name="currentPage.title || t('Untitled Page')"
        :initial-tab="sidebarInitialTab"
        @close="handleCloseSidebar"
        @version-restored="handleVersionRestored"
        @version-selected="handleVersionSelected"
      />
    </div>

    <PageListModal
      v-if="showPages"
      :pages="pages"
      @close="showPages = false"
      @select="selectPage"
      @delete="deletePage"
    />

    <PageTreeModal
      v-if="showPageTree"
      :current-page-id="currentPage?.uniqueId"
      @close="showPageTree = false"
      @navigate="selectPage"
    />

    <NewPageModal
      v-if="showNewPageModal"
      :current-page-path="currentPage?.path || null"
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
      :is-home-page="isCurrentPageHome"
      @save="handleFooterSave"
      @navigate="selectPage"
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
import Pencil from 'vue-material-design-icons/Pencil.vue';
import Information from 'vue-material-design-icons/Information.vue';
import { defineAsyncComponent } from 'vue';
import PageViewer from './components/PageViewer.vue';
import Navigation from './components/Navigation.vue';
import Footer from './components/Footer.vue';
import PageActionsMenu from './components/PageActionsMenu.vue';
import Breadcrumb from './components/Breadcrumb.vue';
import CacheService from './services/CacheService.js';
import './metavox-integration.js'; // Load MetaVox integration

// Lazy-loaded components (only loaded when needed)
// This reduces initial bundle size and improves first load performance
const PageEditor = defineAsyncComponent(() => import('./components/PageEditor.vue'));
const PageListModal = defineAsyncComponent(() => import('./components/PageListModal.vue'));
const PageTreeModal = defineAsyncComponent(() => import('./components/PageTreeModal.vue'));
const NewPageModal = defineAsyncComponent(() => import('./components/NewPageModal.vue'));
const NavigationEditor = defineAsyncComponent(() => import('./components/NavigationEditor.vue'));
const PageDetailsSidebar = defineAsyncComponent(() => import('./components/PageDetailsSidebar.vue'));

// Constants
const HOME_PAGE_UNIQUE_ID = 'page-2e8f694e-147e-4793-8949-4732e679ae6b';

export default {
  name: 'App',
  components: {
    NcButton,
    ContentSave,
    Close,
    Pencil,
    Information,
    PageViewer,
    PageEditor,
    PageListModal,
    PageTreeModal,
    NewPageModal,
    Navigation,
    NavigationEditor,
    Footer,
    PageActionsMenu,
    PageDetailsSidebar,
    Breadcrumb
  },
  data() {
    return {
      HOME_PAGE_UNIQUE_ID,
      pages: [],
      currentPage: null,
      originalPage: null, // For rollback
      editableTitle: '',
      isEditMode: false,
      loading: true,
      error: null,
      showPages: false,
      showPageTree: false,
      showNewPageModal: false,
      showDetailsSidebar: false,
      breadcrumb: [],
      navigation: {
        type: 'dropdown',
        items: []
      },
      canEditNavigation: false,
      showNavigationEditor: false,
      currentLanguage: document.documentElement.lang || 'en',
      footerContent: '',
      canEditFooter: false,
      // Version preview state
      selectedVersion: null,
      versionPage: null,
      loadingVersion: false,
      // Sidebar state
      sidebarInitialTab: 'details-tab'
    };
  },
  computed: {
    /**
     * Page permissions based on folder-level ACLs from GroupFolder
     * Uses Nextcloud's permission system to determine what the user can do
     * Permissions are retrieved from the API for each page
     */
    pagePermissions() {
      const perms = this.currentPage?.permissions || {};
      return {
        editNavigation: this.canEditNavigation,
        viewPages: perms.canRead !== false,  // Default to true if not specified
        // Creating a page requires both write and create permissions
        createPage: (perms.canWrite && perms.canCreate) || false,
        editPage: perms.canWrite || false,
        deletePage: perms.canDelete || false
      };
    },
    /**
     * Helper to check if user can edit the current page
     */
    canEditCurrentPage() {
      return this.currentPage?.permissions?.canWrite || false;
    },
    /**
     * Returns the page to display - either the version preview or the current page
     */
    displayPage() {
      return this.versionPage || this.currentPage;
    },
    /**
     * Check if we're currently showing a version preview
     */
    isShowingVersion() {
      return this.versionPage !== null;
    },
    /**
     * Check if the current page is a home page
     * A page is considered home if it's at the language root level
     * This includes both "nl/home" and "nl" paths
     */
    isCurrentPageHome() {
      if (!this.currentPage || !this.currentPage.path) {
        return false;
      }
      const pathParts = this.currentPage.path.split('/').filter(p => p);

      // Home page can be either:
      // 1. Just language code: "nl" (length === 1)
      // 2. Language + home folder: "nl/home" (length === 2 && last part is "home")
      const isHome = pathParts.length === 1 ||
                     (pathParts.length === 2 && pathParts[1] === 'home');
      return isHome;
    }
  },
  async mounted() {
    // Load pages, navigation, and footer in parallel for faster initial load
    try {
      await Promise.all([
        this.loadPages(),
        this.loadNavigation(),
        this.loadFooter()
      ]);
    } catch (err) {
      // Errors are handled in individual loaders
    }

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
        // Check cache first
        const cached = CacheService.get('pages-list');
        if (cached) {
          this.pages = cached;
          this.loading = false;
          // Continue loading in background to update cache
        } else {
          this.loading = true;
        }

        const response = await axios.get(generateUrl('/apps/intravox/api/pages'));
        this.pages = response.data;

        // Update cache
        CacheService.set('pages-list', response.data);

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
            targetPage = this.pages.find(p => p.uniqueId === pageIdentifier || p.uniqueId === pageIdentifier);
            if (targetPage) {
              foundInHash = true;
            }
          }

          // Fall back to home page if no hash or page not found
          if (!targetPage) {
            targetPage = this.pages.find(p => p.uniqueId === HOME_PAGE_UNIQUE_ID) || this.pages[0];
          }

          // Validate targetPage has a uniqueId before selecting
          if (!targetPage || !targetPage.uniqueId) {
            console.error('IntraVox: No valid page found to load', { targetPage, pages: this.pages });
            this.error = this.t('No valid pages found. Pages might be missing uniqueId.');
            return;
          }

          // Only update URL if we didn't find the page in the hash (i.e., we're loading default/home)
          await this.selectPage(targetPage.uniqueId, !foundInHash);
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
        // Validate pageId
        if (!pageId || pageId === 'undefined') {
          console.error('IntraVox: Cannot select page with invalid ID:', pageId);
          showError(this.t('Invalid page ID'));
          return;
        }

        // Check cache first
        const cacheKey = `page-${pageId}`;
        const cached = CacheService.get(cacheKey);

        // Clear version preview when navigating to a different page
        this.clearVersionPreview();

        if (cached) {
          // Show cached data immediately - no waiting
          this.currentPage = cached;
          this.breadcrumb = cached.breadcrumb || [];
          this.isEditMode = false;
          this.showPages = false;
          this.loading = false;

          // Update URL and metadata immediately from cache
          if (updateUrl && this.currentPage) {
            const pageIdentifier = this.currentPage.uniqueId;
            const newHash = `#${pageIdentifier}`;
            if (window.location.hash !== newHash) {
              window.location.hash = newHash;
            }
          }
          this.updatePageMetadata();

          // Background refresh - don't await, fire and forget
          this.refreshPageInBackground(pageId, cacheKey);
          return;
        }

        // No cache - show loading and wait for response
        this.loading = true;
        const response = await axios.get(generateUrl(`/apps/intravox/api/pages/${pageId}`));
        this.currentPage = response.data;

        // Breadcrumb is now included in page response
        if (response.data.breadcrumb) {
          this.breadcrumb = response.data.breadcrumb;
        }

        this.isEditMode = false;
        this.showPages = false;

        // Update cache
        CacheService.set(cacheKey, response.data);

        // Update URL hash if requested - use uniqueId for permanent links
        if (updateUrl && this.currentPage) {
          const pageIdentifier = this.currentPage.uniqueId;
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
    async loadBreadcrumb(pageId) {
      try {
        const response = await axios.get(generateUrl(`/apps/intravox/api/pages/${pageId}/breadcrumb`));
        this.breadcrumb = response.data;
      } catch (err) {
        console.error('IntraVox: Error loading breadcrumb:', err);
        // Don't show error to user, breadcrumb is not critical
        this.breadcrumb = [];
      }
    },
    /**
     * Refresh page data in background without blocking UI
     * Used when showing cached data first
     */
    async refreshPageInBackground(pageId, cacheKey) {
      try {
        const response = await axios.get(generateUrl(`/apps/intravox/api/pages/${pageId}`));
        // Only update if user is still on the same page
        if (this.currentPage && this.currentPage.uniqueId === response.data.uniqueId) {
          this.currentPage = response.data;
          if (response.data.breadcrumb) {
            this.breadcrumb = response.data.breadcrumb;
          }
        }
        // Always update cache
        CacheService.set(cacheKey, response.data);
      } catch (err) {
        // Silent fail for background refresh - user already has cached data
        console.debug('IntraVox: Background refresh failed:', err.message);
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
      if (!this.currentPage || !this.currentPage.uniqueId) {
        throw new Error('No page to save');
      }

      // Ensure uniqueId exists (for legacy pages that don't have it yet)
      if (!this.currentPage.uniqueId) {
        this.currentPage.uniqueId = this.generateUniqueId();
      }

      const url = generateUrl(`/apps/intravox/api/pages/${this.currentPage.uniqueId}`);

      try {
        await axios.put(url, this.currentPage);

        // Invalidate cache for this page and pages list
        CacheService.delete(`page-${this.currentPage.uniqueId}`);
        CacheService.delete('pages-list');

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
    async handleCreatePage(data) {
      // Support both old format (string) and new format (object)
      const title = typeof data === 'string' ? data : data.title;
      const addToNavigation = typeof data === 'object' ? data.addToNavigation : false;

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

        // If we have a current page, create the new page as a child of the current page
        // by using the current page's path as the parent path
        if (this.currentPage && this.currentPage.path) {
          // Use the current page's full path as the parent path
          // This creates the new page as a child of the current page
          newPage.parentPath = this.currentPage.path;
        }

        await axios.post(generateUrl('/apps/intravox/api/pages'), newPage);
        showSuccess(this.t('Page created'));

        // Reload pages first so the new page is in the array
        await this.loadPages();

        // Add to navigation if requested (after pages are loaded)
        if (addToNavigation) {
          await this.addPageToNavigation(slug, title);
        }

        await this.selectPage(slug);
        // Open the new page in edit mode
        this.isEditMode = true;
      } catch (err) {
        showError(this.t('Could not create page: {error}', { error: err.message }));
      }
    },
    async addPageToNavigation(pageId, pageTitle) {
      try {
        // Get the full page path to determine parent hierarchy
        const page = this.pages.find(p => p.uniqueId === pageId);
        if (!page) {
          throw new Error('Page not found');
        }

        // Ensure navigation structure exists
        if (!this.navigation || !this.navigation.items) {
          showError(this.t('Navigation structure is invalid. Please reload the page.'));
          return;
        }

        // Build the path hierarchy from the page's path
        // Example path: "en/team-sales/campaigns" -> ["team-sales", "campaigns"]
        const pathParts = page.path ? page.path.split('/').filter(part =>
          part && !['en', 'nl', 'de', 'fr', 'departments'].includes(part)
        ) : [];

        // Helper function to find or create navigation item
        const findOrCreateNavItem = (items, pageId, title) => {
          let item = items.find(i => i.pageId === pageId);
          if (!item) {
            item = {
              id: pageId,
              title: title,
              pageId: pageId,
              url: null,
              target: null,
              children: []
            };
            items.push(item);
          }
          return item;
        };

        // Build the navigation hierarchy
        let currentLevel = this.navigation.items;

        // Handle root-level pages (pages with no parent path)
        if (pathParts.length === 0) {
          const newNavItem = {
            id: pageId,
            title: pageTitle,
            pageId: pageId,
            url: null,
            target: null,
            children: []
          };

          // Check if it already exists at root level
          const existingIndex = currentLevel.findIndex(i => i.pageId === pageId);
          if (existingIndex >= 0) {
            currentLevel[existingIndex] = newNavItem;
          } else {
            currentLevel.push(newNavItem);
          }
        } else {
          // For each part of the path except the last one (which is our new page)
          for (let i = 0; i < pathParts.length; i++) {
            const isLastPart = (i === pathParts.length - 1);
            const partPageId = pathParts[i];

            // Find the page to get its title
            const partPage = this.pages.find(p => p.uniqueId === partPageId);
            const partTitle = partPage ? partPage.title : partPageId;

            // If this is the last part and it matches our pageId, this is our page
            if (isLastPart && partPageId === pageId) {
              const newNavItem = {
                id: pageId,
                title: pageTitle,
                pageId: pageId,
                url: null,
                target: null,
                children: []
              };

              const existingIndex = currentLevel.findIndex(i => i.pageId === pageId);
              if (existingIndex >= 0) {
                currentLevel[existingIndex] = newNavItem;
              } else {
                currentLevel.push(newNavItem);
              }
            } else {
              // This is a parent, find or create it
              const parentNavItem = findOrCreateNavItem(currentLevel, partPageId, partTitle);
              currentLevel = parentNavItem.children;
            }
          }
        }

        // Save navigation (send the full navigation structure with type and items)
        await axios.post(generateUrl('/apps/intravox/api/navigation'), {
          navigation: this.navigation
        });

        showSuccess(this.t('Added to navigation'));
      } catch (err) {
        showError(this.t('Failed to add page to navigation'));
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

        if (this.currentPage?.uniqueId === pageId) {
          this.currentPage = null;
          if (this.pages.length > 0) {
            await this.selectPage(this.pages[0].uniqueId);
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
        const url = generateUrl('/apps/intravox/api/navigation');
        const response = await axios.get(url);
        this.navigation = response.data.navigation;
        // Use permissions object if available, fall back to canEdit for backwards compatibility
        const perms = response.data.permissions || {};
        this.canEditNavigation = perms.canWrite ?? response.data.canEdit ?? false;
      } catch (err) {
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
        showError(this.t('Could not save navigation: {error}', { error: err.message }));
      }
    },
    async loadFooter() {
      try {
        const response = await axios.get(generateUrl('/apps/intravox/api/footer'));
        this.footerContent = response.data.content;
        // Use permissions object if available, fall back to canEdit for backwards compatibility
        const perms = response.data.permissions || {};
        this.canEditFooter = perms.canWrite ?? response.data.canEdit ?? false;
      } catch (err) {
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
        showError(this.t('Could not save footer: {error}', { error: err.message }));
      }
    },
    navigateToItem(item) {
      if (item.uniqueId) {
        this.selectPage(item.uniqueId);
      } else if (item.pageId) {
        // Legacy support for old pageId
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
      const currentPageId = this.currentPage.uniqueId;
      try {
        const response = await axios.get(generateUrl('/apps/intravox/api/pages'));
        this.pages = response.data;
        // Re-select the current page to refresh it without changing the URL
        await this.selectPage(currentPageId, false);

        // Open sidebar with Versions tab and auto-select first version
        this.sidebarInitialTab = 'versions-tab';
        this.showDetailsSidebar = true;
      } catch (err) {
        console.error('IntraVox: Error reloading pages after restore:', err);
      }
    },
    handleHashChange() {
      // Handle URL hash changes for navigation
      const hash = window.location.hash;
      if (!hash || hash === '#') {
        // No hash, load home page
        this.selectPage(HOME_PAGE_UNIQUE_ID, false);
        return;
      }

      const pageIdentifier = hash.substring(1); // Remove '#'

      // Find page by uniqueId
      const targetPage = this.pages.find(p => p.uniqueId === pageIdentifier);

      if (targetPage) {
        // Don't update URL since we're already responding to a hash change
        this.selectPage(targetPage.uniqueId, false);
      } else {
        // Fall back to home
        this.selectPage(HOME_PAGE_UNIQUE_ID, true);
      }
    },
    async handleVersionSelected(data) {
      const { version, pageId } = data;
      this.selectedVersion = version;
      this.loadingVersion = true;

      try {
        // Fetch the version content
        const url = generateUrl(`/apps/intravox/api/pages/${pageId}/versions/${version.timestamp}/content`);
        const response = await axios.get(url);

        // Parse the JSON content from the version
        const versionJson = JSON.parse(response.data.content);

        // Create a page object from the version data
        this.versionPage = {
          ...versionJson,
          id: pageId,
          // Add a visual indicator that this is a version
          title: `${versionJson.title || this.currentPage.title} (${this.formatVersionDate(version.timestamp)})`
        };
      } catch (err) {
        showError(this.t('Could not load version: {error}', { error: err.message }));
        this.selectedVersion = null;
        this.versionPage = null;
      } finally {
        this.loadingVersion = false;
      }
    },
    clearVersionPreview() {
      this.selectedVersion = null;
      this.versionPage = null;
    },
    formatVersionDate(timestamp) {
      const date = new Date(timestamp * 1000);
      return date.toLocaleString();
    },
    handleCloseSidebar() {
      this.showDetailsSidebar = false;
      // Reset to default tab when closing
      this.sidebarInitialTab = 'details-tab';
      // Clear version preview when closing sidebar
      this.clearVersionPreview();
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
  flex: 1 1 auto;
  min-width: 0;
  overflow: hidden;
}

.header-right {
  flex: 0 0 auto;
  display: flex;
  gap: 10px;
  align-items: center;
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

/* Breadcrumb row with Details button */
.breadcrumb-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 8px;
  gap: 16px;
}

.breadcrumb-spacer {
  flex: 1;
}

.details-btn {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 36px;
  height: 36px;
  padding: 0;
  background: none;
  border: none;
  border-radius: var(--border-radius);
  color: var(--color-main-text);
  cursor: pointer;
  transition: background-color 0.1s ease;
  flex-shrink: 0;
}

.details-btn:hover {
  background-color: var(--color-background-hover);
}

.details-btn:active {
  background-color: var(--color-primary-element-light);
}

.details-btn-disabled,
.details-btn:disabled {
  opacity: 0.4;
  cursor: default;
  pointer-events: none;
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
