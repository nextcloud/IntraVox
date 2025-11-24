#!/bin/bash

# IntraVox Deployment Script
# Deploys to Nextcloud test server

set -e

echo "🚀 IntraVox Deployment Script"
echo "=============================="

# Configuration
APP_NAME="intravox"
REMOTE_USER="rdekker"
REMOTE_HOST="145.38.191.66"  # Production server
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

echo ""
echo "📦 Step 1: Building frontend..."

# Build from intravox-deploy directory
if [ -d "intravox-deploy" ]; then
    echo "  🔨 Building from intravox-deploy directory..."
    cd intravox-deploy

    # Install dependencies if node_modules doesn't exist
    if [ ! -d "node_modules" ]; then
        echo "  📥 Installing dependencies..."
        npm install
    fi

    # Build
    npm run build

    if [ $? -ne 0 ]; then
        echo "❌ Build failed!"
        exit 1
    fi

    # Copy built files to main directory
    echo "  📋 Copying built files to main directory..."
    cp -r js/* ../js/
    cp -r l10n/* ../l10n/

    cd ..
else
    # Fallback to old build method
    npm run build

    if [ $? -ne 0 ]; then
        echo "❌ Build failed!"
        exit 1
    fi
fi

echo "✅ Build completed"

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

# Create tarball
TARBALL="$TEMP_DIR/${APP_NAME}.tar.gz"
echo "  📦 Creating tarball..."
cd "$TEMP_DIR"
tar -czf "$TARBALL" "$APP_NAME"

echo "✅ Deployment package created"

echo ""
echo "🚢 Step 3: Deploying to server..."
echo "  Server: $REMOTE_HOST"
echo "  Path: $REMOTE_PATH/$APP_NAME"

# Upload tarball
echo "  📤 Uploading package..."
scp -i "$SSH_KEY" "$TARBALL" "${REMOTE_USER}@${REMOTE_HOST}:/tmp/${APP_NAME}.tar.gz"

# Extract and setup on server
echo "  📂 Extracting on server..."
ssh -i "$SSH_KEY" "${REMOTE_USER}@${REMOTE_HOST}" << EOF
    set -e

    # Navigate to apps directory
    cd $REMOTE_PATH

    # Backup existing installation if present
    if [ -d "$APP_NAME" ]; then
        echo "  💾 Backing up existing installation..."
        BACKUP_NAME="${APP_NAME}.backup.\$(date +%Y%m%d_%H%M%S)"
        # Move backup to /tmp instead of apps directory to avoid Nextcloud scanning it
        sudo mv $APP_NAME "/tmp/\$BACKUP_NAME" || true
        echo "  📦 Backup saved to /tmp/\$BACKUP_NAME"
    fi

    # Extract new version
    echo "  📦 Extracting new version..."
    sudo tar -xzf /tmp/${APP_NAME}.tar.gz -C $REMOTE_PATH

    # Set permissions
    echo "  🔐 Setting permissions..."
    sudo chown -R www-data:www-data $REMOTE_PATH/$APP_NAME
    sudo chmod -R 755 $REMOTE_PATH/$APP_NAME

    # Clean up
    rm /tmp/${APP_NAME}.tar.gz

    echo "  ✅ Files deployed"
EOF

echo ""
echo "🔧 Step 4: Enabling app and running setup..."
ssh -i "$SSH_KEY" "${REMOTE_USER}@${REMOTE_HOST}" << EOF
    set -e
    cd /var/www/nextcloud

    # Enable app (will also trigger any necessary setup)
    echo "  🔌 Enabling app..."
    sudo -u www-data php occ app:enable $APP_NAME || true

    # Run setup command
    echo "  🏗️  Running IntraVox setup..."
    sudo -u www-data php occ intravox:setup || echo "  ℹ️  Setup will complete on first use"

    echo "  ✅ App enabled"
EOF

# Cleanup local temp files
rm -rf "$TEMP_DIR"

echo ""
echo "✅ Deployment completed successfully!"
echo ""
echo "📊 Summary:"
echo "  • App Name: $APP_NAME"
echo "  • Server: $REMOTE_HOST"
echo "  • Status: Deployed and enabled"
echo ""
echo "🌐 Access IntraVox at:"
echo "  https://$REMOTE_HOST"
echo ""
echo "🔧 Setup command (if needed):"
echo "  ssh ${REMOTE_USER}@${REMOTE_HOST} 'sudo -u www-data php /var/www/html/occ intravox:setup'"
echo ""
echo "📝 View logs:"
echo "  ssh ${REMOTE_USER}@${REMOTE_HOST} 'sudo tail -f /var/www/html/data/nextcloud.log'"
echo ""
