---
name: Nextcloud bundle size decisions
description: Preserve default production size guards and all languages when optimizing Nextcloud frontend dependencies.
---

Keep webpack's default production size warnings enabled. Preserve the full Nextcloud language set; do not solve a size warning by dropping non-English translations.

**Why:** Nextcloud's root component barrel retains unused component stylesheet side effects, and the dialogs package embeds all locale data in one otherwise unsplittable module. Splitting only by package is insufficient, and suppressing warnings conceals the underlying loading cost.

**How to apply:** Prefer documented per-component entrypoints and genuinely shareable modules. If an upstream dependency changes its export or locale layout, review the build adaptation and its contract checks rather than bypassing them.
