# Changelog

All notable changes to IntraVox will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [0.8.0] - 2025-12-20 - Export/Import, Media & MetaVox Integration

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
- **Video Widget**: Embed videos from YouTube, Vimeo, PeerTube and more
  - Domain whitelist system for security
  - Admin configurable video services

#### User Engagement
- **Emoji Reactions**: React to pages with emoji (Confluence-style)
- **Comments System**: Threaded comments with replies on pages
- **Comment Reactions**: Emoji reactions on individual comments
- **Engagement Settings**: Global and per-page control over reactions/comments

#### Other Features
- **Image Links**: Images can now link to pages or external URLs
- **Local Video Upload**: Upload MP4/WebM/OGG videos to IntraVox
- **Nextcloud Unified Search**: IntraVox pages searchable via Ctrl+K
- **Version History**: View and restore previous page versions

### Changed
- **Export Format v1.3**: Enhanced format with guaranteed `_exportPath` and metadata support
- **Navigation Scrolling**: Horizontal scrollbar for long navigation menus
- **Performance**: Smart cache refresh, localStorage persistence, lazy loading sidebar
- **Admin Interface**: Tabbed navigation for export/import/settings

### Fixed
- Export/Import reliability with proper page hierarchy handling
- Navigation dropdown visibility on all screen sizes
- Comment import with original authors preserved
- Page reactions and comment reactions import
- Confluence import with page ordering

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
