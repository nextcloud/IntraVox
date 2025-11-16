#!/bin/bash

# Script to create IntraVox demo pages via API
# This ensures pages are properly registered in Nextcloud's file cache

set -e

# Configuration
NEXTCLOUD_URL="http://localhost"
USERNAME="admin"
PASSWORD="admin"
API_URL="$NEXTCLOUD_URL/apps/intravox/api/pages"
DEMO_DIR="demo-data/en"

# Colors for output
GREEN='\033[0;32m'
BLUE='\033[0;34m'
RED='\033[0;31m'
NC='\033[0m' # No Color

echo -e "${BLUE}🚀 Creating IntraVox demo pages via API...${NC}"
echo ""

# Check if demo-data exists
if [ ! -d "$DEMO_DIR" ]; then
    echo -e "${RED}❌ Error: $DEMO_DIR not found${NC}"
    exit 1
fi

# Function to create a page via API
create_page() {
    local json_file="$1"
    local page_name=$(basename "$json_file" .json)

    echo -e "${BLUE}📄 Creating page: $page_name${NC}"

    # Read the JSON file
    local page_data=$(cat "$json_file")

    # Create the page via API (via SSH to localhost to avoid domain issues)
    local response=$(ssh -i ~/.ssh/sur rdekker@145.38.191.31 \
        "curl -s -u '$USERNAME:$PASSWORD' \
         -H 'Content-Type: application/json' \
         -X POST \
         '$API_URL' \
         -d '$page_data'")

    # Check if creation was successful
    if echo "$response" | grep -q '"id"'; then
        echo -e "${GREEN}✅ Successfully created: $page_name${NC}"
    else
        echo -e "${RED}❌ Failed to create: $page_name${NC}"
        echo "Response: $response"
    fi
}

# First, clean up old demo folders from filesystem (they're not in cache anyway)
echo -e "${BLUE}🧹 Cleaning up old demo folders...${NC}"
ssh -i ~/.ssh/sur rdekker@145.38.191.31 \
    "sudo rm -rf /var/www/nextcloud/data/__groupfolders/4/files/en/{about,contact,documentation,downloads,news,product-intravox,service-consulting,service-support,service-training,team-development,team-sales,team-support} 2>/dev/null" || true
echo -e "${GREEN}✅ Cleanup complete${NC}"
echo ""

# Create all demo pages
echo -e "${BLUE}=== Creating demo pages ===${NC}"
echo ""

# Process each page folder
for page_dir in "$DEMO_DIR"/*/ ; do
    page_name=$(basename "$page_dir")

    # Skip images directory
    if [ "$page_name" == "images" ]; then
        continue
    fi

    # Check if JSON file exists
    json_file="$page_dir/${page_name}.json"
    if [ -f "$json_file" ]; then
        create_page "$json_file"
        sleep 0.5  # Small delay between requests
    fi
done

echo ""
echo -e "${GREEN}✅ Demo pages creation complete!${NC}"
echo ""
echo -e "${BLUE}🔍 Next steps:${NC}"
echo "   1. Open IntraVox in your browser"
echo "   2. Go to Edit Navigation"
echo "   3. Link the demo pages in your navigation structure"
echo "   4. The pages should now appear in the page selector!"
