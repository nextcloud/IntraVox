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
- `node_modules/`, `vendor/`: app dependencies.
- `generated-artifacts/`: optional local build output and check reports.

Runtime/data and generated reports are excluded from Git and deployment uploads.
They are not a backup and do not transfer through a code merge; each workspace
provisions its own local instance. Back up private data separately before deleting
or replacing `.runtime`. Do not run these scripts against a production instance.

## Develop and check

The imported app already includes release bundles in `js/`; local setup leaves
those tracked files untouched. PHP/source changes are used directly through
source symlinks. Rebuild the frontend for the running local app with:

```bash
npm run build -- --output-path "$PWD/.runtime/nextcloud/custom_apps/intravox/js"
```

For repeated frontend edits, use `npm run watch -- --output-path` with that same
absolute path. Plain `npm run build` still updates the repository's original
`js/` release bundles, for the original release workflow.

To verify a production build without changing tracked release bundles:

```bash
npm run build -- --output-path "$PWD/generated-artifacts/intravox-js"
```

Checks performed during setup:

```bash
SKIP_INTEGRATION=1 bash scripts/ci-local.sh
bash scripts/replit/check.sh
NEXTCLOUD_ROOT="$PWD/.runtime/nextcloud" \
  php -d memory_limit=512M vendor/bin/phpunit -c phpunit-integration.xml
```

- Existing PHP syntax, unit tests, PHPStan, and all frontend guards **passed**.
- Production webpack build **passed**, with existing bundle-size warnings.
- Nextcloud login page was visually checked. HTTP checks confirm that private
  page APIs require sign-in and private files, source, and traversal paths return
  404. IntraVox's public HTML shell remains available for its share-link feature.
- The signed-in UI has **not** been visually verified; the password is left
  for the owner to set privately.
- The real Nextcloud integration suite ran: **36 tests, 104 assertions,
  2 failures, 3 skips**. One failure expects `User not logged in` while the app
  returns `You are not logged in.`; another assumes existing page fixtures in
  the otherwise empty local intranet. These are reported, not hidden or changed
  as part of runtime setup. The original SSH/container test runner is not needed
  for the local command above.

For manual development background-job processing:

```bash
php -d memory_limit=512M -f .runtime/nextcloud/cron.php
```

No production cron service, Redis cache, mail server, external storage, or public
deployment has been configured.
