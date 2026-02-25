<template>
  <NcDialog
    :open="true"
    :name="t('RSS Feed')"
    size="normal"
    @close="$emit('close')">

    <div class="feed-settings">
      <!-- Admin disabled link sharing -->
      <NcNoteCard v-if="linkSharingDisabled" type="error">
        {{ t('RSS feeds are disabled. The administrator has turned off public link sharing in the Nextcloud Sharing settings.') }}
      </NcNoteCard>

      <template v-if="!linkSharingDisabled">
      <p class="feed-settings__description">
        {{ t('Generate a personal RSS feed URL to follow IntraVox updates in your favorite RSS reader. The feed only shows pages you have access to.') }}
      </p>

      <!-- Scope selection -->
      <div class="feed-settings__section">
        <h4 class="feed-settings__label">{{ t('Feed scope') }}</h4>
        <NcCheckboxRadioSwitch
          v-model="localConfig.scope"
          value="language"
          name="feed-scope"
          type="radio">
          {{ t('My language') }}
        </NcCheckboxRadioSwitch>
        <NcCheckboxRadioSwitch
          v-model="localConfig.scope"
          value="all"
          name="feed-scope"
          type="radio">
          {{ t('All languages') }}
        </NcCheckboxRadioSwitch>
      </div>

      <!-- Limit -->
      <div class="feed-settings__section">
        <h4 class="feed-settings__label">{{ t('Maximum items') }}</h4>
        <NcSelect
          v-model="localConfig.limit"
          :options="limitOptions"
          :clearable="false"
          :reduce="opt => opt" />
      </div>

      <!-- Feed URL (shown after token is generated) -->
      <div v-if="feedUrl" class="feed-settings__section">
        <h4 class="feed-settings__label">{{ t('Your feed URL') }}</h4>
        <div class="feed-settings__url-box">
          <input
            ref="urlInput"
            :value="feedUrl"
            class="feed-settings__url-input"
            readonly
            @focus="$refs.urlInput.select()" />
          <NcButton
            type="secondary"
            :aria-label="t('Copy URL')"
            @click="copyFeedUrl">
            <template #icon>
              <ContentCopy :size="20" />
            </template>
          </NcButton>
        </div>
        <p v-if="lastAccessed" class="feed-settings__meta">
          {{ t('Last accessed: {date}', { date: lastAccessed }) }}
        </p>
      </div>

      <!-- Warning about token secrecy -->
      <NcNoteCard v-if="feedUrl" type="warning">
        {{ t('This URL contains a personal token. Anyone with this link can read your feed. Do not share it publicly.') }}
      </NcNoteCard>
      </template>
    </div>

    <template v-if="!linkSharingDisabled" #actions>
      <NcButton v-if="feedUrl"
                type="error"
                @click="revokeToken">
        {{ t('Revoke') }}
      </NcButton>
      <NcButton v-if="feedUrl"
                type="secondary"
                @click="regenerateToken">
        {{ t('Regenerate') }}
      </NcButton>
      <NcButton v-if="!feedUrl"
                type="primary"
                :disabled="generating"
                @click="generateToken">
        {{ t('Generate Feed URL') }}
      </NcButton>
      <NcButton v-else
                type="primary"
                :disabled="saving"
                @click="saveConfig">
        {{ t('Save settings') }}
      </NcButton>
    </template>
  </NcDialog>
</template>

<script>
import axios from '@nextcloud/axios'
import { generateUrl } from '@nextcloud/router'
import { translate as t } from '@nextcloud/l10n'
import { showSuccess, showError } from '@nextcloud/dialogs'
import { NcDialog, NcButton, NcSelect, NcCheckboxRadioSwitch, NcNoteCard } from '@nextcloud/vue'
import ContentCopy from 'vue-material-design-icons/ContentCopy.vue'

export default {
  name: 'FeedSettings',
  components: {
    NcDialog,
    NcButton,
    NcSelect,
    NcCheckboxRadioSwitch,
    NcNoteCard,
    ContentCopy,
  },
  emits: ['close'],
  data() {
    return {
      loading: true,
      generating: false,
      saving: false,
      linkSharingDisabled: false,
      feedUrl: null,
      lastAccessed: null,
      localConfig: {
        scope: 'language',
        limit: 20,
      },
      limitOptions: [10, 20, 30, 50],
    }
  },
  async created() {
    await this.loadToken()
  },
  methods: {
    t(key, vars = {}) {
      return t('intravox', key, vars)
    },

    async loadToken() {
      this.loading = true
      try {
        const url = generateUrl('/apps/intravox/api/feed/token')
        const response = await axios.get(url)
        const data = response.data

        if (data.linkSharingDisabled) {
          this.linkSharingDisabled = true
          return
        }

        if (data.hasToken) {
          this.feedUrl = data.feedUrl
          this.lastAccessed = data.last_accessed
            ? new Date(data.last_accessed).toLocaleString()
            : null
          if (data.config) {
            this.localConfig = {
              scope: data.config.scope === 'all' ? 'all' : 'language',
              limit: data.config.limit || 20,
            }
          }
        }
      } catch (err) {
        showError(this.t('Failed to load feed settings'))
      } finally {
        this.loading = false
      }
    },

    async generateToken() {
      this.generating = true
      try {
        const url = generateUrl('/apps/intravox/api/feed/token')
        const response = await axios.post(url, {
          scope: this.localConfig.scope,
          limit: this.localConfig.limit,
        })
        const data = response.data

        if (data.hasToken) {
          this.feedUrl = data.feedUrl
          showSuccess(this.t('Feed URL generated'))
        }
      } catch (err) {
        showError(this.t('Failed to generate feed URL'))
      } finally {
        this.generating = false
      }
    },

    async regenerateToken() {
      this.generating = true
      try {
        const url = generateUrl('/apps/intravox/api/feed/token')
        const response = await axios.post(url, {
          scope: this.localConfig.scope,
          limit: this.localConfig.limit,
        })
        const data = response.data

        if (data.hasToken) {
          this.feedUrl = data.feedUrl
          showSuccess(this.t('Feed URL regenerated. The old URL no longer works.'))
        }
      } catch (err) {
        showError(this.t('Failed to regenerate feed URL'))
      } finally {
        this.generating = false
      }
    },

    async revokeToken() {
      try {
        const url = generateUrl('/apps/intravox/api/feed/token')
        await axios.delete(url)
        this.feedUrl = null
        this.lastAccessed = null
        showSuccess(this.t('Feed URL revoked'))
      } catch (err) {
        showError(this.t('Failed to revoke feed URL'))
      }
    },

    async saveConfig() {
      this.saving = true
      try {
        const url = generateUrl('/apps/intravox/api/feed/config')
        const response = await axios.put(url, {
          scope: this.localConfig.scope,
          limit: this.localConfig.limit,
        })

        if (response.data.feedUrl) {
          this.feedUrl = response.data.feedUrl
        }
        showSuccess(this.t('Feed settings saved'))
      } catch (err) {
        showError(this.t('Failed to save feed settings'))
      } finally {
        this.saving = false
      }
    },

    async copyFeedUrl() {
      try {
        await navigator.clipboard.writeText(this.feedUrl)
        showSuccess(this.t('Feed URL copied to clipboard'))
      } catch (err) {
        // Fallback: select the input text
        this.$refs.urlInput?.select()
        showError(this.t('Could not copy automatically. Please copy the selected URL.'))
      }
    },
  },
}
</script>

<style scoped>
.feed-settings {
  padding: 0 16px 16px;
}

.feed-settings__description {
  color: var(--color-text-maxcontrast);
  margin-bottom: 16px;
}

.feed-settings__section {
  margin-bottom: 16px;
}

.feed-settings__label {
  font-weight: 600;
  margin: 0 0 8px 0;
  font-size: 14px;
}

.feed-settings__url-box {
  display: flex;
  gap: 8px;
  align-items: center;
}

.feed-settings__url-input {
  flex: 1;
  padding: 8px 12px;
  border: 1px solid var(--color-border);
  border-radius: var(--border-radius);
  background: var(--color-background-dark);
  font-family: monospace;
  font-size: 12px;
  color: var(--color-text-maxcontrast);
}

.feed-settings__meta {
  margin-top: 4px;
  font-size: 12px;
  color: var(--color-text-maxcontrast);
}
</style>
