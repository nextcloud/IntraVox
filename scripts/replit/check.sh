#!/usr/bin/env bash
# Read-only HTTP checks for the running local development instance.
set -euo pipefail
cd "$(dirname "$0")/../.."
BASE="${1:-https://${REPLIT_DEV_DOMAIN:?Set REPLIT_DEV_DOMAIN or pass a base URL}}"
BASE="${BASE%/}"
TMP="$(mktemp -d)"
trap 'rm -rf "$TMP"' EXIT

curl --fail --silent --show-error "$BASE/status.php" > "$TMP/status.json"
php -r '
$s=json_decode(file_get_contents($argv[1]),true,512,JSON_THROW_ON_ERROR);
if (($s["installed"]??false)!==true || ($s["maintenance"]??true)!==false || !str_starts_with($s["versionstring"]??"", "32.")) {
    fwrite(STDERR, "Nextcloud is not ready.\n"); exit(1);
}
echo "Nextcloud reports installed and ready.\n";
' "$TMP/status.json"

curl --fail --silent --show-error "$BASE/index.php/login" > "$TMP/login.html"
grep -q 'name="password"\|id="user"\|id="initial-state-core-login' "$TMP/login.html"
echo "Login page is available."
# IntraVox intentionally serves a public shell for share links. Page content,
# not the shell HTML, must be protected when no share token is supplied.
curl --fail --silent --show-error "$BASE/index.php/apps/intravox/" -o /dev/null
code="$(curl --silent --show-error -o /dev/null -w '%{http_code}' "$BASE/index.php/apps/intravox/api/pages")"
[ "$code" = 401 ] || { echo "Expected unauthenticated page access to be rejected; got $code." >&2; exit 1; }
echo "IntraVox loads; private page content requires sign-in."
ASSET="$(php -r '$m=json_decode(file_get_contents("js/entrypoints.json"),true,512,JSON_THROW_ON_ERROR); foreach ($m["main"] as $a) { if (str_ends_with($a,".js")) { echo $a; break; } }')"
[ -n "$ASSET" ] || { echo "Build manifest has no main JavaScript asset." >&2; exit 1; }
curl --fail --silent --show-error "$BASE/custom_apps/intravox/$ASSET" -o /dev/null
echo "IntraVox JavaScript is served."

for path in \
    /config/config.php /data/owncloud.db /lib/base.php /cron.php \
    /custom_apps/intravox/composer.json \
    /custom_apps/intravox/vendor/composer/installed.json \
    /custom_apps/intravox/.git/config \
    /custom_apps/intravox/scripts/replit/install.php \
    /custom_apps/intravox/src/main.js \
    /custom_apps/intravox/node_modules/vue/dist/vue.runtime.esm-bundler.js \
    /core/../config/config.php /core/%2e%2e/config/config.php; do
    # The preview proxy may normalize dot segments with a redirect first.
    code="$(curl --path-as-is --location --max-redirs 3 --silent --show-error -o /dev/null -w '%{http_code}' "$BASE$path")"
    [ "$code" = 404 ] || { echo "Private path $path returned $code, expected 404." >&2; exit 1; }
done
echo "Private runtime files, source, and traversal paths are blocked."
