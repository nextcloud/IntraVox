#!/usr/bin/env bash
set -euo pipefail
cd "$(dirname "$0")/../.."
composer install --no-interaction --prefer-dist
npm ci --no-audit --no-fund
bash scripts/replit/setup.sh
