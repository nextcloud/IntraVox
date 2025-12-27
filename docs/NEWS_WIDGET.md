# News Widget

The News Widget displays a dynamic list of pages from a selected location in your IntraVox site. It's perfect for showcasing news articles, blog posts, announcements, or any collection of related content.

## Features

- **Multiple layouts**: List, Grid, or Carousel view
- **Source selection**: Show pages from any folder or section
- **MetaVox filtering**: Filter pages based on metadata fields (requires MetaVox app)
- **Customizable display**: Toggle image, date, and excerpt visibility
- **Sorting options**: Sort by date modified or title
- **Autoplay carousel**: Configurable interval for automatic rotation

## Layouts

### List Layout

Displays items in a vertical list with thumbnail, title, date, and excerpt.

![News Widget List Layout](../screenshots/Newswidget-list.png)

### Grid Layout

Shows items in a responsive grid with 2, 3, or 4 columns.

![News Widget Grid Layout](../screenshots/Newswidget-grid.png)

### Carousel Layout

A rotating slideshow with navigation arrows and dot indicators. Supports autoplay with configurable interval.

![News Widget Carousel](../screenshots/Newswidget-carrousel.gif)

## Configuration

To add a News Widget to your page:

1. Click **+ Add Widget** in edit mode
2. Select **News** from the widget picker
3. Configure the widget settings

![News Widget Editor](../screenshots/Newswidget-edit.png)

### Settings

| Setting | Description |
|---------|-------------|
| **Widget title** | Optional title displayed above the widget |
| **Source location** | Select a page or folder to show content from (includes all subpages) |
| **Layout** | Choose between List, Grid, or Carousel |
| **Columns** | For Grid layout: 2, 3, or 4 columns |
| **Autoplay interval** | For Carousel layout: seconds between slides (0 = disabled) |
| **Number of items** | Maximum items to display (1-20) |
| **Sort by** | Date modified or Title |
| **Sort order** | Ascending or Descending |
| **Show image** | Display the page's featured image |
| **Show date** | Display the modification date |
| **Show excerpt** | Display a text excerpt from the page |

## MetaVox Integration

When the [MetaVox](https://apps.nextcloud.com/apps/metavox) app is installed and configured, you can filter News Widget results based on metadata fields.

![MetaVox Filter Configuration](../screenshots/Newswidget-MetaVoxfilter.png)

### Adding Filters

1. Click **+ Add filter** in the MetaVox filters section
2. Select a metadata field from the dropdown
3. Choose an operator (equals, contains, is not empty)
4. Enter a value to filter on

![MetaVox Filter Example](../screenshots/Newswidget-MetaVoxfilter2.png)

### Filter Operators

| Operator | Description |
|----------|-------------|
| **equals** | Exact match |
| **contains** | Partial match (text fields) |
| **is not empty** | Field has any value |

### Checkbox Fields

For checkbox/boolean metadata fields:
- Use `1` for checked/true
- Use `0` for unchecked/false

### Multiple Filters

When using multiple filters, you can choose:
- **Match all**: All filters must match (AND logic)
- **Match any**: At least one filter must match (OR logic)

## Tips

- **Performance**: Limit the number of items for better page load times
- **Images**: Pages need a featured image (first image widget) to display thumbnails
- **Excerpts**: Excerpts are automatically extracted from the first text content on each page
- **Dark backgrounds**: The widget automatically adjusts text colors when placed on dark row backgrounds

## Requirements

- IntraVox 0.8.0 or higher
- MetaVox app (optional, for filtering)
