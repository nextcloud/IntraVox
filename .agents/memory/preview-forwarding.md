---
name: Preview forwarding
description: Distinguish a generic preview proxy response from a failing local Nextcloud server.
---

When Nextcloud is healthy locally but the development-domain response is HTTP 200
with only `Running`, check explicit port forwarding before changing application
code or retrying dependency installation.

**Why:** The preview proxy returned that generic response despite a running,
reachable PHP server; explicitly mapping its local web port to the public HTTP
port restored the real Nextcloud response.

**How to apply:** Compare the development-domain response with the configured
workflow's listening address and local HTTP response. Keep the web workflow and
port mapping consistent, and validate configuration changes through Replit's
configuration tools.
