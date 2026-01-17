# IntraVox API Reference

This document describes the IntraVox REST API for developers who want to integrate with IntraVox programmatically.

## Table of Contents

- [Base URLs](#base-urls)
- [Authentication](#authentication)
- [Response Format](#response-format)
- [Pages API](#pages-api)
- [Media API](#media-api)
- [Versioning API](#versioning-api)
- [Comments API](#comments-api)
- [Reactions API](#reactions-api)
- [Analytics API](#analytics-api)
- [Bulk Operations API](#bulk-operations-api)
- [Navigation & Footer API](#navigation--footer-api)
- [Settings API](#settings-api)
- [Page Metadata API](#page-metadata-api)
- [News API](#news-api)
- [Resources API](#resources-api)
- [Permissions API](#permissions-api)
- [MetaVox Integration API](#metavox-integration-api)
- [Setup & Demo Data API](#setup--demo-data-api)
- [Search API](#search-api)
- [Export/Import API](#exportimport-api)
- [Error Codes](#error-codes)
- [Security](#security)
- [Rate Limiting](#rate-limiting)
- [Migration Tool Integration](#migration-tool-integration)
- [Privacy](#privacy)

---

## Quick Reference: OCS API Endpoints (External Access)

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/v1/pages` | List all pages |
| POST | `/api/v1/pages` | Create a new page |
| GET | `/api/v1/pages/{id}` | Get single page |
| PUT | `/api/v1/pages/{id}` | Update page |
| DELETE | `/api/v1/pages/{id}` | Delete page |
| GET | `/api/v1/pages/{pageId}/media` | List media files |
| POST | `/api/v1/pages/{pageId}/media` | Upload media |
| GET | `/api/v1/pages/{pageId}/media/{filename}` | Get media file |

*All OCS endpoints are prefixed with `/ocs/v2.php/apps/intravox`*

---

## Base URLs

IntraVox provides two API interfaces:

### OCS API (Recommended for external tools)

Use the OCS API when accessing IntraVox from external tools, scripts, or applications using Basic Auth or app passwords:

```
https://your-nextcloud.com/ocs/v2.php/apps/intravox/api/v1/
```

The OCS API properly handles HTTP methods (GET, POST, PUT, DELETE) and is the recommended interface for programmatic access.

### Standard API (Internal use)

The standard API is used internally by the IntraVox frontend with session authentication:

```
https://your-nextcloud.com/apps/intravox/api/
```

## Authentication

The API uses Nextcloud's standard authentication:

1. **App Password + Basic Auth** (recommended for external tools)
   - Create an app password in Nextcloud: Settings → Security → Devices & sessions → Create new app password
   - Include `OCS-APIREQUEST: true` header for OCS endpoints

2. **Session cookies** (for browser-based applications)

3. **Bearer token** with Nextcloud OAuth tokens

### Example with curl (OCS API):
```bash
curl -u "username:app-password" \
  -H "OCS-APIREQUEST: true" \
  https://your-nextcloud.com/ocs/v2.php/apps/intravox/api/v1/pages
```

### Example: Create a page
```bash
curl -X POST \
  -u "username:app-password" \
  -H "Content-Type: application/json" \
  -H "OCS-APIREQUEST: true" \
  -d '{"id": "my-page", "title": "My Page", "uniqueId": "page-12345"}' \
  https://your-nextcloud.com/ocs/v2.php/apps/intravox/api/v1/pages
```

## Response Format

All endpoints return JSON responses with a consistent structure.

### Success Response

```json
{
  "success": true,
  "data": { ... }
}
```

### Error Response

Errors return appropriate HTTP status codes (400, 403, 404, 500) with a consistent format:

```json
{
  "success": false,
  "error": "Human-readable error message",
  "errorId": "err_abc123"
}
```

The `errorId` field is included for server errors (5xx) and can be used by support to correlate with server logs. Client errors (4xx) may not include this field.

**Important:** Error messages are intentionally generic for security reasons. Sensitive details (stack traces, file paths, SQL errors) are logged server-side but never exposed to API consumers.

---

## Pages API

### List All Pages

```
GET /api/pages
```

Returns a flat list of all pages the user has read access to.

**Response:**
```json
[
  {
    "id": "page-abc123",
    "uniqueId": "page-abc123",
    "title": "Welcome Page",
    "path": "/IntraVox/en/Welcome Page",
    "parentId": null,
    "permissions": {
      "canRead": true,
      "canWrite": true,
      "canDelete": true
    },
    "createdAt": "2025-01-15T10:30:00Z",
    "updatedAt": "2025-01-16T14:22:00Z"
  }
]
```

### Get Page Tree

```
GET /api/pages/tree
```

Returns pages organized in a hierarchical tree structure.

### Get Single Page

```
GET /api/pages/{id}
```

Returns full page details including content.

**Parameters:**
- `id` (string): The unique page ID

**Response:**
```json
{
  "id": "page-abc123",
  "uniqueId": "page-abc123",
  "title": "Welcome Page",
  "content": "<p>Page HTML content...</p>",
  "metadata": {
    "author": "admin",
    "category": "general"
  },
  "permissions": {
    "canRead": true,
    "canWrite": true,
    "canDelete": true
  },
  "breadcrumb": [
    {"id": "page-root", "title": "Home"},
    {"id": "page-abc123", "title": "Welcome Page"}
  ]
}
```

### Create Page

```
POST /api/pages
```

Creates a new page.

**Request Body:**
```json
{
  "title": "New Page Title",
  "content": "<p>Initial content</p>",
  "parentId": "page-parent123",
  "metadata": {
    "category": "news"
  }
}
```

**Response:** Returns the created page object with HTTP 201.

### Update Page

```
PUT /api/pages/{id}
```

Updates an existing page.

**Request Body:**
```json
{
  "title": "Updated Title",
  "content": "<p>Updated content</p>",
  "metadata": {
    "category": "updated"
  }
}
```

### Delete Page

```
DELETE /api/pages/{id}
```

Deletes a page. Returns `{"success": true}` on success.

---

## Page Layout & Widgets

IntraVox pages use a structured JSON layout instead of raw HTML. The layout consists of rows containing widgets.

### Layout Structure

```json
{
  "title": "My Page",
  "layout": {
    "columns": 1,
    "rows": [
      {
        "columns": 1,
        "backgroundColor": "",
        "widgets": [...]
      }
    ],
    "sideColumns": {
      "left": { "enabled": false, "widgets": [] },
      "right": { "enabled": false, "widgets": [] }
    }
  }
}
```

### Row Properties

| Property | Type | Description |
|----------|------|-------------|
| `columns` | int | Number of columns (1-4) |
| `backgroundColor` | string | CSS color or variable (e.g., `var(--color-primary-element)`) |
| `collapsible` | bool | Make row collapsible (SharePoint-style) |
| `sectionTitle` | string | Title shown in collapsible header |
| `defaultCollapsed` | bool | Start collapsed (default: false) |
| `widgets` | array | Array of widget objects |

### Widget Types

All widgets have these common properties:
- `type` (string): Widget type identifier
- `column` (int): Which column (1-based)
- `order` (int): Sort order within column

#### Text Widget
```json
{
  "type": "text",
  "column": 1,
  "order": 1,
  "content": "**Markdown** supported text content"
}
```

#### Heading Widget
```json
{
  "type": "heading",
  "column": 1,
  "order": 1,
  "content": "Section Title",
  "level": 2
}
```
- `level`: 1-6 (h1-h6)

#### Image Widget
```json
{
  "type": "image",
  "column": 1,
  "order": 1,
  "src": "photo.jpg",
  "alt": "Description",
  "caption": "Optional caption"
}
```
- `src`: Filename (relative to page media folder) or full URL

#### Video Widget
```json
{
  "type": "video",
  "column": 1,
  "order": 1,
  "url": "https://youtube.com/watch?v=...",
  "caption": ""
}
```

#### Divider Widget
```json
{
  "type": "divider",
  "column": 1,
  "order": 1,
  "style": "solid",
  "color": "",
  "height": "2px"
}
```
- `style`: "solid", "dashed", "dotted"

#### Links Widget (Card Grid)
```json
{
  "type": "links",
  "column": 1,
  "order": 1,
  "columns": 3,
  "items": [
    {
      "title": "Link Title",
      "text": "Description",
      "url": "https://example.com",
      "icon": "home",
      "target": "_blank"
    }
  ]
}
```
- `icon`: Material Design Icon name (without `mdi-` prefix)

#### News Widget
```json
{
  "type": "news",
  "column": 1,
  "order": 1,
  "title": "Latest News",
  "sourcePath": "news",
  "layout": "carousel",
  "columns": 3,
  "limit": 5,
  "sortBy": "modified",
  "sortOrder": "desc",
  "showImage": true,
  "showDate": true,
  "showExcerpt": true,
  "excerptLength": 100,
  "autoplayInterval": 5
}
```
- `layout`: "grid", "carousel", "list"
- `sortBy`: "modified", "created", "title"

#### Files Widget
```json
{
  "type": "files",
  "column": 1,
  "order": 1,
  "path": "/Documents/Shared",
  "showFolders": true,
  "showFiles": true
}
```

### Complete Page Example

```bash
curl -X POST \
  -u "username:app-password" \
  -H "Content-Type: application/json" \
  -d '{
    "title": "Department Page",
    "parentId": "page-parent-123",
    "layout": {
      "columns": 1,
      "rows": [
        {
          "columns": 1,
          "backgroundColor": "var(--color-primary-element)",
          "widgets": [
            {
              "type": "heading",
              "column": 1,
              "order": 1,
              "content": "Welcome to IT Department",
              "level": 1
            }
          ]
        },
        {
          "columns": 2,
          "backgroundColor": "",
          "widgets": [
            {
              "type": "text",
              "column": 1,
              "order": 1,
              "content": "Welcome to the IT department page. Here you will find all relevant information."
            },
            {
              "type": "links",
              "column": 2,
              "order": 1,
              "columns": 1,
              "items": [
                {"title": "Helpdesk", "url": "/p/helpdesk", "icon": "help-circle"},
                {"title": "Systems", "url": "/p/systems", "icon": "server"}
              ]
            }
          ]
        }
      ]
    }
  }' \
  https://your-nextcloud.com/apps/intravox/api/pages
```

---

## Media API

Each IntraVox page has a `_media` folder for storing images and videos. Media can be uploaded via the internal API (session auth) or via WebDAV for external tools.

### Upload Media (Internal API)

```
POST /api/pages/{pageId}/media
```

Uploads a file to a page's media folder. Used by the IntraVox frontend.

**Content-Type:** `multipart/form-data`

**Form Fields:**
- `media`: The file to upload (or `image`/`video` for backward compatibility)

**Response:**
```json
{
  "filename": "img_60f1e5b2d3a4c6.jpg"
}
```

### Upload Media with Custom Name

```
POST /api/pages/{pageId}/media/upload
```

Uploads a file with a specified filename (instead of auto-generated).

**Content-Type:** `multipart/form-data`

**Form Fields:**
- `file`: The file to upload
- `filename`: Desired filename

**Response:**
```json
{
  "filename": "custom-name.jpg",
  "url": "/apps/intravox/api/pages/{pageId}/media/custom-name.jpg"
}
```

### Check for Duplicate Media

```
POST /api/pages/{pageId}/media/check
```

Checks if a file with the same name already exists before uploading.

**Request Body:**
```json
{
  "filename": "image.jpg"
}
```

**Response:**
```json
{
  "exists": true,
  "existingFile": {
    "name": "image.jpg",
    "size": 125432,
    "modified": 1705412520
  }
}
```

### List Media (Internal API)

```
GET /api/pages/{pageId}/media/list
```

Returns all media files for a page (internal route).

### Upload Media via WebDAV (External Tools)

For external tools using Basic Auth, use Nextcloud's WebDAV API to upload media files:

```bash
# First, get the page path
curl -u "user:app-password" \
  -H "OCS-APIREQUEST: true" \
  "https://your-nextcloud.com/ocs/v2.php/apps/intravox/api/v1/pages/{uniqueId}?format=json"
# Response includes: "path": "en/page-folder"

# Upload the file via WebDAV
curl -X PUT \
  -u "user:app-password" \
  --data-binary @/path/to/image.jpg \
  -H "Content-Type: image/jpeg" \
  "https://your-nextcloud.com/remote.php/dav/files/user/IntraVox/{path}/_media/image.jpg"
```

**Example:**
```bash
# Upload banner.svg to page "page-media-test-001"
curl -X PUT \
  -u "admin:app-password" \
  --data-binary @banner.svg \
  -H "Content-Type: image/svg+xml" \
  "https://your-nextcloud.com/remote.php/dav/files/admin/IntraVox/en/api-media-test/_media/banner.svg"
```

### List Media (OCS API)

```
GET /ocs/v2.php/apps/intravox/api/v1/pages/{pageId}/media
```

Returns all media files attached to a page.

**Headers:**
- `OCS-APIREQUEST: true`

**Response:**
```json
{
  "media": [
    {
      "name": "img_60f1e5b2.jpg",
      "size": 125432,
      "mimeType": "image/jpeg",
      "modified": 1705412520
    }
  ]
}
```

### Get Media File (OCS API)

```
GET /ocs/v2.php/apps/intravox/api/v1/pages/{pageId}/media/{filename}
```

Returns the media file content.

---

## Versioning API

### Get Page Versions

```
GET /api/pages/{pageId}/versions
```

Returns version history for a page.

**Response:**
```json
[
  {
    "timestamp": 1705412520,
    "date": "2025-01-16T14:22:00Z",
    "author": "admin",
    "label": "Published version"
  },
  {
    "timestamp": 1705326120,
    "date": "2025-01-15T14:22:00Z",
    "author": "editor",
    "label": null
  }
]
```

### Restore Version

```
POST /api/pages/{pageId}/versions/{timestamp}
```

Restores a page to a previous version.

### Get Version Content

```
GET /api/pages/{pageId}/versions/{timestamp}/content
```

Returns the content of a specific version.

### Update Version Label

```
PUT /api/pages/{pageId}/versions/{timestamp}/label
```

Updates the label/name of a version (for named snapshots).

**Request Body:**
```json
{
  "label": "Before major redesign"
}
```

**Response:**
```json
{
  "success": true,
  "timestamp": 1705412520,
  "label": "Before major redesign"
}
```

---

## Comments API

### Get Comments

```
GET /api/pages/{pageId}/comments
```

**Query Parameters:**
- `limit` (int, optional): Maximum comments to return (default: 50)
- `offset` (int, optional): Pagination offset

**Response:**
```json
{
  "comments": [
    {
      "id": 123,
      "message": "Great page!",
      "authorId": "user1",
      "authorName": "John Doe",
      "createdAt": "2025-01-16T10:00:00Z",
      "parentId": null,
      "replies": []
    }
  ],
  "total": 15
}
```

### Create Comment

```
POST /api/pages/{pageId}/comments
```

**Request Body:**
```json
{
  "message": "This is my comment",
  "parentId": null
}
```

Set `parentId` to another comment ID to create a reply.

### Update Comment

```
PUT /api/comments/{commentId}
```

**Request Body:**
```json
{
  "message": "Updated comment text"
}
```

Only the comment author can update their comments.

### Delete Comment

```
DELETE /api/comments/{commentId}
```

Comment authors and admins can delete comments.

---

## Reactions API

### Page Reactions

```
GET /api/pages/{pageId}/reactions
POST /api/pages/{pageId}/reactions/{emoji}
DELETE /api/pages/{pageId}/reactions/{emoji}
```

**GET Response:**
```json
{
  "reactions": {
    "👍": ["user1", "user2"],
    "❤️": ["user3"]
  },
  "userReactions": ["👍"]
}
```

### Comment Reactions

```
GET /api/comments/{commentId}/reactions
POST /api/comments/{commentId}/reactions/{emoji}
DELETE /api/comments/{commentId}/reactions/{emoji}
```

---

## Analytics API

### Track Page View

```
POST /api/analytics/track/{pageId}
```

Tracks a page view. Called automatically by the frontend. Requires read access to the page.

**Response:**
```json
{
  "success": true
}
```

### Get Page Statistics

```
GET /api/analytics/page/{pageId}
```

**Query Parameters:**
- `days` (int, optional): Number of days to include (default: 30, max: 365)

**Response:**
```json
{
  "success": true,
  "pageId": "page-abc123",
  "period": 30,
  "totalViews": 150,
  "totalUniqueUsers": 42,
  "dailyStats": [
    {
      "date": "2025-01-15",
      "views": 25,
      "uniqueUsers": 12
    }
  ]
}
```

### Get Top Pages

```
GET /api/analytics/top
```

**Query Parameters:**
- `limit` (int, optional): Maximum pages to return (default: 10, max: 50)
- `days` (int, optional): Period to consider (default: 30, max: 365)

**Response:**
```json
{
  "success": true,
  "period": 30,
  "pages": [
    {
      "pageId": "page-abc123",
      "title": "Popular Page",
      "path": "/news/popular",
      "totalViews": 500,
      "totalUniqueUsers": 150
    }
  ]
}
```

### Get Dashboard Statistics

```
GET /api/analytics/dashboard
```

Returns aggregated statistics for the analytics dashboard. **Admin only.**

**Query Parameters:**
- `days` (int, optional): Period to include (default: 30, max: 365)

**Response:**
```json
{
  "success": true,
  "period": 30,
  "totalViews": 5000,
  "totalPagesViewed": 45,
  "dailyStats": [...],
  "topPages": [...]
}
```

### Analytics Settings (Admin Only)

```
GET /api/analytics/settings
POST /api/analytics/settings
```

**GET Response:**
```json
{
  "success": true,
  "enabled": true,
  "retentionDays": 90
}
```

**POST Request Body:**
```json
{
  "enabled": true,
  "retentionDays": 90
}
```

**POST Response:**
```json
{
  "success": true,
  "enabled": true,
  "retentionDays": 90
}
```

---

## Bulk Operations API

All bulk operations require **admin privileges**. Non-admin users receive HTTP 403.

**Operation Limits:** Maximum 100 pages per bulk operation to prevent server overload.

### Validate Operation

```
POST /api/bulk/validate
```

Validates a bulk operation without executing it (dry-run).

**Request Body:**
```json
{
  "pageIds": ["page-1", "page-2", "page-3"],
  "operation": "delete"
}
```

**Response:**
```json
{
  "success": true,
  "operation": "delete",
  "total": 3,
  "canProceed": 2,
  "willFail": 1,
  "valid": [
    {"pageId": "page-1", "title": "Page 1"},
    {"pageId": "page-2", "title": "Page 2"}
  ],
  "invalid": [
    {"pageId": "page-3", "reason": "Permission denied"}
  ]
}
```

### Bulk Delete

```
POST /api/bulk/delete
```

**Request Body:**
```json
{
  "pageIds": ["page-1", "page-2"],
  "deleteChildren": true,
  "dryRun": false
}
```

**Response:**
```json
{
  "success": true,
  "operation": "delete",
  "total": 2,
  "deleted": 2,
  "failed": 0,
  "results": [
    {"pageId": "page-1", "title": "Page 1", "status": "deleted"},
    {"pageId": "page-2", "title": "Page 2", "status": "deleted"}
  ],
  "errors": []
}
```

### Bulk Move

```
POST /api/bulk/move
```

**Request Body:**
```json
{
  "pageIds": ["page-1", "page-2"],
  "targetParentId": "page-parent",
  "dryRun": false
}
```

### Bulk Update

```
POST /api/bulk/update
```

**Request Body:**
```json
{
  "pageIds": ["page-1", "page-2"],
  "updates": {
    "metadata": {
      "category": "archived"
    }
  },
  "dryRun": false
}
```

---

## Navigation & Footer API

### Get Navigation

```
GET /api/navigation
```

Returns the custom navigation configuration.

### Save Navigation

```
POST /api/navigation
```

Updates the navigation configuration (requires write permission).

### Get Footer

```
GET /api/footer
```

### Save Footer

```
POST /api/footer
```

---

## Settings API

### Video Domains

```
GET /api/settings/video-domains
POST /api/settings/video-domains
```

Get or set allowed video embed domains.

**GET Response:**
```json
{
  "domains": ["youtube.com", "vimeo.com", "youtu.be"]
}
```

**POST Request Body:**
```json
{
  "domains": ["youtube.com", "vimeo.com", "dailymotion.com"]
}
```

### Upload Limit

```
GET /api/settings/upload-limit
```

Returns the maximum file upload size.

**Response:**
```json
{
  "limit": 104857600,
  "limitFormatted": "100 MB"
}
```

### Engagement Settings

```
GET /api/settings/engagement
POST /api/settings/engagement
```

Configure page engagement features (comments, reactions).

**Response:**
```json
{
  "commentsEnabled": true,
  "reactionsEnabled": true
}
```

### Publication Settings

```
GET /api/settings/publication
POST /api/settings/publication
```

Configure publication workflow settings.

**Response:**
```json
{
  "requireApproval": false,
  "defaultStatus": "published"
}
```

---

## Page Metadata API

### Get Page Metadata

```
GET /api/pages/{pageId}/metadata
```

Returns metadata for a page without the full content.

**Response:**
```json
{
  "id": "page-abc123",
  "title": "Page Title",
  "author": "admin",
  "createdAt": "2025-01-15T10:00:00Z",
  "updatedAt": "2025-01-16T14:00:00Z",
  "category": "news",
  "tags": ["important", "announcement"]
}
```

### Update Page Metadata

```
PUT /api/pages/{pageId}/metadata
```

Updates only the metadata of a page.

**Request Body:**
```json
{
  "category": "archived",
  "tags": ["old", "archived"]
}
```

### Get Page Content

```
GET /api/pages/{pageId}/content
```

Returns only the content/layout of a page (without metadata).

### Get Breadcrumb

```
GET /api/pages/{id}/breadcrumb
```

Returns the breadcrumb path for a page.

**Response:**
```json
[
  {"id": "page-root", "title": "Home", "path": "/"},
  {"id": "page-parent", "title": "Parent Page", "path": "/parent"},
  {"id": "page-current", "title": "Current Page", "path": "/parent/current"}
]
```

### Check Cache Status

```
GET /api/page/{pageId}/cache-status
```

Returns cache status information for a page.

**Response:**
```json
{
  "cached": true,
  "lastModified": "2025-01-16T14:00:00Z",
  "etag": "abc123"
}
```

---

## News API

### Get News Items

```
GET /api/news
```

Returns news items from pages marked as news.

**Query Parameters:**
- `limit` (int, optional): Maximum items to return (default: 10)
- `sourcePath` (string, optional): Filter by source path

**Response:**
```json
[
  {
    "id": "page-news-1",
    "title": "Latest Announcement",
    "excerpt": "This is an important...",
    "image": "banner.jpg",
    "date": "2025-01-16T10:00:00Z",
    "path": "/news/announcement"
  }
]
```

---

## Resources API

### Get Resource Media

```
GET /api/resources/media/{filename}
GET /api/resources/media/{folder}/{filename}
```

Returns shared resource files (backgrounds, icons, etc.).

**Examples:**
```
GET /api/resources/media/logo.svg
GET /api/resources/media/backgrounds/header-bg.jpg
```

---

## Permissions API

### Get User Permissions

```
GET /api/permissions
```

Returns the current user's permissions in IntraVox.

**Response:**
```json
{
  "isAdmin": true,
  "canCreatePages": true,
  "canEditNavigation": true,
  "canImport": true,
  "canExport": true,
  "canManageSettings": true
}
```

---

## MetaVox Integration API

### Get MetaVox Status

```
GET /api/metavox/status
```

Checks if MetaVox is installed and enabled.

**Response:**
```json
{
  "installed": true,
  "enabled": true,
  "version": "1.2.0"
}
```

### Get MetaVox Fields

```
GET /api/metavox/fields
```

Returns available MetaVox metadata fields for IntraVox pages.

**Response:**
```json
{
  "fields": [
    {"name": "department", "type": "select", "options": ["IT", "HR", "Finance"]},
    {"name": "owner", "type": "user"},
    {"name": "expiryDate", "type": "date"}
  ]
}
```

---

## Setup & Demo Data API

### Run Setup

```
POST /api/setup
```

Initializes IntraVox (creates folder structure, default pages). Admin only.

**Response:**
```json
{
  "success": true,
  "message": "IntraVox setup completed"
}
```

### Get Demo Data Status

```
GET /api/demo-data/status
```

Checks if demo data is installed.

**Response:**
```json
{
  "installed": false,
  "availableLanguages": ["en", "nl", "de"]
}
```

### Get Available Languages

```
GET /api/demo-data/languages
```

Returns languages available for demo data import.

### Import Demo Data

```
POST /api/demo-data/import
```

Imports demo data for a specific language.

**Request Body:**
```json
{
  "language": "en"
}
```

### Clean Start

```
POST /api/demo-data/clean-start
```

Removes all IntraVox content and starts fresh. Admin only.

---

## Search API

### Search Pages

```
GET /api/search?q={query}
```

**Query Parameters:**
- `q` (string): Search query

**Response:**
```json
[
  {
    "id": "page-abc123",
    "title": "Matching Page",
    "excerpt": "...text containing the <mark>search</mark> term..."
  }
]
```

---

## Export/Import API

### Get Exportable Languages

```
GET /api/export/languages
```

Returns a list of languages that can be exported.

**Response:**
```json
{
  "languages": ["en", "nl", "de"]
}
```

### Export Language

```
GET /api/export/language/{language}
GET /api/export/language/{language}/zip
```

Exports all pages for a language as JSON or ZIP.

### Export Single Page

```
GET /api/export/page/{uniqueId}
```

### Import ZIP

```
POST /api/import/zip
```

Imports pages from a ZIP file (requires admin).

### Import from Confluence

```
POST /api/import/confluence/html
```

Imports pages from a Confluence HTML export ZIP file.

**Content-Type:** `multipart/form-data`

**Form Fields:**
- `file`: The Confluence HTML export ZIP file
- `language` (string, optional): Target language (default: "nl")
- `parentPageId` (string, optional): Parent page ID for imported pages

---

## Error Codes

| Code | Meaning | Example |
|------|---------|---------|
| 200 | Success | Request completed |
| 201 | Created | Page/comment created |
| 207 | Multi-Status | Partial success in bulk operations |
| 400 | Bad Request | Invalid parameters, validation error |
| 403 | Forbidden | No permission, admin required |
| 404 | Not Found | Page/resource doesn't exist |
| 429 | Too Many Requests | Rate limit exceeded |
| 500 | Internal Server Error | Server-side error (check errorId in response) |

---

## Security

### Admin-Only Endpoints

The following endpoints require **system administrator** privileges:

| Endpoint | Purpose |
|----------|---------|
| `POST /api/bulk/*` | Bulk operations (delete, move, update) |
| `POST /api/import/*` | ZIP and Confluence imports |
| `POST /api/setup` | Initial setup |
| `POST /api/settings/*` | Video domains, engagement, publication settings |
| `GET /api/analytics/dashboard` | Analytics dashboard |
| `POST /api/analytics/settings` | Analytics configuration |
| `POST /api/demo-data/*` | Demo data management |

### File Upload Security

- **Extension whitelist:** Only allowed extensions: jpg, jpeg, png, gif, webp, svg, mp4, webm, ogg
- **MIME type validation:** Files are validated using both `finfo` and `getimagesize()` for images
- **SVG sanitization:** SVG files are sanitized to remove scripts, iframes, and other dangerous content
- **Size limits:** Respects PHP and Nextcloud upload limits

### Path Traversal Protection

All path parameters are validated against:
- Null byte injection
- URL-encoded traversal patterns (../)
- Double-encoding attacks
- Unicode normalization attacks

---

## Rate Limiting

The API uses Nextcloud's built-in brute force protection. For heavy integrations, consider implementing client-side throttling to avoid triggering rate limits.

## Migration Tool Integration

For migration tools (SharePoint, Confluence, custom CMS) that need to bulk-import pages with media, we recommend the following Nextcloud-supported approach:

### Recommended Workflow

```
┌─────────────────────────────────────────────────────────────────┐
│  Migration Tool                                                  │
├─────────────────────────────────────────────────────────────────┤
│  1. Create page via OCS API                                     │
│     POST /ocs/v2.php/apps/intravox/api/v1/pages                │
│     → Returns pageId and path                                   │
│                                                                  │
│  2. Upload media via WebDAV (Nextcloud native)                  │
│     PUT /remote.php/dav/files/{user}/{path}/_media/{filename}  │
│     → Standard Nextcloud file upload                            │
│                                                                  │
│  3. Update page content with media references                   │
│     - Edit page.json via WebDAV, or                            │
│     - Use OCS PUT endpoint (when supported)                     │
└─────────────────────────────────────────────────────────────────┘
```

### Why WebDAV for Media Uploads?

| Aspect | WebDAV |
|--------|--------|
| **Stability** | Core Nextcloud feature since version 1 |
| **Documentation** | Officially documented at docs.nextcloud.com |
| **Large files** | Chunked upload support built-in |
| **Authentication** | App passwords (secure, revocable) |
| **Reliability** | Used by all official Nextcloud clients |

### Complete Migration Example

```bash
#!/bin/bash
# Example: Migrate a SharePoint page with images to IntraVox

SERVER="https://your-nextcloud.com"
USER="admin"
APP_PASSWORD="your-app-password"
AUTH="$USER:$APP_PASSWORD"

# Step 1: Create the page via OCS API
PAGE_RESPONSE=$(curl -s -X POST \
  -u "$AUTH" \
  -H "Content-Type: application/json" \
  -H "OCS-APIREQUEST: true" \
  -d '{
    "id": "migrated-sharepoint-page",
    "title": "Migrated from SharePoint",
    "uniqueId": "page-sp-migration-001",
    "layout": {
      "columns": 1,
      "rows": [
        {
          "columns": 1,
          "widgets": [
            {
              "type": "heading",
              "column": 1,
              "order": 1,
              "content": "Migrated Page",
              "level": 1
            },
            {
              "type": "image",
              "column": 1,
              "order": 2,
              "src": "header-banner.jpg",
              "alt": "Header image"
            },
            {
              "type": "text",
              "column": 1,
              "order": 3,
              "content": "Content migrated from SharePoint."
            }
          ]
        }
      ]
    }
  }' \
  "$SERVER/ocs/v2.php/apps/intravox/api/v1/pages")

echo "Page created: $PAGE_RESPONSE"

# Step 2: Upload media via WebDAV
# The _media folder is auto-created when the page is created
curl -X PUT \
  -u "$AUTH" \
  --data-binary @/path/to/header-banner.jpg \
  -H "Content-Type: image/jpeg" \
  "$SERVER/remote.php/dav/files/$USER/IntraVox/en/migrated-sharepoint-page/_media/header-banner.jpg"

echo "Media uploaded successfully"
```

### WebDAV Chunked Upload (Large Files)

For files larger than 10MB, use Nextcloud's chunked upload:

```bash
# Initialize chunked upload
UPLOAD_DIR=".upload-$(uuidgen)"
curl -X MKCOL \
  -u "$AUTH" \
  "$SERVER/remote.php/dav/uploads/$USER/$UPLOAD_DIR"

# Upload chunks (5MB each)
split -b 5242880 large-video.mp4 chunk_
for chunk in chunk_*; do
  curl -X PUT \
    -u "$AUTH" \
    --data-binary @$chunk \
    "$SERVER/remote.php/dav/uploads/$USER/$UPLOAD_DIR/$chunk"
done

# Assemble the file
curl -X MOVE \
  -u "$AUTH" \
  -H "Destination: $SERVER/remote.php/dav/files/$USER/IntraVox/en/page/_media/large-video.mp4" \
  "$SERVER/remote.php/dav/uploads/$USER/$UPLOAD_DIR/.file"
```

### Media Path Structure

IntraVox stores media in a predictable structure:

```
/IntraVox/
├── en/                          # Language folder
│   └── page-folder/             # Page folder (same as page ID)
│       ├── page-folder.json     # Page content
│       └── _media/              # Media folder
│           ├── .nomedia         # Marker file (auto-created)
│           ├── header.jpg       # Uploaded images
│           └── video.mp4        # Uploaded videos
```

### Authentication Recommendations

1. **Create dedicated app password** for migration tools:
   - Settings → Security → Devices & sessions
   - Create app password with descriptive name ("SharePoint Migration Tool")

2. **Use service account** for automated migrations:
   - Create dedicated Nextcloud user for migrations
   - Assign appropriate GroupFolder permissions

3. **Revoke after completion**:
   - App passwords can be revoked without affecting user account

---

## Privacy

Analytics data is privacy-conscious:
- User IDs are hashed before storage
- Only daily aggregates are stored, not individual page views
- Data is automatically cleaned up based on retention settings (default: 90 days)
