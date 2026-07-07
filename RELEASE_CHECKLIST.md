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

## 1b. Dependency parity with Nextcloud core

IntraVox bundles `@nextcloud/vue` instead of using the runtime version from the NC server. If the bundled version lags behind what NC core ships, app UI starts to look subtly different from NC's own apps (notably sidebar tabs, NcDialog, NcButton — visual language shifted in 9.6+). The version that ships in this release should be ≥ the version NC bundles for our `min-version` target.

- [ ] Check the bundled major.minor against NC core for the target NC version:
  ```bash
  cat node_modules/@nextcloud/vue/package.json | grep '"version"'
  ```
  Reference (update this table when NC bumps): NC 32 → nc-vue 8.x ; NC 33 → nc-vue 9.6+ ; NC 34 → check `apps/files/package.json` on a fresh server.
- [ ] If bundled is older than NC core's minor, bump:
  ```bash
  npm install @nextcloud/vue@^<X.Y.0>
  ```
  Then `npm run build` and visually verify the PageDetailsSidebar tabs match the NC Files sidebar — that's the canary for shifts in nc-vue look-and-feel.
- [ ] Commit `package.json` + `package-lock.json` together so the bump is reproducible.

---

## 2. Translations (l10n/)

Since v1.6.0, IntraVox translations come from Transifex (`o:nextcloud:p:nextcloud:r:intravox`) via the Nextcloud hosted l10n bot. **As of v1.8.x the fragile "merge `-X ours` + re-add feature strings" dance is gone** — a prebuild guard (`scripts/check-l10n-sync.js`) now guarantees new source strings reach Transifex *before* release, so the bot no longer drops them. Read the model once; after that, releases are a plain merge.

### The model (read once)

- **Source strings live in code** (`t('intravox',…)`, `$t(…)`, `$l->t(…)` in `src/**` and `lib/**`). The committed manifest `l10n/.source-strings.json` (a sha256 + the full sorted msgid list) records the exact set the bot has been given.
- **The moment you add/change/remove a translatable string, push it to Transifex — the same day, not at release.** The prebuild guard fails the build until you do:
  ```bash
  npm run l10n:push        # extract → lint → regenerate the POT + manifest
  git add translationfiles/templates/intravox.pot l10n/.source-strings.json l10n/.source-count.json
  git commit -s -m "l10n: push <N> new source strings to Transifex"
  git push github main     # the Nextcloud bot reads the POT from GitHub only
  ```
  The bot then ingests the POT and **deletes it** in its next `fix(l10n)` commit — that delete is **normal**; the POT is a transient handoff file, the durable record is the manifest. Translators now have the strings, with lead time.
- **Runtime source of truth is the bot's paired `l10n/<lang>.{js,json}` files.** Never hand-edit them. **Never run `npm run l10n:generate-js`** to "reconcile" with the bot — it regenerates `.js` from `.json` and silently drops any string missing from `.json`, desyncing the pair. That script exists only for genuine first-time/local generation. (There is intentionally no bare `npm run l10n` any more — it was the footgun that caused this.)
- Do **not** switch POT generation to `translationtool.phar`/bare `xgettext` — xgettext does not parse Vue templates and drops ~700 frontend strings. The `l10n/en.json` extractor (`scripts/extract-en-json.js`) scans both `src/**` and `lib/**`, so it is the complete source for frontend + PHP.

### Why this used to break every release

New feature strings were added in code but `npm run pot` + committing the POT was skipped. The bot therefore never saw them → translators couldn't translate them → the next bot sync regenerated `l10n/<lang>.{js,json}` from Transifex (which lacked them) and **deleted** them from every language. The `-X ours` merge + hand re-adding strings was a workaround that also desynced `.js` from `.json`. The manifest guard removes the root cause: you cannot build without the strings being pushed.

### At release time — just merge the bot

Because strings were already pushed and translated, the release step is a plain merge of the bot's work. **No `-X ours`, no re-adding strings, no `git rm` of near-empty langs.**

- [ ] Fetch + confirm the bot's commits are l10n-only, then plain-merge:
  ```bash
  git fetch github main
  git log --oneline HEAD..github/main                       # should be only fix(l10n) bot commits
  git diff --name-only HEAD..github/main | grep -v '^l10n/'  # must print nothing
  git merge github/main --no-edit                            # plain merge — NO strategy flag
  ```
- [ ] Assert code and the pushed manifest agree (this is the whole guarantee):
  ```bash
  npm run lint:l10n        # ✓ l10n source strings in sync (N strings)
  ```
  **If this FAILS**, you added strings since the last push — run the `npm run l10n:push` block above, commit, push github, and wait for the next bot sync **before** cutting the tarball. Do not proceed with a red guard.
- [ ] Validate JSON + `.js` shape:
  ```bash
  node -e "require('fs').readdirSync('l10n').filter(f=>f.endsWith('.json')&&!f.startsWith('.')).forEach(f=>JSON.parse(require('fs').readFileSync('l10n/'+f,'utf8')))"
  for f in l10n/*.js; do head -c 50 "$f" | grep -q '^OC.L10N.register' || echo "BAD: $f"; done
  ```

### de_DE / fr / nl policy (unchanged, per rakekniven #63)

Follow the bot for `de_DE` and `fr`: ship whatever the bot has, do **not** hand-bundle reviewed de/fr (that fights the resource and re-conflicts every sync). `nl` is the maintainer's language and the canary — after the merge, verify a few recent feature strings survived:
```bash
for s in "Set as homepage" "Copy page" "Manage structure"; do grep -c "\"$s\"" l10n/nl.js; done   # each ≥1
```
If nl is missing recent strings here, the push step was skipped earlier — `lint:l10n` will also be red. Fix by pushing (above) and waiting for the next bot sync; **do not patch nl.js by hand.**

### Transifex account & team access (needed to *upload* translations)

> Applies to **every VoxCloud app** on the Nextcloud Transifex org (`o:nextcloud:p:nextcloud`) — IntraVox, IntroVox, MetaVox, SearchVox, FormVox, … Whoever does l10n needs the right Transifex access, and it is **not** the same as the read token used elsewhere.

Two different permissions, don't confuse them:

- **Reading** (POT download, `resource_language_stats`, `tx pull`) works with any read token in `~/.transifexrc`. The daily bot sync and the normal release flow need nothing more.
- **Uploading translations** (seeding reviewed `de`/`fr`, filling empties via the API or the web UI) requires **two** things:
  1. Your Transifex account (e.g. `rik@shalution.nl`) must be a **member of the language team for each target language** in the Nextcloud org, with at least the **Translator** role. On the resource page (transifex.com → Nextcloud → the app's resource) use **Join Team** and pick the languages (e.g. German `de`/`de_DE`, French `fr`). Team membership may need coordinator approval — the NC l10n coordinator is **rakekniven** (see #63).
  2. An **API token that inherits those rights** — a token created *after* you joined the teams, from Settings → API on transifex.com. A token minted while you were read-only stays read-only.

**Symptom of missing rights:** a translation upload returns **HTTP 403 `permission_denied`** (even though downloads/stats work). A per-string PATCH may instead 404 on `resource_translations?filter[language]=l:<lang>` — that endpoint is unreliable here; use the PO round-trip (async upload) instead.

**Safe upload method** (fill-only, never clobbers community work — proven on IntraVox de/fr in #63): download the *current* PO for the language, merge your reviewed translations into **only the empty `msgstr` entries**, `msgfmt --check-format`, then upload via `resource_translations_async_uploads`. A successful run reports `translations_updated: 0` (only `translations_created` > 0) — that 0 is your guarantee no existing translation was overwritten. Do this **quickly** after joining: any community translation created between your download and upload could otherwise be missed (rakekniven's warning in #63). If you can't get team access, hand the merged `.po` files to the coordinator to import.

> Note the app-specific gotcha: the resource id uses the app slug verbatim — IntraVox is `…:r:intravox` (with an 'a'), **not** `introvox` (a different app). Never upload one app's PO into another's resource. Double-check the `[o:…:r:<slug>]` line in `.tx/config`.

### Still true — github is ahead of gitea on l10n

The bot pushes only to github. Release push order: **merge github → push github → push gitea** (never force-push; it wipes bot translations).

### Still true — build-artefact hygiene

`l10n/en.json`, `l10n/en.js`, `l10n/.source-count.js` and `l10n/.source-strings.json` are dev artefacts — scrub them from the tarball staging dir (see §7). Only `l10n/.source-count.json` (runtime coverage denominator) and the bot's `l10n/<lang>.{js,json}` ship. Verify translations are actually in the tarball:
```bash
tar -tzf intravox-X.Y.Z.tar.gz | grep -E 'l10n/(nl|de_DE|fr)\.(js|json)$'    # present
tar -xzOf intravox-X.Y.Z.tar.gz intravox/l10n/nl.js | grep -oc '" : "'         # substantial, not a stub
tar -tzf intravox-X.Y.Z.tar.gz | grep -E 'l10n/(en\.js|en\.json|\.source-count\.js|\.source-strings\.json)$'   # must be empty
```
After deploying, a browser still showing English usually means a **stale cache / unchanged version** (NC's cache-buster is `md5(appVersion)` — every release must bump the version), not a missing translation.

- [ ] **Do NOT** require identical keys across languages — incomplete translations fall back to English at runtime.
- [ ] Translation typos live on Transifex and **cannot** be fixed durably in the repo — report them to the language team on transifex.com.

### The everyday developer loop (not just releases)

```
add/change strings in src/** or lib/**  →  npm run build  →  guard FAILS ("strings changed, not pushed")
  →  npm run l10n:push  →  commit POT + .source-strings.json + .source-count.json  →  git push github main
  →  bot ingests POT (uploads + deletes it)  →  translators translate  →  bot syncs l10n/<lang>.{js,json} back
  →  release:  git fetch github && git merge github/main --no-edit  →  npm run lint:l10n ✓  →  tarball/sign/upload
```
A check-only GitHub Action (`.github/workflows/l10n.yml`) runs the same guard on push to main as a backstop; it never writes or auto-commits (that would fight the bot's POT delete).

> **Cross-reference**: [`IntroVox/RELEASE_CHECKLIST.md §2`](../IntroVox/RELEASE_CHECKLIST.md) uses the same Transifex pool — mirror whichever app has the guard first.

---

## 3. Version Management

- [ ] Determine new version number (semantic versioning: MAJOR.MINOR.PATCH)
- [ ] Run `node scripts/sync-version.js X.Y.Z` to update both `package.json` and `appinfo/info.xml` in one go
- [ ] Manually update `openapi.json` `"version": "X.Y.Z"` — `sync-version.js` does NOT touch it yet (current `openapi.json` is at 1.5.5 while app is at 1.6.0; fix on next release)
- [ ] Verify all four locations match:
  ```bash
  grep '"version"' package.json | head -1
  grep '<version>' appinfo/info.xml | head -1
  grep '"version"' openapi.json | head -1
  ```
- [ ] Verify with: `npm run build` (prebuild script checks package.json↔info.xml automatically)
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

- [ ] **Do NOT** run `npm run l10n:generate-js` at release — the `l10n/<lang>.js` files are the bot's paired output, not regenerated from `.json` here (that would drop strings). The bot ships both `.js` and `.json`.
- [ ] Source strings should already be pushed to Transifex (do it the day you add them, see §2). If `npm run lint:l10n` is red, run `npm run l10n:push`, commit the POT + manifest, `git push github main`, and wait for the bot before releasing.
- [ ] Run `npm run build` without errors (its `prebuild` runs `check-l10n-sync.js` — a red guard here means unpushed strings)
  - Bundle size warnings for main/admin are normal (TipTap editor)
- [ ] Test core functionalities on 3dev:
  - [ ] Page CRUD, navigation, media upload
  - [ ] Public share links (with and without password)
  - [ ] Share dialog, share button states
  - [ ] News widget (authenticated and public)
  - [ ] Calendar widget (select calendars, verify events, recurring events, date ranges)
  - [ ] Calendar widget in side column (compact layout) and main content (multi-column)
  - [ ] Calendar widget with Light and Primary background colors
  - [ ] Comments and reactions
  - [ ] Demo data import
  - [ ] **Available languages admin section** (Demo Data tab) — enable/disable a language, verify it appears/disappears from menus
  - [ ] **Language activation creates empty homepage** (try enabling a new language for an existing install)
- [ ] Check browser console for errors
- [ ] Test with GroupFolders extension

---

## 6. Nextcloud Compatibility

- [ ] Check `appinfo/info.xml`: `<nextcloud min-version="32" max-version="34"/>` (update max as new NC versions release; current confirmed range as of June 2026)
- [ ] PHP requirement: `<php min-version="8.2"/>` (matches composer.json; NC34 requires PHP `>=8.2 <8.6`)
- [ ] Test on target Nextcloud version (3dev: NC33; hetzner nc-dev: NC33)
- [ ] **Bundled lib parity** with NC34: `@nextcloud/vue` ≥ 9.8 (NC34 ships 9.8.x); Vue ≥ 3.5 (NC34 ships 3.5.x). See §1b.
- [ ] **Enterprise detection** (when relevant): IntraVox uses `OCP\Util::hasExtendedSupport` for Enterprise gating, not config-fallback (anti-spoofing). Verify behaviour on a non-Enterprise instance after each NC major bump — the API surface can shift.
- [ ] **OC.* globals** (legacy front-end): IntraVox still references `OC.dialogs.filepicker`, `OC.MimeType.getIconUrl`, `OC.L10N.translate`, `OC.requestToken`, `OC.webroot`. All five remain functional in NC34 stable (deprecated, not removed). Migration to `@nextcloud/*` equivalents is a 1.7+ task — not blocking for NC34 support.

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

**Exclude from tarball:** `src/`, `node_modules/`, `screenshots/`, `docs/`, `.git/`, `*.key`, `deploy.sh`, `openapi.json`, `scripts/`, `.tx/`, `.l10nignore`, `translationfiles/`, `examples/`, `showcases/`, `testdata/`

> ⚠️ **`cp -r l10n …` copies dev-only l10n files too.** `l10n/en.json`, `l10n/en.js` and
> `l10n/.source-count.js` are gitignored build artefacts; `l10n/.source-strings.json` is
> committed but dev-only (the source-string manifest for the prebuild guard). None of these
> belong in the release — only `l10n/.source-count.json` (the runtime coverage denominator)
> and the bot's `l10n/<lang>.{js,json}` ship. After copying `l10n/` into the tarball staging
> dir, delete the dev-only ones:
> ```bash
> rm -f "$TEMP_DIR/intravox/l10n/en.json" "$TEMP_DIR/intravox/l10n/en.js" \
>       "$TEMP_DIR/intravox/l10n/.source-count.js" "$TEMP_DIR/intravox/l10n/.source-strings.json"
> ```
> Note: the §9.2 loose `grep -iE '…|en\.json|…'` throws **false positives** on demo-data paths
> containing the substring "en" (e.g. `evenementen.json`). Use the precise anchored check:
> `grep -iE '\.(key|pem|crt|env)$|/\.git/|/src/|\.tx/|translationfiles/|l10n/en\.(js|json)$|source-count\.js$|source-strings\.json$'`.

> **`npm run release` (`create-release.sh`) is NOT the App Store flow.** It only tags
> (`vX.Y.Z-Label` form) and uploads artefacts to Gitea. For an App Store release follow the
> manual §9 steps below (tag `vX.Y.Z`, GitHub release, sign, upload).

The `.tx/`, `.l10nignore`, and `translationfiles/` are dev-only artefacts for Transifex sync. Runtime IntraVox loads translations only from `l10n/*.{js,json}`.

> **POT detail**: the generated `translationfiles/templates/intravox.pot` contains absolute-path source-file references (`#: /Users/rikdekker/Documents/Development/IntraVox/lib/...`). This is harmless — Transifex only uses msgids, not the comments — and the POT is excluded from the tarball anyway. Don't try to make those paths repo-relative; the NC sync-bot regenerates the POT server-side with its own paths.

---

## 8. Git & Repository

- [ ] All changes committed
- [ ] No uncommitted changes: `git status`
- [ ] Sensitive files not tracked: `git ls-files | grep -iE '\.(key|crt|pem|env)$'`

---

## 9. Release Package

### 9.1 Create Tarball

> ⚠️ **STOP** — before running this: (1) is `npm run lint:l10n` green (source strings pushed)? (2) did you merge the bot's latest translations (§2 "At release time — just merge the bot")? A tarball cut before the merge ships the wrong set of languages and must be regenerated. Run `git fetch github && git log --oneline HEAD..github/main` — if it's empty, you're already up to date.

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
tar -tzf intravox-X.Y.Z.tar.gz | grep -iE '(credential|\.key|\.env|deploy|\.git/|node_modules|src/|\.pem|\.crt|\.tx/|translationfiles/)'

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
# 0. Source strings must already be pushed (§2). Confirm the guard is green:
npm run lint:l10n                            # red → npm run l10n:push, commit, push github, wait for bot

# 1. Merge the bot's latest translations (plain merge — NO -X ours)
git fetch github main
git log --oneline HEAD..github/main          # only fix(l10n) bot commits; if empty, skip the merge
git merge github/main --no-edit

# 2. Prep — do NOT run npm run l10n:generate-js (js is the bot's output)
npm run build                                # prebuild re-runs check-l10n-sync.js

# 3. Commit & tag
git add -A
git commit -s -m "Release vX.Y.Z - [Label]"
git tag -a vX.Y.Z -m "Release vX.Y.Z - [Label]"

# 4. Push to BOTH remotes (github first — it's usually ahead from the bot)
git push github main --tags
git push gitea main --tags

# 5. Tarball — AFTER step 1 merged, never before (see section 9.1)

# 6. Deploy & test
./deploy.sh  # select 3dev

# 7. Sign & upload (see sections 9.5-9.7)
```

---

## Notes

- **App ID:** `intravox`
- **Nextcloud version:** 32-34 (check info.xml for actual range)
- **PHP version:** >= 8.2
- **Translation pool:** Transifex resource `o:nextcloud:p:nextcloud:r:intravox`
- **Bundled translations:** Whatever is in `l10n/` at release time (grows automatically as Transifex translators contribute)
- **Source of truth for POT:** `l10n/en.json` (webpack-extracted frontend strings, incl. Vue templates) + `xgettext` on `lib/**.php`, merged by `scripts/generate-pot.js`. Bare xgettext alone cannot read Vue templates — do not replace this with `translationtool.phar`.
- **Existing translations:** `l10n/<lang>.{js,json}` ship as bundled translations and are uploaded to Transifex as "translation memory" on first sync — users keep their localised UI across the 1.6.0 upgrade.
- **Transifex access:** a **read** token in `~/.transifexrc` covers the normal flow (the GH bot does the sync; you rarely need it). **Uploading** translations (seeding reviewed de/fr, filling empties) additionally needs **language-team membership (Translator role) in the Nextcloud org + a write-capable token** — see §2 "Transifex account & team access". A read-only token/account gets HTTP 403 on upload. This is the same for every VoxCloud app on `o:nextcloud:p:nextcloud`.
- **Sibling app reference:** IntroVox uses the same Transifex pool and went through onboarding two days before us — when in doubt about l10n/Transifex behaviour, check [`IntroVox/RELEASE_CHECKLIST.md`](../IntroVox/RELEASE_CHECKLIST.md) first.
- **App Store:** https://apps.nextcloud.com
- **Gitea:** (private repository)
- **GitHub:** https://github.com/nextcloud/IntraVox
- **Signing key:** `intravox.key` in project root (NOT in git!)

---

*Last updated: 2026-07-07 (later same day) — §2: added "Transifex account & team access" — uploading translations (not just reading) needs language-team membership (Translator role) in the Nextcloud org + a write-capable token; a read-only token 403s on upload. Applies to every VoxCloud app on the shared `o:nextcloud:p:nextcloud` org. Documented the safe fill-only PO upload (proven on IntraVox de/fr in #63: de 45→94%, fr 323→94%, `translations_updated: 0` = no community work overwritten) and the intravox-vs-introvox resource-slug trap. Earlier same day — §2 REWRITTEN around a durable source-string guard. Root cause of the recurring release breakage: new feature strings were added in code but `npm run pot` + committing the POT was skipped, so the Nextcloud bot never saw them → translators couldn't translate them → every bot sync deleted them from `l10n/<lang>.{js,json}`. The old `-X ours` merge + hand re-adding strings was a workaround that also desynced `.js` from `.json`. Fix: a committed manifest `l10n/.source-strings.json` (sha256 + sorted msgid list, written by `extract-en-json.js`) and a prebuild guard `scripts/check-l10n-sync.js` (wired into `prebuild`, standalone as `npm run lint:l10n`) that FAILS the build whenever code's string set diverges from the manifest — making "push strings to Transifex" (`npm run l10n:push`, decoupled from release) unmissable. Releases are now a plain `git merge github/main` (no `-X ours`, no re-add) with a green-guard assertion. The bare `npm run l10n` footgun (lossy json→js regen) is renamed to `l10n:generate-js`. A check-only `.github/workflows/l10n.yml` runs the guard on push as a backstop (never auto-commits). de_DE/fr/nl policy unchanged (follow the bot). §5/§7/§9 cross-refs updated; `.source-strings.json` added to the tarball scrub. Earlier history condensed: v1.7.0 dropped the stale per-lang targets after the #63 resource re-provision; 2026-06-18 reverted POT generation to the en.json+lib/xgettext extractor (bare xgettext drops ~700 Vue strings); `l10n/en.json`/`en.js` are gitignored artefacts; `npm run release` is Gitea-only, not the App Store flow.*
