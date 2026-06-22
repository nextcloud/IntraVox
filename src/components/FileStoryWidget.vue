<template>
  <div class="file-story-widget" :class="rowBgClass" :style="rowBgStyle">
    <!-- Loading -->
    <div v-if="loading" class="fs-loading" role="status" aria-live="polite" :aria-label="t('intravox', 'Loading documents')">
      <div v-for="i in 5" :key="i" class="fs-skeleton-row" aria-hidden="true"></div>
    </div>

    <!-- Error -->
    <div v-else-if="error" class="fs-error" role="alert">
      <AlertCircle :size="20" aria-hidden="true" />
      <span>{{ error }}</span>
      <NcButton type="secondary" @click="fetch()">
        {{ t('intravox', 'Retry') }}
      </NcButton>
    </div>

    <!-- No-access: minimal locked placeholder; deliberately hides the folder
         name to avoid leaking the existence of a path the viewer can't see. -->
    <div v-else-if="!files.length && accessReason === 'folder_not_accessible'" class="fs-empty fs-empty--locked" role="status">
      <LockOutline :size="28" aria-hidden="true" />
      <p>{{ t('intravox', 'You do not have access to this widget') }}</p>
    </div>

    <!-- Empty (accessible folder but no documents to show) -->
    <div v-else-if="!files.length" class="fs-empty" role="status">
      <FileDocumentOutline :size="28" aria-hidden="true" />
      <p>{{ emptyMessage }}</p>
      <small v-if="config.folderPath">{{ config.folderPath }}</small>
      <small v-if="showScanHint" class="fs-empty-hint">
        {{ t('intravox', 'If you can see files in the Files app but not here, the file index may be out of sync. Ask an admin to run "occ files:scan" for this folder.') }}
      </small>
    </div>

    <!-- Timeline mode -->
    <div v-else-if="effectiveMode === 'timeline'" class="fs-timeline">
      <section
        v-for="day in timelineDays"
        :key="day.date"
        class="fs-day"
      >
        <header class="fs-day-header">
          <h4 class="fs-day-date">{{ day.label || formatLongDate(day.date) }}</h4>
          <span class="fs-day-count">{{ day.photos.length }}</span>
        </header>
        <ul class="fs-list">
          <li
            v-for="file in day.photos"
            :key="file.file_id"
            class="fs-row"
            tabindex="0"
            role="button"
            :aria-label="t('intravox', 'Open {name}', { name: file.name })"
            @click="openFile(file)"
            @keydown.enter="openFile(file)"
            @keydown.space.prevent="openFile(file)"
          >
            <FileTypeIcon :mime="file.mime" />
            <div class="fs-row-main">
              <div class="fs-row-name">
                {{ file.name }}
                <CloudOutline
                  v-if="file.is_federated"
                  :size="13"
                  class="fs-federated-badge"
                  role="img"
                  :aria-label="t('intravox', 'From federated share — MetaVox metadata not available')"
                  :title="t('intravox', 'From federated share — MetaVox metadata not available')"
                />
              </div>
              <div class="fs-row-meta">
                <span v-if="showColumn('date')" class="fs-row-date">{{ formatTime(file.mtime) }}</span>
                <span v-if="showColumn('size')" class="fs-row-size">{{ formatSize(file.size) }}</span>
                <span v-if="showColumn('path')" class="fs-row-path">{{ file.path }}</span>
              </div>
            </div>
          </li>
        </ul>
      </section>
    </div>

    <!-- Tiles mode — visual grid with preview thumbnails -->
    <div v-else-if="effectiveMode === 'tiles'" class="fs-tiles" :style="tilesGridStyle" role="list">
      <div
        v-for="file in files"
        :key="file.file_id"
        class="fs-tile"
        tabindex="0"
        role="button"
        :aria-label="t('intravox', 'Open {name}', { name: file.name })"
        @click="openFile(file)"
        @keydown.enter="openFile(file)"
        @keydown.space.prevent="openFile(file)"
      >
        <div class="fs-tile-preview">
          <!-- Always render the mime-icon fallback. When NC has a usable
               preview, the <img> on top hides it; on 404 the <img> stays
               unloaded and the fallback shows through. More robust than
               relying on @error event firing across all browsers. -->
          <div class="fs-tile-fallback">
            <FileTypeIcon :mime="file.mime" />
            <span class="fs-tile-ext">{{ fileExt(file.name) }}</span>
          </div>
          <img
            v-if="!tilePreviewErrors[file.file_id]"
            :src="previewUrl(file, 400)"
            alt=""
            loading="lazy"
            decoding="async"
            class="fs-tile-img"
            @error="markPreviewError(file.file_id)"
          />
        </div>
        <div class="fs-tile-body">
          <div class="fs-tile-name" :title="file.name">
            {{ file.name }}
            <CloudOutline
              v-if="file.is_federated"
              :size="12"
              class="fs-federated-badge"
              role="img"
              :aria-label="t('intravox', 'From federated share — MetaVox metadata not available')"
              :title="t('intravox', 'From federated share — MetaVox metadata not available')"
            />
          </div>
          <div class="fs-tile-meta">
            <span v-if="showColumn('date')">{{ formatLongDate(rowDate(file)) }}</span>
            <span v-if="showColumn('size')">{{ formatSize(file.size) }}</span>
          </div>
        </div>
      </div>
    </div>

    <!-- List mode (flat) -->
    <div v-else-if="effectiveMode === 'list'" class="fs-list-mode">
      <ul class="fs-list">
        <li
          v-for="file in files"
          :key="file.file_id"
          class="fs-row"
          tabindex="0"
          role="button"
          :aria-label="t('intravox', 'Open {name}', { name: file.name })"
          @click="openFile(file)"
          @keydown.enter="openFile(file)"
        >
          <FileTypeIcon :mime="file.mime" />
          <div class="fs-row-main">
            <div class="fs-row-name">
                {{ file.name }}
                <CloudOutline
                  v-if="file.is_federated"
                  :size="13"
                  class="fs-federated-badge"
                  role="img"
                  :aria-label="t('intravox', 'From federated share — MetaVox metadata not available')"
                  :title="t('intravox', 'From federated share — MetaVox metadata not available')"
                />
              </div>
            <div class="fs-row-meta">
              <span v-if="showColumn('date')">{{ formatLongDateTime(file.mtime) }}</span>
              <span v-if="showColumn('size')">{{ formatSize(file.size) }}</span>
              <span v-if="showColumn('path')">{{ file.path }}</span>
            </div>
          </div>
        </li>
      </ul>
    </div>

    <!-- Grouped mode -->
    <div v-else-if="effectiveMode === 'grouped'" class="fs-grouped">
      <section
        v-for="group in groups"
        :key="group.key"
        class="fs-group"
      >
        <header class="fs-group-header">
          <h4 class="fs-group-label">{{ group.label }}</h4>
          <span class="fs-group-count">{{ group.count }}</span>
        </header>
        <ul class="fs-list">
          <li
            v-for="file in group.files"
            :key="file.file_id"
            class="fs-row"
            tabindex="0"
            role="button"
            :aria-label="t('intravox', 'Open {name}', { name: file.name })"
            @click="openFile(file)"
            @keydown.enter="openFile(file)"
            @keydown.space.prevent="openFile(file)"
          >
            <FileTypeIcon :mime="file.mime" />
            <div class="fs-row-main">
              <div class="fs-row-name">
                {{ file.name }}
                <CloudOutline
                  v-if="file.is_federated"
                  :size="13"
                  class="fs-federated-badge"
                  role="img"
                  :aria-label="t('intravox', 'From federated share — MetaVox metadata not available')"
                  :title="t('intravox', 'From federated share — MetaVox metadata not available')"
                />
              </div>
              <div class="fs-row-meta">
                <span>{{ formatTime(file.mtime) }}</span>
                <span>{{ formatSize(file.size) }}</span>
              </div>
            </div>
          </li>
        </ul>
      </section>
    </div>

    <!-- Infinite-scroll sentinel -->
    <div
      v-if="pagination.hasMore"
      ref="scrollSentinel"
      class="fs-scroll-sentinel"
    >
      <span v-if="loadingMore" class="fs-loading-more">{{ t('intravox', 'Loading more …') }}</span>
    </div>

    <!-- Truncated notice -->
    <div v-if="pagination.truncated" class="fs-truncated">
      {{ t('intravox', 'Showing first {n} documents. Use filters or a more specific folder.', { n: String(pagination.total) }) }}
    </div>
  </div>
</template>

<script>
import { defineAsyncComponent, h } from 'vue';
import axios from '@nextcloud/axios';
import { generateUrl } from '@nextcloud/router';
import { translate, getCanonicalLocale, translatePlural } from '@nextcloud/l10n';
import { NcButton } from '@nextcloud/vue';
import AlertCircle from 'vue-material-design-icons/AlertCircle.vue';
import FileDocumentOutline from 'vue-material-design-icons/FileDocumentOutline.vue';
import CloudOutline from 'vue-material-design-icons/CloudOutline.vue';
import LockOutline from 'vue-material-design-icons/LockOutline.vue';

// FileTypeIcon renders a small mime-icon. Uses NC's `OC.MimeType.getIconUrl()`
// when available (returns a path to NC's built-in icon SVG), falls back to a
// neutral document icon otherwise.
const FileTypeIcon = {
  name: 'FileTypeIcon',
  props: { mime: { type: String, default: '' } },
  setup(props) {
    return () => {
      let iconUrl = null;
      try {
        if (typeof window !== 'undefined' && window.OC && window.OC.MimeType && typeof window.OC.MimeType.getIconUrl === 'function') {
          iconUrl = window.OC.MimeType.getIconUrl(props.mime || 'application/octet-stream');
        }
      } catch (e) { /* ignore */ }
      if (iconUrl) {
        return h('img', { src: iconUrl, class: 'fs-row-icon', alt: '', loading: 'lazy', width: 32, height: 32 });
      }
      // Fallback: plain neutral document icon
      return h('span', { class: 'fs-row-icon fs-row-icon--placeholder' }, '📄');
    };
  },
};

export default {
  name: 'FileStoryWidget',
  components: { AlertCircle, FileDocumentOutline, CloudOutline, LockOutline, FileTypeIcon, NcButton },
  props: {
    widget: { type: Object, required: true },
    rowBackgroundColor: { type: String, default: '' },
  },
  data() {
    return {
      files: [],
      timeline: [],
      groups: [],
      capabilities: null,
      loading: true,
      error: null,
      // Set to 'folder_not_accessible' when the backend returns a 404 with
      // that reason — used by emptyMessage()/showScanHint() to surface a
      // permission-aware empty-state instead of the generic "no documents".
      accessReason: null,
      pagination: { offset: 0, pageSize: 100, total: 0, hasMore: false, truncated: false },
      loadingMore: false,
      _scrollObserver: null,
      _abortController: null,
      _lastEtag: null,
      // Map file_id → true when its NC preview-thumbnail 404'd. Used in tiles
      // mode to fall back to a mime-icon without flashing a broken image.
      tilePreviewErrors: {},
    };
  },
  computed: {
    config() { return this.widget.config || {}; },
    rowBgClass() {
      const bg = String(this.rowBackgroundColor || '').trim();
      if (!bg || bg === 'transparent') {
        return '';
      }
      const dark = new Set([
        'var(--color-primary-element)',
        'var(--color-primary)',
        'var(--color-error)',
        'var(--color-success)',
        'var(--color-warning)',
      ]);
      if (dark.has(bg)) {
        return 'fs--on-dark';
      }
      return 'fs--on-tinted';
    },
    rowBgStyle() {
      const bg = String(this.rowBackgroundColor || '').trim();
      if (this.rowBgClass !== 'fs--on-dark') {
        return {};
      }
      // Paired text color for the row background, matches Widget.vue's WCAG map.
      const textMap = {
        'var(--color-primary-element)': 'var(--color-primary-element-text)',
        'var(--color-primary)': 'var(--color-primary-text)',
        'var(--color-error)': 'var(--color-error-text)',
        'var(--color-success)': 'var(--color-success-text)',
        'var(--color-warning)': 'var(--color-warning-text)',
      };
      const text = textMap[bg] || '#fff';
      return {
        '--fs-text': text,
        '--fs-text-muted': text,
      };
    },
    effectiveMode() {
      const allowed = ['timeline', 'tiles', 'list', 'grouped'];
      return allowed.includes(this.config.mode) ? this.config.mode : 'timeline';
    },
    timelineDays() {
      return this.timeline || [];
    },
    /**
     * Context-aware empty-state message. Distinguishes "folder not set yet"
     * (user just dropped in the widget) from "filter matches nothing" (user
     * tuned filters too narrow) from generic "folder is empty".
     */
    emptyMessage() {
      if (!this.config.folderPath) {
        return t('intravox', 'No folder selected');
      }
      if (this.accessReason === 'folder_not_accessible') {
        return t('intravox', 'You do not have access to this folder');
      }
      const hasFilters = Array.isArray(this.config.metaVoxFilters) && this.config.metaVoxFilters.length > 0;
      if (hasFilters) {
        return t('intravox', 'No documents match the current filters');
      }
      return t('intravox', 'No documents to show.');
    },
    /**
     * Only show the file-index hint when the user picked a normal folder and
     * got zero results — not when filters are active (more likely cause: no
     * match) and not when the user lacks access (occ files:scan won't help).
     */
    showScanHint() {
      if (!this.config.folderPath) return false;
      if (this.accessReason === 'folder_not_accessible') return false;
      const hasFilters = Array.isArray(this.config.metaVoxFilters) && this.config.metaVoxFilters.length > 0;
      return !hasFilters;
    },
    fetchKey() {
      const c = this.config || {};
      return JSON.stringify({
        f: c.folderPath || '',
        m: c.mode || 'timeline',
        gb: c.groupBy || '',
        gr: c.granularity || 'day',
        l: c.limit ?? null,
        s: c.sortOrder || 'desc',
        sb: c.sortBy || 'mtime',
        flt: Array.isArray(c.metaVoxFilters) ? c.metaVoxFilters : [],
      });
    },
    /**
     * CSS custom properties that drive the tile grid sizing. Discrete steps
     * because pixel-precise control is rarely useful and a 3-button picker
     * keeps the editor UI compact.
     */
    tilesGridStyle() {
      const size = this.config.tileSize || 'medium';
      switch (size) {
        case 'small':
          return { '--fs-tile-min': '120px', '--fs-tile-gap': '10px' };
        case 'large':
          return { '--fs-tile-min': '260px', '--fs-tile-gap': '18px' };
        case 'medium':
        default:
          return { '--fs-tile-min': '180px', '--fs-tile-gap': '14px' };
      }
    },
  },
  watch: {
    fetchKey() {
      // 700 ms debounce so quick edit-mode mutations (folder pick + mode flip
      // + sort change in rapid succession) don't stack four heavy filecache
      // queries on the Apache worker pool and cause 503s on the subsequent
      // page save. Each in-flight fetch holds a worker until its SQL returns.
      clearTimeout(this._debounce);
      this._debounce = setTimeout(() => this.fetch(), 700);
    },
  },
  mounted() {
    if (typeof requestIdleCallback === 'function') {
      this._idleHandle = requestIdleCallback(() => this.fetch());
    } else {
      this.fetch();
    }
  },
  beforeUnmount() {
    clearTimeout(this._debounce);
    if (this._idleHandle && typeof cancelIdleCallback === 'function') {
      cancelIdleCallback(this._idleHandle);
    }
    if (this._scrollObserver) {
      this._scrollObserver.disconnect();
      this._scrollObserver = null;
    }
    if (this._abortController) {
      this._abortController.abort();
      this._abortController = null;
    }
  },
  methods: {
    t(app, text, vars) {
      return translate(app, text, vars);
    },
    async fetch() {
      if (!this.config.folderPath) {
        this.files = [];
        this.timeline = [];
        this.groups = [];
        this.loading = false;
        return;
      }
      // Cancel any pending fetch/fetchMore so we don't race a stale response
      // overwriting fresh data after a rapid config change.
      if (this._abortController) {
        this._abortController.abort();
      }
      this._abortController = new AbortController();
      const signal = this._abortController.signal;

      this.pagination = { offset: 0, pageSize: 100, total: 0, hasMore: false, truncated: false };
      this.loading = true;
      this.error = null;
      this.accessReason = null;
      try {
        const params = this.buildParams(0);
        const url = generateUrl(`/apps/intravox/api/file-story/files?${params.toString()}`);
        const headers = {};
        if (this._lastEtag) headers['If-None-Match'] = this._lastEtag;
        const res = await axios.get(url, { headers, signal, validateStatus: s => (s >= 200 && s < 300) || s === 304 || s === 404 });
        if (res.status === 304) {
          this.loading = false;
          return;
        }
        if (res.status === 404) {
          this.files = [];
          this.timeline = [];
          this.groups = [];
          this.capabilities = null;
          this.accessReason = res.data?.reason || null;
          this.loading = false;
          return;
        }
        const d = res.data || {};
        this.files = d.files || [];
        this.timeline = d.timeline || [];
        this.groups = d.groups || [];
        this.capabilities = d.capabilities || null;
        this.warmFederatedPreviews(this.files);
        if (d.pagination) {
          this.pagination = {
            offset: d.pagination.offset || 0,
            pageSize: d.pagination.pageSize || 100,
            total: d.pagination.total || this.files.length,
            hasMore: !!d.pagination.hasMore,
            truncated: !!d.pagination.truncated,
          };
        }
        if (res.headers && res.headers.etag) this._lastEtag = res.headers.etag;
        this.$nextTick(() => this.setupScrollSentinel());
      } catch (err) {
        if (axios.isCancel?.(err) || err?.name === 'CanceledError' || err?.code === 'ERR_CANCELED') {
          return; // superseded by newer request
        }
        console.error('[FileStoryWidget] fetch failed:', err);
        this.error = err.response?.data?.error || err.message || this.t('intravox', 'Failed to load documents');
      } finally {
        this.loading = false;
      }
    },
    buildParams(offset) {
      const params = new URLSearchParams({ mode: this.effectiveMode });
      const c = this.config || {};
      if (c.folderPath) params.append('folder', c.folderPath);
      if (Array.isArray(c.metaVoxFilters) && c.metaVoxFilters.length) {
        params.append('filters', JSON.stringify(c.metaVoxFilters));
      }
      if (this.effectiveMode === 'grouped' && c.groupBy) {
        params.append('groupBy', String(c.groupBy));
      }
      if (this.effectiveMode === 'timeline' && c.granularity) {
        params.append('granularity', String(c.granularity));
      }
      if (c.limit) params.append('limit', String(c.limit));
      params.append('offset', String(offset));
      params.append('pageSize', String(this.pagination.pageSize || 100));
      params.append('sortOrder', c.sortOrder || 'desc');
      params.append('sortBy', c.sortBy || 'mtime');
      if (offset > 0 && this.pagination.total > 0) {
        params.append('total', String(this.pagination.total));
      }
      return params;
    },
    async fetchMore() {
      if (this.loadingMore || !this.pagination.hasMore) return;
      this.loadingMore = true;
      const signal = this._abortController?.signal;
      try {
        const nextOffset = this.pagination.offset + this.pagination.pageSize;
        const params = this.buildParams(nextOffset);
        const url = generateUrl(`/apps/intravox/api/file-story/files?${params.toString()}`);
        const res = await axios.get(url, { signal, validateStatus: s => s >= 200 && s < 300 });
        const d = res.data || {};
        const newFiles = d.files || [];
        this.files = this.files.concat(newFiles);
        this.warmFederatedPreviews(newFiles);

        // Merge incoming timeline-days + re-sort by date so bucket order is
        // chronologically consistent regardless of pagination boundaries.
        if (Array.isArray(d.timeline) && d.timeline.length) {
          const byDate = new Map();
          for (const day of this.timeline) byDate.set(day.date, day);
          for (const day of d.timeline) {
            if (byDate.has(day.date)) {
              const existing = byDate.get(day.date);
              existing.photos = existing.photos.concat(day.photos || []);
            } else {
              byDate.set(day.date, day);
            }
          }
          const ord = this.config.sortOrder || 'desc';
          this.timeline = Array.from(byDate.values()).sort((a, b) => ord === 'desc'
            ? b.date.localeCompare(a.date)
            : a.date.localeCompare(b.date));
        }

        if (d.pagination) {
          this.pagination = {
            offset: d.pagination.offset || nextOffset,
            pageSize: d.pagination.pageSize || this.pagination.pageSize,
            total: d.pagination.total || this.pagination.total,
            hasMore: !!d.pagination.hasMore,
            truncated: !!d.pagination.truncated,
          };
        }
      } catch (err) {
        if (axios.isCancel?.(err) || err?.name === 'CanceledError' || err?.code === 'ERR_CANCELED') {
          return;
        }
        console.error('[FileStoryWidget] fetchMore failed:', err);
      } finally {
        this.loadingMore = false;
      }
    },
    setupScrollSentinel() {
      if (this._scrollObserver) {
        this._scrollObserver.disconnect();
        this._scrollObserver = null;
      }
      if (!this.pagination.hasMore || typeof IntersectionObserver === 'undefined') return;
      const sentinel = this.$refs.scrollSentinel;
      if (!sentinel) return;
      this._scrollObserver = new IntersectionObserver((entries) => {
        for (const e of entries) {
          if (e.isIntersecting) {
            this.fetchMore().then(() => {
              if (!this.pagination.hasMore && this._scrollObserver) {
                this._scrollObserver.disconnect();
                this._scrollObserver = null;
              }
            });
            break;
          }
        }
      }, { rootMargin: '600px' });
      this._scrollObserver.observe(sentinel);
    },
    openFile(file) {
      // Always open in a new tab via NC's /f/<id> handler — it resolves the
      // right mount per-user (personal storage, GroupFolders in either legacy
      // shared-storage or per-folder jail mode, federated shares).
      const url = generateUrl(`/f/${file.file_id}`);
      window.open(url, '_blank', 'noopener');
    },
    formatLongDate(dateStr) {
      if (!dateStr) return '';
      const d = new Date(dateStr);
      if (Number.isNaN(d.getTime())) return dateStr;
      return d.toLocaleDateString(getCanonicalLocale(), {
        weekday: 'long',
        day: 'numeric',
        month: 'long',
        year: 'numeric',
      });
    },
    formatLongDateTime(mtimeSec) {
      if (!mtimeSec) return '';
      const d = new Date(mtimeSec * 1000);
      try {
        return d.toLocaleString(getCanonicalLocale(), { day: 'numeric', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' });
      } catch (e) { return d.toISOString(); }
    },
    formatTime(mtimeSec) {
      if (!mtimeSec) return '';
      const d = new Date(mtimeSec * 1000);
      try {
        return d.toLocaleTimeString(getCanonicalLocale(), { hour: '2-digit', minute: '2-digit' });
      } catch (e) { return ''; }
    },
    formatSize(bytes) {
      if (!bytes) return '';
      const units = ['B', 'KB', 'MB', 'GB'];
      let i = 0;
      let v = Number(bytes);
      while (v >= 1024 && i < units.length - 1) { v /= 1024; i++; }
      return `${v.toFixed(v >= 10 || i === 0 ? 0 : 1)} ${units[i]}`;
    },
    /**
     * Fire-and-forget warmup of the federated-preview cache. Called after
     * each fetch/fetchMore so the IntraVox proxy can pre-fetch thumbnails
     * from the owning NC instance before the user scrolls to each tile.
     * Caps + dedup live server-side; we just hand over the fileIds.
     */
    warmFederatedPreviews(files) {
      try {
        if (!Array.isArray(files) || files.length === 0) return;
        const fids = files
          .filter(f => f && f.is_federated && f.file_id)
          .map(f => f.file_id);
        if (fids.length === 0) return;
        const url = generateUrl('/apps/intravox/api/preview/warmup');
        axios.post(url, { file_ids: fids }).catch(() => {});
      } catch (_) { /* never block render on prewarm */ }
    },
    /**
     * Build the URL for the preview-thumbnail endpoint. Local files go
     * straight to NC's `/core/preview` (fast, hits NC's own preview cache).
     * Federated files route through IntraVox's proxy because NC's core
     * preview pipeline can't reach the remote storage on its own.
     */
    previewUrl(file, size = 400) {
      if (file && file.is_federated) {
        return generateUrl(`/apps/intravox/api/preview?file_id=${file.file_id}&x=${size}&y=${size}`);
      }
      const fid = (file && typeof file === 'object') ? file.file_id : file;
      return generateUrl(`/core/preview?fileId=${fid}&x=${size}&y=${size}&a=true`);
    },
    markPreviewError(fileId) {
      // Vue 3 reactivity tracks property additions on a plain {} reliably
      // when the parent object is itself reactive, but a direct
      // `this.tilePreviewErrors[id] = true` can race with the v-for loop
      // re-evaluating before the mutation propagates. Reassign the whole
      // object so the v-if condition re-evaluates deterministically.
      this.tilePreviewErrors = { ...this.tilePreviewErrors, [fileId]: true };
    },
    /**
     * Uppercase file extension for placeholder rendering when no preview is
     * available. "Contract Q4.pdf" → "PDF". Empty for files without extension.
     */
    fileExt(name) {
      if (!name || typeof name !== 'string') return '';
      const dot = name.lastIndexOf('.');
      if (dot <= 0 || dot === name.length - 1) return '';
      return name.slice(dot + 1).toUpperCase().slice(0, 5);
    },
    /**
     * Whether a given column-key is enabled in the widget config. Default:
     * date is shown, other columns are opt-in.
     */
    showColumn(key) {
      const cols = this.config.visibleColumns;
      if (!Array.isArray(cols)) {
        return key === 'date';
      }
      return cols.includes(key);
    },
    /**
     * Pick the right timestamp for row display based on user preference.
     * Falls back gracefully when the preferred timestamp isn't on the row.
     */
    rowDate(file) {
      const pref = this.config.dateField || 'mtime';
      if (pref === 'created' && file.created_at) return file.created_at;
      if (pref === 'taken_at' && file.taken_at) return file.taken_at;
      // mtime is unix seconds; the formatter accepts both ISO strings and
      // unix-seconds via timestamp branching, so cast to a Date-friendly form.
      if (file.mtime) {
        return new Date(file.mtime * 1000).toISOString().slice(0, 10);
      }
      return null;
    },
  },
};
</script>

<style scoped>
.file-story-widget {
  width: 100%;
  margin: 12px 0;
}

.fs-loading, .fs-error, .fs-empty {
  display: flex;
  align-items: center;
  justify-content: center;
  flex-direction: column;
  gap: 8px;
  padding: 32px 16px;
  color: var(--fs-text-muted, var(--color-text-maxcontrast));
  font-size: 13px;
  background: var(--color-background-hover);
  border-radius: var(--border-radius-large);
}

.fs-empty-hint {
  display: block;
  max-width: 480px;
  margin-top: 8px;
  line-height: 1.4;
  text-align: center;
}

.fs-skeleton-row {
  width: 100%;
  height: 48px;
  background: var(--color-background-dark);
  border-radius: var(--border-radius);
  margin-bottom: 4px;
  animation: fs-pulse 1.4s ease-in-out infinite;
}

@keyframes fs-pulse {
  0%, 100% { opacity: 0.55; }
  50% { opacity: 0.85; }
}

.fs-day, .fs-group {
  margin-bottom: 16px;
}

/* Transparent date headers — inherit the row/widget background instead of
 * forcing a white block over a colored row. The hairline border separates
 * sections visually without an opaque band. */
.fs-day-header, .fs-group-header {
  display: flex;
  align-items: baseline;
  gap: 10px;
  padding: 10px 4px 4px;
  margin-top: 4px;
  border-bottom: 1px solid var(--color-border);
  background: transparent;
}

.fs-day-date, .fs-group-label {
  margin: 0;
  font-size: 14px;
  font-weight: 600;
  color: var(--fs-text, var(--color-main-text));
  /* Slightly muted weight in the header so it doesn't compete with filenames
   * but stays readable in both light and dark themes via --color-main-text. */
  letter-spacing: 0.01em;
}

.fs-day-count, .fs-group-count {
  font-size: 11px;
  color: var(--fs-text-muted, var(--color-text-maxcontrast));
  /* color-mix tint of the accent color — adapts to theming, no fixed values.
   * Falls back to background-hover if color-mix is not supported (NC32+ targets
   * Chromium 111+ / Firefox 113+ / Safari 16.2+ which all support it). */
  background: color-mix(in srgb, var(--color-primary-element) 14%, transparent);
  padding: 2px 8px;
  border-radius: 999px;
}

.fs-list {
  list-style: none;
  margin: 0;
  padding: 0;
}

.fs-row {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 8px 8px;
  border-radius: var(--border-radius);
  cursor: pointer;
  transition: background 0.12s ease;
}

.fs-row:hover, .fs-row:focus {
  background: var(--color-background-hover);
  outline: none;
}

:deep(.fs-row-icon) {
  width: 32px;
  height: 32px;
  flex-shrink: 0;
  border-radius: 4px;
}

:deep(.fs-row-icon--placeholder) {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  font-size: 22px;
  background: var(--color-background-dark);
}

.fs-row-main {
  flex: 1;
  min-width: 0;
}

.fs-row-name {
  font-size: 14px;
  color: var(--fs-text, var(--color-main-text));
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  display: flex;
  align-items: center;
  gap: 6px;
}

/* Subtle cloud-badge next to filenames that live in federated shares.
   Communicates "this came from another Nextcloud — MetaVox metadata not
   reachable" without breaking the file-rendering layout. */
:deep(.fs-federated-badge) {
  display: inline-flex;
  align-items: center;
  flex-shrink: 0;
  color: var(--fs-text-muted, var(--color-text-maxcontrast));
  opacity: 0.7;
}

:deep(.fs-federated-badge:hover) {
  opacity: 1;
}

.fs-row-meta {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
  font-size: 11px;
  color: var(--fs-text-muted, var(--color-text-maxcontrast));
  margin-top: 2px;
}

/* Tiles mode: grid of preview thumbnails. Responsive via auto-fill so tile
   counts adjust to the column width — same masonry feel as a photo gallery
   but with document covers instead of photos. */
.fs-tiles {
  display: grid;
  /* Driven by tilesGridStyle() — falls back to medium when not set. */
  grid-template-columns: repeat(auto-fill, minmax(var(--fs-tile-min, 180px), 1fr));
  gap: var(--fs-tile-gap, 14px);
}

.fs-tile {
  display: flex;
  flex-direction: column;
  border: 1px solid var(--color-border);
  border-radius: var(--border-radius-large);
  background: var(--color-main-background);
  cursor: pointer;
  overflow: hidden;
  transition: box-shadow 0.12s ease, transform 0.12s ease;
}

.fs-tile:hover, .fs-tile:focus {
  box-shadow: 0 4px 14px rgba(0, 0, 0, 0.12);
  transform: translateY(-1px);
  outline: none;
}

.fs-tile-preview {
  position: relative;
  width: 100%;
  aspect-ratio: 3 / 4;          /* A4-ish portrait — most docs */
  background: var(--color-background-dark);
  overflow: hidden;
}

/* Img sits on top of the fallback; when NC returns 404 the img is unloaded
   and the fallback (mime-icon + extension) shows through. */
.fs-tile-img {
  position: absolute;
  inset: 0;
  width: 100%;
  height: 100%;
  object-fit: cover;
  display: block;
  z-index: 1;
}

.fs-tile-fallback {
  position: absolute;
  inset: 0;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 8px;
  color: var(--fs-text-muted, var(--color-text-maxcontrast));
  z-index: 0;
}

.fs-tile-ext {
  font-size: 12px;
  font-weight: 600;
  letter-spacing: 0.05em;
}

.fs-tile-body {
  padding: 8px 10px 10px;
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.fs-tile-name {
  font-size: 13px;
  font-weight: 500;
  color: var(--fs-text, var(--color-main-text));
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  display: flex;
  align-items: center;
  gap: 6px;
}

.fs-tile-meta {
  display: flex;
  flex-wrap: wrap;
  gap: 6px;
  font-size: 11px;
  color: var(--fs-text-muted, var(--color-text-maxcontrast));
}

.fs-scroll-sentinel {
  display: flex;
  justify-content: center;
  align-items: center;
  min-height: 48px;
  padding: 12px 0;
}

.fs-loading-more {
  font-size: 12px;
  color: var(--fs-text-muted, var(--color-text-maxcontrast));
}

.fs-truncated {
  margin: 12px 0;
  padding: 8px 12px;
  background: var(--color-warning-background);
  border: 1px solid var(--color-warning);
  border-radius: 6px;
  font-size: 12px;
  color: var(--fs-text-muted, var(--color-text-maxcontrast));
}

@media (prefers-reduced-motion: reduce) {
  .fs-skeleton-row {
    animation: none;
  }
}

/* When the row background is dark (e.g. --color-primary-element), the widget
   sits on a saturated surface. Lift muted text, soften hover/border, and keep
   skeletons/tile-cards readable against the colored backdrop. */
.file-story-widget.fs--on-dark .fs-day-count,
.file-story-widget.fs--on-dark .fs-group-count {
  background: rgba(255, 255, 255, 0.18);
  color: var(--fs-text, #fff);
}

.file-story-widget.fs--on-dark .fs-row:hover,
.file-story-widget.fs--on-dark .fs-row:focus {
  background: rgba(255, 255, 255, 0.12);
}

.file-story-widget.fs--on-dark .fs-day-header,
.file-story-widget.fs--on-dark .fs-group-header {
  border-bottom-color: rgba(255, 255, 255, 0.25);
}

.file-story-widget.fs--on-dark .fs-row-meta,
.file-story-widget.fs--on-dark :deep(.fs-federated-badge) {
  opacity: 0.82;
}

.file-story-widget.fs--on-dark .fs-skeleton-row {
  background: rgba(255, 255, 255, 0.12);
}

/* Tiles on a dark row: tinted-glass card so the white tile stops "cutting a
   hole" in the colored background. Filename stays white (--fs-text), reads on
   the translucent backdrop because there's enough contrast against both the
   row-bg and the soft white tint. */
.file-story-widget.fs--on-dark .fs-tile {
  background: rgba(255, 255, 255, 0.14);
  border-color: rgba(255, 255, 255, 0.22);
}

.file-story-widget.fs--on-dark .fs-tile:hover,
.file-story-widget.fs--on-dark .fs-tile:focus {
  background: rgba(255, 255, 255, 0.2);
  box-shadow: 0 4px 14px rgba(0, 0, 0, 0.25);
}

.file-story-widget.fs--on-dark .fs-tile-preview {
  background: rgba(255, 255, 255, 0.08);
}

/* Mime-icon in the placeholder uses currentColor for the SVG paths via NC's
   icon system — lifting opacity is enough to keep the icon visible on the
   tinted tile surface without a stark grey square. */
.file-story-widget.fs--on-dark :deep(.fs-row-icon--placeholder),
.file-story-widget.fs--on-dark .fs-tile-fallback {
  background: transparent;
  color: var(--fs-text, #fff);
  opacity: 0.88;
}
</style>
