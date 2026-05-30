# Changelog

All notable changes to IntraVox will be documented in this file.

IntraVox is a Nextcloud intranet page builder.

## [1.5.4] - 2026-05-30 — Hide source folder for users without access (information disclosure fix)

Patch release that closes an information disclosure issue introduced by 1.5.3.1 and hardens the FileStory / PhotoStory empty-state for users without folder access. No DB migration, no API breaking changes.

### Security
- **FileStory / PhotoStory: source folder path leaked to users without access** — 1.5.3.1 added a "You do not have access to this folder" empty-state that showed the configured folder path (e.g. `Shalution/Administratie/2026`) as context. For a user who is *deliberately excluded* from that folder, this disclosed the existence and naming of paths they shouldn't be aware of — path names can carry sensitive context (client names, project codes, person names, dated boundaries). The empty-state now renders a minimal lock icon + `"You do not have access to this widget"` with no folder name and no scan hint. The folder path remains visible only for users who *do* have access but happen to see an empty result (legitimate context).

### Notes
- The defensive backend guard `PhotoStoryService::assertNotResolvedToUserRoot()` added in 1.5.3.1 stays in place. It already returns `reason: 'folder_not_accessible'` on the 404 response which the new empty-state branches on.
- Known follow-up: News widget's empty-state still shows `Source: {path}` for unauthorised users. Same pattern, lower severity (source is usually a page-id, not a filesystem path); tracked separately.
- Future direction: SharePoint-style **audience targeting** per widget will eventually let admins hide widgets entirely from users who shouldn't see them. The lock-state introduced here is the fallback for the edge-case where audience-target users lose folder permissions after configuration.

## [1.5.3] - 2026-05-30 — FileStory open in new tab + legacy GroupFolders fix

Patch release that fixes a "file no longer exists" toast when clicking documents in FileStory, and makes click-to-open behaviour consistent across all mount types. No DB migration, no API breaking changes.

### Fixed
- **FileStory: "file no longer exists" toast on click for legacy shared-storage GroupFolders** — `FileStoryWidget.openFile()` preferred `OCA.Viewer.open({path})` for inline preview, deriving the path from `file.path` by stripping a leading `files/`. That works for personal storage and per-folder jail GroupFolders, but in legacy shared-storage GroupFolders the cache row stores `__groupfolders/<id>/...` and the user-visible mount-point name (e.g. `Shalution`) isn't carried on the row. The Viewer received `/__groupfolders/4/Administratie/2026/Boekhouding.xlsx`, couldn't resolve it against the user's tree, and NC raised the "file no longer exists" toast. Federated shares hit a similar dead-end via a different code path.
- **FileStory click behaviour now consistent across mount types** — documents always open in a new tab via NC's `/f/<id>` handler, which resolves the right mount server-side for personal storage, both GroupFolders mount strategies, internal shares and federated shares. Removed the path-derivation entirely (`resolveDisplayPath()` deleted).

### Notes
- Behaviour change: clicking a document in FileStory no longer opens the inline NC Viewer overlay — every click now opens a new tab at the file's NC Files location. This trades inline-preview ergonomics for "actually works on every mount type" reliability.
- PhotoStory is unaffected; its widget uses an internal Lightbox component and never touched `OCA.Viewer.open()`.

## [1.5.2] - 2026-05-28 — PhotoStory groupfolder fix + federated previews + sticky navigation

Patch release with two production-blocking PhotoStory fixes (groupfolder albums and federated-file thumbnails), three UX improvements requested from real use, and one admin-tool clarification. No DB migration, no API breaking changes.

### Fixed
- **PhotoStory shows "No photos found" for every groupfolder album** — `PhotoStoryService::extractStorageAndPath()` returned the jailed Node path (e.g. `Albums/Doris Synchroonzwemmen`) while `oc_filecache.path` stores the unjailed form (`__groupfolders/7/Albums/Doris Synchroonzwemmen` or `files/Albums/Doris Synchroonzwemmen`, depending on which mount strategy the groupfolder uses). The SQL `path LIKE` predicate matched zero rows, the widget rendered its empty state, and the hint text misleadingly pointed admins at `occ files:scan` — even though the files were already indexed. The path is now reconstructed by walking the cache wrapper chain for the first `CacheJail::getGetUnjailedRoot()`, which covers both groupfolder mount layouts as well as any other jailed mount (federated, encryption-wrapped). Root-mode `/` enumeration also benefits — separate groupfolder mounts no longer collapse into the personal-storage scope.
- **PhotoStory/FileStory tile previews missing for federated files** — NC's `/core/preview` returns 404 for any file on a `Files_Sharing\External\Storage` mount: the preview providers (Image, Office, PDF) need a local file path or a Collabora/LibreOffice render, and federated files only exist on the remote NC's disk. Result: PDFs, docx, xlsx and even jpg tiles from an OCM share rendered as a generic mime-icon instead of a thumbnail. New shared `PreviewController` at `GET /api/preview?file_id=N&x=400&y=400` closes the gap: local files 302-redirect to `/core/preview` (no overhead, NC's own preview cache stays hot); federated files are handled by the new `FederatedPreviewService` which calls the **owner** instance's `/index.php/apps/files_sharing/publicpreview/{token}` endpoint and caches the result in `appdata/intravox/federated-preview/` keyed by `{fileId}-{etag}-{x}-{y}`. Cold response ~250–400 ms (~5–15 KB transfer per file, not the file body), warm ~180 ms.<br><br>Three protection layers stack to keep this scalable and friendly to the remote: a **per-user rate throttle** (`UserRateThrottle(600/min)`) bounds individual misuse; **in-flight deduplication** via NC's distributed cache ensures that 50 users opening the same uncached tile at once produce a single outbound HTTPS call (49 wait for the cache to materialise, then read); a **per-remote concurrency semaphore** (default 8 simultaneous outbound calls per remote host) prevents an IntraVox-server from saturating one owner instance and tripping its IP-throttle. When the cap is hit the request degrades gracefully to the mime-icon fallback. A companion `POST /api/preview/warmup` endpoint pre-warms up to 16 federated tiles per call; PhotoStoryWidget and FileStoryWidget call it fire-and-forget after every paged fetch so most tiles are already warm by the time the user scrolls into view. Bandwidth scales with viewed files, not with corpus size — fine on 1M-file federated mounts.

### Added
- **PhotoStory: hide RAW sidecars when a JPG/HEIC variant exists** — DSLRs and mirrorless cameras in "RAW + JPG" mode write two files per shot (`IMG_5432.CR2` + `IMG_5432.JPG`) that show up as visual duplicates in any folder view. New `hideRawDuplicates` widget option (default **on**) groups files by `(parent_dir, basename-without-extension)` and prefers the browser-displayable variant over the RAW. Covers Canon (CR2/CR3), Nikon (NEF/NRW), Sony (ARW), Adobe (DNG), Fujifilm (RAF), Olympus (ORF), Panasonic (RW2), Pentax (PEF), Samsung (SRW) and Sigma (X3F). Implemented as over-fetch + dedup + slice so paginated infinite scroll stays correct; `total` continues to count physical files (honest source-of-truth for storage cost).
- **Sticky page navigation** — header (title + Save/Edit) and navigation bar are now wrapped in a `position: sticky; top: 0` topbar so they stay reachable on long pages. Previously a 300-photo Photo Story timeline forced you to scroll all the way back up to reach another page. Dropdowns/megamenus continue to position via `getBoundingClientRect()`, so their placement is unaffected.
- **Orphaned GroupFolder admin: show what's actually in the folder** — the "Content" column used to render the literal "Unknown data" for non-IntraVox orphans, leaving admins to delete blind. `OrphanedDataService::analyzeOrphanedFolder()` now returns a `sampleContents` field with the first 8 top-level entries (name, type, size) sorted alphabetically, and the admin UI renders them as a small listing under the badge. The empty-state label also changes from "Unknown data" to "Non-IntraVox data" for accuracy ([#56](https://github.com/nextcloud/IntraVox/issues/56)).

### Notes
- The PhotoStory groupfolder fix activates on every groupfolder-hosted album with zero config — existing widgets pointing at a groupfolder path will start returning their photos immediately after upgrade.
- The federated preview proxy degrades gracefully: if the owner instance can't produce a thumbnail (no Collabora/LibreOffice on their side, or the file format is unsupported there), the endpoint serves a 302 redirect to the matching mime-icon SVG. No broken-image placeholders.
- `hideRawDuplicates` defaults to enabled also for existing widgets (the param is absent → backend reads its default). Users with RAW-only albums can untick the new editor checkbox.
- No DB migration; widget configs are read back through the new optional field transparently.

## [1.5.1] - 2026-05-28 — Themed-row contrast + widget polish

Patch release that fixes legibility on themed page rows and tightens a handful of widget rough-edges that surfaced in production after 1.5.0. No new features, no API changes.

### Fixed
- **PhotoStory/FileStory contrast on dark row backgrounds** — filenames, day-headers and meta lines used `--color-main-text` (dark) regardless of the row's background colour. On `Primary` (`--color-primary-element`) rows that produced unreadable dark-on-dark text. Both widgets now accept `rowBackgroundColor` from the parent `Widget.vue` (closing a gap with the existing widgets that already consume it) and switch internal text + tile surfaces to a WCAG-paired colour set via two CSS variables (`--fs-text`/`--ps-text` + their muted siblings). Tile bodies become a tinted-glass card on dark rows instead of cutting a hard white rectangle through the coloured backdrop.
- **FileStory tile filenames invisible on dark rows** — regression from the same root cause: tile bodies kept their `--color-main-background` (white) while inheriting the now-white filename colour. Tile surfaces, hover state, preview-fallback bg and mime-icon placeholder all lift to translucent white on `fs--on-dark`.
- **Folder-path "/" silently collapses to empty after save** — `PageService::sanitizePath` strips leading/trailing slashes, so a configured PhotoStory/FileStory `folderPath = "/"` (root) was persisted as `""` and rendered as "no folder selected" after reload. New `sanitizeFolderPath()` wrapper preserves `/` (and `\`) as a meaningful "whole drive" marker before delegating to the generic sanitizer.
- **502/503 during page save** — entering edit mode after a FileStory widget existed triggered four expensive `folder=/` queries within ~250 ms (the legacy debounce). Apache workers saturated on large libraries. FetchKey watcher debounce raised from 250 ms to 700 ms in both widgets.
- **NcAppSidebarTabs double underline (NC 32 regression)** — `@nextcloud/vue` 8.x renders both a 1 px hairline on the tab-strip wrapper *and* a 4 px coloured indicator on the active tab, producing a stacked double underline in the PageDetailsSidebar. Global override in `css/main.css` removes the redundant hairline; scoped Vue CSS couldn't reach the `data-v-`-tagged third-party selector.
- **Photo previews missing for common web formats** — `PhotoStoryService::MEDIA_MIMES` was narrower than what users actually drop into their photo folders. Now also includes `webp`, `gif`, `svg+xml`, `bmp` and `video/webm`.
- **Empty folder picker returning `""` instead of `/`** — NC's `OC.dialogs.filepicker` returns an empty string when the user picks the root; both editors now normalise that to `"/"` so the configured value matches the sanitizer's accepted shape.

### Notes
- Default-themed rows (transparent / `--color-background-hover` / `--color-primary-element-light`) are visually unchanged; the new contrast logic only activates on saturated row colours.
- No DB migration. Existing PhotoStory/FileStory widget configs are read back through the new sanitizer transparently.

## [1.5.0] - 2026-05-27 — Photo Story + File Story widgets

Major release. Introduces two new widgets — **Photo Story** for photo galleries with EXIF, location maps and an Apple-style lightbox; **File Story** for document libraries with multi-mode layouts, MetaVox-aware filtering and federated-share awareness. Adds a fresh wave of perf, security, accessibility and l10n polish across the photo + file widget surface.

### Added — Photo Story Widget

- **Four layout modes**: Timeline (Magazine, Apple or Travelogue style), Highlights (auto-curated top photos), Grid (masonry), and On-this-day (year-over-year retrospective).
- **Lightbox** with keyboard navigation (Arrow/Home/End/Esc/Space), slideshow mode with adjustable speed, focus-trap and focus-restore for screen readers, semi-transparent date/location pill that toggles a mini-map for geo-tagged photos.
- **OpenStreetMap integration** via Leaflet: optional overview map per widget, per-day mini-maps in Timeline mode, and a cross-folder cluster endpoint for browsable map-driven storytelling. Admin-config aware (NC admin can disable all map features instance-wide).
- **EXIF metadata** rendered into a details flyout (people, subjects, camera, location). Reads from NC core `oc_files_metadata` when populated; falls back to the bundled `lsolesen/pel` reader as a last resort with a per-request eager-EXIF cap.
- **MetaVox-driven filtering, grouping and sorting** when the MetaVox app is installed. Supports cross-folder discovery mode (empty folder + ≥1 filter) for "all my photos tagged X across the instance".
- **Geocoding cache** with periodic warmup job for fast country/location lookup on GPS-bearing photos.

### Added — File Story Widget

- **Four layout modes**: Timeline (per-day / per-month / per-year granularity), List (flat sortable), Tiles (visual grid with first-page previews and three configurable sizes: Small/Medium/Large), and Grouped (by file-type or MetaVox field).
- **Federated-share awareness** — incoming OCM shares are detected per-file via a single indexed SQL join (`oc_storages × oc_share_external`). Federated rows render with a subtle cloud-badge and silently skip MetaVox-fetch since the remote NC has its own metadata database we cannot reach cross-instance. Mixed sources (local + federated under one root) keep full controls; pure-federated sources hide the MetaVox UI with an explanatory banner.
- **Configurable visible columns**: Date, File size, Folder path. Date column can render either filesystem mtime or EXIF/MetaVox `taken_at`. Filename + file-type icon are always present.
- **MetaVox filter-builder, sort, group-by** identical to Photo Story but adapted to document use-cases (e.g. group-by `archief_categorie` for compliance views).
- **Open-in-Files-viewer** click target on every row/tile with `role="button"`, Enter+Space keyboard activation and `aria-label` per item.

### Added — Page editor & widget plumbing

- **Widget registration** for Photo Story and File Story in the picker, with iconography and descriptive copy.
- **Editors** for both widgets with folder picker (NC FilePicker dialog), live capability detection (MetaVox available?, source-federated?), sortable filter builder with type-aware operators (`equals`, `contains`, `in`, `year_equals`), and persisted widget config validated by `PageService::sanitizeWidget`.
- **REST API** under `/api/photo-story/*` and `/api/file-story/*` covering paged listing, capabilities, MetaVox field discovery, location clusters, EXIF detail and range-aware video streaming (Photo Story only).

### Performance

- **Paged enumeration** via `oc_filecache` for all primary widget modes — no more full-tree `getDirectoryListing()` on large libraries. Hard caps (5000 cross-folder, 20k filtered, 200k count) prevent OOM on massive folders.
- **Federated detection** is one preloaded SQL query per request, O(1) lookups per file. The previous `IMountManager::findIn('/')` per-file approach (cause of the 2026-05-27 saturation incident on nc-dev) is gone.
- **`clusters`, `highlights` and `on-this-day` endpoints** now go through `listPhotosPaged` with sane caps instead of the unpaged legacy path that risked the same blast radius as the federated-detect outage.
- **`filterFileIdsByScope` collapsed** from `chunks × scopes` SQL roundtrips to one ORed `WHERE` per chunk — at filtered-MetaVox-page scale this drops ~400 queries per page to ~40.
- **`extractGroupfolderId`** memoised per node within a request.
- **Frontend `AbortController`** on every fetch + fetchMore: rapid config changes no longer race stale responses overwriting fresh data, and pending requests cancel on widget unmount.

### Security

- **Per-file ACL guard** on the slice in MetaVox cross-folder hydration (`buildPagedResponseViaMetaVox`) using `$userFolder->getById()`. Bounded to ≤page-size lookups, so sub-folder ACLs inside groupfolders are honored.
- **Filter payload caps**: 16 KB JSON pre-decode rejection on both controllers, value-length cap of 200 chars per filter, max 32 filters and 64 array values per filter — prevents pathological-input DoS.
- **Generic 500 messages** on both controllers (no `$e->getMessage()` reaching client); folder-not-found mapped to clean 404 with empty-state payload instead of generic 500.

### Accessibility (WCAG 2.1 AA)

- **Tiles, rows and hero elements**: `role="button"`, `tabindex="0"`, Enter + Space activation, meaningful `aria-label` derived from caption/location.
- **Lightbox**: focus-trap (Tab cycles within modal, no escape to background), focus-restore on close, counter announced via `aria-live="polite"`, icon-only buttons get descriptive `aria-label` + `aria-pressed` where appropriate.
- **Editors**: orphan `<label>` without `for=` converted to `<div class="editor-label">` to avoid mis-association; form controls properly labelled.
- **Reduced motion**: `@media (prefers-reduced-motion: reduce)` honored for Ken-Burns animation, pulse skeletons, and pill transitions.
- **Status regions**: `role="status"` / `role="alert"` on loading, empty and error states; map-cluster list items keyboard-reachable; federated cloud-badge gets `role="img"` + `aria-label`.
- **Alt-text**: meaningful (caption / location / numbered fallback) instead of filename for photos; decorative `alt=""` for tile previews where the parent already labels the action.

### Internationalisation

- **Backend month/category labels** now route through `IL10N::t()` (`PhotoStoryService::localizedMonth`, `FileStoryController::extractGroupKey`). No more hardcoded Dutch in API payloads.
- **Frontend date formatters** use `getCanonicalLocale()` from `@nextcloud/l10n` everywhere — `toLocaleDateString` / `toLocaleString` / `Intl.DateTimeFormat` calls in PhotoStoryWidget, FileStoryWidget and PhotoLightbox no longer pin `nl-NL`.

### UX polish

- **Retry button** in the error-state of both widgets. Users recover from transient API failures without reloading the page.
- **Context-aware empty messages**: distinguishes "no folder selected" / "no documents match current filters" / "folder is empty".
- **Transparent date headers** in FileStoryWidget Timeline mode — replaces the opaque white sticky bar that clashed with themed/coloured rows. Count-badge uses `color-mix(in srgb, var(--color-primary-element) 14%, transparent)` for a subtle tinted chip that adapts to the active theme.

### Developer-side hardening

- **`scripts/check-import-consistency.js`** runs in `prebuild`: detects mixed sync/async imports of the same `.vue` component (the root cause of a runtime `TypeError` we hit on 2026-05-27) and fails the build before it ships.
- **`scripts/auto-bump-dev.js`** auto-bumps the patch level on dev deploys so NC's `md5(appVersion)` cache-buster always changes — browsers never serve a stale bundle after a deploy.
- **Translation files** (`en/nl/de/fr`) synced for all 122 new UI strings added by Photo Story + File Story.

### Notes

- **No DB migrations** required for the widget functionality itself; existing pages keep working.
- **PhotoStory federated-share awareness** is on the roadmap but not yet implemented (single-storage photo libraries are the typical case). FileStory has the full federated-aware code path.
- **MetaVox cross-instance sync** remains out of scope: NC core exposes no federation tokens or remote-file-id mapping. Roadmap item.

New Vue components: `src/components/PhotoStoryWidget.vue`, `src/components/PhotoStoryWidgetEditor.vue`, `src/components/PhotoLightbox.vue`, `src/components/PhotoStoryMap.vue`, `src/components/PhotoStoryDayMap.vue`, `src/components/PhotoStoryFilterBuilder.vue`, `src/components/FileStoryWidget.vue`, `src/components/FileStoryWidgetEditor.vue`.

Composer: `lsolesen/pel` added for the optional in-process EXIF reader.

## [1.4.1] - 2026-05-20 — Security dependency bumps

Patch release that resolves all open frontend security advisories flagged by GitHub Dependabot shortly after the v1.4.0 push. No functional or API changes — `npm audit fix` lifted eight vulnerable transitive packages to patched versions within their declared semver ranges, no `package.json` edits required. Build, PHPUnit (258/413) and dev-server smoke tests all green.

### Fixed
- **axios** → 1.16.1 — resolves 11 advisories (prototype pollution gadgets, CRLF injection, header injection, NO_PROXY/SSRF bypasses, DoS via deep `toFormData` recursion, streamed upload/response body-size bypasses, null-byte injection in `URLSearchParams`, XSRF token cross-origin leak)
- **dompurify** → 3.4.5 — resolves four XSS bypasses (`SAFE_FOR_TEMPLATES`/`RETURN_DOM`, `ADD_TAGS`/`FORBID_TAGS` short-circuit and function-form, prototype-pollution via `CUSTOM_ELEMENT_HANDLING`)
- **fast-uri** → 3.1.2 — path-traversal via percent-encoded dot segments + host-confusion via percent-encoded authority delimiters
- **fast-xml-builder** / **fast-xml-parser** — XML comment/CDATA injection and attribute-value quote-bypass
- **brace-expansion** → 5.0.6 — DoS via numeric range that defeated documented `max` protection
- **follow-redirects** → 1.16.1 — custom auth-header leak on cross-domain redirects
- **postcss** → 8.5.15 — XSS via unescaped `</style>` in CSS stringify output

After the bump `npm audit` reports zero vulnerabilities.

## [1.4.0] - 2026-05-19 — Enterprise refactor + caching foundation

This release lays down the foundation IntraVox needs to scale cleanly to Nextcloud Enterprise customers with thousands of users on multi-node deployments. Two themes: PageService gets split into focused, testable services, and the caching layer gains group-aware keys + a content-addressable distributed cache + a frontend prefetch pipeline.

User-visible: pages and navigation are noticeably faster on warm caches, especially for groups of users that share the same permission profile. Cold-cache latency is bounded by a new background warmup job. No breaking changes; every public API is unchanged.

### Added
- **Subtree support on `GET /api/pages/tree`** — Optional `rootPageId` query parameter narrows the response to the subtree rooted at the page with that uniqueId. Resolves [#45](https://github.com/nextcloud/IntraVox/issues/45) from JustinDoek (teamhub app builder) who previously had to combine `listPages` + `getBreadcrumb` to list pages under one anchor. Backward compatible — without the parameter the full tree is returned as before. The same parameter is available on the `<PageTreeSelect>` Vue component (`rootPageId` prop) for in-app subtree pickers (`lib/Service/Path/PagePathHelper.php::findSubtree`, `lib/Service/PageService.php`, `lib/Controller/ApiController.php`, `src/components/PageTreeSelect.vue`)
- **ETag / 304 conditional responses on `GET /api/pages/{id}`** — Browser revalidation now returns a 304 with zero body when the cached page is still current. Per-user group hash is included in the ETag so a permission change automatically invalidates the cached entry without leaking content across users (`lib/Http/EtagBuilder.php`, `lib/Controller/HasConditionalResponse.php`, `lib/Controller/ApiController.php`)
- **Group-hash cache key for page tree + permission map** — Tree and navigation caches are now keyed by a hash of the user's group memberships instead of their user-id. At enterprise scale (1000+ users in ~10 groups) this turns thousands of cache entries into dozens — same correctness, two orders of magnitude less memory. Permission path-maps are cached per-language (one entry per supported language, shared across all users) (`lib/Service/GroupContextService.php`, `lib/Service/PageService.php`, `lib/Service/PermissionService.php`)
- **Event-based cache invalidation on group changes** — Adding or removing a user from a group flushes the affected distributed caches via `UserAddedEvent` / `UserRemovedEvent` listeners. Group permission updates propagate within one request cycle instead of waiting for TTL expiry (`lib/Listener/GroupMembershipChangedListener.php`)
- **Page-content distributed cache with mtime-indexed keys** — Sanitized page output is cached under `content_{uniqueId}_{mtime}`; a write bumps mtime, the next reader misses cache and rebuilds. The expensive sanitize-pipeline (~500 lines of widget processing) only runs on cache miss (`lib/Service/PageService.php`)
- **News widget result cache with version counter** — `getNewsPages()` results are cached per `{lang}_{groupHash}_v{counter}_{paramHash}`. Mutations clear the cache; subsequent reads rebuild from a fresh counter state (`lib/Service/PageService.php`)
- **Frontend prefetch service** — `src/services/PrefetchService.js` speculatively loads pages on hover (desktop, 100ms delay) and IntersectionObserver entry (mobile, 200px rootMargin). Respects `navigator.connection.saveData` so users on metered connections aren't surprised by extra requests; max 3 concurrent in-flight requests. Writes through the existing `CacheService` so real navigations pick up the prefetched data instantly
- **LRU eviction on localStorage quota** — `CacheService.set()` now catches `QuotaExceededError`, drops the persistent entry with the earliest expiry, and retries once. Prevents silent cache-write failures on heavy intranets
- **Background cache-warmup job** — Runs every 15 minutes (TIME_INSENSITIVE) and pre-warms the path-map + tree + navigation caches for each supported language. Prevents the cold-cache thundering herd after a deploy or after a page mutation (`lib/BackgroundJob/CacheWarmupJob.php`)
- **`RequestTimer` infrastructure** — Light static utility for measuring p50/p95 latency of expensive operations. Used internally for ad-hoc profiling; not yet wired into TelemetryService (`lib/Performance/RequestTimer.php`)

### Changed
- **PageService.php is 615 lines smaller** — From 6135 to ~5520 lines. Ten pure helpers extracted into focused, individually-testable services. PageService remains the orchestrator for filesystem + cache + permissions, but the sanitize, format, search, path and template logic now live in dedicated modules:
  - `lib/Service/Sanitize/HtmlSanitizer.php` (strip_tags + style-property whitelist + entity decode)
  - `lib/Service/Sanitize/UrlSanitizer.php` (schema-whitelist for link URLs)
  - `lib/Service/Sanitize/ColorSanitizer.php` (NC theme-vars + hex + rgb/rgba)
  - `lib/Service/Sanitize/MediaSanitizer.php` (filename + SVG + image-header validation)
  - `lib/Service/Version/PageVersionFormatter.php` (NC-style "X sec/min/hour/day ago" + metadata accessors)
  - `lib/Service/Template/TemplateMetadataExtractor.php` (preview summary: column count, widget mix, complexity bucket)
  - `lib/Service/News/NewsContentExtractor.php` (excerpt, first-image, markdown strip)
  - `lib/Service/Search/PageSearchHelper.php` (snippet extraction, per-widget-type scoring)
  - `lib/Service/Path/PagePathHelper.php` (depth, page-type, department slug, current-page marking)
  - `lib/Service/Util/PageIdUtils.php` (sanitizeId, RFC 4122 v4 UUID, php.ini size parsing, formatBytes)
- **Test suite grew from 78 (with 36 errors) to 252 / 401 assertions, all green** — Existing Controller tests were updated to match the current constructor signatures; a fresh unit-test layer covers every extracted service

### Fixed
- **Cache-invalidation gaps closed across page/nav/media/import flows** — Discovered during dev verification of the new caching layer: several mutation paths wrote to disk without flushing the distributed caches introduced by PR-3 / PR-12 / PR-13, so changes were invisible for up to 5 minutes after a save. Now resolved:
  - `PageService::createPage` flushes after writing — without this, the new page sat behind the 5-minute tree-cache TTL (visible on "Create from template" — page appeared in the breadcrumb but the editor mounted blank until reload).
  - `PageService::createPageFromTemplate` re-fetches through `getPage()` so the response includes `enrichWithPathData` + the sanitize pipeline. Previously the API returned half-populated page data and the editor rendered blank until a manual save round-tripped through the real read path.
  - `App.vue::handleCreatePageFromTemplate` uses the enriched backend response directly instead of doing a second `selectPage()` round-trip that occasionally 404'd against a freshly-created folder and bounced the user back to the home page. URL hash, local pages array and frontend `CacheService` are all warmed in one synchronous block before the editor mounts.
  - `ImportService::importFromZip` flushes all PageService caches after a bulk import — without this, 50+ imported pages were invisible in tree, navigation and news widgets for the next 5 minutes.
  - `NavigationService::saveNavigation` flushes `intravox-pages` + `intravox-permissions` after writing — previously a menu edit landed on disk but the path-map cache (PR-3) served the old menu for 5 minutes.
  - `PageService::uploadMedia` + `uploadMediaWithOriginalName` flush the per-page content cache so the next page-render reflects the just-uploaded asset (important for image overwrites where users otherwise got the cached old version back).
  - Public `PageService::invalidateAllCaches()` introduced as the cross-service hook for the import path (kept internal `clearCache()` private; only the audit-driven external use case opens it up).
- **Actionable error messages on failed ZIP imports** — Resolves [#52](https://github.com/nextcloud/IntraVox/issues/52) from @apesorguk, who saw only "Import failed. Please check the ZIP file format and try again" when uploading a cloudron Nextcloud backup. The five validation errors in `ImportService` (invalid ZIP, missing export.json, invalid JSON, unsupported version, incomplete export) now bubble through a typed `InvalidImportException` and reach the user with copy that tells them what went wrong and how to fix it ("Make sure you uploaded an IntraVox export, not a Nextcloud Files backup..."). HTTP status is now 400 for these instead of 500. Generic failures still hide behind an `errorId` so server paths don't leak. A `NcNoteCard` above the import form spells out the supported format up front. Error messages translated to NL/DE/FR via a stable `errorCode` (`INVALID_ZIP`, `MISSING_EXPORT_JSON`, `INVALID_JSON`, `UNSUPPORTED_VERSION`, `INCOMPLETE_EXPORT`) the frontend maps to localized strings (`lib/Exception/InvalidImportException.php`, `lib/Service/ImportService.php`, `lib/Controller/ApiController.php`, `lib/Controller/ImportController.php`, `src/components/AdminSettings.vue`)
- **Broken Controller test suite** — `ApiControllerTest`, `BulkControllerTest`, `AnalyticsControllerTest` now compile against the current Controller constructor signatures. The OCP stub gained `ISession`, `ICache`, `ICacheFactory`, `IGroup`, group-membership events and `Files_Versions\IVersion` to keep unit tests runnable without a full Nextcloud install

## [1.3.4] - 2026-05-08 — Version bump for upgrade path

Identical content to 1.3.1 (released earlier today). The version number is bumped to 1.3.4 because an internal 1.3.3 build was published to the App Store on 2026-05-06; instances that picked up that build would not see 1.3.1 as an upgrade. 1.3.4 ensures every existing install gets the editor/table improvements and the privacy cleanup of [1.3.1] below.

No code changes vs. 1.3.1.

## [1.3.1] - 2026-05-08 — Editor, telemetry & table improvements

### Added
- **Text alignment** — New alignment dropdown in the text editor toolbar (left, center, right). Alignment persists through save/reload using CSS classes in markdown storage. Supports paragraphs and headings. Keyboard shortcuts: `Ctrl+Shift+L/E/R`. Custom TipTap extension uses CSS classes instead of inline styles for DOMPurify compatibility (`textAlignExtension.js`, `InlineTextEditor.vue`, `markdownSerializer.js`)
- **Blockquote button** — New blockquote toggle button in the text editor toolbar. Uses the existing StarterKit blockquote extension — only the toolbar button and read-only styling were missing (`InlineTextEditor.vue`, `Widget.vue`, `Footer.vue`)
- **Nextcloud Extended Support telemetry** — Telemetry payload now includes `hasExtendedSupport` (boolean), sourced from Nextcloud's public `OCP\Util::hasExtendedSupport()` API. Helps us understand which share of IntraVox installations runs on Nextcloud Enterprise / Extended Support — relevant for compatibility prioritization and the Nextcloud ISV partnership. Falls under the existing telemetry opt-out (no separate consent), and is listed in the admin "What we collect" overview for transparency. No personal data, just a single yes/no per instance (`TelemetryService.php`, `SupportSettings.vue`)
- **Persistent column widths in tables** — Column widths an editor sets by dragging the TipTap resize handles now survive save/reload. A post-render hydrator in `markdownSerializer.js` builds a `<colgroup>` from `data-colwidth` (modern) or `colwidth` (legacy) cell attributes and any pre-existing `<col style="width: Xpx">`, then converts pixel widths to percentages so the table always fits its container — even when the saved widths sum higher than a narrow page-row column. Tables without explicit widths keep the previous auto-layout behaviour (`markdownSerializer.js`)
- **Table width presets** — New "Width" row in the table toolbar dropdown with presets Auto, 25%, 50%, 75%, 100%. Stored as `data-table-width` on the `<table>` (`InlineTextEditor.vue`)
- **Table alignment** — New "Alignment" row in the same dropdown with Left/Center/Right buttons. Stored as `data-table-align`; rendered as `margin-left: auto` / `margin-right: auto` so a 50%-wide table can sit left, centered, or right with surrounding text (`InlineTextEditor.vue`)
- **Free-form table width drag handle** — A custom ProseMirror plugin adds an 8px-wide drag area on the right edge of the active table. Click+drag to set any pixel width between 80px and the widget container's width; the resulting style survives save/reload via the same hydrator. Coexists with the column-resize handles inside the table — different hit zones (`tableResizeHandle.js`, `InlineTextEditor.vue`)
- **Horizontal scroll wrapper for wide tables** — Tables wider than their page-column scroll horizontally inside a `.tableWrapper` div instead of pushing the page layout sideways. Read-mode wraps every table via the hydrator; edit-mode reuses TipTap's built-in `.tableWrapper` element with the same styling, so what the editor sees matches what readers get (`markdownSerializer.js`, `Widget.vue`, `InlineTextEditor.vue`)

### Changed
- **Toolbar reordered** — Text editor toolbar reorganized into logical groups based on analysis of 10 popular editors: (1) Inline formatting: B, I, U, S (2) Block structure: Heading, Lists, Blockquote (3) Alignment dropdown (4) Insert actions: Link, Table. Compact mode follows the same grouping in the "More" dropdown (`InlineTextEditor.vue`)
- **Alignment as dropdown** — Text alignment uses a single dropdown button (like the heading dropdown) instead of 3 separate buttons. The button icon dynamically reflects the active alignment. Keeps the toolbar compact on all screen sizes (`InlineTextEditor.vue`)
- **Telemetry includes license key for Enterprise claim verification** — `TelemetryService::collectData()` now adds the configured license key (or empty string for community instances). The license server uses it to verify `hasExtendedSupport` claims against the bound `license_usage` row before honoring them; without this binding the boolean would be anonymously spoofable. The key is the same value the app already sends to license validation/usage endpoints, so this introduces no new disclosure (`TelemetryService.php`)
- **Table cell text wrap policy** — Cells use `overflow-wrap: anywhere` (CSS Text Module Level 3) so long unbreakable tokens (URLs, hashes) wrap mid-word when needed. Edit-mode and read-mode use the same rules so what the editor sees is what readers get. Replaces the deprecated `word-break: break-word` combo with the modern one-line equivalent (`InlineTextEditor.vue`, `Widget.vue`)
- **Page rows allow narrow content** — `.page-row`, `.row-content`, `.page-grid` got `min-width: 0` and `max-width: 100%` so a wide table inside a multi-column row no longer forces the row beyond its viewport. The grid columns now use `repeat(N, minmax(0, 1fr))` instead of `repeat(N, 1fr)` so a `1fr` track can shrink below its content's min-content (`PageViewer.vue`, `PageEditor.vue`)

### Fixed
- **Aligned text not surviving save/reload** — Content with text alignment was escaped to raw HTML after saving and reloading. Root cause: `markdownToHtml()` had a validation check (`html === preservedMarkdown`) that incorrectly treated HTML blocks passed through by `marked` as a parse failure, triggering `escapeHtml()`. Fixed by skipping this check when content starts with `<` (`markdownSerializer.js`)
- **Table widths and alignment getting stripped on save** — `data-table-width` and `data-table-align` were not in the DOMPurify allowlist, so user-set widths and alignment from the table dropdown silently disappeared after saving. Added to the allowlist together with the `<div>` tag we now use for the scroll wrapper (`markdownSerializer.js`)
- **Text overflowing table cells** — In fixed-layout tables, long text in a cell could push past the cell border into the next column or beyond the table edge. Multi-cause fix: paragraphs and headings inside cells get `min-width: 0; max-width: 100%`, cells get `white-space: normal` (overrides a Nextcloud core rule that set `nowrap` on `<p>`), and the entire page-row chain was given proper `min-width: 0` so a wide table can no longer push its ancestors sideways (`InlineTextEditor.vue`, `Widget.vue`, `PageViewer.vue`, `PageEditor.vue`)
- **TipTap auto-generated table widths preventing fit-to-container** — TipTap writes `<table style="width: 422px">` based on summed colwidths; in narrow page-row columns this pinned the table beyond its container even with `table-layout: fixed`. The hydrator now strips that auto-style and rebuilds only from user-set `data-table-width` (`markdownSerializer.js`)

### Removed
- **Organization name & contact email from telemetry** — `organizationName` and `contactEmail` fields are no longer included in the telemetry payload sent to `licenses.voxcloud.nl/api/telemetry/report`. These were the only direct identifiers in an otherwise pseudonymous payload, so removing them brings telemetry closer to true anonymity. The fields had no functional purpose for telemetry — the license server doesn't use them — and no direct identifiers remain (`TelemetryService.php`)
- **"Your organization (optional)" admin settings section** — Removed the corresponding UI section, Vue state, and `GET`/`POST /api/settings` endpoints from `LicenseController`. Pre-existing `organization_name` / `contact_email` config values remain in `oc_appconfig` on upgraded instances but are no longer read or transmitted; they can be cleaned up in a future migration (`SupportSettings.vue`, `LicenseController.php`, `routes.php`)

## [1.3.0] - 2026-04-21 — Feed widget & performance

### Added
- **Feed widget** — New widget type for displaying external content on intranet pages. Supports RSS/Atom feeds and admin-configured connections to external systems (Canvas, Moodle, Brightspace, Jira, Confluence, SharePoint, OpenProject, and custom REST APIs). Features include: list and grid layouts (2-4 columns), configurable display options (image, date, excerpt, source, author), per-user OAuth2 personalization for LMS content, OIDC auto-connect for zero-click SSO, manual token fallback, 15-minute server-side caching, and public share support (`FeedWidget.vue`, `FeedWidgetEditor.vue`, `FeedReaderService.php`, `FeedItem.vue`)
- **Feed widget: connection presets** — Administrators configure connections in Admin Settings using platform presets that auto-fill endpoint paths, auth methods, and response field mapping. Presets available for Canvas, Moodle, Brightspace, Jira, Confluence, SharePoint, OpenProject, AFAS, TOPdesk, and Custom REST API. Each preset supports platform-specific content types (e.g. News/Courses/Deadlines for LMS, Pages/Documents/Lists for SharePoint, Bugs/Recent/Created for Jira)
- **Feed widget: content type selection** — Widget editors choose what content to display per connection type. LMS connections offer News/Announcements, My Courses, and Upcoming Deadlines. SharePoint offers Pages/News, Documents, and List items (with library/list selector). Jira offers project filtering and content types (bugs, recent, created). Content type selection happens in the widget editor, not admin settings
- **Feed widget: SharePoint integration** — Full Microsoft Graph API integration via OAuth2 client_credentials flow. Automatic token acquisition and caching using tenant ID, client ID, and client secret. Supports SharePoint site ID resolution (hostname:/path: format), page/news listing, document libraries, and list items. Admin configures site URL + Entra ID credentials; editors choose content type and library in the widget
- **Feed widget: image proxy** — Secure HMAC-signed image proxy bypasses Nextcloud CSP restrictions for feed images. Supports JPEG, PNG, GIF, WebP, AVIF, SVG (with sanitization via enshrined/svg-sanitize), and ICO. Daily signature rotation with yesterday grace window. All feed images (RSS, LMS, SharePoint, Jira) are proxied automatically (`FeedReaderController.php`, `FeedReaderService.php`)
- **Feed widget: OAuth2 account linking** — Users can connect their personal LMS account via OAuth2 popup flow (Canvas, Moodle with local_oauth2 plugin, Brightspace). Connected users see personalized content from their own courses. Token refresh is automatic (`LmsOAuthService.php`, `LmsOAuthController.php`, `LmsTokenService.php`, `OidcTokenBridge.php`)
- **Feed widget: sort and filter** — Feed items can be sorted by date or title (ascending/descending) and filtered by keyword. Filter searches in title, excerpt, and author (case-insensitive). Applied server-side after caching for instant response
- **Feed widget: custom request headers** — REST API connections support configurable HTTP headers (key-value pairs). Enables Nextcloud OCS API integration (`OCS-APIRequest: true`) and other systems requiring custom headers
- **Feed widget: design principle** — IntraVox focuses on organizational content (news, team updates, external feeds). Personal Nextcloud data (activities, notifications, recent files, Talk, Deck, Mail) belongs on the Nextcloud Dashboard. IntraVox does not duplicate Dashboard functionality. For organizational Nextcloud data from remote instances, use the REST API (custom) source type with OCS API endpoints
- **Calendar widget: external ICS feeds** — Editors can add external ICS calendar URLs (e.g. from Moodle, Canvas, Brightspace) directly in the calendar widget. Events from these feeds are visible to all page visitors, including public share viewers. No Nextcloud Calendar subscription required per user. Supports up to 5 ICS feeds per widget with 30-minute caching (`ExternalIcsService.php`, `CalendarWidgetEditor.vue`)
- **Calendar widget: LMS event deep links** — Clicking an external calendar event opens the event in the source LMS. Supports Canvas (native URL field), Brightspace (URL constructed from UID), and Moodle (URL constructed from UID). Unknown sources link to the feed domain
- **Feed widget: singleflight lock** — Prevents thundering herd on cache expiry. When the feed cache expires, only the first request fetches from the external source; concurrent requests wait and read from the freshly populated cache. Uses a distributed lock with unique request ID verification (`FeedReaderService.php`)
- **Feed widget: circuit breaker** — After 3 consecutive failures for a feed source, the circuit breaker opens and returns immediately with "temporarily unavailable". Resets automatically after 5 minutes or on first successful fetch. Prevents cascade failures from unstable external sources
- **Feed widget: background refresh** — New `FeedRefreshJob` background job proactively refreshes configured feed connections every 10 minutes, before cache expiry. Users almost never trigger a cold fetch. Includes its own circuit breaker to skip failing sources
- **Feed widget: rate limiting** — `UserRateThrottle(30/min)` on authenticated feed endpoints, `AnonRateThrottle(30/min)` on public share feed endpoint (`FeedReaderController.php`)
- **Page metadata database index** — New `intravox_page_index` table stores pre-indexed page metadata (title, uniqueId, path, language, status, modification time). Eliminates O(N) filesystem traversals for page listing, tree, and search operations. Updated automatically on page create/update/delete (`PageIndexService.php`, `Version001300Date20260420000000.php`)
- **Nextcloud search: index-first** — The unified search provider (Ctrl+K) now queries the page metadata index for fast title-based results (~1ms), with fallback to full-text filesystem search for content matches (`PageSearchProvider.php`)
- **Distributed page tree cache** — Page tree is cached in Redis/APCu (distributed) in addition to the existing in-process static cache. Shared across PHP processes/requests for ~70% reduction in tree response time. Invalidated automatically on page create/update/delete (`PageService.php`)
- **People widget: scalability guardrails** — Hard cap of 5,000 users on the unscoped filter path to prevent OOM/timeout on large Nextcloud instances. Filter results cached in Redis/APCu for 1 hour. Batch status prefetching reduces API calls from N to 1 (`UserService.php`)
- **Rate limiting on mutating endpoints** — `UserRateThrottle` added to page create/delete (10/min), bulk operations (5/min), comments (20/min), reactions (30/min), and analytics tracking (60/min). Covers `ApiController`, `BulkController`, `CommentController`, `AnalyticsController`
- **GDPR user deletion handler** — `UserDeletedListener` automatically cleans up analytics records, page locks, feed tokens, and LMS OAuth tokens when a Nextcloud user is deleted (`UserDeletedListener.php`, `Application.php`)
- **Audit logging** — Administrative operations logged with `IntraVox Audit:` prefix for SIEM integration: bulk delete/move/update (with page IDs and user), license key changes, organization settings, engagement settings (`BulkController.php`, `LicenseController.php`, `ApiController.php`)
- **Health check endpoint** — `GET /apps/intravox/api/health` returns app status and version for monitoring and orchestration (Kubernetes, uptime monitoring)
- **Scalability documentation** — New [SCALABILITY.md](docs/admin/scalability.md) documenting all performance, caching, resilience, rate limiting, and enterprise features
- **Admin: connection test button** — "Test connection" button on each feed connection card verifies credentials and endpoint by fetching a preview from the external API
- **Admin: connection export/import** — Export all feed connections as JSON (without tokens/secrets). Import on another instance with duplicate detection and preview dialog
- **Admin: connection active/inactive toggle** — Each connection has an NcCheckboxRadioSwitch toggle to temporarily disable it without deleting. Inactive connections show a specific message in widgets ("This connection is currently disabled by an administrator.") and are excluded from the widget editor dropdown. Re-enabling restores all widgets automatically — no reconfiguration needed. Toggle saves immediately
- **Admin: connection status badges** — Connection cards show configuration status as text badges: "Configured" (green), "Not configured" (orange), "Token missing" (orange), "Credentials missing" (orange). Replaces the previous green/grey dots for better visibility
- **Admin: connection remove confirmation** — Removing a feed connection shows a Nextcloud-style confirmation dialog instead of a browser prompt
- **Admin: Clean Start DELETE confirmation** — Destructive "Clean Start" action now requires typing `DELETE` to confirm
- **Admin: orphaned data banner** — Automatically detects orphaned data on admin panel load and shows a warning banner with link to Maintenance tab
- **Admin: advanced options collapse** — Endpoint path, response mapping, and custom headers for custom REST API connections are behind an "Advanced options" toggle
- **Admin: column width warning** — Shows a warning when the configured number of page columns may be too narrow for the available width, with a recommendation for fewer columns
- **Feed widget: error messages** — Specific error messages for inactive connections, 404 (connection not found), 401 (authentication required), 403 (access denied), and 429 (rate limited) instead of generic "Could not load feed"

### Changed
- **Feed widget: HTTP timeout reduced** — Outbound HTTP timeout reduced from 10s to 5s to prevent PHP worker blocking when external sources are slow (`FeedReaderService.php`)
- **Bundle splitting** — Enabled webpack `splitChunks` to separate vendor code (~2.9 MB) from application code (~220 KB). Main bundle reduced from 3.7 MB to 220 KB. Vendor chunk is shared between main and admin entry points and cached separately by browsers (`webpack.config.js`)
- **TipTap lazy-loaded** — TipTap editor and all 8 extensions (~240 KB) are loaded dynamically via `import()` on first editor mount. Pages viewed in read-only mode never download the editor code (`InlineTextEditor.vue`)
- **Widget components lazy-loaded** — All widget components (News, People, Calendar, Feed, Links, InlineTextEditor) loaded via `defineAsyncComponent`. Pages only download the widget types they actually use (`Widget.vue`)
- **Widget watchers debounced** — Deep watchers on News, People, and Feed widgets debounced with 300ms delay to prevent API call bursts during editor configuration changes (`NewsWidget.vue`, `PeopleWidget.vue`, `FeedWidget.vue`)
- **Widget initial fetch deferred** — News and Feed widgets use `requestIdleCallback` for initial data fetch, improving perceived page load performance
- **Page + lock fetch parallelized** — Page content and lock status are now fetched in parallel via `Promise.all` instead of sequentially, eliminating ~100ms waterfall (`App.vue`)
- **Engagement settings cached** — Engagement settings now use `CacheService` with 5-minute TTL, consistent with navigation and footer caching (`App.vue`)
- **News widget: collection limit** — Recursive folder scan stops after collecting enough items (default: `max(limit * 4, 200)`) instead of scanning all folders before applying `array_slice` (`PageService.php`)
- **Tree components: progressive rendering** — PageTreeItem and PageTreeSelectItem render max 50 children per node initially with a "Show more" button for additional items. Prevents DOM bloat with large page hierarchies (`PageTreeItem.vue`, `PageTreeSelectItem.vue`)
- **Navigation/footer HTTP caching** — Added `Cache-Control: private, max-age=300, must-revalidate` and `ETag` headers to navigation and footer API responses, consistent with the existing feed API pattern (`NavigationController.php`, `FooterController.php`)
- **Feed widget: unified connection architecture** — Replaced separate source types (Moodle, Canvas, Brightspace, REST API custom) with a single "Connection" concept. Editors choose RSS or Connection; the admin configures connections with presets (Jira, Confluence, SharePoint, OpenProject, AFAS, TOPdesk, Custom, plus LMS types). Presets auto-fill endpoint, auth method, and response mapping. LMS-specific logic (Moodle POST body auth, Canvas context_codes, Brightspace org unit) is preserved internally but hidden from the user. Backwards-compatible with existing connections
- **Calendar widget: IManager refactor** — Replaced `CalDavBackend` with `OCP\Calendar\IManager` for fetching calendars. This properly handles both regular calendars and ICS subscriptions. Calendar identifiers changed from numeric IDs to string keys (`CalendarService.php`, `CalendarController.php`, `PageService.php`)
- **Calendar widget: hide ICS subscriptions from selector** — Nextcloud ICS subscriptions are no longer shown in the calendar selector since external feeds are now managed via the dedicated ICS URL field
- **CSS theming compliance** — Replaced non-standard `--color-text-light` with `--color-text-maxcontrast` in Feed and News widgets. Replaced hardcoded `#fff`/`white` with `var(--color-primary-element-text)`. Replaced hardcoded `border-radius` values with NC variables. Standardized font-weight to 600 (NC convention). Dark theme backgrounds now use `var(--color-primary-element-light)` instead of hardcoded rgba values. Affects: `FeedItem.vue`, `NewsItem.vue`, `CalendarWidget.vue`, `FeedWidgetEditor.vue`

### Fixed
- **Calendar widget wrong events shown** — When an ICS subscription had the same numeric ID as a regular calendar, the widget showed events from the wrong calendar. Fixed by switching to unique string keys via IManager
- **REST API SSRF hardening** — Connection base URL is now re-validated on every fetch request, not just at save time. Prevents SSRF if an admin account is compromised and a malicious URL is injected into stored connection config
- **Version restore not persisting** — Restoring a page version appeared to work but reverted after a hard refresh. Root cause: the backend reused a stale file node after `IVersionManager::rollback()`, and the frontend masked the issue by showing a version preview instead of the actual restored page. Fixed by re-obtaining a fresh file node after rollback and clearing the version preview after restore
- **SSRF hardening: LMS connectors** — Added `validateUrl()` with private IP range blocking to Moodle, Canvas, and Brightspace fetch methods. Previously only the generic REST API connector validated URLs at fetch time
- **SSRF hardening: ICS calendar feeds** — Added private/reserved IP range blocking to `ExternalIcsService::validateUrl()`. Previously only enforced HTTPS without checking for internal network addresses
- **SSRF hardening: SharePoint & Jira** — Added `validateUrl()` to `resolveSharePointSiteId()` and `getJiraProjects()` to block requests to private IP ranges
- **SSRF hardening: Confluence API importer** — Added URL validation with private IP range blocking to the Confluence REST API importer's base URL
- **XXE hardening: Confluence importer** — Added `LIBXML_NONET` flag to `DOMDocument::loadXML()` and `loadHTML()` in the Confluence Storage Format parser to prevent external entity resolution
- **Token handling: Jira project listing** — Replaced direct admin token decryption with `resolveToken()` for consistent token resolution across all connector methods

### Security
- **CSP hardened** — Removed `unsafe-eval` from Content Security Policy. The Vue 3 runtime-only build and TipTap editor do not require `eval()`. This was a historical precaution that is no longer needed (`PageController.php`)
- **HMAC key hardened** — Image proxy signature key now uses `hash('sha256', ...)` for proper 256-bit key derivation instead of string concatenation (`FeedReaderService.php`)
- **API response size limit** — External API responses larger than 10 MB are rejected before JSON parsing to prevent out-of-memory conditions (`FeedReaderService.php`)

### Accessibility
- **Feed widget aria-live** — Loading and content states announced to screen readers via `aria-live="polite"` and `role="status"` (`FeedWidget.vue`)
- **Feed item semantics** — Removed conflicting `role="article"` from feed item `<a>` tags. Added `focus-visible` outline for keyboard navigation (`FeedItem.vue`)
- **Admin loading spinners** — All loading spinners in admin settings now have `role="status"` and `aria-label="Loading"` for screen reader users (`AdminSettings.vue`)
- **Connection card keyboard nav** — Feed connection expand/collapse headers are keyboard-accessible with `tabindex`, `role="button"`, `aria-expanded`, and Enter/Space handlers (`AdminSettings.vue`)
- **Status dot contrast** — Disconnected connection status indicator has a visible border for better contrast on light backgrounds (`AdminSettings.vue`)

### Documentation
- New [SCALABILITY.md](docs/admin/scalability.md) — Comprehensive guide to performance, caching, resilience, rate limiting, GDPR, and enterprise features
- Updated [ARCHITECTURE.md](docs/architecture/overview.md) with scalability section
- Updated [SECURITY.md](docs/admin/security.md) with CSP, rate limiting, GDPR, audit logging, and feed widget security sections
- Updated [ADMIN_GUIDE.md](docs/admin/guide.md) with health check and audit log sections
- Updated [ADMIN_SETTINGS.md](docs/admin/settings.md) with connection testing, export/import, enabling/disabling connections, Clean Start confirmation, and advanced options collapse
- Updated [FEED_WIDGET.md](docs/features/feed-widget.md) with RSS example screenshot, SharePoint setup guide (Entra ID app registration), content type selection, error messages table, and screenshots for all connection types
- Updated [ACCESSIBILITY.md](docs/user/accessibility.md) with feed widget and admin panel accessibility improvements

## [1.2.0] - 2026-04-16 — Accessibility & bug fixes

### Fixed
- **People widget filter persistence** — Filters using the "does not contain" operator were silently converted to "equals" on save because `not_contains` was missing from the backend operator whitelist. After a page refresh the filter showed different results. The operator is now correctly preserved
- **People widget filter value encoding** — Filter values containing special characters (`&`, `<`, `>`, quotes) were HTML-encoded on save via `htmlspecialchars()`, causing them to no longer match user profile data (e.g., "R&D" became "R&amp;D"). Filter values now use a dedicated `sanitizeFilterValue()` that strips tags and control characters without HTML-encoding. Existing corrupted values are automatically decoded on read
- **Editor contrast on colored rows** — Column labels, placeholder text ("Enter text..."), column borders, and "Add Widget" buttons now adapt to dark row backgrounds (Primary color). Previously these elements were nearly invisible on dark backgrounds

### Added
- **Skip-to-content link** — Keyboard users can skip past the navigation to reach the main content directly (App.vue, PublicPageView.vue)
- **Semantic landmarks** — `<header>`, `<main>` elements replace generic `<div>` wrappers for better screen reader navigation
- **ARIA tab patterns** — Proper `role="tablist/tab/tabpanel"` with `aria-selected` on NewPageModal and MediaPicker tab interfaces
- **ARIA combobox pattern** — PageTreeSelect now announces as a combobox with `aria-expanded` and `role="listbox"` on the dropdown
- **Carousel accessibility** — News carousel has `role="region"`, `aria-roledescription`, `aria-label`, `aria-live="polite"` for slide announcements, and respects `prefers-reduced-motion`
- **Live regions** — Loading states use `role="status"` with `aria-live="polite"`, error states use `role="alert"` (App.vue, PublicPageView.vue, CalendarWidget.vue)
- **Focus-visible styles** — Global `*:focus-visible` outline for keyboard navigation visibility
- **Reduced motion support** — Global `prefers-reduced-motion` media query disables all CSS animations and transitions. Carousel autoplay is skipped when the user prefers reduced motion
- **Visually-hidden utility class** — `.visually-hidden` CSS class for screen reader-only content
- **Breadcrumb current page** — `aria-current="page"` marks the active page in breadcrumb navigation
- **Accessibility documentation** — New [ACCESSIBILITY.md](docs/user/accessibility.md) documenting WCAG 2.1 AA compliance status, legal framework (Wet Digitale Overheid), and implemented measures

### Changed
- **Form labels associated with inputs** — All form inputs across 15+ components now have programmatically associated labels via `for`/`id` pairs or `aria-label` attributes (WidgetEditor, NewPageModal, PageTreeSelect, CommentSection, MediaPicker, AdminSettings, PageEditor, NewsWidgetEditor, PeopleWidgetEditor, CalendarWidgetEditor, LinksEditor, NavigationEditor, PublicPageView)
- **Icon buttons accessible** — All icon-only buttons in InlineTextEditor toolbar, carousel navigation, MediaPicker, and AdminSettings now have `aria-label` attributes
- **Draft badge contrast improved** — Fallback text color darkened from `#856404` to `#6d5003` for a 5.5:1 contrast ratio (WCAG AA requires 4.5:1)
- **Dropdown accessibility** — Navigation dropdowns have `aria-haspopup` and `aria-expanded` attributes
- **WelcomeScreen heading** — Changed from `<h1>` to `<h2>` to prevent duplicate h1 on the page
- **Password error announced** — Public page password error message has `role="alert"` for screen reader announcement
- **MediaPicker strings translated** — All hardcoded English strings wrapped in `t()` translation function

### Fixed
- **Focus anti-pattern removed** — Removed `event.target.blur()` in Navigation.vue that was stripping keyboard focus after clicking the page structure button

### Documentation
- Added [ACCESSIBILITY.md](docs/user/accessibility.md) with full WCAG 2.1 AA compliance matrix
- Added accessibility link to README documentation section

## [1.1.2] - 2026-04-10 — App Store listing improvements

### Fixed
- **Telemetry error feedback**: The "Send report now" button now shows the actual server error message (e.g., rate limit, connectivity issue) instead of silently failing
- **MetaVox icon dynamic loaded** — MetaVox sidebar tab icon is no longer a hardcoded SVG copy. Now loads dynamically from the MetaVox app via `imagePath('metavox', 'app.svg')`, so logo changes in MetaVox are automatically reflected in IntraVox. Dark mode handled by Nextcloud's automatic `app-dark.svg` serving

### Changed
- **App Store description rewritten** — Expanded from ~150 to ~250 words, structured in 6 sections: page editor, widgets, collaboration, content management, enterprise, and requirements
- **App Store summary** — Changed to "SharePoint-style intranet pages for Nextcloud — no code required"
- **Author updated to VoxCloud** — Author name, email (info@voxcloud.nl), and homepage (voxcloud.nl) now reflect VoxCloud branding
- **Screenshots expanded from 3 to 7** — Added calendar widget, people widget, news carousel, templates, and engagement screenshots
- **Category `social` added** — Reflects engagement features (reactions, comments, people widget)

### Added
- **Documentation links in App Store** — Editor Guide, Admin Guide, and API Development Guide now linked from the app listing

### Security
- **axios upgraded to 1.15.0+** — Fixes critical SSRF vulnerability via NO_PROXY hostname normalization bypass ([GHSA-3p68-rc4w-qgx5](https://github.com/advisories/GHSA-3p68-rc4w-qgx5))

## [1.1.1] - 2026-04-08 — Support settings & demo data fix

### Added
- **Support contact settings** — New admin settings section for configuring organization name and support contact details. Contact information is included in telemetry for easier support identification
- **App Store screenshots** — Added calendar widget screenshots (layout, editor, primary, sidebar) and updated admin demo data and edit mode screenshots

### Changed
- **Contact info updated** — Author email changed to info@voxcloud.nl and website URL to voxcloud.nl
- **Admin settings refactored** — Extracted support/contact settings into dedicated `SupportSettings` component for cleaner code organization

### Security
- **serialize-javascript upgraded to 7.0.5** — Fixes excessive CPU usage vulnerability in array-like object serialization during webpack build process ([#42](https://github.com/nextcloud/IntraVox/issues/42))
- **brace-expansion upgraded to 5.0.5** — Fixes bracket handling vulnerability ([#40](https://github.com/nextcloud/IntraVox/issues/40))

### Fixed
- **Demo data imports all languages** — Demo data setup now detects the single active language and imports only that language's content, instead of importing all available languages regardless of configuration

## [1.1.0] - 2026-03-29 — Calendar widget & security fixes

### Added
- **Calendar widget** — New widget that displays upcoming events from shared Nextcloud calendars. Supports multi-calendar selection (merged view), configurable date range, event limit, and show/hide time and location. Events are shown with colored date badges matching the calendar color. Recurring events (RRULE) are correctly expanded into individual occurrences
- **Responsive calendar layout** — Calendar widget automatically adapts to available space: 1 column in side columns, 2 columns in medium containers, 3 columns in wide content areas (via CSS container queries)

### Fixed
- **People widget users lost on reload** — User IDs containing dots, `@` signs, or spaces (common in LDAP/SAML/OIDC environments) were silently stripped during save, causing selected users to disappear after page reload ([#41](https://github.com/nextcloud/IntraVox/issues/41))
- **Deploy script OPcache** — Added Apache/PHP-FPM restart to deploy script to clear OPcache after deploying new PHP controllers

### Security
- **Rate limiting on public People API** — Added `AnonRateThrottle` to the public share endpoint for the People widget to prevent user enumeration

### Documentation
- **Language & demo data** — Added guidance that Nextcloud language setting must match the imported demo data language. Added troubleshooting entry for "Admin sees empty Welcome page after demo import" ([#37](https://github.com/nextcloud/IntraVox/issues/37))

## [1.0.1] - 2026-03-09 — Editor group & scenarios

### Added
- **IntraVox Editors group** — A third permission group (`IntraVox Editors`) is now automatically created during setup with Read + Write + Create permissions. This provides a three-tier permission model out of the box: Users (read), Editors (read/write/create), Admins (full access)
- **Scenarios documentation** — New [SCENARIOS.md](docs/admin/scenarios.md) guide with step-by-step recipes for content approval workflows (using the Nextcloud Approval app and MetaVox) and department-based intranets

### Documentation
- Updated ADMIN_GUIDE, AUTHORIZATION, EDITOR_GUIDE, and README to reflect the new three-group permission model

## [1.0.0] - 2026-03-08 — First stable release

IntraVox 1.0 marks the first stable release. After 19 iterative releases, the app offers a complete intranet platform: a full page builder with 10+ widget types, page versioning, templates, public sharing, RSS feeds, engagement (reactions & comments), draft/published workflow, concurrent edit protection, and multi-language support. The JSON page format and REST API are considered stable from this version onward.

### Added
- **Page locking** — Pessimistic locking prevents concurrent edits. When a user starts editing a page, other users see who is editing and the Edit button is disabled. Locks auto-expire after 15 minutes of inactivity, with a 60-second heartbeat to keep active sessions alive. Locks are released on save, cancel, navigation, and tab close
- **Lock safety net in API** — Backend `updatePage()` rejects saves with HTTP 409 if the page is locked by another user, preventing data loss even if the frontend check is bypassed
- **Force unlock for admins** — IntraVox Admins can force-release a page lock held by another user (e.g. after a browser crash). Includes confirmation dialog to warn about potential unsaved changes
- **Draft pages** ([#32](https://github.com/nextcloud/IntraVox/issues/32)) — Pages can be saved as "Draft" or "Published". Draft pages are only visible to users with write permission and are hidden from read-only users, public shares, search results, RSS feeds, and the page tree. Editors see a clickable status badge in edit mode to toggle between Draft and Published, and a "Draft" indicator in view mode. Backward compatible: existing pages without a status field default to Published
- **Duplicate row** ([#32](https://github.com/nextcloud/IntraVox/issues/32)) — Editors can duplicate a complete row (including all columns and widgets) with a single click. The duplicate button appears in the row controls next to the delete button
- **Sticky edit toolbar** ([#32](https://github.com/nextcloud/IntraVox/issues/32)) — The header toolbar with Save/Cancel buttons stays fixed at the top of the viewport when scrolling, making it accessible on long pages

### Changed
- **Page lock translations** — Lock-related UI strings translated to English, Dutch, German, and French
- **Draft/duplicate translations** — Draft, Published, and Duplicate row strings translated to English, Dutch, German, and French
- **New pages default to Draft** — Newly created pages (both blank and from template) start as Draft and automatically open in edit mode so editors can begin working immediately

### Fixed
- **Links widget tile overflow** — Tiles in narrow containers (sidebar, small columns) no longer shrink to unreadable vertical text. Tiles auto-wrap to the next row when there isn't enough horizontal space, while respecting the configured column count when space allows

### Documentation
- **Editor guide** — Added sections for sticky toolbar, page locking, draft/published status with visibility table, duplicate rows, and updated creating new pages workflow
- **Admin guide** — Added page locking and draft pages sections, updated security considerations
- **README** — Added page editor features (duplicate rows, sticky toolbar, page locking, draft/published), new feature sections with screenshots, updated security section

## [0.9.18] - 2026-03-07

### Added
- **Spacer widget rendering** - Spacer widget now renders correctly in view mode with configurable height (10-200px). Previously fell through to "Unknown Widget Type" error display
- **Links widget tiles layout** - Links widget now supports a `tiles` layout alongside the existing `list` layout. Tiles display a larger icon (36px) with a separate title and subtitle on two lines, creating a card-style presentation. Editors can switch between layouts and set title/subtitle per link in tile mode
- **30+ extra link icons** - Added icons for common intranet use cases: folders, chat, dashboard, contacts, forms, code, support, security, organization, news, and more
- **5 unique demo showcases** with rich, diverse layouts demonstrating all widget types:
  - **de-linden** (Universiteit) — 4 photos, 1/2/3/4-column rows, video, people grid, SURF services, right sidebar
  - **van-der-berg** (Advocatenkantoor) — 3 photos, header row, news grid, file widgets, no sidebars
  - **gemeente-duin** (Gemeente) — 3 photos, 1/2/3/5-column rows, left sidebar, news list
  - **de-bron** (Zorggroep) — 3 photos, 4-column department overview, people cards, video, file widgets, right sidebar
  - **horizon-labs** (Tech startup) — 2 photos, news carousel, culture row, right sidebar

### Documentation
- **Showcases guide** (`SHOWCASES.md`) — Complete documentation of all 5 showcases: widget coverage matrix, technical structure, background color guidelines, image handling, and people widget portability
- **Editor guide updated** — Added documentation for file, spacer, news, and people widgets; updated column support from 1-3 to 1-5; documented collapsible rows, header rows, and side columns
- **Export/import updated** — Widget types list expanded from 6 to 10 (added links, file, news, people)

## [0.9.17] - 2026-03-01

### Added
- **OpenAPI documentation for template endpoints** - Five template API endpoints now fully documented in OpenAPI spec (`GET /api/templates`, `GET /api/templates/{id}`, `POST /api/pages/from-template`, `POST /api/templates`, `DELETE /api/templates/{id}`)
- Template request/response schemas (TemplatePreview, TemplateCreateFromRequest, TemplateSaveRequest) with examples for all endpoints
- "Templates" tag in OpenAPI spec for better API organization and discoverability

### Documentation
- **Template API Quickstart** (`TEMPLATE_API_QUICKSTART.md`) - 5-minute guide with code examples in cURL, JavaScript, Python, and PHP for getting started with template API
- **OpenAPI Tooling Guide** (`OPENAPI_TOOLING.md`) - Complete guide for Swagger UI, Postman integration, code generation, and API testing tools
- **API Development Guide** (`API_DEVELOPMENT_GUIDE.md`) - Best practices for adding endpoints and maintaining OpenAPI spec, with template endpoints as case study

### Security
- **serialize-javascript upgraded to 7.0.3** - Fixed HIGH severity RCE vulnerability (CVE, CVSS 8.1) via npm override. Addresses code injection risk in RegExp.flags and Date.toISOString() during webpack build process

## [0.9.16] - 2026-02-23

### Added
- **RSS feed** - Personal RSS feed for each user with token-based authentication, feed media endpoint, conditional requests (ETag/Last-Modified), and brute force protection
- **RSS feed settings UI** - Generate, regenerate, and revoke feed tokens with configurable scope (my language / all languages) and item limit
- **RSS feed sharing policy** - Feed respects Nextcloud's "Allow users to share via link" admin setting; shows clear error when disabled
- **RSS feed cross-language links** - Feed items link via `#page-{uniqueId}` format, automatically resolving pages across language folders

### Changed
- **Dummy text generator** - Removed `=dad()` alias, only `=dadjokes()` and `=lorem()` are now supported
- **`=lorem()` rich formatting** - Now generates richly formatted content showcasing all text widget capabilities: headings, blockquotes, bullet lists, tables, ordered lists, and mixed inline marks (bold, italic, code, underline, strikethrough)
- **Dummy text multilingual labels** - `=lorem()` section headings, table columns, and status labels are now localized for EN, NL, DE, and FR
- **Documentation** - Added RSS feed admin setup guide with GroupFolder permission requirements (Read + Share), ACL examples, and troubleshooting

### Fixed
- **People widget "Invalid Date"** - Birthdate now correctly displayed regardless of Nextcloud locale settings. Added backend normalization of locale-specific date formats (DD-MM-YYYY, DD/MM/YYYY, DD.MM.YYYY) to ISO 8601 before sending to frontend, with additional frontend fallback for edge cases
- **RSS feed empty for ACL users** - Documented that GroupFolders requires both Read and Share permissions for public feed endpoints; updated all permission tables and recommendations
- **Webpack build failure** - Added `string_decoder` and `buffer` to webpack resolve.fallback to fix build error caused by `@nextcloud/dialogs` 7.3.0 pulling in Node.js core modules via sax/is-svg

## [0.9.15] - 2026-02-19

### Fixed
- **MetaVox sidebar on NC33** - MetaVox metadata tab now works in IntraVox on Nextcloud 33, where MetaVox registers via the new scoped globals API instead of the legacy OCA.Files.Sidebar API
- MetaVox mock Node object now passes correct `mountType` and `mountPoint` attributes (camelCase) so groupfolder detection works properly

## [0.9.14] - 2026-02-18

### Added
- **Nextcloud 33 support** - App now supports Nextcloud 32 and 33 (PHP 8.2+ required)
- **Page nesting depth** increased from 3 to 5 levels for deeper page hierarchies
- **Dummy text generator** (easter egg) - Type `=dadjokes(3,5)` or `=lorem(2,4)` in a text widget and press Enter to generate dummy content (inspired by MS Word's `=rand()`)
- **Birthdate field** support in People widget - display, filter (`is_today`, `within_next_days`)
- **Bluesky** social link support in People widget
- **Date filter operators** for People widget: `is today`, `within next X days`

### Fixed
- **People widget display options** now correctly control rendered fields in grid layout
  - Removed `gridShowFields` override that forced fields off
  - Removed hardcoded `layout !== 'grid'` template restrictions
  - Removed CSS rule that hid headline in grid layout
  - `showFields` is now the single source of truth across all layouts
- All display option checkboxes now always visible in editor (no longer hidden per layout)
- `showFields` whitelist expanded in backend (PageService.php) to support all 15 field types
- Legacy `title` field synced with `role` for backwards compatibility
- Heading widget bottom spacing increased
- Comment cascade delete now properly deletes replies and updates count
- Security: markdown-it updated to 14.1.1 (ReDoS fix in linkify inline rule)
- Security: ajv updated to 8.18.0 (CVE-2025-69873 ReDoS fix)

### Changed
- Twitter links now point to x.com instead of twitter.com
- Dependency updates: axios 1.13.5, qs 6.14.2, webpack 5.105.0, ajv 8.18.0

## [0.9.13] - 2026-02-12

### Added
- **People widget** for displaying user profiles with Card, List, and Grid layouts
- Manual selection or filter-based user selection
- Group filtering with "is one of" operator for multiple groups
- Field filtering with equals, contains, does not contain, is not empty, is empty operators
- Customizable display options (avatar, name, role, headline, email, etc.)
- Pagination with "Show more" when more users match filters
- LDAP/OIDC custom field support (auto-detected)
- User search in Nextcloud Unified Search

### Changed
- Filter fields ordered to match Display Options structure
- Social links combined into single toggle (X/Fediverse)
- Phone number display default changed to off (privacy)

### Fixed
- `profileEnabled` excluded from custom fields display
- Dark background text contrast in People widget
- Column alignment in card layout

## [0.9.12] - 2026-02-09

### Changed
- Templates now install automatically during app install/update (no longer requires manual `occ intravox:setup`)
- Existing templates are preserved (idempotent)

## [0.9.11] - 2026-02-09

### Added
- **Page templates** - 7 default templates (Department, Event, Knowledge Base, Landing Page, News Article, News Hub, Project)
- Visual template preview cards with SVG layout schematic
- Complexity indicator (Simple/Medium/Advanced) and widget count statistics
- Template translations for NL, EN, DE, FR
- Stock images for all templates
- Enlarged template modal with improved gallery

## [0.9.10] - 2026-02-05

### Fixed
- **Text editor table spacing** - Tables no longer double-space or corrupt formatting on save
- Asterisks no longer appear after saving bold/italic combined with underline
- Preserved user blank lines between tables while preventing spacing growth
- Home breadcrumb link in public share view

## [0.9.9] - 2026-02-04

### Added
- **Maintenance tab** in admin settings to scan, recover, and delete orphaned GroupFolder data

### Changed
- Renamed "GroupFolders" to "Team Folders" to match Nextcloud App Store naming
- Specific error messages for different Team Folders failure scenarios
- Full translations in Dutch, German, and French

## [0.9.8] - 2026-02-01

### Added
- **Public sharing** via Nextcloud share links with full anonymous page access
- **Password-protected shares** with session-based auth and brute force protection
- Share dialog with scope indicator and password badge
- Admin shares overview in admin settings
- Public news widget and media access via share token
- Links widget color system redesign with container and per-link background options
- Telemetry expansion (country code, database type, OS family, web server, Docker detection)

### Fixed
- Public share page tree only showing homepage for language-root shares
- Links widget contrast issues on light backgrounds
- Webpack chunk caching (`TypeError: n[e] is undefined` after rebuild)

### Security
- Share token validation before any data access
- Share scope path enforcement prevents access outside shared folder
- Anonymous rate throttling (60 req/min) on all public endpoints
- Password brute force protection (10 attempts/min per IP)

### Removed
- Unused HMAC token system (dead code from earlier approach)

## [0.9.7] - 2026-01-26

### Fixed
- **Code blocks** no longer corrupt after editing - backticks no longer accumulate when saving and re-editing
- Fixed double-processing of nested `<code>` tags inside `<pre>` elements

## [0.9.6] - 2026-01-20

### Changed
- Use `OCP\DB\Exception` with `getReason()` instead of Doctrine exceptions (Nextcloud coding standards)

### Security
- Updated enshrined/svg-sanitize from ^0.20 to ^0.22 (medium severity fix)

## [0.9.5] - 2026-01-20

### Fixed
- **PostgreSQL compatibility** - Handle duplicate key errors (SQLSTATE 23505) in SetupService
- LicenseService null check for shared folder to prevent crashes
- AnalyticsService improved exception handling for unique constraints

## [0.9.2 - 0.9.4] - 2026-01-19 to 2026-01-20

Certificate updates and App Store re-registration. No functional changes.

## [0.9.1] - 2026-01-19

### Added
- **Statistics tab** in admin settings with page counts per language
- **Anonymous telemetry** with opt-in usage statistics (page counts, Nextcloud/PHP version info)
- Translation script (`npm run l10n`) for generating JavaScript translation files

### Fixed
- PHP 8.4 implicit nullable parameter deprecation warning

## [0.9.0] - 2026-01-17

### Added
- **Analytics API** for tracking page views and statistics
- **Bulk Operations API** for batch delete, move, update (admin only, max 100 pages)
- **OCS Media Routes** for external API access via Basic Auth
- **API Error Handling** with consistent responses, `errorId` for support correlation, and `ApiErrorTrait`

### Security
- File upload extension whitelist with MIME type validation and `getimagesize()` cross-check
- SVG sanitization with dangerous element detection
- Path traversal protection (URL-encoded, Unicode, null bytes)
- Admin-only endpoints properly secured

### Fixed
- Sidebar closes on page navigation (prevents stale data display)

## [0.8.9] - 2026-01-15

### Fixed
- **Widget color consistency** - Centralized `colorUtils.js` for consistent dark background detection
- Links widget row background inheritance and Primary color variable
- News widget text contrast on dark row backgrounds

### Changed
- All widgets refactored to use consistent background handling pattern

## [0.8.8] - 2026-01-15

### Added
- **Version History UI** redesigned with Files App styling and relative time formatting
- News widget background color support (None/Light/Primary)
- Field-type specific filter operators for MetaVox (date, number, select, multiselect, checkbox)
- Dynamic value inputs adapting to field type (date picker, number input, dropdown)

### Fixed
- Sidebar state preservation during refresh (uses `v-show` instead of `v-if`)

## [0.8.7] - 2025-12-30

### Added
- **Links widget page selector** - Select internal pages from dropdown with auto-fill
- **Links widget drag-and-drop** - Reorder links by dragging

### Fixed
- Row drag-and-drop: prevent rows from being dropped into columns
- News widget excerpts: strip markdown from preview text
- News widget shared library images from `_resources`
- Links widget data persistence for internal page links

## [0.8.6] - 2025-12-29

### Added
- **Clean Start** option in Demo Data settings to reset a language to empty content

### Fixed
- Added ~80 missing translation keys for row controls, widgets, and versions
- Row controls styling on colored backgrounds

## [0.8.5] - 2025-12-29

### Added
- **Publication date filtering** for News widget using MetaVox date fields
- **Collapsible rows** - SharePoint-style collapsible sections with customizable titles and default collapse state

### Changed
- Editor consolidated: WidgetEditor now uses InlineTextEditor component

### Fixed
- Nested list styling visual hierarchy (cycling list markers per indent level)
- Row controls visibility on colored row backgrounds

## [0.8.4] - 2025-12-27

### Added
- **News widget** with List, Grid, and Carousel layouts
- MetaVox filtering support for news items
- Complete OpenAPI specification for OCS API Viewer
- News widget translations for EN, NL, DE, FR

## [0.8.2] - 2025-12-27

### Added
- **Table support** in text widgets (insert, edit, resize, add/remove rows and columns)
- Compact toolbar mode for narrow columns (auto-detects <400px width)
- Material Design icons for toolbar

### Fixed
- Row drag-and-drop widget type preservation (stable row IDs instead of volatile indices)
- Shared media library 500 error (route parameter mismatch)
- Links editor UI consistency and delete action visibility

## [0.8.1] - 2025-12-21

### Changed
- English demo homepage updated to match Dutch layout structure

## [0.8.0] - 2025-12-21

### Added
- **Row drag-and-drop** reordering in page editor
- **Export/Import system** with ZIP files for full site backup and migration
- **Confluence HTML import** for Atlassian migration
- **MetaVox metadata** export/import integration
- **Shared Media Library** with `_resources` folder and hierarchical folder navigation
- **SVG image support** with server-side sanitization (enshrined/svg-sanitize)
- MediaPicker component with 3-tab interface (Upload, Page Media, Shared Library)

### Changed
- Header row default transparency
- Navigation horizontal scrollbar for long menus
- Toolbar active state contrast improved (WCAG compliant)
- Dynamic link/selection colors per row background

### Security
- `@PublicPage` removal (all pages require authentication)
- `parentPageId` validation
- Enhanced path sanitization and ZIP slip prevention
- Import authorization checks
- Comment IDOR prevention
- iframe sandboxing
- Sensitive log masking

### Fixed
- Import folder structure preservation for nested pages
- MediaPicker SVG preview loading
- GroupFolder setup idempotency
- Page settings persistence

## [0.7.1] - 2025-12-13

### Fixed
- Added 60+ missing engagement-related translation keys for DE, FR, and NL

## [0.7.0] - 2025-12-13

### Added
- **Emoji reactions** on pages (18 emoji options)
- **Comments system** with threaded replies and comment reactions
- Admin engagement settings (global enable/disable)
- Page-level engagement settings (per-page overrides)
- Image link target option (open in same/new tab)

### Changed
- Smart cache refresh (50% fewer API calls)
- localStorage persistence (75% faster initial load)
- Lazy loading sidebar (67% faster)

## [0.6.1] - 2025-12-11

### Fixed
- Clickable image links
- Image crop position (top/center/bottom)

## [0.6.0] - 2025-12-09

### Added
- **Welcome screen** for fresh installations with setup instructions
- **Clickable image links** (to internal pages or external URLs)
- **Video widget** with multi-platform support (YouTube, Vimeo, PeerTube, local files)
- Admin settings with Video Domains management (presets + custom servers)
- Unified media folder structure (`_media/` instead of `images/`)
- New search icon for Nextcloud unified search integration

### Changed
- Complete translations for NL, DE, FR languages
- Default video domains include privacy-friendly platforms
- Performance optimizations for page loading

## [0.5.20] - 2025-12-05

### Added
- **Widget duplicate** button for header row and side column widget toolbars
- Generic `duplicateWidgetGeneric()` helper for all zones

## [0.5.19] - 2025-12-05

### Changed
- **Performance Optimization v2** - Request-level caching for directory listings and folder permissions
- Replaced `setInterval` language polling with MutationObserver
- Replaced `JSON.parse`/`stringify` with `structuredClone()` for faster deep cloning

## [0.5.x Patch Releases] - 2025-12-03 to 2025-12-05

### Fixed
- **v0.5.16** - Admin translations: regenerated l10n `.js` files from `.json` files
- **v0.5.15** - Performance: reduced page load time from ~11s to ~1-2s; fixed admin settings translations
- **v0.5.14** - Header row widgets no longer silently lost when saving a page
- **v0.5.13** - Navigation save handling for both wrapped/unwrapped data formats; admin demo data translations
- **v0.5.12** - Demo data path detection for custom apps directories, Docker, and non-standard setups
- **v0.5.11** - Permission groups created correctly during App Store installation; sync all admins to IntraVox Admins group
- **v0.5.10** - GroupFolders dependency clarification in app description
- **v0.5.9** - App Store release: added app icons, fixed "image not found" error
- **v0.5.8** - Clean build for App Store submission

## [0.5.7] - 2025-12-03

### Added
- **GroupFolder ACL authorization** - PermissionService integration with Nextcloud GroupFolder ACLs
- Users without folder access cannot see content in navigation
- Direct URL links blocked for unauthorized users
- Read-only users don't see Edit/New Page buttons
- All API endpoints enforce server-side permission checks

## [0.5.6] - 2025-12-01

### Added
- **PageTreeSelect** - Hierarchical page selector in navigation editor
- Promote/Demote buttons to move navigation items up/down hierarchy levels
- Mutually exclusive page/URL fields in navigation items

### Fixed
- External URL target selector

## [0.5.5] - 2025-11-30

### Added
- **Page Tree modal** for hierarchical page navigation
- **Side column** support for page layouts
- Improved breadcrumb navigation

### Changed
- Removed all debug statements from Vue components and PHP controllers
- Improved widget sanitization (preserve id, image properties, links, dividers)
- Extended background color support in sanitizer
- Restructured demo data with proper image organization

## [0.5.0] - 2025-11-29

### Changed
- Fixed LinksWidget link names (use `text` property)
- Removed "Add to navigation" option from new page creation modal
- Standardized on `uniqueId` everywhere, removed legacy `pageId` usage
- Fixed navigation links after editing (uniqueId normalization)
- Removed unused search indexing code

## [0.4.13] - 2025-11-24

### Added
- **Folder-level permission filtering** in navigation
- Users only see navigation items for pages they have access to
- External/custom URL links remain visible to all users
- Request-level permission caching for performance
- Recursive filtering: parent items without accessible children are hidden

## [0.4.12] - 2025-11-24

### Added
- **Frontend Cache Service** with in-memory caching (5-minute TTL)
- Parallel API calls for initial page load (pages, navigation, footer)
- Breadcrumb included in page API response (reduces API calls by 50%)

### Changed
- Breadcrumb shows current page as clickable item
- New page creation creates siblings instead of children

### Performance
- 50% reduction in API calls during navigation
- ~95% faster page loads for cached content
- ~60% faster initial application load

## [0.4.10] - 2025-11-24

### Changed
- **Filesystem timestamps** replace manual timestamp management in JSON files
- Page metadata now uses `getMTime()` directly

### Fixed
- Metadata API for uniqueId-based lookups
- Details panel page metadata loading

## [0.4.6] - 2025-11-22

### Added
- **Footer editing** on homepage with full markdown support
- **Granular ACL-based permission system** at folder level (department-specific editing)
- Enhanced Dutch translations for all UI elements
- Path-based home page detection (language-agnostic)

## [0.4.1] - 2025-11-18

### Added
- **Nextcloud Unified Search** integration - IntraVox pages searchable via Ctrl+K
- Searches in titles, headings, and content with direct navigation

### Removed
- Custom search UI (replaced by native Nextcloud search)

## [0.4.0] - 2025-11-16

### Added
- **Links widget** with grid layout support and Material Design icons
- Import pages command for bulk page creation from JSON files
- Demo data deployment scripts

### Changed
- Reduced vertical spacing throughout page viewer and editor for better content density
- Fixed HTML entity encoding in navigation

## [0.3.0] - 2025-11-13

### Changed
- **Dropdown navigation redesign** with custom HTML dropdowns and clean styling
- Increased border-radius on page rows for better visual hierarchy
- Removed PageCacheNotification component
- Debug logging cleanup

## [0.2.9] - 2025-11-12

### Added
- **Navigation system rewrite** with three-level hierarchy support
- Mobile hamburger menu with collapsible levels
- Desktop cascading dropdown using NcActions
- Desktop megamenu with grid-based layout

### Changed
- Nextcloud-compliant styling (removed gradients, transform effects)
- All navigation types use Nextcloud Vue 3 components

## [0.2.8] - 2025-11-11

### Added
- **UniqueId-based URLs** for permanent page identification
- `/p/{uniqueId}` route for shareable links
- Automatic uniqueId generation for legacy pages
- Open Graph meta tags

### Changed
- Removed all debug logging (production readiness)

## [0.2.7] - 2025-11-11

### Added
- **Version history** with automatic version creation on save and one-click restoration
- Page Details sidebar with version tracking

## [0.2.6] - 2025-11-11

### Added
- **Markdown storage** for content (WYSIWYG editor with markdown backend)
- UUID v4 for page uniqueIds

### Changed
- New pages open in edit mode automatically
- Text widgets start empty (no placeholder text)

## [0.2.5] - 2025-11-10

### Added
- **Readable page IDs** generated from titles (e.g., "Welcome" becomes "welcome")
- Clean, readable URLs (e.g., `/apps/intravox#/welcome`)
- Automatic duplicate handling with numbered suffixes
- New pages immediately visible in Files app via scanner integration

### Fixed
- 400 Bad Request errors on page creation/saving
- Text selection contrast with theme colors
- Divider widgets adapt to row background colors

## [0.2.4] - 2025-11-10

### Changed
- Simplified row background color palette to 4 essential theme colors
- Complete overhaul of text color inheritance for proper contrast

## [0.2.3] - 2025-11-10

### Added
- Automated release creation and rollback scripts

## [0.2.2] - 2025-11-10

### Added
- **Row background colors** with theme-based color picker
- **Footer component** with rich text editing for homepage
- PageActionsMenu component with 3-dot menu
- Link support in InlineTextEditor

## [0.2.1] - 2025-11-10

### Fixed
- Column layout persistence (row.columns now saved correctly)
- Cache-busting TypeError in PageController

### Changed
- Default column count changed from 3 to 1
- Removed URL routing (reverted to simple navigation)

## [0.2.0] - 2025-11-09

### Added
- **URL routing** with language support (`/apps/intravox/{language}/{pageId}`)
- Browser back/forward button navigation support
- Footer infrastructure (temporarily disabled)

### Fixed
- Column layout bug where widgets were spread across multiple columns

## [0.1.0] - 2025-11-09

### Added
- **Initial release** of IntraVox
- Multi-language support (Dutch, English, German, French)
- Drag-and-drop page editor with flexible grid layouts (1-5 columns)
- Rich widget types: text, headings, images, links, files, dividers
- Megamenu and dropdown navigation systems
- Real-time collaborative editing via GroupFolders
- Language-aware content management
- Responsive design for mobile, tablet, and desktop
- Vue.js 3 frontend with Nextcloud integration
- PHP backend with CSRF protection
- Comprehensive i18n support with .po files
