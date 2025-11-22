# Pages in IntraVox

This document describes how pages are created, saved, edited, and stored in IntraVox.

## Page Lifecycle

### Creating a New Page

When a user creates a new page in IntraVox:

1. **User Input**: The user provides a page title through the "New Page" dialog
2. **Slug Generation**: The title is converted to a URL-friendly slug (e.g., "Welcome Page" → "welcome-page")
   - Diacritics are preserved and converted to URL-safe characters
   - Spaces become hyphens
   - Special characters are removed
   - Duplicate slugs automatically get numbered suffixes (e.g., "test", "test-2", "test-3")
3. **Unique ID Assignment**: A UUID v4 is generated using `crypto.randomUUID()` for the page's `uniqueId` field
   - Format: `page-xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx`
   - Guarantees uniqueness across server migrations and multi-server scenarios
   - 340 undecillion possible combinations prevent conflicts
   - Used in URLs for permanent, collision-free page identification
4. **Auto Edit Mode**: The new page automatically opens in edit mode for immediate content creation
5. **File System Creation**: A dedicated folder structure is created (see Folder Structure section)

### Page URLs and Navigation

IntraVox uses uniqueId-based URLs for reliable, permanent page identification:

**URL Format**:
- **Hash URLs**: `https://your-nextcloud/apps/intravox#page-abc-123-...`
  - Used for client-side navigation within the app
  - Changes instantly without page reload
  - Browser back/forward buttons work correctly

- **Direct URLs**: `https://your-nextcloud/apps/intravox/p/page-abc-123-...`
  - Shareable links for external use
  - Server renders page with Open Graph meta tags (limited in Nextcloud)
  - Automatically converts to hash URL after page load

**URL Benefits**:
- **Permanent**: URLs remain valid even if page title/slug changes
- **Migration-Safe**: No conflicts when merging different IntraVox installations
- **Bookmarkable**: Browser bookmarks always point to correct page
- **Shareable**: Safe to share in emails, documents, chat messages

**Legacy Page Support**:
- Pages without uniqueId automatically get one on first load
- Old URLs using page ID still work (automatic fallback)
- System seamlessly upgrades legacy pages to new format

### Site Navigation

IntraVox features a comprehensive navigation system with support for three hierarchy levels:

**Navigation Types**:
1. **Dropdown (Cascading)**: Standard dropdown menus with nested submenus
   - Uses Nextcloud Vue NcActions components
   - Supports up to 3 levels of nesting
   - Click to expand/collapse menu items
   - Ideal for structured, categorized content

2. **Megamenu (Grid-based)**: Large dropdown with grid layout
   - Displays all submenu items in a grid
   - Hover to open, shows all options at once
   - Best for sites with many navigation items
   - Visual overview of all sections

**Hierarchy Levels**:
- **Level 1**: Top-level navigation items (e.g., "About", "Services")
- **Level 2**: Sub-items under main categories (e.g., "Our Team", "Our History")
- **Level 3**: Detailed pages under sub-categories (e.g., "CEO", "Board Members")

**Visual Hierarchy**:
- Different font sizes and weights distinguish levels
- Indentation on mobile menu (32px for level 2, 56px for level 3)
- Lighter text colors for deeper levels
- Collapsible items with chevron indicators

**Mobile Navigation**:
- Hamburger menu with NcActions component
- All three levels fully accessible
- Touch-friendly tap-to-expand interface
- Visual indentation for clear hierarchy

**Navigation Editor**:
- Drag-and-drop interface for reordering items
- Add links to IntraVox pages or external URLs
- Configure target (same window or new tab)
- Choose between dropdown and megamenu styles
- Real-time preview of navigation structure

**Nextcloud Integration**:
- Follows Nextcloud design standards
- Uses Nextcloud color variables for theming
- Consistent with Nextcloud Files and other apps
- Responsive design for all screen sizes

### Editing a Page

Pages can be edited through the IntraVox interface:

1. **Edit Mode Activation**: Users click the "Edit Page" button or are automatically placed in edit mode after creating a page
2. **WYSIWYG Editor**: Content is edited using a TipTap-based rich text editor
   - Users see formatted content (bold, italic, headings, lists, etc.)
   - All formatting is done via toolbar buttons
   - No raw markdown syntax is visible to users
3. **Real-time Changes**: Edits are held in memory until explicitly saved
4. **Widget Management**: Users can add, remove, and rearrange widgets using drag-and-drop
5. **Layout Control**: Users can modify the grid layout, add/remove rows, and set background colors

### Saving a Page

When a user saves a page:

1. **Markdown Conversion**: HTML content from the TipTap editor is converted to markdown format
   - Uses `htmlToMarkdown()` utility function
   - Preserves formatting: headings, bold, italic, underline, strikethrough, lists, links
   - Cleans up excessive whitespace and normalizes line breaks
2. **JSON Structure**: Page data is serialized to JSON format containing:
   - Page metadata (id, uniqueId, title, language)
   - Layout structure (rows, columns, background colors)
   - Widget content (stored as markdown in the `content` field)
3. **File Write**: JSON is written to `<page-slug>/<page-slug>.json` in the language-specific folder
4. **File Cache Update**: Nextcloud's file scanner updates the cache for immediate visibility in Files app

## Folder Structure

IntraVox uses a hierarchical folder structure within Nextcloud Files for organizing pages:

### Root Structure

```
IntraVox/
├── nl/                 # Dutch pages
├── en/                 # English pages
├── de/                 # German pages
└── fr/                 # French pages
```

### Language-Specific Folders

**CRITICAL ARCHITECTURE DECISION**: Each language has its own top-level folder containing all pages for that language. This separation is fundamental to IntraVox's architecture and must be respected in:
- Every deployment operation
- All import/export commands
- Frontend language detection
- API endpoints

Each language has its own folder containing all pages for that language. This enables:

- Language-specific content and navigation
- Clean separation between translations
- Easy content management per language
- Isolated namespaces preventing content conflicts between languages

**IMPORTANT**: When deploying demo data or importing pages:
1. Always verify the target language folder before running `occ intravox:import`
2. The import command should target the specific language folder: `occ intravox:import /path/to/nl/`
3. Never deploy content to the wrong language folder (e.g., Dutch content to `en/`)
4. Each language folder is completely independent and self-contained

### Page Folders

Within each language folder, each page has its own dedicated folder:

```
IntraVox/
└── nl/
    ├── home.json              # Home page at root level
    ├── images/                # Home page images (no emoji)
    │   ├── header-logo.png
    │   └── banner.jpg
    ├── welcome/
    │   ├── welcome.json       # Page structure and content
    │   └── images/            # Page-specific images folder
    │       ├── team-photo.jpg
    │       └── office.jpg
    ├── about-us/
    │   ├── about-us.json
    │   └── images/
    └── team/                  # Parent page with nested structure
        ├── team.json
        ├── images/
        └── sales/             # Nested child page
            ├── sales.json
            └── images/
```

### Folder Creation

When a new page is created:

1. **Language Folder**: Created during IntraVox setup (if not already exists)
2. **Page Folder**: Created using the page slug (e.g., `/nl/welcome/`)
3. **Images Subfolder**: Automatically created inside the page folder (e.g., `/nl/welcome/images/`)
   - Each page has its own isolated images folder
   - Images are stored using unique filenames to prevent conflicts
4. **Nested Structure**: Pages can be created inside other pages for hierarchical organization
5. **Home Page Special Case**: The home page is stored at the root level (`/nl/home.json`) with images in `/nl/images/`

### Benefits of This Structure

1. **Human-Readable**: Folder names match page titles (slugified)
2. **Easy Navigation**: Users can browse pages through Nextcloud Files app
3. **Clean URLs**: URLs reflect the folder structure (e.g., `/apps/intravox#/welcome`)
4. **Asset Organization**: Each page has its own images folder for isolated asset management
5. **Version Control Friendly**: Clear folder structure works well with backup and sync tools
6. **Migration Safe**: UUID v4 uniqueIds prevent conflicts when merging IntraVox installations

## Content Storage Format

### JSON Structure

Pages are stored as JSON files with the following structure:

```json
{
  "id": "welcome",
  "uniqueId": "page-550e8400-e29b-41d4-a716-446655440000",
  "title": "Welcome",
  "language": "nl",
  "created": 1731099600,
  "modified": 1731099600,
  "layout": {
    "columns": 2,
    "rows": [
      {
        "columns": 1,
        "backgroundColor": "var(--color-primary-element)",
        "widgets": [
          {
            "type": "heading",
            "column": 1,
            "order": 0,
            "content": "Welcome to IntraVox",
            "level": 1
          },
          {
            "type": "text",
            "column": 1,
            "order": 1,
            "content": "This is **markdown** content with _formatting_."
          },
          {
            "type": "image",
            "column": 1,
            "order": 2,
            "src": "img_691b0c546a8e81.34229105.jpg",
            "alt": "Description",
            "width": "800px",
            "objectFit": "cover"
          },
          {
            "type": "links",
            "column": 1,
            "order": 3,
            "columns": 3,
            "items": [
              {
                "title": "Documentation",
                "url": "https://docs.example.com",
                "icon": "book-open",
                "target": "_blank"
              }
            ]
          },
          {
            "type": "divider",
            "column": 1,
            "order": 4,
            "style": "solid",
            "color": "#cccccc",
            "height": "2px"
          }
        ]
      }
    ]
  }
}
```

### Available Widget Types

IntraVox currently supports 5 widget types:

#### 1. Text Widget
Rich text content with full Markdown support.

```json
{
  "type": "text",
  "column": 1,
  "order": 0,
  "content": "**Bold**, _italic_, [links](https://example.com), lists, tables, and more"
}
```

**Features**:
- Full Markdown syntax support (bold, italic, underline, strikethrough)
- Lists (ordered, unordered, task lists)
- Links and blockquotes
- Tables and code blocks
- Inline editing with TipTap WYSIWYG editor
- Automatically converts to/from Markdown when saving

#### 2. Heading Widget
Structured headings from H1 to H6 for content hierarchy.

```json
{
  "type": "heading",
  "column": 1,
  "order": 0,
  "content": "Section Title",
  "level": 2
}
```

**Properties**:
- `level`: 1-6 (H1 through H6)
- `content`: Plain text heading
- Responsive font sizing
- Proper semantic HTML structure

#### 3. Image Widget
Display images with flexible sizing and positioning options.

```json
{
  "type": "image",
  "column": 1,
  "order": 0,
  "src": "filename.jpg",
  "alt": "Description",
  "width": "800px",
  "objectFit": "cover",
  "objectPosition": "center center"
}
```

**Properties**:
- `src`: Image filename (stored in page's 📷 images folder)
- `alt`: Alternative text for accessibility
- `width`: Display width (300px, 500px, 800px, full, or custom)
- `objectFit`: How image fits container (cover, contain, auto)
- `objectPosition`: Image alignment within container
- Served via API: `/apps/intravox/api/pages/{pageId}/images/{filename}`

#### 4. Links Widget
Grid of clickable links with Material Design icons.

```json
{
  "type": "links",
  "column": 1,
  "order": 0,
  "columns": 3,
  "items": [
    {
      "title": "Link Title",
      "url": "https://example.com",
      "icon": "account-group",
      "target": "_blank"
    }
  ]
}
```

**Properties**:
- `columns`: Grid columns (1-5)
- `items[]`: Array of link objects
  - `title`: Link text
  - `url`: Target URL (internal IntraVox page or external)
  - `icon`: Material Design icon name
  - `target`: `_blank` for new tab, `_self` for same window
- Responsive grid layout
- Hover effects and visual feedback

#### 5. Divider Widget
Visual separator between content sections.

```json
{
  "type": "divider",
  "column": 1,
  "order": 0,
  "style": "solid",
  "color": "#cccccc",
  "height": "2px"
}
```

**Properties**:
- `style`: Line style (solid, dashed, dotted)
- `color`: Border color (hex or CSS variable)
- `height`: Line thickness
- Full-width horizontal rule
- Customizable styling

### Navigation Structure (navigation.json)

Each language folder contains a `navigation.json` file that defines the site navigation menu:

```json
{
  "type": "megamenu",
  "items": [
    {
      "id": "home",
      "title": "Home",
      "pageId": "home",
      "url": null,
      "target": null,
      "children": []
    },
    {
      "id": "about",
      "title": "About Us",
      "pageId": "about-us",
      "url": null,
      "target": null,
      "children": [
        {
          "id": "team",
          "title": "Our Team",
          "pageId": "team",
          "url": null,
          "target": null,
          "children": [
            {
              "id": "management",
              "title": "Management",
              "pageId": "team/management",
              "url": null,
              "target": null,
              "children": []
            }
          ]
        }
      ]
    },
    {
      "id": "external-link",
      "title": "External Resource",
      "pageId": null,
      "url": "https://example.com",
      "target": "_blank",
      "children": []
    }
  ]
}
```

**Navigation Types**:
- `"megamenu"`: Large dropdown with grid layout, shows all items at once
- `"dropdown"`: Standard cascading dropdown menus

**Navigation Item Properties**:
- `id`: Unique identifier for the menu item
- `title`: Display text in navigation menu
- `pageId`: Reference to IntraVox page (for internal links)
- `url`: External URL (for external links, pageId should be null)
- `target`: `"_blank"` for new tab, `null` or `"_self"` for same window
- `children[]`: Nested navigation items (supports 3 levels)

**Hierarchy Levels**:
- **Level 1**: Top-level navigation items
- **Level 2**: Sub-items under main categories
- **Level 3**: Detailed pages under sub-categories

### Markdown in JSON

Content within text widgets is stored as markdown:

- **Storage Format**: Clean, readable markdown text
- **Display Format**: Converted to HTML and rendered as WYSIWYG
- **Supported Syntax**: Headings, bold, italic, underline, strikethrough, lists, links
- **Extended Features**: Tables, code blocks, task lists, blockquotes, horizontal rules

### Why Markdown?

1. **Nextcloud Compatibility**: Can be viewed/edited with Nextcloud Text app
2. **Human-Readable**: Easy to read in raw format
3. **Version Control**: Clean diffs in version history
4. **Future-Proof**: Standard format, easily portable
5. **Lightweight**: Smaller file sizes compared to HTML

## Image Handling

### Upload Process

1. **Image Selection**: User uploads image via image widget
2. **File Storage**: Image saved to `<page-folder>/images/<filename>`
3. **Widget Reference**: Widget stores only the filename (not full path)
4. **Serving**: Images served via IntraVox API: `/apps/intravox/api/pages/{pageId}/images/{filename}`

### Image Widget Properties

- **Filename**: Unique filename generated with `uniqid()` prefix to prevent conflicts
- **Display Options**: Size (cover, contain, auto), alignment, object position
- **Responsive**: CSS handles responsive sizing
- **Security**: Images served only for pages user has access to
- **Storage Location**: All images stored in page-specific `images` folder

## File Management Integration

IntraVox integrates seamlessly with Nextcloud Files:

1. **Immediate Visibility**: File cache scanner ensures new pages appear instantly in Files app
2. **Standard File Operations**: Users can move, copy, delete page folders via Files app (not recommended)
3. **Sharing**: Page folders can be shared using standard Nextcloud sharing features (advanced use case)
4. **Search**: Page content is indexed and searchable through Nextcloud's unified search system

## Technical Implementation

### Key Files

- **`lib/Service/PageService.php`**: Backend service handling page CRUD operations
- **`lib/Service/SetupService.php`**: Groupfolder management
- **`lib/Controller/ApiController.php`**: API endpoints for pages and images
- **`lib/Search/PageSearchProvider.php`**: Nextcloud unified search integration
- **`intravox-deploy/src/App.vue`**: Frontend page creation and slug generation
- **`intravox-deploy/src/utils/markdownSerializer.js`**: Bidirectional markdown ↔ HTML conversion
- **`intravox-deploy/src/components/InlineTextEditor.vue`**: WYSIWYG editor with markdown storage
- **`intravox-deploy/src/components/Widget.vue`**: Widget rendering with markdown display
- **`intravox-deploy/src/components/WidgetPicker.vue`**: Widget type selection interface
- **`intravox-deploy/src/components/Navigation.vue`**: Site navigation (dropdown/megamenu)
- **`intravox-deploy/src/components/Footer.vue`**: Editable footer component

### Security Measures

1. **Content Sanitization**: DOMPurify sanitizes HTML output from markdown
2. **Allowed Tags**: Whitelist of safe HTML tags for rendering
3. **Background Color Validation**: Only whitelisted CSS variables allowed for row backgrounds
4. **Path Validation**: Prevents directory traversal in file operations
5. **Access Control**: Nextcloud's permission system enforces page access rights

## Migration Considerations

### Upgrading from Older Versions

- **Pre-0.2.5**: Pages used technical IDs; migration command creates slug-based folders
- **Pre-0.2.6**: Content stored as HTML; markdown storage preserves existing content compatibility

### Server Migrations

UUID v4 `uniqueId` field ensures:

- No conflicts when merging multiple IntraVox installations
- Safe migrations between Nextcloud servers
- Future support for cross-page references and links

### Backup and Restore

Standard Nextcloud backup procedures apply:

1. **Full Backup**: Include entire `IntraVox/` folder in data directory
2. **Database Backup**: Include IntraVox tables (if any metadata stored)
3. **Restore**: Copy folder structure back and trigger file scanner

## Technical Documentation

For detailed technical implementation information about page loading, breadcrumb generation, and image handling, see:

**[TECHNICAL.md](TECHNICAL.md)** - Technical deep dive into page and image loading systems
- Page loading implementation and bug fixes (v0.4.27)
- Breadcrumb generation algorithms
- Image loading with caching strategies
- Common issues and troubleshooting
- Performance optimization techniques
- Testing and debugging procedures
