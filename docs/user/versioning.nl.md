# IntraVox versiebeheer

IntraVox biedt ingebouwde versie-controle voor alle pagina's, volledig geïntegreerd met Nextcloud's native versie-systeem en GroupFolders.

## Overzicht

Elke wijziging aan een IntraVox-pagina wordt automatisch opgeslagen als nieuwe versie. Hiermee kun je:

- **Versie-geschiedenis bekijken** — zie alle vorige versies van een pagina
- **Versies vergelijken** — bekijk de content van oudere versies
- **Versies herstellen** — zet een pagina terug naar een eerdere staat
- **Wijzigingen tracken** — zie wie wijzigingen maakte en wanneer

## Hoe het werkt

### Technische architectuur

IntraVox-pagina's worden opgeslagen als JSON-bestanden in een Nextcloud-GroupFolder. Versiebeheer gebruikt Nextcloud's ingebouwde `IVersionManager`-API:

```
GroupFolder: IntraVox/
├── nl/
│   ├── homepage.json          ← Huidige versie
│   └── .versions/
│       └── homepage.json/
│           ├── 1736934521     ← Versie van 15 jan 2026, 10:08
│           ├── 1736932860     ← Versie van 15 jan 2026, 09:41
│           └── ...
├── en/
│   └── ...
└── _resources/                ← Gedeelde mediabibliotheek
```

### Voordelen van deze aanpak

1. **Consistentie met Nextcloud Files** — zelfde versiebeheer als reguliere bestanden
2. **Geen extra database** — versies worden door Nextcloud zelf beheerd
3. **GroupFolder-integratie** — versiebeheer werkt automatisch binnen GroupFolders
4. **Betrouwbaar** — gebruikt bewezen Nextcloud-infrastructuur

## Gebruikersinterface

### Versies-tab

Versie-geschiedenis is beschikbaar via de **Versies**-tab in de pagina-zijbalk:

![Versie-zijbalk met versie-geschiedenis](../screenshots/Versioning-1.png)

*De Versies-tab toont de huidige versie bovenaan, gevolgd door de versie-geschiedenis. Elke versie toont auteur, relatieve tijd ("1 sec. geleden", "27 min. geleden") en bestandsgrootte.*

### Een versie bekijken

Klik op een versie in de lijst om de inhoud te bekijken:

![Een versie selecteren en bekijken](../screenshots/Versioning-2.png)

*Selecteer je een oudere versie, dan toont de pagina-content die versie. De titel in de header geeft aan welke versie je bekijkt.*

### Een versie herstellen

Klik op de **herstel-knop** (↺) naast een versie om hem terug te zetten:

![Versie hersteld met success-melding](../screenshots/Versioning-3.png)

*Na herstellen verschijnt een success-melding. De herstelde versie wordt "Huidige versie" en de vorige huidige versie wordt automatisch in de versie-geschiedenis opgeslagen.*

## Versie-informatie

Elke versie toont de volgende informatie:

| Veld | Beschrijving |
|------|--------------|
| **Huidige versie** | Label voor de actieve versie |
| **Auteur** | Gebruiker die de wijziging maakte |
| **Relatieve tijd** | Tijd sinds de wijziging ("1 sec. geleden", "2 uur geleden", "3 dagen geleden") |
| **Bestandsgrootte** | Grootte van het JSON-bestand (bv. "19.5 KB") |

## Herstel-flow

Bij het herstellen van een versie gebeurt het volgende:

```
1. Gebruiker klikt "Herstellen" op versie [X]
   ↓
2. Nextcloud maakt automatisch back-up van huidige versie
   ↓
3. Geselecteerde versie [X] wordt de nieuwe huidige versie
   ↓
4. Versie-geschiedenis wordt bijgewerkt:
   - [Huidig] Herstelde versie (was [X])
   - [Versie] Back-up van vorige huidige (nieuw aangemaakt)
   - [Versie] Andere historische versies
   ↓
5. Pagina-content wordt opnieuw geladen
   ↓
6. Success-melding verschijnt
```

## Integratie met Nextcloud Files

### GroupFolder-versiebeheer

IntraVox gebruikt hetzelfde versiebeheer als Nextcloud Files. Dat betekent:

- **Versie-instellingen** worden gerespecteerd (max aantal versies, retention-policy)
- **Quota** wordt gedeeld met de GroupFolder-quota
- **Beheerders** kunnen versies ook via de Files-app bekijken

### Versies in de Files-app

De JSON-bestanden van IntraVox-pagina's zijn ook zichtbaar in de Nextcloud Files-app:

1. Ga naar **Files → IntraVox**-GroupFolder
2. Navigeer naar de taal-folder (bv. `nl/`)
3. Rechtsklik op een `.json`-bestand
4. Kies **Versies** om de versie-geschiedenis te bekijken

> **Let op**: JSON-bestanden direct via de Files-app bewerken wordt afgeraden. Gebruik altijd de IntraVox-editor.

## Configuratie

### Nextcloud versie-instellingen

IntraVox-versiebeheer volgt de algemene Nextcloud-instellingen:

- **Versions-app** moet geactiveerd zijn
- **GroupFolders** moet geïnstalleerd zijn
- Retention-policy wordt bepaald door Nextcloud-configuratie

### Aanbevolen instellingen

Voor optimaal versiebeheer in IntraVox:

```php
// config/config.php
'versions_retention_obligation' => 'auto',  // Automatisch versie-beheer
'version_expire_days' => 365,               // Bewaar versies 1 jaar
```

## Best practices

### Voor editors

1. **Sla regelmatig op** — elke save maakt een restore-punt
2. **Betekenisvolle wijzigingen** — groepeer gerelateerde wijzigingen in één save
3. **Check voor publiceren** — review de preview voor opslaan

### Voor beheerders

1. **Monitor quota** — versies tellen mee in de GroupFolder-quota
2. **Retention-policy** — configureer hoe lang versies worden bewaard
3. **Back-up-strategie** — versiebeheer vervangt geen back-ups

## Wat triggert een nieuwe versie?

Een nieuwe versie wordt aangemaakt bij:

| Actie | Maakt versie? |
|-------|----------------|
| Pagina-content opslaan (tekst, blokken, structuur) | ✅ Ja |
| Pagina-titel wijzigen | ✅ Ja |
| Navigatie-instellingen wijzigen | ✅ Ja |
| MetaVox-metadata wijzigen | ❌ Nee |
| Tags of comments toevoegen | ❌ Nee |
| Pagina delen | ❌ Nee |

### MetaVox-metadata en versiebeheer

**MetaVox-metadata maakt GEEN nieuwe versies aan.** Dit is bewust:

1. **Aparte opslag** — MetaVox slaat metadata op in een eigen database-tabel (`metavox_file_gf_meta`), niet in het JSON-bestand zelf
2. **Metadata ≠ content** — metadata beschrijft de pagina (auteur, status, tags), terwijl versies *content*-wijzigingen tracken
3. **Nextcloud-standaard** — dit is consistent met hoe Nextcloud bestand-metadata behandelt (tags, comments, shares maken geen versies)
4. **Praktisch** — als elke metadata-wijziging een versie maakte, zou de versie-geschiedenis vervuilen met kleine wijzigingen

```
┌─────────────────────────────────────────────────────────┐
│  IntraVox-pagina (JSON-file)  │  MetaVox-database       │
│  → Wijzigingen maken versies  │  → Geen versies         │
├───────────────────────────────┼─────────────────────────┤
│  homepage.json                │  metavox_file_gf_meta   │
│  - titel                      │  - file_id: 12345       │
│  - content-blokken            │  - author: "Jan"        │
│  - navigatie                  │  - status: "published"  │
│  - settings                   │  - department: "HR"     │
└───────────────────────────────┴─────────────────────────┘
```

> **Tip**: heb je behoefte aan metadata-wijzigingen tracken over tijd? Beschouw dit als een feature-request voor MetaVox (audit-logging).

## Beperkingen

- **Geen versie-labels** — anders dan SharePoint ondersteunt Nextcloud geen custom versie-nummers (1.0, 2.0)
- **Geen major/minor-versies** — alle versies zijn gelijk
- **Geen vergelijkings-view** — diff-view tussen versies is niet beschikbaar
- **Alleen JSON-content** — media in `_resources` heeft eigen versiebeheer
- **Geen metadata-versiebeheer** — MetaVox-metadata-wijzigingen worden niet geversioneerd

## Veelgestelde vragen

### Hoeveel versies worden bewaard?

Dit hangt af van de Nextcloud-configuratie. Standaard bewaart Nextcloud versies via een exponentieel schema: meer recente versies worden vaker bewaard.

### Tellen versies mee in mijn quota?

Ja, versies tellen mee in de GroupFolder-quota. Oude versies worden automatisch opgeruimd volgens de retention-policy.

### Kan ik versies permanent verwijderen?

Via de Nextcloud Files-app kunnen beheerders specifieke versies verwijderen. Dit is niet mogelijk vanuit de IntraVox-interface.

### Wat gebeurt er met media bij herstellen?

Media (afbeeldingen, video's) in de `_resources`-folder heeft eigen versiebeheer. Bij het herstellen van een pagina worden media-bestanden niet automatisch teruggezet.

---

*Laatst bijgewerkt: januari 2026 — IntraVox v0.9.0*
