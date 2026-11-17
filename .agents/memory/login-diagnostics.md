---
name: Login diagnostics
description: Avoid shared login throttling while investigating preview session errors.
---

Do not repeatedly submit deliberately invalid credentials through the preview to diagnose session tokens.

**Why:** Diagnostic attempts contributed to the same login-throttling warning the owner encountered. The development server receives proxied traffic on a shared local connection, so unrelated diagnostic attempts can affect the owner's next login.

**How to apply:** Prefer passive cookie and token checks. A successful fresh-session probe does not establish that the owner's browser has valid cookies. Keep session-token failures separate from password failures, and do not disable CSRF or brute-force protection to make login work.

## Firefox protection setting

The owner reported that disabling Firefox's "enhanced security" allowed login, and supplied a screenshot of the signed-in IntraVox welcome screen. A failing attempt had included all required cookies but lacked the form token. The exact browser mechanism was not established.

**Why:** Refreshing, resetting the login delay, and opening a separate tab had not resolved the failure. Do not treat a successful clean Chromium or HTTP session as proof that this Firefox submission is working.

**How to apply:** If this symptom recurs, check browser protection settings and form-token presence before changing Nextcloud session configuration. Prefer a narrowly scoped site exception over disabling browser protection globally. This is an observed workaround, not a proven application-side fix.
