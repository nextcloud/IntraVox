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

## 0b. Account-property scopes (first release containing the People scope fix)

The release that introduces `AccountScopePolicy` changes what People widgets
show. Two things must not be skipped.

- [ ] **Warn admins in the release notes**, not only in the CHANGELOG. Fields
      users marked private disappear from existing widgets. `email` is the
      most likely surprise: it defaults to Federated, but many instances set
      it to Local, which removes it from public-share People widgets.
- [ ] **Mention the report command as optional diagnostics** — nothing has to be run for the upgrade to work; it only tells an admin in advance what will change
      ```bash
      occ intravox:people:scope-report          # samples 1000 accounts
      occ intravox:people:scope-report --all    # every account
      ```
- [ ] **Do not "tidy" the people cache-key prefix back to `filter_shared_`.**
      It was renamed to `filter_v2_<audience>_<groupHash>_` deliberately.
      Entries written before the fix contain private fields; the rename
      abandons them. Reverting the prefix would keep serving those entries
      for up to an hour after the upgrade, silently undoing the security fix
      on every warm instance.

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

## 1a. OWASP Top 10 (2025) release gate

Loop deze lijst af bij **elke** release. De checks zijn bewust *triggers*, geen
pass/fail-gates: een hit betekent "kijk hier met je ogen naar", niet per se een bug.
Noteer bij een genegeerde hit kort *waarom* in de PR/commit.

Referentie: <https://owasp.org/Top10/2025/> · Cheat Sheets: <https://cheatsheetseries.owasp.org/>

### Uitslag van de 2.5-ronde (26-08-2026)

Twee hits die echte fixes werden, en één bevinding die bewust blijft staan.

- **A02 — footer werd opgeslagen zoals aangeleverd.** `saveFooter()` bewaarde de
  inhoud ongefilterd onder de opmerking "already sanitized by DOMPurify in the
  frontend". Dat klopt voor de editor en is irrelevant voor security: `POST
  /api/footer` is een gewoon endpoint en een handmatig verzoek draait die
  JavaScript niet. De footer rendert via `v-html` op elke pagina. Gefixt met de
  sanitizer die tekstwidgets al jaren gebruikt — dezelfde renderer, dezelfde
  auteurs, dezelfde behandeling. Niet anoniem bereikbaar (de footer gaat niet mee
  in public shares), dus begrensd tot ingelogde gebruikers.
- **A02 — tien lekken in CommentController** gaven `$e->getMessage()` terug aan de
  client. Gefixt via `ApiErrorTrait::safeErrorResponse()`.

**Bewust NIET gefixt in 2.5, wél opgeschreven:** er staan nog **42
`catch (\Exception)`-blokken die `$e->getMessage()` in een response zetten**,
waarvan 38 in `ApiController`. Geteld met:

```bash
python3 - <<'EOF'
import re,glob,os
tot=0
for f in sorted(glob.glob('lib/Controller/*.php')):
    src=open(f,newline='').read(); hits=0
    for m in re.finditer(r'catch \(\\?Exception \$e\) \{(.*?)(?=\n        \}|\n    \}|catch \()', src, re.S):
        body=re.sub(r'\$this->logger->\w+\([^;]*?\);','',m.group(1),flags=re.S)
        if re.search(r"'error'\s*=>\s*\$e->getMessage\(\)", body): hits+=1
    if hits: print(f"{os.path.basename(f):<34} {hits}")
    tot+=hits
print('TOTAAL:',tot)
EOF
```

Waarom uitgesteld: dit is 42 losse beslissingen over wat een client *wel* mag
weten, elk met een eigen publieke boodschap, in een release die al een groot
gedragsoppervlak raakt. Ze in één sweep genericeren is precies hoe je de twee
contract-sites in `CommentController` sloopt (zie `CommentErrorLeakTest`).
Vervolgstap voor 2.6, per controller, met dezelfde aanpak als hier.

Let op de meetfout die makkelijk is: een kale `grep getMessage()` telt er 122.
Daar zitten `logger->error()`-aanroepen bij (geen lek) en `catch`-blokken op
app-eigen types als `InvalidArgumentException` en `PageNotFoundException`, waarvan
de boodschap door deze app zelf geschreven is (geen lek). Alleen generieke
`\Exception`-vangsten tellen.

- **A03** — `npm audit`: 0 vulnerabilities.
- **A04** — geen `rand()`/`uniqid()` op security-paden; de vier hits zijn
  bestandsnamen en nav-ids. Alle drie de signature-vergelijkingen gebruiken
  `hash_equals()`.
- **A05** — één `v-html` zonder zichtbare sanitizer (`Footer.vue:15`); dat was de
  A02-bevinding hierboven en is nu server-side afgedekt.

### A01 — Broken Access Control

- [ ] Elke nieuwe/gewijzigde controller-methode heeft een bewuste access-attribute.
      Geen attribute = admin-only (NC-default). Controleer dat dat ook de bedoeling was:
  ```bash
  grep -rn --include='*.php' -B4 'public function' lib/Controller/ \
    | grep -E '#\[(NoAdminRequired|PublicPage|AuthorizedAdminSetting)\]|public function'
  ```
- [ ] Bij elke `#[NoAdminRequired]`: wordt de *ownership* van het object nog apart
      gecheckt? Ingelogd zijn is geen autorisatie — een user mag niet via een geraden
      `fileId`/`id` bij andermans data (IDOR).
- [ ] Bij elke `#[PublicPage]`: is er een token/secret-check, en gebeurt die met
      `hash_equals()` (niet `===`)?
- [ ] Share-scope: bij wijzigingen aan share-/permissie-logica, test expliciet als
      **anonieme** gebruiker én als user *zonder* rechten — niet alleen als eigenaar.

### A02 — Security Misconfiguration

- [ ] Geen debug-/verbose-output in de release-build (zie sectie 1).
- [ ] Foutmeldingen naar de client lekken geen paden, stacktraces of SQL.
- [ ] Nieuwe appconfig-defaults zijn *secure by default* (dicht, niet open).
- [ ] Als de app externe content of iframes rendert: CSP-policy nog passend?

### A03 — Software Supply Chain Failures *(nieuw in 2025, #3)*

- [ ] `npm audit` — kritieke issues opgelost of expliciet verantwoord.
      Upstream `@nextcloud/*` issues zijn vaak niet fixbaar; noteer dat dan.
- [ ] Lockfile is gecommit en hoort bij deze release-build.
- [ ] Nieuwe dependency toegevoegd sinds vorige release? Check even:
      onderhouden, redelijk gebruikt, en de licentie past.
  ```bash
  git diff <vorige-tag>..HEAD -- package.json composer.json
  ```

### A04 — Cryptographic Failures

- [ ] Alle nieuwe tokens/secrets via `random_bytes()` — nooit `rand()`, `uniqid()` of `md5()`.
      (`md5()` voor cache-keys/ETags is prima; voor security niet.)
- [ ] Alle secret-vergelijkingen via `hash_equals()`:
  ```bash
  grep -rn --include='*.php' -E '\$(token|secret|key|hash|signature)[A-Za-z]*\s*===' lib/
  ```
- [ ] Secrets staan versleuteld (`ICrypto`) opgeslagen, niet plaintext in appconfig.

### A05 — Injection (incl. XSS)

- [ ] **`v-html` zonder zichtbare sanitizer** — elke hit handmatig nalopen:
  ```bash
  grep -rn --include='*.vue' 'v-html' src/ \
    | grep -viE 'sanitiz|dompurify|escapehtml'
  ```
  Regel: alles wat een *gebruiker* kan beïnvloeden (bestandsinhoud, paginatekst,
  veldwaarden, zoek-snippets) moet door DOMPurify of `escapeHtml()` vóór het in
  `v-html` belandt. Server-side HTML samenstellen en "vertrouwen" telt niet.
- [ ] Geen string-interpolatie in SQL — altijd query-builder met named parameters:
  ```bash
  grep -rn --include='*.php' -E 'executeQuery|executeStatement' lib/ \
    | grep -E '"[^"]*\$|\x27[^\x27]*\$'
  ```
- [ ] Elke `shell_exec`/`proc_open`/`exec` gebruikt `escapeshellarg()` op *elk* argument:
  ```bash
  grep -rn --include='*.php' -E 'shell_exec|proc_open|passthru|\bexec\(|\bsystem\(' lib/
  ```

### A06 — Insecure Design

- [ ] Nieuwe feature met een security-dimensie (sharing, upload, externe API,
      tokens)? Beschrijf in de PR kort wie wát mag en hoe dat afgedwongen wordt.

### A07 — Authentication Failures

- [ ] Endpoints die een wachtwoord/token accepteren hebben
      `#[BruteForceProtection]` en `#[AnonRateLimit]`.
- [ ] Tokens hebben een geldigheidsduur en zijn intrekbaar.

### A08 — Software or Data Integrity Failures

- [ ] Tarball gesigneerd met de juiste key; `.sig` gearchiveerd (zie release-sectie).
- [ ] Build gemaakt vanaf een schone `npm ci` op het release-commit
      (appVersion wordt in de bundle gestempeld).
- [ ] Import-/restore-paden valideren hun input vóór verwerking.

### A09 — Security Logging and Alerting Failures

- [ ] Auth-fouten, permissie-weigeringen en admin-acties worden gelogd via
      `LoggerInterface` (niet `error_log()`).
- [ ] Logs bevatten **geen** wachtwoorden, tokens of volledige persoonsgegevens:
  ```bash
  grep -rn --include='*.php' -iE 'logger->[a-z]+\(.*(password|token|secret|apikey)' lib/
  ```

### A10 — Mishandling of Exceptional Conditions *(nieuw in 2025)*

- [ ] Geen lege `catch {}` die een fout stil opslokt — zeker niet rond een
      permissie- of validatie-check:
  ```bash
  grep -rn --include='*.php' -A2 'catch (' lib/ | grep -B1 '^\s*}' | head -20
  ```
- [ ] Faalt de code *dicht*? Bij een exception in een access-check moet toegang
      geweigerd worden, niet toegestaan.
- [ ] Een mislukte upload/import laat geen half-verwerkte staat achter.

### IntraVox-specifiek

- [ ] **Public shares**: de share-scope-logica in `PublicShareService` /
      `PermissionService` is historisch de meest kwetsbare plek (zie de
      anonieme-folder-share-bug in 1.9.2). Bij *elke* wijziging daar: test als
      anonieme gebruiker op een gedeelde én een niet-gedeelde pagina.
- [ ] **Markdown-rendering**: `markdownToHtml()` in `src/utils/markdownSerializer.js`
      is de centrale sanitizer. Nieuwe `v-html`-sinks moeten daar doorheen —
      niet zelf HTML samenstellen.
- [ ] De `afterSanitizeAttributes`-hook (id-allowlist tegen DOM-clobbering) staat
      er nog en de `ALLOWED_ATTR`-lijst is niet ongemerkt verruimd:
  ```bash
  grep -n 'ALLOWED_ATTR\|afterSanitizeAttributes' src/utils/markdownSerializer.js
  ```
- [ ] **SVG-uploads** gaan door `enshrined/svg-sanitize` — die dependency is nog
      aanwezig en wordt daadwerkelijk aangeroepen op het upload-pad.
- [ ] `composer audit` draaien (IntraVox heeft PHP-dependencies).

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
- **Never commit the manifest ahead of the code that uses the strings.** The guard compares manifest against code *per commit*, not across a series, so an l10n commit that lands before its feature commit fails CI — and reports the new strings as **removed** (manifest has them, code does not yet), which reads as the exact opposite of what happened. `main` goes red until the next commit repairs it.

  Commit the feature first and the manifest second, or put both in one commit. This is only about ordering within a push; the rule above still stands: the strings go to Transifex the same day, not at release time.
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

### Coverage baseline — what "82 languages" actually means

`ls l10n/*.json` counts 82, but the bot writes a file as soon as a single string
is translated, so that number says almost nothing. What matters is how many sit
above the **25% pull threshold**, because below it the bot stops syncing a
language back into the repo at all.

Measure it, rather than trusting the file count:

```bash
python3 - <<'EOF'
import json, glob, os
rows = []
for f in sorted(glob.glob('l10n/*.json')):
    b = os.path.basename(f)
    if b.startswith('.source-') or b == 'en.json':
        continue
    d = json.load(open(f)); t = d.get('translations', d)
    c = sum(1 for k, v in t.items()
            if v and v != k and not (isinstance(v, list) and not any(v)))
    rows.append((b[:-5], c))
total = json.load(open('l10n/.source-strings.json'))['count']   # authoritative
for name, lo, hi in [('>=90%',90,101), ('50-90%',50,90), ('25-50%',25,50), ('<25%',0,25)]:
    n = len([r for r in rows if lo <= 100.0*r[1]/total < hi])
    print('%-8s %3d' % (name, n))
print('above 25%% threshold: %d of %d'
      % (len([r for r in rows if 100.0*r[1]/total >= 25]), len(rows)))
EOF
```

**Baseline at 2.3.0 (2026-08-21), 1371 source strings:**

| coverage | languages |
|---|---|
| ≥90% | 8 — `pt_BR` `ga` `zh_HK` `de_DE` `de` `fr` `es` `nl` |
| 50–90% | 3 — `sv` `el` `et_EE` |
| 25–50% | 5 |
| <25% | 66 |

16 of 82 sit above the threshold. Compare against this at the next release: a
language **dropping** below 25% means the bot will stop shipping it, which is
worth noticing before a user does.

> `ga` (Irish, 1348) and `pt_BR` (1348) are ahead of `nl` — those are community
> translators, not us. Do not "tidy" them.

> **This measures the repo, not Transifex, and the two differ a lot.** At 2.3.0
> the repo suggested `nl` was missing ~97 strings; on Transifex only **5** were
> actually empty. Two reasons: the bot has not synced everything back (notably
> plurals), and many `msgstr == msgid` entries are correct — `Dashboard`,
> `Avatar`, `Apps`, `Code` are identical in Dutch. Use the numbers above to spot
> a language falling under the bot threshold; to decide what still needs
> translating, download the PO and count there
> (`Nextcloud/transifix/tools/po_download.py <lang>`).

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

### After a release — fill empty `nl` (and de/fr) translations (optional, maintainer)

New source strings ship untranslated and **fall back to English at runtime** — a release is never blocked by this. But once the bot has ingested the release's POT (next daily sync), the new strings appear as **empty** on Transifex, and the maintainer can fill the languages they control (for IntraVox: `nl`; `de`/`fr` follow the bot/community — do **not** hand-fill them). Timeline: strings become fillable ~1 day after `git push github main`, not immediately.

> ⚠️ Writing translations is an **outward-facing action on the live shared Nextcloud resource** — treat it like a force-push to a shared `main`. The 2026-07-07 incident (whole-PO upload → English-as-confirmed-translation → removed from the German team) is why the golden rules below exist. **Read [`../Nextcloud/transifix/VEILIGHEID-en-backup.md`](../Nextcloud/transifix/VEILIGHEID-en-backup.md) before any write.**

Safe fill workflow (proven for 1.9.0 — 74 `nl` strings filled, 0 community records touched):

```bash
cd ../Nextcloud/transifix/tools           # tx.py / scan_mine.py / patch_translations.py (RESOURCE=…:r:intravox)
mkdir -p /tmp/l10n_fill
python3 po_download.py nl                  # 1. BACKUP: fresh full PO snapshot (Scenario A)
cp /tmp/l10n_fill/tx_current_nl.po /tmp/l10n_fill/backup_nl_$(date +%Y%m%d_%H%M%S).po
# 2. Build tr_nl.json = {"<source msgid>": "<nl>"} for singulars,
#    {"<source msgid>": {"one":"…","other":"…"}} for plurals. Keep %n / %s / {var} EXACT.
python3 patch_translations.py nl /tmp/l10n_fill/tr_nl.json          # 3. DRY-RUN (no writes)
#    → confirm "SKIP (has translation by <someone-not-you>)" count is ZERO before going live
python3 patch_translations.py nl /tmp/l10n_fill/tr_nl.json --live   # 4. per-string PATCH, unreviewed
rm -rf /tmp/l10n_fill                       # 5. cleanup after verifying stats went up
```

Hard rules (full list in VEILIGHEID-en-backup.md): **delta not bulk** (never re-upload a whole PO), **only empty or your-own-still-English records** (the tool re-fetches and asserts this per string), **never `msgstr == source`**, **leave `reviewed` false**, **respect team removal** (404 on only one language = stay out). The `patch_translations.py` tool already enforces the re-fetch guard; don't bypass it.

Verify + review after filling:
```bash
# coverage jumped (read-only stat):
curl -s -H "Authorization: Bearer $TX_TOKEN" \
  "https://rest.api.transifex.com/resource_language_stats/o:nextcloud:p:nextcloud:r:intravox:l:nl" | python3 -c "import sys,json;a=json.load(sys.stdin)['data']['attributes'];print(a['translated_strings'],'/',a['total_strings'])"
```
Your just-written strings are unreviewed — review them in the editor:
`https://app.transifex.com/nextcloud/nextcloud/translate/#nl/intravox/?q=translator%3Arikd`
(Note the `limit out of range` 400 trap on `resource_translations` GET — use `limit=150`, per the diagnosis table in VEILIGHEID-en-backup.md.)

> **Cross-reference**: [`IntroVox/RELEASE_CHECKLIST.md §2`](../IntroVox/RELEASE_CHECKLIST.md) uses the same Transifex pool — mirror whichever app has the guard first.

---

## 2b. Accessibility (WCAG 2.1 AA)

`docs/user/accessibility.md` claims IntraVox meets ~47 of the 50 WCAG 2.1 AA
criteria. Dutch government bodies are legally bound to WCAG 2.1 AA (Wet
Digitale Overheid), so that claim is a promise to buyers, not marketing —
it has to stay true per release. Run these checks on any release that adds
or changes UI.

- [ ] **New interactive components are reachable and operable by keyboard.**
      Tab reaches them, Enter/Space activates, Escape closes what opens.
      Native `<button>`/`<a>` gives this for free; a clickable `<div>` does not.

- [ ] **Every icon-only control has an `aria-label`**, and toggles carry
      `aria-expanded`.
      ```bash
      grep -rnE '<button[^>]*>\s*<[A-Z][A-Za-z]+ :size' src/ | grep -v aria-label
      ```
      Anything this prints is an icon button without a name.

- [ ] **If you used `role="tab"`, implement the whole pattern.** A half-built
      one is worse than none: the screen reader announces "tab 1 of 2" and then
      the arrow keys do nothing. Required: `role="tablist"` with `aria-label`,
      each `role="tab"` with `aria-controls` pointing at a real
      `role="tabpanel"`, `aria-selected`, roving `tabindex` (0 on the active
      tab, -1 on the rest), and Arrow/Home/End moving both focus and selection.
      The panel tabs in `PageTreeModal.vue` are the reference implementation.

- [ ] **Heading levels do not skip** (h1 → h3). The page structure panel's
      "On this page" tab makes this visible: an indentation jump is a skipped
      level.

- [ ] **Focus stays visible.** Never remove an outline without replacing it;
      `:focus-visible` is the global rule in `main.css`.

- [ ] **Colour is not the only signal.** An active item needs a second cue —
      a bar, an icon, weight — because a 5% colour difference reads as
      "nothing changed" to many users. (2.2.0 shipped exactly that bug: hover
      `#f5f5f5` against active `#e5eff5`.)

- [ ] **Verify in the browser**, not only in the source. Paste into the
      console with a panel open:
      ```js
      // tabs: does every role="tab" control a real tabpanel?
      [...document.querySelectorAll('[role="tab"]')].map(t => ({
        naam: t.textContent.trim(),
        controls: t.getAttribute('aria-controls'),
        doelIsPanel: document.getElementById(t.getAttribute('aria-controls'))
          ?.getAttribute('role') === 'tabpanel',
        tabindex: t.getAttribute('tabindex'),
      }))
      // icon buttons without an accessible name
      [...document.querySelectorAll('button')].filter(b =>
        !b.textContent.trim() && !b.getAttribute('aria-label')).length
      ```

- [ ] **Update `docs/user/accessibility.md` (and `.nl.md`) when the answer
      changes.** The tables name concrete mechanisms per criterion; if a
      release adds a component that satisfies — or breaks — one, the row has
      to follow. Both language versions, in the same commit.

---

## 3. Version Management

- [ ] Determine new version number (semantic versioning: MAJOR.MINOR.PATCH)
- [ ] Run `node scripts/sync-version.js X.Y.Z` to update both `package.json` and `appinfo/info.xml` in one go
- [x] ~~Manually update `openapi.json` version~~ — `sync-version.js` now writes all three files (package.json, appinfo/info.xml, openapi.json) and `--check` fails the prebuild when they disagree. Nothing to do by hand.
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

### 4.1 Do the release's changes actually reach the API — and are they documented?

For **every** feature/fix in this release, confirm both halves, because the two drift apart easily:

1. **Exposed at the API layer** (not just the frontend). A widget config field or page mutation only "works via the API" if the **backend accepts and persists it**. The classic trap: a new widget-config key is added in the Vue editor but the backend `sanitizeWidget`/`sanitizePage` allowlist in `lib/Service/Sanitize/PageShapeSanitizer.php` silently drops it (PageService still has same-named private wrappers that delegate there — the allowlist itself moved) — the setting reverts on reload. Every new config key MUST be added to that allowlist (this is what broke `paginationMode`/`pageSize` in 1.9.0 until added). Verify by round-tripping through the real service, e.g.:
   ```bash
   # Adjust the widget/config to the release's new fields, then confirm they survive sanitizePage:
   ssh rik@<nc-dev> "docker exec -u www-data nc-dev php -r '…sanitizePage(…); // assert the new keys are still present'"
   ```
   Also sanity-check the actual endpoints on nc-dev (rename → `PUT /api/pages/{pageId}/metadata`, pagination → `limit`/`offset`/`pageSize` on `/api/{photo,file}-story/*`) return what the UI expects.
2. **Documented in `openapi.json`.** New endpoints, new query params, and — easy to forget — **new fields in shared schemas** (e.g. `WidgetConfig`, `PageTreeNode`). A new widget-config key is invisible in the API doc until added to the `WidgetConfig` schema, even though the endpoint already accepts it.

- [ ] Update `openapi.json` with any new/changed API **endpoints** (paths + verbs). Cross-check `appinfo/routes.php` — every route a release touched should have a documented path (1.9.0 caught `/api/pages/{pageId}/metadata` GET+PUT missing entirely).
- [ ] Update `openapi.json` **shared schemas** for any new config/response fields (notably `WidgetConfig` for new widget settings, `PageTreeNode`/`Permissions` for tree/permission changes).
- [ ] Update descriptions when a field's meaning changed (e.g. `limit` going from "max rows" to "total cap in both pagination modes").
- [ ] Validate the spec: `npx @redocly/cli lint openapi.json` (JSON syntax alone no longer says much)
- [ ] `npm run lint:openapi` — coverage ratchet: every route documented, no phantom paths, no quality debt
- [ ] `npm run test:contract` — Tier A contract test against a running dev instance. Needs
      `INTRAVOX_CONTRACT_USER` and an **app password** in `INTRAVOX_CONTRACT_TOKEN`; revoke it afterwards.
      This is the only check that compares a real response body against the published schema.

      > ⚠️ **Draai dit niet terwijl iemand in dev aan het werk is.** De run doet duizenden
      > requests en Nextclouds brute-force-teller staat op het NETWERK, niet op de gebruiker —
      > dus iedereen op hetzelfde adres krijgt "Te veel aanvragen" te zien. Het script wist de
      > teller zelf bij het afsluiten (ook bij ctrl-C of een fout), maar tijdens de run kan het
      > alsnog raken. Blijft er iets hangen:
      > ```bash
      > ssh rik@178.63.205.103 "docker exec -u www-data nc-dev php occ security:bruteforce:reset <ip>"
      > ```
- [x] ~~Bump `openapi.json` `"version"` to match~~ — handled by `sync-version.js`; the prebuild gate catches drift.
- [ ] Verify all public share endpoints are documented
- [ ] Update response schemas if changed

---

## 4b. Quality gate — run what CI runs

Since the F0–F7 refactor the repository has an automated gate, and it is the
same one `.github/workflows/ci.yml` runs on every push. **Run all of it locally
before tagging**, not a subset — a guard you have not heard of still fails the
pipeline.

- [ ] Every guard, in one go:
  ```bash
  for s in lint:imports lint:facets lint:eol lint:budgets lint:security lint:routes; do
    printf '%-16s ' "$s"; npm run --silent $s >/dev/null 2>&1 && echo ok || echo FAIL
  done
  ```
  | script | what it refuses |
  |---|---|
  | `lint:imports` | mixed sync/async `.vue` imports (webpack chunk race) |
  | `lint:facets` | facet serialisation that does not round-trip |
  | `lint:eol` | a new CRLF file, or any mixed-ending file |
  | `lint:budgets` | a file that grew; the ratchet only lets sizes fall |
  | `lint:security` | a rate-limit/brute-force marker that cannot fire |
  | `lint:routes` | `docs/route-table.md` out of date vs the controllers |

- [ ] PHP syntax check across every source — CI runs this before anything else,
      and it catches a parse error that unit tests never reach because the file
      is not loaded:
  ```bash
  find lib templates -name '*.php' -print0 | xargs -0 -n1 php -l | grep -v 'No syntax errors'
  ```
- [ ] Self-test the packaging guard. CI builds three deliberately broken packages
      (no vendor, dev vendor, and a good one) and asserts the guard rejects the
      first two. Worth running whenever the guard itself changed:
  ```bash
  # the step is inline in .github/workflows/ci.yml under "Self-test the packaging guard"
  ```
- [ ] PHP unit tests:
  ```bash
  ./vendor/bin/phpunit --testsuite Unit --no-coverage
  ```
- [ ] Integration tests, on a real Nextcloud (they need the NC autoloader):
  ```bash
  ./scripts/run-integration-tests.sh
  ```
- [ ] If `lint:budgets` reports growth, **extract code rather than raising the
      budget**. `npm run lint:budgets -- --update` is for recording genuinely new
      files — run it *after* creating them, then re-run the plain check and
      confirm it exits 0. Recording a baseline before the files exist silently
      leaves them unbudgeted.
- [ ] If `lint:routes` is red, regenerate and review the diff — a changed
      handler name is expected after a controller move; a changed **URL** is not:
      ```bash
      npm run route-table && git diff docs/route-table.md
      ```

> The anonymous attack surface is pinned by `PublicEndpointInventoryTest`. If it
> fails, a `#[PublicPage]` was added or moved — that is a deliberate decision to
> review, never something to make green by editing the expectation.

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
- [ ] Test on target Nextcloud version — hetzner `nc-dev` runs **NC34** (34.0.2.1, checked 26-08-2026).
      Verify with: `ssh rik@178.63.205.103 "docker exec -u www-data nc-dev php occ config:system:get version"`
- [ ] **Bundled lib parity** with NC34: `@nextcloud/vue` ≥ 9.8 (NC34 ships 9.8.x); Vue ≥ 3.5 (NC34 ships 3.5.x). See §1b.
- [ ] **Enterprise detection** (when relevant): since 2.4.1 IntraVox asks `IRegistry::delegateHasValidSubscription()` (public since NC 17) whether the instance has an Enterprise subscription, and reports `Util::hasExtendedSupport` separately as the narrower add-on signal. `hasExtendedSupport` alone answers a different question and falls back to the `extendedSupport` system setting, so an admin could set it by hand — do not reintroduce it as the subscription check. Verify behaviour on a non-Enterprise instance after each NC major bump — the API surface can shift.
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

**Exclude from tarball:** `src/`, `node_modules/`, `screenshots/`, `docs/`, `.git/`, `*.key`, `deploy.sh`, `scripts/`, `.tx/`, `.l10nignore`, `translationfiles/`, `examples/`, `showcases/`, `testdata/`

> ⚠️ **`openapi.json` IS shipped** (`create-release.sh:139`) — this line used to claim the
> opposite. It means the spec lands on every installation, so a wrong or stale spec is
> published rather than kept in-repo. That is why its version is now synced and why the
> coverage ratchet runs in the prebuild.

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

> **POT detail**: the generated `translationfiles/templates/intravox.pot` contains absolute-path source-file references (`#: /Users/rikdekker/Documents/Development/voxcloud-apps/intravox/lib/...`). This is harmless — Transifex only uses msgids, not the comments — and the POT is excluded from the tarball anyway. Don't try to make those paths repo-relative; the NC sync-bot regenerates the POT server-side with its own paths.

---

## 8. Git & Repository

- [ ] All changes committed
- [ ] No uncommitted changes: `git status`
- [ ] Sensitive files not tracked: `git ls-files | grep -iE '\.(key|crt|pem|env)$'`

---

## 9. Release Package

**Volgorde die werkt — twee stappen worden vaak omgedraaid:**

1. Merge eerst de bot-vertalingen van GitHub (§9.1 hieronder), dán pas de tarball
   bouwen. Andersom bouw je een pakket met verouderde vertalingen.
2. Maak de **GitHub release vóór de App Store-upload** (§9.6). De App Store haalt
   de tarball zelf op; bestaat de release nog niet, dan krijgt hij een 404-pagina
   en meldt dat het archief ongeldig is — terwijl er niets mis is met het archief.

### 9.1 Create Tarball

> ⚠️ **STOP** — before running this: (1) is `npm run lint:l10n` green (source strings pushed)? (2) did you merge the bot's latest translations (§2 "At release time — just merge the bot")? A tarball cut before the merge ships the wrong set of languages and must be regenerated. Run `git fetch github && git log --oneline HEAD..github/main` — if it's empty, you're already up to date.

> ⚠️ **Fetch GitHub before you build, not after.** The l10n bot commits
> translations to GitHub only. Building before the merge ships a package whose
> `l10n/` is behind — and nothing catches it: the guards pass, the tests pass,
> the packaging smoketest passes, and the tarball installs fine. It surfaced in
> 2.3.0 only because `git push github main` was rejected, *after* the package
> had been signed and verified. Working order is **fetch → merge → build →
> sign**:
> ```bash
> git fetch github && git log --oneline HEAD..github/main   # empty = go ahead
> git merge github/main --no-edit                           # plain merge, never -X ours
> ```

> ⚠️ **Build the tarball from a tree that matches the tag.** `./deploy.sh` runs
> `scripts/auto-bump-dev.js`, which rewrites `appinfo/info.xml` and
> `package.json` to a dev version (2.3.0 → 2.3.0.1). Deploying to nc-dev to test
> the release and *then* packaging produces a tarball whose `info.xml` disagrees
> with the tag. Check before packaging, and restore if it drifted:
> ```bash
> git diff --stat vX.Y.Z             # must be empty
> git checkout appinfo/info.xml package.json
> ```

**Root folder must be `intravox` (lowercase, no version number)**

> ⚠️ **`vendor/` MUST be in the tarball.** `Application.php` requires
> `vendor/autoload.php` behind a `file_exists()`, so a package without it fails
> **silently**: `enshrined/svg-sanitize` and `lsolesen/pel` are simply absent and
> the first SVG upload is a fatal. Build the vendor tree with `--no-dev` in a
> scratch directory so phpunit/mockery never reach an end-user install.

**Prefer `./create-release.sh`**, which does all of the below and runs the
packaging guard itself. The manual recipe is the fallback:

```bash
TEMP_DIR=$(mktemp -d) && \
mkdir -p "$TEMP_DIR/intravox" "$TEMP_DIR/vendor-build" && \
cp -r appinfo lib l10n templates css img js demo-data "$TEMP_DIR/intravox/" && \
cp CHANGELOG.md LICENSE README.md composer.json composer.lock "$TEMP_DIR/intravox/" && \
cp composer.json composer.lock "$TEMP_DIR/vendor-build/" && \
(cd "$TEMP_DIR/vendor-build" && composer install --no-dev --optimize-autoloader --no-interaction) && \
cp -r "$TEMP_DIR/vendor-build/vendor" "$TEMP_DIR/intravox/" && \
cd "$TEMP_DIR" && \
tar -czf intravox-X.Y.Z.tar.gz intravox && \
mv intravox-X.Y.Z.tar.gz /Users/rikdekker/Documents/Development/voxcloud-apps/intravox/ && \
rm -rf "$TEMP_DIR"
```

### 9.2 Tarball Security Check (CRITICAL!)

**Run the versioned guard first** — it is the same check CI runs, so a green
local run means a green pipeline:

```bash
./scripts/check-package-contents.sh intravox-X.Y.Z.tar.gz
```

It asserts the three things that have actually gone wrong before: `vendor/` and
`vendor/autoload.php` are present, `svg-sanitize` and `pel` are present, dev
dependencies (phpunit/mockery/vendor-bin) are absent, and no key material is
packaged. The manual checks below remain useful when diagnosing a failure:

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
git push origin main --tags        # Forgejo — triggert OOK de docs-pipeline
./push-to-github.sh main           # nooit `git push github` direct
git push github vX.Y.Z             # tag apart: het script pusht geen tags
```

> **Forgejo is niet optioneel.** `.forgejo/workflows/notify-website.yml` triggert
> op een push naar `origin` met wijzigingen in `docs/**` en rebuildt
> voxcloud.nl/docs. Push je alleen naar GitHub, dan blijft de documentatie
> stilletjes op de oude versie staan — geen foutmelding, alleen verouderde docs.
> (Overkomen bij 2.6.2, 02-09-2026.)

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

> ⚠️ **Doe dit VÓÓR de App Store-upload, niet erna.** De App Store haalt de
> tarball zelf op bij de download-URL. Bestaat de release nog niet, dan
> downloadt hij GitHubs 404-pagina — negen bytes tekst — en meldt:
>
> > `intravox-X.Y.Z.tar.gz is not a valid tar.gz archive`
>
> Die melding wijst naar het archief of de signature, en allebei zijn dan in
> orde. Er staat simpelweg niets achter de link. Controleer eerst:
>
> ```bash
> gh release view vX.Y.Z --repo nextcloud/IntraVox --json assets,isDraft
> ```
>
> `state` moet `uploaded` zijn en `isDraft` moet `false` zijn. Een `curl -I` op
> de download-URL is géén betrouwbare check: die kan minuten na de upload nog
> 404 geven door CDN-vertraging terwijl de echte download allang werkt.

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

**Upload via de API met Riks eigen token** (gemeten 29-08-2026: intravox staat op
Riks App Store-account, zijn token authenticeert daar):

```bash
TOKEN=$(tr -d '[:space:]' < ~/Documents/Development/.claude/NextcloudApps/Keys/appstore-api-token-rikdekker.txt)
SIG=$(tr -d '\r\n' < ~/Documents/Development/voxcloud-infra/app-tooling/intravox/intravox-X.Y.Z.sig)
curl -s -w "\nHTTP %{http_code}\n" -X POST https://apps.nextcloud.com/api/v1/apps/releases \
  -H "Authorization: Token $TOKEN" -H "Content-Type: application/json" \
  -d "{\"download\":\"https://github.com/nextcloud/IntraVox/releases/download/vX.Y.Z/intravox-X.Y.Z.tar.gz\",\"signature\":\"$SIG\"}"
```

`201` = gelukt. **`403 You do not have permission` betekent bijna altijd de
verkeerde token**, niet een verlopen token: `appstore-api-token.txt` is van Sam
en bezit alleen `metavox`. De rechtencheck komt vóór de signature-check, dus een
`403` zegt niets over je pakket. Web-UI blijft als terugval beschikbaar.


---

### 9.8 Is de release echt af?

> ⚠️ **Draai dit OOK direct ná elke tag-wijziging.** `git tag -d` + opnieuw
> pushen ontkoppelt de release van zijn tag en zet 'm stil terug op
> **draft**; de download-URL geeft dan 404 met 9 bytes "Not Found" en elke
> Nextcloud-update faalt met "An error occurred during the request."
> Herstel: `gh release edit vX.Y.Z --draft=false` (~1 min CDN-propagatie).
> De download zelf is het enige echte bewijs:
>
> ```bash
> curl -s -o /dev/null -w "%{http_code} %{size_download}\n" -L \
>   https://github.com/nextcloud/IntraVox/releases/download/vX.Y.Z/intravox-X.Y.Z.tar.gz
> # 200 + volledige bytes = goed; 404 + 9 = nog draft
> ```

Een release kan halverwege blijven steken zonder dat iets faalt: 2.4.0 en 2.4.1
zijn gebouwd, getest en gedeployed, maar nooit getagd en nooit gepubliceerd. Dat
viel pas twee versies later op. Deze drie regels sluiten dat af:

```bash
V=X.Y.Z
git rev-list -n1 "v$V" >/dev/null 2>&1 && echo "tag ok"     || echo "GEEN TAG"
gh release view "v$V" --repo nextcloud/IntraVox --json assets \
  -q '.assets[0].state' 2>/dev/null                          || echo "GEEN GITHUB RELEASE"
curl -s -H "Accept: application/json" \
  "https://apps.nextcloud.com/api/v1/platform/32.0.0/apps.json?t=$(date +%s)" \
  | python3 -c "import json,sys;a=[x for x in json.load(sys.stdin) if x['id']=='intravox'][0];print('app store:',a['releases'][0]['version'])"
```

> De cache-buster (`?t=`) is nodig: zonder die parameter geeft de App Store-API
> minutenlang de vorige versie terug, wat leest als een mislukte upload.

---

## 10. Post-Release Verification

- [ ] Install from App Store on clean test server
- [ ] Verify version displayed correctly
- [ ] Test upgrade path from previous version
- [ ] Test demo data import on fresh install
- [ ] Test public share links in incognito browser
- [ ] **~1 day later**: after the bot ingests this release's POT, this release's new strings show as empty on Transifex. Optionally fill `nl` (maintainer's language) — see §2 "After a release — fill empty translations". `de`/`fr` follow the bot; don't hand-fill.

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

# 2b. Quality gate — all of it, not a subset (§4b)
for s in lint:imports lint:facets lint:eol lint:budgets lint:security lint:routes; do
  printf '%-16s ' "$s"; npm run --silent $s >/dev/null 2>&1 && echo ok || echo FAIL
done
./vendor/bin/phpunit --testsuite Unit --no-coverage
./scripts/run-integration-tests.sh

# 3. Commit & tag
git add -A
git commit -s -m "Release vX.Y.Z - [Label]"
git tag -a vX.Y.Z -m "Release vX.Y.Z - [Label]"

# 4. Push to BOTH remotes (github first — it's usually ahead from the bot)
git push github main --tags
git push gitea main --tags

# 5. Tarball — AFTER step 1 merged, never before (see section 9.1)
./create-release.sh X.Y.Z "Label" "Description"   # ships vendor/ and self-tests

# 6. Deploy & test
./deploy.sh  # select 3dev

# 7. Sign & upload (see sections 9.5-9.7)
```

---

## Deferred work — pick up right after 2.0.0

### Folder-skip rule unified across every tree walker — DONE (in 2.0)

**Tracked as [#96](https://github.com/nextcloud/IntraVox/issues/96) — closable.**

Shipped after all: the J3 round of the 2.0 test plan measured the bug live
(search returned the "Knowledge Base" TEMPLATE above the real page; an empty
index put templates in the page list), which moved it from annoying to
release-relevant.

The fix is the one this section asked for: a single shared rule,
`PagePathHelper::isInfrastructureFolder()` — skip `images`/`files` plus any
`_`- or `.`-prefixed name — applied at **20 call sites** across PageService,
SystemFileService, PermissionService, PublicShareService, LicenseService,
ImportService and FeedService (whose private SKIP_FOLDERS const is gone).
A new `_folder` never needs twenty edits again.

Reviewed and deliberately NOT converted: the walkers in ExportService,
DemoDataService and OrphanedDataService. Those are copy/cleanup decisions,
not "is this a page?" decisions — export and demo-install must WALK
`_templates`, cleanup has its own scope. Converting them would have silently
dropped templates from exports.

The unit net this section asked for exists: `PageWalkerSkipTest` pins the
helper's contract and the search walker (the one that shipped the bug), with
`_templates` and `_resources` in the fixture.

`.nomedia` was checked as instructed: the explicit test in the page-locator
was a folder-skip (now covered by the `.`-prefix rule); the marker-file
checks use `nodeExists` on files and are untouched.

*(Process note: the first four-site version of this fix was accidentally
`git stash`ed by a second session sharing this worktree and sat in
`stash@{0}` while the checklist said "deferred". Two sessions, one checkout:
don't run git state operations while the other is mid-edit.)*

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

*Last updated: 2026-08-21 (2.3.0) — §4b added: the quality gate now has six prebuild guards plus 752 unit and 23 integration tests, and none of it was mentioned here; run all of it, not the guards you happen to know. §9.1 gained two ordering warnings that both cost a rebuild during 2.3.0: (a) fetch GitHub and merge the l10n bot BEFORE building — building first ships stale `l10n/` and nothing catches it, the guards, tests, packaging smoketest and install check all pass, it surfaced only when `git push github main` was rejected after the package was already signed; (b) `./deploy.sh` runs `auto-bump-dev.js`, so deploying to nc-dev to test the release and then packaging yields a tarball whose `info.xml` disagrees with the tag. §9.1 also now ships `vendor/` (it did not, which fails silently — the first SVG upload is a fatal) and §9.2 runs `scripts/check-package-contents.sh`, the same check CI runs. §4.1 pointed at `PageService.php` for the `sanitizeWidget` allowlist; the implementation moved to `Sanitize/PageShapeSanitizer.php`. §2 gained a coverage baseline: 82 language files but only 16 above the 25% bot threshold — measure it, do not read the file count. Earlier — 2026-08-13 — added "Deferred work — pick up right after 2.0.0": the folder-skip rule is hand-copied across ten tree walkers with differing lists, so `_templates` gets served as a real page in search and (with an empty index) the page list. Four `PageService.php` walkers are fixed in `stash@{0}`; six sites remain, listed with file:line. Held back from 2.0 because it is half-done, touches the release's most heavily changed file, and has no test. Finish it with a shared `isSkippableFolder()` helper plus a fixture test. Earlier — 2026-07-07 (later same day) — §2: added "Transifex account & team access" — uploading translations (not just reading) needs language-team membership (Translator role) in the Nextcloud org + a write-capable token; a read-only token 403s on upload. Applies to every VoxCloud app on the shared `o:nextcloud:p:nextcloud` org. Documented the safe fill-only PO upload (proven on IntraVox de/fr in #63: de 45→94%, fr 323→94%, `translations_updated: 0` = no community work overwritten) and the intravox-vs-introvox resource-slug trap. Earlier same day — §2 REWRITTEN around a durable source-string guard. Root cause of the recurring release breakage: new feature strings were added in code but `npm run pot` + committing the POT was skipped, so the Nextcloud bot never saw them → translators couldn't translate them → every bot sync deleted them from `l10n/<lang>.{js,json}`. The old `-X ours` merge + hand re-adding strings was a workaround that also desynced `.js` from `.json`. Fix: a committed manifest `l10n/.source-strings.json` (sha256 + sorted msgid list, written by `extract-en-json.js`) and a prebuild guard `scripts/check-l10n-sync.js` (wired into `prebuild`, standalone as `npm run lint:l10n`) that FAILS the build whenever code's string set diverges from the manifest — making "push strings to Transifex" (`npm run l10n:push`, decoupled from release) unmissable. Releases are now a plain `git merge github/main` (no `-X ours`, no re-add) with a green-guard assertion. The bare `npm run l10n` footgun (lossy json→js regen) is renamed to `l10n:generate-js`. A check-only `.github/workflows/l10n.yml` runs the guard on push as a backstop (never auto-commits). de_DE/fr/nl policy unchanged (follow the bot). §5/§7/§9 cross-refs updated; `.source-strings.json` added to the tarball scrub. Earlier history condensed: v1.7.0 dropped the stale per-lang targets after the #63 resource re-provision; 2026-06-18 reverted POT generation to the en.json+lib/xgettext extractor (bare xgettext drops ~700 Vue strings); `l10n/en.json`/`en.js` are gitignored artefacts; `npm run release` is Gitea-only, not the App Store flow.*
