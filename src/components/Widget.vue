<template>
  <div class="widget" :class="`widget-${widget.type}`" :style="textColorStyle">
    <!-- Text Widget with Inline Editing -->
    <div v-if="widget.type === 'text'" class="widget-text">
      <InlineTextEditor
        v-if="editable"
        v-model="localContent"
        :editable="true"
        :placeholder="t('Enter text...')"
        @focus="$emit('focus')"
        @blur="onBlur"
      />
      <div v-else v-html="sanitizeHtml(widget.content || '')"></div>
    </div>

    <!-- Heading Widget -->
    <component
      v-else-if="widget.type === 'heading'"
      :is="`h${widget.level}`"
      class="widget-heading"
      v-html="sanitizedContent"
    ></component>

    <!-- Image Widget -->
    <div v-else-if="widget.type === 'image'" class="widget-image">
      <!-- Image with link -->
      <a
        v-if="widget.src && hasImageLink"
        :href="getImageLinkHref()"
        :target="widget.linkType === 'external' ? '_blank' : '_self'"
        :rel="widget.linkType === 'external' ? 'noopener noreferrer' : ''"
        class="image-link"
        @click="handleImageLinkClick"
      >
        <img
          :src="getImageUrl(widget.src)"
          :alt="widget.alt"
          :style="getImageStyle()"
          loading="lazy"
        />
      </a>
      <!-- Image without link -->
      <img
        v-else-if="widget.src"
        :src="getImageUrl(widget.src)"
        :alt="widget.alt"
        :style="getImageStyle()"
        loading="lazy"
      />
      <div v-else class="placeholder">
        <span>{{ t('No image selected') }}</span>
      </div>
    </div>

    <!-- Links Widget (Grid of Links) -->
    <LinksWidget
      v-else-if="widget.type === 'links'"
      :widget="widget"
      @navigate="$emit('navigate', $event)"
    />

    <!-- File Widget -->
    <div v-else-if="widget.type === 'file'" class="widget-file">
      <a :href="getFileUrl(widget.path)" class="file-link">
        <span class="file-icon">📄</span>
        <span class="file-name">{{ widget.name }}</span>
      </a>
    </div>

    <!-- Divider Widget -->
    <div
      v-else-if="widget.type === 'divider'"
      class="widget-divider"
      :style="getDividerStyle()"
    ></div>

    <!-- Video Widget -->
    <div v-else-if="widget.type === 'video'" class="widget-video">
      <!-- Blocked Video - domain not in whitelist -->
      <div v-if="widget.blocked" class="video-blocked">
        <div class="video-blocked-icon">🚫</div>
        <p class="video-blocked-title">{{ t('Video service not allowed') }}</p>
        <p v-if="widget.originalSrc" class="video-blocked-url">
          <span class="url-label">URL:</span>
          <code class="url-value">{{ widget.originalSrc }}</code>
        </p>
        <p v-else-if="widget.blockedDomain" class="video-blocked-url">
          <span class="url-label">{{ t('Domain') }}:</span>
          <code class="url-value">{{ widget.blockedDomain }}</code>
        </p>
        <p class="video-blocked-hint">
          {{ t('Please contact your administrator if you need access to this video service.') }}
        </p>
      </div>

      <!-- Embed Video (YouTube, Vimeo, PeerTube, etc.) -->
      <div v-else-if="widget.provider !== 'local' && widget.src" class="video-embed-wrapper">
        <div class="video-container">
          <iframe
            :src="getEmbedUrl(widget)"
            :title="widget.title || t('Video')"
            frameborder="0"
            allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; fullscreen"
            allowfullscreen
            loading="lazy"
            referrerpolicy="strict-origin-when-cross-origin"
            @load="onVideoEmbedLoad"
            @error="onVideoEmbedError"
          ></iframe>
        </div>
      </div>

      <!-- Local Video File -->
      <div v-else-if="widget.provider === 'local' && widget.src" class="video-container video-local">
        <video
          :src="getVideoUrl(widget.src)"
          :title="widget.title"
          controls
          playsinline
          :autoplay="widget.autoplay"
          :loop="widget.loop"
          :muted="widget.muted || widget.autoplay"
          :preload="widget.autoplay ? 'auto' : 'metadata'"
          @error="onLocalVideoError"
        >
          {{ t('Your browser does not support HTML5 video.') }}
        </video>
        <!-- Error message for local videos -->
        <div v-if="localVideoError" class="video-error-overlay">
          <p class="error-message">{{ t('Video cannot be played') }}</p>
          <p class="error-reason">{{ localVideoError }}</p>
        </div>
      </div>

      <!-- Placeholder -->
      <div v-else class="placeholder">
        <span>{{ t('No video selected') }}</span>
      </div>

      <p v-if="widget.title && !widget.blocked" class="video-title">{{ widget.title }}</p>
    </div>

    <!-- Unknown Widget Type -->
    <div v-else class="widget-unknown">
      {{ t('Unknown widget type: {type}', { type: widget.type }) }}
    </div>
  </div>
</template>

<script>
import { translate as t } from '@nextcloud/l10n';
import { generateUrl } from '@nextcloud/router';
import InlineTextEditor from './InlineTextEditor.vue';
import LinksWidget from './LinksWidget.vue';
import { markdownToHtml } from '../utils/markdownSerializer.js';

export default {
  name: 'Widget',
  components: {
    InlineTextEditor,
    LinksWidget
  },
  props: {
    widget: {
      type: Object,
      required: true
    },
    pageId: {
      type: String,
      required: true
    },
    editable: {
      type: Boolean,
      default: false
    },
    rowBackgroundColor: {
      type: String,
      default: ''
    }
  },
  emits: ['update', 'focus', 'blur', 'navigate'],
  data() {
    return {
      localContent: this.widget.content || '',
      localVideoError: null
    };
  },
  computed: {
    sanitizedContent() {
      return this.sanitizeHtml(this.widget.content || '');
    },
    hasImageLink() {
      // Check if image has a valid link configured
      if (this.widget.linkType === 'internal') {
        return !!this.widget.linkPageId;
      }
      if (this.widget.linkType === 'external') {
        return !!this.widget.linkUrl;
      }
      return false;
    },
    textColorStyle() {
      // Map background colors to their matching text colors (Nextcloud pattern)
      // Each background has a paired text color for optimal contrast
      const bgColor = this.rowBackgroundColor || '';

      const colorMap = {
        'var(--color-primary-element)': 'var(--color-primary-element-text)',
        'var(--color-primary-element-light)': 'var(--color-primary-element-light-text)',
        'var(--color-error)': 'var(--color-error-text)',
        'var(--color-warning)': 'var(--color-warning-text)',
        'var(--color-success)': 'var(--color-success-text)',
        'var(--color-background-dark)': 'var(--color-main-text)',
        'var(--color-background-hover)': 'var(--color-main-text)',
      };

      const textColor = colorMap[bgColor] || 'var(--color-main-text)';
      return { color: textColor };
    }
  },
  watch: {
    'widget.content'(newValue) {
      this.localContent = newValue || '';
    },
    localContent(newValue) {
      // Emit update immediately when content changes (not just on blur)
      if (newValue !== this.widget.content) {
        this.$emit('update', {
          ...this.widget,
          content: newValue
        });
      }
    }
  },
  methods: {
    t(key, vars = {}) {
      return t('intravox', key, vars);
    },
    sanitizeHtml(content) {
      if (!content) return '';
      // Use the markdown serializer to convert markdown to HTML
      return markdownToHtml(content);
    },
    onBlur() {
      // No need to save here anymore - watcher handles it
      this.$emit('blur');
    },
    getImageUrl(filename) {
      if (!filename) return '';
      // Remove legacy prefixes if present
      const cleanFilename = filename.replace(/^(📷 images\/|images\/|_media\/)/, '');
      // Media served via unified API
      return generateUrl(`/apps/intravox/api/pages/${this.pageId}/media/${cleanFilename}`);
    },
    getFileUrl(filename) {
      if (!filename) return '';
      // Files are served via the IntraVox API with pageId
      return generateUrl(`/apps/intravox/api/pages/${this.pageId}/files/${filename}`);
    },
    getVideoUrl(filename) {
      if (!filename) return '';
      // Remove legacy prefixes if present
      const cleanFilename = filename.replace(/^(videos\/|_media\/)/, '');
      // Media served via unified API
      return generateUrl(`/apps/intravox/api/pages/${this.pageId}/media/${cleanFilename}`);
    },
    getImageStyle() {
      const style = {};

      if (this.widget.width) {
        // Custom width - height auto maintains aspect ratio
        style.width = `${this.widget.width}px`;
        style.height = 'auto';
        style.maxWidth = '100%'; // Don't overflow container
      } else {
        // Full width - natural aspect ratio with cropping
        style.width = '100%';
        style.height = 'auto';
        style.maxHeight = '500px';
        style.objectFit = 'cover';

        // Set vertical position for cropping
        const position = this.widget.objectPosition || 'center';
        style.objectPosition = `center ${position}`;
      }

      return style;
    },
    getDividerStyle() {
      const style = {};
      const bgColor = this.rowBackgroundColor || '';

      // Dark backgrounds get light divider, light backgrounds get primary divider
      const darkBackgrounds = [
        'var(--color-primary-element)',
        'var(--color-error)',
        'var(--color-warning)',
        'var(--color-success)',
      ];

      if (darkBackgrounds.includes(bgColor)) {
        style.background = 'var(--color-primary-element-light)';
      } else {
        style.background = 'var(--color-primary-element)';
      }

      return style;
    },
    getEmbedUrl(widget) {
      if (!widget.src) return '';

      let url = widget.src;

      // Parse URL to add/modify parameters
      try {
        const urlObj = new URL(url);
        const hostname = urlObj.hostname.toLowerCase();

        // YouTube
        if (hostname.includes('youtube.com') || hostname.includes('youtube-nocookie.com')) {
          if (widget.autoplay) {
            urlObj.searchParams.set('autoplay', '1');
            urlObj.searchParams.set('mute', '1'); // Required for autoplay
          }
          if (widget.loop) {
            urlObj.searchParams.set('loop', '1');
            // YouTube loop requires playlist parameter with video ID
            const videoId = urlObj.pathname.split('/').pop();
            if (videoId) {
              urlObj.searchParams.set('playlist', videoId);
            }
          }
        }
        // Vimeo
        else if (hostname.includes('vimeo.com')) {
          if (widget.autoplay) {
            urlObj.searchParams.set('autoplay', '1');
            urlObj.searchParams.set('muted', '1');
          }
          if (widget.loop) {
            urlObj.searchParams.set('loop', '1');
          }
        }
        // PeerTube
        else if (urlObj.pathname.includes('/videos/embed/')) {
          if (widget.autoplay) {
            urlObj.searchParams.set('autoplay', '1');
            urlObj.searchParams.set('muted', '1');
          }
          if (widget.loop) {
            urlObj.searchParams.set('loop', '1');
          }
        }
        // Dailymotion
        else if (hostname.includes('dailymotion.com')) {
          if (widget.autoplay) {
            urlObj.searchParams.set('autoplay', '1');
            urlObj.searchParams.set('mute', '1');
          }
          // Dailymotion doesn't have a loop parameter in embed
        }
        // Twitch
        else if (hostname.includes('twitch.tv')) {
          if (widget.autoplay) {
            urlObj.searchParams.set('autoplay', 'true');
            urlObj.searchParams.set('muted', 'true');
          }
        }
        // Generic fallback - try common parameter names
        else {
          if (widget.autoplay) {
            urlObj.searchParams.set('autoplay', '1');
          }
          if (widget.loop) {
            urlObj.searchParams.set('loop', '1');
          }
        }

        return urlObj.toString();
      } catch (e) {
        // Invalid URL, return as-is
        return url;
      }
    },
    getVideoPlatformName(src) {
      if (!src) return 'video platform';
      try {
        const url = new URL(src);
        const hostname = url.hostname.toLowerCase();

        if (hostname.includes('youtube.com') || hostname.includes('youtube-nocookie.com')) {
          return 'YouTube';
        } else if (hostname.includes('vimeo.com')) {
          return 'Vimeo';
        } else if (hostname.includes('dailymotion.com')) {
          return 'Dailymotion';
        } else if (hostname.includes('twitch.tv')) {
          return 'Twitch';
        } else if (url.pathname.includes('/videos/embed/')) {
          // PeerTube instances - use domain name
          return hostname.replace('www.', '');
        } else {
          return hostname.replace('www.', '');
        }
      } catch (e) {
        return 'video platform';
      }
    },
    getOriginalVideoUrl(src) {
      if (!src) return '#';
      try {
        const url = new URL(src);
        const hostname = url.hostname.toLowerCase();

        // Convert embed URLs back to watch URLs
        if (hostname.includes('youtube.com') || hostname.includes('youtube-nocookie.com')) {
          // YouTube embed: /embed/VIDEO_ID -> watch?v=VIDEO_ID
          const videoId = url.pathname.replace('/embed/', '');
          return `https://www.youtube.com/watch?v=${videoId}`;
        } else if (hostname.includes('player.vimeo.com')) {
          // Vimeo player: /video/VIDEO_ID -> vimeo.com/VIDEO_ID
          const videoId = url.pathname.replace('/video/', '');
          return `https://vimeo.com/${videoId}`;
        } else if (hostname.includes('dailymotion.com')) {
          // Dailymotion embed: /embed/video/VIDEO_ID -> dailymotion.com/video/VIDEO_ID
          const videoId = url.pathname.replace('/embed/video/', '');
          return `https://www.dailymotion.com/video/${videoId}`;
        } else if (url.pathname.includes('/videos/embed/')) {
          // PeerTube: /videos/embed/VIDEO_ID -> /videos/watch/VIDEO_ID
          return src.replace('/videos/embed/', '/videos/watch/');
        }
        // Return original for unknown platforms
        return src;
      } catch (e) {
        return src || '#';
      }
    },
    onVideoEmbedLoad() {
      // Video iframe loaded successfully - no action needed
    },
    onVideoEmbedError() {
      // iframe errors are limited, but we log for debugging
      console.warn('Video embed may have failed to load');
    },
    onLocalVideoError(event) {
      const video = event.target;
      const error = video.error;

      if (error) {
        switch (error.code) {
          case MediaError.MEDIA_ERR_ABORTED:
            this.localVideoError = this.t('Video playback was aborted.');
            break;
          case MediaError.MEDIA_ERR_NETWORK:
            this.localVideoError = this.t('A network error occurred while loading the video.');
            break;
          case MediaError.MEDIA_ERR_DECODE:
            this.localVideoError = this.t('The video file is corrupted or uses an unsupported format.');
            break;
          case MediaError.MEDIA_ERR_SRC_NOT_SUPPORTED:
            this.localVideoError = this.t('The video format is not supported by your browser.');
            break;
          default:
            this.localVideoError = this.t('An unknown error occurred while playing the video.');
        }
      }
    },
    getImageLinkHref() {
      // Return the appropriate href for the image link
      if (this.widget.linkType === 'internal' && this.widget.linkPageId) {
        return `#${this.widget.linkPageId}`;
      }
      if (this.widget.linkType === 'external' && this.widget.linkUrl) {
        return this.widget.linkUrl;
      }
      return '#';
    },
    handleImageLinkClick(event) {
      // Handle internal link navigation
      if (this.widget.linkType === 'internal' && this.widget.linkPageId) {
        event.preventDefault();
        this.$emit('navigate', this.widget.linkPageId);
      }
      // External links follow their default behavior (open in new tab)
    }
  }
};
</script>

<style scoped>
.widget {
  width: 100%;
  max-width: 100%;
  box-sizing: border-box;
}

/* Text Widget */
.widget-text {
  width: 100%;
  color: inherit;
  line-height: 1.5;
  margin-top: 0;
  margin-bottom: 12px;
}

.widget-text :deep(p) {
  margin: 0 0 0.5em 0;
  color: inherit !important;
}

.widget-text :deep(p:last-child) {
  margin-bottom: 0;
}

.widget-text :deep(ul),
.widget-text :deep(ol) {
  padding-left: 1.5em;
  margin: 0.5em 0;
  list-style-position: outside;
  color: inherit !important;
}

.widget-text :deep(ul) {
  list-style-type: disc;
}

.widget-text :deep(ol) {
  list-style-type: decimal;
}

.widget-text :deep(li) {
  margin: 0.25em 0;
  color: inherit !important;
  display: list-item;
}

.widget-text :deep(strong) {
  font-weight: bold;
  color: inherit !important;
}

.widget-text :deep(em) {
  font-style: italic;
  color: inherit !important;
}

.widget-text :deep(u) {
  text-decoration: underline;
  color: inherit !important;
}

.widget-text :deep(s) {
  text-decoration: line-through;
  color: inherit !important;
}

.widget-text :deep(a) {
  color: var(--color-primary);
  text-decoration: underline;
}

.widget-text :deep(h1),
.widget-text :deep(h2),
.widget-text :deep(h3),
.widget-text :deep(h4),
.widget-text :deep(h5),
.widget-text :deep(h6) {
  margin: 0.5em 0 !important;
  font-weight: 600 !important;
  color: inherit !important;
}

.widget-text :deep(h1) { font-size: 32px !important; }
.widget-text :deep(h2) { font-size: 28px !important; }
.widget-text :deep(h3) { font-size: 24px !important; }
.widget-text :deep(h4) { font-size: 20px !important; }
.widget-text :deep(h5) { font-size: 18px !important; }
.widget-text :deep(h6) { font-size: 16px !important; }

/* Heading Widget */
.widget-heading {
  margin: 16px 0 8px 0;
  color: inherit;
  font-weight: 600;
}

.widget-heading h1 { font-size: 32px; }
.widget-heading h2 { font-size: 28px; }
.widget-heading h3 { font-size: 24px; }
.widget-heading h4 { font-size: 20px; }
.widget-heading h5 { font-size: 18px; }
.widget-heading h6 { font-size: 16px; }

/* Image Widget */
.widget-image {
  width: 100%;
  max-width: 100%;
  box-sizing: border-box;
  margin: 12px 0;
}

.widget-image img {
  max-width: 100%;
  width: 100%;
  height: auto;
  border-radius: var(--border-radius-container-large);
  display: block;
  object-fit: contain;
}

.widget-image .placeholder {
  padding: 40px;
  background: var(--color-background-dark);
  border: 2px dashed var(--color-border);
  border-radius: var(--border-radius-large);
  text-align: center;
  color: var(--color-text-maxcontrast);
}

/* Clickable image link */
.widget-image .image-link {
  display: block;
  text-decoration: none;
  border-radius: var(--border-radius-container-large);
  transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.widget-image .image-link:hover {
  transform: scale(1.01);
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
}

.widget-image .image-link:focus {
  outline: 2px solid var(--color-primary);
  outline-offset: 2px;
}

.widget-image .image-link img {
  cursor: pointer;
}

/* File Widget */
.widget-file {
  padding: 12px;
  background: var(--color-background-hover);
  border-radius: var(--border-radius-large);
  margin: 12px 0;
}

.file-link {
  display: flex;
  align-items: center;
  gap: 10px;
  color: inherit;
  text-decoration: none;
}

.file-link:hover {
  color: var(--color-primary);
}

.file-icon {
  font-size: 24px;
}

.file-name {
  font-weight: 500;
}

/* Divider Widget */
.widget-divider {
  width: 100%;
  height: 2px;
  margin: 16px 0;
  border: none;
}

/* Video Widget */
.widget-video {
  width: 100%;
  margin: 12px 0;
}

.video-container {
  position: relative;
  width: 100%;
  padding-bottom: 56.25%; /* 16:9 aspect ratio */
  background: var(--color-background-dark);
  border-radius: var(--border-radius-container-large);
  overflow: hidden;
}

.video-container iframe,
.video-container video {
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  border: none;
}

.video-local video {
  object-fit: contain;
}

.video-title {
  margin-top: 8px;
  font-size: 14px;
  text-align: center;
  opacity: 0.8;
  color: inherit;
}

/* Video embed wrapper */
.video-embed-wrapper {
  width: 100%;
}

/* Video error overlay for local videos */
.video-error-overlay {
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 20px;
  box-sizing: border-box;
  background: rgba(0, 0, 0, 0.85);
  z-index: 10;
  text-align: center;
}

.error-message {
  font-size: 16px;
  font-weight: 600;
  color: #fff;
  margin: 0 0 8px 0;
}

.error-reason {
  font-size: 13px;
  color: rgba(255, 255, 255, 0.8);
  margin: 0;
  max-width: 400px;
}

.widget-video .placeholder {
  padding: 40px;
  background: var(--color-background-dark);
  border: 2px dashed var(--color-border);
  border-radius: var(--border-radius-large);
  text-align: center;
  color: var(--color-text-maxcontrast);
}

/* Blocked video message - matches WidgetEditor preview styling */
.video-blocked {
  padding: 24px;
  background: var(--color-background-hover);
  border: 2px dashed var(--color-warning);
  border-radius: var(--border-radius-large);
  text-align: center;
}

.video-blocked-icon {
  font-size: 48px;
  line-height: 1;
  margin-bottom: 12px;
  filter: grayscale(0);
}

.video-blocked-title {
  font-size: 16px;
  font-weight: 600;
  color: var(--color-warning-text);
  background-color: var(--color-warning);
  display: inline-block;
  padding: 4px 12px;
  border-radius: var(--border-radius);
  margin: 0 0 12px 0;
}

.video-blocked-message {
  font-size: 14px;
  color: var(--color-main-text);
  margin: 0 0 4px 0;
  max-width: 400px;
  margin-left: auto;
  margin-right: auto;
}

.video-blocked-url {
  margin: 12px 0;
  padding: 8px 12px;
  background: var(--color-background-dark);
  border-radius: var(--border-radius);
  max-width: 100%;
  overflow: hidden;
}

.video-blocked-url .url-label {
  font-size: 12px;
  font-weight: 600;
  color: var(--color-text-maxcontrast);
  margin-right: 8px;
}

.video-blocked-url .url-value {
  font-size: 12px;
  font-family: var(--font-monospace, monospace);
  color: var(--color-main-text);
  word-break: break-all;
  background: none;
  padding: 0;
}

.video-blocked-hint {
  font-size: 13px;
  color: var(--color-text-maxcontrast);
  margin: 0;
  font-style: italic;
}

/* Unknown Widget */
.widget-unknown {
  padding: 20px;
  background: var(--color-error);
  color: white;
  border-radius: var(--border-radius-large);
  text-align: center;
}

/* Mobile styles */
@media (max-width: 768px) {
  .widget-text :deep(h1) { font-size: 24px !important; }
  .widget-text :deep(h2) { font-size: 22px !important; }
  .widget-text :deep(h3) { font-size: 20px !important; }
  .widget-text :deep(h4) { font-size: 18px !important; }
  .widget-text :deep(h5) { font-size: 16px !important; }
  .widget-text :deep(h6) { font-size: 14px !important; }

  .widget-heading h1 { font-size: 24px; }
  .widget-heading h2 { font-size: 22px; }
  .widget-heading h3 { font-size: 20px; }
  .widget-heading h4 { font-size: 18px; }
  .widget-heading h5 { font-size: 16px; }
  .widget-heading h6 { font-size: 14px; }

  .widget-text {
    font-size: 14px;
  }

  .widget-image img {
    max-width: 100%;
    width: 100%;
    height: auto;
    object-fit: contain;
  }
}
</style>
