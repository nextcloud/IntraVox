# Changelog

All notable changes to IntraVox will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

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
