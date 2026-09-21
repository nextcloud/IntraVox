<template>
  <!-- MediaPicker v0.7.2 - Fixed URL generation -->
  <NcDialog
    :open="open"
    :name="title"
    size="large"
    @update:open="handleClose"
  >
    <!-- Tab Navigation -->
    <div class="media-picker-tabs"
         role="tablist"
         :aria-label="t('intravox', 'Media source')"
         @keydown="onTabKeydown">
      <button
        :id="'intravox-media-tab-upload'"
        ref="tabUpload"
        :class="['tab-button', { active: activeTab === 'upload' }]"
        role="tab"
        aria-controls="intravox-media-panel-upload"
        :aria-selected="activeTab === 'upload'"
        :tabindex="activeTab === 'upload' ? 0 : -1"
        @click="activeTab = 'upload'"
      >
        {{ t('intravox', 'Upload') }}
      </button>
      <button
        :id="'intravox-media-tab-page'"
        ref="tabPage"
        :class="['tab-button', { active: activeTab === 'page' }]"
        role="tab"
        aria-controls="intravox-media-panel-page"
        :aria-selected="activeTab === 'page'"
        :tabindex="activeTab === 'page' ? 0 : -1"
        @click="switchToTab('page')"
      >
        {{ t('intravox', 'Page media') }}
      </button>
      <button
        :id="'intravox-media-tab-resources'"
        ref="tabResources"
        :class="['tab-button', { active: activeTab === 'resources' }]"
        role="tab"
        aria-controls="intravox-media-panel-resources"
        :aria-selected="activeTab === 'resources'"
        :tabindex="activeTab === 'resources' ? 0 : -1"
        @click="switchToTab('resources')"
      >
        {{ t('intravox', 'Shared library') }}
      </button>
    </div>

    <!-- Tab Content -->
    <div class="media-picker-content">
      <!-- Upload Tab -->
      <div v-if="activeTab === 'upload'" id="intravox-media-panel-upload" class="tab-panel" role="tabpanel" aria-labelledby="intravox-media-tab-upload">
        <div class="upload-section">
          <input
            ref="fileInput"
            type="file"
            :accept="acceptTypes"
            class="file-input"
            :aria-label="mediaType === 'image' ? t('intravox', 'Select image file') : t('intravox', 'Select video file')"
            @change="handleFileSelect"
          />

          <div
            v-if="!selectedFile"
            :class="['upload-prompt', { dragging: isDragging }]"
            @click="$refs.fileInput.click()"
            @dragover.prevent
            @dragenter.prevent="isDragging = true"
            @dragleave.prevent="isDragging = false"
            @drop.prevent="handleFileDrop"
          >
            <div class="upload-icon">📁</div>
            <p>{{ mediaType === 'image' ? t('intravox', 'Click to select an image, or drag and drop') : t('intravox', 'Click to select a video, or drag and drop') }}</p>
          </div>

          <div v-else class="file-selected">
            <div class="file-info">
              <span class="file-icon">{{ mediaType === 'image' ? '🖼️' : '🎬' }}</span>
              <div class="file-details">
                <div class="file-name">{{ selectedFile.name }}</div>
                <div class="file-size">{{ formatFileSize(selectedFile.size) }}</div>
              </div>
            </div>
            <button type="button" @click="clearFile" class="btn-remove" :aria-label="t('intravox', 'Remove file')">✕</button>
          </div>

        </div>
      </div>

      <!-- Page Media Tab -->
      <div v-if="activeTab === 'page'" id="intravox-media-panel-page" class="tab-panel" role="tabpanel" aria-labelledby="intravox-media-tab-page">
        <div v-if="isLoadingMedia" class="loading-state">
          <span class="loading-spinner"></span>
          <p>{{ t('intravox', 'Loading media …') }}</p>
        </div>

        <div v-else-if="filteredPageMedia.length === 0" class="empty-state">
          <div class="empty-icon">📭</div>
          <p>{{ t('intravox', 'No media files in this page yet') }}</p>
          <p class="hint">{{ t('intravox', 'Upload files to see them here') }}</p>
        </div>

        <div v-else class="media-grid">
          <div
            v-for="media in filteredPageMedia"
            :key="media.name"
            :class="['media-item', { selected: selectedMedia?.name === media.name && selectedMedia?.folder === 'page' }]"
            @click="selectMedia(media, 'page')"
          >
            <div class="media-thumbnail">
              <img
                v-if="isImage(media.mimeType)"
                :src="getMediaThumbnail(media, 'page')"
                :alt="media.name"
              />
              <div v-else class="video-placeholder">
                <span>🎬</span>
              </div>
            </div>
            <div class="media-info">
              <div class="media-name" :title="media.name">{{ media.name }}</div>
              <div class="media-size">{{ formatFileSize(media.size) }}</div>
            </div>
          </div>
        </div>
      </div>

      <!-- Resources Tab -->
      <div v-if="activeTab === 'resources'" id="intravox-media-panel-resources" class="tab-panel" role="tabpanel" aria-labelledby="intravox-media-tab-resources">
        <!-- Breadcrumb Navigation -->
        <div v-if="currentResourcesPath" class="breadcrumb-nav">
          <button
            class="breadcrumb-item"
            @click="navigateToFolder('')"
          >
            📚 {{ t('intravox', 'Shared library') }}
          </button>
          <span v-for="(segment, index) in breadcrumbSegments" :key="index">
            <span class="breadcrumb-separator">/</span>
            <button
              class="breadcrumb-item"
              @click="navigateToFolder(getPathUpTo(index))"
            >
              {{ segment }}
            </button>
          </span>
        </div>

        <div v-if="isLoadingMedia" class="loading-state">
          <span class="loading-spinner"></span>
          <p>{{ t('intravox', 'Loading shared media …') }}</p>
        </div>

        <div v-else-if="filteredResourcesMedia.length === 0" class="empty-state">
          <div class="empty-icon">📚</div>
          <p>{{ t('intravox', 'No media in shared library yet') }}</p>
          <p class="hint">{{ t('intravox', 'Upload to shared library to see files here') }}</p>
        </div>

        <div v-else class="media-grid">
          <div
            v-for="item in filteredResourcesMedia"
            :key="item.name"
            :class="['media-item', {
              selected: item.type === 'file' && selectedMedia?.path === item.path && selectedMedia?.folder === 'resources',
              'is-folder': item.type === 'folder'
            }]"
            @click="item.type === 'folder' ? handleFolderClick(item) : selectMedia(item, 'resources')"
          >
            <div class="media-thumbnail">
              <div v-if="item.type === 'folder'" class="folder-icon">
                📁
              </div>
              <img
                v-else-if="isImage(item.mimeType)"
                :src="getMediaThumbnail(item, 'resources')"
                :alt="item.name"
              />
              <div v-else class="video-placeholder">
                <span>🎬</span>
              </div>
            </div>
            <div class="media-info">
              <div class="media-name" :title="item.name">{{ item.name }}</div>
              <div v-if="item.type === 'file'" class="media-size">{{ formatFileSize(item.size) }}</div>
              <div v-else class="media-type">{{ t('intravox', 'Folder') }}</div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Dialog Actions -->
    <template #actions>
      <NcButton @click="handleClose">
        {{ t('intravox', 'Cancel') }}
      </NcButton>
      <NcButton
        type="primary"
        :disabled="isUploading || (!selectedFile && !selectedMedia)"
        @click="handleConfirm"
      >
        <template #icon>
          <span v-if="isUploading" class="icon-loading-small"></span>
        </template>
        {{ confirmButtonLabel }}
      </NcButton>
    </template>

    <!-- Duplicate Warning Dialog -->
    <NcDialog
      v-if="showDuplicateDialog"
      :open="showDuplicateDialog"
      :name="t('intravox', 'File already exists')"
      size="small"
      @update:open="showDuplicateDialog = false"
    >
      <p>{{ t('intravox', 'A file with the name "{filename}" already exists.', { filename: duplicateFilename }) }}</p>
      <p>{{ t('intravox', 'What would you like to do?') }}</p>

      <template #actions>
        <NcButton @click="handleDuplicateCancel">
          {{ t('intravox', 'Cancel') }}
        </NcButton>
        <NcButton @click="handleDuplicateRename">
          {{ t('intravox', 'Use different name') }}
        </NcButton>
        <NcButton
          type="error"
          @click="handleDuplicateOverwrite"
        >
          {{ t('intravox', 'Overwrite') }}
        </NcButton>
      </template>
    </NcDialog>
  </NcDialog>
</template>

<script>
import { NcDialog, NcButton } from '@nextcloud/vue'
import axios from '@nextcloud/axios'
import { generateUrl } from '@nextcloud/router'
import { translate } from '@nextcloud/l10n'
import { showError } from '@nextcloud/dialogs'
import { encodeMediaPath } from '../utils/mediaUrl.js'

export default {
  name: 'MediaPicker',

  components: {
    NcDialog,
    NcButton
  },

  props: {
    open: {
      type: Boolean,
      required: true
    },
    pageId: {
      type: String,
      required: true
    },
    mediaType: {
      type: String,
      required: true,
      validator: (value) => ['image', 'video'].includes(value)
    },
    title: {
      type: String,
      default: () => t('intravox', 'Select media')
    }
  },

  emits: ['close', 'select'],

  data() {
    return {
      activeTab: 'upload',
      selectedFile: null,
      isDragging: false,
      uploadTarget: 'page',
      selectedMedia: null,
      pageMediaList: [],
      resourcesMediaList: [],
      isLoadingMedia: false,
      isUploading: false,
      showDuplicateDialog: false,
      duplicateFilename: '',
      pendingUpload: null,
      currentResourcesPath: '', // NEW: Current subfolder path
      folderHistory: []         // NEW: For back navigation
    }
  },

  computed: {
    acceptTypes() {
      if (this.mediaType === 'image') {
        return 'image/jpeg,image/png,image/gif,image/webp,image/svg+xml'
      }
      return 'video/mp4,video/webm,video/ogg'
    },

    confirmButtonLabel() {
      if (this.isUploading) {
        return this.t('intravox', 'Uploading …')
      }
      if (this.activeTab === 'upload' && this.selectedFile) {
        return this.t('intravox', 'Upload')
      }
      return this.t('intravox', 'Select')
    },

    filteredPageMedia() {
      return this.filterMediaByType(this.pageMediaList)
    },

    filteredResourcesMedia() {
      return this.filterMediaByType(this.resourcesMediaList)
    },

    breadcrumbSegments() {
      if (!this.currentResourcesPath) return []
      return this.currentResourcesPath.split('/').filter(s => s)
    }
  },

  watch: {
    open(newVal) {
      if (newVal) {
        this.resetState()
      }
    }
  },

  methods: {
    t(app, text, vars) {
      return translate(app, text, vars);
    },

    handleClose() {
      this.$emit('close')
    },

    resetState() {
      this.activeTab = 'upload'
      this.selectedFile = null
      this.uploadTarget = 'page'
      this.selectedMedia = null
      this.pageMediaList = []
      this.resourcesMediaList = []
      this.isLoadingMedia = false
    },

    handleFileSelect(event) {
      const file = event.target.files[0]
      if (file) {
        this.selectedFile = file
        this.selectedMedia = null
      }
    },

    handleFileDrop(event) {
      this.isDragging = false
      const file = event.dataTransfer?.files?.[0]
      if (!file) return
      // Native drop ignores the input's accept attribute, so validate the
      // dropped file's type against the widget's allowed types ourselves.
      const allowed = this.acceptTypes.split(',')
      if (file.type && !allowed.includes(file.type)) {
        showError(this.mediaType === 'image'
          ? this.t('intravox', 'Please drop an image file (JPEG, PNG, GIF, WebP or SVG).')
          : this.t('intravox', 'Please drop a video file (MP4, WebM or Ogg).'))
        return
      }
      this.selectedFile = file
      this.selectedMedia = null
    },

    clearFile() {
      this.selectedFile = null
      if (this.$refs.fileInput) {
        this.$refs.fileInput.value = ''
      }
    },

    filterMediaByType(mediaList) {
      if (!mediaList || mediaList.length === 0) {
        return []
      }

      const imageTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/svg+xml']
      const videoTypes = ['video/mp4', 'video/webm', 'video/ogg']

      return mediaList.filter(media => {
        // Always include folders (they don't have mimeType)
        if (media.type === 'folder') {
          return true
        }

        if (this.mediaType === 'image') {
          return imageTypes.includes(media.mimeType)
        } else if (this.mediaType === 'video') {
          return videoTypes.includes(media.mimeType)
        }
        return true
      })
    },

    onTabKeydown(event) {
      // WAI-ARIA tabs: pijltjes wisselen van tab, Home/End naar de uiterste.
      const volgorde = ['upload', 'page', 'resources'];
      const huidig = volgorde.indexOf(this.activeTab);
      let doel = null;
      if (event.key === 'ArrowRight' || event.key === 'ArrowDown') {
        doel = volgorde[(huidig + 1) % volgorde.length];
      } else if (event.key === 'ArrowLeft' || event.key === 'ArrowUp') {
        doel = volgorde[(huidig - 1 + volgorde.length) % volgorde.length];
      } else if (event.key === 'Home') {
        doel = volgorde[0];
      } else if (event.key === 'End') {
        doel = volgorde[volgorde.length - 1];
      }
      if (!doel) return;
      event.preventDefault();
      // switchToTab laadt de inhoud van de doeltab; 'upload' heeft dat niet nodig.
      if (doel === 'upload') {
        this.activeTab = 'upload';
      } else {
        this.switchToTab(doel);
      }
      this.$nextTick(() => {
        const refs = { upload: 'tabUpload', page: 'tabPage', resources: 'tabResources' };
        const el = this.$refs[refs[doel]];
        (Array.isArray(el) ? el[0] : el)?.focus();
      });
    },
    async switchToTab(tab) {
      this.activeTab = tab
      if (tab === 'page' && this.pageMediaList.length === 0) {
        await this.loadPageMedia()
      } else if (tab === 'resources' && this.resourcesMediaList.length === 0) {
        await this.loadResourcesMedia()
      }
    },

    async loadPageMedia() {
      this.isLoadingMedia = true
      try {
        const response = await axios.get(
          generateUrl(`/apps/intravox/api/pages/${this.pageId}/media/list`),
          { params: { folder: 'page' } }
        )
        this.pageMediaList = response.data.media || []
      } catch (error) {
        console.error('Failed to load page media:', error)
      } finally {
        this.isLoadingMedia = false
      }
    },

    async loadResourcesMedia(path = '') {
      this.isLoadingMedia = true
      try {
        const response = await axios.get(
          generateUrl(`/apps/intravox/api/pages/${this.pageId}/media/list`),
          {
            params: {
              folder: 'resources',
              path: path
            }
          }
        )
        this.currentResourcesPath = path
        this.resourcesMediaList = response.data.media || []
      } catch (error) {
        console.error('Failed to load resources media:', error)
      } finally {
        this.isLoadingMedia = false
      }
    },

    selectMedia(media, folder) {
      // Store full path for resources media
      this.selectedMedia = {
        ...media,
        folder,
        path: media.path || media.name // Ensure path is always set
      }
      this.selectedFile = null
    },

    navigateToFolder(path) {
      this.loadResourcesMedia(path)
    },

    getPathUpTo(index) {
      return this.breadcrumbSegments.slice(0, index + 1).join('/')
    },

    handleFolderClick(item) {
      if (item.type === 'folder') {
        this.loadResourcesMedia(item.path)
      }
    },

    async handleConfirm() {
      if (this.selectedFile) {
        await this.performUpload()
      } else if (this.selectedMedia) {
        // Picking an EXISTING library file: no upload response, so no measured
        // metadata. It degrades cleanly — the widget keeps working, just without
        // the reserve-space/placeholder benefit until that file is re-uploaded
        // (or a future media-listing carries dimensions). Shape stays consistent.
        this.$emit('select', {
          filename: this.selectedMedia.path || this.selectedMedia.name, // Use path for subfolders
          folder: this.selectedMedia.folder,
          meta: this.selectedMedia.meta || null
        })
      }
    },

    async performUpload(overwrite = false) {
      if (!this.selectedFile) return

      this.isUploading = true

      try {
        // Check for duplicates if not overwriting
        if (!overwrite) {
          const checkFormData = new FormData()
          checkFormData.append('filename', this.selectedFile.name)
          checkFormData.append('target', this.uploadTarget)

          const checkResponse = await axios.post(
            generateUrl(`/apps/intravox/api/pages/${this.pageId}/media/check`),
            checkFormData
          )

          if (checkResponse.data.exists) {
            this.duplicateFilename = this.selectedFile.name
            this.showDuplicateDialog = true
            this.isUploading = false
            return
          }
        }

        // Upload file
        const formData = new FormData()
        formData.append('media', this.selectedFile)
        formData.append('target', this.uploadTarget)
        if (overwrite) {
          formData.append('overwrite', '1')
        }

        const response = await axios.post(
          generateUrl(`/apps/intravox/api/pages/${this.pageId}/media/upload`),
          formData,
          {
            headers: {
              'Content-Type': 'multipart/form-data'
            }
          }
        )

        // Upload successful, emit select event. `meta` (dimensions + dominant
        // colour) is present for raster images so the editor can reserve layout
        // space + set a placeholder; null/absent for video/SVG or older servers.
        this.$emit('select', {
          filename: response.data.filename,
          folder: this.uploadTarget,
          meta: response.data.meta || null
        })

      } catch (error) {
        console.error('Upload failed:', error)
        showError(this.t('intravox', 'Upload failed: ') + (error.response?.data?.error || error.message))
      } finally {
        this.isUploading = false
      }
    },

    handleDuplicateCancel() {
      this.showDuplicateDialog = false
      this.duplicateFilename = ''
    },

    handleDuplicateRename() {
      this.showDuplicateDialog = false
      this.duplicateFilename = ''
      alert(this.t('intravox', 'Please rename the file and try again'))
    },

    async handleDuplicateOverwrite() {
      this.showDuplicateDialog = false
      await this.performUpload(true)
    },

    formatFileSize(bytes) {
      if (bytes === 0) return '0 B'
      const k = 1024
      const sizes = ['B', 'KB', 'MB', 'GB']
      const i = Math.floor(Math.log(bytes) / Math.log(k))
      return Math.round(bytes / Math.pow(k, i) * 100) / 100 + ' ' + sizes[i]
    },

    isImage(mimeType) {
      return mimeType?.startsWith('image/')
    },

    getMediaThumbnail(media, folder) {
      if (folder === 'resources') {
        // Use path for resources (supports subfolders). Encoding per segment
        // keeps the "/" that picks the {folder}/{filename} route, so the split
        // this used to do by hand is no longer needed.
        return generateUrl(`/apps/intravox/api/resources/media/${encodeMediaPath(media.path || media.name)}`)
      }
      return generateUrl('/apps/intravox/api/pages/{pageId}/media/{filename}', {
        pageId: this.pageId,
        filename: media.name
      })
    }
  }
}
</script>

<style scoped>
.media-picker-tabs {
  display: flex;
  gap: 8px;
  border-bottom: 2px solid #e0e0e0;
  margin-bottom: 20px;
}

.tab-button {
  padding: 10px 20px;
  border: none;
  background: transparent;
  cursor: pointer;
  font-size: 14px;
  font-weight: 500;
  color: #666;
  border-bottom: 2px solid transparent;
  margin-bottom: -2px;
  transition: all 0.2s;
}

.tab-button:hover {
  color: #0082c9;
}

.tab-button.active {
  /* Hardgecodeerde #0082c9 gaf 3.19:1 op de actieve-tab-achtergrond — onder de
     4.5:1 van WCAG 1.4.3. De themavariabele is donkerder (4.69:1) en volgt
     bovendien het ingestelde thema, zoals de rest van de app. */
  color: var(--color-primary-element);
  border-bottom-color: var(--color-primary-element);
}

.media-picker-content {
  min-height: 400px;
  max-height: 500px;
  overflow-y: auto;
}

.tab-panel {
  padding: 10px 0;
}

/* Upload Tab Styles */
.upload-section {
  display: flex;
  flex-direction: column;
  gap: 20px;
}

.file-input {
  display: none;
}

.upload-prompt {
  border: 2px dashed #ccc;
  border-radius: 8px;
  padding: 60px 20px;
  text-align: center;
  cursor: pointer;
  transition: all 0.2s;
}

.upload-prompt:hover,
.upload-prompt.dragging {
  border-color: #0082c9;
  background: #f0f9ff;
}

.upload-icon {
  font-size: 64px;
  margin-bottom: 15px;
}

.upload-prompt p {
  margin: 5px 0;
  color: #666;
}

.hint {
  font-size: 12px;
  color: #999;
}

.file-selected {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 15px;
  background: #f8f9fa;
  border: 1px solid #ddd;
  border-radius: 8px;
}

.file-info {
  display: flex;
  align-items: center;
  gap: 12px;
  flex: 1;
}

.file-icon {
  font-size: 32px;
}

.file-details {
  flex: 1;
}

.file-name {
  font-weight: 600;
  margin-bottom: 4px;
  word-break: break-word;
}

.file-size {
  font-size: 12px;
  color: #666;
}

.btn-remove {
  background: none;
  border: none;
  cursor: pointer;
  font-size: 24px;
  color: #999;
  padding: 5px 10px;
  transition: color 0.2s;
}

.btn-remove:hover {
  color: #e74c3c;
}

/* Media Grid Styles */
.media-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
  gap: 15px;
}

.media-item {
  border: 2px solid #ddd;
  border-radius: 8px;
  overflow: hidden;
  cursor: pointer;
  transition: all 0.2s;
}

.media-item:hover {
  border-color: #0082c9;
  box-shadow: 0 2px 8px rgba(0, 130, 201, 0.2);
}

.media-item.selected {
  border-color: #0082c9;
  background: #f0f9ff;
}

.media-thumbnail {
  width: 100%;
  height: 120px;
  background: #f5f5f5;
  display: flex;
  align-items: center;
  justify-content: center;
  overflow: hidden;
}

.media-thumbnail img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.video-placeholder {
  font-size: 48px;
}

.media-info {
  padding: 10px;
  background: white;
}

.media-name {
  font-size: 12px;
  font-weight: 600;
  margin-bottom: 4px;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.media-size {
  font-size: 11px;
  color: #666;
}

/* Loading State */
.loading-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 60px 20px;
  color: #666;
}

.loading-spinner {
  display: inline-block;
  width: 32px;
  height: 32px;
  border: 3px solid #f3f3f3;
  border-top: 3px solid #0082c9;
  border-radius: 50%;
  animation: spin 1s linear infinite;
  margin-bottom: 15px;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

/* Empty State */
.empty-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 60px 20px;
  color: #666;
}

.empty-icon {
  font-size: 64px;
  margin-bottom: 15px;
}

/* Dialog Actions */
.btn {
  padding: 10px 20px;
  border: 1px solid #ddd;
  border-radius: 4px;
  cursor: pointer;
  font-size: 14px;
  transition: all 0.2s;
}

.btn:hover:not(:disabled) {
  background: #f0f0f0;
}

.btn:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.btn-primary {
  background: #0082c9;
  color: white;
  border-color: #0082c9;
}

.btn-primary:hover:not(:disabled) {
  background: #006ba6;
}

.btn-secondary {
  background: white;
  color: #333;
}

.btn-danger {
  background: #e74c3c;
  color: white;
  border-color: #e74c3c;
}

.btn-danger:hover:not(:disabled) {
  background: #c0392b;
}

.spinner {
  display: inline-block;
  width: 14px;
  height: 14px;
  border: 2px solid rgba(255, 255, 255, 0.3);
  border-top-color: currentColor;
  border-radius: 50%;
  animation: spin 0.6s linear infinite;
  margin-right: 8px;
  vertical-align: middle;
}

/* Breadcrumb Navigation */
.breadcrumb-nav {
  display: flex;
  align-items: center;
  gap: 4px;
  padding: 12px 20px;
  background: var(--color-background-hover);
  border-radius: var(--border-radius);
  margin-bottom: 16px;
  overflow-x: auto;
}

.breadcrumb-item {
  background: none;
  border: none;
  color: var(--color-primary);
  cursor: pointer;
  padding: 4px 8px;
  border-radius: var(--border-radius);
  font-size: 14px;
  white-space: nowrap;
  transition: background 0.2s;
}

.breadcrumb-item:hover {
  background: var(--color-primary-element-light);
}

.breadcrumb-separator {
  color: var(--color-text-maxcontrast);
  margin: 0 4px;
}

/* Folder Styling */
.media-item.is-folder {
  cursor: pointer;
}

.media-item.is-folder:hover {
  background: var(--color-primary-element-light);
}

.folder-icon {
  display: flex;
  align-items: center;
  justify-content: center;
  height: 100px;
  font-size: 48px;
}

.media-type {
  font-size: 12px;
  color: var(--color-text-maxcontrast);
}
</style>
