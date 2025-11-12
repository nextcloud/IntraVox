#!/bin/bash

# IntraVox Deployment Script (Optimized)
# Deploys to Nextcloud test server

set -e

echo "🚀 IntraVox Deployment Script"
echo "=============================="

# Configuration
APP_NAME="intravox"
REMOTE_USER="rdekker"
REMOTE_HOST="145.38.191.31"
REMOTE_PATH="/var/www/nextcloud/apps"
SSH_KEY="~/.ssh/sur"
LOCAL_PATH="$(pwd)"

# Files and folders to include in deployment
INCLUDE_ITEMS=(
    "appinfo"
    "lib"
    "l10n"
    "templates"
    "css"
    "img"
    "js"
    "CHANGELOG.md"
    "LICENSE"
    "README.md"
)

# Check if we should skip build
SKIP_BUILD=false
if [ "$1" = "--skip-build" ] || [ "$1" = "-s" ]; then
    SKIP_BUILD=true
fi

if [ "$SKIP_BUILD" = false ]; then
    echo ""
    echo "📦 Step 1: Building frontend..."
    npm run build

    if [ $? -ne 0 ]; then
        echo "❌ Build failed!"
        exit 1
    fi

    echo "✅ Build completed"
else
    echo ""
    echo "⏭️  Step 1: Skipping build (using existing js/)"
fi

echo ""
echo "📋 Step 2: Creating deployment package..."

# Create temporary directory
TEMP_DIR=$(mktemp -d)
DEPLOY_DIR="$TEMP_DIR/$APP_NAME"
mkdir -p "$DEPLOY_DIR"

# Copy files
for item in "${INCLUDE_ITEMS[@]}"; do
    if [ -e "$LOCAL_PATH/$item" ]; then
        echo "  📄 Copying $item..."
        cp -r "$LOCAL_PATH/$item" "$DEPLOY_DIR/"
    else
        echo "  ⚠️  Warning: $item not found, skipping..."
    fi
done

# Create tarball with faster compression
TARBALL="$TEMP_DIR/${APP_NAME}.tar.gz"
echo "  📦 Creating tarball..."
cd "$TEMP_DIR"
# Use pigz for parallel compression if available, otherwise use gzip with low compression
if command -v pigz &> /dev/null; then
    tar -cf - "$APP_NAME" | pigz -1 > "$TARBALL"
else
    tar -czf "$TARBALL" --fast "$APP_NAME" 2>/dev/null || tar -czf "$TARBALL" "$APP_NAME"
fi

echo "✅ Deployment package created"

echo ""
echo "🚢 Step 3: Deploying to server..."
echo "  Server: $REMOTE_HOST"
echo "  Path: $REMOTE_PATH/$APP_NAME"

# Upload tarball
echo "  📤 Uploading package..."
scp -i "$SSH_KEY" -C "$TARBALL" "${REMOTE_USER}@${REMOTE_HOST}:/tmp/${APP_NAME}.tar.gz"

# Extract and setup on server - COMBINED INTO ONE SSH SESSION
echo "  📂 Deploying and enabling..."
ssh -i "$SSH_KEY" "${REMOTE_USER}@${REMOTE_HOST}" << EOF
    set -e

    # Navigate to apps directory
    cd $REMOTE_PATH

    # Clean up old backups (keep only last 3 for speed)
    sudo ls -t /tmp/${APP_NAME}.backup.* 2>/dev/null | tail -n +4 | xargs -r sudo rm -rf 2>/dev/null || true

    # Quick backup - just rename, don't copy
    if [ -d "$APP_NAME" ]; then
        BACKUP_NAME="${APP_NAME}.backup.\$(date +%Y%m%d_%H%M%S)"
        sudo mv $APP_NAME "/tmp/\$BACKUP_NAME" 2>/dev/null || true
    fi

    # Extract new version
    sudo tar -xzf /tmp/${APP_NAME}.tar.gz -C $REMOTE_PATH

    # Set permissions
    sudo chown -R www-data:www-data $REMOTE_PATH/$APP_NAME
    sudo chmod -R 755 $REMOTE_PATH/$APP_NAME

    # Clean up tarball
    rm /tmp/${APP_NAME}.tar.gz

    # Clear caches and refresh app
    cd /var/www/nextcloud

    # Clear Nextcloud caches
    sudo -u www-data php occ maintenance:repair 2>/dev/null || true

    # Disable and re-enable app to clear route cache
    sudo -u www-data php occ app:disable $APP_NAME 2>/dev/null || true
    sudo -u www-data php occ app:enable $APP_NAME 2>/dev/null || true

    # Restart Apache to clear PHP opcache and Apache cache
    sudo service apache2 restart

    echo "  ✅ Deployed, caches cleared, and enabled"
EOF

# Cleanup local temp files
rm -rf "$TEMP_DIR"

echo ""
echo "✅ Deployment completed!"
echo ""
echo "🌐 Access: https://$REMOTE_HOST"
echo ""
echo "💡 Quick tip: Use './deploy-dev.sh --skip-build' to skip the build step"
echo ""
