<template>
  <div class="confluence-import">
    <h2>Import from Confluence</h2>
    <p class="description">
      Import pages from Confluence by uploading an HTML export ZIP file.
      Export your Confluence space as HTML (Space Tools → Content Tools → Export) and upload it here.
    </p>

    <!-- HTML Export Upload Section -->
    <div class="html-export-section">
      <div class="form-group">
        <label for="html-language">Target Language</label>
        <select id="html-language" v-model="htmlImportLanguage" class="input-field" @change="onLanguageChange">
          <option value="nl">Nederlands</option>
          <option value="en">English</option>
        </select>
        <p class="hint">Select the language where pages will be imported</p>
      </div>

      <div class="form-group">
        <label for="html-parent-page">Import Location</label>
        <PageTreeSelect
          v-model="selectedParentPageId"
          :key="htmlImportLanguage"
          :language="htmlImportLanguage"
          placeholder="Select parent page (leave empty for language root)"
          :clearable="true"
        />
        <p class="hint">Select where to place imported pages. Leave empty to import at language root level.</p>
      </div>

      <div class="upload-area">
        <input
          ref="htmlFileInput"
          type="file"
          accept=".zip,application/zip"
          @change="handleHtmlFileSelect"
          class="file-input"
        />
        <div v-if="!selectedHtmlFile" class="upload-prompt" @click="$refs.htmlFileInput.click()">
          <div class="upload-icon">📦</div>
          <p>Click to select Confluence HTML export ZIP file</p>
          <p class="hint">or drag and drop</p>
        </div>
        <div v-else class="file-selected">
          <span class="file-icon">📄</span>
          <span class="file-name">{{ selectedHtmlFile.name }}</span>
          <span class="file-size">({{ formatFileSize(selectedHtmlFile.size) }})</span>
          <button type="button" @click="clearHtmlFile" class="btn-remove">✕</button>
        </div>
      </div>

      <div class="form-actions">
        <button
          type="button"
          @click="uploadHtmlExport"
          :disabled="!selectedHtmlFile || isUploading"
          class="btn btn-primary"
        >
          <span v-if="isUploading" class="spinner"></span>
          {{ isUploading ? 'Importing...' : 'Import HTML Export' }}
        </button>
      </div>

      <!-- Import Progress -->
      <div v-if="uploadProgress" class="progress-section">
        <div class="progress-bar">
          <div class="progress-fill" :style="{ width: uploadProgress + '%' }"></div>
        </div>
        <p class="progress-text">{{ uploadProgress }}% - {{ uploadStatus }}</p>
      </div>

      <!-- Import Result -->
      <div v-if="htmlImportResult" class="result-message" :class="htmlImportResult.success ? 'success' : 'error'">
        <span class="result-icon">{{ htmlImportResult.success ? '✓' : '✕' }}</span>
        <div class="result-content">
          <strong>{{ htmlImportResult.success ? 'Import Successful!' : 'Import Failed' }}</strong>
          <p v-if="htmlImportResult.message">{{ htmlImportResult.message }}</p>
          <p v-if="htmlImportResult.pages">Imported {{ htmlImportResult.pages }} page(s)</p>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import axios from '@nextcloud/axios'
import { generateUrl } from '@nextcloud/router'
import PageTreeSelect from '../../components/PageTreeSelect.vue'

export default {
  name: 'ConfluenceImport',

  components: {
    PageTreeSelect
  },

  data() {
    return {
      // HTML Export Upload
      selectedHtmlFile: null,
      selectedParentPageId: null,
      htmlImportLanguage: 'nl',
      isUploading: false,
      uploadProgress: null,
      uploadStatus: '',
      htmlImportResult: null,
    }
  },

  methods: {
    // HTML Export Upload Methods
    handleHtmlFileSelect(event) {
      const file = event.target.files[0]
      if (file) {
        this.selectedHtmlFile = file
        this.htmlImportResult = null
      }
    },

    clearHtmlFile() {
      this.selectedHtmlFile = null
      this.htmlImportResult = null
      if (this.$refs.htmlFileInput) {
        this.$refs.htmlFileInput.value = ''
      }
    },

    formatFileSize(bytes) {
      if (bytes === 0) return '0 Bytes'
      const k = 1024
      const sizes = ['Bytes', 'KB', 'MB', 'GB']
      const i = Math.floor(Math.log(bytes) / Math.log(k))
      return Math.round(bytes / Math.pow(k, i) * 100) / 100 + ' ' + sizes[i]
    },

    onLanguageChange() {
      // Reset parent page selection when language changes
      this.selectedParentPageId = null
    },

    async uploadHtmlExport() {
      if (!this.selectedHtmlFile) return

      this.isUploading = true
      this.uploadProgress = 0
      this.uploadStatus = 'Uploading...'
      this.htmlImportResult = null

      try {
        const formData = new FormData()
        formData.append('file', this.selectedHtmlFile)
        formData.append('language', this.htmlImportLanguage)
        if (this.selectedParentPageId) {
          formData.append('parentPageId', this.selectedParentPageId)
        }

        const response = await axios.post(
          generateUrl('/apps/intravox/api/import/confluence/html'),
          formData,
          {
            headers: {
              'Content-Type': 'multipart/form-data'
            },
            onUploadProgress: (progressEvent) => {
              this.uploadProgress = Math.round(
                (progressEvent.loaded * 100) / progressEvent.total
              )
              if (this.uploadProgress === 100) {
                this.uploadStatus = 'Processing import...'
              }
            },
          }
        )

        this.htmlImportResult = response.data
        this.uploadProgress = null
        this.uploadStatus = ''

        // Clear file selection on success
        if (response.data.success) {
          setTimeout(() => {
            this.clearHtmlFile()
          }, 3000)
        }

      } catch (error) {
        console.error('HTML import failed:', error)
        this.htmlImportResult = {
          success: false,
          message: error.response?.data?.error || error.message,
        }
        this.uploadProgress = null
        this.uploadStatus = ''
      } finally {
        this.isUploading = false
      }
    },
  },
}
</script>

<style scoped>
.confluence-import {
  max-width: 800px;
  margin: 0 auto;
  padding: 20px;
}

h2 {
  font-size: 24px;
  font-weight: 600;
  margin-bottom: 10px;
}

h3 {
  font-size: 18px;
  font-weight: 600;
  margin-bottom: 15px;
}

.description {
  color: #666;
  margin-bottom: 30px;
  line-height: 1.6;
}

.config-section {
  background: #f8f9fa;
  border-radius: 8px;
  padding: 20px;
  margin-bottom: 20px;
}

.form-group {
  margin-bottom: 20px;
}

label {
  display: block;
  font-weight: 600;
  margin-bottom: 8px;
}

.input-field {
  width: 100%;
  padding: 10px 12px;
  border: 1px solid #ddd;
  border-radius: 4px;
  font-size: 14px;
}

.input-field:focus {
  outline: none;
  border-color: #0082c9;
  box-shadow: 0 0 0 2px rgba(0, 130, 201, 0.1);
}

.hint {
  font-size: 12px;
  color: #666;
  margin-top: 5px;
}

.hint a {
  color: #0082c9;
  text-decoration: none;
}

.hint a:hover {
  text-decoration: underline;
}

.auth-method-selector,
.form-actions {
  display: flex;
  gap: 10px;
}

.method-btn,
.btn {
  padding: 10px 20px;
  border: 1px solid #ddd;
  border-radius: 4px;
  background: white;
  cursor: pointer;
  font-size: 14px;
  transition: all 0.2s;
}

.method-btn:hover,
.btn:hover:not(:disabled) {
  background: #f0f0f0;
}

.method-btn.active {
  background: #0082c9;
  color: white;
  border-color: #0082c9;
}

.btn-primary {
  background: #0082c9;
  color: white;
  border-color: #0082c9;
}

.btn-primary:hover:not(:disabled) {
  background: #006ba6;
}

.btn:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.version-badge {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  padding: 8px 12px;
  border-radius: 4px;
  font-size: 14px;
  font-weight: 600;
  margin-bottom: 15px;
}

.version-cloud {
  background: #e3f2fd;
  color: #1976d2;
}

.version-server,
.version-datacenter {
  background: #f3e5f5;
  color: #7b1fa2;
}

.status-message {
  padding: 12px 16px;
  border-radius: 4px;
  margin-top: 15px;
}

.status-message.success {
  background: #d4edda;
  color: #155724;
  border: 1px solid #c3e6cb;
}

.status-message.error {
  background: #f8d7da;
  color: #721c24;
  border: 1px solid #f5c6cb;
}

.version-label {
  font-weight: normal;
  opacity: 0.8;
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
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

.import-progress {
  margin-top: 20px;
  padding: 15px;
  background: white;
  border-radius: 4px;
  border: 1px solid #ddd;
}

.import-progress h4 {
  font-size: 16px;
  margin-bottom: 15px;
}

.progress-stats {
  display: flex;
  gap: 30px;
}

.stat {
  display: flex;
  flex-direction: column;
  gap: 5px;
}

.stat-label {
  font-size: 12px;
  color: #666;
  text-transform: uppercase;
}

.stat-value {
  font-size: 24px;
  font-weight: 600;
  color: #0082c9;
}

/* HTML Export Upload Styles */
.html-export-section {
  background: #f8f9fa;
  border-radius: 8px;
  padding: 20px;
}

.upload-area {
  margin: 20px 0;
}

.file-input {
  display: none;
}

.upload-prompt {
  border: 2px dashed #ccc;
  border-radius: 8px;
  padding: 40px 20px;
  text-align: center;
  cursor: pointer;
  transition: all 0.2s;
}

.upload-prompt:hover {
  border-color: #0082c9;
  background: #f0f9ff;
}

.upload-icon {
  font-size: 48px;
  margin-bottom: 10px;
}

.file-selected {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 15px;
  background: white;
  border: 1px solid #ddd;
  border-radius: 8px;
}

.file-icon {
  font-size: 24px;
}

.file-name {
  flex: 1;
  font-weight: 600;
}

.file-size {
  color: #666;
  font-size: 14px;
}

.btn-remove {
  background: none;
  border: none;
  cursor: pointer;
  font-size: 20px;
  color: #999;
  padding: 5px 10px;
}

.btn-remove:hover {
  color: #e74c3c;
}

.progress-section {
  margin-top: 20px;
}

.progress-bar {
  width: 100%;
  height: 8px;
  background: #e0e0e0;
  border-radius: 4px;
  overflow: hidden;
}

.progress-fill {
  height: 100%;
  background: #0082c9;
  transition: width 0.3s;
}

.progress-text {
  margin-top: 10px;
  text-align: center;
  color: #666;
}

.result-message {
  display: flex;
  align-items: flex-start;
  gap: 15px;
  padding: 15px;
  border-radius: 8px;
  margin-top: 20px;
}

.result-message.success {
  background: #d4edda;
  border: 1px solid #c3e6cb;
  color: #155724;
}

.result-message.error {
  background: #f8d7da;
  border: 1px solid #f5c6cb;
  color: #721c24;
}

.result-icon {
  font-size: 24px;
  line-height: 1;
}

.result-content {
  flex: 1;
}

.result-content strong {
  display: block;
  margin-bottom: 5px;
}

.result-content p {
  margin: 5px 0;
}
</style>
