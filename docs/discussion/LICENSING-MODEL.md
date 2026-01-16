# IntraVox Licentiemodel: AGPL-3.0 Compliant met Paginalimiet

> **Status**: Voorstel
> **Datum**: 2026-01-15
> **Repository**: Gitea (niet GitHub)

## Context

- **Huidige situatie**: IntraVox is gelicenseerd onder AGPL-3.0 en gemarkeerd als "EVALUATION VERSION - NOT FOR PRODUCTION USE"
- **Downloads**: ~474 totale downloads via GitHub releases
- **Vraag van gebruiker**: Interesse in productie-gebruik, vraagt naar technische of licentie-kwestie
- **Antwoord**: Het is een licentiekwestie, technisch werkt alles

## AGPL-3.0 en Commercialisatie

De AGPL-3.0 licentie staat commercialisatie toe, mits:

1. **Broncode beschikbaar blijft** - Gebruikers moeten toegang hebben tot de broncode
2. **Geen extra restricties** - Je mag geen licentievoorwaarden toevoegen die AGPL rechten beperken
3. **Wijzigingen delen** - Gebruikers die wijzigingen maken moeten deze ook delen

### Wat WEL mag onder AGPL-3.0:

- Geld vragen voor de software
- Dual-licensing aanbieden (AGPL + commercieel)
- Betaalde support/services verkopen
- Hosted versie aanbieden (SaaS)
- "Open core" model: kern open-source, premium features apart

## Voorgesteld Model: Paginalimiet met Dual Licensing

### Waardebepaling Context

Uit de memo voor Nextcloud Core integratie:
- **Build cost intern**: Meerdere persoon-maanden = €60k-€100k+ ontwikkelkosten
- **Strategische waarde**: SharePoint alternatief, digital workplace, adoptie-verbetering
- **Doelgroep**: Enterprise, publieke sector, onderwijs

Dit betekent dat standalone pricing **significant hoger** moet zijn dan typische Nextcloud apps.

### Demo Data Analyse

De demo data bevat:
- **Nederlands (nl)**: 35 pagina's
- **Engels (en)**: 34 pagina's
- **Duits (de)**: 1 pagina (alleen home)
- **Frans (fr)**: 1 pagina (alleen home)

**Conclusie**: Gratis limiet moet minimaal 35 pagina's zijn om demo data te kunnen installeren.

### Gratis Tier (AGPL-3.0)
- **Limiet**: 50 pagina's per taal (200 pagina's totaal met 4 talen)
- **Alle features** inbegrepen
- **Open source** onder AGPL-3.0
- **Ideaal voor**: Kleine teams, evaluatie, non-profits, hobby-projecten

### Betaalde Licentie (Jaarlijks abonnement)

| Tier | Pagina's | Prijs/jaar | Doelgroep |
|------|----------|------------|-----------|
| **Business** | 250 per taal | €499/jaar | Klein MKB (1-2 afdelingen) |
| **Professional** | 1000 per taal | €999/jaar | Middelgroot (volledig intranet) |
| **Enterprise** | Onbeperkt | €1.999/jaar | Grote organisaties (multi-site) |

Inclusief bij alle betaalde tiers:
- Alle features
- Updates naar nieuwe versies
- E-mail support

### Prijsvergelijking

| Alternatief | Kosten voor 100 users/jaar |
|-------------|----------------------------|
| SharePoint Online | €5.040/jaar (€4.20/user/maand) |
| Confluence | €6.600/jaar (€5.50/user/maand) |
| Notion Team | €9.600/jaar (€8/user/maand) |
| **IntraVox Business** | **€499/jaar** |
| **IntraVox Professional** | **€999/jaar** |

**Besparing vs SharePoint**: €4.041 - €4.541/jaar (80-90% goedkoper)

### Waarom paginalimiet werkt

1. **Eenvoudig te implementeren** - Tel aantal pagina's in `listPages()`
2. **Eerlijk** - Schaalt met daadwerkelijk gebruik
3. **Niet te omzeilen** - Pagina's tellen is triviaal te controleren
4. **Demo-vriendelijk** - 50 pagina's gratis = demo data werkt volledig
5. **Enterprise-schaalbaar** - Grote organisaties hebben veel pagina's nodig

## Technische Implementatie

### 1. LicenseService toevoegen

**Nieuwe file**: `lib/Service/LicenseService.php`

```php
class LicenseService {
    private const FREE_PAGE_LIMIT = 50; // per taal

    public function getPageLimit(): int {
        $licenseKey = $this->config->getAppValue('intravox', 'license_key', '');
        if (empty($licenseKey)) {
            return self::FREE_PAGE_LIMIT;
        }
        return $this->validateAndGetLimit($licenseKey);
    }

    public function canCreatePage(string $language): bool {
        $currentCount = $this->pageService->countPages($language);
        return $currentCount < $this->getPageLimit();
    }
}
```

### 2. Pagina-creatie blokkeren

**Wijzig**: `lib/Controller/ApiController.php`

Bij `createPage()` methode:
```php
if (!$this->licenseService->canCreatePage($language)) {
    return new JSONResponse([
        'error' => 'Page limit reached',
        'limit' => $this->licenseService->getPageLimit(),
        'upgrade_url' => 'https://shalution.nl/intravox/pricing'
    ], Http::STATUS_PAYMENT_REQUIRED);
}
```

### 3. Admin Settings uitbreiden

**Wijzig**: `lib/Settings/AdminSettings.php` en `src/admin/AdminSettings.vue`

- Toon huidige pagina-telling per taal
- Licentie key invoerveld
- Status van licentie (geldig/ongeldig/verlopen)
- Link naar upgrade

### 4. Frontend melding

**Wijzig**: `src/App.vue`

Bij benaderen van limiet (80%+):
```javascript
if (pageCount >= limit * 0.8) {
    showNotification('Je nadert de paginalimiet. Upgrade voor meer pagina\'s.');
}
```

## Licentie Validatie: Hybride Model

**Strategie**: Online verificatie primair, offline als fallback.

### Implementatie Flow

```
┌─────────────────────────────────────────────────────────┐
│                    License Check Flow                    │
├─────────────────────────────────────────────────────────┤
│  1. Check lokale cache (geldig < 24 uur)                │
│     ↓ niet gevonden of verlopen                         │
│  2. Online verificatie (shalution.nl/api/license)       │
│     ↓ mislukt (timeout, server down)                    │
│  3. Offline fallback: decodeer key lokaal               │
│     ↓ altijd                                            │
│  4. Cache resultaat lokaal (24 uur)                     │
└─────────────────────────────────────────────────────────┘
```

### Licentie Key Format

```
IV-{TIER}-{LIMIT}-{INSTANCE_HASH}-{CHECKSUM}

Voorbeeld: IV-BUS-250-a3f8c2d1-k9m2
           │   │    │      │       │
           │   │    │      │       └── HMAC checksum
           │   │    │      └────────── Nextcloud instance ID hash
           │   │    └───────────────── Paginalimiet per taal
           │   └────────────────────── Tier (BUS/PRO/ENT)
           └────────────────────────── IntraVox prefix
```

### Online Verificatie Endpoint

**POST** `https://shalution.nl/api/intravox/verify`

```json
{
  "license_key": "IV-BUS-250-a3f8c2d1-k9m2",
  "instance_id": "nextcloud-instance-uuid",
  "version": "0.8.9"
}
```

**Response:**
```json
{
  "valid": true,
  "tier": "business",
  "page_limit": 250,
  "expires": null,
  "support_expires": "2026-01-15"
}
```

### Voordelen Hybride Model

| Aspect | Online | Offline Fallback |
|--------|--------|------------------|
| Werkt zonder internet | Nee | Ja |
| Kan gedeactiveerd worden | Ja | Nee |
| Licentie overdracht | Via dashboard | Nieuwe key nodig |
| Verificatie nauwkeurigheid | 100% | ~99% (key kan lekken) |

## Telemetrie & Gebruiksstatistieken

### Lokale Admin UI (altijd zichtbaar)

In Admin Settings tonen we het huidige gebruik:

```
┌─────────────────────────────────────────────────┐
│  IntraVox Gebruik                               │
├─────────────────────────────────────────────────┤
│  Nederlands:  35 / 50 pagina's  ████████░░ 70%  │
│  Engels:      12 / 50 pagina's  ██░░░░░░░░ 24%  │
│  Duits:        1 / 50 pagina's  ░░░░░░░░░░  2%  │
│  Frans:        0 / 50 pagina's  ░░░░░░░░░░  0%  │
├─────────────────────────────────────────────────┤
│  Totaal:      48 / 200 pagina's                 │
│  Licentie:    Gratis (upgrade naar Pro)         │
└─────────────────────────────────────────────────┘
```

### Anonieme Telemetrie (opt-in)

Bij licentie-verificatie versturen we optioneel anonieme usage stats:

```json
POST https://shalution.nl/api/intravox/telemetry
{
  "instance_hash": "sha256-of-nextcloud-instance-id",
  "version": "0.8.9",
  "pages": {
    "nl": 35,
    "en": 12,
    "de": 1,
    "fr": 0
  },
  "total_pages": 48,
  "license_tier": "free"
}
```

**Voordelen telemetrie:**
- Inzicht in hoeveel pagina's klanten aanmaken
- Identificeer klanten die limiet naderen (sales kans)
- Begrijp welke talen populair zijn
- Onderbouw waarde voor Nextcloud Core discussie

**Privacy:**
- Instance ID wordt gehasht (niet herleidbaar)
- Geen persoonlijke data
- Opt-in voor gratis tier, standaard aan voor betaald
- Documenteren in privacy policy

## Prijsstrategie Onderbouwing

### Build vs Buy Perspectief

Uit de Nextcloud memo:
- **Interne ontwikkeling**: €60.000 - €100.000+ (meerdere persoon-maanden)
- **Strategische waarde**: SharePoint alternatief, adoptie, digital workplace

IntraVox Enterprise (€1.999/jaar) = **~8% van SharePoint kosten**

### Concurrentievergelijking

| Alternatief | 100 users/jaar | 500 users/jaar |
|-------------|----------------|----------------|
| SharePoint Online | €5.040 | €25.200 |
| Confluence Cloud | €6.600 | €33.000 |
| Notion Team | €9.600 | €48.000 |
| **IntraVox Business** | **€499/jaar** | **€499/jaar** |
| **IntraVox Professional** | **€999/jaar** | **€999/jaar** |
| **IntraVox Enterprise** | **€1.999/jaar** | **€1.999/jaar** |

### ROI Calculator (voor sales)

```
Besparing vs SharePoint (500 users, 3 jaar):
  SharePoint: 3 × €25.200 = €75.600
  IntraVox:   3 × €1.999 = €5.997

  Besparing: €69.603 (92%)
```

### Prijspsychologie

- **€499/jaar** (Business): Minder dan €42/maand, makkelijke beslissing
- **€999/jaar** (Professional): Minder dan €84/maand, duidelijke waarde
- **€1.999/jaar** (Enterprise): Minder dan €167/maand, fractie van SharePoint

### Prijsladder logica

```
Gratis → €499 → €999 → €1.999
          (2x)   (2x)
```
Elke stap is ongeveer een verdubbeling - voelt logisch en eerlijk voor klanten.

## Implementatie Stappen

### Fase 1: Backend Licentielogica
1. Creëer `lib/Service/LicenseService.php`
   - Pagina-telling per taal
   - Key parsing en validatie
   - Online verificatie met offline fallback
   - Cache management (24 uur)

2. Voeg limiet-check toe aan `lib/Controller/ApiController.php`
   - `createPage()` blokkeren bij limiet
   - HTTP 402 Payment Required response
   - Duidelijke foutmelding met upgrade URL

3. Creëer `lib/Controller/LicenseController.php`
   - `GET /api/license/status` - huidige status
   - `POST /api/license/activate` - key activeren
   - `DELETE /api/license/deactivate` - key verwijderen

### Fase 2: Telemetrie
4. Creëer `lib/Service/TelemetryService.php`
   - Verzamel pagina-tellingen per taal
   - Verstuur naar shalution.nl/api/intravox/telemetry
   - Respecteer opt-in setting

5. Voeg telemetrie endpoint toe aan verificatie flow
   - Bij licentie-check, stuur ook usage stats
   - Alleen als telemetrie enabled is

### Fase 3: Admin UI
6. Breid `lib/Settings/AdminSettings.php` uit
   - Licentie informatie doorgeven aan frontend
   - Pagina-telling per taal
   - Telemetrie opt-in toggle

7. Update `src/admin/AdminSettings.vue`
   - Licentie key invoerveld
   - Activatie/deactivatie knoppen
   - Status weergave (tier, limiet)
   - **Pagina-gebruik per taal met visuele balk**
   - Telemetrie opt-in checkbox

### Fase 4: Frontend UX
8. Update `src/App.vue`
   - Waarschuwing bij 80% limiet
   - Blokkade melding bij 100%
   - Upgrade CTA

9. Update `src/components/PageEditor.vue`
   - Disable "Nieuwe pagina" bij limiet
   - Tooltip met uitleg

### Fase 5: Marketing & Communicatie
10. Update `README.md`
    - Verwijder "EVALUATION" tekst
    - Voeg pricing sectie toe
    - Link naar shalution.nl/intravox

11. Update `appinfo/info.xml`
    - Verwijder "NOT FOR PRODUCTION USE"
    - Update beschrijving

### Fase 6: Externe Infrastructure
12. Maak shalution.nl/intravox/pricing pagina
13. Implementeer verificatie API endpoint
14. Implementeer telemetrie API endpoint
15. Creëer licentie key generator tool
16. Setup betalingsproces (Stripe/Mollie)
17. Creëer dashboard voor telemetrie inzichten

## Verificatie Checklist

### Functioneel
- Gratis tier stopt bij 50 pagina's per taal (demo data past erin)
- Business key ontgrendelt 250 pagina's per taal
- Professional key ontgrendelt 1000 pagina's per taal
- Enterprise key ontgrendelt onbeperkt
- Ongeldige key wordt afgewezen met duidelijke melding
- Offline fallback werkt als server onbereikbaar is

### Telemetrie
- Admin UI toont pagina-telling per taal met visuele balk
- Telemetrie opt-in toggle werkt correct
- Telemetrie data komt aan op shalution.nl
- Instance ID is correct gehasht (niet herleidbaar)

### UX
- Admin UI toont correcte status en teller
- Waarschuwing verschijnt bij 80%+ gebruik
- "Nieuwe pagina" is disabled bij 100%
- Foutmeldingen bevatten upgrade URL

### Security
- Key kan niet geraden worden (cryptografische checksum)
- Instance binding voorkomt key sharing
- Rate limiting op verificatie endpoint
