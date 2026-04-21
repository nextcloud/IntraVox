<template>
  <div class="feed-widget-editor">
    <!-- Source type selection -->
    <div class="form-group">
      <label for="feed-source-type">{{ t('Source type') }}</label>
      <select id="feed-source-type" v-model="localWidget.sourceType" @change="onSourceTypeChange">
        <option value="rss">{{ t('RSS / Atom feed') }}</option>
        <option v-if="availableConnections.length > 0 || localWidget.sourceType === 'connection'" value="connection">{{ t('Connection') }}</option>
      </select>
    </div>

    <!-- RSS URL input -->
    <div v-if="localWidget.sourceType === 'rss'" class="form-group">
      <label for="feed-url">{{ t('Feed URL') }}</label>
      <input
        id="feed-url"
        v-model="localWidget.feedUrl"
        type="url"
        :placeholder="t('https://example.com/feed.xml')"
        @input="debouncedEmitUpdate"
      />
    </div>

    <!-- Connection selection -->
    <div v-if="localWidget.sourceType === 'connection'" class="form-group">
      <label for="feed-connection">{{ t('Connection') }}</label>
      <select id="feed-connection" v-model="localWidget.connectionId" @change="onConnectionChange">
        <option value="">{{ t('Select a connection...') }}</option>
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
          {{ inactiveSelectedConnection.name }} ({{ t('inactive') }})
        </option>
      </select>
      <p v-if="inactiveSelectedConnection" class="field-hint field-hint--warning">
        {{ t('This connection is currently disabled by an administrator.') }}
      </p>
      <p v-else-if="availableConnections.length === 0" class="field-hint">
        {{ t('No connections configured. Ask an administrator to add one in IntraVox settings.') }}
      </p>
    </div>

    <!-- User LMS connection status -->
    <div v-if="isLmsType && localWidget.connectionId && selectedConnectionAuthMode !== 'token'" class="form-group">
      <div v-if="userConnectionStatus === 'connected'" class="lms-status lms-status-connected">
        <span class="status-badge connected">{{ userConnectionTokenType === 'oidc' ? t('Connected via SSO') : t('Connected') }}</span>
        <button class="disconnect-button" @click="disconnectLms">{{ t('Disconnect') }}</button>
      </div>
      <div v-else-if="userConnectionStatus === 'loading'" class="lms-status">
        <span class="status-badge loading">{{ t('Checking...') }}</span>
      </div>
      <div v-else class="lms-status lms-status-disconnected">
        <span class="status-badge disconnected">{{ t('Not connected') }}</span>
        <div class="connect-actions">
          <button v-if="selectedConnectionAuthMode !== 'token'" class="connect-button" @click="startOAuth" :disabled="oauthLoading">
            {{ oauthLoading ? t('Connecting...') : t('Connect your account') }}
          </button>
          <button v-if="selectedConnectionType === 'moodle' || selectedConnectionType === 'brightspace'" class="connect-button secondary" @click="showManualToken = !showManualToken">
            {{ t('Enter token manually') }}
          </button>
        </div>
        <div v-if="showManualToken" class="manual-token-input">
          <input
            v-model="manualToken"
            type="password"
            :placeholder="t('Paste your API token here')"
          />
          <button @click="saveManualToken" :disabled="!manualToken || manualTokenSaving">
            {{ manualTokenSaving ? t('Saving...') : t('Save token') }}
          </button>
        </div>
        <p v-if="connectError" class="field-error">{{ connectError }}</p>
      </div>
    </div>

    <!-- Brightspace content type -->
    <div v-if="isLmsType && localWidget.connectionId" class="form-group">
      <label for="feed-content-type">{{ t('Content type') }}</label>
      <select id="feed-content-type" v-model="localWidget.contentType" @change="onLmsContentTypeChange">
        <option value="news">{{ t('News / Announcements') }}</option>
        <option value="courses">{{ t('Available Courses') }}</option>
        <option value="assignments">{{ t('Assignments') }}</option>
        <option value="deadlines">{{ t('Upcoming Deadlines') }}</option>
      </select>
    </div>

    <!-- Moodle forum selector (when News + course selected) -->
    <div v-if="isMoodleType && localWidget.connectionId && localWidget.contentType === 'news' && localWidget.courseId" class="form-group">
      <label for="feed-moodle-forum">{{ t('Forum (optional)') }}</label>
      <div v-if="moodleForumsLoading" class="field-hint">{{ t('Loading...') }}</div>
      <template v-else>
        <select id="feed-moodle-forum" v-model="localWidget.moodleForumId" @change="emitUpdate">
          <option value="">{{ t('All forums') }}</option>
          <option v-for="forum in moodleForums" :key="forum.id" :value="forum.id">{{ forum.name }}</option>
        </select>
      </template>
    </div>

    <!-- OpenProject content type -->
    <div v-if="isOpenProjectType && localWidget.connectionId" class="form-group">
      <label for="feed-content-type-op">{{ t('Content type') }}</label>
      <select id="feed-content-type-op" v-model="localWidget.contentType" @change="emitUpdate">
        <option value="">{{ t('All work packages') }}</option>
        <option value="open">{{ t('Open work packages') }}</option>
        <option value="overdue">{{ t('Overdue') }}</option>
        <option value="milestones">{{ t('Milestones') }}</option>
        <option value="recently-updated">{{ t('Recently updated') }}</option>
      </select>
    </div>

    <!-- Jira project selector -->
    <div v-if="isJiraType && localWidget.connectionId" class="form-group">
      <label for="feed-jira-project">{{ t('Project') }}</label>
      <div v-if="jiraProjectsLoading" class="field-hint">{{ t('Loading...') }}</div>
      <template v-else>
        <select id="feed-jira-project" v-model="localWidget.jiraProject" @change="emitUpdate">
          <option value="">{{ t('All projects') }}</option>
          <option v-for="proj in jiraProjects" :key="proj.key" :value="proj.key">{{ proj.name }} ({{ proj.key }})</option>
        </select>
      </template>
    </div>
    <div v-if="isJiraType && localWidget.connectionId" class="form-group">
      <label for="feed-content-type-jira">{{ t('Content type') }}</label>
      <select id="feed-content-type-jira" v-model="localWidget.contentType" @change="emitUpdate">
        <option value="">{{ t('All issues') }}</option>
        <option value="open">{{ t('Open issues') }}</option>
        <option value="recent">{{ t('Recently updated (7 days)') }}</option>
        <option value="created-recent">{{ t('Recently created (7 days)') }}</option>
        <option value="bugs">{{ t('Bugs') }}</option>
      </select>
    </div>

    <!-- SharePoint content type -->
    <div v-if="isSharePointType && localWidget.connectionId" class="form-group">
      <label for="feed-content-type-sp">{{ t('Content type') }}</label>
      <select id="feed-content-type-sp" v-model="localWidget.contentType" @change="onSharePointContentTypeChange">
        <option value="pages">{{ t('All pages') }}</option>
        <option value="news">{{ t('News posts') }}</option>
        <option value="documents">{{ t('Documents') }}</option>
        <option value="list">{{ t('List items') }}</option>
      </select>
    </div>

    <!-- SharePoint list/library selector -->
    <div v-if="isSharePointType && localWidget.connectionId && (localWidget.contentType === 'documents' || localWidget.contentType === 'list')" class="form-group">
      <label for="feed-sp-list">{{ localWidget.contentType === 'documents' ? t('Document library') : t('List') }}</label>
      <div v-if="spListsLoading" class="field-hint">{{ t('Loading...') }}</div>
      <template v-else>
        <select id="feed-sp-list" v-model="localWidget.listId" @change="emitUpdate">
          <option value="">{{ t('Select...') }}</option>
          <option v-for="item in spListsForType" :key="item.id" :value="item.id">{{ item.name }}</option>
        </select>
        <span v-if="spListsError" class="field-hint" style="color: var(--color-error)">{{ spListsError }}</span>
      </template>
    </div>

    <!-- Course selection (for LMS types) -->
    <div v-if="isLmsType && localWidget.connectionId && showCourseIdField" class="form-group">
      <label for="feed-course-id">{{ t('Course (optional)') }}</label>
      <div v-if="coursesLoading" class="field-hint">{{ t('Loading courses...') }}</div>
      <template v-else-if="courses.length > 0 && !manualCourseId">
        <select id="feed-course-id" v-model="localWidget.courseId" @change="onCourseChange">
          <option value="">{{ t('All courses') }}</option>
          <option v-for="course in courses" :key="course.id" :value="course.id">
            {{ course.name }}
          </option>
        </select>
        <button class="link-button" @click="manualCourseId = true">{{ t('Enter ID manually') }}</button>
      </template>
      <template v-else>
        <input
          id="feed-course-id"
          v-model="localWidget.courseId"
          type="text"
          :placeholder="courses.length > 0 ? t('Enter course ID') : t('Course ID (connect your account to see a list)')"
          @blur="emitUpdate"
        />
        <button v-if="courses.length > 0" class="link-button" @click="manualCourseId = false">{{ t('Select from list') }}</button>
      </template>
    </div>

    <hr class="editor-divider" />

    <!-- Layout options -->
    <div class="form-group">
      <label for="feed-layout">{{ t('Layout') }}</label>
      <select id="feed-layout" v-model="localWidget.layout" @change="emitUpdate">
        <option value="list">{{ t('List') }}</option>
        <option value="grid">{{ t('Grid') }}</option>
      </select>
    </div>

    <!-- Grid columns -->
    <div v-if="localWidget.layout === 'grid'" class="form-group">
      <label for="feed-columns">{{ t('Columns') }}</label>
      <select id="feed-columns" v-model.number="localWidget.columns" @change="emitUpdate">
        <option :value="2">2</option>
        <option :value="3">3</option>
        <option :value="4">4</option>
      </select>
    </div>

    <!-- Sort -->
    <div class="form-group">
      <label for="feed-sort-by">{{ t('Sort by') }}</label>
      <div class="sort-row">
        <select id="feed-sort-by" v-model="localWidget.sortBy" @change="emitUpdate">
          <option value="date">{{ t('Date') }}</option>
          <option value="title">{{ t('Title') }}</option>
        </select>
        <button type="button" class="sort-order-toggle" :title="sortOrderLabel" @click="toggleSortOrder">
          {{ sortOrderLabel }}
        </button>
      </div>
    </div>

    <!-- Filter -->
    <div class="form-group">
      <label for="feed-filter">{{ t('Filter by keyword (optional)') }}</label>
      <input
        id="feed-filter"
        v-model="localWidget.filterKeyword"
        type="text"
        :placeholder="t('e.g. announcement, update')"
        @input="debouncedEmitUpdate"
      />
      <span class="field-hint">{{ t('Only show items containing this word in title, excerpt, or author.') }}</span>
    </div>

    <!-- Limit -->
    <div class="form-group">
      <label for="feed-limit">{{ t('Number of items') }}: {{ localWidget.limit }}</label>
      <input
        id="feed-limit"
        v-model.number="localWidget.limit"
        type="range"
        min="1"
        max="20"
        @input="debouncedEmitUpdate"
      />
    </div>

    <!-- Display options -->
    <div class="form-group">
      <label>{{ t('Display options') }}</label>
      <div class="checkbox-group">
        <label class="checkbox-label">
          <input type="checkbox" v-model="localWidget.showImage" @change="emitUpdate" />
          {{ t('Show image') }}
        </label>
        <label class="checkbox-label">
          <input type="checkbox" v-model="localWidget.showDate" @change="emitUpdate" />
          {{ t('Show date') }}
        </label>
        <label class="checkbox-label">
          <input type="checkbox" v-model="localWidget.showExcerpt" @change="emitUpdate" />
          {{ t('Show excerpt') }}
        </label>
        <label class="checkbox-label">
          <input type="checkbox" v-model="localWidget.showSource" @change="emitUpdate" />
          {{ t('Show source') }}
        </label>
        <label class="checkbox-label">
          <input type="checkbox" v-model="localWidget.openInNewTab" @change="emitUpdate" />
          {{ t('Open links in new tab') }}
        </label>
      </div>
    </div>

    <!-- Live preview -->
    <div v-if="hasValidSource" class="feed-preview-container">
      <div class="feed-preview-header">{{ t('Preview') }}</div>
      <div class="feed-preview-content">
        <FeedWidget :widget="localWidget" :key="previewKey" />
      </div>
    </div>
  </div>
</template>

<script>
import axios from '@nextcloud/axios';
import { generateUrl } from '@nextcloud/router';
import FeedWidget from './FeedWidget.vue';

export default {
  name: 'FeedWidgetEditor',
  components: {
    FeedWidget,
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
    sortOrderLabel() {
      if (this.localWidget.sortBy === 'title') {
        return this.localWidget.sortOrder === 'asc' ? 'A → Z' : 'Z → A';
      }
      return this.localWidget.sortOrder === 'desc' ? this.t('Newest first') : this.t('Oldest first');
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
    t(text) {
      return window.t ? window.t('intravox', text) : text;
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
    emitUpdate() {
      this.$emit('update', { ...this.localWidget });
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
            this.connectError = this.t('Popup blocked. Please allow popups for this site.');
          }
        }
      } catch (err) {
        this.connectError = err.response?.data?.error || this.t('Failed to start connection');
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
        this.connectError = err.response?.data?.error || this.t('Failed to save token');
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
        this.connectError = err.response?.data?.error || this.t('Failed to disconnect');
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

.sort-row {
  display: flex;
  gap: 8px;
  align-items: center;
}

.sort-row select {
  flex: 1;
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
