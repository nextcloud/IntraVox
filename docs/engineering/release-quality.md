# Release quality: standards of excellence

[README](../../README.md) · [Playbook](sovereign-development.md) · [Current release](../releases/portal-v1.0.md)

## Confirm, verify, validate

- **Confirm** the intended behaviour, owner approval, target repository/ref,
  release identity, environment boundaries and downstream acceptance criteria.
- **Verify** the source, build, metadata, interfaces, tests, package contents,
  checksums and documentation against those confirmed requirements.
- **Validate** the real authenticated user experience and access boundaries in
  a representative runtime, then have downstream reconcile the received revision.

This is an engineering acceptance contract, not a claim of certification or a
claim that all gates are already automated. Missing evidence is **not proven**,
skipped is **not passed**, and a failure blocks release until resolved and rerun.

## Gate matrix: intended acceptance vs current enforcement

| Gate | Acceptance evidence | Current implementation / gap |
| --- | --- | --- |
| Documentation alignment | Current README, screenshots, code map, changes, build/playbook and limitations; local references valid | Documentation validator and its regression tests in Jenkins and GitHub's frontend job; also runnable locally. Content accuracy and visuals still need human review. |
| Version/asset contract | Matching 3.1.2 package/app metadata and valid built manifest | Prebuild version check; package content guard; do not hardcode hashed filenames |
| PHP correctness | Syntax, unit tests and PHPStan green | `scripts/ci-local.sh`; GitHub CI PHP job on PHP 8.2 |
| Frontend correctness | All prebuild checks pass; no unjustified size/ratchet increases | `npm run build` prebuild and local guards; the hero's first baseline is now recorded with no previously committed budget increased |
| Full integration | Executed tests against isolated real Nextcloud + group folders; no failures; skips explicitly reviewed | Not in Jenkins or GitHub CI; current local invocation fatals before tests run |
| Authenticated UX | Role and theme matrix, settings persistence, create/edit/delete, mobile overflow, search, authorized/denied access | Browser runner; original approved evidence has 52 records; final-polish suite adds four theme/viewport geometry checks, not a substitute for integration |
| Package safety | Production-only dependencies, expected root/metadata/assets, excluded dev/private material, no symlinks | Staging + package guard + regression tests; existing candidate passed |
| Supply-chain checks | Locked dependency review, secret scan, audit findings disposition | GitHub CI includes secret scan and Composer audit; npm audit is advisory/non-blocking there |
| Sonar | Trusted Portal-specific analysis for the same SHA; successful server-side Quality Gate | No repository configuration, scanner stage, supplied threshold policy, or verified result |
| Provenance and transfer | Source SHA, exact unsigned archive checksum, approval and received downstream refs match | Release operator evidence; no automatic proof from mirror schedule |

Do not merge these rows into a single “CI passed” statement. Each has different
coverage and a different evidence source.

## What Jenkins actually does

The [Jenkinsfile](../../Jenkinsfile) currently:

1. Runs the Sovereign documentation validator and its regression tests.
2. Runs `npm ci && npm run build`, including the package's prebuild guards.
3. Runs `bash scripts/check-package-contents.test.sh`.
4. Runs `bash scripts/stage-release.sh`.
5. Archives `build/*.tar.gz` with Jenkins fingerprints and an explicit
   `**/generated-artifacts/**` archive exclusion. There is no `rsync` staging
   step; the positive staging allowlist and archive-content guard prevent QA
   files from entering a tarball. Regression tests explicitly reject a package
   containing `generated-artifacts/sovereign-qa/private-review.html`.

It does **not** install the root development Composer tree, run PHP unit tests,
PHPStan, full integration, authenticated browser checks, dependency/secret
scans or Sonar. No deployment or signing stage is
present. Composer is used by staging for production dependencies only.

A failed prebuild prevents packaging. A successful archive stage does not
fulfil the missing gates. Jenkins fingerprints help identify artifacts but do
not replace a separately recorded SHA-256 and source revision.

## What GitHub CI actually does

The [CI workflow](../../.github/workflows/ci.yml) runs on pushes to `main`
and on pull requests. **A direct push to `dev` is not covered by its push
trigger.** Do not infer that handing off to the approved `dev` branch runs it.
The other workflow files must be read for their own trigger/coverage scope.

The CI workflow has documentation checks, PHP tests/lint/static analysis, selected dependency-free
frontend guards, package-guard fixtures, Composer runtime-dependency audit,
advisory npm audit and a secret scan. It does not perform the full Portal build,
real-runtime integration or Sonar analysis.

Its packaging job uses inline historical fixtures that predate the additional
manifest/theme content requirements in the guard. Treat their consistency as
unverified until the actual job passes; do not cite the local package regression
script's pass as proof that these distinct inline fixtures pass.

## Sonar gate: required downstream evidence, not a fabricated configuration

The configured Athena CI integration exposes evidence for other named projects.
That does not establish a Portal Sonar project or a Portal quality result.
There is no `sonar-project.properties` or Sonar stage in this repository.

Before treating Sonar as satisfied, the trusted downstream CI owner must:

1. Confirm the Portal project key, trusted Sonar endpoint, analysis scope,
   branch mapping and applicable Quality Profile/Quality Gate.
2. Approve measurable thresholds for new-code issues, security review, coverage
   and duplication. No numeric thresholds have been supplied for this Portal;
   do not present invented values as organizational policy.
3. Supply scanner credentials through the CI credential store, never source or
   chat, and configure the scanner/webhook or equivalent trusted result check.
4. Analyse the exact release SHA. If coverage is an input, generate compatible
   coverage reports in a separate job with the required PHP coverage driver.
   Current CI explicitly disables PHP coverage; coverage is not currently proven.
5. Wait for the server-side Quality Gate result and fail on error, rejection,
   timeout, or missing analysis. “Scanner uploaded” is not “Quality Gate passed.”
6. Retain the analysis/task identifier, report URL, commit SHA, gate name,
   threshold policy, result and time in the handoff.

Do not hardcode tokens, guess endpoints, borrow another application's result,
or add decorative passing badges. A future enforcement change must be reviewed
and tested separately; this documentation does not activate a Sonar gate.

## Evidence and sign-off

For each gate record: requirement, source SHA, exact command/job, environment,
tool versions, start/end time, exit/result, skips, report/checksum and reviewer.
Use private attachments for raw QA/build evidence; curate only non-sensitive,
approved documentation screenshots into source.

Acceptance requires all required gates green on the intended candidate, explicit
disposition of allowed skips, UX approval, and downstream receipt reconciliation.
Red gates and missing required Sonar evidence must be resolved or receive an
explicit policy-owner decision; this handoff grants no waiver.
