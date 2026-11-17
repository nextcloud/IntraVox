#!/usr/bin/env bash
set -euo pipefail
cd "$(dirname "$0")/../.."
if ! php -r '$CONFIG=[]; @include ".runtime/nextcloud/config/config.php"; exit(($CONFIG["installed"] ?? false) ? 0 : 1);'; then
    echo "Nextcloud is not installed. Run: bash scripts/replit/setup.sh" >&2
    exit 1
fi
echo "Starting local Nextcloud on port 5000 (development only)."
export PHP_CLI_SERVER_WORKERS=4
exec php -d memory_limit=512M -d upload_max_filesize=128M -d post_max_size=128M \
    -S 0.0.0.0:5000 -t .runtime/nextcloud scripts/replit/router.php
