# IntraVox-API-referentie

> **Let op:** de complete API-referentie is uitvoerig technisch en wordt in zijn geheel onderhouden in het Engels in de IntraVox-repository. Voor de actuele endpoint-specificatie, request-/response-schema's en migratie-voorbeelden, raadpleeg de [Engelse API-referentie](api-reference.en.md).

Op deze pagina vind je een Nederlandstalige inleiding tot de IntraVox-REST-API en pointers naar de relevante secties van de volledige Engelstalige referentie.

## Inleiding

IntraVox biedt een uitgebreide REST-API voor het beheren van pagina's, media, navigatie, comments, reacties, MetaVox-metadata, search, en meer. De API is bedoeld voor:

- **CMS-migraties** — content van SharePoint, Confluence of vergelijkbare systemen importeren
- **Custom integraties** — IntraVox koppelen aan externe systemen (HR, ticketing, LMS)
- **Bulk-operaties** — programmatisch grote aantallen pagina's beheren
- **Frontend-extensies** — eigen UI's bovenop de IntraVox-backend bouwen
- **Backup/restore** — geautomatiseerde content-exports en -imports

## Authenticatie

Alle endpoints vereisen HTTP-Basic-authenticatie met een Nextcloud-app-wachtwoord:

```bash
curl -u "username:app-password-token" \
  https://your-nextcloud.com/apps/intravox/api/pages
```

Maak een app-wachtwoord aan via **Nextcloud-instellingen → Beveiliging → Apparaten & sessies**.

## Base-URLs

Twee API-stijlen worden ondersteund:

| Stijl | Base-URL | Doel |
|-------|----------|------|
| **Interne API** | `/apps/intravox/api/...` | Frontend, sessie-gebaseerd |
| **OCS-API** | `/ocs/v2.php/apps/intravox/api/v1/...` | Externe systemen, app-password |

Beide leveren grotendeels dezelfde functionaliteit; de OCS-variant volgt de Nextcloud-OCS-conventies (statuscodes, headers, fout-formaat).

## Endpoint-categorieën

De Engelstalige referentie behandelt de volgende categorieën:

| Categorie | Doel |
|-----------|------|
| **Pages-API** | CRUD voor pagina's, layout, widgets |
| **Page-layout & widgets** | Widget-types, JSON-schema's, voorbeelden |
| **Media-API** | Upload, download en verwijderen van afbeeldingen, video, bestanden |
| **Versioning-API** | Pagina-versies tonen, preview, restore |
| **Comments-API** | Comments aanmaken, bewerken, verwijderen |
| **Reactions-API** | Emoji-reacties op pagina's en comments |
| **Analytics-API** | Pageviews, top-pagina's, engagement-metrics |
| **Bulk-operations-API** | Meerdere pagina's tegelijk verplaatsen, verwijderen, kopiëren |
| **Navigation & Footer-API** | Navigatie- en footer-structuur beheren |
| **Settings-API** | Admin- en gebruikers-instellingen |
| **Page-metadata-API** | Snelle lijst-/zoek-endpoints op pagina-metadata |
| **News-API** | News-widget-data, MetaVox-filtering, publicatie-datums |
| **Resources-API** | Externe-feed-connections (Jira, Moodle, SharePoint, enz.) |
| **Permissions-API** | Permissie-checks, GroupFolder-ACL-info |
| **MetaVox-integratie-API** | Metadata-velden ophalen en filteren |
| **Setup & demo-data-API** | App-setup-status, demo-content importeren |
| **Calendar-API** | Calendar-widget-events |
| **Search-API** | Pagina-zoeken |
| **Export/Import-API** | Volledige IntraVox-content-export en -import |
| **Error-codes** | Standaard-error-formaat en statuscodes |
| **Security** | CSRF, rate-limiting, sanitization |
| **Migration-tool-integration** | Aanbevolen flow voor SharePoint-/Confluence-migraties |

## Snelstart

### Pagina ophalen

```bash
curl -u "username:app-password" \
  https://your-nextcloud.com/ocs/v2.php/apps/intravox/api/v1/pages/page-abc-123 \
  -H "OCS-APIRequest: true"
```

### Pagina-content updaten

```bash
curl -X PUT \
  -u "username:app-password" \
  -H "Content-Type: application/json" \
  -H "OCS-APIRequest: true" \
  -d '{"title":"Nieuwe titel","layout":{...}}' \
  https://your-nextcloud.com/ocs/v2.php/apps/intravox/api/v1/pages/page-abc-123
```

### Media uploaden (via WebDAV)

```bash
# Upload bestand naar pagina-media-map via WebDAV
curl -u "username:app-password" \
  -T banner.png \
  https://your-nextcloud.com/remote.php/dav/files/username/IntraVox/nl/welcome/_media/banner.png
```

## Voor de complete referentie

Zie de [Engelse API-referentie](api-reference.en.md) voor:

- Volledige request-/response-schema's per endpoint
- HTTP-statuscodes en error-formaat
- Code-voorbeelden in cURL, JavaScript, Python en PHP
- Bulk-operatie-patterns en best-practices
- Rate-limiting-details en CSRF-instructies
- WebDAV-chunked-upload-flow voor grote bestanden
- Migratie-voorbeelden (SharePoint → IntraVox)

## Gerelateerd

- [Template-API-quickstart](template-api-quickstart.md) — pagina's maken vanuit templates (in 5 minuten)
- [OpenAPI-tooling](openapi-tooling.md) — Swagger UI, Postman, code-generatie
- [API-development-gids](api-development.md) — eigen endpoints toevoegen
- [Autorisatie](../admin/authorization.md) — GroupFolder-ACL en permissie-model
- [Beveiliging](../admin/security.md) — CSRF, sanitization, audit-logging
