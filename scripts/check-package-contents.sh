#!/bin/bash
#
# check-package-contents.sh — validate a release tarball before it ships.
#
# Usage: scripts/check-package-contents.sh <tarball>
#
# Why this exists: lib/AppInfo/Application.php loads vendor/autoload.php behind a
# file_exists() guard. A tarball built without vendor/ therefore fails SILENTLY —
# enshrined/svg-sanitize and lsolesen/pel are simply absent, and the first SVG
# upload fatals on a missing class (MediaSanitizer catches \Exception, but a
# missing class throws \Error). intravox-2.2.0.tar.gz shipped exactly this way:
# 906 entries, 0 under vendor/.
#
# The mirror-image failure is just as bad: shipping the dev tree, which drops
# phpunit and mockery into every end-user install. Build vendor/ with
# `composer install --no-dev` and this script proves both properties.

set -euo pipefail

TARBALL="${1:-}"

if [ -z "$TARBALL" ]; then
    echo "Usage: $0 <tarball>" >&2
    exit 2
fi

if [ ! -f "$TARBALL" ]; then
    echo "✗ Not a file: $TARBALL" >&2
    exit 2
fi

# The package root is the single top-level directory inside the tarball.
LISTING=$(tar -tzf "$TARBALL")
ROOT="${LISTING%%/*}"

if [ -z "$ROOT" ]; then
    echo "✗ Could not determine package root in $TARBALL" >&2
    exit 2
fi

# The folder inside the tarball must be the plain app id. Nextcloud unpacks it
# straight into apps/ and derives the app id from the folder name, so anything
# else — a version suffix in particular — makes the App Store refuse the upload:
# "No possible app folder found. App folder must contain only lowercase ASCII
# characters or underscores". 2.3.2 shipped as `intravox-2.3.2` and was rejected
# on submission; nothing here caught it, because this check did not exist.
if [ "$ROOT" != "intravox" ]; then
    echo "✗ Package root is '$ROOT', must be exactly 'intravox'" >&2
    echo "  Nextcloud derives the app id from this folder; a version suffix" >&2
    echo "  is rejected by the App Store. The tarball FILE keeps the version." >&2
    exit 1
fi

FAILURES=0

fail() {
    echo "  ✗ $1" >&2
    FAILURES=$((FAILURES + 1))
}

pass() {
    echo "  ✓ $1"
}

# require_exact <path-inside-package> <description>
require_exact() {
    # A pipe + grep -q can SIGPIPE printf on large listings under pipefail.
    if grep -Fqx "${ROOT}/$1" <<< "$LISTING"; then
        pass "$2"
    else
        fail "missing $2 (${ROOT}/$1)"
    fi
}

# require_prefix <path-prefix-inside-package> <description>
require_prefix() {
    if grep -q "^${ROOT}/$1" <<< "$LISTING"; then
        pass "$2"
    else
        fail "missing $2 (${ROOT}/$1...)"
    fi
}

# forbid_prefix <path-prefix-inside-package> <description>
forbid_prefix() {
    if grep -q "^${ROOT}/$1" <<< "$LISTING"; then
        fail "$2 must not ship (${ROOT}/$1...)"
        grep -m3 "^${ROOT}/$1" <<< "$LISTING" | sed 's/^/      /' >&2
    else
        pass "no $2"
    fi
}

echo "Checking $TARBALL (package root: $ROOT)"

echo "App structure:"
require_exact "appinfo/info.xml"              "app metadata"
require_exact "lib/AppInfo/Application.php"   "application bootstrap"
require_exact "js/entrypoints.json"          "webpack entrypoint manifest"
require_exact "css/intravox-main.css"        "compiled Sovereign theme"

echo "Composer dependencies:"
require_exact "vendor/autoload.php"                          "composer autoloader"
require_prefix "vendor/enshrined/svg-sanitize/"              "enshrined/svg-sanitize (SVG upload)"
require_prefix "vendor/lsolesen/pel/"                        "lsolesen/pel (EXIF)"

echo "Dev dependencies (must be absent):"
forbid_prefix "vendor/phpunit/"  "phpunit"
forbid_prefix "vendor/mockery/"  "mockery"
forbid_prefix "vendor/bin/"      "vendor binaries"
forbid_prefix "vendor/nextcloud/ocp/" "development OCP stubs"
forbid_prefix "vendor/phpstan/" "static-analysis tooling"

echo "Build tooling (must be absent):"
# scripts/ is build-time only -- nothing under lib/, appinfo/ or templates/
# references it at runtime. It matters beyond tidiness: run-contract-tests.sh and
# run-integration-tests.sh carry the dev server's hostname, IP and SSH user as
# defaults, and this tarball goes to the public App Store.
forbid_prefix "scripts/" "build scripts"
for path in .git/ node_modules/ .runtime/ .local/ .agents/ attached_assets/ \
    generated-artifacts/ tests/ src/ coverage/; do
    forbid_prefix "$path" "development-only $path"
done
for path in .replit Jenkinsfile .env; do
    forbid_prefix "$path" "local configuration $path"
done
VERBOSE_LISTING=$(tar -tvzf "$TARBALL")
if grep -q '^l' <<< "$VERBOSE_LISTING"; then
    fail "symlinks must not ship"
else
    pass "no symlinks"
fi
if grep -qE '\.map$' <<< "$LISTING"; then
    fail "source maps must not ship"
else
    pass "no source maps"
fi

echo "Infrastructure details (must be absent):"
# Belt and braces: even if something else drags a file in, the dev host must
# never appear in a published package. Stream the whole archive through grep in
# one pass -- passing a file list to tar breaks on long argument lists and, worse,
# fails silently when a name does not match.
LEAKED="$(tar -xzOf "$TARBALL" 2>/dev/null \
    | grep -aohE 'dev\.rikdekker\.nl|178\.63\.205\.[0-9]{1,3}' | sort -u | head -3 || true)"
if [ -n "$LEAKED" ]; then
    fail "internal host/IP present in tarball"
    printf '%s\n' "$LEAKED" | sed 's/^/      /' >&2
else
    pass "no internal hostnames or IPs"
fi

echo "Secrets (must be absent):"
if grep -qE "\.(key|pem|csr|crt)$" <<< "$LISTING"; then
    fail "key/certificate material present in tarball"
    grep -m3 -E "\.(key|pem|csr|crt)$" <<< "$LISTING" | sed 's/^/      /' >&2
else
    pass "no key/certificate material"
fi

echo
if [ "$FAILURES" -gt 0 ]; then
    echo "✗ Package check FAILED ($FAILURES problem(s))" >&2
    exit 1
fi

echo "✓ Package check passed"
