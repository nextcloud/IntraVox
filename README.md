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
| **Divider** | Visual separator that adapts to row background color |

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
- **Video Embed Domains** - Configure which video platforms are allowed
- **Privacy-First Defaults** - YouTube privacy mode and PeerTube enabled by default
- **Custom Video Servers** - Add your own PeerTube or video hosting domains
- **Demo Data Management** - Install or reinstall demo content per language

### Getting Started

- **Welcome Screen** - New installations show an onboarding guide with clear next steps
- **Quick Setup** - Install demo data with one click to see IntraVox in action
- **Documentation Links** - Direct links to Admin Settings and GitHub documentation

### Integration

- **Nextcloud Unified Search** - Search pages via Ctrl+K with IntraVox app icon
- **Nextcloud Comments API** - Reactions and comments use native Nextcloud infrastructure
- **MetaVox Integration** - Add metadata to pages (when MetaVox app is installed)
- **Files App Integration** - Pages stored as JSON files in GroupFolder
- **Responsive Design** - Works on desktop, tablet, and mobile

### Performance

- **Smart Caching** - Intelligent cache refresh reduces unnecessary API calls by 50%
- **localStorage Persistence** - Cache survives browser refresh for instant page loads
- **Lazy Loading** - Sidebar tabs and data load on-demand

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
│   ├── _media/                 # Shared media folder (images, videos)
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

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/pages` | List all accessible pages |
| GET | `/api/pages/{id}` | Get page content |
| POST | `/api/pages` | Create new page |
| PUT | `/api/pages/{id}` | Update page |
| DELETE | `/api/pages/{id}` | Delete page |
| GET | `/api/navigation` | Get navigation structure |
| POST | `/api/navigation` | Save navigation |
| GET | `/api/pages/{id}/comments` | Get comments for a page |
| POST | `/api/pages/{id}/comments` | Add a comment |
| GET | `/api/pages/{id}/reactions` | Get page reactions |
| POST | `/api/pages/{id}/reactions/{emoji}` | Add page reaction |
| GET | `/api/settings/engagement` | Get engagement settings |

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
- [Engagement User Guide](docs/ENGAGEMENT_GUIDE.md) - How to use reactions and comments
- [Engagement Admin Guide](docs/ENGAGEMENT_ADMIN.md) - Configure engagement settings
- [Engagement Architecture](docs/ENGAGEMENT_ARCHITECTURE.md) - Technical engagement details

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
