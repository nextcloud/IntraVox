#!/usr/bin/env bash
#
# Server-side smoke test against a deployed IntraVox.
#
# This is the automatable half of docs/internal/TESTPLAN-3.0.md: everything that
# can be proven without a browser. It exists because the 3.0 refactor shipped an
# app that was completely empty while 1321 unit tests stayed green -- twice. The
# checks below are the ones that would have caught that in seconds.
#
# What it does NOT do: click anything. Sections C through J of the test plan
# need a human in a browser, and no amount of curl replaces that. Run this
# first; it either hands you a green baseline to start clicking from, or it
# tells you not to bother yet.
#
# Usage:
#   scripts/smoke-test-dev.sh                 # against dev.rikdekker.nl
#   scripts/smoke-test-dev.sh --expect 3.0    # also assert the deployed version
#
# Env:
#   INTRAVOX_DEV_SSH        default rik@178.63.205.103
#   INTRAVOX_DEV_CONTAINER  default nc-dev
#   INTRAVOX_DEV_URL        default https://dev.rikdekker.nl
set -uo pipefail

SSH_HOST="${INTRAVOX_DEV_SSH:-rik@178.63.205.103}"
CONTAINER="${INTRAVOX_DEV_CONTAINER:-nc-dev}"
BASE_URL="${INTRAVOX_DEV_URL:-https://dev.rikdekker.nl}"
APP_DIR="/var/www/html/custom_apps/intravox"

EXPECT_VERSION=""
while [ $# -gt 0 ]; do
    case "$1" in
        --expect) EXPECT_VERSION="${2:-}"; shift 2 ;;
        *) echo "unknown argument: $1" >&2; exit 2 ;;
    esac
done

GREEN='\033[0;32m'; RED='\033[0;31m'; YELLOW='\033[0;33m'; NC='\033[0m'
FAILED=()
WARNED=()

# Run a check. $1 = label, rest = command. The command's own output is only
# shown when it fails -- a green run should be readable at a glance.
check() {
    local name="$1"; shift
    printf '  %-42s' "$name"
    local out
    if out=$("$@" 2>&1); then
        printf '%bok%b\n' "$GREEN" "$NC"
        return 0
    fi
    printf '%bFAILED%b\n' "$RED" "$NC"
    FAILED+=("$name")
    echo "$out" | tail -8 | sed 's/^/      /'
    return 1
}

warn() {
    printf '  %-42s%bwarn%b\n' "$1" "$YELLOW" "$NC"
    WARNED+=("$1")
    [ -n "${2:-}" ] && echo "$2" | sed 's/^/      /'
}

# The host prints a login banner on stderr for every ssh invocation, so stderr
# is dropped here rather than in each caller -- otherwise "Authorized access
# only" shows up as if it were command output.
occ() { ssh -o ConnectTimeout=10 "$SSH_HOST" \
    "sudo docker exec -u www-data ${CONTAINER} php /var/www/html/occ $*" 2>/dev/null; }

incontainer() { ssh -o ConnectTimeout=10 "$SSH_HOST" \
    "sudo docker exec ${CONTAINER} sh -c '$*'" 2>/dev/null; }

# Not via incontainer(): that wraps the command in sh -c '...', and the quotes
# this needs collide with the wrapper's own. Run sed directly in the container.
deployed_version() {
    ssh -o ConnectTimeout=10 "$SSH_HOST" \
        "sudo docker exec ${CONTAINER} sed -n 's|.*<version>\\(.*\\)</version>.*|\\1|p' ${APP_DIR}/appinfo/info.xml" \
        2>/dev/null | head -1
}

http_code() { curl -s -o /dev/null -m 20 -w '%{http_code}' "$@"; }

echo "Smoke-testing ${BASE_URL} (container ${CONTAINER})"
echo

# ---------------------------------------------------------------- environment
echo "Environment"

check "server reachable over ssh" \
    ssh -o ConnectTimeout=10 -o BatchMode=yes "$SSH_HOST" true

NC_VERSION=$(incontainer 'php -r "include \"/var/www/html/version.php\"; echo \$OC_VersionString;"')
PHP_VERSION=$(incontainer 'php -r "echo PHP_VERSION;"')
printf '  %-42s%s\n' "Nextcloud" "${NC_VERSION:-unknown}"
printf '  %-42s%s\n' "PHP" "${PHP_VERSION:-unknown}"

APPS=$(occ app:list 2>/dev/null)
for app in intravox groupfolders circles; do
    line=$(printf '%s\n' "$APPS" | grep -E "^  - ${app}:" | head -1 | sed 's/^  - //')
    if [ -n "$line" ]; then
        printf '  %-42s%s\n' "$app" "$line"
    else
        printf '  %-42s%bnot enabled%b\n' "$app" "$RED" "$NC"
        FAILED+=("$app not enabled")
    fi
done

if [ -n "$EXPECT_VERSION" ]; then
    GOT_VERSION=$(deployed_version)
    if [ "$GOT_VERSION" = "$EXPECT_VERSION" ]; then
        printf '  %-42s%bok%b\n' "version is ${EXPECT_VERSION}" "$GREEN" "$NC"
    else
        printf '  %-42s%bFAILED%b (found %s)\n' "version is ${EXPECT_VERSION}" \
            "$RED" "$NC" "${GOT_VERSION:-nothing}"
        FAILED+=("version ${GOT_VERSION:-unknown}")
    fi
fi
echo

# --------------------------------------------------------- A. does it resolve
# The bug-A class. FolderContext returning the user home instead of the mounted
# groupfolder produced a silent, empty app: HTTP 200, no log line, no content.
echo "A. Folder resolution (bug A)"

check "IntraVox groupfolder is mounted" \
    incontainer "ls -d /var/www/html/data/__groupfolders"

check "app dir holds the split services" \
    incontainer "ls -d ${APP_DIR}/lib/Service/Tree ${APP_DIR}/lib/Service/Write"
echo

# ------------------------------------------------------------ B. the CLI path
# The bug-B class: FolderContext captured the user id at construction, so occ
# (which sets the user during execute()) saw an empty string and every lookup
# threw "User not logged in". Every occ command that touches pages was dead.
echo "B. CLI path (bug B)"

REINDEX=$(occ intravox:reindex 2>&1)
INDEXED=$(printf '%s\n' "$REINDEX" | grep -oE 'Indexed [0-9]+' | grep -oE '[0-9]+' | head -1)

if printf '%s\n' "$REINDEX" | grep -q 'User not logged in'; then
    printf '  %-42s%bFAILED%b\n' "occ intravox:reindex" "$RED" "$NC"
    echo "      'User not logged in' -- this is bug B, and it is a blocker"
    FAILED+=("occ reindex: bug B")
elif [ -z "$INDEXED" ]; then
    printf '  %-42s%bFAILED%b\n' "occ intravox:reindex" "$RED" "$NC"
    printf '%s\n' "$REINDEX" | tail -5 | sed 's/^/      /'
    FAILED+=("occ reindex: no count")
elif [ "$INDEXED" -eq 0 ]; then
    printf '  %-42s%bFAILED%b\n' "occ intravox:reindex" "$RED" "$NC"
    echo "      indexed 0 pages -- the CLI cannot see the content folder"
    FAILED+=("occ reindex: 0 pages")
else
    printf '  %-42s%bok%b (%s pages)\n' "occ intravox:reindex" "$GREEN" "$NC" "$INDEXED"
fi
echo

# ------------------------------------------------------------------- C. HTTP
echo "C. HTTP surface"

for path in "/status.php:200" "/apps/intravox/:200" "/login:302"; do
    url="${path%:*}"; want="${path##*:}"
    got=$(http_code "${BASE_URL}${url}")
    if [ "$got" = "$want" ]; then
        printf '  %-42s%bok%b (%s)\n' "GET ${url}" "$GREEN" "$NC" "$got"
    else
        printf '  %-42s%bFAILED%b (got %s, want %s)\n' "GET ${url}" "$RED" "$NC" "$got" "$want"
        FAILED+=("GET ${url}: ${got}")
    fi
done

# Maintenance mode makes every other check meaningless, so say so plainly.
if curl -s -m 20 "${BASE_URL}/status.php" | grep -q '"maintenance":true'; then
    warn "maintenance mode" "the instance is in maintenance mode; results below are not meaningful"
fi
echo

# ------------------------------------------------- D. the sabre/xml vendor trap
# run-integration-tests.sh overlays a DEV vendor/ when the container has no
# phpunit. That pulls in sabre/xml 4.x, which shadows the core's 2.x through
# PSR-4 and makes every /remote.php/dav/ request fatal. It has bitten twice.
echo "D. Vendor hygiene (sabre trap)"

VENDOR=$(incontainer "ls ${APP_DIR}/vendor/")
if printf '%s\n' "$VENDOR" | grep -qx 'sabre'; then
    printf '  %-42s%bFAILED%b\n' "app vendor/ is production-only" "$RED" "$NC"
    echo "      vendor/sabre is present: this shadows core sabre/xml and kills CalDAV."
    echo "      Fix: NO_AUTO_BUMP=1 ./deploy.sh hetzner"
    FAILED+=("dev vendor/ deployed")
else
    printf '  %-42s%bok%b\n' "app vendor/ is production-only" "$GREEN" "$NC"
fi

DAV=$(http_code -X PROPFIND "${BASE_URL}/remote.php/dav/")
if [ "$DAV" = "401" ]; then
    printf '  %-42s%bok%b (401)\n' "PROPFIND /remote.php/dav/" "$GREEN" "$NC"
else
    printf '  %-42s%bFAILED%b (got %s, want 401)\n' "PROPFIND /remote.php/dav/" "$RED" "$NC" "$DAV"
    [ "$DAV" = "500" ] && echo "      500 here is the sabre/xml shadow, not a Nextcloud bug"
    FAILED+=("DAV: ${DAV}")
fi
echo

# ------------------------------------------------------------------- E. logs
# Errors that mention the app, since the deploy. Anything else on the instance
# is somebody else's problem and should not fail this run.
echo "E. Log"

LOG_ERRORS=$(ssh -o ConnectTimeout=10 "$SSH_HOST" \
    "sudo docker exec -u www-data ${CONTAINER} tail -400 /var/www/html/data/nextcloud.log" 2>/dev/null \
    | python3 -c '
import sys, json
hits = []
for line in sys.stdin:
    try:
        d = json.loads(line)
    except Exception:
        continue
    if d.get("level", 0) < 3:
        continue
    blob = json.dumps(d).lower()
    if "intravox" in blob:
        hits.append((d.get("time"), str(d.get("message"))[:120]))
for t, m in hits[-5:]:
    print(f"{t}  {m}")
print(f"TOTAL {len(hits)}")
' 2>/dev/null)

TOTAL=$(printf '%s\n' "$LOG_ERRORS" | grep -oE 'TOTAL [0-9]+' | grep -oE '[0-9]+')
if [ "${TOTAL:-0}" -eq 0 ]; then
    printf '  %-42s%bok%b\n' "IntraVox errors in the log" "$GREEN" "$NC"
else
    printf '  %-42s%bFAILED%b (%s)\n' "IntraVox errors in the log" "$RED" "$NC" "$TOTAL"
    printf '%s\n' "$LOG_ERRORS" | grep -v '^TOTAL' | sed 's/^/      /'
    FAILED+=("${TOTAL} IntraVox log errors")
fi
echo

# ----------------------------------------------------------------- verdict
if [ ${#FAILED[@]} -eq 0 ]; then
    if [ ${#WARNED[@]} -gt 0 ]; then
        printf '%b✓ smoke test green%b, with warnings: %s\n' "$YELLOW" "$NC" "${WARNED[*]}"
    else
        printf '%b✓ smoke test green%b\n' "$GREEN" "$NC"
    fi
    echo
    echo "  This proves the app resolves its folder, the CLI works, the HTTP"
    echo "  surface answers and nothing is in the log. It proves nothing about"
    echo "  what the app DOES."
    echo
    echo "  Next: docs/internal/TESTPLAN-3.0.md, sections C through J, in a browser."
    exit 0
fi

printf '%b✗ %d check(s) failed:%b %s\n' "$RED" "${#FAILED[@]}" "$NC" "${FAILED[*]}"
echo
echo "  Do not start the manual round until these are green -- every one of them"
echo "  makes the browser tests misleading rather than merely incomplete."
exit 1
