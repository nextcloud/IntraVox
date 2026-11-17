#!/usr/bin/env bash
# Exercise large listings: grep -q must not turn SIGPIPE into false results.
set -euo pipefail
ROOT="$(cd "$(dirname "$0")/.." && pwd)"
TMP="$(mktemp -d)"
trap 'rm -rf "$TMP"' EXIT
APP="$TMP/intravox"
mkdir -p "$APP"/{appinfo,lib/AppInfo,lib/Command,demo-data/sovereign-365/en,js,css,vendor/enshrined/svg-sanitize,vendor/lsolesen/pel}
for file in appinfo/info.xml lib/AppInfo/Application.php lib/Command/SyncSovereignPortalCommand.php \
    demo-data/sovereign-365/en/home.json demo-data/sovereign-365/en/navigation.json demo-data/sovereign-365/en/footer.json js/entrypoints.json \
    css/intravox-main.css vendor/autoload.php vendor/enshrined/svg-sanitize/fixture.php \
    vendor/lsolesen/pel/fixture.php; do
    : > "$APP/$file"
done
for ((i=0;i<3000;i++)); do : > "$APP/lib/large-listing-fixture-$i.php"; done
tar -czf "$TMP/good.tar.gz" -C "$TMP" intravox
bash "$ROOT/scripts/check-package-contents.sh" "$TMP/good.tar.gz" > "$TMP/good.log" 2>&1 \
    || { cat "$TMP/good.log"; exit 1; }
printf '{"url":"https://example.replit.dev"}' > "$APP/demo-data/sovereign-365/en/home.json"
tar -czf "$TMP/sandbox-leak.tar.gz" -C "$TMP" intravox
if bash "$ROOT/scripts/check-package-contents.sh" "$TMP/sandbox-leak.tar.gz" > "$TMP/sandbox-leak.log" 2>&1; then
    echo "Sandbox reference in the Gold Master bundle was accepted." >&2
    exit 1
fi
grep -q 'Sandbox reference in Gold Master bundle' "$TMP/sandbox-leak.log"
: > "$APP/demo-data/sovereign-365/en/home.json"
mkdir -p "$APP/vendor/phpunit"
: > "$APP/vendor/phpunit/forbidden.php"
# Put forbidden entries first, where grep -q used to make printf SIGPIPE.
tar -czf "$TMP/bad.tar.gz" -C "$TMP" intravox/vendor/phpunit intravox
if bash "$ROOT/scripts/check-package-contents.sh" "$TMP/bad.tar.gz" > "$TMP/bad.log" 2>&1; then
    echo "Forbidden development files were accepted." >&2
    exit 1
fi
grep -q 'phpunit must not ship' "$TMP/bad.log"
rm -rf "$APP/vendor/phpunit"
mkdir -p "$APP/generated-artifacts/sovereign-qa"
: > "$APP/generated-artifacts/sovereign-qa/private-review.html"
tar -czf "$TMP/qa-leak.tar.gz" -C "$TMP" intravox
if bash "$ROOT/scripts/check-package-contents.sh" "$TMP/qa-leak.tar.gz" > "$TMP/qa-leak.log" 2>&1; then
    echo "Private generated QA artifacts were accepted." >&2
    exit 1
fi
grep -q 'generated-artifacts/ must not ship' "$TMP/qa-leak.log"
echo "PASS large valid archives accepted; early forbidden entries and generated QA artifacts rejected."
