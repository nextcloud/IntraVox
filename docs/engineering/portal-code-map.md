# Portal code map and engineering constraints

[README](../../README.md) · [Build playbook](sovereign-development.md) · [Quality gates](release-quality.md)

This guide describes the Sovereign customisation, not a replacement architecture
for Nextcloud. Consult the [IntraVox architecture](../architecture/overview.md)
and [API reference](../../openapi.json) for the underlying application.

## Code ownership map

| Area | Source of truth | Behaviour to preserve |
| --- | --- | --- |
| Homepage hero | [SovereignOverviewHero.vue](../../src/components/SovereignOverviewHero.vue) | Product identity, DST-aware Nassau/Athens clocks, native search entry point |
| Brand/context bar | [AglBrandContextBar.vue](../../src/components/AglBrandContextBar.vue) | Compact header, entity lens, C3 destinations; do not duplicate the native logo |
| Page composition | [PageViewer.vue](../../src/components/PageViewer.vue) | Native viewing, editing, page settings and saved page metadata |
| Link cards | [LinksWidget.vue](../../src/components/LinksWidget.vue) | Whole-card navigation, readable subtitles, correct disabled behaviour |
| Executive structure | [OrganogramWidget.vue](../../src/components/OrganogramWidget.vue), [organisation data](../../src/data/aglOrganogram.js) | Interactive structure and role-aware presentation, without replacing ACLs |
| App availability | [aglAppAvailability.js](../../src/utils/aglAppAvailability.js) | Explicit server denial wins; a browser-mounted app does not override it |
| Search | [aglGlobalSearch.js](../../src/utils/aglGlobalSearch.js) | Nextcloud Unified Search, including keyboard entry; no invented external service |
| Navigation | [aglNavigationMixin.js](../../src/utils/aglNavigationMixin.js) | Native navigation semantics and tooltips |
| Theme | [agl-sovereign-theme.scss](../../src/css/agl-sovereign-theme.scss) | Native light/dark/system themes, compact desktop and responsive mobile |
| Baseline definition | [SovereignBaseline.php](../../lib/Service/SovereignBaseline.php) | Stable homepage identity, managed pages, offices and announcement metadata |
| Provisioning | [SovereignBaselineInstaller.php](../../lib/Service/SovereignBaselineInstaller.php), [local seeder](../../scripts/replit/seed-sovereign.php) | Deliberate local-only refresh, stable IDs and protected content boundaries |
| Persisted shape | [PageShapeSanitizer.php](../../lib/Service/Sanitize/PageShapeSanitizer.php) | Accept only supported page/widget metadata; preserve sanitisation boundaries |
| Runtime setup | [setup.sh](../../scripts/replit/setup.sh) | Managed local runtime only; production dependencies separate from dev tools |
| Release packaging | [stage-release.sh](../../scripts/stage-release.sh) | Positive content allowlist, production vendor tree, unsigned package |

## Data and security invariants

- The entity lens filters the display; it is not an authorization boundary.
  Native Nextcloud users, groups, Team Folder grants and executive ACLs decide access.
- Preserve the existing English homepage identity and bookmarks. Do not recreate
  pages just to refresh visual content.
- Announcements are tagged prototype samples. C3 labels and coloured indicators
  do not prove live service health, readiness, audit completion, or certification.
- Ordinary reader and executive paths both need testing. Hidden UI alone does
  not prove private content is inaccessible; check native API responses too.
- Keep native editor Save/Cancel, settings Save/reload, page lifecycle,
  search and navigation behaviour intact.
- Never load development OCP/Sabre dependencies into the Nextcloud app runtime.
  Production packaging uses a separate `composer install --no-dev` tree.

## Asset and dependency boundaries

Webpack emits the ordered [entrypoint manifest](../../js/entrypoints.json) and
JavaScript under `js/`, with extracted CSS under `css/`. PHP uses that manifest;
do not hardcode hashed chunk names. Preserve webpack's default size guards and
all locales. Avoid broad component imports that drag unused styles into bundles.

Use lockfiles. Review any lockfile changes for unrelated upgrades and internal
registry URLs; do not replace a dependency graph as part of a documentation edit.
The app ID and package version remain `intravox` / `3.1.2` unless an explicitly
approved compatible app-version change is made.

## Change-impact checklist

| Change | Required matching documentation and verification |
| --- | --- |
| Layout, theme, navigation or cards | README screenshots and provenance; desktop/mobile, light/dark, editor/settings and relevant role checks |
| Roles, availability or metadata | Authorization and code notes; positive and negative access checks; saved-data round-trip |
| Baseline/provisioning | Managed-file scope, backup/restore instructions, stable IDs and idempotence evidence |
| API or sanitisation | API/schema documentation, route/OpenAPI checks, unit and real runtime tests |
| Dependencies, packaging or asset loading | Build guide, package checks, runtime isolation and reproducible-source evidence |
| Jenkins, Sonar or release rules | Gate matrix, exact enforcement status, evidence locations and downstream operator steps |

For every handoff, update the relevant row's documentation **in the same change**
as the code. Record what was tested and what was not; do not convert an intended
behaviour into a verified claim without evidence.
