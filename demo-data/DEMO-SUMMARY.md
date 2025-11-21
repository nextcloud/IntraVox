# IntraVox Demo Intranet

A complete, professional demo intranet showcasing all IntraVox features.

## 📋 Contents

### Navigation Structure (3 levels)
```
Home
Company
  ├─ About Us
  ├─ News & Updates
  └─ Our Teams
      ├─ Sales Team
      ├─ Development Team
      └─ Support Team
Products
  ├─ IntraVox
  └─ Services
      ├─ Consulting
      ├─ Training
      └─ Support
Resources
  ├─ Documentation
  └─ Downloads
Contact
```

### Pages Created (14 total)

1. **home.json** - Welcome page with hero section and feature showcase
   - Hero banner with call-to-action
   - 3-column feature grid
   - Multiple row backgrounds
   - Dividers for visual separation

2. **about.json** - Company information
   - Mission statement
   - Core values (3 columns)
   - Company story
   - Call-to-action

3. **news.json** - News and updates
   - Multiple news items with dates
   - Mixed 1 and 2 column layouts
   - Background colors for emphasis

4. **team-sales.json** - Sales team page
   - Team overview
   - Member profiles (3 columns)
   - Contact CTA

5. **team-development.json** - Development team page
   - Team responsibilities
   - Tech stack information
   - Member profiles

6. **team-support.json** - Support team page
   - Support services
   - Team member highlights
   - Support contact info

7. **product-intravox.json** - Main product page
   - Feature highlights (3 columns)
   - Use cases
   - Pricing information
   - Call-to-action

8. **service-consulting.json** - Consulting services
9. **service-training.json** - Training services
10. **service-support.json** - Support services

11. **documentation.json** - Documentation hub
    - Quick start guide
    - User/Admin/Developer guides (3 columns)

12. **downloads.json** - Downloads page
    - Latest release info
    - Resources (templates, examples, assets)

13. **contact.json** - Contact information
    - Contact details (2 columns)
    - Sales contact
    - Social media links

14. **navigation.json** - Three-level navigation structure

## ✨ Features Demonstrated

### Layout Features
- ✓ 1, 2, and 3 column layouts
- ✓ Background colors (primary, hover states)
- ✓ Hero sections with large images
- ✓ Dividers for visual separation
- ✓ Responsive design patterns

### Widget Types Used
- ✓ **Headings** (H1, H2, H3)
- ✓ **Text widgets** with markdown:
  - Bold, italic formatting
  - Bulleted lists
  - Numbered lists
  - Links within text
- ✓ **Images** (full-width and custom widths)
- ✓ **Link widgets** for CTAs
- ✓ **Divider widgets**

### Content Patterns
- ✓ Hero banners
- ✓ Feature showcases
- ✓ Team member profiles
- ✓ News/blog posts
- ✓ Product descriptions
- ✓ Contact information
- ✓ Documentation structure

## 📦 Installation

### Upload to Server
```bash
cd demo-data
./upload-to-server.sh
```

Or manually:
```bash
# Upload all JSON files
scp -r en/ user@server:/var/www/nextcloud/data/__groupfolders/4/files/

# Set permissions
ssh user@server "sudo chown -R www-data:www-data /var/www/nextcloud/data/__groupfolders/4/files/en"
ssh user@server "sudo chmod -R 770 /var/www/nextcloud/data/__groupfolders/4/files/en"

# Scan files
ssh user@server "sudo -u www-data php /var/www/nextcloud/occ files:scan --path=/__groupfolders/4/files/en"
```

### Add Images

Replace placeholder image references with real images:

1. Download royalty-free images from:
   - Unsplash (unsplash.com)
   - Pexels (pexels.com)
   - Pixabay (pixabay.com)

2. Upload to `en/images/` folder:
   - hero-collaboration.jpg
   - feature-editor.jpg
   - feature-collaborate.jpg
   - feature-layouts.jpg
   - cta-image.jpg
   - about-mission.jpg
   - news-release.jpg

3. Recommended search terms:
   - "team collaboration"
   - "modern office workspace"
   - "business meeting"
   - "digital workspace"

## 🎨 Customization

All content uses **markdown** in text widgets, making it easy to:
- Edit text formatting
- Add/remove bullet points
- Update links
- Modify headings

All pages use **Nextcloud design tokens**:
- `var(--color-primary-element)` - Primary brand color
- `var(--color-background-hover)` - Subtle background
- Automatically adapts to your Nextcloud theme

## 📊 Content Statistics

- **14 pages** with rich content
- **3-level navigation** structure
- **7 images** referenced (placeholders)
- **Multiple layouts** demonstrated
- **All widget types** showcased
- **100% markdown** content (no HTML)

## 🚀 Perfect For

- Demo presentations
- Sales pitches
- Training materials
- Starting template for new intranets
- Showcasing IntraVox capabilities

## 📝 Notes

- All content is in English
- Content is professional and business-focused
- Demonstrates best practices
- Ready for customization
- No external dependencies

---

**Created with IntraVox** - Transform your Nextcloud into a powerful collaboration space
