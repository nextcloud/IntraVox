# Changelog

All notable changes to IntraVox will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Changed
- **Design System**: Updated all components to use Nextcloud CSS variables
  - Replaced hardcoded `border-radius: 3px` with `var(--border-radius-large)`
  - Ensures compatibility with future Nextcloud theme changes
  - Consistent styling across all IntraVox components

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
