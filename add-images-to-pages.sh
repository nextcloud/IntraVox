#!/bin/bash

# Add header images to all demo pages
# This script adds an image widget at the top of each page

set -e

DEMO_DIR="demo-data/en"

echo "🖼️  Adding images to demo pages..."
echo ""

# Function to add image widget to a page
add_image_to_page() {
    local page_dir="$1"
    local page_id="$2"
    local image_file="$3"
    local alt_text="$4"

    local json_file="$DEMO_DIR/$page_dir/$page_id.json"

    if [ ! -f "$json_file" ]; then
        echo "  ⚠️  Skipping $page_id: file not found"
        return
    fi

    # Use jq to add image widget at the beginning of the first row
    jq --arg img "$image_file" --arg alt "$alt_text" '
        .layout.rows[0].widgets |= [{
            "type": "image",
            "column": 1,
            "order": 1,
            "src": $img,
            "alt": $alt
        }] + map(.order += 1)
    ' "$json_file" > "$json_file.tmp" && mv "$json_file.tmp" "$json_file"

    echo "  ✅ Added image to $page_id"
}

# Add images to pages
add_image_to_page "about" "about" "team-collaboration.jpg" "Team collaboration"
add_image_to_page "contact" "contact" "office-building.jpg" "Modern office building"
add_image_to_page "documentation" "documentation" "documentation.jpg" "Documentation and learning"
add_image_to_page "downloads" "downloads" "data-charts.jpg" "Data and analytics"
add_image_to_page "news" "news" "news.jpg" "Latest news"
add_image_to_page "product-intravox" "product-intravox" "product-showcase.jpg" "IntraVox product showcase"
add_image_to_page "service-consulting" "service-consulting" "consulting.jpg" "Business consulting"
add_image_to_page "service-support" "service-support" "support.jpg" "Customer support"
add_image_to_page "service-training" "service-training" "training.jpg" "Professional training"
add_image_to_page "team-development" "team-development" "developers.jpg" "Development team"
add_image_to_page "team-sales" "team-sales" "sales-team.jpg" "Sales team"
add_image_to_page "team-support" "team-support" "support-team.jpg" "Support team"

echo ""
echo "✅ All images added to pages!"
