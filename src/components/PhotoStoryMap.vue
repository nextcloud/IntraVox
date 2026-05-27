<template>
  <div class="ps-map" :class="{ 'ps-map--fullscreen': fullscreen }">
    <div v-if="loading" class="ps-map-state">
      <NcLoadingIcon :size="24" />
      <span>{{ t('Loading map...') }}</span>
    </div>

    <div v-else-if="fallback" class="ps-map-fallback">
      <Map :size="32" />
      <p>{{ t('Map view requires the Leaflet library — not installed.') }}</p>
      <ul v-if="clusters.length" class="ps-map-list">
        <li
          v-for="c in clusters"
          :key="c.location"
          class="ps-map-list-item"
          tabindex="0"
          role="button"
          :aria-label="t('Show {count} photos in {location}', { count: c.count, location: c.location })"
          @click="$emit('cluster-click', { location: c.location, photo_ids: c.photo_ids })"
          @keydown.enter="$emit('cluster-click', { location: c.location, photo_ids: c.photo_ids })"
          @keydown.space.prevent="$emit('cluster-click', { location: c.location, photo_ids: c.photo_ids })"
        >
          <MapMarker :size="16" />
          <strong>{{ c.location }}</strong>
          <span>{{ c.count }}</span>
        </li>
      </ul>
    </div>

    <div v-else-if="error" class="ps-map-state ps-map-error">
      <AlertCircle :size="24" />
      <span>{{ error }}</span>
    </div>

    <div v-else ref="mapEl" class="ps-map-canvas"></div>
  </div>
</template>

<script>
import axios from '@nextcloud/axios';
import { generateUrl } from '@nextcloud/router';
import { translate as t } from '@nextcloud/l10n';
import { NcLoadingIcon } from '@nextcloud/vue';
import Map from 'vue-material-design-icons/Map.vue';
import MapMarker from 'vue-material-design-icons/MapMarker.vue';
import AlertCircle from 'vue-material-design-icons/AlertCircle.vue';

export default {
  name: 'PhotoStoryMap',
  components: { NcLoadingIcon, Map, MapMarker, AlertCircle },
  props: {
    folderPath: { type: String, required: true },
    fullscreen: { type: Boolean, default: false },
  },
  emits: ['cluster-click'],
  data() {
    return {
      clusters: [],
      loading: true,
      error: null,
      fallback: false,
      leaflet: null,
      mapInstance: null,
      markersLayer: null,
      mapSettings: {
        enabled: true,
        tile_url: 'https://tile.openstreetmap.org/{z}/{x}/{y}.png',
        attribution: '© OpenStreetMap contributors',
        max_zoom: 19,
      },
    };
  },
  watch: {
    folderPath() {
      this.refresh();
    },
  },
  async mounted() {
    await this.loadLeaflet();
    await this.refresh();
  },
  beforeUnmount() {
    if (this.mapInstance) {
      this.mapInstance.remove();
      this.mapInstance = null;
    }
  },
  methods: {
    t(key, vars = {}) {
      return t('intravox', key, vars);
    },
    async loadLeaflet() {
      try {
        const [leaflet] = await Promise.all([
          import('leaflet'),
          import('leaflet/dist/leaflet.css'),
        ]);
        this.leaflet = leaflet.default || leaflet;
      } catch (e) {
        // Leaflet not bundled — graceful fallback
        this.leaflet = null;
        this.fallback = true;
      }
    },
    async refresh() {
      this.loading = true;
      this.error = null;
      try {
        const params = new URLSearchParams({ folder: this.folderPath });
        const url = generateUrl(`/apps/intravox/api/photo-story/clusters?${params.toString()}`);
        const res = await axios.get(url);
        this.clusters = res.data?.clusters || [];
        if (res.data?.map && typeof res.data.map === 'object') {
          this.mapSettings = { ...this.mapSettings, ...res.data.map };
        }
        // Honor admin global-disable: bail out without instantiating Leaflet at all.
        if (this.mapSettings.enabled === false) {
          this.loading = false;
          this.fallback = true;
          return;
        }

        if (this.fallback) {
          this.loading = false;
          return;
        }

        this.loading = false;
        // Allow DOM update before initializing map
        await this.$nextTick();
        this.initMap();
      } catch (e) {
        this.error = e.response?.data?.error || e.message || this.t('Failed to load map');
        this.loading = false;
      }
    },
    initMap() {
      if (!this.leaflet || !this.$refs.mapEl) return;

      const L = this.leaflet;

      // Destroy previous
      if (this.mapInstance) {
        this.mapInstance.remove();
        this.mapInstance = null;
      }

      this.mapInstance = L.map(this.$refs.mapEl, {
        scrollWheelZoom: false,
      }).setView([52.0, 5.0], 4);

      // Tile URL + attribution come from the admin-configurable mapSettings,
      // letting the admin point at OSM (default), MapTiler, self-hosted Protomaps, etc.
      L.tileLayer(this.mapSettings.tile_url, {
        attribution: this.mapSettings.attribution,
        maxZoom: this.mapSettings.max_zoom || 19,
        crossOrigin: true,
        referrerPolicy: 'no-referrer-when-downgrade',
      }).addTo(this.mapInstance);

      const points = [];
      this.markersLayer = L.layerGroup().addTo(this.mapInstance);

      for (const c of this.clusters) {
        if (!c.gps || typeof c.gps.lat !== 'number' || typeof c.gps.lon !== 'number') {
          continue;
        }
        const ll = [c.gps.lat, c.gps.lon];
        points.push(ll);

        const radius = Math.min(10 + Math.sqrt(c.count) * 3, 40);
        const marker = L.circleMarker(ll, {
          radius,
          color: '#fff',
          weight: 2,
          fillColor: '#1976d2',
          fillOpacity: 0.85,
        }).addTo(this.markersLayer);

        marker.bindTooltip(`${c.location} (${c.count})`, { direction: 'top' });
        marker.on('click', () => {
          this.$emit('cluster-click', {
            location: c.location,
            photo_ids: c.photo_ids,
          });
        });
      }

      if (points.length > 0) {
        this.mapInstance.fitBounds(points, { padding: [30, 30], maxZoom: 14 });
      }
    },
  },
};
</script>

<style scoped>
.ps-map {
  width: 100%;
  height: 300px;
  border-radius: var(--border-radius-large);
  overflow: hidden;
  background: var(--color-background-dark);
  position: relative;
}

.ps-map--fullscreen {
  height: 70vh;
}

.ps-map-canvas {
  width: 100%;
  height: 100%;
}

.ps-map-state,
.ps-map-fallback {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 8px;
  padding: 24px;
  height: 100%;
  color: var(--color-text-maxcontrast);
  text-align: center;
}

.ps-map-error {
  color: var(--color-error);
}

.ps-map-fallback p {
  margin: 0 0 8px 0;
  font-size: 13px;
}

.ps-map-list {
  list-style: none;
  margin: 0;
  padding: 0;
  width: 100%;
  max-width: 320px;
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.ps-map-list-item {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 6px 10px;
  background: var(--color-main-background);
  border-radius: var(--border-radius);
  cursor: pointer;
  font-size: 13px;
  color: var(--color-main-text);
}

.ps-map-list-item:hover {
  background: var(--color-background-hover);
}

.ps-map-list-item strong {
  flex: 1;
  font-weight: 500;
}

.ps-map-list-item span {
  font-variant-numeric: tabular-nums;
  color: var(--color-text-maxcontrast);
}
</style>
