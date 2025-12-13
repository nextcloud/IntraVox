# IntraVox Administrator Guide

This guide covers installation, configuration, and maintenance of IntraVox for Nextcloud administrators.

**Related documentation:**
- [Admin Settings Guide](ADMIN_SETTINGS.md) - Demo data and video services configuration
- [Engagement Admin Guide](ENGAGEMENT_ADMIN.md) - Reactions and comments configuration
- [Authorization Guide](AUTHORIZATION.md) - Permissions and access control
- [Architecture](ARCHITECTURE.md) - Technical architecture
- [Engagement Architecture](ENGAGEMENT_ARCHITECTURE.md) - Engagement system technical details

## System Requirements

| Component | Minimum | Recommended |
|-----------|---------|-------------|
| Nextcloud | 32+ | 32+ |
| PHP | 8.1+ | 8.2+ |
| PHP memory_limit | 256MB | 512MB |
| GroupFolders app | Required | Required |

> **Note**: The default PHP memory_limit of 128MB is insufficient for IntraVox. Demo data installation and large pages require at least 256MB. Increase this in your `php.ini`:
> ```ini
> memory_limit = 512M
> ```

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

## Permission Groups

IntraVox automatically creates two permission groups during installation:

| Group | Purpose | Permissions |
|-------|---------|-------------|
| **IntraVox Admins** | Full administrative access | Read, Write, Create, Delete, Share |
| **IntraVox Users** | Standard read access | Read |

### Automatic Admin Synchronization

When IntraVox is installed or re-enabled, **all Nextcloud administrators** (members of the `admin` group) are automatically added to the `IntraVox Admins` group. This ensures:

- All NC admins have full IntraVox permissions
- CLI installations (`occ app:enable intravox`) work correctly
- Consistent behavior regardless of who installs the app

### Adding Users

To give users access to IntraVox:

1. **For read access**: Add users to the `IntraVox Users` group
2. **For admin access**: Add users to the `IntraVox Admins` group (or make them a Nextcloud admin)

You can manage group membership via:
- Nextcloud Admin → Users → Edit user → Groups
- Command line: `occ group:adduser "IntraVox Users" username`

## Initial Setup

### Automatic Setup

When IntraVox is first accessed, it automatically:
1. Creates the permission groups (`IntraVox Admins` and `IntraVox Users`)
2. Syncs Nextcloud admins to `IntraVox Admins`
3. Creates a GroupFolder named "IntraVox"
4. Sets up the basic folder structure
5. Initializes navigation files

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

IntraVox includes demo content to help you get started quickly. Demo data can be installed and managed via **Nextcloud Admin Settings** → **IntraVox**.

### Quick Start

1. Go to **Nextcloud Admin Settings** → **IntraVox**
2. Click **Install** next to your preferred language
3. The GroupFolder and permission groups are created automatically

### Available Languages

| Language | Content |
|----------|---------|
| Nederlands 🇳🇱 | Full intranet |
| English 🇬🇧 | Full intranet |
| Deutsch 🇩🇪 | Homepage only |
| Français 🇫🇷 | Homepage only |

### Command Line Installation

```bash
sudo -u www-data php occ intravox:import-demo-data --language=en
```

See [ADMIN_SETTINGS.md](ADMIN_SETTINGS.md) for detailed demo data management.

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

### Images/Videos Not Loading

1. Verify files exist in the `_media/` subfolder
2. Check file permissions
3. Ensure file paths in JSON match actual filenames
4. Try re-uploading the file via Nextcloud Files
5. For videos: Check that the video domain is whitelisted in Admin Settings

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

## Video Widget Configuration

IntraVox includes a Video Widget for embedding videos from external platforms or uploading local video files. Administrators control which platforms are allowed via **Nextcloud Admin Settings** → **IntraVox** → **Video Services**.

### Default Services

| Service | Privacy |
|---------|---------|
| YouTube (privacy mode) | Enhanced - no tracking |
| Vimeo | Standard |

### Quick Configuration

1. Go to **Admin Settings** → **IntraVox** → **Video Services**
2. Toggle services on/off
3. Add custom domains for corporate video servers

### Security

- Only whitelisted domains can be embedded
- Videos from unknown domains are blocked
- Only HTTPS sources allowed

See [ADMIN_SETTINGS.md](ADMIN_SETTINGS.md) for detailed video configuration.

## Security Considerations

1. **Permissions**: Use principle of least privilege
2. **Content**: Review user-generated content for sensitive information
3. **Media files**: Be aware that uploaded images and videos are accessible to all users with read permission
4. **External links**: Content editors can add external links - review navigation regularly
5. **Video embeds**: Only allow trusted video platforms via the admin settings

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
