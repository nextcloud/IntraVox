# Changelog

All notable changes to IntraVox will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

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
