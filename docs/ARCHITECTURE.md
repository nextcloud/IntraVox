# IntraVox Architecture

IntraVox is a modern intranet application for Nextcloud that provides SharePoint-like content management capabilities. This document describes the technical architecture.

## System Overview

```
+------------------------------------------------------------------+
|                       Nextcloud Server                            |
|  +--------------------------------------------------------------+ |
|  |                       IntraVox App                           | |
|  |  +----------------+  +----------------+  +----------------+  | |
|  |  |   Vue.js SPA   |  |  PHP Backend   |  |  GroupFolder   |  | |
|  |  |   (Frontend)   |->|  (API + Logic) |->|  Integration   |  | |
|  |  +----------------+  +----------------+  +----------------+  | |
|  +--------------------------------------------------------------+ |
|                              |                                    |
|                              v                                    |
|  +--------------------------------------------------------------+ |
|  |                   IntraVox GroupFolder                       | |
|  |  +------------+  +------------+  +------------------------+  | |
|  |  |   Pages    |  | Navigation |  | Images & Attachments   |  | |
|  |  |   (JSON)   |  |   (JSON)   |  |       (files)          |  | |
|  |  +------------+  +------------+  +------------------------+  | |
|  +--------------------------------------------------------------+ |
+------------------------------------------------------------------+
```

## Technology Stack

| Layer | Technology |
|-------|------------|
| Frontend | Vue.js 3, Vue Router, Pinia (state management) |
| Backend | PHP 8.x, Nextcloud App Framework |
| Storage | Nextcloud Files API, GroupFolders |
| Permissions | GroupFolder ACL, Nextcloud Permissions |
| Build | Webpack, npm |
| i18n | Nextcloud L10N, Transifex |

## Directory Structure

```
intravox/
├── appinfo/
│   ├── info.xml           # App metadata
│   └── routes.php         # API route definitions
├── css/
│   └── *.css              # Stylesheets
├── img/
│   └── *.svg              # App icons
├── js/
│   └── *.js               # Compiled JavaScript (build output)
├── l10n/
│   ├── *.js               # Compiled translations
│   └── *.json             # Translation source files
├── lib/
│   ├── Controller/        # API Controllers
│   │   ├── ApiController.php
│   │   ├── FooterController.php
│   │   ├── NavigationController.php
│   │   └── PageController.php
│   ├── Service/           # Business Logic
│   │   ├── FooterService.php
│   │   ├── NavigationService.php
│   │   ├── PageService.php
│   │   ├── PermissionService.php
│   │   └── SetupService.php
│   └── AppInfo/
│       └── Application.php
├── src/
│   ├── components/        # Vue components
│   ├── views/             # Page views
│   ├── stores/            # Pinia stores
│   ├── App.vue            # Root component
│   └── main.js            # Entry point
├── templates/
│   └── index.php          # Main template
└── demo-data/
    ├── nl/                # Dutch demo content
    ├── en/                # English demo content
    ├── de/                # German demo content
    └── fr/                # French demo content
```

## Core Components

### Backend Services

#### PageService
Handles page CRUD operations:
- Load pages from JSON files
- Save page content
- Manage page hierarchy (nested pages)
- Image and attachment handling

#### NavigationService
Manages the navigation structure:
- Load/save navigation configuration
- Support for megamenu and dropdown navigation types
- Multi-level navigation items

#### FooterService
Handles footer content per language.

#### PermissionService
Integrates with GroupFolder permissions:
- Read permissions from GroupFolder ACL
- Check user access to specific paths
- Filter content based on permissions

#### SetupService
Handles initial app setup:
- Creates IntraVox GroupFolder if not exists
- Initializes folder structure

### Frontend Components

#### App.vue
Main application shell with:
- Navigation component
- Router view for page content
- Footer component

#### Navigation.vue
Renders the navigation menu:
- Megamenu support
- Dropdown menus
- Language switcher
- Mobile responsive

#### PageView.vue
Displays page content:
- Renders widgets (text, images, links, etc.)
- Edit mode with drag-and-drop
- Row and column layouts

#### PageEditor.vue
WYSIWYG page editor:
- Widget palette
- Drag-and-drop widget placement
- Real-time preview
- Save/cancel functionality

## Data Model

### Page Structure (JSON)

```json
{
  "uniqueId": "page-uuid-here",
  "title": "Page Title",
  "language": "en",
  "layout": {
    "columns": 1,
    "rows": [
      {
        "columns": 2,
        "backgroundColor": "",
        "widgets": [
          {
            "type": "heading",
            "column": 1,
            "order": 1,
            "content": "Welcome",
            "level": 1
          },
          {
            "type": "text",
            "column": 1,
            "order": 2,
            "content": "Page content here..."
          }
        ]
      }
    ],
    "sideColumns": {
      "left": { "enabled": false, "widgets": [] },
      "right": { "enabled": false, "widgets": [] }
    }
  }
}
```

### Widget Types

| Type | Description |
|------|-------------|
| heading | H1-H6 headings |
| text | Rich text content (Markdown) |
| image | Image with alt text, optional clickable link (internal page or external URL) |
| video | Embedded video from YouTube, Vimeo, PeerTube, or local upload |
| links | Link collection (cards or list) |
| divider | Horizontal separator |

### Navigation Structure (JSON)

```json
{
  "type": "megamenu",
  "items": [
    {
      "id": "nav_home",
      "title": "Home",
      "uniqueId": "page-uuid",
      "url": null,
      "target": null,
      "children": []
    }
  ]
}
```

## API Endpoints

### Pages
- `GET /api/page/{uniqueId}` - Get page by ID
- `PUT /api/page/{uniqueId}` - Update page
- `POST /api/page` - Create new page
- `DELETE /api/page/{uniqueId}` - Delete page
- `GET /api/pages/{language}` - List pages for language

### Navigation
- `GET /api/navigation/{language}` - Get navigation
- `PUT /api/navigation/{language}` - Update navigation

### Footer
- `GET /api/footer/{language}` - Get footer
- `PUT /api/footer/{language}` - Update footer

### Setup
- `GET /api/setup` - Check setup status
- `POST /api/setup` - Run setup

## File Storage

All content is stored in the IntraVox GroupFolder:

```
IntraVox/
├── {language}/
│   ├── navigation.json
│   ├── footer.json
│   ├── home.json
│   ├── _media/
│   │   └── *.jpg, *.png, *.mp4, ...
│   └── {section}/
│       ├── {page}.json
│       ├── _media/
│       └── {subpage}/
│           └── ...
```

The `_media/` folder stores images, videos, and other media files. Local video uploads are stored here alongside images.

### Benefits of File-based Storage
- Version control through Nextcloud versioning
- Easy backup and migration
- No database tables required
- Content editable via Nextcloud Files app
- Integrates with Nextcloud sharing

## Multi-language Support

IntraVox supports multiple languages:
- Each language has its own folder structure
- Navigation and footer are per-language
- Pages can link to translations via uniqueId
- UI translations via Nextcloud L10N system

## Permission Model

See [AUTHORIZATION.md](AUTHORIZATION.md) for detailed permission documentation.

Key points:
- Uses GroupFolder permissions
- Supports ACL for fine-grained control
- Permissions checked at API and UI level
- Navigation filtered based on access

## Build Process

```bash
# Install dependencies
npm install

# Development build with watch
npm run dev

# Production build
npm run build

# Create release tarball
./create-release.sh
```

## Deployment

See README.md for installation instructions. Key steps:
1. Install via Nextcloud App Store or manually
2. Enable the app
3. GroupFolder "IntraVox" is created automatically
4. Add groups to the GroupFolder
5. Import demo data or start creating pages

## Integration Points

### MetaVox Integration
IntraVox can integrate with MetaVox for enhanced navigation:
- Respects MetaVox department structure
- Uses MetaVox nav color settings
- Syncs navigation styles

### GroupFolders
- Automatic GroupFolder creation
- ACL permission reading
- File storage within GroupFolder

### Nextcloud Features
- Uses Nextcloud theming (colors, logo)
- Integrates with Nextcloud Files
- Supports Nextcloud versioning
- Uses Nextcloud authentication
