# Feed-widget

De Feed-widget toont content uit externe bronnen op je intranet-pagina's. Ondersteunt RSS/Atom-feeds en door beheerders geconfigureerde verbindingen met elke REST-API — inclusief LMS-platformen (Canvas, Moodle, Brightspace), projectmanagement (Jira, OpenProject), kennisbanken (Confluence), HR-systemen (AFAS), service desks (TOPdesk), en meer.

![Feed-widgets vanuit meerdere bronnen op een IntraVox-pagina](../../screenshots/feed-examples.png)

![Feed-widgets met Confluence, OpenProject, Canvas, Moodle, SharePoint, Jira, Brightspace](../../screenshots/feed-examples-2.png)

## Features

- **Twee bron-types** — RSS/Atom-feed (plak een URL) of Connection (door beheerder geconfigureerd)
- **Presets voor populaire systemen** — Jira, Confluence, SharePoint, OpenProject, Canvas, Moodle, Brightspace, AFAS, TOPdesk, of volledig custom
- **LMS-content-types** — Nieuws/aankondigingen (met forum-selector), Beschikbare cursussen, Opdrachten en Aankomende deadlines
- **Jira-content-types** — Alle issues, Open, Recent bijgewerkt, Recent aangemaakt, Bugs — met projectselector
- **OpenProject-content-types** — Alle work packages, Open, Overdue, Milestones, of Recent bijgewerkt
- **Twee layouts** — Lijst of grid (2-4 kolommen)
- **Gepersonaliseerde content** — gebruikers koppelen hun eigen LMS-account om alleen hun cursussen, deadlines en aankondigingen te zien
- **OAuth2-integratie** — one-click account-koppeling met Canvas, Moodle en Brightspace via popup-flow
- **Handmatige token-fallback** — gebruikers plakken een API-token voor Moodle of Brightspace zonder OAuth2-setup
- **OIDC auto-connect** — zero-click personalisatie wanneer Nextcloud en het LMS dezelfde identity provider delen
- **Configureerbare weergave** — toon/verberg afbeeldingen, datums, samenvattingen, bron-naam en auteur
- **Live preview** — zie hoe de feed eruit ziet tijdens configuratie, met echte data
- **Automatische taal-onderhandeling** — externe API's ontvangen de taalvoorkeur van de gebruiker via de `Accept-Language`-HTTP-header
- **15-minuten server-side cache** — vermindert API-calls naar externe systemen
- **Public-share-ondersteuning** — Feed-widgets werken op publiek gedeelde IntraVox-pagina's

## Bron-types

### RSS / Atom-feed

Het simpelste bron-type. Voer een feed-URL in en de widget haalt de items op.

**Hoe te gebruiken:**

1. Voeg een Feed-widget toe aan je pagina
2. Selecteer **RSS / Atom-feed** als bron-type
3. Voer de feed-URL in (bv. `https://example.com/feed.xml`)
4. Klik op **Preview** om te verifiëren dat de feed werkt
5. Klik op **Opslaan**

De widget detecteert automatisch RSS-2.0- en Atom-feed-formaten. Afbeeldingen worden geëxtraheerd uit feed-enclosures, `media:content`, `media:thumbnail` of inline `<img>`-tags.

![RSS-feeds vanuit meerdere bronnen in lijst- en grid-layout](../../screenshots/feed-example-rss.png)

### Canvas LMS

Toont gepersonaliseerde content vanuit een Canvas-LMS-instantie. Ondersteunt zowel gedeelde (admin-token) als gepersonaliseerde (per-gebruiker OAuth2) toegang.

**Content-types:**

| Content-type | Wat het toont | API gebruikt |
|--------------|---------------|--------------|
| **Nieuws / aankondigingen** (default) | Cursus-aankondigingen met titel, bericht, auteur en datum. Optioneel gefilterd per cursus. | `/api/v1/announcements` |
| **Mijn cursussen** | De cursussen waarvoor de gebruiker is ingeschreven met code en link | `/api/v1/courses` |
| **Aankomende deadlines** | Opdracht-deadlines voor de komende 30 dagen over alle cursussen | `/api/v1/calendar_events` |

**Personalisatie:** wanneer de verbinding OAuth2 gebruikt (`authMode: oauth2` of `both`), koppelt elke gebruiker zijn eigen Canvas-account. De widget toont dan alleen content uit cursussen waarvoor de gebruiker is ingeschreven.

![Canvas-OAuth2-connection-flow — Connect your account → Authorize → Connected](../../screenshots/feed-canvas-connection.png)

**Setup-vereisten:**

- Beheerder configureert een Canvas-connection in IntraVox-beheerinstellingen (zie [Beheerinstellingen-gids](../admin/settings.md))
- Voor OAuth2: een Developer Key moet aangemaakt zijn in Canvas Admin

### Moodle

Toont content vanuit een Moodle-instantie. Ondersteunt zowel gedeelde (admin-token) als gepersonaliseerde (per-gebruiker OAuth2/handmatig token) toegang.

![Moodle-connection-configuratie in IntraVox-beheerinstellingen](../../screenshots/feed-connections-moodle.png)

**Content-types:**

| Content-type | Wat het toont | API gebruikt |
|--------------|---------------|--------------|
| **Nieuws / aankondigingen** (default) | Forum-discussies. Optioneel gefilterd per cursus en forum. | `mod_forum_get_forum_discussions` / `core_course_get_courses` |
| **Beschikbare cursussen** | Cursus-catalogus — alle beschikbare cursussen (admin-token) of ingeschreven cursussen (per-gebruiker-token) | `core_enrol_get_users_courses` / `core_course_get_courses` |
| **Opdrachten** | Opdrachten-overzicht per cursus met deadlines | `mod_assign_get_assignments` |
| **Aankomende deadlines** | Aankomende calendar-events over alle cursussen | `core_calendar_get_calendar_upcoming_view` |

![Drie Moodle-feed-widgets op één pagina: alle cursussen, opdrachten en aankomende deadlines](../../screenshots/feed-moodle.png)

**Forum-selector:** wanneer "Nieuws / aankondigingen" is geselecteerd en een cursus is gekozen, verschijnt een forum-selector. Hiermee kunnen beheerders een specifiek forum kiezen (bv. "Aankondigingen" of "Algemene discussie") in plaats van alle forums in de cursus.

**Personalisatie-opties:**

1. **OAuth2** — vereist de [local_oauth2-plugin](https://moodle.org/plugins/local_oauth2) geïnstalleerd in Moodle
2. **Handmatig token** — gebruikers plakken hun persoonlijke web-service-token (gegenereerd in Moodle onder Voorkeuren > Beveiligings-sleutels)

**Setup-vereisten:**

- Beheerder configureert een Moodle-connection in IntraVox-beheerinstellingen
- Moodle-web-services moeten ingeschakeld zijn met het REST-protocol geactiveerd
- Voor handmatige tokens: de web-service moet de functies `core_course_get_courses`, `core_enrol_get_users_courses`, `core_calendar_get_calendar_upcoming_view`, `core_webservice_get_site_info`, `mod_forum_get_forums_by_courses`, `mod_forum_get_forum_discussions` en `mod_assign_get_assignments` blootstellen

**Let op:** "Beschikbare cursussen" vervangt het eerdere label "Mijn cursussen" om te reflecteren dat dit organisationele content is (cursus-catalogus), geen persoonlijke data. Bestaande widgets met de oude `my-courses`-waarde blijven werken.

### Brightspace (D2L)

Toont gepersonaliseerde content vanuit een Brightspace-(Desire2Learn-)instantie.

**Content-types:**

| Content-type | Wat het toont | API gebruikt |
|--------------|---------------|--------------|
| **Nieuws / aankondigingen** (default) | Nieuws-items uit een specifieke org-unit, of organisatie-niveau-nieuws | `/d2l/api/le/1.67/{orgUnitId}/news/` |
| **Mijn cursussen** | De cursussen waarvoor de gebruiker actief is ingeschreven | `/d2l/api/lp/1.43/enrollments/myenrollments/` |
| **Aankomende deadlines** | Calendar-events voor de komende 30 dagen | `/d2l/api/le/1.67/calendar/events/myEvents/` |

**Personalisatie-opties:**

1. **OAuth2** — registreer een OAuth2-app in Brightspace Admin onder Manage Extensibility
2. **Handmatig token** — gebruikers plakken een persoonlijke bearer-token uit Brightspace-account-instellingen

**Setup-vereisten:**

- Beheerder configureert een Brightspace-connection in IntraVox-beheerinstellingen
- Voor OAuth2: registreer een OAuth2-applicatie in Brightspace met de redirect-URI getoond in IntraVox

### OpenProject

Toont work packages vanuit een OpenProject-instantie. Gebruikt de OpenProject-API v3 met Basic-authenticatie (`apikey:<token>`).

**Content-types:**

| Content-type | Wat het toont | API-filter |
|--------------|---------------|------------|
| **Alle work packages** (default) | Alle work packages, gesorteerd op laatst bijgewerkt | Geen |
| **Open work packages** | Alleen work packages met open status | Status-operator `o` |
| **Overdue** | Open work packages voorbij hun due date | Due date vóór vandaag + open status |
| **Milestones** | Milestone-type work packages (releases, deadlines) | Type = Milestone |
| **Recent bijgewerkt** | Work packages bijgewerkt in de laatste 7 dagen | Updated in last 7 days |

**Setup:**

1. Ga in OpenProject naar **My Account** → **Access tokens** → **+ API token**

![Een API-token aanmaken in OpenProject](../../screenshots/feed-openproject-accesstoken.png)

2. Kopieer het token (slechts één keer getoond)
3. Voeg in IntraVox-beheerinstellingen een connection toe van type **OpenProject**
4. Voer de Base URL in (bv. `https://openproject.example.com`)
5. Endpoint, auth-methode (Basic) en response-mapping worden vooringevuld door de preset
6. Voer het API-token in als `apikey:<jouw-token>` (bv. `apikey:abc123def456...`)
7. Sla de connection op

**Links:** work-package-links in de feed openen direct in de OpenProject-web-interface (bv. `https://openproject.example.com/work_packages/42`), niet de API.

**Let op:** het Milestone-content-type filtert op het work-package-type met ID `2` (het default "Milestone"-type in OpenProject). Als jouw instantie een ander type-ID voor milestones gebruikt, gebruik dan het REST-API (custom)-type en configureer het filter handmatig.

### Jira

Toont issues vanuit een Jira-instantie. Ondersteunt zowel Jira Data Center (on-premises) als Jira Cloud (Atlassian Cloud).

![Jira-feed-widget met projectselector en content-type-filter](../../screenshots/feed-jira-selection.png)

**Content-types:**

| Content-type | Wat het toont | JQL-filter |
|--------------|---------------|------------|
| **Alle issues** (default) | Alle issues, gesorteerd op laatst bijgewerkt | `updated >= -30d ORDER BY updated DESC` |
| **Open issues** | Issues die niet done zijn | `status != Done` |
| **Recent bijgewerkt (7 dagen)** | Issues bijgewerkt in de afgelopen week | `updated >= -7d` |
| **Recent aangemaakt (7 dagen)** | Issues aangemaakt in de afgelopen week | `created >= -7d` |
| **Bugs** | Alleen bug-type issues | `type = Bug` |

**Projectselector:** een dropdown laat je filteren op een specifiek Jira-project. De beschikbare projecten worden automatisch opgehaald uit de Jira-API.

**Cloud vs. Data Center:**

| | Jira Data Center | Jira Cloud |
|---|---|---|
| **Auth-methode** | Bearer-token (PAT) | Basic auth (email:api-token) |
| **API-versie** | v2 (`/rest/api/2/search`) | v3 (`/rest/api/3/search/jql`) |
| **Auto-detect** | Ja, vanuit Base URL | Ja, `.atlassian.net`-URLs |

De preset detecteert automatisch Cloud vs. Data Center op basis van de Base URL en past API-versie en auth-methode aan. Voor Jira Cloud verschijnt automatisch een veld **Atlassian-account-e-mail** voor het Basic-auth-formaat `email:api-token`.

**Setup:**

1. **Jira Data Center:** maak een Personal Access Token in Jira (Profile → Personal Access Tokens)
2. **Jira Cloud:** maak een API-token via [id.atlassian.com/manage-profile/security/api-tokens](https://id.atlassian.com/manage-profile/security/api-tokens)
3. Voeg in IntraVox-beheerinstellingen een connection toe van type **Jira**
4. Voer de Base URL in (bv. `https://jira.example.com` of `https://your-org.atlassian.net`)
5. Auth-methode, endpoint en response-mapping worden automatisch geconfigureerd door de preset
6. Voer je token in (Cloud: vul ook je Atlassian-e-mail in)
7. Sla de connection op

**Links:** issue-links openen direct in de Jira-web-interface (bv. `https://jira.example.com/browse/PROJ-123`), niet de API-URL.

![Klikken op een Jira-issue in IntraVox opent het in Jira](../../screenshots/feed-jira-online.png)

### Confluence

Toont recent gewijzigde pagina's vanuit een Confluence-instantie. Gebruikt Bearer-token-authenticatie met de Confluence-REST-API.

De preset configureert het endpoint (`/rest/api/content`) en de response-mapping automatisch. Pagina's worden gesorteerd op laatst gewijzigde datum.

### SharePoint (Microsoft Graph)

Toont pagina's, news-posts, documenten of list-items vanuit een SharePoint-site via de Microsoft-Graph-API. Gebruikt OAuth2-client-credentials (app-only) authenticatie.

**Content-types:**

| Content-type | Wat het toont |
|--------------|---------------|
| **Alle pagina's** (default) | Alle site-pagina's gesorteerd op laatst gewijzigd |
| **News-posts** | Alleen SharePoint-news-posts |
| **Documenten** | Bestanden uit een specifieke document-library |
| **List-items** | Items uit een specifieke SharePoint-list |

**Setup — Microsoft-Entra-app-registratie:**

1. Ga naar [Microsoft Entra-admin-center](https://entra.microsoft.com) → **App registrations** → **+ New registration**

![Een applicatie registreren in Microsoft Entra](../../screenshots/feed-appregistration-entra.png)

2. Voeg **API-permissions** toe: `Sites.Read.All` (Application) en `User.Read` (Delegated), en grant admin consent

![API-permissions voor IntraVox-SharePoint-integratie](../../screenshots/feed-appregistration-entra-api-permissions.png)

3. Maak een **Client secret** onder Certificates & secrets

![Een client-secret aanmaken in Microsoft Entra](../../screenshots/feed-appregistration-entra-api-secret.png)

4. Kopieer de **Application (client) ID** en **Directory (tenant) ID** van de Overview-pagina

![Application- en tenant-IDs in Microsoft Entra](../../screenshots/feed-appregistration-entra-aplication-tenantid.png)

5. Voeg in IntraVox-beheerinstellingen een connection toe van type **SharePoint (Graph API)** en vul de Client ID, Client Secret, Tenant ID en je SharePoint-site-URL in

### Hoe Feed-widgets Nextcloud-integratie-apps aanvullen

Veel externe systemen waarmee IntraVox verbindt — OpenProject, Jira, Canvas LMS, Moodle — hebben ook dedicated Nextcloud-integratie-apps (bv. `integration_openproject`, `integration_jira`). Deze zijn **complementair, geen concurrenten**. Elk dient een andere doelgroep en doel:

| | Nextcloud-integratie-app | IntraVox-Feed-widget |
|---|---|---|
| **Doelgroep** | Individuele gebruiker | Team, afdeling of organisatie |
| **Doel** | Actie ondernemen (bestanden linken, items aanmaken, taken beheren) | Bewustwording creëren (in één oogopslag zien wat er speelt) |
| **Context** | Dashboard-widget, unified search, file-sidebar | Embedded in intranet-pagina naast nieuws, agenda, mensen |
| **Zichtbaarheid** | Alleen de geauthenticeerde gebruiker | Iedereen met pagina-toegang, inclusief public shares |
| **Layout** | Vast widget-formaat | Configureerbare lijst of grid, 1-20 items |

**Voorbeeld: OpenProject.** Met de `integration_openproject`-app kunnen individuele ontwikkelaars Nextcloud-bestanden koppelen aan work packages, zoeken naar taken en persoonlijke notificaties ontvangen. De IntraVox-Feed-widget toont een project-status-overzicht op een afdelings-intranet-pagina — zichtbaar voor managers, stakeholders en teamleden die mogelijk geen OpenProject-account hebben. De één is voor *werken in* het systeem, de ander voor *communiceren over* het werk.

Hetzelfde principe geldt voor alle ondersteunde systemen. Een universiteit kan de Moodle-integratie-app gebruiken voor studenten om hun persoonlijke cursussen te bekijken, terwijl ze de IntraVox-Feed-widget gebruiken om aankomende deadlines te tonen op een faculteits-intranet-pagina voor alle medewerkers.

Zie de [architectuur-documentatie](../architecture/overview.md#organizational-communication-not-personal-productivity) voor het design-principe achter deze aanpak.

#### Waarom er geen "Nextcloud"-bron-type is

Het Nextcloud-dashboard is een *persoonlijk productiviteits-overzicht* — het toont mijn mail, mijn taken, mijn recente bestanden, mijn Talk-mentions, mijn notificaties. IntraVox is een *organisationeel communicatie-platform* — het toont bedrijfsnieuws, team-updates, gedeelde resources en data uit externe systemen.

IntraVox dupliceert bewust niet wat het Dashboard al biedt:

| Persoonlijke data | Waar het hoort | Waarom niet in IntraVox |
|-------------------|----------------|--------------------------|
| Mijn recente bestanden | Dashboard (Recommendations-widget) | Persoonlijke productiviteit, algoritmisch per gebruiker |
| Mijn Talk-mentions | Dashboard (Talk-widget) | Persoonlijke messaging |
| Mijn Deck-kaarten | Dashboard (Deck-widget) | Persoonlijk task-management |
| Mijn mail | Dashboard (Mail-widget) | Persoonlijke e-mail |
| Mijn notificaties | Dashboard (Notifications) | Persoonlijke alerts |
| Mijn activity-stream | Dashboard (Activity-widget) | Persoonlijk event-log |

**Voor organisationele Nextcloud-data** (bv. shared folder-activity vanuit een remote Nextcloud-instantie) gebruik je het **REST-API (custom)**-bron-type met de Nextcloud-OCS-API-endpoints. Dit geeft je volledige controle over welke data te tonen en hoe te authenticeren (Bearer-token, OAuth2 of custom headers met `OCS-APIRequest: true`).

### REST-API (custom)

Verbind met elk systeem met een REST/JSON-API — Jira, Confluence, OpenProject, ZGW-APIs of elk ander REST-endpoint.

![Custom-REST-API-connection-configuratie met response-mapping en headers](../../screenshots/feed-connections-custom.png)

**Hoe te gebruiken:**

1. Ga naar IntraVox-**beheerinstellingen** → Feed-verbindingen
2. Voeg een connection toe van type **REST-API (custom)**
3. Configureer:
   - **Base URL** — de API-root (bv. `https://jira.example.com`)
   - **Endpoint-pad** — het API-pad (bv. `/rest/api/2/search?jql=project=KEY`)
   - **Auth-methode** — Bearer-token, API-key (custom header), Basic auth, of geen authenticatie
   - **Response-mapping** — map JSON-velden naar feed-items (titel, URL, samenvatting, datum, afbeelding, auteur)
4. Sla de connection op
5. Voeg een Feed-widget toe en selecteer je REST-API-connection

**Response-mapping:**

De beheerder mapt JSON-velden uit de API-response naar feed-item-properties met dot-notatie-paden:

| Feed-veld | JSON-pad-voorbeeld | Beschrijving |
|-----------|--------------------|--------------|
| Items-pad | `results` of `issues` of `data.items` | Waar de array van items zich bevindt in de JSON-response. Laat leeg als de response zelf een array is |
| Titel | `title` of `fields.summary` | Item-titel (verplicht) |
| URL | `url` of `_links.webui` | Klikbare link. Relatieve URLs worden absoluut gemaakt via de base-URL |
| Samenvatting | `body` of `fields.description` | Tekst-samenvatting (HTML wordt verwijderd, max 300 tekens) |
| Datum | `created_at` of `history.lastUpdated.when` | Publicatie-/update-datum |
| Afbeelding | `thumbnail` of `avatar_url` | Afbeelding-URL (optioneel) |
| Auteur | `author` of `history.lastUpdated.by.displayName` | Auteur-naam (optioneel) |

**Voorbeeld-configuraties:**

| Systeem | Endpoint | Items-pad | Titel | URL |
|---------|----------|-----------|-------|-----|
| Jira | `/rest/api/2/search?jql=ORDER+BY+updated` | `issues` | `fields.summary` | `self` |
| Confluence | `/rest/api/content?spaceKey=WIKI&expand=history.lastUpdated` | `results` | `title` | `_links.webui` |
| OpenProject | `/api/v3/work_packages` | `_embedded.elements` | `subject` | `_links.self.href` (auto-geconverteerd naar web-URL) |
| ZGW (Open Zaak) | `/zaken/api/v1/zaken` | `results` | `omschrijving` | `url` |

**Authenticatie & personalisatie:**

REST-API-connections ondersteunen dezelfde drie authenticatie-niveaus als LMS-connections. Zo werken security-trimmed, gepersonaliseerde feeds:

1. **Admin-token (gedeeld)** — één API-token geconfigureerd door de beheerder. Alle gebruikers zien dezelfde data, beperkt tot waar het service-account toegang toe heeft. Goed voor publieke APIs of gedeelde dashboards.

2. **OAuth2 (per gebruiker)** — elke gebruiker koppelt zijn eigen account via OAuth2. De API geeft alleen data terug waar die gebruiker toegang toe heeft. Volledig security-trimmed. Vereist dat het externe systeem OAuth2 ondersteunt en dat de beheerder Client ID + Secret configureert.

3. **OIDC auto-connect (zero-click SSO)** — als Nextcloud en het externe systeem dezelfde identity provider delen (bv. SURFconext, Keycloak, Azure AD), gebruikt IntraVox automatisch het bestaande SSO-token van de gebruiker. Geen klikken nodig — volledig gepersonaliseerd, volledig security-trimmed, volledig transparant.

De widget resolvet tokens in deze prioriteits-volgorde: OIDC auto-connect → per-gebruiker OAuth2 → admin-fallback. Als er geen token beschikbaar is en authMode is `oauth2`, toont de widget een "Connect your account"-prompt.

**Wanneer welk auth-niveau te gebruiken:**

| Scenario | Auth-niveau | Voorbeeld |
|----------|-------------|-----------|
| Publieke API, geen login nodig | Geen authenticatie | SURF Confluence (publieke spaces) |
| Gedeeld dashboard, zelfde view voor iedereen | Admin-token | Bedrijfs-brede Jira-board |
| Persoonlijke feed, elke gebruiker ziet eigen data | OAuth2 (per gebruiker) | Mijn Jira-tickets, mijn TOPdesk-incidenten |
| SSO-omgeving, zero friction | OIDC auto-connect | SURFconext + Jira, Azure AD + M365 |

## Layouts

### Lijst-layout

Toont items in een verticale lijst met optionele afbeelding, titel, datum, samenvatting en bron.

### Grid-layout

Toont items in een responsive grid. Configureer tussen 2, 3 of 4 kolommen.

## De widget toevoegen

1. Klik op **+ Widget toevoegen** in bewerk-modus
2. Selecteer **Feed** uit de widget-picker
3. Kies een bron-type uit de dropdown
4. Configureer de bron (URL voor RSS, of selecteer een connection voor LMS)
5. Pas layout- en weergave-opties aan
6. Klik op **Opslaan**

Beheerders configureren feed-verbindingen in **Beheerinstellingen → Externe feeds**. Presets zijn beschikbaar voor populaire systemen:

![Beschikbare connection-presets in IntraVox-beheerinstellingen](../../screenshots/feed-presets.png)

## Configuratie

### Bron-instellingen

| Instelling | Beschrijving | Geldt voor |
|------------|--------------|------------|
| **Bron-type** | RSS/Atom-feed of connection (door beheerder geconfigureerd) | Alle |
| **Feed-URL** | URL van de RSS- of Atom-feed | Alleen RSS |
| **Connection** | Door beheerder geconfigureerde connection om te gebruiken | Connections |
| **Content-type** | Afhankelijk van connection-type (zie secties hierboven) | LMS, Jira, OpenProject, SharePoint |
| **Project** | Jira-project-filter (auto-populated dropdown) | Jira |
| **Forum** | Moodle-forum-filter binnen een cursus | Moodle (Nieuws) |
| **Cursus** | Beperk resultaten tot een specifieke cursus | Alleen LMS |
| **Document-library / list** | SharePoint-library- of list-selector | SharePoint |

![Widget-editor met Jira-connection, projectselector, content-type, sortering, filter en live preview](../../screenshots/feed-widget-config.png)

![SharePoint-widget-editor met content-type en document-library-selector](../../screenshots/feed-sharepoint-selection.png)

### Sorteren & filteren

| Instelling | Beschrijving | Default |
|------------|--------------|---------|
| **Sorteer op** | Datum of titel | Datum |
| **Sorteer-volgorde** | Context-afhankelijke toggle: "Nieuwste eerst / Oudste eerst" voor datum, "A → Z / Z → A" voor titel | Nieuwste eerst |
| **Filter op trefwoord** | Toon alleen items met dit woord in titel, samenvatting of auteur | *(leeg — geen filter)* |

Sorteren en filteren worden server-side toegepast na caching. De cache slaat alle items op; sort/filter selecteert uit de gecachte set. Dit betekent dat sort/filter-wijzigingen direct zijn (geen re-fetch vanuit externe API).

![Trefwoord-filter en sorteer-volgorde in de widget-editor met live preview](../../screenshots/feed-search.png)

### Taal

Feed-requests bevatten automatisch de `Accept-Language`-HTTP-header op basis van de Nextcloud-taalvoorkeur van de huidige gebruiker. Bijvoorbeeld, een gebruiker met Nederlands (`nl`) als taal-instelling veroorzaakt dat alle feed-API-requests bevatten:

```
Accept-Language: nl,en;q=0.9
```

Dit vertelt het externe systeem om content in het Nederlands terug te geven indien beschikbaar, met Engels als fallback. Dit volgt de [HTTP-content-negotiation-standaard](https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/Accept-Language) — hetzelfde mechanisme dat browsers gebruiken.

**Hoe het werkt:**

- De taal wordt per request bepaald uit de Nextcloud-instelling van de viewende gebruiker (Instellingen → Persoonlijk → Taal)
- Geen beheerder-configuratie nodig — dit is automatisch, standaard HTTP-gedrag
- Engels (`en`) wordt gebruikt als fallback wanneer de taal van de gebruiker niet is ingesteld of voor anonieme/publieke pagina-bezoekers
- Systemen die `Accept-Language` respecteren zijn onder andere Jira, Confluence, SharePoint (Microsoft Graph), OpenProject, Moodle, Canvas en Brightspace
- RSS/Atom-feeds ontvangen de header ook, hoewel de meeste feeds content in een vaste taal serveren

**Let op:** de taal-header beïnvloedt systeem-gegenereerde strings (bv. issue-type-namen, status-labels, UI-tekst uit APIs) — geen door gebruiker aangemaakte content. De titel en beschrijving van een Jira-issue blijven altijd in de taal waarin ze zijn geschreven, maar veld-labels als "Bug" vs. "Fout" kunnen veranderen.

### Layout-instellingen

| Instelling | Beschrijving | Default |
|------------|--------------|---------|
| **Layout** | Lijst of grid | Lijst |
| **Kolommen** | Aantal grid-kolommen (2-4) | 3 |
| **Aantal items** | Maximum items om te tonen (1-20) | 5 |

### Weergave-opties

| Instelling | Beschrijving | Default |
|------------|--------------|---------|
| **Toon afbeelding** | Toon item-thumbnail of geëxtraheerde afbeelding | Aan |
| **Toon datum** | Toon publicatiedatum | Aan |
| **Toon samenvatting** | Toon tekst-samenvatting | Aan |
| **Toon bron** | Toon de bron-/feed-naam | Uit |
| **Open links in nieuwe tab** | Links openen in een nieuwe browser-tab | Aan |

## Gepersonaliseerde LMS-content

Standaard gebruiken LMS-connections een gedeeld admin-token — alle gebruikers zien dezelfde data. Met per-gebruiker-authenticatie koppelt elke gebruiker zijn eigen account en ziet alleen content waar ze toegang toe hebben.

### Hoe het werkt

De widget resolvet tokens in deze volgorde:

1. **OIDC auto-connect** — als ingeschakeld en Nextcloud + LMS dezelfde identity provider delen, gebruikt de widget automatisch het bestaande SSO-token van de gebruiker (zero clicks)
2. **Per-gebruiker OAuth2-token** — als de gebruiker eerder via OAuth2 heeft gekoppeld
3. **Per-gebruiker handmatig token** — als de gebruiker handmatig een API-token heeft ingevoerd
4. **Admin-fallback** — als `authMode` `both` is, valt terug op het gedeelde admin-token
5. **Geen token** — als `authMode` `oauth2` is en de gebruiker niet heeft gekoppeld, toont een "Connect your account"-prompt

### Je account koppelen

Wanneer je een pagina opent met een Feed-widget die authenticatie vereist:

1. De widget toont een **"Niet gekoppeld"**-badge en een **"Connect your account"**-knop
2. Klik op de knop — een popup opent met de LMS-login-pagina
3. Log in en autoriseer IntraVox
4. De popup sluit automatisch en de widget laadt je gepersonaliseerde content

Voor Moodle of Brightspace zonder OAuth2 kun je ook op **"Token handmatig invoeren"** klikken en je API-token plakken.

### Loskoppelen

Klik op de **"Disconnect"**-knop naast de groene "Connected"-badge in de widget-editor.

## Token-refresh

OAuth2-tokens voor Canvas, Moodle en Brightspace worden automatisch vernieuwd bij verloop — geen gebruikers-actie vereist. Als de refresh faalt (bv. toegang ingetrokken in het LMS), wordt de gebruiker gevraagd opnieuw te koppelen.

Handmatige tokens (Moodle en Brightspace) verlopen niet automatisch.

## Tips

- **Live preview** — de editor toont een live preview van de feed tijdens configuratie. Wijzigingen in bron, filters, layout en weergave-opties worden direct weerspiegeld
- **Content-types** — gebruik "Beschikbare cursussen" om de cursus-catalogus te tonen, "Opdrachten" voor opdrachten-overzichten, "Aankomende deadlines" voor calendar-events, of "Nieuws" voor aankondigingen
- **Cursus-filter** — getoond voor "Nieuws"- en "Opdrachten"-content-types. Laat leeg voor alle cursussen, of selecteer een specifieke cursus
- **Forum-selector** — wanneer "Nieuws" is geselecteerd met een specifieke cursus, kies dan een specifiek forum (bv. "Aankondigingen") in plaats van alle forums
- **Jira-projectfilter** — selecteer een specifiek Jira-project uit de dropdown, of laat staan op "Alle projecten" voor een cross-project-view
- **Meerdere feeds** — voeg meerdere Feed-widgets toe aan een pagina voor verschillende bronnen of content-types (bv. één voor Canvas-deadlines, één voor Brightspace-aankondigingen, één voor RSS)
- **Publieke pagina's** — Feed-widgets op publiek gedeelde pagina's gebruiken het admin-token (indien beschikbaar). Per-gebruiker-tokens worden niet gebruikt voor anonieme bezoekers

## Foutmeldingen

De widget toont specifieke foutmeldingen op basis van wat er fout ging:

| Bericht | Betekenis | Actie |
|---------|-----------|-------|
| "Deze connection is momenteel uitgeschakeld door een beheerder" | De connection bestaat maar is op inactief gezet | Geen actie nodig — de widget hervat automatisch wanneer de beheerder de connection weer inschakelt |
| "Connection bestaat niet meer" | De door de beheerder geconfigureerde connection is verwijderd | Herconfigureer de widget met een andere connection |
| "Authenticatie vereist" | Geen geldig API-token of OAuth2-token beschikbaar | Koppel je account of vraag de beheerder een token te configureren |
| "Toegang geweigerd" | Het API-token mist permissies voor deze content | Check de connection-credentials in beheerinstellingen |
| "Te veel requests" | De externe API-rate-limit is bereikt | Wacht en probeer opnieuw — de widget cached resultaten om dit te voorkomen |
| "Kon feed niet laden" | Generieke verbindings-error | Check de connection-URL en credentials in beheerinstellingen |
| "Bron tijdelijk niet beschikbaar" | De circuit breaker is geopend na herhaalde failures | Het externe systeem is down — de widget probeert automatisch opnieuw na 5 minuten |

## Problemen oplossen

| Probleem | Oplossing |
|----------|-----------|
| "Geen items gevonden" | Check de feed-URL of verifieer dat het LMS content heeft in de geselecteerde cursus |
| "Feed laden mislukt" | Verifieer de connection-URL en credentials in beheerinstellingen |
| Canvas toont "Missing context_codes" | Dit is opgelost in v1.2.0 — de widget haalt nu automatisch cursussen op |
| OAuth2-popup geblokkeerd | Sta popups toe voor je Nextcloud-domein in browser-instellingen |
| "Connect your account" verschijnt niet | De connection moet `authMode` ingesteld hebben op `oauth2` of `both` in beheerinstellingen |
| Verouderde data | De widget cached resultaten voor 15 minuten. Wacht of leeg de Nextcloud-cache |
