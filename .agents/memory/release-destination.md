---
name: Release destination
description: Owner-confirmed source-control destination and mirror boundary.
---

Replit pushes all changes only to `https://github.com/scott-mackenzie/IntraVox-AGL`, branch `dev`, not directly to Gitea.

**Why:** The owner explicitly corrected the release destination. They described an existing mirror sync from Gitea to GitHub that checks every minute for changes.

**How to apply:** Use the GitHub `dev` branch for authorized handoffs. Do not infer that a GitHub push updates Gitea, reverse the mirror, or change mirror settings. Confirm the exact release tag separately; a marketing release label does not authorize changing the underlying Nextcloud app version.
