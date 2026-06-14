# File-story-widget

De File-story-widget rendert de documenten in een Nextcloud-map als een rijke, sorteerbare, filterbare story op een IntraVox-pagina. Het is de documenten-gerichte tegenhanger van de [Photo-story-widget](photo-story-widget.md): beide streamen content direct uit een map, maar File-story is geoptimaliseerd voor office-documenten, PDFs, spreadsheets en andere niet-image-bestanden.

## Features

- **Vier layout-modes**: Timeline, Tegels, Lijst, Gegroepeerd
- **Timeline-granulariteit**: groepeer per dag, maand of jaar
- **Preview-thumbnails**: tegel-modus rendert eerste-pagina-previews van PDFs, Office-documenten enz., met mime-icon-fallback eronder zodat een ontbrekende preview nooit een broken image laat zien
- **MetaVox-integratie**: filter, sorteer en groepeer op elk MetaVox-veld (auteur, project, status, custom fields)
- **Groeperen op**: cluster documenten op bestandscategorie (PDF, Tekstdocumenten, Spreadsheets, Presentaties, Tekst, Overig) of elk single-valued MetaVox-veld
- **Federation-native**: een federated mount van een partner-instelling rendert side-by-side met je eigen bestanden — één widget kan documenten van meerdere Nextcloud-instanties tonen zonder kopiëren of syncen. Zie [Federation](#federation) hieronder.
- **Configureerbare kolommen**: toon datum, bestandsgrootte en/of map-pad naast de bestandsnaam
- **Infinite scroll**: documenten worden in batches van 100 ingeladen met ETag-gebaseerde 304's
- **Direct openen**: klikken opent in Nextcloud's native viewer (`OCA.Viewer`) wanneer beschikbaar, valt terug op `/f/<file_id>`

## Layout-modes

### Timeline

Documenten zijn gegroepeerd op datum met sticky headers per groep. Granulariteit is configureerbaar (default: per maand).

![File-story-widget — Timeline-mode](../../screenshots/FileStory-Timeline.png)

| Granulariteit | Geschikt voor |
|---------------|---------------|
| **Per dag** | Dagelijkse rapporten, vergader-notities |
| **Per maand** (default) | Maandelijkse nieuwsbrieven, maandelijkse contracten |
| **Per jaar** | Archief-views, jaarverslagen |

Rijen tonen bestandstype-icoon + bestandsnaam, met een `HH:MM`-tijd, grootte en map-pad ernaast (geregeld via **Toon kolommen**).

### Tegels

Een visueel grid waarin elk document wordt gerendered als een tegel met eerste-pagina-preview-thumbnail. De fallback (mime-icon + uppercase extensie-badge) wordt eronder gerendered, zodat 404's op documenten zonder geregistreerde preview-provider niet flikkeren.

![File-story-widget — Tegels-mode](../../screenshots/FileStory-Tiles.png)

Tegel-grootte is configureerbaar:

| Grootte | Min tegel-breedte | Gap |
|---------|-------------------|-----|
| **Klein** | 120 px | 10 px |
| **Middel** (default) | 180 px | 14 px |
| **Groot** | 260 px | 18 px |

Het beste voor browseable document-libraries waar een visuele hint helpt met herkenning.

### Lijst

Een platte sorteerbare lijst — geen groepering. Rijen gebruiken een langer `dag maand jaar, HH:MM`-datum-formaat (anders dan Timeline-mode, die alleen de tijd toont omdat de datum al in de sectie-header staat).

![File-story-widget — Lijst-mode](../../screenshots/FileStory-List.png)

Een veelvoorkomend patroon: wijs de bron aan `/`, sorteer op **Wijzigingsdatum — nieuwste eerst**, verberg de bestandsgrootte- en map-pad-kolommen en limiteer op 10 documenten — instant "Recente bestanden"-widget voor de homepage.

![File-story-widget — Recente-bestanden-configuratie](../../screenshots/FileStory-RecentFiles.png)

### Gegroepeerd

Documenten worden geclusterd in secties. Secties zijn geordend op aantal (aflopend), dan op label (case-insensitive oplopend).

![File-story-widget — Gegroepeerd-mode](../../screenshots/FileStory-Grouped.png)

De groeperings-sleutel is configureerbaar:

- **Categorie (bestandstype)** — gesynthetiseerde buckets: `PDF`, `Tekstdocumenten`, `Spreadsheets`, `Presentaties`, `Tekst`, `Overig` (deze labels zijn momenteel hard-coded NL)
- **Elk single-valued MetaVox-veld** — auteur, project, status, afdeling, …

Multiselect-/tag-MetaVox-velden komen niet in aanmerking voor groepering. Items zonder waarde worden gegroepeerd onder `(geen waarde)`.

## Configuratie

Om een File-story-widget aan je pagina toe te voegen:

1. Klik op **+ Widget toevoegen** in bewerk-modus
2. Selecteer **File-story** uit de widget-picker

   ![File-story in de widget-picker](../../screenshots/FileStory.png)

3. Kies een bron-map (multi-map-/cross-map-modus is niet beschikbaar — de server geeft 400 voor een lege map terug)
4. Pas layout, sortering en weergave-opties aan

### Bron

| Instelling | Config-key | Beschrijving |
|------------|------------|--------------|
| **Bron-map** | `folderPath` | De map om documenten uit te lezen |
| **MetaVox-capability-badge** | — | Drie statussen: *MetaVox: rijke metadata beschikbaar* / *Federated share — MetaVox-metadata is niet beschikbaar omdat de bron op een andere Nextcloud-instantie staat. Bestanden worden alleen met naam, datum en bestandstype getoond.* / *MetaVox niet beschikbaar — alleen basis-bestand-metadata* |
| **Filters** | `metaVoxFilters` | MetaVox-veld-filters (verborgen wanneer MetaVox niet beschikbaar is of de bron federated is). Zie [News-widget — MetaVox-integratie](news-widget.md#metavox-integratie) voor filter-mechanica. |

![File-story-widget — MetaVox-filters](../../screenshots/FileStory-MetaVox-Filters.png)

### Layout

| Instelling | Config-key | Beschrijving |
|------------|------------|--------------|
| **Layout-mode** | `mode` | Timeline / Tegels / Lijst / Gegroepeerd |
| **Tegel-grootte** | `tileSize` | Klein / Middel / Groot — alleen Tegels-mode |
| **Groeperen op** | `groupBy` (default `category`) | Categorie (bestandstype) of een single-valued MetaVox-veld — alleen Gegroepeerd-mode. Verborgen wanneer de bron federated is. |
| **Groeperen op datum** | `granularity` (default `month`) | Per dag / Per maand / Per jaar — alleen Timeline-mode |
| **Volgorde** | `sortBy` + `sortOrder` | Sorteer op wijzigingsdatum (`mtime`), bestandsnaam, bestandsgrootte of elk MetaVox-veld (multiselect/tags/checkbox uitgesloten; federated bronnen staan alleen base file-fields toe). Richting-labels passen zich aan aan het veldtype — *Nieuwste/Oudste eerst*, *A → Z* / *Z → A*, *Grootste/Kleinste eerst*. |

### Weergave

| Instelling | Config-key | Beschrijving |
|------------|------------|--------------|
| **Toon kolommen** | `visibleColumns` (default `['date']`) | Toggle welke extra velden verschijnen naast de bestandsnaam: Datum / Bestandsgrootte / Map-pad. De bestandsnaam en het bestandstype-icoon worden altijd getoond. |
| **Maximum documenten** | `limit` | Limiteer het totale aantal documenten (1–500). Laat leeg om alles via infinite scroll te laden. |

De File-story-editor heeft **geen** titel-input — wikkel de widget in een [Inklapbare sectie](../user/editor.md) of voeg een heading-widget erboven toe als je een label als "Handleidingen" boven de document-lijst nodig hebt.

## Federation

File-story is gebouwd rond Nextcloud's **federated sharing** — het protocol waarmee twee onafhankelijke Nextcloud-instanties mappen met elkaar kunnen delen zonder dat er data het thuis-systeem verlaat. Open Cloud Mesh (OCM) onder de motorkap; hetzelfde mechanisme dat de SURF-research-cloud-federatie aandrijft, de GÉANT-cloud-federatie-pilot en elke "Sciebo / Hochschulcloud / Drive @ NRW"-cross-institution-share.

Voor een intranet gebouwd op IntraVox is federation wat een pagina *live laat draaien over organisaties heen* zonder een copy-paste-portal te worden.

![Eén IntraVox-pagina die drie File-story-widgets rendert — elk vanuit een andere Nextcloud-server](../../screenshots/FileStory-demo1.png)

*Eén IntraVox-pagina, drie File-story-widgets, drie verschillende Nextcloud-instanties: een klantenserver links, bestanden van deze server in het midden en een testserver rechts. Elke kolom streamt live vanaf zijn eigenaar — niets wordt gedupliceerd, niets wordt gesynct, geen instantie heeft toegang tot de data van de andere tenants buiten wat expliciet gefedereerd is.*

### Wat dit ontsluit

- **Eén pagina per consortium-project.** Een onderzoeksgroep aan TU Delft, een partner-faculteit aan RU Nijmegen en een niet-academische partner bij TNO uploaden elk hun werkdocumenten naar *hun eigen* Nextcloud. Het project-intranet, gehost bij één van hen, mount de mappen van de anderen via federated share en rendert alle drie de streams in één File-story-widget. Elke partner behoudt eigenaarschap, retentie, AVG-verantwoordelijkheid — maar de project-pagina toont één geconsolideerde view.
- **Eén pagina per onderzoeksinfrastructuur.** Een nationale faciliteit federeert partner-instelling-drop-zones naar zijn publiek-georiënteerde IntraVox-project-pagina. Nieuwe datasets verschijnen op de pagina zodra een partner uploadt — geen webmaster, geen handmatige link-lijst.
- **Eén pagina per onderwijsmodule over instellingen heen.** Een joint master tussen Wageningen en Utrecht toont lecture-materiaal van beide LMSen in één IntraVox-curriculum-pagina. Elke universiteit behoudt eigen gebruikers-accounts en toegangs-beleid; alleen de relevante map wordt gefedereerd.
- **Cross-organisatie-ketenpartners** (een gemeente + een woningcorporatie + een zorgorganisatie) delen project-documenten *zonder* dat één van hen de anderen in hun tenancy hoeft uit te nodigen. De widget toont de documenten van de partner live; als de partner de share intrekt, verdwijnt de rij de volgende keer dat de widget laadt. Geen wees-kopieën, geen verouderde links.

Dit is het FAIR-/EOSC-argument in de praktijk: data blijft waar zijn data-steward is, maar compositie gebeurt op de presentatie-laag. IntraVox is de presentatie-laag.

### Hoe in te stellen

1. Op de **eigenaar-Nextcloud** (de kant die de documenten bezit), deel de map met de federated-Cloud-ID van de partner via de share-dialoog van de Files-app. De federated share verschijnt onder **Extern delen** met de identifier `username@partner.example.org` van de partner.

   ![Federated share — een federated share aanmaken vanuit de Files-app](../../screenshots/FileStory-CreateFederation.png)

2. De partner accepteert de share op zijn Nextcloud; de map verschijnt als een gewone mount in zijn Files-tree.
3. Wijs in IntraVox een File-story-widget aan die mount. Het rendert net zoals een lokale map.

   ![File-story rendert dezelfde federated map side-by-side: eigenaar (links) en partner (rechts)](../../screenshots/FileStory-FederationExample.png)

   *Zelfde `Afspraken intern.docx`-bestand, zelfde widget-config, twee verschillende Nextcloud-instanties. De partner-side-widget streamt de bestand-metadata via OCM in real-time.*

### Wat verandert op een federated bron

Federated mounts erven een beperking van het federated-cloud-protocol zelf: alleen de basis-bestand-velden steken de federatie over. MetaVox-metadata blijft op de instantie van de eigenaar, omdat het in de database van de eigenaar staat en geen onderdeel is van de OCM-payload. De widget detecteert dit en past zich aan:

- De capability-badge schakelt naar de federated-share-status en legt uit dat MetaVox-metadata niet over de federatie heen kan.
- De filter-builder is verborgen.
- De Groeperen-op-dropdown is beperkt tot alleen **Categorie**.
- De sorteer-dropdown laat MetaVox-veld-opties vallen; alleen `Wijzigingsdatum` / `Bestandsnaam` / `Bestandsgrootte` blijven over.
- Per rij krijgen bestanden gemount vanuit een federated bron een klein cloud-badge naast de bestandsnaam, tooltip *"Vanuit federated share — MetaVox-metadata niet beschikbaar"*.

De server her-checkt federation-status op elke `/files`-request, dus een config opgeslagen vóórdat de map federated werd zal niet stilletjes elk resultaat uitfilteren.

### Wat lokaal blijft

Rijke metadata (MetaVox-velden, EXIF, custom indices) leeft in de database van de eigenaar en reist niet over OCM. De widget moffelt dit nooit weg: als een federated mount MetaVox-metadata heeft aan de kant van de eigenaar, blijft die daar. De regel is simpel — rijke metadata voor lokale bronnen, basis-bestand-metadata voor federated bronnen, geen broken links hoe dan ook.

## Performance

- **ETag / 304**: elke `/files`-response draagt een per-gebruiker-ETag (UID gebakken in de hash en een `'w' => 'file-story'`-discriminator, zodat het niet kan botsen met photo-story-ETags of lekken tussen tenants). Vervolg-requests sturen `If-None-Match` en de server geeft `304 Not Modified` terug wanneer er niets veranderd is.
- **Paged streaming**: 100 documenten per pagina via een IntersectionObserver met een 600 px root-margin. Wanneer je limiteert met **Maximum documenten**, wordt pagina-grootte aangepast richting de limiet en `hasMore` springt naar false op de laatste pagina.
- **Truncation-banner**: wanneer de map de hard cap van 200.000 bestanden overschrijdt, wordt de response gemarkeerd als `truncated: true` en toont de widget *"Eerste {n} documenten getoond. Gebruik filters of een specifiekere map."*
- **Debounce**: editor-wijzigingen worden 250 ms gedebounced voordat een fetch wordt getriggerd.

## API

De widget roept de volgende endpoints aan (alle onder `/apps/intravox/api/file-story/`):

| Endpoint | Methode | Doel |
|----------|---------|------|
| `/files` | GET | Lijst documenten. Params: `folder` (verplicht), `mode` (timeline/tiles/list/grouped), `filters` (JSON), `limit`, `offset`, `pageSize` (default 100, max 500), `sortOrder`, `sortBy`, optionele `total`-hint, `granularity` (alleen timeline), `groupBy` (alleen grouped). |
| `/capabilities` | GET | `{ capabilities, metaVoxAvailable, sourceIsFederated, sourceFederatedInfo: { remote, mount_point } \| null }`. Optionele `folder`-param. |
| `/metavox-fields` | GET | Lijst van MetaVox-velden beschikbaar voor de filter-, sort- en group-by-dropdowns |

Het `/files`-endpoint hergebruikt de gedeelde `PhotoStoryService::listPhotosPaged()`-machinerie met een `mimeCategory='documents'`-filter — zelfde SQL- + MetaVox-pad, alleen een andere mime-set.

## Tips

- **Tegels voor libraries, Timeline voor streams**: als gebruikers *browsen*, wint Tegels. Als ze *bijpraten* over wat er nieuw is, wint Timeline.
- **Toon map-pad** wanneer de bron-map submappen bevat en dezelfde bestandsnaam op meerdere plekken kan bestaan.
- **Combineer Gegroepeerd + MetaVox** om een per-auteur- of per-project-overzicht te renderen zonder de map handmatig vooraf te sorteren.
- **Limiteer** op drukke intranet-homepages om te voorkomen dat honderden documenten vooraf worden geladen.
- **Federated mounts** zijn een first-class bron — combineer een lokale-map-widget met een federated-map-widget op dezelfde pagina om één geconsolideerde stream van partner-documenten naast je eigen te renderen.

## Vereisten

- IntraVox 1.5.0 of hoger (File-story-widget zit in een 1.5.x preview-build)
- MetaVox-app (optioneel, aanbevolen voor filteren, sorteren en groeperen op auteur/project/status)
- Nextcloud-preview-providers ingeschakeld voor de document-types waarvoor je thumbnails wilt (PDF, Office)
- `OCA.Viewer` (ingebouwd in Nextcloud) voor de in-page document-preview bij klikken op een rij/tegel — valt terug op een `/f/<file_id>`-redirect

## Beperkingen (huidige preview)

- De widget is nog in actieve ontwikkeling; layouts en optie-namen kunnen veranderen.
- Sorteren op een MetaVox-veld is best-effort over infinite-scroll-pagina's.
- Tegel-previews zijn afhankelijk van Nextcloud's preview-provider-keten; documenten zonder geregistreerde provider vallen terug op de mime-icon-placeholder.
- Multi-map-modus (het equivalent van Photo-story's cross-map-zoeken) is nog niet beschikbaar — kies een concrete bron-map.
- Categorie-labels in Gegroepeerd-mode zijn hard-coded Nederlands (`Tekstdocumenten`, `Spreadsheets`, `Presentaties`, `Tekst`, `Overig`) en nog niet vertaald.
- Een `dateField`-config-key bestaat in de default-config (`mtime` / `taken_at` / `created`) en wordt geconsumeerd door de Tegels-lange-datum-renderer, maar is nog niet ontsloten in de editor-UI — laat staan op default voor nu.

## Gerelateerd

- [Photo-story-widget](photo-story-widget.md) — foto-gerichte tegenhanger
- [News-widget](news-widget.md) — pagina-gerichte tegenhanger met publicatiedatums
- [Feed-widget](feed-widget.md) — voor externe document-bronnen (SharePoint, Moodle, …)
