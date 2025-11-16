#!/bin/bash

# Download royalty-free images from Unsplash for IntraVox demo pages
# All images are from Unsplash and free to use under the Unsplash License

set -e

DEMO_DIR="demo-data/en"

echo "📸 Downloading demo images from Unsplash..."
echo ""

# Create images directories
mkdir -p "$DEMO_DIR/images"
mkdir -p "$DEMO_DIR/about/images"
mkdir -p "$DEMO_DIR/contact/images"
mkdir -p "$DEMO_DIR/documentation/images"
mkdir -p "$DEMO_DIR/downloads/images"
mkdir -p "$DEMO_DIR/news/images"
mkdir -p "$DEMO_DIR/product-intravox/images"
mkdir -p "$DEMO_DIR/service-consulting/images"
mkdir -p "$DEMO_DIR/service-support/images"
mkdir -p "$DEMO_DIR/service-training/images"
mkdir -p "$DEMO_DIR/team-development/images"
mkdir -p "$DEMO_DIR/team-sales/images"
mkdir -p "$DEMO_DIR/team-support/images"

# Home page - Modern office workspace
echo "  📥 Downloading home page image..."
curl -L "https://images.unsplash.com/photo-1497366216548-37526070297c?w=1200&q=80" \
  -o "$DEMO_DIR/images/home-hero.jpg"

# About page - Team collaboration
echo "  📥 Downloading about page image..."
curl -L "https://images.unsplash.com/photo-1522071820081-009f0129c71c?w=1200&q=80" \
  -o "$DEMO_DIR/about/images/team-collaboration.jpg"

# Contact page - Modern office building
echo "  📥 Downloading contact page image..."
curl -L "https://images.unsplash.com/photo-1486406146926-c627a92ad1ab?w=1200&q=80" \
  -o "$DEMO_DIR/contact/images/office-building.jpg"

# Documentation page - Books and learning
echo "  📥 Downloading documentation page image..."
curl -L "https://images.unsplash.com/photo-1524995997946-a1c2e315a42f?w=1200&q=80" \
  -o "$DEMO_DIR/documentation/images/documentation.jpg"

# Downloads page - Digital downloads concept
echo "  📥 Downloading downloads page image..."
curl -L "https://images.unsplash.com/photo-1551288049-bebda4e38f71?w=1200&q=80" \
  -o "$DEMO_DIR/downloads/images/data-charts.jpg"

# News page - Newspaper/news concept
echo "  📥 Downloading news page image..."
curl -L "https://images.unsplash.com/photo-1504711434969-e33886168f5c?w=1200&q=80" \
  -o "$DEMO_DIR/news/images/news.jpg"

# Product IntraVox page - Modern technology
echo "  📥 Downloading product page image..."
curl -L "https://images.unsplash.com/photo-1460925895917-afdab827c52f?w=1200&q=80" \
  -o "$DEMO_DIR/product-intravox/images/product-showcase.jpg"

# Service Consulting page - Business meeting
echo "  📥 Downloading consulting page image..."
curl -L "https://images.unsplash.com/photo-1556761175-b413da4baf72?w=1200&q=80" \
  -o "$DEMO_DIR/service-consulting/images/consulting.jpg"

# Service Support page - Customer support
echo "  📥 Downloading support page image..."
curl -L "https://images.unsplash.com/photo-1486312338219-ce68d2c6f44d?w=1200&q=80" \
  -o "$DEMO_DIR/service-support/images/support.jpg"

# Service Training page - Training/workshop
echo "  📥 Downloading training page image..."
curl -L "https://images.unsplash.com/photo-1524178232363-1fb2b075b655?w=1200&q=80" \
  -o "$DEMO_DIR/service-training/images/training.jpg"

# Team Development page - Developers coding
echo "  📥 Downloading development team image..."
curl -L "https://images.unsplash.com/photo-1531482615713-2afd69097998?w=1200&q=80" \
  -o "$DEMO_DIR/team-development/images/developers.jpg"

# Team Sales page - Business professionals
echo "  📥 Downloading sales team image..."
curl -L "https://images.unsplash.com/photo-1556761175-4b46a572b786?w=1200&q=80" \
  -o "$DEMO_DIR/team-sales/images/sales-team.jpg"

# Team Support page - Support team
echo "  📥 Downloading support team image..."
curl -L "https://images.unsplash.com/photo-1573496359142-b8d87734a5a2?w=1200&q=80" \
  -o "$DEMO_DIR/team-support/images/support-team.jpg"

echo ""
echo "✅ All demo images downloaded successfully!"
echo ""
echo "📋 Images downloaded:"
echo "   - Home: Modern office workspace"
echo "   - About: Team collaboration"
echo "   - Contact: Modern office building"
echo "   - Documentation: Books and learning"
echo "   - Downloads: Data and charts"
echo "   - News: News concept"
echo "   - Product: Technology showcase"
echo "   - Consulting: Business meeting"
echo "   - Support: Customer support"
echo "   - Training: Workshop/training"
echo "   - Development Team: Developers coding"
echo "   - Sales Team: Business professionals"
echo "   - Support Team: Support team"
echo ""
