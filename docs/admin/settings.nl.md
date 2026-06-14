# IntraVox beheer-instellingen

Deze gids behandelt het Nextcloud-beheer-instellingen-paneel voor IntraVox. Bereikbaar via **Nextcloud-beheerinstellingen → IntraVox**.

## Overzicht

Het IntraVox-beheer-instellingen-paneel heeft de volgende tabs:

1. **Video Services** — Configureer toegestane video-embed-domeinen
2. **Demo-data** — Installeer en beheer demo-content
3. **Externe feeds** — Configureer LMS- en feed-verbindingen voor de Feed-widget

---

## Video Services-tab

IntraVox bevat een video-widget waarmee editors video's van externe platforms kunnen embedden. Beheerders bepalen welke platforms zijn toegestaan.

### Standaard-configuratie

De volgende services zijn standaard ingeschakeld voor privacy-first-werking:

| Service | Domein | Privacy-niveau |
|---------|--------|----------------|
| YouTube (privacy-modus) | youtube-nocookie.com | Enhanced — geen tracking-cookies |
| Vimeo | player.vimeo.com | Standaard |

### Video-services beheren

1. Ga naar **Beheerinstellingen → IntraVox**
2. Selecteer de tab **Video Services**
3. Schakel services aan/uit met de toggles

### Service-categorieën

Services zijn gegroepeerd op categorie:

#### Privacy-vriendelijk

- **YouTube (privacy-modus)** — gebruikt youtube-nocookie.com, geen tracking-cookies
- **PeerTube-instances** — gefedereerde open-source video-hosting

#### Populaire platforms

- **Vimeo** — professionele video-hosting
- **Dailymotion** — video-deel-platform

#### PeerTube-servers

Voor-geconfigureerde PeerTube-instances:

- framatube.org
- peertube.tv
- video.blender.org
- En meer...

### Custom video-servers toevoegen

Voor organisaties met eigen video-hosting:

1. Ga naar **Beheerinstellingen → IntraVox → Video Services**
2. Scroll naar "Custom-domein toevoegen"
3. Voer het domein in (bv. `video.bedrijf.nl`)
4. Klik **Toevoegen**

#### Vereisten voor custom-domeinen

| Vereiste | Beschrijving |
|----------|--------------|
| HTTPS | Alleen HTTPS-domeinen toegestaan (beveiliging) |
| Bereikbaar | Domein moet bereikbaar zijn vanuit browsers van gebruikers |
| Iframe toegestaan | Server moet iframe-embedding toestaan (`X-Frame-Options`) |

### Custom-domeinen verwijderen

1. Vind het custom-domein in de lijst
2. Klik op de **X**-knop ernaast
3. Het domein wordt direct van de whitelist verwijderd

### Geblokkeerd-video-gedrag

Wanneer een editor een video embed't van een niet-whitelisted domein:

1. De video toont een "geblokkeerd"-melding met het domein
2. De video-URL blijft bewaard in de pagina-data
3. Zodra de admin het domein whitelist, werkt de video automatisch
4. Editors kunnen de domein-whitelist niet omzeilen

---

## Video-widget-features

### Ondersteunde video-bronnen

| Bron-type | Beschrijving |
|-----------|--------------|
| Externe embed | YouTube, Vimeo, PeerTube, etc. via URL |
| Lokale upload | MP4-, WebM-, OGG-bestanden geüpload naar Nextcloud |

### Playback-opties

Editors kunnen deze opties per video configureren:

| Optie | Beschrijving | Notes |
|-------|--------------|-------|
| Autoplay | Video start bij page-load | Vereist muted |
| Loop | Video herhaalt continu | — |
| Muted | Geen geluid | Vereist voor autoplay |
| Controls | Toon player-controls | Aanbevolen aan |

### Lokale video-upload

1. Klik in de video-widget-editor op "Video uploaden"
2. Selecteer een video-bestand (MP4, WebM of OGG)
3. Video wordt opgeslagen in de `_media/`-folder van de pagina
4. Max bestandsgrootte wordt bepaald door Nextcloud's PHP-upload-limieten

---

## Externe feeds-tab

De Externe feeds-tab laat beheerders verbindingen configureren naar externe systemen — learning management systems (Canvas, Moodle, Brightspace), project management (Jira, OpenProject), knowledge bases (Confluence), samenwerking (SharePoint), en elke custom REST API. Deze verbindingen worden gebruikt door de [Feed-widget](../features/feed-widget.md) op IntraVox-pagina's.

> **Waarom feed-verbindingen configureren als Nextcloud-integratie-apps bestaan?**
>
> Veel van deze systemen (OpenProject, Jira, etc.) hebben eigen Nextcloud-integratie-apps. Die apps dienen *individuele gebruikers* — bestanden linken, taken zoeken, persoonlijke notificaties ontvangen. Feed-verbindingen dienen een ander doel: ze laten je data uit deze systemen tonen op *intranet-pagina's* zichtbaar voor hele teams of afdelingen. Dat is organisatie-bewustzijn, geen persoonlijke productiviteit. Zie de [architectuur-design-principes](../architecture/overview.md) voor details.

### Een verbinding toevoegen

1. Ga naar **Beheerinstellingen → IntraVox → Externe feeds**
2. Klik **+ Verbinding toevoegen**
3. Vul de verbindings-details in (voor niet-LMS-typen staan geavanceerde opties zoals endpoint-pad, response-mapping en custom headers achter de **Geavanceerde opties**-toggle)
4. Klik **Verbindingen opslaan**

### Een verbinding testen

Klik na opslaan op **Verbinding testen** op een verbindings-kaart om credentials en endpoint te verifiëren. Het resultaat toont:

- **Verbinding OK — N items** als de verbinding werkt
- Een foutmelding (authenticatie, permissies, netwerk) als hij faalt

Verbindingen moeten zijn opgeslagen vóór testen (de server heeft de opgeslagen configuratie nodig om de API-call te maken).

### Een verbinding verwijderen

Klik op de **×**-knop op een verbindings-kaart. Een bevestigings-dialoog toont de verbindings-naam — verwijderen heeft pas effect na klikken op **Verbindingen opslaan**.

### Verbindingen aan- en uitzetten

Elke verbinding heeft een **actief/inactief-toggle** in de verbindings-header. Gebruik dit om een verbinding tijdelijk uit te zetten zonder te verwijderen.

**Wanneer een verbinding uitstaat:**

- De toggle staat uit (grijs) en de verbindings-kaart wordt visueel gedempt
- Bestaande widgets die deze verbinding gebruiken tonen: *"Deze verbinding is momenteel uitgeschakeld door een beheerder."*
- De verbinding is niet beschikbaar voor nieuwe widgets in de widget-editor
- Alle verbindings-instellingen (tokens, URLs, mappings) blijven bewaard

**Wanneer een verbinding weer aan staat:**

- Alle widgets geconfigureerd met deze verbinding tonen automatisch weer data
- Geen herconfiguratie nodig — ze behouden hun connectionId-referentie
- Data verschijnt na de volgende refresh (tot 15 minuten door server-side caching, of direct na page-reload)

Aan/uit-toggle wordt direct opgeslagen — geen aparte **Verbindingen opslaan**-klik nodig.

![Een Canvas-verbinding uitschakelen en de resulterende melding op de pagina](../screenshots/feed-disable-feed.png)

### Verbindingen exporteren en importeren

Gebruik de **Exporteren**- en **Importeren**-knoppen onderaan de Externe feeds-tab om verbindings-configuraties tussen Nextcloud-instances te transfereren.

**Exporteren** downloadt alle verbindingen als `intravox-feed-connections.json`. Dit bevat alleen configuratie — **API-tokens, client-secrets en client-ID's worden nooit geëxporteerd** om beveiligingsredenen.

**Importeren** opent een preview-dialoog met:

- Alle verbindingen gevonden in het bestand
- Welke al bestaan (gematcht op naam, getoond als duplicaten)
- Hoeveel nieuwe verbindingen worden toegevoegd

Na bevestiging worden de nieuwe verbindingen toegevoegd met lege token-velden. Voer API-tokens in en klik **Verbindingen opslaan**.

### Verbindings-velden

| Veld | Beschrijving | Vereist |
|------|--------------|---------|
| **Naam** | Weergavenaam voor deze verbinding (bv. "Canvas Universiteit") | Ja |
| **Type** | Platform-type: Canvas, Moodle, Brightspace, Jira, Confluence, SharePoint, OpenProject of Custom | Ja |
| **Basis-URL** | De root-URL van het systeem (bv. `https://canvas.voorbeeld.com`) | Ja |
| **Actief** | Schakel deze verbinding aan of uit | Ja |

Voor **LMS-typen** (Canvas, Moodle, Brightspace) verschijnen extra velden:

| Veld | Beschrijving |
|------|--------------|
| **API-token (admin-fallback)** | Gedeelde API-token als fallback wanneer gebruikers geen eigen account hebben gekoppeld |
| **Gebruikers-authenticatie** | Hoe gebruikers authenticeren — zie [Authenticatie-modi](#gebruikers-authenticatie-modi) hieronder |

Voor **REST API-typen** (Jira, Confluence, OpenProject, Custom) verschijnen extra velden:

| Veld | Beschrijving |
|------|--------------|
| **Endpoint-pad** | API-pad toegevoegd aan de basis-URL (vooraf ingevuld door preset) |
| **Auth-methode** | Bearer-token, Basic auth, API-key (custom header), of Geen authenticatie |
| **API-token** | De API-token of credentials. Format hangt af van auth-methode (zie hieronder) |
| **Response-mapping** | JSON veld-mapping voor titel, URL, samenvatting, datum, afbeelding, auteur (vooraf ingevuld door preset) |
| **Custom request-headers** | Extra HTTP-headers verstuurd met elke request (bv. `OCS-APIRequest: true`) |

### Auth-methode-details (REST API-typen)

| Auth-methode | Token-format | Voorbeeld |
|--------------|--------------|-----------|
| **Bearer-token** | Plain API-token | `ghp_abc123...` |
| **Basic auth** | `gebruikersnaam:wachtwoord` (auto-encoded naar base64) | `apikey:abc123...` (OpenProject) |
| **API-key** | Plain key + custom header-naam | Key: `abc123`, Header: `X-API-Key` |
| **Geen authenticatie** | — | Publieke API's |

### Gebruikers-authenticatie-modi

| Modus | Beschrijving | Use case |
|-------|--------------|----------|
| **Alleen admin-token (gedeeld)** | Alle gebruikers zien dezelfde data via de admin-API-token | Simpele setup, geen personalisatie nodig |
| **OAuth2 (per-user, gepersonaliseerd)** | Elke gebruiker koppelt eigen account via OAuth2 | Volledige personalisatie, gebruikers zien alleen eigen cursussen |
| **Beide (OAuth2 met admin-fallback)** | Gekoppelde gebruikers zien gepersonaliseerde data; anderen zien gedeelde data | Geleidelijke uitrol, best of both worlds |

### OAuth2-configuratie

Bij gebruik van OAuth2 of Beide modus verschijnen extra velden:

| Veld | Beschrijving |
|------|--------------|
| **OAuth2 client-ID** | Client-ID uit de LMS Developer Key / OAuth2-app |
| **OAuth2 client-secret** | Client-secret (versleuteld opgeslagen) |
| **Redirect-URI** | Wordt automatisch getoond — kopieer naar de LMS OAuth2-configuratie |
| **OIDC auto-connect** | Schakel in als de LMS dezelfde identity-provider als Nextcloud gebruikt (zie hieronder) |

### OAuth2 opzetten per LMS

#### Canvas

1. Ga in Canvas naar **Beheer → Developer Keys**
2. Klik **+ Developer Key → API Key**
3. Voer een naam in (bv. "IntraVox")
4. Stel de **Redirect-URI** in op de waarde uit IntraVox-beheerinstellingen
5. Klik **Opslaan** en noteer Client-ID en Client-Secret
6. Zet de key op **ON** (standaard staat hij op OFF)
7. Voer Client-ID en Client-Secret in IntraVox in

#### Moodle

Moodle vereist de [local_oauth2-plugin](https://moodle.org/plugins/local_oauth2) om als OAuth2-provider te werken:

1. Installeer de `local_oauth2`-plugin op je Moodle-instantie
2. Configureer een OAuth2-client in Moodle met de Redirect-URI uit IntraVox
3. Voer Client-ID en Client-Secret in IntraVox in

**Alternatief: handmatige tokens.** Kan de OAuth2-plugin niet worden geïnstalleerd? Zet auth-modus op **Beide** en laat OAuth2-velden leeg. Gebruikers kunnen dan hun persoonlijke Moodle web-service-token handmatig invoeren in de Feed-widget-editor. Genereren in Moodle onder **Voorkeuren → Beveiligingssleutels**.

#### Brightspace

1. Ga in Brightspace naar **Beheer → Manage Extensibility → OAuth 2.0**
2. Klik **Register an app**
3. Voer een naam in (bv. "IntraVox") en stel de **Redirect-URI** in op de waarde uit IntraVox-beheerinstellingen
4. Stel scope in op `core:*:*` (of meer granulaire scopes naar wens)
5. Klik **Register** en noteer Client-ID en Client-Secret
6. Voer Client-ID en Client-Secret in IntraVox in

**Alternatief: handmatige tokens.** Zet auth-modus op **Beide** en laat OAuth2-velden leeg. Gebruikers kunnen dan een persoonlijke bearer-token uit hun Brightspace Account Settings invoeren.

### OpenProject opzetten

OpenProject gebruikt Basic-authenticatie met de API v3. De preset vult endpoint, auth-methode en response-mapping vooraf in.

1. Ga in OpenProject naar **Mijn account → Access tokens**
2. Klik **+ API-token** en kopieer de token (slechts één keer getoond)
3. Klik in IntraVox-beheerinstellingen → Externe feeds op **+ Verbinding toevoegen**
4. Selecteer type **OpenProject**
5. Voer de **Basis-URL** in (bv. `https://openproject.voorbeeld.com`)
6. Voer de **API-token** in als `apikey:<je-token>` (bv. `apikey:7def51d5...`)
7. Klik **Verbindingen opslaan**

De Feed-widget toont dan een **Content-type**-dropdown met opties: alle work packages, Open, Verlopen, Mijlpalen, en Recent bijgewerkt.

### OIDC auto-connect

Indien ingeschakeld probeert IntraVox de bestaande Nextcloud-SSO-token van de gebruiker te gebruiken voor toegang tot de LMS-API — zonder gebruikers-interactie. Dit werkt wanneer:

- Nextcloud en de LMS zijn verbonden met **dezelfde identity-provider** (bv. Keycloak, Azure AD, Authentik)
- De Nextcloud `user_oidc`-app is geïnstalleerd met `store_login_token` aan
- De identity-provider-token heeft de juiste audience/scope voor de LMS-API

Dit is de meest naadloze ervaring voor eind-gebruikers, maar vereist gedeelde SSO-infrastructuur.

### Beveiligings-notes

- API-tokens en OAuth2-client-secrets zijn at-rest versleuteld via Nextcloud's `ICrypto`-service
- Per-user OAuth2-tokens (Canvas, Moodle, Brightspace) zijn versleuteld in de database opgeslagen en worden automatisch ververst bij verlopen
- Wanneer een Nextcloud-gebruiker wordt verwijderd, worden al hun opgeslagen LMS-tokens automatisch opgeruimd
- De admin-API-token wordt nooit aan eind-gebruikers blootgesteld — alleen server-side gebruikt

---

## Demo-data-tab

![Demo-data-tab in de IntraVox-beheer-instellingen](../screenshots/admin-demodata.png)

*Per-taal demo-content: installeer, herinstalleer, of clean-start een complete demo-intranet in een van de ondersteunde talen.*

### Demo-data installeren

1. Ga naar **Nextcloud-beheerinstellingen → IntraVox**
2. Selecteer de tab **Demo-data**
3. Klik **Installeren** naast de taal die je wilt opzetten
4. De GroupFolder en permissie-groepen worden automatisch aangemaakt als ze niet bestaan

### Status-indicators

Het beheer-instellingen-paneel toont:

| Badge | Betekenis |
|-------|-----------|
| **Geïnstalleerd** | Demo-data is geïnstalleerd en klaar |
| **Niet geïnstalleerd** | Geen content voor deze taal |
| **Lege folder** | Folder bestaat maar is leeg |
| **Volledig intranet** | Complete demo met alle pagina's |
| **Alleen homepage** | Basis-demo met homepage |

### Beschikbare talen

| Taal | Vlag | Content-type |
|------|------|--------------|
| Nederlands | 🇳🇱 | Volledig intranet |
| Engels | 🇬🇧 | Volledig intranet |
| Duits | 🇩🇪 | Alleen homepage |
| Frans | 🇫🇷 | Alleen homepage |

### Demo-data opnieuw installeren

Om demo-content terug te zetten naar originele staat:

1. Klik **Herinstalleren** naast de taal
2. Bevestig de actie
3. Alle bestaande content voor die taal wordt vervangen door verse demo-data

> **Waarschuwing**: herinstalleren verwijdert alle aanpassingen aan de demo-content.

### Clean Start

De **Clean Start**-knop verwijdert alle content voor een taal en maakt een verse lege homepage. Dit is een onomkeerbare destructieve operatie — je moet `DELETE` typen in de bevestigings-dialoog om door te gaan.

---

## Beveiligings-overwegingen

### Video-embeds

- Alleen whitelisted domeinen kunnen worden embedded
- Alle externe video's gebruiken HTTPS
- Iframe-sandboxing wordt toegepast
- Geblokkeerde video's tonen domein-naam voor transparantie

### Aanbevelingen

1. Schakel alleen video-services in die je organisatie vertrouwt
2. Geef voorkeur aan privacy-vriendelijke opties (YouTube privacy-modus, PeerTube)
3. Review custom-domeinen vóór toevoegen
4. Audit ingeschakelde services regelmatig

---

## Problemen oplossen

### Video's spelen niet af

1. Check of het domein op de whitelist staat in admin-instellingen
2. Verifieer dat de video-URL klopt
3. Check browser-console op fouten
4. Zorg dat de video-service embedding toestaat

### Demo-data installeert niet

1. Verifieer dat PHP `memory_limit` minimaal 256 MB is
2. Check Nextcloud-logs op fouten
3. Zorg dat de GroupFolders-app is ingeschakeld
4. Probeer via command line:
   ```bash
   sudo -u www-data php occ intravox:import-demo-data --language=nl
   ```

### Custom-domein werkt niet

1. Verifieer dat het domein HTTPS gebruikt
2. Check of de video-server iframe-embedding toestaat
3. Test de video-URL direct in een browser
4. Check op CORS- of X-Frame-Options-beperkingen
