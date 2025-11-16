#!/bin/bash

# Upload IntraVox demo data via WebDAV to ensure proper Nextcloud file cache
# This fixes the issue where tar extraction doesn't update the file cache

set -e

# Configuration
NEXTCLOUD_URL="https://145.38.191.31"
USERNAME="admin"
PASSWORD="admin"
WEBDAV_URL="$NEXTCLOUD_URL/remote.php/dav/files/$USERNAME/Team%20folder/en"
DEMO_DIR="demo-data/en"

echo "🚀 Uploading IntraVox demo data via WebDAV..."
echo "Target: $WEBDAV_URL"
echo ""

# Check if demo-data exists
if [ ! -d "$DEMO_DIR" ]; then
    echo "❌ Error: $DEMO_DIR not found"
    exit 1
fi

# Function to upload a file via WebDAV
upload_file() {
    local local_path="$1"
    local remote_path="$2"

    echo "📤 Uploading: $local_path -> $remote_path"
    curl -k -s -u "$USERNAME:$PASSWORD" \
         -T "$local_path" \
         "$WEBDAV_URL/$remote_path"
}

# Function to create a directory via WebDAV
create_dir() {
    local dir_path="$1"

    echo "📁 Creating directory: $dir_path"
    curl -k -s -u "$USERNAME:$PASSWORD" \
         -X MKCOL \
         "$WEBDAV_URL/$dir_path" 2>/dev/null || true
}

# Upload home.json and navigation.json
echo "=== Uploading root files ==="
upload_file "$DEMO_DIR/home.json" "home.json"
upload_file "$DEMO_DIR/navigation.json" "navigation.json"
echo ""

# Create images directory
echo "=== Creating images directory ==="
create_dir "images"
echo ""

# Upload all page folders
echo "=== Uploading page folders ==="
for page_dir in "$DEMO_DIR"/*/ ; do
    page_name=$(basename "$page_dir")

    # Skip images directory
    if [ "$page_name" == "images" ]; then
        continue
    fi

    echo "--- Processing page: $page_name ---"

    # Create page directory
    create_dir "$page_name"

    # Create page/images subdirectory
    create_dir "$page_name/images"

    # Upload page JSON file
    if [ -f "$page_dir/${page_name}.json" ]; then
        upload_file "$page_dir/${page_name}.json" "$page_name/${page_name}.json"
    fi

    echo ""
done

echo "✅ Demo data uploaded successfully via WebDAV!"
echo ""
echo "🔍 Now verify in Nextcloud:"
echo "   1. Go to Files app → Team folder → en"
echo "   2. You should see all demo page folders"
echo "   3. Try linking them in IntraVox navigation editor"
