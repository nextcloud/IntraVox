# IntraVox App Store Release Checklist

Doorloop deze checklist voor elke release naar de Nextcloud App Store.

---

## 0. Certificaat Verificatie (KRITIEK!)

**⚠️ BELANGRIJK:** Controleer VOOR elke release dat de signing key overeenkomt met het App Store certificaat!

- [ ] Verifieer dat de signing key matcht met het App Store certificaat:
  ```bash
  # Hash van lokale signing key
  openssl rsa -in intravox.key -pubout 2>/dev/null | openssl md5

  # Hash van App Store certificaat (moet IDENTIEK zijn!)
  curl -s "https://apps.nextcloud.com/api/v1/apps.json" | \
    python3 -c "import json,sys; [print(a['certificate']) for a in json.load(sys.stdin) if a['id']=='intravox']" | \
    openssl x509 -pubkey -noout 2>/dev/null | openssl md5
  ```
- [ ] Beide MD5 hashes zijn **IDENTIEK**
- [ ] Controleer certificaat serienummer en geldigheid:
  ```bash
  curl -s "https://raw.githubusercontent.com/nextcloud/app-certificate-requests/master/intravox/intravox.crt" | \
    openssl x509 -text -noout 2>/dev/null | grep -A1 "Serial Number"
  ```

### Waarschuwingen over certificaten:
- **VRAAG NOOIT onnodig een nieuw certificaat aan** - dit revoceert automatisch het oude!
- Vraag alleen een nieuw certificaat aan als de private key is gecompromitteerd of kwijt
- Bewaar `intravox.key` veilig (backup op veilige locatie, NIET in git!)
- Na certificaat-wijziging: download het nieuwe certificaat en bewaar bij de key

---

## 1. Code Kwaliteit & Beveiliging

- [ ] Verwijder alle `console.log()` en debug statements uit JavaScript (`src/`)
- [ ] Verwijder alle `error_log()` en debug code uit PHP (`lib/`)
- [ ] Controleer op hardcoded credentials, API keys of wachtwoorden
- [ ] Zorg dat `.gitignore` up-to-date is (keys, certificates, .env files)
- [ ] Verifieer dat `intravox.key` en andere gevoelige bestanden NIET in de repository staan
- [ ] Voer `npm audit` uit en los kritieke vulnerabilities op
- [ ] Controleer op XSS, SQL injection en andere OWASP kwetsbaarheden
- [ ] Review alle nieuwe code op security issues

---

## 2. Vertalingen (l10n/)

- [ ] Controleer of alle nieuwe strings zijn vertaald in alle 4 talen:
  - [ ] Engels (en)
  - [ ] Nederlands (nl)
  - [ ] Duits (de)
  - [ ] Frans (fr)
- [ ] Valideer JSON syntax in alle vertaalbestanden (`l10n/*.json`)
- [ ] Test de applicatie in elke taal op ontbrekende of afgekapte teksten
- [ ] Controleer demo-data vertalingen (`demo-data/en/`, `de/`, `nl/`, `fr/`)
- [ ] Verifieer dat `.js` vertaalbestanden zijn gegenereerd

---

## 3. Versie Management

- [ ] Bepaal het nieuwe versienummer (semantic versioning: MAJOR.MINOR.PATCH)
- [ ] Update versienummer in `package.json`
- [ ] Update versienummer in `appinfo/info.xml`
- [ ] Voer `npm run version:sync -- --check` uit om te verifiëren dat versies matchen
- [ ] Update `CHANGELOG.md` met alle wijzigingen voor deze release:
  - [ ] Nieuwe features
  - [ ] Bug fixes
  - [ ] Breaking changes
  - [ ] Bekende issues

---

## 4. API Documentatie

- [ ] Update `openapi.json` met eventuele API wijzigingen
- [ ] Valideer dat de OpenAPI spec correct is (geen syntax errors)
- [ ] Controleer dat alle nieuwe endpoints gedocumenteerd zijn
- [ ] Update response schemas indien gewijzigd
- [ ] Verifieer API versienummer in `openapi.json`

---

## 5. Build & Testing

- [ ] Verwijder `node_modules/` en voer `npm ci` uit (clean install)
- [ ] Voer `npm run build` uit zonder errors of warnings
- [ ] Controleer bundle size met `npm run analyze` (geen onverwachte groei)
- [ ] Test alle core functionaliteiten handmatig:
  - [ ] Pagina's aanmaken, bewerken, verwijderen
  - [ ] Navigatie en menu's
  - [ ] Media upload en beheer
  - [ ] Reacties en commentaar
  - [ ] Export en import
  - [ ] Zoekfunctionaliteit
- [ ] Test op een schone Nextcloud installatie
- [ ] Controleer browser console op JavaScript errors
- [ ] Test met verschillende browsers:
  - [ ] Chrome
  - [ ] Firefox
  - [ ] Safari
  - [ ] Edge
- [ ] Test met GroupFolders extensie geïnstalleerd
- [ ] Test de demo-data setup wizard

---

## 6. Nextcloud Compatibiliteit

- [ ] Controleer min/max Nextcloud versie in `appinfo/info.xml`
- [ ] Test op de minimum ondersteunde Nextcloud versie (NC 32)
- [ ] Test op de nieuwste Nextcloud versie
- [ ] Verifieer PHP versie vereiste (8.1+) in `appinfo/info.xml`
- [ ] Controleer dat alle Nextcloud API calls nog werken
- [ ] Test met de laatste versie van @nextcloud/vue

---

## 7. Assets & Bestanden

- [ ] Controleer dat alle benodigde bestanden in de tarball komen:
  - [ ] `appinfo/` (info.xml, routes.php)
  - [ ] `lib/` (PHP backend)
  - [ ] `js/` (compiled JavaScript)
  - [ ] `css/` (stylesheets)
  - [ ] `img/` (icons)
  - [ ] `l10n/` (translations)
  - [ ] `templates/` (PHP templates)
  - [ ] `demo-data/` (demo content)
  - [ ] `README.md`
  - [ ] `CHANGELOG.md`
  - [ ] `LICENSE`
- [ ] Verifieer dat `src/` NIET in de tarball zit (alleen compiled code)
- [ ] Controleer app icon (512x512) voor App Store
- [ ] Update screenshots indien UI is gewijzigd

---

## 8. Git & Repository

- [ ] Alle wijzigingen zijn gecommit
- [ ] Geen uncommitted changes aanwezig
- [ ] Branch is up-to-date met main/master
- [ ] Merge conflicts zijn opgelost
- [ ] Controleer dat gevoelige bestanden niet in git history staan

---

## 9. Release Package

- [ ] Voer `./create-release.sh <version> <label> "<description>"` uit
- [ ] Controleer dat de tarball correct is aangemaakt
- [ ] Verifieer de inhoud van de tarball (`tar -tzf intravox-x.x.x.tar.gz`)
- [ ] **BELANGRIJK:** Controleer dat de root folder in de tarball `intravox` heet (NIET `intravox-x.x.x`)
  - App Store vereist lowercase folder naam zonder versienummer
  - Indien nodig, handmatig herpackagen (zie hieronder)
- [ ] Push naar beide remotes:
  - [ ] `git push gitea main`
  - [ ] `git push github main`
- [ ] Upload tarball naar GitHub release: `gh release upload vX.X.X intravox-x.x.x.tar.gz --clobber`
- [ ] Genereer signature met de juiste key:
  ```bash
  openssl dgst -sha512 -sign /pad/naar/intravox.key /pad/naar/intravox-x.x.x.tar.gz | openssl base64 -A
  ```
- [ ] Upload naar Nextcloud App Store:
  - [ ] Download URL (LOWERCASE!): `https://github.com/nextcloud/intravox/releases/download/vX.X.X/intravox-x.x.x.tar.gz`
  - [ ] Signature (nieuw genereren na elke tarball wijziging!)
  - [ ] Release notes

### Tarball Herpackagen (indien folder naam fout is)

Als de tarball `intravox-x.x.x/` als root folder heeft i.p.v. `intravox/`:

```bash
# Maak nieuwe tarball met correcte folder naam
TEMP_DIR=$(mktemp -d) && \
mkdir -p "$TEMP_DIR/intravox" && \
cp -r appinfo lib l10n templates css img js "$TEMP_DIR/intravox/" && \
cp -r scripts demo-data README.md CHANGELOG.md LICENSE openapi.json "$TEMP_DIR/intravox/" 2>/dev/null || true && \
cd "$TEMP_DIR" && \
tar -czf intravox-x.x.x.tar.gz intravox && \
mv intravox-x.x.x.tar.gz /pad/naar/IntraVox/ && \
rm -rf "$TEMP_DIR"

# Upload nieuwe tarball en genereer nieuwe signature
gh release upload vX.X.X intravox-x.x.x.tar.gz --clobber
openssl dgst -sha512 -sign /pad/naar/intravox.key intravox-x.x.x.tar.gz | openssl base64 -A
```

---

## 10. Post-Release Verificatie

- [ ] Installeer de app vanuit de App Store op een test server
- [ ] Verifieer dat de app correct werkt na installatie
- [ ] Controleer dat de versie correct wordt weergegeven
- [ ] Test de upgrade path van de vorige versie
- [ ] Sync beide remotes:
  - [ ] Gitea (intern)
  - [ ] GitHub (publiek)
- [ ] Maak een release announcement indien major release
- [ ] Update externe documentatie indien nodig

---

## 11. Rollback Plan

- [ ] Backup van de vorige release is beschikbaar
- [ ] Rollback procedure is getest
- [ ] Test server beschikbaar voor noodgevallen
- [ ] Contactgegevens van key stakeholders bij de hand

---

## Quick Commands

```bash
# Versie synchronisatie controleren
npm run version:sync -- --check

# Versie updaten
npm run version:sync -- X.Y.Z

# Production build
npm run build

# Bundle analyse
npm run analyze

# Security audit
npm audit

# Release maken (Gitea)
./create-release.sh <version> <label> "<description>"

# Deploy naar test server
./deploy.sh 1dev
```

---

## Quick Release Flow

Volledige release naar Gitea + GitHub + App Store. Gebruik dit wanneer Claude "maak release" uitvoert:

### 1. Voorbereiding
```bash
npm run version:sync -- --check
npm run build
```

### 2. Commit & Push (beide remotes)
```bash
git add -A
git commit -m "Release vX.Y.Z - [Label]"
git push gitea main
git push github main
```

### 3. Tag (beide remotes)
```bash
git tag -a vX.Y.Z -m "Release vX.Y.Z - [Label]"
git push gitea vX.Y.Z
git push github vX.Y.Z
```

### 4. Tarball maken
**BELANGRIJK:** Root folder moet `intravox` zijn (lowercase, geen versienummer)

```bash
TEMP_DIR=$(mktemp -d) && \
mkdir -p "$TEMP_DIR/intravox" && \
cp -r appinfo lib l10n templates css img js scripts demo-data docs "$TEMP_DIR/intravox/" && \
cp openapi.json CHANGELOG.md LICENSE README.md "$TEMP_DIR/intravox/" && \
cd "$TEMP_DIR" && \
tar -czf intravox-X.Y.Z.tar.gz intravox && \
mv intravox-X.Y.Z.tar.gz ~/Documents/Development/IntraVox/ && \
rm -rf "$TEMP_DIR"
```

**Niet opnemen:** src/, node_modules/, screenshots/, .git/, *.key

### 5. GitHub Release
```bash
gh release create vX.Y.Z --repo nextcloud/IntraVox --title "vX.Y.Z - [Label]" --notes "[notes]"
gh release upload vX.Y.Z intravox-X.Y.Z.tar.gz --repo nextcloud/IntraVox
```

### 6. Signature genereren (voor App Store)
```bash
openssl dgst -sha512 -sign intravox.key intravox-X.Y.Z.tar.gz | openssl base64 -A
```

### 7. App Store Upload
- **URL:** `https://github.com/nextcloud/intravox/releases/download/vX.Y.Z/intravox-X.Y.Z.tar.gz`
  - **BELANGRIJK:** Gebruik lowercase `intravox` in de URL (GitHub is case-insensitive maar App Store vereist lowercase)
- **Signature:** Output van stap 6

**Let op:** Signature opnieuw genereren na elke tarball wijziging!

---

## Notities

- **Minimale Nextcloud versie:** 32
- **PHP versie:** 8.1+
- **Ondersteunde talen:** en, nl, de, fr
- **Vereiste extensies:** GroupFolders
- **App Store:** https://apps.nextcloud.com/apps/intravox

---

*Laatst bijgewerkt: Januari 2026*
