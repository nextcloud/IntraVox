<template>
  <nav class="intravox-navigation" :class="`navigation-${type}`">
    <!-- Dropdown Navigation (Default) -->
    <div v-if="type === 'dropdown'" class="navigation-dropdown">
      <template v-for="item in items" :key="item.id">
        <!-- Top level item with children -->
        <div v-if="item.children && item.children.length > 0"
             class="dropdown-item"
             @mouseenter="showDropdown(item)"
             @mouseleave="hideDropdown">
          <a :href="getItemUrl(item)"
             :target="item.target || '_self'"
             @click.prevent="navigateTo(item)"
             class="dropdown-link">
            {{ item.title }}
            <ChevronDown :size="16" class="chevron-icon" />
          </a>

          <!-- Dropdown menu -->
          <div v-if="activeDropdown === item.id"
               class="dropdown-menu"
               @mouseenter="keepDropdownOpen"
               @mouseleave="hideDropdown">
            <!-- Level 2 items -->
            <template v-for="child in item.children" :key="child.id">
              <!-- Level 2 with children (Level 3) -->
              <div v-if="child.children && child.children.length > 0"
                   class="dropdown-menu-item has-submenu"
                   @mouseenter="showSubmenu(child)"
                   @mouseleave="hideSubmenu">
                <a :href="getItemUrl(child)"
                   :target="child.target || '_self'"
                   @click.prevent="navigateTo(child)"
                   class="dropdown-menu-link">
                  {{ child.title }}
                  <ChevronRight :size="16" class="chevron-right-icon" />
                </a>

                <!-- Level 3 submenu -->
                <div v-if="activeSubmenu === child.id"
                     class="dropdown-submenu"
                     @mouseenter="keepSubmenuOpen"
                     @mouseleave="hideSubmenu">
                  <a v-for="grandchild in child.children"
                     :key="grandchild.id"
                     :href="getItemUrl(grandchild)"
                     :target="grandchild.target || '_self'"
                     @click.prevent="navigateTo(grandchild)"
                     class="dropdown-submenu-link">
                    {{ grandchild.title }}
                  </a>
                </div>
              </div>

              <!-- Level 2 without children -->
              <a v-else
                 :href="getItemUrl(child)"
                 :target="child.target || '_self'"
                 @click.prevent="navigateTo(child)"
                 class="dropdown-menu-link">
                {{ child.title }}
              </a>
            </template>
          </div>
        </div>

        <!-- Top level without children -->
        <a v-else
           :href="getItemUrl(item)"
           :target="item.target || '_self'"
           @click.prevent="navigateTo(item)"
           class="nav-item-link">
          {{ item.title }}
        </a>
      </template>
    </div>

    <!-- Mega Menu Navigation -->
    <div v-else-if="type === 'megamenu'" class="navigation-megamenu">
      <template v-for="item in items" :key="item.id">
        <div class="megamenu-item" @mouseenter="showMegaMenu(item)" @mouseleave="hideMegaMenu">
          <a :href="getItemUrl(item)"
             :target="item.target || '_self'"
             @click.prevent="navigateTo(item)"
             class="megamenu-link">
            {{ item.title }}
            <ChevronDown v-if="item.children && item.children.length > 0" :size="16" class="chevron-icon" />
          </a>

          <!-- Mega menu panel -->
          <div v-if="item.children && item.children.length > 0 && activeMegaMenu === item.id"
               class="megamenu-panel"
               @mouseenter="keepMegaMenuOpen"
               @mouseleave="hideMegaMenu">
            <div class="megamenu-content">
              <div v-for="child in item.children"
                   :key="child.id"
                   class="megamenu-column">
                <a :href="getItemUrl(child)"
                   :target="child.target || '_self'"
                   @click.prevent="navigateTo(child)"
                   class="megamenu-column-title">
                  {{ child.title }}
                </a>

                <!-- Level 3 items -->
                <ul v-if="child.children && child.children.length > 0" class="megamenu-list">
                  <li v-for="grandchild in child.children" :key="grandchild.id">
                    <a :href="getItemUrl(grandchild)"
                       :target="grandchild.target || '_self'"
                       @click.prevent="navigateTo(grandchild)"
                       class="megamenu-list-item">
                      {{ grandchild.title }}
                    </a>
                  </li>
                </ul>
              </div>
            </div>
          </div>
        </div>
      </template>
    </div>

  </nav>
</template>

<script>
import { translate as t } from '@nextcloud/l10n';
import ChevronDown from 'vue-material-design-icons/ChevronDown.vue';
import ChevronRight from 'vue-material-design-icons/ChevronRight.vue';

export default {
  name: 'Navigation',
  components: {
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
      activeMegaMenu: null,
      megaMenuTimeout: null,
      activeDropdown: null,
      dropdownTimeout: null,
      activeSubmenu: null,
      submenuTimeout: null
    };
  },
  methods: {
    t(key, vars = {}) {
      return t('intravox', key, vars);
    },
    navigateTo(item) {
      this.$emit('navigate', item);
      this.hideMegaMenu();
    },
    getItemUrl(item) {
      if (item.url) {
        return item.url;
      }
      if (item.pageId) {
        return `#page-${item.pageId}`;
      }
      return '#';
    },
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
    },
    showDropdown(item) {
      if (this.dropdownTimeout) {
        clearTimeout(this.dropdownTimeout);
      }
      this.activeDropdown = item.id;
      this.activeSubmenu = null;
    },
    keepDropdownOpen() {
      if (this.dropdownTimeout) {
        clearTimeout(this.dropdownTimeout);
      }
    },
    hideDropdown() {
      this.dropdownTimeout = setTimeout(() => {
        this.activeDropdown = null;
        this.activeSubmenu = null;
      }, 200);
    },
    showSubmenu(child) {
      if (this.submenuTimeout) {
        clearTimeout(this.submenuTimeout);
      }
      this.activeSubmenu = child.id;
    },
    keepSubmenuOpen() {
      if (this.submenuTimeout) {
        clearTimeout(this.submenuTimeout);
      }
    },
    hideSubmenu() {
      this.submenuTimeout = setTimeout(() => {
        this.activeSubmenu = null;
      }, 200);
    }
  }
};
</script>

<style scoped>
.intravox-navigation {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 0 16px;
  min-height: 50px;
}

/* Dropdown Navigation */
.navigation-dropdown {
  display: flex;
  align-items: center;
  gap: 4px;
  flex: 1;
}

.nav-item-link {
  padding: 8px 12px;
  color: var(--color-main-text);
  text-decoration: none;
  border-radius: var(--border-radius);
  font-weight: 500;
  white-space: nowrap;
  transition: background-color 0.1s ease;
}

.nav-item-link:hover {
  background-color: var(--color-background-hover);
}

/* Dropdown item */
.dropdown-item {
  position: relative;
  display: inline-block;
}

.dropdown-link {
  display: flex;
  align-items: center;
  gap: 4px;
  padding: 8px 12px;
  color: var(--color-main-text);
  text-decoration: none;
  font-weight: 500;
  white-space: nowrap;
  border-radius: var(--border-radius);
  transition: background-color 0.1s ease;
}

.dropdown-link:hover {
  background-color: var(--color-background-hover);
}

.dropdown-link .chevron-icon {
  transition: transform 0.2s ease;
}

.dropdown-item:hover .chevron-icon {
  transform: rotate(180deg);
}

/* Dropdown menu */
.dropdown-menu {
  position: absolute;
  top: 100%;
  left: 0;
  min-width: 220px;
  margin-top: 4px;
  background: var(--color-main-background);
  border: 1px solid var(--color-border);
  border-radius: var(--border-radius-large);
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
  z-index: 1000;
  padding: 8px 0;
}

.dropdown-menu-item {
  position: relative;
}

.dropdown-menu-link {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 8px;
  padding: 10px 16px;
  color: var(--color-main-text);
  text-decoration: none;
  font-size: 14px;
  transition: background-color 0.1s ease;
}

.dropdown-menu-link:hover {
  background-color: var(--color-background-hover);
}

.chevron-right-icon {
  color: var(--color-text-maxcontrast);
}

/* Level 3 submenu */
.dropdown-submenu {
  position: absolute;
  top: 0;
  left: 100%;
  min-width: 220px;
  margin-left: 4px;
  background: var(--color-main-background);
  border: 1px solid var(--color-border);
  border-radius: var(--border-radius-large);
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
  z-index: 1001;
  padding: 8px 0;
}

.dropdown-submenu-link {
  display: block;
  padding: 10px 16px;
  color: var(--color-main-text);
  text-decoration: none;
  font-size: 14px;
  transition: background-color 0.1s ease;
}

.dropdown-submenu-link:hover {
  background-color: var(--color-background-hover);
}

/* Mega Menu Navigation */
.navigation-megamenu {
  display: flex;
  align-items: center;
  gap: 0;
  flex: 1;
  position: relative;
}

.megamenu-item {
  position: relative;
}

.megamenu-link {
  display: flex;
  align-items: center;
  gap: 4px;
  padding: 8px 16px;
  color: var(--color-main-text);
  text-decoration: none;
  font-weight: 500;
  white-space: nowrap;
  border-radius: var(--border-radius);
  transition: background-color 0.1s ease;
}

.megamenu-link:hover {
  background-color: var(--color-background-hover);
}

.chevron-icon {
  transition: transform 0.2s ease;
}

.megamenu-item:hover .chevron-icon {
  transform: rotate(180deg);
}

.megamenu-panel {
  position: absolute;
  top: 100%;
  left: 0;
  min-width: 600px;
  max-width: 800px;
  margin-top: 4px;
  background: var(--color-main-background);
  border: 1px solid var(--color-border);
  border-radius: var(--border-radius-large);
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
  z-index: 1000;
  padding: 16px;
}

.megamenu-content {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 24px;
}

.megamenu-column {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.megamenu-column-title {
  font-weight: 600;
  font-size: 14px;
  color: var(--color-main-text);
  text-decoration: none;
  padding: 4px 0;
  border-bottom: 2px solid var(--color-primary);
  margin-bottom: 8px;
  transition: color 0.1s ease;
}

.megamenu-column-title:hover {
  color: var(--color-primary);
}

.megamenu-list {
  list-style: none;
  padding: 0;
  margin: 0;
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.megamenu-list-item {
  display: block;
  padding: 6px 8px;
  color: var(--color-text-maxcontrast);
  text-decoration: none;
  border-radius: var(--border-radius);
  font-size: 13px;
  transition: all 0.1s ease;
}

.megamenu-list-item:hover {
  background-color: var(--color-background-hover);
  color: var(--color-main-text);
}
</style>
