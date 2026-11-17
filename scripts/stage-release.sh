#!/usr/bin/env bash
# Build a credential-free, unsigned Nextcloud package from a positive allowlist.
set -euo pipefail
SOURCE="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
OUTPUT="$SOURCE/build"
STAGE="$OUTPUT/intravox"
VERSION="$(node -p 'require(process.argv[1]).version' "$SOURCE/package.json")"

test -f "$SOURCE/js/entrypoints.json" || { echo "Run npm run build first." >&2; exit 1; }
node "$SOURCE/scripts/check-sovereign-bundle.cjs"
mkdir -p "$OUTPUT"
# Only this disposable staging directory is ever removed, never the source app.
rm -rf -- "$STAGE"
mkdir -p "$STAGE"
for item in appinfo lib templates css js img l10n demo-data LICENSE composer.json composer.lock openapi.json; do
    test -e "$SOURCE/$item" || { echo "Required app content missing: $item" >&2; exit 1; }
    cp -a "$SOURCE/$item" "$STAGE/"
done

# No local vendor tree is copied: dev tools and test-only dependencies stay out.
composer install --working-dir="$STAGE" --no-dev --prefer-dist \
    --no-interaction --no-progress --classmap-authoritative

# Runtime, source maps and tooling are never needed in the installable app.
find "$STAGE" -type f \( -name '*.map' -o -name '.DS_Store' \) -delete
if find "$STAGE" -type l -print -quit | grep -q .; then
    echo "Release contains a symlink; refusing to package it." >&2
    exit 1
fi
TARBALL="$OUTPUT/intravox-$VERSION.tar.gz"
tar -czf "$TARBALL" -C "$OUTPUT" intravox
bash "$SOURCE/scripts/check-package-contents.sh" "$TARBALL"
printf '\nUnsigned staging folder: %s\nUnsigned archive: %s\n' "$STAGE" "$TARBALL"
echo "Signing is a separate operation using trusted external credentials."
