# IntraVox Editor Guide

This guide is for content editors who create and maintain pages in IntraVox.

## Prerequisites

To edit content, you need:
- A Nextcloud account
- Editor permissions (assigned by your administrator)
- Access to the relevant IntraVox sections

## Editing Basics

### Entering Edit Mode

1. Navigate to the page you want to edit
2. Click the **Edit** button (pencil icon) in the toolbar
3. The page switches to edit mode

### Edit Mode Interface

```
┌─────────────────────────────────────────────────────────────┐
│  [Save] [Cancel]                              Edit Mode     │
├─────────────────────────────────────────────────────────────┤
│  ┌──────────────┐  ┌────────────────────────────────────┐  │
│  │   Widget     │  │                                    │  │
│  │   Palette    │  │         Page Canvas                │  │
│  │              │  │                                    │  │
│  │  [Heading]   │  │   [Row 1]                         │  │
│  │  [Text]      │  │   ┌─────────────────────────┐     │  │
│  │  [Image]     │  │   │  Widget (editable)      │     │  │
│  │  [Links]     │  │   └─────────────────────────┘     │  │
│  │  [Divider]   │  │                                    │  │
│  │              │  │   [Row 2]                         │  │
│  │  [Add Row]   │  │   ┌──────────┐ ┌──────────┐       │  │
│  └──────────────┘  │   │ Widget 1 │ │ Widget 2 │       │  │
│                    │   └──────────┘ └──────────┘       │  │
│                    └────────────────────────────────────┘  │
└─────────────────────────────────────────────────────────────┘
```

### Saving Changes

- Click **Save** to save your changes
- Click **Cancel** to discard changes and exit edit mode
- Changes are not visible to others until you save

## Page Structure

### Rows

Pages are organized in rows. Each row can have:
- 1, 2, or 3 columns
- A background color
- Multiple widgets

**Adding a row:**
1. Click "Add Row" at the bottom of the page
2. Select the number of columns
3. The new row appears at the bottom

**Configuring a row:**
1. Hover over the row
2. Click the settings icon
3. Change columns or background color

**Deleting a row:**
1. Hover over the row
2. Click the delete icon
3. Confirm deletion

### Columns

Rows can have 1-3 columns:

| Layout | Description |
|--------|-------------|
| 1 column | Full width content |
| 2 columns | Split 50/50 |
| 3 columns | Three equal columns |

### Side Columns

Pages can have optional side columns:
- Left sidebar
- Right sidebar

Enable in page settings.

## Widgets

Widgets are the building blocks of page content.

![Available Widgets](https://raw.githubusercontent.com/nextcloud/intravox/main/screenshots/widgets.png)

*The widget palette showing all available widget types*

### Adding Widgets

1. In the widget palette, click the widget type
2. Drag it to the desired location on the page
3. Or click to add to the first available column

### Widget Types

#### Heading

Titles and section headers.

**Options:**
- Level: H1 (largest) to H6 (smallest)
- Content: The heading text

**Best practices:**
- Use H1 for page title (one per page)
- Use H2 for main sections
- Use H3-H4 for subsections

#### Text

Rich text content with formatting.

**Formatting options:**
- **Bold** (Ctrl+B)
- *Italic* (Ctrl+I)
- Underline (Ctrl+U)
- Bullet lists
- Numbered lists
- Links

**Best practices:**
- Keep paragraphs short
- Use lists for multiple items
- Add links to relevant pages

#### Image

Photos, diagrams, and graphics.

**Options:**
- Image source: Select from IntraVox images folder
- Alt text: Description for accessibility
- Object fit: Cover, contain, or auto

**Image sizes:**
- Small: Thumbnail size
- Medium: Half width
- Large: Full width
- Custom: Specify pixel width

**Best practices:**
- Use descriptive alt text
- Optimize images before upload (< 500KB)
- Use appropriate aspect ratios

#### Links

Collections of links displayed as cards or lists.

**Options:**
- Title: Link title
- Description: Short description
- URL: Destination (page or external)
- Icon: Optional icon
- Target: Same window or new tab
- Columns: 1-4 columns for card layout

**Best practices:**
- Group related links together
- Use descriptive titles
- Indicate external links

#### Divider

Visual separators between content sections.

**Options:**
- Style: Solid line or transparent spacer
- Color: Line color (or inherit)
- Height: Line thickness or space height

### Editing Widgets

1. Click on a widget to select it
2. Use the toolbar or properties panel to edit
3. Changes appear immediately

### Moving Widgets

**Drag and drop:**
1. Click and hold a widget
2. Drag to new position
3. Release to place

**Between columns:**
- Drag widgets between columns in the same row

**Between rows:**
- Drag widgets to different rows

### Deleting Widgets

1. Select the widget
2. Click the delete icon (trash)
3. Widget is removed immediately

## Working with Images

### Uploading Images

1. Open Nextcloud Files
2. Navigate to IntraVox folder > your language > relevant section > images
3. Upload your image
4. Return to IntraVox and select the image

### Image Guidelines

| Type | Recommended Size | Format |
|------|-----------------|--------|
| Hero images | 1920x600 px | JPG |
| Content images | 800x600 px | JPG/PNG |
| Icons | 64x64 px | PNG/SVG |
| Logos | 200x100 px | PNG/SVG |

### Image Optimization

Before uploading:
1. Resize to appropriate dimensions
2. Compress to reduce file size
3. Use JPG for photos, PNG for graphics
4. Keep files under 500KB

## Navigation

### Page Structure View

The Page Structure panel shows all pages in a tree view, making it easy to navigate and manage your content hierarchy.

![Page Structure View](https://raw.githubusercontent.com/nextcloud/intravox/main/screenshots/pagestructure.png)

*Page structure showing the hierarchical organization of pages*

### Editing Navigation

1. Click **Edit Navigation** in the toolbar (requires admin permission)
2. The navigation editor opens

### Navigation Structure

```
Navigation
├── Home (links to homepage)
├── About
│   ├── Our Company
│   └── Team
├── Departments (dropdown)
│   ├── HR
│   ├── Sales
│   └── IT
└── External Links
    └── Company Website (external URL)
```

### Adding Navigation Items

1. Click "Add Item"
2. Enter title
3. Select destination:
   - Page: Link to IntraVox page (by uniqueId)
   - URL: External website
   - None: Parent menu only
4. Set target (same window or new tab)
5. Save navigation

### Navigation Types

**Megamenu:** Large dropdown showing all items at once
**Dropdown:** Cascading menus that expand on hover

### Best Practices

- Keep navigation depth to 3 levels maximum
- Use clear, concise labels
- Group related items together
- Test on mobile devices

## Footer

### Editing Footer

1. Click **Edit Footer** in the toolbar
2. Enter footer content using Markdown
3. Save changes

### Footer Content

Typical footer includes:
- Copyright notice
- Links to legal pages
- Contact information

Example:
```markdown
© 2025 Company Name - [Contact](#) | [Privacy](#) | [Help](#)
```

## Creating New Pages

### From Navigation

1. Edit navigation
2. Add new item with desired title
3. Leave uniqueId empty
4. Save navigation
5. Navigate to the new item
6. IntraVox creates the page automatically

### Page Files

Pages are stored as JSON files:
```
IntraVox/
└── en/
    └── section/
        └── new-page.json
```

## Best Practices

### Content Guidelines

1. **Clear headings**: Use descriptive headings
2. **Short paragraphs**: Break up long text
3. **Visual hierarchy**: Use consistent styling
4. **Call to action**: Guide users to next steps
5. **Fresh content**: Update regularly

### Consistency

1. Use the same heading levels across pages
2. Maintain consistent image sizes
3. Follow your organization's style guide
4. Use approved terminology

### Accessibility

1. Always add alt text to images
2. Use proper heading hierarchy (H1 → H2 → H3)
3. Ensure sufficient color contrast
4. Make link text descriptive ("Read the policy" not "Click here")

### Performance

1. Optimize images before upload
2. Don't overload pages with widgets
3. Use appropriate image sizes
4. Test load times

## Troubleshooting

### Can't Enter Edit Mode

- Check you have edit permissions
- Refresh the page
- Contact administrator

### Changes Not Saving

- Check internet connection
- Try again after a few seconds
- Check for validation errors
- Contact IT support

### Images Not Appearing

- Verify image was uploaded
- Check image path is correct
- Ensure image format is supported
- Try re-selecting the image

### Widget Not Working

- Check widget configuration
- Try removing and re-adding
- Clear browser cache
- Report issue to administrator

## Keyboard Shortcuts

| Shortcut | Action |
|----------|--------|
| Ctrl+S | Save page |
| Ctrl+B | Bold text |
| Ctrl+I | Italic text |
| Ctrl+U | Underline text |
| Escape | Cancel edit / close dialog |
| Delete | Remove selected widget |

## Getting Help

- **Technical issues**: Contact IT support
- **Content questions**: Ask your content lead
- **Feature requests**: Submit via your organization's process
- **Documentation**: See other guides in the docs folder
