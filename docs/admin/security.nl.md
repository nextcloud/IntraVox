# IntraVox-beveiliging — SVG-upload-bescherming

## Overzicht

IntraVox ondersteunt SVG-uploads met uitgebreide sanitization om security-aanvallen te voorkomen. Dit document legt uit welke beveiligingsmaatregelen geïmplementeerd zijn voor veilig SVG-gebruik.

## Waarom SVG-bestanden sanitization nodig hebben

SVG (Scalable Vector Graphics) is XML-gebaseerd en kan bevatten:

- JavaScript-code via `<script>`-tags
- Event-handlers (`onclick`, `onload`, etc.)
- Externe entity-referenties (XXE-aanvallen)
- HTML-injectie via `<foreignObject>`-elementen
- Externe resource-loading (privacy-/tracking-zorgen)

Zonder sanitization kunnen malafide SVG-bestanden JavaScript in browsers van gebruikers uitvoeren, wat leidt tot XSS (Cross-Site Scripting) aanvallen.

## Voorkomen aanvalsvectoren

| Aanvalsvector | Risico | Hoe we het voorkomen |
|---------------|--------|------------------------|
| **XSS via `<script>`-tags** | Kritiek | Verwijderd door svg-sanitize-bibliotheek |
| **Event-handlers** | Kritiek | Gestript (onclick, onload, onerror, etc.) |
| **XXE-aanvallen** | Hoog | DOCTYPE-declaraties geweigerd |
| **HTML-injectie via `<foreignObject>`** | Hoog | foreignObject-elementen verwijderd |
| **Externe resource-loading** | Middel | Externe referenties gestript |

## Implementatie

### Server-side sanitization

**Bibliotheek**: `enshrined/svg-sanitize` v0.20+

- Industriestandaard PHP-bibliotheek
- Gebruikt door WordPress, Drupal en andere grote platforms
- MIT-licentie, actief onderhouden

**Proces**:

1. Bestand-upload ontvangen
2. MIME-type gevalideerd via PHP `finfo_file()` (niet bestandsextensie)
3. SVG-content gesanitized via bibliotheek
4. Extra DOCTYPE-check (XXE-preventie)
5. Gesanitized content naar disk geschreven

**Code-locatie**:

- `lib/Service/PageService.php` — `sanitizeSVG()`-methode (regels 3632–3662)
- Integratie in `uploadMedia()` (regel 1692–1698)
- Integratie in `uploadMediaWithOriginalName()` (regel 3780–3786)

### Defense-in-depth lagen

1. **MIME-type-validatie**: server-side detectie via `finfo_file()` voorkomt bestandsextensie-spoofing
2. **Content-sanitization**: verwijdert malafide elementen en attributen
3. **DOCTYPE-rejectie**: blokkeert XML external entity (XXE) aanvallen
4. **img-tag-rendering**: frontend rendert SVG via `<img>`-tag (scripts werken niet zelfs als sanitization wordt omzeild)
5. **Bestandsgrootte-limieten**: maximaal 50 MB voorkomt DoS-aanvallen
6. **Permissie-checks**: bestaand Nextcloud-permissie-systeem gerespecteerd

### Wat wordt verwijderd

De sanitizer verwijdert of neutraliseert:

- `<script>`-elementen
- Event-handler-attributen (onclick, onmouseover, onerror, onload, etc.)
- `<foreignObject>`-elementen
- `<iframe>`-elementen
- Externe stylesheets (`<link>`)
- Data-URI's in bepaalde contexten
- JavaScript-protocol-handlers (`javascript:`)
- DOCTYPE-declaraties

### Wat blijft behouden

Veilige SVG-features blijven behouden:

- Basis-vormen (rect, circle, path, polygon, etc.)
- Gradients en patterns
- Tekst-elementen
- Transformaties en animaties (SMIL, niet JavaScript-gebaseerd)
- Interne styles (`style`-attribuut, `<style>`-elementen met alleen CSS)
- Toegankelijkheids-attributen (`aria-*`, `role`)

## Beveiligings-best-practices

### Voor gebruikers

1. **Bron-vertrouwen**: upload alleen SVG's uit vertrouwde bronnen
2. **Review vóór upload**: check SVG-content als ontvangen van externe partijen
3. **Meld problemen**: faalt een SVG-upload, neem contact op met je beheerder (bestand bevat mogelijk malafide content)

### Voor beheerders

1. **Up-to-date**: update IntraVox regelmatig voor de laatste security-patches
2. **Monitor logs**: check Nextcloud-logs op SVG-sanitization-fouten
3. **Composer-dependencies**: hou `enshrined/svg-sanitize` up-to-date
4. **Bestandsrechten**: zorg voor juiste Nextcloud-ACL's op de `_resources`-folder

## Testen

### Geldige SVG-voorbeelden

Deze worden geaccepteerd:

```xml
<!-- Simpele vormen -->
<svg xmlns="http://www.w3.org/2000/svg" width="100" height="100">
  <circle cx="50" cy="50" r="40" fill="blue"/>
</svg>

<!-- Gradients -->
<svg xmlns="http://www.w3.org/2000/svg" width="200" height="100">
  <defs>
    <linearGradient id="grad" x1="0%" y1="0%" x2="100%" y2="0%">
      <stop offset="0%" style="stop-color:rgb(255,255,0);stop-opacity:1" />
      <stop offset="100%" style="stop-color:rgb(255,0,0);stop-opacity:1" />
    </linearGradient>
  </defs>
  <rect width="200" height="100" fill="url(#grad)" />
</svg>
```

### Malafide SVG-voorbeelden (worden geweigerd/gestript)

Deze falen of worden gestript:

```xml
<!-- Script-injectie (VERWIJDERD) -->
<svg xmlns="http://www.w3.org/2000/svg">
  <script>alert('XSS')</script>
</svg>

<!-- Event-handler (VERWIJDERD) -->
<svg xmlns="http://www.w3.org/2000/svg">
  <circle onclick="alert('XSS')" cx="50" cy="50" r="40"/>
</svg>

<!-- XXE-aanval (GEWEIGERD) -->
<!DOCTYPE svg [<!ENTITY xxe SYSTEM "file:///etc/passwd">]>
<svg xmlns="http://www.w3.org/2000/svg">
  <text>&xxe;</text>
</svg>
```

## Technische details

### Sanitization-methode

```php
private function sanitizeSVG(string $svgContent): string {
    try {
        $sanitizer = new Sanitizer();
        $sanitizer->removeRemoteReferences(true);

        $cleanSvg = $sanitizer->sanitize($svgContent);

        if ($cleanSvg === false || empty($cleanSvg)) {
            throw new \Exception('SVG-sanitization mislukt — bestand bevat mogelijk malafide content');
        }

        // Extra: weiger DOCTYPE (XXE-aanvalsvector)
        if (stripos($cleanSvg, '<!DOCTYPE') !== false) {
            throw new \Exception('SVG bevat DOCTYPE-declaratie (niet toegestaan)');
        }

        return $cleanSvg;
    } catch (\Exception $e) {
        $this->logger->error('SVG-sanitization-fout: ' . $e->getMessage());
        throw new \Exception('Ongeldig SVG-bestand: ' . $e->getMessage());
    }
}
```

### Fout-afhandeling

Wanneer SVG-sanitization faalt:

1. Fout gelogd in Nextcloud-log
2. Exception geworpen met heldere melding
3. Upload geweigerd (bestand niet opgeslagen)
4. Gebruiker krijgt foutmelding

## Bekende beperkingen

1. **Complexe animaties**: JavaScript-gebaseerde SVG-animaties worden verwijderd (gebruik CSS/SMIL)
2. **Interactieve features**: onclick-handlers en vergelijkbare interactiviteit gestript
3. **Externe resources**: remote afbeeldingen, fonts, stylesheets verwijderd
4. **DOCTYPE**: elke DOCTYPE-declaratie veroorzaakt rejectie (security-maatregel)

## Content Security Policy

IntraVox zet een strikte CSP op alle pagina-responses:

```
script-src: 'self'
frame-src: 'self' [admin-geconfigureerde video-domeinen]
```

Geen `unsafe-eval`- of `unsafe-inline`-directives. De Vue 3 runtime-only-build en TipTap-editor zijn volledig CSP-compliant.

## Feed-widget-beveiliging

### Image-proxy HMAC

Externe feed-afbeeldingen worden via een server-side proxy geserveerd om CSP-beperkingen te omzeilen. Elke afbeelding-URL is ondertekend met een HMAC-SHA256-handtekening afgeleid van het Nextcloud-systeem-secret. Alleen ondertekende URL's worden geproxied — niet-ondertekende of gemanipuleerde URL's worden geweigerd met HTTP 403. De proxy zet `Content-Security-Policy: default-src 'none'`, `X-Content-Type-Options: nosniff` en `Referrer-Policy: no-referrer` op alle responses.

### SSRF-bescherming

Alle uitgaande HTTP-requests van het feed-systeem valideren target-URLs tegen private/gereserveerde IP-ranges via `gethostbynamel()` met `FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE`. Dit geldt voor RSS-feeds, REST-API-verbindingen, LMS-API-calls, SharePoint-Graph-API en de image-proxy.

### Response-grootte-limiet

API-responses groter dan 10 MB worden geweigerd vóór JSON-decoding om out-of-memory te voorkomen. Beschermt tegen verkeerd geconfigureerde of malafide externe API's die buitensporig grote payloads teruggeven.

### Token-opslag

API-tokens en OAuth2-client-secrets zijn at-rest versleuteld via Nextcloud's `ICrypto`-service. Per-user OAuth2-tokens worden in versleutelde user-preferences opgeslagen en automatisch opgeruimd bij gebruikers-verwijdering. Tokens worden nooit opgenomen in feed-verbindings-exports, API-responses of log-berichten.

## Rate limiting

Alle muterende API-endpoints zijn rate-limited via Nextcloud's ingebouwde throttle-attributen. Zie [Schaalbaarheid & enterprise-readiness](scalability.md#rate-limiting) voor de volledige rate-limit-tabel.

## AVG-gebruikers-verwijdering

Wanneer een Nextcloud-gebruiker wordt verwijderd, verwijdert IntraVox's `UserDeletedListener` automatisch alle gerelateerde data: analytics-records, pagina-locks, feed-tokens, LMS-OAuth-tokens.

## Audit-logging

Administratieve operaties (bulk delete/move, licentie-wijzigingen, instellings-updates) worden gelogd met de `IntraVox Audit:`-prefix op `info`-niveau voor SIEM-integratie.

## Compliance

Deze implementatie volgt:

- **OWASP Top 10**: bescherming tegen XSS, XXE en injectie-aanvallen
- **CSP best practices**: strikte CSP zonder `unsafe-eval`, img-tag-rendering voorkomt script-uitvoering
- **AVG**: geautomatiseerde gebruiker-data-cleanup bij account-verwijdering
- **Industriestandaarden**: dezelfde aanpak als WordPress, Drupal, GitHub

## Bronnen

- [OWASP XSS Prevention Cheat Sheet](https://cheatsheetseries.owasp.org/cheatsheets/Cross_Site_Scripting_Prevention_Cheat_Sheet.html)
- [enshrined/svg-sanitize-bibliotheek](https://github.com/darylldoyle/svg-sanitizer)
- [SVG Security Best Practices](https://www.w3.org/TR/SVG/security.html)

## Versie-geschiedenis

- **v0.8.0** (2025-12-16): initiële SVG-ondersteuning met sanitization
  - enshrined/svg-sanitize v0.20
  - Server-side sanitization
  - DOCTYPE-rejectie
  - Multi-layer-defense-aanpak

## Contact

Voor beveiligings-zorgen of bug-meldingen:

- **GitHub Issues**: [github.com/voxcloud/intravox/issues](https://github.com/voxcloud/intravox/issues)

---

**Laatst bijgewerkt**: 2025-12-16
**Document-versie**: 1.0
