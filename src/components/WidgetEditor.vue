<template>
  <NcModal @close="$emit('close')"
           :name="t('Edit widget')"
           size="large"
           class="widget-editor-modal">
    <div class="widget-editor-content">
        <!-- Text Widget -->
        <div v-if="localWidget.type === 'text'" class="form-group full-width-editor">
          <label>{{ t('Text:') }}</label>

          <!-- Tiptap Rich Text Editor -->
          <div v-if="editor" class="rich-text-toolbar">
            <button
              type="button"
              @click="editor.chain().focus().toggleBold().run()"
              :class="{ 'is-active': editor.isActive('bold') }"
              :title="t('Bold')"
              class="toolbar-btn"
            >
              <strong>B</strong>
            </button>
            <button
              type="button"
              @click="editor.chain().focus().toggleItalic().run()"
              :class="{ 'is-active': editor.isActive('italic') }"
              :title="t('Italic')"
              class="toolbar-btn"
            >
              <em>I</em>
            </button>
            <button
              type="button"
              @click="editor.chain().focus().toggleUnderline().run()"
              :class="{ 'is-active': editor.isActive('underline') }"
              :title="t('Underline')"
              class="toolbar-btn"
            >
              <u>U</u>
            </button>
            <button
              type="button"
              @click="editor.chain().focus().toggleStrike().run()"
              :class="{ 'is-active': editor.isActive('strike') }"
              :title="t('Strikethrough')"
              class="toolbar-btn"
            >
              <s>S</s>
            </button>
            <span class="toolbar-divider"></span>
            <button
              type="button"
              @click="editor.chain().focus().toggleBulletList().run()"
              :class="{ 'is-active': editor.isActive('bulletList') }"
              :title="t('Bullet list')"
              class="toolbar-btn"
            >
              • List
            </button>
            <button
              type="button"
              @click="editor.chain().focus().toggleOrderedList().run()"
              :class="{ 'is-active': editor.isActive('orderedList') }"
              :title="t('Numbered list')"
              class="toolbar-btn"
            >
              1. List
            </button>
          </div>

          <editor-content :editor="editor" class="rich-text-editor" />
        </div>

        <!-- Heading Widget -->
        <div v-else-if="localWidget.type === 'heading'">
          <div class="form-group">
            <label>{{ t('Heading:') }}</label>
            <input
              v-model="localWidget.content"
              type="text"
              :placeholder="t('Enter your heading...')"
              class="heading-text-input"
            />
          </div>
          <div class="form-group">
            <label>{{ t('Level:') }}</label>
            <select v-model.number="localWidget.level" class="heading-level-select">
              <option :value="1">{{ t('H1 - Largest') }}</option>
              <option :value="2">{{ t('H2') }}</option>
              <option :value="3">{{ t('H3') }}</option>
              <option :value="4">{{ t('H4') }}</option>
              <option :value="5">{{ t('H5') }}</option>
              <option :value="6">{{ t('H6 - Smallest') }}</option>
            </select>
          </div>
        </div>

        <!-- Image Widget -->
        <div v-else-if="localWidget.type === 'image'">
          <div class="form-group">
            <label>{{ t('Image:') }}</label>
            <NcButton
              type="primary"
              @click="openMediaPicker('image')"
            >
              <template #icon>
                <ImageIcon :size="20" />
              </template>
              {{ t('Browse media...') }}
            </NcButton>
          </div>
          <div v-if="localWidget.src" class="image-preview">
            <img :src="getImageUrl(localWidget.src)" :alt="localWidget.alt" />
          </div>
          <div class="form-group">
            <label>{{ t('Alt text:') }}</label>
            <input
              v-model="localWidget.alt"
              type="text"
              :placeholder="t('Description of the image...')"
              class="widget-input"
            />
          </div>
          <div class="form-group">
            <label>{{ t('Size:') }}</label>
            <div class="size-presets">
              <button
                type="button"
                :class="{ active: localWidget.width === 300 }"
                @click="setImageSize(300)"
                class="size-preset-btn"
              >
                {{ t('Small') }} (300px)
              </button>
              <button
                type="button"
                :class="{ active: localWidget.width === 500 }"
                @click="setImageSize(500)"
                class="size-preset-btn"
              >
                {{ t('Medium') }} (500px)
              </button>
              <button
                type="button"
                :class="{ active: localWidget.width === 800 }"
                @click="setImageSize(800)"
                class="size-preset-btn"
              >
                {{ t('Large') }} (800px)
              </button>
              <button
                type="button"
                :class="{ active: !localWidget.width || localWidget.width === null }"
                @click="setImageSize(null)"
                class="size-preset-btn"
              >
                {{ t('Full width') }}
              </button>
            </div>
          </div>
          <div v-if="localWidget.width" class="form-group">
            <label>{{ t('Custom width (pixels):') }}</label>
            <input
              v-model.number="localWidget.width"
              type="number"
              min="50"
              max="2000"
              class="widget-input"
              :placeholder="t('Width in pixels')"
            />
            <p class="size-hint">{{ t('Height will automatically adjust to maintain aspect ratio') }}</p>
          </div>
          <div v-if="!localWidget.width || localWidget.width === null" class="form-group">
            <label>{{ t('Vertical position:') }}</label>
            <div class="position-presets">
              <button
                type="button"
                :class="{ active: !localWidget.objectPosition || localWidget.objectPosition === 'center' }"
                @click="setObjectPosition('center')"
                class="size-preset-btn"
              >
                {{ t('Center') }}
              </button>
              <button
                type="button"
                :class="{ active: localWidget.objectPosition === 'top' }"
                @click="setObjectPosition('top')"
                class="size-preset-btn"
              >
                {{ t('Top') }}
              </button>
              <button
                type="button"
                :class="{ active: localWidget.objectPosition === 'bottom' }"
                @click="setObjectPosition('bottom')"
                class="size-preset-btn"
              >
                {{ t('Bottom') }}
              </button>
            </div>
            <p class="size-hint">{{ t('Choose which part of the image is visible when cropped') }}</p>
          </div>
          <!-- Image Link Section -->
          <div class="form-group">
            <label>{{ t('Link:') }}</label>
            <div class="size-presets">
              <button
                type="button"
                :class="{ active: !localWidget.linkType || localWidget.linkType === 'none' }"
                @click="setImageLinkType('none')"
                class="size-preset-btn"
              >
                {{ t('No link') }}
              </button>
              <button
                type="button"
                :class="{ active: localWidget.linkType === 'internal' }"
                @click="setImageLinkType('internal')"
                class="size-preset-btn"
              >
                {{ t('Internal page') }}
              </button>
              <button
                type="button"
                :class="{ active: localWidget.linkType === 'external' }"
                @click="setImageLinkType('external')"
                class="size-preset-btn"
              >
                {{ t('External URL') }}
              </button>
            </div>
          </div>
          <!-- Internal page selector -->
          <div v-if="localWidget.linkType === 'internal'" class="form-group">
            <label>{{ t('Select page:') }}</label>
            <PageTreeSelect
              v-model="localWidget.linkPageId"
              :placeholder="t('Select a page...')"
              :clearable="true"
            />
            <p class="size-hint">{{ t('Click on the image to navigate to this page') }}</p>
          </div>
          <!-- External URL input -->
          <div v-if="localWidget.linkType === 'external'" class="form-group">
            <label>{{ t('URL:') }}</label>
            <input
              v-model="localWidget.linkUrl"
              type="url"
              :placeholder="t('https://example.com')"
              class="widget-input"
            />
            <div class="link-target-options">
              <label class="checkbox-label">
                <input
                  type="checkbox"
                  v-model="localWidget.linkNewTab"
                />
                {{ t('Open in new tab') }}
              </label>
            </div>
            <p class="size-hint">{{ localWidget.linkNewTab ? t('Click on the image to open this URL in a new tab') : t('Click on the image to open this URL') }}</p>
          </div>
        </div>

        <!-- File Widget -->
        <div v-else-if="localWidget.type === 'file'">
          <div class="form-group">
            <label>{{ t('File name:') }}</label>
            <input
              v-model="localWidget.name"
              type="text"
              placeholder="Document.pdf"
              class="widget-input"
            />
          </div>
          <div class="form-group">
            <label>{{ t('File path (in IntraVox folder):') }}</label>
            <input
              v-model="localWidget.path"
              type="text"
              placeholder="documents/file.pdf"
              class="widget-input"
            />
          </div>
        </div>

        <!-- Spacer Widget -->
        <div v-else-if="localWidget.type === 'spacer'">
          <div class="form-group">
            <label>{{ t('Height (pixels):') }}</label>
            <input
              v-model.number="localWidget.height"
              type="number"
              min="10"
              max="200"
              placeholder="20"
              class="widget-input"
            />
          </div>
        </div>

        <!-- Video Widget -->
        <div v-else-if="localWidget.type === 'video'">
          <!-- Provider keuze -->
          <div class="form-group">
            <label>{{ t('Video source:') }}</label>
            <div class="size-presets">
              <button
                type="button"
                :class="{ active: localWidget.provider !== 'local' }"
                @click="localWidget.provider = 'embed'; localWidget.src = ''"
                class="size-preset-btn"
              >
                {{ t('Video URL') }}
              </button>
              <button
                type="button"
                :class="{ active: localWidget.provider === 'local' }"
                @click="localWidget.provider = 'local'; localWidget.src = ''"
                class="size-preset-btn"
              >
                {{ t('Local file') }}
              </button>
            </div>
          </div>

          <!-- Video URL (YouTube, Vimeo, PeerTube, etc.) -->
          <div v-if="localWidget.provider !== 'local'" class="form-group">
            <label>{{ t('Video URL:') }}</label>
            <input
              v-model="localWidget.src"
              type="url"
              placeholder="https://www.youtube.com/watch?v=... or https://vimeo.com/..."
              class="widget-input"
              @blur="convertVideoUrl"
              @input="debouncedConvertVideoUrl"
            />
            <p class="hint">{{ t('Paste a video URL from YouTube, Vimeo, PeerTube or other supported platforms.') }}</p>
            <p v-if="detectedPlatform" class="detected-platform">
              {{ t('Detected:') }} <strong>{{ detectedPlatform }}</strong>
            </p>
          </div>

          <!-- Lokaal bestand upload -->
          <div v-else class="form-group">
            <label>{{ t('Video:') }}</label>
            <NcButton
              type="primary"
              @click="openMediaPicker('video')"
            >
              <template #icon>
                <VideoIcon :size="20" />
              </template>
              {{ t('Browse media...') }}
            </NcButton>
            <div v-if="localWidget.src" class="current-video">
              {{ t('Current:') }} {{ localWidget.src }}
            </div>
          </div>

          <!-- Titel -->
          <div class="form-group">
            <label>{{ t('Title (optional):') }}</label>
            <input v-model="localWidget.title" type="text" class="widget-input" />
          </div>

          <!-- Playback Options -->
          <div class="form-group">
            <label>{{ t('Playback options:') }}</label>
            <div class="checkbox-group">
              <label class="checkbox-label">
                <input
                  type="checkbox"
                  v-model="localWidget.autoplay"
                />
                {{ t('Autoplay when page loads') }}
              </label>
              <p class="hint checkbox-hint">{{ t('Video will start automatically (muted for browser compatibility)') }}</p>
            </div>
            <div class="checkbox-group">
              <label class="checkbox-label">
                <input
                  type="checkbox"
                  v-model="localWidget.loop"
                />
                {{ t('Loop video') }}
              </label>
              <p class="hint checkbox-hint">{{ t('Video will restart when finished') }}</p>
            </div>
            <div class="checkbox-group">
              <label class="checkbox-label">
                <input
                  type="checkbox"
                  v-model="localWidget.muted"
                />
                {{ t('Muted') }}
              </label>
              <p class="hint checkbox-hint">{{ t('Video plays without sound (required for autoplay in most browsers)') }}</p>
            </div>
          </div>

          <!-- Preview -->
          <div v-if="embedUrl && localWidget.provider !== 'local'" class="video-preview">
            <p>{{ t('Preview:') }}</p>
            <!-- Blocked video warning - matches Widget.vue styling -->
            <div v-if="isVideoBlocked" class="video-blocked-preview">
              <div class="blocked-icon">🚫</div>
              <p class="blocked-title">{{ t('Video service not allowed') }}</p>
              <p v-if="localWidget.src" class="blocked-url">
                <span class="url-label">URL:</span>
                <code class="url-value">{{ localWidget.src }}</code>
              </p>
              <p class="blocked-hint">
                {{ t('Please contact your administrator if you need access to this video service.') }}
              </p>
            </div>
            <!-- Normal preview -->
            <div v-else class="preview-container" style="height:200px;">
              <iframe
                :src="embedUrl"
                allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; fullscreen"
                allowfullscreen
                referrerpolicy="strict-origin-when-cross-origin"
                style="width:100%;height:100%;border:none;"
              ></iframe>
            </div>
          </div>
          <div v-else-if="localWidget.provider === 'local' && localWidget.src" class="video-preview">
            <p>{{ t('Preview:') }}</p>
            <div class="preview-container" style="height:200px;">
              <video
                :src="getVideoUrl(localWidget.src)"
                controls
                muted
                style="width:100%;height:100%;object-fit:contain;"
              ></video>
            </div>
          </div>
          <!-- No URL entered yet hint -->
          <div v-else-if="localWidget.provider !== 'local' && !embedUrl" class="video-preview-hint">
            <p class="hint">{{ t('Enter a video URL above to see a preview.') }}</p>
          </div>
        </div>

      <div class="modal-footer">
        <NcButton @click="$emit('close')" type="secondary">
          {{ t('Cancel') }}
        </NcButton>
        <NcButton
          @click="save"
          type="primary"
          :disabled="isUploading || (localWidget.type === 'video' && !canSaveVideo)">
          {{ t('Save') }}
        </NcButton>
      </div>
    </div>

    <!-- Media Picker Dialog -->
    <MediaPicker
      v-if="showMediaPicker"
      :open="showMediaPicker"
      :page-id="pageId"
      :media-type="mediaPickerType"
      :title="mediaPickerType === 'image' ? t('Select Image') : t('Select Video')"
      @close="showMediaPicker = false"
      @select="handleMediaSelect"
    />
  </NcModal>
</template>

<script>
import axios from '@nextcloud/axios';
import { generateUrl } from '@nextcloud/router';
import { translate as t } from '@nextcloud/l10n';
import { NcButton, NcModal } from '@nextcloud/vue';
import PageTreeSelect from './PageTreeSelect.vue';
import MediaPicker from './MediaPicker.vue';
import { showError, showSuccess } from '@nextcloud/dialogs';
import ImageIcon from 'vue-material-design-icons/Image.vue';
import VideoIcon from 'vue-material-design-icons/Video.vue';

// Tiptap imports
import { Editor, EditorContent } from '@tiptap/vue-3';
import StarterKit from '@tiptap/starter-kit';
import Underline from '@tiptap/extension-underline';
import Placeholder from '@tiptap/extension-placeholder';

export default {
  name: 'WidgetEditor',
  components: {
    NcButton,
    NcModal,
    EditorContent,
    PageTreeSelect,
    MediaPicker,
    ImageIcon,
    VideoIcon
  },
  props: {
    widget: {
      type: Object,
      required: true
    },
    pageId: {
      type: String,
      required: true
    }
  },
  emits: ['close', 'save'],
  data() {
    return {
      localWidget: JSON.parse(JSON.stringify(this.widget)),
      editor: null,
      detectedPlatform: null,
      embedUrl: null,
      uploadLimitBytes: 50 * 1024 * 1024, // Default 50MB, will be fetched from server
      uploadLimitMB: 50,
      videoDomainWhitelist: [], // Whitelist of allowed video domains
      videoWhitelistLoaded: false, // Track if whitelist has been loaded
      convertVideoUrlTimeout: null, // Debounce timer for video URL conversion
      isUploading: false, // Track upload in progress
      showMediaPicker: false, // Control MediaPicker dialog visibility
      mediaPickerType: 'image' // 'image' or 'video'
    };
  },
  async mounted() {
    // Fetch server upload limit and video whitelist in parallel
    try {
      const [uploadResponse, whitelistResponse] = await Promise.all([
        axios.get(generateUrl('/apps/intravox/api/settings/upload-limit')),
        axios.get(generateUrl('/apps/intravox/api/settings/video-domains'))
      ]);

      if (uploadResponse.data.limit) {
        this.uploadLimitBytes = uploadResponse.data.limit;
        this.uploadLimitMB = uploadResponse.data.limitMB;
      }

      if (whitelistResponse.data.domains) {
        this.videoDomainWhitelist = whitelistResponse.data.domains;
      }
      this.videoWhitelistLoaded = true;
    } catch {
      // Use defaults if fetch fails
      this.videoWhitelistLoaded = true; // Mark as loaded even on error to enable UI
    }

    // Initialize embedUrl for existing video widgets
    // This ensures the preview works and src is preserved when editing
    if (this.localWidget.type === 'video' && this.localWidget.provider !== 'local') {
      // Preserve originalSrc if it exists (from saved widget)
      const originalUrl = this.localWidget.originalSrc;
      const embedUrlFromSrc = this.localWidget.src;

      if (originalUrl) {
        // Widget has originalSrc - use it for the input field and convert to embed
        this.localWidget.src = originalUrl;
        this.convertVideoUrl();
      } else if (embedUrlFromSrc) {
        // Widget only has src (embed URL) - set embedUrl directly for preview
        // Keep src as-is since it's already an embed URL
        this.embedUrl = embedUrlFromSrc;
        this.localWidget.originalSrc = embedUrlFromSrc; // Preserve for saving
        // Detect platform from embed URL
        this.detectPlatformFromEmbedUrl(embedUrlFromSrc);
      }
    }

    // Initialize Tiptap editor for text widgets
    if (this.localWidget.type === 'text') {
      // Decode HTML entities if content contains escaped HTML
      let content = this.localWidget.content || '';
      if (content.includes('&lt;') || content.includes('&gt;')) {
        content = this.decodeHtml(content);
      }

      this.editor = new Editor({
        extensions: [
          StarterKit.configure({
            // Configure paragraph to have no spacing
            paragraph: {
              HTMLAttributes: {
                class: 'tight-paragraph',
              },
            },
          }),
          Underline,
          Placeholder.configure({
            placeholder: 'Typ hier je tekst...'
          })
        ],
        editorProps: {
          attributes: {
            class: 'intravox-editor-full-width'
          }
        },
        content: content,
        onUpdate: ({ editor }) => {
          // Update widget content as HTML
          this.localWidget.content = editor.getHTML();
        }
      });
    }
  },
  beforeUnmount() {
    // Clean up editor instance
    if (this.editor) {
      this.editor.destroy();
    }
    // Clean up debounce timer
    if (this.convertVideoUrlTimeout) {
      clearTimeout(this.convertVideoUrlTimeout);
    }
  },
  computed: {
    /**
     * Check if the current video URL is blocked by the whitelist
     */
    isVideoBlocked() {
      // Only check for embed provider (external URLs)
      if (this.localWidget.type !== 'video' || this.localWidget.provider === 'local') {
        return false;
      }

      const url = this.embedUrl || this.localWidget.src;
      if (!url) {
        return false;
      }

      // Empty whitelist means all domains are blocked
      if (this.videoDomainWhitelist.length === 0) {
        return true;
      }

      try {
        const urlObj = new URL(url);
        const videoHost = urlObj.origin.toLowerCase();

        // Check if any whitelisted domain matches
        return !this.videoDomainWhitelist.some(domain => {
          try {
            const whitelistUrl = new URL(domain);
            return videoHost === whitelistUrl.origin.toLowerCase();
          } catch {
            return false;
          }
        });
      } catch {
        return false;
      }
    },
    /**
     * Get the domain that is blocking the video
     */
    blockedDomain() {
      if (!this.isVideoBlocked) return null;

      const url = this.embedUrl || this.localWidget.src;
      try {
        const urlObj = new URL(url);
        return urlObj.hostname;
      } catch {
        return url;
      }
    },
    /**
     * Check if the Save button should be enabled for video widgets
     */
    canSaveVideo() {
      if (this.localWidget.type !== 'video') {
        return true;
      }

      // Local videos are always allowed
      if (this.localWidget.provider === 'local') {
        return !!this.localWidget.src;
      }

      // External video: must have URL and not be blocked
      return !!(this.embedUrl && !this.isVideoBlocked);
    }
  },
  methods: {
    t(key, vars = {}) {
      return this.$t(key, vars);
    },
    decodeHtml(html) {
      // Decode HTML entities
      const txt = document.createElement('textarea');
      txt.innerHTML = html;
      return txt.value;
    },
    save() {
      // Ensure video URL is converted before saving (in case user didn't blur input)
      if (this.localWidget.type === 'video' && this.localWidget.provider !== 'local') {
        this.convertVideoUrl();
      }
      this.$emit('save', this.localWidget);
      this.$emit('close');
    },
    openMediaPicker(type) {
      this.mediaPickerType = type;
      this.showMediaPicker = true;
    },
    handleMediaSelect(selection) {
      // selection = { filename, folder }
      this.localWidget.src = selection.filename;
      this.localWidget.mediaFolder = selection.folder;

      // For video widgets, ensure provider is set to 'local'
      if (this.mediaPickerType === 'video') {
        this.localWidget.provider = 'local';
      }

      this.showMediaPicker = false;
    },
    debouncedConvertVideoUrl() {
      // Debounce video URL conversion to avoid too many conversions while typing
      if (this.convertVideoUrlTimeout) {
        clearTimeout(this.convertVideoUrlTimeout);
      }
      this.convertVideoUrlTimeout = setTimeout(() => {
        this.convertVideoUrl();
      }, 500);
    },
    getImageUrl(src) {
      if (!src) return '';

      // Check mediaFolder property (new format)
      if (this.localWidget.mediaFolder === 'resources') {
        return generateUrl(`/apps/intravox/api/resources/media/${src}`);
      }

      // Remove legacy prefixes if present
      const cleanFilename = src.replace(/^(📷 images\/|images\/|_media\/)/, '');
      // Default: page media
      return generateUrl(`/apps/intravox/api/pages/${this.pageId}/media/${cleanFilename}`);
    },
    setImageSize(width) {
      // Vue 3: Direct assignment is reactive
      this.localWidget.width = width;
      // Remove height - let aspect ratio determine it
      if (this.localWidget.height !== undefined) {
        delete this.localWidget.height;
      }
    },
    setObjectPosition(position) {
      this.localWidget.objectPosition = position;
    },
    setImageLinkType(linkType) {
      this.localWidget.linkType = linkType;
      // Clear link data when switching types
      if (linkType === 'none') {
        this.localWidget.linkPageId = null;
        this.localWidget.linkUrl = null;
        this.localWidget.linkNewTab = undefined;
      } else if (linkType === 'internal') {
        this.localWidget.linkUrl = null;
        this.localWidget.linkNewTab = undefined;
      } else if (linkType === 'external') {
        this.localWidget.linkPageId = null;
        // Default to opening in new tab for external URLs
        if (this.localWidget.linkNewTab === undefined) {
          this.localWidget.linkNewTab = true;
        }
      }
    },
    convertVideoUrl() {
      const url = this.localWidget.src || '';
      if (!url) {
        this.detectedPlatform = null;
        this.embedUrl = null;
        this.localWidget.originalSrc = null;
        return;
      }

      // Store original URL before converting (if not already an embed URL)
      // This preserves the user's input so it can be displayed when editing
      const isEmbedUrl = url.includes('/embed/') || url.includes('/videos/embed/') || url.includes('player.vimeo.com');
      if (!isEmbedUrl) {
        // Always update originalSrc when user enters a non-embed URL
        // This ensures copied widgets get the new URL, not the old one
        this.localWidget.originalSrc = url;
      }

      try {
        const urlObj = new URL(url);
        const host = urlObj.hostname.toLowerCase();

        // YouTube - Normalize host (handle www., m., music.)
        const normalizedHost = host.replace('www.', '').replace('m.', '').replace('music.', '');

        if (normalizedHost === 'youtube.com' || normalizedHost === 'youtu.be' || normalizedHost === 'youtube-nocookie.com') {
          this.detectedPlatform = 'YouTube';
          let videoId = null;

          if (normalizedHost === 'youtu.be') {
            // Short URL: https://youtu.be/VIDEO_ID or https://youtu.be/VIDEO_ID?t=120
            videoId = urlObj.pathname.slice(1).split(/[/?]/)[0];
          } else {
            // Try multiple extraction methods for youtube.com variants
            videoId = urlObj.searchParams.get('v')  // ?v=xxx (most common)
              || urlObj.pathname.match(/\/(?:embed|v|shorts|live)\/([^/?]+)/)?.[1]  // /embed/xxx, /shorts/xxx, /live/xxx
              || urlObj.pathname.match(/\/watch\/([^/?]+)/)?.[1];  // /watch/xxx (rare format)
          }

          // Validate video ID format (11 characters, alphanumeric + dash/underscore)
          if (videoId && !/^[a-zA-Z0-9_-]{11}$/.test(videoId)) {
            videoId = null;
          }

          if (videoId) {
            this.embedUrl = `https://www.youtube-nocookie.com/embed/${videoId}`;
            this.localWidget.src = this.embedUrl;
          }
          return;
        }

        // Vimeo
        if (host.includes('vimeo.com')) {
          this.detectedPlatform = 'Vimeo';
          let videoId = null;
          let privacyHash = null;

          // Check for unlisted video with privacy hash: https://vimeo.com/VIDEO_ID/HASH
          const unlistedMatch = urlObj.pathname.match(/\/(\d+)\/([a-f0-9]+)$/);
          if (unlistedMatch) {
            videoId = unlistedMatch[1];
            privacyHash = unlistedMatch[2];
          } else {
            // Standard URL: https://vimeo.com/VIDEO_ID
            const pathMatch = urlObj.pathname.match(/\/(\d+)/);
            if (pathMatch) {
              videoId = pathMatch[1];
            }
          }

          if (videoId) {
            // Include privacy hash parameter for unlisted videos
            if (privacyHash) {
              this.embedUrl = `https://player.vimeo.com/video/${videoId}?h=${privacyHash}`;
            } else {
              this.embedUrl = `https://player.vimeo.com/video/${videoId}`;
            }
            this.localWidget.src = this.embedUrl;
          }
          return;
        }

        // Dailymotion
        if (host.includes('dailymotion.com') || host.includes('dai.ly')) {
          this.detectedPlatform = 'Dailymotion';
          let videoId = null;

          if (host.includes('dai.ly')) {
            videoId = urlObj.pathname.slice(1);
          } else {
            const pathMatch = urlObj.pathname.match(/\/video\/([a-zA-Z0-9]+)/);
            if (pathMatch) {
              videoId = pathMatch[1];
            }
          }

          if (videoId) {
            this.embedUrl = `https://www.dailymotion.com/embed/video/${videoId}`;
            this.localWidget.src = this.embedUrl;
          }
          return;
        }

        // PeerTube (various instances)
        if (urlObj.pathname.includes('/videos/watch/') || urlObj.pathname.includes('/w/') || urlObj.pathname.includes('/videos/embed/')) {
          this.detectedPlatform = 'PeerTube';
          let path = urlObj.pathname;

          if (path.includes('/videos/watch/')) {
            path = path.replace('/videos/watch/', '/videos/embed/');
          } else if (path.match(/^\/w\/[a-zA-Z0-9-]+/)) {
            const videoId = path.split('/w/')[1].split('/')[0];
            path = `/videos/embed/${videoId}`;
          }

          urlObj.pathname = path;
          urlObj.search = '';
          urlObj.searchParams.set('p2p', '0');
          urlObj.searchParams.set('peertubeLink', '0');
          this.embedUrl = urlObj.toString();
          this.localWidget.src = this.embedUrl;
          return;
        }

        // TikTok
        if (host.includes('tiktok.com')) {
          this.detectedPlatform = 'TikTok';
          // TikTok embed URLs are more complex, just use the URL as-is for now
          const videoMatch = urlObj.pathname.match(/\/video\/(\d+)/);
          if (videoMatch) {
            this.embedUrl = `https://www.tiktok.com/embed/v2/${videoMatch[1]}`;
            this.localWidget.src = this.embedUrl;
          }
          return;
        }

        // Twitch
        if (host.includes('twitch.tv')) {
          this.detectedPlatform = 'Twitch';
          const channelMatch = urlObj.pathname.match(/^\/([a-zA-Z0-9_]+)$/);
          const videoMatch = urlObj.pathname.match(/\/videos\/(\d+)/);
          const clipMatch = urlObj.pathname.match(/\/clip\/([a-zA-Z0-9-]+)/);

          if (videoMatch) {
            this.embedUrl = `https://player.twitch.tv/?video=${videoMatch[1]}&parent=${window.location.hostname}`;
            this.localWidget.src = this.embedUrl;
          } else if (clipMatch) {
            this.embedUrl = `https://clips.twitch.tv/embed?clip=${clipMatch[1]}&parent=${window.location.hostname}`;
            this.localWidget.src = this.embedUrl;
          } else if (channelMatch) {
            this.embedUrl = `https://player.twitch.tv/?channel=${channelMatch[1]}&parent=${window.location.hostname}`;
            this.localWidget.src = this.embedUrl;
          }
          return;
        }

        // Unknown platform - try to use as-is if it looks like an embed URL
        this.detectedPlatform = null;
        if (urlObj.pathname.includes('/embed') || urlObj.pathname.includes('/player')) {
          this.embedUrl = url;
          this.localWidget.src = url;
          // Store original URL for editing display
          if (!this.localWidget.originalSrc) {
            this.localWidget.originalSrc = url;
          }
        } else {
          // Not a recognized embed URL - store it anyway for the backend to handle
          this.embedUrl = url;
          this.localWidget.src = url;
          if (!this.localWidget.originalSrc) {
            this.localWidget.originalSrc = url;
          }
        }
      } catch (e) {
        // Invalid URL
        this.detectedPlatform = null;
        this.embedUrl = null;
      }
    },
    /**
     * Detect video platform from embed URL (for display purposes only)
     * Used when opening existing video widgets that only have embed URLs
     */
    detectPlatformFromEmbedUrl(url) {
      if (!url) {
        this.detectedPlatform = null;
        return;
      }
      try {
        const urlObj = new URL(url);
        const host = urlObj.hostname.toLowerCase();

        if (host.includes('youtube-nocookie.com') || host.includes('youtube.com')) {
          this.detectedPlatform = 'YouTube';
        } else if (host.includes('vimeo.com')) {
          this.detectedPlatform = 'Vimeo';
        } else if (host.includes('dailymotion.com')) {
          this.detectedPlatform = 'Dailymotion';
        } else if (host.includes('tiktok.com')) {
          this.detectedPlatform = 'TikTok';
        } else if (host.includes('twitch.tv')) {
          this.detectedPlatform = 'Twitch';
        } else if (urlObj.pathname.includes('/videos/embed/')) {
          this.detectedPlatform = 'PeerTube';
        } else {
          this.detectedPlatform = null;
        }
      } catch {
        this.detectedPlatform = null;
      }
    },
    getVideoUrl(filename) {
      if (!filename) return '';

      // Check mediaFolder property (new format)
      if (this.localWidget.mediaFolder === 'resources') {
        return generateUrl(`/apps/intravox/api/resources/media/${filename}`);
      }

      // Remove legacy prefixes if present
      const cleanFilename = filename.replace(/^(videos\/|_media\/)/, '');
      // Default: page media
      return generateUrl(`/apps/intravox/api/pages/${this.pageId}/media/${cleanFilename}`);
    }
  }
};
</script>

<style scoped>
/* Make modal wider for better editing experience */
.widget-editor-modal :deep(.modal-container) {
  max-width: 1200px !important;
}

/* Remove all padding from modal content wrapper */
:deep(.modal-wrapper--large .modal-container) {
  padding: 0;
}

:deep(.modal-wrapper .modal-container .modal-container__content) {
  padding: 0;
  margin: 0;
}

:deep(.modal-container > *) {
  padding: 0;
}

.widget-editor-content {
  padding: 0;
  width: 100%;
  margin: 0;
}

.form-group {
  margin-bottom: 20px;
  padding: 0 20px;
}

.full-width-editor {
  padding: 0;
  margin: 0;
}

.full-width-editor label {
  padding: 20px 20px 0 20px;
  display: block;
  margin: 0;
}

.form-group label {
  display: block;
  margin-bottom: 8px;
  font-weight: 500;
  color: var(--color-main-text);
}

.form-group .hint {
  margin-top: 4px;
  margin-bottom: 0;
  font-size: 12px;
  color: var(--color-text-maxcontrast);
}

.form-group .hint.uploading {
  color: var(--color-primary);
  font-weight: 500;
}

.heading-text-input,
.heading-level-select,
.widget-input {
  width: 100%;
  padding: 8px 12px;
  border: 2px solid var(--color-border-dark);
  border-radius: var(--border-radius-large);
  background: var(--color-main-background);
  color: var(--color-main-text);
  font-size: 14px;
}

.heading-text-input,
.widget-input {
  cursor: text;
}

.heading-level-select {
  cursor: pointer;
}

.heading-text-input:focus,
.heading-level-select:focus,
.widget-input:focus {
  outline: none;
  border-color: var(--color-primary-element);
}

.image-preview {
  margin: 15px 0;
  max-width: 100%;
}

.image-preview img {
  max-width: 100%;
  height: auto;
  border-radius: var(--border-radius-large);
  border: 1px solid var(--color-border);
}

/* Video preview and upload styles */
.video-preview {
  margin: 15px 0;
}

.video-preview .preview-container {
  border-radius: var(--border-radius-large);
  overflow: hidden;
  border: 1px solid var(--color-border);
  background: var(--color-background-dark);
}

.video-preview-hint {
  margin: 15px 0;
  padding: 20px;
  background: var(--color-background-hover);
  border-radius: var(--border-radius-large);
  text-align: center;
}

/* Blocked video preview - matches Widget.vue styling */
.video-blocked-preview {
  padding: 24px;
  background: var(--color-background-hover);
  border: 2px dashed var(--color-warning);
  border-radius: var(--border-radius-large);
  text-align: center;
}

.video-blocked-preview .blocked-icon {
  font-size: 48px;
  line-height: 1;
  margin-bottom: 12px;
  filter: grayscale(0);
}

.video-blocked-preview .blocked-title {
  font-size: 16px;
  font-weight: 600;
  color: var(--color-warning-text);
  background-color: var(--color-warning);
  display: inline-block;
  padding: 4px 12px;
  border-radius: var(--border-radius);
  margin: 0 0 12px 0;
}

.video-blocked-preview .blocked-url {
  margin: 12px 0;
  padding: 8px 12px;
  background: var(--color-background-dark);
  border-radius: var(--border-radius);
  max-width: 100%;
  overflow: hidden;
}

.video-blocked-preview .blocked-url .url-label {
  font-size: 12px;
  font-weight: 600;
  color: var(--color-text-maxcontrast);
  margin-right: 8px;
}

.video-blocked-preview .blocked-url .url-value {
  font-size: 12px;
  font-family: var(--font-monospace, monospace);
  color: var(--color-main-text);
  word-break: break-all;
  background: none;
  padding: 0;
}

.video-blocked-preview .blocked-hint {
  font-size: 13px;
  color: var(--color-text-maxcontrast);
  margin: 0;
  font-style: italic;
}

.current-video {
  margin-top: 8px;
  padding: 8px 12px;
  background: var(--color-background-hover);
  border-radius: var(--border-radius);
  font-size: 13px;
  color: var(--color-text-maxcontrast);
}

.detected-platform {
  margin-top: 8px;
  padding: 6px 10px;
  background: var(--color-primary-element-light);
  border-radius: var(--border-radius);
  font-size: 13px;
  color: var(--color-primary);
}

.modal-footer {
  display: flex;
  justify-content: flex-end;
  gap: 10px;
  padding: 20px;
  border-top: 1px solid var(--color-border);
  margin-top: 0;
}

/* Rich Text Editor Toolbar */
.rich-text-toolbar {
  display: flex;
  gap: 4px;
  padding: 12px 20px;
  background: var(--color-background-dark);
  border: 1px solid var(--color-border);
  border-left: none;
  border-right: none;
  border-radius: 0;
  align-items: center;
  flex-wrap: wrap;
}

.toolbar-btn {
  padding: 6px 12px;
  background: var(--color-main-background);
  border: 1px solid var(--color-border);
  border-radius: var(--border-radius-large);
  color: var(--color-main-text);
  cursor: pointer;
  font-size: 14px;
  transition: all 0.2s;
  display: flex;
  align-items: center;
  gap: 4px;
}

.toolbar-btn:hover {
  background: var(--color-background-hover);
  border-color: var(--color-primary);
}

.toolbar-btn.is-active {
  background: var(--color-primary-element-light);
  border-color: var(--color-primary);
  color: var(--color-primary);
}

.toolbar-divider {
  width: 1px;
  height: 24px;
  background: var(--color-border);
  margin: 0 4px;
}

/* Tiptap Editor Styles */
.rich-text-editor {
  width: 100%;
  border: 1px solid var(--color-border);
  border-top: none;
  border-left: none;
  border-right: none;
  border-radius: 0;
  background: var(--color-main-background);
}

.rich-text-editor :deep(.tiptap) {
  width: 100%;
  max-width: 100%;
}

/* Force full width using custom class to override Tiptap inline styles */
.rich-text-editor :deep(.intravox-editor-full-width) {
  width: 100% !important;
  min-height: 400px;
  max-height: 600px;
  padding: 20px;
  color: var(--color-main-text);
  font-family: var(--font-face);
  font-size: 14px;
  line-height: 1.5;
  overflow-y: auto;
  outline: none;
}

.rich-text-editor :deep(.ProseMirror) {
  width: 100%;
  min-height: 400px;
  max-height: 600px;
  padding: 20px;
  color: var(--color-main-text);
  font-family: var(--font-face);
  font-size: 14px;
  line-height: 1.5;
  overflow-y: auto;
  outline: none;
}

.rich-text-editor :deep(.ProseMirror:focus) {
  outline: none;
}

/* Rich text content styling */
.rich-text-editor :deep(.ProseMirror p) {
  margin: 0;
}

.rich-text-editor :deep(.ProseMirror p + p) {
  margin-top: 0;
}

.rich-text-editor :deep(.ProseMirror ul),
.rich-text-editor :deep(.ProseMirror ol) {
  padding-left: 1.5em;
  margin: 0.5em 0;
  list-style-position: outside;
}

.rich-text-editor :deep(.ProseMirror ul) {
  list-style-type: disc;
}

.rich-text-editor :deep(.ProseMirror ol) {
  list-style-type: decimal;
}

.rich-text-editor :deep(.ProseMirror li) {
  margin: 0.25em 0;
  display: list-item;
}

.rich-text-editor :deep(.ProseMirror li p) {
  margin: 0;
}

.rich-text-editor :deep(.ProseMirror strong) {
  font-weight: 700;
}

.rich-text-editor :deep(.ProseMirror em) {
  font-style: italic;
}

.rich-text-editor :deep(.ProseMirror u) {
  text-decoration: underline;
}

.rich-text-editor :deep(.ProseMirror s) {
  text-decoration: line-through;
}

/* Placeholder */
.rich-text-editor :deep(.ProseMirror p.is-editor-empty:first-child::before) {
  content: attr(data-placeholder);
  float: left;
  color: var(--color-text-maxcontrast);
  pointer-events: none;
  height: 0;
}

.size-presets {
  display: flex;
  gap: 8px;
  flex-wrap: wrap;
}

.size-preset-btn {
  padding: 8px 16px;
  border: 2px solid var(--color-border);
  border-radius: var(--border-radius-large);
  background: var(--color-main-background);
  color: var(--color-main-text);
  cursor: pointer;
  font-size: 14px;
  transition: all 0.2s;
}

.size-preset-btn:hover {
  background: var(--color-background-hover);
  border-color: var(--color-primary);
}

.size-preset-btn.active {
  background: var(--color-primary);
  color: white;
  border-color: var(--color-primary);
}

.custom-size-inputs {
  display: flex;
  gap: 15px;
}

.size-input-group {
  flex: 1;
  display: flex;
  align-items: center;
  gap: 8px;
}

.size-label {
  font-size: 14px;
  color: var(--color-text-maxcontrast);
  min-width: 60px;
}

.size-input {
  flex: 1;
  padding: 8px 12px;
  border: 2px solid var(--color-border-dark);
  border-radius: var(--border-radius-large);
  background: var(--color-main-background);
  color: var(--color-main-text);
  font-size: 14px;
}

.size-input:focus {
  outline: none;
  border-color: var(--color-primary-element);
}

.size-hint {
  margin-top: 8px;
  font-size: 12px;
  color: var(--color-text-maxcontrast);
  font-style: italic;
}

/* Checkbox group styling */
.checkbox-group {
  margin-bottom: 12px;
}

.link-target-options {
  margin-top: 8px;
  margin-bottom: 4px;
}

.checkbox-label {
  display: flex;
  align-items: center;
  gap: 8px;
  cursor: pointer;
  font-size: 14px;
  color: var(--color-main-text);
}

.checkbox-label input[type="checkbox"] {
  width: 18px;
  height: 18px;
  cursor: pointer;
  accent-color: var(--color-primary);
}

.checkbox-hint {
  margin-top: 4px;
  margin-left: 26px;
  margin-bottom: 0;
}

/* Media Picker Integration */
</style>
