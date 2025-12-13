# Engagement Architecture

Technical documentation for IntraVox's engagement system (reactions and comments).

**Audience:** Developers and technical administrators

**Related documentation:**
- [Engagement Admin Guide](ENGAGEMENT_ADMIN.md) - Configuration for administrators
- [Engagement User Guide](ENGAGEMENT_GUIDE.md) - End-user documentation
- [Authorization Guide](AUTHORIZATION.md) - Permission model

---

## Overview

IntraVox uses **Nextcloud's native Comments API** (`OCP\Comments\ICommentsManager`) for all comment and reaction functionality. This provides threading, emoji reactions, @mentions, and Activity feed integration out-of-the-box.

### Why Nextcloud Comments API?

| Aspect | Custom Tables | NC Comments API |
|--------|---------------|-----------------|
| Database tables | 2 custom | 0 (uses `oc_comments`) |
| PHP entities | 2 | 0 |
| PHP mappers | 2 | 0 |
| Migration needed | Yes | No |
| Threading | Build ourselves | Native |
| Emoji reactions | Build ourselves | Native |
| @mentions | Not planned | Native |
| Activity feed | Build ourselves | Native |

Apps using this approach: Nextcloud Files, Deck, Announcementcenter

---

## Data Model

### Object Type Registration

IntraVox registers `intravox_page` as a comment object type:

```php
$event->addEntityCollection('intravox_page', function ($pageId) {
    return $this->pageService->pageExists($pageId);
});
```

### Data Coupling

```
┌─────────────────────────────────────────────────────────────────┐
│  GROUPFOLDER (files)                  │  DATABASE (oc_comments) │
├───────────────────────────────────────┼─────────────────────────┤
│                                       │                         │
│  /IntraVox/nl/welcome.json            │  objectType: intravox_page
│    {                                  │  objectId: page-abc-123 │
│      "uniqueId": "page-abc-123", ◄────┼──────────────────────►  │
│      "title": "Welcome",              │                         │
│      "widgets": [...]                 │  comment_id: 1          │
│    }                                  │  message: "Great!"      │
│                                       │  user_id: "john"        │
│                                       │  reactions: ['👍','❤️'] │
│                                       │                         │
└───────────────────────────────────────┴─────────────────────────┘
```

The `uniqueId` in the .json file is the **key** connecting pages to comments.

### Behavior with Page Operations

| Action | .json file | Comments in DB | Result |
|--------|------------|----------------|--------|
| Edit page | UUID stays | Stay | All intact |
| Move page | UUID stays | Stay | All intact |
| Rename page | UUID stays | Stay | All intact |
| Copy page | New UUID | Not copied | No comments on copy |
| Delete page | Gone | Orphaned | Cleanup needed |

---

## API Endpoints

### Comments

| Method | Endpoint | Description | Permission |
|--------|----------|-------------|------------|
| GET | `/api/pages/{pageId}/comments` | Get comments with replies | Read access |
| POST | `/api/pages/{pageId}/comments` | Create comment | Read access |
| PUT | `/api/comments/{id}` | Edit own comment | Author |
| DELETE | `/api/comments/{id}` | Delete comment | Author or Admin |

### Page Reactions

| Method | Endpoint | Description | Permission |
|--------|----------|-------------|------------|
| GET | `/api/pages/{pageId}/reactions` | Get page reactions | Read access |
| POST | `/api/pages/{pageId}/reactions/{emoji}` | Add reaction | Read access |
| DELETE | `/api/pages/{pageId}/reactions/{emoji}` | Remove reaction | Read access |

### Comment Reactions

| Method | Endpoint | Description | Permission |
|--------|----------|-------------|------------|
| GET | `/api/comments/{id}/reactions` | Get comment reactions | Read access |
| POST | `/api/comments/{id}/reactions/{emoji}` | Add reaction to comment | Read access |
| DELETE | `/api/comments/{id}/reactions/{emoji}` | Remove reaction | Read access |

### Settings

| Method | Endpoint | Description | Permission |
|--------|----------|-------------|------------|
| GET | `/api/settings/engagement` | Get engagement settings | Any user |
| PUT | `/api/settings/engagement` | Update settings | Admin only |

---

## Permission Model

| Action | Required |
|--------|----------|
| View reactions/comments | Read access to page |
| Add emoji reaction | Read access to page |
| Post comment | Read access to page |
| Reply to comment | Read access to page |
| Edit own comment | Comment author |
| Delete own comment | Comment author |
| Delete any comment | IntraVox Admin or NC Admin |

---

## Backend Components

### Files

| File | Purpose |
|------|---------|
| `lib/Listener/CommentsEntityListener.php` | Register `intravox_page` objectType |
| `lib/Service/CommentService.php` | Wrapper around `ICommentsManager` |
| `lib/Controller/CommentController.php` | REST API endpoints |

### Key Service Methods

```php
class CommentService {
    // Comments
    public function getComments(string $pageId, int $limit, int $offset): array;
    public function createComment(string $pageId, string $message, ?string $parentId): array;
    public function updateComment(string $commentId, string $message): array;
    public function deleteComment(string $commentId, bool $isAdmin): void;

    // Page reactions
    public function getPageReactions(string $pageId): array;
    public function addPageReaction(string $pageId, string $emoji): array;
    public function removePageReaction(string $pageId, string $emoji): array;

    // Comment reactions
    public function getCommentReactions(string $commentId): array;
    public function addCommentReaction(string $commentId, string $emoji): array;
    public function removeCommentReaction(string $commentId, string $emoji): array;
}
```

---

## Frontend Components

### Files

| File | Purpose |
|------|---------|
| `src/services/CommentService.js` | API client |
| `src/components/reactions/ReactionBar.vue` | Emoji reactions below page |
| `src/components/reactions/CommentSection.vue` | Comments container |
| `src/components/reactions/CommentItem.vue` | Single comment with replies |
| `src/components/reactions/ReactionPicker.vue` | Emoji picker |

### Component Hierarchy

```
PageViewer.vue
└── ReactionBar.vue          (page reactions)
    └── ReactionPicker.vue
└── CommentSection.vue       (comments container)
    └── CommentItem.vue      (single comment)
        └── ReactionPicker.vue
        └── CommentItem.vue  (replies, 1 level deep)
```

---

## Settings Storage

### Admin Settings

Stored in Nextcloud's config using `OCP\IConfig`:

| Key | Type | Default |
|-----|------|---------|
| `intravox/allowPageReactions` | bool | true |
| `intravox/allowComments` | bool | true |
| `intravox/allowCommentReactions` | bool | true |

### Page Settings

Stored in the page's JSON file:

```json
{
  "uniqueId": "page-abc-123",
  "title": "My Page",
  "settings": {
    "allowReactions": null,
    "allowComments": false,
    "allowCommentReactions": null
  },
  "layout": { ... }
}
```

Values:
- `null` - Inherit from admin (default)
- `false` - Force disabled

Note: Pages can only **disable** features that are globally enabled. They cannot enable features that are globally disabled.

---

## Settings Resolution

### Priority Order

```
Admin Setting (master switch) → Page Setting (can only disable)
```

### Decision Flow

```
Is feature X allowed on this page?
├── Admin setting disabled?
│   └── Yes → Feature disabled (page cannot override)
└── No (Admin enabled)
    └── Page setting explicitly disabled?
        ├── Yes → Feature disabled
        └── No (null/inherit) → Feature enabled
```

---

## UI Position

### Below Page Content

```
┌─────────────────────────────────────────────────┐
│  INTRAVOX PAGE                                  │
├─────────────────────────────────────────────────┤
│                                                 │
│  Page content...                                │
│                                                 │
│  ─────────────────────────────────────────────  │
│                                                 │
│  👍 12  ❤️ 5  🎉 3        [+ React]             │  ← Reaction Bar
│                                                 │
│  ─────────────────────────────────────────────  │
│                                                 │
│  💬 Comments (8)                    [Sort ▼]    │
│                                                 │
│  ┌─────────────────────────────────────────┐   │
│  │ 📝 Write a comment...                   │   │  ← Input at top
│  └─────────────────────────────────────────┘   │
│                                                 │
│  ┌─────────────────────────────────────────┐   │
│  │ 👤 John Doe • 2 hours ago               │   │
│  │ Great article!                          │   │
│  │ [Reply] [😀] 👍 3                        │   │
│  │   └─ 👤 Jane: Agreed!                   │   │  ← 1 level threading
│  └─────────────────────────────────────────┘   │
│                                                 │
└─────────────────────────────────────────────────┘
```

### Why Below Content (not Sidebar)?

| Aspect | Sidebar | Below Content |
|--------|---------|---------------|
| Visibility | Hidden until click | Directly visible |
| Engagement | Low (extra action) | High (just scroll) |
| Context | Separate from content | Connected to content |
| Mobile | Sidebar problematic | Natural flow |

---

## Technical Constraints

- **Threading**: Limited to 1 level (comment -> reply, no reply to reply)
- **Pagination**: Default 50 comments at a time
- **Text format**: Plain text only (no markdown)
- **@mentions**: Supported via NC native support
- **Activity feed**: Automatic integration
- **Reactions**: 18 fixed emoji options

---

## Export/Import (Future)

```json
{
  "exportVersion": "1.0",
  "pages": [
    {
      "uniqueId": "page-abc-123",
      "comments": [
        {
          "userId": "john",
          "displayName": "John Doe",
          "message": "Great article!",
          "createdAt": "2024-12-01T10:00:00Z",
          "reactions": { "👍": ["jane", "anna"] },
          "replies": [...]
        }
      ],
      "pageReactions": {
        "👍": ["john", "jane"],
        "❤️": ["anna"]
      }
    }
  ]
}
```

---

## References

- [Nextcloud Comments API](https://docs.nextcloud.com/server/latest/developer_manual/client_apis/WebDAV/comments.html)
- [ICommentsManager Interface](https://github.com/nextcloud/server/blob/master/lib/public/Comments/ICommentsManager.php)
- [Announcementcenter Implementation](https://github.com/nextcloud/announcementcenter/blob/master/lib/Listener/CommentsEntityListener.php)
