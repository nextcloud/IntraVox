---
name: Release destination
description: Owner-confirmed source-control destination and mirror boundary.
---

Replit pushes all changes only to `https://github.com/scott-mackenzie/IntraVox-AGL`, branch `dev`, not directly to Gitea.

**Why:** The owner explicitly corrected the release destination. Their current
Gold Master handoff describes the downstream path as GitHub dev → Gitea mirror
→ Jenkins → Nextcloud 35.

**How to apply:** Use GitHub `dev` for authorized handoffs, and other branches only
when explicitly requested. Do not claim downstream receipt without evidence,
reverse the mirror, or change its settings. Confirm the exact release tag
separately; a marketing label does not authorize changing the Nextcloud app version.

Development handoff and release approval are separate. An authorized development
candidate can be pushed to `dev` with known failures documented so downstream
can run Jenkins, Sonar and CI/CD validation. Hold release tagging and production
promotion until the required gates and approvals pass.

**Why:** Downstream cannot validate source it has not received. Requiring its
CI results before the development push creates a circular dependency.

**How to apply:** Verify the remote candidate SHA after the development push,
report unresolved checks plainly, and do not represent that transfer as a
release or as permission to deploy.
