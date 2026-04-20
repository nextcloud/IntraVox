# IntraVox Scalability Review

Code review gericht op schaalbaarheid en laadsnelheid. Bevindingen op basis van de huidige main-branch.

## Samenvatting

De app werkt goed op kleine intranetten (< 500 pagina's, < 1k Nextcloud-users, geen externe feeds). Er zijn drie clusters van zorgen die in grotere deployments problematisch worden:

1. **Feed widget** — thundering herd risico bij cache-miss, geen singleflight, geen rate limit, blokkeert PHP-workers.
2. **People widget** — `callForAllUsers()` in filter-mode zonder group-scope, breekt rond 1k-5k accounts.
3. **Veel pagina's** — alle metadata komt van disk (JSON files), geen index. Listing, search en tree worden O(N) op disk I/O.

Onderliggend patroon: **geen caching/indexing op gedeelde hot paths, alles wordt per request opnieuw berekend**. Prima voor een klein intranet, niet voor de "enterprise ready" claim in [appinfo/info.xml](../appinfo/info.xml).

De page-load zelf (bootstrap + widgets in één pagina) is in orde — 4-5 requests bij warme cache, widgets batchen, parallel bootstrap. Wel een paar quick wins mogelijk.

---

## 1. Feed widget — schaalbaarheid bij 1k concurrent users

### Flow
`FeedWidget.vue:110` mount → GET `/apps/intravox/api/feed/external` → [FeedReaderController#getFeed](../lib/Controller/FeedReaderController.php) → [FeedReaderService::fetchFeed:102](../lib/Service/FeedReaderService.php#L102) dispatched naar `fetchRss()` / `fetchMoodle()` / `fetchCanvas()` / `fetchBrightspace()` / `fetchGenericRestApi()`.

### Wat goed zit
- Server-side cache via `ICacheFactory::createDistributed('intravox-feeds')`, TTL 15 min — [FeedReaderService.php:22,84](../lib/Service/FeedReaderService.php#L22).
- RSS-cache is **bronnen-scoped** (cross-user deelbaar). 1k users op dezelfde RSS = 1 cache hit.
- SSRF-bescherming: private IP ranges geblokkeerd — [FeedReaderService.php:1484-1508](../lib/Service/FeedReaderService.php#L1484).
- Outbound timeout 10s — [FeedReaderService.php:23](../lib/Service/FeedReaderService.php#L23).
- Graceful failure: exception → `['items' => [], 'error' => '...']`.

### Gaps

| Gap | Evidence | Impact bij 1k users |
|---|---|---|
| **Geen singleflight/mutex bij cache miss** | [FeedReaderService.php:120-129](../lib/Service/FeedReaderService.php#L120) | Cache expiry → 1000 parallelle externe HTTP-calls naar dezelfde bron. Klassieke thundering herd. |
| **LMS-feeds hebben per-user cache-key** | `'_user_' . $userId` suffix in [FeedReaderService.php:1562-1570](../lib/Service/FeedReaderService.php#L1562) | 1000 LMS-users = 1000 cache entries = 1000 externe calls, óók binnen TTL. |
| **Geen rate limit op FeedReaderController** | Ontbreekt; `FeedController.php:43` heeft wél `#[AnonRateThrottle(30,60)]` | Inconsistent. Geen bescherming. |
| **Geen background refresh job** | Pure pull-on-demand | Cache-expiry valt altijd op een user. |
| **10s timeout × 1000 requests** | PHP-worker blokkeert tijdens outbound call | Trage externe bron = worker pool exhaustion = hele Nextcloud-instantie onresponsief. |
| **Geen circuit breaker** | Geen fallback bij kapotte bron | Instabiele externe feed kan hele app platleggen. |
| **Frontend FeedWidget geen client-side cache** | [FeedWidget.vue:80-108](../src/components/FeedWidget.vue#L80) fetchet elke mount, deep watcher bij property-change | Edit-mode = bursts van calls. |

### Failure mode (waarschijnlijk scenario)
Externe RSS-bron heeft 5-10s latency. Cache expiret. 1000 users refreshen binnen korte tijd. Alle PHP-workers (typisch 20-50) wachten tegelijk op de trage bron → **hele Nextcloud-instantie onresponsief, niet alleen IntraVox**.

### Aanbevelingen
1. **Singleflight** bij cache miss in `FeedReaderService::fetchFeed` — distributed lock of "currently fetching" flag.
2. **Rate limit** op `FeedReaderController` gelijktrekken met `FeedController`.
3. **Background job** die populaire feeds proactief refresht vóór TTL-expiry.
4. **Circuit breaker** — na N consecutive failures externe bron tijdelijk als "down" markeren.
5. **Kortere outbound timeout** (3-5s ipv 10s) om worker-blocking te beperken.

---

## 2. People widget — resource-intensiteit

### Flow
`PeopleWidget.vue:187-241` → GET `/api/people` → [PeopleController::getPeople:178-248](../lib/Controller/PeopleController.php#L178-L248) → [UserService](../lib/Service/UserService.php).

### Twee modes met totaal verschillende profielen

**Efficiënt: handmatige selectie of filter + group**
- `$group->getUsers()` in [UserService.php:141-155](../lib/Service/UserService.php#L141-L155) — alleen users in die groep.
- Schaalt tot tientallen duizenden accounts, mits group-filter gebruikt wordt.

**Kritiek: filter op attributen zónder group** — [UserService.php:225-234](../lib/Service/UserService.php#L225-L234)
```php
$this->userManager->callForAllUsers(function (IUser $user) use (...) {
    $profile = $this->buildUserProfile($user);  // account + groups + status per user
    if ($this->matchesFilters($profile, $filters, $operator)) { ... }
});
```
Itereert **alle users in Nextcloud**, bouwt per user een volledig profiel op (account + groups + status), filtert daarna pas in PHP. Birthday-filters parsen `DateTime` per user in [UserService.php:623-658](../lib/Service/UserService.php#L623-L658).

### Schaal per totaal aantal Nextcloud-accounts

"Totaal aantal accounts in NC", **niet concurrent users**. Voor één enkele render door één user:

| Accounts in NC | Scenario "filter role=HR" (geen group) | Status |
|---|---|---|
| < 1.000 | Traag maar doenlijk | Acceptabel |
| 1.000 | 5-10s response, ~500MB RAM | Problematisch |
| 10.000 | 30-60s, timeout, 2GB+ RAM | Onwerkbaar |
| 100.000 | OOM / PHP timeout | Breekt |
| 10.000 + group-filter | ~500ms | Prima |

Concurrent users verergert: geen server-side cache ([UserService.php](../lib/Service/UserService.php) heeft geen ICache integratie), dus 50 users × 5k accounts = 50 workers die elk 5k profielen bouwen.

### Bijkomende issues
- **LDAP caveat**: `$group->getUsers()` kan 10-30s duren bij groepen met 5000+ leden, geen pagination in Nextcloud API.
- **Status-fetching per user** in loop — [UserService.php:458](../lib/Service/UserService.php#L458) — Nextcloud accepteert array, maar code itereert één-voor-één.
- **Effectieve limit niet scherp**: `maxToCollect = ($limit + $offset) * 2` in filter-mode-zonder-group [UserService.php:225](../lib/Service/UserService.php#L225) — laadt 100-200 users in geheugen.

### Aanbevelingen
1. **UI-guardrail**: filter-mode zonder group blokkeren (of waarschuwing) wanneer NC > 1k accounts heeft.
2. **Server-side cache** per filter-combinatie (Redis/ICache, TTL 1h, invalidatie via user-event listeners).
3. **Harde cap** van 200 users/request.
4. **DB-query** voor account-field filtering ipv `callForAllUsers` + PHP-loop — grotere refactor maar structureel juist.
5. **Status-fetch batchen** ipv per-user.

---

## 3. Schaalbaarheid bij veel pagina's

Kern: alles gaat door het **filesystem**, niet door een database. Page-metadata (title, uniqueId, path) wordt niet geïndexeerd.

### Metrics per schaal

| Metric | 100 pages | 1.000 pages | 10.000 pages |
|---|---|---|---|
| `GET /api/pages` | ~150ms | ~1.5s | **15s+ (timeout)** |
| `GET /api/pages/tree` | ~200ms | ~2s | **20s+** |
| `GET /api/search?q=x` | ~250ms | ~3s | **30s+** |
| PageTreeSelect dropdown render | ~300ms | 3s+ (laggy) | **browser hangt** |
| News widget folder met 1k items | — | 2-3s | N/A |

### Bottlenecks met bewijs

| # | Issue | File:line |
|---|---|---|
| 1 | Page listing = disk-walk + JSON parse + ACL-check per pagina | [PageService.php:633,680,708](../lib/Service/PageService.php#L633) |
| 2 | Tree-cache is **per-user-per-taal** ipv shared | [PageService.php:4014-4017](../lib/Service/PageService.php#L4014-L4017) |
| 3 | Search = O(N) fulltext in PHP, leest alle JSON files per query | [PageService::searchPages:4136](../lib/Service/PageService.php#L4136), `listPagesWithContent()` |
| 4 | Permissions O(N), aparte ACL-lookup per pagina | [PageService.php:278,712](../lib/Service/PageService.php#L278) |
| 5 | Frontend rendert alle tree-nodes (geen virtual scrolling) | [PageTreeSelect.vue:204,208](../src/components/PageTreeSelect.vue#L204), [PageTreeModal.vue:94,125](../src/components/PageTreeModal.vue#L94) |
| 6 | News widget folder-scan zonder limit | [PageService.php:4839,4967](../lib/Service/PageService.php#L4839) |
| 7 | Export laadt alles in geheugen (geen streaming) | [ExportService.php:48](../lib/Service/ExportService.php#L48) |

Background jobs (`LicenseUsageJob`, `TelemetryJob`) itereren **niet** over alle pages — dat is goed.

### Aanbevelingen

**Quick wins (1-2 dagen elk):**

| # | Wat | Impact |
|---|---|---|
| 1 | **Shared tree cache** (Redis/APCu, event-invalidatie) ipv per-user — [PageService.php:3958-4021](../lib/Service/PageService.php#L3958-L4021) | ~70% winst op `/api/pages/tree` |
| 2 | **Virtual scrolling** in PageTreeSelect/PageTreeModal (`vue-virtual-scroller`) | Van 5k DOM-nodes naar ~50 |
| 3 | **Paginated tree API** (`?limit=100&offset=0` + `hasMoreChildren` flag, client expandt on-demand) | Initial tree 5k → 500 items |
| 4 | **Hard limit op News widget source-folder** (bv. max 200 items in tijd-gesorteerde window) | Widget snel ongeacht folder-grootte |
| 5 | **Streaming export** (direct naar ZIP ipv in-memory) | Geen OOM bij grote exports |

**Groter project (1-2 weken):**

| # | Wat | Impact |
|---|---|---|
| 6 | Nextcloud **SearchProvider** implementeren (`OCP\Search\ISearchableProvider`) | Search 3s → 100ms, integreert met Ctrl+K |
| 7 | **Page-metadata index** in DB/cache (title, uniqueId, path, modtime) — update via save/delete listener | Listing/tree hoeft geen JSON files meer te parsen voor metadata; lineaire cost verdwijnt |
| 8 | **Batch-permissions endpoint** (N ACL-checks in 1 call) | Reduceert overhead bij tree-rendering |

---

## 4. Page-load (baseline)

### Wat goed zit
- Parallel bootstrap (4 calls) — [App.vue:403-411](../src/App.vue#L403-L411).
- Stale-while-revalidate pattern — [App.vue:545-579](../src/App.vue#L545-L579).
- LocalStorage cache voor pages-list, navigation, footer (5 min TTL) — [CacheService.js:8-9](../src/services/CacheService.js#L8).
- Widgets batchen keurig (geen N+1).
- Koude load ~10 requests, warme load ~4-5.

### Minor issues (geen blockers)
1. **Waterfall** tussen page-load en lock-check — [App.vue:611](../src/App.vue#L611). Hadden parallel gekund, of server-side gecombineerd.
2. **Engagement settings niet gecached** — [App.vue:1299](../src/App.vue#L1299), inconsistent met nav/footer.
3. **Deep watchers** in News/People widgets — [NewsWidget.vue:157-162](../src/components/NewsWidget.vue#L157-L162), [PeopleWidget.vue:168-178](../src/components/PeopleWidget.vue#L168-L178). Re-fetchen bij elke property-change, geen debounce.

---

## 5. Laadsnelheid — verbeter-opportuniteiten

Bundle-situatie: `intravox-main.js` **3.7 MB** ongecomprimeerd, `splitChunks: false` ([webpack.config.js:39](../webpack.config.js#L39)), TipTap + 11 extensions altijd geladen, alle widgets eager geïmporteerd.

### Wat al goed is
- Editor-modals zijn `defineAsyncComponent` — [App.vue:250-259](../src/App.vue#L250-L259).
- Vue runtime-only bundle (webpack alias).
- `__VUE_PROD_DEVTOOLS__` uit in prod — [webpack.config.js:13](../webpack.config.js#L13).
- `loading="lazy"` op widget-images — [Widget.vue:40,49](../src/components/Widget.vue#L40).
- `/api/feed` heeft Cache-Control + ETag — [FeedController.php:107-111](../lib/Controller/FeedController.php#L107) — deze aanpak doortrekken naar nav/footer.

### Quick wins

| # | Wat | File | Geschatte impact |
|---|---|---|---|
| 1 | **TipTap lazy-load** — alleen in edit-mode importeren | [InlineTextEditor.vue:198-205](../src/components/InlineTextEditor.vue#L198-L205) | -500KB bundle, -150ms FCP |
| 2 | **splitChunks aanzetten** (vendors apart) | [webpack.config.js:38-40](../webpack.config.js#L38-L40) | -500KB main, parallel chunk loading |
| 3 | **Widgets lazy** via `defineAsyncComponent` | [Widget.vue:201-205](../src/components/Widget.vue#L201-L205) | -200KB main |
| 4 | **Cache-Control + ETag** op `/api/navigation` en `/api/footer` | [NavigationService.php](../lib/Service/NavigationService.php), [FooterService.php](../lib/Service/FooterService.php) | -1 XHR per refresh, -50-100ms |
| 5 | **Combine `/api/pages/{id}` + lock + reactions-count** in 1 response | [ApiController.php](../lib/Controller/ApiController.php) | -1 RTT (100-150ms) |
| 6 | **Parallelize lock-check** (minimaal als #5 te groot is) | [App.vue:611](../src/App.vue#L611) | -100ms waterfall |
| 7 | **Widget-fetches in `requestIdleCallback`** | [NewsWidget.vue:164-166](../src/components/NewsWidget.vue#L164-L166), [FeedWidget.vue:82-110](../src/components/FeedWidget.vue#L82-L110) | Betere perceived performance |
| 8 | **Preload/prefetch hints** voor main.js en main.css | [templates/main.php](../templates/main.php) | -~100ms LCP |

**Geschat totaal: -1.2MB bundle, -500ms LCP, -675ms TTI. Alle 8 in ~3-4 dagen.**

### Groter project
- Homepage-content als `__INITIAL_STATE__` in [templates/main.php](../templates/main.php) ipv bootstrap-XHR.
- Responsive `srcset` + LQIP voor widget-images.
- Per-request memoization in `PageService::loadPage()`.

---

## Prioritering

Als ik één volgorde moest kiezen, op basis van risico × moeite:

**P0 — hoog risico, klein werk:**
- Feed widget singleflight + rate limit
- Shared tree-cache voor pages
- News widget folder-limit

**P1 — hoge impact, klein werk:**
- Cache-Control op nav/footer
- Combine page + lock endpoint
- TipTap lazy-load + splitChunks
- Virtual scrolling in PageTreeSelect/Modal

**P2 — structureel, groter werk:**
- People widget: UI-guardrail + cache + DB-filter-refactor
- Page-metadata index + Nextcloud SearchProvider
- Feed widget background-refresh job + circuit breaker

**P3 — polish:**
- Engagement settings cachen
- Debounce op widget watchers
- Streaming export
- `requestIdleCallback` op widget-fetches

---

## Disclaimer

Dit rapport is gebaseerd op statische code-analyse. De geschatte response-tijden en bundle-impact zijn berekend op basis van typische waarden en zouden gevalideerd moeten worden met:
- Profiling tegen een realistische dataset (ten minste 1k pages, 1k users).
- Load-test van Feed widget met singleflight gesimuleerd.
- Browser-devtools Performance tab voor FCP/LCP/TTI metingen.

De bundle-grootte (3.7 MB) is afgeleid uit [js/intravox-main.js](../js/intravox-main.js) in de huidige build en kan per build verschillen.
