# IntraVox Administrator Guide

This guide provides technical documentation for system administrators who need to install, configure, and maintain IntraVox.

---

## Table of Contents

- [Installation](#installation)
- [Architecture Overview](#architecture-overview)
- [Security Features](#security-features)
- [Storage Structure](#storage-structure)
  - [Multi-Language Support](#multi-language-support)
- [API Documentation](#api-documentation)
- [Development](#development)
- [Troubleshooting](#troubleshooting)
- [Advanced Configuration](#advanced-configuration)
  - [Adding Support for Additional Languages](#adding-support-for-additional-languages)

---

## Installation

### System Requirements

- **Nextcloud**: 32 or higher
- **PHP**: 8.0 or higher
- **Node.js**: 16+ and npm (for building frontend)
- **GroupFolders App**: Required for shared storage

### Installation Steps

#### 1. Clone or Download IntraVox

```bash
cd /path/to/nextcloud/apps
git clone https://github.com/shalution/IntraVox.git intravox
cd intravox
```

#### 2. Install Dependencies

```bash
npm install
```

#### 3. Build Frontend

For production:
```bash
npm run build
```

For development (with auto-rebuild):
```bash
npm run watch
```

#### 4. Enable the App

```bash
cd /path/to/nextcloud
php occ app:enable intravox
```

#### 5. Install GroupFolders App

IntraVox requires the GroupFolders app to be installed and enabled:

```bash
# Install from Nextcloud app store or enable if already installed
php occ app:enable groupfolders
```

#### 6. Setup GroupFolder and Permissions

Run the setup command to automatically configure IntraVox:

```bash
php occ intravox:setup
```

This command will:
- Create a GroupFolder named "IntraVox" (or find existing one)
- Create required groups: "IntraVox Admins" and "IntraVox Users"
- Configure folder permissions:
  - IntraVox Admins: Full access (read, write, delete, share)
  - IntraVox Users: Read-only access
- Create language subfolders (nl/, en/, de/, fr/) for multi-language support
- Create a default homepage (home.json) in each language folder
- Create subdirectories (images/, files/) for each language
- Create default navigation structure

**Access Control:**
- **IntraVox Admins** group: Full permissions to create and edit pages
- **IntraVox Users** group: Read-only access to view pages
- Users must be added to one of these groups to access IntraVox content

**Managing users in groups:**
```bash
# Add user to admin group (can edit pages)
php occ group:adduser "IntraVox Admins" username

# Add user to users group (read-only access)
php occ group:adduser "IntraVox Users" username

# Remove user from group
php occ group:removeuser "IntraVox Admins" username
```

**How It Works:**
IntraVox automatically discovers the IntraVox GroupFolder using an intelligent search mechanism:
1. Searches all available GroupFolders
2. Finds the folder with mount point "IntraVox"
3. If multiple exist, uses the most recent one (highest ID)
4. No hardcoded folder IDs - works reliably across installations

---

## Architecture Overview

### Technology Stack

**Frontend:**
- Vue 3 (Composition API)
- Nextcloud Vue Components (@nextcloud/vue)
- Webpack 5 (bundling)
- SortableJS (drag-and-drop via vuedraggable)
- Axios (HTTP client)

**Backend:**
- PHP 8.0+
- Nextcloud App Framework
- Nextcloud GroupFolders API
- Nextcloud Files API
- OCC Commands

### Project Structure

```
intravox/
├── appinfo/
│   ├── info.xml                    # App metadata and version
│   └── routes.php                  # API route definitions
│
├── lib/
│   ├── AppInfo/
│   │   └── Application.php         # App initialization
│   │
│   ├── Command/
│   │   ├── SetupCommand.php        # OCC command: php occ intravox:setup
│   │   ├── MigrateToLanguageStructureCommand.php  # Migration command
│   │   ├── CreateLanguageHomepagesCommand.php     # Create language homepages
│   │   └── CopyNavigationCommand.php              # Copy navigation between languages
│   │
│   ├── Controller/
│   │   ├── PageController.php      # Main page rendering
│   │   └── ApiController.php       # REST API endpoints
│   │
│   └── Service/
│       ├── PageService.php         # Business logic, validation, security, multi-language
│       ├── SetupService.php        # GroupFolder setup and permission management
│       └── NavigationService.php   # Navigation management (dropdown/megamenu)
│
├── src/                            # Vue.js frontend source
│   ├── main.js                     # Application entry point
│   ├── App.vue                     # Root component
│   │
│   └── components/
│       ├── PageViewer.vue          # Read-only page display
│       ├── PageEditor.vue          # Drag-and-drop page editor
│       ├── Widget.vue              # Widget renderer (displays widgets)
│       ├── InlineTextEditor.vue    # Inline text editing with Markdown toolbar
│       ├── WidgetPicker.vue        # Widget type selector modal
│       ├── WidgetEditor.vue        # Widget configuration modal
│       ├── PageListModal.vue       # Page navigation modal
│       ├── NewPageModal.vue        # Create new page modal
│       ├── Navigation.vue          # Navigation bar (dropdown/megamenu)
│       └── NavigationEditor.vue    # Navigation editor modal
│
├── css/
│   └── main.css                    # Global styles and overrides
│
├── img/
│   └── app.svg                     # App icon
│
├── templates/
│   └── main.php                    # Main template (loads Vue app)
│
├── js/                             # Built frontend files (generated)
│   ├── intravox-main.js           # Main bundle
│   └── intravox-main.js.map       # Source map
│
├── package.json                    # NPM dependencies
├── webpack.config.js               # Webpack configuration
└── README.md                       # User documentation
```

### Component Architecture

```
App.vue (Root)
│
├─ Navigation - Navigation bar with dropdown/megamenu
│
├─ NavigationEditor - Edit navigation structure
│
├─ PageListModal - Browse all pages
│
├─ NewPageModal - Create new page
│
├─ PageViewer (Display Mode)
│  └─ Widget (x N) - Renders individual widgets
│     ├─ Text (with Markdown rendering)
│     ├─ Heading (H1-H6)
│     ├─ Image (with positioning support)
│     ├─ Link
│     ├─ File
│     └─ Divider
│
└─ PageEditor (Edit Mode)
   ├─ Draggable Rows/Columns (vuedraggable)
   │  └─ Widget (x N) - Each with edit/delete controls
   │     └─ InlineTextEditor - Inline editing for text widgets
   │
   ├─ WidgetPicker - Select widget type to add
   │
   └─ WidgetEditor - Configure widget properties
      ├─ Text Editor (with Markdown toolbar)
      ├─ Image Upload (with size presets and vertical positioning)
      ├─ Heading Configuration (H1-H6)
      ├─ Link Editor (internal pages or external URLs)
      ├─ File Upload
      └─ Divider (visual separator)
```

### Data Flow

```
User Action → Vue Component → API Call (Axios)
                                    ↓
                            ApiController.php
                                    ↓
                            PageService.php (validation + security + language detection)
                                    ↓
                            NavigationService.php (for navigation operations)
                                    ↓
                            Nextcloud Files API + GroupFolders API
                                    ↓
                    GroupFolder: IntraVox/{language}/*.json (storage)
                                    ↓
                            Response → Vue Component → UI Update
```

---

## Security Features

IntraVox is built with a **security-first** approach:

### Input Validation & Sanitization

**PHP Backend (PageService.php):**
- All input is validated before processing
- Widget types are whitelisted (text, heading, image, link, file, divider)
- Content is sanitized using `htmlspecialchars()` to prevent XSS
- Path traversal prevention for file operations
- Maximum file size limits (5MB for images)
- File type validation (only allowed: jpg, jpeg, png, gif, webp)

**Frontend:**
- Markdown rendering escapes HTML by default
- Only safe Markdown features are rendered
- No inline JavaScript execution possible
- Content Security Policy compatible

### JSON-Only Storage

- All pages are stored as pure JSON (no executable code)
- Widget content is data, never executed as code
- Prevents code injection attacks
- Safe to share between users

### Access Control

- GroupFolder "IntraVox" provides shared storage
- Nextcloud GroupFolders system provides access control
- Two predefined groups control access:
  - **IntraVox Admins**: Full permissions (read, write, delete, share) - Can create and edit pages
  - **IntraVox Users**: Read-only permissions - Can only view pages
- Administrators add users to these groups to grant access
- Users only see content they're authorized to access
- No direct file system access from the frontend
- Permission checks are enforced at the GroupFolder level

### API Security

- All API endpoints require authenticated Nextcloud session
- CSRF protection via Nextcloud's built-in middleware
- No sensitive data in URLs (all POST/PUT requests)
- Proper HTTP methods (GET for reading, POST/PUT for writing, DELETE for removing)

---

## Storage Structure

IntraVox uses a **GroupFolder** for storage, with built-in **multi-language support**:

```
GroupFolder: IntraVox/              # Shared GroupFolder accessible to all authorized users
    ├── nl/                         # Dutch content
    │   ├── home.json              # Dutch homepage
    │   ├── images/                # Dutch images
    │   ├── files/                 # Dutch files
    │   └── about-us/              # Example page folder
    │       ├── about-us.json      # Page content
    │       ├── images/            # Page-specific images
    │       └── files/             # Page-specific files
    │
    ├── en/                         # English content
    │   ├── home.json              # English homepage
    │   ├── images/                # English images
    │   ├── files/                 # English files
    │   └── team-wiki/             # Example page folder
    │       ├── team-wiki.json     # Page content
    │       ├── images/            # Page-specific images
    │       └── files/             # Page-specific files
    │
    ├── de/                         # German content
    │   └── ...                    # Same structure
    │
    └── fr/                         # French content
        └── ...                    # Same structure
```

### Multi-Language Support

IntraVox automatically detects each user's language preference and shows content from the appropriate language folder:

**Supported Languages:**
- `nl` - Dutch
- `en` - English (default)
- `de` - German
- `fr` - French

**How It Works:**
1. User's language preference is read from Nextcloud settings (`core` → `lang`)
2. IntraVox extracts the language code (e.g., `nl_NL` → `nl`)
3. Content is loaded from the corresponding language folder
4. If a language folder doesn't exist, it falls back to English (`en`)

**Creating Content for Multiple Languages:**
- Each language has its own independent content structure
- Pages must be created separately for each language
- The setup command automatically creates all language folders
- To add content for a specific language, users with that language preference simply create pages normally

**Example:**
- A Dutch user sees content from `/IntraVox/nl/`
- An English user sees content from `/IntraVox/en/`
- A German user sees content from `/IntraVox/de/`
- A French user sees content from `/IntraVox/fr/`

### Page JSON Structure

```json
{
  "id": "home",
  "title": "Welcome to IntraVox",
  "layout": {
    "columns": 3,
    "rows": [
      {
        "columns": 2,
        "widgets": [
          {
            "id": "widget-1",
            "type": "heading",
            "content": "Welcome!",
            "level": 1,
            "column": 1,
            "order": 1
          },
          {
            "id": "widget-2",
            "type": "text",
            "content": "**IntraVox** makes collaboration easy.",
            "column": 1,
            "order": 2
          },
          {
            "id": "widget-3",
            "type": "image",
            "src": "company-logo.png",
            "alt": "Company Logo",
            "width": 500,
            "column": 2,
            "order": 1
          }
        ]
      }
    ]
  }
}
```

### Widget Types

| Type | Properties | Description |
|------|-----------|-------------|
| `text` | `content` | Rich text with Markdown support (bold, italic, lists, links, code) |
| `heading` | `content`, `level` (1-6) | Page headings (H1-H6) |
| `image` | `src`, `alt`, `width`, `objectPosition` | Images with size presets (300px/500px/800px/full) and vertical positioning (top/center/bottom) |
| `link` | `url`, `text` | Links to internal pages or external websites |
| `file` | `path`, `name` | Embedded file downloads |
| `divider` | - | Visual separator / horizontal rule |

---

## API Documentation

### Base URL
```
/apps/intravox/api
```

### Endpoints

#### List All Pages
```http
GET /api/pages
```

**Response:**
```json
[
  {
    "id": "home",
    "title": "Welcome",
    "filename": "home.json"
  },
  {
    "id": "about",
    "title": "About Us",
    "filename": "about.json"
  }
]
```

#### Get Page
```http
GET /api/pages/{id}
```

**Response:**
```json
{
  "id": "home",
  "title": "Welcome",
  "layout": { ... }
}
```

#### Create Page
```http
POST /api/pages
Content-Type: application/json

{
  "title": "New Page",
  "layout": {
    "columns": 2,
    "rows": []
  }
}
```

#### Update Page
```http
PUT /api/pages/{id}
Content-Type: application/json

{
  "title": "Updated Title",
  "layout": { ... }
}
```

#### Delete Page
```http
DELETE /api/pages/{id}
```

#### Upload Image
```http
POST /api/pages/{pageId}/images
Content-Type: multipart/form-data

image: [file]
```

**Response:**
```json
{
  "filename": "img_1234567890.jpg"
}
```

---

## Development

### Prerequisites

- Node.js 16+
- npm
- Git

### Development Workflow

1. **Clone and Install:**
```bash
git clone https://github.com/shalution/IntraVox.git intravox
cd intravox
npm install
```

2. **Start Development Server:**
```bash
npm run watch
```
This watches for changes and rebuilds automatically.

3. **Make Changes:**
- Frontend: Edit files in `src/`
- Backend: Edit files in `lib/`
- Styles: Edit `css/main.css`

4. **Test Changes:**
- Reload Nextcloud in your browser
- Test in both view and edit modes
- Test all widget types

5. **Build for Production:**
```bash
npm run build
```

### Deployment Script

IntraVox includes a deployment script (`deploy.sh`) for easy updates:

```bash
bash deploy.sh
```

This script:
1. Builds the frontend
2. Creates a deployment package
3. Uploads to the server via SSH
4. Restarts the app
5. Runs the setup command

---

## Troubleshooting

### App Not Appearing in Navigation

**Problem:** IntraVox doesn't show in the top navigation bar.

**Solution:**
1. Clear Nextcloud cache: `php occ maintenance:mode --off`
2. Verify app is enabled: `php occ app:list`
3. Check `appinfo/info.xml` for correct `<navigations>` entry

### Shared Folder Not Found

**Problem:** "IntraVox folder not accessible" error.

**Solution:**
1. Verify intravox service account exists: `php occ user:list`
2. Run setup command: `php occ intravox:setup`
3. Check folder exists: Navigate to intravox user's files
4. Verify folder is shared with required groups

### Pages Not Saving

**Problem:** Changes don't persist after saving.

**Solution:**
1. Verify user is in "IntraVox Admins" group: `php occ group:list`
2. Check folder permissions: Shared folder must be writable for admins
3. Verify share permissions: `php occ share:list intravox`
4. Check PHP error log for permission issues
5. Ensure language folder exists (e.g., `/IntraVox/en/`)

### Images Not Displaying

**Problem:** Uploaded images don't show on the page.

**Solution:**
1. Verify language-specific images folder exists (e.g., `/IntraVox/en/images/`)
2. Check file permissions
3. Verify image file was uploaded successfully
4. Check browser console for 404 errors

### JavaScript Errors

**Problem:** Frontend not working correctly.

**Solution:**
1. Clear browser cache
2. Rebuild frontend: `npm run build`
3. Check browser console for errors
4. Verify `js/intravox-main.js` exists and is accessible

---

## Advanced Configuration

### Adding Support for Additional Languages

To add support for more languages beyond the default (nl, en, de, fr):

1. Edit `lib/Service/PageService.php`:
```php
private const SUPPORTED_LANGUAGES = ['nl', 'en', 'de', 'fr', 'es', 'it']; // Add your languages
```

2. Edit `lib/Service/SetupService.php` and add the same languages:
```php
private const SUPPORTED_LANGUAGES = ['nl', 'en', 'de', 'fr', 'es', 'it'];
```

3. Run setup to create the new language folders: `php occ intravox:setup`

### Customizing Group Names

By default, IntraVox uses "IntraVox Admins" and "IntraVox Users" groups. To use different group names:

1. Create your custom groups:
```bash
php occ group:add "Your Admin Group"
php occ group:add "Your User Group"
```

2. Edit `lib/Service/SetupService.php` and modify the share methods, or manually share the folder:
```bash
# Share with full permissions
php occ share:create --type=group --sharedWith="Your Admin Group" --path="/intravox/files/IntraVox" --permissions=31

# Share with read-only permissions
php occ share:create --type=group --sharedWith="Your User Group" --path="/intravox/files/IntraVox" --permissions=1
```

### Multi-Instance Setup

IntraVox supports multiple Nextcloud instances sharing the same codebase:

1. Install IntraVox on each instance
2. Create the "intravox" service account on each: `php occ user:add intravox`
3. Run `php occ intravox:setup` on each
4. Each instance gets its own independent content
5. Content is **not** shared between instances

### Backup Strategy

**Important files to backup:**
- GroupFolder `IntraVox/{language}/*.json` - All page data for each language
- GroupFolder `IntraVox/{language}/navigation.json` - Navigation structure
- GroupFolder `IntraVox/{language}/images/` - Uploaded images
- GroupFolder `IntraVox/{language}/files/` - Uploaded files
- Page-specific folders: `IntraVox/{language}/{pageid}/`

**Backup command:**
```bash
# Find the GroupFolder mount point
GROUPFOLDER_ID=$(php occ groupfolders:list | grep IntraVox | grep -oP '^\d+')

# Scan files to ensure filecache is up to date
php occ groupfolders:scan $GROUPFOLDER_ID

# Manual backup (find the actual data directory path)
DATA_DIR=$(php occ config:system:get datadirectory)
GROUPFOLDER_PATH="$DATA_DIR/__groupfolders/$GROUPFOLDER_ID"
tar -czf intravox-backup-$(date +%Y%m%d).tar.gz $GROUPFOLDER_PATH
```

**Restore procedure:**
1. Extract backup to GroupFolder location
2. Run `php occ groupfolders:scan $GROUPFOLDER_ID` to update filecache
3. Verify permissions are correct via GroupFolders admin interface

### Performance Optimization

**Frontend:**
- Built files are already minified
- Images use lazy loading (`loading="lazy"`)
- Vue components use efficient rendering

**Backend:**
- JSON files are cached by Nextcloud's file system
- Nextcloud Files API provides efficient access
- API responses are minimal (no unnecessary data)
- Language-based folder separation improves performance for large deployments

---

## Support

For technical support, please:

1. Check this guide
2. Review [GitHub Issues](https://github.com/shalution/IntraVox/issues)
3. Contact: rik@shalution.nl

---

**Developed by Rik Dekker - [Shalution](https://shalution.nl)**
