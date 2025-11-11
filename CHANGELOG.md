# Changelog

All notable changes to IntraVox will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

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
