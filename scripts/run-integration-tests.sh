#!/usr/bin/env bash
#
# Run the Integration suite against the real Nextcloud on nc-dev.
#
# Unlike the Unit suite (which stubs OCP away and runs anywhere), these tests
# need a live server with the groupfolders app: they create a throwaway
# groupfolder, mount it, check ACL-driven permissions, and remove it again.
# They never touch the real IntraVox content.
#
# Usage:
#   scripts/run-integration-tests.sh              # deploy current tree, then run
#   scripts/run-integration-tests.sh --no-deploy  # run what is already deployed
#   scripts/run-integration-tests.sh --filter Foo # pass through to phpunit
set -euo pipefail

SSH_HOST="${INTRAVOX_DEV_SSH:-}"
CONTAINER="${INTRAVOX_DEV_CONTAINER:-nc-dev}"
APP_DIR="/var/www/html/custom_apps/intravox"

# Not hard-coded: this file is public on github.com/nextcloud/IntraVox.
if [ -z "$SSH_HOST" ]; then
    echo "INTRAVOX_DEV_SSH is not set -- export INTRAVOX_DEV_SSH=user@your-dev-host" >&2
    exit 2
fi

DEPLOY=1
PHPUNIT_ARGS=()
for arg in "$@"; do
    case "$arg" in
        --no-deploy) DEPLOY=0 ;;
        *) PHPUNIT_ARGS+=("$arg") ;;
    esac
done

if [ "$DEPLOY" -eq 1 ]; then
    echo "==> Deploying current tree to ${CONTAINER}"
    NO_AUTO_BUMP=1 ./deploy.sh hetzner >/dev/null 2>&1
    echo "    done"
fi

# deploy.sh ships production files only — tests/ and the phpunit configs are
# deliberately not part of the app tarball. Copy them in separately so the
# suite under test is always the one in the working tree.
echo "==> Copying test files"
TMP_TESTS="$(mktemp -d)"
trap 'rm -rf "$TMP_TESTS"' EXIT
tar -czf "$TMP_TESTS/ivtests.tar.gz" tests phpunit-integration.xml
scp -q "$TMP_TESTS/ivtests.tar.gz" "${SSH_HOST}:/tmp/ivtests.tar.gz"
ssh "$SSH_HOST" "docker cp /tmp/ivtests.tar.gz ${CONTAINER}:/tmp/ivtests.tar.gz \
    && docker exec ${CONTAINER} sh -c 'cd ${APP_DIR} && tar -xzf /tmp/ivtests.tar.gz && chown -R www-data:www-data tests phpunit-integration.xml && rm -f /tmp/ivtests.tar.gz' \
    && rm -f /tmp/ivtests.tar.gz"
echo "    done"

# deploy.sh rebuilds vendor/ with --no-dev on purpose: the dev tree pulls in
# sabre/xml 4.x, which shadows the Nextcloud core's 2.x through PSR-4 and makes
# CalDAV fatal. So the deployed app has no phpunit, and this suite cannot run
# until we put one there. Ship the local vendor/ (which does have it) only when
# the container is missing phpunit, and only for the duration of the run --
# never as part of a deploy.
if ! ssh "$SSH_HOST" "docker exec ${CONTAINER} test -x ${APP_DIR}/vendor/bin/phpunit" 2>/dev/null; then
    echo "==> No phpunit in ${CONTAINER}; shipping dev dependencies"
    if [ ! -x vendor/bin/phpunit ]; then
        echo "    ERROR: no local vendor/bin/phpunit either. Run: composer install" >&2
        exit 1
    fi
    # The whole tree, not just phpunit: the deployed autoloader was generated
    # --no-dev, so a partial copy leaves PHPUnit\TextUI\Application unresolvable.
    #
    # Overlay it, never replace it. The deployed vendor/ carries the app's OWN
    # autoload maps, generated at package time against the deployed lib/ --
    # `rm -rf vendor` before unpacking takes those with it and every
    # OCA\IntraVox class stops resolving, which reads as 27 mystery errors
    # rather than as "you deleted the autoloader".
    TMP_VENDOR="$(mktemp -d)"
    tar -czf "$TMP_VENDOR/ivvendor.tar.gz" vendor
    scp -q "$TMP_VENDOR/ivvendor.tar.gz" "${SSH_HOST}:/tmp/ivvendor.tar.gz"
    ssh "$SSH_HOST" "docker cp /tmp/ivvendor.tar.gz ${CONTAINER}:/tmp/ivvendor.tar.gz \
        && docker exec ${CONTAINER} sh -c 'cd ${APP_DIR} && tar -xzf /tmp/ivvendor.tar.gz && chown -R www-data:www-data vendor && rm -f /tmp/ivvendor.tar.gz' \
        && rm -f /tmp/ivvendor.tar.gz"
    rm -rf "$TMP_VENDOR"
    echo "    done (this container now holds a DEV vendor/ — redeploy before"
    echo "    using it for anything else, or CalDAV will fatal on sabre/xml)"
fi

echo "==> Running Integration suite in ${CONTAINER}"
# www-data, because the tests touch the filesystem as a real user would.
ssh "$SSH_HOST" "docker exec -u www-data ${CONTAINER} sh -c 'cd ${APP_DIR} && php vendor/bin/phpunit -c phpunit-integration.xml ${PHPUNIT_ARGS[*]:-}'"
