# Scalability & Enterprise Readiness

IntraVox is designed to scale from small teams to large organizations. This document describes the performance optimizations, resilience patterns, and enterprise features implemented across the application.

## Performance at Scale

### Tested capacity

| Metric | Supported | Notes |
|--------|-----------|-------|
| Nextcloud users | Up to 10,000 | People widget uses group-scoped queries when possible |
| IntraVox pages | Up to 5,000 | Page metadata indexed in database for fast lookups |
| Concurrent users | Up to 1,000 | Singleflight prevents thundering herd on shared resources |
| External feed sources | Unlimited | Circuit breaker and background refresh prevent cascade failures |

### Sizing the server

The numbers above assume the server can run enough PHP workers for the readers
arriving at once. That is the setting most worth getting right, and the default
is tuned for a small instance.

Feeds are what make a page expensive, and only the *first* reader pays. The RSS
cache is keyed on the feed URL with no user in it, so one reader warms a page and
everyone after them is served from that cache for 15 minutes (an hour if the
source is unreachable). Measured: fifteen cached feeds cost **1.6 ms** in total,
against roughly **160 ms each** when they have to be fetched.

That shape is why the client sends one request per page normally and only splits
into several when the server reports it actually had to fetch. A warm page costs
one PHP worker; a cold one briefly costs three.

| Readers at once | Workers to allow | Memory for PHP | Notes |
|---|---|---|---|
| Up to 25 | 30–50 (the default 150 is ample) | 512 MB–1 GB | A single core is fine. APCu alone is enough. |
| 25–150 | 150 (the Apache default) | 2–4 GB | Add Redis: the feed and page caches are then shared between workers instead of duplicated per process. |
| 150–500 | 300–500 | 8–16 GB | Redis required. Give the background job room to run (see below). |
| 500–1,000 | 600–1,000 | 16–32 GB | Redis required, and consider a second app server behind the load balancer. |

Worker count is `MaxRequestWorkers` for Apache, `pm.max_children` for PHP-FPM.
Budget roughly 30–50 MB of memory per worker for IntraVox, and never allow more
workers than that memory divides into — a machine that swaps is slower than one
that queues.

**Redis is the single most valuable addition.** Without a distributed cache each
PHP worker keeps its own copy, so a hundred workers fetch the same feed a hundred
times. With it, one fetch serves them all. Configure it as both
`memcache.distributed` and `memcache.locking`.

**Readers keep the cache warm themselves.** An entry counts as fresh for 15
minutes and is still served for an hour after that, with exactly one request
going on to refresh it in the background. A feed therefore only falls back to a
blocking fetch if nobody opened the page for a full hour — the busier the
instance, the rarer that is. No configuration is needed for this.

`FeedRefreshJob` refreshes *configured feed connections* (the LMS and
issue-tracker integrations set up in the admin screen) every 10 minutes. It does
not pre-warm RSS URLs entered on a widget; those rely on the mechanism above. If
you use connections, the job needs Nextcloud's cron on system cron rather than
AJAX — AJAX cron only runs when somebody loads a page, which is exactly when you
do not want to be fetching.

**How many feed widgets per page is reasonable?** Up to twenty is comfortable.
Beyond that the client sends more than one request per page view, which costs
against the per-user rate limit of 30 requests a minute. A page with fifty-seven
widgets works, but it is three requests every time somebody opens it — consider
splitting it into sub-pages, which also gives readers something they can scan.

### Bundle optimization

The frontend JavaScript bundle is split for fast initial page loads:

| Chunk | Size | Loading |
|-------|------|---------|
| `intravox-main.js` | ~220 KB | Loaded immediately |
| `intravox-vendors.js` | ~2.9 MB | Loaded immediately (shared vendor code, cached by browser) |
| TipTap editor | ~240 KB | Lazy-loaded only when entering edit mode |
| Widget components | ~5-30 KB each | Lazy-loaded per widget type on the page |

All widget components (News, People, Calendar, Feed, Links) are loaded via `defineAsyncComponent`, so pages that don't use a particular widget type never download its code. The TipTap rich text editor and all its extensions are loaded dynamically on first edit — readers never pay the cost.

### Caching strategy

**Server-side:**

| Cache | Scope | TTL | Backend |
|-------|-------|-----|---------|
| Page tree | Per user + language | 5 min | Redis/APCu (distributed) + in-process static |
| Feed results | Per source + config | 15 min | Redis/APCu (distributed) |
| People filter results | Per filter combination | 1 hour | Redis/APCu (distributed) |
| User status | Per user | Per request | In-process |
| File content / permissions / directory listings | Per request | Request lifetime | In-process |

**Client-side:**

| Cache | TTL | Backend |
|-------|-----|---------|
| Pages list | 5 min | localStorage |
| Navigation | 5 min | localStorage |
| Footer | 5 min | localStorage |
| Engagement settings | 5 min | localStorage |
| Current page | 2 min stale-while-revalidate | localStorage |

**HTTP caching:**

| Endpoint | Cache-Control | ETag |
|----------|--------------|------|
| Navigation API | `private, max-age=300, must-revalidate` | Yes |
| Footer API | `private, max-age=300, must-revalidate` | Yes |
| RSS Feed API | `public, max-age=300, must-revalidate` | Yes |
| Image proxy | `public, max-age=86400, immutable` | No |

### Page metadata index

Page metadata (title, uniqueId, path, language, status, modification time) is indexed in a database table (`intravox_page_index`) for fast lookups. This avoids O(N) filesystem traversals for page listing, tree building, and search operations.

The index is updated automatically on page create, update, and delete. The Nextcloud unified search provider (`Ctrl+K`) uses the index for instant title-based search results, with a fallback to full-text filesystem search for content matches.

### Progressive rendering

Page tree components (PageTreeSelect, PageTreeModal) use progressive rendering: only the first 50 children of each node are rendered initially. A "Show more" button loads additional items in batches of 50. This keeps the DOM small even with thousands of pages.

## Resilience

### Feed widget

External feed sources are protected by three layers:

1. **Singleflight lock** — When the cache expires, only the first request fetches from the external source. A request for a *single* feed waits (up to 5 seconds) and then reads from the freshly populated cache. This prevents thundering herd when many users view the same page simultaneously.

   A *batch* request does not wait. The wait is paid per feed and the feeds in a batch are fetched one after another, so a page of twenty widgets could otherwise sleep twenty times five seconds without issuing a single outbound request. A batch fetches the feed itself instead, which is both faster and honest to the reader.

2. **Circuit breaker** — After 3 consecutive failures for a source, the circuit breaker opens and subsequent requests return immediately with a "temporarily unavailable" message. The circuit resets automatically after 5 minutes, or immediately when a successful fetch occurs.

3. **Stale-while-revalidate** — An entry is fresh for 15 minutes and served for an hour beyond that. Past the fresh window a reader still gets the cached copy immediately, while one request refreshes it in the background. Nobody waits behind someone else's refetch.

   `FeedRefreshJob` additionally refreshes configured feed *connections* every 10 minutes. RSS URLs configured on a widget are not pre-warmed; they are kept warm by readers under the rule above.

Additionally:
- HTTP timeout is 8 seconds, so one unreachable source cannot hold a PHP worker indefinitely
- Private IP ranges are blocked (SSRF protection)
- Image URLs are HMAC-signed for proxy requests
- API responses larger than 10 MB are rejected before parsing to prevent OOM

### People widget

The People widget protects against large Nextcloud installations:

- **Group-scoped queries**: When a group filter is set, only users in that group are loaded — the efficient path that scales to tens of thousands of accounts
- **Hard cap**: The unscoped filter path (no group filter) is capped at 5,000 users to prevent OOM and timeouts
- **Distributed cache**: Filter results are cached for 1 hour in Redis/APCu, preventing repeated full user scans
- **Batch status fetching**: User online/away/DND statuses are prefetched in a single batch call instead of one API call per user

### News widget

The News widget folder scanner has a configurable collection limit (default: `max(limit * 4, 200)`) with early exit. Once enough items are collected, the recursive folder scan stops immediately — including after returning from recursive subfolder calls.

### Export

The ZIP export writes pages incrementally to disk instead of accumulating the entire export in memory. Each page is JSON-encoded and written individually to `export.json`, keeping memory usage constant regardless of the number of pages.

## Security

### Rate limiting

Rate limits are applied per endpoint category, not across the board — 14 of the 73
mutating operations carry an explicit limit. The rest rely on Nextcloud's
brute-force protection, which is a different mechanism: it triggers on repeated
*failed authentication* from one address, not on request volume.

| Endpoint category | Limit | Period |
|-------------------|-------|--------|
| Page create / delete | 10 requests | 60 seconds |
| Bulk operations (delete, move) | 5 requests | 60 seconds |
| Comments | 20 requests | 60 seconds |
| Reactions | 30 requests | 60 seconds |
| Feed fetch (authenticated) | 30 requests | 60 seconds |
| Feed fetch (public share) | 30 requests | 60 seconds |
| Analytics tracking | 60 requests | 60 seconds |
| Public page access | 60 requests | 60 seconds |

### Content Security Policy

IntraVox sets a strict CSP on all page responses:
- `script-src: 'self'` — only scripts from the Nextcloud domain
- `frame-src: 'self' [whitelisted video domains]` — only iframes from configured video services
- No `unsafe-eval` or `unsafe-inline`

### Audit logging

Administrative operations are logged with the `IntraVox Audit:` prefix for SIEM integration:

- Bulk delete / move / update (including page IDs and user)
- License key changes
- Organization settings changes
- Engagement settings changes

Logs are written to the Nextcloud log at `info` level and can be filtered by the `IntraVox Audit:` prefix.

### GDPR compliance

When a Nextcloud user is deleted, IntraVox automatically cleans up:

- Analytics view records (hashed user ID)
- Page lock records
- Feed tokens and LMS OAuth tokens (stored in Nextcloud user preferences)

This is handled by the `UserDeletedListener` which listens to Nextcloud's `UserDeletedEvent`.

### Health check

A public health check endpoint is available for monitoring and orchestration:

```
GET /apps/intravox/api/health
→ {"status": "ok", "app": "intravox", "version": "1.3.0"}
```

## Widget performance

### Debounced watchers

All widget components (News, People, Feed) debounce their configuration watchers with a 300ms delay. This prevents bursts of API calls when widget settings are rapidly changed in the editor.

### Deferred initial fetch

News and Feed widgets use `requestIdleCallback` for their initial data fetch, deferring the network request until the browser's main thread is idle. This improves perceived page load performance by prioritizing layout and rendering.

### Parallel bootstrap

The application loads pages, navigation, footer, and engagement settings in parallel on mount (`Promise.all`). Page content and lock status are also fetched in parallel, eliminating a sequential waterfall that previously added ~100ms to page navigation.
