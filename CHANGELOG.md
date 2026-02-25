# Changelog

All notable changes to IntraVox will be documented in this file.

IntraVox is a Nextcloud intranet page builder.

## [0.9.16] - 2026-02-23

### Added
- **RSS feed** - Personal RSS feed for each user with token-based authentication, feed media endpoint, conditional requests (ETag/Last-Modified), and brute force protection
- **RSS feed settings UI** - Generate, regenerate, and revoke feed tokens with configurable scope (my language / all languages) and item limit
- **RSS feed sharing policy** - Feed respects Nextcloud's "Allow users to share via link" admin setting; shows clear error when disabled
- **RSS feed cross-language links** - Feed items link via `#page-{uniqueId}` format, automatically resolving pages across language folders

### Changed
- **Dummy text generator** - Removed `=dad()` alias, only `=dadjokes()` and `=lorem()` are now supported
- **`=lorem()` rich formatting** - Now generates richly formatted content showcasing all text widget capabilities: headings, blockquotes, bullet lists, tables, ordered lists, and mixed inline marks (bold, italic, code, underline, strikethrough)
- **Dummy text multilingual labels** - `=lorem()` section headings, table columns, and status labels are now localized for EN, NL, DE, and FR
- **Documentation** - Added RSS feed admin setup guide with GroupFolder permission requirements (Read + Share), ACL examples, and troubleshooting

### Fixed
- **People widget "Invalid Date"** - Birthdate now correctly displayed regardless of Nextcloud locale settings. Added backend normalization of locale-specific date formats (DD-MM-YYYY, DD/MM/YYYY, DD.MM.YYYY) to ISO 8601 before sending to frontend, with additional frontend fallback for edge cases
- **RSS feed empty for ACL users** - Documented that GroupFolders requires both Read and Share permissions for public feed endpoints; updated all permission tables and recommendations
- **Webpack build failure** - Added `string_decoder` and `buffer` to webpack resolve.fallback to fix build error caused by `@nextcloud/dialogs` 7.3.0 pulling in Node.js core modules via sax/is-svg

## [0.9.15] - 2026-02-19

### Fixed
- **MetaVox sidebar on NC33** - MetaVox metadata tab now works in IntraVox on Nextcloud 33, where MetaVox registers via the new scoped globals API instead of the legacy OCA.Files.Sidebar API
- MetaVox mock Node object now passes correct `mountType` and `mountPoint` attributes (camelCase) so groupfolder detection works properly

## [0.9.14] - 2026-02-18

### Added
- **Nextcloud 33 support** - App now supports Nextcloud 32 and 33 (PHP 8.2+ required)
- **Page nesting depth** increased from 3 to 5 levels for deeper page hierarchies
- **Dummy text generator** (easter egg) - Type `=dadjokes(3,5)` or `=lorem(2,4)` in a text widget and press Enter to generate dummy content (inspired by MS Word's `=rand()`)
- **Birthdate field** support in People widget - display, filter (`is_today`, `within_next_days`)
- **Bluesky** social link support in People widget
- **Date filter operators** for People widget: `is today`, `within next X days`

### Fixed
- **People widget display options** now correctly control rendered fields in grid layout
  - Removed `gridShowFields` override that forced fields off
  - Removed hardcoded `layout !== 'grid'` template restrictions
  - Removed CSS rule that hid headline in grid layout
  - `showFields` is now the single source of truth across all layouts
- All display option checkboxes now always visible in editor (no longer hidden per layout)
- `showFields` whitelist expanded in backend (PageService.php) to support all 15 field types
- Legacy `title` field synced with `role` for backwards compatibility
- Heading widget bottom spacing increased
- Comment cascade delete now properly deletes replies and updates count
- Security: markdown-it updated to 14.1.1 (ReDoS fix in linkify inline rule)
- Security: ajv updated to 8.18.0 (CVE-2025-69873 ReDoS fix)

### Changed
- Twitter links now point to x.com instead of twitter.com
- Dependency updates: axios 1.13.5, qs 6.14.2, webpack 5.105.0, ajv 8.18.0

## [0.9.13] - 2026-02-12

### Added
- **People widget** for displaying user profiles with Card, List, and Grid layouts
- Manual selection or filter-based user selection
- Group filtering with "is one of" operator for multiple groups
- Field filtering with equals, contains, does not contain, is not empty, is empty operators
- Customizable display options (avatar, name, role, headline, email, etc.)
- Pagination with "Show more" when more users match filters
- LDAP/OIDC custom field support (auto-detected)
- User search in Nextcloud Unified Search

### Changed
- Filter fields ordered to match Display Options structure
- Social links combined into single toggle (X/Fediverse)
- Phone number display default changed to off (privacy)

### Fixed
- `profileEnabled` excluded from custom fields display
- Dark background text contrast in People widget
- Column alignment in card layout

## [0.9.12] - 2026-02-09

### Changed
- Templates now install automatically during app install/update (no longer requires manual `occ intravox:setup`)
- Existing templates are preserved (idempotent)

## [0.9.11] - 2026-02-09

### Added
- **Page templates** - 7 default templates (Department, Event, Knowledge Base, Landing Page, News Article, News Hub, Project)
- Visual template preview cards with SVG layout schematic
- Complexity indicator (Simple/Medium/Advanced) and widget count statistics
- Template translations for NL, EN, DE, FR
- Stock images for all templates
- Enlarged template modal with improved gallery

## [0.9.10] - 2026-02-05

### Fixed
- **Text editor table spacing** - Tables no longer double-space or corrupt formatting on save
- Asterisks no longer appear after saving bold/italic combined with underline
- Preserved user blank lines between tables while preventing spacing growth
- Home breadcrumb link in public share view

## [0.9.9] - 2026-02-04

### Added
- **Maintenance tab** in admin settings to scan, recover, and delete orphaned GroupFolder data

### Changed
- Renamed "GroupFolders" to "Team Folders" to match Nextcloud App Store naming
- Specific error messages for different Team Folders failure scenarios
- Full translations in Dutch, German, and French

## [0.9.8] - 2026-02-01

### Added
- **Public sharing** via Nextcloud share links with full anonymous page access
- **Password-protected shares** with session-based auth and brute force protection
- Share dialog with scope indicator and password badge
- Admin shares overview in admin settings
- Public news widget and media access via share token
- Links widget color system redesign with container and per-link background options
- Telemetry expansion (country code, database type, OS family, web server, Docker detection)

### Fixed
- Public share page tree only showing homepage for language-root shares
- Links widget contrast issues on light backgrounds
- Webpack chunk caching (`TypeError: n[e] is undefined` after rebuild)

### Security
- Share token validation before any data access
- Share scope path enforcement prevents access outside shared folder
- Anonymous rate throttling (60 req/min) on all public endpoints
- Password brute force protection (10 attempts/min per IP)

### Removed
- Unused HMAC token system (dead code from earlier approach)

## [0.9.7] - 2026-01-26

### Fixed
- **Code blocks** no longer corrupt after editing - backticks no longer accumulate when saving and re-editing
- Fixed double-processing of nested `<code>` tags inside `<pre>` elements

## [0.9.6] - 2026-01-20

### Changed
- Use `OCP\DB\Exception` with `getReason()` instead of Doctrine exceptions (Nextcloud coding standards)

### Security
- Updated enshrined/svg-sanitize from ^0.20 to ^0.22 (medium severity fix)

## [0.9.5] - 2026-01-20

### Fixed
- **PostgreSQL compatibility** - Handle duplicate key errors (SQLSTATE 23505) in SetupService
- LicenseService null check for shared folder to prevent crashes
- AnalyticsService improved exception handling for unique constraints

## [0.9.2 - 0.9.4] - 2026-01-19 to 2026-01-20

Certificate updates and App Store re-registration. No functional changes.

## [0.9.1] - 2026-01-19

### Added
- **Statistics tab** in admin settings with page counts per language
- **Anonymous telemetry** with opt-in usage statistics (page counts, Nextcloud/PHP version info)
- Translation script (`npm run l10n`) for generating JavaScript translation files

### Fixed
- PHP 8.4 implicit nullable parameter deprecation warning

## [0.9.0] - 2026-01-17

### Added
- **Analytics API** for tracking page views and statistics
- **Bulk Operations API** for batch delete, move, update (admin only, max 100 pages)
- **OCS Media Routes** for external API access via Basic Auth
- **API Error Handling** with consistent responses, `errorId` for support correlation, and `ApiErrorTrait`

### Security
- File upload extension whitelist with MIME type validation and `getimagesize()` cross-check
- SVG sanitization with dangerous element detection
- Path traversal protection (URL-encoded, Unicode, null bytes)
- Admin-only endpoints properly secured

### Fixed
- Sidebar closes on page navigation (prevents stale data display)

## [0.8.9] - 2026-01-15

### Fixed
- **Widget color consistency** - Centralized `colorUtils.js` for consistent dark background detection
- Links widget row background inheritance and Primary color variable
- News widget text contrast on dark row backgrounds

### Changed
- All widgets refactored to use consistent background handling pattern

## [0.8.8] - 2026-01-15

### Added
- **Version History UI** redesigned with Files App styling and relative time formatting
- News widget background color support (None/Light/Primary)
- Field-type specific filter operators for MetaVox (date, number, select, multiselect, checkbox)
- Dynamic value inputs adapting to field type (date picker, number input, dropdown)

### Fixed
- Sidebar state preservation during refresh (uses `v-show` instead of `v-if`)

## [0.8.7] - 2025-12-30

### Added
- **Links widget page selector** - Select internal pages from dropdown with auto-fill
- **Links widget drag-and-drop** - Reorder links by dragging

### Fixed
- Row drag-and-drop: prevent rows from being dropped into columns
- News widget excerpts: strip markdown from preview text
- News widget shared library images from `_resources`
- Links widget data persistence for internal page links

## [0.8.6] - 2025-12-29

### Added
- **Clean Start** option in Demo Data settings to reset a language to empty content

### Fixed
- Added ~80 missing translation keys for row controls, widgets, and versions
- Row controls styling on colored backgrounds

## [0.8.5] - 2025-12-29

### Added
- **Publication date filtering** for News widget using MetaVox date fields
- **Collapsible rows** - SharePoint-style collapsible sections with customizable titles and default collapse state

### Changed
- Editor consolidated: WidgetEditor now uses InlineTextEditor component

### Fixed
- Nested list styling visual hierarchy (cycling list markers per indent level)
- Row controls visibility on colored row backgrounds

## [0.8.4] - 2025-12-27

### Added
- **News widget** with List, Grid, and Carousel layouts
- MetaVox filtering support for news items
- Complete OpenAPI specification for OCS API Viewer
- News widget translations for EN, NL, DE, FR

## [0.8.2] - 2025-12-27

### Added
- **Table support** in text widgets (insert, edit, resize, add/remove rows and columns)
- Compact toolbar mode for narrow columns (auto-detects <400px width)
- Material Design icons for toolbar

### Fixed
- Row drag-and-drop widget type preservation (stable row IDs instead of volatile indices)
- Shared media library 500 error (route parameter mismatch)
- Links editor UI consistency and delete action visibility

## [0.8.1] - 2025-12-21

### Changed
- English demo homepage updated to match Dutch layout structure

## [0.8.0] - 2025-12-21

### Added
- **Row drag-and-drop** reordering in page editor
- **Export/Import system** with ZIP files for full site backup and migration
- **Confluence HTML import** for Atlassian migration
- **MetaVox metadata** export/import integration
- **Shared Media Library** with `_resources` folder and hierarchical folder navigation
- **SVG image support** with server-side sanitization (enshrined/svg-sanitize)
- MediaPicker component with 3-tab interface (Upload, Page Media, Shared Library)

### Changed
- Header row default transparency
- Navigation horizontal scrollbar for long menus
- Toolbar active state contrast improved (WCAG compliant)
- Dynamic link/selection colors per row background

### Security
- `@PublicPage` removal (all pages require authentication)
- `parentPageId` validation
- Enhanced path sanitization and ZIP slip prevention
- Import authorization checks
- Comment IDOR prevention
- iframe sandboxing
- Sensitive log masking

### Fixed
- Import folder structure preservation for nested pages
- MediaPicker SVG preview loading
- GroupFolder setup idempotency
- Page settings persistence

## [0.7.1] - 2025-12-13

### Fixed
- Added 60+ missing engagement-related translation keys for DE, FR, and NL

## [0.7.0] - 2025-12-13

### Added
- **Emoji reactions** on pages (18 emoji options)
- **Comments system** with threaded replies and comment reactions
- Admin engagement settings (global enable/disable)
- Page-level engagement settings (per-page overrides)
- Image link target option (open in same/new tab)

### Changed
- Smart cache refresh (50% fewer API calls)
- localStorage persistence (75% faster initial load)
- Lazy loading sidebar (67% faster)

## [0.6.1] - 2025-12-11

### Fixed
- Clickable image links
- Image crop position (top/center/bottom)

## [0.6.0] - 2025-12-09

### Added
- **Welcome screen** for fresh installations with setup instructions
- **Clickable image links** (to internal pages or external URLs)
- **Video widget** with multi-platform support (YouTube, Vimeo, PeerTube, local files)
- Admin settings with Video Domains management (presets + custom servers)
- Unified media folder structure (`_media/` instead of `images/`)
- New search icon for Nextcloud unified search integration

### Changed
- Complete translations for NL, DE, FR languages
- Default video domains include privacy-friendly platforms
- Performance optimizations for page loading

## [0.5.20] - 2025-12-05

### Added
- **Widget duplicate** button for header row and side column widget toolbars
- Generic `duplicateWidgetGeneric()` helper for all zones

## [0.5.19] - 2025-12-05

### Changed
- **Performance Optimization v2** - Request-level caching for directory listings and folder permissions
- Replaced `setInterval` language polling with MutationObserver
- Replaced `JSON.parse`/`stringify` with `structuredClone()` for faster deep cloning

## [0.5.x Patch Releases] - 2025-12-03 to 2025-12-05

### Fixed
- **v0.5.16** - Admin translations: regenerated l10n `.js` files from `.json` files
- **v0.5.15** - Performance: reduced page load time from ~11s to ~1-2s; fixed admin settings translations
- **v0.5.14** - Header row widgets no longer silently lost when saving a page
- **v0.5.13** - Navigation save handling for both wrapped/unwrapped data formats; admin demo data translations
- **v0.5.12** - Demo data path detection for custom apps directories, Docker, and non-standard setups
- **v0.5.11** - Permission groups created correctly during App Store installation; sync all admins to IntraVox Admins group
- **v0.5.10** - GroupFolders dependency clarification in app description
- **v0.5.9** - App Store release: added app icons, fixed "image not found" error
- **v0.5.8** - Clean build for App Store submission

## [0.5.7] - 2025-12-03

### Added
- **GroupFolder ACL authorization** - PermissionService integration with Nextcloud GroupFolder ACLs
- Users without folder access cannot see content in navigation
- Direct URL links blocked for unauthorized users
- Read-only users don't see Edit/New Page buttons
- All API endpoints enforce server-side permission checks

## [0.5.6] - 2025-12-01

### Added
- **PageTreeSelect** - Hierarchical page selector in navigation editor
- Promote/Demote buttons to move navigation items up/down hierarchy levels
- Mutually exclusive page/URL fields in navigation items

### Fixed
- External URL target selector

## [0.5.5] - 2025-11-30

### Added
- **Page Tree modal** for hierarchical page navigation
- **Side column** support for page layouts
- Improved breadcrumb navigation

### Changed
- Removed all debug statements from Vue components and PHP controllers
- Improved widget sanitization (preserve id, image properties, links, dividers)
- Extended background color support in sanitizer
- Restructured demo data with proper image organization

## [0.5.0] - 2025-11-29

### Changed
- Fixed LinksWidget link names (use `text` property)
- Removed "Add to navigation" option from new page creation modal
- Standardized on `uniqueId` everywhere, removed legacy `pageId` usage
- Fixed navigation links after editing (uniqueId normalization)
- Removed unused search indexing code

## [0.4.13] - 2025-11-24

### Added
- **Folder-level permission filtering** in navigation
- Users only see navigation items for pages they have access to
- External/custom URL links remain visible to all users
- Request-level permission caching for performance
- Recursive filtering: parent items without accessible children are hidden

## [0.4.12] - 2025-11-24

### Added
- **Frontend Cache Service** with in-memory caching (5-minute TTL)
- Parallel API calls for initial page load (pages, navigation, footer)
- Breadcrumb included in page API response (reduces API calls by 50%)

### Changed
- Breadcrumb shows current page as clickable item
- New page creation creates siblings instead of children

### Performance
- 50% reduction in API calls during navigation
- ~95% faster page loads for cached content
- ~60% faster initial application load

## [0.4.10] - 2025-11-24

### Changed
- **Filesystem timestamps** replace manual timestamp management in JSON files
- Page metadata now uses `getMTime()` directly

### Fixed
- Metadata API for uniqueId-based lookups
- Details panel page metadata loading

## [0.4.6] - 2025-11-22

### Added
- **Footer editing** on homepage with full markdown support
- **Granular ACL-based permission system** at folder level (department-specific editing)
- Enhanced Dutch translations for all UI elements
- Path-based home page detection (language-agnostic)

## [0.4.1] - 2025-11-18

### Added
- **Nextcloud Unified Search** integration - IntraVox pages searchable via Ctrl+K
- Searches in titles, headings, and content with direct navigation

### Removed
- Custom search UI (replaced by native Nextcloud search)

## [0.4.0] - 2025-11-16

### Added
- **Links widget** with grid layout support and Material Design icons
- Import pages command for bulk page creation from JSON files
- Demo data deployment scripts

### Changed
- Reduced vertical spacing throughout page viewer and editor for better content density
- Fixed HTML entity encoding in navigation

## [0.3.0] - 2025-11-13

### Changed
- **Dropdown navigation redesign** with custom HTML dropdowns and clean styling
- Increased border-radius on page rows for better visual hierarchy
- Removed PageCacheNotification component
- Debug logging cleanup

## [0.2.9] - 2025-11-12

### Added
- **Navigation system rewrite** with three-level hierarchy support
- Mobile hamburger menu with collapsible levels
- Desktop cascading dropdown using NcActions
- Desktop megamenu with grid-based layout

### Changed
- Nextcloud-compliant styling (removed gradients, transform effects)
- All navigation types use Nextcloud Vue 3 components

## [0.2.8] - 2025-11-11

### Added
- **UniqueId-based URLs** for permanent page identification
- `/p/{uniqueId}` route for shareable links
- Automatic uniqueId generation for legacy pages
- Open Graph meta tags

### Changed
- Removed all debug logging (production readiness)

## [0.2.7] - 2025-11-11

### Added
- **Version history** with automatic version creation on save and one-click restoration
- Page Details sidebar with version tracking

## [0.2.6] - 2025-11-11

### Added
- **Markdown storage** for content (WYSIWYG editor with markdown backend)
- UUID v4 for page uniqueIds

### Changed
- New pages open in edit mode automatically
- Text widgets start empty (no placeholder text)

## [0.2.5] - 2025-11-10

### Added
- **Readable page IDs** generated from titles (e.g., "Welcome" becomes "welcome")
- Clean, readable URLs (e.g., `/apps/intravox#/welcome`)
- Automatic duplicate handling with numbered suffixes
- New pages immediately visible in Files app via scanner integration

### Fixed
- 400 Bad Request errors on page creation/saving
- Text selection contrast with theme colors
- Divider widgets adapt to row background colors

## [0.2.4] - 2025-11-10

### Changed
- Simplified row background color palette to 4 essential theme colors
- Complete overhaul of text color inheritance for proper contrast

## [0.2.3] - 2025-11-10

### Added
- Automated release creation and rollback scripts

## [0.2.2] - 2025-11-10

### Added
- **Row background colors** with theme-based color picker
- **Footer component** with rich text editing for homepage
- PageActionsMenu component with 3-dot menu
- Link support in InlineTextEditor

## [0.2.1] - 2025-11-10

### Fixed
- Column layout persistence (row.columns now saved correctly)
- Cache-busting TypeError in PageController

### Changed
- Default column count changed from 3 to 1
- Removed URL routing (reverted to simple navigation)

## [0.2.0] - 2025-11-09

### Added
- **URL routing** with language support (`/apps/intravox/{language}/{pageId}`)
- Browser back/forward button navigation support
- Footer infrastructure (temporarily disabled)

### Fixed
- Column layout bug where widgets were spread across multiple columns

## [0.1.0] - 2025-11-09

### Added
- **Initial release** of IntraVox
- Multi-language support (Dutch, English, German, French)
- Drag-and-drop page editor with flexible grid layouts (1-5 columns)
- Rich widget types: text, headings, images, links, files, dividers
- Megamenu and dropdown navigation systems
- Real-time collaborative editing via GroupFolders
- Language-aware content management
- Responsive design for mobile, tablet, and desktop
- Vue.js 3 frontend with Nextcloud integration
- PHP backend with CSRF protection
- Comprehensive i18n support with .po files
