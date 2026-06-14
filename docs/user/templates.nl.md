# Pagina-templates

Pagina-templates laten je snel nieuwe pagina's maken met voor-gebouwde layouts en content-structuren. IntraVox bevat 7 professionele templates die automatisch tijdens setup geïnstalleerd worden, en je kunt je eigen custom templates maken.

## Features

- **Visuele preview-kaarten**: bekijk de layout-structuur voor je een template kiest
- **Voor-gebouwde layouts**: professionele templates met kolommen, header-rijen, sidebars en inklapbare secties
- **Widget-variatie**: templates gebruiken diverse widgets, waaronder kop, tekst, afbeelding, video, nieuws, links en scheider
- **Stock-afbeeldingen**: templates bevatten placeholder-afbeeldingen die naar je nieuwe pagina worden gekopieerd
- **Meertalige ondersteuning**: template-namen en -beschrijvingen zijn vertaald (NL, EN, DE, FR)
- **Custom templates**: sla elke pagina op als herbruikbaar template

## Een pagina maken vanuit een template

1. Navigeer naar de folder waar je de nieuwe pagina wilt maken
2. Klik **+ Nieuwe pagina** in de navigatie
3. Selecteer een template uit de galerij

![Template-selectie-dialoog](../screenshots/Templates-Select-template.png)

4. Voer een titel in voor je nieuwe pagina
5. Klik **Aanmaken**

![Nieuwe-pagina-modaal](../screenshots/Templates-New-page.png)

De pagina wordt aangemaakt met alle template-content, layout en afbeeldingen gekopieerd naar de nieuwe locatie.

![Pagina aangemaakt vanuit template](../screenshots/Templates-Page-created.png)

## Standaard-templates

IntraVox bevat 7 professionele templates:

### Afdeling

Een afdelings-homepage met team-info, services-overzicht en resources.

| Sectie | Inhoud |
|--------|--------|
| Header | Hero-afbeelding met welkomstboodschap |
| Services | 3-koloms grid met service-beschrijvingen |
| Resources | Document-links en contact-informatie |
| Navigatie | Links naar andere afdelingen |

**Complexiteit**: gemiddeld | **Kolommen**: 3 | **Widgets**: 12

### Event

Een event-pagina met programma-schema, sprekersprofielen en aanmeldingsinformatie.

| Sectie | Inhoud |
|--------|--------|
| Header | Event-banner met titel |
| Details | Datum, tijd, locatie met promotie-video |
| Programma | Inklapbare secties voor het schema |
| Sprekers | 3-koloms sprekersprofielen met foto's |
| Aanmelden | Call-to-action met aanmeldlink |

**Complexiteit**: geavanceerd | **Kolommen**: 3 | **Widgets**: 15+

### Knowledge base

Een documentatie-hub met categorieën, FAQ-secties en populaire artikelen.

| Sectie | Inhoud |
|--------|--------|
| Header | Knowledge-base-titel en introductie |
| Categorieën | 3-koloms grid (Handleidingen, FAQ, Beleid) |
| Populaire artikelen | News-widget met meest bekeken content |
| FAQ | Inklapbare FAQ-secties |
| Contact | Helpdesk-contact-info |

**Complexiteit**: geavanceerd | **Kolommen**: 3 | **Widgets**: 15+

### Landingspagina

Een visuele homepage met hero-sectie, features, sidebar en call-to-action.

| Sectie | Inhoud |
|--------|--------|
| Hero | Full-width hero-afbeelding met kop |
| Highlights | Nieuws-carousel met recente content |
| Features | 3-koloms feature-highlights |
| Over | 2-koloms over-sectie met afbeelding |
| Sidebar | Quick links en aankondigingen |
| CTA | Call-to-action-sectie |

**Complexiteit**: geavanceerd | **Kolommen**: 3 | **Widgets**: 18+

### Nieuwsartikel

Een single-news-article-layout met hero-afbeelding, content-gebied en gerelateerde links.

| Sectie | Inhoud |
|--------|--------|
| Header | Artikel-hero-afbeelding |
| Titel | Artikel-kop |
| Content | Hoofdtekst van het artikel |
| Afbeeldingen | 2-koloms ondersteunende afbeeldingen met onderschriften |
| Gerelateerd | Links naar gerelateerde artikelen |

**Complexiteit**: eenvoudig | **Kolommen**: 2 | **Widgets**: 8

### Nieuws-hub

Een centrale nieuws-pagina met carousel, uitgelichte artikelen en categorie-navigatie.

| Sectie | Inhoud |
|--------|--------|
| Header | Hero-banner met nieuws-titel |
| Carousel | News-widget met roterende artikelen |
| Categorieën | 4-koloms categorie-navigatie |
| Uitgelicht | 2-koloms uitgelichte artikelen |
| Links | Quick links naar nieuws-secties |

**Complexiteit**: gemiddeld | **Kolommen**: 4 | **Widgets**: 10

### Project

Een projectmanagement-pagina met statusupdates, mijlpalen, teamleden en documenten.

| Sectie | Inhoud |
|--------|--------|
| Header | Projecttitel en samenvatting |
| Status | Project-status met quick-links |
| Doelstellingen | Inklapbare project-doelen |
| Mijlpalen | Inklapbare tijdlijn |
| Team | 3-koloms teamleden-profielen |
| Documenten | Document-links |

**Complexiteit**: gemiddeld | **Kolommen**: 3 | **Widgets**: 12

## Template-preview-kaarten

Elke template toont een visuele preview-kaart met:

- **Layout-schema**: SVG-visualisatie van de kolom-structuur
- **Header-rij-indicator**: toont of het template een header-sectie heeft
- **Sidebar-indicator**: toont of het template een zij-kolom heeft
- **Inklapbaar-indicator**: toont of het template inklapbare secties gebruikt
- **Widget-badges**: iconen die tonen welke widget-typen worden gebruikt
- **Complexiteit-badge**: eenvoudig, gemiddeld of geavanceerd
- **Statistieken**: kolom-aantal en totaal aantal widgets

## Custom templates maken

Je kunt elke bestaande pagina als template opslaan:

1. Open de pagina die je als template wilt gebruiken
2. Klik op de optie **Opslaan als template** in het pagina-menu
3. De pagina wordt opgeslagen in de `_templates`-folder van jouw taal
4. Het template verschijnt in het "Nieuwe pagina aanmaken"-dialoog

### Template-opslag

Templates worden opgeslagen in de `_templates`-folder per taal:

```
IntraVox/
├── en/
│   └── _templates/
│       ├── department/
│       ├── event/
│       └── my-custom-template/
├── nl/
│   └── _templates/
│       └── ...
```

## Beheerder-notities

### Standaard-templates installeren

Standaard-templates worden automatisch geïnstalleerd via:

```bash
sudo -u www-data php occ intravox:setup
```

Het setup-commando:

1. Maakt `_templates`-folders aan per taal
2. Kopieert de 7 standaard-templates
3. Kopieert template-afbeeldingen naar de `_media`-folder van elk template

### Template-vereisten

Een geldig template moet:

- In een `_templates`-folder staan
- Een `page.json`-bestand hebben met `"isTemplate": true`
- Verwezen afbeeldingen in een `_media`-submap bevatten

### Bestaande templates overslaan

Het setup-commando slaat templates over die al bestaan. Om een standaard-template opnieuw te installeren:

1. Verwijder de template-folder uit `_templates`
2. Run `occ intravox:setup` opnieuw

## Vertalingen

Template-titels en -beschrijvingen worden automatisch vertaald op basis van de taalinstelling van de gebruiker. Translation-keys volgen dit patroon:

- Titel: `template_{template-id}_title`
- Beschrijving: `template_{template-id}_description`

Voor custom templates zonder vertaling wordt de originele titel uit `page.json` getoond.
