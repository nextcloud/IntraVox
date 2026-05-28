<template>
  <div
    v-if="visible"
    class="ps-lightbox"
    role="dialog"
    aria-modal="true"
    tabindex="-1"
    ref="root"
    @click.self="close"
    @keydown="onKey"
    @touchstart="onTouchStart"
    @touchend="onTouchEnd"
  >
    <!-- Top bar -->
    <header class="ps-lb-topbar">
      <div class="ps-lb-counter" aria-live="polite" aria-atomic="true">
        <span class="ps-lb-sr-only">{{ t('Photo') }}</span>
        {{ index + 1 }} / {{ photos.length }}
      </div>
      <div class="ps-lb-actions">
        <button
          class="ps-lb-icon-btn"
          :aria-label="slideshowOn ? t('Pause slideshow') : t('Start slideshow')"
          :aria-pressed="slideshowOn ? 'true' : 'false'"
          :title="slideshowOn ? t('Pause slideshow') : t('Start slideshow')"
          @click="toggleSlideshow"
        >
          <Pause v-if="slideshowOn" :size="20" />
          <Play v-else :size="20" />
        </button>
        <label v-if="slideshowOn" class="ps-lb-sr-only" for="ps-lb-speed-select">{{ t('Slideshow speed') }}</label>
        <select
          v-if="slideshowOn"
          id="ps-lb-speed-select"
          v-model.number="speed"
          class="ps-lb-speed"
          :aria-label="t('Slideshow speed')"
        >
          <option :value="6000">{{ t('Slow') }}</option>
          <option :value="4000">{{ t('Normal') }}</option>
          <option :value="2500">{{ t('Fast') }}</option>
        </select>
        <button
          class="ps-lb-icon-btn"
          :aria-label="t('Toggle info panel')"
          :aria-pressed="infoOpen ? 'true' : 'false'"
          :title="t('Toggle info panel')"
          @click="infoOpen = !infoOpen"
        >
          <InformationOutline :size="20" />
        </button>
        <button
          class="ps-lb-icon-btn"
          :aria-label="t('Close lightbox')"
          :title="t('Close lightbox')"
          @click="close"
        >
          <Close :size="22" />
        </button>
      </div>
    </header>

    <!-- Main content -->
    <div class="ps-lb-stage">
      <button
        class="ps-lb-nav ps-lb-nav--prev"
        :disabled="photos.length < 2"
        :aria-label="t('Previous photo')"
        :title="t('Previous photo')"
        @click.stop="prev"
      >
        <ChevronLeft :size="36" />
      </button>

      <div class="ps-lb-image-wrap" :class="{ 'ps-lb-kenburns': slideshowOn && !isVideo(currentPhoto) }">
        <video
          v-if="currentPhoto && isVideo(currentPhoto)"
          :key="'v' + currentPhoto.file_id"
          :src="videoUrl(currentPhoto)"
          :poster="fullUrl(currentPhoto)"
          class="ps-lb-image ps-lb-video"
          controls
          autoplay
          playsinline
          preload="metadata"
        />
        <img
          v-else-if="currentPhoto"
          :key="currentPhoto.file_id"
          :src="fullUrl(currentPhoto)"
          :alt="currentPhoto.location_display || currentPhoto.location || t('Photo {n} of {total}', { n: index + 1, total: photos.length })"
          class="ps-lb-image"
        />
      </div>

      <button
        class="ps-lb-nav ps-lb-nav--next"
        :disabled="photos.length < 2"
        :aria-label="t('Next photo')"
        :title="t('Next photo')"
        @click.stop="next"
      >
        <ChevronRight :size="36" />
      </button>
    </div>

    <!-- Date + location pill (always visible, left-bottom) -->
    <button
      v-if="currentPhoto"
      type="button"
      class="ps-lb-pill"
      :aria-label="t('Toggle location map')"
      :aria-pressed="miniMapOpen ? 'true' : 'false'"
      :disabled="!currentPhoto.gps"
      @click.stop="onPillClick"
    >
      <div class="ps-lb-pill-date" v-if="currentPhoto.taken_at">
        {{ formatLongDate(currentPhoto.taken_at) }}
        <span class="ps-lb-pill-time">· {{ formatTime(currentPhoto.taken_at) }}</span>
      </div>
      <div v-else class="ps-lb-pill-date ps-lb-pill-date--fallback">
        {{ t('No date in EXIF') }}
      </div>
      <div v-if="locationLabel" class="ps-lb-pill-loc">
        <MapMarker :size="14" />
        <span>{{ locationLabel }}</span>
      </div>
    </button>

    <!-- Mini-map flyout (toggled by clicking pill) -->
    <aside v-if="miniMapOpen && currentPhoto && currentPhoto.gps" class="ps-lb-minimap" @click.stop>
      <div class="ps-lb-minimap-header">
        <span>{{ locationLabel || t('Location') }}</span>
        <button
          class="ps-lb-icon-btn"
          :aria-label="t('Close map')"
          :title="t('Close map')"
          @click="miniMapOpen = false"
        >
          <Close :size="18" />
        </button>
      </div>
      <iframe
        :src="osmEmbedUrl"
        class="ps-lb-minimap-frame"
        loading="lazy"
        referrerpolicy="no-referrer-when-downgrade"
        :title="t('Location map')"
      ></iframe>
      <a
        v-if="osmExternalUrl"
        :href="osmExternalUrl"
        target="_blank"
        rel="noopener noreferrer"
        class="ps-lb-minimap-link"
      >
        {{ t('View larger map') }}
      </a>
    </aside>

    <!-- Details flyout (people/subjects/camera/filename) -->
    <aside v-if="infoOpen && currentPhoto" class="ps-lb-details" @click.stop>
      <header class="ps-lb-details-header">
        <h3>{{ t('Details') }}</h3>
        <button
          class="ps-lb-icon-btn"
          :aria-label="t('Close details')"
          :title="t('Close details')"
          @click="infoOpen = false"
        >
          <Close :size="18" />
        </button>
      </header>
      <dl class="ps-lb-info-list">
        <template v-if="hasPeople">
          <dt>{{ t('People') }}</dt>
          <dd>
            <span v-for="p in currentPhoto.people" :key="p" class="ps-lb-chip">{{ p }}</span>
          </dd>
        </template>
        <template v-if="hasSubjects">
          <dt>{{ t('Subjects') }}</dt>
          <dd>
            <span v-for="s in currentPhoto.subjects" :key="s" class="ps-lb-chip">{{ s }}</span>
          </dd>
        </template>
        <template v-if="currentPhoto.camera">
          <dt>{{ t('Camera') }}</dt>
          <dd>{{ currentPhoto.camera }}</dd>
        </template>
        <template v-if="currentPhoto.name">
          <dt>{{ t('File') }}</dt>
          <dd class="ps-lb-info-filename">{{ currentPhoto.name }}</dd>
        </template>
      </dl>
    </aside>
  </div>
</template>

<script>
import { generateUrl } from '@nextcloud/router';
import { translate as t, getCanonicalLocale } from '@nextcloud/l10n';
import ChevronLeft from 'vue-material-design-icons/ChevronLeft.vue';
import ChevronRight from 'vue-material-design-icons/ChevronRight.vue';
import Close from 'vue-material-design-icons/Close.vue';
import Play from 'vue-material-design-icons/Play.vue';
import Pause from 'vue-material-design-icons/Pause.vue';
import InformationOutline from 'vue-material-design-icons/InformationOutline.vue';
import MapMarker from 'vue-material-design-icons/MapMarker.vue';

export default {
  name: 'PhotoLightbox',
  components: {
    ChevronLeft,
    ChevronRight,
    Close,
    Play,
    Pause,
    InformationOutline,
    MapMarker,
  },
  props: {
    photos: { type: Array, required: true },
    startIndex: { type: Number, default: 0 },
    visible: { type: Boolean, default: false },
  },
  emits: ['close'],
  data() {
    return {
      index: this.startIndex,
      infoOpen: false,
      miniMapOpen: false,
      slideshowOn: false,
      speed: 4000,
      slideshowTimer: null,
      touchStartX: 0,
      touchStartY: 0,
    };
  },
  computed: {
    currentPhoto() {
      return this.photos[this.index] || null;
    },
    hasPeople() {
      return Array.isArray(this.currentPhoto?.people) && this.currentPhoto.people.length > 0;
    },
    hasSubjects() {
      return Array.isArray(this.currentPhoto?.subjects) && this.currentPhoto.subjects.length > 0;
    },
    locationLabel() {
      const p = this.currentPhoto;
      if (!p) return '';
      if (p.location_display) return p.location_display;
      if (p.location) {
        return p.country && !p.location.includes(p.country)
          ? `${p.location}, ${p.country}`
          : p.location;
      }
      return p.country || '';
    },
    osmEmbedUrl() {
      const gps = this.currentPhoto?.gps;
      if (!gps) return '';
      const { lat, lon } = gps;
      const d = 0.005;
      const bbox = `${lon - d},${lat - d},${lon + d},${lat + d}`;
      return `https://www.openstreetmap.org/export/embed.html?bbox=${bbox}&layer=mapnik&marker=${lat},${lon}`;
    },
    osmExternalUrl() {
      const gps = this.currentPhoto?.gps;
      if (!gps) return '';
      return `https://www.openstreetmap.org/?mlat=${gps.lat}&mlon=${gps.lon}#map=15/${gps.lat}/${gps.lon}`;
    },
  },
  watch: {
    visible(v) {
      if (v) {
        this.index = this.startIndex;
        this.$nextTick(() => {
          this.$refs.root?.focus();
        });
      } else {
        this.stopSlideshow();
      }
    },
    speed() {
      if (this.slideshowOn) {
        this.stopSlideshow();
        this.startSlideshow();
      }
    },
    index() {
      // Close transient panels when navigating to next/prev photo
      this.miniMapOpen = false;
    },
  },
  mounted() {
    // Remember the element that opened the lightbox so we can restore focus
    // when it closes — required for keyboard users (WCAG 2.4.3).
    this._previouslyFocused = document.activeElement instanceof HTMLElement
      ? document.activeElement
      : null;
    this.index = this.startIndex;
    this.$nextTick(() => {
      this.$refs.root?.focus();
    });
  },
  beforeUnmount() {
    this.stopSlideshow();
    // Restore focus to the element that triggered the lightbox.
    if (this._previouslyFocused && typeof this._previouslyFocused.focus === 'function') {
      try { this._previouslyFocused.focus(); } catch (e) { /* ignore */ }
      this._previouslyFocused = null;
    }
  },
  methods: {
    t(key, vars = {}) {
      return t('intravox', key, vars);
    },
    fullUrl(photo) {
      // High-resolution image OR video poster-frame. Federated photos
      // route through IntraVox's preview proxy because NC core can't
      // generate previews for files on remote storages.
      if (photo && photo.is_federated) {
        return generateUrl(`/apps/intravox/api/preview?file_id=${photo.file_id}&x=2048&y=2048`);
      }
      return generateUrl(`/core/preview?fileId=${photo.file_id}&x=2048&y=2048&a=true`);
    },
    isVideo(photo) {
      if (!photo) return false;
      const mime = String(photo.mime || '');
      if (mime.startsWith('video/')) return true;
      return /\.(mov|mp4|m4v|avi|mkv|webm|wmv|mpg|mpeg|3gp)$/i.test(photo.name || '');
    },
    videoUrl(photo) {
      // Stream via our own endpoint — handles ACL + range requests + groupfolder
      // path resolution without us having to construct a WebDAV URL client-side.
      return generateUrl(`/apps/intravox/api/photo-story/video?file_id=${photo.file_id}`);
    },
    formatDateTime(iso) {
      if (!iso) return '';
      try {
        const d = new Date(iso);
        return d.toLocaleString(getCanonicalLocale(), {
          day: 'numeric',
          month: 'long',
          year: 'numeric',
          hour: '2-digit',
          minute: '2-digit',
        });
      } catch (e) {
        return iso;
      }
    },
    formatLongDate(iso) {
      if (!iso) return '';
      try {
        const d = new Date(iso);
        return d.toLocaleDateString(getCanonicalLocale(), {
          weekday: 'long',
          day: 'numeric',
          month: 'long',
          year: 'numeric',
        });
      } catch (e) {
        return iso;
      }
    },
    formatTime(iso) {
      if (!iso) return '';
      try {
        const d = new Date(iso);
        return d.toLocaleTimeString(getCanonicalLocale(), { hour: '2-digit', minute: '2-digit' });
      } catch (e) {
        return '';
      }
    },
    onPillClick() {
      // Toggle mini-map if GPS available; otherwise no-op
      if (this.currentPhoto?.gps) {
        this.miniMapOpen = !this.miniMapOpen;
      }
    },
    next() {
      if (!this.photos.length) return;
      this.index = (this.index + 1) % this.photos.length;
    },
    prev() {
      if (!this.photos.length) return;
      this.index = (this.index - 1 + this.photos.length) % this.photos.length;
    },
    close() {
      this.stopSlideshow();
      this.$emit('close');
    },
    onKey(e) {
      // Focus trap: Tab should cycle within the lightbox, never escape it.
      if (e.key === 'Tab') {
        this.trapTab(e);
        return;
      }
      switch (e.key) {
        case 'ArrowRight':
          this.next();
          break;
        case 'ArrowLeft':
          this.prev();
          break;
        case 'Home':
          this.index = 0;
          break;
        case 'End':
          this.index = Math.max(0, this.photos.length - 1);
          break;
        case 'Escape':
          this.close();
          break;
        case ' ':
          e.preventDefault();
          this.toggleSlideshow();
          break;
        default:
          return;
      }
    },
    trapTab(e) {
      const root = this.$refs.root;
      if (!root) return;
      const focusables = root.querySelectorAll(
        'a[href], button:not([disabled]), select:not([disabled]), [tabindex]:not([tabindex="-1"])'
      );
      if (focusables.length === 0) {
        e.preventDefault();
        return;
      }
      const first = focusables[0];
      const last = focusables[focusables.length - 1];
      const active = document.activeElement;
      if (e.shiftKey && (active === first || active === root)) {
        e.preventDefault();
        last.focus();
      } else if (!e.shiftKey && active === last) {
        e.preventDefault();
        first.focus();
      }
    },
    onTouchStart(e) {
      const t0 = e.touches?.[0];
      if (!t0) return;
      this.touchStartX = t0.clientX;
      this.touchStartY = t0.clientY;
    },
    onTouchEnd(e) {
      const t1 = e.changedTouches?.[0];
      if (!t1) return;
      const dx = t1.clientX - this.touchStartX;
      const dy = t1.clientY - this.touchStartY;
      // Horizontal swipe over 60px and dominant axis
      if (Math.abs(dx) > 60 && Math.abs(dx) > Math.abs(dy)) {
        if (dx < 0) this.next();
        else this.prev();
      }
    },
    toggleSlideshow() {
      if (this.slideshowOn) {
        this.stopSlideshow();
      } else {
        this.startSlideshow();
      }
    },
    startSlideshow() {
      this.slideshowOn = true;
      this.slideshowTimer = setInterval(() => {
        this.next();
      }, this.speed);
    },
    stopSlideshow() {
      this.slideshowOn = false;
      if (this.slideshowTimer) {
        clearInterval(this.slideshowTimer);
        this.slideshowTimer = null;
      }
    },
  },
};
</script>

<style scoped>
.ps-lb-sr-only {
  position: absolute;
  width: 1px;
  height: 1px;
  padding: 0;
  margin: -1px;
  overflow: hidden;
  clip: rect(0, 0, 0, 0);
  white-space: nowrap;
  border: 0;
}

.ps-lightbox {
  position: fixed;
  inset: 0;
  z-index: 10000;
  background: rgba(0, 0, 0, 0.95);
  display: flex;
  flex-direction: column;
  color: #fff;
  outline: none;
}

.ps-lb-topbar {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 12px 16px;
  background: linear-gradient(180deg, rgba(0, 0, 0, 0.7), transparent);
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  z-index: 2;
}

.ps-lb-counter {
  font-size: 14px;
  font-variant-numeric: tabular-nums;
  opacity: 0.8;
}

.ps-lb-actions {
  display: flex;
  align-items: center;
  gap: 8px;
}

.ps-lb-icon-btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 40px;
  height: 40px;
  border-radius: 50%;
  border: none;
  background: rgba(255, 255, 255, 0.08);
  color: #fff;
  cursor: pointer;
  transition: background 0.15s;
}

.ps-lb-icon-btn:hover {
  background: rgba(255, 255, 255, 0.18);
}

.ps-lb-speed {
  padding: 6px 8px;
  background: rgba(255, 255, 255, 0.1);
  color: #fff;
  border: 1px solid rgba(255, 255, 255, 0.2);
  border-radius: var(--border-radius);
  font-size: 13px;
}

.ps-lb-stage {
  flex: 1;
  display: flex;
  align-items: center;
  justify-content: center;
  position: relative;
  padding: 60px 60px;
  overflow: hidden;
}

.ps-lb-image-wrap {
  width: 100%;
  height: 100%;
  display: flex;
  align-items: center;
  justify-content: center;
}

.ps-lb-image {
  max-width: 95vw;
  max-height: 90vh;
  object-fit: contain;
  display: block;
}

/* Ken Burns effect during slideshow */
.ps-lb-kenburns .ps-lb-image {
  animation: ps-kenburns 4s cubic-bezier(0.22, 0.61, 0.36, 1) forwards;
}

@keyframes ps-kenburns {
  from {
    transform: scale(1);
    opacity: 0;
  }
  10% {
    opacity: 1;
  }
  to {
    transform: scale(1.1) translate(2%, -2%);
    opacity: 1;
  }
}

.ps-lb-nav {
  position: absolute;
  top: 50%;
  transform: translateY(-50%);
  width: 56px;
  height: 56px;
  border-radius: 50%;
  border: none;
  background: rgba(255, 255, 255, 0.08);
  color: #fff;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: background 0.15s;
}

.ps-lb-nav:hover:not(:disabled) {
  background: rgba(255, 255, 255, 0.2);
}

.ps-lb-nav:disabled {
  opacity: 0.25;
  cursor: not-allowed;
}

.ps-lb-nav--prev {
  left: 12px;
}

.ps-lb-nav--next {
  right: 12px;
}

/* Date + location pill (always-visible left-bottom) */
.ps-lb-pill {
  position: absolute;
  left: 24px;
  bottom: 24px;
  background: rgba(20, 20, 20, 0.78);
  backdrop-filter: blur(12px);
  -webkit-backdrop-filter: blur(12px);
  border: none;
  border-radius: 14px;
  padding: 14px 20px;
  color: #fff;
  z-index: 3;
  box-shadow: 0 6px 24px rgba(0, 0, 0, 0.45);
  cursor: pointer;
  transition: background 0.2s ease, transform 0.2s ease;
  max-width: 70vw;
  text-align: left;
  font: inherit;
}

.ps-lb-pill:disabled {
  cursor: default;
  opacity: 0.85;
}

.ps-lb-pill:hover {
  background: rgba(20, 20, 20, 0.92);
  transform: translateY(-2px);
}

.ps-lb-pill-date {
  font-size: 18px;
  font-weight: 600;
  letter-spacing: -0.01em;
  line-height: 1.2;
}

.ps-lb-pill-date--fallback {
  color: rgba(255, 255, 255, 0.6);
  font-weight: 500;
  font-style: italic;
}

.ps-lb-pill-time {
  font-weight: 400;
  opacity: 0.8;
}

.ps-lb-pill-loc {
  display: flex;
  align-items: center;
  gap: 4px;
  margin-top: 6px;
  font-size: 14px;
  color: rgba(255, 255, 255, 0.85);
}

.ps-lb-pill-loc span {
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

/* Mini-map flyout */
.ps-lb-minimap {
  position: absolute;
  left: 24px;
  bottom: 110px;
  width: 380px;
  max-width: calc(100vw - 48px);
  background: rgba(20, 20, 20, 0.95);
  backdrop-filter: blur(12px);
  -webkit-backdrop-filter: blur(12px);
  border-radius: 14px;
  overflow: hidden;
  z-index: 4;
  box-shadow: 0 10px 40px rgba(0, 0, 0, 0.6);
  display: flex;
  flex-direction: column;
}

.ps-lb-minimap-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 10px 14px;
  color: #fff;
  font-size: 13px;
  border-bottom: 1px solid rgba(255, 255, 255, 0.08);
}

.ps-lb-minimap-frame {
  width: 100%;
  height: 260px;
  border: 0;
  display: block;
  background: #1a1a1a;
}

.ps-lb-minimap-link {
  padding: 10px 14px;
  font-size: 12px;
  color: rgba(255, 255, 255, 0.85);
  text-decoration: none;
  border-top: 1px solid rgba(255, 255, 255, 0.08);
}

.ps-lb-minimap-link:hover {
  color: #fff;
  background: rgba(255, 255, 255, 0.05);
}

/* Details flyout (collapsible) */
.ps-lb-details {
  position: absolute;
  right: 24px;
  bottom: 24px;
  width: 340px;
  max-width: calc(100vw - 48px);
  background: rgba(20, 20, 20, 0.92);
  backdrop-filter: blur(12px);
  -webkit-backdrop-filter: blur(12px);
  border-radius: 14px;
  padding: 16px 20px;
  color: #fff;
  z-index: 3;
  box-shadow: 0 10px 40px rgba(0, 0, 0, 0.5);
  max-height: 60vh;
  overflow-y: auto;
}

.ps-lb-details-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin: 0 0 12px 0;
}

.ps-lb-details-header h3 {
  margin: 0;
  font-size: 15px;
  font-weight: 600;
}

.ps-lb-info-list {
  margin: 0;
  display: grid;
  grid-template-columns: max-content 1fr;
  gap: 8px 12px;
  font-size: 13px;
}

.ps-lb-info-list dt {
  font-weight: 600;
  color: rgba(255, 255, 255, 0.6);
}

.ps-lb-info-list dd {
  margin: 0;
  word-break: break-word;
}

.ps-lb-info-filename {
  font-family: var(--font-face, monospace);
  font-size: 12px;
  opacity: 0.8;
}

.ps-lb-chip {
  display: inline-block;
  padding: 2px 8px;
  margin: 2px 4px 2px 0;
  background: rgba(255, 255, 255, 0.12);
  border-radius: 999px;
  font-size: 12px;
}

@media (max-width: 600px) {
  .ps-lb-stage {
    padding: 60px 8px;
  }

  .ps-lb-nav {
    width: 44px;
    height: 44px;
  }

  .ps-lb-pill {
    left: 12px;
    right: 12px;
    bottom: 12px;
    max-width: none;
  }

  .ps-lb-pill-date {
    font-size: 16px;
  }

  .ps-lb-minimap {
    left: 12px;
    right: 12px;
    bottom: 110px;
    width: auto;
    max-width: none;
  }

  .ps-lb-details {
    left: 12px;
    right: 12px;
    bottom: 12px;
    width: auto;
    max-width: none;
    max-height: 50vh;
  }
}

@media (prefers-reduced-motion: reduce) {
  .ps-lb-kenburns .ps-lb-image {
    animation: none;
  }
  .ps-lb-pill {
    transition: none;
  }
}
</style>
