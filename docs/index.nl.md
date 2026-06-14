# IntraVox documentatie

Welkom bij de IntraVox-documentatie. IntraVox is een Nextcloud-app die Nextcloud Files transformeert in een SharePoint-achtig intranetplatform — drag-and-drop pagina-editor, widgets, meertalige content, native Nextcloud-deling, versiebeheer en ACL.

![IntraVox demo-intranet-homepage](screenshots/intravox%20home.png)

*Een typische IntraVox-intranet — drag-and-drop widgets, meerkoloms rijen, een tree-vormige navigatie, alles geserveerd vanuit Nextcloud zelf.*

## Snelle navigatie

### Voor gebruikers

Leer hoe je pagina's aanmaakt, widgets gebruikt en met content werkt.

- [Overzicht](user/overview.md) — Wat is IntraVox en hoe past het in je dagelijkse workflow
- [Editor-gids](user/editor.md) — Pagina's maken en bewerken, werken met widgets en content blokken
- [Templates](user/templates.md) — Begin nieuwe pagina's vanuit templates
- [Versiebeheer](user/versioning.md) — Versie-geschiedenis van pagina's, preview, herstellen
- [Engagement](user/engagement.md) — Reacties en comments
- [Publiek delen](user/public-sharing.md) — Pagina's extern delen met link-tokens
- [RSS-feeds](user/rss-feeds.md) — Persoonlijke RSS-feeds met token-authenticatie
- [Toegankelijkheid](user/accessibility.md) — WCAG 2.1 AA-conformiteit en toegankelijkheidsfeatures

### Voor beheerders

Installatie, configuratie, scenario's en operations.

- [Beheergids](admin/guide.md) — Dagelijks beheer
- [Instellingen](admin/settings.md) — Referentie voor het admin-paneel
- [Autorisatie](admin/authorization.md) — Rollen, permissies en GroupFolder-ACL
- [Engagement-instellingen](admin/engagement.md) — Reacties en comments configureren
- [Schaalbaarheid](admin/scalability.md) — Capaciteitsplanning en tuning
- [Beveiliging](admin/security.md) — Beveiligingsmodel en sanitization-lagen
- [Scenario's](admin/scenarios.md) — Praktische recepten (approval-workflows, afdelings-intranets, meer)
- [Showcases](admin/showcases.md) — Voorbeeld-deployments
- [Export & Import](admin/export-import.md) — Content tussen instances verplaatsen, inclusief MetaVox-integratie

### Features

Per-feature documentatie voor widgets en mogelijkheden.

- [Calendar-widget](features/calendar-widget.md) — Embed agenda-items op pagina's
- [Feed-widget](features/feed-widget.md) — Externe feeds (Moodle, Jira, SharePoint, ICS, RSS)
- [File-story-widget](features/file-story-widget.md) — Folder-als-documentstroom rendering, federation-aware
- [News-widget](features/news-widget.md) — Folder-als-feed nieuws rendering
- [People-widget](features/people-widget.md) — Toon mensen uit groepen
- [Photo-story-widget](features/photo-story-widget.md) — Folder-als-foto-verhaal met EXIF, kaarten, timeline en federation
- [Engagement-architectuur](features/engagement-architecture.md) — Hoe reacties en comments technisch werken
- [Confluence-import](features/confluence-import.md) — Import vanuit Confluence Cloud, Server of Data Center

### Voor architecten & ontwikkelaars

Technische documentatie voor integratie, evaluatie en bijdrage.

- [Architectuur-overzicht](architecture/overview.md) — Systeem-design en componenten
- [API-referentie](architecture/api-reference.md) — REST API-endpoints
- [API-development gids](architecture/api-development.md) — Bouwen op de IntraVox-API
- [Template API quickstart](architecture/template-api-quickstart.md) — Template-API in 5 minuten
- [OpenAPI-tooling](architecture/openapi-tooling.md) — OpenAPI-specs genereren en valideren

## Aan de slag

Nieuw bij IntraVox? Start met de [snelstart-gids](getting-started.md) voor een per-rol quickstart.

## Ondersteuning

- **Issues & feature requests**: [GitHub Issues](https://github.com/voxcloud/intravox/issues)
- **Broncode**: [GitHub repository](https://github.com/voxcloud/intravox)

## Licentie

IntraVox is gelicentieerd onder de [AGPL-3.0-licentie](https://www.gnu.org/licenses/agpl-3.0.html).
