#!/bin/bash
#
# Deploy Dutch (nl) demo data to IntraVox
#
# IMPORTANT: This script follows the language folder architecture documented in ARCHITECTURE.md
# Each language MUST have its own top-level folder. Never deploy to wrong language folder.
#

set -e

SERVER="145.38.191.66"
USER="rdekker"
SSH_KEY="~/.ssh/sur"
NEXTCLOUD_PATH="/var/www/nextcloud"
TEMP_PATH="/tmp/intravox-deploy"
GROUPFOLDER_PATH="/var/www/nextcloud/data/__groupfolders/1/files"
TARGET_LANGUAGE="nl"

echo "🚀 Deploying Dutch demo data to ${SERVER}..."
echo "📋 Target language folder: ${TARGET_LANGUAGE}/"
echo ""

# ARCHITECTURE REQUIREMENT: Verify target language folder exists
echo "🔍 Verifying ${TARGET_LANGUAGE}/ language folder exists on server..."
ssh -i ${SSH_KEY} ${USER}@${SERVER} \
    "sudo test -d '${GROUPFOLDER_PATH}/${TARGET_LANGUAGE}' && \
        echo '✅ ${TARGET_LANGUAGE}/ folder exists' || \
        (echo '❌ ERROR: ${TARGET_LANGUAGE}/ language folder does not exist!' && \
         echo 'Available language folders:' && \
         sudo ls -la ${GROUPFOLDER_PATH}/ | grep '^d' | awk '{print \$NF}' && \
         exit 1)"

echo ""
echo "📦 Copying demo data to server (preserving ${TARGET_LANGUAGE}/ folder structure)..."
rsync -avz --delete -e "ssh -i ${SSH_KEY}" \
    demo-data/nl/ \
    ${USER}@${SERVER}:${TEMP_PATH}/demo-data/nl/

echo ""
echo "📥 Running import command..."
echo "   Source: ${TEMP_PATH}/demo-data/nl/"
echo "   Target: ${GROUPFOLDER_PATH}/${TARGET_LANGUAGE}/"
ssh -i ${SSH_KEY} ${USER}@${SERVER} \
    "cd ${NEXTCLOUD_PATH} && sudo -u www-data php occ intravox:import ${TEMP_PATH}/demo-data/nl --language ${TARGET_LANGUAGE}"

echo ""
echo "🔄 Scanning groupfolder to register new files..."
ssh -i ${SSH_KEY} ${USER}@${SERVER} \
    "cd ${NEXTCLOUD_PATH} && sudo -u www-data php occ groupfolders:scan 1"

echo ""
echo "✅ Verifying deployment to correct language folder..."
ssh -i ${SSH_KEY} ${USER}@${SERVER} \
    "if [ -f '${GROUPFOLDER_PATH}/${TARGET_LANGUAGE}/home.json' ] && \
        [ -f '${GROUPFOLDER_PATH}/${TARGET_LANGUAGE}/navigation.json' ] && \
        [ -d '${GROUPFOLDER_PATH}/${TARGET_LANGUAGE}/afdeling' ]; then \
        echo '✅ Dutch content successfully deployed to ${TARGET_LANGUAGE}/ folder'; \
        echo 'Files in ${TARGET_LANGUAGE}/:'; \
        sudo ls -la ${GROUPFOLDER_PATH}/${TARGET_LANGUAGE}/ | grep -v '^total'; \
    else \
        echo '❌ ERROR: Expected files not found in ${TARGET_LANGUAGE}/ folder!'; \
        exit 1; \
    fi"

echo ""
echo "🎉 Dutch demo data deployed successfully to ${TARGET_LANGUAGE}/ folder!"
