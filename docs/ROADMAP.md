# IntraVox Roadmap

This document describes planned features and improvements for IntraVox.

---

## 1. Export/Import Functionality

**Status**: Planned
**Priority**: High

### Description
Full export and import of IntraVox content per language, including pages, media, and engagement data.

### Export Format (ZIP)
```
intravox-export-nl-2024-12-12.zip
├── manifest.json                 # Metadata about the export
├── pages/                        # All page content
│   ├── home.json
│   ├── navigation.json
│   ├── footer.json
│   ├── _media/                   # Root media folder
│   │   ├── hero-team.jpg
│   │   └── dashboard.jpg
│   ├── news/
│   │   ├── news.json
│   │   ├── _media/
│   │   │   └── news.jpg
│   │   └── events/
│   │       ├── events.json
│   │       └── _media/
│   └── contact/
│       ├── contact.json
│       └── _media/
└── engagement.json               # Comments and reactions
```

### manifest.json
```json
{
  "exportVersion": "1.0",
  "exportDate": "2024-12-12T10:30:00Z",
  "exportedBy": "admin",
  "language": "nl",
  "appVersion": "0.7.0",
  "pageCount": 15,
  "mediaCount": 42,
  "commentCount": 128,
  "reactionCount": 256
}
```

### engagement.json
```json
{
  "pages": [
    {
      "uniqueId": "page-abc-123",
      "pageReactions": {
        "👍": ["john", "jane"],
        "❤️": ["anna"]
      },
      "comments": [
        {
          "id": "12345",
          "userId": "john",
          "displayName": "John Doe",
          "message": "Great article!",
          "createdAt": "2024-12-01T10:00:00Z",
          "reactions": {
            "👍": ["jane", "anna"]
          },
          "replies": [
            {
              "id": "12346",
              "userId": "jane",
              "displayName": "Jane Smith",
              "message": "Agreed!",
              "createdAt": "2024-12-01T10:15:00Z",
              "reactions": {}
            }
          ]
        }
      ]
    }
  ],
  "users": {
    "john": "John Doe",
    "jane": "Jane Smith",
    "anna": "Anna Johnson"
  }
}
```

### Features
- Export/import per language
- All JSON files and _media folders
- Comments and reactions with displayNames
- Import modes: Merge (keep existing) or Overwrite (replace all)
- Re-index after import for search
- Warnings for unknown users (comments are preserved)

### Admin UI
```
┌─────────────────────────────────────────────────────────────┐
│  Export / Import                                            │
├─────────────────────────────────────────────────────────────┤
│  📤 EXPORT                                                  │
│  ┌────────────────────────────────────────────────────┐    │
│  │ 🇳🇱 Dutch (15 pages, 42 media, 128 comments)      │ [Export] │
│  │ 🇬🇧 English (12 pages, 38 media, 45 comments)     │ [Export] │
│  └────────────────────────────────────────────────────┘    │
│  ☐ Include comments and reactions                          │
│                                                             │
│  📥 IMPORT                                                  │
│  [Choose File]  intravox-export-nl.zip                      │
│  ○ Merge (keep existing, add new)                          │
│  ○ Overwrite (replace all content for this language)       │
│  ☐ Import comments and reactions                           │
│  [Import]                                                   │
└─────────────────────────────────────────────────────────────┘
```

### Technical Implementation
| File | Description |
|------|-------------|
| `lib/Service/ExportImportService.php` | Core export/import logic |
| `lib/Controller/ExportImportController.php` | REST API endpoints |
| `appinfo/routes.php` | New routes |
| `src/components/AdminSettings.vue` | Export/Import tab |

### Edge Cases
- **User doesn't exist**: Import with original userId, show warning
- **UniqueId conflict**: In merge mode, keep existing page
- **Media file too large**: Skip with warning
- **Corrupt ZIP**: Graceful error handling

---

## 2. Image Carousel Widget

**Status**: Planned
**Priority**: Medium

### Description
Extension of the Image widget with carousel/slider functionality for multiple images.

### Features
- Multiple images in one widget
- Auto-advance (optional, configurable interval)
- Manual navigation (arrows, dots)
- Touch/swipe support for mobile
- Different display modes:
  - Fade transition
  - Slide transition
  - Ken Burns effect (optional)
- Thumbnail navigation (optional)
- Fullscreen lightbox option

### Widget Configuration
```json
{
  "type": "image-carousel",
  "images": [
    { "src": "slide1.jpg", "alt": "Slide 1", "caption": "Optional caption" },
    { "src": "slide2.jpg", "alt": "Slide 2", "caption": "" },
    { "src": "slide3.jpg", "alt": "Slide 3", "caption": "" }
  ],
  "settings": {
    "autoPlay": true,
    "interval": 5000,
    "transition": "slide",
    "showDots": true,
    "showArrows": true,
    "showThumbnails": false,
    "enableLightbox": true
  }
}
```

### UI in Page Editor
- Drag & drop multiple images
- Reorder images
- Per image: alt text, caption
- Carousel settings panel

---

## 3. Roll-up / Page Overview Widget

**Status**: Planned
**Priority**: Medium

### Description
Widget to display page overviews based on properties, metadata (MetaVox), or location in the site structure.

### Use Cases
- News overview on homepage
- "Related pages" section
- Department pages overview
- Recently modified pages
- Pages with specific MetaVox tags

### Widget Types

#### 3.1 Content Roll-up
Show pages based on:
- Location (all subpages of a folder)
- MetaVox metadata (tags, categories, custom fields)
- Recent changes
- Manual selection

#### 3.2 Display Options
- **Cards**: Grid with image, title, excerpt
- **List**: Compact list with title and date
- **Featured**: First item large, rest smaller
- **Timeline**: Chronological display

### Widget Configuration
```json
{
  "type": "page-rollup",
  "source": {
    "type": "folder",           // folder | metadata | manual | recent
    "folder": "/news",          // for folder type
    "metadata": {               // for metadata type
      "field": "category",
      "value": "announcements"
    },
    "pages": ["page-id-1", "page-id-2"]  // for manual type
  },
  "display": {
    "style": "cards",           // cards | list | featured | timeline
    "columns": 3,
    "showImage": true,
    "showExcerpt": true,
    "showDate": true,
    "showAuthor": false,
    "excerptLength": 150,
    "maxItems": 6
  },
  "sorting": {
    "field": "date",            // date | title | custom
    "order": "desc"
  },
  "filters": {
    "dateRange": null,          // { start: "2024-01-01", end: "2024-12-31" }
    "language": "current"       // current | all | specific
  }
}
```

### MetaVox Integration
When MetaVox is installed:
- Filter on MetaVox metadata fields
- Display MetaVox metadata in cards
- Sort by custom MetaVox fields

```json
{
  "source": {
    "type": "metadata",
    "metadata": {
      "field": "metavox.department",
      "value": "HR"
    }
  }
}
```

### UI in Page Editor
```
┌─────────────────────────────────────────────────────────────┐
│  Page Roll-up Widget                                        │
├─────────────────────────────────────────────────────────────┤
│  Source:                                                    │
│  ○ Folder: [Select folder ▼]                               │
│  ○ Metadata: [Field ▼] = [Value]                           │
│  ○ Manual selection: [Select pages...]                     │
│  ○ Recent changes                                          │
│                                                             │
│  Display:                                                   │
│  Style: [Cards ▼]  Columns: [3 ▼]  Max items: [6]          │
│  ☑ Show image  ☑ Show excerpt  ☑ Show date                 │
│                                                             │
│  Sorting: [Date ▼] [Descending ▼]                          │
└─────────────────────────────────────────────────────────────┘
```

---

## 4. Comments & Reactions Improvements

**Status**: Partially implemented
**Priority**: Low (basics work)

### Current Status ✓
- [x] Comments with threading (1 level)
- [x] Emoji reactions on pages
- [x] Emoji reactions on comments
- [x] Single reaction per user (Facebook-style)
- [x] Admin settings for enable/disable
- [x] Page-level overrides
- [x] Inline reply input

### Planned Improvements
- [ ] @mentions with notifications
- [ ] Markdown support in comments
- [ ] Comment moderation queue for admins
- [ ] Bulk delete/moderate
- [ ] Export comments (see section 1)
- [ ] Activity feed integration

---

## 5. Other Planned Features

### 5.1 Widget Improvements
- **Video widget**: Local video upload alongside embed
- **File widget**: Multiple files, folder browser
- **Links widget**: Automatic favicon fetching
- **Text widget**: More markdown options, tables

### 5.2 Editor Improvements
- Undo/redo stack
- Widget copy/paste between pages
- Widget templates/presets
- Bulk widget operations

### 5.3 Navigation
- Mega menu improvements
- Breadcrumbs widget
- Sitemap generation
- Quick links widget

### 5.4 Performance
- Image lazy loading
- Widget lazy loading
- Better caching strategy
- CDN support for media

### 5.5 Integrations
- MetaVox metadata in page editor
- CalVox events widget
- Nextcloud Files integration
- Search improvements

---

## Version Planning

| Version | Features | Status |
|---------|----------|--------|
| 0.7.x | Bug fixes, stability | Current |
| 0.8.0 | Export/Import | Planned |
| 0.9.0 | Image Carousel | Planned |
| 1.0.0 | Page Roll-up, MetaVox integration | Planned |

---

## Contributing

Feature requests and bug reports via GitHub Issues:
https://github.com/nextcloud/intravox/issues
