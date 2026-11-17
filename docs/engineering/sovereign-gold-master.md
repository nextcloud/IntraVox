# Sovereign 365 Gold Master content deployment

[Development playbook](sovereign-development.md) · [Release record](../releases/portal-v1.0.md)

## Source and release contract

`demo-data/sovereign-365/en/` is the reviewed, version-controlled English CMS
bundle exported from the live prototype. It contains 23 JSON/SVG files: the
existing bookmarked homepage, navigation, footer, ten office/hub destinations,
three announcements and seven resource SVGs. The native frontend implements
the hero clocks, responsive grids and governance widget from these CMS rows.
No generic demo pages or videos are included in this **Sovereign bundle**.
Existing upstream multilingual demo directories remain separate and unchanged.

The homepage retains `page-3429f149-dd7c-492a-8a4a-dab3ff83d511`.
Application version remains **3.1.2**, with declared Nextcloud support 32–35.
Local evidence from Nextcloud 32 does not establish a Nextcloud 35 qualification
or a successful downstream Jenkins/Sonar run.

## Automatic deployment and explicit synchronization

The registered post-migration repair step runs **after native IntraVox setup**
on app install/update. It calls the same service as:

```bash
php /path/to/nextcloud/occ intravox:sync-sovereign-portal --no-interaction
```

A downstream deployment job replacing files **without a native app upgrade**
(for example a same-version development handoff) must invoke this OCC command
as the Nextcloud service account immediately after installing the package.
Make that invocation a pipeline step, not an operator's manual shell action.
No environment-specific SSH, credentials, production paths or remote deployment
are added to this repository's build-only Jenkins job. Its staged archive now
includes both application code and the Gold Master content.

The synchronizer resolves the current IntraVox Team Folder through the existing
storage resolver; no numeric folder ID is embedded in deployment code or content.
An exclusive Nextcloud lock serializes sync runs. A sorted SHA-256 manifest and
the actual live managed bytes must both match before a no-op is reported.
`--force` creates another private snapshot and rechecks all managed files.
Equal files are not rewritten, preserving file IDs and timestamps.

**This is an authoritative CMS update:** changed managed files are replaced.
Before any content writes, every existing managed file is snapshotted in
app-scoped private appdata `intravox/backups/sovereign-<timestamp>-<random>/files.json`.
That JSON maps bundle-relative paths to their exact previous contents.
Snapshots do not enter the public page tree. Unrelated live pages are not deleted.
The executive folder uses the existing baseline's real Team Folder ACL policy,
including administrator and ExCom access; new executive content is not published
before that policy is applied.

All content writes go through native Folder/File APIs; no `files:scan` is needed.
Page/navigation, permission and people/cohort caches are invalidated, the English
page index is cleared for native rebuilding, and the bundle marker is committed
last. Errors propagate as a nonzero command/deployment result; a partial failure
can be retried because both marker and live bytes are checked.

## Update and verification

To refresh the bundle from this managed development prototype only:

```bash
node scripts/replit/export-sovereign-bundle.cjs
node scripts/check-sovereign-docs.cjs
node --test scripts/check-sovereign-docs.test.cjs
npm ci && npm run build
bash scripts/ci-local.sh
bash scripts/check-package-contents.test.sh
bash scripts/stage-release.sh
```

The export tool has a fixed development-only source and a positive path allowlist.
The deployable synchronizer never uses that source path. Review exported changes
before committing; the release guard rejects sandbox hosts, internal storage
paths, missing destinations, malformed JSON, symlinks and generic demo media.
The bundle is installed from the app path, not a workspace-relative directory.
The disposable integration test exercises backup, idempotence, drift correction,
native file identity and the automatic repair step without touching owner data.

## Recovery

Stop the deployment job before recovery. Review the private snapshot as a
Nextcloud administrator, then restore only the intended paths through native
Folder/File APIs under the configured IntraVox folder. Never expose appdata
snapshots via a public CMS page or copy raw files behind Nextcloud's cache.
Restore a compatible prior app package as needed, clear the seed marker and app
caches, and validate permissions/content on a non-production clone first.
There is no generic production rollback command or credential in this repository.
