# Changelog

All notable changes to IntraVox will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [0.9.4] - 2026-01-20 - Cache Bypass Release

### Fixed
- **App Store Cache**: New version to bypass cached signature data in Nextcloud App Store
  - No functional changes from 0.9.3

## [0.9.3] - 2026-01-20 - App Store Re-registration

### Fixed
- **App Store Certificate Sync**: Re-registered app with new certificate (serial 4824) to sync with App Store database
  - Previous releases were signed correctly but App Store database had outdated certificate reference
  - Resolves "Certificate 4822 has been revoked" error for all users
  - No functional changes from 0.9.2

### Documentation
- Added certificate verification section to RELEASE_CHECKLIST.md
- Added warnings about certificate management best practices

## [0.9.2] - 2026-01-19 - Certificate Update

### Fixed
- **App Store Certificate**: Updated to new code signing certificate after previous certificate was revoked
  - Resolves "Certificate has been revoked" error when installing from App Store
  - No functional changes from 0.9.1

## [0.9.1] - 2026-01-19 - Statistics & PHP 8.4 Fix

### Added
- **Statistics Tab**: New admin settings tab showing page counts per language
  - Overview of pages per language in your IntraVox installation
  - Visual progress indicators for each language
  - Total page count across all languages
- **Anonymous Telemetry**: Opt-in usage statistics to help improve IntraVox
  - Collects: page counts, user counts, Nextcloud/PHP version info
  - No personal data, usernames, or page content collected
  - Can be disabled in Admin Settings
  - Background job runs every 24 hours (with random jitter)
- **Translation Script**: New `npm run l10n` command to generate JavaScript translation files from JSON

### Fixed
- **PHP 8.4 Compatibility**: Fixed implicit nullable parameter deprecation warning ([#14](https://github.com/nextcloud/intravox/issues/14))
  - Changed `string $language = null` to `?string $language = null` in `PageService::buildPageTree()`
  - Resolves "Could not update app" error on PHP 8.4 systems

### Technical
- New `lib/Service/TelemetryService.php` for telemetry collection
- New `lib/Service/LicenseService.php` for usage statistics
- New `lib/BackgroundJob/TelemetryJob.php` (24h interval + random jitter)
- New `lib/BackgroundJob/LicenseUsageJob.php` (24h interval + random jitter)
- New `scripts/generate-l10n.js` for translation file generation
- Updated `AdminSettings.vue` with Statistics and Telemetry tabs

## [0.9.0] - 2026-01-17 - API Security & Error Handling

### Added
- **Analytics API**: New endpoints for page view tracking and statistics
  - `POST /api/analytics/track/{pageId}` - Track page views
  - `GET /api/analytics/page/{pageId}` - Get page statistics
  - `GET /api/analytics/top` - Get top viewed pages
  - `GET /api/analytics/dashboard` - Admin dashboard with aggregate stats
  - `GET/POST /api/analytics/settings` - Admin analytics configuration
- **Bulk Operations API**: New endpoints for batch page management (admin only)
  - `POST /api/bulk/validate` - Dry-run validation before execution
  - `POST /api/bulk/delete` - Bulk delete pages with optional child deletion
  - `POST /api/bulk/move` - Bulk move pages to new parent
  - `POST /api/bulk/update` - Bulk update page metadata
  - Maximum 100 pages per operation to prevent DoS
- **OCS Media Routes**: External API access for media uploads via Basic Auth
  - `POST /ocs/v2.php/apps/intravox/api/v1/pages/{pageId}/media` - Upload media
  - `GET /ocs/v2.php/apps/intravox/api/v1/pages/{pageId}/media` - List media
  - `GET /ocs/v2.php/apps/intravox/api/v1/pages/{pageId}/media/{filename}` - Get media file
- **API Error Handling**: Consistent error responses across all controllers
  - New `ApiErrorTrait` for standardized error handling
  - All responses include `success: true/false` field
  - Error responses include `errorId` for support correlation
  - Generic error messages prevent information disclosure
  - Full error details logged server-side for debugging
- **Versioning Documentation**: New section "What Triggers a New Version?" in VERSIONING.md
  - Clear table showing what actions create versions and what don't
  - Detailed explanation of MetaVox metadata and versioning relationship
  - Visual diagram showing separation between JSON content and MetaVox database

### Changed
- **API Response Format**: All API responses now consistently include `success` field
- **Admin-Only Endpoints**: Settings, import, and bulk operations require admin privileges
  - Removed `@NoAdminRequired` annotation from sensitive endpoints
  - Runtime admin checks as secondary protection layer

### Fixed
- **Sidebar Closes on Page Navigation**: The page details sidebar now automatically closes when navigating to a different page
  - Prevents stale data from previous page being displayed
  - Resolves issue where Versions tab showed "No versions available" after navigation
  - Resolves issue where MetaVox tab showed metadata from previous page
  - Consistent behavior: sidebar always shows fresh data when reopened
- **Simplified Sidebar State Management**: Cleaned up the `pageId` watcher in PageDetailsSidebar
  - Removed complex refresh logic that was causing race conditions
  - Sidebar now relies on clean open/close cycle for data loading

### Security
- **File Upload Security**: Enhanced validation for uploaded files
  - Extension whitelist for allowed file types (jpg, png, gif, webp, svg, mp4, webm, ogg, pdf)
  - MIME type validation with `getimagesize()` cross-check for images
  - SVG sanitization with dangerous element detection (iframe, embed, object, script)
- **Path Traversal Protection**: Hardened path validation
  - URL-encoded bypass prevention
  - Unicode normalization
  - Null byte detection
- **API Documentation**: Updated API_REFERENCE.md with security section
  - Admin-only endpoints table
  - File upload restrictions
  - Error response format

### Technical
- New `lib/Controller/ApiErrorTrait.php` for reusable error handling
- Updated BulkController, AnalyticsController, ApiController with trait
- Sidebar close logic added to `selectPage()` method in App.vue
- PageDetailsSidebar `pageId` watcher simplified to fallback-only reset
- Event handlers for page save (`handlePageSaved`) preserved for version refresh

## [0.8.9] - 2026-01-15 - Widget Color Consistency & Row Background Inheritance

### Added
- **Centralized Color Utilities**: New `colorUtils.js` module for consistent dark background detection
  - Single source of truth for dark background colors across all widgets
  - `isDarkBackground()` and `getEffectiveBackgroundColor()` helper functions
  - Easier to maintain and extend color handling

### Fixed
- **Links Widget Row Background**: Links widget now correctly inherits row background color
  - Added `rowBackgroundColor` prop to LinksWidget component
  - Links on dark row backgrounds (Primary, Error, Success) now show white text
  - Consistent behavior with News widget
- **Links Widget Primary Color**: Fixed individual link "Primary" background using wrong color
  - Changed from `--color-primary-element-light` to `--color-primary-element`
  - Links with Primary background now have correct dark blue color
- **News Widget Text Contrast**: Fixed black text on dark row backgrounds
  - News items without widget background now check row background for styling
  - Items on dark row backgrounds correctly show white text
  - Applies to List, Grid, and Carousel layouts
- **Backwards Compatibility**: Added `--color-primary-element-light` to dark color detection
  - Existing links with old color value still render correctly with white text

### Changed
- **Consistent Widget Pattern**: All widgets now use the same pattern for background handling
  - `effectiveBackgroundColor` computed property (widget bg takes precedence over row bg)
  - `isDarkBackground` check using centralized utility
  - Uniform prop passing from Widget.vue to child widgets

### Technical
- Refactored NewsLayoutList, NewsLayoutGrid, NewsLayoutCarousel to use colorUtils
- Added rowBackgroundColor prop passthrough in Widget.vue for LinksWidget

## [0.8.8] - 2026-01-05 - Version History UI, Widget Background Colors & Field-Type Filters

### Added
- **Files App Style Version History**: Complete redesign of the version history tab in the sidebar
  - Displays "Current version" with real file metadata (modified time, size, author)
  - Relative time formatting like Nextcloud Files app ("36 sec. ago", "2 min. ago", "3 hours ago")
  - Server-side time calculation to avoid timezone issues
  - Version list shows all available versions with author and relative time
  - Click any version to preview its content
  - Restore button to revert to a previous version
  - Custom labels can be added to mark important versions
- **Version History Auto-Refresh**: Version list automatically updates when page is saved
- **Edit Mode Version Focus**: When entering edit mode, version selection resets to "Current version"
  - Prevents confusion about which version is being edited
- **News Widget Background Color**: News widgets now support container background colors
  - Three options: None (transparent), Light (gray), Primary (dark blue)
  - Background color can be set independently from row background
  - Consistent with Links widget styling
- **Consistent Item Styling**: Both News and Links widgets now have consistent item background behavior
  - **None**: Transparent items that inherit parent/row background
  - **Light**: Gray container with white items for contrast
  - **Primary**: Dark blue container with semi-transparent white items and white text
- **Field-Type Specific Filter Operators**: MetaVox filters in the News widget now show operators appropriate for each field type
  - **Date fields**: equals, is before, is after, is not empty, is empty
  - **Number fields**: equals, greater than, less than, greater or equal, less or equal, is not empty, is empty
  - **Select fields**: equals, is one of (multiple selection), is not empty, is empty
  - **Multiselect fields**: contains, contains all, is not empty, is empty
  - **Checkbox fields**: is true, is false, is not empty
  - **Text/Textarea fields**: equals, contains, does not contain, is not empty, is empty
- **Dynamic Value Inputs**: Filter value inputs now adapt to the field type
  - Date fields show a date picker
  - Number fields show a number input
  - Select/Multiselect fields show a dropdown with the field's options from MetaVox
  - Checkbox fields require no value input (operator determines the filter)
- **Multi-Select Filter Values**: "Is one of" operator for select fields allows selecting multiple allowed values

### Fixed
- **Links Widget Individual Link Backgrounds**: Fixed contrast issues when individual links have a dark background color
  - Links with "Primary" background now correctly show white text and icons
  - Individual link background takes precedence over container background for styling
- **News Item Backgrounds**: Fixed inconsistent item backgrounds between News and Links widgets
  - News items now use the same background logic as Links items

### Changed
- **Sidebar State Preservation**: Sidebar tab focus is preserved during page refresh and version restore
  - Uses `v-show` instead of `v-if` to keep component mounted
  - No more unexpected tab switches when saving or restoring versions

### Translations
- Added translations for all new filter operators in all 4 languages (nl, en, de, fr)

## [0.8.7] - 2025-12-30 - Links Widget Page Selector & News Widget Fixes

### Added
- **Links Widget Page Selector**: Internal pages can now be selected from a page tree dropdown
  - New PageTreeSelect component integrated in Links Editor
  - Choose between internal page or external URL (mutually exclusive)
  - Auto-fills link text with page title when selecting a page
  - Backwards compatible with existing links
- **Links Widget Drag-and-Drop**: Links can now be reordered by dragging
  - Drag handle added to each link item in the editor
  - Uses same drag-and-drop pattern as row reordering
  - Smooth animation during drag operations

### Fixed
- **Row Drag-and-Drop**: Fixed issue where dragging a row into a column would cause the row to disappear
  - Added explicit drag group to prevent rows from being dropped into widget zones
  - Rows can now only be reordered within the rows container
- **News Widget Excerpts**: Markdown syntax is now stripped from excerpts
  - Excerpts no longer show raw markdown like `**bold**` or `[link](url)`
  - Supports stripping: bold, italic, strikethrough, links, images, code, headers, blockquotes, lists, horizontal rules
  - Results in clean, readable preview text
- **News Widget Shared Library Images**: Fixed images from Shared Library not displaying in news widget
  - Images added from Shared Library (`_resources` folder) now display correctly
  - `getPageFirstImage()` now returns both `src` and `mediaFolder` properties
  - Image path is correctly constructed based on media source (page media vs shared library)
- **Links Widget Data Persistence**: Fixed internal page links not being saved after page refresh
  - Added `uniqueId` property to link sanitization in backend
  - Legacy `#uniqueId` URLs are automatically converted to the new format

### Translations
- Added missing translations for Links Widget in all 4 languages (nl, en, de, fr)
- Added translations for widget descriptions, icon labels, and background options
- Added missing translations for other widgets (Background color, Video widget description)

## [0.8.6] - 2025-12-29 - Clean Start & Translation Fixes

### Added
- **Clean Start**: New option in Demo Data settings to start fresh with empty content
  - "Clean Start" button per language in Admin Settings → Demo Data
  - Deletes all pages, navigation, footer, comments, reactions, and media for selected language
  - Creates fresh homepage with newly generated unique ID (prevents conflicts)
  - Creates empty navigation with only Home link
  - Creates empty footer
  - Confirmation dialog with clear warning about permanent deletion
  - Useful for starting over without demo content or resetting after testing

### Fixed
- **Translations**: Fixed missing translations in v0.8.5 for Publication dates and Collapsible rows features
  - Added ~80 new translation keys for row controls, widget actions, background colors, video/image widgets, versions, MetaVox integration
  - Regenerated .js translation files from .json sources for all languages (nl, en, de, fr)
- **Row Controls Styling**: Fixed inconsistent styling of row header controls on colored backgrounds
  - Unified font colors, sizes, and backgrounds across all row control elements
  - Fixed "Collapsed by default" label to match other control labels
  - Fixed section title input to use correct system font

## [0.8.5] - 2025-12-29 - Publication Dates, Collapsible Rows & Editor Consolidation

### Added
- **Publication Date Filtering**: News widget can now filter pages based on publication dates
  - Configure MetaVox date fields in Settings → IntraVox → Publication
  - "Show only published pages" checkbox in News widget editor
  - Pages filtered based on publish date (visible from) and expiration date (hidden after)
  - Dropdown selection shows available date fields from MetaVox with display names
  - Warnings shown when MetaVox not available or fields not configured
  - Supports multiple date formats (YYYY-MM-DD, DD-MM-YYYY, ISO 8601, etc.)
- **Collapsible Rows (SharePoint-style)**: Rows can now be made collapsible
  - Checkbox "Collapsible section" in row controls to enable
  - Customizable section title shown in the header
  - Option to collapse by default in view mode
  - Click header to expand/collapse in view mode
  - All widgets in the row collapse together

### Changed
- **Editor Consolidation**: WidgetEditor now uses InlineTextEditor component
  - Consistent editing experience in modal and inline editing modes
  - All toolbar features (tables, links, headings, etc.) available everywhere
  - Single codebase for rich text editing

### Fixed
- **Nested List Styling**: Fixed visual hierarchy for nested lists
  - Ordered lists: 1. → a. → i. → 1. (cycling through levels)
  - Unordered lists: • → ○ → ▪ → • (cycling through levels)
  - Consistent styling in both edit and view modes
- **Row Controls Visibility**: Fixed row drag handle and controls visibility on colored row backgrounds
  - Uses inherited colors for proper contrast on any background color

## [0.8.4] - 2025-12-27 - News Widget & API Documentation

### Added
- **News Widget**: New widget type to display pages as news items
  - Select a source folder to pull pages from
  - Filter pages using MetaVox metadata fields (if MetaVox is installed)
  - Three layout options: List, Grid, and Carousel
  - Configurable number of items, sorting (date/title), and display options
  - Shows page thumbnail, title, date, and excerpt
  - Graceful fallback when MetaVox is not available
- **OpenAPI Specification**: Complete API documentation for third-party integration
  - Compatible with OCS API Viewer app for interactive documentation
  - Documents all IntraVox API endpoints with request/response schemas
  - Includes schemas for pages, widgets, media, navigation, comments, and news

## [0.8.3] - 2025-12-27 - Empty Line Preservation

### Fixed
- **Empty Lines in Text Editor**: Fixed issue where empty lines (hard returns) were being collapsed when saving
  - Multiple consecutive empty lines are now preserved in text widgets
  - Uses zero-width space placeholders to prevent Markdown from collapsing blank lines
  - Works correctly for both editing and viewing modes

## [0.8.2] - 2025-12-27 - Tables & Editor Improvements

### Added
- **Table Support**: Full table editing in text widgets
  - Insert, edit, and delete tables with the toolbar
  - Add/remove rows and columns
  - Resize columns by dragging
  - Tables saved as Markdown for portability
- **Compact Toolbar Mode**: Automatic compact toolbar for narrow columns
  - Shows B/I/U buttons with "More" dropdown for additional options
  - Auto-detects container width (<400px triggers compact mode)

### Changed
- **Table Header Rows**: Header rows now styled identically to body rows
  - Users can apply their own formatting (bold, etc.) if desired
- **Toolbar Icons**: Material Design icons for all toolbar buttons
  - Consistent icon style across the application

### Fixed
- **Row Drag-and-Drop**: Fixed issue where dragging rows caused widgets to lose their type after reordering
  - Uses stable row IDs instead of volatile row indices for cache keys
  - Reliable row reordering even with repeated drags in the same session
- **Shared Media Library**: Fixed 500 error when loading images from shared resources folder
  - Route parameter name mismatch corrected in API controller
- **Links Editor UI**: Delete button now uses consistent NcButton styling
  - Matches row and widget toolbar delete buttons for visual consistency
- **Delete Actions Visibility**: Improved contrast for delete actions in dropdown menus

## [0.8.1] - 2025-12-21 - Demo Data Improvements

### Changed
- **English Demo Homepage**: Updated structure to match Dutch homepage layout
  - Consistent sidebar with "Get Started" and "Resources" sections
  - Same visual layout across all language versions

## [0.8.0] - 2025-12-21 - Export/Import, Security & Editor Improvements

### Added

#### Export/Import System
- **Full IntraVox Export/Import**: Export entire IntraVox installations to ZIP and import on other servers
  - Export pages, navigation, footer, comments, and reactions per language
  - Confluence HTML import support for migrating from Atlassian
  - Parent page selection for targeted imports
  - Page hierarchy preserved during export/import

#### MetaVox Integration
- **MetaVox Metadata Export/Import**: Full integration with MetaVox file metadata
  - Metadata exported with pages when MetaVox is installed
  - Automatic field definition validation and optional auto-creation
  - Version compatibility handling between installations
  - Works seamlessly with or without MetaVox installed

#### Media Management
- **Shared Media Library**: New `_resources` folder per language for reusable media
  - Hierarchical folder structure with subfolder navigation
  - MediaPicker with 3 tabs: Upload, Page media, Shared library
- **SVG Image Support**: Upload and display SVG files with automatic sanitization

#### Editor Improvements
- **Row Drag-and-Drop**: Reorder rows by dragging the handle in the row toolbar
- **Header Row Transparency**: Default header row background is now transparent

#### Other Features
- **Nextcloud Unified Search**: IntraVox pages searchable via Ctrl+K
- **Version History**: View and restore previous page versions

### Changed
- **Export Format v1.3**: Enhanced format with guaranteed `_exportPath` and metadata support
- **Navigation Scrolling**: Horizontal scrollbar for long navigation menus
- **Performance**: Smart cache refresh, localStorage persistence, lazy loading sidebar
- **Admin Interface**: Tabbed navigation for export/import/settings
- **Toolbar Contrast**: Improved visibility for active toolbar buttons (WCAG compliant)
- **Widget Text Contrast**: Dynamic link and selection colors based on row background

### Fixed
- Export/Import reliability with proper page hierarchy handling
- Navigation dropdown visibility on all screen sizes
- Confluence import with page ordering

### Security
- **@PublicPage Removal**: All pages now require Nextcloud authentication
- **parentPageId Validation**: Pages can only be created within authorized groupfolders
- **Enhanced Path Sanitization**: Improved protection against path traversal attacks
- **ZIP Slip Prevention**: Secure ZIP extraction with path validation
- **Temp File Security**: Cryptographically secure filenames with restrictive permissions
- **Import Authorization**: Permission checks before import operations
- **Sensitive Log Masking**: PII and credentials excluded from logs

## [0.7.1] - 2025-12-13 - Translation Fixes

### Fixed
- Added missing engagement translations for German, French, and Dutch

## [0.7.0] - 2025-12-13 - Reactions, Comments & Performance

### Added
- Emoji reactions on pages
- Comments system with threaded replies
- Comment reactions
- Admin and per-page engagement settings

### Changed
- Performance optimizations (50% fewer API calls, localStorage caching)

## [0.6.0] - 2025-12-09 - Video Widget & Media Management

### Added
- Welcome screen for fresh installations
- Video widget with platform whitelist
- Clickable image links
- Local video upload

### Changed
- Media folder renamed to `_media`
- Admin settings redesign with tabs

## [0.5.0] - 2025-11-29 - Navigation & Links

### Fixed
- Links widget text saving
- Navigation links after editing

### Changed
- Simplified new page creation

## [0.4.0] - 2025-11-16 - Enhanced UI

### Added
- Links widget with grid layout
- Import pages CLI command
- Demo data scripts

### Changed
- Reduced vertical spacing
- Fixed HTML entity display in navigation

## [0.3.0] - 2025-11-13 - UI Refinements

### Changed
- Dropdown navigation redesign
- Removed debug logging

## [0.2.0] - 2025-11-08 - Multi-language Support

### Added
- URL routing with language paths
- Footer infrastructure
- Multi-language support (nl, en, de, fr)

## [0.1.0] - 2025-11-07 - Initial Release

### Added
- SharePoint-like page creation with drag-and-drop
- Flexible grid layout (1-5 columns)
- Widget types: text, images, headings, links, files, dividers
- Rich text editor with TipTap
- Page and navigation management
- Secure JSON-based storage
- Nextcloud Files and Groups integration
- Team folder support
