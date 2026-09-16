# Testing

IntraVox has two test suites with deliberately opposite setups.

| | Unit | Integration | Smoke |
|---|---|---|---|
| Runs | anywhere (`vendor/bin/phpunit`) | in CI, or against a dev container | against any deployed instance |
| Nextcloud | stubbed (`tests/Stubs/OCP.php`) | the real thing | the real thing |
| Groupfolders | not involved | real, version-pinned by the server | real |
| Config | `phpunit.xml` | `phpunit-integration.xml` | — |
| Speed | ~4s | ~7s (plus setup) | ~10s |
| Count | 1321 tests | 33 tests | 12 checks |

A fourth kind exists but is not automated: the **manual test plan** for a
release, kept internally because it carries deploy targets and rollback
commands. The smoke script is its automatable half.

## Where these run

```bash
npm run ci        # the whole local gate: 12 checks, ~40s
```

That is the only command that claims to be complete. It runs the unit suite,
phpstan, the frontend guards, and the integration suite when a dev server is
reachable — and when it is not, it says so and withholds the "safe to push"
verdict rather than printing green over an unproven build.

On the server, two workflows:

| Workflow | Trigger | What |
|---|---|---|
| `.forgejo/workflows/ci.yml` | every branch | unit, phpstan, packaging, frontend guards — PHP 8.2 |
| `.forgejo/workflows/integration.yml` | PR, main lines, dispatch | the integration suite on NC 32–35 |

`integration.yml` installs a throwaway Nextcloud plus groupfolders per matrix
entry and runs `occ intravox:setup` — the app's own provisioning, so a break in
that path fails CI rather than someone's first install. PHP is paired per NC
version, read from each branch's `lib/versioncheck.php`: 32 wants 8.1+, 33 and
34 want 8.2+, and **35 requires 8.3**. Worth knowing when reading `info.xml`,
which claims `max-version="35"` alongside PHP 8.2 — Nextcloud sets that floor,
not us, but the pairing reads as if both hold.

The GitHub mirror runs the same fast gate on `main` only. It never receives our
working branches, so widening it would re-run what Forgejo already ran.

## Smoke test

```bash
export INTRAVOX_DEV_SSH=user@your-dev-host
scripts/smoke-test-dev.sh --expect 3.0.0
```

Everything provable about a deployed instance without opening a browser: folder
resolution (the empty-app bug), the CLI path, the HTTP surface, the log, and
the sabre/xml vendor trap that `run-integration-tests.sh` leaves behind. About
ten seconds. It either hands you a green baseline to start clicking from, or it
tells you not to bother yet.

No host is hard-coded in any of these scripts. They are public on
github.com/nextcloud/IntraVox, and a maintainer's account name and container
layout are not something to publish for the convenience of never typing them.

## Unit suite

```bash
vendor/bin/phpunit --testsuite Unit      # or: composer test:unit
```

Runs against OCP stubs, so it never needs a server. This is the suite that
gates a release and the one to run while developing.

Many tests build a `PageService` **without its constructor** and wire only the
collaborators the path under test needs, via a reflective auto-fill loop. Two
things to know when adding a service dependency:

- If a collaborator is `final`, it cannot be mocked. `doubleOrBuild()` builds a
  real one instead, recursing for its own final dependencies.
- If a collaborator can be safely synthesised, give `PageService` a lazy
  accessor (`locator()`, `news()`, …) and add it to `$lazySeamServices` so the
  auto-fill loop leaves it unset. `PageShapeSanitizer` is the exception that
  cannot: two of its widget rules read admin config, so a synthesised instance
  would apply different security rules than the injected one. Tests that reach a
  sanitizing path wire it explicitly.

## Integration suite

```bash
scripts/run-integration-tests.sh                # deploy, then run
scripts/run-integration-tests.sh --no-deploy    # run what is already deployed
scripts/run-integration-tests.sh --filter Foo   # pass through to phpunit
```

The script deploys the working tree to nc-dev, copies `tests/` and
`phpunit-integration.xml` in separately (`deploy.sh` ships production files
only), and runs the suite as `www-data` inside the container.

### Why it exists

IntraVox resolves the folder its content lives in through several layers — a
member's mounted view, falling back to a raw `__groupfolders` walk, with a mount
point *name* match in between. Every one of those layers is there because of a
real production breakage, and none of them is reachable from a unit test,
because unit tests stub the filesystem away.

That means the code deciding *where the intranet lives* had no automated
coverage at all. It is also exactly the code the multi-site seam replaces, so
these tests are the before/after witness for that work.

### Isolation

Most classes create their **own throwaway groupfolder** (mount point prefixed
`IntraVoxITest`), plus a group and a member user, and remove all three in
`tearDownAfterClass`. Stray objects from a crashed run are cleaned up at the
start of the next one, so the suite is repeatable without manual work.

`PageLifecycleTest` is the exception and has to be: `SetupService` resolves the
content folder by the hardcoded name `IntraVox` — that hardcoding *is* the
single-site assumption. (The lookup used to live on `PageService`, which the
3.0 split removed.)
So it writes into the real groupfolder, but only into pages it creates itself,
with a unique id, and it deletes them in `tearDown()` even when a test fails.

### The suite must bite

A test floor that cannot fail is worse than none, because it reports safety it
does not provide. To check, mutate the constant everything resolves through —
**in the container only**, never in the working tree:

```bash
# in nc-dev: set SetupService::GROUPFOLDER_NAME to a name that does not exist
scripts/run-integration-tests.sh --no-deploy    # must FAIL
```

Two tests in `GroupFolderResolutionTest` fail on a wrong constant. Note that
mutating it in the *working tree* and deploying does **not** work as a check:
the `SetupDemoData` migration runs on upgrade and provisions a groupfolder
matching whatever the constant says, so the suite finds one and passes. That is
how an earlier, weaker version of this test slipped through — it compared two
calls to each other instead of asserting which folder was found.

### Requirements

- SSH access to nc-dev (override with `INTRAVOX_DEV_SSH`, `INTRAVOX_DEV_CONTAINER`)
- the groupfolders app enabled on that instance
- an IntraVox groupfolder with at least one page, for the listing assertions
