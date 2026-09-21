<template>
  <div class="feed-widget-editor">
    <!-- Widget title. First field, matching the news, people and calendar
         editors: it names the block on the page, so it comes before the
         question of where the content comes from. -->
    <div class="form-group">
      <NcTextField
        id="feed-widget-title"
        :value.sync="localWidget.title"
        :label="t('intravox', 'Widget title (optional)')"
        :placeholder="t('intravox', 'e.g. Latest news')"
        @update:value="debouncedEmitUpdate"
      />
      <span class="field-hint">{{ t('intravox', 'Left empty, the name from the feed is suggested once. Your own wording always wins.') }}</span>
    </div>

    <!-- Source type selection -->
    <div class="form-group">
      <label for="feed-source-type">{{ t('intravox', 'Source type') }}</label>
      <NcSelect
        input-id="feed-source-type"
        v-model="sourceTypeOption"
        :options="sourceTypeOptions"
        :clearable="false"
        label="label"
      />
    </div>

    <!-- RSS URL input -->
    <div v-if="localWidget.sourceType === 'rss'" class="form-group">
      <NcTextField
        id="feed-url"
        :value.sync="localWidget.feedUrl"
        :label="t('intravox', 'Feed URL')"
        type="url"
        placeholder="https://example.com/feed.xml"
        @update:value="debouncedEmitUpdate"
      />
    </div>

    <!-- Connection selection -->
    <div v-if="localWidget.sourceType === 'connection'" class="form-group">
      <label for="feed-connection">{{ t('intravox', 'Connection') }}</label>
      <select id="feed-connection" v-model="localWidget.connectionId" @change="onConnectionChange">
        <option value="">{{ t('intravox', 'Select a connection …') }}</option>
        <option
          v-for="conn in availableConnections"
          :key="conn.id"
          :value="conn.id"
        >
          {{ conn.name }}
        </option>
        <option
          v-if="inactiveSelectedConnection"
          :value="inactiveSelectedConnection.id"
          disabled
        >
          {{ inactiveSelectedConnection.name }} ({{ t('intravox', 'inactive') }})
        </option>
      </select>
      <p v-if="inactiveSelectedConnection" class="field-hint field-hint--warning">
        {{ t('intravox', 'This connection is currently disabled by an administrator.') }}
      </p>
      <p v-else-if="availableConnections.length === 0" class="field-hint">
        {{ t('intravox', 'No connections configured. Ask an administrator to add one in IntraVox settings.') }}
      </p>
    </div>

    <!-- User LMS connection status -->
    <div v-if="isLmsType && localWidget.connectionId && selectedConnectionAuthMode !== 'token'" class="form-group">
      <div v-if="userConnectionStatus === 'connected'" class="lms-status lms-status-connected">
        <span class="status-badge connected">{{ userConnectionTokenType === 'oidc' ? t('intravox', 'Connected via SSO') : t('intravox', 'Connected') }}</span>
        <button class="disconnect-button" @click="disconnectLms">{{ t('intravox', 'Disconnect') }}</button>
      </div>
      <div v-else-if="userConnectionStatus === 'loading'" class="lms-status">
        <span class="status-badge loading">{{ t('intravox', 'Checking …') }}</span>
      </div>
      <div v-else class="lms-status lms-status-disconnected">
        <span class="status-badge disconnected">{{ t('intravox', 'Not connected') }}</span>
        <div class="connect-actions">
          <button v-if="selectedConnectionAuthMode !== 'token'" class="connect-button" @click="startOAuth" :disabled="oauthLoading">
            {{ oauthLoading ? t('intravox', 'Connecting …') : t('intravox', 'Connect your account') }}
          </button>
          <button v-if="selectedConnectionType === 'moodle' || selectedConnectionType === 'brightspace'" class="connect-button secondary" @click="showManualToken = !showManualToken">
            {{ t('intravox', 'Enter token manually') }}
          </button>
        </div>
        <div v-if="showManualToken" class="manual-token-input">
          <input
            v-model="manualToken"
            type="password"
            :placeholder="t('intravox', 'Paste your API token here')"
          />
          <button @click="saveManualToken" :disabled="!manualToken || manualTokenSaving">
            {{ manualTokenSaving ? t('intravox', 'Saving …') : t('intravox', 'Save token') }}
          </button>
        </div>
        <p v-if="connectError" class="field-error">{{ connectError }}</p>
      </div>
    </div>

    <!-- Brightspace content type -->
    <div v-if="isLmsType && localWidget.connectionId" class="form-group">
      <label for="feed-content-type">{{ t('intravox', 'Content type') }}</label>
      <select id="feed-content-type" v-model="localWidget.contentType" @change="onLmsContentTypeChange">
        <option value="news">{{ t('intravox', 'News/Announcements') }}</option>
        <option value="courses">{{ t('intravox', 'Available courses') }}</option>
        <option value="assignments">{{ t('intravox', 'Assignments') }}</option>
        <option value="deadlines">{{ t('intravox', 'Upcoming deadlines') }}</option>
      </select>
    </div>

    <!-- Moodle forum selector (when News + course selected) -->
    <div v-if="isMoodleType && localWidget.connectionId && localWidget.contentType === 'news' && localWidget.courseId" class="form-group">
      <label for="feed-moodle-forum">{{ t('intravox', 'Forum (optional)') }}</label>
      <div v-if="moodleForumsLoading" class="field-hint">{{ t('intravox', 'Loading …') }}</div>
      <template v-else>
        <select id="feed-moodle-forum" v-model="localWidget.moodleForumId" @change="emitUpdate">
          <option value="">{{ t('intravox', 'All forums') }}</option>
          <option v-for="forum in moodleForums" :key="forum.id" :value="forum.id">{{ forum.name }}</option>
        </select>
      </template>
    </div>

    <!-- OpenProject content type -->
    <div v-if="isOpenProjectType && localWidget.connectionId" class="form-group">
      <label for="feed-content-type-op">{{ t('intravox', 'Content type') }}</label>
      <select id="feed-content-type-op" v-model="localWidget.contentType" @change="emitUpdate">
        <option value="">{{ t('intravox', 'All work packages') }}</option>
        <option value="open">{{ t('intravox', 'Open work packages') }}</option>
        <option value="overdue">{{ t('intravox', 'Overdue') }}</option>
        <option value="milestones">{{ t('intravox', 'Milestones') }}</option>
        <option value="recently-updated">{{ t('intravox', 'Recently updated') }}</option>
      </select>
    </div>

    <!-- Jira project selector -->
    <div v-if="isJiraType && localWidget.connectionId" class="form-group">
      <label for="feed-jira-project">{{ t('intravox', 'Project') }}</label>
      <div v-if="jiraProjectsLoading" class="field-hint">{{ t('intravox', 'Loading …') }}</div>
      <template v-else>
        <select id="feed-jira-project" v-model="localWidget.jiraProject" @change="emitUpdate">
          <option value="">{{ t('intravox', 'All projects') }}</option>
          <option v-for="proj in jiraProjects" :key="proj.key" :value="proj.key">{{ proj.name }} ({{ proj.key }})</option>
        </select>
      </template>
    </div>
    <div v-if="isJiraType && localWidget.connectionId" class="form-group">
      <label for="feed-content-type-jira">{{ t('intravox', 'Content type') }}</label>
      <select id="feed-content-type-jira" v-model="localWidget.contentType" @change="emitUpdate">
        <option value="">{{ t('intravox', 'All issues') }}</option>
        <option value="open">{{ t('intravox', 'Open issues') }}</option>
        <option value="recent">{{ t('intravox', 'Recently updated (7 days)') }}</option>
        <option value="created-recent">{{ t('intravox', 'Recently created (7 days)') }}</option>
        <option value="bugs">{{ t('intravox', 'Bugs') }}</option>
      </select>
    </div>

    <!-- SharePoint content type -->
    <div v-if="isSharePointType && localWidget.connectionId" class="form-group">
      <label for="feed-content-type-sp">{{ t('intravox', 'Content type') }}</label>
      <select id="feed-content-type-sp" v-model="localWidget.contentType" @change="onSharePointContentTypeChange">
        <option value="pages">{{ t('intravox', 'All pages') }}</option>
        <option value="news">{{ t('intravox', 'News posts') }}</option>
        <option value="documents">{{ t('intravox', 'Documents') }}</option>
        <option value="list">{{ t('intravox', 'List items') }}</option>
      </select>
    </div>

    <!-- SharePoint list/library selector -->
    <div v-if="isSharePointType && localWidget.connectionId && (localWidget.contentType === 'documents' || localWidget.contentType === 'list')" class="form-group">
      <label for="feed-sp-list">{{ localWidget.contentType === 'documents' ? t('intravox', 'Document library') : t('intravox', 'List') }}</label>
      <div v-if="spListsLoading" class="field-hint">{{ t('intravox', 'Loading …') }}</div>
      <template v-else>
        <select id="feed-sp-list" v-model="localWidget.listId" @change="emitUpdate">
          <option value="">{{ t('intravox', 'Select …') }}</option>
          <option v-for="item in spListsForType" :key="item.id" :value="item.id">{{ item.name }}</option>
        </select>
        <span v-if="spListsError" class="field-hint" style="color: var(--color-error)">{{ spListsError }}</span>
      </template>
    </div>

    <!-- Course selection (for LMS types) -->
    <div v-if="isLmsType && localWidget.connectionId && showCourseIdField" class="form-group">
      <label for="feed-course-id">{{ t('intravox', 'Course (optional)') }}</label>
      <div v-if="coursesLoading" class="field-hint">{{ t('intravox', 'Loading courses …') }}</div>
      <template v-else-if="courses.length > 0 && !manualCourseId">
        <select id="feed-course-id" v-model="localWidget.courseId" @change="onCourseChange">
          <option value="">{{ t('intravox', 'All courses') }}</option>
          <option v-for="course in courses" :key="course.id" :value="course.id">
            {{ course.name }}
          </option>
        </select>
        <button class="link-button" @click="manualCourseId = true">{{ t('intravox', 'Enter ID manually') }}</button>
      </template>
      <template v-else>
        <input
          id="feed-course-id"
          v-model="localWidget.courseId"
          type="text"
          :placeholder="courses.length > 0 ? t('intravox', 'Enter course ID') : t('intravox', 'Course ID (connect your account to see a list)')"
          @blur="emitUpdate"
        />
        <button v-if="courses.length > 0" class="link-button" @click="manualCourseId = false">{{ t('intravox', 'Select from list') }}</button>
      </template>
    </div>

    <hr class="editor-divider" />

    <!-- Layout options -->
    <div class="form-group">
      <label for="feed-layout">{{ t('intravox', 'Layout') }}</label>
      <select id="feed-layout" v-model="localWidget.layout" @change="emitUpdate">
        <option value="list">{{ t('intravox', 'List') }}</option>
        <option value="grid">{{ t('intravox', 'Grid') }}</option>
      </select>
    </div>

    <!-- Grid columns -->
    <div v-if="localWidget.layout === 'grid'" class="form-group">
      <label for="feed-columns">{{ t('intravox', 'Columns') }}</label>
      <select id="feed-columns" v-model.number="localWidget.columns" @change="emitUpdate">
        <option :value="2">2</option>
        <option :value="3">3</option>
        <option :value="4">4</option>
      </select>
    </div>

    <!-- Sort -->
    <div class="form-group">
      <label for="feed-sort-by">{{ t('intravox', 'Sort by') }}</label>
      <!--
        Select and direction on one row, matching the photo-story editor: the
        two belong to one decision, and a full-width dropdown above a stack of
        radios reads as two unrelated settings.

        A radio pair rather than a toggle button, because Nextcloud's guidance
        is that a dropdown or toggle "should not be used for a small number of
        mutually exclusive options". The old toggle also hid the alternative —
        it showed "Newest first" and you had to press it to find out what else
        there was.
      -->
      <div class="sort-row">
        <NcSelect
          input-id="feed-sort-by"
          v-model="sortByOption"
          :options="sortByOptions"
          :clearable="false"
          label="label"
          class="sort-by-select"
        />
        <div class="sort-order-choice" role="group" :aria-label="t('intravox', 'Sort order')">
          <NcCheckboxRadioSwitch
            :model-value="localWidget.sortOrder"
            value="desc"
            name="feed-sort-order"
            type="radio"
            @update:model-value="setSortOrder"
          >
            {{ sortOrderLabels.desc }}
          </NcCheckboxRadioSwitch>
          <NcCheckboxRadioSwitch
            :model-value="localWidget.sortOrder"
            value="asc"
            name="feed-sort-order"
            type="radio"
            @update:model-value="setSortOrder"
          >
            {{ sortOrderLabels.asc }}
          </NcCheckboxRadioSwitch>
        </div>
      </div>
    </div>

    <!-- Filter -->
    <div class="form-group">
      <label for="feed-filter">{{ t('intravox', 'Filter by keyword (optional)') }}</label>
      <input
        id="feed-filter"
        v-model="localWidget.filterKeyword"
        type="text"
        :placeholder="t('intravox', 'e.g. announcement, update')"
        @input="debouncedEmitUpdate"
      />
      <span class="field-hint">{{ t('intravox', 'Only show items containing this word in title, excerpt, or author.') }}</span>
    </div>

    <!-- Limit -->
    <div class="form-group">
      <label for="feed-limit">{{ t('intravox', 'Number of items') }}: {{ localWidget.limit }}</label>
      <input
        id="feed-limit"
        v-model.number="localWidget.limit"
        type="range"
        min="1"
        max="20"
        @input="debouncedEmitUpdate"
      />
    </div>

    <!--
      Items per page. Sits right under the total, because the two only make
      sense together: this one cannot exceed it, and 0 means "show them all".

      A second control rather than a checkbox, because the useful question is
      not "paginate yes/no" but "how tall may this widget be" — which is the
      number itself.
    -->
    <div class="form-group">
      <label for="feed-page-size">
        {{ t('intravox', 'Items per page') }}:
        {{ localWidget.pageSize > 0 ? localWidget.pageSize : t('intravox', 'all') }}
      </label>
      <input
        id="feed-page-size"
        v-model.number="localWidget.pageSize"
        type="range"
        min="0"
        :max="localWidget.limit"
        @input="debouncedEmitUpdate"
      />
      <span class="field-hint">
        {{ t('intravox', 'Show this many at a time, with arrows to page through the rest. Set to 0 to show every item at once.') }}
      </span>
    </div>

    <!-- Display options.
         Grouped outside-in: first the widget frame, then what each item shows,
         then what a click does. The flat list mixed those three, so "show
         source" (per item) sat next to "open links in new tab" (behaviour). -->
    <div class="form-group">
      <label>{{ t('intravox', 'Display options') }}</label>

      <div class="checkbox-group">
        <span class="checkbox-group-heading">{{ t('intravox', 'Widget') }}</span>
        <NcCheckboxRadioSwitch :model-value="localWidget.showTitle" @update:model-value="v => { localWidget.showTitle = v; emitUpdate(); }">
          {{ t('intravox', 'Show title') }}
        </NcCheckboxRadioSwitch>
      </div>

      <div class="checkbox-group">
        <span class="checkbox-group-heading">{{ t('intravox', 'Per item') }}</span>
        <NcCheckboxRadioSwitch :model-value="localWidget.showImage" @update:model-value="v => { localWidget.showImage = v; emitUpdate(); }">
          {{ t('intravox', 'Show image') }}
        </NcCheckboxRadioSwitch>
        <NcCheckboxRadioSwitch :model-value="localWidget.showDate" @update:model-value="v => { localWidget.showDate = v; emitUpdate(); }">
          {{ t('intravox', 'Show date') }}
        </NcCheckboxRadioSwitch>
        <NcCheckboxRadioSwitch :model-value="localWidget.showExcerpt" @update:model-value="v => { localWidget.showExcerpt = v; emitUpdate(); }">
          {{ t('intravox', 'Show excerpt') }}
        </NcCheckboxRadioSwitch>
        <NcCheckboxRadioSwitch :model-value="localWidget.showSource" @update:model-value="v => { localWidget.showSource = v; emitUpdate(); }">
          {{ t('intravox', 'Show source') }}
        </NcCheckboxRadioSwitch>
      </div>

      <div class="checkbox-group">
        <span class="checkbox-group-heading">{{ t('intravox', 'Links') }}</span>
        <NcCheckboxRadioSwitch :model-value="localWidget.openInNewTab" @update:model-value="v => { localWidget.openInNewTab = v; emitUpdate(); }">
          {{ t('intravox', 'Open links in new tab') }}
        </NcCheckboxRadioSwitch>
      </div>
    </div>

    <!-- Live preview -->
    <div v-if="hasValidSource" class="feed-preview-container">
      <div class="feed-preview-header">{{ t('intravox', 'Preview') }}</div>
      <div class="feed-preview-content">
        <FeedWidget :widget="localWidget" :key="previewKey" @feed-name="onFeedName" />
      </div>
    </div>
  </div>
</template>

<script>
import { NcTextField, NcSelect, NcCheckboxRadioSwitch } from '@nextcloud/vue';
import { defineAsyncComponent } from 'vue';
import axios from '@nextcloud/axios';
import { translate } from '@nextcloud/l10n';
import { generateUrl } from '@nextcloud/router';

export default {
  name: 'FeedWidgetEditor',
  components: {
    NcTextField,
    NcSelect,
    NcCheckboxRadioSwitch,
    // Async to match Widget.vue's strategy.
    FeedWidget: defineAsyncComponent(() => import('./FeedWidget.vue')),
  },
  props: {
    widget: {
      type: Object,
      required: true,
    },
  },
  emits: ['update'],
  data() {
    return {
      localWidget: this.createDefaultWidget(),
      connections: [],
      userConnections: [],
      courses: [],
      coursesLoading: false,
      spLists: { libraries: [], lists: [] },
      spListsLoading: false,
      spListsError: '',
      jiraProjects: [],
      jiraProjectsLoading: false,
      moodleForums: [],
      moodleForumsLoading: false,
      manualCourseId: false,
      oauthLoading: false,
      showManualToken: false,
      manualToken: '',
      manualTokenSaving: false,
      connectError: null,
      // Latches on the first feed name the preview reports, so the suggestion
      // is offered once per editing session and never fights the typist.
      titlePrefilled: false,
    };
  },
  computed: {
    hasValidSource() {
      if (this.localWidget.sourceType === 'rss') {
        return !!this.localWidget.feedUrl;
      }
      return !!this.localWidget.connectionId;
    },
    previewKey() {
      return [
        this.localWidget.sourceType,
        this.localWidget.connectionId,
        this.localWidget.feedUrl,
        this.localWidget.contentType,
        this.localWidget.courseId,
        this.localWidget.jiraProject,
        this.localWidget.listId,
        this.localWidget.moodleForumId,
      ].join('-');
    },
    /**
     * Labels for the two sort directions, which depend on what is being
     * sorted: for a date "newest/oldest" is meaningful where "descending" is
     * jargon, and for a title the reader wants to see A-Z.
     */
    sortOrderLabels() {
      if (this.localWidget.sortBy === 'title') {
        return { asc: 'A \u2192 Z', desc: 'Z \u2192 A' };
      }
      return {
        desc: this.t('intravox', 'Newest first'),
        asc: this.t('intravox', 'Oldest first'),
      };
    },
    sourceTypeOptions() {
      const opties = [{ id: 'rss', label: this.t('intravox', 'RSS/Atom feed') }];
      if (this.availableConnections.length > 0 || this.localWidget.sourceType === 'connection') {
        opties.push({ id: 'connection', label: this.t('intravox', 'Connection') });
      }
      return opties;
    },
    sourceTypeOption: {
      get() {
        return this.sourceTypeOptions.find(o => o.id === this.localWidget.sourceType) || this.sourceTypeOptions[0];
      },
      set(optie) {
        if (!optie) return;
        this.localWidget.sourceType = optie.id;
        this.onSourceTypeChange();
      },
    },
    sortByOptions() {
      return [
        { id: 'date', label: this.t('intravox', 'Date') },
        { id: 'title', label: this.t('intravox', 'Title') },
      ];
    },
    sortByOption: {
      get() {
        return this.sortByOptions.find(o => o.id === this.localWidget.sortBy) || this.sortByOptions[0];
      },
      set(optie) {
        if (!optie) return;
        this.localWidget.sortBy = optie.id;
        this.emitUpdate();
      },
    },
    sortOrderLabel() {
      if (this.localWidget.sortBy === 'title') {
        return this.localWidget.sortOrder === 'asc' ? 'A → Z' : 'Z → A';
      }
      return this.localWidget.sortOrder === 'desc' ? this.t('intravox', 'Newest first') : this.t('intravox', 'Oldest first');
    },
    isLmsType() {
      if (!this.selectedConnection) return false;
      const connType = this.selectedConnection.type;
      return ['moodle', 'canvas', 'brightspace'].includes(connType);
    },
    isMoodleType() {
      if (!this.selectedConnection) return false;
      return this.selectedConnection.type === 'moodle';
    },
    isJiraType() {
      if (!this.selectedConnection) return false;
      return this.selectedConnection.type === 'jira';
    },
    isOpenProjectType() {
      if (!this.selectedConnection) return false;
      return this.selectedConnection.type === 'openproject';
    },
    isSharePointType() {
      if (!this.selectedConnection) return false;
      return this.selectedConnection.type === 'sharepoint';
    },
    spListsForType() {
      if (this.localWidget.contentType === 'documents') {
        return this.spLists.libraries || [];
      }
      return this.spLists.lists || [];
    },
    availableConnections() {
      return this.connections.filter(c => c.active);
    },
    connectionsForType() {
      return this.availableConnections.filter(c => c.type === this.localWidget.sourceType);
    },
    selectedConnection() {
      return this.connections.find(c => c.id === this.localWidget.connectionId) || null;
    },
    inactiveSelectedConnection() {
      const conn = this.selectedConnection;
      if (!conn) return null;
      return conn.active === false ? conn : null;
    },
    selectedConnectionAuthMode() {
      return this.selectedConnection?.authMode || 'token';
    },
    selectedConnectionType() {
      return this.selectedConnection?.type || '';
    },
    userConnectionForSelected() {
      return this.userConnections.find(c => c.id === this.localWidget.connectionId) || null;
    },
    userConnectionStatus() {
      if (!this.localWidget.connectionId) return 'none';
      if (this.userConnectionForSelected === null) return 'disconnected';
      return this.userConnectionForSelected.connected ? 'connected' : 'disconnected';
    },
    userConnectionTokenType() {
      return this.userConnectionForSelected?.tokenType || null;
    },
    showCourseIdField() {
      if (this.localWidget.sourceType !== 'rss') {
        return !this.localWidget.contentType || this.localWidget.contentType === 'news';
      }
      return true;
    },
  },
  watch: {
    widget: {
      handler(newWidget) {
        this.localWidget = { ...this.createDefaultWidget(), ...newWidget };
        // Only a brand-new widget gets the title suggestion. A widget that was
        // saved before has had its chance: an empty title there is a decision,
        // not a gap to fill. `feedUrl`/`connectionId` being set is what makes a
        // widget "already configured" — a fresh one has neither.
        if (newWidget && (newWidget.feedUrl || newWidget.connectionId)) {
          this.titlePrefilled = true;
        }
        // Normalize legacy sourceType values — old widgets stored LMS type names instead of 'connection'
        if (this.localWidget.sourceType && this.localWidget.sourceType !== 'rss' && this.localWidget.sourceType !== 'connection') {
          this.localWidget.sourceType = 'connection';
        }
        // Reload type-specific data when widget changes (e.g. switching between widgets)
        if (this.connections.length > 0) {
          this.loadTypeSpecificData();
        }
      },
      deep: true,
      immediate: true,
    },
  },
  mounted() {
    this.loadConnections().then(() => {
      this.loadTypeSpecificData();
    });
    this.loadUserConnections();
    this.loadCourses();
    window.addEventListener('message', this.handleOAuthMessage);
  },
  beforeUnmount() {
    window.removeEventListener('message', this.handleOAuthMessage);
    clearTimeout(this._debounceTimer);
  },
  methods: {
    t(app, text, vars) {
      return translate(app, text, vars);
    },
    loadTypeSpecificData() {
      if (this.isSharePointType) {
        this.loadSharePointLists();
      }
      if (this.isJiraType) {
        this.loadJiraProjects();
      }
      if (this.isMoodleType) {
        this.loadMoodleForums();
      }
    },
    createDefaultWidget() {
      return {
        type: 'feed',
        title: '',
        sourceType: 'rss',
        feedUrl: '',
        connectionId: '',
        courseId: '',
        contentType: '',
        jiraProject: '',
        moodleForumId: '',
        listId: '',
        layout: 'list',
        columns: 3,
        limit: 5,
        // 0 = no paging, which is how every widget behaved before this option
        // existed. An editor opts in by raising it; nothing changes for a page
        // nobody touches.
        pageSize: 0,
        // Defaults to on, and an existing widget without the key reads as on
        // (`showTitle !== false`): until now the title was stored but never
        // rendered, so a widget that has one should start showing it.
        showTitle: true,
        showImage: true,
        showDate: true,
        showExcerpt: true,
        showSource: false,
        excerptLength: 150,
        openInNewTab: true,
        sortBy: 'date',
        sortOrder: 'desc',
        filterKeyword: '',
      };
    },
    getSourceTypeLabel(type) {
      const labels = {
        moodle: 'Moodle',
        canvas: 'Canvas',
        brightspace: 'Brightspace',
        custom_rest_api: 'REST API (custom)',
      };
      return labels[type] || type;
    },
    hasConnectionType(type) {
      return this.availableConnections.some(c => c.type === type);
    },
    onSourceTypeChange() {
      this.localWidget.feedUrl = '';
      this.localWidget.connectionId = '';
      this.localWidget.courseId = '';
      this.localWidget.contentType = '';
      this.previewItems = [];
      this.previewError = null;
      this.emitUpdate();
    },
    async loadConnections() {
      try {
        const url = generateUrl('/apps/intravox/api/settings/feed-connections');
        const response = await axios.get(url);
        this.connections = response.data.connections || [];
      } catch {
        this.connections = [];
      }
    },
    formatDate(dateString) {
      try {
        return new Date(dateString).toLocaleDateString();
      } catch {
        return dateString;
      }
    },
    onSharePointContentTypeChange() {
      this.localWidget.listId = '';
      this.emitUpdate();
      if (this.localWidget.contentType === 'documents' || this.localWidget.contentType === 'list') {
        this.loadSharePointLists();
      }
    },
    onCourseChange() {
      this.localWidget.moodleForumId = '';
      this.moodleForums = [];
      this.emitUpdate();
      this.loadMoodleForums();
    },
    onLmsContentTypeChange() {
      this.localWidget.moodleForumId = '';
      this.moodleForums = [];
      this.emitUpdate();
    },
    async loadMoodleForums() {
      if (!this.localWidget.connectionId || !this.isMoodleType || !this.localWidget.courseId || this.localWidget.contentType !== 'news') return;
      this.moodleForumsLoading = true;
      try {
        const url = generateUrl(`/apps/intravox/api/feed/moodle-forums/${this.localWidget.connectionId}?courseId=${this.localWidget.courseId}`);
        const response = await axios.get(url);
        this.moodleForums = response.data.forums || [];
      } catch {
        this.moodleForums = [];
      } finally {
        this.moodleForumsLoading = false;
      }
    },
    async loadJiraProjects() {
      if (!this.localWidget.connectionId || !this.isJiraType) return;
      this.jiraProjectsLoading = true;
      try {
        const url = generateUrl(`/apps/intravox/api/feed/jira-projects/${this.localWidget.connectionId}`);
        const response = await axios.get(url);
        this.jiraProjects = response.data.projects || [];
      } catch {
        this.jiraProjects = [];
      } finally {
        this.jiraProjectsLoading = false;
      }
    },
    async loadSharePointLists() {
      if (!this.localWidget.connectionId || !this.isSharePointType) return;
      this.spListsLoading = true;
      this.spListsError = '';
      try {
        const url = generateUrl(`/apps/intravox/api/feed/sharepoint-lists/${this.localWidget.connectionId}`);
        const response = await axios.get(url);
        if (response.data.error) {
          this.spListsError = response.data.error;
          this.spLists = { libraries: [], lists: [] };
        } else {
          this.spLists = {
            libraries: response.data.libraries || [],
            lists: response.data.lists || [],
          };
        }
      } catch {
        this.spListsError = t('intravox', 'Failed to load SharePoint lists');
        this.spLists = { libraries: [], lists: [] };
      } finally {
        this.spListsLoading = false;
      }
    },
    /**
     * Offer the feed's own name as a title, once, for an empty field.
     *
     * A suggestion, deliberately not a binding. Measured over 128 real feeds:
     * 72% of the names a feed gives itself differ from what an editor would
     * pick, and roughly a quarter are unusable as a heading — "Release notes
     * from core" (Vue), "Nieuwsoverzicht" (SURF), "Press releases - RSS" (EC),
     * or 60-character strap lines. A feed knows what it is, not what it means
     * on this page.
     *
     * So: fill only an empty field, never overwrite what someone typed, and
     * never re-fill after they clear it on purpose — `titlePrefilled` latches
     * so a second preview load (a changed URL, a re-render) leaves the field
     * alone. Clearing the title and leaving is a valid choice: this widget
     * then has no heading.
     */
    onFeedName(naam) {
      if (this.titlePrefilled) {
        return;
      }
      this.titlePrefilled = true;
      if ((this.localWidget.title || '').trim() !== '') {
        return;
      }
      const schoon = String(naam).trim();
      if (schoon === '') {
        return;
      }
      this.localWidget.title = schoon;
      this.emitUpdate();
    },
    emitUpdate() {
      this.$emit('update', { ...this.localWidget });
    },
    setSortOrder(waarde) {
      this.localWidget.sortOrder = waarde;
      this.emitUpdate();
    },
    toggleSortOrder() {
      this.localWidget.sortOrder = this.localWidget.sortOrder === 'desc' ? 'asc' : 'desc';
      this.emitUpdate();
    },
    debouncedEmitUpdate() {
      clearTimeout(this._debounceTimer);
      this._debounceTimer = setTimeout(() => this.emitUpdate(), 500);
    },
    onConnectionChange() {
      this.connectError = null;
      this.showManualToken = false;
      this.manualToken = '';
      this.courses = [];
      this.manualCourseId = false;
      this.spLists = { libraries: [], lists: [] };
      // Ensure sourceType is 'connection' when a connection is selected
      if (this.localWidget.connectionId) {
        this.localWidget.sourceType = 'connection';
      }
      // Reset content type and sub-selections when switching connections
      this.localWidget.contentType = '';
      this.localWidget.courseId = '';
      this.localWidget.listId = '';
      this.localWidget.jiraProject = '';
      this.localWidget.moodleForumId = '';
      this.emitUpdate();
      this.loadUserConnections();
      this.loadCourses();
      this.moodleForums = [];
      if (this.isSharePointType) {
        this.loadSharePointLists();
      }
      if (this.isJiraType) {
        this.loadJiraProjects();
      }
    },
    async loadUserConnections() {
      try {
        const url = generateUrl('/apps/intravox/api/lms/connections');
        const response = await axios.get(url);
        this.userConnections = response.data.connections || [];
      } catch {
        this.userConnections = [];
      }
    },
    async loadCourses() {
      if (!this.localWidget.connectionId || this.localWidget.sourceType === 'rss') {
        this.courses = [];
        return;
      }

      this.coursesLoading = true;
      try {
        const url = generateUrl(`/apps/intravox/api/feed/courses/${this.localWidget.connectionId}`);
        const response = await axios.get(url);
        this.courses = response.data.courses || [];
      } catch {
        this.courses = [];
      } finally {
        this.coursesLoading = false;
      }
    },
    async startOAuth() {
      if (!this.localWidget.connectionId) return;

      this.oauthLoading = true;
      this.connectError = null;

      try {
        const url = generateUrl(`/apps/intravox/api/lms/connect/${this.localWidget.connectionId}`);
        const response = await axios.post(url);

        if (response.data.authUrl) {
          // Open OAuth flow in popup
          const popup = window.open(
            response.data.authUrl,
            'intravox-lms-oauth',
            'width=600,height=700,popup=yes'
          );
          if (!popup) {
            this.connectError = this.t('intravox', 'Pop-up blocked. Please allow pop-ups for this site.');
          }
        }
      } catch (err) {
        this.connectError = err.response?.data?.error || this.t('intravox', 'Failed to start connection');
      } finally {
        this.oauthLoading = false;
      }
    },
    handleOAuthMessage(event) {
      if (event.origin !== window.location.origin) {
        return;
      }
      if (event.data?.type === 'intravox-lms-connected') {
        // Only handle messages for the connection this editor is managing
        if (event.data.connectionId && event.data.connectionId !== this.localWidget.connectionId) {
          return;
        }
        this.loadUserConnections();
        this.connectError = null;
        if (event.data.success) {
          this.loadCourses();
        } else if (event.data.error) {
          this.connectError = event.data.error;
        }
      }
    },
    async saveManualToken() {
      if (!this.manualToken || !this.localWidget.connectionId) return;

      this.manualTokenSaving = true;
      this.connectError = null;

      try {
        const url = generateUrl(`/apps/intravox/api/lms/token/${this.localWidget.connectionId}`);
        await axios.post(url, { token: this.manualToken });

        this.manualToken = '';
        this.showManualToken = false;
        await this.loadUserConnections();
        this.loadCourses();
      } catch (err) {
        this.connectError = err.response?.data?.error || this.t('intravox', 'Failed to save token');
      } finally {
        this.manualTokenSaving = false;
      }
    },
    async disconnectLms() {
      if (!this.localWidget.connectionId) return;

      try {
        const url = generateUrl(`/apps/intravox/api/lms/token/${this.localWidget.connectionId}`);
        await axios.delete(url);
        await this.loadUserConnections();
      } catch (err) {
        this.connectError = err.response?.data?.error || this.t('intravox', 'Failed to disconnect');
      }
    },
  },
};
</script>

<style scoped>
.feed-widget-editor {
  display: flex;
  flex-direction: column;
  gap: 20px;
  padding: 16px;
}

.form-group {
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.form-group > label {
  font-weight: 600;
  font-size: 14px;
  color: var(--color-main-text);
}

.form-group select,
.form-group input[type="url"],
.form-group input[type="text"] {
  width: 100%;
  min-width: 0;
  padding: 8px 12px;
  border: 1px solid var(--color-border);
  border-radius: var(--border-radius);
  background: var(--color-main-background);
  color: var(--color-main-text);
  font-size: 14px;
  box-sizing: border-box;
}

/* Same shape as the photo-story editor's .ps-sort-row: the field and its
   direction are one decision and belong on one line. */
.sort-row {
  display: flex;
  gap: 12px;
  align-items: flex-start;
  flex-wrap: wrap;
}

.sort-by-select {
  flex: 1;
  min-width: 200px;
  max-width: 280px;
}

.sort-order-choice {
  display: flex;
  flex-direction: column;
  gap: 2px;
}

/*
 * A dropdown is as wide as its content needs, not as wide as the dialog.
 * NcSelect stretches to its container by default, which in a 860px modal
 * makes a two-option list span the full width and read as a text field.
 */
.feed-widget-editor :deep(.v-select) {
  max-width: 280px;
}

.sort-order-toggle {
  padding: 6px 12px;
  border: 1px solid var(--color-border);
  border-radius: var(--border-radius);
  background: var(--color-main-background);
  cursor: pointer;
  font-size: 13px;
  white-space: nowrap;
  flex-shrink: 0;
}

.sort-order-toggle:hover {
  background: var(--color-background-hover);
}

.field-error {
  color: var(--color-error);
  font-size: 12px;
  margin: 2px 0 0;
}

.field-hint {
  color: var(--color-text-maxcontrast);
  font-size: 12px;
  margin: 2px 0 0;
  font-style: italic;
}

.field-hint--warning {
  color: var(--color-warning-text, var(--color-warning));
  font-style: normal;
}

.feed-preview-container {
  border: 1px solid var(--color-border);
  border-radius: var(--border-radius);
  background: var(--color-background-hover);
  margin-top: 8px;
  overflow: hidden;
}

.feed-preview-header {
  padding: 8px 12px;
  font-size: 13px;
  font-weight: 600;
  color: var(--color-text-maxcontrast);
  text-transform: uppercase;
  letter-spacing: 0.5px;
  border-bottom: 1px solid var(--color-border);
}

.feed-preview-content {
  max-height: 400px;
  overflow-y: auto;
}

.editor-divider {
  border: none;
  border-top: 1px solid var(--color-border);
  margin: 4px 0;
}

.checkbox-group {
  display: flex;
  flex-direction: column;
  gap: 6px;
}

/* Space between the three groups; the first sits right under its label. */
.checkbox-group + .checkbox-group {
  margin-top: 12px;
}

.checkbox-group-heading {
  font-size: 12px;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.4px;
  color: var(--color-text-maxcontrast);
}

.checkbox-label {
  display: flex;
  align-items: center;
  gap: 8px;
  font-weight: normal;
  cursor: pointer;
  font-size: 14px;
}

.checkbox-label input[type="checkbox"] {
  width: 16px;
  height: 16px;
}

.link-button {
  background: none;
  border: none;
  color: var(--color-primary-element);
  cursor: pointer;
  font-size: 12px;
  padding: 4px 0;
  text-decoration: underline;
}

.link-button:hover {
  color: var(--color-primary-element-hover);
}

.lms-status {
  display: flex;
  align-items: center;
  gap: 8px;
  flex-wrap: wrap;
}

.status-badge {
  display: inline-flex;
  align-items: center;
  padding: 4px 10px;
  border-radius: var(--border-radius-large);
  font-size: 12px;
  font-weight: 600;
}

.status-badge.connected {
  background: color-mix(in srgb, var(--color-success) 15%, transparent);
  color: var(--color-success-text, var(--color-success));
  border: 1px solid color-mix(in srgb, var(--color-success) 30%, transparent);
}

.status-badge.disconnected {
  background: color-mix(in srgb, var(--color-warning) 15%, transparent);
  color: var(--color-warning-text, var(--color-warning));
  border: 1px solid color-mix(in srgb, var(--color-warning) 30%, transparent);
}

.status-badge.loading {
  background: var(--color-background-dark);
  color: var(--color-text-maxcontrast);
}

.disconnect-button {
  padding: 4px 10px;
  border: 1px solid var(--color-border);
  border-radius: var(--border-radius);
  background: transparent;
  color: var(--color-text-maxcontrast);
  cursor: pointer;
  font-size: 12px;
}

.disconnect-button:hover {
  background: var(--color-background-hover);
}

.connect-actions {
  display: flex;
  gap: 8px;
  flex-wrap: wrap;
}

.connect-button {
  padding: 6px 14px;
  border: none;
  border-radius: var(--border-radius);
  background: var(--color-primary-element);
  color: var(--color-primary-element-text);
  cursor: pointer;
  font-size: 13px;
}

.connect-button.secondary {
  background: transparent;
  border: 1px solid var(--color-border);
  color: var(--color-main-text);
}

.connect-button:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.manual-token-input {
  display: flex;
  gap: 8px;
  margin-top: 8px;
  width: 100%;
}

.manual-token-input input {
  flex: 1;
  min-width: 0;
  padding: 6px 10px;
  border: 1px solid var(--color-border);
  border-radius: var(--border-radius);
  background: var(--color-main-background);
  color: var(--color-main-text);
  font-size: 13px;
}

.manual-token-input button {
  padding: 6px 14px;
  border: 1px solid var(--color-border);
  border-radius: var(--border-radius);
  background: var(--color-primary-element);
  color: var(--color-primary-element-text);
  cursor: pointer;
  font-size: 13px;
  white-space: nowrap;
}

.manual-token-input button:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}
</style>
