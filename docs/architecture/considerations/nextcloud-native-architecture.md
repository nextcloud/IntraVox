# IntraVox Nextcloud-Native Architecture

> **Document type**: Architectural consideration
> **Purpose**: Explain how IntraVox's "page = folder" model and deep Nextcloud integration inherit twelve enterprise features at zero implementation cost — a key differentiator versus stand-alone wikis like Confluence, XWiki, or BookStack.
> **Audience**: Architects, enterprise procurement, technical decision-makers evaluating IntraVox against Confluence-replacement candidates.

---

## Executive Summary

IntraVox is not "a wiki built on top of Nextcloud" — it is **Nextcloud Files used as a wiki**, with IntraVox providing the presentation, editor, and collaboration layer on top.

The core architectural decision: **every IntraVox page is a folder in the Nextcloud filesystem**. Sub-pages are sub-folders. Attachments live in a `_media/` sub-folder. Page content is stored as a JSON file inside the page-folder. This single decision means IntraVox inherits a dozen enterprise-grade features that competing wiki platforms have to build, maintain, and harden themselves.

| Capability | IntraVox approach | Confluence / XWiki / BookStack approach |
|---|---|---|
| Per-page access control | Inherits GroupFolder ACL per page-folder | Custom permission system |
| Sharing (link/user/expiry/password) | Inherits Nextcloud native sharing | Custom sharing system |
| Version history | Inherits Nextcloud Files_Versions | Custom revision store |
| Audit log of changes | Inherits Nextcloud audit app | Custom audit table |
| WebDAV / desktop sync | Pages accessible via NC desktop client | Not available, or HTTP-only API |
| Backup & restore | Inherits Nextcloud backup tooling | Custom dump/restore |
| Encryption at rest | Inherits Nextcloud server-side encryption | Custom or absent |
| External storage (S3, SMB, SFTP) | Pages can live on any NC external storage | Database-bound, single backend |
| Trash & undelete | Inherits Nextcloud trashbin | Custom soft-delete |
| Storage quota enforcement | Inherits Nextcloud quota | Custom or absent |
| Full-text search (incl. ACL filtering) | Hookable into NC `fulltextsearch` + Elasticsearch | Custom search index |
| GDPR right-to-erasure on user delete | UserDeletedListener cleans IntraVox data automatically | Custom cleanup logic |

This document explains how the architecture works, which Nextcloud interfaces IntraVox implements, and what this means for organizations evaluating IntraVox at scale (e.g. SURF, federated research/education environments, multi-tenant intranet deployments).

---

## The "page = folder" model

### Storage layout

A new IntraVox page named "about-us" is created by [`PageService::saveNewPage()`](../../lib/Service/PageService.php) which calls:

```php
$pageFolder = $targetFolder->newFolder($pageId);       // creates folder
$file = $pageFolder->newFile($pageId . '.json');       // creates content file inside
$file->putContent(json_encode($data, ...));
```

A page tree on disk looks like this:

```
IntraVox/                           ← GroupFolder root
├── nl/                             ← language folder
│   ├── home/
│   │   ├── home.json               ← page content (widgets, layout)
│   │   └── _media/                 ← attachments, images
│   ├── about-us/
│   │   ├── about-us.json
│   │   └── _media/
│   └── products/
│       ├── products.json
│       ├── _media/
│       └── product-1/              ← sub-page = sub-folder
│           ├── product-1.json
│           └── _media/
└── en/
    └── ...
```

Page nesting is **recursive and effectively unbounded** — limited only by filesystem path length and Nextcloud's own folder-depth handling.

### Why this matters

Because every page is a real folder in the Nextcloud filesystem:

1. **It has a real `file_id` in `oc_filecache`** — Nextcloud knows about it as a first-class filesystem object.
2. **It can be shared, secured, versioned, and audited by Nextcloud's own subsystems** — IntraVox does not need to re-implement these.
3. **It is reachable via WebDAV** — administrators and integrators can interact with pages via standard protocols.
4. **It survives outside IntraVox** — if IntraVox were disabled, the pages remain as readable folders with JSON content. There is no proprietary database that becomes opaque.

---

## Inherited enterprise features

### 1. Per-page Access Control (GroupFolder ACL)

[`PermissionService`](../../lib/Service/PermissionService.php) delegates permission checks to Nextcloud's GroupFolders ACL system. Because each page is a folder, each page can have its own ACL rules — read/write/share/delete bits per user or group, with inheritance from parent folders.

This means an administrator can:

- Grant department A read access to `nl/policies/` but restrict `nl/policies/board-only/` to a specific group
- Allow editor role on a page-tree branch while keeping siblings read-only
- Combine with Nextcloud Workflow rules for advanced governance

No custom IntraVox permission model is required. The same ACL UI that administrators already know from GroupFolders applies to IntraVox pages.

### 2. Native Sharing

Right-clicking a page-folder in the Nextcloud Files app yields the standard sharing dialog: share with user, group, federated cloud, public link with expiry, password protection, link permissions. IntraVox additionally provides a [`PublicShareController`](../../lib/Controller/PublicShareController.php) and [`PUBLIC_SHARING.md`](../../user/public-sharing.md) for IntraVox-aware presentation of public-shared pages, but the underlying sharing mechanism is Nextcloud's.

### 3. Version History

Page content saves emit standard Nextcloud file-write events, which trigger Nextcloud's [Files_Versions](https://docs.nextcloud.com/server/latest/user_manual/en/files/version_control.html) app. IntraVox additionally exposes versions through its REST API (`GET /api/pages/{id}/versions`, `POST /api/pages/{id}/versions/{version}/restore`) and provides a versioning UI documented in [`VERSIONING.md`](../../user/versioning.md), but the version store itself is the standard Nextcloud version store.

Operational consequences:

- Version retention follows Nextcloud's configured retention policy
- Versions are storage-tier aware (S3 backends store versions on S3)
- Administrators can prune versions via Nextcloud's standard tools

### 4. Audit Log

Page file-write events are captured by the Nextcloud `admin_audit` app if installed. This means every page change generates an audit-log entry in the same log stream that captures file operations, login events, and admin actions — a single source of truth for compliance reviews.

### 5. WebDAV / Desktop Sync

Because pages are real folders, they are accessible via:

- **WebDAV** (`https://nc.example.org/remote.php/dav/files/{user}/IntraVox/nl/about-us/`)
- **Nextcloud desktop client** (selective sync of an IntraVox subtree)
- **Mobile Nextcloud apps** (browse/preview)

This enables bulk operations, scripted maintenance, and offline read access that database-bound wikis cannot offer.

### 6. Backup and Restore

Any Nextcloud-aware backup solution (BorgBackup of `data/`, NC's built-in backup app, snapshot-based volume backups) captures IntraVox pages as part of the normal Nextcloud data. There is no separate IntraVox dump required, no schema versioning concern, no risk of database/filesystem desync.

Restore is symmetric: restore the GroupFolder, and pages reappear.

### 7. Encryption at Rest

Nextcloud's server-side encryption operates at the storage layer. IntraVox pages are encrypted by the same mechanism that encrypts every other file in Nextcloud. No IntraVox-specific encryption work is required.

### 8. External Storage

IntraVox pages can live on any Nextcloud-supported external storage: S3, SMB/CIFS, SFTP, FTP, WebDAV, OpenStack Swift, Google Drive (read-only), and others. An organization with strict data residency requirements can pin a GroupFolder to a sovereign-region S3 bucket, and IntraVox pages stored there inherit that residency automatically.

### 9. Trash and Undelete

Deleted pages enter Nextcloud's trashbin. Users can restore individual pages or whole sub-trees via the standard Files trashbin UI. Retention is governed by Nextcloud's standard `trashbin_retention_obligation`.

### 10. Storage Quota

Nextcloud quota policies apply to IntraVox content. A GroupFolder with a 100 GB quota will block new page-attachment uploads when full, using the same enforcement Nextcloud applies to ordinary files.

### 11. Full-Text Search (with ACL filtering)

Because page content is a real file in Nextcloud's filesystem, the [`fulltextsearch`](https://github.com/nextcloud/fulltextsearch) app (with Elasticsearch backend) can index page JSON content alongside other documents. This yields:

- ACL-aware search results (users see only pages they may read)
- Search across pages, files, mail, and other indexed content in a single query
- Standard Elasticsearch tuning, relevance ranking, and faceting

IntraVox additionally registers a [`PageSearchProvider`](../../lib/Search/PageSearchProvider.php) for Nextcloud's Unified Search (Ctrl+K), which provides instant title-level search via a lightweight DB index (`intravox_page_index`). The two layers complement each other.

### 12. GDPR Right-to-Erasure

When a Nextcloud user is deleted, IntraVox's [`UserDeletedListener`](../../lib/Listener/) automatically cleans up that user's IntraVox-specific data: analytics records, page locks, RSS feed tokens. The page content itself, being a Nextcloud file, follows Nextcloud's own user-deletion semantics.

---

## Nextcloud interfaces implemented

IntraVox's deep integration is visible in [`Application.php`](../../lib/AppInfo/Application.php), which registers the following:

| NC interface | IntraVox implementation | Effect |
|---|---|---|
| `OCP\Search\IProvider` | `PageSearchProvider`, `UserSearchProvider` | Pages and people appear in Unified Search |
| `OCP\Activity\IProvider` | `Activity\Provider` | Page events appear in NC Activity stream |
| `OCP\EventDispatcher\IEventListener` (×4) | `CommentsEntityListener`, `PageDeletedListener`, `UserDeletedListener`, `GroupMembershipChangedListener` | Reactive cache invalidation, GDPR cleanup |
| `OCP\Settings\IDelegatedSettings` | `AdminSettings` | Admin panel under NC settings, delegatable to non-admin roles |
| `OCP\AppFramework\Bootstrap\IBootstrap` | `Application` | Standard NC bootstrap lifecycle |
| `OCP\Comments\ICommentsManager` | (consumed) | Native NC comments used on pages, no custom comment store |
| `OCP\Files\Versions\IVersionManager` | (consumed) | Native NC file versions used for page versioning |
| `OCP\Files\IRootFolder`, `OCA\GroupFolders\Folder\FolderManager` | (consumed) | Pages stored in GroupFolders with ACL enforcement |

This is not "a Nextcloud app that uses NC for auth" — it is a Nextcloud app that participates in the platform's core subsystems.

---

## What IntraVox adds on top

The Nextcloud-native foundation handles storage, permissions, versions, sharing, audit, backup, search, and quota. IntraVox concentrates its own engineering effort on what Nextcloud does not provide for intranet/wiki use cases:

- **Drag-and-drop page editor** (Vue 3 + TipTap, see [`EDITOR_GUIDE.md`](../../user/editor.md))
- **Widget system** (News, People, Calendar, Feed, PhotoStory, Video, Links — see widget-specific docs)
- **Page layout model** (grid-based rows/columns within a page)
- **Multilingual page tree** (language-folder hierarchy: `nl/`, `en/`, `de/`, `fr/`)
- **Reader engagement** (reactions, comments wired to NC Comments — see [`ENGAGEMENT_ARCHITECTURE.md`](../../features/engagement-architecture.md))
- **Templates** (page-from-template via API, see [`TEMPLATES.md`](../../user/templates.md))
- **RSS feeds with token auth** ([`RSS_FEED.md`](../../user/rss-feeds.md))
- **External-system feed integration** (Moodle, Jira, SharePoint, ICS — see [`FEED_WIDGET.md`](../../features/feed-widget.md))
- **Confluence import** ([`CONFLUENCE_IMPLEMENTATION.md`](../../features/confluence-import.md))
- **Distributed caching** (Redis/APCu, request-scoped + static, with group-keyed bucket invalidation)
- **Background jobs** (cache warmup, feed refresh, geocode warmup, telemetry)
- **Bulk operations** for administrators (`/api/bulk/*`)
- **Health endpoint** for orchestration (`/api/health`)
- **Structured metadata via MetaVox** (optional companion app — see next section)

This is the IntraVox value-add. Everything underneath is Nextcloud.

---

## Structured metadata via MetaVox (beyond Systemtags)

Nextcloud ships with two metadata mechanisms out of the box: **Systemtags** (flat string labels attached to files) and **file comments**. Both are useful, but neither provides what enterprise intranet/wiki content typically needs: **typed, validated, queryable fields that can be scoped to a context**.

IntraVox optionally integrates with [MetaVox](https://github.com/voxcloud/metavox), a separate VoxCloud companion app that adds structured metadata to Nextcloud GroupFolders. The integration is detected at runtime ([`MetaVoxImportService`](../../lib/Service/MetaVoxImportService.php), `$appManager->isInstalled('metavox')`) and degrades gracefully when MetaVox is not installed.

### Why structured metadata, not just tags

| Capability | Nextcloud Systemtags | MetaVox fields |
|---|---|---|
| Data model | Flat string labels, one namespace | Typed fields: select, multiselect, date, text |
| Validation | None — any string is valid | Per-field validation (allowed values, required, format) |
| Scoping | Global across all files | Per-GroupFolder (department/space-scoped definitions) |
| Querying | "Has tag X" / "lacks tag X" | `equals`, `contains`, `in`, `year_equals`, range, faceted |
| Storage | `oc_systemtag` + `oc_systemtag_object_mapping` | `oc_metavox_gf_fields` + `oc_metavox_file_gf_meta` |
| Machine readability | Tag-name string | Structured field/value pairs with type info |
| Edit permissions | Tag-create permission system-wide | Inherits file permissions (no separate ACL surface) |
| Export/import | Manual | JSON schema (EXPORT-IMPORT v1.3) with version tracking |
| Search integration | NC Unified Search | NC Unified Search + cross-folder MetaVox queries |

The key distinction: Systemtags answer **"is this file relevant to topic X?"** MetaVox answers **"what is the value of field Y for this file?"** — which is the difference between a tag and a database column.

### Concrete uses in IntraVox

When MetaVox is installed alongside IntraVox, page-folders gain typed metadata fields that flow through several IntraVox features:

1. **Page classification** — type, department, classification level, expiry/review date, content owner. Defined once per GroupFolder, applied to every page in that GroupFolder.
2. **PhotoStory filtering and sorting** — [`PhotoStoryService::getMetaVoxFieldsForPhotoStory()`](../../lib/Service/PhotoStoryService.php) consumes MetaVox fields to drive cross-folder photo queries: "show photos where `subject = Annual Conference` and `year_equals 2025`". This is impossible with Systemtags because Systemtags have no `year_equals` operator and no concept of a typed date field.
3. **Cross-folder content queries** — MetaVox queries traverse multiple GroupFolders honoring ACL, enabling intranet patterns like "all HR policies expiring in Q3" across the policy library.
4. **Export/import preservation** — the IntraVox ZIP export schema (v1.3, documented in [`EXPORT-IMPORT.md`](../../admin/export-import.md)) includes MetaVox field definitions and values. Import optionally auto-creates field definitions on the target system (`autoCreateMetaVoxFields` option), enabling clean migration between instances.
5. **News and feed widgets** — can filter on MetaVox fields (priority, category, publication-date) rather than relying on folder structure or filename conventions.

### Why this matters architecturally

IntraVox could have built its own page-properties system: a custom table for `page_id → field → value`, with its own field-definition UI, its own permission model, its own export format, its own search integration. That is what most wiki platforms do (Confluence's "page properties macro", XWiki's "AppWithinMinutes").

Instead, IntraVox delegates structured metadata to MetaVox, which delegates storage and ACL to Nextcloud (because MetaVox fields are themselves attached to NC files via the GroupFolder layer). The result:

- **One metadata layer for the whole Nextcloud installation**, not one per app. A `department` field defined for HR's GroupFolder applies equally to HR's IntraVox pages, HR's documents, HR's photos, HR's spreadsheets.
- **Metadata survives outside IntraVox.** If an organization stops using IntraVox, MetaVox fields on the page-folders remain intact and queryable through MetaVox's own UI or API.
- **No new permission surface to secure.** MetaVox field edit-rights inherit from file permissions, which inherit from GroupFolder ACL. There is no separate "who can edit page properties" ACL to misconfigure.

This is the same architectural principle as the page-as-folder model itself: rather than building a custom system, delegate to a platform-level capability and inherit its security, backup, and operational properties.

### Optionality

MetaVox is **not a required dependency** for IntraVox. Organizations that do not need structured metadata can run IntraVox without MetaVox installed; pages work normally and the metadata-related UI elements simply do not appear. This keeps IntraVox lightweight for simple intranet deployments while enabling enterprise/research metadata patterns for organizations that need them.

### Cross-app metadata: a structural advantage over stand-alone wikis

Stand-alone wiki platforms that offer structured fields (XWiki AppWithinMinutes, Confluence page properties, Notion databases) keep that metadata locked inside the wiki. A `department` or `classification` field defined in XWiki applies to XWiki pages only. A `project` tag in Confluence is invisible to documents stored elsewhere. This creates the classic enterprise metadata problem: every content silo grows its own taxonomy, and the same conceptual field is defined three or four times across different systems with subtly different values.

The IntraVox + MetaVox architecture inverts this. Because MetaVox attaches metadata to **Nextcloud files** (not to IntraVox-specific objects), a single field definition applies uniformly across every content type an organization keeps in Nextcloud:

| Content type | How the same MetaVox field applies |
|---|---|
| IntraVox pages | Page-folder carries the field; visible in IntraVox UI, filterable in News/Feed widgets |
| Office documents (`.docx`, `.odt`, `.xlsx`) | Same field on the file; visible in Files sidebar, filterable in MetaVox views |
| Photos | Same field; PhotoStory widget can filter on it ([`PhotoStoryService`](../../lib/Service/PhotoStoryService.php) uses `getMetaVoxFieldsForPhotoStory()`) |
| FormVox forms and submissions | Same field on form files |
| Email archives, PDFs, CAD files, video, any file type | Same field |

Define `project = ORTESE` once, on the GroupFolder. Every page, document, photo, form, and dataset associated with that project carries the field. Searches, listings, and cross-folder queries return consistent results because there is only one definition and one value-space.

For research and education environments specifically, this matters:

- **A research project page** in IntraVox, **the underlying datasets** as files, **the conference photos** as PhotoStory content, and **the participant intake form** as a FormVox submission all share fields like `project`, `funding-source`, `principal-investigator`, `ethics-approval-date`.
- **A search for "everything related to project X"** returns hits across all these content types, ACL-filtered by who is allowed to see what.
- **An organization-wide retention policy** ("delete anything with `classification = restricted` and `expiry-date < today`") applies uniformly without per-app rule duplication.

XWiki cannot deliver this even with AppWithinMinutes because XWiki's structured data lives in XWiki's database; it does not extend to files, photos, or external content stored alongside the wiki. For organizations whose content lives across multiple categories — which is true of virtually every research institution and most enterprises — this is a structural difference, not a feature-checklist difference.

It is the same architectural pattern as the page-as-folder model: rather than building a wiki-specific metadata silo, IntraVox participates in a platform-wide metadata layer. The cost is that some highly wiki-specific structured-data patterns (in-browser form builder for non-developers, computed/derived fields, cross-page joins in a query language) remain stronger in XWiki. The benefit is that for the much more common pattern of "tag and classify content consistently across an organization", there is one definition, one storage layer, one permission model, and one query surface — not one per app.

---

## Architectural implications at scale

### Multi-tenant federated deployments

In federated environments (e.g. research/education consortia where each institution runs its own Nextcloud), IntraVox deploys naturally: each institution's Nextcloud hosts its own IntraVox, with its own page tree, its own permissions, its own user base, federated via standard Nextcloud Federation or SAML/OIDC SSO.

There is no central IntraVox database, no shared IntraVox cluster, no cross-tenant data leakage risk. The blast radius of any one institution's IntraVox is bounded by their own Nextcloud instance — which is precisely the isolation guarantee that consortium architectures require.

### Sovereignty and data residency

Because IntraVox stores everything in Nextcloud files and inherits Nextcloud's external-storage capabilities, data residency is configurable per-GroupFolder. An organization with strict EU-residency requirements pins their IntraVox GroupFolder to an EU-region S3 bucket; that is sufficient. No IntraVox-specific residency configuration is needed.

### Operational simplicity

Operations teams already running Nextcloud do not need a separate operational runbook for IntraVox. Backups, monitoring, log aggregation, security patching, and upgrade procedures are the same as for the underlying Nextcloud installation. The estimated operational delta of adding IntraVox to an existing Nextcloud deployment is approximately **0 FTE** — versus 0.5–1.0 FTE for operating a separate Java/Tomcat-based wiki platform alongside Nextcloud.

### Migration and exit

If an organization later decides to move off IntraVox, the page content is already in an open, documented JSON format on a standard filesystem. There is no proprietary database to extract, no vendor-specific API to script against. A migration script that reads `*.json` files and transforms them to Markdown, XWiki syntax, or Confluence storage format is straightforward.

This is the inverse of typical wiki-platform lock-in: rather than locking content into a proprietary store, IntraVox stores content in a format that is trivially exportable by design.

---

## Comparison to stand-alone wiki platforms

The following compares IntraVox's Nextcloud-native approach to typical stand-alone wiki platforms (Confluence, XWiki, BookStack, Wiki.js) on architectural dimensions only — not on feature parity or UX maturity.

| Dimension | IntraVox (Nextcloud-native) | Stand-alone wiki |
|---|---|---|
| Storage | Nextcloud filesystem (file_id, oc_filecache) | Proprietary database |
| Permissions | GroupFolder ACL | Custom permission model |
| Sharing | NC native sharing | Custom sharing |
| Versions | NC Files_Versions | Custom revision table |
| Audit | NC admin_audit | Custom audit log |
| Search | NC fulltextsearch + Elasticsearch (ACL-aware) | Custom Lucene/Solr setup |
| Backup | NC backup tooling | Custom dump/restore |
| Encryption at rest | NC server-side encryption | Custom or absent |
| External storage | NC external storage (S3/SMB/SFTP/...) | Single database backend |
| Trash/restore | NC trashbin | Custom soft-delete |
| WebDAV | Yes | No |
| Desktop sync | Yes (NC desktop client) | No |
| Federation | NC Federation | No standard equivalent |
| Structured page metadata | MetaVox (typed fields, scoped, queryable) | Custom page-properties system |
| Operational footprint | Same as existing Nextcloud | Separate stack to operate |
| Exit cost | Files already in open format | Database export + transform |

This is not an argument that IntraVox is "better" than every stand-alone wiki on every dimension — it is an argument that **organizations already invested in Nextcloud** can adopt IntraVox at a fraction of the operational, security-engineering, and vendor-risk cost of a parallel wiki platform.

---

## Caveats and honest limitations

This document focuses on architectural advantages of the page-as-folder model. The following limitations should be considered in any evaluation:

1. **No real-time collaborative editing.** IntraVox uses pessimistic page-locking ([`PageLockService`](../../lib/Service/PageLockService.php)) rather than CRDT/OT-based real-time co-editing. Two users cannot edit the same page simultaneously. Adding real-time editing would require integration of Y.js + a WebSocket layer with TipTap — non-trivial work, estimated at 8–12 weeks.

2. **Unified Search ACL filtering.** The `PageSearchProvider` currently does not filter results by ACL — search hits may surface pages the user cannot open. The full-text search path (via `fulltextsearch` app) does filter correctly. This is a known gap on the title-search path.

3. **No PDF/Markdown export of pages.** Pages export as JSON (in ZIP bundles). PDF or Markdown export would require additional rendering work (e.g. weasyprint integration).

4. **No native approval workflow.** Pages have draft/published states ([`PublicationSettingsService`](../../lib/Service/PublicationSettingsService.php)) but no built-in review/sign-off workflow. NC Workflow rules can be used for some governance scenarios.

5. **No native @mentions in comments or pages.** The infrastructure (NC `INotificationManager`) exists but is not yet wired up. This is comparatively small work (~3–4 weeks).

6. **Plugin/extension API for third-party widgets is not exposed.** The widget set is fixed in the codebase. Organizations that need to build proprietary widgets currently fork.

These are real limitations. They do not negate the architectural advantages described in this document, but they should be weighed against the specific requirements of any evaluation.

---

## Conclusion

The "page = folder" decision is IntraVox's most consequential architectural choice. It turns Nextcloud's file storage, ACL model, version system, sharing infrastructure, audit log, backup tooling, encryption layer, external storage, trashbin, quota system, full-text search, and user-lifecycle hooks into IntraVox features — at zero implementation cost and zero ongoing maintenance burden.

For organizations already running Nextcloud — which includes most European research/education networks, many sovereign-cloud deployments, and a growing share of regulated industries — this architecture means IntraVox is not a new platform to evaluate, deploy, secure, and operate. It is **a feature added to a platform they already trust**.

That is the architectural argument for IntraVox in enterprise and federated contexts. The product still needs to be evaluated on UX, feature completeness, performance at scale, and roadmap fit. But the foundation is structurally different from competing wiki platforms, and the difference is worth understanding before comparing feature checkboxes.

---

## Related documentation

- [`ARCHITECTURE.md`](../overview.md) — Overall system architecture
- [`AUTHORIZATION.md`](../../admin/authorization.md) — Permission model details
- [`VERSIONING.md`](../../user/versioning.md) — Page versioning UI and API
- [`PUBLIC_SHARING.md`](../../user/public-sharing.md) — Sharing IntraVox pages externally
- [`SCALABILITY.md`](../../admin/scalability.md) — Scaling considerations
- [`SECURITY.md`](../../admin/security.md) — Security model and sanitization layers
- [`EXPORT-IMPORT.md`](../../admin/export-import.md) — Export/import schema including MetaVox integration
- [`COLLECTIVES_COMPARISON.md`](collectives-comparison.md) — Comparison with Nextcloud Collectives
