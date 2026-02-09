# IntraVox App Store Release Checklist

Follow this checklist for every release to the Nextcloud App Store.

---

## 0. Certificate Verification (CRITICAL!)

**Before every release**, verify that your signing key matches the App Store certificate!

- [ ] Verify signing key exists in project root:
  ```bash
  ls -la intravox.key
  ```
- [ ] Verify key is NOT tracked in git:
  ```bash
  git ls-files | grep intravox.key  # Should return nothing
  ```

### Certificate Warnings:
- **NEVER request a new certificate unnecessarily** - this automatically revokes the old one!
- Only request a new certificate if the private key is compromised or lost
- Keep your `.key` file safe (backup in secure location, NOT in git!)
- After certificate change: download the new certificate and store with the key

---

## 1. Code Quality & Security

- [ ] Remove all debug `console.log()` statements from JavaScript (`src/`)
  - `console.error()` in catch blocks is OK (useful in production)
  - Search: `grep -rn "^\s*console\." src/ --include="*.js" --include="*.vue" | grep -v "// console"`
- [ ] Verify no `error_log()`, `var_dump()`, `print_r()` in PHP (`lib/`)
  - `$this->logger->debug()` is OK (proper logging)
- [ ] Check for hardcoded credentials, API keys, or passwords
- [ ] Ensure `.gitignore` is up-to-date (keys, certificates, .env files)
- [ ] Verify that sensitive files are NOT tracked in git:
  ```bash
  git ls-files | grep -iE '\.(key|crt|pem|env)$'
  ```
- [ ] Run `npm audit` — fix critical issues if possible
  - Upstream @nextcloud dependency vulnerabilities are usually not fixable
- [ ] **Check tarball for sensitive data** (see Section 9.2)

---

## 2. Translations (l10n/)

Supported languages: **EN, NL, DE, FR**

- [ ] Check that all languages have identical keys:
  ```bash
  python3 -c "
  import json
  langs = ['en','nl','de','fr']
  ref = set(json.load(open('l10n/en.json'))['translations'].keys())
  for l in langs:
      keys = set(json.load(open(f'l10n/{l}.json'))['translations'].keys())
      print(f'{l}: {len(keys)} keys', '✓' if keys == ref else f'✗ diff: {len(ref - keys)} missing')
  "
  ```
- [ ] Validate JSON syntax in all translation files
- [ ] Regenerate `.js` translation files: `npm run l10n`
- [ ] Verify JS files are newer than JSON files

---

## 3. Version Management

- [ ] Determine new version number (semantic versioning: MAJOR.MINOR.PATCH)
- [ ] Update version — all three files must match:
  - `package.json` → `"version": "X.Y.Z"`
  - `appinfo/info.xml` → `<version>X.Y.Z</version>`
  - `openapi.json` → `"version": "X.Y.Z"`
- [ ] Verify with: `npm run build` (prebuild script checks automatically)
- [ ] Update `CHANGELOG.md`:
  - [ ] Move items from `[Unreleased]` to `[X.Y.Z] - date - Label`
  - [ ] Sections: Added, Changed, Fixed, Removed, Security

---

## 4. API Documentation

- [ ] Update `openapi.json` with any new/changed API endpoints
- [ ] Validate JSON syntax: `python3 -c "import json; json.load(open('openapi.json')); print('Valid')"`
- [ ] Verify all public share endpoints are documented
- [ ] Update response schemas if changed

---

## 5. Build & Testing

- [ ] Run `npm run l10n` to regenerate JS translation files
- [ ] Run `npm run build` without errors
  - Bundle size warnings for main/admin are normal (TipTap editor)
- [ ] Test core functionalities on 3dev:
  - [ ] Page CRUD, navigation, media upload
  - [ ] Public share links (with and without password)
  - [ ] Share dialog, share button states
  - [ ] News widget (authenticated and public)
  - [ ] Comments and reactions
  - [ ] Demo data import
- [ ] Check browser console for errors
- [ ] Test with GroupFolders extension

---

## 6. Nextcloud Compatibility

- [ ] Check `appinfo/info.xml`: `<nextcloud min-version="32" max-version="32"/>`
- [ ] PHP requirement: `<php min-version="8.1"/>`
- [ ] Test on target Nextcloud version

---

## 7. Assets & Tarball Contents

Required files in tarball:

| Directory    | Contents                          |
|--------------|-----------------------------------|
| `appinfo/`   | info.xml, routes.php              |
| `lib/`       | PHP backend                       |
| `js/`        | Compiled JavaScript (with hashes) |
| `css/`       | Stylesheets                       |
| `img/`       | App icons                         |
| `l10n/`      | Translations (.json + .js)        |
| `templates/` | PHP templates                     |
| `demo-data/` | Demo content for setup wizard     |
| Root files   | CHANGELOG.md, LICENSE, README.md  |

**Exclude from tarball:** `src/`, `node_modules/`, `screenshots/`, `docs/`, `.git/`, `*.key`, `deploy.sh`, `openapi.json`, `scripts/`

---

## 8. Git & Repository

- [ ] All changes committed
- [ ] No uncommitted changes: `git status`
- [ ] Sensitive files not tracked: `git ls-files | grep -iE '\.(key|crt|pem|env)$'`

---

## 9. Release Package

### 9.1 Create Tarball

**Root folder must be `intravox` (lowercase, no version number)**

```bash
TEMP_DIR=$(mktemp -d) && \
mkdir -p "$TEMP_DIR/intravox" && \
cp -r appinfo lib l10n templates css img js demo-data "$TEMP_DIR/intravox/" && \
cp CHANGELOG.md LICENSE README.md "$TEMP_DIR/intravox/" && \
cd "$TEMP_DIR" && \
tar -czf intravox-X.Y.Z.tar.gz intravox && \
mv intravox-X.Y.Z.tar.gz /Users/rikdekker/Documents/Development/IntraVox/ && \
rm -rf "$TEMP_DIR"
```

### 9.2 Tarball Security Check (CRITICAL!)

```bash
# Verify no sensitive files
tar -tzf intravox-X.Y.Z.tar.gz | grep -iE '(credential|\.key|\.env|deploy|\.git/|node_modules|src/|\.pem|\.crt)'

# Verify root folder is "intravox/"
tar -tzf intravox-X.Y.Z.tar.gz | head -1

# Verify required directories exist
for dir in appinfo lib l10n templates js img css demo-data; do
  echo -n "$dir: "; tar -tzf intravox-X.Y.Z.tar.gz | grep "^intravox/$dir/" | wc -l
done

# Verify src/ is NOT included (should be 0)
tar -tzf intravox-X.Y.Z.tar.gz | grep 'src/' | wc -l
```

### 9.3 Push & Tag

```bash
git push gitea main --tags
git push github main --tags
```

### 9.4 Deploy to Test Server

```bash
./deploy.sh
# Select: 2 (3dev)
```

### 9.5 Generate Signature (for App Store)

```bash
# Generate signature using the LOCAL key in project root:
openssl dgst -sha512 -sign intravox.key intravox-X.Y.Z.tar.gz | openssl base64 -A
```

**Note:** The signing key is `intravox.key` in the project root (NOT on USB drive).

### 9.6 GitHub Release

```bash
gh release create vX.Y.Z intravox-X.Y.Z.tar.gz \
  --title "vX.Y.Z - [Label]" \
  --notes "$(cat <<'EOF'
## What's New in vX.Y.Z

[Summary from CHANGELOG.md]

Full changelog: https://github.com/nextcloud/IntraVox/blob/main/CHANGELOG.md
EOF
)"
```

**Download URL:**
```
https://github.com/nextcloud/IntraVox/releases/download/vX.Y.Z/intravox-X.Y.Z.tar.gz
```

### 9.7 App Store Upload

- **URL:** GitHub release download URL (lowercase `intravox` in filename!)
- **Signature:** Output from step 9.5
- **Note:** Regenerate signature after any tarball change!

---

## 10. Post-Release Verification

- [ ] Install from App Store on clean test server
- [ ] Verify version displayed correctly
- [ ] Test upgrade path from previous version
- [ ] Test demo data import on fresh install
- [ ] Test public share links in incognito browser

---

## 11. Rollback Plan

- [ ] Previous release tarball available
- [ ] Test server (3dev) available for emergencies
- [ ] `git revert` or `git checkout v<previous>` ready

---

## Quick Release Flow

```bash
# 1. Prep
npm run l10n
npm run build

# 2. Commit & tag
git add -A
git commit -m "Release vX.Y.Z - [Label]"
git tag -a vX.Y.Z -m "Release vX.Y.Z - [Label]"

# 3. Push
git push gitea main --tags
git push github main --tags

# 4. Tarball (see section 9.1)

# 5. Deploy & test
./deploy.sh  # select 3dev

# 6. Sign & upload (see sections 9.5-9.7)
```

---

## Notes

- **App ID:** `intravox`
- **Minimum Nextcloud version:** 32
- **PHP version:** >= 8.1
- **Supported languages:** EN, NL, DE, FR
- **App Store:** https://apps.nextcloud.com
- **Gitea:** (private repository)
- **GitHub:** https://github.com/nextcloud/IntraVox
- **Signing key:** `intravox.key` in project root (NOT in git!)

---

*Last updated: February 2026*
