# IntraVox RSS-feed

IntraVox biedt een persoonlijke RSS-feed per gebruiker, zodat ze intranet-pagina-updates kunnen volgen in elke RSS-reader.

![RSS-feed in Nextcloud News](../screenshots/rss-result.png)

*IntraVox-feed in Nextcloud News met pagina-inhoud en afbeeldingen*

## Overzicht

- Elke gebruiker kan een persoonlijke feed-token genereren via de IntraVox-UI
- De feed-URL is publiek (geen login vereist) maar beveiligd door een 64-tekens alfanumeriek token
- Feed-items bevatten pagina-titels, beschrijvingen, volledige HTML-content en afbeeldingen
- De feed respecteert de pagina-toegangsrechten van de gebruiker

## Gebruiker-setup

### Een feed-URL genereren

1. Open IntraVox
2. Klik op het drie-puntjes-menu (⋯) rechtsboven
3. Kies **RSS-feed**

![RSS-feed menu-optie](../screenshots/rss-settings1.png)

4. Kies je voorkeur-instellingen:
   - **Feed-scope**: "Mijn taal" of "Alle talen"
   - **Maximum items**: 10, 20, 30 of 50
5. Klik **Genereer feed-URL**

![RSS-feed-instellingen](../screenshots/rss-settings2.png)

6. Kopieer de URL en voeg toe aan je RSS-reader

### Feed-scope

| Scope | Beschrijving |
|-------|--------------|
| **Mijn taal** | Alleen pagina's uit je huidige Nextcloud-taal-folder (bv. `nl/`, `en/`) |
| **Alle talen** | Pagina's uit alle beschikbare taal-folders |

Bij gebruik van "Mijn taal" gebruikt de feed dynamisch je huidige Nextcloud-taal-instelling. Wijzig je je Nextcloud-taal, dan toont de feed automatisch pagina's uit de nieuwe taal.

### Je feed beheren

| Actie | Beschrijving |
|-------|--------------|
| **Instellingen opslaan** | Werk scope en limiet bij zonder URL te wijzigen |
| **Regenereren** | Maak nieuwe token (oude URL stopt met werken) |
| **Intrekken** | Verwijder het token volledig |

## Technische details

### Feed-format

De feed is een RSS 2.0-feed met de volgende uitbreidingen:

- `atom:link` — zelf-refererende feed-URL
- `content:encoded` — volledige pagina-HTML-content met inline afbeeldingen
- `media:thumbnail` — pagina-thumbnail-afbeelding
- `media:content` — pagina-afbeelding-metadata
- Standaard `enclosure` — pagina-afbeelding voor readers die dat ondersteunen

### Feed-URL-structuur

```
https://{nextcloud}/index.php/apps/intravox/feed/{token}
```

Waar `{token}` een 64-tekens alfanumerieke string is, uniek per gebruiker.

### Media-URL-structuur

Afbeeldingen in de feed worden geserveerd via een token-geauthenticeerd media-endpoint:

```
https://{nextcloud}/apps/intravox/feed/{token}/media/{pageId}/{filename}
```

Hierdoor kunnen RSS-readers afbeeldingen tonen zonder Nextcloud-authenticatie te vereisen.

### Caching en conditional requests

De feed ondersteunt efficiënt pollen:

| Header | Beschrijving |
|--------|--------------|
| `ETag` | MD5-hash van de feed-content |
| `Last-Modified` | Tijdstempel van de meest recent gewijzigde pagina |
| `Cache-Control` | `public, max-age=300, must-revalidate` (5 minuten) |

RSS-readers die `If-None-Match` of `If-Modified-Since`-headers sturen, ontvangen `304 Not Modified` wanneer er niets is veranderd.

### Beheerder-setup

De RSS-feed vereist twee dingen correct geconfigureerd door de beheerder:

1. **Nextcloud link-delen** moet aan staan (globale instelling)
2. **GroupFolder-permissies** moeten zowel Read als Share bevatten op folders die in de feed moeten verschijnen

#### Stap 1: link-delen inschakelen

De RSS-feed is functioneel een publieke share-link — hij geeft anonieme toegang tot IntraVox-content via een persoonlijke token. Daarom moet Nextcloud's globale link-deel-instelling aan staan:

1. Ga naar **Nextcloud-beheerinstellingen → Delen**
2. Zorg dat **"Gebruikers toestaan via link en e-mail te delen"** aan staat

Als deze instelling uit staat:

- Gebruikers kunnen geen nieuwe feed-tokens genereren
- Bestaande feed-URLs geven 404
- Het feed-instellingen-dialoog toont een duidelijke foutmelding

![RSS-feed uitgeschakeld](../screenshots/rss-disabled.png)

*Wanneer link-delen door de beheerder is uitgeschakeld, toont het feed-instellingen-dialoog een foutmelding*

Wanneer de beheerder link-delen weer aan zet, werken bestaande tokens (mits niet ingetrokken) automatisch opnieuw.

#### Stap 2: configureer GroupFolder-permissies

Het RSS-feed-endpoint is een publieke pagina (geen geauthenticeerde gebruikers-sessie). In deze context vereist GroupFolders zowel **Read** als **Share** voor een folder om zichtbaar te zijn. Zonder Share-permissie zijn folders verborgen voor feed-requests en blijft de feed leeg.

**Basis-permissies (zonder ACL):**

Gebruik je geen ACL (Advanced Permissions)? Zet dan de basis-permissies voor de gebruikersgroep op **Read + Share** op de IntraVox-GroupFolder:

1. Ga naar **Beheer → GroupFolders** (of **Team Folders**)
2. Vind de "IntraVox"-folder
3. Zet voor elke gebruikersgroep zowel **Read** als **Share** aan

**Met ACL (Advanced Permissions):**

Gebruik je ACL om toegang tot specifieke submappen te beperken? Dan moet de Share-permissie op elk niveau van de folder-hiërarchie staan dat in de feed zichtbaar moet zijn. Een submap is alleen toegankelijk als alle ouder-folders ook zichtbaar zijn.

Voorbeeld-setup voor een gebruikersgroep die alleen Marketing-pagina's in de feed moet zien:

| Pad | ACL-regel | Effect |
|-----|-----------|--------|
| IntraVox-root | Read + Share (basis-permissie) | Folder zichtbaar |
| `nl/` | Read + Share | Taal-folder zichtbaar |
| `nl/afdelingen/` | Read + Share | Ouder-folder zichtbaar |
| `nl/afdelingen/marketing/` | Read + Share | Marketing-pagina's in feed |
| `nl/afdelingen/hr/` | Alles weigeren | Verborgen uit feed en IntraVox |
| `nl/afdelingen/sales/` | Alles weigeren | Verborgen uit feed en IntraVox |

**Belangrijke regels:**

- Een child-folder kan niet zichtbaar zijn als de ouder-folder verborgen is
- ACL kan permissies alleen beperken, nooit boven de basis-GroupFolder-permissies uitkomen
- Meldt een gebruiker een lege feed? Check eerst Share-permissie op de taal-folder(s) — dit is de meest voorkomende oorzaak

#### Lege feeds oplossen

| Symptoom | Oorzaak | Oplossing |
|----------|---------|------------|
| Feed geeft leeg terug (geen items) | Ontbrekende Share-permissie op taal-folder | Voeg Share-permissie toe via ACL of basis-GroupFolder-settings |
| Feed geeft 404 | Link-delen globaal uitgeschakeld | Schakel "Gebruikers toestaan via link en e-mail te delen" in via Nextcloud-Delen-instellingen |
| Feed toont sommige pagina's maar niet alle | ACL weigert toegang tot specifieke submappen | Check ACL-regels op de submap en alle ouder-folders |
| Feed werkt voor admin maar niet voor reguliere gebruikers | Admin-groep heeft alle permissies, gebruikersgroep mist Share | Voeg Share-permissie toe aan de gebruikersgroep |

### Beveiliging

| Feature | Beschrijving |
|---------|--------------|
| **Token-gebaseerde auth** | 64-tekens alfanumeriek token |
| **Brute-force-bescherming** | Ongeldige tokens triggeren Nextcloud's brute-force-throttling |
| **Rate limiting** | 30 feed-requests per minuut, 60 media-requests per minuut |
| **Timing-safe** | Ongeldige-token-responses bevatten willekeurige vertraging om timing-attacks te voorkomen |
| **Toegangscontrole** | Feed bevat alleen pagina's waarvoor de gebruiker rechten heeft |
| **Sharing-policy** | Respecteert Nextcloud's "Gebruikers toestaan via link te delen"-instelling |

### API-endpoints

| Methode | Endpoint | Auth | Beschrijving |
|---------|----------|------|--------------|
| `GET` | `/apps/intravox/feed/{token}` | Publiek (token) | Get RSS-feed XML |
| `GET` | `/apps/intravox/feed/{token}/media/{pageId}/{filename}` | Publiek (token) | Get feed-media-bestand |
| `GET` | `/apps/intravox/api/feed/token` | Sessie | Get huidige gebruikers-token-info |
| `POST` | `/apps/intravox/api/feed/token` | Sessie | Genereer/regenereer token |
| `DELETE` | `/apps/intravox/api/feed/token` | Sessie | Token intrekken |
| `PUT` | `/apps/intravox/api/feed/config` | Sessie | Feed-configuratie bijwerken |

### Cross-language pagina-links

Feed-items linken naar pagina's via het IntraVox hash-URL-format:

```
https://{nextcloud}/apps/intravox/#page-{uniqueId}
```

Wanneer een gebruiker een feed-link klikt naar een pagina in een andere taal dan zijn Nextcloud-instelling, zoekt IntraVox automatisch in alle taal-folders om de pagina te vinden. Hierdoor werken feed-links altijd, ongeacht de taal van de pagina.
