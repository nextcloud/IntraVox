<template>
  <div class="photo-story-widget" :class="rowBgClass" :style="rowBgStyle">
    <!-- Optional map at top -->
    <PhotoStoryMap
      v-if="config.showMap && config.folderPath && capabilities && capabilities.hasLocation && mapSettings.enabled"
      :folder-path="config.folderPath"
      class="ps-map-embed"
      @cluster-click="onClusterClick"
    />

    <!-- Loading skeleton -->
    <div v-if="loading" class="ps-loading" role="status" aria-live="polite" :aria-label="t('Loading photos')">
      <div v-for="i in 6" :key="i" class="ps-skeleton-tile" aria-hidden="true"></div>
    </div>

    <!-- Error -->
    <div v-else-if="error" class="ps-error" role="alert">
      <AlertCircle :size="24" aria-hidden="true" />
      <span>{{ error }}</span>
      <NcButton type="secondary" @click="fetch()">
        {{ t('Retry') }}
      </NcButton>
    </div>

    <!-- Empty -->
    <div v-else-if="!photos.length" class="ps-empty" role="status">
      <ImageMultiple :size="32" aria-hidden="true" />
      <p>{{ emptyMessage }}</p>
      <small v-if="config.folderPath">{{ config.folderPath }}</small>
      <small v-if="showScanHint" class="ps-empty-hint">
        {{ t('If you can see photos in the Files app but not here, the file index may be out of sync. Ask an admin to run "occ files:scan" for this folder.') }}
      </small>
    </div>

    <!-- Timeline mode — Magazine style -->
    <div
      v-else-if="effectiveMode === 'timeline' && effectiveStyle === 'magazine'"
      class="ps-timeline ps-timeline--magazine"
    >
      <section v-for="day in timelineDays" :key="day.date" :data-date="day.date" class="ps-day-mag">
        <header class="ps-day-mag-header">
          <h2 class="ps-day-mag-date">{{ formatLongDate(day.date) }}</h2>
          <p v-if="day.location_summary" class="ps-day-mag-loc">{{ day.location_summary }}</p>
        </header>
        <PhotoStoryDayMap
          v-if="showDayMaps() && day._hasGps && mapSettings.enabled"
          :tile-url="mapSettings.tile_url"
          :attribution="mapSettings.attribution"
          :max-zoom="mapSettings.max_zoom"
          :points="day._gpsPoints"
          :height="180"
          class="ps-day-mag-map"
          @photo-click="openLightboxByFileId($event)"
        />
        <div class="ps-mag-grid">
          <figure
            v-for="(photo, idx) in day.photos"
            :key="photo.file_id"
            class="ps-mag-tile"
            :style="magazineTileStyle(photo)"
            tabindex="0"
            role="button"
            :aria-label="t('Open photo {name}', { name: photo.name })"
            @click="openLightbox(globalIndex(day.photos, idx))"
            @keydown.enter="openLightbox(globalIndex(day.photos, idx))"
            @keydown.space.prevent="openLightbox(globalIndex(day.photos, idx))"
          >
            <img
              :src="previewUrl(photo, 800)"
              :alt="photo.location_display || photo.location || photo.name"
              loading="lazy"
              decoding="async"
              class="ps-mag-img"
            />
          </figure>
        </div>
      </section>
    </div>

    <!-- Timeline mode — Apple style (default) -->
    <div
      v-else-if="effectiveMode === 'timeline' && effectiveStyle === 'apple'"
      class="ps-timeline ps-timeline--apple"
    >
      <section v-for="day in timelineDays" :key="day.date" :data-date="day.date" class="ps-day-apple">
        <header class="ps-day-apple-header">
          <h3 class="ps-day-apple-date">{{ formatLongDate(day.date) }}</h3>
          <span v-if="day.location_summary" class="ps-day-apple-loc">
            <MapMarker :size="14" />
            {{ day.location_summary }}
          </span>
        </header>
        <PhotoStoryDayMap
          v-if="showDayMaps() && day._hasGps && mapSettings.enabled"
          :tile-url="mapSettings.tile_url"
          :attribution="mapSettings.attribution"
          :max-zoom="mapSettings.max_zoom"
          :points="day._gpsPoints"
          :height="140"
          class="ps-day-apple-map"
          @photo-click="openLightboxByFileId($event)"
        />
        <div class="ps-apple-grid">
          <PhotoTile
            v-for="(photo, idx) in day.photos"
            :key="photo.file_id"
            :photo="photo"
            :show-caption="false"
            @click="openLightbox(globalIndex(day.photos, idx))"
          />
        </div>
      </section>
    </div>

    <!-- Timeline mode — Travelogue style (Polarsteps-like rail) -->
    <div
      v-else-if="effectiveMode === 'timeline' && effectiveStyle === 'travelogue'"
      class="ps-timeline ps-timeline--travelogue"
    >
      <section v-for="day in timelineDays" :key="day.date" :data-date="day.date" class="ps-day-trav">
        <div class="ps-trav-rail">
          <div class="ps-trav-bullet"></div>
        </div>
        <div class="ps-trav-content">
          <header class="ps-day-trav-header">
            <h3 class="ps-day-trav-date">{{ formatLongDate(day.date) }}</h3>
            <p v-if="day.location_summary" class="ps-day-trav-loc">
              <MapMarker :size="14" />
              {{ day.location_summary }}
            </p>
          </header>
          <PhotoStoryDayMap
            v-if="showDayMaps() && day._hasGps && mapSettings.enabled"
            :tile-url="mapSettings.tile_url"
            :attribution="mapSettings.attribution"
            :max-zoom="mapSettings.max_zoom"
            :points="day._gpsPoints"
            :height="150"
            class="ps-day-trav-map"
            @photo-click="openLightboxByFileId($event)"
          />
          <div class="ps-trav-grid">
            <PhotoTile
              v-for="(photo, idx) in day.photos"
              :key="photo.file_id"
              :photo="photo"
              :show-caption="false"
              @click="openLightbox(globalIndex(day.photos, idx))"
            />
          </div>
        </div>
      </section>
    </div>

    <!-- Highlights mode -->
    <div v-else-if="effectiveMode === 'highlights'" class="ps-highlights">
      <div
        v-if="highlights.length"
        class="ps-highlight-hero"
        tabindex="0"
        role="button"
        :aria-label="t('Open photo {name}', { name: highlights[0].location || highlights[0].name })"
        @click="openLightbox(0)"
        @keydown.enter="openLightbox(0)"
        @keydown.space.prevent="openLightbox(0)"
      >
        <img
          :src="previewUrl(highlights[0], 1600)"
          :alt="highlights[0].location_display || highlights[0].location || highlights[0].name"
          loading="lazy"
          decoding="async"
          class="ps-hero-img"
        />
        <div v-if="config.showCaptions !== false" class="ps-hero-caption">
          <strong>{{ highlights[0].location || highlights[0].name }}</strong>
          <span v-if="highlights[0].taken_at">{{ formatDate(highlights[0].taken_at) }}</span>
        </div>
      </div>
      <div class="ps-grid ps-grid--two">
        <PhotoTile
          v-for="(photo, idx) in highlights.slice(1)"
          :key="photo.file_id"
          :photo="photo"
          :show-caption="config.showCaptions !== false"
          @click="openLightbox(idx + 1)"
        />
      </div>
    </div>

    <!-- Grid mode -->
    <div
      v-else-if="effectiveMode === 'grid'"
      class="ps-grid ps-grid--masonry"
      :style="gridStyle"
    >
      <PhotoTile
        v-for="(photo, idx) in photos"
        :key="photo.file_id"
        :photo="photo"
        :show-caption="config.showCaptions !== false"
        :style="masonryItemStyle(photo)"
        @click="openLightbox(idx)"
      />
    </div>

    <!-- On this day mode -->
    <div v-else-if="effectiveMode === 'on-this-day'" class="ps-on-this-day">
      <section
        v-for="(group, year) in onThisDayGroups"
        :key="year"
        class="ps-year"
      >
        <header class="ps-day-header">
          <h4 class="ps-day-label">{{ year }}</h4>
        </header>
        <div class="ps-grid ps-grid--responsive">
          <PhotoTile
            v-for="photo in group"
            :key="photo.file_id"
            :photo="photo"
            :show-caption="config.showCaptions !== false"
            @click="openLightbox(photos.indexOf(photo))"
          />
        </div>
      </section>
    </div>

    <!-- Year-jump scrubber (Timeline-only, only if >1 year) -->
    <aside
      v-if="effectiveMode === 'timeline' && yearScrubber.length > 1"
      class="ps-year-scrubber"
      :aria-label="t('Jump to year')"
    >
      <button
        v-for="y in yearScrubber"
        :key="y"
        type="button"
        class="ps-year-btn"
        :title="t('Jump to {year}', { year: String(y) })"
        @click="scrollToYear(y)"
      >
        {{ y }}
      </button>
    </aside>

    <!-- Infinite-scroll sentinel + load-more indicator (folder-mode timeline/grid only) -->
    <div
      v-if="isPagedMode() && pagination.hasMore"
      ref="scrollSentinel"
      class="ps-scroll-sentinel"
      aria-hidden="true"
    >
      <span v-if="loadingMore" class="ps-loading-more">{{ t('Loading more photos…') }}</span>
    </div>

    <!-- Truncation notice when server hit its hard cap -->
    <div v-if="pagination.truncated" class="ps-truncated">
      {{ t('Showing first {n} photos. Use filters or a more specific folder to narrow the selection.', { n: String(pagination.total) }) }}
    </div>

    <!-- Lightbox -->
    <PhotoLightbox
      v-if="lightboxVisible"
      :photos="photos"
      :start-index="lightboxIndex"
      :visible="lightboxVisible"
      @close="lightboxVisible = false"
    />
  </div>
</template>

<script>
import { defineAsyncComponent } from 'vue';
import axios from '@nextcloud/axios';
import { generateUrl } from '@nextcloud/router';
import { translate as t, getCanonicalLocale } from '@nextcloud/l10n';
import { NcButton } from '@nextcloud/vue';
import AlertCircle from 'vue-material-design-icons/AlertCircle.vue';
import ImageMultiple from 'vue-material-design-icons/ImageMultiple.vue';
import MapMarker from 'vue-material-design-icons/MapMarker.vue';

import { h, ref } from 'vue';
const PhotoTile = {
  name: 'PhotoTile',
  props: {
    photo: { type: Object, required: true },
    showCaption: { type: Boolean, default: true },
  },
  emits: ['click'],
  setup(props, { emit }) {
    const hasError = ref(false);
    return () => {
      // Route federated photos through IntraVox's preview proxy; local
      // photos go straight to NC core preview to reuse NC's preview cache.
      const src = props.photo.is_federated
        ? generateUrl(`/apps/intravox/api/preview?file_id=${props.photo.file_id}&x=512&y=512`)
        : generateUrl(`/core/preview?fileId=${props.photo.file_id}&x=512&y=512&a=true`);
      const caption = props.photo.location_display || props.photo.location || '';
      const dateStr = props.photo.taken_at
        ? new Date(props.photo.taken_at).toLocaleDateString(getCanonicalLocale(), { day: 'numeric', month: 'short', year: 'numeric' })
        : '';

      const mime = String(props.photo.mime || '');
      const isVideo = mime.startsWith('video/')
        || /\.(mov|mp4|m4v|avi|mkv|webm|wmv|mpg|mpeg|3gp)$/i.test(props.photo.name || '');
      const isRawMime = mime.match(/x-(canon|nikon|sony|adobe|fuji|olympus|panasonic|pentax|samsung|sigma|dcraw)/i)
        || /\.(cr2|cr3|nef|arw|dng|raf|orf|rw2|pef|srw|x3f)$/i.test(props.photo.name || '');
      let formatLabel;
      if (isVideo) formatLabel = 'VIDEO';
      else if (isRawMime) formatLabel = 'RAW';
      else formatLabel = (props.photo.name || '').split('.').pop()?.toUpperCase() || '';

      const tileChildren = [];

      if (hasError.value) {
        // Fallback placeholder when NC has no preview (e.g. video without ffmpeg server-side)
        tileChildren.push(
          h('div', { class: 'ps-tile-placeholder' }, [
            h('div', { class: 'ps-tile-placeholder-badge' }, formatLabel),
            h('div', { class: 'ps-tile-placeholder-meta' }, [
              h('strong', null, props.photo.name),
              dateStr ? h('span', null, dateStr) : null,
              caption ? h('small', null, caption) : null,
            ].filter(Boolean)),
          ])
        );
      } else {
        // Meaningful alt: prefer location-based description over filename.
        // Decorative inside an already-labelled role=button parent would be
        // tempting but screen-readers may treat the figure label loosely;
        // keep a concise alt for image-only assistive contexts.
        const altText = caption || props.photo.location || props.photo.name || '';
        tileChildren.push(
          h('img', {
            src,
            alt: altText,
            loading: 'lazy',
            decoding: 'async',
            class: 'ps-tile-img',
            onError: () => { hasError.value = true; },
          })
        );
      }

      // Play-button overlay for video tiles (visible on both successful preview and placeholder)
      if (isVideo) {
        tileChildren.push(
          h('div', { class: 'ps-tile-video-badge', 'aria-hidden': 'true' }, [
            h('svg', { width: 22, height: 22, viewBox: '0 0 24 24', fill: 'currentColor' }, [
              h('path', { d: 'M8 5v14l11-7z' }),
            ]),
          ])
        );
      }

      if (props.showCaption && caption && !hasError.value) {
        tileChildren.push(h('figcaption', { class: 'ps-tile-caption' }, caption));
      }

      const accessibleLabel = caption || props.photo.name || 'photo';
      return h('figure', {
        class: ['ps-tile', { 'ps-tile--placeholder': hasError.value, 'ps-tile--video': isVideo }],
        tabindex: 0,
        role: 'button',
        'aria-label': accessibleLabel,
        onClick: () => emit('click'),
        onKeydown: (e) => {
          if (e.key === 'Enter' || e.key === ' ' || e.key === 'Spacebar') {
            e.preventDefault();
            emit('click');
          }
        },
      }, tileChildren);
    };
  },
};

export default {
  name: 'PhotoStoryWidget',
  components: {
    AlertCircle,
    ImageMultiple,
    NcButton,
    MapMarker,
    PhotoTile,
    PhotoLightbox: defineAsyncComponent(() => import('./PhotoLightbox.vue')),
    PhotoStoryMap: defineAsyncComponent(() => import('./PhotoStoryMap.vue')),
    PhotoStoryDayMap: defineAsyncComponent(() => import('./PhotoStoryDayMap.vue')),
  },
  props: {
    widget: { type: Object, required: true },
    rowBackgroundColor: { type: String, default: '' },
  },
  emits: ['open-lightbox'],
  data() {
    return {
      photos: [],
      timeline: [],
      highlights: [],
      capabilities: null,
      // Map settings from the backend (Phase 3.0): admin-configurable tile URL,
      // attribution, max-zoom, plus a global enabled-flag.
      mapSettings: {
        enabled: true,
        tile_url: 'https://tile.openstreetmap.org/{z}/{x}/{y}.png',
        attribution: '© OpenStreetMap contributors',
        max_zoom: 19,
      },
      loading: true,
      error: null,
      lightboxVisible: false,
      lightboxIndex: 0,
      // Client-side memo keyed by fetchKey — instant restore when switching
      // between modes (e.g. Timeline → Grid → Timeline) within the same session.
      _payloadCache: new Map(),
      // ETag from the last successful fetch (Phase 2.9.B2). Sent back as
      // If-None-Match so the server can return 304 instead of recomputing.
      _lastEtag: null,
      // Pagination state for folder-mode timeline + grid.
      pagination: {
        offset: 0,
        pageSize: 200,
        total: 0,
        hasMore: false,
        truncated: false,
      },
      loadingMore: false,
      _scrollObserver: null,
      _abortController: null,
    };
  },
  computed: {
    config() {
      return this.widget.config || {};
    },
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
      return dark.has(bg) ? 'ps--on-dark' : 'ps--on-tinted';
    },
    rowBgStyle() {
      if (this.rowBgClass !== 'ps--on-dark') {
        return {};
      }
      const textMap = {
        'var(--color-primary-element)': 'var(--color-primary-element-text)',
        'var(--color-primary)': 'var(--color-primary-text)',
        'var(--color-error)': 'var(--color-error-text)',
        'var(--color-success)': 'var(--color-success-text)',
        'var(--color-warning)': 'var(--color-warning-text)',
      };
      const bg = String(this.rowBackgroundColor || '').trim();
      const text = textMap[bg] || '#fff';
      return {
        '--ps-text': text,
        '--ps-text-muted': text,
      };
    },
    effectiveMode() {
      const m = this.config.mode || 'timeline';
      const allowed = ['timeline', 'highlights', 'grid', 'on-this-day'];
      return allowed.includes(m) ? m : 'timeline';
    },
    effectiveStyle() {
      const allowed = ['magazine', 'apple', 'travelogue'];
      const s = this.config.style || 'apple';
      return allowed.includes(s) ? s : 'apple';
    },
    emptyMessage() {
      const crossFolder = !!this.config.allMetaVoxFolders;
      const hasFilters = Array.isArray(this.config.metaVoxFilters) && this.config.metaVoxFilters.length > 0;
      if (crossFolder && !hasFilters) {
        return t('intravox', 'Add a filter to start');
      }
      if (!this.config.folderPath && !crossFolder) {
        return t('intravox', 'No folder selected');
      }
      if (this.effectiveMode === 'on-this-day') {
        return t('intravox', 'No photos taken on this day in previous years');
      }
      return t('intravox', 'No photos found');
    },
    /**
     * Show the "files may not be indexed yet" hint only when it actually fits
     * the scenario. Hiding it for filtered queries / on-this-day / cross-folder
     * (those have their own most-likely explanation), showing it only when the
     * user picked a normal folder and got zero results back.
     */
    showScanHint() {
      const crossFolder = !!this.config.allMetaVoxFolders;
      const hasFilters = Array.isArray(this.config.metaVoxFilters) && this.config.metaVoxFilters.length > 0;
      if (crossFolder || hasFilters) return false;
      if (!this.config.folderPath) return false;
      if (this.effectiveMode === 'on-this-day') return false;
      return true;
    },
    gridStyle() {
      const cols = Math.min(Math.max(this.config.columns || 3, 2), 5);
      return { '--ps-grid-cols': cols };
    },
    onThisDayGroups() {
      // Photos already sorted year-desc by backend
      const out = {};
      for (const p of this.photos) {
        if (!p.taken_at) continue;
        const year = String(new Date(p.taken_at).getFullYear());
        if (!out[year]) out[year] = [];
        out[year].push(p);
      }
      return out;
    },
    timelineDays() {
      // Backend already returns days in the requested sortOrder (paged path).
      // We precompute `_gpsPoints` and `_hasGps` per day so the template doesn't
      // call helper methods inside v-for — those create a new array per render
      // pass and trick PhotoStoryDayMap into thinking its `points` prop changed,
      // which re-instantiates Leaflet on every parent re-render.
      let days;
      if (this.timeline && this.timeline.length) {
        days = this.timeline;
      } else if (this.photos.length) {
        const fmt = new Intl.DateTimeFormat(getCanonicalLocale(), { day: 'numeric', month: 'long', year: 'numeric' });
        const sample = this.photos[0];
        const ts = sample.taken_at ? new Date(sample.taken_at) : new Date(sample.mtime * 1000);
        days = [{
          date: ts.toISOString().slice(0, 10),
          label: fmt.format(ts),
          location_summary: null,
          photos: this.photos,
        }];
      } else {
        return [];
      }
      return days.map(day => {
        const points = (day.photos || [])
          .filter(p => p && p.gps && Number.isFinite(p.gps.lat) && Number.isFinite(p.gps.lon))
          .map(p => ({ lat: p.gps.lat, lon: p.gps.lon, file_id: p.file_id, name: p.name }));
        return { ...day, _gpsPoints: points, _hasGps: points.length > 0 };
      });
    },
    // Stable key over only the request-changing config fields. Watching this
    // (instead of the whole widget) prevents cosmetic editor changes (style,
    // columns, toggles, title) from re-fetching the photo list.
    fetchKey() {
      const c = this.config || {};
      return JSON.stringify({
        f: c.folderPath || '',
        m: c.mode || 'timeline',
        l: c.limit ?? null,
        s: c.sortOrder || 'desc',
        sb: c.sortBy || 'mtime',
        x: !!c.allMetaVoxFolders,
        flt: Array.isArray(c.metaVoxFilters) ? c.metaVoxFilters : [],
        dr: c.hideRawDuplicates !== false,
      });
    },
    yearScrubber() {
      // Distinct years present in the timeline, descending (newest first)
      const years = new Set();
      for (const day of this.timelineDays) {
        const y = parseInt((day.date || '').slice(0, 4), 10);
        if (Number.isFinite(y)) years.add(y);
      }
      return Array.from(years).sort((a, b) => b - a);
    },
  },
  watch: {
    // Only re-fetch when a DATA-relevant field changes (folder, mode, filters, limit).
    // Cosmetic fields (style, columns, captions, map toggles, title) reuse the
    // existing `photos` array — no HTTP round-trip needed.
    fetchKey: {
      handler(newKey, oldKey) {
        if (newKey === oldKey) return;
        // 700 ms debounce — see FileStoryWidget for the rationale. Prevents
        // edit-mode mutation storms from stacking expensive listPhotos calls
        // on Apache workers and crashing the subsequent save with 503.
        clearTimeout(this._debounce);
        this._debounce = setTimeout(() => this.fetch(), 700);
      },
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
      this._idleHandle = null;
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
    t(key, vars = {}) {
      return t('intravox', key, vars);
    },
    // Whether the backend will paginate this request. Mirrors controller logic:
    // only folder-mode timeline + grid go through the paged path.
    isPagedMode() {
      const crossFolder = !!this.config.allMetaVoxFolders;
      const m = this.effectiveMode;
      return !crossFolder && !!this.config.folderPath && (m === 'timeline' || m === 'grid');
    },
    async fetch() {
      const crossFolder = !!this.config.allMetaVoxFolders;
      const hasFilters = Array.isArray(this.config.metaVoxFilters) && this.config.metaVoxFilters.length > 0;

      // Cross-folder mode but no filters yet → empty state.
      if (crossFolder && !hasFilters) {
        this.photos = [];
        this.timeline = [];
        this.highlights = [];
        this.loading = false;
        return;
      }
      // No folder picked + not in cross-folder mode → empty state.
      if (!crossFolder && !this.config.folderPath) {
        this.photos = [];
        this.timeline = [];
        this.highlights = [];
        this.loading = false;
        return;
      }

      // Client-side memo for non-paged modes only. Paged mode appends pages
      // into `this.photos` via fetchMore(); restoring the cached first-page
      // payload would silently drop the user's accumulated infinite-scroll
      // state — so we always re-fetch in paged mode and rely on the server's
      // 304 (via If-None-Match) to keep it cheap when nothing changed.
      const key = this.fetchKey;
      if (!this.isPagedMode()) {
        const cached = this._payloadCache.get(key);
        if (cached) {
          this.photos = cached.photos || [];
          this.timeline = cached.timeline || [];
          this.highlights = cached.highlights || [];
          this.capabilities = cached.capabilities || null;
          this.pagination = cached.pagination || { offset: 0, pageSize: 200, total: 0, hasMore: false, truncated: false };
          this.loading = false;
          return;
        }
      }

      // Cancel any pending fetch/fetchMore so we don't race a stale response.
      if (this._abortController) {
        this._abortController.abort();
      }
      this._abortController = new AbortController();
      const signal = this._abortController.signal;

      // Reset pagination for first page
      this.pagination = { offset: 0, pageSize: 200, total: 0, hasMore: false, truncated: false };

      this.loading = true;
      this.error = null;
      try {
        const params = this.buildPhotosParams(0);
        const url = generateUrl(`/apps/intravox/api/photo-story/photos?${params.toString()}`);
        const headers = {};
        if (this._lastEtag) {
          headers['If-None-Match'] = this._lastEtag;
        }
        const res = await axios.get(url, { headers, signal, validateStatus: s => (s >= 200 && s < 300) || s === 304 || s === 404 });
        if (res.status === 304) {
          this.loading = false;
          return;
        }
        if (res.status === 404) {
          // Folder doesn't exist for this user — empty state, not error.
          this.photos = [];
          this.timeline = [];
          this.highlights = [];
          this.capabilities = null;
          this.loading = false;
          return;
        }
        const data = res.data || {};
        this.photos = data.photos || [];
        this.timeline = data.timeline || [];
        this.highlights = data.highlights || [];
        this.capabilities = data.capabilities || null;
        this.warmFederatedPreviews(this.photos);
        if (data.map && typeof data.map === 'object') {
          this.mapSettings = { ...this.mapSettings, ...data.map };
        }
        if (data.pagination) {
          this.pagination = {
            offset: data.pagination.offset || 0,
            pageSize: data.pagination.pageSize || 200,
            total: data.pagination.total || this.photos.length,
            hasMore: !!data.pagination.hasMore,
            truncated: !!data.pagination.truncated,
          };
        }
        if (res.headers && res.headers.etag) {
          this._lastEtag = res.headers.etag;
        }
        if (!this.isPagedMode()) {
          if (this._payloadCache.size >= 8) {
            const oldestKey = this._payloadCache.keys().next().value;
            this._payloadCache.delete(oldestKey);
          }
          this._payloadCache.set(key, {
            photos: this.photos,
            timeline: this.timeline,
            highlights: this.highlights,
            capabilities: this.capabilities,
            pagination: { ...this.pagination },
          });
        }
        // Activate infinite-scroll observer when more pages are available.
        this.$nextTick(() => this.setupScrollSentinel());
      } catch (err) {
        if (axios.isCancel?.(err) || err?.name === 'CanceledError' || err?.code === 'ERR_CANCELED') {
          return;
        }
        console.error('[PhotoStoryWidget] fetch failed:', err);
        this.error = err.response?.data?.error || err.message || this.t('Failed to load photos');
      } finally {
        this.loading = false;
      }
    },
    buildPhotosParams(offset = 0) {
      const params = new URLSearchParams({ mode: this.effectiveMode });
      const crossFolder = !!this.config.allMetaVoxFolders;
      const hasFilters = Array.isArray(this.config.metaVoxFilters) && this.config.metaVoxFilters.length > 0;
      if (this.config.folderPath && !crossFolder) {
        params.append('folder', this.config.folderPath);
      }
      if (hasFilters) {
        params.append('filters', JSON.stringify(this.config.metaVoxFilters));
      }
      if (this.config.limit) {
        params.append('limit', String(this.config.limit));
      }
      if (this.isPagedMode()) {
        params.append('offset', String(offset));
        params.append('pageSize', String(this.pagination.pageSize || 200));
        params.append('sortOrder', this.config.sortOrder || 'desc');
        params.append('sortBy', this.config.sortBy || 'mtime');
        // Pass the total from the first page so the backend can skip the
        // expensive COUNT(*) on subsequent fetchMore() calls.
        if (offset > 0 && this.pagination.total > 0) {
          params.append('total', String(this.pagination.total));
        }
      }
      // Only send the param when the user explicitly opted out. Backend
      // defaults to dedup=on, so omitting matches existing widgets.
      if (this.config.hideRawDuplicates === false) {
        params.append('hideRawDuplicates', '0');
      }
      return params;
    },
    async fetchMore() {
      if (this.loadingMore || !this.pagination.hasMore || !this.isPagedMode()) return;
      this.loadingMore = true;
      const signal = this._abortController?.signal;
      try {
        const nextOffset = this.pagination.offset + this.pagination.pageSize;
        const params = this.buildPhotosParams(nextOffset);
        const url = generateUrl(`/apps/intravox/api/photo-story/photos?${params.toString()}`);
        const res = await axios.get(url, { signal, validateStatus: s => s >= 200 && s < 300 });
        const data = res.data || {};
        const newPhotos = data.photos || [];
        this.photos = this.photos.concat(newPhotos);
        this.warmFederatedPreviews(newPhotos);

        // Merge incoming timeline-days into existing timeline. Backend returns
        // grouped days for THIS page; we merge by date key, then explicitly
        // re-sort by date so cross-page bucket-order is always chronological
        // (insertion-order is wrong when a later page reaches back into earlier
        // dates — happens when SQL mtime-sort and bucket sort disagree, or with
        // mixed taken_at/mtime fallbacks).
        if (Array.isArray(data.timeline) && data.timeline.length) {
          const byDate = new Map();
          for (const d of this.timeline) byDate.set(d.date, d);
          for (const d of data.timeline) {
            if (byDate.has(d.date)) {
              const existing = byDate.get(d.date);
              existing.photos = existing.photos.concat(d.photos || []);
            } else {
              byDate.set(d.date, d);
            }
          }
          const ord = this.config.sortOrder || 'desc';
          this.timeline = Array.from(byDate.values()).sort((a, b) => ord === 'desc'
            ? b.date.localeCompare(a.date)
            : a.date.localeCompare(b.date));
        }

        if (data.pagination) {
          this.pagination = {
            offset: data.pagination.offset || nextOffset,
            pageSize: data.pagination.pageSize || this.pagination.pageSize,
            total: data.pagination.total || this.pagination.total,
            hasMore: !!data.pagination.hasMore,
            truncated: !!data.pagination.truncated,
          };
        }
      } catch (err) {
        if (axios.isCancel?.(err) || err?.name === 'CanceledError' || err?.code === 'ERR_CANCELED') {
          return;
        }
        console.error('[PhotoStoryWidget] fetchMore failed:', err);
      } finally {
        this.loadingMore = false;
      }
    },
    setupScrollSentinel() {
      // Tear down previous observer (e.g. on re-fetch)
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
              // After loading next page, reconnect or disconnect based on hasMore
              if (!this.pagination.hasMore && this._scrollObserver) {
                this._scrollObserver.disconnect();
                this._scrollObserver = null;
              }
            });
            break;
          }
        }
      }, { rootMargin: '800px' });
      this._scrollObserver.observe(sentinel);
    },
    /**
     * Best-effort pre-warm of the federated-preview cache. Picks every
     * `is_federated: true` photo from a newly loaded page and POSTs the
     * fileIds to the warmup endpoint. Backend handles cap, dedup and
     * outbound concurrency limit; we don't await the response.
     */
    warmFederatedPreviews(photos) {
      try {
        if (!Array.isArray(photos) || photos.length === 0) return;
        const fids = photos
          .filter(p => p && p.is_federated && p.file_id)
          .map(p => p.file_id);
        if (fids.length === 0) return;
        const url = generateUrl('/apps/intravox/api/preview/warmup');
        axios.post(url, { file_ids: fids }).catch(() => {});
      } catch (_) {
        // Pre-warm is fire-and-forget — never block render on it.
      }
    },
    previewUrl(photo, size = 512) {
      // Federated files cannot be served by NC core preview (the providers
      // can't reach the remote storage), so route them through IntraVox's
      // preview-proxy. Local files keep the direct /core/preview path.
      if (photo && photo.is_federated) {
        return generateUrl(`/apps/intravox/api/preview?file_id=${photo.file_id}&x=${size}&y=${size}`);
      }
      const fid = (photo && typeof photo === 'object') ? photo.file_id : photo;
      return generateUrl(`/core/preview?fileId=${fid}&x=${size}&y=${size}&a=true`);
    },
    formatDate(iso) {
      if (!iso) return '';
      try {
        const d = new Date(iso);
        return d.toLocaleDateString(getCanonicalLocale(), { day: 'numeric', month: 'long', year: 'numeric' });
      } catch (e) {
        return iso;
      }
    },
    // Locale-aware long-date formatter for sticky day-headers (all three styles).
    // Year is always included so days from different years stay visually distinct.
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
    // Deterministic pseudo-random column-span for magazine style (no width/height yet in photo dict).
    // Hash on file_id so the same photo always lands in the same cell — no flicker on re-render.
    magazineTileStyle(photo) {
      const id = Number(photo.file_id) || 0;
      const span = (id % 3) + 1; // 1, 2 or 3 columns
      return { gridColumn: `span ${span}` };
    },
    // For timeline mode: map photo to its index in the global photos array
    globalIndex(dayPhotos, idx) {
      const target = dayPhotos[idx];
      if (!target) return 0;
      const i = this.photos.findIndex(p => p.file_id === target.file_id);
      return i >= 0 ? i : 0;
    },
    // Mini-map helpers (Phase 2.8): show a per-day Leaflet map above each day-header.
    // Day-level _hasGps / _gpsPoints are precomputed in the timelineDays computed.
    showDayMaps() {
      return this.config.showDayMaps !== false;
    },
    openLightboxByFileId(fileId) {
      const idx = this.photos.findIndex(p => p.file_id === fileId);
      if (idx >= 0) this.openLightbox(idx);
    },
    scrollToYear(year) {
      // Find the first day-section whose date starts with that year and scroll to it
      const root = this.$el;
      if (!root) return;
      const sections = root.querySelectorAll('.ps-timeline section[class^="ps-day-"]');
      const prefix = String(year);
      for (const sec of sections) {
        // Sections use :key="day.date"; we need to match by their first descendant date label.
        const dateAttr = sec.dataset.date || '';
        if (dateAttr.startsWith(prefix)) {
          sec.scrollIntoView({ behavior: 'smooth', block: 'start' });
          return;
        }
      }
      // Fallback: search inside sections' first header text for the year
      for (const sec of sections) {
        const h = sec.querySelector('h2, h3, h4');
        if (h && h.textContent.includes(prefix)) {
          sec.scrollIntoView({ behavior: 'smooth', block: 'start' });
          return;
        }
      }
    },
    // Pseudo-random masonry: 1 in ~3 photos spans 2 rows for visual rhythm
    masonryItemStyle(photo) {
      const mtime = photo.mtime || photo.file_id || 0;
      const tall = (mtime % 3) === 0;
      return tall ? { gridRowEnd: 'span 2' } : {};
    },
    openLightbox(index) {
      this.lightboxIndex = index;
      this.lightboxVisible = true;
      this.$emit('open-lightbox', { index, photos: this.photos });
    },
    onClusterClick(payload) {
      // Open lightbox on first matching photo in cluster
      const ids = payload?.photo_ids || [];
      if (!ids.length) return;
      const idx = this.photos.findIndex(p => ids.includes(p.file_id));
      if (idx >= 0) {
        this.openLightbox(idx);
      }
    },
  },
};
</script>

<style scoped>
.photo-story-widget {
  width: 100%;
  margin: 12px 0;
  container-type: inline-size;
  position: relative;
}

.ps-scroll-sentinel {
  display: flex;
  justify-content: center;
  align-items: center;
  min-height: 60px;
  padding: 24px 0;
}

.ps-loading-more {
  font-size: 13px;
  color: var(--ps-text-muted, var(--color-text-maxcontrast));
}

.ps-truncated {
  margin: 16px 0;
  padding: 10px 14px;
  background: var(--color-warning-background);
  border: 1px solid var(--color-warning);
  border-radius: 8px;
  font-size: 13px;
  color: var(--ps-text-muted, var(--color-text-maxcontrast));
}

/* Year-jump scrubber: floats top-right inside the widget, sticky to viewport */
.ps-year-scrubber {
  position: sticky;
  top: 80px;
  float: right;
  margin: 0 0 0 12px;
  display: flex;
  flex-direction: column;
  gap: 2px;
  padding: 6px 4px;
  /* Theme-aware translucent backdrop (dark mode keeps the same blur but
     against a dark NC background rather than hard-coded white). */
  background: color-mix(in srgb, var(--color-main-background) 70%, transparent);
  backdrop-filter: blur(8px);
  -webkit-backdrop-filter: blur(8px);
  border-radius: 999px;
  z-index: 2;
  font-size: 11px;
  font-weight: 600;
  color: var(--ps-text-muted, var(--color-text-maxcontrast));
}

.ps-year-btn {
  background: none;
  border: 0;
  cursor: pointer;
  padding: 4px 8px;
  border-radius: 999px;
  color: inherit;
  font: inherit;
  transition: background 0.15s ease, color 0.15s ease;
}

.ps-year-btn:hover {
  background: var(--color-primary);
  color: var(--color-primary-text);
}

@container (max-width: 500px) {
  .ps-year-scrubber {
    display: none;
  }
}

.ps-map-embed {
  margin-bottom: 16px;
}

.ps-loading {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
  gap: 8px;
}

.ps-skeleton-tile {
  aspect-ratio: 1 / 1;
  background: var(--color-background-hover);
  border-radius: var(--border-radius);
  animation: ps-pulse 1.5s infinite ease-in-out;
}

@keyframes ps-pulse {
  0%, 100% { opacity: 0.6; }
  50% { opacity: 1; }
}

.ps-error,
.ps-empty {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 12px;
  padding: 40px 20px;
  color: var(--ps-text-muted, var(--color-text-maxcontrast));
  text-align: center;
}

.ps-error {
  color: var(--color-error);
}

.ps-empty {
  background: var(--color-background-hover);
  border: 2px dashed var(--color-border);
  border-radius: var(--border-radius-large);
}

.ps-empty small {
  font-size: 12px;
  opacity: 0.7;
}

.ps-empty-hint {
  /* Secondary hint that appears below the empty-state path. Slightly larger
   * line-height + max-width so the longer text wraps nicely. */
  display: block;
  max-width: 480px;
  margin-top: 8px;
  line-height: 1.4;
}

/* Timeline / on-this-day day section */
.ps-day,
.ps-year {
  margin-bottom: 24px;
}

.ps-day-header {
  margin-bottom: 8px;
}

.ps-day-label {
  margin: 0;
  font-size: 16px;
  font-weight: 600;
  color: var(--ps-text, var(--color-main-text));
}

.ps-day-location {
  margin: 2px 0 0 0;
  font-size: 12px;
  color: var(--ps-text-muted, var(--color-text-maxcontrast));
}

/* Grids */
.ps-grid {
  display: grid;
  gap: 8px;
}

.ps-grid--responsive {
  grid-template-columns: repeat(3, 1fr);
}

@container (max-width: 700px) {
  .ps-grid--responsive {
    grid-template-columns: repeat(2, 1fr);
  }
}

@container (max-width: 400px) {
  .ps-grid--responsive {
    grid-template-columns: 1fr;
  }
}

.ps-grid--two {
  grid-template-columns: repeat(2, 1fr);
}

@container (max-width: 500px) {
  .ps-grid--two {
    grid-template-columns: 1fr;
  }
}

.ps-grid--masonry {
  grid-template-columns: repeat(var(--ps-grid-cols, 3), 1fr);
  grid-auto-rows: 120px;
}

@container (max-width: 700px) {
  .ps-grid--masonry {
    grid-template-columns: repeat(2, 1fr);
  }
}

@container (max-width: 400px) {
  .ps-grid--masonry {
    grid-template-columns: 1fr;
  }
}

/* Photo tile */
:deep(.ps-tile) {
  position: relative;
  margin: 0;
  border-radius: var(--border-radius-large);
  overflow: hidden;
  cursor: pointer;
  background: var(--color-background-dark);
  transition: transform 0.2s ease, box-shadow 0.2s ease;
  aspect-ratio: 1 / 1;
  height: 100%;
}

:deep(.ps-grid--masonry .ps-tile) {
  aspect-ratio: unset;
}

:deep(.ps-tile:hover),
:deep(.ps-tile:focus-visible) {
  transform: scale(1.02);
  box-shadow: 0 4px 14px rgba(0, 0, 0, 0.15);
  outline: none;
}

:deep(.ps-tile-img) {
  width: 100%;
  height: 100%;
  object-fit: cover;
  display: block;
}

/* Video tile: centered play-button overlay on top of the preview / placeholder */
:deep(.ps-tile-video-badge) {
  position: absolute;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%);
  width: 44px;
  height: 44px;
  border-radius: 50%;
  background: rgba(0, 0, 0, 0.55);
  color: #fff;
  display: flex;
  align-items: center;
  justify-content: center;
  pointer-events: none;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.35);
  transition: transform 0.15s ease, background 0.15s ease;
}

:deep(.ps-tile:hover .ps-tile-video-badge),
:deep(.ps-tile:focus .ps-tile-video-badge) {
  background: rgba(0, 0, 0, 0.75);
  transform: translate(-50%, -50%) scale(1.08);
}

:deep(.ps-tile-video-badge svg) {
  margin-left: 2px; /* visual centering — play triangle leans right */
}

:deep(.ps-tile-caption) {
  position: absolute;
  left: 0;
  right: 0;
  bottom: 0;
  padding: 8px 10px;
  font-size: 12px;
  background: linear-gradient(180deg, transparent, rgba(0, 0, 0, 0.65));
  color: #fff;
  opacity: 0;
  transition: opacity 0.2s ease;
}

:deep(.ps-tile:hover .ps-tile-caption),
:deep(.ps-tile:focus-visible .ps-tile-caption) {
  opacity: 1;
}

/* Placeholder for files without a thumbnail (RAW without preview-provider, missing previews) */
:deep(.ps-tile--placeholder) {
  background: linear-gradient(135deg, var(--color-background-dark) 0%, var(--color-main-background) 100%);
  border: 1px solid var(--color-border);
}

:deep(.ps-tile-placeholder) {
  width: 100%;
  height: 100%;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 8px;
  padding: 12px;
  text-align: center;
  color: var(--ps-text, var(--color-main-text));
}

:deep(.ps-tile-placeholder-badge) {
  font-family: var(--font-face, monospace);
  font-size: 11px;
  font-weight: 700;
  letter-spacing: 1px;
  padding: 4px 10px;
  border-radius: 999px;
  background: var(--color-primary-element);
  color: var(--color-primary-element-text);
}

:deep(.ps-tile-placeholder-meta) {
  display: flex;
  flex-direction: column;
  gap: 2px;
  font-size: 11px;
  line-height: 1.3;
  word-break: break-word;
  max-width: 100%;
}

:deep(.ps-tile-placeholder-meta strong) {
  font-size: 12px;
  font-weight: 600;
}

:deep(.ps-tile-placeholder-meta span) {
  color: var(--ps-text-muted, var(--color-text-maxcontrast));
}

:deep(.ps-tile-placeholder-meta small) {
  color: var(--ps-text-muted, var(--color-text-maxcontrast));
  font-size: 10px;
}

/* Highlights hero */
.ps-highlights {
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.ps-highlight-hero {
  position: relative;
  width: 100%;
  max-height: 45vh;
  aspect-ratio: 16 / 9;
  overflow: hidden;
  border-radius: var(--border-radius-large);
  cursor: pointer;
}

.ps-hero-img {
  width: 100%;
  height: 100%;
  max-height: 45vh;
  object-fit: cover;
  display: block;
  transition: transform 0.4s ease;
}

.ps-highlight-hero:hover .ps-hero-img {
  transform: scale(1.02);
}

.ps-hero-caption {
  position: absolute;
  left: 0;
  right: 0;
  bottom: 0;
  padding: 20px;
  background: linear-gradient(180deg, transparent, rgba(0, 0, 0, 0.75));
  color: #fff;
  display: flex;
  flex-direction: column;
  gap: 2px;
}

.ps-hero-caption strong {
  font-size: 18px;
}

.ps-hero-caption span {
  font-size: 13px;
  opacity: 0.85;
}

/* =========================================================================
   Timeline visual styles (Phase 2.2)
   ========================================================================= */

/* ---- Magazine ---- */
.ps-timeline--magazine {
  font-family: 'Georgia', 'Source Serif Pro', 'Iowan Old Style', serif;
}

.ps-day-mag {
  margin-bottom: 80px;
}

/* Transparent header — inherits the row/widget background instead of forcing
 * a white block over a themed row. Hairline underline keeps the visual
 * separator. See file-story-widget.vue::fs-day-header for the same pattern. */
.ps-day-mag-header {
  background: transparent;
  text-align: center;
  padding: 16px 0 12px;
  margin-bottom: 24px;
  border-bottom: 1px solid var(--color-border);
}

/* Mini-map styling — Magazine: editorial wide banner */
.ps-day-mag-map {
  margin: 0 0 28px;
  box-shadow: 0 2px 6px rgba(0, 0, 0, 0.08);
}

/* Mini-map styling — Apple: small, tucked above header */
.ps-day-apple-map {
  margin: 0 0 12px;
  border-radius: 8px;
}

/* Mini-map styling — Travelogue: between header + grid, reisdagboek-feel */
.ps-day-trav-map {
  margin: 4px 0 12px;
  box-shadow: 0 1px 4px rgba(0, 0, 0, 0.1);
}

.ps-day-mag-date {
  margin: 0;
  font-size: 32px;
  font-weight: 400;
  letter-spacing: -0.02em;
  color: var(--ps-text, var(--color-main-text));
  font-family: inherit;
}

.ps-day-mag-loc {
  margin: 4px 0 0 0;
  font-style: italic;
  color: var(--ps-text-muted, var(--color-text-maxcontrast));
  font-size: 16px;
}

.ps-mag-grid {
  display: grid;
  grid-template-columns: repeat(6, 1fr);
  gap: 24px;
}

@container (max-width: 900px) {
  .ps-mag-grid {
    grid-template-columns: repeat(4, 1fr);
  }
}

@container (max-width: 600px) {
  .ps-mag-grid {
    grid-template-columns: repeat(2, 1fr);
    gap: 12px;
  }
  .ps-mag-tile {
    /* On narrow widths a 3-column span overflows; clamp to grid-width */
    grid-column: span 2 !important;
  }
}

.ps-mag-tile {
  margin: 0;
  aspect-ratio: 4 / 3;
  overflow: hidden;
  border-radius: 2px; /* editorial look — almost square corners */
  cursor: pointer;
  background: var(--color-background-dark);
  transition: transform 0.25s ease, box-shadow 0.25s ease;
}

.ps-mag-tile:hover,
.ps-mag-tile:focus-visible {
  transform: scale(1.01);
  box-shadow: 0 6px 22px rgba(0, 0, 0, 0.18);
  outline: none;
}

.ps-mag-img {
  width: 100%;
  height: 100%;
  object-fit: cover;
  display: block;
}

/* ---- Apple ---- */
.ps-timeline--apple {
  /* inherit system font — do not override */
}

.ps-day-apple {
  margin-bottom: 32px;
}

.ps-day-apple-header {
  background: transparent;
  display: flex;
  justify-content: space-between;
  align-items: baseline;
  gap: 12px;
  padding: 10px 4px 4px;
  margin-top: 4px;
  margin-bottom: 12px;
  border-bottom: 1px solid var(--color-border);
}

.ps-day-apple-date {
  margin: 0;
  font-size: 22px;
  font-weight: 600;
  color: var(--ps-text, var(--color-main-text));
}

.ps-day-apple-loc {
  font-size: 13px;
  color: var(--ps-text-muted, var(--color-text-maxcontrast));
  display: inline-flex;
  align-items: center;
  gap: 4px;
}

.ps-apple-grid {
  display: grid;
  grid-template-columns: repeat(5, 1fr);
  gap: 4px;
}

@container (max-width: 900px) {
  .ps-apple-grid {
    grid-template-columns: repeat(4, 1fr);
  }
}

@container (max-width: 600px) {
  .ps-apple-grid {
    grid-template-columns: repeat(3, 1fr);
  }
}

:deep(.ps-timeline--apple .ps-tile) {
  aspect-ratio: 1 / 1;
  border-radius: 6px;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.08);
  overflow: hidden;
}

/* ---- Travelogue (Polarsteps) ---- */
.ps-timeline--travelogue {
  /* outer styling lives on each day section so the rail aligns per-day */
}

.ps-day-trav {
  display: grid;
  grid-template-columns: 60px 1fr;
  gap: 16px;
  margin-bottom: 24px;
}

.ps-trav-rail {
  position: relative;
}

.ps-trav-rail::before {
  content: '';
  position: absolute;
  top: 0;
  bottom: -24px; /* extend into the gap so the line continues between days */
  left: 30px;
  width: 2px;
  background: var(--color-border);
}

.ps-trav-bullet {
  width: 14px;
  height: 14px;
  border-radius: 50%;
  background: var(--color-primary);
  position: absolute;
  left: 24px;
  top: 14px;
  box-shadow: 0 0 0 4px var(--color-main-background);
  z-index: 1;
}

.ps-trav-content {
  min-width: 0;
}

.ps-day-trav-header {
  background: transparent;
  padding: 10px 0 4px;
  margin-top: 4px;
  margin-bottom: 4px;
  border-bottom: 1px solid var(--color-border);
}

.ps-day-trav-date {
  margin: 0 0 4px 0;
  font-size: 18px;
  font-weight: 600;
  color: var(--ps-text, var(--color-main-text));
}

.ps-day-trav-loc {
  margin: 0 0 12px 0;
  font-size: 13px;
  color: var(--ps-text-muted, var(--color-text-maxcontrast));
  display: flex;
  align-items: center;
  gap: 4px;
}

.ps-trav-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 8px;
}

@container (max-width: 500px) {
  .ps-day-trav {
    grid-template-columns: 40px 1fr;
    gap: 8px;
  }
  .ps-trav-rail::before {
    left: 20px;
  }
  .ps-trav-bullet {
    left: 14px;
  }
  .ps-trav-grid {
    grid-template-columns: 1fr;
  }
}

@media (prefers-reduced-motion: reduce) {
  .ps-tile-skeleton,
  .ps-day-skeleton {
    animation: none;
  }
}

/* Dark row-bg contrast lifts. Photo tiles already have their own surfaces
   (image previews, gradient overlays), so the widget only needs to recolor
   the day/section headers and adjust separators to stay legible. */
.photo-story-widget.ps--on-dark :deep(.ps-day-header),
.photo-story-widget.ps--on-dark :deep(.ps-section-header) {
  border-bottom-color: rgba(255, 255, 255, 0.25);
}

.photo-story-widget.ps--on-dark :deep(.ps-day-location),
.photo-story-widget.ps--on-dark :deep(.ps-day-mag-loc),
.photo-story-widget.ps--on-dark :deep(.ps-day-apple-loc),
.photo-story-widget.ps--on-dark :deep(.ps-day-trav-loc) {
  opacity: 0.82;
}

.photo-story-widget.ps--on-dark :deep(.ps-tile-skeleton),
.photo-story-widget.ps--on-dark :deep(.ps-day-skeleton) {
  background: rgba(255, 255, 255, 0.12);
}
</style>
