<template>
  <div class="feed-widget-editor">
    <!-- Source type selection -->
    <div class="form-group">
      <label for="feed-source-type">{{ t('Source type') }}</label>
      <select id="feed-source-type" v-model="localWidget.sourceType" @change="onSourceTypeChange">
        <option value="rss">{{ t('RSS / Atom feed') }}</option>
        <option v-for="lmsType in availableLmsTypes" :key="lmsType" :value="lmsType">
          {{ getSourceTypeLabel(lmsType) }}
        </option>
      </select>
    </div>

    <!-- RSS URL input -->
    <div v-if="localWidget.sourceType === 'rss'" class="form-group">
      <label for="feed-url">{{ t('Feed URL') }}</label>
      <div class="url-input-group">
        <input
          id="feed-url"
          v-model="localWidget.feedUrl"
          type="url"
          :placeholder="t('https://example.com/feed.xml')"
          @blur="emitUpdate"
          @keydown.enter="loadPreview"
        />
        <button class="preview-button" @click="loadPreview" :disabled="!localWidget.feedUrl || previewLoading">
          {{ previewLoading ? t('Loading...') : t('Preview') }}
        </button>
      </div>
      <p v-if="previewError" class="field-error">{{ previewError }}</p>
    </div>

    <!-- LMS connection selection -->
    <div v-else class="form-group">
      <label for="feed-connection">{{ t('Connection') }}</label>
      <select id="feed-connection" v-model="localWidget.connectionId" @change="onConnectionChange">
        <option value="">{{ t('Select a connection...') }}</option>
        <option
          v-for="conn in connectionsForType"
          :key="conn.id"
          :value="conn.id"
        >
          {{ conn.name }}
        </option>
      </select>
      <p v-if="connectionsForType.length === 0" class="field-hint">
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
      <select id="feed-content-type" v-model="localWidget.contentType" @change="emitUpdate">
        <template v-if="localWidget.sourceType === 'nextcloud'">
          <option value="news">{{ t('Activity') }}</option>
          <option value="deadlines">{{ t('Notifications') }}</option>
        </template>
        <template v-else>
          <option value="news">{{ t('News / Announcements') }}</option>
          <option value="my-courses">{{ t('My Courses') }}</option>
          <option value="deadlines">{{ t('Upcoming Deadlines') }}</option>
        </template>
      </select>
    </div>

    <!-- Course selection (for LMS types) -->
    <div v-if="isLmsType && localWidget.connectionId && showCourseIdField" class="form-group">
      <label for="feed-course-id">{{ t('Course (optional)') }}</label>
      <div v-if="coursesLoading" class="field-hint">{{ t('Loading courses...') }}</div>
      <template v-else-if="courses.length > 0 && !manualCourseId">
        <select id="feed-course-id" v-model="localWidget.courseId" @change="emitUpdate">
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

    <!-- Preview -->
    <div v-if="previewItems.length > 0" class="preview-section">
      <h4>{{ t('Preview') }}</h4>
      <div class="preview-items">
        <div v-for="item in previewItems" :key="item.id" class="preview-item">
          <strong>{{ item.title }}</strong>
          <span v-if="item.date" class="preview-date">{{ formatDate(item.date) }}</span>
        </div>
      </div>
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
        <select id="feed-sort-order" v-model="localWidget.sortOrder" @change="emitUpdate" :aria-label="t('Sort order')">
          <option value="desc">{{ t('Newest first') }}</option>
          <option value="asc">{{ t('Oldest first') }}</option>
        </select>
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
        @input="emitUpdate"
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
        @input="emitUpdate"
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
  </div>
</template>

<script>
import axios from '@nextcloud/axios';
import { generateUrl } from '@nextcloud/router';

export default {
  name: 'FeedWidgetEditor',
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
      previewItems: [],
      previewLoading: false,
      previewError: null,
      courses: [],
      coursesLoading: false,
      manualCourseId: false,
      oauthLoading: false,
      showManualToken: false,
      manualToken: '',
      manualTokenSaving: false,
      connectError: null,
    };
  },
  computed: {
    isLmsType() {
      const type = this.localWidget.sourceType;
      return type !== 'rss' && type !== 'custom_rest_api';
    },
    availableConnections() {
      return this.connections.filter(c => c.active);
    },
    connectionsForType() {
      return this.availableConnections.filter(c => c.type === this.localWidget.sourceType);
    },
    selectedConnection() {
      return this.availableConnections.find(c => c.id === this.localWidget.connectionId) || null;
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
      if (this.localWidget.sourceType === 'nextcloud') {
        return false; // Nextcloud has no course concept
      }
      if (this.localWidget.sourceType !== 'rss') {
        return !this.localWidget.contentType || this.localWidget.contentType === 'news';
      }
      return true;
    },
    availableLmsTypes() {
      const types = new Set(this.availableConnections.map(c => c.type));
      return [...types];
    },
  },
  watch: {
    widget: {
      handler(newWidget) {
        this.localWidget = { ...this.createDefaultWidget(), ...newWidget };
      },
      deep: true,
      immediate: true,
    },
  },
  mounted() {
    this.loadConnections();
    this.loadUserConnections();
    this.loadCourses();
    window.addEventListener('message', this.handleOAuthMessage);
  },
  beforeUnmount() {
    window.removeEventListener('message', this.handleOAuthMessage);
  },
  methods: {
    t(text) {
      return window.t ? window.t('intravox', text) : text;
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
        nextcloud: 'Nextcloud',
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
    async loadPreview() {
      if (!this.localWidget.feedUrl && this.localWidget.sourceType === 'rss') return;

      this.previewLoading = true;
      this.previewError = null;

      try {
        const params = new URLSearchParams({
          sourceType: this.localWidget.sourceType,
        });

        if (this.localWidget.sourceType === 'rss') {
          params.append('url', this.localWidget.feedUrl);
        } else {
          params.append('connectionId', this.localWidget.connectionId);
          if (this.localWidget.courseId) {
            params.append('courseId', this.localWidget.courseId);
          }
          if (this.localWidget.contentType) {
            params.append('contentType', this.localWidget.contentType);
          }
        }

        const url = generateUrl(`/apps/intravox/api/feed/preview?${params}`);
        const response = await axios.get(url);

        if (response.data.error) {
          this.previewError = response.data.error;
          this.previewItems = [];
        } else {
          this.previewItems = response.data.items || [];
          if (this.previewItems.length === 0) {
            this.previewError = this.t('Feed returned no items');
          }
        }
      } catch (err) {
        this.previewError = err.response?.data?.error || this.t('Failed to load preview');
        this.previewItems = [];
      } finally {
        this.previewLoading = false;
      }

      this.emitUpdate();
    },
    formatDate(dateString) {
      try {
        return new Date(dateString).toLocaleDateString();
      } catch {
        return dateString;
      }
    },
    emitUpdate() {
      this.$emit('update', { ...this.localWidget });
    },
    onConnectionChange() {
      this.connectError = null;
      this.showManualToken = false;
      this.manualToken = '';
      this.courses = [];
      this.manualCourseId = false;
      this.emitUpdate();
      this.loadUserConnections();
      this.loadCourses();
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
        this.loadUserConnections();
        this.connectError = null;
        if (event.data.success) {
          this.loadCourses();
          this.loadPreview();
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
        this.loadPreview();
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

.url-input-group,
.sort-row {
  display: flex;
  gap: 8px;
}

.sort-row select {
  flex: 1;
}

.url-input-group input {
  flex: 1;
  min-width: 0;
}

.preview-button {
  padding: 8px 16px;
  border: 1px solid var(--color-border);
  border-radius: var(--border-radius);
  background: var(--color-primary-element);
  color: var(--color-primary-element-text);
  cursor: pointer;
  font-size: 14px;
  white-space: nowrap;
  flex-shrink: 0;
}

.preview-button:disabled {
  opacity: 0.5;
  cursor: not-allowed;
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

.preview-section {
  border: 1px solid var(--color-border);
  border-radius: var(--border-radius);
  padding: 12px;
  background: var(--color-background-hover);
}

.preview-section h4 {
  margin: 0 0 8px;
  font-size: 13px;
  color: var(--color-text-maxcontrast);
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.preview-items {
  display: flex;
  flex-direction: column;
  gap: 6px;
}

.preview-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 8px;
  font-size: 13px;
}

.preview-item strong {
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
  flex: 1;
  min-width: 0;
}

.preview-date {
  color: var(--color-text-maxcontrast);
  font-size: 12px;
  white-space: nowrap;
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
  background: var(--color-success);
  color: var(--color-primary-element-text);
}

.status-badge.disconnected {
  background: var(--color-warning);
  color: var(--color-primary-element-text);
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
