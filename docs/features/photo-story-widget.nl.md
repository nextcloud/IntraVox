# Photo-story-widget

De Photo-story-widget verandert een map met foto's (en video's) in een rijke visuele story op een IntraVox-pagina. Hij leest EXIF-metadata en optionele MetaVox-velden om foto's per dag te groeperen, op een kaart te plaatsen en automatisch de meest interessante te selecteren.

Het is de foto-gerichte tegenhanger van de [File-story-widget](file-story-widget.md): beide streamen content direct uit een Nextcloud-map, maar Photo-story is geoptimaliseerd voor afbeeldingen.

## Features

- **Vier layout-modes**: Timeline, Highlights, Grid, On this day
- **Drie timeline-visual-stijlen**: Magazine (serif redactioneel), Apple (schoon grid, default), Travelogue (Polarsteps-achtige rail)
- **EXIF-driven**: foto's kunnen gegroepeerd worden op opname-datum, niet alleen wijzigingsdatum
- **Optionele kaarten**: een overzichtskaart voor de hele story en een mini-kaart per dag
- **MetaVox-integratie**: filter en sorteer op elk MetaVox-veld (mensen, onderwerpen, locaties, custom fields)
- **Cross-map-zoeken**: laat de bron-map leeg en gebruik filters om MetaVox-getagde foto's overal vandaan te halen
- **Federation-aware**: federated mounts van partner-Nextcloud-instanties renderen als een gewone foto-bron — verander een multi-instelling-project-pagina in een live gedeelde foto-muur zonder bestanden te verplaatsen. Zie [Federation](#federation) hieronder.
- **Infinite scroll**: folder-mode laadt foto's in batches; een floating jaar-jump-scrubber verschijnt wanneer de story meerdere jaren beslaat
- **Video- & RAW-ondersteuning**: tegels vallen terug op een typed placeholder (`VIDEO` / `RAW` / extensie) wanneer Nextcloud geen preview kan renderen; video's krijgen een play-button-overlay
- **Lightbox**: full-screen viewer met toetsenbord-navigatie, geopend vanuit elke tegel of dag-kaart-foto
- **Live preview** in de widget-editor: zie je wijzigingen direct toegepast terwijl je tweakt

## Layout-modes

### Timeline

Foto's zijn gegroepeerd per opname-dag, met sticky datum-headers. De default-modus voor narratieven zoals reizen, projecten of evenementen.

![Photo-story-widget — Timeline-mode](../../screenshots/PhotoStory-TimeLine.png)

Drie visuele stijlen zijn beschikbaar:

| Stijl | Look | Geschikt voor |
|-------|------|---------------|
| **Apple** (default) | Schone vierkante tegels in een strak 5-kolom-grid | General-purpose timelines |
| **Magazine** | Redactionele serif-typografie (Georgia), grote gevarieerde tegels, 4:3-aspect, deterministische 1–3-kolom-spans per tegel | Glossy foto-stories, jaarverslagen |
| **Travelogue** | 60 px timeline-rail met bullets per dag, 2-kolom-foto-grid | Reisdagboeken, multi-dag-evenementen |

Timeline is de enige modus waarin de visual-style-picker en de per-dag-mini-kaart-toggle van toepassing zijn. Hij krijgt ook een floating jaar-jump-scrubber aan de rechterkant wanneer de story meer dan één jaar beslaat.

### Highlights

Cureert automatisch de meest interessante foto's met een scoring-model op basis van mensen, onderwerpen, locatie, bestandsgrootte en EXIF-cues, en klapt near-duplicates van foto-bursts in. De top-foto wordt gerendered als een 16:9-hero (tot 45vh hoog) met overlay-caption; de rest vult een twee-kolom-grid eronder.

Highlights heeft zijn eigen interne scorer en **toont de sorteer-dropdown niet** in de editor — volgorde wordt voor je berekend.

### Grid

Een doorlopende masonry-muur — geen datum-groepering. Gebruik dit wanneer chronologie minder belangrijk is dan visuele impact. Kies tussen 2, 3, 4 of 5 kolommen; ruwweg één op de drie foto's spant twee rijen voor een gevarieerd ritme.

![Photo-story-widget — Grid-mode](../../screenshots/PhotoStory-Grid.png)

### On this day

Foto's genomen op de huidige datum in eerdere jaren, gegroepeerd per jaar (nieuwste eerst). Vereist een concrete bron-map — niet beschikbaar in cross-map-modus.

## Configuratie

Om een Photo-story-widget aan je pagina toe te voegen:

1. Klik op **+ Widget toevoegen** in bewerk-modus
2. Selecteer **Photo-story** uit de widget-picker

   ![Photo-story in de widget-picker](../../screenshots/PhotoStory.png)

3. Kies een bron-map (of laat leeg voor cross-map MetaVox-zoeken)
4. Pas layout, sortering en weergave-opties aan
5. Onderaan de editor staat een **live preview** die updatet terwijl je instellingen wijzigt

### Bron

| Instelling | Config-key | Beschrijving |
|------------|------------|--------------|
| **Bron-map** | `folderPath` | De map om foto's uit te lezen. Gebruik `/` voor je hele drive. Laat leeg met minstens één filter voor cross-map MetaVox-zoeken. |
| **MetaVox-capability-badge** | — | Alleen-lezen: toont *MetaVox: rijke metadata beschikbaar* of *MetaVox: alleen basis-EXIF*, op basis van of de app is geïnstalleerd en de map metadata heeft. |
| **Filters** | `metaVoxFilters` | MetaVox-veld-filters (alleen getoond wanneer MetaVox beschikbaar is). Operators: `equals`, `contains`, `in`, `year_equals`. Waarde-inputs passen zich aan aan het veldtype (datum / jaar / nummer / checkbox / select / tekst). Alleen EXIF-namespaced velden (`exif_*`) worden door de server geaccepteerd. |

Als je **Bron-map** leeg laat zonder een filter toe te voegen, toont de widget een waarschuwing: *"Cross-map-modus (lege bron-map) vereist minstens één filter"*.

### Layout

| Instelling | Config-key | Beschrijving |
|------------|------------|--------------|
| **Layout-mode** | `mode` | Timeline, Highlights, Grid of On this day |
| **Volgorde** | `sortBy` + `sortOrder` | Sorteer op opname-datum (`taken_at`), wijzigingsdatum (`mtime`), bestandsnaam, bestandsgrootte of elk MetaVox-veld (multiselect/tags/checkbox uitgesloten). Richting-labels passen zich aan aan het veldtype — *Nieuwste/Oudste eerst*, *A → Z* / *Z → A*, *Grootste/Kleinste eerst*. Verborgen voor Highlights. |
| **Visuele stijl** | `style` | `magazine` / `apple` (default) / `travelogue` — alleen Timeline |
| **Kolommen** | `columns` | 2 / 3 / 4 / 5 — alleen Grid |

Wanneer je sorteert op een MetaVox-veld toont de editor de hint *"Sorteren op een MetaVox-veld ordent binnen elke geladen pagina (best-effort over infinite scroll)."*

### Weergave

| Instelling | Config-key | Beschrijving |
|------------|------------|--------------|
| **Maximum foto's** | `limit` | Limiteer het totale aantal foto's (1–500). Laat leeg om alles via infinite scroll te laden. |
| **Toon captions** | `showCaptions` (default `true`) | Toon locatie- of bestandsnaam-caption bij hover over een tegel |
| **Toon overzichtskaart** | `showMap` (default `false`) | Toon een Leaflet-kaart van alle foto's bovenaan de widget. Uitgeschakeld wanneer de map geen GPS heeft of wanneer de beheerder kaarten globaal heeft uitgezet. |
| **Toon dagelijkse mini-kaart** | `showDayMaps` (default `true`) | Toon een per-dag mini-kaart boven elke timeline-dag-header. Alleen van toepassing op Timeline. |

Er is geen titel-input in de editor — de widget leidt een titel af van de map-naam en datum-range op de server. Een klein **Bron-map**-link-icoon verschijnt bovenaan de gerenderde widget en opent de map in Nextcloud Files.

## Kaarten

De widget kan OpenStreetMap-gebaseerde kaarten renderen via Leaflet wanneer:

1. Foto's GPS-coördinaten in hun EXIF hebben (of via de MetaVox-EXIF-backfill)
2. De beheerder kaarten niet globaal heeft uitgeschakeld
3. De widget-optie is ingeschakeld

De beheerder controleert kaart-beschikbaarheid via deze IConfig-keys (zie [Beheerinstellingen](../admin/settings.md)):

| Key | Doel |
|-----|------|
| `photostory.map.enabled` | Master switch — schakelt alle kaarten uit indien ingesteld op `no` |
| `photostory.tiles.url` | Tile-server-URL (default `https://tile.openstreetmap.org/{z}/{x}/{y}.png`) |
| `photostory.tiles.attribution` | Attribution-tekst getoond op de kaart |
| `photostory.tiles.max_zoom` | Maximum zoom-niveau |

Als de globale toggle uit staat, zijn de **Toon overzichtskaart**- / **Toon dagelijkse mini-kaart**-controls in de editor uitgeschakeld met de hint *"(Uitgeschakeld door beheerder)"*.

## Cross-map MetaVox-zoeken

Het **Bron-map**-veld leeg laten schakelt de widget naar cross-map-modus: hij negeert de map-structuur en haalt elke foto op die matcht met de MetaVox-filters. Dit vereist minstens één filter — zonder filters toont de widget een lege staat met de hint *"Voeg een filter toe om te starten"*.

Cross-map-modus is **emergent**: er is geen expliciete toggle, de legacy `allMetaVoxFolders`-flag wordt automatisch gesynchroniseerd vanuit een lege bron-map zodat oudere opgeslagen pagina's blijven werken. Cross-map-modus loopt momenteel via het legacy in-memory pad; het is niet gepagineerd.

Typische use-cases:

- *"Alle foto's getagd met `Persoon = Anna`"*
- *"Alle foto's waar `Land = Italië` en `Jaar = 2025`"*
- *"Alle foto's met onderwerp `Zonsondergang`"*

## Federation

Photo-story rendert federated mounts (inkomende OCM-/Nextcloud-Federation-shares) als een gewone bron: wijs de widget naar de federated map en partner-foto's verschijnen in dezelfde timeline / grid / highlight als al het andere. Niets wordt gekopieerd — de widget streamt thumbnails en EXIF over de federated link, elke keer opnieuw.

Wat dit ontsluit voor hoger onderwijs / onderzoek:

- **Veldwerkfoto's van een consortiumproject** waarin elke deelnemende universiteit haar eigen Nextcloud beheert. Eén Photo-story-widget toont foto's van het Antarctica-veldwerk van TU Delft, het noordpool-fieldwork van Wageningen en het laboratoriumwerk van een industriepartner — drie tenants, één tijdlijn, geen master-copy.
- **Promotie- en openingsceremonies** waarbij de fotograaf van het partner-instituut zijn shoot in een eigen Nextcloud uploadt en met de gastinstelling federeert. De intranet-pagina van de gastinstelling toont de ceremonie binnen minuten, zonder dat de bestanden ergens hoeven te dupliceren.
- **Studentprojecten over instellingen heen**: docenten van twee hogescholen kunnen één gezamenlijke project-pagina draaien waar de studenten van beide kanten hun output uploaden naar hun eigen Nextcloud-omgeving — de pagina toont alles in één Photo-story.

Federation-bewuste degradatie volgt dezelfde logica als de [File-story-widget](file-story-widget.md#federation):

- MetaVox-velden (mensen, onderwerpen, locaties) komen van de eigenaar-instance; ze reizen niet mee over OCM. Filters en sort-op-MetaVox-veld worden verborgen voor federated bronnen.
- EXIF dat in het bestand zelf zit (*opname-datum*, *GPS*) blijft beschikbaar — Photo-story leest dat aan de viewer-kant.
- Per foto verschijnt een klein cloud-badge dat aangeeft dat de tegel van een federated bron komt.
- Kaarten werken: GPS uit EXIF heeft geen owner-side-database nodig en wordt gewoon op de overzichtskaart geplot.

De widget her-controleert federation-status op elke `/photos`-request — een eerder opgeslagen config waarbij de bron later federated raakt, valt niet stilletjes uit.

## Performance

- **ETag / 304**: elke `/photos`-response draagt een per-gebruiker-ETag (UID gebakken in de hash, zodat het niet kan lekken tussen tenants). Vervolg-requests sturen `If-None-Match` — wanneer er niets veranderd is, geeft de server `304 Not Modified` terug zonder herberekening.
- **Client-side payload-cache**: de widget houdt een in-memory LRU bij van 8 payloads gekeyed op bron-map + modus + filters + sort. Wisselen van modus (Grid → Timeline → Grid) herstelt direct zonder round-trip. Paged modus (folder Timeline + Grid) slaat deze cache over om infinite-scroll-state te bewaren.
- **Paged pad**: folder-modus Timeline + Grid streamen in pagina's van 200 foto's via een IntersectionObserver met een 800 px root-margin. Highlights, On this day en cross-map-modus gebruiken het legacy in-memory pad.
- **Hard cap**: 200.000 bestanden per map-boom. Daarboven wordt de response gemarkeerd als `truncated: true` en verschijnt een gele banner: *"Eerste {n} foto's getoond. Gebruik filters of een specifiekere map om de selectie te beperken."*
- **Debounce**: editor-wijzigingen worden 250 ms gedebounced voordat een fetch wordt getriggerd.
- **`requestIdleCallback`** wordt gebruikt bij mount zodat de eerste fetch de initiële pagina-render niet blokkeert.

## API

De widget roept de volgende endpoints aan (alle onder `/apps/intravox/api/photo-story/`):

| Endpoint | Methode | Doel |
|----------|---------|------|
| `/photos` | GET | Lijst foto's met groepering, sortering, paginering, ETag. Params: `folder`, `mode`, `filters` (JSON), `limit`, `offset`, `pageSize` (max 500), `sortOrder`, `sortBy`, optionele `total`-hint. |
| `/clusters` | GET | Map-cluster-aggregaten voor de overzichtskaart. Params: `folder`. |
| `/capabilities` | GET | `{ capabilities: { hasDate, hasLocation, hasPeople, hasSubjects }, metaVoxAvailable, map: { enabled, tile_url, attribution, max_zoom } }` |
| `/photo-exif` | GET | Per-foto EXIF voor het lightbox-detail-paneel. Params: `file_id`. |
| `/video` | GET | HTTP-Range-aware video-stream (`Accept-Ranges: bytes`, 206-responses). Params: `file_id`. |
| `/metavox-fields` | GET | Lijst van beschikbare MetaVox-velden (gebruikt voor de filter- en sort-dropdowns) |

## Alleen geïndexeerde bestanden worden getoond

Photo-story bevraagt Nextcloud's `oc_filecache` direct via SQL — het voert geen live filesystem-walks uit. Dit is hetzelfde snelle pad dat Nextcloud zelf gebruikt voor zoeken en listing. Het gevolg is dat **bestanden die niet in de file-cache staan onzichtbaar zijn voor de widget**, zelfs als ze op schijf bestaan.

Bestanden worden normaal automatisch geïndexeerd wanneer ze worden geüpload via:

- De Nextcloud-web-UI
- De desktop-sync-client
- Mobiele apps
- WebDAV (de meeste clients)

Bestanden kunnen **uit sync raken met de cache** wanneer ze direct op schijf worden geplaatst (bv. `rsync`, `cp`, server-side scripts) buiten Nextcloud's storage-laag om. In dat geval kan de Files-app ze nog steeds tonen (hij doet een eenmalige live-check bij directory-listing — zie `IWatcher::CHECK_ONCE`), maar Photo-story doet dat niet tot de index is bijgewerkt.

Om de cache weer in sync te brengen:

```bash
# Hele gebruiker
occ files:scan <username>

# Een enkele map
occ files:scan --path="/<username>/files/Photos/MyAlbum"

# Alles (langzaam op grote instanties)
occ files:scan --all
```

Voor installaties waar bestanden regelmatig vanuit externe scripts binnenkomen, schedule `occ files:scan` via cron, of — beter — laat het upload-pad via Nextcloud's storage-API lopen in plaats van bestanden direct te schrijven.

De widget triggert **geen** scan zelf: scans op een volle map kunnen seconden tot minuten duren, en ze triggeren bij elke widget-render zou de pagina-load slecht maken. NC volgt hetzelfde principe — de Files-app her-controleert alleen bestand-metadata, voegt nooit ontbrekende entries toe.

## Ondersteunde bestandsformaten

Photo-story neemt elk bestand mee waarvan het mime-type in één van deze groepen valt:

**Veelvoorkomende image-formaten**: JPEG, PNG, WebP, GIF, SVG, BMP

**Fotografie-grade**: HEIC, HEIF, TIFF

**RAW-camera-formaten**: Canon CR2/CR3, Nikon NEF, Sony ARW, Adobe DNG, dcraw

**Video**: MP4, MOV/QuickTime, AVI, MPEG, WMV, WebM

Bestanden met andere mime-types worden stil overgeslagen. Als gebruikers foto's uploaden met een exotisch formaat (bv. AVIF) en ze verschijnen niet, is dat de reden — het formaat staat niet in de widget-mime-allowlist.

## Waarom sommige tegels een placeholder tonen in plaats van een preview

Photo-story rendert thumbnails via Nextcloud's `/core/preview`-endpoint. Voor elk bestand vraagt Nextcloud zijn preview-provider-keten (zie `config.php` → `enabledPreviewProviders`) om een JPEG te genereren. Als geen enkele provider het mime-type voor dat bestand kan afhandelen, valt de widget terug op een placeholder-tegel met de bestandsextensie als badge.

Veelvoorkomende redenen:

- **Mime-type niet in `enabledPreviewProviders`** — beheerder moet de ontbrekende provider toevoegen. Veelvoorkomende gaten:
  - `image/heic` en `image/heif` vereisen `OC\Preview\HEIC` (NC 28+) **en** server-side `libheif`
  - `image/webp` staat default aan in `OC\Preview\Image`, maar alleen als Imagick of GD's WebP-ondersteuning aanwezig is
  - `image/svg+xml` vereist de expliciete `OC\Preview\SVG`-provider (standaard uit om beveiligings-redenen)
  - RAW-camera-formaten (CR2/NEF/ARW/DNG) vereisen `OC\Preview\Imaginary` met de `imaginary`-externe service, of de `previewgenerator`-app met een wrapper-script
- **Bestand te groot** — preview-generatie is gelimiteerd door `preview_max_filesize_image` (default 50 MB). Grotere foto's krijgen de placeholder
- **Bestand is nog niet pre-rendered** — voor zeer grote libraries, installeer de [previewgenerator](https://apps.nextcloud.com/apps/previewgenerator)-app en draai `occ preview:generate-all` eenmalig. Daarna renderen alle tegels direct
- **Video-previews** vereisen server-side `ffmpeg` in Nextcloud's preview-keten; zonder dat vallen video's terug op een typed placeholder-tegel met play-button-overlay

Tip: open één van de getroffen bestanden via NC's Files-app en check het preview-paneel. Als zelfs Files een generiek icoon toont, ligt het preview-provider-probleem server-side, niet specifiek aan IntraVox.

## Vereisten

- IntraVox 1.5.0 of hoger (Photo-story-widget zit in een 1.5.x preview-build)
- MetaVox-app (optioneel, sterk aanbevolen voor filteren, sorteren en mensen-/plaats-metadata)
- Foto's met EXIF-metadata (opname-datum, GPS) voor de rijkste ervaring
- Leaflet-compatibele kaart-tiles geconfigureerd door de beheerder (optioneel, voor kaarten)
- Een werkende Nextcloud-preview-provider-keten voor de formaten die je wilt tonen (zie "Waarom sommige tegels een placeholder tonen" hierboven)

## Beperkingen (huidige preview)

- De widget is nog in actieve ontwikkeling; layouts en optie-namen kunnen veranderen.
- Sorteren op een MetaVox-veld is best-effort over infinite-scroll-pagina's — binnen elke pagina is de sort exact, maar over pagina-grenzen kan wat interleaving optreden.
- Cross-map-modus is **niet gepagineerd** — hij laadt de volledige result-set in geheugen. Voeg filters toe om de result-set redelijk te houden.
- Highlights en On this day gebruiken altijd het legacy in-memory pad; ze negeren de 200-foto-pagina-grootte en laden alles tot aan de hard cap van 200k.
- Video-previews vereisen server-side `ffmpeg` in Nextcloud; zonder dat vallen video's terug op een typed placeholder-tegel met play-button-overlay.
- Kaart-rendering hangt af van Leaflet; beheerders kunnen kaarten globaal uitschakelen als ze geen third-party tile-traffic willen.
- De jaar-scrubber is verborgen wanneer de widget smaller is dan 500 px.

## Gerelateerd

- [File-story-widget](file-story-widget.md) — documenten-gerichte tegenhanger
- [News-widget](news-widget.md) — pagina-gerichte tegenhanger met publicatiedatums
- [MetaVox-EXIF-backfill](../admin/settings.md) — hoe MetaVox-velden gevuld kunnen worden uit EXIF voor legacy foto's
