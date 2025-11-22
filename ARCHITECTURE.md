# IntraVox Architecture Decisions

This document records critical architectural decisions that must be followed throughout the IntraVox application and all related operations.

## Table of Contents

1. [Language Folder Separation](#language-folder-separation)
2. [Page Storage Structure](#page-storage-structure)
3. [Import and Deployment Requirements](#import-and-deployment-requirements)

## Language Folder Separation

**Decision Date**: 2025-01-22
**Status**: CRITICAL - MUST BE ENFORCED

### The Rule

**Each language MUST have its own top-level folder in the IntraVox groupfolder.**

```
/var/www/nextcloud/data/__groupfolders/1/files/
├── de/     # German language content
├── en/     # English language content
├── fr/     # French language content
└── nl/     # Dutch language content
```

### Why This Matters

1. **Namespace Isolation**: Each language operates in its own isolated namespace
2. **Content Integrity**: Prevents mixing content from different languages
3. **Navigation Independence**: Each language has its own navigation.json and footer.json
4. **Clean URLs**: Language detection relies on folder structure
5. **Future-Proof**: Enables language-specific features and customization

### Where This Must Be Enforced

#### 1. Import Commands

❌ **WRONG** - Importing to wrong language folder:
```bash
# This imports Dutch content to English folder - NEVER DO THIS
occ intravox:import /tmp/demo-data/nl/
# Result: Content ends up in /files/en/ instead of /files/nl/
```

✅ **CORRECT** - Always verify target language:
```bash
# The import command MUST detect language from folder structure
# Or explicitly specify target language
occ intravox:import /tmp/demo-data/nl/ --language nl
```

#### 2. Deployment Scripts

All deployment scripts must:
1. Clearly specify which language they are deploying
2. Verify the target language folder before running import
3. Never assume the default language is correct

Example: [deploy-nl-demo.sh](file:///Users/rikdekker/Documents/Development/IntraVox/deploy-nl-demo.sh)

```bash
#!/bin/bash
# ❌ BAD: Generic import without language check
occ intravox:import /tmp/demo-data/nl/

# ✅ GOOD: Verify language folder exists first
if [ ! -d "${GROUPFOLDER_PATH}/nl" ]; then
    echo "ERROR: nl language folder does not exist!"
    exit 1
fi
occ intravox:import /tmp/demo-data/nl/ --target nl
```

#### 3. Frontend Language Detection

The frontend must:
1. Detect user's language preference
2. Load content from correct language folder
3. Never fall back to wrong language folder
4. Show clear error if language folder is missing

#### 4. API Endpoints

All API endpoints that serve pages or images must:
1. Determine language from user settings or URL parameter
2. Access only the appropriate language folder
3. Return 404 if content doesn't exist in requested language
4. Never serve content from a different language folder

### Common Mistakes to Avoid

#### Mistake 1: Assuming Default Language

❌ **WRONG**:
```php
// Assumes English is always present
$folder = $groupFolder->get('en');
```

✅ **CORRECT**:
```php
// Gets user's actual language preference
$language = $this->getUserLanguage();
$folder = $groupFolder->get($language);
```

#### Mistake 2: Mixing Languages During Import

❌ **WRONG**:
```bash
# Imports Dutch content but language detection fails
rsync demo-data/nl/ /tmp/deploy/
occ intravox:import /tmp/deploy/
# Result: Content imported to default 'en' folder
```

✅ **CORRECT**:
```bash
# Preserve language folder structure in temp location
rsync demo-data/nl/ /tmp/deploy/nl/
occ intravox:import /tmp/deploy/nl/
# Result: Content imported to correct 'nl' folder
```

#### Mistake 3: Not Validating After Deployment

❌ **WRONG**:
```bash
# Deploy and hope it worked
./deploy-nl-demo.sh
echo "Done!"
```

✅ **CORRECT**:
```bash
# Deploy and verify
./deploy-nl-demo.sh

# Verify content in correct language folder
if [ -f "/var/www/nextcloud/data/__groupfolders/1/files/nl/home.json" ]; then
    echo "✅ Dutch content deployed successfully"
else
    echo "❌ ERROR: Dutch content not found in nl/ folder"
    exit 1
fi
```

### Testing Checklist

Before deploying content, verify:

- [ ] Source content is in correct language folder (`demo-data/nl/`, `demo-data/en/`, etc.)
- [ ] Deployment script targets correct language folder
- [ ] Import command will detect or be given correct language
- [ ] After deployment, content exists in correct language folder on server
- [ ] Navigation and footer files are in correct language folder
- [ ] Images are in language-specific folder structure

### Monitoring and Validation

After any deployment:

```bash
# Check what was actually deployed
ssh user@server 'sudo ls -la /var/www/nextcloud/data/__groupfolders/1/files/nl/'

# Verify navigation.json is in correct language
ssh user@server 'sudo cat /var/www/nextcloud/data/__groupfolders/1/files/nl/navigation.json'

# Check recent file modifications to confirm target
ssh user@server 'sudo find /var/www/nextcloud/data/__groupfolders/1/files/ -type f -mmin -5 -ls'
```

## Page Storage Structure

Each page must follow this folder structure:

```
/files/{language}/{page-slug}/
├── {page-slug}.json     # Page content and metadata
└── images/              # Page-specific images
    ├── image1.jpg
    └── image2.png
```

**Exception**: Home page is stored at root level:
```
/files/{language}/
├── home.json
└── images/
```

## Import and Deployment Requirements

### Import Command Behavior

The `occ intravox:import` command must:
1. Detect language from folder structure OR accept explicit `--language` parameter
2. Create/update pages only in the specified language folder
3. Never cross language boundaries
4. Log which language folder is being used
5. Return error if language folder doesn't exist

### Demo Data Structure

Demo data must maintain language folder structure:

```
demo-data/
├── nl/
│   ├── home.json
│   ├── navigation.json
│   ├── footer.json
│   └── images/
├── en/
│   ├── home.json
│   ├── navigation.json
│   ├── footer.json
│   └── images/
└── de/
    ├── home.json
    ├── navigation.json
    ├── footer.json
    └── images/
```

### Deployment Verification

Every deployment must include these steps:

1. **Pre-deployment check**: Verify source content language
2. **Deploy**: Copy to temp location preserving folder structure
3. **Import**: Run import with explicit or detected language
4. **Post-deployment verification**: Confirm content in correct language folder
5. **Scan**: Run groupfolder scan to register files
6. **Functional test**: Load page in correct language to verify

## References

- [PAGES.md](file:///Users/rikdekker/Documents/Development/IntraVox/PAGES.md) - Complete page system documentation
- [TECHNICAL.md](file:///Users/rikdekker/Documents/Development/IntraVox/TECHNICAL.md) - Technical implementation details
- [deploy-nl-demo.sh](file:///Users/rikdekker/Documents/Development/IntraVox/deploy-nl-demo.sh) - Example deployment script

## Version History

- **2025-01-22**: Initial documentation of language folder architecture requirement
  - Documented critical language separation requirement
  - Added common mistakes and solutions
  - Created testing and validation checklists
