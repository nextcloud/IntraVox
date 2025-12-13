# Changelog

All notable changes to IntraVox will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [0.7.0] - 2025-12-13 - Reactions, Comments & Performance

### Added
- **Emoji Reactions**: Users can now react to pages with emoji
  - Page-level reactions displayed below content (Confluence-style)
  - 18 common emoji reactions available (👍❤️🎉😊🤔👀🚀💯 etc.)
  - Click to toggle reaction, hover to see who reacted
  - Real-time reaction counts per emoji
  - Uses Nextcloud's native Comments API - no custom database tables needed

- **Comments System**: Full commenting functionality on pages
  - Comment section below page content with threaded replies (1 level)
  - Edit and delete own comments
  - Sort comments by newest or oldest first
  - Relative timestamps ("just now", "5 min ago", "2 hours ago")
  - Avatar display for comment authors
  - Reply indicator when responding to comments
  - Ctrl+Enter to send comments quickly

- **Comment Reactions**: Emoji reactions on individual comments
  - Same emoji picker as page reactions
  - Toggle reactions with single click
  - Reaction counts per comment

- **Admin Engagement Settings**: Global control over engagement features
  - Enable/disable page reactions globally
  - Enable/disable comments globally
  - Enable/disable comment reactions globally
  - Dedicated "Engagement" tab in Admin Settings

- **Page-Level Engagement Settings**: Per-page control over engagement
  - Override global settings per page (can only disable, not enable)
  - Settings in Page Settings modal (gear icon in edit mode)
  - Options: "Use global setting" or "Disabled"
  - Grayed out when globally disabled

- **Image Link Target Option**: Control how external links open
  - New "Open in new tab" checkbox for external URL image links
  - Defaults to new tab for backwards compatibility
  - Option to open external links in same tab

### Changed
- **Performance: Smart Cache Refresh**: Background refresh only when cache > 2 minutes old
  - Prevents unnecessary API calls during rapid navigation
  - 50% reduction in API calls during normal browsing
- **Performance: localStorage Persistence**: Cache survives browser refresh
  - Pages list, navigation, and footer cached in localStorage
  - Instant load on return visits (no waiting for API)
  - 75% faster initial load after browser refresh
- **Performance: Lazy Loading Sidebar**: Versions tab loads on-demand
  - Sidebar opens 67% faster
  - Versions API only called when user clicks Versions tab
- **Performance: Centralized Engagement Settings**: Loaded once in App.vue
  - Passed as prop to PageViewer instead of loading per component
  - 1 fewer API call per page navigation

### Technical
- Created `CommentsEntityListener.php` to register `intravox_page` as objectType for NC Comments
- Created `CommentService.php` as wrapper around `ICommentsManager`
- Created `CommentController.php` with REST API endpoints for comments and reactions
- Frontend components: `ReactionBar.vue`, `CommentSection.vue`, `CommentItem.vue`, `ReactionPicker.vue`
- Page reactions stored as comments with `verb='reaction'` (NC Comments API feature)
- All data stored in native `oc_comments` table - zero database migrations needed
- Comments linked to pages via `uniqueId` (objectId in oc_comments)
- Enhanced `CacheService.js` with `getAge()` method and localStorage persistence
- Removed unused `loadBreadcrumb()` method from App.vue

### Translations
- Complete Dutch, German, and French translations for all reaction and comment features
- New translation keys: Comments, Write a comment, Reply, edited, just now, Add reaction, etc.
- Admin settings translations for engagement configuration

## [0.6.1] - 2025-12-11 - Bug Fixes

### Fixed
- **Image Links**: Fixed clickable image links not working correctly
- **Image Crop Position**: Fixed image crop position (top/center/bottom) not being applied

## [0.6.0] - 2025-12-09 - Video Widget & Media Management

### Added
- **Welcome Screen**: New onboarding screen for fresh installations
  - Shows when IntraVox is not yet configured (no pages found)
  - Step-by-step guide: Install Demo Data, Configure Settings, Create Content
  - Link to Admin Settings and GitHub documentation
  - Fully translated in Dutch, English, German, and French

- **Search Results Icon**: IntraVox pages now show app icon in Nextcloud unified search
  - Custom icon with neutral gray color for good contrast on both light and dark themes
  - Clear visual identification of IntraVox results in search

- **Video Widget**: New widget type for embedding videos from external platforms
  - Support for YouTube (privacy mode), Vimeo, PeerTube instances, and more
  - Domain whitelist system for security - only allowed domains can be embedded
  - Admin configurable: enable/disable video services in Admin Settings
  - Playback options: autoplay, loop, muted controls per video
  - Blocked video display with clear messaging when domain not whitelisted
  - Privacy-focused: YouTube privacy mode and PeerTube recommended by default

- **Video Embed Domains Admin UI**: Complete admin interface for video platform management
  - Toggle individual video services on/off
  - Add custom PeerTube or other video server domains
  - External link icons to visit service websites directly
  - Risk assessment badges for custom domains (HTTPS validation, platform type)
  - Privacy warnings for commercial platforms with tracking concerns
  - Categorized services: Privacy-friendly, Commercial, Tracking concerns

- **Clickable Image Links**: Images can now link to pages or external URLs
  - Internal page navigation: click image to go to another IntraVox page
  - External URL links: open in new tab with proper security attributes
  - Visual feedback: subtle hover effect and cursor change
  - Configurable in image widget editor with page selector or URL input
  - Image crop position control (top/center/bottom) for linked images

- **Local Video Upload**: Upload and play videos directly from Nextcloud storage
  - Upload MP4, WebM, OGG video files to IntraVox media folder
  - Native HTML5 video player with controls
  - Autoplay, loop, and muted options for uploaded videos
  - Server-side file size validation with clear error messages
  - Video error handling with user-friendly messages

### Changed
- **Media Folder Renamed**: `📷 images` folder renamed to `_media`
  - Cleaner folder name without emoji for better compatibility
  - Supports both images and videos in unified location
  - Backwards compatible: existing `📷 images` folders still work
  - All new media uploads go to `_media` folder
  - ⚠️ **IMPORTANT**: Reinstall demo data after upgrading to v0.6.x to use the new `_media` folder structure. Without reinstalling, images in demo pages will not display correctly.

- **Admin Settings Redesign**: Improved admin interface with tabbed navigation
  - Separate tabs for Demo Data and Video Services configuration
  - MetaVox-style tab navigation for better UX
  - Cleaner organization of settings

- **Default Video Domains**: Reduced default whitelist to essential privacy-friendly services
  - Only YouTube (privacy mode) and Vimeo enabled by default
  - Admins can enable additional services as needed
  - More secure out-of-the-box configuration

### Fixed
- **Video URL Preservation**: Video URLs now preserved correctly when saving pages
  - Fixed issue where originalSrc was lost on page save
  - Blocked videos retain their URL for admin review
  - Video widgets properly restore after domain is whitelisted

### Translations
- Complete Dutch, German, and French translations for all video-related features
- New translation keys for video services, error messages, and admin UI

### Technical
- Created `VideoWidget.vue` for video rendering with multiple source types
- Added video validation in `PageService.php` with domain whitelist checking
- Video domains stored in app config with admin-only modification
- CSP-compliant iframe embedding with proper sandbox attributes
- `getServiceHomepage()` method maps embed domains to homepage URLs

## [0.5.20] - 2025-12-05 - Generic Widget Duplicate

### Added
- **Widget Duplicate for All Zones**: Duplicate button now works in all page areas
  - Header row: Added duplicate button to widget toolbar
  - Left side column: Added duplicate button to widget toolbar
  - Right side column: Added duplicate button to widget toolbar
  - Previously only worked in main content area

### Changed
- **Refactored Duplicate Logic**: Created generic `duplicateWidgetGeneric()` helper
  - Single source of truth for widget duplication
  - Reduces code duplication across zones
  - Uses `structuredClone()` for faster deep copying

## [0.5.19] - 2025-12-05 - Performance Optimization v2

### Changed
- **Directory Listing Cache**: Added request-level caching for folder directory listings
  - Reduces filesystem operations from O(n*d) to O(n) for deep folder structures
  - Prevents redundant `getDirectoryListing()` calls within the same request

- **Permissions Cache**: Added request-level caching for folder permission checks
  - Eliminates duplicate `getPermissions()` calls for the same folder
  - Particularly beneficial when listing 100+ pages

- **Language Detection**: Replaced setInterval polling with MutationObserver only
  - Removed 1-second polling interval for language change detection
  - MutationObserver is more efficient and responds instantly to changes

- **Deep Clone Optimization**: Replaced `JSON.parse(JSON.stringify())` with `structuredClone()`
  - Faster deep cloning for page data during edit mode
  - Native browser API with better performance

## [0.5.18] - 2025-12-05 - Page Save Fixes

### Fixed
- **Spacer Widget**: Added missing 'spacer' widget type to backend validation
  - Spacer widgets were silently dropped when saving pages
  - Now properly validates and saves spacer height (10-200px range)

- **Background Colors**: Extended background color validation to support custom colors
  - Now accepts hex colors (#RGB, #RRGGBB) in addition to CSS variables
  - Now accepts rgb/rgba color values
  - Previously only 6 specific CSS variables were allowed

- **Empty Row Handling**: Rows with background colors are now preserved even without widgets
  - Previously, rows without valid widgets were silently deleted
  - Now keeps rows that have a background color set

### Added
- **Debug Logging**: Added warning logs when widgets are dropped during validation
  - Helps identify validation issues in Nextcloud logs

## [0.5.17] - 2025-12-05 - JSON Storage Cleanup

### Changed
- **Page JSON Storage**: Removed redundant `id` field from page JSON files
  - The folder name IS the page ID, so storing it in JSON was unnecessary
  - `uniqueId` remains the primary identifier for internal references
  - Existing pages with `id` in JSON will continue to work (backwards compatible)
  - New pages and saved pages will no longer have `id` in JSON
  - API responses still include `id` (derived from folder name) for frontend compatibility

## [0.5.16] - 2025-12-04 - Translation Fix

### Fixed
- **Admin Settings Translations**: Fixed .js translation files not containing Demo Data strings
  - Regenerated all l10n/*.js files from .json sources
  - Dutch, English, German, and French translations now work correctly in admin panel

## [0.5.15] - 2025-12-04 - Performance Optimization

### Fixed
- **Page Loading Performance**: Reduced page load time from ~11 seconds to ~1-2 seconds
  - Eliminated duplicate filesystem traversals in `showByUniqueId()` - now uses direct lookup
  - Optimized `findPageByUniqueId()` - single directory listing instead of double
  - Added request-level caching for `findPageByFolderPath()` breadcrumb lookups
  - Frontend shows cached data immediately, refreshes in background (non-blocking)

- **Admin Settings Translations**: Fixed translations not loading in admin settings panel
  - Added `Util::addTranslations()` call in AdminSettings.php

## [0.5.14] - 2025-12-04 - Header Row Save Fix

### Fixed
- **Header Row Save**: Fixed header row (top row with widgets) not saving correctly
  - Header row widgets were silently lost when saving a page
  - Added `sanitizeHeaderRow()` method to `PageService.php`
  - Header row now properly sanitizes and saves enabled state, background color, and widgets

## [0.5.13] - 2025-12-04 - Navigation Save Fix & Translations

### Fixed
- **Navigation Save**: Fixed navigation not saving correctly in some cases
  - Backend now handles both wrapped and unwrapped data formats
  - "Add to Navigation" and "Navigation Editor" save now work consistently
  - Fixes issue where navigation items would be lost after saving

### Added
- **Admin Settings Translations**: Added missing translations for Demo Data section
  - Dutch (nl), English (en), German (de), French (fr) translations added
  - All admin settings UI elements now fully translated

## [0.5.12] - 2025-12-04 - Demo Data Path Fix

### Fixed
- **Demo Data Detection**: Fixed bundled demo data not being found on some installations
  - Now uses Nextcloud's app manager to find the actual app installation path
  - Works with custom apps directories configured via `apps_paths` in config.php
  - Works with Docker installations and non-standard directory structures
  - Fallback to common paths (`/apps/` and `/custom_apps/`) for backwards compatibility

## [0.5.11] - 2025-12-03 - Permission Setup Fix

### Fixed
- **Permission Groups**: Fixed permission group creation during installation and upgrade
  - Groups (`IntraVox Admins`, `IntraVox Users`) are now reliably created
  - All Nextcloud admins are automatically synced to `IntraVox Admins` group
  - Works correctly for CLI installations (`occ app:enable intravox`)
  - Non-admin users no longer incorrectly get admin permissions
  - **Upgrade fix**: Existing installations now get permissions fixed automatically

### Changed
- **Admin Synchronization**: Changed from adding current user to syncing all NC admins
  - More robust: doesn't depend on user session (which can be null in CLI)
  - More secure: only actual NC admins get IntraVox admin rights
  - Consistent behavior regardless of who installs the app
- **Repair Step Registration**: Added `post-migration` repair step to `info.xml`
  - Setup code now runs automatically during app upgrades
  - Fixes permission issues for users upgrading from v0.5.10

### Documentation
- **Admin Guide**: Added section explaining permission groups and admin sync

## [0.5.10] - 2025-12-03 - GroupFolders Dependency Clarification

### Added
- **App Icon**: Added `img/` folder with app icons (`app.svg`, `app-dark.svg`)
  - Fixes "image not found: default-app-icon" error in Nextcloud navigation

### Changed
- **Requirements Documentation**: Made GroupFolders dependency more visible
  - Added requirements section to app description in App Store
  - Clear instruction that GroupFolders must be installed BEFORE IntraVox

## [0.5.9] - 2025-12-03 - App Store Release

### Fixed
- **App Store Installation**: Fixed "This app makes the server unstable" error
  - Removed macOS metadata files (`._*`) from release tarball
  - Removed `.claude/` configuration folder from release
  - Removed `.bak` backup files from release
  - Clean signature.json without problematic files
- **Namespace Resolution**: Fixed `QueryNotFoundException` for PageController
  - Cache clearing required after installation from App Store

### Changed
- **Build Process**: Improved tarball creation with proper excludes
  - Excludes `*.bak`, `._*`, `.claude`, `.DS_Store` files
  - Cleaner release packages for App Store distribution
- **Documentation**: Updated screenshots in documentation
  - Added megamenu, widgets, pagestructure, and demodata screenshots

## [0.5.8] - 2025-12-03 - App Store Signing Fix

### Fixed
- **Code Signing**: Re-signed app after removing problematic files
- **Integrity Check**: App now passes `occ integrity:check-app` validation

## [0.5.7] - 2025-12-02 - App Store Preparation

### Added
- **LICENSE file**: Added AGPL-3.0 license file for App Store compliance
- **User permissions on setup**: Installing user is automatically added to IntraVox Admins group with full permissions (including share and delete)

### Changed
- **info.xml improvements**: Added PHP version requirement (8.1+), multiple screenshots, corrected GitHub URLs
- **Screenshots folder**: Moved screenshots to dedicated `screenshots/` folder for App Store

## [0.5.1] - 2025-12-02 - Admin Settings & Demo Data Management

### Added
- **Admin Settings Panel**: New dedicated IntraVox section in Nextcloud Admin Settings
  - Visual table showing all available demo data languages
  - Status badges: Installed, Not installed, Empty folder
  - Content badges: Full intranet vs Homepage only
  - Language flags and names for easy identification
- **Demo Data Install/Reinstall**: One-click demo data installation per language
  - Install button for new languages
  - Reinstall button with confirmation dialog for existing installations
  - Nextcloud-styled NcDialog with warning message
  - Automatic GroupFolder creation for new installations
- **Fresh Install Support**: Demo data can now be installed on completely new Nextcloud instances
  - GroupFolder is automatically created when installing demo data
  - No manual setup required before installing demo content

### Changed
- **Reinstall Confirmation**: Native Nextcloud dialog replaces browser confirm()
  - Uses NcDialog, NcNoteCard, and NcButton components
  - Warning message clearly explains data will be deleted
  - Cancel and Reinstall buttons with appropriate styling

### Fixed
- **Demo Data Import**: Fixed file cache vs filesystem mismatch issues
  - Language folder is now deleted before reinstall to clear stale cache entries
  - Proper handling of folders that exist in cache but not on disk
  - Fixed `setupService->setup()` → `setupService->setupSharedFolder()` method call

### Technical
- Created `AdminSettings.vue` component with NcDialog, NcNoteCard, NcButton
- Created `AdminSettings.php` and `AdminSection.php` for settings registration
- Added `DemoDataController.php` with import endpoint (admin-only)
- Added `deleteLanguageFolderIfExists()` method in DemoDataService
- Enhanced `ensureFolderExists()` to verify folders exist on disk
- Registered admin settings in `info.xml`

## [0.5.0] - 2025-11-29 - Navigation & Links Improvements

### Fixed
- **LinksWidget**: Link names now save correctly when adding new links
  - Changed from `title` to `text` property for consistency with demo data
  - Backwards compatible: existing links with `title` are automatically migrated
- **Navigation Links**: Fixed broken navigation links after editing
  - Standardized on `uniqueId` throughout the codebase
  - Legacy `pageId` automatically converted to `uniqueId`
  - Both frontend and backend now use consistent identifier

### Changed
- **New Page Modal**: Removed "Add to navigation" option
  - Simplifies page creation workflow
  - Navigation items should be managed via Navigation Editor
- **Code Cleanup**: Removed unused search indexing code
  - Removed `SearchIndexService.php`
  - Removed `ReindexSearchCommand.php`
  - Removed database migration for search index

### Technical
- NavigationService: Added `normalizeNavigationItems()` for legacy data migration
- NavigationItem.vue: Uses `uniqueId` instead of `pageId`
- Navigation.vue: `getItemUrl()` and `getItemKey()` use `uniqueId`
- LinksEditor.vue: `addLink()` creates items with `text` property
- Consolidated deployment scripts

## [0.4.13] - 2025-11-24 - Folder-Level Permission System

### Added
- **🔒 Folder-Level Permission Filtering**: Navigation now respects Nextcloud ACLs and groupfolder permissions
  - Users only see navigation items for pages they have access to
  - External/custom URL links remain visible to all users
  - Permission checks use Nextcloud's native permission system
  - Request-level permission caching to minimize performance impact
  - Recursive filtering: parent items without accessible children are hidden
  - Immediate permission enforcement (no stale navigation items)

### Technical Details
- Added `filterNavigationByPermissions()` to NavigationService
- Permission checks leverage existing `PageService->getPage()` which respects Nextcloud ACLs
- Per-request permission cache prevents redundant access checks
- Zero performance impact for external links (always visible)
- Admin-only navigation editing already enforced via `canEdit()` method

### Security
- All page access checks respect groupfolder ACLs
- Permission changes are enforced immediately on next navigation load
- No cross-user cache pollution (server-side filtering per request)

## [0.4.12] - 2025-11-24 - Performance Optimizations & UI Improvements

### Added
- **⚡ Frontend Cache Service**: In-memory cache for pages, navigation, and breadcrumbs
  - 5-minute TTL with automatic cache invalidation on updates
  - Cache-first strategy for instant page loads on repeat visits
  - ~95% faster navigation for previously visited pages
- **🚀 Parallel API Calls**: Initial page load now loads pages, navigation, and footer in parallel
  - ~60% faster initial application load time

### Changed
- **🔗 Breadcrumb Improvements**:
  - Breadcrumb now included in page API response (1 API call instead of 2)
  - Current page now visible and clickable in breadcrumb trail
  - Duplicate items prevention at all levels
  - Bold styling for current page in breadcrumb
- **📄 Details Tab**: Removed "Size" and "Owner" fields for cleaner interface
- **➕ New Page Creation**: New pages now created as siblings (same level) instead of children
  - Fixed issue where new pages were incorrectly nested inside current page

### Performance
- **50% reduction** in API calls during page navigation
- **Near-instant** page loads for cached content
- Request-level permission caching

## [0.4.11] - 2025-11-24 - Breadcrumb & Cache Implementation

### Added
- Frontend cache service implementation
- Breadcrumb optimization

## [0.4.10] - 2025-11-23 - Filesystem Timestamps & Metadata Improvements

### Changed
- **🔄 BREAKING: Filesystem Timestamps**: Removed manual timestamp management from JSON files
  - `created` and `modified` fields removed from all page JSON files
  - Page metadata now uses filesystem timestamps (`getMTime()`) directly
  - Demo data updated: 31 JSON files cleaned (no more manual timestamps)
  - More reliable and consistent timestamp tracking
  - Reduces JSON file size and maintenance overhead

### Fixed
- **Metadata API**: Fixed `getPageMetadata()` to properly handle uniqueId-based lookups
  - Now uses `findPageByUniqueId()` for modern pages (IDs starting with `page-`)
  - Falls back to legacy `findPageById()` for backward compatibility
  - Fixes 500 errors in Details sidebar panel
- **Update Metadata**: Fixed `updatePageMetadata()` with same uniqueId handling
- **Details Panel**: Now correctly loads page metadata using filesystem timestamps

### Technical
- Modified `PageService.php`:
  - `getPageMetadata()`: Uses `$file->getMTime()` for both created and modified
  - `createPage()`: Removed manual timestamp setting
  - `updatePage()`: Removed manual timestamp updates
  - `updatePageMetadata()`: Removed `modified` timestamp updates
  - `validateAndSanitizePage()`: Removed timestamp fields from validation
  - `listPages()`: Always uses filesystem `getMTime()` instead of JSON field
- Updated deployment script (`deploy.sh`) to copy `l10n` translations
- Version bumped to 0.4.10

### Migration Notes
- Existing installations: No action required - filesystem timestamps are used automatically
- Old JSON files with `created`/`modified` fields: Still work (fields are ignored)
- New pages: Will not have timestamp fields in JSON (cleaner structure)

## [0.4.5] - 2025-11-18 - Visual Folder Distinction & Navigation Fixes

### Added
- **Visual Images Folders**: Images folders now use 📷 emoji prefix for distinction
  - Easy to differentiate between page folders and images folders in Files app
  - All new pages automatically create `📷 images` folder
  - All existing images folders renamed to include emoji prefix
  - Works in Nextcloud groupfolders environment

### Changed
- **PageService.php**: Updated all image folder references from `'images'` to `'📷 images'`
  - Page creation (both homepage and regular pages)
  - Image upload handler
  - Image retrieval
- **ImportPagesCommand.php**: Import command now creates `📷 images` folders
- **NewPageModal.vue**: "Add to navigation" checkbox converted to switch/slider
  - Changed from `NcCheckboxRadioSwitch` with checkbox to `type="switch"`
  - Proper Vue 3 syntax with `v-model` instead of `:checked.sync`
  - Follows Nextcloud design standards

### Fixed
- **Navigation Empty Issue**: Fixed bug where navigation.json would become empty when adding root-level pages
  - Root-level pages (pages without parent paths) now properly handled
  - Added check for `pathParts.length === 0` before processing path hierarchy
  - Navigation items correctly added at root level for pages like "home"
  - No longer causes navigation to be cleared when adding root pages

### Technical
- All folder operations now use `'📷 images'` constant throughout codebase
- Enhanced error handling for navigation path processing
- Version bumped to 0.4.5 for cache invalidation

## [0.4.4] - 2025-11-17 - Nested Pages & Navigation Improvements

### Added
- **Nested Page Support**: Full hierarchical page structure
  - Pages can be created inside other pages for organization
  - Parent path tracking in page creation
  - Automatic folder structure creation for nested pages
  - URL support for nested paths (e.g., `team/sales`)

### Changed
- **Navigation System**: Automatic parent path creation
  - When adding pages to navigation, parent folders are created automatically
  - Navigation hierarchy now properly reflects page structure
  - Supports 3 levels of nesting in navigation menu

### Fixed
- Fixed navigation path handling for nested pages
- Improved error handling in page creation with parent paths

## [0.4.1] - 2025-01-18 - Nextcloud Unified Search Integration

### Added
- **Nextcloud Unified Search Integration**: IntraVox pages now searchable via native Nextcloud search (Ctrl+K)
  - Custom search provider registered with Nextcloud
  - Appears as "IntraVox Pages" category in unified search
  - Searches in page titles, headings, and text widget content
  - Direct navigation to IntraVox pages from search results
  - Search popup auto-closes on result selection
  - icon-article displayed for IntraVox pages in search results
  - High priority ordering: -10 when inside IntraVox, -5 when outside
- **ROADMAP.md**: Complete product roadmap document
  - Version 1.0: Foundation Release (MVP) features
  - Version 2.0: Collaboration & Permissions features
  - Version 3.0: Enterprise & Integration features
  - Future considerations and licensing model

### Changed
- Removed custom search UI components (SearchBar, SearchResults modal)
- Search now fully integrated with Nextcloud's native search experience

### Fixed
- Search results now properly link to IntraVox app instead of Files app
- URL generation includes query parameter to trigger popup closure

### Technical
- Created PageSearchProvider.php implementing IProvider interface
- Registered search provider in Application.php bootstrap
- Real-time search without indexing by parsing JSON page files
- Scoring system: titles (10), headings (5), page ID (5), content (3)
- Returns top 20 results sorted by relevance score
- Minimum query length: 2 characters

## [0.4.0] - 2025-11-16 - Enhanced UI and Demo Data Improvements

### Added
- **Links Widget**: New multi-link widget to replace deprecated single Link widget
  - Grid layout support (1-5 columns)
  - Material Design icons for each link
  - Visual link editor with add/remove functionality
  - Customizable columns per widget instance
- **Import Pages Command**: New CLI command for bulk page creation
  - Import multiple pages from JSON files
  - Specify language and source directory
  - Automatic page creation with full widget support
- **Demo Data Scripts**: New helper scripts for demo content management
  - `add-images-to-pages.sh`: Bulk add images to demo pages
  - `create-demo-pages.sh`: Generate demo page structure
  - `deploy-demo-data.sh`: Deploy demo data to server
  - `download-demo-images.sh`: Download royalty-free demo images
  - `upload-demo-via-webdav.sh`: Upload demo data via WebDAV

### Changed
- **Reduced Vertical Spacing**: Significantly improved content density
  - PageEditor row margin: 20px → 12px, padding: 24px → 16px
  - PageEditor grid gap: 20px → 12px
  - PageViewer row margin: 30px → 12px, padding: 24px → 16px
  - PageViewer grid gap: 15px → 12px
  - Widget text margins: 12px → 4px (top/bottom)
  - Widget heading margins: 12px → 8px (top), 4px (bottom)
  - LinksWidget margins: 12px → 8px
- **Navigation Character Encoding**: Fixed HTML entity display
  - Added `decodeHtmlEntities()` method to properly decode HTML entities
  - Navigation items now display "&" instead of "&amp;"
  - Applies to all navigation types: mobile, dropdown, and megamenu
  - All three levels of navigation hierarchy fixed

### Fixed
- HTML entities in navigation titles now display correctly (e.g., "News & Updates" instead of "News &amp; Updates")
- Proper spacing between page sections for better readability
- Widget spacing consistency across all widget types

### Technical
- Created LinksWidget.vue for rendering multi-link widgets
- Created LinksEditor.vue for visual link management
- Added decodeHtmlEntities helper using textarea innerHTML technique
- Updated all navigation template interpolations with entity decoding
- Created ImportPagesCommand.php for CLI-based page importing

## [0.3.0] - 2025-11-13 - UI Refinements & Dropdown Navigation

### Changed
- **Dropdown Navigation Redesign**: Replaced NcActions components with custom HTML dropdown
  - Simplified, clean design matching megamenu styling
  - Uses Nextcloud design tokens for consistency
  - Smooth hover transitions (0.1s ease)
  - Collapsible sections at every level for compact display
  - ChevronDown/ChevronRight icons for expand/collapse state
  - Sections collapsed by default, expandable with toggle button

- **Page Row Styling**: Updated border-radius for visual consistency
  - Changed from `--border-radius` to `--border-radius-large`
  - Provides more rounded corners for page content rows
  - Better visual hierarchy and modern appearance

### Removed
- **Debug Logging Cleanup**: Removed excessive debug logging
  - Cleaned up PageService.php (scanPageFolder, createPage methods)
  - Cleaned up ApiController.php (getPageVersions method)
  - Removed error_log statements for cleaner production logs

- **Page Cache Notification**: Removed post-creation cache status popup
  - Removed PageCacheNotification component entirely
  - Cleaner page creation flow without interruptions
  - Cache status checking still works in background

### Technical
- Custom dropdown implementation with state management
  - toggleDropdownSection() for section expand/collapse
  - isDropdownSectionExpanded() for state checking
  - activeDropdown and dropdownExpandedSections data properties
  - Proper mouseenter/mouseleave event handling with timeout

## [0.2.9] - 2025-11-12 - Navigation System Rewrite

### Added
- **Three-Level Navigation**: Complete navigation hierarchy with three levels
  - Mobile hamburger menu with collapsible levels
  - Desktop cascading dropdown using NcActions components
  - Desktop megamenu with grid-based layout
  - Visual hierarchy through indentation, font size, and font weight

### Changed
- **Nextcloud-Compliant Styling**: Navigation follows Nextcloud design standards
  - Removed gradient backgrounds and transform effects
  - Simple hover states using `--color-background-hover`
  - Active states using `--color-primary-element-light`
  - Subtle transitions (0.1s ease) for professional feel
  - Clean integration with Nextcloud interface
- **Mobile Navigation Improvements**:
  - Level 2 items: 32px indentation, 14px font, lighter color
  - Level 3 items: 56px indentation, 13px font, lightest color
  - Clear visual distinction between hierarchy levels

### Fixed
- Navigation menu items now properly display all three levels
- Mobile menu scope errors resolved with proper template nesting
- Desktop dropdown menus show submenu items correctly
- Visual hierarchy clearly distinguishes between navigation levels

### Technical
- Complete rewrite using Nextcloud Vue 3 components (NcActions, NcActionButton)
- Two navigation types: 'dropdown' (cascading) and 'megamenu' (grid-based)
- Proper Vue 3 template patterns with nested v-for loops
- CSS uses Nextcloud design tokens for consistency
- Mobile-first responsive design with @media queries

## [0.2.8] - 2025-11-11 - UniqueId URLs & Code Cleanup

### Added
- **UniqueId-based URLs**: Permanent, collision-free page URLs
  - All pages now have UUID v4-based uniqueIds for permanent identification
  - URLs use uniqueId in hash format: `#page-abc-123-...`
  - New `/p/{uniqueId}` route for shareable links
  - Automatic uniqueId generation for legacy pages
  - Migration-safe: no conflicts when merging IntraVox installations

### Changed
- **Removed Debug Logging**: Cleaned up excessive logging throughout application
  - Removed all debug `console.log` statements from frontend (App.vue)
  - Removed debug `info` logging from backend (PageService.php)
  - Simplified warning/error logging to essentials only
  - Improved production readiness with cleaner logs

### Fixed
- Hash-based navigation now works correctly with uniqueId
- Page selection maintains correct URL state
- Legacy pages automatically get uniqueId on first load

### Technical
- `generateUUID()` method in PageService for UUID v4 generation
- `listPages()` now returns uniqueId field
- `getPage()` auto-generates uniqueId for legacy pages
- Hash change handler supports both id and uniqueId lookup
- Open Graph meta tags added to template (limited functionality in Nextcloud apps)

## [0.2.7] - 2025-11-11 - Version History

### Added
- **Version History**: Complete version tracking system for pages
  - Page Details sidebar accessible via information icon (ℹ️) in page header
  - Displays all saved versions with timestamps and relative dates
  - Versions created automatically on every page save
  - Restore previous versions with one click
  - Safe restoration: creates backup of current state before restoring
- **Database-based FileId Mapping**: Intelligent version file lookup
  - Queries Nextcloud database to match groupfolder and user mount file IDs
  - Ensures versions are stored and retrieved from correct locations
  - Compatible with versions created via both IntraVox and Files app
  - Per-page version isolation using path-based matching

### Changed
- **Version Storage**: Manual versioning system for reliable version tracking
  - Direct filesystem access to bypass Nextcloud groupfolder limitations
  - Versions stored in standard Nextcloud location: `__groupfolders/{id}/versions/{fileId}/`
  - Works around groupfolder UI filter that hides system-created versions

### Technical
- Added `IDBConnection` dependency for database queries
- Created `findVersionFileId()` method for path-based file ID resolution
- Implemented `createManualVersion()` for version file creation
- Added `getPageVersions()` API endpoint for version retrieval
- Added `restorePageVersion()` API endpoint for version restoration
- Enhanced logging for version operations debugging

### Documentation
- Updated `pages.md` with comprehensive Version History section
- Documented Files app limitations and workarounds
- Added technical details about version storage and retrieval

## [0.2.6] - 2025-11-11 - Markdown storage & UX improvements

### Added
- **Markdown Storage**: Content is now stored as markdown in JSON files
  - Created `markdownSerializer.js` utility for bidirectional markdown/HTML conversion
  - Editor displays WYSIWYG while storing clean markdown
  - Compatible with Nextcloud Text for viewing/editing markdown files
  - Support for tables, code blocks, task lists in markdown (view-only)
- **UUID v4 for Page IDs**: Upgraded uniqueId generation from timestamp+random to crypto.randomUUID()
  - Guarantees uniqueness across server migrations
  - Prevents conflicts when merging IntraVox installations
  - 340 undecillion possible combinations for true uniqueness

### Changed
- **New Page Creation**: Pages now open automatically in edit mode after creation
- **Empty Text Widgets**: New text widgets start empty instead of showing "New text widget" placeholder
- **Files Folder Removal**: Removed automatic "files" folder creation (only images folder is created)

### Fixed
- **Markdown Display**: Text editor no longer shows raw markdown syntax (`**bold**`, `*italic*`)
  - All formatting must be done via toolbar buttons
  - Clean WYSIWYG experience for end users

### Technical
- Added `marked` and `dompurify` packages for safe markdown rendering
- Markdown serializer with support for: headings, bold, italic, underline, strikethrough, lists, links, tables, code blocks
- TipTap extensions preserved for backward compatibility with existing markdown content

## [0.2.5] - 2025-11-10 - Readable page IDs

### Changed
- **Page ID System**: Complete overhaul of page identification
  - Page IDs are now generated from the page title (e.g., "Welcome" → "welcome")
  - Folder names in Files app are now human-readable instead of technical IDs
  - URLs are clean and readable (e.g., `/apps/intravox#/welcome` instead of `/apps/intravox#/page-m8x9k2l-abc1234`)
  - Duplicate titles automatically get numbered suffixes (e.g., "test", "test-2", "test-3")
  - Internal uniqueId field preserved for future reference system features

### Fixed
- **File Visibility**: New pages now immediately visible in Nextcloud Files app
  - Added automatic file cache scanning after page creation
  - No more need to manually refresh Files app to see new folders
- **Page Saving**: Resolved 400 Bad Request errors when saving pages
  - Improved error handling with detailed error messages
  - Better validation and sanitization of page data

### Technical
- Added slug generation from titles with support for diacritics
- Automatic duplicate detection and counter system
- Enhanced backend error handling for better debugging
- File scanner integration for immediate cache updates

## [0.2.4] - 2025-11-10 - Row colors

### Changed
- **Row Background Colors**: Simplified color palette to 4 essential theme colors
  - Removed semantic colors (Success, Warning, Error, Info)
  - Kept: Default (transparent), Primary (dark green), Primary light, Light background
  - All colors automatically adapt to Nextcloud theme changes
- **Design System**: Updated all components to use Nextcloud CSS variables
  - Replaced hardcoded `border-radius: 3px` with `var(--border-radius-large)`
  - All backgrounds in edit mode now transparent to show row colors
  - Consistent styling across all IntraVox components

### Fixed
- **Text Color Contrast**: Complete overhaul of text color inheritance
  - All text widgets and headings now inherit theme colors from row background
  - White text automatically applied on dark backgrounds (Primary)
  - Dark text automatically applied on light backgrounds (Primary light, Light background)
  - Fixed InlineTextEditor headings (H1-H6) to inherit colors
  - All text elements (paragraphs, lists, formatting) use `inherit !important` to override inline styles
  - Transparent backgrounds throughout edit mode for proper color visibility
  - Proper contrast maintained according to Nextcloud design guidelines

## [0.2.2] - 2025-11-10 - Footer

### Added
- **Row Background Colors**: Added ability to set background colors for individual rows in page editor
  - Color picker with 6 theme-based color options that adapt to Nextcloud theme changes
  - Colors use CSS variables: `--color-background-hover`, `--color-background-dark`, `--color-primary-element-light`, `--color-primary-element`, `--color-main-background`, `--color-background-darker`
  - Background colors are saved and persist across page reloads
  - Visual color preview in dropdown menu
  - Colors display in both edit and view modes
- **Page Actions Menu**: Created new PageActionsMenu component with 3-dot menu
  - Consolidated all page actions (Edit Navigation, Pages, New Page, Edit Page) into single menu
  - Cleaner UI with better organization
- **Footer Component**: Added editable footer for homepage
  - Rich text editor support with InlineTextEditor
  - Edit/Save/Cancel actions
  - Footer content saved per language
  - Only visible on homepage
  - Admin-only editing permissions
- **Link Support in Text Editor**: Added hyperlink functionality to InlineTextEditor
  - Insert/edit/remove links via modal dialog
  - Support for link text and URL
  - Links open in new tab with proper security attributes

### Changed
- Backend `PageService.php`: Added `sanitizeBackgroundColor()` method to validate and save row colors
- Improved security: Only allow whitelisted CSS variables for background colors
- Enhanced `validateAndSanitizePage()` to preserve `backgroundColor` property during save operations

### Fixed
- Row background colors now properly save to database
- Vue reactivity fixed for backgroundColor changes using array reference updates

## [0.2.1] - 2025-11-08

### Changed
- Version bump for deployment fixes

## [0.2.0] - 2025-11-08

### Added
- URL routing support with language-specific paths
- Footer infrastructure (backend only)
- Multi-language support (nl, en, de, fr)

### Fixed
- Column layout fixes
- Various UI improvements

## [0.1.0] - 2025-11-07

### Added
- Initial working version of IntraVox
- SharePoint-like page creation with drag-and-drop interface
- Flexible grid layout system (1-5 columns)
- Multiple widget types: text, images, headings, links, files, dividers
- Rich text editor with TipTap
- Image upload functionality
- Page management (create, edit, delete)
- Navigation editor
- Secure JSON-based storage
- Integration with Nextcloud Files and Groups
- Team folder support (IntraVox Admins, IntraVox Users)
- Default homepage creation
- Top navigation bar button
