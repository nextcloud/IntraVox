# IntraVox gebruikersgids

Welkom bij IntraVox! Deze gids helpt je navigeren en je organisatie-intranet gebruiken.

## Aan de slag

### IntraVox openen

1. Log in op Nextcloud
2. Klik op het IntraVox-icoon in de bovenste menubalk
3. Je ziet de homepage van het intranet

### Interface-overzicht

```
┌─────────────────────────────────────────────────────────────┐
│  [Logo]    Navigatiemenu                       [Taal] 🔍    │
├─────────────────────────────────────────────────────────────┤
│                                                             │
│                     Pagina-inhoud                           │
│                                                             │
│  ┌─────────────────────────────────────────────────────┐   │
│  │                    Hoofdgebied                        │  │
│  │  Koppen, tekst, afbeeldingen, links...               │  │
│  └─────────────────────────────────────────────────────┘   │
│                                                             │
├─────────────────────────────────────────────────────────────┤
│                        Footer                               │
└─────────────────────────────────────────────────────────────┘
```

## Navigatie

### Hoofdmenu

De hoofdnavigatie verschijnt bovenaan elke pagina:

- **Menu-items**: klik om naar verschillende secties te navigeren
- **Dropdowns**: hover of klik op items met pijlen voor submenu's
- **Megamenu**: sommige intranetten gebruiken een groot dropdown met alle opties

![Megamenu-navigatie](../screenshots/megamenu.png)

*Megamenu toont alle navigatie-opties in één oogopslag*

### Breadcrumbs

Onder de navigatie tonen breadcrumbs je huidige locatie:

```
Home > Afdelingen > HR > Beleid
```

Klik op elke breadcrumb om terug te gaan naar dat niveau.

### Zijbalk-navigatie

Sommige pagina's hebben een zijbalk voor snelle toegang tot gerelateerde content.

## Taalkeuze

Als je intranet meerdere talen ondersteunt:

1. Zoek de taalkiezer (meestal rechtsboven)
2. Klik op je voorkeurstaal
3. De pagina-inhoud wisselt naar die taal

Beschikbare talen hangen af van de opzet van je organisatie.

## Content lezen

### Pagina-layouts

Pagina's kunnen verschillende layouts hebben:

- **Eén kolom**: content over de volle breedte
- **Twee kolommen**: content naast elkaar
- **Drie kolommen**: meerdere content-gebieden

### Content-typen

Je komt verschillende content-typen tegen:

| Type | Beschrijving |
|------|--------------|
| Koppen | Sectie-titels (verschillende groottes) |
| Tekst | Paragrafen, lijsten, opgemaakte tekst |
| Afbeeldingen | Foto's, diagrammen, screenshots (kunnen klikbaar zijn) |
| Video's | Embedded video's van YouTube, Vimeo, PeerTube of lokale uploads |
| Links | Snelkoppelingen naar andere pagina's of externe sites |
| Scheidingslijnen | Visuele separators tussen secties |

### Afbeeldingen

- Klik om groter te bekijken (indien ingeschakeld)
- Sommige zijn klikbaar en navigeren naar andere pagina's
- Klikbare afbeeldingen tonen een cursor-wijziging bij hover
- Afbeeldingen kunnen onderschriften hebben

### Video's

- Klik op de play-knop om af te spelen
- Sommige autoplay'en (muted), afhankelijk van instellingen
- Gebruik video-controls voor pauzeren, scrubben, volume
- Sommige openen in fullscreen

### Links

- **Tekstlinks**: onderstreepte tekst, klik om te navigeren
- **Link-cards**: dozen met titel, beschrijving en icoon
- **Externe links**: openen in een nieuw tabblad (icoon)

## Zoeken

1. Klik op het zoek-icoon (🔍) in de navigatie
2. Typ je zoekterm
3. Druk Enter of klik Zoeken
4. Blader door de resultaten

**Tips**:

- Gebruik specifieke trefwoorden voor betere resultaten
- Zoekt in pagina-titels en -inhoud
- Resultaten tonen relevantie-scores

## Pagina-informatie

Sommige pagina's tonen metadata: **Titel**, **Laatst gewijzigd**, **Gewijzigd door**.

Klik op het info-icoon om te zien:

- Pagina-eigenschappen
- Wijzigingsgeschiedenis
- Gerelateerde pagina's

Als je beheerder de [MetaVox](https://apps.nextcloud.com/apps/metavox)-app heeft geïnstalleerd, toont het zijpaneel ook een **MetaVox**-tab met custom metadata — gevoeligheid, afdeling, status, custom datums.

![MetaVox-zijbalk op een IntraVox-pagina](../screenshots/IntraVox-MetaVox.png)

*De MetaVox-tab in het zijpaneel toont team-folder metadata (links) en per-pagina custom velden (rechts) zonder de pagina te verlaten.*

## Veelvoorkomende taken

### Informatie vinden

1. **Gebruik navigatie**: blader door menustructuur
2. **Gebruik zoekfunctie**: zoek op specifieke onderwerpen
3. **Gebruik breadcrumbs**: navigeer terug naar bovenliggende secties

### Terug naar de homepage

- Klik op het logo linksboven
- Of klik "Home" in de navigatie

### Een pagina printen

1. Navigeer naar de pagina
2. Gebruik de print-functie van je browser (`Ctrl+P` / `Cmd+P`)
3. Pagina's zijn print-geoptimaliseerd

### Een pagina delen

1. Kopieer de URL uit de adresbalk
2. Deel de link met collega's
3. Zij zien de pagina (mits toegang)

## RSS-feed

IntraVox kan een persoonlijke RSS-feed genereren zodat je pagina-updates kunt volgen in je RSS-reader (Nextcloud News, Feedly, etc.).

![RSS-feed menu-optie](../screenshots/rss-settings1.png)

*Toegang tot RSS-feed via het pagina-menu (drie puntjes)*

### Je feed instellen

1. Klik op het **drie-puntjes-menu** (⋯) rechtsboven op een IntraVox-pagina
2. Kies **RSS-feed**
3. Configureer:
   - **Feed-scope**: "Mijn taal" of "Alle talen"
   - **Max items**: 10, 20, 30 of 50
4. Klik **Genereer feed-URL**

![RSS-feed instellingen-dialoog](../screenshots/rss-settings2.png)

### Je feed gebruiken

1. Kopieer de gegenereerde feed-URL
2. Open je RSS-reader
3. Voeg een nieuwe feed toe en plak de URL
4. Je reader toont IntraVox-pagina-updates met titels, content-previews en afbeeldingen

![RSS-feed in Nextcloud News](../screenshots/rss-result.png)

### Feed-features

- **Persoonlijke token**: elke feed-URL bevat een veilig persoonlijk token
- **Pagina-afbeeldingen**: feed-items bevatten afbeeldingen als thumbnails en inline
- **Rijke content**: volledige pagina-inhoud via `content:encoded`
- **Conditional requests**: `ETag` en `If-Modified-Since` voor efficiënt polling
- **Toegangscontrole**: alleen pagina's waarvoor je rechten hebt

### Feed beheren

- **Wijzigingen opslaan**: pas scope/limiet aan en klik "Opslaan"
- **Regenereren**: nieuwe feed-URL (oude wordt ongeldig)
- **Intrekken**: token verwijderen — URL wordt ongeldig

### Beveiliging

Je feed-URL bevat een persoonlijk token. Iedereen met deze link kan je feed lezen. Niet publiek delen.

### Feed uitgeschakeld door beheerder

Als je beheerder publieke link-deling heeft uitgeschakeld, zijn RSS-feeds niet beschikbaar. Het instellingen-dialoog toont een foutmelding.

![RSS-feed uitgeschakeld](../screenshots/rss-disabled.png)

## Mobiel gebruik

IntraVox werkt op mobiele apparaten:

- Navigatie collapt naar een menu-knop (☰)
- Tap de menu-knop om navigatie te zien
- Content past zich aan schermgrootte aan
- Veeg om door content te scrollen

## Toegankelijkheid

IntraVox voldoet aan [WCAG 2.1 niveau AA](accessibility.md), zoals vereist door de Wet Digitale Overheid voor overheidsintranetten:

- Toetsenbordnavigatie (Tab, Enter, Escape, pijltjes)
- Screenreader-compatibel
- Skip-to-content-link
- Hoog contrast met Nextcloud dark mode
- Schaalbare tekst (browser-zoom)
- Reduced-motion-ondersteuning

### Sneltoetsen

| Toets | Actie |
|-------|-------|
| Tab | Naar volgend interactief element |
| Enter | Activeer knoppen en links |
| Escape | Sluit menu's en dialogen |
| Pijltjes | Navigeer binnen menu's |

## Problemen oplossen

**Pagina laadt niet**: check internet, vernieuw (`F5`/`Ctrl+R`), wis browser-cache, neem contact op met IT.
**Toegang geweigerd**: je hebt mogelijk geen rechten. Neem contact op met je leidinggevende of IT.
**Afbeeldingen niet zichtbaar**: check internet, vernieuw, geduld bij trage verbinding.
**Zoekfunctie werkt niet**: probeer andere trefwoorden, check spelling, gebruik bredere termen.

## Hulp krijgen

- **IT-support**: voor technische problemen
- **Content-vragen**: neem contact op met de pagina-eigenaar (zie pagina-details)
- **Algemene vragen**: gebruik de Contact-pagina in IntraVox

### Feedback

Heb je suggesties voor verbetering van content?

1. Neem contact op met de content-editor van die sectie
2. Gebruik het feedback-proces van je organisatie
3. Spreek met de intranet-vertegenwoordiger van je afdeling
