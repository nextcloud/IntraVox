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
- **Service Account**: A dedicated user account named "intravox"

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

#### 5. Create Service Account

IntraVox uses a dedicated service account named "intravox" to store shared content:

```bash
# Create the intravox service account
php occ user:add intravox

# Set a secure password when prompted
```

**Important:** This service account should NOT be used for regular login. It serves only as the owner of the shared IntraVox folder.

#### 6. Setup Shared Folder and Permissions

Run the setup command to automatically configure the IntraVox shared folder:

```bash
php occ intravox:setup
```

This command will:
- Create a folder "IntraVox" in the intravox user's home directory
- Create language subfolders (nl/, en/, de/, fr/) for multi-language support
- Create a default homepage (home.json) in each language folder
- Create subdirectories (images/, files/) for each language
- Share the folder with "IntraVox Admins" group (full permissions)
- Share the folder with "IntraVox Users" group (read-only permissions)

**Access Control:**
- **IntraVox Admins** group: Full access (read, write, delete, share)
- **IntraVox Users** group: Read-only access
- Users must be added to these groups to access IntraVox content

**Creating the required groups:**
```bash
# Create the admin group
php occ group:add "IntraVox Admins"

# Create the users group
php occ group:add "IntraVox Users"

# Add users to groups
php occ group:adduser "IntraVox Admins" admin
php occ group:adduser "IntraVox Users" johndoe
```

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
- Nextcloud Files API
- Nextcloud Sharing API
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
│   │   └── SetupCommand.php        # OCC command: php occ intravox:setup
│   │
│   ├── Controller/
│   │   ├── PageController.php      # Main page rendering
│   │   └── ApiController.php       # REST API endpoints
│   │
│   └── Service/
│       ├── PageService.php         # Business logic, validation, security, multi-language
│       └── SetupService.php        # Shared folder setup and sharing logic
│
├── src/                            # Vue.js frontend source
│   ├── main.js                     # Application entry point
│   ├── App.vue                     # Root component
│   │
│   └── components/
│       ├── PageViewer.vue          # Read-only page display
│       ├── PageEditor.vue          # Drag-and-drop page editor
│       ├── Widget.vue              # Widget renderer (displays widgets)
│       ├── WidgetPicker.vue        # Widget type selector modal
│       ├── WidgetEditor.vue        # Widget configuration modal
│       └── PageListModal.vue       # Page navigation modal
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
├─ PageListModal (Navigation)
│
├─ PageViewer (Display Mode)
│  └─ Widget (x N) - Renders individual widgets
│
└─ PageEditor (Edit Mode)
   ├─ Draggable Rows/Columns
   │  └─ Widget (x N) - Each with edit/delete controls
   │
   ├─ WidgetPicker - Select widget type
   │
   └─ WidgetEditor - Configure widget properties
      ├─ Text Editor (with Markdown toolbar)
      ├─ Image Upload (with size presets)
      ├─ Heading Configuration (H1-H6)
      ├─ Link Editor
      ├─ File Upload
      └─ Divider
```

### Data Flow

```
User Action → Vue Component → API Call (Axios)
                                    ↓
                            ApiController.php
                                    ↓
                            PageService.php (validation + security + language detection)
                                    ↓
                            Nextcloud Files API
                                    ↓
                    intravox user's home/IntraVox/{language}/*.json (storage)
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

- Service account "intravox" owns the shared folder
- Nextcloud sharing system provides access control
- Two predefined groups control access:
  - **IntraVox Admins**: Full permissions (read, write, delete, share)
  - **IntraVox Users**: Read-only permissions
- Administrators add users to these groups to grant access
- Users only see content they're authorized to access
- No direct file system access from the frontend

### API Security

- All API endpoints require authenticated Nextcloud session
- CSRF protection via Nextcloud's built-in middleware
- No sensitive data in URLs (all POST/PUT requests)
- Proper HTTP methods (GET for reading, POST/PUT for writing, DELETE for removing)

---

## Storage Structure

IntraVox uses a **service account's shared folder** for storage, with built-in **multi-language support**:

```
intravox user's home/
└── IntraVox/                       # Shared folder (owned by intravox user)
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
| `text` | `content` | Rich text with Markdown support |
| `heading` | `content`, `level` (1-6) | Page headings (H1-H6) |
| `image` | `src`, `alt`, `width` | Images with aspect ratio preservation |
| `link` | `url`, `text` | External or internal links |
| `file` | `path`, `name` | Embedded file downloads |
| `divider` | - | Visual separator / spacer |

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
- `intravox user home/IntraVox/{language}/*.json` - All page data for each language
- `intravox user home/IntraVox/{language}/images/` - Uploaded images
- `intravox user home/IntraVox/{language}/files/` - Uploaded files
- Page-specific folders: `intravox user home/IntraVox/{language}/{pageid}/`

**Backup command:**
```bash
# Scan files to ensure filecache is up to date
php occ files:scan --path="intravox/files/IntraVox"

# Manual backup (find the actual data directory path)
DATA_DIR=$(php occ config:system:get datadirectory)
tar -czf intravox-backup-$(date +%Y%m%d).tar.gz $DATA_DIR/intravox/files/IntraVox/
```

**Restore procedure:**
1. Extract backup to intravox user's IntraVox folder
2. Run `php occ files:scan --path="intravox/files/IntraVox"` to update filecache
3. Verify permissions are correct

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
