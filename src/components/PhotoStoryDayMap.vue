<template>
  <div
    v-if="hasPoints"
    class="ps-day-map"
    :style="{ height: height + 'px' }"
    @click.stop
  >
    <div v-if="!leaflet && !fallback" class="ps-day-map-state">
      <NcLoadingIcon :size="16" />
    </div>
    <div v-else-if="fallback" class="ps-day-map-fallback">
      <MapMarker :size="16" />
      <span>{{ t('intravox', 'Map unavailable') }}</span>
    </div>
    <div v-else ref="mapEl" class="ps-day-map-canvas"></div>
  </div>
</template>

<script>
import { translate, translatePlural } from '@nextcloud/l10n';
import { NcLoadingIcon } from '@nextcloud/vue';
import MapMarker from 'vue-material-design-icons/MapMarker.vue';

// Module-level cache so we only import Leaflet once across all day-maps on a page
let leafletPromise = null;
function loadLeafletModule() {
  if (!leafletPromise) {
    leafletPromise = Promise.all([
      import('leaflet'),
      import('leaflet/dist/leaflet.css'),
    ]).then(([leaflet]) => leaflet.default || leaflet).catch(() => null);
  }
  return leafletPromise;
}

export default {
  name: 'PhotoStoryDayMap',
  components: { NcLoadingIcon, MapMarker },
  props: {
    points: { type: Array, required: true },
    height: { type: Number, default: 160 },
    tileUrl: { type: String, default: 'https://tile.openstreetmap.org/{z}/{x}/{y}.png' },
    attribution: { type: String, default: '© OSM' },
    maxZoom: { type: Number, default: 19 },
  },
  emits: ['photo-click'],
  data() {
    return {
      leaflet: null,
      fallback: false,
      mapInstance: null,
      // Lazy-init: only instantiate Leaflet once this map scrolls near viewport.
      // Prevents 30 simultaneous L.map() calls on initial paint of a long timeline.
      hasIntersected: false,
      observer: null,
    };
  },
  computed: {
    validPoints() {
      return (this.points || []).filter(
        p => p && typeof p.lat === 'number' && typeof p.lon === 'number'
          && Number.isFinite(p.lat) && Number.isFinite(p.lon)
      );
    },
    hasPoints() {
      return this.validPoints.length > 0;
    },
  },
  watch: {
    points: {
      deep: true,
      handler(newP, oldP) {
        if (!this.leaflet || !this.hasIntersected) return;
        // Skip when nothing meaningful changed. Compare on count + first/last
        // file_id; full deep comparison would be O(N) per render-tick.
        const fp = (arr) => Array.isArray(arr)
          ? `${arr.length}:${arr[0]?.file_id || ''}:${arr[arr.length - 1]?.file_id || ''}`
          : '';
        if (fp(newP) === fp(oldP)) return;
        // Debounce: infinite-scroll page-merges can mutate points rapidly. Each
        // initMap() tears down + re-creates the Leaflet instance (expensive).
        clearTimeout(this._pointsDebounce);
        this._pointsDebounce = setTimeout(() => {
          this.initMap();
        }, 250);
      },
    },
  },
  mounted() {
    if (!this.hasPoints) return;
    // Defer Leaflet init until the container is near the viewport.
    // Without IntersectionObserver (older browsers) we fall back to immediate init.
    if (typeof IntersectionObserver === 'undefined') {
      this.activateMap();
      return;
    }
    this.observer = new IntersectionObserver((entries) => {
      for (const e of entries) {
        if (e.isIntersecting) {
          this.activateMap();
          if (this.observer) {
            this.observer.disconnect();
            this.observer = null;
          }
          break;
        }
      }
    }, { rootMargin: '500px' });
    // Observe the component's root element (the .ps-day-map div).
    this.observer.observe(this.$el);
    // Safety net: if IO hasn't triggered within 1.5s (e.g. because the parent
    // hadn't laid out yet at mount time, or the element is somehow non-observable),
    // activate the map unconditionally. Better to render a few extra Leaflet
    // instances than to silently fail.
    this._activateFallbackTimer = setTimeout(() => {
      if (!this.hasIntersected) {
        this.activateMap();
        if (this.observer) {
          this.observer.disconnect();
          this.observer = null;
        }
      }
    }, 1500);
  },
  beforeUnmount() {
    if (this._activateFallbackTimer) {
      clearTimeout(this._activateFallbackTimer);
      this._activateFallbackTimer = null;
    }
    if (this._pointsDebounce) {
      clearTimeout(this._pointsDebounce);
      this._pointsDebounce = null;
    }
    if (this.observer) {
      this.observer.disconnect();
      this.observer = null;
    }
    if (this.mapInstance) {
      this.mapInstance.remove();
      this.mapInstance = null;
    }
  },
  methods: {
    t(app, text, vars) {
      return translate(app, text, vars);
    },
    async activateMap() {
      if (this.hasIntersected) return;
      this.hasIntersected = true;
      const L = await loadLeafletModule();
      if (!L) {
        this.fallback = true;
        return;
      }
      this.leaflet = L;
      await this.$nextTick();
      this.initMap();
    },
    initMap() {
      if (!this.leaflet || !this.$refs.mapEl) return;
      const L = this.leaflet;

      if (this.mapInstance) {
        this.mapInstance.remove();
        this.mapInstance = null;
      }

      const pts = this.validPoints;
      if (pts.length === 0) return;

      // Initial center so the tile layer has a valid view to render against.
      // Without this Leaflet skips tile loading until setView/fitBounds runs,
      // which can leave the map blank in some browser/timing combinations.
      const initialCenter = pts.length === 1 ? [pts[0].lat, pts[0].lon] : [pts[0].lat, pts[0].lon];
      this.mapInstance = L.map(this.$refs.mapEl, {
        scrollWheelZoom: false,
        zoomControl: false,
        attributionControl: false,
        center: initialCenter,
        zoom: 13,
      });

      L.tileLayer(this.tileUrl, {
        attribution: this.attribution,
        maxZoom: this.maxZoom || 19,
        crossOrigin: true,
        referrerPolicy: 'no-referrer-when-downgrade',
      }).addTo(this.mapInstance);

      const latlngs = [];
      for (const p of pts) {
        const ll = [p.lat, p.lon];
        latlngs.push(ll);
        const marker = L.circleMarker(ll, {
          radius: 6,
          color: '#fff',
          weight: 2,
          fillColor: '#1976d2',
          fillOpacity: 0.9,
        }).addTo(this.mapInstance);
        if (p.file_id) {
          marker.on('click', (e) => {
            // Stop propagation so the parent section doesn't also pick it up
            if (e.originalEvent) e.originalEvent.stopPropagation();
            this.$emit('photo-click', p.file_id);
          });
        }
        if (p.name) {
          marker.bindTooltip(p.name, { direction: 'top' });
        }
      }

      if (latlngs.length === 1) {
        this.mapInstance.setView(latlngs[0], 14);
      } else {
        this.mapInstance.fitBounds(latlngs, { padding: [20, 20], maxZoom: 15 });
      }

      // Force Leaflet to recompute its container size after the next paint.
      // Required when the component renders inside a layout that isn't fully
      // sized at mount time (sticky sections, aspect-ratio grids, etc.).
      const map = this.mapInstance;
      requestAnimationFrame(() => {
        try { map.invalidateSize(false); } catch (e) { /* map already removed */ }
      });
    },
  },
};
</script>

<style scoped>
.ps-day-map {
  width: 100%;
  border-radius: var(--border-radius-large);
  overflow: hidden;
  background: var(--color-background-dark);
  margin-bottom: 12px;
  /* isolation creates an own stacking context so Leaflet's internal
     panes (default z-index 200-700) can't render over the sticky
     topbar (.intravox-topbar, z-index: 100) when scrolling. */
  position: relative;
  z-index: 0;
  isolation: isolate;
}

.ps-day-map-canvas {
  width: 100%;
  height: 100%;
}

.ps-day-map-state,
.ps-day-map-fallback {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  width: 100%;
  height: 100%;
  font-size: 12px;
  color: var(--color-text-maxcontrast);
}
</style>
