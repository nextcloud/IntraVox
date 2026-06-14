# IntraVox-showcases

## Overzicht

IntraVox levert 5 demo-showcases die het volledige spectrum aan widget-typen, layout-opties en content-patronen tonen. Elke showcase staat voor een ander organisatie-type met realistische Nederlandstalige content.

## Showcases

### 1. de-linden — Universiteit De Linden

![De Linden-showcase](../screenshots/home-de-linden.png)

**Thema:** Hoger onderwijs — academisch, open, kennisdeling

| Feature | Waarde |
|---------|--------|
| Kolommen | 1, 2, 3, 4 |
| Sidebars | Rechts |
| Header-rij | Nee |
| Foto's | 4 (campus, bibliotheek, aula, studenten) |

**Unieke widgets:**

- Video-widget (YouTube-embed: campus-tour)
- People-widget (grid-layout, 3 kolommen, filter-modus)
- Links in zowel tegels als lijst-layout
- 4-koloms rij met gemengde content (tekst, beeld, video)

**Content-highlights:**

- Nederlandse universiteits-IT-systemen: Brightspace, OSIRIS, SURFdrive, TimeEdit, PURE, WorldCat
- SURF-diensten in sidebar-links (SURFnet, SURFdrive, SURFconext, eduroam)
- Inklapbare onboarding en FAQ-secties

---

### 2. van-der-berg — Advocatenkantoor Van der Berg & Partners

![Van der Berg-showcase](../screenshots/home-van-der-berg.png)

**Thema:** Juridisch — professioneel, premium, vertrouwelijk

| Feature | Waarde |
|---------|--------|
| Kolommen | 1, 2, 3 |
| Sidebars | Geen |
| Header-rij | Ja (full-width kantoorfoto) |
| Foto's | 3 (kantoor-header, vergaderruimte, kantoor) |

**Unieke widgets:**

- Header-rij met full-width afbeelding
- News-widget (grid-layout, 2 kolommen)
- File-widgets (3 downloadbare documenten)
- Dashed-scheider-stijl

**Content-highlights:**

- Juridische praktijkgebieden: Ondernemingsrecht, Arbeidsrecht, Vastgoedrecht
- Externe juridische bronnen: Rechtspraak.nl, KvK, Belastingdienst, Kadaster
- Meerdere kantoorlocaties (Amsterdam, Rotterdam, Den Haag)

---

### 3. gemeente-duin — Gemeente Duinvoorde

![Gemeente Duinvoorde-showcase](../screenshots/home-duinvoorde.png)

**Thema:** Overheid — transparant, dienstverlenend

| Feature | Waarde |
|---------|--------|
| Kolommen | 1, 2, 3, 5 |
| Sidebars | Links (enige showcase met linker-sidebar) |
| Header-rij | Nee |
| Foto's | 3 (gemeentehuis, vergadering, balie) |

**Unieke widgets:**

- Linker-sidebar met quick-links en contact-info
- 5-koloms rij (gemeente-statistieken)
- News-widget (lijst-layout)
- Sidebar-afbeelding bovenaan

**Content-highlights:**

- Gemeente-data: 42.000 inwoners, 320 medewerkers, 12 afdelingen
- Overheids-links: VNG, Overheid.nl, KING, Waarstaatjegemeente.nl
- Gemeenteraads-agenda, balietijden

---

### 4. de-bron — Zorggroep De Bron

![De Bron-showcase](../screenshots/home-de-bron.png)

**Thema:** Zorg — warm, praktisch, patient-centered

| Feature | Waarde |
|---------|--------|
| Kolommen | 1, 2, 4 |
| Sidebars | Rechts |
| Header-rij | Nee |
| Foto's | 3 (zorgteam, verpleging, locatie) |

**Unieke widgets:**

- People-widget (card-layout, 2 kolommen)
- Video-widget (zorg-instructie-video)
- File-widgets (4 protocol-documenten)
- Dashed-scheider binnen content-rij
- 4-koloms afdelings-overzicht

**Content-highlights:**

- Zorg-afdelingen: Verpleging, Revalidatie, Dagbesteding, Thuiszorg
- Kwaliteits-certificeringen: ISO 9001:2015, HKZ Keurmerk
- Externe zorg-links: RIVM, V&VN, IGJ, Vilans, ActiZ
- MIC/MIP incident-melding, scholingspunten

---

### 5. horizon-labs — Horizon Labs

![Horizon Labs-showcase](../screenshots/home-horizon-labs.png)

**Thema:** Tech-startup — modern, dynamisch, innovatief

| Feature | Waarde |
|---------|--------|
| Kolommen | 1, 2, 3 |
| Sidebars | Rechts |
| Header-rij | Nee |
| Foto's | 2 (team, werkplek) |

**Unieke widgets:**

- News-widget (carousel-layout met autoplay)
- `var(--color-primary-element-light)` cultuur-rij
- Links-tegels met afwisselende achtergrond-kleuren

**Content-highlights:**

- Tech-tools: Collabora, Diagrammen, Tabellen, Peilingen
- Sprint-reviews, hackathons, tech-talks
- Kantoren in Amsterdam, Utrecht, Eindhoven

---

## Widget-coverage-matrix

| Widget | de-linden | van-der-berg | gemeente-duin | de-bron | horizon-labs |
|--------|-----------|--------------|----------------|---------|---------------|
| heading | x | x | x | x | x |
| text | x | x | x | x | x |
| image | x (4) | x (3) | x (3) | x (3) | x (2) |
| links (tegels) | x | x | x | x | x |
| links (lijst) | x | x | x | x | x |
| divider | x | x | x | x | x |
| divider (dashed) | | x | | x | |
| video | x | | | x | |
| news (lijst) | | | x | | |
| news (grid) | | x | | | |
| news (carousel) | | | | | x |
| people (grid) | x | | | | |
| people (card) | | | | x | |
| file | | x | | x | |
| collapsible | x | | x | x | x |
| header-rij | | x | | | |
| linker-sidebar | | | x | | |
| rechter-sidebar | x | | | x | x |

## Technische structuur

Elke showcase volgt deze bestandsstructuur:

```
showcases/{naam}/
├── export.json          # Volledige export met content-wrapper (voor import)
├── {naam}.zip           # Direct-importeerbare ZIP
├── *.jpg                # Bron-foto's (Unsplash, royalty-vrij)
└── nl/
    ├── home.json        # Pagina-data (zonder content-wrapper)
    ├── navigation.json  # Megamenu-navigatie-structuur
    ├── footer.json      # Footer-content
    └── _media/          # Foto's voor import (gekopieerd uit root)
        └── *.jpg
```

### Export-format

De `export.json` wikkelt de pagina-data voor import:

```json
{
    "exportVersion": "1.3",
    "schemaVersion": "1.3",
    "exportDate": "2026-03-07T12:00:00.000Z",
    "exportedBy": "IntraVox Showcase - {Naam}",
    "requiresMinVersion": "0.8.11",
    "language": "nl",
    "pages": [{
        "_exportPath": "home",
        "uniqueId": "page-...",
        "title": "...",
        "content": { /* volledige home.json-content */ }
    }],
    "navigation": { /* navigation.json */ },
    "footer": { /* footer.json */ },
    "comments": []
}
```

### Achtergrond-kleuren

Showcases gebruiken alleen CSS-variabelen die correcte tekst-contrast-afhandeling hebben:

| Variabele | Uiterlijk | Tekst-kleur |
|-----------|-----------|--------------|
| `var(--color-primary-element)` | Thema-primair (donkerblauw) | Wit (automatisch) |
| `var(--color-primary-element-light)` | Lichte tint van primary | Donker (automatisch) |
| `var(--color-background-hover)` | Lichtgrijs | Donker (automatisch) |
| `rgba(0,0,0,0.05)` | Subtiel grijs | Donker |
| (lege string) | Transparant | Donker |

**Let op:** hex-kleuren zoals `#1a3a5c` worden technisch door de backend ondersteund, maar krijgen GEEN automatische tekst-contrast. Gebruik altijd CSS-variabelen voor correcte theming.

### Afbeelding-afhandeling

Afbeeldingen moeten in de `nl/_media/`-folder staan voor import. Het `src`-veld in afbeelding-widgets bevat alleen de bestandsnaam (bv. `"campus.jpg"`), niet een volledig pad. Tijdens import worden bestanden uit `_media/` naar de media-folder van de pagina gekopieerd.

### People-widget-portabiliteit

People-widgets gebruiken `selectionMode: "filter"` (niet `"manual"`) zodat ze op elke Nextcloud-instantie werken, ongeacht welke gebruikers-accounts er zijn. Ze tonen automatisch beschikbare gebruikers.

## Een showcase importeren

1. Ga naar **Beheerinstellingen → IntraVox → Import**-tab
2. Upload het `{naam}.zip`-bestand
3. Schakel "Bestaande pagina's overschrijven" in bij vervangen
4. Klik **Importeren**
5. De showcase-pagina, navigatie, footer en media worden geïmporteerd

## Nieuwe showcases maken

Bij het maken van een nieuwe showcase:

1. Gebruik alleen CSS-variabelen voor `backgroundColor` (geen hex-kleuren)
2. Plaats afbeeldingen in `nl/_media/`-folder
3. Gebruik `selectionMode: "filter"` voor people-widgets
4. Zorg dat alle widget-`column`-waarden matchen met het `columns`-aantal van de rij
5. Include `export.json` (met content-wrapper) en `nl/home.json` (zonder)
6. Test import op een schone installatie

---

**Laatst bijgewerkt**: 2026-03-07
