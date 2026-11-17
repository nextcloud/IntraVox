#!/usr/bin/env bash
# A fresh local SQLite install per run. Never deploy to or test owner content.
set -euo pipefail
cd "$(dirname "${BASH_SOURCE[0]}")/.."
ROOT="$PWD"
SOURCE="$ROOT/.runtime/nextcloud"
if [ "${1:-}" = "--no-deploy" ]; then shift; fi # old CI caller; never deploy
if [ ! -f "$SOURCE/lib/base.php" ] || [ ! -d "$SOURCE/custom_apps/groupfolders" ] ||
   [ ! -f "$SOURCE/custom_apps/intravox/vendor/autoload.php" ] ||
   [ ! -x vendor/bin/phpunit ]; then
    echo "Install development dependencies and run scripts/replit/setup.sh first." >&2
    exit 2
fi
umask 077
mkdir -p "$ROOT/.runtime"
SANDBOX="$(mktemp -d "$ROOT/.runtime/integration-XXXXXXXX")"
trap 'rm -rf -- "$SANDBOX"' EXIT
trap 'exit 130' INT
trap 'exit 143' TERM
export NEXTCLOUD_ROOT="$SANDBOX/nextcloud"
export INTRAVOX_TEST_SANDBOX="$SANDBOX"
export INTRAVOX_TEST_TOKEN
INTRAVOX_TEST_TOKEN="$(php -r 'echo bin2hex(random_bytes(32));')"
printf '%s' "$INTRAVOX_TEST_TOKEN" > "$SANDBOX/.disposable"
mkdir -p "$NEXTCLOUD_ROOT/config" "$NEXTCLOUD_ROOT/custom_apps/intravox"
echo "==> Creating disposable Nextcloud (no owner config or data copied)"
# Copy code only, not config, databases, caches, installed app, or owner files.
tar -C "$SOURCE" --exclude='./config' --exclude='./data' --exclude='./custom_apps' \
    --exclude='./updater' -cf - . | tar -C "$NEXTCLOUD_ROOT" -xf -
cp -a "$SOURCE/custom_apps/groupfolders" "$NEXTCLOUD_ROOT/custom_apps/"
APP="$NEXTCLOUD_ROOT/custom_apps/intravox"
for entry in appinfo lib templates css img l10n demo-data; do
    ln -s "$ROOT/$entry" "$APP/$entry"
done
# Reuse only the installed production dependencies, never the dev vendor tree.
cp -a "$SOURCE/custom_apps/intravox/vendor" "$APP/"
cp composer.json composer.lock "$APP/"
# Composer uses the same lock-derived init class in dev and --no-dev trees.
# Give ONLY this disposable production copy a distinct loader name.
composer --working-dir="$APP" --no-plugins config autoloader-suffix IntraVoxDisposableRuntime
composer --working-dir="$APP" --no-plugins dump-autoload --no-dev --no-scripts --quiet
cp scripts/integration/nextcloud.config.php "$NEXTCLOUD_ROOT/config/integration.config.php"
php -d memory_limit=512M scripts/integration/install.php
occ() { php -d memory_limit=512M "$NEXTCLOUD_ROOT/occ" "$@"; }
occ app:enable groupfolders
occ app:enable intravox
occ intravox:setup --skip-demo --no-interaction
php -d memory_limit=512M scripts/integration/fixtures.php
echo "==> Running integration tests against the disposable dataset"
php -d memory_limit=512M vendor/bin/phpunit -c phpunit-integration.xml "$@"
