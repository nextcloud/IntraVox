# Language Management

Your intranet can hold content in **any language Nextcloud supports**. This guide explains how a language becomes active, how to pick the **recommended language** users fall back to, what users see when their own language has no content, and how to add or remove a language.

> Content-driven model (since 1.7.0): a language is **active as soon as it has a homepage** — there are no enable/disable checkboxes. You add a language (which creates an empty homepage) or fill an existing one, and it appears for users in that locale automatically. Users are shown the **recommended language** when their own language has no content (since 1.8.2, [#75](https://github.com/nextcloud/IntraVox/issues/75)).

## Where to find it

1. Open **Administration settings** → **IntraVox**.
2. Switch to the **Languages** tab.
3. The **Intranet languages** section shows the languages that have content, the recommended language, and controls to add or remove a language.

## What you see

- **Languages with content** — a chip for every language that has real, editor-authored content, with its UI translation coverage (e.g. "UI 93%"). The recommended language is marked. This is the live set of languages your users can actually browse.
- **Recommended language** — a dropdown to pick the fallback language (see below). It only offers languages that have content, plus English.
- **Add a language** — pick any Nextcloud-supported language and click **Add language** to create an empty homepage for it.

A language is not something you "switch on" — it becomes active the moment it has a homepage (whether you added it here, imported demo content for it, or an editor created a page in it).

## What users see when their language has no content

A user's Nextcloud **display language** decides which content they see. When their language has no IntraVox content, IntraVox picks a fallback — it does **not** leave them on an empty page. The resolution order is (since 1.8.2, [#75](https://github.com/nextcloud/IntraVox/issues/75)):

1. **The user's own language**, if it has real content (an editor-authored homepage, not just an auto-generated placeholder).
2. **The recommended language** — the language you pick in the **Recommended language** dropdown — if it has content. This is exactly what the setting is for: "which language does a user without their own language see."
3. **English (`en`)** — the universal fallback and Transifex source language.
4. Only when **none** of the above has content does IntraVox show the *"No content in your language yet"* notice, which lists the languages that do have content and links the user to their personal language setting.

So on an English-only intranet, a user whose display language is German simply sees the English pages — no notice. If you set the recommended language to Dutch and have Dutch content, those same users see the Dutch intranet instead.

> **The recommended language is a viewing fallback only.** An editor always authors and saves pages in their **own** language — creating a page never redirects the write into the recommended language.

English cannot be removed: it's the guaranteed final fallback and the Transifex source language. The **Recommended language** dropdown only offers languages that actually have content (plus English), so you can't point the fallback at an empty language.

## Adding a language

1. In **Add a language**, pick the language.
2. Click **Add language**.

What happens server-side:

- A content folder for that language is created if it doesn't exist yet, with an empty `home.json` homepage — so the language is immediately navigable.
- Cached page trees are flushed so the language appears for its users at once.
- The language shows up in the "Languages with content" chips and, if it has bundled demo content (Dutch, English, German, French), in the Demo content table.

Bundled demo content is full-intranet quality for a few languages only (Dutch, English, German, French). For any other language you start from the empty homepage and build up from there — create pages, add navigation entries, and so on.

## Removing a language

1. Find the language chip under **Languages with content**.
2. Click its **×** and confirm.

What happens:

- The language's content folder (all its pages, navigation, footer, comments and media) is moved to the Nextcloud **trash**, so nothing is lost immediately — it can be restored from the Files trash bin.
- Cached page trees are flushed so the language disappears from users' views right away.

Two languages are protected and cannot be removed:

- **English** — the universal fallback and source language.
- **The current recommended language** — pick a different recommended language first, then you can remove it.

## License tally impact

The free-tier limit is **50 pages per language**. It's counted per language content folder, so each language has its own 50-page budget. Removing a language moves its pages to the trash, so they no longer count toward any tally; restoring the folder from the trash brings both the pages and their tally back.

## Common scenarios

### "We only publish in English, but our users have mixed display languages"

Leave English as the recommended language (the default). Every user — whatever their Nextcloud display language — sees the English intranet, with no "change your language" notice. This is the most common single-language setup.

### "We're a Dutch organisation; German/French users should see Dutch"

Set the **Recommended language** to Dutch. Users whose display language has no IntraVox content (e.g. German, French) are shown the Dutch intranet instead of English.

### "We tried Spanish, didn't work out"

Remove the Spanish chip under **Languages with content**. Its folder goes to the trash; no data loss, and you can restore it later from the Files trash bin if you change your mind. (If Spanish is your recommended language, switch the recommended language first.)

### "A new Transifex language reached decent coverage — let's offer it"

Add the language under **Add a language**. It gets an empty homepage; users in that locale then see IntraVox's own interface in their language and the empty homepage as a starting point, and you can build out its pages.

## Backups

- The recommended-language setting lives in `oc_appconfig.intravox.primary_language` — included in standard Nextcloud config backups.
- Content folders are inside the IntraVox GroupFolder — included in standard data backups.

Restoring a backup restores both the recommended language and the content folders, exactly to the moment of backup.

## Troubleshooting

**"A user's display language has content, but they still see the fallback language."**
Content only counts when it's real (editor-authored). A freshly added language starts with an auto-generated placeholder homepage, which does **not** count as content until an editor saves a real page in it. Create/edit a page in that language and the fallback stops.

**"After adding a language, no homepage appears."**
Check the Nextcloud log for `[LanguageHomepageService]` errors. The most common cause is a fresh install where the IntraVox GroupFolder isn't set up yet — the **Setup** action under **Support → Maintenance** resolves it.

**"I removed a language by mistake."**
Its folder is in the Nextcloud trash. Restore it from the **Files** app trash bin; the language reappears with all its pages.

## See also

- [Admin Settings](settings.md) — the Languages tab and the rest of the admin panel.
