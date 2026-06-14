# IntraVox-scenario's

Praktische recepten voor veelvoorkomende use-cases, IntraVox combinerend met andere Nextcloud-apps. Deze scenario's vereisen geen wijzigingen in IntraVox zelf — ze gebruiken bestaande Nextcloud-features en IntraVox' native permissie-model.

**Gerelateerde documentatie:**

- [Beheerdersgids](guide.md) — Installatie en configuratie
- [Autorisatie](authorization.md) — Permissies en toegangscontrole
- [Editor-gids](../user/editor.md) — Content bewerken en publiceren

---

## Scenario 1: content-goedkeurings-workflow

Een drie-laags content-workflow waarbij makers concept-pagina's opstellen, reviewers feedback geven, en publishers content zichtbaar maken voor de organisatie.

> **Verwijst naar issue:** [#33 — Capabilities for moderation of content and workflow function](https://github.com/voxcloud/intravox/issues/33)

### Doel

| Rol | Kan | Groep |
|-----|-----|-------|
| Maker | Concept-pagina's aanmaken en bewerken | IntraVox Editors |
| Reviewer | Concepts bewerken, publicatie aanvragen | Sectie-specifieke editor-groep |
| Publisher | Pagina's publiceren (concept → gepubliceerd) | IntraVox Admins |

### Vereisten

| App | Vereist? | Doel |
|-----|----------|------|
| [GroupFolders](https://apps.nextcloud.com/apps/groupfolders) | Ja | Bestand-gebaseerde permissies en ACL |
| [Approval](https://apps.nextcloud.com/apps/approval) | Aanbevolen | Formele goedkeur/afwijs-workflow op bestanden |
| [MetaVox](https://apps.nextcloud.com/apps/metavox) | Optioneel | Custom metadata-velden voor status-tracking |

### Stap 1: gebruik de ingebouwde permissie-groepen

IntraVox maakt automatisch drie groepen aan tijdens setup:

| Groep | Permissies | Rol in deze workflow |
|-------|------------|-----------------------|
| IntraVox Admins | Volledige toegang | **Publishers** — kunnen concept/gepubliceerd toggelen |
| IntraVox Editors | Lezen + Schrijven + Aanmaken | **Makers/Reviewers** — kunnen pagina's aanmaken en bewerken |
| IntraVox Users | Alleen lezen | **Lezers** — zien alleen gepubliceerde pagina's |

Deze drie-laags-structuur dekt de basis-workflow al:

1. **Editors** maken een nieuwe pagina aan — automatisch als **Concept**
2. Editors schrijven en verfijnen de content terwijl die onzichtbaar blijft voor lezers
3. Een **Admin** reviewt de pagina en toggelt naar **Gepubliceerd**

> **Tip:** concept-pagina's zijn volledig onzichtbaar voor gebruikers met alleen-lezen-toegang. Ze verschijnen niet in navigatie, zoeken, RSS-feeds of publieke share-links.

### Stap 2: sectie-specifieke groepen toevoegen (optioneel)

Voor grotere organisaties: maak custom groepen per afdeling of sectie aan:

1. Ga naar **Nextcloud-beheer → Gebruikers** en maak groepen als:
   - `Marketing Editors`
   - `HR Editors`
2. Ga naar **Beheer → GroupFolders** → IntraVox-folder
3. Schakel **Advanced Permissions** (ACL) in
4. Navigeer in Nextcloud Files naar de sectie-folder (bv. `IntraVox/nl/marketing/`)
5. Klik op het share-icoon → stel ACL-regels in:
   - `Marketing Editors`: Lezen + Schrijven + Aanmaken
   - Anderen: alleen lezen

Marketing-editors kunnen zo alleen marketing-pagina's bewerken, geen HR-pagina's.

### Stap 3: zet de Approval-app op

De [Nextcloud Approval-app](https://apps.nextcloud.com/apps/approval) voegt een formele review-workflow toe aan bestanden. Omdat IntraVox-pagina's als JSON-bestanden in GroupFolders zijn opgeslagen, werkt de Approval-app er direct mee.

#### 3a. Installeren en inschakelen

```bash
occ app:enable approval
```

#### 3b. Maak system-tags

Ga naar **Beheer → Basis-instellingen → Collaboratieve tags** en maak:

| Tag | Zichtbaarheid | Doel |
|-----|----------------|------|
| `Pending Review` | Verborgen | Toegekend als editor review aanvraagt |
| `Approved` | Verborgen | Toegekend bij publisher-goedkeuring |
| `Rejected` | Verborgen | Toegekend bij publisher-afwijzing |

> Verborgen tags zijn alleen zichtbaar voor beheerders, wat de workflow netjes houdt voor reguliere gebruikers.

#### 3c. Configureer de approval-workflow

Ga naar **Beheer → Approval-workflows** en maak een workflow:

| Instelling | Waarde |
|------------|--------|
| **Beschrijving** | Content-publicatie-review |
| **Pending-tag** | `Pending Review` |
| **Approved-tag** | `Approved` |
| **Rejected-tag** | `Rejected` |
| **Approvers** | De `IntraVox Admins`-groep (publishers) |

#### 3d. De workflow in de praktijk

1. **Editor** maakt een pagina in IntraVox (start als Concept)
2. Editor opent het JSON-bestand van de pagina in Nextcloud Files-zijbalk → klikt **Goedkeuring aanvragen**
3. Publishers (IntraVox Admins) ontvangen een notificatie
4. Publisher reviewt de pagina in IntraVox, daarna:
   - **Goedkeuren** → opent Files-zijbalk en keurt het bestand goed → toggelt pagina naar Gepubliceerd in IntraVox
   - **Afwijzen** → wijst af in Files-zijbalk, editor krijgt notificatie om te herzien

#### 3e. Geketende workflows (meervoudige goedkeuring)

Voor meertraps-goedkeuring (bv. editor → afdelings-hoofd → publisher), maak gekoppelde workflows waar de "approved"-tag van workflow A de "pending"-tag van workflow B is:

| Workflow | Pending | Approved | Approvers |
|----------|---------|----------|-----------|
| **A: Sectie-review** | `Pending Review` | `Section Approved` | Sectie-editors |
| **B: Publicatie** | `Section Approved` | `Published` | IntraVox Admins |

### Stap 4: status tracken met MetaVox (optioneel)

Met [MetaVox](https://apps.nextcloud.com/apps/metavox) kun je custom metadata-velden toevoegen aan pagina's voor visuele status-tracking.

#### 4a. Maak metadata-velden

In MetaVox, maak deze velden:

| Veldnaam | Type | Opties |
|----------|------|--------|
| Review-status | Select | `Concept`, `In review`, `Goedgekeurd`, `Gepubliceerd` |
| Reviewer | Tekst | *(vrije tekst)* |
| Review-datum | Datum | *(datum-picker)* |

#### 4b. Gebruik in News-widget

De IntraVox News-widget ondersteunt MetaVox-veld-filtering. Hiermee maak je een "Pending Reviews"-pagina voor publishers:

1. Maak een pagina met een News-widget
2. Stel de bron-folder in op de sectie die je wilt monitoren
3. Voeg een MetaVox-filter toe: `Review-status` is `In review`

Geeft publishers een dashboard van alle pagina's die op hun review wachten.

### Pagina-mockup — publisher-dashboard

Een "Pending Reviews"-pagina als publishers' dagelijkse landings-plek. Twee News-widgets naast elkaar, beide gefilterd op het MetaVox `Review-status`-veld.

```
┌──────────────────────────────────────────────────────────────────────┐
│  IntraVox    Home  Nieuws  Afdelingen  Pending Reviews     🔍 NL    │
├──────────────────────────────────────────────────────────────────────┤
│                                                                      │
│   # Redactioneel dashboard                                           │
│   Pagina's die wachten op review, plus wat recent is gepubliceerd.   │
│                                                                      │
├───────────────────────────────────┬──────────────────────────────────┤
│  ⏳ Wacht op jouw review          │  ✅ Recent gepubliceerd          │
│  ─────────────────────────────    │  ────────────────────────────    │
│  News-widget · Lijst              │  News-widget · Lijst             │
│  bron: /nl/                       │  bron: /nl/                      │
│  filter: Review-status = "In      │  filter: Review-status =         │
│          review"                  │          "Gepubliceerd"          │
│  sort:   Wijziging desc           │  sort:   Wijziging desc          │
│                                   │                                  │
│  • Q3 financiële update           │  • Nieuw kantoor in Utrecht      │
│    Marketing · Anna · 2u terug    │    HR · Sam · vandaag            │
│  • Bijgewerkt reisbeleid          │  • Code-of-conduct refresh       │
│    HR · Mira · gisteren           │    HR · Anna · 2 dagen           │
│  • Pilot-resultaten Project X     │  • Q3 product-lancering          │
│    R&D · Jan · 3 dagen            │    Marketing · Mira · vorige week│
└───────────────────────────────────┴──────────────────────────────────┘
```

Elke card linkt direct naar de pagina; de publisher opent de pagina, klikt **Goedkeuren** in de Files-zijbalk (Approval-app), en toggelt **Gepubliceerd** in IntraVox.

### Tips

- Communiceer het workflow-proces aan je team — de technologie ondersteunt het, mensen moeten de stappen kennen
- Gebruik **Nextcloud Talk** voor review-discussies: deel een link naar de concept-pagina in een Talk-conversatie
- De Approval-app stuurt notificaties — publishers hoeven niet handmatig te checken
- Overweeg **Nextcloud Flow** (automatische tagging) om bestanden in specifieke folders automatisch te taggen, wat handmatige stappen vermindert

---

## Scenario 2: afdelings-gebaseerd intranet

Een multi-afdelings-intranet waar elke afdeling z'n eigen sectie beheert, met organisatie-brede pagina's zichtbaar voor iedereen.

### Doel

```
IntraVox/
├── nl/
│   ├── home.json              ← Iedereen kan lezen
│   ├── nieuws/                ← Editors: centraal communicatie-team
│   ├── afdelingen/
│   │   ├── hr/                ← HR-team: volledige controle
│   │   ├── sales/             ← Sales-team: volledige controle
│   │   ├── marketing/         ← Marketing-team: volledige controle
│   │   └── it/                ← IT-team: volledige controle
│   └── algemeen/              ← Iedereen kan lezen
```

Elke afdeling heeft volledige controle over de eigen sectie. Alle medewerkers kunnen alles lezen. Een centraal communicatie-team beheert organisatie-brede nieuws.

### Vereisten

| App | Vereist? | Doel |
|-----|----------|------|
| [GroupFolders](https://apps.nextcloud.com/apps/groupfolders) | Ja | Bestand-gebaseerde permissies en ACL |

### Stap 1: maak afdelings-groepen

Ga naar **Nextcloud-beheer → Gebruikers** en maak groepen per afdeling:

- `Afdeling HR`
- `Afdeling Sales`
- `Afdeling Marketing`
- `Afdeling IT`
- `Communicatie-team`

Voeg gebruikers toe aan elke groep.

### Stap 2: configureer basis-permissies

Zorg in **Beheer → GroupFolders** dat de IntraVox-folder deze groepen heeft:

| Groep | Basis-permissies |
|-------|-------------------|
| IntraVox Admins | Alles (automatisch) |
| IntraVox Editors | Lezen + Schrijven + Aanmaken (automatisch) |
| IntraVox Users | Lezen (automatisch) |
| Alle afdelings-groepen | Lezen |
| Communicatie-team | Lezen |

> Voeg alle afdelings-groepen en het Communicatie-team toe aan de GroupFolder met **Lezen**-permissie als basis-niveau. ACL-regels in de volgende stap kennen verhoogde permissies toe op specifieke folders.

### Stap 3: schakel ACL in en stel folder-permissies in

1. Schakel **Advanced Permissions** in op de IntraVox-folder in **Beheer → GroupFolders**
2. Navigeer in Nextcloud Files naar elke afdelings-folder en configureer ACL:

**Voor `IntraVox/nl/afdelingen/hr/`:**

| Groep/Gebruiker | Lezen | Schrijven | Aanmaken | Verwijderen |
|------------------|-------|-----------|----------|-------------|
| Afdeling HR | ✅ | ✅ | ✅ | ✅ |

**Voor `IntraVox/nl/afdelingen/sales/`:**

| Groep/Gebruiker | Lezen | Schrijven | Aanmaken | Verwijderen |
|------------------|-------|-----------|----------|-------------|
| Afdeling Sales | ✅ | ✅ | ✅ | ✅ |

*(Herhaal per afdeling)*

**Voor `IntraVox/nl/nieuws/`:**

| Groep/Gebruiker | Lezen | Schrijven | Aanmaken | Verwijderen |
|------------------|-------|-----------|----------|-------------|
| Communicatie-team | ✅ | ✅ | ✅ | ✅ |

### Stap 4: maak de folder-structuur

Maak in IntraVox de pagina-hiërarchie:

1. Maak een **Afdelingen**-ouder-pagina
2. Onder die: subpagina's per afdeling: HR, Sales, Marketing, IT
3. Maak een **Nieuws**-sectie voor organisatie-brede nieuws
4. Stel navigatie in die deze structuur reflecteert

### Stap 5: news-widgets per afdeling

Elke afdelings-homepage kan een News-widget bevatten met eigen recente pagina's:

1. Bewerk de afdelings-homepage (bv. HR-homepage)
2. Voeg een News-widget toe
3. Stel de bron-folder in op de afdelings-folder (bv. `afdelingen/hr`)
4. Configureer layout (grid, lijst of carousel)

De hoofd-homepage kan een News-widget bevatten met organisatie-brede nieuws uit de `nieuws/`-folder.

### Resultaat

| Gebruiker | Kan lezen | Kan bewerken |
|-----------|-----------|---------------|
| Elke medewerker (IntraVox Users) | Alle pagina's | Niets |
| HR-team-lid | Alle pagina's | Alleen `afdelingen/hr/` |
| Sales-team-lid | Alle pagina's | Alleen `afdelingen/sales/` |
| Communicatie-team | Alle pagina's | Alleen `nieuws/` |
| IntraVox Admins | Alle pagina's | Alles |

---

## Scenario 3: knowledge base met documenten + foto's

Een pagina-per-onderwerp-knowledge-base waar elke topic-pagina automatisch documenten en foto's toont die bij dat onderwerp horen — zonder bestanden te kopiëren, zonder folder-per-topic-explosie en zonder handmatige link-curation.

### Doel

Bouw een knowledge base waar elk onderwerp (een procedure, techniek, product, …) op een eigen pagina staat. De pagina rendert:

- Een introductie + inklapbare werkinstructies (tekst)
- Een gegroepeerde lijst van gerelateerde documenten (PDF, Word, tekeningen, …)
- Een foto-galerij van praktijkvoorbeelden

Alle documenten staan in één gedeelde folder; alle foto's blijven in hun originele projectmappen. Eén MetaVox-tag (`topic`) is de enige link tussen ze.

Werkvoorbeeld: een erfgoed-restauratie-bureau met topic-pagina's voor *Glas-in-lood-restauratie*, *Voegwerk*, *Leien-dakwerk*, etc.

### Vereisten

- MetaVox-app geïnstalleerd en geconfigureerd
- Eén **Select**- of **Tekst**-veld in MetaVox genaamd `topic`, met waarden die matchen met je topic-pagina's — *glas-in-lood*, *voegwerk*, *leien-dakwerk*, …
- Optioneel een tweede veld `document_type` (specificatie / inspectie-rapport / tekening / vergunning) voor groepering van de documenten-sectie
- Een centrale documenten-folder, bv. `/KnowledgeBase/Documenten/`
- Foto-folders overal — worden cross-folder opgehaald

### Stap 1: tag documenten met het topic-veld

Stel in de centrale documenten-folder MetaVox `topic` in op elk bestand (en optioneel `document_type`). Bestaande bestanden bulk taggen kan via de MetaVox bulk-editor.

### Stap 2: tag foto's met hetzelfde veld

Stel MetaVox `topic` in op relevante foto's in hun bestaande projectmappen. Verplaatsen of kopiëren niet nodig.

### Stap 3: maak één topic-pagina

Dupliceer een bestaande topic-pagina (of begin opnieuw) voor het nieuwe onderwerp. Het recept per topic-pagina:

| Rij | Layout | Widget(s) | Notes |
|-----|--------|-----------|-------|
| 1 | 1 kol | Kop + Tekst | Topic-titel + korte intro |
| 2 | 1 kol | Inklapbare sectie | Werkinstructies, FAQ-stijl |
| 3 | 2 kol | File Story (links) + Tekst (rechts) | Documenten links, "hoe deze te lezen" rechts |
| 4 | 1 kol | Photo Story | Voorbeelden-galerij |
| Rechter sidebar | — | People + Links | Specialisten + externe referenties |

### Stap 4: configureer File Story (documenten)

- **Bron-folder**: de centrale documenten-folder (bv. `/KnowledgeBase/Documenten/`)
- **Modus**: gegroepeerd
- **Groeperen op**: `document_type` (of *Categorie (bestandstype)* als je het veld oversloeg)
- **Sorteer op**: wijzigingsdatum (desc)
- **Filters**: `topic equals <topic-waarde>` — de enige regel die per topic-pagina verschilt

### Stap 5: configureer Photo Story (galerij)

- **Bron-folder**: *leeg laten* — dit activeert cross-folder MetaVox-zoeken
- **Filters**: `topic equals <topic-waarde>` (vereist wanneer bron leeg is)
- **Modus**: grid (3 of 4 kolommen); tijdlijn als chronologie belangrijk is
- **Toon onderschriften**: aan

Foto's blijven in hun originele projectmappen; cross-folder-modus haalt elke foto met de matching tag op.

### Stap 6: dupliceren per nieuw onderwerp

Per onderwerp: kopieer de pagina, hernoem hem, wijzig de `topic`-filter-waarde in beide widgets. Geen nieuwe folders, geen gekopieerde bestanden.

### Tips

- **Eén MetaVox-tag is alle onderhoud** — drop een nieuw document in de gedeelde folder, tag het, en het verschijnt op de juiste topic-pagina
- **Geen folder-per-topic-explosie** — de centrale documenten-folder blijft een vlak, browsebaar archief
- **Foto's worden gratis hergebruikt** — één projectfoto kan op meerdere topic-pagina's verschijnen door een tweede tag-waarde
- **`document_type`-groepering** maakt de bestandslijst tot een zelfsprekende index — lezers zien direct *"3 specificaties, 5 inspectie-rapporten"* zonder filteren
- **Hub-pagina**: voeg een News-widget op een ouder-pagina toe gefilterd op `topic is niet leeg` voor één doorbladerbare ingang
- **Redactionele flow**: combineer met [Scenario 1](#scenario-1-content-goedkeurings-workflow) als topic-pagina's een review/approval-poort vóór publicatie nodig hebben

---

## Meer scenario's toevoegen

Heb je een use case waar anderen baat bij hebben? Scenario's die we overwegen:

- **Meertalig intranet** — content beheren over talen heen met aparte redactionele teams
- **Externe nieuws-aggregatie** — RSS-feeds gebruiken om IntraVox-content met externe tools te delen
- **Template-gebaseerde pagina-creatie** — paginalayouts standaardiseren over afdelingen

Bijdragen welkom via [GitHub-issues](https://github.com/voxcloud/intravox/issues).
