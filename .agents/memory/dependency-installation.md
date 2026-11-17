---
name: Dependency installation
description: Preserve portable npm lockfiles while installing packages through Replit's security firewall.
---

Check lockfile changes after package installation: the package installation tool
can persist internal firewall download URLs and update more of the dependency
graph than intended.

**Why:** A routine dependency install rewrote public npm URLs to a hostname that
other developers cannot resolve. Restoring the imported graph exposed a blocked
DOMPurify release, so a focused security update was necessary rather than simply
reverting every dependency change.

**How to apply:** Retain publicly reachable canonical registry URLs and integrity
hashes in committed lockfiles. Compare package versions with the imported graph,
keep necessary security updates narrowly scoped, and test a clean locked install
through the normal package firewall without bypassing it.
