<template>
  <div class="confluence-import">
    <h2>Import from Confluence</h2>
    <p class="description">
      Import pages from Confluence Cloud, Server, or Data Center into IntraVox.
      Supported macros: Info/Note/Warning/Tip panels, Code blocks, Images, Attachments, and Expand/Collapse.
    </p>

    <!-- Import Method Selection -->
    <div class="import-method-selector">
      <button
        type="button"
        :class="{ active: importMethod === 'api' }"
        @click="importMethod = 'api'"
        class="method-btn"
      >
        📡 Direct API Import
      </button>
      <button
        type="button"
        :class="{ active: importMethod === 'html' }"
        @click="importMethod = 'html'"
        class="method-btn"
      >
        📁 Upload HTML Export
      </button>
    </div>

    <!-- HTML Export Upload Section -->
    <div v-if="importMethod === 'html'" class="html-export-section">
      <h3>Upload Confluence HTML Export</h3>
      <p class="description">
        Export your Confluence space as HTML (Space Tools → Content Tools → Export) and upload the ZIP file here.
      </p>

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

    <!-- API Import Section -->
    <div v-if="importMethod === 'api'" class="api-import-section">
      <!-- Connection Configuration -->
    <div class="config-section">
      <h3>Connection Settings</h3>

      <div class="form-group">
        <label for="confluence-url">Confluence URL *</label>
        <input
          id="confluence-url"
          v-model="config.baseUrl"
          type="url"
          placeholder="https://yoursite.atlassian.net/wiki or https://confluence.company.com"
          class="input-field"
          @blur="detectVersion"
        />
        <p class="hint">Enter the base URL of your Confluence instance</p>
      </div>

      <!-- Detected Version Display -->
      <div v-if="detectedVersion" class="version-badge" :class="'version-' + detectedVersion">
        <span class="badge-icon">✓</span>
        <span>Detected: {{ versionLabels[detectedVersion] }}</span>
      </div>

      <!-- Authentication Method -->
      <div class="form-group">
        <label>Authentication Method</label>
        <div class="auth-method-selector">
          <button
            type="button"
            :class="{ active: config.authType === 'api_token' }"
            @click="config.authType = 'api_token'"
            class="method-btn"
          >
            API Token (Cloud)
          </button>
          <button
            type="button"
            :class="{ active: config.authType === 'bearer' }"
            @click="config.authType = 'bearer'"
            class="method-btn"
          >
            Personal Access Token (Server v7.9+)
          </button>
          <button
            type="button"
            :class="{ active: config.authType === 'basic' }"
            @click="config.authType = 'basic'"
            class="method-btn"
          >
            Basic Auth (Server Legacy)
          </button>
        </div>
      </div>

      <!-- Auth Fields for API Token / Basic Auth -->
      <div v-if="config.authType === 'api_token' || config.authType === 'basic'" class="form-group">
        <label for="auth-user">Email / Username *</label>
        <input
          id="auth-user"
          v-model="config.authUser"
          type="text"
          :placeholder="config.authType === 'api_token' ? 'your.email@company.com' : 'username'"
          class="input-field"
        />
      </div>

      <!-- Auth Token Field -->
      <div class="form-group">
        <label for="auth-token">
          {{ config.authType === 'api_token' ? 'API Token' : config.authType === 'bearer' ? 'Personal Access Token' : 'Password' }} *
        </label>
        <input
          id="auth-token"
          v-model="config.authToken"
          type="password"
          placeholder="Enter your token/password"
          class="input-field"
        />
        <p v-if="config.authType === 'api_token'" class="hint">
          <a href="https://id.atlassian.com/manage-profile/security/api-tokens" target="_blank">Create an API token</a>
        </p>
      </div>

      <!-- Test Connection Button -->
      <div class="form-actions">
        <button
          type="button"
          @click="testConnection"
          :disabled="!canTestConnection || isTestingConnection"
          class="btn btn-secondary"
        >
          <span v-if="isTestingConnection" class="spinner"></span>
          {{ isTestingConnection ? 'Testing...' : 'Test Connection' }}
        </button>
      </div>

      <!-- Connection Status -->
      <div v-if="connectionStatus" class="status-message" :class="connectionStatus.success ? 'success' : 'error'">
        <span v-if="connectionStatus.success">
          ✓ Connected successfully as {{ connectionStatus.user }}
          <span class="version-label">({{ versionLabels[connectionStatus.version] }})</span>
        </span>
        <span v-else>
          ✗ Connection failed: {{ connectionStatus.error }}
        </span>
      </div>
    </div>

    <!-- Space Selection (only shown after successful connection) -->
    <div v-if="connectionStatus?.success" class="config-section">
      <h3>Select Space to Import</h3>

      <button
        v-if="!spaces.length"
        type="button"
        @click="loadSpaces"
        :disabled="isLoadingSpaces"
        class="btn btn-secondary"
      >
        <span v-if="isLoadingSpaces" class="spinner"></span>
        {{ isLoadingSpaces ? 'Loading...' : 'Load Spaces' }}
      </button>

      <div v-if="spaces.length" class="form-group">
        <label for="space-select">Space *</label>
        <select
          id="space-select"
          v-model="selectedSpace"
          class="input-field"
        >
          <option value="">Select a space...</option>
          <option v-for="space in spaces" :key="space.key" :value="space.key">
            {{ space.name }} ({{ space.key }})
          </option>
        </select>
        <p v-if="selectedSpaceInfo" class="hint">{{ selectedSpaceInfo.description }}</p>
      </div>

      <div class="form-group">
        <label for="target-language">Target Language *</label>
        <select
          id="target-language"
          v-model="config.language"
          class="input-field"
        >
          <option value="nl">Nederlands (nl)</option>
          <option value="en">English (en)</option>
          <option value="de">Deutsch (de)</option>
          <option value="fr">Français (fr)</option>
        </select>
        <p class="hint">Pages will be imported into this language folder in IntraVox</p>
      </div>
    </div>

    <!-- Import Button -->
    <div v-if="connectionStatus?.success && selectedSpace" class="config-section">
      <h3>Start Import</h3>

      <div class="form-actions">
        <button
          type="button"
          @click="startImport"
          :disabled="isImporting"
          class="btn btn-primary"
        >
          <span v-if="isImporting" class="spinner"></span>
          {{ isImporting ? 'Importing...' : 'Import Space' }}
        </button>
      </div>

      <!-- Import Progress -->
      <div v-if="importProgress" class="import-progress">
        <h4>Import Progress</h4>
        <div class="progress-stats">
          <div class="stat">
            <span class="stat-label">Pages:</span>
            <span class="stat-value">{{ importProgress.pagesImported || 0 }}</span>
          </div>
          <div class="stat">
            <span class="stat-label">Media Files:</span>
            <span class="stat-value">{{ importProgress.mediaFilesImported || 0 }}</span>
          </div>
        </div>
      </div>

      <!-- Import Result -->
      <div v-if="importResult" class="status-message" :class="importResult.success ? 'success' : 'error'">
        <span v-if="importResult.success">
          ✓ Import completed successfully!
          Imported {{ importResult.stats.pagesImported }} pages and {{ importResult.stats.mediaFilesImported }} media files.
        </span>
        <span v-else">
          ✗ Import failed: {{ importResult.error }}
        </span>
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
      importMethod: 'html', // 'html' or 'api'

      // HTML Export Upload
      selectedHtmlFile: null,
      selectedParentPageId: null,
      htmlImportLanguage: 'nl',
      isUploading: false,
      uploadProgress: null,
      uploadStatus: '',
      htmlImportResult: null,

      // API Import
      config: {
        baseUrl: '',
        authType: 'api_token',
        authUser: '',
        authToken: '',
        language: 'nl',
      },

      detectedVersion: null,
      connectionStatus: null,
      isTestingConnection: false,

      spaces: [],
      selectedSpace: '',
      isLoadingSpaces: false,

      isImporting: false,
      importProgress: null,
      importResult: null,

      versionLabels: {
        cloud: 'Confluence Cloud',
        server: 'Confluence Server',
        datacenter: 'Confluence Data Center',
      },
    }
  },

  computed: {
    canTestConnection() {
      return this.config.baseUrl &&
        this.config.authToken &&
        (this.config.authType === 'bearer' || this.config.authUser)
    },

    selectedSpaceInfo() {
      if (!this.selectedSpace) return null
      return this.spaces.find(s => s.key === this.selectedSpace)
    },
  },

  methods: {
    detectVersion() {
      // Auto-detect Cloud vs Server based on URL
      if (this.config.baseUrl.includes('.atlassian.net')) {
        this.detectedVersion = 'cloud'
        this.config.authType = 'api_token'
      } else {
        this.detectedVersion = 'server'
        this.config.authType = 'bearer'
      }
    },

    async testConnection() {
      this.isTestingConnection = true
      this.connectionStatus = null
      this.spaces = []
      this.selectedSpace = ''

      try {
        const response = await axios.post(
          generateUrl('/apps/intravox/api/import/confluence/test'),
          {
            baseUrl: this.config.baseUrl,
            authType: this.config.authType,
            authUser: this.config.authUser,
            authToken: this.config.authToken,
          }
        )

        this.connectionStatus = response.data
        this.detectedVersion = response.data.version

      } catch (error) {
        this.connectionStatus = {
          success: false,
          error: error.response?.data?.error || error.message,
        }
      } finally {
        this.isTestingConnection = false
      }
    },

    async loadSpaces() {
      this.isLoadingSpaces = true

      try {
        const response = await axios.post(
          generateUrl('/apps/intravox/api/import/confluence/spaces'),
          {
            baseUrl: this.config.baseUrl,
            authType: this.config.authType,
            authUser: this.config.authUser,
            authToken: this.config.authToken,
          }
        )

        this.spaces = response.data.spaces

      } catch (error) {
        console.error('Failed to load spaces:', error)
      } finally {
        this.isLoadingSpaces = false
      }
    },

    async startImport() {
      this.isImporting = true
      this.importProgress = { pagesImported: 0, mediaFilesImported: 0 }
      this.importResult = null

      try {
        const response = await axios.post(
          generateUrl('/apps/intravox/api/import/confluence'),
          {
            baseUrl: this.config.baseUrl,
            authType: this.config.authType,
            authUser: this.config.authUser,
            authToken: this.config.authToken,
            spaceKey: this.selectedSpace,
            language: this.config.language,
          }
        )

        this.importResult = response.data
        this.importProgress = response.data.stats

      } catch (error) {
        this.importResult = {
          success: false,
          error: error.response?.data?.error || error.message,
        }
      } finally {
        this.isImporting = false
      }
    },

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
.import-method-selector {
  display: flex;
  gap: 10px;
  margin-bottom: 30px;
}

.import-method-selector .method-btn {
  flex: 1;
  padding: 15px 20px;
  font-size: 16px;
  font-weight: 600;
  border: 2px solid #ddd;
  background: white;
  cursor: pointer;
  border-radius: 8px;
  transition: all 0.2s;
}

.import-method-selector .method-btn:hover {
  border-color: #0082c9;
  background: #f0f9ff;
}

.import-method-selector .method-btn.active {
  border-color: #0082c9;
  background: #0082c9;
  color: white;
}

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
