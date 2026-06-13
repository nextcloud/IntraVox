# Upgrade to IntraVox 1.6.0

**TL;DR:** No action required. Your existing installation continues to work exactly as before, with the same four languages enabled by default. New: a Demo Data tab section lets you curate which translated languages appear in your intranet.

## What you need to do

Nothing. `occ upgrade` runs the migration, your config is seeded with `['nl','en','de','fr']` (the legacy default), and your users see no change.

## What's new

### Admin-curated language list

The Demo Data tab in IntraVox admin settings has a new **Available languages** section at the top. Every language IntraVox ships a translation for appears as a checkbox; you tick the ones you want to use in your intranet.

For fresh upgrades the four legacy languages are pre-ticked, so existing menus and content don't change.

See [Language Management](language-management.md) for the full guide.

### Transifex translation pipeline

IntraVox is now packaged for community translations via Nextcloud's [Transifex pool](https://app.transifex.com/nextcloud/nextcloud/intravox/). New translations beyond Dutch, English, German and French will land in IntraVox automatically as the community contributes them on Transifex, and they'll appear in your **Available languages** list for you to enable.

This pipeline activates after the Nextcloud team provisions the `o:nextcloud:p:nextcloud:r:intravox` Transifex resource. Until then, the four bundled languages are the only ones available.

### Empty homepage for new languages

When you enable a language that doesn't have bundled demo content (so: anything other than NL/EN), IntraVox creates an empty homepage in the new content folder. You can build from there.

## Upgrade safety guarantees

We respect a strict contract for upgrades:

1. **No content is ever deleted.** Folders stay on disk regardless of enable/disable changes.
2. **The default set on upgrade is `['nl','en','de','fr']`** — exactly the legacy hardcoded set.
3. **`occ upgrade` creates no new folders.** Only existing language folders get the `_resources`, `_templates`, and `versions` subfolder updates that 1.5.x already did.
4. **English cannot be disabled.** It is the guaranteed fallback for every code path.
5. **License page-counts stay per-language and never reset** on toggle.
6. **Cache flushing happens automatically** when the enabled set changes.
7. **`occ upgrade` from 1.5.x to 1.6.0 produces zero user-visible changes** until you act in admin settings.

## Frontend cache-buster

Like every IntraVox release, 1.6.0 ships with a bumped app version so Nextcloud's JS cache-buster picks up the new admin-settings bundle. If you have a CDN or aggressive browser-cache in front of Nextcloud, expect a one-time cache miss after upgrade. No further action.

## Free tier note

Pages in disabled languages don't count toward the 50-pages-per-language free-tier limit. Existing installs already at or near the limit on `nl/en/de/fr` see no change (since all four start enabled). Organisations curating their list will get back capacity in disabled languages.

## Reporting issues

[GitHub issues](https://github.com/nextcloud/IntraVox/issues). Helpful info to include: your previous IntraVox version, your Nextcloud version, and the `occ` output from `occ upgrade`.
