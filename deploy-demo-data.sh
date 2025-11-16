#!/bin/bash

# Deploy IntraVox demo data to server using OCC import command
# This script uploads demo data and imports it properly into Nextcloud's file cache

set -e

# Configuration
SERVER="145.38.191.31"
SERVER_USER="rdekker"
SSH_KEY="$HOME/.ssh/sur"
DEMO_DIR="demo-data/en"
SERVER_TEMP_DIR="/tmp/intravox-demo-data"

# Colors
GREEN='\033[0;32m'
BLUE='\033[0;34m'
RED='\033[0;31m'
NC='\033[0m' # No Color

echo -e "${BLUE}🚀 Deploying IntraVox demo data...${NC}"
echo ""

# Check if demo-data exists
if [ ! -d "$DEMO_DIR" ]; then
    echo -e "${RED}❌ Error: $DEMO_DIR not found${NC}"
    exit 1
fi

# Step 1: Create tarball of demo data
echo -e "${BLUE}📦 Step 1: Creating demo data package...${NC}"
tar -czf /tmp/intravox-demo-en.tar.gz -C demo-data en
echo -e "${GREEN}✅ Demo data package created${NC}"
echo ""

# Step 2: Upload to server
echo -e "${BLUE}📤 Step 2: Uploading demo data to server...${NC}"
ssh -i "$SSH_KEY" "$SERVER_USER@$SERVER" "mkdir -p $SERVER_TEMP_DIR"
scp -i "$SSH_KEY" /tmp/intravox-demo-en.tar.gz "$SERVER_USER@$SERVER:$SERVER_TEMP_DIR/"
echo -e "${GREEN}✅ Upload complete${NC}"
echo ""

# Step 3: Extract on server
echo -e "${BLUE}📂 Step 3: Extracting demo data on server...${NC}"
ssh -i "$SSH_KEY" "$SERVER_USER@$SERVER" "cd $SERVER_TEMP_DIR && tar -xzf intravox-demo-en.tar.gz && chmod -R 755 en/"
echo -e "${GREEN}✅ Extraction complete${NC}"
echo ""

# Step 4: Run OCC import command
echo -e "${BLUE}🔧 Step 4: Running OCC import command...${NC}"
echo -e "${BLUE}This will import pages and register them in Nextcloud's file cache${NC}"
echo ""

ssh -i "$SSH_KEY" "$SERVER_USER@$SERVER" << 'ENDSSH'
cd /var/www/nextcloud
sudo -u www-data php occ intravox:import /tmp/intravox-demo-data/en --language=en --user=admin
ENDSSH

echo ""
echo -e "${GREEN}✅ Demo data deployment complete!${NC}"
echo ""

# Cleanup
echo -e "${BLUE}🧹 Cleaning up...${NC}"
rm -f /tmp/intravox-demo-en.tar.gz
ssh -i "$SSH_KEY" "$SERVER_USER@$SERVER" "rm -rf $SERVER_TEMP_DIR"
echo -e "${GREEN}✅ Cleanup complete${NC}"
echo ""

echo -e "${BLUE}🎉 Success! Demo pages are now available in IntraVox${NC}"
echo ""
echo -e "${BLUE}📋 Next steps:${NC}"
echo "   1. Open IntraVox at https://145.38.191.31/apps/intravox"
echo "   2. Go to Edit Navigation"
echo "   3. Link the demo pages in your navigation structure"
echo "   4. Pages should now appear in the page selector!"
echo ""
