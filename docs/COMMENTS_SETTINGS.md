# Comments & Reactions Settings Guide

This document describes the configuration options for IntraVox's comments and reactions system.

**Related documentation:**
- [Comments Architecture](COMMENTS_ARCHITECTURE.md) - Technical implementation details
- [Admin Settings Guide](ADMIN_SETTINGS.md) - Video services and demo data

---

## Overview

IntraVox provides two levels of settings for comments and reactions:

1. **Admin Settings** - Global settings for all of IntraVox
2. **Page Settings** - Per-page overrides by editors

---

## Admin Settings

### Accessing Admin Settings

1. Go to **Nextcloud Admin Settings**
2. Select **IntraVox** in the left sidebar
3. Click the **Engagement** tab

### Available Settings

#### Page Reactions

| Setting | Default | Description |
|---------|---------|-------------|
| Allow reactions | On | Users can add emoji reactions to pages |

#### Comments

| Setting | Default | Description |
|---------|---------|-------------|
| Allow comments | On | Users can post comments on pages |
| Allow comment reactions | On | Users can add emoji reactions to comments |

#### Behavior

| Setting | Default | Description |
|---------|---------|-------------|
| Multiple reactions per user | Off | When off: new reaction replaces previous |

### Settings Panel Layout

```
┌─────────────────────────────────────────────┐
│ Engagement                                  │
├─────────────────────────────────────────────┤
│                                             │
│ Page Reactions                              │
│ ┌─────────────────────────────────────────┐ │
│ │ [✓] Allow reactions                     │ │
│ │     Users can add emoji reactions to    │ │
│ │     pages                               │ │
│ └─────────────────────────────────────────┘ │
│                                             │
│ Comments                                    │
│ ┌─────────────────────────────────────────┐ │
│ │ [✓] Allow comments                      │ │
│ │     Users can post comments on pages    │ │
│ │                                         │ │
│ │ [✓] Allow comment reactions             │ │
│ │     Users can add emoji reactions to    │ │
│ │     comments                            │ │
│ └─────────────────────────────────────────┘ │
│                                             │
│ Behavior                                    │
│ ┌─────────────────────────────────────────┐ │
│ │ ( ) Multiple reactions per user         │ │
│ │ (•) One reaction per user               │ │
│ │     New reaction replaces previous      │ │
│ └─────────────────────────────────────────┘ │
│                                             │
└─────────────────────────────────────────────┘
```

---

## Page Settings

Editors with write access to a page can override the global settings per page.

### Accessing Page Settings

1. Open the page in edit mode
2. Click **Page Settings** in the sidebar
3. Find the **Engagement** section

### Available Overrides

| Setting | Options | Description |
|---------|---------|-------------|
| Reactions | Inherit / On / Off | Override global reactions setting |
| Comments | Inherit / On / Off | Override global comments setting |

### Settings in Page Editor

```
┌─────────────────────────────────────────────┐
│ Page Settings                               │
├─────────────────────────────────────────────┤
│                                             │
│ ...other settings...                        │
│                                             │
│ Engagement                                  │
│ ┌─────────────────────────────────────────┐ │
│ │ Reactions: [Inherit from admin ▼]       │ │
│ │                                         │ │
│ │ Comments:  [Inherit from admin ▼]       │ │
│ └─────────────────────────────────────────┘ │
│                                             │
└─────────────────────────────────────────────┘
```

### Dropdown Options

- **Inherit from admin** (default) - Use the global admin setting
- **Enabled** - Force enable on this page
- **Disabled** - Force disable on this page

### Use Cases

| Scenario | Reactions | Comments |
|----------|-----------|----------|
| Announcement page | On | Off |
| Discussion page | On | On |
| Policy document | Off | Off |
| News article | On | On |

---

## How Settings Are Applied

### Priority Order

```
Page Setting (if set) > Admin Setting > Default (On)
```

### Decision Flow

```
Is reactions allowed on this page?
├── Page has explicit setting?
│   ├── Yes, "Enabled" → Show reactions
│   └── Yes, "Disabled" → Hide reactions
└── No (Inherit)
    └── Check admin setting
        ├── Enabled → Show reactions
        └── Disabled → Hide reactions
```

---

## Storage

### Admin Settings

Stored in Nextcloud's config using `OCP\IConfig`:

| Key | Type | Default |
|-----|------|---------|
| `intravox/allow_page_reactions` | bool | true |
| `intravox/allow_comments` | bool | true |
| `intravox/allow_comment_reactions` | bool | true |
| `intravox/single_reaction_per_user` | bool | true |

### Page Settings

Stored in the page's JSON file:

```json
{
  "uniqueId": "page-abc-123",
  "title": "My Page",
  "settings": {
    "allowReactions": null,
    "allowComments": false
  },
  "layout": { ... }
}
```

Values:
- `null` - Inherit from admin (default)
- `true` - Force enabled
- `false` - Force disabled

---

## API

### Get Settings

```
GET /api/settings/engagement
```

Response:
```json
{
  "allowPageReactions": true,
  "allowComments": true,
  "allowCommentReactions": true,
  "singleReactionPerUser": true
}
```

### Update Settings (Admin only)

```
PUT /api/settings/engagement
```

Body:
```json
{
  "allowPageReactions": true,
  "allowComments": true,
  "allowCommentReactions": true,
  "singleReactionPerUser": true
}
```

---

## Permissions

| Action | Required Role |
|--------|---------------|
| View admin settings | Nextcloud Admin |
| Change admin settings | Nextcloud Admin |
| View page settings | Page edit access |
| Change page settings | Page edit access |

---

## Recommendations

### For Intranet Administrators

1. **Start with defaults** - Both reactions and comments enabled
2. **Monitor engagement** - Check if users are using the features
3. **Single reaction mode** - Recommended for cleaner statistics
4. **Disable per page** - Use page settings for special pages like policies

### For Content Editors

1. **News/announcements** - Enable both for engagement
2. **Policy documents** - Consider disabling comments
3. **Discussion pages** - Enable everything
4. **Archived content** - Consider disabling comments

---

## Troubleshooting

### Reactions/Comments Not Showing

1. Check admin settings are enabled
2. Check page settings don't override to disabled
3. Verify user has read access to the page
4. Check browser console for errors

### Cannot Change Settings

1. Verify you have admin rights (for admin settings)
2. Verify you have edit access (for page settings)
3. Check Nextcloud logs for permission errors
