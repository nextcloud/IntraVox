# Schaalbaarheid & enterprise-readiness

IntraVox is ontworpen om te schalen van kleine teams tot grote organisaties. Dit document beschrijft de performance-optimalisaties, resilience-patronen en enterprise-features.

## Performance op schaal

### Geteste capaciteit

| Metric | Ondersteund | Notes |
|--------|-------------|-------|
| Nextcloud-gebruikers | Tot 10.000 | People-widget gebruikt groep-scoped queries waar mogelijk |
| IntraVox-pagina's | Tot 5.000 | Pagina-metadata in DB geïndexeerd voor snelle lookups |
| Gelijktijdige gebruikers | Tot 1.000 | Singleflight voorkomt thundering herd op gedeelde resources |
| Externe feed-bronnen | Onbeperkt | Circuit breaker en background-refresh voorkomen cascade-failures |

### Bundle-optimalisatie

De frontend-JavaScript-bundle is gesplitst voor snelle initial-page-loads:

| Chunk | Grootte | Loading |
|-------|---------|---------|
| `intravox-main.js` | ~220 KB | Direct geladen |
| `intravox-vendors.js` | ~2.9 MB | Direct geladen (gedeelde vendor-code, browser-cached) |
| TipTap-editor | ~240 KB | Lazy-loaded alleen bij edit-modus |
| Widget-componenten | ~5–30 KB elk | Lazy-loaded per widget-type op de pagina |

Alle widget-componenten (News, People, Calendar, Feed, Links) worden via `defineAsyncComponent` geladen, dus pagina's zonder een widget-type downloaden de code nooit. De TipTap rich-text-editor en al z'n uitbreidingen worden dynamisch geladen bij eerste bewerking — lezers betalen die kost nooit.

### Caching-strategie

**Server-side:**

| Cache | Scope | TTL | Backend |
|-------|-------|-----|---------|
| Pagina-tree | Per gebruiker + taal | 5 min | Redis/APCu (gedistribueerd) + in-process statisch |
| Feed-resultaten | Per bron + config | 15 min | Redis/APCu (gedistribueerd) |
| People-filter-resultaten | Per filter-combinatie | 1 uur | Redis/APCu (gedistribueerd) |
| Gebruikers-status | Per gebruiker | Per request | In-process |
| Bestand-content / -permissies / dir-listings | Per request | Request-lifetime | In-process |

**Client-side:**

| Cache | TTL | Backend |
|-------|-----|---------|
| Pagina-lijst | 5 min | localStorage |
| Navigatie | 5 min | localStorage |
| Footer | 5 min | localStorage |
| Engagement-instellingen | 5 min | localStorage |
| Huidige pagina | 2 min stale-while-revalidate | localStorage |

**HTTP-caching:**

| Endpoint | Cache-Control | ETag |
|----------|---------------|------|
| Navigatie-API | `private, max-age=300, must-revalidate` | Ja |
| Footer-API | `private, max-age=300, must-revalidate` | Ja |
| RSS-feed-API | `public, max-age=300, must-revalidate` | Ja |
| Image-proxy | `public, max-age=86400, immutable` | Nee |

### Pagina-metadata-index

Pagina-metadata (titel, uniqueId, pad, taal, status, modification-time) is geïndexeerd in een database-tabel (`intravox_page_index`) voor snelle lookups. Dit vermijdt O(N) filesystem-traversals voor pagina-listing, tree-building en zoek-operaties.

De index wordt automatisch bijgewerkt bij pagina-create, -update en -delete. De Nextcloud unified-search-provider (`Ctrl+K`) gebruikt de index voor instant titel-gebaseerde zoekresultaten, met fallback naar full-text filesystem-search voor content-matches.

### Progressive rendering

Pagina-tree-componenten (PageTreeSelect, PageTreeModal) gebruiken progressive rendering: alleen de eerste 50 children van elk node worden initieel gerenderd. Een "Toon meer"-knop laadt extra items in batches van 50. Hierdoor blijft de DOM klein zelfs met duizenden pagina's.

## Resilience

### Feed-widget

Externe feed-bronnen zijn beschermd door drie lagen:

1. **Singleflight-lock** — wanneer de cache vervalt, fetcht alleen de eerste request van de externe bron. Gelijktijdige requests wachten (tot 5 seconden) en lezen daarna uit de net-gevulde cache. Voorkomt thundering herd wanneer veel gebruikers tegelijk dezelfde pagina openen.

2. **Circuit breaker** — na 3 opeenvolgende mislukkingen voor een bron opent de circuit breaker, en volgende requests geven direct een "tijdelijk niet beschikbaar"-melding terug. De circuit reset automatisch na 5 minuten, of direct bij een succesvolle fetch.

3. **Background refresh** — een Nextcloud-background-job (`FeedRefreshJob`) ververst geconfigureerde feed-verbindingen proactief elke 10 minuten, voor de cache verloopt. Gebruikers triggeren bijna nooit een cold fetch.

Aanvullend:

- HTTP-timeout staat op 5 seconden om PHP-worker-blocking te voorkomen
- Private IP-ranges worden geblokkeerd (SSRF-bescherming)
- Image-URLs zijn HMAC-ondertekend voor proxy-requests
- API-responses groter dan 10 MB worden geweigerd vóór parsing om OOM te voorkomen

### People-widget

De People-widget beschermt tegen grote Nextcloud-installaties:

- **Groep-scoped queries**: bij groep-filter worden alleen gebruikers in die groep geladen — het efficiënte pad dat schaalt naar tienduizenden accounts
- **Hard cap**: het ongescoped filter-pad (geen groep-filter) is afgekapt op 5.000 gebruikers om OOM en timeouts te voorkomen
- **Gedistribueerde cache**: filter-resultaten worden 1 uur in Redis/APCu gecached, wat herhaalde volledige user-scans voorkomt
- **Batch-status-fetching**: gebruikers online/away/DND-statussen in één batch-call vooraf opgehaald in plaats van één API-call per gebruiker

### News-widget

De News-widget-folder-scanner heeft een configureerbare collection-limit (standaard `max(limit * 4, 200)`) met early exit. Zodra genoeg items zijn verzameld stopt de recursieve folder-scan direct — ook na terugkeer uit recursieve subfolder-calls.

### Export

De ZIP-export schrijft pagina's incrementeel naar disk in plaats van de hele export in memory te accumuleren. Elke pagina wordt individueel JSON-encoded en naar `export.json` geschreven, waardoor memory-gebruik constant blijft ongeacht het aantal pagina's.

## Beveiliging

### Rate limiting

Alle muterende en gebruikers-gerichte API-endpoints zijn rate-limited:

| Endpoint-categorie | Limiet | Periode |
|---------------------|--------|---------|
| Pagina aanmaken / verwijderen | 10 requests | 60 sec |
| Bulk-operaties (delete, move) | 5 requests | 60 sec |
| Comments | 20 requests | 60 sec |
| Reacties | 30 requests | 60 sec |
| Feed-fetch (geauthenticeerd) | 30 requests | 60 sec |
| Feed-fetch (publieke share) | 30 requests | 60 sec |
| Analytics-tracking | 60 requests | 60 sec |
| Publieke pagina-toegang | 60 requests | 60 sec |

### Content Security Policy

IntraVox zet een strikte CSP op alle pagina-responses:

- `script-src: 'self'` — alleen scripts van het Nextcloud-domein
- `frame-src: 'self' [whitelisted video-domeinen]` — alleen iframes van geconfigureerde video-services
- Geen `unsafe-eval` of `unsafe-inline`

### Audit-logging

Administratieve operaties worden gelogd met de `IntraVox Audit:`-prefix voor SIEM-integratie:

- Bulk delete / move / update (inclusief pagina-ID's en gebruiker)
- Licentie-sleutel-wijzigingen
- Organisatie-instellingen-wijzigingen
- Engagement-instellingen-wijzigingen

Logs worden geschreven naar het Nextcloud-log op `info`-niveau en filterbaar via de `IntraVox Audit:`-prefix.

### AVG-compliance

Wanneer een Nextcloud-gebruiker wordt verwijderd, ruimt IntraVox automatisch op:

- Analytics-view-records (gehashte user-ID)
- Pagina-lock-records
- Feed-tokens en LMS-OAuth-tokens (opgeslagen in Nextcloud-user-preferences)

Dit wordt afgehandeld door de `UserDeletedListener` die naar Nextcloud's `UserDeletedEvent` luistert.

### Health check

Een publiek health-check-endpoint is beschikbaar voor monitoring en orchestration:

```
GET /apps/intravox/api/health
→ {"status": "ok", "app": "intravox", "version": "1.3.0"}
```

## Widget-performance

### Debounced watchers

Alle widget-componenten (News, People, Feed) debouncen hun configuratie-watchers met 300ms vertraging. Voorkomt API-call-bursts wanneer widget-settings snel worden gewijzigd in de editor.

### Uitgestelde initial-fetch

News- en Feed-widgets gebruiken `requestIdleCallback` voor de initial data-fetch, en stellen de network-request uit tot de hoofdthread van de browser idle is. Verbetert waargenomen page-load-performance door layout en rendering te prioriteren.

### Parallel bootstrap

De applicatie laadt pagina's, navigatie, footer en engagement-instellingen parallel bij mount (`Promise.all`). Pagina-content en lock-status worden ook parallel opgehaald, wat een sequentiële waterfall elimineert die voorheen ~100ms aan pagina-navigatie toevoegde.
