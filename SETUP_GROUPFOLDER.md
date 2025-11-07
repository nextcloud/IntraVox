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

The groupfolder will contain:
```
IntraVox/
├── home.json (default homepage)
├── images/ (uploaded images)
├── documents/ (uploaded files)
└── *.json (page files)
```

## Security & Portability

- Folder is NOT tied to any user account - it's a system-level groupfolder
- Only groups you explicitly add have access
- Admin group has full access by default
- Perfect for company intranets and wikis
- **Portable**: Works across servers because it finds the folder by name, not ID
- No configuration needed when moving between test/production servers
