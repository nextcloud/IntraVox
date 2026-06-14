# IntraVox autorisatie-model

IntraVox gebruikt Nextcloud's native GroupFolder-permissies voor autorisatie. Dat betekent dat toegangscontrole volledig wordt beheerd via Nextcloud's bestaande permissie-systeem — geen aparte permissie-configuratie nodig in IntraVox.

## Overzicht

```
+------------------------------------------------------------------+
|                       Nextcloud-server                            |
|  +--------------------------------------------------------------+ |
|  |                     GroupFolders-app                         | |
|  |  +----------------------------------------------------------+ |
|  |  |                 IntraVox-GroupFolder                     | |
|  |  |  +--------------------+  +--------------------+          | |
|  |  |  | Groep-permissies   |  |    ACL-regels      |          | |
|  |  |  | (basis-toegang)    |  |  (fijn-granulair)  |          | |
|  |  |  +--------------------+  +--------------------+          | |
|  |  +----------------------------------------------------------+ |
|  +--------------------------------------------------------------+ |
|                              |                                    |
|                              v                                    |
|  +--------------------------------------------------------------+ |
|  |                      IntraVox-app                            | |
|  |        PermissionService leest permissies                    | |
|  |        en handhaaft ze op alle operaties                     | |
|  +--------------------------------------------------------------+ |
+------------------------------------------------------------------+
```

## Permissie-typen

IntraVox respecteert de standaard Nextcloud-permissie-bits:

| Permissie | Bit | Beschrijving |
|-----------|-----|--------------|
| Lezen | 1 | Pagina's en content bekijken |
| Bijwerken | 2 | Bestaande pagina's bewerken |
| Aanmaken | 4 | Nieuwe pagina's maken |
| Verwijderen | 8 | Pagina's verwijderen |
| Delen | 16 | Vereist voor RSS-feed-toegang (publieke endpoints vereisen Lezen + Delen) |

## Hoe permissies werken

### 1. Basis-permissies (GroupFolder-groepen)

Wanneer een groep wordt toegevoegd aan de IntraVox-GroupFolder, ontvangen alle leden van die groep de geconfigureerde basis-permissies. Dit is de eerste laag van toegangscontrole.

Voorbeeld:

- Groep "Medewerkers" heeft Lezen-permissie op IntraVox-folder
- Groep "Editors" heeft Lezen + Schrijven + Aanmaken
- Groep "Admins" heeft Alle permissies

### 2. ACL-regels (fijn-granulaire controle)

Met de GroupFolders ACL-feature ingeschakeld kunnen beheerders specifiekere permissies op submappen instellen. ACL-regels overrulen basis-permissies voor specifieke paden.

Voorbeeld:

- `/nl/afdelingen/hr` — HR-groep volledige toegang, anderen alleen-lezen
- `/nl/afdelingen/sales` — Sales-groep volledige toegang, anderen alleen-lezen

### 3. Permissie-overerving

Permissies worden van ouder- naar child-folders overgeërfd:

- Een child-folder kan niet meer permissies hebben dan zijn ouder
- ACL-regels op ouder-folders raken alle children
- Specifiekere regels (diepere paden) overrulen minder specifieke regels

## Permissies opzetten

### Stap 1: maak GroupFolder

1. Ga naar Nextcloud-beheer → GroupFolders
2. Maak een folder met de naam "IntraVox"
3. Voeg groepen toe die toegang moeten hebben

### Stap 2: configureer basis-permissies

Stel voor elke groep het juiste permissie-niveau in:

| Groep | Aanbevolen permissies | Automatisch aangemaakt? |
|-------|------------------------|---------------------------|
| IntraVox Users | Lezen, Delen | Ja |
| IntraVox Editors | Lezen, Schrijven, Aanmaken | Ja |
| IntraVox Admins | Alles | Ja |
| Custom-groepen (bv. afdelingsmanagers) | Lezen, Schrijven, Aanmaken, Verwijderen, Delen | Nee — handmatig toevoegen |

> **Waarom Delen?** De RSS-feed is een publiek endpoint (geen gebruikers-sessie). GroupFolders vereist zowel Lezen als Delen-permissies voor folders om zichtbaar te zijn in publieke requests. Zonder Delen blijven feeds leeg.

### Stap 3: ACL inschakelen (optioneel)

Voor fijn-granulaire controle:

1. Schakel "Advanced Permissions" in op de GroupFolder
2. Navigeer naar submappen in Nextcloud Files
3. Klik op het share-icoon en configureer ACL-regels

### Voorbeeld: afdelings-gebaseerde toegang

```
IntraVox/
├── nl/
│   ├── afdelingen/
│   │   ├── hr/          → HR-groep: volledige toegang, anderen: lezen
│   │   ├── sales/       → Sales-groep: volledige toegang, anderen: lezen
│   │   ├── marketing/   → Marketing-groep: volledige toegang, anderen: lezen
│   │   └── it/          → IT-groep: volledige toegang, anderen: lezen
│   └── nieuws/          → Editors-groep: volledige toegang, anderen: lezen
└── en/
    └── (zelfde structuur)
```

## Permissie-checks in IntraVox

IntraVox checkt permissies op meerdere niveaus:

### API-niveau

Elke API-call valideert permissies vóór uitvoering:

- `GET /api/page` — vereist Lezen
- `PUT /api/page` — vereist Schrijven
- `POST /api/page` — vereist Aanmaken
- `DELETE /api/page` — vereist Verwijderen

### UI-niveau

De frontend past zich aan op basis van permissies:

- Bewerk-knoppen alleen zichtbaar bij Schrijfrechten
- Pagina-aanmaak-opties alleen bij Aanmaken-rechten
- Verwijder-opties alleen bij Verwijderen-rechten

### Navigatie

Navigatie-items worden gefilterd op basis van pagina-permissies — gebruikers zien alleen pagina's waartoe ze toegang hebben.

## Problemen oplossen

### Gebruiker ziet een pagina niet

1. Check of gebruiker lid is van een groep met toegang tot de IntraVox-GroupFolder
2. Check ACL-regels op het specifieke folder-pad
3. Verifieer dat het pagina-bestand op de verwachte locatie bestaat

### Gebruiker kan een pagina niet bewerken

1. Verifieer dat de groep van de gebruiker Schrijfrechten heeft op de GroupFolder
2. Check of ACL-regels Schrijven beperken op dat pad
3. Check ouder-folder-permissies (child kan niet meer dan ouder)

### RSS-feed van gebruiker is leeg

1. Check dat de groep van de gebruiker **Delen**-permissie heeft op de GroupFolder (basis-niveau)
2. Met ACL: verifieer Delen-permissie op de taal-folder (`nl/`, `en/`, etc.) en alle ouder-folders
3. Verifieer dat "Gebruikers toestaan via link en e-mail te delen" aan staat in Nextcloud-beheer → Delen
4. Zie [RSS-feed](../user/rss-feeds.md#beheerder-setup) voor de volledige setup-gids

### Navigatie toont pagina's die gebruiker niet kan openen

Dit zou niet moeten gebeuren bij correct geconfigureerde permissies. Check:

1. Navigatie-bestand-permissies versus pagina-bestand-permissies
2. Cache-issues — probeer Nextcloud-cache te wissen

## Technische implementatie

De `PermissionService`-klasse handelt alle permissie-logica af:

```php
// Check of gebruiker een pad kan lezen
$canRead = $permissionService->canRead('nl/afdelingen/hr');

// Check of gebruiker naar een pad kan schrijven
$canWrite = $permissionService->canWrite('nl/afdelingen/hr');

// Haal volledig permissies-object voor API-response
$permissions = $permissionService->getPermissionsObject('nl/afdelingen/hr');
// Returns: { canRead: true, canWrite: false, canCreate: false, ... }
```

De service:

1. Haalt basis-permissies op via GroupFolder-groep-lidmaatschap
2. Bevraagt ACL-regels uit de database
3. Past regels toe van minst-specifiek naar meest-specifiek pad
4. Geeft de effectieve permissie-bitmask terug

## Best practices

1. **Gebruik groepen** — wijs permissies altijd aan groepen toe, niet aan individuele gebruikers
2. **Principle of least privilege** — begin met alleen-lezen en voeg permissies toe waar nodig
3. **Documenteer je structuur** — houd bij welke groepen toegang hebben tot wat
4. **Test grondig** — test na permissie-setup met gebruikers uit elke groep
5. **Regelmatige audits** — review groep-lidmaatschappen en ACL-regels periodiek
6. **RSS-feed vereist Delen** — de RSS-feed is een publiek endpoint. GroupFolders vereist zowel Lezen als Delen voor folders om zichtbaar te zijn in publieke (niet-geauthenticeerde) requests. Meldt een gebruiker een lege feed? Check dat hun groep Delen-permissie heeft op de relevante folders.
