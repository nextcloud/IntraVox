<template>
  <nav class="intravox-navigation">
    <!-- Page Structure Button (left of navigation) -->
    <button class="page-tree-btn"
            @click="handleTreeClick"
            :aria-label="t('Page structure')"
            :title="t('Page structure')">
      <FileTree :size="20" />
    </button>

    <!-- Mobile hamburger menu -->
    <div class="mobile-nav">
      <NcActions>
        <template #icon>
          <Menu :size="20" />
        </template>
        <template v-for="item in items" :key="getItemKey(item)">
          <!-- Top level items with children -->
          <template v-if="item.children && item.children.length > 0">
            <!-- Title navigates, chevron expands -->
            <NcActionButton @click="handleMobileItemClick(item)">
              <template #icon>
                <span class="mobile-expand-btn" @click.stop="toggleMobileItem(getItemKey(item))">
                  <ChevronRight v-if="!isMobileItemExpanded(getItemKey(item))" :size="20" />
                  <ChevronDown v-else :size="20" />
                </span>
              </template>
              {{ decodeHtmlEntities(item.title) }}
            </NcActionButton>

            <!-- Level 2 items -->
            <template v-if="isMobileItemExpanded(getItemKey(item))">
              <template v-for="child in item.children" :key="getItemKey(child)">
                <!-- Level 2 with children -->
                <NcActionButton v-if="child.children && child.children.length > 0"
                               @click="handleMobileItemClick(child)"
                               class="mobile-nav-level-2">
                  <template #icon>
                    <span class="mobile-expand-btn" @click.stop="toggleMobileItem(getItemKey(child))">
                      <ChevronRight v-if="!isMobileItemExpanded(getItemKey(child))" :size="20" />
                      <ChevronDown v-else :size="20" />
                    </span>
                  </template>
                  {{ decodeHtmlEntities(child.title) }}
                </NcActionButton>

                <!-- Level 2 without children -->
                <NcActionButton v-else
                               @click="handleItemClick(child)"
                               class="mobile-nav-level-2">
                  {{ decodeHtmlEntities(child.title) }}
                </NcActionButton>

                <!-- Level 3 items -->
                <template v-if="child.children && child.children.length > 0 && isMobileItemExpanded(getItemKey(child))">
                  <!-- Level 3 with children -->
                  <template v-for="grandchild in child.children" :key="getItemKey(grandchild)">
                    <NcActionButton v-if="grandchild.children && grandchild.children.length > 0"
                                   @click="handleMobileItemClick(grandchild)"
                                   class="mobile-nav-level-3">
                      <template #icon>
                        <span class="mobile-expand-btn" @click.stop="toggleMobileItem(getItemKey(grandchild))">
                          <ChevronRight v-if="!isMobileItemExpanded(getItemKey(grandchild))" :size="20" />
                          <ChevronDown v-else :size="20" />
                        </span>
                      </template>
                      {{ decodeHtmlEntities(grandchild.title) }}
                    </NcActionButton>

                    <!-- Level 3 without children -->
                    <NcActionButton v-else
                                   @click="handleItemClick(grandchild)"
                                   class="mobile-nav-level-3">
                      {{ decodeHtmlEntities(grandchild.title) }}
                    </NcActionButton>

                    <!-- Level 4 items -->
                    <template v-if="grandchild.children && grandchild.children.length > 0 && isMobileItemExpanded(getItemKey(grandchild))">
                      <NcActionButton v-for="greatGrandchild in grandchild.children"
                                     :key="getItemKey(greatGrandchild)"
                                     @click="handleItemClick(greatGrandchild)"
                                     class="mobile-nav-level-4">
                        {{ decodeHtmlEntities(greatGrandchild.title) }}
                      </NcActionButton>
                    </template>
                  </template>
                </template>
              </template>
            </template>
          </template>

          <!-- Top level items without children -->
          <NcActionButton v-else @click="handleItemClick(item)">
            {{ decodeHtmlEntities(item.title) }}
          </NcActionButton>
        </template>
      </NcActions>
    </div>

    <!-- Desktop Navigation - Cascading Dropdown -->
    <div v-if="type === 'dropdown'" class="desktop-nav desktop-dropdown-nav">
      <template v-for="item in items" :key="getItemKey(item)">
        <!-- Top level with children -->
        <div v-if="item.children && item.children.length > 0"
             class="dropdown-item"
             @mouseenter="showDropdown(item)"
             @mouseleave="hideDropdown">
          <a :href="getItemUrl(item)"
             :target="item.target || '_self'"
             @click.prevent="handleItemClick(item)"
             class="dropdown-trigger">
            {{ decodeHtmlEntities(item.title) }}
            <ChevronDown :size="16" class="chevron-icon" />
          </a>

          <!-- Dropdown menu -->
          <div v-if="activeDropdown === getItemKey(item)"
               class="dropdown-menu"
               @mouseenter="keepDropdownOpen"
               @mouseleave="hideDropdown">
            <div class="dropdown-content">
              <!-- Level 2 items -->
              <template v-for="child in item.children" :key="getItemKey(child)">
                <!-- Level 2 with children -->
                <div v-if="child.children && child.children.length > 0" class="dropdown-section">
                  <!-- Section header (Level 2) - clickable to expand/collapse -->
                  <div class="dropdown-section-header-wrapper">
                    <a v-if="child.uniqueId || child.url"
                       :href="getItemUrl(child)"
                       :target="child.target || '_self'"
                       @click.prevent="handleItemClick(child)"
                       class="dropdown-section-header">
                      {{ decodeHtmlEntities(child.title) }}
                    </a>
                    <div v-else
                         class="dropdown-section-header clickable"
                         @click="toggleDropdownSection(getItemKey(child))">
                      {{ decodeHtmlEntities(child.title) }}
                    </div>
                    <button class="dropdown-toggle-btn"
                            @click="toggleDropdownSection(getItemKey(child))"
                            :aria-label="isDropdownSectionExpanded(getItemKey(child)) ? 'Collapse' : 'Expand'">
                      <ChevronDown v-if="isDropdownSectionExpanded(getItemKey(child))" :size="16" />
                      <ChevronRight v-else :size="16" />
                    </button>
                  </div>

                  <!-- Level 3 items - only show when expanded -->
                  <div v-if="isDropdownSectionExpanded(getItemKey(child))" class="dropdown-section-items">
                    <a v-for="grandchild in child.children"
                       :key="getItemKey(grandchild)"
                       :href="getItemUrl(grandchild)"
                       :target="grandchild.target || '_self'"
                       @click.prevent="handleItemClick(grandchild)"
                       class="dropdown-section-item">
                      {{ decodeHtmlEntities(grandchild.title) }}
                    </a>
                  </div>
                </div>

                <!-- Level 2 without children -->
                <a v-else
                   :href="getItemUrl(child)"
                   :target="child.target || '_self'"
                   @click.prevent="handleItemClick(child)"
                   class="dropdown-item-link">
                  {{ decodeHtmlEntities(child.title) }}
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
          {{ decodeHtmlEntities(item.title) }}
        </a>
      </template>
    </div>

    <!-- Desktop Navigation - Mega Menu -->
    <div v-else-if="type === 'megamenu'" class="desktop-nav desktop-megamenu-nav">
      <template v-for="item in items" :key="getItemKey(item)">
        <!-- Top level with children -->
        <div v-if="item.children && item.children.length > 0"
             class="megamenu-item"
             @mouseenter="showMegaMenu(item)"
             @mouseleave="hideMegaMenu">
          <a :href="getItemUrl(item)"
             :target="item.target || '_self'"
             @click.prevent="handleItemClick(item)"
             class="megamenu-trigger">
            {{ decodeHtmlEntities(item.title) }}
            <ChevronDown :size="16" class="chevron-icon" />
          </a>

          <!-- Mega menu dropdown -->
          <div v-if="activeMegaMenu === getItemKey(item)"
               class="megamenu-dropdown"
               @mouseenter="keepMegaMenuOpen"
               @mouseleave="hideMegaMenu">
            <div class="megamenu-grid">
              <!-- Level 2 columns -->
              <div v-for="child in item.children"
                   :key="getItemKey(child)"
                   class="megamenu-column">
                <!-- Column header (Level 2) -->
                <a v-if="child.uniqueId || child.url"
                   :href="getItemUrl(child)"
                   :target="child.target || '_self'"
                   @click.prevent="handleItemClick(child)"
                   class="megamenu-column-header">
                  {{ decodeHtmlEntities(child.title) }}
                </a>
                <div v-else class="megamenu-column-header">
                  {{ decodeHtmlEntities(child.title) }}
                </div>

                <!-- Level 3 items -->
                <ul v-if="child.children && child.children.length > 0" class="megamenu-list">
                  <li v-for="grandchild in child.children" :key="getItemKey(grandchild)">
                    <a :href="getItemUrl(grandchild)"
                       :target="grandchild.target || '_self'"
                       @click.prevent="handleItemClick(grandchild)"
                       class="megamenu-list-item"
                       :class="{ 'has-children': grandchild.children && grandchild.children.length > 0 }">
                      {{ decodeHtmlEntities(grandchild.title) }}
                    </a>

                    <!-- Level 4 items (nested under level 3) -->
                    <ul v-if="grandchild.children && grandchild.children.length > 0" class="megamenu-sublist">
                      <li v-for="greatGrandchild in grandchild.children" :key="getItemKey(greatGrandchild)">
                        <a :href="getItemUrl(greatGrandchild)"
                           :target="greatGrandchild.target || '_self'"
                           @click.prevent="handleItemClick(greatGrandchild)"
                           class="megamenu-sublist-item">
                          {{ decodeHtmlEntities(greatGrandchild.title) }}
                        </a>
                      </li>
                    </ul>
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
          {{ decodeHtmlEntities(item.title) }}
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
import Menu from 'vue-material-design-icons/Menu.vue';
import FileTree from 'vue-material-design-icons/FileTree.vue';

export default {
  name: 'Navigation',
  components: {
    NcActions,
    NcActionButton,
    ChevronDown,
    ChevronRight,
    Menu,
    FileTree
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
  emits: ['navigate', 'show-tree'],
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
    getItemKey(item) {
      // Generate a unique key for Vue's :key attribute
      return item.uniqueId || item.title.replace(/\s+/g, '-').toLowerCase();
    },
    decodeHtmlEntities(text) {
      if (!text) return '';
      const textarea = document.createElement('textarea');
      textarea.innerHTML = text;
      return textarea.value;
    },
    getItemUrl(item) {
      if (item.url) {
        return item.url;
      }
      if (item.uniqueId) {
        return `#${item.uniqueId}`;
      }
      return '#';
    },
    handleTreeClick(event) {
      // Remove focus from button to prevent "active" state after modal closes
      event.target.blur();
      this.$emit('show-tree');
    },
    handleItemClick(item) {
      this.$emit('navigate', item);
      this.hideDropdown();
      this.hideMegaMenu();
    },
    handleMobileItemClick(item) {
      // Only navigate if item has a link (uniqueId or url)
      if (item.uniqueId || item.url) {
        this.$emit('navigate', item);
      } else {
        // If no link, toggle expand instead
        this.toggleMobileItem(this.getItemKey(item));
      }
    },
    // Dropdown methods
    showDropdown(item) {
      if (this.dropdownTimeout) {
        clearTimeout(this.dropdownTimeout);
      }
      this.activeDropdown = this.getItemKey(item);
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
      this.activeMegaMenu = this.getItemKey(item);
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

/* Page Tree Button */
.page-tree-btn {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 36px;
  height: 36px;
  padding: 0;
  margin-right: 8px;
  background: none;
  border: none;
  border-radius: var(--border-radius);
  color: var(--color-main-text);
  cursor: pointer;
  transition: background-color 0.1s ease;
  flex-shrink: 0;
}

.page-tree-btn:hover {
  background-color: var(--color-background-hover);
}

.page-tree-btn:active {
  background-color: var(--color-primary-element-light);
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

.mobile-nav-level-4 :deep(.action-button__longtext) {
  padding-left: 80px !important;
  font-size: 12px !important;
  color: var(--color-text-maxcontrast) !important;
  font-weight: 300 !important;
}

/* Mobile expand button - larger clickable area */
.mobile-expand-btn {
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 8px;
  margin: -8px;
  border-radius: var(--border-radius);
  cursor: pointer;
  transition: background-color 0.1s ease;
}

.mobile-expand-btn:hover {
  background-color: var(--color-background-dark);
}

.mobile-expand-btn:active {
  background-color: var(--color-primary-element-light);
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
  box-shadow: 0 4px 16px var(--color-box-shadow);
  z-index: 1000;
  padding: 24px;
}

.megamenu-grid {
  display: flex;
  flex-wrap: wrap;
  gap: 0;
  column-gap: 32px;
}

.megamenu-column {
  display: flex;
  flex-direction: column;
  gap: 0;
  padding: 16px 0;
  border-right: 1px solid var(--color-border);
  padding-right: 32px;
  min-width: 200px;
  flex: 0 0 auto;
}

.megamenu-column:last-child {
  border-right: none;
  padding-right: 0;
}

.megamenu-column-header {
  font-weight: 700;
  font-size: 13px;
  color: var(--color-main-text);
  padding: 0 0 12px 0;
  text-decoration: none;
  border-bottom: 2px solid var(--color-primary-element);
  margin-bottom: 12px;
  transition: color 0.1s ease;
  text-transform: none;
  letter-spacing: normal;
}

a.megamenu-column-header:hover {
  color: var(--color-primary-element);
}

a.megamenu-column-header:active {
  color: var(--color-primary-element);
}

.megamenu-list {
  list-style: none;
  padding: 0;
  margin: 0;
  display: flex;
  flex-direction: column;
  gap: 0;
}

.megamenu-list-item {
  display: block;
  padding: 10px 0;
  color: var(--color-text-maxcontrast);
  text-decoration: none;
  font-size: 14px;
  font-weight: 400;
  transition: color 0.1s ease;
  line-height: 1.4;
}

.megamenu-list-item:hover {
  color: var(--color-main-text);
}

.megamenu-list-item:active {
  color: var(--color-primary-element);
}

.megamenu-list-item.has-children {
  font-weight: 500;
}

.megamenu-sublist {
  list-style: none;
  padding: 0 0 0 16px;
  margin: 4px 0 0 0;
  display: flex;
  flex-direction: column;
  gap: 0;
}

.megamenu-sublist-item {
  display: block;
  padding: 8px 0;
  color: var(--color-text-lighter);
  text-decoration: none;
  font-size: 13px;
  font-weight: 400;
  transition: color 0.1s ease;
  line-height: 1.4;
}

.megamenu-sublist-item:hover {
  color: var(--color-main-text);
}

.megamenu-sublist-item:active {
  color: var(--color-primary-element);
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
