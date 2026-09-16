#!/usr/bin/env bash
#
# Verify openapi.json against a RUNNING server.
#
# Everything else in the gate is static. check-openapi.js proves an operation
# exists; SpecMatchesHandlersTest proves the handler does not obviously
# contradict it. Neither can see the one thing that matters most to an
# integrator: whether the body that comes back actually matches the schema.
#
# The four defects that opened the 2.5 work all had a path, an operationId, tags
# and a 200. They passed every static rule while being wrong. This is the only
# check that would have caught them.
#
# TIER A — blocking. The /api/v1/* OCS surface plus /api/health: the routes an
# external integrator builds against, where a broken contract is someone else's
# outage. GET only; the write operations are declared so a generated client can
# call them, but fuzzing a POST against a live instance would create content.
#
# TIER B — reporting only. Every other documented GET. Wider, noisier, and run
# with --report so a failure is information rather than a gate.
#
# Usage:
#   scripts/run-contract-tests.sh                 # Tier A, blocking
#   scripts/run-contract-tests.sh --report        # Tier B, never fails the build
#   scripts/run-contract-tests.sh --setup         # (re)create the venv and exit
#
# The run clears its own address from Nextcloud's brute-force counter on exit.
# Without that, thousands of requests lock out everyone on the same network --
# the counter is per address, not per user.
set -euo pipefail

REPO_ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
SSH_HOST="${INTRAVOX_DEV_SSH:-}"
CONTAINER="${INTRAVOX_DEV_CONTAINER:-nc-dev}"
BASE_URL="${INTRAVOX_DEV_URL:-https://dev.rikdekker.nl}"
VENV="${INTRAVOX_CONTRACT_VENV:-$HOME/.cache/intravox-contract-venv}"

MODE="tier-a"
for arg in "$@"; do
    case "$arg" in
        --report) MODE="tier-b" ;;
        --setup)  MODE="setup" ;;
        *) echo "Unknown argument: $arg" >&2; exit 2 ;;
    esac
done

# ---------------------------------------------------------------------------
# The venv lives outside the repo and is created on demand. Schemathesis pulls
# in a large dependency tree; making it a devDependency of the app would put it
# in every developer's install for a check most of them never run.
# ---------------------------------------------------------------------------
ensure_venv() {
    if [ ! -x "$VENV/bin/schemathesis" ] && [ ! -x "$VENV/bin/st" ]; then
        echo "==> Creating contract-test venv at $VENV"
        python3 -m venv "$VENV"
        "$VENV/bin/pip" install --quiet --upgrade pip
        "$VENV/bin/pip" install --quiet schemathesis
    fi
    ST="$VENV/bin/st"
    [ -x "$ST" ] || ST="$VENV/bin/schemathesis"
    echo "==> $($ST --version 2>&1 | head -1)"
}

if [ "$MODE" = "setup" ]; then
    ensure_venv
    echo "    ready"
    exit 0
fi

ensure_venv

# ---------------------------------------------------------------------------
# Credentials. An app password, never the account password: this runs against a
# real instance and the token is passed to a fuzzer.
# ---------------------------------------------------------------------------
if [ -z "${INTRAVOX_CONTRACT_USER:-}" ] || [ -z "${INTRAVOX_CONTRACT_TOKEN:-}" ]; then
    cat >&2 <<'MSG'
✗ Missing credentials.

Set INTRAVOX_CONTRACT_USER and INTRAVOX_CONTRACT_TOKEN to a Nextcloud user and
an APP PASSWORD (Settings → Security → Create new app password). Not the account
password: this value is handed to a fuzzer that will retry it many times.

  export INTRAVOX_CONTRACT_USER=rik
  export INTRAVOX_CONTRACT_TOKEN=xxxxx-xxxxx-xxxxx-xxxxx-xxxxx

Revoke the app password when you are done.
MSG
    exit 2
fi

# ---------------------------------------------------------------------------
# The spec is written with two servers and a templated host. Schemathesis wants
# one concrete base url, so resolve it here rather than committing a dev
# hostname into openapi.json.
# ---------------------------------------------------------------------------
# ---------------------------------------------------------------------------
# Clear this address from Nextcloud's brute-force counter afterwards.
#
# A run makes thousands of requests, and the counter is keyed on the NETWORK the
# request came from, not on the user. So a contract run locks out anyone sharing
# that address — including whoever is working in the dev instance at the time,
# who then sees "Te veel aanvragen" on a page they had nothing to do with. That
# happened, which is why this exists.
#
# In a trap rather than at the end: an interrupted or failed run leaves the
# counter at its highest, so that is exactly when the reset matters most.
# Best-effort by design — no SSH, no reachable container, or a failed occ call
# must not turn a green contract run red.
reset_bruteforce() {
    local ip
    ip="$(curl -s --max-time 5 https://ifconfig.me 2>/dev/null || true)"
    [ -n "$ip" ] || return 0

    ssh -o ConnectTimeout=5 "$SSH_HOST" \
        "docker exec -u www-data ${CONTAINER} php occ security:bruteforce:reset ${ip}" \
        >/dev/null 2>&1 \
        && echo "==> Cleared brute-force counter for ${ip}" \
        || echo "⚠ Could not clear the brute-force counter for ${ip}. If dev starts answering 429, run:
    ssh ${SSH_HOST} 'docker exec -u www-data ${CONTAINER} php occ security:bruteforce:reset ${ip}'"
}

WORK="$(mktemp -d)"
trap 'rm -rf "$WORK"; reset_bruteforce' EXIT
SPEC="$WORK/openapi-resolved.json"

URL_FILE="$WORK/target-url"
TIER="$MODE" URL_OUT="$URL_FILE" python3 - "$REPO_ROOT/openapi.json" "$SPEC" "$BASE_URL" <<'PYEOF'
import json, sys, os

src, dst, base = sys.argv[1], sys.argv[2], sys.argv[3]
tier = os.environ["TIER"]
spec = json.load(open(src))

# One concrete server for the whole filtered document. Tier A is entirely OCS,
# so it gets the OCS mount; Tier B is everything else, which lives on the app
# mount. The two cannot share a run, and do not need to.
if tier == "tier-a":
    spec["servers"] = [{"url": base.rstrip("/") + "/ocs/v2.php/apps/intravox"}]
else:
    spec["servers"] = [{"url": base.rstrip("/") + "/apps/intravox"}]

VERBS = ("get", "post", "put", "delete", "patch", "head", "options")

def keep(path, verb, op):
    if verb != "get":
        return False                      # never fuzz a write against a live instance
    if tier == "tier-a":
        # An operation-level servers[] override marks the OCS surface, and that
        # list mirrors the 'ocs' block of appinfo/routes.php rather than an
        # editorial tag. Tag-based selection is what sent the first run at
        # /ocs/.../api/health, which is not mounted there.
        return "servers" in op
    return True

paths = {}
for p, item in spec["paths"].items():
    kept = {k: v for k, v in item.items() if k not in VERBS}
    for verb in VERBS:
        op = item.get(verb)
        if op and keep(p, verb, op):
            kept[verb] = op
    if any(v in kept for v in VERBS):
        paths[p] = kept

# Operation-level overrides would otherwise re-point individual calls back at
# the templated host the document ships with.
for _item in paths.values():
    for _op in _item.values():
        if isinstance(_op, dict):
            _op.pop("servers", None)

spec["paths"] = paths
json.dump(spec, open(dst, "w"))

n = sum(1 for p in paths.values() for v in p if v in VERBS)
print(f"    {n} operations selected for {tier}")
with open(os.environ["URL_OUT"], "w") as fh:
    fh.write(spec["servers"][0]["url"])
PYEOF

# ---------------------------------------------------------------------------
# Checks. Deliberately the three that answer "does the response match what we
# published", and not the whole suite: negative-data and security checks would
# report on Nextcloud's behaviour rather than on this spec's accuracy.
# ---------------------------------------------------------------------------
CHECKS="status_code_conformance,response_schema_conformance,content_type_conformance"

echo "==> Contract test against $(cat "$URL_FILE") (${MODE})"

# --rate-limit matters: this points a fuzzer at a live instance, and several of
# these endpoints are themselves rate limited. Without it the run trips the very
# 429s it would then report as contract violations.
set +e
"$ST" run "$SPEC" \
    --url "$(cat "$URL_FILE")" \
    --checks "$CHECKS" \
    --auth "${INTRAVOX_CONTRACT_USER}:${INTRAVOX_CONTRACT_TOKEN}" \
    --header "OCS-APIRequest: true" \
    --max-examples "${INTRAVOX_CONTRACT_EXAMPLES:-20}" \
    --rate-limit "${INTRAVOX_CONTRACT_RATE:-10/s}" \
    --request-timeout 30 \
    --workers 2 \
    --continue-on-failure \
    --no-color
STATUS=$?
set -e

if [ "$MODE" = "tier-b" ]; then
    # Reporting only: Tier B covers endpoints whose shape depends on what
    # happens to be on the dev instance, so a failure here is a lead to chase,
    # not a broken build.
    if [ "$STATUS" -ne 0 ]; then
        echo ""
        echo "⚠ Tier B reported failures (not blocking). Each one is either a spec"
        echo "  error worth fixing or a dev-data artefact worth understanding."
    fi
    exit 0
fi

exit "$STATUS"
