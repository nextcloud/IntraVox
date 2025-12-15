# Confluence Import Guide

This guide explains how to import content from Confluence (Cloud, Server, or Data Center) into IntraVox.

## Table of Contents

1. [Prerequisites](#prerequisites)
2. [Supported Confluence Versions](#supported-confluence-versions)
3. [Authentication Setup](#authentication-setup)
4. [Import Process](#import-process)
5. [Supported Macros](#supported-macros)
6. [Troubleshooting](#troubleshooting)

## Prerequisites

Before importing from Confluence, ensure you have:

- **Admin access** to your IntraVox installation
- **Confluence credentials** with read access to the space you want to import
- A stable internet connection for downloading pages and attachments

## Supported Confluence Versions

IntraVox supports importing from:

- ✅ **Confluence Cloud** (Atlassian hosted)
- ✅ **Confluence Server** (v7.x - 8.x)
- ✅ **Confluence Data Center** (Enterprise on-premise)

The importer automatically detects your Confluence version based on the URL.

## Authentication Setup

### For Confluence Cloud

1. **Create an API Token**:
   - Go to [Atlassian Account Security](https://id.atlassian.com/manage-profile/security/api-tokens)
   - Click "Create API token"
   - Give it a label (e.g., "IntraVox Import")
   - Copy the token (you won't be able to see it again!)

2. **Required Information**:
   - **Confluence URL**: `https://yoursite.atlassian.net/wiki`
   - **Email**: Your Atlassian account email
   - **API Token**: The token you just created

### For Confluence Server (v7.9+)

1. **Create a Personal Access Token**:
   - Go to your Confluence user profile
   - Navigate to "Personal Access Tokens"
   - Click "Create token"
   - Give it a name and set permissions (read access needed)
   - Copy the token

2. **Required Information**:
   - **Confluence URL**: `https://confluence.yourcompany.com`
   - **Personal Access Token**: The token you created

### For Confluence Server (Legacy)

1. **Use Basic Authentication**:
   - **Confluence URL**: `https://confluence.yourcompany.com`
   - **Username**: Your Confluence username
   - **Password**: Your Confluence password

⚠️ **Security Note**: Basic authentication is less secure. Use Personal Access Tokens if available (v7.9+).

## Import Process

### Step 1: Navigate to Import Settings

1. Open IntraVox admin panel
2. Go to **Settings** → **Export/Import** tab
3. Find the **Import from Confluence** section

### Step 2: Configure Connection

1. **Enter Confluence URL**:
   - Cloud: `https://yoursite.atlassian.net/wiki`
   - Server: `https://confluence.yourcompany.com`

2. **Select Authentication Method**:
   - The system automatically detects Cloud vs Server
   - Choose the appropriate method for your version

3. **Enter Credentials**:
   - For Cloud: Email + API Token
   - For Server (v7.9+): Personal Access Token
   - For Server (Legacy): Username + Password

4. **Test Connection**:
   - Click "Test Connection" button
   - Verify the green success message appears
   - Check that the correct version is detected

### Step 3: Select Space

1. **Load Spaces**:
   - Click "Load Spaces" button
   - Wait for the list of available spaces to appear

2. **Choose Space**:
   - Select the space you want to import from the dropdown
   - Review the space description

3. **Select Target Language**:
   - Choose which IntraVox language folder to import into
   - Default: `nl` (Nederlands)
   - Options: `nl`, `en`, `de`, `fr`

### Step 4: Start Import

1. **Click "Import Space"**:
   - The import process begins
   - Progress is shown in real-time

2. **Monitor Progress**:
   - Pages imported count
   - Media files downloaded count

3. **Wait for Completion**:
   - Depending on space size, this may take a few minutes
   - Don't close the browser window during import

### Step 5: Verify Import

1. **Check Success Message**:
   - Green success banner appears when done
   - Review statistics (pages, media files)

2. **View Imported Pages**:
   - Navigate to IntraVox main view
   - Switch to the target language
   - Browse imported pages in the sidebar

## Supported Macros

The following Confluence macros are automatically converted:

### ✅ Fully Supported

| Macro | Converts To | Notes |
|-------|-------------|-------|
| **Info Panel** | Text widget with blue styling | Preserves title and content |
| **Note Panel** | Text widget with gray styling | Preserves title and content |
| **Warning Panel** | Text widget with orange styling | Preserves title and content |
| **Tip Panel** | Text widget with green styling | Preserves title and content |
| **Error Panel** | Text widget with red styling | Preserves title and content |
| **Code Block** | Text widget with syntax highlighting | 30+ languages supported |
| **Images** | Image widget | Downloaded and embedded |
| **Attachments** | File widget | Downloaded and linked |
| **Expand/Collapse** | HTML5 `<details>` element | Native browser support |

### ⚠️ Partially Supported

| Macro | Converts To | Limitations |
|-------|-------------|-------------|
| **Table of Contents** | Placeholder text | Can be recreated manually |
| **Attachments List** | Placeholder text | Individual files are imported |

### ❌ Not Supported (Converted to Placeholder)

- Jira Issue macro
- Include Page macro
- Roadmap macro
- Custom/third-party macros

Unsupported macros are converted to a text block with a warning message indicating the macro name.

## Supported Languages (Code Blocks)

The code macro supports syntax highlighting for:

- JavaScript, TypeScript, Node.js
- PHP, Python, Ruby, Perl
- Java, C, C++, C#, Go, Rust, Scala
- HTML, CSS, SCSS, SASS
- SQL, JSON, YAML, XML
- Bash, PowerShell
- And 15+ more languages

## Import Performance

Expected import times (approximate):

- **10 pages**: ~30 seconds
- **50 pages**: ~2-3 minutes
- **100 pages**: ~5-7 minutes
- **500+ pages**: 20+ minutes

Performance depends on:
- Number of pages
- Number of attachments
- Confluence server response time
- Network speed

## Troubleshooting

### Connection Failed

**Problem**: "Connection failed" error when testing connection.

**Solutions**:
1. Verify Confluence URL is correct
2. Check credentials are valid
3. For Cloud: Ensure API token hasn't expired
4. For Server: Check firewall/network access
5. Test URL in browser first

### No Spaces Listed

**Problem**: "Load Spaces" returns empty list.

**Solutions**:
1. Verify you have read access to at least one space
2. Check Confluence permissions
3. Try with different credentials
4. Check Confluence admin restrictions

### Import Fails Partway Through

**Problem**: Import starts but fails before completing.

**Solutions**:
1. Check Confluence rate limits (Cloud: 100 requests/min)
2. Verify stable internet connection
3. Try importing smaller space first
4. Check IntraVox server logs for errors

### Missing Images/Attachments

**Problem**: Pages imported but images are missing.

**Causes**:
1. Attachment download URLs not accessible
2. Network timeout during download
3. Insufficient storage space

**Solutions**:
1. Re-run import with same settings (will skip existing pages)
2. Manually upload missing attachments
3. Check IntraVox server logs for specific errors

### Macros Not Converting Properly

**Problem**: Confluence macros show as unsupported.

**Solutions**:
1. Check [Supported Macros](#supported-macros) list above
2. For unsupported macros, recreate content manually in IntraVox
3. Contact support for frequently-used macro types

### Rate Limiting (Confluence Cloud)

**Problem**: Import slows down or fails with rate limit errors.

**Explanation**: Confluence Cloud limits API requests to 100/minute.

**Solutions**:
1. Wait and retry - rate limits reset after 1 minute
2. Import smaller spaces
3. Contact Atlassian to increase rate limits (Enterprise plans)

## Best Practices

### Before Import

1. **Test with Small Space First**:
   - Try importing a test space with 5-10 pages
   - Verify conversion quality before importing large spaces

2. **Review Content**:
   - Clean up old/unused pages in Confluence first
   - Archive historical content you don't need

3. **Backup**:
   - Export IntraVox before importing (if you have existing content)
   - Keep Confluence space accessible during transition

### After Import

1. **Review Imported Pages**:
   - Check page hierarchy is correct
   - Verify images and attachments loaded
   - Test links between pages

2. **Update Formatting**:
   - Fix any unsupported macros
   - Adjust styling if needed
   - Update internal links

3. **Set Permissions**:
   - Configure IntraVox page permissions
   - Update navigation menu
   - Test user access

## Getting Help

If you encounter issues not covered in this guide:

1. **Check Server Logs**:
   - IntraVox backend logs: `nextcloud.log`
   - Look for "Confluence import" entries

2. **GitHub Issues**:
   - Report bugs: [IntraVox GitHub Issues](https://github.com/shalution/IntraVox/issues)
   - Include error messages and Confluence version

3. **Documentation**:
   - [IntraVox Documentation](https://github.com/shalution/IntraVox/wiki)
   - [Confluence API Documentation](https://developer.atlassian.com/cloud/confluence/rest/v1/)

## FAQ

**Q: Can I import multiple spaces?**
A: Yes, import each space separately. Choose different target languages or page prefixes to avoid conflicts.

**Q: Will import overwrite existing IntraVox pages?**
A: No, the default behavior is to skip existing pages. Use the "Overwrite" option in ZIP import if needed.

**Q: Are Confluence permissions imported?**
A: No, you need to configure IntraVox permissions separately after import.

**Q: Can I import from Confluence to multiple languages?**
A: Import to one language at a time. For multi-language, import the same space multiple times to different language folders.

**Q: What happens to Confluence comments?**
A: Comments are not imported. IntraVox has its own comment system.

**Q: Can I schedule automatic imports?**
A: Not currently. Import is a one-time manual process. For ongoing sync, contact support for custom solutions.

---

**Last Updated**: December 2025
**IntraVox Version**: 0.8.0+
**Author**: IntraVox Team
