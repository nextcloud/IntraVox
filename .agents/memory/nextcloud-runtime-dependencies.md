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

Composer autoloaders generated from the same lockfile can share an initialization
class name even when one tree is production-only. Unregistering one loader does
not remove that PHP class; loading both trees can still fatal.

**Why:** Running development PHPUnit alongside a separately installed production
app exposed this collision before any integration assertions ran.

**How to apply:** When combining the trees in one isolated test process, regenerate
only the disposable app's loader with a distinct Composer autoloader suffix.
Never regenerate or overlay the owner's installed dependencies to accommodate tests.

A copied Nextcloud code tree needs its own explicit installed-app search paths.
An app-enable command may fetch the published app if the custom-app directory is
not configured, testing the wrong code while apparently enabling it successfully.

**Why:** A fresh isolated install initially selected a published app instead of
the workspace app.

**How to apply:** Configure local app paths and disable app-store downloads before
enabling fixture apps. Assert the resolved app path and dependency provenance in
the isolated suite.
