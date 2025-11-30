# IntraVox Demo Data

This directory contains demonstration content for IntraVox in multiple languages.

## Available Languages

- **nl/** - Dutch demo data
- **en/** - English demo data

## Structure

Each language folder follows this structure:

```
{lang}/
├── home.json              # Home page content
├── navigation.json        # Navigation menu structure
├── footer.json            # Footer content
├── manifest.json          # Import order and page hierarchy
├── images/                # Shared images for all pages
└── {page-folders}/        # Individual page folders
    ├── {page}.json        # Page content
    └── images/            # Page-specific images
```

## Importing Demo Data

### Via Web Interface

1. Open IntraVox app in Nextcloud
2. Click on "Import Demo Data" in settings
3. Select language to import
4. Confirm import

### Via Command Line

```bash
# Import Dutch demo data
sudo -u www-data php occ intravox:demo:import nl

# Import English demo data
sudo -u www-data php occ intravox:demo:import en
```

### Via Deployment Script

```bash
./deploy-demo-data.sh nl
./deploy-demo-data.sh en
```

## Content Overview

### Dutch Demo (nl)

| Section | Pages | Description |
|---------|-------|-------------|
| Home | 1 | Welcome page with feature highlights |
| Afdelingen | 8+ | Department pages (Marketing, HR, IT, Sales) |
| Nieuws | 3 | News and announcements |
| Documentatie | 5 | Documentation and guides |
| Contact | 1 | Contact information |

### English Demo (en)

| Section | Pages | Description |
|---------|-------|-------------|
| Home | 1 | Welcome page |
| About | 3 | About, Careers, Contact |
| News | 4 | Press, Blog, Updates |
| Documentation | 4 | Guides and tutorials |
| Support | 3 | Help and community |
| Events | 3 | Conferences, Meetups, Webinars |

## Images

Images are organized per page folder to ensure proper import:
- Each page folder can have an `images/` subfolder
- Images are referenced by filename only (e.g., `"src": "hero.jpg"`)
- Import process copies images to the correct location in Nextcloud

## Creating New Demo Data

1. Create a new language folder (e.g., `de/`)
2. Add `home.json` with home page content
3. Add `navigation.json` with menu structure
4. Add `footer.json` with footer content
5. Create `manifest.json` listing all pages in import order
6. Create page folders with `{page}.json` files
7. Add images to respective `images/` folders

### manifest.json Format

```json
{
  "pages": [
    "home",
    "about/about",
    "about/contact/contact",
    "news/news"
  ],
  "images": [
    "images/hero.jpg",
    "images/features.jpg"
  ]
}
```

## Customization

To customize demo data for your organization:

1. Copy an existing language folder
2. Edit page JSON files with your content
3. Replace images with your assets
4. Update navigation.json with your menu structure
5. Import using any method above

## Notes

- **Warning:** Importing demo data will overwrite existing content!
- Images should be optimized for web (recommended max 1920px width)
- All text content supports Markdown formatting
- Demo data respects the Language-First folder structure

---

**Last Updated:** 2025-11-30
