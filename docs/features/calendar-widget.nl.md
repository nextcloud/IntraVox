# Calendar-widget

De calendar-widget toont aankomende (of voorbije) afspraken uit Nextcloud-kalenders en externe ICS-feeds op je intranet-pagina's. Events worden weergegeven met gekleurde datum-badges, ondersteunen terugkerende afspraken, en de layout past zich automatisch aan de beschikbare ruimte aan.

![Calendar-widget](../screenshots/calendarwidget-intro.gif)

*Calendar-widget met gekleurde datum-badges, responsieve meerkoloms layout en achtergrond-thema's*

## Features

- **Multi-kalender-ondersteuning** — selecteer meerdere Nextcloud-kalenders voor een samengevoegde view
- **Externe ICS-feeds** — voeg ICS-kalender-URL's toe vanuit elke bron (Moodle, Canvas, Brightspace, Google Calendar, etc.). Events zijn zichtbaar voor alle pagina-bezoekers zonder individuele kalender-abonnementen
- **LMS-deeplinks** — events uit LMS-feeds linken direct naar het event in het bron-systeem (Canvas, Brightspace, Moodle)
- **Gekleurde datum-badges** — elk event toont een datum-badge in de kalender-kleur (paars voor externe feeds)
- **Terugkerende events** — RRULE-patronen worden automatisch uitgeklapt naar individuele occurrences
- **Responsieve grid** — 1, 2 of 3 kolommen afhankelijk van beschikbare ruimte
- **Klikbare events** — Nextcloud-events openen in de Calendar-app, externe events in het bron-systeem
- **Voorbije events** — toon events uit het verleden (afgelopen week, maand, of 3 maanden)
- **Achtergrond-thema's** — geen, licht of primary-achtergrond met automatisch contrast

## Layout

De widget gebruikt een responsieve grid die zich aanpast aan container-breedte via CSS container-queries:

| Container-breedte | Kolommen | Typische plaatsing |
|--------------------|----------|---------------------|
| < 300px | 1 kolom | Zij-kolom |
| 300–499px | 1 kolom | Smalle hoofd-content |
| 500–799px | 2 kolommen | Hoofd-content-gebied |
| 800px+ | 3 kolommen | Brede content / full-width |

Geen handmatige kolom-configuratie nodig — de layout past zich automatisch aan.

## Configuratie

### Widget toevoegen

1. Klik **+ Widget toevoegen** in edit-modus
2. Selecteer **Calendar** uit de widget-picker
3. De editor opent automatisch — selecteer kalenders en configureer opties
4. Klik **Opslaan** om toe te passen

### Instellingen

| Instelling | Beschrijving | Standaard |
|------------|---------------|-----------|
| **Widget-titel** | Optionele titel boven het widget | *(leeg)* |
| **Achtergrond-kleur** | Geen, licht of primary | Geen |
| **Kalenders** | Checkbox-lijst van beschikbare Nextcloud-kalenders (persoonlijk + gedeeld) | *(niets geselecteerd)* |
| **Externe ICS-feeds** | Voeg tot 5 externe ICS-kalender-URL's toe (alleen HTTPS) | *(geen)* |
| **Datum-bereik** | Tijd-periode om events uit te tonen | Aankomend (30 dagen) |
| **Aantal events** | Maximaal te tonen events (1–20) | 5 |
| **Toon tijd** | Toon event start/eind-tijd | Aan |
| **Toon locatie** | Toon event-locatie | Uit |

### Datum-bereik-opties

**Toekomst:**

| Optie | Periode |
|-------|---------|
| Aankomend (30 dagen) | Nu → 30 dagen vooruit |
| Deze week | Maandag → zondag van huidige week |
| Komende 2 weken | Nu → 14 dagen vooruit |
| Deze maand | 1e → laatste dag van huidige maand |
| Komende 3 maanden | Nu → 3 maanden vooruit |
| Komende 6 maanden | Nu → 6 maanden vooruit |
| Komend jaar | Nu → 1 jaar vooruit |

**Verleden:**

| Optie | Periode |
|-------|---------|
| Afgelopen week | 7 dagen terug → nu |
| Afgelopen maand | 30 dagen terug → nu |
| Afgelopen 3 maanden | 3 maanden terug → nu |

## Kalender-selectie

De editor toont alle Nextcloud-kalenders die beschikbaar zijn voor de huidige gebruiker:

- Persoonlijke kalenders
- Kalenders direct gedeeld met de gebruiker
- Kalenders gedeeld met groepen waar de gebruiker in zit

> **Let op:** ICS-subscriptions (via "Nieuwe subscription van link" in Nextcloud Calendar) verschijnen niet in de kalender-selector. Gebruik het **Externe ICS-feeds**-veld — dat zorgt dat alle pagina-bezoekers dezelfde events zien, ongeacht hun individuele Nextcloud-subscriptions.

Elke kalender verschijnt met z'n kleur-dot en weergavenaam. Selecteer meerdere kalenders voor een samengevoegde view — events uit alle geselecteerde kalenders worden gecombineerd en chronologisch gesorteerd.

### Gedeelde kalender-setup

Om een kalender beschikbaar te maken in de widget:

1. Open de **Nextcloud Calendar**-app
2. Klik op het drie-puntjes-menu van een kalender → **Kalender bewerken**
3. Onder **Kalender delen**, voeg gebruikers of groepen toe (bv. "IntraVox Users")
4. De kalender verschijnt nu in de widget-editor voor die gebruikers

## Externe ICS-feeds

Voeg externe ICS-kalender-URL's toe om events te tonen uit elk systeem dat een ICS-feed levert. Dit is de aanbevolen manier voor LMS-kalender-integratie (Moodle, Canvas, Brightspace).

### Hoe het werkt

- De editor biedt een **Externe ICS-feeds**-sectie onder de kalender-selector
- Plak een HTTPS-URL naar een ICS-feed en klik **Toevoegen**
- Tot 5 feeds per widget
- Events uit externe feeds zijn **zichtbaar voor alle pagina-bezoekers** (geauthenticeerd en publieke share), in tegenstelling tot Nextcloud-kalender-subscriptions die per-user zijn
- Externe events worden getoond met een paarse datum-badge om ze te onderscheiden van Nextcloud-events
- Resultaten worden 30 minuten gecached om externe-server-belasting te verminderen

### LMS ICS-URLs

| LMS | Hoe je de ICS-URL krijgt |
|-----|---------------------------|
| **Canvas** | Calendar → Calendar Feed (URL kopiëren) |
| **Moodle** | Calendar → Export calendar → Get calendar URL |
| **Brightspace** | Calendar → Subscribe (URL kopiëren) |
| **Google Calendar** | Calendar-settings → Integrate calendar → Secret address in iCal-format |

### Event-deeplinks

Bij klikken op een extern event opent het bron-systeem:

| Bron | Link-gedrag |
|------|-------------|
| **Canvas** | Opent het event in Canvas Calendar (Canvas levert URL in ICS) |
| **Brightspace** | Opent de event-detail-pagina in Brightspace (URL geconstrueerd uit UID) |
| **Moodle** | Opent de calendar-dag-view in Moodle (URL geconstrueerd uit UID) |
| **Andere ICS-bronnen** | Opent de feed-domein-homepage |

### Beveiliging

- Alleen HTTPS-URL's geaccepteerd
- ICS-feeds worden server-side opgehaald (geen directe browser-requests naar externe servers)
- Feed-content wordt gecached en geparsed via Sabre VObject
- Privé/vertrouwelijke events worden gefilterd

## Event-weergave

Elk event toont:

- **Datum-badge** — dag-nummer en maand-afkorting in een gekleurd vierkant (kalender-kleur)
- **Nabijheids-label** — "Vandaag" of "Morgen" boven de titel waar van toepassing
- **Event-titel** — tot 2 regels, wraps i.p.v. afkappen
- **Tijd** — start- en eind-tijd, of "Hele dag" voor all-day-events
- **Locatie** — getoond onder tijd indien ingeschakeld

### Op events klikken

- **Nextcloud-events** — openen de Nextcloud-Calendar-app op de dag-view van die datum
- **Externe ICS-events** — openen het event in het bron-systeem (zie [Event-deeplinks](#event-deeplinks))
- **Publiek gedeelde pagina's** — Nextcloud-events zijn niet klikbaar (Calendar vereist login). Externe events blijven klikbaar en openen in nieuw tabblad

### Terugkerende events

Events met recurrence-regels (RRULE) — zoals weekvergaderingen of maand-reviews — worden automatisch uitgeklapt naar individuele occurrences binnen het geselecteerde datum-bereik. Elke occurrence verschijnt als apart event met de juiste datum.

## Achtergrond-kleuren

De widget ondersteunt drie achtergrond-stijlen:

| Stijl | Uiterlijk |
|-------|-----------|
| **Geen** | Transparant, geen padding |
| **Licht** | Lichtgrijze achtergrond met padding |
| **Primary** | Primary-thema-kleur (bv. donkerblauw) met witte tekst |

Tekst-kleuren passen zich automatisch aan voor goed contrast op elke achtergrond.

## Tips

- **Kalender-kleuren**: gebruik onderscheidende kleuren in Nextcloud Calendar voor visuele onderscheiding in samengevoegde views
- **Datum-bereik**: kortere bereiken (deze week, 2 weken) werken goed voor drukke kalenders; langere bereiken (3–6 maanden) voor planning/overzicht-pagina's
- **Event-limiet**: toon 5–10 events voor sidebar-widgets, tot 20 voor hoofd-content-gebieden
- **Terugkerende events**: weekvergaderingen tonen meerdere occurrences — pas de event-limiet aan
- **Gedeelde kalenders**: maak een dedicated "Organisatie-events"-kalender gedeeld met alle gebruikers voor bedrijfs-brede events
- **LMS-integratie**: gebruik externe ICS-feeds voor schoolkalenders — alle studenten en staf zien dezelfde events zonder individuele subscriptions
- **Mix bronnen**: combineer Nextcloud-kalenders met externe ICS-feeds in één widget voor een geïntegreerd overzicht

## Screenshots

![Calendar-widget-layout](../screenshots/Calendarwidget-layout.png)

*2-koloms layout in hoofd-content met getimede en hele-dag-events*

![Calendar-widget-sidebar](../screenshots/Calendarwidget-sidebar.png)

*Compacte 1-koloms layout in zij-kolom*

![Calendar-widget-editor](../screenshots/Calendarwidget-editor.png)

*Widget-editor met kalender-selectie en datum-bereik-opties*

![Calendar-widget 3-koloms](../screenshots/Calendarwidget-primary.png)

*Responsieve 3-koloms grid in een full-width rij*

## Vereisten

- IntraVox 1.2.0 of hoger (externe ICS-feeds vereisen unreleased versie)
- Nextcloud Calendar-app geïnstalleerd (voor Nextcloud-kalender-selectie)
- Minstens één kalender of externe ICS-feed geconfigureerd
