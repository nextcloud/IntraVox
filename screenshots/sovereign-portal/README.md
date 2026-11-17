# Approved Portal documentation screenshots

These six WebP images are curated documentation assets from the approved
17 November 2026 authenticated QA session. They replace the original IntraVox
demo imagery in this fork's README. Original feature-guide screenshots elsewhere
remain historical IntraVox examples, not current Sovereign UX evidence.

| Documentation asset | Original capture under `generated-artifacts/sovereign-qa/` | View |
| --- | --- | --- |
| `home-light.webp` | `admin-top-light.png` | Admin homepage top, light theme |
| `home-dark.webp` | `admin-top-dark.png` | Admin homepage top, dark theme |
| `launchpad-light.webp` | `admin-middle-light.png` | Scrolled launchpad/office cards, light theme |
| `editor-light.webp` | `admin-editor-light.png` | Native editor, light theme |
| `settings-light.webp` | `admin-settings-light.png` | Page settings, light theme |
| `mobile-dark.webp` | `reader-mobile.png` | Scrolled reader mobile view, dark theme |

Images were inspected and converted without cropping, compositing, changing
content or inventing UI. Conversion strips metadata and uses WebP quality 88:

```bash
# Example; ImageMagick is documentation tooling, not a runtime dependency.
magick generated-artifacts/sovereign-qa/admin-top-light.png \
  -strip -quality 88 screenshots/sovereign-portal/home-light.webp
```

This is a selected, sanitized presentation copy; it does not turn the original
private QA reports, screenshots directory or runtime into source artifacts.
These assets total less than 512 KiB. Do not commit raw reports, credentials,
owner sessions, private datasets or generated release archives.

## Refresh procedure

1. Preserve existing approved QA evidence privately before rerunning checks.
2. Capture the affected views using temporary QA accounts and native sign-in.
3. Review for sensitive content, browser/account details and prototype labelling.
4. Replace the relevant curated images, keeping relative paths stable.
5. Update this provenance table and the release evidence date/source revision.
6. Run `node scripts/check-sovereign-docs.cjs` and its regression tests.
7. Visually inspect the images and README rendering; obtain renewed UX approval
   for changed UX. Passing link checks alone does not validate image content.

The existing images show the previously approved UX; a documentation-only refresh
does not imply new browser verification. See the [release record](../../docs/releases/portal-v1.0.md).
