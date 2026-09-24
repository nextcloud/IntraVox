<template>
  <div class="feed-widget-editor">
    <!--
      Both halves are named. The form used to open with ten unlabelled fields
      and only name the second half, so the heading outline read
      "(nothing) … Display" and the first heading appeared halfway down.
    -->
    <h4 class="editor-section editor-section--first">{{ t('intravox', 'Source') }}</h4>

    <!-- Widget title. First field, matching the news, people and calendar
         editors: it names the block on the page, so it comes before the
         question of where the content comes from. -->
    <div class="form-group">
      <!--
        v-model, not :value.sync. `.sync` was removed in Vue 3: the component
        renders the value but nothing writes typing back, so the field looked
        filled and saved empty.
      -->
      <NcTextField
        id="feed-widget-title"
        v-model="localWidget.title"
        :label="t('intravox', 'Widget title (optional)')"
        label-outside
        @update:model-value="debouncedEmitUpdate"
      />
      <span class="field-hint">{{ t('intravox', 'Left empty, the name from the feed is suggested once. Your own wording always wins.') }}</span>
    </div>

    <!-- Source type selection -->
    <div class="form-group">
      <label for="feed-source-type">{{ t('intravox', 'Source type') }}</label>
      <NcSelect
        class="short-choice"
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
        v-model="localWidget.feedUrl"
        :label="t('intravox', 'Feed URL')"
        type="url"
        label-outside
        @update:model-value="debouncedEmitUpdate"
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
        <span v-if="spListsError" class="field-error">{{ spListsError }}</span>
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

    <!--
      The form has two halves: where the content comes from, and how it is
      shown. They were separated by a bare <hr>, which draws a line without
      saying what is on either side of it. A named heading does both, and gives
      the second half somewhere obvious to start reading.
    -->
    <h4 class="editor-section">{{ t('intravox', 'Display') }}</h4>

    <!-- Layout options -->
    <div class="form-group">
      <label for="feed-layout">{{ t('intravox', 'Layout') }}</label>
      <NcSelect
        class="short-choice"
        input-id="feed-layout"
        v-model="layoutOption"
        :options="layoutOptions"
        :clearable="false"
        label="label"
      />
    </div>

    <!-- Grid columns -->
    <div v-if="localWidget.layout === 'grid'" class="form-group">
      <label for="feed-columns">{{ t('intravox', 'Columns') }}</label>
      <NcSelect
        class="short-choice"
        input-id="feed-columns"
        v-model="columnsOption"
        :options="columnsOptions"
        :clearable="false"
        label="label"
      />
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
          class="sort-by-select short-choice"
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

    <!--
      How many items, and how many at a time — side by side, because the two only
      make sense together: the second cannot exceed the first, and 0 means "show
      them all". Stacked, they were four rows for one decision.

      A second slider rather than a paginate checkbox, because the useful
      question is not "paginate yes/no" but "how tall may this widget be" —
      which is the number itself.
    -->
    <div class="form-group">
      <div class="slider-pair">
        <!--
          The value sits beside the slider, not in the label. A <label> is the
          control's accessible NAME: baking the number into it renamed the
          slider on every drag step, so a screen reader announced the name again
          on top of the value it already reports. <output> is a live region by
          default, so the number is announced once, as a value.

          It also stops the label being a sentence built by concatenation, which
          a translator cannot reorder.
        -->
        <div class="slider-field">
          <label for="feed-limit">{{ t('intravox', 'Number of items') }}</label>
          <div class="slider-row">
            <input
              id="feed-limit"
              v-model.number="localWidget.limit"
              type="range"
              min="1"
              max="20"
              @input="debouncedEmitUpdate"
            />
            <output for="feed-limit" class="slider-value">{{ localWidget.limit }}</output>
          </div>
        </div>
        <div class="slider-field">
          <label for="feed-page-size">{{ t('intravox', 'Items per page') }}</label>
          <div class="slider-row">
            <input
              id="feed-page-size"
              v-model.number="localWidget.pageSize"
              type="range"
              min="0"
              :max="localWidget.limit"
              @input="debouncedEmitUpdate"
            />
            <output for="feed-page-size" class="slider-value">{{ pageSizeLabel }}</output>
          </div>
        </div>
      </div>
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

      <div class="checkbox-groups">
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
    /* The readout beside the page-size slider. 0 means "no paging", which
       reads better as a word than as a zero. */
    pageSizeLabel() {
      return this.localWidget.pageSize > 0
        ? String(this.localWidget.pageSize)
        : this.t('intravox', 'all');
    },
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
    layoutOptions() {
      return [
        { id: 'list', label: this.t('intravox', 'List') },
        { id: 'grid', label: this.t('intravox', 'Grid') },
      ];
    },
    layoutOption: {
      get() {
        return this.layoutOptions.find(o => o.id === this.localWidget.layout) || this.layoutOptions[0];
      },
      set(optie) {
        if (!optie) return;
        this.localWidget.layout = optie.id;
        this.emitUpdate();
      },
    },
    columnsOptions() {
      return [2, 3, 4].map(n => ({ id: n, label: String(n) }));
    },
    columnsOption: {
      get() {
        return this.columnsOptions.find(o => o.id === this.localWidget.columns) || this.columnsOptions[1];
      },
      set(optie) {
        if (!optie) return;
        this.localWidget.columns = optie.id;
        this.emitUpdate();
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
/*
 * The measure lives here, not on the host modal.
 *
 * WidgetEditor caps .form-group at 900px, but its <style> is scoped: that rule
 * carries the PARENT's data-v attribute and these .form-group elements carry
 * this component's, so it never reached them. Every full-width control was
 * running the modal's 1200px. The 900px is Nextcloud's own NcSettingsSection
 * measure; applying it at the root covers every child without a :deep hack.
 */
.feed-widget-editor {
  display: flex;
  flex-direction: column;
  gap: 24px;
  padding: 24px 20px;
  max-width: 900px;
}

.form-group {
  display: flex;
  flex-direction: column;
  gap: 4px;
}

/*
 * label-outside renders the label inside the component's own scope, so
 * .form-group > label below cannot reach it. Without this the two converted
 * fields would get maxcontrast labels beside fourteen bold ones — trading one
 * inconsistency for another.
 */
.feed-widget-editor :deep(.input-field--label-outside .input-field__label) {
  font-weight: 600;
  font-size: var(--default-font-size);
  color: var(--color-main-text);
  margin-block-end: 4px;
}

.form-group > label {
  font-weight: 600;
  font-size: var(--default-font-size);
  color: var(--color-main-text);
}

/*
 * These are raw controls next to Nextcloud ones, so they have to match them or
 * the column visibly alternates: --default-clickable-area for the height (the
 * NcSelect/NcTextField height), --border-radius-element for the corners (they
 * are 8px, these were 4px), and --color-border-maxcontrast because that is what
 * NcInputField sets its own border to — --color-border left them visibly
 * fainter than the fields beside them.
 *
 * Replacing them with NcSelect/NcTextField is the real fix; this is the part
 * that can be done without rewriting fourteen controls.
 */
.form-group select,
.form-group input[type="url"],
.form-group input[type="text"] {
  width: 100%;
  min-width: 0;
  height: var(--default-clickable-area);
  padding-block: 0;
  padding-inline: 12px;
  border: 1px solid var(--color-border-maxcontrast);
  border-radius: var(--border-radius-element);
  background: var(--color-main-background);
  color: var(--color-main-text);
  font-size: var(--default-font-size);
  box-sizing: border-box;
}

/*
 * The raw controls fall back to the browser's focus ring, which carries no
 * contrast guarantee. The NC components bring their own; these need one.
 */
.feed-widget-editor :is(select, input, button):focus-visible {
  outline: 2px solid var(--color-primary-element);
  outline-offset: 2px;
}

/* Same shape as the photo-story editor's .ps-sort-row: the field and its
   direction are one decision and belong on one line. */
.sort-row {
  display: flex;
  gap: 12px;
  align-items: flex-start;
  flex-wrap: wrap;
}

/* Sized by its content like the other short choices, not stretched by flex. */
.sort-by-select {
  flex: 0 1 auto;
}

/*
 * The two directions sit side by side, not stacked.
 *
 * Stacked, they made a two-line column beside a one-line dropdown: the row grew
 * to the height of the taller half and the dropdown floated against a block of
 * white. Two short, mutually exclusive labels read fine on one line, and the
 * row then has one height. They wrap to two lines on a narrow column, which is
 * what flex-wrap is for.
 */
.sort-order-choice {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: 4px 16px;
}

/*
 * A choice from a short list is as wide as its content; a text field is not.
 *
 * There used to be a blanket `max-width: 280px` on every NcSelect here, written
 * for an 860px modal that is now 1200px. It capped the connection and
 * SharePoint-list pickers too, which hold long names, while raw inputs beside
 * them were width:100% — a 280px dropdown next to a 1160px field.
 *
 * The form now carries the measure (WidgetEditor .form-group, 900px, matching
 * Nextcloud's own NcSettingsSection). On top of that, the dropdowns that pick
 * from a fixed short list — source type, layout, columns, sort by — size to
 * their content rather than stretching: "Date" and "List" do not need 900px to
 * be read, and a full-width two-option list reads as a text field.
 *
 * min-width is NcSelect's own 260px, not a number invented here. Pickers whose
 * options are user data (connections, courses, lists) are deliberately NOT in
 * this rule: their content is unpredictable, so they keep the full width.
 */
.feed-widget-editor :deep(.v-select.short-choice) {
  width: fit-content;
  max-width: 100%;
}

/*
 * --color-error-text, not --color-error. The latter is the pale BACKGROUND tint
 * of the error trio, so using it on text gives near-invisible red-on-white —
 * and in dark mode it is worse, because the tint is built to sit under text
 * rather than be it.
 */
.field-error {
  color: var(--color-error-text);
  font-size: var(--font-size-small);
  margin-block: 0;
}

/*
 * 13px, not 12: --font-size-small is the smallest size Nextcloud has, and it
 * scales with the user's settings where a hardcoded 12px does not. Combined
 * with maxcontrast, 12px was the least legible text in the editor.
 */
/*
 * Not italic. Small AND low-contrast is already the pairing the guidelines warn
 * about; italic is a third reduction on top of two, and Nextcloud does not
 * italicise helper text anywhere. margin-block: 0 because .form-group's 4px gap
 * already spaces it — the old 2px made a 6px total, off the 4px grid.
 */
.field-hint {
  color: var(--color-text-maxcontrast);
  font-size: var(--font-size-small);
  margin-block: 0;
}

.field-hint--warning {
  color: var(--color-warning-text);
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
  font-size: var(--font-size-small);
  font-weight: 600;
  color: var(--color-text-maxcontrast);
  border-bottom: 1px solid var(--color-border);
}

.feed-preview-content {
  max-height: 400px;
  overflow-y: auto;
}

/*
 * Names the second half of the form and separates it at the same time, where a
 * bare <hr> only did the second. Sentence case and 15px: this is a heading over
 * a section, not the supporting text the group labels inside it are.
 */
/*
 * Inline margin 0: the root already pads 20px, so the old `margin: … 20px …`
 * indented the one heading 20px further than every field it introduced — the
 * single most visible misalignment in the form. The 8px plus the root's 24px
 * flex gap makes a 32px band above the rule, matching the 32px below it.
 */
.editor-section {
  margin: 8px 0 0;
  padding-block-start: 32px;
  border-block-start: 1px solid var(--color-border);
  font-size: var(--default-font-size);
  font-weight: 600;
  color: var(--color-main-text);
}

/* The first heading has nothing above it to divide. */
.editor-section--first {
  margin-block-start: 0;
  padding-block-start: 0;
  border-block-start: none;
}

/*
 * Six toggles in three named groups, laid out in columns rather than one tall
 * stack. Each toggle is one short line, so a single column wasted the width the
 * form already has and pushed the preview below the fold. The groups keep their
 * headings — they are what makes "show source" (per item) and "open links in a
 * new tab" (behaviour) legible as different kinds of setting.
 *
 * auto-fit rather than a fixed count: on a narrow column it collapses back to
 * one, without a media query.
 */
/*
 * The two counts share a row. Same auto-fit as the toggle groups, so they drop
 * under each other on a narrow column without a media query.
 */
.slider-pair {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
  gap: 8px 24px;
}

.slider-field {
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.slider-row {
  display: flex;
  align-items: center;
  gap: 12px;
}

/* Fill the column instead of the browser's default track width, and follow the
   theme rather than the browser's accent. */
.slider-field input[type="range"] {
  flex: 1;
  min-width: 0;
  accent-color: var(--color-primary-element);
}

/* Reserved width and tabular digits: the number must not shift the slider
   while you drag it. */
.slider-value {
  min-width: 3ch;
  text-align: end;
  font-size: var(--default-font-size);
  color: var(--color-main-text);
  font-variant-numeric: tabular-nums;
}

.checkbox-groups {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
  gap: 16px 24px;
  align-items: start;
}

.checkbox-group {
  display: flex;
  flex-direction: column;
  gap: 4px;
}

/* Sentence case, not ALL CAPS: the writing guide is explicit about it, and
   uppercase also costs the width these three columns do not have. */
.checkbox-group-heading {
  font-size: var(--font-size-small);
  font-weight: 600;
  color: var(--color-text-maxcontrast);
  margin-bottom: 4px;
}

.link-button {
  background: none;
  border: none;
  color: var(--color-primary-element);
  cursor: pointer;
  font-size: var(--font-size-small);
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
  font-size: var(--font-size-small);
  font-weight: 600;
}

.status-badge.connected {
  background: color-mix(in srgb, var(--color-success) 15%, transparent);
  color: var(--color-success-text);
  border: 1px solid color-mix(in srgb, var(--color-success) 30%, transparent);
}

.status-badge.disconnected {
  background: color-mix(in srgb, var(--color-warning) 15%, transparent);
  color: var(--color-warning-text);
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
  font-size: var(--font-size-small);
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
  font-size: var(--font-size-small);
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
  font-size: var(--font-size-small);
}

.manual-token-input button {
  padding: 6px 14px;
  border: 1px solid var(--color-border);
  border-radius: var(--border-radius);
  background: var(--color-primary-element);
  color: var(--color-primary-element-text);
  cursor: pointer;
  font-size: var(--font-size-small);
  white-space: nowrap;
}

.manual-token-input button:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}
</style>
