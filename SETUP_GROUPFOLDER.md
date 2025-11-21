# IntraVox Groupfolder Setup

IntraVox requires a Nextcloud Groupfolder named **IntraVox** to store pages. The app automatically finds the folder by name, making it portable across servers.

## Automatic Setup (Recommended)

Run the setup command after installing the app:

```bash
sudo -u www-data php occ intravox:setup
```

This will:
- Create a groupfolder named **IntraVox** (if it doesn't exist)
- Give the **admin** group full access
- Create default homepage and subdirectories

## Manual Setup via Web UI

1. Log in as admin
2. Go to **Settings** → **Groupfolders**
3. Click **+ New folder**
4. Name it: **IntraVox** (exact name is important!)
5. Click on the folder to expand settings
6. Under **Groups**, add: **admin** (with all permissions)
7. Add other groups as needed

## Via OCC Command (Alternative)

```bash
# Create groupfolder named "IntraVox"
cd /var/www/nextcloud
sudo -u www-data php occ groupfolders:create "IntraVox"
# Output: Folder created with id X

# Give admin group access
sudo -u www-data php occ groupfolders:group X admin

# Set full permissions (31 = all)
sudo -u www-data php occ groupfolders:permissions X admin 31
```

**Note**: IntraVox automatically finds the folder by name, so no configuration needed!

## Verify Setup

1. Go to IntraVox app in Nextcloud
2. You should see the default homepage
3. The folder appears under **Groupfolders** in Files

## Troubleshooting

**"IntraVox groupfolder not found" error:**
- Make sure a groupfolder named **IntraVox** exists (exact name!)
- Run `sudo -u www-data php occ groupfolders:list` to see all groupfolders
- Create it manually if needed via web UI or OCC command
- Check logs: `sudo tail -f /var/www/nextcloud/data/nextcloud.log`

**App shows empty page:**
- Ensure the **IntraVox** groupfolder exists
- Check if admin group has access to the folder
- Run setup: `sudo -u www-data php occ intravox:setup`
- Verify home.json exists in the groupfolder

## Adding Users/Groups

To give other users access:

1. Go to **Settings** → **Groupfolders**
2. Click on **IntraVox** folder
3. Under **Groups**, click **+ Add group**
4. Select the group and set permissions
5. Users in that group can now access IntraVox

## Folder Structure

The groupfolder uses a language-based hierarchy for multi-language support:

```
IntraVox/
├── nl/                          # Dutch language folder
│   ├── home.json                # Homepage for Dutch
│   ├── 📷 images/               # Root-level images for homepage
│   ├── navigation.json          # Navigation structure for Dutch
│   ├── page-slug/               # Example page folder
│   │   ├── page-slug.json       # Page content and layout
│   │   └── 📷 images/           # Images specific to this page
│   └── parent-page/             # Nested page example
│       ├── parent-page.json
│       ├── 📷 images/
│       └── child-page/          # Nested child page
│           ├── child-page.json
│           └── 📷 images/
├── en/                          # English language folder
│   ├── home.json
│   ├── 📷 images/
│   ├── navigation.json
│   └── ... (same structure as nl/)
├── de/                          # German language folder
└── fr/                          # French language folder
```

### Key Structural Elements

- **Language Folders**: Each language (nl, en, de, fr) has its own root folder
- **home.json**: Special file for the homepage, exists directly in language folder
- **navigation.json**: Stores the navigation menu structure for each language
- **📷 images Folders**: Visually distinct folders (with camera emoji) for storing images
  - Root-level: For homepage images (`/nl/📷 images/`)
  - Page-level: For page-specific images (`/nl/page-slug/📷 images/`)
- **Page Folders**: Named after the page slug (URL-friendly version of title)
- **Nested Pages**: Pages can be nested in parent folders for hierarchical structure

## Permissions and Access Control

### Groupfolder Permissions

IntraVox leverages Nextcloud's groupfolder permissions system:

**Permission Levels**:
- **Read** (1): View pages and content
- **Update** (2): Edit existing pages
- **Create** (4): Create new pages
- **Delete** (8): Delete pages
- **Share** (16): Share pages with others
- **All** (31): Full access (sum of all permissions)

### Setting Permissions via OCC

```bash
# Give a group full access (31 = all permissions)
sudo -u www-data php occ groupfolders:permissions <folder-id> <group-name> 31

# Give read-only access (1 = read only)
sudo -u www-data php occ groupfolders:permissions <folder-id> <group-name> 1

# Give read and update access (3 = read + update)
sudo -u www-data php occ groupfolders:permissions <folder-id> <group-name> 3
```

### Advanced Control with ACLs

For fine-grained control over specific folders (e.g., restricting certain language folders):

1. Enable **Advanced Permissions** on the groupfolder in Nextcloud Settings
2. Navigate to the folder in Files app
3. Right-click and select **Advanced permissions**
4. Set user/group-specific permissions for subfolders

### Common Permission Scenarios

**Scenario 1: Content Editors**
- Group: `content-editors`
- Permissions: 31 (full access)
- Use case: Team members who create and manage all content

**Scenario 2: Viewers Only**
- Group: `employees`
- Permissions: 1 (read only)
- Use case: All staff can view the intranet but not edit

**Scenario 3: Language-Specific Editors**
- Enable Advanced Permissions on groupfolder
- Give Dutch team access only to `/nl/` folder
- Give English team access only to `/en/` folder
- Use case: Separate teams managing different language versions

## Security & Portability

- Folder is NOT tied to any user account - it's a system-level groupfolder
- Only groups you explicitly add have access
- Admin group has full access by default
- Perfect for company intranets and wikis
- **Portable**: Works across servers because it finds the folder by name, not ID
- No configuration needed when moving between test/production servers
- All content is validated and sanitized for security
- Leverages Nextcloud's built-in authentication and authorization
