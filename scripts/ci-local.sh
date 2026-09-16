#!/bin/bash
# Run the whole CI gate locally, in the order .github/workflows/ci.yml runs it.
#
# Why this exists: 2.7.1 shipped with a red pipeline twice in a row because the
# checks were run as a subset. `npm run build` covers the frontend guards, so a
# green build feels like a green pipeline -- but prebuild does NOT run phpstan
# or the PHP tests, and that is exactly where both failures were:
#
#   1. phpstan caught `Node::nodeExists()` (Folder::get() is typed as Node).
#      Runtime verification on dev could never surface it: on a real install
#      that path IS a Folder.
#   2. the file-budget ratchet then tripped on the nine lines the fix added.
#
# It also runs the Integration suite when the dev server is reachable, because
# the unit suite alone cannot answer "does this work on a real install" -- the
# PageService split passed 1302 unit tests while shipping an app with no pages.
#
# Run this before every push on a release branch. It is the only check that
# claims to be complete.
#
# Usage: ./scripts/ci-local.sh [--fix]
#   --fix  re-record the file-budget ratchet if it is the only thing failing
# Env:
#   SKIP_INTEGRATION=1  skip the integration suite (reported, never silent)
#   INTRAVOX_DEV_SSH    user@host of the dev server; unset means the
#                       integration step is skipped (and says so)

set -uo pipefail
cd "$(dirname "${BASH_SOURCE[0]}")/.."

FIX=0
[ "${1:-}" = "--fix" ] && FIX=1

if [ -t 1 ]; then
  RED=$'\033[0;31m'; GREEN=$'\033[0;32m'; YELLOW=$'\033[1;33m'; NC=$'\033[0m'
else
  RED=''; GREEN=''; YELLOW=''; NC=''
fi
FAILED=()

step() {
  local name="$1"; shift
  printf '  %-34s' "$name"
  if out=$("$@" 2>&1); then
    printf '%bok%b\n' "$GREEN" "$NC"
  else
    printf '%bFAILED%b\n' "$RED" "$NC"
    FAILED+=("$name")
    echo "$out" | tail -20 | sed 's/^/      /'
  fi
}

# CI pins PHP 8.2 — the floor composer.json claims. A local 8.5 accepts syntax
# that CI rejects, so say which one this run actually proves.
PHP_VERSION=$(php -r 'echo PHP_MAJOR_VERSION . "." . PHP_MINOR_VERSION;' 2>/dev/null || echo '?')
echo "Running the CI gate locally (PHP ${PHP_VERSION}; CI pins 8.2)"
echo

echo "PHP unit tests + lint"
step "syntax-check all PHP sources" bash -c "find lib tests -name '*.php' -print0 | xargs -0 -n1 -P4 php -l > /dev/null"
step "unit tests" composer run test:unit
step "static analysis (phpstan)" composer run lint:phpstan

echo
echo "Frontend guards"
step "version sync" node scripts/sync-version.js --check
step "import consistency" npm run --silent lint:imports
step "l10n source strings" npm run --silent lint:l10n
step "facet serialization" npm run --silent lint:facets
step "line endings" npm run --silent lint:eol
step "file budgets" npm run --silent lint:budgets
step "security markers" npm run --silent lint:security
step "route table" npm run --silent lint:routes
step "openapi coverage" npm run --silent lint:openapi

echo
echo "Integration (real Nextcloud + groupfolders)"
# The unit suite stubs OCP away, so it cannot tell you whether the app can find
# a page on a real install. That gap is not hypothetical: the PageService split
# passed 1302 green unit tests while shipping an app with no pages at all,
# because FolderContext resolved the user's home folder instead of the mounted
# IntraVox groupfolder. tests/Integration/GroupFolderResolutionTest.php exists
# precisely to catch that, and it was never run.
#
# It needs a live server, so it cannot be a hard gate on a laptop with no SSH
# access. It IS a hard gate whenever the server is reachable, and skipping is
# reported loudly rather than silently passing.
if [ "${SKIP_INTEGRATION:-0}" = "1" ]; then
  printf '  %-34s%bskipped%b (SKIP_INTEGRATION=1)\n' "integration suite" "$YELLOW" "$NC"
  SKIPPED_INTEGRATION=1
elif ssh -o ConnectTimeout=8 -o BatchMode=yes "${INTRAVOX_DEV_SSH:-}" true >/dev/null 2>&1; then
  step "integration suite" ./scripts/run-integration-tests.sh --no-deploy
else
  printf '  %-34s%bskipped%b (dev server unreachable)\n' "integration suite" "$YELLOW" "$NC"
  SKIPPED_INTEGRATION=1
fi

echo
if [ ${#FAILED[@]} -eq 0 ]; then
  if [ "${SKIPPED_INTEGRATION:-0}" = "1" ]; then
    printf '%b✓ unit gate green%b — but the integration suite did NOT run.\n' "$YELLOW" "$NC"
    echo "  Green here does not mean the app works on a real install."
    echo "  Run ./scripts/run-integration-tests.sh before merging to main."
    exit 0
  fi
  printf '%b✓ CI gate green%b — safe to push\n' "$GREEN" "$NC"
  exit 0
fi

printf '%b✗ %d check(s) failed:%b %s\n' "$RED" "${#FAILED[@]}" "$NC" "${FAILED[*]}"

if [ "$FIX" = "1" ] && [ "${#FAILED[@]}" = "1" ] && [ "${FAILED[0]}" = "file budgets" ]; then
  echo
  printf '%b--fix:%b re-recording the file-budget ratchet\n' "$YELLOW" "$NC"
  npm run --silent lint:budgets -- --update && \
    echo "  done — review the .file-budgets.json diff, then re-run"
  exit 1
fi

if printf '%s\n' "${FAILED[@]}" | grep -qx 'file budgets'; then
  echo
  echo "  file budgets: extract code rather than raising the budget. If the"
  echo "  growth is genuinely unavoidable: ./scripts/ci-local.sh --fix"
fi

exit 1
