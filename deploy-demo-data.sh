#!/bin/bash
#
# Deploy ALL demo data (NL + EN) to IntraVox server
# Usage: ./deploy-demo-data.sh [server]
#
# Examples:
#   ./deploy-demo-data.sh                  # Deploy to default dev server 145.38.195.41
#   ./deploy-demo-data.sh 145.38.195.41    # Deploy to specific server
#

set -e

SERVER="${1:-145.38.195.41}"
USER="rdekker"
SSH_KEY="~/.ssh/sur"
NEXTCLOUD_PATH="/var/www/nextcloud"
GROUPFOLDER_PATH="/var/www/nextcloud/data/__groupfolders/1/files"
TEMP_PATH="/tmp/intravox-demo-data"

echo "🚀 Deploying ALL IntraVox demo data to ${SERVER}..."
echo "📋 Languages: NL (Dutch) + EN (English)"
echo ""

# Create tarball with all demo data
echo "📦 Step 1: Packaging demo data..."
cd demo-data
tar -czf /tmp/demo-data-all.tar.gz nl/ en/ || {
    echo "❌ Error: Could not create tarball. Check that nl/ and en/ folders exist in demo-data/"
    exit 1
}
cd ..

TARBALL_SIZE=$(du -h /tmp/demo-data-all.tar.gz | cut -f1)
echo "✓ Tarball created: $TARBALL_SIZE"
echo ""

# Upload to server
echo "📤 Step 2: Uploading to server..."
scp -i ${SSH_KEY} /tmp/demo-data-all.tar.gz ${USER}@${SERVER}:/tmp/

echo ""
echo "🔧 Step 3: Checking prerequisites..."
ssh -i ${SSH_KEY} ${USER}@${SERVER} << 'ENDSSH'
set -e

echo "   Checking GroupFolders app..."
if ! sudo -u www-data php /var/www/nextcloud/occ app:list | grep -q "groupfolders"; then
    echo "   Installing GroupFolders app..."
    sudo -u www-data php /var/www/nextcloud/occ app:install groupfolders
    sudo -u www-data php /var/www/nextcloud/occ app:enable groupfolders
    echo "   ✅ GroupFolders installed"
else
    echo "   ✅ GroupFolders already installed"
fi

echo "   Running IntraVox setup..."
sudo -u www-data php /var/www/nextcloud/occ intravox:setup 2>&1 | grep -E '(✓|✗|IntraVox)' || echo "   Setup completed"

echo "   ✅ Prerequisites ready"
ENDSSH

echo ""
echo "📥 Step 4: Deploying demo data..."
ssh -i ${SSH_KEY} ${USER}@${SERVER} << 'ENDSSH'
set -e

GROUPFOLDER="/var/www/nextcloud/data/__groupfolders/1/files"
TEMP_PATH="/tmp/intravox-demo-data"

# Clean existing language folders to ensure fresh deployment
echo "   Cleaning existing demo data..."
sudo rm -rf "${GROUPFOLDER}/nl"
sudo rm -rf "${GROUPFOLDER}/en"

# Force scan groupfolder to detect removals and clear stale cache entries
echo "   Forcing groupfolder rescan to clear file cache..."
sudo -u www-data php /var/www/nextcloud/occ groupfolders:scan 1 > /dev/null 2>&1 || true

# Clean up any orphaned file cache entries
echo "   Cleaning orphaned file cache entries..."
sudo -u www-data php /var/www/nextcloud/occ files:cleanup > /dev/null 2>&1 || true

# Remove any remaining stale cache entries for nl/en paths
echo "   Removing stale cache entries from database..."
sudo mysql -e "DELETE FROM oc_filecache WHERE path LIKE '%__groupfolders/1/files/nl%';" nextcloud 2>/dev/null || true
sudo mysql -e "DELETE FROM oc_filecache WHERE path LIKE '%__groupfolders/1/files/en%';" nextcloud 2>/dev/null || true

# Create fresh directories
sudo mkdir -p "${GROUPFOLDER}/nl"
sudo mkdir -p "${GROUPFOLDER}/en"
sudo chown -R www-data:www-data "${GROUPFOLDER}/nl" "${GROUPFOLDER}/en"

# Extract tarball
echo "   Extracting demo data..."
sudo rm -rf "${TEMP_PATH}"
sudo mkdir -p "${TEMP_PATH}"
sudo tar -xzf /tmp/demo-data-all.tar.gz -C "${TEMP_PATH}"

# Fix permissions on extracted files so import can read them
echo "   Fixing temp file permissions..."
sudo chmod -R 755 "${TEMP_PATH}"
sudo find "${TEMP_PATH}" -type f -name "*.json" -exec chmod 644 {} \;

# Deploy NL (Dutch) demo data
echo "   📁 Deploying Dutch (nl) demo data..."
sudo -u www-data php /var/www/nextcloud/occ intravox:import --language=nl "${TEMP_PATH}/nl" 2>&1 | grep -v "^$" || true

# Recursively copy all nested page structures and images for NL
echo "   📁 Copying ALL nested NL page structures recursively..."

# Function to recursively copy directory structure
copy_nested_structure() {
    local src_base="$1"
    local dest_base="$2"

    # Find all directories and create them
    find "${src_base}" -type d | while read -r dir; do
        rel_path="${dir#${src_base}/}"
        if [ "$rel_path" != "$dir" ]; then
            sudo -u www-data mkdir -p "${dest_base}/${rel_path}" 2>/dev/null || true
        fi
    done

    # Find and copy all JSON files
    find "${src_base}" -type f -name "*.json" | while read -r file; do
        rel_path="${file#${src_base}/}"
        target_dir="${dest_base}/$(dirname "${rel_path}")"
        sudo -u www-data cp "${file}" "${target_dir}/" 2>/dev/null || true
    done

    # Find and copy all images directories
    find "${src_base}" -type d -name "images" | while read -r img_dir; do
        rel_path="${img_dir#${src_base}/}"
        target_dir="${dest_base}/${rel_path}"
        if [ -d "${img_dir}" ] && [ "$(ls -A ${img_dir} 2>/dev/null)" ]; then
            sudo -u www-data cp -r "${img_dir}/"* "${target_dir}/" 2>/dev/null || true
        fi
    done
}

# Copy the entire NL structure recursively
copy_nested_structure "${TEMP_PATH}/nl" "${GROUPFOLDER}/nl"

echo "   ✅ NL structure copied recursively"

# Deploy EN (English) demo data
echo "   📁 Deploying English (en) demo data..."
sudo -u www-data php /var/www/nextcloud/occ intravox:import --language=en "${TEMP_PATH}/en" 2>&1 | grep -E '(Created|Updated|Successfully|Error|Failed)' || true

# Copy the entire EN structure recursively (same as NL)
echo "   📁 Copying ALL nested EN page structures recursively..."
copy_nested_structure "${TEMP_PATH}/en" "${GROUPFOLDER}/en"

echo "   ✅ EN structure copied recursively"
echo "   ✅ Demo data deployed to groupfolder"
ENDSSH

echo ""
echo "🧭 Step 5: Generating navigation based on available pages..."
ssh -i ${SSH_KEY} ${USER}@${SERVER} << 'ENDSSH'
set -e
GROUPFOLDER="/var/www/nextcloud/data/__groupfolders/1/files"

# Function to generate navigation from directory structure
generate_navigation() {
    local lang="$1"
    local base_path="${GROUPFOLDER}/${lang}"

    echo "   📝 Generating navigation for ${lang}..."

    # Create a temporary script to build navigation JSON
    cat > /tmp/build_nav_${lang}.sh << 'NAVSCRIPT'
#!/bin/bash

LANG="$1"
BASE_PATH="$2"

# Function to extract uniqueId from JSON file
get_unique_id() {
    local file="$1"
    grep -oP '"uniqueId"\s*:\s*"\K[^"]+' "$file" 2>/dev/null | head -1
}

# Function to extract title from JSON file
get_title() {
    local file="$1"
    grep -oP '"title"\s*:\s*"\K[^"]+' "$file" 2>/dev/null | head -1
}

# Function to build navigation tree recursively
build_nav_item() {
    local dir="$1"
    local rel_path="${dir#${BASE_PATH}/}"
    local indent="$2"

    # Get the directory name (this will be the basis for finding the JSON file)
    local dir_name=$(basename "$dir")
    local json_file="${dir}/${dir_name}.json"

    # Skip if no JSON file exists for this directory
    if [ ! -f "$json_file" ]; then
        return
    fi

    local unique_id=$(get_unique_id "$json_file")
    local title=$(get_title "$json_file")

    # If we couldn't extract title or uniqueId, skip this item
    if [ -z "$unique_id" ] || [ -z "$title" ]; then
        return
    fi

    # Find subdirectories (potential children)
    local children_json=""
    local has_children=false

    # Process subdirectories in sorted order
    while IFS= read -r -d '' subdir; do
        if [ "$(basename "$subdir")" != "images" ]; then
            local child_json=$(build_nav_item "$subdir" "    $indent")
            if [ -n "$child_json" ]; then
                if [ "$has_children" = true ]; then
                    children_json="${children_json},
"
                fi
                children_json="${children_json}${child_json}"
                has_children=true
            fi
        fi
    done < <(find "$dir" -mindepth 1 -maxdepth 1 -type d -print0 | sort -z)

    # Build JSON for this item
    local item_json="${indent}{
${indent}  \"title\": \"${title}\",
${indent}  \"url\": null,
${indent}  \"target\": null,
${indent}  \"children\": ["

    if [ "$has_children" = true ]; then
        item_json="${item_json}
${children_json}
${indent}  ],"
    else
        item_json="${item_json}],"
    fi

    item_json="${item_json}
${indent}  \"uniqueId\": \"${unique_id}\"
${indent}}"

    echo "$item_json"
}

# Start building navigation
nav_items=""
first_item=true

# Add home page first
home_json="${BASE_PATH}/home.json"
if [ -f "$home_json" ]; then
    home_id=$(get_unique_id "$home_json")
    home_title=$(get_title "$home_json")
    if [ -n "$home_id" ] && [ -n "$home_title" ]; then
        nav_items="    {
      \"title\": \"${home_title}\",
      \"url\": null,
      \"target\": null,
      \"children\": [],
      \"uniqueId\": \"${home_id}\"
    }"
        first_item=false
    fi
fi

# Process all top-level directories (except images)
for dir in "${BASE_PATH}"/*; do
    if [ -d "$dir" ] && [ "$(basename "$dir")" != "images" ]; then
        item_json=$(build_nav_item "$dir" "    ")
        if [ -n "$item_json" ]; then
            if [ "$first_item" = false ]; then
                nav_items="${nav_items},
"
            fi
            nav_items="${nav_items}${item_json}"
            first_item=false
        fi
    fi
done

# Build final navigation JSON
cat << NAVJSON
{
  "type": "megamenu",
  "items": [
${nav_items}
  ]
}
NAVJSON

NAVSCRIPT

    chmod +x /tmp/build_nav_${lang}.sh

    # Generate navigation and save it
    sudo -u www-data /tmp/build_nav_${lang}.sh "${lang}" "${base_path}" > /tmp/navigation_${lang}.json

    # Copy generated navigation to the groupfolder
    sudo -u www-data cp /tmp/navigation_${lang}.json "${base_path}/navigation.json"

    # Clean up
    rm /tmp/build_nav_${lang}.sh /tmp/navigation_${lang}.json

    echo "   ✅ Navigation generated for ${lang}"
}

# Generate navigation for both languages
generate_navigation "nl"
generate_navigation "en"

echo "   ✅ Navigation files updated"
ENDSSH

echo ""
echo "🔧 Step 6: Fixing file permissions..."
ssh -i ${SSH_KEY} ${USER}@${SERVER} << 'ENDSSH'
set -e
GROUPFOLDER="/var/www/nextcloud/data/__groupfolders/1/files"

echo "   Fixing JSON file permissions (644)..."
sudo find "${GROUPFOLDER}/nl" -name "*.json" -exec chmod 644 {} \; 2>/dev/null || true
sudo find "${GROUPFOLDER}/en" -name "*.json" -exec chmod 644 {} \; 2>/dev/null || true

echo "   Fixing directory permissions (755)..."
sudo find "${GROUPFOLDER}/nl" -type d -exec chmod 755 {} \; 2>/dev/null || true
sudo find "${GROUPFOLDER}/en" -type d -exec chmod 755 {} \; 2>/dev/null || true

echo "   Fixing image file permissions (644)..."
sudo find "${GROUPFOLDER}/nl" -type f \( -name "*.jpg" -o -name "*.png" -o -name "*.gif" \) -exec chmod 644 {} \; 2>/dev/null || true
sudo find "${GROUPFOLDER}/en" -type f \( -name "*.jpg" -o -name "*.png" -o -name "*.gif" \) -exec chmod 644 {} \; 2>/dev/null || true

echo "   ✅ Permissions fixed"
ENDSSH

echo ""
echo "🔄 Step 7: Scanning groupfolder..."
ssh -i ${SSH_KEY} ${USER}@${SERVER} \
    "cd ${NEXTCLOUD_PATH} && sudo -u www-data php occ groupfolders:scan 1"

echo ""
echo "🔍 Step 8: Indexing files..."
ssh -i ${SSH_KEY} ${USER}@${SERVER} << 'ENDSSH'
set -e
cd /var/www/nextcloud

echo "   Indexing NL files..."
sudo -u www-data php occ files:scan --path='/__groupfolders/1/nl' 2>&1 | grep -E '(Folders|Files|Elapsed)' || true

echo "   Indexing EN files..."
sudo -u www-data php occ files:scan --path='/__groupfolders/1/en' 2>&1 | grep -E '(Folders|Files|Elapsed)' || true

echo "   ✅ Files indexed"
ENDSSH

echo ""
echo "🧹 Step 9: Clearing caches..."
ssh -i ${SSH_KEY} ${USER}@${SERVER} << 'ENDSSH'
set -e
cd /var/www/nextcloud

echo "   Cleaning file cache..."
sudo -u www-data php occ files:cleanup > /dev/null

echo "   Rescanning app data..."
sudo -u www-data php occ files:scan-app-data > /dev/null

echo "   ✅ Caches cleared"
ENDSSH

# Cleanup
rm /tmp/demo-data-all.tar.gz

echo ""
echo "✅ Demo data deployed successfully to ${SERVER}!"
echo ""
echo "📊 Deployed:"
echo "   • Dutch (NL): Home + Afdeling > Marketing > Marketingcampagnes"
echo "   • English (EN): Full demo pages + navigation"
echo ""
echo "🌐 Access IntraVox at:"
echo "   https://${SERVER}/apps/intravox"
echo ""
