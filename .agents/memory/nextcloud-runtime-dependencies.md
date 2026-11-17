---
name: Nextcloud runtime dependency isolation
description: Why a symlinked development app must not load development-only Composer packages into Nextcloud.
---

Keep the installed Nextcloud app's production dependency tree separate from the
repository's development dependencies. Resolve dependencies through the
installed app location, not the physical location of symlinked PHP source.

**Why:** Development OCP dependencies pulled in a newer Sabre XML interface than
the host DAV implementation. Loading that development autoloader into Nextcloud
caused fatal errors during legitimate account/calendar creation, even while
ordinary page rendering and isolated unit tests appeared healthy.

**How to apply:** Use the locked production-only Composer installation for the
managed runtime and release. Keep test tools in the repository's own vendor
tree. Verify native account creation and real authenticated requests when
changing runtime linkage; do not suppress DAV failures or disable security to
make tests pass.
