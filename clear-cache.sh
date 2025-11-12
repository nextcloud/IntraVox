#!/bin/bash

# Clear cache script for IntraVox development server

REMOTE_USER="rdekker"
REMOTE_HOST="145.38.191.31"
SSH_KEY="~/.ssh/sur"

echo "🧹 Clearing Nextcloud cache..."

ssh -i "$SSH_KEY" "$REMOTE_USER@$REMOTE_HOST" << 'EOF'
  sudo -u www-data php /var/www/nextcloud/occ maintenance:repair
  sudo service apache2 restart
  echo "✅ Cache cleared and Apache restarted"
EOF

echo ""
echo "✅ Cache operations completed!"
