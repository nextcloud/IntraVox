# IntraVox - Intranet Pages for Nextcloud

**Build collaborative intranet pages directly in Nextcloud with a powerful drag-and-drop editor.**

IntraVox brings SharePoint-style page creation to Nextcloud, enabling teams to build intranets, knowledge bases, and collaborative workspaces within their secure Nextcloud environment.

![IntraVox Demo](https://raw.githubusercontent.com/nextcloud/intravox/main/screenshots/IntraVoxDemo.gif)

*See IntraVox in action: drag-and-drop editing, navigation, and responsive design*

---

![IntraVox Homepage](https://raw.githubusercontent.com/nextcloud/intravox/main/screenshots/intravox%20home.png)

*Professional pages with drag-and-drop editing and smart navigation*

![Edit Pages with Drag-and-Drop](https://raw.githubusercontent.com/nextcloud/intravox/main/screenshots/Intravox%20edit.png)

*Visual editor with flexible layouts and intuitive widget management*

![Fully Responsive Design](https://raw.githubusercontent.com/nextcloud/intravox/main/screenshots/Responsive.gif)

*Responsive design across desktop, tablet, and mobile*

---

## Features

### Page Editor

- **Visual Drag-and-Drop Editor** - Create pages without coding. Drag widgets where you need them
- **Flexible Grid System** - Build layouts with 1-5 columns per row
- **Row Background Colors** - Theme-aware colors that adapt to your Nextcloud theme
- **Header Row** - Full-width header section above the main content
- **Side Columns** - Optional left and/or right side columns for additional content
- **Unlimited Rows** - Add as many content rows as needed

### Available Widgets

![Available Widgets](https://raw.githubusercontent.com/nextcloud/intravox/main/screenshots/widgets.png)

*Widget palette with all available content types*

| Widget | Description |
|--------|-------------|
| **Text** | Rich text with full Markdown support (bold, italic, lists, links, tables) |
| **Heading** | H1-H6 headers with customizable styling |
| **Image** | Visual content with flexible sizing, optional clickable links to pages or external URLs |
| **Video** | Embed videos from YouTube, Vimeo, PeerTube, or upload local videos |
| **Links** | Multi-link grid widget (1-5 columns) with Material Design icons |
| **News** | Display pages from a folder as news items with List, Grid, or Carousel layout |
| **Divider** | Visual separator that adapts to row background color |
| **Spacer** | Add empty space between content |

### News Widget

![News Widget Carousel](https://raw.githubusercontent.com/nextcloud/intravox/main/screenshots/Newswidget-carrousel.gif)

*Carousel layout with automatic rotation*

The News Widget displays pages from a selected folder as dynamic news items:

- **Three Layouts**: List, Grid (2-4 columns), or Carousel with autoplay
- **MetaVox Filtering**: Filter pages by metadata fields (when MetaVox is installed)
- **Publication Date Filtering**: Schedule content with publish and expiration dates
- **Configurable Display**: Toggle image, date, and excerpt visibility
- **Sorting Options**: Sort by date modified or title, ascending or descending
- **Source Selection**: Pick any folder as content source

![News Widget Editor](https://raw.githubusercontent.com/nextcloud/intravox/main/screenshots/Newswidget-edit.png)

*News widget configuration with MetaVox filters*

### Collapsible Rows

Create SharePoint-style collapsible sections for better content organization:

- **Toggle Sections**: Click header to expand/collapse row content
- **Custom Titles**: Set descriptive titles for each collapsible section
- **Default State**: Choose whether rows start expanded or collapsed
- **Visual Feedback**: Clear indicators show expandable content

![Collapsible Sections](https://raw.githubusercontent.com/nextcloud/intravox/main/screenshots/Collapsible-sections.png)

*Collapsible rows for organized content sections*

### Table Support

Full table editing in text widgets:
- Insert tables via the toolbar
- Add/remove rows and columns
- Resize columns by dragging
- Tables saved as Markdown for portability

### Navigation

- **Customizable Navigation** - Build your own navigation structure
- **Three Navigation Levels** - Support for deep page hierarchies
- **Two Display Modes** - Choose between dropdown or megamenu style
- **External Links** - Link to external URLs alongside internal pages
- **Page Structure View** - Tree view of all accessible pages
- **Mobile Menu** - Collapsible hamburger menu on mobile devices

![Megamenu Navigation](https://raw.githubusercontent.com/nextcloud/intravox/main/screenshots/megamenu.png)

*Megamenu navigation with all sections visible*

![Page Structure](https://raw.githubusercontent.com/nextcloud/intravox/main/screenshots/pagestructure.png)

*Page structure view for easy content management*

### Engagement

- **Page Reactions** - Users can react to pages with emoji (👍❤️🎉😊🤔👀🚀💯 etc.)
- **Comments** - Full commenting system with threaded replies (1 level deep)
- **Comment Reactions** - React to individual comments with emoji
- **Admin Control** - Enable/disable reactions and comments globally
- **Page-Level Settings** - Override engagement settings per page

### Content Organization

- **Multi-Language Support** - Separate content per language (Dutch, English, German, French)
- **Nested Pages** - Create pages inside other pages for hierarchical organization
- **Footer** - Editable footer section on the homepage
- **Breadcrumb Navigation** - Automatic breadcrumb trail for easy navigation

### Security & Permissions

- **Nextcloud Native Permissions** - Uses GroupFolder ACL for access control
- **Folder-Level Permissions** - Set different permissions per folder/page
- **Permission-Based Filtering** - Navigation only shows pages the user can access
- **Real-Time Permission Checks** - Changes take effect immediately

### Admin Settings

- **Engagement Settings** - Enable/disable page reactions, comments, and comment reactions
- **Publication Settings** - Configure MetaVox date fields for content scheduling
- **Video Embed Domains** - Configure which video platforms are allowed
- **Privacy-First Defaults** - YouTube privacy mode and PeerTube enabled by default
- **Custom Video Servers** - Add your own PeerTube or video hosting domains
- **Demo Data Management** - Install or reinstall demo content per language

### Getting Started

- **Welcome Screen** - New installations show an onboarding guide with clear next steps
- **Quick Setup** - Install demo data with one click to see IntraVox in action
- **Documentation Links** - Direct links to Admin Settings and GitHub documentation

### Export & Import

- **Full Export** - Export entire IntraVox installations to ZIP files
- **Confluence Import** - Migrate from Atlassian Confluence via HTML export
- **MetaVox Integration** - Metadata preserved during export/import
- **Parent Page Selection** - Import into specific locations in your page tree
- **Page Hierarchy** - Folder structure preserved during export/import

### Version History

- **Files App Style UI** - Version history designed to match Nextcloud Files app
- **Current Version Display** - Shows real file metadata (modified time, size, author)
- **Relative Time** - "36 sec. ago", "2 min. ago" formatting like Nextcloud
- **Version Preview** - Click any version to preview its content before restoring
- **One-Click Restore** - Restore previous versions with automatic backup
- **Version Labels** - Add custom labels to mark important versions
- **Auto-Refresh** - Version list updates automatically when page is saved
- **Edit Mode Awareness** - Entering edit mode always shows current version being edited

### Integration

- **Nextcloud Unified Search** - Search pages via Ctrl+K with IntraVox app icon
- **Nextcloud Comments API** - Reactions and comments use native Nextcloud infrastructure
- **MetaVox Integration** - Add metadata to pages and filter News widgets (when MetaVox is installed)
- **Files App Integration** - Pages stored as JSON files in GroupFolder
- **OpenAPI Documentation** - Complete API specification for third-party integration
- **OCS API Viewer** - Interactive API documentation when OCS API Viewer app is installed
- **Responsive Design** - Works on desktop, tablet, and mobile

### Performance

- **Smart Caching** - Intelligent cache refresh reduces unnecessary API calls by 50%
- **localStorage Persistence** - Cache survives browser refresh for instant page loads
- **Lazy Loading** - Sidebar tabs and data load on-demand

### Security

- **Nextcloud Authentication** - All pages require Nextcloud login
- **GroupFolder Permissions** - Native ACL-based access control
- **Path Traversal Protection** - Enhanced path sanitization
- **ZIP Slip Prevention** - Secure ZIP extraction with path validation
- **SVG Sanitization** - Uploaded SVG files are sanitized to prevent XSS
- **Secure Temp Files** - Cryptographically secure filenames with restrictive permissions

---

## Demo Content

IntraVox includes demo content to help you get started quickly. Install demo data directly from the **Admin Settings** panel.

### Installing Demo Data

1. Go to **Nextcloud Admin Settings** → **IntraVox**
2. Choose a language (Nederlands, English, Deutsch, Français)
3. Click **Install** to set up demo content

The demo pages showcase:
- Different page layouts (single column, multi-column, side columns)
- Various widget types (text, headings, images, videos, links, dividers)
- Clickable images linking to pages and external URLs
- Embedded video examples from different platforms
- Navigation structure examples
- Department page organization
- Row background color options

### Reinstalling Demo Data

If you want to reset demo content to its original state:
1. Go to **Admin Settings** → **IntraVox**
2. Click **Reinstall** next to the language
3. Confirm the action (this will delete all existing demo content for that language)

> **Note**: The demo content is fictional and intended only to demonstrate IntraVox's capabilities. Replace it with your organization's actual content.

---

## Use Cases

- **Company Intranets** - Digital workplace with news, resources, and team information
- **Knowledge Bases** - Documentation that's easy to navigate and maintain
- **Team Wikis** - Collaborative knowledge sharing
- **Project Hubs** - Centralized project information and resources
- **Department Portals** - Dedicated spaces for teams to share information

---

## System Requirements

| Component | Minimum | Recommended |
|-----------|---------|-------------|
| Nextcloud | 32+ | 32+ |
| PHP | 8.1+ | 8.2+ |
| PHP memory_limit | 256MB | 512MB |
| GroupFolders app | Required | Required |

> ⚠️ **Important**: The default PHP memory_limit of 128MB is insufficient for IntraVox. Demo data installation requires at least 256MB. Update your `php.ini` if needed.

---

## Installation

1. Install the **GroupFolders** app from Nextcloud App Store
2. Install **IntraVox** from Nextcloud App Store
3. Go to **Admin Settings** → **IntraVox** and install demo data for your preferred language
4. The `IntraVox` GroupFolder and permission groups are created automatically
5. Assign users to the IntraVox groups or configure custom access via GroupFolder ACL

> **Fresh Installation**: On a new Nextcloud instance, simply install IntraVox and click "Install" for any demo language. The GroupFolder, groups, and permissions are set up automatically.

---

## Configuration

### Permission Groups

IntraVox uses Nextcloud's GroupFolder permissions. Recommended group structure:

| Group | Permissions | Description |
|-------|-------------|-------------|
| IntraVox Admins | Full access | Edit navigation, manage all content |
| IntraVox Editors | Read + Write + Create | Create and edit pages |
| IntraVox Users | Read only | View content only |

### Folder Structure

```
IntraVox/
├── en/                          # English content
│   ├── home.json               # Homepage
│   ├── navigation.json         # Navigation configuration
│   ├── footer.json             # Footer content
│   ├── _resources/             # Shared media library (images, videos, SVG)
│   ├── news/                   # News articles folder
│   │   ├── article1/
│   │   │   ├── article1.json   # Article content
│   │   │   └── _media/         # Article-specific media
│   │   └── article2/
│   └── about/                  # Page folder
│       ├── about.json          # Page content
│       └── _media/             # Page-specific media
├── nl/                          # Dutch content
│   └── ...
├── de/                          # German content
│   └── ...
└── fr/                          # French content
    └── ...
```

### Media Management

- **Page Media** (`_media/`): Images and videos specific to a single page
- **Shared Library** (`_resources/`): Reusable media across all pages in a language
- **Supported Formats**: JPEG, PNG, GIF, WebP, SVG (sanitized), MP4, WebM, OGG

---

## Technical Details

### Technology Stack

| Component | Technology |
|-----------|------------|
| Frontend | Vue.js 3, Nextcloud Vue components |
| Backend | PHP 8.x, Nextcloud AppFramework |
| Storage | Nextcloud GroupFolders (JSON files) |
| Authorization | Nextcloud native permissions + ACL |
| Build | Webpack, npm |

### Page Storage Format

Pages are stored as JSON files with the following structure:

```json
{
    "uniqueId": "page-uuid-v4",
    "title": "Page Title",
    "language": "en",
    "layout": {
        "columns": 1,
        "rows": [...],
        "headerRow": {...},
        "sideColumns": {...}
    }
}
```

### API Endpoints

IntraVox provides a complete REST API documented with OpenAPI 3.1. Install the [OCS API Viewer](https://apps.nextcloud.com/apps/ocs_api_viewer) app for interactive documentation.

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/pages` | List all accessible pages |
| GET | `/api/pages/tree` | Get page tree structure |
| GET | `/api/pages/{id}` | Get page content |
| POST | `/api/pages` | Create new page |
| PUT | `/api/pages/{id}` | Update page |
| DELETE | `/api/pages/{id}` | Delete page |
| GET | `/api/pages/{id}/breadcrumb` | Get breadcrumb navigation |
| GET | `/api/pages/{id}/versions` | Get page version history |
| POST | `/api/pages/{id}/versions/{timestamp}` | Restore a version |
| GET | `/api/pages/{id}/media/list` | List page media files |
| POST | `/api/pages/{id}/media` | Upload media file |
| GET | `/api/navigation` | Get navigation structure |
| POST | `/api/navigation` | Save navigation |
| GET | `/api/footer` | Get footer content |
| POST | `/api/footer` | Save footer |
| GET | `/api/news` | Get news items with filtering |
| GET | `/api/pages/{id}/comments` | Get comments for a page |
| POST | `/api/pages/{id}/comments` | Add a comment |
| GET | `/api/pages/{id}/reactions` | Get page reactions |
| POST | `/api/pages/{id}/reactions/{emoji}` | Add page reaction |
| GET | `/api/settings/engagement` | Get engagement settings |
| GET | `/api/settings/publication` | Get publication settings |
| GET | `/api/metavox/status` | Check MetaVox availability |
| GET | `/api/metavox/fields` | Get MetaVox field definitions |
| GET | `/api/search` | Search pages |
| GET | `/api/export/languages` | Get exportable languages |
| GET | `/api/export/language/{lang}` | Export language content |
| POST | `/api/import/zip` | Import ZIP archive |

For complete API documentation, see the `openapi.json` file or use OCS API Viewer.

---

## Development

### Building from Source

```bash
# Install dependencies
npm install

# Development build with watch
npm run dev

# Production build
npm run build
```

### Deployment

```bash
# Deploy to server
./deploy.sh
```

---

## Documentation

- [Authorization Guide](docs/AUTHORIZATION.md) - User and administrator permissions guide
- [Architecture](docs/ARCHITECTURE.md) - Technical architecture documentation
- [News Widget Guide](docs/NEWS_WIDGET.md) - How to use the News widget
- [Export/Import Guide](docs/EXPORT-IMPORT.md) - Export and import content
- [Engagement User Guide](docs/ENGAGEMENT_GUIDE.md) - How to use reactions and comments
- [Engagement Admin Guide](docs/ENGAGEMENT_ADMIN.md) - Configure engagement settings
- [Engagement Architecture](docs/ENGAGEMENT_ARCHITECTURE.md) - Technical engagement details
- [API Documentation](openapi.json) - OpenAPI 3.1 specification

---

## Troubleshooting

### "Could not find resource intravox/js/intravox-main.js to load"

This error typically occurs after updating IntraVox and is caused by Nextcloud's resource cache not being refreshed. To fix it, run one of the following commands on your server:

```bash
# Option 1: Run maintenance repair (recommended)
sudo -u www-data php occ maintenance:repair

# Option 2: Disable and re-enable the app
sudo -u www-data php occ app:disable intravox
sudo -u www-data php occ app:enable intravox
```

After running either command, refresh your browser (Ctrl+F5) to clear the browser cache.

---

## License

AGPL-3.0

---

## Author

**Rik Dekker** - [Shalution](https://shalution.nl)

---

## Acknowledgments

Built with:
- [Nextcloud](https://nextcloud.com) - Open-source content collaboration platform
- [Vue.js](https://vuejs.org) - Progressive JavaScript framework
- [SortableJS](https://sortablejs.github.io/Sortable/) - Drag-and-drop functionality
- [TipTap](https://tiptap.dev) - Rich text editor
