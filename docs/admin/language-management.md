# Language Management

Your intranet can hold content in any language Nextcloud supports. This guide explains how languages become active, how to pick the **recommended language** users fall back to, and what users see when their own language has no content yet.

> Note on versions: 1.6.0 shipped a checkbox-based "enable a language" model; 1.7.0 replaced it with the content-driven model described here (a language is active once it has a homepage), and 1.8.2 made the landing page fall back to the recommended language ([#75](https://github.com/nextcloud/IntraVox/issues/75)). Some sections below still describe the legacy enable/disable checkboxes for installs that upgraded through 1.6.x.

## Where to find it

1. Open **Administration settings** → **IntraVox**.
2. Switch to the **Languages** tab.
3. The **Intranet languages** section shows which languages have content, the recommended language, and controls to add or remove a language.

> Since 1.7.0, languages are content-driven rather than toggled with checkboxes: a language becomes **active as soon as it has a homepage**. There is no "enable" checkbox — you add a language (which creates an empty homepage) or fill an existing one, and it appears automatically.

## What you see

A checkbox for every language IntraVox ships a translation for. Each row shows the localised language name (e.g. "Nederlands", "Deutsch"), the two-letter code, and — for English — an "always on" marker.

Default state on a fresh upgrade from 1.5.x: all four previously-supported languages (Dutch, English, German, French) are ticked. That mirrors the hardcoded behaviour of pre-1.6.0 IntraVox, so nothing changes for your users until you actively curate the list.

## Enabling a language

1. Tick the checkbox.
2. Click **Save language selection**.

What happens server-side:

- The language is added to `oc_appconfig.intravox.enabled_languages`.
- If a content folder for that language doesn't exist, one is created (empty).
- If the new content folder has no `home.json`, an empty homepage is created — so the language is immediately navigable.
- All cached page trees are flushed so users see the new language at once.
- The Demo Data table refreshes to show the new language with a status badge.

A new language is **never** populated with bundled demo content unless it's Dutch or English. For the other languages, you start with an empty homepage and build from there. (The bundled demo content is full-intranet quality for NL/EN only; for other languages the empty-homepage start is the pattern.)

## Disabling a language

1. Untick the checkbox.
2. Click **Save language selection**.

What happens:

- The language is removed from `oc_appconfig.intravox.enabled_languages`.
- The language disappears from IntraVox menus, navigation, and the demo-data table.
- **The content folder stays on disk.** All pages, navigation, footer, comments, media, and license tally for that language remain intact.
- Page-tree caches are flushed so users no longer see stale references to the disabled language.

If you re-enable the language later, everything reappears exactly as you left it.

## What users see when their language has no content

A user's Nextcloud **display language** decides which content they see. When their language has no IntraVox content, IntraVox picks a fallback — it does **not** leave them on an empty page. The resolution order is (since 1.8.2, issue #75):

1. **The user's own language**, if it has real content (an editor-authored homepage, not just an auto-generated placeholder).
2. **The recommended language** — the language you pick in the **Recommended language** dropdown on the Languages tab — if it has content. This is exactly what the setting is for: "which language does a user without their own language see."
3. **English (`en`)** — the universal fallback and Transifex source language.
4. Only when **none** of the above has content does IntraVox show the *"No content in your language yet"* notice, which lists the languages that do have content and links the user to their personal language setting.

So on an English-only intranet, a user whose display language is German simply sees the English pages — no notice. If you set the recommended language to Dutch and have Dutch content, those same users see the Dutch intranet instead.

> **The recommended language is a viewing fallback only.** An editor always authors and saves pages in their **own** language — creating a page never redirects the write into the recommended language.

English cannot be removed: it's the guaranteed final fallback and the Transifex source language. The **Recommended language** dropdown only offers languages that actually have content (plus English), so you can't point the fallback at an empty language.

## License tally impact

The free-tier limit of **50 pages per language** only counts pages in enabled languages. If you have 60 pages in `IntraVox/de/` but DE is disabled, those pages don't count toward the limit — until you re-enable DE, at which point the 60 pages reappear in the tally.

This is intentional behaviour, not a loophole: organisations that curate their language list shouldn't be charged for capacity they have hidden away. The pages-per-language semantics scale with what's actually in use.

## Common scenarios

### "We've never used German or French — clean it up"

1. Untick `de` and `fr`.
2. Save.

The `IntraVox/de/` and `IntraVox/fr/` folders stay on disk (probably empty if you never used them). If you want to clean them up entirely later, the **Orphaned Data** scan in Maintenance settings will show them as "non-IntraVox data" once they're no longer in the enabled set — but that scan never touches them automatically.

### "A new Transifex language was added — let's try it"

A new language landing in `l10n/` appears automatically in the "Available languages" section after the next IntraVox release. To activate:

1. Tick its checkbox.
2. Save.

An empty homepage is created in the new content folder. Your users in that locale will see IntraVox navigation in their language and the empty homepage as a starting point. You can then create pages, add navigation entries, etc.

### "We tried Spanish, didn't work out, removed all its pages"

1. Untick `es`.
2. Save.

The `IntraVox/es/` folder stays on disk (now empty). No data loss, no cleanup required. You can leave it indefinitely; it's invisible to users.

## Backups

- The enabled-languages config is in `oc_appconfig.intravox.enabled_languages` — included in standard Nextcloud config backups.
- Content folders are inside the IntraVox GroupFolder — included in standard data backups.

Restoring a backup restores both the enabled set and the content folders, exactly to the moment of backup.

## Troubleshooting

**"I disabled DE but a user reports still seeing it in the language switcher."**
The page-tree cache was supposed to be flushed automatically. If you're seeing stale state, try `php occ files:cleanup` or restart your Redis instance. Open an issue if it persists.

**"After enabling a new language, no homepage appears."**
Check the Nextcloud log for `[LanguageHomepageService]` errors. The most common cause is that the `intravox` system user can't access the GroupFolder — usually a fresh-install issue that the **Setup** action in Maintenance settings resolves.

**"I want to fully delete a disabled language's content."**
Disable the language, then go to **Maintenance** → **Orphaned Data**. The disabled-language folders will appear there as inactive content; you can remove them per language with the per-folder cleanup action. Always back up first.

## See also

- [Transifex Integration](../architecture/transifex-integration.md) — the developer-level story.
- [Upgrade 1.6.0](upgrade-1.6.0.md) — what changed and why.
