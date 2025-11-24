#!/bin/bash
#
# Deploy Dutch (nl) demo data to IntraVox
#
# IMPORTANT: This script follows the language folder architecture documented in ARCHITECTURE.md
# Each language MUST have its own top-level folder. Never deploy to wrong language folder.
#
# Key learnings from manual deployment:
# - Import command doesn't handle nested folders recursively
# - Images folders must NOT have emoji characters
# - All JSON files must have a 'path' field
# - Groupfolder scan is required after file changes
#

set -e

SERVER="145.38.191.66"
USER="rdekker"
SSH_KEY="~/.ssh/sur"
NEXTCLOUD_PATH="/var/www/nextcloud"
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
echo "📦 Step 1: Deploy root-level files (home, navigation, footer)..."
# Use tar to preserve permissions and avoid rsync issues
cd demo-data
tar -czf /tmp/demo-data-nl.tar.gz nl/
scp -i ${SSH_KEY} /tmp/demo-data-nl.tar.gz ${USER}@${SERVER}:/tmp/

ssh -i ${SSH_KEY} ${USER}@${SERVER} << 'ENDSSH'
set -e
cd /tmp
sudo rm -rf demo-data-nl
tar -xzf demo-data-nl.tar.gz
mv nl demo-data-nl
sudo chown -R www-data:www-data demo-data-nl
sudo chmod -R 755 demo-data-nl

echo "📥 Importing root-level files with occ command..."
cd /var/www/nextcloud
sudo -u www-data php occ intravox:import --language=nl /tmp/demo-data-nl
ENDSSH

echo ""
echo "📦 Step 2: Manually deploy nested page folders..."
echo "   (Import command doesn't handle these recursively)"

# Deploy afdeling/marketing nested structure
ssh -i ${SSH_KEY} ${USER}@${SERVER} << 'ENDSSH'
set -e

GROUPFOLDER="/var/www/nextcloud/data/__groupfolders/1/files/nl"
SOURCE="/tmp/demo-data-nl"

echo "   Creating nested folder structure..."
# Create root-level images folder
sudo -u www-data mkdir -p "${GROUPFOLDER}/images"

# Create nested page folders with their images subfolders
sudo -u www-data mkdir -p "${GROUPFOLDER}/afdeling/images"
sudo -u www-data mkdir -p "${GROUPFOLDER}/afdeling/marketing/marketingcampagnes"
sudo -u www-data mkdir -p "${GROUPFOLDER}/afdeling/marketing/marketingcampagnes/images"
sudo -u www-data mkdir -p "${GROUPFOLDER}/afdeling/marketing/images"

echo "   Copying marketing page files..."
sudo -u www-data cp -r "${SOURCE}/afdeling/marketing/marketing.json" "${GROUPFOLDER}/afdeling/marketing/" 2>/dev/null || true
sudo -u www-data cp -r "${SOURCE}/afdeling/marketing/marketingcampagnes/marketingcampagnes.json" "${GROUPFOLDER}/afdeling/marketing/marketingcampagnes/" 2>/dev/null || true

echo "   Copying image files..."
# Copy root-level images (used by home page)
if [ -d "${SOURCE}/images" ]; then
    echo "      - Root images folder"
    sudo -u www-data cp -r "${SOURCE}/images/"* "${GROUPFOLDER}/images/" 2>/dev/null || true
fi

# Copy images from afdeling folder
if [ -d "${SOURCE}/afdeling/images" ]; then
    echo "      - Afdeling images folder"
    sudo -u www-data cp -r "${SOURCE}/afdeling/images/"* "${GROUPFOLDER}/afdeling/images/" 2>/dev/null || true
fi

# Copy images from marketing folder
if [ -d "${SOURCE}/afdeling/marketing/images" ]; then
    echo "      - Marketing images folder"
    sudo -u www-data cp -r "${SOURCE}/afdeling/marketing/images/"* "${GROUPFOLDER}/afdeling/marketing/images/" 2>/dev/null || true
fi

# Copy images from marketingcampagnes folder (if any exist in the future)
if [ -d "${SOURCE}/afdeling/marketing/marketingcampagnes/images" ]; then
    echo "      - Marketingcampagnes images folder"
    sudo -u www-data cp -r "${SOURCE}/afdeling/marketing/marketingcampagnes/images/"* "${GROUPFOLDER}/afdeling/marketing/marketingcampagnes/images/" 2>/dev/null || true
fi

echo "   ✅ Nested folders deployed"
ENDSSH

echo ""
echo "🔄 Step 3: Scanning groupfolder to register all files..."
ssh -i ${SSH_KEY} ${USER}@${SERVER} \
    "cd ${NEXTCLOUD_PATH} && sudo -u www-data php occ groupfolders:scan 1"

echo ""
echo "🔍 Step 4: Indexing files for search..."
ssh -i ${SSH_KEY} ${USER}@${SERVER} \
    "cd ${NEXTCLOUD_PATH} && sudo -u www-data php occ files:scan --path='/__groupfolders/1/files/${TARGET_LANGUAGE}'"

echo ""
echo "✅ Step 5: Verifying deployment..."
ssh -i ${SSH_KEY} ${USER}@${SERVER} << 'ENDSSH'
set -e

GROUPFOLDER="/var/www/nextcloud/data/__groupfolders/1/files/nl"

# Check root files
if [ ! -f "${GROUPFOLDER}/home.json" ]; then
    echo "❌ ERROR: home.json not found"
    exit 1
fi

if [ ! -f "${GROUPFOLDER}/navigation.json" ]; then
    echo "❌ ERROR: navigation.json not found"
    exit 1
fi

if [ ! -f "${GROUPFOLDER}/footer.json" ]; then
    echo "❌ ERROR: footer.json not found"
    exit 1
fi

# Check nested structure
if [ ! -f "${GROUPFOLDER}/afdeling/afdeling.json" ]; then
    echo "❌ ERROR: afdeling.json not found"
    exit 1
fi

if [ ! -f "${GROUPFOLDER}/afdeling/marketing/marketing.json" ]; then
    echo "❌ ERROR: marketing.json not found"
    exit 1
fi

if [ ! -f "${GROUPFOLDER}/afdeling/marketing/marketingcampagnes/marketingcampagnes.json" ]; then
    echo "❌ ERROR: marketingcampagnes.json not found"
    exit 1
fi

echo "✅ All required JSON files present"
echo ""
echo "📊 Deployment summary:"
echo "   Root files:"
sudo find "${GROUPFOLDER}" -maxdepth 1 -name "*.json" | wc -l | xargs echo "      JSON files:"

echo "   Nested pages:"
sudo find "${GROUPFOLDER}/afdeling" -name "*.json" | wc -l | xargs echo "      JSON files:"

echo "   Image folders:"
sudo find "${GROUPFOLDER}" -type d -name "images" | wc -l | xargs echo "      Count:"

echo "   Image files:"
sudo find "${GROUPFOLDER}" -type d -name "images" -exec find {} -type f \; | wc -l | xargs echo "      Total files:"
ENDSSH

echo ""
echo "🎉 Dutch demo data deployed successfully to ${TARGET_LANGUAGE}/ folder!"
echo ""
echo "📝 Next steps:"
echo "   1. Refresh IntraVox in your browser"
echo "   2. Navigate to Afdeling > Marketing > Marketingcampagnes"
echo "   3. Verify images are visible"
