# Replit upstream → Sovereign downstream playbook

[README](../../README.md) · [Quality gates](release-quality.md) · [Portal v1.0](../releases/portal-v1.0.md)

## 1. Establish the source and authority

Replit development hands off to
`https://github.com/scott-mackenzie/IntraVox-AGL`, branch `dev`.
The owner approved tag `version-1.0.0`; do not create it until release gates pass.
Portal v1.0 retains Nextcloud app version **3.1.2**.

The owner describes an existing Gitea-to-GitHub mirror with a one-minute check.
This guide does not change its direction or schedule. There is no verified
GitHub-to-Gitea delivery contract. Downstream must identify the received
repository/ref and compare SHAs; elapsed time is not evidence of receipt.

In a separate, authorized downstream development checkout:

```bash
git clone --branch dev https://github.com/scott-mackenzie/IntraVox-AGL.git
cd IntraVox-AGL
git status --short
git rev-parse HEAD
git remote -v
git ls-remote origin refs/heads/dev refs/tags/version-1.0.0
```

Record the exact commit, tool versions and intended destination before building.
An absent release tag is expected while the release is blocked. Use secure
credential management; never embed tokens in remotes, scripts, screenshots or logs.
Do not force-push or overwrite existing tags.

## 2. Prepare a reproducible development build

Read [requirements](../../README.md#requirements-and-compatibility).
Jenkins currently declares no pinned build image; the local inspected toolchain
is Node 24.22.1, npm 11.19.1, PHP 8.2.35 and Composer 2.10.3.
Record the actual successful build agent toolchain; do not infer compatibility
from a dependency-free frontend check on Node 20.

Run each command only if the preceding command succeeds:

```bash
node --version
npm --version
php --version
composer --version
composer install --prefer-dist --no-interaction
npm ci
node scripts/check-sovereign-docs.cjs
node --test scripts/check-sovereign-docs.test.cjs
SKIP_INTEGRATION=1 bash scripts/ci-local.sh
npm run build
```

The original hero file-budget blocker was addressed by recording its first
baseline, without increasing any previously committed budget. Do not bypass
this guard or automatically accept arbitrary budget increases.

Do not use `npm install` to refresh the dependency graph for a normal build.
Do not edit versions to make a Portal marketing label match the Nextcloud app.
Review build-generated changes before including tracked bundles in a handoff.

## 3. Prepare an isolated local runtime

The [managed setup script](../../scripts/replit/setup.sh) provisions Nextcloud
32.0.15 under ignored `.runtime/`, not a production instance. It installs a
separate production-only app vendor tree and builds assets. It also performs
setup actions; inspect it before first use.

```bash
# Development only, after dependencies and prebuild gates are green:
bash scripts/replit/setup.sh
bash scripts/replit/start.sh
```

The start command is long-running. Use a separate terminal for checks. On Replit,
use the existing `Run IntraVox` workflow rather than starting a duplicate server.
Do not point these scripts at a real downstream environment or production data.

**Content refresh is not routine startup.** The local seeder's `--apply` mode
can replace managed English baseline documents. Inspect its scope and take a
private backup of affected Team Folder files and theming settings before any
approved refresh. `--apply --final-ux` is a narrower, still-mutating refresh of
the home and three managed announcements. Neither command is an upgrade or
production migration procedure.

## 4. Execute verification without deploying

Read-only local HTTP smoke checks:

```bash
bash scripts/replit/check.sh
```

Outside Replit, pass the authorized local test URL as the first argument. The
script's readiness check currently expects Nextcloud 32; passing it does not
qualify every major declared in app metadata.

Local real-runtime integration attempt:

```bash
mkdir -p generated-artifacts/release
NEXTCLOUD_ROOT="$PWD/.runtime/nextcloud" \
  php -d memory_limit=512M vendor/bin/phpunit -c phpunit-integration.xml \
  --log-junit generated-artifacts/release/integration.xml
```

**Known blocker:** this command currently exits before running tests with a
duplicate Composer autoloader class error. Fix runtime/test isolation through
the release-check reliability work. Do not overlay the development vendor tree
onto the running Nextcloud app or claim skipped tests passed.

The existing [remote runner](../../scripts/run-integration-tests.sh) has a
default deployment path. **Do not invoke it without understanding that path.**
Even `--no-deploy` copies tests and can overlay development dependencies into a
remote container; it is not read-only and is not an approved alternative in
this handoff. No remote container, production server, or deployment is needed here.

Authenticated browser checks, against the local managed runtime only:

```bash
node scripts/replit/sovereign-browser-check.cjs
```

This runner requires its Chromium/CDP tooling and the local runtime, uses native
login with temporary admin/reader/ExCom accounts, and removes its temporary users
and profiles in `finally`. It creates and deletes a temporary test page and
exercises settings. It is not read-only. Credentials stay in memory; never use
the owner's account or reuse owner cookies.

It writes ignored `generated-artifacts/sovereign-qa/verification.json` and
screenshots. Check `passed`, result count, role coverage, errors and cleanup.
The historical approved 52-check result is preserved privately under
`generated-artifacts/sovereign-qa-approved/`; it is not evidence that a new
revision passed. The expanded final-polish suite adds desktop/mobile light/dark
geometry, header-colour and footer-clearance checks.
Before rerunning, preserve the approved evidence privately: the runner overwrites
filenames. The UX HTML review is a separate handoff document, not automatically
regenerated by the browser command. After building the current unsigned package,
generate the self-contained candidate review explicitly:

```bash
node scripts/replit/build-portal-review.cjs
```

It reads the existing passing browser JSON/captures and package, reports the
current source SHA and dirty/clean status, and writes the ignored
`generated-artifacts/sovereign-qa/portal-v1-gm-review.html`. It does not run
tests, sign, deploy or claim final approval. Preserve the corresponding logs.

## 5. Build and inspect the unsigned package

Only after the relevant checks are green:

```bash
bash scripts/check-package-contents.test.sh
bash scripts/stage-release.sh
bash scripts/check-package-contents.sh build/intravox-3.1.2.tar.gz
sha256sum build/intravox-3.1.2.tar.gz
tar -tzf build/intravox-3.1.2.tar.gz
```

Use a clean development checkout for the release candidate and preserve the
source SHA before and after the build. The staging script removes only the
disposable `build/intravox` directory, copies an explicit app-content allowlist,
installs locked production dependencies there, removes source maps, rejects
symlinks, and validates the archive. It does **not** sign or deploy.

The tarball must have a single plain `intravox/` root, correct metadata, required
production dependencies, compiled theme and asset manifest; it must not include
dev tools, QA output, private runtime files, keys or certificates.
Do not package the repository's development `vendor/` directory.

## 6. Run Jenkins and obtain Sonar evidence

Follow the [gate matrix](release-quality.md) rather than assuming a green Jenkins
packaging job proves integration or Sonar approval. Record the job/run URL,
source SHA, toolchain, gate results and archive SHA-256. Jenkins archives
`build/*.tar.gz`; ensure the workspace does not contribute stale unrelated archives.

Sonar is **not configured in this repository**. A trusted downstream job may
exist outside it, but no verified Portal project key, endpoint, quality profile
or result was supplied. The downstream owner must provide and verify them.
Do not reuse another Athena project's Sonar results or invent a passing badge.

## 7. Transfer for downstream testing, then reconcile release readiness

The owner approved the development push to GitHub `dev` for downstream testing.
This transfer must precede downstream Jenkins/Sonar validation. Do not require
results from an environment that has not yet received the source.

Before the authorized non-force **development push**, verify:

- Record the candidate source SHA, UX approval and known local gate failures.
- README, code map, release notes, build/gate instructions and screenshots match
  the actual change. Run the documentation validator.
- Destination is GitHub `dev`; do not create a release tag during this testing
  handoff. Preserve app version 3.1.2.
- Raw evidence and build outputs are not staged. Check both `git status --short`
  and the staged diff; never use a blanket staging command for release handoff.

After transfer, compare the GitHub remote branch SHA to the candidate SHA.
Downstream independently records the received commit and runs Jenkins, Sonar
and CI/CD builds in its development/test environment. A mirror's polling interval
is not proof of receipt, and a push is not approval of downstream deployment.
Return failures against that exact SHA and iterate through `dev` commits.

Only after required gates pass, reconcile the final reviewed source SHA,
package checksum and approval record, check that no conflicting remote tag
exists, and perform a separately authorized `version-1.0.0` tag/release operation.
Downstream then checks the tag resolves to the intended commit.

Send these private attachments separately, not as commits:

1. Exact unsigned archive and checksum.
2. Build/unit/static/integration logs and Jenkins/Sonar evidence (including failures).
3. `generated-artifacts/sovereign-qa/verification.json` and
   the current `generated-artifacts/sovereign-qa/portal-v1-gm-review.html`,
   plus the original approved 52-check JSON and UX HTML preserved under
   `generated-artifacts/sovereign-qa-approved/`.
4. Toolchain, source SHA, approval record and unresolved limitations.

## 8. Downstream installation and rollback boundary

Deployment is outside this handoff. Downstream operators must separately approve
their environment-specific installation, maintenance window, signing policy and
rollback procedure. Before any later rollout they need:

- A tested backup/restore plan covering the database, data/Team Folders, app
  files, configuration and any theming or provisioning changes.
- A known-good prior app/package and compatibility evidence. Do not assume
  dropping old app files over a newer schema is a safe rollback.
- Staging validation of login, content, role ACLs, native apps, editing, search,
  asset loading and background jobs, plus explicit success/abort criteria.
- An accountable operator and a rehearsed recovery on a non-production clone.

This guide intentionally provides no generic production deploy command.
