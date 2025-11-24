#!/bin/bash

# Deploy demo data to IntraVox server
# Usage: ./deploy-demo-data.sh

set -e

SERVER="145.38.191.66"
USER="rdekker"
SSH_KEY="~/.ssh/sur"
NEXTCLOUD_PATH="/var/www/nextcloud"
TEMP_PATH="/tmp/intravox-deploy"

echo "🚀 Deploying IntraVox demo data to ${SERVER}..."

# Copy demo data to server (including images)
echo "📦 Copying demo data to server..."
rsync -avz --delete -e "ssh -i ${SSH_KEY}" \
    demo-data/ \
    ${USER}@${SERVER}:${TEMP_PATH}/demo-data/

echo "🔐 Setting correct permissions..."
ssh -i ${SSH_KEY} ${USER}@${SERVER} \
    "find ${TEMP_PATH}/demo-data -type f -exec chmod 644 {} \; && \
     find ${TEMP_PATH}/demo-data -type d -exec chmod 755 {} \;"

echo "📥 Running import command..."
ssh -i ${SSH_KEY} ${USER}@${SERVER} \
    "cd ${NEXTCLOUD_PATH} && sudo -u www-data php occ intravox:import ${TEMP_PATH}/demo-data/en"

echo "🔄 Scanning groupfolder to register new files..."
ssh -i ${SSH_KEY} ${USER}@${SERVER} \
    "cd ${NEXTCLOUD_PATH} && sudo -u www-data php occ groupfolders:scan 1"

echo "🔍 Indexing files for search..."
ssh -i ${SSH_KEY} ${USER}@${SERVER} \
    "cd ${NEXTCLOUD_PATH} && sudo -u www-data php occ files:scan --path='/__groupfolders/1/files/en'"

echo "✅ Demo data deployed successfully!"
