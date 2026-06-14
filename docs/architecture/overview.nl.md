# IntraVox-architectuur

IntraVox is een moderne intranet-applicatie voor Nextcloud die SharePoint-achtige content-management-mogelijkheden biedt. Dit document beschrijft de technische architectuur.

## Systeem-overzicht

```
+------------------------------------------------------------------+
|                       Nextcloud-server                            |
|  +--------------------------------------------------------------+ |
|  |                       IntraVox-app                           | |
|  |  +----------------+  +----------------+  +----------------+  | |
|  |  |   Vue.js-SPA   |  |  PHP-backend   |  |  GroupFolder   |  | |
|  |  |   (Frontend)   |->|  (API + logica)|->|  -integratie   |  | |
|  |  +----------------+  +----------------+  +----------------+  | |
|  +--------------------------------------------------------------+ |
|                              |                                    |
|                              v                                    |
|  +--------------------------------------------------------------+ |
|  |                   IntraVox-GroupFolder                       | |
|  |  +------------+  +------------+  +------------------------+  | |
|  |  |  Pagina's  |  | Navigatie  |  | Afbeeldingen & bijlagen|  | |
|  |  |   (JSON)   |  |   (JSON)   |  |       (bestanden)      |  | |
|  |  +------------+  +------------+  +------------------------+  | |
|  +--------------------------------------------------------------+ |
+------------------------------------------------------------------+
```

## Design-principes

### Organisationele communicatie, geen persoonlijke productiviteit

IntraVox is een **organisationeel communicatie-platform** — het publiceert bedrijfsnieuws, team-updates, gedeelde resources en data uit externe systemen naar een breed publiek. Dit is fundamenteel anders dan het **Nextcloud-dashboard**, dat een *persoonlijk productiviteits-overzicht* is dat elke gebruiker zijn eigen mail, taken, bestanden en notificaties toont.

Dit onderscheid strekt zich uit tot externe-systeem-integraties. Veel systemen waarmee IntraVox verbindt — OpenProject, Jira, Canvas LMS, Moodle — hebben ook dedicated Nextcloud-integratie-apps. Deze dienen verschillende doelen:

| | Nextcloud-integratie-apps | IntraVox-Feed-widget |
|---|---|---|
| **Doelgroep** | Individuele gebruiker (mijn taken, mijn bestanden) | Team of afdeling (gedeeld overzicht) |
| **Doel** | Actie ondernemen (bestanden linken, items aanmaken) | Bewustwording creëren (zien wat er speelt) |
| **Context** | Dashboard-widget, zoek, file-sidebar | Embedded in intranet-pagina naast nieuws, agenda, mensen |
| **Zichtbaarheid** | Alleen de geauthenticeerde gebruiker | Iedereen met pagina-toegang, inclusief public shares |
| **Data-scope** | Persoonlijk (security-trimmed per gebruiker) | Organisationeel (gedeeld view of per gebruiker via OAuth/OIDC) |

Deze zijn complementair, geen concurrenten. Een organisatie kan `integration_openproject` gebruiken voor individuele ontwikkelaars om Nextcloud-bestanden aan work packages te koppelen, terwijl ze tegelijkertijd de IntraVox-Feed-widget gebruiken om een project-status-overzicht te tonen op een afdelings-intranet-pagina — zichtbaar voor managers, stakeholders en teamleden die geen OpenProject-account hebben.

Zie de [Feed-widget-documentatie](../features/feed-widget.md#hoe-feed-widgets-nextcloud-integratie-apps-aanvullen) voor gedetailleerde guidance over wanneer welke aanpak te gebruiken.

## Tech-stack

| Laag | Technologie |
|------|-------------|
| Frontend | Vue.js 3, Vue Router, Pinia (state management) |
| Backend | PHP 8.x, Nextcloud-app-framework |
| Opslag | Nextcloud-Files-API, GroupFolders |
| Permissies | GroupFolder-ACL, Nextcloud-permissies |
| Build | Webpack, npm |
| i18n | Nextcloud-L10N, Transifex |

## Bestandsstructuur

```
intravox/
├── appinfo/
│   ├── info.xml           # App-metadata
│   └── routes.php         # API-route-definities
├── css/
│   └── *.css              # Stylesheets
├── img/
│   └── *.svg              # App-icons
├── js/
│   └── *.js               # Gecompileerde JavaScript (build-output)
├── l10n/
│   ├── *.js               # Gecompileerde vertalingen
│   └── *.json             # Vertaling-source-bestanden
├── lib/
│   ├── Controller/        # API-controllers
│   │   ├── ApiController.php
│   │   ├── FooterController.php
│   │   ├── NavigationController.php
│   │   └── PageController.php
│   ├── Service/           # Business-logica
│   │   ├── FooterService.php
│   │   ├── NavigationService.php
│   │   ├── PageService.php
│   │   ├── PermissionService.php
│   │   └── SetupService.php
│   └── AppInfo/
│       └── Application.php
├── src/
│   ├── components/        # Vue-componenten
│   ├── views/             # Pagina-views
│   ├── stores/            # Pinia-stores
│   ├── App.vue            # Root-component
│   └── main.js            # Entry-point
├── templates/
│   └── index.php          # Hoofd-template
└── demo-data/
    ├── nl/                # Nederlandse demo-content
    ├── en/                # Engelse demo-content
    ├── de/                # Duitse demo-content
    └── fr/                # Franse demo-content
```

## Kerncomponenten

### Backend-services

#### PageService

Verwerkt pagina-CRUD-operaties:

- Pagina's laden uit JSON-bestanden
- Pagina-content opslaan
- Pagina-hiërarchie beheren (geneste pagina's)
- Afbeelding- en bijlage-afhandeling

#### NavigationService

Beheert de navigatie-structuur:

- Navigatie-configuratie laden/opslaan
- Ondersteuning voor megamenu- en dropdown-navigatie-types
- Multi-level navigatie-items

#### FooterService

Verwerkt footer-content per taal.

#### PermissionService

Integreert met GroupFolder-permissies:

- Permissies lezen uit GroupFolder-ACL
- Gebruikers-toegang tot specifieke paden checken
- Content filteren op basis van permissies

#### SetupService

Verwerkt initiële app-setup:

- Maakt IntraVox-GroupFolder aan indien niet aanwezig
- Initialiseert map-structuur

### Frontend-componenten

#### App.vue

Hoofd-applicatie-shell met:

- Navigatie-component
- Router-view voor pagina-content
- Footer-component

#### Navigation.vue

Rendert het navigatie-menu:

- Megamenu-ondersteuning
- Dropdown-menu's
- Taal-switcher
- Mobile-responsive

#### PageView.vue

Toont pagina-content:

- Rendert widgets (tekst, afbeeldingen, links, enz.)
- Bewerk-modus met drag-and-drop
- Rij- en kolom-layouts

#### PageEditor.vue

WYSIWYG-pagina-editor:

- Widget-palet
- Drag-and-drop widget-plaatsing
- Real-time preview
- Save-/cancel-functionaliteit

## Datamodel

### Pagina-structuur (JSON)

```json
{
  "uniqueId": "page-uuid-here",
  "title": "Page Title",
  "language": "en",
  "layout": {
    "columns": 1,
    "rows": [
      {
        "columns": 2,
        "backgroundColor": "",
        "widgets": [
          {
            "type": "heading",
            "column": 1,
            "order": 1,
            "content": "Welcome",
            "level": 1
          },
          {
            "type": "text",
            "column": 1,
            "order": 2,
            "content": "Page content here..."
          }
        ]
      }
    ],
    "sideColumns": {
      "left": { "enabled": false, "widgets": [] },
      "right": { "enabled": false, "widgets": [] }
    }
  }
}
```

### Widget-types

| Type | Beschrijving |
|------|--------------|
| heading | H1-H6-headings |
| text | Rich-text-content (Markdown) |
| image | Afbeelding met alt-tekst, optionele klikbare link (interne pagina of externe URL) |
| video | Embedded video van YouTube, Vimeo, PeerTube of lokale upload |
| links | Link-collectie (cards of lijst) |
| divider | Horizontale scheider |

### Navigatie-structuur (JSON)

```json
{
  "type": "megamenu",
  "items": [
    {
      "id": "nav_home",
      "title": "Home",
      "uniqueId": "page-uuid",
      "url": null,
      "target": null,
      "children": []
    }
  ]
}
```

## API-endpoints

### Pagina's

- `GET /api/page/{uniqueId}` — Pagina ophalen op ID
- `PUT /api/page/{uniqueId}` — Pagina bijwerken
- `POST /api/page` — Nieuwe pagina aanmaken
- `DELETE /api/page/{uniqueId}` — Pagina verwijderen
- `GET /api/pages/{language}` — Pagina's voor taal opsommen

### Navigatie

- `GET /api/navigation/{language}` — Navigatie ophalen
- `PUT /api/navigation/{language}` — Navigatie bijwerken

### Footer

- `GET /api/footer/{language}` — Footer ophalen
- `PUT /api/footer/{language}` — Footer bijwerken

### Setup

- `GET /api/setup` — Setup-status checken
- `POST /api/setup` — Setup uitvoeren

## Bestandsopslag

Alle content is opgeslagen in de IntraVox-GroupFolder:

```
IntraVox/
├── {language}/
│   ├── navigation.json
│   ├── footer.json
│   ├── home.json
│   ├── _media/
│   │   └── *.jpg, *.png, *.mp4, ...
│   └── {section}/
│       ├── {page}.json
│       ├── _media/
│       └── {subpage}/
│           └── ...
```

De `_media/`-map slaat afbeeldingen, video's en andere media-bestanden op. Lokale video-uploads staan hier naast afbeeldingen.

### Voordelen van bestand-gebaseerde opslag

- Versiebeheer via Nextcloud-versioning
- Eenvoudige back-up en migratie
- Geen database-tabellen nodig
- Content bewerkbaar via Nextcloud-Files-app
- Integreert met Nextcloud-sharing

## Meertalige ondersteuning

IntraVox ondersteunt meerdere talen:

- Elke taal heeft zijn eigen map-structuur
- Navigatie en footer zijn per taal
- Pagina's kunnen linken naar vertalingen via uniqueId
- UI-vertalingen via Nextcloud-L10N-systeem

## Permissie-model

Zie [AUTHORIZATION.md](../admin/authorization.md) voor gedetailleerde permissie-documentatie.

Kernpunten:

- Gebruikt GroupFolder-permissies
- Ondersteunt ACL voor fijnmazige controle
- Permissies worden gecheckt op API- en UI-niveau
- Navigatie wordt gefilterd op basis van toegang

## Schaalbaarheid & performance

IntraVox gebruikt een multi-layer caching-strategie en resilience-patterns om grote deployments te ondersteunen (tot 10k gebruikers, 5k pagina's):

- **Distributed caching** (Redis/APCu) voor pagina-bomen, feed-resultaten en people-filter-resultaten
- **Singleflight-locks** om thundering herd op externe feed-bronnen te voorkomen
- **Circuit breaker** om falende externe bronnen elegant af te handelen
- **Achtergrond-feed-refresh** via Nextcloud-cron om caches warm te houden
- **Pagina-metadata-database-index** voor snelle listing-, tree- en search-operaties
- **Bundle splitting** (217 KB main + lazy-loaded editor en widgets)
- **Progressive rendering** in tree-componenten (50 items per batch)

Rate limiting, audit logging, AVG-gebruiker-verwijdering en een health-check-endpoint bieden enterprise-grade operationele veiligheid.

Zie [Schaalbaarheid & Enterprise-readiness](../admin/scalability.md) voor volledige details.

## Build-proces

```bash
# Dependencies installeren
npm install

# Development-build met watch
npm run dev

# Production-build
npm run build

# Release-tarball maken
./create-release.sh
```

## Deployment

Zie README.md voor installatie-instructies. Kernstappen:

1. Installeer via Nextcloud-App-Store of handmatig
2. Schakel de app in
3. GroupFolder "IntraVox" wordt automatisch aangemaakt
4. Voeg groepen toe aan de GroupFolder
5. Importeer demo-data of begin met pagina's maken

## Integratie-punten

### MetaVox-integratie

IntraVox kan integreren met MetaVox voor verbeterde navigatie:

- Respecteert MetaVox-afdelings-structuur
- Gebruikt MetaVox-nav-kleur-instellingen
- Synct navigatie-stijlen

### GroupFolders

- Automatische GroupFolder-aanmaak
- ACL-permissie-lezen
- Bestandsopslag binnen GroupFolder

### Nextcloud-features

- Gebruikt Nextcloud-theming (kleuren, logo)
- Integreert met Nextcloud-Files
- Ondersteunt Nextcloud-versioning
- Gebruikt Nextcloud-authenticatie
