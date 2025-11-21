#!/bin/bash

# IntraVox Demo Images Download Script
# Downloads royalty-free images from Unsplash for demo purposes

set -e

echo "📷 IntraVox Demo Images Download"
echo "================================="
echo ""
echo "Using Unsplash royalty-free images"
echo ""

# Target directory
IMAGES_DIR="$(pwd)/en/📷 images"

# Create images directory if it doesn't exist
mkdir -p "$IMAGES_DIR"

cd "$IMAGES_DIR"

echo "📁 Downloading images to: $IMAGES_DIR"
echo ""

# Download images using specific Unsplash photo IDs (royalty-free)
# These are high-quality images suitable for an intranet demo

echo "📥 1/6 Downloading team-hero.jpg (Team collaboration)..."
curl -L "https://images.unsplash.com/photo-1522071820081-009f0129c71c?w=1920&q=80" \
  -o "team-hero.jpg" 2>/dev/null

echo "📥 2/6 Downloading features-showcase.jpg (Modern office)..."
curl -L "https://images.unsplash.com/photo-1497366216548-37526070297c?w=1920&q=80" \
  -o "features-showcase.jpg" 2>/dev/null

echo "📥 3/6 Downloading modern-workspace.jpg (Digital workspace)..."
curl -L "https://images.unsplash.com/photo-1497366811353-6870744d04b2?w=1920&q=80" \
  -o "modern-workspace.jpg" 2>/dev/null

echo "📥 4/6 Downloading secure-platform.jpg (Security/tech)..."
curl -L "https://images.unsplash.com/photo-1563986768609-322da13575f3?w=1920&q=80" \
  -o "secure-platform.jpg" 2>/dev/null

echo "📥 5/6 Downloading collaboration.jpg (Team working together)..."
curl -L "https://images.unsplash.com/photo-1600880292203-757bb62b4baf?w=1920&q=80" \
  -o "collaboration.jpg" 2>/dev/null

echo "📥 6/6 Downloading open-source.jpg (Code/development)..."
curl -L "https://images.unsplash.com/photo-1461749280684-dccba630e2f6?w=1920&q=80" \
  -o "open-source.jpg" 2>/dev/null

# Download additional images for other demo pages

echo "📥 7/16 Downloading hero-office.jpg (Office space)..."
curl -L "https://images.unsplash.com/photo-1497215728101-856f4ea42174?w=1920&q=80" \
  -o "hero-office.jpg" 2>/dev/null

echo "📥 8/16 Downloading about-mission.jpg (Mission/vision)..."
curl -L "https://images.unsplash.com/photo-1552664730-d307ca884978?w=1920&q=80" \
  -o "about-mission.jpg" 2>/dev/null

echo "📥 9/16 Downloading team-group.jpg (Team photo)..."
curl -L "https://images.unsplash.com/photo-1556761175-b413da4baf72?w=1920&q=80" \
  -o "team-group.jpg" 2>/dev/null

echo "📥 10/16 Downloading support-help.jpg (Customer support)..."
curl -L "https://images.unsplash.com/photo-1553877522-43269d4ea984?w=1920&q=80" \
  -o "support-help.jpg" 2>/dev/null

echo "📥 11/16 Downloading documentation.jpg (Documentation/books)..."
curl -L "https://images.unsplash.com/photo-1481627834876-b7833e8f5570?w=1920&q=80" \
  -o "documentation.jpg" 2>/dev/null

echo "📥 12/16 Downloading news-press.jpg (News/media)..."
curl -L "https://images.unsplash.com/photo-1585829365295-ab7cd400c167?w=1920&q=80" \
  -o "news-press.jpg" 2>/dev/null

echo "📥 13/16 Downloading events.jpg (Event/conference)..."
curl -L "https://images.unsplash.com/photo-1540575467063-178a50c2df87?w=1920&q=80" \
  -o "events.jpg" 2>/dev/null

echo "📥 14/16 Downloading careers.jpg (Hiring/careers)..."
curl -L "https://images.unsplash.com/photo-1521737711867-e3b97375f902?w=1920&q=80" \
  -o "careers.jpg" 2>/dev/null

echo "📥 15/16 Downloading contact.jpg (Communication/contact)..."
curl -L "https://images.unsplash.com/photo-1423666639041-f56000c27a9a?w=1920&q=80" \
  -o "contact.jpg" 2>/dev/null

echo "📥 16/16 Downloading downloads.jpg (Download/technology)..."
curl -L "https://images.unsplash.com/photo-1551033406-611cf9a28f67?w=1920&q=80" \
  -o "downloads.jpg" 2>/dev/null

echo ""
echo "✅ All images downloaded!"
echo ""

# Display file sizes
echo "📊 Image inventory:"
ls -lh *.jpg 2>/dev/null | awk '{printf "  %-30s %8s\n", $9, $5}' || echo "  No images found"

echo ""
echo "================================="
echo "✅ Download Complete!"
echo "================================="
echo ""
echo "📁 Location: $IMAGES_DIR"
echo "📷 Images: 16 royalty-free photos from Unsplash"
echo "📝 License: Free to use (Unsplash License)"
echo ""
echo "Next step: Run ./deploy-demo-data.sh to upload to server"
echo ""
