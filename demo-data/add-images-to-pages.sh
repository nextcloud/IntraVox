#!/bin/bash

# IntraVox Demo - Add Images to Pages
# Copies downloaded images to the correct page folders

set -e

echo "📸 IntraVox Demo - Distribute Images to Pages"
echo "=============================================="
echo ""

SOURCE_DIR="$(pwd)/en/📷 images"
DEMO_DIR="$(pwd)/en"

# Check if source images exist
if [ ! -d "$SOURCE_DIR" ]; then
    echo "❌ Error: Images directory not found at $SOURCE_DIR"
    echo "   Run ./download-demo-images.sh first"
    exit 1
fi

echo "📁 Source: $SOURCE_DIR"
echo "📁 Target: $DEMO_DIR"
echo ""

# Helper function to copy image to a page's images folder
copy_to_page() {
    local page_dir="$1"
    local image_name="$2"
    local full_page_path="$DEMO_DIR/$page_dir"
    local images_folder="$full_page_path/📷 images"

    # Create images folder if it doesn't exist
    mkdir -p "$images_folder"

    # Copy image
    if [ -f "$SOURCE_DIR/$image_name" ]; then
        cp "$SOURCE_DIR/$image_name" "$images_folder/"
        echo "  ✓ Copied $image_name to $page_dir"
    else
        echo "  ⚠️  Warning: $image_name not found in source"
    fi
}

echo "📋 Distributing images to pages..."
echo ""

# About page
echo "📄 About page:"
copy_to_page "about" "about-mission.jpg"

# Team pages
echo ""
echo "📄 Team pages:"
copy_to_page "team" "team-group.jpg"
copy_to_page "team" "team-collaboration.jpg"
copy_to_page "team/leadership" "team-diverse.jpg"
copy_to_page "team/management" "team-meeting.jpg"
copy_to_page "team" "team-benefits.jpg"
copy_to_page "team" "team-culture.jpg"

# Documentation pages
echo ""
echo "📄 Documentation pages:"
copy_to_page "documentation" "documentation.jpg"
copy_to_page "documentation/getting-started" "workspace-modern.jpg"
copy_to_page "documentation/user-guide" "books-learning.jpg"
copy_to_page "documentation/admin-guide" "tech-innovation.jpg"

# Support pages
echo ""
echo "📄 Support pages:"
copy_to_page "support" "support-help.jpg"
copy_to_page "support" "tutorial-learning.jpg"
copy_to_page "support" "community-together.jpg"

# News pages
echo ""
echo "📄 News pages:"
copy_to_page "news" "news-press.jpg"
copy_to_page "news" "blog-writing.jpg"

# Press page
echo ""
echo "📄 Press page:"
copy_to_page "press" "news-media.jpg"

# Events pages
echo ""
echo "📄 Events pages:"
copy_to_page "events" "events.jpg"
copy_to_page "events" "conference.jpg"
copy_to_page "events" "webinar-online.jpg"
copy_to_page "events" "conference-stage.jpg"

# Careers pages
echo ""
echo "📄 Careers pages:"
copy_to_page "about/careers" "careers.jpg"
copy_to_page "about/careers" "internship-program.jpg"

# Contact page
echo ""
echo "📄 Contact page:"
copy_to_page "contact" "contact.jpg"

# Downloads page
echo ""
echo "📄 Downloads page:"
copy_to_page "downloads" "downloads.jpg"

echo ""
echo "=============================================="
echo "✅ Image Distribution Complete!"
echo "=============================================="
echo ""
echo "📊 Summary:"
echo "  • Home page: 6 images (already in en/📷 images/)"
echo "  • About: 1 image"
echo "  • Team: 6 images"
echo "  • Documentation: 4 images"
echo "  • Support: 3 images"
echo "  • News: 2 images"
echo "  • Press: 1 image"
echo "  • Events: 4 images"
echo "  • Careers: 2 images"
echo "  • Contact: 1 image"
echo "  • Downloads: 1 image"
echo ""
echo "  Total: 31 images distributed"
echo ""
echo "Next: Run ./deploy-demo-data.sh to upload everything"
echo ""
