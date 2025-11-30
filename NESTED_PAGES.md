# IntraVox Nested Pages Specification

> **Status:** PROPOSED - Not yet implemented
> **Version:** 1.0
> **Last Updated:** 2025-11-30

## Executive Summary

This document specifies the nested pages feature for IntraVox, enabling:
1. **Hierarchical page organization** - Pages can have child pages
2. **Department-based folders** - Isolated content spaces per department
3. **Granular permissions** - Using Nextcloud's ACL system for subfolder access control
4. **Breadcrumb navigation** - Visual path indicator for users

---

## Current State vs. Proposed

### Current Structure (v0.5.x)
```
IntraVox/
├── nl/
│   ├── home/page.json
│   ├── about/page.json
│   └── images/
└── en/
    ├── home/page.json
    └── about/page.json
```

### Proposed Structure (v2.x)
```
IntraVox/
├── nl/
│   ├── public/
│   │   ├── home/page.json
│   │   └── contact/page.json
│   └── departments/
│       ├── marketing/
│       │   ├── page.json
│       │   └── campaigns/
│       │       ├── page.json
│       │       └── 2024/page.json
│       └── hr/
│           ├── page.json
│           └── policies/page.json
└── en/
    └── [same structure]
```

---

## Technical Design

### 1. Parent Tracking

**Primary method:** Folder path (filesystem is source of truth)
**Secondary method:** Metadata in page.json (for performance/caching)

```json
{
  "id": "campaigns",
  "uniqueId": "abc-123-def",
  "title": "Marketing Campaigns",
  "path": "nl/departments/marketing/campaigns",
  "parentPath": "nl/departments/marketing",
  "parentId": "marketing",
  "depth": 1,
  "department": "marketing",
  "language": "nl"
}
```

### 2. Depth Limits

| Area | Maximum Depth | Example |
|------|--------------|---------|
| Public pages | 3 levels | `/nl/public/contact/form/details` |
| Department pages | 2 levels | `/nl/departments/hr/policies/leave` |

### 3. Breadcrumb Generation

Breadcrumbs are generated from the folder path and cached in the page response:

```
Home > Marketing > Campaigns > 2024
```

Implementation in `PageService::getBreadcrumb()` traverses parent folders and includes titles.

### 4. Page Moving

Moving a page involves:
1. Validate depth limits at destination
2. Check for circular references
3. Move folder atomically via Nextcloud API
4. Update metadata for moved page and all children
5. Update navigation references

---

## Permissions Matrix

| Path | Admins | Users | Department Team |
|------|--------|-------|-----------------|
| `/public/*` | Read/Write | Read | Read |
| `/departments/marketing/*` | Read/Write | - | Read/Write |
| `/departments/hr/*` | Read/Write | - | Read/Write |

**Legend:** `-` = No access, `Read` = View only, `Read/Write` = Full access

---

## Implementation Phases

### Phase 1: Backend Foundation
- [ ] Add metadata fields to page.json schema
- [ ] Implement depth calculation and validation
- [ ] Add breadcrumb generation to PageService
- [ ] Create migration command for existing pages

### Phase 2: Frontend Components
- [ ] Breadcrumb component (already partially implemented)
- [ ] Parent selector in create page dialog
- [ ] Page tree navigation component
- [ ] Depth limit warnings in UI

### Phase 3: Move Functionality
- [ ] Move page API endpoint
- [ ] Move page dialog component
- [ ] Batch metadata updates for children
- [ ] Navigation path updates

### Phase 4: Permissions Integration
- [ ] OCC commands for ACL configuration
- [ ] Permission checking in PageService
- [ ] UI elements for permission feedback
- [ ] Department management commands

---

## API Endpoints (Proposed)

```
POST /api/pages/{id}/move
  Body: { "newParentPath": "nl/departments/marketing" }

GET /api/pages/{id}/children
  Returns: Array of child pages

GET /api/pages/tree
  Returns: Full page hierarchy tree
```

---

## Migration Strategy

For existing installations:
1. Run migration command: `occ intravox:migrate:nested`
2. Existing pages move to `/public/` folder
3. Metadata fields are populated automatically
4. Navigation references updated
5. Backup created before migration

---

## Open Questions

1. **Cross-department sharing:** Should pages be shareable across departments?
2. **Department creation:** UI-based or OCC command only?
3. **Search scope:** Should search respect department boundaries?
4. **Navigation scope:** Global navigation or per-department?

---

## References

- [Nextcloud ACL Documentation](https://nextcloud.com/blog/access-control-lists/)
- [Groupfolders GitHub](https://github.com/nextcloud/groupfolders)
- See `docs/archived/STRUCTURE_COMPARISON.md` for detailed options analysis
- See `docs/archived/NESTED_PAGES_PROPOSAL.md` for original proposal

---

**Note:** This feature is planned for IntraVox v2.x and is not yet implemented.
