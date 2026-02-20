<template>
  <div class="person-item" :class="[`layout-${layout}`, itemBackgroundClass]">
    <!-- Avatar with status indicator -->
    <div v-if="showField('avatar')" class="person-avatar">
      <NcAvatar
        :user="user.uid"
        :display-name="user.displayName"
        :size="avatarSize"
        :show-user-status="!!user.status"
        :show-user-status-compact="layout === 'list'"
      />
    </div>

    <!-- Content -->
    <div class="person-content">
      <!-- Name and Pronouns -->
      <h4 v-if="showField('displayName')" class="person-name">
        {{ user.displayName }}
        <span v-if="showField('pronouns') && user.pronouns" class="person-pronouns">({{ user.pronouns }})</span>
      </h4>

      <!-- Role (official job title) -->
      <p v-if="showRole && user.role" class="person-role">
        {{ user.role }}
      </p>

      <!-- Headline (personal tagline) -->
      <p v-if="showHeadline && user.headline && user.headline !== user.role" class="person-headline">
        {{ user.headline }}
      </p>

      <!-- Department/Organisation -->
      <p v-if="showField('department') && (user.organisation || user.department)" class="person-department">
        {{ user.organisation || user.department }}
      </p>

      <!-- Contact info -->
      <div v-if="hasContactInfo" class="person-contact">
        <a
          v-if="showField('email') && user.email"
          :href="`mailto:${user.email}`"
          class="person-contact-item"
        >
          <EmailOutline :size="16" />
          <span>{{ user.email }}</span>
        </a>

        <a
          v-if="showField('phone') && user.phone"
          :href="`tel:${user.phone}`"
          class="person-contact-item"
        >
          <Phone :size="16" />
          <span>{{ user.phone }}</span>
        </a>

        <span
          v-if="showField('address') && user.address"
          class="person-contact-item person-address"
        >
          <MapMarker :size="16" />
          <span>{{ user.address }}</span>
        </span>

        <a
          v-if="showField('website') && user.website"
          :href="user.website"
          target="_blank"
          rel="noopener noreferrer"
          class="person-contact-item"
        >
          <Web :size="16" />
          <span>{{ formatWebsite(user.website) }}</span>
        </a>

        <span
          v-if="showField('birthdate') && user.birthdate"
          class="person-contact-item person-birthdate"
        >
          <CakeVariant :size="16" />
          <span>{{ formatBirthdate(user.birthdate) }}</span>
        </span>
      </div>

      <!-- Social links -->
      <div v-if="hasSocialLinks" class="person-social">
        <a
          v-if="user.twitter"
          :href="getTwitterUrl(user.twitter)"
          target="_blank"
          rel="noopener noreferrer"
          class="person-social-item"
          :title="user.twitter"
        >
          <Twitter :size="18" />
        </a>

        <a
          v-if="user.bluesky"
          :href="getBlueskyUrl(user.bluesky)"
          target="_blank"
          rel="noopener noreferrer"
          class="person-social-item"
          :title="user.bluesky"
        >
          <CloudOutline :size="18" />
        </a>

        <a
          v-if="user.fediverse"
          :href="getFediverseUrl(user.fediverse)"
          target="_blank"
          rel="noopener noreferrer"
          class="person-social-item"
          :title="user.fediverse"
        >
          <Mastodon :size="18" />
        </a>
      </div>

      <!-- Biography (only in card layout) -->
      <p
        v-if="layout === 'card' && showField('biography') && user.biography"
        class="person-biography"
      >
        {{ truncateBio(user.biography) }}
      </p>

      <!-- Custom fields from LDAP/OIDC -->
      <div
        v-if="showField('customFields') && customFields.length > 0"
        class="person-custom-fields"
      >
        <div
          v-for="field in customFields"
          :key="field.key"
          class="person-custom-field"
        >
          <span class="custom-field-label">{{ field.label }}:</span>
          <span class="custom-field-value">{{ field.value }}</span>
        </div>
      </div>

    </div>
  </div>
</template>

<script>
import { NcAvatar } from '@nextcloud/vue';
import EmailOutline from 'vue-material-design-icons/EmailOutline.vue';
import Phone from 'vue-material-design-icons/Phone.vue';
import MapMarker from 'vue-material-design-icons/MapMarker.vue';
import Web from 'vue-material-design-icons/Web.vue';
import Twitter from 'vue-material-design-icons/Twitter.vue';
import CloudOutline from 'vue-material-design-icons/CloudOutline.vue';
import Mastodon from 'vue-material-design-icons/Mastodon.vue';
import CakeVariant from 'vue-material-design-icons/CakeVariant.vue';

export default {
  name: 'PersonItem',
  components: {
    NcAvatar,
    EmailOutline,
    Phone,
    MapMarker,
    Web,
    Twitter,
    CloudOutline,
    Mastodon,
    CakeVariant,
  },
  props: {
    user: {
      type: Object,
      required: true,
    },
    layout: {
      type: String,
      default: 'card',
      validator: (value) => ['card', 'list', 'grid'].includes(value),
    },
    showFields: {
      type: Object,
      default: () => ({
        // Basic information
        avatar: true,
        displayName: true,
        pronouns: false,
        role: true,        // Official job title
        headline: false,   // Personal tagline
        title: true,       // Legacy: for backwards compatibility
        department: true,
        // Contact
        email: true,
        phone: false,
        address: false,
        website: false,
        birthdate: false,
        // Extended
        biography: false,
        socialLinks: false,
        customFields: false,
      }),
    },
    itemBackground: {
      type: String,
      default: 'default',
      validator: (value) => ['transparent', 'dark', 'white', 'default'].includes(value),
    },
  },
  computed: {
    avatarSize() {
      switch (this.layout) {
        case 'card':
          return 80;
        case 'list':
          return 44;
        case 'grid':
          return 64;
        default:
          return 64;
      }
    },
    itemBackgroundClass() {
      return `background-${this.itemBackground}`;
    },
    hasContactInfo() {
      return (this.showField('email') && this.user.email)
        || (this.showField('phone') && this.user.phone)
        || (this.showField('address') && this.user.address)
        || (this.showField('website') && this.user.website)
        || (this.showField('birthdate') && this.user.birthdate);
    },
    hasSocialLinks() {
      // Support both new socialLinks and legacy twitter/fediverse fields
      const showSocial = this.showField('socialLinks')
        || this.showField('twitter')
        || this.showField('fediverse');
      return showSocial && (this.user.twitter || this.user.bluesky || this.user.fediverse);
    },
    showRole() {
      return this.showField('role');
    },
    showHeadline() {
      return this.showField('headline');
    },
    customFields() {
      // Known/standard fields that are handled separately
      // Include camelCase, lowercase, and underscore variants as backend uses propertyToKey()
      // which converts PROPERTY_NAME to lowercase with underscores (e.g., profile_enabled)
      const knownFields = [
        'uid', 'displayName', 'displayname', 'pronouns', 'email', 'phone', 'address', 'website',
        'twitter', 'bluesky', 'fediverse', 'organisation', 'department', 'role', 'birthdate',
        'headline', 'biography', 'avatarUrl', 'groups', 'status',
        // Nextcloud internal fields - all possible naming variants
        'profileEnabled', 'profileenabled', 'profile_enabled',
      ];

      const fields = [];
      for (const [key, value] of Object.entries(this.user)) {
        // Skip known fields, null/empty values, and internal fields
        if (knownFields.includes(key)) continue;
        if (value === null || value === undefined || value === '') continue;
        if (typeof value === 'object') continue; // Skip complex objects

        // Create a readable label from the key
        const label = key
          .replace(/([A-Z])/g, ' $1') // camelCase to spaces
          .replace(/[_-]/g, ' ')       // underscores/dashes to spaces
          .replace(/^\w/, c => c.toUpperCase()) // capitalize first letter
          .trim();

        fields.push({ key, label, value: String(value) });
      }

      return fields;
    },
  },
  methods: {
    showField(fieldName) {
      return this.showFields[fieldName] !== false;
    },
    truncateBio(text, maxLength = 150) {
      if (!text || text.length <= maxLength) return text;
      return text.substring(0, maxLength).trim() + '...';
    },
    formatWebsite(url) {
      // Remove protocol and trailing slash for cleaner display
      return url.replace(/^https?:\/\//, '').replace(/\/$/, '');
    },
    getTwitterUrl(handle) {
      // Handle can be @username or just username
      const username = handle.replace(/^@/, '');
      return `https://x.com/${username}`;
    },
    getBlueskyUrl(handle) {
      // Handle can be @user.bsky.social, user.bsky.social, or a full URL
      if (handle.startsWith('http')) {
        return handle;
      }
      const username = handle.replace(/^@/, '');
      return `https://bsky.app/profile/${username}`;
    },
    formatBirthdate(dateStr) {
      if (!dateStr) return '';
      try {
        const date = new Date(dateStr + 'T00:00:00');
        if (isNaN(date.getTime())) {
          return dateStr;
        }
        return date.toLocaleDateString(undefined, { month: 'long', day: 'numeric' });
      } catch {
        return dateStr;
      }
    },
    getFediverseUrl(handle) {
      // Handle format: @user@instance.social
      if (handle.startsWith('@')) {
        const parts = handle.substring(1).split('@');
        if (parts.length === 2) {
          return `https://${parts[1]}/@${parts[0]}`;
        }
      }
      // If it's already a URL or unknown format, return as-is
      if (handle.startsWith('http')) {
        return handle;
      }
      return '#';
    },
  },
};
</script>

<style scoped>
.person-item {
  display: flex;
  gap: 12px;
  padding: 16px;
  border-radius: var(--border-radius-large);
  transition: background-color 0.2s ease;
  container-type: inline-size;
}

/* Layout variants */
.person-item.layout-card {
  flex-direction: column;
  align-items: center;
  text-align: center;
  background: var(--color-background-hover);
  min-width: 0; /* Allow shrinking in flex/grid containers */
  overflow: hidden;
}

.person-item.layout-list {
  flex-direction: row;
  align-items: center;
  background: transparent;
  padding: 8px 12px;
  border-bottom: 1px solid var(--color-border);
}

.person-item.layout-list:last-child {
  border-bottom: none;
}

.person-item.layout-grid {
  flex-direction: column;
  align-items: center;
  text-align: center;
  padding: 12px;
  background: var(--color-background-hover);
}

/* Background modes */
.person-item.background-transparent {
  background: transparent;
}

.person-item.background-dark {
  background: rgba(255, 255, 255, 0.1);
}

.person-item.background-dark .person-name,
.person-item.background-dark .person-role,
.person-item.background-dark .person-headline,
.person-item.background-dark .person-title,
.person-item.background-dark .person-department,
.person-item.background-dark .person-contact-item,
.person-item.background-dark .person-social-item,
.person-item.background-dark .person-biography,
.person-item.background-dark .person-custom-field,
.person-item.background-dark .custom-field-label,
.person-item.background-dark .custom-field-value {
  color: var(--color-primary-element-text);
}

.person-item.background-dark .custom-field-label {
  opacity: 0.8;
}

.person-item.background-white {
  background: rgba(255, 255, 255, 0.9);
}

.person-item.background-default {
  background: var(--color-background-hover);
}

/* Avatar */
.person-avatar {
  flex-shrink: 0;
}

.layout-card .person-avatar {
  display: flex;
  justify-content: center;
}

/* Content */
.person-content {
  flex: 1;
  min-width: 0;
  max-width: 100%;
  overflow: hidden;
}

.layout-card .person-content {
  width: 100%;
}

.person-name {
  margin: 0 0 4px 0;
  font-size: 16px;
  font-weight: 600;
  color: var(--color-main-text);
  line-height: 1.3;
  word-break: break-word;
  overflow-wrap: break-word;
}

.layout-card .person-name {
  /* Allow name to wrap naturally in card layout */
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

.person-pronouns {
  font-weight: 400;
  font-size: 14px;
  color: var(--color-text-maxcontrast);
}

.layout-grid .person-name {
  font-size: 14px;
  margin-top: 8px;
  /* Limit name to 2 lines for consistent grid item height */
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
  word-break: break-word;
}

/* Role - official job title */
.person-role {
  margin: 0 0 2px 0;
  font-size: 14px;
  font-weight: 500;
  color: var(--color-main-text);
  word-break: break-word;
  overflow-wrap: break-word;
}

.layout-card .person-role {
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

.layout-grid .person-role {
  display: -webkit-box;
  -webkit-line-clamp: 1;
  -webkit-box-orient: vertical;
  overflow: hidden;
  word-break: break-word;
}

/* Headline - personal tagline */
.person-headline {
  margin: 0 0 2px 0;
  font-size: 13px;
  font-style: italic;
  color: var(--color-text-maxcontrast);
  word-break: break-word;
  overflow-wrap: break-word;
}

.layout-card .person-headline {
  display: -webkit-box;
  -webkit-line-clamp: 1;
  -webkit-box-orient: vertical;
  overflow: hidden;
}


/* Legacy .person-title for backwards compatibility */
.person-title {
  margin: 0 0 2px 0;
  font-size: 14px;
  color: var(--color-text-maxcontrast);
  word-break: break-word;
  overflow-wrap: break-word;
}

.layout-card .person-title {
  /* Allow title to wrap in card layout */
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

.layout-grid .person-title {
  /* Limit title to 2 lines for consistent grid item height */
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
  word-break: break-word;
}

.person-department {
  margin: 0 0 8px 0;
  font-size: 13px;
  color: var(--color-text-maxcontrast);
  word-break: break-word;
  overflow-wrap: break-word;
}

/* Contact info */
.person-contact {
  display: flex;
  flex-direction: column;
  gap: 4px;
  margin-top: 8px;
}

.layout-card .person-contact {
  align-items: center;
}

.person-contact-item {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  color: var(--color-primary-element);
  text-decoration: none;
  font-size: 13px;
}

.person-contact-item.person-address,
.person-contact-item.person-birthdate {
  color: var(--color-text-maxcontrast);
}

a.person-contact-item:hover {
  text-decoration: underline;
}

.person-contact-item span {
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
  max-width: 100%;
}

/* Compact card layout for narrow containers */
.layout-card .person-contact-item {
  max-width: 100%;
}

.layout-card .person-contact-item span {
  max-width: calc(100% - 22px); /* Account for icon width */
}

/* Social links */
.person-social {
  display: flex;
  gap: 8px;
  margin-top: 8px;
}

.layout-card .person-social {
  justify-content: center;
}

.person-social-item {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 32px;
  height: 32px;
  border-radius: 50%;
  background: var(--color-background-dark);
  color: var(--color-main-text);
  text-decoration: none;
  transition: background-color 0.2s ease;
}

.person-social-item:hover {
  background: var(--color-primary-element);
  color: var(--color-primary-element-text);
}

/* Biography */
.person-biography {
  margin: 12px 0 0 0;
  font-size: 13px;
  color: var(--color-text-maxcontrast);
  line-height: 1.5;
}

/* Custom fields (LDAP/OIDC) */
.person-custom-fields {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 6px 12px;
  margin-top: 10px;
  padding-top: 10px;
  border-top: 1px solid var(--color-border);
  width: 100%;
}

.layout-card .person-custom-fields {
  text-align: center;
}

.layout-list .person-custom-fields {
  grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
}

.person-custom-field {
  display: flex;
  flex-direction: column;
  gap: 1px;
  font-size: 11px;
  color: var(--color-text-maxcontrast);
  min-width: 0;
  overflow: hidden;
}

.custom-field-label {
  font-weight: 600;
  color: var(--color-text-light);
  font-size: 10px;
  text-transform: uppercase;
  letter-spacing: 0.3px;
}

.custom-field-value {
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
  font-size: 12px;
}

/* Hover effects */
.person-item.layout-card:hover,
.person-item.layout-grid:hover {
  background: var(--color-background-dark);
}

.person-item.layout-list:hover {
  background: var(--color-background-hover);
}

.person-item.background-dark:hover {
  background: rgba(255, 255, 255, 0.15);
}

/* ========================================
   NARROW CONTAINER OPTIMIZATIONS
   For side columns and very narrow widgets
   ======================================== */

/* Card layout in narrow containers */
@container (max-width: 280px) {
  .person-item.layout-card {
    padding: 12px 10px;
    gap: 8px;
  }

  .person-item.layout-card .person-name {
    font-size: 14px;
  }

  .person-item.layout-card .person-role,
  .person-item.layout-card .person-headline,
  .person-item.layout-card .person-title,
  .person-item.layout-card .person-department {
    font-size: 12px;
  }

  .person-item.layout-card .person-headline {
    display: none; /* Hide headline in narrow containers */
  }

  .person-item.layout-card .person-contact {
    margin-top: 6px;
    gap: 3px;
  }

  .person-item.layout-card .person-contact-item {
    font-size: 11px;
    gap: 4px;
  }

  .person-item.layout-card .person-contact-item span {
    max-width: calc(100% - 20px);
  }

  /* Custom fields: single column in narrow containers */
  .person-item.layout-card .person-custom-fields {
    grid-template-columns: 1fr;
    gap: 4px;
    margin-top: 8px;
    padding-top: 8px;
  }

  .person-item.layout-card .custom-field-label {
    font-size: 9px;
  }

  .person-item.layout-card .custom-field-value {
    font-size: 11px;
  }

  /* Hide less important info in very narrow containers */
  .person-item.layout-card .person-social {
    gap: 6px;
  }

  .person-item.layout-card .person-social-item {
    width: 28px;
    height: 28px;
  }

  .person-item.layout-card .person-biography {
    font-size: 11px;
    margin-top: 8px;
    -webkit-line-clamp: 3;
    display: -webkit-box;
    -webkit-box-orient: vertical;
    overflow: hidden;
  }
}

/* Even narrower containers (< 200px) */
@container (max-width: 200px) {
  .person-item.layout-card {
    padding: 10px 8px;
  }

  .person-item.layout-card .person-name {
    font-size: 13px;
    -webkit-line-clamp: 1;
  }

  .person-item.layout-card .person-title {
    -webkit-line-clamp: 1;
  }

  /* Hide department in very narrow to reduce clutter */
  .person-item.layout-card .person-department {
    display: none;
  }

  /* Only show email, hide other contact methods */
  .person-item.layout-card .person-address,
  .person-item.layout-card .person-contact-item:not(:first-child) {
    display: none;
  }

  /* Hide custom fields and biography in very narrow */
  .person-item.layout-card .person-custom-fields,
  .person-item.layout-card .person-biography {
    display: none;
  }
}

/* ========================================
   LIST LAYOUT IN NARROW CONTAINERS
   Transform to card-like stacked layout
   ======================================== */

@container (max-width: 300px) {
  /* Transform list to vertical card-like layout */
  .person-item.layout-list {
    flex-direction: column;
    align-items: center;
    text-align: center;
    padding: 16px 12px;
    border-bottom: none;
    margin-bottom: 12px;
    background: rgba(255, 255, 255, 0.08);
    border-radius: var(--border-radius-large);
  }

  .person-item.layout-list:last-child {
    margin-bottom: 0;
  }

  /* Center avatar */
  .layout-list .person-avatar {
    margin-bottom: 8px;
  }

  /* Center content */
  .layout-list .person-content {
    width: 100%;
    text-align: center;
  }

  /* Contact items centered */
  .layout-list .person-contact {
    align-items: center;
  }

  /* Custom fields single column */
  .layout-list .person-custom-fields {
    grid-template-columns: 1fr;
    text-align: center;
  }
}

</style>
