# IntraVox Administrator Guide

This guide covers installation, configuration, and maintenance of IntraVox for Nextcloud administrators.

## Requirements

- Nextcloud 32 or higher
- PHP 8.1 or higher
- GroupFolders app installed and enabled

## Installation

### Option 1: Nextcloud App Store (Recommended)

1. Go to Nextcloud Admin → Apps
2. Search for "IntraVox"
3. Click "Download and enable"

### Option 2: Manual Installation

1. Download the latest release from GitHub
2. Extract to `custom_apps/intravox/`
3. Enable via Admin → Apps or `occ app:enable intravox`

```bash
cd /var/www/nextcloud/custom_apps
wget https://github.com/your-repo/intravox/releases/latest/download/intravox.tar.gz
tar -xzf intravox.tar.gz
sudo -u www-data php /var/www/nextcloud/occ app:enable intravox
```

## Initial Setup

### Automatic Setup

When IntraVox is first accessed, it automatically:
1. Creates a GroupFolder named "IntraVox"
2. Sets up the basic folder structure
3. Initializes navigation files

### Manual Setup

If automatic setup fails, create the GroupFolder manually:

1. Go to Admin → GroupFolders
2. Create a new folder named exactly "IntraVox"
3. Add at least one group with permissions

## GroupFolder Configuration

### Adding Groups

1. Go to Admin → GroupFolders
2. Find the "IntraVox" folder
3. Click "Add group"
4. Select the group and set permissions

### Recommended Group Setup

| Group | Purpose | Permissions |
|-------|---------|-------------|
| everyone / all-users | All employees | Read |
| intranet-editors | Content creators | Read, Write, Create |
| intranet-admins | Full administrators | All |

### Enabling ACL (Advanced Permissions)

For department-based access control:

1. In GroupFolders, click the settings icon for IntraVox
2. Enable "Advanced Permissions"
3. Navigate to subfolders in Nextcloud Files
4. Use the sharing panel to set ACL rules

See [AUTHORIZATION.md](AUTHORIZATION.md) for detailed permission setup.

## Language Configuration

IntraVox supports multiple languages. Each language has its own content folder:

```
IntraVox/
├── nl/           # Dutch content
├── en/           # English content
├── de/           # German content
└── fr/           # French content
```

### Adding a New Language

1. Create the language folder in IntraVox GroupFolder
2. Create `navigation.json` with navigation structure
3. Create `footer.json` with footer content
4. Create `home.json` for the homepage

Example minimal structure:
```
IntraVox/
└── es/
    ├── navigation.json
    ├── footer.json
    └── home.json
```

## Demo Data

IntraVox includes demo content to help you get started quickly. Demo data can be installed and managed directly from the Nextcloud Admin Settings.

### Installing Demo Data via Admin Settings

1. Go to **Nextcloud Admin Settings** → **IntraVox**
2. You'll see a table with all available demo data languages
3. Click **Install** next to the language you want to set up
4. The GroupFolder and permission groups are created automatically if they don't exist

The Admin Settings panel shows:
- **Status badges**: Installed, Not installed, or Empty folder
- **Content badges**: Full intranet vs Homepage only
- **Language flags** for easy identification

### Reinstalling Demo Data

If you want to reset demo content to its original state:

1. Go to **Admin Settings** → **IntraVox**
2. Click **Reinstall** next to the language
3. Confirm the action (this will delete all existing content for that language and replace it with fresh demo data)

### Available Demo Data

| Language | Flag | Content |
|----------|------|---------|
| Nederlands | 🇳🇱 | Full intranet with departments, news, documentation |
| English | 🇬🇧 | Full intranet (English translation) |
| Deutsch | 🇩🇪 | Full intranet (German translation) |
| Français | 🇫🇷 | Full intranet (French translation) |

### What Demo Data Includes

Each full demo installation contains:
- Homepage with welcome content
- Department pages (HR, IT, Sales, Marketing)
- News section with example articles
- Documentation pages
- Navigation structure with megamenu
- Sample images and icons

### Command Line Installation (Alternative)

For automated deployments, demo data can also be installed via command line:

```bash
sudo -u www-data php occ intravox:import-demo-data --language=en
```

Available options:
- `--language=nl|en|de|fr` - Language to install (default: en)
- `--force` - Overwrite existing content

### Customizing Demo Data

After installation, customize the content:

1. Edit JSON files directly in the IntraVox GroupFolder via Nextcloud Files
2. Replace images in the `images/` subfolders
3. Update navigation.json to match your organization's structure
4. Use the IntraVox editor for visual page editing

## Maintenance

### Clearing Cache

IntraVox uses Nextcloud's caching. To clear:

```bash
sudo -u www-data php occ files:scan --all
sudo -u www-data php occ maintenance:repair
```

### Backup

The IntraVox GroupFolder contains all content. Backup strategies:

1. **File backup**: Include the GroupFolder in your file backups
2. **Nextcloud backup**: Standard Nextcloud backup includes GroupFolders
3. **Export**: Copy the IntraVox folder structure for migration

### Log Files

IntraVox logs to the Nextcloud log. View with:

```bash
tail -f /var/www/nextcloud/data/nextcloud.log | grep -i intravox
```

Or in Nextcloud Admin → Logging.

## Troubleshooting

### "GroupFolder not found" Error

1. Verify GroupFolders app is enabled
2. Check that a folder named exactly "IntraVox" exists
3. Ensure the current user's group has access

```bash
sudo -u www-data php occ app:list | grep groupfolders
```

### Permissions Not Working

1. Check user is member of a group with GroupFolder access
2. Verify ACL rules if using Advanced Permissions
3. Check Nextcloud log for permission errors
4. Try: `occ groupfolders:scan`

### Pages Not Showing

1. Verify JSON files are valid (no syntax errors)
2. Check file permissions on the server
3. Ensure files are in correct folder structure
4. Check browser console for JavaScript errors

### Navigation Not Updating

1. Clear browser cache
2. Verify `navigation.json` is valid JSON
3. Check that page uniqueIds match between navigation and pages

### Images Not Loading

1. Verify images exist in the `images/` subfolder
2. Check file permissions
3. Ensure image paths in JSON match actual filenames
4. Try re-uploading the image via Nextcloud Files

## Performance

### Optimization Tips

1. **Image sizes**: Keep images under 500KB for faster loading
2. **Page structure**: Avoid deeply nested pages (max 3-4 levels)
3. **Navigation**: Keep navigation items reasonable (< 100 total)

### Caching

IntraVox relies on Nextcloud's caching layer. For best performance:
- Enable Redis or APCu caching in Nextcloud
- Use a CDN for static assets if available

## Updates

### Updating IntraVox

Via App Store:
1. Go to Admin → Apps → Updates
2. Update IntraVox

Via command line:
```bash
sudo -u www-data php occ app:update intravox
```

### Version Compatibility

Always check the CHANGELOG.md for breaking changes before updating.

## Security Considerations

1. **Permissions**: Use principle of least privilege
2. **Content**: Review user-generated content for sensitive information
3. **Images**: Be aware that uploaded images are accessible to all users with read permission
4. **External links**: Content editors can add external links - review navigation regularly

## Integration

### MetaVox Integration

If MetaVox is installed, IntraVox can:
- Use MetaVox department colors
- Respect MetaVox navigation structure
- Share theming settings

### Theming

IntraVox uses Nextcloud theming variables:
- Primary color from Nextcloud settings
- Logo from Nextcloud theming
- Font settings from Nextcloud

## Support

- GitHub Issues: Report bugs and feature requests
- Documentation: See other docs/ files for specific topics
- Nextcloud Forums: Community support
