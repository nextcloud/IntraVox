# IntraVox editor-gids

Voor content-editors die pagina's in IntraVox maken en onderhouden.

## Vereisten

Om content te bewerken heb je nodig:

- Een Nextcloud-account
- Editor-rechten (toegewezen door je beheerder)
- Toegang tot de relevante IntraVox-secties

## Bewerken — basis

### Edit-modus openen

1. Navigeer naar de pagina die je wilt bewerken
2. Klik op de **Bewerk**-knop (potlood-icoon) in de toolbar
3. De pagina schakelt naar edit-modus

### Edit-modus-interface

```
┌─────────────────────────────────────────────────────────────┐
│  [Opslaan] [Annuleren]                        Edit-modus    │
├─────────────────────────────────────────────────────────────┤
│  ┌──────────────┐  ┌────────────────────────────────────┐   │
│  │   Widget-    │  │                                    │   │
│  │   palet      │  │      Pagina-canvas                 │   │
│  │              │  │                                    │   │
│  │  [Kop]       │  │  [Rij 1]                           │   │
│  │  [Tekst]     │  │  ┌─────────────────────────┐       │   │
│  │  [Beeld]     │  │  │  Widget (bewerkbaar)    │       │   │
│  │  [Links]     │  │  └─────────────────────────┘       │   │
│  │  [Scheider]  │  │                                    │   │
│  │              │  │  [Rij 2]                           │   │
│  │  [Rij +]     │  │  ┌──────────┐ ┌──────────┐         │   │
│  └──────────────┘  │  │ Widget 1 │ │ Widget 2 │         │   │
│                    │  └──────────┘ └──────────┘         │   │
│                    └────────────────────────────────────┘   │
└─────────────────────────────────────────────────────────────┘
```

### Wijzigingen opslaan

- Klik **Opslaan** om je wijzigingen te bewaren
- Klik **Annuleren** om wijzigingen te verwerpen en edit-modus te verlaten
- Wijzigingen zijn pas zichtbaar voor anderen na opslaan

De Opslaan- en Annuleren-knoppen blijven vast bovenaan de pagina tijdens scrollen, ook op lange pagina's.

![Sticky save-toolbar](../screenshots/page-stickysave.gif)

*De toolbar blijft zichtbaar bovenaan tijdens het scrollen door een lange pagina in edit-modus*

### Pagina-locking

Wanneer je een pagina begint te bewerken, lockt IntraVox hem automatisch om te voorkomen dat andere gebruikers tegelijk wijzigingen maken. Andere gebruikers zien wie aan het bewerken is en kunnen pas in edit-modus na jouw opslaan, annuleren, of na het verlopen van de lock.

- Locks verlopen automatisch na **15 minuten** inactiviteit
- Een heartbeat houdt de lock actief tijdens bewerken
- Locks worden vrijgegeven bij opslaan, annuleren, weg-navigeren of tabblad sluiten
- Verloopt je lock (bv. verbinding kwijt)? Dan krijg je een waarschuwing om je werk op te slaan

**IntraVox-beheerders** kunnen een pagina force-unlocken als een lock is blijven hangen (bv. na browser-crash). Zij zien een "Ontgrendelen"-knop naast de lock-indicator.

### Concept- en gepubliceerd-status

Pagina's hebben een status: **Concept** of **Gepubliceerd**. Dit bepaalt wie de pagina kan zien.

![Concept- en gepubliceerd-status in edit-modus](../screenshots/page-draft-published.png)

*In edit-modus staat de Concept/Gepubliceerd-knop in de toolbar. Klik om te wisselen.*

**Hoe het werkt:**

| Status | Zichtbaar voor editors | Zichtbaar voor lezers | In zoeken | In RSS-feed | Via publieke link |
|--------|--------------------------|------------------------|-----------|--------------|---------------------|
| **Gepubliceerd** | Ja | Ja | Ja | Ja | Ja |
| **Concept** | Ja | Nee | Nee | Nee | Nee |

- **Editors** zijn gebruikers met schrijfrechten op de pagina-folder (IntraVox-beheerders, IntraVox-editors, en gebruikers met schrijftoegang via GroupFolder-ACL)
- **Lezers** zijn gebruikers met alleen-lezen-rechten (reguliere IntraVox-gebruikers)
- Een concept-pagina is volledig onzichtbaar voor lezers — verschijnt niet in navigatie, zoekresultaten, page-tree, RSS-feeds of publieke share-links

**Nieuwe pagina's beginnen als Concept.** Wanneer je een nieuwe pagina maakt (leeg of vanuit template), staat hij automatisch op Concept en opent in edit-modus. Zo kun je je pagina opbouwen voor je hem zichtbaar maakt.

**Status wisselen:**

1. Open edit-modus
2. Klik de **Concept**- of **Gepubliceerd**-knop in de toolbar (met oog-icoon)
3. De status wijzigt direct — sla op om toe te passen

**Best practices:**

- Gebruik Concept om nieuwe pagina's of grote updates voor te bereiden vóór publicatie
- Onthoud dat een gepubliceerde pagina op Concept zetten hem direct onzichtbaar maakt voor lezers
- Alleen editors (gebruikers met schrijfrechten) kunnen de pagina-status zien en wijzigen

## Pagina-structuur

### Rijen

Pagina's zijn georganiseerd in rijen. Elke rij kan hebben:

- 1–5 kolommen
- Een achtergrondkleur
- Meerdere widgets
- Inklapbare sectie (met titel, standaard ingeklapt/uitgeklapt)

Pagina's kunnen ook een **header-rij** (full-width banner bovenaan) en optionele **zij-kolommen** (linker- of rechter-sidebar) hebben.

**Rij toevoegen:**

1. Klik "Rij toevoegen" onderaan de pagina
2. Selecteer het aantal kolommen
3. De nieuwe rij verschijnt onderaan

**Rij configureren:**

1. Hover over de rij
2. Klik op het instellingen-icoon
3. Wijzig kolommen of achtergrondkleur

#### Inklapbare secties

Rijen kunnen inklapbaar worden gemaakt, zodat gebruikers content-secties kunnen uit- en inklappen. Handig voor FAQ-pagina's, lange content, of optionele details.

![Instellingen voor inklapbare sectie in edit-modus](../screenshots/row-collapsible.png)

*Edit-modus: activeer "Inklapbare sectie", stel een sectie-titel in, en vink optioneel "Standaard ingeklapt" aan*

**Inklapbare rij opzetten:**

1. Hover over de rij en klik op het instellingen-icoon
2. Vink **Inklapbare sectie** aan
3. Voer een **Sectie-titel** in (verschijnt als klikbare header)
4. Vink optioneel **Standaard ingeklapt** aan om content bij page-load te verbergen

![Inklapbare sectie in view-modus — uitgeklapt (links) en ingeklapt (rechts)](../screenshots/row-collapsible-details.png)

*View-modus: gebruikers klikken op de pijl om de sectie open/dicht te toggelen*

**Best practices:**

- Gebruik beschrijvende sectie-titels zodat gebruikers weten wat te verwachten
- Gebruik "Standaard ingeklapt" voor aanvullende content die niet iedereen nodig heeft
- Houd vaak-bezochte content standaard uitgeklapt

**Praktijkvoorbeeld — FAQ-pagina met meerdere inklapbare secties:**

![Inklapbare secties in een gepubliceerde pagina](../screenshots/Collapsible-sections.png)

*Typische FAQ-pagina: de eerste sectie is uitgeklapt met inhoud, de rest blijven ingeklapt tot ze worden aangeklikt.*

![Meerdere inklapbare rijen in de editor](../screenshots/Collapsible-sectionsSettings.png)

*Edit-modus: stack meerdere inklapbare rijen. Elk heeft eigen titel en "Standaard ingeklapt"-instelling.*

**Een rij dupliceren:**

Je kunt een complete rij dupliceren, inclusief alle kolommen en widgets.

![Dupliceer-knop in rij-controls](../screenshots/row-copy.png)

*Klik op het kopieer-icoon in de rij-controls om de rij te dupliceren*

1. Hover over de rij
2. Klik op het kopieer-icoon (naast het delete-icoon)
3. Een kopie van de rij verschijnt direct eronder, met alle widgets gedupliceerd
4. Bewerk de kopie onafhankelijk — wijzigingen raken de origineel niet

Handig voor pagina's met repeterende layouts, zoals afdelings-cards of FAQ-secties.

**Een rij verwijderen:**

1. Hover over de rij
2. Klik op het delete-icoon
3. Bevestig verwijdering

### Kolommen

Rijen kunnen 1–5 kolommen hebben:

| Layout | Beschrijving |
|--------|--------------|
| 1 kolom | Full-width content |
| 2 kolommen | Split 50/50 |
| 3 kolommen | Drie gelijke kolommen |
| 4 kolommen | Vier gelijke kolommen |
| 5 kolommen | Vijf gelijke kolommen |

### Zij-kolommen

Pagina's kunnen optionele zij-kolommen hebben:

- Linker-sidebar
- Rechter-sidebar

In te schakelen via pagina-instellingen.

## Widgets

Widgets zijn de bouwblokken van pagina-content.

![Beschikbare widgets](../screenshots/widgets.png)

*Het widget-palet toont alle beschikbare widget-typen*

### Widgets toevoegen

1. Klik in het widget-palet op het widget-type
2. Sleep het naar de gewenste plek op de pagina
3. Of klik om toe te voegen aan de eerste beschikbare kolom

### Widget-typen

#### Kop

Titels en sectie-headers.

**Opties:**

- Niveau: H1 (grootst) tot H6 (kleinst)
- Inhoud: de kop-tekst

**Best practices:**

- Gebruik H1 voor pagina-titel (één per pagina)
- Gebruik H2 voor hoofdsecties
- Gebruik H3–H4 voor subsecties

#### Tekst

Rich-text content met opmaak.

**Opmaak-opties:**

- **Vet** (`Ctrl+B`)
- *Italic* (`Ctrl+I`)
- Onderstrepen (`Ctrl+U`)
- Bullet-lijsten
- Genummerde lijsten
- Links

**Dummy-tekst-generator (easter egg):**

Heb je placeholder-tekst nodig tijdens ontwerpen? Typ een speciaal commando op een lege regel en druk **Enter** voor dummy-content — geïnspireerd op Microsoft Word's `=rand()`.

![Dummy-tekst-generator — Flauwe grappen](../screenshots/dadjokes.gif)

*Flauwe grappen met rijke opmaak: koppen, genummerde lijsten, vet voor de setup en cursief voor de clou*

![Dummy-tekst-generator — Lorem Ipsum](../screenshots/lorem-demo.gif)

*Lorem Ipsum-showcase: koppen, paragrafen, blockquotes, lijsten, tabellen en inline-marks*

| Commando | Beschrijving | Voorbeeld |
|----------|--------------|-----------|
| `=dadjokes()` | Genereer flauwe grappen | `=dadjokes(3,5)` → 3 secties van 5 grappen |
| `=lorem()` | Rich Lorem Ipsum-showcase | `=lorem(6,3)` → 6 secties met gevarieerde opmaak |

**Parameters**: `=(commando)(secties, items)` — beide optioneel, standaard 3 secties van 3 items. Maximum is 20 voor beide waarden.

**Hoe het werkt:**

1. Klik in een tekst-widget in edit-modus
2. Typ `=dadjokes()` of `=lorem()` op een lege regel
3. Druk **Enter**
4. Het commando wordt vervangen door gegenereerde inhoud

**Rich opmaak:**

Beide commando's genereren rijk opgemaakte content die de mogelijkheden van het tekst-widget showcased:

`=dadjokes()` genereert:

- **Sectie-koppen** (bv. "Flauwe Grappen #1", "Flauwe Grappen #2")
- **Genummerde lijsten** met elke grap als list-item
- **Vet** voor de setup en *italic* voor de clou

`=lorem()` roteert door 6 opmaak-patronen om alle widget-features te demonstreren:

| Patroon | Gebruikte elementen |
|---------|---------------------|
| Kop + paragraaf | `<h2>`-kop, **vet** en *italic* tekst |
| Blockquote | Ingesprongen quote-blok |
| Bullet-lijst | `<ul>` met **vet**-fragmenten |
| Tabel | 3-koloms tabel met header, vet voor categorieën, italic voor statussen |
| Genummerde lijst | `<h3>`-kop + `<ol>` met *italic* |
| Mixed inline | `code`, <u>onderstrepen</u>, ~~doorhalen~~, **vet**, *italic* |

Met `=lorem(6,3)` krijg je één van elk patroon — perfect om het volledige tekst-widget aan gebruikers te demonstreren.

**Meertalige ondersteuning:**

Beide commando's passen zich automatisch aan de Nextcloud-taal van de gebruiker aan:

| Taal | Flauwe-grappen-kop | Lorem-koppen |
|------|---------------------|---------------|
| Engels | Dad Jokes | Section, Key Points, Overview, Steps, Additional Notes |
| Nederlands | Flauwe Grappen | Sectie, Kernpunten, Overzicht, Stappen, Aanvullende Opmerkingen |
| Duits | Flachwitze | Abschnitt, Kernpunkte, Übersicht, Schritte, Zusätzliche Hinweise |
| Frans | Blagues de Papa | Section, Points Clés, Aperçu, Étapes, Notes Complémentaires |

Elke taal heeft eigen collectie van ~80 grappen. Tabel-kolom-headers en status-labels zijn ook gelokaliseerd. Geen vertaling beschikbaar voor jouw taal? Dan wordt Engels als fallback gebruikt.

Alle content zit in IntraVox ingebakken (geen internet-verbinding nodig) en wordt elke keer willekeurig geschud, dus krijg je telkens andere content.

#### Afbeelding

Foto's, diagrammen en graphics met optionele klikbare links.

**Opties:**

- Afbeelding-bron: kies uit IntraVox-media-folder of upload nieuwe
- Alt-tekst: beschrijving voor toegankelijkheid
- Object-fit: cover, contain of auto
- Link (optioneel): maak de afbeelding klikbaar
  - Link naar pagina: navigeer naar een IntraVox-pagina
  - Externe URL: open externe website

**Afbeeldings-groottes:**

- Klein: thumbnail-formaat
- Middel: halve breedte
- Groot: volle breedte
- Custom: specificeer pixels-breedte

**Best practices:**

- Gebruik beschrijvende alt-tekst
- Optimaliseer afbeeldingen vóór upload (< 500 KB)
- Gebruik passende aspect-ratio's
- Gebruik klikbare afbeeldingen voor navigatie-cards en banners

#### Video

Embed video's van externe platforms of upload lokale video's.

![Video-widget-editor met platform-detectie](../screenshots/videowidget.png)

*Plak een YouTube/Vimeo/PeerTube-URL en IntraVox detecteert het platform automatisch. Schakel naar **Lokaal bestand** voor een MP4-upload.*

**Ondersteunde platforms:**

- YouTube (privacy-enhanced modus)
- Vimeo
- PeerTube-instances
- Lokale video-upload (MP4)

**Opties:**

- Video-URL: plak een video-URL van een ondersteund platform
- Upload: upload een video-bestand naar Nextcloud-opslag
- Titel: weergave-titel boven de video
- Autoplay: video automatisch starten (gedempt)
- Loop: herhaal video bij einde

![Video-widget gerenderd op een gepubliceerde pagina](../screenshots/videowidgethomepage.png)

*Een YouTube-embed naast andere widgets op een gepubliceerde homepage.*

**Geblokkeerde domeinen:**

Heeft de beheerder het video-platform niet op de whitelist staan? Dan toont het widget een waarschuwings-placeholder in plaats van de player:

![Geblokkeerd video-domein-placeholder](../screenshots/videowidgetblocked.png)

Vraag je beheerder het platform aan te zetten via **Instellingen → IntraVox → Video Services**.

**Best practices:**

- Gebruik waar mogelijk privacy-vriendelijke platforms
- Houd geüploade video's onder 100 MB voor performance
- Voeg altijd een beschrijvende titel toe
- Check dat het video-domein is whitelisted door je beheerder

#### Links

Verzamelingen van links weergegeven als kaarten of lijst.

**Opties:**

- Titel: link-titel
- Beschrijving: korte beschrijving
- URL: bestemming (pagina of extern)
- Icoon: optioneel icoon
- Target: zelfde venster of nieuw tabblad
- Kolommen: 1–4 kolommen voor card-layout

**Best practices:**

- Groepeer gerelateerde links samen
- Gebruik beschrijvende titels
- Geef externe links duidelijk aan

#### Bestand

Link naar een downloadbaar bestand.

**Opties:**

- Pad: bestandspad binnen IntraVox-opslag
- Naam: weergave-naam voor de link

**Best practices:**

- Gebruik beschrijvende bestandsnamen
- Houd bestandspaden georganiseerd in mappen

#### Scheider

Visuele separators tussen content-secties.

**Opties:**

- Stijl: solid, dashed of transparent
- Kleur: lijn-kleur (of inherit)
- Hoogte: lijn-dikte of ruimte-hoogte

#### Spacer

Voegt verticale ruimte toe tussen content-secties.

**Opties:**

- Hoogte: 10–200 pixels (standaard: 20)

#### Nieuws

Dynamische nieuwsfeed die de laatste pagina's toont.

**Layout-opties:**

- Lijst: verticale lijst van artikelen
- Grid: card-grid met configureerbare kolommen
- Carousel: auto-scrollende slider

**Opties:**

- Limiet: maximaal aantal artikelen (standaard: 5)
- Toon afbeelding, datum, samenvatting: zichtbaarheid wisselen
- Samenvatting-lengte: tekens om te tonen
- Sorteer op: gewijzigd-datum, aangemaakt-datum of titel
- Autoplay-interval (alleen carousel): seconden tussen slides

Voor uitgebreide documentatie: zie [News-widget](../features/news-widget.md).

#### Mensen

Gebruikers-directory-widget dat team-leden toont.

**Layout-opties:**

- Card: profile-cards met avatar en details
- Lijst: compacte lijst-weergave
- Grid: avatar-grid met configureerbare kolommen

**Selectie-modi:**

- Filter: toon gebruikers die filter-criteria matchen (aanbevolen voor portabiliteit)
- Handmatig: selecteer specifieke gebruikers op ID

**Opties:**

- Kolommen: 1–4 kolommen
- Limiet: maximaal aantal te tonen gebruikers
- Toon velden: avatar, naam, rol, afdeling, telefoon, e-mail etc. aan/uit
- Sorteer op: weergave-naam, laatste login, etc.

Voor uitgebreide documentatie: zie [People-widget](../features/people-widget.md).

#### Agenda

Toon aankomende afspraken uit gedeelde Nextcloud-agenda's met gekleurde datum-badges en responsieve grid-layout.

**Opties:**

- Agenda's: selecteer één of meer (samengevoegde view met kleur-coding)
- Datum-bereik: toekomst (deze week tot volgend jaar) of verleden (vorige week tot 3 maanden terug)
- Limiet: maximaal aantal te tonen events (1–20)
- Toon tijd: tijdsvermelding aan/uit
- Toon locatie: locatie aan/uit

**Features:**

- Terugkerende afspraken worden automatisch geëxpandeerd naar individuele instances
- Events zijn klikbaar en openen in Nextcloud Calendar
- Layout past zich automatisch aan: 1 kolom in zij-kolommen, 2–3 kolommen in bredere gebieden

Voor uitgebreide documentatie: zie [Calendar-widget](../features/calendar-widget.md).

### Widgets bewerken

1. Klik op een widget om te selecteren
2. Gebruik de toolbar of het properties-paneel om te bewerken
3. Wijzigingen verschijnen direct

### Widgets verplaatsen

**Drag-and-drop:**

1. Klik en houd een widget vast
2. Sleep naar de nieuwe positie
3. Laat los om te plaatsen

**Tussen kolommen:** sleep widgets tussen kolommen in dezelfde rij.

**Tussen rijen:** sleep widgets naar andere rijen.

### Widgets verwijderen

1. Selecteer het widget
2. Klik op het delete-icoon (prullenbak)
3. Widget wordt direct verwijderd

## Werken met media

### Afbeeldingen uploaden

**Via de editor (aanbevolen):**

1. Voeg een afbeelding-widget toe of bewerk een bestaand
2. Klik "Upload" in de afbeelding-editor
3. Selecteer een afbeelding van je computer
4. De afbeelding wordt automatisch naar de `_media/`-folder geüpload

**Via Nextcloud Files:**

1. Open Nextcloud Files
2. Navigeer naar IntraVox-folder → jouw taal → `_media/`
3. Upload je afbeelding
4. Keer terug naar IntraVox en selecteer de afbeelding

**Bestaande afbeelding selecteren — drie tabs:**

![Selecteren uit de pagina's eigen media-folder](../screenshots/Page-media.png)

*De afbeelding-picker heeft drie tabs: **Upload** voor nieuwe bestanden, **Pagina-media** voor afbeeldingen in de `_media/`-folder van de huidige pagina, en **Gedeelde bibliotheek** voor site-brede assets.*

![Gedeelde-bibliotheek-tab met site-brede assets](../screenshots/Shared-library.png)

*De **Gedeelde bibliotheek** bewaart herbruikbare assets zoals achtergronden, iconen en logo's die beschikbaar moeten zijn op alle pagina's.*

### Video's uploaden

**Lokale video-upload:**

1. Voeg een video-widget toe
2. Klik "Video uploaden"
3. Selecteer een MP4-bestand van je computer
4. De video wordt geüpload naar de `_media/`-folder

**Externe video:**

1. Voeg een video-widget toe
2. Plak een video-URL (YouTube, Vimeo, PeerTube)
3. De video wordt embedded vanaf het externe platform

### Media-richtlijnen

| Type | Aanbevolen grootte | Format |
|------|---------------------|--------|
| Hero-afbeeldingen | 1920×600 px | JPG |
| Content-afbeeldingen | 800×600 px | JPG/PNG |
| Iconen | 64×64 px | PNG/SVG |
| Logo's | 200×100 px | PNG/SVG |
| Video's | 1920×1080 px max | MP4 (H.264) |

### Media-optimalisatie

Voor je uploadt:

1. Schaal naar passende afmetingen
2. Comprimeer om bestandsgrootte te verkleinen
3. Gebruik JPG voor foto's, PNG voor graphics
4. Houd afbeelding-bestanden onder 500 KB
5. Houd video-bestanden onder 100 MB voor beste performance

## Navigatie

### Paginastructuur

Het **Paginastructuur**-paneel (te openen via de knop in de navigatiebalk) toont al je echte pagina's in een boom. In de standaardweergave blader je door de hiërarchie en klik je een pagina aan om die te openen.

Zet je **Structuur beheren** aan (beschikbaar waar je bewerkrechten hebt), dan wordt elke rij een set knoppen om de echte pagina's te ordenen — dit is iets anders dan **Navigatie bewerken**, dat alleen de links in de navigatiebalk en hun volgorde wijzigt.

![Paginastructuur in beheer-modus, met knoppen per rij en de twee toelichtingsbanners](../screenshots/PageStructure-edit.png)

*Beheer-modus: elke pagina heeft knoppen voor verplaatsen, herordenen, als-startpagina-instellen, kopiëren en verwijderen. De huidige startpagina (badge "Home") kan niet verplaatst of verwijderd worden.*

#### Wat je per pagina kunt doen

- **Herordenen** — de pijltjes omhoog (↑) en omlaag (↓) verplaatsen een pagina tussen z'n broers en zussen. De pijltjes zijn uitgeschakeld boven- en onderaan een lijst.
- **Naar andere pagina verplaatsen** — de map-pijl opent een inline-paneel waar je een nieuwe ouder kiest, of zet **Naar het hoogste niveau** aan om de pagina naar de root te promoveren. De sub-pagina's gaan mee. Een pagina kan niet in zichzelf of een eigen sub-pagina worden geplaatst, en de maximale nesting-diepte (5 niveaus) wordt gerespecteerd.
- **Als startpagina instellen** — het huis-icoon maakt een pagina de landingspagina voor de huidige taal. Alleen **pagina's op het hoogste niveau** kunnen de startpagina zijn; wil je een sub-pagina als startpagina, verplaats die dan eerst naar het hoogste niveau.
- **Kopiëren** — dupliceert de pagina als een nieuw **Concept** met de titel "… (copy)", inclusief media, zodat je die kunt aanpassen zonder het origineel te raken.
- **Verwijderen** — verwijdert de pagina na een bevestiging.

#### De startpagina is beschermd

De huidige startpagina draagt een **Home**-badge en kan niet verplaatst of verwijderd worden — wijs eerst een andere pagina als startpagina aan, daarna wordt het origineel een gewone pagina die je kunt verplaatsen of verwijderen. Zie [De startpagina instellen](#de-startpagina-instellen) hieronder.

> Al deze knoppen respecteren je rechten: je ziet ze alleen voor pagina's die je mag bewerken, en de server dwingt dezelfde regels af — er kan dus niets buiten je rechten worden herordend, verplaatst, gekopieerd of verwijderd.

### De startpagina instellen

Elke pagina op het hoogste niveau kan de startpagina voor een taal zijn. Klik in **Structuur beheren** op het huis-icoon van de gewenste pagina; die wordt meteen de landingspagina op `…/apps/intravox/`. De wijziging is een verwijzing — de pagina wordt nooit hernoemd of verplaatst — dus bestaande links blijven werken.

### Navigatie bewerken

1. Klik **Navigatie bewerken** in de toolbar (vereist beheerder-rechten)
2. De navigatie-editor opent

### Navigatie-structuur

```
Navigatie
├── Home (link naar homepage)
├── Over
│   ├── Ons bedrijf
│   └── Team
├── Afdelingen (dropdown)
│   ├── HR
│   ├── Sales
│   └── IT
└── Externe links
    └── Bedrijfssite (externe URL)
```

### Navigatie-items toevoegen

1. Klik "Item toevoegen"
2. Voer titel in
3. Selecteer bestemming:
   - Pagina: link naar IntraVox-pagina (op uniqueId)
   - URL: externe website
   - Geen: alleen ouder-menu
4. Stel target in (zelfde venster of nieuw tabblad)
5. Sla navigatie op

### Navigatie-typen

**Megamenu**: groot dropdown dat alle items tegelijk toont.
**Dropdown**: cascading menu's die uitklappen bij hover.

### Best practices

- Houd navigatie-diepte tot maximaal 5 niveaus
- Gebruik heldere, korte labels
- Groepeer gerelateerde items samen
- Test op mobiele apparaten

## Footer

### Footer bewerken

1. Klik **Footer bewerken** in de toolbar
2. Voer footer-content in via Markdown
3. Sla wijzigingen op

### Footer-inhoud

Een typische footer bevat:

- Copyright-vermelding
- Links naar juridische pagina's
- Contact-informatie

Voorbeeld:

```markdown
© 2025 Bedrijfsnaam — [Contact](#) | [Privacy](#) | [Help](#)
```

## Nieuwe pagina's maken

Nieuwe pagina's worden altijd aangemaakt als **Concept** en openen direct in edit-modus, zodat je meteen kunt beginnen met content-bouwen. De pagina is onzichtbaar voor lezers totdat je de status op Gepubliceerd zet en opslaat.

### Vanuit navigatie

1. Bewerk navigatie
2. Voeg nieuw item toe met gewenste titel
3. Laat uniqueId leeg
4. Sla navigatie op
5. Navigeer naar het nieuwe item
6. IntraVox maakt de pagina automatisch aan (als Concept, in edit-modus)

### Pagina-bestanden

Pagina's worden opgeslagen als JSON-bestanden:

```
IntraVox/
└── nl/
    └── sectie/
        └── nieuwe-pagina.json
```

## Best practices

### Content-richtlijnen

1. **Heldere koppen**: gebruik beschrijvende koppen
2. **Korte paragrafen**: breek lange tekst op
3. **Visuele hiërarchie**: gebruik consistente styling
4. **Call to action**: leid gebruikers naar volgende stappen
5. **Verse content**: update regelmatig

### Consistentie

1. Gebruik dezelfde kop-niveaus over pagina's
2. Houd afbeelding-groottes consistent
3. Volg je organisatie-stijlgids
4. Gebruik goedgekeurde terminologie

### Toegankelijkheid

IntraVox voldoet aan [WCAG 2.1 niveau AA](accessibility.md). Als editor kun je toegankelijkheid bewaken:

1. Voeg altijd alt-tekst toe aan afbeeldingen (beschrijft de afbeelding voor screenreaders)
2. Gebruik juiste kop-hiërarchie (H1 → H2 → H3, sla geen niveaus over)
3. Zorg voor voldoende kleurcontrast
4. Maak link-tekst beschrijvend ("Lees het beleid" niet "Klik hier")
5. Voeg titels toe aan video-widgets

### Performance

1. Optimaliseer afbeeldingen vóór upload
2. Overlaad pagina's niet met widgets
3. Gebruik passende afbeelding-groottes
4. Test laadtijden

## Problemen oplossen

### Kan edit-modus niet openen

- Check of je editor-rechten hebt
- Vernieuw de pagina
- Neem contact op met beheerder

### Wijzigingen worden niet opgeslagen

- Check internet-verbinding
- Probeer opnieuw na een paar seconden
- Check op validatie-fouten
- Neem contact op met IT-support

### Afbeeldingen verschijnen niet

- Verifieer dat de afbeelding naar de `_media/`-folder is geüpload
- Check of het afbeelding-pad klopt
- Zorg dat het afbeelding-format ondersteund is
- Probeer opnieuw te selecteren

### Video's spelen niet af

- Check of de video-URL van een whitelisted platform komt
- Lokale video's: verifieer dat het bestand correct is geüpload
- Externe video's: check dat de URL klopt en publiek toegankelijk is
- Geblokkeerd? Neem contact op met je beheerder om het video-domein te whitelisten

### Widget werkt niet

- Check widget-configuratie
- Probeer te verwijderen en opnieuw toe te voegen
- Wis browser-cache
- Meld issue aan beheerder

## Sneltoetsen

| Sneltoets | Actie |
|-----------|-------|
| `Ctrl+S` | Pagina opslaan |
| `Ctrl+B` | Vette tekst |
| `Ctrl+I` | Italic tekst |
| `Ctrl+U` | Onderstreepte tekst |
| `Escape` | Cancel edit / sluit dialoog |
| `Delete` | Verwijder geselecteerde widget |

## Hulp krijgen

- **Technische problemen**: neem contact op met IT-support
- **Content-vragen**: vraag je content-lead
- **Feature-verzoeken**: dien in via het proces van je organisatie
- **Documentatie**: zie andere gidsen in deze documentatie
