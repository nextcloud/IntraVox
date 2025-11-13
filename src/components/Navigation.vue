<template>
  <nav class="intravox-navigation">
    <!-- Mobile hamburger menu -->
    <div class="mobile-nav">
      <NcActions>
        <template v-for="item in items" :key="item.id">
          <!-- Top level items with children -->
          <template v-if="item.children && item.children.length > 0">
            <NcActionButton @click="toggleMobileItem(item.id)">
              <template #icon>
                <ChevronRight v-if="!isMobileItemExpanded(item.id)" :size="20" />
                <ChevronDown v-else :size="20" />
              </template>
              {{ item.title }}
            </NcActionButton>

            <!-- Level 2 items -->
            <template v-if="isMobileItemExpanded(item.id)">
              <template v-for="child in item.children" :key="child.id">
                <NcActionButton @click="child.children && child.children.length > 0 ? toggleMobileItem(child.id) : handleItemClick(child)"
                               class="mobile-nav-level-2">
                  <template #icon>
                    <ChevronRight v-if="child.children && child.children.length > 0 && !isMobileItemExpanded(child.id)" :size="20" />
                    <ChevronDown v-else-if="child.children && child.children.length > 0" :size="20" />
                  </template>
                  {{ child.title }}
                </NcActionButton>

                <!-- Level 3 items -->
                <template v-if="child.children && child.children.length > 0 && isMobileItemExpanded(child.id)">
                  <NcActionButton v-for="grandchild in child.children"
                                 :key="grandchild.id"
                                 @click="handleItemClick(grandchild)"
                                 class="mobile-nav-level-3">
                    {{ grandchild.title }}
                  </NcActionButton>
                </template>
              </template>
            </template>
          </template>

          <!-- Top level items without children -->
          <NcActionButton v-else @click="handleItemClick(item)">
            {{ item.title }}
          </NcActionButton>
        </template>
      </NcActions>
    </div>

    <!-- Desktop Navigation - Cascading Dropdown -->
    <div v-if="type === 'dropdown'" class="desktop-nav desktop-dropdown-nav">
      <template v-for="item in items" :key="item.id">
        <!-- Top level with children -->
        <div v-if="item.children && item.children.length > 0"
             class="dropdown-item"
             @mouseenter="showDropdown(item)"
             @mouseleave="hideDropdown">
          <a :href="getItemUrl(item)"
             :target="item.target || '_self'"
             @click.prevent="handleItemClick(item)"
             class="dropdown-trigger">
            {{ item.title }}
            <ChevronDown :size="16" class="chevron-icon" />
          </a>

          <!-- Dropdown menu -->
          <div v-if="activeDropdown === item.id"
               class="dropdown-menu"
               @mouseenter="keepDropdownOpen"
               @mouseleave="hideDropdown">
            <div class="dropdown-content">
              <!-- Level 2 items -->
              <template v-for="child in item.children" :key="child.id">
                <!-- Level 2 with children -->
                <div v-if="child.children && child.children.length > 0" class="dropdown-section">
                  <!-- Section header (Level 2) - clickable to expand/collapse -->
                  <div class="dropdown-section-header-wrapper">
                    <a v-if="child.pageId || child.url"
                       :href="getItemUrl(child)"
                       :target="child.target || '_self'"
                       @click.prevent="handleItemClick(child)"
                       class="dropdown-section-header">
                      {{ child.title }}
                    </a>
                    <div v-else
                         class="dropdown-section-header clickable"
                         @click="toggleDropdownSection(child.id)">
                      {{ child.title }}
                    </div>
                    <button class="dropdown-toggle-btn"
                            @click="toggleDropdownSection(child.id)"
                            :aria-label="isDropdownSectionExpanded(child.id) ? 'Collapse' : 'Expand'">
                      <ChevronDown v-if="isDropdownSectionExpanded(child.id)" :size="16" />
                      <ChevronRight v-else :size="16" />
                    </button>
                  </div>

                  <!-- Level 3 items - only show when expanded -->
                  <div v-if="isDropdownSectionExpanded(child.id)" class="dropdown-section-items">
                    <a v-for="grandchild in child.children"
                       :key="grandchild.id"
                       :href="getItemUrl(grandchild)"
                       :target="grandchild.target || '_self'"
                       @click.prevent="handleItemClick(grandchild)"
                       class="dropdown-section-item">
                      {{ grandchild.title }}
                    </a>
                  </div>
                </div>

                <!-- Level 2 without children -->
                <a v-else
                   :href="getItemUrl(child)"
                   :target="child.target || '_self'"
                   @click.prevent="handleItemClick(child)"
                   class="dropdown-item-link">
                  {{ child.title }}
                </a>
              </template>
            </div>
          </div>
        </div>

        <!-- Top level without children - regular link -->
        <a v-else
           :href="getItemUrl(item)"
           :target="item.target || '_self'"
           @click.prevent="handleItemClick(item)"
           class="nav-link">
          {{ item.title }}
        </a>
      </template>
    </div>

    <!-- Desktop Navigation - Mega Menu -->
    <div v-else-if="type === 'megamenu'" class="desktop-nav desktop-megamenu-nav">
      <template v-for="item in items" :key="item.id">
        <!-- Top level with children -->
        <div v-if="item.children && item.children.length > 0"
             class="megamenu-item"
             @mouseenter="showMegaMenu(item)"
             @mouseleave="hideMegaMenu">
          <a :href="getItemUrl(item)"
             :target="item.target || '_self'"
             @click.prevent="handleItemClick(item)"
             class="megamenu-trigger">
            {{ item.title }}
            <ChevronDown :size="16" class="chevron-icon" />
          </a>

          <!-- Mega menu dropdown -->
          <div v-if="activeMegaMenu === item.id"
               class="megamenu-dropdown"
               @mouseenter="keepMegaMenuOpen"
               @mouseleave="hideMegaMenu">
            <div class="megamenu-grid">
              <!-- Level 2 columns -->
              <div v-for="child in item.children"
                   :key="child.id"
                   class="megamenu-column">
                <!-- Column header (Level 2) -->
                <a v-if="child.pageId || child.url"
                   :href="getItemUrl(child)"
                   :target="child.target || '_self'"
                   @click.prevent="handleItemClick(child)"
                   class="megamenu-column-header">
                  {{ child.title }}
                </a>
                <div v-else class="megamenu-column-header">
                  {{ child.title }}
                </div>

                <!-- Level 3 items -->
                <ul v-if="child.children && child.children.length > 0" class="megamenu-list">
                  <li v-for="grandchild in child.children" :key="grandchild.id">
                    <a :href="getItemUrl(grandchild)"
                       :target="grandchild.target || '_self'"
                       @click.prevent="handleItemClick(grandchild)"
                       class="megamenu-list-item">
                      {{ grandchild.title }}
                    </a>
                  </li>
                </ul>
              </div>
            </div>
          </div>
        </div>

        <!-- Top level without children -->
        <a v-else
           :href="getItemUrl(item)"
           :target="item.target || '_self'"
           @click.prevent="handleItemClick(item)"
           class="nav-link">
          {{ item.title }}
        </a>
      </template>
    </div>
  </nav>
</template>

<script>
import { translate as t } from '@nextcloud/l10n';
import { NcActions, NcActionButton } from '@nextcloud/vue';
import ChevronDown from 'vue-material-design-icons/ChevronDown.vue';
import ChevronRight from 'vue-material-design-icons/ChevronRight.vue';

export default {
  name: 'Navigation',
  components: {
    NcActions,
    NcActionButton,
    ChevronDown,
    ChevronRight
  },
  props: {
    items: {
      type: Array,
      default: () => []
    },
    type: {
      type: String,
      default: 'dropdown',
      validator: (value) => ['dropdown', 'megamenu'].includes(value)
    }
  },
  emits: ['navigate'],
  data() {
    return {
      // Mobile menu state
      mobileExpandedItems: [],
      // Dropdown state
      activeDropdown: null,
      dropdownTimeout: null,
      dropdownExpandedSections: [],
      // Mega menu state
      activeMegaMenu: null,
      megaMenuTimeout: null
    };
  },
  methods: {
    t(key, vars = {}) {
      return t('intravox', key, vars);
    },
    getItemUrl(item) {
      if (item.url) {
        return item.url;
      }
      if (item.pageId) {
        return `#${item.pageId}`;
      }
      return '#';
    },
    handleItemClick(item) {
      this.$emit('navigate', item);
      this.hideDropdown();
      this.hideMegaMenu();
    },
    // Dropdown methods
    showDropdown(item) {
      if (this.dropdownTimeout) {
        clearTimeout(this.dropdownTimeout);
      }
      this.activeDropdown = item.id;
    },
    keepDropdownOpen() {
      if (this.dropdownTimeout) {
        clearTimeout(this.dropdownTimeout);
      }
    },
    hideDropdown() {
      this.dropdownTimeout = setTimeout(() => {
        this.activeDropdown = null;
        this.dropdownExpandedSections = [];
      }, 200);
    },
    toggleDropdownSection(sectionId) {
      const index = this.dropdownExpandedSections.indexOf(sectionId);
      if (index > -1) {
        this.dropdownExpandedSections.splice(index, 1);
      } else {
        this.dropdownExpandedSections.push(sectionId);
      }
    },
    isDropdownSectionExpanded(sectionId) {
      return this.dropdownExpandedSections.includes(sectionId);
    },
    // Mobile menu methods
    toggleMobileItem(itemId) {
      const index = this.mobileExpandedItems.indexOf(itemId);
      if (index > -1) {
        this.mobileExpandedItems.splice(index, 1);
      } else {
        this.mobileExpandedItems.push(itemId);
      }
    },
    isMobileItemExpanded(itemId) {
      return this.mobileExpandedItems.includes(itemId);
    },
    // Mega menu methods
    showMegaMenu(item) {
      if (this.megaMenuTimeout) {
        clearTimeout(this.megaMenuTimeout);
      }
      this.activeMegaMenu = item.id;
    },
    keepMegaMenuOpen() {
      if (this.megaMenuTimeout) {
        clearTimeout(this.megaMenuTimeout);
      }
    },
    hideMegaMenu() {
      this.megaMenuTimeout = setTimeout(() => {
        this.activeMegaMenu = null;
      }, 200);
    }
  }
};
</script>

<style scoped>
.intravox-navigation {
  display: flex;
  align-items: center;
  width: 100%;
  padding: 0 16px;
}

/* Mobile Navigation */
.mobile-nav {
  display: none;
}

.mobile-nav-level-2 :deep(.action-button__longtext) {
  padding-left: 32px !important;
  font-size: 14px !important;
  color: var(--color-text-maxcontrast) !important;
  font-weight: 400 !important;
}

.mobile-nav-level-3 :deep(.action-button__longtext) {
  padding-left: 56px !important;
  font-size: 13px !important;
  color: var(--color-text-maxcontrast) !important;
  font-weight: 300 !important;
}

/* Hover effects for mobile items */
.mobile-nav :deep(.action-button):hover {
  background-color: var(--color-background-hover) !important;
}

/* Desktop Navigation */
.desktop-nav {
  display: flex;
  align-items: center;
  gap: 4px;
  flex: 1;
}

/* Dropdown Navigation */
.desktop-dropdown-nav {
  gap: 0;
}

.dropdown-item {
  position: relative;
}

.dropdown-trigger {
  display: flex;
  align-items: center;
  gap: 4px;
  padding: 10px 16px;
  color: var(--color-main-text);
  text-decoration: none;
  font-weight: 600;
  white-space: nowrap;
  border-radius: var(--border-radius-large);
  transition: background-color 0.1s ease, opacity 0.1s ease;
  position: relative;
}

.dropdown-trigger:hover {
  background-color: var(--color-background-hover);
}

.dropdown-trigger:active {
  background-color: var(--color-primary-element-light);
}

.dropdown-trigger .chevron-icon {
  transition: transform 0.2s ease;
}

.dropdown-item:hover .chevron-icon {
  transform: rotate(180deg);
}

.dropdown-menu {
  position: absolute;
  top: 100%;
  left: 0;
  min-width: 250px;
  max-width: 350px;
  margin-top: 4px;
  background: var(--color-main-background);
  border: 1px solid var(--color-border);
  border-radius: var(--border-radius-large);
  box-shadow: 0 2px 8px var(--color-box-shadow);
  z-index: 1000;
  padding: 8px;
}

.dropdown-content {
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.dropdown-section {
  display: flex;
  flex-direction: column;
  gap: 4px;
  padding: 4px 0;
}

.dropdown-section + .dropdown-section {
  border-top: 1px solid var(--color-border);
  margin-top: 4px;
  padding-top: 8px;
}

.dropdown-section-header-wrapper {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 8px;
}

.dropdown-section-header {
  font-weight: 600;
  font-size: 14px;
  color: var(--color-main-text);
  padding: 8px 12px;
  text-decoration: none;
  border-radius: var(--border-radius);
  transition: background-color 0.1s ease;
  flex: 1;
}

.dropdown-section-header.clickable {
  cursor: pointer;
}

.dropdown-section-header.clickable:hover {
  background-color: var(--color-background-hover);
}

a.dropdown-section-header:hover {
  background-color: var(--color-background-hover);
}

a.dropdown-section-header:active,
.dropdown-section-header.clickable:active {
  background-color: var(--color-primary-element-light);
}

.dropdown-toggle-btn {
  background: none;
  border: none;
  padding: 8px;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  color: var(--color-main-text);
  border-radius: var(--border-radius);
  transition: background-color 0.1s ease;
  flex-shrink: 0;
}

.dropdown-toggle-btn:hover {
  background-color: var(--color-background-hover);
}

.dropdown-toggle-btn:active {
  background-color: var(--color-primary-element-light);
}

.dropdown-section-items {
  display: flex;
  flex-direction: column;
  gap: 2px;
  padding-left: 8px;
}

.dropdown-section-item {
  display: block;
  padding: 8px 12px;
  color: var(--color-main-text);
  text-decoration: none;
  font-size: 13px;
  border-radius: var(--border-radius);
  transition: background-color 0.1s ease;
}

.dropdown-section-item:hover {
  background-color: var(--color-background-hover);
}

.dropdown-section-item:active {
  background-color: var(--color-primary-element-light);
}

.dropdown-item-link {
  display: block;
  padding: 8px 12px;
  color: var(--color-main-text);
  text-decoration: none;
  font-size: 14px;
  font-weight: 500;
  border-radius: var(--border-radius);
  transition: background-color 0.1s ease;
}

.dropdown-item-link:hover {
  background-color: var(--color-background-hover);
}

.dropdown-item-link:active {
  background-color: var(--color-primary-element-light);
}

.nav-link {
  padding: 10px 16px;
  color: var(--color-main-text);
  text-decoration: none;
  border-radius: var(--border-radius-large);
  font-weight: 600;
  white-space: nowrap;
  transition: background-color 0.1s ease, opacity 0.1s ease;
  display: flex;
  align-items: center;
  gap: 4px;
  position: relative;
}

.nav-link:hover {
  background-color: var(--color-background-hover);
}

.nav-link:active {
  background-color: var(--color-primary-element-light);
}

/* Mega Menu Navigation */
.desktop-megamenu-nav {
  gap: 0;
}

.megamenu-item {
  position: relative;
}

.megamenu-trigger {
  display: flex;
  align-items: center;
  gap: 4px;
  padding: 10px 16px;
  color: var(--color-main-text);
  text-decoration: none;
  font-weight: 600;
  white-space: nowrap;
  border-radius: var(--border-radius-large);
  transition: background-color 0.1s ease, opacity 0.1s ease;
  position: relative;
}

.megamenu-trigger:hover {
  background-color: var(--color-background-hover);
}

.megamenu-trigger:active {
  background-color: var(--color-primary-element-light);
}

.megamenu-trigger .chevron-icon {
  transition: transform 0.2s ease;
}

.megamenu-item:hover .chevron-icon {
  transform: rotate(180deg);
}

.megamenu-dropdown {
  position: absolute;
  top: 100%;
  left: 0;
  min-width: 600px;
  max-width: 900px;
  margin-top: 4px;
  background: var(--color-main-background);
  border: 1px solid var(--color-border);
  border-radius: var(--border-radius-large);
  box-shadow: 0 2px 8px var(--color-box-shadow);
  z-index: 1000;
  padding: 16px;
}

.megamenu-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
  gap: 24px;
}

.megamenu-column {
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.megamenu-column-header {
  font-weight: 600;
  font-size: 14px;
  color: var(--color-main-text);
  padding: 8px 8px;
  text-decoration: none;
  border-bottom: 1px solid var(--color-border);
  margin-bottom: 4px;
  transition: background-color 0.1s ease;
  border-radius: var(--border-radius);
}

a.megamenu-column-header:hover {
  background-color: var(--color-background-hover);
}

a.megamenu-column-header:active {
  background-color: var(--color-primary-element-light);
}

.megamenu-list {
  list-style: none;
  padding: 0;
  margin: 0;
  display: flex;
  flex-direction: column;
  gap: 2px;
}

.megamenu-list-item {
  display: block;
  padding: 8px 12px;
  color: var(--color-main-text);
  text-decoration: none;
  font-size: 13px;
  border-radius: var(--border-radius);
  transition: background-color 0.1s ease;
}

.megamenu-list-item:hover {
  background-color: var(--color-background-hover);
}

.megamenu-list-item:active {
  background-color: var(--color-primary-element-light);
}

/* Responsive - Show mobile menu on smaller screens */
@media (max-width: 768px) {
  .mobile-nav {
    display: block;
  }

  .desktop-nav {
    display: none !important;
  }
}
</style>
