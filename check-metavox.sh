#!/bin/bash

# Simple MetaVox diagnostic script for IntraVox
# Run this on the server: ssh 1dev "bash -s" < check-metavox.sh

echo "=== MetaVox Integration Diagnostic ==="
echo ""

# 1. Check if MetaVox app is installed and enabled
echo "1. Checking MetaVox installation..."
cd /var/www/nextcloud
sudo -u www-data php occ app:list | grep -A 5 metavox
echo ""

# 2. Check MetaVox version
echo "2. MetaVox version:"
sudo -u www-data php occ app:list | grep metavox | head -1
echo ""

# 3. Check IntraVox groupfolder
echo "3. Checking IntraVox groupfolder..."
sudo -u www-data php occ groupfolders:list | grep -i intravox
echo ""

# 4. Check recent export logs
echo "4. Recent export logs (last 50 lines with MetaVox mentions):"
tail -100 /var/www/nextcloud/data/nextcloud.log | grep -i "metavox\|export" | tail -20
echo ""

# 5. Check if there are any MetaVox errors
echo "5. Recent MetaVox errors:"
tail -100 /var/www/nextcloud/data/nextcloud.log | grep -i "metavox" | grep -i "error\|warning"
echo ""

echo "=== Diagnostic Complete ==="
