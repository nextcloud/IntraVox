# Sovereign 365 Portal v1.0 — release record

[README](../../README.md) · [Code map](../engineering/portal-code-map.md) · [Quality gates](../engineering/release-quality.md) · [Playbook](../engineering/sovereign-development.md)

**Status: preparation only; release blocked.** This record distinguishes the
approved UX from unfulfilled engineering gates. It does not announce a release.

**Development transfer approved:** the owner authorized pushing the code and
documentation to GitHub `dev` so downstream can run Jenkins, Sonar and CI/CD
testing. That development handoff is not blocked by missing downstream results.
No tag, signing or deployment is part of the approved push.

## Identity and approval

- Portal label: **v1.0**; owner-confirmed tag: **`version-1.0.0`**.
- Underlying Nextcloud app: **`intravox` 3.1.2**, unchanged.
- Replit destination: **`https://github.com/scott-mackenzie/IntraVox-AGL`**, **`dev`**.
- Owner approved the reviewed Portal UX on **17 November 2026**.
- No direct Gitea push, force-push, signing, Nextcloud deployment or production
  change is authorized by this record.

## Change summary

- Sovereign identity, compact two-tier context/navigation and native theme support.
- Homepage hero with Nassau/Athens clocks and Nextcloud Unified Search entry points.
- Eight app cards, availability-aware disabled states, consistent office cards,
  whole-card C3 links and executive-role destinations.
- Entity-tagged prototype announcements and interactive executive structure.
- Readable dropdown, editor and settings controls across light/dark themes;
  mobile and embedded Preview layout checks.
- Native page settings, Save/reload and create/edit/delete verification.
- Local managed baseline/provisioning and separate production runtime dependencies.
- Unsigned allowlisted packaging and package-guard regression checks.
- Sovereign README, approved UX screenshots, code map, downstream build/release
  playbook and explicit Jenkins/Sonar enforcement boundaries.

Detailed implementation entry points are in the [code map](../engineering/portal-code-map.md).
This is a summary, not a claim that disabled companion apps are absent in the
owner's production Nextcloud.

## Existing candidate evidence

These files are **private handoff attachments**, intentionally ignored by Git:

| Evidence | Recorded result / location |
| --- | --- |
| Authenticated browser checks | `generated-artifacts/sovereign-qa/verification.json`; `passed: true`, 52 records across admin/reader/ExCom, 2026-11-17T13:53:22.767Z |
| UX review | `generated-artifacts/sovereign-qa/portal-v1-ux-review.html`; owner-approved |
| Unsigned package | `build/intravox-3.1.2.tar.gz`; package guard passed; no signature or symlinks; inspected non-vendor contents matched source at preparation time |
| Latest unit/static check | `generated-artifacts/release/portal-v1-unit-static.log`; failed `file budgets` for the hero's missing baseline |
| Latest integration attempt | `generated-artifacts/release/portal-v1-integration.log`; duplicate Composer autoloader fatal before tests ran |
| Expanded preparation report | `generated-artifacts/release/portal-v1-gitea-handoff.html`; historical filename retained after destination correction to GitHub `dev` |

SHA-256 of the inspected candidate package:
`d6e510aa81e94e60de2171a9bbac445e994383a169c9a9f843fce831885fc794`

SHA-256 of the existing 52-check JSON:
`703e3643e1a1e1de0cb5a6fd856814fcbdf8358a4f9d502eaadb96b37f12ec81`

SHA-256 of the approved UX HTML:
`6bc5c0cb396cd0e870752019d65b7ed00d3785e18b3d7ee39d639d16d7f978e5`

These are historical candidate fingerprints, not a reproducible-build or final
tag attestation. The earlier QA evidence was not bound to a committed source SHA.
Bind the final candidate to its reviewed source SHA and repeat affected checks
before release; refresh hashes if any artifact changes.

## Open release conditions

- Resolve the file-budget baseline issue without weakening the ratchet.
- Resolve development/runtime autoloader isolation and execute the full real
  integration suite. Historical assertion/fixture failures remain unproven until
  a complete run succeeds; a startup fatal cannot close them.
- Obtain the Portal-specific Jenkins and Sonar evidence described in the gate matrix.
- Transfer the authorized development candidate to GitHub `dev`, verify its
  remote SHA and let downstream execute Jenkins/Sonar/CI/CD checks against it.
  Keep known failures visible; hold release tagging and promotion until required
  gates pass.
- Create `version-1.0.0` only on the reviewed release commit, with no conflicting
  remote tag, and reconcile downstream receipt independently of mirror timing.

The [CI gate matrix](../engineering/release-quality.md) records additional
automation gaps. This document does not silently waive them.

## Release-owner closeout checklist

- [ ] Source SHA, toolchain and clean build recorded.
- [ ] README, code notes, current screenshots and downstream playbooks validated.
- [ ] Unit/static/prebuild, full integration, package and authenticated UX gates green.
- [ ] Portal-specific Sonar Quality Gate and trusted Jenkins results recorded.
- [ ] Private evidence bundle and exact unsigned package checksum transferred.
- [ ] Authorized GitHub `dev` development handoff SHA verified.
- [ ] After required gates pass, `version-1.0.0` release tag separately authorized and verified.
- [ ] Downstream confirms the received SHA/tag and independently verifies checksums.
- [ ] Separate operator approval exists before any signing or deployment.
