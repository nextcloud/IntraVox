# IntraVox on Replit

This is the original PHP/Vue Nextcloud app, not a standalone Vue website.
Keep the repository layout and stack intact.

## Local development runtime

- PHP 8.2 with the existing extensions; Node 24 for current frontend dependencies.
  Some older Nextcloud frontend packages declare Node 20 and emit engine warnings
  on Node 24; their newer transitive dependencies require 24. Installation,
  frontend checks, and the build were verified on 24 without changing app APIs.
- The imported dependency graph is preserved, except DOMPurify was updated from
  3.4.13 to 3.4.18 because the former download is blocked by the package security
  policy. The lockfile uses public npm registry URLs, not workspace-only URLs.
- Nextcloud **32.0.15**, downloaded from the official server and checked against
  its SHA-256 checksum.
- **Team folders** (`groupfolders`) is installed by Nextcloud's app installer,
  which selects a compatible release and verifies its signature. The verified
  instance used **20.1.19**.
- Nextcloud and IntraVox are enabled. IntraVox's own setup creates its Team folder,
  access groups, empty homepage, and templates. No demo content is imported.
- SQLite is used for this single-instance **development environment only**.
  This is not a production hosting configuration.

## Start

Press **Run**, or start the **Run IntraVox** workflow. It runs:

```bash
bash scripts/replit/start.sh
```

The PHP development server listens on `0.0.0.0:5000` with four workers and a
512 MB memory limit. It serves only `.runtime/nextcloud`, never the repository
root. The custom router applies private-file protections because the PHP
development server does not process Nextcloud's `.htaccess`.

Open Preview **in a new tab** if the embedded frame is blocked: Nextcloud's
normal anti-framing headers are deliberately preserved. The development domain
is trusted explicitly, HTTPS URLs are used behind Replit's proxy, and other
hosts are not trusted automatically.

## First login — required once

Setup is completed privately by the CLI **before** the web server starts.
There is no open web installer and no shared/default password. The `admin`
account initially receives a cryptographically random password that is neither
printed nor saved separately.

In the Replit **Shell**, choose your own password using the hidden prompt:

```bash
php -d memory_limit=512M .runtime/nextcloud/occ user:resetpassword admin
```

Then open Preview in a new tab and log in as **admin** with that password.
IntraVox is the default app, and the initial administrator belongs to
**IntraVox Admins**.

For the embedded Preview, the development login route offers **Use existing
sign-in in Preview**. This requests browser-mediated storage access rather than
copying authentication cookies. If access is granted, it opens a fresh
Nextcloud page using the existing session. **Sign in inside Preview** instead
opens the normal Nextcloud login without requesting cross-context access.
The helper can also be opened at `/intravox-preview-session`.

Firefox may require an explicit cookie exception for the development app
hostname under **Settings → Privacy & Security → Cookies and Site Data →
Manage Exceptions**. An exception on a standalone app tab is distinct from one
for the outer Replit page. A browser `NotAllowedError` can also result from the
embedding frame's policy; app code cannot override that policy. Do not copy
cookies, disable CSRF checks, or claim that a permission request guarantees
authenticated Preview access.

To add sample pages later, use **Administration settings → IntraVox** to install
a demo language, or run the app's existing command:

```bash
php -d memory_limit=512M .runtime/nextcloud/occ intravox:setup --language=en
```

Do not put passwords in chat, source files, shell arguments, or commits.
Password resets require trusted Shell access. Re-running setup preserves
existing passwords, accounts, and content.

## Re-create dependencies and runtime

The post-merge setup automatically runs `scripts/replit/post-merge.sh` to
install locked app dependencies and provision the ignored runtime when absent.
For a fresh import or manual setup:

```bash
composer install --no-interaction --prefer-dist
npm ci --no-audit --no-fund
bash scripts/replit/setup.sh
```

Setup stops on failed downloads, checksum checks, or app installation.
Rerunning it is safe; it does not reset an installed Nextcloud or re-provision
IntraVox permissions once the app's provisioning marker exists.

Local-only files:

- `.runtime/nextcloud/`: downloaded Nextcloud, installed Team folders, private
  Nextcloud configuration, and a managed `custom_apps/intravox` directory.
  Its app source directories link to this repository; its `js/` contains fresh,
  ignored frontend bundles built from the locked dependencies during setup.
- `.runtime/data/`: SQLite database and user/Team folder files, outside the
  web document root.
- `node_modules/`, `vendor/`: development dependencies. The managed Nextcloud
  app has a separate production-only Composer install; never link its vendor
  directory to the development tree (OCP test dependencies conflict with DAV).
- `generated-artifacts/`: optional local build output and check reports.

Runtime/data and generated reports are excluded from Git and deployment uploads.
They are not a backup and do not transfer through a code merge; each workspace
provisions its own local instance. Back up private data separately before deleting
or replacing `.runtime`. Do not run these scripts against a production instance.

## Develop and check

The imported app already includes release bundles in `js/`; local setup leaves
those tracked files untouched. PHP/source changes are used directly through
source symlinks. Rebuild the frontend and refresh the running local app with:

```bash
npm run build
```

Production JavaScript and the ordered entrypoint manifest are emitted to `js/`;
the Sass theme is extracted to `css/intravox-main.css`. PHP loads the manifest
for both the reader and administration entrypoints, so chunk names must not be
hardcoded in templates. Do not override only webpack's output path: the
extracted CSS uses the sibling `css/` directory.
Successful builds automatically refresh the marked, managed local app's
JavaScript directory and publish its manifest last. Unmanaged app directories
are never updated. PHP resolves assets through Nextcloud's installed app path,
not the physical location of symlinked source code.

The root Jenkinsfile first validates Sovereign documentation and runs the
documentation guard's regression tests, builds with `npm ci && npm run build`, then runs
`bash scripts/stage-release.sh`. It stages an **unsigned** app and archive under
the ignored `build/intravox` directory, archives under `build/`, installs only
production Composer dependencies, and checks excluded paths. Trusted external signing credentials
are required for a later `occ integrity:sign-app`; none are stored here.

Checks performed during setup:

```bash
bash scripts/ci-local.sh
bash scripts/replit/check.sh
bash scripts/run-integration-tests.sh
```

The full gate requires the local runtime and dependencies described above.
Integration creates a unique `.runtime/integration-*` install with its own
SQLite database, random-password administrator, Team folders and explicit page,
template and resource fixtures. It copies server **code only**, never owner
configuration or data. The sandbox is removed on success, failure or handled
interruption. A per-run marker and database-path guard reject direct runs against
the owner's instance before Nextcloud boots. There is no SSH/deploy step.
The installed app's production vendor is copied separately; development PHPUnit
is loaded behind Nextcloud's host autoloaders and never overlaid on the app.
The three third-party, live-network feed measurements are benchmarks, not release
checks, and are excluded from the gate. Opt in explicitly with:
`INTRAVOX_BENCH_FEED=1 bash scripts/run-integration-tests.sh tests/Integration/FeedLimitBenchmarkTest.php`.
`phpunit.xml` defaults to Unit only; integration has its own strict configuration
where skipped tests fail. `composer test:integration` runs the disposable runner.
For a deliberately partial unit/static check, use `SKIP_INTEGRATION=1`; a missing
runtime without that explicit opt-out fails rather than reporting a green gate.

## Documentation and downstream handoff contract

Release quality is measured against the standards of excellence in
`docs/engineering/release-quality.md`. Keep `README.md` aligned with code in the
same change: update the change/release record, build instructions, Jenkins and
Sonar gate coverage, and end-to-end downstream playbooks when their behaviour
changes. Changed UX requires current, approved documentation screenshots with
provenance, not the original IntraVox demo imagery.

Replit is the development upstream and the Sovereign developers are downstream
for this handoff. Confirm requirements, verify implementation and evidence, and
validate representative authenticated user paths. Do not claim green gates,
certification, environment coverage or downstream receipt without evidence.
The README links the code map, playbooks and release record. Run
`node scripts/check-sovereign-docs.cjs` and
`node --test scripts/check-sovereign-docs.test.cjs`; both also run in the Jenkins
pipeline and GitHub CI frontend job when those jobs are triggered.

The final-polish candidate keeps app version 3.1.2, uses compact entity badges
with amber ExCom borders/tints, corrects the hero icon wrapper and narrow-screen
grid sizing, keeps the native top bar at #4B164C, and adds 20px footer padding.
`scripts/replit/sovereign-gm-checks.cjs` extends the authenticated suite with
desktop/mobile light/dark geometry checks, including bounds inside the hero
itself (document-level overflow checks alone miss clipped inner grids).
The local primary colour is persisted through the supported theming command;
no production theming was changed. Original approved 52-check evidence is kept
under ignored `generated-artifacts/sovereign-qa-approved/`; fresh candidate
evidence remains in `generated-artifacts/sovereign-qa/`.

## Sovereign baseline provisioning

Gold Master CMS delivery is documented in
`docs/engineering/sovereign-gold-master.md`. The tracked
`demo-data/sovereign-365/en/` bundle is authoritative for its managed paths.
`occ intravox:sync-sovereign-portal --no-interaction` uses native Files APIs,
private appdata snapshots and a live-byte/hash no-op guard. Native app
install/update calls the same service after setup; same-version file replacement
must invoke OCC from the downstream deployment job. Never use raw filesystem
copies into Nextcloud data or a manual `files:scan` as the deployment mechanism.

Fresh English setup uses the Sovereign homepage, office/C3 destinations, tagged
sample announcements, navigation and compliance-baseline footer. It preserves
the native page editor and uses Nextcloud's theme selection, not a forced dark
theme. Announcements are clearly prototype samples, not audit or certification
claims. The official white PNG and original source URLs are retained under
`src/img/athena-brand`; no original corporate SVG was verified.

To replace the managed English baseline on an existing **local** install, first
snapshot its current English Team Folder and theming settings privately under
`.runtime/backups/athena-baseline-<timestamp>/`. Then explicitly run:

```bash
php -d memory_limit=512M scripts/replit/seed-sovereign.php --apply
php -d memory_limit=512M .runtime/nextcloud/occ groupfolders:scan 1 --path en
```

This deliberately replaces the managed Sovereign documents, not unrelated
content. It retains the existing homepage identity/bookmarks, refreshes scoped
caches, and configures local branding through Nextcloud's theming APIs. It is
not run automatically on startup. Keep a fresh backup before every replacement.
The executive subfolder has real Team Folder ACLs: ordinary portal groups are
denied; global admins, ExCom members and existing approved executive identities
are allowed. An empty ExCom group is created when missing, without enrolling
any existing user or changing root grants.

`node scripts/replit/sovereign-browser-check.cjs` runs native Chromium checks with
temporary admin/reader/ExCom accounts and real sign-in forms. Credentials remain
in memory; users/profiles are removed in `finally`. Reports and screenshots stay
in ignored `generated-artifacts/sovereign-qa/`. The settings round-trip creates
and deletes only its own temporary page. It never reuses the owner's cookies.

- Existing PHP syntax, unit tests, PHPStan, and all frontend guards **passed**.
- The Sovereign production build completed with **zero warnings**, retaining
  webpack's default size guards and all locale chunks.
- Nextcloud login page was visually checked. HTTP checks confirm that private
  page APIs require sign-in and private files, source, and traversal paths return
  404. IntraVox's public HTML shell remains available for its share-link feature.
- Real authenticated Chromium checks **passed for admin, reader and ExCom**
  accounts: compact desktop/Preview layouts, mobile overflow, entity-filtered
  news, native light/dark/system themes, C3 destinations, interactive structure,
  Global Search and Ctrl+K, native editor/Cancel, settings Save/reload and
  temporary-page create/edit/delete. Native ACLs returned private content only
  to eligible accounts. No owner password or session was used.
- Scoped English Team Folder scan and HTTP runtime/security smoke checks passed.
  The unsigned `build/intravox` folder and release archive passed content checks,
  including production dependencies and absence of development tooling, source
  maps, private runtime files and symlinks. Package-guard regression tests also
  verify large archive listings and rejection of forbidden early entries.
- Brand images are verified original corporate **PNGs**, not native SVGs.
  The context bar uses the SOVEREIGN 365 label without duplicating the native logo.
  Missing or unauthorized local apps stay disabled, with truthful tooltips rather
  than visible availability pills or navigation-title suffixes. Browser-mounted
  apps supplement unknown availability but never override explicit server denial.
  Tables falls back to Forms when authorized.
- The final home has eight app cards and six standard/eight executive office
  cards, consistent one-line subtitles, DST-aware Nassau/Athens clocks, and native
  Unified Search from the hero, launchpad and keyboard shortcut. Existing home
  UUID/bookmarks remain intact.
- `php scripts/replit/seed-sovereign.php --apply --final-ux` refreshes only the
  home and three managed announcements. Back up those four files privately first.
  It leaves navigation, office pages, access rules and native branding unchanged.
  A repeated refresh changed zero documents.
- Final v1.0 UX verification passed **52 authenticated browser checks** across
  admin, reader and ExCom accounts. This includes eight app cards, six/eight
  office cards, whole-card C3 links, native search entry points, navigation
  tooltips, and readable dropdown/editor/settings controls in both themes.
  All temporary QA accounts were removed. The six top/middle/bottom screenshots
  are collected in `generated-artifacts/sovereign-qa/portal-v1-ux-review.html`.
- The historical integration failures were repaired using disposable fixtures,
  the current authentication message and an explicit fixture writer. The suite
  is never run against the populated owner dataset; authenticated runtime
  browser checks remain a separate check with temporary role accounts.
  The repaired gate passed PHP syntax/unit/static analysis and all frontend
  guards, including real integration tests. The isolated suite also passed in
  randomized order; host OCP/Sabre and production-only app dependency checks
  are included. All 52 authenticated admin/reader/ExCom browser checks and the
  runtime/security smoke checks passed again.

For manual development background-job processing:

```bash
php -d memory_limit=512M -f .runtime/nextcloud/cron.php
```

No production cron service, Redis cache, mail server, external storage, or public
deployment has been configured.
