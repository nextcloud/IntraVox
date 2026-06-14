# IntraVox export & import

## Overzicht

IntraVox biedt uitgebreide export- en import-functionaliteit voor content-beheer tussen installaties. Dit document beschrijft de huidige implementatie en dient als basis voor toekomstige uitbreidingen.

> Voor de volledige technische API-paden, JSON-schema's en migratie-recepten — zie de [Engelse referentie](../../en/intravox/admin/export-import/).

## Inhoud

1. [Admin-interface](#admin-interface)
2. [Export-functionaliteit](#export-functionaliteit)
3. [Import-functionaliteit](#import-functionaliteit)
4. [MetaVox-integratie](#metavox-integratie)
5. [Confluence-import](#confluence-import)
6. [Data-structuur](#data-structuur)
7. [API-endpoints](#api-endpoints)

---

## Admin-interface

**Locatie**: Nextcloud-beheerinstellingen → IntraVox → Export/Import-sectie.

De export/import-interface gebruikt tabbed-navigatie met drie tabs:

### Tab 1: Export

![Export-tab in de IntraVox-beheer-instellingen](../screenshots/export.png)

- Exporteer pagina's per taal
- Optie om comments mee te nemen
- Toont aantal pagina's per taal
- Download als ZIP-bestand

### Tab 2: Confluence

- Import vanuit Confluence-HTML-export
- Upload HTML-export-ZIP
- Selecteer ouder-pagina voor geïmporteerde content
- Behoudt pagina-hiërarchie

### Tab 3: Import

![Import-tab in de IntraVox-beheer-instellingen](../screenshots/import.png)

- Importeer IntraVox-ZIP-bestanden
- Upload eerder geëxporteerde ZIP
- Opties:
  - Comments meenemen (indien beschikbaar in ZIP)
  - Bestaande pagina's overschrijven
  - Auto-create MetaVox-velden (v0.9.0+)
- Toont import-voortgang en resultaten
- Toont MetaVox-compatibility-waarschuwingen indien van toepassing

**Bestandslocatie**: `src/views/AdminSettings.vue`

---

## Export-functionaliteit

### Hoe het werkt

1. **Gebruikers-interactie**:
   - Admin selecteert taal uit dropdown (nl, en, de, fr)
   - Vinkt optioneel "Comments meenemen" aan
   - Klikt "Taal exporteren"-knop

2. **Frontend-request**:

   ```javascript
   // AdminSettings.vue - exportLanguage()
   const response = await axios.get(
     generateUrl('/apps/intravox/api/export/language/{language}/zip'),
     {
       params: { includeComments: this.exportIncludeComments },
       responseType: 'blob'
     }
   )
   ```

3. **Backend-verwerking** (`lib/Service/ExportService.php`):
   ```
   exportLanguageAsZip(language, includeComments)
   ├─ Maak tijdelijke directory
   ├─ Haal alle pagina's op voor taal via PageService
   ├─ Exporteer elke pagina als JSON:
   │  ├─ Pagina-metadata (id, titel, uniqueId, pad, layout, settings)
   │  ├─ Widget-content
   │  └─ Comments (indien includeComments = true)
   ├─ Kopieer _media-folders
   ├─ Kopieer _resources-folder (indien aanwezig)
   ├─ Maak manifest.json met metadata
   ├─ Maak ZIP-archief
   └─ Retourneer ZIP-bestand
   ```

4. **ZIP-structuur**:

   ```
   language-export-YYYYMMDD-HHMMSS.zip
   ├── manifest.json              # Export-metadata
   ├── pages/
   │   ├── {folderId}.json        # Pagina-data met layout
   │   └── ...
   ├── media/
   │   ├── {pageId}/
   │   │   └── image.jpg
   │   └── ...
   └── resources/                 # Gedeelde media-bibliotheek
       ├── logos/
       ├── icons/
       └── backgrounds/
   ```

### Export-format-versies

**v1.0** (vóór v0.9.0):

- Basis-pagina-export met comments
- Geen MetaVox-metadata

**v1.1** (v0.9.0+):

- Inclusief MetaVox-metadata wanneer MetaVox is geïnstalleerd
- Backward-compatible met v1.0-imports

### Manifest-structuur (v1.0)

```json
{
  "version": "1.0",
  "language": "nl",
  "exportDate": "2025-12-16T10:30:00Z",
  "pageCount": 135,
  "includesComments": true,
  "includesMedia": true,
  "includesResources": true
}
```

### Export-structuur (v1.1 met MetaVox)

```json
{
  "exportVersion": "1.1",
  "exportDate": "2025-12-16T14:30:00+00:00",
  "language": "nl",
  "metavox": {
    "version": "1.3.0",
    "fieldDefinitions": [
      {
        "field_name": "document_type",
        "field_label": "Document Type",
        "field_type": "select",
        "field_description": "Type of document",
        "field_options": ["Policy", "Procedure", "Guide"],
        "is_required": true,
        "sort_order": 0,
        "scope": "groupfolder"
      }
    ]
  },
  "navigation": {...},
  "footer": {...},
  "pages": [...]
}
```

---

## Import-functionaliteit

### Opties

- **Comments meenemen**: importeer pagina-comments als die in de ZIP zitten
- **Bestaande pagina's overschrijven**: bestaande pagina's met dezelfde `uniqueId` worden vervangen. Uit: pagina's worden overgeslagen
- **Auto-create MetaVox-velden** (v0.9.0+): wanneer MetaVox geïnstalleerd is, worden ontbrekende veld-definities automatisch aangemaakt

### Importprocess

1. ZIP-bestand wordt geüpload naar tijdelijke locatie
2. `manifest.json` wordt gelezen om versie en taal te bepalen
3. Voor elke pagina:
   a. Check of pagina al bestaat (op uniqueId)
   b. Bij overschrijven of nieuw: maak/update pagina
   c. Kopieer media-bestanden naar de juiste pagina-folder
4. Resources worden gekopieerd naar `_resources/`
5. Navigatie en footer worden bijgewerkt indien aanwezig
6. MetaVox-velden worden aangemaakt indien `auto-create` aan staat
7. Comments worden geïmporteerd indien aanwezig en geselecteerd
8. Resultaat-rapport getoond

### Conflict-handling

| Conflict | Gedrag (overwrite = uit) | Gedrag (overwrite = aan) |
|----------|---------------------------|---------------------------|
| Pagina met dezelfde uniqueId bestaat | Sla over, log waarschuwing | Vervang volledig |
| MetaVox-veld bestaat al | Sla aanmaak over, behoud bestaande | Idem |
| Media-bestand bestaat al | Vervang door versie uit ZIP | Idem |
| Pagina-pad-conflict | Auto-suffix met getal (`-2`, `-3`) | Idem |

### Foutafhandeling

- Ongeldige ZIP: rejecteer met foutmelding
- Ongeldig manifest: rejecteer met details
- Ontbrekende velden: log waarschuwing, ga door met andere pagina's
- Permissie-fouten: stop import, toon welke pagina faalde

---

## MetaVox-integratie

Sinds v0.9.0 integreren IntraVox-exports met [MetaVox](https://apps.nextcloud.com/apps/metavox). Wanneer beide apps zijn geïnstalleerd, bevat een export:

- **Veld-definities** voor de IntraVox-GroupFolder
- **Metadata-waarden** per pagina (gekoppeld via `uniqueId`)

### Compatibility-checks bij import

Bij import van een ZIP met MetaVox-data wordt gecontroleerd:

1. **MetaVox geïnstalleerd?** Zo niet: waarschuwing, metadata wordt overgeslagen
2. **Veld-definities matchen?** Verschillen worden getoond (extra velden, ontbrekende velden, type-conflicten)
3. **Auto-create geselecteerd?** Dan worden ontbrekende velden aangemaakt voor import

### Migratie tussen instanties met MetaVox

Stappen voor verplaatsing van content + metadata:

1. Exporteer op bron-instantie (taal selecteren, comments aan)
2. Importeer op doel-instantie:
   - Auto-create MetaVox-velden: aan
   - Comments: aan
   - Overschrijven: alleen als nodig
3. Verifieer veld-definities matchen
4. Verifieer steekproef van pagina-metadata

---

## Confluence-import

Importeer content uit een Confluence-HTML-export.

### Vereisten

- Confluence Cloud, Server of Data Center
- Admin-rechten in Confluence om export te triggeren
- HTML-export-ZIP gedownload uit Confluence

### Confluence-export voorbereiden

1. In Confluence: **Space settings → Content tools → Export**
2. Kies **HTML**
3. Selecteer pagina's of hele space
4. Download het ZIP-bestand

### Import-stappen in IntraVox

1. Open **Beheerinstellingen → IntraVox → Confluence**-tab
2. Selecteer **ouder-pagina** in IntraVox (waar de imports onder komen)
3. Upload het Confluence-HTML-ZIP
4. Klik **Importeren**
5. IntraVox parsed elke HTML-pagina, behoudt hiërarchie, en zet om naar IntraVox-widget-structuur

### Wat wordt geconverteerd

| Confluence-element | IntraVox-equivalent |
|---------------------|----------------------|
| Pagina-hiërarchie | Page-tree-structuur |
| Headings | Heading-widgets |
| Text + opmaak | Text-widgets (TipTap-HTML) |
| Afbeeldingen | Image-widgets + `_media/` upload |
| Bijlagen | File-widgets |
| Tabellen | Text-widgets (HTML-tabel) |
| Pagina-titels | Pagina-titels |
| Permissies | Niet meegenomen — zet handmatig in GroupFolders |

### Beperkingen

- Macros worden niet geconverteerd (Confluence-specifiek)
- Inline-comments worden overgeslagen
- Versie-geschiedenis blijft niet behouden
- Custom fields worden niet gemigreerd

Zie [Confluence-import](../features/confluence-import.md) voor uitgebreide gids.

---

## Data-structuur

### Pagina JSON-schema (v1.1)

```json
{
  "id": "page-slug",
  "uniqueId": "page-uuid-v4",
  "title": "Pagina-titel",
  "path": "nl/parent/page-slug",
  "parentPath": "nl/parent",
  "layout": {
    "columns": 2,
    "rows": [
      {
        "widgets": [
          {
            "id": "widget-1",
            "type": "heading",
            "content": "Titel",
            "level": 1,
            "column": 1,
            "order": 1
          }
        ]
      }
    ]
  },
  "settings": {
    "allowReactions": true,
    "allowComments": true,
    "allowCommentReactions": true
  },
  "metadata": [
    {
      "field_name": "document_type",
      "field_value": "Policy"
    }
  ],
  "comments": [
    {
      "userId": "alice",
      "displayName": "Alice de Vries",
      "message": "Goed punt",
      "createdAt": "2025-12-15T10:30:00Z",
      "reactions": {"👍": ["bob", "charlie"]}
    }
  ]
}
```

### Belangrijke velden

| Veld | Type | Beschrijving |
|------|------|--------------|
| `uniqueId` | string (UUID v4) | Stabiele ID over instances heen |
| `path` | string | Folder-pad relatief aan IntraVox-root |
| `layout.columns` | int | Aantal kolommen per rij (1–5) |
| `layout.rows[].widgets[]` | array | Widget-instances |
| `settings` | object | Per-pagina engagement-instellingen |
| `metadata` | array | MetaVox-veld-waarden voor deze pagina |

---

## API-endpoints

| Methode | Endpoint | Auth | Doel |
|---------|----------|------|------|
| GET | `/api/export/language/{language}/zip` | Sessie (admin) | Exporteer taal als ZIP |
| POST | `/api/import/zip` | Sessie (admin) | Importeer ZIP |
| POST | `/api/import/confluence` | Sessie (admin) | Importeer Confluence-HTML-ZIP |
| GET | `/api/export/preview` | Sessie (admin) | Preview van wat geëxporteerd zou worden |

### Query-parameters voor export

| Parameter | Type | Default |
|-----------|------|---------|
| `includeComments` | boolean | `false` |
| `includeResources` | boolean | `true` |
| `includeMedia` | boolean | `true` |

### POST-body voor import

```json
{
  "overwrite": false,
  "includeComments": true,
  "autoCreateMetavox": true
}
```

Multipart upload met `file` als ZIP-bestand.

---

## Code-architectuur

Hoofd-componenten:

| Bestand | Verantwoordelijkheid |
|---------|----------------------|
| `lib/Service/ExportService.php` | Export-logica, ZIP-bouw, manifest-generatie |
| `lib/Service/ImportService.php` | Import-logica, ZIP-extract, validatie |
| `lib/Service/ConfluenceImportService.php` | Confluence-HTML-parsing |
| `lib/Controller/ExportController.php` | API-endpoints voor export |
| `lib/Controller/ImportController.php` | API-endpoints voor import |
| `src/views/AdminSettings.vue` | Frontend-UI voor export/import-tabs |

---

## Toekomstige uitbreidingen

Onder overweging:

- **Selectieve export**: per pagina of pagina-tree exporteren in plaats van alle taal
- **Cross-language-import**: een NL-export als basis gebruiken voor een nieuwe EN-vertaling
- **Diff-import**: alleen wijzigingen importeren ten opzichte van de huidige staat
- **Scheduled exports**: automatische periodieke back-ups
- **Cloud-storage-integratie**: export direct naar S3-compatibele opslag

Bijdragen welkom via [GitHub-issues](https://github.com/voxcloud/intravox/issues).
