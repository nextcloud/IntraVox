# IntraVox Nested Pages & Permissions Proposal

> **Status:** ARCHIVED - See [NESTED_PAGES.md](../../NESTED_PAGES.md) for consolidated specification

## Executive Summary

This proposal outlines a new folder structure for IntraVox that supports:
1. **Nested pages** - hierarchical page organization
2. **Department-based folders** - isolated spaces per department
3. **Granular permissions** - using Nextcloud's ACL system for subfolder-level access control

---

## Current Situation

### Current Folder Structure
```
IntraVox/                    (groupfolder root)
├── nl/                      (language folders)
│   ├── home/               (page folders)
│   │   └── page.json
│   ├── about/
│   │   └── page.json
│   └── images/
├── en/
│   ├── home/
│   └── about/
└── l10n/
```

### Current Limitations
- All pages at same level (no hierarchy)
- All pages inherit same permissions from groupfolder root
- No department isolation
- Cannot restrict access to specific pages/sections

---

## Nextcloud ACL Capabilities

### What Nextcloud Groupfolders ACL Can Do

✅ **Subfolder Permission Override**
- Set different permissions on subfolders within a groupfolder
- Override parent folder permissions at deeper levels
- Permissions: Read, Write, Create, Delete, Share
- Can set to: Inherit, Allow, or Deny
- Works per user or per group

✅ **Permission Inheritance**
- Child folders inherit parent permissions by default
- Can be explicitly overridden at any level
- New files/folders inherit from parent

✅ **Group-Based Access**
- Assign different groups different permissions on same folder
- Example: "Marketing Admins" can edit, "All Users" can read

### How to Configure ACL

Via command line (OCC):
```bash
# Enable ACL on groupfolder
occ groupfolders:permissions <folder_id> --enable

# Set permissions on specific path
occ groupfolders:permissions <folder_id> --group "Marketing Team" \
    --path "/en/departments/marketing" \
    +read +write +create +delete

# Deny write access for regular users
occ groupfolders:permissions <folder_id> --group "IntraVox Users" \
    --path "/en/departments/marketing" \
    +read -write -create -delete
```

### Known Limitations

⚠️ **UI Limitations**
- Groupfolder UI only shows files, not advanced ACL settings
- ACL must be configured via command line (occ)
- No visual indication in Files app of ACL restrictions

⚠️ **Potential Issues**
- Some GitHub issues mention ACL not always taking precedence
- Inheritance can be complex with many nested levels
- Performance may degrade with many ACL rules

---

## Proposed New Structure

### Option A: Department-First Structure (NOT Recommended)

```
IntraVox/                           # Groupfolder root
├── public/                         # Public pages (everyone can read)
│   ├── nl/
│   │   ├── home/
│   │   │   └── page.json
│   │   └── contact/
│   │       └── page.json
│   ├── en/
│   │   ├── home/
│   │   │   └── page.json
│   │   └── contact/
│   │       └── page.json
│   └── images/                     # Shared public images
│
├── departments/                    # Department-specific content
│   ├── marketing/
│   │   ├── nl/
│   │   │   ├── campaigns/          # Nested page structure
│   │   │   │   ├── page.json
│   │   │   │   ├── 2024/           # Further nesting
│   │   │   │   │   ├── page.json
│   │   │   │   │   ├── campaign-a/
│   │   │   │   │   │   └── page.json
│   │   │   │   │   └── campaign-b/
│   │   │   │   │       └── page.json
│   │   │   │   └── 2025/
│   │   │   │       └── page.json
│   │   │   ├── team/
│   │   │   │   └── page.json
│   │   │   └── guidelines/
│   │   │       └── page.json
│   │   ├── en/
│   │   │   ├── campaigns/
│   │   │   ├── team/
│   │   │   └── guidelines/
│   │   └── images/                 # Department-specific images
...
```

### Permissions Matrix

| Path | IntraVox Admins | IntraVox Users | Marketing Team | HR Team | IT Team | Finance Team |
|------|----------------|----------------|----------------|---------|---------|--------------|
| `/public/*` | Read/Write | Read | Read | Read | Read | Read |
| `/departments/marketing/*` | Read/Write | - | Read/Write | - | - | - |
| `/departments/hr/*` | Read/Write | - | - | Read/Write | - | - |
| `/departments/it/*` | Read/Write | - | - | - | Read/Write | - |
| `/departments/finance/*` | Read/Write | - | - | - | - | Read/Write |

**Legend:**
- `-` = No access (Deny all)
- `Read` = Can view pages (+read, -write, -create, -delete)
- `Read/Write` = Full access (+read, +write, +create, +delete)

---

## Implementation Plan

### Phase 1: Backend Changes

#### 1.1 Update PageService to Support Nested Pages

**Current:**
- Pages stored at: `IntraVox/{lang}/{pageId}/page.json`
- Flat structure only

**New:**
- Support paths like: `IntraVox/departments/marketing/nl/campaigns/2024/campaign-a/page.json`
- Implement recursive page scanning
- Store full path in page metadata

**Changes needed:**
```php
// PageService.php changes
- Add: getPagePath($pageId): string  // Returns full path
- Update: listPages() to support recursive scanning with depth
- Update: createPage() to support parent page parameter
- Add: movePage($pageId, $newParent) for reorganization
- Update: deletePage() to handle nested children
```

#### 1.2 Add Department Management

**New service:** `DepartmentService.php`
```php
- createDepartment($name, $groups): void
- getDepartments(): array
- configureDepartmentACL($deptName, $permissions): void
- listDepartmentPages($deptName): array
```

#### 1.3 Update Setup Command

**SetupService.php changes:**
- Create `/public` folder structure
- Create `/departments` folder structure
- Configure default ACL rules
- Migrate existing pages to `/public` (migration mode)

### Phase 2: Frontend Changes

#### 2.1 Nested Page Navigation

**Components to update:**
- `PageList.vue` - Show tree structure instead of flat list
- `PageEditor.vue` - Add parent page selector
- `App.vue` - Update routing to support nested paths

**New components:**
- `PageTree.vue` - Hierarchical page browser with expand/collapse
- `DepartmentSelector.vue` - Department picker for new pages
- `BreadcrumbNav.vue` - Show current page path

#### 2.2 Page Creation Flow

**Updated flow:**
1. Click "New Page"
2. Select department (or "Public")
3. Select parent page (optional)
4. Enter page title
5. Page created at correct location in hierarchy

#### 2.3 URL Structure

**Current:**
```
/apps/intravox?page=about#about
```

**New:**
```
/apps/intravox?page=departments/marketing/nl/campaigns/2024/campaign-a#campaign-a
/apps/intravox?page=public/nl/home#home
```

### Phase 3: Permission Integration

#### 3.1 OCC Commands for ACL

**New commands:**
```bash
# Enable ACL for IntraVox
occ intravox:acl:enable

# Set department permissions
occ intravox:acl:department marketing --group "Marketing Team" --permission readwrite
occ intravox:acl:department hr --group "HR Team" --permission readwrite

# Set public folder permissions
occ intravox:acl:public --group "IntraVox Users" --permission read
```

#### 3.2 Permission Checking

**Add to PageService:**
```php
- canReadPage($pageId, $userId): bool
- canEditPage($pageId, $userId): bool
- canDeletePage($pageId, $userId): bool
- getPagePermissions($pageId, $userId): array
```

#### 3.3 Frontend Permission Handling

**Changes:**
- Hide edit button if user lacks write permission
- Show read-only message on protected pages
- Filter page list based on user permissions

### Phase 4: Migration

#### 4.1 Migration Command

**New command:** `MigrateToNestedStructureCommand`
```bash
occ intravox:migrate:nested
```

**What it does:**
1. Create new `/public` and `/departments` structure
2. Move existing pages to `/public/{lang}/`
3. Update page metadata with new paths
4. Preserve all page content and navigation
5. Create backup of old structure

#### 4.2 Backward Compatibility

**During transition:**
- Support both old and new URL formats
- Redirect old URLs to new structure
- Maintain existing navigation during migration

---

## Alternative: Option B - Language-First Structure (RECOMMENDED)

```
IntraVox/
├── nl/
│   ├── public/
│   │   ├── home/
│   │   └── contact/
│   └── departments/
│       ├── marketing/
│       │   ├── campaigns/
│       │   └── team/
│       └── hr/
│           └── policies/
└── en/
    ├── public/
    └── departments/
```

**Pros:**
- Language remains top-level (current pattern)
- Slightly simpler ACL (no language duplication)

**Cons:**
- ACL must be set per language folder
- Harder to manage cross-language department permissions
- More complex department isolation

**Verdict:** **Recommended.** See [STRUCTURE_COMPARISON.md](STRUCTURE_COMPARISON.md) for detailed analysis.

---

## Security Considerations

### 1. Default-Deny Approach
- New departments start with no public access
- Must explicitly grant permissions
- Prevents accidental data exposure

### 2. Admin Override
- IntraVox Admins always have full access
- Can manage all departments and pages
- Can configure ACL rules

### 3. Audit Trail
- Log all permission changes
- Track who created/modified pages in restricted areas
- Use Nextcloud's built-in activity stream

### 4. Testing ACL
- Create test departments
- Verify permissions work as expected
- Test edge cases (deeply nested folders)

---

## User Experience Impact

### Positive Changes
✅ Clear department organization
✅ Nested pages for better content hierarchy
✅ Secure department-specific content
✅ Familiar tree navigation pattern
✅ Breadcrumb navigation for orientation

### Challenges
⚠️ More complex page creation flow
⚠️ Users need to understand departments
⚠️ ACL configuration requires admin knowledge
⚠️ Migration needed for existing installations

### Mitigation Strategies
- Provide clear department selection UI
- Show helpful tooltips and guidance
- Auto-suggest parent pages based on context
- Comprehensive admin documentation

---

## Open Questions

1. **Cross-department sharing**: Should pages be shareable across departments?
   - **Suggestion**: Add explicit cross-department sharing feature in v2.0

2. **Department creation**: Who can create new departments?
   - **Suggestion**: Only IntraVox Admins via OCC command initially, UI in v2.0

3. **Page moving**: Should users be able to move pages between departments?
   - **Suggestion**: Admins only, with permission verification

4. **Search scope**: Should search respect department boundaries?
   - **Suggestion**: Yes, only show results user has access to

5. **Navigation editor**: Should navigation span departments or be per-department?
   - **Suggestion**: Global navigation for public, separate per department

---

## Recommendation

**Proceed with Option B (Language-First Structure)** because:

1. ✅ Clean permission model using Nextcloud ACL
2. ✅ Natural department isolation
3. ✅ Supports nested pages within departments
4. ✅ Scalable to many departments
5. ✅ Aligns with typical organizational structure
6. ✅ Leverages existing Nextcloud groupfolder features

**Next steps:**
1. Get approval on structure and approach
2. Create detailed technical specifications
3. Implement Phase 1 (backend) first
4. Test ACL behavior thoroughly on dev server
5. Iterate based on findings

---

## References

- [Nextcloud ACL Documentation](https://nextcloud.com/blog/access-control-lists/)
- [Groupfolders GitHub](https://github.com/nextcloud/groupfolders)
- [Nextcloud 16 ACL Announcement](https://nextcloud.com/blog/nextcloud-16-implements-access-control-lists-to-replace-classic-file-servers/)

---

**Document Version:** 1.1
**Last Updated:** 2025-11-30
**Status:** Archived - Superseded by NESTED_PAGES.md
