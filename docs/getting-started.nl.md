# Aan de slag met IntraVox

IntraVox is een Nextcloud-app die een SharePoint-achtig intranetplatform biedt bovenop Nextcloud Files: drag-and-drop pagina's, widgets, meertalige content, en lezer-engagement (reacties, comments) — alles opgeslagen als mappen en bestanden binnen Nextcloud, met overerving van sharing, ACL, versiebeheer en audit.

Deze gids helpt je snel op weg op basis van je rol.

## Wat is IntraVox?

IntraVox publiceert organisatie-communicatie — bedrijfsnieuws, HR-beleid, team-portalen, officiële documentatie — naar een breed intern publiek. Pagina's bouw je met widgets (tekst, nieuws, agenda, mensen, feeds, foto-verhalen) via een drag-and-drop editor. Lezers reageren met reacties en comments.

Anders dan Nextcloud Collectives (voor horizontale team-samenwerking op wiki-achtige content), is IntraVox een **top-down communicatieplatform** — communicatieteams publiceren, de organisatie leest.

## Snelstart per rol

### Gebruikers

1. Open IntraVox via het Nextcloud app-menu
2. Blader door pagina's via de zijbalk-navigatie
3. Om een pagina te bewerken (indien schrijfrechten), klik op de bewerk-knop en gebruik de [drag-and-drop editor](user/editor.md)
4. Voeg reacties of comments toe via het engagement-paneel

Zie [Gebruikersoverzicht](user/overview.md) en [Editor-gids](user/editor.md) voor uitgebreide instructies.

### Beheerders

1. Installeer IntraVox vanuit de Nextcloud App Store (of via `occ app:install intravox`)
2. Configureer de GroupFolder die IntraVox-content zal bevatten (één per taal aanbevolen)
3. Open **Instellingen → Beheer → IntraVox** voor permissies en engagement-defaults
4. Stel [autorisatie](admin/authorization.md) in via GroupFolder-ACL
5. Optioneel: importeer bestaande content uit [Confluence](features/confluence-import.md)

Zie [Beheergids](admin/guide.md) en [Scenario's](admin/scenarios.md) voor common deployment patterns.

### Architecten

Voor je IntraVox op schaal evalueert, lees:

- [Architectuur-overzicht](architecture/overview.md) — systeem-design, componenten, technology stack
- [Schaalbaarheid](admin/scalability.md) — capaciteitsplanning, caching, tuning
- [Beveiliging](admin/security.md) — beveiligingsmodel en sanitization-lagen

## Kernconcepten

| Concept | Beschrijving |
|---|---|
| **Pagina** | Een map in een Nextcloud-GroupFolder met een JSON content-bestand en een optionele `_media/` submap. Sub-pagina's zijn sub-mappen. |
| **Widget** | Het bouwblok van een pagina — tekst, afbeelding, video, nieuws, mensen, agenda, feed, foto-verhaal, enz. |
| **Layout** | Pagina's bestaan uit rijen; elke rij bevat 1–5 kolom-uitgelijnde widgets. |
| **GroupFolder** | De Nextcloud-storage en ACL-container die IntraVox-content bevat. Permissies worden overgeërfd van GroupFolder-ACL. |
| **Taalmap** | Top-level mappen (`nl/`, `en/`, `de/`, `fr/`) die content per taal scheiden. |
| **Engagement** | Reacties en comments aan pagina's gekoppeld via Nextcloud's native Comments-API. |
| **Template** | Een pagina die als startpunt dient voor nieuwe pagina's, geïnstantieerd via de [Template-API](architecture/template-api-quickstart.md). |

## Architecturele highlights

- **Pagina's zijn mappen** in het Nextcloud-bestandssysteem — ze erven ACL, sharing, versiebeheer, audit, WebDAV-toegang, encryption, external storage, prullenbak en quota van Nextcloud.
- **Native NC-integratie** — gebruikt NC's Comments, Activity, Search, Versions, Notifications, GroupFolder-ACL en event-systeem in plaats van die te herimplementeren.
- **Optionele MetaVox-integratie** voor gestructureerde, getypte metadata op pagina's die door alle content-typen in Nextcloud stroomt (documenten, foto's, formulieren). Zie [Export & Import](admin/export-import.md).

## Volgende stappen

- [Gebruikersoverzicht](user/overview.md) — Dagelijks werken met IntraVox
- [Beheergids](admin/guide.md) — Setup en configuratie
- [API-referentie](architecture/api-reference.md) — Programmatische toegang
- [Scenario's](admin/scenarios.md) — Praktische deployment-recepten

## Zie ook

- [Architectuur-overzicht](architecture/overview.md) — Systeem-design en internals
- [Autorisatie-gids](admin/authorization.md) — Permissies en rollen
- [Schaalbaarheid](admin/scalability.md) — Performance op schaal
