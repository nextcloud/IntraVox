#!/usr/bin/env bash
# Local development only. Never point this at a production Nextcloud.
set -euo pipefail
cd "$(dirname "$0")/../.."
ROOT="$PWD"
NC="$ROOT/.runtime/nextcloud"
VERSION=32.0.15
umask 077
mkdir -p .runtime/downloads generated-artifacts

if [ ! -f "$NC/version.php" ]; then
    ARCHIVE="nextcloud-${VERSION}.tar.bz2"
    URL="https://download.nextcloud.com/server/releases/$ARCHIVE"
    # Resume interrupted first-time downloads rather than starting over after a
    # setup timeout. The checksum below still verifies the complete archive.
    curl --fail --location --retry 2 --continue-at - --connect-timeout 30 \
        --max-time 1200 --silent --show-error "$URL" -o ".runtime/downloads/$ARCHIVE"
    curl --fail --location --retry 2 "$URL.sha256" -o ".runtime/downloads/$ARCHIVE.sha256"
    # The upstream checksum file also lists release metadata. Verify only our archive.
    (cd .runtime/downloads; grep " ${ARCHIVE}\$" "$ARCHIVE.sha256" | sha256sum -c -)
    tar -xjf ".runtime/downloads/$ARCHIVE" -C .runtime
    rm ".runtime/downloads/$ARCHIVE" ".runtime/downloads/$ARCHIVE.sha256"
fi

if [ ! -f vendor/autoload.php ] || [ ! -f node_modules/webpack/bin/webpack.js ]; then
    echo "Install app dependencies first: composer install && npm ci" >&2
    exit 1
fi

mkdir -p "$NC/custom_apps" "$ROOT/.runtime/data"
APP="$NC/custom_apps/intravox"
# Upgrade the initial local whole-repository symlink to a small runtime tree,
# so freshly compiled bundles can stay outside Git without changing source.
if [ -L "$APP" ] && [ "$(readlink -f "$APP")" = "$ROOT" ]; then
    unlink "$APP"
fi
if [ -e "$APP" ] && [ ! -f "$APP/.replit-managed" ]; then
    echo "Refusing to replace an unmanaged custom_apps/intravox." >&2
    exit 1
fi
mkdir -p "$APP/js"
touch "$APP/.replit-managed"
for entry in appinfo lib templates css img l10n demo-data vendor; do
    if [ ! -e "$APP/$entry" ]; then
        ln -s "$ROOT/$entry" "$APP/$entry"
    elif [ "$(readlink -f "$APP/$entry")" != "$ROOT/$entry" ]; then
        echo "Refusing to replace an unmanaged app path: $entry." >&2
        exit 1
    fi
done
# Serve current dependencies (including security updates), not stale release
# bundles, while keeping all generated output out of the source checkout.
npm run build -- --output-path "$APP/js"
cp scripts/replit/nextcloud.config.php "$NC/config/replit.config.php"

# Complete installation before exposing any HTTP listener. The initial random
# password is never printed, saved separately, or placed on a process command line.
php -d memory_limit=512M scripts/replit/install.php
occ() { php -d memory_limit=512M "$NC/occ" "$@"; }
if ! occ app:list --output=json | php -r '$a=json_decode(stream_get_contents(STDIN),true,512,JSON_THROW_ON_ERROR); exit(isset($a["enabled"]["groupfolders"]) || isset($a["disabled"]["groupfolders"]) ? 0 : 1);'; then
    # Nextcloud's installer chooses a compatible release and verifies its signature.
    occ app:install groupfolders
fi
occ app:enable groupfolders
occ app:enable intravox
if [ "$(occ config:app:get intravox admin_access_provisioned)" != true ]; then
    # Use the app's first-install provisioning, without adding demo content.
    # Its marker prevents subsequent runs from resetting administrator choices.
    occ intravox:setup --skip-demo --no-interaction
fi
occ config:system:set defaultapp --value=intravox
echo "Ready. Set your own admin password in the Shell:"
echo "  php .runtime/nextcloud/occ user:resetpassword admin"
echo "Then start the Run IntraVox workflow."
