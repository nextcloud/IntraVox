# IntraVox Administrator Guide

This guide provides technical documentation for system administrators who need to install, configure, and maintain IntraVox.

---

## Table of Contents

- [Installation](#installation)
- [Architecture Overview](#architecture-overview)
- [Security Features](#security-features)
- [Storage Structure](#storage-structure)
- [API Documentation](#api-documentation)
- [Development](#development)
- [Troubleshooting](#troubleshooting)
- [Advanced Configuration](#advanced-configuration)

---

## Installation

### System Requirements

- **Nextcloud**: 28 or higher
- **PHP**: 8.0 or higher
- **Node.js**: 16+ and npm (for building frontend)
- **Nextcloud Apps**: GroupFolders app must be installed and enabled

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

#### 5. Setup GroupFolder (Recommended)

Run the setup command to automatically configure the IntraVox groupfolder:

```bash
php occ intravox:setup
```

This command will:
- Create a groupfolder named "IntraVox" (system folder, not linked to any user)
- Grant the admin group full access (read, write, share, delete)
- Create a default homepage (home.json)
- Create subdirectories (images/, documents/)
- Configure the app to use this groupfolder

**Important Notes:**
- The groupfolder is a **system folder**, not linked to any user account
- IntraVox finds the groupfolder by name ("IntraVox"), not by ID
- This makes the installation portable across different servers
- Only groups you explicitly add will have access

#### 6. Configure User Access (Optional)

By default, only the admin group has access. To give other users access:

```bash
# Add a group to the IntraVox groupfolder
php occ groupfolders:group <folder_id> <group_id> <permissions>

# Example: Give "IntraVox users" group read/write access (permission 31 = full access)
php occ groupfolders:group 4 "IntraVox users" 31
```

**Permission values:**
- 1 = Read
- 2 = Update
- 4 = Create
- 8 = Delete
- 16 = Share
- 31 = All permissions

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
- GroupFolders API
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
│       ├── PageService.php         # Business logic, validation, security
│       └── SetupService.php        # GroupFolder setup logic
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
                            PageService.php (validation + security)
                                    ↓
                            GroupFolders API
                                    ↓
                        IntraVox/pages/*.json (storage)
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

- GroupFolders provide built-in ACL (Access Control Lists)
- Administrators control which groups have access
- Users only see content they're authorized to access
- No direct file system access from the frontend

### API Security

- All API endpoints require authenticated Nextcloud session
- CSRF protection via Nextcloud's built-in middleware
- No sensitive data in URLs (all POST/PUT requests)
- Proper HTTP methods (GET for reading, POST/PUT for writing, DELETE for removing)

---

## Storage Structure

IntraVox uses a **GroupFolder** for shared storage:

```
Nextcloud GroupFolders/
└── IntraVox/                       # System folder (not linked to user)
    ├── home.json                   # Default homepage
    ├── about-us.json              # Example page
    ├── team-wiki.json             # Example page
    │
    ├── images/                     # Uploaded images
    │   ├── img_1234567890.jpg
    │   ├── img_0987654321.png
    │   └── company-logo.webp
    │
    └── documents/                  # Uploaded files
        ├── policy.pdf
        ├── handbook.docx
        └── presentation.pptx
```

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

### GroupFolder Not Found

**Problem:** "GroupFolder 'IntraVox' not found" error.

**Solution:**
1. Verify GroupFolders app is enabled: `php occ app:enable groupfolders`
2. Run setup command: `php occ intravox:setup`
3. Check groupfolder exists: `php occ groupfolders:list`

### Pages Not Saving

**Problem:** Changes don't persist after saving.

**Solution:**
1. Check folder permissions: GroupFolder must be writable
2. Verify group membership: User must be in a group with write access
3. Check PHP error log for permission issues
4. Ensure `IntraVox/` folder exists

### Images Not Displaying

**Problem:** Uploaded images don't show on the page.

**Solution:**
1. Verify `IntraVox/images/` folder exists
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

### Custom GroupFolder Name

If you want to use a different groupfolder name:

1. Edit `lib/Service/PageService.php`:
```php
private const GROUPFOLDER_NAME = 'YourCustomName';
```

2. Run setup: `php occ intravox:setup`

### Multi-Instance Setup

IntraVox supports multiple Nextcloud instances sharing the same codebase:

1. Install IntraVox on each instance
2. Run `php occ intravox:setup` on each
3. Each instance gets its own `IntraVox` groupfolder
4. Content is **not** shared between instances

### Backup Strategy

**Important files to backup:**
- `Nextcloud GroupFolders/IntraVox/*.json` - All page data
- `Nextcloud GroupFolders/IntraVox/images/` - Uploaded images
- `Nextcloud GroupFolders/IntraVox/documents/` - Uploaded files

**Backup command:**
```bash
# Using Nextcloud's backup tools
php occ files:scan --path="/IntraVox"

# Manual backup
tar -czf intravox-backup-$(date +%Y%m%d).tar.gz /path/to/nextcloud/data/__groupfolders/IntraVox/
```

### Performance Optimization

**Frontend:**
- Built files are already minified
- Images use lazy loading (`loading="lazy"`)
- Vue components use efficient rendering

**Backend:**
- JSON files are cached by Nextcloud
- GroupFolders provide efficient file access
- API responses are minimal (no unnecessary data)

---

## Support

For technical support, please:

1. Check this guide
2. Review [GitHub Issues](https://github.com/shalution/IntraVox/issues)
3. Contact: rik@shalution.nl

---

**Developed by Rik Dekker - [Shalution](https://shalution.nl)**
