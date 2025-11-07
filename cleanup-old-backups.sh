#!/bin/bash

# Cleanup script to remove old backup folders from Nextcloud apps directory
# These backup folders were created by earlier versions of deploy.sh

REMOTE_HOST="145.38.191.124"
REMOTE_USER="rdekker"
SSH_KEY="$HOME/.ssh/id_ed25519"
REMOTE_PATH="/var/www/nextcloud/apps"

echo "🧹 Cleaning up old IntraVox backup folders..."
echo ""

ssh -i "$SSH_KEY" "${REMOTE_USER}@${REMOTE_HOST}" << 'EOF'
cd /var/www/nextcloud/apps

echo "📂 Checking for old backup folders..."
OLD_BACKUPS=$(ls -d intravox.backup.* 2>/dev/null || echo "")

if [ -z "$OLD_BACKUPS" ]; then
    echo "✅ No old backup folders found"
else
    echo "Found backup folders:"
    echo "$OLD_BACKUPS"
    echo ""
    echo "🗑️  Removing old backups..."
    sudo rm -rf intravox.backup.* 2>/dev/null || true
    echo "✅ Old backups removed"
fi

echo ""
echo "📊 Current apps directory contents (intravox*):"
ls -lh | grep intravox || echo "Only intravox app found (no backups)"
EOF

echo ""
echo "✅ Cleanup complete!"
