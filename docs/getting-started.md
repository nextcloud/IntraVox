# Getting Started with IntraVox

IntraVox is a Nextcloud app that provides a SharePoint-like intranet platform on top of Nextcloud Files: drag-and-drop pages, widgets, multi-language content, and reader engagement (reactions, comments) — all stored as folders and files inside Nextcloud, inheriting its sharing, ACL, versioning, and audit.

This guide helps you get started quickly based on your role.

## What is IntraVox?

IntraVox publishes organizational communication content — company news, HR policies, team portals, official documentation — to a broad internal audience. Pages are built from widgets (text, news, calendar, people, feeds, photo stories) using a drag-and-drop editor. Readers engage with reactions and comments.

Unlike Nextcloud Collectives (which is for horizontal team collaboration on wiki-style content), IntraVox is a **top-down communication platform** — communications teams publish, the organization reads. See [Collectives Comparison](architecture/considerations/collectives-comparison.md) for the full distinction.

## Quick Start by Role

### Users

1. Open IntraVox from the Nextcloud app menu
2. Browse pages via the sidebar navigation
3. To edit a page (if you have write access), click the edit button and use the [drag-and-drop editor](user/editor.md)
4. Add reactions or comments to pages via the engagement panel

See the [User Overview](user/overview.md) and [Editor Guide](user/editor.md) for detailed instructions.

### Administrators

1. Install IntraVox from the Nextcloud App Store (or via `occ app:install intravox`)
2. Configure the GroupFolder that will hold IntraVox content (one per language is recommended)
3. Open **Settings → Administration → IntraVox** to configure permissions and engagement defaults
4. Set up [authorization](admin/authorization.md) using GroupFolder ACL
5. Optionally import existing content from [Confluence](features/confluence-import.md)

See the [Admin Guide](admin/guide.md) and [Scenarios](admin/scenarios.md) for common deployment patterns.

### Architects

Before evaluating IntraVox at scale, read:

- [Architecture Overview](architecture/overview.md) — system design, components, technology stack
- [Nextcloud-Native Architecture](architecture/considerations/nextcloud-native-architecture.md) — why "page = folder" inherits a dozen enterprise features from Nextcloud at zero implementation cost
- [Scalability](admin/scalability.md) — capacity planning, caching, and tuning
- [Security](admin/security.md) — security model and sanitization layers

## Key Concepts

| Concept | Description |
|---|---|
| **Page** | A folder in a Nextcloud GroupFolder containing a JSON content file and an optional `_media/` subfolder. Sub-pages are sub-folders. |
| **Widget** | The building block of a page — text, image, video, news, people, calendar, feed, photo story, etc. |
| **Layout** | Pages are composed of rows; each row contains 1–5 column-aligned widgets. |
| **GroupFolder** | The Nextcloud storage and ACL container that holds IntraVox content. Permissions inherit from GroupFolder ACL. |
| **Language Folder** | Top-level folders (`nl/`, `en/`, `de/`, `fr/`) that separate content per language. |
| **Engagement** | Reactions and comments attached to pages via Nextcloud's native Comments API. |
| **Template** | A page that serves as a starting point for new pages, instantiated via the [Template API](architecture/template-api-quickstart.md). |

## Architectural Highlights

- **Pages are folders** in the Nextcloud filesystem — they inherit ACL, sharing, versioning, audit, WebDAV access, encryption, external storage, trash, and quota from Nextcloud. See [Nextcloud-Native Architecture](architecture/considerations/nextcloud-native-architecture.md).
- **Native NC integration** — uses NC's Comments, Activity, Search, Versions, Notifications, GroupFolder ACL, and event system rather than reimplementing them.
- **Optional MetaVox integration** for structured, typed metadata on pages that flows across all content types in Nextcloud (documents, photos, forms). See [Export & Import](admin/export-import.md).

## Next Steps

- [User Overview](user/overview.md) — Working with IntraVox daily
- [Admin Guide](admin/guide.md) — Setup and configuration
- [API Reference](architecture/api-reference.md) — Programmatic access
- [Scenarios](admin/scenarios.md) — Practical deployment recipes

## See Also

- [Architecture Overview](architecture/overview.md) — System design and internals
- [Authorization Guide](admin/authorization.md) — Permissions and roles
- [Scalability](admin/scalability.md) — Performance at scale
