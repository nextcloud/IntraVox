#!/bin/bash

# Create a temporary directory with all unique images
TEMP_IMG="/tmp/intravox-all-images"
mkdir -p "$TEMP_IMG"

# Download all images to temp directory (using the previously created ones)
cd /Users/rikdekker/Documents/Development/IntraVox/demo-data
find . -name "*.jpg" -exec cp {} "$TEMP_IMG/" \; 2>/dev/null

# Now create images folders and copy relevant images based on JSON references
cd /Users/rikdekker/Documents/Development/IntraVox/demo-data/en

# Root level (home page)
mkdir -p images
grep -o '"src": "images/[^"]*"' home.json | sed 's/"src": "images\///' | sed 's/"//' | while read img; do
    cp "$TEMP_IMG/$img" images/ 2>/dev/null
done

# Function to process a page folder
process_page() {
    local page_dir="$1"
    local json_file="$2"
    
    if [ -f "$json_file" ]; then
        mkdir -p "$page_dir/images"
        grep -o '"src": "images/[^"]*"' "$json_file" | sed 's/"src": "images\///' | sed 's/"//' | while read img; do
            cp "$TEMP_IMG/$img" "$page_dir/images/" 2>/dev/null
        done
    fi
}

# Process all subdirectories
for json in $(find . -name "*.json" -not -name "home.json" -not -name "navigation.json" -not -name "footer.json"); do
    dir=$(dirname "$json")
    process_page "$dir" "$json"
done

echo "✓ All image folders created and populated"
