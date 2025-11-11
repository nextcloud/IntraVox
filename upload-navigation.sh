#!/bin/bash

# Upload navigation test data to Nextcloud server
# This script sends the navigation JSON via the API

SERVER="https://145.38.191.31"
NAVIGATION_FILE="/tmp/navigation-testdata.json"

echo "📤 Uploading navigation test data to $SERVER..."
echo ""

# Check if navigation file exists
if [ ! -f "$NAVIGATION_FILE" ]; then
    echo "❌ Error: Navigation file not found at $NAVIGATION_FILE"
    echo "Run: node create-navigation-testdata.js"
    exit 1
fi

# Read the navigation JSON
NAVIGATION_JSON=$(cat "$NAVIGATION_FILE")

# Upload via API (you'll need to authenticate)
echo "⚠️  Note: You need to upload this via the Nextcloud web interface:"
echo ""
echo "1. Open IntraVox in your browser"
echo "2. Click 'Edit Navigation'"
echo "3. Manually add items or use the browser console:"
echo ""
echo "// Paste this in browser console:"
echo "fetch('/apps/intravox/api/navigation', {"
echo "  method: 'POST',"
echo "  headers: {"
echo "    'Content-Type': 'application/json',"
echo "    'requesttoken': OC.requestToken"
echo "  },"
echo "  body: JSON.stringify($NAVIGATION_JSON)"
echo "}).then(r => r.json()).then(console.log)"
echo ""
echo "Or copy the content from: $NAVIGATION_FILE"
