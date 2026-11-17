---
name: Login diagnostics
description: Avoid shared login throttling while investigating preview session errors.
---

Do not repeatedly submit deliberately invalid credentials through the preview to diagnose session tokens.

**Why:** Diagnostic attempts contributed to the same login-throttling warning the owner encountered. The development server receives proxied traffic on a shared local connection, so unrelated diagnostic attempts can affect the owner's next login.

**How to apply:** Prefer passive cookie and token checks. A successful fresh-session probe does not establish that the owner's browser has valid cookies. Keep session-token failures separate from password failures, and do not disable CSRF or brute-force protection to make login work.
