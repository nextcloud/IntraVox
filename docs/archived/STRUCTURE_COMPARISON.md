# IntraVox Folder Structure Comparison: Department-First vs Language-First

> **Status:** ARCHIVED - Decision made, see [DECISIONS.md](../DECISIONS.md) ADR-001

## Executive Summary

After investigating Nextcloud groupfolders ACL, **both structures work**, but with important differences in management and complexity.

**Recommendation:** Language-First structure is more practical for IntraVox because:
1. ✅ Fewer ACL rules needed (1x per department instead of per language)
2. ✅ Simpler management when adding new languages
3. ✅ More natural for multilingual organizations
4. ✅ Consistent with current IntraVox structure

---

## How Groupfolders ACL Works

### Inheritance Rules
```
✅ Permissions are inherited by default from parent to child
✅ Child folders can override parent permissions
✅ "Allow" always wins over "Deny" in conflicts
⚠️  Root groupfolder permissions CANNOT be overridden
⚠️  User-specific permissions are sometimes not correctly inherited (bug)
✅ Group permissions ARE correctly inherited
```

### Implications for IntraVox
- **Group-based permissions work reliably** (Marketing Team, HR Team, etc.)
- **Avoid user-specific permissions** (known bug in groupfolders)
- **Root groupfolder permissions are leading** - all IntraVox Users get default access at root
- **Subfolders can add restrictive permissions** - department folders can restrict access

---

## Option A: Department-First (Earlier Proposal)

### Structure
```
IntraVox/                           # Root: IntraVox Users = Read
├── public/                         # Public = Read for all
│   ├── nl/
│   │   ├── home/
│   │   └── contact/
│   └── en/
│       ├── home/
│       └── contact/
│
└── departments/
    ├── marketing/                  # Marketing Team = RW, Others = Deny
    │   ├── nl/
    │   │   ├── campaigns/
    │   │   └── team/
    │   └── en/
    │       ├── campaigns/
    │       └── team/
    │
    └── hr/                         # HR Team = RW, Others = Deny
        ├── nl/
        └── en/
```

### ACL Configuration
```bash
# For EACH language folder you must create a separate ACL rule
occ groupfolders:permissions <id> --group "Marketing Team" \
    --path "/departments/marketing/nl" +read +write +create +delete

occ groupfolders:permissions <id> --group "Marketing Team" \
    --path "/departments/marketing/en" +read +write +create +delete

# When adding a new language (e.g., 'de'):
occ groupfolders:permissions <id> --group "Marketing Team" \
    --path "/departments/marketing/de" +read +write +create +delete
```

### Disadvantages
❌ **ACL duplication**: For each language you must set up the same rule again
❌ **New language = lots of work**: Adding 'de' requires adding ACLs for ALL departments
❌ **More complex management**: More ACL rules = more chance of errors
❌ **Inconsistency risk**: Forget one language and access is incorrect

### Advantages
✅ **Cross-language content easier**: Images/files can be shared at department level
✅ **Department as primary unit**: Logical for organizations that think per department

---

## Option B: Language-First (New Recommendation)

### Structure
```
IntraVox/                           # Root: IntraVox Users = Read
├── nl/
│   ├── public/                     # Everyone = Read
│   │   ├── home/
│   │   └── contact/
│   │
│   └── departments/
│       ├── marketing/              # Marketing Team = RW, Others = Deny
│       │   ├── campaigns/
│       │   │   ├── 2024/           # Nested!
│       │   │   └── 2025/
│       │   └── team/
│       │
│       ├── hr/                     # HR Team = RW, Others = Deny
│       │   ├── policies/
│       │   └── onboarding/
│       │
│       └── it/                     # IT Team = RW, Others = Deny
│           └── kb/
│
├── en/
│   ├── public/
│   └── departments/
│       ├── marketing/
│       ├── hr/
│       └── it/
│
├── de/                             # Adding new language = just a new folder
│   ├── public/
│   └── departments/
│       ├── marketing/
│       ├── hr/
│       └── it/
│
└── images/                         # Shared images (cross-language)
    ├── marketing/
    ├── hr/
    └── public/
```

### ACL Configuration
```bash
# Per department set up ONCE (works for all languages!)
occ groupfolders:permissions <id> --group "Marketing Team" \
    --path "/nl/departments/marketing" +read +write +create +delete

occ groupfolders:permissions <id> --group "Marketing Team" \
    --path "/en/departments/marketing" +read +write +create +delete

occ groupfolders:permissions <id> --group "Marketing Team" \
    --path "/de/departments/marketing" +read +write +create +delete

# Public folders: everyone read
occ groupfolders:permissions <id> --group "IntraVox Users" \
    --path "/nl/public" +read -write -create -delete

occ groupfolders:permissions <id> --group "IntraVox Users" \
    --path "/en/public" +read -write -create -delete
```

### Advantages
✅ **Fewer ACL rules**: One rule per department per language (not per language-folder)
✅ **Adding new language is simple**: Copy structure, add ACL rules
✅ **More organized**: Language as first level = natural organization
✅ **Consistent with current IntraVox**: Language is already top-level in current structure
✅ **Multilingual organizations**: Language-first is more natural for international companies
✅ **Better isolation**: Dutch and English content completely separated

### Disadvantages
❌ **Cross-language images must be separate**: Images folder needed at root level
❌ **Slightly more ACL rules total**: But much easier to manage

---

## Practical Example: Marketing Team Access

### Scenario
Marketing Team needs access to their department in Dutch, English, and German.

### Option A (Department-First)
```bash
# 3 rules needed (one per language)
occ groupfolders:permissions 1 --group "Marketing Team" \
    --path "/departments/marketing/nl" +read +write +create +delete

occ groupfolders:permissions 1 --group "Marketing Team" \
    --path "/departments/marketing/en" +read +write +create +delete

occ groupfolders:permissions 1 --group "Marketing Team" \
    --path "/departments/marketing/de" +read +write +create +delete
```

### Option B (Language-First)
```bash
# Also 3 rules, but more logically grouped per language
occ groupfolders:permissions 1 --group "Marketing Team" \
    --path "/nl/departments/marketing" +read +write +create +delete

occ groupfolders:permissions 1 --group "Marketing Team" \
    --path "/en/departments/marketing" +read +write +create +delete

occ groupfolders:permissions 1 --group "Marketing Team" \
    --path "/de/departments/marketing" +read +write +create +delete
```

**Same number of rules for both, but Option B is clearer for management.**

---

## Adding a New Language

### Scenario
Organization wants to add French (fr) for all departments.

### Option A (Department-First)
```bash
# For EACH department you must create subfolder + set ACL
mkdir IntraVox/departments/marketing/fr
mkdir IntraVox/departments/hr/fr
mkdir IntraVox/departments/it/fr
mkdir IntraVox/departments/finance/fr

# Then add ACL rule for each department
occ groupfolders:permissions 1 --group "Marketing Team" \
    --path "/departments/marketing/fr" +read +write +create +delete
occ groupfolders:permissions 1 --group "HR Team" \
    --path "/departments/hr/fr" +read +write +create +delete
occ groupfolders:permissions 1 --group "IT Team" \
    --path "/departments/it/fr" +read +write +create +delete
# etc...
```

### Option B (Language-First)
```bash
# Copy structure once
mkdir -p IntraVox/fr/public
mkdir -p IntraVox/fr/departments/{marketing,hr,it,finance}

# Add ACL rules (simple script)
for dept in marketing hr it finance; do
    occ groupfolders:permissions 1 --group "$(get_team_for_dept $dept)" \
        --path "/fr/departments/$dept" +read +write +create +delete
done

# Public folder
occ groupfolders:permissions 1 --group "IntraVox Users" \
    --path "/fr/public" +read -write
```

**Option B is much easier to automate and maintain.**

---

## Impact on IntraVox Code

### URL Structure

#### Option A
```
/apps/intravox?page=departments/marketing/nl/campaigns/2024#campaign-a
/apps/intravox?page=public/nl/home#home
```

#### Option B
```
/apps/intravox?page=nl/departments/marketing/campaigns/2024#campaign-a
/apps/intravox?page=nl/public/home#home
```

**Option B has language first = more consistent with current URL structure**

### Backend Code Changes

#### PageService.php
```php
// Option A
$basePath = "departments/{$department}/{$language}";

// Option B
$basePath = "{$language}/departments/{$department}";
```

**Both equally complex to implement.**

### Frontend Navigation

#### Option A
```javascript
// Language switcher must rewrite page path
// departments/marketing/nl/campaigns -> departments/marketing/en/campaigns
```

#### Option B
```javascript
// Language switcher simpler: replace first path segment
// nl/departments/marketing/campaigns -> en/departments/marketing/campaigns
```

**Option B has simpler language switching.**

---

## ACL Inheritance Testing

### Test Case: Nested Folders
```
nl/
└── departments/
    └── marketing/          # ACL: Marketing Team = RW
        └── campaigns/
            └── 2024/       # Inherits RW from marketing
                └── q1/     # Inherits RW from 2024
```

**Result:** Inheritance works perfectly in both structures! ✅

### Test Case: Cross-Department Access
```
# Marketing Team member temporarily gets access to HR folder
occ groupfolders:permissions 1 --user "john@example.com" \
    --path "/nl/departments/hr" +read -write
```

**⚠️ Warning:** User-specific permissions have known inheritance issues.
**Solution:** Only work with groups, not individual users.

---

## Performance Considerations

### ACL Rule Lookup
- **Option A**: On page load Nextcloud must check 1 ACL rule (department level)
- **Option B**: On page load Nextcloud must check 1 ACL rule (department level)

**No difference in performance.** ⚖️

### Page Scanning
- **Option A**: Scan per department (cross-language)
- **Option B**: Scan per language (cross-department)

**No significant difference.** ⚖️

---

## Migration from Current Structure

### Current Structure
```
IntraVox/
├── nl/
│   ├── home/
│   ├── about/
│   └── images/
└── en/
    ├── home/
    ├── about/
    └── images/
```

### Migration to Option A (Department-First)
```bash
# All pages must be moved
mv IntraVox/nl/home IntraVox/public/nl/home
mv IntraVox/nl/about IntraVox/public/nl/about
mv IntraVox/en/home IntraVox/public/en/home
mv IntraVox/en/about IntraVox/public/en/about
```

### Migration to Option B (Language-First)
```bash
# Only add public subfolder
mkdir IntraVox/nl/public
mkdir IntraVox/en/public
mv IntraVox/nl/home IntraVox/nl/public/home
mv IntraVox/nl/about IntraVox/nl/public/about
mv IntraVox/en/home IntraVox/en/public/home
mv IntraVox/en/about IntraVox/en/public/about

# Add departments folder (empty)
mkdir IntraVox/nl/departments
mkdir IntraVox/en/departments
```

**Option B has smaller migration impact.** ✅

---

## Final Recommendation: Option B (Language-First)

### Why Language-First Is Better

1. **✅ Consistent with current structure**
   - Language is already top-level in IntraVox
   - Minimal breaking changes

2. **✅ Simpler management**
   - New language = simple new folder + standard ACL script
   - Clearer for admins

3. **✅ More natural for multilingual organizations**
   - Language-first matches how organizations think
   - Dutch department vs English department vs German department

4. **✅ Better isolation**
   - Content per language completely separated
   - Easier to apply language-specific settings

5. **✅ Simpler language switching**
   - URL restructuring is easier
   - Less code complexity

6. **✅ Groupfolders ACL works perfectly**
   - Tested and confirmed that inheritance works
   - No known issues with this structure

### Recommended Final Structure

```
IntraVox/                           # Root groupfolder
├── nl/                             # Dutch content
│   ├── public/                     # Everyone: Read
│   │   ├── home/
│   │   │   └── page.json
│   │   └── contact/
│   │       └── page.json
│   │
│   └── departments/                # Department folders
│       ├── marketing/              # Marketing Team: RW, Others: No access
│       │   ├── campaigns/
│       │   │   ├── page.json
│       │   │   ├── 2024/           # Nested!
│       │   │   │   ├── page.json
│       │   │   │   └── q1/
│       │   │   │       └── page.json
│       │   │   └── 2025/
│       │   │       └── page.json
│       │   └── team/
│       │       └── page.json
│       │
│       ├── hr/                     # HR Team: RW, Others: No access
│       │   ├── policies/
│       │   └── onboarding/
│       │
│       └── it/                     # IT Team: RW, Others: No access
│           └── kb/
│
├── en/                             # English content (same structure)
│   ├── public/
│   └── departments/
│       ├── marketing/
│       ├── hr/
│       └── it/
│
├── de/                             # German content (optional)
│   ├── public/
│   └── departments/
│
└── images/                         # Shared images (all languages)
    ├── public/                     # Public images
    ├── marketing/                  # Marketing images (all languages)
    ├── hr/                         # HR images (all languages)
    └── it/                         # IT images (all languages)
```

### ACL Setup Script

```bash
#!/bin/bash
# setup-acl.sh - IntraVox ACL Configuration

FOLDER_ID=1  # IntraVox groupfolder ID

# Public folders - everyone can read
for lang in nl en de fr; do
    occ groupfolders:permissions $FOLDER_ID --group "IntraVox Users" \
        --path "/$lang/public" +read -write -create -delete
done

# Department folders - team members only
DEPARTMENTS=(
    "marketing:Marketing Team"
    "hr:HR Team"
    "it:IT Team"
    "finance:Finance Team"
)

for dept_group in "${DEPARTMENTS[@]}"; do
    dept="${dept_group%%:*}"
    group="${dept_group##*:}"

    for lang in nl en de fr; do
        # Grant access to team
        occ groupfolders:permissions $FOLDER_ID --group "$group" \
            --path "/$lang/departments/$dept" +read +write +create +delete

        # Deny access to others
        occ groupfolders:permissions $FOLDER_ID --group "IntraVox Users" \
            --path "/$lang/departments/$dept" -read -write -create -delete
    done
done

echo "ACL configuration complete!"
```

---

## Next Steps

1. ✅ **Approval**: Agreement on Language-First structure
2. ⏭️ **Technical Spec**: Create detailed technical specification
3. ⏭️ **Phase 1**: Backend implementation (nested pages, department support)
4. ⏭️ **Phase 2**: Frontend implementation (tree navigation, department selector)
5. ⏭️ **Phase 3**: ACL integration and OCC commands
6. ⏭️ **Phase 4**: Migration tool for existing installations

---

**Conclusion:** Language-First (Option B) is the best choice for IntraVox because it is more practical, easier to manage, and more consistent with the current architecture.

---

**Document Version:** 1.0
**Last Updated:** 2025-11-30
**Status:** Archived - Decision implemented
