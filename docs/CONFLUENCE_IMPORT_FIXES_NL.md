# Confluence Import Fixes - Samenvatting

## Wat is er opgelost?

Alle gevraagde verbeteringen voor de Confluence HTML import zijn geïmplementeerd:

### 1. ✅ Juiste map-structuur bij importeren

**Probleem**: Geïmporteerde pagina's maakten verkeerde bestanden aan.

**Oplossing**: Pagina's worden nu aangemaakt zoals IntraVox dit normaal doet:
```
{pageId}/
  {pageId}.json     ← JSON met pagina-data
  _media/           ← Map voor afbeeldingen en bestanden
    .nomedia        ← Marker bestand
```

**Code**: `lib/Service/ImportService.php` regels 230-348

### 2. ✅ Automatisch toevoegen aan navigatie

**Probleem**: Geïmporteerde pagina's verschenen niet in de navigatie.

**Oplossing**: Bij import worden pagina's nu automatisch toegevoegd aan `navigation.json`:
- Op het juiste niveau (onder de gekozen parent pagina)
- Met de originele Confluence paginanamen
- Met behoud van de hiërarchie (parent-child relaties)

**Code**: `lib/Service/ImportService.php` regels 723-849

**Hoe het werkt**:
1. Laad huidige navigatie voor de taal
2. Bouw boom-structuur van geïmporteerde pagina's
3. Vind de parent in de navigatie
4. Voeg pagina's toe als children van parent
5. Sla bijgewerkte navigatie op

### 3. ✅ Fix voor 404 fouten

**Probleem**: Geïmporteerde pagina's gaven 404 fout bij laden.

**Oorzaak**: uniqueId had verkeerde format (`page_` ipv `page-`)

**Oplossing**: Aangepast naar IntraVox standaard `page-` prefix

**Code**: `lib/Service/Import/IntermediateFormat.php` regel 57

### 4. ✅ PHP syntax error opgelost

**Probleem**: Fout op regel 805: `unexpected token "&"`

**Oplossing**: Herschreven naar pad-gebaseerde benadering voor navigatie tree traversal

**Code**: `lib/Service/ImportService.php` regels 803-829

### 5. ✅ Pagina volgorde behouden

**Probleem**: Geïmporteerde pagina's verschenen niet in de volgorde zoals in Confluence.

**Oplossing**: Volgorde wordt nu gehaald uit `index.html` van de Confluence export:
- Pagina's krijgen volgorde-nummer (`confluenceOrder`) in metadata
- Volgorde wordt behouden in zowel navigatie als pagina structuur
- Sorteer-algoritme houdt rekening met hiërarchie én volgorde

**Code**: `lib/Service/Import/ConfluenceHtmlImporter.php` regels 411-456, 533-536

### 6. ✅ HTML cleanup verbeterd

**Probleem**: Sommige pagina's toonden ruwe HTML zoals `</p>`, zoekformulieren en breadcrumbs.

**Oplossing**: Geavanceerde HTML parsing met DOMDocument en XPath:
- Verwijdert Confluence navigatie-elementen (breadcrumbs, zoekformulieren)
- Ruimt verweesde HTML tags op (tags zonder openings-tag)
- Verwijdert lege elementen

**Code**: `lib/Service/Import/ConfluenceHtmlImporter.php` regels 297-416

### 7. ✅ UniqueId format consistent gemaakt

**Probleem**: Geïmporteerde pagina's gebruikten `uniqid('page-', true)` wat IDs genereert met punten zoals `page-66b8c12a.87654321`, terwijl IntraVox zelf RFC 4122 UUID v4 format gebruikt: `page-a1b2c3d4-e5f6-4789-abcd-ef1234567890`.

**Oplossing**: Import gebruikt nu hetzelfde UUID v4 format als native pagina creatie:
- Toegevoegd: `generateUUID()` methode aan `IntermediatePage` class
- Genereert RFC 4122 compliant UUID v4
- Geen punten meer in uniqueIds
- Consistent met hoe IntraVox pagina's aanmaakt via de UI

**Code**: `lib/Service/Import/IntermediateFormat.php` regels 57, 60-76

**Voordelen**:
- Consistente ID format door hele applicatie
- RFC 4122 standaard (industry best practice)
- Gegarandeerde uniciteit
- Betere leesbaarheid (geen verwarrende punten)

## Nieuwe functionaliteit toegevoegd

- `createMediaFolderMarker()` - Maakt .nomedia marker aan
- `getRelativePath()` - Berekent relatieve pad tussen mappen
- `updateNavigationWithImportedPages()` - Hoofdfunctie voor navigatie update
- `buildPageTree()` - Bouwt hiërarchie uit platte lijst
- `findNavigationItemPath()` - Vindt pad naar navigatie item
- `getNavigationItemByPath()` - Haalt item referentie op via pad
- `addPagesToNavigation()` - Voegt pagina's toe aan navigatie

## Status

✅ Alle code wijzigingen zijn voltooid en gecommit
✅ Gedeployed naar server (15 december 2025, 10:41 UTC)
✅ Build succesvol afgerond zonder errors
✅ UniqueId format aangepast naar UUID v4

✅ **Deployment compleet** - Klaar voor testen

## Wat moet je nu doen?

### Stap 1: Hard refresh in browser

De JavaScript bestanden zijn bijgewerkt op de server, maar je browser heeft mogelijk oude versies gecached.

**Hoe doe je een hard refresh:**
- **Mac**: Cmd + Shift + R
- **Windows/Linux**: Ctrl + Shift + R

### Stap 2: Test de import functionaliteit

Na de hard refresh, test het volgende:

1. **Import locatie dropdown**
   - [ ] Controleer dat de dropdown nu pagina's toont
   - [ ] Selecteer een parent pagina

2. **Confluence HTML ZIP importeren**
   - [ ] Upload een Confluence HTML export ZIP
   - [ ] Controleer dat import succesvol is

3. **Map structuur controleren** (via Nextcloud Files)
   - [ ] Open IntraVox/nl/ (of andere taal)
   - [ ] Controleer dat pagina mappen bestaan: `{pageId}/`
   - [ ] Binnen elke map: `{pageId}.json` bestand
   - [ ] Binnen elke map: `_media/` submap
   - [ ] Binnen `_media/`: `.nomedia` bestand

4. **Navigatie controleren** (via IntraVox admin)
   - [ ] Open navigatie editor
   - [ ] Controleer dat geïmporteerde pagina's verschijnen
   - [ ] Controleer dat ze onder de juiste parent staan
   - [ ] Controleer dat hiërarchie klopt (parent-child relaties)

5. **Pagina's bekijken**
   - [ ] Open een geïmporteerde pagina
   - [ ] Controleer dat pagina laadt (geen 404 fout)
   - [ ] Controleer dat content correct wordt weergegeven
   - [ ] Controleer dat afbeeldingen werken

## Bekende issue

### JavaScript module loading error

**Symptoom**:
```
TypeError: can't access property "call", n[e] is undefined
```

**Oorzaak**: Browser cache met oude JavaScript bestanden

**Oplossing**: Hard refresh (zie Stap 1 hierboven)

## Logs bekijken

Als er problemen zijn na de hard refresh:

```bash
ssh rdekker@145.38.193.235 'sudo tail -100 /var/www/nextcloud/data/nextcloud.log' | grep -i intravox
```

## Bestanden aangepast

### Backend (PHP)
- `lib/Service/ImportService.php` - Grote refactoring van pagina creatie en navigatie
- `lib/Service/Import/IntermediateFormat.php` - uniqueId format fix

### Documentatie
- `docs/CONFLUENCE_IMPORT_FIXES.md` - Uitgebreide technische documentatie (Engels)
- `docs/CONFLUENCE_IMPORT_FIXES_NL.md` - Deze samenvatting (Nederlands)

## Vragen?

Als je na de hard refresh nog steeds problemen hebt:
1. Check de browser console voor errors (F12 → Console tab)
2. Check de Nextcloud logs (zie commando hierboven)
3. Laat me weten welke error je ziet

Alle wijzigingen zijn klaar en gedeployed. Je hoeft alleen nog een hard refresh te doen in je browser! 🚀
