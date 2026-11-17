---
name: Nextcloud bundle size decisions
description: Preserve default production size guards and all languages when optimizing Nextcloud frontend dependencies.
---

Keep webpack's default production size warnings enabled. Preserve the full Nextcloud language set; do not solve a size warning by dropping non-English translations.

**Why:** Nextcloud's root component barrel retains unused component stylesheet side effects, and the dialogs package embeds all locale data in one otherwise unsplittable module. Splitting only by package is insufficient, and suppressing warnings conceals the underlying loading cost.

**How to apply:** Prefer documented per-component entrypoints and genuinely shareable modules. If an upstream dependency changes its export or locale layout, review the build adaptation and its contract checks rather than bypassing them.

## Conflicted immutable bundles

Do not assume an incremental build repairs an existing content-hashed asset.
Discard conflicted generated bundles before rebuilding; never merge minified
JavaScript by hand or accept new size warnings as harmless merge noise.

**Why:** Webpack can treat an existing hashed asset as immutable and skip writing
the correct bytes over it. A conflict resolver can also leave merged content in
an index side that looks like a valid conflict choice. The source may be correct
while the retained bundle still contains conflict markers and grows dramatically.

**How to apply:** After generated-asset conflicts, regenerate the affected output
from clean source and confirm its bytes match the tested candidate. Keep the
default size guards enabled so unexpected output growth remains visible.
