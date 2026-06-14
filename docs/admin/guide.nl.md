# IntraVox beheerdersgids

Deze gids behandelt installatie, configuratie en onderhoud van IntraVox voor Nextcloud-beheerders.

**Gerelateerde documentatie:**

- [Beheer-instellingen](settings.md) — Demo-data en video-services-configuratie
- [Engagement-instellingen](engagement.md) — Reacties en comments configureren
- [Autorisatie](authorization.md) — Permissies en toegangscontrole
- [Schaalbaarheid & enterprise-readiness](scalability.md) — Performance, caching, rate limiting, AVG
- [Scenario's](scenarios.md) — Praktische recepten (goedkeurings-workflows, afdelings-intranetten)
- [Architectuur](../architecture/overview.md) — Technische architectuur
- [Engagement-architectuur](../features/engagement-architecture.md) — Engagement-systeem technische details

## Systeemvereisten

| Component | Minimum | Aanbevolen |
|-----------|---------|------------|
| Nextcloud | 32+ | 32+ |
| PHP | 8.1+ | 8.2+ |
| PHP memory_limit | 256 MB | 512 MB |
| GroupFolders-app | Vereist | Vereist |

> **Let op**: de standaard PHP `memory_limit` van 128 MB is onvoldoende voor IntraVox. Demo-data-installatie en grote pagina's vereisen minimaal 256 MB. Verhoog dit in je `php.ini`:
> ```ini
> memory_limit = 512M
> ```

## Installatie

### Optie 1: Nextcloud App Store (aanbevolen)

1. Ga naar Nextcloud-beheer → Apps
2. Zoek op "IntraVox"
3. Klik "Download en inschakelen"

### Optie 2: handmatige installatie

1. Download de laatste release van GitHub
2. Pak uit in `custom_apps/intravox/`
3. Schakel in via Beheer → Apps of `occ app:enable intravox`

```bash
cd /var/www/nextcloud/custom_apps
wget https://github.com/voxcloud/intravox/releases/latest/download/intravox.tar.gz
tar -xzf intravox.tar.gz
sudo -u www-data php /var/www/nextcloud/occ app:enable intravox
```

## Permissie-groepen

IntraVox maakt automatisch drie permissie-groepen aan tijdens installatie:

| Groep | Doel | Permissies |
|-------|------|------------|
| **IntraVox Admins** | Volledige beheerderstoegang | Lezen, Schrijven, Aanmaken, Verwijderen, Delen |
| **IntraVox Editors** | Content-bewerken | Lezen, Schrijven, Aanmaken |
| **IntraVox Users** | Standaard leestoegang | Lezen, Delen |

### Automatische admin-synchronisatie

Wanneer IntraVox wordt geïnstalleerd of opnieuw ingeschakeld, worden **alle Nextcloud-beheerders** (leden van de `admin`-groep) automatisch toegevoegd aan de `IntraVox Admins`-groep. Dit zorgt voor:

- Alle NC-admins hebben volledige IntraVox-rechten
- CLI-installaties (`occ app:enable intravox`) werken correct
- Consistent gedrag ongeacht wie de app installeert

### Gebruikers toevoegen

Om gebruikers toegang te geven tot IntraVox:

1. **Voor leestoegang**: voeg gebruikers toe aan de `IntraVox Users`-groep
2. **Voor bewerk-toegang**: voeg gebruikers toe aan de `IntraVox Editors`-groep (kunnen pagina's aanmaken en bewerken, maar niet verwijderen)
3. **Voor beheerder-toegang**: voeg gebruikers toe aan de `IntraVox Admins`-groep (of maak ze Nextcloud-beheerder)

Groep-lidmaatschap beheer je via:

- Nextcloud-beheer → Gebruikers → Bewerk gebruiker → Groepen
- Command line: `occ group:adduser "IntraVox Editors" gebruikersnaam`

## Initiële setup

### Automatische setup

Wanneer IntraVox voor het eerst wordt geopend:

1. Worden de permissie-groepen (`IntraVox Admins`, `IntraVox Editors` en `IntraVox Users`) aangemaakt
2. Worden Nextcloud-admins gesynchroniseerd naar `IntraVox Admins`
3. Wordt een GroupFolder met naam "IntraVox" aangemaakt
4. Wordt de basis-folder-structuur opgezet
5. Worden navigatie-bestanden geïnitialiseerd

### Handmatige setup

Als automatische setup faalt, maak de GroupFolder handmatig aan:

1. Ga naar Beheer → GroupFolders
2. Maak een nieuwe folder met de exacte naam "IntraVox"
3. Voeg minstens één groep met permissies toe

## GroupFolder-configuratie

### Groepen toevoegen

1. Ga naar Beheer → GroupFolders
2. Vind de "IntraVox"-folder
3. Klik "Groep toevoegen"
4. Selecteer de groep en stel permissies in

### Aanbevolen groep-setup

| Groep | Doel | Permissies |
|-------|------|------------|
| everyone / all-users | Alle medewerkers | Lezen, Delen |
| intranet-editors | Content-creators | Lezen, Schrijven, Aanmaken, Delen |
| intranet-admins | Volledige beheerders | Alles |

> **Belangrijk**: gebruikersgroepen hebben zowel **Lezen** als **Delen** nodig. De Delen-permissie is vereist voor de RSS-feed — zonder die verbergt GroupFolders folders voor publieke feed-requests. Dit geldt ook voor ACL-regels op submappen.

### ACL inschakelen (Advanced Permissions)

Voor afdelings-gebaseerde toegangscontrole:

1. Klik in GroupFolders op het settings-icoon van IntraVox
2. Schakel "Advanced Permissions" in
3. Navigeer naar submappen in Nextcloud Files
4. Gebruik het sharing-paneel om ACL-regels in te stellen

Zie [Autorisatie](authorization.md) voor uitgebreide permissie-setup.

## Taal-configuratie

IntraVox ondersteunt meerdere talen. Elke taal heeft een eigen content-folder. **Belangrijk:** elke gebruiker ziet de content-folder die match't met zijn Nextcloud-taal-instelling (Instellingen → Persoonlijk → Taal). Match de taal van een gebruiker geen beschikbare content-folder, dan ziet hij een leeg intranet in plaats van de verwachte content.

```
IntraVox/
├── nl/           # Nederlandse content
├── en/           # Engelse content
├── de/           # Duitse content
└── fr/           # Franse content
```

### Een nieuwe taal toevoegen

1. Maak de taal-folder aan in de IntraVox-GroupFolder
2. Maak `navigation.json` met navigatie-structuur
3. Maak `footer.json` met footer-content
4. Maak `home.json` voor de homepage

Voorbeeld-minimum-structuur:

```
IntraVox/
└── es/
    ├── navigation.json
    ├── footer.json
    └── home.json
```

## Demo-data

IntraVox bevat demo-content om je snel op weg te helpen. Demo-data kan worden geïnstalleerd en beheerd via **Nextcloud-beheerinstellingen → IntraVox**.

### Snelstart

1. Ga naar **Nextcloud-beheerinstellingen → IntraVox**
2. Klik **Installeren** naast je voorkeurs-taal
3. De GroupFolder en permissie-groepen worden automatisch aangemaakt

> **Belangrijk:** je Nextcloud-taal-instelling moet matchen met de demo-data-taal. Bijvoorbeeld: installeer je de Nederlandse demo-data, zet dan je taal op Nederlands (Instellingen → Persoonlijk → Taal). Anders zie je een lege welkomstpagina in plaats van de demo-content.

### Beschikbare talen

| Taal | Content |
|------|---------|
| Nederlands 🇳🇱 | Volledig intranet |
| Engels 🇬🇧 | Volledig intranet |
| Duits 🇩🇪 | Alleen homepage |
| Frans 🇫🇷 | Alleen homepage |

### Command-line-installatie

```bash
sudo -u www-data php occ intravox:import-demo-data --language=nl
```

Zie [Beheer-instellingen](settings.md) voor uitgebreid demo-data-beheer.

## Onderhoud

### Cache wissen

IntraVox gebruikt Nextcloud's caching. Wissen:

```bash
sudo -u www-data php occ files:scan --all
sudo -u www-data php occ maintenance:repair
```

### Back-up

De IntraVox-GroupFolder bevat alle content. Back-up-strategieën:

1. **Bestand-back-up**: include de GroupFolder in je bestand-back-ups
2. **Nextcloud-back-up**: standaard Nextcloud-back-up bevat GroupFolders
3. **Export**: kopieer de IntraVox-folder-structuur voor migratie

### Health check

Een publiek endpoint is beschikbaar voor monitoring en orchestration (bv. Kubernetes liveness-probes, uptime-monitoring):

```
GET /apps/intravox/api/health
→ {"status": "ok", "app": "intravox", "version": "1.3.0"}
```

### Audit-logs

Administratieve operaties worden gelogd met de `IntraVox Audit:`-prefix. Filter hierop in je SIEM of log-aggregator:

```bash
grep "IntraVox Audit" /var/www/nextcloud/data/nextcloud.log
```

Gelogde operaties: bulk-delete/move/update (met pagina-ID's en handelende gebruiker), licentie-sleutel-wijzigingen, organisatie-instellingen, engagement-instellingen.

### Log-bestanden

IntraVox logt naar het Nextcloud-log. Bekijken:

```bash
tail -f /var/www/nextcloud/data/nextcloud.log | grep -i intravox
```

Of via Nextcloud-beheer → Logging.

### Anonieme gebruiks-statistieken

IntraVox kan periodiek anonieme gebruiks-statistieken versturen om de app te helpen verbeteren. Dit is opt-in en kan worden in-/uitgeschakeld via **Instellingen → IntraVox**.

![Anonieme gebruiks-statistieken-paneel](../screenshots/Statistics.png)

*Schakel **Deel anonieme gebruiksstatistieken** in of uit om telemetrie te activeren. Het paneel toont wanneer het laatste rapport is verstuurd.*

**Wat we verzamelen:**

- Aantal pagina's per taal
- Totaal aantal gebruikers en actieve gebruikers
- IntraVox-, Nextcloud- en PHP-versienummers
- Een unieke hash van je instantie-URL (privacy-vriendelijke identifier)

**Wat we nooit verzamelen:**

- Pagina-inhoud of -titels
- Gebruikersnamen of e-mailadressen
- Je werkelijke server-URL
- Persoonlijke of gevoelige data

Geen persoonlijk identificeerbare informatie verlaat de server. Rapporten worden maximaal eenmaal per dag verstuurd.

## Problemen oplossen

### "GroupFolder niet gevonden"-fout

1. Verifieer dat de GroupFolders-app is ingeschakeld
2. Controleer dat een folder met exact de naam "IntraVox" bestaat
3. Zorg dat de huidige gebruikers-groep toegang heeft

```bash
sudo -u www-data php occ app:list | grep groupfolders
```

### Permissies werken niet

1. Check dat gebruiker lid is van een groep met GroupFolder-toegang
2. Verifieer ACL-regels bij Advanced Permissions
3. Check Nextcloud-log op permissie-fouten
4. Probeer: `occ groupfolders:scan`

### Pagina's verschijnen niet

1. Verifieer dat JSON-bestanden geldig zijn (geen syntax-fouten)
2. Check bestandsrechten op de server
3. Zorg voor correcte folder-structuur
4. Check browser-console op JavaScript-fouten

### Navigatie wordt niet bijgewerkt

1. Wis browser-cache
2. Verifieer dat `navigation.json` geldig JSON is
3. Check dat pagina-uniqueIds matchen tussen navigatie en pagina's

### Afbeeldingen/video's laden niet

1. Verifieer dat bestanden in de `_media/`-submap staan
2. Check bestandsrechten
3. Zorg dat bestandspaden in JSON met werkelijke bestandsnamen matchen
4. Probeer opnieuw te uploaden via Nextcloud Files
5. Voor video's: check dat het video-domein op de whitelist staat in admin-instellingen

## Performance

### Optimalisatie-tips

1. **Afbeelding-groottes**: houd afbeeldingen onder 500 KB voor snelle laadtijden
2. **Pagina-structuur**: vermijd diep geneste pagina's (max 3–4 niveaus)
3. **Navigatie**: houd navigatie-items in toom (< 100 totaal)

### Caching

IntraVox steunt op Nextcloud's caching-laag. Voor beste performance:

- Schakel Redis of APCu caching in Nextcloud in
- Gebruik een CDN voor statische assets indien beschikbaar

## Updates

### IntraVox updaten

Via App Store:

1. Ga naar Beheer → Apps → Updates
2. Update IntraVox

Via command line:

```bash
sudo -u www-data php occ app:update intravox
```

### Versie-compatibility

Check altijd de CHANGELOG.md voor breaking changes vóór een update.

## Video-widget-configuratie

IntraVox bevat een video-widget voor het embedden van video's van externe platforms of het uploaden van lokale video-bestanden. Beheerders bepalen welke platforms zijn toegestaan via **Nextcloud-beheerinstellingen → IntraVox → Video Services**.

### Standaard-services

| Service | Privacy |
|---------|---------|
| YouTube (privacy-modus) | Enhanced — geen tracking |
| Vimeo | Standaard |

### Snelle configuratie

1. Ga naar **Beheerinstellingen → IntraVox → Video Services**
2. Toggle services aan/uit
3. Voeg custom domeinen toe voor bedrijfs-video-servers

### Beveiliging

- Alleen whitelisted domeinen kunnen worden embedded
- Video's van onbekende domeinen worden geblokkeerd
- Alleen HTTPS-bronnen toegestaan

Zie [Beheer-instellingen](settings.md) voor uitgebreide video-configuratie.

## RSS-feed-configuratie

IntraVox biedt een persoonlijke RSS-feed per gebruiker. De feed is een publiek endpoint beveiligd door een persoonlijke token. Als beheerder moet je twee dingen configureren om feeds te laten werken.

### Vereisten

1. **Link-delen inschakelen**: ga naar **Nextcloud-beheerinstellingen → Delen** en schakel **"Gebruikers toestaan via link en e-mail te delen"** in
2. **Share-permissie toekennen**: zorg dat gebruikersgroepen zowel **Lezen** als **Delen**-permissies hebben op de IntraVox-GroupFolder

Zonder link-delen zijn feeds volledig geblokkeerd. Zonder Delen-permissie geven feeds leeg terug.

### GroupFolder-permissie-setup

Het feed-endpoint draait zonder geauthenticeerde gebruikers-sessie. In deze context vereist GroupFolders zowel Lezen als Delen voor zichtbaarheid. Dit geldt voor basis-GroupFolder-permissies én ACL-regels.

**Zonder ACL:** zet de basis-permissies voor elke gebruikersgroep op Lezen + Delen.

**Met ACL:** zorg dat Lezen + Delen is ingesteld op elke folder in de hiërarchie die zichtbaar moet zijn in de feed. Een submap is alleen bereikbaar als alle ouder-folders ook zichtbaar zijn.

Voorbeeld: om Marketing-pagina's zichtbaar te maken in de feed voor `IntraVox Users`:

```
IntraVox (basis: Lezen + Delen)
└── nl/                       → ACL: Lezen + Delen
    └── afdelingen/           → ACL: Lezen + Delen
        ├── marketing/        → ACL: Lezen + Delen  ✓ zichtbaar in feed
        ├── hr/               → ACL: Alles weigeren  ✗ verborgen
        └── sales/            → ACL: Alles weigeren  ✗ verborgen
```

### Problemen oplossen

| Probleem | Oplossing |
|----------|------------|
| Admin ziet lege welkomstpagina na demo-import | Je Nextcloud-taal-instelling match't niet met de demo-data-taal. Wijzig via Instellingen → Persoonlijk → Taal, wis browser-cache of gebruik incognito |
| Gebruikers zien lege feed | Voeg Delen-permissie toe aan de gebruikersgroep op de taal-folder(s) |
| Feed geeft 404 | Schakel "Gebruikers toestaan via link en e-mail te delen" in via Delen-instellingen |
| Feed werkt voor admins maar niet voor reguliere gebruikers | Admin-groep heeft alle permissies; voeg Delen toe aan de gebruikersgroep |
| Sommige pagina's ontbreken in feed | Check ACL-regels op de specifieke submap en alle ouder-folders |

Zie [RSS-feed](../user/rss-feeds.md#beheerder-setup) voor de volledige technische details.

## Pagina-locking

IntraVox gebruikt pessimistic locking om gelijktijdige edits te voorkomen. Wanneer een gebruiker een pagina begint te bewerken, wordt die gelockt voor andere gebruikers.

- Locks verlopen automatisch na **15 minuten** zonder activiteit
- Een heartbeat-signaal houdt de lock actief tijdens actief bewerken
- Locks worden vrijgegeven bij opslaan, annuleren, weg-navigeren of tabblad sluiten

**Admin-override:** IntraVox-beheerders kunnen een pagina force-unlocken die door een andere gebruiker is gelockt. Handig als een lock is blijven hangen (bv. na browser-crash). De "Ontgrendelen"-knop verschijnt naast de lock-indicator voor admins.

## Concept-pagina's

Nieuwe pagina's worden standaard als **Concept** aangemaakt. Concept-pagina's zijn alleen zichtbaar voor gebruikers met schrijfrechten op de pagina-folder.

- **Editors** (schrijfrechten) kunnen concept-pagina's zien en bewerken
- **Lezers** (alleen-lezen-rechten) zien concept-pagina's helemaal niet
- Concept-pagina's worden uitgesloten van: zoekresultaten, RSS-feeds, publieke share-links en de pagina-tree voor lezers
- Editors toggelen de status via de Concept/Gepubliceerd-knop in de edit-toolbar

Geen extra configuratie nodig. Het concept-systeem gebruikt de bestaande GroupFolder-ACL-permissies om zichtbaarheid te bepalen.

## Beveiligings-overwegingen

1. **Permissies**: gebruik principle of least privilege
2. **Content**: review gebruikers-gegenereerde content op gevoelige informatie
3. **Media-bestanden**: wees bewust dat geüploade afbeeldingen en video's toegankelijk zijn voor alle gebruikers met leesrechten
4. **Externe links**: content-editors kunnen externe links toevoegen — review navigatie regelmatig
5. **Video-embeds**: sta alleen vertrouwde video-platforms toe via admin-instellingen
6. **Pagina-locking**: locks voorkomen gelijktijdige edits maar verlopen na 15 minuten — admins kunnen force-unlocken indien nodig
7. **Concept-pagina's**: concept-pagina's zijn verborgen voor lezers maar zichtbaar voor alle gebruikers met schrijfrechten

## Integratie

### MetaVox-integratie

Met MetaVox geïnstalleerd kan IntraVox:

- MetaVox-afdelingskleuren gebruiken
- MetaVox-navigatie-structuur respecteren
- Theming-instellingen delen

### Theming

IntraVox gebruikt Nextcloud-theming-variabelen:

- Primaire kleur uit Nextcloud-instellingen
- Logo uit Nextcloud-theming
- Font-instellingen uit Nextcloud

## Ondersteuning

- **GitHub Issues**: rapporteer bugs en feature-verzoeken
- **Documentatie**: zie andere bestanden in `docs/` voor specifieke onderwerpen
- **Nextcloud Forums**: community-support
