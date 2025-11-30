# IntraVox Deployment Guide

## Overview

IntraVox uses several deployment scripts for different purposes:

| Script | Purpose |
|--------|---------|
| `deploy.sh` | Deploy application to production server |
| `deploy-demo-data.sh` | Deploy demo data (supports multiple languages) |

## Prerequisites

- Node.js 18+ and npm installed
- SSH access to server with key `~/.ssh/sur`
- Write access to Nextcloud apps directory

## Quick Start

### Deploy Application

```bash
./deploy.sh
```

This will:
1. Build the frontend (`npm run build`)
2. Create deployment package
3. Upload to server via rsync
4. Install app in Nextcloud
5. Set correct permissions

### Deploy Demo Data

```bash
# Deploy Dutch demo data
./deploy-demo-data.sh nl

# Deploy English demo data
./deploy-demo-data.sh en
```

**Warning:** Demo data will overwrite existing content!

---

## Build Process

IntraVox uses Webpack to build the Vue 3 frontend.

### Manual Build

```bash
npm install        # First time only
npm run build      # Build to js/ directory
```

### Build Output

The build creates files in `js/`:
- `intravox-main.js` - Main application bundle
- Chunk files for code splitting

---

## Server Configuration

### Production Server

- **Host:** 145.38.195.41
- **App Path:** `/var/www/html/apps/intravox`
- **Data Path:** Groupfolder via Nextcloud

### SSH Access

```bash
ssh rdekker@145.38.195.41
```

---

## Troubleshooting

### Build Errors

Clean rebuild:
```bash
rm -rf node_modules package-lock.json
npm install
npm run build
```

### Permission Errors

Fix server permissions:
```bash
ssh rdekker@145.38.195.41 "sudo chown -R www-data:www-data /var/www/html/apps/intravox"
```

### App Not Showing

Re-enable the app:
```bash
ssh rdekker@145.38.195.41 "sudo -u www-data php /var/www/html/occ app:enable intravox"
```

### Cache Issues

Clear Nextcloud cache:
```bash
ssh rdekker@145.38.195.41 "sudo -u www-data php /var/www/html/occ maintenance:repair"
```

---

## File Structure

```
IntraVox/
├── src/                    # Vue 3 source files
│   ├── components/         # Vue components
│   └── App.vue            # Main component
├── js/                     # Built JavaScript
├── lib/                    # PHP backend
│   ├── Controller/        # API controllers
│   └── Service/           # Business logic
├── appinfo/               # Nextcloud app metadata
├── demo-data/             # Demo content
│   ├── nl/               # Dutch demo data
│   └── en/               # English demo data
├── deploy.sh              # Main deployment script
└── package.json           # Dependencies
```

---

## Demo Data Structure

Each language folder contains:

```
demo-data/{lang}/
├── home.json              # Home page content
├── navigation.json        # Navigation structure
├── footer.json            # Footer content
├── manifest.json          # Page import order
├── images/                # Shared images
└── {page-folders}/        # Individual page folders
    ├── page.json          # Page content
    └── images/            # Page-specific images
```

---

## Version Management

- Version defined in `appinfo/info.xml` and `package.json`
- Use `npm run build` which syncs versions automatically
- Tag releases with `git tag v0.x.x`

See `RELEASE.md` for detailed release procedures.

---

**Last Updated:** 2025-11-30
