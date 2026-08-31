<template>
  <div id="intravox-app">
    <a href="#intravox-main-content" class="skip-link">{{ t('intravox', 'Skip to main content') }}</a>

    <!-- Sticky topbar wraps header + navigation so navigating between pages
         doesn't require scrolling all the way back up on long pages.
         Hidden when the language-fallback notice is showing — it is a
         full-screen, self-contained state with no page to act on. -->
    <div v-if="!showLanguageFallback" class="intravox-topbar" ref="topbar">

    <!-- Header with page title and actions -->
    <header class="intravox-header">
      <div class="header-left">
        <h1 v-if="!isEditMode">{{ currentPage?.title || 'IntraVox' }}</h1>
        <input
          v-else
          v-model="editableTitle"
          type="text"
          class="page-title-input"
          :placeholder="t('intravox', 'Page title')"
          :aria-label="t('intravox', 'Page title')"
        />
      </div>

      <div class="header-right">
        <!-- Publication status indicator (visible to editors in view mode).
             Readers never reach draft/scheduled/expired pages, so this badge is
             only ever seen by users with write access. -->
        <span v-if="!isEditMode && publishStatusBadge"
              class="draft-badge"
              :class="publishStatusBadge.class"
              :title="publishStatusBadge.tooltip">
          {{ publishStatusBadge.label }}
        </span>

        <!-- Content-language indicator. Shown in BOTH view and edit mode, but
             only when the page is NOT in the viewer's own language — an
             exception marker, not a permanent status field. -->
        <span v-if="pageLanguageBadge"
              class="draft-badge language-badge"
              :title="pageLanguageBadge.tooltip">
          {{ pageLanguageBadge.label }}
        </span>

        <!-- Share Button (only visible when NC share exists) -->
        <ShareButton v-if="!isEditMode && currentPage?.uniqueId"
                     :page-unique-id="currentPage.uniqueId"
                     :page-title="currentPage.title"
                     :language="currentLanguage" />

        <!-- Lock indicator (when another user is editing this page) -->
        <span v-if="!isEditMode && pageLock" class="page-lock-indicator">
          {{ t('intravox', '{displayName} is editing this page', { displayName: pageLock.displayName }) }}
          <NcButton v-if="canEditNavigation"
                    @click="forceUnlock"
                    type="tertiary"
                    :aria-label="t('intravox', 'Unlock')">
            {{ t('intravox', 'Unlock') }}
          </NcButton>
        </span>

        <!-- Edit Page Button (only visible when user has edit permissions for this page) -->
        <NcButton v-if="!isEditMode && canEditCurrentPage"
                  @click="startEditMode"
                  type="secondary"
                  :disabled="!!pageLock"
                  :aria-label="t('intravox', 'Edit this page')">
          <template #icon>
            <Pencil :size="20" />
          </template>
          {{ t('intravox', 'Edit page') }}
        </NcButton>

        <!-- Page Actions Menu (3-dot menu) -->
        <PageActionsMenu v-if="!isEditMode"
                         :is-edit-mode="isEditMode"
                         :permissions="pagePermissions"
                         :is-home="isCurrentPageHome"
                         :is-multilingual="isMultilingual"
                         :meta-vox-available="metaVoxAvailable"
                         @edit-navigation="showNavigationEditor = true"
                         @create-page="createNewPage"
                         @rename-page="renameCurrentPage"
                         @page-settings="showPageSettingsModal = true"
                         @save-as-template="showSaveAsTemplateModal = true"
                         @feed-settings="showFeedSettings = true"
                         @copy-page="copyCurrentPage"
                         @show-details="openSidebarTab('details-tab')"
                         @metavox="openSidebarTab('metavox-tab')"
                         @translate-page="openSidebarTab('translations-tab')"
                         @version-history="openSidebarTab('versions-tab')"
                         @delete-page="deleteCurrentPage" />

        <!-- Edit Mode Actions (Save/Cancel) -->
        <template v-else>
          <!-- When a Publish-on / Expire-on date governs, the manual toggle is
               overridden: show the effective state (read-only) with an
               explanation so the editor isn't confused by a stale "Draft". -->
          <span v-if="publicationDateGoverns"
                class="publish-state-chip"
                :class="editToggleDisplay.class"
                :title="t('intravox', 'Publication is controlled by the Publish on date. Clear the date to switch manually.')">
            <component :is="editToggleDisplay.icon" :size="18" />
            {{ editToggleDisplay.label }}
          </span>
          <NcButton v-else
                    @click="toggleDraftStatus"
                    :type="currentPage?.status === 'draft' ? 'warning' : 'secondary'"
                    :aria-label="currentPage?.status === 'draft' ? t('intravox', 'Draft — click to publish') : t('intravox', 'Published — click to unpublish')">
            <template #icon>
              <EyeOff :size="20" v-if="currentPage?.status === 'draft'" />
              <Eye :size="20" v-else />
            </template>
            {{ currentPage?.status === 'draft' ? t('intravox', 'Draft') : t('intravox', 'Published') }}
          </NcButton>
          <NcButton @click="cancelEditMode"
                    type="secondary"
                    :aria-label="t('intravox', 'Cancel editing')">
            <template #icon>
              <Close :size="20" />
            </template>
            {{ t('intravox', 'Cancel') }}
          </NcButton>
          <NcButton @click="saveAndExitEditMode"
                    type="primary"
                    :aria-label="t('intravox', 'Save changes')">
            <template #icon>
              <ContentSave :size="20" />
            </template>
            {{ t('intravox', 'Save') }}
          </NcButton>
        </template>
      </div>
    </header>

    <!-- Navigation -->
    <div class="intravox-nav-bar">
      <Navigation :items="navigation.items"
                  :type="navigation.type"
                  @navigate="navigateToItem" />
    </div>

    </div><!-- /.intravox-topbar -->

    <!-- Main content area with sidebar -->
    <div class="app-content-wrapper"
         :style="{ '--intravox-topbar-height': topbarHeight + 'px' }">

      <!-- Page structure panel: inhoudsopgave links van de content. Blijft
           open tijdens navigeren tot de gebruiker hem sluit. -->
      <PageTreeModal
        v-if="showPageTree"
        ref="pageTreeModal"
        variant="panel"
        :current-page-id="currentPage?.uniqueId"
        :language="currentPage?.language || null"
        :can-manage="canEditNavigation"
        :toc-page-key="tocPageKey"
        :force-pages-tab="isEditMode"
        @close="setPageTreeOpen(false)"
        @navigate="selectPage"
        @manage="showStructureManager = true"
        @reorder="reorderPages"
        @move="movePage"
        @rename="renamePageFromTree"
        @delete="deletePageFromTree"
        @set-homepage="handleSetHomepage"
        @copy="copyPageFromTree"
        @homepage="homepageUniqueId = $event"
      />
      <div v-if="loading" class="loading" role="status" aria-live="polite">
        {{ t('intravox', 'Loading …') }}
      </div>

      <!-- Welcome screen when no pages exist (first install) -->
      <WelcomeScreen v-else-if="showWelcomeScreen" />

      <!-- Language fallback: user's language has no content, another does -->
      <LanguageFallbackNotice v-else-if="showLanguageFallback"
                              :own-language="languageContentStatus.language"
                              :languages-with-content="languageContentStatus.languagesWithContent"
                              :language-names="languageContentStatus.languageNames"
                              :is-admin="languageContentStatus.isAdmin === true" />

      <div v-else-if="error" class="error" role="alert">
        {{ error }}
      </div>

      <main v-else class="intravox-content" id="intravox-main-content"
            :lang="contentLanguageAttr">
        <!-- Content is not in the reader's own language. Shown to READERS, not
             just editors: without it a reader simply meets an unexpected
             language with no explanation, and a screen reader would pronounce
             it with the wrong phonemes (the :lang above fixes that half, WCAG
             3.1.2). Offers the switch when a version in their language exists;
             otherwise it just explains. Never redirects — a shared link must
             open the page it names. -->
        <div v-if="contentLanguageNotice" class="content-language-notice" role="status">
          <span class="content-language-notice__text">
            {{ contentLanguageNotice.text }}
          </span>
          <button v-if="contentLanguageNotice.target"
                  class="content-language-notice__switch"
                  @click="selectPage(contentLanguageNotice.target.uniqueId)">
            {{ contentLanguageNotice.switchLabel }}
          </button>
        </div>

        <!-- Breadcrumb row with Details button -->
        <div class="breadcrumb-row">
          <!-- Structuur-toggle op hetzelfde niveau als de Details-knop (i),
               gespiegeld: structuur links, details rechts -->
          <button class="details-btn structure-btn"
                  :class="{ 'structure-btn-active': showPageTree }"
                  :aria-expanded="showPageTree ? 'true' : 'false'"
                  @click="setPageTreeOpen(!showPageTree)"
                  :aria-label="t('intravox', 'Page structure')"
                  :title="t('intravox', 'Page structure')">
            <FileTree :size="20" />
          </button>
          <Breadcrumb v-if="breadcrumb.length > 0"
                      :breadcrumb="breadcrumb"
                      @navigate="selectPage" />
          <div v-else class="breadcrumb-spacer"></div>
          <!-- Also available while editing: the sidebar holds the MetaVox tab
               with the Publish on date, which editors need during editing. -->
          <button class="details-btn"
                  :class="{ 'structure-btn-active': showDetailsSidebar }"
                  :aria-expanded="showDetailsSidebar ? 'true' : 'false'"
                  @click="setDetailsSidebarOpen(!showDetailsSidebar)"
                  :aria-label="t('intravox', 'Details')"
                  :title="t('intravox', 'Details')">
            <Information :size="20" />
          </button>
        </div>

        <PageViewer
          v-if="!isEditMode && displayPage"
          :page="displayPage"
          :engagement-settings="globalEngagementSettings"
          @navigate="selectPage"
          @rows-changed="tocRevision++"
        />
        <template v-else-if="isEditMode && currentPage">
          <!-- Explains why this page is hidden from readers (draft, scheduled or
               expired) and makes clear it is a visibility filter, not a permission. -->
          <NcNoteCard v-if="publishStateExplanation"
                      type="info"
                      class="draft-meaning-note">
            <p>{{ publishStateExplanation }}</p>
            <p>{{ t('intravox', 'This is a visibility filter, not a permission. The page file keeps the folder\'s normal access rights, so anyone who can read the folder can still open it via Files, WebDAV, search or a sync client. Do not rely on it for confidential content.') }}</p>
          </NcNoteCard>
          <PageEditor
            :page="currentPage"
            @update="updatePage"
          />
        </template>
      </main>

      <!-- Page Details Sidebar (inside content wrapper). NcAppSidebar is
           position:relative met height:100% — bedoeld voor Nextclouds #content,
           waar de omgeving een vaste hoogte heeft. In onze meescrollende layout
           zou hij daardoor wegschuiven; de sticky-wrapper houdt hem, net als het
           structuur-paneel, onder de topbar staan. -->
      <div v-show="currentPage && !loading && !error && showDetailsSidebar"
           class="details-sidebar-sticky">
      <PageDetailsSidebar
        v-show="currentPage && !loading && !error"
        :is-open="showDetailsSidebar"
        :page-id="currentPage?.uniqueId"
        :page-name="currentPage?.title || t('intravox', 'Untitled page')"
        :initial-tab="sidebarInitialTab"
        :translations="currentPage?.translations || []"
        :language-names="languageContentStatus?.languageNames || {}"
        :is-multilingual="isMultilingual"
        :meta-vox-available="metaVoxAvailable"
        :file-id="currentPage?.fileId || null"
        :groupfolder-id="currentPage?.groupfolderId || null"
        @close="handleCloseSidebar"
        @version-restored="handleVersionRestored"
        @version-selected="handleVersionSelected"
        @metadata-saved="refreshPublicationState"
        @navigate="selectPage"
        @translations-changed="handleTranslationsChanged"
      />
      </div><!-- /.details-sidebar-sticky -->
    </div>


    <!-- Structuur beheren: in een modal, waar de actieknoppen per pagina de
         ruimte hebben die het smalle paneel niet heeft -->
    <PageTreeModal
      v-if="showStructureManager"
      ref="structureManager"
      variant="modal"
      start-in-manage-mode
      :current-page-id="currentPage?.uniqueId"
      :language="currentPage?.language || null"
      :can-manage="canEditNavigation"
      @close="handleStructureManagerClose"
      @navigate="selectPage"
      @reorder="reorderPages"
      @move="movePage"
      @rename="renamePageFromTree"
      @delete="deletePageFromTree"
      @set-homepage="handleSetHomepage"
      @copy="copyPageFromTree"
      @homepage="homepageUniqueId = $event"
    />

    <NewPageModal
      v-if="showNewPageModal"
      :current-page-path="currentPage?.path || null"
      @close="showNewPageModal = false"
      @create="handleCreatePage"
      @create-from-template="handleCreatePageFromTemplate"
    />

    <RenamePageModal
      v-if="renameTarget"
      :page-id="renameTarget.pageId"
      :current-title="renameTarget.title"
      @close="renameTarget = null"
      @renamed="onPageRenamed"
    />

    <NavigationEditor
      v-if="showNavigationEditor"
      :navigation="navigationForEditor"
      :pages="pages"
      @close="showNavigationEditor = false"
      @save="saveNavigation"
    />

    <PageSettingsModal
      v-if="showPageSettingsModal"
      :page-unique-id="currentPage?.uniqueId"
      :settings="currentPage?.settings || {}"
      :global-settings="globalEngagementSettings"
      @close="showPageSettingsModal = false"
      @save="handlePageSettingsSave"
    />

    <SaveAsTemplateModal
      v-if="showSaveAsTemplateModal"
      :page-unique-id="currentPage?.uniqueId"
      :page-title="currentPage?.title"
      @close="showSaveAsTemplateModal = false"
      @saved="handleTemplateSaved"
    />

    <FeedSettings
      v-if="showFeedSettings"
      @close="showFeedSettings = false"
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
import { loadState } from '@nextcloud/initial-state';
import { translate } from '@nextcloud/l10n';
import { showSuccess, showError } from '@nextcloud/dialogs';
import { generateSlug } from './utils/slug';
import PageTreeSelect from './components/PageTreeSelect.vue';
import { NcButton, NcDialog, NcCheckboxRadioSwitch, NcNoteCard } from '@nextcloud/vue';
import ContentSave from 'vue-material-design-icons/ContentSave.vue';
import Close from 'vue-material-design-icons/Close.vue';
import Eye from 'vue-material-design-icons/Eye.vue';
import EyeOff from 'vue-material-design-icons/EyeOff.vue';
import ClockOutline from 'vue-material-design-icons/ClockOutline.vue';
import Pencil from 'vue-material-design-icons/Pencil.vue';
import Information from 'vue-material-design-icons/Information.vue';
import FileTree from 'vue-material-design-icons/FileTree.vue';
import { defineAsyncComponent } from 'vue';
import PageViewer from './components/PageViewer.vue';
import Navigation from './components/Navigation.vue';
import Footer from './components/Footer.vue';
import PageActionsMenu from './components/PageActionsMenu.vue';
import Breadcrumb from './components/Breadcrumb.vue';
import ShareButton from './components/ShareButton.vue';
import CacheService from './services/CacheService.js';
import { isSectionAnchor, scrollToHashAnchor, parseFragment } from './utils/headingAnchors.js';

// Lazy-loaded components (only loaded when needed)
// This reduces initial bundle size and improves first load performance
const PageEditor = defineAsyncComponent(() => import('./components/PageEditor.vue'));
const PageTreeModal = defineAsyncComponent(() => import('./components/PageTreeModal.vue'));
const NewPageModal = defineAsyncComponent(() => import('./components/NewPageModal.vue'));
const RenamePageModal = defineAsyncComponent(() => import('./components/RenamePageModal.vue'));
const NavigationEditor = defineAsyncComponent(() => import('./components/NavigationEditor.vue'));
const PageDetailsSidebar = defineAsyncComponent(() => import('./components/PageDetailsSidebar.vue'));
const WelcomeScreen = defineAsyncComponent(() => import('./components/WelcomeScreen.vue'));
const LanguageFallbackNotice = defineAsyncComponent(() => import('./components/LanguageFallbackNotice.vue'));
const PageSettingsModal = defineAsyncComponent(() => import('./components/PageSettingsModal.vue'));
const SaveAsTemplateModal = defineAsyncComponent(() => import('./components/SaveAsTemplateModal.vue'));
const FeedSettings = defineAsyncComponent(() => import('./components/FeedSettings.vue'));

// Helper function to find home page
const findHomePage = (pages) => {
  // Last-resort heuristic, used only when the server could not name the
  // homepage. Note that `slug` and `path` are NOT part of what /api/pages
  // returns (it carries uniqueId, title, modified, status, permissions), so
  // both conditions below are effectively dead against the real payload and
  // this collapses to pages[0] — the alphabetically first page.
  //
  // Kept as a guard against a blank screen rather than trusted: the correct
  // answer comes from the server's homepageUniqueId, which now resolves the
  // legacy loose home.json to its real uniqueId instead of the bare string
  // 'home' (which matched no page and sent readers here).
  return pages.find(p => p.slug === 'home' || p.path?.toLowerCase().includes('/home.json'))
    || pages.find(p => p.uniqueId === 'home')
    || pages[0];
};

export default {
  name: 'App',
  components: {
    NcButton,
    NcCheckboxRadioSwitch,
    NcNoteCard,
    PageTreeSelect,
    ContentSave,
    Close,
    Eye,
    EyeOff,
    ClockOutline,
    Pencil,
    Information,
    FileTree,
    PageViewer,
    PageEditor,
    PageTreeModal,
    RenamePageModal,
    NewPageModal,
    Navigation,
    NavigationEditor,
    Footer,
    PageActionsMenu,
    PageDetailsSidebar,
    Breadcrumb,
    WelcomeScreen,
    LanguageFallbackNotice,
    PageSettingsModal,
    SaveAsTemplateModal,
    ShareButton,
    FeedSettings
  },
  data() {
    return {
      // Whether MetaVox is installed. Read once at boot from initial state —
      // it cannot change while the page is open, and unlike the page response
      // it is never served from a client-side cache.
      metaVoxInstalled: loadState('intravox', 'metaVoxAvailable', false),
      pages: [],
      currentPage: null,
      originalPage: null, // For rollback
      editableTitle: '',
      isEditMode: false,
      loading: true,
      error: null,
      // Inhoudsopgave-paneel: voorkeur overleeft navigatie én reload
      showPageTree: window.localStorage?.getItem('intravox:page-tree-open') === '1',
      // Hoogte van de sticky topbar, als CSS-var doorgegeven aan het paneel
      topbarHeight: 0,
      // Structuur-beheermodal (los van het inhoudsopgave-paneel)
      showStructureManager: false,
      // Hoogt op wanneer de gerenderde pagina van vorm verandert (secties
      // open/dicht), zodat de inhoudsopgave opnieuw scant
      tocRevision: 0,
      showMoveDialog: false,
      homepageUniqueId: null,
      moveToRoot: false,
      movePageNode: null,
      moveTargetId: null,
      moveInProgress: false,
      showNewPageModal: false,
      // Rename modal target: { pageId, title } when open, null when closed.
      // Shared by the page-header action and the page-tree rename button.
      renameTarget: null,
      showSaveAsTemplateModal: false,
      // Net als het structuur-paneel: de gebruiker bepaalt hoeveel chrome er
      // staat, en die keuze overleeft navigatie en herladen
      showDetailsSidebar: window.localStorage?.getItem('intravox:details-open') === '1',
      breadcrumb: [],
      navigation: {
        type: 'dropdown',
        items: []
      },
      // Same structure, but with the items the menu hides (no link, no
      // children) still present -- see loadNavigation() and issue #104.
      navigationForEditor: {
        type: 'dropdown',
        items: []
      },
      canEditNavigation: false,
      showNavigationEditor: false,
      currentLanguage: document.documentElement.lang || 'en',
      // Language content status (drives the landing-page fallback notice).
      // null until loaded; shape: { language, hasContent, languagesWithContent,
      // primaryLanguage, languageNames }.
      languageContentStatus: null,
      footerContent: '',
      canEditFooter: false,
      // Version preview state
      selectedVersion: null,
      versionPage: null,
      loadingVersion: false,
      // Sidebar state
      sidebarInitialTab: 'details-tab',
      // Page settings modal
      showPageSettingsModal: false,
      // RSS Feed settings modal
      showFeedSettings: false,
      // Page locking
      pageLock: null,
      lockHeartbeatTimer: null,
      // Global engagement settings (loaded from API)
      globalEngagementSettings: {
        allowPageReactions: true,
        allowComments: true,
        allowCommentReactions: true,
        singleReactionPerUser: true
      }
    };
  },
  computed: {
    /**
     * Publication-status badge for the current page (view mode, editors only).
     * Uses the backend's effectivePublishState (draft | scheduled | expired |
     * published). Falls back to the manual status for older responses. Returns
     * null when the page is plainly published (no badge).
     */
    publishStatusBadge() {
      const state = this.currentPage?.effectivePublishState
        || (this.currentPage?.status === 'draft' ? 'draft' : 'published');
      switch (state) {
        case 'draft':
          return { class: 'draft', label: this.t('intravox', 'Draft'),
                   tooltip: this.t('intravox', 'Hidden from readers in IntraVox') };
        case 'scheduled':
          return { class: 'scheduled', label: this.t('intravox', 'Scheduled'),
                   tooltip: this.t('intravox', 'Hidden from readers in IntraVox until the publish date') };
        case 'expired':
          return { class: 'expired', label: this.t('intravox', 'Expired'),
                   tooltip: this.t('intravox', 'Past its expiration date, so hidden from readers in IntraVox') };
        default:
          return null;
      }
    },
    /**
     * Which language folder the current page lives in — shown ONLY when that
     * differs from the reader's own interface language.
     *
     * A badge that is always on screen stops being read: on an intranet whose
     * pages are mostly in the viewer's own language it would sit there saying
     * "English" on every English page, which carries no information. It is an
     * exception marker, not a status field — it appears exactly when the page
     * you are on is not in the language you would expect, which is also the
     * only case where "editing this saves back to another language" is worth
     * knowing.
     *
     * Uses the page's OWN language (derived server-side from its folder path),
     * never `currentLanguage`: that is the Nextcloud interface language, which
     * is exactly what this badge exists to contrast against.
     *
     * Like the publication badge it is only shown to users who can write, since
     * it is authoring information rather than something a reader acts on.
     */
    /**
     * Whether this intranet actually holds content in more than one language.
     *
     * Gates every piece of translation UI. Most intranets are single-language,
     * and making that majority carry a multilingual concept they never use is
     * the documented failure of the WordPress plugins — install either and
     * every editor pays the cost whether or not a second language exists.
     */
    isMultilingual() {
      const langs = this.languageContentStatus?.languagesWithContent;
      return Array.isArray(langs) && langs.length > 1;
    },
    /**
     * Whether MetaVox is installed, gating both its sidebar tab and its menu
     * entry.
     *
     * Read from initial state, NOT from the page response: this is a fact
     * about the installation rather than about a page, and page responses are
     * cached client-side. Taking it from a cached page meant a page visited
     * before MetaVox existed kept reporting it absent, which is exactly how
     * the tab went missing.
     *
     * Same rule as isMultilingual above: a tab that exists gets a menu entry,
     * and when the tab is absent the entry is too — so the menu always matches
     * what the sidebar shows.
     */
    metaVoxAvailable() {
      return this.metaVoxInstalled;
    },
    /**
     * The `lang` for the content region.
     *
     * Only set when the page differs from the interface language. Unlabelled
     * fallback content is a WCAG 3.1.2 failure, not a nicety: text inside a
     * `lang="de"` document is positively asserted to BE German, so a screen
     * reader applies German phonemes to a Dutch page.
     */
    contentLanguageAttr() {
      const code = this.currentPage?.language;
      if (!code) {
        return null;
      }
      const uiLang = (this.currentLanguage || '').split(/[-_]/)[0];
      return uiLang && uiLang !== code ? code : null;
    },
    /**
     * Reader-facing notice when the page is not in their own language.
     *
     * Two shapes, and the difference matters:
     *   - a version in the reader's language exists → explain AND offer to
     *     switch, so following a shared link never traps them;
     *   - it does not → only explain, so they know why the language changed.
     *
     * Deliberately never redirects. Every platform researched that auto-
     * redirected on language (SharePoint's old Variations) abandoned it, and
     * both W3C and Google are explicit: offer, do not force. A campaign link
     * has to open the page it names.
     */
    contentLanguageNotice() {
      const code = this.currentPage?.language;
      if (!code) {
        return null;
      }
      const uiLang = (this.currentLanguage || '').split(/[-_]/)[0];
      if (!uiLang || uiLang === code) {
        return null;
      }

      const pageLanguage = this.contentLanguageName(code);

      // A version in the reader's own language, if the page has one.
      const target = (this.currentPage?.translations || [])
        .find(t => t.language === uiLang && t.status !== 'draft');

      if (target) {
        const ownLanguage = this.contentLanguageName(uiLang);
        return {
          text: this.t('intravox', 'This page is in {language}.', { language: pageLanguage }),
          switchLabel: this.t('intravox', 'Read it in {language}', { language: ownLanguage }),
          target,
        };
      }

      return {
        text: this.t('intravox', 'This page is in {language}.', { language: pageLanguage }),
        switchLabel: null,
        target: null,
      };
    },
    pageLanguageBadge() {
      if (!this.canEditCurrentPage) {
        return null;
      }
      const code = this.currentPage?.language;
      if (!code) {
        return null;
      }
      // The interface language, normalised to a base code ('de_DE' -> 'de') to
      // match how content folders are named.
      const uiLang = (this.currentLanguage || '').split(/[-_]/)[0];
      if (!uiLang || uiLang === code) {
        return null;
      }
      const label = this.contentLanguageName(code);
      return {
        label,
        tooltip: this.t('intravox', 'This page is in {language}, not your own language. Editing it saves back to {language}.', { language: label }),
      };
    },
    /**
     * One-line explanation of why the page is currently hidden from readers,
     * shown in an info note card while editing. Returns '' for a plainly
     * published page (no note needed).
     */
    publishStateExplanation() {
      const state = this.currentPage?.effectivePublishState
        || (this.currentPage?.status === 'draft' ? 'draft' : 'published');
      switch (state) {
        case 'draft':
          return this.t('intravox', 'This page is a draft: hidden from readers everywhere in IntraVox until you publish it.');
        case 'scheduled':
          return this.t('intravox', 'This page is scheduled: it has a publish date in the future, so it stays hidden from readers until that moment and then appears automatically. The publish date overrides the Draft/Published button — clear the date to switch manually again.');
        case 'expired':
          return this.t('intravox', 'This page has expired: its expiration date has passed, so it is hidden from readers. Change or clear the expiration date to make it visible again.');
        default:
          return '';
      }
    },
    /**
     * True when a Publish-on / Expire-on date is set and therefore governs
     * publication — the manual draft/published toggle is overridden.
     */
    publicationDateGoverns() {
      return !!this.currentPage?.publicationDateActive;
    },
    /**
     * Read-only display for the edit-mode toggle when a date governs: shows the
     * effective state (Scheduled / Published / Expired) instead of raw status.
     */
    editToggleDisplay() {
      const state = this.currentPage?.effectivePublishState || 'published';
      switch (state) {
        case 'scheduled':
          return { class: 'scheduled', icon: 'ClockOutline', label: this.t('intravox', 'Scheduled') };
        case 'expired':
          return { class: 'expired', icon: 'EyeOff', label: this.t('intravox', 'Expired') };
        default:
          return { class: 'published', icon: 'Eye', label: this.t('intravox', 'Published') };
      }
    },
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
        deletePage: perms.canDelete || false,
        // Save as template requires read on the page (to copy content)
        // Note: The backend also checks if user can write to _templates folder
        saveAsTemplate: perms.canRead !== false
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
     * Sleutel die verandert zodra de inhoudsopgave opnieuw moet scannen: een
     * andere pagina, een versievoorbeeld, of een sectie die open/dicht klapt.
     */
    tocPageKey() {
      const id = this.displayPage?.uniqueId || '';
      return `${id}:${this.isShowingVersion ? 'v' : ''}:${this.tocRevision}`;
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
      if (!this.currentPage) {
        return false;
      }
      // Prefer the server-resolved homepage pointer (configurable homepage).
      if (this.homepageUniqueId && this.currentPage.uniqueId) {
        return this.currentPage.uniqueId === this.homepageUniqueId;
      }
      if (!this.currentPage.path) {
        return false;
      }
      const pathParts = this.currentPage.path.split('/').filter(p => p);

      // Legacy fallback. Home page can be either:
      // 1. Just language code: "nl" (length === 1)
      // 2. Language + home folder: "nl/home" (length === 2 && last part is "home")
      const isHome = pathParts.length === 1 ||
                     (pathParts.length === 2 && pathParts[1] === 'home');
      return isHome;
    },
    /**
     * Show welcome screen when no pages exist (first-time install)
     */
    showWelcomeScreen() {
      return !this.loading && this.pages.length === 0 && !this.error;
    },
    /**
     * Show the language-fallback notice when the user's own language has no
     * real (editor-authored) content but at least one other language does.
     * This replaces the silent "generic placeholder page" the user would
     * otherwise see when content exists only in another language.
     */
    showLanguageFallback() {
      const s = this.languageContentStatus;
      if (this.loading || this.error || !s) {
        return false;
      }
      // hasContent now means "something will be served" (own language, the
      // recommended language, or English — see PageService::getLanguageContentStatus,
      // issue #75). Only block with the notice when nothing can be served.
      return !s.hasContent;
    }
  },
  async mounted() {
    // Load content status FIRST so loadPages knows whether the user's language
    // has content. If it doesn't, we show the fallback notice instead of trying
    // to select a home page (which would 404 when only other languages exist).
    await this.loadContentStatus();

    // Load pages, navigation, footer, and settings in parallel
    try {
      await Promise.all([
        this.loadPages(),
        this.loadNavigation(),
        this.loadFooter(),
        this.loadEngagementSettings()
      ]);
    } catch (err) {
      // Errors are handled in individual loaders
    }

    // Setup hash-based navigation
    window.addEventListener('hashchange', this.handleHashChange);

    // Release page lock on tab/window close
    window.addEventListener('beforeunload', this.handleBeforeUnload);

    // Use MutationObserver to watch for HTML lang attribute changes
    // (more efficient than setInterval polling)
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

    // Topbar-hoogte bijhouden voor het sticky structuur-paneel en voor de
    // scroll-offset van koppen; de hoogte varieert (editmodus-knoppen, smalle
    // schermen, titel over twee regels)
    this.topbarObserver = new ResizeObserver(() => {
      this.setTopbarHeight(this.$refs.topbar?.offsetHeight || 0);
    });
    if (this.$refs.topbar) {
      this.setTopbarHeight(this.$refs.topbar.offsetHeight);
      this.topbarObserver.observe(this.$refs.topbar);
    }
  },
  beforeUnmount() {
    window.removeEventListener('hashchange', this.handleHashChange);
    window.removeEventListener('beforeunload', this.handleBeforeUnload);
    this.stopLockHeartbeat();
    if (this.topbarObserver) {
      this.topbarObserver.disconnect();
    }
    document.documentElement.style.removeProperty('--intravox-topbar-height');
    if (this.langObserver) {
      this.langObserver.disconnect();
    }
  },
  methods: {
    t(app, text, vars) {
      return translate(app, text, vars);
    },
    async handleStructureManagerClose() {
      this.showStructureManager = false;
      // De boom in het paneel kan verouderd zijn na verplaatsen/hernoemen
      await this.refreshPageTrees();
    },
    // Ververst beide bomen: het inhoudsopgave-paneel en (indien open) de
    // beheermodal, die dezelfde structuur tonen
    async refreshPageTrees() {
      await Promise.all([
        this.$refs.pageTreeModal?.loadTree(),
        this.$refs.structureManager?.loadTree()
      ].filter(Boolean));
    },
    setTopbarHeight(height) {
      this.topbarHeight = height;
      // Ook op :root, zodat scroll-margin-top van koppen (Widget.vue) de echte
      // topbar-hoogte gebruikt; die CSS staat buiten .app-content-wrapper.
      document.documentElement.style.setProperty('--intravox-topbar-height', `${height}px`);
    },
    setPageTreeOpen(open) {
      this.showPageTree = open;
      try {
        window.localStorage.setItem('intravox:page-tree-open', open ? '1' : '0');
      } catch (e) {
        // Zonder storage (private mode) geldt de voorkeur alleen deze sessie
      }
    },
    setDetailsSidebarOpen(open) {
      this.showDetailsSidebar = open;
      try {
        window.localStorage.setItem('intravox:details-open', open ? '1' : '0');
      } catch (e) {
        // Zonder storage (private mode) geldt de voorkeur alleen deze sessie
      }
    },
    async loadPages() {
      try {
        // Check cache first
        const cacheKey = 'pages-list';
        const cached = CacheService.get(cacheKey);
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

          // Priority 2: ?page=<uniqueId> query (used by section deep links,
          // where the hash carries the section anchor #h-… instead of the page).
          if (!targetPage) {
            const pageFromQuery = new URLSearchParams(window.location.search).get('page');
            if (pageFromQuery) {
              targetPage = this.pages.find(p => p.uniqueId === pageFromQuery);
              if (targetPage) {
                foundInHash = true;
              }
            }
          }

          // Priority 3: Hash in URL. Een sectielink draagt de pagina bij zich
          // (`#<pageId>#h-<slug>`); een kaal `#h-…` benoemt geen pagina.
          if (!targetPage && hash) {
            const pageIdentifier = parseFragment(hash).pageId;
            if (pageIdentifier) {
              targetPage = this.pages.find(p => p.uniqueId === pageIdentifier);
              if (targetPage) {
                foundInHash = true;
              }
            }
          }

          // The page list is ONE language — the one this reader is served — but
          // a link can point at a page in any language. Before giving up, ask
          // the server, which resolves across every language folder.
          //
          // Without this the backend's cross-language resolution is unreachable
          // from the UI: a link to a page in another language fell through to
          // the block below and landed the reader on their own homepage, with
          // the address bar rewritten so the original link was lost. Sharing a
          // page in a campaign therefore did not work at all unless every
          // recipient happened to share the author's language.
          if (!targetPage) {
            const requestedId = uniqueIdFromUrl
              || new URLSearchParams(window.location.search).get('page')
              || (hash ? parseFragment(hash).pageId : null);

            if (requestedId) {
              const resolved = await this.resolvePageById(requestedId);
              if (resolved) {
                targetPage = resolved;
                // Keep the URL as the sender wrote it.
                foundInHash = true;
              } else {
                // The link named a page that does not exist. Say so: the
                // homepage appearing instead, with the address bar quietly
                // rewritten, gave the reader no way to tell the link had
                // failed — they simply saw the wrong page.
                showError(this.t(
                  'intravox',
                  'That page could not be found. Showing the home page instead.'
                ));
              }
            }
          }

          // Fall back to home page if no hash or page not found. Prefer the
          // configured homepage pointer, then the slug/path heuristic.
          if (!targetPage) {
            // `homepageUniqueId` arrives via an event from the page tree, which
            // has not rendered yet on first load — so on a plain visit to
            // /apps/intravox it is still null here and the reader fell straight
            // through to findHomePage(), which lands on pages[0]: whatever sorts
            // first alphabetically. Ask the server directly instead.
            if (!this.homepageUniqueId) {
              await this.loadHomepageUniqueId();
            }
            if (this.homepageUniqueId) {
              targetPage = this.pages.find(p => p.uniqueId === this.homepageUniqueId)
                || await this.resolvePageById(this.homepageUniqueId);
            }
            if (!targetPage) {
              targetPage = findHomePage(this.pages);
            }
          }

          // Validate targetPage has a uniqueId before selecting
          if (!targetPage || !targetPage.uniqueId) {
            console.error('IntraVox: No valid page found to load', { targetPage, pages: this.pages });
            this.error = this.t('intravox', 'No valid pages found. Pages might be missing UID.');
            return;
          }

          // If the user's language has no content, the fallback notice takes
          // over the screen — don't try to select a page (the pages list here
          // belongs to a fallback language and selecting it would be confusing,
          // and a stale cache could even 404).
          if (this.showLanguageFallback) {
            this.loading = false;
            return;
          }

          // Only update URL if we didn't find the page in the hash (i.e., we're
          // loading default/home). Opening /apps/intravox with no hash lands on
          // the homepage and the address bar becomes #<its uniqueId>, so the URL
          // always names the page on screen — refreshing, bookmarking or sharing
          // it then does what the reader expects.
          await this.selectPage(targetPage.uniqueId, !foundInHash);
        }
        // If no pages found, don't set error - the welcome screen will be shown instead
      } catch (err) {
        console.error('IntraVox: Error loading pages:', err);
        this.error = this.t('intravox', 'Could not load pages: {error}', { error: err.message });
      } finally {
        this.loading = false;
      }
    },
    /**
     * Display name for a CONTENT language.
     *
     * Nextcloud's language list describes INTERFACE translations, so its names
     * carry variant suffixes: 'English (US)', 'Deutsch (Persönlich: Du)',
     * 'Deutsch (Sie)'. Those distinguish translations of the UI, not languages
     * content is written in — a content folder is plain `de`. Telling a reader
     * "This page is in Deutsch (Persönlich: Du)" is nonsense, so the
     * parenthesised part is dropped.
     *
     * Falls back to the uppercased code when the language is unknown, so an
     * exotic content folder still reads as 'EO' rather than nothing.
     */
    contentLanguageName(code) {
      if (!code) {
        return '';
      }
      const names = this.languageContentStatus?.languageNames || {};
      const full = names[code];
      if (!full) {
        return String(code).toUpperCase();
      }
      const base = String(full).split('(')[0].trim();
      return base || String(full);
    },
    /**
     * Open the details sidebar on a given tab.
     *
     * The single route from the actions menu into the sidebar, shared by
     * "Translate page" and "Version history". Both are INSPECTION — bodies of
     * information about this page — so they live in the sidebar, and the menu
     * entry is an accelerator to them rather than a second implementation.
     * (NN/g and Apple HIG both call for exactly this: commands hidden in a menu
     * should have a visible home elsewhere, and vice versa.)
     *
     * Order is load-bearing: set the tab, THEN open. PageDetailsSidebar applies
     * `initialTab` on the closed->open edge only, so opening first would leave
     * it on whatever tab was last shown.
     *
     * @param {string} tabId id of the NcAppSidebarTab to activate
     */
    async openSidebarTab(tabId) {
      if (!this.currentPage?.uniqueId) {
        showError(this.t('intravox', 'Open a page first'));
        return;
      }
      // A tab that is conditionally rendered does not exist on every install,
      // and NcAppSidebar silently falls back to the first tab rather than
      // failing — the wrong destination with no explanation. Refuse instead.
      if (tabId === 'translations-tab' && !this.isMultilingual) {
        return;
      }

      // Already open? The initialTab watcher deliberately refuses to act while
      // the sidebar is open (so a prop change cannot override the tab the user
      // picked). Close it first so the edge fires, rather than weakening that
      // guard for everyone else.
      if (this.showDetailsSidebar) {
        this.showDetailsSidebar = false;
        await this.$nextTick();
      }

      this.sidebarInitialTab = tabId;
      this.setDetailsSidebarOpen(true);
    },
    /**
     * Adopt the translation set after the editor linked or unlinked one, so the
     * reader notice and the switcher update without a page reload.
     */
    handleTranslationsChanged(translations) {
      if (this.currentPage) {
        this.currentPage = { ...this.currentPage, translations: translations || [] };
      }
    },
    /**
     * Fetch the configured homepage from the server.
     *
     * `homepageUniqueId` normally arrives as an event from the page tree, but
     * that component has not rendered when loadPages() runs on first load — so
     * on a plain visit to /apps/intravox the pointer was still null and the
     * reader landed on pages[0], the alphabetically first page, instead of the
     * homepage. Asking directly removes that ordering dependency.
     *
     * Never throws: without an answer the caller falls back to its heuristic,
     * which is the behaviour that was there before.
     */
    async loadHomepageUniqueId() {
      try {
        const response = await axios.get(generateUrl('/apps/intravox/api/pages/tree'));
        const id = response?.data?.homepageUniqueId;
        if (typeof id === 'string' && id !== '') {
          this.homepageUniqueId = id;
        }
      } catch (err) {
        console.warn('[IntraVox] Could not resolve the configured homepage:', err.message);
      }
    },
    /**
     * Ask the server for a page the local list does not contain.
     *
     * The list held in `this.pages` covers ONE language — whichever this reader
     * is served — while a shared link can point at a page in any language. The
     * API resolves across all of them, so this is what makes a link from a
     * colleague working in another language actually open.
     *
     * Returns a list-shaped entry (uniqueId + title) or null when the page
     * genuinely does not exist. Never throws: a failed lookup must fall through
     * to the homepage, exactly as an unknown id always did.
     */
    async resolvePageById(pageId) {
      if (!pageId || pageId === 'undefined' || isSectionAnchor(`#${pageId}`)) {
        return null;
      }
      try {
        const url = generateUrl(`/apps/intravox/api/pages/${encodeURIComponent(pageId)}`);
        const response = await axios.get(url);
        const page = response?.data;
        if (!page?.uniqueId) {
          return null;
        }
        // Add it to the local list so selectPage() and the rest of the UI can
        // find it the same way they find a same-language page.
        if (!this.pages.some(p => p.uniqueId === page.uniqueId)) {
          this.pages.push({
            uniqueId: page.uniqueId,
            title: page.title,
            modified: page.modified,
            status: page.status,
            permissions: page.permissions,
          });
        }
        return page;
      } catch (err) {
        // 404 is the ordinary answer for a stale or mistyped link; anything
        // else is worth a console note but must not break the load.
        if (err.response?.status !== 404) {
          console.warn('[IntraVox] Could not resolve page by id:', pageId, err.message);
        }
        return null;
      }
    },
    async selectPage(pageId, updateUrl = true) {
      try {
        // Validate pageId
        if (!pageId || pageId === 'undefined') {
          console.error('IntraVox: Cannot select page with invalid ID:', pageId);
          showError(this.t('intravox', 'Invalid page ID'));
          return;
        }

        // Release any active lock when navigating away from a page being edited
        if (this.isEditMode && this.currentPage?.uniqueId !== pageId) {
          await this.releaseLock();
          if (this.originalPage) {
            this.currentPage = structuredClone(this.originalPage);
          }
          this.isEditMode = false;
          this.originalPage = null;
        }

        // Bij navigatie blijft de sidebar staan (zoals het structuur-paneel):
        // hij is een vaste werkbalk, niet iets dat per pagina opnieuw geopend
        // moet worden. De gekozen tab blijft ook staan — de sidebar herlaadt
        // zijn inhoud zelf via zijn pageId-watcher.

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

          // Check if this page is locked by another user
          this.checkPageLock(pageId);

          // Smart background refresh - only if cache is older than 2 minutes
          const cacheAge = CacheService.getAge(cacheKey);
          const minRefreshAge = 2 * 60 * 1000; // 2 minutes
          if (!cacheAge || cacheAge > minRefreshAge) {
            this.refreshPageInBackground(pageId, cacheKey);
          }
          return;
        }

        // No cache - fetch page and lock status in parallel
        this.loading = true;
        const [response] = await Promise.all([
          axios.get(generateUrl(`/apps/intravox/api/pages/${pageId}`)),
          this.checkPageLock(pageId),
        ]);
        this.currentPage = response.data;

        // Breadcrumb is now included in page response
        if (response.data.breadcrumb) {
          this.breadcrumb = response.data.breadcrumb;
        }

        this.isEditMode = false;

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
        // Suppress the toast when the language-fallback notice owns the screen:
        // there is no page to load and the notice already explains why.
        if (!this.showLanguageFallback) {
          showError(this.t('intravox', 'Could not load page: {error}', { error: err.message }));
        }
      } finally {
        this.loading = false;
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
    async startEditMode() {
      const pageId = this.currentPage?.uniqueId;
      if (!pageId) return;

      try {
        // Acquire page lock before entering edit mode
        const lockUrl = generateUrl(`/apps/intravox/api/pages/${pageId}/lock`);
        const response = await axios.post(lockUrl);

        if (response.data.success) {
          // Lock acquired — enter edit mode
          this.originalPage = structuredClone(this.currentPage);
          this.editableTitle = this.currentPage?.title || '';
          this.isEditMode = true;
          this.pageLock = null;

          // Clear any version preview - user should edit current version
          this.clearVersionPreview();

          // Start heartbeat to keep lock alive
          this.startLockHeartbeat(pageId);

          // Notify sidebar to reset to current version selection
          window.dispatchEvent(new CustomEvent('intravox:edit:started', {
            detail: { pageId }
          }));
        }
      } catch (err) {
        if (err.response?.status === 409) {
          // Lock denied — another user is editing
          const lock = err.response.data.lock;
          this.pageLock = lock;
          showError(this.t('intravox', '{displayName} is editing this page', {
            displayName: lock?.displayName || 'Someone',
          }));
        } else {
          showError(this.t('intravox', 'Could not start editing: {error}', { error: err.message }));
        }
      }
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

        // Release lock after successful save
        await this.releaseLock();
      } catch (err) {
        console.error('[saveAndExitEditMode] Error:', err);
        showError(this.t('intravox', 'Failed to save: {error}', { error: err.message }));
      }
    },
    cancelEditMode() {
      // Rollback to original state
      if (this.originalPage) {
        this.currentPage = structuredClone(this.originalPage);
      }
      this.isEditMode = false;
      this.originalPage = null;
      showSuccess(this.t('intravox', 'Changes cancelled'));

      // Release lock after cancelling
      this.releaseLock();
    },
    toggleDraftStatus() {
      if (!this.currentPage) return;
      this.currentPage.status = this.currentPage.status === 'draft' ? 'published' : 'draft';
    },
    /**
     * Check if a page is locked by another user (called on page load)
     */
    async checkPageLock(pageId) {
      try {
        const url = generateUrl(`/apps/intravox/api/pages/${pageId}/lock`);
        const response = await axios.get(url);
        this.pageLock = response.data.lock || null;
      } catch (err) {
        this.pageLock = null;
      }
    },
    /**
     * Release the current page lock
     */
    async releaseLock() {
      this.stopLockHeartbeat();
      const pageId = this.currentPage?.uniqueId;
      if (!pageId) return;

      try {
        const url = generateUrl(`/apps/intravox/api/pages/${pageId}/lock`);
        await axios.delete(url);
        this.pageLock = null;
      } catch (err) {
        // Best effort — lock will auto-expire after 15 minutes
        console.warn('[IntraVox] Failed to release lock:', err.message);
      }
    },
    /**
     * Start heartbeat to keep the page lock alive
     */
    startLockHeartbeat(pageId) {
      this.stopLockHeartbeat();
      this.lockHeartbeatTimer = setInterval(async () => {
        try {
          const url = generateUrl(`/apps/intravox/api/pages/${pageId}/lock`);
          const response = await axios.put(url);
          if (!response.data.success) {
            showError(this.t('intravox', 'Your edit lock has expired. Please save your work.'));
            this.stopLockHeartbeat();
          }
        } catch (err) {
          if (err.response?.status === 409) {
            showError(this.t('intravox', 'Your edit lock has expired. Please save your work.'));
            this.stopLockHeartbeat();
          }
          // On network error, keep trying — lock expires after 15 min
        }
      }, 60 * 1000); // Every 60 seconds
    },
    /**
     * Stop the lock heartbeat interval
     */
    stopLockHeartbeat() {
      if (this.lockHeartbeatTimer) {
        clearInterval(this.lockHeartbeatTimer);
        this.lockHeartbeatTimer = null;
      }
    },
    /**
     * Release lock when the browser tab/window is being closed
     */
    handleBeforeUnload() {
      if (this.isEditMode && this.currentPage?.uniqueId) {
        const url = generateUrl(`/apps/intravox/api/pages/${this.currentPage.uniqueId}/lock`);
        fetch(window.location.origin + url, {
          method: 'DELETE',
          keepalive: true,
          headers: { requesttoken: OC.requestToken },
        });
      }
    },
    /**
     * Force-unlock a page (admin only)
     */
    async forceUnlock() {
      const pageId = this.currentPage?.uniqueId;
      const lockedBy = this.pageLock?.displayName || 'Someone';
      if (!pageId) return;

      if (!confirm(this.t('intravox', 'Are you sure you want to unlock this page? {displayName} may lose unsaved changes.', { displayName: lockedBy }))) {
        return;
      }

      try {
        const url = generateUrl(`/apps/intravox/api/pages/${pageId}/lock/force-release`);
        await axios.post(url);
        this.pageLock = null;
        showSuccess(this.t('intravox', 'Page unlocked'));
      } catch (err) {
        showError(this.t('intravox', 'Could not unlock page: {error}', { error: err.message }));
      }
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
        // currentPage carries `baseVersion` from the last load — the server
        // refuses a save that started from an older version of the file rather
        // than silently overwriting whoever saved in the meantime.
        const response = await axios.put(url, this.currentPage);

        // Adopt the version the save produced. Without this the token stays at
        // the value from page load, so the SECOND save in a session would look
        // stale against the file this very editor just wrote, and would be
        // rejected — a conflict with yourself.
        const newBaseVersion = response?.data?.baseVersion;
        if (typeof newBaseVersion === 'number') {
          this.currentPage.baseVersion = newBaseVersion;
        }

        // Invalidate cache for this page and pages list
        CacheService.delete(`page-${this.currentPage.uniqueId}`);
        CacheService.delete('pages-list');

        showSuccess(this.t('intravox', 'Page saved'));

        // Dispatch event to notify sidebar that a new version was created
        window.dispatchEvent(new CustomEvent('intravox:page:saved', {
          detail: { pageId: this.currentPage.uniqueId }
        }));
      } catch (err) {
        console.error('[savePage] Error:', err);
        const errorMsg = err.response?.data?.error || err.message || 'Unknown error';
        showError(this.t('intravox', 'Could not save page: {error}', { error: errorMsg }));
        throw err;
      }
    },
    async updatePage(updatedPage) {
      // Use structuredClone to ensure Vue reactivity captures all nested changes
      this.currentPage = structuredClone(updatedPage);
    },
    createNewPage() {
      this.showNewPageModal = true;
    },
    generateSlug(title) {
      // Shared with RenamePageModal's folder preview (utils/slug.js).
      return generateSlug(title);
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
        showError(this.t('intravox', 'Invalid page title'));
        return;
      }

      // Generate unique ID for internal references
      const uniqueId = this.generateUniqueId();

      try {
        const newPage = {
          id: slug, // Use slug as the page ID (folder name)
          uniqueId: uniqueId, // Store unique ID for internal references
          title: title,
          status: 'draft', // New pages start as draft
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

        const response = await axios.post(generateUrl('/apps/intravox/api/pages'), newPage);
        showSuccess(this.t('intravox', 'Page created'));

        // Select the created page by its stable uniqueId, NOT the derived slug:
        // on a slug collision the backend stores the page under "<slug>-2" while
        // keeping our uniqueId, so selecting by slug would open the pre-existing
        // page and its save would 404 ("Page not found"). Prefer the uniqueId
        // returned by the API, then the one we sent, then fall back to the slug.
        const createdUniqueId = response.data?.uniqueId || response.data?.page?.uniqueId || uniqueId;
        const target = createdUniqueId || slug;

        // Reload pages first so the new page is in the array
        await this.loadPages();

        // Add to navigation if requested (after pages are loaded)
        if (addToNavigation) {
          await this.addPageToNavigation(target, title);
        }

        await this.selectPage(target);
        // Open the new page in edit mode (with lock)
        await this.startEditMode();
      } catch (err) {
        showError(this.t('intravox', 'Could not create page: {error}', { error: err.message }));
      }
    },
    async handleCreatePageFromTemplate(data) {
      const { templateId, title } = data;

      if (!templateId || !title) {
        showError(this.t('intravox', 'Missing template or title'));
        return;
      }

      try {
        const url = generateUrl('/apps/intravox/api/pages/from-template');
        const response = await axios.post(url, {
          templateId,
          pageTitle: title,
          parentPath: this.currentPage?.path || null
        });

        if (response.data.success) {
          showSuccess(this.t('intravox', 'Page created from template'));

          const newPage = response.data.page;

          if (newPage?.uniqueId) {
            // The backend already returned the freshly created page through
            // PageService::getPage() (which runs enrichWithPathData + the
            // sanitize pipeline) so we have a fully populated payload right
            // here. Trying to re-fetch via selectPage() can race with
            // pageDataCache / parent folder listings that haven't yet
            // refreshed and intermittently 404 — sending the user back to
            // the home page. Use the response directly and warm both the
            // local pages array and the frontend CacheService so any
            // subsequent navigation hits an immediate cache.

            // 1. Append to local pages list (no loadPages refresh — that
            //    re-runs its tail-end selectPage(home) which would steal
            //    focus from the new page).
            if (!this.pages.find(p => p.uniqueId === newPage.uniqueId)) {
              this.pages.push({
                uniqueId: newPage.uniqueId,
                title: newPage.title,
                path: newPage.path || null,
                status: newPage.status || 'draft',
                modified: newPage.modified || Math.floor(Date.now() / 1000),
                permissions: newPage.permissions || { canRead: true, canWrite: true },
              });
              CacheService.delete('pages-list');
            }

            // 2. Warm the per-page cache with the backend response so
            //    selectPage() finds it without an API roundtrip.
            CacheService.set(`page-${newPage.uniqueId}`, newPage);

            // 3. Update URL hash before navigating so any concurrent
            //    page-list refresh doesn't redirect us home.
            window.location.hash = `#${newPage.uniqueId}`;

            // 4. Mount the editor.
            this.currentPage = newPage;
            this.breadcrumb = newPage.breadcrumb || [];
            this.isEditMode = false;

            await this.$nextTick();
            await this.startEditMode();
          }
        } else {
          showError(this.t('intravox', 'Could not create page: {error}', { error: response.data.error || 'Unknown error' }));
        }
      } catch (err) {
        console.error('[handleCreatePageFromTemplate] Error:', err);
        showError(this.t('intravox', 'Could not create page from template: {error}', { error: err.message }));
      }
    },
    handleTemplateSaved(template) {
      showSuccess(this.t('intravox', 'Template saved: {name}', { name: template.title }));
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
          showError(this.t('intravox', 'Navigation structure is invalid. Please reload the page.'));
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

        showSuccess(this.t('intravox', 'Added to navigation'));
      } catch (err) {
        showError(this.t('intravox', 'Failed to add page to navigation'));
      }
    },
    async deletePage(pageId) {
      if (!confirm(this.t('intravox', 'Are you sure you want to delete this page?'))) {
        return;
      }

      try {
        await axios.delete(generateUrl(`/apps/intravox/api/pages/${pageId}`));
        showSuccess(this.t('intravox', 'Page deleted'));
        await this.loadPages();

        if (this.currentPage?.uniqueId === pageId) {
          this.currentPage = null;
          if (this.pages.length > 0) {
            await this.selectPage(this.pages[0].uniqueId);
          }
        } else if ((this.currentPage?.translations || []).some(t => t.uniqueId === pageId)) {
          // The open page stays; drop the deleted page from its translations
          // list (the server rows are already gone, only this copy is stale).
          this.currentPage = {
            ...this.currentPage,
            translations: this.currentPage.translations.filter(t => t.uniqueId !== pageId),
          };
        }
      } catch (err) {
        const code = err.response?.data?.error;
        if (code === 'HOMEPAGE_PROTECTED') {
          showError(this.t('intravox', 'This page is the homepage. Set another page as the homepage first.'));
        } else {
          showError(this.t('intravox', 'Could not delete page: {error}', { error: err.message }));
        }
      }
    },
    // ---- Three-dot menu page actions (current page) ----
    deleteCurrentPage() {
      if (this.currentPage?.uniqueId) {
        this.deletePage(this.currentPage.uniqueId);
      }
    },
    async copyCurrentPage() {
      if (!this.currentPage?.uniqueId) return;
      await this.copyPage(this.currentPage.uniqueId, null);
    },
    async copyPageFromTree(payload) {
      // payload = { item, parentId }. Copy the page as a SIBLING (into its own
      // parent, parentId=null means the language root) instead of always root —
      // the tree only shows Copy where the user can create in that parent, so
      // this matches the backend's create-permission check and avoids a 403 for
      // section editors who lack create at root (issue #86 follow-up).
      const item = payload && payload.item ? payload.item : payload;
      const parentId = payload && 'parentId' in payload ? payload.parentId : null;
      const sourceId = item && item.uniqueId ? item.uniqueId : item;
      if (!sourceId) return;
      await this.copyPage(sourceId, parentId);
      await this.refreshPageTrees();
    },
    renameCurrentPage() {
      if (!this.currentPage?.uniqueId) return;
      this.renameTarget = {
        pageId: this.currentPage.uniqueId,
        title: this.currentPage.title || '',
      };
    },
    renamePageFromTree(node) {
      const pageId = node && node.uniqueId ? node.uniqueId : node;
      if (!pageId) return;
      this.renameTarget = {
        pageId,
        title: (node && node.title) || '',
      };
    },
    async onPageRenamed(newTitle) {
      // Reflect the new title immediately in the header/breadcrumb when the
      // renamed page is the one on screen, then refresh pages + navigation so
      // every reference (menu, breadcrumb, list) picks up the change.
      if (this.currentPage && this.renameTarget
          && this.currentPage.uniqueId === this.renameTarget.pageId) {
        this.currentPage.title = newTitle;
      }
      this.renameTarget = null;
      await Promise.all([this.loadPages(), this.loadNavigation()]);
      await this.refreshPageTrees();
    },
    async copyPage(sourceId, targetParentId) {
      try {
        const response = await axios.post(generateUrl('/apps/intravox/api/pages/copy'), {
          sourceId,
          targetParentId: targetParentId || null,
        });
        showSuccess(this.t('intravox', 'Page copied'));
        await this.loadPages();
        const copy = response.data?.page;
        if (copy?.uniqueId) {
          await this.selectPage(copy.uniqueId);
        }
      } catch (err) {
        showError(this.t('intravox', 'Could not copy page: {error}', { error: err.message }));
      }
    },
    async handleSetHomepage(node) {
      const uniqueId = node && node.uniqueId ? node.uniqueId : node;
      if (!uniqueId) return;
      try {
        await axios.post(generateUrl('/apps/intravox/api/homepage'), { pageUniqueId: uniqueId });
        this.homepageUniqueId = uniqueId;
        showSuccess(this.t('intravox', 'Homepage updated'));
        await this.loadPages();
        await this.refreshPageTrees();
      } catch (err) {
        showError(this.t('intravox', 'Could not set homepage: {error}', { error: err.message }));
      }
    },
    // ---- Page management from the structure modal (issue #69) ----
    async reorderPages({ parentId, orderedIds }) {
      // The tree modal already swapped the rows optimistically, so we persist
      // in the background without a full reload (no flash). Only refresh the
      // tree if the server rejects the change, to resync the real order.
      try {
        await axios.post(generateUrl('/apps/intravox/api/pages/reorder'), {
          parentId: parentId || null,
          orderedIds,
        });
        // Keep the pages list in sync for other views, but don't reload the tree.
        this.loadPages();
      } catch (err) {
        showError(this.t('intravox', 'Could not save page order: {error}', { error: err.message }));
        await this.refreshPageTrees();
      }
    },
    async deletePageFromTree(node) {
      const pageId = node && node.uniqueId ? node.uniqueId : node;
      await this.deletePage(pageId);
      await this.refreshPageTrees();
    },
    // The page-structure modal drives the inline move UI and calls this with the
    // resolved payload + a done(ok) callback so it can refresh itself.
    async movePage(payload, done) {
      const { pageId, targetParentId } = payload || {};
      if (!pageId) { if (done) done(false); return; }
      if (targetParentId === pageId) {
        showError(this.t('intravox', 'Cannot move a page into itself or its descendant'));
        if (done) done(false);
        return;
      }
      try {
        await axios.post(generateUrl('/apps/intravox/api/pages/move'), {
          pageId,
          targetParentId: targetParentId || '',
        });
        showSuccess(this.t('intravox', 'Page moved'));
        await this.loadPages();
        if (done) done(true);
      } catch (err) {
        const code = err.response?.data?.error;
        if (code === 'HOMEPAGE_PROTECTED') {
          showError(this.t('intravox', 'This page is the homepage. Set another page as the homepage first.'));
        } else {
          showError(this.t('intravox', 'Could not move page: {error}', { error: code || err.message }));
        }
        if (done) done(false);
      }
    },
    async loadNavigation() {
      try {
        const url = generateUrl('/apps/intravox/api/navigation');
        const response = await axios.get(url);
        this.navigation = response.data.navigation;
        // The menu hides an item without a link and without children; the editor
        // must still show it, otherwise a just-created item disappears on the
        // next load and its link can never be set (issue #104). The server only
        // sends this to users who may edit; fall back to the menu copy so an
        // older server keeps working.
        this.navigationForEditor = response.data.navigationForEditor || response.data.navigation;
        // Editing navigation writes navigation.json at the language root, so it
        // requires write on the root — gate strictly on canWrite. (The legacy
        // `canEdit` fallback is dropped: it mirrored canWrite anyway and a stale
        // truthy value could show "Edit navigation" to a read-only user, whose
        // save would then be refused — issue #86 follow-up.)
        const perms = response.data.permissions || {};
        this.canEditNavigation = perms.canWrite === true;
      } catch (err) {
        // Provide default empty navigation
        this.navigation = {
          type: 'dropdown',
          items: []
        };
        this.navigationForEditor = this.navigation;
        this.canEditNavigation = false;
      }
    },
    async saveNavigation(navigation) {
      try {
        await axios.post(generateUrl('/apps/intravox/api/navigation'), navigation);
        // The POST returns the stored structure unfiltered, so assigning it to
        // this.navigation put linkless items straight into the visitor menu --
        // and made the item seem saved until the next load contradicted it
        // (issue #104). Re-read instead: one round trip, and menu and editor
        // each get the copy they should have.
        await this.loadNavigation();
        this.showNavigationEditor = false;
        showSuccess(this.t('intravox', 'Navigation saved'));
      } catch (err) {
        showError(this.t('intravox', 'Could not save navigation: {error}', { error: err.message }));
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
        showSuccess(this.t('intravox', 'Footer saved'));
      } catch (err) {
        showError(this.t('intravox', 'Could not save footer: {error}', { error: err.message }));
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
      // Only reload navigation - pages structure doesn't change with language
      this.loadNavigation();
      // The fallback notice depends on the user's language, so refresh it too.
      this.loadContentStatus();
      // Force Vue to re-render all translated strings
      this.$forceUpdate();
    },
    async handleVersionRestored(restoredPageData) {
      showSuccess(this.t('intravox', 'Version restored'));

      // Reload pages list to update timestamps, but stay on current page
      const currentPageId = restoredPageData.uniqueId || this.currentPage?.uniqueId;

      try {
        // Invalidate cache for this page
        CacheService.delete(`page-${currentPageId}`);
        CacheService.delete('pages-list');

        // Reload pages list
        const response = await axios.get(generateUrl('/apps/intravox/api/pages'));
        this.pages = response.data;

        // Re-fetch the page to get the fully restored content
        await this.selectPage(currentPageId, false);

        // Ensure version preview is cleared - prevents race condition
        // where sidebar's version-selected event could re-set versionPage
        this.clearVersionPreview();

        // Open sidebar with Versions tab
        this.sidebarInitialTab = 'versions-tab';
        this.setDetailsSidebarOpen(true);
      } catch (err) {
        console.error('IntraVox: Error reloading pages after restore:', err);
      }
    },
    async handleHashChange() {
      // Handle URL hash changes for navigation
      const hash = window.location.hash;

      // Sectie-anker: `#<pageId>#h-<slug>` benoemt de pagina erbij, zodat een
      // gedeelde sectielink niet op de homepage uitkomt. Staat die pagina nog
      // niet open, dan eerst navigeren; het scrollen doet PageViewer na render.
      if (isSectionAnchor(hash)) {
        const { pageId } = parseFragment(hash);
        if (pageId && this.currentPage?.uniqueId !== pageId
            && this.pages.some(p => p.uniqueId === pageId)) {
          await this.selectPage(pageId, false);
          return;
        }
        scrollToHashAnchor();
        return;
      }

      if (!hash || hash === '#') {
        // No hash, load home page
        const homePage = this.resolveHomePage();
        if (homePage) {
          this.selectPage(homePage.uniqueId, false);
        }
        return;
      }

      const pageIdentifier = hash.substring(1); // Remove '#'

      // Find page by uniqueId
      const targetPage = this.pages.find(p => p.uniqueId === pageIdentifier);

      if (targetPage) {
        // Don't update URL since we're already responding to a hash change
        this.selectPage(targetPage.uniqueId, false);
        return;
      }

      // Not in this language's list — ask the server before giving up, the
      // same as on first load. Without this, pasting a link to a page in
      // another language back into the address bar bounced to the homepage
      // and rewrote the URL, so the link could not be opened by any means.
      const resolved = await this.resolvePageById(pageIdentifier);
      if (resolved) {
        this.selectPage(resolved.uniqueId, false);
        return;
      }

      // Genuinely unknown: fall back to home, and say so rather than
      // silently swapping the page out from under the reader.
      showError(this.t('intravox', 'That page could not be found. Showing the home page instead.'));
      const homePage = this.resolveHomePage();
      if (homePage) {
        this.selectPage(homePage.uniqueId, true);
      }
    },
    async handleVersionSelected(data) {
      const { version, pageId } = data;

      // If version is null, clear the preview (show current page)
      if (!version) {
        this.clearVersionPreview();
        return;
      }

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
        showError(this.t('intravox', 'Could not load version: {error}', { error: err.message }));
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
      this.setDetailsSidebarOpen(false);
      // Reset to default tab when closing
      this.sidebarInitialTab = 'details-tab';
      // Clear version preview when closing sidebar
      this.clearVersionPreview();
    },
    /**
     * Re-read just the publication state after MetaVox saved a publish or
     * expiration date. Only these two fields are copied over, so a page being
     * edited keeps its unsaved changes — we just want the Draft/Scheduled/
     * Expired badge and the edit-mode chip to reflect the new date at once.
     */
    async refreshPublicationState() {
      const pageId = this.currentPage?.uniqueId;
      if (!pageId) return;
      try {
        const response = await axios.get(generateUrl(`/apps/intravox/api/pages/${pageId}`));
        const fresh = response.data;
        if (!fresh || this.currentPage?.uniqueId !== pageId) return;
        this.currentPage.effectivePublishState = fresh.effectivePublishState;
        this.currentPage.publicationDateActive = fresh.publicationDateActive;
      } catch (err) {
        // Non-critical: the badge simply keeps its previous value until reload.
        console.error('IntraVox: could not refresh publication state:', err);
      }
    },
    async loadEngagementSettings() {
      try {
        const cached = CacheService.get('engagement-settings');
        if (cached) {
          this.globalEngagementSettings = cached;
          return;
        }
        const response = await axios.get(generateUrl('/apps/intravox/api/settings/engagement'));
        this.globalEngagementSettings = response.data;
        CacheService.set('engagement-settings', response.data);
      } catch (err) {
        // Silent fail - use defaults
      }
    },
    /**
     * Load the language content status that drives the fallback notice:
     * does the user's language have real content, and which languages do?
     */
    async loadContentStatus() {
      try {
        const response = await axios.get(generateUrl('/apps/intravox/api/languages/content-status'));
        this.languageContentStatus = response.data;
        // Configurable homepage: prefer the server-resolved pointer for the
        // initial page choice (falls back to the slug/path heuristic below).
        if (response.data && response.data.homepageUniqueId) {
          this.homepageUniqueId = response.data.homepageUniqueId;
        }
      } catch (err) {
        // Silent fail - notice simply won't show
        this.languageContentStatus = null;
      }
    },
    // Resolve the homepage to land on: prefer the configured pointer, then the
    // legacy slug/path heuristic (configurable homepage).
    resolveHomePage() {
      if (this.homepageUniqueId) {
        const byPointer = this.pages.find(p => p.uniqueId === this.homepageUniqueId);
        if (byPointer) return byPointer;
      }
      return findHomePage(this.pages);
    },
    async handlePageSettingsSave(settings) {
      if (!this.currentPage) return;

      // Initialize settings object if it doesn't exist
      if (!this.currentPage.settings) {
        this.currentPage.settings = {};
      }

      // Update page settings
      this.currentPage.settings.allowReactions = settings.allowReactions;
      this.currentPage.settings.allowComments = settings.allowComments;
      this.currentPage.settings.allowCommentReactions = settings.allowCommentReactions;

      try {
        await this.savePage();
        this.showPageSettingsModal = false;
        showSuccess(this.t('intravox', 'Page settings saved'));
      } catch (err) {
        showError(this.t('intravox', 'Could not save page settings: {error}', { error: err.message }));
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
  overflow-x: clip;
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

/* Sticky topbar wrapping header + nav. Keeps page navigation in reach
   on long pages (e.g. Photo Story timelines with hundreds of photos). */
.intravox-topbar {
  position: sticky;
  top: 0;
  z-index: 100;
  background: var(--color-main-background);
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
  overflow: visible;
}

.header-right {
  flex: 0 0 auto;
  display: flex;
  gap: 10px;
  align-items: center;
}

.draft-badge {
  display: inline-block;
  padding: 4px 12px;
  border-radius: 12px;
  font-size: 12px;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  cursor: default;
}

/* Draft — held back manually (warning/amber). */
.draft-badge.draft {
  background: var(--color-warning-light, #fff3cd);
  color: var(--color-warning-text, #6d5003);
  border: 1px solid var(--color-warning, #ffc107);
}

/* Scheduled — future publish date (info/blue, uses NC primary tokens). */
.draft-badge.scheduled {
  background: var(--color-primary-element-light, #e3effb);
  color: var(--color-primary-element-light-text, var(--color-main-text));
  border: 1px solid var(--color-primary-element, #1a67a3);
}

/* Expired — past expiration date (muted/neutral). */
.draft-badge.expired {
  background: var(--color-background-dark, #ededed);
  color: var(--color-text-maxcontrast, #6b7280);
  border: 1px solid var(--color-border-dark, #c9c9c9);
}

/* Content language — only rendered when the page is NOT in the viewer's own
   language, so it always signals an exception and can carry the same amber as
   Draft. Not uppercased: it holds a language NAME ("Nederlands"), not a status
   word. */
.language-badge {
  background: var(--color-warning-light, #fff3cd);
  color: var(--color-warning-text, #6d5003);
  border: 1px solid var(--color-warning, #ffc107);
  text-transform: none;
  letter-spacing: 0;
}

/* Reader notice: this page is not in your language. Informational rather than
   a warning — the page is fine, it just is not the language you expected — so
   it uses the neutral background tokens, not the amber of the Draft badge. */
.content-language-notice {
  display: flex;
  align-items: center;
  flex-wrap: wrap;
  gap: 8px;
  margin: 0 0 12px;
  padding: 10px 14px;
  border: 1px solid var(--color-border, #dbdbdb);
  border-radius: var(--border-radius-large, 12px);
  background: var(--color-background-hover, #f5f5f5);
  color: var(--color-main-text, #222);
  font-size: 0.95em;
}

.content-language-notice__text {
  flex: 1 1 auto;
}

.content-language-notice__switch {
  flex: 0 0 auto;
  padding: 4px 12px;
  border: 1px solid var(--color-primary-element, #0082c9);
  border-radius: var(--border-radius, 8px);
  background: transparent;
  color: var(--color-primary-element, #0082c9);
  cursor: pointer;
  font-size: inherit;
}

.content-language-notice__switch:hover,
.content-language-notice__switch:focus-visible {
  background: var(--color-primary-element, #0082c9);
  color: var(--color-primary-element-text, #fff);
}

/* Read-only publication-state chip shown in edit mode when a Publish-on date
   governs (the manual toggle is overridden). Mirrors the view-mode badge
   colours, with a help cursor to surface the explanatory tooltip. */
.publish-state-chip {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 6px 12px;
  border-radius: var(--border-radius-pill, 16px);
  font-size: 13px;
  font-weight: 600;
  cursor: help;
}

.publish-state-chip.scheduled {
  background: var(--color-primary-element-light, #e3effb);
  color: var(--color-primary-element-light-text, var(--color-main-text));
  border: 1px solid var(--color-primary-element, #1a67a3);
}

.publish-state-chip.published {
  background: var(--color-success-light, #e8f5e9);
  color: var(--color-success-text, #2d6a2f);
  border: 1px solid var(--color-success, #46ba61);
}

.publish-state-chip.expired {
  background: var(--color-background-dark, #ededed);
  color: var(--color-text-maxcontrast, #6b7280);
  border: 1px solid var(--color-border-dark, #c9c9c9);
}

.page-lock-indicator {
  color: var(--color-warning-text);
  font-size: 13px;
  white-space: nowrap;
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
  /* Naast het structuur-paneel mag brede content (tabellen) het paneel niet
     wegdrukken */
  min-width: 0;
  overflow-y: auto;
}

/* Details-sidebar meelaten plakken onder de sticky topbar, zoals het
   structuur-paneel links. NcAppSidebar zet zelf position:relative + height:100%
   (bedoeld voor Nextclouds #content, dat niet meescrollt). Die eigen regel wint
   qua specificiteit, dus de sticky context moet van een wrapper eromheen komen
   in plaats van van een klasse op het component zelf. */
.details-sidebar-sticky {
  position: sticky;
  top: var(--intravox-topbar-height, 0px);
  align-self: flex-start;
  height: calc(100vh - var(--header-height, 50px) - var(--intravox-topbar-height, 0px));
  flex-shrink: 0;
}

/* De sidebar vult de sticky doos; lange tabinhoud (bv. Versions) scrollt
   binnenin in plaats van afgekapt te worden. */
.details-sidebar-sticky :deep(.app-sidebar) {
  height: 100%;
  max-height: 100%;
  overflow-y: auto;
}

@media (max-width: 1024px) {
  .details-sidebar-sticky {
    position: fixed;
    top: calc(var(--header-height, 50px) + var(--intravox-topbar-height, 0px));
    inset-inline-end: 0;
    bottom: 0;
    max-height: none;
    z-index: 2000;
  }
}

/* Breadcrumb row with structure toggle (left) and Details button (right) */
.breadcrumb-row {
  display: flex;
  align-items: center;
  justify-content: flex-start;
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

/* De i-knop blijft rechts uitgelijnd; structuur-knop en breadcrumb staan links */
.breadcrumb-row .details-btn:not(.structure-btn) {
  margin-left: auto;
}

.structure-btn-active {
  background-color: var(--color-primary-element-light);
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
    overflow-x: clip;
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
.move-page-dialog {
  padding: 8px 4px 4px;
}

.move-page-dialog__label {
  margin: 0 0 4px;
  color: var(--color-text-maxcontrast);
}

.move-page-dialog__title {
  margin: 0 0 12px;
  font-weight: 600;
}

</style>
