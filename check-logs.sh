#!/bin/bash

# Script to check MetaVox logs on 1dev server
# Usage: ./check-logs.sh

echo "=== Checking MetaVox logs on 1dev server ==="
echo ""

# Check recent MetaVox mentions in logs
echo "1. Recent MetaVox log entries:"
ssh rdekker@145.38.193.235 'sudo tail -200 /var/www/nextcloud/data/nextcloud.log' | grep -i metavox | tail -20

echo ""
echo "2. Export-related MetaVox logs:"
ssh rdekker@145.38.193.235 'sudo tail -300 /var/www/nextcloud/data/nextcloud.log' | grep -i "metavox\|export" | grep -i metavox | tail -15

echo ""
echo "3. Import-related MetaVox logs:"
ssh rdekker@145.38.193.235 'sudo tail -300 /var/www/nextcloud/data/nextcloud.log' | grep -i "metavox\|import" | grep -i metavox | tail -15

echo ""
echo "=== End of logs ==="
