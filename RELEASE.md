# IntraVox Release Management

This document describes how to create and manage releases for IntraVox.

## Prerequisites

1. **Gitea Access Token**: You need a personal access token from Gitea with `write:repository` permissions
   - Go to: https://gitea.rikdekker.nl/user/settings/applications
   - Create a new token with repository write access
   - Export it: `export GITEA_TOKEN='your-token-here'`

2. **Clean working directory**: Commit all changes before creating a release

3. **Up-to-date CHANGELOG.md**: Update the changelog with your changes

## Creating a Release

Use the `create-release.sh` script to create a new release:

```bash
./create-release.sh <version> <label> <description>
```

### Example

```bash
export GITEA_TOKEN='your-token-here'
./create-release.sh 0.2.3 "Navigation" "Improved navigation with dropdown menus and better mobile support"
```

### What the script does

1. ✅ Checks you're on the main branch
2. ✅ Verifies no uncommitted changes
3. ✅ Updates version numbers in `package.json` and `appinfo/info.xml`
4. ✅ Builds the frontend (`npm run build`)
5. ✅ Creates a deployment tarball with all necessary files
6. ✅ Commits the version changes
7. ✅ Creates a git tag `v{version}-{label}`
8. ✅ Pushes to Gitea
9. ✅ Creates a Gitea release via API
10. ✅ Uploads the tarball as a release asset

### Release Artifacts

Each release includes:
- **Source code snapshot** (automatic via git tag)
- **Deployment tarball** (`intravox-{version}.tar.gz`)
  - Contains: `appinfo/`, `lib/`, `l10n/`, `templates/`, `css/`, `img/`, `js/`, `README.md`, `CHANGELOG.md`
  - Pre-built JavaScript assets included
  - Ready to extract and deploy

## Rolling Back

Use the `rollback.sh` script to rollback to a previous release:

```bash
./rollback.sh <tag-name>
```

### Example

```bash
./rollback.sh v0.2.2-Footer
```

### What the rollback script does

1. ✅ Checks if the tag exists
2. ✅ Stashes uncommitted changes (with confirmation)
3. ✅ Checks out the specified tag
4. ✅ Shows tag information
5. ✅ Optionally rebuilds and deploys to server

### Important Notes

- **Local repository**: Will be in "detached HEAD" state after rollback
  - Create a new branch: `git checkout -b rollback-branch`
  - Or return to main: `git checkout main`
- **Data files**: JSON data files are NOT rolled back, only the application code
- **Server deployment**: You can choose whether to deploy to server during rollback

## Versioning Scheme

IntraVox follows Semantic Versioning with labels:

```
v<MAJOR>.<MINOR>.<PATCH>-<LABEL>
```

- **MAJOR**: Breaking changes
- **MINOR**: New features (backward compatible)
- **PATCH**: Bug fixes
- **LABEL**: Human-readable feature name

### Examples

- `v0.2.2-Footer` - Footer feature in version 0.2.2
- `v0.3.0-Navigation` - Navigation feature in version 0.3.0
- `v1.0.0-Release` - First stable release

## Deployment Workflow

### Development Server

1. Make changes and test locally
2. Create a release with `create-release.sh`
3. Deploy with `./deploy-dev.sh`

### Production Server

1. Download the tarball from Gitea release page
2. Extract to Nextcloud apps directory
3. Enable: `occ app:enable intravox`
4. Run setup if needed: `occ intravox:setup`

## Rollback Workflow

### Emergency Rollback

If a release breaks production:

```bash
# On your local machine
./rollback.sh v0.2.2-Footer

# Answer 'y' to deploy to server
```

### Manual Server Rollback

```bash
# On the server
cd /var/www/nextcloud/apps
sudo -u www-data mv intravox intravox.broken
sudo -u www-data wget https://gitea.rikdekker.nl/rik/IntraVox/releases/download/v0.2.2-Footer/intravox-0.2.2.tar.gz
sudo -u www-data tar -xzf intravox-0.2.2.tar.gz
sudo -u www-data mv intravox-0.2.2 intravox
cd /var/www/nextcloud
sudo -u www-data php occ app:enable intravox
```

## Release Checklist

Before creating a release:

- [ ] All features tested and working
- [ ] CHANGELOG.md updated with changes
- [ ] No uncommitted changes
- [ ] On main branch
- [ ] Version number decided
- [ ] Label chosen
- [ ] GITEA_TOKEN exported

After creating a release:

- [ ] Verify release appears on Gitea
- [ ] Download tarball and verify contents
- [ ] Test deployment on dev server
- [ ] Update production if needed
- [ ] Announce release to team

## Troubleshooting

### "GITEA_TOKEN environment variable is not set"

```bash
export GITEA_TOKEN='your-token-here'
```

### "Failed to create Gitea release"

- Check your token has correct permissions
- Verify Gitea is accessible
- Check if tag already exists

### "Build failed"

```bash
npm install  # Reinstall dependencies
npm run build  # Try manual build
```

### Rollback doesn't work

```bash
git fetch --tags  # Fetch tags from remote
git tag -l  # List available tags
```

## Advanced: Manual Release Process

If the script fails, you can create a release manually:

```bash
# 1. Update version numbers
# Edit package.json and appinfo/info.xml manually

# 2. Build
npm run build

# 3. Create tarball
tar -czf intravox-0.2.3.tar.gz appinfo lib l10n templates css img js README.md CHANGELOG.md

# 4. Commit and tag
git add -A
git commit -m "Release v0.2.3"
git tag -a v0.2.3-Label -m "Release description"
git push origin main --tags

# 5. Upload to Gitea manually via web interface
# Go to: https://gitea.rikdekker.nl/rik/IntraVox/releases
```

## Questions?

Contact: rik@shalution.nl
